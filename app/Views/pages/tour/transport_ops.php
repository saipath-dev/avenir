<?= view('layouts/head') ?>
<style>
    /* ---------- Transport Operations (same tokens as the Tour Inquiry panel; the Tour Desk bar re-points them) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#b7791f; --tf-amber-soft:#fdf3e1;
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
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .btn-light:hover { background:var(--tf-bg); }
    .tf .btn-success { background:var(--tf-green); border-color:var(--tf-green); }
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl td { padding:9px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr:last-child td { border-bottom:0; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .pill-amber { background:var(--tf-amber-soft); color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-teal { background:var(--tf-teal-soft); color:var(--tf-teal); }
    .tf .flag { display:block; font-size:11.5px; color:var(--tf-amber); font-weight:600; margin-top:2px; }
    .tf .chips { display:flex; gap:6px; flex-wrap:wrap; }
    .tf .chip { border:1px solid var(--tf-line); background:#fff; border-radius:9px; padding:5px 11px; font-size:12px; font-weight:500; cursor:pointer; color:var(--tf-ink); }
    .tf .chip .n { font-family:ui-monospace, Menlo, monospace; font-size:11px; color:var(--tf-muted); margin-left:4px; }
    .tf .chip.on { background:var(--tf-blue-soft); border-color:transparent; color:var(--tf-navy); font-weight:600; }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:14px 0; text-align:center; }
    .tf .kv { display:grid; grid-template-columns:110px 1fr; gap:5px 12px; font-size:12.5px; }
    .tf .kv dt { color:var(--tf-muted); font-weight:500; }
    .tf .kv dd { margin:0; }
    .tf .box { border:1px solid var(--tf-line); border-radius:8px; padding:11px 13px; margin-bottom:12px; }
    .tf .box h6 { font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-muted); font-weight:700; margin:0 0 8px; display:flex; justify-content:space-between; align-items:center; gap:8px; }
    .tf .ladder { display:flex; gap:4px; flex-wrap:wrap; align-items:center; }
    .tf .rung { font-size:11px; padding:3px 8px; border-radius:14px; background:#eef1f5; color:var(--tf-muted); }
    .tf .rung.done { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .rung.now { background:var(--tf-navy); color:#fff; font-weight:600; }
    .tf .trail { font-size:12px; }
    .tf .trail div { padding:5px 0; border-bottom:1px dashed var(--tf-line); }
    .tf .trail div:last-child { border-bottom:0; }
    .tf .trail .who { color:var(--tf-muted); }
    .tf .crewline { padding:8px 0; border-bottom:1px dashed var(--tf-line); font-size:12.5px; }
    .tf .crewline:last-child { border-bottom:0; }
    .tf .crewline .top { display:flex; justify-content:space-between; align-items:center; gap:8px; }
    .tf .crewline .who { display:flex; gap:6px; margin-top:5px; }
    .tf .crewline .who input:first-child { flex:1 1 auto; min-width:0; }
    .tf .crewline .who input[type=number] { width:104px; flex:none; }
    .tf .crewline.replaced { opacity:.78; }
    .tf .crewline .dates { display:flex; gap:6px; align-items:center; margin-top:6px; flex-wrap:wrap; }
    .tf .crewline .dates input { width:138px; flex:none; }
    .tf .crewline .vline { display:flex; gap:6px; align-items:center; margin-top:6px; flex-wrap:wrap; }
    .tf .crewline .rform { margin-top:6px; }
    .tf .crewline .rform .rbox { background:var(--tf-amber-soft); border:1px solid #f0dcb0; border-radius:8px; padding:10px 12px; }
    .tf .crewline .rform .row-2 { display:flex; gap:6px; align-items:center; flex-wrap:wrap; margin-top:6px; }
    .tf .crewline .rform input[type=date] { width:138px; }
    .tf .docrow { display:flex; gap:8px; align-items:center; justify-content:space-between; padding:5px 0; border-bottom:1px dashed var(--tf-line); font-size:12.5px; flex-wrap:wrap; }
    .tf .docrow:last-child { border-bottom:0; }
    .tf .docrow .acts { display:flex; gap:4px; flex-wrap:wrap; }
    .tf .docrow .btn { padding:1px 7px; font-size:11px; }
    /* the driver's advance and settlement (schema 26) */
    .tf .money { display:grid; grid-template-columns:1fr auto; gap:2px 12px; font-size:12.5px; align-items:baseline; }
    .tf .money .amt { font-family:Consolas, Menlo, monospace; text-align:right; white-space:nowrap; }
    .tf .money .tot { font-weight:700; border-top:1px solid var(--tf-line); padding-top:3px; }
    .tf .money .help.manual { color:var(--tf-amber); font-weight:600; }
    .tf .advline { padding:6px 0; border-bottom:1px dashed var(--tf-line); font-size:12.5px; }
    .tf .advline:last-child { border-bottom:0; }
    .tf .advline .acts { display:flex; gap:4px; flex-wrap:wrap; margin-top:3px; }
    .tf .advline .acts .btn { padding:1px 7px; font-size:11px; }
    .tf .pctbtn { padding:2px 9px; font-size:11.5px; }
    .tf .pctbtn.on { background:var(--tf-navy); color:#fff; }
    /* the driver's pack, right under the steps: the list from the master, each document generated from the record where it can be */
    .tf .pack { margin-top:10px; padding:8px 10px; border:1px solid var(--tf-line); border-radius:8px; background:#fbfcfd; }
    .tf .pack .pack-h { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:4px; }
    .tf .pack .docrow.done { opacity:.85; }
    .tf .pack .docrow .name { min-width:180px; }
    .tf .origin-changed td:first-child { border-left:3px solid var(--tf-amber); }
    .tf .origin-added td:first-child { border-left:3px solid var(--tf-teal); }
    .tf .rowdel { color:#b6bfca; padding:0 6px; line-height:1.1; font-size:15px; }
    .tf .rowdel:hover { color:var(--tf-red); background:var(--tf-red-soft); }
    .tf .tbl tr.rowlink { cursor:pointer; }
    .tf .tbl tr.rowlink:hover td { background:#fafbfd; }
    .tf .chip.on .n { color:var(--tf-navy); }
    /* the tour opened: its header, then its vehicle cards */
    .tf .tv-head { display:flex; gap:12px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; padding:12px 14px; }
    .tf .tv-head .who { font-size:14px; font-weight:600; }
    .tf .tv-head .who small { font-weight:400; color:var(--tf-muted); font-size:12px; }
    .tf .tv-mark { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .tf .flag.bad { color:var(--tf-red); }
    .modal .modal-content { border-radius:12px; }
    .tf .cancel-flag { background:#fbe9e6; color:#8a2a22; border:1px solid #f0b8b0; border-radius:8px; padding:8px 12px; margin:8px 0; font-size:13px; }
    /* tf-crew-master-r1: the allocation picker (one category only) and the person allocated on the line */
    .tf .pk-head { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:12px; }
    .tf .pk-head .form-control { width:280px; }
    .tf .pk-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:12px; }
    .tf .pk-card { border:1px solid var(--tf-line); border-radius:10px; padding:12px 14px; background:#fff; display:flex; flex-direction:column; gap:7px; box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .pk-card.warn { border-color:#f0d78c; }
    .tf .pk-card.block { opacity:.6; }
    .tf .pk-card .pk-top { display:flex; gap:11px; align-items:flex-start; }
    .tf .pk-avatar { width:54px; height:54px; border-radius:50%; object-fit:cover; background:var(--tf-blue-soft); border:1px solid var(--tf-line); flex:none; display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--tf-navy); font-size:17px; overflow:hidden; }
    .tf .pk-avatar img { width:100%; height:100%; object-fit:cover; }
    .tf .pk-name { font-size:14.5px; font-weight:700; line-height:1.2; color:var(--tf-navy); }
    .tf .pk-name small { font-weight:400; color:var(--tf-muted); font-size:12px; display:block; margin-top:2px; }
    .tf .pk-line { font-size:12.5px; display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
    .tf .pk-line .lbl { font-size:10.5px; letter-spacing:.05em; text-transform:uppercase; color:var(--tf-muted); font-weight:700; min-width:58px; }
    .tf .pk-docs { display:flex; gap:4px; flex-wrap:wrap; }
    .tf .pk-warn { background:var(--tf-amber-soft); border:1px solid #f0d78c; border-radius:7px; padding:6px 9px; font-size:11.5px; color:#7a5200; }
    .tf .pk-warn ul { margin:0; padding-left:16px; }
    .tf .pk-block { background:var(--tf-red-soft); border:1px solid #f0b8b0; border-radius:7px; padding:6px 9px; font-size:11.5px; color:var(--tf-red); font-weight:600; }
    .tf .pk-foot { display:flex; justify-content:space-between; align-items:center; gap:6px; margin-top:auto; padding-top:4px; }
    .tf .pk-cat { background:var(--tf-navy); color:#fff; font-size:10.5px; font-weight:700; border-radius:20px; padding:2px 9px; }
    .tf .roof { background:#fff3cd; color:#7a5200; border:1px solid #f0d78c; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; white-space:nowrap; }
    .tf .alloc { display:flex; gap:11px; align-items:flex-start; border:1px solid var(--tf-line); border-radius:9px; padding:9px 11px; margin-top:6px; background:#fbfcfd; }
    .tf .alloc .pk-avatar { width:46px; height:46px; font-size:14px; }
    .tf .alloc .pk-name { font-size:13.5px; }
    .tf .alloc .acts { margin-left:auto; display:flex; gap:4px; flex-wrap:wrap; align-items:flex-start; }
    .tf .alloc-empty { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:6px; }
    .tf .btn-pick { background:var(--tf-navy); border-color:var(--tf-navy); color:#fff; font-weight:600; }
    .tf .btn-pick:hover { background:var(--tf-navy-2); border-color:var(--tf-navy-2); color:#fff; }
    .tf .pk-empty { grid-column:1/-1; text-align:center; padding:28px 10px; color:var(--tf-muted); font-size:13px; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar', ['tourdesk_desk_only' => 'transport']) /* tf-transdesk-r1: the Transport Operations Desk strip */ ?>
                <?= view('layouts/tourdesk_menu') ?>

                <div class="tf-title">
                    <div>
                        <h4>Transport Operations</h4>
                        <div class="sub">One row per confirmed tour — the tour's one job on this desk, put here the moment the Quotation Desk presses Confirm Tour. Open the tour to see every vehicle it needs, with its dates, sectors, kilometres, driver and guide. Allocate the vehicle, engage the crew, check the schedule, issue the driver's pack. When every vehicle, driver and guide is allocated, mark <b>Transport work complete</b>: the tour leaves the red count and stays under Completed with its whole history. Kilometres, never rupees: the rate stays on the inquiry and the Tour Board.</div>
                    </div>
                    <div><button class="btn btn-light btn-sm" id="btn_docs"><i class="bi bi-list-check"></i> Driver's pack list</button></div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Desk tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Inquiries</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <!-- the list: one row per tour -->
                <div class="tf-card" id="list_card">
                    <div class="tf-card-h">
                        <div class="chips" id="chips"></div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <select class="form-select form-select-sm" id="f_handler" style="width:auto;max-width:190px" title="Handling agent — whose tour it is">
                                <option value="">Any handling agent</option>
                                <?php foreach ((array) ($handlers ?? []) as $h): ?>
                                <option value="<?= esc($h['id'] !== null ? (string) $h['id'] : (string) $h['name']) ?>"><?= esc($h['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input class="form-control form-control-sm" id="f_q" placeholder="Tour, inquiry, agent, group…" style="width:200px">
                            <input type="date" class="form-control form-control-sm" id="f_from" style="width:auto" title="Arriving from">
                            <input type="date" class="form-control form-control-sm" id="f_to" style="width:auto" title="Arriving to">
                            <button class="btn btn-light btn-sm" id="f_clear" type="button" title="Clear every filter"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="tbl">
                            <thead><tr><th>Tour No.</th><th>Inquiry No.</th><th>Agent</th><th>Tour / group</th><th>Arrival</th><th>Departure</th><th style="width:50px">Pax</th><th>Vehicles</th><th>Vehicle · driver · guide</th><th>Status</th><th>Handler</th><th style="width:70px"></th></tr></thead>
                            <tbody id="tours"><tr><td colspan="12" class="empty">Loading…</td></tr></tbody>
                        </table>
                    </div>
                </div>

                <!-- the tour opened: its vehicle cards -->
                <div id="tour_view" style="display:none">
                    <div class="tf-card mb-3">
                        <div class="tv-head" id="tv_head"></div>
                        <div style="padding:0 14px 12px">
                            <details id="tv_details_wrap"><summary style="cursor:pointer;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--tf-muted);font-weight:700">The whole tour — programme, hotels each night, passengers, flights, requests</summary><div id="tv_details" class="mt-2"></div></details>
                        </div>
                    </div>
                    <!-- tf-transmail-r1: the booking requests to the supplier, the driver and the guide -->
                    <div class="tf-card mb-3" id="tv_requests_card" style="display:none">
                        <div class="tf-card-h">
                            <div><h6>Booking requests — supplier, driver and guide</h6><div class="help">Prepared from the record as soon as somebody is assigned. Send by e-mail where there is an address, copy the same words to WhatsApp, or print the sheet — then record the answer. <b>A request is never a confirmation</b>: the booking stays pending until you record the supplier's or the driver's own answer on the card below.</div></div>
                            <button class="btn btn-light btn-sm" id="rq_refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                        </div>
                        <div style="padding:12px 14px" id="tv_requests"></div>
                    </div>
                    <div class="tf-card">
                        <div class="tf-card-h">
                            <div><h6>Transport required for this tour</h6><div class="help">One card per vehicle. Open a card to set the vehicle, engage the driver and the guide, check the day-by-day schedule and the kilometres, and issue the driver's pack.</div></div>
                        </div>
                        <div style="overflow-x:auto">
                            <table class="tbl">
                                <thead><tr><th style="width:190px">Vehicle</th><th style="width:170px">Driver</th><th style="width:170px">Guide</th><th style="width:150px">Dates</th><th style="width:120px">Days · km</th><th style="width:90px">Pack</th><th style="width:120px">Status</th><th style="width:110px"></th></tr></thead>
                                <tbody id="rows"><tr><td colspan="8" class="empty">Loading…</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<div class="modal fade" id="m_card" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="c_title">Vehicle</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="c_body"></div>
</div></div></div>

<!-- A document of the card (a pack document, the programme, a voucher, the settlement) shown inside the desk.
     A new tab cannot be relied on: some browsers and the desktop app's own pane swallow target=_blank
     and window.open, so every document link on this desk opens here, with Print / Save as PDF, and a way
     to open it as a page in this tab for anyone who wants that. -->
<div class="modal fade" id="m_doc" tabindex="-1"><div class="modal-dialog modal-xl" style="max-width:1080px"><div class="modal-content tf">
    <div class="modal-header">
        <h6 class="modal-title" id="doc_title">Document</h6>
        <div class="d-flex gap-1 align-items-center ms-auto me-2 flex-wrap">
            <button type="button" class="btn btn-primary btn-sm" id="doc_print" title="Print, or choose Save as PDF in the print dialog"><i class="bi bi-printer"></i> Print / Save as PDF</button>
            <a class="btn btn-light btn-sm" id="doc_page" href="#" title="The same document as a page in this tab; Back returns to the desk">Open as a page</a>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body p-0" style="background:#eef1f5">
        <div class="help p-2" id="doc_note" style="display:none"></div>
        <iframe id="doc_frame" title="Document" style="width:100%; height:78vh; border:0; background:#fff"></iframe>
    </div>
</div></div></div>

<!-- tf-crew-master-r1: the allocation picker. Opens on ONE category — the crew line's — and lists only the people of that
     category on the Driver & Guide Master, each as a card: photo, nickname, legal name, the vehicle normally supplied
     with its roof type, the licences and documents with their expiry, the rate in force. Inactive and archived people
     are never offered; an expired licence or document is in front of the person allocating before they go on. -->
<div class="modal fade" id="m_pick" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:1120px"><div class="modal-content tf">
    <div class="modal-header" style="align-items:flex-start">
        <div><h6 class="modal-title" id="pk_title">Allocate</h6><div class="help" id="pk_sub"></div></div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="background:var(--tf-bg)">
        <div class="pk-head">
            <input class="form-control form-control-sm" id="pk_q" placeholder="Name, nickname, phone, vehicle number…">
            <div class="chips" id="pk_chips"></div>
            <span class="help ms-auto" id="pk_count"></span>
        </div>
        <div class="pk-grid" id="pk_grid"></div>
        <div class="help mt-3" id="pk_foot"></div>
    </div>
</div></div></div>

<div class="modal fade" id="m_docs" tabindex="-1"><div class="modal-dialog"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title">The driver's pack — what is handed over and what comes back</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help mb-2">A new card starts with every item switched on here. Cards already open keep the list they started with.</div>
        <table class="tbl" id="doclist"><thead><tr><th>Code</th><th>Document</th><th>When</th><th>In use</th></tr></thead><tbody></tbody></table>
        <div class="row g-2 mt-2 align-items-end">
            <div class="col-3"><label class="form-label help">Code</label><input class="form-control form-control-sm" id="dt_code" placeholder="KEYS"></div>
            <div class="col-5"><label class="form-label help">Document</label><input class="form-control form-control-sm" id="dt_name" placeholder="Vehicle keys and fuel card"></div>
            <div class="col-2"><label class="form-label help">When</label><select class="form-select form-select-sm" id="dt_phase"><option value="before">Before</option><option value="after">After</option></select></div>
            <div class="col-2"><button class="btn btn-primary btn-sm w-100" id="dt_add">Add</button></div>
        </div>
    </div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'tour/transport_ops') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const CAN_MANAGE = <?= !empty($can_manage) ? 'true' : 'false' ?>;
    const CAN_INTAKE = <?= !empty($can_intake) ? 'true' : 'false' ?>;   // tf-walkfix-r1: the inquiry is offered only to a sign-in that may open it
    const CAN_REPLACE = <?= !empty($can_replace) ? 'true' : 'false' ?>;   // the Crew replacement privilege: "Replace mid-tour" (schema 24)
    const CAN_ALLOCATE = <?= !empty($can_allocate) ? 'true' : 'false' ?>;   // tf-pilot-r2: the Driver Allocation privilege — engage crew, set the vehicle, the days worked (Operations)
    const NO_ALLOC = 'Allocation (crew, vehicle, days) is done by Operations — the Driver Allocation privilege.';
    const CAN_BANK = <?= !empty($can_bank) ? 'true' : 'false' ?>;   // tf-crew-master-r1: "Driver & Guide bank details" — the account on the payment lines
    const CAN_ADVANCE = <?= !empty($can_advance) ? 'true' : 'false' ?>;   // the Driver advance privilege: approve, adjust, pay, settlement adjustments, Settled (schema 26)
    const MANAGE_BASE = '<?= base_url($manage_url ?? 'tour/manage') ?>';
    const INTAKE = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const ROOT = '<?= rtrim(base_url(), '/') ?>';
    const MASTER_URL = '<?= base_url('master_files/crew_people') ?>';   // tf-crew-master-r1: the Driver & Guide Master
    if (!INSTALLED) return;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2800 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With':'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const manage = (path, body) => fetch(MANAGE_BASE + path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) }).then(r => r.json());
    // The signed-in person's password, checked on the server before an action that cannot be taken back (cancel,
    // delete). Returns the password to send with the action, or null when the person stepped back or the check
    // failed — nothing is changed on a failure, and the wrong attempt is on the Activity log.
    async function askPassword(title, text, purpose) {
        const pw = await Swal.fire({ title: title + ' — your password', html: `<div style="text-align:left;font-size:13.5px">${esc(text)}</div>`, input: 'password', inputPlaceholder: 'Your password', inputAttributes: { autocomplete: 'current-password' }, inputValidator: v => (v ? undefined : 'Enter your password.'), showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#b23a32' });
        if (!pw.isConfirmed) return null;
        const a = await manage('/reauth', { password: pw.value || '', purpose: purpose || title });
        if (a.status !== 200 || !a.ok) { await Swal.fire({ icon: 'error', title: 'Not done', text: a.message || 'Password not recognised. Nothing was changed.' }); return null; }
        return pw.value || '';
    }
    const ME_NAME = <?= json_encode((string) ($me_name ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    // the ⋯ on a tour row (Admin only; the server checks the privilege again): cancel or delete the whole tour
    function tourManageMenu(btn, t) {
        const live = t.stage !== 'cancelled';
        const items = [{ title: `${t.tour_no} · ${t.tour_name || ''}` }];
        items.push({ label: 'Open on Admin', icon: 'bi-shield-lock', hint: 'Every tour, cancel, delete, restore', onClick: () => window.open(MANAGE_BASE, '_blank') });
        items.push({ sep: true });
        items.push({ label: live ? 'Cancel tour…' : 'Reopen tour…', icon: live ? 'bi-x-circle' : 'bi-arrow-counterclockwise', hint: live ? 'Management: password, reason, final warning; every card marked; off both counts' : 'Management: password, reason, final warning; back on the desks, cards at their first step', onClick: async () => {
            if (live) {
                // Management only, in three steps: the password, the reason, the final warning — then every card is marked
                const pw = await askPassword('Cancel tour ' + t.tour_no, 'Cancelling marks every hotel and transport card cancelled and takes the tour off both desks\' counts. Enter your own sign-in password to go on; it is checked and not kept.', 'Cancel tour ' + t.tour_no);
                if (pw === null) return;
                const why = await Swal.fire({ title: 'Why is ' + t.tour_no + ' cancelled?', input: 'text', inputPlaceholder: 'The reason (required, stays on the trail and on every card)', inputValidator: v => (v && v.trim() ? undefined : 'The reason is required.'), showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#b23a32' });
                if (!why.isConfirmed) return;
                const fin = await Swal.fire({ icon: 'warning', title: 'Cancel tour ' + t.tour_no + '?', html: `<div style="text-align:left;font-size:13.5px"><div style="background:#fbe9e6;border:1px solid #f0b8b0;border-radius:8px;padding:8px 12px;margin-bottom:10px;color:#8a2a22"><b>Last warning.</b> Every hotel, activity and transport card is marked <b>Cancelled</b> for the desks with your reason, the tour leaves the live board and counts for nothing on either desk. Work already done stays on the trail. Reopen brings it back only at the cards' first step.</div>Reason: <b>${esc(why.value.trim())}</b><br>Cancelled by <b>${esc(ME_NAME)}</b>, now; the name and time go on the tour and the Activity log.</div>`, showCancelButton: true, cancelButtonText: 'Keep the tour', confirmButtonText: 'Cancel the tour', confirmButtonColor: '#b23a32', focusCancel: true });
                if (!fin.isConfirmed) return;
                const j = await manage('/tour/' + t.id + '/cancel', { why: why.value.trim(), password: pw });
                if (j.status !== 200) { toast('error', j.message); return; }
                toast('success', j.message); loadTours();
                return;
            }
            // Reopen undoes a cancellation: Management only, in the same three steps — the password, the reason, the last warning
            const pw = await askPassword('Reopen tour ' + t.tour_no, 'Reopening undoes the cancellation: the tour returns to the live board and to both desks at the cards\' first step. Enter your own sign-in password to go on; it is checked and not kept.', 'Reopen tour ' + t.tour_no);
            if (pw === null) return;
            const why = await Swal.fire({ title: 'Why is ' + t.tour_no + ' reopened?', input: 'text', inputPlaceholder: 'The reason (required, stays on the trail)', inputValidator: v => (v && v.trim() ? undefined : 'The reason is required.'), showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#1f7a4d' });
            if (!why.isConfirmed) return;
            const fin = await Swal.fire({ icon: 'warning', title: 'Reopen tour ' + t.tour_no + '?', html: `<div style="text-align:left;font-size:13.5px"><div style="background:#fbe9e6;border:1px solid #f0b8b0;border-radius:8px;padding:8px 12px;margin-bottom:10px;color:#8a2a22"><b>Last warning.</b> The cancellation is undone. The tour is back on the live board and on Hotel Bookings and Transport, every card at its first step; the cancellation and this reopen stay on the trail.</div>Reason: <b>${esc(why.value.trim())}</b><br>Reopened by <b>${esc(ME_NAME)}</b>, now; the name, the time and the reason go on the Activity log.</div>`, showCancelButton: true, cancelButtonText: 'Leave it cancelled', confirmButtonText: 'Reopen the tour', confirmButtonColor: '#1f7a4d', focusCancel: true });
            if (!fin.isConfirmed) return;
            const j = await manage('/tour/' + t.id + '/reopen', { why: why.value.trim(), password: pw });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); loadTours();
        } });
        items.push({ label: 'Delete tour…', icon: 'bi-trash', danger: true, hint: 'Cancelled and archived with its inquiry; off every list and count; restorable from Admin', onClick: async () => {
            const pw = await askPassword('Delete tour ' + t.tour_no, 'Deleting cancels the tour and archives it with its inquiry. Enter your own sign-in password to go on; it is checked and not kept.', 'Delete tour ' + t.tour_no);
            if (pw === null) return;
            const r = await Swal.fire({ title: 'Delete tour ' + t.tour_no + '?', html: '<div style="text-align:left;font-size:13.5px">Are you sure you want to delete this record? The tour is cancelled and archived with its inquiry: off Inquiries, Hotel Bookings, Transport, the Board and every red count. Nothing is removed — it is kept whole under Admin → Deleted / archived and can be restored from there.</div>', input: 'text', inputPlaceholder: 'Why (required, stays on the trail)', inputValidator: v => (v && v.trim() ? undefined : 'The reason is required.'), showCancelButton: true, confirmButtonText: 'Delete the tour', confirmButtonColor: '#b23a32' });
            if (!r.isConfirmed) return;
            const j = await manage('/tour/' + t.id + '/delete', { why: r.value || '', password: pw });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); loadTours();
        } });
        window.tdMenu(btn, items);
    }
    const fmt = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short' }); };
    const fmtY = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); };
    const when = s => s ? String(s).slice(0, 16) : '';
    const num = v => v == null ? '—' : Number(v).toLocaleString();

    const LADDER = ['to_allocate', 'allocated', 'scheduled', 'pack_issued', 'running', 'returned', 'settled'];
    const LABEL = { to_allocate: 'To allocate', allocated: 'Allocated', scheduled: 'Scheduled', pack_issued: 'Pack issued', running: 'Running', returned: 'Returned', settled: 'Settled', cancelled: 'Cancelled' };
    const PILL = { to_allocate: 'pill-grey', allocated: 'pill-blue', scheduled: 'pill-blue', pack_issued: 'pill-teal', running: 'pill-teal', returned: 'pill-green', settled: 'pill-green', cancelled: 'pill-grey' };
    const NEXT_WORD = { allocated: 'Mark allocated', scheduled: 'Schedule checked', pack_issued: 'Pack issued', running: 'On the road', returned: 'Documents back', settled: 'Settled' };
    const KIND = { transfer:'Transfer', local:'Local', arrival:'Arrival', departure:'Departure', positioning:'Positioning', idle:'Stay day', garage:'Garage' };
    const CHIPS = [['pending', 'Pending'], ['completed', 'Completed'], ['cancelled', 'Cancelled'], ['all', 'All']];
    let WHICH = 'pending', CARD = null, TOUR = null, TOURS = [], MASTERS = { vehicles: [], freelancers: [], doc_types: [] };

    const pill = st => `<span class="pill ${PILL[st] || 'pill-grey'}">${esc(LABEL[st] || st)}</span>`;

    // =====================================================================
    //  The list: one row per confirmed tour
    // =====================================================================
    // the red count on the bar is this desk's pending tours: it moves the moment the work does
    function updateBadge(n) {
        const tab = document.querySelector('.tdbar .td-item.on');
        if (!tab || n === undefined || n === null) return;
        let b = tab.querySelector('.td-badge');
        if (Number(n) > 0) {
            if (!b) { b = document.createElement('span'); b.className = 'td-badge'; tab.appendChild(b); }
            b.textContent = String(n);
        } else if (b) {
            b.remove();
        }
    }
    function renderChips(counts) {
        document.getElementById('chips').innerHTML = CHIPS.map(([k, l]) => {
            const n = counts[k];
            return `<span class="chip ${WHICH === k ? 'on' : ''}" data-chip="${k}">${l}${n !== undefined ? `<span class="n">${n}</span>` : ''}</span>`;
        }).join('');
        document.querySelectorAll('[data-chip]').forEach(c => c.addEventListener('click', () => { WHICH = c.dataset.chip; loadTours(); }));
    }
    const filterQs = () => new URLSearchParams({ which: WHICH, q: document.getElementById('f_q').value || '', handler: document.getElementById('f_handler').value || '', from: document.getElementById('f_from').value || '', to: document.getElementById('f_to').value || '' }).toString();
    const alloc = (x, noun) => {
        if (!x || !x.of) return `<span class="help">${noun === 'Guide' ? 'no guide wanted' : '—'}</span>`;
        const ok = x.done >= x.of;
        return `<span class="pill ${ok ? 'pill-green' : 'pill-amber'}">${noun} ${ok ? 'allocated' : 'to allocate'}${x.of > 1 ? ` ${x.done}/${x.of}` : ''}</span>`;
    };
    // A cancelled tour is flagged in red wherever it shows, with the reason, who cancelled it and when
    const cancelFlag = (t, big) => t.stage !== 'cancelled' ? '' : (big
        ? `<div class="cancel-flag"><i class="bi bi-x-octagon-fill"></i> <b>CANCELLED</b> ${t.cancelled_at ? when(t.cancelled_at) : ''}${t.cancelled_by ? ' by ' + esc(t.cancelled_by) : ''}${t.cancel_why ? ' — ' + esc(t.cancel_why) : ''}<div class="help" style="color:inherit">Do no further work on this tour: every card is marked cancelled. Work already done stays on the trail.</div></div>`
        : `<span class="pill pill-red" title="${esc((t.cancelled_at ? when(t.cancelled_at) : '') + (t.cancelled_by ? ' by ' + t.cancelled_by : '') + (t.cancel_why ? ' — ' + t.cancel_why : ''))}"><i class="bi bi-x-octagon-fill"></i> CANCELLED</span>${t.cancel_why ? `<div class="help" style="color:var(--tf-red)">${esc(t.cancel_why)}</div>` : ''}`);
    function workPill(t) {
        if (t.stage === 'cancelled') return cancelFlag(t, false);
        if (t.work === 'complete') return `<span class="pill pill-green">Complete</span><div class="help">${when(t.work_at)}${t.work_by ? ' · ' + esc(t.work_by) : ''}</div>`;
        if (t.work === 'none') return '<span class="pill pill-grey">Nothing for this desk</span>';
        const s = t.strip || {};
        const started = s.vehicle && (s.vehicle.done || (s.driver && s.driver.done));
        return `<span class="pill ${started ? 'pill-blue' : 'pill-amber'}">${started ? 'In progress' : 'Pending'}</span>${s.transport_ready ? '<div class="help">ready to mark complete</div>' : ''}${s.transport && s.transport.status ? `<div class="help">${esc(LABEL[s.transport.status] || s.transport.status)}</div>` : ''}`;
    }
    async function loadTours() {
        const j = await api('/tours?' + filterQs());
        if (j.status !== 200) { toast('error', j.message); return; }
        renderChips(j.data.counts || {});
        updateBadge((j.data.counts || {}).pending);
        const rows = TOURS = j.data.tours || [];
        const tb = document.getElementById('tours');
        if (!rows.length) {
            const empty = { pending: 'No tour is waiting for transport work. Every confirmed tour has its vehicles, drivers and guides allocated.', completed: 'No tour marked complete yet.', cancelled: 'No cancelled tour.', all: 'No confirmed tour yet.' }[WHICH];
            tb.innerHTML = `<tr><td colspan="12" class="empty">${document.getElementById('f_q').value || document.getElementById('f_handler').value ? 'Nothing matches the filter.' : empty}</td></tr>`;
            return;
        }
        tb.innerHTML = rows.map(t => { const s = t.strip || {}; return `<tr class="rowlink" data-tour="${t.id}">
            <td><b>${esc(t.tour_no)}</b>${Number(t.amending) ? '<div class="flag">being amended</div>' : ''}${t.amended ? '<div class="flag">amended — check the cards</div>' : ''}</td>
            <td class="mono">${esc(t.inquiry_no || '—')}</td>
            <td>${esc(t.agent_name || '—')}</td>
            <td>${esc(t.tour_name || '—')}</td>
            <td class="mono">${fmtY(t.arrival_date)}</td>
            <td class="mono">${fmtY(t.departure_date)}<div class="help">${t.nights ?? '?'} nights</div></td>
            <td class="mono">${esc(t.pax_total ?? '?')}</td>
            <td class="mono">${t.vehicles}</td>
            <td><div class="d-flex gap-1 flex-wrap">${alloc(s.vehicle, 'Vehicle')}${alloc(s.driver, 'Driver')}${alloc(s.guide, 'Guide')}</div></td>
            <td>${workPill(t)}</td>
            <td>${t.handler_name ? esc(t.handler_name) : '<span class="pill pill-amber">nobody</span>'}</td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-open-tour="${t.id}">Open</button>${CAN_MANAGE ? ` <button class="td-dots" data-tmanage="${t.id}" title="Admin" type="button">···</button>` : ''}</td></tr>`; }).join('');
        tb.querySelectorAll('[data-open-tour]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); openTour(Number(b.dataset.openTour)); }));
        tb.querySelectorAll('tr[data-tour]').forEach(r => r.addEventListener('click', () => openTour(Number(r.dataset.tour))));
        tb.querySelectorAll('[data-tmanage]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); const t = TOURS.find(x => Number(x.id) === Number(b.dataset.tmanage)); if (t) tourManageMenu(b, t); }));
    }

    // =====================================================================
    //  The tour opened: its vehicle cards
    // =====================================================================
    let ITEMS = [];
    async function openTour(id, quiet) {
        const j = await api('/tour/' + id + '/work');
        if (j.status !== 200) { toast('error', j.message); return; }
        TOUR = j.data.tour; ITEMS = j.data.vehicles || [];
        document.getElementById('list_card').style.display = 'none';
        document.getElementById('tour_view').style.display = '';
        // tf-refresh-r1: the tour is in the address (a refresh opens it again, read fresh); opening one is a step Back can undo
        if (!quiet) { if (window.TfView) TfView.setParams({ tour: id }, { push: true }); else { try { history.replaceState(null, '', location.pathname + '?tour=' + id); } catch (e) {} } }
        renderTourHead();
        renderVehicles();
        loadDetails(id, 'tv_details');
        loadRequests(id);
        if (!quiet) window.scrollTo({ top: 0 });
    }

    // =====================================================================
    //  tf-transmail-r1 — the booking requests to the supplier, the driver and the guide
    //  Prepared automatically from the record. Sending is a deliberate press, and it confirms nothing:
    //  the vehicle, the driver and the guide stay exactly as they are until the answer is recorded here.
    // =====================================================================
    let RQ = { parties: [], messages: [], can_send: false };
    const RQ_STATE = {
        prepared: ['Prepared — not sent', 'pill-amber'],
        sent:     ['Sent — awaiting the answer', 'pill-blue'],
        failed:   ['NOT SENT', 'pill-red'],
        recorded: ['Sent by hand', 'pill-teal'],
    };
    const rqParty = p => p === 'supplier' ? 'Transport supplier / vehicle owner' : (p === 'guide' ? 'Guide' : 'Driver');

    async function loadRequests(tourId) {
        const box = document.getElementById('tv_requests');
        const card = document.getElementById('tv_requests_card');
        if (!box || !card) { return; }
        card.style.display = '';
        box.innerHTML = '<div class="help">Reading the record…</div>';
        const j = await api('/tour/' + tourId + '/requests');
        if (j.status !== 200 || !j.data) { box.innerHTML = '<div class="help">The booking requests could not be read.</div>'; return; }
        if (!j.data.installed) { card.style.display = 'none'; return; }
        RQ = j.data;
        renderRequests();
    }

    function renderRequests() {
        const box = document.getElementById('tv_requests');
        const msgs = (RQ.messages || []).filter(m => m.kind === 'request');
        const replies = (RQ.messages || []).filter(m => m.kind === 'reply');
        if (!msgs.length) {
            box.innerHTML = '<div class="help">Nothing to ask for yet — set the vehicle and engage the driver and the guide, and their requests are prepared here.</div>';
            return;
        }
        const missing = (RQ.missing || []);
        box.innerHTML =
            (missing.length ? `<div class="not-installed mb-2"><b>Contacts missing</b><br>${missing.map(esc).join('<br>')}</div>` : '')
            + (RQ.can_send ? '' : `<div class="not-installed mb-2"><b>Waiting for the mailbox</b><br>${esc(RQ.sender_note || 'No mailbox is set up to send from yet.')}<br>Requests with an address stay <b>Prepared — not sent</b> until it is connected; they are never shown as sent. Meanwhile copy them to WhatsApp or print them, and record what you did.</div>`)
            + `<table class="tbl"><thead><tr><th style="width:190px">Who</th><th>Request</th><th style="width:150px">State</th><th style="width:330px"></th></tr></thead><tbody>`
            + msgs.map(m => {
                const [label, cls] = RQ_STATE[m.state] || ['—', 'pill-grey'];
                const reach = m.to_addr ? esc(m.to_addr) : (m.to_phone ? esc(m.to_phone) + ' <span class="help">(no e-mail — WhatsApp or print)</span>' : '<span style="color:var(--tf-red)">no contact on the master</span>');
                const mine = replies.filter(r => String(r.crew_id || 0) === String(m.crew_id || 0) && r.party === m.party);
                return `<tr data-rq="${m.id}">
                    <td><b>${esc(rqParty(m.party))}</b><div>${esc(m.party_name || '')}</div><div class="help">${reach}</div></td>
                    <td><div class="help">${esc(m.subject || '')}</div>
                        <details><summary style="cursor:pointer;font-size:12px">the words that go</summary><pre style="white-space:pre-wrap;font:12px/1.5 inherit;margin:6px 0 0">${esc(m.body || '')}</pre></details>
                        ${mine.length ? `<div class="mt-1" style="font-size:12px;color:var(--tf-green)"><b>Answer recorded:</b> ${mine.map(r => esc(String(r.body || '').slice(0, 160)) + ' <span class="help">(' + esc(String(r.at || '').slice(0, 16)) + ' · ' + esc(r.by_name || '') + ')</span>').join('<br>')}</div>` : ''}
                    </td>
                    <td><span class="pill ${cls}">${esc(label)}</span>
                        ${m.error ? `<div class="help" style="color:var(--tf-red)">${esc(m.error)}</div>` : ''}
                        <div class="help">${esc(String(m.at || '').slice(0, 16))}${m.by_name ? ' · ' + esc(m.by_name) : ''}</div>
                        <div class="help">a request is not a confirmation</div></td>
                    <td><div class="d-flex flex-wrap gap-1">
                        ${m.to_addr && RQ.can_send && m.state !== 'sent' ? `<button class="btn btn-sm btn-primary" data-rqsend="${m.id}"><i class="bi bi-${m.state === 'failed' ? 'arrow-repeat' : 'envelope'}"></i> ${m.state === 'failed' ? 'Try sending again' : 'Send by e-mail'}</button>` : ''}
                        <button class="btn btn-sm btn-light" data-rqcopy="${m.id}"><i class="bi bi-whatsapp"></i> Copy for WhatsApp</button>
                        <button class="btn btn-sm btn-light" data-rqprint="${m.id}"><i class="bi bi-printer"></i> Print</button>
                        <button class="btn btn-sm btn-light" data-rqhand="${m.id}"><i class="bi bi-check2"></i> Record as sent by hand</button>
                        <button class="btn btn-sm btn-light" data-rqreply="${m.id}"><i class="bi bi-reply"></i> Record the answer</button>
                    </div><div class="mt-1" id="rq_msg_${m.id}" style="font-size:12px;font-weight:600"></div></td></tr>`;
            }).join('') + '</tbody></table>';
        wireRequests();
    }

    function rqSay(id, text, bad) {
        const el = document.getElementById('rq_msg_' + id);
        if (el) { el.textContent = text || ''; el.style.color = bad ? '#8a1c30' : 'var(--tf-green)'; }
    }
    const rqMsg = id => (RQ.messages || []).find(m => String(m.id) === String(id)) || {};

    function wireRequests() {
        // e-mail: the button asks in place and sends on the second press — no browser dialog anywhere
        document.querySelectorAll('[data-rqsend]').forEach(b => b.addEventListener('click', async () => {
            const id = b.dataset.rqsend, m = rqMsg(id);
            if (b.dataset.armed === '1' && Date.now() - Number(b.dataset.armedAt || 0) < 500) { return; }
            if (b.dataset.armed !== '1') {
                b.dataset.armed = '1'; b.dataset.armedAt = String(Date.now());
                b.innerHTML = '<i class="bi bi-envelope"></i> Yes — send to ' + esc(m.to_addr || '');
                b.dataset.wasFailed = m.state === 'failed' ? '1' : '';
                rqSay(id, 'It goes from your own mailbox and is kept on this tour. Sending does not confirm the booking.');
                setTimeout(() => { if (b.dataset.armed === '1') { b.dataset.armed = ''; b.innerHTML = b.dataset.wasFailed ? '<i class="bi bi-arrow-repeat"></i> Try sending again' : '<i class="bi bi-envelope"></i> Send by e-mail'; rqSay(id, ''); } }, 10000);
                return;
            }
            b.dataset.armed = ''; b.disabled = true; b.innerHTML = 'Sending…';
            const j = await post('/request/' + id + '/send', {});
            b.disabled = false; b.innerHTML = '<i class="bi bi-envelope"></i> Send by e-mail';
            rqSay(id, j.message || (j.status === 200 ? 'Sent.' : 'It was not sent.'), j.status !== 200);
            loadRequests(TOUR.id);
        }));
        // the same words, for WhatsApp
        document.querySelectorAll('[data-rqcopy]').forEach(b => b.addEventListener('click', async () => {
            const id = b.dataset.rqcopy, m = rqMsg(id);
            try { await navigator.clipboard.writeText(String(m.body || '')); rqSay(id, 'Copied. Paste it into WhatsApp' + (m.to_phone ? ' to ' + m.to_phone : '') + ', then press "Record as sent by hand".'); }
            catch (e) {
                const ta = document.createElement('textarea'); ta.value = String(m.body || ''); ta.style.width = '100%'; ta.rows = 10;
                const row = b.closest('tr'); row.querySelector('td:nth-child(2)').appendChild(ta); ta.select();
                rqSay(id, 'Copy the text above by hand (the browser would not do it for us), then record it as sent.', true);
            }
        }));
        document.querySelectorAll('[data-rqprint]').forEach(b => b.addEventListener('click', () => {
            const m = rqMsg(b.dataset.rqprint);
            const w = window.open('', '_blank');
            if (!w) { rqSay(b.dataset.rqprint, 'The browser blocked the print window.', true); return; }
            w.document.write('<!doctype html><html><head><meta charset="utf-8"><title>' + esc(m.subject || 'Booking request') + '</title></head><body style="font:13px/1.6 Arial,Helvetica,sans-serif;padding:28px">'
                + '<h3 style="margin:0 0 12px">' + esc(m.subject || '') + '</h3><pre style="white-space:pre-wrap;font:13px/1.6 Arial,Helvetica,sans-serif">' + esc(m.body || '') + '</pre></body></html>');
            w.document.close(); w.focus(); w.print();
        }));
        document.querySelectorAll('[data-rqhand]').forEach(b => b.addEventListener('click', () => rqNote(b.dataset.rqhand, 'request')));
        document.querySelectorAll('[data-rqreply]').forEach(b => b.addEventListener('click', () => rqNote(b.dataset.rqreply, 'reply')));
        const r = document.getElementById('rq_refresh');
        if (r && !r.dataset.wired) { r.dataset.wired = '1'; r.addEventListener('click', () => loadRequests(TOUR.id)); }
    }

    // a small box under the row: how it went out by hand, or what came back. No browser dialog.
    function rqNote(id, kind) {
        const row = document.querySelector('[data-rq="' + id + '"]');
        if (!row || row.nextElementSibling?.classList.contains('rq-note')) { return; }
        const tr = document.createElement('tr');
        tr.className = 'rq-note';
        tr.innerHTML = `<td colspan="4" style="background:#fbfcfe">
            <div style="font-weight:600;margin-bottom:6px">${kind === 'reply' ? 'What came back' : 'How it was sent by hand'}</div>
            <div class="d-flex flex-wrap gap-2 align-items-end">
                <div><label class="help">By</label><select class="form-select form-select-sm" id="rqn_ch_${id}" style="min-width:150px">
                    <option value="whatsapp">WhatsApp</option><option value="phone">Telephone</option><option value="print">Printed / handed over</option><option value="email">E-mail outside the system</option></select></div>
                <div style="flex:1;min-width:260px"><label class="help">${kind === 'reply' ? 'What they said' : 'Anything to note'}</label><input class="form-control form-control-sm" id="rqn_note_${id}" placeholder="${kind === 'reply' ? 'e.g. available, will confirm the registration tomorrow' : 'optional'}"></div>
                <button class="btn btn-sm btn-success" id="rqn_ok_${id}">Record it</button>
                <button class="btn btn-sm btn-light" id="rqn_x_${id}">Close</button>
            </div>
            <div class="help mt-1">Recording this does not confirm the booking — the vehicle, driver or guide stays pending until you record the confirmation on its card.</div></td>`;
        row.parentNode.insertBefore(tr, row.nextSibling);
        document.getElementById('rqn_x_' + id).addEventListener('click', () => tr.remove());
        document.getElementById('rqn_ok_' + id).addEventListener('click', async () => {
            const ch = document.getElementById('rqn_ch_' + id).value;
            const note = document.getElementById('rqn_note_' + id).value.trim();
            if (kind === 'reply' && note === '') { rqSay(id, 'Say what they answered.', true); return; }
            const j = await post('/request/' + id + '/record', { channel: ch === 'phone' ? 'other' : ch, note: note, kind: kind });
            rqSay(id, j.message || 'Recorded.', j.status !== 200);
            tr.remove();
            loadRequests(TOUR.id);
        });
    }

    function backToList() {
        TOUR = null;
        document.getElementById('tour_view').style.display = 'none';
        document.getElementById('list_card').style.display = '';
        if (window.TfView) { if (!backToList.fromHistory) TfView.setParams({ tour: null }, { push: true }); } else { try { history.replaceState(null, '', location.pathname); } catch (e) {} }
        loadTours();
    }
    async function refreshTour() {
        if (!TOUR) { loadTours(); return; }
        await openTour(TOUR.id, true);
        loadTours();
    }
    const load = refreshTour;   // the card's actions call load() after every change
    function renderTourHead() {
        const t = TOUR, s = t.strip || {};
        const mark = t.stage === 'cancelled' ? '' : (s.transport_complete
            ? `<span class="pill pill-green" style="font-size:12px;padding:4px 10px"><i class="bi bi-patch-check-fill"></i> Transport work complete · ${esc(s.transport_complete_by || '')} ${when(s.transport_complete_at)}</span> <button class="btn btn-light btn-sm" data-unmark="${t.id}" title="Take the mark off (say why)">undo</button>`
            : (s.transport_ready
                ? `<button class="btn btn-success btn-sm" data-mark="${t.id}"><i class="bi bi-patch-check"></i> Mark transport work complete</button>`
                : (t.work === 'none' ? '<span class="help">No vehicle card on this tour.</span>' : `<span class="help" title="Every vehicle set, every driver and guide engaged">Mark complete once every vehicle, driver and guide is allocated</span>`)));
        document.getElementById('tv_head').innerHTML = `
            <div>
                <button class="btn btn-light btn-sm mb-2" id="tv_back"><i class="bi bi-arrow-left"></i> All tours</button>
                <div class="who">${esc(t.tour_no)} · ${esc(t.tour_name || '—')} <small>· ${esc(t.agent_name || '—')} · inquiry ${esc(t.inquiry_no || '—')}</small></div>
                <div class="help">${t.dates_line ? esc(t.dates_line) : `${fmtY(t.arrival_date)} – ${fmtY(t.departure_date)} · ${t.nights ?? '?'} nights`} · ${esc(t.pax_total ?? '?')} pax · handled by ${t.handler_name ? `<b>${esc(t.handler_name)}</b>` : '<span class="pill pill-amber">nobody</span>'}${t.quote_version ? ' · sold as v' + t.quote_version : ''}${t.stage === 'cancelled' ? ' · <span class="pill pill-grey">cancelled</span>' : ''}</div>
                ${cancelFlag(t, true)}
                <div class="mt-2" id="tv_strip">${window.tdStrip(s, { compact: true })}</div>
            </div>
            <div class="tv-mark">${mark}<a class="btn btn-light btn-sm" href="${BASE}/tour/${t.id}/programme" target="_blank"><i class="bi bi-printer"></i> Print the programme</a></div>`;
        document.getElementById('tv_back').addEventListener('click', backToList);
        document.querySelectorAll('#tv_head [data-mark]').forEach(b => b.addEventListener('click', () => markTour(Number(b.dataset.mark), true)));
        document.querySelectorAll('#tv_head [data-unmark]').forEach(b => b.addEventListener('click', () => markTour(Number(b.dataset.unmark), false)));
    }
    async function markTour(tourId, on) {
        let why = '';
        if (!on) {
            const r = await Swal.fire({ title: 'Take the mark off?', input: 'text', inputPlaceholder: 'Why (required, stays on the trail)', showCancelButton: true, confirmButtonText: 'Take it off' });
            if (!r.isConfirmed) return;
            why = r.value || '';
        } else {
            const r = await Swal.fire({ title: 'Transport work complete?', text: 'Every vehicle on this tour is set and every driver and guide engaged. The tour leaves the Transport count and stays under Completed; the inquiry, the Hotel Desk and the Board will show the mark.', icon: 'question', showCancelButton: true, confirmButtonText: 'Mark it complete', confirmButtonColor: '#1f8a4c' });
            if (!r.isConfirmed) return;
        }
        const j = await post('/tour/' + tourId + '/complete', { on: on ? 1 : 0, why });
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); refreshTour();
    }
    function renderVehicles() {
        const items = ITEMS.filter(r => !r.archived_at);
        const tb = document.getElementById('rows');
        if (!items.length) { tb.innerHTML = '<tr><td colspan="8" class="empty">No vehicle card on this tour.</td></tr>'; return; }
        tb.innerHTML = items.map(r => `<tr>
            <td><b>${esc(r.vehicle_type)}</b>${r.seats ? ` <span class="help">· ${r.seats} seats</span>` : ''}${Number(r.qty) > 1 ? ` <span class="pill pill-blue">× ${r.qty}</span>` : ''}<div class="help">${r.vehicle_reg ? esc(r.vehicle_reg) : '<span class="pill pill-amber">no vehicle yet</span>'}</div>${Number(r.amended) ? '<span class="flag">Quotation amended — check the card</span>' : ''}</td>
            <td>${r.driver_name ? esc(r.driver_name) : '<span class="help">no driver yet</span>'}${r.driver_status === 'engaged' ? ' <span class="pill pill-green">engaged</span>' : (r.driver_status === 'declined' ? ' <span class="pill pill-red">declined</span>' : '')}</td>
            <td>${r.guide_of ? (r.guide_name ? esc(r.guide_name) : '<span class="help">no guide yet</span>') + (r.guide_status === 'engaged' ? ' <span class="pill pill-green">engaged</span>' : (r.guide_status === 'declined' ? ' <span class="pill pill-red">declined</span>' : '')) : '<span class="help">not wanted</span>'}</td>
            <td class="mono">${fmt(r.arrival_date)} – ${fmtY(r.departure_date)}</td>
            <td class="mono">${r.days ?? '—'} · ${num(r.km_total)} km</td>
            <td class="mono">${r.docs_before} of ${r.docs_before_of}${['pack_issued', 'running', 'returned', 'settled'].includes(r.status) ? ' <span class="pill pill-teal" title="Pack issued">✓</span>' : ''}</td>
            <td>${pill(r.status)}</td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-open="${r.id}">Open</button>${CAN_MANAGE ? ` <button class="td-dots" data-manage="${r.id}" title="Manage" type="button">···</button>` : ''}</td></tr>`).join('');
        tb.querySelectorAll('[data-open]').forEach(b => b.addEventListener('click', () => openCard(Number(b.dataset.open))));
        tb.querySelectorAll('[data-manage]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); const c = ITEMS.find(x => Number(x.id) === Number(b.dataset.manage)); if (c) manageMenu(b, c); }));
    }

    // ---------- the ⋯ Manage menu: archive, restore (Management; the server checks again) ----------
    function manageMenu(btn, c) {
        const items = [{ title: `${c.tour_no} · ${c.vehicle_type}` }];
        if (c.archived_at) {
            items.push({ label: 'Restore to the desk', icon: 'bi-arrow-counterclockwise', hint: 'Back in the queue as it was', onClick: () => act2('/transport_card/' + c.id + '/restore', {}) });
        } else {
            const busy = !['to_allocate', 'settled', 'cancelled'].includes(c.status);
            items.push({ label: 'Archive…', icon: 'bi-archive', hint: busy ? 'Only a card still to allocate, settled or cancelled' : 'Off the desk, kept, restorable', disabled: busy, onClick: async () => {
                const r = await Swal.fire({ title: 'Archive this vehicle card?', input: 'text', inputPlaceholder: 'Why (optional, stays on the history)', showCancelButton: true, confirmButtonText: 'Archive' });
                if (r.isConfirmed) act2('/transport_card/' + c.id + '/archive', { note: r.value || '' });
            } });
        }
        if (CAN_INTAKE) items.push({ sep: true });
        if (CAN_INTAKE) items.push({ label: 'Open the inquiry', icon: 'bi-box-arrow-up-right', hint: 'The quotation and its history', onClick: () => window.open(INTAKE + '?id=' + (c.intake_id || ''), '_blank') });
        window.tdMenu(btn, items);
    }
    async function act2(path, body) {
        const j = await manage(path, body);
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message);
        if (CARD && j.data && Number(j.data.id) === Number(CARD.id)) { CARD = j.data; renderCard(); }
        load();
    }

    async function masters() {
        if (MASTERS.loaded) return MASTERS;
        const j = await api('/masters');
        MASTERS = Object.assign({ loaded: true }, j.data || {});
        if (MASTERS.error) toast('error', 'The vehicle master or the freelancer list could not be read: ' + MASTERS.error);
        return MASTERS;
    }

    async function openCard(id) {
        await masters();
        const j = await api('/card/' + id);
        if (j.status !== 200) { toast('error', j.message); return; }
        CARD = j.data; renderCard();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_card')).show();
    }
    // the tour as this desk needs it, read fresh each time the card opens (a stay confirmed on
    // the other desk a minute ago is already here) — the same block on every desk
    let DETAILS = {};
    async function loadDetails(tourId, boxId) {
        const box = document.getElementById(boxId || 'c_details');
        if (!box) return;
        const j = await api('/tour/' + tourId + '/details');
        if (j.status !== 200) { box.innerHTML = `<div class="help">${esc(j.message || 'The tour details could not be read.')}</div>`; return; }
        DETAILS[tourId] = j.data;
        box.innerHTML = window.tdDetails(j.data, { print: BASE + '/tour/' + tourId + '/programme' });
    }

    // ------------------------------------------------------------------
    // tf-crew-master-r1: the allocation picker
    // A JSD / JSCG / JSNTG line (a GUIDE_JP line counts as JSNTG) is filled from the Driver & Guide Master, STRICTLY by
    // its category: the picker opens on that category alone and lists nobody else — a filter, never a sort. Inactive
    // and archived people are not on the list at all; an expired licence or document, a category still to confirm,
    // is in front of the person allocating before they can go on. A line outside the three (ESD, GUIDE_EN …) keeps the
    // list of the people serving its code.
    // ------------------------------------------------------------------
    const lineCat = k => (MASTERS.line_categories || {})[String(k.role_code || '').toUpperCase()] || null;
    const peopleFor = k => { const cat = lineCat(k); return (MASTERS.people || []).filter(p => p.kind === 'both' || p.kind === k.kind).filter(p => cat ? p.category === cat : (p.codes || []).includes(k.role_code)).map(p => Object.assign({}, p, { serves: true })).sort((a, b) => (a.nickname || a.name).localeCompare(b.nickname || b.name)); };
    const initials = nm => String(nm || '?').trim().split(/\s+/).slice(0, 2).map(s => s[0] || '').join('').toUpperCase() || '?';
    const avatarHtml = (p, fallback) => { const url = p && p.photo ? ROOT + '/' + p.photo : null; return `<div class="pk-avatar">${url ? `<img src="${esc(url)}" alt="">` : esc(initials(p ? (p.nickname || p.name) : fallback))}</div>`; };
    const docPill = (label, expiry, expired) => expiry
        ? `<span class="pill ${expired ? 'pill-red' : 'pill-green'}" title="${esc(label)} ${expired ? 'expired' : 'valid to'} ${esc(expiry)}">${esc(label)} ${expired ? '<b>expired</b> ' : 'to '}${esc(expiry)}</span>`
        : `<span class="pill pill-grey" title="${esc(label)}: expiry not on the master">${esc(label)} — not on file</span>`;
    const vehicleHtml = p => {
        const v = p.vehicle;
        if (p.category === 'JSNTG') return '<span class="help">no vehicle — the guide only; the driver is allocated on the driver line</span>';
        if (!v) return p.vehicle_reg ? `<span class="mono">${esc(p.vehicle_reg)}</span> <span class="help">details not on the master</span>` : '<span class="pill pill-amber">no vehicle on file</span>';
        return `<b class="mono">${esc(v.reg_no || '—')}</b> ${esc(v.type_name || '')}${v.label ? ' · ' + esc(v.label) : ''}${v.vehicle_type === 'van' ? ' <span class="roof" title="Roof type — for the transport staff">' + esc(v.roof_name || 'ROOF TYPE NOT SET') + '</span>' : ''}${v.seats ? ` <span class="help">· ${v.seats} seats${v.max_pax_luggage ? ', ' + v.max_pax_luggage + ' pax with luggage' : ''}</span>` : ''}`;
    };
    function pickCardHtml(p) {
        const cat = p.category, dr = cat === 'JSD' || cat === 'JSCG', gd = cat === 'JSCG' || cat === 'JSNTG';
        const warns = (p.warnings || []).filter(w => w.level !== 'info' && w.level !== 'block');
        const blocked = (p.warnings || []).some(w => w.level === 'block') || p.status !== 'active';
        const docs = [];
        if (dr) docs.push(docPill('Driving licence', p.licence_expiry, p.driving_licence_expired));
        if (gd) docs.push(docPill('Guide licence', p.guide_licence_expiry, p.guide_licence_expired));
        if (dr && p.vehicle) { docs.push(docPill('Insurance', p.vehicle.insurance_expiry, p.vehicle.insurance_expired)); docs.push(docPill('Revenue licence', p.vehicle.licence_expiry, p.vehicle.licence_expired)); }
        const rate = dr ? (p.km_rate != null
            ? `<b class="mono">LKR ${num(p.km_rate)}</b> per km <span class="pill ${p.rate_source === 'special' ? 'pill-teal' : 'pill-grey'}">${p.rate_source === 'special' ? 'special' : 'general'}</span> <span class="help">${p.rate_source === 'special' ? (p.general_rate != null ? 'overrides the general ' + num(p.general_rate) + (p.rate_class_name ? ' (' + esc(p.rate_class_name) + ')' : '') : 'agreed with this driver') : esc(p.rate_class_name || '') + ' · Transport Rates master'}</span>`
            : `<span class="pill pill-amber">no rate per km</span> <span class="help">${p.vehicle ? 'no vehicle class on the master' : 'no vehicle on file'} — the quoted class rate would apply</span>`) : '';
        // the bata (guide fee) in force: the person's own agreed figure, else the standard — with which one it is
        const bataPill = p.bata_source === 'agreed' ? '<span class="pill pill-teal">agreed</span>' : (p.bata_source === 'standard' ? '<span class="pill pill-grey">standard</span>' : '');
        const bataWhy = p.bata_source === 'agreed' ? (p.standard_bata != null ? 'overrides the standard ' + num(p.standard_bata) : 'agreed with this person') + (p.bata_from ? ' · from ' + esc(p.bata_from) : '') : (p.bata_source === 'standard' ? 'the standard ' + (cat === 'JSNTG' ? 'guide fee' : 'bata') + (p.bata_from ? ' from ' + esc(p.bata_from) : '') : '');
        const guide = cat === 'JSNTG'
            ? (p.bata != null ? `<b class="mono">LKR ${num(p.bata)}</b> per day ${bataPill} <span class="help">${bataWhy}</span>${p.overnight_bata != null ? ` · <b class="mono">LKR ${num(p.overnight_bata)}</b> per night <span class="help">overnight allowance${p.overnight_source === 'agreed' ? ' (agreed)' : ' (standard)'} — not in the voucher amount</span>` : ' <span class="help">· no overnight allowance set</span>'}` : '<span class="pill pill-amber">no guide fee — none agreed and no standard set</span>')
            : (p.bata != null ? `<b class="mono">LKR ${num(p.bata)}</b> per day ${bataPill} <span class="help">${bataWhy}</span>` : '<span class="help">no bata — none agreed and no standard set; the Crew Bata master\'s rate applies</span>');
        return `<div class="pk-card ${blocked ? 'block' : (warns.length ? 'warn' : '')}" data-pk="${p.id}">
            <div class="pk-top">${avatarHtml(p)}<div style="min-width:0;flex:1"><div class="pk-name">${p.nickname ? esc(p.nickname) : esc(p.name)}<small>${p.nickname ? esc(p.name) : '&nbsp;'}</small></div><div class="mt-1"><span class="pk-cat">${esc(cat)}</span>${p.category_confirmed ? '' : ' <span class="pill pill-amber" title="Brought over from the driver registration; confirm the category on the master">category to confirm</span>'}${p.languages ? ' <span class="help">' + esc(p.languages) + '</span>' : ''}</div></div></div>
            <div class="pk-line"><span class="lbl">Contact</span><span>${[p.phone, p.whatsapp && p.whatsapp !== p.phone ? 'WhatsApp ' + p.whatsapp : null].filter(Boolean).map(esc).join(' · ') || '<span class="help">—</span>'}</span></div>
            ${dr ? `<div class="pk-line"><span class="lbl">Vehicle</span><span>${vehicleHtml(p)}</span></div>` : ''}
            <div class="pk-line"><span class="lbl">Papers</span><span class="pk-docs">${docs.join('')}</span></div>
            ${rate ? `<div class="pk-line"><span class="lbl">Rate</span><span>${rate}</span></div>` : ''}
            <div class="pk-line"><span class="lbl">${cat === 'JSNTG' ? 'Rate' : 'Bata'}</span><span>${guide}</span></div>
            ${blocked ? `<div class="pk-block">${esc(((p.warnings || []).find(w => w.level === 'block') || {}).text || 'Not available — inactive on the master.')}</div>` : (warns.length ? `<div class="pk-warn"><b>Check before allocating</b><ul>${warns.map(w => `<li>${esc(w.text)}</li>`).join('')}</ul></div>` : '')}
            <div class="pk-foot"><span class="help">${p.engagements ? p.engagements + ' tour' + (Number(p.engagements) === 1 ? '' : 's') + ' so far' : 'first tour with us'}${p.acc ? ' · ' + esc(p.acc) : ''}</span>${blocked ? '<button class="btn btn-light btn-sm" disabled>Not available</button>' : `<button class="btn btn-sm ${warns.length ? 'btn-warning' : 'btn-pick'}" data-pick="${p.id}">${warns.length ? 'Allocate anyway…' : 'Allocate'}</button>`}</div>
        </div>`;
    }
    let PICK = null;   // the picker open: the line, its category, the people, what happens on a choice
    async function openPicker(k, title, onChoose) {
        MASTERS.loaded = false; await masters();   // a person added or activated on the master a moment ago is here
        const cat = lineCat(k);
        PICK = { k, cat, people: peopleFor(k), onChoose, chip: 'all' };
        document.getElementById('pk_title').textContent = `${title} — ${cat ? cat + ' · ' + ((MASTERS.category_names || {})[cat] || '') : k.role_name}`;
        document.getElementById('pk_sub').innerHTML = `Tour <b>${esc(CARD.tour_no)}</b>${CARD.tour_name ? ' · ' + esc(CARD.tour_name) : ''} · ${esc(k.role_name)} · quoted ${k.days ?? '?'} day(s)${cat ? ` · <b>only ${esc(cat)}s on the Driver &amp; Guide Master are listed</b> — a strict filter by category, never a sort` : ''}`;
        document.getElementById('pk_q').value = '';
        renderPicker();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_pick')).show();
        setTimeout(() => document.getElementById('pk_q').focus(), 300);
    }
    function renderPicker() {
        if (!PICK) return;
        const q = document.getElementById('pk_q').value.trim().toLowerCase();
        const drv = PICK.cat !== 'JSNTG' && PICK.k.kind === 'driver';
        const chips = [['all', 'Everyone'], ['ready', 'Papers in order']].concat(drv ? [['car', 'Car'], ['van', 'Van'], ['high', 'High-roof van'], ['minibus', 'Minibus'], ['bus', 'Bus']] : []);
        document.getElementById('pk_chips').innerHTML = chips.map(([k, l]) => `<button class="chip ${PICK.chip === k ? 'on' : ''}" data-chip="${k}">${l}</button>`).join('');
        document.querySelectorAll('#pk_chips [data-chip]').forEach(b => b.addEventListener('click', () => { PICK.chip = b.dataset.chip; renderPicker(); }));
        // the people whose papers are in order first, then those with something to check, each group by name
        const grade = p => (p.warnings || []).some(w => w.level === 'block') ? 2 : ((p.warnings || []).some(w => w.level !== 'info') ? 1 : 0);
        let rows = PICK.people.slice().sort((a, b) => (grade(a) - grade(b)) || (a.nickname || a.name).localeCompare(b.nickname || b.name));
        if (q) rows = rows.filter(p => [p.name, p.nickname, p.phone, p.whatsapp, p.acc, p.vehicle_reg, p.vehicle && p.vehicle.label, p.languages].filter(Boolean).join(' ').toLowerCase().includes(q));
        if (PICK.chip === 'ready') rows = rows.filter(p => !(p.warnings || []).some(w => w.level !== 'info'));
        else if (['car', 'van', 'minibus', 'bus'].includes(PICK.chip)) rows = rows.filter(p => p.vehicle && p.vehicle.vehicle_type === PICK.chip);
        else if (PICK.chip === 'high') rows = rows.filter(p => p.vehicle && p.vehicle.vehicle_type === 'van' && p.vehicle.roof_type === 'high');
        document.getElementById('pk_count').textContent = `${rows.length} of ${PICK.people.length} ${PICK.cat ? PICK.cat + (PICK.people.length === 1 ? '' : 's') : 'people'} on the master`;
        const grid = document.getElementById('pk_grid');
        grid.innerHTML = rows.length ? rows.map(pickCardHtml).join('') : `<div class="pk-empty">${PICK.people.length ? 'Nobody matches — clear the search or the filter.' : `No ${esc(PICK.cat || 'one')} is active on the Driver &amp; Guide Master yet. Add them there (or confirm a record still to classify) and they appear here at once.`}</div>`;
        document.getElementById('pk_foot').innerHTML = `Inactive and archived people are never offered. Not on the list? Add or activate the person on the <a href="${MASTER_URL}" target="_blank">Driver &amp; Guide Master</a> — the list here follows the master; nothing is typed by hand.`;
        grid.querySelectorAll('[data-pick]').forEach(b => b.addEventListener('click', () => choosePerson(Number(b.dataset.pick))));
    }
    // the choice, confirmed with what the person brings — the rate per km or the guide rate, the own bata — and the
    // warnings once more, in front of the person allocating, before anything is written
    async function choosePerson(id) {
        const p = PICK.people.find(x => Number(x.id) === id); if (!p) return;
        const k = PICK.k, cat = PICK.cat;
        const warns = (p.warnings || []).filter(w => w.level !== 'info');
        const dr = k.kind === 'driver';
        const lines = [
            `<div class="pk-top" style="text-align:left">${avatarHtml(p)}<div><div class="pk-name">${p.nickname ? esc(p.nickname) : esc(p.name)}<small>${p.nickname ? esc(p.name) : ''}</small></div><div class="mt-1"><span class="pk-cat">${esc(cat || '')}</span> <span class="help">for ${esc(k.role_name)} on tour ${esc(CARD.tour_no)}</span></div></div></div>`,
            dr ? `<div class="pk-line mt-2"><span class="lbl">Vehicle</span><span>${vehicleHtml(p)}</span></div>` : '',
            dr ? `<div class="pk-line mt-1"><span class="lbl">Rate</span><span>${p.km_rate != null ? `<b class="mono">LKR ${num(p.km_rate)}</b> per km — the ${p.rate_source === 'special' ? 'driver\'s special rate' : 'general rate of the ' + esc(p.rate_class_name || 'vehicle class')} — copied onto this tour now; a later change on the master never moves it` : 'no special rate and no vehicle class on the master — the quoted class rate applies'}</span></div>` : '',
            `<div class="pk-line mt-1"><span class="lbl">${k.kind === 'guide' ? 'Guide fee' : 'Bata'}</span><span>${p.bata != null ? `<b class="mono">LKR ${num(p.bata)}</b> per day — ${p.bata_source === 'agreed' ? 'agreed with ' + esc(p.nickname || p.name) + (p.standard_bata != null ? ' (overrides the standard ' + num(p.standard_bata) + ')' : '') : 'the standard ' + (k.kind === 'guide' ? 'guide fee' : 'bata')}${p.bata_from ? ', in force from ' + esc(p.bata_from) : ''} — copied onto this tour now; a later change on the master never moves it` : 'none agreed and no standard set — the Crew Bata master\'s rate for the role applies'}</span></div>`,
            k.kind === 'guide' ? `<div class="pk-line mt-1"><span class="lbl">Overnight</span><span>${p.overnight_bata != null ? `<b class="mono">LKR ${num(p.overnight_bata)}</b> per night${p.overnight_source === 'agreed' ? ' agreed' : ' (standard)'}${p.overnight_from ? ' from ' + esc(p.overnight_from) : ''} — copied for information; it is not added to the voucher amount` : 'no overnight allowance set — nothing is added for nights away'}</span></div>` : '',
            warns.length ? `<div class="pk-warn mt-2"><b>Check before allocating</b><ul>${warns.map(w => `<li>${esc(w.text)}</li>`).join('')}</ul></div>` : '',
            `<div class="help mt-3">${k.kind === 'guide' ? 'Guide fee per day for this tour (LKR)' : 'Bata per day for this tour (LKR)'} — ${p.bata != null ? 'as on the master (' + (p.bata_source === 'agreed' ? 'agreed' : 'standard') + '); a different figure typed here applies to this tour only' : 'leave blank for the Crew Bata master’s rate'}</div>`,
        ].filter(Boolean).join('');
        const r = await Swal.fire({
            title: warns.length ? 'Allocate anyway?' : 'Allocate?', html: `<div style="text-align:left;font-size:13px" class="tf">${lines}</div>`, width: 560,
            input: 'number', inputValue: p.bata != null ? p.bata : '', inputPlaceholder: 'LKR per day', inputAttributes: { step: '0.01', min: '0' },
            showCancelButton: true, confirmButtonText: warns.length ? 'Allocate anyway' : 'Allocate', cancelButtonText: 'Back to the list', confirmButtonColor: warns.length ? '#b7791f' : '#1e3553',
        });
        if (!r.isConfirmed) return;
        const ok = await PICK.onChoose(p, r.value === '' || r.value == null ? '' : r.value, warns.length > 0);
        if (ok) bootstrap.Modal.getInstance(document.getElementById('m_pick'))?.hide();
    }
    document.getElementById('pk_q').addEventListener('input', renderPicker);
    // the picker sits over the card: when it closes, the card underneath keeps scrolling
    document.getElementById('m_pick').addEventListener('hidden.bs.modal', () => { if (document.querySelector('#m_card.show')) document.body.classList.add('modal-open'); });
    // the engagement from the picker: the person's id only — their name, phone, account, category and rate come from
    // the master on the server, which checks the category strictly once more and asks about any warning not yet seen
    async function crewPick(crewId, p, bata, acknowledged) {
        if (!CAN_ALLOCATE) { toast('error', NO_ALLOC); return false; }
        const j = await withWarning(ack => post('/crew/' + crewId, { person_id: p.id, person_name: '', supplier_acc: '', person_bata: bata, status: 'engaged', acknowledge: ack || acknowledged }));
        if (!j) return false;
        if (j.status !== 200) { toast('error', j.message); return false; }
        CARD = j.data; renderCard(); toast('success', j.message); load();
        return true;
    }

    function renderCard() {
        const c = CARD;
        document.getElementById('c_title').textContent = `${c.tour_no} · ${c.tour_name || ''} · ${c.vehicle_type}${Number(c.qty) > 1 ? ' × ' + c.qty : ''}`;
        const rung = Math.max(0, LADDER.indexOf(c.status));
        const next = c.status !== 'cancelled' && rung < LADDER.length - 1 ? LADDER[rung + 1] : null;
        const prev = rung > 0 && c.status !== 'cancelled' ? LADDER[rung - 1] : null;
        const ladder = LADDER.map((s, i) => `<span class="rung ${i < rung ? 'done' : (i === rung ? 'now' : '')}">${esc(LABEL[s])}</span>`).join('<span class="help">›</span>');

        const vehOpts = '<option value="">— choose from the vehicle master —</option>' + (MASTERS.vehicles || []).map(v =>
            `<option value="${v.id}" ${Number(c.vehicle_id) === v.id ? 'selected' : ''}>${esc(v.type)}${v.reg ? ' · ' + esc(v.reg) : ''}${v.seats ? ' · ' + v.seats + ' seats' : ''}${v.flagged ? ' · flagged' : ''}</option>`).join('');

        // One line per PERSON. A person replaced mid-tour keeps their line, ended on their last
        // day; the replacement has a line of their own from their first day. Each line carries
        // the days worked and, once issued, the payment voucher for exactly those days.
        const dated = !!c.crew_dated;
        // tf-masters-r3: the people on the Drivers & Guides master, those serving the line's category first
        // tf-crew-master-r1: STRICT — a JSD / JSCG / JSNTG (or GUIDE_JP) line lists its own category only; a line outside
        // the three (ESD, GUIDE_EN …) lists the people serving its code. Never a visual sort: nobody else is in the list.
        const personOption = (p, selectedId) => `<option value="${p.id}" ${Number(selectedId) === Number(p.id) ? 'selected' : ''}>${esc(p.name)}${p.nickname ? ' “' + esc(p.nickname) + '”' : ''}${p.category ? ' · ' + esc(p.category) + (p.category_confirmed ? '' : ' (to confirm)') : ((p.codes || []).length ? ' · ' + esc(p.codes.join(', ')) : '')}${p.languages ? ' · ' + esc(p.languages) : ''}${p.bata != null ? ' · own bata ' + num(p.bata) : ''}${p.licence_expired ? ' · licence expired' : ''}</option>`;
        const peopleSelect = (k, id, selectedId) => {
            const people = peopleFor(k);
            const first = people.filter(p => p.serves), rest = people.filter(p => !p.serves);
            const cat = lineCat(k);
            const label = cat ? `${cat} — ${esc((MASTERS.category_names || {})[cat] || '')}` : esc(k.role_name);
            if (cat && !first.length) return `<select class="form-select form-select-sm mt-1" data-crewperson="${k.id}" id="${id}"><option value="">— no ${cat} on the Driver &amp; Guide Master yet —</option></select>`;
            return `<select class="form-select form-select-sm mt-1" data-crewperson="${k.id}" id="${id}"><option value="">— ${cat ? cat + ' from the Driver &amp; Guide Master' : 'from the Drivers &amp; Guides master'} —</option>${first.length ? `<optgroup label="${label}">${first.map(p => personOption(p, selectedId)).join('')}</optgroup>` : ''}${rest.length ? `<optgroup label="Other ${esc(k.kind)}s">${rest.map(p => personOption(p, selectedId)).join('')}</optgroup>` : ''}</select>`;
        };
        // tf-crew-master-r1: who is paid, so nobody pays the wrong person — nickname and legal name side by side, the
        // category, and for the bank privilege the account name as the bank has it, the bank, the account number
        const payeeHtml = k => {
            const p = k.payee; if (!p || !['engaged', 'replaced'].includes(k.status)) return '';
            const bits = [`<b>${esc(p.name)}</b>${p.nickname ? ` “${esc(p.nickname)}”` : ''}`, p.category ? `<span class="pill pill-blue">${esc(p.category)}</span>${p.category_confirmed ? '' : ' <span class="pill pill-amber">to confirm</span>'}` : ''];
            if (p.bank_visible) bits.push(p.bank_account_no ? [`pay to <b>${esc(p.bank_account_name || 'account name not on file')}</b>`, [p.bank_name, p.bank_branch].filter(Boolean).map(esc).join(' '), `A/C <span class="mono">${esc(p.bank_account_no)}</span>`].filter(Boolean).join(' · ') + ' ' + (p.bank_verified ? '<span class="pill pill-green">verified</span>' : '<span class="pill pill-amber">not verified</span>') : '<span class="pill pill-amber">no bank account on the master</span>');
            const w = (p.warnings || []).filter(x => x.level !== 'info');
            return `<div class="help" style="margin-top:3px">${bits.filter(Boolean).join(' · ')}${w.length ? `<div style="color:var(--tf-amber)">${w.map(x => esc(x.text)).join(' ')}</div>` : ''}</div>`;
        };
        // tf-crew-master-r1: the person allocated on a JSD / JSCG / JSNTG line, as a card — photo, nickname, legal name,
        // category, vehicle and roof, the rate copied when engaged, the bank for the privilege, the warnings — with
        // Change… (another person of the same category from the picker) and ✕ (declined)
        const allocHtml = k => {
            const cat = lineCat(k);
            const n = peopleFor(k).length;
            const acts = `<div class="acts">${k.status === 'engaged' ? `<button class="btn btn-light btn-sm" data-pickopen="${k.id}" title="Another ${esc(cat)} from the master takes this line before the tour; mid-tour, use Replace…">Change…</button>` : ''}<button class="btn btn-light btn-sm" data-decline="${k.id}" title="${k.status === 'engaged' ? 'The person declined the job — the line is open again' : 'Nobody for this line — declined'}">✕</button></div>`;
            if (k.status !== 'engaged' || !k.person_name) {
                return `<div class="alloc-empty"><button class="btn btn-pick btn-sm" data-pickopen="${k.id}"><i class="bi bi-person-plus"></i> Choose a ${esc(cat)} from the master…</button><span class="help">only ${esc(cat)}s are listed · ${n} available${k.status === 'declined' ? ' · the last person declined' : ''}</span>${k.status === 'engaged' ? '' : ''}</div>`;
            }
            const p = k.payee;
            if (!p) {
                return `<div class="alloc">${avatarHtml(null, k.person_name)}<div style="min-width:0;flex:1"><div class="pk-name">${esc(k.person_name)}<small>typed by hand before the master — not a record on the Driver &amp; Guide Master</small></div><div class="help">${[k.person_phone, k.supplier_acc].filter(Boolean).map(esc).join(' · ')}</div><div class="pk-warn mt-1">Choose the person from the master with <b>Change…</b> so the vouchers, the pack and the payment name the right record.</div></div>${acts}</div>`;
            }
            const rate = k.kind === 'driver' && k.person_km_rate != null ? `<b class="mono">LKR ${num(k.person_km_rate)}</b> per km <span class="pill ${k.person_rate_source === 'special' ? 'pill-teal' : 'pill-grey'}">${k.person_rate_source === 'special' ? 'special' : (k.person_rate_source === 'general' ? 'general' : 'driver')}</span> <span class="help">copied when engaged${k.person_rate_from ? ' · in force from ' + esc(k.person_rate_from) : ''}${k.person_rate_source === 'general' && p.vehicle && p.vehicle.rate_class_name ? ' · ' + esc(p.vehicle.rate_class_name) : ''}</span>` : (k.kind === 'driver' ? '<span class="pill pill-amber">no rate per km</span> <span class="help">no special rate and no vehicle class on the master — the quoted class rate applies</span>' : '');
            const bsrc = k.person_bata_source === 'agreed' ? '<span class="pill pill-teal">agreed</span>' : (k.person_bata_source === 'standard' ? '<span class="pill pill-grey">standard</span>' : (k.person_bata_source === 'typed' ? '<span class="pill pill-amber">typed for this tour</span>' : ''));
            const bata = k.person_bata != null ? `<b class="mono">LKR ${num(k.person_bata)}</b> per day ${bsrc} <span class="help">${k.kind === 'guide' ? 'guide fee' : 'bata'} copied when engaged${k.person_bata_from ? ' · in force from ' + esc(k.person_bata_from) : ''}</span>${k.person_overnight != null ? ` · <b class="mono">LKR ${num(k.person_overnight)}</b> per night <span class="help">overnight allowance${k.person_overnight_source === 'agreed' ? ' (agreed)' : (k.person_overnight_source === 'standard' ? ' (standard)' : '')}, copied — not in the voucher amount</span>` : ''}` : (k.bata != null ? `<b class="mono">LKR ${num(k.bata)}</b> per day <span class="help">the Crew Bata master</span>` : '<span class="pill pill-amber">no bata</span>');
            const w = (p.warnings || []).filter(x => x.level !== 'info');
            let bank = '';
            if (p.bank_visible) bank = `<div class="pk-line mt-1"><span class="lbl">Pay to</span><span>${p.bank_account_no ? [`<b>${esc(p.bank_account_name || 'account name not on file')}</b>`, [p.bank_name, p.bank_branch].filter(Boolean).map(esc).join(' '), `A/C <span class="mono">${esc(p.bank_account_no)}</span>`].filter(Boolean).join(' · ') + ' ' + (p.bank_verified ? '<span class="pill pill-green">verified</span>' : '<span class="pill pill-amber">not verified</span>') : '<span class="pill pill-amber">no bank account on the master</span>'}</span></div>`;
            return `<div class="alloc">${avatarHtml(p, k.person_name)}<div style="min-width:0;flex:1">
                <div class="pk-name">${p.nickname ? esc(p.nickname) : esc(p.name)}<small>${p.nickname ? esc(p.name) : '&nbsp;'}</small></div>
                <div class="mt-1"><span class="pk-cat">${esc(p.category || '')}</span>${p.category_confirmed ? '' : ' <span class="pill pill-amber">category to confirm</span>'}${p.status !== 'active' || p.archived ? ' <span class="pill pill-red">' + (p.archived ? 'archived' : 'inactive') + ' on the master</span>' : ''}<span class="help"> · ${[p.phone, p.whatsapp && p.whatsapp !== p.phone ? 'WhatsApp ' + p.whatsapp : null, p.supplier_acc].filter(Boolean).map(esc).join(' · ')}</span></div>
                ${k.kind === 'driver' ? `<div class="pk-line mt-1"><span class="lbl">Vehicle</span><span>${vehicleHtml(p)}</span></div>` : ''}
                <div class="pk-line mt-1"><span class="lbl">Rate</span><span>${[rate, bata].filter(Boolean).join(' &nbsp;·&nbsp; ')}</span></div>
                ${bank}
                ${w.length ? `<div class="pk-warn mt-1"><ul>${w.map(x => `<li>${esc(x.text)}</li>`).join('')}</ul></div>` : ''}
            </div>${acts}</div>`;
        };
        const crewHtml = (c.crew || []).map(k => {
            const list = (MASTERS.freelancers || []).filter(f => k.kind === 'guide' ? f.is_guide : f.is_driver);
            const replaced = k.status === 'replaced';
            // the name sits beside the state, so the line reads "engaged — Ruwan Perera" at a glance
            const st = k.status === 'engaged' ? `<span class="pill pill-green">engaged${k.person_name ? ' — ' + esc(k.person_name) : ''}</span>` : (k.status === 'declined' ? '<span class="pill pill-red">declined</span>' : (replaced ? '<span class="pill pill-grey">replaced</span>' : '<span class="pill pill-grey">to engage</span>'));
            const worked = k.days_worked != null ? `${k.days_worked} day${Number(k.days_worked) === 1 ? '' : 's'} worked` : 'days not set';
            const v = k.voucher;
            const canVoucher = (k.status === 'engaged' || replaced) && k.person_name;
            const voucherHtml = !dated ? '' : (v
                ? `<div class="vline"><span class="pill ${v.stale ? 'pill-amber' : 'pill-green'}">voucher ${esc(v.voucher_no)}</span><span>${v.days} day${Number(v.days) === 1 ? '' : 's'} × ${num(v.rate)} = <b>${esc(v.currency)} ${num(v.amount)}</b></span><span class="help">${v.rate_source === 'person' ? (k.person_bata_source === 'standard' ? 'the standard ' + (k.kind === 'guide' ? 'guide fee' : 'bata') + ' copied when engaged' : (k.person_bata_source === 'typed' ? 'the figure typed for this tour' : 'own agreed ' + (k.kind === 'guide' ? 'guide fee' : 'bata'))) : "the master's bata"} · ${when(v.issued_at)} ${esc(v.issued_by || '')}</span>
                    <a class="btn btn-light btn-sm" href="${BASE}/voucher/${v.id}" target="_blank">Print</a><button class="btn btn-light btn-sm" data-vcancel="${v.id}" data-vno="${esc(v.voucher_no)}">Cancel…</button>
                    ${v.stale ? '<div class="help" style="color:var(--tf-amber);flex-basis:100%">The days on the line changed since this voucher was issued — cancel it and issue a fresh one.</div>' : ''}</div>`
                : (canVoucher ? `<div class="vline"><button class="btn btn-light btn-sm" data-vissue="${k.id}" ${k.date_from && k.date_to ? '' : 'disabled title="Set the first and last day first"'}>Issue payment voucher</button><span class="help">${k.date_from && k.date_to ? 'for the ' + worked + ' × ' + (k.person_bata != null ? num(k.person_bata) + (k.person_bata_source === 'standard' ? ' standard ' : (k.person_bata_source === 'typed' ? ' typed ' : ' agreed ')) + (k.kind === 'guide' ? 'guide fee' : 'bata') : (k.bata != null ? num(k.bata) + " the master's bata" : '<b>no bata</b>')) : 'set the first and last day first'}</span></div>` : ''));
            const datesHtml = !dated || k.status === 'declined' ? '' : `<div class="dates"><span class="help">Works</span>
                <input type="date" class="form-control form-control-sm" data-dfrom="${k.id}" value="${esc(k.date_from || '')}"><span class="help">to</span>
                <input type="date" class="form-control form-control-sm" data-dto="${k.id}" value="${esc(k.date_to || '')}">
                <button class="btn btn-light btn-sm" data-dsave="${k.id}">Save days</button><span class="help">${worked}${k.days != null ? ' · quoted ' + k.days : ''}</span></div>`;
            const relation = replaced
                ? `<div class="help">Replaced ${k.replaced_at ? when(k.replaced_at) : ''}${k.replaced_by_name ? ' by <b>' + esc(k.replaced_by_name) + '</b>' : ''}${k.replaced_note ? ' — ' + esc(k.replaced_note) : ''}</div>`
                : (k.replaces_name ? `<div class="help">Took over from <b>${esc(k.replaces_name)}</b>${k.date_from ? ' on ' + fmt(k.date_from) : ''}</div>` : '');
            const hasMaster = (MASTERS.people || []).length > 0;
            const notOnMaster = k.status === 'engaged' && k.person_name && !k.person_id && hasMaster ? ' <span class="pill pill-amber" title="Typed by hand — not a record on the Drivers & Guides master. Add them there so the next tour picks them from the list.">not on the master</span>' : '';
            // tf-crew-master-r1: a JSD / JSCG / JSNTG line is filled from the allocation picker — one category, cards with
            // the photo, nickname, legal name, vehicle and roof, papers and rate — never from a drop-down or a typed name
            const picked = !!MASTERS.master_ready && !!lineCat(k);
            const engageHtml = replaced
                ? `<div class="who" style="align-items:center"><b>${esc(k.person_name || '')}</b><span class="help">${esc(k.supplier_acc || '')}${k.person_phone ? ' · ' + esc(k.person_phone) : ''}${k.person_bata != null ? ' · own bata ' + num(k.person_bata) : ''}</span></div>`
                : (picked ? allocHtml(k) : `${hasMaster ? peopleSelect(k, '', k.person_id) : `<select class="form-select form-select-sm mt-1" data-crewsel="${k.id}"><option value="">— from the ledger (6-9-11) —</option>${list.map(f => `<option value="${esc(f.acc)}" ${k.supplier_acc === f.acc ? 'selected' : ''}>${esc(f.name)}${f.language ? ' · ' + esc(f.language) : ''}</option>`).join('')}</select>`}
                <div class="who">
                    <input class="form-control form-control-sm" data-crewname="${k.id}" placeholder="${hasMaster ? 'or type a name not on the master' : 'or type the name'}" value="${esc(k.person_name || '')}" ${hasMaster && lineCat(k) ? 'hidden' : ''}>
                    <input type="number" step="0.01" class="form-control form-control-sm" data-crewbata="${k.id}" placeholder="own bata" value="${k.person_bata ?? ''}">
                    <button class="btn btn-light btn-sm" data-engage="${k.id}">Engage</button><button class="btn btn-light btn-sm" data-decline="${k.id}" title="Declined the job">✕</button>
                </div>${k.status === 'engaged' && k.person_phone ? `<div class="help">${esc(k.person_name)} · ${esc(k.person_phone)}${k.supplier_acc ? ' · ' + esc(k.supplier_acc) : ''}</div>` : ''}${notOnMaster}${payeeHtml(k)}`);
            const replaceBtn = CAN_REPLACE && dated && k.status === 'engaged' ? `<button class="btn btn-light btn-sm" data-replace="${k.id}" title="Replace mid-tour: another person takes over from a given day; both stay on the card with their days, each paid for their own" style="white-space:nowrap">Replace…</button>` : '';
            return `<div class="crewline ${replaced ? 'replaced' : ''}" data-crewline="${k.id}">
                <div class="top"><div><b>${esc(k.role_name)}</b> ${lineCat(k) ? `<span class="pill pill-blue" title="${esc((MASTERS.category_names || {})[lineCat(k)] || '')}">${esc(lineCat(k))} only</span>` : ''}<span class="help">· ${esc(k.kind)}${k.language ? ' · ' + esc(k.language) : ''} · quoted ${k.days ?? '?'} day(s) · <span title="The quotation's costing figure for this role (Crew Roles &amp; Quotation Costing) — not what is paid: the figure paid is copied from the Driver &amp; Guide Master when the person is engaged">costing bata ${k.bata != null ? num(k.bata) : '<span class="pill pill-amber">not set</span>'} (quoted)</span></span></div><div class="d-flex gap-1 align-items-center">${st}${replaceBtn}</div></div>
                ${relation}
                ${engageHtml}
                ${datesHtml}
                ${voucherHtml}
                <div class="rform" data-rform="${k.id}"></div>
            </div>`;
        }).join('') || '<div class="help">No crew lines on this tour.</div>';
        const vouchersAll = dated && (c.vouchers || []).length ? `<div class="help mt-2"><b>Payment vouchers on this card:</b> ${(c.vouchers || []).map(v => `${esc(v.voucher_no)} · ${esc(v.person_name)} · ${v.days} day(s) · ${esc(v.currency)} ${num(v.amount)} <span class="pill ${v.state === 'issued' ? 'pill-green' : 'pill-grey'}">${esc(v.state)}</span> <a href="${BASE}/voucher/${v.id}" target="_blank">print</a>`).join('<br>')}</div>` : '';

        // The driver's pack, right under the steps. The list is the card's own copy of the
        // Driver's pack list (the master); each document the record can produce has Preview
        // and PDF, so nothing is typed twice; Issued / Waive on each. Once every "before"
        // document is issued or waived the card takes Pack issued by itself (the server does
        // it), stamped with who handed over the last one and when.
        const pk = c.pack || { before: [], after: [], before_done: 0, before_of: 0, after_done: 0, after_of: 0, ready: false, issued: false, reopened: 0 };
        const docUrl = (d, pdf) => `${BASE}/card/${c.id}/pack/${encodeURIComponent(d.doc_code)}${pdf ? '?pdf=1' : ''}`;
        const docRow = (d, ph) => {
            const st = d.state;
            const pill = st === 'pending' ? 'pill-grey' : (st === 'waived' ? 'pill-amber' : 'pill-green');
            const done = ph === 'before' ? ['issued', 'waived'].includes(st) : ['returned', 'accepted', 'waived'].includes(st);
            const gen = d.generate
                ? `<a class="btn btn-light" href="${docUrl(d, false)}" target="_blank" title="Generated from the record — the ${esc(d.generate === 'itinerary' ? 'programme sheet' : d.generate === 'hotels' ? 'stays as the Hotel Desk holds them' : d.generate === 'allowance' ? 'crew, days and bata on this card' : d.generate === 'route' ? 'route on a map with the planned mileage day by day, from the legs on this card' : 'schedule, with the meter columns blank')}; nothing to type"><i class="bi bi-eye"></i> Preview</a><a class="btn btn-light" href="${docUrl(d, true)}" target="_blank" title="The same as a PDF"><i class="bi bi-file-earmark-pdf"></i> PDF</a>${d.generate === 'route' ? `<a class="btn btn-light" href="${docUrl(d, false)}?img=1" title="The whole sheet as one picture (PNG, 1080 px wide) to send on WhatsApp"><i class="bi bi-image"></i> Image</a>` : ''}`
                : `<span class="help" title="Not on the record: prepared by hand and marked here">by hand</span>`;
            const acts = ph === 'before'
                ? (st === 'pending'
                    ? `<button class="btn btn-primary" data-doc="${d.id}" data-state="issued" title="Handed over to the driver"><i class="bi bi-check2"></i> Issued</button><button class="btn btn-light" data-doc="${d.id}" data-state="waived" title="Not needed this time — say why">Waive…</button>`
                    : `<button class="btn btn-light" data-doc="${d.id}" data-state="pending" title="Back to pending">↺ Reopen</button>`)
                : (['pending', 'issued'].includes(st)
                    ? `<button class="btn btn-primary" data-doc="${d.id}" data-state="returned" title="Came back from the driver">Returned</button><button class="btn btn-light" data-doc="${d.id}" data-state="accepted" title="Checked and accepted">Accepted</button><button class="btn btn-light" data-doc="${d.id}" data-state="waived" title="Not needed this time — say why">Waive…</button>`
                    : (st === 'returned' ? `<button class="btn btn-light" data-doc="${d.id}" data-state="accepted">Accepted</button>` : '') + `<button class="btn btn-light" data-doc="${d.id}" data-state="pending" title="Back to pending">↺ Reopen</button>`);
            return `<div class="docrow ${done ? 'done' : ''}" data-packdoc="${esc(d.doc_code)}">
                <div class="name"><span class="pill ${pill}">${esc(st)}</span> <b>${esc(d.doc_name)}</b>${d.at ? `<span class="help"> · ${st === 'pending' ? 'put back to pending ' : ''}${when(d.at)} · ${esc(d.by || '')}</span>` : ''}${d.note ? `<div class="help">${esc(d.note)}</div>` : ''}</div>
                <div class="acts">${gen}${acts}</div>
            </div>`;
        };
        const packHead = pk.issued
            ? `<span class="pill pill-teal" title="Who issued it and when"><i class="bi bi-check2-circle"></i> Pack issued ${when(pk.issued_at)} · ${esc(pk.issued_by || '')}</span>`
            : (pk.ready
                ? (c.status === 'scheduled' ? '<span class="pill pill-green">every document out — issuing…</span>' : `<span class="pill pill-green">every document out</span><span class="help">the pack issues itself once the schedule is checked</span>`)
                : `<span class="help">${pk.before_of ? `${pk.before_of - pk.before_done} still to hand over — the pack issues itself once each is issued or waived` : ''}</span>`);
        const packHtml = `<div class="pack" id="pack">
            <div class="pack-h"><b>Driver's pack</b><span class="pill pill-grey" id="pack_count">${pk.before_done} of ${pk.before_of} handed over</span>${packHead}</div>
            ${(pk.before || []).map(d => docRow(d, 'before')).join('') || '<div class="help">The driver\'s pack list is empty — add the documents under <b>Driver\'s pack list</b> on the desk; a card takes the list when it is made.</div>'}
            ${pk.reopened ? `<span class="flag">${pk.reopened} document${pk.reopened === 1 ? ' was' : 's were'} reopened after the pack went out — hand ${pk.reopened === 1 ? 'it' : 'them'} over again and mark ${pk.reopened === 1 ? 'it' : 'them'} issued.</span>` : ''}
            ${rung >= LADDER.indexOf('pack_issued') || (pk.after || []).some(d => d.state !== 'pending')
                ? `<div class="help mt-2 mb-1" style="font-weight:600">Back after the tour <span class="pill pill-grey">${pk.after_done} of ${pk.after_of} back</span></div>${(pk.after || []).map(d => docRow(d, 'after')).join('') || '<div class="help">Nothing comes back on this list.</div>'}`
                : (pk.after_of ? `<div class="help mt-2">${pk.after_of} document${pk.after_of === 1 ? '' : 's'} come${pk.after_of === 1 ? 's' : ''} back after the tour (${(pk.after || []).map(d => esc(d.doc_name)).join(', ')}) — marked here once the driver is back.</div>` : '')}
            <div id="d_form" class="mt-1"></div>
        </div>`;

        // The driver's advance and settlement (schema 26). What the card says is payable — the
        // vehicle hire on the kilometres and the rate copied onto the card (as a figure; the rate
        // itself is not shown) plus the driver's bata — an advance as a percentage of it, worked
        // out here and adjustable by the privilege, then approved and paid with who, when, how;
        // and the settlement: payable − advances ± adjustments = balance, frozen at Settled.
        const m = c.money || null;
        const cur = m ? m.currency : 'LKR';
        const fm = v => v == null ? '—' : Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const advances = c.advances || [];
        const st = c.settlement || null;
        const driverOn = (c.crew || []).find(k => k.kind === 'driver' && k.status === 'engaged' && k.person_name);
        const cardOpen = !['settled', 'cancelled'].includes(c.status) && !c.archived_at;
        const pcts = c.advance_pcts || [20, 30, 40, 50];
        const ADV_PILL = { requested: 'pill-amber', approved: 'pill-blue', paid: 'pill-green', cancelled: 'pill-grey' };
        const METHODS = { cash: 'Cash', bank: 'Bank transfer', cheque: 'Cheque', other: 'Other' };
        const payableHtml = !m ? '<div class="help">The advance tables are not on this install yet — press Install on Tour Inquiry.</div>' : `
            <div class="money">
                <span>Vehicle hire${m.split && m.split.known ? ` <span class="help">replaced mid-tour${m.split.replaced_on ? ' on ' + esc(m.split.replaced_on) : ''} · split by the kilometres actually driven on each driver's days, ${num(m.split.km)} km${m.qty > 1 ? ' × ' + m.qty + ' units' : ''}</span>` : (m.hire.costed != null ? ` <span class="help">as costed · ${num(m.hire.km_costed)} km${m.qty > 1 ? ' × ' + m.qty + ' units' : ''}${m.rate_source === 'special' ? ' · at the driver\'s SPECIAL LKR rate per km (copied when engaged)' : (m.rate_source === 'general' ? ' · at the GENERAL rate of the driver\'s vehicle class (copied when engaged)' : (m.rate_source === 'driver' ? ' · at the driver\'s own LKR rate per km (copied when engaged)' : ''))}</span>` : ` <span class="help ${m.manual_hire ? 'manual' : ''}">${esc(m.why_no_hire || 'not worked out')}</span>`)}</span><span class="amt">${m.split && m.split.known ? fm(m.split.hire) : (m.hire.costed != null ? fm(m.hire.costed) : (m.manual_hire ? '<span class="pill pill-amber">manual</span>' : '—'))}</span>
                ${m.split ? `<span class="help" style="grid-column:1/-1">${m.split.known ? `Driver replaced mid-tour${m.split.replaced_on ? ' on ' + esc(m.split.replaced_on) : ''}: the hire is split by the kilometres actually driven on each driver's days, each at the rate copied onto their own line — ` : ''}${(m.split.segments || []).map(sg => `<b>${esc(sg.name)}</b> ${esc(sg.date_from || '?')} → ${esc(sg.date_to || '?')}: ${sg.km != null ? num(sg.km) + ' km × ' + num(sg.rate) + (sg.rate_source === 'special' ? ' special' : sg.rate_source === 'general' ? ' general' : sg.rate_source === 'class' ? ' class rate' : '') + ' = ' + fm(sg.hire) : '<span style="color:var(--tf-amber)">' + esc(sg.why || 'not known') + '</span>'}`).join(' · ')}${m.split.known ? ' · together <b>' + fm(m.split.hire) + '</b> on ' + num(m.split.km) + ' km' : ''}</span>` : ''}
                ${(m.bata.lines || []).map(b => `<span>Bata · ${esc(b.name)} <span class="help">${b.days != null ? b.days + ' day' + (b.days === 1 ? '' : 's') : 'days not set'}${b.rate != null ? ' × ' + fm(b.rate) : ''} · ${esc(b.source)}</span></span><span class="amt">${b.amount != null ? fm(b.amount) : '—'}</span>`).join('') || '<span class="help">Bata: no driver engaged yet</span><span class="amt">—</span>'}
                <span class="tot">Total payable to the driver${m.split && m.split.known ? ' <span class="help">(the split hire + the bata)</span>' : ''}</span><span class="amt tot">${cur} ${fm(m.payable_before)}</span>
            </div>`;
        const advForm = !m || !cardOpen ? '' : (!driverOn
            ? '<div class="help mt-2">Engage the driver first — the advance is paid to a person on the card.</div>'
            : `<div class="mt-2" id="adv_new">
                <div class="d-flex gap-1 align-items-center flex-wrap"><span class="help">Advance of</span>
                    ${pcts.map(p => `<button class="btn btn-light btn-sm pctbtn" data-pct="${p}">${p}%</button>`).join('')}
                    <input type="number" min="1" max="100" step="0.5" class="form-control form-control-sm" id="adv_pct" placeholder="other %" style="width:92px">
                    <span class="help">=</span><span class="mono" id="adv_calc">—</span>
                </div>
                <div class="d-flex gap-1 align-items-center flex-wrap mt-1">
                    ${CAN_ADVANCE ? `<input type="number" min="0" step="0.01" class="form-control form-control-sm" id="adv_amt" placeholder="or adjust the amount" style="width:150px" title="Management may set another figure; say why"><input class="form-control form-control-sm" id="adv_why" placeholder="why it differs (needed when adjusted)" style="max-width:260px">` : ''}
                    <input class="form-control form-control-sm" id="adv_note" placeholder="note (optional)" style="max-width:220px">
                    <button class="btn btn-primary btn-sm" id="adv_go" disabled>Request advance</button>
                </div>
                ${payeeHtml(driverOn)}
                <div class="help mt-1">For ${esc(driverOn.person_name)}. The card works the amount out from the percentage of the total payable${CAN_ADVANCE ? '; you hold the Driver advance privilege, so you may set another figure with a reason' : '; Management may adjust it, approves it and records the payment'}.</div>
            </div>`);
        const advRows = advances.map(a => {
            const acts = [];
            if (a.status === 'requested') {
                if (CAN_ADVANCE) acts.push(`<button class="btn btn-primary btn-sm" data-advact="approve" data-adv="${a.id}">Approve</button>`);
                if (CAN_ADVANCE) acts.push(`<button class="btn btn-light btn-sm" data-advact="adjust" data-adv="${a.id}">Adjust…</button>`);
                acts.push(`<button class="btn btn-light btn-sm" data-advact="cancel" data-adv="${a.id}">Cancel…</button>`);
            } else if (a.status === 'approved') {
                if (CAN_ADVANCE) acts.push(`<button class="btn btn-primary btn-sm" data-advact="pay" data-adv="${a.id}">Record payment…</button>`);
                if (CAN_ADVANCE) acts.push(`<button class="btn btn-light btn-sm" data-advact="adjust" data-adv="${a.id}">Adjust…</button>`);
                if (CAN_ADVANCE) acts.push(`<button class="btn btn-light btn-sm" data-advact="cancel" data-adv="${a.id}">Cancel…</button>`);
            }
            const trail = [
                `requested ${when(a.requested_at)} · ${esc(a.requested_by || '')}${a.request_note ? ' — ' + esc(a.request_note) : ''}`,
                a.adjusted_at ? `adjusted from ${fm(a.amount_calc)} ${when(a.adjusted_at)} · ${esc(a.adjusted_by || '')} — ${esc(a.adjust_note || '')}` : '',
                a.approved_at ? `approved ${when(a.approved_at)} · ${esc(a.approved_by || '')}${a.approve_note ? ' — ' + esc(a.approve_note) : ''}` : '',
                a.paid_at ? `<b>paid ${fmt(a.paid_on)}</b> · ${esc(METHODS[a.method] || a.method || '')}${a.reference ? ' · ref ' + esc(a.reference) : ''} · by ${esc(a.paid_by || '')}${a.remarks ? ' — ' + esc(a.remarks) : ''}` : '',
                a.cancelled_at ? `cancelled ${when(a.cancelled_at)} · ${esc(a.cancelled_by || '')} — ${esc(a.cancel_note || '')}` : '',
            ].filter(Boolean);
            return `<div class="advline ${a.status === 'cancelled' ? 'text-muted' : ''}" data-advline="${a.id}">
                <div><span class="pill ${ADV_PILL[a.status] || 'pill-grey'}">${esc(a.status)}</span> <b>${esc(a.advance_no)}</b> · <b>${cur} ${fm(a.amount)}</b>${a.pct != null ? ` <span class="help">${Number(a.pct)}% of ${fm(a.base_total)}</span>` : ' <span class="help">a set figure</span>'} · ${esc(a.person_name || '')}</div>
                <div class="help">${trail.join('<br>')}</div>
                ${acts.length && cardOpen ? `<div class="acts">${acts.join('')}</div>` : ''}
            </div>`;
        }).join('');
        const settleHtml = !st ? '' : `
            ${driverOn ? payeeHtml(driverOn) : ''}
            <div class="help mt-3 mb-1" style="font-weight:600">Settlement ${st.frozen ? `<span class="pill pill-green">${esc(st.settlement_no)} · settled ${when(st.settled_at)} · ${esc(st.settled_by || '')}</span>` : '<span class="pill pill-grey">as the card stands now</span>'}</div>
            <div class="money">
                <span>Total payable <span class="help">hire ${st.hire != null ? fm(st.hire) + ' ' + esc({ actual: 'on ' + num(st.hire_km) + ' km actually driven', split: 'split by each driver\'s days, ' + num(st.hire_km) + ' km actually driven', scheduled: 'on the schedule, ' + num(st.hire_km) + ' km (actual km not all entered)', costed: 'as costed, ' + num(st.hire_km) + ' km' }[st.hire_basis] || st.hire_basis) : (st.frozen && st.hire_basis === 'manual' ? '<span class="pill pill-amber">settled by hand</span> — as adjustment lines' : (st.manual_hire ? '<span class="pill pill-amber">flagged</span> — to be settled by hand, as adjustment lines' : 'not worked out'))} + bata ${fm(st.bata)}</span></span><span class="amt">${fm(st.payable)}</span>
                ${(st.advance_lines || []).map(a => `<span>− advance ${esc(a.advance_no)} <span class="help">paid ${fmt(a.paid_on)}</span></span><span class="amt">− ${fm(a.amount)}</span>`).join('') || '<span class="help">− advances: none paid</span><span class="amt">—</span>'}
                ${(st.adjustment_lines || []).map(x => `<span>${x.amount >= 0 ? '+' : '−'} ${esc(x.label)} <span class="help">${x.note ? esc(x.note) + ' · ' : ''}${esc(x.added_by || '')}</span>${!st.frozen && CAN_ADVANCE && cardOpen ? ` <button class="btn btn-light btn-sm rowdel" data-adjdel="${x.id}" title="Take it off, with why" style="padding:0 5px;font-size:10px">&times;</button>` : ''}</span><span class="amt">${x.amount >= 0 ? '+' : '−'} ${fm(Math.abs(x.amount))}</span>`).join('')}
                <span class="tot">Balance ${st.balance >= 0 ? 'due to the driver' : 'due from the driver'}</span><span class="amt tot">${cur} ${fm(Math.abs(st.balance))}</span>
            </div>
            ${!st.frozen && CAN_ADVANCE && cardOpen ? `<div class="d-flex gap-1 align-items-center flex-wrap mt-1" id="adj_new">
                <input class="form-control form-control-sm" id="adj_label" placeholder="adjustment (fuel, tolls, a deduction…)" style="max-width:220px">
                <input type="number" step="0.01" class="form-control form-control-sm" id="adj_amt" placeholder="+ owed / − deducted" style="width:150px">
                <input class="form-control form-control-sm" id="adj_note" placeholder="note (optional)" style="max-width:200px">
                <button class="btn btn-light btn-sm" id="adj_go">Add adjustment</button></div>` : ''}
            <div class="d-flex gap-2 align-items-center flex-wrap mt-2">
                <a class="btn btn-light btn-sm" href="${BASE}/card/${c.id}/pack/SETTLE" target="_blank"><i class="bi bi-printer"></i> Settlement statement</a>
                <span class="help">${st.frozen ? 'Frozen when the card was settled; the Settlement in the driver\'s pack prints it.' : (st.unpaid_advances ? `${st.unpaid_advances} advance${st.unpaid_advances === 1 ? '' : 's'} still unpaid — record the payment or cancel before the card is settled.` : 'Settled (the last step) freezes these figures with a number.')}</span>
            </div>`;
        const advanceBox = `<div class="box" id="advance_box">
              <h6><span>Driver advance &amp; settlement</span>${advances.length ? `<span class="pill pill-grey">${advances.filter(a => a.status === 'paid').length} paid · ${advances.filter(a => ['requested', 'approved'].includes(a.status)).length} open</span>` : ''}</h6>
              ${payableHtml}
              ${advForm}
              ${advRows ? `<div class="mt-2">${advRows}</div>` : ''}
              <div id="adv_form" class="mt-1"></div>
              ${settleHtml}
            </div>`;

        // Every driven leg shows Google's road distance and the kilometres actually driven side
        // by side, with the costed figure marked. The actual is typed here after the tour, from
        // the driver's log sheet; Google's figure is never overwritten by it, and every later
        // quotation on the same sector learns from what was entered.
        const hasActuals = (c.legs || []).some(l => Object.prototype.hasOwnProperty.call(l, 'actual_km'));
        const drivenLeg = l => !['garage', 'idle'].includes(l.kind) && !(l.kind === 'local' && String(l.from_place || '').trim().toLowerCase() === String(l.to_place || '').trim().toLowerCase());
        const legs = (c.legs || []).map(l => {
            const driven = drivenLeg(l);
            const gq = l.quoted_by === 'google' && l.google_km != null && Number(l.google_km) === Number(l.km);
            const gcell = driven
                ? (l.google_km != null ? `<span class="mono" style="${gq ? 'font-weight:700' : ''}">${num(l.google_km)}</span>${gq ? ' <span class="pill pill-green" style="font-size:9.5px;padding:0 5px">costed</span>' : ''}` : '<span class="help">—</span>')
                : '<span class="help">—</span>';
            const costedNote = driven && !gq && l.km != null ? `<div class="help">costed at ${num(l.km)} km${l.quoted_by ? ' · ' + esc({ history: 'past tours', town_history: 'past tours', manual: 'typed by hand', actual: 'typed actual', google: 'Google', same_place: 'airport area' }[l.quoted_by] || l.quoted_by) : ''}</div>` : '';
            const acell = driven && hasActuals
                ? `<input type="number" min="0" step="0.1" class="form-control form-control-sm mono" style="width:84px" data-actual="${l.id}" value="${l.actual_km != null ? Number(l.actual_km) : ''}" placeholder="driven km" title="The kilometres actually driven on this leg, from the log sheet. Google's figure and the costed one stay as they are.">
                   ${l.actual_km != null ? `<div class="help">${esc(l.actual_by || '')} · ${when(l.actual_at)}${l.actual_note ? ' — ' + esc(l.actual_note) : ''}</div>` : '<div class="help">not entered</div>'}`
                : '<span class="help">—</span>';
            return `<tr class="origin-${esc(l.origin)}">
            <td class="mono">${l.day_no}<div class="help">${fmt(l.day_date)}</div></td>
            <td><span class="pill pill-grey">${esc(KIND[l.kind] || l.kind)}</span></td>
            <td>${esc(l.from_place || '—')} <span class="help">→</span> <b>${esc(l.to_place || '—')}</b>${costedNote}${!driven && l.km != null ? `<div class="help">${num(l.km)} km · allowance</div>` : ''}${l.note ? `<div class="help">${esc(l.note)}</div>` : ''}</td>
            <td>${gcell}</td>
            <td>${acell}</td>
            <td>${driven ? `<input type="number" min="0" step="0.1" class="form-control form-control-sm mono" style="width:76px" data-extra="${l.id}" value="${l.extra_km != null ? Number(l.extra_km) : ''}" placeholder="extra" title="The extra kilometres for site visits and the city tour on this leg. The costed total on the quotation does not change.">` : `<span class="mono">${num(l.extra_km)}</span>`}</td>
            <td>${l.night_hotel ? esc(l.night_hotel) : ''}<div class="help">${esc(l.night_area || '')}</div></td>
            <td><span class="help">${l.origin === 'costed' ? 'as costed' : (l.origin === 'changed' ? 'changed' : 'added')}</span></td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-leg="${l.id}">Edit</button> <button class="btn btn-light btn-sm rowdel" data-delleg="${l.id}">&times;</button></td></tr>`;
        }).join('') || '<tr><td colspan="9" class="empty">No legs on this card.</td></tr>';
        const sumA = c.actuals || {};
        const actualsLine = sumA.has_columns ? `<div class="help mt-2"><b>Kilometres, three ways over the driven legs:</b> Google ${num(sumA.google_km)} km on ${sumA.google_legs} leg${sumA.google_legs === 1 ? '' : 's'} · costed ${num(sumA.quoted_km)} km · actually driven ${num(sumA.actual_km)} km on ${sumA.actual_legs} of ${sumA.driven_legs}${sumA.difference_km != null ? ` — costed against driven over the ${sumA.both_legs} leg${sumA.both_legs === 1 ? '' : 's'} both know: <b>${Number(sumA.difference_km) > 0 ? '+' : ''}${num(sumA.difference_km)} km</b>` : ''}${sumA.missing > 0 ? ` · <span class="pill pill-amber">${sumA.missing} still to enter</span>` : ''}. Type the actual kilometres from the log sheet after the tour; every later quotation on the same sector learns from them. The costed figures never move.</div>` : '';

        const detailsHtml = DETAILS[c.tour_id] ? window.tdDetails(DETAILS[c.tour_id], { print: BASE + '/tour/' + c.tour_id + '/programme' }) : '<div class="help">Reading the tour…</div>';
        document.getElementById('c_body').innerHTML = `
            ${c.strip ? `<div class="mb-3">${window.tdStrip(c.strip, { compact: true })}</div>` : ''}
            <div class="box">
              <h6><span>Where it stands</span><span>${pill(c.status)}</span></h6>
              <div class="ladder mb-2">${ladder}</div>
              <div class="d-flex gap-2 flex-wrap align-items-center">
                ${next ? `<button class="btn btn-primary btn-sm" data-status="${next}" ${next === 'settled' && c.money && !CAN_ADVANCE ? 'disabled title="Settling freezes the driver\'s settlement — it needs the Driver advance privilege"' : ''}>${esc(NEXT_WORD[next] || LABEL[next])}</button>` : ''}
                ${prev ? `<button class="btn btn-light btn-sm" data-status="${prev}" data-back="1">Back one step</button>` : ''}
                <span class="help">${c.status === 'to_allocate' ? 'Allocated means a vehicle set and a driver engaged — taken by itself once both are there.' : (c.status === 'scheduled' ? 'Pack issued is taken by itself the moment every document below is issued or waived.' : (c.status === 'running' ? 'Returned needs every document below marked back — the log sheet and the settlement.' : ''))}</span>
              </div>
              <div id="c_form" class="mt-2"></div>
              <div id="c_rot" class="mt-2" data-job="${c.id}" data-status="${esc(c.status)}"></div>
              ${packHtml}
            </div>
            <div class="box" id="c_details">${detailsHtml}</div>
            <div class="row g-3">
              <div class="col-lg-5">
                <div class="box">
                  <h6>This card</h6>
                  <dl class="kv">
                    <dt>Tour</dt><dd><b>${esc(c.tour_no)}</b> · ${esc(c.tour_name || '—')}<div class="help">${esc(c.agent_name || '—')} · ${esc(c.tour_pax ?? '?')} pax · ${fmt(c.arrival_date)} – ${fmtY(c.departure_date)}</div></dd>
                    <dt>Handling agent</dt><dd>${c.handler_name ? `<b>${esc(c.handler_name)}</b> <span class="help">· whose tour it is</span>` : '<span class="pill pill-amber">nobody</span>'}${c.archived_at ? `<div class="flag" style="color:var(--tf-muted)">Archived ${when(c.archived_at)} by ${esc(c.archived_by || '')}${c.archive_note ? ' — ' + esc(c.archive_note) : ''}</div>` : ''}</dd>
                    ${c.arrival_flight ? `<dt>Arrival</dt><dd>${esc(c.arrival_flight)}</dd>` : ''}
                    ${c.departure_flight ? `<dt>Departure</dt><dd>${esc(c.departure_flight)}</dd>` : ''}
                    <dt>Vehicle size</dt><dd>${esc(c.vehicle_type)}${c.seats ? ` · ${c.seats} seats` : ''}${Number(c.qty) > 1 ? ` · ${c.qty} units` : ''}</dd>
                    <dt>Kilometres</dt><dd class="mono">${num(c.km_total)} km + ${num(c.extra_km_total)} local <span class="help">· costed at ${num(c.km_quoted)} + ${num(c.extra_km_quoted)}</span>${c.actual_km_total != null ? `<div class="help">actually driven ${num(c.actual_km_total)} km so far</div>` : ''}</dd>
                    <dt>Days</dt><dd class="mono">${c.days ?? '—'}</dd>
                  </dl>
                  ${Number(c.amended) ? '<span class="flag">The quotation was amended after this card moved — check the schedule and crew against it.</span>' : ''}
                </div>
                <div class="box">
                  <h6>Vehicle</h6>
                  <select class="form-select form-select-sm mb-1" id="v_sel">${vehOpts}</select>
                  <input class="form-control form-control-sm mb-1" id="v_reg" placeholder="or type the registration" value="${esc(c.vehicle_reg || '')}">
                  <input class="form-control form-control-sm mb-1" id="v_note" placeholder="Note (optional)" value="${esc(c.vehicle_note || '')}">
                  <button class="btn btn-light btn-sm" id="v_set">Set vehicle</button>
                  ${c.vehicle_reg ? `<div class="help mt-1">Now: <b>${esc(c.vehicle_reg)}</b>${c.reg_is_type && !c.master_reg ? ' <span class="pill pill-amber" title="The vehicle master has no registration number for this vehicle — a holder of Transport Rates adds it under The fleet on that screen, or type it here">no registration on the master</span>' : ''}${c.master_reg ? ` <span class="pill pill-blue" title="The vehicle master now has this registration for the vehicle on this card">master: ${esc(c.master_reg)}</span> <button class="btn btn-light btn-sm py-0" id="v_takereg" type="button">take the registration from the master</button>` : ''} · ${when(c.allocated_at)} ${esc(c.allocated_by || '')}</div>` : ''}
                </div>
                <div class="box">
                  <h6>Crew &amp; allowances</h6>
                  ${crewHtml}
                  ${vouchersAll}
                  <div class="help mt-1">Bata per day comes from the crew bata master on the quotation; a person's own agreed bata goes in the third column and is what the payment side uses.${dated ? ' Each line carries the days the person works; the payment voucher is issued for exactly those days. A person replaced mid-tour keeps their line, ended on their last day, and the replacement gets one of their own. The vehicle is the card\'s and never changes with the crew.' : ''}</div>
                </div>
                ${advanceBox}
                <div class="box">
                  <h6>Desk note</h6>
                  <textarea class="form-control form-control-sm" id="c_note" rows="2">${esc(c.note || '')}</textarea>
                  <div class="mt-2"><button class="btn btn-light btn-sm" id="c_savenote">Save note</button></div>
                </div>
              </div>
              <div class="col-lg-7">
                <div class="box">
                  <h6><span>Schedule — as costed; changes here never touch the quotation</span></h6>
                  <div style="overflow-x:auto"><table class="tbl"><thead><tr><th style="width:56px">Day</th><th style="width:90px">Leg</th><th>From → to</th><th style="width:88px" title="Google's road distance for the two places, as quoted">Google km</th><th style="width:110px" title="The kilometres actually driven, entered after the tour">Actual km</th><th style="width:86px" title="Extra kilometres for site visits and the city tour on this leg">Extra km</th><th>Night</th><th style="width:80px"></th><th style="width:96px"></th></tr></thead><tbody>${legs}</tbody></table></div>${actualsLine}
                  <div class="row g-2 mt-2 align-items-end" id="legform">
                    <input type="hidden" id="l_id">
                    <div class="col-md-1"><label class="form-label help">Day</label><input type="number" min="0" class="form-control form-control-sm" id="l_day"></div>
                    <div class="col-md-2"><label class="form-label help">Date</label><input type="date" class="form-control form-control-sm" id="l_date"></div>
                    <div class="col-md-2"><label class="form-label help">From</label><input class="form-control form-control-sm" id="l_from"></div>
                    <div class="col-md-2"><label class="form-label help">To</label><input class="form-control form-control-sm" id="l_to"></div>
                    <div class="col-md-1"><label class="form-label help">km</label><input type="number" min="0" step="0.1" class="form-control form-control-sm" id="l_km"></div>
                    <div class="col-md-1"><label class="form-label help">Local</label><input type="number" min="0" step="0.1" class="form-control form-control-sm" id="l_extra"></div>
                    <div class="col-md-2"><label class="form-label help">Kind</label><select class="form-select form-select-sm" id="l_kind">${Object.entries(KIND).map(([k, v]) => `<option value="${k}">${v}</option>`).join('')}</select></div>
                    <div class="col-md-1"><button class="btn btn-light btn-sm w-100" id="l_save">Add</button></div>
                    <div class="col-md-6"><label class="form-label help">Night at</label><input class="form-control form-control-sm" id="l_hotel" placeholder="hotel"></div>
                    <div class="col-md-6"><label class="form-label help">Note</label><input class="form-control form-control-sm" id="l_note"></div>
                  </div>
                </div>
                <div class="box">
                  <h6>Trail</h6>
                  <div class="trail">${(c.events || []).map(e => `<div><b>${esc(e.event)}</b>${e.to_status ? ` → ${esc(String(e.to_status).replace('_', ' '))}` : ''}${e.note ? ` — ${esc(e.note)}` : ''}<div class="who">${when(e.at)} · ${esc(e.by || '')}</div></div>`).join('') || '<div class="help">Nothing yet.</div>'}</div>
                </div>
              </div>
            </div>`;

        // wiring
        document.querySelectorAll('#c_body [data-status]').forEach(b => b.addEventListener('click', () => {
            if (b.dataset.back) {
                document.getElementById('c_form').innerHTML = `<input class="form-control form-control-sm mb-1" id="s_note" placeholder="Why it goes back (required)"><button class="btn btn-primary btn-sm" id="s_go">Back to ${esc(LABEL[b.dataset.status])}</button>`;
                document.getElementById('s_go').addEventListener('click', () => act('status', { status: b.dataset.status, note: document.getElementById('s_note').value }));
            } else if (b.dataset.status === 'settled' && c.money && c.money.manual_hire) {
                // flagged for a manual settlement (a driver replaced mid-tour, kilometres not all in): settle only by hand, and say how
                document.getElementById('c_form').innerHTML = `<div class="help mb-1" style="color:var(--tf-amber)">${esc(c.money.why_no_hire || '')}</div><label class="help" style="display:flex;gap:6px;align-items:center"><input type="checkbox" id="s_byhand"> The vehicle hire is settled by hand — added above as adjustment lines</label><input class="form-control form-control-sm mb-1 mt-1" id="s_note" placeholder="How the hire was settled (required)"><button class="btn btn-primary btn-sm" id="s_go">Settle by hand</button>`;
                document.getElementById('s_go').addEventListener('click', () => act('status', { status: 'settled', note: document.getElementById('s_note').value, hire_by_hand: document.getElementById('s_byhand').checked ? 1 : 0 }));
            } else {
                act('status', { status: b.dataset.status });
            }
        }));
        loadDetails(c.tour_id);
        if (!CAN_ALLOCATE) { document.querySelectorAll('#v_set, #v_takereg, #c_body [data-engage], #c_body [data-crewsel], #c_body [data-crewperson], #c_body [data-crewname], #c_body [data-crewbata], #v_sel, #v_reg').forEach(el => { el.disabled = true; el.title = NO_ALLOC; }); }
        document.getElementById('v_set').addEventListener('click', () => act('vehicle', { vehicle_id: document.getElementById('v_sel').value, reg: document.getElementById('v_reg').value, note: document.getElementById('v_note').value }));
        document.getElementById('c_savenote').addEventListener('click', () => act('note', { note: document.getElementById('c_note').value }));
        document.getElementById('v_takereg')?.addEventListener('click', () => act('vehicle', { vehicle_id: c.vehicle_id, note: c.vehicle_note || '' }));
        document.querySelectorAll('#c_body [data-engage]').forEach(b => b.addEventListener('click', () => crew(Number(b.dataset.engage), 'engaged')));
        // tf-crew-master-r1: the allocation picker on a JSD / JSCG / JSNTG line
        document.querySelectorAll('#c_body [data-pickopen]').forEach(b => b.addEventListener('click', () => {
            const k = (c.crew || []).find(x => Number(x.id) === Number(b.dataset.pickopen));
            if (k) openPicker(k, k.status === 'engaged' && k.person_name ? 'Change the ' + (lineCat(k) || k.kind) : 'Allocate', (p, bata, ack) => crewPick(k.id, p, bata, ack));
        }));
        // a person picked from the master: the typed-name box clears (the master's name is theirs) and their own bata fills in
        document.querySelectorAll('#c_body [data-crewperson]').forEach(sel => sel.addEventListener('change', () => {
            const p = (MASTERS.people || []).find(x => Number(x.id) === Number(sel.value)); const id = sel.dataset.crewperson;
            const nm = document.querySelector(`[data-crewname="${id}"]`), bt = document.querySelector(`[data-crewbata="${id}"]`);
            if (p) { if (nm) nm.value = ''; if (bt && !bt.value && p.bata != null) bt.value = p.bata; }
        }));
        document.querySelectorAll('#c_body [data-decline]').forEach(b => b.addEventListener('click', () => crew(Number(b.dataset.decline), 'declined')));
        // schema 24: the days worked, the voucher for them, and a replacement mid-tour
        document.querySelectorAll('#c_body [data-dsave]').forEach(b => b.addEventListener('click', async () => {
            const id = b.dataset.dsave;
            const j = await post('/crew/' + id + '/dates', { date_from: document.querySelector(`[data-dfrom="${id}"]`).value, date_to: document.querySelector(`[data-dto="${id}"]`).value });
            if (j.status !== 200) { toast('error', j.message); return; }
            CARD = j.data; renderCard(); toast('success', j.message); load();
        }));
        document.querySelectorAll('#c_body [data-vissue]').forEach(b => b.addEventListener('click', async () => {
            const k = (c.crew || []).find(x => Number(x.id) === Number(b.dataset.vissue));
            const rate = k && k.person_bata != null ? Number(k.person_bata) : (k && k.bata != null ? Number(k.bata) : null);
            const ok = await Swal.fire({ title: 'Issue the payment voucher?', text: `${k ? k.person_name : ''}: ${k ? k.days_worked : '?'} day(s) worked, ${k ? fmt(k.date_from) : ''} to ${k ? fmt(k.date_to) : ''}` + (rate != null ? `, at ${num(rate)} a day = ${num((k.days_worked || 0) * rate)}` : ', no bata on the line yet') + '. The voucher is numbered and kept; a wrong one is cancelled with a note, never edited.', icon: 'question', showCancelButton: true, confirmButtonText: 'Issue voucher', cancelButtonText: 'Not yet' });
            if (!ok.isConfirmed) return;
            const j = await post('/crew/' + b.dataset.vissue + '/voucher', {});
            if (j.status !== 200) { toast('error', j.message); return; }
            CARD = j.data; renderCard(); toast('success', j.message); load();
        }));
        document.querySelectorAll('#c_body [data-vcancel]').forEach(b => b.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'Cancel voucher ' + b.dataset.vno + '?', input: 'text', inputPlaceholder: 'Why (required) — it stays on the trail', showCancelButton: true, confirmButtonText: 'Cancel the voucher', cancelButtonText: 'Keep it' });
            if (!r.isConfirmed) return;
            const j = await post('/voucher/' + b.dataset.vcancel + '/cancel', { why: r.value || '' });
            if (j.status !== 200) { toast('error', j.message); return; }
            CARD = j.data; renderCard(); toast('success', j.message); load();
        }));
        document.querySelectorAll('#c_body [data-replace]').forEach(b => b.addEventListener('click', () => {
            const k = (c.crew || []).find(x => Number(x.id) === Number(b.dataset.replace));
            if (!k) return;
            const list = (MASTERS.freelancers || []).filter(f => k.kind === 'guide' ? f.is_guide : f.is_driver);
            const box = document.querySelector(`#c_body [data-rform="${k.id}"]`);
            const today = new Date().toISOString().slice(0, 10);
            // tf-crew-master-r1: on a JSD / JSCG / JSNTG line the replacement comes from the picker — the same category only
            const picked = !!MASTERS.master_ready && !!lineCat(k);
            let RF = { person: null, ack: false };
            const chosenHtml = () => RF.person
                ? `<div class="alloc" style="margin-top:0;flex:1 1 100%">${avatarHtml(RF.person)}<div style="min-width:0;flex:1"><div class="pk-name">${RF.person.nickname ? esc(RF.person.nickname) : esc(RF.person.name)}<small>${RF.person.nickname ? esc(RF.person.name) : '&nbsp;'}</small></div><div class="mt-1"><span class="pk-cat">${esc(RF.person.category || '')}</span>${k.kind === 'driver' ? ' <span class="help">· ' + vehicleHtml(RF.person) + '</span>' : ''}${k.kind === 'driver' && RF.person.km_rate != null ? ` <span class="help">· LKR ${num(RF.person.km_rate)} per km</span>` : ''}</div></div><div class="acts"><button class="btn btn-light btn-sm" id="rf_pick">Change…</button></div></div>`
                : `<button class="btn btn-pick btn-sm" id="rf_pick"><i class="bi bi-person-plus"></i> Choose the replacement ${esc(lineCat(k) || '')} from the master…</button><span class="help">only ${esc(lineCat(k) || '')}s are listed</span>`;
            box.innerHTML = `<div class="rbox">
                <div><b>Replace ${esc(k.person_name)} mid-tour</b> <span class="help">· another ${esc(picked ? lineCat(k) : k.kind)} takes over from a given day; ${esc(k.person_name)} keeps this line, ended on the day before; the vehicle is not changed</span></div>
                <div class="row-2"><span class="help">Takes over on</span><input type="date" class="form-control form-control-sm" id="rf_date" value="${today}"><span class="help">${esc(k.person_name)}'s last day</span><input type="date" class="form-control form-control-sm" id="rf_last" placeholder="the day before"></div>
                <div class="row-2" id="rf_who">${picked ? chosenHtml() : `${(MASTERS.people || []).length ? peopleSelect(k, 'rf_person', null).replace('data-crewperson', 'data-rperson').replace('class="form-select form-select-sm mt-1"', 'class="form-select form-select-sm" style="max-width:360px"') : `<select class="form-select form-select-sm" id="rf_sel" style="max-width:320px"><option value="">— replacement from the ledger (6-9-11) —</option>${list.filter(f => f.acc !== k.supplier_acc).map(f => `<option value="${esc(f.acc)}">${esc(f.name)}${f.language ? ' · ' + esc(f.language) : ''}${f.bata != null ? ' · own bata ' + num(f.bata) : ''}</option>`).join('')}</select>`}
                    <input class="form-control form-control-sm" id="rf_name" placeholder="${(MASTERS.people || []).length ? 'or type a name not on the master' : 'or type the name'}" style="max-width:220px">`}<input type="number" step="0.01" class="form-control form-control-sm" id="rf_bata" placeholder="${k.kind === 'guide' ? 'guide rate / day' : 'own bata'}" style="width:130px" title="Blank: the Crew Bata master's rate"></div>
                <div class="row-2"><input class="form-control form-control-sm" id="rf_why" placeholder="Why (required) — it stays on the trail" style="max-width:420px"><button class="btn btn-primary btn-sm" id="rf_go" ${picked ? 'disabled' : ''}>Replace</button><button class="btn btn-light btn-sm" id="rf_no">Cancel</button></div>
            </div>`;
            const wirePick = () => document.getElementById('rf_pick')?.addEventListener('click', () => openPicker(k, 'Replacement', (p, bata, ack) => {
                RF = { person: p, ack }; if (bata !== '') document.getElementById('rf_bata').value = bata; else if (p.bata != null) document.getElementById('rf_bata').value = p.bata;
                const who = document.getElementById('rf_who'); const bt = document.getElementById('rf_bata'); who.innerHTML = chosenHtml(); who.appendChild(bt); wirePick();
                document.getElementById('rf_go').disabled = false; return true;
            }));
            wirePick();
            document.getElementById('rf_sel')?.addEventListener('change', () => { const f = list.find(x => x.acc === document.getElementById('rf_sel').value); if (f && f.bata != null && !document.getElementById('rf_bata').value) document.getElementById('rf_bata').value = f.bata; });
            document.getElementById('rf_person')?.addEventListener('change', () => { const p = (MASTERS.people || []).find(x => Number(x.id) === Number(document.getElementById('rf_person').value)); if (p && p.bata != null && !document.getElementById('rf_bata').value) document.getElementById('rf_bata').value = p.bata; });
            document.getElementById('rf_no').addEventListener('click', () => { box.innerHTML = ''; });
            document.getElementById('rf_go').addEventListener('click', async () => {
                if (picked && !RF.person) { toast('error', 'Choose the replacement from the master first.'); return; }
                const j = await withWarning(ack => post('/crew/' + k.id + '/replace', { acknowledge: ack || RF.ack,  date: document.getElementById('rf_date').value, last_day: document.getElementById('rf_last').value, supplier_acc: document.getElementById('rf_sel')?.value || '', person_id: picked ? RF.person.id : (document.getElementById('rf_person')?.value || ''), person_name: document.getElementById('rf_name')?.value || '', person_bata: document.getElementById('rf_bata').value, note: document.getElementById('rf_why').value }));
                if (!j) return;
                if (j.status !== 200) { toast('error', j.message); return; }
                CARD = j.data; renderCard(); toast('success', j.message); load();
            });
            box.scrollIntoView({ block: 'nearest' });
        }));
        // schema 26: the driver's advance and the settlement
        const advCalc = () => {
            const pct = Number(document.getElementById('adv_pct')?.value || 0);
            const base = m ? Number(m.payable_before || 0) : 0;
            const calc = pct > 0 && base > 0 ? Math.round(base * pct) / 100 : null;
            const el = document.getElementById('adv_calc'); if (el) el.textContent = calc != null ? cur + ' ' + fm(calc) : '—';
            const amt = document.getElementById('adv_amt');
            const go = document.getElementById('adv_go'); if (go) go.disabled = !(calc != null || (amt && Number(amt.value) > 0));
            document.querySelectorAll('#adv_new .pctbtn').forEach(x => x.classList.toggle('on', Number(x.dataset.pct) === pct));
        };
        document.querySelectorAll('#adv_new .pctbtn').forEach(b => b.addEventListener('click', () => { document.getElementById('adv_pct').value = b.dataset.pct; advCalc(); }));
        document.getElementById('adv_pct')?.addEventListener('input', advCalc);
        document.getElementById('adv_amt')?.addEventListener('input', advCalc);
        document.getElementById('adv_go')?.addEventListener('click', async () => {
            const body = { crew_id: driverOn ? driverOn.id : null, pct: document.getElementById('adv_pct').value || null, note: document.getElementById('adv_note').value };
            const amt = document.getElementById('adv_amt');
            if (amt && amt.value !== '') { body.amount = amt.value; body.adjust_note = document.getElementById('adv_why').value; }
            await act('advance', body);
        });
        document.querySelectorAll('#c_body [data-advact]').forEach(b => b.addEventListener('click', () => {
            const a = advances.find(x => Number(x.id) === Number(b.dataset.adv)); if (!a) return;
            const box = document.getElementById('adv_form');
            const kind = b.dataset.advact;
            const send = async (body) => {
                const j = await post('/advance/' + a.id + '/' + kind, body);
                if (j.status !== 200) { toast('error', j.message); return; }
                CARD = j.data; renderCard(); toast('success', j.message); load();
            };
            if (kind === 'approve') { send({ note: '' }); return; }
            if (kind === 'adjust') {
                box.innerHTML = `<div class="d-flex gap-1 align-items-center flex-wrap"><span class="help">Adjust ${esc(a.advance_no)} to</span><input type="number" min="0" step="0.01" class="form-control form-control-sm" id="af_amt" value="${Number(a.amount)}" style="width:140px"><input class="form-control form-control-sm" id="af_why" placeholder="why (required)" style="max-width:260px"><button class="btn btn-primary btn-sm" id="af_go">Adjust</button><button class="btn btn-light btn-sm" id="af_no">Cancel</button></div>`;
                document.getElementById('af_go').addEventListener('click', () => send({ amount: document.getElementById('af_amt').value, why: document.getElementById('af_why').value }));
            } else if (kind === 'pay') {
                box.innerHTML = `<div class="d-flex gap-1 align-items-center flex-wrap"><span class="help">Payment of ${esc(a.advance_no)}, ${cur} ${fm(a.amount)}:</span>
                    <input type="date" class="form-control form-control-sm" id="af_on" value="${new Date().toISOString().slice(0, 10)}" style="width:150px">
                    <select class="form-select form-select-sm" id="af_method" style="width:140px">${Object.entries(METHODS).map(([k, v]) => `<option value="${k}">${v}</option>`).join('')}</select>
                    <input class="form-control form-control-sm" id="af_ref" placeholder="reference (needed unless cash)" style="max-width:200px">
                    <input class="form-control form-control-sm" id="af_rem" placeholder="remarks" style="max-width:200px">
                    <button class="btn btn-primary btn-sm" id="af_go">Record payment</button><button class="btn btn-light btn-sm" id="af_no">Cancel</button></div>`;
                document.getElementById('af_go').addEventListener('click', () => send({ paid_on: document.getElementById('af_on').value, method: document.getElementById('af_method').value, reference: document.getElementById('af_ref').value, remarks: document.getElementById('af_rem').value }));
            } else if (kind === 'cancel') {
                box.innerHTML = `<div class="d-flex gap-1 align-items-center flex-wrap"><span class="help">Cancel ${esc(a.advance_no)}:</span><input class="form-control form-control-sm" id="af_why" placeholder="why (required)" style="max-width:300px"><button class="btn btn-primary btn-sm" id="af_go">Cancel the advance</button><button class="btn btn-light btn-sm" id="af_no">Keep it</button></div>`;
                document.getElementById('af_go').addEventListener('click', () => send({ why: document.getElementById('af_why').value }));
            }
            document.getElementById('af_no')?.addEventListener('click', () => { box.innerHTML = ''; });
            box.scrollIntoView({ block: 'nearest' });
        }));
        document.getElementById('adj_go')?.addEventListener('click', () => act('adjustment', { label: document.getElementById('adj_label').value, amount: document.getElementById('adj_amt').value, note: document.getElementById('adj_note').value }));
        document.querySelectorAll('#c_body [data-adjdel]').forEach(b => b.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'Take this adjustment off?', input: 'text', inputPlaceholder: 'Why (required) — it stays on the trail', showCancelButton: true, confirmButtonText: 'Take it off' });
            if (!r.isConfirmed) return;
            const j = await post('/adjustment/' + b.dataset.adjdel + '/remove', { why: r.value || '' });
            if (j.status !== 200) { toast('error', j.message); return; }
            CARD = j.data; renderCard(); toast('success', j.message); load();
        }));
        document.querySelectorAll('#c_body [data-doc]').forEach(b => b.addEventListener('click', () => {
            if (b.dataset.state === 'waived') {
                const row = b.closest('.docrow');
                const name = row ? (row.querySelector('.name b') || {}).textContent : 'this document';
                document.getElementById('d_form').innerHTML = `<div class="d-flex gap-1 align-items-center flex-wrap"><span class="help">Waive <b>${esc(name || '')}</b>:</span><input class="form-control form-control-sm" style="max-width:320px" id="w_note" placeholder="Why it is not needed this time (required)"><button class="btn btn-primary btn-sm" id="w_go">Waive</button><button class="btn btn-light btn-sm" id="w_no">Cancel</button></div>`;
                document.getElementById('w_go').addEventListener('click', () => doc(Number(b.dataset.doc), 'waived', document.getElementById('w_note').value));
                document.getElementById('w_no').addEventListener('click', () => { document.getElementById('d_form').innerHTML = ''; });
                document.getElementById('w_note').focus();
            } else {
                doc(Number(b.dataset.doc), b.dataset.state);
            }
        }));
        document.querySelectorAll('#c_body [data-leg]').forEach(b => b.addEventListener('click', () => {
            const l = (c.legs || []).find(x => Number(x.id) === Number(b.dataset.leg));
            if (!l) return;
            document.getElementById('l_id').value = l.id; document.getElementById('l_day').value = l.day_no; document.getElementById('l_date').value = l.day_date || '';
            document.getElementById('l_from').value = l.from_place || ''; document.getElementById('l_to').value = l.to_place || ''; document.getElementById('l_km').value = l.km ?? '';
            document.getElementById('l_extra').value = l.extra_km ?? ''; document.getElementById('l_kind').value = l.kind; document.getElementById('l_hotel').value = l.night_hotel || ''; document.getElementById('l_note').value = l.note || '';
            document.getElementById('l_save').textContent = 'Save leg';
            document.getElementById('legform').scrollIntoView({ block: 'nearest' });
        }));
        document.querySelectorAll('#c_body [data-extra]').forEach(inp => {
            const save = async () => {
                const l = (c.legs || []).find(x => Number(x.id) === Number(inp.dataset.extra));
                const was = l && l.extra_km != null ? String(Number(l.extra_km)) : '';
                if (String(inp.value) === was) return;
                const j = await post('/card/' + c.id + '/leg/' + inp.dataset.extra, {
                    day_no: l.day_no, day_date: l.day_date, seq: l.seq, from_place: l.from_place, to_place: l.to_place,
                    km: l.km, extra_km: inp.value, kind: l.kind, night_hotel: l.night_hotel, night_area: l.night_area, note: l.note
                });
                if (j.status !== 200) { toast('error', j.message); inp.value = was; return; }
                CARD = j.data; renderCard(); toast('success', 'Extra running saved.'); load();
            };
            inp.addEventListener('change', save);
            inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
        });
        document.querySelectorAll('#c_body [data-actual]').forEach(inp => {
            const save = async () => {
                const l = (c.legs || []).find(x => Number(x.id) === Number(inp.dataset.actual));
                const was = l && l.actual_km != null ? String(l.actual_km) : '';
                if (String(inp.value) === was) return;
                const j = await post('/card/' + c.id + '/actual/' + inp.dataset.actual, { km: inp.value });
                if (j.status !== 200) { toast('error', j.message); inp.value = was; return; }
                CARD = j.data; renderCard(); toast('success', j.message); load();
            };
            inp.addEventListener('change', save);
            inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
        });
        document.querySelectorAll('#c_body [data-delleg]').forEach(b => b.addEventListener('click', async () => {
            const ok = await Swal.fire({ text: 'Are you sure you want to delete this record? The leg comes off the schedule; the costed kilometres on the quotation do not change.', icon: 'question', showCancelButton: true, confirmButtonText: 'Remove the leg', cancelButtonText: 'Cancel' });
            if (!ok.isConfirmed) return;
            const j = await post('/card/' + c.id + '/leg/' + b.dataset.delleg, { delete: 1 });
            if (j.status !== 200) { toast('error', j.message); return; }
            CARD = j.data; renderCard(); toast('success', j.message); load();
        }));
        document.getElementById('l_save').addEventListener('click', async () => {
            const id = document.getElementById('l_id').value;
            const body = { day_no: document.getElementById('l_day').value || 0, day_date: document.getElementById('l_date').value || null, from_place: document.getElementById('l_from').value, to_place: document.getElementById('l_to').value,
                km: document.getElementById('l_km').value, extra_km: document.getElementById('l_extra').value, kind: document.getElementById('l_kind').value, night_hotel: document.getElementById('l_hotel').value, note: document.getElementById('l_note').value };
            const j = await post('/card/' + c.id + '/leg' + (id ? '/' + id : ''), body);
            if (j.status !== 200) { toast('error', j.message); return; }
            CARD = j.data; renderCard(); toast('success', j.message); load();
        });
    }

    // ---------- the document viewer ----------
    // Every document link on this desk (Preview, PDF, Print the programme, a voucher's Print, the
    // settlement statement) opens inside the desk rather than in a new tab, which some browsers
    // and the desktop app's pane block silently. The document is the same page; Print / Save as
    // PDF prints it; Open as a page shows it in this tab, and Back returns here.
    let DOC_RETURN = null;
    function openDoc(url, title) {
        const frame = document.getElementById('doc_frame');
        const note  = document.getElementById('doc_note');
        document.getElementById('doc_title').textContent = title || 'Document';
        document.getElementById('doc_page').setAttribute('href', url);
        note.style.display = 'none'; note.textContent = '';
        frame.src = 'about:blank';
        frame.onload = () => {
            try {
                const d = frame.contentDocument;
                if (d && d.contentType && d.contentType !== 'text/html' && !d.body) return;
                if (d && d.body && d.body.innerText.trim() === '') { note.textContent = 'Nothing came back for this document.'; note.style.display = ''; }
                else if (d && d.querySelector && d.querySelector('.toolbar')) { d.querySelectorAll('.toolbar').forEach(t => { if (t.querySelector('button')) t.style.display = 'none'; }); }
            } catch (e) { /* a PDF or another origin: the frame shows it as it is */ }
        };
        frame.src = url;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_doc')).show();
    }
    // the viewer sits over the card: when it closes, the card underneath keeps scrolling
    document.getElementById('m_doc').addEventListener('hidden.bs.modal', () => {
        document.getElementById('doc_frame').src = 'about:blank';
        if (document.querySelector('#m_card.show')) document.body.classList.add('modal-open');
    });
    document.getElementById('doc_print').addEventListener('click', () => {
        const frame = document.getElementById('doc_frame');
        try { frame.contentWindow.focus(); frame.contentWindow.print(); }
        catch (e) { window.open(frame.src, '_blank') || (location.href = frame.src); }
    });
    document.getElementById('doc_page').addEventListener('click', e => {
        // in this tab, so it opens even where a new tab is blocked; the desk's own page keeps the tour and the card to come back to
        e.preventDefault();
        const url = e.currentTarget.getAttribute('href');
        try { sessionStorage.setItem('tf_desk_return', JSON.stringify({ tour: CARD && CARD.tour_id, card: CARD && CARD.id })); } catch (x) {}
        location.href = url;
    });
    // any link on the desk meant for a new tab opens in the viewer instead
    document.addEventListener('click', e => {
        const a = e.target.closest && e.target.closest('a[target="_blank"]');
        if (!a || !a.href || a.closest('#m_doc')) return;
        if (!a.href.startsWith(BASE + '/') && !a.href.startsWith(BASE.replace(/\/tour\/transport_ops$/, '') + '/tour/')) return;
        e.preventDefault();
        const label = a.closest('.docrow') ? ((a.closest('.docrow').querySelector('.name b') || {}).textContent || '') + (a.href.includes('pdf=1') ? ' — PDF' : '') : (a.textContent || '').trim();
        openDoc(a.href, label || 'Document');
    }, true);

    async function act(kind, body) {
        const j = await post('/card/' + CARD.id + '/' + kind, body);
        if (j.status !== 200) { toast('error', j.message); return; }
        CARD = j.data; renderCard(); toast('success', j.message); load();
    }
    // tf-crew-master-r1: an expired licence or document, a category still to confirm — the clear warning before the
    // allocation; the desk goes on only by saying so, and the same request is sent again acknowledged
    async function withWarning(send) {
        let j = await send(false);
        if (j.status === 409 && j.data && j.data.warnings) {
            const r = await Swal.fire({ icon: 'warning', title: 'Check before allocating', html: `<div class="text-start"><ul style="padding-left:18px">${j.data.warnings.map(w => `<li>${esc(w.text)}</li>`).join('')}</ul></div>`, showCancelButton: true, confirmButtonText: 'Allocate anyway', cancelButtonText: 'Not now' });
            if (!r.isConfirmed) return null;
            j = await send(true);
        }
        return j;
    }
    async function crew(id, status) {
        if (!CAN_ALLOCATE) { toast('error', NO_ALLOC); return; }
        const sel = document.querySelector(`[data-crewsel="${id}"]`), per = document.querySelector(`[data-crewperson="${id}"]`);
        const nm = document.querySelector(`[data-crewname="${id}"]`), bt = document.querySelector(`[data-crewbata="${id}"]`);
        const body = { supplier_acc: sel ? sel.value : '', person_id: per ? per.value : '', person_name: nm ? nm.value : '', person_bata: bt ? bt.value : '', status };
        const j = await withWarning(ack => post('/crew/' + id, Object.assign({}, body, { acknowledge: ack })));
        if (!j) return;
        if (j.status !== 200) { toast('error', j.message); return; }
        CARD = j.data; renderCard(); toast('success', j.message); load();
    }
    async function doc(id, state, note) {
        const before = CARD ? CARD.status : null;
        const j = await post('/doc/' + id, { state, note });
        if (j.status !== 200) { toast('error', j.message); return; }
        CARD = j.data; renderCard(); load();
        const pk = CARD.pack || {};
        if (before !== 'pack_issued' && CARD.status === 'pack_issued' && pk.issued) {
            toast('success', `Every document is out — Pack issued, ${when(pk.issued_at)} by ${pk.issued_by || 'you'}.`);
        } else {
            toast('success', j.message + (pk.before_of ? ` ${pk.before_done} of ${pk.before_of} handed over.` : ''));
        }
    }

    // the driver's pack list — a master, edited here
    function renderDocTypes(rows) {
        document.querySelector('#doclist tbody').innerHTML = rows.map(d => `<tr><td class="mono">${esc(d.code)}</td><td>${esc(d.name)}</td><td>${d.phase === 'after' ? 'After' : 'Before'}</td>
            <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" data-dton="${esc(d.code)}" data-name="${esc(d.name)}" data-phase="${esc(d.phase)}" ${Number(d.active) ? 'checked' : ''}></div></td></tr>`).join('') || '<tr><td colspan="4" class="empty">Empty list.</td></tr>';
        document.querySelectorAll('[data-dton]').forEach(x => x.addEventListener('change', async () => {
            const j = await post('/doc_type', { code: x.dataset.dton, name: x.dataset.name, phase: x.dataset.phase, active: x.checked ? 1 : 0 });
            if (j.status !== 200) { toast('error', j.message); return; }
            MASTERS.doc_types = j.data; renderDocTypes(j.data); toast('success', j.message);
        }));
    }
    document.getElementById('btn_docs').addEventListener('click', async () => {
        await masters(); renderDocTypes(MASTERS.doc_types || []);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_docs')).show();
    });
    document.getElementById('dt_add').addEventListener('click', async () => {
        const j = await post('/doc_type', { code: document.getElementById('dt_code').value, name: document.getElementById('dt_name').value, phase: document.getElementById('dt_phase').value, active: 1 });
        if (j.status !== 200) { toast('error', j.message); return; }
        MASTERS.doc_types = j.data; renderDocTypes(j.data); toast('success', j.message);
        document.getElementById('dt_code').value = ''; document.getElementById('dt_name').value = '';
    });

    document.getElementById('f_q').addEventListener('input', () => { clearTimeout(window.__tq); window.__tq = setTimeout(loadTours, 250); });
    ['f_handler', 'f_from', 'f_to'].forEach(id => document.getElementById(id).addEventListener('change', loadTours));
    document.getElementById('f_clear').addEventListener('click', () => { ['f_handler', 'f_q', 'f_from', 'f_to'].forEach(id => document.getElementById(id).value = ''); WHICH = 'pending'; loadTours(); });
    if (window.TfView) TfView.restoreList(false);   // tf-refresh-r1: this tab's search and filters
    const P0 = new URLSearchParams(location.search);
    if (P0.get('handler')) document.getElementById('f_handler').value = P0.get('handler');
    loadTours();
    // tf-refresh-r1: opened from the address (a refresh): the same tour, read fresh, and the place this tab was at
    if (P0.get('tour')) openTour(Number(P0.get('tour')), true).then(() => { if (window.TfView) TfView.restoreScroll(); });
    window.addEventListener('popstate', () => {
        const id = Number(new URLSearchParams(location.search).get('tour') || 0);
        if (id) { openTour(id, true); }
        else if (TOUR) { backToList.fromHistory = true; try { backToList(); } finally { backToList.fromHistory = false; } }
    });
    // back from a document opened as a page: the same card again
    try {
        const back = JSON.parse(sessionStorage.getItem('tf_desk_return') || 'null');
        if (back && back.card) {
            sessionStorage.removeItem('tf_desk_return');
            if (!P0.get('tour') && back.tour) openTour(Number(back.tour));
            setTimeout(() => openCard(Number(back.card)), 900);
        }
    } catch (x) { /* storage not available: the desk opens as it always has */ }
})();

// tf-rotation-r1 (25 Oct 2026): "Driver Allocation" — after reviewing the card's itinerary, staff send it on to Pending Allocations
(function () {
    const ROT = '<?= base_url('tour/transport_ops/allocations') ?>';
    const CAN_ROT = <?= !empty($can_allocate) ? 'true' : 'false' ?>;
    const esc2 = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    async function fill(el) {
        if (el.dataset.done) return;
        el.dataset.done = '1';
        if (el.dataset.status !== 'to_allocate') return;
        const j = await fetch(ROT + '/review/' + el.dataset.job, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).catch(() => null);
        if (!j || j.status !== 200) return;
        const r = j.data.review;
        el.innerHTML = r
            ? `<div class="help" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><span class="pill pill-blue">In Pending Allocations</span> sent on ${esc2(r.reviewed_at)} by ${esc2(r.reviewed_by)} · <a href="${ROT}">open Pending Allocations</a>${CAN_ROT ? ' · <a href="#" data-rot-back="1">take back</a>' : ''}</div>`
            : (CAN_ROT ? `<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><button class="btn btn-sm" style="background:#842484;color:#fff" data-rot-send="1"><i class="bi bi-person-check"></i> Driver Allocation</button><span class="help">After checking the itinerary below: sends this card to Pending Allocations, where the rotation proposes the driver.</span></div>`
                       : '<div class="help">Awaiting transport review.</div>');
        const go = async on => {
            const k = await fetch(ROT + '/review/' + el.dataset.job, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ on }) }).then(x => x.json());
            Swal.fire({ icon: k.status === 200 ? 'success' : 'error', text: k.status === 200 ? (on ? 'Sent to Pending Allocations.' : 'Back on Transport Operations.') : k.message, timer: k.status === 200 ? 2200 : undefined, showConfirmButton: k.status !== 200 });
            if (k.status === 200) { delete el.dataset.done; el.innerHTML = ''; fill(el); }
        };
        el.querySelector('[data-rot-send]')?.addEventListener('click', () => go(1));
        el.querySelector('[data-rot-back]')?.addEventListener('click', e => { e.preventDefault(); go(0); });
    }
    new MutationObserver(() => { const el = document.getElementById('c_rot'); if (el) fill(el); }).observe(document.body, { childList: true, subtree: true });
})();

// tf-pilot-r2: opened from the "Crew replacement" or "Driver advance" privilege entry — say where that work is on this desk
document.addEventListener('DOMContentLoaded', () => {
    const f = new URLSearchParams(location.search).get('focus');
    if (f === 'crew_change') toast('info', 'Crew replacement: open the tour\'s card and press "Replace…" on the engaged driver or guide line. The person taking over and the days worked are recorded; both people stay on the card.');
    if (f === 'driver_advance') toast('info', 'Driver advance: open the tour\'s card — the advance, its approval, payment and the settlement are in the card\'s payment section.');
});
</script>
</body>
</html>
