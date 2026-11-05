<?= view('layouts/head') ?>
<style>
    /* ---------- Hotel Bookings (same tokens as the Tour Inquiry panel; the Tour Desk bar re-points them) ---------- */
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
    .tf .tbl tr.rowlink { cursor:pointer; }
    .tf .tbl tr.rowlink:hover td { background:#fafbfd; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .pill-amber { background:var(--tf-amber-soft); color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-teal { background:var(--tf-teal-soft); color:var(--tf-teal); }
    .tf .flag { display:block; font-size:11.5px; color:var(--tf-amber); font-weight:600; margin-top:2px; }
    .tf .flag.bad { color:var(--tf-red); }
    .tf .chips { display:flex; gap:6px; flex-wrap:wrap; }
    .tf .chip { border:1px solid var(--tf-line); background:#fff; border-radius:9px; padding:5px 11px; font-size:12px; font-weight:500; cursor:pointer; color:var(--tf-ink); }
    .tf .chip .n { font-family:ui-monospace, Menlo, monospace; font-size:11px; color:var(--tf-muted); margin-left:4px; }
    .tf .chip.on { background:var(--tf-blue-soft); border-color:transparent; color:var(--tf-navy); font-weight:600; }
    .tf .chip.on .n { color:var(--tf-navy); }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:14px 0; text-align:center; }
    .tf .kv { display:grid; grid-template-columns:130px 1fr; gap:5px 12px; font-size:12.5px; }
    .tf .kv dt { color:var(--tf-muted); font-weight:500; }
    .tf .kv dd { margin:0; }
    .tf .box { border:1px solid var(--tf-line); border-radius:8px; padding:11px 13px; margin-bottom:12px; }
    .tf .box h6 { font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-muted); font-weight:700; margin:0 0 8px; }
    .tf .trail { font-size:12px; }
    .tf .trail div { padding:5px 0; border-bottom:1px dashed var(--tf-line); }
    .tf .trail div:last-child { border-bottom:0; }
    .tf .trail .who { color:var(--tf-muted); }
    .tf .acts { display:flex; gap:6px; flex-wrap:wrap; }
    .tf .amended { border-left:3px solid var(--tf-amber); }
    /* the tour opened: its header, then its hotels and its activity lines */
    .tf .tv-head { display:flex; gap:12px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; padding:12px 14px; }
    .tf .tv-head .who { font-size:14px; font-weight:600; }
    .tf .tv-head .who small { font-weight:400; color:var(--tf-muted); font-size:12px; }
    .tf .tv-mark { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .tf .prog { display:inline-flex; align-items:center; gap:6px; font-size:12px; white-space:nowrap; }
    .tf .prog .bar { width:64px; height:6px; border-radius:3px; background:#e4e8ee; overflow:hidden; display:inline-block; }
    .tf .prog .bar i { display:block; height:100%; background:var(--tf-green); }
    .tf .prog.problem .bar i { background:var(--tf-red); }
    .tf .msg { padding:6px 0; border-bottom:1px dashed var(--tf-line); font-size:12px; }
    .tf .msg:last-child { border-bottom:0; }
    .tf .msg .k { display:inline-block; min-width:64px; font-weight:600; }
    .tf .msg .k.reply { color:var(--tf-teal); }
    .tf .msg .k.failed { color:var(--tf-red); }
    .modal .modal-content { border-radius:12px; }
    .tf .cancel-flag { background:#fbe9e6; color:#8a2a22; border:1px solid #f0b8b0; border-radius:8px; padding:8px 12px; margin:8px 0; font-size:13px; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar', ['tourdesk_desk_only' => 'reservation']) /* tf-resdesk-r1: the Hotel Reservation Desk strip */ ?>
                <?= view('layouts/tourdesk_menu') ?>

                <div class="tf-title">
                    <div>
                        <h4>Hotel Bookings <a class="btn btn-light btn-sm ms-2" style="vertical-align:middle" href="<?= base_url('tour/hotel_bookings/mails') ?>" title="Every hotel and restaurant e-mail, sent or not sent (tf-tourmail-r1)"><i class="bi bi-envelope-paper"></i> Sent Mails</a></h4>
                        <div class="sub">One row per confirmed tour — the tour's one job on this desk, put here the moment the Quotation Desk presses Confirm Tour. Open the tour to see every hotel it needs, with dates, rooms, meal plan and requests, and every activity or ticket to arrange. Send each hotel its booking request from the stay, record what it answers, issue the voucher. When every voucher is issued and every line is done, mark <b>Hotel work complete</b>: the tour leaves the red count and stays under Completed with its whole history. Nothing here changes the quotation.</div>
                    </div>
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
                            <thead><tr><th>Tour No.</th><th>Inquiry No.</th><th>Agent</th><th>Tour / group</th><th>Arrival</th><th>Departure</th><th style="width:50px">Pax</th><th>Hotels</th><th>Activities</th><th>Status</th><th>Handler</th><th style="width:70px"></th></tr></thead>
                            <tbody id="tours"><tr><td colspan="12" class="empty">Loading…</td></tr></tbody>
                        </table>
                    </div>
                </div>

                <!-- the tour opened: its hotels and its activity lines -->
                <div id="tour_view" style="display:none">
                    <div class="tf-card mb-3">
                        <div class="tv-head" id="tv_head"></div>
                        <div style="padding:0 14px 12px">
                            <details id="tv_details_wrap"><summary style="cursor:pointer;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--tf-muted);font-weight:700">The whole tour — programme, passengers, flights, requests</summary><div id="tv_details" class="mt-2"></div></details>
                        </div>
                    </div>
                    <div class="tf-card">
                        <div class="tf-card-h">
                            <div><h6>Hotels required for this tour</h6><div class="help">Every stay the quotation carried, in order. Open a stay to send the booking request, record the hotel's answer and issue the voucher.</div></div>
                        </div>
                        <div style="overflow-x:auto">
                            <table class="tbl">
                                <thead><tr><th>Hotel</th><th style="width:150px">Dates</th><th style="width:190px">Rooms · meal</th><th style="width:60px">Party</th><th style="width:230px">Status</th><th style="width:120px"></th></tr></thead>
                                <tbody id="rows"><tr><td colspan="6" class="empty">Loading…</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tf-card mt-3" id="acts_card">
                        <div class="tf-card-h">
                            <div>
                                <h6>Activities &amp; tickets</h6>
                                <div class="help">Entrance tickets, train tickets, safaris, excursions and anything else the quotation carried as a line — each to be requested and confirmed, given its <b>tickets from stock</b> by the party size (an attraction under Ticket Stock control), marked <b>pay at gate</b> where the entrance is paid at the counter on the day, or marked <b>nothing to arrange</b> with a word on why.</div>
                            </div>
                            <div><button class="btn btn-light btn-sm" id="a_add" title="Something to arrange that the quotation did not carry as a line"><i class="bi bi-plus-lg"></i> Add a line</button></div>
                        </div>
                        <div style="overflow-x:auto">
                            <table class="tbl">
                                <thead><tr><th style="width:110px">Day · date</th><th>What</th><th style="width:60px">Party</th><th style="width:110px">Paid</th><th style="width:220px">Status</th><th style="width:120px"></th></tr></thead>
                                <tbody id="a_rows"><tr><td colspan="6" class="empty">Loading…</td></tr></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- tf-supmail-r1: the reservation requests to the activity suppliers and the restaurants -->
                    <div class="tf-card mt-3" id="sreq_card">
                        <div class="tf-card-h">
                            <div>
                                <h6>Booking requests — activities and restaurants</h6>
                                <div class="help">Prepared from the record in the saved wording as soon as a line is on the tour. Sent by e-mail where the master holds an address; otherwise the same words are here to send by hand or print &mdash; then record what you did. <b>A request is never a confirmation</b>: the line stays pending until you record the supplier's own answer above.</div>
                            </div>
                            <div><button class="btn btn-light btn-sm" id="sreq_refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button></div>
                        </div>
                        <div class="tf-card-b" id="sreq_body"><div class="empty">Loading…</div></div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<!-- Manage: correct a card in place (Management) -->
<div class="modal fade" id="m_edit" tabindex="-1"><div class="modal-dialog"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="e_title">Correct this stay</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-md-8"><label class="form-label help">Hotel</label><input class="form-control form-control-sm" id="e_hotel"></div>
            <div class="col-md-4"><label class="form-label help">Area</label><input class="form-control form-control-sm" id="e_area"></div>
            <div class="col-md-4"><label class="form-label help">Check-in</label><input type="date" class="form-control form-control-sm" id="e_in"></div>
            <div class="col-md-4"><label class="form-label help">Check-out</label><input type="date" class="form-control form-control-sm" id="e_out"></div>
            <div class="col-md-4"><label class="form-label help">Meal plan</label><select class="form-select form-select-sm" id="e_meal"><option value="">Not stated</option><option>RO</option><option>BB</option><option>HB</option><option>FB</option><option>AI</option></select></div>
            <div class="col-md-3"><label class="form-label help">Rooms</label><input type="number" min="0" class="form-control form-control-sm" id="e_rooms"></div>
            <div class="col-md-6"><label class="form-label help">Rooms note</label><input class="form-control form-control-sm" id="e_rooms_note"></div>
            <div class="col-md-3"><label class="form-label help">Party</label><input type="number" min="0" class="form-control form-control-sm" id="e_pax"></div>
            <div class="col-12"><label class="form-label help">Agent's notes</label><textarea class="form-control form-control-sm" id="e_special" rows="2"></textarea></div>
            <div class="col-12"><label class="form-label help">Why <span class="text-muted">(stays on the history)</span></label><input class="form-control form-control-sm" id="e_note" maxlength="200" placeholder="e.g. agent moved the check-in by phone"></div>
        </div>
        <div class="help mt-2">This corrects the card the Hotel Desk works from. The quotation is not changed — amend it from the Tour Board if the sale itself changed. Every change here is written to the history with your name.</div>
        <input type="hidden" id="e_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="e_ok">Save correction</button></div>
</div></div></div>

<!-- the stay -->
<div class="modal fade" id="m_card" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="c_title">Stay</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="c_body"></div>
</div></div></div>

<!-- a letter as it was sent -->
<div class="modal fade" id="m_msg" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="g_title">Letter</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="g_body"></div>
</div></div></div>

<!-- an activity or ticket line -->
<div class="modal fade" id="m_act" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="a_title">Activity</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="a_body"></div>
</div></div></div>

<!-- a line the desk adds itself -->
<div class="modal fade" id="m_addact" tabindex="-1"><div class="modal-dialog"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="aa_title">Add something to arrange</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-12"><label class="form-label help">What</label><input class="form-control form-control-sm" id="aa_what" placeholder="e.g. Train tickets Ella – Kandy, 2nd class"></div>
            <div class="col-md-5"><label class="form-label help">Kind</label><select class="form-select form-select-sm" id="aa_kind"><option value="entrance">Entrance ticket</option><option value="train">Train ticket</option><option value="safari">Safari</option><option value="visit">Sightseeing visit</option><option value="excursion">Excursion</option><option value="ayurveda">Ayurveda / spa</option><option value="lunch">Lunch</option><option value="dinner">Dinner</option><option value="other" selected>Other</option></select></div>
            <div class="col-md-3"><label class="form-label help">Day of tour</label><input type="number" min="1" class="form-control form-control-sm" id="aa_day"></div>
            <div class="col-md-4"><label class="form-label help">Party</label><input type="number" min="0" class="form-control form-control-sm" id="aa_pax"></div>
            <div class="col-12"><label class="form-label help">Note</label><input class="form-control form-control-sm" id="aa_note" maxlength="500"></div>
        </div>
        <div class="help mt-2">For something to arrange that the quotation did not carry as a line of its own. It is not carried back into the quotation — if the sale itself changed, ask Management to amend the tour.</div>
        <input type="hidden" id="aa_tour">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="aa_ok">Add to the desk</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script src="<?= base_url('assets/tourmail/tour-mails.js') ?>?r1"></script>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'tour/hotel_bookings') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const SEES_RATES = <?= !empty($sees_rates) ? 'true' : 'false' ?>;
    const CAN_MANAGE = <?= !empty($can_manage) ? 'true' : 'false' ?>;
    const CAN_INTAKE = <?= !empty($can_intake) ? 'true' : 'false' ?>;   // tf-walkfix-r1: the inquiry is offered only to a sign-in that may open it
    const MANAGE_BASE = '<?= base_url($manage_url ?? 'tour/manage') ?>';
    const INTAKE = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const CAN_TICKETS = <?= !empty($can_tickets) ? 'true' : 'false' ?>;   // tf-tickets-r1: the Ticket Stock screen, linked only to a sign-in that may open it
    const TICKETS = '<?= base_url($tickets_url ?? 'tour/ticket_stock') ?>';
    if (!INSTALLED) return;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2800 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With':'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const fmt = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short' }); };
    const fmtY = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); };
    const when = s => s ? String(s).slice(0, 16) : '';

    // the stay's steps, in the desk's words: pending → sent → confirmed → voucher issued; a problem says which
    const STATUS = {
        to_book: ['To book', 'pill-grey'], requested: ['Sent — awaiting reply', 'pill-blue'], confirmed: ['Confirmed', 'pill-teal'],
        voucher_issued: ['Voucher issued', 'pill-green'], problem: ['Problem', 'pill-red'], cancelled: ['Cancelled', 'pill-grey'],
    };
    const PROBLEM_WORD = { REJECTED: 'Rejected / unavailable', ALTERNATIVE: 'Alternative required' };
    const CHIPS = [['pending', 'Pending'], ['completed', 'Completed'], ['cancelled', 'Cancelled'], ['all', 'All']];
    let WHICH = 'pending', REASONS = [], CARD = null, TOUR = null, TOURS = [];

    // =====================================================================
    //  The list: one row per confirmed tour
    // =====================================================================
    function prog(x, word, problemWord) {
        if (!x || !x.of) return `<span class="help">—</span>`;
        const pct = Math.round(100 * x.done / x.of);
        return `<span class="prog ${x.problem ? 'problem' : ''}" title="${esc(x.done)} of ${esc(x.of)} ${esc(word)}"><span class="bar"><i style="width:${pct}%"></i></span><b>${x.done}</b>/${x.of}${x.problem ? ` <span class="pill pill-red">${x.problem} ${esc(problemWord || 'problem')}</span>` : ''}</span>`;
    }
    // A cancelled tour is flagged in red wherever it shows, with the reason, who cancelled it and when
    const cancelFlag = (t, big) => t.stage !== 'cancelled' ? '' : (big
        ? `<div class="cancel-flag"><i class="bi bi-x-octagon-fill"></i> <b>CANCELLED</b> ${t.cancelled_at ? when(t.cancelled_at) : ''}${t.cancelled_by ? ' by ' + esc(t.cancelled_by) : ''}${t.cancel_why ? ' — ' + esc(t.cancel_why) : ''}<div class="help" style="color:inherit">Do no further work on this tour: every card is marked cancelled. Work already done stays on the trail.</div></div>`
        : `<span class="pill pill-red" title="${esc((t.cancelled_at ? when(t.cancelled_at) : '') + (t.cancelled_by ? ' by ' + t.cancelled_by : '') + (t.cancel_why ? ' — ' + t.cancel_why : ''))}"><i class="bi bi-x-octagon-fill"></i> CANCELLED</span>${t.cancel_why ? `<div class="help" style="color:var(--tf-red)">${esc(t.cancel_why)}</div>` : ''}`);
    function workPill(t) {
        if (t.stage === 'cancelled') return cancelFlag(t, false);
        if (t.work === 'complete') return `<span class="pill pill-green">Complete</span><div class="help">${when(t.work_at)}${t.work_by ? ' · ' + esc(t.work_by) : ''}</div>`;
        if (t.work === 'none') return '<span class="pill pill-grey">Nothing for this desk</span>';
        const s = t.strip || {};
        if (t.problems) return `<span class="pill pill-red">Problem</span><div class="help">${t.problems} to sort out</div>`;
        const started = (s.hotels && (s.hotels.done || s.hotels.confirmed)) || (s.activities && s.activities.done);
        return `<span class="pill ${started ? 'pill-blue' : 'pill-amber'}">${started ? 'In progress' : 'Pending'}</span>${s.reservations_ready ? '<div class="help">ready to mark complete</div>' : ''}`;
    }
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
    async function loadTours() {
        const j = await api('/tours?' + filterQs());
        if (j.status !== 200) { toast('error', j.message); return; }
        renderChips(j.data.counts || {});
        updateBadge((j.data.counts || {}).pending);
        const rows = TOURS = j.data.tours || [];
        const tb = document.getElementById('tours');
        if (!rows.length) {
            const empty = { pending: 'No tour is waiting for hotel work. Every confirmed tour has its hotels booked, vouchers issued and activities done.', completed: 'No tour marked complete yet.', cancelled: 'No cancelled tour.', all: 'No confirmed tour yet.' }[WHICH];
            tb.innerHTML = `<tr><td colspan="12" class="empty">${document.getElementById('f_q').value || document.getElementById('f_handler').value ? 'Nothing matches the filter.' : empty}</td></tr>`;
            return;
        }
        tb.innerHTML = rows.map(t => `<tr class="rowlink" data-tour="${t.id}">
            <td><b>${esc(t.tour_no)}</b>${Number(t.amending) ? '<div class="flag">being amended</div>' : ''}${t.amended ? '<div class="flag">amended — check the cards</div>' : ''}</td>
            <td class="mono">${esc(t.inquiry_no || '—')}</td>
            <td>${esc(t.agent_name || '—')}</td>
            <td>${esc(t.tour_name || '—')}${t.hotel_class ? `<div class="help">${esc(t.hotel_class)}</div>` : ''}</td>
            <td class="mono">${fmtY(t.arrival_date)}</td>
            <td class="mono">${fmtY(t.departure_date)}<div class="help">${t.nights ?? '?'} nights</div></td>
            <td class="mono">${esc(t.pax_total ?? '?')}</td>
            <td>${prog(t.strip && t.strip.hotels, 'stays with the voucher issued')}${t.strip && t.strip.hotels && t.strip.hotels.confirmed ? `<div class="help">${t.strip.hotels.confirmed} confirmed, voucher to issue</div>` : ''}${t.strip && t.strip.hotels && t.strip.hotels.flags ? `<div class="flag">${t.strip.hotels.flags} to ask the Quotation Desk</div>` : ''}</td>
            <td>${prog(t.strip && t.strip.activities, 'activity and ticket lines done')}</td>
            <td>${workPill(t)}</td>
            <td>${t.handler_name ? esc(t.handler_name) : '<span class="pill pill-amber">nobody</span>'}</td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-open-tour="${t.id}">Open</button>${CAN_MANAGE ? ` <button class="td-dots" data-tmanage="${t.id}" title="Admin" type="button">···</button>` : ''}</td></tr>`).join('');
        tb.querySelectorAll('[data-open-tour]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); openTour(Number(b.dataset.openTour)); }));
        tb.querySelectorAll('tr[data-tour]').forEach(r => r.addEventListener('click', () => openTour(Number(r.dataset.tour))));
        tb.querySelectorAll('[data-tmanage]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); const t = TOURS.find(x => Number(x.id) === Number(b.dataset.tmanage)); if (t) tourManageMenu(b, t); }));
    }

    // =====================================================================
    //  The tour opened: its hotels and its activity lines
    // =====================================================================
    let ITEMS = [], A_ITEMS = [], A_REASONS = [], ACT = null, DETAILS = {};
    async function openTour(id, quiet) {
        const j = await api('/tour/' + id + '/work');
        if (j.status !== 200) { toast('error', j.message); return; }
        TOUR = j.data.tour; REASONS = j.data.reasons || []; A_REASONS = j.data.a_reasons || [];
        ITEMS = j.data.stays || []; A_ITEMS = j.data.activities || [];
        document.getElementById('list_card').style.display = 'none';
        document.getElementById('tour_view').style.display = '';
        // tf-refresh-r1: the tour is in the address (a refresh opens it again, read fresh); opening one is a step Back can undo
        if (!quiet) { if (window.TfView) TfView.setParams({ tour: id }, { push: true }); else { try { history.replaceState(null, '', location.pathname + '?tour=' + id); } catch (e) {} } }
        renderTourHead();
        renderStays();
        renderActs();
        loadSupplierRequests(id);
        loadDetails(id, 'tv_details');
        if (!quiet) window.scrollTo({ top: 0 });
    }

    // =====================================================================
    //  tf-supmail-r1: the reservation requests to the activity suppliers and the restaurants
    //  Reading this card prepares what is missing; it never sends. Sending is a press, or the
    //  workflow's own automatic step where that is switched on for the installation.
    // =====================================================================
    let SREQ = null;
    async function loadSupplierRequests(tourId) {
        const box = document.getElementById('sreq_body');
        if (!box) { return; }
        box.innerHTML = '<div class="empty">Loading…</div>';
        const j = await api('/tour/' + tourId + '/supplier_requests');
        if (j.status !== 200) { box.innerHTML = '<div class="empty">' + esc(j.message || 'The requests could not be read.') + '</div>'; return; }
        SREQ = j.data || {};
        renderSupplierRequests();
    }

    function renderSupplierRequests() {
        const box = document.getElementById('sreq_body');
        if (!box || !SREQ) { return; }
        if (SREQ.installed === false) {
            box.innerHTML = '<div class="empty">This install does not have the supplier-request table yet. Open Tour Inquiry as the administrator and press Install now.</div>';
            return;
        }
        const msgs = (SREQ.messages || []).filter(m => m.kind === 'request');
        const label = { prepared: ['pill-amber', 'Prepared — not sent'], sent: ['pill-green', 'Sent'], failed: ['pill-red', 'NOT sent'], recorded: ['pill-grey', 'Sent by hand'] };
        box.innerHTML =
              (SREQ.auto_send === false ? '<div class="note mb-2">Automatic sending is switched off on this installation, so these letters are written and wait here. Send each one when you are ready.</div>' : '')
            + ((SREQ.missing || []).length ? '<div class="note mb-2"><b>Still missing:</b><ul class="mb-0">' + SREQ.missing.map(w => '<li>' + esc(w) + '</li>').join('') + '</ul></div>' : '')
            + (msgs.length
                ? '<div style="overflow-x:auto"><table class="tbl"><thead><tr><th style="width:190px">Who</th><th>The request</th><th style="width:150px">State</th><th style="width:230px"></th></tr></thead><tbody>'
                  + msgs.map(m => {
                      const [cls, text] = label[m.state] || ['pill-grey', m.state];
                      return '<tr><td><b>' + esc(m.party_name || '') + '</b>'
                        + '<div class="help">' + (m.party === 'restaurant' ? 'restaurant' : 'activity supplier') + '</div>'
                        + '<div class="help">' + (m.to_addr ? esc(m.to_addr) : '<span class="pill pill-amber">no address</span>') + '</div></td>'
                        + '<td><div class="help">' + esc(m.subject || '') + '</div>'
                        + '<details><summary class="help" style="cursor:pointer">The words that go</summary>'
                        + '<pre style="white-space:pre-wrap;font:inherit;font-size:12.5px;margin:6px 0 0">' + esc(m.body || '') + '</pre></details></td>'
                        + '<td><span class="pill ' + cls + '">' + esc(text) + '</span>'
                        + '<div class="help">' + esc(String(m.at || '').slice(0, 16)) + (m.by_name ? ' · ' + esc(m.by_name) : '') + '</div>'
                        + (m.error ? '<div class="help" style="color:var(--tf-red)">' + esc(m.error) + '</div>' : '')
                        + '<div class="help">a request is not a confirmation</div>'
                        + (m.hold ? '<div class="help" style="color:var(--tf-red);margin-top:4px"><b>Held:</b> ' + esc(m.hold) + '</div>' : '')
                        + '</td>'
                        + '<td style="white-space:nowrap">'
                        + (m.state !== 'sent' && m.to_addr && !m.hold ? '<button class="btn btn-light btn-sm" data-sqsend="' + m.id + '">Send by e-mail</button> ' : '')
                        + (m.hold ? '<span class="pill pill-red">cannot be sent yet</span> ' : '')
                        + (m.state === 'prepared' ? '<button class="btn btn-light btn-sm" data-sqedit="' + m.id + '">Edit the words</button> ' : '')
                        + '<button class="btn btn-light btn-sm" data-sqhand="' + m.id + '">Sent by hand</button> '
                        + '<button class="btn btn-light btn-sm" data-sqreply="' + m.id + '">Record the answer</button></td></tr>';
                  }).join('')
                  + '</tbody></table></div>'
                : '<div class="empty">No activity or restaurant line on this tour yet, so there is nothing to request.</div>');
        document.querySelectorAll('[data-sqsend]').forEach(b => b.addEventListener('click', () => sqSend(b.dataset.sqsend)));
        document.querySelectorAll('[data-sqedit]').forEach(b => b.addEventListener('click', () => sqEdit(b.dataset.sqedit)));
        document.querySelectorAll('[data-sqhand]').forEach(b => b.addEventListener('click', () => sqNote(b.dataset.sqhand, 'request')));
        document.querySelectorAll('[data-sqreply]').forEach(b => b.addEventListener('click', () => sqNote(b.dataset.sqreply, 'reply')));
        const r = document.getElementById('sreq_refresh');
        if (r && !r.dataset.wired) { r.dataset.wired = '1'; r.addEventListener('click', () => { if (TOUR) loadSupplierRequests(TOUR.id); }); }
    }

    // this one letter's own words; the saved template on Master Files is never touched
    async function sqEdit(id) {
        const m = ((SREQ && SREQ.messages) || []).find(x => Number(x.id) === Number(id));
        if (!m) { return; }
        const subject = window.prompt('Subject for this letter only:', m.subject || '');
        if (subject === null) { return; }
        const body = window.prompt('The message for this letter only (the saved template is not changed):', m.body || '');
        if (body === null) { return; }
        const j = await api('/supplier_request/' + id + '/edit', { subject: subject, body: body });
        toast(j.status === 200 ? 'ok' : 'error', j.message || '');
        if (TOUR) { loadSupplierRequests(TOUR.id); }
    }

    async function sqSend(id) {
        const j = await api('/supplier_request/' + id + '/send', {});
        toast(j.status === 200 ? 'ok' : 'error', j.message || '');
        if (TOUR) { loadSupplierRequests(TOUR.id); }
    }

    async function sqNote(id, kind) {
        const what = kind === 'reply' ? 'What did they answer?' : 'How did it go, and anything worth keeping?';
        const note = window.prompt(what, '');
        if (note === null) { return; }
        const j = await api('/supplier_request/' + id + '/record', { channel: kind === 'reply' ? 'other' : 'whatsapp', note: note, kind: kind });
        toast(j.status === 200 ? 'ok' : 'error', j.message || '');
        if (TOUR) { loadSupplierRequests(TOUR.id); }
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
        loadTours();   // the counts on the chips and the bar move with the work
    }
    function renderTourHead() {
        const t = TOUR, s = t.strip || {};
        const mark = t.stage === 'cancelled' ? '' : (s.reservations_confirmed
            ? `<span class="pill pill-green" style="font-size:12px;padding:4px 10px"><i class="bi bi-patch-check-fill"></i> Hotel work complete · ${esc(s.reservations_confirmed_by || '')} ${when(s.reservations_confirmed_at)}</span> <button class="btn btn-light btn-sm" data-unmark="${t.id}" title="Take the mark off (say why)">undo</button>`
            : (s.reservations_ready
                ? `<button class="btn btn-success btn-sm" data-mark="${t.id}"><i class="bi bi-patch-check"></i> Mark hotel work complete</button>`
                : `<span class="help" title="Every stay with its voucher issued and every activity line confirmed, or with nothing to arrange">Mark complete once every voucher is issued and every line is done</span>`));
        document.getElementById('tv_head').innerHTML = `
            <div>
                <button class="btn btn-light btn-sm mb-2" id="tv_back"><i class="bi bi-arrow-left"></i> All tours</button>
                <div class="who">${esc(t.tour_no)} · ${esc(t.tour_name || '—')} <small>· ${esc(t.agent_name || '—')} · inquiry ${esc(t.inquiry_no || '—')}</small></div>
                <div class="help">${t.dates_line ? esc(t.dates_line) : `${fmtY(t.arrival_date)} – ${fmtY(t.departure_date)} · ${t.nights ?? '?'} nights`} · ${esc(t.pax_total ?? '?')} pax${t.hotel_class ? ' · ' + esc(t.hotel_class) : ''} · handled by ${t.handler_name ? `<b>${esc(t.handler_name)}</b>` : '<span class="pill pill-amber">nobody</span>'}${t.quote_version ? ' · sold as v' + t.quote_version : ''}${t.stage === 'cancelled' ? ' · <span class="pill pill-grey">cancelled</span>' : ''}</div>
                ${cancelFlag(t, true)}
                <div class="mt-2" id="tv_strip">${window.tdStrip(s, { compact: true })}</div>
            </div>
            <div class="tv-mark">${mark}${t.stage === 'cancelled' ? '' : `<button class="btn btn-primary btn-sm" id="tv_mailall" title="The booking request to every hotel still to book — listed first, sent on OK"><i class="bi bi-envelope"></i> Email all hotels</button>`}<button class="btn btn-light btn-sm" id="tv_mails" title="Every hotel and restaurant e-mail of this tour, sent or not sent"><i class="bi bi-envelope-paper"></i> Mails</button><a class="btn btn-light btn-sm" href="${BASE}/tour/${t.id}/programme" target="_blank"><i class="bi bi-printer"></i> Print the programme</a></div>`;
        document.getElementById('tv_back').addEventListener('click', backToList);
        // tf-tourmail-r1: every hotel's request in one go (listed first, sent on OK), and the tour's mails as they went
        const ma = document.getElementById('tv_mailall'); if (ma) ma.addEventListener('click', () => mailAllHotels(t));
        document.getElementById('tv_mails').addEventListener('click', () => window.AvhTourMails && window.AvhTourMails.showTourMails(BASE, t.id, t.tour_no));
        wireMarks(document.getElementById('tv_head'));
    }
    /** tf-tourmail-r1: the booking request to every hotel of the tour still to book — the list first, sent only on OK. */
    async function mailAllHotels(t) {
        if (!window.AvhTourMails) return;
        const pj = await api('/tour/' + t.id + '/mail_plan');
        if (pj.status !== 200) { toast('error', pj.message); return; }
        const open = (pj.data.stays || []).filter(s => !s.already);
        if (!open.length) { await Swal.fire({ icon: 'info', title: 'Nothing to send', text: 'Every hotel of ' + t.tour_no + ' has been requested already. A stay is sent again only from its own card (Send).' }); return; }
        const choice = await window.AvhTourMails.review(pj.data, { title: 'Email all hotels — ' + t.tour_no, intro: 'Each hotel below that is still to book gets its booking request by e-mail when you press <b>Send</b>. Check the addresses; untick one to leave it out.', ok: 'Send', focusCancel: true });
        if (!choice) return;
        Swal.fire({ title: 'Sending…', html: 'The booking requests are going from your mailbox — this can take a minute.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let j;
        try { j = await post('/tour/' + t.id + '/mail_all', choice); }
        catch (e) { await Swal.fire({ icon: 'error', title: 'No clear answer from the server', html: 'Before sending again, open <b>Mails</b> on this tour to see which requests went.' }); refreshTour(); return; }
        Swal.close();
        if (j.status !== 200) { toast('error', j.message); return; }
        const bad = (j.data.results || []).some(r => r.status === 'not_sent');
        await Swal.fire({ icon: bad ? 'warning' : 'success', title: bad ? 'Some requests were NOT sent' : 'Booking requests sent', width: 820, html: `<div style="text-align:left;font-size:13px">${esc(j.message)}${window.AvhTourMails.resultsHtml(j.data.results, [])}</div>`, confirmButtonText: 'Done' });
        if (window.AvhMailAlert) window.AvhMailAlert();
        refreshTour();
    }
    async function markTour(tourId, on) {
        let why = '';
        if (!on) {
            const r = await Swal.fire({ title: 'Take the mark off?', input: 'text', inputPlaceholder: 'Why (required, stays on the trail)', showCancelButton: true, confirmButtonText: 'Take it off' });
            if (!r.isConfirmed) return;
            why = r.value || '';
        } else {
            const r = await Swal.fire({ title: 'Hotel work complete?', text: 'Every stay on this tour has its voucher issued and every activity or ticket line is confirmed or has nothing to arrange. The tour leaves the Hotel Bookings count and stays under Completed; the inquiry, the Transport Desk and the Board will show the mark.', icon: 'question', showCancelButton: true, confirmButtonText: 'Mark it complete', confirmButtonColor: '#1f8a4c' });
            if (!r.isConfirmed) return;
        }
        const j = await post('/tour/' + tourId + '/complete', { on: on ? 1 : 0, why });
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); refreshTour();
    }
    function wireMarks(el) {
        el.querySelectorAll('[data-mark]').forEach(b => b.addEventListener('click', () => markTour(Number(b.dataset.mark), true)));
        el.querySelectorAll('[data-unmark]').forEach(b => b.addEventListener('click', () => markTour(Number(b.dataset.unmark), false)));
    }

    function pill(j) {
        const st = typeof j === 'string' ? j : j.status;
        const code = typeof j === 'string' ? null : j.problem_code;
        if (st === 'problem' && code && PROBLEM_WORD[code]) return `<span class="pill pill-red">${esc(PROBLEM_WORD[code])}</span>`;
        const [l, c] = STATUS[st] || [st, 'pill-grey']; return `<span class="pill ${c}">${esc(l)}</span>`;
    }
    function flags(j) {
        let out = '';
        if (Number(j.needs_hotel)) out += `<span class="flag bad">Hotel not named — ask the Quotation Desk</span>`;
        if (Number(j.needs_supplier)) out += `<span class="flag">No supplier record — the name is as the agent wrote it; see Incomplete Work</span>`;
        if (Number(j.needs_meal)) out += `<span class="flag">Meal plan not stated — ask the Quotation Desk</span>`;
        if (Number(j.amended)) out += `<span class="flag">The quotation was amended after this card moved — check it</span>`;
        return out;
    }
    function renderStays() {
        const items = ITEMS.filter(j => !j.archived_at);
        const tb = document.getElementById('rows');
        if (!items.length) { tb.innerHTML = `<tr><td colspan="6" class="empty">No hotel stay on this tour.</td></tr>`; return; }
        tb.innerHTML = items.map(j => {
            const rooms = [j.rooms ? j.rooms + ' room' + (Number(j.rooms) === 1 ? '' : 's') : null, j.rooms_note, j.meal_plan || '<span class="pill pill-amber">meal plan not stated</span>'].filter(Boolean).join(' · ');
            return `<tr class="${Number(j.amended) ? 'amended' : ''}">
                <td>${j.hotel_name ? `<b>${esc(j.hotel_name)}</b>` : '<span class="pill pill-red">no hotel named</span>'}<div class="help">${esc(j.area || '')}${j.day_from ? ` · night${j.day_to !== j.day_from ? 's' : ''} ${j.day_from}${j.day_to !== j.day_from ? '–' + j.day_to : ''}` : ''}</div>${flags(j)}</td>
                <td class="mono">${fmt(j.check_in)} → ${fmt(j.check_out)}<div class="help">${j.nights} night${Number(j.nights) === 1 ? '' : 's'}</div></td>
                <td>${rooms}${j.special_note ? `<div class="help" title="${esc(j.special_note)}">${esc(String(j.special_note).slice(0, 80))}${String(j.special_note).length > 80 ? '…' : ''}</div>` : ''}</td>
                <td class="mono">${esc(j.pax ?? '')}</td>
                <td>${pill(j)}<div class="help">${statusLine(j)}</div></td>
                <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-open="${j.id}">Open</button>${CAN_MANAGE ? ` <button class="td-dots" data-manage="${j.id}" title="Manage" type="button">···</button>` : ''}</td></tr>`;
        }).join('');
        tb.querySelectorAll('[data-open]').forEach(b => b.addEventListener('click', () => openCard(Number(b.dataset.open))));
        tb.querySelectorAll('[data-manage]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); const c = ITEMS.find(x => Number(x.id) === Number(b.dataset.manage)); if (c) manageMenu(b, c); }));
    }

    // =====================================================================
    //  Activities & tickets
    // =====================================================================
    const A_STATUS = {
        to_book: ['To book', 'pill-grey'], requested: ['Requested', 'pill-blue'], confirmed: ['Confirmed', 'pill-green'],
        not_needed: ['Nothing to arrange', 'pill-teal'], problem: ['Problem', 'pill-red'], cancelled: ['Cancelled', 'pill-grey'],
        pay_at_gate: ['Pay at gate', 'pill-amber'],   // tf-tickets-r1: the entrance is paid at the counter on the day — its own status
    };
    const KIND = { entrance: 'Entrance ticket', train: 'Train ticket', safari: 'Safari', visit: 'Visit', excursion: 'Excursion', ayurveda: 'Ayurveda', lunch: 'Lunch', dinner: 'Dinner', other: 'Other' };
    const PAID = { in_tour: 'in the tour price', prepaid: 'prepaid', direct: 'paid direct by the party', unknown: 'not stated' };
    function apill(st) { const [l, c] = A_STATUS[st] || [st, 'pill-grey']; return `<span class="pill ${c}">${esc(l)}</span>`; }
    function areason(code) { const r = A_REASONS.find(x => x.code === code); return r ? r.name : (code || ''); }
    function aStatusLine(j) {
        switch (j.status) {
            case 'requested': return `sent ${when(j.requested_at)}${j.requested_at ? ' · ' + daysSince(j.requested_at) : ''}`;
            case 'confirmed': return `${j.confirmed_with === 'Ticket stock' ? '<b>' + esc(j.confirmation_ref || '') + '</b> from stock' : 'ref ' + esc(j.confirmation_ref || '')} · ${when(j.confirmed_at)}`;
            case 'not_needed': return esc(j.request_note || '');
            case 'pay_at_gate': return esc(j.request_note || 'paid at the counter on the day');
            case 'problem': return esc(j.problem_note || areason(j.problem_code));
            default: return '';
        }
    }
    function renderActs() {
        const items = A_ITEMS.filter(j => !j.archived_at);
        const tb = document.getElementById('a_rows');
        if (!items.length) { tb.innerHTML = `<tr><td colspan="6" class="empty">Nothing to arrange on this tour beyond the hotels.</td></tr>`; return; }
        tb.innerHTML = items.map(j => `<tr class="${Number(j.amended) ? 'amended' : ''}">
            <td class="mono">${j.day_no ? 'Day ' + j.day_no : '—'}<div class="help">${fmt(j.on_date)}</div></td>
            <td><b>${esc(j.title)}</b><div class="help">${esc(KIND[j.kind] || j.kind)}${j.source === 'desk' ? ' · added by the desk' : (j.source === 'excursion' ? ' · excursion on the quotation' : '')}${j.special_note ? ' — ' + esc(j.special_note) : ''}</div>${Number(j.amended) ? '<span class="flag">The quotation was amended after this line moved — check it</span>' : ''}</td>
            <td class="mono">${esc(j.pax ?? '')}${j.qty && Number(j.qty) !== Number(j.pax) ? `<div class="help">× ${esc(j.qty)}</div>` : ''}</td>
            <td class="help">${esc(PAID[j.pay_mode] || j.pay_mode || '—')}</td>
            <td>${apill(j.status)}<div class="help">${aStatusLine(j)}</div></td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-aopen="${j.id}">Open</button>${CAN_MANAGE ? ` <button class="td-dots" data-amanage="${j.id}" title="Manage" type="button">···</button>` : ''}</td></tr>`).join('');
        tb.querySelectorAll('[data-aopen]').forEach(b => b.addEventListener('click', () => openAct(Number(b.dataset.aopen))));
        tb.querySelectorAll('[data-amanage]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); const c = A_ITEMS.find(x => Number(x.id) === Number(b.dataset.amanage)); if (c) aManageMenu(b, c); }));
    }
    function aManageMenu(btn, c) {
        const items = [{ title: `${c.tour_no} · ${c.title}` }];
        if (c.archived_at) {
            items.push({ label: 'Restore to the desk', icon: 'bi-arrow-counterclockwise', hint: 'Back in the queue as it was', onClick: () => aAct2('/service_card/' + c.id + '/restore', {}) });
        } else {
            const busy = ['requested', 'confirmed'].includes(c.status);
            items.push({ label: 'Archive…', icon: 'bi-archive', hint: busy ? 'Not while the supplier holds it — back to "to book" first' : 'Off the desk, kept, restorable', disabled: busy, onClick: async () => {
                const r = await Swal.fire({ title: 'Archive this line?', text: 'Are you sure you want to archive this record? It is kept and can be restored from Manage → Archived.', input: 'text', inputPlaceholder: 'Why (optional, stays on the history)', showCancelButton: true, confirmButtonText: 'Archive' });
                if (r.isConfirmed) aAct2('/service_card/' + c.id + '/archive', { note: r.value || '' });
            } });
        }
        if (CAN_INTAKE) items.push({ sep: true });
        if (CAN_INTAKE) items.push({ label: 'Open the inquiry', icon: 'bi-box-arrow-up-right', hint: 'The quotation and its history', onClick: () => window.open(INTAKE + '?id=' + c.intake_id, '_blank') });
        window.tdMenu(btn, items);
    }
    async function aAct2(path, body) {
        const j = await manage(path, body);
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message);
        if (ACT && j.data && Number(j.data.id) === Number(ACT.id)) { ACT = j.data; renderAct(); }
        refreshTour();
    }
    document.getElementById('a_add')?.addEventListener('click', () => {
        if (!TOUR) return;
        document.getElementById('aa_tour').value = TOUR.id;
        ['aa_what', 'aa_day', 'aa_pax', 'aa_note'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('aa_kind').value = 'other';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_addact')).show();
    });
    document.getElementById('aa_ok')?.addEventListener('click', async () => {
        const j = await post('/tour/' + document.getElementById('aa_tour').value + '/activity', { title: document.getElementById('aa_what').value, kind: document.getElementById('aa_kind').value, day_no: document.getElementById('aa_day').value, pax: document.getElementById('aa_pax').value, note: document.getElementById('aa_note').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        bootstrap.Modal.getInstance(document.getElementById('m_addact')).hide();
        toast('success', j.message); refreshTour();
    });
    async function openAct(id) {
        const j = await api('/activity/' + id);
        if (j.status !== 200) { toast('error', j.message); return; }
        ACT = j.data; renderAct();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_act')).show();
    }
    function renderAct() {
        const c = ACT, st = c.status;
        document.getElementById('a_title').textContent = `${c.tour_no} · ${c.title}`;
        // tf-tickets-r1: tickets from stock (the attraction the line's fee row is under, or one chosen), pay at gate, tickets used
        const fromStock = st === 'confirmed' && c.confirmed_with === 'Ticket stock';
        const stockAtt = c.attraction && c.attraction.control === 'stock' ? c.attraction : null;
        const gateAtt = c.attraction && c.attraction.control === 'pay_at_gate' ? c.attraction : null;
        const can = {
            request: ['to_book', 'problem'].includes(st) && !gateAtt, confirm: ['to_book', 'requested', 'problem'].includes(st) && !gateAtt,
            not_needed: ['to_book', 'requested', 'problem'].includes(st) && !stockAtt && !gateAtt, problem: ['to_book', 'requested', 'confirmed', 'not_needed', 'pay_at_gate'].includes(st),
            back: ['problem', 'requested', 'confirmed', 'not_needed', 'pay_at_gate'].includes(st),
            tickets: c.stock_on && (c.attractions || []).some(a => a.control === 'stock') && (['to_book', 'requested', 'problem'].includes(st) || fromStock) && !gateAtt,
            pay_at_gate: c.stock_on && ['to_book', 'requested', 'problem'].includes(st) && !stockAtt,
            tickets_used: fromStock && (c.tickets || []).some(t => t.status === 'allocated'),
        };
        document.getElementById('a_body').innerHTML = `
            <div class="mb-3">${window.tdStrip(c.strip, { compact: true })}</div>
            <div class="row g-3">
              <div class="col-md-7">
                <div class="box">
                  <h6>The line</h6>
                  <dl class="kv">
                    <dt>Tour</dt><dd><b>${esc(c.tour_no)}</b> · ${esc(c.tour_name || '—')} · ${esc(c.agent_name || '—')}<div class="help">${fmt(c.arrival_date)} – ${fmtY(c.departure_date)} · ${esc(c.tour_pax ?? '?')} pax</div></dd>
                    <dt>Handling agent</dt><dd>${c.handler_name ? `<b>${esc(c.handler_name)}</b>` : '<span class="pill pill-amber">nobody</span>'}${c.archived_at ? `<div class="flag" style="color:var(--tf-muted)">Archived ${when(c.archived_at)} by ${esc(c.archived_by || '')}${c.archive_note ? ' — ' + esc(c.archive_note) : ''}</div>` : ''}</dd>
                    <dt>What</dt><dd><b>${esc(c.title)}</b><div class="help">${esc(KIND[c.kind] || c.kind)}${c.source === 'desk' ? ' · added by the desk' : (c.source === 'excursion' ? ' · an excursion on the quotation' : ' · a service line on the quotation')}</div></dd>
                    <dt>When</dt><dd class="mono">${c.day_no ? 'Day ' + c.day_no : '—'}${c.on_date ? ' · ' + fmtY(c.on_date) : ''}</dd>
                    <dt>Time</dt><dd><div class="d-flex align-items-center gap-2">
                        <input type="time" class="form-control form-control-sm" style="width:130px" id="a_time" value="${esc((c.on_time || '').slice(0, 5))}">
                        <button class="btn btn-light btn-sm" id="a_savetime">Save the time</button>
                        ${c.on_time ? '<button class="btn btn-light btn-sm" id="a_cleartime" title="Take the time off this line">Clear the time</button>' : ''}</div>
                        <div class="help">The hour this line is booked for. A supplier asked to hold something needs it: a request whose wording asks for the time is held until one is entered. Leave it empty if no time has been agreed.</div></dd>
                    <dt>Party</dt><dd class="mono">${esc(c.pax ?? '—')}${c.qty ? ` <span class="help">· quantity ${esc(c.qty)}</span>` : ''}</dd>
                    <dt>Paid</dt><dd>${esc(PAID[c.pay_mode] || c.pay_mode || '—')}</dd>
                    ${c.meal ? `<dt>Meal (Meal Master)</dt><dd><b>${esc(c.meal.restaurant)}</b> · ${esc(c.meal.location)} <span class="help mono">${esc(c.meal.code)}</span>
                        <div class="help">${c.meal.time ? 'at ' + esc(c.meal.time) + ' · ' : ''}${c.meal.adults != null ? c.meal.adults + ' adult' + (c.meal.adults === 1 ? '' : 's') : ''}${c.meal.children ? ', ' + c.meal.children + ' child' + (c.meal.children === 1 ? '' : 'ren') : ''}${c.meal.total != null ? ' · ' + esc(c.meal.currency) + ' ' + Number(c.meal.total).toLocaleString(undefined, { minimumFractionDigits: 2 }) + (c.meal.tax ? ' incl. taxes ' + Number(c.meal.tax).toLocaleString(undefined, { minimumFractionDigits: 2 }) : '') : ''}</div>
                        ${c.meal.contact ? `<div class="help">${esc(c.meal.contact)}</div>` : ''}${c.meal.address ? `<div class="help">${esc(c.meal.address)}</div>` : ''}
                        <div class="help">${esc([c.meal.supplier ? 'Supplier: ' + c.meal.supplier + (c.meal.supplier_acc ? ' (' + c.meal.supplier_acc + ')' : '') : '', c.meal.cuisine, c.meal.description].filter(Boolean).join(' · '))}</div>
                        ${c.meal.outside_note ? `<div class="help" style="color:var(--tf-navy)">Outside although the hotel gives it: ${esc(c.meal.outside_note)}</div>` : ''}</dd>` : ''}
                    ${c.special_note ? `<dt>Note on the quotation</dt><dd style="white-space:pre-line">${esc(c.special_note)}</dd>` : ''}
                  </dl>
                </div>
                <div class="box">
                  <h6>Desk note</h6>
                  <textarea class="form-control form-control-sm" id="a_note" rows="2" placeholder="Anything the next person should know">${esc(c.note || '')}</textarea>
                  <div class="mt-2"><button class="btn btn-light btn-sm" id="a_savenote">Save note</button></div>
                </div>
              </div>
              <div class="col-md-5">
                <div class="box">
                  <h6>Where it stands</h6>
                  <div class="mb-2">${apill(st)}</div>
                  <dl class="kv" style="grid-template-columns:96px 1fr">
                    ${c.requested_at ? `<dt>Requested</dt><dd>${when(c.requested_at)} · ${esc(c.requested_by || '')}${c.request_note && st !== 'not_needed' ? `<div class="help">${esc(c.request_note)}</div>` : ''}</dd>` : ''}
                    ${c.confirmation_ref && !fromStock ? `<dt>Confirmed</dt><dd>ref <b>${esc(c.confirmation_ref)}</b>${c.confirmed_with ? ' with ' + esc(c.confirmed_with) : ''}<div class="help">${when(c.confirmed_at)} · ${esc(c.confirmed_by || '')}</div></dd>` : ''}
                    ${(c.tickets || []).length ? `<dt>Tickets</dt><dd><b class="mono">${esc(c.ticket_text)}</b> <span class="help">· ${esc(c.tickets[0].attraction)} · ${c.tickets.length} from stock${c.tickets.some(t => t.status === 'used') ? ' · ' + c.tickets.filter(t => t.status === 'used').length + ' used' : ''}</span><div class="help">${when(c.tickets[0].allocated_at)} · ${esc(c.tickets[0].allocated_by || '')}</div></dd>` : ''}
                    ${st === 'not_needed' ? `<dt>Nothing to arrange</dt><dd>${esc(c.request_note || '')}</dd>` : ''}
                    ${st === 'pay_at_gate' ? `<dt>Pay at gate</dt><dd>${esc(c.request_note || 'paid at the counter on the day')}</dd>` : ''}
                    ${c.stock_on && ['to_book', 'requested', 'problem'].includes(st) ? `<dt>Entrance</dt><dd class="help">${stockAtt ? `<b>${esc(stockAtt.name)}</b> is under ticket control: ${stockAtt.n_available} available${stockAtt.n_available < (c.pax || 0) ? ' — <span style="color:var(--tf-red)">fewer than the party of ' + esc(c.pax) + '</span>' : ''}` : (gateAtt ? `<b>${esc(gateAtt.name)}</b> is paid at the gate on the day` : (c.gate_hint ? 'the Entrance Fee master flags this one as paid direct at the gate — mark it so if that is right' : 'not matched to an attraction under ticket control' + (CAN_TICKETS ? ` · <a href="${TICKETS}" target="_blank">Ticket Stock</a>` : '')))}</dd>` : ''}
                    ${st === 'problem' ? `<dt>Problem</dt><dd><b>${esc(areason(c.problem_code))}</b>${c.problem_note ? `<div class="help">${esc(c.problem_note)}</div>` : ''}<div class="help">${when(c.problem_at)} · ${esc(c.problem_by || '')}</div></dd>` : ''}
                  </dl>
                  <div class="acts mt-2">
                    ${can.tickets ? `<button class="btn ${stockAtt ? 'btn-primary' : 'btn-light'} btn-sm" data-aact="tickets">${fromStock ? 'More tickets from stock' : 'Allocate tickets from stock'}</button>` : ''}
                    ${can.tickets_used ? `<button class="btn btn-light btn-sm" data-aact="tickets_used">Tickets used</button>` : ''}
                    ${can.pay_at_gate ? `<button class="btn ${gateAtt ? 'btn-primary' : 'btn-light'} btn-sm" data-aact="pay_at_gate">Pay at gate</button>` : ''}
                    ${can.request ? `<button class="btn ${stockAtt || gateAtt ? 'btn-light' : 'btn-primary'} btn-sm" data-aact="request">Request</button>` : ''}
                    ${can.confirm ? `<button class="btn btn-light btn-sm" data-aact="confirm">Record confirmation</button>` : ''}
                    ${can.not_needed ? `<button class="btn btn-light btn-sm" data-aact="not_needed">Nothing to arrange</button>` : ''}
                    ${can.problem ? `<button class="btn btn-light btn-sm" style="color:var(--tf-red)" data-aact="problem">Problem</button>` : ''}
                    ${can.back ? `<button class="btn btn-light btn-sm" data-aact="back">Back to "to book"</button>` : ''}
                  </div>
                  <div id="a_form" class="mt-2"></div>
                </div>
                ${mealBookingBox(c)}
                <div class="box">
                  <h6>Trail</h6>
                  <div class="trail">${(c.events || []).map(e => `<div><b>${esc(e.event)}</b>${e.to_status ? ` → ${esc((A_STATUS[e.to_status] || [e.to_status.replace('_', ' ')])[0].toLowerCase())}` : ''}${e.note ? ` — ${esc(e.note)}` : ''}<div class="who">${when(e.at)} · ${esc(e.by || '')}</div></div>`).join('') || '<div class="help">Nothing yet.</div>'}</div>
                </div>
              </div>
            </div>`;
        document.getElementById('a_savenote').addEventListener('click', () => aAct('note', { note: document.getElementById('a_note').value }));
        document.getElementById('a_savetime')?.addEventListener('click', () => aAct('time', { time: document.getElementById('a_time').value }));
        document.getElementById('a_cleartime')?.addEventListener('click', async () => {
            const go = await Swal.fire({ icon: 'warning', title: 'Clear the time', text: 'Take the time off this line? A request whose wording asks for the time is held until one is entered again.', showCancelButton: true, confirmButtonText: 'Clear the time' });
            if (!go.isConfirmed) return;
            document.getElementById('a_time').value = '';
            aAct('time', { time: '' });
        });
        document.querySelectorAll('#a_body [data-aact]').forEach(b => b.addEventListener('click', () => aForm(b.dataset.aact)));
        // schema 40: booking the restaurant
        document.getElementById('mv_issue_send')?.addEventListener('click', () => mealSendForm(null));
        document.getElementById('mv_issue')?.addEventListener('click', () => aAct('meal_voucher', {}));
        document.querySelectorAll('#a_body [data-mvsend]').forEach(b => b.addEventListener('click', () => mealSendForm(Number(b.dataset.mvsend))));
        document.querySelectorAll('#a_body [data-mvother]').forEach(b => b.addEventListener('click', () => {
            const f = document.getElementById('mv_form');
            f.innerHTML = `<input class="form-control form-control-sm mb-1" id="mv_how" placeholder="How it reached the restaurant (required) — handed over, WhatsApp to …"><button class="btn btn-primary btn-sm" id="mv_ogo">Record as sent</button>`;
            document.getElementById('mv_ogo').addEventListener('click', () => aAct('meal_voucher_sent', { voucher_id: b.dataset.mvother, note: document.getElementById('mv_how').value }));
        }));
        document.querySelectorAll('#a_body [data-mvcancel]').forEach(b => b.addEventListener('click', () => {
            const f = document.getElementById('mv_form');
            f.innerHTML = `<input class="form-control form-control-sm mb-1" id="mv_why" placeholder="Why voucher ${esc(b.dataset.mvno)} is cancelled (required — it stays on the trail)"><button class="btn btn-primary btn-sm" id="mv_cgo">Cancel the voucher</button>`;
            document.getElementById('mv_cgo').addEventListener('click', () => aAct('meal_voucher_cancel', { voucher_id: b.dataset.mvcancel, why: document.getElementById('mv_why').value }));
        }));
        if (c.archived_at) { document.querySelectorAll('#a_body .acts .btn, #a_savenote, #a_note, #a_time, #a_savetime, #a_cleartime').forEach(el => { el.disabled = true; }); }
    }
    // schema 40: the restaurant of a Meal Master meal is booked with a booking voucher (the amount in LKR from the Meal Master),
    // emailed when the tour is confirmed; here the desk sees where it went and sends, records or cancels what is left
    const MV_STATE = { sent: ['emailed', 'pill-green'], other: ['sent another way', 'pill-green'], failed: ['not sent — failed', 'pill-red'], not_sent: ['not sent yet', 'pill-amber'] };
    function mealBookingBox(c) {
        const b = c.meal_booking;
        if (!c.meal || !b) return '';
        const n2 = v => Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const vs = c.meal_vouchers || [];
        const live = vs.find(v => v.state === 'issued');
        const closed = ['cancelled', 'not_needed'].includes(c.status) || c.archived_at;
        const list = vs.map(v => { const [st, pc] = MV_STATE[v.mail_status] || [v.mail_status, 'pill-grey'];
            return `<div class="mb-2"${v.state === 'cancelled' ? ' style="opacity:.6"' : ''} data-mv="${v.id}"><b class="mono">${esc(v.voucher_no)}</b> · <b>LKR ${n2(v.amount_lkr)}</b>
            · ${v.state === 'cancelled' ? '<span class="pill pill-grey">cancelled</span>' + (v.cancel_note ? ` <span class="help">${esc(v.cancel_note)}</span>` : '') : `<span class="pill ${pc}">${esc(st)}</span>`}
            <div class="help">${v.mail_status === 'sent' ? 'to ' + esc(v.to_email) + ' · ' + when(v.sent_at) + ' · ' + esc(v.sent_by || '') : (v.mail_status === 'other' ? esc(v.note || '') + ' · ' + when(v.sent_at) : (v.mail_error ? esc(v.mail_error) : (v.to_email ? 'to ' + esc(v.to_email) : 'no booking email')))}</div>
            <div class="help">issued ${when(v.issued_at)} · ${esc(v.issued_by || '')}</div>
            <div class="d-flex gap-1 mt-1 flex-wrap"><a class="btn btn-light btn-sm" href="${BASE}/meal_voucher/${v.id}" target="_blank"><i class="bi bi-printer"></i> View / print</a>${v.state === 'issued' && !c.archived_at ? `<button class="btn btn-light btn-sm" data-mvsend="${v.id}">${v.mail_status === 'sent' ? 'Send again' : 'Send'}</button><button class="btn btn-light btn-sm" data-mvother="${v.id}">Sent another way</button><button class="btn btn-light btn-sm" data-mvcancel="${v.id}" data-mvno="${esc(v.voucher_no)}">Cancel</button>` : ''}</div></div>`; }).join('');
        const block = b.why ? `<div class="flag">${esc(b.why)}</div>` : (b.stale ? `<div class="flag">The meal on the tour has changed since ${esc(live ? live.voucher_no : 'the voucher')} was issued — cancel it with the reason, then issue a fresh one.</div>` : '');
        return `<div class="box" id="meal_book">
            <h6>Booking voucher</h6>
            <div class="mb-1">${b.amount_lkr != null ? `<b>LKR ${n2(b.amount_lkr)}</b> <span class="help">· ${esc(b.lkr_text || '')}</span>` : '<span class="help">no LKR amount yet</span>'}</div>
            <div class="help mb-1">${b.to ? 'Booking email: ' + esc(b.to) : '<span style="color:var(--tf-amber)">No booking email for the restaurant</span>'}${b.contact ? ' · ' + esc(b.contact) : ''}${b.needs_supplier ? ' · <span style="color:var(--tf-amber)">not linked to a supplier account yet</span>' : ''}</div>
            ${block}
            ${list}
            ${!live && !closed && !b.why ? `<div class="d-flex gap-1 mt-1 flex-wrap"><button class="btn btn-primary btn-sm" id="mv_issue_send">Issue and email the voucher</button><button class="btn btn-light btn-sm" id="mv_issue">Issue only</button></div>` : ''}
            <div id="mv_form" class="mt-2"></div>
        </div>`;
    }
    function mealSendForm(voucherId) {
        const c = ACT, m = c.meal_mail || {}, st = m.start || { subject: '', message: '' };
        const f = document.getElementById('mv_form');
        const opts = (m.templates || []).map((t, i) => `<option value="${i}" ${t.id === st.id ? 'selected' : ''}>${esc(t.name)}</option>`).join('');
        f.innerHTML = `<div class="p-2" style="border:1px solid var(--tf-line);border-radius:8px">
            ${m.can_send ? '' : '<div class="flag mb-1">Your account has no mailbox set up to send from — print the voucher and record it as sent another way, or ask Management to set the mailbox up.</div>'}
            <label class="form-label help mb-0">To</label><input class="form-control form-control-sm mb-1" id="mv_to" value="${esc(c.meal_booking.to || '')}" placeholder="the restaurant's booking email">
            ${m.can_save_master ? '<div class="form-check mb-1"><input class="form-check-input" type="checkbox" id="mv_savem"><label class="form-check-label help" for="mv_savem">Save this address on the Meal Master</label></div>' : ''}
            <label class="form-label help mb-0">Cc</label><input class="form-control form-control-sm mb-1" id="mv_cc" placeholder="optional">
            ${opts ? `<label class="form-label help mb-0">Ready-made email</label><select class="form-select form-select-sm mb-1" id="mv_tpl">${opts}</select>` : ''}
            <label class="form-label help mb-0">Subject</label><input class="form-control form-control-sm mb-1" id="mv_subj" value="${esc(st.subject || '')}">
            <label class="form-label help mb-0">Message <span class="text-muted">(the voucher follows it, and goes as a PDF too)</span></label><textarea class="form-control form-control-sm mb-1" id="mv_msg" rows="5">${esc(st.message || '')}</textarea>
            <button class="btn btn-primary btn-sm" id="mv_go" ${m.can_send ? '' : 'disabled'}>${voucherId ? 'Send the voucher' : 'Issue and send'}</button>
            ${m.from ? `<span class="help ms-1">from ${esc(m.from)}</span>` : ''}</div>`;
        document.getElementById('mv_tpl')?.addEventListener('change', e => { const t = (m.templates || [])[Number(e.target.value)]; if (t) { document.getElementById('mv_subj').value = t.subject; document.getElementById('mv_msg').value = t.message; } });
        document.getElementById('mv_go').addEventListener('click', () => {
            const body = { to: document.getElementById('mv_to').value, cc: document.getElementById('mv_cc').value, subject: document.getElementById('mv_subj').value, message: document.getElementById('mv_msg').value,
                           save_master: document.getElementById('mv_savem')?.checked ? 1 : 0 };
            if (voucherId) aAct('meal_voucher_send', Object.assign(body, { voucher_id: voucherId }));
            else aAct('meal_voucher', Object.assign(body, { send: 1 }));
        });
    }
    function aForm(kind) {
        const f = document.getElementById('a_form');
        const reasons = A_REASONS.map(r => `<option value="${esc(r.code)}">${esc(r.name)}</option>`).join('');
        const forms = {
            request: `<div class="help mb-1">Ask the supplier as you do today — email, phone, the counter; this records that it went.</div><input class="form-control form-control-sm mb-1" id="af_note" placeholder="Note (optional)"><button class="btn btn-primary btn-sm" id="af_go">Mark as requested</button>`,
            confirm: `<input class="form-control form-control-sm mb-1" id="af_ref" placeholder="Confirmation or ticket reference"><input class="form-control form-control-sm mb-1" id="af_with" placeholder="Confirmed with (name at the supplier, optional)"><input class="form-control form-control-sm mb-1" id="af_note" placeholder="Note (optional)"><button class="btn btn-primary btn-sm" id="af_go">Record confirmation</button>`,
            not_needed: `<div class="help mb-1">Nothing to arrange — paid at the gate, no booking taken, already in hand. Say which; it stays on the trail.</div><input class="form-control form-control-sm mb-1" id="af_note" placeholder="Why nothing needs arranging (required)"><button class="btn btn-primary btn-sm" id="af_go">Mark: nothing to arrange</button>`,
            problem: `<select class="form-select form-select-sm mb-1" id="af_code">${reasons}</select><input class="form-control form-control-sm mb-1" id="af_note" placeholder="What happened (needed for Other)"><button class="btn btn-primary btn-sm" id="af_go">Mark as a problem</button><div class="help mt-1">It shows on the Tour Board and on the inquiry so the Quotation Desk can offer the agent an alternative.</div>`,
            back: `<input class="form-control form-control-sm mb-1" id="af_note" placeholder="What changed (required)"><button class="btn btn-primary btn-sm" id="af_go">Back to "to book"</button>${(ACT.tickets || []).some(t => t.status === 'allocated') ? '<div class="help mt-1">The tickets allocated to this line go back to stock with it.</div>' : ''}`,
            // tf-tickets-r1
            tickets: `<div class="help mb-1">The lowest available numbers of the attraction, one per passenger — never a number another tour holds. The numbers go on the card, the Board, the record and the driver's itinerary copy.</div>${(ACT.tickets || []).length ? '' : `<select class="form-select form-select-sm mb-1" id="af_att">${(ACT.attractions || []).filter(a => a.control === 'stock').map(a => `<option value="${a.id}" ${ACT.attraction && ACT.attraction.id === a.id ? 'selected' : ''}>${esc(a.name)} · ${a.n_available} available</option>`).join('')}</select>`}<input class="form-control form-control-sm mb-1" id="af_count" type="number" min="1" value="${(ACT.tickets || []).length ? '' : esc(ACT.pax ?? '')}" placeholder="${(ACT.tickets || []).length ? 'How many more' : 'How many (the party)'}"><input class="form-control form-control-sm mb-1" id="af_note" placeholder="Note (optional)"><button class="btn btn-primary btn-sm" id="af_go">${(ACT.tickets || []).length ? 'Allocate more' : 'Allocate'}</button>`,
            tickets_used: `<div class="help mb-1">The tour has run and the tickets were handed in at the gate. They read "used" on Ticket Stock; the line stays confirmed.</div><button class="btn btn-primary btn-sm" id="af_go">Mark the tickets used</button>`,
            pay_at_gate: `<div class="help mb-1">The entrance is paid at the counter on the day${ACT.gate_amount ? ' — ' + esc(ACT.gate_amount) : ''}. Its own status: counted as arranged, and the driver's itinerary copy says "pay at gate".</div><input class="form-control form-control-sm mb-1" id="af_note" placeholder="Note (optional — who pays, from which float)"><button class="btn btn-primary btn-sm" id="af_go">Mark: pay at gate</button>`,
        };
        f.innerHTML = forms[kind] || '';
        document.getElementById('af_go')?.addEventListener('click', () => aAct(kind, {
            note: document.getElementById('af_note')?.value, ref: document.getElementById('af_ref')?.value,
            with: document.getElementById('af_with')?.value, code: document.getElementById('af_code')?.value,
            attraction_id: document.getElementById('af_att')?.value, count: document.getElementById('af_count')?.value,
        }));
    }
    async function aAct(kind, body) {
        const j = await post('/activity/' + ACT.id + '/' + kind, body);
        if (j.status !== 200) { toast('error', j.message); if (j.data && j.data.id) { ACT = j.data; renderAct(); } return; }
        ACT = j.data; renderAct(); toast('success', j.message); refreshTour();
    }

    // ---------- the ⋯ Manage menu: correct, archive, restore (Management; the server checks again) ----------
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
    function manageMenu(btn, c) {
        const items = [{ title: `${c.tour_no} · ${c.hotel_name || 'no hotel named'}` }];
        if (c.archived_at) {
            items.push({ label: 'Restore to the desk', icon: 'bi-arrow-counterclockwise', hint: 'Back in the queue as it was', onClick: () => act2('/hotel_card/' + c.id + '/restore', {}) });
        } else {
            items.push({ label: 'Correct this stay…', icon: 'bi-pencil-square', hint: 'Hotel, dates, meal plan, rooms — logged', onClick: () => openEdit(c) });
            items.push({ sep: true });
            const busy = ['requested', 'confirmed', 'voucher_issued'].includes(c.status);
            items.push({ label: 'Archive…', icon: 'bi-archive', hint: busy ? 'Not while the hotel holds it — back to "pending" first' : 'Off the desk, kept, restorable', disabled: busy, onClick: async () => {
                const r = await Swal.fire({ title: 'Archive this stay?', text: 'Are you sure you want to archive this record? It is kept and can be restored from Manage → Archived.', input: 'text', inputPlaceholder: 'Why (optional, stays on the history)', showCancelButton: true, confirmButtonText: 'Archive' });
                if (r.isConfirmed) act2('/hotel_card/' + c.id + '/archive', { note: r.value || '' });
            } });
        }
        if (CAN_INTAKE) items.push({ sep: true });
        if (CAN_INTAKE) items.push({ label: 'Open the inquiry', icon: 'bi-box-arrow-up-right', hint: 'The quotation and its history', onClick: () => window.open(INTAKE + '?id=' + c.intake_id, '_blank') });
        window.tdMenu(btn, items);
    }
    async function act2(path, body) {
        const j = await manage(path, body);
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message);
        if (CARD && j.data && Number(j.data.id) === Number(CARD.id)) { const keep = CARD.letter; CARD = j.data; CARD.letter = CARD.letter || keep; renderCard(); }
        refreshTour();
    }
    function openEdit(c) {
        document.getElementById('e_id').value = c.id;
        document.getElementById('e_title').textContent = `Correct ${c.tour_no} · ${c.hotel_name || 'stay'}`;
        document.getElementById('e_hotel').value = c.hotel_name || '';
        document.getElementById('e_area').value = c.area || '';
        document.getElementById('e_in').value = c.check_in || '';
        document.getElementById('e_out').value = c.check_out || '';
        document.getElementById('e_meal').value = c.meal_plan || '';
        document.getElementById('e_rooms').value = c.rooms ?? '';
        document.getElementById('e_rooms_note').value = c.rooms_note || '';
        document.getElementById('e_pax').value = c.pax ?? '';
        document.getElementById('e_special').value = c.special_note || '';
        document.getElementById('e_note').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_edit')).show();
    }
    document.getElementById('e_ok')?.addEventListener('click', async () => {
        const id = document.getElementById('e_id').value;
        const fields = { hotel_name: document.getElementById('e_hotel').value, area: document.getElementById('e_area').value, check_in: document.getElementById('e_in').value, check_out: document.getElementById('e_out').value,
            meal_plan: document.getElementById('e_meal').value, rooms: document.getElementById('e_rooms').value, rooms_note: document.getElementById('e_rooms_note').value, pax: document.getElementById('e_pax').value, special_note: document.getElementById('e_special').value };
        const j = await manage('/hotel_card/' + id + '/edit', { fields, note: document.getElementById('e_note').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        bootstrap.Modal.getInstance(document.getElementById('m_edit')).hide();
        toast('success', j.message);
        if (CARD && Number(CARD.id) === Number(id)) { const keep = CARD.letter; CARD = j.data; CARD.letter = CARD.letter || keep; renderCard(); }
        refreshTour();
    });
    function statusLine(j) {
        switch (j.status) {
            case 'requested': return `sent ${when(j.requested_at)}${j.requested_at ? ' · ' + daysSince(j.requested_at) : ''}`;
            case 'confirmed': return `ref ${esc(j.confirmation_ref || '')} · ${when(j.confirmed_at)} · voucher to issue`;
            case 'voucher_issued': return `${esc(j.voucher_no || '')} · ${when(j.voucher_at)}`;
            case 'problem': return esc(j.problem_note || reasonName(j.problem_code));
            default: return '';
        }
    }
    function daysSince(s) { const d = Math.floor((Date.now() - new Date(String(s).replace(' ', 'T')).getTime()) / 86400000); return d <= 0 ? 'today' : `waiting ${d} day${d === 1 ? '' : 's'}`; }
    function reasonName(code) { const r = REASONS.find(x => x.code === code); return r ? r.name : (code || ''); }

    // =====================================================================
    //  The stay: its card, the letters to the hotel, the hotel's answers
    // =====================================================================
    async function openCard(id) {
        const j = await api('/card/' + id);
        if (j.status !== 200) { toast('error', j.message); return; }
        CARD = j.data; renderCard();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_card')).show();
    }
    async function loadDetails(tourId, boxId) {
        const box = document.getElementById(boxId);
        if (!box) return;
        const j = await api('/tour/' + tourId + '/details');
        if (j.status !== 200) { box.innerHTML = `<div class="help">${esc(j.message || 'The tour details could not be read.')}</div>`; return; }
        DETAILS[tourId] = j.data;
        box.innerHTML = window.tdDetails(j.data, { print: BASE + '/tour/' + tourId + '/programme' });
    }
    const MSG_WORD = { request: 'Request', voucher: 'Voucher', reply: 'Hotel replied' };
    function renderCard() {
        const c = CARD;
        document.getElementById('c_title').textContent = `${c.tour_no} · ${c.hotel_name || 'Hotel not named'} · ${fmt(c.check_in)} → ${fmt(c.check_out)}`;
        const st = c.status;
        const can = {
            request: ['to_book', 'problem'].includes(st) && !Number(c.needs_hotel) && !Number(c.needs_meal),
            confirm: ['to_book', 'requested', 'problem'].includes(st),
            voucher: st === 'confirmed',
            problem: ['to_book', 'requested', 'confirmed', 'voucher_issued'].includes(st),
            back: ['problem', 'requested', 'confirmed', 'voucher_issued'].includes(st),
        };
        const rate = SEES_RATES && c.rate ? (c.rate.available === false && !c.rate.totals
            ? `<div class="help">${esc(c.rate.note || 'No published rate covers these nights.')}</div>`
            : `<div class="help">Published contract: ${esc((c.rate.plans || []).join(', '))}${c.rate.missing && c.rate.missing.length ? ` · <span class="pill pill-amber">${c.rate.missing.length} night(s) not covered</span>` : ''}</div>
               <table class="tbl mt-1"><thead><tr><th>Room type</th><th>Meal</th><th>Single</th><th>Double</th><th>Triple</th></tr></thead><tbody>
               ${(c.rate.totals || []).map(t => `<tr><td>${esc(t.room_type)}</td><td>${esc(t.meal_plan)}</td><td class="mono">${t.sgl ?? '—'}</td><td class="mono">${t.dbl ?? '—'}</td><td class="mono">${t.tpl ?? '—'}</td></tr>`).join('') || '<tr><td colspan="5" class="empty">No complete rate for the whole stay.</td></tr>'}
               </tbody></table><div class="help mt-1">${esc(c.rate.currency || '')} for the whole stay of ${c.rate.nights} night(s). Shown for reference — the quotation is not changed here.</div>`) : '';
        const letter = c.letter || {};
        const kindVoucher = st === 'voucher_issued';
        const canLetter = !Number(c.needs_hotel) && !Number(c.needs_meal) && st !== 'cancelled' && !c.archived_at;
        const msgs = (c.messages || []).map(m => `<div class="msg"><span class="k ${esc(m.kind)} ${m.state === 'failed' ? 'failed' : ''}">${esc(MSG_WORD[m.kind] || m.kind)}${m.state === 'failed' ? ' — not sent' : (m.channel === 'other' ? ' — by other means' : '')}</span> ${when(m.at)} · ${esc(m.by_name || m.by || '')}${m.to_addr ? ` · to ${esc(m.to_addr)}` : ''}${m.error ? `<div class="help" style="color:var(--tf-red)">${esc(m.error)}</div>` : ''}${m.kind === 'reply' ? `<div style="white-space:pre-line">${esc(m.body || '')}</div>` : (m.body ? ` <a href="#" data-msg="${m.id}">view</a>` : '')}</div>`).join('');

        document.getElementById('c_body').innerHTML = `
            ${c.strip ? `<div class="mb-3">${window.tdStrip(c.strip, { compact: true })}</div>` : ''}
            <div class="row g-3">
              <div class="col-lg-7">
                <div class="box">
                  <h6>The stay</h6>
                  <dl class="kv">
                    <dt>Tour</dt><dd><b>${esc(c.tour_no)}</b> · ${esc(c.tour_name || '—')} · ${esc(c.agent_name || '—')}<div class="help">${fmt(c.arrival_date)} – ${fmtY(c.departure_date)} · ${esc(c.tour_pax ?? '?')} pax${c.hotel_class ? ' · ' + esc(c.hotel_class) : ''}</div></dd>
                    <dt>Handling agent</dt><dd>${c.handler_name ? `<b>${esc(c.handler_name)}</b> <span class="help">· whose tour it is</span>` : '<span class="pill pill-amber">nobody</span>'}${c.archived_at ? `<div class="flag" style="color:var(--tf-muted)">Archived ${when(c.archived_at)} by ${esc(c.archived_by || '')}${c.archive_note ? ' — ' + esc(c.archive_note) : ''}</div>` : ''}</dd>
                    <dt>Hotel</dt><dd>${c.hotel_name ? `<b>${esc(c.hotel_name)}</b>` : '<span class="pill pill-red">not named</span>'} ${c.area ? `<span class="help">· ${esc(c.area)}</span>` : ''}${c.hotel_acc ? `<div class="help mono">${esc(c.hotel_acc)}</div>` : ''}${letter.hotel_email || letter.hotel_phone ? `<div class="help">${letter.hotel_email ? esc(letter.hotel_email) : ''}${letter.hotel_email && letter.hotel_phone ? ' · ' : ''}${letter.hotel_phone ? esc(letter.hotel_phone) : ''}</div>` : ''}${flags(c)}</dd>
                    <dt>Nights</dt><dd class="mono">${fmtY(c.check_in)} → ${fmtY(c.check_out)} · ${c.nights} night${Number(c.nights) === 1 ? '' : 's'} <span class="help">(nights ${c.day_from}${c.day_to !== c.day_from ? '–' + c.day_to : ''} of the tour)</span></dd>
                    <dt>Rooms</dt><dd>${c.rooms ? c.rooms + ' room' + (Number(c.rooms) === 1 ? '' : 's') : '—'}${c.rooms_note ? ' · ' + esc(c.rooms_note) : ''}</dd>
                    ${Object.prototype.hasOwnProperty.call(c, 'room_type_text') ? `<dt>Room type</dt><dd><span id="c_rt_word">${c.room_type_text ? `<b>${esc(c.room_type_text)}</b>` : '<span class="help">as the master names it on the rooms line</span>'}</span>${c.archived_at ? '' : ` <button class="btn btn-light btn-sm py-0" id="c_rt_edit" type="button" title="The room type as it should read on this booking — this stay only; the Hotel Master is not changed">change</button><span id="c_rt_form" style="display:none"> <input class="form-control form-control-sm d-inline-block" style="width:220px" id="c_rt_text" maxlength="120" value="${esc(c.room_type_text || '')}" placeholder="e.g. Deluxe Lake View"> <button class="btn btn-primary btn-sm py-0" id="c_rt_save" type="button">Save</button></span>`}</dd>` : ''}
                    <dt>Meal plan</dt><dd>${c.meal_plan ? esc(c.meal_plan) : '<span class="pill pill-amber">not stated</span>'}</dd>
                    <dt>Party</dt><dd class="mono">${esc(c.pax ?? '—')}</dd>
                    ${c.crew_rooms ? `<dt>Crew rooms</dt><dd>${esc(c.crew_rooms)}</dd>` : ''}
                    ${c.special_note ? `<dt>Special requests</dt><dd style="white-space:pre-line">${esc(c.special_note)}</dd>` : ''}
                  </dl>
                </div>
                <div class="box">
                  <h6>${kindVoucher ? 'Voucher to the hotel' : 'Booking request to the hotel'}</h6>
                  ${canLetter ? `
                  <div class="row g-2">
                    <div class="col-md-7"><label class="form-label help">To${letter.to_source === 'stay' ? ' <span class="text-muted">(as the last letter on this stay)</span>' : (letter.to_source === 'master' ? ' <span class="text-muted">(the hotel\'s Reservations e-mail, from the Hotel Master)</span>' : (letter.to_source === 'supplier' ? ' <span class="text-muted">(from the supplier record)</span>' : ''))}</label><input class="form-control form-control-sm" id="l_to" value="${esc(letter.to || '')}" placeholder="${letter.to ? '' : 'No Reservations e-mail on the Hotel Master — type it once; the next letter on this stay keeps it'}">
                      ${letter.to_source === 'stay' && letter.master_email && letter.master_email !== letter.to ? `<div class="help">The Hotel Master has ${esc(letter.master_email)}; this stay uses the address typed for it.</div>` : ''}
                      ${letter.can_master && !letter.master_email ? `<div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="l_save_master"><label class="form-check-label help" for="l_save_master">Also save this address on the Hotel Master as the hotel's Reservations e-mail (for every future booking)</label></div>` : ''}</div>
                    <div class="col-md-5"><label class="form-label help">Copy to</label><input class="form-control form-control-sm" id="l_cc" value="${esc(letter.cc || '')}" placeholder="optional"></div>
                    ${(letter.templates || []).length ? `<div class="col-12"><label class="form-label help">Template <span class="text-muted">(fills the subject and the opening; edit them as you like)</span></label><select class="form-select form-select-sm" id="l_template">${(() => { let g = null, out = ''; (letter.templates || []).forEach(t => { if (t.category_label !== g) { if (g !== null) out += '</optgroup>'; g = t.category_label; out += `<optgroup label="${esc(g)}">`; } out += `<option value="${t.id}" ${Number(t.id) === Number(letter.template_id) ? 'selected' : ''}>${esc(t.name)}${t.is_default ? ' (default)' : ''}</option>`; }); if (g !== null) out += '</optgroup>'; return out; })()}</select></div>` : ''}
                    <div class="col-12"><label class="form-label help">Subject</label><input class="form-control form-control-sm" id="l_subject" value="${esc(letter.subject || '')}"></div>
                    <div class="col-12"><label class="form-label help">Opening of the email <span class="text-muted">(the greeting and the covering lines above the booking details; blank = the standard lines)</span></label><textarea class="form-control form-control-sm" id="l_message" rows="4" maxlength="4000">${esc(letter.message || '')}</textarea></div>
                    <div class="col-12"><label class="form-label help">Remarks for this letter <span class="text-muted">(the agent's requests above go on it already)</span></label><input class="form-control form-control-sm" id="l_remarks" maxlength="500" placeholder="e.g. early check-in requested, connecting rooms if possible"></div>
                    ${SEES_RATES && !kindVoucher && c.rate && (c.rate.totals || []).length ? `<div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="l_rate" checked><label class="form-check-label help" for="l_rate">Quote the published contract rate on the request</label></div></div>` : ''}
                  </div>
                  <div class="acts mt-2">
                    <button class="btn btn-light btn-sm" id="l_preview"><i class="bi bi-eye"></i> Preview</button>
                    <button class="btn btn-primary btn-sm" id="l_send" ${letter.can_send ? '' : 'title="Your account has no mailbox set up to send from"'}><i class="bi bi-send"></i> Send by email</button>
                    <a class="btn btn-light btn-sm" id="l_print" href="#" target="_blank"><i class="bi bi-printer"></i> Print / PDF</a>
                    <button class="btn btn-light btn-sm" id="l_sent" title="The request went another way — WhatsApp, the hotel's portal, the phone"><i class="bi bi-check2"></i> Sent another way…</button>
                  </div>
                  <div class="help mt-2">${letter.can_send ? `Goes from your own mailbox (${esc(letter.sender_email || '')}), as the Hotel Reservation screen sends; the copy sent is kept here.` : 'Your account has no mailbox set up to send from (email, password and host on your profile) — print the letter, or record that it went another way.'}</div>`
                  : `<div class="help">${c.archived_at ? 'This card is archived.' : (st === 'cancelled' ? 'This tour is cancelled.' : 'The letter cannot go until the Quotation Desk fills in what is flagged above (the hotel, the meal plan).')}</div>`}
                </div>
                <div class="box">
                  <h6>Letters and replies</h6>
                  <div>${msgs || '<div class="help">Nothing sent or received yet.</div>'}</div>
                  ${!c.archived_at && st !== 'cancelled' ? `<div class="mt-2"><textarea class="form-control form-control-sm" id="l_reply" rows="2" placeholder="What the hotel said — waitlisted, asked for a deposit, offered another room type… (kept with who and when)"></textarea><div class="mt-1"><button class="btn btn-light btn-sm" id="l_reply_go">Record the hotel's reply</button></div></div>` : ''}
                </div>
                ${rate ? `<div class="box"><h6>Contract rate</h6>${rate}</div>` : ''}
                <div class="box">
                  <h6>Desk note</h6>
                  <textarea class="form-control form-control-sm" id="c_note" rows="2" placeholder="Anything the next person should know">${esc(c.note || '')}</textarea>
                  <div class="mt-2"><button class="btn btn-light btn-sm" id="c_savenote">Save note</button></div>
                </div>
              </div>
              <div class="col-lg-5">
                <div class="box">
                  <h6>Where it stands</h6>
                  <div class="mb-2">${pill(c)}</div>
                  <dl class="kv" style="grid-template-columns:96px 1fr">
                    ${c.requested_at ? `<dt>Sent</dt><dd>${when(c.requested_at)} · ${esc(c.requested_by || '')}${c.request_note ? `<div class="help">${esc(c.request_note)}</div>` : ''}</dd>` : ''}
                    ${c.confirmation_ref ? `<dt>Confirmed</dt><dd>ref <b>${esc(c.confirmation_ref)}</b>${c.confirmed_with ? ' with ' + esc(c.confirmed_with) : ''}<div class="help">${when(c.confirmed_at)} · ${esc(c.confirmed_by || '')}</div></dd>` : ''}
                    ${c.voucher_no ? `<dt>Voucher</dt><dd><b>${esc(c.voucher_no)}</b><div class="help">${when(c.voucher_at)} · ${esc(c.voucher_by || '')}</div></dd>` : ''}
                    ${st === 'problem' ? `<dt>Problem</dt><dd><b>${esc(reasonName(c.problem_code))}</b>${c.problem_note ? `<div class="help">${esc(c.problem_note)}</div>` : ''}<div class="help">${when(c.problem_at)} · ${esc(c.problem_by || '')}</div></dd>` : ''}
                  </dl>
                  <div class="acts mt-2">
                    ${can.request ? `<button class="btn btn-light btn-sm" data-act="request" title="The request went another way; this records that">Mark as sent</button>` : ''}
                    ${can.confirm ? `<button class="btn btn-primary btn-sm" data-act="confirm">Record confirmation</button>` : ''}
                    ${can.voucher ? `<button class="btn btn-success btn-sm" data-act="voucher">Issue voucher</button>` : ''}
                    ${can.problem ? `<button class="btn btn-light btn-sm" style="color:var(--tf-red)" data-act="problem">Rejected / problem</button>` : ''}
                    ${can.back ? `<button class="btn btn-light btn-sm" data-act="back">Back to pending</button>` : ''}
                  </div>
                  <div id="c_form" class="mt-2"></div>
                  ${(Number(c.needs_hotel) || Number(c.needs_meal)) && ['to_book', 'problem'].includes(st) ? `<div class="help mt-2">This stay cannot be requested until the Quotation Desk fills in what is flagged above.</div>` : ''}
                </div>
                <div class="box">
                  <h6>Trail</h6>
                  <div class="trail">${(c.events || []).map(e => `<div><b>${esc(e.event)}</b>${e.to_status ? ` → ${esc((STATUS[e.to_status] || [e.to_status.replace('_', ' ')])[0].toLowerCase())}` : ''}${e.note ? ` — ${esc(e.note)}` : ''}<div class="who">${when(e.at)} · ${esc(e.by || '')}</div></div>`).join('') || '<div class="help">Nothing yet.</div>'}</div>
                  ${(c.activity || []).length ? `<h6 class="mt-3">Corrections</h6>${window.tdHistory((c.activity || []).map(a => Object.assign({ lane: 'hotel' }, a)), {})}` : ''}
                </div>
              </div>
            </div>`;

        document.getElementById('c_savenote').addEventListener('click', () => act('note', { note: document.getElementById('c_note').value }));
        // tf-walkfix-r1: the room type as this booking describes it — changed here for this stay only, never on the master
        document.getElementById('c_rt_edit')?.addEventListener('click', () => { const f = document.getElementById('c_rt_form'); f.style.display = f.style.display === 'none' ? '' : 'none'; if (f.style.display !== 'none') document.getElementById('c_rt_text').focus(); });
        document.getElementById('c_rt_save')?.addEventListener('click', () => act('room_type', { text: document.getElementById('c_rt_text').value }));
        document.querySelectorAll('#c_body [data-act]').forEach(b => b.addEventListener('click', () => form(b.dataset.act)));
        document.querySelectorAll('#c_body [data-msg]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); showMessage(Number(a.dataset.msg)); }));
        document.getElementById('l_reply_go')?.addEventListener('click', () => act('reply', { text: document.getElementById('l_reply').value }));
        if (canLetter) {
            const kind = kindVoucher ? 'voucher' : 'request';
            const qs = () => new URLSearchParams({ rate: document.getElementById('l_rate') && document.getElementById('l_rate').checked ? '1' : '0', remarks: document.getElementById('l_remarks').value || '', message: document.getElementById('l_message').value || '' }).toString();
            document.getElementById('l_message').addEventListener('input', () => { document.getElementById('l_print').href = printUrl(); });
            document.getElementById('l_template')?.addEventListener('change', e => {
                const t = (letter.templates || []).find(x => Number(x.id) === Number(e.target.value));
                if (!t) return;
                document.getElementById('l_subject').value = t.subject; document.getElementById('l_message').value = t.message;
                document.getElementById('l_print').href = printUrl();
                toast('success', 'Template "' + t.name + '" filled in the subject and the opening; edit them as you like.');
            });
            const printUrl = () => `${BASE}/card/${c.id}/document/${kind}?${qs()}`;
            document.getElementById('l_print').href = printUrl();
            document.getElementById('l_remarks').addEventListener('input', () => { document.getElementById('l_print').href = printUrl(); });
            document.getElementById('l_rate')?.addEventListener('change', () => { document.getElementById('l_print').href = printUrl(); });
            document.getElementById('l_preview').addEventListener('click', async () => {
                const r = await fetch(`${BASE}/card/${c.id}/document/${kind}?format=email&${qs()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await r.text();
                document.getElementById('g_title').textContent = document.getElementById('l_subject').value || 'Preview';
                document.getElementById('g_body').innerHTML = `<div class="help mb-2">To ${esc(document.getElementById('l_to').value || '—')}${document.getElementById('l_cc').value ? ' · copy ' + esc(document.getElementById('l_cc').value) : ''}</div>${html}`;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('m_msg')).show();
            });
            document.getElementById('l_send').addEventListener('click', async () => {
                const to = document.getElementById('l_to').value.trim();
                if (!to) { toast('error', 'Enter the hotel\'s email address.'); return; }
                const r = await Swal.fire({ title: (kindVoucher ? 'Send the voucher' : 'Send the booking request') + ' to ' + to + '?', text: 'It goes from your own mailbox; the copy sent is kept on the stay.', icon: 'question', showCancelButton: true, confirmButtonText: 'Send' });
                if (!r.isConfirmed) return;
                await act('send', { kind, to, cc: document.getElementById('l_cc').value, subject: document.getElementById('l_subject').value, remarks: document.getElementById('l_remarks').value, message: document.getElementById('l_message').value, rate: document.getElementById('l_rate') && document.getElementById('l_rate').checked ? 1 : 0, save_master: document.getElementById('l_save_master') && document.getElementById('l_save_master').checked ? 1 : 0 });
            });
            document.getElementById('l_sent').addEventListener('click', async () => {
                const r = await Swal.fire({ title: (kindVoucher ? 'Voucher' : 'Request') + ' sent another way?', input: 'text', inputPlaceholder: 'How — WhatsApp, the hotel\'s portal, by phone (kept on the trail)', showCancelButton: true, confirmButtonText: 'Record it' });
                if (!r.isConfirmed) return;
                await act('sent', { kind, note: r.value || '' });
            });
        }
        if (c.archived_at) { document.querySelectorAll('#c_body .acts .btn, #c_savenote, #c_note').forEach(el => { el.disabled = true; }); }
    }
    function showMessage(id) {
        const m = (CARD.messages || []).find(x => Number(x.id) === id);
        if (!m) return;
        document.getElementById('g_title').textContent = `${MSG_WORD[m.kind] || m.kind} · ${when(m.at)} · ${m.by_name || m.by || ''}`;
        document.getElementById('g_body').innerHTML = `<div class="help mb-2">To ${esc(m.to_addr || '—')}${m.cc_addr ? ' · copy ' + esc(m.cc_addr) : ''} · ${esc(m.subject || '')}${m.state === 'failed' ? ` · <span class="pill pill-red">not sent — ${esc(m.error || '')}</span>` : ''}</div>${m.body || ''}`;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_msg')).show();
    }

    function form(kind) {
        const f = document.getElementById('c_form');
        const reasons = REASONS.map(r => `<option value="${esc(r.code)}">${esc(r.name)}</option>`).join('');
        const forms = {
            request: `<div class="help mb-1">The request went to the hotel another way; this records that it went. To send it from here, use the booking request box.</div><input class="form-control form-control-sm mb-1" id="f_note" placeholder="How it went (optional)"><button class="btn btn-primary btn-sm" id="f_go">Mark as sent</button>`,
            confirm: `<input class="form-control form-control-sm mb-1" id="f_ref" placeholder="Hotel's confirmation reference"><input class="form-control form-control-sm mb-1" id="f_with" placeholder="Confirmed with (name at the hotel, optional)"><input class="form-control form-control-sm mb-1" id="f_note" placeholder="Note (optional)"><button class="btn btn-primary btn-sm" id="f_go">Record confirmation</button>`,
            voucher: `<div class="help mb-1">A voucher number is taken from the counter and put on the card; the voucher can then be printed or sent from the box on the left.</div><input class="form-control form-control-sm mb-1" id="f_note" placeholder="Note (optional)"><button class="btn btn-success btn-sm" id="f_go">Issue voucher</button>`,
            problem: `<select class="form-select form-select-sm mb-1" id="f_code">${reasons}</select><input class="form-control form-control-sm mb-1" id="f_note" placeholder="What the hotel said (needed for Other)"><button class="btn btn-primary btn-sm" id="f_go">Record it</button><div class="help mt-1">Rejected / unavailable, alternative required, or any other problem: it shows on the Tour Board and on the inquiry so the Quotation Desk can offer the agent an alternative. The quotation itself is not changed here.</div>`,
            back: `<input class="form-control form-control-sm mb-1" id="f_note" placeholder="What changed (required)"><button class="btn btn-primary btn-sm" id="f_go">Back to pending</button>`,
        };
        f.innerHTML = forms[kind] || '';
        document.getElementById('f_go')?.addEventListener('click', () => act(kind, {
            note: document.getElementById('f_note')?.value, ref: document.getElementById('f_ref')?.value,
            with: document.getElementById('f_with')?.value, code: document.getElementById('f_code')?.value,
        }));
    }

    async function act(kind, body) {
        const j = await post('/card/' + CARD.id + '/' + kind, body);
        if (j.status !== 200) { toast('error', j.message); return; }
        CARD = j.data; renderCard(); toast('success', j.message); refreshTour();
    }

    document.getElementById('f_q').addEventListener('input', () => { clearTimeout(window.__hq); window.__hq = setTimeout(loadTours, 250); });
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
})();
</script>
</body>
</html>
