<?php
/**
 * tf-pa-upcoming-r1 (22 Oct 2026) — Payment Approval: the uniform look, the four status cards, the "Next 15 days"
 * review list (expands under the cards) and one search across the page.
 * Included once from pages/tour/payment_approval_v2.php, above Payment Requests. Read-only: the figures come from
 * PaymentApprovalUpcomingController (the Tour Payment Dashboard's own statuses and amounts); every action is a link to an
 * existing screen or opens an existing request on this page, where the existing approval controls and permissions apply.
 */
$pauBase = rtrim(base_url(), '/') . '/';
?>
<style id="pau-css">
/* ---- the uniform: white page, dark burgundy and green, clean type ---- */
body.bg-light, body { background:#fff !important; }
:root { --pau-p:#842484; --pau-pd:#842484; --pau-psoft:rgba(132,36,132,.08); --pau-pline:rgba(132,36,132,.22);   /* tf-pa-tabs-r1: the User Manual button's purple (assets/help/help-widget.css --avh-p2 / --avh-p) */
        --pau-b:#842484; --pau-bd:#842484;   /* tf-pa-tabs-r1b: one colour on this page — the burgundy accents follow the purple */ --pau-g:#78b848; --pau-gl:rgba(120,184,72,.18); --pau-ink:#26262c; --pau-mut:#6b6f7b; --pau-line:#e9e4e6; --pau-soft:rgba(132,36,132,.05); --pau-amber:#9a6200; --pau-amberbg:#fdf2dc; }
/* tf-pa-tabs-r1b: on this page the header bar is the same purple as the section bars (the shared bar stays burgundy elsewhere) */
html body #page-topbar { background:var(--pau-p) !important; border-bottom-color:#6d1d6d !important; box-shadow:0 2px 14px rgba(75,42,92,.28) !important; }
html body #page-topbar .avbar-brand { border-right-color:rgba(255,255,255,.3); }
html body #page-topbar .avbar-link:hover, html body #page-topbar .btn-ghost-secondary:hover { background:rgba(255,255,255,.14) !important; }
html body #page-topbar .avps-box:focus-within { border-color:#f5c542; box-shadow:0 0 0 .2rem rgba(245,197,66,.35); }
.page-content .pd-page-header { background:#fff !important; border:1px solid var(--pau-line); border-left:5px solid var(--pau-p); box-shadow:0 2px 10px rgba(132,36,132,.05) !important; padding:1rem 1.25rem; margin-bottom:1rem; }
.page-content .pd-page-header .pd-title-block h4 { color:var(--pau-pd) !important; font-family:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; font-size:1.45rem; }
.page-content .pd-page-header .pd-title-block h4 i { color:var(--pau-p); opacity:1 !important; }
.page-content .pd-page-header .pd-title-block small { color:var(--pau-mut) !important; font-size:.85rem; }
.page-content .pd-date-pill { background:#fff !important; color:var(--pau-ink) !important; border:1px solid var(--pau-line) !important; }
.page-content .pd-date-pill i { color:var(--pau-b); }
.page-content #usdRateEditBtn { color:var(--pau-b) !important; }
.page-content .pd-bank-chip { border:1px solid var(--pau-line) !important; box-shadow:none !important; border-radius:12px; }
.page-content .pd-bank-icon { background:rgba(132,36,132,.08) !important; color:var(--pau-b) !important; }
.page-content .pd-section { box-shadow:0 1px 3px rgba(132,36,132,.06),0 6px 18px rgba(132,36,132,.05) !important; border:1px solid var(--pau-line) !important; border-radius:14px !important; }
.page-content .pd-header-requests, .page-content .pd-header-utility, .page-content .pd-header-expenses, .page-content .pd-header-allocation { background:var(--pau-p) !important; color:#fff; }
.page-content .pd-section-header h5, .page-content .pd-section-header .label-sub { color:#fff !important; }
.page-content #payeeTable > thead > tr > th, .page-content #approvedExpensesTable > thead > tr > th, .page-content #utilityBillTable > thead > tr > th, .page-content #driverAllocationRequestsTable > thead > tr > th,
.page-content .payee-detail-inner-table > thead > tr > th { background:var(--pau-p) !important; color:#fff !important; border-color:var(--pau-p) !important; }
.page-content .pau-table th { background:var(--pau-p); color:#fff; border-bottom-color:var(--pau-p); }
.page-content #payeeTable > tbody > tr, .page-content #approvedExpensesTable > tbody > tr, .page-content .payee-detail-inner-table > tbody > tr > td { background:#fff; color:var(--pau-ink); }
.page-content .payee-detail-inner-table.table-striped > tbody > tr:nth-of-type(odd) > td { background:#fff; --bs-table-accent-bg:transparent; }
.page-content .payee-group-row:hover td { background:var(--pau-psoft) !important; }
.page-content .payee-count-badge { background:var(--pau-psoft) !important; color:var(--pau-p) !important; }
.page-content .payee-group-row .chevron-icon { background:var(--pau-psoft) !important; color:var(--pau-p) !important; }
.page-content .payee-group-row.expanded .chevron-icon { background:var(--pau-p) !important; color:#fff !important; }
.page-content .payee-detail-wrap { background:var(--pau-psoft) !important; border-color:var(--pau-pline) !important; }
.page-content .payee-detail-footer { background:#fff !important; border-top:1px solid var(--pau-pline) !important; }
.page-content .payee-selected-count { color:var(--pau-p) !important; }
.page-content #cbv_summary { background:var(--pau-psoft) !important; border-color:var(--pau-pline) !important; color:var(--pau-pd) !important; }
.page-content .pd-header-requests .label-sub::after { content:' · click a payee to open its requests'; font-weight:400; opacity:.8; }
/* the status pills on the group rows */
.pau-st { display:inline-block; margin-left:8px; padding:2px 9px; border-radius:20px; font-size:.72rem; font-weight:600; vertical-align:middle; white-space:nowrap; }
.pau-st.aw { background:rgba(132,36,132,.12); color:var(--pau-b); }
.pau-st.ok { background:var(--pau-gl); color:var(--pau-g); }
/* the category tabs */
.pau-tabs { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 14px; }
.pau-tab { border:1px solid var(--pau-pline); background:#fff; color:var(--pau-pd); border-radius:999px; padding:8px 16px; font-weight:600; font-size:.9rem; display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-family:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; }
.pau-tab:hover { background:var(--pau-psoft); }
.pau-tab .n { background:var(--pau-psoft); color:var(--pau-p); border-radius:12px; padding:1px 8px; font-size:.78rem; }
.pau-tab[aria-selected="true"] { background:var(--pau-p); border-color:var(--pau-p); color:#fff; }
.pau-tab[aria-selected="true"] .n { background:rgba(255,255,255,.2); color:#fff; }
.pau-tab:focus-visible { outline:3px solid #f5c542; outline-offset:2px; }
.pau-tabnote { font-size:.8rem; color:var(--pau-mut); margin:-6px 0 14px; }
.pau-hidden-sec { display:none !important; }
.page-content .pd-section-header { padding:.8rem 1.25rem !important; }
.page-content #payeeSearchInput, .page-content #driverSearchInput { display:none !important; }   /* tf-pa-tabs-r2: one main search box */
.page-content .pd-section .table-responsive { overflow-x:auto; }
.page-content .payee-detail-inner-table th, .page-content .payee-detail-inner-table td { white-space:nowrap; }

/* ---- cards ---- */
.pau-cards { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin:0 0 14px; }
.pau-card { display:flex; align-items:center; gap:14px; padding:14px 16px; background:#fff; border:1px solid var(--pau-line); border-radius:12px; min-height:88px; font-family:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; text-align:left; color:var(--pau-ink); }
.pau-card .ic { flex:none; width:46px; height:46px; border-radius:50%; display:grid; place-items:center; font-size:1.3rem; }
.pau-card .tt { font-weight:700; font-size:.98rem; line-height:1.2; }
.pau-card .sb { font-size:.78rem; color:var(--pau-mut); line-height:1.2; margin-top:1px; }
.pau-card .nn { font-size:1.7rem; font-weight:800; line-height:1.05; margin-top:4px; letter-spacing:-.02em; }
.pau-card .nn small { font-size:.85rem; font-weight:500; color:var(--pau-mut); margin-left:5px; letter-spacing:0; }
.pau-card.ok .ic { background:var(--pau-gl); color:var(--pau-g); } .pau-card.ok .nn { color:var(--pau-g); }
.pau-card.pend .ic { background:rgba(132,36,132,.08); color:var(--pau-b); } .pau-card.pend .nn { color:var(--pau-b); }
.pau-card.adv .ic { background:var(--pau-amberbg); color:var(--pau-amber); } .pau-card.adv .nn { color:var(--pau-amber); }
button.pau-card.next { width:100%; cursor:pointer; border:1.5px solid var(--pau-b); background:var(--pau-soft); position:relative; transition:box-shadow .15s, background .15s; }
button.pau-card.next:hover { box-shadow:0 6px 16px rgba(132,36,132,.16); background:#fff; }
button.pau-card.next:focus-visible { outline:3px solid #e9c46a; outline-offset:2px; }
button.pau-card.next .ic { background:rgba(132,36,132,.16); color:var(--pau-b); }
button.pau-card.next .tt, button.pau-card.next .nn { color:var(--pau-b); }
button.pau-card.next .chev { position:absolute; right:12px; top:12px; color:var(--pau-b); transition:transform .15s; }
button.pau-card.next[aria-expanded="true"] .chev { transform:rotate(180deg); }
.pau-per { font-size:.72rem; color:var(--pau-mut); margin-top:2px; }

/* ---- the review list ---- */
.pau-sec { border:1.5px solid var(--pau-p); border-radius:12px; background:#fff; padding:14px 16px 16px; margin:0 0 16px; }
.pau-sec[hidden] { display:none; }
.pau-head { display:flex; align-items:center; gap:10px; }
.pau-head h5 { margin:0; color:var(--pau-p); font-weight:700; font-size:1.12rem; font-family:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; }
.pau-close { margin-left:auto; border:0; background:none; color:var(--pau-b); font-weight:600; display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:8px; }
.pau-close:hover { background:rgba(132,36,132,.08); }
.pau-ctl { display:flex; flex-wrap:wrap; align-items:center; gap:10px 14px; margin:12px 0 6px; font-size:.88rem; }
.pau-ctl label { font-weight:600; color:var(--pau-ink); margin:0; }
.pau-ctl select, .pau-ctl input[type=month] { border:1px solid #d9d2d5; border-radius:8px; padding:6px 10px; font-size:.88rem; background:#fff; min-width:150px; }
.pau-range { font-weight:600; color:var(--pau-ink); padding-left:12px; border-left:1px solid var(--pau-line); }
.pau-note { color:var(--pau-mut); font-size:.8rem; margin:0 0 10px; }
.pau-prog { font-size:.82rem; color:var(--pau-mut); display:flex; align-items:center; gap:8px; margin:6px 0 10px; }
.pau-prog .sp { width:14px; height:14px; border:2px solid rgba(132,36,132,.30); border-top-color:var(--pau-b); border-radius:50%; animation:pau-rot .7s linear infinite; }
@keyframes pau-rot { to { transform:rotate(360deg); } }
.pau-table { width:100%; border-collapse:separate; border-spacing:0; font-size:.86rem; }
.pau-table th { background:#f6f4f5; color:#4a4d57; font-weight:600; padding:8px 10px; border-bottom:1px solid var(--pau-line); white-space:nowrap; position:sticky; top:0; }
.pau-table td { padding:8px 10px; border-bottom:1px solid #f0ecee; vertical-align:middle; }
.pau-table tr.tg td { background:var(--pau-soft); border-top:1px solid var(--pau-line); font-weight:600; padding:9px 10px; }
.pau-table tr.tg .tno { color:var(--pau-b); font-weight:800; white-space:nowrap; }
.pau-table tr.tg .mut { color:var(--pau-mut); font-weight:500; }
.pau-table tr.tg.done td { background:rgba(120,184,72,.12); }
.pau-table tr.tg .rv { display:inline-flex; align-items:center; gap:6px; font-weight:500; font-size:.8rem; color:var(--pau-g); cursor:pointer; white-space:nowrap; }
.pau-table td.num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.pau-pill { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.76rem; font-weight:600; white-space:nowrap; }
.pau-pill.awaiting { background:rgba(132,36,132,.12); color:var(--pau-b); }
.pau-pill.rate { background:#e9eefb; color:#2c4a9a; }
.pau-pill.not_submitted { background:var(--pau-amberbg); color:var(--pau-amber); }
.pau-act { display:inline-flex; align-items:center; gap:5px; border-radius:8px; padding:5px 12px; font-size:.8rem; font-weight:600; text-decoration:none !important; white-space:nowrap; border:1px solid transparent; }
.pau-act.rev { background:var(--pau-b); color:#fff !important; }
.pau-act.rev:hover { background:var(--pau-bd); }
.pau-act.sub { border-color:#e3c48a; color:var(--pau-amber) !important; background:#fff; }
.pau-act.sub:hover { background:var(--pau-amberbg); }
.pau-empty { padding:16px; text-align:center; color:var(--pau-mut); }
.pau-wrap { max-height:62vh; overflow:auto; border:1px solid var(--pau-line); border-radius:10px; }

/* ---- one search across the page ---- */
.pau-search { display:flex; align-items:center; gap:10px; margin:0 0 16px; flex-wrap:wrap; }
.pau-search > label { font-weight:700; font-size:1rem; color:var(--pau-ink); margin:0 6px 0 0; font-family:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; }
.pau-search .box { flex:1; min-width:240px; position:relative; }
.pau-search .box i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--pau-mut); }
.pau-search .box input { width:100%; height:42px; border:1px solid #d9d2d5; border-radius:9px; padding:0 12px 0 36px; font-size:.92rem; }
.pau-search .box input:focus { outline:none; border-color:var(--pau-b); box-shadow:0 0 0 .2rem rgba(132,36,132,.14); }
.pau-search .go { height:42px; padding:0 22px; border:0; border-radius:9px; background:var(--pau-b); color:#fff; font-weight:600; }
.pau-search .go:hover { background:var(--pau-bd); }
.pau-search .rs { height:42px; padding:0 18px; border:1px solid #d9d2d5; border-radius:9px; background:#fff; color:var(--pau-ink); font-weight:600; }
.pau-search .res { width:100%; font-size:.8rem; color:var(--pau-mut); }
tr.pau-hit > td { animation:pau-flash 2.4s ease; }
@keyframes pau-flash { 0%,40% { background:#fbe8b8; } 100% { background:inherit; } }
@media (max-width:1199px) { .pau-cards { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (max-width:575px) { .pau-cards { grid-template-columns:1fr; } .pau-range { border-left:0; padding-left:0; } }
@media print { .pau-search, .pau-close, .pau-act { display:none !important; } }
</style>

<div class="pau-cards" id="pauCards">
    <div class="pau-card ok"><span class="ic"><i class="bi bi-check-lg"></i></span><div><div class="tt">Fully paid</div><div class="nn" data-k="fully_paid">…<small>tours</small></div><div class="pau-per" data-per></div></div></div>
    <div class="pau-card pend"><span class="ic"><i class="bi bi-clock-history"></i></span><div><div class="tt">Pending</div><div class="nn" data-k="pending">…<small>tours</small></div><div class="pau-per" data-per></div></div></div>
    <div class="pau-card adv"><span class="ic"><i class="bi bi-wallet2"></i></span><div><div class="tt">Advance payment</div><div class="nn" data-k="advance">…<small>tours</small></div><div class="pau-per" data-per></div></div></div>
    <button type="button" class="pau-card next" id="pauToggle" aria-expanded="false" aria-controls="pauSection">
        <span class="ic"><i class="bi bi-calendar3"></i></span>
        <div><div class="tt" id="pauCardTitle">Next 15 days</div><div class="sb">Awaiting payment approval</div><div class="nn" data-k="unapproved">…<small>tours</small></div></div>
        <i class="bi bi-chevron-down chev" aria-hidden="true"></i>
    </button>
</div>

<section class="pau-sec" id="pauSection" hidden aria-labelledby="pauTitle">
    <div class="pau-head">
        <h5 id="pauTitle">Upcoming tours · Unapproved payment items</h5>
        <button type="button" class="pau-close" id="pauClose"><span>Close</span><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="pau-ctl">
        <label for="pauPeriod">Period:</label>
        <select id="pauPeriod">
            <option value="next15">Next 15 days (rolling)</option>
            <option value="h1">1st–15th of the month</option>
            <option value="h2">16th–month end</option>
        </select>
        <span id="pauMonthWrap" hidden><label for="pauMonth" class="me-2">Month:</label><input type="month" id="pauMonth" value="<?= date('Y-m') ?>"></span>
        <span class="pau-range" id="pauRange">—</span>
    </div>
    <p class="pau-note">Confirmed tours arriving in the period. Includes items not yet submitted; approved, paid and cancelled items are left out. Statuses and amounts are the Tour Payment Dashboard's. Driver settlements and daily bata are reviewed under Approved Driver Expenses below.</p>
    <div class="pau-prog" id="pauProg" hidden><span class="sp"></span><span class="t"></span></div>
    <div class="pau-wrap">
        <table class="pau-table" id="pauTable">
            <thead><tr><th>Tour No</th><th>Arrival</th><th>Agent No / Name</th><th>Client</th><th>Supplier / Payee</th><th>Type</th><th>Currency</th><th class="text-end">Amount</th><th>Status</th><th>Action</th></tr></thead>
            <tbody><tr><td colspan="10" class="pau-empty">Open the card to load the tours.</td></tr></tbody>
        </table>
    </div>
</section>

<div class="pau-search" role="search">
    <label for="pauQ">Search payments</label>
    <div class="box"><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="pauQ" placeholder="Tour number, agent number or name, client name, driver or supplier name" autocomplete="off"></div>
    <button type="button" class="go" id="pauGo">Search</button>
    <button type="button" class="rs" id="pauReset">Reset</button>
    <div class="res" id="pauRes" aria-live="polite"></div>
</div>

<div class="pau-tabs" id="pauTabs" role="tablist" aria-label="Payment category">
    <button type="button" class="pau-tab" role="tab" data-cat="all" aria-selected="true">All <span class="n"></span></button>
    <button type="button" class="pau-tab" role="tab" data-cat="advance" aria-selected="false">Driver Advances <span class="n"></span></button>
    <button type="button" class="pau-tab" role="tab" data-cat="settlement" aria-selected="false">Driver Settlements <span class="n"></span></button>
    <button type="button" class="pau-tab" role="tab" data-cat="hotel" aria-selected="false">Hotel Payments <span class="n"></span></button>
    <button type="button" class="pau-tab" role="tab" data-cat="restaurant" aria-selected="false">Restaurants <span class="n"></span></button>
    <button type="button" class="pau-tab" role="tab" data-cat="entrance" aria-selected="false">Entrance Fees <span class="n"></span></button>
    <button type="button" class="pau-tab" role="tab" data-cat="utility" aria-selected="false">Utility Bills <span class="n"></span></button>
</div>
<p class="pau-tabnote" id="pauTabNote"></p>

<script>
(function () {
    'use strict';
    const BASE = <?= json_encode($pauBase, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
    const API = BASE + 'tour/tour_accounts/payment_approval_v2/';
    const $ = s => document.querySelector(s), $$ = s => Array.from(document.querySelectorAll(s));
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const norm = s => String(s == null ? '' : s).toLowerCase().replace(/\s+/g, ' ').trim();
    const fmtD = d => { if (!d) return '—'; const t = new Date(d + 'T00:00:00'); return isNaN(t) ? d : t.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }); };
    const fmtLong = d => { const t = new Date(d + 'T00:00:00'); return t.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); };
    const money = n => n == null ? '—' : Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const sec = $('#pauSection'), tog = $('#pauToggle'), tbody = $('#pauTable tbody'), prog = $('#pauProg');
    let state = { p: 'next15', m: $('#pauMonth').value, summary: null, tours: [], loaded: false, run: 0 };
    let reviewed = {}; try { reviewed = JSON.parse(localStorage.getItem('pau-reviewed') || '{}') || {}; } catch (e) { reviewed = {}; }

    async function getJSON(url) {
        const r = await fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    }
    function setCards(s) {
        ['fully_paid', 'pending', 'advance', 'unapproved'].forEach(k => {
            const el = document.querySelector('.pau-card [data-k="' + k + '"]');
            if (el) el.innerHTML = (s ? esc(s.cards[k]) : '–') + '<small>tour' + (s && s.cards[k] === 1 ? '' : 's') + '</small>';
        });
        const per = s ? (s.label + ' · ' + fmtLong(s.from) + ' – ' + fmtLong(s.to)) : '';
        $$('.pau-card [data-per]').forEach(el => el.textContent = per);
        $('#pauCardTitle').textContent = s ? (s.period === 'next15' ? 'Next 15 days' : s.label) : 'Next 15 days';
        $('#pauRange').textContent = s ? fmtLong(s.from) + ' – ' + fmtLong(s.to) : '—';
    }
    async function loadSummary() {
        const run = ++state.run;
        setCards(null); $$('.pau-card .nn').forEach(el => el.innerHTML = '…<small>tours</small>');
        try {
            const s = await getJSON(API + 'upcoming?p=' + encodeURIComponent(state.p) + '&m=' + encodeURIComponent(state.m));
            if (run !== state.run) return;
            if (s.status !== 200) throw new Error(s.message || 'error');
            state.summary = s; state.loaded = false; setCards(s);
            if (!sec.hidden) loadTours();
        } catch (e) {
            if (run !== state.run) return;
            $$('.pau-card .nn').forEach(el => el.innerHTML = '–<small>not available</small>');
        }
    }
    async function loadTours() {
        const s = state.summary; if (!s) return;
        const run = state.run; state.loaded = true; state.tours = [];
        tbody.innerHTML = s.tours.length ? '' : '<tr><td colspan="10" class="pau-empty">No confirmed tour arriving ' + esc(fmtLong(s.from)) + ' – ' + esc(fmtLong(s.to)) + ' has an item awaiting approval.</td></tr>';
        let i = 0;
        for (const t of s.tours) {
            if (run !== state.run) return;
            i++; prog.hidden = false; prog.querySelector('.t').textContent = 'Loading tour ' + i + ' of ' + s.tours.length + ' (' + t.manual_id + ')…';
            try {
                const d = await getJSON(API + 'upcoming_tour?manual_id=' + encodeURIComponent(t.manual_id));
                if (run !== state.run) return;
                if (d.status === 200 && d.items.length) { state.tours.push(d); tbody.insertAdjacentHTML('beforeend', tourHTML(d)); }
            } catch (e) {
                tbody.insertAdjacentHTML('beforeend', '<tr><td colspan="10" class="pau-empty">' + esc(t.manual_id) + ': could not be loaded.</td></tr>');
            }
        }
        prog.hidden = true;
        // the card shows the tours actually listed once every tour is read
        const n = state.tours.length, el = document.querySelector('.pau-card [data-k="unapproved"]');
        if (el) el.innerHTML = esc(n) + '<small>tour' + (n === 1 ? '' : 's') + '</small>';
        if (!n && s.tours.length) tbody.innerHTML = '<tr><td colspan="10" class="pau-empty">No item awaiting approval in this period.</td></tr>';
        applySearch();
    }
    function action(t, it) {
        const mid = t.tour.manual_id, tpd = BASE + 'tour/tour_payment_dashboard?manual_id=' + encodeURIComponent(mid);
        if (it.state === 'not_submitted') return '<a class="pau-act sub" href="' + esc(tpd) + '" title="Submit the payment request on the Tour Payment Dashboard"><i class="bi bi-send"></i>Submit request</a>';
        if (it.state === 'rate') return '<a class="pau-act rev" href="' + esc(BASE + 'tour/tour_accounts/hotel_rate_confirm') + '" title="Confirm the rate on Hotel Rate Confirmation"><i class="bi bi-eye"></i>Review rate</a>';
        return '<a class="pau-act rev" href="' + esc(tpd) + '" data-review="' + esc(mid) + '" data-payee="' + esc(norm(it.supplier)) + '"><i class="bi bi-eye"></i>Review</a>';
    }
    function tourHTML(d) {
        const t = d.tour, key = t.manual_id, done = !!reviewed[key];
        const hay = norm([t.manual_id, t.agent_no, t.agent_name, t.client].join(' '));
        let h = '<tr class="tg' + (done ? ' done' : '') + '" data-tour="' + esc(key) + '" data-hay="' + esc(hay) + '">'
            + '<td class="tno">' + esc(t.manual_id) + '</td><td>' + esc(fmtD(t.start_date)) + '</td>'
            + '<td>' + esc(t.agent_no || '—') + ' <span class="mut">' + esc(t.agent_name) + '</span></td>'
            + '<td>' + esc(t.client) + ' <span class="mut">· ' + esc(t.pax) + ' pax</span></td>'
            + '<td colspan="4" class="mut">' + d.items.length + ' item' + (d.items.length === 1 ? '' : 's') + ' to review</td>'
            + '<td colspan="2"><label class="rv"><input type="checkbox" data-done="' + esc(key) + '"' + (done ? ' checked' : '') + '> Reviewed <span class="mut">(this browser)</span></label></td></tr>';
        d.items.forEach(it => {
            h += '<tr data-tour-item="' + esc(key) + '" data-cat="' + esc(it.tab || '') + '" data-hay="' + esc(hay + ' ' + norm(it.supplier)) + '">'
                + '<td></td><td></td><td></td><td></td>'
                + '<td>' + esc(it.supplier || '—') + '<div class="mut" style="font-size:.76rem">' + esc([it.detail, it.date ? 'service ' + fmtD(it.date) : ''].filter(Boolean).join(' · ')) + '</div></td>'
                + '<td>' + esc(it.type) + '</td><td>' + esc(it.currency) + '</td><td class="num">' + money(it.amount) + '</td>'
                + '<td><span class="pau-pill ' + esc(it.state) + '" title="Dashboard status: ' + esc(it.status) + '">' + esc(it.label) + '</span></td>'
                + '<td>' + action(d, it) + '</td></tr>';
        });
        return h;
    }
    function open(v) {
        sec.hidden = !v; tog.setAttribute('aria-expanded', v ? 'true' : 'false');
        if (v && !state.loaded) loadTours();
        if (v) sec.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    tog.addEventListener('click', () => open(sec.hidden));
    $('#pauClose').addEventListener('click', () => { open(false); tog.focus(); });   // collapses this section only
    $('#pauPeriod').addEventListener('change', e => { state.p = e.target.value; $('#pauMonthWrap').hidden = state.p === 'next15'; loadSummary(); });
    $('#pauMonth').addEventListener('change', e => { if (/^\d{4}-\d{2}$/.test(e.target.value)) { state.m = e.target.value; loadSummary(); } });
    tbody.addEventListener('change', e => {
        const k = e.target.getAttribute && e.target.getAttribute('data-done'); if (!k) return;
        if (e.target.checked) reviewed[k] = Date.now(); else delete reviewed[k];
        try { localStorage.setItem('pau-reviewed', JSON.stringify(reviewed)); } catch (er) { /* the tick still shows */ }
        const tr = e.target.closest('tr'); if (tr) tr.classList.toggle('done', e.target.checked);
    });
    // Review: a request already on this page opens in its payee group (the existing approval controls); otherwise the tour
    // opens on the Tour Payment Dashboard (the link's own address)
    tbody.addEventListener('click', e => {
        const a = e.target.closest('a[data-review]'); if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
        const mid = norm(a.getAttribute('data-review')), payee = a.getAttribute('data-payee');
        const groups = $$('#payeeTable > tbody > tr.payee-group-row').filter(g => (' ' + String(g.getAttribute('data-manual-ids') || '') + ' ').indexOf(' ' + mid + ' ') !== -1);
        const g = groups.find(x => norm(x.getAttribute('data-payee-name')) === payee) || (groups.length === 1 ? groups[0] : null);
        if (!g) return;   // not on this page: follow the link
        e.preventDefault();
        g.style.display = '';
        if (!g.classList.contains('expanded')) g.click();
        const det = document.querySelector(g.getAttribute('data-target'));
        const rows = det ? Array.from(det.querySelectorAll('tbody tr')).filter(r => { const s = r.querySelector('strong'); return s && norm(s.textContent) === mid; }) : [];
        (rows[0] || g).scrollIntoView({ block: 'center', behavior: 'smooth' });
        [g].concat(rows).forEach(r => { r.classList.remove('pau-hit'); void r.offsetWidth; r.classList.add('pau-hit'); });
    });

    // ---- one search: tour number, agent number or name, client name (and payee / driver names, as before) ----
    let look = {};
    async function enrich() {   // the agent and client of the tours already listed in Payment Requests and Approved Driver Expenses
        const ids = new Set();
        $$('#payeeTable > tbody > tr.payee-group-row, #approvedExpensesTable > tbody > tr.payee-group-row').forEach(g => {
            const det = document.querySelector(g.getAttribute('data-target'));
            if (det) det.querySelectorAll('tbody tr strong').forEach(s => { const v = s.textContent.trim(); if (/^AH\s*\d+/i.test(v)) ids.add(v); });
        });
        if (!ids.size) return;
        try { const d = await getJSON(API + 'tour_lookup?ids=' + encodeURIComponent(Array.from(ids).join(','))); if (d.status === 200) look = d.data || {}; } catch (e) { look = {}; }
    }
    function groupHay(g) {
        let h = norm(g.getAttribute('data-payee-name')) + ' ' + norm(g.getAttribute('data-manual-ids'));
        const det = document.querySelector(g.getAttribute('data-target'));
        if (det) det.querySelectorAll('tbody tr').forEach(r => {
            const s = r.querySelector('strong'); const mid = s ? s.textContent.trim() : '';
            const x = look[mid]; h += ' ' + norm(r.textContent);
            if (x) h += ' ' + norm(x.agent_no + ' ' + x.agent_name + ' ' + x.client);
        });
        return h;
    }
    let cat = 'all';
    const inCat = el => cat === 'all' || (el.getAttribute('data-cat') || '') === cat;
    function applySearch() {
        const q = norm($('#pauQ').value), words = q ? q.split(' ') : [];
        const hit = h => words.every(w => h.indexOf(w) !== -1);
        let nTours = 0, nPay = 0, nDrv = 0;
        // the review list: a tour shows with all its items when the tour matches, or with the matching items
        $$('#pauTable tr.tg').forEach(tg => {
            const k = tg.getAttribute('data-tour'), items = $$('#pauTable tr[data-tour-item="' + CSS.escape(k) + '"]');
            const tourHit = !words.length || hit(tg.getAttribute('data-hay'));
            let any = false;
            items.forEach(r => { const v = (tourHit || hit(r.getAttribute('data-hay'))) && inCat(r); r.style.display = v ? '' : 'none'; any = any || v; });
            tg.style.display = any ? '' : 'none'; if (any) nTours++;
        });
        [['#payeeTable', 'pay'], ['#approvedExpensesTable', 'drv']].forEach(([tsel, kind]) => {
            $$(tsel + ' > tbody > tr.payee-group-row').forEach(g => {
                const det = document.querySelector(g.getAttribute('data-target'));
                if (!words.length) { const show = inCat(g); g.style.display = show ? '' : 'none'; if (show && !g.classList.contains('expanded')) g.click(); return; }
                const v = hit(groupHay(g)) && inCat(g);
                g.style.display = v ? '' : 'none';
                if (v) { if (kind === 'pay') nPay++; else nDrv++; if (!g.classList.contains('expanded')) g.click(); }
                if (det && !v) { det.classList.remove('show'); g.classList.remove('expanded'); }
            });
        });
        sections(); counts();
        $('#pauRes').textContent = words.length
            ? 'Showing ' + nPay + ' payee group' + (nPay === 1 ? '' : 's') + ' in Payment Requests, ' + nDrv + ' driver group' + (nDrv === 1 ? '' : 's') + ' in Driver Settlements'
              + (sec.hidden ? '' : ' and ' + nTours + ' tour' + (nTours === 1 ? '' : 's') + ' in the review list') + ' for “' + $('#pauQ').value.trim() + '”.'
            : '';
    }
    // which sections a tab shows: Payment Requests for the request tabs, Approved Driver Expenses for settlements, all of them for All
    function card(sel) { const t = document.querySelector(sel); return t ? t.closest('.pd-section') : null; }
    function sections() {
        const req = card('#payeeTable'), exp = card('#approvedExpensesTable'), util = card('#utilityBillTable'), alloc = card('#driverAllocationRequestsTable');
        if (req) req.classList.toggle('pau-hidden-sec', cat === 'settlement' || cat === 'utility');
        if (exp) exp.classList.toggle('pau-hidden-sec', cat !== 'all' && cat !== 'settlement');
        if (util) util.classList.toggle('pau-hidden-sec', cat !== 'all' && cat !== 'utility');
        if (alloc) alloc.classList.toggle('pau-hidden-sec', cat !== 'all');
        const emptyId = 'pauTabEmpty'; let e = document.getElementById(emptyId);
        const visible = $$('#payeeTable > tbody > tr.payee-group-row').filter(g => g.style.display !== 'none').length;
        if (req && !['all', 'settlement', 'utility'].includes(cat) && !visible) {
            if (!e) { e = document.createElement('div'); e.id = emptyId; e.className = 'pau-empty'; req.querySelector('.card-body').appendChild(e); }
            e.textContent = 'No ' + tabName(cat).toLowerCase() + ' awaiting approval' + ($('#pauQ').value.trim() ? ' for this search' : '') + '.';
        } else if (e) { e.remove(); }
    }
    const tabName = c => ({ all: 'All', advance: 'Driver Advances', entrance: 'Entrance Fees', restaurant: 'Restaurants', hotel: 'Hotel Payments', settlement: 'Driver Settlements', utility: 'Utility Bills' })[c] || c;
    function counts() {   // requests awaiting approval per tab (one per request row), settlements and utility bills by their rows; not affected by the search
        const n = { advance: 0, entrance: 0, restaurant: 0, hotel: 0, settlement: 0, utility: 0 };
        $$('#payeeTable > tbody > tr.payee-group-row').forEach(g => { const c = g.getAttribute('data-cat'), det = document.querySelector(g.getAttribute('data-target')); if (c in n && det) n[c] += det.querySelectorAll('tbody tr .row-check').length; });
        $$('#approvedExpensesTable > tbody > tr.payee-group-row').forEach(g => { const det = document.querySelector(g.getAttribute('data-target')); if (det) n.settlement += det.querySelectorAll('tbody tr .driver-tour-check').length; });
        n.utility = $$('#utilityBillTable tbody tr').filter(r => r.querySelector('.btn-utility-accept, .btn-utility-reject')).length;
        n.all = Object.values(n).reduce((a, b) => a + b, 0);
        $$('#pauTabs .pau-tab').forEach(b => { const c = b.getAttribute('data-cat'); b.querySelector('.n').textContent = n[c]; b.title = n[c] + (c === 'settlement' ? ' tours with approved expenses, settlement due' : c === 'utility' ? ' bills pending' : ' requests awaiting approval'); });
        $('#pauTabNote').textContent = cat === 'all' ? 'Every request awaiting approval, approved driver expenses due for settlement, pending utility bills and driver allocation requests. Activities are sorted by the Payment Desk\'s rule: restaurant, café and meal payees under Restaurants; entrance tickets and other activity services under Entrance Fees.'
            : cat === 'settlement' ? 'Driver expenses already approved, waiting for the settlement payment. Tick the tours and use the existing Accept / Reject controls; View opens the expense breakdown.'
            : cat === 'utility' ? 'Utility bill payments pending, with their existing Accept / Reject controls.'
            : tabName(cat) + ' awaiting approval. Tick the requests and use the payee\'s existing Accept / Reject controls as before.';
    }
    $('#pauTabs').addEventListener('click', e => {
        const b = e.target.closest('.pau-tab'); if (!b) return;
        cat = b.getAttribute('data-cat');
        $$('#pauTabs .pau-tab').forEach(x => x.setAttribute('aria-selected', x === b ? 'true' : 'false'));
        applySearch();
    });
    // the status on every group row, so the stage is clear before a group is opened (the tables render after this partial)
    document.addEventListener('DOMContentLoaded', () => {
        $$('#payeeTable > tbody > tr.payee-group-row').forEach(g => { const td = g.children[2]; if (td && !td.querySelector('.pau-st')) td.insertAdjacentHTML('beforeend', '<span class="pau-st aw">Awaiting approval</span>'); });
        $$('#approvedExpensesTable > tbody > tr.payee-group-row').forEach(g => { const td = g.children[2]; if (td && !td.querySelector('.pau-st')) td.insertAdjacentHTML('beforeend', '<span class="pau-st ok">Expenses approved · settlement due</span>'); });
        counts();
        $$('#payeeTable > tbody > tr.payee-group-row, #approvedExpensesTable > tbody > tr.payee-group-row').forEach(g => { if (!g.classList.contains('expanded')) g.click(); });   // details open at once
    });

    let tmr = null;
    $('#pauQ').addEventListener('input', () => { clearTimeout(tmr); tmr = setTimeout(applySearch, 200); });
    $('#pauQ').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); applySearch(); } });
    $('#pauGo').addEventListener('click', applySearch);
    $('#pauReset').addEventListener('click', () => {
        $('#pauQ').value = ''; applySearch();
        $('#pauQ').focus();
    });

    // the cards load after the page is shown; the agent / client list for the search after that
    window.addEventListener('load', () => { loadSummary().then(enrich); });
})();
</script>
