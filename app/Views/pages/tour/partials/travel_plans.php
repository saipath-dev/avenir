<?php
/**
 * tf-travel-r1 (24 Nov 2026) — the Travel Plans tab of the Tour Inquiry, to Shantha's Passenger Travel Plans mock-up:
 * the party at a glance, one card per early departure (who, the flight, the pickup, the transfer), the group after the
 * departure, the room plan by night, Preview changes and Save. Server: Tour\TravelPlansController, TourFlow\TravelPlans.
 */
?>
<style id="tp-css">
.tp, .tp-mod { --p:#842484; --pd:#5b195b; --g:#78b848; --gd:#4e8a26; --ln:#e6dae6; --soft:#f8f1f8; --ink:#2b2b33; --mut:#6b6f7b; --amb:#fff4dc; --ambd:#8a5a00; }
.tp { font-size:13.5px; color:var(--ink); }
.tp *, .tp-mod * { box-sizing:border-box; }
.tp-top { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin:4px 0 14px; }
.tp-top h3 { margin:0; font-size:22px; font-weight:800; color:var(--p); flex:1 1 auto; }
.tp-btn { display:inline-flex; align-items:center; gap:7px; border:1.5px solid var(--g); background:#fff; color:var(--gd); border-radius:9px; padding:7px 14px; font-weight:700; font-size:13px; cursor:pointer; white-space:nowrap; }
.tp-btn:hover { background:#f1f8ea; } .tp-btn.solid { background:var(--g); color:#fff; } .tp-btn.solid:hover { background:var(--gd); }
.tp-btn.p { border-color:var(--p); color:var(--p); } .tp-btn.p:hover { background:var(--soft); } .tp-btn.red { border-color:#d9a3a3; color:#b3261e; } .tp-btn[disabled] { opacity:.5; cursor:default; }
.tp-cards { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:14px; }
.tp-card { background:#fff; border:1px solid var(--ln); border-radius:12px; padding:12px 14px; display:flex; gap:12px; align-items:center; }
.tp-card i { font-size:26px; color:var(--p); } .tp-card b { font-size:22px; font-weight:800; } .tp-card span { color:#3d3340; } .tp-card small { display:block; color:var(--mut); font-size:12px; }
.tp-card.date { background:var(--soft); }
.tp-grid { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:14px; align-items:start; }
.tp-box { background:#fff; border:1px solid var(--ln); border-radius:12px; overflow:hidden; margin-bottom:14px; }
.tp-box > .h { background:var(--p); color:#fff; padding:11px 16px; font-weight:800; font-size:17px; display:flex; gap:10px; align-items:center; }
.tp-box > .h .sp { flex:1; } .tp-box > .h.soft { background:var(--soft); color:var(--p); }
.tp-box > .b { padding:14px 16px; }
.tp-two { display:grid; grid-template-columns:260px minmax(0,1fr); gap:16px; }
.tp-sec { font-weight:800; color:var(--p); font-size:14.5px; margin:2px 0 8px; display:flex; gap:8px; align-items:center; }
.tp-pax { border:1px solid var(--ln); border-radius:10px; padding:6px; max-height:330px; overflow:auto; }
.tp-pax label { display:flex; gap:9px; align-items:center; padding:6px 8px; border-radius:7px; margin:2px 0; cursor:pointer; font-weight:600; }
.tp-pax label.on { background:#eaf5e0; } .tp-pax label.off { color:var(--mut); cursor:default; font-weight:400; } .tp-pax input { width:17px; height:17px; accent-color:var(--g); }
.tp-pax .tag { margin-left:auto; font-size:11px; color:var(--mut); font-weight:400; }
.tp-f { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:10px; margin-bottom:12px; }
.tp-f.four { grid-template-columns:repeat(4, minmax(0,1fr)); }
.tp-f label { display:block; font-size:12px; color:var(--mut); margin-bottom:3px; }
.tp-in { width:100%; border:1px solid #d9cfdb; border-radius:8px; padding:6px 9px; height:36px; font-size:13px; background:#fff; color:var(--ink); }
.tp-in:focus { outline:2px solid rgba(132,36,132,.2); border-color:var(--p); }
.tp-tr { border:1px solid var(--ln); border-radius:10px; padding:10px 12px; display:grid; grid-template-columns:1.2fr 1fr 1.2fr; gap:10px; align-items:center; margin-bottom:10px; }
.tp-tr b { color:var(--p); font-size:14.5px; } .tp-tr small { display:block; color:var(--mut); }
.tp-pill { display:inline-flex; gap:6px; align-items:center; border-radius:14px; padding:3px 11px; font-size:12px; font-weight:700; white-space:nowrap; }
.tp-pill.w { background:var(--amb); color:var(--ambd); } .tp-pill.g { background:#e8f3de; color:#3f7a1c; } .tp-pill.r { background:#fde8e8; color:#b3261e; } .tp-pill.p { background:var(--soft); color:var(--p); }
.tp-note { background:var(--soft); border-radius:9px; padding:8px 12px; font-size:12.5px; color:#4a3a4c; display:flex; gap:8px; align-items:flex-start; margin:8px 0; }
.tp-warn { background:var(--amb); border:1px solid #f0d48a; color:#6b4a00; border-radius:9px; padding:9px 12px; font-size:13px; display:flex; gap:8px; align-items:flex-start; }
.tp-bad { background:#fdecef; border:1px solid #f3b7c3; color:#8a1f2a; border-radius:9px; padding:9px 12px; font-size:13px; }
.tp-side .big { background:#eef7e6; border-radius:10px; padding:12px; display:flex; gap:12px; align-items:center; margin-bottom:10px; } .tp-side .big i { font-size:28px; color:var(--gd); }
.tp-side .big b { font-size:17px; display:block; } .tp-side .big small { color:#4b5a40; }
.tp-link { display:flex; align-items:center; gap:10px; border:1px solid var(--ln); border-radius:10px; padding:10px 12px; margin-bottom:8px; color:var(--p); font-weight:700; cursor:pointer; background:#fff; width:100%; text-align:left; }
.tp-link:hover { background:var(--soft); } .tp-link i:last-child { margin-left:auto; }
.tp-tbl { width:100%; border-collapse:collapse; } .tp-tbl th { background:var(--soft); text-align:left; padding:8px 10px; font-size:12.5px; color:#4a3a4c; }
.tp-tbl td { padding:8px 10px; border-bottom:1px solid #f1eaf1; vertical-align:top; } .tp-tbl tr:last-child td { border-bottom:0; }
.tp-tbl .r { text-align:right; } .tp-tbl .chg td { background:#fffaf0; }
.tp-rooms { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px; margin-top:12px; }
.tp-rbox { border:1px solid var(--ln); border-radius:10px; padding:10px; } .tp-rbox h6 { margin:0 0 6px; font-size:13px; font-weight:800; color:var(--p); }
.tp-rbox .row4 { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:6px; } .tp-rbox .row4 label { font-size:11px; color:var(--mut); display:block; text-align:center; }
.tp-rbox .row4 input { width:100%; height:34px; border:1px solid #d9cfdb; border-radius:7px; text-align:center; font-weight:700; }
.tp-rbox .sum { font-size:12px; color:var(--mut); margin-top:6px; }
.tp-foot { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-top:12px; } .tp-foot .tp-warn { flex:1 1 380px; }
.tp-list { margin:0; padding-left:18px; } .tp-list li { margin:3px 0; }
.tp-plans { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
.tp-plan { border:1px solid var(--ln); background:#fff; border-radius:10px; padding:7px 12px; cursor:pointer; text-align:left; line-height:1.3; }
.tp-plan b { display:block; font-size:13px; } .tp-plan small { color:var(--mut); } .tp-plan.on { background:var(--p); border-color:var(--p); color:#fff; } .tp-plan.on small { color:rgba(255,255,255,.85); }
.tp-dep label { display:flex; gap:8px; align-items:center; font-size:13px; padding:3px 0; } .tp-dep input { accent-color:var(--g); }
.tp-mod { position:fixed; inset:0; background:rgba(30,18,32,.45); z-index:2000; display:flex; align-items:flex-start; justify-content:center; padding:30px 12px; overflow:auto; }
.tp-mod .in { background:#fff; border-radius:14px; width:min(1100px, 100%); box-shadow:0 20px 60px rgba(0,0,0,.25); }
.tp-mod .hd { background:var(--p); color:#fff; padding:12px 18px; border-radius:14px 14px 0 0; display:flex; align-items:center; font-weight:800; font-size:17px; }
.tp-mod .hd button { margin-left:auto; background:transparent; border:1px solid rgba(255,255,255,.5); color:#fff; border-radius:8px; padding:4px 12px; cursor:pointer; }
.tp-mod .bd { padding:16px 18px; } .tp-mod h5 { color:var(--p); font-weight:800; font-size:15px; margin:14px 0 8px; } .tp-mod h5:first-child { margin-top:0; }
.tp-mod .up { color:#b3261e; } .tp-mod .down { color:#3f7a1c; }
@media (max-width:1100px) { .tp-grid { grid-template-columns:1fr; } .tp-two { grid-template-columns:1fr; } }
@media (max-width:760px) { .tp-cards { grid-template-columns:repeat(2, minmax(0,1fr)); } .tp-f, .tp-f.four { grid-template-columns:repeat(2, minmax(0,1fr)); } .tp-tr { grid-template-columns:1fr; } .tp-rooms { grid-template-columns:1fr; }
    .tp-top h3 { font-size:18px; } .tp-card b { font-size:18px; } .tp-tbl.stack thead { display:none; } .tp-tbl.stack tr { display:block; border-bottom:1px solid var(--ln); padding:6px 0; } .tp-tbl.stack td { display:block; border:0; padding:2px 10px; } .tp-tbl.stack td[data-l]::before { content:attr(data-l) ': '; color:var(--mut); font-size:12px; } }
</style>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url('tour/intake') ?>';
    const S = { id: null, d: null, cur: null, form: null, host: null, busy: false };
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const dm = s => { if (!s) return '—'; const p = String(s).slice(0, 10).split('-'); return p.length === 3 ? Number(p[2]) + ' ' + MON[Number(p[1]) - 1] + ' ' + p[0] : esc(s); };
    const dshort = s => { if (!s) return '—'; const p = String(s).slice(0, 10).split('-'); return Number(p[2]) + ' ' + MON[Number(p[1]) - 1]; };
    const addDays = (s, n) => { const t = new Date(s + 'T00:00:00Z'); t.setUTCDate(t.getUTCDate() + n); return t.toISOString().slice(0, 10); };   // UTC both ways: a local midnight east of Greenwich is the day before in UTC
    const usd = n => n === null || n === undefined ? '—' : 'USD ' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const RW = { sgl: 'single', dbl: 'double', twn: 'twin', tpl: 'triple' };
    const roomsTxt = m => { const t = ['sgl', 'dbl', 'twn', 'tpl'].filter(k => Number(m && m[k]) > 0).map(k => m[k] + ' ' + RW[k] + (Number(m[k]) === 1 ? '' : 's')); return t.length ? t.join(' + ') : 'no rooms set'; };
    const beds = m => Number(m.sgl || 0) + 2 * (Number(m.dbl || 0) + Number(m.twn || 0)) + 3 * Number(m.tpl || 0);
    function toast(kind, msg) { if (window.Swal) Swal.fire({ icon: kind, text: msg, timer: kind === 'success' ? 2200 : undefined, showConfirmButton: kind !== 'success' }); else alert(msg); }
    async function api(path, body) {
        const o = body === undefined ? { headers: { 'X-Requested-With': 'XMLHttpRequest' } } : { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) };
        try { return await (await fetch(BASE + '/intake/' + S.id + '/travel' + path, o)).json(); } catch (e) { return { status: 0, message: 'The server did not answer (are you still signed in?).' }; }
    }

    function blankForm() {
        const d = S.d, it = d.intake, last = it.nights >= 2 ? it.nights : 1;
        const dep = it.arrival ? addDays(it.arrival, Math.max(1, last - 1)) : '';
        const hotel = (d.nights_hotels.find(h => h.day_no === Math.max(1, last - 1)) || {}).hotel || '';
        return { plan_id: null, members: [], depart_date: dep, flight: '', flight_time: '', airport: 'Colombo / CMB', pickup_date: dep, pickup_time: '', pickup_place: hotel, vehicle_class: (d.classes.find(c => c.seats >= 4) || d.classes[0] || {}).code || '',
            rooms: { sgl: 0, dbl: 0, twn: 0, tpl: 0 }, join: [], note: '', cont_rooms: Object.assign({}, d.continuing.rooms), extra_rooms: d.extra ? Object.assign({}, d.extra.rooms) : { sgl: 0, dbl: 0, twn: 0, tpl: 0 }, review_rooms: false };
    }
    function formOf(p) {
        const d = S.d;
        return { plan_id: p.id, members: p.members.slice(), depart_date: p.depart_date, flight: p.flight || '', flight_time: p.flight_time || '', airport: p.airport || 'Colombo / CMB', pickup_date: p.pickup_date || p.depart_date,
            pickup_time: p.pickup_time || '', pickup_place: p.pickup_place || '', vehicle_class: p.vehicle_class || '', rooms: Object.assign({}, p.rooms), join: p.join.slice(), note: p.note || '',
            cont_rooms: Object.assign({}, d.continuing.rooms), extra_rooms: d.extra ? Object.assign({}, d.extra.rooms) : { sgl: 0, dbl: 0, twn: 0, tpl: 0 }, review_rooms: !!p.rooms_reviewed_at };
    }

    async function load(keep) {
        const j = await api('');
        if (j.status !== 200) { S.host.innerHTML = `<div class="tp-bad">${esc(j.message || 'The travel plans could not be read.')}</div>`; return; }
        S.d = j.data;
        if (!keep || !S.form) { S.form = S.d.plans.length ? formOf(S.d.plans[0]) : null; }
        draw();
    }

    // a plan as it would be with the form's changes: what the summary cards and the room plan show while editing
    function leavingNow() { const f = S.form; let n = 0; S.d.plans.forEach(p => { if (!f || p.id !== f.plan_id) n += p.members.length; }); return n + (f ? f.members.length : 0); }

    function draw() {
        const d = S.d, it = d.intake, f = S.form, locked = it.locked;
        const leaving = leavingNow(), cont = it.pax_total - leaving;
        const range = it.arrival ? dshort(it.arrival) + '–' + dm(it.departure) : 'Dates not set';
        const planTabs = d.plans.map(p => `<button type="button" class="tp-plan${f && f.plan_id === p.id ? ' on' : ''}" data-plan="${p.id}"><b>Early departure ${p.no} — ${dshort(p.depart_date)}</b><small>${p.members.length} passenger${p.members.length === 1 ? '' : 's'} · ${esc(p.flight || 'flight to be entered')}</small></button>`).join('')
            + (f && !f.plan_id ? `<button type="button" class="tp-plan on"><b>New early departure</b><small>not saved yet</small></button>` : '');
        let h = `<div class="tp">
            <div class="tp-top"><h3>Tour Inquiry — Passenger Travel Plans</h3>
                ${locked ? `<span class="tp-pill w"><i class="bi bi-lock"></i> ${esc(locked)}</span>` : `<button type="button" class="tp-btn solid" data-add><i class="bi bi-plus-lg"></i> Add early departure</button>`}</div>
            <div class="tp-cards">
                <div class="tp-card date"><i class="bi bi-calendar3"></i><div><small>Tour dates</small><b style="font-size:18px">${esc(range)}</b></div></div>
                <div class="tp-card"><i class="bi bi-people"></i><div><b>${it.pax_total}</b> <span>passengers total</span><small>${d.passengers.length} named on the inquiry</small></div></div>
                <div class="tp-card"><i class="bi bi-airplane"></i><div><b>${leaving}</b> <span>leaving early</span><small>${d.plans.length + (f && !f.plan_id ? 1 : 0)} departure group${d.plans.length + (f && !f.plan_id ? 1 : 0) === 1 ? '' : 's'}</small></div></div>
                <div class="tp-card"><i class="bi bi-people-fill"></i><div><b>${cont}</b> <span>continuing</span><small>to ${dm(it.departure)}</small></div></div>
            </div>
            ${d.passengers.length < it.pax_total ? `<div class="tp-warn" style="margin-bottom:12px"><i class="bi bi-exclamation-triangle"></i><div>The inquiry is for ${it.pax_total} passengers but ${d.passengers.length} are named. Add the names on the Passengers tab so departing passengers can be chosen by name.</div></div>` : ''}
            ${planTabs ? `<div class="tp-plans">${planTabs}</div>` : ''}`;
        if (!f) {
            h += `<div class="tp-box"><div class="b"><div class="tp-note"><i class="bi bi-info-circle"></i><div>Everybody travels together on this inquiry. When some passengers leave before the rest, press <b>Add early departure</b>: choose them by name, enter their flight and pickup, and the groups, the airport transfer, the rooms by night and the costing follow.</div></div></div></div>`;
        } else {
            h += planHtml(f);
        }
        h += daysHtml();
        h += `<div class="tp-grid"><div>${openHtml()}</div><div>${historyHtml()}</div></div></div>`;
        S.host.innerHTML = h;
        wire();
    }

    function planHtml(f) {
        const d = S.d, it = d.intake, locked = !!it.locked;
        const depDay = f.depart_date && it.arrival ? Math.round((new Date(f.depart_date) - new Date(it.arrival)) / 864e5) + 1 : null;
        const pickDay = f.pickup_date && it.arrival ? Math.round((new Date(f.pickup_date) - new Date(it.arrival)) / 864e5) + 1 : null;
        const lastNight = pickDay ? pickDay - 1 : null;
        const others = {}; d.plans.forEach(p => { if (p.id !== f.plan_id) p.members.forEach(m => { others[m] = p; }); });
        const paxList = d.passengers.map(px => { const o = others[px.id], on = f.members.includes(px.id);
            return `<label class="${o ? 'off' : (on ? 'on' : '')}"><input type="checkbox" data-pax="${px.id}" ${on ? 'checked' : ''} ${o || locked ? 'disabled' : ''}>${esc(px.name)}${o ? `<span class="tag">leaves ${dshort(o.depart_date)}</span>` : ''}</label>`; }).join('') || '<div class="tp-note">No passengers are named yet (Passengers tab).</div>';
        const nightHotel = n => (d.nights_hotels.find(x => x.day_no === n) || {}).hotel || '';
        const hotelOpts = [...new Set(d.nights_hotels.map(x => x.hotel).filter(Boolean))];
        const saved = d.plans.find(p => p.id === f.plan_id) || null;
        const tr = saved && saved.transfer;
        const depSvc = saved ? saved.dep_day_services : [];
        const sel = (v, opts) => opts.map(([k, t]) => `<option value="${esc(k)}"${String(v) === String(k) ? ' selected' : ''}>${esc(t)}</option>`).join('');
        const eve = f.depart_date ? addDays(f.depart_date, -1) : '';
        const rbox = (title, key, m, sub) => `<div class="tp-rbox"><h6>${title}</h6><div class="row4">${['sgl', 'dbl', 'twn', 'tpl'].map(k => `<div><label>${k.toUpperCase()}</label><input type="number" min="0" max="99" data-room="${key}.${k}" value="${Number(m[k] || 0)}" ${locked ? 'disabled' : ''}></div>`).join('')}</div><div class="sum">${sub}</div></div>`;
        const contN = it.pax_total - leavingNow();
        return `<div class="tp-grid">
            <div>
                <div class="tp-box"><div class="h"><i class="bi bi-airplane-fill"></i>Early departure — ${depDay ? 'Day ' + depDay + ' · ' : ''}${dm(f.depart_date)}<span class="sp"></span>${saved ? `<span class="tp-pill ${saved.rooms_reviewed_at ? 'g' : 'w'}">${saved.rooms_reviewed_at ? 'Rooms confirmed' : 'Rooms to review'}</span>` : '<span class="tp-pill p">Not saved</span>'}</div><div class="b">
                    <div class="tp-two">
                        <div><div class="tp-sec"><i class="bi bi-people"></i>Select passenger names</div><div class="tp-pax">${paxList}</div>
                            <div class="sum" style="font-size:12px;color:var(--mut);margin-top:6px">${f.members.length} selected · a passenger can be in one departure group only</div></div>
                        <div>
                            <div class="tp-sec"><i class="bi bi-send"></i>Departure details (early group)</div>
                            <div class="tp-f four">
                                <div><label>Departure date</label><input class="tp-in" type="date" data-f="depart_date" value="${esc(f.depart_date)}" min="${esc(it.arrival ? addDays(it.arrival, 1) : '')}" max="${esc(it.departure ? addDays(it.departure, -1) : '')}" ${locked ? 'disabled' : ''}></div>
                                <div><label>Flight</label><input class="tp-in" data-f="flight" value="${esc(f.flight)}" placeholder="To be entered" ${locked ? 'disabled' : ''}></div>
                                <div><label>Flight time</label><input class="tp-in" type="time" data-f="flight_time" value="${esc(f.flight_time)}" ${locked ? 'disabled' : ''}></div>
                                <div><label>Airport</label><select class="tp-in" data-f="airport" ${locked ? 'disabled' : ''}>${sel(f.airport, [['Colombo / CMB', 'Colombo / CMB'], ['Mattala / HRI', 'Mattala / HRI']])}</select></div>
                            </div>
                            <div class="tp-sec"><i class="bi bi-car-front"></i>Airport pickup (from hotel)</div>
                            <div class="tp-f">
                                <div><label>Pickup date</label><select class="tp-in" data-f="pickup_date" ${locked ? 'disabled' : ''}>${sel(f.pickup_date, [[f.depart_date, 'Day of the flight · ' + dshort(f.depart_date)], [eve, 'Evening before · ' + dshort(eve)]])}</select></div>
                                <div><label>Pickup time</label><input class="tp-in" type="time" data-f="pickup_time" value="${esc(f.pickup_time)}" ${locked ? 'disabled' : ''}></div>
                                <div><label>Pickup hotel</label><input class="tp-in" data-f="pickup_place" list="tp_hotels" value="${esc(f.pickup_place)}" placeholder="${esc(nightHotel(lastNight) || 'Select hotel')}" ${locked ? 'disabled' : ''}><datalist id="tp_hotels">${hotelOpts.map(x => `<option value="${esc(x)}">`).join('')}</datalist></div>
                            </div>
                            ${f.pickup_date && f.pickup_date < f.depart_date ? `<div class="tp-warn" style="margin-bottom:10px"><i class="bi bi-moon-stars"></i><div>Collected the evening before the flight: the night of ${dm(f.pickup_date)} is not slept, so their last hotel night is ${lastNight ? 'night ' + lastNight : '—'}. Arrange a late check-out or day use at ${esc(f.pickup_place || nightHotel(lastNight) || 'the hotel')} and add any charge.</div></div>` : ''}
                            <div class="tp-sec" style="margin-top:4px"><i class="bi bi-calendar-event"></i>Departure-day services (day ${pickDay || '—'})</div>
                            <div class="tp-dep">${depSvc.length ? depSvc.map(s => `<label><input type="checkbox" data-join="${s.id}" ${f.join.includes(s.id) ? 'checked' : ''} ${locked ? 'disabled' : ''}>${esc(s.title)} <span style="color:var(--mut)">— the early group joins before leaving</span></label>`).join('')
                                : `<div style="color:var(--mut);font-size:12.5px">${saved ? 'No activities or meals on that day.' : 'Save the plan once to choose which of that day\'s services the early group still joins.'}</div>`}</div>
                        </div>
                    </div>
                    <div class="tp-tr" style="margin-top:14px">
                        <div><b><i class="bi bi-bus-front"></i> Transfer planning</b><small>${f.members.length} passenger${f.members.length === 1 ? '' : 's'} · separate airport transfer${tr ? ' · ' + (tr.km !== null ? tr.km + ' km' : 'km not set') : ''}</small></div>
                        <div><label style="font-size:12px;color:var(--mut)">Vehicle</label><select class="tp-in" data-f="vehicle_class" ${locked ? 'disabled' : ''}>${sel(f.vehicle_class, d.classes.map(c => [c.code, c.name + ' · ' + c.seats + ' seats']))}</select></div>
                        <div>${tr ? (tr.unpriced ? `<span class="tp-pill w"><i class="bi bi-exclamation-circle"></i> ${tr.unpriced} leg${tr.unpriced === 1 ? '' : 's'} without distance</span>` : `<span class="tp-pill g"><i class="bi bi-check2-circle"></i> ${esc(tr.type || '')} costed${tr.amount !== null ? ' · ' + esc(tr.currency || 'LKR') + ' ' + Number(tr.amount).toLocaleString() : ''}</span>`) : '<span class="tp-pill w"><i class="bi bi-exclamation-circle"></i> Planning required</span>'}
                            <small style="color:var(--mut);display:block;margin-top:4px">Driver and availability are allocated with the confirmed tour (Operations).</small></div>
                    </div>
                    ${tr && tr.legs.length ? `<table class="tp-tbl stack" style="margin-bottom:8px"><thead><tr><th>Day</th><th>From</th><th>To</th><th class="r">Km</th></tr></thead><tbody>${tr.legs.map(l => `<tr><td data-l="Day">${l.day}</td><td data-l="From">${esc(l.from || '—')}</td><td data-l="To">${esc(l.to || '—')}</td><td class="r" data-l="Km">${l.km !== null ? l.km : '<span class="tp-pill w">to enter</span>'}</td></tr>`).join('')}</tbody></table>` : ''}
                    <div class="tp-note"><i class="bi bi-info-circle"></i><div>Keep transport available for the ${contN} continuing passenger${contN === 1 ? '' : 's'}: the main vehicle, driver and guide stay with them and are not reduced in proportion.</div></div>
                </div></div>

                <div class="tp-box"><div class="h soft"><i class="bi oci oci-bed"></i>Room plan (hotel occupancy)</div><div class="b" id="tp_rooms">
                    ${roomPlanHtml()}
                    <div class="tp-rooms">
                        ${rbox('Rooms — early group', 'rooms', f.rooms, `${f.members.length} guest${f.members.length === 1 ? '' : 's'} · ${roomsTxt(f.rooms)} · sleeps ${beds(f.rooms)} · nights 1–${lastNight || '—'}`)}
                        ${rbox('Rooms — continuing guests', 'cont_rooms', f.cont_rooms, `${contN} guests · ${roomsTxt(f.cont_rooms)} · sleeps ${beds(f.cont_rooms)} · every night`)}
                        ${rbox('Extra rooms after the departure', 'extra_rooms', f.extra_rooms, 'For example a guest left alone in a twin needs a single from night ' + (lastNight ? lastNight + 1 : '—') + '.')}
                    </div>
                    <label style="display:flex;gap:8px;align-items:center;margin-top:12px;font-weight:600"><input type="checkbox" data-f="review_rooms" ${f.review_rooms ? 'checked' : ''} ${locked ? 'disabled' : ''} style="width:18px;height:18px;accent-color:var(--g)">I have reviewed the room allocation and the named room sharing (six guests are not necessarily three twins).</label>
                    <div class="tp-note"><i class="bi bi-info-circle"></i><div>Room mix is entered by staff, never guessed. Review named room sharing on the Passengers tab for every hotel stay that runs past the departure.</div></div>
                </div></div>
            </div>
            <div class="tp-side">
                <div class="tp-box"><div class="h soft"><i class="bi bi-people"></i>Group after departure</div><div class="b">
                    <div class="big"><i class="bi bi-people-fill"></i><div><b>${contN} passenger${contN === 1 ? '' : 's'} continuing</b><small>Continue the tour from ${dm(f.pickup_date || f.depart_date)}${pickDay ? ' (Day ' + pickDay + ')' : ''} to ${dm(it.departure)}.</small></div></div>
                    <button type="button" class="tp-link" data-go="rooms"><i class="bi oci oci-bed"></i>Review rooms by night<i class="bi bi-chevron-right"></i></button>
                    <button type="button" class="tp-link" data-go="acts"><i class="bi bi-cup-hot"></i>Review meals &amp; activities<i class="bi bi-chevron-right"></i></button>
                    <button type="button" class="tp-link" data-go="check"><i class="bi bi-calculator"></i>Recalculate quotation<i class="bi bi-chevron-right"></i></button>
                    ${d.bookings.length ? `<div class="tp-sec" style="margin-top:12px"><i class="bi bi-exclamation-triangle"></i>Confirmed bookings to amend</div><ul class="tp-list">${d.bookings.map(b => `<li><b>${esc(b.hotel)}</b> ${dshort(b.check_in)}–${dshort(b.check_out)} <span class="tp-pill w">${esc(b.status)}</span><div style="font-size:12px;color:var(--mut)">${esc(b.why)}</div></li>`).join('')}</ul>` : ''}
                </div></div>
            </div>
        </div>
        <div class="tp-foot">
            <div class="tp-warn"><i class="bi bi-exclamation-triangle"></i><div>Confirmed bookings require an amendment; no automatic cancellation. Issued quotation versions stay as issued until a new version is made.</div></div>
            ${locked ? '' : `${saved ? `<button type="button" class="tp-btn red" data-del><i class="bi bi-trash"></i> Remove plan</button>` : `<button type="button" class="tp-btn p" data-cancel>Cancel</button>`}
            <button type="button" class="tp-btn p" data-preview><i class="bi bi-eye"></i> Preview changes</button>
            <button type="button" class="tp-btn solid" data-save><i class="bi bi-check2-circle"></i> Save travel plan</button>`}
        </div>`;
    }

    function roomPlanHtml() {
        const rows = S.d.room_plan;
        if (!rows.length) return '<div class="tp-note">No hotel nights on the inquiry yet.</div>';
        const ST = { whole: ['p', 'Whole party'], confirmed: ['g', 'Confirmed'], review: ['w', 'Review required'], short: ['r', 'Rooms do not fit the guests'] };
        return `<table class="tp-tbl stack"><thead><tr><th>Night</th><th>Staying guests</th><th>Room mix (as saved)</th><th>Status</th></tr></thead><tbody>${rows.map(r => { const s = ST[r.status] || ['p', r.status];
            return `<tr><td data-l="Night">${r.from === r.to ? dshort(r.from) : dshort(r.from) + '–' + dshort(r.to)} <span style="color:var(--mut)">(night${r.from_day === r.to_day ? ' ' + r.from_day : 's ' + r.from_day + '–' + r.to_day})</span></td><td data-l="Guests"><b>${r.guests}</b></td><td data-l="Rooms">${esc(r.mix_text)}</td><td data-l="Status"><span class="tp-pill ${s[0]}">${s[1]}</span></td></tr>`; }).join('')}</tbody></table>`;
    }

    function daysHtml() {
        const d = S.d;
        return `<div class="tp-box"><div class="h soft"><i class="bi bi-calendar-week"></i>Passengers by itinerary day and hotel night</div><div class="b" style="padding:0">
            <table class="tp-tbl stack"><thead><tr><th>Day</th><th>Hotel (night)</th><th class="r">On tour that day</th><th class="r">Staying that night</th><th>Leaving</th></tr></thead><tbody>${d.days.map(x => `<tr class="${x.leaving.length ? 'chg' : ''}"><td data-l="Day">Day ${x.day_no} · ${dshort(x.date)}</td><td data-l="Hotel">${x.night ? esc(x.hotel || '—') : '<span style="color:var(--mut)">departure day</span>'}</td><td class="r" data-l="On tour">${x.day_guests}</td><td class="r" data-l="Night">${x.night ? x.night_guests : '—'}</td><td data-l="Leaving">${x.leaving.length ? `<span class="tp-pill w"><i class="bi bi-airplane"></i> ${x.leaving.length}</span> ${esc(x.leaving.join(', '))}` : ''}</td></tr>`).join('')}</tbody></table>
            <div style="padding:8px 14px;font-size:12px;color:var(--mut)">Departing passengers keep their earlier services, rooms and history; only what comes after their departure changes.</div></div></div>`;
    }

    function openHtml() {
        const o = S.d.open || { blockers: [], warnings: [] };
        if (!o.blockers.length && !o.warnings.length) return S.d.plans.length ? `<div class="tp-box"><div class="h soft"><i class="bi bi-check2-circle"></i>Readiness</div><div class="b"><span class="tp-pill g">Nothing open on the travel plans</span></div></div>` : '';
        return `<div class="tp-box"><div class="h soft"><i class="bi bi-list-check"></i>Still open on the travel plans</div><div class="b"><ul class="tp-list">${o.blockers.map(b => `<li><span class="tp-pill r">Blocks the quotation</span> ${esc(b.label)}</li>`).join('')}${o.warnings.map(b => `<li><span class="tp-pill w">To do</span> ${esc(b.label)}</li>`).join('')}</ul></div></div>`;
    }

    function historyHtml() {
        const h = S.d.history || [];
        return `<div class="tp-box"><div class="h soft"><i class="bi bi-clock-history"></i>Travel plan history</div><div class="b">${h.length ? `<ul class="tp-list">${h.map(x => `<li><b>${esc(String(x.at).slice(0, 16))}</b> · ${esc(x.by_name || x.by_user || '')}<div style="font-size:12.5px">${esc(x.summary)}</div></li>`).join('')}</ul>` : '<span style="color:var(--mut)">No changes yet.</span>'}</div></div>`;
    }

    function readForm() {
        const f = S.form, h = S.host;
        h.querySelectorAll('[data-f]').forEach(el => { const k = el.dataset.f; f[k] = el.type === 'checkbox' ? el.checked : el.value; });
        f.members = [...h.querySelectorAll('[data-pax]:checked')].map(el => Number(el.dataset.pax));
        f.join = [...h.querySelectorAll('[data-join]:checked')].map(el => Number(el.dataset.join));
        h.querySelectorAll('[data-room]').forEach(el => { const [g, k] = el.dataset.room.split('.'); f[g][k] = Math.max(0, Number(el.value) || 0); });
        return f;
    }

    function wire() {
        const h = S.host;
        h.querySelectorAll('[data-plan]').forEach(b => b.addEventListener('click', () => { const p = S.d.plans.find(x => x.id === Number(b.dataset.plan)); if (p) { S.form = formOf(p); draw(); } }));
        const add = h.querySelector('[data-add]'); if (add) add.addEventListener('click', () => { S.form = blankForm(); draw(); });
        const can = h.querySelector('[data-cancel]'); if (can) can.addEventListener('click', () => { S.form = S.d.plans.length ? formOf(S.d.plans[0]) : null; draw(); });
        h.querySelectorAll('[data-pax], [data-f="depart_date"], [data-f="pickup_date"], [data-room]').forEach(el => el.addEventListener('change', () => {
            const was = S.form.depart_date; readForm();
            if (S.form.depart_date !== was) { S.form.pickup_date = S.form.depart_date; const it = S.d.intake; const n = Math.round((new Date(S.form.depart_date) - new Date(it.arrival)) / 864e5); const ht = (S.d.nights_hotels.find(x => x.day_no === n) || {}).hotel; if (ht && !S.form.pickup_place) S.form.pickup_place = ht; }
            draw(); }));
        h.querySelectorAll('[data-go]').forEach(b => b.addEventListener('click', () => { const g = b.dataset.go;
            if (g === 'rooms') { const r = document.getElementById('tp_rooms'); if (r) r.scrollIntoView({ behavior: 'smooth', block: 'start' }); return; }
            if (window.tfInqShow) window.tfInqShow(g); }));
        const pv = h.querySelector('[data-preview]'); if (pv) pv.addEventListener('click', preview);
        const sv = h.querySelector('[data-save]'); if (sv) sv.addEventListener('click', save);
        const dl = h.querySelector('[data-del]'); if (dl) dl.addEventListener('click', remove);
    }

    function payload() { const f = readForm(); return Object.assign({}, f); }

    async function save() {
        if (S.busy) return; S.busy = true;
        const btn = S.host.querySelector('[data-save]'); if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…'; }
        const j = await api('/save', payload());
        S.busy = false;
        if (j.status !== 200) { toast('error', j.message); if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle"></i> Save travel plan'; } return; }
        S.d = j.data; const p = S.d.plans.find(x => x.id === j.plan_id); S.form = p ? formOf(p) : null; draw(); toast('success', j.message); if (window.tfInqSignalAgain) window.tfInqSignalAgain();
        if (window.tfInqReload) try { window.tfInqReload(); } catch (e) {}
    }

    async function remove() {
        const ok = window.Swal ? (await Swal.fire({ icon: 'warning', title: 'Remove this travel plan?', text: 'The passengers stay with the tour to the end again: their group, the separate transfer and the service quantities the plan changed are put back.', showCancelButton: true, confirmButtonText: 'Remove plan', confirmButtonColor: '#b3261e' })).isConfirmed : confirm('Remove this travel plan?');
        if (!ok) return;
        const j = await api('/delete', { plan_id: S.form.plan_id });
        if (j.status !== 200) { toast('error', j.message); return; }
        S.d = j.data; S.form = S.d.plans.length ? formOf(S.d.plans[0]) : null; draw(); toast('success', j.message); if (window.tfInqSignalAgain) window.tfInqSignalAgain();
    }

    async function preview() {
        if (S.busy) return; S.busy = true;
        const btn = S.host.querySelector('[data-preview]'); if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Working it out…'; }
        const j = await api('/preview', payload());
        S.busy = false; if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-eye"></i> Preview changes'; }
        if (j.status !== 200) { toast('error', j.message); return; }
        showPreview(j.data);
    }

    function showPreview(p) {
        const c = p.changes, diff = (a, b) => { if (a === null || b === null || a === undefined || b === undefined) return ''; const x = Number(b) - Number(a); return x === 0 ? '<span style="color:var(--mut)">no change</span>' : `<span class="${x > 0 ? 'up' : 'down'}">${x > 0 ? '+' : '−'}${usd(Math.abs(x)).replace('USD ', 'USD ')}</span>`; };
        const groups = Object.values(c.by_group || {});
        const m = document.createElement('div'); m.className = 'tp-mod';
        m.innerHTML = `<div class="in"><div class="hd"><i class="bi bi-eye me-2"></i> Preview changes — nothing is saved yet<button type="button" data-x>Close ✕</button></div><div class="bd">
            <h5>Hotels — guests and rooms by night</h5>
            <table class="tp-tbl stack"><thead><tr><th>Night</th><th>Hotel</th><th class="r">Guests before → after</th><th>Rooms before</th><th>Rooms after</th></tr></thead><tbody>${c.nights.map(n => `<tr class="${n.changed ? 'chg' : ''}"><td data-l="Night">${n.day_no} · ${dshort(n.date)}</td><td data-l="Hotel">${esc(n.hotel || '—')}</td><td class="r" data-l="Guests">${n.guests_before} → <b>${n.guests_after}</b></td><td data-l="Before">${esc(n.rooms_before)}</td><td data-l="After"><b>${esc(n.rooms_after)}</b></td></tr>`).join('')}</tbody></table>
            <h5>Activities, entrances and meals</h5>
            ${c.services.length ? `<table class="tp-tbl stack"><thead><tr><th>Day</th><th>Service</th><th>Quantity before → after</th></tr></thead><tbody>${c.services.map(s => `<tr><td data-l="Day">${s.day_no}</td><td data-l="Service">${esc(s.title)}</td><td data-l="Qty">${esc(s.from)} → <b>${esc(s.to)}</b></td></tr>`).join('')}</tbody></table>` : '<div style="color:var(--mut)">No service quantity changes.</div>'}
            ${(c.service_money || []).length ? `<h5>Activity and meal costs that change</h5><table class="tp-tbl stack"><thead><tr><th>Day</th><th>Line</th><th class="r">Before</th><th class="r">After</th></tr></thead><tbody>${c.service_money.map(s => `<tr><td data-l="Day">${s.day_no ?? ''}</td><td data-l="Line">${esc(s.title)}${s.new ? ' <span class="tp-pill p">new line</span>' : ''}${s.gone ? ' <span class="tp-pill w">no longer costed</span>' : ''}</td><td class="r" data-l="Before">${usd(s.before)}</td><td class="r" data-l="After">${usd(s.after)}</td></tr>`).join('')}</tbody></table>` : ''}
            <h5>Transport</h5>
            <table class="tp-tbl stack"><thead><tr><th>Vehicle</th><th>Size</th><th class="r">Km</th><th class="r">Cost</th></tr></thead><tbody>${c.vehicles_after.map(v => `<tr class="${/early departure/i.test(v.label) ? 'chg' : ''}"><td data-l="Vehicle">${esc(v.label)}${v.state === 'missing' ? ` <span class="tp-pill w">${esc(v.why || 'incomplete')}</span>` : ''}</td><td data-l="Size">${esc(v.type || '')}</td><td class="r" data-l="Km">${v.km ?? '—'}</td><td class="r" data-l="Cost">${usd(v.amount_usd)}</td></tr>`).join('')}</tbody></table>
            <div class="tp-note"><i class="bi bi-info-circle"></i><div>The main vehicle, driver and guide follow the transport arrangement: they stay with the continuing guests and are not reduced in proportion. ${c.crew.length ? 'Crew: ' + c.crew.map(x => esc(x.role) + ' ' + x.days + ' day' + (x.days === 1 ? '' : 's')).join(', ') + ' (unchanged).' : ''}</div></div>
            ${c.cost_hidden ? `<h5>Costing</h5><div class="tp-note"><i class="bi bi-lock"></i><div>The tour's cost totals are shown to the Pricing group only. The hotel, transport and service lines above are what changes.</div></div>` : `<h5>Costing (tour cost, before markup)</h5>
            <table class="tp-tbl stack"><thead><tr><th>Part</th><th class="r">Before</th><th class="r">After</th><th class="r">Change</th></tr></thead><tbody>${c.parts.map(x => `<tr><td data-l="Part">${esc(x.label)}</td><td class="r" data-l="Before">${usd(x.before)}</td><td class="r" data-l="After">${usd(x.after)}</td><td class="r" data-l="Change">${diff(x.before, x.after)}</td></tr>`).join('')}
                <tr><td><b>Total</b></td><td class="r"><b>${usd(c.cost_before)}</b></td><td class="r"><b>${usd(c.cost_after)}</b></td><td class="r"><b>${diff(c.cost_before, c.cost_after)}</b></td></tr></tbody></table>`}
            ${groups.length ? `<h5>Each group's own costs (hotel rooms and own transfer)</h5><table class="tp-tbl stack"><thead><tr><th>Group</th><th class="r">Guests</th><th class="r">Hotel rooms</th><th class="r">Own transfer</th><th class="r">Per person (own costs)</th></tr></thead><tbody>${groups.map(g => { const own = Number(g.hotel || 0) + Number(g.transport || 0); return `<tr><td data-l="Group">${esc(g.name)}</td><td class="r" data-l="Guests">${g.pax}</td><td class="r" data-l="Hotel">${usd(g.hotel || 0)}</td><td class="r" data-l="Transfer">${g.transport ? usd(g.transport) : '—'}</td><td class="r" data-l="Per person">${g.pax ? usd(own / g.pax) : '—'}</td></tr>`; }).join('')}</tbody></table>
                <div class="tp-note"><i class="bi bi-info-circle"></i><div>Shared costs (the main vehicle, driver, guide, shared activities) are not split here. Tell me whether the agent should see one tour price or a separate per-person price for each group.</div></div>` : ''}
            ${c.blockers_after.length ? `<h5>Still to complete after this plan</h5><ul class="tp-list">${c.blockers_after.map(b => `<li>${esc(b)}</li>`).join('')}</ul>` : ''}
            ${c.bookings.length ? `<h5>Confirmed bookings to amend (not changed automatically)</h5><ul class="tp-list">${c.bookings.map(b => `<li><b>${esc(b.hotel)}</b> ${dshort(b.check_in)}–${dshort(b.check_out)} · ${esc(b.status)} — ${esc(b.why)}</li>`).join('')}</ul>` : ''}
            <div class="tp-foot"><span style="flex:1;color:var(--mut)">This is a trial: nothing on the inquiry has changed.</span><button type="button" class="tp-btn p" data-x>Back to the plan</button><button type="button" class="tp-btn solid" data-ok><i class="bi bi-check2-circle"></i> Save travel plan</button></div>
        </div></div>`;
        document.body.appendChild(m);
        m.querySelectorAll('[data-x]').forEach(b => b.addEventListener('click', () => m.remove()));
        m.addEventListener('click', e => { if (e.target === m) m.remove(); });
        m.querySelector('[data-ok]').addEventListener('click', () => { m.remove(); save(); });
    }

    function mount(host, id) {
        if (!host) return;
        S.id = id; let box = host.querySelector(':scope > .tp-host'); if (!box) { box = document.createElement('div'); box.className = 'tp-host'; host.appendChild(box); }
        S.host = box; box.innerHTML = '<div style="padding:14px;color:#6b6f7b">Reading the travel plans…</div>';
        load(false);
    }
    window.TravelPlans = { mount, reload: () => load(true) };
})();
</script>
