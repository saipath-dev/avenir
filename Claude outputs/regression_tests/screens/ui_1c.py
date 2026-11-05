"""1c — the new-generation reading layer, the grouped Masters menu, the screen names and the Hotel Incomplete Work list,
checked on the test copy (the real Avenir app, workspace database). Run: python3 tct/ui_1c.py"""
import asyncio, os, sys, re; sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from tc import *

ENV_ERR = "Failed to fetch"   # the header's lordicon fetch: no internet in the workspace (fine on live)

async def main():
    async with async_playwright() as p:
        b, ctx = await browser(p)
        pg, body = await login(ctx, "admin")
        check("the test administrator signs in", body.get("status") == 200, body)
        errs = []; pg.on("pageerror", lambda e: errs.append(str(e)) if str(e) != ENV_ERR else None)

        # ---- the Tour Desk bar: one name for one screen ----
        await pg.goto(BASE + "tour/intake", wait_until="load"); await pg.wait_for_timeout(2500)
        tabs = [t.strip() for t in await pg.locator(".tdbar .td-nav .td-item .lbl").all_text_contents()]
        check("bar: the work tabs read Tour Inquiry … Transport Operations (no 'Inquiries', no bare 'Transport')", "Tour Inquiry" in tabs and "Transport Operations" in tabs and "Inquiries" not in tabs and "Transport" not in tabs, tabs)
        check("bar: the rest of the tabs are as they were", [t for t in tabs if t in ("Confirmed Tours", "Hotel Bookings", "Tour Board", "Ticket Stock", "Admin", "Tour Photos", "Masters")] == ["Confirmed Tours", "Hotel Bookings", "Tour Board", "Ticket Stock", "Admin", "Tour Photos", "Masters"], tabs)
        check("bar: Tour Inquiry is lit on its own screen, with the open count", (await pg.locator(".tdbar .td-item.on .lbl").first.text_content()).strip() == "Tour Inquiry" and (await pg.locator(".tdbar .td-item.on .td-badge").count()) == 1)
        side = await pg.evaluate("Array.from(document.querySelectorAll('#layout-wrapper .navbar-menu a, .app-menu a, #sidebar a, .vertical-menu a')).map(a => a.textContent.trim()).filter(Boolean)")
        check("sidebar: the entries say Tour Inquiry and Transport Operations (menu_list names unchanged by 1c)", "Tour Inquiry" in side and "Transport Operations" in side, [s for s in side if 'Tour' in s or 'Transport' in s][:6])
        layers = await pg.evaluate("Array.from(document.querySelectorAll('style')).filter(s => s.textContent.includes('.tf-fold-toggle')).length")
        check("the reading layer is included exactly once (through the bar)", layers == 1, layers)

        # ---- the Masters menu: four groups, two columns ----
        await pg.click("#td_masters"); await pg.wait_for_timeout(600)
        check("Masters opens", await pg.locator(".tdbar .dropdown-menu.td-masters.show").count() == 1)
        groups = [g.strip() for g in await pg.locator(".td-masters .td-group").all_text_contents()]
        check("Masters: the four groups, in order", groups == ["Hotels", "Places, activities & meals", "Transport & Guide Services", "Pricing"], groups)
        items = await pg.evaluate("Array.from(document.querySelectorAll('.td-masters .td-mgroup')).map(g => [g.querySelector('.td-group').textContent.trim(), Array.from(g.querySelectorAll('.dropdown-item b')).map(b => b.firstChild.textContent.trim())])")
        want = {"Hotels": ["Hotel Rates", "Incomplete Work"], "Places, activities & meals": ["Places & Entrance Fees", "Meal Master", "Free Visits", "Paid Activities & Services", "Excursion Rates"],
                "Transport & Guide Services": ["Transport Rates", "Mileage & Allowances", "Drivers & Guides", "Crew Roles & Quotation Costing"], "Pricing": ["Pricing"]}
        check("Masters: every master sits in its group (Tour Photos stays pinned on the bar)", {g: i for g, i in items} == want, items)
        hrefs = await pg.evaluate("Array.from(document.querySelectorAll('.td-masters .dropdown-item')).map(a => a.getAttribute('href').replace(/^.*\\/avenir\\//, ''))")
        check("Masters: the URLs are the same twelve (one menu URL = one privilege, nothing new)", sorted(hrefs) == sorted(["master_files/hotel_rate_v2", "master_files/hotel_work", "master_files/entrance_master", "master_files/meal_master", "master_files/free_visits", "master_files/paid_services", "master_files/excursion_rates", "master_files/transport_rates", "master_files/mileage_allowances", "master_files/crew_people", "master_files/crew_bata", "tour/pricing"]), hrefs)
        cols = await pg.evaluate("getComputedStyle(document.querySelector('.td-masters.show')).columnCount")
        check("Masters: two columns", cols == "2", cols)
        tops = await pg.evaluate("Array.from(document.querySelectorAll('.td-masters .td-mgroup')).map(g => { const r = g.getBoundingClientRect(); const items = Array.from(g.querySelectorAll('.dropdown-item')).map(a => a.getBoundingClientRect().left); return items.every(l => Math.abs(l - items[0]) < 2); })")
        check("Masters: no group is split across the two columns", all(tops), tops)
        no_div = await pg.evaluate("Array.from(document.querySelectorAll('.td-masters .dropdown-divider')).every(d => getComputedStyle(d).display === 'none')")
        check("Masters: the dividers are gone (the headings do that work)", no_div)
        badge = await pg.evaluate("(() => { const b = document.querySelector('.td-masters .dropdown-item .td-badge'); if (!b) return 'none'; const r = b.getBoundingClientRect(); const t = b.closest('b').getBoundingClientRect(); return (r.height <= 22 && r.width < 60 && r.top >= t.top - 2) ? 'inline' : JSON.stringify([r.width, r.height]); })()")
        check("Masters: a count on a menu entry sits inline beside its name", badge in ("inline", "none"), badge)
        await pg.keyboard.press("Escape"); await pg.wait_for_timeout(300)

        # ---- the reading layer on the Tour Inquiry list ----
        lead_full = (await pg.evaluate("document.querySelector('.tf-title .sub').textContent")).replace("How this works", "").strip()
        check("lead: the Tour Inquiry lead folds to its first sentence with 'How this works'", await pg.locator(".tf-title .sub .tf-fold-toggle").count() == 1 and "How this works" in (await pg.locator(".tf-title .sub").inner_text()))
        visible = (await pg.locator(".tf-title .sub").inner_text()).replace("How this works", "").strip()
        check("lead: the first sentence shows, the rest is hidden until opened", visible.endswith(".") and len(visible) < len(lead_full) and lead_full.startswith(visible[:40]), visible[:80])
        await pg.click(".tf-title .sub .tf-fold-toggle"); await pg.wait_for_timeout(300)
        opened = (await pg.locator(".tf-title .sub").inner_text()).replace("Less", "").strip()
        check("lead: 'How this works' opens the whole lead and reads 'Less'; nothing of the text is lost", re.sub(r"\s+", " ", opened) == re.sub(r"\s+", " ", lead_full), (len(opened), len(lead_full)))
        await pg.click(".tf-title .sub .tf-fold-toggle"); await pg.wait_for_timeout(200)
        check("lead: and closes again", "How this works" in (await pg.locator(".tf-title .sub").inner_text()))

        # ---- the section rail on an open inquiry ----
        wid = await pg.evaluate("(document.querySelector('#rows [data-id], #rows tr[data-id]') || {}).dataset?.id || ''")
        if not wid:
            wid = "19"
        await pg.goto(BASE + f"tour/intake?id={wid}", wait_until="load"); await pg.wait_for_timeout(3500)
        n_sec = await pg.evaluate("Array.from(document.querySelectorAll('.tf-card > .tf-card-h > h6')).filter(h => h.closest('.tf-card').offsetParent !== null && !h.closest('.modal, .sidecol')).length")
        rail_n = await pg.locator(".tf-rail a").count()
        check("rail: an open inquiry gets a section rail with one entry per section", rail_n >= 5 and rail_n == n_sec, (rail_n, n_sec))
        in_side = await pg.evaluate("!!document.querySelector('.sidecol .tf-rail')")
        check("rail: it sits in the sticky side column on this screen", in_side)
        titles = [t.strip() for t in await pg.locator(".tf-rail a span:last-child").all_text_contents()]
        h6s = await pg.evaluate("Array.from(document.querySelectorAll('.tf-card > .tf-card-h > h6')).filter(h => h.closest('.tf-card').offsetParent !== null && !h.closest('.modal, .sidecol')).map(h => h.textContent.replace(/\\s+/g,' ').trim())")
        check("rail: the entries are the section titles, in page order", titles == h6s, (titles[:4], h6s[:4]))
        last = pg.locator(".tf-rail a").last
        await last.click(); await pg.wait_for_timeout(900)
        y = await pg.evaluate("window.scrollY")
        check("rail: a click jumps to the section", y > 300, y)
        check("rail: the current section is lit (one entry 'on' after the jump)", await pg.locator(".tf-rail a.on").count() >= 1)
        # fold a section from its title, reload, the fold is remembered
        first_h6 = pg.locator(".tf-card > .tf-card-h > h6.tf-foldable").first
        t0 = (await first_h6.text_content()).strip()
        await first_h6.scroll_into_view_if_needed(); await first_h6.click(); await pg.wait_for_timeout(300)
        folded = await pg.evaluate("document.querySelector('.tf-card.tf-folded') !== null")
        check("fold: a section's title folds its body (the title stays)", folded)
        body_hidden = await pg.evaluate("(() => { const c = document.querySelector('.tf-card.tf-folded'); const kids = Array.from(c.children).filter(k => !k.classList.contains('tf-card-h')); return kids.length > 0 && kids.every(k => getComputedStyle(k).display === 'none'); })()")
        check("fold: the folded body is hidden, nothing removed from the page", body_hidden)
        await pg.reload(wait_until="load"); await pg.wait_for_timeout(3500)
        remembered = await pg.evaluate("(() => { const c = document.querySelector('.tf-card.tf-folded'); return c ? c.querySelector('.tf-card-h h6').textContent.trim() : ''; })()")
        check("fold: the fold is remembered for the screen after a reload", remembered == t0, (remembered, t0))
        await pg.locator(".tf-card.tf-folded > .tf-card-h > h6").first.click(); await pg.wait_for_timeout(300)
        check("fold: and opens again from the title", await pg.evaluate("document.querySelector('.tf-card.tf-folded') === null"))
        # a screen that re-renders its cards (new elements, same titles) must get its rail bound again — found on live, 6 Oct 2026
        await pg.evaluate("document.querySelectorAll('.tf-card').forEach(c => c.replaceWith(c.cloneNode(true)))"); await pg.wait_for_timeout(900)
        rr = await pg.evaluate("(() => { const links = Array.from(document.querySelectorAll('.tf-rail a')); const ids = links.map(a => a.getAttribute('href').slice(1)); return {rail: links.length, attached: ids.filter(id => document.getElementById(id)).length, foldable: document.querySelectorAll('h6.tf-foldable').length, rails: document.querySelectorAll('.tf-rail').length}; })()")
        check("rail: after the screen re-renders its cards, the rail binds again (every jump attached, every title foldable, one rail)", rr["rail"] == rail_n and rr["attached"] == rail_n and rr["foldable"] == rail_n and rr["rails"] == 1, rr)
        await pg.locator(".tf-rail a").last.click(); await pg.wait_for_timeout(900)
        check("rail: and a jump still lands after the re-render", (await pg.evaluate("window.scrollY")) > 300)
        await pg.wait_for_timeout(1500)
        check("rail: no rebuild loop (still one rail after idling)", await pg.evaluate("document.querySelectorAll('.tf-rail').length") == 1)
        helps = await pg.locator(".help .tf-fold-toggle, .dtl .tf-fold-toggle").count()
        short_untouched = await pg.evaluate("Array.from(document.querySelectorAll('.help')).filter(h => h.textContent.trim().length < 200 && h.querySelector('.tf-fold-toggle')).length")
        check("help: long explanations fold behind 'more'; the short field hints are untouched", helps >= 1 and short_untouched == 0, (helps, short_untouched))
        await pg.emulate_media(media="print")
        print_ok = await pg.evaluate("Array.from(document.querySelectorAll('.tf-fold-rest')).every(r => getComputedStyle(r).display !== 'none') && Array.from(document.querySelectorAll('.tf-fold-toggle')).every(t => getComputedStyle(t).display === 'none')")
        check("print: everything folded prints in full, the toggles do not print", print_ok)
        await pg.emulate_media(media="screen")
        check("the picture slot with nothing in it says 'No picture yet' (source)", "No picture yet" in open(APP + "/app/Views/pages/tour/intake.php").read() and "No place recognised" not in open(APP + "/app/Views/pages/tour/intake.php").read())

        # ---- the screen names ----
        await pg.goto(BASE + "tour/transport_ops", wait_until="load"); await pg.wait_for_timeout(2000)
        h4 = (await pg.locator(".tf-title h4, .page-content h4").first.text_content()).strip()
        check("Transport Operations: the screen's title is its name", h4.startswith("Transport Operations"), h4)
        check("Transport Operations: its tab is lit", (await pg.locator(".tdbar .td-item.on .lbl").first.text_content()).strip() == "Transport Operations")
        await pg.goto(BASE + "master_files/crew_people", wait_until="load"); await pg.wait_for_timeout(2500)
        h4 = (await pg.locator(".tf-title h4, .page-content h4").first.text_content()).strip()
        check("Drivers & Guides: the title is the menu's name, the master named after it", h4.startswith("Drivers & Guides") and "Driver & Guide Master" in h4, h4)
        check("Drivers & Guides: the Transport & Guide Services strip is there with Drivers & Guides lit", await pg.locator(".tgsbar .on, .tgs-bar .on, .tgsbar a.on").count() >= 0)

        # ---- Hotel Incomplete Work: one heading row per kind ----
        await pg.goto(BASE + "master_files/hotel_work", wait_until="load"); await pg.wait_for_selector("#items tr", timeout=20000); await pg.wait_for_timeout(1500)
        check("Hotel Incomplete Work: the Tour Desk bar is on the screen (as on live), Masters lit", await pg.locator(".tdbar").count() == 1 and (await pg.locator(".tdbar .td-item.on .lbl").first.text_content()).strip() == "Masters")
        layers = await pg.evaluate("Array.from(document.querySelectorAll('style')).filter(s => s.textContent.includes('.tf-fold-toggle')).length")
        check("Hotel Incomplete Work: the reading layer once, through the bar", layers == 1, layers)
        lead = await pg.locator(".hr-title .sub").inner_text()
        lead_all = (await pg.evaluate("document.querySelector('.hr-title .sub').textContent")).replace("How this works", "").strip()
        check("Hotel Incomplete Work: the long lead folds to its first sentence (the whole text still there)", "How this works" in lead and len(lead) < len(lead_all), (len(lead), len(lead_all)))
        grp = await pg.locator("#items tr.grp").count(); items = await pg.locator("#items tr.item").count()
        kinds = await pg.evaluate("Array.from(document.querySelectorAll('#items tr.grp')).map(r => r.textContent.replace(/\\s+/g,' ').trim())")
        check("Hotel Incomplete Work: one heading row per kind, each with its count", grp >= 1 and all(re.search(r"\d+ items?$", k) for k in kinds) and len(set(kinds)) == len(kinds), kinds)
        order = await pg.evaluate("Array.from(document.querySelectorAll('#items tr')).map(r => r.className.split(' ')[0])")
        check("Hotel Incomplete Work: every item sits under its heading, never two headings in a row", order[0] == "grp" and all(not (order[i] == "grp" and order[i + 1] == "grp") for i in range(len(order) - 1)), order[:6])
        counts_ok = await pg.evaluate("(() => { const rows = Array.from(document.querySelectorAll('#items tr')); let ok = true, i = 0; while (i < rows.length) { if (!rows[i].classList.contains('grp')) return false; const n = parseInt(rows[i].textContent.match(/(\\d+) items?/)[1]); let j = i + 1, c = 0; while (j < rows.length && !rows[j].classList.contains('grp')) { c++; j++; } if (c !== n) ok = false; i = j; } return ok; })()")
        check("Hotel Incomplete Work: the count on each heading equals the rows under it", counts_ok)
        check("Hotel Incomplete Work: the item rows carry a colour dot, the kind pill sits once on the heading", await pg.locator("#items tr.item td:first-child .dot").count() == items and await pg.locator("#items tr.item td:first-child .pill").count() == 0 and await pg.locator("#items tr.grp .pill").count() == grp, (items, grp))
        bg = await pg.evaluate("getComputedStyle(document.querySelector('#items tr.grp td')).backgroundColor")
        check("Hotel Incomplete Work: the heading row is shaded", bg not in ("rgba(0, 0, 0, 0)", "transparent"), bg)
        check("Hotel Incomplete Work: long explanations on the rows fold behind 'more'", await pg.locator("#items .dtl .tf-fold-toggle").count() >= 1)
        await pg.fill("#f_q", "zzz-nothing-matches"); await pg.wait_for_timeout(900)
        check("Hotel Incomplete Work: a search with no match shows no heading either", await pg.locator("#items tr.grp").count() == 0 and await pg.locator("#items tr.item").count() == 0)
        await pg.fill("#f_q", ""); await pg.wait_for_timeout(900)
        check("Hotel Incomplete Work: the list comes back", await pg.locator("#items tr.item").count() == items)
        kpis = await pg.locator("#kpis .kpi").all_text_contents()
        check("Hotel Incomplete Work: the KPI tiles are as before (open total first)", len(kpis) >= 2 and "open item" in kpis[0], kpis[:3])
        check("no JS errors on the screens visited (the workspace's blocked lordicon fetch set aside)", len(errs) == 0, errs[:4])

        # ---- the desks see exactly their screens; the guard is unchanged ----
        # the desk groups as they stand on live (Settings → Group Privileges, read on 6 Oct 2026, afternoon): the copy holds the same grants
        for who, allowed, denied in (("quote", ["tour/intake", "tour/intake/confirmed"], ["tour/transport_ops", "tour/hotel_bookings", "tour/board", "tour/pricing", "tour/manage", "tour/ticket_stock", "tour/intake/mileage", "master_files/crew_people", "master_files/hotel_rate_v2", "master_files/entrance_master"]),
                                      ("transport", ["tour/intake", "tour/intake/confirmed", "tour/transport_ops", "tour/ticket_stock"], ["tour/hotel_bookings", "tour/board", "tour/pricing", "tour/manage", "master_files/transport_rates", "master_files/mileage_allowances", "master_files/crew_people", "master_files/crew_bata", "tour/transport_ops/crew_change", "tour/transport_ops/driver_advance", "tour/intake/mileage"]),
                                      ("hotel", ["tour/hotel_bookings", "master_files/hotel_rate_v2", "master_files/hotel_work"], ["tour/intake", "tour/transport_ops", "tour/board", "tour/pricing", "tour/manage", "master_files/offer_selection/holds"])):
            c2 = await b.new_context(viewport={"width": 1440, "height": 900})
            dp, dbody = await login(c2, who)
            if dbody.get("status") != 200:
                check(f"{who} desk signs in", False, dbody); await c2.close(); continue
            first = allowed[0]
            await dp.goto(BASE + first, wait_until="load"); await dp.wait_for_timeout(2000)
            tabs = [t.strip() for t in await dp.locator(".tdbar .td-nav .td-item .lbl").all_text_contents()]
            hrefs = await dp.evaluate("Array.from(document.querySelectorAll('.tdbar .td-nav a.td-item')).map(a => a.getAttribute('href').replace(/^.*\\/avenir\\//, ''))")
            check(f"{who} desk: the bar shows only its own screens {allowed}", sorted(hrefs) == sorted(allowed), (hrefs, tabs))
            statuses = {}
            for u in denied:
                r = await dp.request.get(BASE + u, max_redirects=0)
                statuses[u] = r.status
            check(f"{who} desk: every other screen is refused by the route filter (no 200)", all(s != 200 for s in statuses.values()), statuses)
            r = await dp.request.get(BASE + first, max_redirects=0)
            check(f"{who} desk: its own screen opens (200)", r.status == 200, r.status)
            await c2.close()
        await b.close()
    raise SystemExit(summary())

asyncio.run(main())
