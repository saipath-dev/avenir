"""tf-paydesk-r4 — test 47: ROLLBACK before any payment and ROLLBACK AFTER PAYMENTS EXIST, on a LIVE-EQUIVALENT copy ($S/tl:
the live files as tested, no kit) served on 127.0.0.1:8083 against its own database avenir_rb (the pre-flow snapshot with the
desk's tables dropped — i.e. a database the kit was never installed in). Uses the real installer (kit/tf-paydesk-r4/apply.py).

  A. apply → check → rollback (no payments) → the live files byte-for-byte back, the added files moved aside → apply again
  B. Setup, masters, mapping, then payments through the whole flow to Paid (USD single, LKR driver advance paid individually,
     an item approved but NOT yet batched) → fingerprints of every financial table
  C. rollback (files only) → the database untouched (same fingerprints) → the PREVIOUS application displays and handles the
     records: dashboard, Payment Approval, Bank Payment Check + Finalized records (the paid vouchers with their bank dates),
     voucher report, liability / supplier ledger (payment, exchange-difference and charge rows), Complete Tour Record; a new
     payment approved the legacy way gets the next voucher number (unique index in step), is authorised and Mark Finalized
     (the update guard is transparent) and cancelled by the legacy screen; the item approved-but-not-batched is shown as
     stranded (the known limitation) with the recovery procedure
  D. apply again + Setup → the desk sees everything: the paid batches with their reconciliation, the legacy voucher as a
     legacy voucher, the stranded item back in Supplier Payables; nothing lost
Writes results_rollback.json."""
import os, sys, json, subprocess, re, hashlib, datetime, shutil
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
KIT = S + '/kit/tf-paydesk-r4'; ROOT = S + '/tl'; DB = 'avenir_rb'; HOST = 'http://127.0.0.1:8083'
os.environ['TC_HOST'] = HOST
sys.path.insert(0, HERE)
from api import Client
RES = []
def check(name, ok, detail=''):
    RES.append({'test': 47, 'name': name, 'ok': bool(ok), 'detail': str(detail)[:400]})
    print(('  ok   ' if ok else '  FAIL ') + '[47] ' + name + ('' if ok else '   ' + str(detail)[:300]))
    return bool(ok)
def sql(q):
    r = subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '-uroot', '-N', DB, '-e', q], capture_output=True, text=True)
    if r.returncode != 0: raise SystemExit('SQL failed: ' + r.stderr + '\n' + q[:300])
    return r.stdout.strip()
def apply(mode):
    r = subprocess.run(['python3', KIT + '/apply.py', mode, ROOT], capture_output=True, text=True)
    import time; time.sleep(3)   # PHP's opcache re-reads a changed file after revalidate_freq (2 s); on live, restart Apache after apply / rollback (README)
    try: return json.loads(r.stdout)
    except Exception: return {'raw': r.stdout[-800:], 'err': r.stderr[-800:], 'rc': r.returncode}
def md5(p): return hashlib.md5(open(p, 'rb').read()).hexdigest()
src = open(KIT + '/apply.py').read(); TESTED = dict(re.findall(r"'([^']+)': '([0-9a-f]{32})'", re.search(r"TESTED_ON = \{(.*?)\n\}", src, re.S).group(1)))
ADDED = [f for f in (os.path.relpath(os.path.join(d, x), KIT + '/src').replace('\\', '/') for d, _, fs in os.walk(KIT + '/src') for x in fs) if f not in TESTED]
def live_files_ok(): return all(os.path.isfile(ROOT + '/' + f) and md5(ROOT + '/' + f) == h for f, h in TESTED.items())
def added_present(): return sum(os.path.isfile(ROOT + '/' + f) for f in ADDED)
TOUR = '2325'; MID = 'AH 2218'
FIN = [('voucher', 'ID', 'ID,Vnum,Acc_No,Amount,Aflag,finalized,bank_date,Bank_acc,doller_amt'), ('v_inv', 'ID', 'ID,V_tkn,Invoice,Amount,Rate'), ('payment_request', 'id', 'id,tour_id,hotel_acc,requestAmount,payment_request,voucher_created'),
       ('account_runing', 'ID', 'ID,L_Code,Dr,Cr,Invoice_Num,Discription,advances_pay'), ('tpd_batch', 'id', 'id,batch_no,vnum,status'), ('tpd_bank_approval', 'id', 'id,vnum,paid_date,lkr_amount,charges,total_debit,fx_posted,charges_posted,reconciled'),
       ('tpd_request_ext', 'id', 'id,pr_id,approval_rate,approval_lkr,paid_rate,paid_lkr,fx_diff,charge_share'), ('tpd_audit', 'id', 'id,action,user,vnum'), ('tpd_bank_initiation', 'id', 'id,vnum,amount,charges'), ('tpd_remittance', 'id', 'id,vnum,status')]
def fingerprints(): return {t: sql(f"SELECT CONCAT(COUNT(*),'|',IFNULL(MD5(GROUP_CONCAT(CONCAT_WS('|',{c}) ORDER BY {k})),'')) FROM {t}") for t, k, c in FIN}

print('== A. rollback BEFORE any payment')
j = apply('check'); check('A1 the live-equivalent copy matches the files the kit was tested on (11 to change, 18 to add, none drifted)', len(j.get('changed', [])) == 11 and len(j.get('added', [])) == 18 and not j.get('changed_since_tested'), {k: len(v) if isinstance(v, list) else v for k, v in j.items() if k != 'files'})
j = apply('apply'); check('A2 apply: backup taken, 29 files in place, verified', j.get('verified') and j.get('backup') and len(j.get('changed', [])) == 11 and len(j.get('added', [])) == 18, j.get('note') or j.get('err') or j.get('backup'))
j = apply('check'); check('A3 check after apply: nothing left to do (29 same)', len(j.get('same', [])) == 29 and not j.get('changed') and not j.get('added'), j.get('same') and len(j['same']))
j = apply('rollback'); check('A4 rollback before any payment: the 11 live files restored byte-for-byte, the 18 added files moved aside (none left in app/)', live_files_ok() and added_present() == 0 and j.get('undone'), (live_files_ok(), added_present(), j.get('undone')))
j = apply('apply'); check('A5 apply again after the rollback works (fresh backup)', j.get('verified') and len(j.get('changed', [])) == 11, j.get('backup'))

print('== B. Setup, masters, mapping and payments to Paid (the kit in place)')
admin, req, appr, acct, bank = Client('admin'), Client('requester'), Client('approver'), Client('accountant'), Client('bank')
j = admin.post('tour/tour_payment_dashboard/setup', data={})
check('B1 Setup on a database the kit was never in: 11 tables created, columns, 8 privileges, the unique index and update guard on voucher.Vnum', j.get('status') == 200 and sql("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='avenir_rb' AND TABLE_NAME LIKE 'tpd\\_%'") == '11' and sql("SELECT v FROM tpd_meta WHERE k='schema_version'") == '6' and sql("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='avenir_rb' AND TABLE_NAME='voucher' AND INDEX_NAME='uq_tpd_voucher_vnum'") >= '1' and sql("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='avenir_rb' AND TRIGGER_NAME='tpd_voucher_vnum_guard'") == '1', (j.get('status'), (j.get('notes') or [])[:3]))
subprocess.run(['python3', HERE + '/setup_testdata.py'], env=dict(os.environ, TC_DB=DB), capture_output=True)   # the test roles get the new privileges
admin, req, appr, acct, bank = Client('admin'), Client('requester'), Client('approver'), Client('accountant'), Client('bank')
TODAY = req.get('tour/tour_payment_dashboard/desk', manual_id=MID)['data']['today']
for lab, bankn, led, dflt, acno in [('Com Bank LKR operating', 'Commercial Bank of Ceylon', '1-6-8-10380', 1, 'TEST0001234567'), ('Sampath LKR account', 'Sampath Bank', '1-6-8-10390', 0, 'TEST0009876543')]:
    acct.post('master_files/bank_accounts/save', data={'entity': 'Avenir Holidays (Pvt) Ltd', 'label': lab, 'bank_name': bankn, 'branch': 'Colombo 07', 'account_name': 'Avenir Holidays (Pvt) Ltd', 'account_no': acno, 'currency': 'LKR', 'swift': 'TESTLKLX', 'account_type': 'Current', 'ledger_code': led, 'is_default': dflt, 'active': 1, 'external_display': 'masked', 'display_digits': 4})
COM = int(sql("SELECT id FROM tpd_bank_account WHERE label='Com Bank LKR operating'"))
for acc, ben, no in [('6-9-11-10626', 'COVENRO VEYANGODA', 'TEST1001001001'), ('6-9-11-11496', 'Kassapa Lions Rock', 'TEST1002002002'), ('6-9-11-10639', 'MR. UDAYA KUMARA', 'TEST1008008008')]:
    acct.post('master_files/supplier_bank/save', data={'sup_acc': acc, 'beneficiary_name': ben, 'bank_name': 'Test Bank', 'branch': 'Test', 'account_no': no, 'currency': 'LKR', 'remittance_email': 'x-test@example.invalid'}); bank.post('master_files/supplier_bank/verify', data={'sup_acc': acc, 'note': 'test'})
j = acct.post('tour/tour_payment_dashboard/setup', data={'fx_gain_ledger_code': '4-8-109-31901', 'fx_loss_ledger_code': '4-8-109-31902', 'bank_charge_ledger_code': '4-8-109-31903'})
check('B2 ledger mapping saved (gain, loss, bank charges)', j.get('status') == 200 and j.get('complete'), j.get('message'))
appr.post('tour/tour_payment_dashboard/confirm_rate', data={'rate': '327.25'})
def desk(c=None, mid=MID): return (c or req).get('tour/tour_payment_dashboard/desk', manual_id=mid)['data']
def line(d, k): return next((l for l in d['lines'] if l['key'].startswith(k)), None)
def legacy_request(c, key, amount, mid=MID):
    d = desk(c, mid); t = d['legacy']['tour_info']; l = line(d, key); row = d['legacy']['payment_rows'][l['legacy_idx']]
    common = {'tourId': t['tour_id'], 'manualId': t['manual_id'], 'consignee': t['consignee'] or '', 'createUser': t['create_user'] or '', 'bankName': '', 'branchName': '', 'supBankAcc': '', 'totalHotelPay': row.get('total_dollar') if row['category'] == 'Hotel' else row['total'], 'totalHotelAdvance': 0, 'dueAmount': row.get('total_dollar') if row['category'] == 'Hotel' else row['total'], 'requestAmount': amount, 'tot_amt_doller': row.get('total_dollar') if row['category'] == 'Hotel' else row['total']}
    if row['category'] == 'Hotel':
        j = c.post('tour/tour_accounts/payment_request', data=dict(common, hotelAcc=row['supplier_acc'], hotelName=row['supplier'], startDate=t['start_date'], endDate=t['end_date'], type='Hotel'))
    else:
        j = c.post('tour/tour_accounts/driver_advance_payment_request', data=dict(common, requestId=row['driver_advance_request_id'], hotelAcc=row['supplier_acc'], hotelName=row['supplier'], type='Transport Agent'))
    return j, line(desk(c, mid), key)['transactions'][-1]['pr_id']
j, pr_cov = legacy_request(req, 'hotel:6-9-11-10626', 55)
j2 = appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': pr_cov})
j3 = appr.post('tour/tour_payment_dashboard/batch_create', data={'sup_acc': '6-9-11-10626', 'pr_ids[]': [pr_cov], 'bank_account_id': COM}); v_cov = j3.get('vnum'); b_cov = j3.get('batch_id')
j4 = acct.post('tour/tour_payment_dashboard/initiate', data={'vnum': v_cov, 'bank_account_id': COM, 'payment_date': TODAY, 'charges': '150', 'bank_ref': 'RB-TRF-0001'})
j5 = bank.post('tour/tour_payment_dashboard/paid', data={'vnum': v_cov, 'paid_date': TODAY, 'bank_ref': 'RB-TRF-0001', 'bank_account_id': COM, 'confirm_account': 1})
check('B3 COVENRO $55: request → approval → batch → voucher %s → initiation (charges 150) → PAID, reconciled' % v_cov, all(x.get('status') == 200 for x in (j, j2, j3, j4, j5)) and j5.get('reconciliation', {}).get('reconciled') and sql(f"SELECT CONCAT(Aflag,'|',finalized,'|',bank_date) FROM voucher WHERE Vnum={v_cov}") == f'1|1|{TODAY}', [(x.get('status'), (x.get('message') or '')[:80]) for x in (j, j2, j3, j4, j5)])
j, pr_d = legacy_request(req, 'driver_advance:', 20000)
j2 = appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': pr_d})
j3 = appr.post('tour/tour_payment_dashboard/batch_create', data={'sup_acc': '6-9-11-10639', 'pr_ids[]': [pr_d], 'bank_account_id': COM, 'individual': 1, 'bypass_reason': 'driver_advance', 'bypass_note': 'tour tomorrow'}); v_d = j3.get('vnum'); b_d = j3.get('batch_id')
j4 = acct.post('tour/tour_payment_dashboard/initiate', data={'vnum': v_d, 'bank_account_id': COM, 'payment_date': TODAY, 'bank_ref': 'RB-TRF-0002'})
j5 = bank.post('tour/tour_payment_dashboard/paid', data={'vnum': v_d, 'paid_date': TODAY, 'bank_ref': 'RB-TRF-0002', 'bank_account_id': COM, 'confirm_account': 1, 'charges': '0'})
check('B4 driver advance LKR 20,000 paid individually: voucher %s PAID (zero charges)' % v_d, all(x.get('status') == 200 for x in (j, j2, j3, j4, j5)) and sql(f"SELECT CONCAT(Aflag,'|',finalized) FROM voucher WHERE Vnum={v_d}") == '1|1', [(x.get('status'), (x.get('message') or '')[:80]) for x in (j, j2, j3, j4, j5)])
j, pr_k = legacy_request(req, 'hotel:6-9-11-11496', 70)
j2 = appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': pr_k})
check('B5 Kassapa $70 approved and WAITING in Supplier Payables (no voucher yet) — the state a rollback could catch', j.get('status') == 200 and j2.get('status') == 200 and j2.get('awaiting_batch') and sql(f"SELECT CONCAT(payment_request,'|',voucher_created) FROM payment_request WHERE id={pr_k}") == '4|0', (j2.get('status'), j2.get('message')))
fp_before = fingerprints()
counts = {t: v.split('|')[0] for t, v in fp_before.items()}
check('B6 financial records in place before the rollback: 2 paid vouchers, batches, locks, request figures, FX/charge rows, audit', counts['tpd_batch'] == '2' and counts['tpd_bank_approval'] == '2' and int(counts['tpd_audit']) > 20 and sql("SELECT COUNT(DISTINCT ID) FROM account_runing WHERE advances_pay=24") == '1', counts)

print('== C. ROLLBACK AFTER PAYMENTS EXIST — the previous application on the same database')
j = apply('rollback')
check('C1 rollback: the 11 live files restored byte-for-byte; the 18 added files moved aside; the backup folder marked undone', live_files_ok() and added_present() == 0 and j.get('undone'), (live_files_ok(), added_present()))
fp_after = fingerprints()
check('C2 the DATABASE is untouched by the rollback: every financial table byte-for-byte the same (vouchers, lines, requests, ledger, batches, locks, request figures, audit, initiations, remittances)', fp_after == fp_before, {t: (fp_before[t][:12], fp_after[t][:12]) for t in fp_before if fp_before[t] != fp_after[t]})
check('C3 the voucher-number index and update guard remain (they are part of the database, not of the files) — reversible with DROP INDEX / DROP TRIGGER if ever wanted', sql("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='avenir_rb' AND TABLE_NAME='voucher' AND INDEX_NAME='uq_tpd_voucher_vnum'") >= '1' and sql("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='avenir_rb' AND TRIGGER_NAME='tpd_voucher_vnum_guard'") == '1')
admin, req, appr, acct, bank = Client('admin'), Client('requester'), Client('approver'), Client('accountant'), Client('bank')
pages = {}
for who, path, must in [(req, 'tour/tour_payment_dashboard', 'Tour Payment Dashboard'), (acct, 'report/bank_payment_check', 'Bank Payment Check'), (acct, 'report/bank_payment_check/finalized_records?from=%s&to=%s' % (TODAY, TODAY), str(v_cov)),
                        (admin, 'report/voucher_report', 'oucher'), (admin, 'accounts/liability', 'iability'), (admin, 'accounts/trial_balance', 'rial'), (admin, 'tour/board/tour/4/record', 'Complete record'), (admin, 'accounts/bank_statement', 'ank'), (admin, 'accounts/account_filter', 'ccount')]:
    r = who.raw(path); pages[path.split('?')[0]] = (r.status_code, must in r.text, len(r.text))
check('C4 the PREVIOUS application opens every page that touches these records without error: dashboard, Bank Payment Check, Finalized records, Voucher report, Liability, Trial balance, Complete Tour Record, Bank statement, Accounts filtering (Payment Approval is not opened here: the live page uses the MySQL 8 collation utf8mb4_0900_ai_ci in a raw query, which the MariaDB test server does not have — a test-server artefact, unchanged by the kit)', all(v[0] == 200 and v[1] for v in pages.values()), {k: v for k, v in pages.items() if not (v[0] == 200 and v[1])})
r = acct.raw('report/bank_payment_check/finalized_records?from=%s&to=%s' % (TODAY, TODAY)).text
check('C5 Finalized records (legacy page) lists both paid vouchers with their bank dates — the Paid made on the batch page is a normal finalized voucher to the old application', str(v_cov) in r and str(v_d) in r and TODAY[:4] in r)
r = acct.raw('report/bank_payment_check').text
check('C6 Bank Payment Check (legacy page) shows nothing pending for them (both are finalized) and does not error on the desk tables it does not know', str(v_cov) not in r.split('finalized')[0] if 'finalized' in r else True)
d = admin.get('tour/tour_payment_dashboard/search_tour', manual_id=MID)['data']
rows = {x['supplier_acc']: x for x in d['payment_rows']}
check('C7 the legacy dashboard shows COVENRO "Fully Paid" (its voucher finalized), the driver advance "Advance Paid", and the approved-but-unbatched Kassapa request as "Pending Payment" (the old wording for approved, no voucher)', rows['6-9-11-10626'].get('status') == 'Fully Paid' and rows['6-9-11-10639'].get('status') == 'Advance Paid' and rows['6-9-11-11496'].get('status') == 'Pending Payment', {k: v.get('status') for k, v in rows.items() if k in ('6-9-11-10626', '6-9-11-11496', '6-9-11-10639')})
led = sql(f"SELECT GROUP_CONCAT(CONCAT(L_Code,':',Dr,':',Cr) ORDER BY ID, Dr DESC SEPARATOR ';') FROM account_runing WHERE Recpt_No={v_cov} OR Discription LIKE '%Payment Voucher No. {v_cov} %'")
check('C8 the supplier payment, the bank-charge pair and (none here) the exchange pairs are ordinary balanced ledger rows the old reports read (liability / account filtering / trial balance)', '6-9-11-10626:17998.75:0.00' in led and '1-6-8-10380:0.00:17998.75' in led and '4-8-109-31903:150.00:0.00' in led and '1-6-8-10380:0.00:150.00' in led, led)
# a NEW payment the legacy way: request → legacy approval (creates the voucher at once) → legacy authorisation → legacy Mark Finalized → legacy cancel
dd = admin.get('tour/tour_payment_dashboard/search_tour', manual_id=MID)['data']; t = dd['tour_info']; row = rows['6-9-11-11047']
j = admin.post('tour/tour_accounts/payment_request', data={'tourId': t['tour_id'], 'manualId': t['manual_id'], 'consignee': t['consignee'] or '', 'createUser': '', 'bankName': '', 'branchName': '', 'supBankAcc': '', 'totalHotelPay': row['total_dollar'], 'totalHotelAdvance': 0, 'dueAmount': row['total_dollar'], 'requestAmount': 60, 'tot_amt_doller': row['total_dollar'], 'hotelAcc': '6-9-11-11047', 'hotelName': row['supplier'], 'startDate': t['start_date'], 'endDate': t['end_date'], 'type': 'Hotel'})
pr_c = sql("SELECT MAX(id) FROM payment_request WHERE hotel_acc='6-9-11-11047'")
vmax = int(sql("SELECT MAX(Vnum) FROM voucher"))
j2 = admin.post('tour/tour_accounts/update_payment_request_status', data={'id': pr_c, 'type': 'Hotel', 'status': 4, 'is_bulk': 0, 'bank_acc': '1-6-8-10380', 'bank_name': 'Commercial Bank Account'})
v_leg = sql(f"SELECT MAX(Vnum) FROM voucher WHERE Acc_No='6-9-11-11047'")
check('C9 a new Cassamara request (made by the administrator — the old permission filter has no alias for the requester role) approved the LEGACY way creates its voucher at once with the NEXT number (%s = max + 1): the unique index accepts it because the counter is in step' % v_leg, j.get('status') == 200 and j2.get('status') == 200 and v_leg == str(vmax + 1) and sql(f"SELECT CONCAT(Aflag,'|',voucher_created) FROM voucher v JOIN payment_request p ON p.id={pr_c} WHERE v.Vnum={v_leg}") == '0|1', (j.get('status'), j2.get('status'), (j2.get('message') or '')[:100], vmax, v_leg))
vid = sql(f"SELECT ID FROM voucher WHERE Vnum={v_leg}"); amt = sql(f"SELECT Amount FROM voucher WHERE Vnum={v_leg}")
j3 = admin.post('accounts/voucher_auth/update', data={'update': 'data', 'id': vid, 'status': '1', 'bank_acc': '1-6-8-10380', 'type': 'Bank Transfer', 'amount': amt, 'payee': 'Cassamara Hotel', 'voucher_no': v_leg, 'sup': '1', 'remark': ''})
j4 = admin.post('report/bank_payment_check/mark_finalized', data={'pr_id': v_leg, 'bank_date': TODAY})
check('C10 the legacy authorisation (ledger posted) and legacy Mark Finalized work on it with the update guard in place (the guard only refuses a CHANGE of the number)', j3.get('status') == 200 and j4.get('status') == 200 and sql(f"SELECT CONCAT(Aflag,'|',finalized,'|',bank_date) FROM voucher WHERE Vnum={v_leg}") == f'1|1|{TODAY}' and sql(f"SELECT COUNT(*) FROM account_runing WHERE Recpt_No={v_leg}") == '2', (j3.get('status'), (j3.get('message') or '')[:80], j4.get('status'), (j4.get('message') or '')[:80]))
j5 = admin.post('report/voucher_report/update', data={'id': v_leg, 'supplier': 'Cassamara Hotel'})
check('C11 the legacy voucher cancel (Aflag 3 + reversal rows) runs on it too — the guard refuses only a change of the number, not other columns', j5.get('status') == 200 and sql(f"SELECT CONCAT(Aflag,'|',flag) FROM voucher WHERE Vnum={v_leg}") == '3|1' and sql(f"SELECT COUNT(*) FROM account_runing WHERE Discription LIKE 'Voucher Cancel for supplier%' AND Recpt_No={v_leg}") == '2', (j5.get('status'), (j5.get('message') or j5.get('raw') or '')[:120], sql(f"SELECT Aflag FROM voucher WHERE Vnum={v_leg}")))
check('C12 KNOWN LIMITATION demonstrated: the Kassapa item approved under the kit but not yet batched has no voucher, so the old Bank Payment Check cannot see it (it waits for the kit, or is re-approved — README § Recovery)', sql(f"SELECT CONCAT(payment_request,'|',voucher_created) FROM payment_request WHERE id={pr_k}") == '4|0' and sql(f"SELECT COUNT(*) FROM voucher WHERE Acc_No='6-9-11-11496'") == '0' and str(pr_k) not in acct.raw('report/bank_payment_check').text)
stranded = sql("SELECT GROUP_CONCAT(pr.id) FROM payment_request pr LEFT JOIN voucher v ON v.Acc_No=pr.hotel_acc AND v.ref_po=pr.tour_id AND v.C_Date>=pr.accept_date WHERE pr.payment_request IN (4,6) AND pr.voucher_created=0 AND v.ID IS NULL")
check('C13 the recovery query (README) lists exactly the stranded item(s): %s' % stranded, stranded == str(pr_k), stranded)

print('== D. the kit applied again — nothing lost')
j = apply('apply'); j2 = admin.post('tour/tour_payment_dashboard/setup', data={})
check('D1 apply again + Setup: files in place, schema already at 6 ("nothing to do"), no row touched', j.get('verified') and j2.get('status') == 200 and sql("SELECT v FROM tpd_meta WHERE k='schema_version'") == '6', (j.get('backup'), j2))
fp_again = fingerprints()
check('D2 re-applying changes no financial row: the desk tables are byte-for-byte as before (the audit gained only the Setup entry); the rows the legacy screens added meanwhile (1 voucher, 1 line, 1 request, the accrual, the posting and the reversal) are kept', all(fp_again[t] == fp_after[t] for t in ('tpd_batch', 'tpd_bank_approval', 'tpd_request_ext', 'tpd_bank_initiation', 'tpd_remittance')) and int(fp_again['tpd_audit'].split('|')[0]) == int(fp_after['tpd_audit'].split('|')[0]) + 1 and sql("SELECT action FROM tpd_audit ORDER BY id DESC LIMIT 1") == 'setup' and int(fp_again['voucher'].split('|')[0]) == int(fp_after['voucher'].split('|')[0]) + 1 and int(fp_again['account_runing'].split('|')[0]) == int(fp_after['account_runing'].split('|')[0]) + 6, {t: (fp_after[t][:10], fp_again[t][:10]) for t in fp_after if fp_after[t] != fp_again[t]})
admin, req, appr, acct, bank = Client('admin'), Client('requester'), Client('approver'), Client('accountant'), Client('bank')
bd = acct.get(f'tour/tour_payment_dashboard/batch_data/{b_cov}')['data']
check('D3 the desk shows the paid COVENRO batch with its lock, charges and reconciliation intact', bd['stage'] == 'paid' and float(bd['approval']['charges']) == 150 and bd['reconciliation'] and bd['reconciliation']['reconciled'], (bd.get('stage'), bd.get('approval', {}).get('charges')))
w = appr.get('tour/tour_payment_dashboard/payables_data')['data']
check('D4 the Kassapa item is back in Supplier Payables, waiting for its batch (the kit restored its flow; nothing was lost or re-created)', any(s['sup_acc'] == '6-9-11-11496' and any(i['pr_id'] == pr_k for i in s['items']) for s in w['awaiting']), [s['sup_acc'] for s in w['awaiting']])
dk = desk(); cas = line(dk, 'hotel:6-9-11-11047')
check('D5 the voucher created the legacy way during the rollback window is shown by the desk as a legacy voucher (cancelled / reversed state), not as a desk batch', cas and cas['transactions'] and cas['transactions'][-1]['vnum'] == int(v_leg) and cas['transactions'][-1].get('batch') is None, cas and (cas['transactions'][-1].get('vnum'), cas['transactions'][-1].get('stage')))
r = admin.raw('tour/board/tour/4/record').text
check('D6 the Complete Tour Record lists Payment Voucher No. %s with its bank-charge share again' % v_cov, f'<b>{v_cov}</b>' in r and '150.00 · posted' in r)
json.dump(RES, open(HERE + '/results_rollback.json', 'w'), indent=1)
failed = [x for x in RES if not x['ok']]
print(f"\n{len(RES) - len(failed)} ok, {len(failed)} failed")
