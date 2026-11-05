"""tf-pilot-r1 — screenshots of the common starting page per role (TEST COPY)."""
import asyncio, os, sys
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, os.path.join(S, 'tct')); os.environ['TC_HOST'] = 'http://127.0.0.1:8081'
import tc
from api import ACC
tc.ACC.update(ACC)
from playwright.async_api import async_playwright
OUT = os.path.join(HERE, 'shots_pilot'); os.makedirs(OUT, exist_ok=True)
async def main():
    async with async_playwright() as p:
        for who, w in [('approver', 1440), ('accountant', 1440), ('requester', 1440), ('auditor', 1440), ('requester', 420)]:
            b, ctx = await tc.browser(p, w, 1100)
            await ctx.route('**/*', lambda r: r.continue_() if '127.0.0.1' in r.request.url else r.abort())
            pg, _ = await tc.login(ctx, who)
            await pg.goto(tc.BASE + 'dashboard2/tiles', wait_until='load'); await pg.wait_for_timeout(1500)
            await pg.screenshot(path=f'{OUT}/pilot-{who}-{w}.png', full_page=True)
            await b.close()
asyncio.run(main())
