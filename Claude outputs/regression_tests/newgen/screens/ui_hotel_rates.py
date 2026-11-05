"""The Hotel Rates panel on the test copy: the hotel list, search, a hotel's detail with its plans and the rate-on-a-date
card, the approval queue, the readiness page, the hold list — read only, as the System Administrator."""
import asyncio, os, sys; sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from tc import *

async def main():
    async with async_playwright() as p:
        b, ctx = await browser(p)
        pg, body = await login(ctx, "admin")
        errs = []; pg.on("pageerror", lambda e: errs.append(str(e)) if str(e) != "Failed to fetch" else None)
        await pg.goto(BASE + "master_files/hotel_rate_v2", wait_until="load")
        try:
            await pg.wait_for_selector("#hotel_list .hotel-item", timeout=30000)
        except Exception:
            pass
        await pg.wait_for_timeout(1500)
        n_db = int(sql("SELECT COUNT(*) FROM hr2_hotel WHERE status = 'active'") or 0)
        n = await pg.locator("#hotel_list .hotel-item").count()
        check("the hotel list shows the active hotels", n > 0 and n <= n_db, (n, n_db))
        check("the Tour Desk bar is on the panel, Masters lit", (await pg.locator(".tdbar .td-item.on .lbl").first.text_content() or "").strip() == "Masters")
        # a hotel with approved plans
        # a hotel with an approved plan that really carries a rate row, and the first day of that season
        row = sql("SELECT c.hotel_id, h.name, sd.date_from FROM hr2_rate r JOIN hr2_season s ON s.id = r.season_id JOIN hr2_season_date sd ON sd.season_id = s.id JOIN hr2_contract c ON c.id = s.contract_id JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.status = 'approved' AND h.status = 'active' AND (r.dbl > 0 OR r.sgl > 0) ORDER BY c.id LIMIT 1")
        if row:
            hid, hname, vf = row.split("\t")
            await pg.fill("#hotel_search", hname[:12]); await pg.wait_for_timeout(900)
            shown = await pg.locator("#hotel_list .hotel-item").count()
            check("search by name narrows the list", 0 < shown < max(n, 2), shown)
            await pg.locator("#hotel_list .hotel-item").first.click()
            await pg.wait_for_selector("#detail", state="visible", timeout=15000); await pg.wait_for_timeout(1500)
            dtxt = await pg.evaluate("document.querySelector('#detail').innerText")
            check("the detail opens with the hotel's name", hname.split(" ")[0] in dtxt, dtxt[:80])
            check("its plans are listed with their status", "approved" in dtxt.lower() or "plan" in dtxt.lower())
            try:
                await pg.wait_for_selector("#rates_on_box .ro-none, #rates_on_box table.ro-table", timeout=15000)
                rtxt = await pg.text_content("#rates_on_box")
                check("the rate-on-a-date card answers for today: a grid, or 'no approved rate' with the next season named", "No approved rate" in rtxt or await pg.locator("#rates_on_box td.cell").count() > 0, rtxt[:120])
                if vf:
                    await pg.fill("#ro_date", vf); await pg.dispatch_event("#ro_date", "change"); await pg.wait_for_timeout(1500)
                    cells = await pg.locator("#rates_on_box td.cell:not(.none)").count()
                    check(f"on {vf} (a plan's first day) the grid prices at least one room", cells >= 1, cells)
            except Exception as e:
                check("the rate-on-a-date card renders", False, str(e)[:80])
        # the approval queue and readiness
        r = await pg.goto(BASE + "master_files/hotel_rate_approval", wait_until="load"); await pg.wait_for_timeout(2500)
        check("the approval queue opens", r.status == 200 and await pg.locator("#queue_body, .queue, table").count() >= 1)
        drafts = int(sql("SELECT COUNT(*) FROM hr2_contract WHERE status='draft'") or 0)
        qtxt = await pg.evaluate("document.body.innerText")
        check("the queue speaks of the drafts waiting", drafts == 0 or "draft" in qtxt.lower() or "waiting" in qtxt.lower())
        r = await pg.goto(BASE + "master_files/hotel_rate_v2/readiness", wait_until="load"); await pg.wait_for_timeout(2500)
        check("the quotation readiness page opens", r.status == 200)
        j = await pg.evaluate("fetch('%smaster_files/hotel_rate_v2/readiness/data', {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r => r.json())" % BASE)
        check("readiness data answers", isinstance(j, dict) and (j.get("status") == 200 or "data" in j))
        j2 = await pg.evaluate("fetch('%smaster_files/hotel_rate_v2/summary', {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r => r.json())" % BASE)
        check("the panel summary answers for the dashboard tiles", isinstance(j2, dict))
        j3 = await pg.evaluate("fetch('%smaster_files/offer_selection/holds', {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r => ({s: r.status}))" % BASE)
        check("the rate-review holds list answers to the System Administrator", j3["s"] == 200, j3)
        check("no JS errors", not errs, errs[:3])
        await b.close()
    raise SystemExit(summary())
asyncio.run(main())
