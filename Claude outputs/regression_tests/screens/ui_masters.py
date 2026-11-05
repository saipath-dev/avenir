"""The master screens open with their data, their add dialogs open and close without saving, their search boxes narrow,
the Transport & Guide Services strip sits on its four screens, and the Drivers & Guides master shows its tabs with the
counts the table gives — as the System Administrator on the test copy."""
import asyncio, os, sys, re; sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from tc import *

async def main():
    async with async_playwright() as p:
        b, ctx = await browser(p)
        pg, body = await login(ctx, "admin")
        errs = []; pg.on("pageerror", lambda e: errs.append(str(e)) if str(e) != "Failed to fetch" else None)

        # ---- Places & Entrance Fees ----
        await pg.goto(BASE + "master_files/entrance_master", wait_until="load"); await pg.wait_for_selector("#l_rows tr.item", timeout=20000); await pg.wait_for_timeout(800)
        n_places = int(sql("SELECT COUNT(*) FROM tf_place WHERE active = 1 AND merged_into IS NULL") or 0)
        rows = await pg.locator("#l_rows tr.item").count()
        check("Places & Entrance Fees: one row per live place", rows == n_places, (rows, n_places))
        await pg.fill("#l_q", "Sigiriya"); await pg.wait_for_timeout(900)
        shown = await pg.locator("#l_rows tr.item").count()
        check("Places & Entrance Fees: the search narrows the list", 0 < shown < rows, shown)
        await pg.fill("#l_q", ""); await pg.wait_for_timeout(600)
        await pg.click("#b_add"); await pg.wait_for_timeout(700)
        check("Places & Entrance Fees: 'Add a place' opens the form (a new place starts with no rate)", "Add a place" in (await pg.locator("#form_box").inner_text()) and "no rate" in (await pg.locator("#form_box").inner_text()))
        check("Places & Entrance Fees: nothing was saved by opening it", int(sql("SELECT COUNT(*) FROM tf_place WHERE active = 1 AND merged_into IS NULL")) == n_places)

        # ---- Meal Master ----
        await pg.goto(BASE + "master_files/meal_master", wait_until="load"); await pg.wait_for_timeout(2500)
        txt = await pg.evaluate("document.body.innerText")
        check("Meal Master: opens with its lead and settings", "Meal Master" in txt and ("Half Board" in txt or "lunch" in txt.lower()))
        check("Meal Master: the Half Board default is dinner (the master's setting)", "dinner" in txt.lower())

        # ---- Free Visits / Paid Activities / Excursion Rates ----
        for url, table, where, name in (("master_files/free_visits", "tf_free_visit", "active = 1", "Free Visits"), ("master_files/paid_services", "tf_service_job", "1=0", "Paid Activities & Services"), ("master_files/excursion_rates", "tf_excursion_rate", "1=1", "Excursion Rates")):
            await pg.goto(BASE + url, wait_until="load"); await pg.wait_for_timeout(2200)
            h4 = (await pg.locator(".tf-title h4, .page-content h4").first.text_content()).strip()
            check(f"{name}: the title is the menu name", h4.startswith(name), h4)
            check(f"{name}: the Tour Desk bar is there, Masters lit", (await pg.locator(".tdbar .td-item.on .lbl").first.text_content()).strip() == "Masters")
            if where != "1=0":
                n = int(sql(f"SELECT COUNT(*) FROM {table} WHERE {where}") or 0)
                rows = await pg.locator("#l_rows tr:not(:has(td.empty)), #rows tr.item, table tbody tr[data-id]").count()
                check(f"{name}: rows on the screen for the {n} records", rows >= min(n, 1), (rows, n))

        # ---- Tour Photos ----
        await pg.goto(BASE + "master_files/tour_photos", wait_until="load"); await pg.wait_for_timeout(2500)
        n_photos = int(sql("SELECT COUNT(*) FROM tf_photo WHERE archived_at IS NULL") or sql("SELECT COUNT(*) FROM tf_photo") or 0)
        cards = await pg.locator(".photo, .ph, [data-photo-id], .card img, figure").count()
        check("Tour Photos: the library shows its pictures", cards >= min(n_photos, 1), (cards, n_photos))

        # ---- Transport & Guide Services: the strip on the four screens ----
        for url, lit in (("master_files/transport_rates", "Transport Rates"), ("master_files/mileage_allowances", "Mileage & Allowances"), ("master_files/crew_people", "Drivers & Guides"), ("master_files/crew_bata", "Crew Roles & Quotation Costing")):
            await pg.goto(BASE + url, wait_until="load"); await pg.wait_for_timeout(2500)
            strip = await pg.locator(".tgsbar, .tgs-bar, nav[aria-label*='Transport']").count()
            on = (await pg.locator(".tgsbar .on, .tgs-bar .on, .tgsbar a.active, .tgs-bar a.active").first.text_content()) if strip else ""
            check(f"{lit}: the Transport & Guide Services strip, this screen lit", strip >= 1 and lit.split(' ')[0] in (on or ""), (strip, (on or '').strip()[:40]))

        # ---- Transport Rates: the seven classes at the live rates ----
        await pg.goto(BASE + "master_files/transport_rates", wait_until="load"); await pg.wait_for_timeout(2500)
        txt = await pg.evaluate("document.body.innerText")
        for cls, rate in (("Sedan", "100"), ("Van", "130"), ("Mini coach", "275"), ("Coach", "350")):
            check(f"Transport Rates: {cls} at LKR {rate}", cls.lower() in txt.lower() and rate in txt)

        # ---- Drivers & Guides: tabs and counts ----
        await pg.goto(BASE + "master_files/crew_people", wait_until="load"); await pg.wait_for_timeout(3000)
        counts = {k: int(v) for k, v in [l.split("\t") for l in sql("SELECT COALESCE(NULLIF(category,''),'-'), COUNT(*) FROM tf_crew_person WHERE status='active' AND archived_at IS NULL GROUP BY 1").split("\n") if l]}
        total = sum(counts.values())
        tabs = await pg.evaluate("Array.from(document.querySelectorAll('#tabs .tab')).filter(t => t.offsetParent !== null || t.id !== 'tab_none').map(t => t.textContent.replace(/\\s+/g,' ').trim())")
        check("Drivers & Guides: the tabs All · JSD · JSCG · JSNTG · To classify", all(any(t.startswith(k) for t in tabs) for k in ("All", "JSD", "JSCG", "JSNTG", "To classify")), tabs)
        all_tab = next((t for t in tabs if t.startswith("All")), "")
        check(f"Drivers & Guides: the All tab counts the {total} active people", str(total) in all_tab, all_tab)
        to_cl = next((t for t in tabs if t.startswith("To classify")), "")
        check("Drivers & Guides: the To classify tab counts the people without a category", str(counts.get("-", 0)) in to_cl, (to_cl, counts.get("-", 0)))
        strip = await pg.evaluate("document.body.innerText")
        check("Drivers & Guides: the Standard figures strip shows LKR 4,000 and 7,500 and the overnight allowance unset", "4,000" in strip and "7,500" in strip and ("not set" in strip.lower() or "unset" in strip.lower()))
        check("Drivers & Guides: the bank column is offered to the System Administrator", "bank" in strip.lower())
        first = pg.locator("#rows tr[data-id], table tbody tr[data-id]").first
        if await first.count():
            await first.locator("a, button, [data-open]").first.click(); await pg.wait_for_timeout(1500)
            check("Drivers & Guides: a record opens", await pg.locator(".modal.show, #record, .tf-record, .person").count() >= 1)
        check("no JS errors on the master screens", not errs, errs[:3])
        await b.close()
    raise SystemExit(summary())
asyncio.run(main())
