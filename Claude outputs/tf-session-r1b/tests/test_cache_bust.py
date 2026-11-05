"""tf-session-r1b: the version mark on plugins.js — the page asks for plugins.js?v=<mtime>; after plugins.js changes, the next
page load asks for the new version (so the browser does not use its cached copy); the page still works (no script error)."""
import asyncio, os, re, sys, time, json
sys.path.insert(0, '/tmp/claude-0/-home-claude/1f177919-91eb-5b5f-a3ff-b7531d256531/scratchpad/paydesk')
from api import ACC
S = '/tmp/claude-0/-home-claude/1f177919-91eb-5b5f-a3ff-b7531d256531/scratchpad'
BASE = 'http://127.0.0.1:8081/avenir/'; PJ = S + '/tm/public/assets/js/plugins.js'
res = []
def check(n, ok, d=''):
    res.append((n, bool(ok), str(d)[:300])); print(('  ok   ' if ok else '  FAIL ') + n + ('' if ok else '  ' + str(d)[:300]), flush=True)
async def main():
    from playwright.async_api import async_playwright
    async with async_playwright() as p:
        b = await p.chromium.launch(); ctx = await b.new_context(); pg = await ctx.new_page()
        errs = []; pg.on('pageerror', lambda e: errs.append(pg.url.split('/avenir/')[-1] + ': ' + str(e) + ' @ ' + (getattr(e, 'stack', '') or '')[:160].replace(chr(10), ' ')))
        await pg.goto(BASE, wait_until='load')
        u, pw = ACC['accountant']; await pg.fill('#username', u); await pg.fill('#password-input', pw); await pg.click('#login_btn')
        await pg.wait_for_timeout(4000)
        reqs = []; pg.on('request', lambda r: reqs.append(r.url) if 'plugins.js' in r.url else None)
        await pg.goto(BASE + 'accounts/daily_bank_rec', wait_until='load'); await pg.wait_for_timeout(4000)
        src1 = await pg.evaluate("[...document.querySelectorAll('script[src*=\"plugins.js\"]')].map(s => s.getAttribute('src'))")
        m1 = int(os.path.getmtime(PJ))
        check('the page asks for plugins.js with the version mark = the file\'s modification time', len(src1) == 1 and src1[0].endswith('plugins.js?v=%d' % m1), (src1, m1))
        check('the session watcher of the sign-out fix is loaded', await pg.evaluate('window.__tfSessionWatch === true'), '')
        n_before = len(reqs)
        await pg.goto(BASE + 'accounts/daily_bank_rec', wait_until='load'); await pg.wait_for_timeout(4000)
        cached = await pg.evaluate("performance.getEntriesByType('resource').filter(e => e.name.includes('plugins.js')).map(e => [e.name, e.transferSize])")
        os.utime(PJ, (time.time(), m1 + 61))   # plugins.js "updated" (content unchanged; modification time later)
        try:
            await pg.goto(BASE + 'accounts/daily_bank_rec', wait_until='load'); await pg.wait_for_timeout(4000)
            src3 = await pg.evaluate("[...document.querySelectorAll('script[src*=\"plugins.js\"]')].map(s => s.getAttribute('src'))")
            fetched = await pg.evaluate("performance.getEntriesByType('resource').filter(e => e.name.includes('plugins.js')).map(e => [e.name, e.transferSize])")
            check('after plugins.js changes, the next page load asks for the new version (v=%d) and fetches it from the server, not the cache' % (m1 + 61),
                  src3 and src3[0].endswith('v=%d' % (m1 + 61)) and any(f[0].endswith('v=%d' % (m1 + 61)) and f[1] > 0 for f in fetched), (src3, fetched, cached))
        finally:
            os.utime(PJ, (time.time(), m1))
        errs2 = [e for e in errs if not e.startswith(': ') and 'lord-icon' not in e]   # existing before this change: the sign-in page's app.js error; lord-icon's icon download (external site, blocked in the cloud)
        check('no script error on the pages after signing in (the sign-in page\'s existing app.js error aside)', not errs2, errs2[:3])
        await b.close()
    print('%d/%d passed' % (sum(1 for r in res if r[1]), len(res)))
    json.dump(res, open(S + '/sessfix/results_cache_bust.json', 'w'), indent=1)
asyncio.run(main())
