r"""tf-bankrec-r2 — end-to-end test of the Daily Bank Reconciliation r2 with DUMMY data only (cloud test copy).

Part A  the r1 flow with the r2 rules (three dummy accounts TEST LKR / USD / JPY, ledger rows 99980001-99980299).
Part B  the r2 verification on a fourth dummy account "TEST LKR verification account (dummy)" (ledger 1-6-8-29904, rows
        99980301-99980399): D9 timing items, D10 opening outstanding items over several days, partial clearing, undo and
        reopening, two users at once (true parallel requests on a multi-worker server), changes after review, pending
        postings and the verification of a posted entry, exchange-rate explanations, permissions and four eyes.
The real ledger is checked before and after: rows outside the dummy ID range must be byte-for-byte the same.
"""
import os, sys, re, json, subprocess, threading, time
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, S + '/paydesk'); sys.path.insert(0, HERE)
from api import Client, ACC
import test_bankrec as T
from test_bankrec import sql, LEDGER_SIG, csv_bytes, LKR_ROWS

RES = []
def check(name, ok, detail=''):
    RES.append({'name': name, 'ok': bool(ok), 'detail': str(detail)[:500]})
    print(('  ok   ' if ok else '  FAIL ') + name + ('' if ok else '   ' + str(detail)[:500]))
    return ok

MW = 'http://127.0.0.1:8083/avenir/'   # a multi-worker PHP server on the same test copy, for the parallel requests

class BR(T.BR):
    def __init__(self, who, base=None):
        self.c = Client(who, base=base); self.who = who; self.base = self.c.base + 'accounts/daily_bank_rec/'; self.tok = None; self.name = 'csrf_test_name'

def ensure_users():
    """A second preparer and a second reviewer (same groups as the first ones), test copy only."""
    h = subprocess.run(['php', '-r', 'echo password_hash("tc-pay-2026", PASSWORD_DEFAULT);'], capture_output=True, text=True).stdout
    for uid, user, g in [(918, 'tcaccount2', 908), (917, 'tcpayappr2', 907)]:
        if sql(f"SELECT COUNT(*) FROM user_acc_tbl WHERE user = '{user}'") == '0':
            sql(f"INSERT INTO user_acc_tbl (id, emp_id, user, pass, group_id, access, login_type, user_role) VALUES ({uid}, {g}, '{user}', '{h}', {g}, 1, 1, 0)")

def start_mw():
    subprocess.run(['bash', '-c', f'curl -s -o /dev/null http://127.0.0.1:8083/ || (cd {S} && PHP_CLI_SERVER_WORKERS=8 setsid nohup php -S 127.0.0.1:8083 {S}/router_tmr.php >> {S}/php8083.log 2>&1 < /dev/null & sleep 3)'])

RACES = []
def race(calls):
    """Fire every (user, path, data) at the same instant from its own thread; returns [(code, json)] in order. Records
    when each request started and ended, to show that requests of different users were in the server at the same time."""
    bar = threading.Barrier(len(calls)); out = [None] * len(calls); tm = [None] * len(calls)
    for u, _, _ in calls:
        u.token()
    def run(i, u, path, data):
        bar.wait()
        t0 = time.monotonic(); out[i] = u.post(path, data); tm[i] = (t0, time.monotonic(), u.who)
    ths = [threading.Thread(target=run, args=(i, u, p, d)) for i, (u, p, d) in enumerate(calls)]
    [t.start() for t in ths]; [t.join() for t in ths]
    users = sorted(set(x[2] for x in tm))
    first = {w: min(x for x in tm if x[2] == w) for w in users}   # each user's first request
    overlap = max(f[0] for f in first.values()) < min(f[1] for f in first.values())
    RACES.append({'path': calls[0][1], 'users': users, 'requests': len(calls), 'first_requests_overlapped': overlap,
                  'span_ms': round((max(x[1] for x in tm) - min(x[0] for x in tm)) * 1000), 'codes': [o[0] for o in out]})
    return out

def day(u, acct, d):
    c, j = u.get('api/day', account=acct, date=d)
    return j.get('day', {}) if c == 200 else {'status_code': 'HTTP %s' % c, 'raw': j}

V_ROWS = [
    (99980301, 500000, 0, '2026-08-01', 'TEST OPENING V (dummy)', 0, '', '', ''),
    (99980302, 0, 20000, '2026-08-28', 'TEST CHEQUE 000201 (dummy)', 0, '000201', '', ''),
    (99980303, 0, 30000, '2026-08-29', 'TEST CHEQUE 000202 (dummy)', 0, '000202', '', ''),
    (99980304, 90000, 0, '2026-09-02', 'RECEIPT - TEST AGENT XYZ (dummy)', 0, '', 'TEST AGENT XYZ', ''),
    (99980305, 0, 15000, '2026-09-02', 'VOUCHER PAYMENT - TEST VENDOR J (dummy)', 90011, '', 'TEST VENDOR J', '9910'),
    (99980306, 0, 8000, '2026-09-03', 'VOUCHER PAYMENT - TEST VENDOR K (dummy)', 90012, '', 'TEST VENDOR K', '9911'),
    (99980307, 0, 12000, '2026-09-04', 'VOUCHER PAYMENT - TEST VENDOR L (dummy)', 90013, '', 'TEST VENDOR L', '9912'),
]
S1 = [['Txn Date', 'Value Date', 'Description', 'Cheque No', 'Debit', 'Credit', 'Balance'],
      ['01/09/2026', '01/09/2026', 'IB CEFT CHGS', '', '25.00', '', '499,975.00 CR'],
      ['03/09/2026', '03/09/2026', 'CHQ 000201 TEST CHEQUE', '000201', '20,000.00', '', '479,975.00 CR'],
      ['03/09/2026', '03/09/2026', 'CEFT RECEIPT - TEST AGENT XYZ', '', '', '50,000.00', '529,975.00 CR'],
      ['03/09/2026', '03/09/2026', 'AH 9911 - TEST VENDOR K - 90012', '', '8,000.00', '', '521,975.00 CR']]
S2 = [['Txn Date', 'Value Date', 'Description', 'Cheque No', 'Debit', 'Credit', 'Balance'],
      ['04/09/2026', '04/09/2026', 'AH 9910 - TEST VENDOR J - 90011', '', '15,000.00', '', '506,975.00 CR'],
      ['04/09/2026', '04/09/2026', 'AH 9912 - TEST VENDOR L - 90013', '', '12,000.00', '', '494,975.00 CR'],
      ['05/09/2026', '05/09/2026', 'CEFT RECEIPT - TEST AGENT XYZ', '', '', '40,000.00', '534,975.00 CR']]
S3 = [['Txn Date', 'Value Date', 'Description', 'Cheque No', 'Debit', 'Credit', 'Balance'],
      ['06/09/2026', '06/09/2026', 'TEST INTEREST CREDIT', '', '', '1,000.00', '535,975.00 CR']]

def setup_v(adm):
    if sql("SELECT COUNT(*) FROM account_posting WHERE L_Code = '1-6-8-29904'") == '0':
        sql("INSERT INTO account_posting (P_Code, PName, B_Code, T_Code, H_Code, L_Code) VALUES (29904, 'TEST Bank LKR verification (dummy)', 1, 6, 8, '1-6-8-29904')")
    sql("DELETE FROM account_runing WHERE ID BETWEEN 99980300 AND 99980399")
    vals = ','.join(f"({i}, '1-6-8-29904', {dr}, {cr}, '{desc}', '{d}', {pv}, '{chk}', '{payee}', '{tour}', 'test', '2026-09-06 10:00:00', '')" for i, dr, cr, d, desc, pv, chk, payee, tour in V_ROWS)
    sql(f"INSERT INTO account_runing (ID, L_Code, Dr, Cr, Discription, Date, Recpt_No, Chk_No, payee, index_no, Cashier, Dt, inv_total) VALUES {vals}")
    label = 'TEST LKR verification account (dummy)'
    have = sql(f"SELECT id FROM tpd_bank_account WHERE label = '{label}'")
    if have:
        return int(have.split()[0])
    r = adm.c.s.post(adm.c.base + 'master_files/bank_accounts/save', data={'label': label, 'bank_name': 'TEST BANK (dummy)', 'account_name': 'Avenir Holidays TEST', 'account_no': 'TEST-LKR-0004', 'currency': 'LKR',
        'ledger_code': '1-6-8-29904', 'active': '0', 'external_display': 'masked', 'display_digits': '4', 'note': 'dummy account for the reconciliation r2 tests', adm.name: adm.token()}, headers={'X-Requested-With': 'XMLHttpRequest'})
    return int(r.json().get('id'))

def upload(u, acct, rows, opening, name):
    return u.post('statement/save', {'account': acct, 'source': 'upload', 'opening_balance': opening, 'currency': 'LKR'}, files={'file': (name, csv_bytes(rows), 'text/csv')})

def part_a(adm, prep, rev, view, a0):
    # ---------------- setup (schema 2)
    code, j = adm.post('setup')
    check('A set-up by the System Administrator: schema version 2 (tables, columns, claims)', code == 200 and sql("SELECT v FROM tpd_meta WHERE k='brc_schema_version'") == '2' and sql("SHOW TABLES LIKE 'brc_claim'") == 'brc_claim', (code, j.get('message')))
    code, j = adm.post('setup')
    check('A set-up again changes nothing (idempotent)', code == 200 and 'nothing to do' in j.get('message', ''), j.get('message'))
    for t in ['brc_mark_event', 'brc_claim', 'brc_match_item', 'brc_match', 'brc_mark', 'brc_transfer', 'brc_correction', 'brc_opening', 'brc_day', 'brc_line', 'brc_statement', 'brc_fx_rate', 'brc_account_map']:
        sql(f"DELETE FROM {t}")
    T.grant_menus()
    ids = T.setup_dummy(adm)
    for u in (prep, rev, view):
        u.page()
    # ---------------- permissions
    for who in ['requester', 'ops', 'transh', 'hotelres', 'quote', 'hotel', 'transport', 'mgmt']:
        u = BR(who); r = u.page()
        c2, _ = u.get('api/accounts'); c3, _ = u.post('mapping/propose', {'account': ids['LKR'], 'rec_start': '2026-09-01'})
        c4, _ = u.get('api/opening_candidates', account=ids['LKR']); c5, _ = u.post('match/explain', {'id': 1, 'note': 'not allowed'})
        check(f'A {who}: no access (page refused; data, opening candidates, changes and explanations 403)', r.status_code in (302, 303) and c2 == 403 and c3 == 403 and c4 == 403 and c5 == 403, (r.status_code, c2, c3, c4, c5))
    c, j = view.post('mapping/propose', {'account': ids['LKR'], 'rec_start': '2026-09-01'})
    check('A view-only (bank authoriser) cannot propose (403)', c == 403, c)
    c, j = prep.post('mapping/propose', {'account': ids['LKR'], 'rec_start': '2026-09-01'}, csrf=False)
    check('A a change without the CSRF token is refused (403)', c == 403, c)
    maps = {}
    for cur in ('LKR', 'USD'):
        c, j = prep.post('mapping/propose', {'account': ids[cur], 'rec_start': '2026-09-01', 'note': 'test'}); maps[cur] = j.get('id')
    c, j = adm.post('mapping/propose', {'account': ids['JPY'], 'rec_start': '2026-09-01'}); maps['JPY'] = j.get('id')
    c, j = adm.post('mapping/decide', {'map_id': maps['JPY'], 'decision': 'confirm'})
    check('A four eyes: the proposer cannot confirm their own mapping, even as administrator (403)', c == 403, c)
    for cur in ('LKR', 'USD', 'JPY'):
        c, j = rev.post('mapping/decide', {'map_id': maps[cur], 'decision': 'confirm', 'note': 'checked'})
    check('A the reviewer confirms the three mappings', sql("SELECT COUNT(*) FROM brc_account_map WHERE status='confirmed'") == '3', '')
    for d, r in [('2026-09-01', '300.00'), ('2026-09-02', '300.50'), ('2026-09-03', '301.00'), ('2026-09-04', '301.00'), ('2026-09-05', '302.00')]:
        prep.post('rate/save', {'currency': 'USD', 'rate_date': d, 'rate': r, 'source': 'TEST rate (dummy)'})
    for d, r in [('2026-09-01', '2.00'), ('2026-09-04', '2.02'), ('2026-09-05', '2.03')]:
        prep.post('rate/save', {'currency': 'JPY', 'rate_date': d, 'rate': r, 'source': 'TEST rate (dummy)'})
    c, j = prep.post('rate/save', {'currency': 'USD', 'rate_date': '2026-09-04', 'rate': '301.50', 'source': 'TEST rate (dummy)'})
    c2, j2 = prep.post('rate/save', {'currency': 'USD', 'rate_date': '2026-09-04', 'rate': '301.50', 'source': 'TEST rate (dummy)', 'note': 'corrected test rate'})
    ver = sql("SELECT version FROM brc_fx_rate WHERE currency='USD' AND rate_date='2026-09-04'")
    c3, j3 = prep.post('rate/save', {'currency': 'USD', 'rate_date': '2026-09-04', 'rate': '301.50', 'source': 'TEST rate (dummy)', 'note': 'corrected test rate', 'version': ver})
    check('A changing a rate needs a reason (400) and the version it changes (409 without); then saved with old and new value', c == 400 and c2 == 409 and c3 == 200 and sql("SELECT CONCAT(rate,'|',version) FROM brc_fx_rate WHERE currency='USD' AND rate_date='2026-09-04'") == '301.500000|1', (c, c2, c3, j3.get('message')))
    # ---------------- LKR statement
    broken = [list(r) for r in LKR_ROWS]; broken[4][6] = '749,970.00 CR'
    c, j = upload(prep, ids['LKR'], broken, '1,000,000.00 CR', 'combank_test_sep.csv'); sA = j.get('id')
    line4 = sql(f"SELECT id FROM brc_line WHERE statement_id = {sA} AND line_no = 4")
    c, j = prep.post('line/edit', {'id': line4, 'balance': '749,975.00 CR', 'reason': 'typed wrongly in the test file'})
    check('A a draft line change without the version it changes is refused (400)', c == 400, (c, j.get('message')))
    c, j = prep.post('line/edit', {'id': line4, 'balance': '749,975.00 CR', 'reason': 'typed wrongly in the test file', 'version': 0})
    check('A the draft line is corrected (version 0 → 1); the imported value is kept', c == 200 and sql(f"SELECT CONCAT(balance,'|',edited,'|',version,'|',JSON_UNQUOTE(JSON_EXTRACT(orig_json, '$.balance'))) FROM brc_line WHERE id = {line4}") == '749975.00|1|1|749970.00', (c, j.get('message')))
    c, j = prep.post('statement/accept', {'id': sA})
    check('A the corrected LKR statement is accepted (closing 3,893,106.00)', c == 200 and sql(f"SELECT CONCAT(status,'|',closing_balance) FROM brc_statement WHERE id = {sA}") == 'accepted|3893106.00', j.get('message'))
    usd = [{'date': '2026-09-02', 'description': 'TEST AGENT REMITTANCE ABC TRAVEL', 'money_in': '5,000.00', 'balance': '55,000.00'}, {'date': '2026-09-03', 'description': 'TRF TO TEST-LKR-0001', 'money_out': '10,000.00', 'balance': '45,000.00'},
           {'date': '2026-09-05', 'description': 'TRF CHGS USD', 'money_out': '15.00', 'balance': '44,985.00'}]
    c, j = prep.post('statement/save', {'account': ids['USD'], 'source': 'manual', 'opening_balance': '50,000.00', 'closing_balance': '44,985.00', 'period_from': '2026-09-01', 'period_to': '2026-09-05', 'lines': usd}); sU = j.get('id'); prep.post('statement/accept', {'id': sU})
    jpy = [['Date', 'Narrative', 'Amount'], ['2026-09-04', 'TEST JP AGENT YAMADA TRAVEL', '200000']]
    c, j = prep.post('statement/save', {'account': ids['JPY'], 'source': 'upload', 'opening_balance': '1000000', 'closing_balance': '1,200,000', 'period_from': '2026-09-01', 'period_to': '2026-09-05', 'date_format': 'Y-m-d'}, files={'file': ('jpy_test.csv', csv_bytes(jpy), 'text/csv')}); sJ = j.get('id'); prep.post('statement/accept', {'id': sJ})
    check('A USD (typed) and JPY (uploaded) statements accepted', sql(f"SELECT GROUP_CONCAT(status) FROM brc_statement WHERE id IN ({sU},{sJ})") == 'accepted,accepted', '')
    # ---------------- LKR matching with D10
    c, j = prep.get('api/suggest', account=ids['LKR'], **{'from': '2026-09-01', 'to': '2026-09-05'})
    by = {s['line']['description']: s for s in j.get('suggestions', [])}
    chq = by.get('CHQ 000123 TEST CHEQUE', {})
    check('A the cheque from before the first day is suggested, marked "booked before the first day — not an opening item yet"', chq.get('book', [{}])[0].get('key') == '99980002.1' and any('not an opening item yet' in w for w in chq.get('why', [])), chq.get('why'))
    for desc in ['AH 9901 - TEST HOTEL A - 90001', 'AH 9902 - TEST SUPPLIER B - 90002', 'CHQ 000123 TEST CHEQUE', 'TRF FROM TEST-USD-0002']:
        c, j = prep.post('match/create', {'account': ids['LKR'], 'line_ids': [by[desc]['line']['id']], 'book_keys': [b['key'] for b in by[desc]['book']], 'method': 'suggested'})
        if desc.startswith('CHQ'):
            check('A matching the cheque warns that clearing it does not explain the opening difference until it is an accepted opening item', c == 200 and 'not accepted opening items' in j.get('message', ''), j.get('message'))
    lid = lambda desc: sql(f"SELECT id FROM brc_line WHERE statement_id = {sA} AND description = '{desc}'")
    c, j = prep.post('match/create', {'account': ids['LKR'], 'line_ids': [lid('AH 9905 - TEST JEEP D - 90005')], 'book_keys': ['99980010.1'], 'diff_reason': 'rounding'})
    c, j = prep.post('match/create', {'account': ids['LKR'], 'line_ids': [lid('AH 9903 - TEST GUIDE C - 90003')], 'book_keys': ['99980007.1'], 'note': 'one of the two identical ledger rows'})
    prep.post('mark/create', {'account': ids['LKR'], 'side': 'book', 'ref': '99980008.1', 'mark': 'duplicate', 'related': '99980007.1', 'note': 'same PV 90003 and amount as 99980007 — to investigate'})
    prep.post('mark/create', {'account': ids['LKR'], 'side': 'book', 'ref': '99980009.1', 'mark': 'timing', 'expected_date': '2026-09-08', 'note': 'PV 90004 paid after 5 Sep'})
    d = day(view, ids['LKR'], '2026-09-05'); r = d.get('reconciliation', {})
    check('A D10: after the cheque of 30 Aug cleared, the opening difference 150,000.00 is still NOT explained (not an accepted opening item) → insufficient data',
          d['status_code'] == 'insufficient' and r['opening_remaining'] == '150000.00' and r['pre_start_cleared_not_accepted'] == '-150000.00' and r['unexplained_lkr'] == '0.00' and any('not an accepted opening item' in x for x in d['reasons']), (r, d['reasons'][:2]))
    c, j = prep.post('opening/propose', {'account': ids['LKR'], 'book_key': '99980002.1', 'evidence': 'cheque 000123 issued 30 Aug, presented 2 Sep (test)'}); oA = j.get('id')
    c2, j2 = prep.post('opening/decide', {'id': oA, 'decision': 'accept'})
    c3, j3 = rev.post('opening/decide', {'id': oA, 'decision': 'accept', 'note': 'checked against the statement'})
    check('A the cheque is proposed as an opening outstanding item (+150,000.00); the preparer cannot accept it (403); the reviewer accepts it', c == 200 and sql(f"SELECT CONCAT(amount,'|',source,'|',status) FROM brc_opening WHERE id = {oA}") == '150000.00|ledger|accepted' and c2 == 403 and c3 == 200, (j.get('message'), c2, c3))
    d = day(view, ids['LKR'], '2026-09-05'); r = d['reconciliation']
    check('A 5 Sep: opening difference explained by the accepted item (remaining 0.00); the item shows as cleared on 2 Sep (−150,000.00 cleared, 0.00 outstanding)', r['opening_remaining'] == '0.00' and r['opening_items_cleared'] == '-150000.00' and r['opening_items_outstanding'] == '0.00' and [o['state'] for o in d['items']['opening']] == ['cleared'], (r, d['items']['opening']))
    d1 = day(view, ids['LKR'], '2026-09-01'); r1 = d1['reconciliation']
    check('A 1 Sep (first day): the same item is outstanding (150,000.00); the opening difference is explained; day 1 reconciles apart from the 25.00 charge', r1['opening_remaining'] == '0.00' and r1['opening_items_outstanding'] == '150000.00' and [o['state'] for o in d1['items']['opening']] == ['outstanding'] and d1['status_code'] == 'open_items' and d1['counts']['unexplained_items'] == 1, (r1, d1['status_label'], d1['counts']))
    # ---------------- corrections (never posted) and the pending-posting status
    charge_ids = sql(f"SELECT GROUP_CONCAT(id) FROM brc_line WHERE statement_id = {sA} AND category = 'charge'").split(',')
    c, j = prep.post('correction/charges', {'account': ids['LKR'], 'line_ids': charge_ids}); cC = j.get('id')
    c2, j2 = prep.post('correction/propose', {'account': ids['LKR'], 'kind': 'unbooked_receipt', 'description': 'Cash deposit 4 Sep not in the ledger (test)', 'entry_date': '2026-09-04',
        'lines': [{'ledger_code': '1-6-8-29901', 'dr': '235000', 'cr': ''}, {'ledger_code': '2-9-99-29909', 'dr': '', 'cr': '235000'}], 'line_ids': [lid('CASH DEPOSIT')]}); cR = j2.get('id')
    c3, j3 = prep.post('correction/propose', {'account': ids['LKR'], 'kind': 'unbooked_receipt', 'description': 'the same deposit again (test)', 'entry_date': '2026-09-04',
        'lines': [{'ledger_code': '1-6-8-29901', 'dr': '235000', 'cr': ''}, {'ledger_code': '2-9-99-29909', 'dr': '', 'cr': '235000'}], 'line_ids': [lid('CASH DEPOSIT')]})
    check('A corrections proposed (charges 4,550.00; deposit 235,000.00); a second proposal for the same line is refused (409)', c == 200 and c2 == 200 and c3 == 409, (j.get('message'), j2.get('message'), j3.get('message')))
    d = day(view, ids['LKR'], '2026-09-05')
    check('A 5 Sep with corrections only PROPOSED: "All items explained — ledger not yet corrected — 2 correction(s) awaiting approval"', d['status_code'] == 'explained_pending' and 'ledger not yet corrected' in d['status_label'] and d['pending_postings']['proposed']['count'] == 2 and d['pending_postings']['proposed']['total'] == '239550.00', (d['status_label'], d['pending_postings']['summary']))
    for cid in (cC, cR):
        rev.post('correction/decide', {'id': cid, 'decision': 'approve', 'note': 'test approval'})
    d = day(view, ids['LKR'], '2026-09-05'); r = d['reconciliation']
    check('A after APPROVAL (not posted): still "ledger not yet corrected — 2 approved but not posted (239,550.00)"; never "Reconciled"', d['status_code'] == 'explained_pending' and d['pending_postings']['approved']['count'] == 2 and '2 approved but not posted (239,550.00)' in d['status_label'] and not d['status_label'].startswith('Reconciled'), d['status_label'])
    check('A the difference 345,451.00 is explained to 0.00 (230,450.00 + 115,000.00 + rounding 1.00)', r['difference_lkr'] == '345451.00' and r['bank_items_not_in_book'] == '230450.00' and r['book_items_not_on_statement'] == '115000.00' and r['match_differences'] == '1.00' and r['unexplained_lkr'] == '0.00', r)
    c, j = prep.get('api/corrections', account=ids['LKR'])
    st = {x['id']: (x['state'], x['ledger_corrected']) for x in j['corrections']}
    check('A the corrections list says where each stands: "Approved — not posted (ledger not corrected)"', st.get(cC) == ('approved', False) and st.get(cR) == ('approved', False) and all('ledger not corrected' in x['state_label'] for x in j['corrections'] if x['id'] in (cC, cR)), st)
    # ---------------- transfers and FX explanations
    c, j = prep.get('api/rates', **{'from': '2026-09-01', 'to': '2026-09-05'}); ts = j.get('transfer_suggestions', [])
    c, j = prep.post('transfer/create', {'from_line': ts[0]['from']['line_id'], 'to_line': ts[0]['to']['line_id'], 'note': 'test transfer'})
    c2, j2 = prep.post('transfer/create', {'from_line': ts[0]['from']['line_id'], 'to_line': ts[0]['to']['line_id'], 'note': 'again'})
    check('A the USD → LKR transfer is recorded with the day rate 301 and difference −10,000.00; recording it twice is refused (409)', c == 200 and '-10,000.00' in j.get('message', '') and c2 == 409, (j.get('message'), c2))
    c, j = prep.get('api/suggest', account=ids['USD'], **{'from': '2026-09-01', 'to': '2026-09-05'})
    sg = {s['line']['description']: s for s in j.get('suggestions', [])}
    rem = sg.get('TEST AGENT REMITTANCE ABC TRAVEL', {})
    check('A USD: the remittance is SUGGESTED within the 1 % tolerance, but shown with its difference 2,500.00 "to explain"', rem.get('needs_explanation') is True and rem.get('difference_lkr') == '2500.00', rem)
    ul = lambda desc: sql(f"SELECT id FROM brc_line WHERE statement_id = {sU} AND description = '{desc}'")
    c, j = prep.post('match/create', {'account': ids['USD'], 'line_ids': [ul('TEST AGENT REMITTANCE ABC TRAVEL')], 'book_keys': ['99980102.1'], 'method': 'suggested', 'diff_reason': 'exchange rate'})
    check('A an exchange-rate difference without an explanation is refused (400): the tolerance never explains it', c == 400 and 'Explain' in j.get('message', ''), j.get('message'))
    c, j = prep.post('match/create', {'account': ids['USD'], 'line_ids': [ul('TEST AGENT REMITTANCE ABC TRAVEL')], 'book_keys': ['99980102.1'], 'method': 'suggested', 'diff_reason': 'exchange rate', 'note': 'booked at 300.00; credited at the 2 Sep rate 300.50'})
    c2, j2 = prep.post('match/create', {'account': ids['USD'], 'line_ids': [ul('TRF TO TEST-LKR-0001')], 'book_keys': ['99980103.1'], 'diff_reason': 'exchange rate', 'note': 'USD 10,000 sold at 300.00 (bank); valued at the day rate 301.00'})
    check('A USD lines matched with explained exchange-rate differences (+2,500.00, −10,000.00)', c == 200 and c2 == 200 and sql(f"SELECT GROUP_CONCAT(CONCAT(difference_lkr,':',diff_note IS NOT NULL) ORDER BY id) FROM brc_match WHERE bank_account_id = {ids['USD']} AND status='active'") == '2500.00:1,-10000.00:1', (j.get('message'), j2.get('message')))
    c, j = prep.post('correction/charges', {'account': ids['USD'], 'line_ids': [ul('TRF CHGS USD')]}); cU = j.get('id'); rev.post('correction/decide', {'id': cU, 'decision': 'approve'})
    d = day(view, ids['USD'], '2026-09-05'); r = d['reconciliation']
    check('A USD 5 Sep: differences visible (−7,500.00 in matches, 2 explained), revaluation 97,500.00 at 302 (TEST rate) shown; charge approved, not posted', r['match_differences'] == '-7500.00' and all(x['explained'] for x in d['items']['differences']) and len(d['items']['differences']) == 2 and r['revaluation'] == '97500.00' and r['revaluation_rate_source'] == 'TEST rate (dummy)' and d['status_code'] == 'explained_pending' and r['unexplained_lkr'] == '0.00', (r, d['status_label']))
    mU = sql(f"SELECT id FROM brc_match WHERE bank_account_id = {ids['USD']} AND status='active' ORDER BY id LIMIT 1")
    sql(f"UPDATE brc_match SET diff_note = NULL WHERE id = {mU}")   # as a match recorded under r1 without an explanation
    d = day(view, ids['USD'], '2026-09-05')
    check('A a recorded exchange-rate difference without an explanation (as from r1) is shown "to explain" → items to explain', d['status_code'] == 'open_items' and d['counts']['differences_unexplained'] == 1, (d['status_label'], d['counts']))
    c, j = view.post('match/explain', {'id': mU, 'note': 'credited at the 2 Sep rate'})
    c2, j2 = prep.post('match/explain', {'id': mU, 'diff_reason': 'exchange rate', 'note': 'booked at 300.00; credited at the 2 Sep rate 300.50'})
    d = day(view, ids['USD'], '2026-09-05')
    check('A the view-only user cannot explain it (403); the preparer explains it → explained (pending the approved charge)', c == 403 and c2 == 200 and d['status_code'] == 'explained_pending', (c, c2, d['status_label']))
    c, j = prep.post('day/prepare', {'account': ids['USD'], 'date': '2026-09-05', 'note': 'test'})
    c2, j2 = prep.post('day/prepare', {'account': ids['USD'], 'date': '2026-09-05', 'note': 'test', 'fx_note': 'unrealised difference on the USD balance at 302.00; month-end entry by Accounts'}); dU = j2.get('id')
    check('A the USD daily record needs an explanation of the revaluation (400 without); prepared with it', c == 400 and 'revaluation' in j.get('message', '') and c2 == 200, (j.get('message'), j2.get('message')))
    c, j = rev.post('day/review', {'id': dU, 'decision': 'reviewed', 'note': 'ok'})
    check('A USD record reviewed → "Reviewed — ledger not yet corrected" (the charge is approved but not posted)', c == 200 and day(view, ids['USD'], '2026-09-05')['status_code'] == 'reviewed_pending', j.get('message'))
    c, j = prep.post('match/create', {'account': ids['JPY'], 'line_ids': [sql(f"SELECT id FROM brc_line WHERE statement_id = {sJ}")], 'book_keys': ['99980202.1']})
    d = day(view, ids['JPY'], '2026-09-05')
    check('A JPY 5 Sep: matched exactly; revaluation 32,000.00 shown; status explained (nothing pending)', c == 200 and d['reconciliation']['revaluation'] == '32000.00' and d['status_code'] == 'explained', d['status_label'])
    # ---------------- LKR daily record, four eyes
    c, j = prep.post('day/prepare', {'account': ids['LKR'], 'date': '2026-09-05', 'note': 'test'}); d1 = j.get('id')
    c2, _ = prep.post('day/review', {'id': d1, 'decision': 'reviewed'})
    c3, j3 = rev.post('day/review', {'id': d1, 'decision': 'returned', 'note': 'please re-check the charges (test)'})
    c, j = prep.post('day/prepare', {'account': ids['LKR'], 'date': '2026-09-05', 'note': 'charges re-checked'}); d2 = j.get('id')
    c4, j4 = rev.post('day/review', {'id': d1, 'decision': 'reviewed'})
    c5, j5 = rev.post('day/review', {'id': d2, 'decision': 'reviewed', 'note': 'ok'})
    d = day(view, ids['LKR'], '2026-09-05')
    snap = json.loads(sql(f"SELECT pending_json FROM brc_day WHERE id = {d2}"))
    check('A LKR record: the preparer cannot review (403); returned with a reason; prepared again and reviewed → "Reviewed — ledger not yet corrected"; the record keeps the 2 pending postings',
          c2 == 403 and c3 == 200 and c4 == 409 and c5 == 200 and d['status_code'] == 'reviewed_pending' and snap['approved']['count'] == 2 and d['review']['state'] == 'reviewed', (c2, c3, c4, c5, d['status_label'], d.get('review')))
    mid = sql(f"SELECT id FROM brc_match WHERE bank_account_id = {ids['LKR']} AND status = 'active' ORDER BY id DESC LIMIT 1")
    c, j = prep.post('match/undo', {'id': mid, 'reason': 'test undo'})
    d = day(view, ids['LKR'], '2026-09-05')
    check('A undoing a match after review: kept as undone; the day falls back to "items to explain" and the reviewed record is flagged "changed since review"', c == 200 and sql(f"SELECT status FROM brc_match WHERE id = {mid}") == 'undone' and d['status_code'] == 'open_items' and d['review']['state'] == 'reviewed_outdated', (d['status_label'], d['review'].get('label')))
    c, j = prep.post('match/create', {'account': ids['LKR'], 'line_ids': [lid('AH 9903 - TEST GUIDE C - 90003')], 'book_keys': ['99980007.1'], 'note': 're-matched after the undo test'})
    d = day(view, ids['LKR'], '2026-09-05')
    check('A matching the same items again with the same explanation makes the reviewed record current again (what was reviewed is what the data says)', c == 200 and d['review']['state'] == 'reviewed' and d['status_code'] == 'reviewed_pending', d['review'].get('label'))
    c, j = prep.post('statement/void', {'id': sA, 'reason': 'should be refused'})
    c2, j2 = rev.post('statement/void', {'id': sA, 'reason': 'test: refused while it has matches'})
    check('A void: preparer refused (403); reviewer refused while the statement has matches (409)', c == 403 and c2 == 409, (c, c2))
    c, j = prep.post('correction/decide', {'id': cC, 'decision': 'approve'})
    c2, j2 = adm.post('correction/propose', {'account': ids['LKR'], 'kind': 'other', 'description': 'admin test proposal', 'entry_date': '2026-09-05', 'lines': [{'ledger_code': '1-6-8-29901', 'dr': '1', 'cr': ''}, {'ledger_code': '2-9-99-29909', 'dr': '', 'cr': '1'}]})
    c3, _ = adm.post('correction/decide', {'id': j2.get('id'), 'decision': 'approve'})
    adm.post('correction/withdraw', {'id': j2.get('id'), 'reason': 'test proposal withdrawn'})
    check('A four eyes kept: the preparer cannot approve (403); the administrator cannot approve their own proposal (403)', c == 403 and c3 == 403, (c, c3))
    return ids, {'sA': sA, 'sU': sU, 'sJ': sJ, 'cC': cC, 'cR': cR, 'cU': cU, 'dU': dU, 'd2': d2}

def part_b(adm, prep, rev, view, ids):
    prep2, rev2 = BR('accountant2'), BR('approver2')
    for u in (prep2, rev2):
        u.page()
    V = setup_v(adm)
    ids['V'] = V
    c, j = prep.post('mapping/propose', {'account': V, 'rec_start': '2026-09-01', 'note': 'r2 verification'}); c2, j2 = rev.post('mapping/decide', {'map_id': j.get('id'), 'decision': 'confirm'})
    check('B verification account (dummy, inactive): mapping proposed and confirmed (first day 1 Sep)', c == 200 and c2 == 200, (j.get('message'), j2.get('message')))
    c, j = upload(prep, V, S1, '500,000.00 CR', 'test_v_s1.csv'); s1 = j.get('id'); c2, j2 = prep.post('statement/accept', {'id': s1})
    check('B statement 1 (1–3 Sep, 4 lines) accepted', c == 200 and c2 == 200, (j.get('message'), j2.get('message')))
    L = lambda desc, sid=None: sql(f"SELECT id FROM brc_line WHERE bank_account_id = {V} AND description = '{desc}'" + (f" AND statement_id = {sid}" if sid else '') + " ORDER BY id LIMIT 1")
    # ---------------- D10: opening outstanding items
    d = day(view, V, '2026-09-01')
    check('B 1 Sep before opening items: bank 499,975.00, book 450,000.00, opening difference 50,000.00 not explained → insufficient', d['bank']['closing'] == '499975.00' and d['book']['closing'] == '450000.00' and d['reconciliation']['opening_difference_lkr'] == '50000.00' and d['status_code'] == 'insufficient', (d.get('bank'), d.get('book'), d['reasons'][:2]))
    c, j = prep.get('api/opening_candidates', account=V)
    cand = {x['key']: x for x in j.get('candidates', [])}
    check('B candidates: the ledger rows booked in the 60 days before the first day, with their effect on bank − book (cheques +20,000.00 and +30,000.00)', {'99980301.1', '99980302.1', '99980303.1'} <= set(cand) and cand['99980302.1']['effect_at_start'] == '20000.00' and cand['99980303.1']['effect_at_start'] == '30000.00', list(cand))
    c, j = prep.post('opening/propose', {'account': V, 'book_key': '99980302.1', 'evidence': 'cheque 000201 issued 28 Aug, not presented by 1 Sep'}); oA = j.get('id')
    c2, j2 = prep.post('opening/propose', {'account': V, 'book_key': '99980303.1', 'evidence': 'cheque 000202 issued 29 Aug, not presented by 1 Sep'}); oB = j2.get('id')
    c3, j3 = prep2.post('opening/propose', {'account': V, 'book_key': '99980302.1', 'evidence': 'again'})
    c4, j4 = prep.post('opening/propose', {'account': V, 'book_key': '99980304.1', 'evidence': 'on/after the first day'})
    check('B both cheques proposed as opening items; the same row again is refused (409); a row dated on or after the first day is refused (400)', c == 200 and c2 == 200 and c3 == 409 and c4 == 400, (c, c2, c3, j3.get('message'), c4, j4.get('message')))
    d = day(view, V, '2026-09-01')
    check('B proposed items do not explain anything yet: still insufficient, "50,000.00 proposed, awaiting acceptance"', d['status_code'] == 'insufficient' and d['reconciliation']['opening_items_proposed'] == '50000.00' and any('awaiting acceptance' in x for x in d['reasons']), d['reasons'][:2])
    c, _ = prep.post('opening/decide', {'id': oA, 'decision': 'accept'})
    c2, j2 = rev.post('opening/decide', {'id': oA, 'decision': 'accept'}); c3, j3 = rev2.post('opening/decide', {'id': oB, 'decision': 'accept', 'note': 'checked'})
    check('B the preparer cannot accept (403); the reviewers accept both', c == 403 and c2 == 200 and c3 == 200, (c, c2, c3))
    d = day(view, V, '2026-09-01'); r = d['reconciliation']
    check('B D10 day 1 reconciles: opening difference explained by 2 outstanding opening items (remaining 0.00); only the 25.00 charge left', r['opening_remaining'] == '0.00' and r['opening_items_outstanding'] == '50000.00' and d['status_code'] == 'open_items' and d['counts']['unexplained_items'] == 1 and d['counts']['opening_outstanding'] == 2, (r, d['counts']))
    c, j = prep.post('correction/charges', {'account': V, 'line_ids': [L('IB CEFT CHGS')]}); cV = j.get('id')
    d = day(view, V, '2026-09-01')
    check('B 1 Sep with the charge correction proposed → "All items explained — ledger not yet corrected — 1 correction(s) awaiting approval (25.00)"', d['status_code'] == 'explained_pending' and '1 correction(s) awaiting approval (25.00)' in d['status_label'], d['status_label'])
    # ---------------- D9: timing items
    c, j = prep.post('mark/create', {'account': V, 'side': 'book', 'ref': '99980305.1', 'mark': 'timing', 'expected_date': '2026-09-04', 'note': 'PV 90011 sent 2 Sep, clears 4 Sep'}); kJ = j.get('id')
    c2, j2 = prep.post('mark/create', {'account': V, 'side': 'book', 'ref': '99980304.1', 'mark': 'timing', 'expected_date': '2026-09-05', 'note': 'agent XYZ receipt in two parts'}); kX = j2.get('id')
    mk0 = sql(f"SELECT CONCAT(created_by,'|',created_at,'|',expected_date) FROM brc_mark WHERE id = {kJ}")
    d = day(view, V, '2026-09-02')
    check('B 2 Sep: two timing items (−15,000.00, +90,000.00); bank − book −25,025.00 explained to 0.00', c == 200 and c2 == 200 and d['reconciliation']['difference_lkr'] == '-25025.00' and d['counts']['timing'] == 2 and d['reconciliation']['unexplained_lkr'] == '0.00' and d['status_code'] == 'explained_pending', (d['reconciliation'], d['status_label']))
    c, j = prep.get('api/suggest', account=V, **{'from': '2026-09-01', 'to': '2026-09-03'})
    sg = {s['line']['description']: s for s in j.get('suggestions', [])}
    chq = sg.get('CHQ 000201 TEST CHEQUE', {})
    check('B the cheque line is suggested for ledger row 99980302 ("opening item #… (accepted)")', chq.get('book', [{}])[0].get('key') == '99980302.1' and any('accepted' in w for w in chq.get('why', [])), chq.get('why'))
    c, j = prep.post('match/create', {'account': V, 'line_ids': [chq['line']['id']], 'book_keys': ['99980302.1'], 'method': 'suggested'})
    check('B the cheque is matched (no warning: it is an accepted opening item)', c == 200 and 'not accepted' not in j.get('message', ''), j.get('message'))
    # ---------------- partial clearing
    c0, j0 = prep.post('match/create', {'account': V, 'line_ids': [L('CEFT RECEIPT - TEST AGENT XYZ', s1)], 'book_keys': ['99980304.1'], 'partial': 1, 'note': 'first part of the XYZ receipt'})
    c, j = prep.post('match/create', {'account': V, 'line_ids': [L('CEFT RECEIPT - TEST AGENT XYZ', s1)], 'book_keys': ['99980304.1'], 'partial': 1, 'note': 'first part of the XYZ receipt', 'expect_timing': [kX]})
    check('B D9: a match that would clear a timing classification the user did not confirm is refused (409); confirmed, the partial match is recorded', c0 == 409 and 'classified as timing' in j0.get('message', '') and c == 200, (j0.get('message'), j.get('message')))
    pid = j.get('id')
    c2, j2 = prep.post('match/undo', {'id': pid, 'reason': 'redo with the expected date'})
    mkx = sql(f"SELECT CONCAT(status,'|',IFNULL(cleared_by_match,'-')) FROM brc_mark WHERE id = {kX}")
    check('B undoing the partial match restores the timing classification of the receipt row (status active, as first recorded)', c2 == 200 and mkx == 'active|-' and 'restored' in j2.get('message', ''), (j2.get('message'), mkx))
    c, j = prep.post('match/create', {'account': V, 'line_ids': [L('CEFT RECEIPT - TEST AGENT XYZ', s1)], 'book_keys': ['99980304.1'], 'diff_reason': 'other', 'expect_timing': [kX]})
    c2, j2 = prep.post('match/create', {'account': V, 'line_ids': [L('CEFT RECEIPT - TEST AGENT XYZ', s1)], 'book_keys': ['99980304.1'], 'partial': 1, 'expected_date': '2026-09-05', 'note': 'agent XYZ paid 50,000 of 90,000; the rest expected 5 Sep', 'expect_timing': [kX]}); pid = j2.get('id')
    check('B partial clearing: 50,000.00 of the 90,000.00 receipt; "other" without an explanation is refused (400); the partial match is recorded (rest −40,000.00 expected 5 Sep)', c == 400 and c2 == 200 and sql(f"SELECT CONCAT(kind,'|',remainder_lkr,'|',expected_date) FROM brc_match WHERE id = {pid}") == 'partial|-40000.00|2026-09-05', (j.get('message'), j2.get('message')))
    check('B the receipt row\'s timing classification is cleared by the partial match (kept: status "matched", match noted)', sql(f"SELECT CONCAT(status,'|',cleared_by_match) FROM brc_mark WHERE id = {kX}") == f'matched|{pid}', sql(f"SELECT CONCAT(status,'|',IFNULL(cleared_by_match,'-')) FROM brc_mark WHERE id = {kX}"))
    # ---------------- two users at once (1): the same match from two preparers, 10 requests at the same instant
    start_mw()
    pA, pB = BR('accountant', base=MW), BR('accountant2', base=MW)
    pA.page(); pB.page()
    lK = L('AH 9911 - TEST VENDOR K - 90012')
    res = race([(pA if i % 2 == 0 else pB, 'match/create', {'account': V, 'line_ids': [lK], 'book_keys': ['99980306.1'], 'method': 'manual'}) for i in range(10)])
    codes = sorted(c for c, _ in res)
    pA.page(); pB.page()
    nact = sql(f"SELECT COUNT(*) FROM brc_match m JOIN brc_match_item i ON i.match_id = m.id WHERE m.status = 'active' AND i.line_id = {lK}")
    check('B two users at once — duplicate matches: 10 simultaneous requests (2 preparers) → exactly one match, 9 refused (409), one claim', codes == [200] + [409] * 9 and nact == '1' and sql(f"SELECT COUNT(*) FROM brc_claim WHERE item_key = 'L{lK}'") == '1', (codes, nact))
    d = day(view, V, '2026-09-03'); r = d['reconciliation']
    check('B 3 Sep: bank − book 4,975.00 = opening item 000202 outstanding 30,000.00 − charge 25.00 − partial rest 40,000.00 + timing 15,000.00; cheque 000201 cleared', r['difference_lkr'] == '4975.00' and r['partial_remainders'] == '-40000.00' and r['opening_items_cleared'] == '-20000.00' and r['opening_items_outstanding'] == '30000.00' and r['unexplained_lkr'] == '0.00' and d['status_code'] == 'explained_pending' and sorted(o['state'] for o in d['items']['opening']) == ['cleared', 'outstanding'], (r, d['status_label']))
    # ---------------- daily record 3 Sep: review with a correction still proposed, then approved
    c, j = prep.post('day/prepare', {'account': V, 'date': '2026-09-03', 'note': 'r2 test'}); r3 = j.get('id')
    c2, j2 = rev.post('day/review', {'id': r3, 'decision': 'reviewed', 'note': 'ok'})
    d = day(view, V, '2026-09-03')
    check('B 3 Sep reviewed while the charge correction is only proposed → "Reviewed — ledger not yet corrected — 1 correction(s) awaiting approval (25.00)"', c2 == 200 and d['status_code'] == 'reviewed_pending' and 'awaiting approval' in d['status_label'], d['status_label'])
    c, j = rev.post('correction/decide', {'id': cV, 'decision': 'approve', 'note': 'ok'})
    d = day(view, V, '2026-09-03')
    check('B approving the correction after the review does not make the review out of date (posting progress only), and the status now says "approved but not posted"', d['review']['state'] == 'reviewed' and d['review']['postings_progressed'] and '1 approved but not posted (25.00)' in d['status_label'], (d['review'], d['status_label']))
    # ---------------- two users at once (2): the same draft accepted by two preparers at the same instant
    c, j = upload(prep, V, S2, '521,975.00 CR', 'test_v_s2.csv'); s2 = j.get('id')
    res = race([(pA, 'statement/accept', {'id': s2}), (pB, 'statement/accept', {'id': s2})])
    codes = sorted(c for c, _ in res)
    pA.page(); pB.page()
    check('B two users at once — duplicate acceptance: one 200, one 409; accepted once (one audit row)', codes == [200, 409] and sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action = 'brc_statement_accepted' AND ref_id = '{s2}'") == '1', (codes, [j.get('message') for _, j in res]))
    # ---------------- D9: the timing item is suggested and cleared by the match; undo restores it
    c, j = prep.get('api/suggest', account=V, **{'from': '2026-09-01', 'to': '2026-09-05'})
    sugs = j.get('suggestions', [])
    sJ = next((s for s in sugs if s['line']['description'] == 'AH 9910 - TEST VENDOR J - 90011'), {})
    sC = next((s for s in sugs if s.get('type') == 'complete'), {})
    check('B D9: the timing item PV 90011 is offered in the suggestions (high), saying that accepting clears the classification', sJ.get('confidence') == 'high' and sJ.get('book', [{}])[0].get('key') == '99980305.1' and sJ['book'][0].get('timing') and sJ.get('clears_timing') == 1 and sJ.get('timing_ids') == [kJ] and any('timing item' in w for w in sJ.get('why', [])), sJ)
    check('B the second part of the XYZ receipt is suggested as completing the partial match', sC.get('match_id') == pid and sC.get('line', {}).get('amount') == '40000.00' and not sC.get('needs_explanation'), sC)
    c, j = prep2.post('match/create', {'account': V, 'line_ids': [sJ['line']['id']], 'book_keys': ['99980305.1'], 'method': 'suggested', 'expect_timing': sJ['timing_ids']}); mJ = j.get('id')
    mk = sql(f"SELECT CONCAT(status,'|',cleared_by_match,'|',cleared_by,'|',created_by,'|',created_at,'|',expected_date) FROM brc_mark WHERE id = {kJ}")
    ev = sql(f"SELECT GROUP_CONCAT(CONCAT(event,':',match_id,':',by_user) ORDER BY id) FROM brc_mark_event WHERE mark_id = {kJ}")
    au = sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action = 'brc_timing_cleared_by_match' AND ref_id = '{kJ}' AND prev_value LIKE '%\"classified_by\":\"tcaccount\"%'")
    check('B accepting it (second preparer) clears the timing classification: kept with the original classifier, time and expected date; event and audit recorded',
          c == 200 and mk == f'matched|{mJ}|tcaccount2|{mk0.split("|")[0]}|{mk0.split("|")[1]}|{mk0.split("|")[2]}' and ev == f'cleared_by_match:{mJ}:tcaccount2' and au == '1' and 'timing classification' in j.get('message', ''), (mk, mk0, ev, au, j.get('message')))
    c, j = prep.post('match/undo', {'id': mJ, 'reason': 'test: undo restores the timing item'})
    mk2 = sql(f"SELECT CONCAT(status,'|',IFNULL(cleared_by_match,'-'),'|',IFNULL(cleared_by,'-'),'|',created_by,'|',created_at,'|',expected_date) FROM brc_mark WHERE id = {kJ}")
    d = day(view, V, '2026-09-04')
    check('B undo restores the outstanding status: the same timing classification is active again (classifier, time, expected date unchanged); the claim is the classification again',
          c == 200 and mk2 == f'active|-|-|{mk0}' and sql(f"SELECT CONCAT(kind,':',ref_id) FROM brc_claim WHERE item_key = 'B{V}:99980305.1'") == f'mark:{kJ}' and any(it.get('key') == '99980305.1' for it in d['items']['timing']), (mk2, mk0, j.get('message')))
    c, j = prep.post('match/create', {'account': V, 'line_ids': [sJ['line']['id']], 'book_keys': ['99980305.1'], 'method': 'suggested', 'expect_timing': [kJ]}); mJ = j.get('id')
    check('B reopening: matched again → cleared again (history: cleared, restored, cleared)', c == 200 and sql(f"SELECT GROUP_CONCAT(event ORDER BY id) FROM brc_mark_event WHERE mark_id = {kJ}") == 'cleared_by_match,restored_by_undo,cleared_by_match', sql(f"SELECT GROUP_CONCAT(event ORDER BY id) FROM brc_mark_event WHERE mark_id = {kJ}"))
    # ---------------- two users at once (3): match and classify the same ledger row at the same instant
    lL = L('AH 9912 - TEST VENDOR L - 90013')
    res = race([(pA, 'match/create', {'account': V, 'line_ids': [lL], 'book_keys': ['99980307.1']}), (pB, 'mark/create', {'account': V, 'side': 'book', 'ref': '99980307.1', 'mark': 'timing', 'note': 'race test classification'})])
    codes = [c for c, _ in res]
    pA.page(); pB.page()
    check('B two users at once — one matches, the other classifies the same ledger row (as timing): exactly one succeeds; the match never clears a classification its user did not see', sorted(codes) == [200, 409] and sql(f"SELECT COUNT(*) FROM brc_claim WHERE item_key = 'B{V}:99980307.1'") == '1', (codes, [j.get('message') for _, j in res]))
    if codes[1] == 200:   # the classification won: clear it and match
        prep.post('mark/clear', {'id': res[1][1].get('id'), 'reason': 'race test: match instead'})
        prep.post('match/create', {'account': V, 'line_ids': [lL], 'book_keys': ['99980307.1']})
    # ---------------- daily record 4 Sep: review refused when the data changed after preparation; two reviewers at once
    c, j = prep.post('day/prepare', {'account': V, 'date': '2026-09-04', 'note': 'r2 test'}); r4 = j.get('id')
    mL = sql(f"SELECT m.id FROM brc_match m JOIN brc_match_item i ON i.match_id = m.id WHERE m.status = 'active' AND i.line_id = {lL}")
    prep2.post('match/undo', {'id': mL, 'reason': 'test: change after preparation'})
    c2, j2 = rev.post('day/review', {'id': r4, 'decision': 'reviewed'})
    check('B the reviewer cannot review a record whose data changed after it was prepared (409, audited); nothing is reviewed', c2 == 409 and 'changed after this record was prepared' in j2.get('message', '') and sql(f"SELECT review_status FROM brc_day WHERE id = {r4}") == 'prepared' and sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action = 'brc_day_review_refused' AND ref_id = '{r4}'") == '1', j2.get('message'))
    check('B the record shows "changed after it was prepared; prepare it again"', day(view, V, '2026-09-04')['review']['state'] == 'prepared_outdated', '')
    prep.post('match/create', {'account': V, 'line_ids': [lL], 'book_keys': ['99980307.1']})
    c, j = prep.post('day/prepare', {'account': V, 'date': '2026-09-04', 'note': 'prepared again'}); r4 = j.get('id')
    rA, rB = BR('approver', base=MW), BR('approver2', base=MW); rA.page(); rB.page()
    res = race([(rA, 'day/review', {'id': r4, 'decision': 'reviewed', 'note': 'A'}), (rB, 'day/review', {'id': r4, 'decision': 'reviewed', 'note': 'B'})])
    codes = sorted(c for c, _ in res)
    check('B two users at once — daily-record review by two reviewers at the same instant: one 200, one 409; reviewed once', codes == [200, 409] and sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action = 'brc_day_reviewed' AND ref_id = '{r4}'") == '1', (codes, [j.get('message') for _, j in res]))
    # ---------------- changes after review: a matched ledger row
    sql("UPDATE account_runing SET Discription = 'VOUCHER PAYMENT - TEST VENDOR L (dummy, edited)' WHERE ID = 99980307")
    d = day(view, V, '2026-09-04')
    c, j = view.get('api/day_record', id=r4)
    check('B a matched ledger row changed after review → flagged "changed since review: ledger rows" (re-review needed); the day is insufficient; the reviewed snapshot is unchanged',
          d['review']['state'] == 'reviewed_outdated' and 'ledger rows (book side)' in d['review']['changed_labels'] and d['status_code'] == 'insufficient' and j['record']['status_code'] == 'explained_pending' and j['state']['state'] == 'reviewed_outdated', (d['review'], d['status_label'], j.get('record', {}).get('status_label')))
    sql("UPDATE account_runing SET Discription = 'VOUCHER PAYMENT - TEST VENDOR L (dummy)' WHERE ID = 99980307")
    check('B the dummy row put back exactly → the review is current again', day(view, V, '2026-09-04')['review']['state'] == 'reviewed', '')
    # ---------------- complete the partial match; undo and reopen
    c, j = prep.post('match/create', {'account': V, 'line_ids': [sC['line']['id']], 'book_keys': [], 'supersedes': pid, 'method': 'complete'}); pc = j.get('id')
    check('B the partial match is completed by the 5 Sep line (new match; the partial one kept as superseded)', c == 200 and sql(f"SELECT CONCAT(status,'|',superseded_by) FROM brc_match WHERE id = {pid}") == f'superseded|{pc}' and sql(f"SELECT CONCAT(kind,'|',difference_lkr,'|',supersedes_id) FROM brc_match WHERE id = {pc}") == f'full|0.00|{pid}', (j.get('message'),))
    d3 = day(view, V, '2026-09-03')
    check('B completing it later does not change the reviewed 3 Sep: same figures, the review stays current', d3['reconciliation']['difference_lkr'] == '4975.00' and d3['reconciliation']['unexplained_lkr'] == '0.00' and d3['review']['state'] == 'reviewed', (d3['review'].get('label'), d3['reconciliation']))
    c, j = prep.post('match/undo', {'id': pc, 'reason': 'test: undo the completion'})
    check('B undoing the completion makes the partial match active again', c == 200 and sql(f"SELECT status FROM brc_match WHERE id = {pid}") == 'active' and 'active again' in j.get('message', ''), j.get('message'))
    c, j = prep.post('match/create', {'account': V, 'line_ids': [sC['line']['id']], 'book_keys': [], 'supersedes': pid}); pc = j.get('id')
    check('B completed again ("Add selected" on the partial match)', c == 200, j.get('message'))
    # ---------------- opening items across several days
    rows = []
    for dd, oa_state, out, diff in [('2026-09-01', 'outstanding', '50000.00', '49975.00'), ('2026-09-02', 'outstanding', '50000.00', '-25025.00'), ('2026-09-03', 'cleared', '30000.00', '4975.00'), ('2026-09-04', 'cleared', '30000.00', '-10025.00'), ('2026-09-05', 'cleared', '30000.00', '29975.00')]:
        d = day(view, V, dd); r = d['reconciliation']; st = {o['book_key']: o['state'] for o in d['items']['opening']}
        rows.append((dd, r['difference_lkr'], r['opening_remaining'], r['opening_items_outstanding'], st.get('99980302.1'), st.get('99980303.1'), r['unexplained_lkr'], d['status_code']))
        ok = r['difference_lkr'] == diff and r['opening_remaining'] == '0.00' and r['opening_items_outstanding'] == out and st.get('99980302.1') == oa_state and st.get('99980303.1') == 'outstanding' and r['unexplained_lkr'] == '0.00'
        check(f'B opening items on {dd}: cheque 000202 outstanding, cheque 000201 {oa_state}; bank − book {diff} explained to 0.00', ok, rows[-1])
    # ---------------- pending postings, a posted entry verified against the approved lines
    c, j = prep.post('day/prepare', {'account': V, 'date': '2026-09-05', 'note': 'r2 test'}); r5 = j.get('id'); rev.post('day/review', {'id': r5, 'decision': 'reviewed'})
    c, j = view.get('api/days', account=V)
    rec5 = next(x for x in j['days'] if x['id'] == r5)
    check('B the reviewed 5 Sep record shows the pending posting kept at preparation (1 approved, not posted: 25.00) and now', rec5['pending']['approved']['count'] == 1 and rec5['pending']['approved']['total'] == '25.00' and rec5['live']['state'] == 'reviewed' and '1 approved but not posted' in (rec5['live']['pending_now'] or ''), rec5)
    c, j = prep.post('correction/posted', {'id': cV, 'ledger_id': '99980301'})
    check('B recording a posting with an entry that does not match the approved lines is refused (409) and audited', c == 409 and 'does not match' in j.get('message', '') and sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action = 'brc_correction_posting_refused' AND ref_id = '{cV}'") == '1', j.get('message'))
    chl = json.loads(sql(f"SELECT entry_json FROM brc_correction WHERE id = {cV}"))
    # the fixture: an entry as Accounts would post it by hand in the accounting module (dummy ID, test copy only)
    sql(f"INSERT INTO account_runing (ID, L_Code, Dr, Cr, Discription, Date, Recpt_No, Chk_No, payee, index_no, Cashier, Dt, inv_total) VALUES (99980399, '{chl[0]['ledger_code']}', 25, 0, 'BANK CHARGES 01-09 (dummy posting, test fixture)', '2026-09-05', 0, '', '', '', 'test', '2026-09-06 11:00:00', ''), (99980399, '1-6-8-29904', 0, 25, 'BANK CHARGES 01-09 (dummy posting, test fixture)', '2026-09-05', 0, '', '', '', 'test', '2026-09-06 11:00:00', '')")
    d = day(view, V, '2026-09-05')
    check('B the posted entry changes the ledger → the reviewed 5 Sep record is flagged for re-review (ledger rows)', d['review']['state'] == 'reviewed_outdated' and 'ledger rows (book side)' in d['review']['changed_labels'], d['review'])
    c, j = prep.post('correction/posted', {'id': cV, 'ledger_id': '99980399'})
    c2, j2 = prep.get('api/corrections', account=V); stv = next(x for x in j2['corrections'] if x['id'] == cV)
    check('B the posted entry 99980399 matches the approved lines → linked and verified: "Posted — linked to a verified ledger entry"', c == 200 and stv['state'] == 'posted' and stv['ledger_corrected'] and stv['posted_entry_date'] == '2026-09-05', (j.get('message'), stv['state_label']))
    d5 = day(view, V, '2026-09-05'); d1 = day(view, V, '2026-09-01')
    check('B 5 Sep after the verified posting: nothing pending; bank − book 30,000.00 (the outstanding cheque); "Reconciled — all items explained"', d5['status_code'] == 'explained' and d5['pending_postings']['not_corrected'] == 0 and d5['reconciliation']['difference_lkr'] == '30000.00' and d5['reconciliation']['unexplained_lkr'] == '0.00', (d5['status_label'], d5['reconciliation']['difference_lkr']))
    check('B 1 Sep (before the posting date): the same correction is shown as not yet posted at that date', d1['status_code'] == 'explained_pending' and 'approved but not posted' in d1['status_label'], d1['status_label'])
    sql("UPDATE account_runing SET Discription = 'BANK CHARGES 01-09 (dummy posting, CHANGED)' WHERE ID = 99980399 AND L_Code = '1-6-8-29904'")
    d5 = day(view, V, '2026-09-05')
    check('B if the posted entry changes in the ledger afterwards: "Posted entry changed in the ledger — verify again"; the charge is no longer explained', d5['pending_postings']['posted_changed']['count'] == 1 and d5['status_code'] == 'open_items', (d5['status_label'], d5['pending_postings']['summary']))
    sql("UPDATE account_runing SET Discription = 'BANK CHARGES 01-09 (dummy posting, test fixture)' WHERE ID = 99980399 AND L_Code = '1-6-8-29904'")
    c, j = prep.post('day/prepare', {'account': V, 'date': '2026-09-05', 'note': 'after the posting'}); r5 = j.get('id'); c2, j2 = rev2.post('day/review', {'id': r5, 'decision': 'reviewed'})
    check('B prepared again and reviewed → "Reconciled — reviewed" with nothing pending', c2 == 200 and day(view, V, '2026-09-05')['status_code'] == 'reviewed', day(view, V, '2026-09-05')['status_label'])
    # ---------------- conflicting edits and two drafts accepted at once
    c, j = upload(prep, V, S3, '534,975.00 CR', 'test_v_s3.csv'); s3 = j.get('id'); l3 = sql(f"SELECT id FROM brc_line WHERE statement_id = {s3}")
    c1, j1 = prep2.post('line/edit', {'id': l3, 'description': 'TEST INTEREST CREDIT SEPT', 'reason': 'wording from the paper statement', 'version': 0})
    c2, j2 = prep.post('line/edit', {'id': l3, 'description': 'TEST INTEREST', 'reason': 'my change on the old version', 'version': 0})
    check('B two users at once — conflicting edits: the second change based on the old version is refused (409); the first is kept', c1 == 200 and c2 == 409 and sql(f"SELECT CONCAT(description,'|',version) FROM brc_line WHERE id = {l3}") == 'TEST INTEREST CREDIT SEPT|1', (j1.get('message'), j2.get('message')))
    c, j = upload(prep2, V, S3, '534,975.00 CR', 'test_v_s3_copy.csv'); s3b = j.get('id')
    res = race([(pA, 'statement/accept', {'id': s3}), (pB, 'statement/accept', {'id': s3b})])
    codes = sorted(c for c, _ in res)
    check('B two users at once — two drafts of the same period accepted at the same instant: one accepted, the other refused (overlap)', codes == [200, 409] and sql(f"SELECT COUNT(*) FROM brc_statement WHERE id IN ({s3},{s3b}) AND status = 'accepted'") == '1', (codes, [j.get('message') for _, j in res]))
    for u in (pA, pB):
        u.page()
    winner, loser = (s3, s3b) if sql(f"SELECT status FROM brc_statement WHERE id = {s3}") == 'accepted' else (s3b, s3)
    prep.post('statement/void', {'id': loser, 'reason': 'duplicate draft of the race test'})
    lw = sql(f"SELECT id FROM brc_line WHERE statement_id = {winner}")
    c, j = prep.post('mark/create', {'account': V, 'side': 'bank', 'ref': lw, 'mark': 'excluded', 'note': 'test: interest credit outside this test'})
    c2, j2 = prep.post('mark/clear', {'id': j.get('id'), 'reason': 'test: classification cleared'})
    check('B a classification is made and cleared (kept as cleared; the claim released)', c == 200 and c2 == 200 and sql(f"SELECT COUNT(*) FROM brc_claim WHERE item_key = 'L{lw}'") == '0', (j.get('message'), j2.get('message')))
    # ---------------- rate change after review; conflicting rate edits
    c, j = view.get('api/rates', **{'from': '2026-09-05', 'to': '2026-09-05'})
    rr = next(x for x in j['rates'] if x['currency'] == 'USD'); v0 = int(rr['version'])
    c1, j1 = prep2.post('rate/save', {'currency': 'USD', 'rate_date': '2026-09-05', 'rate': '302.10', 'source': 'TEST rate (dummy)', 'note': 'bank board rate corrected (test)', 'version': v0})
    c2, j2 = prep.post('rate/save', {'currency': 'USD', 'rate_date': '2026-09-05', 'rate': '302.20', 'source': 'TEST rate (dummy)', 'note': 'my change on the old version', 'version': v0})
    d = day(view, ids['USD'], '2026-09-05')
    check('B two users at once — conflicting rate edits: the change on the old version is refused (409)', c1 == 200 and c2 == 409, (j1.get('message'), j2.get('message')))
    check('B a rate change after review → the reviewed USD record is flagged (rates; statement LKR values); the revaluation changes', d['review']['state'] == 'reviewed_outdated' and 'exchange rates' in d['review']['changed_labels'] and d['reconciliation']['revaluation'] != '97500.00', (d['review'].get('changed_labels'), d['reconciliation']['revaluation']))
    c3, j3 = prep2.post('rate/save', {'currency': 'USD', 'rate_date': '2026-09-05', 'rate': '302.00', 'source': 'TEST rate (dummy)', 'note': 'back to the reviewed rate (test)', 'version': v0 + 1})
    check('B the rate put back → the review is current again', c3 == 200 and day(view, ids['USD'], '2026-09-05')['review']['state'] == 'reviewed', j3.get('message'))
    # ---------------- four eyes and permissions on the new actions
    c, j = adm.post('opening/propose', {'account': V, 'book_key': '99980301.1', 'evidence': 'admin test'}); oX = j.get('id')
    c2, _ = adm.post('opening/decide', {'id': oX, 'decision': 'accept'})
    c3, _ = rev.post('opening/decide', {'id': oX, 'decision': 'withdraw', 'note': 'not mine'})
    c4, _ = adm.post('opening/decide', {'id': oX, 'decision': 'withdraw', 'note': 'test proposal withdrawn'})
    check('B four eyes on opening items: the proposer (administrator) cannot accept (403); only the proposer withdraws (403 for another; 200 for the proposer)', c == 200 and c2 == 403 and c3 == 403 and c4 == 200 and sql(f"SELECT CONCAT(status,'|',IFNULL(active_key,'-')) FROM brc_opening WHERE id = {oX}") == 'withdrawn|-', (c, c2, c3, c4))
    c, _ = rev.post('match/create', {'account': V, 'line_ids': [l3], 'book_keys': ['99980301.1']}); c2, _ = view.post('opening/propose', {'account': V, 'book_key': '99980301.1'})
    c3, _ = view.get('api/opening_candidates', account=V); c4, _ = view.get('api/day_record', id=r5)
    check('B the reviewer cannot match (403); view-only cannot propose (403) but can read candidates and records (200)', c == 403 and c2 == 403 and c3 == 200 and c4 == 200, (c, c2, c3, c4))
    return {'V': V, 'cV': cV, 's1': s1, 's2': s2, 'r3': r3, 'r4': r4, 'r5': r5, 'pid': pid}

def main():
    ensure_users()
    sig0 = sql(LEDGER_SIG)
    a0 = int(sql("SELECT COALESCE(MAX(id),0) FROM tpd_audit"))
    adm = BR('admin'); adm.page()
    prep, rev, view = BR('accountant'), BR('approver'), BR('bank')
    ids, a = part_a(adm, prep, rev, view, a0)
    b = part_b(adm, prep, rev, view, ids)
    acts = set(sql(f"SELECT GROUP_CONCAT(DISTINCT action ORDER BY action) FROM tpd_audit WHERE id > {a0}").split(','))
    need = {'access_denied', 'brc_classified', 'brc_correction_approved', 'brc_correction_proposed', 'brc_correction_withdrawn', 'brc_correction_posting_refused', 'brc_correction_posting_verified', 'brc_day_prepared', 'brc_day_returned', 'brc_day_reviewed',
            'brc_day_review_refused', 'brc_difference_explained', 'brc_line_changed', 'brc_mapping_confirmed', 'brc_mapping_proposed', 'brc_match_undone', 'brc_matched', 'brc_opening_item_accepted', 'brc_opening_item_proposed', 'brc_opening_item_withdrawn',
            'brc_rate_changed', 'brc_rate_entered', 'brc_setup', 'brc_statement_accepted', 'brc_statement_kept', 'brc_statement_voided', 'brc_timing_cleared_by_match', 'brc_timing_restored_by_undo', 'brc_transfer_recorded', 'brc_classification_cleared'}
    check('two users at once: in every race the first requests of the two users were in the server at the same time (overlapping)', RACES and all(r['first_requests_overlapped'] for r in RACES), RACES)
    json.dump(RACES, open(HERE + '/races_r2.json', 'w'), indent=1)
    check('every kind of change is in the audit trail (incl. timing cleared/restored, explanations, posting verified/refused, review refused)', need <= acts, sorted(need - acts))
    den = int(sql(f"SELECT COUNT(*) FROM tpd_audit WHERE id > {a0} AND action = 'access_denied'"))
    check(f'refusals are audited too ({den} rows)', den >= 30, den)
    check('the real ledger is unchanged: rows, sums and every row\'s reconciliation flags (aflag, bank_rec, bankst_flag, BankFlg)', sql(LEDGER_SIG) == sig0, (sig0, sql(LEDGER_SIG)))
    dummy = sql("SELECT CONCAT(COUNT(*),'|',SUM(Dr),'|',SUM(Cr)) FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999")
    check('dummy ledger rows: the 15 of r1 + 7 of the verification account + the 2-row test fixture posting (24 rows) — the module wrote none of them', dummy == '24|23494025.00|3537370.00', dummy)
    nbrc = sql("SELECT COUNT(*) FROM tpd_audit WHERE id > %d AND (action LIKE 'brc%%' OR ref_type LIKE 'brc%%') AND user NOT IN ('tcaccount','tcaccount2','tcpayappr','tcpayappr2','tcbankappr','tcadmin','tcpayreq','tcops','tctransh','tchotelres','tcquote','tchotel','tctransport','tcmgmt')" % a0)
    check('every audited action is attributed to an identifiable test login', nbrc == '0', nbrc)
    json.dump({'ids': ids, 'a': a, 'b': b}, open(HERE + '/test_ids_r2.json', 'w'))

if __name__ == '__main__':
    try:
        main()
    finally:
        out = os.environ.get('BRC_RESULTS', HERE + '/results_bankrec_r2.json')
        json.dump(RES, open(out, 'w'), indent=1)
        print('%d/%d passed' % (sum(r['ok'] for r in RES), len(RES)))
