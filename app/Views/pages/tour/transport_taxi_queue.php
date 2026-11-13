<?= view('layouts/head') ?>
<?php
/**
 * tf-taxiq-r1 (3 Nov 2026) — the combined taxi queue: one lane for cars and vans together
 * (tour/transport_ops/allocations/queue). Every driver the Super Administrator has put in the rotation pool, in the
 * order the record gives: the one whose last tour ended longest ago is next. Rest, tours and absences come from the
 * allocation record, never from a hand-sorted list — the positions cannot be rearranged to favour anybody.
 *
 * It shows and it records a driver's answer to a car-rate offer. It allocates nobody: the allocation itself stays
 * on Pending Allocations and the Transport desk, with the existing rules, warnings and privileges untouched.
 */
?>
<style>
    /* tf-taxiq-r2: the supplied mockups — orchid purple headings (#842484), Avenir green actions (#78b848),
       the mockup's card sizes, spacing and status colours, with the status spelled out in words on every card. */
    .tq { --ink:#1d2733; --muted:#5f6f80; --line:#e3e7ee; --p:#842484; --p-soft:rgba(132,36,132,.08); --g:#78b848;
          --g-soft:#eef7e4; --g-ink:#3f6b1f; --amber:#1a1a1a; --amber-soft:#ffffff; --red:#8a1c30; --red-soft:#fbe9e7;
          --grey-soft:#eef1f5; color:var(--ink); font-size:14px; }
    .tq .band { background:var(--p); color:#fff; border-radius:14px; padding:18px 22px; margin-bottom:18px; }
    .tq .band .eyebrow { font-size:11.5px; letter-spacing:.09em; text-transform:uppercase; opacity:.85; font-weight:700; }
    .tq .band h4 { margin:6px 0 4px; font-weight:800; font-size:26px; color:#fff; letter-spacing:-.01em; }
    .tq .band .sub { font-size:13.5px; opacity:.93; max-width:860px; line-height:1.55; }
    .tq .band .links { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
    .tq .band .links a { background:rgba(255,255,255,.14); color:#fff; border:1px solid rgba(255,255,255,.3);
                         border-radius:9px; padding:6px 12px; font-size:12.5px; font-weight:600; text-decoration:none; }
    .tq .band .links a:hover { background:rgba(255,255,255,.24); color:#fff; }
    .tq .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(215px,1fr)); gap:12px; margin-bottom:18px; }
    .tq .stat { background:#fff; border:1px solid var(--line); border-radius:12px; padding:14px 18px; }
    .tq .stat .n { font-size:24px; font-weight:800; color:var(--p); line-height:1.1; }
    .tq .stat .l { font-size:12.5px; color:var(--muted); margin-top:3px; }
    .tq .card2 { background:#fff; border:1px solid var(--line); border-radius:14px; margin-bottom:18px;
                 box-shadow:0 1px 3px rgba(16,24,40,.05); }
    .tq .card2 > .hd { padding:16px 20px 12px; }
    .tq .card2 > .hd h6 { margin:0; font-weight:800; font-size:17px; color:var(--p); }
    .tq .card2 > .hd .sub { font-size:12.5px; color:var(--muted); margin-top:3px; line-height:1.5; }
    .tq .card2 > .bd { padding:4px 20px 20px; }
    /* the approved mockup's lane: one horizontal line of cards, scrolled sideways, with the connecting rail */
    .tq .lane-wrap { position:relative; }
    .tq .lane { display:flex; gap:14px; overflow-x:auto; padding:16px 2px 14px; scroll-snap-type:x proximity; }
    .tq .lane::before { content:''; position:absolute; left:0; right:0; top:50%; height:4px; background:var(--p-soft); border-radius:4px; z-index:0; }
    .tq .lane::-webkit-scrollbar { height:8px; }
    .tq .lane::-webkit-scrollbar-thumb { background:#d4dae3; border-radius:8px; }
    .tq .drv { position:relative; z-index:1; flex:0 0 244px; border:1.5px solid var(--line); border-radius:14px;
               padding:16px; background:#fff; scroll-snap-align:start; }
    .tq .drv.next { border-color:var(--g); background:var(--g-soft); }
    .tq .drv .pos { display:inline-block; border-radius:999px; padding:4px 11px; font-size:10.5px;
                    font-weight:800; letter-spacing:.05em; }
    .tq .drv .ico { font-size:40px; line-height:1; margin:14px 0 8px; }
    .tq .drv .nm { font-weight:800; font-size:16.5px; line-height:1.25; }
    .tq .drv .ty { font-size:11.5px; font-weight:800; color:var(--p); letter-spacing:.07em; margin-top:2px; }
    .tq .drv .st { font-size:12.5px; color:var(--muted); margin-top:8px; line-height:1.5; }
    .tq .drv .why { font-size:12px; color:var(--red); margin-top:6px; line-height:1.5; }
    .tq .drv .last { font-size:11.5px; color:var(--muted); margin-top:8px; padding-top:8px; border-top:1px dashed var(--line); }
    .tq .s-ready { background:var(--g-soft); color:var(--g-ink); }
    .tq .s-tour  { background:var(--amber-soft); border:1px solid #d9d3dd; color:var(--amber); }
    .tq .s-alloc { background:var(--p-soft); color:var(--p); }
    .tq .s-rest  { background:var(--grey-soft); color:var(--muted); }
    .tq .s-away  { background:var(--amber-soft); border:1px solid #d9d3dd; color:var(--amber); }
    .tq .s-block { background:var(--red-soft); color:var(--red); }
    .tq .rate { border:1px solid var(--line); border-radius:12px; padding:14px 16px; height:100%; }
    .tq .rate .lbl { font-size:12px; color:var(--muted); }
    .tq .rate .val { font-weight:800; font-size:21px; margin-top:4px; letter-spacing:-.01em; }
    .tq .rate .val small { font-size:12px; font-weight:600; color:var(--muted); }
    .tq .rate.pick { border-color:var(--p); background:var(--p-soft); }
    .tq .btn-g { background:var(--g); border:0; color:#fff; font-weight:700; border-radius:10px; padding:10px 20px; font-size:14px; }
    .tq .btn-g:hover:not(:disabled) { background:#6aa63f; color:#fff; }
    .tq .btn-p { background:var(--p); border:0; color:#fff; font-weight:700; border-radius:10px; padding:10px 20px; font-size:14px; }
    .tq .btn-p:hover:not(:disabled) { background:#6d1d6d; color:#fff; }
    .tq .btn-q { background:#fff; border:1px solid var(--line); color:var(--ink); font-weight:600; border-radius:10px; padding:10px 18px; font-size:14px; }
    .tq .note { background:var(--p-soft); border:1px solid rgba(132,36,132,.18); color:var(--ink);
                border-radius:12px; padding:14px 16px; font-size:13.5px; line-height:1.55; }
    .tq .warn { background:var(--amber-soft); border:1px solid rgba(183,121,31,.25); color:#7a5210;
                border-radius:12px; padding:14px 16px; font-size:13.5px; line-height:1.55; }
    .tq .help { color:var(--muted); font-size:12.5px; line-height:1.55; }
    .tq .foot { font-size:12.5px; color:var(--muted); line-height:1.7; }
    .tq .req { border:1px solid var(--line); border-radius:12px; padding:14px 16px; margin-bottom:10px; }
    .tq .form-control, .tq .form-select { font-size:14px; border-radius:9px; }
    .tq label.lb { font-size:12px; color:var(--muted); font-weight:600; display:block; margin-bottom:4px; }
    @media (max-width:575.98px) { .tq .band h4 { font-size:21px; } .tq .drv { flex-basis:85%; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tq" id="tq">
            <div class="container-fluid">
                <?= view('layouts/tourdesk_bar', ['tourdesk_desk_only' => 'transport']) ?>
                <?= view('layouts/tourdesk_menu') ?>
                <div class="band">
                    <div class="eyebrow">Transport Operations</div>
                    <h4>🚕 Automatic driver allocation</h4>
                    <div class="sub">One fair taxi queue for cars and vans together. The drivers are those the Super Administrator has put in the rotation pool on the Drivers &amp; Guides master — nothing is entered twice. The order is read from the allocation record; it cannot be rearranged by hand to favour a driver.</div>
                    <div class="links">
                        <a href="<?= base_url('tour/transport_ops/allocations') ?>">Pending Allocations</a>
                        <a href="<?= base_url('tour/transport_ops/allocations/registry') ?>">Driver details &amp; agreed rates</a>
                        <a href="<?= base_url('tour/transport_ops/allocations/log') ?>">Bypass &amp; Not Available report</a>
                    </div>
                </div>

                <div class="stats" id="stats"></div>

                <div class="card2">
                    <div class="hd">
                        <h6>Combined taxi lane</h6>
                        <div class="sub">Cars and vans in one queue, nearest turn first. The vehicle picture is a label only; the position comes from the record of who last worked.</div>
                    </div>
                    <div class="bd"><div class="lane-wrap"><div class="lane" id="lane"><div class="help">Loading…</div></div></div>
                        <div class="help">Scroll sideways to see the whole queue. Positions cannot be rearranged by hand to favour a driver.</div></div>
                </div>

                <div id="next_box"></div>
                <div id="approvals"></div>

                <div class="card2">
                    <div class="hd"><h6>Allocation rules</h6></div>
                    <div class="bd foot">
                        Drivers &amp; Guides master → the Super Administrator's rotation pool → this combined queue → a confirmed tour → the Transport team's own confirmation.<br>
                        <b><?= (int) ($rest_days ?? 5) ?> full days of rest</b> after a tour ends, before a driver returns to the lane.<br>
                        A driver who declines a car-rate offer keeps his turn: the refusal is recorded and costs him nothing.<br>
                        Safety, passenger capacity, language, overlap and availability rules stay in force, and rest exceptions need their own approval.<br>
                        A van taking a small tour at the car rate needs the driver's agreement <b>and</b> approval by somebody else before it can be allocated.
                    </div>
                </div>
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
    const CAN  = <?= !empty($can_allocate) ? 'true' : 'false' ?>;
    const esc = s => String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    const money = v => v === null || v === undefined || v === '' ? '—' : 'Rs. ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const day = s => s ? new Date(s + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) : '—';
    let D = null;

    // the mockup's words and colours, one per state — spelled out, never a colour on its own
    const STATE = {
        ready:   ['READY',            's-ready'],
        on_tour: ['ON TOUR',          's-tour'],
        allocated: ['ALLOCATED',      's-alloc'],
        resting: ['RESTING',          's-rest'],
        away:    ['NOT AVAILABLE',    's-away'],
        blocked: ['DETAILS MISSING',  's-block'],
    };

    async function load() {
        const r = await fetch(BASE + '/queue/data', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const j = await r.json();
        if (j.status !== 200) { document.getElementById('lane').innerHTML = '<div class="help">' + esc(j.message || 'The queue could not be read.') + '</div>'; return; }
        D = j.data;
        render();
    }

    function render() {
        const q = D.queue || [];
        const next = q.find(x => x.state === 'ready');
        document.getElementById('stats').innerHTML =
              `<div class="stat"><div class="n">${q.length}</div><div class="l">drivers in the rotation pool</div></div>`
            + `<div class="stat"><div class="n">1</div><div class="l">combined queue — cars and vans</div></div>`
            + `<div class="stat"><div class="n">${D.rest_days}</div><div class="l">full days of rest after a tour</div></div>`
            + `<div class="stat"><div class="n" style="font-size:17px;padding-top:5px">${next ? esc(next.name) : 'Nobody ready'}</div>`
            + `<div class="l">${next ? 'next in line · ' + esc(next.kind === 'van' ? 'Van' : 'Car') : 'no driver is ready today'}</div></div>`;

        document.getElementById('lane').innerHTML = q.length ? q.map(d => {
            const [label, cls] = STATE[d.state] || ['—', 's-rest'];
            const isNext = next && d.person_id === next.person_id;
            return `<div class="drv ${isNext ? 'next' : ''}">
                <span class="pos ${isNext ? 's-ready' : cls}">#${d.position} ${isNext ? 'NEXT IN LINE' : esc(label)}</span>
                <div class="ico">${d.kind === 'van' ? '🚐' : (d.kind === 'car' ? '🚗' : '❔')}</div>
                <div class="nm">${esc(d.name)}</div>
                <div class="ty">${esc(d.vehicle ? (d.kind === 'van' ? 'VAN' : 'CAR') + (d.vehicle.reg ? ' · ' + d.vehicle.reg : '') : 'NO VEHICLE ON THE MASTER')}</div>
                <div class="st">${esc(d.state === 'blocked' ? 'Ready in turn, but cannot be allocated yet' : d.note)}${d.vehicle && d.vehicle.guests ? ' · up to ' + d.vehicle.guests + ' guests' : ''}</div>
                ${d.state === 'blocked' && (d.blocked_why || []).length ? `<div class="why">Missing on the master: ${(d.blocked_why || []).map(esc).join('; ')}</div>` : ''}
                <div class="last">${d.last_tour_end
                    ? 'last tour ended ' + esc(day(d.last_tour_end)) + (d.last_tour_no ? ' · ' + esc(d.last_tour_no) : '')
                    : (d.holds_count ? 'his turn counted from ' + esc(day(d.last_served)) + ' · ' + d.holds_count + (d.holds_count > 1 ? ' tours held' : ' tour held') : 'no tour on the record yet')}</div>
            </div>`;
        }).join('') : '<div class="help">Nobody is in the rotation pool yet. Put drivers in it on <b>Driver details &amp; agreed rates</b>.</div>';

        renderNext(next);
        renderApprovals();
    }

    function renderNext(next) {
        const t = D.next_tour, cr = D.car_rate;
        const box = document.getElementById('next_box');
        if (!t) {
            box.innerHTML = `<div class="card2"><div class="hd"><h6>Next allocation</h6></div>
                <div class="bd help">No tour is waiting for a driver. When one is confirmed it appears here, with whose turn it is.</div></div>`;
            return;
        }
        const head = `<div class="hd"><h6>Next allocation · ${esc(t.guests ?? '?')} passenger${Number(t.guests) === 1 ? '' : 's'}</h6>`
            + `<div class="sub">${esc(t.tour_no || '')}${t.tour_name ? ' · ' + esc(t.tour_name) : ''} · ${esc(day(t.arrival))} – ${esc(day(t.departure))}${t.required_class ? ' · needs ' + esc(t.required_class) : ''}</div></div>`;
        if (!cr) {
            box.innerHTML = `<div class="card2">${head}<div class="bd">`
                + (next ? `<div style="font-size:15px">Whose turn it is: <b>${esc(next.name)}</b>${next.vehicle && next.vehicle.reg ? ' · ' + esc(next.vehicle.reg) : ''}</div>` : '<div class="help">Nobody in the lane is ready for it today.</div>')
                + `<div class="help mt-2">Allocate it on Pending Allocations, where the capacity, language, overlap and rest checks run and a bypass has to be explained.</div></div></div>`;
            return;
        }
        box.innerHTML = `<div class="card2">${head}<div class="bd">
            <div class="warn mb-3">🚐 <b>A van is next, and this tour would fit a car.</b> ${esc(cr.name)} may take it at the car rate if he agrees. His answer is recorded either way, and declining does not cost him his turn.</div>
            <div class="row g-3">
                <div class="col-md-3"><div class="rate"><div class="lbl">Driver</div><div class="val" style="font-size:16px">${esc(cr.name)}</div><div class="lbl">Van · next in line</div></div></div>
                <div class="col-md-3"><div class="rate"><div class="lbl">Normal van rate</div><div class="val">${money(cr.van_rate_km)}<small> / km</small></div></div></div>
                <div class="col-md-3"><div class="rate"><div class="lbl">Car rate on his record</div><div class="val">${money(cr.car_rate_km)}<small> / km</small></div></div></div>
                <div class="col-md-3"><div class="rate pick"><label class="lb">Rate he agrees to (per km)</label>
                    <input class="form-control" id="cr_rate" value="${cr.car_rate_km !== null && cr.car_rate_km !== undefined ? cr.car_rate_km : ''}" placeholder="rupees per km"></div></div>
            </div>
            <div class="row g-3 mt-1 align-items-end">
                <div class="col-md-4"><label class="lb">Driver agrees?</label>
                    <select class="form-select" id="cr_choice"><option value="">Choose…</option><option value="yes">Yes — he agrees</option><option value="no">No — he declines</option></select></div>
                <div class="col-md-5"><label class="lb">What he said (kept on the record)</label>
                    <input class="form-control" id="cr_note" placeholder="e.g. agreed on the phone, 3 Nov"></div>
                <div class="col-md-3">${CAN ? `<button class="btn-g w-100" id="cr_go">Request Pilot approval</button>` : '<span class="help">Recording an answer needs the Driver Allocation privilege.</span>'}</div>
            </div>
            <div id="cr_msg" class="mt-3" style="font-size:13.5px;font-weight:700"></div>
            <div class="help mt-2">Approval is required before the van is allocated at the car rate. Recording the answer allocates nothing and moves nobody's turn.</div>
        </div></div>`;
        wireCarRate(cr, t);
    }

    function renderApprovals() {
        const rs = D.requests || [];
        const box = document.getElementById('approvals');
        if (!rs.length) { box.innerHTML = ''; return; }
        box.innerHTML = `<div class="card2"><div class="hd"><h6>Car rates waiting for approval</h6>
            <div class="sub">A van at the car rate is approved by somebody other than the person who asked. Until then the tour cannot be allocated to that driver.</div></div>
            <div class="bd">` + rs.map(r => {
                const mine = String(r.requested_by_id || '') === String(D.me_id || '');
                return `<div class="req">
                    <div style="font-size:15px"><b>${esc(r.person_name || '')}</b> — ${money(r.agreed_rate_km)}<small class="help"> / km</small>
                        ${r.tour_no ? ' · ' + esc(r.tour_no) : ''}${r.guests ? ' · ' + esc(r.guests) + ' passengers' : ''}</div>
                    <div class="help">asked by ${esc(r.requested_by || '')} on ${esc(String(r.requested_at || '').slice(0, 16))}${r.driver_said ? ' — “' + esc(r.driver_said) + '”' : ''}</div>
                    <div class="d-flex flex-wrap gap-2 align-items-end mt-2">
                        ${D.can_approve && !mine
                            ? `<div style="flex:1;min-width:240px"><label class="lb">Note (kept with the decision)</label><input class="form-control" id="ap_note_${r.id}"></div>
                               <button class="btn-g" data-ap="${r.id}">Approve</button><button class="btn-q" data-rf="${r.id}">Refuse</button>`
                            : `<span class="help">${mine ? 'You asked for this one, so somebody else approves it.' : 'Approving needs the Pricing privilege (Pilot → Pricing).'}</span>`}
                    </div>
                    <div id="ap_msg_${r.id}" class="mt-2" style="font-size:13px;font-weight:700"></div></div>`;
            }).join('') + '</div></div>';
        wireApprovals();
    }

    function arm(btn, label, onFire, say) {
        // the answer is confirmed in place — no browser dialog, and a double-click cannot decide
        if (btn.dataset.armed === '1' && Date.now() - Number(btn.dataset.at || 0) < 500) { return true; }
        if (btn.dataset.armed !== '1') {
            btn.dataset.armed = '1'; btn.dataset.at = String(Date.now()); btn.dataset.was = btn.textContent;
            btn.textContent = label;
            say();
            setTimeout(() => { if (btn.dataset.armed === '1') { btn.dataset.armed = ''; btn.textContent = btn.dataset.was; } }, 10000);
            return true;
        }
        btn.dataset.armed = ''; btn.textContent = btn.dataset.was;
        onFire();
        return false;
    }

    function wireCarRate(cr, t) {
        const say = (x, bad) => { const el = document.getElementById('cr_msg'); if (el) { el.textContent = x || ''; el.style.color = bad ? '#8a1c30' : '#3f6b1f'; } };
        const go = document.getElementById('cr_go');
        if (!go) { return; }
        go.addEventListener('click', () => {
            const choice = document.getElementById('cr_choice').value;
            if (!choice) { say('Choose whether he agreed or declined.', true); return; }
            const agrees = choice === 'yes';
            arm(go, agrees ? 'Yes — record it and ask for approval' : 'Yes — record that he declined',
                async () => {
                    go.disabled = true;
                    const r = await fetch(BASE + '/queue/car_rate/' + cr.person_id, {
                        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ agrees: agrees, rate: document.getElementById('cr_rate').value.trim(),
                            note: document.getElementById('cr_note').value.trim(),
                            job_id: t.job_id || null, tour_id: t.tour_id || null, tour_no: t.tour_no || null,
                            guests: t.guests || null, van_rate: cr.van_rate_km, car_rate: cr.car_rate_km })
                    });
                    const j = await r.json();
                    go.disabled = false;
                    say(j.message || 'Recorded.', j.status !== 200);
                    if (j.status === 200) { setTimeout(load, 1200); }
                },
                () => say(agrees ? 'This records his agreement and asks for approval. It allocates nothing.' : 'This records the refusal. His place in the queue is unchanged.'));
        });
    }

    function wireApprovals() {
        const decide = (id, yes, btn) => {
            const say = (x, bad) => { const el = document.getElementById('ap_msg_' + id); if (el) { el.textContent = x || ''; el.style.color = bad ? '#8a1c30' : '#3f6b1f'; } };
            arm(btn, yes ? 'Yes — approve it' : 'Yes — refuse it',
                async () => {
                    btn.disabled = true;
                    const r = await fetch(BASE + '/queue/approval/' + id, {
                        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ approve: yes, note: (document.getElementById('ap_note_' + id) || {}).value || '' })
                    });
                    const j = await r.json();
                    btn.disabled = false;
                    say(j.message || 'Recorded.', j.status !== 200);
                    if (j.status === 200) { setTimeout(load, 1500); }
                },
                () => say(yes ? 'This approves the car rate; the tour may then be allocated to him.' : 'This refuses it; he keeps his place in the queue.'));
        };
        document.querySelectorAll('[data-ap]').forEach(b => b.addEventListener('click', () => decide(b.dataset.ap, true, b)));
        document.querySelectorAll('[data-rf]').forEach(b => b.addEventListener('click', () => decide(b.dataset.rf, false, b)));
    }

    load();
})();
</script>

</body>
</html>
