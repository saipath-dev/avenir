<?= view('layouts/head') ?>
<style>
    /* ---------- Free Visits (same tokens as the Tour Inquiry panel) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:860px; }
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
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .rowdel { color:#b6bfca; padding:0 6px; line-height:1.1; font-size:15px; }
    .tf .rowdel:hover { color:var(--tf-red); background:var(--tf-red-soft); }
    .tf .nocost { display:flex; gap:8px; align-items:flex-start; background:var(--tf-green-soft); color:var(--tf-green); border-radius:8px; padding:8px 10px; font-size:12px; }
    .tf .filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .tf .filters .form-control, .tf .filters .form-select { max-width:240px; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>

                <div class="tf-title">
                    <div>
                        <h4>Free Visits</h4>
                        <div class="sub">Each destination's normal sightseeing and en-route stops — a walk round the Galle Fort ramparts, a photo stop at the Ella Gap, a spice garden on the way to Kandy. <b>A free visit has no cost</b>: put on a day of an inquiry, it is in the itinerary the client reads and never in the costing. Anything with a rate — an entrance, a ticket, a safari, a village tour — belongs under <?= !empty($sees_paid) ? '<a href="' . base_url($paid_url ?? 'master_files/paid_services') . '">Paid Activities &amp; Services</a>' : 'Paid Activities &amp; Services' ?>.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Inquiry tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6 id="f_title">Add a free visit</h6><button class="btn btn-light btn-sm" id="f_new" hidden>New</button></div>
                            <div class="tf-card-b">
                                <div class="row g-2">
                                    <div class="col-12"><label class="form-label help">Destination</label>
                                        <input class="form-control form-control-sm" id="f_dest" list="dl_dest" placeholder="Galle, Kandy, Ella…" maxlength="120" autocomplete="off"></div>
                                    <div class="col-12"><label class="form-label help">What it is</label>
                                        <input class="form-control form-control-sm" id="f_name" placeholder="Walk round the Galle Fort ramparts" maxlength="190"></div>
                                    <div class="col-12"><label class="form-label help">Sort of visit</label>
                                        <select class="form-select form-select-sm" id="f_kind"></select></div>
                                    <div class="col-12"><label class="form-label help">Description on the client quotation <span class="text-muted">(optional)</span></label>
                                        <textarea class="form-control form-control-sm" id="f_desc" rows="3" maxlength="1000" placeholder="What the client reads under the day"></textarea></div>
                                    <div class="col-12"><div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="f_active" checked>
                                        <label class="form-check-label" for="f_active" style="font-size:12.5px">Active — offered on the day programme</label></div></div>
                                </div>
                                <div class="help mt-2" id="f_origin"></div>
                                <div class="nocost mt-3"><i class="bi bi-info-circle"></i><span>No cost, no rate: a free visit is never costed, so there is nothing to price here.</span></div>
                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-primary btn-sm" id="f_save"><i class="bi bi-check2"></i> Save</button>
                                </div>
                                <datalist id="dl_dest"></datalist>
                                <input type="hidden" id="f_id">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>By destination</h6>
                                <div class="filters">
                                    <input class="form-control form-control-sm" id="l_q" placeholder="Find…">
                                    <select class="form-select form-select-sm" id="l_dest"><option value="">All destinations</option></select>
                                    <label class="help" style="display:flex;gap:5px;align-items:center"><input type="checkbox" id="l_all"> show switched off</label>
                                    <span class="help" id="l_count"></span>
                                </div></div>
                            <div class="tf-card-b" style="padding:0;overflow-x:auto">
                                <table class="tbl"><thead><tr><th>Free visit</th><th>Sort</th><th>On days</th><th></th></tr></thead>
                                <tbody id="l_rows"><tr><td colspan="4" class="empty">Loading…</td></tr></tbody></table>
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
    const BASE = '<?= base_url($page_url ?? 'master_files/free_visits') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2200 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const $ = id => document.getElementById(id);
    let ROWS = [], KINDS = {}, DESTS = [];

    const NONE = '__none';   // the list filter for the ones nobody has placed yet
    function clearForm() {
        ['f_id', 'f_name', 'f_desc'].forEach(k => $(k).value = '');
        $('f_kind').value = 'sightseeing'; $('f_active').checked = true;
        $('f_title').textContent = 'Add a free visit'; $('f_new').hidden = true;
        if ($('l_dest').value && $('l_dest').value !== NONE) $('f_dest').value = $('l_dest').value;
        $('f_origin').textContent = '';
    }
    function fillForm(id) {
        const r = ROWS.find(x => Number(x.id) === Number(id)); if (!r) return;
        $('f_id').value = r.id; $('f_dest').value = r.destination || ''; $('f_name').value = r.name; $('f_kind').value = r.kind;
        $('f_desc').value = r.description || ''; $('f_active').checked = Number(r.active) === 1;
        $('f_title').textContent = 'Change ' + r.name; $('f_new').hidden = false;
        $('f_origin').textContent = r.origin ? r.origin + '.' : '';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function render() {
        const q = $('l_q').value.trim().toLowerCase();
        const dest = $('l_dest').value;
        const rows = ROWS.filter(r => (!dest || (dest === NONE ? !r.destination : String(r.destination).toLowerCase() === dest.toLowerCase()))
            && (!q || (r.name + ' ' + (r.destination || '') + ' ' + (r.description || '') + ' ' + (r.origin || '')).toLowerCase().includes(q)));
        const places = [...new Set(rows.map(r => r.destination || ''))];
        $('l_count').textContent = ROWS.length ? rows.length + ' of ' + ROWS.length + ' · ' + places.filter(Boolean).length + ' destination' + (places.filter(Boolean).length === 1 ? '' : 's') : '';
        let html = '';
        places.forEach(p => {
            const mine = rows.filter(r => (r.destination || '') === p);
            html += `<tr class="dest"><td colspan="4">${p ? esc(p) : 'Not placed yet <span class="help" style="font-weight:400">— give each a destination with Edit; until then it is offered only when searched for</span>'} <span class="help" style="font-weight:400">${mine.length} free visit${mine.length === 1 ? '' : 's'}</span></td></tr>`;
            html += mine.map(r => `<tr class="${Number(r.active) === 1 ? '' : 'off'}" data-row="${r.id}">
                <td><b>${esc(r.name)}</b>${Number(r.active) === 1 ? '' : ' <span class="pill pill-grey">off</span>'}${r.source === 'backfill' ? ' <span class="pill pill-blue" title="Copied once from the no-charge stops on inquiries typed before this master existed">from past inquiries</span>' : ''}
                    ${r.origin ? `<div class="help">${esc(r.origin)}</div>` : ''}
                    ${r.description ? `<div class="help">${esc(r.description.length > 180 ? r.description.slice(0, 180) + '…' : r.description)}</div>` : ''}</td>
                <td><span class="pill pill-green">${esc(r.kind_word || r.kind)}</span></td>
                <td>${r.used ? `<span class="pill pill-blue">${r.used} day line(s)</span>` : '<span class="help">—</span>'}</td>
                <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-edit="${r.id}">Edit</button>
                    ${Number(r.active) === 1 ? `<button class="btn btn-light btn-sm rowdel" data-del="${r.id}" title="Switch off (kept, never deleted)">&times;</button>` : `<button class="btn btn-light btn-sm" data-on="${r.id}">Switch on</button>`}</td></tr>`).join('');
        });
        $('l_rows').innerHTML = html || `<tr><td colspan="4" class="empty">${ROWS.length ? 'Nothing matches.' : 'No free visits yet. Add the first one on the left.'}</td></tr>`;
        document.querySelectorAll('#l_rows [data-edit]').forEach(b => b.addEventListener('click', () => fillForm(b.dataset.edit)));
        document.querySelectorAll('#l_rows [data-del]').forEach(b => b.addEventListener('click', async () => {
            const r = ROWS.find(x => Number(x.id) === Number(b.dataset.del));
            const ok = await Swal.fire({ title: 'Are you sure you want to delete this record?', text: r.name + ' is switched off and kept, never erased' + (r.used ? ': the ' + r.used + ' day line(s) that carry it keep it as written' : '') + '. It can be switched on again under "show switched off".', icon: 'warning', showCancelButton: true, confirmButtonText: 'Switch off' });
            if (!ok.isConfirmed) return;
            const j = await post('/delete/' + r.id, {});
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
        document.querySelectorAll('#l_rows [data-on]').forEach(b => b.addEventListener('click', async () => {
            const r = ROWS.find(x => Number(x.id) === Number(b.dataset.on));
            if (!r.destination) { fillForm(r.id); $('f_active').checked = true; toast('info', 'Give it a destination, then Save.'); return; }
            const j = await post('/save', { id: r.id, destination: r.destination, name: r.name, kind: r.kind, description: r.description || '', active: 1 });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', 'Switched on again.'); load();
        }));
    }
    async function load() {
        const j = await api('/data' + ($('l_all').checked ? '?all=1' : ''));
        const d = j.data || {};
        ROWS = d.visits || []; KINDS = d.kinds || {}; DESTS = d.destinations || [];
        if (!$('f_kind').options.length) $('f_kind').innerHTML = Object.entries(KINDS).map(([k, v]) => `<option value="${esc(k)}">${esc(v)}</option>`).join('');
        $('dl_dest').innerHTML = DESTS.map(x => `<option value="${esc(x)}">`).join('');
        const keep = $('l_dest').value;
        const withVisits = [...new Set(ROWS.map(r => r.destination).filter(Boolean))].sort((a, b) => a.localeCompare(b));
        const unplaced = ROWS.some(r => !r.destination);
        $('l_dest').innerHTML = '<option value="">All destinations</option>' + withVisits.map(x => `<option value="${esc(x)}">${esc(x)}</option>`).join('') + (unplaced ? `<option value="${NONE}">Not placed yet</option>` : '');
        $('l_dest').value = withVisits.includes(keep) || (keep === NONE && unplaced) ? keep : '';
        render();
    }
    $('f_save').addEventListener('click', async () => {
        const btn = $('f_save');
        if (btn.disabled) return;
        btn.disabled = true;
        let j;
        try {
            j = await post('/save', { id: $('f_id').value || null, destination: $('f_dest').value, name: $('f_name').value, kind: $('f_kind').value, description: $('f_desc').value, active: $('f_active').checked ? 1 : 0 });
        } finally { btn.disabled = false; }
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); clearForm(); load();
    });
    $('f_new').addEventListener('click', clearForm);
    $('l_q').addEventListener('input', render);
    $('l_dest').addEventListener('change', render);
    $('l_all').addEventListener('change', load);
    load();
})();
</script>
</body>
</html>
