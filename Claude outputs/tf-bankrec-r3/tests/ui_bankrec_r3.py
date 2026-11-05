"""tf-bankrec-r3 — the r3 changes in a real browser (cloud test copy, dummy data left by test_bankrec_r3.py), with screenshots.
  * the OLD Bank Reconciliation screen refuses a tick on a protected date (message shown) and still allows an earlier date;
  * the new workflow warns about old-screen flags in the protected period (flags simulated on dummy rows, put back);
  * pending corrections per day: only the amount up to the day, the correction total separately;
  * a posted correction whose ledger entry changes: "verify again" (dummy entry 99980399, put back);
  * a user holding both the preparer and the reviewer privilege: no Review button on their own record, and refused if forced.
Dummy ledger rows only; the real ledger signature is compared before and after."""
import asyncio, os, sys, json
from playwright.async_api import async_playwright
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, S + '/paydesk'); sys.path.insert(0, HERE)
from api import ACC
from test_bankrec import sql
from test_bankrec_r2 import LEDGER_SIG
ACC['dual'] = ('tcdual', 'tc-pay-2026')
HOST = os.environ.get('BRC_HOST', 'http://127.0.0.1:8081'); BASE = HOST + '/avenir/'
OUT = os.environ.get('BRC_SHOTS', HERE + '/shots_r3'); os.makedirs(OUT, exist_ok=True)
IDS = json.load(open(HERE + '/test_ids_r3.json'))
res = []
def check(n, ok, d=''):
    res.append((n, bool(ok), str(d)[:300])); print(('  ok   ' if ok else '  FAIL ') + n + ('' if ok else '  ' + str(d)[:300]), flush=True)

VROWS = '99980302,99980303,99980304,99980305,99980306,99980307'
def vflags():
    return sql(f"SELECT GROUP_CONCAT(CONCAT(ID, ':', L_Code, ':', COALESCE(New_L_Code, ''), ':', COALESCE(bank_rec, 0), ':', COALESCE(bankst_flag, 0)) ORDER BY ID) FROM account_runing WHERE ID IN ({VROWS})")

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

async def alert_text(pg):
    return await pg.evaluate("[...document.querySelectorAll('.swal2-popup, .swal-modal, .sweet-alert')].filter(e => { const s = getComputedStyle(e), r = e.getBoundingClientRect(); return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0; }).map(e => e.innerText).join(' | ')")

async def close_alert(pg):
    await pg.evaluate("[...document.querySelectorAll('.swal2-confirm, .swal-button, .sweet-alert button.confirm')].forEach(b => { if (b.getBoundingClientRect().width > 0) b.click(); })")
    await pg.wait_for_timeout(600)

async def old_screen(b):
    ctx = await b.new_context(viewport={'width': 1440, 'height': 900})
    pg, errs = await login(ctx, 'admin')
    await pg.goto(BASE + 'accounts/bank_reconcil', wait_until='load'); await pg.wait_for_timeout(1500)
    await pg.evaluate("document.getElementById('drange2').value = '2026-09-10'")
    await pg.evaluate("jQuery('#bank').val('1-6-8-29904').trigger('change')")
    await pg.click('.btn_s'); await pg.wait_for_timeout(3000)
    n = await pg.evaluate("document.querySelectorAll('button.btn_realize').length")
    have = await pg.evaluate("[...document.querySelectorAll('button.btn_realize')].map(b => b.value).join(',')")
    check('old screen lists the TEST verification account (dummy) up to 2026-09-10 with its tick buttons', n >= 5 and '99980305' in have and '99980302' in have, (n, have))
    await shot(pg, '34_old_screen_dummy_account_listed')
    f0 = vflags()
    # a protected date (2026-09-02 >= first day 2026-09-01)
    async with pg.expect_response(lambda r: 'bank_reconcil/update' in r.url, timeout=15000) as resp:
        await pg.click('button.btn_realize[value="99980305"]')
    r = await resp.value
    await pg.wait_for_timeout(1200)
    txt = await alert_text(pg)
    check('old screen: ticking the 2026-09-02 row (on/after the first day 2026-09-01) is refused (409) and the reason is shown on screen',
          r.status == 409 and '2026-09-01' in txt and 'Daily Bank Reconciliation' in txt and 'Nothing was changed' in txt, (r.status, txt[:300]))
    green = await pg.evaluate("getComputedStyle(document.querySelector('button.btn_realize[value=\"99980305\"]').closest('tr')).backgroundColor")
    check('old screen: the refused row is not marked (not green) and its flags in the database are unchanged', '52, 179, 10' not in green and vflags() == f0, (green, vflags()))
    await shot(pg, '35_old_screen_refused_protected_date', full=False)
    await close_alert(pg)
    # an earlier date (2026-08-28) stays available; untick it again
    async with pg.expect_response(lambda r: 'bank_reconcil/update' in r.url, timeout=15000) as resp:
        await pg.click('button.btn_realize[value="99980302"]')
    r = await resp.value
    await pg.wait_for_timeout(1500)
    set1 = sql("SELECT COALESCE(bank_rec, 0) FROM account_runing WHERE ID = 99980302")
    green = await pg.evaluate("getComputedStyle(document.querySelector('button.btn_realize[value=\"99980302\"]').closest('tr')).backgroundColor")
    check('old screen: the 2026-08-28 row (before the first day) can still be ticked (200, bank_rec 1, row green)', r.status == 200 and set1 == '1' and '52, 179, 10' in green, (r.status, set1, green))
    await pg.evaluate("document.querySelector('button.btn_realize[value=\"99980302\"]').scrollIntoView({block: 'center'})")
    await shot(pg, '36_old_screen_earlier_date_still_allowed', full=False)
    async with pg.expect_response(lambda r: 'bank_reconcil/update' in r.url, timeout=15000) as resp:
        await pg.click('button.btn_realize[value="99980302"]')
    r = await resp.value
    await pg.wait_for_timeout(1200)
    check('old screen: unticking it puts it back (bank_rec 0); every dummy row\'s flags as before', r.status == 200 and vflags() == f0, (r.status, vflags(), f0))
    check('no script error on the old screen', not errs, errs[:3])
    await ctx.close()

async def new_workflow(b):
    L, V, cV = IDS['ids']['LKR'], IDS['b']['V'], IDS['b']['cV']
    ctx = await b.new_context(viewport={'width': 1440, 'height': 900})
    pg, errs = await login(ctx, 'accountant')
    # ---- warning about old-screen flags in the protected period (simulated on dummy rows, then put back)
    nl0 = sql("SELECT COALESCE(New_L_Code, 'NULL') FROM account_runing WHERE ID = 99980307")
    f0 = vflags()
    sql("UPDATE account_runing SET bank_rec = 1 WHERE ID = 99980306")
    sql("UPDATE account_runing SET New_L_Code = L_Code, L_Code = '1-6-112-10782' WHERE ID = 99980307 AND L_Code = '1-6-8-29904'")
    try:
        await open_acct(pg, V, '2026-09-05')
        txt = await pg.evaluate("(document.getElementById('legacyBanner') || {innerText: ''}).innerText")
        check('new workflow warns: "Old reconciliation screen: 2 ledger row(s) … carry its flags", naming row 99980306 (bank_rec) and 99980307 (moved to the unrealised account)',
              'Old reconciliation screen: 2 ledger row(s)' in txt and '99980306' in txt and 'bank_rec' in txt and '99980307' in txt and 'unrealised' in txt and 'left as they are' in txt, txt[:400])
        await shot(pg, '37_new_workflow_warns_old_screen_flags')
        await pg.goto(BASE + 'accounts/daily_bank_rec', wait_until='load'); await pg.wait_for_timeout(3500)
        txt = await pg.evaluate('document.body.innerText')
        check('overview: "Old-screen flags in the period: 2" on the TEST verification account', 'Old-screen flags in the period: 2' in txt, '')
        await shot(pg, '38_overview_old_screen_flags')
        check('the warning changed nothing: the simulated flags are still set', sql("SELECT CONCAT(bank_rec, '|', L_Code) FROM account_runing WHERE ID IN (99980306, 99980307) ORDER BY ID") == '1|1-6-8-29904\n0|1-6-112-10782', '')
    finally:
        sql("UPDATE account_runing SET bank_rec = 0 WHERE ID = 99980306")
        sql(f"UPDATE account_runing SET L_Code = New_L_Code, New_L_Code = {'NULL' if nl0 == 'NULL' else repr(nl0)} WHERE ID = 99980307 AND L_Code = '1-6-112-10782'")
    check('the simulated flags put back (dummy rows as before)', vflags() == f0, (vflags(), f0))
    # ---- pending corrections: the amount up to the day and the correction total
    for dd, want, name in (('2026-09-01', ('25.00 LKR up to this day; correction total 4,550.00 LKR', ': 25.00 of 4,550.00 falls up to this day'), '39_pending_amount_up_to_day_1sep'),
                           ('2026-09-04', ('236,050.00 LKR up to this day; correction total 239,550.00 LKR', ': 1,050.00 of 4,550.00 falls up to this day'), '40_pending_amount_up_to_day_4sep'),
                           ('2026-09-05', ('239,550.00',), '41_pending_amount_day_5sep_full')):
        await open_acct(pg, L, dd)
        txt = await pg.evaluate("document.getElementById('banners').innerText")
        ok = all(w in txt for w in want)
        if dd == '2026-09-05':
            ok = ok and 'up to this day' not in txt.split('Corrections covering several days')[0]
        check(f'pending banner {dd}: ' + ' / '.join(want) + ('' if dd != '2026-09-05' else ' (whole correction falls by this day: no split)'), ok, txt[:500])
        await shot(pg, name, full=False)
    # ---- a posted correction whose ledger entry changes: verify again
    desc0 = sql("SELECT Discription FROM account_runing WHERE ID = 99980399 LIMIT 1")
    sql("UPDATE account_runing SET Discription = 'BANK CHARGES 01-09 (dummy posting, wording changed)' WHERE ID = 99980399")
    try:
        await open_acct(pg, V, '2026-09-05'); await tab(pg, 'corrections'); await pg.wait_for_timeout(1000)
        txt = await pg.evaluate("document.getElementById('corrTable').innerText")
        has_btn = await pg.evaluate(f"!!document.querySelector('#corrTable button[onclick=\"reverifyCorr({cV})\"]')")
        check('corrections: the changed posted entry shows "verify again" with a Verify again button for the preparer', has_btn and 'verify again' in txt.lower(), txt[:400])
        await pg.evaluate(f"document.querySelector('#corrTable button[onclick=\"reverifyCorr({cV})\"]').scrollIntoView({{block: 'center'}})")
        await shot(pg, '42_posted_entry_changed_verify_again', full=False)
        await pg.click(f'#corrTable button[onclick="reverifyCorr({cV})"]'); await pg.wait_for_timeout(2500)
        await close_alert(pg)
        txt = await pg.evaluate("document.getElementById('corrTable').innerText")
        st = sql(f"SELECT posting_state FROM brc_correction WHERE id = {cV}")
        check('Verify again on screen: the entry still matches the approved lines → posted and verified again (button gone)',
              not await pg.evaluate(f"!!document.querySelector('#corrTable button[onclick=\"reverifyCorr({cV})\"]')") and st == 'verified', (st, txt[:300]))
        await shot(pg, '43_verified_again', full=False)
        check('a correction the dual-role user withdrew reads "withdrawn by tcdual" (not as if reviewed)', 'withdrawn by tcdual' in txt, txt[:400])
    finally:
        sql("UPDATE account_runing SET Discription = %s WHERE ID = 99980399" % repr(desc0))
    j = await pg.evaluate('([p, d]) => BRC.post(p, d)', ['correction/reverify', {'id': cV}])
    check('wording put back and verified again (the dummy entry as before the UI test)', j.get('status') == 200, j)
    check('no script error on the preparer\'s pages', not errs, errs[:3])
    await ctx.close()

async def dual_role(b):
    V = IDS['b']['V']
    ctx = await b.new_context(viewport={'width': 1440, 'height': 900})
    pg, errs = await login(ctx, 'dual')
    await open_acct(pg, V, '2026-09-02')
    j = await pg.evaluate('([p, d]) => BRC.post(p, d)', ['day/prepare', {'account': V, 'date': '2026-09-02', 'note': 'dual-role UI test (dummy)'}])
    rid = j.get('id')
    check('dual-role user (preparer + reviewer, not administrator) prepares a daily record for 2 Sep', j.get('status') == 200 and rid, j)
    await open_acct(pg, V, '2026-09-02'); await tab(pg, 'days'); await pg.wait_for_timeout(1500)
    row = await pg.evaluate(f"(() => {{ const r = [...document.querySelectorAll('#daysTable tbody tr')].find(t => t.cells[0] && t.cells[0].innerText.trim() === '{rid}'); return r ? r.innerHTML : ''; }})()")
    check('their own record has no "Reviewed" or "Return" button', row and 'reviewDay(' not in row, row[:300])
    await shot(pg, '44_dual_role_own_record_no_review_button')
    j = await pg.evaluate('([p, d]) => BRC.post(p, d).then(j => { B.done(j); return j; })', ['day/review', {'id': rid, 'decision': 'reviewed', 'note': 'forced from the page'}])
    await pg.wait_for_timeout(1000)
    txt = await alert_text(pg)
    check('forcing the review from the page is refused by the server (403) with the reason shown', j.get('status') == 403 and txt, (j, txt[:200]))
    await shot(pg, '45_dual_role_review_refused', full=False)
    await close_alert(pg)
    ctx2 = await b.new_context(viewport={'width': 1440, 'height': 900})
    rv, errs2 = await login(ctx2, 'approver')
    await open_acct(rv, V, '2026-09-02')
    j2 = await rv.evaluate('([p, d]) => BRC.post(p, d)', ['day/review', {'id': rid, 'decision': 'reviewed', 'note': 'reviewed by another user'}])
    check('another reviewer reviews it (200)', j2.get('status') == 200, j2)
    check('no script error on the dual-role user\'s or the reviewer\'s pages', not errs and not errs2, (errs[:2], errs2[:2]))
    await ctx.close(); await ctx2.close()

async def main():
    sig0 = sql(LEDGER_SIG)
    async with async_playwright() as p:
        b = await p.chromium.launch()
        await old_screen(b)
        await new_workflow(b)
        await dual_role(b)
        await b.close()
    check('the real ledger is unchanged (rows, sums, every row\'s reconciliation flags)', sql(LEDGER_SIG) == sig0, (sig0, sql(LEDGER_SIG)))
    print('%d/%d ok' % (sum(1 for r in res if r[1]), len(res)))
    json.dump(res, open(HERE + '/results_ui_r3.json', 'w'), indent=1)

asyncio.run(main())
