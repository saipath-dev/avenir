"""The hotel code HTL-### on every screen that shows a hotel (6 Oct 2026): the Hotel Rates panel (rail, header, Hotel
details), Hotel Incomplete Work (chips, the Attach supplier account picker, the supplier-details note, the whole-hotel
Ignore), the old Supplier master's preview, the quotation's hotel search. Screenshots under _work/hotel_codes/."""
import asyncio, sys, os, time
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from tc import *
OUT = os.path.join(S, "hotel_codes"); os.makedirs(OUT, exist_ok=True)
async def main():
    async with async_playwright() as p:
        b, ctx = await browser(p, 1440, 900)
        pg, body = await login(ctx, "admin")
        errs = []; pg.on("pageerror", lambda e: errs.append(str(e)) if str(e) not in ("Failed to fetch",) and "google is not defined" not in str(e) else None)
        # 1. Hotel Rates panel: rail + header + Hotel tab
        await pg.goto(BASE + "master_files/hotel_rate_v2", wait_until="load"); await pg.wait_for_timeout(3500)
        await pg.fill("#hotel_search", "HTL-002"); await pg.wait_for_timeout(600)
        n = await pg.locator("#hotel_list .hotel-item").count()
        check("rail search 'HTL-002' narrows to one hotel", n == 1, n)
        await pg.click("#hotel_list .hotel-item"); await pg.wait_for_timeout(2500)
        check("the header shows the code chip", (await pg.locator("#h_code").inner_text()).strip() == "HTL-002", await pg.locator("#h_code").inner_text())
        await pg.fill("#hotel_search", ""); await pg.wait_for_timeout(600)
        await pg.screenshot(path=f"{OUT}/01_hotel_rates_rail_and_header.png")
        # the Hotel tab (details form)
        await pg.click(".hr-tab[data-tab='hotel']"); await pg.wait_for_timeout(1200)
        txt = (await pg.evaluate("document.body.innerText")).lower()
        check("the Hotel tab shows 'Hotel code' read-only and 'Supplier accounts'", "hotel code" in txt and "supplier accounts" in txt and "permanent" in txt)
        await pg.evaluate("document.querySelector('.hr-tab[data-tab=hotel]').scrollIntoView({block:'start'})"); await pg.wait_for_timeout(400)
        await pg.screenshot(path=f"{OUT}/02_hotel_rates_hotel_tab.png", full_page=False)
        # 2. Hotel Incomplete Work
        await pg.goto(BASE + "master_files/hotel_work", wait_until="load"); await pg.wait_for_timeout(3500)
        chips = await pg.locator("#items .hcode").count()
        check("items carry hotel code chips", chips > 50, chips)
        await pg.screenshot(path=f"{OUT}/03_hotel_work_list.png")
        # attach dialog with the pick list
        await pg.fill("#f_q", "Moi Talpe") if await pg.locator("#f_q").count() else None
        await pg.wait_for_timeout(1200)
        btn = pg.locator("#items [data-act='attach']").first
        check("an 'Attach supplier account' button is there", await btn.count() == 1 and "account" in (await btn.inner_text()))
        await btn.click(); await pg.wait_for_timeout(800)
        await pg.fill("#at_q", "Jetwing"); await pg.wait_for_timeout(900)
        rows = await pg.locator("#at_results button").count()
        check("typing 'Jetwing' lists suppliers to pick from, with codes", rows >= 3 and (await pg.locator("#at_results .hcode").count()) >= 3, rows)
        check("Attach & load stays disabled until one is chosen", await pg.locator("#at_ok").is_disabled())
        await pg.locator("#at_results button").first.click(); await pg.wait_for_timeout(300)
        check("choosing one enables it and shows the choice", (not await pg.locator("#at_ok").is_disabled()) and await pg.locator("#at_chosen").is_visible())
        await pg.screenshot(path=f"{OUT}/04_hotel_work_attach_pick.png")
        await pg.keyboard.press("Escape"); await pg.wait_for_timeout(600)
        # supplier details modal: code line
        sb = pg.locator("#items [data-act='supplier']").first
        await sb.click(); await pg.wait_for_timeout(700)
        check("the supplier-details modal explains the code is given automatically", "given automatically" in (await pg.locator("#sd_code").inner_text()))
        await pg.screenshot(path=f"{OUT}/05_hotel_work_supplier_details.png")
        await pg.keyboard.press("Escape"); await pg.wait_for_timeout(600)
        # ignore dialog with the whole-hotel option (Misty Villa: two sheets)
        if await pg.locator("#f_q").count():
            await pg.fill("#f_q", "Misty Villa"); await pg.wait_for_timeout(1200)
        ib = pg.locator("#items [data-act='ignored']").first
        await ib.click(); await pg.wait_for_timeout(600)
        check("Ignore offers to take the hotel's other open sheet along", await pg.locator("#m_resolve_scope_wrap").is_visible() and "1 other open" in (await pg.locator("#m_resolve_scope_label").inner_text()), await pg.locator("#m_resolve_scope_label").inner_text())
        await pg.screenshot(path=f"{OUT}/06_hotel_work_ignore_whole_hotel.png")
        await pg.keyboard.press("Escape"); await pg.wait_for_timeout(500)
        # 3. legacy Supplier master: the preview
        await pg.goto(BASE + "master_files/supplier", wait_until="load"); await pg.wait_for_timeout(2500)
        sel = pg.locator("#sup_cat, select[name='sup_cat']").first
        if await sel.count():
            await sel.select_option("1"); await pg.wait_for_timeout(1500)
            v = await pg.input_value("#hotel_code")
            check("the old Supplier screen previews the next H-number from the shared sequence", v.startswith("H") and v[1:].isdigit(), v)
            await pg.screenshot(path=f"{OUT}/07_legacy_supplier_preview.png")
        # 4. the quotation's hotel search
        iid = sql("SELECT i.id FROM tf_intake i JOIN tf_intake_day d ON d.intake_id = i.id WHERE i.archived_at IS NULL ORDER BY i.id DESC LIMIT 1")
        if iid:
            await pg.goto(BASE + f"tour/intake?id={iid}", wait_until="load"); await pg.wait_for_timeout(4000)
            tr = pg.locator("#detail_cols [data-day]").first
            if await tr.count():
                await tr.click(); await pg.wait_for_timeout(1200)
                await pg.fill("#md_hotel", "HTL-002"); await pg.wait_for_timeout(1500)
                s = await pg.locator("#md_hotel_sugg").inner_text()
                check("the night's hotel search finds a hotel by its code", "HTL-002" in s and "Cinnamon Bentota" in s, s[:120])
                await pg.screenshot(path=f"{OUT}/08_quotation_hotel_search_by_code.png")
                await pg.fill("#md_hotel", "cinnamon"); await pg.wait_for_timeout(1200)
                s = await pg.locator("#md_hotel_sugg").inner_text()
                check("and shows the code beside each name", "HTL-" in s, s[:120])
                await pg.keyboard.press("Escape")
        check("no page script error on these screens", not errs, errs[:3])
        await b.close()
    sys.exit(summary())
asyncio.run(main())
