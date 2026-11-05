<?= view('layouts/head') ?>
<style>
    /* ---------- Excursion Rates (same tokens as the Tour Inquiry panel) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#b7791f; --tf-amber-soft:#fdf3e1;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:820px; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .btn-primary { background:var(--tf-navy); border-color:var(--tf-navy); }
    .tf .btn-primary:hover { background:var(--tf-navy-2); border-color:var(--tf-navy-2); }
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .btn-light:hover { background:var(--tf-bg); }
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl td { padding:9px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr:last-child td { border-bottom:0; }
    .tf .tbl tr.off td { opacity:.5; }
    .tf .tbl tbody tr:hover { background:var(--tf-bg); }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .rate { font-size:15px; font-weight:600; }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .rowdel { color:#b6bfca; padding:0 6px; line-height:1.1; font-size:15px; }
    .tf .rowdel:hover { color:var(--tf-red); background:var(--tf-red-soft); }
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
                        <h4>Excursion Rates</h4>
                        <div class="sub">The excursions a quotation can carry — a jeep safari, a whale-watching boat, the hill-country train, a village tour — with their own rates, kept apart from entrance fees. Typed here and changed here, by a person. A quotation copies the rate the moment the excursion is put on it, so a change here never moves a figure already quoted. Tick <b>includes transport</b> when the rate brings its own vehicle: the quotation then waives that day's local running so the mileage is never charged twice.</div>
                    </div>
                </div>

                <div class="not-installed" id="retire_note" style="background:var(--tf-blue-soft);color:var(--tf-navy);margin-bottom:14px"><i class="bi bi-info-circle"></i> The activities and excursions can now also be kept by destination under <a href="<?= base_url('master_files/paid_services') ?>"><b>Master Files → Paid Activities &amp; Services</b></a> — the same list, with its rate structure. This screen stays in use, working on that same list, until UAT is signed off; nothing retires it automatically.</div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Inquiry tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6 id="f_title">Add an excursion</h6><button class="btn btn-light btn-sm" id="f_new" hidden>New</button></div>
                            <div class="tf-card-b">
                                <div class="row g-2">
                                    <div class="col-5"><label class="form-label help">Code</label><input class="form-control form-control-sm mono" id="f_code" placeholder="YALA-JEEP" maxlength="30"></div>
                                    <div class="col-7"><label class="form-label help">Name</label><input class="form-control form-control-sm" id="f_name" placeholder="Yala jeep safari (half day)" maxlength="160"></div>
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
                                        <label class="form-check-label" for="f_active" style="font-size:12.5px">Active — offered when a quotation is built</label></div></div>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-primary btn-sm" id="f_save"><i class="bi bi-check2"></i> Save</button>
                                </div>
                                <div class="help mt-2">The code is the key: it cannot be changed once used. A blank valid-from or valid-to means no limit. Nothing here is a quotation figure until somebody puts the excursion on a quotation.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>The list</h6><span class="help" id="l_count"></span></div>
                            <div class="tf-card-b" style="padding:0;overflow-x:auto">
                                <table class="tbl"><thead><tr><th>Code</th><th>Excursion</th><th>Charged</th><th>Rate</th><th>Transport</th><th>Valid</th><th>Used</th><th></th></tr></thead>
                                <tbody id="l_rows"><tr><td colspan="8" class="empty">Loading…</td></tr></tbody></table>
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
    const BASE = '<?= base_url($page_url ?? 'master_files/excursion_rates') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2200 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const UNIT = { per_person: 'per person', per_group: 'per group', per_vehicle: 'per vehicle' };
    const $ = id => document.getElementById(id);
    let ROWS = [];

    function clearForm() {
        ['f_code','f_name','f_rate','f_from','f_to','f_note'].forEach(k => $(k).value = '');
        $('f_unit').value = 'per_person'; $('f_cur').value = 'USD'; $('f_inc').checked = false; $('f_active').checked = true;
        $('f_code').readOnly = false; $('f_title').textContent = 'Add an excursion'; $('f_new').hidden = true;
    }
    function fillForm(code) {
        const r = ROWS.find(x => x.code === code); if (!r) return;
        $('f_code').value = r.code; $('f_code').readOnly = true;
        $('f_name').value = r.name; $('f_unit').value = r.unit; $('f_rate').value = r.rate; $('f_cur').value = r.currency;
        $('f_from').value = r.valid_from || ''; $('f_to').value = r.valid_to || ''; $('f_note').value = r.note || '';
        $('f_inc').checked = Number(r.includes_transport) === 1; $('f_active').checked = Number(r.active) === 1;
        $('f_title').textContent = 'Change ' + r.name; $('f_new').hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function render() {
        $('l_count').textContent = ROWS.length ? ROWS.length + ' excursion' + (ROWS.length === 1 ? '' : 's') + ' · ' + ROWS.filter(r => Number(r.active) === 1).length + ' active' : '';
        $('l_rows').innerHTML = ROWS.length ? ROWS.map(r => `<tr class="${Number(r.active) === 1 ? '' : 'off'}">
            <td class="mono">${esc(r.code)}</td>
            <td><b>${esc(r.name)}</b>${r.note ? `<div class="help">${esc(r.note)}</div>` : ''}${Number(r.active) === 1 ? '' : ' <span class="pill pill-grey">off</span>'}</td>
            <td>${esc(UNIT[r.unit] || r.unit)}</td>
            <td class="mono"><span class="rate">${esc(r.currency)} ${Number(r.rate).toLocaleString(undefined, { minimumFractionDigits: 2 })}</span></td>
            <td>${Number(r.includes_transport) === 1 ? '<span class="pill pill-green">included</span>' : '<span class="pill pill-grey">vehicle drives</span>'}</td>
            <td class="help">${r.valid_from || r.valid_to ? esc((r.valid_from || '…') + ' → ' + (r.valid_to || '…')) : 'no limit'}</td>
            <td>${r.used ? `<span class="pill pill-blue">${r.used} line(s)</span>` : '<span class="help">—</span>'}</td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-edit="${esc(r.code)}">Edit</button>
                <button class="btn btn-light btn-sm rowdel" data-del="${esc(r.code)}" title="${r.used ? 'Switch off' : 'Remove'}">&times;</button></td>
        </tr>`).join('') : '<tr><td colspan="8" class="empty">No excursions yet. Add the first one on the left.</td></tr>';
        document.querySelectorAll('#l_rows [data-edit]').forEach(b => b.addEventListener('click', () => fillForm(b.dataset.edit)));
        document.querySelectorAll('#l_rows [data-del]').forEach(b => b.addEventListener('click', async () => {
            const r = ROWS.find(x => x.code === b.dataset.del);
            const ok = await Swal.fire({ title: r.used ? 'Switch off ' + r.name + '?' : 'Remove ' + r.name + '?', text: r.used ? 'It is on ' + r.used + ' quotation line(s); those keep their copied figures. It will no longer be offered to new quotations.' : 'It has never been quoted, so it is deleted.', showCancelButton: true, confirmButtonText: r.used ? 'Switch off' : 'Remove' });
            if (!ok.isConfirmed) return;
            const j = await post('/delete/' + encodeURIComponent(b.dataset.del), {});
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
    }
    async function load() {
        const j = await api('/data');
        ROWS = (j.data || []);
        render();
    }
    $('f_save').addEventListener('click', async () => {
        const j = await post('/save', {
            code: $('f_code').value, name: $('f_name').value, unit: $('f_unit').value, rate: $('f_rate').value, currency: $('f_cur').value,
            valid_from: $('f_from').value, valid_to: $('f_to').value, note: $('f_note').value,
            includes_transport: $('f_inc').checked ? 1 : 0, active: $('f_active').checked ? 1 : 0
        });
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); clearForm(); load();
    });
    $('f_new').addEventListener('click', clearForm);
    load();
})();
</script>
</body>
</html>
