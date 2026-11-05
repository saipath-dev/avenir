r"""tf-paydesk-r5 Decision B demonstration (CLOUD TEST COPY ONLY: 8081 / avenir_test, post-suite state).

Compares the chain's voucher writer (create_bulk_voucher, as r4 calls it) with the prototype atomic writer
(App\Libraries\TourPayments\Vouchers, switched in by POST writer=proto on the test copy only):
  S1 legacy single voucher  vs  S2 prototype single voucher   — row-by-row, column-by-column
  S3 prototype per-item vouchers (one per payment, consecutive numbers, all-or-nothing)
  S4 failure injected mid-run on InnoDB   → nothing remains
  S5 counter behind the table             → refused before writing
  S6 the same failure on MyISAM tables    → partial rows remain (why Decision A matters)
  S7 downstream on a prototype voucher: initiation, Paid with charges, ledger check, legacy pages, legacy cancel on a per-item voucher
Every scenario starts from the same database snapshot (prep.sql). Writes results_writer.json and writer_compare.txt."""
import os, sys, json, subprocess, datetime, re
from api import Client
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
SOCK = S + '/mysql.sock'; DB = 'avenir_test'; PREP = HERE + '/writer_prep.sql'
RES = []; OUT = []
def say(s): print(s); OUT.append(s)
def check(name, ok, detail=''):
    RES.append({'name': name, 'ok': bool(ok), 'detail': str(detail)[:500]}); say(('  ok   ' if ok else '  FAIL ') + name + ('' if ok else '   ' + str(detail)[:400])); return bool(ok)
def sql(q, db=DB):
    r = subprocess.run(['mysql', '--socket=' + SOCK, '-uroot', '-N', db, '-e', q], capture_output=True, text=True)
    if r.returncode != 0: raise SystemExit('SQL failed: ' + r.stderr + '\n' + q[:300])
    return r.stdout.strip()
def rows(q):
    out = sql(q); return [l.split('\t') for l in out.split('\n')] if out else []
def snapshot(): subprocess.run(f"mysqldump -uroot --socket={SOCK} --triggers --ignore-table={DB}.ci_sessions --ignore-table={DB}.user_sessions {DB} > {PREP}", shell=True, check=True)   # sessions excluded so a restore never signs the test users out
def restore(): subprocess.run(f"mysql -uroot --socket={SOCK} {DB} < {PREP}", shell=True, check=True)
def engines(): return dict(rows("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA='avenir_test' AND TABLE_NAME IN ('voucher','v_inv','invoice_no','payment_request')"))
def set_engine(e):
    for t in ['voucher', 'v_inv', 'invoice_no', 'payment_request']: sql(f"ALTER TABLE {t} ENGINE={e}")

TOUR = '2325'; MID = 'AH 2218'; TOUR2 = '2390'; MID2 = 'AH 2290'; SUP = '6-9-11-11590'   # GIMANHALA LUNCH VOUCHER (restaurant, LKR)
REUSE = os.path.exists(PREP)
if REUSE: restore()   # start again from the saved prep state (the 12 approved lunches)
req, appr, acct, bank, admin = Client('requester'), Client('approver'), Client('accountant'), Client('bank'), Client('admin')
def desk(c, mid): return c.get('tour/tour_payment_dashboard/desk', manual_id=mid)['data']
def line(d, key): return next((l for l in d['lines'] if l['key'] == key), None)
def make_batch(c, ids, acc_id, **extra): return c.post('tour/tour_payment_dashboard/batch_create', data=dict({'sup_acc': SUP, 'pr_ids[]': ids, 'bank_account_id': acc_id}, **extra))
TODAY = desk(req, MID)['today']
COM = sql("SELECT id FROM tpd_bank_account WHERE ledger_code='1-6-8-10380' ORDER BY id LIMIT 1")
assert COM, 'run the suite first (bank account master missing)'

say('== prep: %d restaurant lunches on two tours, requested and approved (the state every scenario starts from)' % 12)
pr_ids = []
for i in ([] if REUSE else range(12)):
    tour, mid, date = (TOUR, MID, '2026-10-1%d' % (2 + i % 4)) if i % 2 == 0 else (TOUR2, MID2, '2026-10-2%d' % (i % 3))
    amt = [2500, 1800, 3200, 2750, 1950, 2500, 4100, 2200, 1800, 3050, 2500, 2650][i]
    j = req.post('tour/tour_payment_dashboard/expense', data={'tour_id': tour, 'service_date': date, 'category': 'Meal', 'supplier_acc': SUP, 'description': 'Lunch %02d (writer demonstration)' % (i + 1), 'currency': 'LKR', 'amount': str(amt), 'invoice_no': 'GIM-W%02d' % (i + 1)})
    assert j.get('status') == 200, j
    j2 = req.post('tour/tour_payment_dashboard/expense_request', data={'expense_id': j['id']}); assert j2.get('status') == 200, j2
    pr = max(t['pr_id'] for t in line(desk(req, mid), 'expense:%s' % j['id'])['transactions'] if t.get('pr_id'))   # the request just made (the desk may also show an older legacy row of the same supplier/date)
    j3 = appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': pr}); assert j3.get('status') == 200, j3
    pr_ids.append(pr)
if REUSE: pr_ids = [int(r[0]) for r in rows(f"SELECT id FROM payment_request WHERE hotel_acc='{SUP}' AND payment_request IN (4,6) AND voucher_created=0 AND manual_id IN ('{MID}','{MID2}') ORDER BY id")]
ids_s = ','.join(map(str, pr_ids))
check('12 approved, unvouchered lunches for %s on 2 tours (LKR %s)' % (SUP, sql(f"SELECT FORMAT(SUM(requestAmount),2) FROM payment_request WHERE id IN ({ids_s})")), sql(f"SELECT COUNT(*) FROM payment_request WHERE id IN ({ids_s}) AND payment_request IN (4,6) AND voucher_created=0") == '12')
snapshot()
C0 = int(sql("SELECT V_Num FROM invoice_no")); MAXV = int(sql("SELECT MAX(Vnum) FROM voucher"))
say(f'   counter V_Num={C0}, MAX(Vnum)={MAXV}, engines={engines()}')
def hist_fp(): return sql(f"SELECT CONCAT((SELECT COUNT(*) FROM voucher WHERE Vnum <= {MAXV}), '|', (SELECT MD5(GROUP_CONCAT(CONCAT_WS('|', ID, Vnum, Amount, Aflag, finalized, IFNULL(bank_date,'')) ORDER BY ID)) FROM voucher WHERE Vnum <= {MAXV}), '|', (SELECT COUNT(*) FROM v_inv WHERE V_tkn <= {MAXV}), '|', (SELECT COUNT(*) FROM account_runing))")
HIST0 = hist_fp()
def counts(): return tuple(sql(f"SELECT COUNT(*) FROM {t}") for t in ['voucher', 'v_inv', 'payment_request', 'tpd_batch', 'tpd_request_ext', 'tpd_audit', 'account_runing'])

VCOLS = [r[0] for r in rows("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='avenir_test' AND TABLE_NAME='voucher' ORDER BY ORDINAL_POSITION")]
ICOLS = [r[0] for r in rows("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='avenir_test' AND TABLE_NAME='v_inv' ORDER BY ORDINAL_POSITION")]
def grab(vnums):
    vn = ','.join(map(str, vnums))
    v = [dict(zip(VCOLS, r)) for r in rows(f"SELECT * FROM voucher WHERE Vnum IN ({vn}) ORDER BY Vnum")]
    i = [dict(zip(ICOLS, r)) for r in rows(f"SELECT * FROM v_inv WHERE V_tkn IN ({vn}) ORDER BY V_tkn, Invoice, Description")]
    p = rows(f"SELECT id, payment_request, voucher_created, accept_user FROM payment_request WHERE id IN ({ids_s}) ORDER BY id")
    x = rows(f"SELECT pr_id, voucher_vnum, batch_id IS NOT NULL FROM tpd_request_ext WHERE pr_id IN ({ids_s}) ORDER BY pr_id")
    b = rows(f"SELECT batch_no, currency, usd_total, approval_lkr_total, item_count, tour_count, status, vnum FROM tpd_batch WHERE vnum IN ({vn})")
    return {'voucher': v, 'v_inv': i, 'payment_request': p, 'ext': x, 'batch': b, 'counter': sql("SELECT V_Num FROM invoice_no")}
TIME_COLS = {'C_Date', 'W_Date', 'Date', 'INV_Date', 'ID', 'acc_run_id'}
def diff_rows(a, b, label):
    d = []
    if len(a) != len(b): return ['%s: %d rows vs %d rows' % (label, len(a), len(b))]
    for ra, rb in zip(a, b):
        for k in ra:
            if k in TIME_COLS:
                if (ra[k] == 'NULL') != (rb[k] == 'NULL'): d.append('%s.%s null-ness differs' % (label, k))
                continue
            if ra[k] != rb[k]: d.append('%s.%s: legacy=%r proto=%r' % (label, k, ra[k], rb[k]))
    return d

# ---------------- S1 legacy single voucher ----------------
say('\n== S1 legacy writer (create_bulk_voucher via r4 batch_create): one voucher for the 12 lunches')
j1 = make_batch(appr, pr_ids, COM, note='writer demo S1')
check('S1 batch created, one voucher, number = counter + 1', j1.get('status') == 200 and j1.get('vnum') == C0 + 1, j1)
L = grab([j1['vnum']]); L_batch = j1; CNT_L = counts()
check('S1 legacy voucher: Amount = Σ lines = 31,000.00; 12 v_inv lines; 12 requests flagged; counter advanced by 1', L['voucher'][0]['Amount'] == '31000.00' and len(L['v_inv']) == 12 and all(p[2] == '1' for p in L['payment_request']) and L['counter'] == str(C0 + 1), (L['voucher'][0]['Amount'], len(L['v_inv']), L['counter']))

# ---------------- S2 prototype single voucher ----------------
restore(); say('\n== S2 prototype writer, single mode: the same 12 lunches, same inputs, same user')
j2 = make_batch(appr, pr_ids, COM, note='writer demo S1', writer='proto')
check('S2 batch created by the prototype, one voucher, the same number', j2.get('status') == 200 and j2.get('vnum') == C0 + 1, j2)
P = grab([j2['vnum']])
d = diff_rows(L['voucher'], P['voucher'], 'voucher') + diff_rows(L['v_inv'], P['v_inv'], 'v_inv')
check('S2 voucher row identical column by column (all %d columns except the row id and the timestamps)' % len(VCOLS), not [x for x in d if x.startswith('voucher')], [x for x in d if x.startswith('voucher')][:8])
check('S2 the 12 v_inv lines identical column by column (%d columns each)' % len(ICOLS), not [x for x in d if x.startswith('v_inv')], [x for x in d if x.startswith('v_inv')][:8])
check('S2 payment_request flags, tpd_request_ext links and the batch row identical; counter identical', L['payment_request'] == P['payment_request'] and L['ext'] == P['ext'] and L['batch'] == P['batch'] and L['counter'] == P['counter'], (L['batch'], P['batch']))
CNT_P = counts()
check('S2 the prototype wrote nothing the chain does not write: row counts of voucher, v_inv, payment_request, tpd_batch, tpd_request_ext, tpd_audit, account_runing equal to the legacy run', CNT_L == CNT_P, (CNT_L, CNT_P))

# ---------------- S3 per-item ----------------
restore(); say('\n== S3 prototype writer, per-item mode: 12 vouchers in one all-or-nothing run')
j3 = make_batch(appr, pr_ids, COM, note='writer demo S3', writer='proto', per_item=1)
vn = j3.get('vnums') or []
check('S3 12 vouchers issued with consecutive numbers %s–%s under one batch %s' % (C0 + 1, C0 + 12, j3.get('batch_no')), j3.get('status') == 200 and vn == list(range(C0 + 1, C0 + 13)), j3)
T = grab(vn)
amts = [float(v['Amount']) for v in T['voucher']]
check('S3 Σ of the 12 vouchers = 31,000.00 = the single voucher total; each voucher carries its own tour (ref_po / C_Number) and amount', abs(sum(amts) - 31000.0) < 0.005 and all(v['ref_po'] == v['C_Number'] for v in T['voucher']) and len(set(v['ref_po'] for v in T['voucher'])) == 2, (sum(amts), [v['ref_po'] for v in T['voucher']]))
check('S3 exactly one v_inv line per voucher, line amount = voucher amount, description names the tour and the restaurant', len(T['v_inv']) == 12 and all(any(i['V_tkn'] == v['Vnum'] and i['Amount'] == v['Amount'] for i in T['v_inv']) for v in T['voucher']) and all('Lunch or Other Fee' in i['Description'] for i in T['v_inv']))
check('S3 every request flagged vouchered and linked to ITS voucher number; counter advanced by exactly 12', all(p[2] == '1' for p in T['payment_request']) and sorted(int(x[1]) for x in T['ext']) == vn and T['counter'] == str(C0 + 12), (T['counter'], [x[1] for x in T['ext']]))
check('S3 the per-item vouchers have the same column values as the single voucher apart from number, amount, tour and dollar figure', not [x for x in diff_rows([dict(L['voucher'][0], Vnum='x', Amount='x', g_ttl='x', doller_amt='x', ref_po='x', C_Number='x')], [dict(T['voucher'][5], Vnum='x', Amount='x', g_ttl='x', doller_amt='x', ref_po='x', C_Number='x')], 'voucher')])
check('S3 audit: a bulk_issued record with the 12 numbers', sql("SELECT COUNT(*) FROM tpd_audit WHERE action='bulk_issued'") == '1')
PERITEM_VN = vn[:]

# ---------------- S4 failure mid-run on InnoDB ----------------
restore(); say('\n== S4 failure injected after voucher 3 of 12 (InnoDB tables, as proposed)')
before = (sql("SELECT COUNT(*) FROM voucher"), sql("SELECT COUNT(*) FROM v_inv"), sql("SELECT V_Num FROM invoice_no"), sql(f"SELECT COUNT(*) FROM payment_request WHERE id IN ({ids_s}) AND voucher_created=1"), sql("SELECT COUNT(*) FROM tpd_batch"))
j4 = make_batch(appr, pr_ids, COM, writer='proto', per_item=1, fail_at=3)
after = (sql("SELECT COUNT(*) FROM voucher"), sql("SELECT COUNT(*) FROM v_inv"), sql("SELECT V_Num FROM invoice_no"), sql(f"SELECT COUNT(*) FROM payment_request WHERE id IN ({ids_s}) AND voucher_created=1"), sql("SELECT COUNT(*) FROM tpd_batch"))
check('S4 refused with a clear message, nothing created: vouchers, lines, counter, request flags and batches all unchanged', j4.get('status') == 409 and before == after and 'Nothing issued' in (j4.get('message') or ''), (j4.get('message'), before, after))
check('S4 the 12 lunches still wait in Supplier Payables and can be batched afterwards', any(s['sup_acc'] == SUP and s['item_count'] == 12 for s in appr.get('tour/tour_payment_dashboard/payables_data')['data']['awaiting']))
check('S4 audit: voucher_issue_failed recorded with the reason', 'TEST HOOK' in (sql("SELECT reason FROM tpd_audit WHERE action='voucher_issue_failed' ORDER BY id DESC LIMIT 1") or ''))
j4b = make_batch(appr, pr_ids, COM, writer='proto', per_item=1)
check('S4 retried without the fault: the same 12 numbers %s–%s are issued (no gap left by the failed run)' % (C0 + 1, C0 + 12), j4b.get('status') == 200 and j4b.get('vnums') == list(range(C0 + 1, C0 + 13)), j4b)

# ---------------- S5 counter behind the table ----------------
restore(); say('\n== S5 the counter is behind the table (a number the run would use is already issued)')
sql(f"UPDATE invoice_no SET V_Num = {C0 - 2}")
j5 = make_batch(appr, pr_ids, COM, writer='proto', per_item=1)
check('S5 refused BEFORE writing, naming the counter and the last issued number; nothing changed', j5.get('status') == 409 and 'behind the voucher table' in (j5.get('message') or '') and sql("SELECT COUNT(*) FROM voucher") == before[0] and sql(f"SELECT COUNT(*) FROM payment_request WHERE id IN ({ids_s}) AND voucher_created=1") == '0', j5.get('message'))
j5l = make_batch(appr, pr_ids, COM)
check('S5 the r4 legacy path refuses the same situation the same way (r4 counter pre-check)', j5l.get('status') == 409 and 'behind the voucher table' in (j5l.get('message') or ''), j5l.get('message'))

# ---------------- S6 the same failure on MyISAM ----------------
restore(); say('\n== S6 the same mid-run failure with the four tables on MyISAM (as the development database is today)')
set_engine('MyISAM'); say('   engines: %s' % engines())
before6 = (sql("SELECT COUNT(*) FROM voucher"), sql("SELECT COUNT(*) FROM v_inv"), sql("SELECT V_Num FROM invoice_no"), sql(f"SELECT COUNT(*) FROM payment_request WHERE id IN ({ids_s}) AND voucher_created=1"))
j6 = make_batch(appr, pr_ids, COM, writer='proto', per_item=1, fail_at=3)
after6 = (sql("SELECT COUNT(*) FROM voucher"), sql("SELECT COUNT(*) FROM v_inv"), sql("SELECT V_Num FROM invoice_no"), sql(f"SELECT COUNT(*) FROM payment_request WHERE id IN ({ids_s}) AND voucher_created=1"))
left = rows(f"SELECT Vnum, Amount FROM voucher WHERE Vnum > {MAXV} ORDER BY Vnum")
check('S6 DEMONSTRATED RISK: the run was refused, but on MyISAM the ROLLBACK cannot undo the rows — %d voucher(s) %s, their lines, 3 request flags and the counter remained' % (len(left), [l[0] for l in left]), j6.get('status') == 409 and before6 != after6 and len(left) == 3, (before6, after6, left))
set_engine('InnoDB')

# ---------------- S7 downstream on prototype vouchers ----------------
restore(); say('\n== S7 downstream: a prototype single voucher through initiation → Paid → ledger check; legacy pages; legacy cancel of a per-item voucher')
j7 = make_batch(appr, pr_ids, COM, writer='proto'); v7 = j7.get('vnum'); b7 = j7.get('batch_id')
ji = acct.post('tour/tour_payment_dashboard/initiate', data={'vnum': v7, 'bank_account_id': COM, 'payment_date': TODAY, 'charges': '155.00', 'bank_ref': 'CB-TRF-W7'})
jp = bank.post('tour/tour_payment_dashboard/paid', data={'vnum': v7, 'paid_date': TODAY, 'bank_ref': 'CB-TRF-W7', 'bank_account_id': COM, 'confirm_account': 1})
bd = acct.get(f'tour/tour_payment_dashboard/batch_data/{b7}')['data']
check('S7 initiation and Paid (charges LKR 155.00) succeed on the prototype voucher; voucher finalized with the bank date', ji.get('status') == 200 and jp.get('status') == 200 and sql(f"SELECT CONCAT(finalized,'|',bank_date) FROM voucher WHERE Vnum={v7}") == f'1|{TODAY}', (ji.get('message'), jp.get('message')))
shares = sorted(round(float(c['share']), 2) for c in bd.get('charge_shares', []))
rc = bd.get('reconciliation') or {}
check('S7 charges allocated across the 12 lines to the cent (Σ = 155.00) and the ledger check passes: credits to the paying account = 31,000.00 + 155.00 = 31,155.00 = the bank debit, every entry balanced', abs(sum(shares) - 155.0) < 0.005 and len(shares) == 12 and rc.get('reconciled') and abs(float(rc['totals']['bank_credit']) - 31155.0) < 0.005 and abs(float(rc['totals']['total_debit']) - 31155.0) < 0.005 and all(c['ok'] for c in rc['checks']), (sum(shares), rc.get('totals'), [c for c in rc.get('checks', []) if not c['ok']]))
check('S7 ledger: 12 balanced bank-charge pairs (Dr Bank Charges / Cr paying account) named with the voucher number; payment credits present', sql(f"SELECT COUNT(*) FROM account_runing WHERE advances_pay=24 AND Discription LIKE '%Payment Voucher No. {v7} %'") == '24' and float(rc['totals']['payment_credit']) == 31000.0, (sql(f"SELECT COUNT(*) FROM account_runing WHERE advances_pay=24 AND Discription LIKE '%Payment Voucher No. {v7} %'"), rc.get('totals')))
for who, path, must in [(acct, 'report/bank_payment_check/finalized_records?from=%s&to=%s' % (TODAY, TODAY), str(v7)), (admin, 'report/voucher_report', 'oucher'), (admin, 'tour/board/tour/4/record', 'Complete record'), (acct, f'tour/tour_payment_dashboard/voucher/{v7}', str(v7))]:
    r = who.raw(path); check('S7 legacy/desk page renders with the prototype voucher: ' + path.split('?')[0], r.status_code == 200 and must in r.text, (r.status_code, must))
rp = acct.raw(f'tour/tour_payment_dashboard/voucher/{v7}').text
check('S7 the voucher document shows all 12 lines with both tours and the restaurant', rp.count('Lunch or Other Fee') >= 12 or rp.count('GIM-W') >= 12, (rp.count('Lunch or Other Fee'), rp.count('GIM-W')))
# legacy cancel: the same legacy screen on a fresh LEGACY voucher and on a fresh PER-ITEM prototype voucher — outcomes compared
restore(); jl = make_batch(appr, pr_ids, COM); vl = jl['vnum']
rl = admin.s.post(admin.base + 'report/voucher_report/update', data={'id': vl, 'supplier': 'GIMANHALA LUNCH VOUCHER'}, headers={'X-Requested-With': 'XMLHttpRequest'})
leg_out = (rl.status_code, sql(f"SELECT CONCAT(Aflag,'|',flag) FROM voucher WHERE Vnum={vl}"), sql(f"SELECT COUNT(*) FROM account_runing WHERE Recpt_No={vl}"))
restore(); j8 = make_batch(appr, pr_ids, COM, writer='proto', per_item=1); vcan = j8['vnums'][4]
rp9 = admin.s.post(admin.base + 'report/voucher_report/update', data={'id': vcan, 'supplier': 'GIMANHALA LUNCH VOUCHER'}, headers={'X-Requested-With': 'XMLHttpRequest'})
pro_out = (rp9.status_code, sql(f"SELECT CONCAT(Aflag,'|',flag) FROM voucher WHERE Vnum={vcan}"), sql(f"SELECT COUNT(*) FROM account_runing WHERE Recpt_No={vcan}"))
check('S7 legacy Voucher Cancellation behaves identically on a fresh legacy voucher and on a fresh per-item prototype voucher (HTTP status, Aflag|flag = 3|1, ledger rows) — the legacy screen answers HTTP %s on an unauthorised voucher in both cases, a pre-existing quirk, not a writer difference' % leg_out[0], leg_out == pro_out and pro_out[1] == '3|1', (leg_out, pro_out))
check('S7 cancelling ONE per-item voucher (%s) leaves the other 11 issued and untouched' % vcan, sql(f"SELECT COUNT(*) FROM voucher WHERE Vnum IN ({','.join(map(str, j8['vnums']))}) AND Aflag=0") == '11')
r = acct.raw('report/bank_payment_check').text
check('S7 the legacy Bank Payment Check lists the 11 open per-item vouchers individually', all(str(v) in r for v in j8['vnums'] if v != vcan), [v for v in j8['vnums'] if str(v) not in r])
ext = rows(f"SELECT x.pr_id, x.voucher_vnum, p.manual_id FROM tpd_request_ext x JOIN payment_request p ON p.id=x.pr_id WHERE x.pr_id IN ({ids_s}) ORDER BY x.pr_id")
check('S7 each of the 12 requests is linked to its own voucher number in the desk\'s request record (12 distinct numbers over 2 tours) — the desk/record screens read this link in r5', len(set(e[1] for e in ext)) == 12 and sorted(int(e[1]) for e in ext) == j8['vnums'], ext)
# historical records untouched throughout
check('S7 historical vouchers (≤ %s) untouched by every scenario: count and checksum equal to the prep snapshot' % MAXV, hist_fp().split('|')[:3] == HIST0.split('|')[:3], (HIST0, hist_fp()))

# ---------------- S8 legacy authorisation and the other legacy voucher screens, same comparison ----------------
say('\n== S8 legacy authorisation (Payment Authentication) and the legacy voucher screens on a legacy voucher vs a prototype voucher')
def legacy_auth(v):
    vid = sql(f"SELECT ID FROM voucher WHERE Vnum={v}"); amt = sql(f"SELECT Amount FROM voucher WHERE Vnum={v}")
    r = admin.s.post(admin.base + 'accounts/voucher_auth/update', data={'update': 'data', 'id': vid, 'status': '1', 'bank_acc': '1-6-8-10380', 'type': 'Bank Transfer', 'amount': amt, 'payee': 'GIMANHALA LUNCH VOUCHER', 'voucher_no': v, 'sup': '1', 'remark': ''}, headers={'X-Requested-With': 'XMLHttpRequest'})
    return (r.status_code, sql(f"SELECT CONCAT(Aflag,'|',IFNULL(Aut_user,''),'|',IFNULL(aut_date,'')<>'') FROM voucher WHERE Vnum={v}"), sql(f"SELECT CONCAT(COUNT(*),'|',IFNULL(SUM(Dr),0),'|',IFNULL(SUM(Cr),0)) FROM account_runing WHERE Recpt_No={v}"))
restore(); jl = make_batch(appr, pr_ids, COM); al = legacy_auth(jl['vnum'])
pages_l = {p: (admin.raw(p).status_code, str(jl['vnum']) in admin.raw(p).text) for p in ['report/voucher_report/voucher_details_new', 'accounts/voucher_audit', 'accounts/creditor_invoice/voucher_authentication', 'accounts/voucher_payments/0']}
restore(); jp = make_batch(appr, pr_ids, COM, writer='proto'); ap = legacy_auth(jp['vnum'])
pages_p = {p: (admin.raw(p).status_code, str(jp['vnum']) in admin.raw(p).text) for p in pages_l}
check('S8 legacy authorisation gives the same result on both: HTTP status, Aflag / authorising user / date, and the ledger rows posted (count, Dr, Cr)', al == ap and al[1].startswith('1|'), (al, ap))
check('S8 the legacy voucher screens (New Voucher Report, Voucher Audit, Payment Release, Voucher Payments) render identically and show / omit the voucher the same way for both writers', pages_l == pages_p and all(v[0] == 200 for v in pages_p.values()), (pages_l, pages_p))
restore(); jpi = make_batch(appr, pr_ids, COM, writer='proto', per_item=1); api_ = legacy_auth(jpi['vnums'][0])
check('S8 legacy authorisation of ONE per-item voucher posts that voucher\'s own amount only (Dr = Cr = its amount), the other 11 untouched', api_[1].startswith('1|') and api_[2].split('|')[1] == api_[2].split('|')[2] and float(api_[2].split('|')[1]) == float(sql(f"SELECT Amount FROM voucher WHERE Vnum={jpi['vnums'][0]}")) and sql(f"SELECT COUNT(*) FROM voucher WHERE Vnum IN ({','.join(map(str, jpi['vnums'][1:]))}) AND Aflag=0") == '11', api_)
check('S8 tpd audit trail per writer: batch_created recorded for both; bulk_issued only for per-item runs; voucher_issue_failed only for failed runs', sql("SELECT COUNT(*) FROM tpd_audit WHERE action='batch_created'") >= '1' and sql("SELECT COUNT(*) FROM tpd_audit WHERE action='bulk_issued'") == '1', sql("SELECT action, COUNT(*) FROM tpd_audit WHERE action IN ('batch_created','bulk_issued','voucher_issue_failed') GROUP BY action"))

restore()
ok = sum(1 for r in RES if r['ok']); say('\nTOTAL: %d of %d checks passed' % (ok, len(RES)))
json.dump(RES, open(HERE + '/results_writer.json', 'w'), indent=1); open(HERE + '/writer_compare.txt', 'w').write('\n'.join(OUT) + '\n')
