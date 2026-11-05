"""tf-letter-r1 on the cloud mirror of the live code (8081, test database, local test mail sink). Test data only."""
import asyncio, os, sys, json, glob, email, base64, subprocess
from email import policy
sys.path.insert(0, os.path.dirname(__file__))
from tc import *
SINK = S + "/smtpsink/msgs"; OUT = S + "/tct/letter_shots"; os.makedirs(OUT, exist_ok=True)
NEW_ADDR = "No. 980/4A, Level 4, Wickramasinghe Pl, Sri Jayawardenepura 10100, Sri Lanka"
def msgs(): return sorted(glob.glob(SINK + "/*.eml"))
async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch(); ctx = await b.new_context(viewport={"width": 900, "height": 1200})
        pg, _ = await login(ctx, "admin")
        hdr = {"Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest"}
        r = await pg.request.post(BASE + "tour/manage/company", data=json.dumps({"address": NEW_ADDR}), headers=hdr); j = await r.json()
        check("1. company address saved through Tour Desk Admin → company lines", j.get("status") == 200 and j["data"]["company"]["address"] == NEW_ADDR, json.dumps(j)[:200])
        # printed letters
        for jid, want in ((10, "As per agreed tactical rate"), (11, "As per agreed contract rates")):
            r = await pg.request.get(BASE + f"tour/hotel_bookings/card/{jid}/document/request?rate=1"); h = await r.text()
            check(f"2. printed request {jid}: rate named '{want}', no figures", r.status == 200 and want in h and "USD" not in h.split("<body")[1] and "Rate, as per contract" not in h, str(r.status))
            check(f"   printed request {jid}: the new address", NEW_ADDR in h)
            await pg.goto(BASE + f"tour/hotel_bookings/card/{jid}/document/request?rate=1"); await pg.screenshot(path=f"{OUT}/print-{jid}.png", full_page=True)
        # the e-mail, sent to the local sink
        n0 = len(msgs())
        r = await pg.request.post(BASE + "tour/hotel_bookings/card/11/send", data=json.dumps({"kind": "request", "to": "hotel-test@example.invalid", "cc": "", "subject": "", "message": "", "rate": 1}), headers=hdr, timeout=90000); j = await r.json()
        check("3. booking request sent", j.get("status") == 200, json.dumps(j)[:300])
        m = msgs()
        check("   the mail server got it", len(m) == n0 + 1)
        msg = email.message_from_bytes(open(m[-1], "rb").read(), policy=policy.default)
        html = None; imgs = {}
        for part in msg.walk():
            ct = part.get_content_type()
            if ct == "text/html": html = part.get_content()
            if ct.startswith("image/"): imgs[(part.get("Content-ID") or "").strip("<>")] = (ct, part.get_payload(decode=True))
        check("   the logo travels inside the e-mail (inline picture)", len(imgs) == 1 and all(c.startswith("avh") for c in imgs), str(list(imgs)))
        check("   the e-mail points at it (cid:), no localhost address", html and "cid:avh" in html and "localhost" not in html and "127.0.0.1" not in html)
        check("   rate named, never a figure", "As per agreed contract rates" in html and "USD" not in html and "single" not in html.lower())
        check("   new address on the e-mail", NEW_ADDR in html and "Park Road" not in html)
        check("   confidentiality line", "rates it refers to are confidential" in html)
        check("   r1b: the footer is white with bold dark letters (no dark band)", "#2b1d33" not in html and "background:#ffffff;border-top:2px solid #842484" in html and "font-weight:bold;color:#842484" in html)
        check("   r1b: the address only once (the letterhead), not repeated at the bottom", html.count("Wickramasinghe Pl") == 1, str(html.count("Wickramasinghe Pl")))
        # show the e-mail as a mail client would: cid → data
        for cid, (ct, data) in imgs.items():
            html = html.replace("cid:" + cid, "data:" + ct + ";base64," + base64.b64encode(data).decode())
        open(OUT + "/email.html", "w").write(html)
        pg2 = await ctx.new_page(); await pg2.set_viewport_size({"width": 760, "height": 1000})
        await pg2.set_content(html); await pg2.wait_for_timeout(500); await pg2.screenshot(path=OUT + "/email.png", full_page=True)
        # the copy kept on the stay keeps the picture's own address (it shows in the system)
        r = await pg.request.get(BASE + "tour/hotel_bookings/card/11", headers=hdr); c = (await r.json())["data"]
        kept = c["messages"][0]["body"] if c.get("messages") else ""
        check("4. the copy kept on the stay shows the logo in the system (its own address), and no figures", "assets/images/email/avenir-logo-email.png" in kept and "USD" not in kept)
        await b.close()
    return summary()
sys.exit(asyncio.run(main()))
