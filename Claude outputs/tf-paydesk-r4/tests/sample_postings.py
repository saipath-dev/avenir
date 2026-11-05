"""tf-paydesk-r4 — sample balanced postings reconciled to the bank debit, read back from the test copy through the batch page's data."""
import os, sys, subprocess
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
os.environ['TC_HOST'] = 'http://127.0.0.1:8081'; sys.path.insert(0, HERE)
from api import Client
def sql(q): return subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '-uroot', '-N', 'avenir_test', '-e', q], capture_output=True, text=True).stdout.strip()
acct = Client('accountant')
out = ['tf-paydesk-r4 — SAMPLE BALANCED POSTINGS RECONCILED TO THE BANK DEBIT (test copy, read back from account_runing through the batch page)', 'Every entry id carries two rows (Dr and Cr). "payment" rows are the chain\'s own authorisation posting; "charges" and "fx" rows are the desk\'s, one balanced pair per tour line.', '']
m = lambda v: '{:,.2f}'.format(float(v))
for sup, title in [('6-9-11-11047', 'A. USD batch, TWO tours, exchange LOSS and bank charges 300.00 — Cassamara Hotel (AH 2218 + AH 2290)'), ('6-9-11-11590', 'B. LKR batch, TWO tours with UNEQUAL lines, bank charges 75.55 allocated 48.35 / 27.20 — Gimanhala expenses'), ('6-9-11-11496', 'C. USD single tour, exchange GAIN, charges 200.00, paid from the second Avenir account (Sampath) — Kassapa part 2'), ('6-9-11-10626', 'D. USD single tour, same-day rate (no difference), charges 150.00 — COVENRO')]:
    bid = sql(f"SELECT b.id FROM tpd_batch b JOIN tpd_bank_approval a ON a.vnum=b.vnum WHERE b.sup_acc='{sup}' AND a.reconciled=1 ORDER BY b.id DESC LIMIT 1")
    d = acct.get(f'tour/tour_payment_dashboard/batch_data/{bid}')['data']; r = d['reconciliation']; app = d['approval']; b = d['batch']
    out.append(title); out.append('-' * len(title))
    out.append(f"Batch {b['batch_no']} · Payment Voucher No. {b['vnum']} · paid {app['paid_date']} · paying account {r['bank_ledger']} {r['bank_name']}" + (f" · USD {m(app['usd_amount'])} × {float(app['usd_rate']):.2f}" if app.get('usd_rate') else ''))
    out.append(f"Final LKR {m(app['lkr_amount'])} (provisional at approval {m(app['approval_lkr'])}, difference {('+' if float(app['fx_diff'] or 0) >= 0 else '')}{m(app['fx_diff'] or 0)}) + bank charges {m(app['charges'] or 0)} = TOTAL DEBITED {m(app['total_debit'])}")
    out.append('')
    out.append(f"{'entry':>6}  {'kind':<9} {'account':<16} {'name':<34} {'tour':<5} {'Dr':>12} {'Cr':>12}  description")
    for x in r['rows']:
        out.append(f"{x['entry']:>6}  {x['group']:<9} {x['account']:<16} {x['name'][:34]:<34} {x['tour']:<5} {m(x['dr']) if x['dr'] else '':>12} {m(x['cr']) if x['cr'] else '':>12}  {x['description'][:70]}")
    out.append(f"{'':>6}  {'':<9} {'':<16} {'TOTALS':<34} {'':<5} {m(r['totals']['dr']):>12} {m(r['totals']['cr']):>12}")
    out.append('')
    for c in r['checks']:
        e = m(c['expected']) if isinstance(c['expected'], (int, float)) else c['expected']; a = m(c['actual']) if isinstance(c['actual'], (int, float)) else c['actual']
        out.append(f"  [{'ok' if c['ok'] else 'FAIL'}] {c['what']}: expected {e} — in the ledger {a}")
    out.append(f"  => {'RECONCILED' if r['reconciled'] else 'NOT RECONCILED'}: credits to {r['bank_ledger']} = {m(r['totals']['bank_credit'])} = the bank debit {m(r['totals']['total_debit'])}")
    shares = sql(f"SELECT GROUP_CONCAT(CONCAT(tour_id,' → ',IFNULL(charge_share,'0.00')) ORDER BY tour_id SEPARATOR ' · ') FROM tpd_request_ext WHERE batch_id={bid}")
    out.append(f"  Charge share recorded on each tour's payment record (tpd_request_ext.charge_share): {shares}")
    out.append('')
open(HERE + '/sample-postings.txt', 'w').write('\n'.join(out) + '\n'); print('\n'.join(out))
