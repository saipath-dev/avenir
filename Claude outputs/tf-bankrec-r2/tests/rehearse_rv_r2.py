"""tf-bankrec-r2 — CLOUD REHEARSAL of the isolated-instance run: the test copy is loaded with the demonstration data
exactly as snapshotted from avenir_rv (12 Oct 2026; bank account ids 1-3 mapped to 4-6), upgraded with the same
upgrade SQL, and the same browser script (tests/rv_test_r2.js) is run in two browser sessions, races included."""
import asyncio, os, sys, json, re, subprocess
from playwright.async_api import async_playwright
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, S + '/paydesk'); sys.path.insert(0, HERE)
import test_bankrec as T
from test_bankrec import sql
K2 = HERE + '/kit/tf-bankrec-r2'; K1 = HERE + '/kit/tf-bankrec-r1'
SNAP = K2 + '/sql/demo-snapshot-r1-20261012'
MAPID = {'1': '4', '2': '5', '3': '6'}
ACCT_COLS = {'brc_account_map': ['bank_account_id'], 'brc_statement': ['bank_account_id'], 'brc_line': ['bank_account_id'], 'brc_match': ['bank_account_id'], 'brc_mark': ['bank_account_id'],
             'brc_transfer': ['from_account_id', 'to_account_id'], 'brc_correction': ['bank_account_id'], 'brc_day': ['bank_account_id'], 'tpd_bank_account': ['id']}
TOK = re.compile(r"NULL|'(?:[^'\\]|\\.)*'")

def remap(t, text):
    out = []
    for line in text.strip().split('\n'):
        m = re.match(r"INSERT INTO `(\w+)` \(([^)]*)\) VALUES \((.*)\);$", line, re.S)
        cols = [c.strip(' `') for c in m.group(2).split(',')]
        vals = TOK.findall(m.group(3))
        assert len(vals) == len(cols), (t, len(vals), len(cols))
        for c in ACCT_COLS.get(t, []):
            i = cols.index(c); v = vals[i].strip("'")
            vals[i] = "'" + MAPID.get(v, v) + "'"
        if t == 'account_posting':   # the cloud copy has its own auto-numbered IDs
            i = cols.index('ID'); cols.pop(i); vals.pop(i)
        out.append(f"INSERT INTO `{t}` ({', '.join('`' + c + '`' for c in cols)}) VALUES ({', '.join(vals)});")
    return '\n'.join(out)

def mysql_file(path):
    r = subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '--default-character-set=utf8mb4', '-uroot', 'avenir_test'], stdin=open(path), capture_output=True, text=True)
    if r.returncode:
        raise SystemExit(path + ': ' + r.stderr[:500])
    return r.stdout

def load():
    sql("DROP TABLE IF EXISTS brc_claim, brc_mark_event, brc_account_map, brc_statement, brc_line, brc_fx_rate, brc_match, brc_match_item, brc_mark, brc_transfer, brc_opening, brc_correction, brc_day")
    mysql_file(K1 + '/sql/setup-test-bed.sql')
    parts = ['brc_account_map', 'brc_statement', 'brc_line', 'brc_fx_rate', 'brc_match', 'brc_match_item', 'brc_mark', 'brc_transfer', 'brc_correction', 'brc_day']
    buf = ["DELETE FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999;", "DELETE FROM tpd_bank_account WHERE label LIKE 'TEST % account (dummy)';",
           "DELETE FROM account_posting WHERE L_Code IN ('1-6-8-29901','1-6-8-29902','1-6-8-29903','1-6-8-29904','2-9-99-29909');"]
    for t in parts + ['tpd_bank_account', 'account_runing', 'account_posting']:
        buf.append(remap(t, open(f'{SNAP}/d_{t}.sql', encoding='utf-8').read()))
    buf.append("INSERT INTO tpd_meta (k, v) VALUES ('brc_schema_version', '1') ON DUPLICATE KEY UPDATE v = '1';")
    open(HERE + '/r2work/rehearse_load.sql', 'w', encoding='utf-8').write('\n'.join(buf) + '\n')
    mysql_file(HERE + '/r2work/rehearse_load.sql')
    T.grant_menus()
    # the upgrade SQL of the kit, for this database name
    up = open(K2 + '/sql/upgrade-v2-test-bed.sql', encoding='utf-8').read().replace("'avenir_restore_verify_20261011'", "'avenir_test'")
    open(HERE + '/r2work/rehearse_upgrade.sql', 'w', encoding='utf-8').write(up)
    print('upgrade:', mysql_file(HERE + '/r2work/rehearse_upgrade.sql').strip().split('\n')[-1])
    # the verification account and its dummy rows (sql/dummy-data-r2-verification.sql)
    mysql_file(K2 + '/sql/dummy-data-r2-verification.sql')
    V = sql("SELECT id FROM tpd_bank_account WHERE label = 'TEST LKR verification account (dummy)'")
    return int(V)

async def main():
    V = load()
    sig0 = sql(T.LEDGER_SIG)
    ids = {'LKR': 4, 'USD': 5, 'JPY': 6, 'V': V}
    cfg = {'base': '/avenir/', 'pw': 'tc-pay-2026', 'prep': 'tcaccount', 'rev': 'tcpayappr', 'view': 'tcbankappr', 'depts': ['tchotelres', 'tcops'], 'ids': ids}
    js = open(K2 + '/tests/rv_test_r2.js', encoding='utf-8').read()
    async with async_playwright() as p:
        b = await p.chromium.launch()
        A = await (await b.new_context()).new_page(); B = await (await b.new_context()).new_page()
        await A.goto('http://127.0.0.1:8083/avenir/', wait_until='load'); await B.goto('http://localhost:8083/avenir/', wait_until='load')
        for pg in (A, B):
            await pg.evaluate('cfg => { window.__rvCfg = cfg; }', cfg); await pg.evaluate(js)
        ev = lambda pg, code: pg.evaluate(code)
        async def race(callA, callB):
            at = await A.evaluate('Date.now() + 2500')
            ra, rb = await asyncio.gather(A.evaluate(f'__rv2.race({at}, {callA})'), B.evaluate(f'__rv2.race({at}, {callB})'))
            return ra, rb
        RACES = []
        await ev(A, '__rv2.phaseA()'); await ev(A, '__rv2.phaseC()'); await ev(A, '__rv2.phaseB()')
        F = await ev(A, '__rv2.facts.B_ids')
        mk = lambda path, data: f"'{path}', {json.dumps(data)}"
        ra, rb = await race(f"'tcaccount', {mk('match/create', {'account': V, 'line_ids': [F['lK']], 'book_keys': ['99980306.1']})}, 5", f"'tcaccount', {mk('match/create', {'account': V, 'line_ids': [F['lK']], 'book_keys': ['99980306.1']})}, 5")
        RACES.append(('duplicate matches (2 sessions × 5)', [x['code'] for x in ra + rb], ra + rb))
        await ev(A, '__rv2.phaseB2()'); F = await ev(A, '__rv2.facts.B_ids')
        ra, rb = await race(f"'tcaccount', {mk('statement/accept', {'id': F['s2']})}, 1", f"'tcaccount', {mk('statement/accept', {'id': F['s2']})}, 1")
        RACES.append(('duplicate acceptance', [x['code'] for x in ra + rb], ra + rb))
        await ev(A, '__rv2.phaseB3()'); F = await ev(A, '__rv2.facts.B_ids')
        ra, rb = await race(f"'tcaccount', {mk('match/create', {'account': V, 'line_ids': [F['lL']], 'book_keys': ['99980307.1']})}, 1", f"'tcaccount', {mk('mark/create', {'account': V, 'side': 'book', 'ref': '99980307.1', 'mark': 'timing', 'note': 'race test classification'})}, 1")
        RACES.append(('match vs classify', [x['code'] for x in ra + rb], ra + rb))
        await ev(A, '__rv2.phaseB4()'); F = await ev(A, '__rv2.facts.B_ids')
        ra, rb = await race(f"'tcpayappr', {mk('day/review', {'id': F['r4'], 'decision': 'reviewed', 'note': 'session A'})}, 1", f"'tcpayappr', {mk('day/review', {'id': F['r4'], 'decision': 'reviewed', 'note': 'session B'})}, 1")
        RACES.append(('two reviewers at once', [x['code'] for x in ra + rb], ra + rb))
        await ev(A, '__rv2.phaseB5()')
        sql("UPDATE account_runing SET Discription = 'VOUCHER PAYMENT - TEST VENDOR L (dummy, edited)' WHERE ID = 99980307")
        await ev(A, '__rv2.phaseB6(true)')
        sql("UPDATE account_runing SET Discription = 'VOUCHER PAYMENT - TEST VENDOR L (dummy)' WHERE ID = 99980307")
        await ev(A, '__rv2.phaseB6(false)')
        await ev(A, '__rv2.phaseB7()'); F = await ev(A, '__rv2.facts.B_ids')
        # conflicting edit: both sessions saw version 0; B changes first, A's change on the old version is refused
        await ev(B, "__rv2.login('tcaccount')")
        cb = await ev(B, f"__rv2.post('line/edit', {{id: {F['l3']}, description: 'TEST INTEREST CREDIT SEPT', reason: 'wording from the paper statement', version: 0}})")
        ca = await ev(A, f"(async () => {{ await __rv2.login('tcaccount'); return __rv2.post('line/edit', {{id: {F['l3']}, description: 'TEST INTEREST', reason: 'my change on the old version', version: 0}}); }})()")
        RACES.append(('conflicting edits (version 0 twice)', [cb[0], ca[0]], [cb[1].get('message'), ca[1].get('message')]))
        s3b = await ev(B, "(async () => { const [c, j] = await __rv2.upload(__rvCfg.ids.V, [__rv2.HDR, ['06/09/2026', '06/09/2026', 'TEST INTEREST CREDIT', '', '', '1,000.00', '535,975.00 CR']], '534,975.00 CR', 'test_v_s3_copy.csv'); return j.id; })()")
        ra, rb = await race(f"'tcaccount', {mk('statement/accept', {'id': F['s3']})}, 1", f"'tcaccount', {mk('statement/accept', {'id': s3b})}, 1")
        RACES.append(('two drafts of the same period accepted at once', [x['code'] for x in ra + rb], ra + rb))
        loser = s3b if ra[0]['code'] == 200 else F['s3']
        await ev(A, f"__rv2.post('statement/void', {{id: {loser}, reason: 'duplicate draft of the race test'}})")
        # conflicting rate edits (USD 5 Sep) after the USD record was reviewed in the demonstration
        v0 = await ev(A, "(async () => { await __rv2.login('tcaccount'); const [c, j] = await __rv2.get('api/rates', {from: '2026-09-05', to: '2026-09-05'}); return +j.rates.find(r => r.currency === 'USD').version; })()")
        rb_ = await ev(B, f"__rv2.post('rate/save', {{currency: 'USD', rate_date: '2026-09-05', rate: '302.10', source: 'TEST rate (dummy)', note: 'bank board rate corrected (test)', version: {v0}}})")
        ra_ = await ev(A, f"__rv2.post('rate/save', {{currency: 'USD', rate_date: '2026-09-05', rate: '302.20', source: 'TEST rate (dummy)', note: 'my change on the old version', version: {v0}}})")
        RACES.append(('conflicting rate edits', [rb_[0], ra_[0]], [rb_[1].get('message'), ra_[1].get('message')]))
        du = await ev(A, f"__rv2.day({ids['USD']}, '2026-09-05')")
        rateflag = du['review']['state']
        rr = await ev(B, f"__rv2.post('rate/save', {{currency: 'USD', rate_date: '2026-09-05', rate: '302.00', source: 'TEST rate (dummy)', note: 'back to the reviewed rate (test)', version: {v0 + 1}}})")
        du2 = await ev(A, f"__rv2.day({ids['USD']}, '2026-09-05')")
        await ev(A, '__rv2.phaseB8()')
        log = await ev(A, '__rv2.log'); facts = await ev(A, '__rv2.facts')
        await b.close()
    extra = []
    def chk(n, c, d=''):
        extra.append(['ok' if c else 'FAIL', n, '' if c else json.dumps(d)[:300]])
    chk('race: duplicate matches → exactly one 200, nine 409', sorted(RACES[0][1]) == [200] + [409] * 9, RACES[0][1])
    chk('race: duplicate acceptance → one 200, one 409', sorted(RACES[1][1]) == [200, 409], RACES[1][1])
    chk('race: match vs classify → exactly one succeeds', sorted(RACES[2][1]) == [200, 409], RACES[2][1])
    chk('race: two reviewers → one 200, one 409', sorted(RACES[3][1]) == [200, 409], RACES[3][1])
    chk('conflicting edits → the change on the old version refused (200, 409)', RACES[4][1] == [200, 409], RACES[4][1])
    chk('race: two drafts → one accepted, one refused', sorted(RACES[5][1]) == [200, 409], RACES[5][1])
    chk('conflicting rate edits → the change on the old version refused; the reviewed USD record flagged; current again when put back', RACES[6][1] == [200, 409] and rateflag == 'reviewed_outdated' and rr[0] == 200 and du2['review']['state'] == 'reviewed', [RACES[6][1], rateflag, du2['review']['state']])
    chk('the real ledger is unchanged', sql(T.LEDGER_SIG) == sig0, '')
    allr = log + extra
    for r in allr:
        print(('  ok   ' if r[0] == 'ok' else '  FAIL ') + r[1] + ('' if r[0] == 'ok' else '   ' + r[2]))
    print('%d/%d passed' % (sum(1 for r in allr if r[0] == 'ok'), len(allr)))
    json.dump({'log': allr, 'facts': facts, 'races': RACES}, open(HERE + '/results_rehearse_rv_r2.json', 'w'), indent=1)

asyncio.run(main())
