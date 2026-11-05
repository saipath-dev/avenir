<?= view('layouts/head') ?>
<?php
/**
 * tf-rotation-r1 (25 Oct 2026) — Pending Allocations (tour/transport_ops/allocations): the cards staff sent on from Transport
 * Operations, the fair rotation's proposal for each — Accept, Not Available (reason, period, the next in turn proposed), Bypass
 * Rotation (reason, the warning acknowledged) — and the lanes Awaiting review · Confirming · Upcoming Tours · On Tour.
 */
$esc = static fn ($s) => esc((string) $s);
?>
<style>
    .tf { --tf-navy:#1e3553; --tf-teal:#0f8b7e; --tf-amber:#b7791f; --tf-amber-soft:#fdf3e1; --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --p:#842484; --g:#78b848; --gold:#e0a526; color:var(--tf-ink); }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:12px; }
    .tf .tf-title h4 { margin:0; font-weight:700; color:var(--p); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:900px; }
    .tf .lanes { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:14px; }
    .tf .lane { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:10px; border:1px solid var(--tf-line); background:#fff; font-weight:600; font-size:13px; color:var(--tf-ink); cursor:pointer; }
    .tf .lane.on { background:var(--p); border-color:var(--p); color:#fff; }
    .tf .lane .n { min-width:22px; height:22px; border-radius:11px; background:rgba(132,36,132,.12); color:var(--p); display:inline-grid; place-items:center; font-size:12px; font-weight:800; padding:0 6px; }
    .tf .lane.on .n { background:#fff; }
    .tf .rcard { background:#fff; border:1px solid var(--tf-line); border-radius:12px; margin-bottom:12px; box-shadow:0 2px 8px rgba(20,20,30,.04); }
    .tf .rcard .hd { display:flex; gap:12px; align-items:center; padding:12px 16px; border-bottom:1px solid var(--tf-line); flex-wrap:wrap; }
    .tf .rcard .hd b.no { color:var(--p); font-size:15px; }
    .tf .rcard .hd .meta { color:var(--tf-muted); font-size:12.5px; }
    .tf .rcard .bd { padding:14px 16px; }
    .tf .pill { display:inline-block; padding:2px 9px; border-radius:999px; font-size:11.5px; font-weight:700; }
    .tf .pill-p { background:rgba(132,36,132,.12); color:var(--p); } .tf .pill-g { background:var(--tf-green-soft); color:var(--tf-green); } .tf .pill-a { background:var(--tf-amber-soft); color:var(--tf-amber); } .tf .pill-r { background:var(--tf-red-soft); color:var(--tf-red); } .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .prop { display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap; padding:14px; border:1px solid rgba(120,184,72,.6); background:rgba(120,184,72,.08); border-radius:12px; }
    .tf .prop .who { flex:1; min-width:260px; }
    .tf .prop .who b { font-size:16px; }
    .tf .prop .who .l { color:var(--tf-muted); font-size:12.5px; }
    .tf .prop .acts { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .tf .btn-p { background:var(--p); color:#fff; border:0; } .tf .btn-p:hover { filter:brightness(1.08); color:#fff; }
    .tf .btn-g { background:var(--g); color:#1f2a14; border:0; font-weight:700; } .tf .btn-g:hover { filter:brightness(1.06); color:#1f2a14; }
    .tf .paybox { padding:10px 12px; border:1px solid rgba(132,36,132,.25); background:rgba(132,36,132,.04); border-radius:10px; font-size:13px; }
    .tf .paybox b.amt { color:var(--p); }
    .tf .carrate { margin-top:10px; padding:10px 12px; border:1px dashed var(--gold); background:#fffaf0; border-radius:10px; font-size:13px; }
    .tf .carrate b.amt { font-size:15px; color:var(--p); }
    .tf .nobody { padding:12px 14px; border:1px dashed var(--tf-red); background:var(--tf-red-soft); border-radius:10px; font-size:13px; }
    .tf table.cand { width:100%; font-size:12.5px; margin-top:10px; }
    .tf table.cand th { font-weight:700; color:var(--tf-muted); font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; padding:6px 8px; border-bottom:1px solid var(--tf-line); text-align:left; }
    .tf table.cand td { padding:7px 8px; border-bottom:1px solid #eef1f5; vertical-align:top; }
    .tf table.cand tr.ok td:first-child { border-left:3px solid var(--g); } .tf table.cand tr.out td:first-child { border-left:3px solid #d8dde4; }
    .tf .why { color:var(--tf-red); font-size:12px; }
    .tf .help { color:var(--tf-muted); font-size:12px; }
    .tf .empty { padding:30px; text-align:center; color:var(--tf-muted); }
    .tf .lanetable { width:100%; font-size:13px; } .tf .lanetable th { text-align:left; padding:8px 10px; background:var(--p); color:#fff; font-weight:600; font-size:12px; } .tf .lanetable td { padding:8px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .lanetable th:first-child { border-radius:8px 0 0 0; } .tf .lanetable th:last-child { border-radius:0 8px 0 0; }
    .tf .warnbox { padding:12px 14px; border:2px solid var(--tf-red); background:var(--tf-red-soft); border-radius:10px; font-size:13px; color:#5a1d18; }
    @media (max-width:767px) { .tf .rcard .hd, .tf .prop { padding:10px 12px; }
        .tf .lanetable thead { display:none; } .tf .lanetable, .tf .lanetable tbody, .tf .lanetable tr, .tf .lanetable td { display:block; width:100%; }
        .tf .lanetable tr { border:1px solid var(--tf-line); border-radius:12px; margin-bottom:10px; padding:6px 4px; background:#fff; }
        .tf .lanetable td { border:0; padding:4px 10px; } .tf .lanetable td[data-l]::before { content:attr(data-l); display:block; font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--tf-muted); }
        .tf table.cand thead { display:none; } .tf table.cand, .tf table.cand tbody, .tf table.cand tr, .tf table.cand td { display:block; width:100%; } .tf table.cand tr { border-bottom:1px solid var(--tf-line); padding:6px 0; } .tf table.cand td { border:0; padding:2px 8px; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar', ['tourdesk_desk_only' => 'transport']) ?>
                <?= view('layouts/tourdesk_menu') ?>

                <div class="tf-title">
                    <div>
                        <h4>Pending Allocations</h4>
                        <div class="sub">Each card sent on from Transport Operations, with the fair rotation's proposal: the vehicle class from the booking's passengers, Japanese-speaking drivers in the pool with that vehicle, five full days at home after a tour, no overlapping engagements, the one waiting longest first. Accept, or record Not Available (the next in turn is proposed), or Bypass Rotation with the reason — every decision is logged.</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-light btn-sm" href="<?= base_url('tour/transport_ops/allocations/registry') ?>"><i class="bi bi-people"></i> Driver details &amp; agreed rates</a>
                        <a class="btn btn-light btn-sm" href="<?= base_url('tour/transport_ops/allocations/log') ?>"><i class="bi bi-journal-text"></i> Bypass &amp; Not Available report</a>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="alert alert-warning">The Tour Desk tables are not installed on this database.<?= !empty($install_error) ? ' ' . $esc($install_error) : '' ?></div>
                <?php else: ?>
                <div class="lanes" id="lanes">
                    <?php foreach (['awaiting_review' => 'Awaiting review (Transport Operations)', 'pending' => 'Pending allocation', 'confirming' => 'Confirming (guide / vehicle)', 'upcoming' => 'Upcoming Tours', 'on_tour' => 'On Tour'] as $k => $l): ?>
                    <button type="button" class="lane<?= ($lane ?? 'pending') === $k ? ' on' : '' ?>" data-lane="<?= $k ?>"><?= $esc($l) ?> <span class="n" id="n-<?= $k ?>">–</span></button>
                    <?php endforeach; ?>
                </div>
                <div id="body"><div class="empty">Loading…</div></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url('tour/transport_ops/allocations') ?>';
    const OPS = '<?= base_url('tour/transport_ops') ?>';
    const CAN = <?= !empty($can_allocate) ? 'true' : 'false' ?>;
    const WARN = <?= json_encode($warning_bypass ?? '') ?>;
    const REST = <?= (int) ($rest_days ?? 5) ?>;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmt = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
    const money = n => 'LKR ' + Number(n || 0).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const api = (p, o) => fetch(BASE + p, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, o || {})).then(r => r.json());
    const post = (p, b) => api(p, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(b || {}) });
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2600 : undefined, showConfirmButton: icon !== 'success' });
    const body = document.getElementById('body');
    if (!body) return;
    let DATA = null, LANE = document.querySelector('.lane.on')?.dataset.lane || 'pending';
    const ASSESS = {};

    async function load() {
        const j = await api('/data');
        if (j.status !== 200) { body.innerHTML = '<div class="empty">' + esc(j.message) + '</div>'; return; }
        DATA = j.data;
        Object.entries(DATA.counts).forEach(([k, n]) => { const el = document.getElementById('n-' + k); if (el) el.textContent = n; });
        render();
    }
    document.querySelectorAll('.lane').forEach(b => b.addEventListener('click', () => { document.querySelectorAll('.lane').forEach(x => x.classList.toggle('on', x === b)); LANE = b.dataset.lane; history.replaceState(null, '', '?lane=' + LANE); render(); }));

    function crewLine(c) { return `<span class="pill ${c.status === 'engaged' ? 'pill-g' : 'pill-grey'}">${esc(c.kind)} ${esc(c.role_code || '')}${c.person_name ? ': ' + esc(c.person_name) : ''} · ${c.status === 'engaged' ? 'engaged' : 'to engage'}</span>`; }
    const kmText = r => `${Number(r.km_total || 0).toLocaleString()} km route + ${Number(r.extra_km_total || 0).toLocaleString()} km local = <b>${(Number(r.km_total || 0) + Number(r.extra_km_total || 0)).toLocaleString()} payable km</b>${Number(r.qty) > 1 ? ' × ' + r.qty + ' vehicles' : ''}`;
    // required (from the guests) and quoted (from the quotation) class, and the actual vehicle on the card
    function vehicleCell(r) {
        const al = r.allocation;
        const actual = al ? `${esc((al.vehicle_type || '').replace(/^./, m => m.toUpperCase()))}${al.vehicle_roof ? ' (' + esc(al.vehicle_roof) + ' roof)' : ''} <b>${esc(al.vehicle_reg || '')}</b>${al.vehicle_guests ? ' · ' + al.vehicle_guests + ' guests' : ''}` : (r.vehicle_reg ? '<b>' + esc(r.vehicle_reg) + '</b>' : '<span class="help">not set</span>');
        return `<div><span class="help">Required:</span> ${esc(r.needed?.name || r.vehicle_type)}${r.needed && !r.needed.agrees ? ' <span class="help">(quoted ' + esc(r.needed.card_name) + ')</span>' : ''}</div><div><span class="help">Actual:</span> ${actual}${al && Number(al.car_rate_tour) ? ' <span class="pill pill-a">car rate</span>' : ''}</div>`;
    }
    const ALLOC_PILL = { complete: 'pill-g', guide_open: 'pill-a', driver_no_vehicle: 'pill-a', awaiting_driver: 'pill-p', not_reviewed: 'pill-grey' };
    const TOUR_PILL = { not_started: 'pill-grey', on_tour: 'pill-g', finished: 'pill-grey' };

    function render() {
        const rows = DATA.lanes[LANE] || [];
        if (!rows.length) { body.innerHTML = '<div class="empty">Nothing in this lane.</div>'; return; }
        if (LANE !== 'pending') {
            body.innerHTML = `<div class="rcard"><div class="bd" style="overflow:auto"><table class="lanetable"><thead><tr><th>Tour</th><th>Dates</th><th>Guests</th><th>Vehicle — required / actual</th><th>Crew</th><th>Allocation</th><th>Tour</th><th>Desk step</th><th></th></tr></thead><tbody>
                ${rows.map(r => `<tr><td><b>${esc(r.tour_no)}</b><div class="help">${esc(r.tour_name || '')} · ${esc(r.agent_name || '')}</div></td><td data-l="Dates">${fmt(r.arrival_date)} – ${fmt(r.departure_date)}</td><td data-l="Guests">${r.pax}</td><td data-l="Vehicle">${vehicleCell(r)}</td>
                    <td data-l="Crew">${(r.crew || []).map(crewLine).join(' ')}</td>
                    <td data-l="Allocation"><span class="pill ${ALLOC_PILL[r.alloc_stage] || 'pill-grey'}">${esc(r.alloc_label)}</span>${LANE === 'awaiting_review' && CAN ? `<div class="mt-1"><button class="btn btn-sm btn-p" data-review="${r.id}">Driver Allocation →</button></div>` : ''}</td>
                    <td data-l="Tour"><span class="pill ${r.urgent ? 'pill-r' : (TOUR_PILL[r.tour_stage] || 'pill-grey')}">${esc(r.tour_label)}${r.urgent ? ' — allocation incomplete' : ''}</span></td>
                    <td data-l="Desk step" class="help">${esc(r.desk_step)}</td>
                    <td><a class="btn btn-light btn-sm" href="${OPS}?tour=${r.tour_id}">Open card</a></td></tr>`).join('')}</tbody></table></div>
                <div class="help" style="padding:0 16px 12px">Allocation = driver engaged + his vehicle on the card + every other crew line on the card engaged. Tour = the dates against today. Desk step = the card's own step on Transport Operations (To allocate → Allocated → Scheduled → Pack issued → Running → Returned).</div></div>`;
            body.querySelectorAll('[data-review]').forEach(b => b.addEventListener('click', async () => { const j = await post('/review/' + b.dataset.review, { on: 1 }); toast(j.status === 200 ? 'success' : 'error', j.status === 200 ? 'Sent to Pending Allocations.' : j.message); if (j.status === 200) load(); }));
            return;
        }
        body.innerHTML = rows.map(r => `<div class="rcard" id="rc-${r.id}">
            <div class="hd"><b class="no">${esc(r.tour_no)}</b><span class="meta">${esc(r.tour_name || '')} · ${esc(r.agent_name || '')}</span><span class="pill pill-p">${r.pax} guests</span><span class="meta">${fmt(r.arrival_date)} – ${fmt(r.departure_date)} · ${r.days || '?'} days · ${kmText(r)}</span>
                ${r.urgent ? '<span class="pill pill-r">' + esc(r.tour_label) + ' — no driver yet</span>' : ''}<span class="meta">sent on by ${esc(r.review?.reviewed_by || '')}</span><span class="ms-auto d-flex gap-2"><a class="btn btn-light btn-sm" href="${OPS}?tour=${r.tour_id}">Open card</a>${CAN ? `<button class="btn btn-light btn-sm" data-unreview="${r.id}" title="Take the card back to Transport Operations">Take back</button>` : ''}</span></div>
            <div class="bd" id="rb-${r.id}"><div class="help">Assessing the rotation…</div></div></div>`).join('');
        body.querySelectorAll('[data-unreview]').forEach(b => b.addEventListener('click', async () => { const j = await post('/review/' + b.dataset.unreview, { on: 0 }); if (j.status === 200) load(); else toast('error', j.message); }));
        rows.forEach(r => assess(r.id));
    }

    function payBox(pay) {
        if (!pay) return '';
        return `<div class="${pay.missing ? 'nobody' : 'paybox'} mt-2"><b>Payment basis:</b> ${esc(pay.source)} — ${pay.missing ? '<b>not on file</b> (to be supplied; nothing is assumed)' : 'LKR ' + Number(pay.rate_km).toFixed(2) + '/km'} × ${esc(pay.km_text)}${pay.amount != null ? ' = <b class="amt">' + money(pay.amount) + '</b> <span class="help">(scheduled; settled on actual km)</span>' : ''}</div>`;
    }

    async function assess(jobId) {
        const j = await api('/assess/' + jobId);
        const el = document.getElementById('rb-' + jobId);
        if (!el) return;
        if (j.status !== 200) { el.innerHTML = '<div class="nobody">' + esc(j.message) + '</div>'; return; }
        const a = j.data; ASSESS[jobId] = a;
        const need = a.need;
        let h = `<div class="mb-2"><span class="pill pill-p">Required: ${esc(need.name || need.card_name)}</span> <span class="help">${a.job.pax} guests → ${esc(need.name || '?')} (up to ${need.max_pax ?? '?'} guests)${need.agrees ? '' : ' · the quotation says ' + esc(need.card_name)} · driver line ${esc(a.job.line_code || '—')}${need.guide_required ? ' · 8+ guests: driver AND a separate guide' : ''}${a.car_rate_tour ? ' · car-rate tour: opted-in van drivers join this rotation' : ''}</span></div>`;
        if (a.blocks && a.blocks.length) {
            h += `<div class="nobody"><b>Not allocated automatically — the tour stays pending.</b><ul class="mb-0 mt-1">${a.blocks.map(b => '<li>' + esc(b) + '</li>').join('')}</ul></div>`;
        } else if (a.pool_size === 0) {
            h += `<div class="nobody"><b>The rotation pool is not set.</b> Management marks the drivers in the pool on <a href="${BASE}/registry">Driver details &amp; agreed rates</a>; nobody is proposed until then.</div>`;
        }
        const p = a.proposal;
        if (p) {
            h += `<div class="prop"><div class="who"><b>${esc(p.nickname || p.name)}</b> <span class="help">${esc(p.name)} · ${esc(p.category)}${p.mobile ? ' · ' + esc(p.mobile) : ''}</span>
                <div class="l">Required: <b>${esc(need.name)}</b> · Actual vehicle: <b>${esc(p.vehicle?.type_name || '')}${p.vehicle?.roof ? ' (' + esc(p.vehicle.roof) + ' roof)' : ''} ${esc(p.vehicle?.reg_no || '?')}</b> · carries ${p.vehicle?.guests ?? '?'} guests</div>
                <div class="l">${p.queue_from ? 'In the queue from ' + fmt(p.queue_from) + ' (' + esc(p.queue_tour) + ')' : 'No tour yet — first in turn'}${p.last_tour_end ? ' · last completed ' + esc(p.last_tour_no) + ' ' + fmt(p.last_tour_end) + ' · free from ' + fmt(p.free_from) : ''}</div>
                ${p.warnings?.length ? '<div class="why">' + p.warnings.map(w => esc(w.text)).join(' ') + '</div>' : ''}
                ${p.car_rate_van && a.car_rate ? `<div class="carrate"><b>Van at the car rate.</b> The actual vehicle is a van (${esc(p.vehicle?.reg_no || '')}); this ${a.job.pax}-guest tour is paid at his agreed car rate: <b class="amt">${money(a.car_rate.amount)}</b> = LKR ${Number(a.car_rate.rate_km).toFixed(2)}/km × ${esc(a.car_rate.km_text)}. The van rate does not apply.<br>
                    <label class="mt-2 d-flex gap-2 align-items-start"><input type="checkbox" id="cr-${jobId}"> <span>The driver has been told these terms and <b>accepts the car rate for this tour</b> (recorded with your name and the time).</span></label></div>` : payBox(p.payment)}
            </div>
            <div class="acts">${CAN ? `<button class="btn btn-g" data-accept="${jobId}" data-person="${p.id}"><i class="bi bi-check2-circle"></i> Accept allocation</button>
                <button class="btn btn-light" data-na="${jobId}" data-person="${p.id}" data-name="${esc(p.nickname || p.name)}"><i class="bi bi-x-circle"></i> Not Available</button>
                ${a.bypass_candidates.length ? `<button class="btn btn-outline-danger btn-sm" data-bypass="${jobId}"><i class="bi bi-exclamation-triangle"></i> Bypass Rotation</button>` : ''}` : '<span class="help">Deciding needs the Driver Allocation privilege.</span>'}</div></div>`;
        } else if (!(a.blocks && a.blocks.length) && a.pool_size) {
            h += `<div class="nobody"><b>No suitable driver — the tour stays pending.</b> The reasons are listed below for every driver. Bypass Rotation can change only the rotation order or pool membership; it cannot override vehicle suitability or capacity, language/category, availability, overlap, rest days or car-rate consent.
                ${CAN && a.bypass_candidates.length ? `<div class="mt-2"><button class="btn btn-outline-danger btn-sm" data-bypass="${jobId}">Bypass Rotation — ${a.bypass_candidates.length} suitable driver${a.bypass_candidates.length === 1 ? '' : 's'} outside the pool</button></div>` : '<div class="mt-1"><b>No suitable driver exists outside the pool either</b> — supply the missing details, add a suitable driver to the pool (management), or record a change of availability.</div>'}</div>`;
        }
        h += `<details class="mt-2"><summary class="help" style="cursor:pointer">Every driver assessed (${a.all.length}) — ${a.eligible.length} eligible, in rotation order</summary><div style="overflow:auto"><table class="cand"><thead><tr><th>Driver</th><th>Vehicle offered</th><th>In the queue from</th><th>Payment basis</th><th>Result</th></tr></thead><tbody>
            ${[...a.eligible, ...a.all.filter(x => !x.eligible)].map(x => `<tr class="${x.eligible ? 'ok' : 'out'}"><td><b>${esc(x.nickname || x.name)}</b><div class="help">${esc(x.category || 'no category')}${x.in_pool ? '' : ' · not in pool'}${x.car_rate_optin ? ' · car-rate opt-in' : ''}</div></td><td>${x.vehicle ? esc(x.vehicle.type_name || '') + ' ' + esc(x.vehicle.reg_no || '') + ' · ' + x.vehicle.guests + ' guests' + (x.car_rate_van ? ' <span class="pill pill-a">van at car rate</span>' : '') : '<span class="help">—</span>'}</td><td>${x.queue_from ? fmt(x.queue_from) : '<span class="help">no tour yet</span>'}</td><td class="help">${x.payment ? (x.payment.missing ? '<span class="why">rate not on file</span>' : 'LKR ' + Number(x.payment.rate_km).toFixed(2) + '/km → ' + money(x.payment.amount)) : '—'}</td><td>${x.eligible ? '<span class="pill pill-g">eligible</span>' : (x.why_out.length ? '<span class="why">' + x.why_out.map(esc).join('; ') + '</span>' : '<span class="why">suitable, but the card is blocked — see above</span>')}</td></tr>`).join('')}</tbody></table></div></details>`;
        el.innerHTML = h;
        el.querySelectorAll('[data-accept]').forEach(b => b.addEventListener('click', () => accept(jobId, Number(b.dataset.person), false)));
        el.querySelectorAll('[data-na]').forEach(b => b.addEventListener('click', () => notAvailable(jobId, Number(b.dataset.person), b.dataset.name)));
        el.querySelectorAll('[data-bypass]').forEach(b => b.addEventListener('click', () => bypass(jobId)));
    }

    async function accept(jobId, personId, isBypass, extra) {
        const a = ASSESS[jobId];
        const cr = document.getElementById('cr-' + jobId);
        const body = Object.assign({ person_id: personId, car_rate_accepted: cr ? (cr.checked ? 1 : 0) : 0 }, extra || {});
        if (!isBypass && cr && !cr.checked) { toast('warning', 'Record the driver\'s acceptance of the car-rate terms first (tick the box).'); return; }
        const j = await post((isBypass ? '/bypass/' : '/accept/') + jobId, body);
        toast(j.status === 200 ? 'success' : 'error', j.message);
        if (j.status === 200) load();
    }

    async function notAvailable(jobId, personId, name) {
        const a = ASSESS[jobId];
        const r = await Swal.fire({ title: name + ' — Not Available', html: `<div style="text-align:left;font-size:13.5px">
            <label>Reason <span style="color:#b23a32">*</span></label><input id="na_reason" class="swal2-input" style="margin:4px 0 10px;width:100%" placeholder="e.g. family matter, vehicle in repair, declined">
            <div style="display:flex;gap:8px"><div style="flex:1"><label>From</label><input id="na_from" type="date" class="swal2-input" style="margin:4px 0;width:100%" value="${a.job.arrival_date}"></div><div style="flex:1"><label>To</label><input id="na_to" type="date" class="swal2-input" style="margin:4px 0;width:100%" value="${a.job.departure_date}"></div></div>
            <div style="color:#5f6f80;font-size:12px;margin-top:6px">The period is kept on the driver's record and the next driver in turn is proposed automatically. Recorded with your name for the report.</div></div>`,
            showCancelButton: true, confirmButtonText: 'Record Not Available', confirmButtonColor: '#842484', focusConfirm: false,
            preConfirm: () => { const v = document.getElementById('na_reason').value.trim(); if (!v) { Swal.showValidationMessage('The reason is required.'); return false; } return { reason: v, date_from: document.getElementById('na_from').value, date_to: document.getElementById('na_to').value }; } });
        if (!r.isConfirmed) return;
        const j = await post('/not_available/' + jobId, Object.assign({ person_id: personId }, r.value));
        toast(j.status === 200 ? 'success' : 'error', j.message);
        if (j.status === 200) { ASSESS[jobId] = j.data; assess(jobId); }
    }

    async function bypass(jobId) {
        const a = ASSESS[jobId];
        const opts = a.bypass_candidates.map(x => `<option value="${x.id}">${esc(x.nickname || x.name)} — ${esc(x.vehicle ? (x.vehicle.reg_no || '') + ' ' + (x.vehicle.type_name || '') : 'no matching vehicle')}${x.eligible ? '' : ' (' + esc(x.why_out.join('; ')) + ')'}</option>`).join('');
        const r = await Swal.fire({ title: 'Bypass Rotation', width: 640, html: `<div style="text-align:left;font-size:13.5px">
            <div class="warnbox" style="padding:12px 14px;border:2px solid #b23a32;background:#fbe9e7;border-radius:10px;color:#5a1d18">${esc(WARN)}</div>
            <label style="margin-top:12px">Driver to allocate instead</label>${opts ? `<select id="bp_person" class="swal2-select" style="margin:4px 0 10px;width:100%">${opts}</select>` : '<div style="color:#b23a32;margin:6px 0 10px">No other driver can take this tour. Bypass Rotation changes only the rotation order or pool membership; it cannot override vehicle suitability or capacity, language/category, availability, overlap, rest days or car-rate consent.</div><input type="hidden" id="bp_person" value="0">'}
            <label>Reason <span style="color:#b23a32">*</span></label><textarea id="bp_reason" class="swal2-textarea" style="margin:4px 0 8px;width:100%;height:80px" placeholder="The operational reason this tour must go to this driver"></textarea>
            <label style="display:flex;gap:8px;align-items:flex-start"><input type="checkbox" id="bp_ack"> <span>I have read the warning. My name, the reason and the driver passed over (${esc(a.proposal ? (a.proposal.nickname || a.proposal.name) : 'nobody proposed')}) will be recorded and reported.</span></label>
            <label style="display:flex;gap:8px;align-items:flex-start;margin-top:8px"><input type="checkbox" id="bp_cr"> <span>If this driver is a van driver on a car-rate tour: he has accepted the car-rate terms for this tour.</span></label></div>`,
            showCancelButton: true, confirmButtonText: 'Allocate out of turn', confirmButtonColor: '#b23a32', focusConfirm: false,
            preConfirm: () => { const reason = document.getElementById('bp_reason').value.trim(); if (!reason) { Swal.showValidationMessage('The reason is mandatory.'); return false; } if (!document.getElementById('bp_ack').checked) { Swal.showValidationMessage('Confirm that you have read the warning.'); return false; }
                return { person_id: Number(document.getElementById('bp_person').value), reason, warning_ack: 1, car_rate_accepted: document.getElementById('bp_cr').checked ? 1 : 0 }; } });
        if (!r.isConfirmed) return;
        const j = await post('/bypass/' + jobId, r.value);
        toast(j.status === 200 ? 'success' : 'error', j.message);
        if (j.status === 200) load();
    }

    load();
})();
</script>
</body>
</html>
