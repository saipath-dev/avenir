"""tf-paydesk-r1 — Playwright helpers for the TEST COPY (8081). `python3 ui.py smoke` loads the dashboard and screenshots it."""
import asyncio, os, sys, json
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, os.path.join(S, 'tct'))
os.environ['TC_HOST'] = 'http://127.0.0.1:8081'
import tc
from api import ACC
tc.ACC.update(ACC)
from playwright.async_api import async_playwright
OUT = os.path.join(HERE, 'shots'); os.makedirs(OUT, exist_ok=True)
MID = os.environ.get('MID', 'AH 2218')

async def open_dash(ctx, who='admin', errs=None):
    pg, body = await tc.login(ctx, who)
    if errs is not None:
        pg.on('pageerror', lambda e: errs.append('PAGEERROR ' + str(e)))
        pg.on('console', lambda m: errs.append('CONSOLE ' + m.text) if m.type == 'error' and 'Failed to load resource' not in m.text else None)
    await pg.goto(tc.BASE + 'tour/tour_payment_dashboard?manual_id=' + MID.replace(' ', '%20'), wait_until='networkidle')
    await pg.wait_for_selector('#tpd_days .line', timeout=30000)
    await pg.wait_for_timeout(600)
    return pg

async def smoke():
    errs = []
    async with async_playwright() as p:
        b, ctx = await tc.browser(p, 1440, 2200)
        pg = await open_dash(ctx, 'admin', errs)
        await pg.screenshot(path=f'{OUT}/smoke_desktop.png', full_page=True)
        n = await pg.locator('#tpd_days .line').count()
        print('lines rendered:', n, '| head:', (await pg.locator('#tpd_head h4').inner_text()))
        await b.close()
        b = await p.chromium.launch(); ctx = await b.new_context(viewport={'width': 390, 'height': 844}, is_mobile=True, has_touch=True, device_scale_factor=2)
        pg = await open_dash(ctx, 'admin', errs)
        await pg.screenshot(path=f'{OUT}/smoke_phone.png', full_page=True)
        await b.close()
    print('errors:', json.dumps(errs[:10], indent=0))

if __name__ == '__main__':
    asyncio.run(smoke())
