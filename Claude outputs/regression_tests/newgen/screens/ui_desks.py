"""The desks, as the people on them see the system: each test account's sidebar and Tour Desk bar carry exactly the screens
its group holds, nothing else opens, the Masters menu is absent, and a signed-out visitor is sent to the sign-in page."""
import asyncio, os, sys; sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from tc import *

EXPECT = {
    "quote":     {"group": 26, "bar": ["tour/intake", "tour/intake/confirmed"], "label": "Quotation Desk"},
    "transport": {"group": 28, "bar": ["tour/intake", "tour/intake/confirmed", "tour/transport_ops", "tour/ticket_stock"], "label": "Transport & Crew Guide Allocation Desk"},
    "hotel":     {"group": 27, "bar": ["tour/hotel_bookings", "master_files/hotel_rate_v2", "master_files/hotel_work"], "label": "Hotel Desk"},
}
ALL_URLS = ["tour/intake", "tour/intake/confirmed", "tour/hotel_bookings", "tour/transport_ops", "tour/board", "tour/ticket_stock", "tour/manage", "tour/pricing", "master_files/hotel_rate_v2", "master_files/hotel_work", "master_files/hotel_rate_approval",
            "master_files/entrance_master", "master_files/meal_master", "master_files/free_visits", "master_files/paid_services", "master_files/excursion_rates", "master_files/tour_photos", "master_files/transport_rates", "master_files/mileage_allowances", "master_files/crew_people", "master_files/crew_bata", "tour/tour_payment_dashboard"]

async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch()
        for who, ex in EXPECT.items():
            ctx = await b.new_context(viewport={"width": 1440, "height": 900})
            pg, body = await login(ctx, who)
            check(f"{who}: signs in", body.get("status") == 200, body)
            held = set(sql(f"SELECT DISTINCT url FROM user_priv_q WHERE group_id={ex['group']}").split("\n")) - {""}
            await pg.goto(BASE + "dashboard", wait_until="load"); await pg.wait_for_timeout(1500)
            name = (await pg.evaluate("(document.querySelector('.user-name-text, .user-name-sub-text, header .d-xl-inline-block') || {}).innerText || document.body.innerText.slice(0,200)"))
            check(f"{who}: the header names the group", ex["label"] in (await pg.evaluate("document.body.innerText")), name[:80])
            side = await pg.evaluate("Array.from(document.querySelectorAll('#layout-wrapper .app-menu a[href], .navbar-menu a[href]')).map(a => a.getAttribute('href').replace(/^.*\\/avenir\\//, '').replace(/\\/$/, '')).filter(h => h && !h.startsWith('#') && !h.startsWith('javascript'))")
            tour_side = sorted(set(h for h in side if h.startswith("tour/") or h.startswith("master_files/")))
            check(f"{who}: the sidebar's Tour / Master entries are exactly the group's grants", tour_side == sorted(u for u in held if u.startswith("tour/") or u.startswith("master_files/")), (tour_side, sorted(held)))
            await pg.goto(BASE + ex["bar"][0], wait_until="load"); await pg.wait_for_timeout(2000)
            hrefs = await pg.evaluate("Array.from(document.querySelectorAll('.tdbar .td-nav a.td-item')).map(a => a.getAttribute('href').replace(/^.*\\/avenir\\//, ''))")
            check(f"{who}: the Tour Desk bar shows its screens only", sorted(hrefs) == sorted(ex["bar"]), hrefs)
            check(f"{who}: no Masters menu on the bar", await pg.locator("#td_masters").count() == 0)
            quote_btn = await pg.locator(".tdbar .td-quote").count()
            check(f"{who}: New quote only for a desk that holds Tour Inquiry", (quote_btn == 1) == ("tour/intake" in ex["bar"]), quote_btn)
            refused = {}
            for u in ALL_URLS:
                gate = "tour/intake" if u == "tour/intake/confirmed" else u
                r = await pg.request.get(BASE + u, max_redirects=0)
                if (r.status == 200) != (gate in held):
                    refused[u] = r.status
            check(f"{who}: every screen answers as the grants say ({len(ALL_URLS)} asked)", not refused, refused)
            # an action the desk never holds: a POST is refused before anything is written
            r = await pg.request.post(BASE + "tour/transport_ops/driver_advance/approve", data="{}", headers={"Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest"}, max_redirects=0)
            check(f"{who}: a driver-advance approval POST is refused", r.status != 200 or "status\":200" not in (await r.text()), r.status)
            await ctx.close()
        # signed out
        ctx = await b.new_context(); pg = await ctx.new_page()
        r = await pg.goto(BASE + "tour/intake", wait_until="load")
        check("signed out: Tour Inquiry sends the visitor to sign in", "login_form" in (await pg.content()) or "/avenir/" == pg.url[-8:] or r.status in (302, 401, 403), pg.url)
        r2 = await pg.request.get(BASE + "tour/intake/list", max_redirects=0)
        check("signed out: a data endpoint does not answer with data", r2.status != 200 or "inquiry_no" not in (await r2.text()), r2.status)
        await ctx.close(); await b.close()
    raise SystemExit(summary())
asyncio.run(main())
