<?= view('layouts/head') ?>
<style>
    /* ---------- Place to Visit / Entrance Fee Master (tf-safari-r1 redesign) — Avenir orchid #842484 and green #78b848 ---------- */
    .tf { --av-orchid:#842484; --av-orchid-2:#6d1d6d; --av-orchid-soft:#f6e9f6; --av-green:#78b848; --av-green-2:#5f9a36; --av-green-soft:#eef7e6;
          --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff; --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-blue-soft:#eef1f8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#e2e6ec; --tf-bg:#f5f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:12px 16px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:700; font-size:14px; color:var(--av-orchid); }
    .tf .tf-card-b { padding:14px 16px; }
    .tf .page-h { display:flex; gap:14px; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .page-h h4 { margin:0; font-weight:700; color:var(--av-orchid); }
    .tf .page-h .sub { color:var(--tf-muted); font-size:13px; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .btn-av { background:var(--av-orchid); border-color:var(--av-orchid); color:#fff; }
    .tf .btn-av:hover, .tf .btn-av:focus { background:var(--av-orchid-2); border-color:var(--av-orchid-2); color:#fff; }
    .tf .btn-go { background:var(--av-green); border-color:var(--av-green); color:#fff; }
    .tf .btn-go:hover { background:var(--av-green-2); border-color:var(--av-green-2); color:#fff; }
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .btn-light:hover { background:var(--tf-bg); }
    .tf .btn-del { background:#fff; border:1px solid #efc9c5; color:var(--tf-red); }
    .tf .btn-del:hover { background:var(--tf-red-soft); }
    .tf .search-card { border:2px solid var(--av-orchid-soft); }
    .tf .search-row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
    .tf .search-box { position:relative; flex:1 1 380px; }
    .tf .search-box i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--av-orchid); font-size:18px; }
    .tf .search-box input { height:46px; padding-left:42px; font-size:15px; border-radius:23px; border:1.5px solid #d9c3d9; }
    .tf .search-box input:focus { border-color:var(--av-orchid); box-shadow:0 0 0 3px rgba(132,36,132,.12); }
    .tf .filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .tf .filters .form-select, .tf .filters .form-control { width:auto; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:13px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:9px 12px; border-bottom:1px solid var(--tf-line); white-space:nowrap; background:#fafbfc; }
    .tf .tbl td { padding:10px 12px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr.off td { opacity:.6; }
    .tf .tbl tbody tr:hover td { background:#fcf8fc; }
    .tf .num { font-variant-numeric:tabular-nums; white-space:nowrap; }
    .tf .money { font-weight:600; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 9px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--av-green-soft); color:var(--av-green-2); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-orchid { background:var(--av-orchid-soft); color:var(--av-orchid); }
    .tf .missing { color:var(--tf-red); font-weight:600; font-size:12px; }
    .tf .empty { color:var(--tf-muted); font-size:13px; padding:14px 0; text-align:center; }
    .tf .acts { white-space:nowrap; text-align:right; }
    .tf .acts .btn { margin-left:4px; }
    .tf .chip { display:inline-flex; gap:4px; align-items:center; font-size:11.5px; background:var(--tf-blue-soft); border-radius:14px; padding:1px 4px 1px 9px; margin:2px 4px 2px 0; }
    .tf .chip button { border:0; background:transparent; color:#7b8ba0; padding:0 5px; line-height:1.2; font-size:14px; }
    .tf .try-out { font-size:12.5px; border-radius:8px; padding:10px 12px; margin-top:10px; }
    /* the editor */
    #m_place .modal-header { background:var(--av-orchid); color:#fff; }
    #m_place .modal-header .btn-close { filter:invert(1); }
    #m_place .sec { border:1px solid var(--tf-line); border-radius:10px; margin-bottom:14px; }
    #m_place .sec-h { padding:10px 14px; background:#faf6fa; border-bottom:1px solid var(--tf-line); border-radius:10px 10px 0 0; display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; }
    #m_place .sec-h b { color:var(--av-orchid); font-size:13.5px; }
    #m_place .sec-b { padding:12px 14px; }
    #m_place label.form-label { font-size:12px; color:var(--tf-muted); margin-bottom:2px; }
    #m_place .sec-nav { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px; position:sticky; top:-16px; background:#fff; padding:6px 0; z-index:2; }
    #m_place .sec-nav a { font-size:12px; text-decoration:none; color:var(--av-orchid); border:1px solid var(--av-orchid-soft); border-radius:14px; padding:3px 10px; }
    #m_place .sec-nav a:hover { background:var(--av-orchid-soft); }
    #m_place .rate-now { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:8px; margin-bottom:10px; }
    #m_place .rate-now div { border:1px solid var(--tf-line); border-radius:8px; padding:8px 10px; }
    #m_place .rate-now span { display:block; font-size:11px; color:var(--tf-muted); text-transform:uppercase; letter-spacing:.03em; }
    #m_place .rate-now b { font-size:16px; font-variant-numeric:tabular-nums; }
    #m_place .linked { background:var(--av-green-soft); border-radius:8px; padding:8px 10px; font-size:12.5px; }
    #m_place .sup-res { border:1px solid var(--tf-line); border-radius:8px; max-height:200px; overflow:auto; margin-top:4px; }
    #m_place .sup-res button { display:block; width:100%; text-align:left; border:0; border-bottom:1px solid var(--tf-line); background:#fff; padding:6px 10px; font-size:12.5px; }
    #m_place .sup-res button:hover { background:var(--av-orchid-soft); }
    @media (max-width: 640px) { .tf .filters .form-select, .tf .filters .form-control { width:100%; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>

                <div class="page-h">
                    <div>
                        <h4>Place to Visit / Entrance Fee Master</h4>
                        <div class="sub">Paid entrance places and safari parks: entrance rates for adults and children, jeep hire, and who to book and pay. Quotations price entrances and safaris only from here.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="tf-card"><div class="tf-card-b" style="color:var(--tf-amber)">This master needs its update installed. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> as the administrator and press Install now.</div></div>
                <?php else: ?>

                <!-- 1. search, filters and Add -->
                <div class="tf-card search-card mb-3"><div class="tf-card-b">
                    <div class="search-row">
                        <div class="search-box"><i class="bi bi-search"></i><input class="form-control" id="l_q" placeholder="Search park or place, supplier name or code…" autocomplete="off"></div>
                        <button class="btn btn-av" id="b_add" style="height:46px;border-radius:23px;padding:0 22px"><i class="bi bi-plus-lg"></i> Add place</button>
                    </div>
                    <div class="filters mt-2">
                        <select class="form-select form-select-sm" id="l_status">
                            <option value="">All in the master (not archived)</option><option value="parks">Safari parks</option><option value="priced">Charged</option>
                            <option value="held">No rate — held</option><option value="off">Not in use</option><option value="archived">Archived</option></select>
                        <select class="form-select form-select-sm" id="l_dest"><option value="">All destinations</option></select>
                        <label class="help d-flex gap-1 align-items-center">Rates on <input type="date" class="form-control form-control-sm" id="l_on" style="width:150px"></label>
                        <span class="help" id="l_count"></span>
                    </div>
                </div></div>

                <!-- 2. safari parks at a glance -->
                <div class="tf-card mb-3">
                    <div class="tf-card-h"><h6><i class="bi bi-truck"></i> Safari parks — rates at a glance</h6><span class="help">Entrance per person; jeep per vehicle. The customer sees one safari price; this breakdown is internal.</span></div>
                    <div style="overflow-x:auto"><table class="tbl" id="parks_tbl"><thead><tr>
                        <th>Park</th><th>Adult entrance / person</th><th>Child entrance / person</th><th>Child ages</th><th>Jeep / vehicle</th><th>Jeep capacity</th><th>Valid</th><th>Jeep operator</th><th></th>
                    </tr></thead><tbody id="parks_rows"><tr><td colspan="9" class="empty">Loading…</td></tr></tbody></table></div>
                </div>

                <!-- 3. every place -->
                <div class="tf-card mb-3">
                    <div class="tf-card-h"><h6><i class="bi bi-geo-alt"></i> All places</h6><span class="help">Delete removes a place that was never used; a place used in bookings is archived instead, so its history stays.</span></div>
                    <div style="overflow-x:auto"><table class="tbl"><thead><tr>
                        <th>Place</th><th>Destination</th><th>Entrance (adult / child)</th><th>Jeep</th><th>Suppliers</th><th>Status</th><th></th>
                    </tr></thead><tbody id="l_rows"><tr><td colspan="7" class="empty">Loading…</td></tr></tbody></table></div>
                </div>

                <!-- 4. quick price check -->
                <div class="row g-3 mb-3">
                    <div class="col-lg-6">
                        <div class="tf-card h-100">
                            <div class="tf-card-h"><h6><i class="bi bi-calculator"></i> Price check</h6><span class="help">In the rate's own currency, by the place's rules</span></div>
                            <div class="tf-card-b">
                                <div class="row g-2">
                                    <div class="col-12"><label class="help">Place</label><select class="form-select form-select-sm" id="pc_place"></select></div>
                                    <div class="col-6 col-md-3"><label class="help">Date</label><input type="date" class="form-control form-control-sm" id="pc_date"></div>
                                    <div class="col-6 col-md-2"><label class="help">Adults</label><input type="number" min="0" class="form-control form-control-sm" id="pc_ad" value="2"></div>
                                    <div class="col-8 col-md-4"><label class="help">Children's ages</label><input class="form-control form-control-sm" id="pc_ages" placeholder="e.g. 4, 9"></div>
                                    <div class="col-4 col-md-3"><label class="help">Jeeps</label><input type="number" min="0" class="form-control form-control-sm" id="pc_jeeps" placeholder="auto"></div>
                                </div>
                                <div id="pc_out"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="tf-card h-100">
                            <div class="tf-card-h"><h6><i class="bi bi-info-circle"></i> How charges are worked out</h6></div>
                            <div class="tf-card-b help" style="font-size:12.5px">
                                <div><b>Entrance</b> — each adult at the adult rate. Each child by the place's age rules: under the free age, nothing; within the child ages, the child rate; older, the adult rate. A child's charge also waits for the Child Fare Review on the inquiry.</div>
                                <div class="mt-2"><b>Jeep</b> — per vehicle. Jeeps needed = all passengers, adults and children, ÷ the jeep capacity, rounded up — unless the line says how many.</div>
                                <div class="mt-2"><b>Currency</b> — LKR rates are turned into USD at the day's USD rate on the quotation. A rate must be above 0; with nothing to pay, mark it Free.</div>
                                <div class="mt-2"><b>Customer</b> — sees one safari price (entrance + jeep). The breakdown stays on the internal costing.</div>
                                <?php if (!empty($sees_paid)): ?><div class="mt-2">Safaris are no longer offered from <a href="<?= base_url($paid_url ?? 'master_files/paid_services') ?>" target="_blank">Paid Activities &amp; Services</a>.</div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="conflict_box"></div>
                <div id="import_box" class="mb-3"></div>
                <datalist id="dl_dest"></datalist>

                <!-- the editor -->
                <div class="modal fade" id="m_place" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header"><h5 class="modal-title" id="mp_title">Place</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                            <div class="modal-body" id="mp_body"></div>
                            <div class="modal-footer justify-content-between"><span class="help" id="mp_foot"></span><button class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
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
    const BASE = '<?= base_url($page_url ?? 'master_files/entrance_master') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2600 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const $ = id => document.getElementById(id);
    const amt = (cur, v) => v == null ? null : (cur || '') + ' ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    const fmtDate = d => { if (!d) return ''; const t = new Date(d + 'T00:00:00'); return isNaN(t) ? d : t.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); };
    const ageN = x => x == null ? '' : String(Number(x)).replace(/\.0+$/, '');
    const STATUS = { priced: ['Charged', 'pill-green'], free: ['Free', 'pill-green'], no_rate: ['No rate — held', 'pill-red'], check: ['To decide — held', 'pill-red'], off: ['Not in use', 'pill-grey'], archived: ['Archived', 'pill-grey'] };
    const OUTCOME = { imported: ['Imported', 'pill-green'], merged: ['Merged in (duplicate)', 'pill-orchid'], split: ['Safari — entrance and jeep kept apart', 'pill-orchid'], sightseeing: ['General sightseeing — outside', 'pill-grey'], review: ['Held for review — not imported', 'pill-amber'], service: ['Meal / service / activity — outside', 'pill-grey'], not_listed: ['Not on the list', 'pill-amber'] };
    let D = { places: [], roles: {}, charges: {}, currencies: [], pay_modes: {}, categories: [], import: { counts: {}, rows: [] } };
    let OPEN = null, IMP = 'review', IMP_OPEN = false;
    const byId = id => D.places.find(p => p.id === Number(id));
    const period = r => r ? (r.valid_from || r.valid_to ? (r.valid_from ? fmtDate(r.valid_from) : '…') + ' – ' + (r.valid_to ? fmtDate(r.valid_to) : '…') : 'no end date') : '';
    const party = (p, role) => (p.parties || {})[role] || null;
    const eff = (pt, f) => pt ? (pt.own[f] || (pt.supplier ? pt.supplier[f === 'contact_person' ? 'contact' : (f === 'business_name' ? 'name' : f)] : null)) : null;
    const partyName = pt => pt ? (pt.own.business_name || (pt.supplier ? pt.supplier.name : null)) : null;
    const entranceText = r => !r ? '<span class="missing">not set</span>' : (Number(r.free_entry) === 1 ? '<span class="pill pill-green">Free</span>' : `<span class="money">${esc(amt(r.currency, r.adult_rate))}</span>`);
    const childText = r => !r ? '<span class="missing">not set</span>' : (Number(r.free_entry) === 1 ? '<span class="pill pill-green">Free</span>' : (r.child_rate == null ? '<span class="missing">no child rate</span>' : (Number(r.child_rate) === 0 ? '<span class="pill pill-green">Free</span>' : `<span class="money">${esc(amt(r.currency, r.child_rate))}</span>`)));
    const ageText = p => p.child_age_from == null && p.child_age_to == null ? '<span class="help">not set</span>' : esc([p.child_age_from ? 'free under ' + ageN(p.child_age_from) : null, 'child ' + (p.child_age_from ? ageN(p.child_age_from) : '0') + '–' + (p.child_age_to != null ? ageN(p.child_age_to) : '…'), p.child_age_to != null ? 'adult over ' + ageN(p.child_age_to) : null].filter(Boolean).join(' · '));

    // ---------------------------------------------------------------- search and filters
    function matches(p, q) {
        if (!q) return true;
        const pt = Object.values(p.parties || {});
        const hay = [p.name, p.code, p.location, p.destination, ...(p.aliases || []).map(a => a.alias),
            ...pt.flatMap(x => [x.own.business_name, x.own.contact_person, x.supplier && x.supplier.name, x.supplier && x.supplier.acc_no])].join(' ').toLowerCase();
        return q.split(/\s+/).every(w => hay.includes(w));
    }
    function shown() {
        const q = $('l_q').value.trim().toLowerCase(), st = $('l_status').value, dest = $('l_dest').value;
        return D.places.filter(p => p.merged_into == null
            && (st === 'archived' ? p.status === 'archived' : p.status !== 'archived')
            && (!st || st === 'archived' || (st === 'held' ? (p.status === 'no_rate' || p.status === 'check') : (st === 'parks' ? Number(p.jeep) === 1 : p.status === st)))
            && (!dest || (p.destination || '').toLowerCase() === dest.toLowerCase())
            && matches(p, q));
    }

    // ---------------------------------------------------------------- the two tables
    function render() {
        const rows = shown();
        $('l_count').textContent = rows.length + ' shown';
        const parks = rows.filter(p => Number(p.jeep) === 1);
        $('parks_rows').innerHTML = parks.map(p => {
            const e = p.current, j = p.current_jeep, op = party(p, 'jeep_operator');
            return `<tr class="${Number(p.active) === 1 ? '' : 'off'}">
                <td><b>${esc(p.name)}</b>${p.code ? ' <span class="mono help">' + esc(p.code) + '</span>' : ''}</td>
                <td>${entranceText(e)}</td><td>${childText(e)}</td><td class="help">${ageText(p)}</td>
                <td>${j ? (Number(j.free_entry) === 1 ? '<span class="pill pill-green">Free</span>' : `<span class="money">${esc(amt(j.currency, j.adult_rate))}</span>`) : '<span class="missing">not set</span>'}</td>
                <td class="num">${p.jeep_seats ? p.jeep_seats + ' passengers' : '<span class="missing">to confirm</span>'}</td>
                <td class="help num">${esc(period(e || j))}</td>
                <td>${op && partyName(op) ? esc(partyName(op)) + (Number(p.pay_together) === 1 ? '<div class="help">jeep + entrance paid together</div>' : '') : '<span class="help">not set</span>'}</td>
                <td class="acts"><button class="btn btn-light btn-sm" data-edit="${p.id}"><i class="bi bi-pencil"></i> Edit</button></td></tr>`;
        }).join('') || '<tr><td colspan="9" class="empty">No safari park matches.</td></tr>';
        $('l_rows').innerHTML = rows.map(p => {
            const [pt, pc] = STATUS[p.status] || [p.status, 'pill-grey'], e = p.current, j = p.current_jeep;
            const sups = Object.values(p.parties || {}).map(x => partyName(x)).filter(Boolean);
            return `<tr class="${Number(p.active) === 1 ? '' : 'off'}">
                <td><b>${esc(p.name)}</b>${p.code ? ' <span class="mono help">' + esc(p.code) + '</span>' : ''}<div>${p.category ? '<span class="pill pill-orchid">' + esc(p.category) + '</span>' : ''}</div></td>
                <td>${p.destination ? esc(p.destination) : '<span class="help">—</span>'}</td>
                <td>${e ? (Number(e.free_entry) === 1 ? '<span class="pill pill-green">Free</span>' : `<span class="money">${esc(amt(e.currency, e.adult_rate))}</span> / ${childText(e)}`) : '<span class="missing">no rate</span>'}</td>
                <td>${Number(p.jeep) === 1 ? (j ? `<span class="money">${esc(amt(j.currency, j.adult_rate))}</span>` : '<span class="missing">no rate</span>') : '<span class="help">—</span>'}</td>
                <td>${sups.length ? sups.map(esc).join('<br>') : '<span class="help">—</span>'}</td>
                <td><span class="pill ${pc}">${pt}</span>${p.used ? ` <span class="help">${p.used} open line${p.used === 1 ? '' : 's'}</span>` : ''}</td>
                <td class="acts">${p.status === 'archived'
                    ? `<button class="btn btn-light btn-sm" data-restore="${p.id}"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>`
                    : `<button class="btn btn-light btn-sm" data-edit="${p.id}"><i class="bi bi-pencil"></i> Edit</button><button class="btn btn-del btn-sm" data-del="${p.id}"><i class="bi bi-trash"></i> Delete</button>`}</td></tr>`;
        }).join('') || '<tr><td colspan="7" class="empty">Nothing matches the search.</td></tr>';
        document.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => openPlace(Number(b.dataset.edit))));
        document.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', () => delPlace(Number(b.dataset.del))));
        document.querySelectorAll('[data-restore]').forEach(b => b.addEventListener('click', () => restorePlace(Number(b.dataset.restore))));
        priceCheckPlaces();
    }

    // ---------------------------------------------------------------- delete (or archive) and restore
    async function delPlace(id) {
        const p = byId(id);
        const c = await api('/place/' + id + '/delete');
        if (c.status !== 200) { toast('error', c.message); return; }
        const k = c.data;
        const r = await Swal.fire({
            title: k.archive ? 'Archive ' + p.name + '?' : 'Delete ' + p.name + '?',
            html: k.archive
                ? `<div style="text-align:left;font-size:14px">It has been used (${esc(k.why.join(', '))}), so it cannot be deleted. It will be <b>archived</b>: kept with its rates for that history, no longer offered to staff, and it can be restored.</div><input id="del_note" class="swal2-input" placeholder="Reason (optional)">`
                : `<div style="text-align:left;font-size:14px">It has never been used. It will be <b>removed permanently</b> with its rates, other names and supplier details. This cannot be undone.</div><input id="del_note" class="swal2-input" placeholder="Reason (optional)">`,
            icon: 'warning', showCancelButton: true, confirmButtonText: k.archive ? 'Archive' : 'Delete permanently', confirmButtonColor: k.archive ? '#842484' : '#b23a32', focusCancel: true,
            preConfirm: () => document.getElementById('del_note').value });
        if (!r.isConfirmed) return;
        const j = await post('/place/' + id + '/delete', { note: r.value || '' });
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); load();
    }
    async function restorePlace(id) {
        const p = byId(id);
        const r = await Swal.fire({ title: 'Restore ' + p.name + '?', text: 'It comes back into use and is offered to staff again.', icon: 'question', showCancelButton: true, confirmButtonText: 'Restore', confirmButtonColor: '#78b848' });
        if (!r.isConfirmed) return;
        const j = await post('/place/' + id + '/restore', {});
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); load();
    }

    // ---------------------------------------------------------------- the editor
    const field = (label, key, val, opts = {}) => `<div class="${opts.col || 'col-md-6'}"><label class="form-label">${label}</label>${opts.area
        ? `<textarea class="form-control form-control-sm" data-${opts.ns || 'p'}="${key}" rows="${opts.rows || 2}" placeholder="${esc(opts.ph || '')}">${esc(val ?? '')}</textarea>`
        : `<input class="form-control form-control-sm" data-${opts.ns || 'p'}="${key}" value="${esc(val ?? '')}" ${opts.type ? 'type="' + opts.type + '"' : ''} ${opts.attrs || ''} placeholder="${esc(opts.ph || '')}">`}</div>`;

    function placeSection(p) {
        const v = p || { name: '', code: '', destination: '', location: '', map_link: '', description: '', category: '', pay_mode: 'in_tour', jeep: 0, active: 1 };
        const cats = (D.categories || []).slice(); if (v.category && !cats.includes(v.category)) cats.push(v.category);
        return `<div class="sec" id="sec_place"><div class="sec-h"><b>1. Place details</b><span class="help">${p ? 'Last read ' + esc(fmtDate(D.on)) : 'A new place starts with no rate — it is held until one is entered.'}</span></div><div class="sec-b"><div class="row g-2" data-pform>
            ${field('Name *', 'name', v.name)}
            ${field('Code', 'code', v.code, { col: 'col-md-3', attrs: 'maxlength="60"' })}
            <div class="col-md-3"><label class="form-label">Category</label><select class="form-select form-select-sm" data-p="category"><option value="">—</option>${cats.map(c => `<option ${v.category === c ? 'selected' : ''}>${esc(c)}</option>`).join('')}</select></div>
            ${field('Destination / area', 'destination', v.destination, { col: 'col-md-4', attrs: 'list="dl_dest"' })}
            ${field('Location (address)', 'location', v.location, { col: 'col-md-8' })}
            ${field('Map link', 'map_link', v.map_link, { col: 'col-md-8', ph: 'https://maps.google.com/…' })}
            <div class="col-md-4"><label class="form-label">Paid</label><select class="form-select form-select-sm" data-p="pay_mode">${Object.entries(D.pay_modes).map(([k, t]) => `<option value="${k}" ${v.pay_mode === k ? 'selected' : ''}>${esc(t)}</option>`).join('')}</select></div>
            <div class="col-12"><label class="d-inline-flex gap-2 align-items-center" style="font-size:13px"><input type="checkbox" data-p="jeep" ${Number(v.jeep) === 1 ? 'checked' : ''}> <b>Safari park</b> — a jeep is hired here, charged per vehicle beside the entrance</label></div>
            ${field('Description for the client', 'description', v.description, { col: 'col-12', area: true })}
            <div class="col-12"><button class="btn btn-av btn-sm" id="mp_psave"><i class="bi bi-check2"></i> ${p ? 'Save place details' : 'Add the place'}</button>
                ${p ? `<button class="btn btn-light btn-sm ms-1" id="mp_use">${Number(p.active) === 1 ? 'Take out of use' : 'Put back in use'}</button>` : ''}</div>
        </div></div></div>`;
    }

    function rateRows(p, charge) {
        const rs = p.rates.filter(r => (r.charge || 'entrance') === charge), cur = charge === 'jeep' ? p.current_jeep : p.current;
        if (!rs.length) return `<div class="help">No ${charge === 'jeep' ? 'jeep' : 'entrance'} rate yet.</div>`;
        return `<table class="tbl"><thead><tr><th>Valid</th>${charge === 'jeep' ? '<th>Per vehicle</th>' : '<th>Adult / person</th><th>Child / person</th>'}<th>Note</th><th></th></tr></thead><tbody>
            ${rs.map(r => `<tr><td class="num">${esc(period(r))}${cur && cur.id === r.id ? ' <span class="pill pill-green">current</span>' : ''}</td>
                ${charge === 'jeep' ? `<td>${Number(r.free_entry) === 1 ? 'Free' : '<span class="money">' + esc(amt(r.currency, r.adult_rate)) + '</span>'}</td>` : `<td>${entranceText(r)}</td><td>${childText(r)}</td>`}
                <td class="help" style="min-width:260px">${esc(r.note || '')}</td>
                <td class="acts"><button class="btn btn-light btn-sm" data-redit="${r.id}">Edit</button><button class="btn btn-del btn-sm" data-roff="${r.id}" title="Switched off and kept">Switch off</button></td></tr>`).join('')}
            </tbody></table>`;
    }

    function ratesSection(p) {
        const park = Number(p.jeep) === 1, e = p.current, j = p.current_jeep;
        return `<div class="sec" id="sec_rates"><div class="sec-h"><b>2. Rates and children</b><span class="help">Rates on ${esc(fmtDate(D.on))}</span></div><div class="sec-b">
            <div class="rate-now">
                <div><span>Adult entrance / person</span><b>${e ? (Number(e.free_entry) === 1 ? 'Free' : esc(amt(e.currency, e.adult_rate))) : '—'}</b></div>
                <div><span>Child entrance / person</span><b>${e ? (Number(e.free_entry) === 1 ? 'Free' : (e.child_rate == null ? '—' : esc(amt(e.currency, e.child_rate)))) : '—'}</b></div>
                ${park ? `<div><span>Jeep / vehicle</span><b>${j ? (Number(j.free_entry) === 1 ? 'Free' : esc(amt(j.currency, j.adult_rate))) : '—'}</b></div>
                <div><span>Jeep capacity</span><b>${p.jeep_seats ? p.jeep_seats + ' passengers' : '—'}</b></div>` : ''}
                <div><span>Valid</span><b style="font-size:13px">${esc(period(e || j)) || '—'}</b></div>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="help mb-1"><b>Entrance rates</b> (per person)</div>${rateRows(p, 'entrance')}
                    ${park ? `<div class="help mb-1 mt-3"><b>Jeep rates</b> (per vehicle)</div>${rateRows(p, 'jeep')}` : ''}
                    ${p.rates_off.length ? `<div class="help mt-2">${p.rates_off.length} older rate(s) switched off and kept.</div>` : ''}
                </div>
                <div class="col-12">
                    <div class="help mb-1"><b>Children's ages</b> — used to work out each child's entrance</div>
                    <div class="row g-2" data-ageform>
                        ${field('Free under age', 'child_age_from', p.child_age_from, { col: 'col-6 col-md-3', type: 'number', attrs: 'min="0" max="17.99" step="0.01"', ns: 'g', ph: 'e.g. 3' })}
                        ${field('Child rate up to age', 'child_age_to', p.child_age_to, { col: 'col-6 col-md-3', type: 'number', attrs: 'min="0" max="17.99" step="0.01"', ns: 'g', ph: 'e.g. 11.99' })}
                        ${field('Age rule note', 'age_note', p.age_note, { col: 'col-12 col-md-6', ns: 'g', ph: 'As stated by the park / operator' })}
                        ${park ? field('Jeep capacity (passengers per jeep)', 'jeep_seats', p.jeep_seats, { col: 'col-6 col-md-3', type: 'number', attrs: 'min="1" max="60"', ns: 'g', ph: 'e.g. 6' }) : ''}
                        <div class="col-12 help" id="mp_agerule">${esc(p.age_rule || '')}</div>
                        <div class="col-12"><button class="btn btn-av btn-sm" id="mp_gsave"><i class="bi bi-check2"></i> Save ages${park ? ' and capacity' : ''}</button></div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="help mb-1"><b id="rf_title">Add a rate</b></div>
            <div class="row g-2" data-rform>
                <input type="hidden" data-f="id">
                <div class="col-6 col-md-3"><label class="form-label">Charge</label><select class="form-select form-select-sm" data-f="charge">${Object.entries(D.charges).filter(([k]) => k !== 'jeep' || park).map(([k]) => `<option value="${k}">${k === 'jeep' ? 'Jeep — per vehicle' : 'Entrance — per person'}</option>`).join('')}</select></div>
                <div class="col-6 col-md-2"><label class="form-label">Currency</label><select class="form-select form-select-sm" data-f="currency">${(D.currencies || ['USD', 'LKR']).map(c => `<option ${c === 'LKR' ? 'selected' : ''}>${c}</option>`).join('')}</select></div>
                <div class="col-6 col-md-2"><label class="form-label">Valid from</label><input type="date" class="form-control form-control-sm" data-f="valid_from"></div>
                <div class="col-6 col-md-2"><label class="form-label">Valid to</label><input type="date" class="form-control form-control-sm" data-f="valid_to"></div>
                <div class="col-6 col-md-3 d-flex align-items-end"><label class="d-inline-flex gap-2 align-items-center" style="font-size:12.5px"><input type="checkbox" data-f="free_entry"> Free for this period</label></div>
                <div class="col-6 col-md-3"><label class="form-label" data-adult-l>Adult rate / person</label><input type="number" min="0" step="0.01" class="form-control form-control-sm" data-f="adult_rate"></div>
                <div class="col-6 col-md-3" data-childwrap><label class="form-label">Child rate / person</label><input type="number" min="0" step="0.01" class="form-control form-control-sm" data-f="child_rate" placeholder="blank = no child rate"></div>
                <div class="col-12 col-md-6"><label class="form-label">Note</label><input class="form-control form-control-sm" data-f="note" maxlength="255" placeholder="Who gave the rate, foreign / SAARC…"></div>
                <div class="col-12 d-flex gap-3 flex-wrap"><label class="help d-inline-flex gap-1 align-items-center"><input type="checkbox" data-f="close_previous"> end the current rate the day before</label>
                    <label class="help d-inline-flex gap-1 align-items-center"><input type="checkbox" data-f="confirm_high"> the figure is right in USD</label></div>
                <div class="col-12"><button class="btn btn-av btn-sm" id="mp_rsave"><i class="bi bi-check2"></i> Save rate</button> <button class="btn btn-light btn-sm" id="mp_rnew" hidden>Add a new rate instead</button></div>
            </div>
        </div></div>`;
    }

    function partySection(p, role, n) {
        const pt = party(p, role) || { own: {}, supplier: null, supplier_id: null };
        const o = pt.own || {}, s = pt.supplier;
        const ph = f => s && s[f] ? 'From supplier: ' + s[f] : '';
        const title = role === 'jeep_operator' ? 'Jeep operator' : 'Entrance authority / ticket supplier';
        const tog = role === 'ticket_supplier' && Number(p.jeep) === 1 ? `<div class="col-12"><label class="d-inline-flex gap-2 align-items-center" style="font-size:13px;background:var(--av-green-soft);border-radius:8px;padding:6px 10px"><input type="checkbox" id="mp_together" ${Number(p.pay_together) === 1 ? 'checked' : ''}> <b>Paid together with the jeep</b> — one payment to the jeep operator covers the jeep and the entrance (one payment slip, both shown in the breakdown)</label></div>` : '';
        return `<div class="sec" id="sec_${role}"><div class="sec-h"><b>${n}. ${title}</b><span class="help">${pt.updated_at ? 'Saved ' + esc(String(pt.updated_at).slice(0, 16)) + ' by ' + esc(pt.updated_by || '') : 'Not saved yet'}</span></div><div class="sec-b"><div class="row g-2" data-party="${role}">${tog}
            <div class="col-12"><label class="form-label">Supplier record</label>
                <input type="hidden" data-q="supplier_id" value="${pt.supplier_id ?? ''}">
                <div data-linked>${s ? `<div class="linked"><i class="bi bi-link-45deg"></i> Linked to <b>${esc(s.name)}</b> <span class="mono">${esc(s.acc_no || '')}</span> — blank fields below use this record. <button class="btn btn-link btn-sm p-0 ms-2" data-unlink>Unlink</button></div>` : ''}</div>
                <input class="form-control form-control-sm mt-1" data-supq placeholder="${s ? 'Link a different supplier — type a name or account code' : 'Link a supplier — type a name or account code (e.g. Lucky Jeep)'}">
                <div class="sup-res" data-supres hidden></div></div>
            ${field('Business name', 'business_name', o.business_name, { ns: 'q', ph: ph('name') })}
            ${field('Contact person', 'contact_person', o.contact_person, { ns: 'q', ph: ph('contact') })}
            ${field('Phone', 'phone', o.phone, { col: 'col-md-4', ns: 'q', ph: ph('phone') })}
            ${field('WhatsApp', 'whatsapp', o.whatsapp, { col: 'col-md-4', ns: 'q', ph: ph('contact_mobile') })}
            ${field('E-mail', 'email', o.email, { col: 'col-md-4', ns: 'q', type: 'email', ph: ph('email') })}
            ${field('Address', 'address', o.address, { col: 'col-12', ns: 'q', ph: ph('address') })}
            ${field('Booking instructions', 'booking_instructions', o.booking_instructions, { col: 'col-md-6', ns: 'q', area: true, rows: 3, ph: role === 'jeep_operator' ? 'How far ahead to book, pick-up point, safari times…' : 'Where tickets are bought, documents needed…' })}
            ${field('Payment terms', 'payment_terms', o.payment_terms, { col: 'col-md-6', ns: 'q', area: true, rows: 3, ph: ph('payment_terms') || 'e.g. paid on the day by the guide; bank transfer 7 days before' })}
            <div class="col-12 help mt-2"><b>Bank details</b> — the account number is never shown in full here.</div>
            ${field('Account holder', 'account_holder', o.account_holder, { ns: 'q', ph: ph('account_holder') })}
            <div class="col-md-6"><label class="form-label">Account number</label><input class="form-control form-control-sm" data-q="account_number" autocomplete="off" value="" placeholder="${o.account_masked ? esc(o.account_masked) + ' saved — type to replace' : (s && s.account_masked ? 'From supplier: ' + esc(s.account_masked) : '')}">
                ${o.account_masked ? '<label class="help d-inline-flex gap-1 align-items-center mt-1"><input type="checkbox" data-q="clear_account"> remove the saved account number</label>' : ''}</div>
            ${field('Bank', 'bank', o.bank, { col: 'col-md-4', ns: 'q', ph: ph('bank') })}
            ${field('Branch', 'branch', o.branch, { col: 'col-md-4', ns: 'q', ph: ph('branch') })}
            ${field('Bank code', 'bank_code', o.bank_code, { col: 'col-md-2', ns: 'q', ph: ph('bank_code') })}
            ${field('Branch code', 'branch_code', o.branch_code, { col: 'col-md-2', ns: 'q', ph: ph('branch_code') })}
            ${field('SWIFT code', 'swift_code', o.swift_code, { col: 'col-md-3', ns: 'q' })}
            <div class="col-12"><button class="btn btn-av btn-sm" data-qsave="${role}"><i class="bi bi-check2"></i> Save ${title.toLowerCase()}</button></div>
        </div></div></div>`;
    }

    function namesSection(p, n) {
        return `<div class="sec" id="sec_names"><div class="sec-h"><b>${n}. Other names and duplicates</b></div><div class="sec-b">
            <div>${p.aliases.map(a => `<span class="chip">${esc(a.alias)}<button title="Take this other name off (kept)" data-aoff="${a.id}">&times;</button></span>`).join('') || '<span class="help">None yet.</span>'}</div>
            <div class="d-flex gap-2 mt-2"><input class="form-control form-control-sm" id="mp_anew" placeholder="Another name this place is written as" maxlength="190"><button class="btn btn-light btn-sm" id="mp_aadd">Add</button></div>
            <div class="d-flex gap-2 mt-3 flex-wrap align-items-center"><span class="help">Same place as</span><select class="form-select form-select-sm" id="mp_into" style="max-width:260px"><option value="">choose…</option>${D.places.filter(x => x.id !== p.id && Number(x.active) === 1 && x.merged_into == null).map(x => `<option value="${x.id}">${esc(x.name)}${x.code ? ' (' + esc(x.code) + ')' : ''}</option>`).join('')}</select><button class="btn btn-light btn-sm" id="mp_merge">Merge into it</button></div>
            <div class="help mt-2">${p.sources.length ? 'Imported from: ' + p.sources.map(s => esc(s.name) + (s.code ? ' (' + esc(s.code) + ')' : '')).join(', ') : 'Added in this master.'}</div>
        </div></div>`;
    }

    function openPlace(id, keepSection) {
        const p = id ? byId(id) : null;
        OPEN = p ? p.id : 0;
        $('mp_title').textContent = p ? p.name + (p.code ? ' (' + p.code + ')' : '') : 'Add a place';
        const park = p && Number(p.jeep) === 1;
        let n = 3;
        $('mp_body').innerHTML = p ? `<div class="sec-nav"><a href="#sec_place">Place details</a><a href="#sec_rates">Rates and children</a>${park ? '<a href="#sec_jeep_operator">Jeep operator</a>' : ''}<a href="#sec_ticket_supplier">Ticket supplier</a><a href="#sec_names">Other names</a></div>
            ${placeSection(p)}${ratesSection(p)}${park ? partySection(p, 'jeep_operator', n++) : ''}${partySection(p, 'ticket_supplier', n++)}${namesSection(p, n)}` : placeSection(null);
        $('mp_foot').textContent = p ? (p.used_all ? 'Used on ' + p.used_all + ' booking line(s): Delete will archive it.' : 'Never used in a booking.') : '';
        wireEditor(p);
        const m = bootstrap.Modal.getOrCreateInstance($('m_place'));
        if (!$('m_place').classList.contains('show')) m.show();
        if (keepSection) document.getElementById(keepSection)?.scrollIntoView({ block: 'start' });
        if (window.TfView && p) TfView.setParams({ place: p.id }, { push: !TfView.param('place') });   // a refresh opens the same place again
    }

    function chargeForm(f) {
        const jeep = f.querySelector('[data-f="charge"]').value === 'jeep';
        f.querySelector('[data-childwrap]').hidden = jeep;
        f.querySelector('[data-adult-l]').textContent = jeep ? 'Rate per vehicle' : 'Adult rate / person';
    }

    async function reloadKeep(section) { await load(); if (OPEN) openPlace(OPEN, section); }

    function wireEditor(p) {
        const body = $('mp_body');
        $('mp_psave').addEventListener('click', async () => {
            const v = { id: p ? p.id : undefined };
            body.querySelectorAll('[data-p]').forEach(x => v[x.dataset.p] = x.type === 'checkbox' ? (x.checked ? 1 : 0) : x.value);
            const j = await post('/place', v);
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); await load(); openPlace(j.data.id, p ? 'sec_place' : 'sec_rates');
        });
        if (!p) return;
        $('mp_use')?.addEventListener('click', async () => {
            const on = Number(p.active) === 1;
            if (on) { const ok = await Swal.fire({ title: 'Take ' + p.name + ' out of use?', text: 'Lines naming it are held until someone puts them right; it is not offered to staff.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Take out of use', confirmButtonColor: '#842484' }); if (!ok.isConfirmed) return; }
            const j = await post('/place', { id: p.id, active: on ? 0 : 1 });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); reloadKeep('sec_place');
        });
        // ages and capacity
        const agePreview = () => {
            const a = body.querySelector('[data-g="child_age_from"]').value, b = body.querySelector('[data-g="child_age_to"]').value;
            const parts = []; if (a !== '' && Number(a) > 0) parts.push('under ' + ageN(a) + ' years: free'); parts.push('child rate ' + (a !== '' && Number(a) > 0 ? 'from ' + ageN(a) + ' ' : '') + (b !== '' ? 'up to ' + ageN(b) + ' years' : '(no upper age)')); if (b !== '') parts.push('older: adult rate');
            $('mp_agerule').textContent = a === '' && b === '' ? 'No age rule set: every child of the party pays the child rate.' : parts.join('; ').replace(/^./, c => c.toUpperCase()) + '.';
        };
        body.querySelectorAll('[data-g="child_age_from"],[data-g="child_age_to"]').forEach(x => x.addEventListener('input', agePreview));
        $('mp_gsave').addEventListener('click', async () => {
            const v = { id: p.id };
            body.querySelectorAll('[data-g]').forEach(x => v[x.dataset.g] = x.value);
            const j = await post('/place', v);
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', 'Saved. Open inquiries take it the next time they are opened.'); reloadKeep('sec_rates');
        });
        // rates
        const f = body.querySelector('[data-rform]');
        f.querySelector('[data-f="charge"]').addEventListener('change', () => chargeForm(f)); chargeForm(f);
        $('mp_rsave').addEventListener('click', async e => {
            const v = { place_id: p.id };
            f.querySelectorAll('[data-f]').forEach(x => v[x.dataset.f] = x.type === 'checkbox' ? (x.checked ? 1 : 0) : x.value);
            if (!v.id) delete v.id;
            e.target.disabled = true; let j; try { j = await post('/rate', v); } finally { e.target.disabled = false; }
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); reloadKeep('sec_rates');
        });
        body.querySelectorAll('[data-redit]').forEach(b => b.addEventListener('click', () => {
            const r = p.rates.find(x => x.id === Number(b.dataset.redit));
            const set = (k, val) => { const x = f.querySelector(`[data-f="${k}"]`); if (x.type === 'checkbox') x.checked = !!Number(val); else x.value = val ?? ''; };
            set('id', r.id); set('charge', r.charge || 'entrance'); set('free_entry', r.free_entry); set('valid_from', r.valid_from); set('valid_to', r.valid_to); set('currency', r.currency);
            set('adult_rate', Number(r.free_entry) === 1 ? '' : r.adult_rate); set('child_rate', r.child_rate); set('note', r.note); set('close_previous', 0);
            f.querySelector('[data-f="charge"]').disabled = true; chargeForm(f);
            $('rf_title').textContent = 'Edit the ' + (r.charge === 'jeep' ? 'jeep' : 'entrance') + ' rate ' + period(r); $('mp_rnew').hidden = false;
            f.scrollIntoView({ block: 'center' });
        }));
        $('mp_rnew').addEventListener('click', () => openPlace(p.id, 'sec_rates'));
        body.querySelectorAll('[data-roff]').forEach(b => b.addEventListener('click', async () => {
            const ok = await Swal.fire({ title: 'Switch this rate off?', text: 'It is kept, not deleted. Days it covered then have no rate — quotations wait until another rate is entered.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Switch off', confirmButtonColor: '#b23a32' });
            if (!ok.isConfirmed) return;
            const j = await post('/rate/' + b.dataset.roff + '/off', {});
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); reloadKeep('sec_rates');
        }));
        // the jeep operator and the ticket supplier
        body.querySelectorAll('[data-party]').forEach(box => {
            const role = box.dataset.party, q = box.querySelector('[data-supq]'), res = box.querySelector('[data-supres]');
            let t = null;
            q.addEventListener('input', () => {
                clearTimeout(t);
                t = setTimeout(async () => {
                    if (q.value.trim().length < 2) { res.hidden = true; return; }
                    const j = await api('/suppliers?q=' + encodeURIComponent(q.value.trim()));
                    const list = (j.data || []);
                    res.innerHTML = list.map(s => `<button type="button" data-pick="${s.id}" data-name="${esc(s.name)}" data-acc="${esc(s.acc_no || '')}"><b>${esc(s.name)}</b> <span class="mono help">${esc(s.acc_no || '')}</span></button>`).join('') || '<div class="help p-2">No supplier matches.</div>';
                    res.hidden = false;
                    res.querySelectorAll('[data-pick]').forEach(b => b.addEventListener('click', () => {
                        box.querySelector('[data-q="supplier_id"]').value = b.dataset.pick;
                        box.querySelector('[data-linked]').innerHTML = `<div class="linked"><i class="bi bi-link-45deg"></i> Will link to <b>${esc(b.dataset.name)}</b> <span class="mono">${esc(b.dataset.acc)}</span> — press Save. <button class="btn btn-link btn-sm p-0 ms-2" data-unlink>Unlink</button></div>`;
                        res.hidden = true; q.value = ''; wireUnlink(box);
                    }));
                }, 250);
            });
            wireUnlink(box);
            box.querySelector('[data-qsave]').addEventListener('click', async () => {
                const v = { place_id: p.id, role };
                box.querySelectorAll('[data-q]').forEach(x => v[x.dataset.q] = x.type === 'checkbox' ? (x.checked ? 1 : 0) : x.value);
                const j = await post('/party', v);
                if (j.status !== 200) { toast('error', j.message); return; }
                toast('success', j.message); reloadKeep('sec_' + role);
            });
        });
        $('mp_together')?.addEventListener('change', async e => {
            const j = await post('/place', { id: p.id, pay_together: e.target.checked ? 1 : 0 });
            if (j.status !== 200) { toast('error', j.message); e.target.checked = !e.target.checked; return; }
            toast('success', e.target.checked ? 'Saved: the jeep operator is paid the jeep and the entrance together.' : 'Saved: the jeep and the entrance are paid separately.'); reloadKeep('sec_ticket_supplier');
        });
        // other names, merge
        $('mp_aadd').addEventListener('click', async () => {
            const j = await post('/alias', { place_id: p.id, alias: $('mp_anew').value });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); reloadKeep('sec_names');
        });
        body.querySelectorAll('[data-aoff]').forEach(b => b.addEventListener('click', async () => {
            const j = await post('/alias/' + b.dataset.aoff + '/off', {});
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); reloadKeep('sec_names');
        }));
        $('mp_merge').addEventListener('click', async () => {
            const into = Number($('mp_into').value); if (!into) { toast('error', 'Choose the place it is the same as.'); return; }
            const pi = byId(into);
            const ok = await Swal.fire({ title: 'Merge ' + p.name + ' into ' + pi.name + '?', text: p.name + ' is kept, out of use, and read as ' + pi.name + ' from now on.', icon: 'question', showCancelButton: true, confirmButtonText: 'Merge', confirmButtonColor: '#842484' });
            if (!ok.isConfirmed) return;
            const j = await post('/places/merge', { from: p.id, into });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); await load(); openPlace(into);
        });
    }
    function wireUnlink(box) {
        box.querySelector('[data-unlink]')?.addEventListener('click', () => { box.querySelector('[data-q="supplier_id"]').value = ''; box.querySelector('[data-linked]').innerHTML = '<div class="help">Not linked — press Save to keep it so.</div>'; });
    }

    // ---------------------------------------------------------------- the price check (the same rules the quotation uses, in the rate's currency)
    function priceCheckPlaces() {
        const sel = $('pc_place'), keep = sel.value;
        const live = D.places.filter(p => Number(p.active) === 1 && p.merged_into == null && p.status !== 'archived');
        sel.innerHTML = '<option value="">Choose a place…</option>' + live.map(p => `<option value="${p.id}">${esc(p.name)}${Number(p.jeep) === 1 ? ' — safari' : ''}</option>`).join('');
        if (keep && live.some(p => String(p.id) === keep)) sel.value = keep;
        priceCheck();
    }
    function pickRate(p, date, charge) {
        let hit = null;
        (p.rates || []).forEach(r => { if ((r.charge || 'entrance') !== charge) return; if (r.valid_from && r.valid_from > date) return; if (r.valid_to && r.valid_to < date) return; if (!hit || (r.valid_from || '') >= (hit.valid_from || '')) hit = r; });
        return hit;
    }
    function priceCheck() {
        const p = byId($('pc_place').value), out = $('pc_out');
        if (!p) { out.innerHTML = ''; return; }
        const date = $('pc_date').value || D.on, ad = Math.max(0, Number($('pc_ad').value || 0));
        const ages = $('pc_ages').value.split(/[,\s]+/).filter(x => x !== '').map(Number).filter(x => !isNaN(x));
        const e = pickRate(p, date, 'entrance'), j = Number(p.jeep) === 1 ? pickRate(p, date, 'jeep') : null;
        const lines = []; let total = 0, ok = true; const cur = (e || j || {}).currency || '';
        if (!e) { lines.push('<span class="missing">No entrance rate on ' + esc(fmtDate(date)) + '</span>'); ok = false; }
        else if (Number(e.free_entry) === 1) lines.push('Entrance: free');
        else {
            lines.push(`${ad} adult${ad === 1 ? '' : 's'} × ${amt(e.currency, e.adult_rate)} = <b>${amt(e.currency, ad * e.adult_rate)}</b>`); total += ad * e.adult_rate;
            ages.forEach((a, i) => {
                let what, v;
                if (p.child_age_from != null && a < p.child_age_from) { what = 'free (under ' + ageN(p.child_age_from) + ')'; v = 0; }
                else if (p.child_age_to != null && a > p.child_age_to) { what = 'adult rate (over ' + ageN(p.child_age_to) + ')'; v = e.adult_rate; }
                else if (e.child_rate == null) { what = '<span class="missing">no child rate</span>'; v = null; ok = false; }
                else { what = 'child rate'; v = e.child_rate; }
                lines.push(`child ${i + 1} (${ageN(a)} yrs): ${what}${v != null ? ' = <b>' + amt(e.currency, v) + '</b>' : ''}`); if (v != null) total += v;
            });
        }
        if (Number(p.jeep) === 1) {
            const typed = $('pc_jeeps').value, pax = ad + ages.length;
            const n = typed !== '' ? Number(typed) : (p.jeep_seats ? Math.ceil(pax / p.jeep_seats) : null);
            if (!j) { lines.push('<span class="missing">No jeep rate on ' + esc(fmtDate(date)) + '</span>'); ok = false; }
            else if (n == null) { lines.push('<span class="missing">Jeeps: give the number (jeep capacity not set)</span>'); ok = false; }
            else { const jr = Number(j.free_entry) === 1 ? 0 : j.adult_rate; lines.push(`${n} jeep${n === 1 ? '' : 's'}${typed === '' ? ' (' + pax + ' passengers ÷ ' + p.jeep_seats + ' seats)' : ''} × ${amt(j.currency, jr)} = <b>${amt(j.currency, n * jr)}</b>`); total += n * jr; }
        }
        out.innerHTML = `<div class="try-out" style="background:${ok ? 'var(--av-green-soft)' : 'var(--tf-red-soft)'}">${lines.map(l => '<div>' + l + '</div>').join('')}
            <div class="mt-1" style="font-size:14px">${ok ? 'Safari price for the party: <b>' + esc(amt(cur, total)) + '</b>' : '<b>Incomplete</b> — fill in what is missing in the master.'}</div>
            <div class="help mt-1">${esc(p.age_rule || '')}</div></div>`;
    }
    ['pc_place', 'pc_date', 'pc_ad', 'pc_ages', 'pc_jeeps'].forEach(id => $(id).addEventListener('input', priceCheck));

    // ---------------------------------------------------------------- the one-time import (read only) and conflicts
    function importBox() {
        const t = D.import || { counts: {}, rows: [] }, c = t.counts || {};
        const rows = (t.rows || []).filter(r => IMP === 'all' || (IMP === 'excursion' ? r.source === 'excursion' : (r.source === 'old_list' && (IMP === 'master' ? ['imported', 'merged', 'split'].includes(r.outcome) : r.outcome === IMP))));
        const chip = (k, label, n) => `<button class="btn btn-sm ${IMP === k ? 'btn-av' : 'btn-light'}" data-imp="${k}">${label} <b>${n || 0}</b></button>`;
        $('import_box').innerHTML = `<div class="tf-card"><div class="tf-card-h"><h6 style="color:var(--tf-muted)">One-time import from the old Place to Visit list</h6><button class="btn btn-light btn-sm" id="imp_toggle">${IMP_OPEN ? 'Hide' : 'Show'} (${c.old || 0} old records)</button></div>
            ${IMP_OPEN ? `<div class="tf-card-b"><div class="d-flex gap-1 flex-wrap mb-2">${chip('review', 'Held for review', c.review)}${chip('master', 'Into the master', (c.imported || 0) + (c.merged || 0) + (c.split || 0))}${chip('sightseeing', 'General sightseeing', c.sightseeing)}${chip('service', 'Meals / services', c.service)}${chip('excursion', 'Excursion Rates', c.excursion)}${chip('all', 'All', (c.old || 0) + (c.excursion || 0))}</div>
            <div style="overflow-x:auto;max-height:380px"><table class="tbl"><thead><tr><th>Old record</th><th>Its figure</th><th>What became of it</th></tr></thead><tbody>
            ${rows.map(r => { const [ot, oc] = OUTCOME[r.outcome] || [r.outcome, 'pill-grey']; return `<tr><td><b>${esc(r.name)}</b>${r.code ? ' <span class="mono help">' + esc(r.code) + '</span>' : ''}</td><td class="mono">${esc(r.figure || '')}</td><td><span class="pill ${oc}">${ot}</span>${r.place ? ` <span class="help">→ ${esc(r.place)}</span>` : ''}<div class="help">${esc(r.reason || '')}</div></td></tr>`; }).join('') || '<tr><td colspan="3" class="empty">Nothing here.</td></tr>'}
            </tbody></table></div></div>` : ''}</div>`;
        $('imp_toggle').addEventListener('click', () => { IMP_OPEN = !IMP_OPEN; importBox(); });
        document.querySelectorAll('[data-imp]').forEach(b => b.addEventListener('click', () => { IMP = b.dataset.imp; importBox(); }));
    }
    function conflictBox() {
        const c = D.conflicts || [];
        $('conflict_box').innerHTML = c.length ? `<div class="tf-card mb-3" style="border-left:3px solid var(--tf-amber)"><div class="tf-card-b"><b style="font-size:13px">Free visits this master also holds</b>
            ${c.map(x => `<div class="help">“${esc(x.free_name)}” is ${esc(x.place)}${x.code ? ' (' + esc(x.code) + ')' : ''}</div>`).join('')}</div></div>` : '';
    }

    async function load() {
        const j = await api('/data?on=' + encodeURIComponent($('l_on').value || ''));
        if (j.status !== 200) { toast('error', j.message || 'The master would not load.'); return; }
        D = j.data || D;
        if (!$('l_on').value) $('l_on').value = D.on;
        if (!$('pc_date').value) $('pc_date').value = D.on;
        const keep = $('l_dest').value;
        const dests = [...new Set(D.places.map(p => p.destination).filter(Boolean))].sort((a, b) => a.localeCompare(b));
        $('l_dest').innerHTML = '<option value="">All destinations</option>' + dests.map(x => `<option value="${esc(x)}">${esc(x)}</option>`).join('');
        $('l_dest').value = dests.includes(keep) ? keep : '';
        $('dl_dest').innerHTML = (D.destinations || []).map(x => `<option value="${esc(x)}">`).join('');
        importBox(); conflictBox(); render();
    }

    $('b_add').addEventListener('click', () => openPlace(0));
    $('l_q').addEventListener('input', render);
    $('l_status').addEventListener('change', render);
    $('l_dest').addEventListener('change', render);
    $('l_on').addEventListener('change', load);
    $('m_place').addEventListener('hidden.bs.modal', () => { OPEN = null; if (window.TfView && TfView.param('place')) TfView.setParams({ place: null }, { push: true }); });
    window.addEventListener('popstate', () => {
        const v = Number(new URLSearchParams(location.search).get('place') || 0);
        if (v && byId(v)) openPlace(v); else if ($('m_place').classList.contains('show')) bootstrap.Modal.getInstance($('m_place'))?.hide();
    });
    if (window.TfView) TfView.restoreList(false);
    load().then(() => { const v = Number(new URLSearchParams(location.search).get('place') || 0); if (v && byId(v)) openPlace(v); });
})();
</script>
</body>
</html>
