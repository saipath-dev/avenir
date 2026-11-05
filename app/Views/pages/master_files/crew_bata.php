<?= view('layouts/head') ?>
<style>
    /* ---------- Crew Bata (same tokens as the Tour Inquiry panel) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#b7791f; --tf-amber-soft:#fdf3e1;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card + .tf-card { margin-top:16px; }
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
    .tf .tbl td { padding:9px 10px; border-bottom:1px solid var(--tf-line); vertical-align:middle; }
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
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .rowdel { color:#b6bfca; padding:0 6px; line-height:1.1; font-size:15px; }
    .tf .rowdel:hover { color:var(--tf-red); background:var(--tf-red-soft); }
    .tf .bata-edit { display:flex; gap:4px; align-items:center; min-width:230px; }
    .tf .bata-edit select { max-width:72px; }
    .tf .bata-edit input { max-width:110px; }
    .tf .costing-banner { display:flex; gap:12px; align-items:flex-start; margin:10px 0 8px; padding:11px 14px; border-radius:10px; background:var(--tf-amber-soft); border:1px solid #f0d78c; color:#7a5200; font-size:12.5px; line-height:1.45; max-width:980px; }
    .tf .costing-banner i { font-size:20px; color:var(--tf-amber); flex:none; margin-top:1px; }
    .tf .costing-banner a { color:var(--tf-teal); font-weight:600; }
    .tf .pill-costing { background:var(--tf-amber-soft); color:var(--tf-amber); border:1px solid #f0d78c; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>
                <?= view('layouts/tgs_bar') ?>

                <div class="tf-title">
                    <div>
                        <h4>Crew Roles &amp; Quotation Costing <span class="help">· Admin · Transport &amp; Guide Services</span></h4>
                        <div class="costing-banner"><i class="bi bi-calculator"></i><div><b>Quotation costing figures — not what is paid.</b> The bata per day on this screen is the figure a quotation is <b>costed</b> with (it goes into the customer's price). It is <b>not necessarily the amount paid</b> to a driver or guide: what is actually paid — the standard bata and guide fee, each person's own agreed figure, the rate per kilometre — is kept on <a href="<?= base_url('master_files/crew_people') ?>">Drivers &amp; Guides</a>, and a tour copies those the day the person is engaged.</div></div>
                        <div class="sub">The driver and guide types a quotation can carry — a category (Driver, Chauffeur guide, National guide, and so on) in a language — with the costing bata per day each is quoted at. The quotation screen picks the type and the language from this list and shows the bata and the amount (bata × days); nobody changes a bata there. A bata changed here reaches every inquiry still being worked on straight away; a quotation already issued keeps the figures it went out with. A freelancer's own agreed bata on the supplier ledger is kept for the payment side and is not what is quoted.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Inquiry tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6 id="f_title">Add a driver or guide type</h6><button class="btn btn-light btn-sm" id="f_new" hidden>New</button></div>
                            <div class="tf-card-b">
                                <div class="row g-2">
                                    <div class="col-6"><label class="form-label help">Driver or guide</label>
                                        <select class="form-select form-select-sm" id="f_kind"><option value="driver">Driver</option><option value="guide">Guide (not driving)</option></select></div>
                                    <div class="col-6"><label class="form-label help">Code</label><input class="form-control form-control-sm mono" id="f_code" placeholder="CG_DE" maxlength="20"></div>
                                    <div class="col-6"><label class="form-label help">Category (type)</label>
                                        <input class="form-control form-control-sm" id="f_cat" list="dl_cat" placeholder="Driver · Chauffeur guide · National guide" maxlength="60"><datalist id="dl_cat"></datalist></div>
                                    <div class="col-6"><label class="form-label help">Language</label>
                                        <input class="form-control form-control-sm" id="f_lang" list="dl_lang" placeholder="English · Japanese · German" maxlength="40"><datalist id="dl_lang"></datalist></div>
                                    <div class="col-12"><label class="form-label help">Name shown on the quotation</label><input class="form-control form-control-sm" id="f_name" placeholder="German speaking chauffeur guide" maxlength="80"></div>
                                    <div class="col-4"><label class="form-label help">Currency</label>
                                        <select class="form-select form-select-sm" id="f_cur"><option>LKR</option><option>USD</option><option>EUR</option><option>JPY</option></select></div>
                                    <div class="col-8"><label class="form-label help">Costing bata per day <span class="pill pill-costing">quotation figure</span></label><input type="number" min="0" step="0.01" class="form-control form-control-sm" id="f_bata" placeholder="not set"></div>
                                    <div class="col-12"><label class="form-label help">Note</label><input class="form-control form-control-sm" id="f_note" placeholder="What the bata covers, who decides it" maxlength="255"></div>
                                    <div class="col-12"><div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="f_active" checked>
                                        <label class="form-check-label" for="f_active" style="font-size:12.5px">Active — offered when a quotation is built</label></div></div>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-primary btn-sm" id="f_save"><i class="bi bi-check2"></i> Save</button>
                                </div>
                                <div class="help mt-2">The code is the key and cannot be changed once used; a driver type stays a driver type. A blank bata is shown as "no bata set" on every quotation that carries the type, and Go for Quotation stops until it is set — nothing is ever guessed.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>Drivers</h6><span class="help" id="l_count_driver"></span></div>
                            <div class="tf-card-b" style="padding:0;overflow-x:auto">
                                <table class="tbl"><thead><tr><th>Code</th><th>Category</th><th>Language</th><th>Name</th><th title="The figure a quotation is costed with — not the amount paid">Costing bata / day <span class="pill pill-costing">quoted</span></th><th>On quotations</th><th></th></tr></thead>
                                <tbody id="l_driver"><tr><td colspan="7" class="empty">Loading…</td></tr></tbody></table>
                            </div>
                        </div>
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>Guides</h6><span class="help" id="l_count_guide"></span></div>
                            <div class="tf-card-b" style="padding:0;overflow-x:auto">
                                <table class="tbl"><thead><tr><th>Code</th><th>Category</th><th>Language</th><th>Name</th><th title="The figure a quotation is costed with — not the amount paid">Costing bata / day <span class="pill pill-costing">quoted</span></th><th>On quotations</th><th></th></tr></thead>
                                <tbody id="l_guide"><tr><td colspan="7" class="empty">Loading…</td></tr></tbody></table>
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
    const BASE = '<?= base_url($page_url ?? 'master_files/crew_bata') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2200 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const $ = id => document.getElementById(id);
    const CURS = ['LKR', 'USD', 'EUR', 'JPY'];
    let ROWS = [];

    function suggestName() {
        if ($('f_code').readOnly) return;   // an existing role keeps the name typed for it
        const cat = $('f_cat').value.trim(), lang = $('f_lang').value.trim();
        if (!cat) return;
        $('f_name').placeholder = (lang ? lang + ' speaking ' : '') + cat.toLowerCase();
        if (!$('f_code').value.trim() && cat) {
            const initials = cat.split(/\s+/).map(w => w[0] || '').join('').toUpperCase();
            $('f_code').value = (initials + (lang ? '_' + lang.slice(0, 2).toUpperCase() : '')).replace(/[^A-Z0-9_\-]/g, '').slice(0, 20);
        }
    }
    function clearForm() {
        ['f_code','f_cat','f_lang','f_name','f_bata','f_note'].forEach(k => $(k).value = '');
        $('f_name').placeholder = 'German speaking chauffeur guide';
        $('f_kind').value = 'driver'; $('f_kind').disabled = false; $('f_cur').value = 'LKR'; $('f_active').checked = true;
        $('f_code').readOnly = false; $('f_title').textContent = 'Add a driver or guide type'; $('f_new').hidden = true;
    }
    function fillForm(code) {
        const r = ROWS.find(x => x.code === code); if (!r) return;
        $('f_code').value = r.code; $('f_code').readOnly = true;
        $('f_kind').value = r.kind; $('f_kind').disabled = true;
        $('f_cat').value = r.category || ''; $('f_lang').value = r.language || ''; $('f_name').value = r.name;
        $('f_bata').value = r.bata ?? ''; $('f_cur').value = r.currency || 'LKR'; $('f_note').value = r.note || '';
        $('f_active').checked = Number(r.active) === 1;
        $('f_title').textContent = 'Change ' + r.name; $('f_new').hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function rowsOf(kind) {
        const rows = ROWS.filter(r => r.kind === kind);
        return rows.length ? rows.map(r => `<tr class="${Number(r.active) === 1 ? '' : 'off'}" data-row="${esc(r.code)}">
            <td class="mono">${esc(r.code)}</td>
            <td><b>${esc(r.category || '—')}</b></td>
            <td>${esc(r.language || 'any')}</td>
            <td>${esc(r.name)}${r.note ? `<div class="help">${esc(r.note)}</div>` : ''}${Number(r.active) === 1 ? '' : ' <span class="pill pill-grey">off</span>'}</td>
            <td><div class="bata-edit">
                <select class="form-select form-select-sm" data-batacur="${esc(r.code)}">${CURS.map(c => `<option${c === (r.currency || 'LKR') ? ' selected' : ''}>${c}</option>`).join('')}</select>
                <input type="number" min="0" step="0.01" class="form-control form-control-sm mono" data-bata="${esc(r.code)}" value="${r.bata ?? ''}" placeholder="not set">
                <button class="btn btn-light btn-sm" data-batasave="${esc(r.code)}" title="Save this bata">Save</button></div>
                ${r.bata == null ? '<div class="help" style="color:var(--tf-red)">no costing bata set — every quotation carrying it stops at Go for Quotation</div>' : '<div class="help">quotation costing · not the amount paid</div>'}</td>
            <td>${r.used ? `<span class="pill pill-blue">${r.used} line(s)</span>${r.open ? `<div class="help">${r.open} still being worked on</div>` : ''}` : '<span class="help">—</span>'}</td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-edit="${esc(r.code)}">Edit</button>
                <button class="btn btn-light btn-sm rowdel" data-del="${esc(r.code)}" title="${r.used ? 'Switch off' : 'Remove'}">&times;</button></td>
        </tr>`).join('') : `<tr><td colspan="7" class="empty">No ${kind} types yet. Add the first one on the left.</td></tr>`;
    }
    function render() {
        ['driver', 'guide'].forEach(kind => {
            const rows = ROWS.filter(r => r.kind === kind);
            $('l_count_' + kind).textContent = rows.length ? rows.length + ' type' + (rows.length === 1 ? '' : 's') + ' · ' + rows.filter(r => Number(r.active) === 1).length + ' active · ' + rows.filter(r => r.bata == null).length + ' without a bata' : '';
            $('l_' + kind).innerHTML = rowsOf(kind);
        });
        const cats = [...new Set(ROWS.map(r => r.category).filter(Boolean))].sort();
        const langs = [...new Set(ROWS.map(r => r.language).filter(Boolean))].sort();
        $('dl_cat').innerHTML = cats.map(c => `<option value="${esc(c)}">`).join('');
        $('dl_lang').innerHTML = langs.map(c => `<option value="${esc(c)}">`).join('');
        document.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => fillForm(b.dataset.edit)));
        document.querySelectorAll('[data-batasave]').forEach(b => b.addEventListener('click', async () => {
            const code = b.dataset.batasave;
            const j = await post('/bata', { code, bata: document.querySelector(`[data-bata="${code}"]`).value, currency: document.querySelector(`[data-batacur="${code}"]`).value });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
        document.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', async () => {
            const r = ROWS.find(x => x.code === b.dataset.del);
            const ok = await Swal.fire({ title: r.used ? 'Switch off ' + r.name + '?' : 'Are you sure you want to delete this record?', text: r.used ? 'It is on ' + r.used + ' quotation line(s); those keep what they carry. It will no longer be offered to new quotations.' : r.name + ' has never been quoted, so it is deleted from the master.', showCancelButton: true, confirmButtonText: r.used ? 'Switch off' : 'Delete' });
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
    ['f_cat', 'f_lang'].forEach(k => $(k).addEventListener('input', suggestName));
    $('f_save').addEventListener('click', async () => {
        const j = await post('/save', {
            code: $('f_code').value, kind: $('f_kind').value, category: $('f_cat').value, language: $('f_lang').value, name: $('f_name').value,
            bata: $('f_bata').value, currency: $('f_cur').value, note: $('f_note').value, active: $('f_active').checked ? 1 : 0
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
