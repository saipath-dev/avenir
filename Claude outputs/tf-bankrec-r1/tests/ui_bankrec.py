"""tf-bankrec-r1 — the reconciliation screens in a real browser (dummy data from test_bankrec.py), with screenshots.
Host: BRC_HOST (default the cloud test copy http://127.0.0.1:8081). Users: the test copy's accountant (prepare),
approver (review) and bank approver (view)."""
import asyncio, os, sys, json, re
from playwright.async_api import async_playwright
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, S + '/paydesk'); sys.path.insert(0, HERE)
from api import ACC
import test_bankrec as T
HOST = os.environ.get('BRC_HOST', 'http://127.0.0.1:8081'); BASE = HOST + '/avenir/'
OUT = os.environ.get('BRC_SHOTS', HERE + '/shots'); os.makedirs(OUT, exist_ok=True)
IDS = json.load(open(HERE + '/test_ids.json'))
res = []
def check(n, ok, d=''):
    res.append((n, bool(ok), str(d)[:300])); print(('  ok   ' if ok else '  FAIL ') + n + ('' if ok else '  ' + str(d)[:300]))

async def login(ctx, who):
    u, p = ACC[who]
    pg = await ctx.new_page()
    errs = []
    pg.on('pageerror', lambda e: errs.append(str(e)) if '/assets/' not in (getattr(e, 'stack', '') or '') else None)   # the app's own layout scripts (data tables, icons) are not this module
    for attempt in range(6):
        await pg.goto(BASE, wait_until='load')
        await pg.fill('#username', u); await pg.fill('#password-input', p)
        async with pg.expect_response(lambda r: '/login' in r.url and r.request.method == 'POST', timeout=20000) as resp:
            await pg.click('#login_btn')
        j = await (await resp.value).json()
        if j.get('status') == 429:   # the login rate limit after many test sign-ins: wait and try again
            await pg.wait_for_timeout(int((float(j.get('retry_after') or 5) + 1) * 1000)); continue
        break
    await pg.wait_for_timeout(1500)
    return pg, errs

async def shot(pg, name, full=False):
    # the app's top bar is fixed; let it scroll with the page so a full-page picture does not repeat it mid-page
    await pg.evaluate("document.querySelectorAll('#page-topbar, .navbar-header, header').forEach(e => { if (getComputedStyle(e).position === 'fixed') e.style.position = 'absolute'; })")
    await pg.screenshot(path=f'{OUT}/{name}.png', full_page=full)

async def tab(pg, href):
    await pg.click(f'a[href="#{href}"]'); await pg.wait_for_timeout(900)

async def main():
    L, U, J = IDS['ids']['LKR'], IDS['ids']['USD'], IDS['ids']['JPY']
    async with async_playwright() as p:
        b = await p.chromium.launch()
        ctx = await b.new_context(viewport={'width': 1440, 'height': 900})
        pg, errs = await login(ctx, 'accountant')
        # overview
        await pg.goto(BASE + 'accounts/daily_bank_rec', wait_until='load'); await pg.wait_for_timeout(2500)
        txt = await pg.evaluate('document.body.innerText')
        check('overview lists the three dummy accounts with their mapping and status', all(x in txt for x in ['TEST LKR account (dummy)', 'TEST USD account (dummy)', 'TEST JPY account (dummy)', 'Confirmed']), txt[:300])
        await shot(pg, '01_overview_accounts', True)
        # LKR summary
        await pg.goto(BASE + f'accounts/daily_bank_rec/account/{L}', wait_until='load'); await pg.wait_for_timeout(1500)
        await pg.fill('#asAt', '2026-09-05'); await pg.click('#btnRefresh'); await pg.wait_for_timeout(2500)
        txt = await pg.evaluate('document.body.innerText')
        check('LKR summary shows bank and book separately, the difference and the status', '3,893,106.00' in txt and '3,547,655.00' in txt and '345,451.00' in txt and 'Reconciled' in txt, re.findall(r'(Reconciled[^\n]*|Unreconciled[^\n]*)', txt)[:2])
        await shot(pg, '02_lkr_summary', True)
        # statements tab + view
        await tab(pg, 'statements')
        await pg.click('#stmtTable tbody tr:first-child button'); await pg.wait_for_timeout(1500)
        await shot(pg, '03_lkr_statement_accepted_lines', True)
        # import preview with a deliberately broken file (checked, not kept)
        await pg.click('#btnUpload'); await pg.wait_for_timeout(500)
        broken = [list(r) for r in T.LKR_ROWS]; broken[4][6] = '749,970.00 CR'
        open(OUT + '/_broken.csv', 'wb').write(T.csv_bytes(broken))
        await pg.set_input_files('#impFile', OUT + '/_broken.csv')
        await pg.fill('input[name=opening_balance]', '1,000,000.00 CR')
        await pg.click('#btnCheck'); await pg.wait_for_timeout(2500)
        txt = await pg.evaluate("document.getElementById('previewBox').innerText")
        check('upload preview: columns recognised, balance break reported in red, nothing kept', 'Must be resolved' in txt and '749970.00' in txt and 'Nothing has been kept' in txt, txt[:300])
        await pg.evaluate("document.getElementById('importCard').scrollIntoView()"); await pg.wait_for_timeout(400)
        await shot(pg, '04_upload_check_finds_balance_break', True)
        await pg.click('#importCard .card-h button'); await pg.wait_for_timeout(300)
        # matching: undo two matches (with a reason) so the suggestions can be shown, then accept them on screen
        # (through the signed-in page itself: a second sign-in of the same user would end the browser's session)
        post = lambda path, data: pg.evaluate('([p, d]) => BRC.post(p, d)', [path, data])
        for desc in ['AH 9901 - TEST HOTEL A - 90001', 'AH 9902 - TEST SUPPLIER B - 90002']:
            mid = T.sql(f"SELECT m.id FROM brc_match m JOIN brc_match_item i ON i.match_id = m.id JOIN brc_line l ON l.id = i.line_id WHERE m.status = 'active' AND l.description = '{desc}' AND l.bank_account_id = {L} LIMIT 1")
            await post('match/undo', {'id': mid, 'reason': 'screenshot of the suggestions (dummy data)'})
        await pg.reload(wait_until='load'); await pg.wait_for_timeout(1500)
        await pg.fill('#asAt', '2026-09-05'); await pg.click('#btnRefresh'); await pg.wait_for_timeout(1500)
        await tab(pg, 'matching'); await pg.wait_for_timeout(800)
        await pg.click('#btnSuggest'); await pg.wait_for_timeout(2000)
        txt = await pg.evaluate("document.getElementById('suggestBox').innerText")
        check('suggestions are listed with confidence and reasons; nothing matched until accepted', 'high' in txt and 'PV 90001' in txt and 'nothing is matched until you accept' in txt, txt[:300])
        await shot(pg, '05_matching_suggestions', True)
        await pg.click('text=Accept all high-confidence'); await pg.wait_for_timeout(3500)
        n = int(T.sql(f"SELECT COUNT(*) FROM brc_match WHERE bank_account_id = {L} AND status = 'active'"))
        check('"Accept all high-confidence" matched them again', n == 6, n)
        await pg.wait_for_timeout(1000)
        await shot(pg, '06_matching_after_accept', True)
        # charges
        await tab(pg, 'charges'); await shot(pg, '07_bank_charges', True)
        txt = await pg.evaluate("document.getElementById('charges').innerText")
        check('bank charges are listed separately with their type and correction', 'CEFT transfer fee' in txt and 'GPRS rental' in txt and 'correction #' in txt, txt[:200])
        # a pending correction for the reviewer screenshots (dummy)
        await post('correction/propose', {'account': L, 'kind': 'duplicate_looking', 'description': 'Ledger row 99980008 repeats PV 90003 (dummy) — proposed reversal for review', 'entry_date': '2026-09-05',
            'lines': [{'ledger_code': '1-6-8-29901', 'dr': '75000', 'cr': ''}, {'ledger_code': '2-9-99-29909', 'dr': '', 'cr': '75000'}], 'book_keys': ['99980008.1']})
        await tab(pg, 'corrections'); await pg.reload(wait_until='load'); await pg.wait_for_timeout(2000); await tab(pg, 'corrections')
        await shot(pg, '08_corrections_preparer', True)
        await tab(pg, 'days'); await shot(pg, '09_daily_records', True)
        await tab(pg, 'audit'); await pg.wait_for_timeout(800); await shot(pg, '10_audit_trail')
        # USD account summary and the rates & transfers page
        await pg.goto(BASE + f'accounts/daily_bank_rec/account/{U}', wait_until='load'); await pg.wait_for_timeout(1200)
        await pg.fill('#asAt', '2026-09-05'); await pg.click('#btnRefresh'); await pg.wait_for_timeout(2500)
        txt = await pg.evaluate('document.body.innerText')
        check('USD summary shows USD and LKR side by side and the revaluation', '44,985.00' in txt and '13,585,470.00' in txt and '97,500.00' in txt, '')
        await shot(pg, '11_usd_summary', True)
        await pg.goto(BASE + 'accounts/daily_bank_rec/fx', wait_until='load'); await pg.fill('#fFrom', '2026-08-25'); await pg.fill('#fTo', '2026-09-10'); await pg.click('#btnShow'); await pg.wait_for_timeout(2000)
        txt = await pg.evaluate('document.body.innerText')
        check('rates & transfers: the USD → LKR transfer with rate obtained 300.0000, day rate 301.0000, difference −10,000.00', '300.0000' in txt and '301.0000' in txt and '10,000.00' in txt, '')
        await shot(pg, '12_rates_and_transfers', True)
        check('no script error on the preparer\'s pages', not errs, errs[:3])
        # reviewer
        ctx2 = await b.new_context(viewport={'width': 1440, 'height': 900})
        rv, errs2 = await login(ctx2, 'approver')
        await rv.goto(BASE + 'accounts/daily_bank_rec', wait_until='load'); await rv.wait_for_timeout(3500)
        txt = await rv.evaluate('document.body.innerText')
        check('the reviewer sees "Waiting for review" with the pending correction', 'Waiting for review' in txt and 'repeats PV 90003' in txt, '')
        await shot(rv, '13_reviewer_waiting_for_review', True)
        await rv.goto(BASE + f'accounts/daily_bank_rec/account/{L}#corrections', wait_until='load'); await rv.wait_for_timeout(3000)
        btns = await rv.locator('#corrTable button', has_text='Approve').count()
        check('the reviewer gets Approve / Reject on the correction proposed by someone else', btns >= 1, btns)
        await shot(rv, '14_reviewer_corrections', True)
        check('no script error on the reviewer\'s pages', not errs2, errs2[:3])
        # view-only and a department without access
        ctx3 = await b.new_context(viewport={'width': 1440, 'height': 900})
        vw, _ = await login(ctx3, 'bank')
        await vw.goto(BASE + f'accounts/daily_bank_rec/account/{L}', wait_until='load'); await vw.wait_for_timeout(2500)
        check('view-only: no Prepare / Upload / Match buttons', await vw.locator('#btnUpload').count() == 0 and await vw.locator('text=Prepare the daily record').count() == 0, '')
        ctx4 = await b.new_context(viewport={'width': 1440, 'height': 900})
        no, _ = await login(ctx4, 'hotelres')
        r = await no.goto(BASE + 'accounts/daily_bank_rec', wait_until='load')
        check('Hotel Reservations is refused the page', '403' in no.url or 'daily_bank_rec' not in no.url, no.url)
        # phone width
        ctx5 = await b.new_context(viewport={'width': 390, 'height': 844}, device_scale_factor=2)
        ph, _ = await login(ctx5, 'accountant')
        await ph.goto(BASE + f'accounts/daily_bank_rec/account/{L}', wait_until='load'); await ph.wait_for_timeout(1500)
        await ph.fill('#asAt', '2026-09-05'); await ph.click('#btnRefresh'); await ph.wait_for_timeout(2500)
        await shot(ph, '15_phone_summary', True)
        await b.close()
    print('%d/%d ok' % (sum(1 for r in res if r[1]), len(res)))
    json.dump(res, open(HERE + '/results_ui.json', 'w'), indent=1)

asyncio.run(main())
