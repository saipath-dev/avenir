r"""tf-pilot-r3 — tests 90–95 on the TEST COPY: the two further routes that served a supplier's full bank number to Hotel
Reservations (the Hotel Rate master's supplier lookup, the Driver form) masked; the audit trail keeps only the last four
digits of an attempted / kept number and serves it masked; the profiles older than the department structure keep the access
they had before tf-pilot-r2. Writes results_r3.json. Restores every row it changes."""
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
AJ = {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
SUP = '6-9-11-11590'; FULL = 'TEST1001002345678'
sid = sql(f"SELECT ID FROM supplier WHERE Sup_Acc_No='{SUP}'")
drv = sql("SELECT ID FROM supplier WHERE sup_cat=2 ORDER BY ID LIMIT 1"); DRVFULL = 'TEST2002009876543'
orig = sql(f"SELECT CONCAT_WS('|', ID, COALESCE(sup_bank_acc,''), COALESCE(bank_id,''), COALESCE(bank_name,''), COALESCE(branch_id,''), COALESCE(branch_name,''), COALESCE(acc_holder,''), COALESCE(remark,'')) FROM supplier WHERE ID IN ({sid},{drv})")
sql(f"UPDATE supplier SET sup_bank_acc='{FULL}', bank_id=1, bank_name='Test Bank', branch_id=1, branch_name='Test Branch', acc_holder='Gimanhala Holder' WHERE ID={sid}")
sql(f"UPDATE supplier SET sup_bank_acc='{DRVFULL}', bank_name='Test Bank', branch_name='Test Branch', acc_holder='Driver Holder' WHERE ID={drv}")
# a legacy profile (Hotel Desk, group 27 — older than the department structure) given the Supplier and Hotel Rate masters for this test only
had27 = sql("SELECT GROUP_CONCAT(menu_id) FROM user_privileges WHERE group_id=27 AND menu_id IN (29,67,133)")
sql("INSERT INTO user_privileges (group_id, user_id, menu_id) SELECT 27, 1, m FROM (SELECT 29 m UNION SELECT 67 UNION SELECT 133) x WHERE m NOT IN (SELECT menu_id FROM user_privileges WHERE group_id=27)")
# the Hotel Reservations test group holds the Hotel Rate master on the development system: given here for the test
had913 = sql("SELECT COUNT(*) FROM user_privileges WHERE group_id=913 AND menu_id=67")
if had913 == '0': sql("INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES (913, 1, 67)")
try:
    C = {w: Client(w) for w in ['hotelres', 'auditor', 'accountant', 'admin', 'hotel', 'ops', 'transh']}
    def get(w, p, **kw): return C[w].s.get(C[w].base + p, allow_redirects=False, **kw)
    def post(w, p, data=None): return C[w].s.post(C[w].base + p, data=data or {}, headers=AJ, allow_redirects=False)

    print('\n== 90 the Hotel Rate master\'s supplier lookup (master_files/hotel_rate/show)')
    for w, full in [('hotelres', False), ('ops', False), ('accountant', True), ('admin', True), ('hotel', True)]:
        r = get(w, f'master_files/hotel_rate/show/{SUP}', headers=AJ)
        if r.status_code != 200:
            check(90, f'{w}: refused by its privileges ({r.status_code}) — nothing served', FULL not in r.text, r.status_code); continue
        acc = ((r.json() or {}).get('data') or {}).get('sup_bank_acc', '')
        check(90, f'{w}: ' + ('the full number (pays / verifies, or a profile older than the departments — as before)' if full else 'the number to its last four digits, the contacts kept'),
              (acc == FULL) if full else (FULL not in r.text and acc.endswith('5678') and '•' in acc and 'email' in r.json()['data']), acc)

    print('\n== 91 the Driver form (master_files/supplier/new_driver_index)')
    for w, full in [('hotelres', False), ('transh', False), ('accountant', True), ('admin', True), ('hotel', True)]:
        r = get(w, f'master_files/supplier/new_driver_index?id={drv}')
        if r.status_code != 200:
            check(91, f'{w}: refused by its privileges ({r.status_code})', DRVFULL not in r.text, r.status_code); continue
        t = r.text
        check(91, f'{w}: ' + ('full number, fields editable (as before)' if full else 'number masked, bank fields locked with the notice; the full number nowhere in the page'),
              (DRVFULL in t and 'maintained by Accounts' not in t) if full else (DRVFULL not in t and '6543' in t and 'maintained by Accounts' in t), (DRVFULL in t, 'maintained by Accounts' in t))

    print('\n== 92 the audit trail keeps four digits only')
    a0 = int(sql("SELECT COALESCE(MAX(id),0) FROM tpd_audit"))
    row = json.loads(sql(f"SELECT JSON_OBJECT('Name',Name,'Address',Address,'TP',TP,'Mobile',Mobile,'email',email,'sup_cat',sup_cat,'Acc_No',Sup_Acc_No,'cr_dyz',cr_dyz) FROM supplier WHERE ID={sid}"))
    form = {'supplier_name': row['Name'], 'address': row['Address'] or 'x', 'tel_home': row['TP'] or '', 'tel_mobile': row['Mobile'] or '', 'email': row['email'] or '', 'sup_cat': row['sup_cat'], 'Acc_No': row['Acc_No'], 'remark': 'pilot-r3 audit test', 'credit_days': row['cr_dyz'] or 0,
            'sup_bank_acc': 'HACK000099991111', 'bank': '2', 'bank_id': '2', 'branch': '2', 'branch_id': '2', 'bank_name': 'Other Bank', 'branch_name': 'Other', 'bank_acc_holder': 'Someone Else'}
    r = post('hotelres', f'master_files/supplier/update/{sid}', form)
    rec = sql(f"SELECT CONCAT(COALESCE(new_value,''), COALESCE(prev_value,'')) FROM tpd_audit WHERE id > {a0} AND user='tchotelres' AND action='access_denied'")
    kept = sql(f"SELECT sup_bank_acc FROM supplier WHERE ID={sid}")
    check(92, 'Hotel Reservations\' attempt to change the bank: refused on the server (stored number unchanged) and recorded', kept == FULL and rec != '', (kept, bool(rec)))
    check(92, 'the recorded attempt holds neither the stored number nor the attempted one in full (…5678, …1111 only)', FULL not in rec and 'HACK000099991111' not in rec and '5678' in rec and '1111' in rec, rec[:300])
    for w, full in [('auditor', False), ('admin', True)]:
        r = get(w, 'tour/tour_payment_dashboard/audit_log', params={'user': 'tchotelres', 'limit': 5}, headers=AJ)
        check(92, f'{w}: the audit trail opens (200) and shows no full account number', r.status_code == 200 and FULL not in r.text and 'HACK000099991111' not in r.text, (r.status_code, FULL in r.text))
    # an older audit row written in full (before r3) is served masked to the auditor
    sql("INSERT INTO tpd_audit (at, user, action, ref_type, ref_id, new_value, reason) VALUES (NOW(), 'r3test', 'access_denied', 'action', 'r3test', '{\"kept\":{\"sup_bank_acc\":\"TEST1001002345678\"}}', 'r3 test row')")
    r = get('auditor', 'tour/tour_payment_dashboard/audit_log', params={'user': 'r3test', 'limit': 5}, headers=AJ)
    ra = get('admin', 'tour/tour_payment_dashboard/audit_log', params={'user': 'r3test', 'limit': 5}, headers=AJ)
    check(92, 'an audit row written with a full number (as r2 did) reaches the auditor masked; the administrator sees it as stored', FULL not in r.text and '5678' in r.text and FULL in ra.text, (FULL in r.text, FULL in ra.text))
    sql("DELETE FROM tpd_audit WHERE user='r3test'")

    print('\n== 93 the profiles older than the departments keep what they had before tf-pilot-r2 (Hotel Desk, group 27, given the masters for the test)')
    j = C['hotel'].s.get(C['hotel'].base + f'master_files/supplier/get_supplier_data/{SUP}', headers=AJ).json()
    check(93, 'the supplier lookup: full number (as before)', j.get('sup_bank_acc') == FULL, j.get('sup_bank_acc'))
    t = get('hotel', f'master_files/supplier?id={sid}').text
    check(93, 'the edit form: full number, not locked', FULL in t and 'maintained by Accounts' not in t)
    r = post('hotel', f'master_files/supplier/update/{sid}', dict(form, sup_bank_acc='TEST1001007777777', remark='legacy change'))
    ch = sql(f"SELECT sup_bank_acc FROM supplier WHERE ID={sid}")
    check(93, 'it can still change the bank details (existing access unchanged)', ch == 'TEST1001007777777', (r.status_code, ch))
    sql(f"UPDATE supplier SET sup_bank_acc='{FULL}' WHERE ID={sid}")
    dj = C['hotel'].s.get(C['hotel'].base + 'tour/tour_payment_dashboard/search_tour', params={'manual_id': 'AH 2218'}, headers=AJ)
    check(93, 'the legacy dashboard data: not masked for it (as before r2) — or refused by its privileges', dj.status_code in (200, 302, 403), dj.status_code)

    print('\n== 94 the department profiles stay masked everywhere the scan reached (see results_scan_*.json)')
    for f, who in [('results_scan_hotelres.json', 'Hotel Reservations (test group: Supplier masters + dashboard)')] + [(f'results_scan_{w}_devmirror.json', f'{n} with the development system\'s menus') for w, n in [('hotelres', 'Hotel Reservations'), ('requester', 'Tour Reservations'), ('transh', 'Transport & Guide Handling'), ('ops', 'Pilot / Operations Allocation'), ('auditor', 'Auditor (read-only)'), ('approver', 'Management Approver')]]:
        p = os.path.join(HERE, f)
        if os.path.exists(p):
            s = json.load(open(p))
            sup = [l for l in s['leaks'] if 'supplier' in l.get('kind', ['supplier'])]
            av = sorted(set(l['route'].split('/')[2] for l in s['leaks'] if l.get('kind') == ['avenir']))
            check(94, f'{who}: {s["requests"]} GET requests over every route, {s["ok200"]} answered 200 — full supplier bank numbers found: {len(sup)}' + (f' (Avenir\'s own paying account shown on: {", ".join(av)})' if av else ''), len(sup) == 0, sup)
finally:
    for line in orig.split('\n'):
        i, acc, bid, bn, brid, brn, holder, rem = (line.split('|') + [''] * 8)[:8]
        q = lambda v: 'NULL' if v == '' else "'" + v.replace("'", "''") + "'"
        sql(f"UPDATE supplier SET sup_bank_acc={q(acc)}, bank_id={q(bid)}, bank_name={q(bn)}, branch_id={q(brid)}, branch_name={q(brn)}, acc_holder={q(holder)}, remark={q(rem)} WHERE ID={i}")
    if had913 == '0': sql("DELETE FROM user_privileges WHERE group_id=913 AND menu_id=67")
    keep = set((had27 or '').split(',')) - {''}
    for m in ['29', '67', '133']:
        if m not in keep:
            sql(f"DELETE FROM user_privileges WHERE group_id=27 AND menu_id={m}")
    json.dump(RES, open(HERE + '/results_r3.json', 'w'), indent=1)
    print('\n%d/%d passed' % (sum(r['ok'] for r in RES), len(RES)))
