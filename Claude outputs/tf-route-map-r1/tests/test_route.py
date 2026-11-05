"""Driver's route map — checks on the TEST COPY (127.0.0.1:8081, avenir_test): the pack row on the card, the sheet, the picture, the PDF, the phone."""
import asyncio, os, sys, json, re
HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(os.path.dirname(HERE), 'tct'))
os.environ['TC_HOST'] = 'http://127.0.0.1:8081'
import tc
from playwright.async_api import async_playwright
OUT = os.path.join(HERE, 'shots'); os.makedirs(OUT, exist_ok=True)
JOB = int(os.environ.get('JOB', '3'))

async def main():
    errs = []
    async with async_playwright() as p:
        b, ctx = await tc.browser(p, 1440, 1100)
        pg, body = await tc.login(ctx, 'transport')
        pg.on('pageerror', lambda e: errs.append('PAGEERROR ' + str(e)))
        # the card's pack: the Route map row with Preview / PDF / Image
        await pg.goto(tc.BASE + f'tour/transport_ops?tour={JOB}', wait_until='networkidle')
        await pg.wait_for_timeout(1500)
        op = pg.locator("button[data-open]").first
        if await op.count():
            await op.click(); await pg.wait_for_timeout(1500)
        row = pg.locator('[data-packdoc="ROUTE"]')
        n = await row.count()
        print('pack row ROUTE on the card:', n)
        if n:
            await row.first.scroll_into_view_if_needed()
            links = await row.first.locator('a').all_inner_texts()
            print('   buttons:', [t.strip() for t in links])
            box = await row.first.bounding_box()
            await pg.screenshot(path=f'{OUT}/r1_card_pack_row.png', clip={'x': 0, 'y': max(0, box['y'] - 160), 'width': 1440, 'height': 320})
        # the sheet
        url = tc.BASE + f'tour/transport_ops/card/{JOB}/pack/ROUTE'
        r = await pg.goto(url, wait_until='networkidle')
        print('sheet:', r.status, (await pg.title()))
        await pg.screenshot(path=f'{OUT}/r2_sheet.png', full_page=True)
        # the picture and the PDF through the signed-in session
        for q, name in (('?img=1', 'r3_card.png'), ('?map=1', 'r4_map.png'), ('?pdf=1', 'r5_sheet.pdf')):
            res = await pg.request.get(url + q)
            bodyb = await res.body()
            ct = res.headers.get('content-type', '')
            cd = res.headers.get('content-disposition', '')
            open(f'{OUT}/{name}', 'wb').write(bodyb)
            print(f'{q}: {res.status} {ct} {len(bodyb)} bytes {cd}')
        await b.close()
        # the sheet on a phone
        b = await p.chromium.launch()
        ctx = await b.new_context(viewport={'width': 390, 'height': 844}, is_mobile=True, has_touch=True, device_scale_factor=2)
        pg, body = await tc.login(ctx, 'transport')
        await pg.goto(url, wait_until='networkidle')
        await pg.screenshot(path=f'{OUT}/r6_sheet_phone.png', full_page=True)
        await b.close()
    print('errors:', errs)

asyncio.run(main())
