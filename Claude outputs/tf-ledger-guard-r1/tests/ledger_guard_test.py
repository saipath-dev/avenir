r"""tf-ledger-guard-r1 — tests on the CLOUD TEST COPY with dummy ledger entries only (inserted here, removed at the end)."""
import os, sys, json, subprocess, re
from api import Client
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE); SOCK = S + '/mysql.sock'
RES = []
def check(name, ok, detail=''):
    RES.append({'name': name, 'ok': bool(ok), 'detail': str(detail)[:300]}); print(('  ok   ' if ok else '  FAIL ') + name + ('' if ok else '   ' + str(detail)[:300])); return ok
def sql(q):
    r = subprocess.run(['mysql', '--socket=' + SOCK, '-uroot', '-N', 'avenir_test', '-e', q], capture_output=True, text=True)
    if r.returncode != 0: raise SystemExit(r.stderr + q)
    return r.stdout.strip()
ADV, OTHER = 99990001, 99990002
sql(f"DELETE FROM account_runing WHERE ID IN ({ADV},{OTHER})")
sql(f"""INSERT INTO account_runing (ID, L_Code, Dr, Cr, Discription, Date, Linkacc, aflag, Cashier, Dt, inv_total) VALUES
 ({ADV}, '1-6-8-10380', 5000, 0, 'TEST ADVANCE (dummy)', '2026-10-11', '990001', 0, 'test', NOW(), ''),
 ({ADV}, '6-11-107-10381', 0, 5000, 'TEST ADVANCE (dummy)', '2026-10-11', '990001', 0, 'test', NOW(), ''),
 ({OTHER}, '1-6-8-10380', 0, 700, 'TEST OTHER ENTRY (dummy)', '2026-10-11', '990001', 0, 'test', NOW(), ''),
 ({OTHER}, '6-9-11-10550', 700, 0, 'TEST OTHER ENTRY (dummy)', '2026-10-11', '990001', 0, 'test', NOW(), '')""")
flags = lambda i: sql(f"SELECT GROUP_CONCAT(aflag ORDER BY L_Code) FROM account_runing WHERE ID={i}")
a0 = int(sql("SELECT COALESCE(MAX(id),0) FROM tpd_audit"))
try:
    adm = Client('admin'); req = Client('requester')
    AJ = {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
    r = adm.s.get(adm.base + f'tour/tour_details_search/advance_delete?id={ADV}', headers=AJ)
    check('GET advance_delete (the old way) is refused with 405 and changes nothing', r.status_code == 405 and flags(ADV) == '0,0', (r.status_code, flags(ADV)))
    r = req.s.post(req.base + 'tour/tour_details_search/advance_delete', data={'id': ADV, 'confirm': f'FLAG {ADV}', 'reason': 'test reason'}, headers=AJ, allow_redirects=False)
    check('a profile without the privilege is refused (POST) and nothing changes', r.status_code in (302, 403) and flags(ADV) == '0,0', (r.status_code, flags(ADV)))
    p = adm.s.get(adm.base + f'tour/tour_details_search/advance_delete/prepare?id={ADV}', headers=AJ).json()
    check('prepare (administrator): shows both legs, eligible, the confirmation phrase and a CSRF token; nothing changes', p.get('eligible') is True and len(p.get('rows', [])) == 2 and p.get('confirm_phrase') == f'FLAG {ADV}' and p.get('csrf', {}).get('hash') and flags(ADV) == '0,0', {k: p.get(k) for k in ['eligible', 'confirm_phrase']})
    tok = p['csrf']
    r = adm.s.post(adm.base + 'tour/tour_details_search/advance_delete', data={'id': ADV, 'confirm': f'FLAG {ADV}', 'reason': 'test reason'}, headers=AJ)
    check('POST without the CSRF token is refused (403) and nothing changes', r.status_code == 403 and flags(ADV) == '0,0', (r.status_code, r.text[:120]))
    p = adm.s.get(adm.base + f'tour/tour_details_search/advance_delete/prepare?id={ADV}', headers=AJ).json(); tok = p['csrf']
    r = adm.s.post(adm.base + 'tour/tour_details_search/advance_delete', data={'id': ADV, 'reason': 'test reason', tok['name']: tok['hash']}, headers=AJ)
    check('POST without the typed confirmation is refused (400) and nothing changes', r.status_code == 400 and flags(ADV) == '0,0', (r.status_code, r.text[:120]))
    p = adm.s.get(adm.base + f'tour/tour_details_search/advance_delete/prepare?id={OTHER}', headers=AJ).json(); tok = p['csrf']
    r = adm.s.post(adm.base + 'tour/tour_details_search/advance_delete', data={'id': OTHER, 'confirm': f'FLAG {OTHER}', 'reason': 'test reason', tok['name']: tok['hash']}, headers=AJ)
    check('an entry that is not a tour advance is refused (409) and nothing changes', r.status_code == 409 and flags(OTHER) == '0,0' and p.get('eligible') is False, (r.status_code, r.text[:120], flags(OTHER)))
    p = adm.s.get(adm.base + f'tour/tour_details_search/advance_delete/prepare?id={ADV}', headers=AJ).json(); tok = p['csrf']
    r = adm.s.post(adm.base + 'tour/tour_details_search/advance_delete', headers=dict(AJ, **{tok['header']: tok['hash']}), data={'id': ADV, 'confirm': f'FLAG {ADV}', 'reason': 'test: dummy advance taken out'})
    check('the full POST (token in the header, confirmation, reason) flags both legs of the dummy advance', r.status_code == 200 and flags(ADV) == '1,1', (r.status_code, r.text[:160], flags(ADV)))
    au = sql(f"SELECT CONCAT(user,'|',action,'|',ref_type,'|',ref_id,'|',LEFT(reason,40),'|',JSON_LENGTH(JSON_EXTRACT(prev_value,'$.rows')),'|',JSON_EXTRACT(new_value,'$.rows_changed'),'|',JSON_UNQUOTE(JSON_EXTRACT(prev_value,'$.rows[0].aflag')),'>',JSON_UNQUOTE(JSON_EXTRACT(new_value,'$.rows[0].aflag'))) FROM tpd_audit WHERE id>{a0} AND action='ledger_entry_flagged'")
    check('the change is recorded: user, entry, reason, both rows before (aflag 0) and after (aflag 1)', au.startswith(f'tcadmin|ledger_entry_flagged|ledger_entry|{ADV}|test: dummy advance taken out|2|2|0>1'), au)
    p = adm.s.get(adm.base + f'tour/tour_details_search/advance_delete/prepare?id={ADV}', headers=AJ).json(); tok = p['csrf']
    r = adm.s.post(adm.base + 'tour/tour_details_search/advance_delete', data={'id': ADV, 'confirm': f'FLAG {ADV}', 'reason': 'again', tok['name']: tok['hash']}, headers=AJ)
    check('flagging the same entry again is refused (409)', r.status_code == 409, (r.status_code, r.text[:120]))
    refusals = int(sql(f"SELECT COUNT(*) FROM tpd_audit WHERE id>{a0} AND action IN ('access_denied','ledger_flag_refused')"))
    check(f'every refusal is recorded ({refusals} refusal rows)', refusals >= 6, refusals)
finally:
    sql(f"DELETE FROM account_runing WHERE ID IN ({ADV},{OTHER})")
    json.dump(RES, open(HERE + '/results_ledger_guard_cloud.json', 'w'), indent=1)
    print('%d/%d passed' % (sum(r['ok'] for r in RES), len(RES)))
