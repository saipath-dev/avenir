"""tf-tourmail-r1 on the cloud mirror (8081, test database, local test mail sink — no mail leaves this machine).
Test inquiry INQ 1019 (test copy): Confirm Tour with the hotel list, the mails, the alert, Sent Mails, Email all hotels."""
import asyncio, os, sys, json, glob, email, subprocess
from email import policy
sys.path.insert(0, os.path.dirname(__file__))
from tc import *
SINK = S + "/smtpsink/msgs"; OUT = S + "/tct/tourmail_shots"; os.makedirs(OUT, exist_ok=True)
def msgs(): return sorted(glob.glob(SINK + "/*.eml"))
def sql(q): return subprocess.run(["mysql", "--socket=" + S + "/mysql.sock", "-uroot", "-N", "avenir_test", "-e", q], capture_output=True, text=True).stdout.strip()
HDR = {"Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest"}
async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch(); ctx = await b.new_context(viewport={"width": 1400, "height": 950})
        pg, _ = await login(ctx, "admin")
        LOGS = []
        pg.on("console", lambda m: LOGS.append("console " + m.type + ": " + m.text))
        pg.on("pageerror", lambda e: LOGS.append("pageerror: " + str(e)))
        pg.on("request", lambda r: LOGS.append("req " + r.method + " " + r.url) if r.method == "POST" else None)

        # 1. the list before Confirm
        r = await pg.request.get(BASE + "tour/intake/intake/19/mail_preview", headers=HDR); j = await r.json()
        d = j.get("data") or {}
        st = d.get("stays") or []
        check("1. preview: 7 stays as Confirm makes them", j.get("status") == 200 and len(st) == 7, json.dumps(j)[:300])
        names = [s["hotel_name"] for s in st]
        check("   addresses: Kynrock, Kandalama, Queens, Lighthouse have one; Kassapa, Palms, Morven none", [bool(s["email"]) for s in st] == [True, False, True, True, True, False, False], str([(s["hotel_name"], s["email"]) for s in st]))
        check("   no address → listed as will not be sent, with why", all(("No e-mail address" in (s["blocked"] or "")) for s in st if not s["email"]))
        check("   room type: agent asked '1 Twin Room', rooms were DBL → corrected to TWN, noted", d["rooms"]["suggest"] == "twin" and all("TWN" in s["rooms_line"] and "DBL" not in s["rooms_line"] and "DBL" in (s.get("rooms_was") or "") for s in st), str([(s["rooms_line"], s.get("rooms_was")) for s in st]))
        check("   driver rooms set by hand to 2 nights → the person says which (none pre-filled), warned", d["crew"]["driver"]["choose"] and d["crew"]["driver"]["nights"] == 2 and all(s["driver"] == 0 for s in st), json.dumps(d["crew"]))
        check("   sender: the signed-in mailbox", d["sender"]["can_send"] and d["sender"]["email"] == "quotes-test@avenirholidays.com")
        n0 = len(msgs())
        check("   the preview sent nothing", len(msgs()) == n0)
        # 2. Confirm Tour on the screen
        await pg.goto(BASE + "tour/intake", wait_until="domcontentloaded"); await pg.wait_for_timeout(2500)
        btn = pg.locator('[data-confirm="19"]')
        if await btn.count() == 0:
            await pg.fill("#f_q", "1019") if await pg.locator("#f_q").count() else None
            await pg.wait_for_timeout(1500)
        check("2. Confirm Tour on the inquiry list", await btn.count() == 1)
        await btn.click(); await pg.wait_for_timeout(2500)
        # restaurants step (if shown)
        t = await pg.locator(".swal2-title").inner_text() if await pg.locator(".swal2-title").count() else ""
        if t.startswith("Restaurants"):
            await pg.screenshot(path=OUT + "/00-restaurants.png")
            await pg.click(".swal2-confirm"); await pg.wait_for_timeout(2500)
            t = await pg.locator(".swal2-title").inner_text()
        check("   the hotel list comes up before anything", t.startswith("Hotel booking requests"), t)
        html = await pg.locator(".swal2-html-container").inner_html()
        check("   it shows the corrected room type and why", "corrected (was" in html and "Room type corrected on the letters to TWN" in html)
        check("   it warns about the driver's 2 nights", "Driver rooms were set by hand to 2 nights" in html)
        summ = await pg.locator("#tm_sum").inner_text()
        check("   4 will be sent, 3 will NOT (no address)", "4 requests will be sent" in summ and "3 will NOT be sent" in summ, summ)
        # the person: driver's 2 nights at Kassapa (day 2-3); types an address for The Palms; leaves Morven and Kassapa without
        await pg.locator('[data-crew="driver"][data-i="1"]').fill("2")
        await pg.locator('[data-mail="5"]').fill("palms-test@example.invalid")
        await pg.wait_for_timeout(300)
        summ = await pg.locator("#tm_sum").inner_text()
        check("   typing The Palms' address: 5 will go, 2 will not", "5 requests will be sent" in summ and "2 will NOT be sent" in summ, summ)
        await pg.screenshot(path=OUT + "/01-hotel-list.png")
        check("   still nothing sent", len(msgs()) == n0)
        await pg.click(".swal2-confirm"); await pg.wait_for_timeout(1200)
        # password (TEST account of the test copy)
        await pg.fill(".swal2-input", ACC["admin"][1]); await pg.click(".swal2-confirm"); await pg.wait_for_timeout(2000)
        html = await pg.locator(".swal2-html-container").inner_html()
        check("3. the final warning says what goes when Confirm Tour is pressed", "5 hotel booking requests" in html and "2 will NOT be sent" in html, html[-400:])
        await pg.screenshot(path=OUT + "/02-final-warning.png")
        check("   still nothing sent", len(msgs()) == n0)
        await pg.click(".swal2-confirm")
        await pg.wait_for_function("(document.querySelector('.swal2-title')||{}).textContent && /confirmed|No clear answer|Not/.test(document.querySelector('.swal2-title').textContent)", timeout=150000); await pg.wait_for_timeout(1500)
        t = await pg.locator(".swal2-title").inner_text()
        print("   [result dialog]", t, (await pg.locator(".swal2-html-container").inner_text())[:300])
        html = await pg.locator(".swal2-html-container").inner_html()
        check("4. confirmed; the result lists each hotel: 5 sent, 2 NOT sent with why", "confirmed" in t and html.count(">sent<") == 5 and html.count("NOT sent<") == 2 and "No e-mail address" in html, t)
        await pg.screenshot(path=OUT + "/03-result.png")
        m = msgs()
        check("   the mail server got 5 messages", len(m) == n0 + 5, str(len(m) - n0))
        rcpts = sorted(open(x + ".rcpt").read() for x in m[n0:])
        check("   to the 5 hotels' addresses", rcpts == sorted(["kynrock-test@example.invalid", "kandalama-test@example.invalid", "queens-test@example.invalid", "lighthouse-test@example.invalid", "palms-test@example.invalid"]), str(rcpts))
        bodies = []
        for x in m[n0:]:
            msg = email.message_from_bytes(open(x, "rb").read(), policy=policy.default)
            h = next((pt.get_content() for pt in msg.walk() if pt.get_content_type() == "text/html"), "")
            bodies.append((open(x + ".rcpt").read(), h))
        k = dict(bodies)
        check("   letters: rooms TWN (no DBL), names (TWN), rate basis, no figures", all("1 TWN" in h and "(DBL)" not in h and "1 DBL" not in h and "As per agreed contract rates" in h and "USD" not in h for h in k.values()))
        check("   crew: no driver line where none was set; Kassapa's set to 2 — Kassapa had no address, so it was not sent", all("Driver room required" not in h for h in k.values()))
        tid = int(sql("SELECT id FROM tf_tour WHERE intake_id=19"))
        cards = sql(f"SELECT id, day_from, status, rooms_note, IFNULL(crew_rooms,'-') FROM tf_hotel_job WHERE tour_id={tid} ORDER BY seq")
        check("5. the cards: TWN, Kassapa's driver line, requested where sent, to book where not", cards.count("requested") == 5 and cards.count("to_book") == 2 and "Driver room required (2 nights)" in cards and "DBL" not in cards, cards)
        ev = sql(f"SELECT COUNT(*) FROM tf_tour_event WHERE tour_id={tid} AND event='letter_lines' AND note LIKE '%room type corrected from \"1 DBL%to \"1 TWN%the agent asked for \"1 Twin Room\"%'")
        check("   the change of room type is noted on each stay's trail", ev == "7", ev)
        nf = sql(f"SELECT COUNT(*) FROM tf_hotel_message WHERE tour_id={tid} AND state='failed' AND error LIKE 'No e-mail address%'")
        check("   the 2 not sent are kept with why", nf == "2", nf)
        # 6. the alert, Sent Mails
        r = await pg.request.get(BASE + "notification/unsent_mails", headers=HDR); a = (await r.json())["data"]
        check("6. the alert: 2 not sent, for this tour", a["allowed"] and a["count"] == 2 and all(i["tour_id"] == tid for i in a["items"]), json.dumps(a)[:300])
        await pg.goto(BASE + "tour/hotel_bookings", wait_until="domcontentloaded"); await pg.wait_for_timeout(2500)
        al = pg.locator("#mailAlertDropdown")
        check("   the red alert shows in the top bar", await al.count() == 1 and "d-none" not in (await al.get_attribute("class")) and (await pg.locator("#mail_alert_count").inner_text()) == "2")
        await pg.click("#page-header-mailalert"); await pg.wait_for_timeout(800)
        await pg.screenshot(path=OUT + "/04-alert.png")
        await pg.goto(BASE + f"tour/hotel_bookings/mails?tour_id={tid}", wait_until="domcontentloaded"); await pg.wait_for_timeout(2500)
        txt = await pg.locator("#sm_list").inner_text()
        check("7. Sent Mails lists the 5 sent and the 2 not sent", txt.count("Sent\n") + txt.count("Sent\t") >= 0 and txt.count("NOT sent") == 2 and txt.count("Hotel booking request") == 7, txt[:400])
        await pg.screenshot(path=OUT + "/05-sent-mails.png", full_page=True)
        await pg.click('#sm_chips [data-state="not_sent"]'); await pg.wait_for_timeout(1500)
        txt = await pg.locator("#sm_list").inner_text()
        check("   Not sent: the 2", txt.count("NOT sent") == 2 and txt.count("Hotel booking request") == 2, txt[:300])
        hid = int(sql(f"SELECT MAX(id) FROM tf_hotel_message WHERE tour_id={tid} AND state='sent'"))
        r = await pg.request.get(BASE + f"tour/hotel_bookings/mails/copy/hotel/{hid}"); h = await r.text()
        check("   the copy as it went opens", r.status == 200 and "HOTEL BOOKING REQUEST" in h and "Sent" in h)
        # 8. Email all hotels (Hotel Desk): the 2 left — one typed now, one left out
        await pg.goto(BASE + f"tour/hotel_bookings?tour={tid}", wait_until="domcontentloaded"); await pg.wait_for_timeout(3000)
        await pg.click("#tv_mailall"); await pg.wait_for_timeout(2000)
        html = await pg.locator(".swal2-html-container").inner_html()
        check("8. Email all hotels: the 5 sent show as requested already, not sent again", html.count("Requested already") == 5, str(html.count("Requested already")))
        idx = [i for i, s in enumerate(st) if s["hotel_name"] and "Kassapa" in s["hotel_name"]][0]
        await pg.locator(f'[data-mail="{idx}"]').fill("kassapa-test@example.invalid"); await pg.wait_for_timeout(300)
        await pg.screenshot(path=OUT + "/06-email-all.png")
        n1 = len(msgs())
        await pg.click(".swal2-confirm")
        await pg.wait_for_function("(document.querySelector('.swal2-title')||{}).textContent && /sent|NOT|No clear answer/.test(document.querySelector('.swal2-title').textContent)", timeout=150000); await pg.wait_for_timeout(1000)
        await pg.screenshot(path=OUT + "/07-email-all-result.png")
        check("   Kassapa sent now; Morven still not sent; nothing sent twice", len(msgs()) == n1 + 1 and open(msgs()[-1] + ".rcpt").read() == "kassapa-test@example.invalid", str(len(msgs()) - n1))
        kh = email.message_from_bytes(open(msgs()[-1], "rb").read(), policy=policy.default)
        kh = next((pt.get_content() for pt in kh.walk() if pt.get_content_type() == "text/html"), "")
        check("   Kassapa's letter: driver room 2 nights, TWN", "Driver room required (2 nights)" in kh and "1 TWN" in kh)
        r = await pg.request.get(BASE + "notification/unsent_mails", headers=HDR); a = (await r.json())["data"]
        check("   the alert now: 1 (Morven)", a["count"] == 1 and "MORVEN" in a["items"][0]["name"].upper(), json.dumps(a)[:200])
        # 9. recorded as sent another way → off the alert
        mj = int(sql(f"SELECT id FROM tf_hotel_job WHERE tour_id={tid} AND status='to_book'"))
        r = await pg.request.post(BASE + f"tour/hotel_bookings/card/{mj}/sent", data=json.dumps({"kind": "request", "note": "TEST — sent through the hotel's portal"}), headers=HDR); jj = await r.json()
        r = await pg.request.get(BASE + "notification/unsent_mails", headers=HDR); a = (await r.json())["data"]
        check("9. recorded as sent another way → the alert is clear", jj.get("status") == 200 and a["count"] == 0, json.dumps(a)[:200])
        await pg.goto(BASE + "tour/hotel_bookings", wait_until="domcontentloaded"); await pg.wait_for_timeout(2500)
        check("   the top bar alert is gone", "d-none" in (await pg.locator("#mailAlertDropdown").get_attribute("class")))
        # 10. a person without Hotel Bookings sees no alert data
        await b.close()
    return summary()
sys.exit(asyncio.run(main()))
