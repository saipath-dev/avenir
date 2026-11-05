"""tf-help-r1 demo and checks on the test copy: the User Manual button on every screen, the question
"Where do I go to approve hotel rates?" for three people with different permissions, help for the screen,
the PDF, and that asking changes nothing in the database."""
import asyncio, os, subprocess, sys, json
sys.path.insert(0, os.path.dirname(__file__))
from tc import *

OUT = S + "/tct/help_shots"
os.makedirs(OUT, exist_ok=True)
SOCK = S + "/mysql.sock"
Q = "Where do I go to approve hotel rates?"


def db_state():
    sql = ("SELECT CONCAT((SELECT COUNT(*) FROM hr2_contract), '/', (SELECT COALESCE(SUM(status='approved'),0) FROM hr2_contract), '/', "
           "(SELECT COALESCE(MAX(approved_at),'') FROM hr2_contract), '/', (SELECT COUNT(*) FROM a_log), '/', (SELECT COUNT(*) FROM tf_intake_day), '/', "
           "(SELECT COALESCE(MAX(updated_at),'') FROM tf_intake_day))")
    return subprocess.run(["mysql", "--socket=" + SOCK, "-uroot", "-N", "avenir_test", "-e", sql], capture_output=True, text=True).stdout.strip()


async def ask_on(pg, who, path, q, shot):
    await pg.goto(BASE + path, wait_until="domcontentloaded")
    await pg.wait_for_timeout(1800)
    fab = pg.locator("#avh-root .avh-fab")
    check(f"{who}: User Manual button on {path}", await fab.count() == 1 and await fab.is_visible())
    before = db_state()
    await fab.click()
    await pg.wait_for_selector("#avh-panel:not([hidden]) .avh-list, #avh-panel:not([hidden]) .avh-muted", timeout=8000)
    await pg.wait_for_timeout(600)
    await pg.fill("#avh-q", q)
    await pg.press("#avh-q", "Enter")
    await pg.wait_for_selector("#avh-body .avh-answer, #avh-body .avh-warn, #avh-body .avh-card", timeout=8000)
    await pg.wait_for_timeout(700)
    after = db_state()
    check(f"{who}: asking changed nothing in the database", before == after, before + " vs " + after)
    await pg.screenshot(path=f"{OUT}/{shot}.png")
    txt = await pg.locator("#avh-body").inner_text()
    return txt


async def main():
    async with async_playwright() as p:
        b, ctx = await browser(p, 1440, 900)
        # --- 1. System Administrator on the Hotel Rates screen ---
        pg, _ = await login(ctx, "admin")
        txt = await ask_on(pg, "admin", "master_files/hotel_rate_v2", Q, "01-admin-approve")
        check("admin: answer is 'Approve hotel rates'", "Approve hotel rates" in txt, txt[:300])
        check("admin: steps name Hotel Rate Approval and Approval queue", "Hotel Rate Approval" in txt and "Approval queue" in txt)
        go = pg.locator("#avh-body a.avh-go").first
        check("admin: direct link to the Approval queue", "hotel_rate_approval" in (await go.get_attribute("href") or ""), await go.get_attribute("href"))
        check("admin: 'You have access' shown", "You have access" in txt)
        # follow the link: the queue opens, nothing approved
        before = db_state()
        await go.click()
        await pg.wait_for_timeout(3500)
        check("admin: link opens Hotel Rates with the Approval queue", "hotel_rate_approval" in pg.url and await pg.locator("#m_queue.show, .modal.show").count() >= 1, pg.url)
        await pg.screenshot(path=f"{OUT}/02-admin-link-opens-queue.png")
        after = db_state()
        check("admin: opening the queue approved nothing", before.split('/')[:3] == after.split('/')[:3], before + " vs " + after)
        # help for this screen on the Tour Inquiry page
        await pg.goto(BASE + "tour/intake", wait_until="domcontentloaded"); await pg.wait_for_timeout(1500)
        await pg.click("#avh-root .avh-fab"); await pg.wait_for_selector("#avh-body .avh-list", timeout=8000); await pg.wait_for_timeout(500)
        t2 = await pg.locator("#avh-body").inner_text()
        check("admin: Tour Inquiry shows its own help topics", "Check hotels again" in t2 or "not appearing" in t2, t2[:300])
        await pg.screenshot(path=f"{OUT}/03-admin-help-for-this-screen-inquiry.png")
        # a screen with no topics yet
        await pg.goto(BASE + "dashboard", wait_until="domcontentloaded"); await pg.wait_for_timeout(1500)
        check("admin: button on the dashboard", await pg.locator("#avh-root .avh-fab").count() == 1)
        # a question the manual cannot answer
        await pg.click("#avh-root .avh-fab"); await pg.wait_for_timeout(1200)
        await pg.fill("#avh-q", "How do I pay a driver advance?"); await pg.press("#avh-q", "Enter"); await pg.wait_for_timeout(1500)
        t3 = await pg.locator("#avh-body").inner_text()
        check("admin: no guessing when there is no checked answer", "No checked answer" in t3, t3[:200])
        await pg.screenshot(path=f"{OUT}/04-admin-no-answer.png")
        # two-part question
        await pg.fill("#avh-q", "How do I enter rates manually or correct a rate?"); await pg.press("#avh-q", "Enter"); await pg.wait_for_timeout(1500)
        t4 = await pg.locator("#avh-body").inner_text()
        check("admin: two-part question gets both answers", "Enter hotel rates by hand" in t4 and "Correct a wrong hotel rate" in t4, t4[:200])
        await pg.screenshot(path=f"{OUT}/05-admin-two-part.png", full_page=False)
        # PDF
        r = await pg.request.get(BASE + "help/manual")
        check("PDF opens for viewing (inline)", r.status == 200 and r.headers.get("content-type", "").startswith("application/pdf") and "inline" in r.headers.get("content-disposition", ""), r.headers)
        r2 = await pg.request.get(BASE + "help/manual?download=1")
        check("PDF downloads (attachment)", r2.status == 200 and "attachment" in r2.headers.get("content-disposition", ""))
        await ctx.close()

        # --- 2. Hotel Desk: may enter rates, may not approve ---
        ctx = await b.new_context(viewport={"width": 1440, "height": 900})
        pg, _ = await login(ctx, "hotel")
        txt = await ask_on(pg, "hotel desk", "master_files/hotel_rate_v2", Q, "06-hoteldesk-approve")
        check("hotel desk: told approval needs Hotel Rate Approval", "needs the Hotel Rate Approval permission" in txt, txt[:400])
        check("hotel desk: told who can do it", "Who can do it" in txt and "System Administrator" in txt, txt[:500])
        off = await pg.locator("#avh-body .avh-go.avh-off").count()
        check("hotel desk: Approval queue link shown locked, not as a link", off >= 1)
        # Hotel Desk may upload: answer unlocked
        await pg.fill("#avh-q", "How do I upload a hotel rate sheet?"); await pg.press("#avh-q", "Enter"); await pg.wait_for_timeout(1500)
        t5 = await pg.locator("#avh-body").inner_text()
        check("hotel desk: upload answer with access", "You have access" in t5 and "Read a PDF" in t5, t5[:300])
        await pg.screenshot(path=f"{OUT}/07-hoteldesk-upload.png")
        await ctx.close()

        # --- 3. Quotation Desk: no Hotel Rates at all ---
        ctx = await b.new_context(viewport={"width": 1440, "height": 900})
        pg, _ = await login(ctx, "quote")
        txt = await ask_on(pg, "quotation desk", "tour/intake", Q, "08-quotedesk-approve")
        check("quotation desk: told an authorised person is needed", "Who can do it" in txt, txt[:300])
        await pg.fill("#avh-q", "Why is a hotel rate not appearing in my inquiry?"); await pg.press("#avh-q", "Enter"); await pg.wait_for_timeout(1500)
        t6 = await pg.locator("#avh-body").inner_text()
        check("quotation desk: inquiry troubleshooting with access to Tour Inquiry", "Check hotels again" in t6 and "You have access" in t6, t6[:300])
        await pg.screenshot(path=f"{OUT}/09-quotedesk-not-appearing.png")
        await ctx.close()
        await b.close()
    return summary()

sys.exit(asyncio.run(main()))
