"""tf-bankrec-r2 — the r2 screens in a real browser (dummy data left by test_bankrec_r2.py), with screenshots.
Users: the test copy's accountant (prepare), approver (review), bank approver (view). States needed for a picture are
made through the signed-in page itself (BRC.post) and put back afterwards; dummy ledger rows only."""
import asyncio, os, sys, json, re
from playwright.async_api import async_playwright
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, S + '/paydesk'); sys.path.insert(0, HERE)
from api import ACC
from test_bankrec import sql
HOST = os.environ.get('BRC_HOST', 'http://127.0.0.1:8081'); BASE = HOST + '/avenir/'
OUT = os.environ.get('BRC_SHOTS', HERE + '/shots_r2'); os.makedirs(OUT, exist_ok=True)
IDS = json.load(open(HERE + '/test_ids_r2.json'))
res = []
def check(n, ok, d=''):
    res.append((n, bool(ok), str(d)[:300])); print(('  ok   ' if ok else '  FAIL ') + n + ('' if ok else '  ' + str(d)[:300]))

async def login(ctx, who):
    u, p = ACC[who]
    pg = await ctx.new_page()
    errs = []
    pg.on('pageerror', lambda e: errs.append(str(e)) if '/assets/' not in (getattr(e, 'stack', '') or '') else None)
    for attempt in range(6):
        await pg.goto(BASE, wait_until='load')
        await pg.fill('#username', u); await pg.fill('#password-input', p)
        async with pg.expect_response(lambda r: '/login' in r.url and r.request.method == 'POST', timeout=20000) as resp:
            await pg.click('#login_btn')
        j = await (await resp.value).json()
        if j.get('status') == 429:
            await pg.wait_for_timeout(int((float(j.get('retry_after') or 5) + 1) * 1000)); continue
        break
    await pg.wait_for_timeout(1200)
    return pg, errs

async def shot(pg, name, full=True):
    await pg.evaluate("document.querySelectorAll('#page-topbar, .navbar-header, header').forEach(e => { if (getComputedStyle(e).position === 'fixed') e.style.position = 'absolute'; })")
    await pg.screenshot(path=f'{OUT}/{name}.png', full_page=full)

async def tab(pg, href):
    await pg.click(f'a[href="#{href}"]'); await pg.wait_for_timeout(1000)

async def open_acct(pg, acct, date):
    await pg.goto(BASE + f'accounts/daily_bank_rec/account/{acct}', wait_until='load'); await pg.wait_for_timeout(1500)
    await pg.fill('#asAt', date); await pg.click('#btnRefresh'); await pg.wait_for_timeout(2500)

async def main():
    L, U, V = IDS['ids']['LKR'], IDS['ids']['USD'], IDS['b']['V']
    post = None
    async with async_playwright() as p:
        b = await p.chromium.launch()
        ctx = await b.new_context(viewport={'width': 1440, 'height': 900})
        pg, errs = await login(ctx, 'accountant')
        post = lambda path, data: pg.evaluate('([p, d]) => BRC.post(p, d)', [path, data])
        # ---- D9: a timing item in the suggestions, accepted, history
        await open_acct(pg, V, '2026-09-05')
        mJ = sql(f"SELECT m.id FROM brc_match m JOIN brc_match_item i ON i.match_id = m.id WHERE m.status = 'active' AND i.book_key = '99980305.1' AND m.bank_account_id = {V}")
        j = await post('match/undo', {'id': mJ, 'reason': 'screenshot: show the timing item in the suggestions (dummy)'})
        check('undo of the PV 90011 match restores the timing item (for the picture)', j['status'] == 200 and 'restored' in j['message'], j)
        await open_acct(pg, V, '2026-09-05'); await tab(pg, 'matching')
        await pg.click('#btnShowOpen'); await pg.wait_for_timeout(1200)
        await pg.click('#btnSuggest'); await pg.wait_for_timeout(2000)
        txt = await pg.evaluate("document.getElementById('suggestBox').innerText")
        check('D9 on screen: the timing item is in the suggestions with "accepting clears the classification"', 'timing item' in txt and 'accepting clears the classification' in txt and '90011' in txt, txt[:400])
        await shot(pg, '16_d9_timing_item_in_suggestions')
        btn = await pg.evaluate("(() => { const i = SUGG.findIndex(s => s.line.description.includes('90011')); return i; })()")
        await pg.click(f'#sg{btn}'); await pg.wait_for_timeout(2500)
        st = sql(f"SELECT CONCAT(k.status,'|',k.created_by) FROM brc_mark k WHERE k.bank_account_id = {V} AND k.book_key = '99980305.1' ORDER BY k.id LIMIT 1")
        check('accepting it on screen clears the classification (status matched; classified by tcaccount kept)', st == 'matched|tcaccount', st)
        await pg.evaluate("loadOpen()"); await pg.wait_for_timeout(1500)
        await pg.evaluate("document.getElementById('markHistTable').scrollIntoView()"); await pg.wait_for_timeout(500)
        txt = await pg.evaluate("document.getElementById('markHistTable').innerText")
        check('the history shows the timing classification cleared, restored and cleared again, with users and times', txt.count('cleared by match') >= 2 and 'restored' in txt, txt[:300])
        await shot(pg, '17_d9_timing_history_after_match')
        # ---- partial clearing on screen: undo the completion, show, complete again
        pc = sql(f"SELECT id FROM brc_match WHERE bank_account_id = {V} AND status = 'active' AND supersedes_id IS NOT NULL ORDER BY id DESC LIMIT 1")
        j = await post('match/undo', {'id': pc, 'reason': 'screenshot: show the partial match (dummy)'})
        await open_acct(pg, V, '2026-09-05')
        txt = await pg.evaluate("document.getElementById('partialCard').innerText")
        check('partial clearing on screen: "Partly cleared (rest outstanding)" −40,000.00 expected 2026-09-05', '40,000.00' in txt and '2026-09-05' in txt, txt[:200])
        await shot(pg, '18_partial_clearing_summary')
        await tab(pg, 'matching'); await pg.click('#btnShowOpen'); await pg.wait_for_timeout(1200)
        await pg.click('#btnSuggest'); await pg.wait_for_timeout(1800)
        txt = await pg.evaluate("document.getElementById('suggestBox').innerText")
        check('the 5 Sep line is suggested as completing the partial match', 'completes partial match' in txt, txt[:300])
        await shot(pg, '19_partial_completion_suggested')
        btn = await pg.evaluate("SUGG.findIndex(s => s.type === 'complete')")
        await pg.click(f'#sg{btn}'); await pg.wait_for_timeout(2500)
        check('completed on screen (the partial match superseded)', sql(f"SELECT COUNT(*) FROM brc_match WHERE bank_account_id = {V} AND status = 'active' AND kind = 'partial'") == '0', '')
        # ---- D10: opening items at day 1 and day 3; candidates
        await open_acct(pg, V, '2026-09-01')
        txt = await pg.evaluate("document.getElementById('openNowTable').innerText + ' ' + document.getElementById('recKv').innerText")
        check('D10 on screen, 1 Sep: both cheques outstanding; opening difference explained (not yet explained 0.00)', txt.count('outstanding') >= 2 and '50,000.00' in txt, txt[:400])
        await shot(pg, '20_d10_opening_items_day1')
        await open_acct(pg, V, '2026-09-03')
        txt = await pg.evaluate("document.getElementById('openNowTable').innerText")
        check('D10 on screen, 3 Sep: cheque 000201 cleared 2026-09-03, cheque 000202 still outstanding', 'cleared 2026-09-03' in txt and 'outstanding' in txt, txt[:300])
        await shot(pg, '21_d10_opening_items_day3')
        await tab(pg, 'corrections'); await pg.wait_for_timeout(1200)
        await pg.evaluate("document.getElementById('candTable').scrollIntoView()"); await pg.wait_for_timeout(400)
        txt = await pg.evaluate("document.getElementById('candTable').innerText")
        check('the ledger rows booked before the first day are listed with their effect and opening-item state', '99980302.1' in txt and 'accepted' in txt and '20,000.00' in txt, txt[:300])
        await shot(pg, '22_d10_candidates_and_corrections_posted')
        # ---- pending postings (LKR 5 Sep: approved, not posted; reviewed)
        await open_acct(pg, L, '2026-09-05')
        txt = await pg.evaluate("document.getElementById('banners').innerText + ' | ' + document.getElementById('statusPill').innerText")
        check('pending postings on screen: "Pending postings — the ledger is NOT corrected … approved but not posted" on the reviewed day', 'NOT corrected' in txt and 'approved but not posted' in txt and 'Reviewed — ledger not yet corrected' in txt, txt[:400])
        await shot(pg, '23_pending_postings_on_reviewed_day')
        await tab(pg, 'corrections'); await pg.wait_for_timeout(1200)
        txt = await pg.evaluate("document.getElementById('corrTable').innerText")
        check('the corrections say "Approved — not posted (ledger not corrected)"', 'Approved — not posted (ledger not corrected)' in txt, txt[:300])
        await shot(pg, '24_corrections_approved_not_posted')
        await tab(pg, 'days'); await pg.wait_for_timeout(1500)
        txt = await pg.evaluate("document.getElementById('daysTable').innerText")
        check('daily records show what was not posted when prepared, and the record\'s state now', 'approved but not posted' in txt and 'current' in txt, txt[:300])
        await shot(pg, '25_daily_records_pending_postings')
        # ---- changed since review (a dummy ledger row edited, then put back)
        sql("UPDATE account_runing SET Discription = 'VOUCHER PAYMENT - TEST VENDOR L (dummy, edited)' WHERE ID = 99980307")
        try:
            await open_acct(pg, V, '2026-09-04')
            txt = await pg.evaluate("document.getElementById('banners').innerText")
            check('changed since review on screen: red banner "Changed since review — re-review needed … ledger rows"', 'Changed since review' in txt and 'ledger rows' in txt, txt[:300])
            await shot(pg, '26_changed_since_review_banner')
            await tab(pg, 'days'); await pg.wait_for_timeout(1500)
            await shot(pg, '27_daily_records_rereview_needed')
            rid = await pg.evaluate("(() => { const r = [...document.querySelectorAll('#daysTable tbody tr')].find(t => t.innerText.includes('2026-09-04') && t.innerText.includes('re-review needed')); return r ? r.cells[0].innerText : null; })()")
            await pg.evaluate(f"viewRecord({rid})"); await pg.wait_for_timeout(1500)
            txt = await pg.evaluate("document.querySelector('.swal2-html-container').innerText")
            check('the reviewed snapshot is shown as it was, with "re-review needed" and the data now', 'Status when prepared' in txt and 're-review needed' in txt and 'Now:' in txt, txt[:300])
            await shot(pg, '28_reviewed_snapshot_kept', full=False)
            await pg.keyboard.press('Escape'); await pg.wait_for_timeout(400)
            # the overview and the reviewer's list while the record is out of date
            await pg.goto(BASE + 'accounts/daily_bank_rec', wait_until='load'); await pg.wait_for_timeout(3000)
            txt = await pg.evaluate('document.body.innerText')
            check('overview: "Ledger not corrected" and "Re-review needed" flags per account', 'Ledger not corrected' in txt and 'Re-review needed' in txt, '')
            await shot(pg, '29_overview_flags')
            ctx2 = await b.new_context(viewport={'width': 1440, 'height': 900})
            rv, errs2 = await login(ctx2, 'approver')
            await rv.goto(BASE + 'accounts/daily_bank_rec', wait_until='load'); await rv.wait_for_timeout(4000)
            txt = await rv.evaluate("document.getElementById('reviewList').innerText")
            check('the reviewer\'s list shows "Re-review needed" for the changed record', 'Re-review needed' in txt, txt[:300])
            await rv.evaluate("document.getElementById('reviewCard').scrollIntoView()")
            await shot(rv, '30_reviewer_rereview_list')
            check('no script error on the reviewer\'s pages', not errs2, errs2[:3])
        finally:
            sql("UPDATE account_runing SET Discription = 'VOUCHER PAYMENT - TEST VENDOR L (dummy)' WHERE ID = 99980307")
        # ---- exchange-rate differences (USD)
        await open_acct(pg, U, '2026-09-05')
        txt = await pg.evaluate("document.getElementById('diffTable').innerText + ' ' + document.getElementById('recKv').innerText")
        check('USD on screen: each exchange-rate difference with its explanation; the revaluation with its rate and source', 'exchange rate' in txt and 'credited at the 2 Sep rate' in txt and '97,500.00' in txt and 'TEST rate (dummy)' in txt, txt[:400])
        await shot(pg, '31_usd_exchange_differences_explained')
        await tab(pg, 'days'); await pg.wait_for_timeout(1200)
        await shot(pg, '32_usd_record_with_fx_explanation')
        check('no script error on the preparer\'s pages', not errs, errs[:3])
        # ---- phone width
        ctx5 = await b.new_context(viewport={'width': 390, 'height': 844}, device_scale_factor=2)
        ph, _ = await login(ctx5, 'accountant')
        await open_acct(ph, L, '2026-09-05')
        await shot(ph, '33_phone_summary_pending')
        await b.close()
    print('%d/%d ok' % (sum(1 for r in res if r[1]), len(res)))
    json.dump(res, open(HERE + '/results_ui_r2.json', 'w'), indent=1)

asyncio.run(main())
