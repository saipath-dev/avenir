#!/usr/bin/env python3
"""tf-session-r1b — "the dashboard disappears after signing in" + a version mark on plugins.js, 12 Oct 2026.
Approved for the DEVELOPMENT system (http://localhost/avenir/); also accepted on the isolated test instance
(http://localhost/avenir_rv/). Live is refused.

   4 files changed, none added, no database change:
     app/Controllers/LoginController.php   the sign-in page no longer ends a session that is still valid
     app/Filters/UserSessionFilter.php     background requests without a valid session get a quiet 401 JSON, no cookie
     public/assets/js/plugins.js           no blank page; red "Your session has ended … Sign in again" notice
     app/Views/layouts/scripts.php         plugins.js?v=<the file's modification time>: browsers load a changed
                                           plugins.js at once (no Ctrl+F5), now and after any later update of it

    python3 apply.py check    <root>   what would change (nothing is written)
    python3 apply.py apply    <root>   back up the 4 files to before/<time>/, then copy the new ones
    python3 apply.py rollback <root>   put back the files of the newest apply of that copy
"""
import hashlib, json, os, re, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
R2 = {   # the files as they are on the development system and the isolated instance today (md5) — what r1 replaces
    'app/Controllers/LoginController.php': 'a93a41e05c62ce93282dce1dc45d981d',
    'app/Filters/UserSessionFilter.php': '4032a302bd7076ca549b238b8d689d9d',
    'public/assets/js/plugins.js': '714426587875cdc648abf17cd38df4f8',
    'app/Views/layouts/scripts.php': '647eeae7476b0dd7d1e0266d6e800f82',
}
ADDED = []
REQUIRES = {}
def md5(p): return hashlib.md5(open(p, 'rb').read()).hexdigest()
def files(): return sorted(os.path.relpath(os.path.join(d, f), SRC).replace('\\', '/') for d, _, fs in os.walk(SRC) for f in fs)
def copy_name(p): return os.path.basename(os.path.normpath(str(p).strip().replace('\\', '/'))).lower()

def take_out(path, keep_dir, stamp):
    """Remove an added file: delete it; if deleting is not allowed, move it into the backup folder; last resort: rename it
    so that it is never loaded (not *.php)."""
    try:
        os.remove(path); return 'deleted'
    except OSError:
        pass
    try:
        os.makedirs(keep_dir, exist_ok=True)
        os.rename(path, os.path.join(keep_dir, os.path.basename(path))); return 'moved to ' + keep_dir
    except OSError:
        pass
    alt = path + '.removed-' + stamp
    os.rename(path, alt); return 'renamed to ' + os.path.basename(alt) + ' (never loaded)'

def main():
    if len(sys.argv) != 3 or sys.argv[1] not in ('check', 'apply', 'rollback'):
        print(__doc__); sys.exit(2)
    mode, root = sys.argv[1], os.path.abspath(sys.argv[2])
    app = os.path.join(root, 'app', 'Config', 'App.php')
    if not os.path.isfile(os.path.join(root, 'app', 'Config', 'Routes.php')) or not os.path.isfile(app):
        raise SystemExit('Not an Avenir root: ' + root)
    m = re.search(r"public\s+string\s+\$baseURL\s*=\s*'([^']*)'", open(app, encoding='utf-8', errors='replace').read())
    base = m.group(1) if m else ''
    if base not in ('http://localhost/avenir/', 'http://localhost/avenir_rv/'):
        raise SystemExit('Refused: ' + root + ' has the base URL "' + base + '". tf-session-r1b is approved for development '
                         '(http://localhost/avenir/) and the isolated test instance only; live needs a separate approval.')
    stamp = time.strftime('%Y%m%d-%H%M%S'); rep = {'mode': mode, 'root': root, 'base_url': base, 'files': []}
    if mode == 'rollback':
        bdir = os.path.join(KIT, 'before')
        mine = sorted(b for b in (os.listdir(bdir) if os.path.isdir(bdir) else [])
                      if os.path.isfile(os.path.join(bdir, b, 'apply.json')) and os.path.isfile(os.path.join(bdir, b, 'root.txt'))
                      and copy_name(open(os.path.join(bdir, b, 'root.txt'), encoding='utf-8').read()) == copy_name(root))
        if not mine:
            raise SystemExit('No apply of ' + root + ' left to undo in ' + bdir)
        last = os.path.join(bdir, mine[-1]); info = json.load(open(os.path.join(last, 'apply.json'), encoding='utf-8'))
        for rel in info['changed']:
            shutil.copy2(os.path.join(last, 'files', rel), os.path.join(root, rel))
            rep['files'].append(rel + ' restored (' + md5(os.path.join(root, rel)) + (', the original file' if md5(os.path.join(root, rel)) == R2.get(rel) else ', NOT the original file') + ')')
        for rel in info.get('added', []):
            p = os.path.join(root, rel)
            if os.path.exists(p):
                rep['files'].append(rel + ' ' + take_out(p, os.path.join(last, 'added-removed', os.path.dirname(rel)), stamp))
        os.rename(os.path.join(last, 'apply.json'), os.path.join(last, 'apply.rolled-back-' + stamp + '.json'))
        rep['undone'] = mine[-1]
        bad = [rel for rel in R2 if md5(os.path.join(root, rel)) != R2[rel]] + [rel for rel in ADDED if os.path.exists(os.path.join(root, rel))]
        rep['back_to_original'] = not bad
        rep['note'] = 'The original files are back. No database step.'
        print(json.dumps(rep, indent=1)); sys.exit(1 if bad else 0)
    changed, added, same, drifted, missing_req = [], [], [], [], []
    for rel, h in REQUIRES.items():
        p = os.path.join(root, rel)
        if not os.path.isfile(p) or md5(p) != h:
            missing_req.append(rel + (' (' + md5(p) + ')' if os.path.isfile(p) else ' (missing)'))
    for rel in files():
        dst = os.path.join(root, rel)
        if not os.path.exists(dst):
            (added if rel in ADDED else drifted).append(rel if rel in ADDED else rel + ' (missing)')
        elif md5(dst) == md5(os.path.join(SRC, rel)):
            same.append(rel)
        elif rel in ADDED or md5(dst) != R2.get(rel):
            drifted.append(rel + ' (' + md5(dst) + ')')
        else:
            changed.append(rel)
    rep.update(changed=changed, added=added, same=same, not_original=drifted, required_but_different=missing_req)
    if drifted or missing_req:
        rep['note'] = 'Stopped: the target files are not the ones this kit was made for; nothing was written.'
        print(json.dumps(rep, indent=1)); sys.exit(1)
    if mode == 'check':
        print(json.dumps(rep, indent=1)); return
    if not changed and not added:
        rep['note'] = 'Already in; nothing to do.'; print(json.dumps(rep, indent=1)); return
    bdir = os.path.join(KIT, 'before', stamp)
    n = 1
    while os.path.exists(bdir):
        n += 1; bdir = os.path.join(KIT, 'before', stamp + '-' + str(n))
    os.makedirs(bdir)
    for rel in changed:
        os.makedirs(os.path.dirname(os.path.join(bdir, 'files', rel)), exist_ok=True)
        shutil.copy2(os.path.join(root, rel), os.path.join(bdir, 'files', rel))
    json.dump({'changed': changed, 'added': added, 'stamp': stamp, 'state': 'copying'}, open(os.path.join(bdir, 'apply.json'), 'w', encoding='utf-8'), indent=1)
    open(os.path.join(bdir, 'root.txt'), 'w', encoding='utf-8').write(root)
    for rel in changed + added:
        os.makedirs(os.path.dirname(os.path.join(root, rel)), exist_ok=True)
        shutil.copy2(os.path.join(SRC, rel), os.path.join(root, rel))
    bad = [rel for rel in changed + added if md5(os.path.join(root, rel)) != md5(os.path.join(SRC, rel))]
    json.dump({'changed': changed, 'added': added, 'stamp': stamp, 'md5': {rel: md5(os.path.join(SRC, rel)) for rel in changed + added}}, open(os.path.join(bdir, 'apply.json'), 'w', encoding='utf-8'), indent=1)
    rep['backup'] = bdir; rep['verified'] = not bad; rep['mismatch'] = bad
    rep['next'] = 'No database step. Pages now load plugins.js?v=<modification time>, so browsers fetch the new file at once; a page left open after signing out shows the red notice instead of going blank.'
    print(json.dumps(rep, indent=1))
    if bad:
        sys.exit(1)

if __name__ == '__main__':
    main()
