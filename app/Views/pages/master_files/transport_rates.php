<?= view('layouts/head') ?>
<style>
    /* ---------- Transport Rates (same tokens as the Tour Inquiry panel) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:820px; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .btn-primary { background:var(--tf-navy); border-color:var(--tf-navy); }
    .tf .btn-primary:hover { background:var(--tf-navy-2); border-color:var(--tf-navy-2); }
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .btn-light:hover { background:var(--tf-bg); }
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl td { padding:9px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr:last-child td { border-bottom:0; }
    .tf .tbl tr.off td { opacity:.5; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .rate { font-size:15px; font-weight:600; }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .stale { border-left:3px solid var(--tf-amber); background:var(--tf-amber-soft); border-radius:8px; padding:10px 12px; font-size:12.5px; }
    /* ================= tf-kmrate-r2 (14 Nov 2026): Transport Rate Management to Shantha's mock-up ================= */
    .trm { --p:#842484; --g:#78B848; --ink:#1A1A1A; --mut:#5E5964; --line:#E3DCE7; color:var(--ink); }
    .trm-bar { display:flex; align-items:center; background:var(--p); border-radius:8px; height:44px; padding:0 14px; margin-bottom:14px; }
    .tf .trm-back { display:inline-flex; align-items:center; gap:10px; background:none; border:0; color:#fff; font-size:16px; font-weight:700; cursor:pointer; padding:4px 8px; }
    .trm h1 { font-size:38px; font-weight:800; color:var(--p); margin:0; line-height:1.15; letter-spacing:-.01em; }
    .trm .trm-sub { font-size:18px; font-weight:600; color:#2B2530; margin:2px 0 12px; }
    .trm .trm-tools { display:flex; gap:14px; align-items:center; flex-wrap:wrap; margin-bottom:14px; }
    .trm .trm-search { display:flex; align-items:center; gap:10px; width:320px; max-width:100%; height:38px; border:1.5px solid #B9AEC2; border-radius:6px; padding:0 12px; background:#fff; margin:0; }
    .trm .trm-search input { border:0; outline:0; flex:1; min-width:0; font-size:15px; background:transparent; color:var(--ink); }
    .tf .trm .trm-manual { display:inline-flex; align-items:center; gap:10px; height:38px; padding:0 18px; border:1.5px solid var(--p); border-radius:6px; background:#fff; color:var(--p); font-size:15px; font-weight:700; cursor:pointer; }
    .trm .trm-table { width:100%; border-collapse:separate; border-spacing:0; border:1px solid var(--line); border-radius:10px; overflow:hidden; background:#fff; }
    .trm .trm-table > thead > tr > th { background:#F1E4F3; color:var(--p); font-size:19px; font-weight:800; text-align:left; padding:10px 18px; vertical-align:middle; }
    .trm .trm-table > thead > tr > th small { display:block; font-size:13.5px; font-weight:600; color:#3B3341; }
    .trm .trm-table > tbody > tr > td { padding:7px 18px; border-top:1px solid var(--line); vertical-align:middle; }
    .trm .trm-cat { font-size:19px; font-weight:800; color:var(--ink); white-space:nowrap; }
    .trm .trm-cat .ret { font-size:11.5px; font-weight:700; color:#B3261E; background:#FBE7E5; border-radius:4px; padding:2px 7px; margin-left:8px; vertical-align:middle; }
    .trm .trm-amt { font-size:19px; font-weight:800; color:var(--ink); white-space:nowrap; }
    .trm .trm-amt.lkr { display:inline-block; background:#DFF3CC; color:#215C0B; border-radius:5px; padding:1px 10px; }
    .trm .trm-none { display:inline-block; background:#FCE2B0; color:#8A4B00; font-size:15px; font-weight:800; border-radius:5px; padding:2px 12px; white-space:nowrap; }
    .trm .trm-check { display:inline-block; background:#FCE2B0; color:#8A4B00; font-size:13.5px; font-weight:700; border-radius:5px; padding:2px 10px; margin-left:8px; vertical-align:middle; white-space:nowrap; }
    .trm .trm-acts { display:flex; gap:10px; flex-wrap:nowrap; }
    .tf .trm .trm-btn { width:132px; height:34px; border:0; border-radius:6px; background:var(--g); color:#fff; font-size:14.5px; font-weight:700; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,.15); white-space:nowrap; }
    .tf .trm .trm-btn:hover { background:#69A53C; }
    .tf .trm .trm-hist { width:112px; height:34px; border:1.5px solid var(--p); border-radius:6px; background:#fff; color:var(--p); font-size:14.5px; font-weight:700; cursor:pointer; white-space:nowrap; }
    .tf .trm .trm-hist i { transition:transform .15s; display:inline-block; } .tf .trm .trm-hist.open i { transform:rotate(180deg); }
    .trm tr.trm-more > td { background:#FBF8FC; padding:12px 18px 14px; }
    .trm .trm-h { width:100%; border-collapse:collapse; font-size:13.5px; background:#fff; border:1px solid var(--line); border-radius:8px; }
    .trm .trm-h th { text-align:left; font-size:12px; color:var(--mut); font-weight:700; padding:7px 10px; border-bottom:1px solid var(--line); background:#fff; white-space:nowrap; }
    .trm .trm-h td { padding:7px 10px; border-bottom:1px solid var(--line); vertical-align:top; } .trm .trm-h tr:last-child td { border-bottom:0; }
    .trm .trm-cap { display:flex; gap:18px; align-items:center; flex-wrap:wrap; font-size:13.5px; margin-top:10px; color:#2B2530; }
    .trm .trm-cap .lk { color:var(--p); font-weight:700; background:none; border:0; padding:0; cursor:pointer; font-size:13.5px; }
    .trm details.trm-fleet { margin-top:16px; } .trm details.trm-fleet > summary { cursor:pointer; color:var(--p); font-weight:700; font-size:14px; padding:6px 2px; }
    #avh-root .avh-fab { display:none !important; }   /* the manual opens from the User Manual button beside the search — nothing floats over the table */
    @media (max-width: 1100px) { .trm .trm-acts { flex-wrap:wrap; } .trm .trm-table > thead > tr > th, .trm .trm-table > tbody > tr > td { padding-left:10px; padding-right:10px; } .trm h1 { font-size:30px; } }
    @media (max-width: 720px) { .trm .trm-table thead { display:none; } .trm .trm-table tbody tr.trm-row { display:grid; grid-template-columns:1fr 1fr; } .trm .trm-table tbody tr.trm-row td { border-top:0; padding:6px 10px; }
        .trm .trm-table tbody tr.trm-row td:first-child, .trm .trm-table tbody tr.trm-row td:last-child { grid-column:1 / -1; } .trm .trm-table tbody tr.trm-row td:first-child { border-top:1px solid var(--line); padding-top:10px; }
        .trm .trm-table tbody tr.trm-row td[data-l]::before { content:attr(data-l); display:block; font-size:11.5px; color:var(--mut); font-weight:600; } .tf .trm .trm-btn { width:auto; flex:1; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <div class="trm">
                <div class="trm-bar"><button type="button" class="trm-back" id="trm_back"><i class="bi bi-arrow-left"></i> Back</button></div>
                <h1>Transport Rate Management</h1>
                <div class="trm-sub">USD quotations / LKR driver payments</div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Inquiry tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div class="trm-tools">
                    <label class="trm-search"><i class="bi bi-search" style="color:#5E5964"></i><input id="trm_q" placeholder="Search vehicles" aria-label="Search vehicles" autocomplete="off"></label>
                    <button type="button" class="trm-manual" id="trm_manual"><i class="bi bi-book"></i> User Manual</button>
                </div>
                <div id="stale" class="mb-3" hidden></div>

                <div style="overflow-x:auto"><table class="trm-table" id="board">
                    <thead><tr><th>Vehicle category</th><th>USD / km<small>Customer quotation</small></th><th>LKR / km<small>Driver payment</small></th><th>Actions</th></tr></thead>
                    <tbody><tr><td colspan="4" class="help">Loading…</td></tr></tbody>
                </table></div>

                <details class="trm-fleet" id="fleet_card"><summary>The fleet — as the Vehicle master holds it (read-only)</summary>
                    <div class="tf-card"><div class="tf-card-b" style="overflow-x:auto"><table class="tbl" id="fleet"><thead><tr><th>Vehicle</th><th>Seats</th><th>Owner (ledger)</th><th>Registration number</th></tr></thead><tbody><tr><td colspan="4" class="help">Loading…</td></tr></tbody></table></div></div>
                </details>

                <?php endif; ?>
                </div>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<!-- set / change one rate (tf-kmrate-r2): opens from the row's button only -->
<div class="modal fade tf" id="m_rate" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="r_title" style="color:#842484;font-weight:800;font-size:18px">Set Transport Rate</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div style="font-size:15px;margin-bottom:10px"><b id="r_vehicle"></b><br><span id="r_lane" style="color:#3B3341"></span></div>
        <div class="help" id="r_now" style="margin-bottom:10px"></div>
        <label class="form-label help" id="r_amount_l">Amount per kilometre</label>
        <input type="number" min="0" step="0.01" class="form-control" id="f_rate">
        <label class="form-label help mt-2">Effective date</label>
        <input type="date" class="form-control" id="f_from">
        <label class="form-label help mt-2">Reason</label>
        <input class="form-control" id="f_reason" maxlength="200" placeholder="e.g. fuel increase, new season">
        <input type="hidden" id="f_class"><input type="hidden" id="f_cur">
        <div class="help mt-2">The rate applies from the effective date and stays in force until it is changed again. The rate it replaces is kept in the history.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-sm" id="f_save" style="background:#78B848;border-color:#78B848;color:#fff;font-weight:700;padding:6px 18px">Save rate</button></div>
</div></div></div>

<!-- correct -->
<div class="modal fade tf" id="m_fix" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Correct a typing mistake</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help" id="x_what"></div>
        <label class="form-label help mt-2">Corrected rate per kilometre</label>
        <input type="number" min="0" step="0.01" class="form-control form-control-sm" id="x_rate">
        <label class="form-label help mt-2">Reason</label>
        <input class="form-control form-control-sm" id="x_reason" placeholder="e.g. typed 950 for 95">
        <input type="hidden" id="x_id">
        <div class="help mt-2">Use this only for a figure that was never meant. A rate that has genuinely changed is a new rate from a date — set it on the left.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="x_ok">Correct it</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($rates_url ?? 'master_files/transport_rates') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const show = sel => { const el = document.querySelector(sel); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : new bootstrap.Modal(el)).show(); };
    const hide = sel => { const i = bootstrap.Modal.getInstance(document.querySelector(sel)); if (i) i.hide(); };
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2600 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With':'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const money = (v, cur) => v == null ? '—' : (cur ? esc(cur) + ' ' : '') + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtDate = d => { if (!d) return ''; const [y, m, dd] = String(d).split('-'); return `${Number(dd)} ${['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][Number(m) - 1]} ${y}`; };

    let BOARD = [], OPEN = {}, HIST = {};
    const ROOTB = '<?= rtrim(base_url(), '/') ?>';
    let CREW_URL = ROOTB + '/master_files/crew_people', CREW_MASTER = false;   // Transport & Guide Services: the people whose vehicle is in a class
    const amount = (c) => c.currency + ' ' + Number(c.rate_km).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    // a quotation rate lower than a smaller category's is worth a second look: flagged, never changed
    const checkRate = (b, i) => !!b.current && BOARD.slice(0, i).some(s => s.current && Number(s.current.rate_km) > Number(b.current.rate_km));

    function histHtml(b) {
        const rows = HIST[b.code];
        const cap = `<div class="trm-cap"><span>Seats <b>${b.seats ?? '—'}</b></span><span>Carries up to <b data-maxpax-of="${esc(b.code)}">${b.max_pax}</b> passengers <button type="button" class="lk" data-maxpax="${esc(b.code)}">change</button></span>
            ${b.crew ? `<span>${CREW_MASTER ? `<a href="${CREW_URL}?class=${encodeURIComponent(b.code)}" style="color:#842484;font-weight:700">${b.crew} on the Driver &amp; Guide Master</a>` : b.crew + ' on the Driver &amp; Guide Master'}</span>` : ''}
            <button type="button" class="lk" data-act="${esc(b.code)}" data-on="${b.active ? 0 : 1}">${b.active ? 'Retire this category' : 'Reinstate this category'}</button></div>`;
        if (!rows) return '<div class="help">Loading the history…</div>' + cap;
        return `<table class="trm-h"><thead><tr><th>Rate for</th><th>Amount per km</th><th>Effective from</th><th>Until</th><th>Changed by</th><th>Reason</th><th>Status</th><th></th></tr></thead><tbody>
            ${rows.map(r => `<tr><td>${r.currency === 'LKR' ? 'Driver payment (LKR)' : 'Customer quotation (USD)'}</td>
                <td><b>${esc(amount(r))}</b>${r.corrected_at ? `<div class="help">corrected from ${Number(r.corrected_from).toFixed(2)} by ${esc(r.corrected_by || '')}</div>` : ''}</td>
                <td>${fmtDate(r.valid_from)}</td><td>${r.valid_to ? fmtDate(r.valid_to) : 'in force'}</td>
                <td>${esc(r.set_by || '—')}<div class="help">${esc((r.set_at || '').slice(0, 16))}</div></td><td>${esc(r.reason || '—')}</td>
                <td>${r.state === 'current' ? 'Current' : (r.state === 'upcoming' ? 'Upcoming' : 'Previous')}${r.used_on ? `<div class="help">on ${r.used_on} inquiry line${r.used_on === 1 ? '' : 's'}</div>` : ''}</td>
                <td><button type="button" class="btn btn-light btn-sm" data-fix="${r.id}" data-rate="${r.rate_km}" data-cur="${esc(r.currency)}" data-code="${esc(b.code)}" title="Put a typing mistake right; logged as a correction">Correct</button></td></tr>`).join('')
            || '<tr><td colspan="8" class="help">No rate has ever been set for this category.</td></tr>'}</tbody></table>` + cap;
    }

    function render() {
        const q = (document.getElementById('trm_q').value || '').trim().toLowerCase();
        const list = BOARD.map((b, i) => [b, i]).filter(([b]) => !q || (b.name + ' ' + b.code + ' ' + (b.seats ?? '')).toLowerCase().includes(q));
        document.querySelector('#board tbody').innerHTML = list.map(([b, i]) => {
            const c = b.current, d = b.driver;
            return `<tr class="trm-row" data-row="${esc(b.code)}">
                <td><span class="trm-cat">${esc(b.name)}</span>${b.active ? '' : '<span class="ret">Retired</span>'}</td>
                <td data-l="USD / km · Customer quotation">${c ? `<span class="trm-amt">${esc(amount(c))}</span>${checkRate(b, i) ? '<span class="trm-check" title="Lower than a smaller category\'s rate — please confirm it">Check rate</span>' : ''}` : '<span class="trm-none">Not set</span>'}</td>
                <td data-l="LKR / km · Driver payment">${d ? `<span class="trm-amt lkr">${esc(amount(d))}</span>` : '<span class="trm-none">Not set</span>'}</td>
                <td><div class="trm-acts">
                    <button type="button" class="trm-btn" data-set="${esc(b.code)}" data-lane="USD">${c ? 'Change USD' : 'Set USD'}</button>
                    <button type="button" class="trm-btn" data-set="${esc(b.code)}" data-lane="LKR">${d ? 'Change LKR' : 'Set LKR'}</button>
                    <button type="button" class="trm-hist ${OPEN[b.code] ? 'open' : ''}" data-hist="${esc(b.code)}" aria-expanded="${OPEN[b.code] ? 'true' : 'false'}">History <i class="bi bi-chevron-down"></i></button></div></td></tr>
                ${OPEN[b.code] ? `<tr class="trm-more"><td colspan="4">${histHtml(b)}</td></tr>` : ''}`;
        }).join('') || `<tr><td colspan="4" class="help" style="padding:16px 18px">${BOARD.length ? 'No vehicle category matches the search.' : 'No vehicle categories are set up.'}</td></tr>`;

        document.querySelectorAll('#board [data-set]').forEach(b => b.addEventListener('click', () => openRate(b.dataset.set, b.dataset.lane)));
        document.querySelectorAll('#board [data-hist]').forEach(b => b.addEventListener('click', () => toggleHist(b.dataset.hist)));
        document.querySelectorAll('#board [data-fix]').forEach(x => x.addEventListener('click', () => {
            const b = BOARD.find(v => v.code === x.dataset.code);
            document.getElementById('x_id').value = x.dataset.fix;
            document.getElementById('x_rate').value = x.dataset.rate;
            document.getElementById('x_reason').value = '';
            document.getElementById('x_what').textContent = 'Currently ' + x.dataset.cur + ' ' + Number(x.dataset.rate).toFixed(2) + ' per km for ' + (b ? b.name : x.dataset.code) + '.';
            document.getElementById('x_id').dataset.code = x.dataset.code;
            show('#m_fix');
        }));
        document.querySelectorAll('#board [data-maxpax]').forEach(btn => btn.addEventListener('click', async () => {
            const b = BOARD.find(x => x.code === btn.dataset.maxpax);
            if (!b) return;
            const r = await Swal.fire({
                title: b.name + ' — carries up to',
                html: 'A party of up to this many travels in a ' + esc(b.name) + (b.seats ? ' (it seats ' + b.seats + '; the spare seats are the legroom, the luggage and the guide)' : '') + '. New vehicle choices follow the new figure; vehicles already on inquiries stay as they are.',
                input: 'number', inputValue: b.max_pax, inputAttributes: { min: 1, max: b.seats || 99, step: 1 },
                showCancelButton: true, confirmButtonText: 'Save', cancelButtonText: 'Cancel'
            });
            if (!r.isConfirmed) return;
            const j = await post('/class/' + encodeURIComponent(b.code) + '/max_pax', { max_pax: r.value });
            if (j.status !== 200) { toast('error', j.message); return; }
            took(j.data); toast('success', j.message);
        }));
        document.querySelectorAll('#board [data-act]').forEach(b => b.addEventListener('click', async () => {
            const on = b.dataset.on === '1';
            if (!on) {
                const ok = await Swal.fire({ text: 'Retire this category? It stops being offered on new inquiries. Rates and past quotations are untouched, and Reinstate brings it back.', icon: 'question', showCancelButton: true, confirmButtonText: 'Retire', cancelButtonText: 'Keep it' });
                if (!ok.isConfirmed) return;
            }
            const j = await post('/class/' + encodeURIComponent(b.dataset.act) + '/active', { active: on ? 1 : 0 });
            if (j.status !== 200) { toast('error', j.message); return; }
            took(j.data); toast('success', j.message);
        }));
    }

    // a board that came back from a save: the crew counts are kept from the last full load
    function took(board) {
        const crew = Object.fromEntries(BOARD.map(b => [b.code, b.crew || 0]));
        BOARD = (board || []).map(b => Object.assign({ crew: crew[b.code] || 0 }, b));
        render();
    }

    async function toggleHist(code) {
        OPEN[code] = !OPEN[code];
        render();
        if (OPEN[code]) await loadHist(code);
    }
    async function loadHist(code) {
        const j = await api('/history/' + encodeURIComponent(code));
        if (j.status !== 200) { toast('error', j.message); return; }
        HIST[code] = j.data || [];
        if (OPEN[code]) render();
    }

    function openRate(code, cur) {
        const b = BOARD.find(x => x.code === code); if (!b) return;
        const lkr = cur === 'LKR', now = lkr ? b.driver : b.current;
        document.getElementById('f_class').value = code;
        document.getElementById('f_cur').value = lkr ? 'LKR' : 'USD';
        document.getElementById('r_title').textContent = now ? 'Change Transport Rate' : 'Set Transport Rate';
        document.getElementById('r_vehicle').textContent = b.name;
        document.getElementById('r_lane').textContent = lkr ? 'LKR per km — Driver payment' : 'USD per km — Customer quotation';
        document.getElementById('r_now').innerHTML = (b.active ? '' : '<b>This category is retired.</b> Saving a rate puts it back in use. ') + (now ? 'Current rate: <b>' + esc(amount(now)) + '</b> since ' + fmtDate(now.valid_from) + '.' : 'Current rate: <b>Not set</b>.');
        document.getElementById('r_amount_l').textContent = 'Amount per kilometre (' + (lkr ? 'LKR' : 'USD') + ')';
        document.getElementById('f_rate').value = ''; document.getElementById('f_rate').placeholder = lkr ? 'e.g. 95.00' : 'e.g. 0.70';
        document.getElementById('f_from').value = new Date().toLocaleDateString('en-CA');
        document.getElementById('f_reason').value = '';
        show('#m_rate');
        setTimeout(() => document.getElementById('f_rate').focus(), 350);
    }

    async function load() {
        const j = await api('/board');
        if (j.status !== 200) { toast('error', j.message); return; }
        CREW_URL = ROOTB + '/' + (j.data.crew_url || 'master_files/crew_people'); CREW_MASTER = !!j.data.crew_master;
        BOARD = j.data.board;
        render();
        const st = document.getElementById('stale');
        if ((j.data.stale || []).length) {
            st.hidden = false;
            st.innerHTML = `<div class="stale"><b>${j.data.stale.length} open inquir${j.data.stale.length === 1 ? 'y is' : 'ies are'} still priced at an earlier rate</b> — ${j.data.stale.map(s => esc(s.inquiry_no) + ' (' + esc(s.class_code) + ' at ' + money(s.km_rate) + ', now ' + money(s.current) + ')').join(', ')}. Nothing is re-priced by itself: open the inquiry and press Re-price if the new rate should apply.</div>`;
        } else {
            st.hidden = true;
        }
    }

    // tf-masters-r3: the fleet as the Vehicle master holds it — read-only; the number is entered on Master Files → Vehicle
    async function loadFleet() {
        const j = await api('/fleet');
        const tb = document.querySelector('#fleet tbody');
        const rows = j.data || [];
        tb.innerHTML = rows.length ? rows.map(v => `<tr data-vehicle="${v.id}">
            <td><b>${esc(v.type)}</b>${v.flagged ? ' <span class="pill pill-amber" title="Flagged on the vehicle master">flagged</span>' : ''}</td>
            <td class="mono">${v.seats || '—'}</td>
            <td class="mono">${esc(v.owner || '—')}</td>
            <td class="mono" data-reg="${v.id}">${v.reg ? `<b>${esc(v.reg)}</b>` : '<span class="pill pill-amber" title="No registration number on the Vehicle master yet — enter it under Master Files → Vehicle">not on the master yet</span>'}</td>
        </tr>`).join('') : '<tr><td colspan="4" class="help">No vehicles on the master.</td></tr>';
    }
    loadFleet();

    document.getElementById('trm_q').addEventListener('input', render);
    document.getElementById('trm_manual').addEventListener('click', () => { const f = document.querySelector('#avh-root .avh-fab'); if (f) f.click(); else location.href = ROOTB + '/help'; });
    document.getElementById('f_save').addEventListener('click', async () => {
        const code = document.getElementById('f_class').value;
        const j = await post('/rate', {
            class_code: code, rate_km: document.getElementById('f_rate').value,
            currency: document.getElementById('f_cur').value, valid_from: document.getElementById('f_from').value || null,
            reason: document.getElementById('f_reason').value
        });
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_rate'); took(j.data); toast('success', j.message);
        if (OPEN[code]) loadHist(code);
        load();
    });
    document.getElementById('x_ok').addEventListener('click', async () => {
        const code = document.getElementById('x_id').dataset.code;
        const j = await post('/rate/' + document.getElementById('x_id').value + '/correct', { rate_km: document.getElementById('x_rate').value, reason: document.getElementById('x_reason').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_fix'); took(j.data); toast('success', j.message);
        if (code && OPEN[code]) loadHist(code);
    });

    load();
})();
// tf-kmrate-r2: Back returns to the page the user came from (its filters and position are the browser's own)
document.getElementById('trm_back')?.addEventListener('click', function () {
    let same = false;
    try { same = !!document.referrer && new URL(document.referrer).origin === location.origin && document.referrer.split('#')[0] !== location.href.split('#')[0]; } catch (e) {}
    if (same && history.length > 1) history.back(); else location.href = '<?= base_url('home/tour_desk') ?>';
});
</script>
</body>
</html>
