"""Railway view of Mileage by sector — screenshots and checks on the TEST COPY (127.0.0.1:8081, database avenir_test)."""
import asyncio, os, sys, json
HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(os.path.dirname(HERE), 'tct'))
os.environ['TC_HOST'] = 'http://127.0.0.1:8081'
import tc
from playwright.async_api import async_playwright
OUT = os.path.join(HERE, 'shots'); os.makedirs(OUT, exist_ok=True)
IID = int(os.environ.get('IID', '19'))

async def card(pg):
    c = pg.locator('#mil_card')
    await c.scroll_into_view_if_needed()
    await pg.evaluate("() => { const c = document.getElementById('mil_card'); window.scrollTo(0, c.getBoundingClientRect().top + window.scrollY - 150); }")
    await pg.wait_for_timeout(350)
    return c

async def shot(pg, name, extra=0):
    bb = await pg.locator('#mil_card').bounding_box()
    vp = pg.viewport_size
    h = min(vp['height'] - bb['y'], bb['height'] + extra)
    await pg.screenshot(path=f'{OUT}/{name}', clip={'x': bb['x'], 'y': bb['y'], 'width': bb['width'], 'height': h})

async def main():
    errs = []
    async with async_playwright() as p:
        b, ctx = await tc.browser(p, 1440, 2000)
        pg, body = await tc.login(ctx, 'admin')
        pg.on('console', lambda m: errs.append(m.text) if m.type == 'error' else None)
        pg.on('requestfailed', lambda r: errs.append('FAILED ' + r.url[:100]))
        pg.on('response', lambda r: errs.append('HTTP %d %s' % (r.status, r.url[:100])) if r.status >= 400 else None)
        pg.on('pageerror', lambda e: errs.append('PAGEERROR ' + str(e)))
        await pg.evaluate("() => { try { localStorage.removeItem('avh_mileage_view'); } catch (e) {} }")
        await pg.goto(tc.BASE + f'tour/intake?id={IID}', wait_until='networkidle')
        await pg.wait_for_selector('#mil_card', timeout=30000)
        c = await card(pg)
        await shot(pg, '1_railway.png')
        # hover a sector
        seg = pg.locator('#mil_card .trk-seg:not(.gap)').nth(1)
        await seg.hover(); await pg.wait_for_timeout(500)
        vis = await pg.locator('#mil_card .trk-pop').is_visible()
        print('hover sector -> window shown:', vis)
        await shot(pg, '2_hover_sector.png')
        # hover a station with a picture
        st = pg.locator('#mil_card .trk-st').nth(3)
        await pg.mouse.move(5, 5); await pg.wait_for_timeout(400)
        await st.hover(); await pg.wait_for_timeout(500)
        await shot(pg, '3_hover_station.png')
        await pg.mouse.move(5, 5); await pg.wait_for_timeout(500)
        print('after leaving -> hidden:', await pg.locator('#mil_card .trk-pop').is_hidden())
        # click pins; type an actual km and save; the window comes back after the save
        await seg.click(); await pg.wait_for_timeout(300)
        print('pinned:', await pg.locator('#mil_card .trk-pop.pinned').count() == 1)
        inp = pg.locator('#mil_card .trk-pop [data-actual]')
        if await inp.count():
            await inp.fill('41'); await inp.press('Enter'); await pg.wait_for_timeout(1500)
            print('after save, window back:', await pg.locator('#mil_card .trk-pop.pinned').count() == 1,
                  '| value now:', await pg.locator('#mil_card .trk-pop [data-actual]').input_value() if await pg.locator('#mil_card .trk-pop [data-actual]').count() else None)
            await shot(pg, '4_pinned_after_save.png')
            # put it back
            inp = pg.locator('#mil_card .trk-pop [data-actual]')
            await inp.fill(''); await inp.press('Enter'); await pg.wait_for_timeout(1500)
        await pg.wait_for_timeout(2600)   # the "saved" message closes by itself after two seconds
        await pg.keyboard.press('Escape'); await pg.wait_for_timeout(300)
        print('esc -> hidden:', await pg.locator('#mil_card .trk-pop').is_hidden())
        # a day opened
        await pg.locator('#mil_card .trk-dayrow summary').nth(0).click(); await pg.wait_for_timeout(300)
        c = await card(pg)
        await shot(pg, '5_day_open.png')
        # list view, unchanged
        await pg.locator('#mil_card [data-mview="list"]').click(); await pg.wait_for_timeout(800)
        c = await card(pg)
        await shot(pg, '6_list_view.png')
        print('list rows:', await pg.locator('#mil_card table.tbl tbody tr').count())
        await pg.locator('#mil_card [data-mview="rail"]').click(); await pg.wait_for_timeout(800)
        print('map url:', await pg.locator('#mil_card .mv-switch a').get_attribute('href') if await pg.locator('#mil_card .mv-switch a').count() else None)
        await b.close()
        # phone
        b = await p.chromium.launch()
        ctx = await b.new_context(viewport={'width': 390, 'height': 844}, is_mobile=True, has_touch=True, device_scale_factor=2)
        pg, body = await tc.login(ctx, 'admin')
        pg.on('pageerror', lambda e: errs.append('PAGEERROR(m) ' + str(e)))
        await pg.goto(tc.BASE + f'tour/intake?id={IID}', wait_until='networkidle')
        await pg.wait_for_selector('#mil_card', timeout=30000)
        c = await card(pg)
        await c.screenshot(path=f'{OUT}/7_phone.png')
        await pg.locator('#mil_card .trk-seg:not(.gap)').nth(1).tap(); await pg.wait_for_timeout(600)
        print('phone tap -> sheet:', await pg.locator('#mil_card .trk-pop.sheet.pinned').count() == 1)
        await pg.screenshot(path=f'{OUT}/8_phone_tap.png')
        await b.close()
    print('console errors:', json.dumps(errs[:20], indent=0))

asyncio.run(main())
