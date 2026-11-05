"""tf-mailcc-r1 on the cloud mirror (local test mail sink only; test addresses): "Copy every e-mail to" in Tour Desk Admin."""
import asyncio, os, sys, json, glob, email, subprocess
from email import policy
sys.path.insert(0, os.path.dirname(__file__))
from tc import *
SINK = S + "/smtpsink/msgs"; OUT = S + "/tct/mailcc_shots"; os.makedirs(OUT, exist_ok=True)
def msgs(): return sorted(glob.glob(SINK + "/*.eml"))
HDR = {"Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest"}
CC = "iqbaal-test@example.invalid, dsm-test@example.invalid"
async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch(); ctx = await b.new_context(viewport={"width": 1300, "height": 900})
        pg, _ = await login(ctx, "admin")
        r = await pg.request.post(BASE + "tour/manage/company", data=json.dumps({"always_cc": "not-an-address, dsm-test@example.invalid"}), headers=HDR); j = await r.json()
        check("1. a wrong address is refused, nothing saved", j.get("status") != 200 and "is not an e-mail address" in j.get("message", ""), json.dumps(j)[:200])
        r = await pg.request.post(BASE + "tour/manage/company", data=json.dumps({"always_cc": "iqbaal-test@example.invalid; dsm-test@example.invalid"}), headers=HDR); j = await r.json()
        check("   saved, written one way", j.get("status") == 200 and j["data"]["company"]["always_cc"] == CC, json.dumps(j)[:300])
        check("   the other company lines untouched", "Wickramasinghe" in j["data"]["company"]["address"] and j["data"]["company"]["name"])
        await pg.goto(BASE + "tour/manage", wait_until="domcontentloaded"); await pg.wait_for_timeout(2500)
        tab = pg.locator("[data-tab='settings'], button:has-text('Settings')").first
        await tab.click(); await pg.wait_for_timeout(2500)
        v = await pg.input_value("#co_always_cc")
        check("2. Tour Desk Admin → Settings shows the box with the addresses", v == CC, v)
        await pg.locator("#co_always_cc").scroll_into_view_if_needed(); await pg.screenshot(path=OUT + "/01-setting.png")
        # a quotation (to an agent) — copied
        n0 = len(msgs())
        q = json.dumps({"to": "agent-test@example.invalid", "cc": "", "subject": "TEST — quotation (copy check)", "message": "TEST"})
        r = await pg.request.post(BASE + "tour/intake/intake/20/quote/9/email/send", data=q, headers=HDR, timeout=90000); j = await r.json()
        m = msgs()
        rc = open(m[-1] + ".rcpt").read().split() if len(m) > n0 else []
        hdr = email.message_from_bytes(open(m[-1], "rb").read(), policy=policy.default) if len(m) > n0 else {}
        check("3. a quotation: sent, and copied to both", j.get("status") == 200 and sorted(rc) == sorted(["agent-test@example.invalid", "iqbaal-test@example.invalid", "dsm-test@example.invalid"]) and "iqbaal-test@example.invalid" in str(hdr.get("Cc")), f"{rc} cc={hdr.get('Cc') if hdr else None}")
        # a hotel letter with its own CC that is also one of the two — not twice
        n0 = len(msgs())
        r = await pg.request.post(BASE + "tour/hotel_bookings/card/11/send", data=json.dumps({"kind": "request", "to": "hotel-test@example.invalid", "cc": "dsm-test@example.invalid", "subject": "", "message": "", "rate": 1}), headers=HDR, timeout=90000); j = await r.json()
        m = msgs()
        rc = open(m[-1] + ".rcpt").read().split() if len(m) > n0 else []
        hdr = email.message_from_bytes(open(m[-1], "rb").read(), policy=policy.default)
        check("4. a hotel letter: copied to both, the one typed too is not copied twice", j.get("status") == 200 and sorted(rc) == sorted(["hotel-test@example.invalid", "dsm-test@example.invalid", "iqbaal-test@example.invalid"]) and str(hdr.get("Cc")).count("dsm-test") == 1, f"{rc} {hdr.get('Cc')}")
        # emptied: nobody copied
        r = await pg.request.post(BASE + "tour/manage/company", data=json.dumps({"always_cc": ""}), headers=HDR); j = await r.json()
        n0 = len(msgs())
        r = await pg.request.post(BASE + "tour/intake/intake/20/quote/9/email/send", data=q, headers=HDR, timeout=90000)
        m = msgs(); rc = open(m[-1] + ".rcpt").read().split() if len(m) > n0 else []
        check("5. emptied: the next mail is copied to nobody", rc == ["agent-test@example.invalid"], str(rc))
        await b.close()
    return summary()
sys.exit(asyncio.run(main()))
