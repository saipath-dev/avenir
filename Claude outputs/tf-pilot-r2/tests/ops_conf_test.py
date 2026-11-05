r"""tf-pilot-r2 — tests 80–87 on the TEST COPY: the Driver Allocation page and the Crew replacement entry fixed, allocation
reserved to Operations (and the administrator, and the existing allocating groups), the supplier master's bank details masked and
locked for Hotel Reservations on every route, the dashboard JSON masked, a request's bank details taken from the master.
Writes results_ops_conf.json."""
import os, sys, json, subprocess, re
from api import Client
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE); SOCK = S + '/mysql.sock'
RES = []
def check(test, name, ok, detail=''):
    RES.append({'test': test, 'name': name, 'ok': bool(ok), 'detail': str(detail)[:500]}); print(('  ok   ' if ok else '  FAIL ') + f'[{test}] ' + name + ('' if ok else '   ' + str(detail)[:400])); return bool(ok)
def sql(q):
    r = subprocess.run(['mysql', '--socket=' + SOCK, '-uroot', '-N', 'avenir_test', '-e', q], capture_output=True, text=True)
    if r.returncode != 0: raise SystemExit('SQL failed: ' + r.stderr + '\n' + q[:300])
    return r.stdout.strip()
C = {w: Client(w) for w in ['admin', 'ops', 'transh', 'hotelres', 'requester', 'accountant', 'transport']}
AJ = {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
def get(w, p, **kw): return C[w].s.get(C[w].base + p, allow_redirects=False, **kw)
def post(w, p, data=None):
    if p.startswith('tour/transport_ops/'):   # the Transport Desk posts JSON
        return C[w].s.post(C[w].base + p, json=data or {}, headers=AJ, allow_redirects=False)
    return C[w].s.post(C[w].base + p, data=data or {}, headers=AJ, allow_redirects=False)
def denied_count(user, like=''): return int(sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action='access_denied' AND user='{user}'" + (f" AND ref_id LIKE '{like}%'" if like else '')))
TOUR, MID = '2325', 'AH 2218'
tname = sql(f"SELECT consignee FROM consignee WHERE tour_id='{TOUR}'")

print('\n== 80 the Driver Allocation page')
for w in ['ops', 'admin']:
    r = get(w, f'dashboard2/transport/driver_allow/tour_transport?tour_id={TOUR}')
    check(80, f'{w}: the page for a tour opens (200) and shows the tour', r.status_code == 200 and tname.split('/')[0].strip() in r.text, (r.status_code, len(r.text)))
    r = get(w, 'dashboard2/transport/driver_allow/tour_transport')
    loc = r.headers.get('Location', '')
    r2 = C[w].s.get(loc if loc.startswith('http') else C[w].base + loc, allow_redirects=False) if loc else None
    check(80, f'{w}: opened WITHOUT a tour (the menu entry) it no longer errors — it goes to the existing "Tours pending driver allocation" list (302 → 200)', r.status_code == 302 and 'pending_tours' in loc and r2 is not None and r2.status_code == 200, (r.status_code, loc, r2.status_code if r2 is not None else None))
d0 = denied_count('tctransh')
for w in ['transh', 'requester', 'hotelres']:
    r = get(w, f'dashboard2/transport/driver_allow/tour_transport?tour_id={TOUR}')
    check(80, f'{w}: the allocation page is refused (302 to the 403 page)', r.status_code == 302 and '403' in r.headers.get('Location', ''), (r.status_code, r.headers.get('Location')))

print('\n== 81 the Crew replacement and Driver advance entries')
for w in ['ops', 'admin']:
    r = get(w, 'tour/transport_ops/crew_change'); loc = r.headers.get('Location', '')
    r2 = C[w].s.get(loc, allow_redirects=False) if loc.startswith('http') else C[w].s.get(C[w].base + loc.lstrip('/'), allow_redirects=False)
    check(81, f'{w}: "Crew replacement" opens the Transport Desk at its replacement work (302 → tour/transport_ops?focus=crew_change → 200) — no second workflow', r.status_code == 302 and 'transport_ops?focus=crew_change' in loc and r2.status_code == 200, (r.status_code, loc, r2.status_code))
r = get('transh', 'tour/transport_ops/crew_change')
check(81, 'Transport & Guide Handling: "Crew replacement" refused (→ 403 page) and recorded', r.status_code == 302 and '403' in r.headers.get('Location', '') and denied_count('tctransh', 'tour/transport_ops/crew_change') >= 1, (r.status_code, r.headers.get('Location')))
r = get('transh', 'tour/transport_ops/driver_advance')
check(81, 'Transport & Guide Handling (holds Driver advance): the entry opens the desk at the advance (focus=driver_advance)', r.status_code == 302 and 'focus=driver_advance' in r.headers.get('Location', ''), r.headers.get('Location'))
r = get('ops', 'tour/transport_ops/driver_advance')
check(81, 'Operations (no Driver advance privilege): that entry is refused and recorded', r.status_code == 302 and '403' in r.headers.get('Location', '') and denied_count('tcops', 'tour/transport_ops/driver_advance') >= 1, r.headers.get('Location'))

print('\n== 82 allocation actions on the Transport Desk')
job = sql(f"SELECT j.id FROM tf_transport_job j ORDER BY j.id DESC LIMIT 1"); crew = sql(f"SELECT id FROM tf_transport_crew WHERE job_id={job} ORDER BY id LIMIT 1")
tt = sql(f"SELECT tour_id FROM tf_transport_job WHERE id={job}"); stage0 = sql(f"SELECT CONCAT(stage,'|',COALESCE(cancelled_at,'')) FROM tf_tour WHERE id={tt}")
sql(f"UPDATE tf_tour SET stage='confirmed', cancelled_at=NULL WHERE id={tt}")   # the test copy's tours are all cancelled; one is reopened for the test and put back after
d0 = denied_count('tctransh')
codes = {
    'crew (engage)': post('transh', f'tour/transport_ops/crew/{crew}', {'status': 'engaged'}).status_code,
    'vehicle': post('transh', f'tour/transport_ops/card/{job}/vehicle', {'reg': 'TEST-X'}).status_code,
    'days worked': post('transh', f'tour/transport_ops/crew/{crew}/dates', {'date_from': '2026-10-12', 'date_to': '2026-10-13'}).status_code,
    'replace': post('transh', f'tour/transport_ops/crew/{crew}/replace', {'date': '2026-10-13'}).status_code,
}
check(82, 'Transport & Guide Handling cannot allocate: engage crew, set the vehicle, the days worked, replace — all 403 %s, each recorded' % codes, all(c == 403 for c in codes.values()) and denied_count('tctransh') - d0 >= 4, (codes, denied_count('tctransh') - d0))
reg0 = sql(f"SELECT COALESCE(vehicle_reg,'') FROM tf_transport_job WHERE id={job}") if sql("SHOW COLUMNS FROM tf_transport_job LIKE 'vehicle_reg'") else None
for w, reg in [('ops', 'TEST-OPS-1'), ('admin', 'TEST-ADM-1'), ('transport', 'TEST-LEG-1')]:
    r = post(w, f'tour/transport_ops/card/{job}/vehicle', {'vehicle_id': '', 'reg': reg, 'note': 'pilot-r2 test'})
    j = r.json() if r.headers.get('Content-Type', '').startswith('application/json') else {}
    check(82, f'{w}: sets the vehicle (200)' + (' — the existing Transport & Crew Guide Allocation Desk group keeps the ability it has today' if w == 'transport' else ''), r.status_code == 200 and j.get('status') == 200, (r.status_code, str(j)[:200]))
r = post('ops', f'tour/transport_ops/crew/{crew}', {'status': 'engaged', 'person_name': '', 'supplier_acc': ''})
check(82, 'Operations passes the allocation gate for engaging crew (the service answers on the content, not 403: %s)' % r.status_code, r.status_code != 403, r.text[:200])
st, ca = stage0.split('|', 1)
sql(f"UPDATE tf_tour SET stage='{st}', cancelled_at=" + (f"'{ca}'" if ca else 'NULL') + f" WHERE id={tt}")
r = post('requester', f'tour/transport_ops/card/{job}/vehicle', {'reg': 'X'})
check(82, 'a payment requester (no Transport Operations) is refused at the route (403)', r.status_code == 403, r.status_code)
pt = get('transh', 'tour/transport_ops').text; po = get('ops', 'tour/transport_ops').text
check(82, 'the desk page tells the browser: CAN_ALLOCATE false for Transport & Guide Handling, true for Operations', 'const CAN_ALLOCATE = false' in pt and 'const CAN_ALLOCATE = true' in po)

print('\n== 83 the legacy allocation endpoints')
codes = {w: post(w, 'dashboard2/transport/driver_allow/driver_allocation', {'tour_id': '', 'driver': ''}).status_code for w in ['transh', 'hotelres', 'requester']}
check(83, 'the legacy allocate / remove endpoints refuse every department without the Driver Allocation privilege (403, nothing executed): %s' % codes, all(c == 403 for c in codes.values()), codes)
r = get('ops', f'dashboard2/transport/driver_allow/driver_allocation_details?tour_id={TOUR}', headers=AJ)
check(83, 'Operations reaches the allocation details of a tour (200)', r.status_code == 200, r.status_code)

print('\n== 84 the supplier master: Hotel Reservations keeps the contacts, sees the bank to four digits, cannot change it')
SUP = '6-9-11-11590'; FULL = 'TEST1001002345678'
sql(f"UPDATE supplier SET sup_bank_acc='{FULL}', bank_id=1, bank_name='Test Bank', branch_id=1, branch_name='Test Branch', acc_holder='Gimanhala Holder' WHERE Sup_Acc_No='{SUP}'")
sid = sql(f"SELECT ID FROM supplier WHERE Sup_Acc_No='{SUP}'"); email = sql(f"SELECT email FROM supplier WHERE Sup_Acc_No='{SUP}'")
t = get('hotelres', 'master_files/supplier/all').text
check(84, 'Supplier Report (list): the full number is nowhere on the page; the masked "…5678" and the contact e-mail are', FULL not in t and '5678' in t and '•' in t and email in t, (FULL in t, '5678' in t, email in t))
j = C['hotelres'].s.get(C['hotelres'].base + f'master_files/supplier/get_supplier_data/{SUP}', headers=AJ).json()
check(84, 'the supplier lookup (used by the booking pages): bank number masked, contacts kept', '•' in (j.get('sup_bank_acc') or '') and j.get('sup_bank_acc', '').endswith('5678') and j.get('email') == email and j.get('Mobile') is not None, {k: j.get(k) for k in ['sup_bank_acc', 'email', 'Mobile']})
t = get('hotelres', f'master_files/supplier?id={sid}').text
check(84, 'the edit form: the number masked, the bank fields locked (notice shown); the full number is not in the page', FULL not in t and 'maintained by Accounts' in t, (FULL in t, 'maintained by Accounts' in t))
row = json.loads(subprocess.run(['mysql', '--socket=' + SOCK, '-uroot', '-N', 'avenir_test', '-e', f"SELECT JSON_OBJECT('Name',Name,'Address',Address,'TP',TP,'Mobile',Mobile,'email',email,'sup_cat',sup_cat,'Acc_No',Sup_Acc_No,'remark',remark,'cr_dyz',cr_dyz) FROM supplier WHERE ID={sid}"], capture_output=True, text=True).stdout.strip())
form = {'supplier_name': row['Name'], 'address': row['Address'] or 'x', 'tel_home': row['TP'] or '', 'tel_mobile': row['Mobile'] or '', 'email': row['email'] or '', 'sup_cat': row['sup_cat'], 'Acc_No': row['Acc_No'], 'remark': 'pilot-r2 contact update', 'credit_days': row['cr_dyz'] or 0,
        'sup_bank_acc': 'HACK000099999', 'bank': '2', 'bank_id': '2', 'branch': '2', 'branch_id': '2', 'bank_name': 'Other Bank', 'branch_name': 'Other', 'bank_acc_holder': 'Someone Else'}
d0 = denied_count('tchotelres')
r = post('hotelres', f'master_files/supplier/update/{sid}', form); j = r.json() if r.status_code == 200 else {}
after = sql(f"SELECT CONCAT_WS('|', sup_bank_acc, bank_id, bank_name, branch_id, branch_name, acc_holder, remark) FROM supplier WHERE ID={sid}")
check(84, 'a direct update with a different bank account: contacts saved, the bank details EXACTLY as before, the attempt recorded (%s)' % (j.get('message') or r.status_code), j.get('status') == 200 and after.startswith(f'{FULL}|1|Test Bank|1|Test Branch|Gimanhala Holder|') and after.endswith('pilot-r2 contact update') and denied_count('tchotelres') - d0 == 1, (after, denied_count('tchotelres') - d0))
cform = dict(form, supplier_name='Pilot R2 Test Hotel %s' % os.getpid(), Acc_No='', sup_cat='1', sup_bank_acc='NEW00011112222')
r = post('hotelres', 'master_files/supplier/create', cform); j = r.json() if r.status_code == 200 else {}
newrow = sql(f"SELECT CONCAT_WS('|', COALESCE(sup_bank_acc,'-'), COALESCE(bank_id,'-')) FROM supplier WHERE Name='{cform['supplier_name']}'")
check(84, 'a new supplier entered by Hotel Reservations: created with its contacts, the bank fields left empty for Accounts (%s)' % (j.get('message') or r.status_code), (j.get('status') in (201, 200) and newrow in ('-|-', '|-', '-|', '|')) or (j.get('status') not in (201, 200) and 'NEW00011112222' not in sql("SELECT COALESCE(GROUP_CONCAT(sup_bank_acc),'') FROM supplier")), (j, newrow))
j = C['accountant'].s.get(C['accountant'].base + f'master_files/supplier/get_supplier_data/{SUP}', headers=AJ).json()
check(84, 'Accounts (pays) still sees the full number in the master', j.get('sup_bank_acc') == FULL, j.get('sup_bank_acc'))
r = post('accountant', f'master_files/supplier/update/{sid}', dict(form, sup_bank_acc='TEST1001009999999', remark='accounts change'));
ch = sql(f"SELECT sup_bank_acc FROM supplier WHERE ID={sid}")
check(84, 'Accounts can still change the bank details (finance access preserved)', ch == 'TEST1001009999999', (r.status_code, ch))
sql(f"UPDATE supplier SET sup_bank_acc='{FULL}', bank_id=1, bank_name='Test Bank', branch_id=1, branch_name='Test Branch', acc_holder='Gimanhala Holder' WHERE ID={sid}")

print('\n== 85 the dashboard data and a payment request')
dj = C['hotelres'].s.get(C['hotelres'].base + 'tour/tour_payment_dashboard/desk', params={'manual_id': MID}, headers=AJ).text
sj = C['hotelres'].s.get(C['hotelres'].base + 'tour/tour_payment_dashboard/search_tour', params={'manual_id': MID}, headers=AJ).text
check(85, 'the Tour Payment Dashboard JSON (the desk and the legacy tour search) carries no full bank number for Hotel Reservations', FULL not in dj and FULL not in sj and ('5678' in dj or '5678' in sj), (FULL in dj, FULL in sj))
aj = C['accountant'].s.get(C['accountant'].base + 'tour/tour_payment_dashboard/desk', params={'manual_id': MID}, headers=AJ).text
check(85, 'for Accounts the same data keeps the full number', FULL in aj)
t = sql(f"SELECT CONCAT(tour_id,'|',manual_id) FROM consignee WHERE tour_id='{TOUR}'").split('|')
n0 = int(sql("SELECT COALESCE(MAX(id),0) FROM payment_request"))
t = sql("SELECT CONCAT(tour_id,'|',manual_id) FROM consignee WHERE tour_id='2390'").split('|')
SUP2 = sql("SELECT s.Sup_Acc_No FROM supplier s WHERE s.sup_cat=1 AND NOT EXISTS (SELECT 1 FROM payment_request p WHERE p.tour_id='2390' AND p.hotel_acc=s.Sup_Acc_No) ORDER BY s.ID LIMIT 1"); FULL2 = 'TEST2002003344556'
sql(f"UPDATE supplier SET sup_bank_acc='{FULL2}', bank_name='Test Bank 2', branch_name='Test Branch 2' WHERE Sup_Acc_No='{SUP2}'")
r = post('hotelres', 'tour/tour_accounts/payment_request', {'tourId': t[0], 'manualId': t[1], 'consignee': '', 'createUser': '', 'bankName': 'x', 'branchName': 'x', 'supBankAcc': '•••••••••••••4556', 'totalHotelPay': 10, 'totalHotelAdvance': 0, 'dueAmount': 10, 'requestAmount': 10, 'tot_amt_doller': 10, 'hotelAcc': SUP2, 'hotelName': 'TEST SUPPLIER', 'activityId': 0, 'type': 'lunch'})
stored = sql(f"SELECT CONCAT_WS('|', COALESCE(sup_bank_acc,'-'), COALESCE(bank_name,'-'), COALESCE(branch_name,'-')) FROM payment_request WHERE id > {n0} AND hotel_acc='{SUP2}' ORDER BY id DESC LIMIT 1")
check(85, 'a request made from the masked dashboard stores the bank details of the MASTER (%s), never the masked text or the bank names the browser sent' % stored, stored == f'{FULL2}|Test Bank 2|Test Branch 2', (r.status_code, r.text[:160], stored))

print('\n== 86 the Pilot shortcuts of Operations')
t = get('ops', 'dashboard2/tiles').text; seg = t.split('id="pilot_panel"')[1].split('Menu Categories')[0]
hrefs = re.findall(r'href="[^"]*/avenir/([^"]+)"', seg)
check(86, 'Operations\' Pilot offers Driver Allocation (→ the pending list) and Crew replacement (→ the desk); both open', 'dashboard2/transport/driver_allow/pending_tours' in hrefs and 'tour/transport_ops/crew_change' in hrefs and get('ops', 'dashboard2/transport/driver_allow/pending_tours').status_code == 200, hrefs)
t = get('transh', 'dashboard2/tiles').text; seg = t.split('id="pilot_panel"')[1].split('Menu Categories')[0]
check(86, 'Transport & Guide Handling\'s Pilot offers neither', 'driver_allow' not in seg and 'crew_change' not in seg)
ok = sum(1 for r in RES if r['ok']); print('\n%d ok, %d failed' % (ok, len(RES) - ok))
json.dump(RES, open(HERE + '/results_ops_conf.json', 'w'), indent=1)
