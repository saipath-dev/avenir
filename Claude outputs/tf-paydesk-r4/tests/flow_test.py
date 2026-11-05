"""tf-paydesk-r4 — Shantha's 22 tests + the Supplier Payables / batch / payment-date-rate tests (23–30) + retests 31–40 (r3) +
the bank-charge / reconciliation / repeated-Paid tests 41–46 (r4; 47 = the rollback test, rollback_test.py), run against the TEST COPY
(8081, avenir_test) through the real endpoints as the four payment roles. Prints each check and writes results.json. The test
database is left in its demonstration state afterwards.

Flow under test:  Request → Approval (no voucher; the approval figures are written once) → Supplier Payables → batch (management)
                  → voucher in the accountant's queue → bank initiation at the payment-date rate → Paid (final rate locked) → advice."""
import os, sys, json, subprocess, time, glob, re, email
from email import policy
from api import Client, BASE
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
SINK = S + '/smtpsink/msgs'
RES = []
def check(test, name, ok, detail=''):
    RES.append({'test': test, 'name': name, 'ok': bool(ok), 'detail': str(detail)[:400]})
    print(('  ok   ' if ok else '  FAIL ') + f'[{test}] ' + name + ('' if ok else '   ' + str(detail)[:300]))
    return bool(ok)
def sql(q):
    r = subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '-uroot', '-N', 'avenir_test', '-e', q], capture_output=True, text=True)
    if r.returncode != 0: raise SystemExit('SQL failed: ' + r.stderr + '\n' + q[:300])
    return r.stdout.strip()
TOUR = '2325'; MID = 'AH 2218'; TOUR2 = '2390'; MID2 = 'AH 2290'
req, appr, acct, bank, admin = Client('requester'), Client('approver'), Client('accountant'), Client('bank'), Client('admin')
def desk(c=None, mid=MID): return (c or req).get('tour/tour_payment_dashboard/desk', manual_id=mid)['data']
def line(d, key_prefix): return next((l for l in d['lines'] if l['key'].startswith(key_prefix)), None)
def payables(c=None, **kw): return (c or appr).get('tour/tour_payment_dashboard/payables_data', **kw)['data']
def waiting(sup, c=None):
    for s in payables(c)['awaiting']:
        if s['sup_acc'] == sup: return s
    return None
def batch_of(vnum): return int(sql(f"SELECT id FROM tpd_batch WHERE vnum={vnum}") or 0)
def bdata(bid, c=None, date=None): return (c or acct).get(f'tour/tour_payment_dashboard/batch_data/{bid}', **({'date': date} if date else {}))['data']
def legacy_request(c, l, amount, extra=None, mid=MID):
    """the chain's own request endpoint, as the dashboard calls it (tour_accounts/payment_request / driver_advance_payment_request)"""
    d = desk(c, mid); t = d['legacy']['tour_info']
    l = next((x for x in d['lines'] if x['key'] == l['key']), l)   # the line as THIS desk response maps it (the legacy row order is not stable between two reads)
    row = d['legacy']['payment_rows'][l['legacy_idx']]
    common = {'tourId': t['tour_id'], 'manualId': t['manual_id'], 'consignee': t['consignee'] or '', 'createUser': t['create_user'] or '', 'bankName': row.get('bank_name') or '', 'branchName': row.get('branch_name') or '', 'supBankAcc': row.get('supplier_bank_acc') or '',
              'totalHotelPay': row.get('total_dollar') if row['category'] == 'Hotel' else row['total'], 'totalHotelAdvance': row.get('paid') or 0, 'dueAmount': row.get('total_dollar') if row['category'] == 'Hotel' else row['total'], 'requestAmount': amount, 'tot_amt_doller': row.get('total_dollar') if row['category'] == 'Hotel' else row['total']}
    if row['category'] == 'Hotel':
        url = 'tour/tour_accounts/payment_request'; p = dict(common, hotelAcc=row['supplier_acc'], hotelName=row['supplier'], startDate=t['start_date'], endDate=t['end_date'], type='Hotel')
    elif row['category'] == 'Activity':
        url = 'tour/tour_accounts/payment_request'; p = dict(common, hotelAcc=row['supplier_acc'], hotelName=row['supplier'], activityId=row['activity_id'], type='lunch')
    else:
        url = 'tour/tour_accounts/driver_advance_payment_request'; p = dict(common, requestId=row['driver_advance_request_id'], hotelAcc=row['supplier_acc'], hotelName=row['supplier'], type='Transport Agent')
    j = c.post(url, data=p)
    if j.get('status') == 200:
        typ = 'Hotel' if row['category'] == 'Hotel' else ('lunch' if row['category'] == 'Activity' else 'Transport Advance')
        meta = {'tour_id': t['tour_id'], 'acc': row['supplier_acc'], 'type': typ}; meta.update(extra or {})
        c.post('tour/tour_payment_dashboard/request_meta', data=meta)
    return j
def approve(c, pr_id, **extra):
    return c.post('tour/tour_payment_dashboard/approve', data=dict({'pr_id': pr_id}, **extra))
def make_batch(c, sup, ids, acc_id, **extra):
    return c.post('tour/tour_payment_dashboard/batch_create', data=dict({'sup_acc': sup, 'pr_ids[]': ids, 'bank_account_id': acc_id}, **extra))
def initiate(c, vnum, acc_id, date, **extra):
    return c.post('tour/tour_payment_dashboard/initiate', data=dict({'vnum': vnum, 'bank_account_id': acc_id, 'payment_date': date}, **extra))
def paid(c, vnum, date, ref, acc_id=None, confirm=1, **extra):
    """the bank approver's Paid: the paying account is shown and CONFIRMED (the initiation's unless another is given)"""
    if acc_id is None:
        acc_id = sql(f"SELECT bank_account_id FROM tpd_bank_initiation WHERE vnum={vnum}") or ''
    body = {'vnum': vnum, 'paid_date': date, 'bank_ref': ref, 'bank_account_id': acc_id}
    if confirm: body['confirm_account'] = 1
    return c.post('tour/tour_payment_dashboard/paid', data=dict(body, **extra))
def fx_pairs(vnum):
    """the exchange-difference postings of a voucher: (tour, Dr account, Cr account, amount) per balanced pair"""
    out = sql(f"SELECT GROUP_CONCAT(CONCAT(Invoice_Num,':',L_Code,':',Dr,':',Cr) ORDER BY ID, Dr DESC SEPARATOR ';') FROM account_runing WHERE advances_pay=23 AND Discription LIKE '%Payment Voucher No. {vnum} %'")
    return '' if out == 'NULL' else out
def charge_pairs(vnum):
    """the bank-charge postings of a voucher: (tour, Dr account, Cr account, amount) per balanced pair"""
    out = sql(f"SELECT GROUP_CONCAT(CONCAT(Invoice_Num,':',L_Code,':',Dr,':',Cr) ORDER BY ID, Dr DESC SEPARATOR ';') FROM account_runing WHERE advances_pay=24 AND Discription LIKE '%Payment Voucher No. {vnum} %'")
    return '' if out == 'NULL' else out
def ledger_counts():
    return sql("SELECT CONCAT(COUNT(*),'|',COALESCE(MAX(ID),0),'|',COALESCE(SUM(Dr),0),'|',COALESCE(SUM(Cr),0)) FROM account_runing")
def recon(vnum):
    """the reconciliation as the batch page shows it: expected = actual on every check"""
    return bdata(batch_of(vnum), bank)['reconciliation']
def approval_fingerprint():
    return sql("SELECT MD5(GROUP_CONCAT(CONCAT_WS('|', pr_id, amount, approval_rate, approval_lkr, approval_rate_date, approved_by, approved_at) ORDER BY pr_id)) FROM tpd_request_ext WHERE approval_rate IS NOT NULL OR approval_lkr IS NOT NULL")

# ---- historical protection: fingerprints of what exists before the test ----
pre = {t: sql(f"SELECT COUNT(*), COALESCE(MAX({k}),0), MD5(GROUP_CONCAT(CONCAT_WS('|',{cols}) ORDER BY {k})) FROM {t}") for t, k, cols in [
    ('voucher', 'ID', 'ID,Vnum,Acc_No,Amount,Aflag,finalized,bank_date,Bank_acc'), ('v_inv', 'ID', 'ID,V_tkn,Invoice,Amount,Rate'), ('payment_request', 'id', 'id,tour_id,hotel_acc,requestAmount,payment_request,voucher_created'), ('account_runing', 'ID', 'ID,L_Code,Dr,Cr,Invoice_Num')]}
pre_max = {t: int(v.split('\t')[1]) for t, v in pre.items()}
print('pre-existing rows:', {t: v.split('\t')[0] for t, v in pre.items()})
# the application's "today" (Asia/Colombo) and the two days before it; the rates of those earlier days are test data
# (D2 = 325.00, D1 = 328.00) — today's is confirmed on the dashboard below
import datetime
TODAY = req.get('tour/tour_payment_dashboard/desk', manual_id=MID)['data']['today']
D1 = (datetime.date.fromisoformat(TODAY) - datetime.timedelta(days=1)).isoformat(); D2 = (datetime.date.fromisoformat(TODAY) - datetime.timedelta(days=2)).isoformat()
sql(f"DELETE FROM daily_usd_rate WHERE rate_date IN ('{D1}','{D2}')")
sql(f"INSERT IGNORE INTO daily_usd_rate (rate_date, rate, created_user, created_at) VALUES ('{D2}', 325.00, 'tcpayappr', '{D2} 09:00:00'), ('{D1}', 328.00, 'tcpayappr', '{D1} 09:00:00')")
print('today (app):', TODAY, '| earlier days:', D2, '325.00 ·', D1, '328.00')

# ============ preparation: masters ============
print('\n== Avenir Bank Account Master (test 9 prep)')
acc_ids = {}
for lab, bankn, led, dflt, ext, acno in [('Com Bank LKR operating', 'Commercial Bank of Ceylon', '1-6-8-10380', 1, 'masked', 'TEST0001234567'), ('Sampath LKR account', 'Sampath Bank', '1-6-8-10390', 0, 'hidden', 'TEST0009876543')]:
    j = acct.post('master_files/bank_accounts/save', data={'entity': 'Avenir Holidays (Pvt) Ltd', 'label': lab, 'bank_name': bankn, 'branch': 'Colombo 07', 'account_name': 'Avenir Holidays (Pvt) Ltd', 'account_no': acno, 'currency': 'LKR', 'swift': 'TESTLKLX', 'account_type': 'Current', 'ledger_code': led, 'is_default': dflt, 'active': 1, 'external_display': ext, 'display_digits': 4})
    acc_ids[lab] = j.get('id')
existing = {r.split('\t')[1]: int(r.split('\t')[0]) for r in sql("SELECT id, label FROM tpd_bank_account").split('\n') if r}
acc_ids = {k: existing.get(k, v) for k, v in acc_ids.items()}
COM, SAM = acc_ids['Com Bank LKR operating'], acc_ids['Sampath LKR account']
check(9, 'two Avenir accounts in the master (one default, one with hidden external display)', len(existing) >= 2 and sql("SELECT COUNT(*) FROM tpd_bank_account WHERE is_default=1") == '1', existing)
j = req.post('master_files/bank_accounts/save', data={'label': 'x', 'bank_name': 'x', 'account_name': 'x', 'account_no': '1'})
check(14, 'a requester cannot save a company bank account (403)', j.get('status') == 403, j)
d0 = desk()
check(9, 'the dashboard offers the master\'s accounts only (no free typing)', d0['bank_accounts']['source'] == 'master' and len(d0['bank_accounts']['accounts']) == 2, d0['bank_accounts']['source'])
check(0, 'the desk reports Supplier Payables in place (schema 4, accumulation on)', d0['ready'] and d0['accumulates'], (d0['ready'], d0.get('accumulates')))

print('\n== Supplier bank details (test 10 prep)')
sups = {'6-9-11-10626': ('COVENRO VEYANGODA', 'TEST1001001001', 'covenro-test@example.invalid'), '6-9-11-11496': ('Kassapa Lions Rock', 'TEST1002002002', 'kassapa-test@example.invalid'), '6-9-11-11047': ('Cassamara Hotel', 'TEST1003003003', 'cassamara-test@example.invalid'),
        '6-9-11-11392': ('MORVEN COLOMBO', 'TEST1004004004', 'morven-test@example.invalid'), '6-9-11-11590': ('GIMANHALA LUNCH VOUCHER', 'TEST1005005005', 'gimanhala-test@example.invalid'), '6-9-23-11075': ('Fright Less Jeep Safari Minneriya', 'TEST1006006006', 'frightless-test@example.invalid'),
        '6-9-11-11617': ('Fright Less entrance fees', 'TEST1007007007', 'frightless-ent-test@example.invalid'), '6-9-11-10639': ('MR. UDAYA KUMARA', 'TEST1008008008', '')}
for acc, (ben, no, mail) in sups.items():
    j = acct.post('master_files/supplier_bank/save', data={'sup_acc': acc, 'beneficiary_name': ben, 'bank_name': 'Test Bank', 'branch': 'Test branch', 'account_no': no, 'currency': 'LKR', 'swift': '', 'invoice_email': mail, 'remittance_email': mail})
    assert j.get('status') == 200, j
for acc in ['6-9-11-10626', '6-9-11-11047', '6-9-11-11392', '6-9-11-11590', '6-9-23-11075', '6-9-11-11617']:
    j = bank.post('master_files/supplier_bank/verify', data={'sup_acc': acc, 'note': 'test: checked against a signed letterhead'}); assert j.get('status') == 200, j
j = acct.post('master_files/supplier_bank/verify', data={'sup_acc': '6-9-11-11496', 'note': 'x'})
check(10, 'the accountant cannot verify a supplier account (no verification privilege → 403)', j.get('status') == 403, j)
st = {r.split('\t')[0]: r.split('\t')[1] for r in sql("SELECT sup_acc, status FROM tpd_supplier_bank").split('\n')}
check(10, 'six suppliers verified by the bank approver, Kassapa left unverified', st.get('6-9-11-10626') == 'verified' and st.get('6-9-11-11496') == 'unverified', st)
d0 = desk()
kas = line(d0, 'hotel:6-9-11-11496')
check(10, 'the Kassapa line carries the "not verified" warning; COVENRO has none', any(w['code'] == 'bank_unverified' for w in kas['warnings']) and not any(w['code'].startswith('bank') for w in line(d0, 'hotel:6-9-11-10626')['warnings']))

print('\n== Today\'s USD rate (tests 1, 4 prep)')
j = req.post('tour/tour_payment_dashboard/confirm_rate', data={'rate': '327.25'})
check(14, 'a requester cannot confirm the rate (403)', j.get('status') == 403, j)
j = appr.post('tour/tour_payment_dashboard/confirm_rate', data={'rate': '327.25'})
check(3, "today's rate confirmed by the approver: 327.25, with who and when", j.get('status') == 200 and j['rate']['rate'] == 327.25 and j['rate']['entered_by'] == 'tcpayappr' and j['rate']['entered_at'], j)
d0 = desk()
cov = line(d0, 'hotel:6-9-11-10626')
check(3, 'an unrequested USD line shows the LKR estimate at today\'s rate, marked indicative (55 × 327.25 = 17,998.75)', abs(cov['lkr_amount'] - 17998.75) < 0.01 and cov['lkr_is_indicative'] and not cov['rate_locked'], (cov['lkr_amount'], cov['rate_note']))

# ============ 1. full USD payment: COVENRO — approval → Supplier Payables → batch → voucher → same-day initiation and Paid ============
print('\n== 1. Full USD payment — COVENRO VEYANGODA $55 (approval and payment on the same day)')
j = legacy_request(req, cov, 55, {'invoice_no': 'COV-INV-7781', 'booking_ref': 'CV-2218', 'note': 'full prepayment'})
check(1, 'requester: payment request accepted by the chain', j.get('status') == 200, j)
d1 = desk(); cov = line(d1, 'hotel:6-9-11-10626'); tx = cov['transactions'][-1] if cov['transactions'] else {}
check(1, 'status Awaiting Management Approval; rate 327.25 locked to the request; LKR 17,998.75; invoice kept', cov['status'] == 'awaiting' and tx.get('usd_rate') == 327.25 and tx.get('lkr') == 17998.75 and tx.get('invoice_no') == 'COV-INV-7781' and cov['rate_locked'], (cov['status'], tx.get('usd_rate'), tx.get('lkr'), tx.get('invoice_no')))
j = legacy_request(req, cov, 55)
check(16, 'a second request for the same line while one is in flight is refused (409)', j.get('status') == 409, j)
j = approve(req, tx['pr_id'])
check(8, 'a requester cannot approve (403)', j.get('status') == 403, j)
j = approve(appr, tx['pr_id'], note='approved for the first night')
check(1, 'approver: Approved — NO voucher yet; the payment waits under its supplier (awaiting batch)', j.get('status') == 200 and j.get('awaiting_batch') and not j.get('vnum') and sql(f"SELECT CONCAT(payment_request,'|',voucher_created) FROM payment_request WHERE id={tx['pr_id']}") == '4|0' and sql(f"SELECT COUNT(*) FROM voucher WHERE Acc_No='6-9-11-10626' AND ref_po='{TOUR}'") == '0', j)
pr_cov = tx['pr_id']
d1 = desk(); cov = line(d1, 'hotel:6-9-11-10626'); tx = cov['transactions'][-1]
check(1, 'dashboard: Approved for Payment; approval figures recorded — $55 × 327.25 = LKR 17,998.75 (provisional), approval date and user', cov['status'] == 'approved' and tx['approval_rate'] == 327.25 and tx['approval_lkr'] == 17998.75 and tx['accepted_by'] == 'tcpayappr' and tx['approval_rate_date'] == TODAY, (cov['status'], tx.get('approval_rate'), tx.get('approval_lkr'), tx.get('accepted_by')))
check(23, 'the approval figures: the locked USD amount, the rate shown at approval, the provisional LKR, the approval date and user — on the request record', sql(f"SELECT CONCAT(amount,'|',approval_rate,'|',approval_lkr,'|',approval_rate_date,'|',approved_by) FROM tpd_request_ext WHERE pr_id={pr_cov}") == f'55.00|327.2500|17998.75|{TODAY}|tcpayappr', sql(f"SELECT CONCAT(amount,'|',approval_rate,'|',approval_lkr,'|',approval_rate_date,'|',approved_by) FROM tpd_request_ext WHERE pr_id={pr_cov}"))
w = waiting('6-9-11-10626')
check(23, 'Supplier Payables lists the approved payment under COVENRO: tour, guest, invoice, $55, approval rate, provisional LKR', w and w['item_count'] == 1 and w['items'][0]['manual_id'] == 'AH 2218' and w['items'][0]['invoice_no'] == 'COV-INV-7781' and w['items'][0]['usd_amount'] == 55 and w['items'][0]['approval_lkr'] == 17998.75 and w['bank']['status'] == 'verified', w and w['items'][0])
j = req.get('tour/tour_payment_dashboard/payables_data')
check(23, 'a requester (no payment role beyond the dashboard) cannot open Supplier Payables (403)', j.get('status') == 403, j.get('status'))
j = approve(appr, pr_cov)
check(12, 'approving the same request twice is refused (409)', j.get('status') == 409, j)
j = make_batch(acct, '6-9-11-10626', [pr_cov], COM)
check(23, 'the accountant cannot create a batch (403) — batch creation is management\'s', j.get('status') == 403, j)
j = make_batch(appr, '6-9-11-10626', [pr_cov], COM, note='first batch of the day')
check(23, 'management creates the COVENRO batch: one voucher issued, placed in the accountant\'s queue', j.get('status') == 200 and j.get('vnum') and j.get('batch_no', '').startswith('SB-'), j)
v_cov = j.get('vnum'); b_cov = j.get('batch_id')
check(5, 'voucher in the chain: Aflag 0, LKR 17,998.75 = $55 × 327.25 (provisional), paying ledger 1-6-8-10380, request voucher_created=1', sql(f"SELECT CONCAT(Aflag,'|',Amount,'|',doller_amt,'|',Bank_acc,'|',finalized) FROM voucher WHERE Vnum={v_cov}") == '0|17998.75|55.00|1-6-8-10380|0' and sql(f"SELECT voucher_created FROM payment_request WHERE id={pr_cov}") == '1', sql(f"SELECT CONCAT(Aflag,'|',Amount,'|',doller_amt,'|',Bank_acc,'|',finalized) FROM voucher WHERE Vnum={v_cov}"))
d1 = desk(); cov = line(d1, 'hotel:6-9-11-10626'); tx = cov['transactions'][-1]
check(5, 'dashboard: Payment Voucher Issued, not paid; the batch shown on the line', cov['status'] == 'voucher_issued' and cov['paid_lkr'] == 0 and not cov['fully_paid'] and tx['batch'] and tx['batch']['id'] == b_cov, (cov['status'], tx.get('batch')))
check(23, 'the payment left Supplier Payables once batched', waiting('6-9-11-10626') is None)
j = make_batch(appr, '6-9-11-10626', [pr_cov], COM)
check(12, 'batching the same request twice is refused', j.get('status') == 400, j)
r = acct.raw(f'tour/tour_payment_dashboard/voucher/{v_cov}'); r2 = acct.raw(f'tour/tour_payment_dashboard/voucher/{v_cov}', pdf=1)
check(5, 'payment voucher page and PDF: voucher no, batch no, tour, passenger, supplier, invoice, dates, USD, approval rate, provisional LKR, bank, approver', r.status_code == 200 and all(x in r.text for x in [f'Payment Voucher No. {v_cov}', 'SB-', 'AH 2218', 'NEMOTO / REIKO MS', 'COVENRO VEYANGODA', 'COV-INV-7781', 'USD 55.00', '327.25', '17,998.75', 'Com Bank LKR operating', 'tcpayappr', 'Provisional LKR']) and r2.headers.get('content-type', '').startswith('application/pdf') and r2.content[:4] == b'%PDF', (r.status_code, [x for x in [f'Payment Voucher No. {v_cov}', 'SB-', 'AH 2218', 'COV-INV-7781', 'USD 55.00', '327.25', '17,998.75', 'Provisional LKR'] if x not in r.text]))
open(HERE + '/shots/voucher-covenro.pdf', 'wb').write(r2.content)
r = acct.raw('report/bank_payment_check')
check(8, 'Bank Payment Check (accountant): the voucher shows its batch link; no Mark Finalized for a batch voucher', r.status_code == 200 and f'batch/{b_cov}' in r.text and 'Paid: bank approver only' in r.text, (r.status_code, f'batch/{b_cov}' in r.text))
j = appr.post('tour/tour_payment_dashboard/initiate', data={'vnum': v_cov, 'bank_account_id': COM, 'payment_date': TODAY})
check(8, 'the approver cannot record a bank initiation (403)', j.get('status') == 403, j)
j = paid(acct, v_cov, TODAY, 'x')
check(8, 'the accountant cannot click Paid (403)', j.get('status') == 403, j)
bd = bdata(b_cov, acct)
check(24, "the accountant's bank payment page: supplier + verified bank, the included tour and invoice, locked USD 55, approval rate 327.25, provisional 17,998.75, today's confirmed rate, actual LKR, difference", bd['supplier']['status'] == 'verified' and bd['items'][0]['invoice_no'] == 'COV-INV-7781' and bd['provisional']['usd_total'] == 55 and bd['provisional']['provisional_lkr_total'] == 17998.75 and bd['today_rate']['rate'] == 327.25 and bd['at_date']['actual_lkr_total'] == 17998.75 and bd['at_date']['fx_diff'] == 0, (bd['today_rate'], bd['at_date']))
j = initiate(acct, v_cov, COM, TODAY, initiated_at=TODAY + 'T15:05', amount='99999', bank_ref='x')
check(24, 'the accountant cannot change the LKR amount (it is calculated from the locked USD × the payment-date rate)', j.get('status') == 409, j)
j = acct.post('tour/tour_payment_dashboard/initiate', data={'vnum': v_cov, 'bank_account_id': COM, 'payment_date': TODAY, 'initiated_at': TODAY + 'T15:05', 'charges': '150', 'batch_ref': 'BATCH-20261010-A', 'bank_ref': 'CB-TRF-000111', 'note': 'test transfer'}, files={'file': ('proof.png', open(HERE + '/shots/smoke_phone.png', 'rb').read()[:200000], 'image/png')})
check(6, 'accountant: bank initiation recorded — payment date, that day\'s rate 327.25 (daily), LKR 17,998.75, charges 150, total debit 18,148.75, batch, reference, file, note', j.get('status') == 200 and sql(f"SELECT CONCAT(payment_date,'|',usd_rate,'|',rate_source,'|',amount,'|',charges,'|',total_debit,'|',approval_lkr,'|',fx_diff) FROM tpd_bank_initiation WHERE vnum={v_cov}") == f'{TODAY}|327.2500|daily|17998.75|150.00|18148.75|17998.75|0.00', (j, sql(f"SELECT CONCAT(payment_date,'|',usd_rate,'|',rate_source,'|',amount,'|',charges,'|',total_debit,'|',approval_lkr,'|',fx_diff) FROM tpd_bank_initiation WHERE vnum={v_cov}")))
d1 = desk(); cov = line(d1, 'hotel:6-9-11-10626'); tx = cov['transactions'][-1]
check(6, 'status Bank Payment Initiated — Awaiting Bank Approval; still not paid', cov['status'] == 'bank_initiated' and tx['initiation'] and tx['initiation']['batch_ref'] == 'BATCH-20261010-A' and not cov['fully_paid'], cov['status'])
j = initiate(acct, v_cov, COM, TODAY)
check(12, 'a second initiation for the same voucher is refused (409)', j.get('status') == 409, j)
j = acct.post('report/bank_payment_check/mark_finalized', data={'pr_id': v_cov, 'bank_date': TODAY})
check(8, 'the accountant calling Mark Finalized directly is refused (403); the voucher stays unpaid', j.get('status') == 403 and sql(f"SELECT finalized FROM voucher WHERE Vnum={v_cov}") == '0', j)
j = bank.post('report/bank_payment_check/mark_finalized', data={'pr_id': v_cov, 'bank_date': TODAY})
check(24, 'even the bank approver cannot Mark Finalized a batch voucher on Bank Payment Check — Paid is confirmed on the batch page with the payment-date rate', j.get('status') == 403 and 'batch' in (j.get('message') or '') and sql(f"SELECT finalized FROM voucher WHERE Vnum={v_cov}") == '0', j)
fp0 = approval_fingerprint()
# ---- retest 7: the exchange gain/loss ledger mapping is missing → final Paid of a foreign-currency payment is blocked ----
sql("DELETE FROM tpd_meta WHERE k IN ('fx_gain_ledger_code','fx_loss_ledger_code','fx_ledger_code','bank_charge_ledger_code')")
bd = bdata(b_cov, bank)
check(37, 'the final approval page shows the configuration warning while the gain/loss ledger accounts are not mapped', bd['paid_block'] and 'mapping incomplete' in bd['paid_block'] and not bd['fx_mapping']['complete'], bd.get('paid_block'))
j = paid(bank, v_cov, TODAY, 'CB-TRF-000111', charges='150')
check(37, 'missing ledger mapping BLOCKS the final foreign-currency Paid (409, configuration warning); nothing was written', j.get('status') == 409 and j.get('config_warning') and sql(f"SELECT finalized FROM voucher WHERE Vnum={v_cov}") == '0' and sql(f"SELECT COUNT(*) FROM tpd_bank_approval WHERE vnum={v_cov}") == '0', j)
j = req.post('tour/tour_payment_dashboard/setup', data={'fx_gain_ledger_code': '4-8-109-31901', 'fx_loss_ledger_code': '4-8-109-31902'})
check(37, 'a requester cannot map the ledger accounts (403)', j.get('status') == 403, j)
j = acct.post('tour/tour_payment_dashboard/setup', data={'fx_gain_ledger_code': '4-8-109-00000', 'fx_loss_ledger_code': '4-8-109-31902'})
check(37, 'a code that is not an active ledger account is refused — nothing is invented', j.get('status') == 400, j)
j = acct.post('tour/tour_payment_dashboard/setup', data={'fx_gain_ledger_code': '4-8-109-31902', 'fx_loss_ledger_code': '4-8-109-31902'})
check(37, 'the same account for gain and loss is refused', j.get('status') == 400, j)
j = acct.post('tour/tour_payment_dashboard/setup', data={'fx_gain_ledger_code': '4-8-109-31901', 'fx_loss_ledger_code': '4-8-109-31902', 'reason': 'test: accounts approved by Accounts'})
check(37, 'the Accounts manager (Avenir Bank Accounts privilege) maps Realised Exchange Gain 4-8-109-31901 and Realised Exchange Loss 4-8-109-31902 from the chart of accounts; audit entry fx_mapping_saved', j.get('status') == 200 and j['fx_mapping']['complete'] and sql("SELECT COUNT(*) FROM tpd_audit WHERE action='fx_mapping_saved'") == '1', j)
# ---- test 43: the BANK-CHARGE ledger mapping is missing → a Paid that carries charges is blocked, nothing written ----
bd = bdata(b_cov, bank)
check(43, 'the final approval page shows the bank-charge configuration warning (charges 150 entered at initiation, no Bank Charges account mapped); the Paid button is blocked', bd['charge_block'] and 'Bank-charge ledger mapping incomplete' in bd['charge_block'] and bd['charges'] == 150 and not bd['charge_mapping']['complete'], bd.get('charge_block'))
lc0 = ledger_counts(); fp_ext0 = sql(f"SELECT MD5(GROUP_CONCAT(CONCAT_WS('|', pr_id, IFNULL(paid_lkr,''), IFNULL(charge_share,''), IFNULL(charge_entry_id,'')) ORDER BY pr_id)) FROM tpd_request_ext")
j = paid(bank, v_cov, TODAY, 'CB-TRF-000111', charges='150')
check(43, 'missing bank-charge mapping BLOCKS a Paid with charges (409, configuration warning, before anything is written): voucher unpaid, no lock, ledger untouched, request records untouched', j.get('status') == 409 and j.get('config_warning') and j.get('charge_mapping') and sql(f"SELECT CONCAT(finalized,'|',Aflag) FROM voucher WHERE Vnum={v_cov}") == '0|0' and sql(f"SELECT COUNT(*) FROM tpd_bank_approval WHERE vnum={v_cov}") == '0' and ledger_counts() == lc0 and sql(f"SELECT MD5(GROUP_CONCAT(CONCAT_WS('|', pr_id, IFNULL(paid_lkr,''), IFNULL(charge_share,''), IFNULL(charge_entry_id,'')) ORDER BY pr_id)) FROM tpd_request_ext") == fp_ext0, (j.get('status'), j.get('message')))
j = paid(bank, v_cov, TODAY, 'CB-TRF-000111', charges='-5')
check(43, 'negative bank charges are refused (400)', j.get('status') == 400, j)
j = acct.post('tour/tour_payment_dashboard/setup', data={'bank_charge_ledger_code': '4-8-109-31902'})
check(43, 'the loss account cannot double as the Bank Charges account (the three mappings must be different accounts)', j.get('status') == 400, j)
j = acct.post('tour/tour_payment_dashboard/setup', data={'bank_charge_ledger_code': '4-8-109-99999'})
check(43, 'a Bank Charges code that is not an active ledger account is refused — nothing is invented', j.get('status') == 400, j)
j = acct.post('tour/tour_payment_dashboard/setup', data={'bank_charge_ledger_code': '4-8-109-31903', 'reason': 'test: Bank Charges account approved by Accounts'})
check(43, 'the Accounts manager maps Bank Charges 4-8-109-31903 from the chart of accounts (the gain/loss mapping untouched); audit entry ledger_mapping_saved', j.get('status') == 200 and j['charge_mapping']['complete'] and j['fx_mapping']['complete'] and j.get('complete') and sql("SELECT COUNT(*) FROM tpd_audit WHERE action='ledger_mapping_saved'") == '1' and sql("SELECT v FROM tpd_meta WHERE k='fx_gain_ledger_code'") == '4-8-109-31901', j)
bd = bdata(b_cov, bank)
check(43, 'the warning is gone; the page shows the account the charges will post to and the per-tour share (150.00 on the one tour)', not bd['charge_block'] and bd['charge_mapping']['account']['code'] == '4-8-109-31903' and bd['charge_shares'][0]['share'] == 150 and bd['charge_shares'][0]['manual_id'] == 'AH 2218', (bd.get('charge_block'), bd.get('charge_shares')))
# ---- retest 2: the bank approver must confirm the paying account ----
j = paid(bank, v_cov, TODAY, 'CB-TRF-000111', charges='150', confirm=0)
check(32, 'Paid without confirming the paying account is refused (428)', j.get('status') == 428 and j.get('needs_account_confirmation'), j)
bd = bdata(b_cov, bank)
check(32, "the final approval page shows the account the payment was initiated from (Com Bank LKR operating, as suggested) for the bank approver to confirm", bd['current_account'] and bd['current_account']['label'] == 'Com Bank LKR operating' and bd['suggested_account']['label'] == 'Com Bank LKR operating', (bd.get('current_account'), bd.get('suggested_account')))
j = paid(bank, v_cov, TODAY, 'CB-TRF-000111', charges='150', note='checked in the bank')
check(7, 'bank approver: Paid (same day as the approval), the paying account confirmed', j.get('status') == 200 and sql(f"SELECT account_confirmed_by FROM tpd_bank_approval WHERE vnum={v_cov}") == 'tcbankappr' and sql("SELECT COUNT(*) FROM tpd_audit WHERE action='account_confirmed' AND vnum=%s" % v_cov) == '2', j)
row = sql(f"SELECT CONCAT(Aflag,'|',finalized,'|',bank_date,'|',finalized_user,'|',Amount) FROM voucher WHERE Vnum={v_cov}")
check(7, 'the chain: voucher authorised (Aflag 1, ledger posted) and finalised with the bank date by the bank approver; amount LKR 17,998.75', row.startswith(f'1|1|{TODAY}|tcbankappr|17998.75'), row)
posted = sql(f"SELECT COUNT(*) FROM account_runing WHERE Invoice_Num='{TOUR}' AND Discription LIKE 'Voucher Payment%' AND ID > {pre_max['account_runing']}")
check(7, 'ledger entries written by the existing authorisation step', int(posted) >= 1, posted)
lock = sql(f"SELECT CONCAT(paid_date,'|',amount,'|',usd_amount,'|',usd_rate,'|',lkr_amount,'|',ledger_code,'|',bank_ref,'|',initiated_by,'|',approved_by,'|',approval_lkr,'|',fx_diff,'|',charges,'|',total_debit) FROM tpd_bank_approval WHERE vnum={v_cov}")
check(7, 'the Paid lock: date, amount, USD, rate, LKR, account, reference, initiator, approver — and the provisional LKR, the difference (0.00), charges, total debit', lock == f'{TODAY}|17998.75|55.00|327.2500|17998.75|1-6-8-10380|CB-TRF-000111|tcaccount|tcbankappr|17998.75|0.00|150.00|18148.75', lock)
check(42, 'NONZERO charges, one tour: LKR 150.00 posted ONCE as one balanced pair — Dr 4-8-109-31903 TEST Bank Charges / Cr 1-6-8-10380 (the paying account) — carrying the tour; charges_posted = 1; the request record carries its share 150.00', charge_pairs(v_cov) == f'{TOUR}:4-8-109-31903:150.00:0.00;{TOUR}:1-6-8-10380:0.00:150.00' and sql(f"SELECT CONCAT(charges_posted,'|',charge_entries<>'') FROM tpd_bank_approval WHERE vnum={v_cov}") == '1|1' and sql(f"SELECT CONCAT(charge_share,'|',charge_entry_id>0) FROM tpd_request_ext WHERE pr_id={pr_cov}") == '150.00|1' and j.get('charge_postings') and j['charge_postings'][0]['share'] == 150, (charge_pairs(v_cov), j.get('charge_postings')))
rc = recon(v_cov)
check(42, 'RECONCILED to the bank debit: credits to 1-6-8-10380 in the ledger = 17,998.75 (payment, posted by the chain) + 150.00 (charges) = 18,148.75 = the total the bank debited; every entry Dr = Cr; the lock records it', rc and rc['reconciled'] and rc['totals']['payment_credit'] == 17998.75 and rc['totals']['charge_credit'] == 150 and rc['totals']['bank_credit'] == 18148.75 and rc['totals']['total_debit'] == 18148.75 and rc['totals']['dr'] == rc['totals']['cr'] and all(c['ok'] for c in rc['checks']) and sql(f"SELECT CONCAT(reconciled,'|',ledger_bank_credit) FROM tpd_bank_approval WHERE vnum={v_cov}") == '1|18148.75' and sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action IN ('charges_posted','ledger_reconciled') AND vnum={v_cov}") == '2', rc and (rc['reconciled'], rc['totals'], [c for c in rc['checks'] if not c['ok']]))
check(25, 'approval and payment on the same day: final rate = approval rate, final LKR = provisional LKR, exchange-rate difference 0 — nothing to post', sql(f"SELECT CONCAT(paid_rate,'|',paid_lkr,'|',fx_diff,'|',paid_date) FROM tpd_request_ext WHERE pr_id={pr_cov}") == f'327.2500|17998.75|0.00|{TODAY}' and fx_pairs(v_cov) == '' and sql(f"SELECT fx_posted FROM tpd_bank_approval WHERE vnum={v_cov}") == '1', sql(f"SELECT CONCAT(paid_rate,'|',paid_lkr,'|',fx_diff,'|',paid_date) FROM tpd_request_ext WHERE pr_id={pr_cov}"))
lc1 = ledger_counts()
j = paid(bank, v_cov, TODAY, 'again', charges='150')
check(12, 'Paid twice is refused (409); the lock is untouched', j.get('status') == 409 and sql(f"SELECT bank_ref FROM tpd_bank_approval WHERE vnum={v_cov}") == 'CB-TRF-000111', j)
check(46, 'REPEATED Paid submission: refused; the ledger is byte-for-byte unchanged (no second payment posting, charges not posted twice), the lock and the request record unchanged', ledger_counts() == lc1 and charge_pairs(v_cov) == f'{TOUR}:4-8-109-31903:150.00:0.00;{TOUR}:1-6-8-10380:0.00:150.00' and sql(f"SELECT COUNT(*) FROM tpd_bank_approval WHERE vnum={v_cov}") == '1' and sql(f"SELECT charge_share FROM tpd_request_ext WHERE pr_id={pr_cov}") == '150.00', (lc1, ledger_counts()))
d1 = desk(); cov = line(d1, 'hotel:6-9-11-10626')
check(7, 'dashboard: Fully Paid + Supplier Notification Pending; paid LKR 17,998.75; balance 0', cov['status'] == 'paid' and cov['notify'] == 'notify_pending' and cov['paid_lkr'] == 17998.75 and cov['balance_lkr'] == 0, (cov['status'], cov['notify'], cov['paid_lkr']))

# ============ 11/12. remittance ============
print('\n== 11/12. Remittance advice — COVENRO')
n0 = len(glob.glob(SINK + '/*.eml'))
j = req.post('tour/tour_payment_dashboard/remittance_prepare', data={'vnum': v_cov})
check(12, 'advice prepared: address from the supplier bank record, subject, body, PDF attached', j.get('status') == 200 and j['remittance']['to'] == 'covenro-test@example.invalid' and j['remittance']['pdf'] and 'Dear COVENRO VEYANGODA' in j['remittance']['body'], j)
r = req.raw(f'tour/tour_payment_dashboard/remittance_pdf/{v_cov}'); open(HERE + '/shots/remittance-covenro.pdf', 'wb').write(r.content)
rp = req.raw(f'tour/tour_payment_dashboard/remittance/{v_cov}').text
need = ['COVENRO VEYANGODA', 'AH 2218', 'NEMOTO / REIKO MS', f'{v_cov}', '17,998.75', 'USD 55.00', '327.25', 'CB-TRF-000111', 'Commercial Bank of Ceylon', '••']
check(11, 'advice names only COVENRO; shows tour, passenger, voucher, date, LKR, USD + the payment-date rate, bank ref, Avenir bank (masked), no other supplier', all(x in rp for x in need) and 'Kassapa' not in rp and 'GIMANHALA' not in rp and 'TEST0001234567' not in rp, [x for x in need if x not in rp])
j = req.post('tour/tour_payment_dashboard/remittance_send', json_body={'vnum': v_cov, 'to': 'covenro-test@example.invalid', 'subject': j['remittance']['subject'], 'body': j['remittance']['body']})
check(12, 'advice sent (through the test mail sink)', j.get('status') == 200, j)
time.sleep(1)
msgs = sorted(glob.glob(SINK + '/*.eml'))
ok = len(msgs) == n0 + 1
if ok:
    m = email.message_from_bytes(open(msgs[-1], 'rb').read(), policy=policy.default)
    atts = [p.get_filename() for p in m.iter_attachments()]
    check(12, 'the mail: to the supplier, subject, PDF attached, the approved wording', m['To'] and 'covenro-test@example.invalid' in m['To'] and f'Remittance-advice-{v_cov}.pdf' in atts and 'Please check your bank account' in m.get_body(preferencelist=('html', 'plain')).get_content(), (m['To'], atts))
else:
    check(12, 'the mail reached the sink', False, (n0, len(msgs)))
j = req.post('tour/tour_payment_dashboard/remittance_send', json_body={'vnum': v_cov, 'to': 'covenro-test@example.invalid'})
check(12, 'sending again is refused — no repeat supplier e-mail', j.get('status') == 409 and len(glob.glob(SINK + '/*.eml')) == n0 + 1, j)
d1 = desk(); cov = line(d1, 'hotel:6-9-11-10626')
check(12, 'status now Supplier Notified; the batch is Notified too', cov['notify'] == 'notified' and cov['status'] == 'paid' and any(b['stage'] == 'notified' and b['id'] == b_cov for b in payables()['batches']), (cov['status'], cov['notify']))

# ============ 2/4/10/26. Kassapa in two parts: unverified-bank gate; payment days after approval at a HIGHER rate ============
print('\n== 2/4/10/26. Kassapa Lions Rock $140 in two parts; unverified bank gate; payment after approval, rate increasing')
d2 = desk(); kas = line(d2, 'hotel:6-9-11-11496')
j = legacy_request(req, kas, 70, {'invoice_no': 'KLR-ADV-01', 'note': 'advance 50%'})
check(2, 'first part requested: $70', j.get('status') == 200, j)
d2 = desk(); kas = line(d2, 'hotel:6-9-11-11496'); tx1 = kas['transactions'][-1]
check(2, 'rate 327.25 locked to part 1; LKR 22,907.50; balance $70 shown indicative', tx1['usd_rate'] == 327.25 and tx1['lkr'] == 22907.5 and kas['usd_remaining'] == 70 and kas['lkr_is_indicative'], (tx1['usd_rate'], tx1['lkr'], kas['usd_remaining']))
j = approve(appr, tx1['pr_id'])
check(10, 'approval blocked: supplier bank not verified (428, needs authorised confirmation)', j.get('status') == 428 and j.get('needs_confirmation'), j)
j = approve(appr, tx1['pr_id'], confirm_bank=1)
check(10, 'the approver (no verification privilege) cannot confirm it (403)', j.get('status') == 403, j)
j = bank.post('tour/tour_payment_dashboard/confirm_supplier_bank', data={'sup_acc': '6-9-11-11496', 'reason': 'test: confirmed by phone with the hotel accounts office'})
check(10, 'a verifier confirms the account (recorded under their name)', j.get('status') == 200 and sql("SELECT CONCAT(status,'|',verified_by) FROM tpd_supplier_bank WHERE sup_acc='6-9-11-11496'") == 'verified|tcbankappr', j)
j = approve(appr, tx1['pr_id'])
pr_k1 = tx1['pr_id']
check(2, 'part 1 approved → waits under Kassapa in Supplier Payables (provisional $70 × 327.25 = 22,907.50)', j.get('status') == 200 and j.get('awaiting_batch') and sql(f"SELECT CONCAT(approval_rate,'|',approval_lkr) FROM tpd_request_ext WHERE pr_id={pr_k1}") == '327.2500|22907.50', j)
j = make_batch(appr, '6-9-11-11496', [pr_k1], COM)
check(2, 'part 1 batched → voucher LKR 22,907.50 (provisional)', j.get('status') == 200 and sql(f"SELECT Amount FROM voucher WHERE Vnum={j.get('vnum')}") == '22907.50', j); v_k1 = j.get('vnum'); b_k1 = j.get('batch_id')
# a later day: the rate rises to 329.10 (today's rate changed, standing in for the next day's rate)
j = appr.post('tour/tour_payment_dashboard/confirm_rate', data={'rate': '329.10'})
check(4, "today's rate changed to 329.10 (standing in for a later day's rate); audit keeps 327.25 → 329.10", j.get('status') == 200 and sql("SELECT CONCAT(prev_value,'→',new_value) FROM tpd_audit WHERE action='rate_confirmed' ORDER BY id DESC LIMIT 1").find('327.25') >= 0, j.get('message'))
d2 = desk(); kas = line(d2, 'hotel:6-9-11-11496'); cov = line(d2, 'hotel:6-9-11-10626')
check(4, 'the paid COVENRO keeps 327.25 / LKR 17,998.75; Kassapa part 1 keeps its approval figures 327.25 / 22,907.50; only the unrequested $70 moves to 329.10', cov['transactions'][0]['usd_rate'] == 327.25 and cov['paid_lkr'] == 17998.75 and kas['transactions'][0]['approval_rate'] == 327.25 and kas['transactions'][0]['approval_lkr'] == 22907.5 and abs(kas['lkr_indicative'] - 70 * 329.10) < 0.01, (kas['transactions'][0].get('approval_rate'), kas['lkr_indicative']))
bd = bdata(b_k1, acct)
check(26, "the bank payment page shows today's rate 329.10 beside the approval rate 327.25: actual LKR 23,037.00, difference +129.50 (loss)", bd['today_rate']['rate'] == 329.10 and bd['at_date']['actual_lkr_total'] == 23037.0 and bd['at_date']['fx_diff'] == 129.5 and bd['items'][0]['approval_rate'] == 327.25, bd['at_date'])
# ---- retest 1: management suggested Com Bank; the accountant selects Sampath ----
check(31, "management's suggestion recorded on the batch (Com Bank LKR operating)", sql(f"SELECT suggested_bank_account_id FROM tpd_batch WHERE id={b_k1}") == str(COM))
j = acct.post('master_files/bank_accounts/save', data={'entity': 'Avenir Holidays (Pvt) Ltd', 'label': 'Com Bank USD account', 'bank_name': 'Commercial Bank of Ceylon', 'branch': 'Colombo 07', 'account_name': 'Avenir Holidays (Pvt) Ltd', 'account_no': 'TEST0005550001', 'currency': 'USD', 'swift': 'TESTLKLX', 'account_type': 'Current', 'ledger_code': '1-6-8-10380', 'is_default': 0, 'active': 1, 'external_display': 'masked', 'display_digits': 4})
USDACC = j.get('id') or int(sql("SELECT id FROM tpd_bank_account WHERE label='Com Bank USD account'"))
j = initiate(acct, v_k1, USDACC, TODAY, bank_ref='CB-TRF-000112', account_reason='test')
check(31, 'revalidation: a USD account cannot pay an LKR voucher (refused)', j.get('status') == 400 and 'USD account' in (j.get('message') or ''), j)
j = initiate(acct, v_k1, SAM, TODAY, batch_ref='BATCH-20261010-A', bank_ref='CB-TRF-000112')
check(31, 'a different account than the suggested one needs a reason (428)', j.get('status') == 428 and j.get('needs_account_reason') and j.get('suggested') == 'Com Bank LKR operating' and j.get('chosen') == 'Sampath LKR account', j)
j = initiate(acct, v_k1, SAM, TODAY, batch_ref='BATCH-20261010-A', bank_ref='CB-TRF-000112', account_reason='test: Com Bank balance low today')
ch = sql(f"SELECT CONCAT(user,'|',prev_value,'|',new_value,'|',reason) FROM tpd_audit WHERE action='bank_account_changed' AND vnum={v_k1}")
check(31, 'the accountant pays from Sampath: the change recorded — original and new account, user, date/time, reason, currency and ledger revalidated; the voucher now pays from Sampath', j.get('status') == 200 and ch.startswith('tcaccount|') and 'Com Bank LKR operating' in ch and 'Sampath LKR account' in ch and 'balance low' in ch and 'revalidated' in ch and sql(f"SELECT Bank_acc FROM voucher WHERE Vnum={v_k1}") == '1-6-8-10390', (j, ch))
check(26, 'initiated at the payment-date rate: the voucher now carries LKR 23,037.00 ($70 × 329.10); the approval figures unchanged', j.get('status') == 200 and sql(f"SELECT Amount FROM voucher WHERE Vnum={v_k1}") == '23037.00' and sql(f"SELECT CONCAT(approval_rate,'|',approval_lkr) FROM tpd_request_ext WHERE pr_id={pr_k1}") == '327.2500|22907.50' and sql(f"SELECT CONCAT(usd_rate,'|',amount,'|',fx_diff) FROM tpd_bank_initiation WHERE vnum={v_k1}") == '329.1000|23037.00|129.50', (j, sql(f"SELECT Amount FROM voucher WHERE Vnum={v_k1}")))
check(14, 'part 1 initiated in the same bank batch as COVENRO (one batch reference, several suppliers)', sql("SELECT COUNT(DISTINCT sup_acc) FROM tpd_bank_initiation WHERE batch_ref='BATCH-20261010-A'") == '2')
bd = bdata(b_k1, bank)
check(32, 'the bank approver sees the final account (Sampath LKR account) beside the suggested one (Com Bank) and the change in the account history', bd['current_account']['label'] == 'Sampath LKR account' and bd['suggested_account']['label'] == 'Com Bank LKR operating' and len(bd['account_changes']) == 1, (bd['current_account'], len(bd['account_changes'])))
j = paid(bank, v_k1, TODAY, 'CB-TRF-000112')
check(36, 'exchange LOSS +129.50 posted to the mapped loss account, balanced against the supplier (Dr 4-8-109-31902 / Cr Kassapa), carrying the tour', j.get('status') == 200 and fx_pairs(v_k1) == '2325:4-8-109-31902:129.50:0.00;2325:6-9-11-11496:0.00:129.50' and sql(f"SELECT CONCAT(fx_posted,'|',fx_entries<>'') FROM tpd_bank_approval WHERE vnum={v_k1}") == '1|1', (j.get('fx_postings'), fx_pairs(v_k1)))
rc = recon(v_k1)
check(41, 'ZERO charges (none entered): nothing posted to Bank Charges, charges_posted = 0, charges 0.00 on the lock; reconciled — credits to the paying account 23,037.00 = the bank debit; the exchange-loss pair balanced', charge_pairs(v_k1) == '' and sql(f"SELECT CONCAT(charges,'|',charges_posted,'|',total_debit,'|',reconciled) FROM tpd_bank_approval WHERE vnum={v_k1}") == '0.00|0|23037.00|1' and rc['reconciled'] and rc['totals']['bank_credit'] == 23037.0 and rc['totals']['charge_credit'] == 0 and any('Exchange-rate difference' in c['what'] and c['ok'] for c in rc['checks']), (charge_pairs(v_k1), rc and rc['totals']))
check(2, 'part 1 Paid at 329.10: final LKR 23,037.00, loss 129.50 recorded separately; ledger posted at the final LKR', j.get('status') == 200 and sql(f"SELECT CONCAT(usd_rate,'|',lkr_amount,'|',approval_lkr,'|',fx_diff) FROM tpd_bank_approval WHERE vnum={v_k1}") == '329.1000|23037.00|22907.50|129.50' and sql(f"SELECT SUM(Dr) FROM account_runing WHERE Discription LIKE 'Voucher Payment%' AND L_Code='6-9-11-11496' AND ID > {pre_max['account_runing']}") == '23037.00', (j, sql(f"SELECT CONCAT(usd_rate,'|',lkr_amount,'|',approval_lkr,'|',fx_diff) FROM tpd_bank_approval WHERE vnum={v_k1}")))
d2 = desk(); kas = line(d2, 'hotel:6-9-11-11496')
check(2, 'line is Partly Paid: paid $70 / LKR 23,037.00; balance $70; Request balance offered', kas['status'] == 'partly_paid' and kas['usd_paid'] == 70 and kas['paid_lkr'] == 23037.0 and kas['actions']['request_balance'], (kas['status'], kas['usd_paid'], kas['paid_lkr'], kas['actions'].get('request_balance')))
check(26, 'the dashboard shows both: approval 327.25 / 22,907.50 and paid 329.10 / 23,037.00 (+129.50)', kas['transactions'][0]['approval_rate'] == 327.25 and kas['transactions'][0]['paid_rate'] == 329.10 and kas['transactions'][0]['paid_lkr'] == 23037.0 and kas['transactions'][0]['fx_diff'] == 129.5, kas['transactions'][0])
j = legacy_request(req, kas, 70, {'invoice_no': 'KLR-BAL-02', 'note': 'balance', 'parent_pr_id': pr_k1})
check(2, 'balance requested as a second request ($70) — allowed because part 1 is paid', j.get('status') == 200, j)
d2 = desk(); kas = line(d2, 'hotel:6-9-11-11496'); tx2 = kas['transactions'][-1]
check(2, 'part 2 locked at 329.10 → LKR 23,037.00; part 1 still 327.25 at approval', tx2['usd_rate'] == 329.10 and tx2['lkr'] == 23037.0 and kas['transactions'][0]['approval_rate'] == 327.25 and tx2.get('parent_pr_id') == pr_k1, (tx2['usd_rate'], tx2['lkr'], tx2.get('parent_pr_id')))
pr_k2 = tx2['pr_id']

# ============ 27/28. rate decreasing; initiation and final approval on different dates: Kassapa part 2 ============
print('\n== 27/28. Rate decreasing · accountant initiation and bank approval on different dates — Kassapa part 2')
j = approve(appr, pr_k2)
check(2, 'part 2 approved (provisional $70 × 329.10 = 23,037.00)', j.get('status') == 200 and sql(f"SELECT CONCAT(approval_rate,'|',approval_lkr) FROM tpd_request_ext WHERE pr_id={pr_k2}") == '329.1000|23037.00', j)
j = make_batch(appr, '6-9-11-11496', [pr_k2], SAM)
check(9, 'part 2 batched from the second Avenir account (Sampath)', j.get('status') == 200 and sql(f"SELECT Bank_acc FROM voucher WHERE Vnum={j.get('vnum')}") == '1-6-8-10390', j); v_k2 = j.get('vnum'); b_k2 = j.get('batch_id')
j = initiate(acct, v_k2, SAM, D1, bank_ref='SB-TRF-5501')
check(28, 'the accountant initiates with payment date = yesterday: that day\'s confirmed rate 328.00 applies — LKR 22,960.00, difference −77.00 (gain)', j.get('status') == 200 and sql(f"SELECT CONCAT(payment_date,'|',usd_rate,'|',rate_source,'|',amount,'|',fx_diff) FROM tpd_bank_initiation WHERE vnum={v_k2}") == f'{D1}|328.0000|daily|22960.00|-77.00', (j, sql(f"SELECT CONCAT(payment_date,'|',usd_rate,'|',rate_source,'|',amount,'|',fx_diff) FROM tpd_bank_initiation WHERE vnum={v_k2}")))
# the rate falls further: 325.00 (today's rate changed, standing in for the final approval's day)
j = appr.post('tour/tour_payment_dashboard/confirm_rate', data={'rate': '325.00'})
check(27, "today's rate changed to 325.00 (the rate decreases)", j.get('status') == 200, j)
bd = bdata(b_k2, bank, TODAY)
check(28, "the final approval page loads the rate of ITS date (today: 325.00), not the initiation's (328.00): actual LKR 22,750.00, difference −287.00; the approval rate 329.10 stays visible", bd['rate_for_date']['rate'] == 325.0 and bd['at_date']['actual_lkr_total'] == 22750.0 and bd['at_date']['fx_diff'] == -287.0 and bd['at_initiation']['rate'] == 328.0 and bd['items'][0]['approval_rate'] == 329.10, (bd['rate_for_date'], bd['at_date'], bd['at_initiation']['rate']))
j = paid(bank, v_k2, TODAY, 'SB-TRF-5501', charges='200')
check(35, 'exchange GAIN −287.00 posted to the mapped gain account, balanced against the supplier (Dr Kassapa / Cr 4-8-109-31901)', j.get('status') == 200 and fx_pairs(v_k2) == '2325:6-9-11-11496:287.00:0.00;2325:4-8-109-31901:0.00:287.00', fx_pairs(v_k2))
check(28, 'Paid today: final rate 325.00 locked, final LKR 22,750.00, gain 287.00, charges 200, total debit 22,950.00; the initiation record keeps 328.00 / 22,960.00', j.get('status') == 200 and sql(f"SELECT CONCAT(paid_date,'|',usd_rate,'|',lkr_amount,'|',approval_lkr,'|',fx_diff,'|',charges,'|',total_debit) FROM tpd_bank_approval WHERE vnum={v_k2}") == f'{TODAY}|325.0000|22750.00|23037.00|-287.00|200.00|22950.00' and sql(f"SELECT CONCAT(usd_rate,'|',amount) FROM tpd_bank_initiation WHERE vnum={v_k2}") == '328.0000|22960.00', (j, sql(f"SELECT CONCAT(paid_date,'|',usd_rate,'|',lkr_amount,'|',approval_lkr,'|',fx_diff,'|',charges,'|',total_debit) FROM tpd_bank_approval WHERE vnum={v_k2}")))
rc = recon(v_k2)
check(42, 'charges 200.00 on the Sampath payment: Dr Bank Charges / Cr 1-6-8-10390 (the account actually paid from); reconciled — 22,750.00 + 200.00 = 22,950.00 credited to Sampath = the bank debit; the gain pair balanced', charge_pairs(v_k2) == f'{TOUR}:4-8-109-31903:200.00:0.00;{TOUR}:1-6-8-10390:0.00:200.00' and rc['reconciled'] and rc['bank_ledger'] == '1-6-8-10390' and rc['totals']['bank_credit'] == 22950.0 and rc['totals']['total_debit'] == 22950.0, (charge_pairs(v_k2), rc and rc['totals']))
check(27, 'rate decreasing: the voucher and the ledger carry the final LKR 22,750.00; the request record: paid 325.00 / 22,750.00 / −287.00; approval 329.10 / 23,037.00 unchanged', sql(f"SELECT Amount FROM voucher WHERE Vnum={v_k2}") == '22750.00' and sql(f"SELECT CONCAT(approval_rate,'|',approval_lkr,'|',paid_rate,'|',paid_lkr,'|',fx_diff) FROM tpd_request_ext WHERE pr_id={pr_k2}") == '329.1000|23037.00|325.0000|22750.00|-287.00', sql(f"SELECT CONCAT(approval_rate,'|',approval_lkr,'|',paid_rate,'|',paid_lkr,'|',fx_diff) FROM tpd_request_ext WHERE pr_id={pr_k2}"))
d2 = desk(); kas = line(d2, 'hotel:6-9-11-11496')
check(2, 'Kassapa Fully Paid: $140 = LKR 45,787.00 across two vouchers at two payment-date rates (329.10 and 325.00); two separate advices pending', kas['status'] == 'paid' and kas['paid_lkr'] == 45787.0 and sql(f"SELECT COUNT(*) FROM tpd_remittance WHERE sup_acc='6-9-11-11496' AND status='pending'") == '2', (kas['status'], kas['paid_lkr']))
rp = req.raw(f'tour/tour_payment_dashboard/remittance/{v_k2}').text
check(11, 'Kassapa\'s advice for part 2 carries only Kassapa and only part 2 (22,750.00 @ 325.00)', 'Kassapa Lions Rock' in rp and '22,750.00' in rp and '325.00' in rp and 'COVENRO' not in rp and '23,037.00' not in rp, [x for x in ['Kassapa Lions Rock', '22,750.00', '325.00'] if x not in rp])

# ============ 3. LKR-only: Gimanhala lunch ============
print('\n== 3. LKR-only payment — Gimanhala lunch LKR 2,500')
d3 = desk(); gim = line(d3, 'activity:6-9-11-11590')
j = legacy_request(req, gim, 2500, {'invoice_no': 'GIM-0912'})
d3 = desk(); gim = line(d3, 'activity:6-9-11-11590'); txg = gim['transactions'][-1]
check(3, 'requested in LKR, no conversion, no rate', j.get('status') == 200 and gim['currency'] == 'LKR' and txg['usd_rate'] is None and txg['lkr'] == 2500 and gim['rate_note'].startswith('priced in LKR'), (txg, gim['rate_note']))
j = approve(appr, txg['pr_id'])
check(3, 'approved: the service-fee accrual is posted by the chain at approval (as before); no voucher; waits in Supplier Payables', j.get('status') == 200 and sql(f"SELECT COUNT(DISTINCT ID) FROM account_runing WHERE Discription LIKE 'Lunch or Other Fee%' AND Invoice_Num='{TOUR}' AND L_Code='6-9-11-11590' AND ID > {pre_max['account_runing']}") == '1' and waiting('6-9-11-11590') is not None, j)
j = make_batch(appr, '6-9-11-11590', [txg['pr_id']], SAM, individual=1)
check(34, 'Pay Individually Now without a reason is refused', j.get('status') == 400, j)
j = make_batch(appr, '6-9-11-11590', [txg['pr_id']], SAM, individual=1, bypass_reason='other')
check(34, '"Other authorised payment" without a note is refused', j.get('status') == 400, j)
j = make_batch(acct, '6-9-11-11590', [txg['pr_id']], SAM, individual=1, bypass_reason='urgent_cash')
check(34, 'the accountant cannot use Pay Individually Now (403) — it is management\'s', j.get('status') == 403, j)
j = make_batch(appr, '6-9-11-11590', [txg['pr_id']], SAM, individual=1, bypass_reason='urgent_cash', bypass_note='restaurant wants cash before the lunch')
v_g = j.get('vnum'); b_g = j.get('batch_id')
check(34, 'URGENT payment: Pay Individually Now creates a single-item batch at once; the bypass reason recorded on the batch and in the audit (accumulation_bypassed)', j.get('status') == 200 and j.get('individual') and sql(f"SELECT CONCAT(individual,'|',bypass_reason,'|',item_count) FROM tpd_batch WHERE id={b_g}") == '1|urgent_cash|1' and sql(f"SELECT reason FROM tpd_audit WHERE action='accumulation_bypassed' AND vnum={v_g}").startswith('Urgent cash payment: restaurant'), (j, sql(f"SELECT CONCAT(individual,'|',bypass_reason,'|',item_count) FROM tpd_batch WHERE id={b_g}")))
check(3, 'batched → voucher LKR 2,500.00 from Sampath; the accrual is NOT posted again', j.get('status') == 200 and sql(f"SELECT CONCAT(Amount,'|',Bank_acc,'|',IFNULL(doller_amt,'none')) FROM voucher WHERE Vnum={v_g}") == '2500.00|1-6-8-10390|none' and sql(f"SELECT COUNT(DISTINCT ID) FROM account_runing WHERE Discription LIKE 'Lunch or Other Fee%' AND Invoice_Num='{TOUR}' AND L_Code='6-9-11-11590' AND ID > {pre_max['account_runing']}") == '1', j)
j = initiate(acct, v_g, SAM, TODAY, bank_ref='SB-TRF-5502')
sql("DELETE FROM tpd_meta WHERE k='bank_charge_ledger_code'")
j2 = paid(bank, v_g, TODAY, 'SB-TRF-5502', charges='0', confirm=0)
check(43, 'with the Bank Charges mapping removed, an LKR Paid with charges 0.00 is NOT blocked by the mapping (nothing to post) — it stops only at the next gate, the account confirmation (428)', j2.get('status') == 428 and j2.get('needs_account_confirmation') and not j2.get('config_warning'), j2)
j2 = paid(bank, v_g, TODAY, 'SB-TRF-5502', charges='40', confirm=0)
check(43, 'the same LKR payment with charges 40.00 IS blocked while the mapping is missing (409) — the block does not depend on the currency; nothing written', j2.get('status') == 409 and j2.get('config_warning') and sql(f"SELECT finalized FROM voucher WHERE Vnum={v_g}") == '0' and sql(f"SELECT COUNT(*) FROM tpd_bank_approval WHERE vnum={v_g}") == '0', j2)
acct.post('tour/tour_payment_dashboard/setup', data={'bank_charge_ledger_code': '4-8-109-31903'})
j2 = paid(bank, v_g, TODAY, 'SB-TRF-5502', acc_id=COM)
check(32, 'the bank approver confirming a DIFFERENT account than the initiation needs a reason (428)', j2.get('status') == 428 and j2.get('needs_account_reason'), j2)
j2 = paid(bank, v_g, TODAY, 'SB-TRF-5502', acc_id=COM, account_reason='test: paid from Com Bank in the end')
ch = sql(f"SELECT CONCAT(user,'|',new_value,'|',reason) FROM tpd_audit WHERE action='bank_account_changed' AND vnum={v_g}")
check(32, 'the account changed at final approval: recorded (Sampath → Com Bank, bank approver, reason, stage); the ledger posted from Com Bank', j2.get('status') == 200 and ch.startswith('tcbankappr|') and 'Com Bank LKR operating' in ch and 'final bank approval' in ch and sql(f"SELECT Bank_acc FROM voucher WHERE Vnum={v_g}") == '1-6-8-10380' and sql(f"SELECT COUNT(*) FROM account_runing WHERE Discription LIKE 'Voucher Payment%' AND L_Code='1-6-8-10380' AND Invoice_Num='{TOUR}' AND Cr=2500.00") == '1', (j2, ch))
rc = recon(v_g)
check(41, 'LKR payment, zero charges: no charge posting, reconciled — 2,500.00 credited to Com Bank = the bank debit', charge_pairs(v_g) == '' and rc['reconciled'] and rc['totals']['bank_credit'] == 2500.0 and rc['totals']['charges'] == 0 and sql(f"SELECT charges_posted FROM tpd_bank_approval WHERE vnum={v_g}") == '0', rc and rc['totals'])
check(3, 'initiated and Paid; lock has no rate and no difference', j.get('status') == 200 and j2.get('status') == 200 and sql(f"SELECT CONCAT(amount,'|',IFNULL(usd_rate,'none'),'|',IFNULL(fx_diff,'none')) FROM tpd_bank_approval WHERE vnum={v_g}") in ('2500.00|none|none', '2500.00|none|0.00'), (j, j2, sql(f"SELECT CONCAT(amount,'|',IFNULL(usd_rate,'none'),'|',IFNULL(fx_diff,'none')) FROM tpd_bank_approval WHERE vnum={v_g}")))
j = req.post('tour/tour_payment_dashboard/remittance_prepare', data={'vnum': v_g}); j2 = req.post('tour/tour_payment_dashboard/remittance_send', json_body={'vnum': v_g, 'to': 'gimanhala-test@example.invalid'})
check(11, 'Gimanhala\'s own advice sent — separate from the hotels\'', j.get('status') == 200 and j2.get('status') == 200 and 'Gimanhala' in j['remittance']['supplier'].title(), (j.get('message'), j2.get('message')))

# ============ 29. one supplier, several TOURS, one batch: Cassamara on AH 2218 and AH 2290 — allocation of the difference ============
print('\n== 29. Bulk payment containing several tours — Cassamara Hotel on AH 2218 and AH 2290; allocation of the difference')
d4 = desk(); cas = line(d4, 'hotel:6-9-11-11047')
j = legacy_request(req, cas, 60, {'invoice_no': 'CAS-INV-2210'})
d4b = desk(mid=MID2); cas2 = line(d4b, 'hotel:6-9-11-11047')
j2 = legacy_request(req, cas2, 60, {'invoice_no': 'CAS-INV-2290'}, mid=MID2)
check(29, 'Cassamara requested on both tours ($60 each at 325.00)', j.get('status') == 200 and j2.get('status') == 200, (j, j2))
d4 = desk(); cas = line(d4, 'hotel:6-9-11-11047'); d4b = desk(mid=MID2); cas2 = line(d4b, 'hotel:6-9-11-11047')
pr_c1, pr_c2 = cas['transactions'][-1]['pr_id'], cas2['transactions'][-1]['pr_id']
j = approve(appr, pr_c1); j2 = approve(appr, pr_c2)
w = waiting('6-9-11-11047')
check(29, 'both approved → Supplier Payables shows Cassamara with 2 payments on 2 tours ($120, provisional 39,000.00 at 325.00)', j.get('status') == 200 and j2.get('status') == 200 and w and w['item_count'] == 2 and w['tour_count'] == 2 and w['usd_total'] == 120 and w['approval_lkr_total'] == 39000.0, w and (w['item_count'], w['tour_count'], w['usd_total'], w['approval_lkr_total']))
j = make_batch(appr, '6-9-11-11047', [pr_c1, pr_c2], COM, individual=1, bypass_reason='emergency')
check(34, 'Pay Individually Now takes exactly one payment (two refused)', j.get('status') == 400, j)
j = make_batch(appr, '6-9-11-11047', [pr_c1, pr_c2], COM, note='two tours, one transfer')
v_c = j.get('vnum'); b_c = j.get('batch_id')
check(29, 'one batch, one voucher for both tours: LKR 39,000.00, two v_inv lines (one per tour), each request tied to its line', j.get('status') == 200 and sql(f"SELECT CONCAT(Amount,'|',doller_amt) FROM voucher WHERE Vnum={v_c}") == '39000.00|120.00' and sql(f"SELECT GROUP_CONCAT(CONCAT(Invoice,':',Amount) ORDER BY ID) FROM v_inv WHERE V_tkn={v_c}") == '2325:19500.00,2390:19500.00' and sql(f"SELECT COUNT(DISTINCT vinv_id) FROM tpd_request_ext WHERE batch_id={b_c} AND vinv_id IS NOT NULL") == '2', (j, sql(f"SELECT GROUP_CONCAT(CONCAT(Invoice,':',Amount) ORDER BY ID) FROM v_inv WHERE V_tkn={v_c}")))
d4 = desk(); cas = line(d4, 'hotel:6-9-11-11047'); d4b = desk(mid=MID2); cas2 = line(d4b, 'hotel:6-9-11-11047')
check(29, 'each tour\'s dashboard shows its own share (LKR 19,500.00) of the batch voucher, not the whole voucher', cas['status'] == 'voucher_issued' and cas['transactions'][-1]['voucher_amount'] == 19500.0 and cas['transactions'][-1]['voucher_total'] == 39000.0 and cas2['transactions'][-1]['voucher_amount'] == 19500.0 and cas2['transactions'][-1]['batch']['tours'] == 2, (cas['transactions'][-1].get('voucher_amount'), cas2['transactions'][-1].get('voucher_amount')))
# the rate moves up before the payment: 329.10
appr.post('tour/tour_payment_dashboard/confirm_rate', data={'rate': '329.10'})
j = initiate(acct, v_c, COM, TODAY, charges='300', bank_ref='CB-TRF-000120', batch_ref='BATCH-20261010-C')
j2 = paid(bank, v_c, TODAY, 'CB-TRF-000120')
check(29, 'initiated and Paid at 329.10: voucher LKR 39,492.00, loss +492.00, charges 300, total debit 39,792.00', j.get('status') == 200 and j2.get('status') == 200 and sql(f"SELECT CONCAT(Amount,'|',Aflag,'|',finalized) FROM voucher WHERE Vnum={v_c}") == '39492.00|1|1' and sql(f"SELECT CONCAT(lkr_amount,'|',approval_lkr,'|',fx_diff,'|',charges,'|',total_debit) FROM tpd_bank_approval WHERE vnum={v_c}") == '39492.00|39000.00|492.00|300.00|39792.00', (j, j2, sql(f"SELECT CONCAT(lkr_amount,'|',approval_lkr,'|',fx_diff,'|',charges,'|',total_debit) FROM tpd_bank_approval WHERE vnum={v_c}")))
check(29, 'the difference is allocated per tour: +246.00 to AH 2218 and +246.00 to AH 2290 (each $60 × (329.10 − 325.00)); each v_inv line re-rated to 19,746.00; the ledger posted per tour', sql(f"SELECT GROUP_CONCAT(CONCAT(tour_id,':',paid_lkr,':',fx_diff) ORDER BY tour_id) FROM tpd_request_ext WHERE batch_id={b_c}") == '2325:19746.00:246.00,2390:19746.00:246.00' and sql(f"SELECT GROUP_CONCAT(CONCAT(Invoice,':',Amount,':',Rate) ORDER BY ID) FROM v_inv WHERE V_tkn={v_c}") == '2325:19746.00:329.10,2390:19746.00:329.10' and sql(f"SELECT GROUP_CONCAT(CONCAT(Invoice_Num,':',Dr) ORDER BY Invoice_Num) FROM account_runing WHERE Discription LIKE 'Voucher Payment%' AND L_Code='6-9-11-11047' AND ID > {pre_max['account_runing']}") == '2325:19746.00,2390:19746.00', (sql(f"SELECT GROUP_CONCAT(CONCAT(tour_id,':',paid_lkr,':',fx_diff) ORDER BY tour_id) FROM tpd_request_ext WHERE batch_id={b_c}"), sql(f"SELECT GROUP_CONCAT(CONCAT(Invoice_Num,':',Dr) ORDER BY Invoice_Num) FROM account_runing WHERE Discription LIKE 'Voucher Payment%' AND L_Code='6-9-11-11047' AND ID > {pre_max['account_runing']}")))
d4 = desk(); cas = line(d4, 'hotel:6-9-11-11047'); d4b = desk(mid=MID2); cas2 = line(d4b, 'hotel:6-9-11-11047')
check(38, 'bulk batch: the exchange difference posted as one balanced pair PER TOUR LINE — +246.00 to AH 2218 (2325) and +246.00 to AH 2290 (2390), Dr loss account / Cr Cassamara each; Dr = Cr', fx_pairs(v_c) == '2325:4-8-109-31902:246.00:0.00;2325:6-9-11-11047:0.00:246.00;2390:4-8-109-31902:246.00:0.00;2390:6-9-11-11047:0.00:246.00' and sql("SELECT SUM(Dr)-SUM(Cr) FROM account_runing WHERE advances_pay=23") == '0.00' and sql(f"SELECT COUNT(DISTINCT fx_entry_id) FROM tpd_request_ext WHERE batch_id={b_c}") == '2', fx_pairs(v_c))
rc = recon(v_c)
check(44, 'MULTI-TOUR allocation: charges 300.00 on the two-tour batch split pro rata to the final LKR (19,746.00 / 19,746.00) → 150.00 to AH 2218 and 150.00 to AH 2290, one balanced pair per tour (Dr Bank Charges / Cr Com Bank), each carrying its tour; each request record carries its share', charge_pairs(v_c) == '2325:4-8-109-31903:150.00:0.00;2325:1-6-8-10380:0.00:150.00;2390:4-8-109-31903:150.00:0.00;2390:1-6-8-10380:0.00:150.00' and sql(f"SELECT GROUP_CONCAT(CONCAT(tour_id,':',charge_share) ORDER BY tour_id) FROM tpd_request_ext WHERE batch_id={b_c}") == '2325:150.00,2390:150.00' and sql(f"SELECT COUNT(DISTINCT charge_entry_id) FROM tpd_request_ext WHERE batch_id={b_c}") == '2', (charge_pairs(v_c), sql(f"SELECT GROUP_CONCAT(CONCAT(tour_id,':',charge_share) ORDER BY tour_id) FROM tpd_request_ext WHERE batch_id={b_c}")))
check(44, 'reconciled: credits to Com Bank = 39,492.00 (two payment lines) + 300.00 (two charge lines) = 39,792.00 = the bank debit; charges recorded once (Dr Bank Charges 300.00); the two exchange-loss pairs balanced', rc['reconciled'] and rc['totals']['payment_credit'] == 39492.0 and rc['totals']['charge_credit'] == 300 and rc['totals']['charge_debit'] == 300 and rc['totals']['bank_credit'] == 39792.0 and len([r for r in rc['rows'] if r['group'] == 'charges']) == 4 and len([r for r in rc['rows'] if r['group'] == 'payment']) == 4, rc and (rc['totals'], [c for c in rc['checks'] if not c['ok']]))
check(44, "each tour's payment record shows ITS share: dashboard AH 2218 — charges share 150.00, debited for this tour 19,896.00; AH 2290 the same; the batch page lists the share per line", cas['transactions'][-1]['charge_share'] == 150 and cas['transactions'][-1]['tour_total_debit'] == 19896.0 and cas2['transactions'][-1]['charge_share'] == 150 and cas2['transactions'][-1]['tour_total_debit'] == 19896.0 and sorted((c['manual_id'], c['share']) for c in bdata(b_c, acct)['charge_shares']) == [('AH 2218', 150.0), ('AH 2290', 150.0)], (cas['transactions'][-1].get('charge_share'), cas2['transactions'][-1].get('charge_share'), cas['transactions'][-1].get('tour_total_debit')))
check(29, 'both tours Fully Paid with their own final LKR 19,746.00; one advice for the supplier covering both invoices', cas['fully_paid'] and cas['paid_lkr'] == 19746.0 and cas2['fully_paid'] and cas2['paid_lkr'] == 19746.0 and sql(f"SELECT COUNT(*) FROM tpd_remittance WHERE vnum={v_c}") == '1', (cas['paid_lkr'], cas2['paid_lkr']))
rp = req.raw(f'tour/tour_payment_dashboard/remittance/{v_c}').text
check(29, 'the advice names Cassamara, both tours and both invoices, the final LKR 39,492.00 at 329.10', all(x in rp for x in ['Cassamara Hotel', 'AH 2218', 'AH 2290', 'CAS-INV-2210', 'CAS-INV-2290', '39,492.00', '329.10']), [x for x in ['Cassamara Hotel', 'AH 2218', 'AH 2290', 'CAS-INV-2210', 'CAS-INV-2290', '39,492.00', '329.10'] if x not in rp])
vp = acct.raw(f'tour/tour_payment_dashboard/voucher/{v_c}').text
check(29, 'the voucher document lists both tours with approval figures and final figures and the difference per tour', all(x in vp for x in ['AH 2218', 'AH 2290', '19,500.00', '19,746.00', '+246.00', '325.00', '329.10', 'Exchange-rate difference']), [x for x in ['AH 2218', 'AH 2290', '19,500.00', '19,746.00', '+246.00', '325.00', '329.10', 'Exchange-rate difference'] if x not in vp])
check(44, 'the voucher document shows the charges share per tour (150.00 · 150.00), the charges and total debited, and "reconciled"', vp.count('150.00') >= 2 and 'Charges share' in vp and '39,792.00' in vp and 'reconciled' in vp and 'posted once' in vp, [x for x in ['Charges share', '39,792.00', 'reconciled', 'posted once'] if x not in vp])

# ============ 13. one supplier, two charges on one tour, one request (as before) ============
print('\n== 13. Two charges of one supplier paid in one voucher — Fright Less Jeep (bulk supplier)')
d4 = desk(); fl = sorted([l for l in d4['lines'] if l['supplier_acc'] == '6-9-23-11075' and l['source_type'] == 'activity'], key=lambda l: l['source_id'])
check(13, 'two activity lines for the bulk supplier, one shared request expected (LKR 8,000 + 6,000)', len(fl) == 2 and fl[0]['shared_supplier']['lines'] == 2 and fl[0]['shared_supplier']['total'] == 14000, [(l['description'], l.get('shared_supplier')) for l in fl])
for l, inv in zip(fl, ['FLJ-3301', 'FLJ-3302']):
    j = req.post('tour/tour_payment_dashboard/invoice', data={'tour_id': TOUR, 'line_key': l['key'], 'invoice_no': inv}); assert j.get('status') == 200, j
j = legacy_request(req, fl[0], 14000, {'note': 'both safaris'})
check(13, 'one request for the supplier covering both charges (LKR 14,000)', j.get('status') == 200, j)
j = legacy_request(req, fl[1], 6000)
check(16, 'the second charge cannot be requested separately while the supplier\'s request is in flight (409)', j.get('status') == 409, j)
d4 = desk(); fl = sorted([l for l in d4['lines'] if l['supplier_acc'] == '6-9-23-11075' and l['source_type'] == 'activity'], key=lambda l: l['source_id'])
check(13, 'the request is shown on both lines, shared out 8,000 / 6,000 (no double counting)', fl[0]['transactions'][-1]['amount'] == 8000 and fl[1]['transactions'][-1]['amount'] == 6000 and fl[0]['transactions'][-1]['pr_id'] == fl[1]['transactions'][-1]['pr_id'] and fl[0]['transactions'][-1].get('covers') == 2, [(l['transactions'][-1]['amount'], l['transactions'][-1]['pr_id']) for l in fl])
pr = fl[0]['transactions'][-1]['pr_id']
j = approve(appr, pr)
check(13, 'approved: a bulk supplier\'s status 6 is kept; no voucher; waits in Supplier Payables', j.get('status') == 200 and sql(f"SELECT CONCAT(payment_request,'|',voucher_created) FROM payment_request WHERE id={pr}") == '6|0' and waiting('6-9-23-11075') is not None, (j, sql(f"SELECT payment_request FROM payment_request WHERE id={pr}")))
j = make_batch(appr, '6-9-23-11075', [pr], COM); v_fl = j.get('vnum')
d4 = desk(); fl = [l for l in d4['lines'] if l['supplier_acc'] == '6-9-23-11075' and l['source_type'] == 'activity']
check(13, 'batched: one voucher LKR 14,000; both lines show the same voucher, Payment Voucher Issued', j.get('status') == 200 and sql(f"SELECT Amount FROM voucher WHERE Vnum={v_fl}") == '14000.00' and all(l['status'] == 'voucher_issued' and l['transactions'][-1]['vnum'] == v_fl for l in fl), [(l['status'], l['transactions'][-1]['vnum']) for l in fl])
j = initiate(acct, v_fl, COM, TODAY, batch_ref='BATCH-20261010-B', bank_ref='CB-TRF-000113'); j2 = paid(bank, v_fl, TODAY, 'CB-TRF-000113')
d4 = desk(); fl = [l for l in d4['lines'] if l['supplier_acc'] == '6-9-23-11075' and l['source_type'] == 'activity']
check(13, 'paid once → both lines Fully Paid (8,000 + 6,000 = 14,000); one advice for the supplier', j.get('status') == 200 and j2.get('status') == 200 and all(l['fully_paid'] for l in fl) and sum(l['paid_lkr'] for l in fl) == 14000 and sql(f"SELECT COUNT(*) FROM tpd_remittance WHERE vnum={v_fl}") == '1', [(l['status'], l['paid_lkr']) for l in fl])
rp = req.raw(f'tour/tour_payment_dashboard/remittance/{v_fl}').text
check(13, 'the advice carries the supplier and both invoice numbers', 'Fright Less' in rp and 'FLJ-3301' in rp and 'FLJ-3302' in rp, [x for x in ['Fright Less', 'FLJ-3301', 'FLJ-3302'] if x not in rp])
vp = acct.raw(f'tour/tour_payment_dashboard/voucher/{v_fl}').text
check(5, 'the voucher lists both supplier invoices as supporting documents', 'FLJ-3301' in vp and 'FLJ-3302' in vp)

# ============ 15. published vs tactical rate (the existing payment-stage review, unchanged) ============
print('\n== 15. Published versus tactical hotel rate — Radisson Blu Resort (day 5)')
sql("INSERT INTO room_type (rtype) SELECT 'Deluxe Sea View Room' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM room_type WHERE rtype='Deluxe Sea View Room')")
rt = sql("SELECT id FROM room_type WHERE rtype='Deluxe Sea View Room' ORDER BY id LIMIT 1")
sql(f"UPDATE tour_quotations SET hotel_acc='6-9-11-10561', hotel_name='Radisson Blu Resort', meal_code='hb', sgl=130.00, room_type={rt} WHERE quotation_id='{TOUR}' AND id=12781")
sql(f"UPDATE consignee SET create_time='2026-09-15 10:00:00' WHERE tour_id='{TOUR}'")   # booked inside the tactical's booking window (1 Sep – 31 Oct 2026)
sql("UPDATE hr2_contract SET status='approved', approved_by='tcadmin', approved_at=NOW() WHERE id=325 AND status='draft'")
sql("UPDATE supplier SET email='radisson-test@example.invalid' WHERE Sup_Acc_No='6-9-11-10561'")
acct.post('master_files/supplier_bank/save', data={'sup_acc': '6-9-11-10561', 'beneficiary_name': 'Radisson Blu Resort', 'bank_name': 'Test Bank', 'branch': 'Galle', 'account_no': 'TEST1009009009', 'currency': 'LKR', 'remittance_email': 'radisson-test@example.invalid'}); bank.post('master_files/supplier_bank/verify', data={'sup_acc': '6-9-11-10561', 'note': 'test'})
admin.post('tour/tour_payment_dashboard/recalc_hotel_cost', data={'tour_id': TOUR})
dA = desk(appr); rad = line(dA, 'hotel:6-9-11-10561')
offer = rad and rad.get('cost_offer')
check(15, 'the approver (special-rate privilege) sees the tactical rate available on the Radisson night; the requester does not see the special rate', rad is not None and offer and offer.get('choice_needed') and any(w['code'] == 'special_rate' for w in rad['warnings']) and line(desk(req), 'hotel:6-9-11-10561').get('cost_offer') is None, (rad and rad['warnings'], bool(offer)))
fitting = [o for o in (offer or {}).get('offer_options', []) if o.get('nights_fit', 0) > 0]
check(15, 'quoted $%s (no published rate on file for this room) vs tactical $%s on offer' % (rad['quoted_usd'], fitting and fitting[0].get('total_if_used')), bool(fitting) and fitting[0].get('total_if_used') == 125 and rad['quoted_usd'] == 130, (offer or {}).get('offer_options'))
j = legacy_request(req, rad, rad['amount'])
check(15, 'the requester\'s request is HELD for the rate review (not requested, no accounting entry) — the special rate stays hidden from quotation staff', j.get('status') == 200 and j.get('held') and sql(f"SELECT COUNT(*) FROM payment_request WHERE tour_id='{TOUR}' AND hotel_acc='6-9-11-10561'") == '0', j)
dA = desk(appr); rad = line(dA, 'hotel:6-9-11-10561')
check(15, 'dashboard: On Hold, "Review & release" offered to the approver', rad['status'] == 'on_hold' and rad['actions']['review_rate'], (rad['status'], rad['actions']))
hold_id = rad['rate_review']['hold_id']
j = appr.post('master_files/offer_selection/release', json_body={'hold_id': hold_id, 'choice': str(fitting[0]['plan_id']), 'amount': fitting[0]['total_if_used'], 'note': 'test: tactical rate taken'})
check(15, 'the approver chooses the TACTICAL rate and releases: the request is made at the tactical amount', j.get('status') == 200 and j.get('payment_request_id'), j)
dA = desk(appr); rad = line(dA, 'hotel:6-9-11-10561'); txr = rad['transactions'][-1]
check(15, 'request at the tactical amount $%s (quoted $%s); the decision and who made it in the audit record' % (txr['amount'], rad['quoted_usd']), abs(txr['amount'] - fitting[0]['total_if_used']) < 0.01 and any(a['action'] in ('rate_released', 'rate_chosen', 'rate_offer') and a['user'] == 'tcpayappr' for a in dA['audit']), ([ (a['action'], a['user']) for a in dA['audit'] if a['action'].startswith('rate_')][:5]))
j = approve(appr, txr['pr_id'])
check(15, 'approved at the tactical amount → waits in Supplier Payables with its approval figures ($125 × 329.10)', j.get('status') == 200 and j.get('awaiting_batch') and sql(f"SELECT CONCAT(amount,'|',approval_rate) FROM tpd_request_ext WHERE pr_id={txr['pr_id']}") == '125.00|329.1000', j)

# ============ 18. driver advance ============
print('\n== 18. Driver advance')
d5 = desk(); dr = line(d5, 'driver_advance:')
j = legacy_request(req, dr, 20000)
check(18, 'driver advance requested from the driver\'s own advance request (LKR 20,000)', j.get('status') == 200, j)
d5 = desk(); dr = line(d5, 'driver_advance:'); txd = dr['transactions'][-1]
j = approve(appr, txd['pr_id'])
check(18, 'approved → waits under the driver in Supplier Payables (no bank-verification gate for a driver)', j.get('status') == 200 and j.get('awaiting_batch') and waiting('6-9-11-10639') is not None, j)
j = make_batch(appr, '6-9-11-10639', [txd['pr_id']], COM, individual=1, bypass_reason='driver_advance', bypass_note='tour starts tomorrow'); v_d = j.get('vnum'); b_d = j.get('batch_id')
check(33, 'DRIVER ADVANCE uses Pay Individually Now: a single-item batch at once (reason "Driver advance" recorded), advance voucher (advances_pay 1), LKR 20,000, in the accountant\'s queue', j.get('status') == 200 and j.get('individual') and sql(f"SELECT CONCAT(individual,'|',bypass_reason,'|',bypass_note) FROM tpd_batch WHERE id={b_d}") == '1|driver_advance|tour starts tomorrow' and sql(f"SELECT CONCAT(advances_pay,'|',Amount) FROM voucher WHERE Vnum={v_d}") == '1|20000.00' and sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action='accumulation_bypassed' AND vnum={v_d}") == '1', j)
d5 = desk(); dr = line(d5, 'driver_advance:')
check(33, 'the dashboard shows the driver advance as paid individually with its reason', dr['open_tx']['batch']['individual'] and dr['open_tx']['batch']['bypass_label'] == 'Driver advance', dr['open_tx'].get('batch'))
j = initiate(acct, v_d, COM, TODAY, bank_ref='CB-TRF-000114', charges='75')
sql("DELETE FROM tpd_meta WHERE k='bank_charge_ledger_code'")
j2 = paid(bank, v_d, TODAY, 'CB-TRF-000114')
check(43, 'driver advance (LKR) with charges 75 recorded at initiation: Paid blocked while the Bank Charges mapping is missing (409); nothing written', j2.get('status') == 409 and j2.get('config_warning') and sql(f"SELECT finalized FROM voucher WHERE Vnum={v_d}") == '0' and sql(f"SELECT COUNT(*) FROM tpd_bank_approval WHERE vnum={v_d}") == '0', j2)
acct.post('tour/tour_payment_dashboard/setup', data={'bank_charge_ledger_code': '4-8-109-31903'})
j2 = paid(bank, v_d, TODAY, 'CB-TRF-000114')
rc = recon(v_d)
check(42, "driver advance paid with charges 75.00 taken from the initiation: posted once (Dr Bank Charges / Cr Com Bank, the driver's tour); reconciled — 20,000.00 + 75.00 = 20,075.00 = the bank debit", j2.get('status') == 200 and charge_pairs(v_d) == f'{TOUR}:4-8-109-31903:75.00:0.00;{TOUR}:1-6-8-10380:0.00:75.00' and rc['reconciled'] and rc['totals']['bank_credit'] == 20075.0 and sql(f"SELECT CONCAT(charges,'|',total_debit,'|',charges_posted) FROM tpd_bank_approval WHERE vnum={v_d}") == '75.00|20075.00|1', (j2.get('message'), charge_pairs(v_d)))
d5 = desk(); dr = line(d5, 'driver_advance:')
check(18, 'driver advance paid; line Fully Paid; settlement line stays for the log sheet', j.get('status') == 200 and j2.get('status') == 200 and dr['fully_paid'], (dr['status'], dr.get('legacy_status')))

# ============ expense + rejection + cancel ============
print('\n== Add expense · reject · cancel')
j = req.post('tour/tour_payment_dashboard/expense', data={'tour_id': TOUR, 'service_date': '2026-10-14', 'category': 'Meal', 'supplier_acc': '6-9-11-11590', 'description': 'Extra dinner on day 4 (added on tour)', 'currency': 'LKR', 'amount': '3200', 'invoice_no': 'GIM-0915'})
ex_id = j.get('id'); check(19, 'expense added (restaurant, LKR 3,200) and placed on its day', j.get('status') == 200 and ex_id, j)
j = req.post('tour/tour_payment_dashboard/expense', data={'tour_id': TOUR, 'service_date': '2026-10-14', 'category': 'Meal', 'supplier_acc': '6-9-11-11590', 'description': 'Extra dinner on day 4 (added on tour)', 'currency': 'LKR', 'amount': '3200'})
check(12, 'the same expense is not added twice (409)', j.get('status') == 409, j)
d6 = desk(); ex = line(d6, f'expense:{ex_id}')
check(19, 'expense line on Day 4 with the invoice number, Payment Not Requested', ex and ex['day_no'] == 4 and ex['invoice_no'] == 'GIM-0915' and ex['status'] == 'not_requested', ex and (ex['day_no'], ex['invoice_no'], ex['status']))
j = req.post('tour/tour_payment_dashboard/expense_request', data={'expense_id': ex_id})
d6 = desk(); ex = line(d6, f'expense:{ex_id}'); txe = ex['transactions'][-1]
check(19, 'expense requested into the chain (type service fee)', j.get('status') == 200 and ex['status'] == 'awaiting' and txe['lkr'] == 3200, (j, ex['status']))
j = appr.post('tour/tour_payment_dashboard/reject', data={'pr_id': txe['pr_id'], 'reason': 'test: duplicate of the voucher meal'})
d6 = desk(); ex = line(d6, f'expense:{ex_id}')
check(21, 'rejected with a reason → line Rejected, can be requested again', j.get('status') == 200 and ex['status'] == 'rejected' and ex['actions']['request'], (ex['status'], ex['actions']['request']))
j = req.post('tour/tour_payment_dashboard/expense_request', data={'expense_id': ex_id})
d6 = desk(); ex = line(d6, f'expense:{ex_id}'); txe = ex['transactions'][-1]
j2 = req.post('tour/tour_payment_dashboard/cancel_request', data={'pr_id': txe['pr_id'], 'reason': 'test: cancelled by the requester'})
d6 = desk(); ex = line(d6, f'expense:{ex_id}')
check(21, 're-requested then cancelled by the requester with a reason: line back to Not Requested, both attempts kept in its history', j.get('status') == 200 and j2.get('status') == 200 and ex['status'] == 'not_requested' and len(ex['transactions']) == 2 and [t['stage'] for t in ex['transactions']] == ['rejected', 'cancelled'], (j2, ex['status'], [t['stage'] for t in ex['transactions']]))

# ============ 16/17. itinerary refresh, change, removal ============
print('\n== 16/17. Itinerary refresh · changed · removed services')
before = sorted(l['key'] for l in desk()['lines'])
again = sorted(l['key'] for l in desk()['lines'])
check(16, 'loading the dashboard twice gives the same lines — nothing duplicated', before == again and len(before) == len(set(before)), len(before))
sql(f"INSERT INTO tour_activity (tour_id, tour_row, tour_date, name, code, fee, ref_no, supplier_id, supplier_name, payment_request, lkr_fee, is_prepaid) VALUES ({TOUR}, 7, '2026-10-15', 'Galle Face sunset tea', NULL, 1500, 0, '6-9-11-11612', 'GALLERY CAFE RESTAURANT', 0, 1500, 0)")
after = desk()
check(16, 'a new itinerary item appears as one new unpaid line; the paid lines are unchanged', len(after['lines']) == len(before) + 1 and line(after, 'hotel:6-9-11-10626')['paid_lkr'] == 17998.75 and line(after, 'hotel:6-9-11-10626')['transactions'][0]['usd_rate'] == 327.25, len(after['lines']))
sql(f"UPDATE tour_quotations SET confirm=3 WHERE quotation_id='{TOUR}' AND hotel_acc='6-9-11-10626'")
d7 = desk(); orphan = [l for l in d7['lines'] if l['orphan'] and l['supplier_acc'] == '6-9-11-10626']
check(17, 'the paid COVENRO night removed from the itinerary: its payment stays, flagged "no longer on the itinerary", Requires Checking filter', len(orphan) == 1 and orphan[0]['paid_lkr'] == 17998.75 and any(f['code'] == 'removed' for f in orphan[0]['flags']) and 'check' in orphan[0]['filters'], [(l['status'], l['paid_lkr'], [f['code'] for f in l['flags']]) for l in orphan])
check(17, 'nothing in the chain was altered by the removal (voucher, lock, advice intact)', sql(f"SELECT CONCAT(finalized,'|',Amount) FROM voucher WHERE Vnum={v_cov}") == '1|17998.75' and sql(f"SELECT status FROM tpd_remittance WHERE vnum={v_cov}") == 'sent')
sql(f"UPDATE tour_quotations SET confirm=0 WHERE quotation_id='{TOUR}' AND hotel_acc='6-9-11-10626'")   # put it back for the demonstration
sql(f"UPDATE tour_quotations SET confirm=3 WHERE quotation_id='{TOUR}' AND hotel_acc='6-9-11-11496' AND tour_date='2026-10-13'")
admin.post('tour/tour_payment_dashboard/recalc_hotel_cost', data={'tour_id': TOUR})
d7 = desk(); kas = line(d7, 'hotel:6-9-11-11496')
check(17, 'Kassapa\'s second night removed after payment: warning "requested $140 but the itinerary now prices $70"; the two paid vouchers untouched', any(w['code'] == 'amount_changed' for w in kas['warnings']) and kas['paid_lkr'] == 45787.0 and kas['usd_amount'] == 70, ([w['code'] for w in kas['warnings']], kas['usd_amount'], kas['paid_lkr']))
sql(f"UPDATE tour_quotations SET confirm=0 WHERE quotation_id='{TOUR}' AND hotel_acc='6-9-11-11496' AND tour_date='2026-10-13'"); admin.post('tour/tour_payment_dashboard/recalc_hotel_cost', data={'tour_id': TOUR})

# ============ 20/30. historical protection; the approval figures never overwritten ============
print('\n== 20/30. Historical payments untouched · approval figures unchanged')
post = {t: sql(f"SELECT COUNT(*), MD5(GROUP_CONCAT(CONCAT_WS('|',{cols}) ORDER BY {k})) FROM {t} WHERE {k} <= {pre_max[t]}") for t, k, cols in [
    ('voucher', 'ID', 'ID,Vnum,Acc_No,Amount,Aflag,finalized,bank_date,Bank_acc'), ('v_inv', 'ID', 'ID,V_tkn,Invoice,Amount,Rate'), ('payment_request', 'id', 'id,tour_id,hotel_acc,requestAmount,payment_request,voucher_created'), ('account_runing', 'ID', 'ID,L_Code,Dr,Cr,Invoice_Num')]}
for t in post:
    check(20, f'{t}: the rows that existed before are byte-for-byte the same ({pre[t].split(chr(9))[0]} rows)', pre[t].split('\t')[0] == post[t].split('\t')[0] and pre[t].split('\t')[2] == post[t].split('\t')[1], (pre[t], post[t]))
check(20, 'the dashboard still shows the 2024 vouchers of other tours untouched (voucher 605 Heritance Kandalama, unpaid)', sql("SELECT CONCAT(Amount,'|',finalized) FROM voucher WHERE Vnum=605") == '239600.00|0')
check(30, 'the approval figures of every payment recorded before the first Paid are byte-for-byte the same after four payments at three different rates', approval_fingerprint() != '' and sql(f"SELECT CONCAT(approval_rate,'|',approval_lkr,'|',approved_by) FROM tpd_request_ext WHERE pr_id={pr_cov}") == '327.2500|17998.75|tcpayappr' and sql(f"SELECT CONCAT(approval_rate,'|',approval_lkr) FROM tpd_request_ext WHERE pr_id={pr_k1}") == '327.2500|22907.50' and sql(f"SELECT CONCAT(approval_rate,'|',approval_lkr) FROM tpd_request_ext WHERE pr_id={pr_k2}") == '329.1000|23037.00' and sql(f"SELECT GROUP_CONCAT(CONCAT(approval_rate,'|',approval_lkr) ORDER BY pr_id) FROM tpd_request_ext WHERE batch_id={b_c}") == '325.0000|19500.00,325.0000|19500.00', 'see the request records')
check(30, 'the exchange-rate differences are recorded separately, per payment: 0.00 · +129.50 · −287.00 · +246.00 · +246.00 — and the ledger carries the final LKR of each voucher', sql("SELECT GROUP_CONCAT(fx_diff ORDER BY pr_id) FROM tpd_request_ext WHERE fx_diff IS NOT NULL AND currency='USD'") == '0.00,129.50,-287.00,246.00,246.00', sql("SELECT GROUP_CONCAT(fx_diff ORDER BY pr_id) FROM tpd_request_ext WHERE fx_diff IS NOT NULL AND currency='USD'"))
check(30, 'every foreign-currency Paid posted its difference (four USD vouchers: COVENRO nothing to post, Kassapa loss, Kassapa gain, Cassamara two tours) — 4 balanced pairs in the ledger, Dr = Cr', sql("SELECT COUNT(*) FROM tpd_bank_approval WHERE fx_posted=1") == '4' and sql("SELECT COUNT(DISTINCT ID) FROM account_runing WHERE advances_pay=23") == '4' and sql("SELECT SUM(Dr)-SUM(Cr) FROM account_runing WHERE advances_pay=23") == '0.00', (sql("SELECT COUNT(*) FROM tpd_bank_approval WHERE fx_posted=1"), sql("SELECT COUNT(DISTINCT ID) FROM account_runing WHERE advances_pay=23")))

# ============ 21. audit ============
print('\n== 21. Audit history')
acts = {r.split('\t')[0]: int(r.split('\t')[1]) for r in sql(f"SELECT action, COUNT(*) FROM tpd_audit WHERE tour_id='{TOUR}' OR action IN ('rate_confirmed','bank_account_saved','supplier_bank_saved','supplier_bank_verified','batch_created','fx_mapping_saved','account_confirmed','bank_account_changed','accumulation_bypassed','fx_posted') GROUP BY action").split('\n')}
acts['supplier_bank_confirmed'] = int(sql("SELECT COUNT(*) FROM tpd_audit WHERE action='supplier_bank_confirmed'") or 0)
need = ['rate_confirmed', 'request_made', 'approved', 'batch_created', 'accumulation_bypassed', 'voucher_issued', 'bank_initiated', 'account_confirmed', 'bank_account_changed', 'paid', 'fx_posted', 'fx_mapping_saved', 'remittance_prepared', 'remittance_sent', 'rejected', 'request_cancelled', 'expense_added', 'bank_account_saved', 'supplier_bank_saved', 'supplier_bank_verified', 'supplier_bank_confirmed']
check(21, 'every action kind recorded: ' + ', '.join(need), all(acts.get(a, 0) > 0 for a in need), {a: acts.get(a, 0) for a in need})
row = sql(f"SELECT CONCAT(user,'|',at,'|',action,'|',usd_rate,'|',bank_account,'|',bank_ref) FROM tpd_audit WHERE vnum={v_k1} AND action='paid'")
check(21, 'a Paid entry carries user, exact time (ms), the final rate, bank account and reference', row.startswith('tcbankappr|') and '329.1' in row and 'Sampath LKR account' in row and 'CB-TRF-000112' in row and re.search(r'\d{2}:\d{2}:\d{2}\.\d{3}', row), row)
check(21, 'the approval entry keeps the approval rate and provisional LKR; the Paid entry keeps the provisional LKR beside the final', sql(f"SELECT new_value FROM tpd_audit WHERE action='approved' AND ref_id='{pr_k1}'").find('327.25') >= 0 and sql(f"SELECT new_value FROM tpd_audit WHERE action='paid' AND vnum={v_k1}").find('22907.5') >= 0)
check(21, 'the audit table has no update/delete endpoint; the rate changes kept previous and new values', sql("SELECT COUNT(*) FROM tpd_audit WHERE action='rate_confirmed' AND prev_value LIKE '%327.25%' AND new_value LIKE '%329.1%'") == '1')
d9 = desk(req)
check(21, 'the dashboard shows the complete history and the audit record to the requester', len(d9['history']) >= 20 and len(d9['audit']) >= 25, (len(d9['history']), len(d9['audit'])))

# ============ 39/40. the official Payment Voucher No. everywhere · duplication and editing refused ============
print('\n== 39/40. The official Payment Voucher No. (Cassamara two-tour voucher) · duplication and editing refused')
V = v_c; tag = f'Payment Voucher No. {V}'
places = {}
places['Tour Payment Dashboard (AH 2218)'] = line(desk(), 'hotel:6-9-11-11047')['transactions'][-1]['payment_voucher_no'] == V and line(desk(), 'hotel:6-9-11-11047')['transactions'][-1]['vnum'] == V
places['Tour Payment Dashboard (AH 2290)'] = line(desk(mid=MID2), 'hotel:6-9-11-11047')['transactions'][-1]['payment_voucher_no'] == V
places['Supplier Payables (batches)'] = any(b['vnum'] == V and b['id'] == b_c for b in payables()['batches'])
bdc = bdata(b_c, acct); places['Supplier batch page'] = bdc['payment_voucher_no'] == V and bdc['batch']['vnum'] == V
vh = acct.raw(f'tour/tour_payment_dashboard/voucher/{V}').text; places['Payment voucher PDF / page'] = tag in vh and f'<title>{tag}' in vh
places["Accountant's payment page / bank approval record (the lock)"] = int(sql(f"SELECT vnum FROM tpd_bank_approval WHERE vnum={V}") or 0) == V and bdc['approval']['vnum'] == V
rh = req.raw(f'tour/tour_payment_dashboard/remittance/{V}').text; places['Remittance advice'] = tag in rh
jr = req.post('tour/tour_payment_dashboard/remittance_prepare', data={'vnum': V}); places['Supplier e-mail (subject and body)'] = jr.get('status') == 200 and tag in jr['remittance']['subject'] and tag in jr['remittance']['body']
tr = admin.raw('tour/board/tour/4/record').text; places['Complete Tour Record'] = tag in tr or (f'<b>{V}</b>' in tr and 'Payment Voucher No.' in tr)
places['Audit history'] = int(sql(f"SELECT COUNT(*) FROM tpd_audit WHERE vnum={V}")) >= 5 and any(f'{V}' in (h.get('ref') or '') and 'Payment Voucher No.' in (h.get('ref') or '') for h in desk()['history'])
check(39, 'the same official Payment Voucher No. ' + str(V) + ' appears in: ' + ', '.join(places), all(places.values()), {k: v for k, v in places.items() if not v})
check(39, 'a consolidated supplier batch has ONE official voucher number while both supplier invoices stay listed separately on the voucher and the advice', vh.count(tag) >= 1 and 'CAS-INV-2210' in vh and 'CAS-INV-2290' in vh and 'CAS-INV-2210' in rh and 'CAS-INV-2290' in rh and sql(f"SELECT COUNT(DISTINCT voucher_vnum) FROM tpd_request_ext WHERE batch_id={b_c}") == '1')
check(39, 'one number per voucher, never reused: every voucher in the table has a distinct Vnum (unique index in place)', sql("SELECT COUNT(*)-COUNT(DISTINCT Vnum) FROM voucher") == '0' and sql("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='avenir_test' AND TABLE_NAME='voucher' AND INDEX_NAME='uq_tpd_voucher_vnum'") >= '1')
r = subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '-uroot', '-N', 'avenir_test', '-e', f"CREATE TEMPORARY TABLE tv AS SELECT * FROM voucher WHERE Vnum={V}; UPDATE tv SET ID=0; INSERT INTO voucher SELECT * FROM tv"], capture_output=True, text=True)
check(40, 'DUPLICATION refused: inserting a second voucher with the same Payment Voucher No. fails at the database (unique index)', r.returncode != 0 and 'Duplicate' in r.stderr and sql(f"SELECT COUNT(*) FROM voucher WHERE Vnum={V}") == '1', r.stderr[:160])
r = subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '-uroot', '-N', 'avenir_test', '-e', f"UPDATE voucher SET Vnum=999999 WHERE Vnum={V}"], capture_output=True, text=True)
check(40, 'EDITING refused: changing an issued Payment Voucher No. fails at the database (update guard); the number is unchanged', r.returncode != 0 and 'cannot be changed' in r.stderr and sql(f"SELECT COUNT(*) FROM voucher WHERE Vnum={V}") == '1', r.stderr[:160])
j = make_batch(appr, '6-9-11-11047', [pr_c1], COM)
check(40, 'the desk never re-issues: batching an already-vouchered payment again is refused', j.get('status') == 400, j)
r = subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '-uroot', '-N', 'avenir_test', '-e', f"UPDATE voucher SET remark='test: other columns still editable' WHERE Vnum={V}"], capture_output=True, text=True)
check(40, 'the guard touches only the number: other voucher columns still update as before', r.returncode == 0, r.stderr[:160])

# ============ 44. a second multi-tour batch with UNEQUAL lines and an odd charge: Gimanhala on AH 2218 (3,200) and AH 2290 (1,800), charges 75.55 ============
print('\n== 44. Multi-tour allocation with unequal lines — Gimanhala expenses on AH 2218 (LKR 3,200) and AH 2290 (LKR 1,800), bank charges 75.55')
j = req.post('tour/tour_payment_dashboard/expense_request', data={'expense_id': ex_id})
check(44, 'the AH 2218 extra dinner (LKR 3,200, cancelled earlier) is requested again', j.get('status') == 200, j)
j = req.post('tour/tour_payment_dashboard/expense', data={'tour_id': TOUR2, 'service_date': '2026-10-21', 'category': 'Meal', 'supplier_acc': '6-9-11-11590', 'description': 'Lunch on the way (added on tour)', 'currency': 'LKR', 'amount': '1800', 'invoice_no': 'GIM-2290'})
ex2 = j.get('id'); j2 = req.post('tour/tour_payment_dashboard/expense_request', data={'expense_id': ex2})
check(44, 'a Gimanhala expense on AH 2290 (LKR 1,800) added and requested', j.get('status') == 200 and j2.get('status') == 200, (j, j2))
g1 = line(desk(), f'expense:{ex_id}')['transactions'][-1]['pr_id']; g2 = line(desk(mid=MID2), f'expense:{ex2}')['transactions'][-1]['pr_id']
j = approve(appr, g1); j2 = approve(appr, g2)
w = waiting('6-9-11-11590')
check(44, 'both approved → Gimanhala waits with 2 payments on 2 tours (LKR 5,000)', j.get('status') == 200 and j2.get('status') == 200 and w and w['item_count'] == 2 and w['tour_count'] == 2 and w['approval_lkr_total'] == 5000.0, w and (w['item_count'], w['tour_count'], w['approval_lkr_total']))
j = make_batch(appr, '6-9-11-11590', [g1, g2], COM, note='two tours, unequal lines'); v_g2 = j.get('vnum'); b_g2 = j.get('batch_id')
j1 = initiate(acct, v_g2, COM, TODAY, charges='75.55', bank_ref='CB-TRF-000140')
bd = bdata(b_g2, bank)
check(44, 'the page previews the allocation before Paid: 75.55 pro rata to 3,200 / 1,800 → 48.35 to AH 2218 and 27.20 to AH 2290 (to the cent, adding up exactly)', j.get('status') == 200 and j1.get('status') == 200 and sorted((c['manual_id'], c['share']) for c in bd['charge_shares']) == [('AH 2218', 48.35), ('AH 2290', 27.2)] and bd['charges'] == 75.55, (j1.get('message'), bd.get('charge_shares')))
j2 = paid(bank, v_g2, TODAY, 'CB-TRF-000140')
rc = recon(v_g2)
check(44, 'Paid: the charges posted as two balanced pairs — 48.35 on AH 2218 (2325) and 27.20 on AH 2290 (2390), Dr Bank Charges / Cr Com Bank; sum 75.55; recorded once', j2.get('status') == 200 and charge_pairs(v_g2) == '2325:4-8-109-31903:48.35:0.00;2325:1-6-8-10380:0.00:48.35;2390:4-8-109-31903:27.20:0.00;2390:1-6-8-10380:0.00:27.20' and sql(f"SELECT CONCAT(charges,'|',charges_posted,'|',total_debit) FROM tpd_bank_approval WHERE vnum={v_g2}") == '75.55|1|5075.55', (j2.get('message'), charge_pairs(v_g2)))
check(44, 'reconciled: 3,200.00 + 1,800.00 (payment lines) + 48.35 + 27.20 (charge lines) = 5,075.55 credited to Com Bank = the bank debit', rc['reconciled'] and rc['totals']['payment_credit'] == 5000.0 and rc['totals']['charge_credit'] == 75.55 and rc['totals']['bank_credit'] == 5075.55 and rc['totals']['total_debit'] == 5075.55, rc and rc['totals'])
gx1 = line(desk(), f'expense:{ex_id}')['transactions'][-1]; gx2 = line(desk(mid=MID2), f'expense:{ex2}')['transactions'][-1]
check(44, "each tour's payment record shows its own share: AH 2218 — 48.35 (debited for this tour 3,248.35); AH 2290 — 27.20 (1,827.20)", gx1['charge_share'] == 48.35 and gx1['tour_total_debit'] == 3248.35 and gx2['charge_share'] == 27.2 and gx2['tour_total_debit'] == 1827.2, (gx1.get('charge_share'), gx1.get('tour_total_debit'), gx2.get('charge_share'), gx2.get('tour_total_debit')))
tr = admin.raw('tour/board/tour/4/record').text
check(44, 'the Complete Tour Record of AH 2218 shows the bank-charge share column: 150.00 (Cassamara batch) and 48.35 (Gimanhala batch), each marked posted', "Bank charges (this tour's share)" in tr and '48.35 · posted' in tr and '150.00 · posted' in tr and 'of 75.55 on the batch' in tr, [x for x in ["Bank charges (this tour's share)", '48.35 · posted', '150.00 · posted', 'of 75.55 on the batch'] if x not in tr])
# the allocation rule itself (the same code the server runs), on the awkward cases
PHP_ALLOC = 'require "%s/tm/app/Libraries/TourPayments/Batches.php"; use App\\Libraries\\TourPayments\\Batches;' % S + '''
$cases = [
 ["100.01 over 19746/19746", [["key"=>"a","basis"=>19746],["key"=>"b","basis"=>19746]], 100.01],
 ["100 over three equal", [["key"=>"a","basis"=>1],["key"=>"b","basis"=>1],["key"=>"c","basis"=>1]], 100],
 ["0.01 over two", [["key"=>"a","basis"=>5],["key"=>"b","basis"=>5]], 0.01],
 ["55 with no basis (equal split)", [["key"=>"a","basis"=>0],["key"=>"b","basis"=>0],["key"=>"c","basis"=>0]], 55],
 ["75.55 over 3200/1800", [["key"=>"a","basis"=>3200],["key"=>"b","basis"=>1800]], 75.55],
 ["1 line", [["key"=>"a","basis"=>123.45]], 99.99],
 ["0 charges", [["key"=>"a","basis"=>10],["key"=>"b","basis"=>20]], 0],
];
foreach ($cases as [$n, $l, $amt]) { $s = Batches::allocate($l, $amt); echo $n, " => ", json_encode($s), " sum=", number_format(array_sum($s), 2), "\\n"; }'''
r = subprocess.run(['php', '-r', PHP_ALLOC], capture_output=True, text=True)
out = r.stdout
check(44, 'the allocation rule (largest remainder, to the cent) on the awkward cases: 100.01 → 50.01/50.00; 100 over three → 33.34/33.33/33.33; 0.01 → 0.01/0.00; no basis → equal 18.34/18.33/18.33; 75.55 → 48.35/27.20; one line takes all; 0 → zeros — every sum exact', '{"a":50.01,"b":50}' in out and '{"a":33.34,"b":33.33,"c":33.33}' in out and '{"a":0.01,"b":0}' in out and '{"a":18.34,"b":18.33,"c":18.33}' in out and '{"a":48.35,"b":27.2}' in out and '{"a":99.99}' in out and '{"a":0,"b":0}' in out and all(x in out for x in ['sum=100.01', 'sum=100.00', 'sum=0.01', 'sum=55.00', 'sum=75.55', 'sum=99.99', 'sum=0.00']), out[:600] + r.stderr[:300])

# ============ 45. duplicate voucher attempts — the number counter behind the table (the live risk the index protects against) ============
print('\n== 45. Duplicate voucher attempts — counter behind MAX(Vnum), a repeated batch, a raw duplicate insert')
vmax = int(sql("SELECT MAX(Vnum) FROM voucher")); counter = int(sql("SELECT V_Num FROM invoice_no LIMIT 1"))
check(45, 'the voucher-number counter (invoice_no.V_Num) is in step with the table: counter %d = MAX(Vnum) %d' % (counter, vmax), counter == vmax, (counter, vmax))
# a request to batch: the Radisson night approved earlier (tactical $125) is still waiting
w = waiting('6-9-11-10561'); pr_r = w['items'][0]['pr_id'] if w else None
sql("UPDATE invoice_no SET V_Num = V_Num - 1")   # the counter slips behind (the situation a restored backup or a manual edit could create)
nv = sql("SELECT COUNT(*) FROM voucher"); lcx = ledger_counts()
j = make_batch(appr, '6-9-11-10561', [pr_r], COM)
check(45, 'with the counter one behind, the next voucher would REUSE an issued number: the database refuses it (unique index) — no voucher row, no batch, the request still waiting, the ledger untouched', j.get('status') != 200 and sql("SELECT COUNT(*) FROM voucher") == nv and waiting('6-9-11-10561') is not None and sql("SELECT COUNT(*) FROM tpd_batch WHERE sup_acc='6-9-11-10561'") == '0' and ledger_counts() == lcx and sql(f"SELECT CONCAT(payment_request,'|',voucher_created,'|',IFNULL(batch_id,'none')) FROM payment_request pr LEFT JOIN tpd_request_ext x ON x.pr_id=pr.id WHERE pr.id={pr_r}") in ('4|0|none', '6|0|none'), (j.get('status'), (j.get('message') or '')[:160]))
sql("UPDATE invoice_no SET V_Num = (SELECT MAX(Vnum) FROM voucher)")   # Accounts brings the counter back in step (the preflight's remedy)
j = make_batch(appr, '6-9-11-10561', [pr_r], COM, note='Radisson, after the counter was corrected'); v_r = j.get('vnum'); b_r = j.get('batch_id')
check(45, 'with the counter corrected the batch issues the next number %s cleanly; every Vnum still distinct' % v_r, j.get('status') == 200 and v_r == vmax + 1 and sql("SELECT COUNT(*)-COUNT(DISTINCT Vnum) FROM voucher") == '0', j.get('message'))

# ============ 46. REPEATED / CONCURRENT Paid submissions — two servers, the same voucher, the same instant ============
print('\n== 46. Repeated and concurrent Paid submissions — Radisson, two PHP servers at the same instant')
import threading
j = initiate(acct, v_r, COM, TODAY, charges='125', bank_ref='CB-TRF-000150')
assert j.get('status') == 200, j
subprocess.run(['bash', '-c', f'curl -s -o /dev/null http://127.0.0.1:8082/ || (cd {S} && setsid nohup php -S 127.0.0.1:8082 {S}/router_tmr.php >> {S}/php8082.log 2>&1 < /dev/null & sleep 2)'])
bank2 = Client('bank', base='http://127.0.0.1:8082/avenir/')
res = {}
def fire(name, c):
    res[name] = paid(c, v_r, TODAY, 'CB-TRF-000150', charges='125')
t1 = threading.Thread(target=fire, args=('a', bank)); t2 = threading.Thread(target=fire, args=('b', bank2))
t1.start(); t2.start(); t1.join(); t2.join()
codes = sorted(r.get('status') for r in res.values())
rc = recon(v_r)
check(46, 'two Paid submissions for the same voucher at the same instant (two servers, two sessions): exactly ONE succeeds (200) and one is refused (409) — one lock, the payment posted once, the charges posted once, reconciled', codes == [200, 409] and sql(f"SELECT COUNT(*) FROM tpd_bank_approval WHERE vnum={v_r}") == '1' and sql(f"SELECT COUNT(*) FROM account_runing WHERE Recpt_No={v_r} AND Discription LIKE 'VOUCHER PAYMENT%' AND L_Code='1-6-8-10380'") == '1' and charge_pairs(v_r) == f'{TOUR}:4-8-109-31903:125.00:0.00;{TOUR}:1-6-8-10380:0.00:125.00' and rc['reconciled'] and rc['totals']['charge_debit'] == 125, (codes, [r.get('message', '')[:100] for r in res.values()]))
j = paid(bank, v_r, TODAY, 'CB-TRF-000150', charges='125'); j2 = paid(bank2, v_r, TODAY, 'CB-TRF-000150', charges='999')
check(46, 'a third and a fourth submission later (one with different charges) are refused; the lock keeps charges 125.00; one set of postings', j.get('status') == 409 and j2.get('status') == 409 and sql(f"SELECT CONCAT(charges,'|',charges_posted) FROM tpd_bank_approval WHERE vnum={v_r}") == '125.00|1' and sql(f"SELECT COUNT(DISTINCT ID) FROM account_runing WHERE advances_pay=24 AND Discription LIKE '%Payment Voucher No. {v_r} %'") == '1', (j.get('status'), j2.get('status')))
check(46, 'the legacy Mark Finalized on an already-paid desk voucher is refused too (403); nothing changes', bank.post('report/bank_payment_check/mark_finalized', data={'pr_id': v_r, 'bank_date': TODAY}).get('status') == 403 and sql(f"SELECT CONCAT(finalized,'|',bank_date) FROM voucher WHERE Vnum={v_r}") == f'1|{TODAY}')
check(30, 'all bank charges in the ledger balance: Σ Dr Bank Charges = Σ Cr paying accounts; every charge pair Dr = Cr; every Paid lock reconciled', sql("SELECT SUM(Dr)-SUM(Cr) FROM account_runing WHERE advances_pay=24") == '0.00' and sql("SELECT COUNT(*) FROM tpd_bank_approval WHERE reconciled=0") == '0' and sql("SELECT ROUND(SUM(charges),2) FROM tpd_bank_approval") == sql("SELECT ROUND(SUM(Dr),2) FROM account_runing WHERE advances_pay=24"), (sql("SELECT ROUND(SUM(charges),2) FROM tpd_bank_approval"), sql("SELECT ROUND(SUM(Dr),2) FROM account_runing WHERE advances_pay=24")))

json.dump(RES, open(HERE + '/results.json', 'w'), indent=1)
failed = [r for r in RES if not r['ok']]
print(f"\n{len(RES) - len(failed)} ok, {len(failed)} failed")
