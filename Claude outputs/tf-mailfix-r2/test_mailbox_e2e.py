"""tf-mailfix-r2 on the cloud mirror of the live code (8081, test database, the company mail server = a local test sink
with one test login). The password typed is a TEST value of the sink, never a real one."""
import asyncio, os, sys, json, subprocess, glob
sys.path.insert(0, os.path.dirname(__file__))
from tc import *
SINK = S + "/smtpsink"; OUT = S + "/tct/mailbox_shots"; os.makedirs(OUT, exist_ok=True)
RIGHT, WRONG = "right-test-pass", "another-wrong-pass"
def db(): return subprocess.run(["mysql", "--socket=" + S + "/mysql.sock", "-uroot", "-N", "avenir_test", "-e", "SELECT CONCAT(email,'|',host,'|',email_password) FROM user_acc_tbl WHERE user='tcadmin'"], capture_output=True, text=True).stdout.strip()
def msgs(): return sorted(glob.glob(SINK + "/msgs/*.eml"))
async def main():
    seen = []   # every response text, to prove no password comes back
    async with async_playwright() as p:
        b = await p.chromium.launch(); ctx = await b.new_context(viewport={"width": 1280, "height": 860})
        pg, _ = await login(ctx, "admin")
        hdr = {"Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest"}
        quote = json.dumps({"to": "agent-test@example.invalid", "cc": "", "subject": "TEST — quotation mail check (test copy)", "message": "TEST — Dear Agent,\n\nplease find the quotation attached."})
        n0 = len(msgs())
        r = await pg.request.post(BASE + "tour/intake/intake/20/quote/9/email/send", data=quote, headers=hdr, timeout=90000); j = await r.json(); seen.append(json.dumps(j))
        check("1. old password: Send now says the login was refused (her error now)", j["status"] == 400 and "refused the login for quotes-test@avenirholidays.com" in j["message"], j["message"][:200])
        check("   nothing reached the mail server", len(msgs()) == n0)
        # My Profile → Mailbox
        await pg.goto(BASE + "my_profile", wait_until="domcontentloaded"); await pg.wait_for_timeout(1500)
        btn = pg.locator("button[data-bs-target='#mailboxModal']")
        check("2. My Profile has a Mailbox button", await btn.count() == 1)
        await btn.click(); await pg.wait_for_timeout(1500)
        check("   the box shows the address", await pg.input_value("#mb_email") == "quotes-test@avenirholidays.com")
        srv = await pg.locator("#mb_server").inner_text()
        check("   the box shows the company mail server", srv.startswith("127.0.0.1 · port 2465 · SSL (the company mail server)"), srv)
        check("   password saved: yes", (await pg.locator("#mb_has").inner_text()) == "yes")
        await pg.screenshot(path=OUT + "/01-mailbox-box.png")
        await pg.click("#mb_check"); await pg.wait_for_selector("#mb_result.alert", timeout=30000); t = await pg.locator("#mb_result").inner_text(); seen.append(t)
        check("3. Check saved login: refused, in plain words", "refused the login" in t, t)
        before = db()
        await pg.fill("#mb_password", WRONG); await pg.click("#mb_save"); await pg.wait_for_timeout(500)
        await pg.wait_for_function("document.querySelector('#mb_result').className.includes('alert')", timeout=30000); t = await pg.locator("#mb_result").inner_text(); seen.append(t)
        check("4. wrong password: refused, 'Nothing was saved.'", "refused the login" in t and "Nothing was saved." in t, t)
        check("   the account is unchanged", db() == before)
        check("   the password box is emptied", await pg.input_value("#mb_password") == "")
        await pg.screenshot(path=OUT + "/02-wrong-password.png")
        await pg.fill("#mb_password", RIGHT); await pg.click("#mb_save")
        await pg.wait_for_function("document.querySelector('#mb_result').textContent.startsWith('Saved') || document.querySelector('#mb_result').className.includes('alert-danger')", timeout=30000)
        t = await pg.locator("#mb_result").inner_text(); seen.append(t)
        check("5. right password: 'Saved. The mail server … accepted the login'", t.startswith("Saved. The mail server 127.0.0.1 accepted the login for quotes-test@avenirholidays.com."), t)
        check("   saved on the account", db().endswith("|" + RIGHT))
        check("   checking sent nothing", len(msgs()) == n0)
        await pg.screenshot(path=OUT + "/03-saved.png")
        await pg.click("#mb_check"); await pg.wait_for_timeout(500)
        await pg.wait_for_function("!document.querySelector('#mb_check').disabled", timeout=30000); t = await pg.locator("#mb_result").inner_text(); seen.append(t)
        check("6. Check saved login: accepted", "accepted the login" in t and "Sending will work." in t, t)
        html = await pg.content()
        check("   the password is nowhere in the page", RIGHT not in html)
        # the quotation now goes
        r = await pg.request.post(BASE + "tour/intake/intake/20/quote/9/email/send", data=quote, headers=hdr, timeout=90000); j = await r.json(); seen.append(json.dumps(j))
        check("7. Send now: sent", j["status"] == 200 and "sent to agent-test@example.invalid" in j["message"], j["message"][:200])
        m = msgs()
        body = open(m[-1], "rb").read() if len(m) > n0 else b""
        check("   the mail server got one message, to the agent, with the PDF", len(m) == n0 + 1 and open(m[-1] + ".rcpt").read() == "agent-test@example.invalid" and b"application/pdf" in body and b"Quotation-INQ" in body, str(len(m) - n0))
        check("   from the mailbox address", b"From: Avenir Holidays <quotes-test@avenirholidays.com>" in body)
        # limits and safety
        codes = []
        for i in range(7):
            r = await pg.request.post(BASE + "my_profile/mailbox/check", data="{}", headers=hdr, timeout=60000); jj = await r.json(); codes.append(jj["status"]); seen.append(json.dumps(jj))
        check("8. too many checks in 10 minutes are stopped", 429 in codes, str(codes))
        r = await pg.request.get(BASE + "my_profile/mailbox", headers=hdr); jj = await r.json(); seen.append(json.dumps(jj))
        check("   the mailbox answer never carries the password", "password" not in json.dumps(jj["data"]).replace("has_password", ""))
        check("9. no response or message ever showed a password", not any(RIGHT in s or WRONG in s or "wrong-old-pass" in s for s in seen))
        await ctx.close()
        ctx2 = await b.new_context()
        pg2 = await ctx2.new_page()
        r = await pg2.request.get(BASE + "my_profile/mailbox", headers=hdr, max_redirects=0)
        check("10. signed out: the mailbox is not reachable", r.status in (302, 303, 401, 403) or "login" in (r.headers.get("location") or ""), str(r.status))
        await b.close()
    return summary()
sys.exit(asyncio.run(main()))
