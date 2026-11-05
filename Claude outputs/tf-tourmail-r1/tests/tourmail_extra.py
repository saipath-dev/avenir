"""tf-tourmail-r1 extra checks on the test copy: a restaurant voucher not sent is on the alert and on Sent Mails;
an account without Hotel Bookings gets nothing from the alert; pages open."""
import asyncio, os, sys, json
sys.path.insert(0, os.path.dirname(__file__))
from tc import *
HDR = {"X-Requested-With": "XMLHttpRequest"}
async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch(); ctx = await b.new_context()
        pg, _ = await login(ctx, "admin")
        r = await pg.request.get(BASE + "notification/unsent_mails", headers=HDR); a = (await r.json())["data"]
        check("1. a restaurant voucher not sent (no address) is on the alert", a["count"] == 1 and a["items"][0]["type"] == "restaurant" and "No e-mail address for Test Restaurant" in a["items"][0]["why"], json.dumps(a)[:300])
        r = await pg.request.get(BASE + "tour/hotel_bookings/mails/data?state=not_sent", headers=HDR); d = (await r.json())["data"]
        check("   and on Sent Mails → Not sent", any(m["type"] == "restaurant" and m["open"] for m in d["mails"]), json.dumps(d)[:300])
        for path in ["tour/hotel_bookings", "tour/intake", "tour/hotel_bookings/mails", "my_profile", "dashboard"]:
            r = await pg.request.get(BASE + path); t = await r.text()
            check(f"2. {path} opens", r.status == 200 and "mailAlertDropdown" in t and "Exception" not in t[:3000], str(r.status))
        for who in ("quote", "transport", "hotel"):
            ctx2 = await b.new_context(); pg2, _ = await login(ctx2, who)
            r = await pg2.request.get(BASE + "notification/unsent_mails", headers=HDR); jj = await r.json(); print("   ", who, r.status, json.dumps(jj)[:200]); a = jj.get("data") or {"allowed": False, "count": 0}
            r2 = await pg2.request.get(BASE + "tour/hotel_bookings/mails/data", headers=HDR)
            print(f"   {who}: alert allowed={a['allowed']} count={a['count']} · Sent Mails data status={r2.status}")
            check(f"3. {who}: the alert answers only a Hotel Bookings holder, and Sent Mails follows the same privilege", (a["allowed"] and r2.status == 200) or (not a["allowed"] and a["count"] == 0 and r2.status in (302, 403)), f"{a} {r2.status}")
            await ctx2.close()
        await b.close()
    return summary()
sys.exit(asyncio.run(main()))
