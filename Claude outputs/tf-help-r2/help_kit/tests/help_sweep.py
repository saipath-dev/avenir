"""tf-help-r1: is the User Manual button on every screen? As System Administrator, GET every menu page of the
test copy and look for the help script in the HTML; JSON and other answers must be left alone."""
import asyncio, os, sys, subprocess, re
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from tc import *
SKIP = re.compile(r"delete|remove|approve|cancel|backup|logout|update|reset|run|export|download|print|sync|send|create|import", re.I)
def sql(q): return subprocess.run(["mysql", "--socket", S + "/mysql.sock", "-uroot", "avenir_test", "-N", "-B", "-e", q], capture_output=True, text=True).stdout.strip()
async def main():
    urls = sorted(set(u for u in sql("SELECT url FROM menu_list WHERE del=0 AND url<>''").split("\n") if u and not SKIP.search(u)))
    async with async_playwright() as p:
        b, ctx = await browser(p)
        pg, body = await login(ctx, "admin")
        html_with, html_without, other, errors = [], [], [], []
        for u in urls:
            try:
                r = await pg.request.get(BASE + u, timeout=60000, max_redirects=0)
            except Exception as e:
                errors.append((u, str(e)[:60])); continue
            ct = r.headers.get("content-type", "")
            if r.status != 200:
                other.append((u, r.status)); continue
            t = await r.text()
            if "text/html" in ct and "</body>" in t.lower():
                (html_with if "assets/help/help-widget.js" in t else html_without).append(u)
            else:
                other.append((u, ct[:30]))
                if "assets/help/help-widget.js" in t: errors.append((u, "help added to a non-HTML answer"))
        # a JSON endpoint and an AJAX page request must not get the script
        r = await pg.request.get(BASE + "master_files/hotel_rate_v2/hotels?q=palms")
        jt = await r.text()
        check("JSON endpoint left alone", "help-widget" not in jt)
        r = await pg.request.get(BASE + "master_files/hotel_rate_v2", headers={"X-Requested-With": "XMLHttpRequest"})
        check("AJAX request for a page left alone", "help-widget" not in await r.text())
        print(f"   {len(urls)} menu pages asked: {len(html_with)} HTML pages with the button, {len(html_without)} HTML pages without, {len(other)} not HTML/200")
        check("every HTML page carries the User Manual button", not html_without, html_without[:20])
        check("no help script on anything that is not a page", not errors, errors[:10])
        print("   not HTML / not 200 (redirects, files):", other[:25])
        await b.close()
    return summary()
sys.exit(asyncio.run(main()))
