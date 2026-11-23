<?= view('layouts/head') ?>
<?php
/**
 * tf-rotation-r1 (25 Oct 2026) — Driver details & agreed rates: the rotation section of the Drivers & Guides master
 * (tour/transport_ops/allocations/registry). One row per Japanese-speaking driver already on the master (nothing duplicated):
 * in the rotation pool, willing to take 1–2 passenger tours at the car rate, the agreed car rate and van rate per km, notes,
 * Not Available periods — and what is still missing, flagged for the office to supply.
 */
$esc = static fn ($s) => esc((string) $s);
?>
<style>
    .tf { --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --p:#842484; --g:#78b848; --gold:#e0a526; --red:#b23a32; color:var(--tf-ink); }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:12px; }
    .tf .tf-title h4 { margin:0; font-weight:700; color:var(--p); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:900px; }
    .tf .drv { background:#fff; border:1px solid var(--tf-line); border-radius:12px; margin-bottom:12px; box-shadow:0 2px 8px rgba(20,20,30,.04); }
    .tf .drv .hd { display:flex; gap:12px; align-items:center; padding:12px 16px; border-bottom:1px solid var(--tf-line); flex-wrap:wrap; }
    .tf .drv .hd b { font-size:15px; }
    .tf .drv .bd { padding:12px 16px; display:grid; grid-template-columns:1.1fr 1fr 1fr; gap:14px; }
    .tf .drv .bd h6 { font-size:11.5px; text-transform:uppercase; letter-spacing:.05em; color:var(--tf-muted); margin:0 0 6px; font-weight:800; }
    .tf .pill { display:inline-block; padding:2px 9px; border-radius:999px; font-size:11.5px; font-weight:700; }
    .tf .pill-p { background:rgba(132,36,132,.12); color:var(--p); } .tf .pill-g { background:rgba(120,184,72,.2); color:#2d5a1a; } .tf .pill-a { background:#ffffff; border:1px solid #d9d3dd; color:#1a1a1a; } .tf .pill-r { background:#fbe9e7; color:var(--red); } .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .veh { font-size:12.5px; padding:4px 0; border-bottom:1px dashed #eef1f5; }
    .tf .form-check-label { font-size:13px; }
    .tf .form-control-sm { font-size:13px; }
    .tf .miss { color:var(--red); font-size:12.5px; }
    .tf .help { color:var(--tf-muted); font-size:12px; }
    .tf .btn-p { background:var(--p); color:#fff; border:0; } .tf .btn-p:hover { filter:brightness(1.08); color:#fff; }
    .tf .unav { font-size:12.5px; padding:4px 0; }
    .tf .sum { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
    .tf .sum span { background:#fff; border:1px solid var(--tf-line); border-radius:10px; padding:8px 14px; font-size:13px; }
    .tf .sum b { color:var(--p); }
    @media (max-width:991px) { .tf .drv .bd { grid-template-columns:1fr; } }
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
                        <h4>Driver details &amp; agreed rates</h4>
                        <div class="sub">The rotation section of the Drivers &amp; Guides master — every Japanese-speaking driver (JSD / JSCG) already on the master, nothing duplicated. Management marks the rotation pool; the office enters the agreed car rate and whether a van driver accepts 1–2 passenger tours at that rate. Anything still missing is flagged here.</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-light btn-sm" href="<?= base_url('master_files/crew_people?desk=transport') ?>"><i class="bi bi-person-vcard"></i> Drivers &amp; Guides master</a>
                        <a class="btn btn-light btn-sm" href="<?= base_url('tour/transport_ops/allocations') ?>"><i class="bi bi-arrow-left"></i> Pending Allocations</a>
                    </div>
                </div>
                <div class="sum" id="sum"></div>
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
    const fmt = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
    const api = (p, o) => fetch(BASE + p, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, o || {})).then(r => r.json());
    const post = (p, b) => api(p, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(b || {}) });
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2200 : undefined, showConfirmButton: icon !== 'success' });
    const body = document.getElementById('body');
    let CAN = false;

    async function load() {
        const j = await api('/registry/data');
        if (j.status !== 200) { body.innerHTML = '<div class="miss p-3">' + esc(j.message) + '</div>'; return; }
        CAN = j.data.can_edit;
        const d = j.data.drivers;
        const pool = d.filter(x => x.in_pool).length, optin = d.filter(x => x.car_rate_optin).length, vm = d.filter(x => !x.vehicle_ready).length, pm = d.filter(x => !x.payment_ready).length;
        document.getElementById('sum').innerHTML = `<span><b>${d.length}</b> Japanese-speaking drivers on the master</span><span><b>${pool}</b> in the rotation pool</span><span><b>${optin}</b> opted in to car-rate tours</span><span><b>${vm}</b> with vehicle details to supply</span><span><b>${pm}</b> with payment details to supply</span>
            <div class="help" style="flex-basis:100%">Vehicle details complete = vehicle type, roof type (vans), recorded guest capacity and a confirmed category — needed before he can be matched to any tour. Payment details complete = a rate per km on the Drivers &amp; Guides master (what a normal tour is paid at) and, when he has opted in, his agreed car rate. The opt-in is his general willingness; his acceptance of the car-rate terms is recorded separately for each tour when it is allocated.</div>`;
        if (!d.length) { body.innerHTML = '<div class="help p-3">No JSD / JSCG driver on the Drivers & Guides master yet.</div>'; return; }
        body.innerHTML = d.map(x => `<div class="drv" id="d-${x.id}">
            <div class="hd"><b>${esc(x.nickname || x.name)}</b><span class="help">${esc(x.name)} · ${esc(x.category)}${x.mobile ? ' · ' + esc(x.mobile) : ''}${x.languages ? ' · ' + esc(x.languages) : ''}</span>
                ${x.status !== 'active' ? '<span class="pill pill-grey">' + esc(x.status) + '</span>' : ''}${x.in_pool ? '<span class="pill pill-g">in rotation pool</span>' : '<span class="pill pill-grey">not in pool</span>'}${x.car_rate_optin ? '<span class="pill pill-a">accepts car-rate tours</span>' : ''}
                <span class="pill ${x.vehicle_ready ? 'pill-g' : 'pill-r'}" title="Can he be matched to a tour: vehicle type, roof (vans), recorded guest capacity, confirmed category">Vehicle details: ${x.vehicle_ready ? 'complete' : 'to supply — ' + x.vehicle_missing.map(esc).join(', ')}</span>
                <span class="pill ${x.payment_ready ? 'pill-g' : 'pill-r'}" title="Can an allocation be paid: a rate per km on the master; the agreed car rate when he has opted in">Payment details: ${x.payment_ready ? 'complete' : 'to supply — ' + x.payment_missing.map(esc).join(', ')}</span>
                ${x.payment_warnings.length ? '<span class="pill pill-a">' + x.payment_warnings.map(esc).join('; ') + '</span>' : ''}
                <span class="ms-auto help">${x.last_tour_end ? 'last tour ' + esc(x.last_tour_no) + ' ended ' + fmt(x.last_tour_end) + ' · free from ' + fmt(x.free_from) : 'no completed tour on record'}</span></div>
            <div class="bd">
                <div><h6>Vehicles on the master</h6>${x.vehicles.length ? x.vehicles.map(v => `<div class="veh"><b>${esc(v.reg_no || '—')}</b> ${esc(v.type_name)}${v.roof ? ' · ' + esc(v.roof) + ' roof' : (v.type === 'van' ? ' · <span class="miss">roof type not set</span>' : '')}${v.guests ? ' · ' + v.guests + ' guests recorded' + (v.capacity != null && v.capacity < v.guests ? ' (a van: used for ' + v.capacity + ')' : '') : ' · <span class="miss">guest capacity not recorded</span>'}${v.seats ? ' · ' + v.seats + ' seats' : ''}${v.current ? '' : ' <span class="help">(not current)</span>'}</div>`).join('') : '<div class="miss">No vehicle on file — add it on the Drivers & Guides master.</div>'}
                    <div class="help mt-2">Rate per km on the Drivers &amp; Guides master (paid on normal tours): ${x.master_rate_km ? '<b>LKR ' + Number(x.master_rate_km).toFixed(2) + '</b> (' + esc(x.master_rate_source || '') + ')' : '<span class="miss">none on file</span>'}</div></div>
                <div><h6>Rotation &amp; agreed rates</h6>
                    <div class="form-check mb-1"><input class="form-check-input" type="checkbox" id="pool-${x.id}" ${x.in_pool ? 'checked' : ''} ${CAN ? '' : 'disabled'}><label class="form-check-label" for="pool-${x.id}">In the approved rotation pool</label></div>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="optin-${x.id}" ${x.car_rate_optin ? 'checked' : ''} ${CAN && x.has_van ? '' : 'disabled'}><label class="form-check-label" for="optin-${x.id}">Willing to accept 1–2 passenger tours at the car rate <span class="help">(general opt-in; accepted again per tour)</span>${x.has_van ? '' : ' <span class="help">(van drivers only)</span>'}</label></div>
                    <div class="row g-2"><div class="col-6"><label class="help">Agreed car rate, LKR/km <span class="help">(car-rate van tours only)</span></label><input class="form-control form-control-sm" id="car-${x.id}" inputmode="decimal" value="${x.agreed_car_rate_km ?? ''}" ${CAN ? '' : 'disabled'}></div><div class="col-6"><label class="help">Agreed van rate, LKR/km <span class="help">(checked against the master)</span></label><input class="form-control form-control-sm" id="van-${x.id}" inputmode="decimal" value="${x.agreed_van_rate_km ?? ''}" ${CAN ? '' : 'disabled'}></div></div>
                    <input class="form-control form-control-sm mt-2" id="rn-${x.id}" placeholder="Rate note (basis, date agreed)" value="${esc(x.rate_note || '')}" ${CAN ? '' : 'disabled'}>
                    <input class="form-control form-control-sm mt-2" id="nt-${x.id}" placeholder="Notes (languages, availability, remarks)" value="${esc(x.notes || '')}" ${CAN ? '' : 'disabled'}>
                    ${CAN ? `<button class="btn btn-sm btn-p mt-2" data-save="${x.id}"><i class="bi bi-save"></i> Save</button>` : ''} <span class="help">${x.updated_at ? 'saved ' + esc(x.updated_at) + ' by ' + esc(x.updated_by) : ''}</span></div>
                <div><h6>Not Available</h6>${x.unavailable.length ? x.unavailable.map(u => `<div class="unav">${fmt(u.date_from)} – ${fmt(u.date_to)} · ${esc(u.reason)} <span class="help">(${esc(u.recorded_by)})</span>${CAN ? ` <button class="btn btn-link btn-sm p-0" data-clear="${u.id}">clear</button>` : ''}</div>`).join('') : '<div class="help">No period recorded.</div>'}
                    ${CAN ? `<div class="row g-2 mt-1"><div class="col-6"><input type="date" class="form-control form-control-sm" id="uf-${x.id}"></div><div class="col-6"><input type="date" class="form-control form-control-sm" id="ut-${x.id}"></div><div class="col-12"><input class="form-control form-control-sm" id="ur-${x.id}" placeholder="Reason"></div><div class="col-12"><button class="btn btn-sm btn-light" data-unav="${x.id}"><i class="bi bi-calendar-x"></i> Record Not Available</button></div></div>` : ''}</div>
            </div></div>`).join('');
        body.querySelectorAll('[data-save]').forEach(b => b.addEventListener('click', async () => {
            const id = b.dataset.save;
            const j = await post('/registry/' + id, { in_pool: document.getElementById('pool-' + id).checked ? 1 : 0, car_rate_optin: document.getElementById('optin-' + id).checked ? 1 : 0,
                agreed_car_rate_km: document.getElementById('car-' + id).value, agreed_van_rate_km: document.getElementById('van-' + id).value, rate_note: document.getElementById('rn-' + id).value, notes: document.getElementById('nt-' + id).value });
            toast(j.status === 200 ? 'success' : 'error', j.message); if (j.status === 200) load();
        }));
        body.querySelectorAll('[data-unav]').forEach(b => b.addEventListener('click', async () => {
            const id = b.dataset.unav;
            const j = await post('/unavailable', { person_id: Number(id), date_from: document.getElementById('uf-' + id).value, date_to: document.getElementById('ut-' + id).value, reason: document.getElementById('ur-' + id).value });
            toast(j.status === 200 ? 'success' : 'error', j.message); if (j.status === 200) load();
        }));
        body.querySelectorAll('[data-clear]').forEach(b => b.addEventListener('click', async () => { const j = await post('/unavailable/' + b.dataset.clear + '/clear', {}); if (j.status === 200) load(); else toast('error', j.message); }));
    }
    load();
})();
</script>
</body>
</html>
