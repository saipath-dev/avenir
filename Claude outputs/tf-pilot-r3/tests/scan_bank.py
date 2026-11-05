r"""tf-pilot-r3 — TEST COPY ONLY. Seeds a distinct fake bank account number on every supplier (originals kept in a scratch table and
put back at the end), then requests every GET route of the application as the given test sign-ins (Hotel Reservations first) with
sample ids and parameters, and looks for any full supplier / Avenir bank account number in what comes back (pages, JSON, exports,
PDFs as text). Writes results_scan_<who>.json."""
import os, sys, json, re, subprocess, time
from api import Client
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE); SOCK = S + '/mysql.sock'
def sql(q, db='avenir_test'):
    r = subprocess.run(['mysql', '--socket=' + SOCK, '-uroot', '-N', db, '-e', q], capture_output=True, text=True)
    if r.returncode != 0: raise SystemExit('SQL failed: ' + r.stderr + '\n' + q[:300])
    return r.stdout.strip()
WHO = sys.argv[1:] or ['hotelres']
SKIP = re.compile(r'(^|/)(add|remove|delete|destroy|update|create|save|store|cancel|approve|reject|finalize|finalized_mark|mark|send|post|insert|confirm|verify|setup|initiate|paid|release|allocate|assign|upload|import|generate|reset|edit|logout|login|backup_database|db_backup|db_backup_view|run1|run2|run3|clear|sync|cron|toggle|process|close|lock|unlock|restore|archive|year_end|test|install|migrate)(/|_|$)', re.I)
routes = json.load(open(HERE + '/routes_get.json'))
# ---- seed (test copy only) ----
had = sql("SHOW TABLES LIKE 'zz_scan_sup_bak'")
if not had:
    sql("CREATE TABLE zz_scan_sup_bak AS SELECT ID, sup_bank_acc FROM supplier")
sql("UPDATE supplier SET sup_bank_acc = CONCAT('97', LPAD(ID, 10, '0'))")
KIND = {x: 'supplier' for x in sql("SELECT sup_bank_acc FROM supplier").split('\n') if x}
for t, c, k in [('tpd_supplier_bank', 'account_no', 'supplier'), ('tpd_bank_account', 'account_no', 'avenir')]:
    try:
        for x in sql(f"SELECT {c} FROM {t}").split('\n'):
            d = re.sub(r'\D', '', x)
            if len(d) >= 8: KIND[d] = k
    except SystemExit:
        pass
secrets = set(KIND)
print('secrets', len(secrets))
NUMS = ['1', '2390', '2325', '8061', '635', '32', '40', '17', '7']
ANYS = ['6-9-11-11590', 'AH 2290', '2390']
QS = {'manual_id': 'AH 2290', 'tour_id': '2390', 'acc': '6-9-11-11590', 'sup_acc': '6-9-11-11590', 'id': '1', 'cat': '99', 'from': '2024-01-01', 'to': '2026-12-31', 'date1': '2024-01-01', 'date2': '2026-12-31', 'start_date': '2024-01-01', 'end_date': '2026-12-31', 'q': '', 'search_location': 'a', 'date': '2026-10-15', 'hotelId': '6-9-11-11590', 'hotel': '6-9-11-11590', 'inquiry_id': '1', 'destination_city': 'a', 'location': 'a', 'search': 'a', 'term': 'a'}
def variants(p):
    if '(:num)' not in p and '(:any)' not in p and '(:segment)' not in p and '(:alpha)' not in p and '(:alphanum)' not in p:
        return [p]
    out = [p]
    for ph, vals in [('(:num)', NUMS), ('(:any)', ANYS), ('(:segment)', ['6-9-11-11590', 'pdf', '1']), ('(:alpha)', ['a']), ('(:alphanum)', ['1'])]:
        nxt = []
        for q in out:
            if ph in q:
                nxt += [q.replace(ph, v, 1) for v in vals]
            else:
                nxt.append(q)
        out = nxt
    out = [q for q in out if '(:' not in q]
    return out[:12]
def leaks(body):
    found = set()
    for d in re.findall(r'\d{8,}', body):
        if d in secrets: found.add(d)
    for m in re.findall(r'(?:\d[ -]?){8,}', body):
        d = re.sub(r'\D', '', m)
        if d in secrets: found.add(d)
    return sorted(found)
try:
    for who in WHO:
        c = Client(who)
        res = {'who': who, 'requests': 0, 'ok200': 0, 'skipped': [], 'leaks': [], 'errors': []}
        t0 = time.time()
        for p in routes:
            if SKIP.search(p):
                res['skipped'].append(p); continue
            for v in variants(p):
                try:
                    r = c.s.get(c.base + v, params=QS, allow_redirects=False, timeout=60)
                except Exception as e:
                    res['errors'].append([v, str(e)[:100]]); continue
                res['requests'] += 1
                res.setdefault('codes', {}); res['codes'][str(r.status_code)] = res['codes'].get(str(r.status_code), 0) + 1
                if r.status_code in (301, 302, 303, 307):
                    loc = r.headers.get('Location', '')
                    if re.search(r'/avenir/?$|login', loc) and '403' not in loc:
                        chk = c.s.get(c.base + 'dashboard2/tiles', allow_redirects=False)
                        if chk.status_code != 200:
                            res.setdefault('session_lost_after', []).append(v)
                            c = Client(who)
                    continue
                if r.status_code != 200:
                    continue
                res['ok200'] += 1
                body = r.text if 'pdf' not in r.headers.get('Content-Type', '') else r.content.decode('latin-1')
                L = leaks(body)
                res.setdefault('ok_routes', []).append(v)
                if L:
                    res['leaks'].append({'route': v, 'count': len(L), 'kind': sorted(set(KIND[x] for x in L)), 'example_last4': [x[-4:] for x in L[:3]], 'ctype': r.headers.get('Content-Type', '')[:40]})
        res['seconds'] = round(time.time() - t0)
        json.dump(res, open(HERE + f'/results_scan_{who}.json', 'w'), indent=1)
        print(who, 'requests', res['requests'], '200s', res['ok200'], 'supplier-number leaks', sum(1 for l in res['leaks'] if 'supplier' in l['kind']), 'Avenir-account pages', sum(1 for l in res['leaks'] if l['kind'] == ['avenir']))
        for l in res['leaks']:
            print('   ', l)
finally:
    sql("UPDATE supplier s JOIN zz_scan_sup_bak b ON b.ID = s.ID SET s.sup_bank_acc = b.sup_bank_acc")
    sql("DROP TABLE zz_scan_sup_bak")
    print('suppliers restored')
