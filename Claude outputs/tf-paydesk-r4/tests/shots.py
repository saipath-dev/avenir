"""tf-paydesk-r2 — screenshots of the demonstration on the TEST COPY: the dashboard (desktop and phone), Supplier Payables, the
batch windows, the accountant's bank payment page, the bank approver's final approval page, the documents, the masters."""
import asyncio, os, sys, json, subprocess
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, os.path.join(S, 'tct'))
os.environ['TC_HOST'] = 'http://127.0.0.1:8081'
import tc
from api import ACC, Client
tc.ACC.update(ACC)
from playwright.async_api import async_playwright
OUT = os.path.join(HERE, 'shots'); os.makedirs(OUT, exist_ok=True)
MID = 'AH 2218'; MID2 = 'AH 2290'
errs = []
def sql(q):
    return subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '-uroot', '-N', 'avenir_test', '-e', q], capture_output=True, text=True).stdout.strip()

async def dash(ctx, who, mid=MID):
    pg, body = await tc.login(ctx, who)
    pg.on('pageerror', lambda e: errs.append(who + ': ' + str(e)))
    await pg.goto(tc.BASE + 'tour/tour_payment_dashboard?manual_id=' + mid.replace(' ', '%20'), wait_until='networkidle')
    await pg.wait_for_selector('#tpd_days .line', timeout=30000); await pg.wait_for_timeout(700)
    return pg

async def page(ctx, who, path, wait=None):
    pg, body = await tc.login(ctx, who)
    pg.on('pageerror', lambda e: errs.append(who + ' ' + path + ': ' + str(e)))
    await pg.goto(tc.BASE + path, wait_until='networkidle')
    if wait: await pg.wait_for_selector(wait, timeout=30000)
    await pg.wait_for_timeout(700)
    return pg

async def clip(pg, sel, path, pad=0):
    el = pg.locator(sel).first; await el.scroll_into_view_if_needed(); await pg.wait_for_timeout(250)
    bb = await el.bounding_box(); vp = pg.viewport_size
    await pg.screenshot(path=path, clip={'x': max(0, bb['x'] - pad), 'y': max(0, bb['y'] - pad), 'width': min(vp['width'], bb['width'] + 2 * pad), 'height': bb['height'] + 2 * pad}, full_page=True)

async def modal_shot(pg, modal_id, path):
    await pg.wait_for_selector(f'#{modal_id}.show', timeout=8000); await pg.wait_for_timeout(600)
    bb = await pg.locator(f'#{modal_id} .modal-dialog').bounding_box()
    await pg.screenshot(path=path, clip={'x': bb['x'] - 10, 'y': max(0, bb['y'] - 10), 'width': bb['width'] + 20, 'height': bb['height'] + 20})

def demo_waiting_item():
    """the demonstration needs an approved payment WAITING for its batch: COVENRO's night on the second tour AH 2290 ($55) — requested and approved here if not yet"""
    if sql("SELECT COUNT(*) FROM payment_request WHERE hotel_acc='6-9-11-10626' AND tour_id='2390' AND payment_request IN (1,4,6)") != '0':
        return
    c = Client('requester'); d = c.get('tour/tour_payment_dashboard/desk', manual_id=MID2)['data']; t = d['legacy']['tour_info']
    l = next(x for x in d['lines'] if x['key'].startswith('hotel:6-9-11-10626')); row = d['legacy']['payment_rows'][l['legacy_idx']]
    j = c.post('tour/tour_accounts/payment_request', data={'tourId': t['tour_id'], 'manualId': t['manual_id'], 'consignee': t['consignee'] or '', 'createUser': '', 'bankName': '', 'branchName': '', 'supBankAcc': '', 'totalHotelPay': row['total_dollar'], 'totalHotelAdvance': 0, 'dueAmount': row['total_dollar'], 'requestAmount': row['total_dollar'], 'tot_amt_doller': row['total_dollar'], 'hotelAcc': '6-9-11-10626', 'hotelName': row['supplier'], 'startDate': t['start_date'], 'endDate': t['end_date'], 'type': 'Hotel'})
    pr = sql("SELECT MAX(id) FROM payment_request WHERE hotel_acc='6-9-11-10626' AND tour_id='2390'")
    c.post('tour/tour_payment_dashboard/request_meta', data={'tour_id': t['tour_id'], 'acc': '6-9-11-10626', 'type': 'Hotel', 'invoice_no': 'COV-INV-7790', 'note': 'demo'})
    print('demo item (COVENRO on AH 2290):', j.get('status'), Client('approver').post('tour/tour_payment_dashboard/approve', data={'pr_id': pr}).get('status'))

async def main():
    demo_waiting_item()
    async with async_playwright() as p:
        # ---- the dashboard, desktop (admin sees everything) ----
        b, ctx = await tc.browser(p, 1440, 1600)
        pg = await dash(ctx, 'admin')
        await pg.screenshot(path=f'{OUT}/01-dashboard-desktop.png', full_page=True)
        await pg.screenshot(path=f'{OUT}/01a-dashboard-top.png', clip={'x': 0, 'y': 0, 'width': 1440, 'height': 1250})
        await clip(pg, '#tpd_sched_card', f'{OUT}/01b-dashboard-middle.png')
        await clip(pg, '#tpd_head', f'{OUT}/02-header.png')
        await clip(pg, '#tpd_kpis', f'{OUT}/03-kpis.png')
        await clip(pg, '#tpd_fx', f'{OUT}/04-exchange-rate.png')
        await pg.evaluate("() => toggleLine('hotel:6-9-11-11496')"); await pg.wait_for_timeout(500)
        await clip(pg, '#tpd_days .day:nth-child(2)', f'{OUT}/05-day2-kassapa-details.png')
        await pg.evaluate("() => toggleLine('hotel:6-9-11-11496')")
        await pg.evaluate("() => toggleLine('hotel:6-9-11-11047')"); await pg.wait_for_timeout(500)
        await clip(pg, '[data-key="hotel:6-9-11-11047"]', f'{OUT}/05b-cassamara-two-tour-batch.png')
        await pg.evaluate("() => toggleLine('hotel:6-9-11-11047')")
        await pg.evaluate("() => setFilter('paid')"); await pg.wait_for_timeout(400)
        await clip(pg, '#tpd_sched_card', f'{OUT}/06-filter-fully-paid.png')
        await pg.evaluate("() => setFilter('all')"); await pg.wait_for_timeout(300)
        await clip(pg, '#tpd_overall', f'{OUT}/07-progress.png', 8)
        await pg.evaluate("() => tpdHistTab('h')"); await pg.wait_for_timeout(200)
        await clip(pg, '#tpd_history', f'{OUT}/08-history.png', 8)
        await pg.evaluate("() => tpdHistTab('a')"); await pg.wait_for_timeout(300)
        await clip(pg, '#tpd_audit', f'{OUT}/09-audit.png', 8)
        await pg.evaluate("() => tpdView('classic')"); await pg.wait_for_timeout(500)
        await clip(pg, '#payment_table', f'{OUT}/10-classic-table.png', 8)
        await pg.evaluate("() => tpdView('modern')")
        await b.close()

        if os.environ.get('SKIP_ACTIONS'):
            await docs(p); await phone(p); print('errors:', json.dumps(errs[:10], indent=0)); return

        # ---- the steps, each as its role: the Galle Face tea (LKR 1,500, GALLERY CAFE) requested → approved → batch → bank page → Paid ----
        Client('accountant').post('master_files/supplier_bank/save', data={'sup_acc': '6-9-11-11612', 'beneficiary_name': 'Gallery Cafe Restaurant', 'bank_name': 'Test Bank', 'branch': 'Colombo 03', 'account_no': 'TEST1010010010', 'currency': 'LKR', 'remittance_email': 'gallery-test@example.invalid'})
        print('verify:', Client('bank').post('master_files/supplier_bank/verify', data={'sup_acc': '6-9-11-11612', 'note': 'demo: checked'}).get('message'))
        b, ctx = await tc.browser(p, 1440, 1200)
        pg = await dash(ctx, 'requester')
        key = await pg.evaluate("() => (_desk.lines.find(l => l.supplier_acc === '6-9-11-11612') || {}).key")
        await pg.evaluate(f"() => tpdRequest('{key}')")
        await modal_shot(pg, 'paymentRequestModal', f'{OUT}/11-request-modal.png')
        await pg.fill('#pr_invoice_no', 'GCR-5120'); await pg.fill('#pr_note', 'sunset tea, day 5')
        await pg.click('#pr_submit_btn'); await pg.wait_for_timeout(2500)
        await pg.keyboard.press('Enter'); await pg.wait_for_timeout(1500)
        await b.close()
        b, ctx = await tc.browser(p, 1440, 1200)
        pg = await dash(ctx, 'approver')
        await pg.evaluate(f"() => openApprove('{key}')")
        await pg.wait_for_selector('#approveModal.show', timeout=8000); await pg.wait_for_timeout(400)
        await pg.check('#ap_paynow'); await pg.select_option('#ap_bypass_reason', 'urgent_cash'); await pg.fill('#ap_bypass_note', 'cafe wants payment before the visit'); await pg.wait_for_timeout(300)
        await modal_shot(pg, 'approveModal', f'{OUT}/12-approve-modal.png')
        await pg.click('#ap_submit'); await pg.wait_for_timeout(3500); await pg.keyboard.press('Enter'); await pg.wait_for_timeout(1500)
        # Supplier Payables: the approved payments waiting, and the batch window on the Radisson (USD) item
        await pg.goto(tc.BASE + 'tour/tour_payment_dashboard/payables', wait_until='networkidle'); await pg.wait_for_selector('.sup', timeout=20000); await pg.wait_for_timeout(600)
        await pg.screenshot(path=f'{OUT}/12b-supplier-payables.png', full_page=True)
        idx = await pg.evaluate("() => _pp.awaiting.findIndex(s => s.sup_acc === '6-9-11-10626')")
        if idx >= 0:
            await pg.evaluate(f"() => openBatch({idx})")
            await modal_shot(pg, 'batchModal', f'{OUT}/12c-create-batch-window.png')
            await pg.keyboard.press('Escape'); await pg.wait_for_timeout(500)
        await pg.evaluate("() => ppTab('batches')"); await pg.wait_for_timeout(400)
        await pg.screenshot(path=f'{OUT}/12d-supplier-payables-batches.png', full_page=True)
        # the dashboard's own windows on the Radisson line (approved, waiting): Create batch, and Pay Individually Now — neither submitted
        await pg.goto(tc.BASE + 'tour/tour_payment_dashboard?manual_id=AH%202290', wait_until='networkidle'); await pg.wait_for_selector('#tpd_days .line', timeout=30000); await pg.wait_for_timeout(700)
        await pg.evaluate("() => openBatch('hotel:6-9-11-10626')")
        await modal_shot(pg, 'batchModal', f'{OUT}/13-dashboard-create-batch.png')
        await pg.keyboard.press('Escape'); await pg.wait_for_timeout(500)
        await pg.evaluate("() => openPayNow('hotel:6-9-11-10626')")
        await modal_shot(pg, 'payNowModal', f'{OUT}/13b-pay-individually-now.png')
        await pg.keyboard.press('Escape'); await pg.wait_for_timeout(300)
        await b.close()
        # the settings page (ledger mapping) and the configuration warning on a USD batch while the mapping is removed
        b, ctx = await tc.browser(p, 1440, 1000)
        pg = await page(ctx, 'accountant', 'tour/tour_payment_dashboard/settings', '#gain_code')
        await pg.fill('#acc_q', 'TEST'); await pg.click('#acc_q + button'); await pg.wait_for_timeout(800)
        await pg.screenshot(path=f'{OUT}/13c-settings-ledger-mapping.png', full_page=True)
        jr = Client('approver').post('tour/tour_payment_dashboard/batch_create', data={'sup_acc': '6-9-11-10626', 'pr_ids[]': [sql("SELECT id FROM payment_request WHERE hotel_acc='6-9-11-10626' AND tour_id='2390' AND payment_request IN (4,6) AND voucher_created=0 ORDER BY id DESC LIMIT 1")], 'bank_account_id': sql("SELECT id FROM tpd_bank_account WHERE is_default=1")})
        print('demo batch:', jr.get('status'), (jr.get('message') or '')[:80])
        rb = jr.get('batch_id')
        if rb:
            print('demo initiate:', Client('accountant').post('tour/tour_payment_dashboard/initiate', data={'vnum': jr['vnum'], 'bank_account_id': sql("SELECT id FROM tpd_bank_account WHERE is_default=1"), 'payment_date': sql("SELECT CURDATE()"), 'bank_ref': 'CB-TRF-000199', 'charges': '125'}).get('status'))
            sql("UPDATE tpd_meta SET v='' WHERE k IN ('fx_gain_ledger_code','fx_loss_ledger_code','bank_charge_ledger_code')")
            pg2, _ = await tc.login(ctx, 'bank')
            await pg2.goto(tc.BASE + f'tour/tour_payment_dashboard/batch/{rb}', wait_until='networkidle'); await pg2.wait_for_selector('.fig', timeout=20000); await pg2.wait_for_timeout(600)
            await pg2.screenshot(path=f'{OUT}/13d-paid-blocked-mapping-missing.png', full_page=True)
            sql("UPDATE tpd_meta SET v='4-8-109-31901' WHERE k='fx_gain_ledger_code'"); sql("UPDATE tpd_meta SET v='4-8-109-31902' WHERE k='fx_loss_ledger_code'")
            await pg2.goto(tc.BASE + f'tour/tour_payment_dashboard/batch/{rb}', wait_until='networkidle'); await pg2.wait_for_selector('.fig', timeout=20000); await pg2.wait_for_timeout(600)
            await pg2.screenshot(path=f'{OUT}/13e-paid-blocked-bank-charges-unmapped.png', full_page=True)
            sql("UPDATE tpd_meta SET v='4-8-109-31903' WHERE k='bank_charge_ledger_code'")
            await pg2.goto(tc.BASE + f'tour/tour_payment_dashboard/batch/{rb}', wait_until='networkidle'); await pg2.wait_for_selector('.fig', timeout=20000); await pg2.wait_for_timeout(600)
            await pg2.screenshot(path=f'{OUT}/16c-final-approval-charges-share-preview.png', full_page=True)
        await b.close()
        vn = sql("SELECT Vnum FROM voucher WHERE Acc_No='6-9-11-11612' ORDER BY Vnum DESC LIMIT 1")
        bid = sql(f"SELECT id FROM tpd_batch WHERE vnum={vn}") if vn else ''
        if not bid:
            raise SystemExit('the demo batch was not created: ' + json.dumps(errs[:5]))
        # the accountant: Bank Payment Check, then the batch's bank payment page → initiate
        b, ctx = await tc.browser(p, 1440, 1200)
        pg = await page(ctx, 'accountant', 'report/bank_payment_check')
        await pg.screenshot(path=f'{OUT}/14-bank-payment-check-accountant.png', full_page=False)
        await pg.goto(tc.BASE + f'tour/tour_payment_dashboard/batch/{bid}', wait_until='networkidle'); await pg.wait_for_selector('#f_submit', timeout=20000); await pg.wait_for_timeout(600)
        await pg.screenshot(path=f'{OUT}/15-bank-payment-page-accountant.png', full_page=True)
        sam = sql("SELECT id FROM tpd_bank_account WHERE label='Sampath LKR account'")
        await pg.select_option('#f_bank', sam); await pg.wait_for_timeout(300)
        await pg.fill('#f_account_reason', 'Com Bank balance low today'); await pg.fill('#f_charges', '50'); await pg.fill('#f_bank_ref', 'SB-TRF-000130'); await pg.fill('#f_batch_ref', 'BATCH-DEMO'); await pg.fill('#f_note', 'demo transfer')
        await pg.screenshot(path=f'{OUT}/15b-accountant-selects-other-account.png', full_page=True)
        await pg.click('#f_submit'); await pg.wait_for_timeout(3000)
        await b.close()
        # the bank approver: the final approval page → Paid
        b, ctx = await tc.browser(p, 1440, 1200)
        pg = await page(ctx, 'bank', f'tour/tour_payment_dashboard/batch/{bid}', '#f_submit')
        await pg.screenshot(path=f'{OUT}/16-final-approval-page-bank-approver.png', full_page=True)
        await pg.check('#f_confirm_account'); await pg.wait_for_timeout(200)
        await pg.click('#f_submit'); await pg.wait_for_timeout(800); await pg.keyboard.press('Enter'); await pg.wait_for_timeout(3500)
        await pg.screenshot(path=f'{OUT}/17-batch-paid-locked.png', full_page=True)
        await b.close()
        # a USD batch page with the approval rate beside the payment-date rate (Kassapa part 2: 329.10 → 325.00) and the two-tour Cassamara batch
        b, ctx = await tc.browser(p, 1440, 1200)
        k2 = sql("SELECT b.id FROM tpd_batch b WHERE b.sup_acc='6-9-11-11496' ORDER BY b.id DESC LIMIT 1")
        pg = await page(ctx, 'accountant', f'tour/tour_payment_dashboard/batch/{k2}', '.fig')
        await pg.screenshot(path=f'{OUT}/17b-batch-usd-rates-compared.png', full_page=True)
        c2 = sql("SELECT b.id FROM tpd_batch b WHERE b.sup_acc='6-9-11-11047' ORDER BY b.id DESC LIMIT 1")
        await pg.goto(tc.BASE + f'tour/tour_payment_dashboard/batch/{c2}', wait_until='networkidle'); await pg.wait_for_selector('.fig', timeout=20000); await pg.wait_for_timeout(600)
        await pg.screenshot(path=f'{OUT}/17c-batch-two-tours.png', full_page=True)
        g2 = sql("SELECT b.id FROM tpd_batch b WHERE b.sup_acc='6-9-11-11590' AND b.tour_count=2 ORDER BY b.id DESC LIMIT 1")
        if g2:
            await pg.goto(tc.BASE + f'tour/tour_payment_dashboard/batch/{g2}', wait_until='networkidle'); await pg.wait_for_selector('.fig', timeout=20000); await pg.wait_for_timeout(600)
            await pg.screenshot(path=f'{OUT}/17e-batch-charges-allocated-reconciled.png', full_page=True)
        await b.close()
        # the remittance window and Add expense (requester)
        b, ctx = await tc.browser(p, 1440, 1200)
        pg = await dash(ctx, 'requester')
        await pg.evaluate(f"() => openRemit('{key}')")
        await modal_shot(pg, 'remitModal', f'{OUT}/16b-remittance-modal.png')
        await pg.keyboard.press('Escape'); await pg.wait_for_timeout(400)
        await pg.evaluate("() => openExpenseModal()"); await pg.wait_for_timeout(600)
        await modal_shot(pg, 'expenseModal', f'{OUT}/17d-add-expense-modal.png')
        await b.close()
        await docs(p)
        await phone(p)
    print('errors:', json.dumps(errs[:10], indent=0))

async def docs(p):
    b, ctx = await tc.browser(p, 1100, 1400)
    pg, body = await tc.login(ctx, 'accountant')
    vn = sql("SELECT Vnum FROM voucher WHERE Acc_No='6-9-11-11047' AND Vnum > 605 ORDER BY Vnum DESC LIMIT 1")
    await pg.goto(tc.BASE + f'tour/tour_payment_dashboard/voucher/{vn}', wait_until='networkidle'); await pg.screenshot(path=f'{OUT}/18-payment-voucher.png', full_page=True)
    await pg.goto(tc.BASE + f'tour/tour_payment_dashboard/remittance/{vn}', wait_until='networkidle'); await pg.screenshot(path=f'{OUT}/19-remittance-advice.png', full_page=True)
    await pg.goto(tc.BASE + 'tour/tour_payment_dashboard/sheet?manual_id=AH%202218', wait_until='networkidle'); await pg.screenshot(path=f'{OUT}/20-export-sheet.png', full_page=True)
    await pg.goto(tc.BASE + 'master_files/bank_accounts', wait_until='networkidle'); await pg.screenshot(path=f'{OUT}/21-bank-account-master.png', full_page=False)
    await pg.goto(tc.BASE + 'master_files/supplier_bank?q=a', wait_until='networkidle'); await pg.screenshot(path=f'{OUT}/22-supplier-bank-details.png', full_page=False)
    await pg.goto(tc.BASE + 'tour/tour_accounts/payment_approval_v2', wait_until='networkidle'); await pg.wait_for_timeout(800); await pg.screenshot(path=f'{OUT}/22b-payment-approval-page-link.png', clip={'x': 0, 'y': 0, 'width': 1100, 'height': 420})
    await b.close()
    b, ctx = await tc.browser(p, 1300, 1400)
    pg, body = await tc.login(ctx, 'admin')
    await pg.goto(tc.BASE + 'tour/board/tour/4/record', wait_until='networkidle'); await pg.wait_for_timeout(600)
    y = await pg.evaluate("() => document.getElementById('s7').getBoundingClientRect().top + window.scrollY")
    await pg.screenshot(path=f'{OUT}/22c-complete-tour-record-payments.png', clip={'x': 0, 'y': max(0, y - 10), 'width': 1300, 'height': 560}, full_page=True)
    await b.close()

async def phone(p):
    b = await p.chromium.launch(); ctx = await b.new_context(viewport={'width': 390, 'height': 844}, is_mobile=True, has_touch=True, device_scale_factor=2)
    pg = await dash(ctx, 'approver')
    await pg.screenshot(path=f'{OUT}/23-phone-top.png', full_page=False)
    await pg.evaluate("() => { document.getElementById('tpd_sched_card').scrollIntoView(); }"); await pg.wait_for_timeout(400)
    await pg.screenshot(path=f'{OUT}/24-phone-schedule.png', full_page=False)
    await pg.evaluate("() => toggleLine('hotel:6-9-11-11496')"); await pg.wait_for_timeout(400)
    await pg.evaluate("() => { document.querySelector('[data-key=\"hotel:6-9-11-11496\"]').scrollIntoView(); }"); await pg.wait_for_timeout(300)
    await pg.screenshot(path=f'{OUT}/25-phone-line-details.png', full_page=False)
    await pg.screenshot(path=f'{OUT}/26-phone-full.png', full_page=True)
    k2 = sql("SELECT b.id FROM tpd_batch b WHERE b.sup_acc='6-9-11-11496' ORDER BY b.id DESC LIMIT 1")
    await pg.goto(tc.BASE + f'tour/tour_payment_dashboard/batch/{k2}', wait_until='networkidle'); await pg.wait_for_selector('.fig', timeout=20000); await pg.wait_for_timeout(500)
    await pg.screenshot(path=f'{OUT}/27-phone-batch-page.png', full_page=False)
    await pg.goto(tc.BASE + 'tour/tour_payment_dashboard/payables', wait_until='networkidle'); await pg.wait_for_timeout(1200)
    await pg.screenshot(path=f'{OUT}/28-phone-payables.png', full_page=False)
    await b.close()

asyncio.run(main())
