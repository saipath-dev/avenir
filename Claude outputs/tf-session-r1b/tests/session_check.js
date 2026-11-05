// tf-session-r1 — checks on the ISOLATED TEST INSTANCE (http://localhost/avenir_rv/), test login only. Run in a tab that
// shows an Avenir page of the instance:  window.__sessCfg = { base: '/avenir_rv/', user, pw };  await __sess.run('before'|'after')
window.__sess = (() => {
  const C = () => window.__sessCfg;
  const log = [];
  const ok = (n, c, d) => { log.push([c ? 'ok' : 'FAIL', n, c ? '' : JSON.stringify(d ?? '').slice(0, 300)]); return c; };
  const wait = ms => new Promise(r => setTimeout(r, ms));
  async function login() {
    const h = await fetch(C().base, { credentials: 'same-origin', redirect: 'manual' }).then(r => r.type === 'opaqueredirect' ? '' : r.text());   // no redirect is followed (the base URL's host can differ from this tab's)
    const m = h.match(/name="([^"]+)" value="([0-9a-f]{32})"/);
    if (!m && !/password-input/.test(h)) return 'already';   // the sign-in page sent us to the start page (still signed in)
    const fd = new FormData(); fd.append('username', C().user); fd.append('password', C().pw); if (m) fd.append(m[1], m[2]);
    const j = await fetch(C().base + 'login', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }).then(r => r.json()).catch(() => ({}));
    return j.status;
  }
  const api = () => fetch(C().base + 'accounts/daily_bank_rec/api/accounts', { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin', redirect: 'manual' })
    .then(r => r.type === 'opaqueredirect' ? 302 : r.status).catch(() => 0);
  async function run(phase) {
    const after = phase === 'after';
    await fetch(C().base + 'logout', { credentials: 'same-origin', redirect: 'manual' }).catch(() => 0); await login();
    const signedIn = await api();
    // 1. a background request without a session (as sent by a page left open after signing out)
    const r1 = await fetch(C().base + 'inventory/stock/header_stock_data', { credentials: 'omit', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, redirect: 'manual' });
    let j1 = {}; try { j1 = await r1.clone().json(); } catch (e) { }
    const c1 = r1.type === 'opaqueredirect' ? 302 : r1.status;
    if (after) ok('S1 a background request without a session gets a quiet 401 JSON "session ended" (no redirect to the sign-in page, which used to end sessions)', c1 === 401 && j1.session_ended === true, [c1, j1]);
    else ok('S1 PROBLEM: a background request without a session is redirected to the sign-in page (302)', c1 === 302, c1);
    // 2. a page request without a session is still sent to the sign-in page
    const r2 = await fetch(C().base + 'accounts/daily_bank_rec', { credentials: 'omit', redirect: 'manual' });
    ok('S2 a page request without a session is still sent to the sign-in page (302, unchanged)', r2.type === 'opaqueredirect', r2.type);
    // 3. opening the sign-in page while signed in (where a redirected background request lands)
    const s3a = signedIn;
    await fetch(C().base, { credentials: 'same-origin', redirect: 'manual' });   // a signed-in user is sent to the start page (on the base URL's host)
    const s3b = await api();
    if (after) ok('S3 opening the sign-in page while signed in keeps the session (api 200 before and after)', s3a === 200 && s3b === 200, [s3a, s3b]);
    else ok('S3 PROBLEM: opening the sign-in page while signed in ends the session (api 200 → ' + s3b + ')', s3a === 200 && s3b !== 200, [s3a, s3b]);
    await login();
    // 4. the notice in a page whose session has ended (after fix) / the sign-in page's HTML (before)
    await fetch(C().base + 'logout', { credentials: 'same-origin', redirect: 'manual' });
    let status4 = null, notice = false;
    await new Promise(res => jQuery.ajax({ url: C().base + 'inventory/stock/header_stock_data', dataType: 'json', complete: x => { status4 = x.status; res(); } }));
    await wait(600); notice = !!document.getElementById('tf-session-ended');
    if (after) ok('S4 the page shows the red notice "Your session has ended … Sign in again" when a background request is answered 401', status4 === 401 && notice, [status4, notice]);
    else ok('S4 (before) a background request after sign-out gets the sign-in page (200 HTML after the redirect); no notice', status4 !== 401 && !notice, [status4, notice]);
    const noticeText = notice ? document.getElementById('tf-session-ended').innerText : '';
    window.__sessShotReady = notice;
    return { phase, noticeText, log };
  }
  async function late() {
    // 5. HTML with plugins.js inserted after the page has loaded (what happened when a page received the sign-in page)
    const len0 = document.body ? document.body.innerText.length : -1;
    try { jQuery('<div id="late-html"></div>').appendTo(document.body).html('<p>inserted</p><script src="' + C().base + 'assets/js/plugins.js?late=' + Date.now() + '"></scr' + 'ipt>'); } catch (e) { }
    await wait(1500);
    const len1 = document.body ? document.body.innerText.length : -1;
    return [len0, len1];
  }
  async function signInAgain() { await login(); let st = null; await new Promise(res => jQuery.ajax({ url: C().base + 'accounts/daily_bank_rec/api/accounts', dataType: 'json', complete: x => { st = x.status; res(); } })); await wait(600); return [st, !!document.getElementById('tf-session-ended')]; }
  return { run, late, signInAgain, log, login, api };
})();
'session checks loaded';
