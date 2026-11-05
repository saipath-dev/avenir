// tf-bankrec-r3 on DEVELOPMENT — checks that need no reconciliation data (development has none and gets none: D11).
// Run in a tab on http://127.0.0.1/avenir/ (its own sign-in, separate from anyone's localhost session), TEST logins only.
//   window.__devCfg = { base: '/avenir/', pw, prep: 'TestAccounts', rev: 'TestFinApprover', view: 'TestBankAuth', others: [...] }
//   await __dev.run()  → { log, facts }.  Every POST below is refused before anything is written (permission, security
//   token, or "nothing to act on"); the database is compared before and after by the caller.
window.__dev = (() => {
  const C = () => window.__devCfg; const log = []; const facts = {};
  const ok = (n, c, d) => { log.push([c ? 'ok' : 'FAIL', n, c ? '' : JSON.stringify(d ?? '').slice(0, 400)]); return c; };
  const API = () => C().base + 'accounts/daily_bank_rec/';
  let who = null, tok = null;
  async function login(user) {
    if (who === user) return true;
    await fetch(C().base + 'logout', { credentials: 'same-origin' }).catch(() => 0);
    const h = await fetch(C().base, { credentials: 'same-origin' }).then(r => r.text());
    const m = h.match(/name="([^"]+)" value="([0-9a-f]{32})"/);
    const fd = new FormData(); fd.append('username', user); fd.append('password', C().pw); if (m) fd.append(m[1], m[2]);
    for (let i = 0; i < 8; i++) {
      const j = await fetch(C().base + 'login', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }).then(r => r.json()).catch(() => ({}));
      if (j.status === 429) { await new Promise(r => setTimeout(r, ((+j.retry_after || 5) + 1) * 1000)); continue; }
      tok = null; who = j.status === 200 ? user : null; return j.status === 200;
    }
    return false;
  }
  async function token() {
    if (tok) return tok;
    const h = await fetch(API().replace(/\/$/, ''), { credentials: 'same-origin', redirect: 'manual' }).then(r => r.type === 'opaqueredirect' ? '' : r.text()).catch(() => '');
    const m = h.match(/const csrf = \{ name: "([^"]+)", hash: "([0-9a-f]+)"/);
    tok = m ? { name: m[1], hash: m[2] } : null; return tok;
  }
  async function get(path, params) {
    let r; try { r = await fetch(API() + path + (params ? '?' + new URLSearchParams(params) : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin', redirect: 'manual' }); } catch (e) { return [0, {}]; }
    let j = {}; try { j = await r.json(); } catch (e) { }
    return [r.status || (r.type === 'opaqueredirect' ? 302 : 0), j];
  }
  async function post(path, data, noToken) {
    const fd = new FormData(); for (const [k, v] of Object.entries(data || {})) fd.append(k, v);
    const t = noToken ? null : await token(); if (t) fd.set(t.name, t.hash);
    let r; try { r = await fetch(API() + path, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin', redirect: 'manual' }); } catch (e) { return [0, {}]; }
    let j = {}; try { j = await r.json(); } catch (e) { }
    if (j.csrf && tok) tok.hash = j.csrf;
    return [r.status || 302, j];
  }
  const page = async path => { const r = await fetch(C().base + path, { credentials: 'same-origin', redirect: 'manual' }); const t = r.type === 'opaqueredirect' ? '' : await r.text(); return [r.type === 'opaqueredirect' ? 302 : r.status, t]; };
  async function run() {
    // 1. who may see what (r1 rules, unchanged): preparer, reviewer, view-only see the pages and data; other departments do not
    for (const u of [C().prep, C().rev, C().view]) {
      if (!ok(u + ' signs in', await login(u), u)) continue;
      const [p1, h1] = await page('accounts/daily_bank_rec'); const [p2] = await page('accounts/daily_bank_rec/fx');
      const [a1, j1] = await get('api/accounts'); const [a2] = await get('api/rates', { from: '2026-10-01', to: '2026-10-12' }); const [a3] = await get('api/audit');
      facts[u] = { overview: p1, fx: p2, accounts: [a1, (j1.accounts || []).length, j1.schema_version ?? j1.version ?? null], rates: a2, audit: a3, csrf_in_page: /const csrf = \{/.test(h1), set_up: !/not set up/i.test(h1) };
      ok(u + ': overview, rates page and data answer 200; no bank account is mapped (0 accounts)', p1 === 200 && p2 === 200 && a1 === 200 && a2 === 200 && a3 === 200 && (j1.accounts || []).length === 0, facts[u]);
    }
    for (const u of C().others) {
      await login(u); const [p1] = await page('accounts/daily_bank_rec'); const [a1] = await get('api/accounts'); const [w1] = await post('correction/reverify', { id: 999999 });
      facts[u] = [p1, a1, w1];
      ok(u + ': no access to the page, the data or any action (redirect/403)', p1 !== 200 && a1 === 403 && w1 === 403, facts[u]);
    }
    // 2. actions refused by role, before anything is written (four-eyes and view-only, as in r1/r2)
    await login(C().view);
    const v = [await post('correction/reverify', { id: 999999 }), await post('mapping/propose', { account: 999999, rec_start: '2026-10-01' }), await post('day/review', { id: 999999, decision: 'reviewed' }), await post('setup', {})].map(x => x[0]);
    ok('view-only (' + C().view + '): verify again, propose a mapping, review, set up → all 403', v.every(c => c === 403), v);
    await login(C().prep);
    const p = [await post('day/review', { id: 999999, decision: 'reviewed' }), await post('mapping/decide', { map_id: 999999, decision: 'confirm' }), await post('setup', {})].map(x => x[0]);
    ok('preparer (' + C().prep + '): review, confirm a mapping, set up → 403 (reviewer / administrator only)', p.every(c => c === 403), p);
    const [cs] = await post('correction/reverify', { id: 999999 }, true);
    ok('a change without the security token is refused (403)', cs === 403, cs);
    const [r3, j3] = await post('correction/reverify', { id: 999999 });
    ok('r3 route "verify again" is installed: with nothing posted it answers 409 "Only a correction whose posting was verified can be verified again."', r3 === 409 && /posting was verified/.test(j3.message || ''), [r3, j3.message]);
    await login(C().rev);
    const q = [await post('correction/reverify', { id: 999999 }), await post('day/prepare', { account: 999999, date: '2026-10-01' }), await post('mapping/propose', { account: 999999, rec_start: '2026-10-01' })].map(x => x[0]);
    ok('reviewer (' + C().rev + '): verify again, prepare, propose a mapping → 403 (preparer only)', q.every(c => c === 403), q);
    // 3. the old screen: still opens for its users; the r3 refusal handling is in the page; no write is sent
    await login(C().prep);
    const [o1, oh] = await page('accounts/bank_reconcil');
    facts.old_screen = { status: o1, r3_handlers: (oh.match(/tf-bankrec-r3: show the reason given by the server/g) || []).length, swal_fire_handlers: (oh.match(/Swal\.fire\("", \(\(xhr && xhr\.responseJSON/g) || []).length };
    ok('the old Bank Reconciliation screen opens for ' + C().prep + ' (200) and carries the r3 refusal messages (7 handlers)', o1 === 200 && facts.old_screen.r3_handlers === 7, facts.old_screen);
    await login(C().others[0]); const [o2] = await page('accounts/bank_reconcil');
    ok('the old screen stays closed to ' + C().others[0] + ' (no privilege)', o2 !== 200, o2);
    // 4. the version mark on plugins.js
    await login(C().prep); const [d1, dh] = await page('accounts/daily_bank_rec');
    const v1 = (dh.match(/assets\/js\/plugins\.js\?v=(\d+)/) || [])[1];
    facts.version_mark = v1;
    ok('pages load plugins.js?v=<modification time> (' + v1 + ')', d1 === 200 && /^\d{10}$/.test(v1 || ''), v1);
    await fetch(C().base + 'logout', { credentials: 'same-origin' }); who = null;
    return { log, facts };
  }
  return { run, login, get, post, page, log, facts };
})();
'dev checks loaded';
