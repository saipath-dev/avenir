// tf-bankrec-r3 — verification on the ISOLATED TEST INSTANCE (http://localhost/avenir_rv/, database
// avenir_restore_verify_20261011), dummy data only, test logins only. Rehearsed first on the cloud test copy with the same
// script (cfg.base '/avenir/', the cloud's test logins). Runs after tf-bankrec-r2's verification (rv_test_r2.js) left its data.
// Set window.__rvCfg = { base, pw, prep, rev, view, dual, old, pwOld?, pwDual?, depts: [...], ids: { LKR, USD, JPY, V } }, then:
//   await __rv3.phaseD7()          the OLD Bank Reconciliation screen (accounts/bank_reconcil): refusals on/after the first
//                                  day, earlier dates still allowed (a dummy row ticked and unticked again)
//   await __rv3.phaseFlags(n)      the new workflow's warning about old-screen flags (n = 2 after SQL set flags on two dummy
//                                  rows, 0 after they were put back) — the flags are set and put back OUTSIDE, in phpMyAdmin
//   await __rv3.phasePending()     pending corrections per day: the amount up to the day and the correction total
//   await __rv3.phasePostLines()   the approved lines of the V charge correction (to write the dummy ledger entry from)
//   await __rv3.phasePosted(id)    recording the dummy entry as the posting → verified and linked
//   await __rv3.phaseChanged(step) after each SQL change of the dummy entry: 'wording' | 'amount' | 'amount-back' | 'all-back'
//   await __rv3.phaseDual()        a user with both the preparer and the reviewer privilege and their own work
//   __rv3.race(at, user, path, data, n)   one tab's half of a two-user race (two DIFFERENT users, two sessions)
// Results: __rv3.log = [[ok|FAIL, name, detail]]; __rv3.facts = values read back.
window.__rv3 = (() => {
  const R = { log: [], facts: {} };
  const C = () => window.__rvCfg;
  const API = () => C().base + 'accounts/daily_bank_rec/';
  const ok = (name, cond, detail) => { R.log.push([cond ? 'ok' : 'FAIL', name, cond ? '' : JSON.stringify(detail ?? '').slice(0, 500)]); return cond; };
  const pwOf = u => (u === C().old && C().pwOld) || (u === C().dual && C().pwDual) || C().pw;
  let tok = null, who = null;
  async function login(user) {
    if (who === user) return true;
    await fetch(C().base + 'logout', { credentials: 'same-origin' }).catch(() => 0);
    const h = await fetch(C().base, { credentials: 'same-origin' }).then(r => r.text());
    const m = h.match(/name="([^"]+)" value="([0-9a-f]{32})"/);
    const fd = new FormData(); fd.append('username', user); fd.append('password', pwOf(user)); if (m) fd.append(m[1], m[2]);
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
    let r; try { r = await fetch(API() + path + (params ? '?' + new URLSearchParams(params) : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin', redirect: 'manual' }); } catch (e) { return [0, { message: String(e) }]; }
    let j = {}; try { j = await r.json(); } catch (e) { }
    return [r.status || (r.type === 'opaqueredirect' ? 302 : 0), j];
  }
  async function post(path, data) {
    const fd = new FormData();
    for (const [k, v] of Object.entries(data || {})) fd.append(k, typeof v === 'object' ? JSON.stringify(v) : v);
    const t = await token(); if (t) fd.set(t.name, t.hash);
    let r; try { r = await fetch(API() + path, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin', redirect: 'manual' }); } catch (e) { return [0, { message: String(e) }]; }
    let j = {}; try { j = await r.json(); } catch (e) { }
    if (j.csrf && tok) tok.hash = j.csrf;
    return [r.status || 302, j];
  }
  // the old screen posts form fields with jQuery ($.ajax, no JSON); the same here
  async function old(path, fields) {
    const p = new URLSearchParams(); for (const [k, v] of Object.entries(fields)) p.append(k, v);
    let r; try { r = await fetch(C().base + 'accounts/bank_reconcil/' + path, { method: 'POST', body: p, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json, text/javascript, */*' }, credentials: 'same-origin', redirect: 'manual' }); } catch (e) { return [0, { message: String(e) }]; }
    let j = {}; const t = await r.text(); try { j = JSON.parse(t); } catch (e) { j = { raw: t.slice(0, 160) }; }
    return [r.status || 302, j];
  }
  const day = async (acct, d) => { await login(C().view); const [c, j] = await get('api/day', { account: acct, date: d }); return c === 200 ? j.day : { status_code: 'HTTP ' + c }; };
  const corr = async (acct, id) => { await login(C().view); const [, j] = await get('api/corrections', { account: acct }); return (j.corrections || []).find(x => x.id == id) || {}; };
  const lineId = async (acct, desc) => { await login(C().view); const [, j] = await get('api/statements', { account: acct }); for (const s of (j.statements || []).filter(x => x.status === 'accepted')) { const [, sj] = await get('api/statement', { id: s.id }); const l = (sj.lines || []).find(x => (x.description || '').startsWith(desc)); if (l) return l.id; } return null; };

  // ------------------------------------------------------------------ D7: the old screen
  async function phaseD7() {
    const V = C().ids.V, vcode = '1-6-8-29904';
    if (!await login(C().old)) { ok('D7 the old-screen test login signs in', false, C().old); return; }
    const page = await fetch(C().base + 'accounts/bank_reconcil', { credentials: 'same-origin', redirect: 'manual' });
    ok('D7 ' + C().old + ' opens the old Bank Reconciliation screen (200)', page.status === 200, page.status);
    const res = {};
    res.update_protected = await old('update', { id: 99980305, flag: 0, bank: 0 });
    res.update_earlier = await old('update', { id: 99980302, flag: 0, bank: 0 });
    res.update_earlier_back = await old('update', { id: 99980302, flag: 0, bank: 1 });
    res.finalize_mixed = await old('finalize_selected', { 'records[0][id]': 99980302, 'records[0][bank]': 0, 'records[1][id]': 99980306, 'records[1][bank]': 0 });
    res.reconcile_protected = await old('reconciliation', { date2: '2026-09-05', bank: vcode });
    res.reconcile_earlier = await old('reconciliation', { date2: '2026-08-31', bank: vcode });
    res.unrealised_move = await old('post_unrealised_entries', { 'itemArray[0][lcode]': vcode, 'itemArray[0][ID]': 99980306 });
    res.unrealised_back_one = await old('repost_single_entry', { rowId: 99980304 });
    res.unrealised_back_range = await old('repost_unrealised_entries', { date1: '2026-08-15', date2: '2026-09-10', bank: vcode });
    res.update_lkr_dummy = await old('update', { id: 99980003, flag: 0, bank: 0 });
    R.facts.D7 = Object.fromEntries(Object.entries(res).map(([k, [c, j]]) => [k, [c, (j.message || j.raw || '').slice(0, 220), j.protected_period || false]]));
    const [c1, j1] = res.update_protected;
    ok('D7 "update" on dummy row 99980305 (2026-09-02, on/after the first day 2026-09-01 of the verification account) → refused 409 with the reason (names the first day and Daily Bank Reconciliation)',
      c1 === 409 && j1.protected_period === true && /2026-09-01/.test(j1.message) && /Daily Bank Reconciliation/.test(j1.message) && /Nothing was changed/.test(j1.message), R.facts.D7.update_protected);
    ok('D7 earlier dates stay available: dummy row 99980302 (2026-08-28, before the first day) ticked (200) and unticked again (200)', res.update_earlier[0] === 200 && res.update_earlier_back[0] === 200, [R.facts.D7.update_earlier, R.facts.D7.update_earlier_back]);
    ok('D7 "finalize selected" with one row before and one on/after the first day → all refused (409)', res.finalize_mixed[0] === 409, R.facts.D7.finalize_mixed);
    ok('D7 "reconcile up to" 2026-09-05 refused (409); up to 2026-08-31 allowed (200)', res.reconcile_protected[0] === 409 && res.reconcile_earlier[0] === 200, [R.facts.D7.reconcile_protected, R.facts.D7.reconcile_earlier]);
    ok('D7 moving a row to the unrealised account, moving one back, reposting a range reaching the first day, and a TEST LKR row → all refused (409)',
      [res.unrealised_move, res.unrealised_back_one, res.unrealised_back_range, res.update_lkr_dummy].every(x => x[0] === 409), [R.facts.D7.unrealised_move, R.facts.D7.unrealised_back_one, R.facts.D7.unrealised_back_range, R.facts.D7.update_lkr_dummy]);
    return R.facts.D7;
  }

  // ------------------------------------------------------------------ warning about old-screen flags (set outside by SQL)
  async function phaseFlags(n) {
    const V = C().ids.V;
    const d = await day(V, '2026-09-05'); const lf = d.legacy_flags || {};
    await login(C().view); const [, ja] = await get('api/accounts');
    const ov = ((ja.accounts || []).find(a => a.id == V) || {}).day || {};
    R.facts['flags_' + n] = { count: lf.count, rows: (lf.rows || []).map(r => [r.id, r.date, r.amount, r.flags.join(' + ')]), warnings: d.warnings, overview: ov.legacy_flags, status: d.status_label };
    if (n) {
      const rows = Object.fromEntries((lf.rows || []).map(r => [r.id, r.flags]));
      ok('F the new workflow warns about the old-screen flags in the protected period: 2 rows (bank_rec on 99980306; 99980307 moved to the unrealised account), on the day and on the overview',
        lf.count === 2 && (rows[99980306] || []).includes('bank_rec') && (rows[99980307] || []).some(f => /unrealised/.test(f)) && (d.warnings || []).some(w => /old Bank Reconciliation screen/.test(w)) && ov.legacy_flags === 2, R.facts['flags_' + n]);
    } else {
      ok('F flags put back: no warning on the day or the overview', (lf.count || 0) === 0 && !(ov.legacy_flags) && !(d.warnings || []).length, R.facts['flags_' + n]);
    }
    return R.facts['flags_' + n];
  }

  // ------------------------------------------------------------------ pending corrections per day
  async function phasePending() {
    const L = C().ids.LKR;
    const days = ['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04', '2026-09-05', '2026-09-06', '2026-09-07', '2026-09-08'];
    const seen = {}; let consistent = true, multi = null;
    for (const dd of days) {
      const d = await day(L, dd); const p = d.pending_postings || {}; const ap = p.approved || {}; const list = p.list || [];
      const ids = list.map(c => c.id);
      const sumA = list.filter(c => c.state === 'approved').reduce((s, c) => s + Math.round(parseFloat(c.attributable) * 100), 0);
      const sumF = list.filter(c => c.state === 'approved').reduce((s, c) => s + Math.round(parseFloat(c.full_amount) * 100), 0);
      const each = list.map(c => [c.id, c.state, c.attributable, c.full_amount]);
      seen[dd] = { total: ap.total, full_total: ap.full_total, summary: p.summary, each, status: d.status_label };
      const thisOk = ids.length === new Set(ids).size && Math.round(parseFloat(ap.total || 0) * 100) === sumA && Math.round(parseFloat(ap.full_total || 0) * 100) === sumF
        && list.every(c => Math.abs(parseFloat(c.attributable)) <= Math.abs(parseFloat(c.full_amount)) + 0.001)
        && ((ap.total !== ap.full_total) === /up to this day; correction total/.test(((p.summary || '').match(/approved but not posted \(([^)]*)\)/) || ['', ''])[1]));
      consistent = consistent && thisOk;
      for (const c of list) if (parseFloat(c.attributable) < Math.abs(parseFloat(c.full_amount)) - 0.001 && !multi) multi = { id: c.id, day: dd, attributable: c.attributable, full: c.full_amount };
    }
    R.facts.pending = seen; R.facts.pending_multi = multi;
    ok('P each day lists each correction once; the day\'s total = the sum of the amounts up to that day; the full total = the sum of the correction totals; "up to this day; correction total" shown exactly when they differ',
      consistent, seen);
    ok('P a correction covering several days is counted only for the part up to the day (found: correction ' + (multi ? multi.id + ', ' + multi.attributable + ' of ' + multi.full + ' on ' + multi.day : 'none') + ')', !!multi, seen);
    if (multi) {
      // the same correction on later days: never decreasing, and complete on the last day
      const series = days.map(dd => ((seen[dd].each.find(e => e[0] === multi.id) || [])[2])).filter(Boolean).map(Number);
      R.facts.pending_series = series;
      ok('P that correction\'s amount grows day by day and equals its total once all its days have passed (' + series.join(' → ') + ')',
        series.every((v, i) => i === 0 || v >= series[i - 1]) && Math.abs(series[series.length - 1] - Math.abs(parseFloat(multi.full))) < 0.001, series);
    }
    // a daily record prepared for a day inside the correction's period keeps the split, and the reviewer sees it
    const mid = multi ? multi.day : '2026-09-03';
    await login(C().prep); let [c, j] = await post('day/prepare', { account: L, date: mid, note: 'r3: amounts up to the day (dummy demonstration)' }); const rid = j.id;
    await login(C().view); const [c2, j2] = await get('api/day_record', { id: rid });
    const rec = (j2.record || {}); const pend = rec.pending || (rec.snapshot || {}).pending_postings || {};
    R.facts.pending_record = { id: rid, prepared: c, message: j.message, kept: pend.summary || JSON.stringify(rec).match(/[^"]*up to this day; correction total[^"]*/)?.[0] };
    ok('P a daily record prepared for ' + mid + ' keeps "… up to this day; correction total …" in its snapshot', c === 200 && /up to this day; correction total/.test(JSON.stringify(j2)), R.facts.pending_record);
    await login(C().rev); [c, j] = await post('day/review', { id: rid, decision: 'reviewed', note: 'r3 (dummy demonstration)' });
    const dm = await day(L, mid);
    R.facts.pending_reviewed = { code: c, status: dm.status_label };
    ok('P reviewed: "Reviewed — ledger not yet corrected — … approved but not posted (… up to this day; correction total …)"', c === 200 && dm.status_code === 'reviewed_pending' && /up to this day; correction total/.test(dm.status_label), R.facts.pending_reviewed);
    return seen;
  }

  // ------------------------------------------------------------------ a posted dummy correction: verify, change, verify again
  async function phasePostLines() {
    const F = C().cV; const c = await corr(C().ids.V, F);
    R.facts.cV = { id: c.id, status: c.status, state: c.state, amount: c.amount, entry_date: c.entry_date, lines: c.entry || c.lines || c.entry_json, description: c.description };
    return R.facts.cV;
  }
  async function phasePosted(ledgerId) {
    const V = C().ids.V, F = C().cV;
    const before = await day(V, '2026-09-05');
    await login(C().prep); const [c, j] = await post('correction/posted', { id: F, ledger_id: String(ledgerId) });
    const a = await corr(V, F); const d = await day(V, '2026-09-05');
    R.facts.posted = { code: c, message: j.message, state: a.state, label: a.state_label, posted_ref: a.posted_ref, by: a.posted_noted_by, pending_before: (before.pending_postings || {}).summary, pending_after: (d.pending_postings || {}).summary, status_after: d.status_label };
    ok('R the dummy ledger entry ' + ledgerId + ' (test database only) is recorded as the posting of correction ' + F + ': checked against the approved lines → "posted and verified", linked to the entry',
      c === 200 && a.state === 'posted' && String(a.posted_ref) === String(ledgerId) && a.posting_state === 'verified', R.facts.posted);
    ok('R the 5 Sep pending postings no longer include it (the ledger is corrected for that item)', !((d.pending_postings || {}).approved || {}).ids?.includes(F), R.facts.posted);
    return R.facts.posted;
  }
  async function phaseChanged(step) {
    const V = C().ids.V, F = C().cV;
    const a = await corr(V, F);
    R.facts['changed_' + step] = { state: a.state, label: a.state_label };
    if (step === 'wording') {
      const d = await day(V, '2026-09-05');
      ok('R the entry\'s wording changed in the ledger (SQL, dummy row) → "Posted entry changed in the ledger — verify again"', a.state === 'posted_changed', [a.state_label, d.status_label]);
      await login(C().rev); const [c1] = await post('correction/reverify', { id: F });
      await login(C().prep); const [c2, j2] = await post('correction/reverify', { id: F });
      const b = await corr(V, F);
      await login(C().prep); const [c3, j3] = await post('correction/reverify', { id: F });
      R.facts.reverify = { reviewer: c1, preparer: [c2, j2.message], state: b.state, again: [c3, j3.message] };
      ok('R the reviewer (no preparer privilege) cannot verify again (403); the preparer verifies again → the entry still matches → posted and verified; verifying again with nothing changed is refused (409)',
        c1 === 403 && c2 === 200 && b.state === 'posted' && c3 === 409, R.facts.reverify);
    } else if (step === 'amount') {
      await login(C().prep); const [c, j] = await post('correction/reverify', { id: F });
      const b = await corr(V, F);
      R.facts.reverify_amount = [c, j.message, b.state];
      ok('R the entry\'s amounts changed (25 → 30): verifying again is refused (409, "does not match") and it stays "verify again"', c === 409 && /does not match/.test(j.message || '') && b.state === 'posted_changed', R.facts.reverify_amount);
    } else if (step === 'amount-back') {
      ok('R amounts put back → posted and verified again without any action (the entry is as verified)', a.state === 'posted', a.state_label);
    } else if (step === 'all-back') {
      await login(C().prep); const [c] = await post('correction/reverify', { id: F });
      const b = await corr(V, F);
      ok('R wording put back → "verify again" → verified again; the correction is posted and verified', a.state === 'posted_changed' && c === 200 && b.state === 'posted', [a.state, c, b.state]);
    }
    return R.facts['changed_' + step];
  }

  // ------------------------------------------------------------------ a user with both privileges and their own work
  async function phaseDual() {
    const V = C().ids.V, D = C().dual;
    if (!await login(D)) { ok('U the dual-role test login signs in', false, D); return; }
    let [c, j] = await post('opening/propose', { account: V, book_key: '99980301.1', evidence: 'dual-role test (dummy)' }); const oid = j.id;
    const [c2, j2] = await post('opening/decide', { id: oid, decision: 'accept', note: 'own' });
    const [c3] = await post('opening/decide', { id: oid, decision: 'withdraw', note: 'dual-role test withdrawn' });
    ok('U ' + D + ' (preparer AND reviewer privilege, not an administrator) proposes an opening item (200), cannot accept their own (403), withdraws it (200)', c === 200 && c2 === 403 && c3 === 200, [c, c2, c3, j2.message]);
    const l3 = C().l3 || await lineId(V, 'TEST INTEREST CREDIT'); R.facts.l3 = l3;
    await login(D);
    [c, j] = await post('correction/propose', { account: V, kind: 'unbooked_receipt', description: 'TEST interest credit 6 Sep not in the ledger (dual-role test, dummy)', entry_date: '2026-09-06',
      lines: [{ ledger_code: '1-6-8-29904', dr: '1000', cr: '' }, { ledger_code: '2-9-99-29909', dr: '', cr: '1000' }], line_ids: [l3] }); const cid = j.id;
    const [c4, j4] = await post('correction/decide', { id: cid, decision: 'approve', note: 'own' });
    const [c5] = await post('correction/withdraw', { id: cid, reason: 'dual-role test withdrawn' });
    ok('U the same user proposes a correction (200), cannot approve it (403), withdraws it (200)', c === 200 && c4 === 403 && c5 === 200, [c, j.message, c4, j4.message, c5]);
    [c, j] = await post('day/prepare', { account: V, date: '2026-09-05', note: 'dual-role test' }); const rid = j.id;
    const [c6, j6] = await post('day/review', { id: rid, decision: 'reviewed', note: 'own' });
    await login(C().rev); const [c7] = await post('day/review', { id: rid, decision: 'reviewed', note: 'reviewed by the reviewer (dummy)' });
    ok('U the same user prepares a daily record (200), cannot review it (403); the reviewer reviews it (200)', c === 200 && c6 === 403 && c7 === 200, [c, c6, j6.message, c7]);
    await login(C().prep); [c, j] = await post('day/prepare', { account: V, date: '2026-09-04', note: 'prepared by the preparer (dummy)' });
    await login(D); const [c8, j8] = await post('day/review', { id: j.id, decision: 'reviewed', note: 'dual-role user reviews someone else\'s record' });
    ok('U the same user may review a record someone else prepared (200)', c === 200 && c8 === 200, [c, c8, j8.message]);
    R.facts.dual = { opening: oid, correction: cid, own_record: rid, other_record: j.id };
    return R.facts.dual;
  }

  // race(at, user, path, data, n): this tab's own session; wait for the shared instant `at`, then send
  async function race(at, user, path, data, n) {
    await login(user); await token();
    const out = [];
    while (Date.now() < at) await new Promise(r => setTimeout(r, Math.min(20, at - Date.now())));
    for (let i = 0; i < (n || 1); i++) { const t0 = Date.now(); const [c, j] = await post(path, data); out.push({ user, code: c, message: j.message, id: j.id, t0, t1: Date.now() }); }
    return out;
  }
  return { R, log: R.log, facts: R.facts, login, get, post, old, day, corr, lineId, token, race, phaseD7, phaseFlags, phasePending, phasePostLines, phasePosted, phaseChanged, phaseDual, who: () => who };
})();
'loaded r3';
