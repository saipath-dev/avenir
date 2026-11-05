"""tf-paydesk-r4 — results.txt from results.json (flow_test.py) + results_rollback.json (rollback_test.py)."""
import json, os, datetime
HERE = os.path.dirname(os.path.abspath(__file__))
names = {int(k): v for k, v in json.load(open(HERE + '/test_names_r3.json')).items()}
names.update({
    41: 'r4 — ZERO bank charges: nothing posted, lock reconciled (USD and LKR payments)',
    42: 'r4 — NONZERO bank charges: posted once to the mapped Bank Charges account against the paying account, reconciled to the bank debit',
    43: 'r4 — MISSING bank-charge mapping blocks a Paid that carries charges (no partial entry); zero charges never need it; mapping by Accounts only',
    44: 'r4 — MULTI-TOUR allocation of charges (equal and unequal lines, to the cent), shown in each tour\'s payment record and on the documents',
    45: 'r4 — DUPLICATE voucher attempts: counter behind the table refused before anything is written; the next number issued cleanly',
    46: 'r4 — REPEATED and CONCURRENT Paid submissions: one succeeds, the rest refused; one set of postings',
    47: 'r4 — ROLLBACK before any payment and after payments exist (live-equivalent copy, real installer): database untouched, the previous application handles the records, re-apply loses nothing',
})
res = json.load(open(HERE + '/results.json')) + json.load(open(HERE + '/results_rollback.json'))
by = {}
for r in res:
    by.setdefault(int(r['test']), []).append(r)
out = ['tf-paydesk-r4 — test results (TEST COPY: 127.0.0.1:8081 avenir_test for tests 0–46; the live-equivalent copy 127.0.0.1:8083 avenir_rb for test 47)', 'Run ' + datetime.datetime.now().strftime('%Y-%m-%d %H:%M') + ' (server time). Every check below is a real request through the application as the role named, or a direct read of the test database.', '']
tot = ok = 0
for t in sorted(by):
    rows = by[t]; n = len(rows); k = sum(1 for r in rows if r['ok']); tot += n; ok += k
    out.append('%2d. %s: %s (%d/%d checks)' % (t, names.get(t, 'test %d' % t), 'PASS' if k == n else 'FAIL', k, n))
    for r in rows:
        out.append('       - %s   %s%s' % ('ok' if r['ok'] else 'FAIL', r['name'], '' if r['ok'] else '   [' + r['detail'] + ']'))
out.append('')
out.append('TOTAL: %d of %d checks passed, %d failed, across %d tests (0–47).' % (ok, tot, tot - ok, len(by)))
open(HERE + '/results.txt', 'w').write('\n'.join(out) + '\n')
print(out[-1])
