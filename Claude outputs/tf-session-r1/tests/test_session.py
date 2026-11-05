"""tf-session-r1 — tests of the sign-out problem and its fix on the cloud test copy (127.0.0.1:8081, avenir_test).
Run once on the original code (expect the problem to be reproduced) and once on the fixed code:
    python3 test_session.py before|after
HTTP checks with requests, the two-tab scenario and the late plugins.js in a real browser (Playwright)."""
import os, sys, re, json, time, asyncio, subprocess, requests
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, S + '/paydesk')
from api import ACC
MODE = sys.argv[1] if len(sys.argv) > 1 else 'after'
BASE = 'http://127.0.0.1:8081/avenir/'
AJ = {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json, text/javascript, */*; q=0.01'}
res = []
def check(name, ok, detail=''):
    res.append([('ok' if ok else 'FAIL'), name, '' if ok else str(detail)[:300]])
    print(('  ok   ' if ok else '  FAIL ') + name + ('' if ok else '   ' + str(detail)[:300]))
def sql(q):
    r = subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '-uroot', '-N', '-B', 'avenir_test', '-e', q], capture_output=True, text=True)
    if r.returncode: raise SystemExit(r.stderr)
    return r.stdout.strip()

def login(who):
    s = requests.Session(); u, p = ACC[who]
    r = s.get(BASE); m = re.search(r'name="([^"]+)" value="([0-9a-f]{32})"', r.text)
    data = {'username': u, 'password': p}
    if m: data[m.group(1)] = m.group(2)
    for _ in range(8):
        j = s.post(BASE + 'login', data=data, headers={'X-Requested-With': 'XMLHttpRequest'}).json()
        if j.get('status') == 429: time.sleep(float(j.get('retry_after') or 3) + 0.5); continue
        break
    assert j.get('status') == 200, j
    return s, j
def page(s, path):   # a page request, redirects not followed
    return s.get(BASE + path, allow_redirects=False)
def signed_in(s):
    r = page(s, 'dashboard2/tiles')
    return r.status_code == 200
def loc(r):
    return r.headers.get('Location', '')

# ---------------------------------------------------------------- HTTP
s, j = login('accountant')
check('H0 sign-in works (JSON 200, start page dashboard2/tiles, needs_usd_rate field kept)', j.get('redirect') == 'dashboard2/tiles' and 'needs_usd_rate' in j, j)
check('H0 signed in: a page opens', signed_in(s))
r = s.get(BASE + '?redirect=' + 'inventory%2Fstock%2Fheader_stock_data', allow_redirects=False)
still = signed_in(s)
if MODE == 'before':
    check('H1 PROBLEM REPRODUCED: opening the sign-in page (where a redirected background check lands) signs out the valid session', r.status_code == 200 and not still, [r.status_code, still])
else:
    check('H1 opening the sign-in page with a valid session goes to the start page (302) and the session stays signed in', r.status_code in (302, 303) and loc(r).endswith('dashboard2/tiles') and still, [r.status_code, loc(r), still])
anon = requests.Session(); anon.get(BASE)
r = anon.get(BASE + 'inventory/stock/header_stock_data', headers=AJ, allow_redirects=False)
if MODE == 'before':
    check('H2 PROBLEM: a background check without a session is redirected to the sign-in page (302)', r.status_code in (302, 303) and '?redirect=' in loc(r), [r.status_code, loc(r)])
else:
    jj = r.json() if r.headers.get('Content-Type', '').startswith('application/json') else {}
    check('H2 a background check without a session gets 401 JSON "session ended" (no redirect to the sign-in page)', r.status_code == 401 and jj.get('session_ended') is True and jj.get('login') == BASE, [r.status_code, r.text[:200]])
r = anon.get(BASE + 'dashboard2/tiles', allow_redirects=False)
check('H3 a page request without a session is still redirected to the sign-in page with ?redirect= (unchanged)', r.status_code in (302, 303) and loc(r).endswith('?redirect=dashboard2%2Ftiles'), [r.status_code, loc(r)])
r = anon.get(BASE)
check('H5 the sign-in page opens without a session (form shown)', r.status_code == 200 and 'login_form' in r.text, r.status_code)
# H4 revoked token: no loop
s4, _ = login('accountant')
uid = sql("SELECT id FROM user_acc_tbl WHERE user = 'tcaccount'")
sql(f"UPDATE user_sessions SET is_active = 0, revoke_reason = 'test revoke', revoked_at = NOW() WHERE uid = {uid} AND is_active = 1 ORDER BY id DESC LIMIT 1")
r1 = page(s4, 'dashboard2/tiles'); r2 = s4.get(BASE, allow_redirects=False)
r3 = s4.get(BASE + 'inventory/stock/header_stock_data', headers=AJ, allow_redirects=False)
check('H4 a revoked session: the page goes to the sign-in page, which shows the form (no redirect loop)', r1.status_code in (302, 303) and r2.status_code == 200 and 'login_form' in r2.text, [r1.status_code, loc(r1), r2.status_code])
check('H4 a revoked session: background check ' + ('redirected (before)' if MODE == 'before' else '401 JSON'), (r3.status_code in (302, 303)) if MODE == 'before' else (r3.status_code == 401), r3.status_code)
# H6 logout then sign-in page
s6, _ = login('accountant')
lo = s6.get(BASE + 'logout', headers=AJ).json()
check('H6 sign-out works; afterwards pages go to the sign-in page', lo.get('status') == 200 and not signed_in(s6), lo)
# H7 admin start page
sa, ja = login('admin')
r = sa.get(BASE, allow_redirects=False)
if MODE == 'after':
    check('H7 System Administrator already signed in, opening the sign-in page → the administration dashboard (302)', r.status_code in (302, 303) and loc(r).endswith('/avenir/dashboard') and signed_in(sa), [r.status_code, loc(r)])
# H8 permissions unchanged: a department without the privilege still gets 403 JSON on a protected AJAX route
sh, _ = login('hotelres')
r = sh.get(BASE + 'accounts/daily_bank_rec/api/accounts', headers=AJ, allow_redirects=False)
check('H8 a signed-in user without the privilege still gets 403 (permission filter unchanged)', r.status_code == 403, r.status_code)


# H9 a background check sent while signed out (no cookie, or the old cookie) whose answer arrives after a new sign-in:
# its Set-Cookie headers are applied to the new sign-in's cookie jar, as a browser does with a late answer
def late_answer(jar_session, send_cookie):
    r = requests.get(BASE + 'inventory/stock/header_stock_data', headers=AJ, allow_redirects=False, cookies=send_cookie)
    raw = r.raw.headers.getlist('Set-Cookie') if hasattr(r.raw.headers, 'getlist') else []
    for h in raw:
        name, _, rest = h.partition('=')
        val = rest.split(';', 1)[0]
        if name.strip() != 'ci_session': continue
        for c in [c for c in jar_session.cookies if c.name == 'ci_session']:
            jar_session.cookies.clear(c.domain, c.path, c.name)
        if not (val in ('', 'deleted') or 'Max-Age=0' in h or '1970' in h):
            jar_session.cookies.set('ci_session', val, domain='127.0.0.1', path='/')
    return r.status_code, raw
for label, cookie in (('no cookie (sent after signing out)', {}), ('the old, ended session cookie', {'ci_session': '0123456789abcdef0123456789abcdef'})):
    s9, _ = login('accountant')
    code, raw = late_answer(s9, cookie)
    ok9 = signed_in(s9)
    if MODE == 'before':
        check('H9 PROBLEM REPRODUCED: a background check with %s answered after a new sign-in deletes that sign-in (%d, %d cookie header(s))' % (label, code, len(raw)), not ok9, [code, raw])
    else:
        check('H9 a background check with %s answered after a new sign-in: 401 and no cookie sent back; the new sign-in stays' % label, code == 401 and not raw and ok9, [code, raw, ok9])

# ---------------------------------------------------------------- browser
async def browser():
    from playwright.async_api import async_playwright
    async with async_playwright() as p:
        b = await p.chromium.launch()
        ctx = await b.new_context(viewport={'width': 1366, 'height': 900})
        errs = []
        async def ev(pg, code, default=None, timeout=12):
            try: return await asyncio.wait_for(pg.evaluate(code), timeout)
            except Exception as e: print('    (evaluate gave up: %s)' % str(e)[:80], flush=True); return default
        async def ui_login(pg, who):
            u, pw = ACC[who]
            await pg.goto(BASE, wait_until='load')
            await pg.fill('#username', u); await pg.fill('#password-input', pw)
            await pg.click('#login_btn')
            await pg.wait_for_url(re.compile(r'.*/(dashboard|dashboard2/tiles)$'), timeout=30000, wait_until='commit')
            try: await pg.wait_for_load_state('load', timeout=15000)
            except Exception: pass
            await pg.wait_for_timeout(2500)
            print('    (signed in on screen as %s: %s)' % (who, pg.url), flush=True)
        A = await ctx.new_page(); A.on('pageerror', lambda e: errs.append(('A', str(e)[:200])))
        await ui_login(A, 'admin')
        a_len0 = await ev(A, 'document.body ? document.body.innerText.length : -1')
        B = await ctx.new_page(); B.on('pageerror', lambda e: errs.append(('B', str(e)[:200])))
        await B.goto(BASE + 'dashboard2/tiles', wait_until='commit'); await B.wait_for_timeout(3000)
        # the session ends (signed out from tab B); tab A stays open with its background checks running
        await ev(B, "fetch('/avenir/logout', {headers: {'X-Requested-With': 'XMLHttpRequest'}}).then(r => r.json())")
        print('    (signed out from tab B; waiting for tab A\'s checks)', flush=True)
        await A.wait_for_timeout(32000)
        a_state = await ev(A, "({len: document.body ? document.body.innerText.length : -1, ready: document.readyState, notice: !!document.getElementById('tf-session-ended'), url: location.pathname})", {'len': -1, 'ready': '?', 'notice': False, 'url': '?'})
        if MODE == 'after':
            check('P1 after sign-out, the old tab keeps its page (not blanked) and shows "Your session has ended … Sign in again"', a_state['len'] > 200 and a_state['notice'], a_state)
        # tab A's background checks are held in the network while tab B signs in, and answered afterwards (a slow server;
        # on 12 Oct the blank page had piled up timers that fired ~10 checks at a time)
        held = []
        async def hold(route):
            try:
                resp = await route.fetch()   # the server answers now (signed-out cookies), the page gets it later
            except Exception as e:
                resp = None
            held.append((route, resp))
        await A.route(re.compile(r'.*/inventory/stock/header_stock_data.*'), hold)
        await ev(A, "for (let i = 0; i < 5; i++) jQuery.ajax({url: '/avenir/inventory/stock/header_stock_data', dataType: 'json'})")
        for _ in range(40):
            if held: break
            await A.wait_for_timeout(1000)
        await A.wait_for_timeout(1500)
        await ui_login(B, 'admin')
        for r_, resp in held:
            try:
                if resp is not None: await r_.fulfill(response=resp)
                else: await r_.continue_()
            except Exception as e:
                print('    (fulfil: %s)' % str(e)[:100], flush=True)
        await A.unroute(re.compile(r'.*/inventory/stock/header_stock_data.*'))
        await B.wait_for_timeout(4000)
        try: await B.reload(wait_until='commit', timeout=15000)
        except Exception as e: print('    reload:', str(e)[:120], flush=True)
        await B.wait_for_timeout(6000)
        b_state = await ev(B, "({len: document.body ? document.body.innerText.length : -1, ready: document.readyState, url: location.pathname})", {'len': -1, 'ready': '?', 'url': '?'})
        b_signed = await ev(B, "fetch('/avenir/dashboard2/tiles', {redirect: 'manual'}).then(r => r.type !== 'opaqueredirect' && r.status === 200)", False)
        a_after = {'notice': True, 'len': -1}
        for _ in range(9):   # tab A's own background checks run every 20-30 s; the next successful one removes the notice
            a_after = await ev(A, "({notice: !!document.getElementById('tf-session-ended'), len: document.body ? document.body.innerText.length : -1})", {'notice': True, 'len': -1})
            if not a_after['notice'] or MODE == 'before': break
            await A.wait_for_timeout(5000)
        if MODE == 'before':
            check('P1 PROBLEM REPRODUCED: background checks from an old tab, answered just after a new sign-in in another tab, sign it out (%d held)' % len(held), (not b_signed) or b_state['len'] < 200, [b_state, b_signed])
        else:
            check('P1 background checks from an old tab answered just after a new sign-in in another tab: the sign-in stays and its page stays visible (%d held)' % len(held), b_signed and b_state['len'] > 200 and b_state['ready'] == 'complete', [b_state, b_signed])
            check('P1 the old tab works again once signed in elsewhere (notice removed after a successful check)', not a_after['notice'] and a_after['len'] > 200, a_after)
        # P2: plugins.js run after the page has loaded
        C = await ctx.new_page(); await C.goto(BASE + 'dashboard2/tiles', wait_until='commit'); await C.wait_for_timeout(4000)
        len0 = await ev(C, 'document.body.innerText.length', -1)
        await ev(C, "new Promise(r => { try { jQuery('<div id=late-html></div>').appendTo(document.body).html('<p>inserted</p><script src=\\'/avenir/assets/js/plugins.js?late=1\\'></scr' + 'ipt>'); } catch (e) {} setTimeout(r, 800); })")
        await C.wait_for_timeout(1500)
        len1 = await ev(C, 'document.body ? document.body.innerText.length : -1', -1)
        if MODE == 'before':
            check('P2 PROBLEM REPRODUCED: HTML with plugins.js inserted after load (as when a page receives the sign-in page) blanks the page', len1 < 50, [len0, len1])
        else:
            check('P2 HTML with plugins.js inserted after load leaves the page as it was', len1 >= len0 - 5 and len1 > 200, [len0, len1])
        if MODE == 'after':
            noisy = [e for e in errs if 'session' in e[1].lower() or 'tfSession' in e[1]]
            check('P3 no script error from the new code on the pages used', not noisy, noisy)
        await b.close()
asyncio.run(browser())
print('%d/%d passed (%s)' % (sum(1 for r in res if r[0] == 'ok'), len(res), MODE))
json.dump(res, open(HERE + '/results_session_%s.json' % MODE, 'w'), indent=1)
