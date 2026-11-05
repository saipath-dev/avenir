#!/usr/bin/env python3
"""tf-pilot-r3 — supplier bank details: the remaining routes closed, the audit trail masked, existing access preserved (11 Oct 2026).
Installs OVER tf-pilot-r2 on the development system. No database change. 10 files changed, none added.
   1. Two more routes served a supplier's FULL bank account number to Hotel Reservations: the Hotel Rate master's supplier
      lookup and hotel search (master_files/hotel_rate/show, get_hotels) and the Driver form (master_files/supplier/
      new_driver_index). Both now give the last four digits (the Driver form also locks the bank fields, as the Supplier
      form does). Found by requesting every GET route of the application as each department test login.
   2. The same masking where the department profiles that neither pay nor verify met full numbers: the hotel lookups used
      while quoting (tour/tour/get_hotel_details, tour/quotation/get_hotel_details, inquiry/get_hotel_details), the
      payment-requested report (tour/tour_accounts/payment_requested_report) and the Bank Payment Check pages
      (finalized records included). Accounts, the Bank Authoriser, the verifier and the administrator see them in full.
   3. The audit trail: an attempted bank change is recorded with the account numbers to their last four digits, and the
      audit_log endpoint masks numbers in recorded values for everyone but the roles that pay or verify.
   4. Existing access kept: profiles that existed before the department structure (group ids up to 28 - Operational,
      Maintenance, Sales, the legacy Auditor, Admin, Management, the Transport desks...) see and edit supplier bank details
      on the legacy screens exactly as before tf-pilot-r2 (r2 had masked and locked them too).

    python3 apply.py check    <avenir root>   what would change (nothing is written)
    python3 apply.py apply    <avenir root>   back up every file it changes to before/<time>/, then copy the files
    python3 apply.py rollback <avenir root>   undo the newest apply of that copy (backups put back)
"""
import hashlib, json, os, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
TESTED_ON = {   # the development system files as they were when this kit was tested (r5 + pilot-r1 + pilot-r2 installed, 11 Oct 2026)
    'app/Controllers/InquiryController.php': '700564b6bc2cc2f234a3b0f6fd49fde2',
    'app/Controllers/MasterFiles/HotelRateController.php': 'a0f501875805c6cd94b542fa2c13bd63',
    'app/Controllers/MasterFiles/SupplierController.php': '294fe1d31572863da0019bfcdc02537f',
    'app/Controllers/Report/BankPaymentCheckController.php': 'b1b1b7b71ae7483fff19c0d62e37791f',
    'app/Controllers/Tour/NewTourController.php': 'a2adb6b675294d77fd5654ed3e797399',
    'app/Controllers/Tour/TourAccountsController.php': 'acb29252075e4633c91fe56f9ebd5c06',
    'app/Controllers/Tour/TourController.php': '522bde4004b660983f465e407687ed47',
    'app/Controllers/Tour/TourPaymentDeskController.php': '32b6406bbfd89dc564ce548c59faecd0',
    'app/Libraries/TourPayments/Mask.php': 'a49234a533b559c634124bb2ab6e7654',
    'app/Views/pages/master_files/new_driver.php': '6c5f8049e876153d90aa76df175acc4a',
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
        aside = os.path.join(root, 'writable', 'pilot_r3_rolled_back_' + stamp)
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
