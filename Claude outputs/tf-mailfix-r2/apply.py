#!/usr/bin/env python3
"""tf-mailfix-r2 — e-mail sending fix (r1) + My Profile → Mailbox (r2), for an Avenir copy.

    python3 apply.py check    <avenir root>   what would change (nothing is written)
    python3 apply.py apply    <avenir root>   back up every file it changes to before/<time>/, copy the files, add one line
                                              to app/Config/Routes.php (require Routes.mailbox.php)
    python3 apply.py rollback <avenir root>   undo the newest apply of that copy: its backups put back, the files it added
                                              moved to writable/mailfix_rolled_back_<time>/. Run again to undo the one before.
r1 files: app/Config/CompanyMail.php, app/Libraries/TourFlow/MailServer.php, app/Controllers/Mail/SendController.php,
          app/Libraries/TourFlow/QuoteMail.php, app/Libraries/TourFlow/HotelMail.php
r2 files: app/Controllers/MailboxController.php, app/Config/Routes.mailbox.php, app/Views/pages/_mailbox_modal.php,
          app/Views/pages/my_profile.php (the Mailbox button and box), MailServer.php (the login check)
"""
import hashlib, json, os, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
ROUTES_LINE = "require __DIR__ . '/Routes.mailbox.php'; // tf-mailfix-r2: My Profile -> Mailbox\n"
def md5(p): return hashlib.md5(open(p, 'rb').read()).hexdigest()
def files(): return sorted(os.path.relpath(os.path.join(d, f), SRC) for d, _, fs in os.walk(SRC) for f in fs)
def copy_name(p): return os.path.basename(os.path.normpath(str(p).strip().replace('\\', '/'))).lower()
def patched_routes(text):
    if 'Routes.mailbox.php' in text:
        return text
    nl = '\r\n' if '\r\n' in text else '\n'
    line = ROUTES_LINE.replace('\n', nl)
    for anchor in ("require __DIR__ . '/Routes.help.php';", "require __DIR__ . '/Routes.hotel_rate_v2.php';"):
        if anchor in text:
            i = text.index(anchor); j = text.find('\n', i); j = len(text) if j == -1 else j + 1
            return text[:j] + line + text[j:]
    return text.rstrip('\r\n') + nl + line
def main():
    if len(sys.argv) != 3 or sys.argv[1] not in ('check', 'apply', 'rollback'):
        print(__doc__); sys.exit(2)
    mode, root = sys.argv[1], os.path.abspath(sys.argv[2])
    routes = os.path.join(root, 'app', 'Config', 'Routes.php')
    if not os.path.isfile(routes):
        raise SystemExit('Not an Avenir root: ' + root)
    stamp = time.strftime('%Y%m%d-%H%M%S'); rep = {'mode': mode, 'root': root, 'files': []}
    if mode == 'rollback':
        bdir = os.path.join(KIT, 'before')
        mine = sorted(b for b in (os.listdir(bdir) if os.path.isdir(bdir) else [])
                      if os.path.isfile(os.path.join(bdir, b, 'apply.json')) and os.path.isfile(os.path.join(bdir, b, 'root.txt'))
                      and copy_name(open(os.path.join(bdir, b, 'root.txt'), encoding='utf-8').read()) == copy_name(root))
        if not mine:
            raise SystemExit('No apply of ' + root + ' left to undo in ' + bdir)
        last = os.path.join(bdir, mine[-1]); info = json.load(open(os.path.join(last, 'apply.json'), encoding='utf-8'))
        aside = os.path.join(root, 'writable', 'mailfix_rolled_back_' + stamp)
        for rel in info['changed'] + info.get('patched', []):
            shutil.copy2(os.path.join(last, 'files', rel), os.path.join(root, rel)); rep['files'].append(rel + ' restored')
        for rel in info['added']:
            p = os.path.join(root, rel)
            if os.path.isfile(p):
                os.makedirs(os.path.dirname(os.path.join(aside, rel)), exist_ok=True); shutil.move(p, os.path.join(aside, rel)); rep['files'].append(rel + ' moved aside')
        os.rename(os.path.join(last, 'apply.json'), os.path.join(last, 'apply.rolled-back-' + stamp + '.json'))
        rep['undone'] = mine[-1]
        print(json.dumps(rep, indent=1)); return
    changed, added, same = [], [], []
    for rel in files():
        dst = os.path.join(root, rel)
        (added if not os.path.exists(dst) else (same if md5(dst) == md5(os.path.join(SRC, rel)) else changed)).append(rel)
    rtext = open(routes, encoding='utf-8', newline='').read()
    patch = [] if 'Routes.mailbox.php' in rtext else ['app/Config/Routes.php']
    rep.update(changed=changed, added=added, same=same, patched=patch)
    if mode == 'check':
        print(json.dumps(rep, indent=1)); return
    if not changed and not added and not patch:
        rep['note'] = 'Already in; nothing to do.'; print(json.dumps(rep, indent=1)); return
    bdir = os.path.join(KIT, 'before', stamp); os.makedirs(bdir)
    for rel in changed + patch:
        os.makedirs(os.path.dirname(os.path.join(bdir, 'files', rel)), exist_ok=True); shutil.copy2(os.path.join(root, rel), os.path.join(bdir, 'files', rel))
    open(os.path.join(bdir, 'root.txt'), 'w', encoding='utf-8').write(root + '\n')
    json.dump({'root': root, 'changed': changed, 'added': added, 'patched': patch}, open(os.path.join(bdir, 'apply.json'), 'w', encoding='utf-8'), indent=1)
    for rel in changed + added:
        os.makedirs(os.path.dirname(os.path.join(root, rel)), exist_ok=True); shutil.copy2(os.path.join(SRC, rel), os.path.join(root, rel))
    if patch:
        open(routes, 'w', encoding='utf-8', newline='').write(patched_routes(rtext))
    bad = [rel for rel in files() if md5(os.path.join(root, rel)) != md5(os.path.join(SRC, rel))]
    rep.update(backup=bdir, verified=not bad and 'Routes.mailbox.php' in open(routes, encoding='utf-8').read(), differing=bad)
    print(json.dumps(rep, indent=1))
if __name__ == '__main__':
    main()
