"""Security over the wire on the test copy: sign-in refusals and throttling, the session cookie's flags, no data without a
session, the action-only privileges (mileage override, crew replacement, driver advance, bank details) refused to the
desks on POST as well as GET, the bank endpoint closed to everyone but the System Administrator, and the sign-out."""
import asyncio, os, sys; sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from tc import *

async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch()
        # ---- sign-in refusals ----
        ctx = await b.new_context(); pg = await ctx.new_page()
        await pg.goto(BASE, wait_until="load")
        for u, pw, why in (("tcadmin", "wrong-password", "a wrong password"), ("nobody-here", "x", "an unknown user"), ("", "", "empty fields")):
            r = await pg.request.post(BASE + "login", form={"username": u, "password": pw})
            check(f"sign-in refuses {why} ({r.status})", r.status in (400, 401, 429) and '"status":200' not in (await r.text()), r.status)
        await ctx.close()
        # ---- a real sign-in: the session cookie ----
        ctx = await b.new_context(); pg, body = await login(ctx, "quote")
        cookies = await ctx.cookies()
        sess = next((c for c in cookies if c["name"] == "ci_session"), None)
        check("the session cookie is HttpOnly", sess is not None and sess.get("httpOnly") is True, sess and {k: sess[k] for k in ("name", "httpOnly", "sameSite")})
        check("the session cookie is SameSite Lax or Strict", sess is not None and sess.get("sameSite") in ("Lax", "Strict"), sess and sess.get("sameSite"))
        # ---- action-only privileges, checked inside the controllers: the real endpoints, as the groups that do not hold them ----
        c3 = await b.new_context(); p3, _ = await login(c3, "transport")          # holds Transport Operations, not crew replacement nor driver advance
        tc_id = sql("SELECT id FROM tf_transport_crew ORDER BY id LIMIT 1") or "1"
        r = await p3.request.post(BASE + f"tour/transport_ops/crew/{tc_id}/replace", data='{"person_id":0,"why":"battery"}', headers={"Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest"}, max_redirects=0)
        t = await r.text()
        check(f"transport desk: a crew replacement is refused by the controller ({r.status})", r.status in (403, 404, 400) or '"status":403' in t or 'replacement' in t.lower() and '"status":200' not in t, (r.status, t[:100]))
        adv = sql("SELECT id FROM tf_driver_advance ORDER BY id LIMIT 1") or "1"
        r = await p3.request.post(BASE + f"tour/transport_ops/card/{adv}/advance_approve", data="{}", headers={"Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest"}, max_redirects=0)
        check(f"transport desk: a driver-advance approval is refused ({r.status})", '"status":200' not in (await r.text()), r.status)
        await c3.close()
        # the bank details: Management holds Drivers & Guides but not the bank privilege; the System Administrator holds both
        c4 = await b.new_context(); p4, mb = await login(c4, "mgmt")
        check("management tester signs in", mb.get("status") == 200, mb)
        r = await p4.request.get(BASE + "master_files/crew_people/data", headers={"X-Requested-With": "XMLHttpRequest"}, max_redirects=0)
        t = await r.text()
        check(f"management: the Driver & Guide Master opens ({r.status}) but carries no bank column", r.status == 200 and '"can_bank":false' in t and "bank_account_no" not in t, (r.status, t[:80]))
        pid = sql("SELECT id FROM tf_crew_person ORDER BY id LIMIT 1") or "1"
        r = await p4.request.get(BASE + f"master_files/crew_people/person/{pid}", headers={"X-Requested-With": "XMLHttpRequest"}, max_redirects=0)
        check("management: a person's record carries no bank details", "bank_account_no" not in (await r.text()) or '"bank_account_no":null' in (await r.text()), r.status)
        await c4.close()
        c5 = await b.new_context(); p5, _ = await login(c5, "admin")
        r = await p5.request.get(BASE + "master_files/crew_people/data", headers={"X-Requested-With": "XMLHttpRequest"}, max_redirects=0)
        check("System Administrator: the bank column is offered", r.status == 200 and '"can_bank":true' in (await r.text()))
        await c5.close()
        # ---- a desk cannot read another desk's data endpoints ----
        for u in ("tour/hotel_bookings/data", "tour/board/data", "tour/manage/backups", "master_files/crew_people/data", "master_files/transport_rates/board"):
            r = await pg.request.get(BASE + u, headers={"X-Requested-With": "XMLHttpRequest"}, max_redirects=0)
            check(f"quotation desk: {u} gives no data ({r.status})", r.status != 200 or ('"data"' not in (await r.text())), r.status)
        # ---- sign-out ends the session ----
        await pg.goto(BASE + "logout", wait_until="load"); await pg.wait_for_timeout(800)
        r = await pg.request.get(BASE + "tour/intake/list", headers={"X-Requested-With": "XMLHttpRequest"}, max_redirects=0)
        check("after sign-out the session is gone", r.status != 200 or "inquiry_no" not in (await r.text()), r.status)
        await ctx.close()
        # ---- throttling: repeated wrong passwords lock the account for a while ----
        ctx = await b.new_context(); pg = await ctx.new_page(); await pg.goto(BASE, wait_until="load")
        statuses = []
        for i in range(8):
            r = await pg.request.post(BASE + "login", form={"username": "tcthrottle", "password": "wrong-%d" % i})
            statuses.append(r.status)
        check("eight wrong tries in a row end in a lock-out (429) or steady refusals, never a 200", 200 not in statuses and (429 in statuses or all(s == 401 for s in statuses)), statuses)
        await ctx.close(); await b.close()
    raise SystemExit(summary())
asyncio.run(main())
