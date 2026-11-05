#!/usr/bin/env python3
"""tf-pilot-r2 — Operations faults fixed, allocation reserved to Operations, supplier bank details protected (11 Oct 2026).
Installs OVER tf-pilot-r1 on the development system. No database change. 13 files: 12 changed, 1 new (TourPayments/Mask.php).
   1. Driver Allocation (dashboard2/transport/driver_allow/tour_transport): opened without a tour (its menu entry) it failed
      with "Undefined array key tour_id"; it now goes to the existing "Tours pending driver allocation" list, where each tour
      links to it. The Crew replacement and Driver advance privilege entries had no page (404); they now open the Transport
      Desk where that work already is (focus hint) — no second workflow.
   2. Allocation (engage a driver / guide, set the vehicle, the days worked) on the Transport Desk needs the Driver
      Allocation privilege (Operations) — or one of the existing allocating groups (Management 21, Transport & Crew Guide
      Allocation Desk 28), which keep exactly what they have. Every refusal is recorded (access_denied).
   3. Supplier bank details: the legacy Supplier master (list, form, lookup) shows the account number to its last four digits
      and keeps the stored bank fields whatever is sent, for every sign-in that neither pays nor verifies; the contact
      details stay editable; attempts are recorded. The Tour Payment Dashboard JSON masks bank numbers the same way, and a
      payment request made from it takes the bank details from the supplier master on the server.

    python3 apply.py check    <avenir root>   what would change (nothing is written)
    python3 apply.py apply    <avenir root>   back up every file it changes to before/<time>/, then copy the files
    python3 apply.py rollback <avenir root>   undo the newest apply of that copy (backups put back; Mask.php moved aside)
"""
import hashlib, json, os, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
TESTED_ON = {   # the development system files as they were when this kit was tested (r5 + pilot-r1 installed, 11 Oct 2026)
    'app/Controllers/NewDashboard/DriverAllocationController.php': '12c20a1300b69389c3a23a57440779f6',
    'app/Controllers/Tour/TourDeskController.php': '3b7c37199344435bdb1e6562dcfc317a',
    'app/Config/Routes.tour_flow.php': '6e114c0543ba8728afec4000a51e94bf',
    'app/Libraries/TourFlow/Desks.php': 'f8f304bc293cf88203bee3e3c8e5951e',
    'app/Views/pages/tour/transport_ops.php': 'c4fae880a63c411d3171d0ac8b2ea48f',
    'app/Controllers/MasterFiles/SupplierController.php': '47671f6e26b9a540be91b5d6a1c85b06',
    'app/Views/pages/master_files/new_supplier.php': 'aa172297c806199c4a5f66edb161f257',
    'app/Controllers/Tour/TourPaymentDashboardController.php': '2c090785a3e989d87fec170dcd1559e2',
    'app/Controllers/Tour/TourAccountsController.php': '98c7b929857cf897c6b273bd13c8b0ce',
    'app/Controllers/Tour/TourPaymentDeskController.php': '88424cd47dc334a366fa6c789d713beb',
    'app/Filters/UserPermissionFilter.php': '56394ea34448ca0bb90a598652d5e16d',
    'app/Libraries/Pilot.php': 'df9a7e38484196a6aa3e88fbb51bd905',
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
        aside = os.path.join(root, 'writable', 'pilot_r2_rolled_back_' + stamp)
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
    rep['next'] = 'Nothing to set up: no database change. Users sign out and in once.'
    print(json.dumps(rep, indent=1))
if __name__ == '__main__':
    main()
