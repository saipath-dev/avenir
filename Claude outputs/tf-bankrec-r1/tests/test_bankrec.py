r"""tf-bankrec-r1 — end-to-end test of the Daily Bank Reconciliation with DUMMY data only.

Runs against a test instance (default: the cloud test copy http://127.0.0.1:8081/avenir/, database avenir_test).
Dummy data: three inactive TEST bank accounts, three TEST ledger accounts (L_Code 1-6-8-29901/29902/29903) and a TEST
suspense account, TEST ledger rows with IDs 99980001-99980299, TEST statements and rates. The real ledger is checked
before and after: rows outside the dummy ID range must be byte-for-byte the same (count, sums, CRC of every row incl. the
reconciliation flags aflag, bank_rec, bankst_flag, BankFlg).
"""
import os, sys, re, json, subprocess, io
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, S + '/paydesk')
from api import Client, ACC

DB = os.environ.get('BRC_DB', 'avenir_test')
SOCK = os.environ.get('BRC_SOCK', S + '/mysql.sock')
GROUPS = json.loads(os.environ.get('BRC_GROUPS', '{"prepare": [908], "review": [907], "view": [909, 910]}'))
RES = []
def check(name, ok, detail=''):
    RES.append({'name': name, 'ok': bool(ok), 'detail': str(detail)[:400]})
    print(('  ok   ' if ok else '  FAIL ') + name + ('' if ok else '   ' + str(detail)[:400]))
    return ok
def sql(q):
    r = subprocess.run(['mysql', '--socket=' + SOCK, '-uroot', '-N', '-B', DB, '-e', q], capture_output=True, text=True)
    if r.returncode != 0:
        raise SystemExit(r.stderr + '\n' + q[:300])
    return r.stdout.strip()

LEDGER_SIG = """SELECT CONCAT(COUNT(*), '|', COALESCE(SUM(Dr),0), '|', COALESCE(SUM(Cr),0), '|', COALESCE(SUM(CRC32(CONCAT_WS('|', ID, L_Code, Dr, Cr, Discription, Date, aflag, bank_rec, bankst_flag, BankFlg, Recpt_No, Chk_No))),0))
  FROM account_runing WHERE ID NOT BETWEEN 99980000 AND 99989999"""

class BR:
    """A signed-in user of the reconciliation pages (keeps the CSRF token the server rotates)."""
    def __init__(self, who):
        self.c = Client(who); self.who = who; self.base = self.c.base + 'accounts/daily_bank_rec/'; self.tok = None; self.name = 'csrf_test_name'
    def page(self, path=''):
        r = self.c.s.get(self.base + path, allow_redirects=False)
        m = re.search(r'const csrf = \{ name: "([^"]+)", hash: "([0-9a-f]+)"', r.text)
        if m:
            self.name, self.tok = m.group(1), m.group(2)
        return r
    def token(self):
        if not self.tok:
            r = self.c.s.get(self.c.base + 'tour/tour_payment_dashboard')
            m = re.search(r'name="(csrf_[^"]+)" value="([0-9a-f]{32})"', r.text) or re.search(r"formData.append\('([^']+)', '([0-9a-f]{32})'\)", r.text)
            if m:
                self.name, self.tok = m.group(1), m.group(2)
            else:
                self.page()
        return self.tok
    def get(self, path, **params):
        r = self.c.s.get(self.base + path, params=params, headers={'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}, allow_redirects=False)
        try:
            return r.status_code, r.json()
        except Exception:
            return r.status_code, {'raw': r.text[:200]}
    def post(self, path, data=None, files=None, csrf=True):
        d = dict(data or {})
        for k, v in list(d.items()):
            if isinstance(v, (list, dict)):
                d[k] = json.dumps(v)
        if csrf:
            d[self.name] = self.token()
        r = self.c.s.post(self.base + path, data=d, files=files, headers={'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}, allow_redirects=False)
        try:
            j = r.json()
        except Exception:
            j = {'raw': r.text[:300]}
        if isinstance(j, dict) and j.get('csrf'):
            self.tok = j['csrf']
        return r.status_code, j

def csv_bytes(rows):
    out = io.StringIO()
    import csv
    w = csv.writer(out)
    for r in rows:
        w.writerow(r)
    return out.getvalue().encode('utf-8')

LKR_ROWS = [['Txn Date', 'Value Date', 'Description', 'Cheque No', 'Debit', 'Credit', 'Balance'],
    ['01/09/2026', '01/09/2026', 'AH 9901 - TEST HOTEL A - 90001', '', '50,000.00', '', '950,000.00 CR'],
    ['01/09/2026', '01/09/2026', 'IB CEFT CHGS', '', '25.00', '', '949,975.00 CR'],
    ['02/09/2026', '02/09/2026', 'CHQ 000123 TEST CHEQUE', '000123', '150,000.00', '', '799,975.00 CR'],
    ['02/09/2026', '02/09/2026', 'AH 9902 - TEST SUPPLIER B - 90002', '', '50,000.00', '', '749,975.00 CR'],
    ['02/09/2026', '02/09/2026', 'IB CEFT CHGS', '', '25.00', '', '749,950.00 CR'],
    ['03/09/2026', '03/09/2026', 'TRF FROM TEST-USD-0002', '', '', '3,000,000.00', '3,749,950.00 CR'],
    ['03/09/2026', '03/09/2026', 'FUND TRANSFER FEE', '', '1,000.00', '', '3,748,950.00 CR'],
    ['03/09/2026', '03/09/2026', 'AH 9903 - TEST GUIDE C - 90003', '', '75,000.00', '', '3,673,950.00 CR'],
    ['04/09/2026', '04/09/2026', 'CASH DEPOSIT', '', '', '235,000.00', '3,908,950.00 CR'],
    ['05/09/2026', '05/09/2026', 'AH 9905 - TEST JEEP D - 90005', '', '12,344.00', '', '3,896,606.00 CR'],
    ['05/09/2026', '05/09/2026', 'GPRS RENTAL CHARGES', '', '3,500.00', '', '3,893,106.00 CR']]

def setup_dummy(adm):
    """Dummy chart-of-accounts codes, ledger rows and three inactive TEST bank accounts (through the real master)."""
    for code, p, name in [('1-6-8-29901', 29901, 'TEST Bank LKR (dummy)'), ('1-6-8-29902', 29902, 'TEST Bank USD (dummy)'), ('1-6-8-29903', 29903, 'TEST Bank JPY (dummy)'), ('2-9-99-29909', 29909, 'TEST Suspense (dummy)')]:
        if not sql(f"SELECT COUNT(*) FROM account_posting WHERE L_Code = '{code}'") != '0':
            pass
        if sql(f"SELECT COUNT(*) FROM account_posting WHERE L_Code = '{code}'") == '0':
            b, t, h = code.split('-')[:3]
            sql(f"INSERT INTO account_posting (P_Code, PName, B_Code, T_Code, H_Code, L_Code) VALUES ({p}, '{name}', {b}, {t}, {h}, '{code}')")
    sql("DELETE FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999")
    rows = [
        (99980001, '1-6-8-29901', 1000000, 0, '2026-08-01', 'TEST OPENING (dummy)', 0, '', '', ''),
        (99980002, '1-6-8-29901', 0, 150000, '2026-08-30', 'TEST CHEQUE 000123 (dummy)', 0, '000123', '', ''),
        (99980003, '1-6-8-29901', 0, 50000, '2026-09-01', 'VOUCHER PAYMENT - TEST HOTEL A (dummy)', 90001, '', 'TEST HOTEL A', '9901'),
        (99980004, '1-6-8-29901', 0, 20000, '2026-09-02', 'VOUCHER PAYMENT - TEST SUPPLIER B (dummy)', 90002, '', 'TEST SUPPLIER B', '9902'),
        (99980005, '1-6-8-29901', 0, 30000, '2026-09-02', 'VOUCHER PAYMENT - TEST SUPPLIER B (dummy)', 90002, '', 'TEST SUPPLIER B', '9902'),
        (99980006, '1-6-8-29901', 3000000, 0, '2026-09-03', 'JOURNAL ENTRY - USD TO LKR (dummy)', 0, '', '', ''),
        (99980007, '1-6-8-29901', 0, 75000, '2026-09-03', 'VOUCHER PAYMENT - TEST GUIDE C (dummy)', 90003, '', 'TEST GUIDE C', '9903'),
        (99980008, '1-6-8-29901', 0, 75000, '2026-09-03', 'VOUCHER PAYMENT - TEST GUIDE C (dummy)', 90003, '', 'TEST GUIDE C', '9903'),
        (99980009, '1-6-8-29901', 0, 40000, '2026-09-04', 'VOUCHER PAYMENT - TEST RESTAURANT E (dummy)', 90004, '', 'TEST RESTAURANT E', '9904'),
        (99980010, '1-6-8-29901', 0, 12345, '2026-09-05', 'VOUCHER PAYMENT - TEST JEEP D (dummy)', 90005, '', 'TEST JEEP D', '9905'),
        (99980101, '1-6-8-29902', 15000000, 0, '2026-08-01', 'TEST OPENING USD 50,000 @ 300 (dummy)', 0, '', '', ''),
        (99980102, '1-6-8-29902', 1500000, 0, '2026-09-02', 'TEST AGENT REMITTANCE (dummy)', 0, '', '', ''),
        (99980103, '1-6-8-29902', 0, 3000000, '2026-09-03', 'JOURNAL ENTRY - USD TO LKR (dummy)', 0, '', '', ''),
        (99980201, '1-6-8-29903', 2000000, 0, '2026-08-01', 'TEST OPENING JPY 1,000,000 @ 2.00 (dummy)', 0, '', '', ''),
        (99980202, '1-6-8-29903', 404000, 0, '2026-09-04', 'TEST JP AGENT (dummy)', 0, '', '', ''),
    ]
    vals = ','.join(f"({i}, '{l}', {dr}, {cr}, '{desc}', '{d}', {pv}, '{chk}', '{payee}', '{tour}', 'test', NOW(), '')" for i, l, dr, cr, d, desc, pv, chk, payee, tour in rows)
    sql(f"INSERT INTO account_runing (ID, L_Code, Dr, Cr, Discription, Date, Recpt_No, Chk_No, payee, index_no, Cashier, Dt, inv_total) VALUES {vals}")
    ids = {}
    for label, no, cur, code in [('TEST LKR account (dummy)', 'TEST-LKR-0001', 'LKR', '1-6-8-29901'), ('TEST USD account (dummy)', 'TEST-USD-0002', 'USD', '1-6-8-29902'), ('TEST JPY account (dummy)', 'TEST-JPY-0003', 'JPY', '1-6-8-29903')]:
        have = sql(f"SELECT id FROM tpd_bank_account WHERE label = '{label}'")
        if have:
            ids[cur] = int(have.split()[0]); continue
        r = adm.c.s.post(adm.c.base + 'master_files/bank_accounts/save', data={'label': label, 'bank_name': 'TEST BANK (dummy)', 'account_name': 'Avenir Holidays TEST', 'account_no': no, 'currency': cur,
            'ledger_code': code, 'active': '0', 'external_display': 'masked', 'display_digits': '4', 'note': 'dummy account for the reconciliation tests', adm.name: adm.token()}, headers={'X-Requested-With': 'XMLHttpRequest'})
        ids[cur] = int(r.json().get('id'))
    return ids

def grant_menus():
    for cap, groups in GROUPS.items():
        urls = ['accounts/daily_bank_rec'] + ([f'accounts/daily_bank_rec/{cap}'] if cap in ('prepare', 'review') else [])
        for g in groups:
            for u in urls:
                mid = sql(f"SELECT id FROM menu_list WHERE url = '{u}' AND del = 0 LIMIT 1")
                if mid and sql(f"SELECT COUNT(*) FROM user_privileges WHERE group_id = {g} AND menu_id = {mid}") == '0':
                    uid = sql(f"SELECT COALESCE(MIN(user_id), 1) FROM user_privileges WHERE group_id = {g}") or '1'
                    sql(f"INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES ({g}, {uid}, {mid})")

def main():
    sig0 = sql(LEDGER_SIG)
    a0 = int(sql("SELECT COALESCE(MAX(id),0) FROM tpd_audit"))
    adm = BR('admin'); adm.page()
    # ---------------- setup
    code, j = adm.post('setup')
    check('set-up by the System Administrator creates the tables and privileges (admin only)', code == 200 and sql("SELECT v FROM tpd_meta WHERE k='brc_schema_version'") == '1', (code, j.get('message')))
    code, j = adm.post('setup')
    check('set-up again changes nothing (idempotent)', code == 200 and 'nothing to do' in j.get('message', ''), j.get('message'))
    if os.environ.get('BRC_RESET', '1') == '1':
        # start from empty reconciliation tables (test instances only; every row in them is test data)
        for t in ['brc_match_item', 'brc_match', 'brc_mark', 'brc_transfer', 'brc_correction', 'brc_opening', 'brc_day', 'brc_line', 'brc_statement', 'brc_fx_rate', 'brc_account_map']:
            sql(f"DELETE FROM {t}")
    grant_menus()
    ids = setup_dummy(adm)
    check('three dummy TEST bank accounts exist in the Avenir Bank Accounts master (inactive, so payments never offer them)', len(ids) == 3 and sql("SELECT COUNT(*) FROM tpd_bank_account WHERE label LIKE 'TEST % account (dummy)' AND active = 0") == '3', ids)
    prep, rev, view = BR('accountant'), BR('approver'), BR('bank')
    for u in (prep, rev, view):
        u.page()
    # ---------------- permissions on pages and data
    for who in ['requester', 'ops', 'transh', 'hotelres', 'quote', 'hotel', 'transport', 'mgmt']:
        u = BR(who)
        r = u.page()
        c2, j2 = u.get('api/accounts')
        c3, j3 = u.post('mapping/propose', {'account': ids['LKR'], 'rec_start': '2026-09-01'})
        check(f'{who}: no access (page refused, data 403, change 403)', r.status_code in (302, 303) and c2 == 403 and c3 == 403, (r.status_code, c2, c3))
    c, j = view.get('api/accounts')
    check('view-only profile (bank authoriser) sees the accounts, masked', c == 200 and all('•' in a['account_no_masked'] for a in j['accounts']), c)
    c, j = view.post('mapping/propose', {'account': ids['LKR'], 'rec_start': '2026-09-01'})
    check('view-only profile cannot propose a mapping (403)', c == 403, c)
    c, j = prep.post('mapping/propose', {'account': ids['LKR'], 'rec_start': '2026-09-01'}, csrf=False)
    check('a change without the CSRF token is refused (403)', c == 403, (c, j.get('message')))
    # ---------------- mapping (four eyes)
    maps = {}
    for cur in ('LKR', 'USD'):
        c, j = prep.post('mapping/propose', {'account': ids[cur], 'rec_start': '2026-09-01', 'note': 'test'})
        maps[cur] = j.get('id')
        check(f'{cur}: preparer proposes the mapping (first day 1 Sep 2026)', c == 200 and maps[cur], (c, j.get('message')))
    c, j = prep.post('mapping/decide', {'map_id': maps['LKR'], 'decision': 'confirm'})
    check('the preparer cannot confirm (no review privilege, 403)', c == 403, c)
    c, j = adm.post('mapping/propose', {'account': ids['JPY'], 'rec_start': '2026-09-01'}); maps['JPY'] = j.get('id')
    c, j = adm.post('mapping/decide', {'map_id': maps['JPY'], 'decision': 'confirm'})
    check('four eyes: the proposer cannot confirm their own mapping, even as administrator (403)', c == 403, (c, j.get('message')))
    c, j = rev.post('mapping/decide', {'map_id': maps['USD'], 'decision': 'reject', 'note': 'x'})
    check('a rejection needs a reason (400)', c == 400, c)
    for cur in ('LKR', 'USD', 'JPY'):
        c, j = rev.post('mapping/decide', {'map_id': maps[cur], 'decision': 'confirm', 'note': 'checked against the master'})
        check(f'{cur}: reviewer confirms the mapping', c == 200, (c, j.get('message')))
    c, j = view.get('api/day', account=ids['LKR'], date='2026-09-05')
    check('before any statement the day is "Unreconciled — insufficient data"', c == 200 and j['day']['status_code'] == 'insufficient' and j['day']['status_label'] == 'Unreconciled — insufficient data', j.get('day', {}).get('status_label'))
    # ---------------- rates
    for d, r in [('2026-09-01', '300.00'), ('2026-09-02', '300.50'), ('2026-09-03', '301.00'), ('2026-09-04', '301.00'), ('2026-09-05', '302.00')]:
        prep.post('rate/save', {'currency': 'USD', 'rate_date': d, 'rate': r, 'source': 'TEST rate (dummy)'})
    for d, r in [('2026-09-01', '2.00'), ('2026-09-04', '2.02'), ('2026-09-05', '2.03')]:
        prep.post('rate/save', {'currency': 'JPY', 'rate_date': d, 'rate': r, 'source': 'TEST rate (dummy)'})
    c, j = prep.post('rate/save', {'currency': 'USD', 'rate_date': '2026-09-04', 'rate': '301.50', 'source': 'TEST rate (dummy)'})
    check('changing an existing rate needs a reason (400)', c == 400, c)
    c, j = prep.post('rate/save', {'currency': 'USD', 'rate_date': '2026-09-04', 'rate': '301.50', 'source': 'TEST rate (dummy)', 'note': 'corrected test rate'})
    au = sql(f"SELECT CONCAT(action,'|',prev_value LIKE '%301.000000%','|',new_value LIKE '%301.50%') FROM tpd_audit WHERE id > {a0} AND action = 'brc_rate_changed' ORDER BY id DESC LIMIT 1")
    check('a rate change is kept with the old and new value', c == 200 and au == 'brc_rate_changed|1|1', (c, au))
    # ---------------- LKR statement: upload with a balance break, edit, accept
    broken = [list(r) for r in LKR_ROWS]; broken[4][6] = '749,970.00 CR'
    files = {'file': ('combank_test_sep.csv', csv_bytes(broken), 'text/csv')}
    c, j = prep.post('statement/preview', {'account': ids['LKR'], 'source': 'upload', 'opening_balance': '1,000,000.00 CR', 'currency': 'LKR'}, files=files)
    ch = j.get('checks', {})
    check('preview recognises the columns and reports the balance break, keeping nothing', c == 200 and j.get('map', {}).get('money_out') == 4 and any('printed balance 749970.00' in e for e in ch.get('errors', [])) and not any('Line 5:' in e for e in ch.get('errors', [])) and sql(f"SELECT COUNT(*) FROM brc_statement WHERE bank_account_id = {ids['LKR']}") == '0', (c, ch.get('errors'), j.get('map')))
    check('categories recognised: 4 bank charges, 1 transfer, receipts and payments', ch.get('summary', {}).get('categories') == {'payment': 5, 'charge': 4, 'transfer': 1, 'receipt': 1}, ch.get('summary', {}).get('categories'))
    files = {'file': ('combank_test_sep.csv', csv_bytes(broken), 'text/csv')}
    c, j = prep.post('statement/save', {'account': ids['LKR'], 'source': 'upload', 'opening_balance': '1,000,000.00 CR', 'currency': 'LKR'}, files=files)
    sA = j.get('id')
    check('the statement is kept as a draft (nothing posted)', c == 200 and sA and sql(f"SELECT status FROM brc_statement WHERE id = {sA}") == 'draft', (c, j.get('message')))
    c, j = prep.post('statement/accept', {'id': sA})
    check('a draft with a balance break cannot be accepted (409)', c == 409, (c, j.get('message')))
    line4 = sql(f"SELECT id FROM brc_line WHERE statement_id = {sA} AND line_no = 4")
    c, j = prep.post('line/edit', {'id': line4, 'balance': '749,975.00 CR', 'reason': 'typed wrongly in the test file'})
    orig = sql(f"SELECT JSON_UNQUOTE(JSON_EXTRACT(orig_json, '$.balance')) FROM brc_line WHERE id = {line4}")
    check('a draft line is corrected with a reason; the imported value is kept', c == 200 and orig == '749970.00' and sql(f"SELECT CONCAT(balance,'|',edited) FROM brc_line WHERE id = {line4}") == '749975.00|1', (c, orig))
    c, j = prep.post('statement/accept', {'id': sA})
    check('the corrected statement is accepted (closing 3,893,106.00)', c == 200 and sql(f"SELECT CONCAT(status,'|',closing_balance) FROM brc_statement WHERE id = {sA}") == 'accepted|3893106.00', (c, j.get('message')))
    c, j = prep.post('line/edit', {'id': line4, 'balance': '1.00', 'reason': 'should be refused'})
    check('a line of an accepted statement cannot be changed (409)', c == 409, c)
    files = {'file': ('combank_test_sep_again.csv', csv_bytes(LKR_ROWS), 'text/csv')}
    c, j = prep.post('statement/save', {'account': ids['LKR'], 'source': 'upload', 'opening_balance': '1,000,000.00 CR'}, files=files)
    sB = j.get('id'); w = ' '.join(j.get('checks', {}).get('warnings', [])); e = ' '.join(j.get('checks', {}).get('errors', []))
    check('importing the same statement again is flagged (duplicate-looking lines, overlapping period)', c == 200 and 'look the same' in w and 'overlaps accepted statement' in e, (w, e))
    c, j = prep.post('statement/accept', {'id': sB})
    check('the overlapping copy cannot be accepted (409)', c == 409, c)
    c, j = prep.post('statement/void', {'id': sB, 'reason': 'imported twice in the test'})
    check('the duplicate draft is voided by the preparer (kept for the record)', c == 200 and sql(f"SELECT status FROM brc_statement WHERE id = {sB}") == 'void', c)
    # ---------------- USD statement typed in, JPY statement with one amount column
    usd = [{'date': '2026-09-02', 'description': 'TEST AGENT REMITTANCE ABC TRAVEL', 'money_in': '5,000.00', 'balance': '55,000.00'},
           {'date': '2026-09-03', 'description': 'TRF TO TEST-LKR-0001', 'money_out': '10,000.00', 'balance': '45,000.00'},
           {'date': '2026-09-05', 'description': 'TRF CHGS USD', 'money_out': '15.00', 'balance': '44,985.00'}]
    c, j = prep.post('statement/save', {'account': ids['USD'], 'source': 'manual', 'opening_balance': '50,000.00', 'closing_balance': '44,985.00', 'period_from': '2026-09-01', 'period_to': '2026-09-05', 'lines': usd})
    sU = j.get('id'); c2, j2 = prep.post('statement/accept', {'id': sU})
    lk = sql(f"SELECT GROUP_CONCAT(CONCAT(TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM fx_rate)),'*',money_in-money_out,'=',lkr_amount) ORDER BY line_no) FROM brc_line WHERE statement_id = {sU}")
    check('USD statement typed in and accepted; each line keeps USD and its LKR value at the day rate', c == 200 and c2 == 200 and lk == '300.5*5000.00=1502500.00,301*-10000.00=-3010000.00,302*-15.00=-4530.00', (c, c2, lk))
    jpy = [['Date', 'Narrative', 'Amount'], ['2026-09-04', 'TEST JP AGENT YAMADA TRAVEL', '200000']]
    files = {'file': ('jpy_test.csv', csv_bytes(jpy), 'text/csv')}
    c, j = prep.post('statement/save', {'account': ids['JPY'], 'source': 'upload', 'opening_balance': '1000000', 'closing_balance': '1,200,000', 'period_from': '2026-09-01', 'period_to': '2026-09-05', 'date_format': 'Y-m-d'}, files=files)
    sJ = j.get('id'); c2, j2 = prep.post('statement/accept', {'id': sJ})
    check('JPY statement (one amount column, no balances) accepted; LKR value 404,000.00', c == 200 and c2 == 200 and sql(f"SELECT lkr_amount FROM brc_line WHERE statement_id = {sJ}") == '404000.00', (c, c2, j.get('message'), j2.get('message')))
    # ---------------- LKR: before matching
    c, j = view.get('api/day', account=ids['LKR'], date='2026-09-05'); d = j['day']; r = d['reconciliation']
    check('LKR 5 Sep: bank 3,893,106.00 and book 3,547,655.00 shown separately; difference 345,451.00', d['bank']['closing'] == '3893106.00' and d['book']['closing'] == '3547655.00' and r['difference_lkr'] == '345451.00', (d['bank'], d['book'], r))
    check('the opening difference 150,000.00 on 1 Sep is "Unreconciled — insufficient data" until explained', r['opening_difference_lkr'] == '150000.00' and d['status_code'] == 'insufficient' and any('opening difference' in x for x in d['reasons']), (r['opening_remaining'], d['reasons']))
    check('the explanation adds up: unexplained remainder 0.00', r['unexplained_lkr'] == '0.00', r)
    # ---------------- suggestions and matching
    c, j = prep.get('api/suggest', account=ids['LKR'], **{'from': '2026-09-01', 'to': '2026-09-05'})
    sug = j.get('suggestions', [])
    byline = {s['line']['description']: s for s in sug}
    check('suggestions: PV 90001 one-to-one (high), PV 90002 one line to two ledger rows (high), the cheque from before the first day, the transfer',
          byline.get('AH 9901 - TEST HOTEL A - 90001', {}).get('confidence') == 'high' and len(byline.get('AH 9902 - TEST SUPPLIER B - 90002', {}).get('book', [])) == 2 and byline.get('CHQ 000123 TEST CHEQUE', {}).get('book', [{}])[0].get('key') == '99980002.1' and byline.get('TRF FROM TEST-USD-0002', {}).get('book', [{}])[0].get('key') == '99980006.1',
          [(s['line']['description'], s['confidence'], [b['key'] for b in s['book']]) for s in sug])
    check('the two identical 75,000 ledger rows are not auto-suggested as a sure match; they are flagged duplicate-looking', byline.get('AH 9903 - TEST GUIDE C - 90003', {}).get('confidence') in (None, 'low') and any(x['side'] == 'book' and len(x['items']) == 2 for x in j.get('duplicates', [])), (byline.get('AH 9903 - TEST GUIDE C - 90003'), j.get('duplicates')))
    for desc in ['AH 9901 - TEST HOTEL A - 90001', 'AH 9902 - TEST SUPPLIER B - 90002', 'CHQ 000123 TEST CHEQUE', 'TRF FROM TEST-USD-0002']:
        s = byline[desc]
        c, j = prep.post('match/create', {'account': ids['LKR'], 'line_ids': [s['line']['id']], 'book_keys': [b['key'] for b in s['book']], 'method': 'suggested'})
        check(f'accepted suggestion: {desc}', c == 200, (c, j.get('message')))
    lid = lambda desc: sql(f"SELECT id FROM brc_line WHERE statement_id = {sA} AND description = '{desc}'")
    c, j = prep.post('match/create', {'account': ids['LKR'], 'line_ids': [lid('AH 9901 - TEST HOTEL A - 90001')], 'book_keys': ['99980007.1']})
    check('a line already matched cannot be matched again (409)', c == 409, c)
    c, j = prep.post('match/create', {'account': ids['LKR'], 'line_ids': [lid('AH 9905 - TEST JEEP D - 90005')], 'book_keys': ['99980010.1']})
    check('a match with a difference (12,344 vs 12,345) needs a reason (400)', c == 400 and 'differ' in j.get('message', ''), j.get('message'))
    c, j = prep.post('match/create', {'account': ids['LKR'], 'line_ids': [lid('AH 9905 - TEST JEEP D - 90005')], 'book_keys': ['99980010.1'], 'diff_reason': 'rounding'})
    check('matched with the 1.00 rounding difference recorded', c == 200, j.get('message'))
    c, j = prep.post('match/create', {'account': ids['LKR'], 'line_ids': [lid('AH 9903 - TEST GUIDE C - 90003')], 'book_keys': ['99980007.1'], 'note': 'one of the two identical ledger rows'})
    check('manual match: the 75,000 payment with the first ledger row', c == 200, j.get('message'))
    c, j = prep.post('mark/create', {'account': ids['LKR'], 'side': 'book', 'ref': '99980008.1', 'mark': 'duplicate', 'related': '99980007.1', 'note': 'same PV 90003 and amount as 99980007 — to investigate'})
    check('the second 75,000 ledger row is classified duplicate-looking (not deleted, not changed)', c == 200 and sql("SELECT COUNT(*) FROM account_runing WHERE ID = 99980008") == '1', j.get('message'))
    c, j = prep.post('mark/create', {'account': ids['LKR'], 'side': 'book', 'ref': '99980009.1', 'mark': 'timing', 'expected_date': '2026-09-08', 'note': 'PV 90004 paid after 5 Sep'})
    check('the 40,000 ledger row is a timing item (expected 8 Sep)', c == 200, j.get('message'))
    c, j = view.get('api/day', account=ids['LKR'], date='2026-09-05'); d = j['day']; r = d['reconciliation']
    check('after matching the cheque from 30 Aug the opening difference is fully explained (remaining 0.00)', r['opening_remaining'] == '0.00' and r['opening_explained_by_earlier_book_items'] == '150000.00', r)
    check('still "items to explain": 4 charges and the cash deposit have no correction yet', d['status_code'] == 'open_items' and d['counts']['unexplained_items'] == 5, (d['status_label'], d['counts']))
    # ---------------- corrections (never posted)
    charge_ids = sql(f"SELECT GROUP_CONCAT(id) FROM brc_line WHERE statement_id = {sA} AND category = 'charge'").split(',')
    c, j = prep.post('correction/charges', {'account': ids['LKR'], 'line_ids': charge_ids})
    cC = j.get('id')
    ent = json.loads(sql(f"SELECT entry_json FROM brc_correction WHERE id = {cC}") or '[]') if cC else []
    check('bank charges (4 lines, 4,550.00) proposed as a correction: Dr the bank-charges account, Cr the bank ledger', c == 200 and [e['dr'] for e in ent] == ['4550.00', '0.00'] and ent[1]['ledger_code'] == '1-6-8-29901', (c, j.get('message'), ent))
    c, j = prep.post('correction/propose', {'account': ids['LKR'], 'kind': 'unbooked_receipt', 'description': 'Cash deposit 4 Sep not in the ledger (test)', 'entry_date': '2026-09-04',
        'lines': [{'ledger_code': '1-6-8-29901', 'dr': '235000', 'cr': ''}, {'ledger_code': '2-9-99-29909', 'dr': '', 'cr': '230000'}], 'line_ids': [lid('CASH DEPOSIT')]})
    check('an entry that does not balance is refused (400)', c == 400 and 'does not balance' in j.get('message', ''), j.get('message'))
    c, j = prep.post('correction/propose', {'account': ids['LKR'], 'kind': 'unbooked_receipt', 'description': 'Cash deposit 4 Sep not in the ledger (test)', 'entry_date': '2026-09-04',
        'lines': [{'ledger_code': '1-6-8-29901', 'dr': '235000', 'cr': ''}, {'ledger_code': '2-9-99-29909', 'dr': '', 'cr': '235000'}], 'line_ids': [lid('CASH DEPOSIT')]})
    cR = j.get('id')
    check('the cash deposit is proposed as a correction', c == 200 and cR, j.get('message'))
    sig_mid = sql(LEDGER_SIG); dummy_rows = sql("SELECT COUNT(*) FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999")
    check('proposing corrections wrote nothing to the ledger (real rows unchanged, dummy rows still 15)', sig_mid == sig0 and dummy_rows == '15', (sig0, sig_mid, dummy_rows))
    c, j = prep.post('correction/decide', {'id': cC, 'decision': 'approve'})
    check('the preparer cannot approve (no review privilege, 403)', c == 403, c)
    c, j = adm.post('correction/propose', {'account': ids['LKR'], 'kind': 'other', 'description': 'admin test proposal', 'entry_date': '2026-09-05', 'lines': [{'ledger_code': '1-6-8-29901', 'dr': '1', 'cr': ''}, {'ledger_code': '2-9-99-29909', 'dr': '', 'cr': '1'}]})
    cA = j.get('id'); c, j = adm.post('correction/decide', {'id': cA, 'decision': 'approve'})
    check('four eyes: the administrator cannot approve their own proposal (403)', c == 403, c)
    adm.post('correction/withdraw', {'id': cA, 'reason': 'test proposal withdrawn'})
    for cid in (cC, cR):
        c, j = rev.post('correction/decide', {'id': cid, 'decision': 'approve', 'note': 'test approval'})
        check(f'reviewer approves correction {cid}; the message says it is not posted', c == 200 and 'does not post' in j.get('message', ''), j.get('message'))
    c, j = prep.post('correction/posted', {'id': cR, 'ledger_id': '123456789'})
    check('recording a posting for a ledger entry that does not exist is refused (404)', c == 404, (c, j.get('message')))
    check('approving corrections wrote nothing to the ledger', sql(LEDGER_SIG) == sig0 and sql("SELECT COUNT(*) FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999") == '15', '')
    c, j = view.get('api/day', account=ids['LKR'], date='2026-09-05'); d = j['day']; r = d['reconciliation']
    check('LKR 5 Sep: every item explained → "Reconciled — all items explained (awaiting review)"', d['status_code'] == 'explained' and r['unexplained_lkr'] == '0.00', (d['status_label'], d['reasons'], d['counts']))
    exp = {'bank_items_not_in_book': '230450.00', 'book_items_not_on_statement': '115000.00', 'match_differences': '1.00', 'opening_remaining': '0.00', 'difference_lkr': '345451.00'}
    check('the difference 345,451.00 = statement items not in the ledger 230,450.00 + ledger items not on the statement 115,000.00 + rounding 1.00', all(r[k] == v for k, v in exp.items()), r)
    c, j = view.get('api/day', account=ids['LKR'], date='2026-09-03'); d3 = j['day']
    check('3 Sep (earlier day): the 40,000 row is not yet due, the deposit not yet in; status explained', d3['status_code'] == 'explained' and d3['reconciliation']['unexplained_lkr'] == '0.00', (d3['status_label'], d3['reasons']))
    # ---------------- transfers and USD / JPY
    c, j = prep.get('api/rates', **{'from': '2026-09-01', 'to': '2026-09-05'})
    ts = j.get('transfer_suggestions', [])
    check('the USD → LKR transfer is suggested (USD 10,000 out on 3 Sep, LKR 3,000,000 in on 3 Sep, implied 300.00)', len(ts) == 1 and ts[0]['implied_rate'] in ('300', '300.0'), ts)
    c, j = prep.post('transfer/create', {'from_line': ts[0]['from']['line_id'], 'to_line': ts[0]['to']['line_id'], 'note': 'test transfer'})
    tr = sql("SELECT CONCAT(TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM implied_rate)),'|',TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM daily_rate)),'|',rate_difference_lkr) FROM brc_transfer ORDER BY id DESC LIMIT 1")
    check('transfer recorded on both sides with the day rate 301.00 and the difference −10,000.00 LKR', c == 200 and tr == '300|301|-10000.00', (c, tr, j.get('message')))
    ul = lambda desc: sql(f"SELECT id FROM brc_line WHERE statement_id = {sU} AND description = '{desc}'")
    c, j = prep.post('match/create', {'account': ids['USD'], 'line_ids': [ul('TEST AGENT REMITTANCE ABC TRAVEL')], 'book_keys': ['99980102.1'], 'diff_reason': 'exchange rate'})
    c2, j2 = prep.post('match/create', {'account': ids['USD'], 'line_ids': [ul('TRF TO TEST-LKR-0001')], 'book_keys': ['99980103.1'], 'diff_reason': 'exchange rate'})
    check('USD lines matched to the LKR ledger with their exchange-rate differences (+2,500.00, −10,000.00)', c == 200 and c2 == 200 and sql(f"SELECT GROUP_CONCAT(difference_lkr ORDER BY id) FROM brc_match WHERE bank_account_id = {ids['USD']} AND status='active'") == '2500.00,-10000.00', (j.get('message'), j2.get('message')))
    c, j = prep.post('correction/charges', {'account': ids['USD'], 'line_ids': [ul('TRF CHGS USD')]})
    rev.post('correction/decide', {'id': j.get('id'), 'decision': 'approve'})
    c, j = view.get('api/day', account=ids['USD'], date='2026-09-05'); d = j['day']; r = d['reconciliation']
    check('USD 5 Sep: bank USD 44,985.00 (LKR 13,585,470.00 at 302.00), book LKR 13,500,000.00, difference 85,470.00', d['bank']['closing'] == '44985.00' and d['bank']['closing_lkr'] == '13585470.00' and d['book']['closing'] == '13500000.00' and r['difference_lkr'] == '85470.00', (d['bank'], d['book']))
    check('USD: explained by rate differences −7,500.00, the charge −4,530.00 and the revaluation 97,500.00; status explained', r['match_differences'] == '-7500.00' and r['bank_items_not_in_book'] == '-4530.00' and r['revaluation'] == '97500.00' and r['unexplained_lkr'] == '0.00' and d['status_code'] == 'explained', (r, d['reasons']))
    c, j = prep.post('match/create', {'account': ids['JPY'], 'line_ids': [sql(f"SELECT id FROM brc_line WHERE statement_id = {sJ}")], 'book_keys': ['99980202.1']})
    c2, j2 = view.get('api/day', account=ids['JPY'], date='2026-09-05'); d = j2['day']; r = d['reconciliation']
    check('JPY 5 Sep: matched exactly; revaluation 32,000.00 explains the difference; status explained', c == 200 and r['difference_lkr'] == '32000.00' and r['revaluation'] == '32000.00' and d['status_code'] == 'explained', (j.get('message'), r, d['reasons']))
    # ---------------- daily record (four eyes)
    c, j = prep.post('day/prepare', {'account': ids['LKR'], 'date': '2026-09-05', 'note': 'test'}); d1 = j.get('id')
    check('the preparer records the LKR day', c == 200 and d1, j.get('message'))
    c, j = prep.post('day/review', {'id': d1, 'decision': 'reviewed'})
    check('the preparer cannot review (403)', c == 403, c)
    c, j = rev.post('day/review', {'id': d1, 'decision': 'returned', 'note': 'please re-check the charges (test)'})
    check('the reviewer returns the record with a reason', c == 200, j.get('message'))
    c, j = prep.post('day/prepare', {'account': ids['LKR'], 'date': '2026-09-05', 'note': 'charges re-checked'}); d2 = j.get('id')
    c, j = rev.post('day/review', {'id': d2, 'decision': 'reviewed', 'note': 'ok'})
    c2, j2 = view.get('api/day', account=ids['LKR'], date='2026-09-05')
    check('prepared again and reviewed → "Reconciled — reviewed"', c == 200 and j2['day']['status_code'] == 'reviewed', (j.get('message'), j2['day']['status_label']))
    # ---------------- undo keeps the record; void of an accepted statement
    mid = sql(f"SELECT id FROM brc_match WHERE bank_account_id = {ids['LKR']} AND status = 'active' ORDER BY id DESC LIMIT 1")
    c, j = prep.post('match/undo', {'id': mid, 'reason': 'test undo'})
    c2, j2 = view.get('api/day', account=ids['LKR'], date='2026-09-05')
    check('undoing a match keeps it (status undone) and the day falls back to "items to explain"', c == 200 and sql(f"SELECT status FROM brc_match WHERE id = {mid}") == 'undone' and j2['day']['status_code'] == 'open_items', (j.get('message'), j2['day']['status_label']))
    c, j = prep.post('match/create', {'account': ids['LKR'], 'line_ids': [lid('AH 9903 - TEST GUIDE C - 90003')], 'book_keys': ['99980007.1'], 'note': 're-matched after the undo test'})
    c, j = prep.post('statement/void', {'id': sA, 'reason': 'should be refused'})
    check('the preparer cannot void an accepted statement (403: reviewer only)', c == 403, (c, j.get('message')))
    c, j = rev.post('statement/void', {'id': sA, 'reason': 'test: refused while it has matches'})
    check('an accepted statement with matches cannot be voided until they are undone (409)', c == 409, (c, j.get('message')))
    # ---------------- audit
    acts = sql(f"SELECT GROUP_CONCAT(DISTINCT action ORDER BY action) FROM tpd_audit WHERE id > {a0}").split(',')
    need = ['access_denied', 'brc_classified', 'brc_correction_approved', 'brc_correction_proposed', 'brc_correction_withdrawn', 'brc_day_prepared', 'brc_day_returned', 'brc_day_reviewed', 'brc_line_changed', 'brc_mapping_confirmed', 'brc_mapping_proposed',
            'brc_match_undone', 'brc_matched', 'brc_rate_changed', 'brc_rate_entered', 'brc_setup', 'brc_statement_accepted', 'brc_statement_kept', 'brc_statement_voided', 'brc_transfer_recorded']
    check('every kind of change is in the audit trail with user, time and values', all(a in acts for a in need), sorted(set(need) - set(acts)))
    row = sql(f"SELECT CONCAT(user,'|',LEFT(at,10),'|',prev_value IS NOT NULL,'|',new_value LIKE '%749975.00%','|',reason) FROM tpd_audit WHERE id > {a0} AND action = 'brc_line_changed' LIMIT 1")
    check('the line change is audited with user, date, the value before (as imported) and after, and the reason', row.startswith('tcaccount|') and row.endswith('|1|1|typed wrongly in the test file'), row)
    den = int(sql(f"SELECT COUNT(*) FROM tpd_audit WHERE id > {a0} AND action = 'access_denied'"))
    check(f'refusals are audited too ({den} rows)', den >= 20, den)
    # ---------------- the ledger was never written
    check('the real ledger is unchanged: rows, sums and every row\'s reconciliation flags (aflag, bank_rec, bankst_flag, BankFlg)', sql(LEDGER_SIG) == sig0, (sig0, sql(LEDGER_SIG)))
    check('the dummy ledger rows are unchanged too (15 rows; the module only reads the ledger)', sql("SELECT CONCAT(COUNT(*),'|',SUM(Dr),'|',SUM(Cr)) FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999") == '15|22904000.00|3452345.00', sql("SELECT CONCAT(COUNT(*),'|',SUM(Dr),'|',SUM(Cr)) FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999"))
    json.dump({'ids': ids, 'statements': {'lkr': sA, 'lkr_dup': sB, 'usd': sU, 'jpy': sJ}}, open(HERE + '/test_ids.json', 'w'))

if __name__ == '__main__':
    try:
        main()
    finally:
        out = os.environ.get('BRC_RESULTS', HERE + '/results_bankrec.json')
        json.dump(RES, open(out, 'w'), indent=1)
        print('%d/%d passed' % (sum(r['ok'] for r in RES), len(RES)))
