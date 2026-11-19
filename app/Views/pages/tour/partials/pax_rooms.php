<?php
/**
 * tf-paxrooms-r1 (19 Nov 2026) — the Passengers tab of the Tour Inquiry, to Shantha's approved Passenger Management
 * mock-up: Room Summary and Passenger Count on the left; Passenger Management (import, add, group tools, search and
 * filters, the passenger table, pages) and Room Allocation (Detail) on the right. Each hotel stay has its own room
 * requirements, rooms and sharing — chosen on the bar above. Server: Tour\PaxRoomsController, TourFlow\PaxRooms.
 */
?>
<style id="pr-css">
.pr, .pr-mod { --p:#8b1d40; --pd:#60122b; --g:#78b848; --gd:#4e8a26; --ln:#e6dade; --soft:#f8f1f3; --ink:#2b2b33; --mut:#6b6f7b; }
.pr { font-size:13px; color:var(--ink); }
.pr *, .pr-mod * { box-sizing:border-box; }
.pr-stays { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin:2px 0 10px; }
.pr-stays .lb { font-weight:700; color:var(--p); font-size:12px; text-transform:uppercase; letter-spacing:.04em; margin-right:2px; }
.pr-stay { border:1px solid var(--ln); background:#fff; border-radius:9px; padding:5px 11px; cursor:pointer; text-align:left; line-height:1.25; }
.pr-stay b { display:block; font-size:12.5px; } .pr-stay small { color:var(--mut); font-size:11px; }
.pr-stay.on { background:var(--p); border-color:var(--p); color:#fff; } .pr-stay.on small { color:rgba(255,255,255,.85); }
.pr-st { display:inline-block; border-radius:10px; padding:1px 8px; font-size:10.5px; font-weight:700; margin-left:6px; vertical-align:middle; }
.pr-st.requested { background:#eef0f3; color:#4a4d57; } .pr-st.sent { background:#fdf0d5; color:#8a5a00; } .pr-st.confirmed { background:#e4f4d6; color:#2f6d12; } .pr-st.amend { background:#fbdde3; color:#a3123a; }
.pr-flags { margin:0 0 10px; display:grid; gap:5px; }
.pr-flag { border-radius:7px; padding:6px 10px; font-size:12px; display:flex; gap:8px; align-items:flex-start; }
.pr-flag.warn { background:#fff6e0; border:1px solid #f0d48a; color:#6b4a00; } .pr-flag.bad { background:#fdecef; border:1px solid #f3b7c3; color:#8f1232; }
.pr-grid { display:grid; grid-template-columns:300px minmax(0,1fr); gap:14px; align-items:start; }
.pr-card { background:#fff; border:1px solid var(--ln); border-radius:10px; overflow:hidden; margin-bottom:12px; }
.pr-card > .h { background:var(--soft); padding:8px 12px; font-weight:800; color:var(--p); font-size:14px; display:flex; align-items:center; gap:8px; }
.pr-card > .h .sp { flex:1; } .pr-card > .h i { font-size:16px; }
.pr-mini { width:100%; border-collapse:collapse; } .pr-mini th { text-align:left; font-size:11.5px; padding:5px 12px; border-bottom:1px solid var(--ln); }
.pr-mini td { padding:4px 12px; border-bottom:1px solid #f1eaec; font-size:12.5px; } .pr-mini td:last-child, .pr-mini th:last-child { text-align:right; }
.pr-mini tr.tot td { background:var(--soft); font-weight:800; border-bottom:0; }
.pr-main { background:#fff; border:1px solid var(--ln); border-radius:10px; padding:12px 14px; }
.pr-top { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:10px; } .pr-top h3 { margin:0; font-size:17px; font-weight:800; display:flex; gap:8px; align-items:center; flex:1; } .pr-top h3 i { color:var(--p); }
.pr-btn { display:inline-flex; align-items:center; gap:6px; border:1.5px solid var(--g); background:#fff; color:var(--gd); border-radius:7px; padding:5px 12px; font-weight:700; font-size:12.5px; cursor:pointer; white-space:nowrap; text-decoration:none; }
.pr-btn:hover { background:#f1f8ea; color:var(--gd); } .pr-btn.solid { background:var(--g); color:#fff; } .pr-btn.p { border-color:var(--p); color:var(--p); } .pr-btn.p.solid { background:var(--p); color:#fff; }
.pr-btn.grey { border-color:var(--ln); color:var(--ink); } .pr-btn[disabled] { opacity:.5; cursor:default; }
.pr-fil { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:8px; align-items:center; }
.pr-fil input, .pr-fil select, .pr-in { border:1px solid var(--ln); border-radius:7px; padding:5px 9px; font-size:12.5px; background:#fff; height:32px; color:var(--ink); }
.pr-fil .s { flex:1 1 220px; min-width:160px; }
.pr-tbl-w { overflow-x:auto; border:1px solid var(--ln); border-radius:8px; }
.pr-tbl { width:100%; border-collapse:collapse; min-width:860px; }
.pr-tbl th { background:var(--soft); text-align:left; font-size:11.5px; padding:6px 8px; white-space:nowrap; border-bottom:1px solid var(--ln); }
.pr-tbl td { padding:4px 8px; border-bottom:1px solid #f1eaec; font-size:12.5px; vertical-align:middle; white-space:nowrap; }
.pr-tbl tr:last-child td { border-bottom:0; } .pr-tbl td.nm { font-weight:600; white-space:normal; min-width:170px; }
.pr-tbl select { border:1px solid var(--ln); border-radius:6px; padding:2px 6px; font-size:12px; background:#f6eef1; height:26px; max-width:150px; }
.pr-ty { display:inline-block; border-radius:5px; padding:1px 8px; font-size:11.5px; font-weight:700; background:#fdf0c8; color:#6b4a00; } .pr-ty.child { background:#dcecfb; color:#1c4f82; } .pr-ty.infant { background:#efe3f7; color:#5b2a7d; }
.pr-ic { border:0; background:none; cursor:pointer; padding:2px 4px; font-size:14px; color:var(--p); } .pr-ic.del { color:#c62828; }
.pr-dup { display:inline-block; margin-left:6px; border-radius:9px; padding:0 7px; font-size:10.5px; font-weight:700; background:#fff0c9; color:#7a5200; cursor:help; }
.pr-pg { display:flex; justify-content:flex-end; align-items:center; gap:8px; margin:8px 0 2px; font-size:12px; } .pr-pg select { height:28px; border:1px solid var(--ln); border-radius:6px; }
.pr-pg button { width:28px; height:28px; border:1px solid var(--ln); background:#fff; border-radius:6px; cursor:pointer; } .pr-pg button.on { background:var(--p); color:#fff; border-color:var(--p); font-weight:700; } .pr-pg button[disabled] { opacity:.4; cursor:default; }
.pr-sec { display:flex; align-items:center; gap:8px; font-weight:800; color:var(--p); font-size:14px; margin:12px 0 6px; } .pr-sec .sp { flex:1; }
.pr-alloc { display:grid; grid-template-columns:minmax(0,1fr) 250px; gap:12px; align-items:start; }
.pr-rt td.no { background:#fdf3c9; font-weight:800; text-align:center; } .pr-rt td.cat { background:#f3e6ea; text-align:center; } .pr-rt td.occ { font-weight:800; text-align:center; background:#e4f4d6; }
.pr-rt td.occ.under { background:#fff0c9; } .pr-rt td.occ.over { background:#fbdde3; color:#a3123a; } .pr-rt td.occ.zero { background:#f2f2f4; color:var(--mut); } .pr-rt td.px { white-space:normal; }
.pr-ok { border:1px solid #cfe6bb; background:#f1f8ea; border-radius:10px; padding:10px 12px; } .pr-ok.todo { border-color:#f0d48a; background:#fff8e6; }
.pr-ok h4 { margin:0 0 6px; font-size:13.5px; font-weight:800; color:var(--gd); display:flex; gap:7px; align-items:center; } .pr-ok.todo h4 { color:#8a5a00; }
.pr-ok div { display:flex; justify-content:space-between; padding:2px 0; font-size:12.5px; } .pr-ok div b { font-weight:800; }
.pr-foot { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
.pr-empty { padding:14px; color:var(--mut); text-align:center; font-size:12.5px; }
.pr-menu { position:relative; display:inline-block; } .pr-menu .dd { display:none; position:absolute; right:0; top:calc(100% + 4px); z-index:30; background:#fff; border:1px solid var(--ln); border-radius:9px; box-shadow:0 10px 26px rgba(40,20,25,.18); min-width:300px; padding:5px; }
.pr-menu.open .dd { display:block; } .pr-menu .dd button { display:flex; gap:8px; align-items:center; width:100%; text-align:left; border:0; background:none; padding:7px 10px; border-radius:6px; font-size:12.5px; cursor:pointer; color:var(--ink); } .pr-menu .dd button:hover { background:var(--soft); } .pr-menu .dd hr { margin:4px 0; border:0; border-top:1px solid var(--ln); }
.pr-old { margin-top:12px; border:1px solid var(--ln); border-radius:10px; background:#fff; padding:6px 12px; } .pr-old > summary { cursor:pointer; font-weight:700; color:var(--p); font-size:12.5px; padding:4px 0; }
.pr-mod { position:fixed; inset:0; background:rgba(30,15,20,.45); z-index:2000; display:flex; align-items:flex-start; justify-content:center; padding:5vh 12px; overflow:auto; }
.pr-mod .box { background:#fff; border-radius:12px; width:min(680px,100%); box-shadow:0 20px 60px rgba(0,0,0,.3); overflow:hidden; font-size:13px; color:#2b2b33; } .pr-mod .box.wide { width:min(900px,100%); }
.pr-mod .mh { background:#8b1d40; color:#fff; font-weight:800; padding:10px 14px; display:flex; align-items:center; } .pr-mod .mh span { flex:1; } .pr-mod .mh button { border:0; background:none; color:#fff; font-size:18px; cursor:pointer; }
.pr-mod .mb { padding:12px 14px; max-height:68vh; overflow:auto; } .pr-mod .mf { padding:10px 14px; background:#f8f1f3; display:flex; gap:8px; justify-content:flex-end; align-items:center; } .pr-mod .mf .msg { flex:1; font-size:12px; color:#a3123a; font-weight:600; }
.pr-form { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px 10px; } .pr-form label { display:block; font-size:11.5px; font-weight:700; margin-bottom:2px; } .pr-form .w2 { grid-column:span 2; } .pr-form .w3 { grid-column:1 / -1; }
.pr-form input, .pr-form select { width:100%; border:1px solid #e6dade; border-radius:7px; padding:5px 9px; font-size:13px; height:32px; background:#fff; }
.pr-form .sub { grid-column:1 / -1; font-weight:800; color:#8b1d40; border-top:1px solid #e6dade; padding-top:8px; margin-top:4px; font-size:12.5px; } .pr-note { font-size:11.5px; color:#6b6f7b; }
.pr-mt { width:100%; border-collapse:collapse; } .pr-mt th { text-align:left; font-size:11.5px; padding:5px 6px; background:#f8f1f3; } .pr-mt td { padding:4px 6px; border-bottom:1px solid #f1eaec; font-size:12.5px; } .pr-mt input, .pr-mt select { border:1px solid #e6dade; border-radius:6px; height:30px; padding:3px 7px; font-size:12.5px; width:100%; background:#fff; }
/* tf-paxrooms-r2 (19 Nov 2026): the sizes and spacing of the approved mock-up */
.pr { font-size:14px; }
.pr-grid { grid-template-columns:310px minmax(0,1fr); gap:18px; }
.pr-card { margin-bottom:16px; } .pr-card > .h { padding:12px 16px; font-size:16.5px; gap:10px; } .pr-card > .h i { font-size:19px; }
.pr-mini th { font-size:13px; padding:9px 16px; } .pr-mini td { padding:7px 16px; font-size:14px; } .pr-mini tr.tot td { padding:9px 16px; }
.pr-main { padding:18px 20px; }
.pr-top { margin-bottom:14px; gap:10px; } .pr-top h3 { font-size:20px; gap:10px; } .pr-top h3 i { font-size:22px; }
.pr-btn { padding:8px 16px; font-size:14px; border-radius:8px; gap:8px; }
.pr-fil { gap:10px; margin-bottom:12px; } .pr-fil input, .pr-fil select, .pr-in { height:38px; font-size:13.5px; padding:6px 12px; border-radius:8px; }
.pr-sw { position:relative; flex:1 1 240px; min-width:170px; } .pr-sw i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--mut); } .pr-sw input { width:100%; padding-left:36px; }
.pr-tbl th { font-size:13px; padding:10px; } .pr-tbl td { padding:9px 10px; font-size:14px; } .pr-tbl th.srt { cursor:pointer; user-select:none; }
.pr-tbl select { height:32px; font-size:13.5px; min-width:118px; padding:3px 8px; border-radius:7px; }
.pr-ty { padding:3px 12px; font-size:13px; border-radius:6px; } .pr-ic { font-size:16px; padding:2px 6px; }
.pr-pg { font-size:13.5px; margin:12px 0 4px; gap:8px; } .pr-pg select { height:34px; padding:0 8px; } .pr-pg button { width:36px; height:34px; border-radius:7px; }
.pr-sec { font-size:17px; margin:18px 0 10px; gap:10px; } .pr-sec i { font-size:20px; }
.pr-alloc { grid-template-columns:minmax(0,1fr) 290px; gap:16px; } .pr-rt td { padding:11px 10px; }
.pr-ok { padding:14px 16px; } .pr-ok h4 { font-size:16px; margin-bottom:10px; } .pr-ok div { font-size:14px; padding:3px 0; }
.pr-foot { margin-top:16px; gap:12px; } .pr-foot .pr-btn { padding:10px 20px; font-size:15px; }
.pr-menu .dd a { display:flex; gap:8px; align-items:center; padding:7px 10px; border-radius:6px; font-size:13px; color:var(--ink); text-decoration:none; } .pr-menu .dd a:hover { background:var(--soft); } .pr-menu .dd button { font-size:13px; }
@media (max-width:1100px) { .pr-grid { grid-template-columns:1fr; } .pr-alloc { grid-template-columns:1fr; } }
</style>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url('tour/intake') ?>';
    const mem = {};   // what the person was looking at, per inquiry: the stay, filters, page
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[c]);
    const nm = s => String(s || '').replace(/\//g, ' ');
    const dm = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day:'numeric', month:'short' }) : '';
    const dmy = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) : '';
    const STATE = { requested:'Requested', sent:'Sent to hotel', confirmed:'Hotel confirmed', amend:'Amendment to review' };

    function mount(pane, id) {
        id = Number(id);
        let root = pane.querySelector(':scope > .pr');
        if (!root) {
            root = document.createElement('div'); root.className = 'pr';
            const old = document.createElement('details'); old.className = 'pr-old';
            old.innerHTML = '<summary>Party and rooms the quotation is priced on (Single / Double / Twin / Triple, sub-groups)</summary>';
            [...pane.children].forEach(c => {
                const t = ((c.querySelector(':scope > .tf-card-h h6') || {}).textContent || '').trim();
                if (t === 'Passengers') c.style.display = 'none'; else old.appendChild(c);
            });
            pane.prepend(root);
            pane.appendChild(old);
        }
        const S = mem[id] = mem[id] || { id, stay:null, q:'', ft:'', fn:'', fr:'', page:1, per:25, sel:new Set() };
        S.root = root;
        if (!S.data) root.innerHTML = '<div class="pr-empty">Loading the passengers…</div>';
        else draw(S);
        fetch(BASE + '/intake/' + id + '/paxrooms', { headers:{ 'X-Requested-With':'XMLHttpRequest' } }).then(r => r.json()).then(j => {
            if (j.status !== 200) { root.innerHTML = '<div class="pr-flag bad">' + esc(j.message || 'The passengers could not be read.') + '</div>'; return; }
            S.data = j.data; draw(S);
        }).catch(e => { root.innerHTML = '<div class="pr-flag bad">The passengers could not be read: ' + esc(e) + '</div>'; });
    }

    async function post(S, what, body, quiet) {
        let j;
        try {
            const r = await fetch(BASE + '/intake/' + S.id + '/paxrooms/' + what, { method:'POST', headers:{ 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest' }, body:JSON.stringify(body || {}) });
            j = await r.json();
        } catch (e) { j = { status:500, message:'Not saved: ' + e }; }
        if (j.status === 200 && j.data) { S.data = j.data; draw(S); if (!quiet) toast(j.message, true); }
        return j;
    }
    function toast(msg, ok) {
        if (window.Swal && Swal.fire) Swal.fire({ toast:true, position:'top-end', timer:ok ? 2200 : 5000, showConfirmButton:false, icon:ok ? 'success' : 'error', title:msg });
        else if (!ok) console.warn(msg);
    }

    const curStay = S => { const st = S.data.stays; if (!st.length) return null; return st.find(s => s.key === S.stay) || (S.stay = st[0].key, st[0]); };
    const roomOf = (stay, pid) => stay ? stay.rooms.find(r => r.pax.includes(pid)) || null : null;
    const beds = (S, r) => S.data.beds[r.room_type];
    const ptype = p => String(p.pax_type || 'adult').toLowerCase();
    const occ = (S, r) => r.pax.filter(pid => { const p = S.data.passengers.find(x => Number(x.id) === pid); return p && ptype(p) !== 'infant'; }).length;
    const roomLabel = (S, r) => r.room_no + ' · ' + S.data.types[r.room_type] + (r.category ? ' ' + r.category : '');
    const catOpts = (stay, cur, blank) => '<option value="">' + (blank || '—') + '</option>' + (stay ? stay.categories.list : []).map(c => '<option' + (String(cur || '').toLowerCase() === c.toLowerCase() ? ' selected' : '') + '>' + esc(c) + '</option>').join('');
    const typeOpts = (S, cur) => Object.entries(S.data.types).map(([k, w]) => '<option value="' + k + '"' + (cur === k ? ' selected' : '') + '>' + w + '</option>').join('');

    function filtered(S, stay) {
        const q = S.q.trim().toLowerCase();
        const out = S.data.passengers.filter(p => {
            if (q && !(nm(p.full_name) + ' ' + (p.passport_no || '') + ' ' + (p.nationality || '')).toLowerCase().includes(q)) return false;
            if (S.ft && ptype(p) !== S.ft) return false;
            if (S.fn && String(p.nationality || '') !== S.fn) return false;
            if (S.fr) { const r = roomOf(stay, Number(p.id)); if (S.fr === '-' ? !!r : !r || String(r.id) !== S.fr) return false; }
            return true;
        });
        if (S.st) { const o = { adult:0, child:1, infant:2 }; out.sort((a, b) => (o[ptype(a)] - o[ptype(b)]) * (S.st === 'desc' ? -1 : 1)); }
        return out;
    }

    function summaryRows(S, stay) {
        // the requirements staff entered; where none, the rooms themselves
        const src = stay ? (stay.reqs.length ? stay.reqs : Object.values(stay.rooms.reduce((a, r) => { const k = r.room_type + '|' + (r.category || ''); (a[k] = a[k] || { room_type:r.room_type, category:r.category, rooms:0 }).rooms++; return a; }, {}))) : [];
        let h = '', tot = 0;
        Object.entries(S.data.types).forEach(([k, w]) => {
            const rows = src.filter(r => r.room_type === k);
            if (!rows.length) { h += '<tr><td>' + w + '</td><td>–</td><td>0</td></tr>'; return; }
            rows.forEach(r => { tot += Number(r.rooms); h += '<tr><td>' + w + '</td><td>' + (r.category ? '<b>' + esc(r.category) + '</b>' : '–') + '</td><td><b>' + Number(r.rooms) + '</b></td></tr>'; });
        });
        return h + '<tr class="tot"><td colspan="2">Total Rooms</td><td>' + tot + '</td></tr>';
    }

    function draw(S) {
        const d = S.data, stay = curStay(S), ro = !!d.locked, dis = ro ? ' disabled' : '';
        const tab = document.querySelector('.inq-tab[data-tab="pax"]'); if (tab) tab.innerHTML = '<i class="bi oci oci-group"></i> Passengers (' + d.passengers.length + ')';
        const list = filtered(S, stay), pages = Math.max(1, Math.ceil(list.length / S.per)); if (S.page > pages) S.page = pages;
        const view = list.slice((S.page - 1) * S.per, S.page * S.per);
        const nats = [...new Set(d.passengers.map(p => p.nationality).filter(Boolean))].sort();
        const cnt = { adult:0, child:0, infant:0 }; d.passengers.forEach(p => cnt[ptype(p)] = (cnt[ptype(p)] || 0) + 1);
        const inRoom = stay ? new Set(stay.rooms.flatMap(r => r.pax)).size : 0, cap = stay ? stay.rooms.reduce((a, r) => a + beds(S, r), 0) : 0;
        const complete = stay && stay.rooms.length && !stay.flags.some(f => ['over', 'under', 'unalloc'].includes(f.kind));
        let h = '';
        if (d.locked) h += '<div class="pr-flags"><div class="pr-flag warn"><i class="bi bi-lock"></i>' + esc(d.locked) + '</div></div>';
        h += '<div class="pr-stays"><span class="lb">Hotel stay</span>' + (d.stays.length ? d.stays.map(s => '<button type="button" class="pr-stay' + (stay && s.key === stay.key ? ' on' : '') + '" data-stay="' + esc(s.key) + '"><b>' + esc(s.hotel) + '<span class="pr-st ' + s.state + '">' + STATE[s.state] + '</span></b><small>' + (s.check_in ? dm(s.check_in) + ' – ' + dm(s.check_out) : 'Day ' + s.day_from) + ' · ' + s.nights + ' night' + (s.nights === 1 ? '' : 's') + ' · ' + s.rooms.length + ' room' + (s.rooms.length === 1 ? '' : 's') + '</small></button>').join('') : '<span class="pr-note">No hotel is on the itinerary yet — room requirements are entered per hotel once the hotels are chosen. Passenger names can be entered now.</span>') + '</div>';
        const flags = (stay ? stay.flags : []).concat(d.orphans.map(o => ({ level:'warn', text:'A hotel that is no longer on the itinerary still has ' + (o.rooms ? o.rooms + ' room entr' + (o.rooms === 1 ? 'y' : 'ies') : 'room requirements') + ' recorded. They are kept for reference and not used.' })));
        if (flags.length) h += '<div class="pr-flags">' + flags.map(f => '<div class="pr-flag ' + f.level + '"><i class="bi bi-exclamation-triangle"></i><span>' + esc(f.text) + '</span></div>').join('') + '</div>';
        h += '<div class="pr-grid"><div>'
          + '<div class="pr-card"><div class="h"><i class="bi bi-door-closed"></i>Room Summary</div>'
          + '<table class="pr-mini"><thead><tr><th>Room Type</th><th>Category</th><th>Rooms</th></tr></thead><tbody>' + summaryRows(S, stay) + '</tbody></table></div>'
          + '<div class="pr-card"><div class="h"><i class="bi bi-people"></i>Passenger Count</div><table class="pr-mini"><tbody><tr><td>Adults</td><td>' + cnt.adult + '</td></tr><tr><td>Children</td><td>' + (cnt.child || 0) + '</td></tr><tr><td>Infants</td><td>' + (cnt.infant || 0) + '</td></tr><tr class="tot"><td>Total</td><td>' + d.passengers.length + '</td></tr></tbody></table></div>'
          + '</div><div class="pr-main">';
        h += '<div class="pr-top"><h3><i class="bi bi-people-fill"></i>Passenger Management</h3>'
          + '<button type="button" class="pr-btn" data-act="import"' + dis + '><i class="bi bi-upload"></i>Import Excel / CSV</button>'
          + '<button type="button" class="pr-btn" data-act="add"' + dis + '><i class="bi bi-plus-lg"></i>Add Passenger</button>'
          + '<span class="pr-menu"><button type="button" class="pr-btn" data-act="menu"><i class="bi bi-diagram-3"></i>Group Tools <i class="bi bi-chevron-down"></i></button><span class="dd">'
          + '<button type="button" data-act="reqs"' + dis + '><i class="bi bi-list-ol"></i>Room requirements of this hotel (type, category, rooms)…</button>'
          + '<button type="button" data-act="g_create"' + dis + '><i class="bi bi-plus-square"></i>Create room entries from the requirements</button>'
          + '<button type="button" data-act="room_add"' + dis + '><i class="bi bi-door-open"></i>Add a room…</button><hr>'
          + '<button type="button" data-act="g_assign"' + dis + '><i class="bi bi-box-arrow-in-right"></i>Assign selected passengers to a room…</button>'
          + '<button type="button" data-act="g_cat"' + dis + '><i class="bi bi-tags"></i>Change category of selected rooms…</button>'
          + '<button type="button" data-act="g_del"' + dis + '><i class="bi bi-trash"></i>Delete selected passengers…</button><hr>'
          + '<button type="button" data-act="g_copy"' + dis + '><i class="bi bi-copy"></i>Copy the arrangement from another hotel…</button><hr>'
          + (d.stays.length > 1 ? '<a target="_blank" href="' + BASE + '/intake/' + S.id + '/paxrooms_pdf?stay=all"><i class="bi bi-files"></i>Rooming lists — all hotels (PDF)</a>' : '')
          + '<button type="button" data-act="g_hist"><i class="bi bi-clock-history"></i>Change history</button></span></span></div>';
        h += '<div class="pr-fil"><span class="pr-sw"><i class="bi bi-search"></i><input type="search" data-f="q" placeholder="Search by name, passport, etc…" value="' + esc(S.q) + '"></span>'
          + '<select data-f="ft"><option value="">Type: All</option>' + ['adult', 'child', 'infant'].map(t => '<option value="' + t + '"' + (S.ft === t ? ' selected' : '') + '>' + t[0].toUpperCase() + t.slice(1) + '</option>').join('') + '</select>'
          + '<select data-f="fn"><option value="">Nationality: All</option>' + nats.map(n => '<option' + (S.fn === n ? ' selected' : '') + '>' + esc(n) + '</option>').join('') + '</select>'
          + '<select data-f="fr"><option value="">Room: All</option><option value="-"' + (S.fr === '-' ? ' selected' : '') + '>Not in a room</option>' + (stay ? stay.rooms.map(r => '<option value="' + r.id + '"' + (S.fr === String(r.id) ? ' selected' : '') + '>' + esc(r.room_no) + '</option>').join('') : '') + '</select>'
          + '<button type="button" class="pr-btn grey" data-act="clear">Clear</button></div>';
        h += '<div class="pr-tbl-w"><table class="pr-tbl"><thead><tr><th><input type="checkbox" data-act="all"' + (view.length && view.every(p => S.sel.has(Number(p.id))) ? ' checked' : '') + '></th><th>#</th><th>Passenger Name <span style="font-weight:400">(as per passport)</span></th><th class="srt" data-sort="type" title="Sort by type">Type <i class="bi bi-caret-' + (S.st === 'desc' ? 'up' : 'down') + '-fill" style="font-size:10px"></i></th><th>Nationality</th><th>Passport No.</th><th>Gender</th><th>Date of Birth</th><th>Room No.</th><th>Room Type</th><th>Category</th><th></th></tr></thead><tbody>';
        if (!view.length) h += '<tr><td colspan="12" class="pr-empty">' + (d.passengers.length ? 'No passenger matches the search.' : 'No passenger names yet. Room requirements can be entered without names; add or import the names when the agent sends them.') + '</td></tr>';
        view.forEach(p => {
            const pid = Number(p.id), r = roomOf(stay, pid), t = ptype(p), no = d.passengers.indexOf(p) + 1;
            h += '<tr><td><input type="checkbox" data-sel="' + pid + '"' + (S.sel.has(pid) ? ' checked' : '') + '></td><td>' + no + '</td><td class="nm">' + esc(nm(p.full_name)) + (d.dupes.includes(pid) ? '<span class="pr-dup" title="Another passenger on this inquiry has the same name. Please check: nothing was removed.">same name</span>' : '') + '</td>'
              + '<td><span class="pr-ty ' + t + '">' + t[0].toUpperCase() + t.slice(1) + '</span></td><td>' + esc(p.nationality || '–') + '</td><td>' + esc(p.passport_no || '–') + '</td><td>' + esc(p.gender || '–') + '</td><td>' + (p.dob ? dmy(p.dob) : '–') + '</td>'
              + '<td>' + (r ? '<b>' + esc(r.room_no) + '</b>' : '–') + '</td><td>' + (r ? d.types[r.room_type] : '–') + '</td>'
              + '<td>' + (r ? '<select data-rcat="' + r.id + '"' + dis + ' title="The category of room ' + esc(r.room_no) + ' at this hotel">' + catOpts(stay, r.category) + '</select>' : '–') + '</td>'
              + '<td><button type="button" class="pr-ic" data-edit="' + pid + '" title="Edit"' + dis + '><i class="bi bi-pencil"></i></button><button type="button" class="pr-ic del" data-del="' + pid + '" title="Delete"' + dis + '><i class="bi bi-trash"></i></button></td></tr>';
        });
        h += '</tbody></table></div>';
        h += '<div class="pr-pg">' + (S.sel.size ? '<span style="margin-right:auto;font-weight:700;color:var(--p)">' + S.sel.size + ' selected</span>' : '') + 'Show <select data-f="per">' + [25, 50, 100].map(n => '<option' + (S.per === n ? ' selected' : '') + '>' + n + '</option>').join('') + '</select> per page'
          + '<button type="button" data-pg="1"' + (S.page <= 1 ? ' disabled' : '') + '>«</button><button type="button" data-pg="' + (S.page - 1) + '"' + (S.page <= 1 ? ' disabled' : '') + '>‹</button>';
        for (let i = Math.max(1, S.page - 2); i <= Math.min(pages, S.page + 2); i++) h += '<button type="button" data-pg="' + i + '"' + (i === S.page ? ' class="on"' : '') + '>' + i + '</button>';
        h += '<button type="button" data-pg="' + (S.page + 1) + '"' + (S.page >= pages ? ' disabled' : '') + '>›</button><button type="button" data-pg="' + pages + '"' + (S.page >= pages ? ' disabled' : '') + '>»</button></div>';
        h += '<div class="pr-sec"><i class="bi bi-door-open"></i>Room Allocation (Detail)</div>';
        h += '<div class="pr-alloc"><div class="pr-tbl-w"><table class="pr-tbl pr-rt" style="min-width:520px"><thead><tr><th>Room No.</th><th>Room Type</th><th>Category</th><th>Occupancy</th><th>Passengers</th><th></th></tr></thead><tbody>';
        if (!stay || !stay.rooms.length) h += '<tr><td colspan="6" class="pr-empty">' + (stay ? (stay.reqs.length ? 'The requirements are entered. Use Group Tools → Create room entries from the requirements.' : 'No rooms yet for this hotel. Use Group Tools → Room requirements of this hotel, or assign a room while adding a passenger.') : 'Choose the hotels on the itinerary first.') + '</td></tr>';
        (stay ? stay.rooms : []).forEach(r => {
            const n = occ(S, r), b = beds(S, r), cls = !r.pax.length ? 'zero' : n > b ? 'over' : n < b ? 'under' : '';
            h += '<tr><td class="no">' + esc(r.room_no) + '</td><td style="text-align:center">' + d.types[r.room_type] + '</td><td class="cat">' + (r.category ? esc(r.category) : '–') + '</td><td class="occ ' + cls + '">' + n + ' / ' + b + '</td><td class="px">'
              + (r.pax.length ? r.pax.map(pid => esc(nm((d.passengers.find(x => Number(x.id) === pid) || {}).full_name))).join(', &nbsp;') : '<span style="color:var(--mut)">Names to follow</span>') + '</td><td><button type="button" class="pr-ic" data-room="' + r.id + '" title="Edit this room: number, type, category, who shares"' + dis + '><i class="bi bi-pencil"></i></button></td></tr>';
        });
        h += '</tbody></table></div><div class="pr-ok' + (complete ? '' : ' todo') + '"><h4><i class="bi ' + (complete ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill') + '"></i>' + (complete ? 'Allocation Complete' : (stay && stay.rooms.length ? 'Allocation Incomplete' : 'Allocation Not Started')) + '</h4>'
          + '<div><span>Total Passengers:</span><b>' + d.passengers.length + '</b></div><div><span>Total Rooms:</span><b>' + (stay ? stay.rooms.length : 0) + '</b></div><div><span>Total Occupancy:</span><b>' + inRoom + ' / ' + cap + '</b></div></div></div>';
        h += '<div class="pr-foot"><a class="pr-btn" target="_blank" href="' + BASE + '/intake/' + S.id + '/paxrooms_pdf?stay=' + encodeURIComponent(stay ? stay.key : 'all') + '"><i class="bi bi-file-earmark-pdf"></i>Rooming List (PDF)</a>'
          + '<a class="pr-btn" href="' + BASE + '/intake/' + S.id + '/paxrooms_export"><i class="bi bi-download"></i>Passenger List (Excel)</a></div>';
        h += '</div></div>';
        const act = document.activeElement, keep = act && act.dataset && act.dataset.f === 'q' && S.root.contains(act) ? act.selectionStart : null;
        S.root.innerHTML = h;
        if (keep !== null) { const i = S.root.querySelector('[data-f="q"]'); i.focus(); i.setSelectionRange(keep, keep); }
        bind(S);
    }

    function bind(S) {
        const R = S.root;
        if (R._prBound) { R._prS = S; return; }
        R._prBound = true; R._prS = S;
        R.addEventListener('input', e => { const S = R._prS, f = e.target.dataset.f; if (f === 'q') { S.q = e.target.value; S.page = 1; draw(S); } });
        R.addEventListener('change', async e => {
            const S = R._prS, t = e.target, f = t.dataset.f;
            if (f === 'ft' || f === 'fn' || f === 'fr') { S[f] = t.value; S.page = 1; draw(S); }
            else if (f === 'per') { S.per = Number(t.value); S.page = 1; draw(S); }
            else if (t.dataset.sel) { const id = Number(t.dataset.sel); t.checked ? S.sel.add(id) : S.sel.delete(id); draw(S); }
            else if (t.dataset.act === 'all') { const stay = curStay(S); filtered(S, stay).slice((S.page - 1) * S.per, S.page * S.per).forEach(p => t.checked ? S.sel.add(Number(p.id)) : S.sel.delete(Number(p.id))); draw(S); }
            else if (t.dataset.rcat) { const stay = curStay(S), r = stay.rooms.find(x => String(x.id) === t.dataset.rcat); const j = await post(S, 'room', { stay:stay.key, id:r.id, room_no:r.room_no, room_type:r.room_type, category:t.value }); if (j.status !== 200) { toast(j.message, false); draw(S); } }
        });
        R.addEventListener('click', e => {
            const S = R._prS, th = e.target.closest('th[data-sort]'); if (th) { S.st = S.st === 'asc' ? 'desc' : S.st === 'desc' ? '' : 'asc'; S.page = 1; draw(S); return; }
            const b = e.target.closest('button, a'); if (!b || !R.contains(b)) { R.querySelectorAll('.pr-menu.open').forEach(m => m.classList.remove('open')); return; }
            const a = b.dataset.act;
            if (a !== 'menu') R.querySelectorAll('.pr-menu.open').forEach(m => m.classList.remove('open'));
            if (b.dataset.stay) { S.stay = b.dataset.stay; S.fr = ''; draw(S); }
            else if (b.dataset.pg) { S.page = Number(b.dataset.pg); draw(S); }
            else if (b.dataset.edit) paxDialog(S, S.data.passengers.find(p => String(p.id) === b.dataset.edit));
            else if (b.dataset.del) delPax(S, [Number(b.dataset.del)]);
            else if (b.dataset.room) roomDialog(S, curStay(S).rooms.find(r => String(r.id) === b.dataset.room));
            else if (a === 'menu') b.parentNode.classList.toggle('open');
            else if (a === 'clear') { S.q = S.ft = S.fn = S.fr = ''; S.page = 1; draw(S); }
            else if (a === 'add') paxDialog(S, null);
            else if (a === 'import') importDialog(S);
            else if (a === 'reqs') reqDialog(S);
            else if (a === 'room_add') roomDialog(S, null);
            else if (a === 'g_assign') assignDialog(S);
            else if (a === 'g_cat') catDialog(S);
            else if (a === 'g_del') { if (!S.sel.size) toast('Tick the passengers to delete first.', false); else delPax(S, [...S.sel]); }
            else if (a === 'g_create') { const s = curStay(S); if (!s) return; post(S, 'create_rooms', { stay:s.key }).then(j => { if (j.status !== 200) toast(j.message, false); }); }
            else if (a === 'g_copy') copyDialog(S);
            else if (a === 'g_hist') histDialog(S);
        });
    }

    // ---------- dialogs ----------
    function modal(title, body, buttons, wide) {
        const m = document.createElement('div'); m.className = 'pr-mod';
        m.innerHTML = '<div class="box' + (wide ? ' wide' : '') + '"><div class="mh"><span>' + esc(title) + '</span><button type="button" data-x>×</button></div><div class="mb">' + body + '</div><div class="mf"><span class="msg"></span>' + buttons + '<button type="button" class="pr-btn grey" data-x>Close</button></div></div>';
        document.body.appendChild(m);
        const close = () => m.remove();
        m.addEventListener('click', e => { if (e.target === m || e.target.closest('[data-x]')) close(); });
        m.say = t => { m.querySelector('.msg').textContent = t || ''; };
        m.close = close;
        return m;
    }
    function confirmBox(text, yes) {
        return new Promise(res => {
            const m = modal('Please confirm', '<p style="margin:0">' + text + '</p>', '<button type="button" class="pr-btn p solid" data-ok>' + esc(yes) + '</button>');
            m.querySelector('[data-ok]').addEventListener('click', () => { m.close(); res(true); });
            m.addEventListener('click', e => { if (e.target === m || e.target.closest('[data-x]')) res(false); });
        });
    }
    async function delPax(S, ids) {
        const names = ids.map(i => nm((S.data.passengers.find(p => Number(p.id) === i) || {}).full_name)).filter(Boolean);
        if (!await confirmBox('Delete ' + (ids.length === 1 ? '<b>' + esc(names[0]) + '</b>' : '<b>' + ids.length + ' passengers</b> (' + esc(names.slice(0, 6).join(', ')) + (names.length > 6 ? '…' : '') + ')') + ' from this inquiry? Their room allocations at every hotel go with them. This cannot be undone.', 'Delete')) return;
        const j = await post(S, 'pax_delete', { ids });
        if (j.status === 200) ids.forEach(i => S.sel.delete(i)), draw(S); else toast(j.message, false);
    }

    function paxDialog(S, p) {
        const stay = curStay(S), cur = p ? roomOf(stay, Number(p.id)) : null, d = S.data;
        const roomOpts = () => '<option value="0">— not in a room yet —</option>' + (stay ? stay.rooms.map(r => '<option value="' + r.id + '"' + (cur && cur.id === r.id ? ' selected' : '') + '>' + esc(roomLabel(S, r)) + ' (' + occ(S, r) + '/' + beds(S, r) + ')</option>').join('') : '') + '<option value="new">+ New room…</option>';
        const m = modal(p ? 'Edit Passenger' : 'Add Passenger', '<div class="pr-form">'
          + '<div class="w2"><label>Passenger name (as per passport) *</label><input id="pp_name" maxlength="160" value="' + esc(p ? p.full_name : '') + '" placeholder="SURNAME GIVEN NAME MR/MS"></div>'
          + '<div><label>Type</label><select id="pp_type">' + ['adult', 'child', 'infant'].map(t => '<option value="' + t + '"' + (p && ptype(p) === t ? ' selected' : '') + '>' + t[0].toUpperCase() + t.slice(1) + '</option>').join('') + '</select></div>'
          + '<div><label>Nationality</label><input id="pp_nat" maxlength="60" value="' + esc(p ? p.nationality || '' : '') + '" placeholder="e.g. JPN"></div>'
          + '<div><label>Passport No. <span style="font-weight:400">(optional)</span></label><input id="pp_pp" maxlength="40" value="' + esc(p ? p.passport_no || '' : '') + '"></div>'
          + '<div><label>Gender</label><select id="pp_g"><option value="">–</option><option value="F"' + (p && p.gender === 'F' ? ' selected' : '') + '>F</option><option value="M"' + (p && p.gender === 'M' ? ' selected' : '') + '>M</option></select></div>'
          + '<div><label>Date of Birth <span style="font-weight:400">(optional)</span></label><input id="pp_dob" type="date" value="' + esc(p ? p.dob || '' : '') + '"></div>'
          + (stay ? '<div class="sub">Room at ' + esc(stay.hotel) + ' <span class="pr-note">(' + dm(stay.check_in) + ' – ' + dm(stay.check_out) + ') — each hotel keeps its own arrangement</span></div>'
          + '<div><label>Room</label><select id="pp_room">' + roomOpts() + '</select></div>'
          + '<div><label>Room Type</label><select id="pp_rt">' + typeOpts(S, cur ? cur.room_type : 'TWN') + '</select></div>'
          + '<div><label>Room Category <span style="font-weight:400">(optional)</span></label><select id="pp_rc">' + catOpts(stay, cur ? cur.category : '') + '</select></div>'
          + '<div class="w3 pr-note" id="pp_share"></div>' : '<div class="w3 pr-note">Rooms are assigned per hotel once the hotels are on the itinerary.</div>') + '</div>',
          '<button type="button" class="pr-btn solid" data-save>' + (p ? 'Save' : 'Add Passenger') + '</button>' + (p ? '' : '<button type="button" class="pr-btn" data-save="more">Add &amp; next</button>'));
        const $ = i => m.querySelector('#' + i);
        const sync = () => {
            if (!stay) return;
            const v = $('pp_room').value, r = stay.rooms.find(x => String(x.id) === v);
            $('pp_rt').disabled = $('pp_rc').disabled = v === '0';
            if (r) { $('pp_rt').value = r.room_type; $('pp_rc').value = r.category || ''; }
            const others = r ? r.pax.filter(i => !p || i !== Number(p.id)).map(i => nm((d.passengers.find(x => Number(x.id) === i) || {}).full_name)) : [];
            $('pp_share').innerHTML = v === '0' ? 'Not placed in a room at this hotel yet.' : v === 'new' ? 'A new room is created with the type and category chosen here.' : (others.length ? 'Shares with: <b>' + esc(others.join(', ')) + '</b>' : 'Nobody else is in this room yet.') + (r && occ(S, r) + (cur && cur.id === r.id ? 0 : 1) > beds(S, r) ? ' <b style="color:#a3123a">This room would be over capacity.</b>' : '');
        };
        if (stay) { $('pp_room').addEventListener('change', sync); sync(); }
        setTimeout(() => $('pp_name').focus(), 30);
        m.querySelectorAll('[data-save]').forEach(btn => btn.addEventListener('click', async () => {
            const body = { id:p ? p.id : null, full_name:$('pp_name').value, pax_type:$('pp_type').value, nationality:$('pp_nat').value, passport_no:$('pp_pp').value, gender:$('pp_g').value, dob:$('pp_dob').value };
            if (stay) { body.stay = stay.key; body.room_id = $('pp_room').value; if (body.room_id !== '0') { body.room_type = $('pp_rt').value; body.category = $('pp_rc').value; } }
            btn.disabled = true;
            const j = await post(S, 'pax', body);
            btn.disabled = false;
            if (j.status !== 200) { m.say(j.message); return; }
            m.close();
            if (btn.dataset.save === 'more') paxDialog(S, null);
        }));
    }

    function reqDialog(S) {
        const stay = curStay(S); if (!stay) return;
        const row = r => '<tr><td><select data-k="t">' + typeOpts(S, r.room_type) + '</select></td><td><select data-k="c">' + catOpts(stay, r.category, '— no category —') + '</select></td><td style="width:90px"><input data-k="n" type="number" min="0" max="500" value="' + (r.rooms || '') + '"></td><td style="width:30px"><button type="button" class="pr-ic del" data-rm title="Remove this line">×</button></td></tr>';
        const m = modal('Room requirements — ' + stay.hotel, '<p class="pr-note" style="margin:0 0 8px">What the agent needs at this hotel, with or without passenger names: room type, room category and number of rooms. The same room type may appear with different categories (for example 8 Twin Standard and 2 Twin Deluxe). Categories are '
          + (stay.categories.from_master ? 'those of this hotel in the Hotel Rate Master.' : 'the general list, because this hotel has no room types in the Hotel Rate Master yet.') + '</p>'
          + '<table class="pr-mt"><thead><tr><th>Room Type</th><th>Room Category</th><th>Rooms</th><th></th></tr></thead><tbody id="rq_b">' + (stay.reqs.length ? stay.reqs : [{ room_type:'TWN', category:'', rooms:'' }]).map(row).join('') + '</tbody></table>'
          + '<button type="button" class="pr-btn" id="rq_add" style="margin-top:8px"><i class="bi bi-plus-lg"></i>Add a line</button>'
          + '<p class="pr-note" style="margin:10px 0 0">The quotation is priced on ' + (Object.entries(S.data.inquiry.priced).filter(([, n]) => n > 0).map(([k, n]) => n + ' ' + S.data.types[k]).join(', ') || 'no rooms yet') + '. Requirements that differ are flagged for a price review; the quotation is not changed here.</p>',
          '<button type="button" class="pr-btn" data-save="make">Save &amp; create room entries</button><button type="button" class="pr-btn solid" data-save>Save</button>');
        const tb = m.querySelector('#rq_b');
        m.querySelector('#rq_add').addEventListener('click', () => tb.insertAdjacentHTML('beforeend', row({ room_type:'TWN', category:'', rooms:'' })));
        tb.addEventListener('click', e => { if (e.target.closest('[data-rm]')) e.target.closest('tr').remove(); });
        m.querySelectorAll('[data-save]').forEach(btn => btn.addEventListener('click', async () => {
            const rows = [...tb.querySelectorAll('tr')].map(tr => ({ room_type:tr.querySelector('[data-k=t]').value, category:tr.querySelector('[data-k=c]').value, rooms:Number(tr.querySelector('[data-k=n]').value || 0) }));
            let j = await post(S, 'reqs', { stay:stay.key, rows }, btn.dataset.save === 'make');
            if (j.status === 200 && btn.dataset.save === 'make') j = await post(S, 'create_rooms', { stay:stay.key });
            if (j.status !== 200) { m.say(j.message); return; }
            m.close();
        }));
    }

    function roomDialog(S, r) {
        const stay = curStay(S); if (!stay) return; const d = S.data;
        const free = d.passengers.filter(p => !roomOf(stay, Number(p.id)));
        const m = modal(r ? 'Room ' + r.room_no + ' — ' + stay.hotel : 'Add Room — ' + stay.hotel, '<div class="pr-form">'
          + '<div><label>Room No.</label><input id="rm_no" maxlength="12" value="' + esc(r ? r.room_no : '') + '" placeholder="next number"></div>'
          + '<div><label>Room Type</label><select id="rm_t">' + typeOpts(S, r ? r.room_type : 'TWN') + '</select></div>'
          + '<div><label>Room Category <span style="font-weight:400">(optional)</span></label><select id="rm_c">' + catOpts(stay, r ? r.category : '') + '</select></div>'
          + (r ? '<div class="sub">Who shares this room</div><div class="w3" id="rm_px">' + (r.pax.length ? r.pax.map(pid => '<div style="display:flex;align-items:center;gap:8px;padding:3px 0"><span style="flex:1">' + esc(nm((d.passengers.find(x => Number(x.id) === pid) || {}).full_name)) + '</span><button type="button" class="pr-btn grey" data-out="' + pid + '">Take out</button></div>').join('') : '<span class="pr-note">Nobody yet — names to follow.</span>') + '</div>'
          + '<div class="w2"><label>Add a passenger to this room</label><select id="rm_add"><option value="">' + (free.length ? '— choose —' : 'Everyone is in a room at this hotel') + '</option>' + free.map(p => '<option value="' + p.id + '">' + esc(nm(p.full_name)) + '</option>').join('') + '</select></div><div style="align-self:end"><button type="button" class="pr-btn" id="rm_addb">Add to room</button></div>' : '') + '</div>',
          (r ? '<button type="button" class="pr-btn p" data-delroom>Delete room</button>' : '') + '<button type="button" class="pr-btn solid" data-save>Save</button>');
        const $ = i => m.querySelector('#' + i);
        const reopen = () => { m.close(); const nr = curStay(S).rooms.find(x => r && x.id === r.id); if (nr) roomDialog(S, nr); };
        m.querySelector('[data-save]').addEventListener('click', async () => {
            const j = await post(S, 'room', { stay:stay.key, id:r ? r.id : null, room_no:$('rm_no').value, room_type:$('rm_t').value, category:$('rm_c').value });
            if (j.status !== 200) m.say(j.message); else m.close();
        });
        if (r) {
            m.querySelector('#rm_px').addEventListener('click', async e => { const b = e.target.closest('[data-out]'); if (!b) return; const j = await post(S, 'assign', { stay:stay.key, room_id:0, pax_ids:[Number(b.dataset.out)] }, true); if (j.status !== 200) m.say(j.message); else reopen(); });
            $('rm_addb').addEventListener('click', async () => { const v = $('rm_add').value; if (!v) return; const j = await post(S, 'assign', { stay:stay.key, room_id:r.id, pax_ids:[Number(v)] }, true); if (j.status !== 200) m.say(j.message); else reopen(); });
            m.querySelector('[data-delroom]').addEventListener('click', async () => {
                if (!await confirmBox('Delete room <b>' + esc(r.room_no) + '</b> at ' + esc(stay.hotel) + '?' + (r.pax.length ? ' Its ' + r.pax.length + ' passenger' + (r.pax.length === 1 ? '' : 's') + ' stay on the inquiry, without a room at this hotel.' : ''), 'Delete room')) return;
                const j = await post(S, 'room_delete', { id:r.id }); if (j.status !== 200) m.say(j.message); else m.close();
            });
        }
    }

    function assignDialog(S) {
        const stay = curStay(S); if (!stay) return toast('Choose a hotel stay first.', false);
        if (!S.sel.size) return toast('Tick the passengers to assign first.', false);
        if (!stay.rooms.length) return toast('This hotel has no rooms yet. Create them from the requirements, or use Add Room.', false);
        const ids = [...S.sel], n = ids.filter(i => ptype(S.data.passengers.find(p => Number(p.id) === i) || {}) !== 'infant').length;
        const m = modal('Assign ' + ids.length + ' passenger' + (ids.length === 1 ? '' : 's') + ' — ' + stay.hotel, '<p style="margin:0 0 8px">' + esc(ids.map(i => nm((S.data.passengers.find(p => Number(p.id) === i) || {}).full_name)).join(', ')) + '</p><div class="pr-form"><div class="w3"><label>Room</label><select id="as_r">'
          + stay.rooms.map(r => '<option value="' + r.id + '">' + esc(roomLabel(S, r)) + ' — ' + occ(S, r) + ' / ' + beds(S, r) + '</option>').join('') + '<option value="0">— take them out of their rooms at this hotel —</option></select></div><div class="w3 pr-note" id="as_n"></div></div>',
          '<button type="button" class="pr-btn solid" data-save>Assign</button>');
        const sel = m.querySelector('#as_r'), note = m.querySelector('#as_n');
        const chk = () => { const r = stay.rooms.find(x => String(x.id) === sel.value); if (!r) { note.textContent = ''; return true; } const stayIn = r.pax.filter(i => !ids.includes(i) && ptype(S.data.passengers.find(p => Number(p.id) === i) || {}) !== 'infant').length; const over = stayIn + n > beds(S, r); note.innerHTML = over ? '<b style="color:#a3123a">' + (stayIn + n) + ' people for ' + beds(S, r) + ' bed' + (beds(S, r) === 1 ? '' : 's') + ' — over capacity. You may still assign; the room is flagged.</b>' : 'Fits: ' + (stayIn + n) + ' / ' + beds(S, r) + '.'; return !over; };
        sel.addEventListener('change', chk); chk();
        m.querySelector('[data-save]').addEventListener('click', async () => { const j = await post(S, 'assign', { stay:stay.key, room_id:Number(sel.value), pax_ids:ids }); if (j.status !== 200) m.say(j.message); else { S.sel.clear(); draw(S); m.close(); } });
    }

    function catDialog(S) {
        const stay = curStay(S); if (!stay) return toast('Choose a hotel stay first.', false);
        if (!stay.rooms.length) return toast('This hotel has no rooms yet.', false);
        const m = modal('Change category of selected rooms — ' + stay.hotel, '<div class="pr-form"><div class="w3"><label>New category</label><select id="ct_c">' + catOpts(stay, '', '— no category —') + '</select></div></div>'
          + '<table class="pr-mt" style="margin-top:10px"><thead><tr><th style="width:30px"><input type="checkbox" id="ct_all"></th><th>Room</th><th>Type</th><th>Category now</th><th>Passengers</th></tr></thead><tbody>'
          + stay.rooms.map(r => '<tr><td><input type="checkbox" data-r="' + r.id + '"></td><td><b>' + esc(r.room_no) + '</b></td><td>' + S.data.types[r.room_type] + '</td><td>' + esc(r.category || '–') + '</td><td>' + esc(r.pax.map(pid => nm((S.data.passengers.find(x => Number(x.id) === pid) || {}).full_name)).join(', ') || 'Names to follow') + '</td></tr>').join('') + '</tbody></table>',
          '<button type="button" class="pr-btn solid" data-save>Change category</button>', true);
        m.querySelector('#ct_all').addEventListener('change', e => m.querySelectorAll('[data-r]').forEach(c => c.checked = e.target.checked));
        m.querySelector('[data-save]').addEventListener('click', async () => {
            const ids = [...m.querySelectorAll('[data-r]:checked')].map(c => Number(c.dataset.r)); if (!ids.length) return m.say('Tick the rooms first.');
            const j = await post(S, 'rooms_category', { stay:stay.key, ids, category:m.querySelector('#ct_c').value }); if (j.status !== 200) m.say(j.message); else m.close();
        });
    }

    function copyDialog(S) {
        const stay = curStay(S); if (!stay) return;
        const from = S.data.stays.filter(s => s.key !== stay.key && (s.rooms.length || s.reqs.length));
        if (!from.length) return toast('No other hotel has room requirements or rooms to copy yet.', false);
        const m = modal('Copy the arrangement to ' + stay.hotel, '<div class="pr-form"><div class="w3"><label>Copy from</label><select id="cp_f">' + from.map(s => '<option value="' + esc(s.key) + '">' + esc(s.hotel) + ' (' + dm(s.check_in) + ') — ' + s.rooms.length + ' rooms</option>').join('') + '</select></div></div>'
          + '<p class="pr-note" style="margin:10px 0 0">The requirements, rooms and who shares are copied as a starting point; you can then change them for this hotel. A room category is kept only where this hotel has the same category in the Hotel Rate Master; otherwise it is left blank for you to choose. Nothing is copied if this hotel already has an arrangement.</p>',
          '<button type="button" class="pr-btn solid" data-save>Copy</button>');
        m.querySelector('[data-save]').addEventListener('click', async () => { const j = await post(S, 'copy', { from:m.querySelector('#cp_f').value, to:stay.key }); if (j.status !== 200) m.say(j.message); else m.close(); });
    }

    function histDialog(S) {
        const name = k => (S.data.stays.find(s => s.key === k) || {}).hotel || '';
        modal('Change history — passengers and rooms', S.data.history.length ? '<table class="pr-mt"><thead><tr><th>When</th><th>By</th><th>What</th><th>Detail</th></tr></thead><tbody>' + S.data.history.map(h => '<tr><td style="white-space:nowrap">' + esc(new Date(h.at.replace(' ', 'T')).toLocaleString('en-GB', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' })) + '</td><td>' + esc(h.by || '') + '</td><td style="white-space:nowrap">' + esc(h.action) + '</td><td>' + esc(h.detail || name(h.stay_key)) + '</td></tr>').join('') + '</tbody></table>' : '<p class="pr-note">No changes recorded yet.</p>', '', true);
    }

    function importDialog(S) {
        const m = modal('Import passenger list — Excel / CSV', '<p class="pr-note" style="margin:0 0 8px">An Excel (.xlsx) or CSV file with a heading row. Columns are recognised by their headings: <b>Name</b> (required), Type, Nationality, Passport, Gender, Date of birth. Nothing is saved until you press Import.</p><input type="file" id="im_f" accept=".xlsx,.csv,.txt"><div id="im_p" style="margin-top:10px"></div>',
          '<button type="button" class="pr-btn solid" data-save disabled>Import ticked rows</button>', true);
        let rows = [];
        const norm = s => String(s || '').toUpperCase().replace(/[^A-Z0-9]+/g, '');
        m.querySelector('#im_f').addEventListener('change', async e => {
            const f = e.target.files[0]; if (!f) return; m.say('');
            const fd = new FormData(); fd.append('file', f);
            let j; try { j = await (await fetch(BASE + '/intake/' + S.id + '/paxrooms/parse', { method:'POST', body:fd, headers:{ 'X-Requested-With':'XMLHttpRequest' } })).json(); } catch (x) { j = { status:500, message:String(x) }; }
            if (j.status !== 200) { m.say(j.message); return; }
            rows = j.rows; const have = new Set(S.data.passengers.map(p => norm(p.full_name))), seen = new Set();
            m.querySelector('#im_p').innerHTML = rows.length ? '<table class="pr-mt"><thead><tr><th style="width:30px"></th><th>#</th><th>Name</th><th>Type</th><th>Nationality</th><th>Passport</th><th>Gender</th><th>Date of birth</th><th>Check</th></tr></thead><tbody>' + rows.map((r, i) => {
                const k = norm(r.full_name), dup = have.has(k) ? 'Same name is already on this inquiry' : seen.has(k) ? 'Same name twice in this file' : ''; seen.add(k);
                return '<tr><td><input type="checkbox" data-i="' + i + '" checked></td><td>' + (i + 1) + '</td><td><b>' + esc(r.full_name) + '</b></td><td>' + esc(r.pax_type || '') + '</td><td>' + esc(r.nationality || '') + '</td><td>' + esc(r.passport_no || '') + '</td><td>' + esc(r.gender || '') + '</td><td>' + esc(r.dob || '') + '</td><td>' + (dup ? '<span class="pr-dup" style="margin:0">' + dup + '</span>' : '') + '</td></tr>'; }).join('') + '</tbody></table><p class="pr-note" style="margin:8px 0 0">' + rows.length + ' name' + (rows.length === 1 ? '' : 's') + ' read. Names marked in the Check column match another name: they are still ticked — untick any you do not want. Nothing is skipped or deleted automatically.</p>' : '<p class="pr-note">No names were found in the file.</p>';
            m.querySelector('[data-save]').disabled = !rows.length;
        });
        m.querySelector('[data-save]').addEventListener('click', async () => {
            const pick = [...m.querySelectorAll('[data-i]:checked')].map(c => rows[Number(c.dataset.i)]); if (!pick.length) return m.say('Tick at least one row.');
            const j = await post(S, 'import', { rows:pick }); if (j.status !== 200) m.say(j.message); else m.close();
        });
    }

    window.PaxRooms = { mount };
})();
</script>
