<?= view('layouts/head') ?>
<?php
/**
 * tf-paydesk-r2 — Supplier Payables (tour/tour_payment_dashboard/payables).
 *
 *   Payment Approval → the approved payment waits HERE under its supplier → management selects the supplier's payments
 *   and creates the batch → one voucher in the accountant's queue → the batch page carries it to Paid.
 */
$P = $tpd_perms ?? [];
?>
<style>
    .tpp { --p:#842484; --p2:#8f2a8d; --soft:#f3e7f3; --ink:#2c3e50; --muted:#6c757d; --line:#e4e8ee; color:var(--ink); }
    .tpp .btn-p { background:var(--p); border-color:var(--p); color:#fff; } .tpp .btn-p:hover { background:var(--p2); color:#fff; }
    .tpp .btn-outline-p { color:var(--p); border-color:var(--p); } .tpp .btn-outline-p:hover { background:var(--soft); color:var(--p); }
    .tpp .card { border:1px solid var(--line); border-radius:14px; }
    .tpp .num { font-variant-numeric: tabular-nums; }
    .tpp .muted { color:var(--muted); }
    .tpp .pill { display:inline-block; border-radius:999px; padding:2px 9px; font-size:11.5px; font-weight:700; white-space:nowrap; }
    .tpp .pill.green { background:#e4f4ea; color:#1f8a4c; } .tpp .pill.amber { background:#fff3d6; color:#9a6400; } .tpp .pill.red { background:#fde8e8; color:#b42318; } .tpp .pill.grey { background:#eef0f3; color:#6c757d; }
    .tpp .pill.violet { background:#ece6f7; color:#5b3fa6; } .tpp .pill.teal { background:#dff5f2; color:#0b6e63; } .tpp .pill.indigo { background:#e5e9fb; color:#3141a8; } .tpp .pill.olive { background:#eef3dc; color:#5c6b12; }
    .tpp .kpi { border:1px solid var(--line); border-radius:12px; padding:12px 14px; background:#fff; } .tpp .kpi .lbl { font-size:11.5px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; } .tpp .kpi .val { font-size:1.35rem; font-weight:700; }
    .tpp .sup { border:1px solid var(--line); border-radius:14px; background:#fff; margin-bottom:12px; overflow:hidden; }
    .tpp .sup-h { display:flex; flex-wrap:wrap; align-items:center; gap:10px 16px; padding:12px 16px; background:linear-gradient(90deg, var(--soft), #fff); cursor:pointer; }
    .tpp .sup-h h6 { margin:0; font-weight:700; color:var(--p); }
    .tpp .sup table { margin:0; font-size:12.5px; }
    .tpp .tabs .btn { border-radius:999px; }
    .tpp .mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
    @media (max-width: 640px) { .tpp .kpi .val { font-size:1.1rem; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content"><div class="container-fluid tpp">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div><h4 class="mb-0" style="color:var(--p); font-weight:700;"><i class="ri-stack-line me-1"></i>Supplier Payables</h4>
                <div class="small muted">Approved payments wait here under their supplier. Management selects a supplier's payments and creates the batch; its voucher then goes to the accountant's queue (Bank Payment Check) and the batch page carries it to Paid at the rate of the actual payment date.</div></div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-sm btn-outline-p" href="<?= base_url('tour/tour_payment_dashboard') ?>"><i class="ri-dashboard-line me-1"></i>Tour Payment Dashboard</a>
                <a class="btn btn-sm btn-outline-p" href="<?= base_url('tour/tour_accounts/payment_approval_v2') ?>"><i class="ri-shield-check-line me-1"></i>Payment approval</a>
                <?php if (!empty($P['accountant'])): ?><a class="btn btn-sm btn-outline-p" href="<?= base_url('report/bank_payment_check') ?>"><i class="ri-bank-line me-1"></i>Bank Payment Check</a><?php endif; ?>
            </div>
        </div>
        <?php if (empty($tpd_accumulates)): ?><div class="alert alert-warning">Supplier Payables is not set up on this installation yet (System Administrator: Tour Payment Dashboard → Set up). Approvals still issue their voucher directly.</div><?php endif; ?>
        <div class="row g-2 mb-3" id="pp_kpis"></div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3 tabs">
            <button class="btn btn-sm btn-p" id="tab_awaiting" onclick="ppTab('awaiting')"><i class="ri-time-line me-1"></i>Awaiting batch <span class="badge bg-light text-dark ms-1" id="n_awaiting">0</span></button>
            <button class="btn btn-sm btn-outline-p" id="tab_batches" onclick="ppTab('batches')"><i class="ri-file-list-3-line me-1"></i>Batches <span class="badge bg-light text-dark ms-1" id="n_batches">0</span></button>
            <div class="ms-auto d-flex gap-2"><input type="text" class="form-control form-control-sm" id="pp_q" placeholder="supplier · tour · guest · batch no" style="min-width:240px;"><button class="btn btn-sm btn-light" onclick="ppLoad()"><i class="ri-search-line"></i></button></div>
        </div>
        <div id="pp_awaiting"></div>
        <div id="pp_batches" style="display:none;"></div>
    </div></div></div>
</div>

<!-- Create the batch -->
<div class="modal fade tpp" id="batchModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-stack-line me-1" style="color:var(--p);"></i>Create the supplier's payment batch</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="bg-light rounded p-3 mb-3 small" id="bt_summary"></div>
        <div id="bt_bank_warn" class="mb-3"></div>
        <div class="table-responsive"><table class="table table-sm align-middle" style="font-size:12.5px;"><thead class="table-light"><tr><th style="width:28px;"><input type="checkbox" id="bt_all" onchange="btToggleAll(this.checked)"></th><th>Tour</th><th>Guest</th><th>Service</th><th>Invoice</th><th class="text-end">USD</th><th class="text-end">Approval rate</th><th class="text-end">Provisional LKR</th><th>Approved</th></tr></thead><tbody id="bt_rows"></tbody>
            <tfoot><tr class="fw-semibold"><td colspan="5" class="text-end">Selected</td><td class="text-end num" id="bt_usd">0.00</td><td></td><td class="text-end num" id="bt_lkr">0.00</td><td id="bt_count"></td></tr></tfoot></table></div>
        <div class="row g-2 mt-1">
            <div class="col-md-6"><label class="form-label fw-semibold small">Avenir bank account to pay from <span class="text-danger">*</span></label><select class="form-select" id="bt_bank"></select><div class="form-text" id="bt_bank_note"></div></div>
            <div class="col-md-6"><label class="form-label fw-semibold small">Note for the accountant (optional)</label><input type="text" class="form-control" id="bt_note" maxlength="500"></div>
        </div>
        <div class="muted small mt-2">One batch = one supplier = one payment voucher. The approval figures of each payment stay as they are; the accountant's bank payment page values the batch at the rate of the actual payment date.</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-p" id="bt_submit" onclick="submitBatch()"><i class="ri-file-text-line me-1"></i>Create batch &amp; issue voucher</button></div>
</div></div></div>

<!-- tf-paydesk-r3: Pay Individually Now -->
<div class="modal fade tpp" id="payNowModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-flashlight-line me-1" style="color:var(--p);"></i>Pay Individually Now</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="bg-light rounded p-3 mb-3 small" id="pn_summary"></div>
        <div id="pn_bank_warn" class="mb-3"></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Why must this payment not wait for the supplier's bulk batch? <span class="text-danger">*</span></label>
            <select class="form-select" id="pn_reason"><option value="">— choose —</option><option value="driver_advance">Driver advance</option><option value="driver_settlement">Driver settlement</option><option value="urgent_cash">Urgent cash payment</option><option value="pay_at_gate">Pay-at-gate item</option><option value="emergency">Emergency supplier payment</option><option value="other">Other authorised payment</option></select></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Note (required for "Other")</label><input type="text" class="form-control" id="pn_note" maxlength="500"></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Suggested Avenir paying account <span class="text-danger">*</span></label><select class="form-select" id="pn_bank"></select><div class="form-text">The accountant confirms the actual paying account at initiation.</div></div>
        <div class="muted small">A single-item batch is created at once and its Payment Voucher No. goes to the accountant's queue; approval, voucher, bank initiation and the bank approver's Paid still apply. The bypass and its reason are kept in the audit history.</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-p" id="pn_submit" onclick="submitPayNow()"><i class="ri-flashlight-line me-1"></i>Pay individually now</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
const TPD_URL = '<?= base_url('tour/tour_payment_dashboard/') ?>';
const P = <?= json_encode($P) ?>;
let _pp = null, _tab = 'awaiting', _batch = null;
const esc = s => String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money2 = n => parseFloat(n || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const dmy = d => { if (!d) return '—'; const dt = new Date(String(d).slice(0, 10) + 'T00:00:00'); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}); };
const dmyt = d => { if (!d) return '—'; const dt = new Date(String(d).replace(' ', 'T')); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}) + ' ' + dt.toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit'}); };
const STAGE_COLOR = {issued:'violet', initiated:'teal', paid:'olive', notified:'green', cancelled:'red'};
function toast(ok, msg) { Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? 'Done' : 'Not done', text: msg || '', timer: ok ? 3200 : undefined, showConfirmButton: !ok }); }
async function postJson(path, body) {
    const fd = Object.entries(body || {}).reduce((f, [k, v]) => { if (Array.isArray(v)) v.forEach(x => f.append(k + '[]', x)); else if (v !== undefined && v !== null) f.append(k, v); return f; }, new FormData());
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    try { const r = await fetch(TPD_URL + path, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }); return await r.json(); } catch (e) { return { status: 0, message: 'Network error. Please try again.' }; }
}
async function ppLoad() {
    const q = document.getElementById('pp_q').value.trim();
    const sup = new URLSearchParams(location.search).get('sup_acc') || '';
    const j = await fetch(`${TPD_URL}payables_data?q=${encodeURIComponent(q)}${sup && !q ? '&sup_acc=' + encodeURIComponent(sup) : ''}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).catch(() => ({}));
    if (!j || j.status !== 200) { document.getElementById('pp_awaiting').innerHTML = `<div class="alert alert-danger">${esc((j && j.message) || 'Could not load.')}</div>`; return; }
    _pp = j.data; render();
}
function render() {
    const d = _pp, T = d.totals;
    const k = (lbl, val, sub) => `<div class="col-6 col-xl-3"><div class="kpi"><div class="lbl">${lbl}</div><div class="val num">${val}</div><div class="small muted">${sub}</div></div></div>`;
    document.getElementById('pp_kpis').innerHTML = k('Suppliers with approved payments waiting', T.suppliers, `${T.items} payment${T.items === 1 ? '' : 's'}`) + k('USD waiting (locked amounts)', '$ ' + money2(T.usd), 'final LKR at the payment-date rate')
        + k('Provisional LKR (approval rates)', 'LKR ' + money2(T.provisional_lkr), 'the approval figures, never overwritten') + k("Today's USD rate", d.today_rate ? money2(d.today_rate.rate) : '—', d.today_rate ? `confirmed by ${esc(d.today_rate.by)}` : 'not confirmed today');
    document.getElementById('n_awaiting').textContent = T.items; document.getElementById('n_batches').textContent = d.batches.length;
    renderAwaiting(); renderBatches(); ppTab(_tab);
}
function renderAwaiting() {
    const d = _pp;
    document.getElementById('pp_awaiting').innerHTML = d.awaiting.length ? d.awaiting.map((s, i) => {
        const sb = s.bank || {};
        const st = sb.status === 'verified' ? '<span class="pill green">bank verified</span>' : (sb.status === 'changed' ? '<span class="pill red">bank CHANGED</span>' : '<span class="pill amber">bank not verified</span>');
        return `<div class="sup"><div class="sup-h" onclick="document.getElementById('sup_${i}').classList.toggle('d-none')">
            <div class="flex-grow-1"><h6>${esc(s.supplier_name)} <span class="muted fw-normal small">${esc(s.sup_acc)}</span></h6><div class="small muted">${s.item_count} approved payment${s.item_count === 1 ? '' : 's'} · ${s.tour_count} tour${s.tour_count === 1 ? '' : 's'} (${s.tours.map(esc).join(', ')}) ${st}${s.is_bulk ? ' <span class="pill grey">bulk-payment supplier</span>' : ''}${s.rate_missing ? ' <span class="pill red">a USD item has no approval rate</span>' : ''}</div></div>
            <div class="text-end"><div class="num"><b>${s.usd_total > 0 ? '$ ' + money2(s.usd_total) : ''}${s.usd_total > 0 && s.lkr_total > 0 ? ' + ' : ''}${s.lkr_total > 0 ? 'LKR ' + money2(s.lkr_total) : ''}</b></div><div class="small muted">provisional LKR ${money2(s.approval_lkr_total)}</div></div>
            ${P.batch ? `<button class="btn btn-sm btn-p" onclick="event.stopPropagation(); openBatch(${i})"><i class="ri-stack-line me-1"></i>Create batch</button>` : ''}
        </div>
        <div id="sup_${i}" class="table-responsive"><table class="table table-sm align-middle"><thead class="table-light"><tr><th>Tour</th><th>Guest · dates · pax</th><th>Service</th><th>Invoice / ref</th><th class="text-end">Amount</th><th class="text-end">Approval rate</th><th class="text-end">Provisional LKR</th><th>Requested</th><th>Approved</th><th>Due</th>${P.batch ? '<th></th>' : ''}</tr></thead><tbody>
            ${s.items.map(it => `<tr><td><a href="${TPD_URL}?manual_id=${encodeURIComponent(it.manual_id)}"><b>${esc(it.manual_id)}</b></a>${it.tour_cancelled ? ' <span class="pill red">cancelled</span>' : ''}</td><td>${esc(it.guest)}<br><span class="muted">${dmy(it.start_date)} – ${dmy(it.end_date)} · ${it.pax} pax${it.agent ? ' · ' + esc(it.agent) : ''}</span></td><td>${esc(it.kind)}${it.service_dates.length ? '<br><span class="muted">' + it.service_dates.map(dmy).join(', ') + '</span>' : ''}</td><td>${esc(it.invoice_no || '—')}${it.booking_ref ? '<br><span class="muted">' + esc(it.booking_ref) + '</span>' : ''}</td><td class="text-end num">${it.currency === 'USD' ? '$ ' + money2(it.usd_amount) : 'LKR ' + money2(it.lkr_amount)}</td><td class="text-end num">${it.currency === 'USD' ? (it.approval_rate ? money2(it.approval_rate) + (it.approval_rate_date ? '<br><span class="muted">' + dmy(it.approval_rate_date) + '</span>' : '') : '<span class="text-danger">none</span>') : '—'}</td><td class="text-end num">${money2(it.approval_lkr || 0)}</td><td class="muted">${esc(it.requested_by || '')}<br>${dmyt(it.requested_at)}</td><td class="muted">${esc(it.approved_by || '')}<br>${dmyt(it.approved_at)}</td><td>${it.due_date ? dmy(it.due_date) : '—'}</td>${P.batch ? `<td class="text-end"><button class="btn btn-sm btn-outline-p" title="Pay Individually Now — a single-item batch, the accumulation stage bypassed with a recorded reason" onclick="openPayNow(${i}, ${it.pr_id})"><i class="ri-flashlight-line me-1"></i>Pay now</button></td>` : ''}</tr>`).join('')}
        </tbody></table></div></div>`;
    }).join('') : '<div class="card"><div class="card-body text-center muted py-5">No approved payment is waiting for a batch.</div></div>';
}
function renderBatches() {
    const d = _pp;
    document.getElementById('pp_batches').innerHTML = `<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size:12.5px;"><thead class="table-light"><tr><th>Batch</th><th>Supplier</th><th>Tours</th><th class="text-end">USD</th><th class="text-end">Provisional LKR</th><th class="text-end">Final LKR</th><th class="text-end">FX diff.</th><th>Payment Voucher No.</th><th>Status</th><th>Created</th><th>Paid</th><th></th></tr></thead><tbody>
        ${d.batches.length ? d.batches.map(b => `<tr><td><a href="${TPD_URL}batch/${b.id}"><b>${esc(b.batch_no)}</b></a>${b.individual == 1 ? `<br><span class="pill amber" title="${esc(b.bypass_note || '')}">individual · ${esc(b.bypass_label || '')}</span>` : ''}</td><td>${esc(b.supplier_name)}</td><td>${b.tours.map(esc).join(', ')} <span class="muted">(${b.item_count})</span></td><td class="text-end num">${b.usd_total ? money2(b.usd_total) : '—'}</td><td class="text-end num">${money2(b.approval_lkr_total)}</td><td class="text-end num">${b.paid_lkr ? money2(b.paid_lkr) : (b.voucher_amount && b.stage !== 'issued' ? money2(b.voucher_amount) : '—')}</td><td class="text-end num ${b.fx_diff > 0 ? 'text-danger' : (b.fx_diff < 0 ? 'text-success' : '')}">${b.fx_diff !== null && b.fx_diff !== undefined ? (b.fx_diff > 0 ? '+' : '') + money2(b.fx_diff) : '—'}</td><td><a href="${TPD_URL}voucher/${b.vnum}" target="_blank"><b>${b.vnum}</b></a></td><td><span class="pill ${STAGE_COLOR[b.stage] || 'grey'}">${esc(b.stage_label)}</span></td><td class="muted">${esc(b.created_by)}<br>${dmyt(b.created_at)}</td><td class="muted">${b.paid_date ? dmy(b.paid_date) + (b.paid_rate ? ' @ ' + money2(b.paid_rate) : '') + '<br>' + esc(b.paid_by || '') : '—'}</td><td><a class="btn btn-sm btn-outline-p" href="${TPD_URL}batch/${b.id}">Open</a></td></tr>`).join('') : '<tr><td colspan="12" class="text-center muted py-4">No batch yet.</td></tr>'}
    </tbody></table></div></div></div>`;
}
function ppTab(t) { _tab = t; document.getElementById('pp_awaiting').style.display = t === 'awaiting' ? '' : 'none'; document.getElementById('pp_batches').style.display = t === 'batches' ? '' : 'none'; document.getElementById('tab_awaiting').className = 'btn btn-sm ' + (t === 'awaiting' ? 'btn-p' : 'btn-outline-p'); document.getElementById('tab_batches').className = 'btn btn-sm ' + (t === 'batches' ? 'btn-p' : 'btn-outline-p'); }
function supplierBankHtml(sb) {
    if (!sb) return '';
    const bad = sb.status !== 'verified';
    const st = sb.status === 'verified' ? '<span class="pill green">Verified</span>' : (sb.status === 'changed' ? '<span class="pill red">CHANGED since verification</span>' : '<span class="pill amber">Not verified</span>');
    return `<div class="border rounded p-2 ${bad ? 'border-danger' : ''}" style="font-size:12.5px;"><div class="fw-semibold">Supplier bank details ${st}</div><div>${esc(sb.beneficiary_name || sb.supplier_name)} · ${esc([sb.bank_name, sb.branch].filter(Boolean).join(' · ') || 'no bank on file')}${sb.account_no ? ' · A/C ' + esc(sb.account_no) : ''}${sb.swift ? ' · ' + esc(sb.swift) : ''}</div>${sb.verified_by ? `<div class="muted">verified by ${esc(sb.verified_by)} on ${dmyt(sb.verified_at)}</div>` : ''}${bad ? (P.verifier ? '<label class="d-flex gap-2 align-items-center mt-1"><input type="checkbox" id="bt_confirm_bank"> <span>I have checked these bank details with the supplier — confirm them (recorded under my name)</span></label>' : '<div class="text-danger mt-1">A holder of the Supplier bank verification privilege must confirm this account before the batch can be created.</div>') : ''}</div>`;
}
function openBatch(i) {
    const s = _pp.awaiting[i]; if (!s) return;
    _batch = { sup: s, selected: {} }; s.items.forEach(it => { _batch.selected[it.pr_id] = true; });
    document.getElementById('bt_summary').innerHTML = `<div class="d-flex justify-content-between"><span>Supplier</span><b>${esc(s.supplier_name)}</b></div><div class="d-flex justify-content-between"><span>Approved payments waiting</span><b>${s.item_count} · ${s.tour_count} tour${s.tour_count === 1 ? '' : 's'}</b></div>${_pp.today_rate ? `<div class="d-flex justify-content-between"><span>Today's confirmed USD rate</span><span class="num">${money2(_pp.today_rate.rate)} (${esc(_pp.today_rate.by)})</span></div>` : ''}`;
    document.getElementById('bt_bank_warn').innerHTML = s.items[0].type !== 'Transport Advance' ? supplierBankHtml(s.bank) : '';
    const b = _pp.bank_accounts;
    document.getElementById('bt_bank').innerHTML = '<option value="">— choose —</option>' + b.accounts.map(a => `<option value="${b.source === 'master' ? a.id : a.ledger_code}" ${a.is_default ? 'selected' : ''}>${esc(a.label)}${a.account_no_masked ? ' · ' + esc(a.account_no_masked) : ''}${a.currency ? ' · ' + esc(a.currency) : ''}</option>`).join('');
    document.getElementById('bt_bank_note').innerHTML = b.source === 'master' ? 'Accounts from the Avenir Bank Account Master (no free typing).' : '<span class="text-warning">The Avenir Bank Account Master is empty — the ledger\'s bank accounts are offered meanwhile.</span>';
    document.getElementById('bt_note').value = '';
    btRender();
    new bootstrap.Modal(document.getElementById('batchModal')).show();
}
function btRender() {
    const b = _batch; if (!b) return;
    document.getElementById('bt_rows').innerHTML = b.sup.items.map(it => `<tr class="${b.selected[it.pr_id] ? '' : 'text-muted'}"><td><input type="checkbox" ${b.selected[it.pr_id] ? 'checked' : ''} onchange="btToggle(${it.pr_id}, this.checked)"></td><td><b>${esc(it.manual_id)}</b>${it.tour_cancelled ? ' <span class="pill red">tour cancelled</span>' : ''}</td><td>${esc(it.guest)}</td><td>${esc(it.kind)}${it.service_dates.length ? '<br><span class="muted">' + it.service_dates.map(dmy).join(', ') + '</span>' : ''}</td><td>${esc(it.invoice_no || '—')}</td><td class="text-end num">${it.currency === 'USD' ? money2(it.usd_amount) : '<span class="muted">LKR ' + money2(it.lkr_amount) + '</span>'}</td><td class="text-end num">${it.currency === 'USD' ? (it.approval_rate ? money2(it.approval_rate) : '<span class="text-danger">none</span>') : '—'}</td><td class="text-end num">${money2(it.approval_lkr || 0)}</td><td class="muted">${esc(it.approved_by || '')}<br>${dmyt(it.approved_at)}</td></tr>`).join('');
    const sel = b.sup.items.filter(it => b.selected[it.pr_id]);
    document.getElementById('bt_usd').textContent = money2(sel.reduce((s, it) => s + (it.usd_amount || 0), 0));
    document.getElementById('bt_lkr').textContent = money2(sel.reduce((s, it) => s + (it.approval_lkr || 0), 0));
    document.getElementById('bt_count').textContent = `${sel.length} of ${b.sup.items.length}`;
    document.getElementById('bt_all').checked = sel.length === b.sup.items.length && sel.length > 0;
}
function btToggle(id, on) { if (_batch) { _batch.selected[id] = on; btRender(); } }
function btToggleAll(on) { if (_batch) { _batch.sup.items.forEach(it => { _batch.selected[it.pr_id] = on; }); btRender(); } }
async function submitBatch() {
    const b = _batch; if (!b) return;
    const ids = b.sup.items.filter(it => b.selected[it.pr_id]).map(it => it.pr_id);
    if (!ids.length) return toast(false, 'Select at least one approved payment.');
    const body = { sup_acc: b.sup.sup_acc, pr_ids: ids, note: document.getElementById('bt_note').value };
    body[_pp.bank_accounts.source === 'master' ? 'bank_account_id' : 'ledger_code'] = document.getElementById('bt_bank').value;
    const cb = document.getElementById('bt_confirm_bank'); if (cb && cb.checked) body.confirm_bank = 1;
    const btn = document.getElementById('bt_submit'); btn.disabled = true;
    const j = await postJson('batch_create', body); btn.disabled = false;
    if (j.status === 428) { toast(false, j.message); return; }
    if (j.status === 200) { bootstrap.Modal.getInstance(document.getElementById('batchModal')).hide(); await Swal.fire({ icon: 'success', title: 'Batch created', html: `<div class="small">${esc(j.message)}</div>`, confirmButtonText: 'Open the batch', showCancelButton: true, cancelButtonText: 'Stay here' }).then(r => { if (r.isConfirmed) location.href = `${TPD_URL}batch/${j.batch_id}`; }); ppLoad(); return; }
    toast(false, j.message);
}
let _payNow = null;
function openPayNow(i, prId) {
    const s = _pp.awaiting[i]; const it = s && s.items.find(x => x.pr_id === prId); if (!it) return;
    _payNow = { s, it };
    document.getElementById('pn_summary').innerHTML = `<div class="d-flex justify-content-between"><span>Supplier</span><b>${esc(s.supplier_name)}</b></div><div class="d-flex justify-content-between"><span>Payment</span><b>${esc(it.manual_id)} · ${esc(it.kind)} · ${it.currency === 'USD' ? '$ ' + money2(it.usd_amount) + ' (provisional LKR ' + money2(it.approval_lkr) + ')' : 'LKR ' + money2(it.lkr_amount)}</b></div>`;
    document.getElementById('pn_bank_warn').innerHTML = it.type !== 'Transport Advance' ? supplierBankHtml(s.bank).replace('id="bt_confirm_bank"', 'id="pn_confirm_bank"') : '';
    const b = _pp.bank_accounts;
    document.getElementById('pn_bank').innerHTML = b.accounts.map(a => `<option value="${b.source === 'master' ? a.id : a.ledger_code}" ${a.is_default ? 'selected' : ''}>${esc(a.label)}${a.account_no_masked ? ' · ' + esc(a.account_no_masked) : ''}</option>`).join('');
    document.getElementById('pn_reason').value = it.type === 'Transport Advance' ? 'driver_advance' : ''; document.getElementById('pn_note').value = '';
    new bootstrap.Modal(document.getElementById('payNowModal')).show();
}
async function submitPayNow() {
    const p = _payNow; if (!p) return;
    const body = { sup_acc: p.s.sup_acc, pr_ids: [p.it.pr_id], individual: 1, bypass_reason: document.getElementById('pn_reason').value, bypass_note: document.getElementById('pn_note').value };
    body[_pp.bank_accounts.source === 'master' ? 'bank_account_id' : 'ledger_code'] = document.getElementById('pn_bank').value;
    const cb = document.getElementById('pn_confirm_bank'); if (cb && cb.checked) body.confirm_bank = 1;
    const btn = document.getElementById('pn_submit'); btn.disabled = true;
    const j = await postJson('batch_create', body); btn.disabled = false;
    if (j.status === 200) { bootstrap.Modal.getInstance(document.getElementById('payNowModal')).hide(); await Swal.fire({ icon: 'success', title: 'Paid individually — batch created', html: `<div class="small">${esc(j.message)}</div>`, confirmButtonText: 'Open the batch', showCancelButton: true, cancelButtonText: 'Stay here' }).then(r => { if (r.isConfirmed) location.href = `${TPD_URL}batch/${j.batch_id}`; }); ppLoad(); return; }
    toast(false, j.message);
}
document.getElementById('pp_q').addEventListener('keydown', e => { if (e.key === 'Enter') ppLoad(); });
document.addEventListener('DOMContentLoaded', () => { if (new URLSearchParams(location.search).get('tab') === 'batches') _tab = 'batches'; ppLoad(); });
</script>
</body>
