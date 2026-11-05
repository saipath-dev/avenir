// tf-bankrec-r2 — verification and the 6–8 Sep demonstration on the ISOLATED TEST INSTANCE (http://localhost/avenir_rv/,
// database avenir_restore_verify_20261011), dummy data only, test logins only. Rehearsed first on the cloud test copy
// with the same script (cfg.base '/avenir/', the cloud's test logins).
// Set window.__rvCfg = { base, pw, prep, rev, view, depts: [...], ids: { LKR, USD, JPY, V } } and then run, in order:
//   await __rv2.phaseA()  the existing demonstration data under the r2 rules (read only)
//   await __rv2.phaseC()  the 6–8 Sep demonstration (D10 opening item, import, D9 timing match, explain, review, re-review, FX)
//   await __rv2.phaseB()  the verification account: D10 over several days, partial clearing, undo/reopen, changes after
//                         review, pending postings, four eyes, permissions (the parallel races are run separately: race())
// Results: __rv2.log = [[ok|FAIL, name, detail]]; __rv2.facts = values read back.
window.__rv2 = (() => {
  const R = { log: [], facts: {} };
  const C = () => window.__rvCfg;
  const API = () => C().base + 'accounts/daily_bank_rec/';
  const ok = (name, cond, detail) => { R.log.push([cond ? 'ok' : 'FAIL', name, cond ? '' : JSON.stringify(detail ?? '').slice(0, 400)]); return cond; };
  let tok = null, who = null;
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
    // a user without the privilege is redirected (possibly to another host name): no token then; the server refuses anyway.
    // No trailing slash: on WAMP the .htaccess redirects '…/daily_bank_rec/' to '/public/…' (found on the isolated instance, 12 Oct)
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
    const fd = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData)) for (const [k, v] of Object.entries(data || {})) fd.append(k, typeof v === 'object' ? JSON.stringify(v) : v);
    const t = await token(); if (t) fd.set(t.name, t.hash);
    let r; try { r = await fetch(API() + path, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin', redirect: 'manual' }); } catch (e) { return [0, { message: String(e) }]; }
    let j = {}; try { j = await r.json(); } catch (e) { }
    if (j.csrf && tok) tok.hash = j.csrf;
    return [r.status || 302, j];
  }
  const day = async (acct, d) => { await login(C().view); const [c, j] = await get('api/day', { account: acct, date: d }); return c === 200 ? j.day : { status_code: 'HTTP ' + c }; };
  const csv = rows => new Blob([rows.map(r => r.map(c => /[",\n]/.test(c) ? '"' + c.replace(/"/g, '""') + '"' : c).join(',')).join('\n')], { type: 'text/csv' });
  const upload = async (acct, rows, opening, name) => { const fd = new FormData(); fd.append('account', acct); fd.append('source', 'upload'); fd.append('opening_balance', opening); fd.append('currency', 'LKR'); fd.append('file', csv(rows), name); return post('statement/save', fd); };
  const lineId = async (acct, desc, stmt) => { await login(C().view); const [, j] = await get('api/statements', { account: acct }); for (const s of (j.statements || []).filter(x => x.status !== 'void' && (!stmt || x.id == stmt))) { const [, sj] = await get('api/statement', { id: s.id }); const l = sj.lines.find(x => x.description === desc); if (l) return l.id; } return null; };
  const HDR = ['Txn Date', 'Value Date', 'Description', 'Cheque No', 'Debit', 'Credit', 'Balance'];

  // ------------------------------------------------------------------ A: the existing demonstration under r2
  async function phaseA() {
    const I = C().ids;
    const d5 = await day(I.LKR, '2026-09-05'); const r5 = d5.reconciliation || {};
    R.facts.A_lkr5 = { status: d5.status_label, review: (d5.review || {}).label, opening_remaining: r5.opening_remaining, pre_start_not_accepted: r5.pre_start_cleared_not_accepted, unexplained: r5.unexplained_lkr, pending: (d5.pending_postings || {}).summary };
    ok('A LKR 5 Sep under r2 (D10): the cheque of 30 Aug cleared but is not an accepted opening item → "Unreconciled — insufficient data"', d5.status_code === 'insufficient' && r5.opening_remaining === '150000.00' && r5.pre_start_cleared_not_accepted === '-150000.00' && (d5.reasons || []).some(x => x.includes('not an accepted opening item')), R.facts.A_lkr5);
    ok('A the record reviewed under r1 (#1) is flagged "changed since review — re-review needed"; its snapshot is kept', (d5.review || {}).state === 'reviewed_outdated', d5.review);
    const du = await day(I.USD, '2026-09-05');
    R.facts.A_usd5 = { status: du.status_label, differences: (du.items || {}).differences, revaluation: (du.reconciliation || {}).revaluation };
    ok('A USD 5 Sep: the two exchange-rate differences recorded under r1 without an explanation are "to explain" → items to explain', du.status_code === 'open_items' && (du.counts || {}).differences_unexplained === 2, R.facts.A_usd5);
    const dj = await day(I.JPY, '2026-09-05');
    R.facts.A_jpy5 = { status: dj.status_label, revaluation: (dj.reconciliation || {}).revaluation };
    ok('A JPY 5 Sep: still explained (revaluation 32,000.00 shown)', dj.status_code === 'explained' && dj.reconciliation.revaluation === '32000.00', R.facts.A_jpy5);
    const [c, j] = await get('api/corrections', { account: I.LKR });
    R.facts.A_corrections = (j.corrections || []).map(x => [x.id, x.state_label, x.amount]);
    ok('A the approved corrections are shown "Approved — not posted (ledger not corrected)"', (j.corrections || []).every(x => x.state === 'approved') && (j.corrections || []).length >= 2, R.facts.A_corrections);
    const [c2, ja] = await get('api/accounts');
    const lk = (ja.accounts || []).find(a => a.id == I.LKR) || {};
    ok('A overview: "Re-review needed" flag on the LKR account', (lk.rereview || []).length === 1, lk.rereview);
  }

  // ------------------------------------------------------------------ C: the 6–8 Sep demonstration
  async function phaseC() {
    const I = C().ids;
    // D10: the cheque of 30 Aug as an opening outstanding item
    await login(C().prep);
    let [c, j] = await get('api/opening_candidates', { account: I.LKR });
    const cand = (j.candidates || []).find(x => x.key === '99980002.1');
    ok('C1 D10: the cheque of 30 Aug is listed among the ledger rows before the first day (effect +150,000.00; cleared 2 Sep)', cand && cand.effect_at_start === '150000.00' && cand.cleared_on === '2026-09-02', cand);
    [c, j] = await post('opening/propose', { account: I.LKR, book_key: '99980002.1', evidence: 'cheque 000123 issued 30 Aug, presented 2 Sep (demonstration)' }); const oId = j.id;
    ok('C1 the preparer proposes it as an opening outstanding item', c === 200, j.message);
    await login(C().rev);
    [c, j] = await post('opening/decide', { id: oId, decision: 'accept', note: 'seen on the 2 Sep statement' });
    ok('C1 the reviewer accepts it', c === 200, j.message);
    let d1 = await day(I.LKR, '2026-09-01'), d5 = await day(I.LKR, '2026-09-05');
    R.facts.C1 = { d1: [d1.status_label, d1.reconciliation.opening_remaining, d1.reconciliation.opening_items_outstanding], d5: [d5.status_label, d5.reconciliation.opening_remaining, d5.reconciliation.opening_items_cleared] };
    ok('C1 day 1 now reconciles: opening difference explained (remaining 0.00, 150,000.00 outstanding); 5 Sep: cleared on 2 Sep; both "ledger not yet corrected"', d1.reconciliation.opening_remaining === '0.00' && d1.reconciliation.opening_items_outstanding === '150000.00' && d5.reconciliation.opening_items_cleared === '-150000.00' && d1.status_code === 'explained_pending' && d5.status_code === 'explained_pending', R.facts.C1);
    // import 6–8 Sep
    const S68 = [HDR, ['06/09/2026', '06/09/2026', 'AH 9904 - TEST RESTAURANT E - 90004', '', '40,000.00', '', '3,853,106.00 CR'], ['06/09/2026', '06/09/2026', 'IB CEFT CHGS', '', '25.00', '', '3,853,081.00 CR'],
      ['07/09/2026', '07/09/2026', 'CEFT RECEIPT - TEST AGENT ABC TRAVEL', '', '', '100,000.00', '3,953,081.00 CR'], ['08/09/2026', '08/09/2026', 'AH 9906 - TEST HOTEL F - 90006', '', '60,000.00', '', '3,893,081.00 CR']];
    await login(C().prep);
    const fd = new FormData(); fd.append('account', I.LKR); fd.append('source', 'upload'); fd.append('opening_balance', '3,893,106.00 CR'); fd.append('currency', 'LKR'); fd.append('file', csv(S68), 'DUMMY-statement-TEST-LKR-06-to-08-Sep-2026.csv');
    [c, j] = await post('statement/preview', fd);
    R.facts.C2_preview = { errors: (j.checks || {}).errors, warnings: (j.checks || {}).warnings, categories: ((j.checks || {}).summary || {}).categories, closing: ((j.checks || {}).summary || {}).closing_balance };
    ok('C2 Check: no errors; categories payment 2, charge 1, receipt 1; closing 3,893,081.00; nothing kept', c === 200 && !(j.checks.errors || []).length && j.checks.summary.closing_balance === '3893081.00', R.facts.C2_preview);
    [c, j] = await upload(I.LKR, S68, '3,893,106.00 CR', 'DUMMY-statement-TEST-LKR-06-to-08-Sep-2026.csv'); const s68 = j.id;
    [c, j] = await post('statement/accept', { id: s68 });
    ok('C2 kept as a draft and accepted (statement ' + s68 + ')', c === 200, j.message);
    // D9: the timing item is offered and cleared by the match
    [c, j] = await get('api/suggest', { account: I.LKR, from: '2026-09-01', to: '2026-09-08' });
    const sg = (j.suggestions || []).find(s => s.line.description === 'AH 9904 - TEST RESTAURANT E - 90004');
    R.facts.C3_suggestion = sg ? { confidence: sg.confidence, book: sg.book.map(b => b.key), timing: sg.timing_ids, why: sg.why } : null;
    ok('C3 D9: the 6 Sep payment PV 90004 is suggested (high) for the timing item 99980009 — no need to clear it first', sg && sg.confidence === 'high' && sg.book[0].key === '99980009.1' && sg.timing_ids.length === 1, R.facts.C3_suggestion);
    [c, j] = await post('match/create', { account: I.LKR, line_ids: [sg.line.id], book_keys: ['99980009.1'], method: 'suggested', expect_timing: sg.timing_ids });
    ok('C3 accepted: matched; the timing classification is cleared by the match (kept in the record)', c === 200 && /timing classification/.test(j.message), j.message);
    let d8 = await day(I.LKR, '2026-09-08'); let r8 = d8.reconciliation;
    R.facts.C4 = { status: d8.status_label, bank: d8.bank.closing, book: d8.book.closing, diff: r8.difference_lkr, not_in_ledger: r8.bank_items_not_in_book, not_on_statement: r8.book_items_not_on_statement, rounding: r8.match_differences, opening_cleared: r8.opening_items_cleared, unexplained: r8.unexplained_lkr, to_explain: d8.counts.unexplained_items };
    ok('C4 8 Sep: bank 3,893,081.00, book 3,547,655.00, difference 345,426.00 explained to 0.00; 3 items to explain (charge 25, receipt 100,000, payment 60,000)', d8.bank.closing === '3893081.00' && d8.book.closing === '3547655.00' && r8.difference_lkr === '345426.00' && r8.unexplained_lkr === '0.00' && d8.status_code === 'open_items' && d8.counts.unexplained_items === 3, R.facts.C4);
    // explain the three items
    await login(C().prep);
    const lCh = await lineId(I.LKR, 'IB CEFT CHGS', s68), lRc = await lineId(I.LKR, 'CEFT RECEIPT - TEST AGENT ABC TRAVEL', s68), lPy = await lineId(I.LKR, 'AH 9906 - TEST HOTEL F - 90006', s68);
    await login(C().prep);
    const [c1, j1] = await post('correction/charges', { account: I.LKR, line_ids: [lCh] });
    const [c2, j2] = await post('correction/propose', { account: I.LKR, kind: 'unbooked_receipt', description: 'CEFT receipt from TEST AGENT ABC TRAVEL 7 Sep not in the ledger (demonstration)', entry_date: '2026-09-07', lines: [{ ledger_code: '1-6-8-29901', dr: '100000', cr: '' }, { ledger_code: '2-9-99-29909', dr: '', cr: '100000' }], line_ids: [lRc] });
    const [c3, j3] = await post('mark/create', { account: I.LKR, side: 'bank', ref: lPy, mark: 'timing', expected_date: '2026-09-10', note: 'PV 90006 TEST HOTEL F paid 8 Sep; the voucher is to be entered in the ledger (demonstration)' });
    ok('C5 the three items explained: charge and receipt proposed as corrections, the payment classified as timing', c1 === 200 && c2 === 200 && c3 === 200, [j1.message, j2.message, j3.message]);
    d8 = await day(I.LKR, '2026-09-08');
    R.facts.C5 = { status: d8.status_label, pending: d8.pending_postings.summary };
    ok('C5 8 Sep → "All items explained — ledger not yet corrected — 2 correction(s) awaiting approval (100,025.00); 2 approved but not posted (239,550.00)"', d8.status_code === 'explained_pending' && d8.pending_postings.proposed.count === 2 && d8.pending_postings.proposed.total === '100025.00' && d8.pending_postings.approved.total === '239550.00', R.facts.C5);
    await login(C().prep);
    [c, j] = await post('day/prepare', { account: I.LKR, date: '2026-09-08', note: 'demonstration 6–8 Sep' }); const rec8 = j.id;
    ok('C6 the preparer prepares the 8 Sep record', c === 200, j.message);
    [c, j] = await post('day/review', { id: rec8, decision: 'reviewed' });
    ok('C6 the preparer cannot review it (403)', c === 403, c);
    await login(C().rev);
    [c, j] = await post('correction/decide', { id: j1.id, decision: 'approve', note: 'demonstration' });
    const [cx, jx] = await post('correction/decide', { id: j2.id, decision: 'approve', note: 'demonstration' });
    [c, j] = await post('day/review', { id: rec8, decision: 'reviewed', note: 'demonstration' });
    d8 = await day(I.LKR, '2026-09-08');
    R.facts.C6 = { status: d8.status_label, review: d8.review.label, pending: d8.pending_postings.summary };
    ok('C6 the reviewer approves both corrections (not posted) and reviews the record → "Reviewed — ledger not yet corrected — 4 approved but not posted (339,575.00)"', c === 200 && d8.status_code === 'reviewed_pending' && d8.pending_postings.approved.count === 4 && d8.pending_postings.approved.total === '339575.00', R.facts.C6);
    // re-review 5 Sep
    await login(C().prep);
    [c, j] = await post('day/prepare', { account: I.LKR, date: '2026-09-05', note: 'prepared again under r2 (opening item accepted)' }); const rec5 = j.id;
    await login(C().rev);
    [c, j] = await post('day/review', { id: rec5, decision: 'reviewed', note: 're-reviewed' });
    d5 = await day(I.LKR, '2026-09-05');
    R.facts.C7 = { status: d5.status_label, review: d5.review.label };
    ok('C7 5 Sep prepared again and re-reviewed → review current', c === 200 && d5.review.state === 'reviewed' && d5.status_code === 'reviewed_pending', R.facts.C7);
    // USD: explain the r1 differences, prepare with the revaluation explained, review
    await login(C().view);
    [c, j] = await get('api/day', { account: I.USD, date: '2026-09-05' });
    const diffs = j.day.items.differences;
    await login(C().prep);
    const notes = { '2500.00': 'booked at 300.00; credited at the 2 Sep rate 300.50 (demonstration)', '-10000.00': 'USD 10,000 sold at 300.00 (bank); valued at the day rate 301.00 (demonstration)' };
    let exOk = true;
    for (const x of diffs) { const [cc] = await post('match/explain', { id: x.match_id, diff_reason: 'exchange rate', note: notes[x.amount_lkr] || 'exchange-rate difference (demonstration)' }); exOk = exOk && cc === 200; }
    [c, j] = await post('day/prepare', { account: I.USD, date: '2026-09-05', note: 'demonstration' });
    const [c4, j4] = await post('day/prepare', { account: I.USD, date: '2026-09-05', note: 'demonstration', fx_note: 'unrealised difference on the USD balance at 302.00; month-end revaluation entry by Accounts (demonstration)' });
    await login(C().rev);
    const [c5] = await post('day/review', { id: j4.id, decision: 'reviewed', note: 'demonstration' });
    const du = await day(I.USD, '2026-09-05');
    R.facts.C8 = { explained: exOk, refused_without_fx_note: c, status: du.status_label, review: du.review.label };
    ok('C8 USD: the two exchange-rate differences explained; the record needs the revaluation explained (400 without); reviewed → "Reviewed — ledger not yet corrected" (USD charge approved, not posted)', exOk && c === 400 && c4 === 200 && c5 === 200 && du.status_code === 'reviewed_pending', R.facts.C8);
  }

  // ------------------------------------------------------------------ B: the verification account
  async function phaseB() {
    const I = C().ids, V = I.V;
    await login(C().prep);
    let [c, j] = await post('mapping/propose', { account: V, rec_start: '2026-09-01', note: 'r2 verification (dummy)' });
    await login(C().rev); let [c2] = await post('mapping/decide', { map_id: j.id, decision: 'confirm', note: 'dummy' });
    ok('B verification account: mapping proposed and confirmed', c === 200 && c2 === 200, j.message);
    await login(C().prep);
    const S1 = [HDR, ['01/09/2026', '01/09/2026', 'IB CEFT CHGS', '', '25.00', '', '499,975.00 CR'], ['03/09/2026', '03/09/2026', 'CHQ 000201 TEST CHEQUE', '000201', '20,000.00', '', '479,975.00 CR'],
      ['03/09/2026', '03/09/2026', 'CEFT RECEIPT - TEST AGENT XYZ', '', '', '50,000.00', '529,975.00 CR'], ['03/09/2026', '03/09/2026', 'AH 9911 - TEST VENDOR K - 90012', '', '8,000.00', '', '521,975.00 CR']];
    [c, j] = await upload(V, S1, '500,000.00 CR', 'test_v_s1.csv'); const s1 = j.id; [c2] = await post('statement/accept', { id: s1 });
    ok('B statement 1 (1–3 Sep) accepted', c === 200 && c2 === 200, j.message);
    let d = await day(V, '2026-09-01');
    ok('B 1 Sep: opening difference 50,000.00 not explained → insufficient', d.status_code === 'insufficient' && d.reconciliation.opening_difference_lkr === '50000.00', d.reasons);
    await login(C().prep);
    const [ca, ja] = await post('opening/propose', { account: V, book_key: '99980302.1', evidence: 'cheque 000201 not presented by 1 Sep (dummy)' });
    const [cb, jb] = await post('opening/propose', { account: V, book_key: '99980303.1', evidence: 'cheque 000202 not presented by 1 Sep (dummy)' });
    const [cc] = await post('opening/propose', { account: V, book_key: '99980302.1', evidence: 'again' });
    const [cd] = await post('opening/propose', { account: V, book_key: '99980304.1', evidence: 'after the first day' });
    const [ce] = await post('opening/decide', { id: ja.id, decision: 'accept' });
    ok('B both cheques proposed; the same row again refused (409); a row on/after the first day refused (400); the preparer cannot accept (403)', ca === 200 && cb === 200 && cc === 409 && cd === 400 && ce === 403, [ca, cb, cc, cd, ce]);
    await login(C().rev);
    await post('opening/decide', { id: ja.id, decision: 'accept' }); await post('opening/decide', { id: jb.id, decision: 'accept' });
    await login(C().prep);
    const lChg = await lineId(V, 'IB CEFT CHGS', s1); await login(C().prep);
    [c, j] = await post('correction/charges', { account: V, line_ids: [lChg] }); const cV = j.id;
    const [k1, kj1] = await post('mark/create', { account: V, side: 'book', ref: '99980305.1', mark: 'timing', expected_date: '2026-09-04', note: 'PV 90011 sent 2 Sep, clears 4 Sep (dummy)' });
    const [k2, kj2] = await post('mark/create', { account: V, side: 'book', ref: '99980304.1', mark: 'timing', expected_date: '2026-09-05', note: 'agent XYZ receipt in two parts (dummy)' });
    [c, j] = await get('api/suggest', { account: V, from: '2026-09-01', to: '2026-09-03' });
    const chq = (j.suggestions || []).find(s => s.line.description === 'CHQ 000201 TEST CHEQUE');
    await post('match/create', { account: V, line_ids: [chq.line.id], book_keys: ['99980302.1'], method: 'suggested' });
    const lX = await lineId(V, 'CEFT RECEIPT - TEST AGENT XYZ', s1); await login(C().prep);
    const [p0, pj0] = await post('match/create', { account: V, line_ids: [lX], book_keys: ['99980304.1'], partial: 1, expected_date: '2026-09-05', note: 'agent XYZ paid 50,000 of 90,000 (dummy)' });
    const [p1, pj1] = await post('match/create', { account: V, line_ids: [lX], book_keys: ['99980304.1'], partial: 1, expected_date: '2026-09-05', note: 'agent XYZ paid 50,000 of 90,000 (dummy)', expect_timing: [kj2.id] });
    ok('B partial clearing: refused while the timing classification is not confirmed (409); recorded once confirmed (rest −40,000.00)', p0 === 409 && p1 === 200, [pj0.message, pj1.message]);
    R.facts.B_ids = { s1, cV, kJ: kj1.id, kX: kj2.id, partial: pj1.id };
    const lK = await lineId(V, 'AH 9911 - TEST VENDOR K - 90012', s1);
    R.facts.B_ids.lK = lK;
    for (const [dd, diff, a, b] of [['2026-09-01', '49975.00', 'outstanding', 'outstanding'], ['2026-09-02', '-25025.00', 'outstanding', 'outstanding']]) {
      d = await day(V, dd); const st = Object.fromEntries(d.items.opening.map(o => [o.book_key, o.state]));
      ok(`B ${dd}: bank − book ${diff} explained to 0.00; cheques ${a}/${b}`, d.reconciliation.difference_lkr === diff && d.reconciliation.unexplained_lkr === '0.00' && d.reconciliation.opening_remaining === '0.00' && st['99980302.1'] === a && st['99980303.1'] === b && d.status_code === 'explained_pending', [d.reconciliation, st, d.status_label]);
    }
  }
  // ------------------------------------------------------------------ races: the same request from two sessions at one instant
  // race(at, user, path, data, n): log in as user (this tab's own session), wait until the clock reaches `at` (ms since
  // epoch, the same for both tabs), then send n requests one after the other; returns [{code, message, t0, t1}]
  async function race(at, user, path, data, n) {
    await login(user); await token();
    const out = [];
    while (Date.now() < at) await new Promise(r => setTimeout(r, Math.min(20, at - Date.now())));
    for (let i = 0; i < (n || 1); i++) { const t0 = Date.now(); const [c, j] = await post(path, data); out.push({ code: c, message: j.message, id: j.id, t0, t1: Date.now() }); }
    return out;
  }
  async function phaseB2() {   // after race 1 (duplicate matches)
    const I = C().ids, V = I.V, F = R.facts.B_ids;
    let d = await day(V, '2026-09-03'); let r = d.reconciliation;
    R.facts.B3 = { diff: r.difference_lkr, partial: r.partial_remainders, cleared: r.opening_items_cleared, outstanding: r.opening_items_outstanding, unexplained: r.unexplained_lkr, status: d.status_label };
    ok('B 3 Sep: 4,975.00 = cheque 000202 outstanding 30,000.00 − charge 25.00 − partial rest 40,000.00 + timing 15,000.00; cheque 000201 cleared', r.difference_lkr === '4975.00' && r.partial_remainders === '-40000.00' && r.opening_items_cleared === '-20000.00' && r.opening_items_outstanding === '30000.00' && r.unexplained_lkr === '0.00' && d.status_code === 'explained_pending', R.facts.B3);
    await login(C().prep); let [c, j] = await post('day/prepare', { account: V, date: '2026-09-03', note: 'r2 verification' }); F.r3 = j.id;
    await login(C().rev); [c, j] = await post('day/review', { id: F.r3, decision: 'reviewed' });
    d = await day(V, '2026-09-03');
    ok('B 3 Sep reviewed while the charge correction is only proposed → "Reviewed — ledger not yet corrected — 1 correction(s) awaiting approval (25.00)"', c === 200 && d.status_code === 'reviewed_pending' && /awaiting approval/.test(d.status_label), d.status_label);
    await login(C().rev); await post('correction/decide', { id: F.cV, decision: 'approve', note: 'dummy' });
    d = await day(V, '2026-09-03');
    ok('B approving the correction afterwards keeps the review current (posting progress only); now "approved but not posted"', d.review.state === 'reviewed' && d.review.postings_progressed && /1 approved but not posted \(25\.00\)/.test(d.status_label), [d.review, d.status_label]);
    const S2 = [HDR, ['04/09/2026', '04/09/2026', 'AH 9910 - TEST VENDOR J - 90011', '', '15,000.00', '', '506,975.00 CR'], ['04/09/2026', '04/09/2026', 'AH 9912 - TEST VENDOR L - 90013', '', '12,000.00', '', '494,975.00 CR'], ['05/09/2026', '05/09/2026', 'CEFT RECEIPT - TEST AGENT XYZ', '', '', '40,000.00', '534,975.00 CR']];
    await login(C().prep); [c, j] = await upload(V, S2, '521,975.00 CR', 'test_v_s2.csv'); F.s2 = j.id;
    ok('B statement 2 (4–5 Sep) kept as a draft (to be accepted by two sessions at once)', c === 200, j.message);
  }
  async function phaseB3() {   // after race 2 (duplicate acceptance)
    const I = C().ids, V = I.V, F = R.facts.B_ids;
    await login(C().prep);
    let [c, j] = await get('api/suggest', { account: V, from: '2026-09-01', to: '2026-09-05' });
    const sJ = (j.suggestions || []).find(s => s.line.description === 'AH 9910 - TEST VENDOR J - 90011'), sC = (j.suggestions || []).find(s => s.type === 'complete');
    F.lJ = sJ && sJ.line.id; F.lC = sC && sC.line.id;
    ok('B D9: the timing item PV 90011 is suggested (high) with "accepting clears the classification"; the 5 Sep line completes the partial match', sJ && sJ.confidence === 'high' && sJ.timing_ids[0] == F.kJ && sJ.why.some(w => w.includes('timing item')) && sC && sC.match_id == F.partial, [sJ, sC]);
    const [c0, j0] = await post('match/create', { account: V, line_ids: [sJ.line.id], book_keys: ['99980305.1'], method: 'suggested' });
    [c, j] = await post('match/create', { account: V, line_ids: [sJ.line.id], book_keys: ['99980305.1'], method: 'suggested', expect_timing: sJ.timing_ids }); F.mJ = j.id;
    ok('B D9: matching without confirming the timing classification is refused (409); accepted with it → cleared by the match', c0 === 409 && c === 200 && /timing classification/.test(j.message), [j0.message, j.message]);
    [c, j] = await post('match/undo', { id: F.mJ, reason: 'verification: undo restores the timing item' });
    let d = await day(V, '2026-09-04');
    const t = (d.items.timing || []).find(x => x.key === '99980305.1');
    R.facts.B_restored = t ? t.mark : null;
    ok('B undo restores the timing classification as first recorded (same classification, classifier, time, expected 2026-09-04)', c === 200 && /restored/.test(j.message) && t && t.mark.id == F.kJ && t.mark.expected_date === '2026-09-04' && t.mark.created_by === C().prep, R.facts.B_restored);
    await login(C().prep);
    [c, j] = await post('match/create', { account: V, line_ids: [sJ.line.id], book_keys: ['99980305.1'], method: 'suggested', expect_timing: [F.kJ] }); F.mJ = j.id;
    [c, j] = await get('api/open', { account: V, from: '2026-09-01', to: '2026-09-05' });
    const hist = (j.mark_history || []).filter(e => e.mark_id == F.kJ).map(e => e.event).reverse();
    R.facts.B_history = hist;
    ok('B reopening: matched again; the history reads cleared → restored → cleared', hist.join(',') === 'cleared_by_match,restored_by_undo,cleared_by_match', hist);
    F.lL = await lineId(V, 'AH 9912 - TEST VENDOR L - 90013', F.s2);
  }
  async function phaseB4() {   // after race 3 (match vs classify)
    const I = C().ids, V = I.V, F = R.facts.B_ids;
    await login(C().prep);
    let [c, j] = await get('api/open', { account: V, from: '2026-09-01', to: '2026-09-05' });
    const mk = (j.marks || []).find(k => k.book_key === '99980307.1');
    if (mk) { await post('mark/clear', { id: mk.id, reason: 'race test: match instead' }); await post('match/create', { account: V, line_ids: [F.lL], book_keys: ['99980307.1'] }); }
    [c, j] = await post('day/prepare', { account: V, date: '2026-09-04', note: 'r2 verification' }); const r4a = j.id;
    [c, j] = await get('api/open', { account: V, from: '2026-09-01', to: '2026-09-05' });
    const mL = (j.matches || []).find(m => m.items.some(i => i.book_key === '99980307.1'));
    await post('match/undo', { id: mL.id, reason: 'verification: change after preparation' });
    await login(C().rev); [c, j] = await post('day/review', { id: r4a, decision: 'reviewed' });
    ok('B the reviewer cannot review a record whose data changed after it was prepared (409); nothing reviewed', c === 409 && /changed after this record was prepared/.test(j.message), j.message);
    await login(C().prep); await post('match/create', { account: V, line_ids: [F.lL], book_keys: ['99980307.1'] });
    [c, j] = await post('day/prepare', { account: V, date: '2026-09-04', note: 'prepared again' }); F.r4 = j.id;
    ok('B prepared again (record ' + F.r4 + ', to be reviewed by two sessions at once)', c === 200, j.message);
  }
  async function phaseB5() {   // after race 4 (two reviewers): check, then the ledger row is changed by SQL (outside)
    const V = C().ids.V, F = R.facts.B_ids;
    const d = await day(V, '2026-09-04');
    ok('B 4 Sep reviewed once; review current', d.review.state === 'reviewed' && d.review.record_id == F.r4, d.review);
  }
  async function phaseB6(expectChanged) {   // called after the dummy ledger row was edited (true) and after it was put back (false)
    const V = C().ids.V, F = R.facts.B_ids;
    const d = await day(V, '2026-09-04');
    if (expectChanged) {
      await login(C().view); const [c, j] = await get('api/day_record', { id: F.r4 });
      R.facts.B_changed = { review: d.review.label, status: d.status_label, snapshot_status: j.record && j.record.status_label };
      return ok('B a matched ledger row changed after review → "changed since review: ledger rows" (re-review needed); day insufficient; the reviewed snapshot unchanged', d.review.state === 'reviewed_outdated' && d.review.changed_labels.includes('ledger rows (book side)') && d.status_code === 'insufficient' && j.record.status_code === 'explained_pending', R.facts.B_changed);
    }
    return ok('B the dummy row put back exactly → the review is current again', d.review.state === 'reviewed', d.review);
  }
  async function phaseB7() {
    const I = C().ids, V = I.V, F = R.facts.B_ids;
    await login(C().prep);
    let [c, j] = await post('match/create', { account: V, line_ids: [F.lC], book_keys: [], supersedes: F.partial, method: 'complete' }); const pc = j.id;
    let d3 = await day(V, '2026-09-03');
    ok('B the partial match is completed by the 5 Sep line; the reviewed 3 Sep stays current with the same figures', c === 200 && d3.review.state === 'reviewed' && d3.reconciliation.difference_lkr === '4975.00' && d3.reconciliation.unexplained_lkr === '0.00', [j.message, d3.review]);
    await login(C().prep); [c, j] = await post('match/undo', { id: pc, reason: 'verification: undo the completion' });
    const [c2, j2] = await post('match/create', { account: V, line_ids: [F.lC], book_keys: [], supersedes: F.partial });
    ok('B undoing the completion makes the partial match active again; completed again', c === 200 && /active again/.test(j.message) && c2 === 200, [j.message, j2.message]);
    for (const [dd, a, out, diff] of [['2026-09-01', 'outstanding', '50000.00', '49975.00'], ['2026-09-02', 'outstanding', '50000.00', '-25025.00'], ['2026-09-03', 'cleared', '30000.00', '4975.00'], ['2026-09-04', 'cleared', '30000.00', '-10025.00'], ['2026-09-05', 'cleared', '30000.00', '29975.00']]) {
      const d = await day(V, dd); const r = d.reconciliation; const st = Object.fromEntries(d.items.opening.map(o => [o.book_key, o.state]));
      R.facts['B_open_' + dd] = [r.difference_lkr, r.opening_remaining, r.opening_items_outstanding, st['99980302.1'], st['99980303.1'], r.unexplained_lkr, d.status_code];
      ok(`B opening items ${dd}: cheque 000202 outstanding, cheque 000201 ${a}; bank − book ${diff} explained to 0.00`, r.difference_lkr === diff && r.opening_remaining === '0.00' && r.opening_items_outstanding === out && st['99980302.1'] === a && st['99980303.1'] === 'outstanding' && r.unexplained_lkr === '0.00', R.facts['B_open_' + dd]);
    }
    await login(C().prep); [c, j] = await post('day/prepare', { account: V, date: '2026-09-05', note: 'r2 verification' }); const r5 = j.id;
    await login(C().rev); await post('day/review', { id: r5, decision: 'reviewed' });
    await login(C().view); [c, j] = await get('api/days', { account: V });
    const rec = (j.days || []).find(x => x.id == r5) || {};
    R.facts.B_r5 = { pending_kept: rec.pending && rec.pending.summary, now: rec.live };
    ok('B the reviewed 5 Sep record keeps the pending posting (1 approved, not posted: 25.00) and shows it now', rec.pending && rec.pending.approved.count === 1 && rec.live.state === 'reviewed' && /1 approved but not posted/.test(rec.live.pending_now || ''), R.facts.B_r5);
    await login(C().prep); [c, j] = await post('correction/posted', { id: F.cV, ledger_id: '99980301' });
    ok('B recording a posting with an entry that does not match the approved lines is refused (409); nothing recorded', c === 409 && /does not match/.test(j.message), j.message);
    const S3 = [HDR, ['06/09/2026', '06/09/2026', 'TEST INTEREST CREDIT', '', '', '1,000.00', '535,975.00 CR']];
    [c, j] = await upload(V, S3, '534,975.00 CR', 'test_v_s3.csv'); F.s3 = j.id;
    F.l3 = await lineId(V, 'TEST INTEREST CREDIT', F.s3);
  }
  async function phaseB8() {   // after the conflicting edit (two sessions) and race 5 (two drafts accepted)
    const I = C().ids, V = I.V, F = R.facts.B_ids;
    await login(C().admin || C().prep);
    let [c, j] = await post('opening/propose', { account: V, book_key: '99980301.1', evidence: 'four-eyes test (dummy)' }); const oX = j.id;
    await login(C().prep); const [c2] = await post('opening/decide', { id: oX, decision: 'accept' });
    await login(C().rev); const [c3] = await post('opening/decide', { id: oX, decision: 'withdraw', note: 'not mine' });
    const [c4] = await post('match/create', { account: V, line_ids: [F.l3], book_keys: ['99980301.1'] });
    await login(C().prep); const [c5] = await post('opening/decide', { id: oX, decision: 'withdraw', note: 'four-eyes test withdrawn' });
    ok('B four eyes: the proposer cannot accept (403); the reviewer cannot withdraw (403) or match (403); the proposer withdraws (200)', c === 200 && c2 === 403 && c3 === 403 && c4 === 403 && c5 === 200, [c, c2, c3, c4, c5]);
    await login(C().view);
    const [v1] = await post('opening/propose', { account: V, book_key: '99980301.1' }); const [v2] = await post('match/explain', { id: 1, note: 'not allowed' }); const [v3] = await get('api/opening_candidates', { account: V });
    ok('B view-only: cannot propose (403) or explain (403); can read candidates (200)', v1 === 403 && v2 === 403 && v3 === 200, [v1, v2, v3]);
    for (const u of (C().depts || [])) {
      await login(u); const [d1] = await get('api/accounts'); const [d2] = await get('api/opening_candidates', { account: V }); const [d3] = await post('match/explain', { id: 1, note: 'x' });
      ok(u + ': no access (403 on data, candidates and explanations)', d1 === 403 && d2 === 403 && d3 === 403, [d1, d2, d3]);
    }
  }
  return { R, log: R.log, facts: R.facts, login, get, post, day, upload, lineId, phaseA, phaseB, phaseB2, phaseB3, phaseB4, phaseB5, phaseB6, phaseB7, phaseB8, phaseC, race, token, csv, HDR, who: () => who };
})();
'loaded';
