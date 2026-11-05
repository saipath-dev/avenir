"""Tour Operations end to end on the screens, as the System Administrator: a new inquiry by hand from the bar, its record,
its costing page (blockers named, nothing guessed), the Confirmed Tours list, Hotel Bookings, Transport Operations, the
Tour Board, a tour record, Ticket Stock, Admin (backups list reads the copies folder), Pricing, the Tour Payment
Dashboard, the quotation and e-mail pages of an inquiry that has a quotation. The inquiry made here is archived at the end."""
import asyncio, os, sys, re, time; sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from tc import *

async def main():
    async with async_playwright() as p:
        b, ctx = await browser(p)
        pg, body = await login(ctx, "admin")
        errs = []; pg.on("pageerror", lambda e: errs.append(str(e)) if str(e) != "Failed to fetch" else None)
        T = "UI FLOW %d" % int(time.time())

        # ---- New quote → Start by hand ----
        await pg.goto(BASE + "tour/intake", wait_until="load"); await pg.wait_for_timeout(2000)
        await pg.click("#td_quote"); await pg.wait_for_timeout(500)
        check("New quote offers 'Paste an email' and 'Start by hand'", await pg.locator(".tdbar .dropdown-item[data-start='paste']").count() == 1 and await pg.locator(".tdbar .dropdown-item[data-start='manual']").count() == 1)
        await pg.click(".tdbar .dropdown-item[data-start='manual']"); await pg.wait_for_timeout(2500)
        check("Start by hand opens the manual form", await pg.locator("#n_save").is_visible() and await pg.locator("#n_agent_name").is_visible(), pg.url)
        before = int(sql("SELECT COUNT(*) FROM tf_intake"))
        await pg.fill("#n_agent_name", "LION TOURS"); await pg.fill("#n_tour_name", T); await pg.fill("#n_arrival_date", "2027-03-10"); await pg.fill("#n_departure_date", "2027-03-14")
        await pg.dispatch_event("#n_departure_date", "change"); await pg.wait_for_timeout(300)
        await pg.fill("#n_pax_total", "2"); await pg.fill("#n_pax_adults", "2"); await pg.fill("#n_pax_children", "0")
        check("the nights are counted from the two dates, never typed", (await pg.input_value("#n_nights")) in ("4", "4 nights") and (await pg.get_attribute("#n_nights", "readonly")) is not None, await pg.input_value("#n_nights"))
        await pg.click("#n_save"); await pg.wait_for_timeout(3500)
        after = int(sql("SELECT COUNT(*) FROM tf_intake"))
        check("saving makes one inquiry record", after == before + 1, (before, after))
        nid = int(sql(f"SELECT id FROM tf_intake WHERE tour_name = '{T}' ORDER BY id DESC LIMIT 1") or 0)
        check("the new inquiry has a number and 4 nights from its dates", nid > 0 and sql(f"SELECT nights FROM tf_intake WHERE id={nid}") == "4", nid)
        if nid:
            await pg.goto(BASE + f"tour/intake?id={nid}", wait_until="load"); await pg.wait_for_timeout(3500)
            txt = await pg.evaluate("document.body.innerText")
            check("the record opens with its group name and 'Still needed' list", T in txt and "Still needed" in txt)
            check("the section rail is there", await pg.locator(".tf-rail a").count() >= 5)
            cj = await pg.evaluate("fetch('%stour/intake/intake/%d/costing', {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r => r.json())" % (BASE, nid))
            codes = [re.sub(r"_\d+$", "", b.get("code", "")) for b in (cj.get("data", {}).get("blockers") or [])]
            check("the costing names what blocks a quotation instead of guessing (room mix, hotel per night, pricing)", "rooms_mix" in codes and "hotel_none" in codes and "pricing" in codes, codes)
            check("the costing of an empty inquiry is nothing, not a guess", float(cj.get("data", {}).get("cost_usd") or 0) == 0.0 and cj["data"]["ready"] is False)
            check("no quotation version exists for it", sql(f"SELECT COUNT(*) FROM tf_quote WHERE intake_id={nid}") == "0")
            await pg.goto(BASE + "tour/intake", wait_until="load"); await pg.wait_for_timeout(2500)
            check("it is listed among the open inquiries", T in (await pg.evaluate("document.body.innerText")))

        # ---- an inquiry that has a quotation: the quotation page and the e-mail page ----
        row = sql("SELECT q.intake_id, q.id FROM tf_quote q ORDER BY q.id DESC LIMIT 1")
        if row:
            iid, qid = row.split("\t")
            r = await pg.goto(BASE + f"tour/intake/intake/{iid}/quote/{qid}", wait_until="load"); await pg.wait_for_timeout(2500)
            qtxt = await pg.evaluate("document.body.innerText")
            check("the client quotation page opens with a per-person price in USD", r.status == 200 and ("USD" in qtxt or "US$" in qtxt), r.status)
            check("the quotation page shows no cost or margin column (client view)", "margin" not in qtxt.lower() and "cost_lkr" not in qtxt)
            r = await pg.goto(BASE + f"tour/intake/intake/{iid}/quote/{qid}/email", wait_until="load"); await pg.wait_for_timeout(2000)
            check("the quotation e-mail page opens", r.status == 200)

        # ---- the desks' screens ----
        for url, title, must in (("tour/intake/confirmed", "Confirmed Tours", None), ("tour/hotel_bookings", "Hotel Bookings", None), ("tour/transport_ops", "Transport Operations", None), ("tour/board", "Tour Board", None), ("tour/ticket_stock", "Ticket Stock", None), ("tour/pricing", "Pricing", "markup"), ("tour/tour_payment_dashboard", "Tour Payment Dashboard", None)):
            r = await pg.goto(BASE + url, wait_until="load"); await pg.wait_for_timeout(2200)
            h4 = (await pg.locator(".tf-title h4, .page-content h4").first.text_content() or "").strip()
            body_txt = await pg.evaluate("document.body.innerText")
            check(f"{title}: opens with its name", r.status == 200 and h4.startswith(title), (r.status, h4[:40]))
            if must:
                check(f"{title}: shows '{must}'", must in body_txt.lower())
        confirmed = int(sql("SELECT COUNT(*) FROM tf_tour WHERE cancelled_at IS NULL AND archived_at IS NULL") or 0)
        await pg.goto(BASE + "tour/transport_ops", wait_until="load"); await pg.wait_for_timeout(2500)
        ttxt = await pg.evaluate("document.body.innerText")
        if confirmed == 0:
            check("Transport Operations: with no confirmed tour it says no tour is waiting (nothing invented)", "no tour" in ttxt.lower() and "waiting" in ttxt.lower(), ttxt[:160])
        # ---- a tour record ----
        tid = sql("SELECT MAX(id) FROM tf_tour")
        if tid:
            r = await pg.goto(BASE + f"tour/board/tour/{tid}/record", wait_until="load"); await pg.wait_for_timeout(2500)
            rtxt = await pg.evaluate("document.body.innerText")
            check("the tour record opens with its number and history", r.status == 200 and "AVH-" in rtxt and ("History" in rtxt or "history" in rtxt))

        # ---- Admin: the backups list reads the copies folder ----
        await pg.goto(BASE + "tour/manage", wait_until="load"); await pg.wait_for_timeout(2500)
        j = await pg.evaluate("fetch('%stour/manage/backups', {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r => r.json())" % BASE)
        check("Admin: the backups list answers with the copies folder", j.get("status") == 200 and j["data"]["folder"] == "writable/tf_backups" and isinstance(j["data"]["backups"], list))
        atxt = await pg.evaluate("document.body.innerText")
        check("Admin: the page opens as Admin with the copies of the TourFlow tables", "Admin" in atxt and ("copy" in atxt.lower() or "backup" in atxt.lower()))

        # ---- Ticket Stock ----
        await pg.goto(BASE + "tour/ticket_stock", wait_until="load"); await pg.wait_for_timeout(2500)
        n_att = int(sql("SELECT COUNT(*) FROM tf_ticket_attraction") or 0)
        stxt = await pg.evaluate("document.body.innerText")
        check("Ticket Stock: the attractions under control are listed (or the screen says there are none)", n_att == 0 or stxt.count("tickets") >= 1)

        if nid:
            sql(f"UPDATE tf_intake SET archived_at = NOW(), archived_by = 'battery', archive_note = 'ui flow: archived at the end' WHERE id = {nid}")
            check("the inquiry made here is archived at the end, not deleted", sql(f"SELECT COUNT(*) FROM tf_intake WHERE id={nid} AND archived_at IS NOT NULL") == "1")
        check("no JS errors on the Tour Operations screens", not errs, errs[:3])
        await b.close()
    raise SystemExit(summary())
asyncio.run(main())
