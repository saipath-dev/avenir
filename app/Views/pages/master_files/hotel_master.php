<?= view('layouts/head') ?>
<style>
    /* ---------- Hotel Incomplete Work (same tokens as the Hotel Rates panel) ---------- */
    .hr2 { --hr-navy: #1e3553; --hr-navy-2: #2c4a73; --hr-teal: #0f8b7e; --hr-teal-soft: #e3f4f1; --hr-amber: #b7791f; --hr-amber-soft: #fdf3e1; --hr-red: #b23a32; --hr-red-soft: #fbe9e7; --hr-green: #1f8a4c; --hr-green-soft: #e4f4ea; --hr-blue-soft: #e6eef8; --hr-ink: #1d2733; --hr-muted: #5f6f80; --hr-line: #dfe5ec; --hr-bg: #f4f6f9; --hr-card: #ffffff; --hr-radius: 10px; color: var(--hr-ink); }
    .hr2 .hr-card { background: var(--hr-card); border: 1px solid var(--hr-line); border-radius: var(--hr-radius); box-shadow: 0 1px 2px rgba(16,24,40,.04); }
    .hr2 .hr-card + .hr-card { margin-top: 14px; }
    .hr2 .hr-card-b { padding: 14px 16px; }
    .hr2 .hr-title { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 14px; }
    .hr2 .hr-title h4 { margin: 0; font-weight: 600; color: var(--hr-navy); }
    .hr2 .hr-title .sub { color: var(--hr-muted); font-size: 12.5px; max-width: 760px; }
    .hr2 .hr-toolbar { margin-left: auto; display: flex; flex-wrap: wrap; gap: 6px; }
    .hr2 .btn { white-space: nowrap; border-radius: 8px; font-weight: 500; font-size: 12.5px; padding: 6px 12px; line-height: 1.4; }
    .hr2 .btn-sm { padding: 4px 9px; font-size: 12px; }
    .hr2 .btn-primary { background: var(--hr-navy); border-color: var(--hr-navy); color: #fff; }
    .hr2 .btn-primary:hover { background: var(--hr-navy-2); border-color: var(--hr-navy-2); }
    .hr2 .btn-light { background: #fff; border: 1px solid var(--hr-line); color: var(--hr-ink); }
    .hr2 .btn-light:hover { background: var(--hr-bg); }
    .hr2 .btn-danger-soft { background: var(--hr-red-soft); border-color: transparent; color: var(--hr-red); }
    .hr2 .btn-warn-soft { background: var(--hr-amber-soft); border-color: transparent; color: var(--hr-amber); }
    .hr2 .btn-green-soft { background: var(--hr-green-soft); border-color: transparent; color: var(--hr-green); }
    .hr2 .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; letter-spacing: .02em; line-height: 1.6; white-space: nowrap; }
    .hr2 .pill-green { background: var(--hr-green-soft); color: var(--hr-green); }
    .hr2 .pill-amber { background: var(--hr-amber-soft); color: var(--hr-amber); }
    .hr2 .pill-red { background: var(--hr-red-soft); color: var(--hr-red); }
    .hr2 .pill-blue { background: var(--hr-blue-soft); color: var(--hr-navy); }
    .hr2 .pill-grey { background: #eef1f5; color: var(--hr-muted); }
    .hr2 .mono { font-family: Consolas, Menlo, monospace; font-size: 12px; }
    .hr2 .hcode { display: inline-block; font-family: Consolas, Menlo, monospace; font-size: 11.5px; font-weight: 700; letter-spacing: .03em; padding: 1px 7px; border-radius: 6px; background: var(--hr-blue-soft); color: var(--hr-navy); white-space: nowrap; }
    .hr2 .pick { max-height: 260px; overflow: auto; border: 1px solid var(--hr-line); border-radius: 8px; margin-top: 6px; }
    .hr2 .pick button { display: block; width: 100%; text-align: left; border: 0; border-bottom: 1px solid var(--hr-line); background: #fff; padding: 7px 10px; font-size: 12.5px; }
    .hr2 .pick button:last-child { border-bottom: 0; }
    .hr2 .pick button:hover, .hr2 .pick button.on { background: var(--hr-bg); }
    .hr2 .pick .sub { font-size: 11.5px; color: var(--hr-muted); }
    .hr2 .chosen { border: 1px solid var(--hr-line); border-radius: 8px; padding: 8px 10px; margin-top: 8px; background: var(--hr-bg); font-size: 12.5px; }
    .hr2 .help { font-size: 12px; color: var(--hr-muted); }
    /* KPI tiles */
    .hr2 .kpis { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; margin-bottom: 14px; }
    .hr2 .kpi { background: var(--hr-card); border: 1px solid var(--hr-line); border-radius: var(--hr-radius); padding: 10px 12px; cursor: pointer; border-left: 4px solid var(--hr-line); }
    .hr2 .kpi:hover { background: var(--hr-bg); }
    .hr2 .kpi.on { outline: 2px solid var(--hr-navy); }
    .hr2 .kpi.red { border-left-color: var(--hr-red); } .hr2 .kpi.amber { border-left-color: var(--hr-amber); } .hr2 .kpi.blue { border-left-color: var(--hr-navy); } .hr2 .kpi.ok { border-left-color: var(--hr-green); }
    .hr2 .kpi .n { font-size: 22px; font-weight: 700; color: var(--hr-navy); line-height: 1.1; }
    .hr2 .kpi .l { font-size: 12px; color: var(--hr-muted); margin-top: 2px; }
    /* filters */
    .hr2 .filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .hr2 .filters .form-control, .hr2 .filters .form-select { font-size: 12.5px; border-radius: 8px; }
    /* table */
    .hr2 table.wk { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .hr2 table.wk th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--hr-muted); padding: 8px 10px; border-bottom: 1px solid var(--hr-line); white-space: nowrap; }
    .hr2 table.wk td { padding: 9px 10px; border-bottom: 1px solid #eef1f5; vertical-align: top; }
    .hr2 table.wk tr.item:hover td { background: #fbfcfd; }
    .hr2 table.wk tr.done td { opacity: .6; }
    .hr2 table.wk tr.ignored td { opacity: .75; font-style: italic; }
    .hr2 table.wk tr.grp td { background: var(--hr-bg); border-top: 2px solid var(--hr-line); padding: 8px 12px; font-size: 12.5px; }  /* one heading row per kind (6 Oct 2026) */
    .hr2 table.wk tr.grp .pill { font-size: 11.5px; }
    .hr2 .dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
    .hr2 .dot.red { background: var(--hr-red); } .hr2 .dot.amber { background: var(--hr-amber); } .hr2 .dot.blue { background: var(--hr-navy); }
    .hr2 .ttl { font-weight: 600; color: var(--hr-ink); }
    .hr2 .dtl { color: var(--hr-muted); font-size: 12px; margin-top: 3px; max-width: 900px; white-space: pre-line; }
    .hr2 .meta { font-size: 11.5px; color: var(--hr-muted); margin-top: 4px; display: flex; flex-wrap: wrap; gap: 10px; }
    .hr2 .meta a { color: var(--hr-navy); text-decoration: none; }
    .hr2 .meta a:hover { text-decoration: underline; }
    .hr2 .nights { font-family: Consolas, Menlo, monospace; font-size: 11.5px; color: var(--hr-ink); margin-top: 4px; }
    .hr2 .note { font-size: 11.5px; color: var(--hr-navy); margin-top: 4px; white-space: pre-line; border-left: 2px solid var(--hr-line); padding-left: 8px; }
    .hr2 .acts { display: flex; flex-direction: column; gap: 4px; align-items: stretch; min-width: 128px; }
    .hr2 .empty { padding: 26px 16px; text-align: center; color: var(--hr-muted); }
    .hr2 .empty b { color: var(--hr-green); }
    .hr2 .modal-content { border-radius: 12px; }
    .hr2 textarea.form-control { font-size: 12.5px; }
    .hr2 .not-installed { padding: 14px 16px; border-radius: 8px; background: var(--hr-amber-soft); color: var(--hr-amber); font-size: 13px; }
</style>
<style>
    /* ---------- Hotel Master — add, edit or remove hotels (hotel-dup-r1, 27 Oct 2026) ---------- */
    .hr2 table.hm { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .hr2 table.hm th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--hr-muted); padding: 8px 10px; border-bottom: 1px solid var(--hr-line); white-space: nowrap; }
    .hr2 table.hm td { padding: 8px 10px; border-bottom: 1px solid #eef1f5; vertical-align: middle; }
    .hr2 table.hm tr:hover td { background: #fbfcfd; }
    .hr2 table.hm tr.inactive td, .hr2 table.hm tr.merged td { color: var(--hr-muted); }
    .hr2 table.hm td.num, .hr2 table.hm th.num { text-align: right; font-variant-numeric: tabular-nums; }
    .hr2 .dep-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 6px 14px; font-size: 12.5px; }
    .hr2 .dep-grid b { color: var(--hr-navy); }
    .hr2 .dep-grid .zero { color: var(--hr-muted); }
    .hr2 .hm-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--hr-line); margin-bottom: 12px; flex-wrap: wrap; }
    .hr2 .hm-tabs button { border: 0; background: none; padding: 8px 12px; font-size: 12.5px; color: var(--hr-muted); border-bottom: 2px solid transparent; }
    .hr2 .hm-tabs button.on { color: var(--hr-navy); border-bottom-color: var(--hr-navy); font-weight: 600; }
    .hr2 .danger-zone { border: 1px solid var(--hr-red-soft); border-radius: 8px; padding: 10px 12px; background: #fff; height: 100%; }
    .hr2 .danger-zone h6 { color: var(--hr-red); font-size: 12.5px; margin-bottom: 6px; }
    .hr2 .form-label { font-size: 11.5px; text-transform: uppercase; letter-spacing: .03em; color: var(--hr-muted); margin-bottom: 2px; }
    .hr2 .hist { font-size: 12px; max-height: 320px; overflow: auto; }
    .hr2 .hist div { padding: 5px 0; border-bottom: 1px solid #eef1f5; }
    .hr2 .hist .mono { color: var(--hr-muted); }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content hr2" id="hr2">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>

                <div class="hr-title">
                    <div>
                        <h4>Hotel Master — add, edit or remove hotels</h4>
                        <div class="sub">Every hotel on file, by code and name — the single source of truth for hotel names. Open a hotel to correct its name or details, to remove a genuine duplicate (merge), to archive a hotel with history, or to delete a wrong entry that nothing refers to. Each removal shows what depends on the record first and needs the typed word; every action is logged.<?php if (empty($can_manage)): ?> <b>Your group may look but not change: the changes need the Tour Desk Manage privilege (Management).</b><?php endif; ?></div>
                    </div>
                    <div class="hr-toolbar">
                        <a class="btn btn-light btn-sm" href="<?= base_url($panel_url ?? 'master_files/hotel_rate_v2') ?>"><i class="bi bi-cash-stack"></i> Hotel Rates</a>
                        <?php if (!empty($is_sysadmin)): ?><button class="btn btn-light btn-sm" id="btn_owners" title="Which account(s) may add or delete a hotel"><i class="bi bi-person-lock"></i> Who may add / delete</button><?php endif; ?>
                        <?php if (!empty($can_add_delete)): ?><a class="btn btn-primary btn-sm" href="<?= base_url($new_supplier_url ?? 'master_files/supplier?cat=1') ?>" title="A hotel is born as a hotel supplier: the account number and the HTL code are given together, and a similar name is warned before it is created"><i class="bi bi-plus-lg"></i> Add hotel</a><?php endif; ?>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="hr-card"><div class="hr-card-b not-installed"><b><i class="bi bi-exclamation-triangle"></i> The Hotel Rates tables are not at the current version.</b> <span class="help">Open Hotel Rates as System Administrator and press Install now.</span></div></div>
                <?php else: ?>

                <div class="hr-card">
                    <div class="hr-card-b">
                        <div class="filters">
                            <input class="form-control form-control-sm" id="f_q" placeholder="Hotel code, name, location, supplier account…" style="width:300px" autocomplete="off">
                            <select class="form-select form-select-sm" id="f_status" style="width:auto">
                                <option value="active">Active</option>
                                <option value="inactive">Archived</option>
                                <option value="merged">Merged (duplicates removed)</option>
                                <option value="all">All</option>
                            </select>
                            <label class="help"><input type="checkbox" id="f_dups"> only hotels with a similar name on file</label>
                            <button class="btn btn-light btn-sm" id="f_clear">Clear</button>
                            <span class="help ms-auto" id="count"></span>
                        </div>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="hm">
                            <thead><tr><th>Code</th><th>Hotel</th><th>Location</th><th>Supplier account</th><th>Status</th><th class="num">Approved plans</th><th class="num">Drafts</th><th class="num">Nights / yr</th><th class="num">Nights all time</th><th></th></tr></thead>
                            <tbody id="rows"><tr><td colspan="10" class="empty">Loading…</td></tr></tbody>
                        </table>
                    </div>
                </div>

                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<!-- hotel modal -->
<div class="modal fade hr2" id="m_hotel" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mh_title">Hotel</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="hm-tabs"><button class="on" data-tab="details">Details</button><button data-tab="deps">Dependencies</button><button data-tab="rooms">Room types</button><button data-tab="history">History</button><button data-tab="actions" id="tab_actions_btn">Remove / archive / delete</button></div>
        <div id="tab_details">
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label">Hotel name</label><input class="form-control form-control-sm" id="h_name"><div class="help" id="h_name_help"></div></div>
                <div class="col-md-3"><label class="form-label">Hotel code</label><div class="form-control form-control-sm" style="background:#f8f9fb" id="h_code"></div></div>
                <div class="col-md-3"><label class="form-label">Supplier account(s)</label><div class="form-control form-control-sm mono" style="background:#f8f9fb" id="h_accs"></div></div>
                <div class="col-md-3"><label class="form-label">Location</label><input class="form-control form-control-sm" id="h_location" list="dl_loc"><datalist id="dl_loc"></datalist></div>
                <div class="col-md-3"><label class="form-label">Chain / management company</label><input class="form-control form-control-sm" id="h_chain"></div>
                <div class="col-md-3"><label class="form-label">Star category</label><select class="form-select form-select-sm" id="h_star"><option value="">— not set —</option><option>1 star</option><option>2 star</option><option>3 star</option><option>4 star</option><option>5 star</option><option>Unrated</option></select></div>
                <div class="col-md-3"><label class="form-label">Hotel grade</label><select class="form-select form-select-sm" id="h_grade"><option value="">— not set —</option><option>Standard</option><option>Superior</option><option>Deluxe</option><option>Luxury</option></select></div>
                <div class="col-md-3"><label class="form-label">Website</label><input class="form-control form-control-sm" id="h_website"></div>
                <div class="col-md-6"><label class="form-label">Address</label><input class="form-control form-control-sm" id="h_address"></div>
                <div class="col-md-3"><label class="form-label">Reservations e-mail</label><input class="form-control form-control-sm" id="h_res_email"></div>
                <div class="col-md-3"><label class="form-label">Reservations phone</label><input class="form-control form-control-sm" id="h_res_phone"></div>
                <div class="col-md-3"><label class="form-label">Accounts e-mail</label><input class="form-control form-control-sm" id="h_acc_email"></div>
                <div class="col-md-3"><label class="form-label">Sales contact</label><input class="form-control form-control-sm" id="h_sales"></div>
                <div class="col-md-3"><label class="form-label">Phone</label><input class="form-control form-control-sm" id="h_phone"></div>
                <div class="col-md-3"><label class="form-label">Status</label><div class="form-control form-control-sm" style="background:#f8f9fb" id="h_status"></div></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" id="h_notes" rows="2"></textarea></div>
            </div>
            <div class="mt-3 d-flex gap-2 align-items-center flex-wrap"><button class="btn btn-primary btn-sm" id="h_save"><i class="bi bi-save"></i> Save changes</button><a class="btn btn-light btn-sm" id="h_open_rates" target="_blank"><i class="bi bi-cash-stack"></i> Open in Hotel Rates</a><span class="help">A name change goes to the Hotel Master, the supplier record and the ledger name together, and is logged.</span></div>
            <div class="mt-3" id="h_similar"></div>
        </div>
        <div id="tab_deps" style="display:none"><div class="help mb-2">What refers to this record. A hotel with any history is archived or merged — never deleted.</div><div class="dep-grid" id="deps_grid"></div><div class="mt-2" id="deps_verdict"></div></div>
        <div id="tab_rooms" style="display:none"><div class="help mb-2">Room types as the hotel writes them. A room type that no rate, supplement or night refers to can be deleted here (Management); one in use is set inactive on the Hotel Rates screen.</div><div id="rooms_box"></div></div>
        <div id="tab_history" style="display:none"><div class="hist" id="hist_box"></div></div>
        <div id="tab_actions" style="display:none">
            <div class="row g-3">
                <div class="col-md-4"><div class="danger-zone"><h6><i class="bi bi-intersect"></i> Remove duplicate (merge)</h6><div class="help mb-2">This record is a second copy of a hotel that already exists. Its plans, room types and supplier code move under the hotel you keep; its tour nights, payments and vouchers stay on their own account; the copy is hidden from the pickers. Undoable.</div><button class="btn btn-warn-soft btn-sm" id="a_merge">Remove duplicate…</button></div></div>
                <div class="col-md-4"><div class="danger-zone"><h6><i class="bi bi-archive"></i> Archive</h6><div class="help mb-2">The hotel has history (nights, payments, rates) but is no longer used. It leaves the hotel pickers; everything on file stays exactly as it is. Restorable.</div><button class="btn btn-warn-soft btn-sm" id="a_archive">Archive…</button> <button class="btn btn-light btn-sm" id="a_restore">Restore</button></div></div>
                <div class="col-md-4"><div class="danger-zone"><h6><i class="bi bi-trash"></i> Delete incorrect entry</h6><div class="help mb-2">Only a record with no booking or financial history — no quotation nights, bookings, vouchers, payments, inquiries. Its own rate plans and old-screen rows can be included explicitly. Everything removed goes to the audit log first; the code number is never reused. Reserved to the owner account: reason + password.</div><button class="btn btn-danger-soft btn-sm" id="a_delete">Delete…</button> <span class="help" id="a_delete_note"></span></div></div>
                <div class="col-12" id="a_unmerge_wrap" style="display:none"><div class="danger-zone"><h6><i class="bi bi-arrow-counterclockwise"></i> Undo merge</h6><div class="help mb-2">This record was folded into another hotel. Undoing puts it back with its supplier code, plans and room types.</div><button class="btn btn-light btn-sm" id="a_unmerge">Undo merge…</button></div></div>
            </div>
        </div>
    </div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($master_url ?? 'master_files/hotel_master') ?>';
    const PANEL = '<?= base_url($panel_url ?? 'master_files/hotel_rate_v2') ?>';
    const CAN_MANAGE = <?= !empty($can_manage) ? 'true' : 'false' ?>;
    const CAN_ADD_DELETE = <?= !empty($can_add_delete) ? 'true' : 'false' ?>;
    const OWNERS = <?= json_encode($owners ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const PRESELECT = <?= (int) ($preselect ?? 0) ?>;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    // focus:false — the confirmation boxes (SweetAlert) open above this modal; Bootstrap's focus trap would otherwise
    // pull the keyboard back to the modal's Close button on every keystroke, so the typed word could never be entered
    const showModal = sel => { const el = document.querySelector(sel); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el, { focus: false }) : new bootstrap.Modal(el, { focus: false })).show(); };
    const hideModal = sel => { const el = document.querySelector(sel); const inst = bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(el) : null; if (inst) inst.hide(); };
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 3200 : undefined, showConfirmButton: icon !== 'success' });
    async function api(path, opts) { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); if (r.status === 401 || r.redirected) { location.reload(); throw new Error('Signed out'); } return r.json(); }
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const pill = (cls, t) => `<span class="pill pill-${cls}">${esc(t)}</span>`;
    const S = { hotels: [], cur: null, q: '', status: 'active', dups: false, simKeys: new Set() };
    if (!INSTALLED) { return; }

    const nameKey = n => String(n || '').toLowerCase().replace(/&/g, ' and ').replace(/[^a-z0-9 ]+/g, ' ').replace(/\b(the|hotel|hotels|resort|resorts|and|spa|by|pvt|ltd)\b/g, ' ').replace(/\s+/g, '');
    function similarKeys() {
        // the server's "same name / one contains the other" rule, applied client-side for the list filter (approximate)
        const keys = S.hotels.filter(h => h.status !== 'merged').map(h => ({ id: h.id, k: nameKey(h.name) }));
        const out = new Set();
        for (let i = 0; i < keys.length; i++) for (let j = i + 1; j < keys.length; j++) {
            const a = keys[i].k, b = keys[j].k; if (a.length < 5 || b.length < 5) continue;
            if (a === b || (Math.abs(a.length - b.length) <= 3 && (a.includes(b) || b.includes(a)))) { out.add(keys[i].id); out.add(keys[j].id); }
        }
        return out;
    }
    async function load() {
        const j = await api('/list'); if (j.status !== 200) { toast('error', j.message); return; }
        S.hotels = j.data.map(h => Object.assign(h, { id: Number(h.id) })); S.simKeys = similarKeys(); render();
    }
    function render() {
        const q = S.q.trim().toLowerCase();
        const codeNo = (q.match(/^(?:htl[\s-]*|h)?0*(\d{1,6})$/i) || [])[1];
        const list = S.hotels.filter(h => {
            if (S.status !== 'all' && h.status !== S.status) return false;
            if (S.dups && !S.simKeys.has(h.id)) return false;
            if (q && !(`${h.name} ${h.location_name || ''} ${h.chain || ''} ${h.primary_sup_acc_no || ''} ${h.hotel_code || ''}`.toLowerCase().includes(q)) && !(codeNo && h.hotel_code && parseInt(String(h.hotel_code).replace(/\D/g, ''), 10) === parseInt(codeNo, 10))) return false;
            return true;
        });
        document.getElementById('count').textContent = `${list.length} of ${S.hotels.length} hotel(s)`;
        const st = h => h.status === 'active' ? pill('green', 'active') : h.status === 'inactive' ? pill('amber', 'archived') : pill('grey', 'merged into ' + (h.merged_into_code || '') + ' ' + (h.merged_into_name || ''));
        document.getElementById('rows').innerHTML = list.map(h => `<tr class="${esc(h.status)}" data-id="${h.id}">
            <td><span class="hcode">${esc(h.hotel_code || '')}</span></td>
            <td><b>${esc(h.name)}</b>${S.simKeys.has(h.id) ? ' ' + pill('amber', 'similar name on file') : ''}${h.chain ? `<div class="help">${esc(h.chain)}</div>` : ''}</td>
            <td>${esc(h.location_name || '')}</td><td class="mono">${esc(h.primary_sup_acc_no || '')}</td><td>${st(h)}</td>
            <td class="num">${h.plans_approved}</td><td class="num">${h.plans_draft}</td><td class="num">${h.nights_last_year}</td><td class="num">${h.nights_all}</td>
            <td class="text-nowrap text-end"><button class="btn btn-primary btn-sm" data-open="${h.id}" title="Open the hotel: name, details, dependencies, room types, history"><i class="bi bi-pencil"></i> ${CAN_MANAGE ? 'Edit' : 'View'}</button>${CAN_MANAGE ? ` <button class="btn btn-sm" style="background:var(--hr-red);border-color:var(--hr-red);color:#fff" data-remove="${h.id}" title="Opens the Remove / archive / delete options for this hotel — nothing happens until a typed confirmation"><i class="bi bi-x-circle"></i> Remove</button>` : ''}</td></tr>`).join('') || '<tr><td colspan="10" class="empty">No hotel matches.</td></tr>';
        document.querySelectorAll('[data-open]').forEach(b => b.addEventListener('click', () => openHotel(Number(b.dataset.open))));
        // Remove opens the same dialog on its Remove / archive / delete tab: merge, archive or delete, each with the dependency review and the typed word
        document.querySelectorAll('[data-remove]').forEach(b => b.addEventListener('click', () => openHotel(Number(b.dataset.remove), 'actions')));
    }
    document.getElementById('f_q').addEventListener('input', e => { S.q = e.target.value; render(); });
    document.getElementById('f_status').addEventListener('change', e => { S.status = e.target.value; render(); });
    document.getElementById('f_dups').addEventListener('change', e => { S.dups = e.target.checked; render(); });
    document.getElementById('f_clear').addEventListener('click', () => { S.q = ''; S.status = 'active'; S.dups = false; document.getElementById('f_q').value = ''; document.getElementById('f_status').value = 'active'; document.getElementById('f_dups').checked = false; render(); });

    // ---------------- one hotel ----------------
    const tabs = document.querySelectorAll('.hm-tabs button');
    tabs.forEach(b => b.addEventListener('click', () => showTab(b.dataset.tab)));
    function showTab(t) { tabs.forEach(b => b.classList.toggle('on', b.dataset.tab === t)); ['details', 'deps', 'rooms', 'history', 'actions'].forEach(k => document.getElementById('tab_' + k).style.display = k === t ? '' : 'none'); }
    const F = { name: 'h_name', location_name: 'h_location', chain: 'h_chain', star_category: 'h_star', grade: 'h_grade', website: 'h_website', address: 'h_address', reservations_email: 'h_res_email', reservations_phone: 'h_res_phone', accounts_email: 'h_acc_email', sales_contact: 'h_sales', phone: 'h_phone', notes: 'h_notes' };
    const depsText = d => `${d.plans_approved} approved plan(s), ${d.plans_draft} draft(s), ${d.room_types} room type(s) · ${d.quotation_nights} night(s) on ${d.tours} tour(s) / quotation(s) · ${d.inquiry_nights} night(s) on inquiries · ${d.desk_cards} Hotel Desk card(s) · ${d.payment_requests} payment request(s), ${d.vouchers} voucher(s) · supplier account(s) ${(d.accounts || []).join(', ') || '—'}`;
    const similarListHtml = list => `<table class="hm" style="width:100%"><thead><tr><th>Code</th><th>Name</th><th>Location</th><th>Status</th><th class="num">Nights / yr</th><th class="num">Plans</th></tr></thead><tbody>${list.map(m => `<tr><td><span class="hcode">${esc(m.hotel_code || '')}</span></td><td><b>${esc(m.name)}</b><div class="help">${esc(m.why || '')}</div></td><td>${esc(m.location_name || '')}</td><td>${esc(m.status)}</td><td class="num">${m.nights_last_year}</td><td class="num">${Number(m.plans_approved) + Number(m.plans_draft)}</td></tr>`).join('')}</tbody></table>`;

    async function openHotel(id, tab) {
        const j = await api('/hotel/' + id); if (j.status !== 200) { toast('error', j.message); return; }
        const d = j.data, h = d.hotel, dep = d.dependencies; S.cur = d;
        document.getElementById('mh_title').innerHTML = `<span class="hcode">${esc(h.hotel_code || '')}</span> ${esc(h.name)}`;
        Object.entries(F).forEach(([k, id2]) => {
            const el = document.getElementById(id2); const v = h[k] ?? '';
            // tf-hotelgrade-r1: a word typed in before the list existed stays on the record and on the
            // list, marked, so saving the hotel never quietly throws it away
            if (el.tagName === 'SELECT' && v !== '' && ![...el.options].some(o => o.value === v || o.text === v)) {
                const o = document.createElement('option'); o.value = v; o.text = v + ' (as it was typed)'; el.appendChild(o);
            }
            el.value = v; el.disabled = !CAN_MANAGE || h.status === 'merged';
        });
        document.getElementById('h_code').textContent = h.hotel_code || '';
        document.getElementById('h_accs').textContent = (h.accounts || []).map(a => a.sup_acc_no).join(', ');
        document.getElementById('h_status').innerHTML = h.status === 'active' ? pill('green', 'active') : h.status === 'inactive' ? pill('amber', 'archived') : pill('grey', 'merged');
        document.getElementById('dl_loc').innerHTML = (d.locations || []).map(l => `<option value="${esc(l.name)}"></option>`).join('');
        document.getElementById('h_name_help').textContent = CAN_MANAGE ? 'Correct spelling here; the supplier record and the ledger name follow. An exact duplicate of another hotel is refused.' : 'Editing needs the Tour Desk Manage privilege.';
        document.getElementById('h_save').style.display = CAN_MANAGE && h.status !== 'merged' ? '' : 'none';
        document.getElementById('h_open_rates').href = `${PANEL}?hotel=${encodeURIComponent(h.primary_sup_acc_no || '')}`;
        const sim = (d.similar || []).filter(m => m.status !== 'merged');
        document.getElementById('h_similar').innerHTML = sim.length ? `<div class="help mb-1"><b>Similar names on file</b> — if one of these is the same hotel, remove the duplicate on the last tab instead of renaming.</div>${similarListHtml(sim)}` : '';
        // dependencies
        const cells = [['Approved rate plans', dep.plans_approved], ['Draft rate plans', dep.plans_draft], ['Superseded / cancelled plans', dep.plans_other], ['Rate rows', dep.rate_rows], ['Room types', dep.room_types], ['Quotation nights (all time)', dep.quotation_nights], ['… of which upcoming', dep.quotation_nights_upcoming], ['Tours / quotations', dep.tours], ['Inquiry nights', dep.inquiry_nights], ['Hotel Desk cards', dep.desk_cards], ['Hotel cost lines', dep.hotel_costs], ['Payment requests', dep.payment_requests], ['Vouchers (Accounts)', dep.vouchers], ['Payment holds', dep.payment_holds], ['Rate sheet imports', dep.imports], ['Old Hotel Rate screen rows', Number(dep.legacy_rows) + Number(dep.old_screen_rows)], ['Open work items', dep.work_items_open], ['Hotels merged into it', dep.absorbed]];
        document.getElementById('deps_grid').innerHTML = cells.map(([l, n]) => `<div class="${Number(n) ? '' : 'zero'}"><b>${esc(n)}</b> ${esc(l)}</div>`).join('') + `<div><b>${esc((dep.accounts || []).join(', ') || '—')}</b> supplier account(s)</div>`;
        document.getElementById('deps_verdict').innerHTML = dep.can_delete ? `${pill('green', 'No booking or financial history — it may be deleted')}${(dep.removable || []).length ? `<div class="help mt-1">Would go with it, if included: ${dep.removable.map(esc).join(' · ')}</div>` : ''}` : `${pill('amber', 'Has booking or financial history — archive or merge, never delete')}<div class="help mt-1">${dep.blocks_delete.map(esc).join(' · ')}</div>`;
        // room types
        const rooms = d.room_types || [];
        document.getElementById('rooms_box').innerHTML = rooms.length ? `<table class="hm"><thead><tr><th>Name</th><th>Priced as</th><th>Status</th><th>Legacy id</th><th></th></tr></thead><tbody>${rooms.map(r => `<tr><td><b>${esc(r.name)}</b></td><td>${esc(r.priced_as)}</td><td>${esc(r.status)}</td><td class="mono">${esc(r.legacy_room_type_id || '')}</td><td class="text-end">${CAN_MANAGE && h.status !== 'merged' ? `<button class="btn btn-light btn-sm" data-rtdel="${r.id}" title="Delete — only when nothing refers to it"><i class="bi bi-trash"></i></button>` : ''}</td></tr>`).join('')}</tbody></table>` : '<div class="empty">No room types.</div>';
        document.querySelectorAll('[data-rtdel]').forEach(b => b.addEventListener('click', () => deleteRoomType(h, rooms.find(r => String(r.id) === b.dataset.rtdel))));
        // history
        document.getElementById('hist_box').innerHTML = (d.audit || []).map(a => `<div><span class="mono">${esc(a.created_at)}</span> &nbsp;${pill(a.action === 'delete' || a.action === 'merge' ? 'red' : (a.action === 'archive' ? 'amber' : 'blue'), a.action)} &nbsp;${esc(a.user)}${a.flag ? ' · ' + esc(a.flag) : ''}</div>`).join('') || '<div class="empty">No history yet.</div>';
        // actions
        const merged = h.status === 'merged';
        document.getElementById('tab_actions_btn').style.display = CAN_MANAGE ? '' : 'none';
        document.getElementById('a_merge').disabled = merged; document.getElementById('a_archive').disabled = merged || h.status === 'inactive'; document.getElementById('a_restore').disabled = merged || h.status !== 'inactive'; document.getElementById('a_delete').disabled = merged || !CAN_ADD_DELETE;
        document.getElementById('a_delete_note').textContent = CAN_ADD_DELETE ? 'Reserved to your account: reason + your password.' : 'Reserved to ' + (OWNERS.join(', ') || 'no account yet') + '.';
        document.getElementById('a_unmerge_wrap').style.display = merged ? '' : 'none';
        showTab(tab || 'details'); showModal('#m_hotel');
        if (window.TfView) TfView.setParams({ hotel: id, tab: tab && tab !== 'details' ? tab : null }, { push: !TfView.param('hotel') });   // tf-refresh-r1
    }

    document.getElementById('h_save').addEventListener('click', async () => {
        const h = S.cur.hotel; const body = {}; Object.entries(F).forEach(([k, id2]) => body[k] = document.getElementById(id2).value);
        const name = body.name.trim().replace(/\s+/g, ' ');
        if (!name) { toast('error', 'The hotel needs a name.'); return; }
        if (name !== h.name) {
            const sim = await api('/similar?name=' + encodeURIComponent(name) + '&exclude=' + h.id); const matches = (sim.data || []).filter(m => m.status !== 'merged');
            if (matches.length) {
                const ok = await Swal.fire({ icon: 'warning', title: 'A similar hotel is already on file', html: `<div style="text-align:left;font-size:13px">${similarListHtml(matches)}<br>If one of these is the same hotel, do not rename — use <b>Remove duplicate</b> so the records are joined. Rename only if <b>${esc(name)}</b> is really a different hotel.</div>`, width: 640, showCancelButton: true, confirmButtonText: 'Rename anyway', cancelButtonText: 'Keep the current name' });
                if (!ok.isConfirmed) { document.getElementById('h_name').value = h.name; return; }
            }
            body.name = name;
        } else { delete body.name; }
        const r = await post(`/hotel/${h.id}/update`, body); toast(r.status === 200 ? 'success' : 'error', r.message);
        if (r.status === 200) { await load(); openHotel(h.id, 'details'); }
    });

    async function typed(word, title, html, confirmText, color) {
        const r = await Swal.fire({ icon: 'warning', title, html: `<div style="text-align:left;font-size:13px">${html}<br><br>Type <b>${word}</b> to confirm.</div>`, width: 700, input: 'text', inputPlaceholder: word, inputValidator: v => (String(v).trim().toUpperCase() === word ? null : `Type ${word} to confirm, or cancel`), showCancelButton: true, confirmButtonText: confirmText, confirmButtonColor: color || undefined });
        return r.isConfirmed;
    }
    async function reason(title) { const r = await Swal.fire({ title, input: 'text', inputPlaceholder: 'Why — goes to the audit log', showCancelButton: true, confirmButtonText: 'Continue' }); return r.isConfirmed ? (r.value || '') : null; }
    const depsHtml = (label, d) => `<b>${esc(label)}:</b> ${esc(d.name)} (${esc(d.hotel_code || '')})<br><span class="help">${esc(depsText(d))}</span>`;

    document.getElementById('a_merge').addEventListener('click', async () => {
        const me = S.cur.dependencies, h = S.cur.hotel;
        const pick = await Swal.fire({ title: `Keep which hotel instead of "${esc(h.name)}"?`, html: `<div style="text-align:left;font-size:13px"><div class="help mb-2">${depsHtml('This record', me)}</div><input class="form-control form-control-sm mb-1" id="mp_q" placeholder="Type part of the hotel name…" autocomplete="off"><select class="form-select form-select-sm" id="mp_sel" size="7"></select><div class="help mt-1">Pick the record to keep — the one with the history (tours, payments, approved rates).</div></div>`, width: 680, showCancelButton: true, confirmButtonText: 'Review…', didOpen: () => { const q = document.getElementById('mp_q'), sel = document.getElementById('mp_sel'); const fill = () => { const w = q.value.trim().toLowerCase(); const list = S.hotels.filter(x => x.id !== Number(h.id) && x.status !== 'merged' && (!w || `${x.name} ${x.location_name || ''} ${x.hotel_code || ''} ${x.primary_sup_acc_no || ''}`.toLowerCase().includes(w))).slice(0, 60); sel.innerHTML = list.map(x => `<option value="${x.id}">${esc(x.hotel_code || '')} ${esc(x.name)}${x.location_name ? ' · ' + esc(x.location_name) : ''} · ${x.nights_last_year} nights/yr</option>`).join(''); }; q.addEventListener('input', fill); fill(); setTimeout(() => q.focus(), 200); }, preConfirm: () => { const v = document.getElementById('mp_sel').value; if (!v) { Swal.showValidationMessage('Pick the hotel to keep'); return false; } return Number(v); } });
        if (!pick.isConfirmed) return;
        const kj = await api(`/hotel/${pick.value}`); if (kj.status !== 200) { toast('error', kj.message); return; } const keep = kj.data.dependencies;
        const ok = await typed('MERGE', 'Remove duplicate?', `${depsHtml('Keep', keep)}<br><br>${depsHtml('Fold in and hide', me)}<br><br>The plans, room types and supplier code of <b>${esc(me.name)}</b> move under <b>${esc(keep.name)}</b>; its tour nights, payments and vouchers stay on their own supplier account, untouched; the duplicate disappears from the hotel pickers. Logged; undoable from the kept hotel.`, 'Remove duplicate');
        if (!ok) return; const note = await reason('Why is this a duplicate?'); if (note === null) return;
        const r = await post(`/hotel/${h.id}/merge`, { keep_id: pick.value, confirm: 'MERGE', note }); toast(r.status === 200 ? 'success' : 'error', r.message);
        if (r.status === 200) { hideModal('#m_hotel'); await load(); openHotel(pick.value, 'deps'); }
    });
    document.getElementById('a_unmerge').addEventListener('click', async () => {
        const h = S.cur.hotel; const ok = await typed('UNMERGE', 'Undo the merge?', `<b>${esc(h.name)}</b> comes back as a hotel of its own with its supplier code, plans and room types.`, 'Undo merge'); if (!ok) return;
        const r = await post(`/hotel/${h.id}/unmerge`, { confirm: 'UNMERGE' }); toast(r.status === 200 ? 'success' : 'error', r.message); if (r.status === 200) { await load(); openHotel(h.id, 'details'); }
    });
    document.getElementById('a_archive').addEventListener('click', async () => {
        const me = S.cur.dependencies, h = S.cur.hotel;
        const ok = await typed('ARCHIVE', `Archive ${esc(h.name)}?`, `${depsHtml('This record', me)}<br><br>The hotel leaves the pickers on inquiries and bookings. Its rate plans, nights on file, payments and vouchers stay exactly as they are. It can be restored any time.`, 'Archive'); if (!ok) return;
        const note = await reason('Why is it archived?'); if (note === null) return;
        const r = await post(`/hotel/${h.id}/archive`, { confirm: 'ARCHIVE', note }); toast(r.status === 200 ? 'success' : 'error', r.message); if (r.status === 200) { await load(); openHotel(h.id, 'details'); }
    });
    document.getElementById('a_restore').addEventListener('click', async () => {
        const h = S.cur.hotel; const r = await post(`/hotel/${h.id}/restore`, {}); toast(r.status === 200 ? 'success' : 'error', r.message); if (r.status === 200) { await load(); openHotel(h.id, 'details'); }
    });
    document.getElementById('a_delete').addEventListener('click', async () => {
        const h = S.cur.hotel; if (!CAN_ADD_DELETE) { toast('error', 'Deleting a hotel is reserved to ' + (OWNERS.join(', ') || 'no account yet') + '.'); return; }
        const pj = await api(`/hotel/${h.id}/removal`); if (pj.status !== 200) { toast('error', pj.message); return; } const p = pj.data, me = p.dependencies;
        if (!p.can_delete) { await Swal.fire({ icon: 'info', title: 'Cannot be deleted', html: `<div style="text-align:left;font-size:13px"><b>${esc(me.name)}</b> has history:<ul>${p.blocks.map(b => `<li>${esc(b)}</li>`).join('')}</ul>Bookings, quotations, vouchers, payments and inquiries are protected: a record with any of them is never deleted. Use <b>Archive</b>, or <b>Remove duplicate</b> if it is the same hotel as another record.</div>`, width: 620 }); return; }
        const list = (rows, cls) => `<table class="hm" style="width:100%"><tbody>${rows.map(r => `<tr><td style="width:38%"><b>${esc(r.what)}</b>${r.needs_include ? ' <span class="pill pill-amber">needs the tick</span>' : ''}</td><td class="${cls}">${esc(r.detail)}</td></tr>`).join('')}</tbody></table>`;
        // one dialog, every step visible: what goes, the include tick, the reason, the password, the word DELETE
        const form = `<div style="text-align:left;font-size:13px"><b>This record:</b> ${esc(me.name)} (${esc(me.hotel_code || '')})<br><span class="help">${esc(depsText(me))}</span>
            <br><br><b style="color:var(--hr-red)">Will be removed</b>${list(p.removed, '')}<br><b style="color:var(--hr-green)">Stays</b>${list(p.kept, 'help')}
            <hr>
            ${p.has_rates ? `<div class="mb-2"><b>Step 1</b> — <label><input type="checkbox" id="del_inc"> Include its rate plans and old-screen rows (${esc((p.removable || []).join(', '))}). They are written to the audit log as full sheets first.</label></div>` : `<div class="mb-2"><b>Step 1</b> — nothing else to include: this record has no rate plans or old-screen rows.</div>`}
            <div class="mb-2"><b>Step 2</b> — Reason (required, goes to the audit log)<input class="form-control form-control-sm" id="del_reason" placeholder="e.g. created by mistake — the real hotel is HTL-130"></div>
            <div class="mb-2"><b>Step 3</b> — Your own sign-in password (checked against your login, never stored)<input type="password" class="form-control form-control-sm" id="del_pw" autocomplete="current-password"></div>
            <div class="mb-1"><b>Step 4</b> — Type <b>DELETE</b> in the box below, then press <b>Delete now</b>.</div></div>`;
        const ok = await Swal.fire({ icon: 'warning', title: `Delete ${esc(h.name)}?`, html: form, width: 780, input: 'text', inputPlaceholder: 'DELETE', showCancelButton: true, confirmButtonText: 'Delete now', confirmButtonColor: '#c0392b',
            preConfirm: v => {
                const inc = p.has_rates ? document.getElementById('del_inc').checked : true;
                const reason = document.getElementById('del_reason').value.trim(), pw = document.getElementById('del_pw').value;
                if (p.has_rates && !inc) { Swal.showValidationMessage('Step 1: tick "Include its rate plans and old-screen rows", or cancel and archive the hotel instead'); return false; }
                if (reason.length < 5) { Swal.showValidationMessage('Step 2: give a reason of at least five characters'); return false; }
                if (!pw) { Swal.showValidationMessage('Step 3: enter your sign-in password'); return false; }
                if (String(v || '').trim().toUpperCase() !== 'DELETE') { Swal.showValidationMessage('Step 4: type DELETE in the box'); return false; }
                return { include_rates: inc, reason, password: pw };
            } });
        if (!ok.isConfirmed) return;
        const includeRates = ok.value.include_rates, reason = { value: ok.value.reason }, pw = { value: ok.value.password };
        const r = await post(`/hotel/${h.id}/delete`, { confirm: 'DELETE', reason: reason.value, password: pw.value, include_rates: includeRates }); toast(r.status === 200 ? 'success' : 'error', r.message); if (r.status === 200) { hideModal('#m_hotel'); load(); }
    });
    const ownersBtn = document.getElementById('btn_owners'); if (ownersBtn) ownersBtn.addEventListener('click', async () => {
        const r = await Swal.fire({ title: 'Who may add or delete a hotel?', input: 'text', inputValue: OWNERS.join(', '), inputPlaceholder: 'usernames, comma-separated', text: 'Only these sign-in names may register a hotel or delete one. Everyone else sees the controls disabled. Logged.', showCancelButton: true, confirmButtonText: 'Save' }); if (!r.isConfirmed) return;
        const j = await post('/owners', { owners: r.value || '' }); toast(j.status === 200 ? 'success' : 'error', j.message); if (j.status === 200) setTimeout(() => location.reload(), 1200);
    });
    async function deleteRoomType(h, r) {
        if (!r) return; const ok = await typed('DELETE', `Delete room type "${esc(r.name)}"?`, 'Only possible when no rate, supplement, child rule or inquiry night refers to it; otherwise set it inactive on the Hotel Rates screen. The row is written to the audit log.', 'Delete', '#c0392b'); if (!ok) return;
        const note = await reason('Why is it deleted?'); if (note === null) return;
        const j = await post(`/hotel/${h.id}/room_type/${r.id}/delete`, { confirm: 'DELETE', note }); toast(j.status === 200 ? 'success' : 'error', j.message); if (j.status === 200) openHotel(h.id, 'rooms');
    }

    // tf-refresh-r1: the hotel open in the dialog (and its tab) is in the address — a refresh opens it again, read fresh
    document.getElementById('m_hotel').addEventListener('hidden.bs.modal', () => { if (window.TfView && TfView.param('hotel')) TfView.setParams({ hotel: null, tab: null }, { push: true }); });
    document.querySelectorAll('#m_hotel [data-tab]').forEach(b => b.addEventListener('click', () => { if (window.TfView && TfView.param('hotel')) TfView.setParams({ tab: b.dataset.tab !== 'details' ? b.dataset.tab : null }); }));
    window.addEventListener('popstate', () => {
        const id = Number(new URLSearchParams(location.search).get('hotel') || 0);
        if (id) openHotel(id, new URLSearchParams(location.search).get('tab') || 'details');
        else if (document.getElementById('m_hotel').classList.contains('show')) hideModal('#m_hotel');
    });
    (async () => { await load(); if (window.TfView) TfView.restoreList(true); if (PRESELECT) openHotel(PRESELECT, new URLSearchParams(location.search).get('tab') || 'details'); })();
})();
</script>
</body>
</html>
