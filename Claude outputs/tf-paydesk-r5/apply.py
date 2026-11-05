#!/usr/bin/env python3
"""tf-paydesk-r5 — Bulk supplier payment, separation of duties, the request privilege (11 Oct 2026). Installs OVER tf-paydesk-r4
(schema 6 → 7). What it adds, on top of everything of r4 (which stays as installed):
   1. BULK PAY in Supplier Payables (management): review the supplier's approved items, "Select all eligible (n)", the
      server's exclusion list with reasons (cancelled tour, on hold, disputed, already vouchered/batched, bank not verified or
      changed, USD item without a rate), a confirmation step (count, totals, N vouchers, one transfer, a one-time reference),
      then ONE bulk batch: a SEPARATE Payment Voucher for every item (its own official number, its own tour), all issued in
      one operation — if any voucher cannot be issued, nothing is issued; a repeated confirmation creates nothing twice.
      The accountant initiates the ONE bank transfer for the whole batch (charges allocated to the cent across the vouchers,
      resumable); the bank approver marks the whole batch Paid (every voucher through the r4 Paid: lock, charges, ledger
      check; the batch reconciled only when every voucher is); ONE itemised remittance advice for the transfer.
      Each item keeps its own tour, service date, invoice, voucher number; the legacy screens list the vouchers individually.
   2. SEPARATION OF DUTIES, on the server: a user never approves, batches, initiates or finally authorises (Paid) a payment
      they requested; a user never marks Paid what they initiated. The System Administrator is not exempt. Every refusal is
      an audit entry (separation_blocked).
   3. THE REQUEST PRIVILEGE: a new privilege entry "Tour payment requests (make / cancel)" (tour/tour_payment_dashboard/request)
      created by Setup. Seeing the dashboard no longer lets a user make or cancel payment requests or add expenses — the
      legacy request routes are covered too. GRANT IT to the profiles that make requests (Hotel Reservations, Transport,
      and whoever requests restaurant/other payments) right after Setup, or nobody but the System Administrator can request.
   4. The desk now recognises a voucher CANCELLED on the legacy Voucher Cancellation screen (Aflag 3) as well as a rejected
      one (Aflag 2): it is never initiated, paid or re-used, and it is shown as cancelled (an r4 gap).
   5. The Bank Authoriser role: no code — a group profile created by the administrator (see README § 3).
The voucher tables (voucher, v_inv, invoice_no, payment_request) are NOT converted by this kit (Decision A, deferred): on
MyISAM the bulk issue is undone by hand after a failure and the failure recorded; on InnoDB it is one transaction.
34 files (29 of r4 re-shipped, 5 new): the r4 files are shipped again so the kit is complete; only 12 of them differ.

Before "apply": the r4 kit must be installed (apply.py check tells you if a file is not the r4 version). After "apply": the
System Administrator opens Tour Payment Dashboard once and presses "Set up now" (or POST tour/tour_payment_dashboard/setup)
— schema 7: the new tpd_batch / tpd_request_ext / tpd_remittance columns and indexes, the request privilege entry. No
existing row is touched. Then Settings → Group Privileges: grant "Tour payment requests (make / cancel)" to the requesting
profiles; grant "Bank payment approver" to the Bank Authoriser profile only.

    python3 apply.py check    <avenir root>   what would change (nothing is written)
    python3 apply.py apply    <avenir root>   back up every file it changes to before/<time>/, then copy the files
    python3 apply.py rollback <avenir root>   undo the newest apply of that copy (its backups put back; the added files
                                              moved aside to writable/paydesk_rolled_back_<time>/). The database is NOT
                                              touched by rollback — the schema-7 columns stay (harmless to r4) and the
                                              request privilege entry stays granted as you left it.
"""
import hashlib, json, os, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
TESTED_ON = {   # the r4 files as installed on the development system (the tf-paydesk-r4 kit, 11 Oct 2026)
    'app/Controllers/Tour/TourPaymentDeskController.php': '06a23ffcba1196f9e940f3bbdab4547a',
    'app/Config/Routes.php': 'd0c925b1542be3fc9ce2e73dba478f83',
    'app/Views/pages/tour/tpd_voucher.php': '86ae8b08355d44422cab80d1bf19109b',
    'app/Views/pages/tour/tpd_payables.php': '9814a545683a88aba47201b5464b80b3',
    'app/Filters/UserPermissionFilter.php': 'a78e746cd112e95cb3ec9e9d7cd8fdf8',
    'app/Libraries/TourPayments/Documents.php': '0e99bde5517bdcaa2a64d2461b04b7d4',
    'app/Libraries/TourPayments/PayDesk.php': '2b2d61897dda7df1a460a223dc60b996',
    'app/Libraries/TourPayments/Schema.php': '4e59cdb6caca50c2489c7bfa24a5ba92',
    'app/Libraries/TourPayments/Batches.php': '6a53f2b2d4b2e2d91a422375da30d5fe',
    'app/Libraries/TourPayments/Perms.php': '41000335bb737e237e7aa273d630e18f',
    'app/Controllers/Report/BankPaymentCheckController.php': '335645c7ac7048fb17539d284ff792e9',
    'app/Views/pages/report/bank_payment_check.php': 'f7acd23f313eb0b36236a6d95f713111',
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
        aside = os.path.join(root, 'writable', 'paydesk_rolled_back_' + stamp)
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
    rep['next'] = 'System Administrator: open Tour Payment Dashboard and press "Set up now" (creates the tpd_ tables, columns, privilege entries and the voucher-number guard), grant the privileges under Settings → Group Privileges, then map the Realised Exchange Gain, Realised Exchange Loss and Bank Charges accounts under Tour Payment Dashboard → Settings — see README.txt.'
    print(json.dumps(rep, indent=1))
if __name__ == '__main__':
    main()
