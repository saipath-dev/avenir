// tf-bankrec-r1 — the reconciliation flow on the ISOLATED TEST INSTANCE (http://localhost/avenir_rv/, database
// avenir_restore_verify_20261011) with dummy data only. Runs in a browser-pane tab of that site. Test logins only.
window.__rv = { log: [], state: 'running' };
(async () => {
  const R = window.__rv, BASE = '/avenir_rv/', API = BASE + 'accounts/daily_bank_rec/';
  const PW = 'Colombo1234';
  const ok = (name, cond, detail) => { R.log.push([cond ? 'ok' : 'FAIL', name, cond ? '' : JSON.stringify(detail ?? '').slice(0, 300)]); return cond; };
  let tok = null;
  async function login(user) {
    await fetch(BASE + 'logout', { credentials: 'same-origin' }).catch(() => 0);
    const h = await fetch(BASE, { credentials: 'same-origin' }).then(r => r.text());
    const m = h.match(/name="([^"]+)" value="([0-9a-f]{32})"/);
    const fd = new FormData(); fd.append('username', user); fd.append('password', PW); if (m) fd.append(m[1], m[2]);
    for (let i = 0; i < 6; i++) {
      const j = await fetch(BASE + 'login', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }).then(r => r.json()).catch(() => ({}));
      if (j.status === 429) { await new Promise(r => setTimeout(r, ((+j.retry_after || 5) + 1) * 1000)); continue; }
      tok = null; return j.status === 200;
    }
    return false;
  }
  async function token() {
    if (tok) return tok;
    const h = await fetch(API, { credentials: 'same-origin' }).then(r => r.text());
    const m = h.match(/const csrf = \{ name: "([^"]+)", hash: "([0-9a-f]+)"/);
    tok = m ? { name: m[1], hash: m[2] } : null; return tok;
  }
  async function get(path, params) {
    const r = await fetch(API + path + (params ? '?' + new URLSearchParams(params) : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin', redirect: 'manual' });
    let j = {}; try { j = await r.json(); } catch (e) { }
    return [r.status || (r.type === 'opaqueredirect' ? 302 : 0), j];
  }
  async function post(path, data, opts) {
    const fd = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData)) for (const [k, v] of Object.entries(data || {})) fd.append(k, typeof v === 'object' ? JSON.stringify(v) : v);
    if (!(opts && opts.noCsrf)) { const t = await token(); if (t) fd.set(t.name, t.hash); }
    const r = await fetch(API + path, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin', redirect: 'manual' });
    let j = {}; try { j = await r.json(); } catch (e) { }
    if (j.csrf && tok) tok.hash = j.csrf;
    return [r.status || 302, j];
  }
  const csv = rows => new Blob([rows.map(r => r.map(c => /[",\n]/.test(c) ? '"' + c.replace(/"/g, '""') + '"' : c).join(',')).join('\n')], { type: 'text/csv' });
  try {
    const ids = window.__rvIds;  // {LKR, USD, JPY} set before running
    const LKR = [['Txn Date', 'Value Date', 'Description', 'Cheque No', 'Debit', 'Credit', 'Balance'],
      ['01/09/2026', '01/09/2026', 'AH 9901 - TEST HOTEL A - 90001', '', '50,000.00', '', '950,000.00 CR'], ['01/09/2026', '01/09/2026', 'IB CEFT CHGS', '', '25.00', '', '949,975.00 CR'],
      ['02/09/2026', '02/09/2026', 'CHQ 000123 TEST CHEQUE', '000123', '150,000.00', '', '799,975.00 CR'], ['02/09/2026', '02/09/2026', 'AH 9902 - TEST SUPPLIER B - 90002', '', '50,000.00', '', '749,975.00 CR'],
      ['02/09/2026', '02/09/2026', 'IB CEFT CHGS', '', '25.00', '', '749,950.00 CR'], ['03/09/2026', '03/09/2026', 'TRF FROM TEST-USD-0002', '', '', '3,000,000.00', '3,749,950.00 CR'],
      ['03/09/2026', '03/09/2026', 'FUND TRANSFER FEE', '', '1,000.00', '', '3,748,950.00 CR'], ['03/09/2026', '03/09/2026', 'AH 9903 - TEST GUIDE C - 90003', '', '75,000.00', '', '3,673,950.00 CR'],
      ['04/09/2026', '04/09/2026', 'CASH DEPOSIT', '', '', '235,000.00', '3,908,950.00 CR'], ['05/09/2026', '05/09/2026', 'AH 9905 - TEST JEEP D - 90005', '', '12,344.00', '', '3,896,606.00 CR'],
      ['05/09/2026', '05/09/2026', 'GPRS RENTAL CHARGES', '', '3,500.00', '', '3,893,106.00 CR']];
    // ---- departments without access
    for (const u of ['TestHotelRes', 'TestTourRes', 'TestTransport', 'TestOperations']) {
      ok('login ' + u, await login(u));
      const [c1] = await get('api/accounts'); const [c2] = await post('mapping/propose', { account: ids.LKR, rec_start: '2026-09-01' });
      ok(u + ': no access (data 403, change 403)', c1 === 403 && c2 === 403, [c1, c2]);
    }
    ok('login TestBankAuth', await login('TestBankAuth'));
    let [c, j] = await get('api/accounts');
    ok('Bank Authoriser: view only — sees the accounts, masked', c === 200 && j.accounts.every(a => a.account_no_masked.includes('•')), c);
    [c] = await post('mapping/propose', { account: ids.LKR, rec_start: '2026-09-01' });
    ok('Bank Authoriser cannot propose (403)', c === 403, c);
    // ---- preparer
    ok('login TestAccounts', await login('TestAccounts'));
    [c] = await post('mapping/propose', { account: ids.LKR, rec_start: '2026-09-01' }, { noCsrf: true });
    ok('a change without the CSRF token is refused (403)', c === 403, c);
    const maps = {};
    for (const cur of ['LKR', 'USD', 'JPY']) { [c, j] = await post('mapping/propose', { account: ids[cur], rec_start: '2026-09-01', note: 'test bed' }); maps[cur] = j.id; ok(cur + ': mapping proposed by Accounts', c === 200, j.message); }
    [c] = await post('mapping/decide', { map_id: maps.LKR, decision: 'confirm' });
    ok('Accounts cannot confirm (403)', c === 403, c);
    for (const [cur, d, r] of [['USD', '2026-09-01', '300.00'], ['USD', '2026-09-02', '300.50'], ['USD', '2026-09-03', '301.00'], ['USD', '2026-09-04', '301.50'], ['USD', '2026-09-05', '302.00'], ['JPY', '2026-09-01', '2.00'], ['JPY', '2026-09-04', '2.02'], ['JPY', '2026-09-05', '2.03']])
      await post('rate/save', { currency: cur, rate_date: d, rate: r, source: 'TEST rate (dummy)' });
    // ---- reviewer confirms
    ok('login TestFinApprover', await login('TestFinApprover'));
    for (const cur of ['LKR', 'USD', 'JPY']) { [c, j] = await post('mapping/decide', { map_id: maps[cur], decision: 'confirm', note: 'checked' }); ok(cur + ': mapping confirmed by the Management Approver', c === 200, j.message); }
    // ---- statements
    ok('login TestAccounts', await login('TestAccounts'));
    let fd = new FormData(); fd.append('account', ids.LKR); fd.append('source', 'upload'); fd.append('opening_balance', '1,000,000.00 CR'); fd.append('file', csv(LKR), 'combank_test_sep.csv');
    [c, j] = await post('statement/save', fd); const sA = j.id;
    ok('LKR statement uploaded and kept as a draft', c === 200 && sA, j.message);
    [c, j] = await post('statement/accept', { id: sA }); ok('LKR statement accepted', c === 200, j.message);
    [c, j] = await post('statement/save', { account: ids.USD, source: 'manual', opening_balance: '50,000.00', closing_balance: '44,985.00', period_from: '2026-09-01', period_to: '2026-09-05', lines: [
      { date: '2026-09-02', description: 'TEST AGENT REMITTANCE ABC TRAVEL', money_in: '5,000.00', balance: '55,000.00' }, { date: '2026-09-03', description: 'TRF TO TEST-LKR-0001', money_out: '10,000.00', balance: '45,000.00' },
      { date: '2026-09-05', description: 'TRF CHGS USD', money_out: '15.00', balance: '44,985.00' }] });
    const sU = j.id; [c, j] = await post('statement/accept', { id: sU }); ok('USD statement typed in and accepted', c === 200, j.message);
    fd = new FormData(); fd.append('account', ids.JPY); fd.append('source', 'upload'); fd.append('opening_balance', '1000000'); fd.append('closing_balance', '1,200,000'); fd.append('period_from', '2026-09-01'); fd.append('period_to', '2026-09-05'); fd.append('date_format', 'Y-m-d');
    fd.append('file', csv([['Date', 'Narrative', 'Amount'], ['2026-09-04', 'TEST JP AGENT YAMADA TRAVEL', '200000']]), 'jpy_test.csv');
    [c, j] = await post('statement/save', fd); const sJ = j.id; [c, j] = await post('statement/accept', { id: sJ }); ok('JPY statement uploaded and accepted', c === 200, j.message);
    [c, j] = await get('api/day', { account: ids.LKR, date: '2026-09-05' });
    ok('LKR before matching: bank 3,893,106.00, book 3,547,655.00, opening difference 150,000.00 → "Unreconciled — insufficient data"', j.day.bank.closing === '3893106.00' && j.day.book.closing === '3547655.00' && j.day.reconciliation.opening_difference_lkr === '150000.00' && j.day.status_code === 'insufficient', j.day.reconciliation);
    // ---- matching
    [c, j] = await get('api/suggest', { account: ids.LKR, from: '2026-09-01', to: '2026-09-05' });
    const sug = Object.fromEntries((j.suggestions || []).map(s => [s.line.description, s]));
    ok('suggestions found for PV 90001, PV 90002 (two rows), the cheque of 30 Aug and the transfer', ['AH 9901 - TEST HOTEL A - 90001', 'AH 9902 - TEST SUPPLIER B - 90002', 'CHQ 000123 TEST CHEQUE', 'TRF FROM TEST-USD-0002'].every(k => sug[k]) && sug['CHQ 000123 TEST CHEQUE'].book[0].key === '99980002.1', Object.keys(sug));
    for (const k of ['AH 9901 - TEST HOTEL A - 90001', 'AH 9902 - TEST SUPPLIER B - 90002', 'CHQ 000123 TEST CHEQUE', 'TRF FROM TEST-USD-0002']) { [c, j] = await post('match/create', { account: ids.LKR, line_ids: [sug[k].line.id], book_keys: sug[k].book.map(b => b.key), method: 'suggested' }); ok('matched: ' + k, c === 200, j.message); }
    const [, st] = await get('api/statement', { id: sA }); const L = Object.fromEntries(st.lines.map(l => [l.description, l.id]));
    [c, j] = await post('match/create', { account: ids.LKR, line_ids: [L['AH 9905 - TEST JEEP D - 90005']], book_keys: ['99980010.1'], diff_reason: 'rounding' }); ok('matched with the 1.00 rounding difference', c === 200, j.message);
    [c, j] = await post('match/create', { account: ids.LKR, line_ids: [L['AH 9903 - TEST GUIDE C - 90003']], book_keys: ['99980007.1'], note: 'first of two identical rows' }); ok('matched the 75,000 payment', c === 200, j.message);
    [c, j] = await post('mark/create', { account: ids.LKR, side: 'book', ref: '99980008.1', mark: 'duplicate', related: '99980007.1', note: 'same PV 90003 and amount — to investigate' }); ok('second 75,000 row classified duplicate-looking', c === 200, j.message);
    [c, j] = await post('mark/create', { account: ids.LKR, side: 'book', ref: '99980009.1', mark: 'timing', expected_date: '2026-09-08', note: 'PV 90004 paid after 5 Sep' }); ok('40,000 row classified timing', c === 200, j.message);
    const charges = st.lines.filter(l => l.category === 'charge').map(l => l.id);
    [c, j] = await post('correction/charges', { account: ids.LKR, line_ids: charges }); const cC = j.id; ok('4 bank charges proposed as one correction (4,550.00)', c === 200, j.message);
    [c, j] = await post('correction/propose', { account: ids.LKR, kind: 'unbooked_receipt', description: 'Cash deposit 4 Sep not in the ledger (test bed)', entry_date: '2026-09-04', lines: [{ ledger_code: '1-6-8-29901', dr: '235000', cr: '' }, { ledger_code: '2-9-99-29909', dr: '', cr: '235000' }], line_ids: [L['CASH DEPOSIT']] }); const cR = j.id; ok('cash deposit proposed as a correction', c === 200, j.message);
    [c, j] = await post('correction/decide', { id: cC, decision: 'approve' }); ok('Accounts cannot approve corrections (403)', c === 403, c);
    // transfer + USD/JPY
    [c, j] = await get('api/rates', { from: '2026-09-01', to: '2026-09-05' }); const ts = j.transfer_suggestions || [];
    ok('the USD → LKR transfer is suggested', ts.length === 1, ts);
    if (ts[0]) { [c, j] = await post('transfer/create', { from_line: ts[0].from.line_id, to_line: ts[0].to.line_id, note: 'test bed' }); ok('transfer recorded: rate obtained 300, day rate 301, difference −10,000.00', c === 200 && /difference -10,000\.00/.test(j.message), j.message); }
    const [, su] = await get('api/statement', { id: sU }); const U = Object.fromEntries(su.lines.map(l => [l.description, l.id]));
    await post('match/create', { account: ids.USD, line_ids: [U['TEST AGENT REMITTANCE ABC TRAVEL']], book_keys: ['99980102.1'], diff_reason: 'exchange rate' });
    await post('match/create', { account: ids.USD, line_ids: [U['TRF TO TEST-LKR-0001']], book_keys: ['99980103.1'], diff_reason: 'exchange rate' });
    [c, j] = await post('correction/charges', { account: ids.USD, line_ids: [U['TRF CHGS USD']] }); const cU = j.id;
    const [, sj] = await get('api/statement', { id: sJ }); [c, j] = await post('match/create', { account: ids.JPY, line_ids: [sj.lines[0].id], book_keys: ['99980202.1'] }); ok('JPY line matched exactly', c === 200, j.message);
    // ---- reviewer approves the corrections
    ok('login TestFinApprover', await login('TestFinApprover'));
    for (const id of [cC, cR, cU]) { [c, j] = await post('correction/decide', { id, decision: 'approve', note: 'test bed approval' }); ok('correction ' + id + ' approved; not posted by the system', c === 200 && /does not post/.test(j.message), j.message); }
    // ---- results per account
    for (const [cur, diff, extra] of [['LKR', '345451.00', r => r.bank_items_not_in_book === '230450.00' && r.book_items_not_on_statement === '115000.00' && r.match_differences === '1.00' && r.opening_remaining === '0.00'],
      ['USD', '85470.00', r => r.match_differences === '-7500.00' && r.bank_items_not_in_book === '-4530.00' && r.revaluation === '97500.00'], ['JPY', '32000.00', r => r.revaluation === '32000.00']]) {
      [c, j] = await get('api/day', { account: ids[cur], date: '2026-09-05' }); const r = j.day.reconciliation;
      ok(cur + ' 5 Sep: difference ' + diff + ' fully explained → "' + j.day.status_label + '"', r.difference_lkr === diff && r.unexplained_lkr === '0.00' && j.day.status_code === 'explained' && extra(r), [j.day.status_label, r, j.day.reasons]);
    }
    // ---- daily record, four eyes
    ok('login TestAccounts', await login('TestAccounts'));
    [c, j] = await post('day/prepare', { account: ids.LKR, date: '2026-09-05', note: 'test bed' }); const d1 = j.id; ok('Accounts prepares the LKR daily record', c === 200, j.message);
    [c] = await post('day/review', { id: d1, decision: 'reviewed' }); ok('Accounts cannot review it (403)', c === 403, c);
    ok('login TestFinApprover', await login('TestFinApprover'));
    [c, j] = await post('day/review', { id: d1, decision: 'reviewed', note: 'ok' }); ok('the Management Approver reviews it', c === 200, j.message);
    [c, j] = await get('api/day', { account: ids.LKR, date: '2026-09-05' }); ok('LKR 5 Sep → "Reconciled — reviewed"', j.day.status_code === 'reviewed', j.day.status_label);
    [c, j] = await post('statement/void', { id: sA, reason: 'should be refused while matched' }); ok('an accepted statement with matches cannot be voided (409)', c === 409, [c, j.message]);
    R.ids = { sA, sU, sJ, cC, cR, cU, d1 };
    R.state = 'done';
  } catch (e) { R.state = 'error'; R.error = String(e && e.stack || e); }
})();
'started';
