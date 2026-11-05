<?= view('layouts/head') ?>
<style>
    /* ---------- Driver & Guide Master (same tokens as the Tour Inquiry panel) ---------- */
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
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:860px; }
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
    .tf .tbl tr.off td { opacity:.55; }
    .tf .tbl tbody tr:hover { background:var(--tf-bg); }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; margin:1px 2px 1px 0; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .pill-teal { background:var(--tf-teal-soft); color:var(--tf-teal); }
    .tf .pill-cat { background:var(--tf-navy); color:#fff; }
    .tf .roof { background:#fff3cd; color:#7a5200; border:1px solid #f0d78c; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; white-space:nowrap; display:inline-block; }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .tabs { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
    .tf .tabs .tab { border:1px solid var(--tf-line); background:#fff; border-radius:20px; padding:5px 14px; font-size:12.5px; font-weight:600; cursor:pointer; color:var(--tf-ink); }
    .tf .tabs .tab .n { font-weight:400; color:var(--tf-muted); margin-left:4px; }
    .tf .tabs .tab.on { background:var(--tf-navy); border-color:var(--tf-navy); color:#fff; }
    .tf .tabs .tab.on .n { color:#dbe3ee; }
    .tf .tabs .tab.warn { border-color:#f0d78c; background:var(--tf-amber-soft); color:var(--tf-amber); }
    .tf .filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .tf .filters .form-control { width:auto; }
    .tf .avatar { width:36px; height:36px; border-radius:50%; object-fit:cover; background:var(--tf-bg); border:1px solid var(--tf-line); }
    .tf .warn-list { margin:0; padding-left:16px; font-size:12px; }
    .tf .warn-list li.block { color:var(--tf-red); }
    .tf .warn-list li.warn { color:var(--tf-amber); }
    .tf .warn-list li.info { color:var(--tf-muted); }
    /* the editor */
    .tf .ed-sec { border:1px solid var(--tf-line); border-radius:8px; padding:12px 14px; margin-bottom:12px; }
    .tf .ed-sec h6 { font-size:12px; letter-spacing:.05em; text-transform:uppercase; color:var(--tf-muted); font-weight:700; margin:0 0 8px; display:flex; justify-content:space-between; align-items:center; gap:8px; }
    .tf .ed-sec.locked { background:var(--tf-bg); }
    .tf .thumb { width:84px; height:64px; object-fit:cover; border-radius:6px; border:1px solid var(--tf-line); }
    .tf .thumbs { display:flex; gap:8px; flex-wrap:wrap; }
    .tf .thumbs .ph { position:relative; text-align:center; font-size:10.5px; color:var(--tf-muted); }
    .tf .thumbs .ph button { position:absolute; top:-6px; right:-6px; border:0; background:var(--tf-red); color:#fff; border-radius:50%; width:18px; height:18px; font-size:11px; line-height:18px; padding:0; }
    .tf .filebox { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .tf .filebox a { font-size:12px; }
    .tf .bank { background:#fbfaf3; border-color:#e8dfb6; }
    .tf .ratebox { border:1px solid var(--tf-line); border-radius:8px; padding:10px 12px; height:100%; background:#fbfcfd; }
    .tf .ratebox.on { border-color:var(--tf-teal); background:var(--tf-teal-soft); }
    .tf .ratebox .lbl { font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--tf-muted); font-weight:700; }
    .tf .ratebox .big { font-size:20px; font-weight:700; color:var(--tf-navy); margin:4px 0 2px; }
    .tf .ratebox .big small { font-size:12px; font-weight:500; color:var(--tf-muted); }
    .tf .sticky-actions { position:sticky; bottom:0; background:#fff; border-top:1px solid var(--tf-line); padding:10px 0 0; display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .tf .modal-xl { max-width:1040px; }
    .tf .std { display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:10px; }
    .tf .std .ratebox .big { font-size:18px; }
    .tf .std .ratebox.none { border-style:dashed; background:#fff; }
    .tf .hist { font-size:12px; }
    .tf .hist div { padding:4px 0; border-bottom:1px dashed var(--tf-line); }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar', ['tourdesk_desk_only' => service('request')->getGet('desk') === 'transport' ? 'transport' : null]) /* tf-transdesk-r1: opened from the Transport Desk (?desk=transport) the strip shows that desk's functions; otherwise unchanged */ ?>
                <?= view('layouts/tgs_bar') ?>

                <div class="tf-title">
                    <div>
                        <h4>Drivers &amp; Guides <span class="help">· the Driver &amp; Guide Master · Transport &amp; Guide Services</span></h4>
                        <div class="sub">One record per person the company engages, in three categories: <b>JSD</b> Japanese Speaking Driver (drives, speaks Japanese, is not the licensed guide), <b>JSCG</b> Japanese Speaking Chauffeur Guide (drives, speaks Japanese, holds the guide licence) and <b>JSNTG</b> Japanese Speaking National Tourist Guide (the licensed guide; no vehicle — a driver is allocated separately). Each tab lists its own category only. Transport Operations picks the person for a confirmed tour from here, strictly by category; the pack, the vouchers and the payment screens name them. Nothing is deleted: a person who no longer works for the company is archived.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Drivers &amp; Guides master needs the schema 36 upgrade. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> as the administrator and press Install now; this screen then works.</div>
                <?php elseif (empty($master)): ?>
                <div class="not-installed">The Driver &amp; Guide Master (categories, vehicles, rates, bank details) needs the schema 44 upgrade. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> as the administrator and press Install now; the driver registration is then brought onto the master.</div>
                <?php else: ?>

                <div class="tf-card" id="std_card">
                    <div class="tf-card-h"><h6>Standard figures <span class="pill pill-teal">what is PAID</span> <span class="help">— paid to the crew unless a person has their own agreed figure; each with the date it took effect. Not the quotation's costing figures: those are under <a href="<?= base_url('master_files/crew_bata') ?>">Crew Roles &amp; Quotation Costing</a>.</span></h6><span id="std_actions"></span></div>
                    <div class="tf-card-b"><div class="std" id="std_boxes"></div></div>
                </div>

                <div class="tf-card">
                    <div class="tf-card-h">
                        <div class="tabs" id="tabs">
                            <button class="tab on" data-tab="ALL">All <span class="n" id="n_ALL"></span></button>
                            <button class="tab" data-tab="JSD" title="Japanese Speaking Driver">JSD <span class="n" id="n_JSD"></span></button>
                            <button class="tab" data-tab="JSCG" title="Japanese Speaking Chauffeur Guide">JSCG <span class="n" id="n_JSCG"></span></button>
                            <button class="tab" data-tab="JSNTG" title="Japanese Speaking National Tourist Guide">JSNTG <span class="n" id="n_JSNTG"></span></button>
                            <button class="tab warn" data-tab="NONE" id="tab_none" style="display:none" title="Brought over from the driver registration with no category that could be read from it — classify or archive">To classify <span class="n" id="n_NONE"></span></button>
                        </div>
                        <div class="filters">
                            <span id="l_class"></span>
                            <input class="form-control form-control-sm" id="l_q" placeholder="Name, nickname, phone, NIC, vehicle, account" style="width:260px">
                            <label class="help" style="display:inline-flex;gap:5px;align-items:center"><input type="checkbox" id="l_arch"> show archived</label>
                            <span id="add_btns"></span>
                        </div>
                    </div>
                    <div class="tf-card-b" id="tab_note" style="padding:8px 14px;display:none"></div>
                    <div class="tf-card-b" style="padding:0;overflow-x:auto">
                        <table class="tbl"><thead><tr><th></th><th>Person</th><th>Contact</th><th>Licences</th><th>Vehicle</th><th>Rate / km</th><th id="th_bank">Bank</th><th>Tours</th><th></th></tr></thead>
                        <tbody id="l_rows"><tr><td colspan="9" class="empty">Loading…</td></tr></tbody></table>
                    </div>
                    <div class="tf-card-b help" id="l_count"></div>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- the standard figures -->
<div class="modal fade" id="m_std" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="s_title">Standard figures</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="s_body"></div>
</div></div></div>

<!-- the record -->
<div class="modal fade" id="m_edit" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="e_title">Add</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="e_body"></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'master_files/crew_people') ?>';
    const ROOT = '<?= rtrim(base_url(), '/') ?>';
    const INSTALLED = <?= !empty($installed) && !empty($master) ? 'true' : 'false' ?>;
    const CAN_BANK = <?= !empty($can_bank) ? 'true' : 'false' ?>;   // "Driver & Guide bank details": the bank section and the account on the payment screens
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2200 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const upload = async (path, file, extra) => { const fd = new FormData(); fd.append('file', file); Object.entries(extra || {}).forEach(([k, v]) => { if (v != null) fd.append(k, v); }); const r = await fetch(BASE + path, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }); return r.json(); };
    const $ = id => document.getElementById(id);
    const num = v => v == null ? '—' : Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const CAT = { JSD: 'Japanese Speaking Driver', JSCG: 'Japanese Speaking Chauffeur Guide', JSNTG: 'Japanese Speaking National Tourist Guide' };
    const drives = c => c === 'JSD' || c === 'JSCG';
    const guides = c => c === 'JSCG' || c === 'JSNTG';
    let PEOPLE = [], ROLES = [], LEDGER = [], COUNTS = {}, META = {}, TAB = 'ALL', P = null;   // P: the record open in the editor
    // from Transport Rates: ?class=SEDAN lists the people whose vehicle is in that class (a filter chip, cleared with its ×)
    let CLASS = (new URLSearchParams(location.search).get('class') || '').toUpperCase();

    // ---------------- the list ----------------
    function warnHtml(ws, max, skip) {
        const list = (ws || []).filter(w => w.level !== 'info' && !(skip || []).includes(w.code)).slice(0, max || 3);
        return list.length ? `<ul class="warn-list">${list.map(w => `<li class="${w.level}">${esc(w.text)}</li>`).join('')}</ul>` : '';
    }
    function catPill(p) {
        if (!p.category) return '<span class="pill pill-amber" title="No category yet — classify the record">to classify</span>';
        return `<span class="pill pill-cat" title="${esc(CAT[p.category] || '')}">${esc(p.category)}</span>${p.category_confirmed ? '' : ' <span class="pill pill-amber" title="Proposed from the driver registration — open the record and confirm">to confirm</span>'}`;
    }
    function render() {
        const q = $('l_q').value.trim().toLowerCase();
        let rows = PEOPLE.filter(p => TAB === 'ALL' || (TAB === 'NONE' ? !p.category : p.category === TAB));
        if (CLASS) rows = rows.filter(p => p.vehicle && String(p.vehicle.rate_class || '').toUpperCase() === CLASS);
        const cls = CLASS ? (META.rate_classes || []).find(x => x.code === CLASS) : null;
        $('l_class').innerHTML = CLASS ? `<span class="pill pill-blue" style="font-size:12px;padding:4px 10px">vehicle class: ${esc(cls ? cls.name : CLASS)}${cls && cls.rate_km != null ? ' · general LKR ' + num(cls.rate_km) + ' / km' : ''} <a href="#" id="l_class_x" title="Show everyone" style="margin-left:6px;text-decoration:none">&times;</a></span> <a class="help" href="${ROOT}/master_files/transport_rates">Transport Rates</a>` : '';
        $('l_class_x')?.addEventListener('click', e => { e.preventDefault(); CLASS = ''; history.replaceState(null, '', location.pathname); render(); });
        if (q) rows = rows.filter(p => [p.name, p.nickname, p.mobile, p.whatsapp, p.phone, p.nic, p.supplier_acc, p.email, p.languages, p.vehicle_reg, p.vehicle && p.vehicle.reg_no].filter(Boolean).join(' ').toLowerCase().includes(q));
        $('l_count').textContent = rows.length + ' shown · ' + (COUNTS.ALL || 0) + ' active on the master' + (COUNTS.to_confirm ? ' · ' + COUNTS.to_confirm + ' with the category still to confirm' : '');
        $('th_bank').style.display = CAN_BANK ? '' : 'none';
        $('l_rows').innerHTML = rows.length ? rows.map(p => {
            const v = p.vehicle;
            const lic = [];
            if (drives(p.category) || (!p.category && p.is_driver)) lic.push(p.licence_no || p.licence_expiry ? `<span class="mono">${esc(p.licence_no || 'DL')}</span> ${p.licence_expiry ? `<span class="pill ${p.licence_expired ? 'pill-red' : 'pill-grey'}">${p.licence_expired ? 'expired ' : 'to '}${esc(p.licence_expiry)}</span>` : ''}` : '<span class="help">driving licence not on file</span>');
            if (guides(p.category) || (!p.category && p.is_guide)) lic.push(p.guide_licence_no || p.guide_licence_expiry ? `guide <span class="mono">${esc(p.guide_licence_no || '')}</span> ${p.guide_licence_expiry ? `<span class="pill ${p.guide_licence_expired ? 'pill-red' : 'pill-grey'}">${p.guide_licence_expired ? 'expired ' : 'to '}${esc(p.guide_licence_expiry)}</span>` : ''}` : '<span class="help">guide licence not on file</span>');
            const veh = p.category === 'JSNTG' ? '<span class="help">no vehicle (guide)</span>' : (v ? `<b class="mono">${esc(v.reg_no || '')}</b> ${v.type_name ? esc(v.type_name) : ''}${v.vehicle_type === 'van' ? ' <span class="roof">' + esc(v.roof_name || 'ROOF?') + '</span>' : ''}<div class="help">${esc(v.label || '')}${v.seats ? ' · ' + v.seats + ' seats' : ''}${v.max_pax_luggage ? ' · ' + v.max_pax_luggage + ' pax w/ luggage' : ''}</div>${v.insurance_expired ? '<span class="pill pill-red">insurance expired</span>' : ''}${v.licence_expired ? '<span class="pill pill-red">revenue licence expired</span>' : ''}` : (p.vehicle_reg ? `<span class="mono">${esc(p.vehicle_reg)}</span> <span class="help">details not on file</span>` : '<span class="help">—</span>'));
            const ef = p.rate_effective || {};
            const bt = p.bata_effective || {};
            const bataPill = bt.source === 'agreed' ? '<span class="pill pill-teal">agreed</span>' : (bt.source === 'standard' ? '<span class="pill pill-grey">standard</span>' : '');
            const bataLine = bt.bata != null ? `${p.category === 'JSNTG' ? 'guide fee' : 'bata'} ${num(bt.bata)} / day ${bataPill}${bt.overnight != null ? ' · overnight ' + num(bt.overnight) + (bt.overnight_source === 'agreed' ? ' agreed' : '') : ''}` : (p.category ? `<span class="pill pill-amber">no ${p.category === 'JSNTG' ? 'guide fee' : 'bata'}</span>` : '');
            const rate = p.category === 'JSNTG' ? (bt.bata != null ? `<span class="mono">LKR ${num(bt.bata)}</span><div class="help">guide fee / day ${bataPill}${bt.overnight != null ? '<br>overnight ' + num(bt.overnight) + ' / night' + (bt.overnight_source === 'agreed' ? ' agreed' : '') : ''}</div>` : '<span class="pill pill-amber">no guide fee</span>')
                : (ef.rate_km != null ? `<span class="mono">LKR ${num(ef.rate_km)}</span><div class="help">${ef.source === 'special' ? '<span class="pill pill-teal">special</span>' + (ef.general ? ' general ' + num(ef.general.rate_km) : '') : '<span class="pill pill-grey">general</span> ' + esc(ef.class_name || '')}${bataLine ? '<br>' + bataLine : ''}</div>` : `<span class="help">no rate${v ? ' · class not set' : ''}</span>${bataLine ? '<div class="help">' + bataLine + '</div>' : ''}`);
            const bank = !CAN_BANK ? '' : `<td>${p.has_bank ? `<span class="mono">${esc(p.bank_account_name || '')}</span>${p.bank_account_name ? '' : '<span class="help">account name not on file</span>'}<div class="help">${esc(p.bank_name || '')}${p.bank_branch ? ' · ' + esc(p.bank_branch) : ''}</div>${p.bank_verified ? '<span class="pill pill-green">verified</span>' : '<span class="pill pill-amber">not verified</span>'}` : '<span class="help">no account</span>'}</td>`;
            return `<tr class="${p.archived || p.status !== 'active' ? 'off' : ''}">
            <td>${p.photo_file_url ? `<img class="avatar" src="${ROOT}/${esc(p.photo_file_url)}" alt="">` : '<div class="avatar"></div>'}</td>
            <td><b>${esc(p.name)}</b>${p.nickname ? ` <span class="help">“${esc(p.nickname)}”</span>` : ''}<div>${catPill(p)}${p.archived ? '<span class="pill pill-grey">archived</span>' : (p.status !== 'active' ? '<span class="pill pill-grey">inactive</span>' : '')}</div><div class="help">${esc(p.languages || '')}${p.supplier_acc ? ' · ' + esc(p.supplier_acc) : ''}</div></td>
            <td>${[p.mobile, p.whatsapp ? 'WhatsApp ' + p.whatsapp : null, p.email].filter(Boolean).map(esc).join('<br>') || '<span class="help">—</span>'}</td>
            <td>${lic.join('<br>') || '<span class="help">—</span>'}</td>
            <td>${veh}</td>
            <td>${rate}</td>
            ${bank}
            <td class="mono">${p.engagements || 0}</td>
            <td style="white-space:nowrap">${p.archived ? `<button class="btn btn-light btn-sm" data-restore="${p.id}">Restore</button>` : `<button class="btn btn-light btn-sm" data-open="${p.id}">Open</button> <button class="btn btn-light btn-sm" data-archive="${p.id}" title="Kept, never deleted">Archive…</button>`}</td>
        </tr>`; }).join('') : `<tr><td colspan="9" class="empty">${TAB === 'ALL' ? 'Nobody on the master yet.' : (TAB === 'NONE' ? 'Nothing to classify.' : 'No ' + TAB + ' on the master yet — add one with the button above.')}</td></tr>`;
        document.querySelectorAll('[data-open]').forEach(b => b.addEventListener('click', () => openRecord(Number(b.dataset.open))));
        document.querySelectorAll('[data-archive]').forEach(b => b.addEventListener('click', async () => {
            const p = PEOPLE.find(x => x.id === Number(b.dataset.archive));
            const r = await Swal.fire({ title: 'Archive ' + p.name + '?', text: 'They are no longer offered on Transport; the tours they worked keep their name; the record can be restored from "show archived".', input: 'text', inputPlaceholder: 'Why (optional)', showCancelButton: true, confirmButtonText: 'Archive' });
            if (!r.isConfirmed) return;
            const j = await post('/archive/' + p.id, { note: r.value || '' });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
        document.querySelectorAll('[data-restore]').forEach(b => b.addEventListener('click', async () => {
            const j = await post('/restore/' + b.dataset.restore, {});
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
    }
    function renderTabs() {
        ['ALL', 'JSD', 'JSCG', 'JSNTG', 'NONE'].forEach(k => { const el = $('n_' + k); if (el) el.textContent = COUNTS[k] != null ? COUNTS[k] : ''; });
        $('tab_none').style.display = (COUNTS.NONE || 0) > 0 ? '' : 'none';
        document.querySelectorAll('#tabs .tab').forEach(t => t.classList.toggle('on', t.dataset.tab === TAB));
        // every category has its own Add
        const adds = TAB === 'ALL' || TAB === 'NONE' ? ['JSD', 'JSCG', 'JSNTG'] : [TAB];
        $('add_btns').innerHTML = adds.map(c => `<button class="btn btn-primary btn-sm" data-add="${c}" title="${esc(CAT[c])}"><i class="bi bi-plus-lg"></i> Add ${c}</button>`).join(' ');
        document.querySelectorAll('[data-add]').forEach(b => b.addEventListener('click', () => openRecord(null, b.dataset.add)));
        const notes = { JSD: 'Japanese Speaking Drivers: drive the tour, speak Japanese, are not the licensed guide. A JSD line on a confirmed tour lists these people only.',
            JSCG: 'Japanese Speaking Chauffeur Guides: drive the vehicle, speak Japanese, hold the guide licence. A JSCG line lists these people only.',
            JSNTG: 'Japanese Speaking National Tourist Guides: the licensed guide; no vehicle on this master — a driver and vehicle are allocated to the tour separately. A JSNTG (or Japanese guide) line lists these people only.',
            NONE: 'Brought over from the driver registration (Supplier → All Drivers) with nothing on it that says which category they are, or not a person at all (a vehicle provider, a company). Open each: set the category and confirm, or archive. Nobody here is offered on a tour until classified.' };
        $('tab_note').style.display = notes[TAB] ? '' : 'none';
        $('tab_note').innerHTML = notes[TAB] ? `<span class="help">${esc(notes[TAB])}</span>` : '';
    }
    // ---------------- the standard figures (bata, guide fee, overnight allowance), each with its history ----------------
    function renderStandards() {
        const list = META.standards || [];
        $('std_boxes').innerHTML = list.map(st => `<div class="ratebox ${st.amount != null ? 'on' : 'none'}"><div class="lbl">${esc(st.name)} <span class="help">— ${esc((st.for || []).join(' / '))}</span></div>
            ${st.amount != null ? `<div class="big mono">LKR ${num(st.amount)} <small>${esc(st.unit)}</small></div><div class="help">in force from ${esc(st.effective_from)}${(st.history || []).length > 1 ? ' · ' + st.history.length + ' figures on record' : ''}</div>`
                : `<div class="big" style="font-size:14px;color:var(--tf-amber)">not set</div><div class="help">${st.item === 'overnight' ? 'No figure until the rule and amount are confirmed; it is never added to a settlement by itself.' : 'No figure yet.'}</div>`}</div>`).join('');
        $('std_actions').innerHTML = META.can_standards ? '<button class="btn btn-light btn-sm" id="std_open"><i class="bi bi-clock-history"></i> History &amp; new figure…</button>' : '<button class="btn btn-light btn-sm" id="std_open">History…</button>';
        $('std_open').addEventListener('click', openStandards);
    }
    function openStandards() {
        const can = !!META.can_standards, today = new Date().toISOString().slice(0, 10);
        $('s_body').innerHTML = (META.standards || []).map(st => `<div class="ed-sec"><h6>${esc(st.name)} <span class="help">${esc((st.for || []).join(' / '))} · ${esc(st.unit)}${st.amount != null ? ` · in force: <b class="mono">LKR ${num(st.amount)}</b> from ${esc(st.effective_from)}` : ' · not set'}</span></h6>
            ${(st.history || []).length ? `<table class="tbl"><thead><tr><th>From</th><th>Figure</th><th>Note</th><th>Set by</th></tr></thead><tbody>${st.history.map(h => `<tr><td class="mono">${esc(h.effective_from)}${h.in_force ? '' : ' <span class="pill pill-grey">future</span>'}</td><td class="mono">${h.amount != null ? 'LKR ' + num(h.amount) : '<span class="help">no figure (withdrawn)</span>'}</td><td>${esc(h.note || '')}</td><td class="help">${esc(h.set_by || '')} · ${esc(String(h.set_at || '').slice(0, 16))}</td></tr>`).join('')}</tbody></table>` : `<div class="help">${st.item === 'overnight' ? 'Deliberately not set: no figure is invented until the overnight allowance rule and amount are confirmed. Until then a JSNTG\'s overnight allowance is only what is agreed on their own record, and it is never added to a settlement by itself.' : 'Nothing on record.'}</div>`}
            ${can ? `<div class="row g-2 align-items-end mt-1">${f('New figure (LKR, ' + st.unit + ')', 'sd_amt_' + st.item, '', { col: 'col-md-3', type: 'number', cls: 'mono' })}<div class="col-md-3"><label class="form-label help">Effective from</label><input type="date" class="form-control form-control-sm" id="sd_from_${st.item}" value="${today}"></div>${f('Note', 'sd_note_' + st.item, '', { col: 'col-md-4', ph: 'why / who decided', max: 255 })}<div class="col-md-2"><button class="btn btn-light btn-sm" data-std="${st.item}">Add</button></div>
                <div class="col-12 help">A new figure from a date is a new line: the earlier ones stay, every person\'s own agreed figure stays, and no tour already allocated moves — each copied the figure in force the day the person was engaged.</div></div>` : '<div class="help">Only the System Administrator sets the standard figures.</div>'}
        </div>`).join('') + ((META.standard_history || []).length ? `<div class="ed-sec"><h6>Changes</h6><div class="hist">${META.standard_history.map(h => `<div><span class="help">${esc(String(h.at || '').slice(0, 16))} · ${esc(h.by_name || '')}</span> — ${esc(h.summary)}</div>`).join('')}</div></div>` : '');
        document.querySelectorAll('[data-std]').forEach(b => b.addEventListener('click', async () => {
            const it = b.dataset.std;
            const amt = $('sd_amt_' + it).value;
            if (amt === '') { toast('error', 'Give the figure in LKR.'); return; }
            const r = await Swal.fire({ title: 'Set the ' + (META.standards.find(x => x.item === it) || {}).name.toLowerCase() + '?', html: `<b>LKR ${num(amt)}</b> from <b>${esc($('sd_from_' + it).value)}</b>.<br><span class="help">Every person\'s own agreed figure stays; tours already allocated keep the figure copied when the person was engaged.</span>`, showCancelButton: true, confirmButtonText: 'Set it' });
            if (!r.isConfirmed) return;
            const j = await post('/standard/' + it, { amount: amt, effective_from: $('sd_from_' + it).value, note: $('sd_note_' + it).value });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); META.standards = j.data.standards; META.standard_history = j.data.standard_history; renderStandards(); openStandards(); load();
        }));
        bootstrap.Modal.getOrCreateInstance($('m_std')).show();
    }
    async function load() {
        const j = await api('/data' + ($('l_arch').checked ? '?archived=1' : ''));
        PEOPLE = j.data?.people || []; ROLES = j.data?.roles || []; LEDGER = j.data?.ledger || []; COUNTS = j.data?.counts || {}; META = j.data || {};
        renderStandards(); renderTabs(); render();
    }

    // ---------------- the record ----------------
    const f = (label, id, val, extra) => `<div class="${(extra && extra.col) || 'col-md-4'}"><label class="form-label help">${label}</label><input class="form-control form-control-sm ${(extra && extra.cls) || ''}" id="${id}" value="${esc(val ?? '')}" ${(extra && extra.type) ? 'type="' + extra.type + '"' : ''} ${(extra && extra.ph) ? 'placeholder="' + esc(extra.ph) + '"' : ''} ${(extra && extra.max) ? 'maxlength="' + extra.max + '"' : ''}></div>`;
    const fileBox = (label, url, kind, exists) => `<div class="filebox"><span class="help">${label}</span>${url ? `<a href="${ROOT}/${esc(url)}" target="_blank">${exists === false ? 'file missing on the server' : 'view copy'}</a>` : '<span class="help">no copy</span>'}<input type="file" class="form-control form-control-sm" style="width:230px" data-up="${kind}" accept=".jpg,.jpeg,.png,.webp,.pdf"></div>`;
    function expPill(d, expired) { return d ? `<span class="pill ${expired ? 'pill-red' : 'pill-grey'}">${expired ? 'expired ' : 'valid to '}${esc(d)}</span>` : ''; }

    async function openRecord(id, newCat) {
        if (id) {
            const j = await api('/person/' + id);
            if (j.status !== 200) { toast('error', j.message); return; }
            P = j.data;
        } else {
            P = { id: null, category: newCat, category_confirmed: true, status: 'active', kind: newCat === 'JSNTG' ? 'guide' : (newCat === 'JSCG' ? 'both' : 'driver'), codes: [], warnings: [], vehicle: null, rates: [], history: [], id_type: 'nic', garage_same: false, bata_currency: 'LKR' };
        }
        renderEditor();
        bootstrap.Modal.getOrCreateInstance($('m_edit')).show();
        if (window.TfView && id) TfView.setParams({ person: id }, { push: !TfView.param('person') });   // tf-refresh-r1 (a new, unsaved record is never put in the address)
    }
    function renderEditor() {
        const p = P, c = p.category, isNew = !p.id;
        $('e_title').innerHTML = isNew ? `Add a ${esc(c || 'person')} <span class="help">· ${esc(CAT[c] || '')}</span>` : `${esc(p.name)} ${p.nickname ? '<span class="help">“' + esc(p.nickname) + '”</span>' : ''} <span class="pill pill-cat">${esc(c || 'to classify')}</span>${p.category_confirmed ? '' : ' <span class="pill pill-amber">category to confirm</span>'}`;
        const catOpts = ['JSD', 'JSCG', 'JSNTG'].map(k => `<option value="${k}" ${c === k ? 'selected' : ''}>${k} — ${CAT[k]}</option>`).join('');
        const warn = (p.warnings || []).length ? `<div class="ed-sec" style="border-color:#f0d78c;background:var(--tf-amber-soft)"><h6>Warnings</h6>${warnHtml(p.warnings, 10)}</div>` : '';
        const personal = `<div class="ed-sec"><h6>Category &amp; status</h6><div class="row g-2">
            <div class="col-md-6"><label class="form-label help">Category</label><select class="form-select form-select-sm" id="e_cat">${c ? '' : '<option value="">— to classify —</option>'}${catOpts}</select>
                ${!isNew && !p.category_confirmed ? `<div class="mt-1"><button class="btn btn-light btn-sm" id="e_confirm"><i class="bi bi-check2"></i> Confirm the category as shown</button> <span class="help">proposed from the driver registration</span></div>` : ''}</div>
            <div class="col-md-3"><label class="form-label help">Status</label><select class="form-select form-select-sm" id="e_status"><option value="active" ${p.status !== 'inactive' ? 'selected' : ''}>Active — offered on Transport</option><option value="inactive" ${p.status === 'inactive' ? 'selected' : ''}>Inactive — kept, not offered</option></select></div>
            <div class="col-md-3"><label class="form-label help">Ledger account <span class="text-muted">(6-9-11, from accounts)</span></label><input class="form-control form-control-sm mono" id="e_acc" value="${esc(p.supplier_acc || '')}" placeholder="blank until accounts gives one" maxlength="50"></div>
        </div></div>
        <div class="ed-sec"><h6>Personal details</h6><div class="row g-2">
            ${f('Full legal name', 'e_name', p.name, { col: 'col-md-5', ph: 'as on the NIC / licence', max: 190 })}
            ${f('Nickname / known as', 'e_nick', p.nickname, { col: 'col-md-3', ph: 'what the staff call them', max: 120 })}
            <div class="col-md-2"><label class="form-label help">ID type</label><select class="form-select form-select-sm" id="e_idtype"><option value="nic" ${p.id_type !== 'passport' ? 'selected' : ''}>NIC</option><option value="passport" ${p.id_type === 'passport' ? 'selected' : ''}>Passport</option></select></div>
            ${f('NIC / passport number', 'e_nic', p.nic, { col: 'col-md-2', cls: 'mono', max: 40 })}
            ${f('Mobile', 'e_mobile', p.mobile, { col: 'col-md-3', ph: '07x xxx xxxx', max: 60 })}
            ${f('WhatsApp', 'e_wa', p.whatsapp, { col: 'col-md-3', ph: 'if different', max: 60 })}
            ${f('E-mail (if available)', 'e_email', p.email, { col: 'col-md-3', max: 190 })}
            ${f('Languages', 'e_langs', p.languages, { col: 'col-md-3', ph: 'Japanese, English', max: 190 })}
            ${f('Home address', 'e_home', p.home_address, { col: 'col-md-12', max: 400 })}
            ${c === 'JSNTG' ? f('Japanese language details / remarks', 'e_jp', p.japanese_note, { col: 'col-md-12', ph: 'JLPT level, years in Japan, specialities…', max: 400 }) : ''}
            ${f('General remarks', 'e_note', p.note, { col: 'col-md-12', ph: 'Anything the desk should know', max: 500 })}
            <div class="col-md-12">${isNew ? '<span class="help">Save the record first, then the photo, licence copies and vehicle can be added.</span>' : `<div class="d-flex gap-3 align-items-center flex-wrap">${p.photo_file_url ? `<img class="thumb" src="${ROOT}/${esc(p.photo_file_url)}" alt="photo">` : ''}${fileBox('Photo', p.photo_file_url, 'photo')}</div>`}</div>
        </div></div>`;
        const garage = !drives(c) ? '' : `<div class="ed-sec"><h6>Garage / vehicle base address</h6><div class="row g-2">
            <div class="col-md-12"><label class="help" style="display:inline-flex;gap:6px;align-items:center"><input type="checkbox" id="e_gsame" ${p.garage_same ? 'checked' : ''}> Same as home address</label></div>
            ${f('Garage / vehicle base address', 'e_garage', p.garage_same ? '' : p.garage_address, { col: 'col-md-12', max: 400 })}
        </div></div>`;
        const licences = `<div class="ed-sec"><h6>Licences</h6><div class="row g-2">
            ${drives(c) || (!c && p.is_driver) ? `${f('Driving licence number', 'e_lic', p.licence_no, { col: 'col-md-4', cls: 'mono', max: 60 })}<div class="col-md-3"><label class="form-label help">Driving licence expiry</label><input type="date" class="form-control form-control-sm" id="e_licexp" value="${esc(p.licence_expiry || '')}"> ${expPill(p.licence_expiry, p.licence_expired)}</div><div class="col-md-5">${isNew ? '' : fileBox('Driving licence copy', p.licence_file_url, 'licence', p.licence_file ? undefined : false)}</div>` : ''}
            ${guides(c) || (!c && p.is_guide) ? `${f(c === 'JSNTG' ? 'National Tourist Guide licence number' : 'Tourist Guide licence number', 'e_glic', p.guide_licence_no, { col: 'col-md-4', cls: 'mono', max: 60 })}<div class="col-md-3"><label class="form-label help">Guide licence expiry <span class="text-muted">(if applicable)</span></label><input type="date" class="form-control form-control-sm" id="e_glicexp" value="${esc(p.guide_licence_expiry || '')}"> ${expPill(p.guide_licence_expiry, p.guide_licence_expired)}</div><div class="col-md-5">${isNew ? '' : fileBox('Guide licence copy', p.guide_licence_file_url, 'guide_licence')}</div>` : ''}
            ${c === 'JSD' ? '<div class="col-12 help">A JSD holds no guide licence; the guide-licence fields are not asked for.</div>' : ''}
        </div></div>`;
        const v = p.vehicle;
        const vehicle = !drives(c) ? (c === 'JSNTG' ? '<div class="ed-sec locked"><h6>Vehicle</h6><span class="help">A national tourist guide carries no vehicle on this master; a driver and vehicle are allocated to the tour separately.</span></div>' : '') : (isNew ? '<div class="ed-sec locked"><h6>Vehicle normally supplied</h6><span class="help">Save the record first, then put the vehicle on file.</span></div>' : `<div class="ed-sec"><h6>Vehicle normally supplied ${v ? `<span class="help">on file: <b class="mono">${esc(v.reg_no || '')}</b> ${v.vehicle_type === 'van' ? '<span class="roof">' + esc(v.roof_name || 'ROOF TYPE NOT SET') + '</span>' : ''}</span>` : '<span class="help">none on file</span>'}</h6><div class="row g-2" id="v_form">
            ${f('Registration number', 'v_reg', v && v.reg_no, { col: 'col-md-3', cls: 'mono', max: 40 })}
            ${f('Make', 'v_make', v && v.make, { col: 'col-md-3', ph: 'Toyota', max: 80 })}
            ${f('Model', 'v_model', v && v.model, { col: 'col-md-3', ph: 'KDH / Prius', max: 80 })}
            ${f('Year (if available)', 'v_year', v && v.year, { col: 'col-md-3', type: 'number' })}
            <div class="col-md-3"><label class="form-label help">Vehicle type</label><select class="form-select form-select-sm" id="v_type"><option value="">—</option>${Object.entries(META.vehicle_types || {}).map(([k, n]) => `<option value="${k}" ${v && v.vehicle_type === k ? 'selected' : ''}>${esc(n)}</option>`).join('')}</select></div>
            <div class="col-md-3" id="v_roof_wrap"><label class="form-label help">Van roof type <span style="color:var(--tf-red)">*</span></label><select class="form-select form-select-sm" id="v_roof"><option value="">— choose —</option>${Object.entries(META.roof_types || {}).map(([k, n]) => `<option value="${k}" ${v && v.roof_type === k ? 'selected' : ''}>${esc(n)}</option>`).join('')}</select></div>
            <div class="col-md-3"><label class="form-label help">Rate class <span class="text-muted">(Transport Rates master)</span></label><select class="form-select form-select-sm" id="v_class"><option value="">— from the vehicle type —</option>${(META.rate_classes || []).map(rc => `<option value="${esc(rc.code)}" ${v && v.rate_class === rc.code ? 'selected' : ''}>${esc(rc.name)}${rc.rate_km != null ? ' · LKR ' + num(rc.rate_km) + ' / km' : ' · no rate yet'}</option>`).join('')}</select><div class="help" id="v_general"></div></div>
            <div class="col-md-3"><label class="form-label help">Ownership</label><select class="form-select form-select-sm" id="v_own"><option value="">—</option>${Object.entries(META.ownership || {}).map(([k, n]) => `<option value="${k}" ${v && v.ownership === k ? 'selected' : ''}>${esc(n)}</option>`).join('')}</select></div>
            ${f('Seating capacity', 'v_seats', v && v.seats, { col: 'col-md-3', type: 'number' })}
            ${f('Max passengers with tour luggage', 'v_maxpax', v && v.max_pax_luggage, { col: 'col-md-3', type: 'number' })}
            <div class="col-md-3"><label class="form-label help">Insurance expiry</label><input type="date" class="form-control form-control-sm" id="v_ins" value="${esc(v && v.insurance_expiry || '')}"> ${v ? expPill(v.insurance_expiry, v.insurance_expired) : ''}</div>
            <div class="col-md-3"><label class="form-label help">Revenue licence expiry</label><input type="date" class="form-control form-control-sm" id="v_lic" value="${esc(v && v.licence_expiry || '')}"> ${v ? expPill(v.licence_expiry, v.licence_expired) : ''}</div>
            ${f('Vehicle note', 'v_note', v && v.note, { col: 'col-md-3', max: 400 })}
            <div class="col-md-12 d-flex gap-2 align-items-center flex-wrap"><button class="btn btn-light btn-sm" id="v_save">${v ? 'Save vehicle changes' : 'Put the vehicle on file'}</button>${v ? '<button class="btn btn-light btn-sm" id="v_new" title="The vehicle normally supplied changes: the one on file stays in the record\'s history">Replace with another vehicle…</button>' : ''}<span class="help">The registration number travels to the pack and the voucher.</span></div>
            ${v ? `<div class="col-md-6">${fileBox('Insurance copy', v.insurance_file_url, 'vehicle_insurance', v.insurance_file ? undefined : false)}</div><div class="col-md-6">${fileBox('Revenue licence copy', v.licence_file_url, 'vehicle_licence', v.licence_file ? undefined : false)}</div>
            <div class="col-md-12"><label class="form-label help">Photographs <span class="text-muted">— several, exterior and interior</span></label><div class="thumbs" id="v_photos">${(v.photos || []).map(ph => `<div class="ph"><img class="thumb" src="${ROOT}/${esc(ph.url)}" alt=""><button type="button" data-phrm="${ph.id}" title="Remove">×</button><div>${esc(ph.view)}</div></div>`).join('') || '<span class="help">none yet</span>'}</div>
                <div class="filebox mt-2"><select class="form-select form-select-sm" id="v_phview" style="width:auto"><option value="exterior">Exterior</option><option value="interior">Interior</option><option value="other">Other</option></select><input type="file" class="form-control form-control-sm" style="width:260px" id="v_phfile" accept=".jpg,.jpeg,.png,.webp" multiple><span class="help">up to ${META.upload_mb || 8} MB each</span></div></div>` : ''}
        </div></div>`);
        const eff = p.rate_effective || {};
        const gen = eff.general, spc = eff.special;
        const rates = !drives(c) ? '' : (isNew ? '' : `<div class="ed-sec"><h6>Transport rate — LKR per kilometre <span class="help">${eff.rate_km != null ? `in force for this driver: <b class="mono">LKR ${num(eff.rate_km)}</b> / km · ${eff.source === 'special' ? 'special rate (overrides the general)' : 'general rate of the vehicle class'}` : 'no rate in force — set the vehicle class, or a special rate'}</span></h6>
            <div class="row g-2">
              <div class="col-md-6"><div class="ratebox ${eff.source === 'general' ? 'on' : ''}"><div class="lbl">General rate <span class="help">— ${esc(eff.class_name || (v && v.rate_class) || 'vehicle class not set')}</span></div>
                ${gen ? `<div class="big mono">LKR ${num(gen.rate_km)} <small>/ km</small></div><div class="help">Transport Rates master, from ${esc(gen.valid_from)} · the normal rate for every ${esc(gen.class_name)}</div>` : `<div class="help">${v && v.rate_class ? 'No rate on the Transport Rates master for this class yet.' : 'Choose the vehicle class on the vehicle above; the general rate fills in from the Transport Rates master.'}</div>`}
                <div class="help"><a href="${ROOT}/master_files/transport_rates" target="_blank">Master Files → Transport Rates</a></div></div></div>
              <div class="col-md-6"><div class="ratebox ${eff.source === 'special' ? 'on' : ''}"><div class="lbl">Special rate <span class="help">— agreed with this driver only</span></div>
                ${spc ? `<div class="big mono">LKR ${num(spc.rate_km)} <small>/ km</small></div><div class="help">from ${esc(spc.effective_from)} · overrides the general rate for ${esc(p.nickname || p.name)} only</div>` : '<div class="help">None — the general rate applies automatically.</div>'}</div></div>
            </div>
            <div class="row g-2 align-items-end mt-1">${f('New special rate per km (LKR)', 'r_rate', '', { col: 'col-md-3', type: 'number', cls: 'mono' })}<div class="col-md-3"><label class="form-label help">Effective from</label><input type="date" class="form-control form-control-sm" id="r_from" value="${new Date().toISOString().slice(0, 10)}"></div>${f('Note', 'r_note', '', { col: 'col-md-4', ph: 'agreed with…', max: 255 })}<div class="col-md-2"><button class="btn btn-light btn-sm" id="r_add">Add special rate</button></div>
            <div class="col-12 help">A special rate belongs to this driver / vehicle arrangement and overrides the general rate for them only. A new rate is a new line: the earlier ones stay, and a tour copies the rate in force the day the person is engaged — a later change never moves it.</div>
            ${(p.rates || []).length ? `<div class="col-12"><table class="tbl"><thead><tr><th>From</th><th>Special rate / km</th><th>Note</th><th>Set by</th></tr></thead><tbody>${p.rates.map(r => `<tr><td class="mono">${esc(r.effective_from)}${r.in_force ? '' : ' <span class="pill pill-grey">future</span>'}</td><td class="mono">LKR ${num(r.rate_km)}</td><td>${esc(r.note || '')}</td><td class="help">${esc(r.set_by || '')} · ${esc(String(r.set_at || '').slice(0, 16))}</td></tr>`).join('')}</tbody></table></div>` : ''}</div></div>`);
        const br = p.bata_row, bt = p.bata_effective || {};
        const guide = c === 'JSNTG';
        const what = guide ? 'guide fee' : 'bata';
        const stdBox = (label, std, agreed, unit, src, noneText) => `<div class="row g-2">
              <div class="col-md-6"><div class="ratebox ${src === 'standard' ? 'on' : ''}"><div class="lbl">Standard ${label} <span class="help">— every ${esc(c || 'person')}</span></div>
                ${std ? `<div class="big mono">LKR ${num(std.v)} <small>${unit}</small></div><div class="help">in force from ${esc(std.from)} · set under Standard figures above</div>` : `<div class="help">${noneText}</div>`}</div></div>
              <div class="col-md-6"><div class="ratebox ${src === 'agreed' ? 'on' : ''}"><div class="lbl">Agreed ${label} <span class="help">— ${esc(p.nickname || p.name || 'this person')} only</span></div>
                ${agreed ? `<div class="big mono">LKR ${num(agreed.v)} <small>${unit}</small></div><div class="help">from ${esc(agreed.from)} · overrides the standard for ${esc(p.nickname || p.name)} only</div>` : `<div class="help">None — ${std ? 'the standard applies automatically.' : (guide && label !== what ? 'nothing is paid or settled for it until a figure is agreed or the standard confirmed.' : 'the Crew Bata master\'s figure for the role applies, as before.')}</div>`}</div></div>
            </div>`;
        const payHead = bt.bata != null ? `in force for ${esc(p.nickname || p.name)}: <b class="mono">LKR ${num(bt.bata)}</b> per day · ${bt.source === 'agreed' ? 'agreed (overrides the standard)' : 'the standard ' + what}` : (isNew ? '' : `no ${what} in force`);
        const pay = `<div class="ed-sec"><h6>${guide ? 'Guide fee and overnight allowance' : 'Bata'} <span class="help">${payHead}</span></h6>
            ${isNew ? `<div class="row g-2 align-items-end">${f(guide ? 'Agreed guide fee (per day, LKR)' : 'Agreed bata (per day, LKR)', 'e_bata', p.bata, { col: 'col-md-3', type: 'number', cls: 'mono', ph: 'standard if blank' })}${guide ? f('Agreed overnight allowance (per night, LKR)', 'e_overnight', p.overnight_bata, { col: 'col-md-3', type: 'number', cls: 'mono', ph: 'blank until agreed' }) : ''}<div class="col-12 help">Leave blank and the standard ${what} applies. A figure here is saved with the record as the first line of the agreed history, from today.</div></div>`
                : `${stdBox(what, bt.standard ? { v: bt.standard.bata, from: bt.standard.effective_from } : null, bt.agreed ? { v: bt.agreed.bata, from: bt.agreed.effective_from } : null, '/ day', bt.source, 'No standard ' + what + ' set.')}
                ${guide ? `<div class="mt-2">${stdBox('overnight allowance', bt.standard_overnight ? { v: bt.standard_overnight.overnight, from: bt.standard_overnight.effective_from } : null, bt.agreed_overnight ? { v: bt.agreed_overnight.overnight, from: bt.agreed_overnight.effective_from } : null, '/ night', bt.overnight_source, 'Not set — no figure is invented until the overnight allowance rule and amount are confirmed.')}</div><div class="help mt-1">The overnight allowance is kept apart from the guide fee: it is copied onto the tour and printed for information, and is never added into a voucher or settlement by itself.</div>` : ''}
                <div class="row g-2 align-items-end mt-1">${f(guide ? 'New agreed guide fee (per day, LKR)' : 'New agreed bata (per day, LKR)', 'bt_amount', '', { col: 'col-md-3', type: 'number', cls: 'mono' })}${guide ? f('Agreed overnight allowance (per night, LKR)', 'bt_overnight', '', { col: 'col-md-3', type: 'number', cls: 'mono' }) : ''}<div class="col-md-2"><label class="form-label help">Effective from</label><input type="date" class="form-control form-control-sm" id="bt_from" value="${new Date().toISOString().slice(0, 10)}"></div>${f('Note', 'bt_note', '', { col: guide ? 'col-md-2' : 'col-md-3', ph: 'agreed with…', max: 255 })}<div class="col-md-2"><button class="btn btn-light btn-sm" id="bt_add">Add</button></div>
                <div class="col-12 help">${guide ? 'The guide fee is paid per day worked; the overnight allowance per night away. ' : 'Paid per day worked, beside the rate per kilometre. '}An agreed figure belongs to this person and overrides the standard for them only. A new figure from a date is a new line — the earlier ones stay, and a tour copies the figure in force the day the person is engaged; a later change on either never moves it.</div>
                ${(p.batas || []).length ? `<div class="col-12"><table class="tbl"><thead><tr><th>From</th><th>${guide ? 'Agreed guide fee / day' : 'Agreed bata / day'}</th>${guide ? '<th>Agreed overnight / night</th>' : ''}<th>Note</th><th>Set by</th></tr></thead><tbody>${p.batas.map(b => `<tr><td class="mono">${esc(b.effective_from)}${b.in_force ? '' : ' <span class="pill pill-grey">future</span>'}</td><td class="mono">${b.bata != null ? 'LKR ' + num(b.bata) : '—'}</td>${guide ? `<td class="mono">${b.overnight != null ? 'LKR ' + num(b.overnight) : '—'}</td>` : ''}<td>${esc(b.note || '')}</td><td class="help">${esc(b.set_by || '')} · ${esc(String(b.set_at || '').slice(0, 16))}</td></tr>`).join('')}</tbody></table></div>` : ''}</div>`}
            <div class="row g-2 mt-1">
            <div class="col-md-6"><label class="form-label help">Also serves <span class="text-muted">(quotation roles outside the three categories — an English tour, say)</span></label><div class="codes" id="e_codes">${ROLES.filter(r => !['JSD', 'JSCG', 'JSNTG', 'GUIDE_JP'].includes(r.code) && (c === 'JSCG' || r.kind === (c === 'JSNTG' ? 'guide' : 'driver'))).map(r => `<label style="display:inline-flex;gap:4px;align-items:center;margin-right:10px;font-size:12px"><input type="checkbox" value="${esc(r.code)}" data-code ${(p.codes || []).includes(r.code) ? 'checked' : ''}> <span class="mono">${esc(r.code)}</span> <span class="help">${esc(r.name || '')}</span></label>`).join('') || '<span class="help">none</span>'}</div></div>
        </div></div>`;
        const bank = !CAN_BANK ? '<div class="ed-sec locked"><h6>Bank details</h6><span class="help">Kept for Accounts / Administration: the holders of "Driver &amp; Guide bank details" see and change them.</span></div>' : `<div class="ed-sec bank"><h6>Bank details <span class="help">Accounts / Administration only</span></h6><div class="row g-2">
            ${f('Bank name', 'b_bank', p.bank_name, { col: 'col-md-3', max: 120 })}
            ${f('Branch', 'b_branch', p.bank_branch, { col: 'col-md-3', max: 120 })}
            ${f('Account number', 'b_acc', p.bank_account_no, { col: 'col-md-3', cls: 'mono', max: 60 })}
            ${f('Account name exactly as registered with the bank', 'b_name', p.bank_account_name, { col: 'col-md-3', max: 190 })}
            <div class="col-md-3"><label class="help" style="display:inline-flex;gap:6px;align-items:center;margin-top:28px"><input type="checkbox" id="b_ver" ${p.bank_verified ? 'checked' : ''}> Bank details verified</label></div>
            <div class="col-md-3"><label class="form-label help">Verification date</label><input type="date" class="form-control form-control-sm" id="b_verat" value="${esc(p.bank_verified_at || '')}">${p.bank_verified_by ? `<div class="help">by ${esc(p.bank_verified_by)}</div>` : ''}</div>
            ${f('Payment remarks', 'b_rem', p.payment_remarks, { col: 'col-md-6', max: 400 })}
            <div class="col-12 help">At payment, Accounts see the nickname and the account name side by side${p.nickname && p.bank_account_name ? `: <b>“${esc(p.nickname)}” → ${esc(p.bank_account_name)}</b>` : ''}.</div>
        </div></div>`;
        const hist = (p.history || []).length ? `<div class="ed-sec"><h6>History</h6><div class="hist">${p.history.map(h => `<div><span class="help">${esc(String(h.at || '').slice(0, 16))} · ${esc(h.by_name || '')}</span> — ${esc(h.summary)}</div>`).join('')}</div></div>` : '';
        $('e_body').innerHTML = warn + personal + garage + licences + vehicle + rates + pay + bank + hist + `<div class="sticky-actions"><button class="btn btn-primary btn-sm" id="e_save"><i class="bi bi-check2"></i> ${isNew ? 'Save' : 'Save changes'}</button><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>${p.migrated ? `<span class="help">Brought over from the driver registration (${esc(p.migrated_from || '')}); the registration stays the accounting record.</span>` : ''}</div>`;
        wireEditor();
    }
    function wireEditor() {
        const p = P;
        $('e_cat').addEventListener('change', () => { P.category = $('e_cat').value || null; collect(); renderEditor(); });
        const gs = $('e_gsame'); if (gs) { const sync = () => { $('e_garage').disabled = gs.checked; if (gs.checked) $('e_garage').value = ''; }; gs.addEventListener('change', sync); sync(); }
        const vt = $('v_type'), vr = $('v_roof'), vc = $('v_class');
        if (vt) {
            // the rate class follows the type (and the roof, and the seats) unless a person chose one; the general rate shows beside it
            const suggest = () => { const t = vt.value, r = vr.value, seats = Number($('v_seats').value || 0); const have = (META.rate_classes || []).map(x => x.code);
                const want = t === 'car' ? ['SEDAN'] : t === 'van' ? (r === 'high' ? ['HIROOF', 'VAN'] : ['VAN', 'HIROOF']) : t === 'minibus' ? ['MINICOACH'] : t === 'bus' ? (seats > 45 ? ['BUS55', 'BUS45', 'COACH33'] : seats > 33 ? ['BUS45', 'COACH33', 'BUS55'] : ['COACH33', 'BUS45', 'BUS55']) : [];
                return want.find(w => have.includes(w)) || ''; };
            const general = () => { const rc = (META.rate_classes || []).find(x => x.code === vc.value); $('v_general').innerHTML = !vc.value ? 'General rate: choose the class' : (rc && rc.rate_km != null ? `General rate <b class="mono">LKR ${num(rc.rate_km)}</b> / km from ${esc(rc.valid_from)}` : 'No general rate on the Transport Rates master for this class yet'); };
            const roof = () => { $('v_roof_wrap').style.display = vt.value === 'van' ? '' : 'none'; };
            const follow = () => { const sg = suggest(); if (sg && (!vc.value || vc.dataset.auto === '1')) { vc.value = sg; vc.dataset.auto = '1'; } general(); };
            vt.addEventListener('change', () => { roof(); follow(); }); vr.addEventListener('change', follow); $('v_seats').addEventListener('input', follow);
            vc.addEventListener('change', () => { vc.dataset.auto = vc.value ? '0' : '1'; general(); });
            roof(); if (!vc.value) { vc.dataset.auto = '1'; follow(); } else { general(); }
        }
        $('bt_add')?.addEventListener('click', async () => {
            const j = await post('/bata/' + p.id, { bata: $('bt_amount').value, overnight: $('bt_overnight') ? $('bt_overnight').value : null, effective_from: $('bt_from').value, note: $('bt_note').value });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); await refreshRecord(); load();
        });
        $('e_confirm')?.addEventListener('click', async () => {
            const j = await post('/confirm/' + p.id, { category: $('e_cat').value });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); P = j.data; await refreshRecord(); load();
        });
        $('e_save').addEventListener('click', async () => {
            const body = collect();
            const j = await post('/save' + (p.id ? '/' + p.id : ''), body);
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message);
            P = j.data; await refreshRecord(); load();
        });
        $('v_save')?.addEventListener('click', async () => {
            const body = { reg_no: $('v_reg').value, make: $('v_make').value, model: $('v_model').value, year: $('v_year').value, vehicle_type: $('v_type').value, roof_type: $('v_roof').value, rate_class: $('v_class').value, rate_class_cleared: $('v_class').value ? 0 : 1, ownership: $('v_own').value, seats: $('v_seats').value, max_pax_luggage: $('v_maxpax').value, insurance_expiry: $('v_ins').value, licence_expiry: $('v_lic').value, note: $('v_note').value };
            const j = await post('/vehicle/' + p.id + (p.vehicle ? '/' + p.vehicle.id : ''), body);
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); await refreshRecord(); load();
        });
        $('v_new')?.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'Replace the vehicle?', text: 'The vehicle on file stays in the record\'s history; the form clears for the new one. Fill it in and press "Put the vehicle on file".', showCancelButton: true, confirmButtonText: 'Replace' });
            if (!r.isConfirmed) return;
            P.vehicle = null; renderEditor();
            $('v_save').textContent = 'Put the vehicle on file';
            $('v_save').onclick = null;
            $('v_save').addEventListener('click', async () => {
                const body = { reg_no: $('v_reg').value, make: $('v_make').value, model: $('v_model').value, year: $('v_year').value, vehicle_type: $('v_type').value, roof_type: $('v_roof').value, rate_class: $('v_class').value, rate_class_cleared: $('v_class').value ? 0 : 1, ownership: $('v_own').value, seats: $('v_seats').value, max_pax_luggage: $('v_maxpax').value, insurance_expiry: $('v_ins').value, licence_expiry: $('v_lic').value, note: $('v_note').value };
                const j = await post('/vehicle/' + p.id, body);
                if (j.status !== 200) { toast('error', j.message); return; }
                toast('success', j.message); await refreshRecord(); load();
            }, { once: true });
        });
        $('r_add')?.addEventListener('click', async () => {
            const j = await post('/rate/' + p.id, { rate_km: $('r_rate').value, effective_from: $('r_from').value, note: $('r_note').value });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); await refreshRecord(); load();
        });
        document.querySelectorAll('[data-up]').forEach(inp => inp.addEventListener('change', async () => {
            if (!inp.files.length) return;
            const j = await upload('/upload/' + p.id + '/' + inp.dataset.up, inp.files[0], { vehicle_id: p.vehicle ? p.vehicle.id : null });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); await refreshRecord(); load();
        }));
        $('v_phfile')?.addEventListener('change', async () => {
            const files = [...$('v_phfile').files]; if (!files.length) return;
            for (const fl of files) { const j = await upload('/upload/' + p.id + '/vehicle_photo', fl, { view: $('v_phview').value, vehicle_id: p.vehicle ? p.vehicle.id : null }); if (j.status !== 200) { toast('error', j.message); break; } }
            await refreshRecord(); load();
        });
        document.querySelectorAll('[data-phrm]').forEach(b => b.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'Remove this photograph?', showCancelButton: true, confirmButtonText: 'Remove' });
            if (!r.isConfirmed) return;
            const j = await post('/photo/' + p.id + '/' + b.dataset.phrm + '/remove', {});
            if (j.status !== 200) { toast('error', j.message); return; }
            await refreshRecord();
        }));
    }
    function collect() {
        const g = id => { const el = $(id); return el ? el.value : undefined; };
        const body = {
            name: g('e_name'), nickname: g('e_nick'), category: g('e_cat') || null, kind: P.kind, status: g('e_status'), supplier_acc: g('e_acc'),
            id_type: g('e_idtype'), nic: g('e_nic'), mobile: g('e_mobile'), whatsapp: g('e_wa'), email: g('e_email'), languages: g('e_langs'), home_address: g('e_home'), japanese_note: g('e_jp'), note: g('e_note'),
            garage_same: $('e_gsame') ? $('e_gsame').checked : false, garage_address: g('e_garage'),
            licence_no: g('e_lic'), licence_expiry: g('e_licexp'), guide_licence_no: g('e_glic'), guide_licence_expiry: g('e_glicexp'),
            bata: g('e_bata'), overnight_bata: g('e_overnight'), vehicle_reg: P.vehicle ? P.vehicle.reg_no : P.vehicle_reg,
            role_codes: [...document.querySelectorAll('#e_codes [data-code]:checked')].map(c => c.value),
            category_confirmed: P.id ? (P.category_confirmed ? 1 : 0) : 1,
        };
        if (CAN_BANK) Object.assign(body, { bank_name: g('b_bank'), bank_branch: g('b_branch'), bank_account_no: g('b_acc'), bank_account_name: g('b_name'), bank_verified: $('b_ver') ? $('b_ver').checked : false, bank_verified_at: g('b_verat'), payment_remarks: g('b_rem') });
        // keep what was typed while the editor re-renders
        Object.assign(P, { name: body.name, nickname: body.nickname, status: body.status, supplier_acc: body.supplier_acc, id_type: body.id_type, nic: body.nic, mobile: body.mobile, whatsapp: body.whatsapp, email: body.email, languages: body.languages, home_address: body.home_address, japanese_note: body.japanese_note, note: body.note, garage_same: body.garage_same, garage_address: body.garage_address, licence_no: body.licence_no, licence_expiry: body.licence_expiry, guide_licence_no: body.guide_licence_no, guide_licence_expiry: body.guide_licence_expiry, bata: body.bata, overnight_bata: body.overnight_bata });
        return body;
    }
    async function refreshRecord() {
        if (!P || !P.id) return;
        const j = await api('/person/' + P.id);
        if (j.status === 200) { P = j.data; renderEditor(); }
    }

    document.querySelectorAll('#tabs .tab').forEach(t => t.addEventListener('click', () => { TAB = t.dataset.tab; renderTabs(); render(); }));
    $('l_q').addEventListener('input', render);
    $('l_arch').addEventListener('change', load);
    // tf-refresh-r1: the record open in the window is in the address (?person=N) — a refresh opens it again, read fresh; Back closes it
    document.getElementById('m_edit').addEventListener('hidden.bs.modal', () => { if (window.TfView && TfView.param('person')) TfView.setParams({ person: null }, { push: true }); });
    window.addEventListener('popstate', () => {
        const v = Number(new URLSearchParams(location.search).get('person') || 0);
        const m = document.getElementById('m_edit');
        if (v) { openRecord(v); } else if (m.classList.contains('show')) { bootstrap.Modal.getInstance(m)?.hide(); }
    });
    Promise.resolve(load()).then(() => { const v = Number(new URLSearchParams(location.search).get('person') || 0); if (v) openRecord(v); });
})();
</script>
</body>
</html>
