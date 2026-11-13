<?= view('layouts/head') ?>
<style>
    /* ---------- Hotel Incomplete Work (same tokens as the Hotel Rates panel) ---------- */
    .hr2 { --hr-navy: #1e3553; --hr-navy-2: #2c4a73; --hr-teal: #0f8b7e; --hr-teal-soft: #e3f4f1; --hr-amber: #1a1a1a; --hr-amber-soft: #ffffff; --hr-red: #b23a32; --hr-red-soft: #fbe9e7; --hr-green: #1f8a4c; --hr-green-soft: #e4f4ea; --hr-blue-soft: #e6eef8; --hr-ink: #1d2733; --hr-muted: #5f6f80; --hr-line: #dfe5ec; --hr-bg: #f4f6f9; --hr-card: #ffffff; --hr-radius: 10px; color: var(--hr-ink); }
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
    .hr2 .pill-amber { background: var(--hr-amber-soft); border:1px solid #d9d3dd; color: var(--hr-amber); }
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
    .hr2 .dot.red { background: var(--hr-red); } .hr2 .dot.amber { background: #b7791f; } .hr2 .dot.blue { background: var(--hr-navy); }
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
    .hr2 .not-installed { padding: 14px 16px; border-radius: 8px; background: var(--hr-amber-soft); border:1px solid #d9d3dd; color: var(--hr-amber); font-size: 13px; }
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
                        <h4>Hotel Incomplete Work</h4>
                        <div class="sub">Everything the system found missing or doubtful about hotel rates and payments and did not guess: nights with no contract rate, hotels missing from the panel, drafts waiting for approval, rate sheets without a supplier record, unusual margins, unmapped countries, sheet queries. Items are added automatically and closed automatically once the gap is filled; close the rest here with a note.</div>
                    </div>
                    <div class="hr-toolbar">
                        <button class="btn btn-light btn-sm" id="btn_add"><i class="bi bi-plus-lg"></i> Add item</button>
                        <button class="btn btn-light btn-sm" id="btn_export" title="The open items as plain text — paste it to Claude or a colleague to fill the gaps"><i class="bi bi-clipboard"></i> Export for Claude</button>
                        <?php if (!empty($is_sysadmin)): ?><button class="btn btn-light btn-sm" id="btn_seed" title="Registers the items in writable/hotel_rates_entry/work_items.json (rate-sheet queries, merges to confirm …)"><i class="bi bi-file-earmark-arrow-up"></i> Load seed items</button><?php endif; ?>
                        <button class="btn btn-primary btn-sm" id="btn_scan"><i class="bi bi-arrow-repeat"></i> Refresh</button>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="hr-card"><div class="hr-card-b not-installed">
                    <b><i class="bi bi-exclamation-triangle"></i> The Hotel Rates tables are not at the current version.</b>
                    <div class="help mt-1">Open <a href="<?= base_url($panel_url ?? 'master_files/hotel_rate_v2') ?>">Hotel Rates</a> as System Administrator and press <b>Install now</b> (or Settings → Re-run install); this queue is created by the same step.<?= !empty($error) ? ' — ' . esc($error) : '' ?></div>
                </div></div>
                <?php else: ?>

                <div class="kpis" id="kpis"></div>

                <div class="hr-card">
                    <div class="hr-card-b">
                        <div class="filters">
                            <select class="form-select form-select-sm" id="f_status" style="width:auto">
                                <option value="open">Open</option>
                                <option value="done">Done</option>
                                <option value="ignored">Ignored</option>
                                <option value="all">All</option>
                            </select>
                            <select class="form-select form-select-sm" id="f_kind" style="width:auto">
                                <option value="">Every kind</option>
                                <?php foreach (($kinds ?? []) as $k => $def): ?><option value="<?= esc($k) ?>"><?= esc($def['label']) ?></option><?php endforeach; ?>
                            </select>
                            <input class="form-control form-control-sm" id="f_q" placeholder="Hotel, tour, code, text…" style="width:260px">
                            <button class="btn btn-light btn-sm" id="f_clear">Clear</button>
                            <span class="help ms-auto" id="scan_info"></span>
                        </div>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="wk">
                            <thead><tr><th style="width:150px">What</th><th>Item</th><th style="width:150px">Hotel / tour</th><th style="width:110px">Dates</th><th style="width:70px">Seen</th><th style="width:140px"></th></tr></thead>
                            <tbody id="items"><tr><td colspan="6" class="empty">Loading…</td></tr></tbody>
                        </table>
                    </div>
                </div>

                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<!-- resolve modal -->
<div class="modal fade hr2" id="m_resolve" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="m_resolve_title">Close item</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="ttl" id="m_resolve_item"></div>
        <label class="form-label mt-3">Note <span class="help">— what was done, or why it is ignored</span></label>
        <textarea class="form-control" id="m_resolve_note" rows="3"></textarea>
        <div class="form-check mt-2" id="m_resolve_scope_wrap" style="display:none"><input class="form-check-input" type="checkbox" id="m_resolve_scope"><label class="form-check-label" for="m_resolve_scope" id="m_resolve_scope_label">Also ignore the hotel's other open items of this kind</label><div class="help">Same note on each; nothing is deleted — any of them can be reopened.</div></div>
        <input type="hidden" id="m_resolve_id"><input type="hidden" id="m_resolve_status">
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="m_resolve_ok">Save</button></div>
</div></div></div>

<!-- add item modal -->
<div class="modal fade hr2" id="m_add" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Add an item by hand</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-md-7"><label class="form-label">Kind</label><select class="form-select form-select-sm" id="a_kind">
                <?php foreach (($kinds ?? []) as $k => $def): if (!empty($def['auto'])) { continue; } ?><option value="<?= esc($k) ?>"><?= esc($def['label']) ?></option><?php endforeach; ?>
            </select></div>
            <div class="col-md-5"><label class="form-label">Level</label><select class="form-select form-select-sm" id="a_level"><option value="amber">Amber — check</option><option value="red">Red — blocks a payment / quotation</option><option value="blue">Blue — note</option></select></div>
            <div class="col-12"><label class="form-label">Title</label><input class="form-control form-control-sm" id="a_title" placeholder="e.g. Perahera dates missing on the Casamara sheet"></div>
            <div class="col-12"><label class="form-label">Detail <span class="help">— what is missing and what would settle it</span></label><textarea class="form-control" id="a_detail" rows="3"></textarea></div>
            <div class="col-md-6"><label class="form-label">Supplier account <span class="help">(optional)</span></label><input class="form-control form-control-sm mono" id="a_acc" placeholder="6-9-11-…"></div>
            <div class="col-md-6"><label class="form-label">Tour manual ID <span class="help">(optional)</span></label><input class="form-control form-control-sm" id="a_manual" placeholder="AH 1234"></div>
            <div class="col-md-6"><label class="form-label">From date</label><input type="date" class="form-control form-control-sm" id="a_from"></div>
            <div class="col-md-6"><label class="form-label">To date</label><input type="date" class="form-control form-control-sm" id="a_to"></div>
        </div>
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="a_save">Add</button></div>
</div></div></div>

<!-- export modal -->
<div class="modal fade hr2" id="m_export" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Open items — export</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help mb-2">Paste this to Claude (or a colleague) with the missing information — rate sheets, supplier codes, decisions — and the gaps get filled from here. Nothing in it is guessed.</div>
        <textarea class="form-control mono" id="export_text" rows="18" readonly></textarea>
    </div>
    <div class="modal-footer"><a class="btn btn-light" id="export_json" href="#" target="_blank">Download JSON</a><button class="btn btn-primary" id="export_copy"><i class="bi bi-clipboard"></i> Copy</button></div>
</div></div></div>

<!-- attach supplier modal -->
<div class="modal fade hr2" id="m_attach" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Attach the supplier account and load the sheet</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="ttl" id="at_item"></div>
        <div class="help mt-2">Find the hotel's supplier record (created under Master Files → Supplier) by its name, its hotel code or its account number and choose it. The account is written into the transcribed sheet, the hotel is created on the Hotel Master from the supplier record if it is not there yet — with its hotel code given automatically — and the plan is loaded as a <b>draft</b> for the approval queue. Nothing is typed by hand.</div>
        <div class="note" id="at_known" style="display:none"></div>
        <label class="form-label mt-3">Hotel name, hotel code or account</label><input class="form-control form-control-sm" id="at_q" placeholder="e.g. Cinnamon, HTL-017 or 6-9-11-10234" autocomplete="off">
        <div class="pick" id="at_results" style="display:none"></div>
        <div class="chosen" id="at_chosen" style="display:none"></div>
        <input type="hidden" id="at_acc"><input type="hidden" id="at_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="at_ok" disabled>Attach &amp; load</button></div>
</div></div></div>

<!-- supplier details modal -->
<div class="modal fade hr2" id="m_supplier" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Supplier details — what's known so far</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="ttl" id="sd_item"></div>
        <div class="chosen" id="sd_code"></div>
        <div class="help mt-2">Not the supplier account — that still gets created under Master Files → Supplier whenever it's ready. This is only what is already known (from the rate sheet, an email, or anywhere else), kept here so it isn't lost and nobody has to hunt for it again when the record is created. Leave a field blank to clear it.</div>
        <div class="row g-2 mt-1">
            <div class="col-12"><label class="form-label">Chain / group <span class="help">(optional)</span></label><input class="form-control form-control-sm" id="sd_chain" placeholder="e.g. Thema Collection"></div>
            <div class="col-12"><label class="form-label">Address</label><textarea class="form-control form-control-sm" id="sd_address" rows="2"></textarea></div>
            <div class="col-md-6"><label class="form-label">Contact person</label><input class="form-control form-control-sm" id="sd_contact"></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control form-control-sm" id="sd_phone"></div>
            <div class="col-12"><label class="form-label">Email</label><input class="form-control form-control-sm" id="sd_email"></div>
            <div class="col-12"><label class="form-label">Notes <span class="help">(optional)</span></label><textarea class="form-control form-control-sm" id="sd_notes" rows="2" placeholder="Anything else worth keeping — payment terms, who handles reservations, …"></textarea></div>
        </div>
        <input type="hidden" id="sd_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="sd_ok">Save</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($work_url ?? 'master_files/hotel_work') ?>';
    const PANEL = '<?= base_url($panel_url ?? 'master_files/hotel_rate_v2') ?>';
    const APPROVAL = '<?= base_url('master_files/hotel_rate_approval') ?>';
    const TIMELINE = '<?= base_url('tour/quotation/timeline') ?>';
    const DASHBOARD = '<?= base_url('tour/tour_payment_dashboard') ?>';
    const KINDS = <?= json_encode($kinds ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const PRESELECT = <?= json_encode($preselect ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const money = v => (v === null || v === undefined || v === '') ? '—' : Number(v).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const showModal = sel => { const el = document.querySelector(sel); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : new bootstrap.Modal(el)).show(); };
    const hideModal = sel => { const el = document.querySelector(sel); const inst = bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(el) : null; if (inst) { inst.hide(); } };
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2200 : undefined, showConfirmButton: icon !== 'success' });
    async function api(path, opts) { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); if (r.status === 403) { let j = null; try { j = await r.json(); } catch (e) {} return { status: 403, message: (j && j.message) || 'Your user group is not allowed to do this.' }; } return r.json(); }
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const pill = (cls, t) => `<span class="pill pill-${cls}">${esc(t)}</span>`;
    const S = { items: [], counts: {}, kind: PRESELECT.kind || '', status: PRESELECT.status || 'open', q: PRESELECT.q || '', hotel: PRESELECT.hotel || '', tour: PRESELECT.tour || '' };
    if (!INSTALLED) { return; }

    const fStatus = document.getElementById('f_status'), fKind = document.getElementById('f_kind'), fQ = document.getElementById('f_q');
    fStatus.value = S.status; fKind.value = S.kind; fQ.value = S.q;

    function kpisHtml() {
        const total = Object.values(S.counts).reduce((a, c) => a + c.n, 0);
        let h = `<div class="kpi ${S.kind === '' ? 'on' : ''} ${total ? 'red' : 'ok'}" data-kind=""><div class="n">${total}</div><div class="l">open item${total === 1 ? '' : 's'}${total ? '' : ' — nothing missing'}</div></div>`;
        for (const [k, def] of Object.entries(KINDS)) {
            const c = S.counts[k];
            if (!c && !['no_rate', 'draft_pending', 'sheet_no_supplier', 'unusual_margin'].includes(k)) { continue; }
            const n = c ? c.n : 0;
            const lvl = c ? (c.red ? 'red' : (c.amber ? 'amber' : 'blue')) : 'ok';
            h += `<div class="kpi ${S.kind === k ? 'on' : ''} ${lvl}" data-kind="${k}"><div class="n">${n}</div><div class="l">${esc(def.label)}</div></div>`;
        }
        return h;
    }
    function kindPill(it) {
        const cls = it.level === 'red' ? 'red' : it.level === 'amber' ? 'amber' : 'blue';
        return `<span class="dot ${cls}"></span>${pill(cls, it.kind_label)}`;
    }
    function links(it) {
        const out = [];
        if (it.hotel_acc || it.hotel_id) {
            const d = it.ref_date ? '&date=' + it.ref_date + (it.ref_date_to ? '&to=' + it.ref_date_to : '') : '';
            out.push(`<a href="${PANEL}?hotel=${encodeURIComponent(it.hotel_acc || '')}${d}" target="_blank" title="Open in Hotel Rates on these dates">${esc(it.hotel_name || it.hotel_acc)}</a>`);
        } else if (it.hotel_name) { out.push(esc(it.hotel_name)); }
        if (it.hotel_code) { out.push(`<span class="hcode" title="Hotel code — permanent, given by the system">${esc(it.hotel_code)}</span>`); }
        if (it.hotel_acc) { out.push(`<span class="mono" title="Supplier account (ledger)">${esc(it.hotel_acc)}</span>`); }
        if (it.plan_id) { out.push(`<a href="${APPROVAL}" target="_blank" title="Open the approval queue">plan #${it.plan_id}</a>`); }
        if (it.tour_id) { out.push(`<a href="${DASHBOARD}?manual_id=${encodeURIComponent(it.manual_id || '')}" target="_blank" title="Tour Payment Dashboard">${esc(it.manual_id || it.tour_id)}</a> <a href="${TIMELINE}/${encodeURIComponent(it.tour_id)}" target="_blank" title="Quotation timeline" class="help">timeline</a>`); }
        return out.join('<br>');
    }
    function nightsHtml(it) {
        const n = it.data && it.data.nights;
        if (!Array.isArray(n) || !n.length) { return ''; }
        return `<div class="nights">${n.slice(0, 8).map(x => typeof x === 'object' ? `${esc(x.date)} ${esc(x.room_type || '')} ${esc((x.meal_plan || '').toUpperCase())}${x.tour ? ' · ' + esc(x.tour) : ''}${x.quoted !== undefined ? ' · quoted ' + money(x.quoted) : ''}${x.source === 'none' ? ' · no quoted rate' : ''}` : esc(x)).join('<br>')}${n.length > 8 ? '<br>… +' + (n.length - 8) + ' more' : ''}</div>`;
    }
    function toursHtml(it) {
        const t = it.data && it.data.tours;
        if (!Array.isArray(t) || t.length < 2) { return ''; }
        return `<div class="help mt-1">${t.length} confirmed tours: ${t.slice(0, 10).map(x => `<a href="${DASHBOARD}?manual_id=${encodeURIComponent(x.manual_id || '')}" target="_blank">${esc(x.manual_id || x.tour_id)}</a>`).join(', ')}${t.length > 10 ? ' … +' + (t.length - 10) : ''}</div>`;
    }
    const SUPPLIER_DETAIL_KINDS = ['sheet_no_supplier', 'missing_supplier'];
    function supplierHtml(it) {
        if (!SUPPLIER_DETAIL_KINDS.includes(it.kind)) { return ''; }
        const sd = (it.data && it.data.supplier_details) || null;
        let h = '';
        if (sd && (sd.address || sd.chain || sd.contact_person || sd.phone || sd.email)) {
            const parts = [];
            if (sd.chain) { parts.push(esc(sd.chain)); }
            if (sd.address) { parts.push(esc(sd.address)); }
            if (sd.contact_person) { parts.push('Contact: ' + esc(sd.contact_person)); }
            if (sd.phone) { parts.push(esc(sd.phone)); }
            if (sd.email) { parts.push(esc(sd.email)); }
            h += `<div class="meta">${parts.join(' · ')}</div>`;
        }
        if (sd && sd.notes) { h += `<div class="help mt-1">${esc(sd.notes)}</div>`; }
        const missing = it.supplier_missing || [];
        if (missing.length) { h += `<div class="help mt-1" style="color:var(--hr-amber)"><i class="bi bi-info-circle"></i> Still need: ${missing.map(esc).join(', ')}</div>`; }
        return h;
    }
    function actions(it) {
        const b = [];
        if (it.status === 'open') {
            if (SUPPLIER_DETAIL_KINDS.includes(it.kind)) { b.push(`<button class="btn btn-sm btn-light" data-act="supplier" data-id="${it.id}"><i class="bi bi-person-lines-fill"></i> Supplier details</button>`); }
            if (it.kind === 'sheet_no_supplier') { b.push(`<button class="btn btn-sm btn-green-soft" data-act="attach" data-id="${it.id}"><i class="bi bi-link-45deg"></i> Attach supplier account</button>`); }
            b.push(`<button class="btn btn-sm btn-light" data-act="done" data-id="${it.id}"><i class="bi bi-check2"></i> Done</button>`);
            b.push(`<button class="btn btn-sm btn-light" data-act="ignored" data-id="${it.id}"><i class="bi bi-eye-slash"></i> Ignore</button>`);
        } else {
            b.push(`<button class="btn btn-sm btn-light" data-act="open" data-id="${it.id}"><i class="bi bi-arrow-counterclockwise"></i> Reopen</button>`);
        }
        return `<div class="acts">${b.join('')}</div>`;
    }
    function render() {
        document.getElementById('kpis').innerHTML = kpisHtml();
        document.querySelectorAll('#kpis .kpi').forEach(k => k.addEventListener('click', () => { S.kind = k.dataset.kind; fKind.value = S.kind; load(); }));
        const tb = document.getElementById('items');
        if (!S.items.length) {
            tb.innerHTML = `<tr><td colspan="6" class="empty">${S.status === 'open' && !S.kind && !S.q ? '<b>Nothing is missing.</b> Every confirmed night has a contract rate, no draft is waiting, every sheet has a supplier account.' : 'No items match.'}</td></tr>`;
            return;
        }
        // one group per kind, the kind named once with its count (6 Oct 2026: terse rows, the explanation folded on each);
        // the kinds keep the order the list already had (the most urgent first), the items within a kind keep theirs
        const kindOrder = []; S.items.forEach(it => { if (!kindOrder.includes(it.kind_label)) kindOrder.push(it.kind_label); });
        const sorted = S.items.slice().sort((a, b) => kindOrder.indexOf(a.kind_label) - kindOrder.indexOf(b.kind_label));
        let lastKind = null;
        tb.innerHTML = sorted.map(it => (lastKind === it.kind_label ? '' : (lastKind = it.kind_label, `<tr class="grp"><td colspan="6">${kindPill(it)} <span class="help">${sorted.filter(x => x.kind_label === it.kind_label).length} item${sorted.filter(x => x.kind_label === it.kind_label).length === 1 ? '' : 's'}</span></td></tr>`)) + `<tr class="item ${it.status}" data-id="${it.id}">
            <td><span class="dot ${it.level === 'red' ? 'red' : it.level === 'amber' ? 'amber' : 'blue'}"></span>${it.source === 'manual' ? '<div class="help mt-1">added by hand</div>' : ''}${it.status !== 'open' ? '<div class="mt-1">' + pill(it.status === 'done' ? 'green' : 'grey', it.status) + '</div>' : ''}</td>
            <td><div class="ttl">${esc(it.title)}</div>${it.detail ? `<div class="dtl">${esc(it.detail)}</div>` : ''}${nightsHtml(it)}${toursHtml(it)}${supplierHtml(it)}${it.note ? `<div class="note">${esc(it.note)}</div>` : ''}</td>
            <td>${links(it)}</td>
            <td>${esc(it.dates || '')}${it.amount !== null && it.amount !== undefined ? `<div class="help">${money(it.amount)}</div>` : ''}</td>
            <td><span title="first seen ${esc(it.created_at)} · last seen ${esc(it.last_seen_at || '')} · seen ${it.seen_count}×">${it.age_days === 0 ? 'today' : it.age_days + ' d'}</span></td>
            <td>${actions(it)}</td></tr>`).join('');
        tb.querySelectorAll('[data-act]').forEach(b => b.addEventListener('click', () => onAct(b.dataset.act, Number(b.dataset.id))));
    }
    async function load() {
        const qs = new URLSearchParams({ status: S.status, kind: S.kind, q: S.q, hotel: S.hotel, tour_id: S.tour });
        const j = await api('/list?' + qs.toString());
        if (j.status !== 200) { toast('error', j.message); return; }
        S.items = j.data.items; S.counts = j.data.counts;
        document.getElementById('scan_info').textContent = j.data.last_scan ? 'last scan ' + j.data.last_scan : '';
        render();
    }
    function onAct(act, id) {
        const it = S.items.find(x => Number(x.id) === Number(id)); // the API returns ids as strings
        if (!it) { return; }
        if (act === 'attach') {
            document.getElementById('at_id').value = id; document.getElementById('at_item').textContent = it.title;
            const sd = (it.data && it.data.supplier_details) || null, known = document.getElementById('at_known');
            if (sd && (sd.address || sd.chain || sd.contact_person || sd.phone || sd.email)) {
                known.style.display = ''; known.innerHTML = 'Known so far — ' + [sd.chain, sd.address, sd.contact_person, sd.phone, sd.email].filter(Boolean).map(esc).join(' · ');
            } else { known.style.display = 'none'; known.innerHTML = ''; }
            chooseSupplier(null);
            // start from what the sheet says: its account when it carries one, else the hotel's name
            const start = (it.data && it.data.acc_on_sheet) || it.hotel_acc || (it.hotel_name || '').replace(/[,.].*$/, '').trim();
            document.getElementById('at_q').value = start; searchSuppliers(start);
            showModal('#m_attach'); return;
        }
        if (act === 'supplier') {
            const sd = (it.data && it.data.supplier_details) || {};
            document.getElementById('sd_id').value = id; document.getElementById('sd_item').textContent = it.title;
            document.getElementById('sd_code').innerHTML = it.hotel_code
                ? `Hotel code <span class="hcode">${esc(it.hotel_code)}</span> <span class="help">— permanent, given by the system; it stays if the hotel is renamed or archived.</span>`
                : `<span class="help">The hotel code (HTL-…) is given automatically the moment the hotel is created on the Hotel Master — nobody types it.</span>`;
            document.getElementById('sd_chain').value = sd.chain || ''; document.getElementById('sd_address').value = sd.address || '';
            document.getElementById('sd_contact').value = sd.contact_person || ''; document.getElementById('sd_phone').value = sd.phone || '';
            document.getElementById('sd_email').value = sd.email || ''; document.getElementById('sd_notes').value = sd.notes || '';
            showModal('#m_supplier'); return;
        }
        document.getElementById('m_resolve_id').value = id; document.getElementById('m_resolve_status').value = act;
        document.getElementById('m_resolve_title').textContent = act === 'done' ? 'Mark as done' : act === 'ignored' ? 'Ignore this item' : 'Reopen this item';
        document.getElementById('m_resolve_item').textContent = it.title; document.getElementById('m_resolve_note').value = '';
        // the whole hotel at once (Ignore / Done): the other open items of the same kind for the same hotel, as far as this list shows them
        const key = it.hotel_id ? 'id:' + it.hotel_id : (it.hotel_name ? 'nm:' + String(it.hotel_name).trim().toLowerCase() : '');
        const sibs = key ? S.items.filter(x => x.status === 'open' && x.kind === it.kind && Number(x.id) !== Number(it.id) && (x.hotel_id ? 'id:' + x.hotel_id : (x.hotel_name ? 'nm:' + String(x.hotel_name).trim().toLowerCase() : '-')) === key).length : 0;
        const wrap = document.getElementById('m_resolve_scope_wrap'), box = document.getElementById('m_resolve_scope');
        box.checked = false;
        wrap.style.display = (act !== 'open' && sibs > 0) ? '' : 'none';
        document.getElementById('m_resolve_scope_label').textContent = `Also ${act === 'done' ? 'mark done' : 'ignore'} the ${sibs} other open ${esc(it.kind_label).toLowerCase()} item${sibs === 1 ? '' : 's'} of ${it.hotel_name || 'this hotel'}`;
        showModal('#m_resolve');
    }
    document.getElementById('m_resolve_ok').addEventListener('click', async () => {
        const id = document.getElementById('m_resolve_id').value, status = document.getElementById('m_resolve_status').value, note = document.getElementById('m_resolve_note').value;
        if (status === 'ignored' && !note.trim()) { toast('error', 'Say why it is ignored.'); return; }
        const scope = document.getElementById('m_resolve_scope_wrap').style.display !== 'none' && document.getElementById('m_resolve_scope').checked ? 'hotel' : '';
        const j = await post(`/item/${id}/status`, scope ? { status, note, scope } : { status, note });
        if (j.status !== 200) { toast('error', j.message); return; }
        hideModal('#m_resolve'); toast('success', j.message); load();
    });
    // the "Attach supplier account" picker: search by name / hotel code / account, choose one, nothing typed by hand
    let atTimer = null, atSeq = 0;
    function supplierLine(r) {
        const where = r.hotel_id ? `on the Hotel Master as <b>${esc(r.hotel_name || r.name)}</b>${r.hotel_code ? ' <span class="hcode">' + esc(r.hotel_code) + '</span>' : ''}` : 'not on the Hotel Master yet — it will be created with its hotel code';
        return `<div><b>${esc(r.name)}</b> <span class="mono">${esc(r.acc)}</span>${r.legacy_code && !r.hotel_code ? ' <span class="help">(' + esc(r.legacy_code) + ')</span>' : ''}</div><div class="sub">${r.address ? esc(r.address) + ' · ' : ''}${where}</div>`;
    }
    function chooseSupplier(r) {
        const acc = document.getElementById('at_acc'), box = document.getElementById('at_chosen'), ok = document.getElementById('at_ok');
        acc.value = r ? r.acc : ''; ok.disabled = !r;
        box.style.display = r ? '' : 'none'; box.innerHTML = r ? '<div class="help mb-1">Chosen</div>' + supplierLine(r) : '';
        document.querySelectorAll('#at_results button').forEach(b => b.classList.toggle('on', !!r && b.dataset.acc === r.acc));
    }
    async function searchSuppliers(q) {
        const list = document.getElementById('at_results'); q = (q || '').trim();
        if (q.length < 2) { list.style.display = 'none'; list.innerHTML = ''; return; }
        const seq = ++atSeq;
        const j = await api('/suppliers?q=' + encodeURIComponent(q));
        if (seq !== atSeq) { return; }                                 // a newer search has overtaken this one
        if (j.status !== 200) { toast('error', j.message); return; }
        const rows = j.data || [];
        list.style.display = '';
        list.innerHTML = rows.length ? rows.map((r, i) => `<button type="button" data-i="${i}" data-acc="${esc(r.acc)}">${supplierLine(r)}</button>`).join('')
            : `<div class="help p-2">No hotel supplier matches "${esc(q)}". If the supplier record does not exist yet, create it under Master Files → Supplier first.</div>`;
        list.querySelectorAll('button').forEach(b => b.addEventListener('click', () => chooseSupplier(rows[Number(b.dataset.i)])));
        if (rows.length === 1) { chooseSupplier(rows[0]); } else { chooseSupplier(null); }
    }
    document.getElementById('at_q').addEventListener('input', e => { clearTimeout(atTimer); atTimer = setTimeout(() => searchSuppliers(e.target.value), 250); });
    document.getElementById('at_ok').addEventListener('click', async () => {
        const id = document.getElementById('at_id').value, acc = document.getElementById('at_acc').value.trim();
        if (!acc) { toast('error', 'Choose the supplier account from the list.'); return; }
        const j = await post(`/item/${id}/attach_supplier`, { acc });
        if (j.status !== 200) { toast('error', j.message); return; }
        hideModal('#m_attach'); Swal.fire({ icon: 'success', title: 'Loaded', text: j.message }); load();
    });
    document.getElementById('sd_ok').addEventListener('click', async () => {
        const id = document.getElementById('sd_id').value;
        const body = { chain: document.getElementById('sd_chain').value, address: document.getElementById('sd_address').value, contact_person: document.getElementById('sd_contact').value, phone: document.getElementById('sd_phone').value, email: document.getElementById('sd_email').value, notes: document.getElementById('sd_notes').value };
        const j = await post(`/item/${id}/supplier_details`, body);
        if (j.status !== 200) { toast('error', j.message); return; }
        hideModal('#m_supplier'); toast('success', j.message); load();
    });
    document.getElementById('btn_scan').addEventListener('click', async () => {
        const b = document.getElementById('btn_scan'); b.disabled = true;
        const j = await post('/scan', {}); b.disabled = false;
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); load();
    });
    document.getElementById('btn_add').addEventListener('click', () => { ['a_title', 'a_detail', 'a_acc', 'a_manual', 'a_from', 'a_to'].forEach(i => document.getElementById(i).value = ''); showModal('#m_add'); });
    document.getElementById('a_save').addEventListener('click', async () => {
        const body = { kind: document.getElementById('a_kind').value, level: document.getElementById('a_level').value, title: document.getElementById('a_title').value, detail: document.getElementById('a_detail').value, hotel_acc: document.getElementById('a_acc').value, manual_id: document.getElementById('a_manual').value, ref_date: document.getElementById('a_from').value, ref_date_to: document.getElementById('a_to').value };
        const j = await post('/item', body);
        if (j.status !== 200) { toast('error', j.message); return; }
        hideModal('#m_add'); toast('success', j.message); load();
    });
    document.getElementById('btn_export').addEventListener('click', async () => {
        const qs = new URLSearchParams({ kind: S.kind, q: S.q });
        const r = await fetch(BASE + '/export?' + qs.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        document.getElementById('export_text').value = await r.text();
        document.getElementById('export_json').href = BASE + '/export?format=json&' + qs.toString();
        showModal('#m_export');
    });
    document.getElementById('export_copy').addEventListener('click', async () => {
        const t = document.getElementById('export_text');
        try { await navigator.clipboard.writeText(t.value); toast('success', 'Copied.'); } catch (e) { t.select(); document.execCommand('copy'); toast('success', 'Copied.'); }
    });
    const seed = document.getElementById('btn_seed'); if (seed) seed.addEventListener('click', async () => {
        const j = await post('/load_seed', {});
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); load();
    });
    fStatus.addEventListener('change', () => { S.status = fStatus.value; load(); });
    fKind.addEventListener('change', () => { S.kind = fKind.value; load(); });
    let t = null; fQ.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { S.q = fQ.value.trim(); load(); }, 250); });
    document.getElementById('f_clear').addEventListener('click', () => { S.q = ''; S.kind = ''; S.status = 'open'; S.hotel = ''; S.tour = ''; fQ.value = ''; fKind.value = ''; fStatus.value = 'open'; load(); });
    load();
})();
</script>
</body>
</html>
