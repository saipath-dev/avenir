#!/usr/bin/env python3
"""tf-bankrec-r3 for the DEVELOPMENT system (http://localhost/avenir/), 12 Oct 2026 — approved by Shantha for development
only. Takes development from tf-bankrec-r1 to r3 in one step (r2 + r3 code). Live is refused.

   13 files changed (r1 → r3) and 1 added:
     app/Config/Routes.php                                      +4 routes in the r1 group (3 from r2, correction/reverify)
     app/Controllers/Accounts/DailyBankRecController.php        r2 + r3
     app/Controllers/Accounts/BankReconsillationController.php  the OLD screen: 6 write actions ask the D7 guard first
     app/Libraries/BankRec/Corrections.php, Matching.php, Rec.php, Schema.php, Statements.php
     app/Views/pages/accounts/bankrec/_common.php, account.php, fx.php, index.php
     app/Views/pages/accounts/bank_reconcillation.php           the OLD screen shows the server's reason
     app/Libraries/BankRec/LegacyGuard.php                      (added) the D7 guard — refuses only, never changes a flag
   Database: sql/upgrade-schema-v1-to-v2-development.sql (phpMyAdmin, database avenir) — the r2 schema step: 2 tables and
   columns added to the brc_ tables only. Nothing writes to the ledger (account_runing) or its reconciliation flags.

    python3 apply.py check    <avenir root>   what would change (nothing is written)
    python3 apply.py apply    <avenir root>   back up every file it changes to before/<time>/, then copy the files
    python3 apply.py rollback <avenir root>   put back the r1 files of the newest apply and take LegacyGuard.php out
                                              (then sql/rollback-schema-v2-to-v1-development.sql for the database)

   Guard: the target's app/Config/App.php must have the development base URL (http://localhost/avenir/) exactly; anything
   else (the isolated instance, live) is refused. Every changed file must be exactly the r1 file it replaces (or already
   r3); anything else stops before writing.
"""
import hashlib, json, os, re, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
R2 = {   # the tf-bankrec-r1 files as installed on development (md5) — what this kit replaces
    'app/Config/Routes.php': '921c2ea6c5b994b70c3cf4d65ab33d37',
    'app/Controllers/Accounts/DailyBankRecController.php': '0d96e3038a50d9fbc4fcbe717319aab5',
    'app/Controllers/Accounts/BankReconsillationController.php': '0fdfc868c73756ca9e91a6fff9c7779b',
    'app/Libraries/BankRec/Corrections.php': 'ca42191c995b2045aecbb7da674f1e00',
    'app/Libraries/BankRec/Matching.php': 'f8d46c37e84c5db7164e285ad5cb13ce',
    'app/Libraries/BankRec/Rec.php': 'ed6df57705123a5a29cdd0365a47ad8a',
    'app/Libraries/BankRec/Schema.php': 'd321e7bba1c6bf61585211efc244f278',
    'app/Libraries/BankRec/Statements.php': '72c4017ce254c2d089d7a16bd26c9ff4',
    'app/Views/pages/accounts/bankrec/_common.php': 'bc00c3eccad472ce1c90527997eee521',
    'app/Views/pages/accounts/bankrec/account.php': '17996f0be6cb7319f60ad160ab83b14a',
    'app/Views/pages/accounts/bankrec/fx.php': '195f8a0f99e329df87373aef74e5fdf5',
    'app/Views/pages/accounts/bankrec/index.php': '320355c140da3604cb80ad2b3906c2f1',
    'app/Views/pages/accounts/bank_reconcillation.php': 'b60efa2f9f2b3ff2a5271e57528711c0',
}
ADDED = ['app/Libraries/BankRec/LegacyGuard.php']
REQUIRES = {   # r1 file this kit relies on and does not change
    'app/Libraries/BankRec/Access.php': '660e4f1840873fcc1497bd10c0a3b0a4',
}
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
    alt = path + '.r3-removed-' + stamp
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
    if base != 'http://localhost/avenir/':
        raise SystemExit('Refused: ' + root + ' has the base URL "' + base + '", not the development system (http://localhost/avenir/). '
                         'This kit is approved for development only; live needs a separate approval and kit.')
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
            rep['files'].append(rel + ' restored (' + md5(os.path.join(root, rel)) + (', the r1 file' if md5(os.path.join(root, rel)) == R2.get(rel) else ', NOT the r1 file') + ')')
        for rel in info.get('added', []):
            p = os.path.join(root, rel)
            if os.path.exists(p):
                rep['files'].append(rel + ' ' + take_out(p, os.path.join(last, 'added-removed', os.path.dirname(rel)), stamp))
        os.rename(os.path.join(last, 'apply.json'), os.path.join(last, 'apply.rolled-back-' + stamp + '.json'))
        rep['undone'] = mine[-1]
        bad = [rel for rel in R2 if md5(os.path.join(root, rel)) != R2[rel]] + [rel for rel in ADDED if os.path.exists(os.path.join(root, rel))]
        rep['back_to_r1'] = not bad
        rep['note'] = ('The r1 files are back. Then run sql/rollback-schema-v2-to-v1-development.sql in phpMyAdmin (database avenir) '
                       'to put the reconciliation tables back to schema version 1.')
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
    rep.update(changed=changed, added=added, same=same, not_r1=drifted, r1_files_required_but_different=missing_req)
    if drifted or missing_req:
        rep['note'] = 'Stopped: the target is not tf-bankrec-r1 as installed on development; nothing was written.'
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
    rep['next'] = 'Run sql/upgrade-schema-v1-to-v2-development.sql in phpMyAdmin with the database avenir selected.'
    print(json.dumps(rep, indent=1))
    if bad:
        sys.exit(1)

if __name__ == '__main__':
    main()
