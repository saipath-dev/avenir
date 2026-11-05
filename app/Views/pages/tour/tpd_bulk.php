<?= view('layouts/head') ?>
<?php
/**
 * tf-paydesk-r5 — a BULK supplier payment batch (tour/tour_payment_dashboard/bulk/{id}): one bank transfer, one voucher per item.
 *   the accountant initiates the whole transfer once (date, account, rate, reference, charges — allocated across the vouchers to the cent);
 *   the bank approver marks the whole transfer Paid once; the ledger is checked voucher by voucher and over the batch;
 *   one itemised remittance advice lists every voucher.
 */
$P = $tpd_perms ?? [];
?>
<style>
    .tpb { --p:#842484; --p2:#8f2a8d; --soft:#f3e7f3; --ink:#2c3e50; --muted:#6c757d; --line:#e4e8ee; color:var(--ink); }
    .tpb .btn-p { background:var(--p); border-color:var(--p); color:#fff; } .tpb .btn-p:hover { background:var(--p2); color:#fff; }
    .tpb .btn-outline-p { color:var(--p); border-color:var(--p); } .tpb .btn-outline-p:hover { background:var(--soft); color:var(--p); }
    .tpb .card { border:1px solid var(--line); border-radius:14px; } .tpb .card h6 { font-weight:700; color:var(--p); }
    .tpb .num { font-variant-numeric: tabular-nums; } .tpb .muted { color:var(--muted); }
    .tpb .pill { display:inline-block; border-radius:999px; padding:2px 9px; font-size:11.5px; font-weight:700; white-space:nowrap; }
    .tpb .pill.green { background:#e4f4ea; color:#1f8a4c; } .tpb .pill.amber { background:#fff3d6; color:#9a6400; } .tpb .pill.red { background:#fde8e8; color:#b42318; } .tpb .pill.grey { background:#eef0f3; color:#6c757d; }
    .tpb .pill.violet { background:#ece6f7; color:#5b3fa6; } .tpb .pill.teal { background:#dff5f2; color:#0b6e63; } .tpb .pill.olive { background:#eef3dc; color:#5c6b12; }
    .tpb .fig { display:flex; justify-content:space-between; gap:12px; padding:7px 0; border-bottom:1px dashed var(--line); font-size:13px; } .tpb .fig:last-child { border-bottom:0; }
    .tpb .fig b { font-size:14px; } .tpb .fig.big b { font-size:18px; color:var(--p); } .tpb .fig .k { color:var(--muted); }
    .tpb .steps { display:flex; gap:6px; flex-wrap:wrap; } .tpb .steps span { font-size:11.5px; padding:3px 9px; border-radius:999px; background:#eef0f3; color:var(--muted); } .tpb .steps span.done { background:#e4f4ea; color:#1f8a4c; } .tpb .steps span.now { background:var(--soft); color:var(--p); font-weight:700; }
    .tpb table.items { font-size:12.5px; } .tpb .ok { color:#1f8a4c; } .tpb .bad { color:#b42318; }
    @media (max-width: 640px) { .tpb .fig { flex-direction:column; gap:2px; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content"><div class="container-fluid tpb" id="bp">
        <div class="text-center muted py-5">Loading bulk batch <?= esc($batch_no ?? '') ?>…</div>
    </div></div></div>
</div>

<div class="modal fade tpb" id="remitModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-mail-send-line me-1" style="color:var(--p);"></i>Consolidated remittance advice</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="bg-light rounded p-3 mb-3 small" id="rm_summary"></div>
        <div class="row g-2">
            <div class="col-md-7"><label class="form-label fw-semibold small">To <span class="text-danger">*</span></label><input type="email" class="form-control" id="rm_to"></div>
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
const BULK_ID = <?= (int) $bulk_id ?>;
const P = <?= json_encode($P) ?>;
const ACCOUNTS = <?= json_encode(array_values(array_filter($bank_accounts ?? [], static fn ($a) => (int) $a['active'] === 1))) ?>;
let _d = null;
const esc = s => String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money2 = n => parseFloat(n || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const num = v => parseFloat(String(v === null || v === undefined ? '' : v).replace(/,/g, '')) || 0;
const dmy = d => { if (!d) return '—'; const dt = new Date(String(d).slice(0, 10) + 'T00:00:00'); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}); };
const dmyt = d => { if (!d) return '—'; const dt = new Date(String(d).replace(' ', 'T')); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}) + ' ' + dt.toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit'}); };
const STAGE_COLOR = {issued:'violet', initiated:'teal', initiated_partial:'amber', paid_partial:'amber', paid_unreconciled:'amber', paid_with_cancelled:'red', paid:'olive', notified:'green', cancelled:'red'};
function toast(ok, msg) { Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? 'Done' : 'Not done', text: msg || '', timer: ok ? 4200 : undefined, showConfirmButton: !ok }); }
async function postJson(path, body) {
    const fd = body instanceof FormData ? body : Object.entries(body || {}).reduce((f, [k, v]) => { if (Array.isArray(v)) v.forEach(x => f.append(k + '[]', x)); else if (v !== undefined && v !== null) f.append(k, v); return f; }, new FormData());
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    try { const r = await fetch(TPD_URL + path, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }); return await r.json(); } catch (e) { return { status: 0, message: 'Network error. Please try again.' }; }
}
async function postJsonBody(path, obj) {
    try { const r = await fetch(TPD_URL + path, { method: 'POST', body: JSON.stringify(obj || {}), headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' } }); return await r.json(); } catch (e) { return { status: 0, message: 'Network error. Please try again.' }; }
}
async function load() {
    const j = await fetch(`${TPD_URL}bulk_data/${BULK_ID}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).catch(() => ({}));
    if (!j || j.status !== 200) { document.getElementById('bp').innerHTML = `<div class="alert alert-danger">${esc((j && j.message) || 'Could not load the bulk batch.')}</div>`; return; }
    _d = j.data; render();
}
function render() {
    const d = _d, b = d.batch, t = d.totals, c = d.counts, n = d.count;
    const cur = b.currency;
    const canInit = P.initiator && c.issued > 0;   // tf-paydesk-r7f: the explicit initiation privilege
    const canPay = P.bank_approver && c.issued === 0 && c.paid < n;
    const stageCls = STAGE_COLOR[d.stage] || 'grey';
    const steps = `<div class="steps"><span class="done">Approved</span><span class="done">${n} vouchers issued</span><span class="${c.initiated + c.paid === n ? 'done' : (c.initiated ? 'now' : (canInit ? 'now' : ''))}">Bank transfer initiated ${c.initiated + c.paid}/${n}</span><span class="${c.paid === n ? 'done' : (c.paid ? 'now' : (canPay ? 'now' : ''))}">Paid ${c.paid}/${n}</span><span class="${d.reconciled ? 'done' : ''}">Ledger balanced</span><span class="${d.remittance && d.remittance.status === 'sent' ? 'done' : ''}">Supplier notified</span></div>`;
    const rows = d.children.map((k, i) => `<tr><td class="muted">${i + 1}</td><td><a href="${TPD_URL}voucher/${k.vnum}" target="_blank"><b>${k.vnum}</b></a><br><a class="small" href="${TPD_URL}batch/${k.batch_id}">${esc(k.batch_no)}</a></td>
        <td><a href="${TPD_URL}?manual_id=${encodeURIComponent(k.manual_id || '')}" target="_blank"><b>${esc(k.manual_id)}</b></a><br><span class="muted">${esc(k.guest)}</span></td><td>${esc(k.kind)}${k.service_dates && k.service_dates.length ? '<br><span class="muted">' + k.service_dates.map(dmy).join(', ') + '</span>' : ''}</td><td>${esc(k.invoice_no || '—')}</td>
        <td class="text-end num">${k.currency === 'USD' ? 'USD ' + money2(k.usd_amount) : 'LKR ' + money2(k.lkr_amount)}</td>
        <td class="text-end num">${k.paid_lkr !== null ? money2(k.paid_lkr) : (k.voucher_amount !== null ? '<span class="muted">' + money2(k.voucher_amount) + '</span>' : '—')}</td>
        <td class="text-end num">${k.charge_share !== null ? money2(k.charge_share) : '—'}</td>
        <td class="text-end num">${k.fx_diff !== null ? money2(k.fx_diff) : '—'}</td>
        <td><span class="pill ${STAGE_COLOR[k.stage] || 'grey'}">${esc(k.stage)}</span>${k.stage === 'paid' ? (k.reconciled ? ' <span class="ok" title="ledger balanced">✓</span>' : ' <span class="bad" title="ledger not balanced — re-run Paid">!</span>') : ''}</td></tr>`).join('');
    const acctOpts = ACCOUNTS.map(a => `<option value="${a.id}" ${d.bank_account && a.id == d.bank_account.id ? 'selected' : ''}>${esc(a.label)} · ${esc(a.ledger_code)} (${esc(a.currency)})</option>`).join('');
    const initForm = canInit ? `<div class="card mt-3"><div class="card-body"><h6><i class="ri-bank-line me-1"></i>Initiate the ONE bank transfer (accountant)</h6>
        <div class="muted small mb-2">The same payment date, account, rate and reference apply to every voucher of the batch. Bank charges entered here are allocated across the ${n} vouchers pro rata to the cent and posted at Paid. ${c.initiated ? '<b>' + c.initiated + ' already initiated — only the rest are initiated now.</b>' : ''}</div>
        <div class="row g-2">
            <div class="col-md-3"><label class="form-label small fw-semibold">Payment date</label><input type="date" class="form-control" id="f_date" value="${esc(b.payment_date || d.today)}" max="${esc(d.today)}"></div>
            <div class="col-md-4"><label class="form-label small fw-semibold">Paying account</label><select class="form-select" id="f_acct">${acctOpts}</select></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Bank reference</label><input type="text" class="form-control" id="f_ref" value="${esc(b.bank_ref || '')}"></div>
            <div class="col-md-2"><label class="form-label small fw-semibold">Bank charges (LKR)</label><input type="text" class="form-control num" id="f_charges" value="${b.charges_total !== null ? money2(b.charges_total) : '0.00'}"></div>
            <div class="col-md-8"><label class="form-label small fw-semibold">Note</label><input type="text" class="form-control" id="f_note"></div>
            <div class="col-md-4"><label class="form-label small fw-semibold">Reason if the account differs from the suggested one</label><input type="text" class="form-control" id="f_reason"></div>
        </div>
        ${d.is_usd ? `<div class="small mt-2 ${d.today_rate ? 'ok' : 'bad'}">${d.today_rate ? 'USD rate of ' + esc(d.today) + ': ' + money2(d.today_rate.rate) : 'No USD rate confirmed for ' + esc(d.today) + ' — it is asked for when you initiate.'}</div>` : ''}
        <div class="mt-3"><button class="btn btn-p" id="btn_init" onclick="submitInitiate()"><i class="ri-send-plane-2-line me-1"></i>Initiate transfer for ${n - c.initiated - c.paid} voucher${n - c.initiated - c.paid === 1 ? '' : 's'}</button></div></div></div>` : '';
    const payForm = canPay ? `<div class="card mt-3"><div class="card-body"><h6><i class="ri-checkbox-circle-line me-1"></i>Paid — final bank approval for the whole transfer</h6>
        <div class="muted small mb-2">Every voucher is locked at the payment-date rate, its exchange difference and bank-charge share are posted, and the ledger is checked voucher by voucher and over the batch. ${c.paid ? '<b>' + c.paid + ' already Paid — only the rest are marked now.</b>' : ''}</div>
        <div class="row g-2">
            <div class="col-md-3"><label class="form-label small fw-semibold">Paid date</label><input type="date" class="form-control" id="p_date" value="${esc(b.payment_date || d.today)}" max="${esc(d.today)}"></div>
            <div class="col-md-4"><label class="form-label small fw-semibold">Paying account (confirm)</label><select class="form-select" id="p_acct">${acctOpts}</select></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Bank reference</label><input type="text" class="form-control" id="p_ref" value="${esc(b.bank_ref || '')}"></div>
            <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" id="p_confirm"><label class="form-check-label small" for="p_confirm">I confirm the account</label></div></div>
        </div>
        <div class="mt-3"><button class="btn btn-success" id="btn_paid" onclick="submitPaid()"><i class="ri-lock-line me-1"></i>Paid — ${n - c.paid} voucher${n - c.paid === 1 ? '' : 's'}</button></div></div></div>` : '';
    const recon = c.paid > 0 ? `<div class="card mt-3"><div class="card-body"><h6><i class="ri-scales-3-line me-1"></i>Ledger check over the batch ${d.reconciled ? '<span class="pill green">balanced</span>' : '<span class="pill amber">not yet balanced</span>'}</h6>
        <div class="fig"><span class="k">Σ final LKR of the ${n} vouchers</span><b class="num">${money2(t.paid_lkr)}</b></div>
        <div class="fig"><span class="k">Bank charges entered / allocated</span><b class="num">${money2(t.charges_total)} / ${money2(t.charges_allocated)}</b></div>
        <div class="fig"><span class="k">Σ exchange differences</span><b class="num">${money2(t.fx_diff)}</b></div>
        <div class="fig big"><span class="k">Σ credits to the paying account (payments + charges) = the one bank debit</span><b class="num">${money2(t.bank_credit)} ${Math.abs(t.bank_credit - t.total_debit) < 0.005 ? '<span class="ok">= ' + money2(t.total_debit) + '</span>' : '<span class="bad">≠ ' + money2(t.total_debit) + '</span>'}</b></div>
        <div class="small muted mt-2">"Balanced" means the postings equal the Paid figures. It is <b>not</b> a bank-statement match — that is Bank Reconciliation Step 2.</div></div></div>` : '';
    const rem = d.remittance;
    const remit = c.paid === n && n > 0 ? `<div class="card mt-3"><div class="card-body"><h6><i class="ri-mail-send-line me-1"></i>Consolidated remittance advice</h6>${rem && rem.status === 'sent' ? `<div>Sent ${dmyt(rem.sent_at)} to ${esc(rem.to_email)} by ${esc(rem.sent_by)} — one advice listing all ${n} vouchers. <a href="${TPD_URL}bulk_remittance/${b.id}" target="_blank">View</a> · <a href="${TPD_URL}bulk_remittance/${b.id}?pdf=1" target="_blank">PDF</a></div>` : `<div>Pending — one itemised advice for ${esc(b.supplier_name)} listing every voucher. <a href="${TPD_URL}bulk_remittance/${b.id}" target="_blank">Preview</a>${P.accountant || P.bank_approver ? ` · <a href="#" onclick="openRemit(); return false;">Prepare &amp; send</a>` : ''}</div>`}</div></div>` : '';
    const audit = (d.audit || []).slice(-40).reverse().map(a => `<tr><td class="num small">${dmyt(a.at)}</td><td class="small">${esc(a.user)}</td><td class="small">${esc(a.action)}${a.vnum ? ' · PV ' + a.vnum : ''}</td><td class="small muted">${esc(a.reason || '')}</td></tr>`).join('');
    document.getElementById('bp').innerHTML = `
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div><h4 class="mb-1 fw-bold" style="color:var(--p);">Bulk batch ${esc(b.batch_no)} <span class="pill ${stageCls}">${esc(d.stage_label)}</span></h4>
                <div class="small muted">${esc(b.supplier_name)} · ${n} vouchers (Payment Voucher No. ${d.children.length ? d.children[0].vnum + '–' + d.children[d.children.length - 1].vnum : '—'}) · ${d.tours.length} tour${d.tours.length === 1 ? '' : 's'} · one bank transfer of <b style="color:var(--ink);">${cur} ${money2(t.transfer_total)}</b> · created ${dmyt(b.created_at)} by ${esc(b.created_by)}${b.note ? ' · ' + esc(b.note) : ''}${d.atomic ? '' : ' · <span class="bad">issued without transactional tables</span>'}</div>
                <div class="mt-2">${steps}</div></div>
            <div class="d-flex gap-2 flex-wrap"><a class="btn btn-sm btn-outline-p" href="${TPD_URL}payables?tab=batches"><i class="ri-arrow-left-line me-1"></i>Supplier Payables</a><a class="btn btn-sm btn-outline-p" href="${TPD_URL}bulk_remittance/${b.id}" target="_blank"><i class="ri-file-list-3-line me-1"></i>Itemised advice</a></div>
        </div>
        <div class="row g-3"><div class="col-lg-8">
            <div class="card"><div class="card-body"><h6><i class="ri-file-list-3-line me-1"></i>The ${n} vouchers of this transfer</h6>
                <div class="table-responsive"><table class="table table-sm align-middle items mb-0"><thead class="table-light"><tr><th>#</th><th>Voucher · batch</th><th>Tour · guest</th><th>Service</th><th>Invoice</th><th class="text-end">Amount</th><th class="text-end">Final LKR</th><th class="text-end">Charge share</th><th class="text-end">FX diff</th><th>Stage</th></tr></thead><tbody>${rows}</tbody>
                <tfoot class="table-light fw-bold"><tr><td colspan="5">Total</td><td class="text-end num">${cur} ${money2(t.voucher_total)}</td><td class="text-end num">${money2(t.paid_lkr || t.transfer_lkr || 0)}</td><td class="text-end num">${money2(t.charges_allocated)}</td><td class="text-end num">${money2(t.fx_diff)}</td><td></td></tr></tfoot></table></div>
                <div class="small muted mt-2">Σ of the vouchers ${t.vouchers_equal_transfer ? '<span class="ok">= the transfer</span>' : '<span class="bad">≠ the transfer</span>'} · each voucher keeps its own tour, service, invoice and number; the legacy per-voucher Mark Finalized is blocked for them.</div>
            </div></div>
            ${initForm}${payForm}
        </div><div class="col-lg-4">
            <div class="card"><div class="card-body"><h6><i class="ri-exchange-dollar-line me-1"></i>The transfer</h6>
                <div class="fig big"><span class="k">Transfer</span><b class="num">${cur} ${money2(t.transfer_total)}</b></div>
                ${d.is_usd ? `<div class="fig"><span class="k">Rate of the payment date</span><b class="num">${b.usd_rate ? money2(b.usd_rate) : '—'}</b></div><div class="fig"><span class="k">LKR at that rate</span><b class="num">${t.transfer_lkr !== null ? money2(t.transfer_lkr) : '—'}</b></div>` : ''}
                <div class="fig"><span class="k">Bank charges</span><b class="num">${t.charges_total !== null ? money2(t.charges_total) : '—'}</b></div>
                <div class="fig"><span class="k">Total debited</span><b class="num">${b.total_debit !== null ? money2(b.total_debit) : '—'}</b></div>
                <div class="fig"><span class="k">Payment date</span><b>${dmy(b.payment_date)}</b></div>
                <div class="fig"><span class="k">Bank reference</span><b>${esc(b.bank_ref || '—')}</b></div>
                <div class="fig"><span class="k">Paying account</span><b>${d.bank_account ? esc(d.bank_account.label) + ' · ' + esc(d.bank_account.ledger_code) : '— (suggested at creation)'}</b></div>
                <div class="fig"><span class="k">Supplier bank</span><b>${d.supplier ? esc(d.supplier.bank_name || '') + ' <span class="pill ' + (d.supplier.status === 'verified' ? 'green' : 'amber') + '">' + esc(d.supplier.status) + '</span>' : '—'}</b></div>
                <div class="fig"><span class="k">Selected / confirmed</span><b>${esc(b.confirmed_by || b.created_by)} · ${dmyt(b.confirmed_at || b.created_at)}</b></div>
                <div class="fig"><span class="k">Initiated</span><b>${b.initiated_by ? esc(b.initiated_by) + ' · ' + dmyt(b.initiated_at) : '—'}</b></div>
                <div class="fig"><span class="k">Paid</span><b>${b.paid_by ? esc(b.paid_by) + ' · ' + dmyt(b.paid_at) : '—'}</b></div>
            </div></div>
            ${recon}${remit}
            <div class="card mt-3"><div class="card-body"><h6><i class="ri-history-line me-1"></i>Audit record</h6><div class="table-responsive" style="max-height:320px; overflow:auto;"><table class="table table-sm mb-0"><tbody>${audit || '<tr><td class="muted small">—</td></tr>'}</tbody></table></div></div></div>
        </div></div>`;
}
async function submitInitiate() {
    const btn = document.getElementById('btn_init'); btn.disabled = true;
    const j = await postJson('bulk_initiate', { bulk_id: BULK_ID, payment_date: document.getElementById('f_date').value, bank_account_id: document.getElementById('f_acct').value, bank_ref: document.getElementById('f_ref').value, charges: document.getElementById('f_charges').value, note: document.getElementById('f_note').value, account_reason: document.getElementById('f_reason').value });
    btn.disabled = false;
    if (j.needs_rate) { const r = await Swal.fire({ title: 'USD rate of ' + j.date, input: 'text', inputLabel: 'LKR per 1 USD', showCancelButton: true }); if (r.isConfirmed && r.value) { await postJson('confirm_rate', { rate: r.value, date: j.date }); return submitInitiate(); } }
    toast(j.status === 200 || j.status === 207, j.message); load();
}
async function submitPaid() {
    if (!document.getElementById('p_confirm').checked) return toast(false, 'Confirm the paying account first.');
    const r = await Swal.fire({ title: 'Mark the whole transfer Paid?', html: `${_d.count - _d.counts.paid} voucher(s) will be locked at the payment-date rate; exchange differences and bank-charge shares are posted; the ledger is checked.`, icon: 'question', showCancelButton: true, confirmButtonText: 'Paid' });
    if (!r.isConfirmed) return;
    const btn = document.getElementById('btn_paid'); btn.disabled = true;
    const j = await postJson('bulk_paid', { bulk_id: BULK_ID, paid_date: document.getElementById('p_date').value, bank_account_id: document.getElementById('p_acct').value, bank_ref: document.getElementById('p_ref').value, confirm_account: 1 });
    btn.disabled = false;
    toast(j.status === 200 || j.status === 207, j.message); load();
}
async function openRemit() {
    const j = await postJson('bulk_remittance_prepare', { bulk_id: BULK_ID });
    if (j.status !== 200) return toast(false, j.message);
    const r = j.remittance;
    document.getElementById('rm_summary').innerHTML = `<div class="d-flex justify-content-between"><span>Supplier</span><b>${esc(_d.batch.supplier_name)}</b></div><div class="d-flex justify-content-between"><span>Vouchers</span><b>${r.items}</b> · bulk batch ${esc(_d.batch.batch_no)}</div><div class="muted">One advice for the whole transfer, every voucher listed. Review and send once.</div>`;
    document.getElementById('rm_to').value = r.to || ''; document.getElementById('rm_cc').value = ''; document.getElementById('rm_subject').value = r.subject || ''; document.getElementById('rm_body').innerHTML = r.body || '';
    document.getElementById('rm_attach').innerHTML = r.pdf ? `<i class="ri-attachment-2"></i> Attachment: <a href="${r.pdf}" target="_blank">Remittance-advice-${esc(_d.batch.batch_no)}.pdf</a> · <a href="${r.preview}" target="_blank">preview as a page</a>` : `<span class="text-warning">The PDF library is not on this server — the advice goes as the e-mail text; <a href="${r.preview}" target="_blank">preview</a>.</span>`;
    new bootstrap.Modal(document.getElementById('remitModal')).show();
}
async function submitRemittance() {
    const to = document.getElementById('rm_to').value.trim();
    const r = await Swal.fire({ title: 'Send the consolidated advice?', html: `To <b>${esc(to)}</b>. It is sent once; all vouchers of the batch are then marked Notified.`, icon: 'question', showCancelButton: true, confirmButtonText: 'Send' });
    if (!r.isConfirmed) return;
    const btn = document.getElementById('rm_send'); btn.disabled = true;
    const j = await postJsonBody('bulk_remittance_send', { bulk_id: BULK_ID, to, cc: document.getElementById('rm_cc').value, subject: document.getElementById('rm_subject').value, body: document.getElementById('rm_body').innerHTML }); btn.disabled = false;
    if (j.status === 200) bootstrap.Modal.getInstance(document.getElementById('remitModal')).hide();
    toast(j.status === 200, j.message); load();
}
document.addEventListener('DOMContentLoaded', () => load());
</script>
</body>
