// tf-legacy-retire-r1 — refusal checks in a browser tab, on the isolated test instance or on development. No tool is ever run:
// every request below is refused before any ledger operation (no token / wrong token → 403; valid token → 409, retired).
//   window.__lrCfg = { base: '/avenir_rv/', user: 'TestLegacyTools', pw, other: 'TestAccounts', otherPw }   (own sign-in; 127.0.0.1 tab;
//   the passwords are typed in at run time and never stored)
//   window.__lrCfg = { base: '/avenir/', useCurrentSession: true }        (development: the session already open; never signs in or out)
//   await __lr.run()  → { log, facts }        await __lr.pages() → only the page checks (no request to the tools)
window.__lr = (() => {
  const C = () => window.__lrCfg; let log = []; let facts = {};
  const ok = (n, c, d) => { log.push([c ? 'ok' : 'FAIL', n, c ? '' : JSON.stringify(d ?? '').slice(0, 400)]); return c; };
  const AJ = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };
  const MSG_A = 'This one-off was completed on 21 October 2025 (entries 15947–16232) and has been retired. Running it again would copy the same 2024/25 entries into the ledger a second time. Nothing was changed.';
  const MSG_B = 'Bank Req Data Transfer has been retired. The 2024/25 unreconciled bank items were already carried forward on 21 October 2025, and the current ledger has no 31 March 2025 brought-forward balance to adjust. Nothing was changed.';
  const MSG_T = 'Security token missing or expired — reload the page. Nothing was changed.';
  const BUTTONS = ['des_update', 'des_other_update', 'hotel_voucher_accept', 'entrance_update', 'other_update', 'transport_update', 'voucher_date_update', 'verify_tour'];
  const ACTIONS = BUTTONS.concat(['debtor_receipt_generate']);
  const PA = 'settings/manual_update/bank_recon_record_insert', PB = 'accounts/bank_unrealised_value/process';
  async function login(user, pw) {
    if (C().useCurrentSession) throw new Error('never signs in or out of the current session');
    await fetch(C().base + 'logout', { credentials: 'same-origin', redirect: 'manual' }).catch(() => 0);
    const h = await fetch(C().base, { credentials: 'same-origin', redirect: 'manual' }).then(r => r.type === 'opaqueredirect' ? '' : r.text());
    const m = h.match(/name="([^"]+)" value="([0-9a-f]{32})"/);
    const fd = new FormData(); fd.append('username', user); fd.append('password', pw); if (m) fd.append(m[1], m[2]);
    for (let i = 0; i < 8; i++) {
      const j = await fetch(C().base + 'login', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }).then(r => r.json()).catch(() => ({}));
      if (j.status === 429) { await new Promise(r => setTimeout(r, ((+j.retry_after || 5) + 1) * 1000)); continue; }
      return j.status === 200;
    }
    return false;
  }
  const page = async p => { const r = await fetch(C().base + p, { credentials: 'same-origin', redirect: 'manual' }); return [r.type === 'opaqueredirect' ? 302 : r.status, r.type === 'opaqueredirect' ? '' : await r.text()]; };
  async function post(p, data) {
    const fd = new FormData(); for (const [k, v] of Object.entries(data || {})) fd.append(k, v);
    let r; try { r = await fetch(C().base + p, { method: 'POST', body: fd, headers: AJ, credentials: 'same-origin', redirect: 'manual' }); } catch (e) { return [0, {}]; }
    let j = {}; try { j = await r.json(); } catch (e) { }
    return [r.type === 'opaqueredirect' ? 302 : r.status, j];
  }
  const tok = h => { const m = h.match(/data-csrf-name="([^"]+)" data-csrf-hash="([0-9a-f]+)"/); return m ? [m[1], m[2]] : null; };
  async function pages(who) {
    const [c1, h1] = await page('settings/manual_update'); const [c2, h2] = await page('accounts/bank_unrealised_value');
    facts.pages = { manual_update: c1, bank_unrealised_value: c2, missing_buttons: BUTTONS.filter(b => !h1.includes(`id="${b}"`)), missing_actions: ACTIONS.filter(e => !h1.includes(`settings/manual_update/${e}'`)) };
    ok(`[${who}] Manual Update opens (200): "Bank Not Reconciliation Record Insert" is replaced by the "Retired" notice; the 8 other buttons and the 9 other actions are unchanged`,
      c1 === 200 && h1.includes('id="bank_recon_record_insert_retired"') && h1.includes('Retired:</strong> Bank Not Reconciliation Record Insert') && !h1.includes('id="bank_recon_record_insert"')
      && !facts.pages.missing_buttons.length && !facts.pages.missing_actions.length, facts.pages);
    ok(`[${who}] Bank Req Data Transfer opens (200): the Process button is replaced by the "Retired" notice`,
      c2 === 200 && h2.includes('id="process_btn_retired"') && !h2.includes('id="process_btn"') && h2.includes('id="bank"'), c2);
    return tok(h1);
  }
  async function tools(who, t) {
    const a1 = await post(PA), a2 = await post(PA, { [t[0]]: '0'.repeat(32) }), a3 = await post(PA, { [t[0]]: t[1] });
    facts[who + ' A'] = [a1[0], a2[0], a3[0]];
    ok(`[${who}] "Bank Not Reconciliation Record Insert": no token → 403, wrong token → 403; valid token → 409 (retired)`,
      a1[0] === 403 && a2[0] === 403 && a3[0] === 409 && a1[1].message === MSG_T && a2[1].message === MSG_T && a3[1].message === MSG_A && a3[1].retired === true, [a1, a2, a3]);
    const t2 = [t[0], a3[1].csrf || t[1]];
    const b1 = await post(PB, { bank: '1-6-8-10380' }), b2 = await post(PB, { bank: '1-6-8-10380', [t2[0]]: 'f'.repeat(32) }), b3 = await post(PB, { bank: '1-6-8-10380', [t2[0]]: t2[1] });
    facts[who + ' B'] = [b1[0], b2[0], b3[0]];
    ok(`[${who}] "Bank Req Data Transfer": no token → 403, wrong token → 403; valid token → 409 (retired)`,
      b1[0] === 403 && b2[0] === 403 && b3[0] === 409 && b1[1].message === MSG_T && b3[1].message === MSG_B && b3[1].retired === true, [b1, b2, b3]);
  }
  async function run() {
    log = []; facts = {};
    const who = C().useCurrentSession ? 'current session' : C().user;
    if (!C().useCurrentSession && !ok(`${C().user} signs in`, await login(C().user, C().pw))) return { log, facts };
    const t = await pages(who);
    if (!ok(`[${who}] the pages carry the security token`, !!t)) return { log, facts };
    await tools(who, t);
    if (!C().useCurrentSession && C().other) {
      await login(C().other, C().otherPw);
      const x = await post(PA), y = await post(PB, { bank: '1-6-8-10380' });
      facts.other = [x[0], y[0]];
      ok(`[${C().other}] without the two privileges: stopped by the permission filter (403) before the tool`, x[0] === 403 && y[0] === 403 && x[1].message !== MSG_A && y[1].message !== MSG_B, [x, y]);
      await fetch(C().base + 'logout', { credentials: 'same-origin', redirect: 'manual' }).catch(() => 0);
    } else if (!C().useCurrentSession) {
      await fetch(C().base + 'logout', { credentials: 'same-origin', redirect: 'manual' }).catch(() => 0);
    }
    return { log, facts };
  }
  async function pagesOnly() { log = []; facts = {}; await pages(C().useCurrentSession ? 'current session' : C().user); return { log, facts }; }
  return { run, pages: pagesOnly, post, page, login };
})();
'retire checks loaded';
