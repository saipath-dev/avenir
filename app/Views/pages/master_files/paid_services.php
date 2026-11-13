<?= view('layouts/head') ?>
<style>
    /* ---------- Paid Activities & Services (same tokens as the Tour Inquiry panel) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:900px; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .btn-primary { background:var(--tf-navy); border-color:var(--tf-navy); }
    .tf .btn-primary:hover { background:var(--tf-navy-2); border-color:var(--tf-navy-2); }
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .btn-light:hover { background:var(--tf-bg); }
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl td { padding:8px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr.off td { opacity:.5; }
    .tf .tbl tbody tr:hover { background:var(--tf-bg); }
    .tf .tbl tr.dest td { background:#f7f9fc; font-weight:600; color:var(--tf-navy); font-size:12.5px; padding:7px 10px; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .rate { font-weight:600; white-space:nowrap; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .rowdel { color:#b6bfca; padding:0 6px; line-height:1.1; font-size:15px; }
    .tf .rowdel:hover { color:var(--tf-red); background:var(--tf-red-soft); }
    .tf .filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .tf .filters .form-control, .tf .filters .form-select { max-width:220px; }
    .tf.tf-skin .filters .form-select { max-width:220px; }
    .tf .note { font-size:12px; background:var(--tf-blue-soft); color:var(--tf-navy); border-radius:8px; padding:8px 10px; }
    .tf .bulk { display:none; gap:8px; align-items:center; flex-wrap:wrap; padding:8px 14px; border-bottom:1px solid var(--tf-line); background:#fffdf6; }
    .tf .bulk.on { display:flex; }
</style>
<?= view('layouts/tf_master_skin') ?>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf tf-skin" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>

                <div class="tf-title">
                    <div>
                        <h4>Paid Activities &amp; Services</h4>
                        <div class="sub">Everything on a day that costs money, by destination — Sigiriya: the rock entrance, the village tour, the jeep safari; Kandy: the Temple of the Tooth, the Kandyan dance; Polonnaruwa and Anuradhapura: the ancient-city entrances — each with its rate structure. Put on a day of an inquiry, a paid item pulls its rate and goes straight to the costing. Sightseeing with nothing to pay belongs under <?= !empty($sees_free) ? '<a href="' . base_url($free_url ?? 'master_files/free_visits') . '">Free Visits</a>' : 'Free Visits' ?>.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Inquiry tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div class="row g-3">
                    <div class="col-xl-4">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6 id="f_title">Add an activity or excursion</h6><button class="btn btn-light btn-sm" id="f_new" hidden>New</button></div>
                            <div class="tf-card-b">
                                <div class="row g-2">
                                    <div class="col-12"><label class="form-label help">Destination</label>
                                        <input class="form-control form-control-sm" id="f_loc" list="dl_dest" placeholder="Sigiriya, Kandy, Yala…" maxlength="120" autocomplete="off"></div>
                                    <div class="col-5"><label class="form-label help">Code</label><input class="form-control form-control-sm mono" id="f_code" placeholder="SIG-VILLAGE" maxlength="30"></div>
                                    <div class="col-7"><label class="form-label help">Name</label><input class="form-control form-control-sm" id="f_name" placeholder="Village tour with bullock cart" maxlength="160"></div>
                                    <div class="col-6"><label class="form-label help">Charged</label>
                                        <select class="form-select form-select-sm" id="f_unit"><option value="per_person">Per person</option><option value="per_group">Per group (one lot)</option><option value="per_vehicle">Per vehicle</option></select></div>
                                    <div class="col-3"><label class="form-label help">Rate</label><input type="number" min="0" step="0.01" class="form-control form-control-sm" id="f_rate"></div>
                                    <div class="col-3"><label class="form-label help">Currency</label>
                                        <select class="form-select form-select-sm" id="f_cur"><option>USD</option><option>LKR</option></select></div>
                                    <div class="col-6"><label class="form-label help">Valid from</label><input type="date" class="form-control form-control-sm" id="f_from"></div>
                                    <div class="col-6"><label class="form-label help">Valid to</label><input type="date" class="form-control form-control-sm" id="f_to"></div>
                                    <div class="col-12"><div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="f_inc">
                                        <label class="form-check-label" for="f_inc" style="font-size:12.5px"><b>Includes transport</b> — the rate brings its own jeep, boat or train, so the quotation waives that day's local running for the vehicle</label></div></div>
                                    <div class="col-12"><label class="form-label help">Note</label><input class="form-control form-control-sm" id="f_note" placeholder="What is included, timings, who to book with" maxlength="255"></div>
                                    <div class="col-12"><div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="f_active" checked>
                                        <label class="form-check-label" for="f_active" style="font-size:12.5px">Active — offered on the day programme</label></div></div>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-primary btn-sm" id="f_save"><i class="bi bi-check2"></i> Save</button>
                                </div>
                                <div class="help mt-2">The code is the key: it cannot be changed once used. A blank valid-from or valid-to means no limit. A quotation copies the rate the moment the activity is put on a day, so a change here never moves a figure already quoted.</div>
                                <datalist id="dl_dest"></datalist>
                            </div>
                        </div>
                        <div class="tf-card mt-3">
                            <div class="tf-card-h"><h6>Entrances &amp; tickets</h6></div>
                            <div class="tf-card-b">
                                <?php if (!empty($master_on)): ?>
                                <div class="note">Entrances and safaris are kept in the <b>Place to Visit / Entrance Fee Master</b> — the one authority on whether they are charged and at what (the entrance a head, adult and child; at a safari park the jeep per vehicle; for a period). They are shown here read only: their rates, destination and use are set there, never here. An entrance or safari not in the master is held. The old list's meals, services and activities (lunch, guide, shows …) are kept here as before.</div>
                                <?php if (!empty($sees_master)): ?>
                                <a class="btn btn-light btn-sm mt-2" href="<?= base_url($master_url ?? 'master_files/entrance_master') ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Open the Place to Visit / Entrance Fee Master</a>
                                <?php endif; ?>
                                <?php else: ?>
                                <div class="note">Entrances, tickets and whatever is priced a head are the company's priced list, kept under <b>Master Files → Entrance Fee</b> — that screen also opens each entry's accounts posting, so an entry is added or its name, rate and who settles it changed there, never here. They show on this screen by destination as soon as they are saved.</div>
                                <?php endif; ?>
                                <?php if (!empty($sees_fee)): ?>
                                <a class="btn btn-light btn-sm mt-2" id="b_fee" href="<?= base_url($fee_url ?? 'master_files/entrance_fee') ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Open Entrance Fee</a>
                                <?php endif; ?>
                                <div class="help mt-2">Here you put each entry under its destination — nobody's guess: an entry sits under a destination only once a person places it, its address shown beside it for reference. Until then it is under <b>Not placed yet</b> and is offered on the day programme only when searched for. Find the ones at one place (the Find box reads the address too), tick them — or <b>Tick all shown</b> — and put them under the destination together. An entry can also be switched off, so it is no longer offered or matched; the lines already priced from it keep it.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-8">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>By destination</h6>
                                <div class="filters">
                                    <input class="form-control form-control-sm" id="l_q" placeholder="Search an activity or service: name, code, destination…">
                                    <select class="form-select form-select-sm" id="l_dest"><option value="">All destinations</option></select>
                                    <select class="form-select form-select-sm" id="l_type"><option value="">Entrances and activities</option><option value="e">Entrances &amp; tickets</option><option value="a">Activities &amp; excursions</option></select>
                                    <label class="help" style="display:flex;gap:5px;align-items:center"><input type="checkbox" id="l_all"> show switched off</label>
                                    <span class="help" id="l_count"></span>
                                </div></div>
                            <div class="help" style="padding:6px 14px;border-bottom:1px solid var(--tf-line)"><label style="display:inline-flex;gap:6px;align-items:center"><input type="checkbox" id="tick_all"> Tick all the entrances shown</label></div>
                            <div class="bulk" id="bulk"><span class="help" id="bulk_n"></span>
                                <input class="form-control form-control-sm" id="bulk_dest" list="dl_dest" placeholder="Destination" style="max-width:200px">
                                <button class="btn btn-light btn-sm" id="bulk_go">Put the ticked entrances under it</button>
                                <button class="btn btn-light btn-sm" id="bulk_clear">Clear</button></div>
                            <div class="tf-card-b" style="padding:0;overflow-x:auto">
                                <table class="tbl"><thead><tr><th style="width:26px"></th><th>Item</th><th>Rate</th><th>Settled</th><th>Used</th><th></th></tr></thead>
                                <tbody id="l_rows"><tr><td colspan="6" class="empty">Loading…</td></tr></tbody></table>
                            </div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'master_files/paid_services') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2200 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const $ = id => document.getElementById(id);
    const UNIT = { per_person: 'per person', per_group: 'per group', per_vehicle: 'per vehicle' };
    const SETTLED = { in_tour: 'in the tour', prepaid: 'prepaid', direct: 'paid direct' };
    let ENTS = [], ACTS = [], DESTS = [], SHOWN = [];
    const MASTER_ON = <?= !empty($master_on) ? 'true' : 'false' ?>;
    // schema 39: an entrance or a safari is kept in the Place to Visit / Entrance Fee Master alone — read only here
    const SEES_MASTER = <?= !empty($sees_master) ? 'true' : 'false' ?>;
    const MASTER_URL = '<?= base_url($master_url ?? 'master_files/entrance_master') ?>';
    const editable = r => !r.master;
    const oldEnt = id => ENTS.find(x => !x.master && Number(x.id) === Number(id));
    const ticked = new Set();

    function clearForm() {
        ['f_code', 'f_name', 'f_rate', 'f_from', 'f_to', 'f_note'].forEach(k => $(k).value = '');
        $('f_unit').value = 'per_person'; $('f_cur').value = 'USD'; $('f_inc').checked = false; $('f_active').checked = true;
        $('f_code').readOnly = false; $('f_title').textContent = 'Add an activity or excursion'; $('f_new').hidden = true;
        if ($('l_dest').value && $('l_dest').value !== '__none') $('f_loc').value = $('l_dest').value;
    }
    function fillForm(code) {
        const r = ACTS.find(x => x.code === code); if (!r) return;
        $('f_code').value = r.code; $('f_code').readOnly = true; $('f_loc').value = r.location || '';
        $('f_name').value = r.name; $('f_unit').value = r.unit; $('f_rate').value = r.rate; $('f_cur').value = r.currency;
        $('f_from').value = r.valid_from || ''; $('f_to').value = r.valid_to || ''; $('f_note').value = r.note || '';
        $('f_inc').checked = Number(r.includes_transport) === 1; $('f_active').checked = Number(r.active) === 1;
        $('f_title').textContent = 'Change ' + r.name; $('f_new').hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function items() {
        const e = ENTS.map(x => ({ t: 'e', key: x.master ? 'p' + x.place_id : 'e' + x.id, name: x.name, dest: x.destination || '', active: Number(x.active) === 1, row: x }));
        const a = ACTS.map(x => ({ t: 'a', key: 'a' + x.code, name: x.name, dest: x.location || '', active: Number(x.active) === 1, row: x }));
        return e.concat(a);
    }
    function rateCell(it) {
        const r = it.row;
        if (it.t === 'a') return `<span class="rate mono">${esc(r.currency)} ${Number(r.rate).toLocaleString(undefined, { minimumFractionDigits: 2 })}</span> <span class="help">${esc(UNIT[r.unit] || r.unit)}</span>`
            + (Number(r.includes_transport) === 1 ? ' <span class="pill pill-green">transport included</span>' : '')
            + (r.valid_from || r.valid_to ? `<div class="help">${esc((r.valid_from || '…') + ' → ' + (r.valid_to || '…'))}</div>` : '');
        if (r.master) {
            // schema 39: a place of the Place to Visit / Entrance Fee Master — its word for today
            if (r.rate_state === 'priced') return `<span class="rate mono">${esc(r.rate_text)}</span>`;
            if (r.rate_state === 'free') return '<span class="pill pill-green">free</span>';
            if (r.rate_state === 'off') return '<span class="pill pill-grey">not in use</span>';
            return `<span class="pill pill-red" title="${esc(r.rate_text || '')}">no rate — held</span><div class="help">${esc(r.rate_text || '')}</div>`;
        }
        if (r.rate_state === 'priced') return `<span class="rate mono">USD ${Number(r.usd).toFixed(2)}</span> <span class="help">a head</span>`;
        if (r.rate_state === 'rupees') return `<span class="pill pill-red" title="The list holds ${esc(r.usd)} — rupees in the USD column? Correct it under Entrance Fee.">check the figure: ${esc(r.usd)}</span>`;
        return '<span class="pill pill-amber" title="The list holds no amount: a line priced from it stops the quotation until the amount is set under Entrance Fee, or the line is marked No charge">no amount yet</span>';
    }
    function render() {
        const q = $('l_q').value.trim().toLowerCase();
        const dest = $('l_dest').value;
        const type = $('l_type').value;
        const rows = items().filter(it => (!type || it.t === type)
            && (!dest || (dest === '__none' ? it.dest === '' : it.dest.toLowerCase() === dest.toLowerCase()))
            && (!q || (it.name + ' ' + it.dest + ' ' + (it.row.code || '') + ' ' + (it.row.location || '')).toLowerCase().includes(q)));
        rows.sort((a, b) => (a.dest === '' ? 1 : 0) - (b.dest === '' ? 1 : 0) || a.dest.localeCompare(b.dest) || a.t.localeCompare(b.t)
            || (a.dest === '' ? String(a.row.location || '').localeCompare(String(b.row.location || '')) : 0) || a.name.localeCompare(b.name));
        SHOWN = rows.filter(r => r.t === 'e' && editable(r.row)).map(r => Number(r.row.id));
        const places = [...new Set(rows.map(r => r.dest))];
        $('l_count').textContent = rows.length + ' item' + (rows.length === 1 ? '' : 's') + ' · ' + places.filter(Boolean).length + ' destination' + (places.filter(Boolean).length === 1 ? '' : 's');
        let html = '';
        places.forEach(p => {
            const mine = rows.filter(r => r.dest === p);
            html += `<tr class="dest"><td colspan="6">${p ? esc(p) : 'Not placed yet <span class="help" style="font-weight:400">— tick an entrance and put it under a destination; Edit an activity to give it one</span>'} <span class="help" style="font-weight:400">${mine.filter(r => r.t === 'e').length} entrance(s) · ${mine.filter(r => r.t === 'a').length} activit${mine.filter(r => r.t === 'a').length === 1 ? 'y' : 'ies'}</span></td></tr>`;
            html += mine.map(it => {
                const r = it.row;
                if (it.t === 'e') return `<tr class="${it.active ? '' : 'off'}" data-ent="${r.master ? 'p' + r.place_id : r.id}">
                    <td>${editable(r) ? `<input type="checkbox" data-tick="${r.id}" ${ticked.has(Number(r.id)) ? 'checked' : ''}>` : ''}</td>
                    <td><b>${esc(r.name)}</b> <span class="pill ${r.master ? 'pill-blue' : 'pill-grey'}">${r.master ? (r.jeep ? 'safari park' : 'entrance') : (MASTER_ON ? 'meal / service / activity' : 'entrance / ticket')}</span>${it.active ? '' : ' <span class="pill pill-grey">off</span>'}
                        <div class="help">${r.code ? '<span class="mono">' + esc(r.code) + '</span> · ' : ''}<span title="The entry's address in Entrance Fee — shown for reference, never read for a destination">${esc(r.location || 'no address')}</span></div></td>
                    <td>${rateCell(it)}</td>
                    <td><span class="help">${esc(SETTLED[r.pay_mode] || r.pay_mode)}</span></td>
                    <td>${r.used ? `<span class="pill pill-blue">${r.used} line(s)</span>` : '<span class="help">—</span>'}</td>
                    <td style="white-space:nowrap">${editable(r) ? `<button class="btn btn-light btn-sm" data-place="${r.id}" title="The destination it sits under here">Destination</button>
                        <button class="btn btn-light btn-sm" data-onoff="${r.id}" title="${it.active ? 'No longer offered on the day programme or matched; the entry stays in Entrance Fee' : 'Offered and matched again'}">${it.active ? 'Switch off' : 'Switch on'}</button>` : (SEES_MASTER ? `<a class="btn btn-light btn-sm" href="${MASTER_URL}" target="_blank" title="Rates, destination and use are kept in the master">Open in the master</a>` : '<span class="help">kept in the Place to Visit / Entrance Fee Master</span>')}</td></tr>`;
                return `<tr class="${it.active ? '' : 'off'}" data-act="${esc(r.code)}">
                    <td></td>
                    <td><b>${esc(r.name)}</b> <span class="pill pill-blue">activity</span>${it.active ? '' : ' <span class="pill pill-grey">off</span>'}
                        <div class="help"><span class="mono">${esc(r.code)}</span>${r.note ? ' · ' + esc(r.note) : ''}</div></td>
                    <td>${rateCell(it)}</td>
                    <td><span class="help">in the tour</span></td>
                    <td>${r.used ? `<span class="pill pill-blue">${r.used} line(s)</span>` : '<span class="help">—</span>'}</td>
                    <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-edit="${esc(r.code)}">Edit</button>
                        ${it.active ? `<button class="btn btn-light btn-sm rowdel" data-del="${esc(r.code)}" title="Switch off (kept, never deleted)">&times;</button>` : `<button class="btn btn-light btn-sm" data-acton="${esc(r.code)}">Switch on</button>`}</td></tr>`;
            }).join('');
        });
        $('l_rows').innerHTML = html || `<tr><td colspan="6" class="empty">${ENTS.length + ACTS.length ? 'Nothing matches.' : 'Nothing paid yet. Add an activity on the left; entrances come from Master Files → Entrance Fee.'}</td></tr>`;
        $('bulk').classList.toggle('on', ticked.size > 0);
        $('bulk_n').textContent = ticked.size + ' entrance' + (ticked.size === 1 ? '' : 's') + ' ticked';

        document.querySelectorAll('#l_rows [data-tick]').forEach(b => b.addEventListener('change', () => { const id = Number(b.dataset.tick); if (b.checked) ticked.add(id); else ticked.delete(id); $('bulk').classList.toggle('on', ticked.size > 0); $('bulk_n').textContent = ticked.size + ' entrance' + (ticked.size === 1 ? '' : 's') + ' ticked'; }));
        document.querySelectorAll('#l_rows [data-edit]').forEach(b => b.addEventListener('click', () => fillForm(b.dataset.edit)));
        document.querySelectorAll('#l_rows [data-place]').forEach(b => b.addEventListener('click', async () => {
            const r = oldEnt(b.dataset.place);
            const res = await Swal.fire({ title: 'Destination for ' + r.name, input: 'text', inputValue: r.destination || '', inputAttributes: { list: 'dl_dest', maxlength: 120 },
                text: 'Where it is offered on the day programme' + (r.location ? ' (its address: ' + r.location + ')' : '') + '. Leave it empty to take it back to Not placed yet.', showCancelButton: true, confirmButtonText: 'Save' });
            if (!res.isConfirmed) return;
            const j = await post('/entrance/' + r.id, { destination: res.value || '' });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
        document.querySelectorAll('#l_rows [data-onoff]').forEach(b => b.addEventListener('click', async () => {
            const r = oldEnt(b.dataset.onoff);
            const on = Number(r.active) === 1;
            if (on) {
                const ok = await Swal.fire({ title: 'Switch off ' + r.name + '?', text: 'It will no longer be offered on the day programme or matched to a typed line. ' + (r.used ? 'The ' + r.used + ' line(s) already priced from it keep it and its figure. ' : '') + 'The entry itself stays in Entrance Fee.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Switch off' });
                if (!ok.isConfirmed) return;
            }
            const j = await post('/entrance/' + r.id, { active: on ? 0 : 1 });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
        document.querySelectorAll('#l_rows [data-acton]').forEach(b => b.addEventListener('click', async () => {
            const r = ACTS.find(x => x.code === b.dataset.acton);
            if (!r.location) { fillForm(r.code); $('f_active').checked = true; toast('info', 'Give it a destination, then Save.'); return; }
            const j = await post('/activity', { code: r.code, name: r.name, location: r.location, unit: r.unit, rate: r.rate, currency: r.currency, valid_from: r.valid_from || '', valid_to: r.valid_to || '', note: r.note || '', includes_transport: Number(r.includes_transport) === 1 ? 1 : 0, active: 1 });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', 'Switched on again.'); load();
        }));
        $('tick_all').checked = SHOWN.length > 0 && SHOWN.every(id => ticked.has(id));
        document.querySelectorAll('#l_rows [data-del]').forEach(b => b.addEventListener('click', async () => {
            const r = ACTS.find(x => x.code === b.dataset.del);
            const ok = await Swal.fire({ title: 'Are you sure you want to delete this record?', text: r.name + ' is switched off and kept, never erased' + (r.used ? ': the ' + r.used + ' quotation line(s) that carry it keep their copied figures' : '') + '. It can be switched on again under "show switched off".', icon: 'warning', showCancelButton: true, confirmButtonText: 'Switch off' });
            if (!ok.isConfirmed) return;
            const j = await post('/activity/delete/' + encodeURIComponent(r.code), {});
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
    }
    async function load() {
        const j = await api('/data');
        const d = j.data || {};
        const all = $('l_all').checked;
        ENTS = (d.entrances || []).filter(x => all || Number(x.active) === 1);
        ACTS = (d.activities || []).filter(x => all || Number(x.active) === 1);
        DESTS = d.destinations || [];
        $('dl_dest').innerHTML = DESTS.map(x => `<option value="${esc(x)}">`).join('');
        const keep = $('l_dest').value;
        const withItems = [...new Set(items().map(i => i.dest).filter(Boolean))].sort((a, b) => a.localeCompare(b));
        const unplaced = items().some(i => i.dest === '');
        $('l_dest').innerHTML = '<option value="">All destinations</option>' + withItems.map(x => `<option value="${esc(x)}">${esc(x)}</option>`).join('') + (unplaced ? '<option value="__none">Not placed yet</option>' : '');
        $('l_dest').value = withItems.includes(keep) || (keep === '__none' && unplaced) ? keep : '';
        render();
    }
    $('f_save').addEventListener('click', async () => {
        const btn = $('f_save');
        if (btn.disabled) return;
        btn.disabled = true;
        let j;
        try {
            j = await post('/activity', {
                code: $('f_code').value, name: $('f_name').value, location: $('f_loc').value, unit: $('f_unit').value, rate: $('f_rate').value, currency: $('f_cur').value,
                valid_from: $('f_from').value, valid_to: $('f_to').value, note: $('f_note').value,
                includes_transport: $('f_inc').checked ? 1 : 0, active: $('f_active').checked ? 1 : 0
            });
        } finally { btn.disabled = false; }
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); clearForm(); load();
    });
    $('bulk_go').addEventListener('click', async () => {
        const dest = $('bulk_dest').value.trim();
        if (!dest) { toast('error', 'Type the destination first.'); return; }
        let n = 0, bad = [];
        for (const id of ticked) {
            const j = await post('/entrance/' + id, { destination: dest });
            if (j.status === 200) n++; else bad.push(j.message);
        }
        ticked.clear(); $('bulk_dest').value = '';
        toast(bad.length ? 'error' : 'success', n + ' entrance(s) put under ' + dest + '.' + (bad.length ? ' ' + bad.join(' ') : ''));
        load();
    });
    $('bulk_clear').addEventListener('click', () => { ticked.clear(); render(); });
    $('tick_all').addEventListener('change', e => { SHOWN.forEach(id => e.target.checked ? ticked.add(id) : ticked.delete(id)); render(); });
    $('f_new').addEventListener('click', clearForm);
    $('l_q').addEventListener('input', render);
    (function () { var q = new URLSearchParams(location.search).get('q') || ''; if (q) { $('l_q').value = q; $('l_q').dispatchEvent(new Event('input', { bubbles: true })); } })();   // tf-stage3 (the input event lets TfView keep this value instead of the last one) — ?q= from the Master Files search
    $('l_dest').addEventListener('change', render);
    $('l_type').addEventListener('change', render);
    $('l_all').addEventListener('change', load);
    load();
})();
</script>
</body>
</html>
