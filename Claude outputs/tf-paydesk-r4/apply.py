#!/usr/bin/env python3
"""tf-paydesk-r4 — the Tour Payment & Accounting Workflow (10/11 Oct 2026): Supplier Payables and batches, the payment-date
exchange rate, the four r3 corrections (paying account confirmed by the accountant and the bank approver, Pay Individually
Now, Realised Exchange Gain / Loss mapping with balanced per-tour postings, the official Payment Voucher No. — unique and
immutable), and the r4 items: a configurable BANK CHARGES ledger mapping (existing active account chosen by Accounts),
charges allocated per tour line and posted ONCE at Paid (Dr Bank Charges / Cr paying account) with the ledger read back and
RECONCILED to the bank debit (payment + charges), Paid blocked before any write while a needed mapping is missing, one Paid
at a time per voucher (repeated / concurrent submissions refused), and the voucher-number counter checked before a voucher
is issued. Supersedes r1–r3 (never installed). Extends the existing chain (payment_request → voucher → Bank Payment Check);
18 new files, 11 changed. For an Avenir copy.

Before "apply": run the READ-ONLY preflight (preflight/preflight_vnum.sql or preflight_vnum.py) on the live database and
read its findings (README.txt § Preflight). After "apply": the System Administrator opens Tour Payment Dashboard once and
presses "Set up now" (or POST tour/tour_payment_dashboard/setup) — that creates the tpd_ tables (11), the new columns, the
eight privilege entries, the unique index on voucher.Vnum and its update guard (schema-reference.sql shows the tables). No
existing row is touched. Then grant the privileges under Settings → Group Privileges, add the company's accounts under
Master Files → Avenir Bank Accounts, enter the suppliers' bank details under Master Files → Supplier Bank Details, and —
once Accounts has created them — map the Realised Exchange Gain, Realised Exchange Loss and Bank Charges accounts under
Tour Payment Dashboard → Settings (a foreign-currency Paid, or a Paid with bank charges, is blocked until then).

    python3 apply.py check    <avenir root>   what would change (nothing is written)
    python3 apply.py apply    <avenir root>   back up every file it changes to before/<time>/, then copy the files
    python3 apply.py rollback <avenir root>   undo the newest apply of that copy (its backups put back; the added files
                                              moved aside to writable/paydesk_rolled_back_<time>/). The database is NOT
                                              touched by rollback — see README.txt § Rollback for what that means.
"""
import hashlib, json, os, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
TESTED_ON = {   # the live files as they were when this kit was tested (10 Oct 2026)
    'app/Controllers/Tour/TourPaymentDashboardController.php': '62668ab6113e146239dd63ec8ae1849a',
    'app/Views/pages/tour/tour_payment_dashboard.php': '9f8157f2f076ff939806f0a29e0f7a98',
    'app/Controllers/Tour/TourAccountsController.php': 'fe0e123e9c532560ba3802955b28d30a',
    'app/Controllers/Tour/TourAccountsCheckController.php': '45a0b7f53d20c75b077d22cbc634ceeb',
    'app/Controllers/Report/BankPaymentCheckController.php': '92016d63c7ef6bb931be1eb3dd3cef49',
    'app/Views/pages/report/bank_payment_check.php': 'c08eada4a8635b044ef312dfad037395',
    'app/Config/Routes.php': '209f23dad56bbff3b77b483525bd67ea',
    'app/Filters/UserPermissionFilter.php': 'cba87b9af5f11a1cde0b54b38c5e266e',
    'app/Views/pages/tour/payment_approval_v2.php': '6d948c76f074908e84d527efda28b179',
    'app/Libraries/TourFlow/TourRecord.php': 'f370a9a1bbb1e109d0faa7da8a502e57',
    'app/Views/pages/tour/tour_record.php': 'd87d203ceb345ba221516aa90118e866',
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
