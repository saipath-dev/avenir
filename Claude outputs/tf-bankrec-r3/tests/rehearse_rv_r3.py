"""tf-bankrec-r3 — CLOUD REHEARSAL of the isolated-instance run of r3.
1. rehearse_rv_r2.py rebuilds the isolated instance's data (r1 demonstration snapshot → v2 upgrade → the r2 verification
   run in two browser sessions), i.e. the state avenir_rv is in now.
2. The before-r3 snapshot is taken with the kit's SQL (tools/r3_db.py, the same statements as for phpMyAdmin, here without
   INTO DUMPFILE), and all tables are checksummed.
3. tests/rv_test_r3.js runs in two browser sessions with the SQL steps in between (flags on dummy rows, the dummy posting
   entry and its changes), the two-user races included.
4. The kit's reset SQL (built from the snapshot) is run, and every table is compared with step 2."""
import asyncio, os, sys, json, subprocess, hashlib
from playwright.async_api import async_playwright
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, S + '/paydesk'); sys.path.insert(0, HERE)
import test_bankrec as T
from test_bankrec import sql
K3 = HERE + '/kit/tf-bankrec-r3'
sys.path.insert(0, K3 + '/tools')
import r3_db
OUT = HERE + '/r3work/rehearse'; os.makedirs(OUT + '/snap', exist_ok=True)
DB = 'avenir_test'

def mysql_file(path):
    r = subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '--default-character-set=utf8mb4', '-uroot', DB], stdin=open(path), capture_output=True, text=True)
    if r.returncode:
        raise SystemExit(path + ': ' + r.stderr[:500])
    return r.stdout

def raw(q):
    r = subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '--default-character-set=utf8mb4', '-uroot', '-N', '-B', '--raw', DB, '-e', q], capture_output=True, text=True)
    if r.returncode:
        raise SystemExit(r.stderr[:500])
    return r.stdout

def columns():
    out = {}
    for t, _, _ in r3_db.PARTS:
        out[t] = [c for c in raw(f"SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{t}' ORDER BY ORDINAL_POSITION").split('\n') if c]
    return out

def snapshot(cols, d):
    os.makedirs(d, exist_ok=True)
    for t, where, order in r3_db.PARTS:
        txt = raw('SET SESSION group_concat_max_len = 67108864; ' + r3_db.snapshot_query(t, cols[t], where, order)).rstrip('\n')
        open(f'{d}/d_{t}.sql', 'w', encoding='utf-8').write(txt)
    return {t: hashlib.md5(open(f'{d}/d_{t}.sql', 'rb').read()).hexdigest() for t, _, _ in r3_db.PARTS}

SKIP = ('tpd_audit', 'a_log', 'ci_sessions', 'user_sessions', 'login_attempts', 'tpd_login_throttle')   # append-only audit, activity log (logins, page visits), sessions
def checksums():
    ts = [t for t in raw("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME").split('\n') if t]
    out = {}
    for i in range(0, len(ts), 40):
        for line in raw('CHECKSUM TABLE ' + ', '.join('`' + t + '`' for t in ts[i:i + 40])).split('\n'):
            if line:
                n, v = line.split('\t'); out[n.split('.')[-1]] = v
    return out

def ensure_dual():
    import test_bankrec_r3
    test_bankrec_r3.ensure_dual()

async def main():
    # 1. the isolated instance's state (r1 demo → v2 → r2 verification), on the r3 code
    r = subprocess.run([sys.executable, '-u', HERE + '/rehearse_rv_r2.py'], capture_output=True, text=True)
    last = r.stdout.strip().split('\n')[-1]
    print('r2 verification rebuilt:', last, flush=True)
    f2 = json.load(open(HERE + '/results_rehearse_rv_r2.json'))['facts']
    V = int(sql("SELECT id FROM tpd_bank_account WHERE label = 'TEST LKR verification account (dummy)'"))
    ensure_dual()
    # 2. before-r3 snapshot (the kit's statements) and every table's checksum
    cols = columns(); json.dump(cols, open(OUT + '/columns.json', 'w'), indent=1)
    snap0 = snapshot(cols, OUT + '/snap')
    sum0 = checksums(); sig0 = sql(T.LEDGER_SIG)
    flags0 = sql("SELECT GROUP_CONCAT(CONCAT(ID, ':', L_Code, ':', COALESCE(New_L_Code, ''), ':', COALESCE(bank_rec, 0), ':', COALESCE(bankst_flag, 0)) ORDER BY ID, L_Code) FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999")
    # 3. the r3 verification in two sessions
    ids = {'LKR': 4, 'USD': 5, 'JPY': 6, 'V': V}
    cfg = {'base': '/avenir/', 'pw': 'tc-pay-2026', 'prep': 'tcaccount', 'rev': 'tcpayappr', 'view': 'tcbankappr', 'dual': 'tcdual', 'old': 'tcadmin', 'pwOld': 'tc-admin-2026',
           'depts': ['tchotelres', 'tcops'], 'ids': ids, 'cV': f2['B_ids']['cV']}
    js = open(K3 + '/tests/rv_test_r3.js', encoding='utf-8').read()
    extra = []
    def chk(n, c, d=''):
        extra.append(['ok' if c else 'FAIL', n, '' if c else json.dumps(d)[:400]])
    RACES = []
    async with async_playwright() as p:
        b = await p.chromium.launch()
        A = await (await b.new_context()).new_page(); B = await (await b.new_context()).new_page()
        await A.goto('http://127.0.0.1:8083/avenir/', wait_until='load'); await B.goto('http://localhost:8083/avenir/', wait_until='load')
        for pg in (A, B):
            await pg.evaluate('cfg => { window.__rvCfg = cfg; }', cfg); await pg.evaluate(js)
        ev = lambda pg, code: pg.evaluate(code)
        # D7
        a0 = int(sql("SELECT COALESCE(MAX(id), 0) FROM tpd_audit"))
        await ev(A, '__rv3.phaseD7()')
        chk('D7 every dummy row\'s flags (bank_rec, bankst_flag, unrealised moves) exactly as before the old-screen calls', sql("SELECT GROUP_CONCAT(CONCAT(ID, ':', L_Code, ':', COALESCE(New_L_Code, ''), ':', COALESCE(bank_rec, 0), ':', COALESCE(bankst_flag, 0)) ORDER BY ID, L_Code) FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999") == flags0, '')
        chk('D7 the 7 refusals are audited ("brc_legacy_refused", by the old-screen login)', sql(f"SELECT CONCAT(COUNT(*), '|', COALESCE(GROUP_CONCAT(DISTINCT user), '')) FROM tpd_audit WHERE id > {a0} AND action = 'brc_legacy_refused'") == '7|tcadmin', sql(f"SELECT CONCAT(COUNT(*), '|', COALESCE(GROUP_CONCAT(DISTINCT user), '')) FROM tpd_audit WHERE id > {a0} AND action = 'brc_legacy_refused'"))
        # flags set by the old screen before the guard existed (simulated by SQL on dummy rows) → warning; put back
        nl0 = sql("SELECT COALESCE(New_L_Code, 'NULL') FROM account_runing WHERE ID = 99980307")
        sql("UPDATE account_runing SET bank_rec = 1 WHERE ID = 99980306")
        sql("UPDATE account_runing SET New_L_Code = L_Code, L_Code = '1-6-112-10782' WHERE ID = 99980307 AND L_Code = '1-6-8-29904'")
        await ev(A, '__rv3.phaseFlags(2)')
        sql("UPDATE account_runing SET bank_rec = 0 WHERE ID = 99980306")
        sql(f"UPDATE account_runing SET L_Code = New_L_Code, New_L_Code = {'NULL' if nl0 == 'NULL' else repr(nl0)} WHERE ID = 99980307 AND L_Code = '1-6-112-10782'")
        await ev(A, '__rv3.phaseFlags(0)')
        chk('F the simulated flags put back exactly', sql("SELECT GROUP_CONCAT(CONCAT(ID, ':', L_Code, ':', COALESCE(New_L_Code, ''), ':', COALESCE(bank_rec, 0), ':', COALESCE(bankst_flag, 0)) ORDER BY ID, L_Code) FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999") == flags0, '')
        # pending amounts per day
        await ev(A, '__rv3.phasePending()')
        # the dummy posting entry, from the approved lines
        cv = await ev(A, '__rv3.phasePostLines()')
        lines = cv['lines']; edate = cv['entry_date']
        vals = ', '.join(f"(99980399, '{l['ledger_code']}', {l.get('dr') or 0}, {l.get('cr') or 0}, 'BANK CHARGES 01-09 (dummy posting, test bed)', '{edate}', 0, '', '', '', 'test', '{edate} 12:00:00', '')" for l in lines)
        ins = "INSERT INTO account_runing (ID, L_Code, Dr, Cr, Discription, Date, Recpt_No, Chk_No, payee, index_no, Cashier, Dt, inv_total) VALUES " + vals
        open(OUT + '/dummy_posting.sql', 'w').write(ins + ';\n')
        sql("DELETE FROM account_runing WHERE ID = 99980399"); sql(ins)
        await ev(A, '__rv3.phasePosted(99980399)')
        sql("UPDATE account_runing SET Discription = 'BANK CHARGES 01-09 (dummy posting, test bed, wording changed)' WHERE ID = 99980399")
        await ev(A, "__rv3.phaseChanged('wording')")
        sql("UPDATE account_runing SET Dr = IF(Dr > 0, 30, 0), Cr = IF(Cr > 0, 30, 0) WHERE ID = 99980399")
        await ev(A, "__rv3.phaseChanged('amount')")
        sql("UPDATE account_runing SET Dr = IF(Dr > 0, 25, 0), Cr = IF(Cr > 0, 25, 0) WHERE ID = 99980399")
        await ev(A, "__rv3.phaseChanged('amount-back')")
        sql("UPDATE account_runing SET Discription = 'BANK CHARGES 01-09 (dummy posting, test bed)' WHERE ID = 99980399")
        await ev(A, "__rv3.phaseChanged('all-back')")
        # dual role
        await ev(A, '__rv3.phaseDual()')
        l3 = await ev(A, '__rv3.facts.l3')
        # two DIFFERENT users at the same instant, in two sessions
        mk = lambda path, data: f"'{path}', {json.dumps(data)}"
        async def race(callA, callB, ua, ub):
            # both sessions signed in BEFORE the instant is chosen (a sign-in can be slowed by the login throttle)
            await A.evaluate(f"(async () => {{ await __rv3.login('{ua}'); await __rv3.token(); }})()")
            await B.evaluate(f"(async () => {{ await __rv3.login('{ub}'); await __rv3.token(); }})()")
            at = await A.evaluate('Date.now() + 2500')
            return await asyncio.gather(A.evaluate(f'__rv3.race({at}, {callA})'), B.evaluate(f'__rv3.race({at}, {callB})'))
        ra, rb = await race(f"'tcaccount', {mk('mark/create', {'account': V, 'side': 'bank', 'ref': l3, 'mark': 'timing', 'note': 'race: preparer (dummy)', 'expected_date': '2026-09-08'})}, 1",
                            f"'tcdual', {mk('mark/create', {'account': V, 'side': 'bank', 'ref': l3, 'mark': 'excluded', 'note': 'race: dual-role user (dummy)'})}, 1", 'tcaccount', 'tcdual')
        RACES.append(('two users classify the same statement line', [x['code'] for x in ra + rb], ra + rb))
        win = [x for x in ra + rb if x['code'] == 200]
        if win:
            pg = A if win[0] in ra else B
            await ev(pg, f"(async () => {{ await __rv3.login('{win[0]['user']}'); return __rv3.post('mark/clear', {{id: {win[0]['id']}, reason: 'race test cleared (dummy)'}}); }})()")
        rid = await ev(A, f"(async () => {{ await __rv3.login('tcaccount'); const [c, j] = await __rv3.post('day/prepare', {{account: {V}, date: '2026-09-03', note: 'race: two reviewers (dummy)'}}); return j.id; }})()")
        a_race = int(sql("SELECT COALESCE(MAX(id), 0) FROM tpd_audit"))
        ra, rb = await race(f"'tcpayappr', {mk('day/review', {'id': rid, 'decision': 'reviewed', 'note': 'race: reviewer'})}, 1", f"'tcdual', {mk('day/review', {'id': rid, 'decision': 'reviewed', 'note': 'race: dual-role user'})}, 1", 'tcpayappr', 'tcdual')
        RACES.append(('two users review the same daily record', [x['code'] for x in ra + rb], ra + rb))
        log = await ev(A, '__rv3.log'); facts = await ev(A, '__rv3.facts')
        await b.close()
    ov = lambda r: max(x['t0'] for x in r[2]) < min(x['t1'] for x in r[2])
    chk('T two DIFFERENT users (tcaccount, tcdual) classify the same line at the same instant: one 200, one 409', sorted(RACES[0][1]) == [200, 409], RACES[0])
    chk('T two DIFFERENT reviewers (tcpayappr, tcdual) review the same record at the same instant: one 200, one 409; one audit row', sorted(RACES[1][1]) == [200, 409] and sql(f"SELECT COUNT(*) FROM tpd_audit WHERE id > {a_race} AND action = 'brc_day_reviewed' AND ref_id = '{rid}'") == '1', RACES[1])
    chk('T in both races the two requests were in the server at the same time', ov(RACES[0]) and ov(RACES[1]), [[(x['t0'], x['t1']) for x in r[2]] for r in RACES])
    chk('the real ledger is unchanged (rows, sums, every row\'s reconciliation flags)', sql(T.LEDGER_SIG) == sig0, '')
    # 4. the reset (rollback of the data) and the comparison
    open(OUT + '/reset.sql', 'w', encoding='utf-8').write(r3_db.reset_sql(OUT + '/snap', DB))
    # the dual-role TEST login was created by ensure_dual() before the snapshot in this rehearsal: keep it for the cloud suites
    reset = open(OUT + '/reset.sql', encoding='utf-8').read()
    reset = '\n'.join(l for l in reset.split('\n') if not ('user_privileges' in l or 'user_acc_tbl' in l or 'FROM employee' in l or 'FROM group_profile' in l))
    open(OUT + '/reset_cloud.sql', 'w', encoding='utf-8').write(reset)
    print('reset:', mysql_file(OUT + '/reset_cloud.sql').strip().split('\n')[-1])
    snap1 = snapshot(cols, OUT + '/snap_after_reset')
    sum1 = checksums()
    diff = sorted(t for t in set(sum0) | set(sum1) if sum0.get(t) != sum1.get(t) and t not in SKIP)
    chk('reset: every part of the snapshot is back byte for byte (' + str(len(snap0)) + ' parts)', snap0 == snap1, [t for t in snap0 if snap0[t] != snap1.get(t)])
    chk('reset: every table of the database has the checksum it had before r3 (the audit trail, the activity log a_log and the session tables excepted)', not diff, diff)
    chk('reset: the real ledger is still unchanged', sql(T.LEDGER_SIG) == sig0, '')
    allr = log + extra
    for r in allr:
        print(('  ok   ' if r[0] == 'ok' else '  FAIL ') + r[1] + ('' if r[0] == 'ok' else '   ' + r[2]))
    print('%d/%d passed' % (sum(1 for r in allr if r[0] == 'ok'), len(allr)))
    json.dump({'log': allr, 'facts': facts, 'races': RACES, 'snapshot_md5': snap0, 'changed_tables_after_reset': diff}, open(HERE + '/results_rehearse_rv_r3.json', 'w'), indent=1)

asyncio.run(main())
