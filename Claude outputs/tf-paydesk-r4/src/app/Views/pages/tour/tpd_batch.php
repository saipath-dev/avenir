<?= view('layouts/head') ?>
<?php
/**
 * tf-paydesk-r2 — a supplier payment batch (tour/tour_payment_dashboard/batch/{id}):
 *   the accountant's bank payment page (the payment-date rate, the LKR calculated, charges, total debited, reference) and
 *   the bank approver's final approval page (the rate of the actual payment date, locked at Paid). The approval figures
 *   stay visible beside the payment-date figures throughout.
 * tf-paydesk-r4 — the bank charges: allocated per tour line (pro rata to the final LKR, to the cent), posted once at Paid to the
 *   mapped Bank Charges account against the paying account, and the ledger read back and reconciled to the bank debit.
 */
$P = $tpd_perms ?? [];
?>
<style>
    .tpb { --p:#842484; --p2:#8f2a8d; --soft:#f3e7f3; --ink:#2c3e50; --muted:#6c757d; --line:#e4e8ee; color:var(--ink); }
    .tpb .btn-p { background:var(--p); border-color:var(--p); color:#fff; } .tpb .btn-p:hover { background:var(--p2); color:#fff; }
    .tpb .btn-outline-p { color:var(--p); border-color:var(--p); } .tpb .btn-outline-p:hover { background:var(--soft); color:var(--p); }
    .tpb .card { border:1px solid var(--line); border-radius:14px; } .tpb .card h6 { font-weight:700; color:var(--p); }
    .tpb .num { font-variant-numeric: tabular-nums; } .tpb .muted { color:var(--muted); } .tpb .mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
    .tpb .pill { display:inline-block; border-radius:999px; padding:2px 9px; font-size:11.5px; font-weight:700; white-space:nowrap; }
    .tpb .pill.green { background:#e4f4ea; color:#1f8a4c; } .tpb .pill.amber { background:#fff3d6; color:#9a6400; } .tpb .pill.red { background:#fde8e8; color:#b42318; } .tpb .pill.grey { background:#eef0f3; color:#6c757d; }
    .tpb .pill.violet { background:#ece6f7; color:#5b3fa6; } .tpb .pill.teal { background:#dff5f2; color:#0b6e63; } .tpb .pill.olive { background:#eef3dc; color:#5c6b12; }
    .tpb .fig { display:flex; justify-content:space-between; gap:12px; padding:7px 0; border-bottom:1px dashed var(--line); font-size:13px; } .tpb .fig:last-child { border-bottom:0; }
    .tpb .fig b { font-size:14px; } .tpb .fig.big b { font-size:18px; color:var(--p); } .tpb .fig .k { color:var(--muted); }
    .tpb .fx { background:linear-gradient(135deg,#0f6b55,#1f8a4c); color:#fff; border-radius:14px; padding:14px 16px; }
    .tpb .fx input { font-weight:700; font-size:1.1rem; } .tpb .fx .lbl { font-size:11.5px; opacity:.85; text-transform:uppercase; letter-spacing:.04em; }
    .tpb .steps { display:flex; gap:6px; flex-wrap:wrap; } .tpb .steps span { font-size:11.5px; padding:3px 9px; border-radius:999px; background:#eef0f3; color:var(--muted); } .tpb .steps span.done { background:#e4f4ea; color:#1f8a4c; } .tpb .steps span.now { background:var(--soft); color:var(--p); font-weight:700; }
    .tpb table.items { font-size:12.5px; } .tpb .lock { color:#1f8a4c; }
    .tpb .diff-pos { color:#b42318; } .tpb .diff-neg { color:#1f8a4c; }
    @media (max-width: 640px) { .tpb .fig { flex-direction:column; gap:2px; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content"><div class="container-fluid tpb" id="bp">
        <div class="text-center muted py-5">Loading batch <?= esc($batch_no ?? '') ?>…</div>
    </div></div></div>
</div>

<!-- Remittance advice -->
<div class="modal fade tpb" id="remitModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-mail-send-line me-1" style="color:var(--p);"></i>Remittance advice to the supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="bg-light rounded p-3 mb-3 small" id="rm_summary"></div>
        <div class="row g-2">
            <div class="col-md-7"><label class="form-label fw-semibold small">To <span class="text-danger">*</span></label><input type="email" class="form-control" id="rm_to"><div class="form-text" id="rm_to_note"></div></div>
            <div class="col-md-5"><label class="form-label fw-semibold small">Cc</label><input type="text" class="form-control" id="rm_cc" placeholder="optional"></div>
            <div class="col-12"><label class="form-label fw-semibold small">Subject</label><input type="text" class="form-control" id="rm_subject"></div>
            <div class="col-12"><label class="form-label fw-semibold small">Message</label><div class="border rounded p-2 bg-white" id="rm_body" contenteditable="true" style="min-height:140px; font-size:13px;"></div></div>
            <div class="col-12 small" id="rm_attach"></div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-p" id="rm_send" onclick="submitRemittance()"><i class="ri-send-plane-line me-1"></i>Send to supplier</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
const TPD_URL = '<?= base_url('tour/tour_payment_dashboard/') ?>';
const BATCH_ID = <?= (int) $batch_id ?>;
const P = <?= json_encode($P) ?>;
let _d = null, _date = null, _charges = '';
const esc = s => String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money2 = n => parseFloat(n || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const num = v => parseFloat(String(v === null || v === undefined ? '' : v).replace(/,/g, '')) || 0;
const dmy = d => { if (!d) return '—'; const dt = new Date(String(d).slice(0, 10) + 'T00:00:00'); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}); };
const dmyt = d => { if (!d) return '—'; const dt = new Date(String(d).replace(' ', 'T')); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}) + ' ' + dt.toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit'}); };
const STAGE_COLOR = {issued:'violet', initiated:'teal', paid:'olive', notified:'green', cancelled:'red'};
const diffCls = v => v > 0.004 ? 'diff-pos' : (v < -0.004 ? 'diff-neg' : '');
const diffTxt = v => v === null || v === undefined ? '—' : (v > 0.004 ? '+ ' + money2(v) + ' loss' : (v < -0.004 ? '− ' + money2(Math.abs(v)) + ' gain' : '0.00'));
function toast(ok, msg) { Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? 'Done' : 'Not done', text: msg || '', timer: ok ? 3600 : undefined, showConfirmButton: !ok }); }
async function postJson(path, body) {
    const fd = body instanceof FormData ? body : Object.entries(body || {}).reduce((f, [k, v]) => { if (Array.isArray(v)) v.forEach(x => f.append(k + '[]', x)); else if (v !== undefined && v !== null) f.append(k, v); return f; }, new FormData());
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    try { const r = await fetch(TPD_URL + path, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }); return await r.json(); } catch (e) { return { status: 0, message: 'Network error. Please try again.' }; }
}
async function postJsonBody(path, obj) {
    try { const r = await fetch(TPD_URL + path, { method: 'POST', body: JSON.stringify(obj || {}), headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' } }); return await r.json(); } catch (e) { return { status: 0, message: 'Network error. Please try again.' }; }
}
async function load(date) {
    const j = await fetch(`${TPD_URL}batch_data/${BATCH_ID}${date ? '?date=' + encodeURIComponent(date) : ''}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).catch(() => ({}));
    if (!j || j.status !== 200) { document.getElementById('bp').innerHTML = `<div class="alert alert-danger">${esc((j && j.message) || 'Could not load the batch.')}</div>`; return; }
    _d = j.data; _date = _d.for_date; render();
}
function render() {
    const d = _d, b = d.batch, v = d.voucher, sb = d.supplier, init = d.initiation, app = d.approval, rem = d.remittance;
    const stage = d.stage;
    const order = ['issued', 'initiated', 'paid', 'notified'];
    const si = order.indexOf(stage);
    const steps = `<div class="steps">${[['Approved for payment', true], ['Batch created · voucher issued', si >= 0], ['Bank payment initiated', si >= 1], ['Paid — final bank approval', si >= 2], ['Supplier notified', si >= 3]].map(([t, done], i) => `<span class="${done ? 'done' : (i === si + 1 ? 'now' : '')}">${t}</span>`).join('')}</div>`;
    const acct = d.bank_account;
    const bankSt = sb.status === 'verified' ? '<span class="pill green">Verified</span>' : (sb.status === 'changed' ? '<span class="pill red">CHANGED since verification</span>' : '<span class="pill amber">Not verified</span>');
    // the figures of the step at hand: the day's rate, or the initiation's / the lock's
    const isUsd = d.is_usd;
    let rateRow = null, figs = null, rateNote = '';
    if (app) { figs = d.final; rateRow = { rate: app.usd_rate, src: app.rate_source, date: app.paid_date }; }
    else if (stage === 'initiated' && !P.bank_approver) { figs = d.at_initiation; rateRow = { rate: init.usd_rate, src: init.rate_source, date: init.payment_date }; }
    else { figs = d.at_date; rateRow = { rate: d.rate_for_date ? d.rate_for_date.rate : null, src: d.rate_for_date ? 'daily' : null, date: d.for_date, by: d.rate_for_date ? d.rate_for_date.by : null }; }
    const canInit = P.accountant && stage === 'issued';
    const canPay = P.bank_approver && stage === 'initiated';
    const editing = canInit || canPay;
    const rateMissing = isUsd && !rateRow.rate;
    const charges = app ? app.charges : (init && !canPay ? init.charges : (canPay && _charges === '' && init ? init.charges : _charges));
    const actual = figs && figs.actual_lkr_total !== null ? figs.actual_lkr_total : null;
    const total = actual !== null ? actual + num(charges) : null;
    const fx = figs ? figs.fx_diff : null;
    const shares = app ? Object.fromEntries((d.charge_shares || []).map(c => [String(c.pr_id), c.share])) : allocateShares(d.items.map((it, i) => ({ key: String(it.pr_id), basis: figs && figs.lines[i] && figs.lines[i].actual_lkr !== null ? figs.lines[i].actual_lkr : (it.approval_lkr || 0) })), num(charges));
    const items = d.items.map((it, i) => {
        const line = figs ? figs.lines[i] : null;
        const sh = shares[String(it.pr_id)];
        return `<tr><td><a href="${TPD_URL}?manual_id=${encodeURIComponent(it.manual_id)}" target="_blank"><b>${esc(it.manual_id)}</b></a><br><span class="muted">${esc(it.guest)}</span></td><td>${esc(it.kind)}${it.service_dates.length ? '<br><span class="muted">' + it.service_dates.map(dmy).join(', ') + '</span>' : ''}</td><td>${esc(it.invoice_no || '—')}${it.booking_ref ? '<br><span class="muted">' + esc(it.booking_ref) + '</span>' : ''}</td>
            <td class="text-end num">${it.currency === 'USD' ? '$ ' + money2(it.usd_amount) + ' <i class="ri-lock-fill lock" title="locked USD amount"></i>' : 'LKR ' + money2(it.lkr_amount)}</td>
            <td class="text-end num">${it.currency === 'USD' ? money2(it.approval_rate) + '<br><span class="muted">' + dmy(it.approval_rate_date) + '</span>' : '—'}</td><td class="text-end num">${money2(it.approval_lkr)}</td>
            <td class="text-end num">${line && line.actual_lkr !== null ? money2(line.actual_lkr) : '—'}</td><td class="text-end num ${line ? diffCls(line.fx_diff) : ''}">${line && line.fx_diff !== null ? (line.fx_diff > 0 ? '+' : '') + money2(line.fx_diff) : '—'}</td>
            <td class="text-end num chg-share" data-pr="${it.pr_id}">${sh !== undefined && sh !== null ? money2(sh) : (num(charges) > 0 ? '—' : '0.00')}</td>
            <td class="muted">${esc(it.approved_by || '')}<br>${dmyt(it.approved_at)}</td></tr>`;
    }).join('');
    const sumRow = `<tr class="fw-semibold table-light"><td colspan="3" class="text-end">Totals</td><td class="text-end num">${isUsd ? '$ ' + money2(figs ? figs.usd_total : b.usd_total) : 'LKR ' + money2(b.approval_lkr_total)}</td><td></td><td class="text-end num">${money2(b.approval_lkr_total)}</td><td class="text-end num">${actual !== null ? money2(actual) : '—'}</td><td class="text-end num ${diffCls(fx)}">${fx !== null ? (fx > 0 ? '+' : '') + money2(fx) : '—'}</td><td class="text-end num" id="v_chg_sum">${money2(num(charges))}</td><td></td></tr>`;

    // the rate panel (green, like Today's rate on the dashboard)
    const rateEditable = editing && rateMissing && d.can_enter_rate;
    const ratePanel = isUsd ? `<div class="fx mb-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="flex-grow-1"><div class="lbl">${app ? 'Final payment-date rate (locked)' : (canPay ? 'Rate of the actual payment date' : (canInit ? "Today's confirmed USD rate · the payment date's rate" : 'Payment-date USD rate'))}</div>
                <div class="small" style="opacity:.9;">${app ? `${dmy(app.paid_date)} · ${app.rate_source === 'entered' ? 'entered on this page' : 'Daily USD Rate'} · locked by ${esc(app.approved_by)}` : (rateRow.rate ? `${dmy(rateRow.date)} · confirmed${rateRow.by ? ' by ' + esc(rateRow.by) : ''} (Daily USD Rate)` : `${dmy(rateRow.date)} · <b>not confirmed</b>${rateEditable ? ' — enter it to confirm it for that day' : ' — ask the Daily USD Rate holder to confirm it'}`)}</div></div>
            <div class="d-flex align-items-center gap-2"><span class="small">USD 1 =</span><div class="input-group" style="width:170px;"><span class="input-group-text">LKR</span><input type="text" class="form-control num" id="f_rate" value="${rateRow.rate ? money2(rateRow.rate) : ''}" ${rateEditable ? '' : 'readonly'} inputmode="decimal" oninput="recalc()"></div></div>
            <div class="small" style="opacity:.9;"><i class="ri-lock-line me-1"></i>Approval rate${d.items.filter(i => i.currency === 'USD').length > 1 ? 's' : ''}: ${[...new Set(d.items.filter(i => i.currency === 'USD').map(i => money2(i.approval_rate)))].join(' / ')} — kept for comparison</div>
        </div></div>` : '';

    // the form of the step at hand
    let form = '';
    if (canInit || canPay) {
        // the paying account: management SUGGESTED one (batch); the accountant selects or confirms the actual one; the bank approver sees it and confirms it
        const baseAcct = canPay ? (d.current_account || d.suggested_account) : (d.suggested_account || d.current_account);
        const bankSel = `<select class="form-select" id="f_bank" onchange="accountChanged()">${(d.bank_accounts.accounts || []).map(a => `<option value="${d.bank_accounts.source === 'master' ? a.id : a.ledger_code}" data-ledger="${esc(a.ledger_code || '')}" ${baseAcct && (baseAcct.id ? a.id == baseAcct.id : (a.ledger_code && a.ledger_code === baseAcct.ledger_code)) ? 'selected' : ''}>${esc(a.label)}${a.account_no_masked ? ' · ' + esc(a.account_no_masked) : ''}${a.currency ? ' · ' + esc(a.currency) : ''}</option>`).join('')}</select>`;
        window._baseLedger = baseAcct ? baseAcct.ledger_code : null;
        form = `<div class="card mt-3"><div class="card-body">
            <h6>${canInit ? '<i class="ri-bank-line me-1"></i>Bank payment initiation (accountant)' : '<i class="ri-checkbox-circle-line me-1"></i>Final bank approval — Paid'}</h6>
            <div class="row g-2">
                <div class="col-md-4"><label class="form-label fw-semibold small">${canInit ? 'Actual payment date' : 'Final payment date'} <span class="text-danger">*</span></label><input type="date" class="form-control" id="f_date" value="${esc(_date)}" max="${esc(d.today)}" onchange="changeDate(this.value)"></div>
                <div class="col-md-4"><label class="form-label fw-semibold small">Avenir paying account <span class="text-danger">*</span></label>${bankSel}<div class="form-text">${canPay && init ? `initiated from <b>${esc(d.current_account ? d.current_account.label : '')}</b> by ${esc(init.initiated_by)}` : (d.suggested_account ? `suggested by management: <b>${esc(d.suggested_account.label)}</b> — select or confirm the actual paying account` : 'select the actual paying account')}</div></div>
                <div class="col-12" id="f_acct_reason_wrap" style="display:none;"><label class="form-label fw-semibold small text-danger">Reason for paying from a different account <span class="text-danger">*</span></label><input type="text" class="form-control" id="f_account_reason" maxlength="300" placeholder="recorded with the original and the new account, your name, date and time"></div>
                ${canPay ? `<div class="col-12"><label class="d-flex gap-2 align-items-start small"><input type="checkbox" id="f_confirm_account" class="mt-1"> <span>I have checked the transaction in the bank and <b>confirm the paying account</b> selected above (its currency and ledger account are revalidated on Paid).</span></label></div>` : ''}
                <div class="col-md-4"><label class="form-label fw-semibold small">Bank charges (LKR)</label><input type="text" class="form-control num" id="f_charges" value="${charges !== null && charges !== undefined && charges !== '' ? money2(charges) : ''}" placeholder="0.00" inputmode="decimal" oninput="_charges = this.value; recalc()"></div>
                <div class="col-md-4"><label class="form-label fw-semibold small">Bank / batch reference${canInit ? '' : ' <span class="text-danger">*</span>'}</label><input type="text" class="form-control" id="f_batch_ref" value="${esc(init ? (init.batch_ref || '') : '')}" placeholder="batch reference" ${canPay ? 'readonly' : ''}></div>
                <div class="col-md-4"><label class="form-label fw-semibold small">Bank transaction reference ${canPay ? '<span class="text-danger">*</span>' : ''}</label><input type="text" class="form-control" id="f_bank_ref" value="${esc(init ? (init.bank_ref || '') : '')}"></div>
                ${canInit ? `<div class="col-md-4"><label class="form-label fw-semibold small">Payment screenshot / file</label><input type="file" class="form-control" id="f_file" accept=".pdf,.png,.jpg,.jpeg"></div>` : ''}
                <div class="col-12"><label class="form-label fw-semibold small">${canInit ? "Accountant's notes" : 'Note'}</label><input type="text" class="form-control" id="f_note" maxlength="500"></div>
            </div>
            <div class="small muted mt-2">${canInit ? 'The approved USD amount, supplier, invoices and allocations cannot be changed here. The LKR amount is calculated: locked USD × the rate of the payment date. This does not mark the payment as paid — only the bank approver\'s Paid does.' : 'Check the transaction in the bank — account, beneficiary, amount — then click Paid. Paid permanently locks the final payment date, exchange rate, LKR amount, charges, total debit and reference. It cannot be undone from here.'}</div>
            ${canPay && d.paid_block ? `<div class="alert alert-danger small mt-3 mb-0"><i class="ri-error-warning-line me-1"></i><b>Final Paid is blocked — configuration warning.</b> ${esc(d.paid_block)}${P.admin || P.bank_master ? ` <a href="${TPD_URL}settings">Open Settings → Ledger mapping</a>` : ''}</div>` : ''}
            <div class="alert alert-danger small mt-3 mb-0" id="chg_block" style="display:${(canInit || canPay) && num(charges) > 0 && !(d.charge_mapping && d.charge_mapping.complete) ? '' : 'none'};"><i class="ri-error-warning-line me-1"></i><b>${canPay ? 'Final Paid is blocked while bank charges are entered' : 'Bank charges cannot be posted yet'} — configuration warning.</b> ${esc(d.charge_mapping ? d.charge_mapping.warning || '' : '')}${P.admin || P.bank_master ? ` <a href="${TPD_URL}settings">Open Settings → Ledger mapping</a>` : ''}</div>
            ${(canInit || canPay) && d.charge_mapping && d.charge_mapping.complete ? `<div class="small muted mt-2"><i class="ri-bank-card-2-line me-1"></i>Bank charges will post once at Paid: Dr <b>${esc(d.charge_mapping.account.code)} ${esc(d.charge_mapping.account.name)}</b> / Cr the paying account, one pair per tour line.</div>` : ''}
            ${canPay && d.is_usd && d.fx_mapping && d.fx_mapping.complete ? `<div class="small muted mt-2"><i class="ri-exchange-funds-line me-1"></i>Exchange-rate difference will post per tour line: gains to <b>${esc(d.fx_mapping.gain.code)} ${esc(d.fx_mapping.gain.name)}</b>, losses to <b>${esc(d.fx_mapping.loss.code)} ${esc(d.fx_mapping.loss.name)}</b>.</div>` : ''}
            <div class="mt-3 d-flex gap-2 flex-wrap"><button class="btn ${canPay ? 'btn-success' : 'btn-p'}" id="f_submit" onclick="${canPay ? 'submitPaid()' : 'submitInitiate()'}" ${(rateMissing && !rateEditable) || (canPay && d.paid_block) || (canPay && num(charges) > 0 && !(d.charge_mapping && d.charge_mapping.complete)) ? 'disabled' : ''}><i class="ri-${canPay ? 'lock-line' : 'send-plane-2-line'} me-1"></i>${canPay ? 'Paid — lock the final figures' : 'Bank payment initiated'}</button>${rateMissing && !rateEditable ? '<span class="small text-danger align-self-center">The rate of this date must be confirmed first.</span>' : ''}</div>
        </div></div>`;
    }
    const figPanel = `<div class="card"><div class="card-body">
        <h6><i class="ri-calculator-line me-1"></i>Figures</h6>
        ${isUsd ? `<div class="fig"><span class="k">Locked USD amount</span><b class="num">$ ${money2(figs ? figs.usd_total : b.usd_total)} <i class="ri-lock-fill lock"></i></b></div>` : ''}
        ${isUsd ? `<div class="fig"><span class="k">Approval-date exchange rate${d.items.filter(i => i.currency === 'USD').length > 1 ? 's' : ''}</span><b class="num">${[...new Set(d.items.filter(i => i.currency === 'USD').map(i => money2(i.approval_rate)))].join(' / ')}</b></div>` : ''}
        <div class="fig"><span class="k">Provisional LKR amount (approval)</span><b class="num">${money2(b.approval_lkr_total)}</b></div>
        <div class="fig"><span class="k">${app ? 'Final payment date' : 'Actual payment date'}</span><b>${dmy(app ? app.paid_date : (rateRow.date || _date))}</b></div>
        ${isUsd ? `<div class="fig"><span class="k">${app ? 'Final exchange rate (locked)' : (canPay ? 'Exchange rate of the payment date' : "Today's confirmed USD exchange rate")}</span><b class="num" id="v_rate">${rateRow.rate ? money2(rateRow.rate) : '—'}</b></div>` : ''}
        <div class="fig big"><span class="k">${app ? 'Final LKR amount (locked)' : 'Actual LKR payment amount'}</span><b class="num" id="v_actual">${actual !== null ? money2(actual) : '—'}</b></div>
        <div class="fig"><span class="k">Bank charges</span><b class="num" id="v_charges">${charges !== null && charges !== undefined && charges !== '' ? money2(charges) : '—'}</b></div>
        <div class="fig"><span class="k">Total amount debited</span><b class="num" id="v_total">${total !== null ? money2(total) : '—'}</b></div>
        ${isUsd ? `<div class="fig"><span class="k">Exchange-rate difference (final − provisional)</span><b class="num ${diffCls(fx)}" id="v_fx">${diffTxt(fx)}</b></div>` : ''}
        ${app ? `<div class="fig"><span class="k">Payment Voucher No.</span><b>${b.vnum}</b></div><div class="fig"><span class="k">Bank transaction reference</span><b>${esc(app.bank_ref || '')}</b></div><div class="fig"><span class="k">Paying account (confirmed${app.account_confirmed_by ? ' by ' + esc(app.account_confirmed_by) : ''})</span><b>${esc(acct ? acct.label : (v ? v.Bank : ''))}</b></div><div class="fig"><span class="k">Initiated · Paid</span><b>${init ? esc(init.initiated_by) + ' ' + dmyt(init.initiated_at) : '—'} · ${esc(app.approved_by)} ${dmyt(app.approved_at)}</b></div>${app.fx_posted == 1 ? '<div class="fig"><span class="k">Ledger</span><b>' + (fx > 0.004 ? 'loss posted to ' + esc(d.fx_mapping && d.fx_mapping.loss ? d.fx_mapping.loss.code + ' ' + d.fx_mapping.loss.name : 'the loss account') : (fx < -0.004 ? 'gain posted to ' + esc(d.fx_mapping && d.fx_mapping.gain ? d.fx_mapping.gain.code + ' ' + d.fx_mapping.gain.name : 'the gain account') : 'no difference to post')) + (app.fx_entries ? ' · entries ' + esc(app.fx_entries) + ' (one balanced pair per tour line)' : '') + '</b></div>' : (isUsd && Math.abs(fx || 0) > 0.004 ? '<div class="fig"><span class="k">Ledger</span><b class="text-danger">difference NOT posted — ledger mapping was incomplete</b></div>' : '')}${num(app.charges) > 0 ? `<div class="fig"><span class="k">Bank charges in the ledger</span><b>${app.charges_posted == 1 ? 'posted once to ' + esc(d.charge_mapping && d.charge_mapping.account ? d.charge_mapping.account.code + ' ' + d.charge_mapping.account.name : 'the Bank Charges account') + ' against ' + esc(acct ? acct.label : (v ? v.Bank : '')) + (app.charge_entries ? ' · entries ' + esc(app.charge_entries) + ' (one pair per tour line)' : '') : '<span class="text-danger">NOT posted</span>'}</b></div>` : ''}${d.reconciliation ? `<div class="fig"><span class="k">Reconciled to the bank debit</span><b class="${d.reconciliation.reconciled ? 'text-success' : 'text-danger'}">${d.reconciliation.reconciled ? 'yes — credits to ' + esc(d.reconciliation.bank_ledger) + ' = ' + money2(d.reconciliation.totals.bank_credit) + ' = total debited' : 'NO — see the reconciliation below'}</b></div>` : ''}` : ''}
        ${(d.account_changes || []).length ? `<div class="small mt-2"><b>Paying account changes:</b> ${d.account_changes.map(a => { let p = {}, n = {}; try { p = JSON.parse(a.prev_value || '{}'); n = JSON.parse(a.new_value || '{}'); } catch (e) {} return `<div>${esc(p.account || '?')} → <b>${esc(n.account || '?')}</b> at ${esc(n.stage || '')} · ${esc(a.user)} · ${esc(a.at)}${a.reason ? ' · <i>' + esc(a.reason) + '</i>' : ''}</div>`; }).join('')}</div>` : ''}
        ${init && !app ? `<div class="small muted mt-2">Initiated ${dmyt(init.initiated_at)} by ${esc(init.initiated_by)} for payment date ${dmy(init.payment_date)}${init.usd_rate ? ' at ' + money2(init.usd_rate) : ''}: LKR ${money2(init.amount)}${init.charges ? ' + charges ' + money2(init.charges) : ''} = ${money2(init.total_debit || init.amount)}${init.bank_ref ? ' · ref ' + esc(init.bank_ref) : ''}${init.batch_ref ? ' · batch ' + esc(init.batch_ref) : ''}${canPay && init.payment_date !== _date ? ' — <b>the final payment date differs: the figures above follow the rate of ' + dmy(_date) + '</b>' : ''}</div>` : ''}
    </div></div>`;
    const rec = app && d.reconciliation ? (() => { const r = d.reconciliation; const grp = { payment: 'Payment (authorisation, posted by the chain)', charges: 'Bank charges', fx: 'Exchange-rate difference' };
        return `<div class="card mt-3"><div class="card-body"><h6><i class="ri-scales-3-line me-1"></i>Ledger reconciliation to the bank debit ${r.reconciled ? '<span class="pill green">reconciled</span>' : '<span class="pill red">DOES NOT RECONCILE</span>'}</h6>
        <div class="small muted mb-2">Paying account ${esc(r.bank_ledger)} ${esc(r.bank_name)} · final LKR ${money2(r.totals.final_lkr)} + bank charges ${money2(r.totals.charges)} = total debited <b>${money2(r.totals.total_debit)}</b> · credits to the paying account found in the ledger <b>${money2(r.totals.bank_credit)}</b></div>
        <div class="table-responsive"><table class="table table-sm mb-2" style="font-size:12px;"><thead class="table-light"><tr><th>Entry</th><th>Account</th><th>Tour</th><th>Description</th><th class="text-end">Dr</th><th class="text-end">Cr</th></tr></thead><tbody>${(r.rows || []).map(x => `<tr><td class="mono">${x.entry}<br><span class="muted">${esc(grp[x.group] || x.group)}</span></td><td><span class="mono">${esc(x.account)}</span> ${esc(x.name)}</td><td>${esc(x.tour)}</td><td class="muted">${esc(x.description)}</td><td class="text-end num">${x.dr ? money2(x.dr) : ''}</td><td class="text-end num">${x.cr ? money2(x.cr) : ''}</td></tr>`).join('')}<tr class="fw-semibold table-light"><td colspan="4" class="text-end">Totals</td><td class="text-end num">${money2(r.totals.dr)}</td><td class="text-end num">${money2(r.totals.cr)}</td></tr></tbody></table></div>
        <div class="small">${(r.checks || []).map(c => `<div>${c.ok ? '<i class="ri-checkbox-circle-fill text-success"></i>' : '<i class="ri-close-circle-fill text-danger"></i>'} ${esc(c.what)}: expected <b>${typeof c.expected === 'number' ? money2(c.expected) : esc(c.expected)}</b>, in the ledger <b>${typeof c.actual === 'number' ? money2(c.actual) : esc(c.actual)}</b></div>`).join('')}</div></div></div>`; })() : '';
    const remit = app ? `<div class="card mt-3"><div class="card-body"><h6><i class="ri-mail-send-line me-1"></i>Remittance advice</h6>${rem && rem.status === 'sent' ? `<div>Sent ${dmyt(rem.sent_at)} to ${esc(rem.to_email)} by ${esc(rem.sent_by)}. <a href="${TPD_URL}remittance/${b.vnum}" target="_blank">View</a> · <a href="${TPD_URL}remittance_pdf/${b.vnum}" target="_blank">PDF</a></div>` : `<div>Pending — one advice for ${esc(b.supplier_name)}, with the final figures. <a href="${TPD_URL}remittance/${b.vnum}" target="_blank">Preview</a>${P.requester ? ` · <a href="#" onclick="openRemit(); return false;">Prepare &amp; send</a>` : ''}</div>`}</div></div>` : '';
    const docs = (d.documents || []).length ? `<div class="mt-2 small"><b>Documents:</b> ${d.documents.map(x => `<a href="${TPD_URL}file/${x.id}" target="_blank">${esc(x.title || x.kind)}${x.number ? ' · ' + esc(x.number) : ''}</a>`).join(' · ')}</div>` : '';
    const audit = `<div class="card mt-3"><div class="card-body"><h6><i class="ri-history-line me-1"></i>Audit history</h6><div class="table-responsive"><table class="table table-sm mb-0" style="font-size:12px;"><thead class="table-light"><tr><th>Date &amp; time</th><th>User</th><th>Action</th><th>Detail</th><th>Rate / bank / ref</th></tr></thead><tbody>${(d.audit || []).map(r => `<tr><td class="mono">${esc(r.at)}</td><td>${esc(r.user)}</td><td><b>${esc(r.action)}</b></td><td class="muted">${esc((r.new_value || '').slice(0, 220))}${r.reason ? '<br>' + esc(r.reason) : ''}</td><td class="muted">${r.usd_rate ? 'rate ' + money2(r.usd_rate) + '<br>' : ''}${esc(r.bank_account || '')}${r.bank_ref ? '<br>ref ' + esc(r.bank_ref) : ''}</td></tr>`).join('') || '<tr><td colspan="5" class="muted">Nothing yet.</td></tr>'}</tbody></table></div></div></div>`;

    document.getElementById('bp').innerHTML = `
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
            <div><div class="d-flex align-items-center gap-2 flex-wrap"><h4 class="mb-0" style="color:var(--p); font-weight:700;">Batch ${esc(b.batch_no)}</h4><span class="pill ${STAGE_COLOR[stage] || 'grey'}">${esc(d.stage_label)}</span></div>
                <div class="small muted mt-1">${esc(b.supplier_name)} · ${b.item_count} payment${b.item_count === 1 ? '' : 's'} · ${b.tour_count} tour${b.tour_count === 1 ? '' : 's'} (${d.tours.map(esc).join(', ')}) · <b style="color:var(--ink);">Payment Voucher No. <a href="${TPD_URL}voucher/${b.vnum}" target="_blank">${b.vnum}</a></b> (<a href="${TPD_URL}voucher/${b.vnum}?pdf=1" target="_blank">PDF</a>) · created ${dmyt(b.created_at)} by ${esc(b.created_by)}${b.note ? ' · ' + esc(b.note) : ''}</div>
                ${d.individual ? `<div class="small mt-1"><span class="pill amber"><i class="ri-flashlight-line me-1"></i>Paid individually — accumulation bypassed: ${esc(d.bypass_label || '')}</span>${b.bypass_note ? ' <span class="muted">' + esc(b.bypass_note) + '</span>' : ''}</div>` : ''}
                <div class="mt-2">${steps}</div></div>
            <div class="d-flex gap-2 flex-wrap"><a class="btn btn-sm btn-outline-p" href="${TPD_URL}payables?tab=batches"><i class="ri-arrow-left-line me-1"></i>Supplier Payables</a>${P.accountant ? `<a class="btn btn-sm btn-outline-p" href="<?= base_url('report/bank_payment_check') ?>"><i class="ri-bank-line me-1"></i>Bank Payment Check</a>` : ''}</div>
        </div>
        ${ratePanel}
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card"><div class="card-body"><h6><i class="ri-bank-card-line me-1"></i>Supplier and verified bank details</h6>
                    <div><b>${esc(sb.beneficiary_name || sb.supplier_name)}</b> ${bankSt}</div><div class="small">${esc([sb.bank_name, sb.branch].filter(Boolean).join(' · ') || 'No bank on file')}${sb.account_no ? ' · A/C <span class="mono">' + esc(sb.account_no) + '</span>' : ''}${sb.swift ? ' · ' + esc(sb.swift) : ''}${sb.currency ? ' · ' + esc(sb.currency) : ''}</div>
                    ${sb.verified_by ? `<div class="small muted">verified by ${esc(sb.verified_by)} on ${dmyt(sb.verified_at)}</div>` : '<div class="small text-danger">Not verified — check before paying.</div>'}${sb.differs_from_master ? `<div class="small text-danger">Differs from the supplier master's account.</div>` : ''}
                </div></div>
                <div class="card mt-3"><div class="card-body"><h6><i class="ri-file-list-3-line me-1"></i>Included tours and invoices</h6>
                    <div class="table-responsive"><table class="table table-sm align-middle items mb-0"><thead class="table-light"><tr><th>Tour · guest</th><th>Service</th><th>Invoice / ref</th><th class="text-end">Locked amount</th><th class="text-end">Approval rate</th><th class="text-end">Provisional LKR</th><th class="text-end">${app ? 'Final LKR' : 'Actual LKR'}</th><th class="text-end">FX diff.</th><th class="text-end" title="this tour's share of the bank charges (pro rata to its final LKR)">Charges share</th><th>Approved</th></tr></thead><tbody>${items}${sumRow}</tbody></table></div>
                    <div class="small muted mt-2">The approval figures (USD, rate at approval, provisional LKR, approval date and user) are never overwritten. The exchange-rate difference is each payment's own: locked USD × (payment-date rate − approval rate). Bank charges are allocated to the tours pro rata to their final LKR, to the cent, and each tour's payment record shows its share.</div>${docs}
                </div></div>
            </div>
            <div class="col-lg-5">${figPanel}${form}${remit}</div>
            <div class="col-12">${rec}</div>
        </div>
        ${audit}`;
}
function recalc() {
    const d = _d; if (!d) return;
    const rate = num(document.getElementById('f_rate') ? document.getElementById('f_rate').value : 0);
    const charges = num(document.getElementById('f_charges') ? document.getElementById('f_charges').value : 0);
    let actual = 0, prov = 0, ok = true;
    d.items.forEach(it => { prov += it.approval_lkr || 0; if (it.currency === 'USD') { if (!rate) ok = false; actual += Math.round(it.usd_amount * rate * 100) / 100; } else actual += it.lkr_amount || 0; });
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.innerHTML = v; };
    set('v_rate', rate ? money2(rate) : '—'); set('v_actual', ok ? money2(actual) : '—'); set('v_charges', money2(charges)); set('v_total', ok ? money2(actual + charges) : '—');
    const fx = ok ? actual - prov : null; const el = document.getElementById('v_fx'); if (el) { el.textContent = diffTxt(fx); el.className = 'num ' + diffCls(fx); }
    // the charges allocated live across the tour lines (the same rule as the server: pro rata to the final LKR, largest remainder)
    const sh = allocateShares(d.items.map(it => ({ key: String(it.pr_id), basis: it.currency === 'USD' ? Math.round(it.usd_amount * rate * 100) / 100 : (it.lkr_amount || 0) })), charges);
    document.querySelectorAll('.chg-share').forEach(td => { const v = sh[td.dataset.pr]; td.textContent = v !== undefined ? money2(v) : '—'; }); set('v_chg_sum', money2(charges));
    const mapped = d.charge_mapping && d.charge_mapping.complete; const blk = document.getElementById('chg_block'); if (blk) blk.style.display = charges > 0 && !mapped ? '' : 'none';
    const btn = document.getElementById('f_submit'); if (btn) btn.disabled = !ok || (P.bank_approver && d.stage === 'initiated' && charges > 0 && !mapped) || (P.bank_approver && d.stage === 'initiated' && !!d.paid_block);
}
function allocateShares(lines, amount) {
    const out = {}; if (!lines.length) return out;
    const cents = Math.round((amount || 0) * 100); const total = lines.reduce((t, l) => t + Math.max(0, l.basis || 0), 0);
    const raw = lines.map(l => cents * (total > 0 ? Math.max(0, l.basis || 0) / total : 1 / lines.length)); const fl = raw.map(r => Math.floor(r + 1e-9));
    let left = cents - fl.reduce((a, b) => a + b, 0);
    const order = raw.map((r, i) => i).sort((a, b) => ((raw[b] - fl[b]) - (raw[a] - fl[a])) || (a - b));
    for (const i of order) { if (left <= 0) break; fl[i]++; left--; }
    lines.forEach((l, i) => { out[l.key] = fl[i] / 100; }); return out;
}
function accountChanged() {
    const sel = document.getElementById('f_bank'); const opt = sel && sel.options[sel.selectedIndex];
    const differs = opt && window._baseLedger && opt.dataset.ledger !== window._baseLedger;
    const w = document.getElementById('f_acct_reason_wrap'); if (w) w.style.display = differs ? '' : 'none';
}
function changeDate(v) { if (!v) return; _charges = document.getElementById('f_charges') ? document.getElementById('f_charges').value : _charges; load(v); }
async function submitInitiate() {
    const d = _d; const fd = new FormData();
    fd.append('vnum', d.batch.vnum); fd.append(d.bank_accounts.source === 'master' ? 'bank_account_id' : 'ledger_code', document.getElementById('f_bank').value);
    fd.append('account_reason', (document.getElementById('f_account_reason') || {}).value || '');
    fd.append('payment_date', document.getElementById('f_date').value); fd.append('usd_rate', (document.getElementById('f_rate') || {}).value || ''); fd.append('charges', document.getElementById('f_charges').value.replace(/,/g, ''));
    fd.append('batch_ref', document.getElementById('f_batch_ref').value); fd.append('bank_ref', document.getElementById('f_bank_ref').value); fd.append('note', document.getElementById('f_note').value);
    const f = document.getElementById('f_file') && document.getElementById('f_file').files[0]; if (f) fd.append('file', f);
    const btn = document.getElementById('f_submit'); btn.disabled = true;
    const j = await postJson('initiate', fd); btn.disabled = false;
    toast(j.status === 200, j.message); if (j.status === 200) { _charges = ''; load(); }
}
async function submitPaid() {
    const d = _d;
    const cb = document.getElementById('f_confirm_account');
    if (cb && !cb.checked) return toast(false, 'Confirm the paying account shown before clicking Paid.');
    const r = await Swal.fire({ title: 'Confirm Paid?', html: `<div class="text-start small">Final payment date <b>${esc(dmy(document.getElementById('f_date').value))}</b>${d.is_usd ? ` · rate <b>${esc((document.getElementById('f_rate') || {}).value || '')}</b>` : ''} · LKR <b>${esc(document.getElementById('v_actual').textContent)}</b> · total debit <b>${esc(document.getElementById('v_total').textContent)}</b>.<br>These figures are locked permanently.</div>`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Paid', confirmButtonColor: '#1f8a4c' });
    if (!r.isConfirmed) return;
    const btn = document.getElementById('f_submit'); btn.disabled = true;
    const body = { vnum: d.batch.vnum, paid_date: document.getElementById('f_date').value, usd_rate: (document.getElementById('f_rate') || {}).value || '', charges: document.getElementById('f_charges').value.replace(/,/g, ''), bank_ref: document.getElementById('f_bank_ref').value, note: document.getElementById('f_note').value,
        confirm_account: document.getElementById('f_confirm_account') && document.getElementById('f_confirm_account').checked ? 1 : 0, account_reason: (document.getElementById('f_account_reason') || {}).value || '' };
    body[d.bank_accounts.source === 'master' ? 'bank_account_id' : 'ledger_code'] = document.getElementById('f_bank').value;
    const j = await postJson('paid', body); btn.disabled = false;
    toast(j.status === 200, j.message); if (j.status === 200) { _charges = ''; load(); }
}
async function openRemit() {
    const d = _d; const j = await postJson('remittance_prepare', { vnum: d.batch.vnum });
    if (j.status !== 200) return toast(false, j.message);
    const r = j.remittance;
    document.getElementById('rm_summary').innerHTML = `<div class="d-flex justify-content-between"><span>Supplier</span><b>${esc(r.supplier)}</b></div><div class="d-flex justify-content-between"><span>Voucher</span><b>${r.vnum}</b> · batch ${esc(d.batch.batch_no)}</div><div class="muted">One advice per supplier, per payment — nothing of another supplier is on it. Review the e-mail and the attachment, then send.</div>`;
    document.getElementById('rm_to').value = r.to || ''; document.getElementById('rm_to_note').textContent = 'Address from: ' + r.supplier_email_source; document.getElementById('rm_cc').value = ''; document.getElementById('rm_subject').value = r.subject || ''; document.getElementById('rm_body').innerHTML = r.body || '';
    document.getElementById('rm_attach').innerHTML = r.pdf ? `<i class="ri-attachment-2"></i> Attachment: <a href="${r.pdf}" target="_blank">Remittance-advice-${r.vnum}.pdf</a> · <a href="${r.preview}" target="_blank">preview as a page</a>` : `<span class="text-warning">The PDF library is not on this server — the advice goes as the e-mail text; <a href="${r.preview}" target="_blank">preview</a>.</span>`;
    new bootstrap.Modal(document.getElementById('remitModal')).show();
}
async function submitRemittance() {
    const to = document.getElementById('rm_to').value.trim();
    const r = await Swal.fire({ title: 'Send the remittance advice?', html: `To <b>${esc(to)}</b>. It is sent once; the supplier is then marked Notified.`, icon: 'question', showCancelButton: true, confirmButtonText: 'Send' });
    if (!r.isConfirmed) return;
    const btn = document.getElementById('rm_send'); btn.disabled = true;
    const j = await postJsonBody('remittance_send', { vnum: _d.batch.vnum, to, cc: document.getElementById('rm_cc').value, subject: document.getElementById('rm_subject').value, body: document.getElementById('rm_body').innerHTML }); btn.disabled = false;
    if (j.status === 200) bootstrap.Modal.getInstance(document.getElementById('remitModal')).hide();
    toast(j.status === 200, j.message); load();
}
document.addEventListener('DOMContentLoaded', () => load());
</script>
</body>
