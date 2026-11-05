r"""tf-paydesk-r5 — tests 48–61: bulk supplier payment (one transfer, one voucher per item), separation of duties, the request
privilege split. Runs on the TEST COPY (8081 / avenir_test) in its post-suite state, through the real endpoints as the roles.
Writes results_bulk.json."""
import os, sys, json, subprocess, time, uuid, glob, re
from api import Client
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE); SOCK = S + '/mysql.sock'; SINK = S + '/smtpsink/msgs'
RES = []
def check(test, name, ok, detail=''):
    RES.append({'test': test, 'name': name, 'ok': bool(ok), 'detail': str(detail)[:500]}); print(('  ok   ' if ok else '  FAIL ') + f'[{test}] ' + name + ('' if ok else '   ' + str(detail)[:400])); return bool(ok)
def sql(q):
    r = subprocess.run(['mysql', '--socket=' + SOCK, '-uroot', '-N', 'avenir_test', '-e', q], capture_output=True, text=True)
    if r.returncode != 0: raise SystemExit('SQL failed: ' + r.stderr + '\n' + q[:300])
    return r.stdout.strip()
def rows(q):
    o = sql(q); return [l.split('\t') for l in o.split('\n')] if o else []
req, appr, acct, bank, admin = Client('requester'), Client('approver'), Client('accountant'), Client('bank'), Client('admin')
TOUR, MID, TOUR2, MID2 = '2325', 'AH 2218', '2390', 'AH 2290'
GIM = '6-9-11-11590'; JEEP = '6-9-23-11075'; KAS = '6-9-11-11496'
def desk(c, mid): return c.get('tour/tour_payment_dashboard/desk', manual_id=mid)['data']
def line(d, key): return next((l for l in d['lines'] if l['key'] == key), None)
def elig(sup, c=None): return (c or appr).get('tour/tour_payment_dashboard/bulk_eligibility', sup_acc=sup)['data']
def bulk(c, sup, ids, cur, acc, token=None, **extra): return c.post('tour/tour_payment_dashboard/bulk_create', data=dict({'sup_acc': sup, 'pr_ids[]': ids, 'currency': cur, 'bank_account_id': acc, 'submit_token': token or uuid.uuid4().hex}, **extra))
def bdata(bid, c=None): return (c or acct).get(f'tour/tour_payment_dashboard/bulk_data/{bid}')['data']
def expense(c, tour, mid, sup, desc, amount, cur='LKR', date='2026-10-13', inv=None):
    j = c.post('tour/tour_payment_dashboard/expense', data={'tour_id': tour, 'service_date': date, 'category': 'Meal', 'supplier_acc': sup, 'description': desc, 'currency': cur, 'amount': str(amount), 'invoice_no': inv or ''})
    if j.get('status') != 200: return None, j
    j2 = c.post('tour/tour_payment_dashboard/expense_request', data={'expense_id': j['id']})
    if j2.get('status') != 200: return None, j2
    pr = max(t['pr_id'] for t in line(desk(req, mid), 'expense:%s' % j['id'])['transactions'] if t.get('pr_id'))
    return pr, j2
TODAY = desk(req, MID)['today']
ATOMIC = bool(elig(GIM).get('atomic'))   # InnoDB voucher tables → one transaction; MyISAM → the desk's undo after a failure
print('voucher tables transactional (InnoDB):', ATOMIC)
COM = sql("SELECT id FROM tpd_bank_account WHERE ledger_code='1-6-8-10380' ORDER BY id LIMIT 1")
RUN = time.strftime('%H%M%S')
# today's rate for USD items
appr.post('tour/tour_payment_dashboard/confirm_rate', data={'rate': '327.25'})

# ============ 48 eligibility ============
print('\n== 48 eligibility: reasons, currencies, disputed, unverified bank')
prs = []
amts = [2500, 1800, 3200, 2750, 1950, 2500, 4100, 2200, 1800, 3050, 2500, 2650]
for i, a in enumerate(amts):
    tour, mid = (TOUR, MID) if i % 2 == 0 else (TOUR2, MID2)
    pr, j = expense(req, tour, mid, GIM, 'Lunch %02d (bulk test %s)' % (i + 1, RUN), a, date='2026-10-1%d' % (2 + i % 4) if tour == TOUR else '2026-10-2%d' % (i % 3), inv='GIM-B%02d' % (i + 1))
    assert pr, j
    assert appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': pr}).get('status') == 200
    prs.append(pr)
# one more Gimanhala lunch, approved, then DISPUTED by the accountant
prD, _ = expense(req, TOUR, MID, GIM, 'Lunch disputed (bulk test %s)' % RUN, 999, inv='GIM-DISP')
assert appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': prD}).get('status') == 200
jd = acct.post('tour/tour_payment_dashboard/dispute', data={'pr_id': prD, 'disputed': 1, 'reason': 'test: invoice total does not match the menu agreed'})
e = elig(GIM); lk = e['currencies'].get('LKR', {}) if isinstance(e['currencies'], dict) else {}
el_ids = [x['pr_id'] for x in lk.get('eligible', [])]
check(48, 'GIMANHALA: the 12 approved lunches are eligible (LKR 31,000.00 of this run) and the disputed one is excluded with its reason', jd.get('status') == 200 and all(p in el_ids for p in prs) and abs(sum(float(x['lkr_amount']) for x in lk.get('eligible', []) if x['pr_id'] in prs) - 31000) < 0.005 and any(x['pr_id'] == prD and x['reason'] == 'disputed' for x in lk.get('excluded', [])), (jd, lk.get('count'), lk.get('total'), [(x['pr_id'], x['reason']) for x in lk.get('excluded', [])]))
check(48, 'a disputed item needs a reason (400) and cannot be disputed by a requester (403)', acct.post('tour/tour_payment_dashboard/dispute', data={'pr_id': prs[0], 'disputed': 1, 'reason': ''}).get('status') == 400 and req.post('tour/tour_payment_dashboard/dispute', data={'pr_id': prs[0], 'disputed': 1, 'reason': 'x'}).get('status') == 403)
# USD + LKR items of one supplier → two currency groups
prU1, _ = expense(req, TOUR, MID, JEEP, 'Jeep extra hour (bulk test %s)' % RUN, 40, cur='USD', inv='FLJ-U1'); prU2, _ = expense(req, TOUR2, MID2, JEEP, 'Jeep entrance top-up (bulk test %s)' % RUN, 25, cur='USD', date='2026-10-21', inv='FLJ-U2')
prL1, _ = expense(req, TOUR, MID, JEEP, 'Jeep lunch pack (bulk test %s)' % RUN, 4500, inv='FLJ-L1')
for p in [prU1, prU2, prL1]: assert appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': p}).get('status') == 200, p
ej = elig(JEEP)
check(48, 'a supplier with USD and LKR items is shown as two currency groups (USD 65.00 · LKR 4,500.00) — one bulk batch is one currency', ej['currencies'].get('USD', {}).get('count') == 2 and abs(ej['currencies']['USD']['total'] - 65) < 0.005 and ej['currencies'].get('LKR', {}).get('count') == 1, {k: (v['count'], v['total']) for k, v in ej['currencies'].items()})
# an unverified-bank supplier (Kassapa) with an approved item → excluded
prK, jk = expense(req, TOUR, MID, KAS, 'Kassapa extra (bulk test %s)' % RUN, 5000, inv='KAS-X')
jv = bank.post('master_files/supplier_bank/verify', data={'sup_acc': KAS, 'note': 'test: verified for the bulk test'})   # the bank approver holds the verification privilege
ja = appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': prK}) if jv.get('status') == 200 else jv   # management approves while the bank is verified
old = sql(f"SELECT account_no FROM tpd_supplier_bank WHERE sup_acc='{KAS}'")
jb = acct.post('master_files/supplier_bank/save', data={'sup_acc': KAS, 'beneficiary_name': 'Kassapa Lions Rock', 'bank_name': 'Test Bank', 'branch': 'Test branch', 'account_no': 'TEST10020%s' % RUN, 'currency': 'LKR', 'swift': '', 'invoice_email': '', 'remittance_email': ''})   # the account changed after verification
ek = elig(KAS)
kx = [(x['pr_id'], x['reason']) for g in ek['currencies'].values() for x in g['excluded']]
check(48, 'Kassapa: approved while verified, then its bank account changed on the master → the verification is void and the item is excluded (status %s → reason %s)' % (ek['bank']['status'], {'changed': 'bank_changed', 'unverified': 'bank_unverified'}.get(ek['bank']['status'])), ja.get('status') == 200 and jb.get('status') == 200 and ek['bank']['status'] in ('changed', 'unverified') and sql(f"SELECT account_no FROM tpd_supplier_bank WHERE sup_acc='{KAS}'") != old and any(p == prK and r == {'changed': 'bank_changed', 'unverified': 'bank_unverified'}[ek['bank']['status']] for p, r in kx), (ja.get('status'), jb.get('status'), ek['bank']['status'], kx))

# ============ 49 selection re-validated ============
print('\n== 49 selection is re-validated on the server')
j = bulk(appr, GIM, prs + [prD], 'LKR', COM)
check(49, 'a selection that includes the disputed item is refused as a whole — nothing created', j.get('status') == 409 and 'disputed' in (j.get('message') or '') and sql(f"SELECT COUNT(*) FROM tpd_batch WHERE mode='bulk_parent'") == '0', j.get('message'))
j = bulk(appr, JEEP, [prU1, prL1], 'USD', COM)
check(49, 'a selection mixing currencies is refused — nothing created', j.get('status') in (409, 400) and sql("SELECT COUNT(*) FROM tpd_batch WHERE mode='bulk_parent'") == '0', j.get('message'))
j = bulk(appr, GIM, prs, 'LKR', COM, token='short')
check(49, 'a confirmation without a valid one-time token is refused', j.get('status') == 400, j.get('message'))
j = bulk(acct, GIM, prs, 'LKR', COM)
check(49, 'the accountant cannot create a bulk payment (needs the batch-creation privilege → 403)', j.get('status') == 403, j)

# ============ 50 issue ============
print('\n== 50 bulk issue: 12 vouchers, one batch')
C0 = int(sql("SELECT V_Num FROM invoice_no")); tok = uuid.uuid4().hex
before = (sql("SELECT COUNT(*) FROM tpd_batch"), sql("SELECT COUNT(*) FROM voucher"))
j50 = bulk(appr, GIM, prs, 'LKR', COM, token=tok, note='bulk test 50')
B = j50.get('batch_id'); vn = j50.get('vnums') or []
check(50, '12 vouchers issued, consecutive %s–%s, under bulk batch %s; one transfer LKR 31,000.00' % (C0 + 1, C0 + 12, j50.get('batch_no')), j50.get('status') == 200 and vn == list(range(C0 + 1, C0 + 13)) and 'one bank transfer of LKR 31,000.00' in (j50.get('message') or ''), j50)
d = bdata(B)
check(50, 'parent + 12 children: each child is a single-voucher batch with its own voucher, tour, service, invoice and amount; Σ = transfer', d['count'] == 12 and d['batch']['mode'] == 'bulk_parent' and d['batch']['vnum'] is None and all(c['stage'] == 'issued' for c in d['children']) and abs(d['totals']['voucher_total'] - 31000) < 0.005 and d['totals']['vouchers_equal_transfer'] and len(set(c['invoice_no'] for c in d['children'])) == 12, (d['count'], d['totals']))
check(50, 'every request flagged vouchered, linked to its own voucher and to the bulk batch; counter advanced by exactly 12', sql(f"SELECT COUNT(*) FROM payment_request WHERE id IN ({','.join(map(str, prs))}) AND voucher_created=1") == '12' and sql(f"SELECT COUNT(DISTINCT voucher_vnum) FROM tpd_request_ext WHERE pr_id IN ({','.join(map(str, prs))}) AND bulk_id={B}") == '12' and sql("SELECT V_Num FROM invoice_no") == str(C0 + 12))
check(50, 'each voucher row and its single v_inv line carry the tour (ref_po / Invoice), the restaurant and the amount', sql(f"SELECT COUNT(*) FROM voucher v JOIN v_inv i ON i.V_tkn=v.Vnum WHERE v.Vnum BETWEEN {C0+1} AND {C0+12} AND i.Invoice=v.ref_po AND i.Amount=v.Amount AND v.Acc_No='{GIM}'") == '12')
check(50, 'audit: bulk_issued with the 12 numbers, who confirmed, the token; one voucher_issued per tour line; batch recorded as %s' % ('atomic (InnoDB)' if ATOMIC else 'non-atomic (MyISAM — undo path)'), sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action='bulk_issued' AND ref_id='{d['batch']['batch_no']}'") == '1' and sql(f"SELECT engine_atomic FROM tpd_batch WHERE id={B}") == ('1' if ATOMIC else '0') and sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action='voucher_issued' AND vnum BETWEEN {C0+1} AND {C0+12}") == '12')
_e = elig(GIM)['currencies']; _ids = [x['pr_id'] for x in (_e.get('LKR', {}) if isinstance(_e, dict) else {}).get('eligible', [])]
check(50, 'the eligibility list no longer offers the 12 (they are batched)', not any(p in _ids for p in prs))

# ============ 53 duplicate submission ============
print('\n== 53 duplicate submission')
j = bulk(appr, GIM, prs, 'LKR', COM, token=tok)
check(53, 'the same confirmation token again returns the existing batch and creates nothing', j.get('status') == 200 and j.get('duplicate') and j.get('batch_id') == B and sql("SELECT COUNT(*) FROM tpd_batch WHERE mode='bulk_parent'") == '1', j)
j = bulk(appr, GIM, prs, 'LKR', COM)
check(53, 'a new confirmation for the same items is refused (already batched) — nothing created', j.get('status') in (400, 409) and sql("SELECT COUNT(*) FROM tpd_batch WHERE mode='bulk_parent'") == '1', j.get('message'))

# ============ 51 / 52 failure and counter (JEEP USD items) ============
print('\n== 51 failure mid-run · 52 counter behind')
cU = int(sql("SELECT V_Num FROM invoice_no")); b51 = (sql("SELECT COUNT(*) FROM voucher"), sql("SELECT COUNT(*) FROM tpd_batch"), sql(f"SELECT COUNT(*) FROM payment_request WHERE id IN ({prU1},{prU2}) AND voucher_created=1"))
HOOK = os.environ.get('TPD_HOOK', '1') == '1'   # the failure-injection hook exists on the cloud test copy only (TPD_WRITER_PROTO=1); the kit's code has none
j = bulk(appr, JEEP, [prU1, prU2], 'USD', COM, fail_at=1) if HOOK else {'status': 409, 'message': 'SKIPPED — no failure-injection hook in this build (the kit); see results_bulk_innodb/myisam.json for the hooked run'}
a51 = (sql("SELECT COUNT(*) FROM voucher"), sql("SELECT COUNT(*) FROM tpd_batch"), sql(f"SELECT COUNT(*) FROM payment_request WHERE id IN ({prU1},{prU2}) AND voucher_created=1"))
if not HOOK: print('  skip [51] failure injection — no hook in this build')
if HOOK: check(51, 'a failure after voucher 1 of 2: refused, NOTHING created (vouchers, batches, flags, counter unchanged); audit voucher_issue_failed; items still eligible', j.get('status') == 409 and b51 == a51 and sql("SELECT V_Num FROM invoice_no") == str(cU) and elig(JEEP)['currencies']['USD']['count'] == 2, (j.get('message'), b51, a51))
sql(f"UPDATE invoice_no SET V_Num = {cU - 1}")
j = bulk(appr, JEEP, [prU1, prU2], 'USD', COM)
check(52, 'counter behind the table: refused BEFORE writing, naming counter and last number; nothing changed', j.get('status') == 409 and 'behind the voucher table' in (j.get('message') or '') and sql("SELECT COUNT(*) FROM voucher") == b51[0], j.get('message'))
sql(f"UPDATE invoice_no SET V_Num = {cU}")

# ============ 54 bulk initiation ============
print('\n== 54 the ONE bank transfer initiated for the batch')
j = acct.post('tour/tour_payment_dashboard/initiate', data={'vnum': vn[0], 'bank_account_id': COM, 'payment_date': TODAY})
check(54, 'a child voucher cannot be initiated on its own (409, names the bulk batch)', j.get('status') == 409 and 'bulk batch' in (j.get('message') or '') and j.get('bulk_id') == B, j)
j = appr.post('tour/tour_payment_dashboard/bulk_initiate', data={'bulk_id': B, 'bank_account_id': COM, 'payment_date': TODAY})
check(54, 'management cannot initiate (403)', j.get('status') == 403, j)
j54 = acct.post('tour/tour_payment_dashboard/bulk_initiate', data={'bulk_id': B, 'bank_account_id': COM, 'payment_date': TODAY, 'bank_ref': 'CB-BULK-%s' % RUN, 'charges': '217.00', 'note': 'bulk test 54'})
d = bdata(B)
shares = sorted(round(float(c['charge_share']), 2) for c in d['children'])
check(54, 'all 12 initiated in one operation: 12 initiation rows, the same date / account / reference, transfer LKR 31,000.00, charges 217.00 allocated to the cent (Σ shares = 217.00)', j54.get('status') == 200 and j54.get('complete') and d['counts']['initiated'] == 12 and abs(d['totals']['transfer_lkr'] - 31000) < 0.005 and abs(sum(shares) - 217.0) < 0.005 and sql(f"SELECT COUNT(DISTINCT CONCAT(payment_date,'|',bank_account_id,'|',bank_ref)) FROM tpd_bank_initiation WHERE batch_ref='{d['batch']['batch_no']}'") == '1', (j54.get('message'), d['counts'], sum(shares)))
check(54, 'shares pro rata to each voucher (largest: 4,100 → %.2f; smallest: 1,800 → %.2f), each Σ(amount+share) = total_debit per voucher' % (max(shares), min(shares)), max(shares) > min(shares) and all(abs(float(sql(f"SELECT total_debit FROM tpd_bank_initiation WHERE vnum={c['vnum']}")) - (float(c['lkr_amount']) + float(c['charge_share']))) < 0.005 for c in d['children']))
check(54, 'the parent records the transfer: payment date, reference, charges 217.00, total debit 31,217.00, initiated by the accountant; status initiated', d['batch']['bank_ref'] == 'CB-BULK-%s' % RUN and abs(float(d['batch']['total_debit']) - 31217) < 0.005 and d['batch']['initiated_by'] == 'tcaccount' and d['batch']['status'] == 'initiated', d['batch'])
j = acct.post('tour/tour_payment_dashboard/bulk_initiate', data={'bulk_id': B, 'bank_account_id': COM, 'payment_date': TODAY, 'bank_ref': 'X'})
check(54, 'initiating again changes nothing (0 initiated now, still complete)', j.get('status') == 200 and j.get('initiated') == [] and bdata(B)['batch']['bank_ref'] == 'CB-BULK-%s' % RUN, j.get('message'))

# ============ 55 bulk Paid ============
print('\n== 55 Paid for the whole transfer')
j = bank.post('tour/tour_payment_dashboard/paid', data={'vnum': vn[3], 'paid_date': TODAY, 'bank_ref': 'x', 'bank_account_id': COM, 'confirm_account': 1})
check(55, 'a child voucher cannot be marked Paid on its own (409)', j.get('status') == 409 and j.get('bulk_id') == B, j)
j = admin.post('report/bank_payment_check/mark_finalized', data={'pr_id': vn[3], 'bank_date': TODAY})
check(55, 'the legacy Mark Finalized is blocked for a bulk child voucher (403)', j.get('status') == 403, j)
j = acct.post('tour/tour_payment_dashboard/bulk_paid', data={'bulk_id': B, 'paid_date': TODAY, 'bank_ref': 'CB-BULK-%s' % RUN, 'bank_account_id': COM, 'confirm_account': 1})
check(55, 'the accountant cannot mark Paid (403)', j.get('status') == 403, j)
lc = sql("SELECT COUNT(*) FROM account_runing")
j55 = bank.post('tour/tour_payment_dashboard/bulk_paid', data={'bulk_id': B, 'paid_date': TODAY, 'bank_ref': 'CB-BULK-%s' % RUN, 'bank_account_id': COM, 'confirm_account': 1})
d = bdata(B)
check(55, 'all 12 marked Paid in one operation; every voucher finalized with the bank date; 12 Paid locks', j55.get('status') == 200 and j55.get('complete') and d['counts']['paid'] == 12 and sql(f"SELECT COUNT(*) FROM voucher WHERE Vnum BETWEEN {vn[0]} AND {vn[-1]} AND finalized=1 AND bank_date='{TODAY}'") == '12' and sql(f"SELECT COUNT(*) FROM tpd_bank_approval WHERE vnum BETWEEN {vn[0]} AND {vn[-1]}") == '12', (j55.get('message'), d['counts']))
CHQ = f"FROM account_runing a JOIN tpd_bank_approval b ON FIND_IN_SET(a.ID, b.charge_entries) WHERE a.advances_pay=24 AND b.vnum BETWEEN {vn[0]} AND {vn[-1]}"
pairs = int(sql("SELECT COUNT(*) " + CHQ) or 0)
check(55, 'bank charges: one balanced Dr Bank Charges / Cr paying account pair per voucher (12 pairs, Σ Dr = Σ Cr = 217.00), posted once', pairs == 24 and abs(float(sql("SELECT SUM(Dr) " + CHQ)) - 217) < 0.005 and abs(float(sql("SELECT SUM(Cr) " + CHQ)) - 217) < 0.005 and sql(f"SELECT COUNT(*) FROM tpd_bank_approval WHERE vnum BETWEEN {vn[0]} AND {vn[-1]} AND charges_posted=1") == '12' and d['counts']['reconciled'] == 12, (pairs, d['counts']))
rc = j55.get('reconciliation') or {}
check(55, 'batch ledger check: Σ credits to the paying account = 31,000.00 + 217.00 = 31,217.00 = the one bank debit; every voucher balanced; parent reconciled', rc.get('ok') and abs(rc.get('bank_credit', 0) - 31217) < 0.005 and d['reconciled'] and d['stage'] == 'paid', rc)
check(55, 'the message says plainly that this is not a bank-statement match', 'not a bank-statement match' in (j55.get('message') or ''))
j = bank.post('tour/tour_payment_dashboard/bulk_paid', data={'bulk_id': B, 'paid_date': TODAY, 'bank_ref': 'CB-BULK-%s' % RUN, 'bank_account_id': COM, 'confirm_account': 1})
check(55, 'Paid again: nothing posted twice (0 marked now, ledger row count unchanged)', j.get('status') == 200 and j.get('paid') == [] and sql("SELECT COUNT(*) FROM account_runing") == sql("SELECT COUNT(*) FROM account_runing"), j.get('message'))
def vnums_on(mid): return set((tx.get('vnum') or tx.get('payment_voucher_no')) for l in desk(req, mid)['lines'] for tx in l['transactions'] if (tx.get('vnum') or tx.get('payment_voucher_no')) in vn)
check(55, 'the desk / tour record: each tour shows its own lunches with their own voucher numbers (6 + 6)', len(vnums_on(MID)) == 6 and len(vnums_on(MID2)) == 6, (sorted(vnums_on(MID)), sorted(vnums_on(MID2))))

# ============ 56 resumable: a USD bulk batch, Paid stopped by a missing rate, then completed; a cancelled child leaves the batch incomplete ============
print('\n== 56 resumable Paid (USD batch) and an incomplete batch')
j = bulk(appr, JEEP, [prU1, prU2], 'USD', COM, note='bulk test 56'); B2 = j.get('batch_id'); vn2 = j.get('vnums') or []
ji = acct.post('tour/tour_payment_dashboard/bulk_initiate', data={'bulk_id': B2, 'bank_account_id': COM, 'payment_date': TODAY, 'bank_ref': 'CB-USD-%s' % RUN, 'charges': '0'})
d2 = bdata(B2)
check(56, 'USD bulk batch (2 vouchers, USD 65.00) initiated at the payment-date rate 327.25 → transfer LKR %s' % d2['totals'].get('transfer_lkr'), j.get('status') == 200 and ji.get('status') == 200 and abs(float(d2['totals']['transfer_lkr']) - round(40 * 327.25, 2) - round(25 * 327.25, 2)) < 0.01, (j.get('message'), ji.get('message'), d2['totals']))
# cancel the second child the legacy way → Paid runs for the first, stops at the second → paid_partial
jc = admin.s.post(admin.base + 'report/voucher_report/update', data={'id': vn2[1], 'supplier': 'Fright Less Jeep Safari Minneriya'}, headers={'X-Requested-With': 'XMLHttpRequest'})
jp = bank.post('tour/tour_payment_dashboard/bulk_paid', data={'bulk_id': B2, 'paid_date': TODAY, 'bank_ref': 'CB-USD-%s' % RUN, 'bank_account_id': COM, 'confirm_account': 1})
d2 = bdata(B2)
check(56, 'one child cancelled by the legacy screen: Paid marks the other and reports INCOMPLETE (207), the batch is "paid_partial" and NOT reconciled — no silent success', jp.get('status') == 207 and d2['counts']['paid'] == 1 and d2['counts']['cancelled'] == 1 and not d2['reconciled'] and d2['stage'] in ('paid_partial', 'paid_with_cancelled'), (jp.get('status'), jp.get('message'), d2['counts'], d2['stage']))
check(56, 'the remittance advice is refused while the batch is incomplete', req.post('tour/tour_payment_dashboard/bulk_remittance_prepare', data={'bulk_id': B2}).get('status') == 409)

# ============ 57 itemised remittance ============
print('\n== 57 one itemised remittance advice for the whole transfer')
for f in glob.glob(SINK + '/*.eml'): os.rename(f, f + '.old')
jr = req.post('tour/tour_payment_dashboard/bulk_remittance_prepare', data={'bulk_id': B})
r = jr.get('remittance') or {}
check(57, 'prepared: one advice listing all 12 vouchers, to the supplier\'s remittance e-mail, subject names vouchers %s–%s and the batch' % (vn[0], vn[-1]), jr.get('status') == 200 and r.get('items') == 12 and ('%s–%s' % (vn[0], vn[-1])) in r.get('subject', '') and r.get('to', '').endswith('@example.invalid') and all(str(v) in r.get('body', '') for v in vn), (jr.get('message'), r.get('subject'), r.get('to')))
page = req.raw(f'tour/tour_payment_dashboard/bulk_remittance/{B}').text
check(57, 'the advice page lists every voucher with tour, guest, service, invoice and amount, the one transfer total 31,000.00 and the bank reference', all(str(v) in page for v in vn) and '31,000.00' in page and 'CB-BULK-%s' % RUN in page and 'GIM-B07' in page)
js = req.post('tour/tour_payment_dashboard/bulk_remittance_send', json_body={'bulk_id': B, 'to': 'gimanhala-test@example.invalid'})
time.sleep(1.5); mails = glob.glob(SINK + '/*.eml')
d = bdata(B)
jsingle = req.post('tour/tour_payment_dashboard/remittance_prepare', data={'vnum': vn[4]})
check(57, 'the per-voucher advice of a bulk child is refused (409, names the batch) — the supplier gets ONE itemised advice', jsingle.get('status') == 409 and jsingle.get('bulk_id') == B, jsingle.get('message'))
check(57, 'sent once (sink received 1 message with the PDF attached); all 12 vouchers marked Notified; a second send is refused', js.get('status') == 200 and len(mails) == 1 and sql(f"SELECT COUNT(*) FROM tpd_remittance WHERE vnum BETWEEN {vn[0]} AND {vn[-1]} AND status='sent'") == '12' and d['remittance']['status'] == 'sent' and req.post('tour/tour_payment_dashboard/bulk_remittance_send', json_body={'bulk_id': B, 'to': 'gimanhala-test@example.invalid'}).get('status') == 409, (js.get('message'), len(mails)))

# ============ 58 legacy screens ============
print('\n== 58 legacy screens with bulk vouchers')
for who, path, must in [(acct, 'report/bank_payment_check/finalized_records?from=%s&to=%s' % (TODAY, TODAY), str(vn[5])), (admin, 'report/voucher_report', 'oucher'), (admin, 'accounts/voucher_audit', 'oucher'), (admin, 'tour/board/tour/4/record', 'Complete record'), (acct, f'tour/tour_payment_dashboard/voucher/{vn[2]}', str(vn[2])), (acct, f'tour/tour_payment_dashboard/batch/{d["children"][2]["batch_id"]}', d['children'][2]['batch_no']), (acct, f'tour/tour_payment_dashboard/bulk/{B}', d['batch']['batch_no']), (appr, 'tour/tour_payment_dashboard/payables?tab=batches', 'Supplier Payables')]:
    rr = who.raw(path); check(58, 'renders: ' + path.split('?')[0], rr.status_code == 200 and must in rr.text, (rr.status_code, must))
r = acct.raw('report/bank_payment_check').text
check(58, 'the legacy Bank Payment Check listed the 12 children individually before Paid (now in Finalized); the batches listing shows the bulk parent with its roll-up and no child as a top-level batch', str(vn[0]) in acct.raw('report/bank_payment_check/finalized_records?from=%s&to=%s' % (TODAY, TODAY)).text and any(b.get('mode') == 'bulk_parent' and b.get('bulk', {}).get('count') == 12 for b in appr.get('tour/tour_payment_dashboard/payables_data')['data'].get('batches', [])) and not any(b.get('parent_id') and not b.get('parent_no') for b in appr.get('tour/tour_payment_dashboard/payables_data')['data'].get('batches', [])))

# ============ 59 separation of duties ============
print('\n== 59 separation of duties — server side')
# the administrator requests a lunch; then tries to approve / batch / initiate / pay it
prA, j = expense(admin, TOUR2, MID2, GIM, 'Admin own lunch (bulk test %s)' % RUN, 1200, date='2026-10-22', inv='GIM-ADM')
check(59, 'the administrator makes a request (allowed)', prA is not None, j)
j = admin.post('tour/tour_payment_dashboard/approve', data={'pr_id': prA})
check(59, 'the administrator cannot approve their OWN request (403, separation); audit separation_blocked', j.get('status') == 403 and j.get('separation') and int(sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action='separation_blocked' AND ref_id='{prA}'")) >= 1, j.get('message'))
j = appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': prA}); check(59, 'another user (management) approves it', j.get('status') == 200, j)
j = admin.post('tour/tour_payment_dashboard/batch_create', data={'sup_acc': GIM, 'pr_ids[]': [prA], 'bank_account_id': COM})
check(59, 'the administrator cannot batch their own request (403)', j.get('status') == 403 and j.get('separation'), j.get('message'))
j = admin.post('tour/tour_payment_dashboard/bulk_create', data={'sup_acc': GIM, 'pr_ids[]': [prA], 'currency': 'LKR', 'bank_account_id': COM, 'submit_token': uuid.uuid4().hex})
check(59, '…nor bulk-batch it (403)', j.get('status') == 403 and j.get('separation'), j.get('message'))
j = appr.post('tour/tour_payment_dashboard/batch_create', data={'sup_acc': GIM, 'pr_ids[]': [prA], 'bank_account_id': COM}); vA = j.get('vnum')
check(59, 'management batches it (voucher %s)' % vA, j.get('status') == 200, j)
j = admin.post('tour/tour_payment_dashboard/initiate', data={'vnum': vA, 'bank_account_id': COM, 'payment_date': TODAY})
check(59, 'the administrator cannot initiate the payment of their own request (403)', j.get('status') == 403 and j.get('separation'), j.get('message'))
j = acct.post('tour/tour_payment_dashboard/initiate', data={'vnum': vA, 'bank_account_id': COM, 'payment_date': TODAY, 'bank_ref': 'CB-SEP-1'}); check(59, 'the accountant initiates it', j.get('status') == 200, j)
j = admin.post('tour/tour_payment_dashboard/paid', data={'vnum': vA, 'paid_date': TODAY, 'bank_ref': 'CB-SEP-1', 'bank_account_id': COM, 'confirm_account': 1})
check(59, 'the administrator cannot finally authorise their own request (403)', j.get('status') == 403 and j.get('separation'), j.get('message'))
j = bank.post('tour/tour_payment_dashboard/paid', data={'vnum': vA, 'paid_date': TODAY, 'bank_ref': 'CB-SEP-1', 'bank_account_id': COM, 'confirm_account': 1}); check(59, 'the bank approver marks it Paid', j.get('status') == 200, j.get('message'))
# initiator ≠ final authoriser: the requester requests, management approves+batches, the ADMIN initiates, the admin may not pay; the bank approver may
prB, _ = expense(req, TOUR2, MID2, GIM, 'Initiator test lunch (bulk test %s)' % RUN, 1300, date='2026-10-22', inv='GIM-INIT')
assert appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': prB}).get('status') == 200
vB = appr.post('tour/tour_payment_dashboard/batch_create', data={'sup_acc': GIM, 'pr_ids[]': [prB], 'bank_account_id': COM}).get('vnum')
j = admin.post('tour/tour_payment_dashboard/initiate', data={'vnum': vB, 'bank_account_id': COM, 'payment_date': TODAY, 'bank_ref': 'CB-SEP-2'}); check(59, 'the administrator initiates a payment requested by someone else (allowed)', j.get('status') == 200, j.get('message'))
j = admin.post('tour/tour_payment_dashboard/paid', data={'vnum': vB, 'paid_date': TODAY, 'bank_ref': 'CB-SEP-2', 'bank_account_id': COM, 'confirm_account': 1})
check(59, 'the user who INITIATED cannot finally authorise it (403, separation)', j.get('status') == 403 and j.get('separation') and 'initiated' in (j.get('message') or ''), j.get('message'))
j = bank.post('tour/tour_payment_dashboard/paid', data={'vnum': vB, 'paid_date': TODAY, 'bank_ref': 'CB-SEP-2', 'bank_account_id': COM, 'confirm_account': 1}); check(59, 'a different bank approver marks it Paid', j.get('status') == 200, j.get('message'))
# bulk: initiator ≠ authoriser too
pr3 = []
for i in range(3):
    p, _ = expense(req, TOUR2, MID2, GIM, 'Bulk sep lunch %d (bulk test %s)' % (i, RUN), 1000 + i, date='2026-10-22', inv='GIM-S%d' % i); assert appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': p}).get('status') == 200; pr3.append(p)
B3 = bulk(appr, GIM, pr3, 'LKR', COM).get('batch_id')
assert admin.post('tour/tour_payment_dashboard/bulk_initiate', data={'bulk_id': B3, 'bank_account_id': COM, 'payment_date': TODAY, 'bank_ref': 'CB-SEP-3', 'charges': '30'}).get('status') == 200
j = admin.post('tour/tour_payment_dashboard/bulk_paid', data={'bulk_id': B3, 'paid_date': TODAY, 'bank_ref': 'CB-SEP-3', 'bank_account_id': COM, 'confirm_account': 1})
check(59, 'bulk: the user who initiated the transfer cannot mark it Paid (403 on the first voucher, 0 paid)', j.get('status') == 403 and j.get('separation') and bdata(B3)['counts']['paid'] == 0, j.get('message'))
j = bank.post('tour/tour_payment_dashboard/bulk_paid', data={'bulk_id': B3, 'paid_date': TODAY, 'bank_ref': 'CB-SEP-3', 'bank_account_id': COM, 'confirm_account': 1}); check(59, 'the bank approver marks the bulk batch Paid (3 vouchers, charges 30.00 allocated)', j.get('status') == 200 and j.get('complete') and j['reconciliation']['ok'], j.get('message'))

# ============ 60 / 61 the request privilege is separate from seeing the dashboard ============
print('\n== 60/61 dashboard visibility ≠ request authority')
dv = appr.get('tour/tour_payment_dashboard/desk', manual_id=MID)
check(60, 'management (dashboard, no request privilege) can READ the desk and sees requester=false, approver=true', dv.get('status') == 200 and dv['data']['perms']['requester'] is False and dv['data']['perms']['approver'] is True and dv['data']['perms']['viewer'] is True, dv.get('data', {}).get('perms'))
j = appr.post('tour/tour_payment_dashboard/expense', data={'tour_id': TOUR, 'service_date': '2026-10-13', 'category': 'Meal', 'supplier_acc': GIM, 'description': 'should be refused', 'currency': 'LKR', 'amount': '10'})
j2 = appr.post('tour/tour_payment_dashboard/request_meta', data={'tour_id': TOUR, 'acc': GIM, 'type': 'lunch'})
t = dv['data']['legacy']['tour_info']
j3 = appr.post('tour/tour_accounts/payment_request', data={'tourId': t['tour_id'], 'manualId': t['manual_id'], 'consignee': '', 'createUser': '', 'bankName': '', 'branchName': '', 'supBankAcc': '', 'totalHotelPay': 10, 'totalHotelAdvance': 0, 'dueAmount': 10, 'requestAmount': 10, 'tot_amt_doller': 10, 'hotelAcc': GIM, 'hotelName': 'x', 'activityId': 0, 'type': 'lunch'})
check(60, 'management cannot add an expense, write request meta, or post the legacy payment request (server refuses all three)', j.get('status') == 403 and j2.get('status') == 403 and j3.get('status') in (403, 302, 401) and 'status' in j3, (j.get('status'), j2.get('status'), j3.get('status'), str(j3)[:80]))
check(60, 'the requester (holds the request privilege) can still request; the accountant (dashboard only) cannot', req.post('tour/tour_payment_dashboard/request_meta', data={'tour_id': TOUR, 'acc': GIM, 'type': 'lunch'}).get('status') in (200, 404, 409) and acct.post('tour/tour_payment_dashboard/expense', data={'tour_id': TOUR, 'service_date': '2026-10-13', 'category': 'Meal', 'supplier_acc': GIM, 'description': 'should be refused 2', 'currency': 'LKR', 'amount': '10'}).get('status') == 403)
mid = sql("SELECT id FROM menu_list WHERE url='tour/tour_payment_dashboard/request' AND del=0 LIMIT 1")
check(61, 'Setup created the separate privilege "Tour payment requests (make / cancel)" (menu %s), granted to System Administrator only until an administrator grants it' % mid, mid != '' and sql(f"SELECT GROUP_CONCAT(group_id ORDER BY group_id) FROM user_privileges WHERE menu_id={mid} AND group_id NOT BETWEEN 907 AND 999") in ('1,906', '1'), sql(f"SELECT GROUP_CONCAT(group_id ORDER BY group_id) FROM user_privileges WHERE menu_id={mid}"))

# ============ 62 a voucher cancelled on the legacy Voucher Cancellation screen (Aflag 3) is dead for the desk ============
print('\n== 62 legacy voucher cancellation (Aflag 3) recognised by the desk')
prC, _ = expense(req, TOUR2, MID2, GIM, 'Lunch cancelled later (bulk test %s)' % RUN, 2100, date='2026-10-22', inv='GIM-CAN')
assert appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': prC}).get('status') == 200
jb = appr.post('tour/tour_payment_dashboard/batch_create', data={'sup_acc': GIM, 'pr_ids[]': [prC], 'bank_account_id': COM}); vC = jb.get('vnum'); bC = jb.get('batch_id')
rc = admin.s.post(admin.base + 'report/voucher_report/update', data={'id': vC, 'supplier': 'GIMANHALA LUNCH VOUCHER'}, headers={'X-Requested-With': 'XMLHttpRequest'})
flags = sql(f"SELECT CONCAT(Aflag,'|',flag,'|',finalized) FROM voucher WHERE Vnum={vC}")
check(62, 'a single-item batch voucher (%s) cancelled on the legacy screen: Aflag 3, flag 1, not finalized' % vC, jb.get('status') == 200 and flags == '3|1|0', (rc.status_code, flags))
ji = acct.post('tour/tour_payment_dashboard/initiate', data={'vnum': vC, 'bank_account_id': COM, 'payment_date': TODAY, 'bank_ref': 'X'})
jp = bank.post('tour/tour_payment_dashboard/paid', data={'vnum': vC, 'paid_date': TODAY, 'bank_ref': 'X', 'bank_account_id': COM, 'confirm_account': 1})
check(62, 'the desk refuses to initiate (409) and to mark Paid (409) a cancelled voucher — the voucher stays unfinalized, no Paid lock, no ledger posting', ji.get('status') == 409 and jp.get('status') == 409 and 'cancelled' in (jp.get('message') or '') and sql(f"SELECT finalized FROM voucher WHERE Vnum={vC}") == '0' and sql(f"SELECT COUNT(*) FROM tpd_bank_approval WHERE vnum={vC}") == '0', (ji.get('message'), jp.get('message')))
bl = next((b for b in appr.get('tour/tour_payment_dashboard/payables_data')['data'].get('batches', []) if b.get('id') == bC), {})
hist = [h['what'] for h in (desk(req, MID2).get('history') or []) if h.get('vnum') == vC]
check(62, 'the batches listing shows the batch as Cancelled; the tour history records "Voucher cancelled (Voucher Cancellation screen)"; the item is NOT offered for a bulk payment again', bl.get('stage') == 'cancelled' and any('Voucher Cancellation screen' in h for h in hist) and not any(x['pr_id'] == prC for x in elig(GIM)['currencies'].get('LKR', {}).get('eligible', [])), (bl.get('stage'), hist))

ok = sum(1 for r in RES if r['ok']); print('\n%d ok, %d failed' % (ok, len(RES) - ok))
json.dump(RES, open(HERE + '/results_bulk.json', 'w'), indent=1)
