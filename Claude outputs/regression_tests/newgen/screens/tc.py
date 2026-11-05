"""The test copy (the real Avenir app on 127.0.0.1:8080, workspace database avenir_test). Shared helpers for the screen checks.
Accounts: see tc/TEST_ACCOUNTS.txt (test copy only)."""
import asyncio, os, sys
from playwright.async_api import async_playwright
ACC = {"admin": ("tcadmin", "tc-admin-2026"), "quote": ("tcquote", "tc-desk-2026"), "transport": ("tctransport", "tc-desk-2026"), "hotel": ("tchotel", "tc-desk-2026"), "mgmt": ("tcmgmt", "tc-desk-2026")}
S = os.environ.get("NEWGEN_WORK", os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "_work"))   # where screenshots and logs go
os.makedirs(S, exist_ok=True)
APP = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", ".."))       # the application tree under test
DB_SOCKET = os.environ.get("NEWGEN_DB_SOCKET", "")                                                      # the test database: a socket …
DB_HOST, DB_PORT, DB_USER, DB_NAME = os.environ.get("NEWGEN_DB_HOST", "127.0.0.1"), os.environ.get("NEWGEN_DB_PORT", "3307"), os.environ.get("NEWGEN_DB_USER", "root"), os.environ.get("NEWGEN_DB_NAME", "avenir_test")
if not DB_NAME.endswith("_test"):
    raise SystemExit("REFUSED: the database name must end in _test (" + DB_NAME + "). Nothing was run.")
import subprocess
def sql(q):
    """One query against the test database (read or write — it is a test copy), result as text."""
    cmd = ["mysql"] + (["--socket", DB_SOCKET] if DB_SOCKET else ["-h", DB_HOST, "-P", DB_PORT]) + ["-u" + DB_USER, DB_NAME, "-N", "-B", "-e", q]
    return subprocess.run(cmd, capture_output=True, text=True).stdout.strip()
HOST = os.environ.get("NEWGEN_HOST", "http://127.0.0.1:8080")
BASE = HOST + "/avenir/"

async def login(ctx, who="admin"):
    u, p = ACC[who]
    pg = await ctx.new_page()
    await pg.goto(BASE, wait_until="load")
    await pg.fill("#username", u); await pg.fill("#password-input", p)
    async with pg.expect_response(lambda r: "/login" in r.url and r.request.method == "POST", timeout=20000) as resp:
        await pg.click("#login_btn")
    r = await resp.value
    body = await r.json()
    try:
        await pg.wait_for_url("**/dashboard**", timeout=15000)
    except Exception:
        pass
    await pg.wait_for_timeout(800)
    return pg, body

async def browser(p, width=1440, height=900):
    b = await p.chromium.launch()
    ctx = await b.new_context(viewport={"width": width, "height": height})
    return b, ctx

results = []
def check(name, ok, detail=""):
    results.append((name, bool(ok), detail)); print(("  ok   " if ok else "  FAIL ") + name + ("" if ok else "  " + str(detail)[:300]))
def summary():
    failed = [r for r in results if not r[1]]
    print(f"{len(results) - len(failed)} ok, {len(failed)} failed")
    return 1 if failed else 0
