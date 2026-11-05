"""tf-letter-r1 regression: a quotation still goes out (with its PDF) through the changed SendController. Test copy only:
test sink, test login, test agent address."""
import asyncio, os, sys, json, glob, email
sys.path.insert(0, os.path.dirname(__file__))
from tc import *
SINK = S + "/smtpsink"
def msgs(): return sorted(glob.glob(SINK + "/msgs/*.eml"))
async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch(); ctx = await b.new_context()
        pg, _ = await login(ctx, "admin")
        hdr = {"Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest"}
        quote = json.dumps({"to": "agent-test@example.invalid", "cc": "", "subject": "TEST — quotation regression (test copy) · Café", "message": "TEST — Dear Agent,\n\nplease find the quotation attached — 日本語 check."})
        n0 = len(msgs())
        r = await pg.request.post(BASE + "tour/intake/intake/20/quote/9/email/send", data=quote, headers=hdr, timeout=90000); j = await r.json()
        check("quotation Send now: sent", j["status"] == 200 and "sent to agent-test@example.invalid" in j["message"], j["message"][:200])
        m = msgs(); raw = open(m[-1], "rb").read() if len(m) > n0 else b""
        msg = email.message_from_bytes(raw)
        parts = [(pt.get_content_type(), pt.get_filename()) for pt in msg.walk()]
        check("one message with the PDF", len(m) == n0 + 1 and any(t == "application/pdf" for t, _ in parts), str(parts))
        html = next((pt for pt in msg.walk() if pt.get_content_type() == "text/html"), None)
        txt = html.get_payload(decode=True).decode(html.get_content_charset() or "ascii") if html else ""
        check("declared UTF-8 and the text reads right", html is not None and (html.get_content_charset() or "").lower() == "utf-8" and "日本語" in txt and "—" in txt, (html.get_content_charset() if html else None))
        subj = str(email.header.make_header(email.header.decode_header(msg["Subject"])))
        check("subject reads right", "Café" in subj and "—" in subj, subj)
        await b.close()
    sys.exit(summary())
asyncio.run(main())
