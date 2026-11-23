<?= view('layouts/head') ?>
<?php
/**
 * tf-finance-r1 (23 Nov 2026, Shantha) — the Finance page (tour/finance).
 * Four compact tabs — Payment Approval, Ready for Bank Payment, Finalized Payments, Bank Reconciliation — and only the
 * selected tab's list below, with its count, search, filters and pages. The data is read-only (tour/finance/data); every
 * action goes to the endpoint that already performs it, so the records, the approvals and the separation of duties stay
 * as they are. The voucher is a preview until it is finalised; printing and the PDF are unlocked only then.
 * Colours (Shantha, 23 Nov 2026): one purple #842484 throughout (frame, tabs, headings, outline buttons), #78B848 for the primary action buttons, white text on colour. The Tour Desk bar is not drawn on this page.
 */
$P = $tpd_perms ?? [];
?>
<style>
    /* tf-finance-r1 (Shantha, 23 Nov 2026): on the Finance page every coloured bar is the Avenir purple #842484 — the system's top bar, its side menu and its notice chip included. This page only; no other page changes. */
    :root { --vz-primary:#842484; --vz-primary-rgb:132,36,132; --vz-primary-text-emphasis:#6f1e6f; --vz-primary-bg-subtle:rgba(132,36,132,.08); --vz-header-bg:#842484; --vz-vertical-menu-bg:#842484; }
    html body #page-topbar#page-topbar, html body .app-topbar.app-topbar, html body #avnl-note, html body .app-menu.navbar-menu, html body .navbar-menu .navbar-brand-box, html body .bg-primary, html body .bg-vertical-gradient { --vz-header-bg:#842484; --vz-header-border:#842484; background:#842484 !important; background-image:none !important; border-color:#842484; }
    html body #avh-root { --avh-p:#6f1e6f; --avh-p2:#842484; } html body #avh-root .avh-fab { background:#842484; } html body #avh-root .avh-fab:hover { background:#6f1e6f; }
    .fin { --bg:#842484; --bg2:#6f1e6f; --pur:#842484; --grn:#78B848; --grn2:#69a53d; --ink:#2a2230; --mut:#6c757d; --line:#e6e0ea; --soft:#f6e9f6; color:var(--ink); font-size:13.5px; }
    .fin a:not([class]), .fin-drawer a:not([class]) { color:#842484; } .fin a:not([class]):hover, .fin-drawer a:not([class]):hover { color:#6f1e6f; }
    .fin .fn-head { background:var(--bg); color:#fff; border-radius:12px 12px 0 0; padding:12px 18px; display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; }
    .fin .fn-head h4 { margin:0; color:#fff; font-weight:700; font-size:18px; letter-spacing:.2px; }
    .fin .fn-brand { display:flex; align-items:center; gap:14px; } .fin .fn-logo { background:#fff; border-radius:8px; padding:5px 10px; display:inline-flex; align-items:center; flex:none; } .fin .fn-logo img { height:34px; width:auto; display:block; }
    .fin .fn-head .sub { color:rgba(255,255,255,.85); font-size:12.5px; }
    .fin .fn-head .hbtn { background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.4); border-radius:8px; padding:5px 12px; font-size:12.5px; font-weight:600; text-decoration:none; cursor:pointer; }
    .fin .fn-head .hbtn:hover { background:rgba(255,255,255,.24); color:#fff; }
    .fin .fn-frame { border:2px solid var(--bg); border-top:0; border-radius:0 0 12px 12px; background:#fff; padding:12px 14px 14px; }
    .fin .fn-tabs { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
    .fin .fn-tab { display:inline-flex; align-items:center; gap:8px; height:36px; padding:0 14px; border-radius:10px; border:1px solid var(--line); background:#fff; color:var(--ink); font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; }
    .fin .fn-tab i { color:var(--bg); font-size:15px; }
    .fin .fn-tab:hover { background:var(--soft); }
    .fin .fn-tab.on { background:var(--bg); border-color:var(--bg); color:#fff; box-shadow:0 3px 10px rgba(132,36,132,.3); }
    .fin .fn-tab.on i { color:#fff; }
    .fin .fn-tab .n { min-width:22px; padding:1px 7px; border-radius:10px; background:var(--soft); color:var(--bg); font-size:11.5px; font-weight:700; text-align:center; }
    .fin .fn-tab.on .n { background:#fff; color:var(--bg); }
    .fin .fn-bar { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:10px; }
    .fin .fn-bar input, .fin .fn-bar select { height:34px; border:1px solid #cfc6d3; border-radius:8px; padding:0 10px; font-size:13px; background:#fff; color:var(--ink); }
    .fin .fn-bar input.q { flex:1 1 240px; min-width:180px; }
    .fin .fn-bar label { font-size:12px; color:var(--mut); margin:0 -2px 0 4px; }
    .fin .btn-g { background:var(--grn); border:1px solid var(--grn); color:#fff; border-radius:8px; padding:6px 14px; font-size:13px; font-weight:700; cursor:pointer; white-space:nowrap; text-decoration:none; display:inline-block; }
    .fin .btn-g:hover { background:var(--grn2); border-color:var(--grn2); color:#fff; } .fin .btn-g:disabled { opacity:.5; cursor:not-allowed; }
    .fin .btn-o { background:#fff; border:1px solid var(--bg); color:var(--bg); border-radius:8px; padding:6px 14px; font-size:13px; font-weight:700; cursor:pointer; white-space:nowrap; text-decoration:none; display:inline-block; }
    .fin .btn-o:hover { background:var(--soft); color:var(--bg); } .fin .btn-o:disabled { opacity:.5; cursor:not-allowed; }
    .fin .btn-q { background:#fff; border:1px solid #cfc6d3; color:var(--ink); border-radius:8px; padding:5px 12px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap; text-decoration:none; display:inline-block; }
    .fin .btn-q:hover { background:#f4f2f5; color:var(--ink); } .fin .btn-q:disabled { opacity:.5; cursor:not-allowed; }
    .fin .fn-wrap { border:1px solid var(--line); border-radius:10px; overflow:auto; }
    .fin table.ft { width:100%; border-collapse:collapse; min-width:860px; }
    .fin table.ft th { background:var(--pur); color:#fff; text-align:left; font-size:11.5px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; padding:8px 10px; white-space:nowrap; }
    .fin table.ft td { padding:8px 10px; border-top:1px solid #efe9f1; vertical-align:middle; }
    .fin table.ft tr.frow:hover td { background:#fbf7fb; }
    .fin .r { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; } .fin .mut { color:var(--mut); font-size:12px; }
    .fin .cat { display:inline-block; min-width:38px; text-align:center; border-radius:6px; padding:2px 7px; font-size:11.5px; font-weight:700; background:#f3e3f3; color:var(--pur); }
    .fin .st { display:inline-block; border-radius:12px; padding:2px 10px; font-size:12px; font-weight:700; white-space:nowrap; }
    .fin .st.w { background:#fff3d6; color:#8a6100; } .fin .st.b { background:#e6eefb; color:#17509a; } .fin .st.g { background:#e8f3de; color:#3f7a1c; } .fin .st.r { background:#fde8e8; color:#b3261e; } .fin .st.p { background:#f3e3f3; color:var(--pur); }
    .fin .fn-foot { display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; margin-top:10px; }
    .fin .pg { display:flex; gap:4px; align-items:center; flex-wrap:wrap; }
    .fin .pg button { min-width:32px; height:30px; border:1px solid var(--line); background:#fff; border-radius:7px; font-size:12.5px; font-weight:600; cursor:pointer; color:var(--ink); }
    .fin .pg button.on { background:var(--bg); border-color:var(--bg); color:#fff; } .fin .pg button:disabled { opacity:.45; cursor:default; }
    .fin .empty { padding:28px 16px; text-align:center; color:var(--mut); }
    .fin .sel { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .fin-drawer { position:fixed; top:0; right:0; bottom:0; width:min(560px, 100vw); background:#fff; box-shadow:-6px 0 24px rgba(30,10,30,.25); z-index:1060; display:none; flex-direction:column; }
    .fin-drawer.on { display:flex; }
    .fin-shade { position:fixed; inset:0; background:rgba(30,10,30,.35); z-index:1055; display:none; } .fin-shade.on { display:block; }
    .fin-drawer .dh { background:#842484; color:#fff; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; gap:10px; }
    .fin-drawer .dh b { font-size:16px; } .fin-drawer .dh button { background:transparent; border:1px solid rgba(255,255,255,.5); color:#fff; border-radius:7px; padding:3px 10px; font-weight:700; cursor:pointer; }
    .fin-drawer .db { padding:14px 16px; overflow:auto; flex:1; }
    .fin-drawer .df { padding:10px 16px; border-top:1px solid var(--line); display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end; }
    .fin-drawer .box { border:1px solid var(--line); border-radius:10px; margin-bottom:12px; overflow:hidden; }
    .fin-drawer .box > h6 { margin:0; background:#842484; color:#fff; font-size:12.5px; font-weight:700; padding:7px 12px; letter-spacing:.02em; }
    .fin-drawer .box > div { padding:10px 12px; }
    .fin-drawer .kv { display:grid; grid-template-columns:150px 1fr; gap:4px 10px; font-size:13px; } .fin-drawer .kv span { color:var(--mut); }
    .fin-drawer .fg { display:grid; grid-template-columns:1fr 1fr; gap:8px 10px; } .fin-drawer .fg .w2 { grid-column:1 / -1; }
    .fin-drawer .fg label { display:block; font-size:12px; font-weight:600; color:#5a5160; margin-bottom:2px; }
    .fin-drawer .fg input, .fin-drawer .fg select, .fin-drawer textarea { width:100%; height:34px; border:1px solid #cfc6d3; border-radius:8px; padding:0 10px; font-size:13px; } .fin-drawer textarea { height:64px; padding:8px 10px; }
    .fin-drawer .draft { border:1.5px dashed #b9a9c2; border-radius:8px; padding:10px 12px; background:#fbf9fc; }
    .fin-drawer .lock { background:#f4f2f5; border-radius:8px; padding:7px 10px; font-size:12.5px; color:#5a5160; }
    .fin-msg { position:fixed; right:16px; bottom:16px; z-index:1080; display:flex; flex-direction:column; gap:8px; max-width:min(440px, 92vw); }
    .fin-msg .m { background:#fff; border:1px solid #e6e0ea; border-left:4px solid #78B848; border-radius:10px; padding:10px 14px; box-shadow:0 6px 18px rgba(16,24,40,.14); font-size:13px; }
    .fin-msg .m.err { border-left-color:#b3261e; }
    @media (max-width:640px) { .fin .fn-bar input[type=date] { flex:1 1 120px; min-width:0; } .fin .fn-bar select { flex:1 1 100%; } .fin .fn-tab { flex:1 1 calc(50% - 6px); justify-content:space-between; padding:0 10px; } .fin-drawer .fg { grid-template-columns:1fr; } .fin-drawer .kv { grid-template-columns:120px 1fr; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content tf" id="tf"><div class="container-fluid">
        <div class="fin" id="fin">
            <div class="fn-head">
                <div class="fn-brand"><span class="fn-logo"><img src="<?= base_url('assets/images/Logo-new.png') ?>" alt="Avenir Holidays — Connecting Cultures"></span><div><h4>Finance</h4><div class="sub">Supplier payments: approval → bank payment → finalized voucher · bank reconciliation</div></div></div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="hbtn" href="<?= base_url('tour/payment_desk') ?>">Payment Desk (full chain)</a>
                    <button class="hbtn" type="button" onclick="load()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                </div>
            </div>
            <div class="fn-frame">
                <?php if (empty($tpd_ready)): ?><div class="alert alert-warning">The Tour Payment Desk is not installed on this system, so Finance has nothing to show.</div><?php endif; ?>
                <div class="fn-tabs" id="fn_tabs"></div>
                <div class="fn-bar" id="fn_bar"></div>
                <div class="fn-wrap"><div id="fn_list"><div class="empty">Loading…</div></div></div>
                <div class="fn-foot"><div class="sel" id="fn_sel"></div><div class="pg" id="fn_pg"></div></div>
            </div>
        </div>
    </div></div></div>
</div>
<div class="fin-shade" id="fn_shade" onclick="closeDrawer()"></div>
<div class="fin fin-drawer" id="fn_drawer"><div class="dh"><b id="fd_title"></b><button type="button" onclick="closeDrawer()">Close ✕</button></div><div class="db" id="fd_body"></div><div class="df" id="fd_foot"></div></div>
<div class="fin-msg" id="fn_msg"></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
const FIN_URL = '<?= base_url('tour/finance/') ?>';
const TPD_URL = '<?= base_url('tour/tour_payment_dashboard/') ?>';
const DBR_URL = '<?= base_url('accounts/daily_bank_rec/') ?>';
const P = <?= json_encode($P) ?>;
const ME = <?= json_encode((string) ($tpd_user ?? '')) ?>;
const CSRF = { name: '<?= csrf_token() ?>', hash: '<?= csrf_hash() ?>' };
const PER = 25;
const TABS = [['approval', 'Payment Approval', 'bi-clipboard-check'], ['ready', 'Ready for Bank Payment', 'bi-bank'], ['final', 'Finalized Payments', 'bi-patch-check'], ['recon', 'Bank Reconciliation', 'bi-arrow-left-right']];
let D = null, TAB = (new URLSearchParams(location.search).get('tab')) || 'approval', PAGE = 1, BUSY = false, PICK = new Set(), F = { q: '', cat: '', from: '', to: '' };
if (!TABS.some(t => t[0] === TAB)) TAB = 'approval';

const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const money = n => n === null || n === undefined || n === '' ? '—' : Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const dmy = s => { if (!s || String(s).startsWith('0000')) return '—'; const d = String(s).slice(0, 10).split('-'); return d.length === 3 ? `${d[2]} ${MON[Number(d[1]) - 1] || d[1]} ${d[0]}` : esc(s); };
const dmyt = s => s ? dmy(s) + ' ' + String(s).slice(11, 16) : '—';
const last4 = s => { const t = String(s ?? '').replace(/\s/g, ''); return t ? '•••• ' + t.slice(-4).replace(/[^0-9A-Za-z]/g, '•') : '—'; };
const tourCell = r => r.tour ? `<b>${esc(r.tour)}</b>${r.inq ? `<div class="mut">INQ ${esc(r.inq)}</div>` : ''}` : '<span class="mut">no tour</span>';
function say(text, kind) { const box = document.getElementById('fn_msg'); const m = document.createElement('div'); m.className = 'm ' + (kind || ''); m.textContent = text; box.appendChild(m); setTimeout(() => m.remove(), kind === 'err' ? 15000 : 7000); }
async function post(url, data) {
    const fd = new FormData(); for (const k in data) { if (Array.isArray(data[k])) data[k].forEach(v => fd.append(k + '[]', v)); else if (data[k] !== undefined && data[k] !== null) fd.append(k, data[k]); } fd.append(CSRF.name, CSRF.hash);
    try { const r = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }); return await r.json(); } catch (e) { return { status: 0, message: 'Network error. Nothing was confirmed — reload the page before trying again.' }; }
}
async function load() {
    const j = await fetch(FIN_URL + 'data', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, cache: 'no-store' }).then(r => r.json()).catch(() => ({ status: 0, message: 'Network error.' }));
    if (j.status !== 200) { document.getElementById('fn_list').innerHTML = `<div class="empty">${esc(j.message || 'The list could not be loaded.')}</div>`; return; }
    D = j.data; PICK = new Set([...PICK].filter(id => D.approval.some(r => r.pr_id === id))); draw();
}
function setTab(t) { TAB = t; PAGE = 1; F = { q: '', cat: '', from: '', to: '' }; history.replaceState(null, '', '?tab=' + t); draw(); }
function setF(k, v) { F[k] = v; PAGE = 1; drawList(); }

// ---- what each tab lists ------------------------------------------------------------------------------------------------------
const dateOf = r => TAB === 'approval' ? (r.due || '') : TAB === 'final' ? (r.paid_date || '') : (r.payment_date || String(r.created || '').slice(0, 10));
const catOf = r => TAB === 'ready' ? (r.kind === 'payable' ? 'To prepare' : r.stage === 'record' ? 'To record' : 'To finalize') : (r.cat || '');
function rows() {
    if (!D) return [];
    const all = TAB === 'recon' ? ((D.recon && D.recon.rows) || []) : (D[TAB] || []);
    if (TAB === 'recon') return all;
    const q = F.q.trim().toLowerCase();
    return all.filter(r => {
        if (q && ![r.payee, r.tour, r.inq, r.vnum, r.pr_id ? '#' + r.pr_id : '', r.batch_no, r.bank_ref, r.invoice_no].some(x => String(x ?? '').toLowerCase().includes(q))) return false;
        if (F.cat && catOf(r) !== F.cat) return false;
        const d = String(dateOf(r) || '').slice(0, 10);
        if (F.from && (!d || d < F.from)) return false;
        if (F.to && (!d || d > F.to)) return false;
        return true;
    });
}
function draw() {
    document.getElementById('fn_tabs').innerHTML = TABS.map(([k, n, ic]) => `<button type="button" class="fn-tab${k === TAB ? ' on' : ''}" onclick="setTab('${k}')"><i class="bi ${ic}"></i>${n}<span class="n">${D ? Number(D.counts[k] || 0) : '–'}</span></button>`).join('');
    const bar = document.getElementById('fn_bar');
    if (TAB === 'recon') { bar.innerHTML = ''; }
    else {
        const all = D ? (D[TAB] || []) : []; const cats = [...new Set(all.map(catOf).filter(Boolean))].sort();
        const dl = TAB === 'approval' ? 'Check-in' : TAB === 'final' ? 'Paid' : 'Date';
        bar.innerHTML = `<input class="q" id="ff_q" placeholder="Search payee, tour, inquiry or voucher" value="${esc(F.q)}" oninput="setF('q', this.value)">
            <select onchange="setF('cat', this.value)"><option value="">${TAB === 'ready' ? 'All steps' : 'All categories'}</option>${cats.map(c => `<option${c === F.cat ? ' selected' : ''}>${esc(c)}</option>`).join('')}</select>
            <label>${dl} from</label><input type="date" value="${esc(F.from)}" onchange="setF('from', this.value)"><label>to</label><input type="date" value="${esc(F.to)}" onchange="setF('to', this.value)">
            <button type="button" class="btn-q" onclick="setTab(TAB)">Clear</button>`;
    }
    drawList();
}
const TH = {
    approval: ['', 'Payee', 'Category', 'Tour / Inquiry', 'Check-in date', 'Amount', 'Status', ''],
    ready: ['Payee', 'Voucher', 'Tour / Inquiry', 'Payment date', 'Amount (LKR)', 'Status', ''],
    final: ['Voucher', 'Payee', 'Category', 'Tour / Inquiry', 'Paid date', 'Amount (LKR)', 'Bank reference', ''],
    recon: ['Bank account', 'Ledger', 'Status', 'Reconciled from', 'Last day prepared', 'Paid here (30 days)', ''],
};
const held = r => !!(r.holds && r.holds.length);
const CAN_HOLD = !!(P.approver || P.initiator), CAN_RELEASE = !!P.approver;   // Hold: approver or accountant; Release: management approver only
// Hold / Release: the existing 'disputed' record of the request — a reason is required both ways, each is written to the audit trail
const holdBox = (prId, h) => box(h ? 'On hold' : 'Hold', (h ? kv([['Reason', esc(h.reason || '—')], ['Held by', `${esc(h.by || '—')} · ${dmyt(h.at)}`]]) + '<div class="lock mt-2 mb-2">A held amount stays outstanding. It cannot enter an individual payment, a supplier batch or a bulk payment, and its bank payment cannot be recorded or finalised, until it is released.</div>' : '') +
    ((h ? CAN_RELEASE : CAN_HOLD) ? `<textarea id="fh_reason_${prId}" maxlength="300" placeholder="${h ? 'Reason for releasing the hold (required)' : 'Reason for the hold (required)'}"></textarea><div class="mt-2"><button type="button" class="btn-o" id="fh_go_${prId}" onclick="doHold(${prId}, ${h ? 0 : 1})">${h ? 'Release Hold' : 'Put on Hold'}</button></div>` : `<span class="mut">${h ? 'Releasing a hold needs the management approval privilege.' : 'A hold needs the approval or the bank-initiation privilege.'}</span>`));
const amt = r => `${r.currency ? esc(r.currency) + ' ' : ''}${money(r.amount)}`;
function rowHtml(r) {
    if (TAB === 'approval') {
        const can = r.status === 'requested';
        return `<tr class="frow"><td>${can && P.approver ? `<input type="checkbox" ${PICK.has(r.pr_id) ? 'checked' : ''} onchange="pick(${r.pr_id}, this.checked)" aria-label="Select request ${r.pr_id}">` : ''}</td>
            <td><b>${esc(r.payee)}</b><div class="mut">Request #${r.pr_id} · ${esc(r.by || '')}</div></td><td><span class="cat" title="${esc(r.type || '')}">${esc(r.cat)}</span></td><td>${tourCell(r)}${r.tour_cancelled ? ' <span class="st r">Tour cancelled</span>' : ''}</td>
            <td>${dmy(r.due)}</td><td class="r"><b>${amt(r)}</b></td><td>${can ? '<span class="st w">Awaiting approval</span>' : '<span class="st r">Waiting for the rate</span>'}${held(r) ? ' <span class="st r">On hold</span>' : ''}</td>
            <td class="r"><button type="button" class="btn-q" onclick="review('approval', ${r.pr_id})">Review →</button></td></tr>`;
    }
    if (TAB === 'ready') {
        const st = r.kind === 'payable' ? '<span class="st p">Approved — to prepare for bank</span>' + (held(r) ? ` <span class="st r">${r.holds.length} on hold</span>` : '') : (r.stage === 'record' ? '<span class="st w">Bank payment to record</span>' : (r.locked ? '<span class="st r">Finalization interrupted — resume</span>' : '<span class="st b">Recorded — awaiting finalization</span>') ) + (held(r) ? ' <span class="st r">On hold</span>' : '');
        return `<tr class="frow"><td><b>${esc(r.payee)}</b>${r.kind === 'payable' ? `<div class="mut">${Number(r.items)} approved item${r.items === 1 ? '' : 's'}</div>` : (r.batch_no ? `<div class="mut">Batch ${esc(r.batch_no)}${r.bulk_parent ? ' · bulk payment' : ''}</div>` : '')}</td>
            <td>${r.vnum ? `<b>PV ${Number(r.vnum)}</b>` : '<span class="mut">not issued yet</span>'}</td><td>${tourCell(r)}</td><td>${r.payment_date ? dmy(r.payment_date) : '<span class="mut">—</span>'}</td>
            <td class="r"><b>${money(r.amount)}</b>${r.usd ? `<div class="mut">USD ${money(r.usd)}</div>` : ''}</td><td>${st}</td>
            <td class="r"><button type="button" class="btn-q" onclick="review('ready', '${esc(r.key)}')">Review →</button></td></tr>`;
    }
    if (TAB === 'final') {
        return `<tr class="frow"><td><b>PV ${Number(r.vnum)}</b></td><td><b>${esc(r.payee)}</b></td><td><span class="cat">${esc(r.cat)}</span></td><td>${tourCell(r)}</td><td>${dmy(r.paid_date)}</td>
            <td class="r"><b>${money(r.amount)}</b>${r.usd ? `<div class="mut">USD ${money(r.usd)}</div>` : ''}</td><td>${esc(r.bank_ref || '—')}</td>
            <td class="r"><button type="button" class="btn-q" onclick="review('final', ${Number(r.vnum)})">View voucher →</button></td></tr>`;
    }
    return `<tr class="frow"><td><b>${esc(r.label || ('Account ' + r.bank_account_id))}</b> <span class="mut">${esc(r.currency || '')}</span></td><td>${esc(r.ledger_code)}</td>
        <td><span class="st ${r.status === 'confirmed' ? 'g' : 'w'}">${esc(r.status)}</span></td><td>${dmy(r.rec_start)}</td><td>${r.last_day ? dmy(r.last_day) + `<div class="mut">${esc(r.last_status || '')}</div>` : '—'}</td>
        <td>${Number(r.desk_paid_30d)} · LKR ${money(r.desk_paid_30d_total)}</td><td class="r">${P.dbr ? `<a class="btn-q" href="${DBR_URL}account/${Number(r.bank_account_id)}">Open →</a>` : ''}</td></tr>`;
}
function drawList() {
    const list = rows(), pages = Math.max(1, Math.ceil(list.length / PER)); if (PAGE > pages) PAGE = pages;
    const part = list.slice((PAGE - 1) * PER, PAGE * PER);
    let empty = { approval: 'No payment is waiting for approval.', ready: 'No approved payment is waiting for the bank.', final: 'No finalized payment yet.', recon: (D && D.recon && D.recon.note) || 'No bank account is set up for the Daily Bank Reconciliation yet.' }[TAB];
    if (!D) empty = 'Loading…';
    if (D && TAB !== 'recon' && (D[TAB] || []).length && !list.length) empty = 'Nothing matches the search and filters.';
    document.getElementById('fn_list').innerHTML = part.length ? `<table class="ft"><thead><tr>${TH[TAB].map((h, i) => `<th${/Amount/.test(h) ? ' class="r"' : ''}>${TAB === 'approval' && i === 0 && P.approver ? '<input type="checkbox" onchange="pickPage(this.checked)" aria-label="Select all on this page">' : h}</th>`).join('')}</tr></thead><tbody>${part.map(rowHtml).join('')}</tbody></table>` : `<div class="empty">${esc(empty)}</div>`;
    const pg = document.getElementById('fn_pg'); let b = `<span class="mut me-2">${list.length ? `${(PAGE - 1) * PER + 1}–${Math.min(PAGE * PER, list.length)} of ${list.length}` : '0 items'}</span>`;
    if (pages > 1) { b += `<button ${PAGE === 1 ? 'disabled' : ''} onclick="go(${PAGE - 1})">‹</button>`; for (let i = 1; i <= pages; i++) { if (i === 1 || i === pages || Math.abs(i - PAGE) <= 2) b += `<button class="${i === PAGE ? 'on' : ''}" onclick="go(${i})">${i}</button>`; else if (Math.abs(i - PAGE) === 3) b += '<span class="mut">…</span>'; } b += `<button ${PAGE === pages ? 'disabled' : ''} onclick="go(${PAGE + 1})">›</button>`; }
    pg.innerHTML = b;
    const sel = document.getElementById('fn_sel');
    if (TAB === 'approval' && P.approver) sel.innerHTML = `<b>${PICK.size} selected</b><button type="button" class="btn-g" ${PICK.size ? '' : 'disabled'} onclick="approveMany()">Approve Selected</button><button type="button" class="btn-o" ${PICK.size ? '' : 'disabled'} onclick="returnMany()">Return with Reason</button>`;
    else if (TAB === 'recon') sel.innerHTML = P.dbr ? `<a class="btn-g" href="${DBR_URL}">Open Daily Bank Reconciliation</a>` : '<span class="mut">The Daily Bank Reconciliation needs its own privilege.</span>';
    else if (TAB === 'ready') sel.innerHTML = P.payables ? `<a class="btn-q" href="${TPD_URL}payables">Older screen: Supplier Payables</a>` : '';
    else sel.innerHTML = '';
}
function go(p) { PAGE = p; drawList(); }
function pick(id, on) { on ? PICK.add(id) : PICK.delete(id); drawList(); }
function pickPage(on) { rows().slice((PAGE - 1) * PER, PAGE * PER).filter(r => r.status === 'requested').forEach(r => on ? PICK.add(r.pr_id) : PICK.delete(r.pr_id)); drawList(); }

// ---- the review panel ---------------------------------------------------------------------------------------------------------
function openDrawer(title, body, foot) { document.getElementById('fd_title').textContent = title; document.getElementById('fd_body').innerHTML = body; document.getElementById('fd_foot').innerHTML = foot || ''; document.getElementById('fn_drawer').classList.add('on'); document.getElementById('fn_shade').classList.add('on'); }
function closeDrawer() { document.getElementById('fn_drawer').classList.remove('on'); document.getElementById('fn_shade').classList.remove('on'); }
const kv = pairs => `<div class="kv">${pairs.filter(Boolean).map(([k, v]) => `<span>${esc(k)}</span><div>${v}</div>`).join('')}</div>`;
const box = (t, inner) => `<div class="box"><h6>${esc(t)}</h6><div>${inner}</div></div>`;
async function payeeAccount(type, id, target) {
    const el = document.getElementById(target); if (!el) return;
    const j = await fetch(`${TPD_URL}account_of?ref_type=${encodeURIComponent(type)}&ref_id=${encodeURIComponent(id)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).catch(() => null);
    const a = j && j.account;
    if (document.getElementById(target) !== el) return;
    el.classList.remove('mut');
    el.innerHTML = a ? kv([['Account holder', esc(a.holder_name || '—')], ['Bank / branch', esc([a.bank_name, a.branch].filter(Boolean).join(' · ') || '—')], ['Account number', esc(last4(a.account_no))], ['Currency', esc(a.currency || '—')],
        ['Check', a.review && a.review.label ? esc(a.review.label) : (a.complete ? 'Complete' : 'Incomplete')]]) : '<span class="mut">No registered bank account is recorded for this payment yet. It is chosen and checked on the existing payment screens before the bank payment.</span>';
}
const accountOptions = sel => ((D.bank_accounts && D.bank_accounts.accounts) || []).map(x => `<option value="${x.id !== null ? 'id:' + x.id : 'lc:' + esc(x.ledger_code)}" ${x.ledger_code === sel ? 'selected' : ''}>${esc(x.label)}${x.account_no_masked ? ' · ' + esc(last4(x.account_no_masked)) : ''}</option>`).join('');
const voucherBtns = (vnum, final) => `<a class="btn-o" href="${TPD_URL}voucher/${Number(vnum)}" target="_blank">${final ? 'Open voucher' : 'Preview voucher'}</a>` + (final ? ` <a class="btn-g" href="${TPD_URL}voucher/${Number(vnum)}?print=1" target="_blank">Print Voucher</a> <a class="btn-g" href="${TPD_URL}voucher/${Number(vnum)}?pdf=1" target="_blank">Download PDF</a>` : ` <button class="btn-q" disabled title="Unlocked when the payment is finalized">Print / Download — after finalization</button>`);

function review(tab, id) {
    if (tab === 'approval') {
        const r = D.approval.find(x => x.pr_id === id); if (!r) return;
        openDrawer('Review & Approve — Request #' + r.pr_id,
            box('Payment request', kv([['Payee', `<b>${esc(r.payee)}</b>`], ['Category', `<span class="cat">${esc(r.cat)}</span> ${esc(r.type || '')}`], ['Tour', r.tour ? `<a href="${TPD_URL}?manual_id=${encodeURIComponent(r.tour)}" target="_blank">${esc(r.tour)}</a>` : '—'], ['Inquiry', r.inq ? 'INQ ' + esc(r.inq) : '—'],
                ['Amount', `<b>${amt(r)}</b>`], ['Check-in date', dmy(r.due)], ['Invoice no.', esc(r.invoice_no || '—')], ['Booking reference', esc(r.booking_ref || '—')], ['Requested by', `${esc(r.by || '—')} · ${dmyt(r.at)}`]])) +
            box('Payee bank account', `<div id="fd_acct" class="mut">Reading…</div>`) +
            box('Voucher preview', `<div class="draft"><b>DRAFT — no voucher number yet</b><div class="mut mb-1">The voucher number is given when the approved payment is prepared for the bank.</div>${kv([['Pay to', esc(r.payee)], ['For', `${esc(r.type || '')} · ${esc(r.tour || '')}${r.inq ? ' · INQ ' + esc(r.inq) : ''}`], ['Amount', amt(r)]])}</div><div class="lock mt-2">Print / Download is unlocked only after finalization.</div>`) +
            holdBox(r.pr_id, held(r) ? r.holds[0] : null) +
            (P.approver && r.status === 'requested' ? box('Return with reason', `<textarea id="fd_reason" maxlength="500" placeholder="Reason (needed only when returning the request)"></textarea>`) : ''),
            P.approver && r.status === 'requested' ? `<button class="btn-o" id="fd_ret" onclick="doReturn(${r.pr_id})">Return with Reason</button><button class="btn-g" id="fd_ok" onclick="doApprove(${r.pr_id})">Approve</button>` : `<span class="mut">${r.status === 'requested' ? 'Approval needs the management approval privilege.' : 'The rate must be confirmed before this request can be approved.'}</span>`);
        payeeAccount('request', r.pr_id, 'fd_acct');
        return;
    }
    if (tab === 'ready') {
        const r = D.ready.find(x => x.key === id); if (!r) return;
        if (r.kind === 'payable') {
            const lines = r.lines || [];
            const lineRows = lines.map(l => `<tr><td><input type="checkbox" class="fb_pick" value="${l.pr_id}" ${l.held ? 'disabled' : 'checked'} onchange="batchSum('${esc(r.key)}')" aria-label="Request ${l.pr_id}"></td><td><b>#${l.pr_id}</b></td><td>${esc(l.tour || '—')}</td><td class="r">${l.usd ? 'USD ' + money(l.usd) : '—'}</td><td class="r">${money(l.lkr)}</td><td>${l.held ? `<span class="st r" title="${esc(l.hold_reason || '')}">On hold</span>` : '<span class="st g">Ready</span>'}</td></tr>`).join('');
            openDrawer('Prepare for Bank Payment — ' + r.payee, box('Approved payments of this payee', kv([['Payee', `<b>${esc(r.payee)}</b>`], ['Approved items', String(Number(r.items))], ['Inquiries', esc(r.inq ? r.inq.split(', ').map(x => 'INQ ' + x).join(', ') : '—')]]) +
                `<div class="fn-wrap mt-2"><table class="ft" style="min-width:0"><thead><tr><th></th><th>Request</th><th>Tour</th><th class="r">Amount</th><th class="r">LKR (approval)</th><th>Status</th></tr></thead><tbody>${lineRows || '<tr><td colspan="6" class="mut">No item.</td></tr>'}</tbody></table></div><div class="mt-2" id="fb_sum"></div>`) +
                box('Payee bank account', `<div class="mut">Shown on the voucher once it is created; the registered account is checked by the system before the voucher is made.</div>`) +
                (P.batch ? box('Create the payment', `<div class="fg"><div class="w2"><label>Suggested bank account for payment</label><select id="fb_acct">${accountOptions('')}</select></div><div class="w2"><label>Note</label><input id="fb_note" maxlength="300"></div></div>
                    <div class="lock mt-2">The selected items go on one payment to this payee. This step gives the Payment Voucher number; the voucher stays a preview until the bank payment is finalized.${r.rate_missing ? ' A rate is missing for at least one item.' : ''}</div>`) : '') +
                lines.map(l => holdBox(l.pr_id, l.held ? (r.holds || []).find(h => h.pr_id === l.pr_id) || { reason: l.hold_reason } : null).replace('<h6>', `<h6>Request #${l.pr_id} — `)).join(''),
                P.batch ? `<button class="btn-g" id="fb_go" onclick="doBatch('${esc(r.key)}')">Create Payment Voucher</button>` : '<span class="mut">Creating the payment needs the batch privilege (management).</span>');
            batchSum(r.key);
            return;
        }
        const head = box('Payment', kv([['Voucher', `<b>PV ${Number(r.vnum)}</b>`], ['Payee', `<b>${esc(r.payee)}</b>`], ['Tour', esc(r.tour || (r.non_tour ? 'non-tour payment' : '—'))], ['Inquiry', r.inq ? 'INQ ' + esc(r.inq) : '—'], ['Amount (LKR)', `<b>${money(r.amount)}</b>`], r.usd ? ['Amount (USD)', money(r.usd)] : null,
            r.batch_no ? ['Batch', esc(r.batch_no)] : null])) + box('Payee bank account', `<div id="fd_acct" class="mut">Reading…</div>`) + box('Voucher', voucherBtns(r.vnum, false)) +
            (r.pr_ids || []).map(id => holdBox(id, (r.holds || []).find(h => h.pr_id === id) || null).replace('<h6>', `<h6>Request #${id} — `)).join('');
        if (r.bulk_parent) {
            openDrawer('Bank payment — PV ' + r.vnum, head + `<div class="lock">This voucher is part of one bulk payment (one bank transfer for several vouchers). The bank details and the finalization are entered once, for the whole payment.</div>`, `<a class="btn-g" href="${TPD_URL}bulk/${Number(r.bulk_parent)}">Open the bulk payment</a>`);
        } else if (r.stage === 'record') {
            openDrawer('Record Bank Payment — PV ' + r.vnum, head + box('Record bank payment', `<div class="fg">
                <div class="w2"><label>Bank account for payment</label><select id="fr_acct">${accountOptions(r.bank_ledger)}</select></div>
                <div><label>Actual payment date</label><input type="date" id="fr_date" value="${D.today}" max="${D.today}"></div>
                <div><label>Transaction reference</label><input id="fr_ref" maxlength="100"></div>
                <div><label>Amount paid (LKR)</label><input value="${money(r.amount)}" disabled></div>
                <div><label>Bank charges (LKR)</label><input id="fr_chg" placeholder="0.00"></div>
                <div class="w2" id="fr_rate_box" style="display:none"><label>USD rate of the payment date (LKR per 1 USD)</label><input id="fr_rate" inputmode="decimal" placeholder="for example 327.25"></div>
                <div class="w2" id="fr_reason_box" style="display:none"><label>Reason for a different paying account</label><input id="fr_reason" maxlength="300"></div>
                <div class="w2"><label>Note</label><input id="fr_note" maxlength="300"></div></div>`),
                P.initiate ? `<button class="btn-g" id="fr_go" onclick="doRecord(${Number(r.vnum)})">Save Payment Details</button>` : '<span class="mut">Recording the bank payment needs the bank-initiation privilege.</span>');
        } else {
            openDrawer('Review & Finalize — PV ' + r.vnum, head + box('Bank payment recorded', kv([['Recorded by', `${esc(r.initiated_by || '—')} · ${dmyt(r.initiated_at)}`], ['Payment date', dmy(r.payment_date)], ['Transaction reference', esc(r.bank_ref || '—')], ['Bank charges', money(r.charges || 0)], ['Total debit (LKR)', `<b>${money(r.total_debit)}</b>`]])) +
                box('Finalize', `<div class="fg"><div class="w2"><label>Bank account the payment was made from</label><select id="fz_acct">${accountOptions(r.init_ledger || r.bank_ledger)}</select></div><div><label>Paid date (bank)</label><input type="date" id="fz_date" value="${esc(r.payment_date || D.today)}" max="${D.today}"></div><div><label>Final bank reference</label><input id="fz_ref" maxlength="100" value="${esc(r.bank_ref || '')}"></div>
                <div><label>Bank charges (LKR)</label><input id="fz_chg" value="${r.charges !== null && r.charges !== undefined ? esc(r.charges) : ''}"></div><div><label>Note</label><input id="fz_note" maxlength="300"></div>
                <div class="w2" id="fz_rate_box" style="display:none"><label>USD rate of the paid date (LKR per 1 USD)</label><input id="fz_rate" inputmode="decimal" placeholder="for example 327.25"></div>
                <div class="w2" id="fz_reason_box" style="display:none"><label>Reason for a different paying account</label><input id="fz_reason" maxlength="300"></div>
                <div class="w2"><label style="font-weight:400"><input type="checkbox" id="fz_conf" style="width:auto;height:auto;margin-right:6px">I have checked the paying account and the payee account shown above.</label></div></div>
                <div class="lock mt-2">Finalizing marks the payment Paid, posts it to the accounts once and unlocks the voucher for printing. It must be done by a different person from the one who recorded the bank payment.</div>`),
                P.bank_approver ? `<button class="btn-g" id="fz_go" onclick="doFinalize(${Number(r.vnum)})">Review &amp; Finalize</button>` : '<span class="mut">Finalizing needs the bank payment approver privilege.</span>');
        }
        payeeAccount('voucher', r.vnum, 'fd_acct');
        return;
    }
    const r = D.final.find(x => x.vnum === id); if (!r) return;
    openDrawer('Final Voucher — PV ' + r.vnum, box('Finalized payment', kv([['Voucher', `<b>PV ${Number(r.vnum)}</b> <span class="st g">Finalized</span>`], ['Payee', `<b>${esc(r.payee)}</b>`], ['Tour', esc(r.tour || '—')], ['Inquiry', r.inq ? 'INQ ' + esc(r.inq) : '—'], ['Paid date', dmy(r.paid_date)],
        ['Amount (LKR)', `<b>${money(r.amount)}</b>`], r.usd ? ['Amount (USD)', money(r.usd)] : null, ['Bank charges', money(r.charges || 0)], ['Total debit (LKR)', money(r.total_debit)], ['Bank reference', esc(r.bank_ref || '—')]])) +
        box('Signatures on record', kv([['Prepared by', esc(r.prepared_by || '—')], ['Approved by', esc(r.mgmt_by || '—')], ['Bank payment recorded by', esc(r.recorded_by || '—')], ['Finalized by', `${esc(r.finalized_by || '—')} · ${dmyt(r.finalized_at)}`]])), voucherBtns(r.vnum, true));
}

// ---- the actions: each goes to the endpoint that already does it -----------------------------------------------------------------
async function busy(ids, fn) { if (BUSY) return; BUSY = true; ids.forEach(i => { const b = document.getElementById(i); if (b) b.disabled = true; }); try { await fn(); } finally { BUSY = false; ids.forEach(i => { const b = document.getElementById(i); if (b) b.disabled = false; }); } }
function doApprove(id) { busy(['fd_ok', 'fd_ret'], async () => { const r = await post(TPD_URL + 'approve', { pr_id: id }); say(r.message || (r.status === 200 ? 'Approved.' : 'No answer.'), r.status === 200 ? '' : 'err'); if (r.status === 200) { PICK.delete(id); closeDrawer(); await load(); } }); }
function doReturn(id) {
    const reason = document.getElementById('fd_reason').value.trim(); if (!reason) { say('Write the reason before returning the request.', 'err'); document.getElementById('fd_reason').focus(); return; }
    busy(['fd_ok', 'fd_ret'], async () => { const r = await post(TPD_URL + 'reject', { pr_id: id, reason }); say(r.message || (r.status === 200 ? 'Returned.' : 'No answer.'), r.status === 200 ? '' : 'err'); if (r.status === 200) { PICK.delete(id); closeDrawer(); await load(); } });
}
async function approveMany() {
    const ids = [...PICK]; if (!ids.length || BUSY) return; if (!confirm(`Approve ${ids.length} selected payment request${ids.length === 1 ? '' : 's'}?`)) return;
    BUSY = true; let ok = 0; const bad = [];
    for (const id of ids) { const r = await post(TPD_URL + 'approve', { pr_id: id }); if (r.status === 200) { ok++; PICK.delete(id); } else bad.push(`#${id}: ${r.message || 'no answer'}`); }
    BUSY = false; if (ok) say(`${ok} request${ok === 1 ? '' : 's'} approved.`); bad.forEach(m => say(m, 'err')); await load();
}
async function returnMany() {
    const ids = [...PICK]; if (!ids.length || BUSY) return; const reason = (prompt(`Reason for returning ${ids.length} selected request${ids.length === 1 ? '' : 's'}:`) || '').trim(); if (!reason) return;
    BUSY = true; let ok = 0; const bad = [];
    for (const id of ids) { const r = await post(TPD_URL + 'reject', { pr_id: id, reason }); if (r.status === 200) { ok++; PICK.delete(id); } else bad.push(`#${id}: ${r.message || 'no answer'}`); }
    BUSY = false; if (ok) say(`${ok} request${ok === 1 ? '' : 's'} returned.`); bad.forEach(m => say(m, 'err')); await load();
}
// a USD payment needs the confirmed rate of its date: the existing rule. The field appears only when the system asks for it.
function needRate(pfx, r) { document.getElementById(pfx + '_rate_box').style.display = ''; document.getElementById(pfx + '_rate').focus(); say(r.message || 'The USD rate of that day is needed.', 'err'); }
async function rateFirst(pfx, date) {
    const box = document.getElementById(pfx + '_rate_box'), v = document.getElementById(pfx + '_rate').value.trim();
    if (box.style.display === 'none' || !v) return true;
    const r = await post(TPD_URL + 'confirm_rate', { rate: v, date });
    if (r.status !== 200) { say(r.message || 'The rate could not be saved.', 'err'); return false; }
    return true;
}
function batchSum(key) {
    const r = D.ready.find(x => x.key === key), el = document.getElementById('fb_sum'); if (!r || !el) return;
    const ids = [...document.querySelectorAll('.fb_pick:checked')].map(c => Number(c.value)); const pickd = (r.lines || []).filter(l => ids.includes(l.pr_id));
    const rest = (r.lines || []).filter(l => !ids.includes(l.pr_id));
    el.innerHTML = `<b>${pickd.length} selected</b> · LKR ${money(pickd.reduce((a, l) => a + (l.lkr || 0), 0))}${pickd.some(l => l.usd) ? ' · USD ' + money(pickd.reduce((a, l) => a + (l.usd || 0), 0)) : ''}` + (rest.length ? ` <span class="mut">· ${rest.length} item${rest.length === 1 ? ' stays' : 's stay'} outstanding (LKR ${money(rest.reduce((a, l) => a + (l.lkr || 0), 0))})</span>` : '');
}
function doBatch(key) {
    const r = D.ready.find(x => x.key === key); if (!r) return;
    const ids = [...document.querySelectorAll('.fb_pick:checked')].map(c => Number(c.value)); if (!ids.length) { say('Select at least one approved item.', 'err'); return; }
    busy(['fb_go'], async () => {
        const sel = document.getElementById('fb_acct').value; const data = { sup_acc: r.sup_acc, pr_ids: ids, note: document.getElementById('fb_note').value };
        if (sel.startsWith('id:')) data.bank_account_id = sel.slice(3); else if (sel) data.ledger_code = sel.slice(3);
        const j = await post(TPD_URL + 'batch_create', data);
        say(j.message || 'No answer.', j.status === 200 ? '' : 'err'); if (j.status === 200) { closeDrawer(); await load(); }
    });
}
function doHold(prId, on) {
    const reason = document.getElementById('fh_reason_' + prId).value.trim(); if (!reason) { say(on ? 'Write the reason for the hold.' : 'Write the reason for releasing the hold.', 'err'); document.getElementById('fh_reason_' + prId).focus(); return; }
    busy(['fh_go_' + prId], async () => {
        const j = await post(TPD_URL + 'dispute', { pr_id: prId, disputed: on, reason });
        say(j.status === 200 ? (on ? 'Request #' + prId + ' is on hold.' : 'The hold on request #' + prId + ' is released.') : (j.message || 'No answer.'), j.status === 200 ? '' : 'err'); if (j.status === 200) { closeDrawer(); await load(); }
    });
}
function doRecord(vnum) {
    busy(['fr_go'], async () => {
        const r0 = D.ready.find(x => x.vnum === vnum); const sel = document.getElementById('fr_acct').value;
        const data = { vnum, amount: r0 ? r0.amount : '', payment_date: document.getElementById('fr_date').value, bank_ref: document.getElementById('fr_ref').value, charges: document.getElementById('fr_chg').value, note: document.getElementById('fr_note').value, account_reason: document.getElementById('fr_reason').value };
        if (sel.startsWith('id:')) data.bank_account_id = sel.slice(3); else if (sel) data.ledger_code = sel.slice(3);
        if (!(await rateFirst('fr', data.payment_date))) return;
        const r = await post(TPD_URL + 'initiate', data);
        if (r.needs_rate) { needRate('fr', r); return; }
        if (r.needs_account_reason) { document.getElementById('fr_reason_box').style.display = ''; say(r.message, 'err'); return; }
        say(r.message || 'No answer.', r.status === 200 ? '' : 'err'); if (r.status === 200) { closeDrawer(); await load(); }
    });
}
function doFinalize(vnum) {
    busy(['fz_go'], async () => {
        if (!(await rateFirst('fz', document.getElementById('fz_date').value))) return;
        const data = { vnum, paid_date: document.getElementById('fz_date').value, bank_ref: document.getElementById('fz_ref').value, charges: document.getElementById('fz_chg').value, note: document.getElementById('fz_note').value,
            account_reason: document.getElementById('fz_reason').value, confirm_account: document.getElementById('fz_conf').checked ? 1 : 0 };
        const sel = document.getElementById('fz_acct').value; if (sel.startsWith('id:')) data.bank_account_id = sel.slice(3); else if (sel) data.ledger_code = sel.slice(3);
        const r = await post(TPD_URL + 'paid', data);
        if (r.needs_rate) { needRate('fz', r); return; }
        if (r.needs_account_reason) { document.getElementById('fz_reason_box').style.display = ''; say(r.message, 'err'); return; }
        say(r.message || 'No answer.', r.status === 200 ? '' : 'err'); if (r.status === 200) { closeDrawer(); TAB = 'final'; PAGE = 1; history.replaceState(null, '', '?tab=final'); await load(); }
    });
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
draw(); load();
</script>
</body>
</html>
