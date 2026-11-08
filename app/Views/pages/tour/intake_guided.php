<?= view('layouts/head') ?>
<style>
    /* ---------- Guided Inquiry (tf-guided-r2) — the approved palette ---------- */
    .gi { --gi-plum:#842484; --gi-plum-d:#6b1d6b; --gi-plum-soft:#f7edf7; --gi-green:#78b848; --gi-green-d:#67a23c;
          --gi-ink:#241f28; --gi-muted:#6b6472; --gi-line:#e4dfe8; --gi-bg:#faf8fb; --gi-card:#fff;
          --gi-amber-soft:#fdf3e1; --gi-amber:#9a6b12; --gi-red:#b23a32; --gi-red-soft:#fbe9e7;
          color:var(--gi-ink); font-size:14px; }
    .gi h4, .gi h5, .gi h6 { color:var(--gi-plum); margin:0; font-weight:700; }
    .gi .gi-head { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:14px; }
    .gi .gi-head h4 { font-size:26px; letter-spacing:-.01em; }
    .gi .card2 { background:var(--gi-card); border:1px solid var(--gi-line); border-radius:14px; box-shadow:0 1px 3px rgba(36,31,40,.06); overflow:hidden; margin-bottom:16px; }
    .gi .card2 > .hd { background:var(--gi-plum); color:#fff; padding:13px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .gi .card2 > .hd h5 { color:#fff; font-size:15.5px; }
    .gi .card2 > .bd { padding:18px; }
    .gi .btn-go { background:var(--gi-green); border:0; color:#fff; font-weight:700; border-radius:9px; padding:10px 20px; font-size:14px; }
    .gi .btn-go:hover:not(:disabled) { background:var(--gi-green-d); }
    .gi .btn-confirm { background:#8a1c30; }
    .gi .asking { background:#8a1c30 !important; border-color:#8a1c30 !important; color:#fff !important; }
    .gi .ask-note { font-size:12px; font-weight:600; color:#8a1c30; margin-left:8px; }
    .gi .btn-confirm:hover:not(:disabled) { background:#6f1626; }
    .gi .btn-go:disabled, .gi .btn-plum:disabled, .gi .btn-ghost:disabled, .gi .btn-quiet:disabled { opacity:.5; cursor:not-allowed; }
    .gi .btn-plum { background:var(--gi-plum); border:0; color:#fff; font-weight:700; border-radius:9px; padding:10px 20px; font-size:14px; }
    .gi .btn-plum:hover:not(:disabled) { background:var(--gi-plum-d); }
    .gi .btn-ghost { background:#fff; border:1.5px solid var(--gi-plum); color:var(--gi-plum); font-weight:700; border-radius:9px; padding:9px 18px; font-size:13.5px; }
    .gi .btn-ghost:hover:not(:disabled) { background:var(--gi-plum-soft); }
    .gi .btn-quiet { background:#fff; border:1px solid var(--gi-line); color:var(--gi-ink); font-weight:600; border-radius:9px; padding:8px 15px; font-size:13px; }
    .gi .btn-quiet:hover:not(:disabled) { background:var(--gi-bg); }
    .gi .btn-red { background:#fff; border:1px solid var(--gi-red); color:var(--gi-red); font-weight:600; border-radius:9px; padding:7px 13px; font-size:12.5px; }
    .gi .btn-red:hover:not(:disabled) { background:var(--gi-red-soft); }
    .gi .btn-xs { padding:5px 11px; font-size:12px; border-radius:8px; }
    /* the stage rail */
    .gi .rail { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:18px; }
    .gi .st { border:1px solid var(--gi-line); border-radius:12px; background:#fff; padding:12px 14px; text-align:left; cursor:pointer; }
    .gi .st:hover { background:var(--gi-plum-soft); }
    .gi .st .n { display:inline-flex; width:24px; height:24px; border-radius:50%; background:var(--gi-plum-soft); color:var(--gi-plum);
                 align-items:center; justify-content:center; font-weight:700; font-size:12.5px; margin-right:8px; }
    .gi .st .t { font-weight:700; font-size:13.5px; }
    .gi .st .s { font-size:11.5px; color:var(--gi-muted); margin-top:3px; }
    .gi .st.on { border-color:var(--gi-plum); box-shadow:0 0 0 3px rgba(132,36,132,.12); }
    .gi .st.on .n { background:var(--gi-plum); color:#fff; }
    .gi .st.done .n { background:var(--gi-green); color:#fff; }
    @media (max-width:860px) { .gi .rail { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:520px) { .gi .rail { grid-template-columns:1fr; } }
    .gi label.lb { font-size:12.5px; font-weight:600; color:var(--gi-muted); display:block; margin-bottom:5px; }
    .gi .form-control, .gi .form-select { border:1px solid var(--gi-line); border-radius:9px; padding:9px 12px; font-size:14px; }
    .gi .form-control:focus, .gi .form-select:focus { border-color:var(--gi-plum); box-shadow:0 0 0 3px rgba(132,36,132,.14); outline:0; }
    .gi .form-control.sm, .gi .form-select.sm { padding:6px 9px; font-size:13px; border-radius:7px; }
    .gi .form-control[readonly] { background:var(--gi-bg); }
    .gi .muted { color:var(--gi-muted); font-size:12.5px; }
    .gi .small { font-size:12px; }
    .gi .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .gi .pill { display:inline-block; font-size:11px; font-weight:700; border-radius:999px; padding:3px 11px; }
    .gi .pill-plum { background:var(--gi-plum-soft); color:var(--gi-plum); }
    .gi .pill-green { background:#e4f4ea; color:#1f8a4c; }
    .gi .pill-amber { background:var(--gi-amber-soft); color:var(--gi-amber); }
    .gi .pill-red { background:var(--gi-red-soft); color:var(--gi-red); }
    .gi .note { background:var(--gi-amber-soft); color:var(--gi-amber); border-radius:10px; padding:11px 15px; font-size:13px; font-weight:600; margin-bottom:16px; }
    .gi .err { background:var(--gi-red-soft); color:var(--gi-red); border-radius:10px; padding:11px 15px; font-size:13.5px; margin-bottom:14px; }
    .gi .sec { border:1px solid var(--gi-line); border-radius:12px; margin-bottom:10px; overflow:hidden; }
    .gi .sec > summary { cursor:pointer; list-style:none; padding:13px 16px; display:flex; justify-content:space-between; align-items:center; font-weight:700; color:var(--gi-plum); background:var(--gi-bg); gap:12px; }
    .gi .sec > summary::-webkit-details-marker { display:none; }
    .gi .sec > div { padding:14px 16px; }
    .gi .tbl { width:100%; border-collapse:collapse; font-size:13px; }
    .gi .tbl th { text-align:left; font-size:11px; letter-spacing:.04em; text-transform:uppercase; color:var(--gi-muted); padding:8px 10px; border-bottom:1px solid var(--gi-line); white-space:nowrap; }
    .gi .tbl td { padding:8px 10px; border-bottom:1px solid var(--gi-line); vertical-align:top; }
    .gi .tbl tr:last-child td { border-bottom:0; }
    .gi .wrap { overflow-x:auto; }
    .gi .acts { display:flex; gap:6px; flex-wrap:wrap; }
    /* a save that worked, said once and quietly */
    .gi .toast2 { position:fixed; left:50%; transform:translateX(-50%); bottom:26px; background:var(--gi-green); color:#fff;
                  font-weight:700; font-size:13.5px; padding:11px 22px; border-radius:999px; box-shadow:0 6px 20px rgba(36,31,40,.22);
                  z-index:1080; max-width:92vw; }
    .gi .busy { opacity:.55; pointer-events:none; }

    /* tf-guided-r1: the floating "back to top" button belongs to the theme and is shared by every screen,
       so it is only steered here, on this one — never hidden for any other. Below 1200px it is hidden, so
       it can never sit over a table cell or an action button; from 1200px there is clear margin beside the
       panel and room reserved underneath. */
    @media (max-width:1199.98px) { #back-to-top { display:none !important; } }
    @media (min-width:1200px)   { .gi { padding-bottom:96px; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content gi" id="gi">
            <div class="container-fluid" style="max-width:1180px">

                <?= view('layouts/tourdesk_bar') ?>

                <div class="gi-head">
                    <div>
                        <h4>Tour Inquiry <span class="pill pill-plum align-middle">Guided</span></h4>
                        <div class="muted">One inquiry, four stages. Every figure, every e-mail and every record is the same one the classic panel uses.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn-ghost" id="b_classic" type="button">Classic panel</button>
                        <button class="btn-go" id="b_new" type="button">New inquiry</button>
                    </div>
                </div>

                <div id="err" class="err" style="display:none"></div>

                <div class="card2" id="c_pick">
                    <div class="hd"><h5>Open an inquiry</h5></div>
                    <div class="bd">
                        <div class="row g-2 align-items-end">
                            <div class="col-sm-6 col-md-5">
                                <label class="lb" for="q">Inquiry number or customer</label>
                                <input type="text" id="q" class="form-control" placeholder="for example INQ 1040" autocomplete="off">
                            </div>
                            <div class="col-sm-6 col-md-7 d-flex gap-2 flex-wrap">
                                <button class="btn-plum" id="q_go" type="button">Open inquiry</button>
                                <button class="btn-ghost" id="b_start_mail" type="button">Start from an e-mail</button>
                                <button class="btn-ghost" id="b_start_hand" type="button">Start by hand</button>
                            </div>
                        </div>
                        <div id="q_rows" class="mt-3"></div>
                        <div id="q_new" class="mt-3"></div>
                    </div>
                </div>

                <div id="work" style="display:none">
                    <div class="rail" id="rail"></div>
                    <div id="stage"></div>
                </div>

            </div>
        </div>
    </div>
</div>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    // =====================================================================
    //  Guided Inquiry (tf-guided-r2, 2 Nov 2026)
    //  Every control on this panel calls the inquiry's own existing endpoint with the
    //  same payload the classic panel sends. No rule, rate, validation, permission,
    //  template or automation is reimplemented here: this is the same engine, guided.
    //  The classic panel stays whole at tour/intake?classic=1 and nothing routes
    //  through this file that did not already route through TourIntakeController.
    // =====================================================================
    const BASE    = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const CLASSIC = '<?= base_url($classic_url ?? 'tour/intake?classic=1') ?>';
    const OPEN_ID = <?= (int) ($open_id ?? 0) ?>;
    const SEES_PRICING = <?= !empty($sees_pricing) ? 'true' : 'false' ?>;
    const CAN_ISSUE = <?= !empty($can_issue) ? 'true' : 'false' ?>;   // tf-panel2-r1

    const $ = id => document.getElementById(id);
    const esc = s => String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const api = async (path, opts) => {
        const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {}));
        const t = await r.text();
        try { return JSON.parse(t); } catch (e) { return { status: r.status, message: 'The server did not answer in the expected way.' }; }
    };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });

    const err = t => { const b = $('err'); if (!t) { b.style.display = 'none'; b.textContent = ''; return; }
                       b.textContent = t; b.style.display = ''; window.scrollTo({ top: 0, behavior: 'smooth' }); };
    let toastTimer = null;
    function ok(t) {
        if (!t) { return; }
        let el = document.querySelector('.gi .toast2');
        if (!el) { el = document.createElement('div'); el.className = 'toast2'; $('gi').appendChild(el); }
        el.textContent = t;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { if (el && el.parentNode) { el.parentNode.removeChild(el); } }, 4200);
    }
    const num   = v => v === null || v === undefined || v === '' ? '—' : Number(v).toLocaleString('en-US');
    const money = v => v === null || v === undefined || v === '' ? '—' : '$' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const lkr   = v => v === null || v === undefined || v === '' ? '—' : 'LKR ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const d10   = v => String(v === null || v === undefined ? '' : v).slice(0, 10);

    let IT = null, COST = null, STAGE = 1;
    let M = null, CREWM = null, EXCM = null, SVCM = null, REVIEW = null;   // the masters, read once each

    // tf-guided-r1.1: tf_intake carries no customer column — the customer is the agent account, and the
    // lead name comes from the first passenger. Reading a field that does not exist is what showed "—".
    const lead   = () => { const p = (IT.passengers || [])[0]; return p ? (p.full_name || p.name || '') : ''; };
    const source = () => (IT.source_from || IT.source_subject || IT.source_text) ? 'e-mail' : 'typed in';

    const STAGES = [
        { n: 1, t: 'Customer & Booking',    s: 'who is travelling, the agent, requests and questions' },
        { n: 2, t: 'Travel Details',        s: 'arrival and departure, nights, party and rooms' },
        { n: 3, t: 'Itinerary & Services',  s: 'the days, hotels, transport, excursions and meals' },
        { n: 4, t: 'Review & Quotation',    s: 'the costing, child fares and the quotation' },
    ];

    // ---------------------------------------------------------------- the one place a save happens
    // Every editing control ends here. The endpoint is the inquiry's own; whatever it answers is
    // what the person is told, word for word, and what the panel then shows.
    async function save(path, body, opts) {
        opts = opts || {};
        err('');
        let j;
        try { j = await post(path, body); }
        catch (e) { err('That did not reach the server. Nothing was saved — try again.'); return null; }
        if (!j || (j.status && j.status !== 200)) {
            err((j && j.message) || 'That was refused. Nothing was saved.');
            return null;
        }
        if (j.data && j.data.id) { IT = j.data; }
        else if (j.data && j.data.inquiry) { IT = j.data.inquiry; if (j.data.review) { REVIEW = j.data.review; } }
        else if (IT) {
            // an endpoint that answers with something other than the inquiry (the costing, for one):
            // the record is read again so the panel never shows a figure older than the save
            try { const f = await api('/intake/' + IT.id); if (f && f.status === 200) { IT = f.data; } } catch (e) {}
        }
        if (j.message) { ok(j.message); }
        if (opts.costing !== false) { await loadCosting(); }
        if (opts.render !== false) { render(); }
        return j;
    }

    async function loadCosting() {
        if (!IT) { return; }
        try { const c = await api('/intake/' + IT.id + '/costing'); COST = (c && c.status === 200) ? c.data : null; }
        catch (e) { COST = null; }
    }

    // the masters, each read once per visit and kept
    async function masters()  { if (M === null)     { const j = await api('/masters');            M     = (j && j.data) || {}; } return M; }
    async function crewM()    { if (CREWM === null) { const j = await api('/crew');               CREWM = (j && j.data) || []; } return CREWM; }
    async function excM()     { if (EXCM === null)  { const j = await api('/excursions');         EXCM  = (j && j.data) || []; } return EXCM; }
    async function svcM()     { if (SVCM === null)  { const j = await api('/services');           SVCM  = (j && j.data) || {}; } return SVCM; }

    // ---------------------------------------------------------------- find and open
    async function search() {
        err('');
        const q = $('q').value.trim();
        let j;
        try { j = await api('/list?q=' + encodeURIComponent(q) + '&status=open'); }
        catch (e) { err('The inquiry list did not answer. Try again.'); return; }
        if (j.status !== 200) { err(j.message || 'The inquiry list was refused.'); return; }
        const rows = (j.data && j.data.items) || [];
        if (!rows.length) { $('q_rows').innerHTML = '<div class="muted">No matching inquiry.</div>'; return; }
        $('q_rows').innerHTML = '<div class="wrap"><table class="tbl"><tbody>' + rows.slice(0, 12).map(r => '<tr class="hit" style="cursor:pointer" data-id="' + r.id + '">'
            + '<td><b class="mono">' + esc(r.inquiry_no || ('#' + r.id)) + '</b> <span class="pill pill-plum">' + esc(r.status || '') + '</span></td>'
            + '<td>' + esc(r.agent_name || '—') + '<div class="muted">' + esc(r.tour_name || r.handler_name || '') + '</div></td>'
            + '<td class="muted">' + esc(d10(r.arrival_date)) + (r.nights ? ' · ' + r.nights + ' n' : '') + '</td></tr>').join('') + '</tbody></table></div>';
        document.querySelectorAll('#q_rows [data-id]').forEach(tr => tr.addEventListener('click', () => open_(tr.dataset.id)));
    }

    async function open_(id) {
        err('');
        let j;
        try { j = await api('/intake/' + id); }
        catch (e) { err('That inquiry did not load. Try again.'); return false; }
        if (!j || j.status !== 200) { err((j && j.message) || 'That inquiry could not be opened.'); return false; }
        IT = j.data; COST = null; REVIEW = null;
        await loadCosting();
        $('c_pick').style.display = 'none';
        $('work').style.display = '';
        STAGE = 1; render();
        return true;
    }

    async function reopen() {
        if (!IT) { return; }
        err('');
        const j = await api('/intake/' + IT.id);
        if (j && j.status === 200) { IT = j.data; await loadCosting(); render(); ok('Reopened from the record.'); }
    }

    // ---------------------------------------------------------------- starting a new inquiry
    function startEmail() {
        err('');
        $('q_new').innerHTML = '<div class="card2"><div class="hd"><h5>Start from the agent’s e-mail</h5></div><div class="bd">'
            + '<div class="muted mb-2">Paste the e-mail exactly as it arrived. It is kept with the inquiry word for word; reading it is the same reader the classic panel uses.</div>'
            + '<div class="row g-2 mb-2"><div class="col-md-6"><label class="lb">From</label><input class="form-control" id="n_from" placeholder="agent@example.com"></div>'
            + '<div class="col-md-6"><label class="lb">Subject</label><input class="form-control" id="n_subj"></div></div>'
            + '<label class="lb">The e-mail</label><textarea class="form-control" id="n_text" rows="9"></textarea>'
            + '<div class="acts mt-3"><button class="btn-go" id="n_read" type="button">Save and read it</button>'
            + '<button class="btn-ghost" id="n_keep" type="button">Save without reading</button>'
            + '<button class="btn-quiet" id="n_cancel" type="button">Cancel</button></div></div></div>';
        $('n_cancel').addEventListener('click', () => { $('q_new').innerHTML = ''; });
        const go = async (read) => {
            const text = $('n_text').value.trim();
            if (!text) { err('Paste the e-mail first.'); return; }
            const b = { source_text: text, source_from: $('n_from').value.trim(), source_subject: $('n_subj').value.trim(), read: read ? 1 : 0 };
            const j = await post('/intake', b);
            if (!j || j.status !== 200) { err((j && j.message) || 'That was refused. Nothing was saved.'); return; }
            ok(j.message || 'Saved.');
            const id = (j.saved && j.saved.id) || (j.data && j.data.id);
            if (id) { $('q_new').innerHTML = ''; open_(id); }
        };
        $('n_read').addEventListener('click', () => go(true));
        $('n_keep').addEventListener('click', () => go(false));
    }

    function startHand() {
        err('');
        $('q_new').innerHTML = '<div class="card2"><div class="hd"><h5>Start by hand</h5></div><div class="bd">'
            + '<div class="muted mb-2">The inquiry is created with what is typed here, so a number is used only when one is really saved.</div>'
            + '<div class="row g-2">'
            + '<div class="col-md-4"><label class="lb">Customer / agent</label><input class="form-control" id="h_agent"></div>'
            + '<div class="col-md-4"><label class="lb">Tour name</label><input class="form-control" id="h_tour"></div>'
            + '<div class="col-md-4"><label class="lb">Agent e-mail</label><input class="form-control" id="h_email"></div>'
            + '<div class="col-md-3"><label class="lb">Arrival</label><input type="date" class="form-control" id="h_arr"></div>'
            + '<div class="col-md-3"><label class="lb">Departure</label><input type="date" class="form-control" id="h_dep"></div>'
            + '<div class="col-md-2"><label class="lb">Adults</label><input type="number" min="0" class="form-control" id="h_ad"></div>'
            + '<div class="col-md-2"><label class="lb">Children</label><input type="number" min="0" class="form-control" id="h_ch"></div>'
            + '<div class="col-md-2"><label class="lb">Meal basis</label><input class="form-control" id="h_meal" placeholder="HB"></div>'
            + '</div>'
            + '<div class="acts mt-3"><button class="btn-go" id="h_save" type="button">Create the inquiry</button>'
            + '<button class="btn-quiet" id="h_cancel" type="button">Cancel</button></div>'
            + '<div class="muted mt-2">A blank night is made for each night the dates give, for the itinerary to be filled in.</div></div></div>';
        $('h_cancel').addEventListener('click', () => { $('q_new').innerHTML = ''; });
        $('h_save').addEventListener('click', async () => {
            const f = {
                agent_name: $('h_agent').value.trim(), tour_name: $('h_tour').value.trim(), agent_email: $('h_email').value.trim(),
                arrival_date: $('h_arr').value, departure_date: $('h_dep').value,
                pax_adults: $('h_ad').value, pax_children: $('h_ch').value, meal_basis: $('h_meal').value.trim(),
            };
            if (!f.agent_name) { err('Type the customer or agent first.'); return; }
            let nights = 0;
            if (f.arrival_date && f.departure_date) {
                nights = Math.max(0, Math.round((new Date(f.departure_date) - new Date(f.arrival_date)) / 86400000));
            }
            const j = await post('/intake', { fields: f, make_nights: nights });
            if (!j || j.status !== 200) { err((j && j.message) || 'That was refused. Nothing was saved.'); return; }
            ok(j.message || 'Created.');
            if (j.data && j.data.id) { $('q_new').innerHTML = ''; open_(j.data.id); }
        });
    }

    // ---------------------------------------------------------------- the rail
    function done(n) {
        if (!IT) { return false; }
        if (n === 1) { return !!(IT.agent_name || (IT.passengers || []).length); }
        if (n === 2) { return !!(IT.arrival_date && (IT.days || []).length); }
        if (n === 3) { return (IT.days || []).some(d => d.hotel_name || d.hotel_name_raw || d.hotel_id); }
        if (n === 4) { return !!(IT.status && String(IT.status).toLowerCase() === 'quoted'); }
        return false;
    }
    function rail() {
        $('rail').innerHTML = STAGES.map(s => '<button class="st ' + (STAGE === s.n ? 'on' : '') + ' ' + (done(s.n) ? 'done' : '') + '" data-st="' + s.n + '" type="button">'
            + '<div><span class="n">' + (done(s.n) && STAGE !== s.n ? '&#10003;' : s.n) + '</span><span class="t">' + s.t + '</span></div>'
            + '<div class="s">' + s.s + '</div></button>').join('');
        document.querySelectorAll('[data-st]').forEach(b => b.addEventListener('click', () => { STAGE = Number(b.dataset.st); render(); }));
    }

    const locked = () => !!(IT && (IT.locked || IT.archived));
    const lockNote = () => locked()
        ? '<div class="note">This inquiry is read-only: ' + (IT.archived ? 'it is archived.' : 'it has been confirmed as a tour.') + ' Figures and records are shown as they stand.</div>'
        : '';

    const header = () => '<div class="hd"><h5>' + esc(IT.inquiry_no || ('Inquiry #' + IT.id)) + ' — ' + STAGES[STAGE - 1].t + '</h5>'
        + '<span class="pill" style="background:rgba(255,255,255,.18);color:#fff">Stage ' + STAGE + ' of 4</span></div>';

    const foot = () => '<div class="d-flex gap-2 mt-4 flex-wrap">'
        + (STAGE > 1 ? '<button class="btn-quiet" id="b_prev" type="button">Back</button>' : '')
        + (STAGE < 4 ? '<button class="btn-go" id="b_next" type="button">Continue</button>' : '')
        + '<button class="btn-quiet" id="b_reopen" type="button">Reopen from the record</button>'
        + '<button class="btn-ghost" id="b_open_classic" type="button">Open this inquiry in the classic panel</button>'
        + '</div>';

    function wireFoot() {
        if ($('b_prev')) { $('b_prev').addEventListener('click', () => { STAGE--; render(); }); }
        if ($('b_next')) { $('b_next').addEventListener('click', () => { STAGE++; render(); }); }
        if ($('b_reopen')) { $('b_reopen').addEventListener('click', reopen); }
        $('b_open_classic').addEventListener('click', () => { window.location.href = CLASSIC + '&id=' + IT.id; });
    }

    // ---------------------------------------------------------------- an editable inquiry field
    // One control, one key of the inquiry's own field set, saved through intake/{id}/field —
    // the same endpoint, the same validation and the same history line as the classic panel.

    // tf-confirmux-r1 (3 Nov 2026): every "are you sure" in this panel is asked in the panel, never by the
    // browser. A browser that suppresses dialogs — Edge does, once someone ticks "don't let this page create
    // more dialogs" — answers no silently, and the button looks dead. Here the button itself asks: the first
    // press turns it into the answer and says what will happen beside it; the second press does it. It gives
    // up on its own after ten seconds, and only one button is ever asking at a time.
    let ASKING = null;
    function askStop() {
        if (!ASKING) { return; }
        const a = ASKING; ASKING = null;
        clearTimeout(a.timer);
        a.el.innerHTML = a.html;
        a.el.classList.remove('asking');
        if (a.note && a.note.parentNode) { a.note.parentNode.removeChild(a.note); }
    }
    function ask(el, question, yesLabel, run) {
        // tf-confirmux-r2: a double-click is one burst, not two decisions — the armed button ignores a second
        // press inside half a second, so nothing can be armed and done by one slip of the mouse
        if (ASKING && ASKING.el === el) {
            if (Date.now() - ASKING.at < 500) { return; }
            askStop(); run(); return;
        }
        askStop();
        const note = document.createElement('span');
        note.className = 'ask-note';
        note.textContent = question;
        const html = el.innerHTML;
        el.innerHTML = yesLabel;
        el.classList.add('asking');
        if (el.parentNode) { el.parentNode.insertBefore(note, el.nextSibling); }
        ASKING = { el: el, html: html, note: note, at: Date.now(), timer: setTimeout(askStop, 10000) };
    }

    function fld(key, label, type, cls) {
        const v = IT[key] === null || IT[key] === undefined ? '' : IT[key];
        const val = (type === 'date') ? d10(v) : v;
        return '<label class="lb">' + esc(label) + '</label>'
            + '<input class="form-control ' + (cls || '') + '" type="' + (type || 'text') + '" data-fld="' + key + '" value="' + esc(val) + '"'
            + (locked() ? ' readonly' : '') + '>';
    }
    // tf-hotelgrade-r1: the hotel grade the agent asked for, from the same list the Hotel Master uses,
    // so a request can be matched to the hotels. A word typed in before the list existed is kept on it.
    const HOTEL_GRADES = ['Standard', 'Superior', 'Deluxe', 'Luxury'];
    function pickFld(key, label, list) {
        const v = IT[key] === null || IT[key] === undefined ? '' : String(IT[key]);
        const all = list.slice();
        if (v !== '' && !all.includes(v)) { all.push(v); }
        return '<label class="lb">' + esc(label) + '</label>'
            + '<select class="form-control" data-fld="' + key + '"' + (locked() ? ' disabled' : '') + '>'
            + '<option value="">\u2014 not set \u2014</option>'
            + all.map(x => '<option value="' + esc(x) + '"' + (x === v ? ' selected' : '') + '>' + esc(x)
                        + (list.includes(x) ? '' : ' (as it was typed)') + '</option>').join('')
            + '</select>';
    }

    function wireFields() {
        document.querySelectorAll('[data-fld]').forEach(el => {
            el.addEventListener('change', async () => {
                const key = el.dataset.fld;
                const was = el.value;
                const j = await save('/intake/' + IT.id + '/field', { key: key, value: el.value }, { render: false, costing: false });
                if (!j) { el.value = was; return; }        // refused: what was typed stays on the screen
                await loadCosting();
                rail();
            });
        });
    }

    // =====================================================================
    //  Stage 1 — Customer & Booking
    // =====================================================================
    async function stage1() {
        const reqs = IT.requests || [];
        const mm = await masters();
        const agents = mm.agents || [];
        let handlers = [];
        try { const hj = await api('/handlers'); handlers = (hj && hj.data) || []; } catch (e) {}
        const ed = IT.edit_requests || {};
        $('stage').innerHTML = '<div class="card2">' + header() + '<div class="bd">'
            + lockNote()
            + '<div class="row g-3">'
            + '<div class="col-md-5">' + fld('agent_name', 'Customer / agent') + '</div>'
            + '<div class="col-md-4"><label class="lb">Take a registered agent from the ledger</label>'
            +   '<select class="form-select" id="s_agent"' + (locked() ? ' disabled' : '') + '><option value="">— choose —</option>'
            +   agents.slice(0, 500).map(a => '<option value="' + esc(a.acc || '') + '"' + ((IT.agent_acc && String(IT.agent_acc) === String(a.acc)) ? ' selected' : '') + '>' + esc(a.name || a.acc) + '</option>').join('')
            +   '</select></div>'
            + '<div class="col-md-3"><label class="lb">Lead passenger</label><input class="form-control" value="' + esc(lead() || '—') + '" readonly></div>'
            + '<div class="col-md-4">' + fld('agent_email', 'Agent e-mail') + '</div>'
            + '<div class="col-md-4">' + fld('agent_country', 'Country') + '</div>'
            + '<div class="col-md-4">' + fld('agent_reference', 'Agent’s own reference') + '</div>'
            + '<div class="col-md-5">' + fld('tour_name', 'Tour name') + '</div>'
            + '<div class="col-md-4"><label class="lb">Handling agent</label><input class="form-control" value="' + esc(IT.handler_name || '—') + '" readonly></div>'
            + '<div class="col-md-3"><label class="lb">Status</label>'
            +   '<select class="form-select" id="s_status"' + (locked() ? ' disabled' : '') + '>'
            +   ['new', 'working', 'quoted', 'lost'].map(s => '<option value="' + s + '"' + (String(IT.status || '') === s ? ' selected' : '') + '>' + s + '</option>').join('')
            +   '</select></div>'
            + '<div class="col-md-3"><label class="lb">Adults</label><input class="form-control" value="' + num(IT.pax_adults) + '" readonly></div>'
            + '<div class="col-md-3"><label class="lb">Children</label><input class="form-control" value="' + num(IT.pax_children) + '" readonly></div>'
            + '<div class="col-md-3"><label class="lb">Came in as</label><input class="form-control" value="' + esc(source()) + '" readonly></div>'
            + '<div class="col-md-3"><label class="lb">Follow up on</label><input type="date" class="form-control" id="f_follow" value="' + esc(d10(IT.follow_up_on)) + '"' + (locked() ? ' readonly' : '') + '></div>'
            + '</div>'
            + '<div class="muted mt-2">The party is counted on Travel Details, with the names and the room mix. The handling agent is set on the inquiry list, as before.</div>'

            + '<h6 class="mt-4 mb-2">Requests &amp; questions <span class="pill pill-plum">' + reqs.length + '</span></h6>'
            + '<div class="wrap"><table class="tbl"><thead><tr><th>What was asked</th><th style="width:130px">Status</th><th>Answer</th><th style="width:150px"></th></tr></thead><tbody>'
            + reqs.map(r => '<tr data-req="' + r.id + '">'
                + '<td><input class="form-control sm" data-k="title" value="' + esc(r.title || r.quote_text || '') + '"' + (locked() ? ' readonly' : '') + '>'
                +     (r.quote_text && r.quote_text !== r.title ? '<div class="muted small mt-1">from the e-mail: ' + esc(r.quote_text) + '</div>' : '') + '</td>'
                + '<td><select class="form-select sm" data-k="status"' + (locked() ? ' disabled' : '') + '>'
                +     ['open', 'answered', 'declined'].map(s => '<option value="' + s + '"' + (String(r.status || 'open') === s ? ' selected' : '') + '>' + s + '</option>').join('') + '</select></td>'
                + '<td><input class="form-control sm" data-k="answer" value="' + esc(r.answer || '') + '"' + (locked() ? ' readonly' : '') + '></td>'
                + '<td><div class="acts">' + (locked() ? '' : '<button class="btn-quiet btn-xs" data-act="save" type="button">Save</button><button class="btn-red btn-xs" data-act="del" type="button">Remove</button>') + '</div></td></tr>').join('')
            + (reqs.length ? '' : '<tr><td colspan="4" class="muted">None recorded on this inquiry.</td></tr>')
            + '</tbody></table></div>'
            + (locked() ? '' : '<div class="row g-2 mt-2 align-items-end"><div class="col-md-8"><label class="lb">Add a request or question</label><input class="form-control" id="r_new" placeholder="what the agent asked for"></div>'
                + '<div class="col-md-4"><button class="btn-quiet" id="r_add" type="button">Add it</button></div></div>')

            + '<h6 class="mt-4 mb-2">The agent’s e-mail</h6>'
            + (IT.source_text
                ? '<div class="muted mb-1">' + esc(IT.source_from || '') + (IT.source_subject ? ' — ' + esc(IT.source_subject) : '')
                    + (IT.source_received_at ? ' · ' + esc(IT.source_received_at) : '') + '</div>'
                  + '<textarea class="form-control" id="t_mail" rows="7"' + (locked() ? ' readonly' : '') + '>' + esc(IT.source_text) + '</textarea>'
                  + (IT.extract_error ? '<div class="note mt-2">The last read did not finish: ' + esc(IT.extract_error) + '</div>' : '')
                  + (ed.read_blocked ? '<div class="note mt-2">' + esc(ed.read_blocked) + '</div>' : '')
                  + (locked() ? '' : '<div class="acts mt-2"><button class="btn-quiet" id="m_save" type="button">Save the corrected text</button>'
                     + (ed.read_blocked ? '' : '<button class="btn-ghost" id="m_read" type="button">Read it again</button>') + '</div>')
                  + '<div class="muted mt-1">Reading keeps everything already entered by hand. The original stays exactly as it was pasted.</div>'
                : (locked() ? '<div class="muted">No e-mail is kept with this inquiry.</div>'
                   : '<div class="muted mb-2">Nothing pasted yet. An inquiry started by hand can still be given the agent’s e-mail here.</div>'
                     + '<textarea class="form-control" id="t_paste" rows="7" placeholder="paste the e-mail here"></textarea>'
                     + '<div class="acts mt-2"><button class="btn-go" id="m_attach_read" type="button">Attach it and read it</button>'
                     + '<button class="btn-quiet" id="m_attach" type="button">Attach it only</button></div>'))
            + foot() + '</div></div>';

        wireFields(); wireFoot();

        if ($('s_agent')) { $('s_agent').addEventListener('change', async e => {
            if (!e.target.value) { return; }
            await save('/intake/' + IT.id + '/agent', { acc: e.target.value });
        }); }
        if ($('s_status')) { $('s_status').addEventListener('change', e => save('/intake/' + IT.id + '/status', { status: e.target.value })); }
        if ($('f_follow')) { $('f_follow').addEventListener('change', e => save('/intake/' + IT.id + '/follow_up', { on: e.target.value || null })); }

        document.querySelectorAll('[data-req]').forEach(tr => {
            const id = tr.dataset.req;
            const g = k => { const el = tr.querySelector('[data-k="' + k + '"]'); return el ? el.value : ''; };
            const sv = tr.querySelector('[data-act="save"]'), dl = tr.querySelector('[data-act="del"]');
            if (sv) { sv.addEventListener('click', () => save('/intake/' + IT.id + '/request/' + id, { title: g('title'), status: g('status'), answer: g('answer') })); }
            if (dl) { dl.addEventListener('click', () => ask(dl, 'This removes the request from the inquiry.', 'Yes \u2014 remove', () => save('/intake/' + IT.id + '/request/' + id, { delete: 1 }))); }
        });
        if ($('r_add')) { $('r_add').addEventListener('click', () => {
            const t = $('r_new').value.trim();
            if (!t) { err('Type the request first.'); return; }
            save('/intake/' + IT.id + '/request', { title: t });
        }); }

        if ($('m_save')) { $('m_save').addEventListener('click', () => save('/intake/' + IT.id + '/email_edit', { source_text: $('t_mail').value })); }
        if ($('m_read')) { $('m_read').addEventListener('click', () => save('/intake/' + IT.id + '/read', {})); }
        if ($('m_attach')) { $('m_attach').addEventListener('click', () => {
            const t = $('t_paste').value.trim();
            if (!t) { err('Paste the e-mail first.'); return; }
            save('/intake/' + IT.id + '/email', { source_text: t, read: 0 });
        }); }
        if ($('m_attach_read')) { $('m_attach_read').addEventListener('click', () => {
            const t = $('t_paste').value.trim();
            if (!t) { err('Paste the e-mail first.'); return; }
            save('/intake/' + IT.id + '/email', { source_text: t, read: 1 });
        }); }
    }

    // =====================================================================
    //  Stage 2 — Travel Details
    // =====================================================================
    function stage2() {
        const days = IT.days || [], pax = IT.passengers || [], groups = IT.groups || [], rooms = IT.rooms || {};
        const dc = IT.dates_check || {};
        const gopts = sel => '<option value="">whole party</option>' + groups.map(g => '<option value="' + g.id + '"' + (String(sel || '') === String(g.id) ? ' selected' : '') + '>' + esc(g.name || ('Group ' + g.id)) + '</option>').join('');
        const party = IT.pax_total !== null && IT.pax_total !== undefined ? IT.pax_total : (Number(IT.pax_adults) || 0) + (Number(IT.pax_children) || 0);

        $('stage').innerHTML = '<div class="card2">' + header() + '<div class="bd">'
            + lockNote()
            + (dc.differs && !dc.confirmed
                ? '<div class="note">The e-mail says ' + esc(d10(dc.email_arrival)) + ' to ' + esc(d10(dc.email_departure)) + ', the inquiry says ' + esc(d10(IT.arrival_date)) + ' to ' + esc(d10(IT.departure_date)) + '. '
                  + (locked() ? '' : '<button class="btn-quiet btn-xs ms-2" id="d_email" type="button">Use the e-mail dates</button> <button class="btn-quiet btn-xs" id="d_keep" type="button">Keep these dates</button>') + '</div>'
                : '')
            + '<div class="row g-3">'
            + '<div class="col-md-3">' + fld('arrival_date', 'Arrival', 'date') + '</div>'
            + '<div class="col-md-3">' + fld('departure_date', 'Departure', 'date') + '</div>'
            + '<div class="col-md-2"><label class="lb">Nights</label><input class="form-control" value="' + num(days.length) + '" readonly></div>'
            + '<div class="col-md-2"><label class="lb">Party</label><input class="form-control" value="' + num(party) + '" readonly></div>'
            + '<div class="col-md-2">' + fld('meal_basis', 'Meal basis') + '</div>'
            + '<div class="col-md-2">' + fld('pax_adults', 'Adults', 'number') + '</div>'
            + '<div class="col-md-2">' + fld('pax_children', 'Children', 'number') + '</div>'
            + '<div class="col-md-2">' + pickFld('hotel_class', 'Hotel grade asked for', HOTEL_GRADES) + '</div>'
            + '<div class="col-md-3">' + fld('arrival_flight', 'Arrival flight') + '</div>'
            + '<div class="col-md-3">' + fld('departure_flight', 'Departure flight') + '</div>'
            + '<div class="col-md-12">' + fld('rooms_note', 'What the agent said about rooms') + '</div>'
            + '</div>'
            + '<div class="muted mt-2">The night count follows the dates; it is never typed. The nights themselves are on Itinerary and Services.</div>'
            + ((IT.missing_nights || []).length && !locked()
                ? '<div class="note mt-3">' + (IT.missing_nights || []).length + ' night(s) the dates give have no line yet. <button class="btn-quiet btn-xs ms-2" id="d_missing" type="button">Add a blank line for each</button></div>' : '')

            + '<h6 class="mt-4 mb-2">Passengers <span class="pill pill-plum">' + pax.length + '</span></h6>'
            + '<div class="wrap"><table class="tbl"><thead><tr><th>Name</th><th style="width:120px">Room type</th><th style="width:170px">Group</th><th>Note</th><th style="width:150px"></th></tr></thead><tbody>'
            + pax.map(p => '<tr data-pax="' + p.id + '">'
                + '<td><input class="form-control sm" data-k="full_name" value="' + esc(p.full_name || '') + '"' + (locked() ? ' readonly' : '') + '></td>'
                + '<td><input class="form-control sm" data-k="room_type" value="' + esc(p.room_type || '') + '" placeholder="DBL"' + (locked() ? ' readonly' : '') + '></td>'
                + '<td><select class="form-select sm" data-k="group_id"' + (locked() ? ' disabled' : '') + '>' + gopts(p.group_id) + '</select></td>'
                + '<td><input class="form-control sm" data-k="note" value="' + esc(p.note || '') + '"' + (locked() ? ' readonly' : '') + '></td>'
                + '<td><div class="acts">' + (locked() ? '' : '<button class="btn-quiet btn-xs" data-act="save" type="button">Save</button><button class="btn-red btn-xs" data-act="del" type="button">Remove</button>') + '</div></td></tr>').join('')
            + (pax.length ? '' : '<tr><td colspan="5" class="muted">No names listed yet.</td></tr>')
            + '</tbody></table></div>'
            + (locked() ? '' : '<div class="row g-2 mt-2 align-items-end"><div class="col-md-5"><label class="lb">Add a name</label><input class="form-control" id="p_new"></div>'
                + '<div class="col-md-3"><label class="lb">Room type</label><input class="form-control" id="p_new_rt" placeholder="DBL"></div>'
                + '<div class="col-md-4"><button class="btn-quiet" id="p_add" type="button">Add the name</button></div></div>')

            + '<h6 class="mt-4 mb-2">Groups <span class="pill pill-plum">' + groups.length + '</span></h6>'
            + '<div class="muted mb-2">A group is used when one party splits — its own vehicle, its own rooms. Leave it empty when everybody travels together.</div>'
            + (groups.length
                ? '<div class="wrap"><table class="tbl"><thead><tr><th>Group</th><th style="width:85px">Pax</th><th style="width:75px">SGL</th><th style="width:75px">DBL</th><th style="width:75px">TWN</th><th style="width:75px">TPL</th><th style="width:95px">First night</th><th style="width:95px">Last night</th><th style="width:150px"></th></tr></thead><tbody>'
                  + groups.map(g => '<tr data-grp="' + g.id + '">'
                    + '<td><input class="form-control sm" data-k="name" value="' + esc(g.name || '') + '"' + (locked() ? ' readonly' : '') + '></td>'
                    + '<td><input type="number" min="0" class="form-control sm" data-k="pax" value="' + esc(g.pax === null || g.pax === undefined ? '' : g.pax) + '"' + (locked() ? ' readonly' : '') + '></td>'
                    + ['sgl', 'dbl', 'twn', 'tpl'].map(k => '<td><input type="number" min="0" class="form-control sm" data-k="rooms_' + k + '" value="' + esc(g['rooms_' + k] === null || g['rooms_' + k] === undefined ? 0 : g['rooms_' + k]) + '"' + (locked() ? ' readonly' : '') + '></td>').join('')
                    + '<td><input type="number" min="1" class="form-control sm" data-k="day_from" value="' + esc(g.day_from === null || g.day_from === undefined ? '' : g.day_from) + '"' + (locked() ? ' readonly' : '') + '></td>'
                    + '<td><input type="number" min="1" class="form-control sm" data-k="day_to" value="' + esc(g.day_to === null || g.day_to === undefined ? '' : g.day_to) + '"' + (locked() ? ' readonly' : '') + '></td>'
                    + '<td><div class="acts">' + (locked() ? '' : '<button class="btn-quiet btn-xs" data-act="save" type="button">Save</button><button class="btn-red btn-xs" data-act="del" type="button">Remove</button>') + '</div></td></tr>').join('')
                  + '</tbody></table></div>'
                : '<div class="muted">No groups: the whole party travels together.</div>')
            + (locked() ? '' : '<div class="row g-2 mt-2 align-items-end"><div class="col-md-5"><label class="lb">Add a group</label><input class="form-control" id="g_new" placeholder="for example Sato family"></div>'
                + '<div class="col-md-4"><button class="btn-quiet" id="g_add" type="button">Add the group</button></div></div>')

            + '<h6 class="mt-4 mb-2">Room mix</h6>'
            + '<div class="muted mb-2">' + esc(rooms.rooms !== undefined ? (rooms.rooms + ' room(s) sleeping ' + rooms.sleeps + ' for a party of ' + rooms.pax + (rooms.source ? ' (' + rooms.source + ')' : '')) : '') + '</div>'
            + '<div class="row g-2">'
            + ['sgl', 'dbl', 'twn', 'tpl'].map(k => '<div class="col-6 col-md-2"><label class="lb">' + k.toUpperCase() + '</label>'
                + '<input type="number" min="0" class="form-control" data-room="' + k + '" value="' + esc(rooms[k] === null || rooms[k] === undefined ? 0 : rooms[k]) + '"' + (locked() ? ' readonly' : '') + '></div>').join('')
            + (locked() ? '' : '<div class="col-md-4 d-flex align-items-end"><button class="btn-quiet" id="rm_save" type="button">Save the room mix</button></div>')
            + '</div>'
            + foot() + '</div></div>';

        wireFields(); wireFoot();

        if ($('d_email'))   { $('d_email').addEventListener('click', () => save('/intake/' + IT.id + '/dates', { action: 'email' })); }
        if ($('d_keep'))    { $('d_keep').addEventListener('click', () => save('/intake/' + IT.id + '/dates', { action: 'keep' })); }
        if ($('d_missing')) { $('d_missing').addEventListener('click', () => save('/intake/' + IT.id + '/nights_missing', {})); }

        document.querySelectorAll('[data-pax]').forEach(tr => {
            const id = tr.dataset.pax;
            const g = k => { const el = tr.querySelector('[data-k="' + k + '"]'); return el ? el.value : ''; };
            const sv = tr.querySelector('[data-act="save"]'), dl = tr.querySelector('[data-act="del"]');
            if (sv) { sv.addEventListener('click', async () => {
                // the group is only sent when it really changed: one press, one record, one message
                const now  = (pax.find(x => String(x.id) === String(id)) || {});
                const was  = now.group_id === null || now.group_id === undefined ? '' : String(now.group_id);
                const moved = String(g('group_id')) !== was;
                const j = await save('/intake/' + IT.id + '/pax/' + id,
                    { full_name: g('full_name'), room_type: g('room_type'), note: g('note') },
                    { render: !moved, costing: !moved });
                if (j && moved) { await save('/intake/' + IT.id + '/pax/' + id + '/group', { group_id: g('group_id') }); }
            }); }
            if (dl) { dl.addEventListener('click', () => ask(dl, 'This takes the passenger off the inquiry.', 'Yes \u2014 remove', () => save('/intake/' + IT.id + '/pax/' + id, { delete: 1 }))); }
        });
        if ($('p_add')) { $('p_add').addEventListener('click', () => {
            const n = $('p_new').value.trim();
            if (!n) { err('Type the name first.'); return; }
            save('/intake/' + IT.id + '/pax', { full_name: n, room_type: $('p_new_rt').value.trim() });
        }); }

        document.querySelectorAll('[data-grp]').forEach(tr => {
            const id = tr.dataset.grp;
            const g = k => { const el = tr.querySelector('[data-k="' + k + '"]'); return el ? el.value : ''; };
            const sv = tr.querySelector('[data-act="save"]'), dl = tr.querySelector('[data-act="del"]');
            if (sv) { sv.addEventListener('click', () => save('/intake/' + IT.id + '/group/' + id, {
                name: g('name'), pax: g('pax'), day_from: g('day_from'), day_to: g('day_to'),
                rooms_sgl: g('rooms_sgl'), rooms_dbl: g('rooms_dbl'), rooms_twn: g('rooms_twn'), rooms_tpl: g('rooms_tpl') })); }
            if (dl) { dl.addEventListener('click', () => ask(dl, 'Its names and vehicles go back with the whole party.', 'Yes \u2014 remove the group', () => save('/intake/' + IT.id + '/group/' + id, { delete: 1 }))); }
        });
        if ($('g_add')) { $('g_add').addEventListener('click', () => {
            const n = $('g_new').value.trim();
            if (!n) { err('Name the group first.'); return; }
            save('/intake/' + IT.id + '/group', { name: n });
        }); }

        if ($('rm_save')) { $('rm_save').addEventListener('click', () => {
            const b = {};
            document.querySelectorAll('[data-room]').forEach(el => { b[el.dataset.room] = el.value === '' ? 0 : Number(el.value); });
            save('/intake/' + IT.id + '/rooms', b);
        }); }
    }

    // =====================================================================
    //  Stage 3 — Itinerary & Services
    //  Ten sections. Each one edits through the endpoint the classic panel uses, so the
    //  hotel panel, the rate rules, the mileage history, the masters and the Mileage and
    //  Pricing privileges all apply here exactly as they do there.
    // =====================================================================
    let OPEN_SEC = 0;

    function areaOpts(sel) {
        const areas = (M && M.areas) || [];
        return '<option value="">—</option>' + areas.map(a => '<option value="' + esc(a.name) + '"' + (String(sel || '') === String(a.name) ? ' selected' : '') + '>' + esc(a.name) + '</option>').join('');
    }
    function hotelList(area) {
        const areas = (M && M.areas) || [], hot = (M && M.hotels) || {};
        const a = areas.find(x => String(x.name) === String(area));
        return a && hot[a.id] ? hot[a.id] : [];
    }

    async function stage3() {
        await masters();
        const days = IT.days || [], veh = IT.vehicles || [], crew = IT.crew || [], sectors = IT.sectors || [],
              exc = IT.excursions || [], svc = IT.services || [], meals = IT.meals || {}, mil = IT.mileage || {};
        const ro = locked();
        const secs = [];

        // ---- 1. day by day
        secs.push(['Day by day', days.length + ' night(s)',
            '<div class="muted mb-2">One line per night. The area and the hotel are the panel’s own masters; what is typed is matched the same way the classic panel matches it.</div>'
            + '<div class="wrap"><table class="tbl"><thead><tr><th style="width:60px">Night</th><th style="width:130px">Date</th><th>Area</th><th>Hotel</th>'
            + '<th style="width:110px">Meal plan</th><th style="width:90px">Rooms</th><th>Programme</th><th style="width:210px"></th></tr></thead><tbody>'
            + days.map(d => '<tr data-day="' + d.id + '">'
                + '<td class="mono">' + esc(d.day_no) + '</td>'
                + '<td><input type="date" class="form-control sm" data-k="day_date" value="' + esc(d10(d.day_date)) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td><select class="form-select sm" data-k="area"' + (ro ? ' disabled' : '') + '>' + areaOpts(d.area) + '</select></td>'
                + '<td><input class="form-control sm" data-k="hotel_name_raw" list="hl' + d.id + '" value="' + esc(d.hotel_name_raw || d.hotel_name || '') + '"' + (ro ? ' readonly' : '') + '>'
                +     '<datalist id="hl' + d.id + '">' + hotelList(d.area).slice(0, 300).map(h => '<option value="' + esc(h.name) + '">').join('') + '</datalist>'
                +     '<div class="muted small mt-1">' + esc(d.rate_state === 'frozen' ? ('rate kept ' + (d.rate_currency || '') + ' ' + (d.rate_dbl || d.rate_sgl || '')) : (d.rate_state || '')) + (d.match_note ? ' · ' + esc(String(d.match_note).slice(0, 90)) : '') + '</div></td>'
                + '<td><input class="form-control sm" data-k="meal_plan" value="' + esc(d.meal_plan || '') + '" placeholder="HB"' + (ro ? ' readonly' : '') + '></td>'
                + '<td><input type="number" min="0" class="form-control sm" data-k="rooms" value="' + esc(d.rooms === null || d.rooms === undefined ? '' : d.rooms) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td><input class="form-control sm" data-k="narrative" value="' + esc(d.narrative || '') + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td><div class="acts">' + (ro ? '' : '<button class="btn-quiet btn-xs" data-act="save" type="button">Save</button>'
                +     '<button class="btn-quiet btn-xs" data-act="reprice" type="button">Re-price</button>'
                +     '<button class="btn-red btn-xs" data-act="del" type="button">Remove</button>') + '</div></td></tr>').join('')
            + (days.length ? '' : '<tr><td colspan="8" class="muted">No nights yet.</td></tr>')
            + '</tbody></table></div>'
            + (ro ? '' : '<div class="acts mt-3"><button class="btn-quiet" id="day_add" type="button">Add a night</button>'
                + '<button class="btn-quiet" id="d_recheck" type="button">Check the hotels against the panel again</button></div>')
            + '<div class="muted mt-2">Special notes for a night are in the Special notes section below.</div>']);

        // ---- 2. hotels and their rates
        const frozen = days.filter(d => d.rate_state === 'frozen').length;
        const drift  = days.filter(d => d.rate_drift).length;
        secs.push(['Hotels', days.filter(d => d.hotel_name || d.hotel_name_raw || d.hotel_id).length + ' night(s) with a hotel',
            '<div class="muted mb-2">' + frozen + ' night(s) have a rate kept from the hotel panel' + (drift ? ', and ' + drift + ' have moved since' : '') + '. The hotel itself is chosen on the Day by day lines above.</div>'
            + '<div class="wrap"><table class="tbl"><thead><tr><th style="width:60px">Night</th><th>Hotel</th><th style="width:120px">Room type</th><th style="width:130px">Rate kept</th><th>What the panel says now</th></tr></thead><tbody>'
            + days.map(d => '<tr><td class="mono">' + esc(d.day_no) + '</td><td>' + esc(d.hotel_name_raw || d.hotel_name || '—') + '</td>'
                + '<td>' + esc(d.room_type_name || d.room_type_text || '—') + '</td>'
                + '<td class="mono">' + esc(d.rate_currency || '') + ' ' + esc(d.rate_dbl || d.rate_sgl || d.rate_tpl || '—') + '</td>'
                + '<td class="muted small">' + esc(d.rate_drift ? d.rate_drift.text : (d.rate_note || d.match_note || '')) + '</td></tr>').join('')
            + '</tbody></table></div>'
            + (ro || !drift ? '' : '<div class="acts mt-3"><button class="btn-quiet" id="d_takenew" type="button">Use all the new rates</button></div>'
                + '<div class="muted mt-1">Taking the new rates is a recalculation: it is logged, like every other.</div>')]);

        // ---- 3. meals
        const mdays = (meals.days || []);
        const isMeal = x => ['meal', 'lunch', 'dinner', 'breakfast'].indexOf(String(x.kind || '')) !== -1 || !!x.meal_id;
        const mealLines = svc.filter(isMeal).length;
        const mc = (meals.count && typeof meals.count === 'object') ? meals.count : null;
        secs.push(['Meals', mealLines + ' meal line(s)' + (mc ? ' · ' + (mc.arranged || 0) + ' arranged, ' + (mc.open || 0) + ' still open' : '') + ' · ' + days.filter(d => d.meal_plan).length + ' night(s) on a meal plan',
            (meals.available === false
                ? '<div class="muted">The Meal Master is not installed, so meals outside the hotel are not costed on this inquiry.</div>'
                : '<div class="muted mb-2">Lunches and dinners taken outside the hotel, from the Meal Master. The hotel’s own half-board dinner is marked on the night.</div>'
                  + '<div class="wrap"><table class="tbl"><thead><tr><th style="width:60px">Day</th><th>Meal</th><th style="width:110px">Type</th><th style="width:90px">Pax</th><th style="width:130px">Cost</th><th style="width:120px"></th></tr></thead><tbody>'
                  + svc.filter(isMeal).map(s => '<tr data-meal="' + s.id + '"><td class="mono">' + esc(s.day_no) + '</td>'
                    + '<td>' + esc(s.title || s.fee_code || '') + '<div class="muted small">' + esc(s.meal_time || '') + '</div></td>'
                    + '<td class="muted">' + esc(s.meal_auto ? 'automatic' : 'chosen') + '</td>'
                    + '<td class="mono">' + num(s.pax || s.qty) + '</td>'
                    + '<td class="mono">' + money(s.amount_usd) + '</td>'
                    + '<td>' + (ro ? '' : '<button class="btn-red btn-xs" data-act="del" type="button">Remove</button>') + '</td></tr>').join('')
                  + (mealLines ? '' : '<tr><td colspan="6" class="muted">No meals outside the hotel on this inquiry.</td></tr>')
                  + '</tbody></table></div>'
                  + (ro ? '' : '<div class="row g-2 mt-3 align-items-end">'
                     + '<div class="col-md-2"><label class="lb">Day</label><select class="form-select sm" id="ml_day">' + days.map(d => '<option value="' + d.day_no + '">' + d.day_no + '</option>').join('') + '</select></div>'
                     + '<div class="col-md-2"><label class="lb">Meal</label><select class="form-select sm" id="ml_type"><option value="lunch">lunch</option><option value="dinner">dinner</option></select></div>'
                     + '<div class="col-md-4"><label class="lb">From the Meal Master</label><select class="form-select sm" id="ml_pick"><option value="">— load the day first —</option></select></div>'
                     + '<div class="col-md-2"><label class="lb">Adults</label><input type="number" min="0" class="form-control sm" id="ml_ad" value="' + esc(IT.pax_adults === null || IT.pax_adults === undefined ? 0 : IT.pax_adults) + '"></div>'
                     + '<div class="col-md-2"><label class="lb">Children</label><input type="number" min="0" class="form-control sm" id="ml_ch" value="' + esc(IT.pax_children === null || IT.pax_children === undefined ? 0 : IT.pax_children) + '"></div>'
                     + '<div class="col-md-8 acts"><button class="btn-quiet" id="ml_load" type="button">What the day offers</button>'
                     + '<button class="btn-quiet" id="ml_add" type="button">Put it on the day</button>'
                     + '<button class="btn-quiet" id="ml_fill" type="button">Fill the whole tour</button></div></div>')
                  + ((meals.problems || []).length ? '<div class="note mt-3">' + esc((meals.problems || []).join(' ')) + '</div>' : ''))]);

        // ---- 4. transport and vehicles
        secs.push(['Transport &amp; vehicles', veh.length + ' vehicle(s)',
            '<div class="muted mb-2">The vehicle follows the party unless it is set by hand. The rate per kilometre is the master’s; a special rate is a pricing decision.</div>'
            + '<div class="wrap"><table class="tbl"><thead><tr><th>Vehicle</th><th style="width:110px">Class</th><th style="width:80px">Qty</th><th style="width:80px">Seats</th><th style="width:140px">Rate / km</th><th>Why this one</th></tr></thead><tbody>'
            + veh.map(v => '<tr data-veh="' + v.id + '" data-seq="' + (v.sort_order || 0) + '"><td>' + esc(v.label || '') + '</td>'
                + '<td><input class="form-control sm" data-k="type" value="' + esc(v.type || v.class_code || '') + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td><input type="number" min="1" class="form-control sm" data-k="qty" value="' + esc(v.qty || 1) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td class="mono">' + num(v.seats) + '</td>'
                + '<td class="mono">' + lkr(v.km_rate) + '<div class="muted small">' + esc(v.rate_by === 'master' ? 'master rate' : 'special') + '</div></td>'
                + '<td class="muted small">' + esc(v.why || '') + '</td></tr>').join('')
            + (veh.length ? '' : '<tr><td colspan="6" class="muted">No vehicle planned yet.</td></tr>')
            + '</tbody></table></div>'
            + (ro ? '' : '<div class="acts mt-3"><button class="btn-quiet" id="v_save" type="button">Save the vehicles</button>'
                + '<button class="btn-quiet" id="v_auto" type="button">Choose from the party again</button>'
                + (SEES_PRICING ? '<button class="btn-quiet" id="v_reprice" type="button">Re-price from today’s master rates</button>' : '') + '</div>')
            + (SEES_PRICING ? '' : '<div class="muted mt-2">A special rate per kilometre and re-pricing are Management’s, under Pricing.</div>')
            + '<h6 class="mt-4 mb-2" style="font-size:13px">Stay days</h6>'
            + '<div class="muted mb-2">On a day with no driving the vehicle can be kept on, and charged, or released.</div>'
            + '<div class="wrap"><table class="tbl"><tbody>'
            + (IT.vehicle_days || []).map(vd => '<tr data-vd="' + (vd.vehicle_seq || 0) + ':' + (vd.day_no || 0) + '"><td>Day ' + esc(vd.day_no) + '</td>'
                + '<td>' + esc(vd.label || '') + '</td><td>' + (vd.retained ? '<span class="pill pill-green">kept on</span>' : '<span class="pill pill-plum">released</span>') + '</td>'
                + '<td>' + (ro ? '' : '<button class="btn-quiet btn-xs" data-act="toggle" type="button">' + (vd.retained ? 'Release it' : 'Keep it on') + '</button>') + '</td></tr>').join('')
            + ((IT.vehicle_days || []).length ? '' : '<tr><td class="muted">No stay day on this itinerary.</td></tr>')
            + '</tbody></table></div>']);

        // ---- 5. crew
        secs.push(['Crew — driver &amp; guide', crew.length + ' crew line(s)',
            '<div class="muted mb-2">The bata per day is the master’s for the role (Master Files &rarr; Crew Bata) and never travels from this screen.</div>'
            + '<div class="wrap"><table class="tbl"><thead><tr><th>Role</th><th style="width:110px">Days</th><th style="width:130px">Bata / day</th><th style="width:130px">Amount</th><th>Why</th><th style="width:150px"></th></tr></thead><tbody>'
            + crew.map(c => '<tr data-crew="' + esc(c.role_code) + '"><td>' + esc(c.role_name || c.role_code) + '<div class="muted small">' + esc(c.kind || '') + ' · ' + esc(c.language || '') + '</div></td>'
                + '<td><input type="number" min="0" class="form-control sm" data-k="days" value="' + esc(c.days === null || c.days === undefined ? '' : c.days) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td class="mono">' + lkr(c.bata) + '</td>'
                + '<td class="mono">' + lkr(c.amount) + '</td>'
                + '<td class="muted small">' + esc(c.days_basis || c.why || '') + '</td>'
                + '<td><div class="acts">' + (ro ? '' : '<button class="btn-quiet btn-xs" data-act="save" type="button">Save</button><button class="btn-red btn-xs" data-act="del" type="button">Remove</button>') + '</div></td></tr>').join('')
            + (crew.length ? '' : '<tr><td colspan="6" class="muted">No crew planned yet.</td></tr>')
            + '</tbody></table></div>'
            + (ro ? '' : '<div class="row g-2 mt-3 align-items-end"><div class="col-md-5"><label class="lb">Add a role from the master</label>'
                + '<select class="form-select sm" id="cw_pick"><option value="">— choose —</option></select></div>'
                + '<div class="col-md-2"><label class="lb">Days</label><input type="number" min="0" class="form-control sm" id="cw_days"></div>'
                + '<div class="col-md-5 acts"><button class="btn-quiet" id="cw_add" type="button">Add the role</button>'
                + '<button class="btn-quiet" id="cw_auto" type="button">Choose from the agent’s market again</button></div></div>')]);

        // ---- 6. sectors and kilometres
        secs.push(['Sectors &amp; kilometres', sectors.length + ' sector(s)',
            '<div class="muted mb-2">' + num(mil.charged_km) + ' km charged of ' + num(mil.km) + ' driven' + (mil.extra_km ? ', ' + num(mil.extra_km) + ' km extra running' : '')
            + (mil.km_rate ? ', at ' + lkr(mil.km_rate) + ' a kilometre' : '') + '. A distance typed over the driving history needs the Mileage override privilege.</div>'
            + '<div class="wrap"><table class="tbl"><thead><tr><th style="width:55px">Day</th><th>From</th><th>To</th><th style="width:100px">Km</th><th style="width:100px">Extra</th><th style="width:100px">Actual</th><th>Where the figure comes from</th><th style="width:150px"></th></tr></thead><tbody>'
            + sectors.map(s => '<tr data-sec="' + s.id + '"' + (s.removed_at ? ' style="opacity:.5"' : '') + '>'
                + '<td><input type="number" min="0" class="form-control sm" data-k="day_no" value="' + esc(s.day_no) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td><input class="form-control sm" data-k="from_place" value="' + esc(s.from_place || '') + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td><input class="form-control sm" data-k="to_place" value="' + esc(s.to_place || '') + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td><input class="form-control sm mono" data-k="km" value="' + esc(s.km === null || s.km === undefined ? '' : s.km) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td><input class="form-control sm mono" data-k="extra_km" value="' + esc(s.extra_km === null || s.extra_km === undefined ? '' : s.extra_km) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td><input class="form-control sm mono" data-k="actual_km" value="' + esc(s.actual_km === null || s.actual_km === undefined ? '' : s.actual_km) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td class="muted small">' + esc(s.why || s.km_by || '') + (s.google_km ? ' · Google ' + esc(s.google_km) + ' km' : '') + '</td>'
                + '<td><div class="acts">' + (ro ? '' : (s.removed_at
                    ? '<button class="btn-quiet btn-xs" data-act="restore" type="button">Put it back</button>'
                    : '<button class="btn-quiet btn-xs" data-act="save" type="button">Save</button><button class="btn-red btn-xs" data-act="del" type="button">Take it off</button>')) + '</div></td></tr>').join('')
            + (sectors.length ? '' : '<tr><td colspan="8" class="muted">No legs yet.</td></tr>')
            + '</tbody></table></div>'
            + (ro ? '' : '<div class="row g-2 mt-3 align-items-end"><div class="col-md-2"><label class="lb">Day</label><input type="number" min="0" class="form-control sm" id="sc_day"></div>'
                + '<div class="col-md-3"><label class="lb">From</label><input class="form-control sm" id="sc_from"></div>'
                + '<div class="col-md-3"><label class="lb">To</label><input class="form-control sm" id="sc_to"></div>'
                + '<div class="col-md-4 acts"><button class="btn-quiet" id="sc_add" type="button">Add the leg</button>'
                + '<button class="btn-quiet" id="sc_auto" type="button">Read the driving again</button>'
                + '<button class="btn-quiet" id="sc_google" type="button">Ask Google</button></div></div>')]);

        // ---- 7. excursions and activities
        secs.push(['Excursions &amp; activities', exc.length + ' line(s)',
            '<div class="muted mb-2">Each line is costed at the excursion master’s own rate for its unit.</div>'
            + '<div class="wrap"><table class="tbl"><thead><tr><th style="width:55px">Day</th><th>Excursion</th><th style="width:90px">Qty</th><th style="width:120px">Rate</th><th style="width:120px">Amount</th><th style="width:150px"></th></tr></thead><tbody>'
            + exc.map(x => '<tr data-exc="' + x.id + '">'
                + '<td><input type="number" min="0" class="form-control sm" data-k="day_no" value="' + esc(x.day_no) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td>' + esc(x.name || x.code) + '<div class="muted small">' + esc(x.unit || '') + '</div></td>'
                + '<td><input class="form-control sm mono" data-k="qty" value="' + esc(x.qty) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td class="mono">' + esc(x.currency || '') + ' ' + esc(x.rate) + '</td>'
                + '<td class="mono">' + esc(x.currency || '') + ' ' + esc(x.amount) + '</td>'
                + '<td><div class="acts">' + (ro ? '' : '<button class="btn-quiet btn-xs" data-act="save" type="button">Save</button><button class="btn-red btn-xs" data-act="del" type="button">Remove</button>') + '</div></td></tr>').join('')
            + (exc.length ? '' : '<tr><td colspan="6" class="muted">No excursion on this inquiry.</td></tr>')
            + '</tbody></table></div>'
            + (ro ? '' : '<div class="row g-2 mt-3 align-items-end"><div class="col-md-2"><label class="lb">Day</label><input type="number" min="0" class="form-control sm" id="ex_day"></div>'
                + '<div class="col-md-6"><label class="lb">From the excursion master</label><select class="form-select sm" id="ex_pick"><option value="">— choose —</option></select></div>'
                + '<div class="col-md-4"><button class="btn-quiet" id="ex_add" type="button">Put it on the day</button></div></div>')]);

        // ---- 8. entrances and places
        const nonMeal = svc.filter(s => ['meal', 'lunch', 'dinner', 'breakfast'].indexOf(String(s.kind || '')) === -1 && !s.meal_id);
        secs.push(['Entrances &amp; places', nonMeal.length + ' service line(s)',
            '<div class="muted mb-2">Entrances, tickets and paid activities, each at the rate its master holds for the date.</div>'
            + '<div class="wrap"><table class="tbl"><thead><tr><th style="width:55px">Day</th><th>What</th><th style="width:110px">State</th><th style="width:120px">USD</th><th>How it was worked out</th><th style="width:120px"></th></tr></thead><tbody>'
            + nonMeal.map(s => '<tr data-svc="' + s.id + '">'
                + '<td><input type="number" min="0" class="form-control sm" data-k="day_no" value="' + esc(s.day_no) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td><input class="form-control sm" data-k="title" value="' + esc(s.title || s.fee_code || '') + '"' + (ro ? ' readonly' : '') + '><div class="muted small mt-1">' + esc(s.kind || '') + '</div></td>'
                + '<td>' + (s.match_state === 'matched' ? '<span class="pill pill-green">priced</span>' : '<span class="pill pill-amber">' + esc(s.match_state || '') + '</span>') + '</td>'
                + '<td class="mono">' + money(s.amount_usd) + '</td>'
                + '<td class="muted small">' + esc(String(s.match_note || '').slice(0, 160)) + '</td>'
                + '<td><div class="acts">' + (ro ? '' : '<button class="btn-quiet btn-xs" data-act="save" type="button">Save</button><button class="btn-red btn-xs" data-act="del" type="button">Remove</button>') + '</div></td></tr>').join('')
            + (nonMeal.length ? '' : '<tr><td colspan="6" class="muted">No entrance or paid place on this inquiry.</td></tr>')
            + '</tbody></table></div>'
            + (ro ? '' : '<div class="row g-2 mt-3 align-items-end"><div class="col-md-2"><label class="lb">Day</label><input type="number" min="0" class="form-control sm" id="sv_day"></div>'
                + '<div class="col-md-6"><label class="lb">From the priced list</label><select class="form-select sm" id="sv_pick"><option value="">— choose —</option></select>'
                + '<div class="muted small mt-1">Entrances, tickets and paid activities come priced from their master; anything else can be named by hand below.</div></div>'
                + '<div class="col-md-4"><button class="btn-quiet" id="sv_add" type="button">Put it on the day</button></div>'
                + '<div class="col-md-8"><label class="lb">…or name it by hand</label><input class="form-control sm" id="sv_title" placeholder="what the line is for"></div>'
                + '<div class="col-md-4"><button class="btn-quiet" id="sv_add_hand" type="button">Add the named line</button></div></div>')]);

        // ---- 9. tour photos (its own page, kept separate in the function map)
        secs.push(['Tour photos', 'kept with the day programme',
            '<div class="muted mb-2">The pictures on the client quotation are chosen on the Tour Photos page, which stays its own screen.</div>'
            + '<button class="btn-quiet" id="ph_open" type="button">Open Tour Photos for this inquiry</button>']);

        // ---- 10. special notes, per night
        secs.push(['Special notes', days.filter(d => d.special_note).length + ' night(s) with a note',
            '<div class="muted mb-2">A note kept with one night. It travels with the day on the desks and on the quotation.</div>'
            + '<div class="wrap"><table class="tbl"><thead><tr><th style="width:60px">Night</th><th>Note</th><th style="width:110px"></th></tr></thead><tbody>'
            + days.map(d => '<tr data-note="' + d.id + '"><td class="mono">' + esc(d.day_no) + '</td>'
                + '<td><input class="form-control sm" data-k="special_note" value="' + esc(d.special_note || '') + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td>' + (ro ? '' : '<button class="btn-quiet btn-xs" data-act="save" type="button">Save</button>') + '</td></tr>').join('')
            + '</tbody></table></div>']);

        $('stage').innerHTML = '<div class="card2">' + header() + '<div class="bd">'
            + lockNote()
            + '<div class="muted mb-3">Ten sections. Everything here is saved through the inquiry’s own endpoints, so the rate rules, the masters, the mileage history and the Pricing and Mileage privileges apply exactly as on the classic panel.</div>'
            + secs.map((s, i) => '<details class="sec"' + (i === OPEN_SEC ? ' open' : '') + ' data-si="' + i + '">'
                + '<summary><span>' + s[0] + '</span><span class="muted">' + esc(s[1]) + '</span></summary>'
                + '<div>' + s[2] + '</div></details>').join('')
            + foot() + '</div></div>';

        document.querySelectorAll('[data-si]').forEach(d => d.addEventListener('toggle', () => { if (d.open) { OPEN_SEC = Number(d.dataset.si); } }));
        wireFoot();
        wire3();
    }

    // ---------------------------------------------------------------- stage 3 wiring
    function rowVals(tr) {
        const o = {};
        tr.querySelectorAll('[data-k]').forEach(el => { o[el.dataset.k] = el.value; });
        return o;
    }

    function wire3() {
        const id = IT.id;

        // 1. day by day
        document.querySelectorAll('[data-day]').forEach(tr => {
            const d = tr.dataset.day;
            const sv = tr.querySelector('[data-act="save"]'), dl = tr.querySelector('[data-act="del"]'), rp = tr.querySelector('[data-act="reprice"]');
            if (sv) { sv.addEventListener('click', () => save('/intake/' + id + '/day/' + d, rowVals(tr))); }
            if (rp) { rp.addEventListener('click', () => save('/intake/' + id + '/day/' + d + '/reprice', {})); }
            if (dl) { dl.addEventListener('click', () => ask(dl, 'This takes the night off the itinerary.', 'Yes \u2014 remove the night', () => save('/intake/' + id + '/day/' + d, { delete: 1 }))); }
        });
        if ($('day_add'))   { $('day_add').addEventListener('click', () => save('/intake/' + id + '/day', {})); }
        if ($('d_recheck')) { $('d_recheck').addEventListener('click', () => save('/intake/' + id + '/recheck', {})); }
        if ($('d_takenew')) { $('d_takenew').addEventListener('click', () => ask($('d_takenew'), 'Every night that has moved takes today\u2019s rate. It is a recalculation, and it is logged.', 'Yes \u2014 take the new rates', () => save('/intake/' + id + '/recheck', { take_new_rates: 1 }))); }

        // 3. meals
        document.querySelectorAll('[data-meal]').forEach(tr => {
            const s = tr.dataset.meal, dl = tr.querySelector('[data-act="del"]');
            if (dl) { dl.addEventListener('click', () => ask(dl, 'This takes the meal off the day.', 'Yes \u2014 remove', () => save('/intake/' + id + '/service/' + s, { delete: 1 }))); }
        });
        if ($('ml_load')) { $('ml_load').addEventListener('click', async () => {
            const j = await api('/intake/' + id + '/meals/options?day=' + encodeURIComponent($('ml_day').value) + '&type=' + encodeURIComponent($('ml_type').value));
            if (!j || j.status !== 200) { err((j && j.message) || 'The Meal Master did not answer.'); return; }
            const d = j.data || {};
            const list = (d.suggestions || []).concat(d.others || []);
            $('ml_pick').innerHTML = list.length
                ? list.map(m => '<option value="' + esc(m.id || m.meal_id) + '">' + esc(m.restaurant || m.name || m.title || '') + (m.location ? ' — ' + esc(m.location) : '')
                    + (m.adult_rate ? ' · ' + esc(m.currency || '') + ' ' + esc(m.adult_rate) : '') + '</option>').join('')
                : '<option value="">the master offers nothing for that day</option>';
            ok(list.length ? list.length + ' place(s) the Meal Master offers for that day.' : 'The Meal Master has nothing for that day and meal.');
        }); }
        if ($('ml_add')) { $('ml_add').addEventListener('click', () => {
            const m = $('ml_pick').value;
            if (!m) { err('Choose a meal first.'); return; }
            save('/intake/' + id + '/meal', { day_no: $('ml_day').value, meal_type: $('ml_type').value, meal_id: m,
                qty_adult: $('ml_ad') ? $('ml_ad').value : 0, qty_child: $('ml_ch') ? $('ml_ch').value : 0 });
        }); }
        if ($('ml_fill')) { $('ml_fill').addEventListener('click', () => save('/intake/' + id + '/meals/fill', {})); }

        // 4. transport
        if ($('v_save')) { $('v_save').addEventListener('click', () => {
            const rows = [];
            document.querySelectorAll('[data-veh]').forEach(tr => { const v = rowVals(tr); rows.push({ type: v.type, qty: Number(v.qty || 1) }); });
            save('/intake/' + id + '/vehicle', { rows: rows });
        }); }
        if ($('v_auto'))    { $('v_auto').addEventListener('click', () => save('/intake/' + id + '/vehicle', { auto: 1 })); }
        if ($('v_reprice')) { $('v_reprice').addEventListener('click', () => save('/intake/' + id + '/vehicle', { reprice: 1 })); }
        document.querySelectorAll('[data-vd]').forEach(tr => {
            const [seq, day] = tr.dataset.vd.split(':');
            const t = tr.querySelector('[data-act="toggle"]');
            if (t) { t.addEventListener('click', () => save('/intake/' + id + '/vehicle_day', { vehicle_seq: seq, day_no: day, retained: /Keep/.test(t.textContent) ? 1 : 0 })); }
        });

        // 5. crew
        document.querySelectorAll('[data-crew]').forEach(tr => {
            const code = tr.dataset.crew;
            const sv = tr.querySelector('[data-act="save"]'), dl = tr.querySelector('[data-act="del"]');
            if (sv) { sv.addEventListener('click', () => save('/intake/' + id + '/crew', { code: code, days: rowVals(tr).days })); }
            if (dl) { dl.addEventListener('click', () => ask(dl, 'This takes the crew line off the inquiry.', 'Yes \u2014 remove', () => save('/intake/' + id + '/crew', { code: code, delete: 1 }))); }
        });
        if ($('cw_pick')) { crewM().then(list => {
            const rows = Array.isArray(list) ? list : (list.roles || []);
            $('cw_pick').innerHTML = '<option value="">— choose —</option>' + rows.map(r => '<option value="' + esc(r.code || r.role_code) + '">' + esc(r.name || r.role_name || r.code) + '</option>').join('');
        }); }
        if ($('cw_add'))  { $('cw_add').addEventListener('click', () => {
            const c = $('cw_pick').value;
            if (!c) { err('Choose the role first.'); return; }
            save('/intake/' + id + '/crew', { code: c, days: $('cw_days').value });
        }); }
        if ($('cw_auto')) { $('cw_auto').addEventListener('click', () => save('/intake/' + id + '/crew', { auto: 1 })); }

        // 6. sectors
        document.querySelectorAll('[data-sec]').forEach(tr => {
            const s = tr.dataset.sec;
            const sv = tr.querySelector('[data-act="save"]'), dl = tr.querySelector('[data-act="del"]'), rs = tr.querySelector('[data-act="restore"]');
            if (sv) { sv.addEventListener('click', async () => {
                const v = rowVals(tr);
                const j = await save('/intake/' + id + '/sector/' + s, { day_no: v.day_no, from_place: v.from_place, to_place: v.to_place, km: v.km, extra_km: v.extra_km }, { render: false });
                if (j && v.actual_km !== undefined) { await save('/intake/' + id + '/sector/' + s + '/actual', { km: v.actual_km }); }
                else { render(); }
            }); }
            if (dl) { dl.addEventListener('click', () => ask(dl, 'This takes the leg off the mileage.', 'Yes \u2014 remove', () => save('/intake/' + id + '/sector/' + s, { delete: 1 }))); }
            if (rs) { rs.addEventListener('click', () => save('/intake/' + id + '/sector/' + s, { restore: 1 })); }
        });
        if ($('sc_add'))    { $('sc_add').addEventListener('click', () => {
            if (!$('sc_from').value.trim() || !$('sc_to').value.trim()) { err('Type where the leg runs from and to.'); return; }
            save('/intake/' + id + '/sector', { day_no: $('sc_day').value, from_place: $('sc_from').value.trim(), to_place: $('sc_to').value.trim() });
        }); }
        if ($('sc_auto'))   { $('sc_auto').addEventListener('click', () => ask($('sc_auto'), 'Every leg typed by hand is thrown away and the driving history is read again.', 'Yes \u2014 read it again', () => save('/intake/' + id + '/sector', { auto: 1 }))); }
        if ($('sc_google')) { $('sc_google').addEventListener('click', () => save('/intake/' + id + '/google', {})); }

        // 7. excursions
        document.querySelectorAll('[data-exc]').forEach(tr => {
            const x = tr.dataset.exc;
            const sv = tr.querySelector('[data-act="save"]'), dl = tr.querySelector('[data-act="del"]');
            if (sv) { sv.addEventListener('click', () => save('/intake/' + id + '/excursion/' + x, rowVals(tr))); }
            if (dl) { dl.addEventListener('click', () => ask(dl, 'This takes the excursion off the day.', 'Yes \u2014 remove', () => save('/intake/' + id + '/excursion/' + x, { delete: 1 }))); }
        });
        if ($('ex_pick')) { excM().then(list => {
            const rows = Array.isArray(list) ? list : (list.items || []);
            $('ex_pick').innerHTML = '<option value="">— choose —</option>' + rows.map(r => '<option value="' + esc(r.code) + '">' + esc(r.name || r.code) + (r.rate ? ' — ' + esc(r.currency || '') + ' ' + esc(r.rate) : '') + '</option>').join('');
        }); }
        if ($('ex_add')) { $('ex_add').addEventListener('click', () => {
            const c = $('ex_pick').value;
            if (!c) { err('Choose the excursion first.'); return; }
            save('/intake/' + id + '/excursion', { day_no: $('ex_day').value, code: c });
        }); }

        // 8. entrances and places
        document.querySelectorAll('[data-svc]').forEach(tr => {
            const s = tr.dataset.svc;
            const sv = tr.querySelector('[data-act="save"]'), dl = tr.querySelector('[data-act="del"]');
            if (sv) { sv.addEventListener('click', () => save('/intake/' + id + '/service/' + s, rowVals(tr))); }
            if (dl) { dl.addEventListener('click', () => ask(dl, 'This takes the line off the day.', 'Yes \u2014 remove', () => save('/intake/' + id + '/service/' + s, { delete: 1 }))); }
        });
        let SVROWS = [];
        if ($('sv_pick')) { svcM().then(d => {
            SVROWS = (d && d.items) || [];
            $('sv_pick').innerHTML = '<option value="">— choose —</option>' + SVROWS.slice(0, 500).map((r, i) => '<option value="' + i + '">' + esc(r.name || r.code) + (r.usd ? ' — $' + esc(r.usd) : '') + '</option>').join('');
        }); }
        if ($('sv_add')) { $('sv_add').addEventListener('click', () => {
            const i = $('sv_pick').value;
            if (i === '') { err('Choose the place first.'); return; }
            const r = SVROWS[Number(i)] || {};
            const b = { day_no: $('sv_day').value, title: r.name || r.code, fee_code: r.code, kind: r.kind };
            if (r.place_id) { b.place_id = r.place_id; }
            if (r.id && r.master) { b.fee_id = r.id; }
            save('/intake/' + id + '/service', b);
        }); }
        if ($('sv_add_hand')) { $('sv_add_hand').addEventListener('click', () => {
            const t = $('sv_title').value.trim();
            if (!t) { err('Name the line first.'); return; }
            save('/intake/' + id + '/service', { day_no: $('sv_day').value, title: t });
        }); }

        // 9. photos — its own page
        if ($('ph_open')) { $('ph_open').addEventListener('click', () => { window.location.href = CLASSIC + '&id=' + id + '&panel=photos'; }); }

        // 10. special notes
        document.querySelectorAll('[data-note]').forEach(tr => {
            const d = tr.dataset.note, sv = tr.querySelector('[data-act="save"]');
            if (sv) { sv.addEventListener('click', () => save('/intake/' + id + '/day/' + d, { special_note: rowVals(tr).special_note })); }
        });
    }

    // =====================================================================
    //  Stage 4 — Review & Quotation
    //  The costing, the markup (Management only), the Child Fare Review, Go for Quotation,
    //  the versions and the quotation e-mail. Every one of them is the existing endpoint:
    //  the rules that decide what may be issued, and to whom it may be sent, are unchanged.
    // =====================================================================
    async function stage4() {
        const m = (COST && COST.markup) || {};
        const quotes = IT.quotes || [];
        const ro = locked();
        if (REVIEW === null) {
            try { const j = await api('/intake/' + IT.id + '/child_review'); REVIEW = (j && j.data && j.data.review) || {}; }
            catch (e) { REVIEW = {}; }
        }
        const items = (REVIEW && REVIEW.items) || [];
        const kids  = (REVIEW && REVIEW.children) || [];
        const beds  = (REVIEW && REVIEW.beds) || { no_bed: 'shares a bed', extra_bed: 'extra bed' };
        const rsrc = (REVIEW && REVIEW.reasons) || [];
        const reasons = Array.isArray(rsrc)
            ? rsrc.map(x => (typeof x === 'string' ? { code: x, label: x } : { code: x.code || x.key || '', label: x.label || x.name || x.code || '' }))
            : Object.keys(rsrc).map(k => ({ code: k, label: typeof rsrc[k] === 'string' ? rsrc[k] : (rsrc[k] && (rsrc[k].label || rsrc[k].name)) || k }));
        const nKids = (Number(IT.pax_children) || 0);

        $('stage').innerHTML = '<div class="card2">' + header() + '<div class="bd">'
            + lockNote()
            + (COST ? (COST.cost_hidden
                ? '<div class="row g-3">'
                  + '<div class="col-md-4"><label class="lb">Client price</label><div class="form-control mono">' + money(m.sell_usd) + '</div></div>'
                  + '<div class="col-md-4"><label class="lb">Per person</label><div class="form-control mono">' + money(m.per_person_usd) + '</div></div>'
                  + '<div class="col-md-4"><label class="lb">Party</label><div class="form-control mono">' + num(COST.pax) + '</div></div>'
                  + '</div>'
                : '<div class="row g-3">'
                  + '<div class="col-md-3"><label class="lb">Cost</label><div class="form-control mono">' + money(COST.cost_usd) + '</div></div>'
                  + '<div class="col-md-3"><label class="lb">Client price</label><div class="form-control mono">' + money(m.sell_usd) + '</div></div>'
                  + '<div class="col-md-3"><label class="lb">Per person</label><div class="form-control mono">' + money(m.per_person_usd) + '</div></div>'
                  + '<div class="col-md-3"><label class="lb">Party</label><div class="form-control mono">' + num(COST.pax) + '</div></div>'
                  + '</div>')
                + (m.hidden
                    ? '<div class="note mt-3">The tour cost, the markup and the profit belong to Management, under Pricing, and are not shown on this desk. The supplier figure on each line stays, for the work itself.</div>'
                    : (SEES_PRICING && !ro
                        ? '<div class="row g-2 mt-2 align-items-end"><div class="col-md-3"><label class="lb">Markup for this inquiry (%)</label>'
                          + '<input class="form-control" id="mk_pct" value="' + esc(IT.markup_pct === null || IT.markup_pct === undefined ? '' : IT.markup_pct) + '" placeholder="blank = the default"></div>'
                          + '<div class="col-md-5"><label class="lb">Why</label><input class="form-control" id="mk_note"></div>'
                          + '<div class="col-md-4"><button class="btn-quiet" id="mk_save" type="button">Set the markup</button></div></div>'
                          + '<div class="muted mt-1">' + esc(IT.markup_at ? ('Last set by ' + (IT.markup_by || '') + ' on ' + IT.markup_at) : 'On the default markup.') + '</div>'
                        : ''))
                : '<div class="note">The costing has not been built for this inquiry yet. Fill the itinerary on stage 3 and it is costed from the masters.</div>')

            + '<div class="acts mt-3">'
            + (ro ? '' : '<button class="btn-quiet" id="b_recalc" type="button">Recalculate the saved figures</button>')
            + '<button class="btn-quiet" id="b_prev_int" type="button">Preview — internal</button>'
            + '<button class="btn-quiet" id="b_prev_cli" type="button">Preview — client</button>'
            + '</div>'
            + ((IT.recalc || []).length ? '<div class="muted mt-2">Last recalculation: ' + esc((IT.recalc[0] || {}).what || '') + ' · ' + esc((IT.recalc[0] || {}).at || '') + '</div>' : '')

            // ---- Child Fare Review
            + '<h6 class="mt-4 mb-2">Child Fare Review</h6>'
            + (nKids === 0
                ? '<div class="muted">No children on this inquiry, so there is nothing to review.</div>'
                : '<div class="muted mb-2">' + nKids + ' child(ren) on this inquiry. Each child\u2019s age and bedding decide what the contracts and the masters charge, so they are entered first; every item whose charge depends on a child is then decided below, before a quotation is issued.</div>'
                  + '<div class="wrap"><table class="tbl"><thead><tr><th style="width:90px">Child</th><th style="width:140px">Age</th><th style="width:220px">Bedding</th></tr></thead><tbody>'
                  + Array.from({ length: nKids }, (x, i) => { const c = kids[i] || {}; return '<tr data-kid="' + i + '">'
                    + '<td class="mono">' + (i + 1) + '</td>'
                    + '<td><input type="number" min="0" max="17" step="0.5" class="form-control sm" data-k="age" value="' + esc(c.age === null || c.age === undefined ? '' : c.age) + '"' + (ro ? ' readonly' : '') + '></td>'
                    + '<td><select class="form-select sm" data-k="bed"' + (ro ? ' disabled' : '') + '>'
                    +   Object.keys(beds).map(k => '<option value="' + esc(k) + '"' + (String(c.bed || 'no_bed') === k ? ' selected' : '') + '>' + esc(beds[k]) + '</option>').join('')
                    + '</select></td></tr>'; }).join('')
                  + '</tbody></table></div>'
                  + (ro ? '' : '<button class="btn-quiet mt-2" id="kid_save" type="button">Save the children</button>')
                  + '<div class="wrap mt-3"><table class="tbl"><thead><tr><th>Item</th><th>What the rule gives</th><th style="width:140px">Decision</th><th style="width:190px">Why</th><th style="width:170px"></th></tr></thead><tbody>'
                  + items.map(r => '<tr data-cf="' + esc(r.key) + '"><td>' + esc(r.title || r.key) + '<div class="muted small">' + esc(r.source || '') + '</div></td>'
                    + '<td class="small">' + esc(r.child_calc || r.suggest_why || '') + (r.policy ? '<div class="muted small mt-1">' + esc(String(r.policy).slice(0, 180)) + '</div>' : '') + '</td>'
                    + '<td>' + (r.decision === 'applicable' ? '<span class="pill pill-green">applicable</span>' : (r.decision === 'not_applicable' ? '<span class="pill pill-plum">no charge</span>' : '<span class="pill pill-amber">to check</span>'))
                    +   (r.status === 'review_again' ? '<div class="muted small mt-1">to check again</div>' : '')
                    +   (r.decided_by ? '<div class="muted small mt-1">' + esc(r.decided_by) + '</div>' : '') + '</td>'
                    + '<td><select class="form-select sm" data-k="reason_code"' + (ro ? ' disabled' : '') + '><option value="">— why —</option>'
                    +   reasons.map(x => '<option value="' + esc(x.code) + '"' + (String(r.reason_code || '') === String(x.code) ? ' selected' : '') + '>' + esc(x.label) + '</option>').join('')
                    + '</select><input class="form-control sm mt-1" data-k="reason" value="' + esc(r.reason || '') + '" placeholder="in your own words"' + (ro ? ' readonly' : '') + '></td>'
                    + '<td><div class="acts">' + (ro ? '' : '<button class="btn-quiet btn-xs" data-act="yes" type="button">Applicable</button><button class="btn-quiet btn-xs" data-act="no" type="button">No charge</button>') + '</div></td></tr>').join('')
                  + (items.length ? '' : '<tr><td colspan="5" class="muted">Nothing on this inquiry depends on a child fare yet. Enter each child\u2019s age above and the contracts are read again.</td></tr>')
                  + '</tbody></table></div>'
                  + (REVIEW && REVIEW.complete === false ? '<div class="note mt-2">Some items are still to be decided; a quotation cannot be issued until every one of them is.</div>' : ''))

            // ---- the quotation
            + '<h6 class="mt-4 mb-2">Quotation</h6>'
            + (ro ? '' : '<div class="acts mb-3"><button class="btn-go" id="b_go" type="button">Go for Quotation</button></div>'
                + '<div class="muted mb-2">Go for Quotation checks the inquiry and, when nothing is missing, freezes a draft version. Nothing is sent by it.</div>')
            + '<div id="go_out"></div>'
            + '<div class="wrap"><table class="tbl"><thead><tr><th style="width:80px">Version</th><th style="width:120px">Status</th><th style="width:130px">Client price</th><th style="width:120px">Max pax</th><th>Issued</th><th style="width:260px"></th></tr></thead><tbody>'
            + quotes.map(q => '<tr data-q="' + q.id + '"><td class="mono">v' + esc(q.version) + '</td>'
                + '<td>' + (q.status === 'issued' ? '<span class="pill pill-green">issued</span>' : (q.status === 'draft' ? '<span class="pill pill-amber">draft</span>' : '<span class="pill pill-plum">' + esc(q.status) + '</span>'))
                +     (Number(q.review_required) === 1 ? '<div class="muted small">staff review required</div>' : '') + '</td>'
                + '<td class="mono">' + money(q.sell_usd) + '</td>'
                + '<td><input class="form-control sm mono" data-k="max_pax" value="' + esc(q.max_pax === null || q.max_pax === undefined ? '' : q.max_pax) + '"' + (ro ? ' readonly' : '') + '></td>'
                + '<td class="muted small">' + esc(q.issued_at ? (q.issued_at + ' by ' + (q.issued_by || '')) : (q.created_at || '')) + '</td>'
                + '<td><div class="acts">'
                +     '<button class="btn-quiet btn-xs" data-act="view" type="button">View</button>'
                +     '<button class="btn-quiet btn-xs" data-act="pdf" type="button">PDF</button>'
                +     (ro ? '' : (q.status === 'draft' ? (CAN_ISSUE ? '<button class="btn-quiet btn-xs" data-act="issue" type="button">Issue</button>' : '')+'<button class="btn-red btn-xs" data-act="discard" type="button">Discard</button>' : ''))
                +     (ro ? '' : '<button class="btn-quiet btn-xs" data-act="maxpax" type="button">Save max</button><button class="btn-quiet btn-xs" data-act="mail" type="button">E-mail it</button>')
                +     '</div></td></tr>').join('')
            + (quotes.length ? '' : '<tr><td colspan="6" class="muted">No quotation version on this inquiry yet.</td></tr>')
            + '</tbody></table></div>'
            + '<div id="mail_out" class="mt-3"></div>'

            // ---- confirming the tour
            // ---- the itinerary and the other documents, by e-mail
            + '<h6 class="mt-4 mb-2">E-mail the itinerary or another document</h6>'
            + '<div class="muted mb-2">The same Communications e-mail the classic panel sends: from your own mailbox, with the company copy rule and the existing signature, and whatever goes out is kept on this inquiry\u2019s record.</div>'
            + '<button class="btn-ghost" id="b_doc_mail" type="button">Prepare an e-mail</button>'
            + '<div id="doc_out" class="mt-3"></div>'

            + '<h6 class="mt-4 mb-2">Confirm the tour</h6>'
            + (ro
                ? '<div class="muted">This inquiry is already a confirmed tour.</div>'
                : '<div class="muted mb-2">When the agent says yes, the tour is numbered and released to the Hotel and Transport desks. The hotels\u2019 booking requests go with it exactly as you set them below, and each restaurant of the Meal Master is booked and its voucher issued. Your own password is asked for first \u2014 you type it, it is checked once, and it is never stored.</div>'
                  + '<button class="btn-go btn-confirm" id="b_confirm" type="button">Confirm the tour\u2026</button>'
                  + '<div id="conf_out" class="mt-3"></div>')
            + foot() + '</div></div>';

        wireFoot();

        if ($('mk_save')) { $('mk_save').addEventListener('click', () => save('/intake/' + IT.id + '/markup', { pct: $('mk_pct').value.trim(), note: $('mk_note').value.trim() })); }
        if ($('b_recalc')) { $('b_recalc').addEventListener('click', () => ask($('b_recalc'), 'The saved figures are worked out again from today\u2019s masters. Every change is logged.', 'Yes \u2014 recalculate', () => save('/intake/' + IT.id + '/recalculate', {}))); }
        if ($('b_prev_int')) { $('b_prev_int').addEventListener('click', () => window.open(BASE + '/intake/' + IT.id + '/preview?view=internal', '_blank')); }
        if ($('b_prev_cli')) { $('b_prev_cli').addEventListener('click', () => window.open(BASE + '/intake/' + IT.id + '/preview?view=client', '_blank')); }

        if ($('kid_save')) { $('kid_save').addEventListener('click', async () => {
            const kids = [];
            document.querySelectorAll('[data-kid]').forEach(tr => {
                const a = tr.querySelector('[data-k="age"]'), b = tr.querySelector('[data-k="bed"]');
                kids.push({ age: a && a.value !== '' ? Number(a.value) : null, bed: b ? b.value : 'no_bed' });
            });
            if (kids.some(k => k.age === null)) { err('Give every child\u2019s age: the contracts charge by age.'); return; }
            const j = await save('/intake/' + IT.id + '/children', { children: kids }, { render: false });
            if (j && j.data && j.data.review) { REVIEW = j.data.review; }
            await loadCosting(); render();
        }); }

        document.querySelectorAll('[data-cf]').forEach(tr => {
            const key = tr.dataset.cf;
            const g = k => { const el = tr.querySelector('[data-k="' + k + '"]'); return el ? el.value : ''; };
            const y = tr.querySelector('[data-act="yes"]'), n = tr.querySelector('[data-act="no"]');
            const put = async dec => {
                // "No charge" has to say why — the rule is the application's; the panel asks for it
                // here rather than letting the press fail on the server for an empty reason.
                if (dec === 'not_applicable' && g('reason_code') === '' && g('reason').trim() === '') {
                    err('Say why no child fare applies: choose a reason, or type one.');
                    const sel = tr.querySelector('[data-k="reason_code"]');
                    if (sel) { sel.focus(); }
                    return;
                }
                const j = await save('/intake/' + IT.id + '/child_review', { key: key, decision: dec, reason_code: g('reason_code'), reason: g('reason') }, { render: false });
                if (j && j.data && j.data.review) { REVIEW = j.data.review; }
                await loadCosting(); render();
            };
            if (y) { y.addEventListener('click', () => put('applicable')); }
            if (n) { n.addEventListener('click', () => put('not_applicable')); }
        });

        if ($('b_go')) { $('b_go').addEventListener('click', async () => {
            err('');
            const j = await post('/intake/' + IT.id + '/go', {});
            if (!j) { err('Go for Quotation did not reach the server.'); return; }
            if (j.data && j.data.id) { IT = j.data; }
            await loadCosting();
            const blockers = j.blockers || [];
            render();
            if ($('go_out')) {
                $('go_out').innerHTML = j.ok
                    ? '<div class="note" style="background:#e4f4ea;color:#1f8a4c">' + esc(j.message || 'A draft version is ready.') + '</div>'
                    : '<div class="err">' + esc(j.message || '') + '<ul class="mt-2 mb-0">' + blockers.map(b => '<li>' + esc(typeof b === 'string' ? b : (b.label || b.text || b.why || b.message || '')) + '</li>').join('') + '</ul></div>';
            }
        }); }

        if ($('b_doc_mail')) { $('b_doc_mail').addEventListener('click', docMailBox); }
        if ($('b_confirm'))  { $('b_confirm').addEventListener('click', confirmBox); }

        wireQuotes();
    }

    // ---------------------------------------------------------------- the itinerary and other documents by e-mail
    // The Communications e-mail the classic panel already sends: the same draft, the same signature,
    // the same company copy rule, the same attachments (the quotation / itinerary PDF among them), and
    // the same record. Nothing here composes or sends anything of its own.
    async function docMailBox(group) {
        err('');
        const j = await api('/comms/' + IT.id + '/compose' + (group ? '?group=' + encodeURIComponent(group) : ''));
        if (!j || j.status !== 200) { err((j && j.message) || 'The e-mail could not be prepared.'); return; }
        const d = j.data || {}, def = d.defaults || {}, docs = d.documents || [], snd = d.sender || {};
        const tpls = d.templates || [], groups = d.groups || {};
        const groups_ = {};
        docs.forEach(x => { (groups_[x.group] = groups_[x.group] || []).push(x); });
        const t0 = tpls.find(t => String(t.id) === String(d.template_id)) || tpls[0] || null;
        const sig = d.signature || '';
        // the message the window starts with: the saved wording, filled from this tour. Editing it here
        // is this one e-mail's own copy — the saved template is only changed on Master Files.
        // the company signature is added by the mail itself when it goes, so it is NOT typed into the box:
        // putting it here too would sign every letter twice. The preview shows it below the message.
        const startBody = t0
            ? (t0.message || '')
            : (def.greeting || '') + (def.quoted ? '\n\n' + def.quoted : '');
        $('doc_out').innerHTML = '<div class="card2"><div class="hd"><h5>E-mail from this inquiry</h5></div><div class="bd">'
            + '<div class="muted mb-2">From ' + esc(snd.email || '') + (snd.name ? ' (' + esc(snd.name) + ')' : '')
            + (snd.can_send === false ? ' — this mailbox is not set up to send yet, so Send will say so and keep the attempt on the record.' : '') + '</div>'
            + ((d.always_cc || []).length ? '<div class="muted mb-2">A copy always goes to ' + esc((d.always_cc || []).join(', ')) + ', as the company setting says.</div>' : '')
            + (d.stage_note ? '<div class="note mb-2">' + esc(d.stage_note) + '</div>' : '')
            + '<div class="row g-2">'
            + '<div class="col-md-6"><label class="lb">Which e-mail this is</label><select class="form-select" id="dm_grp">'
            +   Object.keys(groups).map(g => '<option value="' + esc(g) + '"' + (g === d.group ? ' selected' : '') + '>' + esc(groups[g]) + '</option>').join('')
            + '</select></div>'
            + '<div class="col-md-6"><label class="lb">Saved wording</label>'
            +   (tpls.length
                    ? '<select class="form-select" id="dm_tpl">' + tpls.map(t => '<option value="' + esc(t.id) + '"' + (t0 && String(t.id) === String(t0.id) ? ' selected' : '') + '>' + esc(t.name) + (t.is_default ? ' (starts this group)' : '') + '</option>').join('') + '</select>'
                    : '<div class="muted">No saved wording for this group yet.</div>')
            +   (d.can_manage_templates ? '<div class="muted small mt-1"><a href="' + esc(d.templates_url || '') + '" target="_blank">Manage templates</a> — changing the saved wording there never changes an e-mail already sent.</div>' : '')
            + '</div>'
            + '<div class="col-md-6"><label class="lb">To</label><input class="form-control" id="dm_to" value="' + esc(def.to || '') + '"></div>'
            + '<div class="col-md-6"><label class="lb">CC</label><input class="form-control" id="dm_cc" value="' + esc(def.cc || '') + '"></div>'
            + '<div class="col-md-12"><label class="lb">Subject</label><input class="form-control" id="dm_subj" value="' + esc((t0 && t0.subject) || def.subject || '') + '"></div>'
            + '<div class="col-md-12"><label class="lb">Message <span class="muted">(edit it for this e-mail as you like — the saved template is not touched; the company signature is added underneath when it goes)</span></label>'
            +   '<textarea class="form-control" id="dm_body" rows="14">' + esc(startBody) + '</textarea></div>'
            + '</div>'
            + '<div id="dm_miss" class="mt-2"></div>'
            + '<h6 class="mt-3 mb-2" style="font-size:13px">Attach</h6>'
            + (docs.length
                ? Object.keys(groups_).map(g => '<div class="mb-2"><div class="muted small mb-1">' + esc(g) + '</div>'
                    + groups_[g].map(x => '<div class="small d-flex align-items-center gap-2"><label style="cursor:pointer;flex:1">'
                        + '<input type="checkbox" class="dm_doc" value="' + esc(x.key) + '"> ' + esc(x.label) + '</label>'
                        + '<button class="btn-quiet btn-xs" type="button" data-see="' + esc(x.key) + '">See it</button></div>').join('')
                    + '</div>').join('')
                : '<div class="muted">No document is on this inquiry yet. Issue a quotation version and its itinerary PDF appears here; an invoice raised on this inquiry’s tour appears here too.</div>')
            + '<div class="acts mt-3"><button class="btn-quiet" id="dm_check" type="button">Check what would go</button>'
            + '<button class="btn-go" id="dm_send" type="button">Send now</button>'
            + '<button class="btn-quiet" id="dm_close" type="button">Close</button></div>'
            + '<div id="dm_out" class="mt-3"></div></div></div>';
        $('doc_out').scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        const picked = () => Array.from(document.querySelectorAll('.dm_doc')).filter(c => c.checked);
        const body = () => ({ to: $('dm_to').value.trim(), cc: $('dm_cc').value.trim(), subject: $('dm_subj').value.trim(),
                              body: $('dm_body').value, docs: picked().map(c => c.value), tag: 'guided' });
        let CHECKED = false;   // Send waits until the letter has been looked at and nothing is blocking it
        const dirty = () => { CHECKED = false; };
        ['dm_to', 'dm_cc', 'dm_subj', 'dm_body'].forEach(i => { if ($(i)) { $(i).addEventListener('input', dirty); } });
        document.querySelectorAll('.dm_doc').forEach(c => c.addEventListener('change', dirty));

        $('dm_close').addEventListener('click', () => { err(''); $('doc_out').innerHTML = ''; });
        // the attachment itself, opened exactly as it would be attached — nothing is sent by looking
        document.querySelectorAll('[data-see]').forEach(b => b.addEventListener('click', () => {
            window.open(BASE + '/comms/' + IT.id + '/doc?key=' + encodeURIComponent(b.dataset.see), '_blank');
        }));

        // choosing another group of wording draws the box again with that group's templates;
        // the address and the ticked documents are kept, because only the wording changed
        if ($('dm_grp')) { $('dm_grp').addEventListener('change', async e => {
            const keepTo = $('dm_to').value, keepCc = $('dm_cc').value, ticked = picked().map(c => c.value);
            await docMailBox(e.target.value);
            if ($('dm_to')) { $('dm_to').value = keepTo; $('dm_cc').value = keepCc; }
            document.querySelectorAll('.dm_doc').forEach(c => { if (ticked.indexOf(c.value) >= 0) { c.checked = true; } });
        }); }

        if ($('dm_tpl')) { $('dm_tpl').addEventListener('change', e => {
            const t = tpls.find(x => String(x.id) === String(e.target.value));
            if (!t) { return; }
            $('dm_subj').value = t.subject || '';
            $('dm_body').value = (t.message || '');
            CHECKED = false;
            showMissing(t);
            ok('The ' + (t.name || 'saved') + ' wording is in the message, with this tour’s own details filled in. Edit it for this e-mail as you like.');
        }); }

        // what the tour could not fill, named on the screen rather than guessed
        function showMissing(t) {
            const m = (t && t.missing) || [];
            $('dm_miss').innerHTML = m.length
                ? '<div class="note">This wording asks for ' + esc(m.join(', ')) + ', and the record has none. Put it on the inquiry, or take those words out of the message before sending.</div>'
                : '';
        }
        showMissing(t0);

        $('dm_check').addEventListener('click', async () => {
            err('');
            const p = await post('/comms/' + IT.id + '/preview', body());
            if (!p || p.status !== 200) { err((p && p.message) || 'The preview was refused.'); return; }
            const x = p.data || {};
            CHECKED = !!x.can_send;
            const line = (lbl, val) => '<div style="display:flex;gap:10px;padding:5px 0;border-bottom:1px solid var(--gi-line)">'
                + '<div style="width:86px;flex:0 0 86px;font-size:12px;font-weight:700;color:#5f6f80">' + esc(lbl) + '</div>'
                + '<div style="flex:1;font-size:13px">' + val + '</div></div>';
            $('dm_out').innerHTML =
                  ((x.stops || []).length ? '<div class="err mb-2"><b>This cannot be sent yet:</b><ul class="mt-2 mb-0">' + x.stops.map(w => '<li>' + esc(w) + '</li>').join('') + '</ul></div>' : '')
                + ((x.warnings || []).length ? '<div class="note mb-2"><ul class="mb-0">' + x.warnings.map(w => '<li>' + esc(w) + '</li>').join('') + '</ul></div>' : '')
                + '<div style="border:1px solid var(--gi-line);border-radius:10px;background:#fff;overflow:hidden">'
                + '<div style="background:#f4f6f9;padding:8px 14px;font-size:12px;font-weight:700;color:#1e3553;letter-spacing:.04em;text-transform:uppercase">Exactly what would go — nothing has been sent</div>'
                + '<div style="padding:10px 14px">'
                +   line('From', esc(x.from || ''))
                +   line('To', esc(x.to || '') || '<span class="muted">nobody yet</span>')
                +   line('CC', (x.cc ? esc(x.cc) : '') + ((x.always_cc || []).length ? (x.cc ? ' · ' : '') + '<span class="muted">' + esc((x.always_cc || []).join(', ')) + ' (the company copy, added by the system)</span>' : (x.cc ? '' : '<span class="muted">nobody</span>')))
                +   line('Subject', esc(x.subject || '') || '<span class="muted">none</span>')
                + '</div>'
                + '<div style="padding:12px 14px;border-top:1px solid var(--gi-line);white-space:pre-wrap;font-size:13px;line-height:1.65">' + esc(x.message || '') + '</div>'
                + (x.signature ? '<div style="padding:12px 14px;border-top:1px dashed var(--gi-line);white-space:pre-wrap;font-size:12.5px;line-height:1.6;color:#5f6f80"><div style="font-size:11.5px;font-weight:700;letter-spacing:.04em;margin-bottom:6px">SIGNATURE (added by the system)</div>' + esc(x.signature) + '</div>' : '')
                + ((x.attachments || []).length
                    ? '<div style="padding:10px 14px;border-top:1px solid var(--gi-line)"><div style="font-size:12px;font-weight:700;color:#5f6f80;margin-bottom:6px">ATTACHED (' + x.attachments.length + ')</div>'
                      + x.attachments.map(a => '<div class="small d-flex align-items-center gap-2" style="padding:2px 0"><div style="flex:1">' + esc(a.label) + ' <span class="muted">· ' + esc(a.group) + '</span></div>'
                        + '<a class="btn-quiet btn-xs" href="' + esc(a.open) + '" target="_blank">Open it</a></div>').join('')
                      + '</div>'
                    : '<div style="padding:10px 14px;border-top:1px solid var(--gi-line)" class="muted small">Nothing attached.</div>')
                + '</div>'
                + '<div class="muted mt-2">Nothing has been sent. ' + (x.can_send ? 'Press Send now to send it.' : 'Send is held until the points above are put right.') + '</div>';
            if (x.can_send) { ok('This is exactly what would go. Nothing has been sent.'); }
            else { err('The letter is not ready to send yet — the preview says what is missing.'); }
        });
        $('dm_send').addEventListener('click', async () => {
            const b0 = body();
            if (!b0.to) { err('Type the address it goes to.'); return; }
            if (!CHECKED) {
                // never send a letter nobody has looked at: the check runs first and holds on anything wrong
                const p = await post('/comms/' + IT.id + '/preview', b0);
                if (!p || p.status !== 200 || !(p.data || {}).can_send) {
                    $('dm_check').click();
                    return;
                }
                CHECKED = true;
            }
            ask($('dm_send'), 'It goes to ' + b0.to + ' from your own mailbox, and is kept on this inquiry.', 'Yes — send it now', send_);
        });
        async function send_() {
            const b = body();
            if (!b.to) { err('Type the address it goes to.'); return; }
            const r = await post('/comms/' + IT.id + '/send', b);
            if (!r || r.status !== 200 || r.ok === false) { err((r && r.message) || 'The e-mail was not sent. The attempt is kept on the record.'); return; }
            ok(r.message || 'Sent.');
            $('doc_out').innerHTML = '';
            reopen();
        }
    }

    // ---------------------------------------------------------------- Confirm Tour, with the booking requests
    // The same three endpoints the classic panel uses: the mail plan is read from mail_preview, the
    // signed-in person's own password is checked by reauth, and confirm numbers the tour, releases it to
    // the desks, sends the hotel requests that were ticked and books each Meal Master restaurant with its
    // voucher. The password is typed by the person, sent once, cleared from the screen, never stored here.
    async function confirmBox() {
        err('');
        const j = await api('/intake/' + IT.id + '/mail_preview');
        if (!j || j.status !== 200) { err((j && j.message) || 'The booking requests could not be read.'); return; }
        const d = j.data || {}, stays = d.stays || [], snd = d.sender || {}, crew = d.crew || {};
        $('conf_out').innerHTML = '<div class="card2"><div class="hd"><h5>Confirm the tour</h5></div><div class="bd">'
            + '<div class="muted mb-2">From ' + esc(snd.email || '')
            + (snd.can_send === false ? ' — this mailbox is not set up to send yet. The tour is still confirmed; each request that cannot go is kept on its stay with the reason, and shows under "Mails not sent".' : '') + '</div>'
            + '<h6 class="mb-2" style="font-size:13px">The hotels’ booking requests</h6>'
            + (stays.length
                ? '<div class="wrap"><table class="tbl"><thead><tr><th style="width:70px">Send</th><th>Hotel</th><th style="width:150px">Nights</th><th>Goes to</th><th style="width:170px">Driver / guide room</th></tr></thead><tbody>'
                  + stays.map((s, i) => '<tr data-stay="' + i + '" data-day="' + s.day_from + '">'
                    + '<td><input type="checkbox" class="st_send"' + (s.email && !s.already && !s.needs_hotel && !s.needs_meal ? ' checked' : '') + (s.email ? '' : ' disabled') + '></td>'
                    + '<td>' + esc(s.hotel_name || '') + '<div class="muted small">' + esc(s.rooms_line || s.rooms_note || '') + (s.meal_plan ? ' · ' + esc(s.meal_plan) : '') + '</div></td>'
                    + '<td class="muted small">' + esc(d10(s.check_in)) + ' → ' + esc(d10(s.check_out)) + ' (' + esc(s.nights) + ')</td>'
                    + '<td><input class="form-control sm st_email" value="' + esc(s.email || '') + '" placeholder="no address on the master">'
                    +   '<div class="muted small mt-1">' + esc(s.already ? 'requested already — not sent again' : (s.email ? ('from the ' + (s.email_source || 'record')) : 'nothing can be sent without an address')) + '</div></td>'
                    + '<td><label class="small d-block"><input type="checkbox" class="st_driver"' + (s.driver ? ' checked' : '') + '> driver</label>'
                    +   '<label class="small d-block"><input type="checkbox" class="st_guide"' + (s.guide ? ' checked' : '') + '> guide</label></td></tr>').join('')
                  + '</tbody></table></div>'
                : '<div class="muted">No hotel stay on this tour, so there is no booking request to send.</div>')
            + '<div class="row g-2 mt-2"><div class="col-md-6"><label class="lb">Copy the requests to</label>'
            +   '<input class="form-control" id="cf_cc" value="' + esc(d.cc || '') + '" placeholder="optional"></div></div>'
            + '<div class="muted mt-2">Each restaurant of the Meal Master on this tour is booked when it is confirmed, and its voucher issued and e-mailed. A request or voucher that cannot go is kept with its reason and nothing else is held up.</div>'
            + '<h6 class="mt-3 mb-2" style="font-size:13px">Your password</h6>'
            + '<div class="row g-2 align-items-end"><div class="col-md-5">'
            +   '<input type="password" class="form-control" id="cf_pass" autocomplete="off" placeholder="type your own password"></div>'
            + '<div class="col-md-7 acts"><button class="btn-go" id="cf_check" type="button">Check my password</button>'
            +   '<button class="btn-go btn-confirm" id="cf_go" type="button" disabled>Check your password first</button>'
            +   '<button class="btn-quiet" id="cf_close" type="button">Close</button></div></div>'
            + '<div id="cf_msg" class="mt-2" style="display:none;font-size:13px;font-weight:600"></div>'
            + '<div class="muted mt-2">It is checked once and never stored. Confirming cannot be undone from here: the inquiry becomes read-only and the tour goes to the desks.</div>'
            + '<div id="cf_out" class="mt-3"></div></div></div>';
        $('conf_out').scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // tf-confirmux-r1: what the box has to say is said in the box, under the buttons — the page's own
        // banner sits at the very top and scrolls the person away from the button they just pressed
        const cmsg = (t, bad) => {
            const m = $('cf_msg');
            if (!m) { if (bad) { err(t); } return; }
            if (!t) { m.style.display = 'none'; m.textContent = ''; return; }
            m.textContent = t; m.style.color = bad ? '#8a1c30' : '#2f7d32'; m.style.display = '';
        };

        const mailPlan = () => ({
            cc: $('cf_cc').value.trim(),
            stays: Array.from(document.querySelectorAll('[data-stay]')).map(tr => ({
                day_from: Number(tr.dataset.day),
                send: tr.querySelector('.st_send').checked ? 1 : 0,
                email: tr.querySelector('.st_email').value.trim(),
                driver: tr.querySelector('.st_driver').checked ? 1 : 0,
                guide: tr.querySelector('.st_guide').checked ? 1 : 0,
            })),
        });
        const clearPass = () => { const p = $('cf_pass'); if (p) { p.value = ''; } };

        $('cf_close').addEventListener('click', () => { clearPass(); err(''); $('conf_out').innerHTML = ''; });
        $('cf_pass').focus();
        const runCheck = async () => {
            err(''); cmsg('');
            const p = $('cf_pass').value;
            if (!p) { cmsg('Type your own password in the box on the left, then press Check my password.', true); $('cf_pass').focus(); return; }
            cmsg('Checking\u2026');
            const r = await post('/intake/' + IT.id + '/reauth', { password: p });
            if (!r || r.ok !== true) {
                $('cf_go').disabled = true; $('cf_go').textContent = 'Check your password first';
                cmsg((r && r.message) || 'That password was not accepted.', true); $('cf_pass').focus(); return;
            }
            $('cf_go').disabled = false; $('cf_go').textContent = 'Confirm the tour';
            cmsg(r.message ? r.message + ' Press Confirm the tour when you are ready.' : 'Password confirmed. Press Confirm the tour when you are ready.');
            ok(r.message || 'Checked. Confirm the tour when you are ready.');
        };
        $('cf_check').addEventListener('click', runCheck);
        // 2. Enter in the password box does the check, instead of nothing
        $('cf_pass').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); runCheck(); } });
        $('cf_go').addEventListener('click', async () => {
            err(''); cmsg('');
            // tf-confirmux-r1: no second "are you sure" box. The person has read the list above and typed their own
            // password; a browser dialog on top of that is one more thing to understand, and a browser that suppresses
            // dialogs (Edge does, once someone ticks "don't let this page create more dialogs") made the button look dead.
            const plan = mailPlan();
            const p = $('cf_pass').value;
            const go = $('cf_go');
            go.disabled = true; go.textContent = 'Confirming\u2026'; cmsg('Confirming the tour\u2026');
            const r = await post('/intake/' + IT.id + '/confirm', { password: p, mail: plan });
            clearPass();
            go.textContent = 'Confirm the tour';
            if (!r || r.status !== 200) {
                go.disabled = false;
                cmsg((r && r.message) || 'The tour was not confirmed. Nothing was changed.', true);
                return;
            }
            ok(r.message || 'Confirmed.');
            const rows = (x, label) => (x || []).length
                ? '<div class="muted small mt-2">' + label + ': ' + (x || []).map(m => esc((m.hotel || m.restaurant || '') + ' — ' + m.status + (m.why ? ' (' + m.why + ')' : ''))).join('; ') + '</div>' : '';
            cmsg('');
            $('cf_out').innerHTML = '<div class="note" style="background:#e4f4ea;color:#1f8a4c">' + esc(r.message || '') + '</div>'
                + rows(r.hotel_mails, 'Hotel requests') + rows(r.meal_bookings, 'Restaurant vouchers')
                // tf-confirmux-r1: the next step, where the work now is, instead of leaving the person to find it
                + '<div class="acts mt-3"><a class="btn-go" style="text-decoration:none;display:inline-block" href="'
                + BASE + '/confirmed">Open the Confirmed Tour panel</a></div>';
            if (r.data && r.data.id) { IT = r.data; }
            await loadCosting(); render();
        });
    }

    // ---------------------------------------------------------------- the quotation versions
    function wireQuotes() {
        const id = IT.id;
        document.querySelectorAll('[data-q]').forEach(tr => {
            const q = tr.dataset.q;
            const get = k => { const el = tr.querySelector('[data-k="' + k + '"]'); return el ? el.value : ''; };
            const on = (act, fn) => { const b = tr.querySelector('[data-act="' + act + '"]'); if (b) { b.addEventListener('click', fn); } };
            on('view',    () => window.open(BASE + '/intake/' + id + '/quote/' + q + '?view=client', '_blank'));
            on('pdf',     () => window.open(BASE + '/intake/' + id + '/quote/' + q + '?view=client&pdf=1', '_blank'));
            on('issue',   (e) => ask(e.currentTarget, 'The version is issued and the inquiry is marked quoted.', 'Yes \u2014 issue it', () => save('/intake/' + id + '/quote/' + q + '/issue', {})));
            on('discard', (e) => ask(e.currentTarget, 'This throws the draft version away.', 'Yes \u2014 discard it', () => save('/intake/' + id + '/quote/' + q + '/discard', {})));
            on('maxpax',  () => save('/intake/' + id + '/quote/' + q + '/max_pax', { max_pax: get('max_pax') }));
            on('mail',    () => mailBox(q));
        });
    }

    // The quotation e-mail: the draft comes from the existing template, the preview is the
    // existing preview, and Send now is the existing send — from the signed-in person's own
    // mailbox, to the address they confirm here. Nothing is sent by opening this box.
    async function mailBox(qid) {
        err('');
        const j = await api('/intake/' + IT.id + '/quote/' + qid + '/email');
        if (!j || j.status !== 200) { err((j && j.message) || 'The quotation e-mail could not be drafted.'); return; }
        const d = j.data || {};
        const tpls = d.templates || [];
        const tpl0 = tpls.find(t => String(t.id) === String(d.template_id)) || tpls[0] || {};
        $('mail_out').innerHTML = '<div class="card2"><div class="hd"><h5>E-mail the quotation</h5></div><div class="bd">'
            + '<div class="muted mb-2">From ' + esc(d.from || '')
            + (d.can_send === false ? ' — this mailbox is not set up to send, so only the preview will work.' : '') + '</div>'
            + (d.locked ? '<div class="note">' + esc(d.locked) + '</div>' : '')
            + '<div class="row g-2">'
            + (tpls.length ? '<div class="col-md-6"><label class="lb">Template</label><select class="form-select" id="q_tpl">'
                + tpls.map(t => '<option value="' + esc(t.id) + '"' + (String(t.id) === String(tpl0.id) ? ' selected' : '') + '>' + esc(t.name) + '</option>').join('')
                + '</select></div>' : '')
            + '<div class="col-md-6"><label class="lb">Maximum travellers quoted</label><input class="form-control" value="' + esc(d.max_pax === null || d.max_pax === undefined ? 'not set' : d.max_pax) + '" readonly></div>'
            + '<div class="col-md-6"><label class="lb">To</label><input class="form-control" id="q_to" value="' + esc(d.to || '') + '">'
            + (d.to_note ? '<div class="muted small mt-1">' + esc(d.to_note) + '</div>' : '') + '</div>'
            + '<div class="col-md-6"><label class="lb">CC</label><input class="form-control" id="q_cc" value="' + esc(d.cc || '') + '"></div>'
            + '<div class="col-md-12"><label class="lb">Subject</label><input class="form-control" id="q_subj" value="' + esc(d.subject || tpl0.subject || '') + '"></div>'
            + '<div class="col-md-12"><label class="lb">Message</label><textarea class="form-control" id="q_msg" rows="9">' + esc(d.message || tpl0.message || '') + '</textarea></div>'
            + '</div>'
            + '<div class="acts mt-3"><button class="btn-quiet" id="q_prev" type="button">Preview exactly what would go</button>'
            + '<button class="btn-go" id="q_send" type="button">Send now</button>'
            + '<button class="btn-quiet" id="q_close" type="button">Close</button></div>'
            + '<div class="muted mt-2">The quotation PDF is attached by the system, with the price rows up to the version’s maximum.</div>'
            + '<div id="q_prev_out" class="mt-3"></div></div></div>';
        $('mail_out').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        const body = () => ({ to: $('q_to').value.trim(), cc: $('q_cc').value.trim(), subject: $('q_subj').value.trim(), message: $('q_msg').value });
        $('q_close').addEventListener('click', () => { err(''); $('mail_out').innerHTML = ''; });
        if ($('q_tpl')) { $('q_tpl').addEventListener('change', e => {
            const t = tpls.find(x => String(x.id) === String(e.target.value));
            if (!t) { return; }
            $('q_subj').value = t.subject || '';
            $('q_msg').value  = t.message || '';
            ok('The ' + (t.name || 'template') + ' wording is in the message.');
        }); }
        $('q_prev').addEventListener('click', async () => {
            err('');
            const p = await post('/intake/' + IT.id + '/quote/' + qid + '/email/preview', body());
            if (!p || p.status !== 200) { err((p && p.message) || 'The preview was refused.'); return; }
            const x = p.data || {};
            $('q_prev_out').innerHTML = (x.warning ? '<div class="note">' + esc(x.warning) + '</div>' : '')
                + '<div class="muted mb-1">To ' + esc(x.to || '') + (x.cc ? ', cc ' + esc(x.cc) : '') + ' · attachment ' + esc(x.attachment || '') + '</div>'
                + '<div style="border:1px solid var(--gi-line);border-radius:10px;padding:14px;background:#fff">' + (x.html || '') + '</div>';
            ok('This is exactly what would be sent. Nothing has been sent.');
        });
        $('q_send').addEventListener('click', () => {
            const b0 = body();
            ask($('q_send'), 'The quotation goes to ' + (b0.to || 'the address above') + ' from your own mailbox, and is recorded on the version.', 'Yes \u2014 send it now', qsend_);
        });
        async function qsend_() {
            const b = body();
            const s = await post('/intake/' + IT.id + '/quote/' + qid + '/email/send', b);
            if (!s || s.status !== 200) { err((s && s.message) || 'The quotation was not sent.'); return; }
            ok(s.message || 'Sent.');
            $('mail_out').innerHTML = '';
            reopen();
        }
    }

    // ---------------------------------------------------------------- render
    function render() {
        rail();
        if (STAGE === 1) { stage1(); }
        else if (STAGE === 2) { stage2(); }
        else if (STAGE === 3) { stage3(); }
        else { stage4(); }
    }

    // ---------------------------------------------------------------- start
    $('q_go').addEventListener('click', search);
    $('q').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); search(); } });
    $('b_classic').addEventListener('click', () => { window.location.href = CLASSIC; });
    $('b_new').addEventListener('click', startHand);
    $('b_start_mail').addEventListener('click', startEmail);
    $('b_start_hand').addEventListener('click', startHand);
    // an address with an id that cannot be opened must not leave an empty box: the list comes up,
    // under the reason, so the person can simply pick another inquiry
    if (OPEN_ID) {
        open_(OPEN_ID).then(async ok => {
            if (ok) { return; }
            const why = $('err').textContent;   // the list clears the banner, so the reason is put back
            await search();
            if (why) { err(why); }
        });
    } else { search(); }
})();
</script>
</body>
</html>
