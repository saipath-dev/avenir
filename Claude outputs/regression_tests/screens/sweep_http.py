"""The PHP side over the wire, on the test copy (the real controllers, the real data): (1) every request the screens make while
the Tour Operations screens load as the System Administrator — documents, XHR, fetch — with its status and, for JSON, that it parses;
(2) the privilege guard: each of the four test accounts asks for every TourFlow / Hotel Rates menu URL and the answer is compared
with the privileges table (user_priv_q). Run: python3 tct/sweep_http.py"""
import asyncio, os, sys, json, subprocess, collections; sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from tc import *
SCREENS = ["tour/intake", "tour/intake?id=19", "tour/intake/intake/19/costing", "tour/intake?id=20", "tour/intake/intake/20/quote/9", "tour/intake/intake/20/quote/9/email",
           "tour/intake/confirmed", "tour/hotel_bookings", "tour/hotel_bookings/card/14", "tour/transport_ops", "tour/board", "tour/board/tour/4/record", "tour/ticket_stock",
           "tour/manage", "tour/pricing", "master_files/hotel_rate_v2", "master_files/hotel_work", "master_files/entrance_master", "master_files/meal_master",
           "master_files/paid_services", "master_files/free_visits", "master_files/excursion_rates", "master_files/tour_photos", "tour/tour_payment_dashboard",
           "master_files/crew_people", "master_files/transport_rates", "master_files/mileage_allowances", "master_files/crew_bata", "dashboard"]
MENU_URLS = ["tour/intake", "tour/intake/confirmed", "tour/hotel_bookings", "tour/transport_ops", "tour/board", "tour/ticket_stock", "tour/manage", "tour/pricing", "tour/intake/mileage",
             "tour/transport_ops/crew_change", "tour/transport_ops/driver_advance", "master_files/hotel_rate_v2", "master_files/hotel_work", "master_files/hotel_rate_approval",
             "master_files/entrance_master", "master_files/meal_master", "master_files/free_visits", "master_files/paid_services", "master_files/excursion_rates",
             "master_files/tour_photos", "master_files/transport_rates", "master_files/mileage_allowances", "master_files/crew_people", "master_files/crew_bata", "master_files/crew_people/bank"]
GATES = {"tour/intake/confirmed": "tour/intake", "master_files/crew_people/bank": "master_files/crew_people/bank"}
# privileges that gate actions only (no page of their own): a holder gets 404 on GET, a non-holder is refused before the route — both are "not 200"
ACTION_ONLY = {"tour/intake/mileage", "tour/transport_ops/crew_change", "tour/transport_ops/driver_advance", "master_files/crew_people/bank"}
async def main():
    async with async_playwright() as p:
        b, ctx = await browser(p)
        pg, body = await login(ctx, "admin")
        check("System Administrator signs in", body.get("status") == 200)
        seen = []
        def on_resp(r):
            u = r.url
            if not u.startswith(HOST) or "/assets/" in u or "/uploads/" in u: return
            seen.append((r.request.resource_type, r.request.method, u.replace(BASE, ""), r.status, r.headers.get("content-type", "")))
        pg.on("response", on_resp)
        for s in SCREENS:
            try:
                await pg.goto(BASE + s, wait_until="load", timeout=60000); await pg.wait_for_timeout(2500)
            except Exception as e:
                check(f"screen {s} loads", False, str(e)[:100])
        by = collections.Counter((t, st) for t, m, u, st, ct in seen)
        bad = [x for x in seen if x[3] >= 400]
        check(f"{len(seen)} requests made by the {len(SCREENS)} screens (documents, XHR, fetch): none answered 4xx/5xx", not bad, bad[:8])
        docs = [x for x in seen if x[0] == "document"]; xhr = [x for x in seen if x[0] in ("xhr", "fetch")]
        print(f"   documents {len(docs)}, data requests {len(xhr)}, distinct endpoints {len(set(u.split('?')[0] for t, m, u, st, ct in xhr))}")
        # every JSON answer parses and carries a status
        js_bad = []
        for t, m, u, st, ct in xhr:
            if "json" in ct and (u.startswith("tour/") or u.startswith("master_files/")):   # the Tour Operations endpoints (the legacy dashboard's own are not this release's)
                r = await pg.request.get(BASE + u, headers={"X-Requested-With": "XMLHttpRequest"}) if m == "GET" else None
                if r is None: continue
                try:
                    j = await r.json()
                    if not isinstance(j, (dict, list)): js_bad.append((u, "not an object"))
                except Exception as e:
                    js_bad.append((u, str(e)[:60]))
        check(f"every Tour Operations GET data endpoint the screens use answers valid JSON when asked again ({len([x for x in xhr if 'json' in x[4] and x[1]=='GET' and (x[2].startswith('tour/') or x[2].startswith('master_files/'))])} asked)", not js_bad, js_bad[:6])
        logn = subprocess.run(["grep", "-c", "CRITICAL\\|ERROR -", S + "/tc/avenir/writable/logs/log-2026-10-06.log"], capture_output=True, text=True).stdout.strip()
        print("   application log lines of level CRITICAL/ERROR so far today (the copy's log):", logn)
        # ---- the guard ----
        for who, uid in (("admin", 901), ("quote", 902), ("transport", 903), ("hotel", 904)):
            c2 = await b.new_context(viewport={"width": 1200, "height": 800})
            dp, dbody = await login(c2, who)
            held = set(sql(f"SELECT url FROM user_priv_q WHERE user_id={uid}").split("\n")) - {""}
            mism = {}
            for u in MENU_URLS:
                gate = GATES.get(u, u)
                expect = (gate in held or (who == "admin")) and u not in ACTION_ONLY
                r = await dp.request.get(BASE + u, max_redirects=0)
                got = r.status == 200
                if got != expect: mism[u] = (r.status, "held" if expect else "not held")
                if u in ACTION_ONLY and (gate in held or who == "admin") and r.status not in (404, 405): mism[u] = (r.status, "action-only privilege held: expected 404")
            check(f"guard: {who} — {len(MENU_URLS)} menu URLs answer exactly as the privileges table says ({len([u for u in MENU_URLS if GATES.get(u,u) in held]) if who != 'admin' else len(MENU_URLS)} open)", not mism, mism)
            await c2.close()
        # signed out: every URL refused
        c3 = await b.new_context(); r_bad = {}
        for u in MENU_URLS[:8]:
            r = await c3.request.get(BASE + u, max_redirects=0)
            if r.status == 200: r_bad[u] = r.status
        check("guard: signed out, no Tour Desk screen opens", not r_bad, r_bad)
        await c3.close(); await b.close()
    raise SystemExit(summary())
asyncio.run(main())
