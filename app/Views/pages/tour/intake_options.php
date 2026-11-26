<?= view('layouts/head') ?>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    /* ---------------- tf-roomopt-r2 (25 Nov 2026, Shantha's approved mock-up): Room options → Quotation rates → PDF preview ---------------- */
    .rq { --p:#842484; --p2:#6a1c6a; --g:#78b848; --ink:#1c1a21; --mute:#4a4652; --line:#d8d3de; --soft:#f5f3f7; --amber:#8a5a00; --amber-bg:#fdf3dc; --red:#a3261d; --red-bg:#fbe9e7; --ok:#2e6b1f; --ok-bg:#eaf5e2;
        font-family:'Figtree','Segoe UI',system-ui,sans-serif; font-size:18px; line-height:1.55; color:var(--ink); max-width:1180px; margin:0 auto 60px; }
    .rq * { box-sizing:border-box; }
    .rq-top { background:linear-gradient(180deg,#9d4c9d 0%,#842484 60%,#7a207a 100%); color:#fff; border-radius:16px 16px 0 0; padding:22px 32px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
    .rq-top a { color:#fff; text-decoration:none; font-size:20px; } .rq-top a:hover { text-decoration:underline; }
    .rq-top .back { margin-left:auto; border:2px solid rgba(255,255,255,.8); border-radius:10px; padding:8px 18px; font-size:17px; font-weight:600; min-height:48px; display:inline-flex; align-items:center; }
    .rq-body { background:#f7f5f9; border:1px solid var(--line); border-top:0; border-radius:0 0 16px 16px; padding:30px 32px 36px; }
    .rq h1 { font-size:30px; font-weight:800; margin:0 0 8px; line-height:1.2; }
    .rq h2 { font-size:26px; font-weight:800; margin:0 0 16px; }
    .rq h3 { font-size:23px; font-weight:800; margin:0; }
    .rq .sub { color:var(--mute); font-size:18px; margin:0; }
    .rq .test { display:inline-block; background:#fff3cd; color:#7a5300; border:1px solid #e9cf7a; border-radius:8px; padding:2px 10px; font-size:15px; font-weight:700; margin-left:6px; }
    .rq-steps { display:flex; gap:14px; flex-wrap:wrap; margin:22px 0 26px; }
    .rq-steps button { min-height:52px; padding:0 24px; border:2px solid var(--line); background:#fff; color:var(--ink); border-radius:12px; font:inherit; font-size:19px; font-weight:600; cursor:pointer; }
    .rq-steps button.on { background:var(--p); border-color:var(--p); color:#fff; }
    .rq-steps button:focus-visible, .rq button:focus-visible, .rq select:focus-visible, .rq input:focus-visible { outline:3px solid #f0b400; outline-offset:2px; }
    .rq-card { background:#fff; border:1.5px solid var(--line); border-radius:16px; padding:24px 28px; margin-bottom:20px; }
    .rq-card.off { border-style:dashed; background:#fbfafc; }
    .rq-card.chosen { border-color:var(--g); box-shadow:0 0 0 2px rgba(120,184,72,.35); }
    .rq-ol { color:var(--p); font-weight:800; letter-spacing:.04em; font-size:18px; text-transform:uppercase; margin:0 0 6px; }
    .rq-room { border-top:1.5px solid #e6e1ea; padding:16px 0 4px; }
    .rq-room b { font-size:20px; display:block; } .rq-room span { color:var(--mute); display:block; font-size:18px; }
    .rq-note { color:var(--mute); font-size:17px; margin:10px 0 0; }
    .rq-warn { background:var(--amber-bg); color:var(--amber); border-radius:10px; padding:12px 16px; margin-top:14px; font-size:17px; }
    .rq-err { background:var(--red-bg); color:var(--red); border-radius:10px; padding:12px 16px; margin-top:14px; font-size:17px; }
    .rq-okb { background:var(--ok-bg); color:var(--ok); border-radius:10px; padding:12px 16px; margin-top:14px; font-size:17px; }
    .rq-row { display:flex; gap:14px; align-items:center; flex-wrap:wrap; margin-top:18px; }
    .rq-chk { display:inline-flex; align-items:center; gap:12px; font-size:19px; cursor:pointer; min-height:48px; }
    .rq-chk input { width:26px; height:26px; accent-color:var(--p); }
    .rq-btn { min-height:48px; padding:0 22px; border-radius:10px; font:inherit; font-size:18px; font-weight:700; cursor:pointer; border:2px solid var(--p); background:#fff; color:var(--p); }
    .rq-btn.g { background:var(--g); border-color:var(--g); color:#14210b; } .rq-btn.p { background:var(--p); color:#fff; }
    .rq-btn.q { border:1.5px solid var(--line); color:var(--mute); font-weight:600; }
    .rq-btn:disabled { opacity:.45; cursor:not-allowed; }
    .rq-next { display:flex; justify-content:flex-end; margin-top:8px; }
    .rq-status { color:var(--mute); font-size:19px; margin:20px 0 0; }
    .rq-ed { background:#fff; border:2px solid var(--p); border-radius:16px; padding:24px 28px; margin-bottom:20px; }
    .rq-ed label.f { display:flex; flex-direction:column; gap:6px; font-weight:600; font-size:17px; }
    .rq-ed input[type=text], .rq-ed select { min-height:48px; border:1.5px solid #bdb5c6; border-radius:10px; padding:0 12px; font:inherit; font-size:18px; background:#fff; color:var(--ink); }
    .rq-rooms { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin:18px 0; }
    .rq-rooms .r { border:1.5px solid #e1dbe6; border-radius:12px; padding:14px; background:var(--soft); display:flex; flex-direction:column; gap:10px; }
    .rq-who { width:100%; border-collapse:collapse; margin-top:8px; }
    .rq-who th { text-align:left; font-size:16px; color:var(--mute); padding:8px 10px; border-bottom:1.5px solid #e6e1ea; }
    .rq-who td { padding:10px; border-bottom:1px solid #eee9f1; vertical-align:middle; }
    .rq-who td select { width:100%; }
    .rq-t { width:100%; border-collapse:collapse; font-size:17px; margin-top:10px; }
    .rq-t th { background:#f1eef4; text-align:left; padding:10px 12px; font-weight:700; }
    .rq-t td { padding:10px 12px; border-top:1px solid #e6e1ea; vertical-align:top; }
    .rq-t .r { text-align:right; white-space:nowrap; }
    .rq-t tr.tot td { font-weight:800; background:#f3eaf3; color:var(--p); font-size:19px; }
    .rq-scroll { overflow-x:auto; }
    .rq-price { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-top:16px; }
    .rq-price div { background:var(--soft); border-radius:12px; padding:14px 16px; } .rq-price small { display:block; color:var(--mute); font-size:16px; } .rq-price b { font-size:22px; }
    .rq-price .fam { background:#f3eaf3; } .rq-price .fam b { color:var(--p); font-size:25px; }
    .rq-frame { width:100%; height:880px; border:1.5px solid var(--line); border-radius:12px; background:#fff; }
    .rq-empty { color:var(--mute); padding:20px 0; }
    /* tf-roomopt-r3: the numbered missing items */
    .rq-ck { background:#fff; border:1.5px solid var(--line); border-radius:16px; padding:22px 26px; margin-bottom:22px; }
    .rq-ckh { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
    .rq-bar { flex:1 1 260px; height:14px; background:#ece8f0; border-radius:99px; overflow:hidden; } .rq-bar i { display:block; height:100%; background:var(--g); }
    .rq-it { border:1.5px solid #e6e1ea; border-left:6px solid #c0392b; border-radius:12px; padding:16px 18px; margin-top:14px; }
    .rq-it.ok { border-left-color:#3d8a26; background:#fafdf7; } .rq-it.re { border-left-color:#d08a00; }
    .rq-it .h { display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap; } .rq-it .n { font-weight:800; font-size:20px; min-width:34px; color:var(--p); }
    .rq-pill { border-radius:99px; padding:3px 12px; font-size:15px; font-weight:700; } .rq-pill.open { background:var(--red-bg); color:var(--red); } .rq-pill.ok { background:var(--ok-bg); color:var(--ok); } .rq-pill.re { background:var(--amber-bg); color:var(--amber); }
    .rq-it dl { display:grid; grid-template-columns:minmax(150px, 210px) 1fr; gap:6px 14px; margin:12px 0 0; font-size:17px; } .rq-it dt { color:var(--mute); font-weight:600; } .rq-it dd { margin:0; }
    .rq-it textarea { width:100%; min-height:64px; border:1.5px solid #bdb5c6; border-radius:10px; padding:10px 12px; font:inherit; font-size:17px; }
    @media (max-width:760px) { .rq-it dl { grid-template-columns:1fr; } }
    @media (max-width:760px) { .rq-top, .rq-body { padding:18px 16px; } .rq h1 { font-size:26px; } .rq-card, .rq-ed { padding:18px; } .rq-frame { height:600px; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content" id="tf">
            <div class="container-fluid">
                <div class="rq" id="rq">
                    <div class="rq-top"><a href="<?= base_url('tour/intake') ?>">Tour Desk</a><span aria-hidden="true">/</span><a id="rq_crumb" href="<?= base_url('tour/intake?id=' . (int) $intake_id) ?>">Inquiry</a>
                        <a class="back" href="<?= base_url('tour/intake?id=' . (int) $intake_id) ?>">← Back to the inquiry</a></div>
                    <div class="rq-body">
                        <h1>Room options &amp; quotation</h1>
                        <p class="sub" id="rq_sub">Reading the inquiry…</p>
                        <p class="sub" id="rq_sub2"></p>
                        <div class="rq-steps" role="tablist">
                            <button type="button" role="tab" data-step="1">1. Room options</button>
                            <button type="button" role="tab" data-step="2">2. Quotation rates</button>
                            <button type="button" role="tab" data-step="3">3. PDF preview</button>
                        </div>
                        <div id="rq_main"></div>
                    </div>
                </div>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(() => {
    const BASE = '<?= base_url('tour/intake') ?>', ID = <?= (int) $intake_id ?>, SEES = <?= !empty($sees_pricing) ? 'true' : 'false' ?>;
    const TYPES = { family: 'Family room', single: 'Single room', double: 'Double room', twin: 'Twin room', triple: 'Triple room' };
    const BEDS = { family: 5, single: 1, double: 2, twin: 2, triple: 3 };
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const api = async (p, o) => { try { const r = await fetch(BASE + p, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, o || {})); return await r.json(); } catch (e) { return { status: 0, message: 'The system could not be reached. Try again.' }; } };
    const post = (p, b) => api(p, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(b || {}) });
    const say = (icon, text) => (window.Swal ? Swal.fire({ icon, text, confirmButtonColor: '#842484' }) : alert(text));
    const ask = async text => window.Swal ? (await Swal.fire({ text, icon: 'question', showCancelButton: true, confirmButtonText: 'Yes', cancelButtonText: 'Cancel', confirmButtonColor: '#842484' })).isConfirmed : confirm(text);
    const money = v => v == null ? '—' : 'USD ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtD = d => { const t = new Date(d + 'T00:00:00Z'); return isNaN(t) ? d : t.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' }); };
    let IT = null, STEP = Number(new URLSearchParams(location.search).get('step') || 1), EDIT = null, COST = null, QUOTE = null;

    async function load() {
        const j = await api('/intake/' + ID);
        if (j.status !== 200) { document.getElementById('rq_main').innerHTML = `<div class="rq-err">${esc(j.message || 'The inquiry could not be read.')}</div>`; return; }
        IT = j.data; COST = null; CHECK = null;
        const kids = (IT.party_slots || []).filter(s => s.kind === 'child');
        document.getElementById('rq_crumb').textContent = 'Inquiry ' + String(IT.inquiry_no || '').replace(/^INQ\s*/, '');
        const n = Number((IT.tour_dates && IT.tour_dates.nights) || IT.nights || 0);
        const a = IT.arrival_date, z = IT.departure_date;
        document.getElementById('rq_sub').textContent = `${a && z ? fmtD(a).replace(/ \d{4}$/, '') + ' – ' + fmtD(z) : 'Dates not set'} · ${n} night${n === 1 ? '' : 's'} · ${Number(IT.pax_adults || 0)} adult${Number(IT.pax_adults || 0) === 1 ? '' : 's'}${kids.length ? ' & ' + kids.length + ' child' + (kids.length === 1 ? '' : 'ren') : ''}`;
        document.getElementById('rq_sub2').textContent = kids.length ? 'Children aged ' + kids.map(k => k.age ?? '?').join(', ').replace(/, ([^,]*)$/, ' and $1') : '';
        draw();
    }
    function setStep(s) { STEP = s; history.replaceState(null, '', '?step=' + s); draw(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    document.querySelectorAll('[data-step]').forEach(b => b.addEventListener('click', () => setStep(Number(b.dataset.step))));
    function draw() {
        document.querySelectorAll('[data-step]').forEach(b => { const on = Number(b.dataset.step) === STEP; b.classList.toggle('on', on); b.setAttribute('aria-selected', on); });
        if (STEP === 1) drawOptions(); else if (STEP === 2) drawRates(); else drawPdf();
    }
    const opts = () => IT.room_options || [];
    const slotOf = s => (IT.party_slots || []).find(x => x.slot === s);
    const status = () => { const inc = opts().filter(o => o.included).length, ch = opts().find(o => o.chosen); return `${inc} option${inc === 1 ? '' : 's'} included · ${ch ? 'Agent chose: ' + esc(ch.name) : 'No option selected for booking'}`; };

    // ---------- step 1: room options ----------
    function roomBlock(rm) {
        const gs = rm.guests || null;
        let who = '';
        if (gs) who = gs.map(g => { const s = slotOf(g.slot); return s ? esc(s.kind === 'adult' ? (s.name || s.label) : 'Child ' + (s.age ?? '?') + (s.name ? ' (' + s.name + ')' : '')) + (s.kind === 'child' && !g.bed ? ' — shares a bed' : '') : 'someone not in the party'; }).join(', ');
        else who = esc((rm.label || '').replace(/^[^—]*— /, ''));
        return `<div class="rq-room"><b>${esc(TYPES[rm.type] || 'Room')}</b><span>${who || 'Nobody yet'}</span></div>`;
    }
    function drawOptions() {
        const m = document.getElementById('rq_main'), list = opts(), locked = !!IT.locked;
        m.innerHTML = `<h2>${list.length ? (['', 'One way', 'Two ways', 'Three ways', 'Four ways', 'Five ways'][list.length] || list.length + ' ways') + ' to stay' : 'Ways to stay'}</h2>
            ${EDIT ? editorHtml() : ''}
            ${list.length ? '' : '<p class="rq-empty">No room options yet. Add the first way the party could share rooms.</p>'}
            ${list.map(o => `<div class="rq-card ${o.included ? '' : 'off'} ${o.chosen ? 'chosen' : ''}">
                <p class="rq-ol">Option ${o.no}${o.chosen ? ' · Agent\'s choice' : ''}</p><h3>${esc(o.name)}</h3>
                ${(o.rooms || []).map(roomBlock).join('')}
                ${o.note ? `<p class="rq-note">${esc(o.note)}</p>` : ''}
                ${(o.check || []).length ? `<div class="rq-warn">${o.check.map(esc).join('<br>')}</div>` : ''}
                <div class="rq-row"><label class="rq-chk"><input type="checkbox" data-inc="${o.id}" ${o.included ? 'checked' : ''} ${locked ? 'disabled' : ''}> Include in quotation</label>
                    ${locked ? '' : `<span style="flex:1"></span><button type="button" class="rq-btn" data-edit="${o.id}">Edit</button><button type="button" class="rq-btn q" data-del="${o.id}">Remove</button>`}</div>
            </div>`).join('')}
            ${locked ? '' : `<div class="rq-row" style="margin-top:4px"><button type="button" class="rq-btn" data-add>+ Add another option</button></div>`}
            <div class="rq-next"><button type="button" class="rq-btn g" data-go2 ${list.some(o => o.included) ? '' : 'disabled'}>Review quotation rates →</button></div>
            <p class="rq-status">${status()}</p>`;
        m.querySelectorAll('[data-inc]').forEach(c => c.addEventListener('change', async () => { const j = await post('/intake/' + ID + '/roomopt/' + c.dataset.inc, { included: c.checked }); if (j.status !== 200) say('error', j.message); IT = j.data || IT; COST = null; CHECK = null; draw(); }));
        m.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => openEdit(Number(b.dataset.edit))));
        m.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', async () => { if (!(await ask('Remove this room option? The inquiry and its other options stay as they are.'))) return; const j = await post('/intake/' + ID + '/roomopt/' + b.dataset.del, { delete: 1 }); if (j.status !== 200) return say('error', j.message); IT = j.data; COST = null; CHECK = null; draw(); }));
        m.querySelectorAll('[data-add]').forEach(b => b.addEventListener('click', () => openEdit(null)));
        m.querySelectorAll('[data-go2]').forEach(b => b.addEventListener('click', () => setStep(2)));
        if (EDIT) bindEditor();
    }
    function openEdit(id) {
        const o = id ? opts().find(x => x.id === id) : null;
        let rooms;
        if (o && (o.rooms || []).length && o.rooms[0].guests) rooms = JSON.parse(JSON.stringify(o.rooms.map(r => ({ type: r.type, guests: r.guests }))));
        else if (o) { rooms = o.rooms.map(r => ({ type: r.type, guests: [] })); }   // an option saved before names: people are placed again
        else rooms = [{ type: 'twin', guests: [] }];
        EDIT = { id: o ? o.id : null, name: o ? o.name : '', note: o ? (o.note || '') : '', rooms };
        draw(); setTimeout(() => { const el = document.getElementById('rq_name'); if (el) { el.focus(); el.scrollIntoView({ behavior: 'smooth', block: 'center' }); } }, 50);
    }
    function editorHtml() {
        const E = EDIT, slots = IT.party_slots || [];
        const where = {}; E.rooms.forEach((r, ri) => (r.guests || []).forEach(g => { where[g.slot] = { ri, bed: !!g.bed }; }));
        return `<div class="rq-ed" id="rq_ed"><h3 style="margin-bottom:16px">${E.id ? 'Edit option' : 'Add a room option'}</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px">
                <label class="f">Option name<input type="text" id="rq_name" maxlength="120" value="${esc(E.name)}" placeholder="e.g. One twin + one triple"></label>
                <label class="f">Note for staff (optional)<input type="text" id="rq_note" maxlength="300" value="${esc(E.note)}" placeholder="e.g. Confirm bedding with each hotel"></label></div>
            <div class="rq-rooms">${E.rooms.map((r, ri) => `<div class="r"><b style="font-size:19px">Room ${ri + 1}</b>
                <label class="f">Room type<select data-rt="${ri}">${Object.entries(TYPES).map(([k, w]) => `<option value="${k}" ${r.type === k ? 'selected' : ''}>${w}</option>`).join('')}</select></label>
                <span style="font-size:16px;color:#4a4652">Sleeps ${BEDS[r.type] || '?'} in beds · the hotel's category (Superior, Deluxe …) comes from each night</span>
                ${E.rooms.length > 1 ? `<button type="button" class="rq-btn q" data-rmroom="${ri}">Remove room ${ri + 1}</button>` : ''}</div>`).join('')}
                <div class="r" style="justify-content:center;align-items:center;background:#fff"><button type="button" class="rq-btn" data-addroom>+ Add a room</button></div></div>
            <h3 style="font-size:22px">Who sleeps where</h3>
            <div class="rq-scroll"><table class="rq-who"><thead><tr><th>Guest</th><th>Room</th><th>Bed</th></tr></thead><tbody>
            ${slots.map(s => { const w = where[s.slot]; return `<tr><td><b>${esc(s.kind === 'adult' ? (s.name || s.label) : 'Child ' + (s.age ?? '?'))}</b>${s.kind === 'child' && s.name ? `<br><span style="color:#4a4652;font-size:16px">${esc(s.name)}</span>` : ''}</td>
                <td><select data-slot="${s.slot}" aria-label="Room for ${esc(s.label)}"><option value="">Not decided — confirm with the agent</option>${E.rooms.map((r, ri) => `<option value="${ri}" ${w && w.ri === ri ? 'selected' : ''}>Room ${ri + 1} · ${TYPES[r.type]}</option>`).join('')}</select></td>
                <td>${s.kind === 'adult' ? 'Own bed' : `<select data-bed="${s.slot}" aria-label="Bed for ${esc(s.label)}"><option value="1" ${!w || w.bed ? 'selected' : ''}>Own bed</option><option value="0" ${w && !w.bed ? 'selected' : ''}>Shares a bed (no extra bed)</option></select>`}</td></tr>`; }).join('')}
            </tbody></table></div>
            <p class="rq-note">Every guest goes in exactly one room. A child who shares a bed is still a child guest: the hotel's child rate is charged unless the Child Fare Review says otherwise.</p>
            <div class="rq-row"><button type="button" class="rq-btn g" data-save>Save option</button><button type="button" class="rq-btn q" data-cancel>Cancel</button></div></div>`;
    }
    function readEditor() {
        const E = EDIT; E.name = document.getElementById('rq_name').value; E.note = document.getElementById('rq_note').value;
        const ed = document.getElementById('rq_ed');
        const pos = {}; ed.querySelectorAll('[data-slot]').forEach(s => { pos[s.dataset.slot] = s.value; });
        const bed = {}; ed.querySelectorAll('[data-bed]').forEach(s => { bed[s.dataset.bed] = s.value === '1'; });
        E.rooms.forEach(r => { r.guests = []; });
        (IT.party_slots || []).forEach(s => { const v = pos[s.slot]; if (v !== '' && v != null && E.rooms[Number(v)]) E.rooms[Number(v)].guests.push({ slot: s.slot, bed: s.kind === 'adult' ? true : (bed[s.slot] !== false) }); });
    }
    function bindEditor() {
        const ed = document.getElementById('rq_ed');
        ed.querySelectorAll('[data-rt]').forEach(s => s.addEventListener('change', () => { readEditor(); EDIT.rooms[Number(s.dataset.rt)].type = s.value; draw(); }));
        ed.querySelectorAll('[data-rmroom]').forEach(b => b.addEventListener('click', () => { readEditor(); EDIT.rooms.splice(Number(b.dataset.rmroom), 1); draw(); }));
        ed.querySelectorAll('[data-addroom]').forEach(b => b.addEventListener('click', () => { readEditor(); EDIT.rooms.push({ type: 'twin', guests: [] }); draw(); }));
        ed.querySelector('[data-cancel]').addEventListener('click', () => { EDIT = null; draw(); });
        ed.querySelector('[data-save]').addEventListener('click', async () => {
            readEditor();
            const j = await post('/intake/' + ID + '/roomopt' + (EDIT.id ? '/' + EDIT.id : ''), { name: EDIT.name, note: EDIT.note, rooms: EDIT.rooms });
            if (j.status !== 200) return say('error', j.message || 'Not saved.');
            IT = j.data; EDIT = null; COST = null; CHECK = null; draw();
        });
    }

    // ---------- tf-roomopt-r3: the numbered missing items, read again from the records each time ----------
    let CHECK = null;
    async function loadChecklist() { const j = await api('/intake/' + ID + '/checklist'); CHECK = j.status === 200 ? j.data : { items: [], total: 0, resolved: 0, open: 0, ready: false, error: j.message }; return CHECK; }
    function checklistHtml(C, compact) {
        if (C.error) return `<div class="rq-err">${esc(C.error)}</div>`;
        const pct = C.total ? Math.round(100 * C.resolved / C.total) : 100;
        const open = C.items.filter(i => !i.resolved), shut = C.items.filter(i => i.resolved);
        const item = i => `<div class="rq-it ${i.resolved ? 'ok' : (i.reopened ? 're' : '')}" id="ck_${esc(i.key)}"><div class="h"><span class="n">${i.no}.</span>
                <div style="flex:1 1 300px"><b style="font-size:19px">${esc(i.category_word)}</b><div style="font-size:17px;margin-top:2px">${esc(i.label)}</div></div>
                <span class="rq-pill ${i.resolved ? 'ok' : (i.reopened ? 're' : 'open')}">${i.resolved ? 'Resolved' : (i.reopened ? 'Open again' : 'Open')}</span></div>
            ${i.resolved ? `<p class="rq-note">Resolved ${esc(String(i.resolved_at || '').slice(0, 16))} — the check no longer finds it.${i.note ? ' Note: ' + esc(i.note) : ''}</p>` : `<dl>
                <dt>Affects</dt><dd>${i.affects.length ? 'Option ' + i.affects.join(', ') : 'The whole quotation'}</dd>
                <dt>What is needed</dt><dd>${esc(i.action)}</dd>
                <dt>Where</dt><dd><a class="rq-btn" style="display:inline-flex;align-items:center;text-decoration:none;min-height:44px" href="<?= base_url() ?>${esc(i.link)}" target="_blank" rel="noopener">Open ${esc(i.where)} ↗</a></dd>
                <dt>Complete when</dt><dd>The check above no longer finds the problem — the item then turns green by itself. A note does not close it.</dd>
                <dt>Staff note</dt><dd><textarea data-cknote="${esc(i.key)}" placeholder="e.g. Rate requested from the hotel on 26 Nov — waiting for reply">${esc(i.note || '')}</textarea>
                    <div class="rq-row" style="margin-top:8px"><button type="button" class="rq-btn q" data-cksave="${esc(i.key)}">Save note</button>${i.note_by ? `<span class="rq-note" style="margin:0">Last note by ${esc(i.note_by)}, ${esc(String(i.note_at || '').slice(0, 16))}</span>` : ''}</div></dd></dl>`}</div>`;
        return `<div class="rq-ck" id="rq_ck"><div class="rq-ckh"><h3>Missing items — ${C.resolved} of ${C.total} resolved</h3><div class="rq-bar" aria-hidden="true"><i style="width:${pct}%"></i></div>
                <button type="button" class="rq-btn" data-ckre>Recheck now</button></div>
            <p class="rq-note">${C.total === 0 ? 'Nothing is missing: every check passes.' : (C.ready ? 'Every item is resolved — the quotation can be created.' : `${C.open} item${C.open === 1 ? '' : 's'} still block${C.open === 1 ? 's' : ''} the quotation. Each one is checked again against the records every time this list is shown.`)}</p>
            ${compact ? open.slice(0, 50).map(i => `<div class="rq-it"><div class="h"><span class="n">${i.no}.</span><div style="flex:1">${esc(i.category_word)} — ${esc(i.label)}</div><span class="rq-pill open">Open</span></div></div>`).join('') : open.map(item).join('') + (shut.length ? `<details style="margin-top:14px"><summary style="cursor:pointer;font-weight:700;color:#2e6b1f">${shut.length} resolved</summary>${shut.map(item).join('')}</details>` : '')}</div>`;
    }
    function bindChecklist(root, redraw) {
        root.querySelectorAll('[data-ckre]').forEach(b => b.addEventListener('click', async () => { b.disabled = true; b.textContent = 'Checking…'; COST = null; await loadChecklist(); redraw(); }));
        root.querySelectorAll('[data-cksave]').forEach(b => b.addEventListener('click', async () => {
            const k = b.dataset.cksave, ta = root.querySelector(`[data-cknote="${CSS.escape(k)}"]`);
            const j = await post('/intake/' + ID + '/checklist_note', { key: k, note: ta ? ta.value : '' });
            if (j.status !== 200) return say('error', j.message); CHECK = j.data; say('success', j.message); redraw();
        }));
    }
    // ---------- step 2: quotation rates ----------
    async function drawRates() {
        const m = document.getElementById('rq_main');
        if (!opts().some(o => o.included)) { m.innerHTML = '<h2>Quotation rates</h2><p class="rq-empty">Include at least one room option first.</p>'; return; }
        m.innerHTML = '<h2>Quotation rates</h2><p class="rq-empty">Working out each option from the hotel contracts…</p>';
        if (!COST) { const j = await api('/intake/' + ID + '/options_costing'); if (j.status !== 200) { m.innerHTML = `<h2>Quotation rates</h2><div class="rq-err">${esc(j.message || 'The options could not be priced.')}</div>`; return; } COST = j; }
        const rows = COST.data || [], sees = !!COST.sees_cost;
        if (!CHECK) await loadChecklist();
        m.innerHTML = `<h2>Quotation rates</h2>${checklistHtml(CHECK, false)}
            <p class="sub" style="margin:-6px 0 18px">Each option is priced on its own for the whole tour: its rooms at each hotel's contract rate for the night, plus the tour's transport, guide, entrances and meals once. The options are never added together.</p>
            ${rows.map(r => `<div class="rq-card"><p class="rq-ol">Option ${r.no}</p><h3>${esc(r.name)}</h3>
                <div class="rq-scroll"><table class="rq-t"><thead><tr><th>Night</th><th>Hotel</th><th>Room category · meal plan</th><th>Rooms</th><th>Rate</th></tr></thead><tbody>
                ${(r.nights || []).map(n => `<tr><td>${n.day_no}<br><span style="color:#4a4652;font-size:15px">${n.date ? esc(fmtD(n.date).replace(/ \d{4}$/, '')) : ''}</span></td><td>${esc(n.hotel || 'Hotel not chosen')}</td><td>${esc(n.category || '—')}${n.meal ? ' · ' + esc(n.meal) : ''}</td>
                    <td>${(n.rooms || []).map(esc).join('<br>') || '—'}</td><td>${n.ok ? '<span style="color:#2e6b1f;font-weight:700">Rate found</span>' : `<span style="color:#a3261d;font-weight:700">Missing</span><br><span style="font-size:15px;color:#4a4652">${esc(n.why || '')}</span>`}</td></tr>`).join('')}
                </tbody></table></div>
                ${r.ready && r.guests ? `<div class="rq-price">${r.guests.map(g => `<div><small>${esc(g.label)}${g.label === 'Adult' ? ' · per person' : ''}${g.count > 1 ? ' × ' + g.count : ''}</small><b>${money(g.sell_usd)}</b>${sees && g.cost_usd != null ? `<small>cost ${money(g.cost_usd)}</small>` : ''}</div>`).join('')}
                    <div class="fam"><small>Whole family · ${Number(IT.pax_total || 0)} guests</small><b>${money(r.total_usd)}</b></div></div>
                    ${sees ? `<p class="rq-note">Internal (Pricing group only): total cost ${money(r.cost_usd)} · markup ${r.markup_pct == null ? '—' : r.markup_pct + '%'}</p>` : ''}`
                : `<div class="rq-err"><b>Not ready to quote — ${r.blockers.length} thing${r.blockers.length === 1 ? '' : 's'} to fix:</b><ul style="margin:8px 0 0">${r.blockers.slice(0, 15).map(b => `<li>${esc(b.label)}</li>`).join('')}${r.blockers.length > 15 ? `<li>… and ${r.blockers.length - 15} more</li>` : ''}</ul></div>`}
            </div>`).join('')}
            <div class="rq-next"><button type="button" class="rq-btn g" data-go3>Continue to PDF preview →</button></div>
            <p class="rq-status">${status()}</p>`;
        m.querySelector('[data-go3]').addEventListener('click', () => setStep(3));
        bindChecklist(m, () => drawRates());
    }

    // ---------- step 3: one client quotation PDF ----------
    async function drawPdf() {
        const m = document.getElementById('rq_main');
        if (!COST) { m.innerHTML = '<h2>PDF preview</h2><p class="rq-empty">Checking every option…</p>'; const j = await api('/intake/' + ID + '/options_costing'); COST = j.status === 200 ? j : { data: [] }; }
        await loadChecklist();
        const inc = COST.data || [], ready = inc.length > 0 && inc.every(r => r.ready) && CHECK.ready;
        const qs = (IT.quotes || []).slice().sort((a, b) => Number(b.version) - Number(a.version));
        const q = QUOTE || qs.find(x => x.status === 'draft') || qs[0] || null;
        const view = q ? `${BASE}/intake/${ID}/quote/${q.id}?view=client` : null;
        m.innerHTML = `<h2>PDF preview</h2>
            <p class="sub" style="margin:-6px 0 18px">One quotation for the agent: the itinerary once, then every included option with its rooms and prices. Choosing the agent's option comes later, after the agent replies.</p>
            ${ready ? `<div class="rq-okb">All ${inc.length} included option${inc.length === 1 ? ' is' : 's are'} priced. Create the quotation to see the PDF.</div>`
                    : `<div class="rq-err">The quotation can be made when every missing item is resolved (${CHECK.resolved} of ${CHECK.total} so far). The list is under 2. Quotation rates.</div>${checklistHtml(CHECK, true)}`}
            <div class="rq-row"><button type="button" class="rq-btn p" data-make ${ready && !IT.locked ? '' : 'disabled'}>Create quotation (one PDF)</button>
                ${view ? `<a class="rq-btn" style="display:inline-flex;align-items:center;text-decoration:none" target="_blank" href="${view}">Open full preview</a><a class="rq-btn" style="display:inline-flex;align-items:center;text-decoration:none" target="_blank" href="${view}&pdf=1">Download PDF</a>` : ''}</div>
            ${view ? `<p class="rq-note">Showing quotation v${esc(q.version)} (${esc(q.status)}).</p><iframe class="rq-frame" src="${view}" title="Client quotation preview"></iframe>` : '<p class="rq-empty">No quotation version yet.</p>'}
            <p class="rq-status">${status()}</p>`;
        bindChecklist(m, () => drawPdf());
        const mk = m.querySelector('[data-make]');
        if (mk) mk.addEventListener('click', async () => {
            mk.disabled = true; mk.textContent = 'Creating…';
            const j = await post('/intake/' + ID + '/go', {});
            if (!j.ok) { say('warning', (j.message || 'Not created.') + (j.blockers && j.blockers.length ? '\n\n' + j.blockers.slice(0, 6).map(b => '• ' + b.label).join('\n') : '')); mk.disabled = false; mk.textContent = 'Create quotation (one PDF)'; return; }
            QUOTE = j.quote; IT = j.data || IT; drawPdf();
        });
    }
    load();
})();
</script>
</body>
</html>
