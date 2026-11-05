r"""tf-pilot-r1 — tests 70–76 on the TEST COPY: the common starting page (Pilot) for every department, the department panel,
confidential data (bank details masked by role, reports and figures refused and RECORDED for the wrong role, direct links and
exports included), the read-only auditor, the administrator unchanged. Writes results_pilot.json."""
import os, sys, json, subprocess, re
from api import Client, ACC, BASE
import requests
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE); SOCK = S + '/mysql.sock'
RES = []
def check(test, name, ok, detail=''):
    RES.append({'test': test, 'name': name, 'ok': bool(ok), 'detail': str(detail)[:500]}); print(('  ok   ' if ok else '  FAIL ') + f'[{test}] ' + name + ('' if ok else '   ' + str(detail)[:400])); return bool(ok)
def sql(q):
    r = subprocess.run(['mysql', '--socket=' + SOCK, '-uroot', '-N', 'avenir_test', '-e', q], capture_output=True, text=True)
    if r.returncode != 0: raise SystemExit('SQL failed: ' + r.stderr + '\n' + q[:300])
    return r.stdout.strip()
def login_redirect(who):
    u, p = ACC[who]; s = requests.Session(); r = s.get(BASE); m = re.search(r'name="([^"]+)" value="([0-9a-f]{32})"', r.text)
    data = {'username': u, 'password': p}
    if m: data[m.group(1)] = m.group(2)
    j = s.post(BASE + 'login', data=data, headers={'X-Requested-With': 'XMLHttpRequest'}).json(); return j.get('redirect') or j, s
def page(c, path): return c.s.get(c.base + path, headers={'X-Requested-With': 'XMLHttpRequest'} if path.endswith(('_data', 'get')) or '?' in path and 'json' in path else {}, allow_redirects=False)
def denied(c, path, method='GET', **kw):
    f = c.s.post if method == 'POST' else c.s.get
    r = f(c.base + path, headers={'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}, allow_redirects=False, **kw)
    return r.status_code
C = {w: Client(w) for w in ['admin', 'requester', 'approver', 'accountant', 'bank', 'auditor', 'quote']}
den0 = int(sql("SELECT COUNT(*) FROM tpd_audit WHERE action='access_denied'") or 0)

print('\n== 70 the common starting page after login')
import time
for who, want in [('approver', 'dashboard2/tiles'), ('requester', 'dashboard2/tiles'), ('accountant', 'dashboard2/tiles'), ('bank', 'dashboard2/tiles'), ('auditor', 'dashboard2/tiles'), ('quote', 'dashboard2/tiles'), ('admin', 'dashboard')]:
    rd, _ = login_redirect(who)
    if isinstance(rd, dict) and rd.get('status') == 429: time.sleep(6); rd, _ = login_redirect(who)   # the login rate limit of the application (per network)
    check(70, f'{who}: login sends to {want}', rd == want, rd)
for who in ['requester', 'approver', 'accountant', 'bank', 'auditor', 'quote']:
    r = C[who].s.get(C[who].base + 'dashboard2/tiles', allow_redirects=False); t = r.text
    has = sql("SELECT COUNT(*) FROM user_priv_q WHERE user='%s' AND url='dashboard2/tiles'" % ACC[who][0])
    check(70, f'{who}: Pilot opens (200) even without the page\'s own menu (held: {has}) and shows the department panel', r.status_code == 200 and 'id="pilot_panel"' in t and 'Pilot' in t, (r.status_code, 'pilot_panel' in t))
# the panel grants nothing: the requester's page lists none of the areas it does not hold
t = C['requester'].s.get(C['requester'].base + 'dashboard2/tiles').text
cats = re.findall(r'data-cat-name="([^"]+)"', t)
check(70, 'the requester\'s Pilot lists only its own areas (%s) — no Accounts, Accounts Reports, Ground Handling, Settings' % ', '.join(cats), cats and not set(cats) & {'accounts', 'accounts reports', 'ground handling', 'settings'}, cats)
check(70, 'the requester\'s Pilot offers no Operations Allocation / Settings shortcut and no report list', 'driver_allow/tour_transport' not in t.split('id="pilot_panel"')[1].split('Menu Categories')[0] and 'settings/user_account' not in t.split('id="pilot_panel"')[1].split('Menu Categories')[0])

print('\n== 71 the department panel: tasks waiting, by privilege')
def panel(who):
    t = C[who].s.get(C[who].base + 'dashboard2/tiles').text; seg = t.split('id="pilot_panel"')[1].split('Menu Categories')[0]
    return seg, dict((lbl.strip(), int(n)) for n, lbl in re.findall(r'<div class="n"[^>]*>(\d+)</div><div class="l">([^<]+)</div>', seg))
seg, tasks = panel('approver'); want = int(sql("SELECT COUNT(*) FROM payment_request WHERE payment_request=1"))
check(71, 'management sees "Payment requests awaiting approval" = %d (the table says %d) and a Supplier Payables shortcut' % (tasks.get('Payment requests awaiting approval', -1), want), tasks.get('Payment requests awaiting approval') == want and 'tour_payment_dashboard/payables' in seg, tasks)
seg, tasks = panel('accountant'); want = int(sql("SELECT COUNT(*) FROM tpd_batch WHERE status='issued' AND parent_id IS NULL AND (mode IS NULL OR mode<>'bulk_parent')"))
check(71, 'the accountant sees "Payment vouchers awaiting bank initiation" = %d and the Bank Payment Check shortcut; no approval line' % want, tasks.get('Payment vouchers awaiting bank initiation') == want and 'report/bank_payment_check' in seg and 'awaiting approval' not in seg, tasks)
seg, tasks = panel('bank'); want = int(sql("SELECT COUNT(*) FROM tpd_batch WHERE status='initiated' AND parent_id IS NULL"))
check(71, 'the bank approver sees "initiated, awaiting final authorisation (Paid)" = %d; no initiation line' % want, tasks.get('Bank payments initiated, awaiting final authorisation (Paid)') == want and 'awaiting bank initiation' not in seg, tasks)
seg, tasks = panel('requester'); want = int(sql("SELECT COUNT(*) FROM payment_request WHERE payment_request=1 AND payment_request_user='tcpayreq'"))
check(71, 'the requester sees only its own requests awaiting approval = %d (no approval / initiation / Paid lines)' % want, tasks.get('My payment requests awaiting approval') == want and 'Payment requests awaiting approval' not in tasks and 'awaiting bank initiation' not in seg and 'Paid)' not in seg, tasks)
seg, tasks = panel('auditor')
check(71, 'the auditor sees reports and no task of a doer (no approval, initiation, Paid or request line)', 'Your reports' in seg and not any(k in seg for k in ['awaiting approval', 'awaiting bank initiation', 'final authorisation']), tasks)

print('\n== 72 confidential data: bank details masked by role')
sup = sql("SELECT sup_acc FROM tpd_supplier_bank WHERE account_no IS NOT NULL AND account_no<>'' ORDER BY id LIMIT 1"); full = sql(f"SELECT account_no FROM tpd_supplier_bank WHERE sup_acc='{sup}'")
def getbank(who):
    j = C[who].get('master_files/supplier_bank/get', acc=sup); return (j.get('data') or {}).get('account_no'), (j.get('data') or {}).get('account_no_masked'), j.get('status')
a = getbank('accountant'); r_ = getbank('requester'); m = getbank('approver'); au = getbank('auditor')
check(72, 'the accountant (pays) sees the full supplier account number; the requester sees the last four digits only; management and the auditor are not served by this endpoint at all (403)', a[0] == full and a[1] is False and r_[0] != full and r_[0].endswith(full[-4:]) and '•' in r_[0] and m[2] == 403 and au[2] == 403, (a, r_, m, au))
d = C['requester'].get('tour/tour_payment_dashboard/desk', manual_id='AH 2218')['data']
banks = [l.get('supplier_bank', {}).get('account_no') for l in d['lines'] if l.get('supplier_bank') and l['supplier_bank'].get('account_no')]
check(72, 'the requester\'s desk shows every supplier account masked (%d lines)' % len(banks), banks and all('•' in b for b in banks), banks[:3])
pj = C['approver'].get('tour/tour_payment_dashboard/payables_data')['data']
check(72, 'Supplier Payables for management: supplier accounts masked; the Avenir paying accounts offered masked (account_no_masked only)', all('•' in (s['bank'].get('account_no') or '•') for s in pj['awaiting']) and all('account_no' not in a or a.get('account_no') is None for a in pj['bank_accounts']['accounts']), (len(pj['awaiting']), list(pj['bank_accounts']['accounts'][0].keys()) if pj['bank_accounts']['accounts'] else None))

print('\n== 73 confidential pages, figures, exports: refused for the wrong role, recorded')
def row(who, path, method='GET'):
    code = denied(C[who], path, method); return code
tests = [('requester', 'accounts/balance_sheet', 'GET'), ('requester', 'dashboard/get_bank_balances', 'GET'), ('requester', 'accounts/trial_balance', 'GET'), ('quote', 'tour/tour_payment_dashboard/payables_data', 'GET'), ('quote', 'report/bank_payment_check/finalized_records', 'GET'), ('quote', 'master_files/supplier_bank/get?sup_acc=' + sup, 'GET'), ('requester', 'settings/user_account', 'GET'), ('approver', 'report/bank_payment_check', 'GET'), ('accountant', 'tour/tour_accounts/payment_approval_v2', 'GET'), ('auditor', 'master_files/bank_accounts', 'GET'), ('auditor', 'settings/group_privilege/0', 'GET')]
codes = [(w, p, row(w, p, m)) for w, p, m in tests]
check(73, 'every confidential page / data endpoint outside the role is refused (403): ' + ', '.join(f'{w}→{p.split("?")[0]}' for w, p, _ in tests), all(c == 403 for _, _, c in codes), [(w, p, c) for w, p, c in codes if c != 403])
vn = sql("SELECT vnum FROM tpd_batch WHERE vnum IS NOT NULL ORDER BY id DESC LIMIT 1")
ex = [('quote', f'tour/tour_payment_dashboard/voucher/{vn}?pdf=1'), ('quote', f'tour/tour_payment_dashboard/voucher/{vn}'), ('requester', 'report/bank_payment_check/finalized_records?from=2026-01-01&to=2026-12-31')]
exc = [(w, p, C[w].s.get(C[w].base + p, allow_redirects=False).status_code) for w, p in ex]
check(73, 'direct links and exports too: a voucher PDF / page and the finalized-payments export are refused (302 to the 403 page or 403) outside the role', all(c in (302, 403) for _, _, c in exc), exc)
den1 = int(sql("SELECT COUNT(*) FROM tpd_audit WHERE action='access_denied'"))
last = sql("SELECT CONCAT(user,' | ',ref_id,' | ',LEFT(reason,60)) FROM tpd_audit WHERE action='access_denied' ORDER BY id DESC LIMIT 1")
check(73, 'every refusal was recorded in the audit trail (access_denied rows: +%d, e.g. %s)' % (den1 - den0, last), den1 - den0 >= len(tests) + len(ex), (den0, den1))
ok_pages = [('accountant', 'report/bank_payment_check'), ('approver', 'tour/tour_payment_dashboard/payables'), ('bank', 'report/bank_payment_check'), ('auditor', 'report/bank_payment_check/finalized_records'), ('auditor', 'tour/tour_payment_dashboard/payables'), ('requester', 'tour/tour_payment_dashboard')]
okc = [(w, p, C[w].s.get(C[w].base + p, allow_redirects=False).status_code) for w, p in ok_pages]
check(73, 'the permitted pages of each role still open (200) — the legacy Payment Approval page is not tested here: it uses a MySQL 8 collation the test database lacks', all(c == 200 for _, _, c in okc), okc)

print('\n== 74 the auditor is read-only')
pr = sql("SELECT id FROM payment_request WHERE payment_request=4 ORDER BY id DESC LIMIT 1")
before = (sql(f"SELECT CONCAT(Aflag,'|',flag) FROM voucher WHERE Vnum={vn}"), sql(f"SELECT COALESCE(disputed,0) FROM tpd_request_ext WHERE pr_id={pr}"))
c1 = denied(C['auditor'], 'tour/tour_payment_dashboard/dispute', 'POST', data={'pr_id': pr, 'disputed': 1, 'reason': 'x'})
c2 = denied(C['auditor'], 'report/voucher_report/update', 'POST', data={'id': vn, 'supplier': 'x'})
c3 = denied(C['auditor'], f'tour/tour_payment_dashboard/payables_data', 'GET')
c4 = denied(C['auditor'], 'settings/group_privilege/add/910/1', 'GET')
check(74, 'the auditor may read Supplier Payables (200) but every write is refused: POST dispute %s, POST legacy voucher cancel %s, GET privilege add %s' % (c1, c2, c4), c3 == 200 and c1 == 403 and c2 == 403 and c4 == 403, (c1, c2, c3, c4))
check(74, 'the refusals name the read-only profile in the audit trail', int(sql("SELECT COUNT(*) FROM tpd_audit WHERE action='access_denied' AND user='tcauditor' AND reason LIKE 'read-only profile%'")) >= 3)
after = (sql(f"SELECT CONCAT(Aflag,'|',flag) FROM voucher WHERE Vnum={vn}"), sql(f"SELECT COALESCE(disputed,0) FROM tpd_request_ext WHERE pr_id={pr}"))
check(74, 'nothing changed: the voucher flags and the dispute flag are exactly as before the attempts', before == after, (before, after))

print('\n== 75 the administrator keeps every permission')
adm = [(p, C['admin'].s.get(C['admin'].base + p, allow_redirects=False).status_code) for p in ['dashboard', 'dashboard2/tiles', 'accounts/balance_sheet', 'settings/user_account', 'report/bank_payment_check', 'tour/tour_payment_dashboard/payables']]
check(75, 'administrator: dashboard, Pilot, Balance Sheet, User Accounts, Bank Payment Check, Supplier Payables all 200', all(c == 200 for _, c in adm), adm)
a = getbank('admin'); check(75, 'administrator sees full bank details', a[0] == full, a)
check(75, 'the administrator\'s Pilot panel shows the full report list and all task lines', 'Your reports' in panel('admin')[0] and len(panel('admin')[1]) >= 5, panel('admin')[1])

print('\n== 76 the r4/r5 payment suite endpoints untouched for their roles')
j = C['requester'].post('tour/tour_payment_dashboard/request_meta', data={'tour_id': '2325', 'acc': '6-9-11-11590', 'type': 'lunch'})
check(76, 'the requester can still write request meta (%s); management cannot (403)' % j.get('status'), j.get('status') in (200, 404, 409) and C['approver'].post('tour/tour_payment_dashboard/request_meta', data={'tour_id': '2325', 'acc': '6-9-11-11590', 'type': 'lunch'}).get('status') == 403)
print('\n== 77 the audit trail is readable by the administrator, the Accounts manager and the auditor — and by nobody else')
ja = C['admin'].get('tour/tour_payment_dashboard/audit_log', action='access_denied', limit=5); jau = C['auditor'].get('tour/tour_payment_dashboard/audit_log', action='access_denied', limit=5)
check(77, 'administrator and auditor read the access_denied entries (newest first, with user, route and reason); the requester, management and the bank approver are refused', ja.get('status') == 200 and ja.get('count') >= 1 and all(r['action'] == 'access_denied' and r['user'] and r['ref_id'] for r in ja['rows']) and jau.get('status') == 200 and all(C[w].get('tour/tour_payment_dashboard/audit_log').get('status') == 403 for w in ['requester', 'approver', 'bank']), (ja.get('status'), ja.get('count'), jau.get('status')))
check(77, 'a refused desk ACTION is recorded too (the requester tried to approve): action access_denied, ref_type action, reason "privilege: …"', C['requester'].post('tour/tour_payment_dashboard/approve', data={'pr_id': 0}).get('status') == 403 and int(sql("SELECT COUNT(*) FROM tpd_audit WHERE action='access_denied' AND ref_type='action' AND user='tcpayreq' AND ref_id='tour/tour_payment_dashboard/approve'")) >= 1)
ok = sum(1 for r in RES if r['ok']); print('\n%d ok, %d failed' % (ok, len(RES) - ok))
json.dump(RES, open(HERE + '/results_pilot.json', 'w'), indent=1)
