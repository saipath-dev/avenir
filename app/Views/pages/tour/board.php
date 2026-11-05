<?= view('layouts/head') ?>
<style>
    /* ---------- Tour Board (same tokens as the Tour Inquiry panel; the Tour Desk bar re-points them) ---------- */
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
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl th.lq { color:#842484; } .tf .tbl th.lh { color:#1878c0; } .tf .tbl th.lt { color:#b8640f; }
    .tf .tbl td { padding:10px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr:last-child td { border-bottom:0; }
    .tf .tbl tr.trow { cursor:pointer; }
    .tf .tbl tr.trow:hover td { background:var(--tf-bg); }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .pill-amber { background:var(--tf-amber-soft); color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-teal { background:var(--tf-teal-soft); color:var(--tf-teal); }
    .tf .chips { display:flex; gap:6px; flex-wrap:wrap; }
    .tf .chip { border:1px solid var(--tf-line); background:#fff; border-radius:9px; padding:5px 11px; font-size:12px; font-weight:500; cursor:pointer; color:var(--tf-ink); }
    .tf .chip .n { font-family:ui-monospace, Menlo, monospace; font-size:11px; color:var(--tf-muted); margin-left:4px; }
    .tf .chip.on { background:var(--tf-blue-soft); border-color:transparent; color:var(--tf-navy); font-weight:600; }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:14px 0; text-align:center; }
    .tf .bar { height:6px; border-radius:3px; background:#eef1f5; margin-top:6px; overflow:hidden; max-width:180px; }
    .tf .bar i { display:block; height:100%; background:#1878c0; }
    .tf .bar.t i { background:#b8640f; }
    .tf .money { font-family:ui-monospace, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .late { display:block; font-size:11.5px; color:var(--tf-amber); font-weight:600; margin-top:3px; }
    .tf .stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
    .tf .stat { background:#fff; border:1px solid var(--tf-line); border-radius:10px; padding:10px 14px; min-width:130px; }
    .tf .stat b { display:block; font-size:20px; font-weight:600; line-height:1.1; }
    .tf .stat span { font-size:11.5px; color:var(--tf-muted); }
    .tf .box { border:1px solid var(--tf-line); border-radius:8px; padding:11px 13px; margin-bottom:12px; }
    .tf .box h6 { font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-muted); font-weight:700; margin:0 0 8px; }
    .tf .trail { font-size:12px; max-height:360px; overflow:auto; }
    .tf .trail div { padding:5px 0; border-bottom:1px dashed var(--tf-line); }
    .tf .trail div:last-child { border-bottom:0; }
    .tf .trail .who { color:var(--tf-muted); }
    .tf .lane { font-size:10.5px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; margin-right:6px; }
    .tf .lane.hotel { color:#1878c0; } .tf .lane.transport { color:#b8640f; } .tf .lane.quotation { color:#842484; } .tf .lane.tour { color:var(--tf-ink); } .tf .lane.service { color:#0f8b7e; }
    .tf .kv { display:grid; grid-template-columns:120px 1fr; gap:5px 12px; font-size:12.5px; }
    .tf .kv dt { color:var(--tf-muted); }
    .tf .kv dd { margin:0; }
    .modal .modal-content { border-radius:12px; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>
                <?= view('layouts/tourdesk_menu') ?>

                <div class="tf-title">
                    <div>
                        <h4>Tour Board</h4>
                        <div class="sub">Every confirmed tour, three lanes, nearest arrival first. Money appears here and nowhere else on the desks. A row opens the tour's full trail — every card, every move, who and when — and the buttons only Management holds: un-confirm, amend, cancel, reopen.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Desk tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Inquiries</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div class="stats" id="stats"></div>

                <div class="tf-card">
                    <div class="tf-card-h">
                        <div class="chips" id="chips"></div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <select class="form-select form-select-sm" id="f_handler" style="width:auto;max-width:190px" title="Handling agent — whose tour it is">
                                <option value="">Any handling agent</option>
                                <?php foreach ((array) ($handlers ?? []) as $h): ?>
                                <option value="<?= esc($h['id'] !== null ? (string) $h['id'] : (string) $h['name']) ?>"><?= esc($h['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input class="form-control form-control-sm" id="f_q" placeholder="Tour, agent, group…" style="width:200px">
                            <input type="date" class="form-control form-control-sm" id="f_from" style="width:auto" title="Arriving from">
                            <input type="date" class="form-control form-control-sm" id="f_to" style="width:auto" title="Arriving to">
                            <button class="btn btn-light btn-sm" id="f_clear" type="button" title="Clear every filter"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="tbl">
                            <thead><tr><th>Tour</th><th class="lq" style="width:200px">Quotation</th><th class="lh" style="width:210px">Hotels</th><th class="lt" style="width:210px">Transport</th><th style="width:90px">Stage</th></tr></thead>
                            <tbody id="rows"><tr><td colspan="5" class="empty">Loading…</td></tr></tbody>
                        </table>
                    </div>
                </div>

                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<div class="modal fade" id="m_tour" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="t_title">Tour</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="t_body"></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'tour/board') ?>';
    const INTAKE = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const HOTELS = '<?= base_url('tour/hotel_bookings') ?>';
    const TRANSPORT = '<?= base_url('tour/transport_ops') ?>';
    // where each item of a tour's strip opens: the desk that does that work, on this tour
    // tf-walkfix-r1: only the desks this sign-in may open are links; the rest of the chips stay labels
    const CAN = { hotel: <?= !empty($can_hotel) ? 'true' : 'false' ?>, transport: <?= !empty($can_transport) ? 'true' : 'false' ?>, intake: <?= !empty($can_intake) ? 'true' : 'false' ?> };
    const deskLinks = id => ({ hotels: CAN.hotel ? `${HOTELS}?tour=${id}` : null, activities: CAN.hotel ? `${HOTELS}?tour=${id}` : null, vehicle: CAN.transport ? `${TRANSPORT}?tour=${id}` : null, driver: CAN.transport ? `${TRANSPORT}?tour=${id}` : null, guide: CAN.transport ? `${TRANSPORT}?tour=${id}` : null });
    // back from a desk (the tab shown again): the board is read again, so what was saved there shows here
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible' && typeof load === 'function') load(); });
    window.addEventListener('pageshow', e => { if (e.persisted && typeof load === 'function') load(); });
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const CAN_MANAGE = <?= !empty($can_manage) ? 'true' : 'false' ?>;
    const MANAGE_BASE = '<?= base_url($manage_url ?? 'tour/manage') ?>';
    const HANDLERS = <?= json_encode(array_values((array) ($handlers ?? [])), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (!INSTALLED) return;
    const NAMES = Object.fromEntries(HANDLERS.filter(h => h.username).map(h => [h.username, h.name]));
    const staffName = u => (u && NAMES[u]) || u || '';

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2800 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With':'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const manage = (path, body) => fetch(MANAGE_BASE + path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) }).then(r => r.json());
    const fmt = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short' }); };
    const fmtY = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); };
    const when = s => s ? String(s).slice(0, 16) : '';
    const ME_NAME = <?= json_encode((string) ($me_name ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    // The signed-in person's password, checked on the server before an action that cannot be taken back (cancel,
    // delete). Returns the password to send with the action, or null when the person stepped back or the check
    // failed — nothing is changed on a failure, and the wrong attempt is on the Activity log.
    async function askPassword(title, text, path, sender, purpose) {
        const pw = await Swal.fire({ title: title + ' — your password', html: `<div style="text-align:left;font-size:13.5px">${esc(text)}</div>`, input: 'password', inputPlaceholder: 'Your password', inputAttributes: { autocomplete: 'current-password' }, inputValidator: v => (v ? undefined : 'Enter your password.'), showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#b23a32' });
        if (!pw.isConfirmed) return null;
        const a = await sender(path, { password: pw.value || '', purpose: purpose || title });
        if (a.status !== 200 || !a.ok) { await Swal.fire({ icon: 'error', title: 'Not done', text: a.message || 'Password not recognised. Nothing was changed.' }); return null; }
        return pw.value || '';
    }
    const lkr = v => v == null ? '—' : 'LKR ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const TL = { to_allocate:'To allocate', allocated:'Allocated', scheduled:'Scheduled', pack_issued:'Pack issued', running:'Running', returned:'Returned', settled:'Settled', cancelled:'Cancelled' };
    const HL = { to_book:'To book', requested:'Requested', confirmed:'Confirmed', voucher_issued:'Voucher issued', problem:'Problem', cancelled:'Cancelled' };
    const STAGE = { confirmed:['Confirmed','pill-blue'], operating:['Operating','pill-teal'], completed:['Completed','pill-green'], closed:['Closed','pill-green'], cancelled:['CANCELLED','pill-red'] };
    const CHIPS = [['live', 'Operating'], ['arriving', 'Arriving this week'], ['problems', 'Problems'], ['completed', 'Completed'], ['cancelled', 'Cancelled'], ['all', 'All']];
    let FILTER = 'live', TOUR = null;

    const stagePill = s => { const [l, c] = STAGE[s] || [s, 'pill-grey']; return `<span class="pill ${c}">${esc(l)}</span>`; };

    function renderChips() {
        document.getElementById('chips').innerHTML = CHIPS.map(([k, l]) => `<span class="chip ${FILTER === k ? 'on' : ''}" data-chip="${k}">${l}</span>`).join('');
        document.querySelectorAll('[data-chip]').forEach(c => c.addEventListener('click', () => { FILTER = c.dataset.chip; load(); }));
    }

    async function load() {
        renderChips();
        const j = await api('/data?' + new URLSearchParams({ which: FILTER, q: document.getElementById('f_q').value || '', handler: document.getElementById('f_handler').value || '', from: document.getElementById('f_from').value || '', to: document.getElementById('f_to').value || '' }).toString());
        if (j.status !== 200) { toast('error', j.message); return; }
        const tours = j.data.tours || [], inq = j.data.inquiries || {};
        const problems = tours.filter(t => t.problems > 0).length, late = tours.filter(t => (t.late || []).length).length;
        document.getElementById('stats').innerHTML = [
            [inq.open ?? 0, 'inquiries with the Quotation Desk', CAN.intake ? INTAKE : null],
            [tours.filter(t => ['confirmed', 'operating'].includes(t.stage)).length, 'tours confirmed or operating'],
            [tours.filter(t => t.days_to_arrival !== null && t.days_to_arrival >= 0 && t.days_to_arrival <= 7).length, 'arriving within 7 days'],
            [problems, 'with a hotel or activity problem'],
            [late, 'late against arrival'],
        ].map(([n, l, href]) => `<div class="stat"><b>${n}</b><span>${href ? `<a href="${href}">${l}</a>` : l}</span></div>`).join('');

        const tb = document.getElementById('rows');
        if (!tours.length) { tb.innerHTML = '<tr><td colspan="5" class="empty">No tours here.</td></tr>'; return; }
        tb.innerHTML = tours.map(t => {
            const h = t.lanes.hotel, tr = t.lanes.transport, q = t.lanes.quotation;
            const hp = h.total ? Math.round(100 * h.booked / h.total) : 0;
            const tp = tr.total ? Math.round(100 * tr.rung / Math.max(1, tr.of)) : 0;
            return `<tr class="trow" data-tour="${t.id}">
                <td><b>${esc(t.tour_no)}</b> · ${esc(t.tour_name || '—')}<div class="help">${esc(t.agent_name || '—')} · ${fmt(t.arrival_date)} – ${fmtY(t.departure_date)} · ${esc(t.pax_total ?? '?')} pax · confirmed ${when(t.confirmed_at)} by ${esc(t.confirmed_by || '')}</div>
                    <div class="td-own" title="Handling agent — whose tour it is"><i class="bi bi-person-badge"></i> ${t.handler_name ? `<b>${esc(t.handler_name)}</b>` : '<span class="pill pill-amber">no handling agent</span>'}</div>
                    ${t.days_to_arrival !== null && t.days_to_arrival >= 0 && ['confirmed', 'operating'].includes(t.stage) ? `<span class="help">arrival in ${t.days_to_arrival} day${t.days_to_arrival === 1 ? '' : 's'}</span>` : ''}
                    ${(t.late || []).map(x => `<span class="late">${esc(x)}</span>`).join('')}
                    ${t.strip ? `<div style="margin-top:6px">${window.tdStrip(t.strip, { compact: true, open: deskLinks(t.id) })}</div>` : ''}
                    <div class="help" style="margin-top:4px"><a href="${BASE}/tour/${t.id}/record" title="The complete record of this tour, from the inquiry to today — one printable page"><i class="bi bi-journal-text"></i> Complete record</a></div></td>
                <td>${esc(q.status || '—')}${t.quote_version ? ` <span class="help">· sold as v${t.quote_version}</span>` : ''}${Number(t.amending) ? ' <span class="pill pill-amber">being amended</span>' : ''}${(t.edit_waiting || []).length ? ` <span class="pill pill-blue" data-er-waiting title="${esc((t.edit_waiting || []).map(w => '#' + w.seq + (w.reason ? ': ' + w.reason : '')).join(' · '))}"><i class="bi bi-pencil-square"></i> ${(t.edit_waiting || []).length} Edit Request${(t.edit_waiting || []).length === 1 ? '' : 's'} waiting</span>` : ''}<div class="help">transport <span class="money">${lkr(t.money.transport_lkr)}</span></div><div class="help">hotels: cost engine, next phase · invoice: later</div></td>
                <td>${h.total ? `${h.booked} of ${h.total} booked` : 'no stays'}<div class="bar"><i style="width:${hp}%"></i></div>
                    ${h.problem ? `<span class="pill pill-red" style="margin-top:6px">${h.problem} problem${h.problem === 1 ? '' : 's'}</span> ` : ''}${h.flags ? `<span class="pill pill-amber" style="margin-top:6px">${h.flags} to fill in</span>` : ''}
                    <div class="help">${h.voucher_issued} voucher${h.voucher_issued === 1 ? '' : 's'} · ${h.requested} requested · ${h.to_book} to book</div>
                    ${(t.lanes.service && t.lanes.service.total) ? `<div class="help" style="margin-top:4px">activities &amp; tickets: <b>${t.lanes.service.done} of ${t.lanes.service.total}</b> done${t.lanes.service.problem ? ` · <span class="pill pill-red">${t.lanes.service.problem} problem</span>` : ''}</div>` : ''}
                    ${t.strip && t.strip.reservations_confirmed ? '<span class="pill pill-green" style="margin-top:4px"><i class="bi bi-patch-check-fill"></i> hotels &amp; activities confirmed — hotel work complete</span>' : (t.strip && t.strip.hotel_work === 'pending' ? '<div class="help" style="margin-top:4px">hotel work pending</div>' : '')}</td>
                <td>${tr.total ? esc(TL[tr.status] || tr.status) : 'no vehicle'}${tr.amended ? ' <span class="pill pill-amber">amended</span>' : ''}<div class="bar t"><i style="width:${tp}%"></i></div>
                    ${t.strip && t.strip.transport_complete ? '<span class="pill pill-green" style="margin-top:4px"><i class="bi bi-patch-check-fill"></i> transport work complete</span>' : (t.strip && t.strip.transport_work === 'pending' ? '<div class="help" style="margin-top:4px">transport work pending</div>' : '')}</td>
                <td>${stagePill(t.stage)}${t.stage === 'cancelled' && t.cancel_why ? `<div class="help" style="color:var(--tf-red)" title="${esc((t.cancelled_at ? when(t.cancelled_at) : '') + (t.cancelled_by ? ' by ' + t.cancelled_by : ''))}">${esc(t.cancel_why)}</div>` : ''}</td></tr>`;
        }).join('');
        tb.querySelectorAll('[data-tour]').forEach(r => r.addEventListener('click', e => { if (e.target.closest('a')) return; openTour(Number(r.dataset.tour)); }));
    }

    async function openTour(id, fromAddress) {
        const j = await api('/tour/' + id);
        if (j.status !== 200) { toast('error', j.message); if (fromAddress && window.TfView) TfView.setParams({ tour: null }); return; }
        TOUR = j.data; renderTour();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_tour')).show();
        // tf-refresh-r1: a refresh opens the same tour again, read fresh; Back closes it
        if (window.TfView && !fromAddress) TfView.setParams({ tour: id }, { push: true });
    }
    document.getElementById('m_tour').addEventListener('hidden.bs.modal', () => { if (window.TfView && TfView.param('tour')) TfView.setParams({ tour: null }, { push: true }); });
    window.addEventListener('popstate', () => {
        const id = Number(new URLSearchParams(location.search).get('tour') || 0);
        const m = document.getElementById('m_tour');
        if (id) { openTour(id, true); }
        else if (m.classList.contains('show')) { bootstrap.Modal.getInstance(m)?.hide(); }
    });

    function renderTour() {
        const t = TOUR;
        document.getElementById('t_title').textContent = `${t.tour_no} · ${t.tour_name || ''} · ${t.agent_name || ''}`;
        const hotels = (t.hotel_jobs || []).map(j => `<tr><td>${j.hotel_name ? esc(j.hotel_name) : '<span class="pill pill-red">no hotel named</span>'}<div class="help">${esc(j.area || '')}</div></td>
            <td class="mono">${fmt(j.check_in)} → ${fmt(j.check_out)} · ${j.nights}n</td><td>${j.meal_plan || '<span class="pill pill-amber">meal?</span>'}</td>
            <td><span class="pill ${j.status === 'problem' ? 'pill-red' : (j.status === 'voucher_issued' ? 'pill-green' : 'pill-grey')}">${esc(HL[j.status] || j.status)}</span>${j.problem_note ? `<div class="help">${esc(j.problem_note)}</div>` : ''}${j.confirmation_ref ? `<div class="help">ref ${esc(j.confirmation_ref)}</div>` : ''}${j.voucher_no ? `<div class="help">${esc(j.voucher_no)}</div>` : ''}</td></tr>`).join('') || '<tr><td colspan="4" class="empty">No stays.</td></tr>';
        const trans = (t.transport_jobs || []).map(j => `<tr><td>${esc(j.vehicle_type)}${Number(j.qty) > 1 ? ' × ' + j.qty : ''}<div class="help">${j.vehicle_reg ? esc(j.vehicle_reg) : 'no vehicle yet'}</div></td>
            <td>${(j.crew || []).map(c => `${esc(c.role_name)}: ${c.person_name ? esc(c.person_name) : '<span class="help">nobody yet</span>'}`).join('<br>') || '—'}</td>
            <td class="mono">${Number(j.km_total).toLocaleString()} km<div class="help">costed ${Number(j.km_quoted).toLocaleString()}</div></td>
            <td><span class="pill pill-blue">${esc(TL[j.status] || j.status)}</span><div class="help">pack ${(j.docs || []).filter(d => d.phase === 'before' && d.state !== 'pending').length} of ${(j.docs || []).filter(d => d.phase === 'before').length}</div></td></tr>`).join('') || '<tr><td colspan="4" class="empty">No vehicle.</td></tr>';
        const acts = (t.stage === 'cancelled'
            ? `<button class="btn btn-light btn-sm" data-act="reopen">Reopen tour</button>`
            : `<button class="btn btn-light btn-sm" data-act="unconfirm">Un-confirm</button><button class="btn btn-light btn-sm" data-act="amend">Amend the quotation</button><button class="btn btn-light btn-sm" style="color:var(--tf-red)" data-act="cancel">Cancel tour</button>`)
            + (CAN_MANAGE ? `<button class="btn btn-light btn-sm" style="color:var(--tf-red)" id="t_delete" title="Admin: cancelled and archived with its inquiry — off every list and count, restorable from Admin → Deleted / archived">Delete tour</button>` : '');

        document.getElementById('t_body').innerHTML = `
            <div class="row g-3">
              <div class="col-lg-7">
                <div class="box">
                  <h6>The tour</h6>
                  ${t.strip ? `<div class="mb-2">${window.tdStrip(t.strip, { open: deskLinks(t.id) })}</div>` : ''}
                  <div class="mb-2 d-flex gap-2 flex-wrap"><a class="btn btn-light btn-sm" href="${BASE}/tour/${t.id}/record"><i class="bi bi-journal-text"></i> Complete record</a>${CAN.hotel ? `<a class="btn btn-light btn-sm" href="${HOTELS}?tour=${t.id}"><i class="bi bi-building"></i> Open on Hotel Bookings</a>` : ''}${CAN.transport ? `<a class="btn btn-light btn-sm" href="${TRANSPORT}?tour=${t.id}"><i class="bi bi-truck"></i> Open on Transport</a>` : ''}</div>
                  <dl class="kv">
                    <dt>Stage</dt><dd>${stagePill(t.stage)}${Number(t.amending) ? ' <span class="pill pill-amber">quotation open for amendment</span>' : ''}</dd>
                    ${t.quote_version ? `<dt>Sold as</dt><dd>quotation version ${t.quote_version}</dd>` : ''}
                    <dt>Hotel work</dt><dd>${t.reservations_confirmed_at ? `<span class="pill pill-green"><i class="bi bi-patch-check-fill"></i> complete</span> <span class="help">${when(t.reservations_confirmed_at)} by ${esc(t.reservations_confirmed_by || '')}</span>` : (t.strip && t.strip.hotel_work === 'none' ? '<span class="help">nothing for the Hotel Desk</span>' : '<span class="pill pill-amber">pending</span>')} ${CAN.hotel ? `<a class="help" href="${HOTELS}?tour=${t.id}">open on Hotel Bookings</a>` : ''}</dd>
                    <dt>Transport work</dt><dd>${t.transport_complete_at ? `<span class="pill pill-green"><i class="bi bi-patch-check-fill"></i> complete</span> <span class="help">${when(t.transport_complete_at)} by ${esc(t.transport_complete_by || '')}</span>` : (t.strip && t.strip.transport_work === 'none' ? '<span class="help">nothing for the Transport Desk</span>' : '<span class="pill pill-amber">pending</span>')} ${CAN.transport ? `<a class="help" href="${TRANSPORT}?tour=${t.id}">open on Transport</a>` : ''}</dd>
                    <dt>Dates</dt><dd class="mono">${t.dates_line ? esc(t.dates_line) : `${fmtY(t.arrival_date)} – ${fmtY(t.departure_date)} · ${t.nights ?? '?'} nights`} · ${t.pax_total ?? '?'} pax</dd>
                    <dt>Confirmed</dt><dd>${when(t.confirmed_at)} by ${esc(t.confirmed_by || '')}</dd>
                    <dt>Handling agent</dt><dd>${t.handler_name ? `<b>${esc(t.handler_name)}</b>` : '<span class="pill pill-amber">nobody</span>'} <span class="help">· whose tour it is; last edited by ${esc(staffName(t.updated_by) || '—')} ${when(t.updated_at)}</span>${CAN_MANAGE ? ` <button class="btn btn-light btn-sm py-0" id="t_reassign" type="button"><i class="bi bi-person-check"></i> Reassign</button>` : ''}</dd>
                    ${t.cancelled_at ? `<dt style="color:var(--tf-red)">Cancelled</dt><dd style="color:var(--tf-red)"><b>${when(t.cancelled_at)} by ${esc(t.cancelled_by || '')}</b> — ${esc(t.cancel_why || '')}<div class="help">Every hotel, activity and transport card is marked cancelled for the desks; nothing further is done on this tour.</div></dd>` : ''}
                    <dt>Transport cost</dt><dd class="money">${lkr(t.money.transport_lkr)} <span class="help">as quoted</span></dd>
                    <dt>Hotel cost</dt><dd class="help">${esc(t.money.note)}</dd>
                    <dt>Inquiry</dt><dd>${CAN.intake ? `<a href="${INTAKE}?id=${t.intake_id}">open the quotation</a>` : '<span class="help">on the Tour Inquiry screen, which this sign-in does not hold</span>'}</dd>
                  </dl>
                </div>
                ${t.details ? `<details class="box"><summary style="cursor:pointer;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--tf-muted);font-weight:700">The whole tour — programme, passengers, flights, requests</summary><div class="mt-2">${window.tdDetails(t.details, { print: BASE + '/tour/' + t.id + '/programme' })}</div></details>` : ''}
                <div class="box"><h6>Hotels</h6><div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Hotel</th><th>Nights</th><th>Meal</th><th>Status</th></tr></thead><tbody>${hotels}</tbody></table></div></div>
                ${(t.service_jobs || []).length ? `<div class="box"><h6>Activities &amp; tickets</h6><div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Day</th><th>What</th><th>Paid</th><th>Status</th></tr></thead><tbody>${(t.service_jobs || []).filter(j => !j.archived_at).map(j => `<tr><td class="mono">${j.day_no ? 'Day ' + j.day_no : '—'}<div class="help">${fmt(j.on_date)}</div></td><td><b>${esc(j.title)}</b><div class="help">${esc(j.kind)}${j.source === 'desk' ? ' · added by the desk' : ''}</div></td><td class="help">${esc(({ in_tour: 'in the tour price', prepaid: 'prepaid by the agent', direct: 'paid on the spot', own_arrangement: 'agent arranges', not_arranged: 'not arranged', no_charge: 'no charge', unknown: 'not stated' })[j.pay_mode] || j.pay_mode || '—')}</td><td><span class="pill ${j.status === 'problem' ? 'pill-red' : (['confirmed', 'not_needed'].includes(j.status) ? 'pill-green' : (j.status === 'pay_at_gate' ? 'pill-amber' : 'pill-grey'))}">${esc(j.status === 'not_needed' ? 'nothing to arrange' : (j.status === 'to_book' ? 'to book' : (j.status === 'pay_at_gate' ? 'pay at gate' : j.status.replace('_', ' '))))}</span>${j.ticket_text ? `<div class="help">tickets <b class="mono">${esc(j.ticket_text)}</b> from stock${j.tickets_used ? ' · ' + j.tickets_used + ' used' : ''}</div>` : (j.confirmation_ref ? `<div class="help">ref ${esc(j.confirmation_ref)}</div>` : '')}${j.status === 'pay_at_gate' && j.request_note ? `<div class="help">${esc(j.request_note)}</div>` : ''}${j.problem_note ? `<div class="help">${esc(j.problem_note)}</div>` : ''}</td></tr>`).join('')}</tbody></table></div></div>` : ''}
                <div class="box"><h6>Transport</h6><div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Vehicle</th><th>Crew</th><th>km</th><th>Status</th></tr></thead><tbody>${trans}</tbody></table></div></div>
              </div>
              <div class="col-lg-5">
                ${(t.edit_waiting || []).length ? `<div class="box" data-er-box style="border-left:3px solid var(--tf-navy)">
                  <h6>Edit Requests waiting for amendment</h6>
                  ${t.edit_waiting.map(w => `<div style="font-size:12.5px;padding:6px 0;border-bottom:1px solid #eef2f6"><b>#${w.seq}</b> “${esc(String(w.pasted_text || '').slice(0, 120))}” → “${esc(String(w.change_to || '').slice(0, 120))}”
                    <div class="help">Why: ${esc(w.reason || '')} · asked by ${esc(w.requested_by || '')} ${when(w.requested_at)}</div></div>`).join('')}
                  <div class="help mt-2">Saved while the tour is confirmed: nothing has changed. <b>Amend the quotation</b> opens the tour; each request is then checked and confirmed on the inquiry, and Confirm Tour waits for a quotation version issued after it.</div>
                </div>` : ''}
                <div class="box">
                  <h6>Management</h6>
                  <div class="d-flex gap-2 flex-wrap">${acts}</div>
                  <div id="t_form" class="mt-2"></div>
                  <div class="help mt-2">Management only; each of these asks for your password and a reason, and goes on the Activity log with your name. Un-confirm only while no card has moved. Amend opens the quotation for correction; on Confirm, cards that have not moved are rebuilt and the rest are marked for their desk. Cancel keeps every card and the whole trail; Reopen undoes a cancellation.</div>
                </div>
                <div class="box">
                  <h6>Communications &amp; History</h6>
                  <div class="help mb-2">Every move on the desks and every edit to the quotation — who, when, what changed. <a class="btn btn-sm" style="background:#842484;border-color:#842484;color:#fff" href="${INTAKE}/comms/${t.intake_id}" target="_blank"><i class="bi bi-chat-left-text"></i> Communications &amp; History</a> <span class="help">— correspondence, attachments, agreed arrangements, follow-ups, hand-over.</span></div>
                  <div class="trail">${window.tdHistory(t.history || [], { empty: 'Nothing yet.', names: NAMES })}</div>
                </div>
              </div>
            </div>`;
        document.getElementById('t_reassign')?.addEventListener('click', async () => {
            const opts = {};
            HANDLERS.filter(h => h.id).forEach(h => { opts[h.id] = h.name + (h.group ? ' · ' + h.group : ''); });
            const r = await Swal.fire({ title: 'Reassign ' + t.tour_no, input: 'select', inputOptions: opts, inputValue: t.handler_id || '', inputPlaceholder: 'Choose who handles this tour', showCancelButton: true, confirmButtonText: 'Reassign' });
            if (!r.isConfirmed || !r.value) return;
            const j = await manage('/intake/' + t.intake_id + '/reassign', { user_id: r.value });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message);
            openTour(t.id); load();
        });
        document.getElementById('t_delete')?.addEventListener('click', async () => {
            const pw = await askPassword('Delete tour ' + t.tour_no, 'Deleting cancels the tour and archives it with its inquiry. Enter your own sign-in password to go on; it is checked and not kept.', '/reauth', manage, 'Delete tour ' + t.tour_no);
            if (pw === null) return;
            const r = await Swal.fire({ title: 'Delete tour ' + t.tour_no + '?', html: '<div style="text-align:left;font-size:13.5px">Are you sure you want to delete this record? The tour is cancelled and archived with its inquiry: off Inquiries, Hotel Bookings, Transport, the Board and every red count. Nothing is removed — it is kept whole under Admin → Deleted / archived and can be restored from there.</div>', input: 'text', inputPlaceholder: 'Why (required, stays on the trail)', inputValidator: v => (v && v.trim() ? undefined : 'The reason is required.'), showCancelButton: true, confirmButtonText: 'Delete the tour', confirmButtonColor: '#b23a32' });
            if (!r.isConfirmed) return;
            const j = await manage('/tour/' + t.id + '/delete', { why: r.value || '', password: pw });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message);
            bootstrap.Modal.getInstance(document.getElementById('m_tour')).hide(); load();
        });
        // Every change to a confirmed tour — Un-confirm, Amend, Cancel, Reopen — is Management's, in the same three steps:
        // the password (checked on the server, not kept), the reason (required, stays on the trail), the last warning.
        // The server checks all three again on the request itself, so the screen is a courtesy, not the guard.
        const ACTS = {
            unconfirm: { title: 'Un-confirm tour', verb: 'Un-confirm', color: '#b23a32', why: 'Why is {no} un-confirmed?', ask: 'Un-confirming removes every hotel, activity and transport card from the desks and opens the inquiry again for editing; the tour number is not reused. Only possible while no card has moved. Enter your own sign-in password to go on; it is checked and not kept.',
                warn: 'The tour leaves the Board and both desks: its hotel, activity and transport cards are removed, and the inquiry is open for editing again as if it had not been confirmed. The confirmation and this un-confirm stay on the Activity log.', keep: 'Keep the tour', go: 'Un-confirm the tour', done: 'Un-confirmed' },
            amend: { title: 'Amend tour', verb: 'Amend', color: '#2f6fb3', why: 'What is being corrected on {no}?', ask: 'Amending opens the inquiry for correction while the tour stays on the desks marked amending. Confirm Tour closes it again (with your password), rebuilding the cards that have not moved. Enter your own sign-in password to go on; it is checked and not kept.',
                warn: 'The inquiry opens for editing. The tour stays on the desks marked <b>amending</b> until Confirm Tour is pressed again; cards that have not moved are then rebuilt from the corrected quotation, and cards already in progress are marked amended for their desk.', keep: 'Leave it as it is', go: 'Open for amendment', done: 'Opened for amendment' },
            cancel: { title: 'Cancel tour', verb: 'Cancel', color: '#b23a32', why: 'Why is {no} cancelled?', ask: 'Cancelling marks every hotel and transport card cancelled and takes the tour off both desks\' counts. Enter your own sign-in password to go on; it is checked and not kept.',
                warn: 'The tour leaves the live board; every hotel, activity and transport card is marked <b>Cancelled</b> for the desks with your reason, and the tour counts for nothing on either desk. Hotel and transport work already done stays on the trail. Reopen brings it back only at the cards\' first step.', keep: 'Keep the tour', go: 'Cancel the tour', done: 'Cancelled' },
            reopen: { title: 'Reopen tour', verb: 'Reopen', color: '#1f7a4d', why: 'Why is {no} reopened?', ask: 'Reopening undoes the cancellation: the tour returns to the live board and to both desks at the cards\' first step. Enter your own sign-in password to go on; it is checked and not kept.',
                warn: 'The cancellation is undone. The tour is back on the live board and on Hotel Bookings and Transport, every card at its first step; the cancellation and this reopen stay on the trail.', keep: 'Leave it cancelled', go: 'Reopen the tour', done: 'Reopened' },
        };
        document.querySelectorAll('#t_body [data-act]').forEach(b => b.addEventListener('click', async () => {
            const kind = b.dataset.act, a = ACTS[kind];
            if (!a) return;
            if (!CAN_MANAGE) { toast('error', 'Only Management changes a confirmed tour (the Tour Desk Manage privilege).'); return; }
            const pw = await askPassword(a.title + ' ' + t.tour_no, a.ask, '/reauth', post, a.title + ' ' + t.tour_no);
            if (pw === null) return;
            const why = await Swal.fire({ title: a.why.replace('{no}', t.tour_no), input: 'text', inputPlaceholder: 'The reason (required, stays on the trail)', inputValidator: v => (v && v.trim() ? undefined : 'The reason is required.'), showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: a.color });
            if (!why.isConfirmed) return;
            const fin = await Swal.fire({ icon: 'warning', title: a.title + ' ' + t.tour_no + '?', html: `<div style="text-align:left;font-size:13.5px"><div style="background:#fbe9e6;border:1px solid #f0b8b0;border-radius:8px;padding:8px 12px;margin-bottom:10px;color:#8a2a22"><b>Last warning.</b> ${a.warn}</div>Reason: <b>${esc(why.value.trim())}</b><br>${a.done} by <b>${esc(ME_NAME)}</b>, now; the name, the time and the reason go on the Activity log.</div>`, showCancelButton: true, cancelButtonText: a.keep, confirmButtonText: a.go, confirmButtonColor: a.color, focusCancel: true });
            if (!fin.isConfirmed) return;
            const j = await post('/tour/' + t.id + '/' + kind, { why: why.value.trim(), password: pw });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message);
            if (kind === 'unconfirm') { bootstrap.Modal.getInstance(document.getElementById('m_tour')).hide(); load(); return; }
            TOUR = j.data; renderTour(); load();
        }));
    }

    document.getElementById('f_q').addEventListener('input', () => { clearTimeout(window.__bq); window.__bq = setTimeout(load, 250); });
    ['f_handler', 'f_from', 'f_to'].forEach(id => document.getElementById(id).addEventListener('change', load));
    document.getElementById('f_clear').addEventListener('click', () => { ['f_handler', 'f_q', 'f_from', 'f_to'].forEach(id => document.getElementById(id).value = ''); FILTER = 'live'; load(); });
    if (window.TfView) TfView.restoreList(false);   // tf-refresh-r1: this tab's search and filters
    const H0 = new URLSearchParams(location.search).get('handler');
    if (H0) document.getElementById('f_handler').value = H0;
    load();
    const T0 = Number(new URLSearchParams(location.search).get('tour') || 0);
    if (T0) openTour(T0, true);
})();
</script>
</body>
</html>
