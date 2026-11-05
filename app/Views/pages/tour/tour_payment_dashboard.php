<?= view('layouts/head') ?>
<?php
/**
 * tf-paydesk-r1 (10 Oct 2026) — the Tour Payment Dashboard in the approved design: the tour's header and figures,
 * Today's USD Exchange Rate, the payment schedule day by day in itinerary order, and the chain's steps on each line
 * (request → management approval → voucher → bank initiation → Paid → remittance advice). The data comes from
 * tour/tour_payment_dashboard/desk; the request / special-rate / rate-review code of the earlier dashboard is kept
 * as it was (tf-offers-r1/r3), so those flows behave exactly as before.
 */
$TPD = $tpd_perms ?? [];
$TPD_READY = !empty($tpd_ready);
?>
<style>
    /* ---------- tf-paydesk-r1: the system's colours (Tour Desk theme) ---------- */
    .tpd { --p:#842484; --p2:#8f2a8d; --soft:#f3e7f3; --ink:#2c3e50; --muted:#6c757d; --line:#e4e8ee; --bg:#f6f7fa; --card:#fff; --radius:14px;
           --green:#1f8a4c; --green-soft:#e4f4ea; --blue:#2563eb; --blue-soft:#e8effd; --indigo:#4f46e5; --indigo-soft:#eceafd; --violet:#7c3aed; --violet-soft:#f1eafe;
           --teal:#0f766e; --teal-soft:#dff4f1; --amber:#b7791f; --amber-soft:#fdf3e1; --red:#b23a32; --red-soft:#fbe9e7; --purple:#6d28d9; --purple-soft:#ede9fe;
           --grey:#6c757d; --grey-soft:#eef0f3; --yellow:#a16207; --yellow-soft:#fef9c3; --olive:#4d7c0f; --olive-soft:#ecfccb;
           color:var(--ink); font-size:13.5px; }
    .tpd .card { border:1px solid var(--line); border-radius:var(--radius); box-shadow:0 1px 3px rgba(16,24,40,.05); }
    .tpd .btn-p { background:var(--p); border-color:var(--p); color:#fff; }
    .tpd .btn-p:hover { background:var(--p2); border-color:var(--p2); color:#fff; }
    .tpd .btn-outline-p { border:1px solid var(--p); color:var(--p); background:#fff; }
    .tpd .btn-outline-p:hover { background:var(--soft); color:var(--p); }
    .tpd .text-p { color:var(--p) !important; }
    .tpd .muted { color:var(--muted); }
    .tpd .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .tpd .num { font-variant-numeric: tabular-nums; }

    /* header */
    .tpd .tpd-head { background:linear-gradient(135deg, var(--p) 0%, #5b1a5b 100%); color:#fff; border-radius:var(--radius); padding:16px 18px; }
    .tpd .tpd-head h4 { color:#fff; margin:0; font-weight:700; letter-spacing:.2px; }
    .tpd .tpd-head .chip { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22); border-radius:999px; padding:3px 10px; font-size:12px; margin:3px 4px 0 0; }
    .tpd .tpd-head .chip b { font-weight:600; }
    .tpd .tpd-head .tid { background:#fff; color:var(--p); border-radius:8px; padding:2px 8px; font-weight:700; font-size:12.5px; }
    .tpd .tpd-head .btn { border-radius:10px; }
    .tpd .tpd-head .btn-light { background:#fff; color:var(--p); border-color:#fff; font-weight:600; }
    .tpd .tpd-head .btn-ghost { background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.35); }
    .tpd .tpd-head .btn-ghost:hover { background:rgba(255,255,255,.22); color:#fff; }

    /* KPI tiles */
    .tpd .kpi { background:var(--card); border:1px solid var(--line); border-radius:var(--radius); padding:14px 16px; height:100%; }
    .tpd .kpi .lbl { font-size:12px; color:var(--muted); }
    .tpd .kpi .val { font-size:1.35rem; font-weight:700; margin:2px 0; }
    .tpd .kpi .sub { font-size:12px; color:var(--muted); }
    .tpd .kpi.warn .val { color:var(--amber); } .tpd .kpi.bad .val { color:var(--red); } .tpd .kpi.good .val { color:var(--green); }

    /* exchange rate */
    .tpd .fx { background:linear-gradient(135deg,#0f5e52 0%, #176b63 55%, #1f7a6f 100%); color:#fff; border-radius:var(--radius); padding:16px 18px; }
    .tpd .fx .ico { width:46px; height:46px; border-radius:12px; background:rgba(255,255,255,.14); display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
    .tpd .fx h5 { color:#fff; margin:0; font-weight:700; }
    .tpd .fx .sub { color:rgba(255,255,255,.85); font-size:12.5px; }
    .tpd .fx .rate-box { background:#fff; color:#1d2733; border-radius:12px; padding:6px 12px; display:flex; align-items:center; gap:10px; }
    .tpd .fx .rate-box small { color:#6c757d; font-weight:600; letter-spacing:.3px; }
    .tpd .fx .rate-box input { border:0; outline:0; font-size:1.3rem; font-weight:700; width:120px; text-align:right; background:transparent; color:#1d2733; }
    .tpd .fx .rate-box .cur { font-size:1.3rem; font-weight:700; }
    .tpd .fx .btn-confirm { background:#e8a33d; border-color:#e8a33d; color:#1d2733; font-weight:700; border-radius:12px; padding:8px 16px; }
    .tpd .fx .btn-confirm:hover { background:#f0b254; border-color:#f0b254; }
    .tpd .fx .lock { font-size:12.5px; color:rgba(255,255,255,.9); }
    .tpd .fx.missing { background:linear-gradient(135deg,#7a2e2e 0%, #9a3b3b 100%); }

    /* schedule */
    .tpd .sched-h { padding:14px 18px 10px; }
    .tpd .sched-h h5 { margin:0; font-weight:700; }
    .tpd .filters { display:flex; flex-wrap:wrap; gap:6px; padding:0 18px 12px; }
    .tpd .filters .f { border:1px solid var(--line); background:#fff; color:var(--ink); border-radius:999px; padding:5px 12px; font-size:12.5px; font-weight:600; cursor:pointer; }
    .tpd .filters .f .n { color:var(--muted); font-weight:500; margin-left:4px; }
    .tpd .filters .f.on { background:var(--ink); color:#fff; border-color:var(--ink); }
    .tpd .filters .f.on .n { color:rgba(255,255,255,.75); }
    .tpd .filters .f.zero { opacity:.55; }
    .tpd .day { border-top:1px solid var(--line); }
    .tpd .day-h { display:flex; align-items:center; gap:12px; padding:12px 18px; cursor:pointer; background:#fafbfc; }
    .tpd .day-h:hover { background:#f3f4f7; }
    .tpd .date-tile { width:58px; min-width:58px; border-radius:12px; background:var(--green-soft); color:var(--green); text-align:center; padding:6px 0; line-height:1.1; }
    .tpd .date-tile b { display:block; font-size:1.15rem; }
    .tpd .date-tile small { font-size:10px; font-weight:700; letter-spacing:.4px; }
    .tpd .date-tile.none { background:var(--grey-soft); color:var(--grey); }
    .tpd .date-tile.done { background:var(--green); color:#fff; }
    .tpd .day-h .ttl { font-weight:700; font-size:14px; }
    .tpd .day-h .rt { color:var(--muted); font-size:12.5px; }
    .tpd .day-h .amt { margin-left:auto; text-align:right; }
    .tpd .day-h .amt b { font-size:15px; }
    .tpd .day-h .amt .s { color:var(--muted); font-size:12px; }
    .tpd .day-h .car { transition:transform .2s; color:var(--muted); }
    .tpd .day.closed .car { transform:rotate(-90deg); }
    .tpd .day.closed .day-b { display:none; }
    .tpd .line { display:grid; grid-template-columns: 44px minmax(0,1.5fr) minmax(0,.9fr) minmax(0,.9fr) minmax(0,.9fr) minmax(0,.9fr) 150px 170px; gap:10px 12px; align-items:start; padding:12px 18px; border-top:1px dashed var(--line); }
    .tpd .line:hover { background:#fcfbfd; }
    .tpd .line .ico { width:40px; height:40px; border-radius:12px; background:var(--grey-soft); display:flex; align-items:center; justify-content:center; font-size:1.1rem; color:var(--ink); }
    .tpd .line .ico.hotel { background:#eef2ff; color:#3730a3; } .tpd .line .ico.meal { background:#fef3c7; color:#92400e; } .tpd .line .ico.entrance { background:#fee2e2; color:#991b1b; }
    .tpd .line .ico.activity { background:#dcfce7; color:#166534; } .tpd .line .ico.transport, .tpd .line .ico.driver_advance, .tpd .line .ico.driver_settlement { background:#e0f2fe; color:#075985; } .tpd .line .ico.guide { background:#fae8ff; color:#86198f; }
    .tpd .line .sup { font-weight:700; font-size:14px; }
    .tpd .line .desc { color:var(--muted); font-size:12.5px; }
    .tpd .line .warn { font-size:12px; margin-top:3px; }
    .tpd .line .warn.danger { color:var(--red); } .tpd .line .warn.warning { color:var(--amber); } .tpd .line .warn.info { color:var(--blue); } .tpd .line .warn.success { color:var(--green); }
    .tpd .line .k { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.3px; }
    .tpd .line .v { font-weight:700; font-size:14px; }
    .tpd .line .v small { font-weight:500; color:var(--muted); font-size:11.5px; }
    .tpd .line .acts { display:flex; flex-direction:column; gap:5px; align-items:stretch; }
    .tpd .line .acts .btn { border-radius:10px; font-size:12.5px; padding:6px 10px; white-space:nowrap; }
    .tpd .line .more { grid-column:1 / -1; }
    .tpd .pill { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:4px 10px; font-size:12px; font-weight:700; white-space:nowrap; }
    .tpd .pill.grey { background:var(--grey-soft); color:var(--grey); } .tpd .pill.blue { background:var(--blue-soft); color:var(--blue); } .tpd .pill.indigo { background:var(--indigo-soft); color:var(--indigo); }
    .tpd .pill.violet { background:var(--violet-soft); color:var(--violet); } .tpd .pill.teal { background:var(--teal-soft); color:var(--teal); } .tpd .pill.green { background:var(--green-soft); color:var(--green); }
    .tpd .pill.olive { background:var(--olive-soft); color:var(--olive); } .tpd .pill.purple { background:var(--purple-soft); color:var(--purple); } .tpd .pill.yellow { background:var(--yellow-soft); color:var(--yellow); }
    .tpd .pill.red { background:var(--red-soft); color:var(--red); } .tpd .pill.amber { background:var(--amber-soft); color:var(--amber); }
    .tpd .pill.sm { font-size:11px; padding:2px 8px; }
    .tpd .stages { display:flex; gap:3px; margin-top:6px; }
    .tpd .stages i { display:block; height:5px; flex:1; border-radius:3px; background:var(--grey-soft); }
    .tpd .stages i.done { background:var(--green); } .tpd .stages i.now { background:var(--p); }
    .tpd .detail { background:#fafbfd; border:1px solid var(--line); border-radius:12px; padding:12px 14px; margin-top:6px; font-size:12.5px; }
    .tpd .detail h6 { font-size:12px; text-transform:uppercase; letter-spacing:.4px; color:var(--muted); margin:8px 0 4px; }
    .tpd .tx { display:grid; grid-template-columns: 1fr auto; gap:4px 12px; padding:8px 10px; border:1px solid var(--line); border-radius:10px; background:#fff; margin-bottom:6px; }
    .tpd .tx .meta { color:var(--muted); font-size:12px; }
    .tpd .ladder { list-style:none; padding:0; margin:0; }
    .tpd .ladder li { display:flex; gap:8px; align-items:flex-start; padding:3px 0; font-size:12.5px; }
    .tpd .ladder li .dot { width:10px; height:10px; border-radius:50%; margin-top:4px; background:var(--grey-soft); border:2px solid #cfd4dc; flex:none; }
    .tpd .ladder li.done .dot { background:var(--green); border-color:var(--green); } .tpd .ladder li.now .dot { background:#fff; border-color:var(--p); box-shadow:0 0 0 3px var(--soft); }
    .tpd .ladder li .who { color:var(--muted); }

    /* progress / attention */
    .tpd .bar { background:var(--grey-soft); border-radius:8px; height:8px; overflow:hidden; }
    .tpd .bar i { display:block; height:100%; background:var(--green); border-radius:8px; }
    .tpd .att { border-left:3px solid var(--amber); padding:6px 10px; margin-bottom:8px; background:#fff; }
    .tpd .att.red { border-color:var(--red); } .tpd .att.blue { border-color:var(--blue); }
    .tpd .att b { display:block; font-size:13px; } .tpd .att span { color:var(--muted); font-size:12px; }
    .tpd .note-red { background:var(--red-soft); color:#7f1d1d; border-radius:10px; padding:8px 12px; font-size:12.5px; }

    /* history */
    .tpd .hist { font-size:12.5px; }
    .tpd .hist .row-h { display:grid; grid-template-columns: 150px 1fr 170px; gap:10px; padding:7px 0; border-bottom:1px dashed var(--line); }
    .tpd .hist .when { color:var(--muted); white-space:nowrap; }
    .tpd .hist .what b { display:block; }
    .tpd .audit td, .tpd .audit th { font-size:12px; vertical-align:top; }
    .tpd .audit pre { margin:0; font-size:11px; white-space:pre-wrap; word-break:break-word; color:var(--muted); max-height:80px; overflow:auto; }

    /* classic */
    .tpd .classic { display:none; }
    .tpd.show-classic .classic { display:block; } .tpd.show-classic .modern { display:none; }

    /* mobile */
    @media (max-width: 991.98px) {
        .tpd .line { grid-template-columns: 44px 1fr; }
        .tpd .line .acts { grid-column:1 / -1; flex-direction:row; flex-wrap:wrap; }
        .tpd .line .acts .btn { flex:1 1 auto; }
        .tpd .line .cell { grid-column:2; }
        .tpd .line .cells { grid-column:1 / -1; display:grid; grid-template-columns:1fr 1fr; gap:6px 10px; }
        .tpd .line .dates, .tpd .line .status { grid-column:1 / -1; }
        .tpd .line .dates .k { display:inline; margin-right:6px; } .tpd .line .dates .v { display:inline; }
        .tpd .hist .row-h { grid-template-columns: 1fr; gap:2px; }
        .tpd .fx .rate-box input { width:90px; }
        .tpd .day-h .rt { display:none; }
    }
    @media (min-width: 992px) { .tpd .line .cells { display:contents; } }

    /* legacy bits kept */
    .bg-purple { background-color:#6d28d9 !important; color:#fff !important; } .bg-orange { background-color:#ea580c !important; color:#fff !important; } .bg-teal { background-color:#0d9488 !important; color:#fff !important; }
    .tpd-status-square { display:inline-block; width:18px; height:18px; border-radius:4px; margin:1px; cursor:default; }
    .tpd-status-squares { display:flex; flex-wrap:wrap; gap:3px; justify-content:center; }
    .tpd-legend { display:flex; flex-wrap:wrap; gap:10px 16px; font-size:.74rem; color:#475569; }
    .tpd-legend-top { margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid #e9ecef; }
    .tpd-legend-item { display:flex; align-items:center; gap:5px; }
    .tpd-upcoming-row { cursor:pointer; transition:background-color .12s ease; } .tpd-upcoming-row:hover { background:#f8fafc; }
    .tpd-cat-card { border-radius:10px; border:1px solid #e9ecef; padding:12px 16px; background:#fff; }
    .category-icon-circle { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
    .color-primary { background:#dbeafe; color:#1d4ed8; } .color-success { background:#d1fae5; color:#065f46; } .color-info { background:#cffafe; color:#0e7490; } .color-purple { background:#ede9fe; color:#6d28d9; } .color-warning { background:#fef3c7; color:#b45309; }
    .tpd-progress-wrap { background:#f1f5f9; border-radius:8px; height:6px; } .tpd-progress-bar { border-radius:8px; height:6px; }
    #no-tour-msg { display:none; } #tour-content { display:none; } #upcoming-tours-panel { display:block; }
    @media print { .no-print, .page-topbar, .app-menu, .tpd-head .btn, .filters, .line .acts { display:none !important; } .main-content { margin-left:0 !important; } }
</style>

<body class="bg-light">
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid tpd" id="tpd">

                <!-- Page title + search -->
                <div class="row align-items-center mb-3">
                    <div class="col-md-5">
                        <h4 class="mb-0 fw-bold text-p">Tour Payment Dashboard</h4>
                        <div class="muted small">Tour payments and accounting, day by day — request, approval, voucher, bank initiation, Paid, supplier advice.</div>
                    </div>
                    <div class="col-md-7">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <input type="text" id="manual_id_input" class="form-control" placeholder="Tour ID (e.g. AH 2218)" value="" style="max-width:300px;">
                            <button class="btn btn-p px-4" id="search_btn" onclick="searchTour()"><i class="ri-search-line me-1"></i> Open</button>
                        </div>
                    </div>
                </div>

                <?php if (!$TPD_READY): ?>
                <div class="alert alert-warning d-flex align-items-center gap-2 py-2">
                    <i class="ri-tools-line fs-5"></i>
                    <div>The payment workflow's tables are not set up on this installation yet. <?php if (!empty($TPD['admin'])): ?><button class="btn btn-sm btn-warning ms-2" onclick="tpdSetup()">Set up now</button><?php else: ?>Ask the System Administrator to run the setup (Tour Payment Dashboard → Set up).<?php endif; ?></div>
                </div>
                <?php endif; ?>

                <!-- tf-offers-r3: hotel payments waiting for the System Administrator's rate review (holders only) -->
                <div id="rate_review_queue" class="card border-0 shadow-sm mb-3" style="display:none;"></div>

                <!-- Upcoming tours (before any search) -->
                <div id="upcoming-tours-panel">
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
                                <div>
                                    <h6 class="mb-1 fw-bold">Upcoming tours</h6>
                                    <div class="muted small">Tours starting in the date range. Open one to see its payments day by day.</div>
                                </div>
                                <div class="d-flex align-items-end gap-2">
                                    <div><label class="form-label small mb-1">From</label><input type="date" id="upcoming_from" class="form-control form-control-sm"></div>
                                    <div><label class="form-label small mb-1">To</label><input type="date" id="upcoming_to" class="form-control form-control-sm"></div>
                                    <button class="btn btn-sm btn-outline-p" id="upcoming_refresh_btn" onclick="loadUpcomingTours()"><i class="ri-refresh-line"></i> Refresh</button>
                                </div>
                            </div>
                            <div class="tpd-legend tpd-legend-top">
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#22c55e;"></span>Fully Paid</div>
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#f59e0b;"></span>Pending Payment</div>
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#3b82f6;"></span>Advance Paid</div>
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#14b8a6;"></span>Payment on Process</div>
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#92400e;"></span>Waiting Rate Confirmation</div>
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#f97316;"></span>Not Paid</div>
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#ef4444;"></span>Payment Not Requested</div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle" id="upcoming_tours_table">
                                    <thead class="table-light"><tr><th>Tour ID</th><th>Tour</th><th>Start</th><th>End</th><th class="text-center">Pax</th><th>Flights</th><th class="text-center">Hotels</th><th class="text-center">Activities</th><th class="text-center">Driver advance</th></tr></thead>
                                    <tbody id="upcoming_tours_body"></tbody>
                                </table>
                            </div>
                            <div id="upcoming_empty_msg" class="text-center muted py-4" style="display:none;"><i class="ri-calendar-line fs-3 d-block mb-1"></i>No tours starting in the selected date range.</div>
                            <div id="upcoming_loading_msg" class="text-center muted py-4"><i class="ri-loader-4-line fs-3 d-block mb-1"></i>Loading upcoming tours...</div>
                        </div>
                    </div>
                </div>

                <div id="no-tour-msg" class="alert alert-warning"><i class="ri-information-line me-1"></i> No tour found for that Tour ID.</div>

                <!-- ==================== THE TOUR ==================== -->
                <div id="tour-content">
                    <div class="mb-2 d-flex gap-2 align-items-center no-print">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="backToUpcomingSummary()"><i class="ri-arrow-left-line me-1"></i> Back to the list</button>
                        <span class="muted small ms-auto">View:</span>
                        <div class="btn-group btn-group-sm"><button class="btn btn-outline-p active" id="view_modern" onclick="tpdView('modern')">Payment desk</button><button class="btn btn-outline-p" id="view_classic" onclick="tpdView('classic')">Classic table</button></div>
                    </div>

                    <div class="modern">
                        <!-- header -->
                        <div class="tpd-head mb-3" id="tpd_head"></div>
                        <!-- KPIs -->
                        <div class="row g-2 mb-3" id="tpd_kpis"></div>
                        <!-- exchange rate -->
                        <div class="fx mb-3" id="tpd_fx"></div>
                        <!-- schedule -->
                        <div class="card mb-3" id="tpd_sched_card">
                            <div class="sched-h d-flex flex-wrap align-items-start justify-content-between gap-2">
                                <div><h5>Payment schedule</h5><div class="muted small">Arranged automatically in confirmed itinerary order</div></div>
                                <div class="d-flex gap-2 flex-wrap no-print">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="tpdToggleDays(false)">Collapse days</button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="tpdToggleDays(true)">Expand days</button>
                                    <?php if (!empty($TPD['requester'])): ?><button class="btn btn-sm btn-p" onclick="openExpenseModal()"><i class="ri-add-line me-1"></i>Add Expense</button><?php endif; ?>
                                </div>
                            </div>
                            <div class="filters" id="tpd_filters"></div>
                            <div id="tpd_days"></div>
                            <div class="px-3 py-2 muted small d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span id="tpd_showing"></span>
                                <span id="tpd_cost_tools" style="display:none;" class="no-print">Hotel rates from the Hotel Rates panel: <span class="badge bg-success">Ad hoc</span> <span class="badge bg-info text-dark">Contracted</span> <span class="badge bg-danger">Quoted — check</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 ms-1" onclick="recalcHotelCosts()"><i class="ri-refresh-line"></i> Recalculate</button></span>
                            </div>
                        </div>
                        <!-- progress + attention -->
                        <div class="row g-3 mb-3">
                            <div class="col-lg-4"><div class="card h-100"><div class="card-body" id="tpd_overall"></div></div></div>
                            <div class="col-lg-4"><div class="card h-100"><div class="card-body" id="tpd_cats"></div></div></div>
                            <div class="col-lg-4"><div class="card h-100"><div class="card-body" id="tpd_attention"></div></div></div>
                        </div>
                        <!-- history + audit -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <h6 class="fw-bold mb-0"><i class="ri-history-line me-1"></i>Complete payment history</h6>
                                    <div class="btn-group btn-group-sm no-print"><button class="btn btn-outline-secondary active" id="hist_tab_h" onclick="tpdHistTab('h')">Timeline</button><button class="btn btn-outline-secondary" id="hist_tab_a" onclick="tpdHistTab('a')">Audit record</button></div>
                                </div>
                                <div id="tpd_history" class="hist"></div>
                                <div id="tpd_audit" class="table-responsive" style="display:none;"></div>
                            </div>
                        </div>
                        <!-- tickets (usage notes) -->
                        <div class="card mb-3">
                            <div class="card-body p-0">
                                <div class="px-3 pt-3 pb-2"><h6 class="fw-semibold mb-0"><i class="ri-ticket-2-line me-1"></i>Tickets (stock requests)</h6></div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="tickets_table">
                                        <thead class="table-light"><tr><th>Usage note</th><th>Date</th><th>Items</th><th class="text-center">Qty</th><th>Status</th><th>Approved by</th></tr></thead>
                                        <tbody id="tickets_table_body"></tbody>
                                    </table>
                                </div>
                                <div id="tickets_empty_msg" class="text-center muted py-4" style="display:none;"><i class="ri-ticket-2-line fs-3 d-block mb-1"></i>No tickets requested for this tour.</div>
                            </div>
                        </div>
                    </div>

                    <!-- the earlier table, unchanged, for comparison -->
                    <div class="classic">
                        <div class="card mb-3"><div class="card-body p-0"><div class="table-responsive">
                            <table class="table table-hover mb-0" id="payment_table">
                                <thead class="table-light"><tr><th>Day</th><th>Category</th><th>Supplier</th><th>Description</th><th class="text-end">Total Amount</th><th class="text-end">Advance Paid</th><th class="text-end">Balance</th><th>Request Date</th><th>Due Date</th><th>Status</th><th>Action</th></tr></thead>
                                <tbody id="payment_table_body"></tbody>
                            </table>
                        </div><div class="px-3 py-2 text-muted small" id="table_showing"></div></div></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div><!-- /layout-wrapper -->

<!-- ==================== PAYMENT REQUEST MODAL ==================== -->
<div class="modal fade" id="paymentRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Payment Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="bg-light rounded p-3 mb-3 small">
                    <div class="d-flex justify-content-between"><span>Supplier</span><span class="fw-semibold" id="pr_supplier_name">-</span></div>
                    <div class="d-flex justify-content-between"><span>Category</span><span class="fw-semibold" id="pr_category_lbl">-</span></div>
                    <div class="d-flex justify-content-between"><span>Total Amount</span><span class="fw-semibold" id="pr_total_amt">-</span></div>
                    <!-- Hotel cost handoff: what the Hotel Rates panel says we pay (ad hoc → contract → quoted) -->
                    <div class="d-flex justify-content-between" id="pr_cost_row" style="display:none;">
                        <span>Hotel cost (payable)</span><span><span class="fw-semibold" id="pr_cost_amt">-</span> <span class="badge" id="pr_cost_badge"></span></span>
                    </div>
                    <div class="text-muted" id="pr_cost_detail" style="display:none; font-size:.75rem;"></div>
                    <!-- tf-offers-r3: a hotel payment held for the rate review — who prepared it, when, for how much -->
                    <div id="pr_release_info" class="mt-2 border rounded p-2 bg-white" style="display:none; font-size:.8rem;"></div>
                    <!-- tf-crew-master-r1: a driver's or guide's payment — who is paid, from the Driver & Guide Master -->
                    <div id="pr_payee_box" class="mt-2 border rounded p-2 bg-white" style="display:none; font-size:.8rem;"></div>
                    <!-- tf-offers-r1: published rate · tactical / early bird · rate used for the hotel payment · saving -->
                    <div id="pr_offer_box" class="mt-2" style="display:none;"></div>
                    <div class="d-flex justify-content-between align-items-center" id="pr_rate_row" style="display:none;">
                        <span>Today's USD Rate</span>
                        <span>
                            <span class="fw-semibold" id="pr_usd_rate">-</span>
                            <a href="<?= base_url('master_files/daily_usd_rate') ?>" target="_blank"
                               class="ms-1 text-decoration-none" title="Edit USD rate">
                                <i class="ri-edit-line"></i>
                            </a>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between" id="pr_lkr_row" style="display:none;">
                        <span>Est. LKR Amount</span><span class="fw-semibold text-primary" id="pr_lkr_amt">-</span>
                    </div>
                </div>
                <div class="alert alert-warning small py-2 px-3 mb-3" id="pr_rate_missing_alert" style="display:none;">
                    <i class="ri-error-warning-line me-1"></i> Today's USD rate hasn't been entered yet.
                    <a href="<?= base_url('master_files/daily_usd_rate') ?>" target="_blank" class="alert-link">Set it now</a>.
                </div>
                <input type="hidden" id="pr_row_idx">
                <div class="mb-3">
                    <label class="form-label fw-semibold small" id="pr_amount_label">Request Amount <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="pr_amount" placeholder="Enter amount">
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-6"><label class="form-label fw-semibold small">Supplier invoice number</label><input type="text" class="form-control form-control-sm" id="pr_invoice_no" placeholder="optional"></div>
                    <div class="col-6"><label class="form-label fw-semibold small">Hotel booking reference</label><input type="text" class="form-control form-control-sm" id="pr_booking_ref" placeholder="optional"></div>
                    <div class="col-6"><label class="form-label fw-semibold small">Due date</label><input type="date" class="form-control form-control-sm" id="pr_due_date"></div>
                    <div class="col-6"><label class="form-label fw-semibold small">Note</label><input type="text" class="form-control form-control-sm" id="pr_note" placeholder="optional"></div>
                </div>
                <div class="alert alert-info small py-2 px-3 mt-2 mb-0" id="pr_balance_note" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-secondary" id="pr_return_btn" style="display:none;" onclick="returnHeldPayment()">
                    <i class="ri-arrow-go-back-line me-1"></i> Return to staff
                </button>
                <button type="button" class="btn btn-warning" id="pr_submit_btn" onclick="submitPaymentRequest()">
                    <i class="ri-send-plane-line me-1"></i> Send Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== tf-paydesk-r1 MODALS ==================== -->
<!-- Approve (management) -->
<div class="modal fade tpd" id="approveModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-shield-check-line me-1 text-p"></i>Approve payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="bg-light rounded p-3 mb-3 small" id="ap_summary"></div>
        <div class="small mb-3" id="ap_rate"></div>
        <div id="ap_bank_warn"></div>
        <div class="mb-3" id="ap_bank_wrap">
            <label class="form-label fw-semibold small">Avenir paying account <span class="text-danger">*</span></label>
            <select class="form-select" id="ap_bank"></select>
            <div class="form-text" id="ap_bank_note"></div>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold small">Internal payment note (optional)</label><input type="text" class="form-control" id="ap_note" maxlength="300" placeholder="e.g. advance for the first two nights"></div>
        <!-- tf-paydesk-r3: Pay Individually Now straight after the approval (management with the batch privilege) -->
        <div class="border rounded p-2 mb-2" id="ap_paynow_wrap" style="display:none; background:#fffaf0;">
            <label class="d-flex gap-2 align-items-start small fw-semibold"><input type="checkbox" id="ap_paynow" class="mt-1" onchange="document.getElementById('ap_paynow_fields').style.display = this.checked ? '' : 'none'"> <span><i class="ri-flashlight-line me-1"></i>Pay individually now — do not wait for the supplier's bulk batch</span></label>
            <div id="ap_paynow_fields" style="display:none;" class="mt-2">
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Reason <span class="text-danger">*</span></label><select class="form-select form-select-sm" id="ap_bypass_reason"><option value="">— choose —</option><option value="driver_advance">Driver advance</option><option value="driver_settlement">Driver settlement</option><option value="urgent_cash">Urgent cash payment</option><option value="pay_at_gate">Pay-at-gate item</option><option value="emergency">Emergency supplier payment</option><option value="other">Other authorised payment</option></select></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Suggested paying account</label><select class="form-select form-select-sm" id="ap_paynow_bank"></select></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Note (required for "Other")</label><input type="text" class="form-control form-control-sm" id="ap_bypass_note" maxlength="500"></div>
                </div>
                <div class="muted" style="font-size:11.5px; margin-top:4px;">A single-item batch is created at once; its Payment Voucher No. goes to the accountant's queue. The bypass and its reason are kept in the audit history.</div>
            </div>
        </div>
        <div class="muted small" id="ap_after">Your approval creates the payment instruction and voucher and places it in Bank Payment Check for the accountant. It does <b>not</b> mark the payment as paid.</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-p" id="ap_submit" onclick="submitApprove()"><i class="ri-check-double-line me-1"></i>Approve for payment</button></div>
</div></div></div>

<!-- tf-paydesk-r2: Supplier payment batch (management) — the supplier's approved payments waiting in Supplier Payables → one voucher -->
<div class="modal fade tpd" id="batchModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-stack-line me-1 text-p"></i>Create the supplier's payment batch</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="bg-light rounded p-3 mb-3 small" id="bt_summary"></div>
        <div id="bt_bank_warn" class="mb-3"></div>
        <div class="table-responsive"><table class="table table-sm align-middle" id="bt_table" style="font-size:12.5px;"><thead class="table-light"><tr><th style="width:28px;"><input type="checkbox" id="bt_all" onchange="btToggleAll(this.checked)"></th><th>Tour</th><th>Guest</th><th>Service</th><th>Invoice</th><th class="text-end">USD</th><th class="text-end">Approval rate</th><th class="text-end">Provisional LKR</th><th>Approved</th></tr></thead><tbody id="bt_rows"></tbody>
            <tfoot><tr class="fw-semibold"><td colspan="5" class="text-end">Selected</td><td class="text-end num" id="bt_usd">0.00</td><td></td><td class="text-end num" id="bt_lkr">0.00</td><td id="bt_count"></td></tr></tfoot></table></div>
        <div class="row g-2 mt-1">
            <div class="col-md-6"><label class="form-label fw-semibold small">Avenir bank account to pay from <span class="text-danger">*</span></label><select class="form-select" id="bt_bank"></select><div class="form-text" id="bt_bank_note"></div></div>
            <div class="col-md-6"><label class="form-label fw-semibold small">Note for the accountant (optional)</label><input type="text" class="form-control" id="bt_note" maxlength="500"></div>
        </div>
        <div class="muted small mt-2">One batch = one supplier = one payment voucher. The approval figures of each payment stay as they are; the accountant's bank payment page values the batch at the rate of the actual payment date.</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-p" id="bt_submit" onclick="submitBatch()"><i class="ri-file-text-line me-1"></i>Create batch &amp; issue voucher</button></div>
</div></div></div>

<!-- tf-paydesk-r3: Pay Individually Now for an approved line -->
<div class="modal fade tpd" id="payNowModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-flashlight-line me-1 text-p"></i>Pay Individually Now</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="bg-light rounded p-3 mb-3 small" id="pn_summary"></div>
        <div id="pn_bank_warn" class="mb-3"></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Why must this payment not wait for the supplier's bulk batch? <span class="text-danger">*</span></label>
            <select class="form-select" id="pn_reason"><option value="">— choose —</option><option value="driver_advance">Driver advance</option><option value="driver_settlement">Driver settlement</option><option value="urgent_cash">Urgent cash payment</option><option value="pay_at_gate">Pay-at-gate item</option><option value="emergency">Emergency supplier payment</option><option value="other">Other authorised payment</option></select></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Note (required for "Other")</label><input type="text" class="form-control" id="pn_note" maxlength="500"></div>
        <div class="mb-2"><label class="form-label fw-semibold small">Suggested Avenir paying account <span class="text-danger">*</span></label><select class="form-select" id="pn_bank"></select><div class="form-text">The accountant confirms the actual paying account at initiation; the bank approver confirms it at Paid.</div></div>
        <div class="muted small">A single-item batch is created at once and its Payment Voucher No. goes to the accountant's queue; bank initiation and the bank approver's Paid still apply. The bypass and its reason are kept in the audit history.</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-p" id="pn_submit" onclick="submitPayNow()"><i class="ri-flashlight-line me-1"></i>Pay individually now</button></div>
</div></div></div>

<!-- Record bank initiation (accountant) -->
<div class="modal fade tpd" id="initModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-bank-line me-1 text-p"></i>Record bank initiation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="bg-light rounded p-3 mb-3 small" id="in_summary"></div>
        <div id="in_supplier_bank" class="mb-3"></div>
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label fw-semibold small">Avenir bank account <span class="text-danger">*</span></label><select class="form-select" id="in_bank"></select><div class="form-text" id="in_bank_note"></div></div>
            <div class="col-md-6"><label class="form-label fw-semibold small">Initiation date &amp; time <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" id="in_at"></div>
            <div class="col-md-4"><label class="form-label fw-semibold small">Amount initiated <span class="text-danger">*</span></label><input type="text" class="form-control num" id="in_amount"></div>
            <div class="col-md-2"><label class="form-label fw-semibold small">Currency</label><input type="text" class="form-control" id="in_currency" value="LKR" readonly></div>
            <div class="col-md-3"><label class="form-label fw-semibold small">Bank charges</label><input type="text" class="form-control num" id="in_charges" placeholder="0.00"></div>
            <div class="col-md-3"><label class="form-label fw-semibold small">Batch reference</label><input type="text" class="form-control" id="in_batch" placeholder="one batch, several suppliers"></div>
            <div class="col-md-6"><label class="form-label fw-semibold small">Bank transaction reference</label><input type="text" class="form-control" id="in_ref"></div>
            <div class="col-md-6"><label class="form-label fw-semibold small">Payment screenshot / file (PDF, PNG, JPG)</label><input type="file" class="form-control" id="in_file" accept=".pdf,.png,.jpg,.jpeg"></div>
            <div class="col-12"><label class="form-label fw-semibold small">Accountant's notes</label><input type="text" class="form-control" id="in_note" maxlength="500"></div>
        </div>
        <div class="muted small mt-2">This records that the transfer was initiated at the bank. It does not mark the payment as paid — only the bank approver's <b>Paid</b> does that.</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-p" id="in_submit" onclick="submitInitiate()"><i class="ri-send-plane-2-line me-1"></i>Bank payment initiated</button></div>
</div></div></div>

<!-- Paid (bank approver) -->
<div class="modal fade tpd" id="paidModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-checkbox-circle-line me-1 text-success"></i>Paid — final bank approval</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="bg-light rounded p-3 mb-3 small" id="pd_summary"></div>
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label fw-semibold small">Actual payment date <span class="text-danger">*</span></label><input type="date" class="form-control" id="pd_date"></div>
            <div class="col-md-6"><label class="form-label fw-semibold small">Final bank reference <span class="text-danger">*</span></label><input type="text" class="form-control" id="pd_ref"></div>
            <div class="col-12"><label class="form-label fw-semibold small">Note (optional)</label><input type="text" class="form-control" id="pd_note" maxlength="500"></div>
        </div>
        <div class="note-red mt-3">Check the transaction in the bank — account, beneficiary and amount — before clicking Paid. Paid locks the date, amount, currency, exchange rate, LKR value, paying account, bank reference, who initiated and who approved. It cannot be undone from here.</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-success" id="pd_submit" onclick="submitPaid()"><i class="ri-lock-line me-1"></i>Paid</button></div>
</div></div></div>

<!-- Remittance advice -->
<div class="modal fade tpd" id="remitModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-mail-send-line me-1 text-p"></i>Remittance advice to the supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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

<!-- Add expense -->
<div class="modal fade tpd" id="expenseModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-add-circle-line me-1 text-p"></i>Add expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label fw-semibold small">Service date</label><input type="date" class="form-control" id="ex_date"></div>
            <div class="col-md-6"><label class="form-label fw-semibold small">Category</label><select class="form-select" id="ex_cat"><option>Other</option><option>Meal</option><option>Entrance</option><option>Activity</option><option>Transport</option><option>Guide</option></select></div>
            <div class="col-12"><label class="form-label fw-semibold small">Supplier (from the master) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="ex_sup_q" placeholder="type to search the supplier master" autocomplete="off"><input type="hidden" id="ex_sup_acc">
                <div id="ex_sup_list" class="list-group position-absolute shadow" style="z-index:1080; max-height:220px; overflow:auto; display:none;"></div>
                <div class="form-text" id="ex_sup_note">A supplier from the master is needed for the payment to enter the chain.</div></div>
            <div class="col-12"><label class="form-label fw-semibold small">Description <span class="text-danger">*</span></label><input type="text" class="form-control" id="ex_desc" maxlength="255"></div>
            <div class="col-md-4"><label class="form-label fw-semibold small">Currency</label><select class="form-select" id="ex_cur"><option>LKR</option><option>USD</option></select></div>
            <div class="col-md-8"><label class="form-label fw-semibold small">Amount <span class="text-danger">*</span></label><input type="text" class="form-control num" id="ex_amount"></div>
            <div class="col-md-6"><label class="form-label fw-semibold small">Supplier invoice number</label><input type="text" class="form-control" id="ex_inv"></div>
            <div class="col-md-6"><label class="form-label fw-semibold small">Due date</label><input type="date" class="form-control" id="ex_due"></div>
            <div class="col-12"><label class="form-label fw-semibold small">Note</label><input type="text" class="form-control" id="ex_note" maxlength="500"></div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-p" onclick="submitExpense()"><i class="ri-save-line me-1"></i>Add</button></div>
</div></div></div>

<!-- Invoice / booking reference / document -->
<div class="modal fade tpd" id="docModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-attachment-2 me-1 text-p"></i>Invoice &amp; documents</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="bg-light rounded p-3 mb-3 small" id="dc_summary"></div>
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label fw-semibold small">Supplier invoice number</label><input type="text" class="form-control" id="dc_inv"></div>
            <div class="col-md-6"><label class="form-label fw-semibold small">Hotel booking reference</label><input type="text" class="form-control" id="dc_ref"></div>
            <div class="col-md-5"><label class="form-label fw-semibold small">Attach a document</label><select class="form-select" id="dc_kind"><option value="invoice">Supplier invoice</option><option value="booking">Booking confirmation</option><option value="other">Other</option></select></div>
            <div class="col-md-7"><label class="form-label fw-semibold small">&nbsp;</label><input type="file" class="form-control" id="dc_file" accept=".pdf,.png,.jpg,.jpeg"></div>
        </div>
        <div id="dc_list" class="mt-3 small"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-p" onclick="submitDoc()"><i class="ri-save-line me-1"></i>Save</button></div>
</div></div></div>

<!-- Supplier bank details (read / verify — tf-stage5: verification by Finance & Approvals Management Approver, never by the person who entered them) -->
<div class="modal fade tpd" id="supBankModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="ri-bank-card-line me-1 text-p"></i>Supplier bank details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="sb_body"></div>
    <div class="modal-footer"><a class="btn btn-light" id="sb_master" target="_blank" href="<?= base_url('master_files/supplier_bank') ?>">Open Supplier Bank Details</a><button type="button" class="btn btn-p" id="sb_confirm" style="display:none;" onclick="confirmSupplierBank()"><i class="ri-shield-check-line me-1"></i>Verify these details</button></div>
</div></div></div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>
<script>
const voucherPrintBaseUrl = '<?= base_url('accounts/voucher_print/') ?>';
// tf-offers-r1: Published rate = quotation basis and the payment by default; a Tactical / Early Bird that fits the stay is
// flagged here to a sign-in holding "Tactical / Early Bird rate selection" (only they get row.cost_offer), who applies it
// or keeps the published rate
const CAN_CHOOSE_OFFER = <?= !empty($can_choose_offer) ? 'true' : 'false' ?>;
const OFFER_CHOOSE_URL = '<?= base_url('master_files/offer_selection/choose') ?>';
// tf-offers-r3: the review before a hotel payment is logged (who, when); the terms of a special rate, for holders only
const OFFER_REVIEWED_URL = '<?= base_url('master_files/offer_selection/reviewed') ?>';
const OFFER_TERMS_URL = '<?= base_url('master_files/offer_selection/offer') ?>/';
// tf-offers-r3: hotel payments held for the rate review — the list, release to Payment approval, return to staff
const OFFER_HOLDS_URL = '<?= base_url('master_files/offer_selection/holds') ?>';
const OFFER_RELEASE_URL = '<?= base_url('master_files/offer_selection/release') ?>';
const OFFER_RETURN_URL = '<?= base_url('master_files/offer_selection/return') ?>';
// tf-paydesk-r7e: this page's security token, sent with each POST below (the desk's postJson / postJsonBody already send it)
const TF_CSRF_NAME = '<?= csrf_token() ?>', TF_CSRF_HASH = '<?= csrf_hash() ?>';
const TF_CSRF_JSON = { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': TF_CSRF_HASH };
const OFFER_KIND = { tactical: 'Tactical rate', early_bird: 'Early Bird' };

// ==========================================
//  tf-paydesk-r1 — state, loading, rendering
// ==========================================
const TPD_URL = '<?= base_url('tour/tour_payment_dashboard/') ?>';
const TPD_PERMS = <?= json_encode($TPD) ?>;
const TPD_READY = <?= $TPD_READY ? 'true' : 'false' ?>;
const TPD_USER = <?= json_encode((string) session()->get('username')) ?>;
let _desk = null;            // the desk's answer (tour, rate, days, lines, history, …)
let _tourData = null;        // the earlier dashboard's data (payment_rows, tour_info, …) — the request / special-rate code reads it
let _filter = 'all';
let _closedDays = {};
let _openLines = {};
let _histTab = 'h';
let _pending = {};           // the line / transaction a modal is working on

const STATUS_LABEL = {not_requested:'Payment Not Requested', requested:'Payment Requested', awaiting:'Awaiting Management Approval', approved:'Approved for Payment', voucher_issued:'Payment Voucher Issued',
    bank_initiated:'Bank Payment Initiated — Awaiting Bank Approval', paid:'Fully Paid', notify_pending:'Supplier Notification Pending', notified:'Supplier Notified', partly_paid:'Partly Paid', on_hold:'On Hold', check:'Requires Checking', unverified:'Unverified — review needed', rejected:'Rejected', reversed:'Reversed', cancelled:'Cancelled'};
const STATUS_COLOR = {not_requested:'grey', requested:'blue', awaiting:'blue', approved:'indigo', voucher_issued:'violet', bank_initiated:'teal', paid:'green', notify_pending:'olive', notified:'green', partly_paid:'purple', on_hold:'yellow', check:'yellow', unverified:'yellow', rejected:'red', reversed:'red', cancelled:'grey'};
const STAGES = ['not_requested','awaiting','approved','voucher_issued','bank_initiated','paid','notified'];
const GROUP_ICON = {hotel:'ri-hotel-bed-line', meal:'ri-restaurant-line', entrance:'ri-coupon-3-line', activity:'ri-run-line', transport:'ri-car-line', driver_advance:'ri-steering-2-line', driver_settlement:'ri-steering-2-line', guide:'ri-user-voice-line', other:'ri-price-tag-3-line'};

function money(n, cur) { return (cur ? cur + ' ' : '') + fmtNum(n); }
function money2(n) { return parseFloat(n || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
function dmy(d) { return d ? fmtDate(d) : '—'; }
function dmyt(d) { if (!d) return '—'; const s = String(d).replace(' ', 'T'); const dt = new Date(s); if (isNaN(dt)) return esc(String(d)); return dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}) + ' ' + dt.toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit'}); }
function pill(status, extra) { const c = STATUS_COLOR[status] || 'grey'; return `<span class="pill ${c} ${extra || ''}">${esc(STATUS_LABEL[status] || status)}</span>`; }
async function postJson(path, body) {
    const fd = body instanceof FormData ? body : Object.entries(body || {}).reduce((f, [k, v]) => { if (Array.isArray(v)) v.forEach(x => f.append(k + '[]', x)); else if (v !== undefined && v !== null) f.append(k, v); return f; }, new FormData());
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    let r, j;
    try { r = await fetch(TPD_URL + path, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }); j = await r.json(); }
    catch (e) { return { status: 0, message: 'Network error. Please try again.' }; }
    return j;
}
async function postJsonBody(path, obj) {   // for text that carries HTML (the strip-tags filter checks form fields only)
    let r, j;
    try { r = await fetch(TPD_URL + path, { method: 'POST', body: JSON.stringify(obj || {}), headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' } }); j = await r.json(); }
    catch (e) { return { status: 0, message: 'Network error. Please try again.' }; }
    return j;
}
// the chain keys a request by tour + supplier + type: one request covers every unrequested activity of the supplier
function tpdCeiling(row) {
    if (!row || row.category !== 'Activity') return row ? row.total : 0;
    return _tourData.payment_rows.filter(r => r.category === 'Activity' && r.supplier_acc === row.supplier_acc && r.status === 'Payment Not Requested').reduce((s, r) => s + (parseFloat(r.total) || 0), 0) || row.total;
}
function toast(ok, msg) { Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? 'Done' : 'Not done', text: msg || '', timer: ok ? 2600 : undefined, showConfirmButton: !ok }); }

// ---- loading ----
function searchTour() {
    const manualId = document.getElementById('manual_id_input').value.trim();
    if (!manualId) { Swal.fire('Tour ID', 'Enter the Tour ID (e.g. AH 2218).', 'warning'); return; }
    document.getElementById('tour-content').style.display = 'none';
    document.getElementById('no-tour-msg').style.display = 'none';
    fetch(`${TPD_URL}desk?manual_id=${encodeURIComponent(manualId)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(res => {
            if (res.status === 200) {
                _desk = res.data;
                _tourData = Object.assign({}, _desk.legacy, { tickets: _desk.tickets });
                _todayUsdRate = _desk.rate.rate ? parseFloat(_desk.rate.rate) : null; _usdRateLoaded = true;
                document.getElementById('upcoming-tours-panel').style.display = 'none';
                document.getElementById('tour-content').style.display = 'block';
                renderDesk();
                renderClassic();
                history.replaceState(null, '', '?manual_id=' + encodeURIComponent(_desk.tour.manual_id));
            } else {
                document.getElementById('no-tour-msg').style.display = 'block';
                document.getElementById('no-tour-msg').textContent = res.message || 'No tour found for that Tour ID.';
                document.getElementById('upcoming-tours-panel').style.display = 'block';
            }
        })
        .catch(() => Swal.fire('Error', 'Failed to load the tour.', 'error'));
}
document.getElementById('manual_id_input').addEventListener('keydown', e => { if (e.key === 'Enter') searchTour(); });
function reload() { if (_desk) { document.getElementById('manual_id_input').value = _desk.tour.manual_id; searchTour(); } }

// Today's USD rate — the request / special-rate code reads _todayUsdRate (as before); the panel is rendered from the desk's answer
let _todayUsdRate = null, _usdRateLoaded = false;
function loadUsdRate(force) {
    if (_usdRateLoaded && !force) return;
    fetch(TPD_URL + 'get_usd_rate', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).then(res => {
        if (res.status === 200) _todayUsdRate = res.rate ? parseFloat(res.rate) : null;
        _usdRateLoaded = true;
        if (_desk) { _desk.rate.rate = _todayUsdRate; renderFx(); }
        refreshOpenPaymentRequestModalRate();
    }).catch(() => { _usdRateLoaded = true; });
}
function refreshOpenPaymentRequestModalRate() {
    const modalEl = document.getElementById('paymentRequestModal');
    if (!modalEl.classList.contains('show')) return;
    const rowIdx = document.getElementById('pr_row_idx').value;
    if (rowIdx === '' || !_tourData) return;
    const row = _tourData.payment_rows[rowIdx];
    if (row && row.category === 'Hotel') refreshPaymentRequestRateUi(true);
}
document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible' && _desk) loadUsdRate(true); });
function updateTodayRateBanner() { if (_desk) renderFx(); }

document.addEventListener('DOMContentLoaded', function () {
    initUpcomingDateDefaults();
    loadUpcomingTours();
    loadRateReviewQueue();   // tf-offers-r3
    const _mid = new URLSearchParams(window.location.search).get('manual_id');
    if (_mid) { document.getElementById('manual_id_input').value = _mid; searchTour(); }
    const m = document.getElementById('paymentRequestModal');
    if (m) m.addEventListener('hidden.bs.modal', _endReleaseMode);
    try { const v = localStorage.getItem('tpd_view'); if (v === 'classic') tpdView('classic'); } catch (e) {}
});
document.getElementById('upcoming_from').addEventListener('change', loadUpcomingTours);
document.getElementById('upcoming_to').addEventListener('change', loadUpcomingTours);

function tpdView(v) {
    document.getElementById('tpd').classList.toggle('show-classic', v === 'classic');
    document.getElementById('view_modern').classList.toggle('active', v !== 'classic');
    document.getElementById('view_classic').classList.toggle('active', v === 'classic');
    try { localStorage.setItem('tpd_view', v); } catch (e) {}
}
async function tpdSetup() {
    const j = await postJson('setup', {});
    if (j.status === 200) { await Swal.fire({ icon: 'success', title: 'Set up', html: '<div class="text-start small">' + (j.notes || []).map(esc).join('<br>') + '</div>', width: 700 }); location.reload(); }
    else toast(false, j.message);
}

// ---- rendering ----
function renderDesk() {
    const d = _desk;
    renderHead(d); renderKpis(d); renderFx(); renderFilters(d); renderDays(d); renderOverall(d); renderCats(d); renderAttention(d); renderHistory(d); renderAudit(d); renderTickets(d.tickets || []);
    document.getElementById('tpd_cost_tools').style.display = d.lines.some(l => l.source_type === 'hotel') ? '' : 'none';
    if (!d.ready) document.getElementById('tpd_days').insertAdjacentHTML('afterbegin', '<div class="alert alert-warning m-3">The workflow tables are not set up: the chain\'s own steps still work, but initiations, Paid locks, advices and the audit record cannot be written until the System Administrator runs the setup.</div>');
}
function renderHead(d) {
    const t = d.tour;
    const chips = [
        ['ri-calendar-2-line', `${dmy(t.start_date)} – ${dmy(t.end_date)}`, t.nights !== null ? `${t.nights} night${t.nights === 1 ? '' : 's'}` : ''],
        ['ri-group-line', `${t.pax} pax`, ''], ['ri-building-4-line', t.agency || '—', 'agency'],
        ['ri-steering-2-line', (t.drivers || []).join(', ') || 'Driver to allocate', 'driver'], ['ri-user-voice-line', t.guide || 'No guide', 'guide'],
    ];
    if (t.arrival_flight) chips.push(['ri-flight-land-line', t.arrival_flight, '']);
    if (t.departure_flight) chips.push(['ri-flight-takeoff-line', t.departure_flight, '']);
    document.getElementById('tpd_head').innerHTML = `
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap"><h4>${esc(t.name)}</h4><span class="tid">${esc(t.manual_id)}</span><span class="chip" style="margin:0;">${esc(t.status)}</span></div>
                <div class="mt-2">${chips.map(c => `<span class="chip"><i class="${c[0]}"></i><b>${esc(c[1])}</b>${c[2] ? '<span style="opacity:.8">· ' + esc(c[2]) + '</span>' : ''}</span>`).join('')}</div>
            </div>
            <div class="d-flex gap-2 flex-wrap no-print">
                ${d.accumulates && TPD_PERMS.payables ? `<a class="btn btn-sm btn-ghost" href="${TPD_URL}payables"><i class="ri-stack-line me-1"></i>Supplier Payables</a>` : ''}
                ${TPD_PERMS.admin || TPD_PERMS.bank_master ? `<a class="btn btn-sm btn-ghost" href="${TPD_URL}settings" title="Ledger mapping of the exchange gain / loss accounts"><i class="ri-settings-3-line me-1"></i>Settings</a>` : ''}
                <a class="btn btn-sm btn-ghost" target="_blank" href="${TPD_URL}sheet?manual_id=${encodeURIComponent(t.manual_id)}"><i class="ri-file-list-3-line me-1"></i>Export payment sheet</a>
                <a class="btn btn-sm btn-ghost" target="_blank" href="${TPD_URL}sheet?manual_id=${encodeURIComponent(t.manual_id)}&pdf=1"><i class="ri-file-pdf-line me-1"></i>PDF</a>
                <button class="btn btn-sm btn-light" onclick="document.getElementById('tpd_history').scrollIntoView({behavior:'smooth'})"><i class="ri-history-line me-1"></i>History</button>
            </div>
        </div>`;
}
function renderKpis(d) {
    const T = d.totals, n = d.lines.length, paidN = d.lines.filter(l => l.fully_paid).length;
    const usdOpen = d.lines.filter(l => l.currency === 'USD' && l.usd_remaining > 0).reduce((s, l) => s + l.usd_remaining, 0);
    const k = (cls, lbl, val, sub) => `<div class="col-6 col-xl-3"><div class="kpi ${cls}"><div class="lbl">${lbl}</div><div class="val num">${val}</div><div class="sub">${sub}</div></div></div>`;
    document.getElementById('tpd_kpis').innerHTML =
        k('', 'Estimated tour cost', 'LKR ' + fmtNum(T.estimated), `${n} payment item${n === 1 ? '' : 's'}` + (usdOpen > 0 && !d.rate.rate ? ` · $ ${fmtNum(usdOpen)} not converted (no rate today)` : ''))
        + k('good', 'Total paid', 'LKR ' + fmtNum(T.paid), `${paidN} item${paidN === 1 ? '' : 's'} completed`)
        + k(T.outstanding > 0 ? 'bad' : '', 'Outstanding', 'LKR ' + fmtNum(T.outstanding), T.unverified > 0 ? `LKR ${fmtNum(T.unverified)} unverified — review needed` : (T.estimated > 0 ? `${100 - T.pct}% remaining` : '—'))
        + k(T.due_week > 0 || T.overdue > 0 ? 'warn' : '', T.overdue > 0 ? 'Overdue + due this week' : 'Due this week', 'LKR ' + fmtNum(T.due_week + T.overdue), T.overdue > 0 ? `LKR ${fmtNum(T.overdue)} overdue — needs attention` : (T.due_week > 0 ? 'Needs attention' : 'Nothing due in 7 days'));
}
function renderFx() {
    const r = _desk.rate, el = document.getElementById('tpd_fx');
    el.classList.toggle('missing', !r.rate);
    const canConfirm = !!r.can_confirm;
    const when = r.set ? `Confirmed ${dmyt(r.entered_at)} by <b>${esc(r.entered_by || '')}</b>` : (r.last ? `Not entered today. Last rate ${money2(r.last.rate)} on ${dmy(r.last.date)} by ${esc(r.last.by || '')}` : 'Not entered today');
    el.innerHTML = `
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="ico"><i class="ri-exchange-dollar-line"></i></div>
            <div class="flex-grow-1">
                <h5>Today's USD exchange rate</h5>
                <div class="sub">${dmy(r.date)} · Used only when a new payment is requested · ${when}</div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="rate-box"><small>USD 1 =</small><span class="cur">LKR</span><input type="text" id="fx_rate" value="${r.rate ? money2(r.rate) : ''}" placeholder="0.00" ${canConfirm ? '' : 'readonly'} inputmode="decimal"></div>
                ${canConfirm ? `<button class="btn btn-confirm" onclick="confirmRate()"><i class="ri-check-line me-1"></i>${r.set ? 'Change rate' : 'Confirm rate'}</button>` : ''}
                <div class="lock"><i class="ri-lock-line me-1"></i>Paid and requested items keep their original rate</div>
            </div>
        </div>
        ${!r.rate ? '<div class="mt-2 small" style="color:#fff;"><i class="ri-error-warning-line me-1"></i>USD payments cannot be requested until today\'s rate is confirmed' + (canConfirm ? '' : ' by an authorised user (Daily USD Rate privilege)') + '.</div>' : ''}`;
}
async function confirmRate() {
    const v = document.getElementById('fx_rate').value;
    const r = await Swal.fire({ title: 'Confirm today\'s rate', html: `USD 1 = <b>LKR ${esc(v)}</b> for ${esc(dmy(_desk.rate.date))}.<br><small class="text-muted">Payments already requested keep their own locked rate; only new requests use this one.</small>`, icon: 'question', showCancelButton: true, confirmButtonText: 'Confirm' });
    if (!r.isConfirmed) return;
    const j = await postJson('confirm_rate', { rate: v });
    toast(j.status === 200, j.message);
    if (j.status === 200) { _desk.rate = j.rate; _todayUsdRate = j.rate.rate; reload(); }
}

function renderFilters(d) {
    const F = [['all', 'All payments'], ['outstanding', 'Outstanding'], ['due_soon', 'Due soon'], ['overdue', 'Overdue'], ['awaiting', 'Awaiting approval'], ['bank_initiated', 'Bank initiated'], ['paid', 'Fully paid'], ['notify_pending', 'Notification pending'], ['check', 'Needs checking'], ['unverified', 'Unverified']];
    document.getElementById('tpd_filters').innerHTML = F.map(([k, l]) => `<button class="f ${_filter === k ? 'on' : ''} ${(d.counts[k] || 0) === 0 ? 'zero' : ''}" onclick="setFilter('${k}')">${l}<span class="n">${d.counts[k] || 0}</span></button>`).join('');
}
function setFilter(k) { _filter = k; renderFilters(_desk); renderDays(_desk); }
function tpdToggleDays(open) { _desk.days.forEach(day => { _closedDays[day.day_no] = !open; }); renderDays(_desk); }
function lineByKey(k) { return _desk.lines.find(l => l.key === k); }

function renderDays(d) {
    const box = document.getElementById('tpd_days');
    let shown = 0, html = '';
    d.days.forEach(day => {
        const lines = day.lines.map(lineByKey).filter(l => l && (l.filters || []).includes(_filter));
        if (!lines.length && _filter !== 'all') return;
        shown += lines.length;
        const dt = day.date ? new Date(day.date + 'T00:00:00') : null;
        const allPaid = day.lines.length > 0 && day.paid_count === day.lines.length;
        const tile = dt ? `<div class="date-tile ${allPaid ? 'done' : ''}"><b>${dt.getDate()}</b><small>${dt.toLocaleDateString('en-GB', {month:'short'}).toUpperCase()} · ${dt.toLocaleDateString('en-GB', {weekday:'short'}).toUpperCase()}</small></div>` : `<div class="date-tile none"><b>—</b><small>NO DAY</small></div>`;
        const ttl = day.day_no ? `Day ${day.day_no}${day.title ? ' · ' + esc(day.title) : ''}` : esc(day.title);
        const sub = [day.route, day.hotel ? 'Night: ' + day.hotel : ''].filter(Boolean).map(esc).join(' · ');
        const counts = day.lines.length ? `${day.paid_count} paid · ${day.pending} pending` : 'no payments';
        html += `<div class="day ${_closedDays[day.day_no] ? 'closed' : ''}" data-day="${day.day_no}">
            <div class="day-h" onclick="toggleDay(${day.day_no})">${tile}<div><div class="ttl">${ttl}</div><div class="rt">${sub || '&nbsp;'}</div></div>
                <div class="amt"><b class="num">LKR ${fmtNum(day.total)}</b><div class="s">${allPaid ? 'Fully paid' : counts}</div></div><i class="ri-arrow-down-s-line fs-4 car"></i></div>
            <div class="day-b">${lines.length ? lines.map(renderLine).join('') : '<div class="px-4 py-2 muted small">No payments on this day' + (_filter !== 'all' ? ' for this filter' : '') + '.</div>'}</div>
        </div>`;
    });
    box.innerHTML = html || '<div class="p-4 muted">No payment lines on this tour yet.</div>';
    document.getElementById('tpd_showing').textContent = `Showing ${shown} of ${d.lines.length} payment item${d.lines.length === 1 ? '' : 's'}`;
}
function toggleDay(n) { _closedDays[n] = !_closedDays[n]; renderDays(_desk); }
function toggleLine(k) { _openLines[k] = !_openLines[k]; renderDays(_desk); }

function renderLine(l) {
    const usd = l.currency === 'USD';
    const warn = (l.warnings || []).map(w => `<div class="warn ${w.level}"><i class="ri-error-warning-line me-1"></i>${esc(w.text)}</div>`).join('');
    const descOk = l.description && l.description !== l.supplier && !/^\d+$/.test(String(l.description).trim()) && String(l.description).trim().toLowerCase() !== String(l.category).toLowerCase();
    const sub = [l.category, l.nights ? `${l.nights} night${l.nights === 1 ? '' : 's'} · ${l.service_dates.map(dmy).join(', ')}` : (l.service_date ? dmy(l.service_date) : ''), descOk ? l.description : ''].filter(Boolean).map(esc).join(' · ');
    const live = (l.transactions || []).filter(t => t.usd_rate && !['cancelled', 'rejected', 'reversed'].includes(t.stage));
    const rates = usd ? [...new Set(live.map(t => money2(t.usd_rate)))] : [];
    const rateTxt = rates.length > 1 ? rates.join(' / ') : (l.usd_rate ? money2(l.usd_rate) : '—');
    // tf-paydesk-r2: the approval rate stays visible beside the payment-date rate
    const fxTx = live.filter(t => t.approval_rate && (t.paid_rate || t.init_rate) && Math.abs((t.paid_rate || t.init_rate) - t.approval_rate) > 0.00001);
    const fxNote = fxTx.length ? fxTx.map(t => `approval ${money2(t.approval_rate)} → ${t.paid_rate ? 'paid' : 'bank'} ${money2(t.paid_rate || t.init_rate)}${t.fx_diff ? ` (${t.fx_diff > 0 ? 'loss' : 'gain'} ${money2(Math.abs(t.fx_diff))})` : ''}`).join('; ') : '';
    const rateCell = usd ? `<div class="k">Rate used</div><div class="v num">${rateTxt} ${l.rate_locked ? '<i class="ri-lock-fill text-success" title="locked"></i>' : (l.usd_rate ? '<i class="ri-lock-unlock-line muted" title="indicative"></i>' : '')}</div><div class="muted" style="font-size:11px;">${fxNote ? esc(fxNote) : (rates.length > 1 ? 'paid in parts — each part keeps the rate of its own day' : esc(l.rate_note || ''))}</div>`
                         : `<div class="k">Rate</div><div class="v">LKR</div><div class="muted" style="font-size:11px;">no conversion</div>`;
    const amtCell = `<div class="k">${usd ? 'USD amount' : 'Amount'}</div><div class="v num">${usd ? '$ ' + money2(l.usd_amount) : 'LKR ' + fmtNum(l.amount)}</div>${usd && l.quoted_usd && Math.abs(l.quoted_usd - l.usd_amount) > 0.009 ? `<div class="muted" style="font-size:11px;">quoted $ ${money2(l.quoted_usd)}</div>` : ''}${l.invoice_no ? `<div class="muted" style="font-size:11px;">Inv. ${esc(l.invoice_no)}</div>` : ''}`;
    const lkrCell = `<div class="k">LKR amount</div><div class="v num">${l.lkr_amount > 0 ? fmtNum(l.lkr_amount) : '—'}${l.lkr_is_indicative && l.lkr_amount > 0 ? ' <small>indicative</small>' : ''}</div><div class="muted" style="font-size:11px;">paid ${fmtNum(l.paid_lkr)} · balance ${fmtNum(l.balance_lkr)}</div>`;
    const dates = `<div class="k">Requested / due</div><div class="v" style="font-size:12.5px;">${l.request_date ? dmy(l.request_date) : '—'} / ${l.due_date ? dmy(l.due_date) : '—'}</div>${l.urgency ? `<span class="pill sm ${l.urgency === 'overdue' ? 'red' : 'amber'}">${l.urgency_label}</span>` : ''}`;
    const stIdx = STAGES.indexOf(l.status === 'requested' ? 'awaiting' : l.status);
    const doneUpTo = l.notify === 'notified' ? 6 : (l.fully_paid ? 5 : Math.max(0, stIdx - 1));
    const nowAt = l.notify === 'notify_pending' ? 6 : (l.fully_paid ? 0 : stIdx);
    const stages = `<div class="stages" title="${esc(l.status_label)}${l.notify_label ? ' · ' + esc(l.notify_label) : ''}">${STAGES.slice(1).map((s, i) => `<i class="${i + 1 <= doneUpTo ? 'done' : (i + 1 === nowAt ? 'now' : '')}"></i>`).join('')}</div>`;
    const a = l.actions || {};
    const open = l.open_tx;
    // tf-paydesk-r2: an approved payment waits under its supplier in Supplier Payables; its batch carries it from the voucher to Paid
    const waitNote = l.status === 'approved' && _desk.accumulates ? `<div class="muted" style="font-size:11px;">waiting under ${esc(l.supplier)} in ${a.payables ? `<a href="${TPD_URL}payables?sup_acc=${encodeURIComponent(l.supplier_acc)}">Supplier Payables</a>` : 'Supplier Payables'}</div>` : '';
    const batchNote = open && open.batch ? `<div class="muted" style="font-size:11px;">batch <a href="${TPD_URL}batch/${open.batch.id}">${esc(open.batch.no)}</a> · Payment Voucher No. <b>${open.vnum || ''}</b>${open.batch.items > 1 ? ` · ${open.batch.items} payments${open.batch.tours > 1 ? ' · ' + open.batch.tours + ' tours' : ''}` : ''}${open.batch.individual ? ' · <span class="text-warning">paid individually: ' + esc(open.batch.bypass_label || '') + '</span>' : ''}</div>` : '';
    const status = `<div>${pill(l.status)}${l.notify ? '<div class="mt-1">' + pill(l.notify, 'sm') + '</div>' : ''}${l.partly ? '<div class="mt-1"><span class="pill sm purple">Partly Paid</span></div>' : ''}${stages}${waitNote}${batchNote}</div>`;
    const btn = [];
    if (a.request) btn.push(`<button class="btn btn-p" onclick="tpdRequest('${l.key}')"><i class="ri-send-plane-line me-1"></i>Request payment</button>`);
    if (a.request_balance) btn.push(`<button class="btn btn-outline-p" onclick="tpdRequest('${l.key}', true)"><i class="ri-send-plane-line me-1"></i>Request balance</button>`);
    if (a.review_rate) btn.push(`<button class="btn btn-warning" onclick="openRateReview(${l.legacy_idx})">Review &amp; release</button>`);
    if (a.approve) btn.push(`<button class="btn btn-p" onclick="openApprove('${l.key}')"><i class="ri-shield-check-line me-1"></i>Approve payment</button>`);
    if (a.reject) btn.push(`<button class="btn btn-outline-danger" onclick="tpdReject('${l.key}')">Reject</button>`);
    if (a.issue_voucher) btn.push(_desk.accumulates ? `<button class="btn btn-p" onclick="openBatch('${l.key}')"><i class="ri-stack-line me-1"></i>Create batch · issue voucher</button><button class="btn btn-outline-p" onclick="openPayNow('${l.key}')" title="Pay Individually Now — a single-item batch, the accumulation stage bypassed with a recorded reason"><i class="ri-flashlight-line me-1"></i>Pay individually now</button>` : `<button class="btn btn-p" onclick="tpdIssueVoucher('${l.key}')"><i class="ri-file-text-line me-1"></i>Issue voucher</button>`);
    if (a.initiate) btn.push(open && open.batch ? `<a class="btn btn-p" href="${TPD_URL}batch/${open.batch.id}"><i class="ri-bank-line me-1"></i>Bank payment page</a>` : `<button class="btn btn-p" onclick="openInitiate('${l.key}')"><i class="ri-bank-line me-1"></i>Record bank initiation</button>`);
    if (a.paid) btn.push(open && open.batch ? `<a class="btn btn-success" href="${TPD_URL}batch/${open.batch.id}"><i class="ri-checkbox-circle-line me-1"></i>Final approval · Paid</a>` : `<button class="btn btn-success" onclick="openPaid('${l.key}')"><i class="ri-checkbox-circle-line me-1"></i>Paid</button>`);
    if (a.remit && l.notify === 'notify_pending') btn.push(`<button class="btn btn-outline-p" onclick="openRemit('${l.key}')"><i class="ri-mail-send-line me-1"></i>Prepare remittance</button>`);
    if (a.cancel_request) btn.push(`<button class="btn btn-outline-secondary" onclick="tpdCancelRequest('${l.key}')">Cancel request</button>`);
    btn.push(`<button class="btn btn-outline-secondary" onclick="toggleLine('${l.key}')"><i class="ri-${_openLines[l.key] ? 'eye-off' : 'eye'}-line me-1"></i>${_openLines[l.key] ? 'Hide details' : 'Details'}</button>`);
    return `<div class="line" data-key="${l.key}">
        <div class="ico ${l.group}"><i class="${GROUP_ICON[l.group] || 'ri-price-tag-3-line'}"></i></div>
        <div class="cell"><div class="sup">${esc(l.supplier)}</div><div class="desc">${sub}</div>${warn}${l.rate_label && usd ? `<div class="muted" style="font-size:11.5px;"><i class="ri-price-tag-3-line me-1"></i>${esc(l.rate_label)}</div>` : ''}</div>
        <div class="cells"><div>${amtCell}</div><div>${rateCell}</div><div>${lkrCell}</div></div>
        <div class="dates">${dates}</div>
        <div class="status">${status}</div>
        <div class="acts">${btn.join('')}</div>
        ${_openLines[l.key] ? `<div class="more">${renderDetail(l)}</div>` : ''}
    </div>`;
}
function ladder(tx) {
    const steps = [
        ['Requested', tx.requested_at, tx.requested_by], ['Approved for payment', tx.accepted_at && tx.pr_status !== 5 ? tx.accepted_at : null, tx.accepted_by],
        ['Voucher issued', tx.voucher_issued_at, tx.voucher_issued_by], ['Bank initiated', tx.initiation ? tx.initiation.initiated_at : null, tx.initiation ? tx.initiation.initiated_by : null],
        ['Paid', tx.finalized ? (tx.approval ? tx.approval.approved_at : tx.finalized_at) : null, tx.approval ? tx.approval.approved_by : tx.finalized_by],
        ['Supplier notified', tx.remittance && tx.remittance.status === 'sent' ? tx.remittance.sent_at : null, tx.remittance ? tx.remittance.sent_by : null],
    ];
    let reached = -1; steps.forEach((s, i) => { if (s[1]) reached = i; });
    return '<ul class="ladder">' + steps.map((s, i) => `<li class="${s[1] ? 'done' : (i === reached + 1 ? 'now' : '')}"><span class="dot"></span><span><b>${s[0]}</b>${s[1] ? ` <span class="who">— ${dmyt(s[1])}${s[2] ? ' · ' + esc(s[2]) : ''}</span>` : ''}</span></li>`).join('') + '</ul>';
}
function renderDetail(l) {
    const sb = l.supplier_bank;
    let h = '<div class="detail"><div class="row g-3">';
    h += '<div class="col-lg-7"><h6>Transactions</h6>';
    if (!l.transactions.length) h += '<div class="muted">No payment request yet.</div>';
    l.transactions.forEach((tx, i) => {
        const usdTx = tx.currency === 'USD';
        const amt = usdTx ? `$ ${money2(tx.amount)}${tx.usd_rate ? ` × ${money2(tx.usd_rate)} = LKR ${money2(tx.lkr || 0)}` : ''}` : `LKR ${money2(tx.amount)}`;
        // tf-paydesk-r2: the approval figures (never overwritten) beside the payment-date figures (locked at Paid)
        const fig = usdTx && tx.approval_rate ? `<div class="meta">Approval figures: $ ${money2(tx.amount)} × ${money2(tx.approval_rate)}${tx.approval_rate_date ? ' (' + dmy(tx.approval_rate_date) + ')' : ''} = LKR ${money2(tx.approval_lkr)} <span class="muted">provisional</span>${tx.request_rate && Math.abs(tx.request_rate - tx.approval_rate) > 0.00001 ? ` · requested at ${money2(tx.request_rate)}` : ''}</div>`
            + (tx.paid_rate ? `<div class="meta"><i class="ri-lock-fill text-success"></i> Payment-date figures: $ ${money2(tx.amount)} × ${money2(tx.paid_rate)} = LKR ${money2(tx.paid_lkr)} · exchange-rate ${tx.fx_diff > 0 ? 'loss' : (tx.fx_diff < 0 ? 'gain' : 'difference')} <b>${tx.fx_diff > 0 ? '+' : ''}${money2(tx.fx_diff || 0)}</b></div>` : (tx.init_rate && !tx.finalized ? `<div class="meta">At bank initiation: × ${money2(tx.init_rate)} = LKR ${money2(tx.lkr)} <span class="muted">(final at Paid, at the rate of the actual payment date)</span></div>` : '')) : '';
        h += `<div class="tx"><div><b>${i + 1}. ${esc(STATUS_LABEL[tx.stage] || tx.stage)}</b> <span class="meta">· ${amt}${tx.usd_rate ? ' · rate ' + esc(tx.rate_from || '') : ''}${tx.invoice_no ? ' · inv. ' + esc(tx.invoice_no) : ''}${tx.booking_ref ? ' · ref ' + esc(tx.booking_ref) : ''}${tx.bulk && !tx.batch ? ' · bulk voucher' : ''}</span>
            ${fig}
            ${reviewBox(tx, l)}
            ${tx.batch ? `<div class="meta">Batch <a href="${TPD_URL}batch/${tx.batch.id}"><b>${esc(tx.batch.no)}</b></a> · ${esc(tx.batch.supplier || '')} · ${tx.batch.items} payment${tx.batch.items === 1 ? '' : 's'}${tx.batch.tours > 1 ? ' across ' + tx.batch.tours + ' tours' : ''}${tx.voucher_total && tx.batch.items > 1 ? ' · voucher total LKR ' + money2(tx.voucher_total) : ''}${tx.batch.individual ? ' · <span class="text-warning">paid individually — ' + esc(tx.batch.bypass_label || '') + '</span>' : ''}</div>` : ''}
            ${tx.vnum ? `<div class="meta"><b>Payment Voucher No. <a href="${TPD_URL}voucher/${tx.vnum}" target="_blank">${tx.vnum}</a></b> · ${esc(tx.bank || '')}${tx.remark ? ' · ' + esc(tx.remark) : ''} · <a href="${TPD_URL}voucher/${tx.vnum}?pdf=1" target="_blank">PDF</a></div>` : ''}
            ${tx.initiation ? `<div class="meta">Initiated ${dmyt(tx.initiation.initiated_at)} by ${esc(tx.initiation.initiated_by)}${tx.initiation.payment_date ? ' · payment date ' + dmy(tx.initiation.payment_date) : ''}${tx.initiation.usd_rate ? ' · rate ' + money2(tx.initiation.usd_rate) : ''}${tx.initiation.batch_ref ? ' · batch ' + esc(tx.initiation.batch_ref) : ''}${tx.initiation.bank_ref ? ' · ref ' + esc(tx.initiation.bank_ref) : ''}${tx.initiation.charges ? ' · charges ' + money2(tx.initiation.charges) : ''}${tx.initiation.total_debit ? ' · total debit ' + money2(tx.initiation.total_debit) : ''}</div>` : ''}
            ${tx.approval ? `<div class="meta"><i class="ri-lock-fill text-success"></i> Paid ${dmy(tx.approval.paid_date)} · LKR ${money2(tx.batch && tx.batch.items > 1 ? tx.paid_lkr : tx.approval.lkr_amount)}${tx.approval.usd_rate ? ' ($ ' + money2(tx.batch && tx.batch.items > 1 ? tx.amount : tx.approval.usd_amount) + ' @ ' + money2(tx.approval.usd_rate) + ')' : ''}${tx.batch && tx.batch.items > 1 ? ' · batch total LKR ' + money2(tx.approval.lkr_amount) : ''}${tx.approval.charges ? ' · bank charges ' + money2(tx.approval.charges) + (tx.batch && tx.batch.items > 1 && tx.charge_share !== null && tx.charge_share !== undefined ? ' (this tour\'s share ' + money2(tx.charge_share) + ')' : '') + (tx.approval.charges_posted == 1 ? ', posted' : '') : ''}${tx.approval.total_debit ? ' · total debited ' + money2(tx.approval.total_debit) + (tx.batch && tx.batch.items > 1 && tx.tour_total_debit !== null && tx.tour_total_debit !== undefined ? ' (this tour ' + money2(tx.tour_total_debit) + ')' : '') : ''}${tx.approval.reconciled == 1 ? ' · ledger reconciled' : ''} · ref ${esc(tx.approval.bank_ref || '')} · approved by ${esc(tx.approval.approved_by)}</div>` : (tx.finalized ? `<div class="meta"><i class="ri-lock-fill text-success"></i> Paid ${dmy(tx.paid_date)} (Bank Payment Check, ${esc(tx.finalized_by || '')})</div>` : '')}
            ${tx.remittance ? `<div class="meta">Remittance advice: ${esc(tx.remittance.status)}${tx.remittance.sent_at ? ' · sent ' + dmyt(tx.remittance.sent_at) + ' to ' + esc(tx.remittance.to || '') : ''}${tx.remittance.error ? ' · <span class="text-danger">' + esc(tx.remittance.error) + '</span>' : ''} ${tx.vnum ? `· <a href="${TPD_URL}remittance/${tx.vnum}" target="_blank">view</a>` : ''}${tx.remittance.status !== 'sent' && tx.finalized && TPD_PERMS.requester ? ` · <a href="#" onclick="openRemit('${l.key}', ${tx.vnum}); return false;">prepare / send</a>` : ''}</div>` : ''}
            </div><div>${ladder(tx)}</div></div>`;
    });
    if (l.advance) h += `<div class="meta muted">Advance paid ${fmtNum(l.advance.paid)} · expenses against it ${fmtNum(l.advance.expenses)} · remaining ${fmtNum(l.advance.remaining)} · settled ${fmtNum(l.advance.settled)}</div>`;
    h += '</div><div class="col-lg-5">';
    h += '<h6>Supplier bank details</h6>';
    if (sb) {
        const st = sb.status === 'verified' ? '<span class="pill sm green">Verified</span>' : (sb.status === 'changed' ? '<span class="pill sm red">CHANGED since verification</span>' : (sb.status === 'inactive' ? '<span class="pill sm grey">Inactive</span>' : '<span class="pill sm amber">Not verified</span>'));
        h += `<div>${esc(sb.beneficiary_name || sb.supplier_name)} ${st}</div><div class="muted">${esc([sb.bank_name, sb.branch].filter(Boolean).join(' · ') || 'No bank on file')}${sb.account_no ? ' · A/C ' + esc(sb.account_no) : ''}${sb.swift ? ' · ' + esc(sb.swift) : ''}${sb.currency ? ' · ' + esc(sb.currency) : ''}</div>`;
        if (sb.verified_by) h += `<div class="muted">verified by ${esc(sb.verified_by)} on ${dmyt(sb.verified_at)}</div>`;
        if (sb.differs_from_master) h += `<div class="text-danger">Differs from the supplier master's account (${esc(sb.master.account_no || '—')}).</div>`;
        h += `<div class="mt-1"><a href="#" onclick="openSupplierBank('${l.key}'); return false;">View / verify</a> · <a href="<?= base_url('master_files/supplier_bank') ?>?q=${encodeURIComponent(sb.sup_acc)}" target="_blank">edit on the master</a></div>`;
    } else h += '<div class="muted">—</div>';
    if (l.payee) h += `<h6>Who is paid</h6><div>${_payeeBlockHtml({ payee: l.payee, total: l.amount, category: l.category, supplier_bank_acc: l.supplier_bank_acc })}</div>`;
    h += '<h6>Invoice & documents</h6>';
    h += (l.documents || []).length ? '<ul class="mb-1 ps-3">' + l.documents.map(x => `<li>${esc(x.title || x.kind)}${x.number ? ' · ' + esc(x.number) : ''}${x.file_path ? ` · <a href="${TPD_URL}file/${x.id}" target="_blank">open</a>` : ''} <span class="muted">(${esc(x.uploaded_by || '')}, ${dmyt(x.uploaded_at)})</span></li>`).join('') + '</ul>' : '<div class="muted">None attached.</div>';
    if (TPD_PERMS.requester) h += `<a href="#" onclick="openDoc('${l.key}'); return false;"><i class="ri-attachment-2"></i> Add invoice number / document</a>`;
    if (l.source_type === 'hotel' && l.cost_offer) h += `<h6>Rate</h6><div>${_offerCompactHtml(_tourData.payment_rows[l.legacy_idx], l.legacy_idx)}</div>`;
    if (l.expense && TPD_PERMS.requester && !l.transactions.length) h += `<div class="mt-2"><a href="#" class="text-danger" onclick="tpdCancelExpense(${l.expense.id}); return false;">Cancel this expense</a></div>`;
    h += '</div></div></div>';
    return h;
}

// tf-paydesk-r7h build 5: an association with no stored link — shown, not counted as paid, reviewed (IS / is NOT the payment) by a holder of
// the batch privilege before any further payment of the line; a review that confirmed it is shown as such
function reviewBox(tx, l) {
    const rv = tx.review;
    if (!rv) return tx.reviewed ? `<div class="meta"><i class="ri-shield-check-line text-success me-1"></i>Linked by review: ${esc(tx.reviewed.by)} confirmed on ${dmyt(tx.reviewed.at)} — ${esc(tx.reviewed.reason)}</div>` : '';
    const vs = (rv.vnums || []).filter(Boolean);
    const what = rv.kind === 'line' ? `Payment Voucher No. ${rv.vnum} of this supplier is not linked to any payment request`
        : (rv.state === 'invalid' ? `The desk record names Payment Voucher No. ${vs.join(', ')}, but it does not agree` : `Payment Voucher No. ${vs.join(', ')} may be this payment — not linked`);
    let h = `<div class="warn warning mt-1"><i class="ri-question-line me-1"></i><b>Unverified — review needed.</b> ${what}${rv.basis ? ' (' + esc(rv.basis) + ')' : ''}. It is not counted as paid, and no further payment of this item is allowed until a holder of the batch privilege reviews it.`;
    if (TPD_PERMS.batch && _desk.accumulates) h += vs.map(vn => ` <button class="btn btn-sm btn-light py-0" onclick="tpdReview('${l.key}', ${tx.pr_id || 0}, ${vn}, 'is', '${rv.kind}')">PV ${vn} IS this payment…</button> <button class="btn btn-sm btn-light py-0" onclick="tpdReview('${l.key}', ${tx.pr_id || 0}, ${vn}, 'not', '${rv.kind}')">PV ${vn} is NOT this payment…</button>`).join('');
    return h + '</div>';
}
async function tpdReview(key, prId, vnum, decision, kind) {
    const l = lineByKey(key); if (!l) return;
    const is = decision === 'is';
    const r = await Swal.fire({ title: is ? `Payment Voucher No. ${vnum} IS this payment?` : `Payment Voucher No. ${vnum} is NOT this payment?`,
        html: `<div class="small text-start">${esc(l.supplier)} · ${esc(l.description || l.category)}${prId ? ' · request #' + prId : ''}<br>Open the voucher first (payee, tour, service, invoice, amount). Your name, the time and the reason are recorded once in the audit history. ${is ? 'The voucher then counts as this payment.' : 'The voucher is then no longer shown as this payment.'} Nothing else is changed.</div>`,
        input: 'text', inputLabel: 'How did you check? (required — recorded)', showCancelButton: true, confirmButtonText: is ? 'Yes — this is the payment' : 'Not this payment',
        confirmButtonColor: is ? '#2d6a4f' : '#b42318', inputValidator: v => (!v || !v.trim()) ? 'A reason is required.' : undefined });
    if (!r.isConfirmed) return;
    const body = { kind: kind, decision: decision, vnum: vnum, reason: r.value };
    if (kind === 'line') { body.tour_id = _desk.tour.tour_id; body.line_key = key; } else body.pr_id = prId;
    const j = await postJson('payment_review', body); toast(j.status === 200, j.message); if (j.status === 200) reload();
}
function renderOverall(d) {
    const T = d.totals;
    document.getElementById('tpd_overall').innerHTML = `<h6 class="fw-bold mb-3">Overall payment progress</h6>
        <div class="d-flex align-items-center gap-3"><div style="position:relative;width:130px;height:130px;"><div id="tpd_donut"></div><div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;"><b style="font-size:1.4rem;">${T.pct}%</b><small class="muted">of estimated cost paid</small></div></div>
        <div class="flex-grow-1 small"><div class="d-flex justify-content-between"><span>Paid</span><b class="num">LKR ${fmtNum(T.paid)}</b></div><div class="d-flex justify-content-between"><span>Remaining</span><b class="num">LKR ${fmtNum(T.outstanding)}</b></div><div class="d-flex justify-content-between border-top pt-1 mt-1"><span>Total</span><b class="num">LKR ${fmtNum(T.estimated)}</b></div></div></div>`;
    if (window._overallChart) { try { window._overallChart.destroy(); } catch (e) {} }
    window._overallChart = new ApexCharts(document.getElementById('tpd_donut'), { series: [Math.max(0, T.paid), Math.max(0, T.outstanding)], chart: { type: 'donut', width: 130, height: 130, sparkline: { enabled: true } }, labels: ['Paid', 'Remaining'], colors: ['#1f8a4c', '#e5e7eb'], legend: { show: false }, dataLabels: { enabled: false }, tooltip: { enabled: false }, plotOptions: { pie: { donut: { size: '74%' } } }, stroke: { width: 0 } });
    window._overallChart.render();
}
function renderCats(d) {
    const order = ['hotel', 'meal', 'entrance', 'activity', 'transport', 'driver_advance', 'driver_settlement', 'guide', 'other'];
    const keys = order.filter(k => d.categories[k]);
    document.getElementById('tpd_cats').innerHTML = '<h6 class="fw-bold mb-3">Payments by category</h6>' + (keys.length ? keys.map(k => { const c = d.categories[k]; return `<div class="mb-2"><div class="d-flex justify-content-between small"><span>${esc(c.label)} <span class="muted">(${c.count})</span></span><b>${c.pct}%</b></div><div class="bar"><i style="width:${c.pct}%"></i></div><div class="muted" style="font-size:11px;">paid ${fmtNum(c.paid)} of ${fmtNum(c.total)}</div></div>`; }).join('') : '<div class="muted">No payment lines.</div>');
}
function renderAttention(d) {
    const items = [];
    const due = d.lines.filter(l => !l.fully_paid && l.urgency === 'due_soon'), over = d.lines.filter(l => !l.fully_paid && l.urgency === 'overdue');
    if (over.length) items.push(['red', `${over.length} overdue payment${over.length === 1 ? '' : 's'}`, `LKR ${fmtNum(over.reduce((s, l) => s + l.balance_lkr, 0))} outstanding`]);
    if (due.length) items.push(['', `${due.length} payment${due.length === 1 ? '' : 's'} due this week`, `LKR ${fmtNum(due.reduce((s, l) => s + l.balance_lkr, 0))} outstanding`]);
    const quoted = d.lines.filter(l => (l.warnings || []).some(w => ['rate_check', 'rate_quoted', 'rate_none', 'rate_expired'].includes(w.code)));
    if (quoted.length) items.push(['', `${quoted.length} hotel rate${quoted.length === 1 ? '' : 's'} to check`, 'Quoted / no contract / expired — check against approved contracts']);
    const special = d.lines.filter(l => (l.warnings || []).some(w => w.code === 'special_rate'));
    if (special.length) items.push(['blue', `${special.length} special rate${special.length === 1 ? '' : 's'} available`, 'Published rate used until an authorised user decides']);
    const bank = d.lines.filter(l => (l.warnings || []).some(w => w.code === 'bank_unverified' || w.code === 'bank_changed'));
    if (bank.length) items.push(['', `${bank.length} supplier bank account${bank.length === 1 ? '' : 's'} to verify`, 'Release waits until a Finance & Approvals Management Approver verifies them in the registry']);
    const removed = d.lines.filter(l => l.orphan);
    if (removed.length) items.push(['red', `${removed.length} payment${removed.length === 1 ? '' : 's'} whose service left the itinerary`, 'Shown flagged; an authorised adjustment is needed']);
    const aw = d.lines.filter(l => ['awaiting', 'requested'].includes(l.status)), bi = d.lines.filter(l => l.status === 'bank_initiated'), np = d.lines.filter(l => l.notify === 'notify_pending');
    if (aw.length) items.push(['blue', `${aw.length} awaiting management approval`, 'Approve from the schedule or on the Payment approval page']);
    const ap = d.lines.filter(l => l.status === 'approved');
    if (ap.length && d.accumulates) items.push(['blue', `${ap.length} approved — waiting under the supplier for a batch`, 'Management creates the supplier\'s payment batch in Supplier Payables']);
    if (bi.length) items.push(['blue', `${bi.length} bank payment${bi.length === 1 ? '' : 's'} initiated`, 'Awaiting the bank approver\'s Paid']);
    if (np.length) items.push(['', `${np.length} supplier notification${np.length === 1 ? '' : 's'} pending`, 'Prepare and send the remittance advice']);
    if (!d.rate.rate && d.lines.some(l => l.currency === 'USD' && l.usd_remaining > 0)) items.push(['red', "Today's USD rate not entered", 'USD payments cannot be requested until it is confirmed']);
    document.getElementById('tpd_attention').innerHTML = '<h6 class="fw-bold mb-3">Needs attention</h6>' + (items.length ? items.map(i => `<div class="att ${i[0]}"><b>${esc(i[1])}</b><span>${esc(i[2])}</span></div>`).join('') : '<div class="muted">Nothing needs attention.</div>')
        + '<div class="note-red mt-3">Completed payments keep their exchange rate and LKR value when the itinerary or today\'s rate changes.</div>';
}
function renderHistory(d) {
    const h = d.history || [];
    document.getElementById('tpd_history').innerHTML = h.length ? h.map(x => `<div class="row-h"><div class="when">${dmyt(x.at)}<br><span class="muted">${esc(x.by || '')}</span></div><div class="what"><b>${esc(x.what)}</b><span class="muted">${esc(x.detail || '')}</span></div><div class="muted">${x.vnum ? `<a href="${TPD_URL}voucher/${x.vnum}" target="_blank">${esc(x.ref)}</a>` : esc(x.ref || '')}</div></div>`).join('') : '<div class="muted">Nothing yet.</div>';
}
function renderAudit(d) {
    const a = d.audit || [];
    const fmt = v => { if (!v) return ''; try { const o = JSON.parse(v); return typeof o === 'object' ? Object.entries(o).filter(([k, x]) => x !== null && x !== '' && typeof x !== 'object').map(([k, x]) => `${k}: ${x}`).join(' · ') : String(o); } catch (e) { return String(v); } };
    document.getElementById('tpd_audit').innerHTML = `<table class="table table-sm audit mb-0"><thead class="table-light"><tr><th>Date &amp; time</th><th>User</th><th>Action</th><th>Previous → new</th><th>Reason</th><th>Rate / bank / ref</th></tr></thead><tbody>`
        + (a.length ? a.map(r => `<tr><td class="mono">${esc(r.at)}</td><td>${esc(r.user)}</td><td><b>${esc(r.action)}</b>${r.ref_type ? `<br><span class="muted">${esc(r.ref_type)} ${esc(r.ref_id || '')}${r.vnum ? ' · Payment Voucher No. ' + r.vnum : ''}${r.invoice_no ? ' · inv. ' + esc(r.invoice_no) : ''}</span>` : ''}</td><td><pre>${esc(fmt(r.prev_value))}${r.prev_value && r.new_value ? '\n→ ' : ''}${esc(fmt(r.new_value))}</pre></td><td>${esc(r.reason || '')}</td><td class="muted">${r.usd_rate ? 'rate ' + money2(r.usd_rate) + '<br>' : ''}${esc(r.bank_account || '')}${r.bank_ref ? '<br>ref ' + esc(r.bank_ref) : ''}</td></tr>`).join('') : '<tr><td colspan="6" class="muted">No audit entries yet.</td></tr>')
        + '</tbody></table><div class="muted small mt-1">Append-only: nothing here can be edited or deleted from the application.</div>';
}
function tpdHistTab(t) { _histTab = t; document.getElementById('tpd_history').style.display = t === 'h' ? '' : 'none'; document.getElementById('tpd_audit').style.display = t === 'a' ? '' : 'none'; document.getElementById('hist_tab_h').classList.toggle('active', t === 'h'); document.getElementById('hist_tab_a').classList.toggle('active', t === 'a'); }

// the earlier table, as it was
function renderClassic() {
    const data = _tourData, t = data.tour_info, rows = data.payment_rows;
    const tbody = document.getElementById('payment_table_body');
    tbody.innerHTML = '';
    rows.forEach((row, idx) => {
        const statusCls = statusClass(row.status);
        const canPay     = row.status === 'Not Paid' || row.status === 'Partially Paid';
        // Driver Advance can only be requested once the driver has actually submitted
        // an advance request (driver_advance_request row) — nothing to push otherwise.
        const canRequest = row.status === 'Payment Not Requested'
            && (['Hotel', 'Activity'].includes(row.category)
                || (row.category === 'Driver Advance' && !!row.driver_advance_request_id));
        let btnHtml = '<span class="text-muted small">-</span>';
        // Pay Now hidden for now — not needed this release. Keep canPay/fillPaymentForm
        // intact for future use; just don't render the button.
        // if (canPay) {
        //     btnHtml = `<button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="fillPaymentForm(${idx})">Pay Now</button>`;
        // } else
        if (canRequest) {
            btnHtml = `<button class="btn btn-sm btn-outline-warning py-0 px-2" onclick="openPaymentRequestModal(${idx})">Payment Request</button>`;
        }
        // tf-offers-r3: held for the System Administrator's rate review — nothing requested yet
        const rr = row.category === 'Hotel' ? row.rate_review : null;
        const held = !!(rr && rr.status === 'awaiting' && row.status === 'Payment Not Requested');
        if (held) {
            btnHtml = CAN_CHOOSE_OFFER && rr.hold_id
                ? `<button class="btn btn-sm btn-warning py-0 px-2 rate-review-btn" onclick="openRateReview(${idx})">Review &amp; release</button>`
                : `<span class="badge bg-orange rate-review-wait" title="Prepared by ${esc(rr.prepared_by)} on ${esc(String(rr.prepared_at || '').slice(0, 16))}. The System Administrator confirms the rate, then it goes to Payment approval.">Awaiting rate review</span>`;
        } else if (rr && rr.status === 'returned' && row.status === 'Payment Not Requested') {
            btnHtml += `<div class="small text-muted rate-review-returned" style="max-width:220px; white-space:normal;">Returned by ${esc(rr.returned_by || '')}${rr.note ? ': ' + esc(rr.note) : ''}</div>`;
        }
        const catColor = catColorClass(row.category);
        const descHtml = row.voucher_no
            ? `<a href="${voucherPrintBaseUrl}${row.voucher_no}?tourid=${t.tour_id}" target="_blank" class="text-dark text-decoration-none"><i class="ri-file-text-line text-secondary me-1"></i>${esc(row.description)}</a>`
            : `<i class="ri-file-text-line text-secondary me-1"></i>${esc(row.description)}`;
        // Not-yet-requested hotel rows: show the $ Q Amount (tour_accounts' own
        // display for this state) instead of an LKR total, since no rate has
        // been applied/locked in yet at this point.
        const isHotelNotRequested = row.category === 'Hotel' && row.status === 'Payment Not Requested';
        // LKR amount in bigger font; when a per-row USD rate was locked in at request
        // time (Hotel only — everything else is LKR-native), show the $ x rate below
        // it as subtext, matching what was actually converted for this request.
        let totalCellHtml;
        if (isHotelNotRequested) {
            totalCellHtml = `<span class="fw-semibold">$ ${fmtNum(row.total_dollar ?? 0)}</span>`;
        } else {
            totalCellHtml = `<div class="fw-semibold" style="font-size:1.05rem;">LKR ${fmtNum(row.total)}</div>`;
            if (row.usd_rate) {
                totalCellHtml += `<div class="text-muted" style="font-size:.75rem;">$ ${fmtNum(row.total_dollar ?? 0)} @ ${fmtRate(row.usd_rate)}</div>`;
            }
        }
        // Hotel cost handoff: payable amount per the Hotel Rates panel, with its source
        if (row.category === 'Hotel' && row.cost_dollar !== null && row.cost_dollar !== undefined) {
            totalCellHtml += _hotelCostHtml(row, idx);
        }
        tbody.innerHTML += `<tr>
            <td><span class="badge bg-light text-dark border">${esc(row.day)}</span></td>
            <td><span class="badge ${catColor}">${esc(row.category)}</span></td>
            <td class="small">${esc(row.supplier)}${_payeeLineHtml(row)}</td>
            <td class="small text-dark">${descHtml}</td>
            <td class="text-end">${totalCellHtml}</td>
            <td class="text-end small">${fmtNum(row.paid)}</td>
            <td class="text-end small fw-semibold">${fmtNum(row.balance)}</td>
            <td class="small">${row.request_date ? fmtDate(row.request_date) : '-'}</td>
            <td class="small">${_stayDatesHtml(row)}</td>
            <td><span class="badge rounded-pill ${held ? 'bg-orange' : statusCls}">${esc(held ? 'Awaiting rate review' : row.status)}</span></td>
            <td>${btnHtml}</td>
        </tr>`;
    });
    document.getElementById('table_showing').textContent = `Showing 1 to ${rows.length} of ${rows.length} entries`;
}

// ---- actions ----
function tpdRequest(key, balance) {
    const l = lineByKey(key);
    if (!l) return;
    if (l.source_type === 'expense') return tpdExpenseRequest(l);
    if (l.legacy_idx === null || l.legacy_idx === undefined) return toast(false, 'This line cannot be requested from here.');
    _pending.request = { key, balance: !!balance, line: l };
    openPaymentRequestModal(l.legacy_idx);
    // the extra fields of the request: supplier invoice, booking reference, due date, note
    setTimeout(() => {
        const inv = document.getElementById('pr_invoice_no'), ref = document.getElementById('pr_booking_ref'), due = document.getElementById('pr_due_date'), note = document.getElementById('pr_note');
        if (inv) inv.value = l.invoice_no || ''; if (ref) ref.value = l.booking_ref || ''; if (due) due.value = l.due_date || ''; if (note) note.value = '';
        const bal = document.getElementById('pr_balance_note');
        if (bal && !balance && l.source_type === 'activity' && l.shared_supplier && l.shared_supplier.lines > 1) {
            const sum = tpdCeiling(_tourData.payment_rows[l.legacy_idx]);
            bal.style.display = ''; bal.textContent = `${esc(l.supplier)} has ${l.shared_supplier.lines} charges on this tour; one payment request covers all of them (LKR ${money2(sum)} not yet requested). Enter the amount for the charges you are requesting now.`;
            document.getElementById('pr_amount').value = sum;
            return;
        }
        if (bal) { bal.style.display = balance ? '' : 'none'; if (balance && l.currency === 'USD') { bal.textContent = `Balance payment: $ ${money2(l.usd_remaining)} of $ ${money2(l.usd_amount)} remains ($ ${money2(l.usd_paid)} already paid). Today's rate applies to this part only.`; document.getElementById('pr_amount').value = l.usd_remaining; updatePaymentRequestLkrPreview(); } else if (balance) { bal.textContent = `Balance payment: LKR ${money2(l.balance_lkr)} remains.`; document.getElementById('pr_amount').value = l.balance_lkr; } }
    }, 50);
}
// after the chain accepted the request: the desk records the invoice, reference, due date and note with it
async function tpdAfterRequest(row, res) {
    const p = _pending.request;
    if (!p || res.held) return;
    const type = row.category === 'Hotel' ? 'Hotel' : (row.category === 'Activity' ? 'lunch' : 'Transport Advance');
    const last = (p.line.transactions || []).filter(t => t.finalized).slice(-1)[0];
    await postJson('request_meta', { tour_id: _tourData.tour_info.tour_id, acc: row.supplier_acc, type, invoice_no: (document.getElementById('pr_invoice_no') || {}).value || '', booking_ref: (document.getElementById('pr_booking_ref') || {}).value || '', due_date: (document.getElementById('pr_due_date') || {}).value || '', note: (document.getElementById('pr_note') || {}).value || '', parent_pr_id: p.balance && last ? last.pr_id : '' });
    _pending.request = null;
}
async function tpdExpenseRequest(l) {
    const r = await Swal.fire({ title: 'Request payment', html: `<div class="text-start small">${esc(l.supplier)} · ${esc(l.description)}<br><b>${l.currency} ${money2(l.amount)}</b>${l.currency === 'USD' ? (_desk.rate.rate ? ` → LKR ${money2(l.amount * _desk.rate.rate)} at today's rate ${money2(_desk.rate.rate)} (locked to this request)` : ' — today\'s USD rate is not entered') : ''}</div><input id="sw_inv" class="swal2-input" placeholder="Supplier invoice number (optional)" value="${esc(l.invoice_no || '')}"><input id="sw_note" class="swal2-input" placeholder="Note (optional)">`, showCancelButton: true, confirmButtonText: 'Send request', preConfirm: () => ({ invoice_no: document.getElementById('sw_inv').value, note: document.getElementById('sw_note').value }) });
    if (!r.isConfirmed) return;
    const j = await postJson('expense_request', { expense_id: l.source_id, invoice_no: r.value.invoice_no, note: r.value.note });
    toast(j.status === 200, j.message); if (j.status === 200) reload();
}
async function tpdCancelRequest(key) {
    const l = lineByKey(key), tx = l && l.open_tx; if (!tx) return;
    const r = await Swal.fire({ title: 'Cancel this request?', input: 'text', inputLabel: 'Reason', showCancelButton: true, confirmButtonText: 'Cancel request', inputValidator: v => !v ? 'A reason is required' : null });
    if (!r.isConfirmed) return;
    const j = await postJson('cancel_request', { pr_id: tx.pr_id, reason: r.value });
    toast(j.status === 200, j.message); if (j.status === 200) reload();
}
function bankOptions(sel, noteEl) {
    const b = _desk.bank_accounts;
    sel.innerHTML = '<option value="">— choose —</option>' + b.accounts.map(a => `<option value="${b.source === 'master' ? a.id : a.ledger_code}" ${a.is_default ? 'selected' : ''}>${esc(a.label)}${a.account_no_masked ? ' · ' + esc(a.account_no_masked) : ''}${a.currency ? ' · ' + esc(a.currency) : ''}</option>`).join('');
    if (noteEl) noteEl.innerHTML = b.source === 'master' ? 'Accounts from the Avenir Bank Account Master (no free typing).' : '<span class="text-warning">The Avenir Bank Account Master is empty — the ledger\'s bank accounts are offered meanwhile. <a target="_blank" href="<?= base_url('master_files/bank_accounts') ?>">Add the company accounts</a>.</span>';
}
function bankField() { return _desk.bank_accounts.source === 'master' ? 'bank_account_id' : 'ledger_code'; }
function supplierBankHtml(sb, forApprove) {
    if (!sb) return '';
    const bad = sb.status !== 'verified';
    const st = sb.status === 'verified' ? '<span class="pill sm green">Verified</span>' : (sb.status === 'changed' ? '<span class="pill sm red">CHANGED since verification</span>' : '<span class="pill sm amber">Not verified</span>');
    return `<div class="border rounded p-2 ${bad ? 'border-danger' : ''}" style="font-size:12.5px;"><div class="fw-semibold">Supplier bank details ${st}</div><div>${esc(sb.beneficiary_name || sb.supplier_name)} · ${esc([sb.bank_name, sb.branch].filter(Boolean).join(' · ') || 'no bank on file')}${sb.account_no ? ' · A/C ' + esc(sb.account_no) : ''}${sb.swift ? ' · ' + esc(sb.swift) : ''}</div>${sb.verified_by ? `<div class="muted">verified by ${esc(sb.verified_by)} on ${dmyt(sb.verified_at)}</div>` : ''}${bad && forApprove ? '<div class="text-warning mt-1" style="color:#9a6a12 !important">Approval may proceed. The bank release waits until a Finance &amp; Approvals Management Approver — not the person who entered the details — verifies this exact account in the Supplier Bank Account Registry.</div>' : ''}</div>`;
}
function openApprove(key) {
    const l = lineByKey(key), tx = l && l.open_tx; if (!tx) return;
    _pending.approve = { key, tx, line: l };
    document.getElementById('ap_summary').innerHTML = `<div class="d-flex justify-content-between"><span>Supplier</span><b>${esc(l.supplier)}</b></div><div class="d-flex justify-content-between"><span>Service</span><span>${esc(l.category)}${l.service_date ? ' · ' + dmy(l.service_date) : ''}</span></div><div class="d-flex justify-content-between"><span>Requested</span><b>${tx.currency === 'USD' ? '$ ' + money2(tx.amount) + (tx.usd_rate ? ' × ' + money2(tx.usd_rate) + ' = LKR ' + money2(tx.lkr) : '') : 'LKR ' + money2(tx.amount)}</b></div><div class="d-flex justify-content-between"><span>Requested by</span><span>${esc(tx.requested_by || '')} · ${dmyt(tx.requested_at)}</span></div>${tx.invoice_no ? `<div class="d-flex justify-content-between"><span>Supplier invoice</span><span>${esc(tx.invoice_no)}</span></div>` : ''}${l.rate_label && l.currency === 'USD' ? `<div class="d-flex justify-content-between"><span>Hotel rate</span><span>${esc(l.rate_label)}</span></div>` : ''}`;
    document.getElementById('ap_bank_warn').innerHTML = l.pr_type !== 'Transport Advance' ? supplierBankHtml(l.supplier_bank, true) : '';
    // tf-paydesk-r2: with Supplier Payables the approval names no paying account (the batch does) and shows the approval-date rate
    const accum = !!_desk.accumulates;
    document.getElementById('ap_bank_wrap').style.display = accum ? 'none' : '';
    if (!accum) bankOptions(document.getElementById('ap_bank'), document.getElementById('ap_bank_note'));
    const apRate = tx.currency === 'USD' ? (_desk.rate.rate || tx.usd_rate) : null;
    document.getElementById('ap_rate').innerHTML = accum && tx.currency === 'USD' ? (apRate ? `<div class="d-flex justify-content-between"><span>Rate at approval (today's confirmed rate)</span><b class="num">${money2(apRate)}</b></div><div class="d-flex justify-content-between"><span>Provisional LKR</span><b class="num">${money2(tx.amount * apRate)}</b></div><div class="muted" style="font-size:11.5px;">These approval figures are kept as they are. The final LKR is calculated at the rate of the actual bank-payment date.</div>` : '<div class="text-danger small">Today\'s USD rate is not confirmed and this request carries no locked rate — confirm the rate first.</div>') : '';
    document.getElementById('ap_after').textContent = accum ? 'Your approval places this payment under its supplier in Supplier Payables. Management then creates the supplier\'s payment batch, which issues the voucher for the accountant. Nothing is marked paid here.' : 'Your approval creates the payment instruction and voucher and places it in Bank Payment Check for the accountant. It does not mark the payment as paid.';
    document.getElementById('ap_note').value = '';
    // tf-paydesk-r3: Pay Individually Now straight after the approval
    const pnw = document.getElementById('ap_paynow_wrap'); pnw.style.display = accum && TPD_PERMS.batch ? '' : 'none';
    document.getElementById('ap_paynow').checked = false; document.getElementById('ap_paynow_fields').style.display = 'none';
    document.getElementById('ap_bypass_reason').value = l.source_type === 'driver_advance' ? 'driver_advance' : ''; document.getElementById('ap_bypass_note').value = '';
    if (accum && TPD_PERMS.batch) bankOptions(document.getElementById('ap_paynow_bank'));
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}
async function submitApprove() {
    const p = _pending.approve; if (!p) return;
    const body = { pr_id: p.tx.pr_id, note: document.getElementById('ap_note').value };
    if (!_desk.accumulates) body[bankField()] = document.getElementById('ap_bank').value;
    const cb = document.getElementById('ap_confirm_bank');
    if (cb && cb.checked) body.confirm_bank = 1;
    const btn = document.getElementById('ap_submit'); btn.disabled = true;
    const payNow = document.getElementById('ap_paynow') && document.getElementById('ap_paynow').checked;
    if (payNow && !document.getElementById('ap_bypass_reason').value) { btn.disabled = false; return toast(false, 'Choose the reason the payment must not wait for a bulk batch.'); }
    const j = await postJson('approve', body); btn.disabled = false;
    if (j.status === 428) { toast(false, j.message); return; }
    if (j.status === 200) bootstrap.Modal.getInstance(document.getElementById('approveModal')).hide();
    if (j.status === 200 && payNow && j.awaiting_batch) {
        const b2 = { sup_acc: p.line.supplier_acc, pr_ids: [p.tx.pr_id], individual: 1, bypass_reason: document.getElementById('ap_bypass_reason').value, bypass_note: document.getElementById('ap_bypass_note').value };
        b2[bankField()] = document.getElementById('ap_paynow_bank').value;
        const j2 = await postJson('batch_create', b2);
        toast(j2.status === 200, (j.message || '') + (j2.status === 200 ? ' ' + j2.message : ' — but the individual batch was not created: ' + j2.message)); reload(); return;
    }
    toast(j.status === 200, j.message); if (j.status === 200) reload();
}
async function tpdReject(key) {
    const l = lineByKey(key), tx = l && l.open_tx; if (!tx) return;
    const r = await Swal.fire({ title: 'Reject this request?', input: 'textarea', inputLabel: 'Reason (kept in the audit record)', showCancelButton: true, confirmButtonText: 'Reject', inputValidator: v => !v ? 'A reason is required' : null });
    if (!r.isConfirmed) return;
    const j = await postJson('reject', { pr_id: tx.pr_id, reason: r.value }); toast(j.status === 200, j.message); if (j.status === 200) reload();
}
async function tpdIssueVoucher(key) {
    const l = lineByKey(key), tx = l && l.open_tx; if (!tx) return;
    const sel = document.createElement('select'); bankOptions(sel);
    const r = await Swal.fire({ title: 'Issue the voucher', html: `<div class="text-start small mb-2">${esc(l.supplier)} · LKR ${money2(tx.lkr || tx.amount)} — a bulk-payment supplier: the voucher is issued now.</div><label class="small">Avenir paying account</label>${sel.outerHTML.replace('<select', '<select id="sw_bank" class="form-select"')}`, showCancelButton: true, confirmButtonText: 'Issue voucher', preConfirm: () => document.getElementById('sw_bank').value || (Swal.showValidationMessage('Choose the account'), false) });
    if (!r.isConfirmed) return;
    const body = { 'pr_ids': [tx.pr_id] }; body[bankField()] = r.value;
    const j = await postJson('issue_voucher', body); toast(j.status === 200, j.message); if (j.status === 200) reload();
}
// tf-paydesk-r2: the supplier's approved payments waiting in Supplier Payables (any tour) → a batch → one voucher
let _batch = null;
async function openBatch(key) {
    const l = lineByKey(key), tx = l && l.open_tx; if (!tx) return;
    const j = await fetch(`${TPD_URL}batch_items?sup_acc=${encodeURIComponent(l.supplier_acc)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).catch(() => ({}));
    if (!j || j.status !== 200) return toast(false, (j && j.message) || 'Could not load the supplier\'s approved payments.');
    _batch = { key, sup: j.data, bank: j.bank_accounts, selected: {} };
    j.data.items.forEach(it => { _batch.selected[it.pr_id] = !it.possible_payment; });   // tf-paydesk-r7h: an item held in Supplier Payables is not offered
    const sb = j.data.bank;
    document.getElementById('bt_summary').innerHTML = `<div class="d-flex justify-content-between"><span>Supplier</span><b>${esc(j.data.supplier_name || l.supplier)}</b></div><div class="d-flex justify-content-between"><span>Approved payments waiting</span><b>${j.data.item_count} · ${j.data.tour_count} tour${j.data.tour_count === 1 ? '' : 's'}</b></div>${j.today_rate ? `<div class="d-flex justify-content-between"><span>Today's confirmed USD rate</span><span class="num">${money2(j.today_rate.rate)} (${esc(j.today_rate.by)})</span></div>` : ''}`;
    document.getElementById('bt_bank_warn').innerHTML = l.pr_type !== 'Transport Advance' ? supplierBankHtml(sb, true).replace('id="ap_confirm_bank"', 'id="bt_confirm_bank"').replace('the payment can be approved', 'the batch can be created') : '';
    const sel = document.getElementById('bt_bank'); const saved = _desk.bank_accounts; _desk.bank_accounts = j.bank_accounts || saved; bankOptions(sel, document.getElementById('bt_bank_note')); _desk.bank_accounts = saved;
    document.getElementById('bt_note').value = '';
    btRender();
    new bootstrap.Modal(document.getElementById('batchModal')).show();
}
function btRender() {
    const b = _batch; if (!b) return;
    document.getElementById('bt_rows').innerHTML = b.sup.items.map(it => `<tr class="${b.selected[it.pr_id] ? '' : 'text-muted'}"><td><input type="checkbox" ${b.selected[it.pr_id] ? 'checked' : ''} ${it.possible_payment ? 'disabled' : ''} onchange="btToggle(${it.pr_id}, this.checked)"></td><td><b>${esc(it.manual_id)}</b>${it.tour_cancelled ? ' <span class="pill sm red">tour cancelled</span>' : ''}${it.possible_payment ? ` <span class="pill sm red" title="${esc(it.possible_payment.basis)} — release it in Supplier Payables first">held — may be paid by PV ${it.possible_payment.vnums.join(', ')}</span>` : ''}</td><td>${esc(it.guest)}</td><td>${esc(it.kind)}${it.service_dates.length ? '<br><span class="muted">' + it.service_dates.map(dmy).join(', ') + '</span>' : ''}</td><td>${esc(it.invoice_no || '—')}</td><td class="text-end num">${it.currency === 'USD' ? money2(it.usd_amount) : '<span class="muted">LKR ' + money2(it.lkr_amount) + '</span>'}</td><td class="text-end num">${it.currency === 'USD' ? (it.approval_rate ? money2(it.approval_rate) : '<span class="text-danger">none</span>') : '—'}</td><td class="text-end num">${money2(it.approval_lkr || 0)}</td><td class="muted">${esc(it.approved_by || '')}<br>${dmyt(it.approved_at)}</td></tr>`).join('') || '<tr><td colspan="9" class="muted">Nothing approved is waiting for this supplier.</td></tr>';
    const sel = b.sup.items.filter(it => b.selected[it.pr_id]);
    document.getElementById('bt_usd').textContent = money2(sel.reduce((s, it) => s + (it.usd_amount || 0), 0));
    document.getElementById('bt_lkr').textContent = money2(sel.reduce((s, it) => s + (it.approval_lkr || 0), 0));
    document.getElementById('bt_count').textContent = `${sel.length} of ${b.sup.items.length}`;
    document.getElementById('bt_all').checked = sel.length === b.sup.items.length && sel.length > 0;
}
function btToggle(id, on) { if (_batch) { _batch.selected[id] = on; btRender(); } }
function btToggleAll(on) { if (_batch) { _batch.sup.items.forEach(it => { _batch.selected[it.pr_id] = on && !it.possible_payment; }); btRender(); } }
async function submitBatch() {
    const b = _batch; if (!b) return;
    const ids = b.sup.items.filter(it => b.selected[it.pr_id]).map(it => it.pr_id);
    if (!ids.length) return toast(false, 'Select at least one approved payment.');
    const body = { sup_acc: b.sup.sup_acc, pr_ids: ids, note: document.getElementById('bt_note').value };
    body[b.bank && b.bank.source === 'master' ? 'bank_account_id' : 'ledger_code'] = document.getElementById('bt_bank').value;
    const cb = document.getElementById('bt_confirm_bank'); if (cb && cb.checked) body.confirm_bank = 1;
    const btn = document.getElementById('bt_submit'); btn.disabled = true;
    const j = await postJson('batch_create', body); btn.disabled = false;
    if (j.status === 428) { toast(false, j.message); return; }
    if (j.status === 200) bootstrap.Modal.getInstance(document.getElementById('batchModal')).hide();
    toast(j.status === 200, j.message); if (j.status === 200) reload();
}
// tf-paydesk-r3: Pay Individually Now for an approved line — a single-item batch, the bypass reason recorded
let _payNow = null;
async function openPayNow(key) {
    const l = lineByKey(key), tx = l && l.open_tx; if (!tx) return;
    _payNow = { key, line: l, tx };
    document.getElementById('pn_summary').innerHTML = `<div class="d-flex justify-content-between"><span>Supplier</span><b>${esc(l.supplier)}</b></div><div class="d-flex justify-content-between"><span>Payment</span><b>${tx.currency === 'USD' ? '$ ' + money2(tx.amount) + (tx.approval_lkr ? ' (provisional LKR ' + money2(tx.approval_lkr) + ')' : '') : 'LKR ' + money2(tx.amount)}</b></div><div class="d-flex justify-content-between"><span>Approved</span><span>${esc(tx.accepted_by || '')} · ${dmyt(tx.accepted_at)}</span></div>`;
    document.getElementById('pn_bank_warn').innerHTML = l.pr_type !== 'Transport Advance' ? supplierBankHtml(l.supplier_bank, true).replace('id="ap_confirm_bank"', 'id="pn_confirm_bank"').replace('the payment can be approved', 'it can be paid') : '';
    bankOptions(document.getElementById('pn_bank'));
    document.getElementById('pn_reason').value = l.source_type === 'driver_advance' ? 'driver_advance' : (l.source_type === 'driver_settlement' ? 'driver_settlement' : ''); document.getElementById('pn_note').value = '';
    new bootstrap.Modal(document.getElementById('payNowModal')).show();
}
async function submitPayNow() {
    const p = _payNow; if (!p) return;
    const body = { sup_acc: p.line.supplier_acc, pr_ids: [p.tx.pr_id], individual: 1, bypass_reason: document.getElementById('pn_reason').value, bypass_note: document.getElementById('pn_note').value };
    body[bankField()] = document.getElementById('pn_bank').value;
    const cb = document.getElementById('pn_confirm_bank'); if (cb && cb.checked) body.confirm_bank = 1;
    const btn = document.getElementById('pn_submit'); btn.disabled = true;
    const j = await postJson('batch_create', body); btn.disabled = false;
    if (j.status === 428) { toast(false, j.message); return; }
    if (j.status === 200) bootstrap.Modal.getInstance(document.getElementById('payNowModal')).hide();
    toast(j.status === 200, j.message); if (j.status === 200) reload();
}
function openInitiate(key) {
    const l = lineByKey(key), tx = l && l.open_tx; if (!tx || !tx.vnum) return;
    if (tx.batch) { location.href = `${TPD_URL}batch/${tx.batch.id}`; return; }
    _pending.init = { key, tx };
    document.getElementById('in_summary').innerHTML = `<div class="d-flex justify-content-between"><span>Voucher</span><b><a href="${TPD_URL}voucher/${tx.vnum}" target="_blank">${tx.vnum}</a>${tx.voucher_no ? ' · No. ' + tx.voucher_no : ''}</b></div><div class="d-flex justify-content-between"><span>Payee</span><b>${esc(l.supplier)}</b></div><div class="d-flex justify-content-between"><span>Amount</span><b>LKR ${money2(tx.voucher_amount)}${tx.voucher_usd ? ' ($ ' + money2(tx.voucher_usd) + ' @ ' + money2(tx.usd_rate || 0) + ')' : ''}</b></div><div class="d-flex justify-content-between"><span>Approved</span><span>${esc(tx.accepted_by || '')} · ${dmyt(tx.accepted_at)}</span></div>${tx.invoice_no ? `<div class="d-flex justify-content-between"><span>Supplier invoice</span><span>${esc(tx.invoice_no)}</span></div>` : ''}`;
    document.getElementById('in_supplier_bank').innerHTML = supplierBankHtml(l.supplier_bank, false);
    bankOptions(document.getElementById('in_bank'), document.getElementById('in_bank_note'));
    const now = new Date(); now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('in_at').value = now.toISOString().slice(0, 16);
    document.getElementById('in_amount').value = money2(tx.voucher_amount); document.getElementById('in_charges').value = ''; document.getElementById('in_batch').value = ''; document.getElementById('in_ref').value = ''; document.getElementById('in_note').value = ''; document.getElementById('in_file').value = '';
    new bootstrap.Modal(document.getElementById('initModal')).show();
}
async function submitInitiate() {
    const p = _pending.init; if (!p) return;
    const fd = new FormData();
    fd.append('vnum', p.tx.vnum); fd.append(bankField(), document.getElementById('in_bank').value); fd.append('initiated_at', document.getElementById('in_at').value); fd.append('amount', document.getElementById('in_amount').value.replace(/,/g, ''));
    fd.append('currency', 'LKR'); fd.append('charges', document.getElementById('in_charges').value.replace(/,/g, '')); fd.append('batch_ref', document.getElementById('in_batch').value); fd.append('bank_ref', document.getElementById('in_ref').value); fd.append('note', document.getElementById('in_note').value);
    const f = document.getElementById('in_file').files[0]; if (f) fd.append('file', f);
    const btn = document.getElementById('in_submit'); btn.disabled = true;
    const j = await postJson('initiate', fd); btn.disabled = false;
    if (j.status === 200) bootstrap.Modal.getInstance(document.getElementById('initModal')).hide();
    toast(j.status === 200, j.message); if (j.status === 200) reload();
}
function openPaid(key) {
    const l = lineByKey(key), tx = l && l.open_tx; if (!tx || !tx.vnum) return;
    if (tx.batch) { location.href = `${TPD_URL}batch/${tx.batch.id}`; return; }
    _pending.paid = { key, tx };
    const i = tx.initiation;
    document.getElementById('pd_summary').innerHTML = `<div class="d-flex justify-content-between"><span>Voucher</span><b>${tx.vnum}</b></div><div class="d-flex justify-content-between"><span>Beneficiary</span><b>${esc(l.supplier)}</b></div><div class="d-flex justify-content-between"><span>Amount</span><b>LKR ${money2(tx.voucher_amount)}${tx.voucher_usd ? ' ($ ' + money2(tx.voucher_usd) + ' @ ' + money2(tx.usd_rate || 0) + ')' : ''}</b></div><div class="d-flex justify-content-between"><span>Paying account</span><span>${esc(i && i.label ? i.label : (tx.bank || ''))}</span></div>${i ? `<div class="d-flex justify-content-between"><span>Initiated</span><span>${dmyt(i.initiated_at)} · ${esc(i.initiated_by)}${i.batch_ref ? ' · batch ' + esc(i.batch_ref) : ''}</span></div>` : '<div class="text-warning">No bank initiation recorded yet — the accountant\'s step is missing.</div>'}${l.supplier_bank ? supplierBankHtml(l.supplier_bank, false) : ''}`;
    document.getElementById('pd_date').value = new Date().toISOString().slice(0, 10); document.getElementById('pd_date').max = new Date().toISOString().slice(0, 10);
    document.getElementById('pd_ref').value = i ? (i.bank_ref || i.batch_ref || '') : ''; document.getElementById('pd_note').value = '';
    new bootstrap.Modal(document.getElementById('paidModal')).show();
}
async function submitPaid() {
    const p = _pending.paid; if (!p) return;
    const btn = document.getElementById('pd_submit'); btn.disabled = true;
    const j = await postJson('paid', { vnum: p.tx.vnum, paid_date: document.getElementById('pd_date').value, bank_ref: document.getElementById('pd_ref').value, note: document.getElementById('pd_note').value }); btn.disabled = false;
    if (j.status === 200) bootstrap.Modal.getInstance(document.getElementById('paidModal')).hide();
    toast(j.status === 200, j.message); if (j.status === 200) reload();
}
async function openRemit(key, vnum) {
    const l = lineByKey(key); if (!l) return;
    const tx = vnum ? l.transactions.find(t => t.vnum === vnum) : l.transactions.filter(t => t.finalized && (!t.remittance || t.remittance.status !== 'sent')).slice(-1)[0];
    if (!tx) return toast(false, 'No paid voucher without an advice on this line.');
    const j = await postJson('remittance_prepare', { vnum: tx.vnum });
    if (j.status !== 200) return toast(false, j.message);
    const r = j.remittance; _pending.remit = { key, vnum: tx.vnum };
    document.getElementById('rm_summary').innerHTML = `<div class="d-flex justify-content-between"><span>Supplier</span><b>${esc(r.supplier)}</b></div><div class="d-flex justify-content-between"><span>Voucher</span><b>${r.vnum}</b> · LKR ${money2(tx.voucher_amount)}</div><div class="muted">One advice per supplier, per payment — nothing of another supplier is on it. Review the e-mail and the attachment, then send.</div>`;
    document.getElementById('rm_to').value = r.to || ''; document.getElementById('rm_to_note').textContent = 'Address from: ' + r.supplier_email_source; document.getElementById('rm_cc').value = ''; document.getElementById('rm_subject').value = r.subject || ''; document.getElementById('rm_body').innerHTML = r.body || '';
    document.getElementById('rm_attach').innerHTML = r.pdf ? `<i class="ri-attachment-2"></i> Attachment: <a href="${r.pdf}" target="_blank">Remittance-advice-${r.vnum}.pdf</a> · <a href="${r.preview}" target="_blank">preview as a page</a>` : `<span class="text-warning">The PDF library is not on this server — the advice goes as the e-mail text; <a href="${r.preview}" target="_blank">preview</a>.</span>`;
    new bootstrap.Modal(document.getElementById('remitModal')).show();
}
async function submitRemittance() {
    const p = _pending.remit; if (!p) return;
    const to = document.getElementById('rm_to').value.trim();
    const r = await Swal.fire({ title: 'Send the remittance advice?', html: `To <b>${esc(to)}</b>. It is sent once; the supplier is then marked Notified.`, icon: 'question', showCancelButton: true, confirmButtonText: 'Send' });
    if (!r.isConfirmed) return;
    const btn = document.getElementById('rm_send'); btn.disabled = true;
    const j = await postJsonBody('remittance_send', { vnum: p.vnum, to, cc: document.getElementById('rm_cc').value, subject: document.getElementById('rm_subject').value, body: document.getElementById('rm_body').innerHTML }); btn.disabled = false;
    if (j.status === 200) bootstrap.Modal.getInstance(document.getElementById('remitModal')).hide();
    toast(j.status === 200, j.message); reload();
}
function openExpenseModal() {
    ['ex_date', 'ex_sup_q', 'ex_sup_acc', 'ex_desc', 'ex_amount', 'ex_inv', 'ex_due', 'ex_note'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('ex_cur').value = 'LKR'; document.getElementById('ex_cat').value = 'Other'; document.getElementById('ex_date').value = _desk.tour.start_date || '';
    new bootstrap.Modal(document.getElementById('expenseModal')).show();
}
let _supTimer = null;
document.getElementById('ex_sup_q').addEventListener('input', function () {
    clearTimeout(_supTimer); document.getElementById('ex_sup_acc').value = '';
    const q = this.value.trim(); const list = document.getElementById('ex_sup_list');
    if (q.length < 2) { list.style.display = 'none'; return; }
    _supTimer = setTimeout(async () => {
        const j = await fetch(`${TPD_URL}suppliers?q=${encodeURIComponent(q)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).catch(() => ({}));
        const rows = (j && j.data) || [];
        list.innerHTML = rows.length ? rows.map(s => `<a href="#" class="list-group-item list-group-item-action py-1" onclick="pickSupplier('${esc(s.Sup_Acc_No)}', '${esc(String(s.Name).replace(/'/g, '&#39;'))}'); return false;"><b>${esc(s.Name)}</b> <span class="muted">${esc(s.Sup_Acc_No)}</span></a>`).join('') : '<div class="list-group-item muted py-1">No supplier found</div>';
        list.style.display = 'block';
    }, 250);
});
function pickSupplier(acc, name) { document.getElementById('ex_sup_acc').value = acc; document.getElementById('ex_sup_q').value = name.replace(/&#39;/g, "'"); document.getElementById('ex_sup_list').style.display = 'none'; }
async function submitExpense() {
    const j = await postJson('expense', { tour_id: _desk.tour.tour_id, service_date: document.getElementById('ex_date').value, category: document.getElementById('ex_cat').value, supplier_acc: document.getElementById('ex_sup_acc').value, supplier_name: document.getElementById('ex_sup_q').value, description: document.getElementById('ex_desc').value, currency: document.getElementById('ex_cur').value, amount: document.getElementById('ex_amount').value, invoice_no: document.getElementById('ex_inv').value, due_date: document.getElementById('ex_due').value, note: document.getElementById('ex_note').value });
    if (j.status === 200) bootstrap.Modal.getInstance(document.getElementById('expenseModal')).hide();
    toast(j.status === 200, j.message); if (j.status === 200) reload();
}
async function tpdCancelExpense(id) {
    const r = await Swal.fire({ title: 'Cancel this expense?', input: 'text', inputLabel: 'Reason', showCancelButton: true, confirmButtonText: 'Cancel expense', inputValidator: v => !v ? 'A reason is required' : null });
    if (!r.isConfirmed) return;
    const j = await postJson('expense_cancel', { id, reason: r.value }); toast(j.status === 200, j.message); if (j.status === 200) reload();
}
function openDoc(key) {
    const l = lineByKey(key); if (!l) return;
    _pending.doc = { key, line: l };
    document.getElementById('dc_summary').innerHTML = `<b>${esc(l.supplier)}</b> · ${esc(l.category)}${l.service_date ? ' · ' + dmy(l.service_date) : ''}`;
    document.getElementById('dc_inv').value = l.invoice_no || ''; document.getElementById('dc_ref').value = l.booking_ref || ''; document.getElementById('dc_file').value = '';
    document.getElementById('dc_list').innerHTML = (l.documents || []).length ? '<b>On file:</b><ul class="mb-0 ps-3">' + l.documents.map(x => `<li>${esc(x.title || x.kind)}${x.number ? ' · ' + esc(x.number) : ''}${x.file_path ? ` · <a href="${TPD_URL}file/${x.id}" target="_blank">open</a>` : ''}</li>`).join('') + '</ul>' : '';
    new bootstrap.Modal(document.getElementById('docModal')).show();
}
async function submitDoc() {
    const p = _pending.doc; if (!p) return;
    const inv = document.getElementById('dc_inv').value.trim(), ref = document.getElementById('dc_ref').value.trim(), f = document.getElementById('dc_file').files[0];
    const last = (p.line.transactions || []).slice(-1)[0];
    let ok = true, msg = '';
    if (inv || ref) { const j = await postJson('invoice', { tour_id: _desk.tour.tour_id, line_key: p.key, pr_id: last ? last.pr_id : '', invoice_no: inv, booking_ref: ref }); ok = ok && j.status === 200; msg = j.message; }
    if (f) { const fd = new FormData(); fd.append('tour_id', _desk.tour.tour_id); fd.append('line_key', p.key); fd.append('kind', document.getElementById('dc_kind').value); fd.append('number', document.getElementById('dc_kind').value === 'invoice' ? inv : ref); fd.append('file', f); if (last && last.vnum) fd.append('vnum', last.vnum); const j = await postJson('document', fd); ok = ok && j.status === 200; msg = j.message; }
    if (!inv && !ref && !f) return toast(false, 'Enter a number or attach a file.');
    if (ok) bootstrap.Modal.getInstance(document.getElementById('docModal')).hide();
    toast(ok, msg || 'Saved.'); if (ok) reload();
}
function openSupplierBank(key) {
    const l = lineByKey(key), sb = l && l.supplier_bank; if (!sb) return;
    _pending.sb = { key, acc: sb.sup_acc };
    document.getElementById('sb_body').innerHTML = supplierBankHtml(sb, false) + (sb.master ? `<div class="muted small mt-2">Supplier master: ${esc(sb.master.bank_name || '—')} ${esc(sb.master.branch || '')} · A/C ${esc(sb.master.account_no || '—')} · ${esc(sb.master.holder || '')}</div>` : '') + (sb.remittance_email ? `<div class="small mt-1">Remittance e-mail: ${esc(sb.remittance_email)}</div>` : '<div class="small text-warning mt-1">No remittance e-mail on file.</div>');
    document.getElementById('sb_master').href = '<?= base_url('master_files/supplier_bank') ?>?q=' + encodeURIComponent(sb.sup_acc);
    document.getElementById('sb_confirm').style.display = TPD_PERMS.verifier && sb.status !== 'verified' && sb.account_no ? '' : 'none';
    new bootstrap.Modal(document.getElementById('supBankModal')).show();
}
async function confirmSupplierBank() {
    const p = _pending.sb; if (!p) return;
    const r = await Swal.fire({ title: 'Verify these bank details?', input: 'text', inputLabel: 'How were they checked against the bank\'s document? (kept in the audit record)', showCancelButton: true, confirmButtonText: 'Verify', inputValidator: v => !v ? 'Say how they were checked' : null });
    if (!r.isConfirmed) return;
    const j = await postJson('confirm_supplier_bank', { sup_acc: p.acc, reason: r.value });
    if (j.status === 200) bootstrap.Modal.getInstance(document.getElementById('supBankModal')).hide();
    toast(j.status === 200, j.message); if (j.status === 200) reload();
}


// ==========================================
//  Upcoming tours list (as before)
// ==========================================
function _fmtDateInput(d) {
    // yields YYYY-MM-DD for <input type="date">
    return d.toISOString().slice(0, 10);
}

function initUpcomingDateDefaults() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const twoWeeks = new Date();
    twoWeeks.setDate(twoWeeks.getDate() + 14);
    document.getElementById('upcoming_from').value = _fmtDateInput(tomorrow);
    document.getElementById('upcoming_to').value   = _fmtDateInput(twoWeeks);
}

function loadUpcomingTours() {
    const from = document.getElementById('upcoming_from').value;
    const to   = document.getElementById('upcoming_to').value;

    document.getElementById('upcoming_loading_msg').style.display = 'block';
    document.getElementById('upcoming_empty_msg').style.display   = 'none';
    document.getElementById('upcoming_tours_body').innerHTML = '';

    const params = new URLSearchParams();
    if (from) params.set('from', from);
    if (to)   params.set('to', to);

    fetch(`<?= base_url('tour/tour_payment_dashboard/list_upcoming') ?>?${params.toString()}`)
        .then(r => r.json())
        .then(res => {
            document.getElementById('upcoming_loading_msg').style.display = 'none';
            if (res.status === 200) {
                renderUpcomingTours(res.data || []);
            } else {
                renderUpcomingTours([]);
            }
        })
        .catch(() => {
            document.getElementById('upcoming_loading_msg').style.display = 'none';
            renderUpcomingTours([]);
        });
}

// One small square per row in this category — e.g. 4 hotels renders 4 squares,
// each colored via the SAME statusClass() mapping the detail view's Status
// column already uses, so a square's color always means the same thing here
// as it does after clicking into the full detail. A hyphen shows for an empty
// category (no rows of that type on this tour) rather than a blank cell.
// Dedicated palette for the upcoming-tours squares — deliberately separate from
// statusClass() (used by the single-tour detail view's Status badges below).
// Red is reserved for the most severe case (nobody's even requested payment
// yet); Not Paid (a request exists, nothing's moved) is one step down at
// orange. Waiting Rate Confirmation gets its own color rather than sharing,
// since it's a genuinely different kind of blocker (needs a daily USD rate
// set before the request can even be made) from the others it might
// otherwise be lumped with.
const UPCOMING_STATUS_COLORS = {
    'Fully Paid':                '#22c55e',
    'Paid':                      '#22c55e',
    'Partially Paid':            '#f59e0b',
    'Pending Payment':           '#f59e0b',
    'Pending Approval':          '#8b5cf6',
    'Requested':                 '#8b5cf6',
    'Advance Paid':              '#3b82f6',
    'Payment on Process':        '#14b8a6',
    'Waiting Rate Confirmation': '#92400e',
    'Not Paid':                  '#f97316',
    'Payment Not Requested':     '#ef4444',
    'Cancelled':                 '#6b7280',
};

function _statusSquaresHtml(statuses) {
    if (!statuses || !statuses.length) {
        return '<span class="text-muted small">-</span>';
    }
    const squares = statuses.map(s => {
        const color = UPCOMING_STATUS_COLORS[s] || '#6b7280';
        return `<span class="tpd-status-square" style="background:${color};" title="${esc(s)}"></span>`;
    }).join('');
    return `<div class="tpd-status-squares">${squares}</div>`;
}

// Shows arrival/departure flight + time when set on the tour, matching the
// "CODE - TIME" convention already used elsewhere (reservation_card_print.php).
// Falls back to a dash when neither is available.
function _flightsHtml(t) {
    const lines = [];
    if (t.arrival_flight) {
        lines.push(`<div><i class="ri-flight-land-line text-muted me-1"></i>${esc(t.arrival_flight)}${t.arrival_flight_time ? ' - ' + esc(t.arrival_flight_time) : ''}</div>`);
    }
    if (t.departure_flight) {
        lines.push(`<div><i class="ri-flight-takeoff-line text-muted me-1"></i>${esc(t.departure_flight)}${t.departure_flight_time ? ' - ' + esc(t.departure_flight_time) : ''}</div>`);
    }
    return lines.length ? lines.join('') : '<span class="text-muted small">-</span>';
}

function renderUpcomingTours(tours) {
    const tbody = document.getElementById('upcoming_tours_body');
    tbody.innerHTML = '';

    if (!tours.length) {
        document.getElementById('upcoming_empty_msg').style.display = 'block';
        return;
    }
    document.getElementById('upcoming_empty_msg').style.display = 'none';

    tours.forEach(t => {
        const cats = t.categories || {};
        const tr = document.createElement('tr');
        tr.className = 'tpd-upcoming-row';
        tr.innerHTML = `
            <td class="fw-semibold text-primary">${esc(t.manual_id)}</td>
            <td>${esc(t.consignee)}</td>
            <td>${fmtDate(t.start_date)}</td>
            <td>${fmtDate(t.end_date)}</td>
            <td class="text-center">${t.passenger_count ?? '-'}</td>
            <td class="small">${_flightsHtml(t)}</td>
            <td class="text-center">${_statusSquaresHtml(cats.hotels)}</td>
            <td class="text-center">${_statusSquaresHtml(cats.activities)}</td>
            <td class="text-center">${_statusSquaresHtml(cats.driver_advance)}</td>
        `;
        tr.addEventListener('click', () => selectUpcomingTour(t.manual_id));
        tbody.appendChild(tr);
    });
}

function selectUpcomingTour(manualId) {
    document.getElementById('manual_id_input').value = manualId;
    document.getElementById('upcoming-tours-panel').style.display = 'none';
    searchTour();
}

function backToUpcomingSummary() {
    document.getElementById('tour-content').style.display   = 'none';
    document.getElementById('no-tour-msg').style.display    = 'none';
    document.getElementById('manual_id_input').value = '';
    document.getElementById('upcoming-tours-panel').style.display = 'block';
    // Refresh — the user may have just made a payment on the tour they're
    // leaving, so the list should reflect its updated status, not a stale one.
    loadUpcomingTours();
}

// ==========================================
//  Payment request · special rates · rate review (tf-offers-r1 / r3, as before)
// ==========================================
const tourAccountsBaseUrl = '<?= base_url('tour/tour_accounts/') ?>';

// ---- Hotel cost handoff (Hotel Rates panel → what we pay the hotel) ----
function _costBadgeCls(row) {
    if (row.cost_needs_check) return 'bg-danger';
    if (row.cost_offer && row.cost_offer.choice_needed) return 'bg-warning text-dark';
    if (row.cost_offer && (row.cost_offer.offers_used || []).length) return 'bg-success';
    if (row.cost_source === 'adhoc' || row.cost_source === 'mixed_adhoc') return 'bg-success';
    return 'bg-info text-dark';
}
function _hotelCostHtml(row, idx) {
    const plans = (row.cost_plans || []).join(', ');
    const nights = (row.cost_nights || []).map(n => `${n.date}: ${n.source === 'adhoc' ? 'ad hoc' : n.source === 'contracted' ? 'contract' : 'quoted'} $ ${fmtNum(n.pay_total)}${n.supplements > 0 ? ' (incl. supplements $ ' + fmtNum(n.supplements) + ')' : ''}`).join('\n');
    let html = `<div class="mt-1" style="font-size:.78rem; white-space:nowrap;" title="${esc(nights)}"><span class="text-muted">Hotel cost</span> <span class="fw-semibold">$ ${fmtNum(row.cost_dollar)}</span> <span class="badge ${_costBadgeCls(row)}">${esc(row.cost_source_label || '')}</span></div>`;
    if (row.cost_margin !== null && row.cost_margin !== undefined) {
        html += `<div class="${row.cost_margin < 0 ? 'text-danger' : 'text-muted'}" style="font-size:.72rem;">margin $ ${fmtNum(row.cost_margin)}${row.cost_supplements > 0 ? ' · supplements $ ' + fmtNum(row.cost_supplements) : ''}${plans ? ' · ' + esc(plans) : ''}</div>`;
    }
    if (row.cost_needs_check) {
        html += `<div class="text-danger" style="font-size:.72rem; white-space:normal; max-width:260px;"><i class="ri-error-warning-line"></i> ${esc((row.cost_check_notes || []).join(' '))}</div>`;
    }
    html += _offerCompactHtml(row, idx);
    return html;
}
// ---- tf-offers-r1: what the hotel is paid, beside the published rate, and the special rates to decide ----
function _offerFitting(o) { return (o.offer_options || []).filter(x => x.nights_fit > 0); }
// what a special rate saves or costs more: against the published rate, or — with no published rate on file — against
// the quoted figure the stay is paid at meanwhile (tf-offers-r3)
function _offerDiffTxt(o, row, x) {
    let d = null, against = '';
    if (x.saving !== null && x.saving !== undefined) { d = x.saving; }
    else if (row && row.total_dollar !== null && row.total_dollar !== undefined) { d = Math.round((row.total_dollar - x.total_if_used) * 100) / 100; against = ' against the quoted figure'; }
    if (d === null) return '';
    return (d >= 0 ? 'saves $ ' : 'costs more by $ ') + fmtNum(Math.abs(d)) + against;
}
function _offerCompactHtml(row, idx) {
    const o = row.cost_offer; if (!o) return '';
    let h = '';
    if (o.pub_total !== null && o.pub_total !== undefined && (o.offers_used || []).length) {
        h += `<div class="text-muted" style="font-size:.72rem;">Published $ ${fmtNum(o.pub_total)}${o.saving ? ` · <span class="${o.saving > 0 ? 'text-success' : 'text-danger'}">saving $ ${fmtNum(o.saving)}</span>` : ''}</div>`;
    }
    (o.offers_used || []).forEach(u => { h += `<div style="font-size:.72rem; color:#065f46; white-space:normal; max-width:260px;"><i class="ri-price-tag-3-line"></i> ${esc(OFFER_KIND[u.kind] || 'Offer')} applied: ${esc(u.name)}${u.nights < o.nights ? ` (${u.nights} of ${o.nights} nights)` : ''}</div>`; });
    const fitting = _offerFitting(o);
    if (o.choice_needed) {
        // tf-offers-r3: no special rate is proposed — the published rate stays until an authorized person selects one
        h += `<div class="text-warning fw-semibold" style="font-size:.72rem; white-space:normal; max-width:260px;"><i class="ri-flag-line"></i> ${fitting.length} special rate${fitting.length === 1 ? '' : 's'} available — published rate used until you decide</div>`;
    } else if (o.choice && o.choice.choice === 'published' && fitting.length) {
        h += `<div class="text-muted" style="font-size:.72rem;">Published rate kept by ${esc(o.choice.chosen_by)}</div>`;
    }
    if (o.choice && o.choice.choice === 'offer') {
        h += `<div class="text-muted" style="font-size:.7rem;">applied by ${esc(o.choice.chosen_by)} · ${esc(String(o.choice.chosen_at || '').slice(0, 16))}</div>`;
    }
    if (fitting.length && CAN_CHOOSE_OFFER && idx !== undefined) {
        h += `<a href="#" class="small" style="font-size:.72rem;" onclick="chooseOfferRate(${idx}); return false;"><i class="ri-exchange-line"></i> ${o.choice_needed ? 'Review special rate' : 'Change the payment rate'}</a>`;
    }
    return h;
}
// The payment step (tf-offers-r1): published rate, the special rate(s) available, the final amount to pay — the
// authorized user picks the rate; the pick fills the request amount, which stays theirs to confirm or correct.
function _offerCurrentPick(o) {
    // tf-offers-r3: the published rate is the default — a special rate is used only when an authorized person selects it
    // (an earlier decision on file is shown as it was made)
    if (o.choice) return o.choice.choice === 'published' ? 'published' : String(o.choice.contract_id);
    return 'published';
}
function _offerBlockHtml(row, idx) {
    const o = row.cost_offer; if (!o) return '';
    const fitting = _offerFitting(o);
    if (!fitting.length && !(o.offers_used || []).length) return '';   // no special rate: the payment goes ahead as before
    const pubTxt = o.pub_total !== null && o.pub_total !== undefined ? '$ ' + fmtNum(o.pub_total) : null;
    const pubAmount = o.pub_total !== null && o.pub_total !== undefined ? o.pub_total : (row.total_dollar ?? o.pay_total);
    const pick = _offerCurrentPick(o);
    const radio = (val, amount, title, sub) => `<label class="d-flex gap-2 align-items-start border rounded p-2 mb-1" style="cursor:pointer;"><input type="radio" name="pr_rate_pick" value="${val}" data-amount="${amount}" ${pick === val ? 'checked' : ''} class="mt-1" onchange="offerPickChanged()"><span class="flex-grow-1"><span class="d-flex justify-content-between gap-2"><b>${title}</b><b>$ ${fmtNum(amount)}</b></span><span class="text-muted" style="font-size:.72rem;">${sub}</span></span></label>`;
    let h = `<div class="border rounded p-2" style="background:#fff; font-size:.8rem;" id="pr_offer_block">`;
    h += `<div class="fw-semibold mb-1"><i class="ri-lock-line"></i> Special rate — confidential (authorized users only)</div>`;
    h += radio('published', pubAmount, 'Published contract rate', pubTxt ? 'the rate the quotation was built on' : 'no published rate on file — the quoted figure');
    fitting.forEach(x => {
        h += radio(String(x.plan_id), x.total_if_used, x.kind === 'early_bird' ? 'Available early bird rate' : 'Available tactical rate',
            `${esc(x.name)}${x.rate_code ? ' · ' + esc(x.rate_code) : ''} · ${x.nights_fit} of ${o.nights} night${o.nights === 1 ? '' : 's'}${_offerDiffTxt(o, row, x) ? ' · ' + _offerDiffTxt(o, row, x) : ''}${x.book_to ? ' · book by ' + esc(x.book_to) : ''}`
            + ` · <a href="#" class="offer-terms-link" data-plan="${x.plan_id}" onclick="return _offerTermsFromPanel(event, ${x.plan_id}, ${idx});">View offer</a>`);
    });
    h += `<div class="d-flex justify-content-between mt-1"><span class="fw-semibold">Final amount to pay the hotel</span><span class="fw-bold" id="pr_final_amt">-</span></div>`;
    h += `<div class="d-flex justify-content-between text-muted" style="font-size:.72rem;"><span>Internal margin (quoted $ ${fmtNum(row.total_dollar ?? 0)} − final)</span><span id="pr_final_margin">-</span></div>`;
    h += `<input type="text" id="pr_offer_note" class="form-control form-control-sm mt-1" placeholder="Note on the rate used (optional)">`;
    h += `<div class="text-muted mt-1" style="font-size:.72rem;">Changes only what the hotel is paid and our internal saving — never the client quotation or selling price.${o.choice ? ` Last decided by ${esc(o.choice.chosen_by)} on ${esc(String(o.choice.chosen_at || '').slice(0, 16))}.` : ''}</div>`;
    if ((o.history || []).length) {
        h += `<details class="mt-1"><summary style="font-size:.72rem;" class="text-muted">History (${o.history.length})</summary>${o.history.map(e => `<div style="font-size:.7rem;" class="text-muted">${esc(String(e.created_at).slice(0, 16))} · ${esc(e.by_user)} — ${esc(e.label || e.event)}${e.offer_name ? ': ' + esc(e.offer_name) : ''}${e.event === 'reviewed' ? '' : ' · paid $ ' + fmtNum(e.used_total) + (e.published_total !== null ? ' (published $ ' + fmtNum(e.published_total) + ')' : '')}${e.note ? ' — ' + esc(e.note) : ''}</div>`).join('')}</details>`;
    }
    return h + '</div>';
}
function offerPickChanged() {
    const sel = document.querySelector('input[name=pr_rate_pick]:checked'); if (!sel) return;
    const amount = parseFloat(sel.dataset.amount);
    document.getElementById('pr_amount').value = amount > 0 ? amount : '';
    document.getElementById('pr_final_amt').textContent = '$ ' + fmtNum(amount);
    const row = _tourData && _tourData.payment_rows[document.getElementById('pr_row_idx').value];
    const m = row ? (row.total_dollar ?? 0) - amount : null;
    const el = document.getElementById('pr_final_margin'); if (el && m !== null) { el.textContent = '$ ' + fmtNum(m); el.className = m < 0 ? 'text-danger' : ''; }
    updatePaymentRequestLkrPreview();
}
// The authorized user's review before a hotel payment with a special rate available: the notice, then the special
// rate itself; "Proceed to Payment" opens only once they have reviewed it.
const _offerReviewed = {};
// ---- tf-offers-r3: hotel payments held for the System Administrator's rate review ----
let _releaseMode = null;
async function loadRateReviewQueue() {
    const box = document.getElementById('rate_review_queue');
    if (!box || !CAN_CHOOSE_OFFER) return;
    let j;
    try { j = await (await fetch(OFFER_HOLDS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).json(); } catch (e) { j = null; }
    const list = (j && j.status === 200 && j.data) || [];
    if (!list.length) { box.style.display = 'none'; box.innerHTML = ''; return; }
    box.innerHTML = `<div class="card-body p-3"><div class="fw-semibold mb-2"><i class="ri-time-line me-1"></i> Hotel payments awaiting your rate review (${list.length})</div>`
        + `<div class="table-responsive"><table class="table table-sm align-middle mb-0" style="font-size:.85rem;"><thead><tr><th>Tour</th><th>Hotel</th><th>Prepared by</th><th>Prepared on</th><th class="text-end">Amount</th><th></th></tr></thead><tbody>`
        + list.map(h => `<tr class="rate-review-item"><td>${esc(h.manual_id || h.tour_id)}</td><td>${esc(h.hotel_name || h.hotel_acc)}</td><td>${esc(h.prepared_by)}</td><td>${esc(String(h.prepared_at || '').slice(0, 16))}</td><td class="text-end">$ ${fmtNum(h.request_amount)}</td>`
            + `<td class="text-end"><button class="btn btn-sm btn-outline-primary py-0" onclick="_openHeldTour('${esc(h.manual_id || '')}')">Open</button></td></tr>`).join('')
        + `</tbody></table></div><div class="text-muted small mt-1">Prepared by payment staff for stays with a tactical or early bird. Review the rate, then release each to Payment approval.</div></div>`;
    box.style.display = 'block';
}
function _openHeldTour(mid) {
    if (!mid) return;
    document.getElementById('manual_id_input').value = mid;
    searchTour();
}
// "Review & release": the notice and the review (special rates still undecided), then the payment panel in release mode
async function openRateReview(idx) {
    if (!_tourData) return;
    const row = _tourData.payment_rows[idx]; const rr = row && row.rate_review; const o = row && row.cost_offer;
    if (!rr || !rr.hold_id) return;
    if (o && o.choice_needed > 0 && _offerFitting(o).length) {
        const key = _tourData.tour_info.tour_id + '|' + o.hotel_acc;
        if (!_offerReviewed[key]) {
            if (!await _reviewSpecialRate(row)) return;
            _offerReviewed[key] = true;
        }
    }
    _releaseMode = { idx, hold: rr };
    _showPaymentRequestModal(idx);
    const info = document.getElementById('pr_release_info');
    info.innerHTML = `<div class="fw-semibold"><i class="ri-time-line"></i> Waiting for your rate review</div>`
        + `<div>Prepared by <b>${esc(rr.prepared_by)}</b> on ${esc(String(rr.prepared_at || '').slice(0, 16))} for <b>$ ${fmtNum(rr.amount)}</b>${rr.bank_name ? ' · ' + esc(rr.bank_name) : ''}${rr.sup_bank_acc ? ' ' + esc(rr.sup_bank_acc) : ''}</div>`
        + `<div class="text-muted">Choose the rate and confirm the final amount. "Release to Payment approval" makes the payment request for ${esc(rr.prepared_by)} at that amount.</div>`;
    info.style.display = 'block';
    // the published rate stays selected; the amount starts as prepared (within the quoted figure)
    if (rr.amount && document.querySelector('input[name=pr_rate_pick]:checked') && document.querySelector('input[name=pr_rate_pick]:checked').value === 'published') {
        document.getElementById('pr_amount').value = rr.amount;
        if (typeof updatePaymentRequestLkrPreview === 'function') updatePaymentRequestLkrPreview();
    }
    document.getElementById('pr_submit_btn').innerHTML = '<i class="ri-check-double-line me-1"></i> Release to Payment approval';
    document.getElementById('pr_return_btn').style.display = 'inline-block';
}
function _endReleaseMode() {
    _releaseMode = null;
    const info = document.getElementById('pr_release_info'); if (info) { info.style.display = 'none'; info.innerHTML = ''; }
    const rb = document.getElementById('pr_return_btn'); if (rb) rb.style.display = 'none';
    const sb = document.getElementById('pr_submit_btn'); if (sb) sb.innerHTML = '<i class="ri-send-plane-line me-1"></i> Send Request';
}
document.addEventListener('DOMContentLoaded', function () {
    const m = document.getElementById('paymentRequestModal');
    if (m) m.addEventListener('hidden.bs.modal', _endReleaseMode);
});
async function _releaseHeldPayment() {
    const rm = _releaseMode; if (!rm) return;
    const row = _tourData.payment_rows[rm.idx];
    let amount = parseFloat(String(document.getElementById('pr_amount').value).replace(/,/g, ''));
    if (isNaN(amount) || amount <= 0) { Swal.fire('Warning', 'Please enter a valid amount greater than zero.', 'warning'); return; }
    // the same ceiling as any payment request: the quoted figure or the hotel cost payable now (checked again by the server)
    const sel = document.querySelector('input[name=pr_rate_pick]:checked');
    const pick = sel ? sel.value : 'published';
    const ceiling = Math.max(row.total_dollar ?? row.total ?? 0, row.cost_dollar ?? 0);
    if (ceiling > 0 && amount > ceiling) { Swal.fire('Amount Exceeded!', `Request amount ($ ${fmtNum(amount)}) cannot exceed the Q Amount / hotel cost ($ ${fmtNum(ceiling)}).`, 'error'); return; }
    const body = { hold_id: rm.hold.hold_id, choice: pick, amount, note: (document.getElementById('pr_offer_note') || {}).value || '' };
    const btn = document.getElementById('pr_submit_btn'); btn.disabled = true;
    let j;
    try {
        const res = await fetch(OFFER_RELEASE_URL, { method: 'POST', headers: TF_CSRF_JSON, body: JSON.stringify(body) });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
    } catch (e) { j = { status: 0 }; }
    btn.disabled = false;
    if (j.status !== 200) { Swal.fire('Not released', j.message || 'The payment could not be released.', 'error'); return; }
    bootstrap.Modal.getInstance(document.getElementById('paymentRequestModal')).hide();
    await Swal.fire('Released', j.message || 'Released to Payment approval.', 'success');
    loadRateReviewQueue(); searchTour();
}
async function returnHeldPayment() {
    const rm = _releaseMode; if (!rm) return;
    const holdId = rm.hold.hold_id, who = rm.hold.prepared_by;
    bootstrap.Modal.getInstance(document.getElementById('paymentRequestModal')).hide();
    const r = await Swal.fire({ title: 'Return to staff', input: 'textarea', inputLabel: `Back to ${who} — nothing is requested. Why?`, inputPlaceholder: 'e.g. bank details to correct', showCancelButton: true, confirmButtonText: 'Return' });
    if (!r.isConfirmed) return;
    let j;
    try {
        const res = await fetch(OFFER_RETURN_URL, { method: 'POST', headers: TF_CSRF_JSON, body: JSON.stringify({ hold_id: holdId, note: r.value || '' }) });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
    } catch (e) { j = { status: 0 }; }
    if (j.status !== 200) { Swal.fire('Not returned', j.message || 'The payment could not be returned.', 'error'); return; }
    await Swal.fire('Returned', j.message || 'Returned to staff.', 'success');
    loadRateReviewQueue(); searchTour();
}
async function _reviewSpecialRate(row) {
    const o = row.cost_offer;
    const n = await Swal.fire({ icon: 'info', title: 'Special rate available', text: 'A Tactical / Early Bird Rate is available for this booking. Please review the special rate before proceeding with payment.',
        showCancelButton: true, confirmButtonText: 'Review the special rate', cancelButtonText: 'Not now' });
    if (!n.isConfirmed) return false;
    const stay = (o.nights_detail || []);
    const stayTxt = stay.length ? `${esc(stay[0].date)}${stay.length > 1 ? ' – ' + esc(stay[stay.length - 1].date) : ''} · ${o.nights} night${o.nights === 1 ? '' : 's'} · ${esc(stay[0].room_type || '')} ${esc(stay[0].meal_plan || '')}` : '';
    let ticked = false; const viewed = [];
    // tf-offers-r3: "View offer" opens the offer's terms and comes back to this review, the tick kept
    for (;;) {
        let viewId = null;
        window._offerViewFromReview = (id) => { viewId = id; Swal.close(); return false; };
        let html = `<div class="text-start" style="font-size:.85rem;">`;
        html += `<div class="text-muted small mb-2">${esc(row.supplier || '')} · ${stayTxt}<br><i class="ri-lock-line"></i> Confidential — authorized users only. It changes only what the hotel is paid, never the client quotation or selling price.</div>`;
        html += `<div class="d-flex justify-content-between border-bottom py-1"><span>Published contract rate${o.pub_total !== null && o.pub_total !== undefined ? '' : ' <span class="text-muted small">(none on file — the quoted figure is paid)</span>'}</span><b style="white-space:nowrap;">$ ${fmtNum(o.pub_total !== null && o.pub_total !== undefined ? o.pub_total : (row.total_dollar ?? o.pay_total))}</b></div>`;
        _offerFitting(o).forEach(x => {
            html += `<div class="border rounded p-2 mt-2"><div class="d-flex justify-content-between gap-2"><b>${esc(OFFER_KIND[x.kind] || 'Special rate')}: ${esc(x.name)}</b><b style="white-space:nowrap;">$ ${fmtNum(x.total_if_used)}</b></div>`
                + `<div class="small text-muted">${x.rate_code ? 'Reference ' + esc(x.rate_code) + ' · ' : ''}${x.nights_fit} of ${o.nights} night${o.nights === 1 ? '' : 's'} fit${_offerDiffTxt(o, row, x) ? ' · ' + _offerDiffTxt(o, row, x) : ''}${x.book_to ? ' · booking deadline ' + esc(x.book_to) : ''}</div>`
                + `<button type="button" class="btn btn-sm btn-outline-secondary mt-1 py-0 offer-view-btn" data-plan="${x.plan_id}" onclick="_offerViewFromReview(${x.plan_id})"><i class="ri-file-list-3-line"></i> View offer</button></div>`;
        });
        const notFit = (o.offer_options || []).filter(x => x.nights_fit === 0);
        if (notFit.length) html += `<div class="small text-muted mt-2">Not applicable: ${notFit.map(x => `${esc(x.name)}${x.why ? ' (' + esc(x.why) + ')' : ''}`).join('; ')}</div>`;
        html += `<div class="small mt-2">The published rate stays selected on the payment step. A special rate is used only if you select it there.</div>`;
        html += `<label class="d-flex gap-2 align-items-center mt-3"><input type="checkbox" id="offer_reviewed"${ticked ? ' checked' : ''}> <span>I have reviewed the special rate</span></label></div>`;
        const r = await Swal.fire({ title: 'Review the special rate', html, width: 640, showCancelButton: true, confirmButtonText: 'Proceed to Payment', cancelButtonText: 'Cancel',
            didOpen: () => { const b = Swal.getConfirmButton(); b.disabled = !ticked; document.getElementById('offer_reviewed').addEventListener('change', e => { ticked = e.target.checked; b.disabled = !ticked; }); },
            preConfirm: () => document.getElementById('offer_reviewed').checked || (Swal.showValidationMessage('Tick "I have reviewed the special rate" first'), false) });
        if (viewId) {
            const x = _offerFitting(o).find(f => f.plan_id === viewId);
            if (x && !viewed.includes(x.name)) viewed.push(x.name);
            await _showOfferTerms(viewId, row, 'Back to the review');
            continue;
        }
        if (!r.isConfirmed) return false;
        return await _recordOfferReview(row, viewed);
    }
}
// tf-offers-r3: the review is kept with who and when (whatever is decided on the payment step); if it cannot be
// recorded, the payment does not open
async function _recordOfferReview(row, viewed) {
    const o = row.cost_offer;
    let j;
    try {
        const res = await fetch(OFFER_REVIEWED_URL, { method: 'POST', headers: TF_CSRF_JSON, body: JSON.stringify({ tour_id: _tourData.tour_info.tour_id, hotel_acc: o.hotel_acc, viewed: viewed || [] }) });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
    } catch (e) { j = { status: 0 }; }
    if (j.status !== 200) { await Swal.fire('Review not recorded', (j.message || 'The review could not be recorded.') + ' The payment was not opened — try again.', 'error'); return false; }
    if (j.data) { o.history = [j.data].concat(o.history || []); }
    return true;
}
// The terms of one tactical / early bird (tf-offers-r3): stay and booking windows, markets, minimum stay, the rates
// (the stay's room and meal plan marked), supplements, restrictions and conditions. "Back" returns where it was opened.
async function _showOfferTerms(planId, row, backLabel) {
    let j;
    try {
        const res = await fetch(OFFER_TERMS_URL + encodeURIComponent(planId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
    } catch (e) { j = { status: 0 }; }
    if (j.status !== 200 || !j.data) { await Swal.fire({ icon: 'error', title: 'Offer terms', text: j.message || 'The offer terms could not be loaded.', confirmButtonText: backLabel || 'Back' }); return; }
    await Swal.fire({ title: esc(j.data.kind_label || 'Special rate'), html: _offerTermsHtml(j.data, row), width: 820, confirmButtonText: backLabel || 'Back to the payment', customClass: { popup: 'offer-terms-popup' } });
}
function _offerTermsFromPanel(ev, planId, idx) {
    if (ev) { ev.preventDefault(); ev.stopPropagation(); }
    const row = _tourData && _tourData.payment_rows[idx];
    _showOfferTerms(planId, row, 'Back to the payment');
    return false;
}
function _offerTermsHtml(t, row) {
    const o = row && row.cost_offer; const stay = (o && o.nights_detail) || [];
    const norm = v => String(v || '').toLowerCase().replace(/\s+room$/, '').trim();
    const isStay = r => stay.some(n => norm(n.room_type) === norm(r.room_type) && String(n.meal_plan || '').toUpperCase() === String(r.meal_plan || '').toUpperCase());
    const d = v => v ? esc(v) : '—';
    const money = v => v === null || v === undefined || v === '' ? '—' : fmtNum(v);
    const STATUS = { active: 'Active', deadline_soon: 'Booking deadline soon', booking_closed: 'Booking closed', expired: 'Expired', withdrawn: 'Withdrawn', draft: 'Draft', upcoming: 'Booking opens later' };
    let h = `<div class="text-start" style="font-size:.82rem;">`;
    h += `<div class="fw-semibold mb-1" style="font-size:.95rem;">${esc(t.name)}</div>`;
    h += `<div class="text-muted small mb-2">${esc((t.hotel && t.hotel.name) || '')}${t.rate_code ? ' · Reference ' + esc(t.rate_code) : ''} · ${esc(STATUS[t.offer_status] || t.offer_status || '')}<br><i class="ri-lock-line"></i> Confidential — authorized users only.</div>`;
    const kv = (k, v) => `<div class="d-flex gap-2 border-bottom py-1"><span class="text-muted" style="min-width:150px;">${k}</span><span>${v}</span></div>`;
    h += kv('Stay dates', `${d(t.valid_from)} – ${d(t.valid_to)}`);
    h += kv('Booking window', t.book_from || t.book_to ? `${d(t.book_from)} – ${d(t.book_to)}` : 'any booking date');
    h += kv('Markets', t.markets && t.markets.length ? t.markets.map(esc).join(', ') : 'all markets');
    h += kv('Minimum stay', t.min_stay ? `${t.min_stay} night${t.min_stay === 1 ? '' : 's'}` : 'none');
    h += kv('Prices', `${esc(t.currency || '')} ${t.pricing_mode === 'per_person' ? 'per person' : 'per room'} per night${t.taxes_included === false ? ', taxes extra' : (t.taxes_included ? ', taxes included' : '')}`);
    // what this offer gives THIS stay, night by night, as the payment works it out
    const mine = ((row && row.cost_nights) || []).map(n => ({ n, op: (n.options || []).find(x => Number(x.plan_id) === Number(t.id)) })).filter(x => x.op);
    if (mine.length) {
        h += `<div class="mt-2 fw-semibold">For this stay</div><div style="overflow-x:auto;"><table class="table table-sm mb-1 offer-terms-thisstay" style="font-size:.78rem;"><thead><tr><th>Night</th><th>Room · meal plan</th><th class="text-end">Single / Double / Triple</th><th class="text-end">Night total</th><th></th></tr></thead><tbody>`;
        let tot = 0;
        mine.forEach(({ n, op }) => {
            if (op.ok) tot += Number(op.total || 0);
            h += `<tr><td>${esc(n.date)}</td><td>${esc(n.room_type || '')} ${esc(n.meal_plan || '')}</td><td class="text-end">${(op.rates || []).map(money).join(' / ')}</td><td class="text-end">${op.ok ? money(op.total) : '—'}</td><td class="small text-muted">${op.ok ? '' : esc(op.why || 'does not apply')}</td></tr>`;
        });
        h += `</tbody><tfoot><tr><th colspan="3">Total for the nights it fits</th><th class="text-end">${money(tot)}</th><th></th></tr></tfoot></table></div>`;
    }
    (t.seasons || []).forEach(se => {
        h += `<div class="mt-2 fw-semibold">${esc(se.name || 'Season')} <span class="text-muted fw-normal small">${(se.dates || []).map(x => d(x.from) + ' – ' + d(x.to)).join(', ')}${se.min_stay ? ' · minimum ' + se.min_stay + ' nights' : ''}</span></div>`;
        h += `<div style="overflow-x:auto;"><table class="table table-sm mb-1" style="font-size:.78rem;"><thead><tr><th>Room</th><th>Meal plan</th><th class="text-end">Single</th><th class="text-end">Double</th><th class="text-end">Triple</th></tr></thead><tbody>`;
        (se.rates || []).forEach(r => {
            const mine = isStay(r);
            h += `<tr${mine ? ' class="table-warning offer-terms-stay"' : ''}><td>${esc(r.room_type)}${mine ? ' <span class="badge bg-warning text-dark">this stay</span>' : ''}</td><td>${esc(r.meal_plan)}</td><td class="text-end">${money(r.sgl)}</td><td class="text-end">${money(r.dbl)}</td><td class="text-end">${money(r.tpl)}</td></tr>`;
        });
        h += `</tbody></table></div>`;
    });
    if ((t.room_supplements || []).length) {
        h += `<div class="mt-2 fw-semibold">Room supplements</div>` + t.room_supplements.map(x => { const me = stay.some(n => norm(n.room_type) === norm(x.room_type)); return `<div class="small${me ? ' offer-terms-stay-room' : ''}">${esc(x.room_type)} + ${fmtNum(x.amount)}${x.per ? ' per ' + esc(x.per) : ''}${x.season ? ' (' + esc(x.season) + ')' : ''}${me ? ' <span class="badge bg-warning text-dark">this stay</span>' : ''}${x.note ? ' — ' + esc(x.note) : ''}</div>`; }).join('');
    }
    if ((t.meal_deltas || []).length) {
        h += `<div class="mt-2 fw-semibold">Meal plan supplements</div>` + t.meal_deltas.map(x => `<div class="small">${esc(x.from)} → ${esc(x.to)} + ${fmtNum(x.adult)} per adult${x.child !== null && x.child !== undefined ? ', ' + fmtNum(x.child) + ' per child' : ''}${x.season ? ' (' + esc(x.season) + ')' : ''}${x.note ? ' — ' + esc(x.note) : ''}</div>`).join('');
    }
    if ((t.supplements || []).length) {
        h += `<div class="mt-2 fw-semibold">Supplements</div>` + t.supplements.map(x => `<div class="small">${esc(x.name)}${x.compulsory ? ' <b>(compulsory)</b>' : ''}${x.date_from ? ' · ' + d(x.date_from) + (x.date_to && x.date_to !== x.date_from ? ' – ' + d(x.date_to) : '') : ''}${x.amount !== null && x.amount !== undefined ? ' · ' + fmtNum(x.amount) + (x.per ? ' per ' + esc(x.per) : '') : ''}${x.note ? ' — ' + esc(x.note) : ''}</div>`).join('');
    }
    if ((t.restrictions || []).length) {
        h += `<div class="mt-2 fw-semibold">Restrictions</div>` + t.restrictions.map(x => `<div class="small">${esc(String(x.kind || '').replace(/_/g, ' '))}${x.date_from ? ' · ' + d(x.date_from) + (x.date_to && x.date_to !== x.date_from ? ' – ' + d(x.date_to) : '') : ''}${x.value ? ' · ' + esc(x.value) : ''}${x.note ? ' — ' + esc(x.note) : ''}</div>`).join('');
    }
    (t.conditions || []).forEach(c => { h += `<div class="mt-2 fw-semibold">${esc(c.label)}</div><div class="small" style="white-space:pre-wrap;">${esc(c.text)}</div>`; });
    h += `<div class="text-muted small mt-2">${t.source_file ? 'Source: ' + esc(t.source_file) + ' · ' : ''}${t.approved_by ? 'approved by ' + esc(t.approved_by) + (t.approved_at ? ' on ' + esc(String(t.approved_at).slice(0, 10)) : '') : ''}</div>`;
    return h + '</div>';
}
async function openPaymentRequestModal(rowIdx) {
    if (!_tourData) return;
    const row = _tourData.payment_rows[rowIdx];
    const o = row && row.category === 'Hotel' ? row.cost_offer : null;
    // a special rate nobody has decided on yet: the notice and the review come first (authorized users only get cost_offer)
    if (o && CAN_CHOOSE_OFFER && o.choice_needed > 0 && _offerFitting(o).length) {
        const key = _tourData.tour_info.tour_id + '|' + o.hotel_acc;
        if (!_offerReviewed[key]) {
            if (!await _reviewSpecialRate(row)) return;
            _offerReviewed[key] = true;
        }
    }
    _showPaymentRequestModal(rowIdx);
}
// saves the rate picked on the payment step as the decision for the stay (who and when kept), before the request
async function _saveOfferPick(row) {
    const o = row.cost_offer; const sel = document.querySelector('input[name=pr_rate_pick]:checked');
    if (!o || !sel) return true;
    const pick = sel.value;
    const current = o.choice ? (o.choice.choice === 'published' ? 'published' : String(o.choice.contract_id)) : null;
    if (pick === current) return true;
    let j;
    try {
        const res = await fetch(OFFER_CHOOSE_URL, { method: 'POST', headers: TF_CSRF_JSON, body: JSON.stringify({ tour_id: _tourData.tour_info.tour_id, hotel_acc: o.hotel_acc, choice: pick, note: (document.getElementById('pr_offer_note') || {}).value || '' }) });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
    } catch (e) { j = { status: 0 }; }
    if (j.status !== 200) { Swal.fire('Not saved', j.message || 'The rate decision could not be saved — the payment request was not sent.', 'error'); return false; }
    return true;
}
async function chooseOfferRate(idx) {
    if (!_tourData) return;
    const row = _tourData.payment_rows[idx]; const o = row && row.cost_offer; if (!o) return;
    const prm = document.getElementById('paymentRequestModal'); const inst = prm && window.bootstrap && bootstrap.Modal.getInstance(prm); if (inst) inst.hide();
    // preselected: the decision on file; with none, the published rate (tf-offers-r3: no special rate is ever preselected)
    const cur = o.choice ? (o.choice.choice === 'published' ? 'published' : String(o.choice.contract_id)) : 'published';   // tf-offers-r3: published by default
    const opt = (val, title, sub, disabled) => `<label class="d-flex gap-2 align-items-start border rounded p-2 mb-1 text-start" style="cursor:${disabled ? 'default' : 'pointer'}; font-size:.85rem;${disabled ? ' opacity:.55;' : ''}"><input type="radio" name="offer_pick" value="${val}" ${cur === val && !disabled ? 'checked' : ''} ${disabled ? 'disabled' : ''} class="mt-1"><span><b>${title}</b><br><span class="small text-muted">${sub}</span></span></label>`;
    let html = `<div class="text-start small text-muted mb-2">${esc(row.supplier || '')} · ${o.nights} night${o.nights === 1 ? '' : 's'}. Only what the hotel is paid — the customer quotation does not change.</div>`;
    html += opt('published', `Published / contract rate — $ ${o.pub_total !== null && o.pub_total !== undefined ? fmtNum(o.pub_total) : fmtNum(o.pay_total) + ' (quoted figure — no published rate on file)'}`, 'keep the rate the quotation was built on');
    (o.offer_options || []).forEach(x => {
        const fits = x.nights_fit > 0;
        html += opt(String(x.plan_id), `Apply ${esc(OFFER_KIND[x.kind] || 'Offer')}: ${esc(x.name)}${fits ? ' — $ ' + fmtNum(x.total_if_used) : ''}`,
            fits ? `${x.nights_fit} of ${o.nights} night${o.nights === 1 ? '' : 's'} fit${x.saving !== null && x.saving !== undefined ? (x.saving >= 0 ? ' · saves $ ' : ' · costs more by $ ') + fmtNum(Math.abs(x.saving)) : ''}${x.rate_code ? ' · ' + esc(x.rate_code) : ''}${x.book_to ? ' · book by ' + esc(x.book_to) : ''}`
                 : `does not fit this stay${x.why ? ': ' + esc(x.why) : ''}`, !fits);
    });
    html += `<textarea id="offer_note" class="form-control form-control-sm mt-2" rows="2" placeholder="Note (optional) — why this rate"></textarea>`;
    const r = await Swal.fire({ title: 'Rate for the hotel payment', html, width: 680, showCancelButton: true, confirmButtonText: 'Use this rate',
        preConfirm: () => { const v = document.querySelector('input[name=offer_pick]:checked'); if (!v) { Swal.showValidationMessage('Pick a rate'); return false; } return { choice: v.value, note: (document.getElementById('offer_note') || {}).value || '' }; } });
    if (!r.isConfirmed || !r.value) return;
    let j;
    try {
        const res = await fetch(OFFER_CHOOSE_URL, { method: 'POST', headers: TF_CSRF_JSON, body: JSON.stringify({ tour_id: _tourData.tour_info.tour_id, hotel_acc: o.hotel_acc, choice: r.value.choice, note: r.value.note }) });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
        if ((res.status === 403 || j.status === 403) && !j.csrf) { j = { status: 403, message: 'Your user group does not hold "Tactical / Early Bird rate selection".' }; }
    } catch (e) { j = { status: 0, message: 'Could not save the decision.' }; }
    if (j.status === 200) { Swal.fire({ icon: 'success', text: j.message, timer: 2400, showConfirmButton: false }); searchTour(); }
    else { Swal.fire('Not saved', j.message || 'Could not save the decision.', 'error'); }
}
async function recalcHotelCosts() {
    if (!_tourData) return;
    const fd = new FormData(); fd.append('tour_id', _tourData.tour_info.tour_id); fd.append(TF_CSRF_NAME, TF_CSRF_HASH);
    try {
        const r = await fetch('<?= base_url('tour/tour_payment_dashboard/recalc_hotel_cost') ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const j = await r.json();
        if (j.status === 200) { Swal.fire({ icon: 'success', text: j.message, timer: 1800, showConfirmButton: false }); searchTour(); } else { Swal.fire('Error', j.message || 'Could not recalculate', 'error'); }
    } catch (e) { Swal.fire('Error', 'Could not recalculate the hotel costs', 'error'); }
}

// The line under the supplier's name on a driver's / guide's row: the nickname and the category from the master
function _payeeLineHtml(row) {
    const p = row.payee;
    if (!p) return '';
    const bits = [];
    if (p.nickname) bits.push('“' + esc(p.nickname) + '”');
    if (p.category) bits.push('<span class="badge bg-primary">' + esc(p.category) + '</span>');
    if (p.status !== 'active' || p.archived) bits.push('<span class="badge bg-danger">' + (p.archived ? 'archived' : 'inactive') + '</span>');
    return bits.length ? '<div class="text-muted" style="font-size:.72rem;">' + bits.join(' · ') + '</div>' : '';
}
function _payeeBlockHtml(row) {
    const p = row.payee;
    if (!p) return '';
    const tour = _tourData && _tourData.tour_info ? (_tourData.tour_info.manual_id || _tourData.tour_info.tour_id || '') : '';
    const amount = 'LKR ' + fmtNum(row.total ?? 0);
    const mismatch = p.bank_visible && p.bank_account_no && row.supplier_bank_acc && String(p.bank_account_no).replace(/\s/g, '') !== String(row.supplier_bank_acc).replace(/\s/g, '');
    let h = `<div class="fw-semibold"><i class="ri-user-line"></i> Who is paid</div>`
        + `<div><b>${esc(p.name)}</b>${p.nickname ? ` · known as <b>“${esc(p.nickname)}”</b>` : ''}${p.category ? ` · <span class="badge bg-primary">${esc(p.category)}</span>${p.category_confirmed ? '' : ' <span class="badge bg-warning text-dark">category to confirm</span>'}` : ''}${p.status !== 'active' || p.archived ? ' <span class="badge bg-danger">' + (p.archived ? 'archived' : 'inactive') + ' on the master</span>' : ''}</div>`
        + `<div class="text-muted">Tour ${esc(tour)} · ${esc(row.category || '')} · amount ${amount}</div>`;
    if (p.bank_visible) {
        h += p.bank_account_no
            ? `<div>${[`Bank account name <b>${esc(p.bank_account_name || '(not on file)')}</b>`, [p.bank_name, p.bank_branch].filter(Boolean).map(esc).join(' '), `A/C <span class="font-monospace">${esc(p.bank_account_no)}</span>`].filter(Boolean).join(' · ')} ${p.bank_verified ? '<span class="badge bg-success">verified' + (p.bank_verified_at ? ' ' + esc(p.bank_verified_at) : '') + '</span>' : '<span class="badge bg-warning text-dark">not verified</span>'}</div>`
            : '<div class="text-warning">No bank account on the Driver &amp; Guide Master.</div>';
        if (mismatch) h += `<div class="text-danger">The account on the master (${esc(p.bank_account_no)}) differs from the supplier record (${esc(row.supplier_bank_acc)}) — check before paying.</div>`;
        if (p.payment_remarks) h += `<div class="text-muted">${esc(p.payment_remarks)}</div>`;
    }
    const w = (p.warnings || []).filter(x => x.level !== 'info');
    if (w.length) h += `<div class="text-warning">${w.map(x => esc(x.text)).join(' ')}</div>`;
    return h;
}
function _showPaymentRequestModal(rowIdx) {
    if (!_tourData) return;
    const row = _tourData.payment_rows[rowIdx];
    const isHotel = row.category === 'Hotel';
    // Hotel requests are made in $ (matching tour_accounts' Q Amount), everything
    // else is requested in LKR. A hotel request defaults to what the Hotel Rates
    // panel says we PAY (ad hoc → contract rate), not the customer's selling price.
    const hasCost = isHotel && row.cost_dollar !== null && row.cost_dollar !== undefined && row.cost_dollar > 0;
    const displayAmount = isHotel ? (hasCost ? row.cost_dollar : (row.total_dollar ?? 0)) : row.total;
    document.getElementById('pr_cost_row').style.display = hasCost ? 'flex' : 'none';
    document.getElementById('pr_cost_detail').style.display = hasCost ? 'block' : 'none';
    if (hasCost) {
        document.getElementById('pr_cost_amt').textContent = '$ ' + fmtNum(row.cost_dollar);
        const badge = document.getElementById('pr_cost_badge'); badge.className = 'badge ' + _costBadgeCls(row); badge.textContent = row.cost_source_label || '';
        document.getElementById('pr_cost_detail').innerHTML = `Quoted to the customer $ ${fmtNum(row.total_dollar ?? 0)} · margin $ ${fmtNum(row.cost_margin ?? 0)}${(row.cost_plans || []).length ? ' · ' + esc(row.cost_plans.join(', ')) : ''}${row.cost_needs_check ? '<br><span class="text-danger">' + esc((row.cost_check_notes || []).join(' ')) + '</span>' : ''}`;
    }

    const offerBox = document.getElementById('pr_offer_box');
    offerBox.innerHTML = isHotel && row.cost_offer ? _offerBlockHtml(row, rowIdx) : '';
    offerBox.style.display = offerBox.innerHTML ? 'block' : 'none';
    // tf-crew-master-r1: a driver's / guide's payment names who is paid — nickname and legal name side by side, the
    // category, and for the holders of "Driver & Guide bank details" the account name as the bank has it, the bank, the
    // account number — with the tour and the amount, so nobody pays the wrong person
    const payeeBox = document.getElementById('pr_payee_box');
    payeeBox.innerHTML = !isHotel ? _payeeBlockHtml(row) : '';
    payeeBox.style.display = payeeBox.innerHTML ? 'block' : 'none';

    document.getElementById('pr_row_idx').value = rowIdx;
    document.getElementById('pr_supplier_name').textContent = row.supplier || '-';
    document.getElementById('pr_category_lbl').textContent  = row.category || '-';
    // Total Amount stays the quoted figure (Q Amount); the request itself defaults to the payable cost
    document.getElementById('pr_total_amt').textContent     = (isHotel ? '$ ' : 'LKR ') + fmtNum(isHotel ? (row.total_dollar ?? 0) : row.total);
    document.getElementById('pr_amount_label').textContent  = (isHotel ? 'Request Amount ($) *' : 'Request Amount (LKR) *');
    document.getElementById('pr_amount').value = displayAmount > 0 ? displayAmount : '';
    if (document.querySelector('input[name=pr_rate_pick]')) offerPickChanged();   // tf-offers-r1: the rate picked sets the final amount

    // Today's USD rate — only relevant for Hotel, which posts in $ but is
    // converted to LKR server-side (payment_request()) at this same rate.
    // Force a fresh fetch here (not the cached value) so an edit/delete made
    // on the Daily USD Rate page is reflected right before the user submits.
    document.getElementById('pr_rate_row').style.display = isHotel ? 'flex' : 'none';
    document.getElementById('pr_lkr_row').style.display  = isHotel ? 'flex' : 'none';
    if (isHotel) {
        document.getElementById('pr_usd_rate').textContent = 'Refreshing...';
        loadUsdRate(true);
    }
    refreshPaymentRequestRateUi(isHotel);

    const modal = new bootstrap.Modal(document.getElementById('paymentRequestModal'));
    modal.show();
}

// Re-applies the current _todayUsdRate to the modal's rate row/missing-alert/
// LKR preview — called right after a (forced) loadUsdRate() resolves so the
// modal reflects the latest rate even if it was already open.
function refreshPaymentRequestRateUi(isHotel) {
    document.getElementById('pr_rate_missing_alert').style.display = (isHotel && !_todayUsdRate) ? 'block' : 'none';
    if (isHotel && _todayUsdRate) {
        document.getElementById('pr_usd_rate').textContent = '1 $ = LKR ' + fmtRate(_todayUsdRate);
    } else if (isHotel) {
        document.getElementById('pr_usd_rate').textContent = 'Not set';
    }
    updatePaymentRequestLkrPreview();
}

// Live LKR preview as the $ amount is edited — same formula payment_request()
// applies server-side (requestAmount * today's USD rate).
function updatePaymentRequestLkrPreview() {
    const rowIdx = document.getElementById('pr_row_idx').value;
    if (rowIdx === '' || !_tourData) return;
    const row = _tourData.payment_rows[rowIdx];
    if (row.category !== 'Hotel' || !_todayUsdRate) return;

    const amt = parseFloat(String(document.getElementById('pr_amount').value).replace(/,/g, ''));
    document.getElementById('pr_lkr_amt').textContent = !isNaN(amt) && amt > 0
        ? 'LKR ' + fmtNum(amt * _todayUsdRate)
        : '-';
}
document.getElementById('pr_amount').addEventListener('input', updatePaymentRequestLkrPreview);

async function submitPaymentRequest() {
    if (!_tourData) return;
    if (_releaseMode) return _releaseHeldPayment();   // tf-offers-r3: the System Administrator releasing a held payment
    const rowIdx = document.getElementById('pr_row_idx').value;
    const row    = _tourData.payment_rows[rowIdx];
    const t      = _tourData.tour_info;

    if (row.category === 'Hotel' && !_todayUsdRate) {
        Swal.fire('Warning', "Today's USD rate hasn't been entered yet. Please set it before requesting a hotel payment.", 'warning');
        return;
    }

    let requestAmount = document.getElementById('pr_amount').value;
    requestAmount = parseFloat(String(requestAmount).replace(/,/g, ''));
    if (isNaN(requestAmount) || requestAmount <= 0) {
        Swal.fire('Warning', 'Please enter a valid amount greater than zero.', 'warning');
        return;
    }

    // Can't request more than the quoted amount (Q Amount for Hotel, total for others) —
    // same ceiling tour_accounts enforces on its own Request button.
    const ceiling = row.category === 'Hotel' ? Math.max(row.total_dollar ?? row.total, row.cost_dollar ?? 0) : tpdCeiling(row);   // tf-paydesk-r1: one request covers a supplier's charges
    if (ceiling > 0 && requestAmount > ceiling) {
        const unit = row.category === 'Hotel' ? '$' : 'LKR';
        Swal.fire('Amount Exceeded!', `Request amount (${unit} ${fmtNum(requestAmount)}) cannot exceed the Q Amount / hotel cost (${unit} ${fmtNum(ceiling)}).`, 'error');
        return;
    }

    let url, payload;
    // Hotel amounts (totalHotelPay/dueAmount/requestAmount/tot_amt_doller) are all
    // in $ on tour_accounts — only the LKR posting is derived server-side from
    // requestAmount × the day's USD rate. Everything else stays LKR.
    const isHotel   = row.category === 'Hotel';
    const totalAmt  = isHotel ? (row.total_dollar ?? row.total) : row.total;
    const common = {
        tourId:   t.tour_id,
        manualId: t.manual_id,
        consignee: t.consignee || '',
        createUser: t.create_user || '',
        bankName:   row.bank_name || '',
        branchName: row.branch_name || '',
        supBankAcc: row.supplier_bank_acc || '',
        totalHotelPay:      totalAmt,
        totalHotelAdvance:  row.paid || 0,
        dueAmount:          totalAmt,
        requestAmount:      requestAmount,
        tot_amt_doller:     totalAmt,
    };

    if (isHotel) {
        url = tourAccountsBaseUrl + 'payment_request';
        payload = Object.assign({}, common, {
            hotelAcc:   row.supplier_acc,
            hotelName:  row.supplier,
            startDate:  t.start_date || '',
            endDate:    t.end_date || '',
            type:       'Hotel',
        });
    } else if (row.category === 'Activity') {
        url = tourAccountsBaseUrl + 'payment_request';
        payload = Object.assign({}, common, {
            hotelAcc:   row.supplier_acc,
            hotelName:  row.supplier,
            activityId: row.activity_id,
            type:       'lunch',
        });
    } else if (row.category === 'Driver Advance') {
        if (!row.driver_advance_request_id) {
            Swal.fire('Warning', 'No driver-submitted advance request found for this driver yet.', 'warning');
            return;
        }
        // Mirrors tour_accounts_note's "Advance Request" row / driver-advance-request
        // button — same endpoint, advances the driver's own submitted request.
        url = tourAccountsBaseUrl + 'driver_advance_payment_request';
        payload = Object.assign({}, common, {
            requestId: row.driver_advance_request_id,
            hotelAcc:  row.supplier_acc,
            hotelName: row.supplier,
            type:      'Transport Agent',
        });
    } else {
        Swal.fire('Warning', 'Payment request is not supported for this category.', 'warning');
        return;
    }

    const submitBtn = document.getElementById('pr_submit_btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Requesting...';
    // tf-offers-r1: the rate the authorized user picked is recorded (who, when, which) before the request goes
    if (isHotel && row.cost_offer && !(await _saveOfferPick(row))) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ri-send-plane-line me-1"></i> Send Request';
        return;
    }

    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams(Object.assign({}, payload, { [TF_CSRF_NAME]: TF_CSRF_HASH })),   // tf-paydesk-r7e
    })
        .then(r => r.json())
        .then(res => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ri-send-plane-line me-1"></i> Send Request';
            if (res.status === 200) {
                bootstrap.Modal.getInstance(document.getElementById('paymentRequestModal')).hide();
                // tf-offers-r3: held for the System Administrator's rate review — not requested yet
                // tf-paydesk-r1: the desk records the invoice, reference, due date and note with the request, then the dashboard reloads
                tpdAfterRequest(row, res).then(() => Swal.fire(res.held ? 'Sent for rate review' : 'Success', res.message || 'Payment request submitted.', res.held ? 'info' : 'success')).then(() => {
                    searchTour(); // Refresh dashboard
                });
            } else {
                Swal.fire('Error', res.message || 'Failed to submit payment request.', 'error');
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ri-send-plane-line me-1"></i> Send Request';
            Swal.fire('Error', 'Network error. Please try again.', 'error');
        });
}

// ==========================================
//  Helpers (as before)
// ==========================================
function fmtNum(n) {
    return parseFloat(n || 0).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
}

// Exchange rates need decimal precision (e.g. 324.56) — fmtNum() rounds to a whole
// number, which is correct for LKR totals but silently misrepresents the rate itself.
function fmtRate(n) {
    return parseFloat(n || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function fmtDate(d) {
    if (!d) return '-';
    const dt = new Date(d);
    return dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'});
}

// Hotel rows carry every night's date (stay_dates) so a multi-night booking shows
// all dates comma-separated + a nights badge, instead of collapsing to one date.
function _stayDatesHtml(row) {
    if (row.category !== 'Hotel' || !row.stay_dates || !row.stay_dates.length) {
        return row.due_date ? fmtDate(row.due_date) : '-';
    }
    const dateList = row.stay_dates.map(fmtDate).join(', ');
    const nights   = row.night_count || row.stay_dates.length;
    const badge    = nights > 1 ? ` <span class="badge bg-light text-dark border">${nights} nights</span>` : '';
    return dateList + badge;
}

function statusClass(s) {
    const map = {
        'Fully Paid':              'bg-success',
        'Partially Paid':          'bg-warning text-dark',
        'Pending Payment':         'bg-warning text-dark',
        'Pending Approval':        'bg-purple',
        'Advance Paid':            'bg-primary',
        'Not Paid':                'bg-danger',
        'Requested':               'bg-purple',
        'Cancelled':               'bg-secondary',
        'Payment Not Requested':     'bg-secondary',
        'Waiting Rate Confirmation': 'bg-orange',
        'Payment on Process':        'bg-teal',
        'Paid':                      'bg-success',
    };
    return map[s] || 'bg-secondary';
}

function ticketStatusClass(s) {
    const map = {
        'pending':  'bg-warning text-dark',
        'approved': 'bg-success',
        'rejected': 'bg-danger',
    };
    return map[s] || 'bg-secondary';
}

function renderTickets(tickets) {
    const tbody = document.getElementById('tickets_table_body');
    tbody.innerHTML = '';

    if (!tickets.length) {
        document.getElementById('tickets_empty_msg').style.display = 'block';
        document.getElementById('tickets_table').style.display = 'none';
        return;
    }
    document.getElementById('tickets_empty_msg').style.display = 'none';
    document.getElementById('tickets_table').style.display = '';

    tickets.forEach(t => {
        const statusCls = ticketStatusClass(t.status);
        const statusLabel = t.status ? t.status.charAt(0).toUpperCase() + t.status.slice(1) : '-';
        const approvedHtml = t.status === 'rejected'
            ? `<span class="text-danger small" title="${esc(t.rejection_reason || '')}"><i class="ri-close-circle-line me-1"></i>${esc(t.approved_by || '-')}</span>`
            : (t.approved_by ? esc(t.approved_by) : '<span class="text-muted">-</span>');
        tbody.innerHTML += `<tr>
            <td class="fw-semibold text-primary">${esc(t.usage_note_no)}</td>
            <td class="small">${fmtDate(t.date)}</td>
            <td class="small">${esc(t.item_names || '-')}</td>
            <td class="text-center small">${t.total_quantity ?? 0}</td>
            <td><span class="badge rounded-pill ${statusCls}">${statusLabel}</span></td>
            <td class="small">${approvedHtml}</td>
        </tr>`;
    });
}

function statusColor(s) {
    if (s === 'Completed')   return 'bg-success text-white';
    if (s === 'Cancelled')   return 'bg-danger text-white';
    return 'bg-primary text-white';
}

function catColorClass(cat) {
    const map = {
        'Hotel':     'bg-primary',
        'Activity':  'bg-success',
        'Transport': 'bg-info text-dark',
        'Driver Advance': 'bg-info text-dark',
        'Driver':    'bg-purple',
        'Other':     'bg-warning text-dark',
    };
    return map[cat] || 'bg-secondary';
}

function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}
</script>
</body>
