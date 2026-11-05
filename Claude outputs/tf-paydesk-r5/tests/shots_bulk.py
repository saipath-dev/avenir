"""tf-paydesk-r5 — screenshots of the bulk supplier payment on the TEST COPY (post-suite snapshot restored by bulk_reset.sh or by hand):
Supplier Payables with Bulk pay, the review (select all eligible, excluded list), the confirmation, the bulk batch page through
initiation and Paid, the grouped batches listing, the itemised remittance advice, a separation-of-duties refusal."""
import asyncio, os, sys, subprocess, time, uuid
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, os.path.join(S, 'tct'))
os.environ['TC_HOST'] = 'http://127.0.0.1:8081'
import tc
from api import ACC, Client
tc.ACC.update(ACC)
from playwright.async_api import async_playwright
OUT = os.path.join(HERE, 'shots_bulk'); os.makedirs(OUT, exist_ok=True)
errs = []
def sql(q):
    return subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '-uroot', '-N', 'avenir_test', '-e', q], capture_output=True, text=True).stdout.strip()
req, appr, acct, bank, admin = Client('requester'), Client('approver'), Client('accountant'), Client('bank'), Client('admin')
TOUR, MID, TOUR2, MID2 = '2325', 'AH 2218', '2390', 'AH 2290'
GIM = '6-9-11-11590'; KAS = '6-9-11-11496'
def desk(c, mid): return c.get('tour/tour_payment_dashboard/desk', manual_id=mid)['data']
def line(d, key): return next((l for l in d['lines'] if l['key'] == key), None)
def expense(c, tour, mid, sup, desc, amount, cur='LKR', date='2026-10-13', inv=None):
    j = c.post('tour/tour_payment_dashboard/expense', data={'tour_id': tour, 'service_date': date, 'category': 'Meal', 'supplier_acc': sup, 'description': desc, 'currency': cur, 'amount': str(amount), 'invoice_no': inv or ''})
    assert j.get('status') == 200, j
    j2 = c.post('tour/tour_payment_dashboard/expense_request', data={'expense_id': j['id']}); assert j2.get('status') == 200, j2
    return max(t['pr_id'] for t in line(desk(req, mid), 'expense:%s' % j['id'])['transactions'] if t.get('pr_id'))
COM = sql("SELECT id FROM tpd_bank_account WHERE ledger_code='1-6-8-10380' ORDER BY id LIMIT 1")
TODAY = desk(req, MID)['today']

def demo_items():
    """six Gimanhala lunches on two tours (approved), one disputed, one not approved yet; a Kassapa item with an unverified bank"""
    prs = []
    for i, a in enumerate([2500, 1800, 3200, 2750, 1950, 2500]):
        tour, mid = (TOUR, MID) if i % 2 == 0 else (TOUR2, MID2)
        pr = expense(req, tour, mid, GIM, 'Lunch — day %d' % (i + 1), a, date=('2026-10-1%d' % (2 + i % 4)) if tour == TOUR else ('2026-10-2%d' % (i % 3)), inv='GIM-%04d' % (7710 + i))
        assert appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': pr}).get('status') == 200; prs.append(pr)
    prD = expense(req, TOUR, MID, GIM, 'Lunch — group of 12 (invoice queried)', 30000, inv='GIM-7725')
    assert appr.post('tour/tour_payment_dashboard/approve', data={'pr_id': prD}).get('status') == 200
    acct.post('tour/tour_payment_dashboard/dispute', data={'pr_id': prD, 'disputed': 1, 'reason': 'invoice total does not match the menu agreed (12 pax × 2,500)'})
    expense(req, TOUR2, MID2, GIM, 'Lunch — day 7 (not yet approved)', 2650, date='2026-10-22', inv='GIM-7726')
    prK = expense(req, TOUR, MID, KAS, 'Kassapa extra night supplement', 5000, inv='KAS-0091')
    return prs

async def login(ctx, who):
    await ctx.route('**/*', lambda route: route.continue_() if '127.0.0.1' in route.request.url else route.abort())   # no CDN waits in the sandbox
    pg, body = await tc.login(ctx, who)
    pg.on('pageerror', lambda e: errs.append(who + ': ' + str(e)))
    return pg
async def goto(pg, path, wait=None):
    await pg.goto(tc.BASE + path, wait_until='load')
    if wait: await pg.wait_for_selector(wait, timeout=30000)
    await pg.wait_for_timeout(1200)
async def modal_shot(pg, modal_id, path):
    await pg.wait_for_selector(f'#{modal_id}.show', timeout=8000); await pg.wait_for_timeout(700)
    bb = await pg.locator(f'#{modal_id} .modal-content').bounding_box()
    await pg.screenshot(path=path, clip={'x': bb['x'] - 10, 'y': max(0, bb['y'] - 10), 'width': bb['width'] + 20, 'height': bb['height'] + 20})
async def clip(pg, sel, path, pad=0):
    el = pg.locator(sel).first; await el.scroll_into_view_if_needed(); await pg.wait_for_timeout(250)
    bb = await el.bounding_box(); vp = pg.viewport_size
    await pg.screenshot(path=path, clip={'x': max(0, bb['x'] - pad), 'y': max(0, bb['y'] - pad), 'width': min(vp['width'], bb['width'] + 2 * pad), 'height': bb['height'] + 2 * pad}, full_page=True)

async def main():
    prs = demo_items()
    async with async_playwright() as p:
        b, ctx = await tc.browser(p, 1440, 1500)
        # 1. management: Supplier Payables with the Bulk pay button, the disputed chip
        pg = await login(ctx, 'approver')
        await goto(pg, 'tour/tour_payment_dashboard/payables', '#pp_awaiting .sup')
        await pg.screenshot(path=f'{OUT}/01-payables-awaiting.png', full_page=True)
        gi = await pg.evaluate("() => _pp.awaiting.findIndex(s => s.sup_acc === '%s')" % GIM)
        await clip(pg, f'#pp_awaiting .sup:nth-child({gi + 1})', f'{OUT}/02-gimanhala-waiting.png', 4)
        # 2. the review: select all eligible, the excluded list with reasons
        await pg.evaluate("(i) => openBulk(i)", gi)
        await modal_shot(pg, 'bulkModal', f'{OUT}/03-bulk-review-select-all-eligible.png')
        # 3. the confirmation step with counts, totals, N vouchers, one transfer, the one-time reference
        await pg.evaluate("() => { document.getElementById('bk_bank').value = document.getElementById('bk_bank').options[1].value; bkReview(); }")
        await pg.wait_for_timeout(400)
        await modal_shot(pg, 'bulkModal', f'{OUT}/04-bulk-confirm.png')
        # 4. confirm → the vouchers are issued; the success dialog
        await pg.evaluate("() => { submitBulk(); return 1; }")
        await pg.wait_for_selector('.swal2-popup', timeout=30000); await pg.wait_for_timeout(600)
        bb = await pg.locator('.swal2-popup').bounding_box()
        await pg.screenshot(path=f'{OUT}/05-bulk-created.png', clip={'x': bb['x'] - 10, 'y': bb['y'] - 10, 'width': bb['width'] + 20, 'height': bb['height'] + 20})
        await pg.click('.swal2-cancel'); await pg.wait_for_timeout(1500)
        B = sql("SELECT MAX(id) FROM tpd_batch WHERE mode='bulk_parent'")
        # 5. the batches tab: the bulk parent with its roll-up, children grouped under it
        await pg.evaluate("() => ppTab('batches')"); await pg.wait_for_timeout(400)
        await clip(pg, '#pp_batches', f'{OUT}/06-batches-grouped.png', 4)
        # 6. the bulk batch page (issued)
        await goto(pg, f'tour/tour_payment_dashboard/bulk/{B}')
        await pg.screenshot(path=f'{OUT}/07-bulk-page-issued.png', full_page=True)
        # 7. the accountant: Bank Payment Check lists each voucher; initiation of the ONE transfer with charges
        pa = await login(ctx, 'accountant')
        await goto(pa, 'report/bank_payment_check')
        await pa.screenshot(path=f'{OUT}/08-bank-payment-check-children.png', full_page=True)
        await goto(pa, f'tour/tour_payment_dashboard/bulk/{B}')
        await pa.screenshot(path=f'{OUT}/09-bulk-page-accountant-initiate.png', full_page=True)
        j = acct.post('tour/tour_payment_dashboard/bulk_initiate', data={'bulk_id': B, 'bank_account_id': COM, 'payment_date': TODAY, 'bank_ref': 'CB-7781-DEMO', 'charges': '120.00', 'note': 'demo'})
        print('initiate:', j.get('status'), (j.get('message') or '')[:120])
        await goto(pa, f'tour/tour_payment_dashboard/bulk/{B}')
        await pa.screenshot(path=f'{OUT}/10-bulk-page-initiated.png', full_page=True)
        # 8. separation of duties: the accountant who initiated cannot mark Paid (server refusal, even as administrator)
        j = acct.post('tour/tour_payment_dashboard/bulk_paid', data={'bulk_id': B, 'paid_date': TODAY, 'bank_ref': 'CB-7781-DEMO', 'bank_account_id': COM, 'confirm_account': 1})
        print('accountant tries Paid:', j.get('status'), (j.get('message') or '')[:160])
        # 9. the bank approver marks the whole transfer Paid; ledger balanced over the batch
        pb = await login(ctx, 'bank')
        await goto(pb, f'tour/tour_payment_dashboard/bulk/{B}')
        await pb.screenshot(path=f'{OUT}/11-bulk-page-bank-approver.png', full_page=True)
        j = bank.post('tour/tour_payment_dashboard/bulk_paid', data={'bulk_id': B, 'paid_date': TODAY, 'bank_ref': 'CB-7781-DEMO', 'bank_account_id': COM, 'confirm_account': 1})
        print('paid:', j.get('status'), (j.get('message') or '')[:160])
        await goto(pb, f'tour/tour_payment_dashboard/bulk/{B}')
        await pb.screenshot(path=f'{OUT}/12-bulk-page-paid-ledger-balanced.png', full_page=True)
        # 10. the itemised remittance advice
        j = req.post('tour/tour_payment_dashboard/bulk_remittance_prepare', data={'bulk_id': B}); print('advice:', j.get('status'))
        await goto(pb, f'tour/tour_payment_dashboard/bulk_remittance/{B}')
        await pb.screenshot(path=f'{OUT}/13-itemised-remittance-advice.png', full_page=True)
        # 11. the tour record / desk shows each lunch with its own voucher number
        pd = await login(ctx, 'requester')
        await goto(pd, 'tour/tour_payment_dashboard?manual_id=' + MID.replace(' ', '%20'), '#tpd_days .line')
        await pd.evaluate("() => { const l = document.querySelector('[data-key^=\"expense:\"]'); }")
        await pd.screenshot(path=f'{OUT}/14-dashboard-own-voucher-per-item.png', full_page=True)
        # 12. phone width: the review modal
        b2, ctx2 = await tc.browser(p, 420, 900)
        pm = await login(ctx2, 'approver')
        await goto(pm, 'tour/tour_payment_dashboard/payables', '#pp_awaiting')
        await pm.screenshot(path=f'{OUT}/15-phone-payables.png', full_page=True)
        await b.close(); await b2.close()
    print('errors:', errs or 'none')

asyncio.run(main())
