<?= view('layouts/head') ?>
<?php
/**
 * tf-paydesk-r7 (17 Oct 2026) — the unified Payment Desk (tour/payment_desk), in the Tour Desk's style.
 * One page over the whole payment chain: requests, approvals, supplier payables, batches, the accountant's bank initiation,
 * the bank approver's Paid, paid items, non-tour payments, the vouchers not yet on the desk (adoption), the Daily Bank
 * Reconciliation and the exceptions. The data is read-only (tour/payment_desk/data); each action goes to the endpoint that
 * already performs it, and each person sees only the actions their privileges allow (the endpoints check again).
 */
$P = $tpd_perms ?? [];
?>
<style>
    .pdk { --p:#842484; --p2:#8f2a8d; --soft:#f3e7f3; --ink:#2c3e50; --muted:#6c757d; --line:#e4e8ee; --bg:#f6f7fa; --card:#fff; --radius:14px;
           --green:#1f8a4c; --green-soft:#e4f4ea; --blue:#2563eb; --blue-soft:#e8effd; --violet:#7c3aed; --violet-soft:#f1eafe; --teal:#0f766e; --teal-soft:#dff4f1;
           --amber:#1a1a1a; --amber-soft:#ffffff; --red:#b23a32; --red-soft:#fbe9e7; --grey:#6c757d; --grey-soft:#eef0f3; color:var(--ink); font-size:13.5px; }
    .pdk .card { border:1px solid var(--line); border-radius:var(--radius); box-shadow:0 1px 3px rgba(16,24,40,.05); }
    .pdk .btn-p { background:var(--p); border-color:var(--p); color:#fff; } .pdk .btn-p:hover { background:var(--p2); border-color:var(--p2); color:#fff; }
    .pdk .btn-outline-p { border:1px solid var(--p); color:var(--p); background:#fff; } .pdk .btn-outline-p:hover { background:var(--soft); color:var(--p); }
    .pdk .muted { color:var(--muted); } .pdk .num { font-variant-numeric: tabular-nums; } .pdk .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .pdk .pd-head { background:linear-gradient(135deg, var(--p) 0%, #5b1a5b 100%); color:#fff; border-radius:var(--radius); padding:16px 18px; }
    .pdk .pd-head h4 { color:#fff; margin:0; font-weight:700; letter-spacing:.2px; }
    .pdk .pd-head .sub { color:rgba(255,255,255,.85); font-size:12.5px; }
    .pdk .pd-head .chip { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22); border-radius:999px; padding:3px 10px; font-size:12px; margin:6px 4px 0 0; }
    .pdk .pd-head .btn-ghost { background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.35); border-radius:10px; }
    .pdk .pd-head .btn-ghost:hover { background:rgba(255,255,255,.22); color:#fff; }
    .pdk .kpis { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:10px; }
    .pdk .kpi { background:var(--card); border:1px solid var(--line); border-radius:var(--radius); padding:12px 14px; cursor:pointer; text-align:left; }
    .pdk .kpi:hover { border-color:var(--p); } .pdk .kpi.on { border-color:var(--p); box-shadow:0 0 0 2px var(--soft); }
    .pdk .kpi .lbl { font-size:12px; color:var(--muted); } .pdk .kpi .val { font-size:1.35rem; font-weight:700; margin:2px 0 0; }
    .pdk .kpi.warn .val { color:var(--amber); } .pdk .kpi.bad .val { color:var(--red); } .pdk .kpi.good .val { color:var(--green); }
    .pdk .sec-h { padding:14px 18px 6px; } .pdk .sec-h h5 { margin:0; font-weight:700; } .pdk .sec-h .help { color:var(--muted); font-size:12.5px; margin-top:2px; }
    .pdk table.pt { width:100%; font-size:12.5px; } .pdk table.pt th { font-size:11px; text-transform:uppercase; letter-spacing:.3px; color:var(--muted); font-weight:700; padding:8px 10px; border-bottom:1px solid var(--line); background:#fafbfc; white-space:nowrap; }
    .pdk table.pt td { padding:9px 10px; border-bottom:1px dashed var(--line); vertical-align:top; } .pdk table.pt tr:hover td { background:#fcfbfd; } .pdk table.pt tr.focus td { background:var(--soft); }
    .pdk .pill { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:3px 9px; font-size:11.5px; font-weight:700; white-space:nowrap; }
    .pdk .pill.grey { background:var(--grey-soft); color:var(--grey); } .pdk .pill.blue { background:var(--blue-soft); color:var(--blue); } .pdk .pill.violet { background:var(--violet-soft); color:var(--violet); }
    .pdk .pill.teal { background:var(--teal-soft); color:var(--teal); } .pdk .pill.green { background:var(--green-soft); color:var(--green); } .pdk .pill.amber { background:var(--amber-soft); border:1px solid #d9d3dd; color:var(--amber); } .pdk .pill.red { background:var(--red-soft); color:var(--red); }
    .pdk .acts { display:flex; flex-wrap:wrap; gap:5px; } .pdk .acts .btn { border-radius:10px; font-size:12px; padding:4px 10px; white-space:nowrap; }
    .pdk .empty { color:var(--muted); padding:18px; text-align:center; }
    .pdk .note-box { border-left:3px solid var(--amber); background:#fff; padding:8px 12px; font-size:12.5px; margin:0 18px 10px; } .pdk .note-box.red { border-color:var(--red); } .pdk .note-box.blue { border-color:var(--blue); }
    .pdk .exc { border:1px solid var(--line); border-radius:12px; margin:0 18px 12px; } .pdk .exc .h { padding:10px 14px; display:flex; justify-content:space-between; gap:10px; align-items:center; } .pdk .exc .h b { font-size:13px; }
    .pdk .check { display:flex; gap:8px; padding:5px 0; border-bottom:1px dashed var(--line); font-size:12.5px; } .pdk .check .i { width:20px; flex:none; font-weight:700; } .pdk .check.ok .i { color:var(--green); } .pdk .check.bad .i { color:var(--red); }
    .pdk-msg { position:fixed; right:18px; bottom:18px; z-index:2000; max-width:520px; }
    .pdk-msg .m { background:#fff; border:1px solid #e4e8ee; border-left:4px solid #1f8a4c; border-radius:12px; padding:10px 14px; margin-top:8px; box-shadow:0 6px 20px rgba(16,24,40,.12); font-size:13px; }
    .pdk-msg .m.err { border-left-color:#b23a32; } .pdk-msg .m.info { border-left-color:#2563eb; }
    @media (max-width: 760px) { .pdk table.pt { display:block; overflow-x:auto; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content"><div class="container-fluid pdk" id="pdk">
        <div class="pd-head mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
                <div>
                    <h4><i class="ri-bank-card-line me-1"></i>Payment Desk</h4>
                    <div class="sub">Requests → approval → supplier payables → batches → bank initiation → Paid → remittance → reconciliation · tour and non-tour payments</div>
                    <div id="pd_chips"></div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-ghost" href="<?= base_url('tour/tour_payment_dashboard') ?>"><i class="ri-route-line me-1"></i>Tour Payment Dashboard</a>
                    <?php if (!empty($P['payables'])): ?><a class="btn btn-sm btn-ghost" href="<?= base_url('tour/tour_payment_dashboard/payables') ?>"><i class="ri-stack-line me-1"></i>Supplier Payables</a><?php endif; ?>
                    <?php if (!empty($P['accountant']) || !empty($P['bank_approver'])): ?><a class="btn btn-sm btn-ghost" href="<?= base_url('report/bank_payment_check') ?>"><i class="ri-bank-line me-1"></i>Bank Payment Check</a><?php endif; ?>
                    <?php if (!empty($P['dbr'])): ?><a class="btn btn-sm btn-ghost" href="<?= base_url('accounts/daily_bank_rec') ?>"><i class="ri-scales-3-line me-1"></i>Daily Bank Reconciliation</a><?php endif; ?>
                    <button class="btn btn-sm btn-ghost" type="button" onclick="load()"><i class="ri-refresh-line me-1"></i>Refresh</button>
                </div>
            </div>
        </div>
        <?php if (empty($tpd_ready)): ?>
            <div class="alert alert-warning">The Tour Payment Desk is not installed on this system, so the Payment Desk has nothing to show.</div>
        <?php endif; ?>
        <?php if (!empty($tpd_ready) && empty($adoption_ready)): ?>
            <div class="alert alert-warning small">Adoption of existing vouchers is not set up yet (table tpd_adoption).<?php if (!empty($P['admin'])): ?> <a href="#" onclick="runSetup(); return false;">Set it up now</a> (creates the table only).<?php endif; ?></div>
        <?php endif; ?>
        <div class="kpis mb-3" id="pd_kpis"></div>
        <div class="card"><div id="pd_section"><div class="empty">Loading the Payment Desk…</div></div></div>
    </div></div></div>
</div>

<div class="modal fade pdk" id="pdModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold" id="pm_title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="pm_body"></div>
    <div class="modal-footer" id="pm_foot"></div>
</div></div></div>
<div class="pdk-msg" id="pd_msg"></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
const PD_URL = '<?= base_url('tour/payment_desk/') ?>';
const TPD_URL = '<?= base_url('tour/tour_payment_dashboard/') ?>';
const DBR_URL = '<?= base_url('accounts/daily_bank_rec/') ?>';
const P = <?= json_encode($P) ?>;
const ME = <?= json_encode((string) ($tpd_user ?? '')) ?>;
const FOCUS = <?= (int) ($focus_vnum ?? 0) ?>;
const CSRF = { name: '<?= csrf_token() ?>', hash: '<?= csrf_hash() ?>' };
let D = null, SEC = null, BUSY = false;

const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const money = n => n === null || n === undefined || n === '' ? '—' : Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const dmy = s => { if (!s) return '—'; const d = String(s).slice(0, 10).split('-'); return d.length === 3 ? `${d[2]}/${d[1]}/${d[0]}` : esc(s); };
const dmyt = s => s ? dmy(s) + ' ' + String(s).slice(11, 16) : '—';
const pill = (t, c) => `<span class="pill ${c}">${esc(t)}</span>`;
function say(text, kind) { const box = document.getElementById('pd_msg'); const m = document.createElement('div'); m.className = 'm ' + (kind || ''); m.innerHTML = esc(text); box.appendChild(m); setTimeout(() => m.remove(), kind === 'err' ? 15000 : 8000); }

async function post(url, data) {
    const fd = new FormData(); for (const k in data) { if (data[k] !== undefined && data[k] !== null) fd.append(k, data[k]); } fd.append(CSRF.name, CSRF.hash);
    try { const r = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }); return await r.json(); } catch (e) { return { status: 0, message: 'Network error. Nothing was confirmed — reload the page before trying again.' }; }
}

const SECTIONS = [
    ['requests', 'Requests', 'Waiting for management approval (approve on the Tour Payment Dashboard of the tour).'],
    ['payables', 'Supplier payables', 'Approved payments waiting under their supplier for a batch.'],
    ['batches', 'Batches', 'Supplier batches in progress and those completed in the last 14 days.'],
    ['initiation', 'Bank initiation', 'Vouchers on the desk waiting for the accountant to record the bank initiation.'],
    ['authorisation', 'Bank authorisation', 'Initiated payments waiting for a different person with the bank-approver privilege to mark Paid.'],
    ['paid', 'Paid', 'Paid on the desk in the last 30 days, with the ledger read-back and the remittance advice.'],
    ['non_tour', 'Non-tour payments', 'Payments with no tour (utility bills, office costs): adopted, then initiated and Paid like any other.'],
    ['not_on_desk', 'Not on the desk', 'Unpaid bank-transfer vouchers raised outside the desk. Each is checked and adopted before its bank payment.'],
    ['reconciliation', 'Reconciliation', 'The Daily Bank Reconciliation accounts; the desk\'s postings appear there as book-side items.'],
    ['exceptions', 'Exceptions', 'Items that need a person\'s attention.'],
];

async function load() {
    const j = await fetch(PD_URL + 'data', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, cache: 'no-store' }).then(r => r.json()).catch(() => ({ status: 0, message: 'Network error.' }));
    if (j.status !== 200) { document.getElementById('pd_section').innerHTML = `<div class="empty">${esc(j.message || 'The Payment Desk could not be loaded.')}</div>`; return; }
    D = j.data;
    const roles = [['admin', 'Administrator'], ['approver', 'Management approver'], ['batch', 'Batches'], ['initiate', 'Accountant (initiation)'], ['bank_approver', 'Bank approver (Paid)'], ['requester', 'Requests'], ['viewer', 'Viewer']].filter(([k]) => P[k]).map(([, n]) => n);
    document.getElementById('pd_chips').innerHTML = `<span class="chip"><i class="ri-user-line"></i><b>${esc(ME)}</b></span>` + roles.map(r => `<span class="chip">${esc(r)}</span>`).join('') + `<span class="chip"><i class="ri-time-line"></i>${dmyt(D.generated_at)}</span>`;
    if (!SEC) {
        SEC = 'authorisation';
        if (P.initiate && !P.bank_approver) SEC = 'initiation';
        if (!P.initiate && !P.bank_approver && (P.approver || P.requester)) SEC = 'requests';
        if (FOCUS) { for (const [k] of SECTIONS) { if ((D[k] && D[k].rows || []).some(r => Number(r.vnum) === FOCUS)) { SEC = k; break; } } }
    }
    renderKpis(); render();
}

function renderKpis() {
    const c = D.counts || {}; const cls = { exceptions: c.exceptions ? 'bad' : 'good', not_on_desk: c.not_on_desk ? 'warn' : '', authorisation: c.authorisation ? 'warn' : '', initiation: c.initiation ? 'warn' : '' };
    document.getElementById('pd_kpis').innerHTML = SECTIONS.map(([k, n]) => `<button type="button" class="kpi ${cls[k] || ''} ${SEC === k ? 'on' : ''}" onclick="SEC='${k}'; renderKpis(); render();"><div class="lbl">${esc(n)}</div><div class="val num">${k === 'reconciliation' ? (c[k] || 0) + ' acct' : (c[k] || 0)}</div></button>`).join('');
}

function head(k) { const s = SECTIONS.find(x => x[0] === k); return `<div class="sec-h"><h5>${esc(s[1])}</h5><div class="help">${esc(s[2])}</div></div>`; }
function table(cols, rows) { return rows.length ? `<div class="table-responsive"><table class="pt"><thead><tr>${cols.map(c => `<th>${c}</th>`).join('')}</tr></thead><tbody>${rows.join('')}</tbody></table></div>` : '<div class="empty">Nothing here.</div>'; }
const tr = (r, cells) => `<tr class="${FOCUS && Number(r.vnum) === FOCUS ? 'focus' : ''}" ${r.vnum ? `data-vnum="${Number(r.vnum)}"` : ''}>${cells.map(c => `<td>${c}</td>`).join('')}</tr>`;
const pv = r => r.vnum ? `<a href="${TPD_URL}voucher/${Number(r.vnum)}" target="_blank" class="fw-bold">${Number(r.vnum)}</a>` : '—';
const whom = r => r.kind === 'tour' ? (r.manual_id ? `<a href="${TPD_URL}?manual_id=${encodeURIComponent(r.manual_id)}" target="_blank">${esc(r.manual_id)}</a>` : esc(r.tour_id || '')) : pill('non-tour', 'violet') + (r.ref ? `<div class="muted">${esc(r.ref)}</div>` : '');
const via = r => r.via === 'batch' ? pill(r.batch_no || 'batch', 'teal') : (r.via === 'request' ? pill('desk request', 'blue') : (r.via === 'adoption' ? pill('adopted', 'violet') : pill('not on desk', 'amber')));

function actsFor(r, stage) {
    const a = [];
    if (r.via === 'batch' && r.batch_id) {
        a.push(r.bulk_parent ? `<a class="btn btn-outline-p" href="${TPD_URL}bulk/${r.bulk_parent}" target="_blank">Bulk page</a>` : `<a class="btn btn-outline-p" href="${TPD_URL}batch/${r.batch_id}" target="_blank">Batch page</a>`);
        return a.join('');
    }
    if (stage === 'adopt' && P.initiate) a.push(`<button class="btn btn-p" onclick="openAdopt(${Number(r.vnum)})">Check &amp; adopt</button>`);
    if (stage === 'initiate' && P.initiate) a.push(`<button class="btn btn-p" onclick="openInitiate(${Number(r.vnum)})">Record bank initiation</button>`);
    if (stage === 'paid' && P.bank_approver) {
        if (r.initiated_by && String(r.initiated_by).toLowerCase() === ME.toLowerCase()) a.push(`<span class="muted small">You initiated it — a different person marks Paid</span>`);
        else a.push(`<button class="btn btn-p" onclick="openPaid(${Number(r.vnum)})">Confirm Paid</button>`);
    }
    if (!a.length) a.push('<span class="muted small">no action for your role</span>');
    return a.join('');
}

function render() {
    const k = SEC, box = document.getElementById('pd_section'); const s = D[k] || { rows: [] };
    let h = head(k) + (s.error ? `<div class="note-box red">${esc(s.error)}</div>` : '') + (s.note ? `<div class="note-box blue">${esc(s.note)}</div>` : '');
    const rows = s.rows || [];
    if (k === 'requests') h += table(['Request', 'Tour', 'Supplier', 'Type', 'LKR', 'Status', 'Requested', ''], rows.map(r => tr(r, [`#${r.pr_id}`, r.manual_id ? `<a href="${TPD_URL}?manual_id=${encodeURIComponent(r.manual_id)}" target="_blank">${esc(r.manual_id)}</a>${r.tour_cancelled ? ' ' + pill('tour cancelled', 'red') : ''}` : esc(r.tour_id), esc(r.supplier), esc(r.type), `<span class="num">${money(r.amount)}</span>`, pill(r.status, r.status === 'requested' ? 'amber' : 'grey'), `${esc(r.by)}<div class="muted">${dmyt(r.at)}</div>`, P.approver && r.manual_id ? `<a class="btn btn-sm btn-outline-p" href="${TPD_URL}?manual_id=${encodeURIComponent(r.manual_id)}" target="_blank">Open the tour to approve</a>` : ''])));
    if (k === 'payables') h += table(['Supplier', 'Payments', 'Tours', 'USD', 'Approval LKR', ''], rows.map(r => tr(r, [`<b>${esc(r.supplier)}</b>${r.disputed ? ' ' + pill(r.disputed + ' disputed', 'red') : ''}${r.rate_missing ? ' ' + pill('rate missing', 'amber') : ''}`, r.items, esc((r.tours || []).join(', ')), `<span class="num">${r.usd_total ? money(r.usd_total) : '—'}</span>`, `<span class="num">${money(r.approval_lkr_total || r.lkr_total)}</span>`, P.payables ? `<a class="btn btn-sm btn-outline-p" href="${TPD_URL}payables" target="_blank">${P.batch ? 'Create a batch' : 'Open Supplier Payables'}</a>` : ''])));
    if (k === 'batches') h += table(['Batch', 'Voucher', 'Supplier', 'Stage', 'Items', 'Totals', 'Initiated / Paid', ''], rows.map(r => tr(r, [`<b>${esc(r.batch_no)}</b><div class="muted">${esc(r.mode)}</div>`, pv(r), esc(r.supplier), pill(r.stage_label || r.stage, ['paid', 'notified'].includes(r.stage) ? 'green' : (r.stage === 'cancelled' ? 'red' : (String(r.stage).startsWith('initiated') ? 'blue' : 'amber'))), r.items, `${r.currency === 'USD' && r.usd_total ? 'USD ' + money(r.usd_total) + '<br>' : ''}<span class="muted">approval LKR ${money(r.approval_lkr_total)}</span>`, `${esc(r.initiated_by || '—')}<div class="muted">${r.paid_by ? 'Paid by ' + esc(r.paid_by) + ' ' + dmy(r.paid_date) : ''}</div>`, P.payables ? `<a class="btn btn-sm btn-outline-p" href="${TPD_URL}${r.mode === 'bulk_parent' ? 'bulk' : 'batch'}/${r.id}" target="_blank">Open</a>` : ''])));
    if (k === 'initiation') h += table(['PV', 'Payee', 'Tour', 'On the desk via', 'LKR', 'Status', 'Paying account', ''], rows.map(r => tr(r, [pv(r), esc(r.payee), whom(r), via(r) + (r.bank_leg ? '<div>' + pill('bank leg at Paid', 'amber') + '</div>' : ''), `<span class="num">${money(r.amount)}</span>${r.usd ? `<div class="muted">USD ${money(r.usd)}</div>` : ''}`, r.aflag === 1 ? pill('authorised', 'blue') : pill('issued', 'grey'), esc(r.bank || r.bank_ledger || '—'), `<div class="acts">${actsFor(r, 'initiate')}</div>`])));
    if (k === 'authorisation') h += table(['PV', 'Payee', 'Tour', 'Initiated', 'Payment date', 'LKR / charges / total debit', 'Reference', ''], rows.map(r => tr(r, [pv(r), esc(r.payee), whom(r), `${esc(r.initiated_by)}<div class="muted">${dmyt(r.initiated_at)}</div>`, dmy(r.payment_date), `<span class="num">${money(r.amount)}</span> / <span class="num">${money(r.charges || 0)}</span> / <b class="num">${money(r.total_debit)}</b>${r.locked ? '<div>' + pill('Paid interrupted — resume', 'amber') + '</div>' : ''}${r.bank_leg ? '<div>' + pill('bank leg at Paid', 'amber') + '</div>' : ''}`, esc(r.bank_ref || '—'), `<div class="acts">${actsFor(r, 'paid')}</div>`])));
    if (k === 'paid') h += table(['PV', 'Payee', 'Paid', 'LKR', 'FX / charges', 'Total debit', 'Ledger read-back', 'Remittance'], rows.map(r => tr(r, [pv(r), esc(r.payee) + (r.old_route ? '<div>' + pill('older Mark Finalized', 'amber') + '</div>' : ''), `${dmy(r.paid_date)}<div class="muted">${esc(r.paid_by)} · initiated by ${esc(r.initiated_by || '—')}</div>`, `<span class="num">${money(r.lkr)}</span>${r.usd ? `<div class="muted">USD ${money(r.usd)} × ${money(r.rate)}</div>` : ''}`, `${r.fx_diff ? (r.fx_diff > 0 ? 'loss ' : 'gain ') + money(Math.abs(r.fx_diff)) : '—'} / ${money(r.charges || 0)}`, `<b class="num">${money(r.total_debit)}</b>`, r.read_back === null ? pill('not read back', 'amber') : (r.reconciled ? pill('reconciled ' + money(r.read_back), 'green') : pill('does not reconcile ' + money(r.read_back), 'red')), r.remittance ? `${pill(r.remittance, r.remittance === 'sent' ? 'green' : 'grey')} <a href="${TPD_URL}remittance/${Number(r.vnum)}" target="_blank">view</a>` : '—'])));
    if (k === 'non_tour') h += table(['PV', 'Payee', 'Reference', 'LKR', 'Stage', ''], rows.map(r => { const st = r.via === null ? 'adopt' : (!r.initiated ? 'initiate' : 'paid'); return tr(r, [pv(r), esc(r.payee), esc(r.ref || '—') + (r.description ? `<div class="muted">${esc(r.description)}</div>` : ''), `<span class="num">${money(r.amount)}</span>`, st === 'adopt' ? pill('not on desk', 'amber') : (st === 'initiate' ? pill('adopted — initiate', 'violet') : pill('initiated — awaiting Paid', 'blue')), `<div class="acts">${actsFor(r, st)}</div>`]); }));
    if (k === 'not_on_desk') h += (D.adoption_ready ? '' : '<div class="note-box">Adoption is not set up yet (table tpd_adoption).</div>') + table(['PV', 'Payee', 'Tour', 'Posting mode', 'Status', 'LKR', 'Raised', ''], rows.map(r => tr(r, [pv(r), esc(r.payee), whom(r), r.mode === 'direct' ? pill('direct to bank', 'grey') : (r.mode === 'bank_control_2step' ? pill('via Bank Control', 'grey') : (r.mode === 'bank_control_only' ? pill('Bank Control only', 'amber') : pill(r.mode, 'red'))), r.aflag === 1 ? pill('authorised', 'blue') : pill('issued', 'grey'), `<span class="num">${money(r.amount)}</span>`, `${dmy(r.created)}<div class="muted">${esc(r.created_by || '')}</div>`, `<div class="acts">${D.adoption_ready ? actsFor(r, 'adopt') : ''}</div>`])));
    if (k === 'reconciliation') h += table(['Account', 'Ledger', 'Status', 'Reconciled from', 'Last day prepared', 'Desk Paid (30 days)', ''], rows.map(r => tr(r, [`<b>${esc(r.label || ('account ' + r.bank_account_id))}</b> <span class="muted">${esc(r.currency)}</span>`, `<span class="mono">${esc(r.ledger_code)}</span>`, pill(r.status, r.status === 'confirmed' ? 'green' : 'amber'), dmy(r.rec_start), r.last_day ? `${dmy(r.last_day)}<div class="muted">${esc(r.last_status || '')}</div>` : '—', `${r.desk_paid_30d} · LKR <span class="num">${money(r.desk_paid_30d_total)}</span>`, P.dbr ? `<a class="btn btn-sm btn-outline-p" href="${DBR_URL}account/${Number(r.bank_account_id)}" target="_blank">Open</a>` : ''])));
    if (k === 'exceptions') { const items = s.items || []; h += items.map(x => `<div class="exc"><div class="h"><div><b>${esc(x.title)}</b><div class="muted small">${esc(x.help)}</div></div>${x.rows.length ? pill(x.rows.length, x.key === 'refusals' ? 'grey' : 'red') : pill('none', 'green')}</div>${x.rows.length ? `<div class="table-responsive"><table class="pt"><thead><tr>${Object.keys(x.rows[0]).map(c => `<th>${esc(c.replace(/_/g, ' '))}</th>`).join('')}</tr></thead><tbody>${x.rows.map(r => `<tr>${Object.keys(x.rows[0]).map(c => `<td>${c === 'vnum' ? pv(r) : esc(r[c])}</td>`).join('')}</tr>`).join('')}</tbody></table></div>` : ''}</div>`).join(''); }
    box.innerHTML = h;
    const f = FOCUS && box.querySelector(`tr[data-vnum="${FOCUS}"]`); if (f) f.scrollIntoView({ block: 'center' });
}

function rowOf(vnum) { for (const k of ['initiation', 'authorisation', 'non_tour', 'not_on_desk']) { const r = (D[k] && D[k].rows || []).find(x => Number(x.vnum) === Number(vnum)); if (r) return r; } return null; }
function modal(title, body, foot) { document.getElementById('pm_title').innerHTML = title; document.getElementById('pm_body').innerHTML = body; document.getElementById('pm_foot').innerHTML = foot; bootstrap.Modal.getOrCreateInstance(document.getElementById('pdModal')).show(); }
function closeModal() { bootstrap.Modal.getOrCreateInstance(document.getElementById('pdModal')).hide(); }

async function openAdopt(vnum) {
    modal(`Check &amp; adopt Payment Voucher No. ${vnum}`, '<div class="muted">Checking…</div>', '');
    const j = await fetch(`${PD_URL}adopt_check?vnum=${vnum}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, cache: 'no-store' }).then(r => r.json()).catch(() => ({ status: 0, message: 'Network error.' }));
    if (j.status !== 200) { document.getElementById('pm_body').innerHTML = `<div class="text-danger">${esc(j.message)}</div>`; return; }
    const c = j.data, v = c.voucher || {};
    const list = (c.checks || []).filter(x => x.key !== 'posting_mode' || !c.needs_bank_leg).map(x => `<div class="check ${x.ok ? 'ok' : 'bad'}"><span class="i">${x.ok ? '✓' : '✗'}</span><div><b>${esc(x.what)}</b><div class="muted">${esc(x.detail)}</div></div></div>`).join('');
    const blocking = (c.checks || []).filter(x => !x.ok && x.key !== 'posting_mode');
    const leg = c.needs_bank_leg ? `<div class="note-box mt-2" style="margin:8px 0;"><label class="d-flex gap-2"><input type="checkbox" id="ad_leg"><span><b>Post the bank leg at Paid.</b> This voucher's posting credits Bank Control (1-6-8-9750) only. Tick to have the desk post, once at Paid, Dr Bank Control / Cr the paying account for LKR ${money(v.amount)}, so the paying account and the Daily Bank Reconciliation show the payment.</span></label></div>` : '';
    document.getElementById('pm_body').innerHTML = `<div class="mb-2"><b>${esc(v.payee)}</b> · LKR <b class="num">${money(v.amount)}</b> · ${c.kind && c.kind.kind === 'tour' ? 'tour ' + esc(c.kind.manual_id || c.kind.tour_id) : 'non-tour payment'} · ${v.aflag === 1 ? 'authorised' : 'issued'} · raised ${dmy(v.created)} by ${esc(v.created_by || '')}<div class="muted">${esc(v.description || '')}</div></div>${list}${leg}<div class="mt-2"><label class="form-label small fw-semibold">Note (optional)</label><input class="form-control form-control-sm" id="ad_note" maxlength="300"></div><div class="muted small mt-2">Adoption records the voucher on the desk only. It does not change the voucher or the ledger.</div>`;
    document.getElementById('pm_foot').innerHTML = `<button class="btn btn-light" data-bs-dismiss="modal">Close</button>${blocking.length ? '<span class="text-danger small">Cannot be adopted — see the checks marked ✗.</span>' : `<button class="btn btn-p" id="ad_go" onclick="doAdopt(${vnum})">Adopt onto the Payment Desk</button>`}`;
}

async function doAdopt(vnum) {
    if (BUSY) return; BUSY = true; const b = document.getElementById('ad_go'); if (b) b.disabled = true;
    const leg = document.getElementById('ad_leg');
    const r = await post(PD_URL + 'adopt', { vnum, bank_leg: leg && leg.checked ? 1 : 0, note: (document.getElementById('ad_note') || {}).value || '' });
    BUSY = false; if (b) b.disabled = false;
    say(r.message || 'No answer.', r.status === 200 ? '' : 'err'); if (r.status === 200) { closeModal(); load(); }
}

function accountOptions(selLedger) {
    const a = (D.bank_accounts && D.bank_accounts.accounts) || [];
    return a.map(x => `<option value="${x.id !== null ? 'id:' + x.id : 'lc:' + esc(x.ledger_code)}" ${x.ledger_code === selLedger ? 'selected' : ''}>${esc(x.label)} (${esc(x.ledger_code)})</option>`).join('');
}

function openInitiate(vnum) {
    const r = rowOf(vnum); if (!r) return;
    modal(`Record the bank initiation — Payment Voucher No. ${vnum}`, `<div class="mb-2"><b>${esc(r.payee)}</b> · LKR <b class="num">${money(r.amount)}</b> · ${r.kind === 'tour' ? 'tour ' + esc(r.manual_id || r.tour_id || '') : 'non-tour payment'}${r.aflag === 1 ? ' · already authorised from ' + esc(r.bank_ledger) + ' — initiate from that account' : ''}</div>
        <div class="row g-2"><div class="col-md-7"><label class="form-label small fw-semibold">Paying account</label><select class="form-select form-select-sm" id="in_acct">${accountOptions(r.bank_ledger)}</select></div>
        <div class="col-md-5"><label class="form-label small fw-semibold">Actual payment date</label><input type="date" class="form-control form-control-sm" id="in_date" value="${D.today}" max="${D.today}"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Bank reference</label><input class="form-control form-control-sm" id="in_ref" maxlength="100"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Bank charges (LKR)</label><input class="form-control form-control-sm num" id="in_chg" placeholder="0.00"></div>
        <div class="col-12" id="in_reason_box" style="display:none;"><label class="form-label small fw-semibold">Reason for a different paying account</label><input class="form-control form-control-sm" id="in_reason" maxlength="300"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Note</label><input class="form-control form-control-sm" id="in_note" maxlength="300"></div></div>`,
        `<button class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-p" id="in_go" onclick="doInitiate(${vnum})">Record bank initiation</button>`);
}

async function doInitiate(vnum) {
    if (BUSY) return; BUSY = true; const b = document.getElementById('in_go'); b.disabled = true;
    const r0 = rowOf(vnum); const sel = document.getElementById('in_acct').value; const data = { vnum, amount: r0 ? r0.amount : '', payment_date: document.getElementById('in_date').value, bank_ref: document.getElementById('in_ref').value, charges: document.getElementById('in_chg').value, note: document.getElementById('in_note').value, account_reason: document.getElementById('in_reason').value };
    if (sel.startsWith('id:')) data.bank_account_id = sel.slice(3); else data.ledger_code = sel.slice(3);
    const r = await post(TPD_URL + 'initiate', data);
    BUSY = false; b.disabled = false;
    if (r.needs_account_reason) { document.getElementById('in_reason_box').style.display = ''; say(r.message, 'info'); return; }
    say(r.message || 'No answer.', r.status === 200 ? '' : 'err'); if (r.status === 200) { closeModal(); load(); }
}

function openPaid(vnum) {
    const r = rowOf(vnum); if (!r) return;
    modal(`Confirm Paid — Payment Voucher No. ${vnum}`, `<div class="mb-2"><b>${esc(r.payee)}</b> · LKR <b class="num">${money(r.amount)}</b> + charges ${money(r.charges || 0)} = total debit <b class="num">${money(r.total_debit)}</b><div class="muted">Initiated by ${esc(r.initiated_by)} ${dmyt(r.initiated_at)} · reference ${esc(r.bank_ref || '—')}${r.bank_leg ? ' · the desk also posts the bank leg (Dr Bank Control / Cr the paying account)' : ''}</div></div>
        <div class="row g-2"><div class="col-md-5"><label class="form-label small fw-semibold">Paid date (bank)</label><input type="date" class="form-control form-control-sm" id="pd_date" value="${r.payment_date || D.today}" max="${D.today}"></div>
        <div class="col-md-7"><label class="form-label small fw-semibold">Final bank reference</label><input class="form-control form-control-sm" id="pd_ref" value="${esc(r.bank_ref || '')}" maxlength="100"></div>
        <div class="col-md-5"><label class="form-label small fw-semibold">Bank charges (LKR)</label><input class="form-control form-control-sm num" id="pd_chg" value="${r.charges !== null ? r.charges : ''}"></div>
        <div class="col-md-7"><label class="form-label small fw-semibold">Note</label><input class="form-control form-control-sm" id="pd_note" maxlength="300"></div></div>
        <div class="muted small mt-2">Paid authorises the voucher through the guarded posting (if it is not authorised yet), finalises it, locks the figures, posts the bank charges once, reads the ledger back and creates the supplier's remittance advice.</div>`,
        `<button class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-p" id="pd_go" onclick="doPaid(${vnum})">Paid</button>`);
}

async function doPaid(vnum) {
    if (BUSY) return; BUSY = true; const b = document.getElementById('pd_go'); b.disabled = true;
    const r = await post(TPD_URL + 'paid', { vnum, paid_date: document.getElementById('pd_date').value, bank_ref: document.getElementById('pd_ref').value, charges: document.getElementById('pd_chg').value, note: document.getElementById('pd_note').value });
    BUSY = false; b.disabled = false;
    say(r.message || 'No answer.', r.status === 200 ? '' : 'err'); if (r.status === 200) { closeModal(); load(); }
}

async function runSetup() { const r = await post(PD_URL + 'setup', {}); say(r.message || 'No answer.', r.status === 200 ? '' : 'err'); if (r.status === 200) location.reload(); }

load();
</script>
</body>
</html>
