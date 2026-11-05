"""A screen-by-screen survey of Tour Operations on the test copy (the real app, as the test System Administrator):
one full-page screenshot per screen, the HTTP status, the page's JS errors. Usage: python3 tct/survey.py <out-dir> [who]"""
import asyncio, os, sys, json, subprocess; sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from tc import *
OUT = sys.argv[1] if len(sys.argv) > 1 else S + "/shots"; os.makedirs(OUT, exist_ok=True)
WHO = sys.argv[2] if len(sys.argv) > 2 else "admin"
report = {}
async def shot(pg, name, url, settle=2500, full=True, pre=None):
    errs = []
    pg.on("pageerror", lambda e: errs.append(str(e)))
    try:
        resp = await pg.goto(BASE + url, wait_until="load", timeout=45000)
        await pg.wait_for_timeout(settle)
        if pre: await pre(pg)
        await pg.screenshot(path=f"{OUT}/{name}.png", full_page=full)
        height = await pg.evaluate("document.documentElement.scrollHeight")
        title = (await pg.evaluate("(Array.from(document.querySelectorAll('.page-content h4, .main-content h4, h4')).find(h => !h.closest('.modal, .swal2-container, #layout-wrapper > header')) || {}).innerText || ''")).strip()[:60]
        info = await pg.evaluate("({folds: document.querySelectorAll('.tf-fold').length, rail: !!document.querySelector('.tf-rail'), railItems: document.querySelectorAll('.tf-rail a').length, bar: !!document.querySelector('.tdbar')})")
        # the workspace has no internet: the header's lordicon fetch fails here (it does not on live) — an environmental error, listed apart
        # errors that come from the test workspace's blocked internet, not from Avenir: a CDN fetch refused, and the Google
        # loader script (maps / charts) that cannot load here, so its global is missing; on live both load normally
        ENV = ('Failed to fetch', 'google is not defined')
        env = [e for e in errs if e in ENV]; errs = [e for e in errs if e not in ENV]
        report[name] = {"url": url, "status": resp.status if resp else None, "height": height, "h4": title, "errors": errs[:5], "env_errors": len(env), **info}
        print(f"  {name:26s} {resp.status if resp else '?'} {url:46s} h{height:5d} {title[:34]:34s} folds {info['folds']:2d} rail {info['railItems']:2d} {'ERR ' + errs[0][:60] if errs else ''}")
    except Exception as e:
        report[name] = {"url": url, "error": str(e)[:200]}; print(f"  {name:26s} ERROR {url} {str(e)[:100]}")
async def main():
    working = sql("SELECT id FROM tf_intake WHERE status='working' ORDER BY id DESC LIMIT 1") or "19"
    tour = sql("SELECT t.id FROM tf_tour t ORDER BY t.id DESC LIMIT 1") or "4"
    tour_intake = sql(f"SELECT intake_id FROM tf_tour WHERE id={tour}") or "20"
    quote = sql(f"SELECT id FROM tf_quote WHERE intake_id={tour_intake} ORDER BY id DESC LIMIT 1")
    hjob = sql(f"SELECT id FROM tf_hotel_job WHERE tour_id={tour} ORDER BY id LIMIT 1")
    async with async_playwright() as p:
        b, ctx = await browser(p)
        pg, body = await login(ctx, WHO)
        if body.get("status") != 200: print("login failed", body); return
        await shot(pg, "01_inquiries", "tour/intake", 3000, full=False)
        await shot(pg, "02_inquiry_working", f"tour/intake?id={working}", 3500)
        await shot(pg, "03_costing", f"tour/intake/intake/{working}/costing", 2500)
        await shot(pg, "04_inquiry_toured", f"tour/intake?id={tour_intake}", 3500)
        if quote:
            await shot(pg, "05_client_quotation", f"tour/intake/intake/{tour_intake}/quote/{quote}", 2500)
            await shot(pg, "06_quote_email", f"tour/intake/intake/{tour_intake}/quote/{quote}/email", 2500)
        await shot(pg, "07_confirmed_tours", "tour/intake/confirmed", 2500)
        await shot(pg, "08_hotel_bookings", "tour/hotel_bookings", 2500)
        if hjob: await shot(pg, "10_hotel_card", f"tour/hotel_bookings/card/{hjob}", 2500)
        await shot(pg, "11_transport_list", "tour/transport_ops", 2500)
        await shot(pg, "13_tour_board", "tour/board", 2500)
        await shot(pg, "14_tour_record", f"tour/board/tour/{tour}/record", 2500)
        await shot(pg, "15_ticket_stock", "tour/ticket_stock", 2500)
        await shot(pg, "16_admin", "tour/manage", 2500)
        await shot(pg, "17_pricing", "tour/pricing", 2500)
        await shot(pg, "18_hotel_rates", "master_files/hotel_rate_v2", 3000)
        await shot(pg, "19_hotel_work", "master_files/hotel_work", 2500)
        await shot(pg, "20_entrance_master", "master_files/entrance_master", 2500)
        await shot(pg, "21_meal_master", "master_files/meal_master", 2500)
        await shot(pg, "22_paid_services", "master_files/paid_services", 2500)
        await shot(pg, "23_free_visits", "master_files/free_visits", 2500)
        await shot(pg, "24_excursion_rates", "master_files/excursion_rates", 2500)
        await shot(pg, "25_tour_photos", "master_files/tour_photos", 2500)
        await shot(pg, "27_payment_dashboard", "tour/tour_payment_dashboard", 2500)
        await shot(pg, "28_crew_people", "master_files/crew_people", 3000)
        await shot(pg, "29_transport_rates", "master_files/transport_rates", 2500)
        await shot(pg, "30_mileage_allowances", "master_files/mileage_allowances", 2500)
        await shot(pg, "31_crew_bata", "master_files/crew_bata", 2500)
        async def open_masters(pg):
            try:
                await pg.locator("#td_masters").click(); await pg.wait_for_timeout(700)
            except Exception as e: print("   (Masters menu did not open)", str(e)[:80])
        await shot(pg, "32_masters_menu", "tour/intake", 2500, full=False, pre=open_masters)
        await shot(pg, "33_dashboard", "dashboard", 2500, full=False)
        await b.close()
    json.dump(report, open(OUT + "/report.json", "w"), indent=1)
    # the legacy dashboard's script error (getAttribute of null, from the theme's sample-chart file) was fixed on 6 Oct 2026
    # (public/assets/js/pages/charts/echart.js, tf-legacy-js-r1): no screen is excused any more
    KNOWN = {}
    bad = {k: v for k, v in report.items() if (v.get("status", 200) != 200 or v.get("errors") or v.get("error")) and k not in KNOWN}
    known = {k: v for k, v in report.items() if k in KNOWN and (v.get("errors") or v.get("error"))}
    print("screens:", len(report), "with a non-200, a JS error or a failure:", len(bad), "| known legacy items:", len(known)); [print("   ", k, v.get("status"), v.get("errors") or v.get("error")) for k, v in bad.items()]; [print("    known:", k, "—", KNOWN[k]) for k in known]
    raise SystemExit(1 if bad else 0)
asyncio.run(main())
