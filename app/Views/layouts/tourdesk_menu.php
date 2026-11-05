<?php
/**
 * Tour Desk — the compact ⋯ menu, and the history list, shared by every panel.
 *
 *   <?= view('layouts/tourdesk_menu') ?>
 *
 * window.tdMenu(button, items) opens a small menu beside a button. items is a list of
 * { label, icon?, hint?, danger?, disabled?, onClick } or { sep: true }. The menu is drawn
 * on the body, so a table that scrolls sideways never clips it, and it closes on any click
 * or Escape. Nothing here decides who sees a menu: the page draws the ⋯ only when the
 * signed-in group holds the Manage privilege, and the server asks the same question again.
 *
 * window.tdHistory(rows, opts) draws a history list (the rows from intake/{id}/activity or a
 * tour's history) as one line per change with who and when, and the field-by-field changes
 * folded under it.
 *
 * window.tdDetails(details, opts) draws the tour as a desk needs it once it is confirmed —
 * the programme day by day with the hotel each night and what happens that day, the
 * passengers, the flights, the agent's requests, the vehicle and the crew — from
 * TourService::details(). opts.print is the URL of the page to print. No money in it.
 *
 * window.tdStrip(strip, opts) draws the one status strip every screen shows for a tour —
 * the quotation and the version sold, hotels done (voucher issued) of total, activities done
 * of total, the "hotel work complete" mark, vehicle, driver and guide each on its own, and
 * the "transport work complete" mark — from TourService::strip(). Counts and states only,
 * never another desk's money. opts.compact draws it as one line of pills for a table row.
 */
?>
<style>
    .td-dots { display:inline-grid; place-items:center; width:30px; height:30px; border-radius:8px; border:1px solid #e4e8ee; background:#fff; color:#2c3e50;
               font-size:16px; line-height:1; font-weight:700; letter-spacing:1px; cursor:pointer; padding:0 0 4px; vertical-align:middle; }
    .td-dots:hover, .td-dots.open { background:rgba(132,36,132,.08); border-color:rgba(132,36,132,.25); color:#842484; }
    .td-menu { position:absolute; z-index:1090; min-width:230px; max-width:320px; background:#fff; border:1px solid rgba(132,36,132,.18); border-radius:12px; padding:6px;
               box-shadow:0 8px 32px rgba(0,0,0,.15); font-family:inherit; font-size:13px; color:#2c3e50; }
    .td-menu .it { display:flex; gap:10px; align-items:flex-start; width:100%; text-align:left; padding:8px 10px; border:0; background:none; border-radius:9px; color:inherit; cursor:pointer; }
    .td-menu .it:hover:not([disabled]) { background:rgba(132,36,132,.09); }
    .td-menu .it[disabled] { opacity:.5; cursor:not-allowed; }
    .td-menu .it i { color:#842484; font-size:14px; margin-top:1px; flex:none; }
    .td-menu .it.danger, .td-menu .it.danger i { color:#b23a32; }
    .td-menu .it b { display:block; font-weight:600; line-height:1.25; }
    .td-menu .it span { display:block; font-size:11.5px; color:#6c757d; line-height:1.3; margin-top:1px; }
    .td-menu .sep { height:1px; background:#eef1f5; margin:4px 6px; }
    .td-menu .ttl { font-size:10.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#6c757d; padding:6px 10px 2px; }
    /* the history list */
    .td-hist { font-size:12.5px; }
    .td-hist .row0 { padding:8px 0; border-bottom:1px dashed #e4e8ee; }
    .td-hist .row0:last-child { border-bottom:0; }
    .td-hist .lane { display:inline-block; font-size:10px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; padding:1px 6px; border-radius:4px; margin-right:6px; background:#eef1f5; color:#6c757d; }
    .td-hist .lane.quotation { background:#f3e7f3; color:#842484; } .td-hist .lane.hotel { background:#e6eef8; color:#1878c0; } .td-hist .lane.transport { background:#fdf3e1; color:#b8640f; } .td-hist .lane.tour { background:#e4f4ea; color:#1f8a4c; } .td-hist .lane.service { background:#e3f4f1; color:#0f8b7e; }
    .td-hist .who { font-size:11.5px; color:#6c757d; margin-top:2px; }
    .td-hist details { margin-top:4px; }
    .td-hist summary { cursor:pointer; font-size:11.5px; color:#842484; }
    .td-hist table { width:100%; border-collapse:collapse; font-size:12px; margin-top:4px; }
    .td-hist td { padding:3px 6px; border-bottom:1px solid #f1f3f6; vertical-align:top; }
    .td-hist td.k { color:#6c757d; width:40%; }
    .td-hist td .from { color:#b23a32; text-decoration:line-through; opacity:.8; }
    .td-hist td .to { color:#1f8a4c; font-weight:600; }
    .td-hist .empty { color:#6c757d; text-align:center; padding:12px; }
    .td-own { display:inline-flex; gap:6px; align-items:center; font-size:12px; color:#6c757d; }
    .td-own b { color:#2c3e50; font-weight:600; }
    /* the tour's status strip */
    .td-strip { display:flex; gap:6px; flex-wrap:wrap; align-items:center; font-size:11.5px; }
    .td-strip .s { display:inline-flex; gap:5px; align-items:center; border:1px solid #e4e8ee; border-radius:20px; padding:2px 9px 2px 7px; background:#fff; color:#2c3e50; white-space:nowrap; line-height:1.6; }
    .td-strip .s i { font-size:11px; }
    .td-strip .s.done { background:#e4f4ea; border-color:transparent; color:#1f8a4c; }
    .td-strip .s.open { background:#eef1f5; border-color:transparent; color:#5f6f80; }
    .td-strip a.s.go { text-decoration:none; cursor:pointer; }
    .td-strip a.s.go:hover, .td-strip a.s.go:focus-visible { box-shadow:0 0 0 2px rgba(24,120,192,.25); text-decoration:underline; outline:none; }
    .td-strip a.s.go::after { content:'\2197'; font-size:10px; margin-left:2px; opacity:.7; }
    .td-strip .s.problem { background:#fbe9e7; border-color:transparent; color:#b23a32; }
    .td-strip .s.none { background:#fff; color:#8a97a5; border-style:dashed; }
    .td-strip .s.mark { background:#1f8a4c; border-color:#1f8a4c; color:#fff; font-weight:700; letter-spacing:.02em; }
    .td-strip .s b { font-weight:700; }
    /* the tour details block */
    .td-det { font-size:12.5px; }
    .td-det h6 { font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:#6c757d; font-weight:700; margin:12px 0 6px; display:flex; justify-content:space-between; align-items:center; }
    .td-det h6:first-child { margin-top:0; }
    .td-det table { width:100%; border-collapse:collapse; }
    .td-det th { text-align:left; font-size:10.5px; letter-spacing:.04em; text-transform:uppercase; color:#6c757d; padding:5px 7px; border-bottom:1px solid #e4e8ee; }
    .td-det td { padding:6px 7px; border-bottom:1px solid #f1f3f6; vertical-align:top; }
    .td-det .dim { color:#6c757d; font-size:11.5px; }
    .td-det .t { display:inline-block; font-size:10.5px; padding:1px 7px; border-radius:10px; background:#eef1f5; color:#6c757d; white-space:nowrap; }
    .td-det .t.ok { background:#e4f4ea; color:#1f8a4c; } .td-det .t.bad { background:#fbe9e7; color:#b23a32; }
    .td-det .flight { display:flex; gap:14px; flex-wrap:wrap; }
    .td-det .print { font-size:11.5px; font-weight:500; text-transform:none; letter-spacing:0; }
</style>
<script>
(function () {
    'use strict';
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let open = null;
    function close() {
        if (!open) return;
        open.menu.remove();
        open.btn.classList.remove('open');
        open = null;
    }
    window.tdMenu = function (btn, items) {
        if (open && open.btn === btn) { close(); return; }
        close();
        const m = document.createElement('div');
        m.className = 'td-menu';
        m.innerHTML = items.map(it => {
            if (it.sep) return '<div class="sep"></div>';
            if (it.title) return `<div class="ttl">${esc(it.title)}</div>`;
            return `<button type="button" class="it ${it.danger ? 'danger' : ''}" ${it.disabled ? 'disabled' : ''}>${it.icon ? `<i class="bi ${esc(it.icon)}"></i>` : ''}<div><b>${esc(it.label)}</b>${it.hint ? `<span>${esc(it.hint)}</span>` : ''}</div></button>`;
        }).join('');
        document.body.appendChild(m);
        const r = btn.getBoundingClientRect();
        const w = m.offsetWidth, h = m.offsetHeight;
        let left = window.scrollX + r.right - w;
        if (left < window.scrollX + 8) left = window.scrollX + r.left;
        let top = window.scrollY + r.bottom + 6;
        if (r.bottom + h + 12 > window.innerHeight && r.top - h - 6 > 0) top = window.scrollY + r.top - h - 6;
        m.style.left = left + 'px';
        m.style.top = top + 'px';
        btn.classList.add('open');
        open = { btn, menu: m };
        const acts = items.filter(it => !it.sep && !it.title);
        m.querySelectorAll('.it').forEach((b, i) => b.addEventListener('click', e => { e.stopPropagation(); if (acts[i].disabled) return; close(); acts[i].onClick && acts[i].onClick(); }));
        setTimeout(() => {
            document.addEventListener('click', close, { once: true });
        }, 0);
    };
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    window.addEventListener('resize', close);

    window.tdDetails = function (d, opts) {
        opts = opts || {};
        if (!d) return '<div class="td-det"><div class="dim">The tour details could not be read.</div></div>';
        const MEAL = { RO: 'room only', BB: 'bed & breakfast', HB: 'half board', FB: 'full board', AI: 'all inclusive' };
        const PAID = { in_tour: 'in the tour', prepaid: 'prepaid', direct: 'paid direct by the party', own_arrangement: "the party's own arrangement", not_arranged: 'not arranged', unknown: '' };
        const fmt = x => { if (!x) return '—'; const t = new Date(x + 'T00:00:00'); return isNaN(t) ? x : t.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short' }); };
        const st = x => {
            if (!x) return '';
            const s = x.status, ref = x.ref || x.confirmation_ref, note = x.note || x.problem_note;
            // tf-tickets-r1: 'pay_at_gate' is its own done state (the entrance paid at the counter on the day)
            const word = s === 'confirmed' ? 'confirmed' + (ref ? ' · ref ' + esc(ref) : '') : (s === 'voucher_issued' ? 'voucher ' + esc(x.voucher_no || 'issued') : (s === 'not_needed' ? 'nothing to arrange' : (s === 'pay_at_gate' ? 'pay at gate' + (note ? ' — ' + esc(note) : '') : (s === 'problem' ? 'problem' + (note ? ' — ' + esc(note) : '') : (s === 'requested' ? 'requested' : (s === 'cancelled' ? 'cancelled' : 'to book'))))));
            return `<span class="t ${s === 'problem' ? 'bad' : (['confirmed', 'voucher_issued', 'not_needed', 'pay_at_gate'].includes(s) ? 'ok' : '')}">${word}</span>`;
        };
        const days = (d.days || []).map(day => `<tr>
            <td style="white-space:nowrap"><b>Day ${day.day_no}</b><div class="dim">${fmt(day.date)}</div>${day.area ? `<div>${esc(day.area)}</div>` : ''}</td>
            <td>${day.narrative ? `<div>${esc(day.narrative)}</div>` : ''}
                ${(day.services || []).map(s => `<div>• ${esc(s.title)} <span class="dim">— ${esc(s.kind)}${PAID[s.pay_mode] ? ', ' + PAID[s.pay_mode] : ''}</span> ${st(s.desk)}${s.note ? `<div class="dim">${esc(s.note)}</div>` : ''}</div>`).join('')}
                ${(day.excursions || []).map(x => `<div>• ${esc(x.name)} <span class="dim">— excursion${Number(x.includes_transport) ? ', transport included' : ''}</span> ${st(x.desk)}${x.note ? `<div class="dim">${esc(x.note)}</div>` : ''}</div>`).join('')}
                ${(day.desk_lines || []).map(l => `<div>• ${esc(l.title)} <span class="dim">— added by the desk</span> ${st(l)}</div>`).join('')}
                ${day.special_note ? `<div class="dim"><b>Note:</b> ${esc(day.special_note)}</div>` : ''}</td>
            <td>${day.hotel ? `<b>${esc(day.hotel)}</b><div class="dim">${day.meal_plan ? esc(MEAL[String(day.meal_plan).toUpperCase()] || day.meal_plan) : '<span class="t bad">meal plan not stated</span>'}${day.rooms ? ` · ${day.rooms} room${Number(day.rooms) === 1 ? '' : 's'}` : ''}${day.rooms_note ? ' · ' + esc(day.rooms_note) : ''}</div>${day.stay ? `<div>${st(day.stay)}</div>` : ''}` : '<span class="t bad">no hotel named</span>'}</td></tr>`).join('');
        const pax = (d.passengers || []).map((p, i) => `<tr><td class="dim">${i + 1}</td><td>${esc(p.full_name)}</td><td class="dim">${esc(p.room_type || '')}</td><td class="dim">${esc(p.note || '')}</td></tr>`).join('');
        const veh = (d.vehicles || []).map(v => `<div><b>${esc(v.vehicle_type)}</b>${Number(v.qty) > 1 ? ' × ' + v.qty : ''} · ${v.vehicle_reg ? esc(v.vehicle_reg) : '<span class="t">no vehicle yet</span>'} <span class="t">${esc(String(v.status).replace('_', ' '))}</span>
            ${(v.crew || []).map(c => `<div class="dim" style="margin-left:10px">${esc(c.role_name)}: ${c.person_name ? `<b>${esc(c.person_name)}</b>` : '<span class="t">nobody yet</span>'}${c.status === 'engaged' ? ' <span class="t ok">engaged</span>' : (c.status === 'declined' ? ' <span class="t bad">declined</span>' : (c.status === 'replaced' ? ' <span class="t">replaced</span>' : ''))}${c.date_from && c.date_to ? ` <span class="t">${esc(c.date_from)} to ${esc(c.date_to)}</span>` : ''}</div>`).join('')}</div>`).join('');
        const req = (d.requests || []).map(r => `<tr><td class="dim" style="white-space:nowrap">${esc(r.kind)}<div>${esc(r.status)}</div></td><td>${esc(r.title)}</td><td class="dim">${r.answer ? esc(r.answer) : '—'}</td></tr>`).join('');
        const paxLine = `${d.pax_total ?? '?'} pax${d.pax_adults !== null && d.pax_adults !== undefined ? ` (${d.pax_adults} adults${Number(d.pax_children) > 0 ? `, ${d.pax_children} children` : ''})` : ''}`;
        return `<div class="td-det">
            <h6><span>The tour</span>${opts.print ? `<a class="print" href="${esc(opts.print)}" target="_blank"><i class="bi bi-printer"></i> Print the programme</a>` : ''}</h6>
            <div><b>${esc(d.tour_no)}</b>${d.tour_name ? ' · ' + esc(d.tour_name) : ''} · ${esc(d.agent_name || '—')}${d.agent_acc ? ` <span class="dim">${esc(d.agent_acc)}</span>` : ''}${d.market_code ? ` <span class="dim">· ${esc(d.market_code)} market</span>` : ''}</div>
            <div class="dim">${fmt(d.arrival_date)} – ${fmt(d.departure_date)} · ${d.nights ?? '?'} nights · ${paxLine}${d.rooms_note ? ' · ' + esc(d.rooms_note) : ''}${d.hotel_class ? ' · ' + esc(d.hotel_class) : ''}${d.quote_version ? ' · sold as quotation v' + d.quote_version : ''}</div>
            <div class="flight"><span>Arrival: <b>${esc(d.arrival_flight || 'flight not stated')}</b></span><span>Departure: <b>${esc(d.departure_flight || 'flight not stated')}</b></span></div>
            ${d.note ? `<div class="dim" style="margin-top:4px"><b>Notes:</b> ${esc(d.note)}</div>` : ''}
            <h6>Programme</h6>
            <div style="overflow-x:auto"><table><thead><tr><th>Day</th><th>What happens</th><th style="width:34%">Night</th></tr></thead><tbody>${days || '<tr><td colspan="3" class="dim">No nights on the quotation.</td></tr>'}</tbody></table></div>
            ${(d.tour_services || []).length || (d.tour_desk_lines || []).length ? `<div class="dim" style="margin-top:4px"><b>On the tour as a whole:</b> ${(d.tour_services || []).map(s => '• ' + esc(s.title) + ' ' + st(s.desk)).join(' ')} ${(d.tour_desk_lines || []).map(l => '• ' + esc(l.title) + ' ' + st(l)).join(' ')}</div>` : ''}
            <div class="row g-3">
              <div class="col-md-6"><h6>Passengers</h6>${pax ? `<table><thead><tr><th>#</th><th>Name</th><th>Room</th><th>Note</th></tr></thead><tbody>${pax}</tbody></table>` : '<div class="dim">No names on the quotation yet.</div>'}</div>
              <div class="col-md-6"><h6>Vehicle &amp; crew</h6>${veh || '<div class="dim">No vehicle line on the quotation.</div>'}</div>
            </div>
            ${req ? `<h6>The agent's requests</h6><table><thead><tr><th>Kind</th><th>Request</th><th style="width:40%">Answer</th></tr></thead><tbody>${req}</tbody></table>` : ''}
        </div>`;
    };

    window.tdStrip = function (s, opts) {
        opts = opts || {};
        if (!s) return '';
        // opts.open: { hotels, activities, vehicle, driver, guide } — the address each item opens (the desk that does
        // the work, on this tour); an item with one is a link, the rest are labels. opts.names: show who and what.
        const open = opts.open || {};
        const pill = (cls, icon, text, title, key) => open[key]
            ? `<a class="s ${cls} go" href="${esc(open[key])}" title="${esc((title || '') + ' — open on the desk')}"><i class="bi ${icon}"></i>${text}</a>`
            : `<span class="s ${cls}" title="${esc(title || '')}"><i class="bi ${icon}"></i>${text}</span>`;
        const nOf = x => `<b>${x.done}</b> of ${x.of}`;
        const out = [];
        const q = s.quotation || {};
        out.push(pill(q.status === 'confirmed' ? 'done' : (q.status === 'cancelled' ? 'problem' : 'open'), 'bi-check2-circle',
            `Inquiry ${q.status === 'confirmed' ? 'confirmed' : esc(q.status || '')}${q.version ? ` · v${q.version}` : ''}`,
            q.version ? `Sold as quotation version ${q.version}` : 'No version recorded'));
        const h = s.hotels || {};
        // done is a stay with its voucher issued; a stay confirmed but not yet vouchered is on the way
        const hExtra = h.confirmed ? ` · ${h.confirmed} confirmed` : '';
        out.push(pill(h.state === 'done' ? 'done' : (h.state === 'problem' ? 'problem' : (h.state === 'none' ? 'none' : 'open')), 'bi-building',
            h.of ? `Hotels ${nOf(h)}${h.problem ? ` · ${h.problem} problem` : hExtra}` : 'Hotels —', h.of ? `${h.done} of ${h.of} stays with the voucher issued${h.confirmed ? `; ${h.confirmed} confirmed, voucher to issue` : ''}` : 'No hotel stays', h.of ? 'hotels' : null));
        const a = s.activities || {};
        out.push(pill(a.state === 'done' ? 'done' : (a.state === 'problem' ? 'problem' : (a.state === 'none' ? 'none' : 'open')), 'bi-ticket-perforated',
            a.of ? `Activities ${nOf(a)}${a.problem ? ` · ${a.problem} problem` : ''}` : 'Activities —', a.of ? `${a.done} of ${a.of} activity and ticket lines confirmed, or with nothing to arrange` : 'No activities or tickets on the quotation', a.of ? 'activities' : null));
        if (s.reservations_confirmed) out.push(pill('mark', 'bi-patch-check-fill', 'Hotel work complete', `Hotels & activities confirmed — marked by ${s.reservations_confirmed_by || ''} ${String(s.reservations_confirmed_at || '').slice(0, 16)}`));
        const st = (x, noun, key) => {
            const cls = x.state === 'allocated' ? 'done' : (x.state === 'not_required' || x.state === 'no_vehicle_line' ? 'none' : 'open');
            const word = x.state === 'allocated' ? 'allocated' : (x.state === 'not_required' ? 'not required' : (x.state === 'no_vehicle_line' ? 'no vehicle line' : 'to allocate'));
            // once allocated, the name itself: the registration, the driver, the guide
            const names = (x.names || []).map(esc).join(', ');
            const text = x.state === 'allocated' && names ? `${noun} <b>${names}</b>${x.of > 1 ? ` (${x.done}/${x.of})` : ''}` : `${noun} ${word}${x.of > 1 ? ` (${x.done}/${x.of})` : ''}`;
            return pill(cls, noun === 'Vehicle' ? 'bi-truck-front' : 'bi-person', text, `${noun}: ${word}${names ? ' — ' + (x.names || []).join(', ') : ''}`, x.state === 'not_required' || x.state === 'no_vehicle_line' ? null : key);
        };
        out.push(st(s.vehicle || {}, 'Vehicle', 'vehicle'));
        out.push(st(s.driver || {}, 'Driver', 'driver'));
        out.push(st(s.guide || {}, 'Guide', 'guide'));
        if (s.transport_complete) out.push(pill('mark', 'bi-patch-check-fill', 'Transport work complete', `Marked by ${s.transport_complete_by || ''} ${String(s.transport_complete_at || '').slice(0, 16)}`));
        return `<div class="td-strip${opts.compact ? ' compact' : ''}">${out.join('')}</div>`;
    };

    const when = s => s ? String(s).slice(0, 16) : '';
    window.tdHistory = function (rows, opts) {
        opts = opts || {};
        // opts.names: sign-in name → person's name, for the desks' trail lines that carry only the sign-in name
        const who = u => (opts.names && opts.names[u]) || u || '';
        if (!rows || !rows.length) return `<div class="td-hist"><div class="empty">${esc(opts.empty || 'Nothing yet.')}</div></div>`;
        return '<div class="td-hist">' + rows.map(r => {
            const changes = r.changes || [];
            const more = (r.changes_total || 0) - changes.length;
            const table = changes.length ? `<details><summary>${changes.length} change${changes.length === 1 ? '' : 's'}${more > 0 ? ` (+${more} more)` : ''}</summary><table>${changes.map(c => {
                const from = c.from === null || c.from === undefined || c.from === '' ? '' : `<span class="from">${esc(c.from)}</span>`;
                const to = c.to === null || c.to === undefined || c.to === '' ? '<span class="help">(cleared)</span>' : `<span class="to">${esc(c.to)}</span>`;
                return `<tr><td class="k">${esc(c.what)}</td><td>${from}${from ? ' → ' : ''}${to}</td></tr>`;
            }).join('')}</table></details>` : '';
            return `<div class="row0"><span class="lane ${esc(r.lane || '')}">${esc(r.lane || r.entity || '')}</span><b>${esc(r.action || '')}</b>${r.summary ? ` <span>${esc(r.summary)}</span>` : ''}${opts.link && r.inquiry_no ? ` <a href="${opts.link}?id=${r.intake_id}">${esc(r.inquiry_no)}</a>` : ''}<div class="who">${when(r.at)} · ${esc(who(r.by))}${r.kind === 'event' ? ' · desk trail' : ''}</div>${table}</div>`;
        }).join('') + '</div>';
    };
})();
</script>
