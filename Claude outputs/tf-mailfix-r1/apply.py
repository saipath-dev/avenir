#!/usr/bin/env python3
"""tf-mailfix-r1 — the quotation / hotel-letter email fix, for an Avenir copy.

    python3 apply.py check    <avenir root>   what would change (nothing is written)
    python3 apply.py apply    <avenir root>   back up the 3 files it changes to before/<time>/, then copy the 5 files
    python3 apply.py rollback <avenir root>   put the 3 files back from the newest backup of that copy; the 2 new files
                                              are moved to writable/mailfix_rolled_back_<time>/
Changed:  app/Controllers/Mail/SendController.php, app/Libraries/TourFlow/QuoteMail.php, app/Libraries/TourFlow/HotelMail.php
New:      app/Config/CompanyMail.php, app/Libraries/TourFlow/MailServer.php
"""
import hashlib, json, os, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
def md5(p): return hashlib.md5(open(p, 'rb').read()).hexdigest()
def files(): return sorted(os.path.relpath(os.path.join(d, f), SRC) for d, _, fs in os.walk(SRC) for f in fs)
def main():
    if len(sys.argv) != 3 or sys.argv[1] not in ('check', 'apply', 'rollback'):
        print(__doc__); sys.exit(2)
    mode, root = sys.argv[1], os.path.abspath(sys.argv[2])
    if not os.path.isfile(os.path.join(root, 'app', 'Config', 'Routes.php')):
        raise SystemExit('Not an Avenir root: ' + root)
    stamp = time.strftime('%Y%m%d-%H%M%S'); rep = {'mode': mode, 'root': root, 'files': []}
    name = os.path.basename(os.path.normpath(root)).lower()
    if mode == 'rollback':
        bdir = os.path.join(KIT, 'before')
        mine = sorted(b for b in (os.listdir(bdir) if os.path.isdir(bdir) else []) if os.path.isfile(os.path.join(bdir, b, 'root.txt'))
                      and os.path.basename(os.path.normpath(open(os.path.join(bdir, b, 'root.txt'), encoding='utf-8').read().strip().replace('\\', '/'))).lower() == name)
        if not mine:
            raise SystemExit('No backup of ' + root + ' in ' + bdir)
        last = os.path.join(bdir, mine[-1]); info = json.load(open(os.path.join(last, 'apply.json'), encoding='utf-8'))
        aside = os.path.join(root, 'writable', 'mailfix_rolled_back_' + stamp)
        for rel in info['changed']:
            shutil.copy2(os.path.join(last, 'files', rel), os.path.join(root, rel)); rep['files'].append(rel + ' restored')
        for rel in info['added']:
            p = os.path.join(root, rel)
            if os.path.isfile(p):
                os.makedirs(os.path.dirname(os.path.join(aside, rel)), exist_ok=True); shutil.move(p, os.path.join(aside, rel)); rep['files'].append(rel + ' moved aside')
        print(json.dumps(rep, indent=1)); return
    changed, added, same = [], [], []
    for rel in files():
        dst = os.path.join(root, rel)
        (added if not os.path.exists(dst) else (same if md5(dst) == md5(os.path.join(SRC, rel)) else changed)).append(rel)
    rep.update(changed=changed, added=added, same=same)
    if mode == 'check':
        print(json.dumps(rep, indent=1)); return
    bdir = os.path.join(KIT, 'before', stamp); os.makedirs(bdir)
    for rel in changed:
        os.makedirs(os.path.dirname(os.path.join(bdir, 'files', rel)), exist_ok=True); shutil.copy2(os.path.join(root, rel), os.path.join(bdir, 'files', rel))
    open(os.path.join(bdir, 'root.txt'), 'w', encoding='utf-8').write(root + '\n')
    json.dump({'root': root, 'changed': changed, 'added': added, 'before_md5': {r: md5(os.path.join(root, r)) for r in changed}}, open(os.path.join(bdir, 'apply.json'), 'w', encoding='utf-8'), indent=1)
    for rel in changed + added:
        os.makedirs(os.path.dirname(os.path.join(root, rel)), exist_ok=True); shutil.copy2(os.path.join(SRC, rel), os.path.join(root, rel))
    bad = [rel for rel in files() if md5(os.path.join(root, rel)) != md5(os.path.join(SRC, rel))]
    rep.update(backup=bdir, verified=not bad, differing=bad)
    print(json.dumps(rep, indent=1))
if __name__ == '__main__':
    main()
