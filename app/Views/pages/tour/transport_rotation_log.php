<?= view('layouts/head') ?>
<?php
/** tf-rotation-r1 (25 Oct 2026) — the rotation report: every Bypass Rotation and Not Available, and the acceptances (tour/transport_ops/allocations/log). */
$esc = static fn ($s) => esc((string) $s);
?>
<style>
    .tf { --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --p:#842484; --g:#78b848; --red:#b23a32; color:var(--tf-ink); }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:12px; }
    .tf .tf-title h4 { margin:0; font-weight:700; color:var(--p); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; }
    .tf .flt { display:flex; gap:8px; flex-wrap:wrap; align-items:end; background:#fff; border:1px solid var(--tf-line); border-radius:12px; padding:12px 14px; margin-bottom:12px; }
    .tf .flt label { font-size:11.5px; color:var(--tf-muted); display:block; }
    .tf table { width:100%; font-size:12.5px; background:#fff; border:1px solid var(--tf-line); border-radius:12px; overflow:hidden; }
    .tf th { background:var(--p); color:#fff; font-weight:600; padding:8px 10px; text-align:left; font-size:12px; }
    .tf td { padding:8px 10px; border-bottom:1px solid #eef1f5; vertical-align:top; }
    .tf .pill { display:inline-block; padding:2px 9px; border-radius:999px; font-size:11.5px; font-weight:700; }
    .tf .ev-bypass { background:#fbe9e7; color:var(--red); } .tf .ev-not_available { background:#ffffff; border:1px solid #d9d3dd; color:#1a1a1a; } .tf .ev-accepted { background:rgba(120,184,72,.2); color:#2d5a1a; } .tf .ev-car_rate_accepted { background:rgba(132,36,132,.12); color:var(--p); }
    .tf .help { color:var(--tf-muted); font-size:12px; }
    .tf .btn-p { background:var(--p); color:#fff; border:0; }
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
                    <div><h4>Rotation report — Bypass &amp; Not Available</h4><div class="sub">Every time the rotation was bypassed (who, why, the driver passed over) and every Not Available recorded, with the acceptances and car-rate acceptances for reference. Nothing here can be edited.</div></div>
                    <div><a class="btn btn-light btn-sm" href="<?= base_url('tour/transport_ops/allocations') ?>"><i class="bi bi-arrow-left"></i> Pending Allocations</a></div>
                </div>
                <div class="flt">
                    <div><label>Event</label><select class="form-select form-select-sm" id="f_event"><option value="">Bypass + Not Available + acceptances</option><option value="bypass">Bypass Rotation only</option><option value="not_available">Not Available only</option><option value="accepted">Accepted by rotation</option><option value="car_rate_accepted">Car-rate acceptances</option></select></div>
                    <div><label>From</label><input type="date" class="form-control form-control-sm" id="f_from"></div>
                    <div><label>To</label><input type="date" class="form-control form-control-sm" id="f_to"></div>
                    <div><label>Tour / driver / reason / user</label><input class="form-control form-control-sm" id="f_q" placeholder="search"></div>
                    <div><button class="btn btn-sm btn-p" id="f_go"><i class="bi bi-search"></i> Show</button></div>
                    <div><button class="btn btn-sm btn-light" id="f_csv"><i class="bi bi-download"></i> CSV</button></div>
                </div>
                <div id="body"><div class="help p-3">Loading…</div></div>
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
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const body = document.getElementById('body');
    let ROWS = [];
    const LABEL = { bypass: 'Bypass Rotation', not_available: 'Not Available', accepted: 'Accepted (rotation)', car_rate_accepted: 'Car-rate accepted' };
    function detail(r) {
        const d = r.details || {};
        if (r.event === 'bypass') return veh + 'Passed over: ' + esc(d.passed_over ? d.passed_over.name : '—') + (d.why_out?.length ? ' · set aside: ' + esc(d.why_out.join('; ')) : ' · set aside: rotation order') + ' · warning acknowledged · payment: ' + pay(d.payment);
        if (r.event === 'not_available') return 'Period ' + esc(d.date_from) + ' – ' + esc(d.date_to) + (d.from_registry ? ' (recorded on the registry)' : ' (next driver in turn proposed)');
        const veh = d.required_class ? 'Required ' + esc(d.required_class) + ' · actual ' + esc(d.actual_vehicle || '—') + '. ' : '';
        const pay = x => x ? (x.rate_km != null ? 'LKR ' + Number(x.rate_km).toFixed(2) + '/km (' + esc(x.source || '') + ') × ' + esc(x.km_text || (Number(x.km).toLocaleString() + ' km')) + ' = LKR ' + Number(x.amount).toLocaleString('en-GB', { minimumFractionDigits: 2 }) : esc(x.source || '') + ' — rate not on file') : '';
        if (r.event === 'car_rate_accepted') return 'Accepted by the driver for this tour. ' + pay(d) + (d.actual_vehicle ? ' · vehicle ' + esc(d.actual_vehicle) : '');
        if (r.event === 'accepted') return veh + 'Payment: ' + pay(d.payment) + (d.eligible?.length ? ' · eligible in order: ' + esc(d.eligible.join(', ')) : '');
        return '';
    }
    async function load() {
        const q = new URLSearchParams({ event: document.getElementById('f_event').value, from: document.getElementById('f_from').value, to: document.getElementById('f_to').value, q: document.getElementById('f_q').value });
        const j = await fetch(BASE + '/log/data?' + q, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());
        if (j.status !== 200) { body.innerHTML = '<div class="help p-3">' + esc(j.message) + '</div>'; return; }
        ROWS = j.data;
        if (!ROWS.length) { body.innerHTML = '<div class="help p-3">Nothing recorded for this selection.</div>'; return; }
        body.innerHTML = `<div style="overflow:auto"><table><thead><tr><th>When</th><th>Event</th><th>Tour</th><th>Driver</th><th>Reason</th><th>Details</th><th>By</th></tr></thead><tbody>
            ${ROWS.map(r => `<tr><td>${esc(r.at)}</td><td><span class="pill ev-${esc(r.event)}">${esc(LABEL[r.event] || r.event)}</span></td><td><b>${esc(r.tour_no || '')}</b></td><td>${esc(r.person_name || '')}</td><td>${esc(r.reason || '')}</td><td class="help">${detail(r)}</td><td>${esc(r.by_user)}</td></tr>`).join('')}</tbody></table></div>`;
    }
    document.getElementById('f_go').addEventListener('click', load);
    document.getElementById('f_csv').addEventListener('click', () => {
        const lines = [['When', 'Event', 'Tour', 'Driver', 'Reason', 'Details', 'By'].join(',')].concat(ROWS.map(r => [r.at, LABEL[r.event] || r.event, r.tour_no || '', r.person_name || '', r.reason || '', detail(r).replace(/&amp;/g, '&'), r.by_user].map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',')));
        const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([lines.join('\n')], { type: 'text/csv' })); a.download = 'rotation-report.csv'; a.click();
    });
    load();
})();
</script>
</body>
</html>
