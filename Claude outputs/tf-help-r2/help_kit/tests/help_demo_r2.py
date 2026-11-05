"""tf-help-r2 checks on a test copy (TC_HOST, default the cloud mirror of the live code on 127.0.0.1:8081):
one manual per screen — Hotel Rates and Hotel Master on Hotel Rates (new), chosen by the open tab; Hotel Master
on Supplier; no own manual on Tour Inquiry and the dashboard; answers from another manual labelled; each manual's
own PDF and the full manual; permissions; and that opening help or asking changes nothing in the database."""
import asyncio, os, subprocess, sys, json
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, os.environ.get("TC_LIB", "/tmp/claude-0/-home-claude/1f177919-91eb-5b5f-a3ff-b7531d256531/scratchpad/tct"))
from tc import *
OUT = S + "/tct/help_r2_shots"
os.makedirs(OUT, exist_ok=True)
SOCK = S + "/mysql.sock"
Q = "Where do I go to approve hotel rates?"

def db_state():
    sql = ("SELECT CONCAT((SELECT COUNT(*) FROM hr2_contract), '/', (SELECT COALESCE(SUM(status='approved'),0) FROM hr2_contract), '/', "
           "(SELECT COALESCE(MAX(approved_at),'') FROM hr2_contract), '/', (SELECT COUNT(*) FROM hr2_hotel), '/', (SELECT COALESCE(SUM(status='active'),0) FROM hr2_hotel), '/', "
           "(SELECT COUNT(*) FROM hr2_audit), '/', (SELECT COUNT(*) FROM supplier), '/', (SELECT COUNT(*) FROM tf_intake_day))")
    return subprocess.run(["mysql", "--socket=" + SOCK, "-uroot", "-N", "avenir_test", "-e", sql], capture_output=True, text=True).stdout.strip()

async def panel_open(pg):
    await pg.click("#avh-root .avh-fab")
    await pg.wait_for_selector("#avh-panel:not([hidden]) #avh-body .avh-list, #avh-panel:not([hidden]) #avh-body .avh-warn", timeout=8000)
    await pg.wait_for_timeout(500)

async def ask(pg, q):
    await pg.fill("#avh-q", q); await pg.press("#avh-q", "Enter")
    await pg.wait_for_selector("#avh-body .avh-back", timeout=8000); await pg.wait_for_timeout(600)
    return await pg.locator("#avh-body").inner_text()

async def sub(pg):
    return await pg.locator("#avh-sub").inner_text()

async def main():
    start = db_state()
    async with async_playwright() as p:
        b = await p.chromium.launch()
        ctx = await b.new_context(viewport={"width": 1440, "height": 900})
        pg, _ = await login(ctx, "admin")
        HR = "master_files/hotel_rate_v2"
        # 1. Hotel Rates (new), nothing open: the Hotel Rates manual, with a switch to Hotel Master
        await pg.goto(BASE + HR, wait_until="domcontentloaded"); await pg.wait_for_timeout(2500)
        await panel_open(pg)
        s = await sub(pg); body = await pg.locator("#avh-body").inner_text()
        check("Hotel Rates screen opens the Hotel Rates manual", s.startswith("Hotel Rates manual"), s)
        check("both manuals of this screen offered", await pg.locator("#avh-body .avh-switch [data-section=hotel_rates].on").count() == 1 and await pg.locator("#avh-body .avh-switch [data-section=hotel_master]").count() == 1)
        check("Hotel Rates manual lists its own 17 topics", "HOTEL RATES MANUAL · 17 TOPICS" in body.upper(), body[:300])
        foot = await pg.locator("#avh-foot").inner_text()
        hrefs = await pg.locator("#avh-foot a").evaluate_all("as => as.map(a => a.getAttribute('href'))")
        check("footer: this manual's PDF and the full manual", "Hotel Rates manual:" in foot and any(h.endswith("help/manual/hotel_rates") for h in hrefs) and any(h.endswith("help/manual") for h in hrefs), str(hrefs))
        await pg.screenshot(path=f"{OUT}/01-hotel-rates-manual.png")
        txt = await ask(pg, Q)
        check("approve question answered in the Hotel Rates manual", "Approve hotel rates" in txt and "from the" not in txt.split("Approve hotel rates")[0], txt[:200])
        go = await pg.locator("#avh-body a.avh-go").first.get_attribute("href")
        check("direct link to the Approval queue", go and go.endswith("master_files/hotel_rate_approval"), go)
        await pg.screenshot(path=f"{OUT}/02-approve-answer.png")
        txt = await ask(pg, "How do I add a new hotel?")
        check("a Hotel Master question from the Hotel Rates manual: answered, marked as from the other manual", "Add a new hotel" in txt and "from the Hotel Master manual" in txt, txt[:200])
        await pg.screenshot(path=f"{OUT}/03-answer-from-other-manual.png")
        await pg.click("#avh-panel .avh-x"); await pg.wait_for_timeout(300)
        # 2. a hotel open on its Hotel details tab: the Hotel Master manual
        await pg.fill("#hotel_search", "Heritance Ahungalla"); await pg.wait_for_timeout(600)
        await pg.locator("#hotel_list .hotel-item").first.click(); await pg.wait_for_timeout(2500)
        await pg.locator(".hr-tab[data-tab=hotel]").click(); await pg.wait_for_timeout(500)
        await pg.click("#avh-root .avh-fab"); await pg.wait_for_timeout(800)
        s = await sub(pg); body = await pg.locator("#avh-body").inner_text()
        check("Hotel details tab open → the Hotel Master manual", s.startswith("Hotel Master manual"), s)
        check("Hotel Master manual lists its own 12 topics", "HOTEL MASTER MANUAL · 12 TOPICS" in body.upper(), body[:300])
        check("Hotel Master examples offered", "How do I add a new hotel?" in body)
        await pg.screenshot(path=f"{OUT}/04-hotel-master-manual.png")
        txt = await ask(pg, "How do I change a hotel's reservations e-mail?")
        check("reservations e-mail answered", "The hotel's reservations e-mail" in txt and "You have access" in txt, txt[:200])
        await pg.screenshot(path=f"{OUT}/05-reservations-email.png")
        # switch by hand
        await pg.click("#avh-body .avh-back [data-home]"); await pg.wait_for_timeout(400)
        await pg.click("#avh-body .avh-switch [data-section=hotel_rates]"); await pg.wait_for_timeout(400)
        check("switch to the Hotel Rates manual by hand", (await sub(pg)).startswith("Hotel Rates manual"))
        await pg.click("#avh-panel .avh-x"); await pg.wait_for_timeout(300)
        # back to the rate tab: Hotel Rates again
        await pg.locator(".hr-tab[data-tab=plans]").click(); await pg.wait_for_timeout(400)
        await pg.click("#avh-root .avh-fab"); await pg.wait_for_timeout(600)
        check("rate tab open → the Hotel Rates manual again", (await sub(pg)).startswith("Hotel Rates manual"))
        await pg.click("#avh-panel .avh-x"); await pg.wait_for_timeout(300)
        # Room types tab
        await pg.locator(".hr-tab[data-tab=rooms]").click(); await pg.wait_for_timeout(400)
        await pg.click("#avh-root .avh-fab"); await pg.wait_for_timeout(600)
        check("Room types tab open → the Hotel Master manual", (await sub(pg)).startswith("Hotel Master manual"))
        await pg.click("#avh-panel .avh-x")
        # 3. Supplier: the Hotel Master manual only
        await pg.goto(BASE + "master_files/supplier", wait_until="domcontentloaded"); await pg.wait_for_timeout(1800)
        await panel_open(pg)
        check("Supplier screen opens the Hotel Master manual", (await sub(pg)).startswith("Hotel Master manual"))
        check("no switch on a one-manual screen", await pg.locator("#avh-body .avh-switch").count() == 0)
        txt = await ask(pg, "How do I add a new hotel?")
        go = await pg.locator("#avh-body a.avh-go").first.get_attribute("href")
        check("add-hotel answer links to New Supplier", "Supplier Catogory" in txt and go and go.endswith("master_files/supplier"), go)
        await pg.screenshot(path=f"{OUT}/06-supplier-add-hotel.png")
        # 4. Tour Inquiry: no own manual yet; the hotel topics that help there
        await pg.goto(BASE + "tour/intake", wait_until="domcontentloaded"); await pg.wait_for_timeout(1800)
        await panel_open(pg)
        body = await pg.locator("#avh-body").inner_text()
        check("Tour Inquiry: says it has no manual of its own yet, names the manuals there are", "no manual of its own yet" in body and "Hotel Rates and Hotel Master" in body, body[:300])
        check("Tour Inquiry: the hotel topics for this screen, labelled", "Check hotels again" in body and await pg.locator("#avh-body .avh-tag").count() >= 3)
        await pg.screenshot(path=f"{OUT}/07-tour-inquiry.png")
        txt = await ask(pg, "how do i do a quotation hotel rates are already finalized")
        check("quotation question: no checked answer, no guessing", "No checked answer" in txt and "Hotel Rates and Hotel Master" in txt, txt[:300])
        txt = await ask(pg, "how to add a driver")
        check("'how to add a driver': no checked answer (not 'Add a new hotel')", "No checked answer" in txt and "Add a new hotel" not in txt.split("QUESTIONS YOU CAN ASK")[0].split("Questions you can ask")[0], txt[:300])
        txt = await ask(pg, "Why is a hotel rate not appearing in my inquiry?")
        check("inquiry troubleshooting still answered on Tour Inquiry", "Why is a hotel rate not appearing" in txt, txt[:200])
        # 5. dashboard
        await pg.goto(BASE + "dashboard", wait_until="domcontentloaded"); await pg.wait_for_timeout(1500)
        await panel_open(pg)
        body = await pg.locator("#avh-body").inner_text()
        check("dashboard: other manuals one click away", "Other manuals" in body)
        await pg.click("#avh-body details.avh-all summary >> nth=0"); await pg.wait_for_timeout(300)
        await pg.click("#avh-body [data-section=hotel_master].avh-inline"); await pg.wait_for_timeout(400)
        check("dashboard: open the Hotel Master manual from Other manuals", (await sub(pg)).startswith("Hotel Master manual"))
        # 6. the help page lists every manual with its PDF
        await pg.goto(BASE + "help", wait_until="domcontentloaded"); await pg.wait_for_timeout(1800)
        body = await pg.locator("#avh-body").inner_text()
        check("help page: both manuals, each with its PDF", "Hotel Rates manual" in body and "Hotel Master manual" in body and await pg.locator("#avh-body .avh-pman a[href$='help/manual/hotel_master']").count() == 1, body[:200])
        await pg.screenshot(path=f"{OUT}/08-help-page.png", full_page=True)
        # 7. PDFs
        for path, name in (("help/manual/hotel_rates", "Avenir-Hotel-Rates-Manual-"), ("help/manual/hotel_master", "Avenir-Hotel-Master-Manual-"), ("help/manual", "Avenir-User-Manual-")):
            r = await pg.request.get(BASE + path)
            body = await r.body()
            check(f"PDF {path} opens inline as {name}…", r.status == 200 and body[:5] == b"%PDF-" and "inline" in r.headers.get("content-disposition", "") and name in r.headers.get("content-disposition", ""), r.headers.get("content-disposition"))
            r = await pg.request.get(BASE + path + "?download=1")
            check(f"PDF {path} downloads", r.status == 200 and "attachment" in r.headers.get("content-disposition", ""))
        r = await pg.request.get(BASE + "help/manual/general"); check("no PDF for a made-up or general section", r.status == 404)
        r = await pg.request.get(BASE + "help/manual/..%2F..%2Fenv"); check("PDF route refuses odd names", r.status in (404, 400))
        await ctx.close()
        # 8. Hotel Desk: may change details, may not archive or merge
        ctx = await b.new_context(viewport={"width": 1440, "height": 900})
        pg, _ = await login(ctx, "hotel")
        await pg.goto(BASE + HR, wait_until="domcontentloaded"); await pg.wait_for_timeout(2500)
        await pg.fill("#hotel_search", "Heritance Ahungalla"); await pg.wait_for_timeout(600)
        await pg.locator("#hotel_list .hotel-item").first.click(); await pg.wait_for_timeout(2500)
        await pg.locator(".hr-tab[data-tab=hotel]").click(); await pg.wait_for_timeout(400)
        await panel_open(pg)
        check("hotel desk: Hotel details → Hotel Master manual", (await sub(pg)).startswith("Hotel Master manual"))
        txt = await ask(pg, "How do I archive a hotel we no longer use?")
        check("hotel desk: archiving needs Tour Desk Admin, says who", "Tour Desk Admin" in txt and "Who can do it" in txt, txt[:400])
        await pg.screenshot(path=f"{OUT}/09-hoteldesk-archive.png")
        txt = await ask(pg, "How do I change a hotel's details?")
        check("hotel desk: may change the details", "You have access" in txt, txt[:200])
        await ctx.close()
        await b.close()
    end = db_state()
    check("nothing in the database changed (plans, approvals, hotels, history, suppliers, inquiry nights)", start == end, start + " vs " + end)
    return summary()

sys.exit(asyncio.run(main()))
