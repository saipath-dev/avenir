r"""tf-legacy-retire-r1 — cloud test copy (127.0.0.1:8081, database avenir_test; TEST logins and dummy rows only).

  P  positive control with the ORIGINAL code: both tools really do write (so the "nothing written" checks can see a write)
  O  the other Manual Update buttons with the ORIGINAL code, then with the NEW code, on the same restored database:
     same answers and the same tables afterwards
  R  refusals with the NEW code: no token / wrong token → 403; valid token → 409 with the retirement message; a user
     without the privilege is stopped by the permission filter; every attempt audited; the ledger, the entry counter and the
     2024 ledger unchanged; every table unchanged except the audit trail, the activity log and sessions
  B  rollback: the 5 original files back (md5), the pages show the original buttons again; then the kit applied again
"""
import os, sys, json, re, subprocess, time, hashlib, secrets
S = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, S + '/paydesk')
import api
SOCK = S + '/mysql.sock'; DB = 'avenir_test'; TM = S + '/tm'; KIT = S + '/legacy/kit/tf-legacy-retire-r1'
SNAP = S + '/legacy/snap_s0.sql'
AJ = {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
LEGACY_PW = os.environ.get('TC_LEGACY_PW') or secrets.token_urlsafe(18)   # a one-time password made for this run; never written anywhere
api.ACC['legacy'] = ('tclegacy', LEGACY_PW)
RES = []
def check(name, ok, detail=''):
    RES.append([('ok' if ok else 'FAIL'), name, '' if ok else str(detail)[:600]])
    print(('  ok   ' if ok else '  FAIL ') + name + ('' if ok else '  →  ' + str(detail)[:600]), flush=True)
def sql(q):
    r = subprocess.run(['mysql', '--socket=' + SOCK, '-uroot', '-N', '-B', DB, '-e', q], capture_output=True, text=True)
    if r.returncode != 0:
        raise SystemExit(r.stderr + '\n' + q[:300])
    return r.stdout.strip()
def ledger():
    a = sql("SELECT CONCAT(COUNT(*), '|', COALESCE(SUM(Dr),0), '|', COALESCE(SUM(Cr),0), '|', COALESCE(SUM(bank_rec),0), '|', COALESCE(SUM(bankst_flag),0)) FROM account_runing")
    cs = sql("CHECKSUM TABLE account_runing, invoice_no, account_runing_2024").split('\n')
    return {'account_runing': a, 'checksums': [l.split('\t')[1] for l in cs]}
EXCLUDE = {'a_log', 'ci_sessions', 'user_sessions', 'tpd_audit'}
def tables():
    ts = sql("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'avenir_test' AND TABLE_TYPE = 'BASE TABLE' ORDER BY 1").split('\n')
    out = {}
    for i in range(0, len(ts), 40):
        for l in sql('CHECKSUM TABLE ' + ', '.join('`%s`' % t for t in ts[i:i + 40])).split('\n'):
            n, v = l.split('\t'); out[n.split('.', 1)[1]] = v
    return out
def tdiff(a, b): return sorted(t for t in set(a) | set(b) if t not in EXCLUDE and a.get(t) != b.get(t))
def snapshot():
    with open(SNAP, 'w') as f:
        subprocess.run(['mysqldump', '--socket=' + SOCK, '-uroot', '--single-transaction', '--routines', '--triggers', '--skip-dump-date', DB], stdout=f, check=True)
def restore():
    with open(SNAP) as f:
        subprocess.run(['mysql', '--socket=' + SOCK, '-uroot', DB], stdin=f, check=True)
def kit(mode):
    r = subprocess.run(['python3', KIT + '/apply.py', mode, TM], capture_output=True, text=True)
    time.sleep(3)   # PHP's opcache revalidates changed files every 2 s (opcache.revalidate_freq = 2)
    try:
        return r.returncode, json.loads(r.stdout)
    except Exception:
        return r.returncode, {'raw': r.stdout[-500:] + r.stderr[-500:]}
FILES = ['app/Libraries/BankRec/LegacyGuard.php', 'app/Controllers/Settings/ManualUpdateController.php', 'app/Controllers/Accounts/BankUnrealisedValueController.php',
         'app/Views/pages/settings/manual_update.php', 'app/Views/pages/accounts/bank_unrealised_value_change.php']
def md5(p): return hashlib.md5(open(p, 'rb').read()).hexdigest()
ORIG = {l.split()[1]: l.split()[0] for l in open(S + '/legacy/orig.md5').read().split('\n') if l.strip()}
NEW = {l.split()[1]: l.split()[0] for l in open(KIT + '/SOURCE-MD5.txt').read().split('\n') if l.strip()}
def state(): return {f: md5(TM + '/' + f) for f in FILES}

OTHER = ['des_update', 'des_other_update', 'hotel_voucher_accept', 'entrance_update', 'other_update', 'transport_update', 'voucher_date_update', 'verify_tour', 'debtor_receipt_generate']
BUTTONS = ['des_update', 'des_other_update', 'hotel_voucher_accept', 'entrance_update', 'other_update', 'transport_update', 'voucher_date_update', 'verify_tour']
MSG_A = 'This one-off was completed on 21 October 2025 (entries 15947–16232) and has been retired. Running it again would copy the same 2024/25 entries into the ledger a second time. Nothing was changed.'
MSG_B = 'Bank Req Data Transfer has been retired. The 2024/25 unreconciled bank items were already carried forward on 21 October 2025, and the current ledger has no 31 March 2025 brought-forward balance to adjust. Nothing was changed.'
MSG_T = 'Security token missing or expired — reload the page. Nothing was changed.'
PA = 'settings/manual_update/bank_recon_record_insert'; PB = 'accounts/bank_unrealised_value/process'

def setup():
    gid = 921
    h = subprocess.run(['php', '-r', 'echo password_hash(getenv("LR_PW"), PASSWORD_DEFAULT);'], capture_output=True, text=True, env=dict(os.environ, LR_PW=LEGACY_PW)).stdout
    if sql(f"SELECT COUNT(*) FROM group_profile WHERE id = {gid}") == '0':
        sql(f"INSERT INTO group_profile (id, type, user, del) VALUES ({gid}, 'TEST Legacy tools holder (test copy)', 1, 0)")
    if sql(f"SELECT COUNT(*) FROM employee WHERE emp_num = {gid}") == '0':
        sql(f"INSERT INTO employee (emp_num, Acc_No, Name) VALUES ({gid}, '4-8-9-90{gid}', 'Legacy tools tester')")
    if sql("SELECT COUNT(*) FROM user_acc_tbl WHERE user = 'tclegacy'") == '0':
        sql(f"INSERT INTO user_acc_tbl (id, emp_id, user, pass, group_id, access, login_type, user_role) VALUES ({gid}, {gid}, 'tclegacy', '{h}', {gid}, 1, 1, 0)")
    sql(f"UPDATE user_acc_tbl SET pass = '{h}' WHERE user = 'tclegacy'")   # a new one-time password every run
    if sql("SELECT COUNT(*) FROM menu_list WHERE url = 'accounts/bank_unrealised_value' AND del = 0") == '0':   # menu 184 on development
        sql("INSERT INTO menu_list (cat_id, name, user, del, url, order_id, show_in_menu_bar) VALUES (4, 'Bank Req Data Transfer', 1, 0, 'accounts/bank_unrealised_value', 184, 0)")
    for u in ('settings/manual_update', 'accounts/bank_unrealised_value'):
        mid = sql(f"SELECT id FROM menu_list WHERE url = '{u}' AND del = 0 LIMIT 1")
        if sql(f"SELECT COUNT(*) FROM user_privileges WHERE group_id = {gid} AND menu_id = {mid}") == '0':
            sql(f"INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES ({gid}, 1, {mid})")

def post(c, path, data=None):
    r = c.s.post(c.base + path, data=data or {}, headers=AJ, timeout=600)
    try:
        j = r.json()
    except Exception:
        j = {'raw': r.text[:300]}
    return r.status_code, j
def page(c, path):
    r = c.s.get(c.base + path, timeout=120); return r.status_code, r.text
def token(html):
    m = re.search(r'data-csrf-name="([^"]+)" data-csrf-hash="([0-9a-f]+)"', html)
    return (m.group(1), m.group(2)) if m else None

def main():
    t0 = time.time()
    if state() != ORIG:
        rc, j = kit('rollback'); print('pre-rollback', rc)
    check('start: the 5 files on the test copy are the originals (same md5 as development and the isolated instance)', state() == ORIG, state())
    setup(); snapshot()
    L0 = ledger(); T0 = tables()
    # ---------------------------------------------------------------- P: positive control with the ORIGINAL code
    adm = api.Client('admin')
    sql("SET SESSION sql_mode = ''; INSERT INTO account_runing (ID, L_Code, Dr, Cr, Discription, Date, Cashier, Dt) VALUES (12340, '9-99-999-90001', 1.00, 0, 'TEST DUMMY source row (test copy)', '2025-03-01', 'tc', NOW()), (12340, '9-99-999-90002', 0, 1.00, 'TEST DUMMY source row (test copy)', '2025-03-01', 'tc', NOW())")
    sql("SET SESSION sql_mode = ''; INSERT INTO account_runing_2024 (ID, L_Code, Dr, Cr, Discription, Date, Cashier, Dt, bank_rec) VALUES (99981001, '1-6-8-10380', 0, 2.00, 'TEST DUMMY 2024 bank row (test copy)', '2025-03-20', 'tc', NOW(), 0), (99981001, '9-99-999-90003', 2.00, 0, 'TEST DUMMY 2024 other row (test copy)', '2025-03-20', 'tc', NOW(), 0)")
    n0 = int(sql("SELECT COUNT(*) FROM account_runing")); acc0 = int(sql("SELECT acc_id FROM invoice_no LIMIT 1"))
    ca, ja = post(adm, PA)
    n1 = int(sql("SELECT COUNT(*) FROM account_runing")); acc1 = int(sql("SELECT acc_id FROM invoice_no LIMIT 1"))
    cb, jb = post(adm, PB, {'bank': '1-6-8-10380'})
    n2 = int(sql("SELECT COUNT(*) FROM account_runing"))
    check(f'P positive control, ORIGINAL code: "Bank Not Reconciliation Record Insert" writes (ledger rows {n0}→{n1}, entry counter {acc0}→{acc1}); '
          f'"Bank Req Data Transfer" writes ({n1}→{n2}) — so the checks below can see a write', ca == 200 and n1 >= n0 + 2 and acc1 == acc0 + 286 and cb == 200 and n2 == n1 + 2, (ca, ja, cb, jb, n0, n1, n2, acc0, acc1))
    restore()
    check('P the test copy is restored to the snapshot: ledger, entry counter and 2024 ledger as before; all tables as before',
          ledger() == L0 and not tdiff(T0, tables()), (ledger(), L0, tdiff(T0, tables())))
    # ---------------------------------------------------------------- O: other Manual Update buttons, ORIGINAL code
    adm = api.Client('admin')
    c, h = page(adm, 'settings/manual_update')
    orig_ids = [b for b in BUTTONS if f'id="{b}"' in h]
    check('O ORIGINAL page: Manual Update opens (200) with the 8 visible buttons + "Bank Not Reconciliation Record Insert"', c == 200 and orig_ids == BUTTONS and 'id="bank_recon_record_insert"' in h, (c, orig_ids))
    before = {}
    for e in OTHER:
        t = time.time(); code, j = post(adm, 'settings/manual_update/' + e); before[e] = (code, j.get('status'), j.get('message'), round(time.time() - t, 1))
    T_orig = tables()
    restore()
    # ---------------------------------------------------------------- apply the kit
    rc, j = kit('apply')
    check('apply: apply.py apply → the 5 files copied and verified by md5; originals backed up', rc == 0 and j.get('verified') is True and state() == NEW, (rc, j))
    adm = api.Client('admin')
    after = {}
    for e in OTHER:
        t = time.time(); code, jj = post(adm, 'settings/manual_update/' + e); after[e] = (code, jj.get('status'), jj.get('message'), round(time.time() - t, 1))
    T_new = tables()
    same = [e for e in OTHER if before[e][:3] == after[e][:3]]
    check('O NEW code: the 9 other Manual Update actions answer exactly as before (' + '; '.join(f'{e} {before[e][0]}/{before[e][2]}' for e in OTHER) + ')', same == OTHER, (before, after))
    d = tdiff(T_orig, T_new)
    check('O NEW code: after running all 9 other actions, every table is the same as after running them with the ORIGINAL code (audit, activity log and sessions aside)', not d, d)
    restore()
    check('O the test copy is restored to the snapshot again', ledger() == L0 and not tdiff(T0, tables()), tdiff(T0, tables()))
    # ---------------------------------------------------------------- R: refusals, NEW code
    a0 = int(sql("SELECT COALESCE(MAX(id), 0) FROM tpd_audit"))
    expect = []
    for who in ('legacy', 'admin'):
        u = api.ACC[who][0]; cl = api.Client(who)
        c1, h1 = page(cl, 'settings/manual_update'); c2, h2 = page(cl, 'accounts/bank_unrealised_value')
        check(f'R [{u}] Manual Update opens (200): the retired button is replaced by the "Retired" notice; the 8 other buttons and their actions are still there',
              c1 == 200 and 'id="bank_recon_record_insert_retired"' in h1 and 'Retired:</strong> Bank Not Reconciliation Record Insert' in h1 and 'id="bank_recon_record_insert"' not in h1
              and all(f'id="{b}"' in h1 for b in BUTTONS) and all(f"settings/manual_update/{e}'" in h1 for e in OTHER), (c1, [b for b in BUTTONS if f'id="{b}"' not in h1]))
        check(f'R [{u}] Bank Req Data Transfer opens (200): the Process button is replaced by the "Retired" notice; nothing else on the page changed',
              c2 == 200 and 'id="process_btn_retired"' in h2 and 'id="process_btn"' not in h2 and 'id="bank"' in h2, c2)
        tok = token(h1)
        # A
        ca1, ja1 = post(cl, PA)                                          # as the old open page sends it: no token
        ca2, ja2 = post(cl, PA, {tok[0]: '0' * 32})                      # a wrong token
        ca3, ja3 = post(cl, PA, {tok[0]: tok[1]})                        # the valid token
        check(f'R [{u}] "Bank Not Reconciliation Record Insert": no token → 403, wrong token → 403 ("{MSG_T}"); valid token → 409 with the retirement message',
              (ca1, ca2, ca3) == (403, 403, 409) and ja1.get('message') == MSG_T and ja2.get('message') == MSG_T and ja3.get('message') == MSG_A and ja3.get('retired') is True, (ca1, ja1, ca2, ja2, ca3, ja3))
        tok2 = (tok[0], ja3.get('csrf') or tok[1])                       # the server rotates the token after a valid one
        cb1, jb1 = post(cl, PB, {'bank': '1-6-8-10380'})
        cb2, jb2 = post(cl, PB, {'bank': '1-6-8-10380', tok2[0]: 'f' * 32})
        cb3, jb3 = post(cl, PB, {'bank': '1-6-8-10380', tok2[0]: tok2[1]})
        check(f'R [{u}] "Bank Req Data Transfer": no token → 403, wrong token → 403; valid token → 409 with the retirement message',
              (cb1, cb2, cb3) == (403, 403, 409) and jb1.get('message') == MSG_T and jb3.get('message') == MSG_B, (cb1, jb1, cb2, cb3, jb3))
        expect += [(u, 'bank_recon_record_insert', 'token', 403), (u, 'bank_recon_record_insert', 'token', 403), (u, 'bank_recon_record_insert', 'retired', 409),
                   (u, 'bank_unrealised_value', 'token', 403), (u, 'bank_unrealised_value', 'token', 403), (u, 'bank_unrealised_value', 'retired', 409)]
    acct = api.Client('accountant')
    cx, jx = post(acct, PA); cy, jy = post(acct, PB, {'bank': '1-6-8-10380'})
    check('R a user WITHOUT the two privileges (tcaccount) is stopped by the permission filter (403) before the tool: the retirement message is never reached',
          cx == 403 and cy == 403 and jx.get('message') != MSG_A and jy.get('message') != MSG_B, (cx, jx, cy, jy))
    rows = sql(f"SELECT user, action, ref_type, ref_id, JSON_UNQUOTE(JSON_EXTRACT(new_value, '$.why')), JSON_EXTRACT(new_value, '$.answer'), COALESCE(JSON_UNQUOTE(JSON_EXTRACT(new_value, '$.bank')), ''), COALESCE(ip, ''), reason FROM tpd_audit WHERE id > {a0} ORDER BY id").split('\n')
    rows = [r.split('\t') for r in rows if r]
    got = [(r[0], r[3], r[4], int(r[5])) for r in rows if r[1] == 'legacy_tool_refused']
    check(f'R every attempt is audited: {len(got)} "legacy_tool_refused" rows, one per attempt, with the user, the tool, the reason (token / retired) and the answer (403 / 409)', got == expect, (got, expect))
    check('R the audit rows carry the IP address, the posted bank for Bank Req Data Transfer, ref_type legacy_tool and a reason ending "(nothing was changed)"',
          all(r[7] != '' and r[2] == 'legacy_tool' and r[8].endswith('(nothing was changed)') for r in rows if r[1] == 'legacy_tool_refused')
          and all(r[6] == '1-6-8-10380' for r in rows if r[1] == 'legacy_tool_refused' and r[3] == 'bank_unrealised_value'), rows)
    other = [(r[0], r[1]) for r in rows if r[1] != 'legacy_tool_refused']
    check('R the permission filter audited the unprivileged user\'s two attempts (access_denied)', other == [('tcaccount', 'access_denied'), ('tcaccount', 'access_denied')], other)
    L1 = ledger(); T1 = tables()
    check(f'R ledger unchanged: account_runing {L1["account_runing"]} (rows|ΣDr|ΣCr|Σbank_rec|Σbankst_flag), checksums of account_runing / invoice_no (entry counter) / account_runing_2024 identical',
          L1 == L0, (L1, L0))
    check('R every other table identical (only the audit trail, the activity log and sessions changed)', not tdiff(T0, T1), tdiff(T0, T1))
    # ---------------------------------------------------------------- B: rollback
    rc, j = kit('rollback')
    adm = api.Client('admin'); c1, h1 = page(adm, 'settings/manual_update'); c2, h2 = page(adm, 'accounts/bank_unrealised_value')
    check('B rollback: apply.py rollback restores the 5 original files exactly (md5 = the originals); the pages show the original buttons again (not pressed)',
          rc == 0 and j.get('all_five_original') is True and state() == ORIG and 'id="bank_recon_record_insert"' in h1 and 'Retired:' not in h1 and 'id="process_btn"' in h2, (rc, j, state()))
    rc, j = kit('apply')
    check('B the kit applied again after the rollback (verified by md5) — the test copy ends with the change in, like development will', rc == 0 and j.get('verified') is True and state() == NEW, (rc, j))
    check('B ledger still unchanged at the end', ledger() == L0, ledger())
    json.dump({'results': RES, 'other_actions_before': before, 'other_actions_after': after, 'ledger': L0, 'audit_rows': rows}, open(S + '/legacy/results_cloud.json', 'w'), indent=1, ensure_ascii=False)
    print(f"\n{sum(1 for r in RES if r[0] == 'ok')}/{len(RES)} passed  ({round(time.time() - t0)} s)")

if __name__ == '__main__':
    main()
