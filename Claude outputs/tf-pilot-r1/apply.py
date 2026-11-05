#!/usr/bin/env python3
"""tf-pilot-r1 — the common starting page (Pilot), confidential access, read-only auditor (11 Oct 2026). Installs OVER
tf-paydesk-r5 on the development system. 10 files: 8 changed (LoginController, NewDashboardController, tile_dashboard view, Routes (the audit_log route),
UserPermissionFilter, TourPayments/Perms, TourPayments/SupplierBank, Tour/TourPaymentDeskController — refused desk actions recorded,
the read-only audit_log endpoint) and 2 new (Libraries/Pilot.php, dashboard2/pilot_panel.php).
No database change: nothing to set up, nothing to roll back in the database.
   1. Every department lands on dashboard2/tiles (Pilot) after login; the System Administrator keeps /dashboard, drivers their
      portal. The page is open to every signed-in user, but shows only the areas, shortcuts, tasks and reports the user's
      own privileges allow; its data widgets keep their own menu requirements. Landing there grants nothing.
   2. Confidential access on the server (the global permission filter): every refusal is RECORDED (tpd_audit, action
      access_denied: who, what, how, from where); supplier bank account numbers are masked to the last four digits for every
      role that neither pays nor verifies; a payment role without the Tour Payment Dashboard menu (the Bank Authoriser, the
      Auditor) reaches the desk pages its own privilege is about; the Auditor (profile 34, and any profile named read-only /
      auditor) can open what it is granted and change nothing (no POST/PUT/DELETE, no acting GET route).
   3. The administrator is unchanged (group 1 bypasses the filter as before).

    python3 apply.py check    <avenir root>   what would change (nothing is written)
    python3 apply.py apply    <avenir root>   back up every file it changes to before/<time>/, then copy the files
    python3 apply.py rollback <avenir root>   undo the newest apply of that copy (its backups put back; the added files
                                              moved aside to writable/pilot_rolled_back_<time>/).
"""
import hashlib, json, os, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
TESTED_ON = {   # the development system files as they were when this kit was tested (r5 installed, 11 Oct 2026)
    'app/Controllers/LoginController.php': '71116c1b328567ae28f3fe15f59e5eb6',
    'app/Controllers/NewDashboardController.php': '44795f1794653f3fc82f83ac0f91d622',
    'app/Views/pages/dashboard2/tile_dashboard.php': '572f0bc691fddb4301a7570a3cdbf389',
    'app/Filters/UserPermissionFilter.php': '4bc6a23d4f0d55c27c8715f92f4bc44e',
    'app/Libraries/TourPayments/Perms.php': 'dfd14974e1c71fe5f81b74caca8073ab',
    'app/Libraries/TourPayments/SupplierBank.php': 'd3c4518c1c1ffbeb19a5e6d694088a11',
    'app/Controllers/Tour/TourPaymentDeskController.php': '540c0ccfe3a76934307d6a423f30c869',
    'app/Config/Routes.php': '1b857e636bf330c259bb6a3aac8d7ab4',
}
def md5(p): return hashlib.md5(open(p, 'rb').read()).hexdigest()
def files(): return sorted(os.path.relpath(os.path.join(d, f), SRC).replace('\\', '/') for d, _, fs in os.walk(SRC) for f in fs)
def copy_name(p): return os.path.basename(os.path.normpath(str(p).strip().replace('\\', '/'))).lower()
def main():
    if len(sys.argv) != 3 or sys.argv[1] not in ('check', 'apply', 'rollback'):
        print(__doc__); sys.exit(2)
    mode, root = sys.argv[1], os.path.abspath(sys.argv[2])
    if not os.path.isfile(os.path.join(root, 'app', 'Config', 'Routes.php')):
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
        aside = os.path.join(root, 'writable', 'pilot_rolled_back_' + stamp)
        for rel in info['changed']:
            shutil.copy2(os.path.join(last, 'files', rel), os.path.join(root, rel)); rep['files'].append(rel + ' restored')
        for rel in info['added']:
            p = os.path.join(root, rel)
            if os.path.isfile(p):
                os.makedirs(os.path.dirname(os.path.join(aside, rel)), exist_ok=True); shutil.move(p, os.path.join(aside, rel)); rep['files'].append(rel + ' moved aside')
        os.rename(os.path.join(last, 'apply.json'), os.path.join(last, 'apply.rolled-back-' + stamp + '.json'))
        rep['undone'] = mine[-1]
        print(json.dumps(rep, indent=1)); return
    changed, added, same, drifted = [], [], [], []
    for rel in files():
        dst = os.path.join(root, rel)
        if not os.path.exists(dst):
            added.append(rel)
        elif md5(dst) == md5(os.path.join(SRC, rel)):
            same.append(rel)
        elif rel in TESTED_ON and md5(dst) != TESTED_ON[rel]:
            drifted.append(rel)
        else:
            changed.append(rel)
    rep.update(changed=changed, added=added, same=same, changed_since_tested=drifted)
    if drifted:
        rep['note'] = 'Stopped: these files were changed after the kit was tested; nothing was written.'
        print(json.dumps(rep, indent=1)); sys.exit(1)
    if mode == 'check':
        print(json.dumps(rep, indent=1)); return
    if not changed and not added:
        rep['note'] = 'Already in; nothing to do.'; print(json.dumps(rep, indent=1)); return
    bdir = os.path.join(KIT, 'before', stamp)
    n = 1
    while os.path.exists(bdir):   # two applies within the same second (a rehearsal): a distinct backup folder each time
        n += 1; bdir = os.path.join(KIT, 'before', stamp + '-' + str(n))
    os.makedirs(bdir)
    for rel in changed:
        os.makedirs(os.path.dirname(os.path.join(bdir, 'files', rel)), exist_ok=True); shutil.copy2(os.path.join(root, rel), os.path.join(bdir, 'files', rel))
    open(os.path.join(bdir, 'root.txt'), 'w', encoding='utf-8').write(root + '\n')
    json.dump({'root': root, 'changed': changed, 'added': added}, open(os.path.join(bdir, 'apply.json'), 'w', encoding='utf-8'), indent=1)
    for rel in changed + added:
        os.makedirs(os.path.dirname(os.path.join(root, rel)), exist_ok=True); shutil.copy2(os.path.join(SRC, rel), os.path.join(root, rel))
    bad = [rel for rel in files() if md5(os.path.join(root, rel)) != md5(os.path.join(SRC, rel))]
    rep.update(backup=bdir, verified=not bad, differing=bad)
    rep['next'] = 'Nothing to set up: no database change. Sign out and in once; every department now lands on Pilot (dashboard2/tiles). Refusals are recorded in tpd_audit (action access_denied).'
    print(json.dumps(rep, indent=1))
if __name__ == '__main__':
    main()
