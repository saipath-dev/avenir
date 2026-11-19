<?= view('layouts/head') ?>
<style>
    /* ---------- Confirmed Tours (tf-confirmed-r1) — the Board's tokens and list, read-only ---------- */
    .tf { --tf-navy:#1e3553; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:820px; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl th.lh { color:#1878c0; } .tf .tbl th.lt { color:#1a1a1a; }
    .tf .tbl td { padding:10px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr:last-child td { border-bottom:0; }
    .tf .tbl tr.trow:hover td { background:var(--tf-bg); }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-teal { background:var(--tf-teal-soft); color:var(--tf-teal); }
    .tf .chips { display:flex; gap:6px; flex-wrap:wrap; }
    .tf .chip { border:1px solid var(--tf-line); background:#fff; border-radius:9px; padding:5px 11px; font-size:12px; font-weight:500; cursor:pointer; color:var(--tf-ink); }
    .tf .chip .n { font-family:ui-monospace, Menlo, monospace; font-size:11px; color:var(--tf-muted); margin-left:4px; }
    .tf .chip.on { background:var(--tf-blue-soft); border-color:transparent; color:var(--tf-navy); font-weight:600; }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:14px 0; text-align:center; }
    .tf .td-strip { margin-bottom:4px; }
    .tf .acts { display:flex; flex-direction:column; gap:5px; align-items:flex-start; }
    .tf .acts .btn { white-space:nowrap; }
    /* tf-confirmed-r3 (19 Nov 2026, Shantha): the Confirmed Tours list in the form of the Tour Inquiry list — the same card,
       search, filter chips and drop-downs, one line per tour — and a Confirm button on every line: burgundy while work is
       outstanding (it opens Operations Control), green when everything is checked. Avenir burgundy #8b1d40, green #78b848. */
    .tf { --line:#e7dfe3; --mut:#5f6470; --ink:#1A1A1A; --p:#8b1d40; --g:#78b848; }
    .tf .tq-card { background:#fff; border-radius:14px; box-shadow:0 1px 5px rgba(53,7,22,.08); overflow:hidden; }
    .tf .tq-lh { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; padding:18px 20px 14px; } .tf .tq-lh h2 { font-size:21px; font-weight:800; margin:0; color:var(--ink); }
    .tf .tq-search { display:flex; align-items:center; gap:10px; flex:1 1 300px; max-width:520px; height:44px; padding:0 14px; margin:0; border:1.5px solid var(--line); border-radius:10px; background:#fff; } .tf .tq-search i { color:var(--mut); }
    .tf .tq-search input { border:0; outline:0; flex:1 1 auto; min-width:0; font-size:14.5px; background:transparent; color:var(--ink); } .tf .tq-search:focus-within, .tf .tq-sel:focus-within { border-color:var(--p); box-shadow:0 0 0 3px rgba(139,29,64,.14); }
    .tf .tq-fl { display:flex; gap:8px; flex-wrap:wrap; align-items:center; padding:0 20px 14px; border-bottom:1px solid var(--line); } .tf .tq-fl #chips { display:contents; }
    .tf .tq-pf { height:36px; padding:0 16px; border-radius:18px; border:1.5px solid var(--line); background:#fff; color:var(--ink); font-size:13.5px; font-weight:700; cursor:pointer; white-space:nowrap; }
    .tf .tq-pf.on { background:#660f2b; border-color:#660f2b; color:#fff; } .tf .tq-pf:hover:not(.on) { border-color:var(--p); color:var(--p); }
    .tf .tq-sel { display:inline-flex; align-items:center; gap:6px; height:36px; padding:0 12px; margin:0; border:1.5px solid var(--line); border-radius:18px; background:#fff; font-size:13.5px; font-weight:700; white-space:nowrap; }
    .tf .tq-sel > span::after { content:':'; } .tf .tq-sel select, .tf .tq-sel input { border:0; outline:0; background:transparent; font:inherit; font-weight:600; color:var(--ink); height:30px; max-width:150px; } .tf .tq-sel.dt input { font-size:12.5px; max-width:124px; } .tf .tq-sel i { font-style:normal; color:var(--mut); }
    .tf .tq-clear { height:36px; padding:0 12px; border:0; background:none; color:var(--mut); font-size:13px; font-weight:700; cursor:pointer; } .tf .tq-clear:hover { color:var(--p); }
    .tf table.tq-tbl { width:100%; border-collapse:collapse; } .tf table.tq-tbl th { text-align:left; font-size:11.5px; letter-spacing:.07em; text-transform:uppercase; color:var(--mut); font-weight:700; padding:14px 16px 10px; white-space:nowrap; }
    .tf table.tq-tbl td { padding:14px 16px; border-top:1px solid var(--line); font-size:14.5px; vertical-align:middle; color:var(--ink); } .tf table.tq-tbl td.empty { text-align:center; color:var(--mut); padding:30px 20px; }
    .tf table.tq-tbl tbody tr.trow:hover td { background:#fbf7f9; }
    .tf table.tq-tbl td > b { display:block; font-weight:800; } .tf table.tq-tbl td small { display:block; font-size:12.5px; color:var(--mut); margin-top:2px; }
    .tf table.tq-tbl td:first-child b, .tf table.tq-tbl td:first-child small { white-space:nowrap; }
    .tf table.tq-tbl td.px { font-weight:800; } .tf table.tq-tbl td.ac { text-align:right; white-space:nowrap; } .tf table.tq-tbl td.ac .acts { align-items:flex-end; }
    .tf .ct-go { display:flex; align-items:center; justify-content:flex-end; gap:8px; }
    .tf .tq-open { display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 18px; border:0; border-radius:9px; background:var(--p); color:#fff !important; font-size:14.5px; font-weight:700; cursor:pointer; white-space:nowrap; text-decoration:none; }
    .tf .tq-open:hover { filter:brightness(1.08); } .tf .tq-open.go { background:var(--g); } .tf .tq-open.ct-conf { background:var(--p); min-width:118px; justify-content:center; } .tf .tq-open.ct-conf.ok { background:var(--g); cursor:default; } .tf .tq-open.off { background:#b9b9b9; cursor:default; }
    .tf .ct-wait { font-size:12px; color:var(--mut); text-align:right; margin-top:4px; white-space:normal; max-width:230px; }
    .tf .ct-more { position:relative; } .tf .ct-more > summary { list-style:none; width:40px; height:40px; border:1.5px solid var(--line); border-radius:9px; display:grid; place-items:center; cursor:pointer; font-size:18px; color:var(--ink); background:#fff; } .tf .ct-more > summary::-webkit-details-marker { display:none; }
    .tf .ct-more[open] > summary { border-color:var(--p); color:var(--p); }
    .tf .ct-more .dd { position:absolute; right:0; top:calc(100% + 6px); z-index:40; min-width:260px; background:#fff; border:1px solid var(--line); border-radius:10px; box-shadow:0 12px 28px rgba(40,20,25,.18); padding:6px; text-align:left; }
    .tf .ct-more .dd a, .tf .ct-more .dd button { display:flex; align-items:center; gap:9px; width:100%; padding:8px 11px; border:0; background:none; border-radius:7px; font-size:13.5px; font-weight:600; color:var(--ink); text-decoration:none; cursor:pointer; text-align:left; } .tf .ct-more .dd a:hover, .tf .ct-more .dd button:hover { background:#f8f1f3; color:var(--p); } .tf .ct-more .dd i { color:var(--p); }
    .tf .tq-foot { padding:12px 20px 14px; border-top:1px solid var(--line); font-size:13px; color:var(--mut); }
    @media (max-width:900px) { .tf table.tq-tbl thead { display:none; } .tf table.tq-tbl, .tf table.tq-tbl tbody { display:block; } .tf table.tq-tbl tr { display:grid; grid-template-columns:1fr auto; gap:6px 12px; padding:14px; border-top:1px solid var(--line); }
        .tf table.tq-tbl td { display:block; padding:0; border:0; } .tf table.tq-tbl td.ac { grid-column:1 / -1; } .tf table.tq-tbl td.px::before { content:'Pax '; color:var(--mut); font-weight:500; } .tf .tq-fl { flex-wrap:nowrap; overflow-x:auto; } .tf .tq-pf, .tf .tq-sel, .tf .tq-clear { flex:none; } }
    /* tf-confirmed-r4 (19 Nov 2026, Shantha): the list starts directly with Confirmed Tours — no Tour Desk strip, no New quote.
       Status chips carry their counts; Completed is a count only. Arrangements read as five labelled lines with a signal dot. */
    .tf .ct-top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin:2px 2px 14px; } .tf .ct-top h2 { margin:0; font-size:23px; font-weight:800; color:var(--ink); }
    .tf .ct-prev { display:inline-flex; align-items:center; gap:8px; height:38px; padding:0 15px; border:1.5px solid var(--p); border-radius:9px; color:var(--p); font-size:13.5px; font-weight:700; text-decoration:none; background:#fff; } .tf .ct-prev:hover { background:var(--p); color:#fff; }
    .tf .tq-lh { padding:16px 20px 12px; } .tf .tq-lh #chips { display:flex; gap:8px; flex-wrap:wrap; align-items:center; flex:1 1 auto; } .tf .tq-search { flex:0 1 380px; }
    .tf .tq-fl { padding-bottom:16px; border-bottom:0; }
    .tf .tq-pf { display:inline-flex; align-items:center; gap:8px; } .tf .tq-pf .n { min-width:22px; height:22px; padding:0 6px; border-radius:11px; display:inline-grid; place-items:center; font-size:12px; font-weight:800; background:#f1e7eb; color:var(--p); }
    .tf .tq-pf.on .n { background:#fff; color:#660f2b; } .tf .tq-pf.count { cursor:default; background:#f7f7f8; color:var(--mut); } .tf .tq-pf.count:hover { border-color:var(--line); color:var(--mut); } .tf .tq-pf.count .n { background:#e3f1d6; color:#3f7a17; }
    .tf table.tq-tbl thead th { background:var(--p); color:#fff; padding:12px 16px; font-size:11.5px; } .tf table.tq-tbl td { vertical-align:top; }
    .tf .ct-st { display:inline-block; padding:7px 11px; border-radius:8px; font-size:13px; font-weight:700; line-height:1.3; background:#eef7e6; color:#3f7a17; border:1px solid #d5e9c2; }
    .tf .ct-st.amber { background:#fff6e0; color:#8a5a00; border-color:#f1dfae; } .tf .ct-st.red { background:#fbe9e7; color:#a3261d; border-color:#f1c8c3; } .tf .ct-st.grey { background:#f1f2f4; color:#555b66; border-color:#e0e2e6; }
    .tf .ct-ar { display:flex; align-items:center; justify-content:space-between; gap:14px; font-size:13px; padding:2px 0; min-width:230px; } .tf .ct-ar .l { display:inline-flex; align-items:center; gap:7px; color:var(--mut); font-weight:600; white-space:nowrap; } .tf .ct-ar .l i { color:var(--p); font-size:14px; width:16px; text-align:center; }
    .tf .ct-ar .v { display:inline-flex; align-items:center; gap:6px; font-weight:700; color:var(--ink); text-decoration:none; text-align:right; } .tf .ct-ar a.v:hover { color:var(--p); text-decoration:underline; }
    .tf .ct-ar .d { width:9px; height:9px; border-radius:50%; background:#b9bcc4; flex:none; } .tf .ct-ar .v.g .d { background:#4caf2f; } .tf .ct-ar .v.a .d { background:#f0a800; } .tf .ct-ar .v.r .d { background:#d93025; }
    .tf .tq-foot { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; } .tf .ct-pg { display:flex; gap:6px; align-items:center; }
    .tf .ct-pg button { min-width:34px; height:34px; padding:0 9px; border:1.5px solid var(--line); border-radius:8px; background:#fff; font-size:13px; font-weight:700; color:var(--ink); cursor:pointer; } .tf .ct-pg button.on { background:var(--p); border-color:var(--p); color:#fff; } .tf .ct-pg button:disabled { opacity:.4; cursor:default; }
    .tf .ct-fin { background:#fbf7f9; } .tf .ct-fin label { display:flex; gap:9px; align-items:flex-start; font-size:14px; font-weight:600; margin:6px 0; cursor:pointer; } .tf .ct-fin input[type=checkbox] { width:17px; height:17px; margin-top:2px; accent-color:var(--p); }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <div class="ct-top"><h2>Confirmed Tours</h2><a class="ct-prev" href="<?= base_url(($intake_url ?? 'tour/intake') . '/previous') ?>" title="Search and view the completed tours"><i class="bi bi-clock-history"></i> Previous Tours</a></div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Desk tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Inquiries</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div class="tq-card">
                    <div class="tq-lh"><div id="chips"></div><label class="tq-search"><i class="bi bi-search"></i><input id="f_q" placeholder="Search by tour no., customer or tour" title="Tour number, customer (agent), group or tour name"></label></div>
                    <div class="tq-fl" id="filters">
                        <label class="tq-sel dt"><span>Travel date</span><input type="date" id="f_from" title="Arriving from"><i>–</i><input type="date" id="f_to" title="Arriving to"></label>
                        <button class="tq-clear" id="f_clear" type="button" title="Clear every filter"><i class="bi bi-x-lg"></i> Clear</button>
                        <label class="tq-sel"><span>Agent</span><select id="f_handler" title="Handling agent — whose tour it is">
                            <option value="">All</option>
                            <?php foreach ((array) ($handlers ?? []) as $h): ?>
                                <option value="<?= esc($h['id'] !== null ? (string) $h['id'] : (string) $h['name']) ?>"><?= esc($h['name']) ?></option>
                                <?php endforeach; ?>
                        </select></label>
                        <label class="tq-sel"><span>Customer</span><select id="f_customer" title="Customer (agent)"><option value="">All</option></select></label>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="tq-tbl">
                            <thead><tr><th>Tour</th><th>Customer &amp; Tour</th><th>Travel dates</th><th>Pax</th><th>Status</th><th>Arrangements</th><th style="text-align:right">Actions</th></tr></thead>
                            <tbody id="rows"><tr><td colspan="7" class="empty">Loading…</td></tr></tbody>
                        </table>
                    </div>
                    <div class="tq-foot"><span id="list_foot"></span><span class="ct-pg" id="pager"></span></div>
                </div>

                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;
    const BASE = '<?= base_url($page_url ?? 'tour/intake/confirmed') ?>';
    const INTAKE = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const PAGE   = '<?= base_url($page_url ?? 'tour/intake/confirmed') ?>';
    const HOTELS = '<?= base_url('tour/hotel_bookings') ?>';
    const TRANSPORT = '<?= base_url('tour/transport_ops') ?>';
    const BOARD = '<?= base_url('tour/board') ?>';
    // a link only where this sign-in already holds the screen behind it; everything else is a label
    const CAN = { hotel: <?= !empty($can_hotel) ? 'true' : 'false' ?>, transport: <?= !empty($can_transport) ? 'true' : 'false' ?>, board: <?= !empty($can_board) ? 'true' : 'false' ?> };

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmt = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short' }); };
    const fmtY = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); };
    const when = s => s ? String(s).slice(0, 16) : '';
    const STAGE = { confirmed:['Confirmed','pill-blue'], operating:['Operating','pill-teal'], completed:['Completed','pill-green'], closed:['Closed','pill-green'], cancelled:['CANCELLED','pill-red'] };
    // tf-confirmed-r4: the place of a tour is decided by the server — Confirmed until its arrival date, Arrived – Finalization from
    // that day, Completed once its finalization is recorded (counted here, listed only on Previous Tours), Cancelled when cancelled.
    const CHIPS = [['live', 'Confirmed'], ['arrived', 'Arrived \u2013 Finalization'], ['completed', 'Completed'], ['cancelled', 'Cancelled'], ['all', 'All']];
    const groupOf = t => t.group;
    const PER = 10; let PG = 1;
    let FILTER = 'live', DATA = { tours: [], counts: {} };
    const AS_OF = (m => m ? '?as_of=' + m[1] : '')(/[?&]as_of=(\d{4}-\d{2}-\d{2})/.exec(location.search));   // honoured by the server on the developer's machine only

    // tf-workflow-r1: "the client accepted" and "every service is confirmed" are two different things,
    // and the line says which one this tour is at. Only the second one reads Confirmed.
    const stagePill = (t) => {
        const box = (cls, text, sub) => `<span class="ct-st ${cls}">${text}</span>${sub ? `<small>${sub}</small>` : ''}`;
        if (t.stage === 'cancelled') {
            return box('red', 'Cancelled', t.cancelled_at ? 'on ' + esc(when(t.cancelled_at)) : '')
                + `<small style="color:#a3261d"><b style="display:inline">Reason:</b> ${esc(t.cancel_why || 'not recorded')}</small>`
                + `<small><b style="display:inline">Refunds / charges outstanding:</b> none recorded for this tour</small>`;
        }
        if (t.reopened) {
            return box('red', '<i class="bi bi-exclamation-triangle-fill"></i> Changed after final confirmation', esc(t.reopened.line || ''))
                + `<small>the agent was told on ${esc(when(t.final_confirmed_at))} \u2014 finish the work again, then confirm again</small>`;
        }
        const sold = t.quote_version ? `sold as v${t.quote_version} \u00b7 ${esc(fmtY(String(t.confirmed_at || '').slice(0, 10)))}` : '';
        if (t.group === 'arrived') {
            return box('amber', 'Arrived \u2013 finalization', t.can_finalize ? 'tour finished \u2014 settle payments, then finalize' : 'on tour \u2014 departs ' + esc(fmtY(t.departure_date)));
        }
        if (t.final_confirmed_at) { return box('', '<i class="bi bi-patch-check-fill"></i> Confirmed', 'to the agent ' + esc(when(t.final_confirmed_at)) + (t.final_confirmed_by ? ' \u00b7 ' + esc(t.final_confirmed_by) : '')); }
        if (t.ready && t.ready.ok) { return box('', 'All departments confirmed', 'ready for the final confirmation'); }
        return box('', 'Client accepted \u2013 reservations pending', sold);
    };
    // tf-confirmed-r4: one arrangement = one line: what it is, a signal dot, and where it stands
    const arow = (icon, label, cls, text, href, title) => `<div class="ct-ar" title="${esc(title || '')}"><span class="l"><i class="${icon}"></i>${label}</span>`
        + (href ? `<a class="v ${cls}" href="${esc(href)}"><i class="d"></i>${text}</a>` : `<span class="v ${cls}"><i class="d"></i>${text}</span>`) + '</div>';
    const laneRow = (icon, label, x, href) => {
        x = x || {};
        if (!x.of) { return arow(icon, label, '', 'Not required', null, 'Nothing of this kind on the tour'); }
        const cls = x.problem ? 'r' : (x.done >= x.of ? 'g' : 'a');
        const word = x.problem ? `${x.problem} problem` : (x.done >= x.of ? 'Confirmed' : 'Pending');
        return arow(icon, `${label} ${x.done} of ${x.of}`, cls, word, href, `${x.done} of ${x.of} done`);
    };
    const crewRow = (icon, label, x, href) => {
        x = x || {};
        if (x.state === 'not_required' || x.state === 'no_vehicle_line') { return arow(icon, label, '', 'Not required', null, ''); }
        if (x.state === 'allocated') { const n = (x.names || []).map(esc).join(', '); return arow(icon, label, 'g', n || 'Assigned', href, 'Assigned'); }
        return arow(icon, label, 'r', 'To allocate' + (x.of > 1 ? ` (${x.done}/${x.of})` : ''), href, 'Still to allocate');
    };
    // the same pills as the shared status strip (window.tdStrip), split into the two desks' columns
    const pill = (cls, icon, text, title, href) => href
        ? `<a class="s ${cls} go" href="${esc(href)}" title="${esc((title || '') + ' — open on the desk')}"><i class="bi ${icon}"></i>${text}</a>`
        : `<span class="s ${cls}" title="${esc(title || '')}"><i class="bi ${icon}"></i>${text}</span>`;
    const lane = x => x.state === 'done' ? 'done' : (x.state === 'problem' ? 'problem' : (x.state === 'none' ? 'none' : 'open'));
    const crew = (x, noun, href) => {
        x = x || {};
        const cls = x.state === 'allocated' ? 'done' : (x.state === 'not_required' || x.state === 'no_vehicle_line' ? 'none' : 'open');
        const word = x.state === 'allocated' ? 'allocated' : (x.state === 'not_required' ? 'not required' : (x.state === 'no_vehicle_line' ? 'no vehicle line' : 'to allocate'));
        const names = (x.names || []).map(esc).join(', ');
        const text = x.state === 'allocated' && names ? `${noun} <b>${names}</b>${x.of > 1 ? ` (${x.done}/${x.of})` : ''}` : `${noun} ${word}${x.of > 1 ? ` (${x.done}/${x.of})` : ''}`;
        return pill(cls, noun === 'Vehicle' ? 'oci oci-car' : 'bi-person', text, `${noun}: ${word}`, x.state === 'not_required' || x.state === 'no_vehicle_line' ? null : href);
    };
    // tf-workflow-r1: the final confirmation. Green and pressable only when every required section is
    // green; until then it says what is still being arranged, and cannot be pressed at all.
    const finalBtn = (t) => {
        if (t.stage === 'cancelled') { return ''; }
        const r = t.ready || {};
        const label = r.again ? 'Send updated confirmation' : 'Final confirm';
        if (r.ok) {
            return `<button class="btn btn-sm" style="background:${r.again ? '#8a1c30' : '#78b848'};border-color:transparent;color:#fff;font-weight:600" data-final="${t.id}">`
                 + `<i class="bi bi-patch-check"></i> ${esc(label)}</button>`;
        }
        if (t.final_confirmed_at && !t.reopened) { return ''; }
        // tf-confirmed-r2: what is being waited for is printed, not hidden in a tooltip nobody can reach on a tablet
        const waiting = (r.waiting || []);
        return `<button class="btn btn-light btn-sm" disabled><i class="bi bi-hourglass-split"></i> ${esc(label)}</button>`
             + `<div class="help" style="color:var(--tf-amber);max-width:230px">`
             + (waiting.length
                 ? 'Waiting for:<br>' + waiting.map(w => '\u00b7 ' + esc(w)).join('<br>')
                 : esc(r.why || 'The teams are still arranging this tour.'))
             + `</div>`;
    };

    // tf-confirmed-r2: the confirmed itinerary goes to the agent from this line — the address, the issued
    // version and the vouchers are already chosen. It posts to the same Communications send the panel uses,
    // so the sender, the company copy rule, the record and a failure are all handled exactly as before.
    const mailBtn = (t) => {
        if (!t.final_confirmed_at || t.reopened) { return ''; }
        return `<button class="btn btn-sm" style="background:#8b1d40;border-color:#8b1d40;color:#fff" data-send="${t.id}">`
             + `<i class="bi bi-envelope-paper"></i> Send the confirmed itinerary</button>`;
    };


    // tf-confirmed-r3: the Confirm button of a line. Burgundy while work is outstanding — it opens Operations Control, where the
    // work is checked; green once every section is done — pressing it then records the final confirmation (the same step as before).
    const isDone = t => !!t.final_confirmed_at && !t.reopened;   // confirmed and finalised: its button is green and the line sinks to the foot
    const confirmBtn = (t) => {
        const go = `${PAGE}/${t.id}/control`, r = t.ready || {};
        if (t.stage === 'cancelled') { return '<span class="tq-open ct-conf off">Cancelled</span>'; }
        if (r.ok) { return `<button type="button" class="tq-open ct-conf" data-final="${t.id}" title="Everything is checked — press to record the final confirmation"><i class="bi bi-patch-check"></i> ${r.again ? 'Confirm again' : 'Confirm'}</button>`; }
        if (isDone(t)) { return `<span class="tq-open ct-conf ok" title="Confirmed and finalised"><i class="bi bi-check-lg"></i> Confirmed</span>`; }
        return `<a class="tq-open ct-conf" href="${go}" title="Work is still outstanding — open the tour to check and complete it, then confirm">Confirm</a>`;
    };
    const waitNote = (t) => {
        const r = t.ready || {};
        if (t.stage === 'cancelled' || r.ok || (t.final_confirmed_at && !t.reopened)) { return ''; }
        const w = r.waiting || [];
        return `<div class="ct-wait">${w.length ? 'Waiting for: ' + w.map(esc).join(' · ') : esc(r.why || 'The teams are still arranging this tour.')}</div>`;
    };

    const work = (state, at, by, done) => state === 'complete'
        ? `<span class="pill pill-green"><i class="bi bi-patch-check-fill"></i> ${done}</span>${at ? `<div class="help">${esc(when(at))}${by ? ' · ' + esc(by) : ''}</div>` : ''}`
        : (state === 'pending' ? '<span class="pill pill-amber">in progress</span>' : '<span class="help">nothing for this desk</span>');

    function renderChips() {
        const c = DATA.counts || {};
        document.getElementById('chips').innerHTML = CHIPS.map(([k, l]) => k === 'completed'
            ? `<span class="tq-pf count" title="A count only \u2014 completed tours are searched and viewed on Previous Tours">${l} <span class="n">${c[k] ?? 0}</span></span>`
            : `<button type="button" class="tq-pf ${FILTER === k ? 'on' : ''}" data-chip="${k}">${l} <span class="n">${c[k] ?? 0}</span></button>`).join('');
        document.querySelectorAll('[data-chip]').forEach(x => x.addEventListener('click', () => { FILTER = x.dataset.chip; PG = 1; render(); }));
    }

    function render() {
        renderChips();
        const cust = document.getElementById('f_customer'), want = cust.value;
        const names = [...new Set((DATA.tours || []).map(t => t.agent_name).filter(Boolean))].sort();
        cust.innerHTML = '<option value="">All</option>' + names.map(n => `<option${n === want ? ' selected' : ''}>${esc(n)}</option>`).join('');
        const tours = (DATA.tours || []).filter(t => (FILTER === 'all' || groupOf(t) === FILTER) && (!want || t.agent_name === want));
        // the lines still to confirm stay on top; the confirmed (green) ones go to the foot — each part keeps its own order, nearest arrival first
        if (FILTER === 'live') tours.sort((x, y) => Number(isDone(x)) - Number(isDone(y)));
        const total = tours.length, pages = Math.max(1, Math.ceil(total / PER)); if (PG > pages) PG = pages;
        const from = (PG - 1) * PER, shown = tours.slice(from, from + PER);
        document.getElementById('list_foot').textContent = total ? `${from + 1}\u2013${from + shown.length} of ${total} tour${total === 1 ? '' : 's'}` : '0 tours';
        const pg = document.getElementById('pager');
        pg.innerHTML = `<button type="button" data-pg="${PG - 1}" ${PG <= 1 ? 'disabled' : ''} title="Previous page"><i class="bi bi-chevron-left"></i></button>`
            + Array.from({ length: pages }, (_, i) => `<button type="button" data-pg="${i + 1}" class="${PG === i + 1 ? 'on' : ''}">${i + 1}</button>`).join('')
            + `<button type="button" data-pg="${PG + 1}" ${PG >= pages ? 'disabled' : ''} title="Next page"><i class="bi bi-chevron-right"></i></button>`;
        pg.querySelectorAll('[data-pg]').forEach(x => x.addEventListener('click', () => { PG = Number(x.dataset.pg); render(); }));
        const tb = document.getElementById('rows');
        const none = { live: 'No confirmed tours are waiting to arrive.', arrived: 'No tour is in finalization. A confirmed tour moves here by itself on its arrival date.', cancelled: 'No cancelled tours.', all: 'No tours here.' };
        if (!total) { tb.innerHTML = `<tr><td colspan="7" class="empty">${none[FILTER] || none.all}</td></tr>`; return; }
        tb.innerHTML = shown.map(t => {
            const p = t.progress || {}, h = p.hotels || {}, a = p.activities || {};
            const toHotel = CAN.hotel ? `${HOTELS}?tour=${t.id}` : null, toTransport = CAN.transport ? `${TRANSPORT}?tour=${t.id}` : null;
            const programme = CAN.board ? `${BOARD}/tour/${t.id}/programme` : (CAN.hotel ? `${HOTELS}/tour/${t.id}/programme` : (CAN.transport ? `${TRANSPORT}/tour/${t.id}/programme` : null));
            const send = (t.final_confirmed_at && !t.reopened) ? `<button type="button" data-send="${t.id}"><i class="bi bi-envelope-paper"></i> Send the confirmed itinerary</button>` : '';
            return `<tr class="trow" data-tour="${t.id}">
                <td><b>${esc(t.tour_no)}</b>${t.inquiry_no ? `<small>Inquiry ${esc(t.inquiry_no)}</small>` : ''}${t.handler_name ? `<small title="Handling agent — whose tour it is"><i class="bi bi-person-badge"></i> ${esc(t.handler_name)}</small>` : ''}</td>
                <td><b>${esc(t.agent_name || '—')}</b><small>${esc(t.tour_name || '')}</small></td>
                <td style="white-space:nowrap">${fmt(t.arrival_date)} – ${fmtY(t.departure_date)}${t.nights != null ? `<small>${t.nights} night${Number(t.nights) === 1 ? '' : 's'}</small>` : ''}</td>
                <td class="px">${t.pax_total ?? '—'}</td>
                <td>${stagePill(t)}${Number(t.amending) ? '<small style="color:#8a5a00">being amended</small>' : ''}${t.problems ? `<small style="color:#a3261d">${t.problems} problem${t.problems === 1 ? '' : 's'}</small>` : ''}</td>
                <td>${laneRow('bi oci oci-bed', 'Hotels', h, toHotel)}${laneRow('bi bi-ticket-perforated oc-tilt', 'Activities', a, toHotel)}
                    ${crewRow('bi oci oci-car', 'Vehicle', p.vehicle, toTransport)}${crewRow('bi bi-person', 'Driver', p.driver, toTransport)}${crewRow('bi bi-person-badge', 'Guide', p.guide, toTransport)}</td>
                <td class="ac"><div class="acts"><div class="ct-go"><a class="tq-open go" href="${PAGE}/${t.id}/control" title="Open the confirmed tour — Operations Control">Open <i class="bi bi-arrow-right"></i></a>${t.group === 'arrived' ? `<button type="button" class="tq-open ct-conf" data-fin="${t.id}" title="Record that the tour has finished and every payment is settled">Finalize</button>` : confirmBtn(t)}
                    <details class="ct-more"><summary title="More for this tour"><i class="bi bi-three-dots"></i></summary><div class="dd">
                        <a href="${PAGE}/${t.id}/control"><i class="bi bi-box-arrow-up-right"></i> Open Operations Control</a>
                        ${send}
                        <a href="${INTAKE}/comms/${t.intake_id}"><i class="bi bi-chat-left-text"></i> Communications &amp; History</a>
                        ${CAN.board ? `<a href="${BOARD}/tour/${t.id}/record"><i class="bi bi-journal-text"></i> Complete record</a>` : ''}
                        ${programme ? `<a href="${programme}"><i class="bi bi-printer"></i> Programme</a>` : ''}
                    </div></details></div>${t.group === 'arrived' ? '' : waitNote(t)}</div></td></tr>`;
        }).join('');
        wireFinal();
        wireSend();
        wireFin();
    }

    // tf-final-r1: Finalize opens under its line. Completed only when staff tick both statements and press the button;
    // the tour then leaves this panel (the Completed count goes up by one) and is found on Previous Tours. Its record and history stay.
    function wireFin() {
        document.querySelectorAll('[data-fin]').forEach(b => b.addEventListener('click', () => {
            const t = (DATA.tours || []).find(x => String(x.id) === String(b.dataset.fin)) || {}, tr = b.closest('tr');
            const old = document.getElementById('finbox'); if (old) { const same = old.dataset.id === String(t.id); old.remove(); if (same) return; }
            const row = document.createElement('tr'); row.id = 'finbox'; row.dataset.id = String(t.id); row.className = 'ct-fin';
            row.innerHTML = `<td colspan="7"><div style="max-width:760px"><b style="font-size:15px">Finalization \u2014 ${esc(t.tour_no || '')}</b>`
                + (t.can_finalize ? '' : `<small style="color:#a3261d;font-size:13px;margin:4px 0">This tour has not finished yet \u2014 it departs ${esc(fmtY(t.departure_date))}. It can be finalized from that day.</small>`)
                + `<label><input type="checkbox" id="fin_a" ${t.can_finalize ? '' : 'disabled'}> The tour has finished.</label>`
                + `<label><input type="checkbox" id="fin_b" ${t.can_finalize ? '' : 'disabled'}> All payments and outstanding balances are settled.</label>`
                + `<div style="display:flex;gap:10px;align-items:center;margin-top:8px;flex-wrap:wrap"><button type="button" class="tq-open go" id="fin_go" disabled style="opacity:.5">Finalization complete</button>`
                + `<button type="button" class="tq-clear" id="fin_x">Close</button><span id="fin_msg" style="font-size:13px;font-weight:700"></span></div>`
                + `<small style="font-size:12.5px;margin-top:6px">The tour moves to Completed and is then found on Previous Tours. Its record and history are kept.</small></div></td>`;
            tr.parentNode.insertBefore(row, tr.nextSibling);
            const A = document.getElementById('fin_a'), B = document.getElementById('fin_b'), go = document.getElementById('fin_go'), msg = document.getElementById('fin_msg');
            const gate = () => { const ok = A.checked && B.checked; go.disabled = !ok; go.style.opacity = ok ? '' : '.5'; };
            A.addEventListener('change', gate); B.addEventListener('change', gate);
            document.getElementById('fin_x').addEventListener('click', () => row.remove());
            go.addEventListener('click', async () => {
                if (go.disabled) return; go.disabled = true; go.textContent = 'Saving\u2026';
                let j = null;
                try { const r = await fetch(PAGE + '/' + t.id + '/finalize' + AS_OF, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ finished: A.checked, settled: B.checked }) }); j = await r.json(); } catch (e) { j = null; }
                if (!j || j.status !== 200) { msg.style.color = '#a3261d'; msg.textContent = (j && j.message) || 'It was not saved. Nothing was changed.'; go.disabled = false; go.textContent = 'Finalization complete'; return; }
                msg.style.color = '#3f7a17'; msg.textContent = j.message || 'Completed.'; setTimeout(load, 1500);
            });
        }));
    }

    // tf-workflow-r1: pressing it records the final confirmation on the tour. It sends nothing by
    // itself — the handler then sends the confirmed itinerary from Communications & History, where
    // the e-mail and its attachments are kept on the record with everything else.

    // tf-confirmed-r2: the confirmed-itinerary letter, opened under the line it belongs to. Nothing is sent by
    // opening it; the button asks in place and sends on the second press. No browser dialog anywhere.
    function wireSend() {
        document.querySelectorAll('[data-send]').forEach(b => b.addEventListener('click', () => {
            const t = (DATA.tours || []).find(x => String(x.id) === String(b.dataset.send)) || {};
            const m = t.mail || {};
            const tr = b.closest('tr');
            if (document.getElementById('sendbox-' + t.id)) { return; }
            document.querySelectorAll('.sendbox').forEach(x => x.remove());
            const row = document.createElement('tr');
            row.className = 'sendbox';
            row.innerHTML = `<td colspan="7" style="background:#fbfcfe">
                <div style="max-width:880px">
                  <div style="font-weight:600;color:var(--tf-navy);margin-bottom:6px">Confirmed itinerary to the agent — ${esc(t.tour_no || '')}</div>
                  ${(m.missing || []).length ? `<div class="not-installed" style="margin-bottom:8px">${(m.missing || []).map(esc).join('<br>')}</div>` : ''}
                  <div class="row g-2">
                    <div class="col-md-6"><label class="help">To</label><input class="form-control form-control-sm" id="sb_to-${t.id}" value="${esc(m.to || '')}"></div>
                    <div class="col-md-6"><label class="help">Copy to (optional)</label><input class="form-control form-control-sm" id="sb_cc-${t.id}" value=""></div>
                    <div class="col-12"><label class="help">Subject</label><input class="form-control form-control-sm" id="sb_subj-${t.id}" value="${esc(m.subject || '')}"></div>
                    <div class="col-12"><label class="help">Message</label><textarea class="form-control form-control-sm" rows="7" id="sb_body-${t.id}">${esc((m.greeting || '') + (m.body || ''))}</textarea></div>
                    <div class="col-12"><label class="help">Attachments</label><div id="sb_docs-${t.id}">
                      ${(m.docs || []).map((d, i) => `<label style="display:block;font-size:12.5px"><input type="checkbox" data-doc="${esc(d.key)}" ${d.on ? 'checked' : ''}> ${esc(d.label)}</label>`).join('') || '<span class="help">Nothing to attach.</span>'}
                    </div></div>
                  </div>
                  <div class="acts" style="flex-direction:row;gap:8px;margin-top:10px;align-items:center">
                    <button class="btn btn-sm" style="background:#8b1d40;border-color:#8b1d40;color:#fff" id="sb_go-${t.id}">Send it</button>
                    <span class="help" id="sb_why-${t.id}"></span>
                    <button class="btn btn-light btn-sm" id="sb_x-${t.id}">Close</button>
                    <span id="sb_msg-${t.id}" style="font-size:13px;font-weight:600"></span>
                  </div>
                  <div class="help" style="margin-top:6px">It goes from your own mailbox, with the company copy rule, and is kept on this tour's Communications &amp; History with its attachments and the delivery result.</div>
                </div></td>`;
            row.id = 'sendbox-' + t.id;
            tr.parentNode.insertBefore(row, tr.nextSibling);
            const msg = (text, bad) => { const el = document.getElementById('sb_msg-' + t.id); el.textContent = text; el.style.color = bad ? '#8a1c30' : '#1f8a4c'; };
            document.getElementById('sb_x-' + t.id).addEventListener('click', () => row.remove());
            const go = document.getElementById('sb_go-' + t.id);
            // tf-supmail-r2 (Shantha, 4 Nov 2026): a warning beside a live button reads as "send it anyway".
            // While there is no address to send to, the button is OFF and says so; it comes back the moment
            // an address is typed. The server refuses an empty recipient regardless, so this is the plain
            // version of a rule that holds either way.
            const addrBox = document.getElementById('sb_to-' + t.id);
            const why = document.getElementById('sb_why-' + t.id);
            const gate = () => {
                const v = (addrBox.value || '').trim();
                const ok = v !== '' && /^[^@\s,;]+@[^@\s,;]+\.[^@\s,;]+$/.test(v.split(/[,;\s]+/)[0]);
                go.disabled = !ok;
                go.style.opacity = ok ? '' : '.5';
                go.style.cursor = ok ? '' : 'not-allowed';
                go.textContent = ok ? (go.dataset.armed === '1' ? go.textContent : 'Send it') : 'Send it';
                why.textContent = ok ? '' : (v === '' ? 'No address to send to — nothing can go.' : 'That does not look like an e-mail address.');
                why.style.color = '#8a1c30';
                if (!ok) { go.dataset.armed = ''; }
                addrBox.style.borderColor = ok ? '' : '#8a1c30';
            };
            addrBox.addEventListener('input', gate);
            gate();
            go.addEventListener('click', async () => {
                const to = addrBox.value.trim();
                if (go.disabled || !to) { gate(); msg('There is no address to send to. Put the agent\u2019s address on the inquiry, or type one here.', true); return; }
                if (go.dataset.armed === '1' && Date.now() - Number(go.dataset.armedAt || 0) < 500) { return; }
                if (go.dataset.armed !== '1') {
                    go.dataset.armed = '1'; go.dataset.armedAt = String(Date.now()); go.textContent = 'Yes — send to ' + to;
                    msg('It goes now, from your own mailbox, and is kept on the record.');
                    setTimeout(() => { if (go.dataset.armed === '1') { go.dataset.armed = ''; go.textContent = 'Send it'; msg(''); } }, 10000);
                    return;
                }
                go.dataset.armed = ''; go.disabled = true; go.textContent = 'Sending…'; msg('Sending…');
                const docs = [...document.querySelectorAll('#sb_docs-' + t.id + ' input:checked')].map(i => i.dataset.doc);
                let j = null;
                try {
                    const r = await fetch(INTAKE + '/comms/' + t.intake_id + '/send', {
                        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ to: to, cc: document.getElementById('sb_cc-' + t.id).value.trim(),
                            subject: document.getElementById('sb_subj-' + t.id).value,
                            body: document.getElementById('sb_body-' + t.id).value, docs: docs })
                    });
                    j = await r.json();
                } catch (e) { j = null; }
                go.disabled = false; go.textContent = 'Send it';
                if (!j || j.status !== 200 || j.ok === false) {
                    msg((j && j.message) || 'It was not sent. The attempt is kept on the record.', true);
                    return;
                }
                msg(j.message || 'Sent.');
            });
        }));
    }

    function wireFinal() {
        // tf-confirmux-r1: no browser confirm() or alert(). A browser that suppresses dialogs made the
        // press look dead, and a dialog is one more thing to read: the question and the answer are in the row.
        const say = (tr, text, bad) => {
            let box = tr.querySelector('.final-say');
            if (!box) { box = document.createElement('div'); box.className = 'final-say mt-2'; box.style.fontSize = '13px'; box.style.fontWeight = '600';
                        (tr.querySelector('.acts') || tr.lastElementChild).appendChild(box); }
            box.style.color = bad ? '#8a1c30' : '#2f7d32';
            box.innerHTML = text;
            return box;
        };
        document.querySelectorAll('[data-final]').forEach(b => b.addEventListener('click', async () => {
            const id = b.dataset.final;
            const tr = b.closest('tr');
            const t  = (DATA.tours || []).find(x => String(x.id) === String(id)) || {};
            if (b.dataset.armed === '1' && Date.now() - Number(b.dataset.armedAt || 0) < 500) { return; }
            if (b.dataset.armed !== '1') {
                b.dataset.armed = '1'; b.dataset.armedAt = String(Date.now());
                b.innerHTML = '<i class="bi bi-patch-check"></i> Yes — confirm ' + esc(t.tour_no || 'this tour');
                say(tr, 'Every department has confirmed. Press again to mark ' + esc(t.tour_no || 'this tour')
                      + ' <b>Confirmed</b> and record who did it. Nothing is e-mailed by this press.');
                return;
            }
            b.disabled = true;
            b.innerHTML = '<i class="bi bi-hourglass-split"></i> Confirming\u2026';
            let j = null;
            try {
                const r = await fetch(PAGE + '/' + id + '/final', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                j = await r.json();
            } catch (e) { j = null; }
            if (!j || j.status !== 200) {
                say(tr, esc((j && j.message) || 'The final confirmation did not go through. Nothing was changed.'), true);
                b.disabled = false; b.dataset.armed = ''; b.innerHTML = '<i class="bi bi-patch-check"></i> Final confirm';
                return;
            }
            const intake = j.data && j.data.intake_id;
            say(tr, esc(j.message || 'Confirmed.') + (intake
                ? ' <a class="btn btn-sm mt-1" style="background:#8b1d40;border-color:#8b1d40;color:#fff;text-decoration:none" href="'
                  + INTAKE + '/comms/' + intake + '"><i class="bi bi-chat-left-text"></i> Send the confirmed itinerary</a>' : ''));
            setTimeout(load, 2500);
        }));
    }

    async function load() {
        const qs = new URLSearchParams({ q: document.getElementById('f_q').value || '', handler: document.getElementById('f_handler').value || '', from: document.getElementById('f_from').value || '', to: document.getElementById('f_to').value || '' }).toString();
        try {
            const r = await fetch(BASE + '/data?' + qs + AS_OF.replace('?', '&'), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await r.json();
            if (j.status !== 200) { document.getElementById('rows').innerHTML = `<tr><td colspan="7" class="empty">${esc(j.message || j.msg || 'Could not load the tours.')}</td></tr>`; return; }
            DATA = j.data || { tours: [], counts: {} };
        } catch (e) {
            document.getElementById('rows').innerHTML = '<tr><td colspan="7" class="empty">Could not load the tours.</td></tr>';
            return;
        }
        render();
    }

    document.getElementById('f_q').addEventListener('input', () => { clearTimeout(window.__cq); window.__cq = setTimeout(load, 250); });
    ['f_handler', 'f_from', 'f_to'].forEach(id => document.getElementById(id).addEventListener('change', load));
    document.getElementById('f_customer').addEventListener('change', render);
    document.addEventListener('click', e => { document.querySelectorAll('.ct-more[open]').forEach(d => { if (!d.contains(e.target)) d.removeAttribute('open'); }); });
    document.getElementById('f_clear').addEventListener('click', () => { ['f_handler', 'f_q', 'f_from', 'f_to', 'f_customer'].forEach(id => document.getElementById(id).value = ''); FILTER = 'live'; load(); });
    // back from a desk (the tab shown again): read again, so what was done there shows here
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') load(); });
    load();
})();
</script>
</body>
</html>
