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
                        <h4>Confirmed Tours</h4>
                        <div class="sub">One line per confirmed tour, nearest arrival first: the tour and its inquiry, the agent, the party, the dates, where it stands, and how far Hotel Bookings &amp; Activities and Transport have got with it. Open a line to see the confirmed inquiry — the record of the sale — with its progress. A tour stays <b>Client accepted — reservations pending</b> while the teams arrange it; each team confirms its own section on its own desk. When every section is green, Final confirm marks the tour Confirmed, and the confirmed itinerary goes to the agent from Communications &amp; History.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Desk tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Inquiries</a> and press Install; this screen then works.</div>
                <?php else: ?>

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
                            <thead><tr><th>Tour</th><th>Agent · group</th><th>Pax</th><th>Dates</th><th>Status</th><th class="lh" style="width:230px">Hotel Bookings &amp; Activities</th><th class="lt" style="width:230px">Transport</th><th style="width:130px"></th></tr></thead>
                            <tbody id="rows"><tr><td colspan="8" class="empty">Loading…</td></tr></tbody>
                        </table>
                    </div>
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
    const CHIPS = [['live', 'Confirmed & operating'], ['completed', 'Completed'], ['cancelled', 'Cancelled'], ['all', 'All']];
    let FILTER = 'live', DATA = { tours: [], counts: {} };

    // tf-workflow-r1: "the client accepted" and "every service is confirmed" are two different things,
    // and the line says which one this tour is at. Only the second one reads Confirmed.
    const stagePill = (t) => {
        const s = t.stage;
        if (s === 'cancelled') { return '<span class="pill pill-red">CANCELLED</span>'; }
        // tf-confirmed-r2: a desk that reopened its work after the agent was told comes first, in red. The
        // line must never read "Confirmed" while a section is open again.
        if (t.reopened) {
            return `<span class="pill pill-red"><i class="bi bi-exclamation-triangle-fill"></i> Changed after final confirmation</span>`
                + `<div class="help" style="color:var(--tf-red)">${esc(t.reopened.line || '')}</div>`
                + `<div class="help">the agent was told it was confirmed on ${esc(when(t.final_confirmed_at))}`
                + `${t.final_confirmed_by ? ' · ' + esc(t.final_confirmed_by) : ''} — finish the work again, then send the updated confirmation</div>`;
        }
        if (t.final_confirmed_at) {
            return `<span class="pill pill-green"><i class="bi bi-patch-check-fill"></i> Confirmed</span>`
                + `<div class="help">to the agent ${esc(when(t.final_confirmed_at))}${t.final_confirmed_by ? ' · ' + esc(t.final_confirmed_by) : ''}</div>`;
        }
        if (s === 'completed' || s === 'closed') { const [l, c] = STAGE[s]; return `<span class="pill ${c}">${esc(l)}</span>`; }
        if (t.ready && t.ready.ok) {
            return '<span class="pill pill-green"><i class="bi bi-check2-circle"></i> All departments confirmed</span>'
                + '<div class="help">ready for the final confirmation to the agent</div>';
        }
        return '<span class="pill pill-amber">Client accepted — reservations pending</span>';
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
        return pill(cls, noun === 'Vehicle' ? 'bi-truck-front' : 'bi-person', text, `${noun}: ${word}`, x.state === 'not_required' || x.state === 'no_vehicle_line' ? null : href);
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
        return `<button class="btn btn-sm" style="background:#842484;border-color:#842484;color:#fff" data-send="${t.id}">`
             + `<i class="bi bi-envelope-paper"></i> Send the confirmed itinerary</button>`;
    };


    const work = (state, at, by, done) => state === 'complete'
        ? `<span class="pill pill-green"><i class="bi bi-patch-check-fill"></i> ${done}</span>${at ? `<div class="help">${esc(when(at))}${by ? ' · ' + esc(by) : ''}</div>` : ''}`
        : (state === 'pending' ? '<span class="pill pill-amber">in progress</span>' : '<span class="help">nothing for this desk</span>');

    function renderChips() {
        const c = DATA.counts || {};
        document.getElementById('chips').innerHTML = CHIPS.map(([k, l]) => `<span class="chip ${FILTER === k ? 'on' : ''}" data-chip="${k}">${l}<span class="n">${c[k] ?? 0}</span></span>`).join('');
        document.querySelectorAll('[data-chip]').forEach(x => x.addEventListener('click', () => { FILTER = x.dataset.chip; render(); }));
    }

    function render() {
        renderChips();
        const tours = (DATA.tours || []).filter(t => FILTER === 'all' || t.group === FILTER);
        const tb = document.getElementById('rows');
        if (!tours.length) { tb.innerHTML = '<tr><td colspan="8" class="empty">No confirmed tours here.</td></tr>'; return; }
        tb.innerHTML = tours.map(t => {
            const p = t.progress || {}, h = p.hotels || {}, a = p.activities || {};
            const toHotel = CAN.hotel ? `${HOTELS}?tour=${t.id}` : null, toTransport = CAN.transport ? `${TRANSPORT}?tour=${t.id}` : null;
            const programme = CAN.board ? `${BOARD}/tour/${t.id}/programme` : (CAN.hotel ? `${HOTELS}/tour/${t.id}/programme` : (CAN.transport ? `${TRANSPORT}/tour/${t.id}/programme` : null));
            return `<tr class="trow" data-tour="${t.id}">
                <td><b>${esc(t.tour_no)}</b>${t.inquiry_no ? `<div class="help">inquiry ${esc(t.inquiry_no)}</div>` : ''}${t.handler_name ? `<div class="help" title="Handling agent — whose tour it is"><i class="bi bi-person-badge"></i> ${esc(t.handler_name)}</div>` : ''}</td>
                <td>${esc(t.agent_name || '—')}<div class="help">${esc(t.tour_name || '')}</div></td>
                <td>${t.pax_total ?? '—'}</td>
                <td style="white-space:nowrap">${fmt(t.arrival_date)} – ${fmtY(t.departure_date)}${t.nights != null ? `<div class="help">${t.nights} night${Number(t.nights) === 1 ? '' : 's'}</div>` : ''}</td>
                <td>${stagePill(t)}${Number(t.amending) ? ' <span class="pill pill-amber">being amended</span>' : ''}${t.problems ? ` <span class="pill pill-red">${t.problems} problem${t.problems === 1 ? '' : 's'}</span>` : ''}
                    ${t.quote_version ? `<div class="help">sold as v${t.quote_version} · ${esc(when(t.confirmed_at))}</div>` : ''}
                    ${t.stage === 'cancelled' && t.cancel_why ? `<div class="help" style="color:var(--tf-red)">${esc(t.cancel_why)}</div>` : ''}</td>
                <td><div class="td-strip">${pill(lane(h), 'bi-building', h.of ? `Hotels <b>${h.done}</b> of ${h.of}${h.problem ? ` · ${h.problem} problem` : (h.confirmed ? ` · ${h.confirmed} confirmed` : '')}` : 'Hotels —', h.of ? `${h.done} of ${h.of} stays with the voucher issued` : 'No hotel stays', h.of ? toHotel : null)}
                    ${pill(lane(a), 'bi-ticket-perforated', a.of ? `Activities <b>${a.done}</b> of ${a.of}${a.problem ? ` · ${a.problem} problem` : ''}` : 'Activities —', a.of ? `${a.done} of ${a.of} activity and ticket lines done` : 'No activities or tickets', a.of ? toHotel : null)}</div>
                    ${work(p.hotel_work, p.reservations_confirmed_at, p.reservations_confirmed_by, 'hotel work complete')}</td>
                <td><div class="td-strip">${crew(p.vehicle, 'Vehicle', toTransport)}${crew(p.driver, 'Driver', toTransport)}${crew(p.guide, 'Guide', toTransport)}</div>
                    ${work(p.transport_work, p.transport_complete_at, p.transport_complete_by, 'transport work complete')}</td>
                <td><div class="acts">
                    ${finalBtn(t)}
                    ${mailBtn(t)}
                    <a class="btn btn-light btn-sm" href="${INTAKE}?id=${t.intake_id}" title="The confirmed inquiry — the record of the sale, with its progress"><i class="bi bi-box-arrow-up-right"></i> Open</a>
                    <a class="btn btn-sm" style="background:#842484;border-color:#842484;color:#fff" href="${INTAKE}/comms/${t.intake_id}" title="Correspondence, attachments, agreed arrangements, follow-ups and hand-over"><i class="bi bi-chat-left-text"></i> Communications &amp; History</a>
                    ${CAN.board ? `<a class="btn btn-light btn-sm" href="${BOARD}/tour/${t.id}/record" title="The complete record of this tour, from the inquiry to today"><i class="bi bi-journal-text"></i> Complete record</a>` : ''}
                    ${programme ? `<a class="btn btn-light btn-sm" href="${programme}" title="The programme day by day, to print"><i class="bi bi-printer"></i> Programme</a>` : ''}
                </div></td></tr>`;
        }).join('');
        wireFinal();
        wireSend();
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
            row.innerHTML = `<td colspan="8" style="background:#fbfcfe">
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
                    <button class="btn btn-sm" style="background:#842484;border-color:#842484;color:#fff" id="sb_go-${t.id}">Send it</button>
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
                ? ' <a class="btn btn-sm mt-1" style="background:#842484;border-color:#842484;color:#fff;text-decoration:none" href="'
                  + INTAKE + '/comms/' + intake + '"><i class="bi bi-chat-left-text"></i> Send the confirmed itinerary</a>' : ''));
            setTimeout(load, 2500);
        }));
    }

    async function load() {
        const qs = new URLSearchParams({ q: document.getElementById('f_q').value || '', handler: document.getElementById('f_handler').value || '', from: document.getElementById('f_from').value || '', to: document.getElementById('f_to').value || '' }).toString();
        try {
            const r = await fetch(BASE + '/data?' + qs, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await r.json();
            if (j.status !== 200) { document.getElementById('rows').innerHTML = `<tr><td colspan="8" class="empty">${esc(j.message || j.msg || 'Could not load the tours.')}</td></tr>`; return; }
            DATA = j.data || { tours: [], counts: {} };
        } catch (e) {
            document.getElementById('rows').innerHTML = '<tr><td colspan="8" class="empty">Could not load the tours.</td></tr>';
            return;
        }
        render();
    }

    document.getElementById('f_q').addEventListener('input', () => { clearTimeout(window.__cq); window.__cq = setTimeout(load, 250); });
    ['f_handler', 'f_from', 'f_to'].forEach(id => document.getElementById(id).addEventListener('change', load));
    document.getElementById('f_clear').addEventListener('click', () => { ['f_handler', 'f_q', 'f_from', 'f_to'].forEach(id => document.getElementById(id).value = ''); FILTER = 'live'; load(); });
    // back from a desk (the tab shown again): read again, so what was done there shows here
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') load(); });
    load();
})();
</script>
</body>
</html>
