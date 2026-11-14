<?= view('layouts/head') ?>
<style>
    /* ---------- Meal Master (same tokens as the Tour Inquiry panel) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:960px; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .btn-primary { background:var(--tf-navy); border-color:var(--tf-navy); }
    .tf .btn-primary:hover { background:var(--tf-navy-2); border-color:var(--tf-navy-2); }
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .btn-light:hover { background:var(--tf-bg); }
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl td { padding:8px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr.off td { opacity:.55; }
    .tf .tbl tbody tr:hover { background:var(--tf-bg); }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .tf .filters .form-control, .tf .filters .form-select { max-width:200px; }
    .tf .sec { font-weight:600; font-size:12px; color:var(--tf-navy); text-transform:uppercase; letter-spacing:.04em; margin:14px 0 6px; }
    .tf .sec:first-child { margin-top:0; }
    .tf .leg { display:flex; gap:6px; align-items:center; margin-bottom:6px; }
    .tf .leg input { max-width:220px; } .tf .leg .legdef { display:inline-flex; align-items:center; gap:5px; font-size:12.5px; font-weight:600; color:#3F7F1F; white-space:nowrap; margin:0 4px; } .tf .leg .legdef input { max-width:none; }
    .tf .example { background:var(--tf-bg); border-radius:8px; padding:8px 10px; font-size:12px; }
    .tf dl.kv { display:grid; grid-template-columns:150px 1fr; gap:6px 12px; margin:0; font-size:12.5px; }
    .tf dl.kv dt { color:var(--tf-muted); font-weight:500; }
    .tf dl.kv dd { margin:0; }
    .tf .req::after { content:' *'; color:var(--tf-red); }
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
                        <h4>Meal Master</h4>
                        <div class="sub">Lunches and dinners outside the hotel for passengers on <b>Half Board</b> packages — the hotel gives breakfast and one more meal, and the other is arranged here. Each meal is one restaurant's lunch or dinner at one location for a period, with the quotation rate in USD, the actual amount the restaurant charges in LKR (on the booking voucher) and the taxes. Give it the <b>route legs</b> it serves (Colombo → Kandy …): once a tour's route is set, each day's lunch place is picked from here — a restaurant on the day's leg first, then the cheapest — and can be changed on the itinerary. The supplier is picked from the Supplier master<?= !empty($sees_supplier) ? ' (<a href="' . base_url($supplier_url ?? 'master_files/supplier') . '" target="_blank">Master Files → Suppliers</a>)' : '' ?>; a restaurant not there yet can be entered by its name and booked, and is paid once it is linked. A meal is switched off and kept, never deleted.</div>
                    </div>
                    <?php if (!empty($installed)): ?><button class="btn btn-primary btn-sm" id="b_add"><i class="bi bi-plus-lg"></i> Add a meal</button><?php endif; ?>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Inquiry tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> as the administrator and press Install now; this screen then works.</div>
                <?php else: ?>

                <div class="tf-card mb-3">
                    <div class="tf-card-h"><h6>Meals</h6>
                        <div class="filters">
                            <input class="form-control form-control-sm" id="l_q" placeholder="Search a meal: code, restaurant, town, cuisine, supplier…">
                            <select class="form-select form-select-sm" id="l_type"><option value="">Lunch and dinner</option><option value="lunch">Lunch</option><option value="dinner">Dinner</option></select>
                            <select class="form-select form-select-sm" id="l_loc"><option value="">Every location</option></select>
                            <select class="form-select form-select-sm" id="l_sup"><option value="">Every supplier</option></select>
                            <select class="form-select form-select-sm" id="l_valid" title="Validity, read on the date beside it">
                                <option value="">Any validity</option><option value="valid">Valid on</option><option value="future">Not yet valid on</option><option value="ended">Ended by</option>
                            </select>
                            <input type="date" class="form-control form-control-sm" id="l_on" style="max-width:150px" title="The date the validity is read on">
                            <select class="form-select form-select-sm" id="l_status"><option value="in_use">In use</option><option value="off">Switched off</option><option value="">In use and switched off</option></select>
                            <span class="help" id="l_count"></span>
                        </div></div>
                    <div class="tf-card-b" style="padding:0;overflow-x:auto">
                        <table class="tbl"><thead><tr><th>Code</th><th>Meal</th><th>Location &amp; route</th><th>Rates</th><th>Validity</th><th>Status</th><th>Used</th><th></th></tr></thead>
                        <tbody id="l_rows"><tr><td colspan="8" class="empty">Loading…</td></tr></tbody></table>
                    </div>
                </div>

                <div class="tf-card">
                    <div class="tf-card-h"><h6>Settings</h6><span class="help">Read by every inquiry's Meals card</span></div>
                    <div class="tf-card-b">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3"><label class="form-label help">An HB hotel gives breakfast and</label>
                                <select class="form-select form-select-sm" id="s_hb"><option value="dinner">Dinner</option><option value="lunch">Lunch</option></select>
                                <div class="help">unless a night of the inquiry says otherwise</div></div>
                            <div class="col-md-2"><label class="form-label help">Usual lunch time</label><input class="form-control form-control-sm" id="s_lunch" placeholder="12:30"></div>
                            <div class="col-md-2"><label class="form-label help">Usual dinner time</label><input class="form-control form-control-sm" id="s_dinner" placeholder="19:30"></div>
                            <div class="col-md-3"><label class="form-label help">Meal suppliers come from these Supplier master categories</label><div id="s_cats"></div></div>
                            <div class="col-md-2"><button class="btn btn-light btn-sm" id="s_save">Save settings</button></div>
                            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="s_auto"><label class="form-check-label" for="s_auto" style="font-size:12.5px">Pick the lunch place of each day of a tour automatically once its route is set — a restaurant on the day's route leg first, then in a town on the route, then where the party sleeps; the cheapest of those. A person's pick is never replaced.</label></div></div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade tf tf-skin" id="m_meal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="f_title">Add a meal</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" id="f_id">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="sec">The meal</div>
                <div class="row g-2">
                    <div class="col-md-5"><label class="form-label help">Meal Code <span class="text-muted">(blank: given — ML-0001…)</span></label><input class="form-control form-control-sm" id="f_code" maxlength="40" placeholder="ML-KDY-01"></div>
                    <div class="col-md-3"><label class="form-label help req">Meal Type</label><select class="form-select form-select-sm" id="f_type"><option value="lunch">Lunch</option><option value="dinner">Dinner</option></select></div>
                    <div class="col-md-4"><label class="form-label help">Status</label><div class="form-check form-switch mt-1"><input class="form-check-input" type="checkbox" role="switch" id="f_active" checked><label class="form-check-label" for="f_active" style="font-size:12.5px">Active — offered for bookings</label></div></div>
                    <div class="col-12"><label class="form-label help">Restaurant / meal supplier — from the Supplier master <span class="text-muted">(linked before it is paid)</span></label><select class="form-select form-select-sm" id="f_sup"></select>
                        <div class="help" id="f_supnote"></div></div>
                    <div class="col-md-7"><label class="form-label help">Restaurant name as the meal is sold <span class="text-muted">(blank: the supplier's name; needed when it is not in the Supplier master yet)</span></label><input class="form-control form-control-sm" id="f_rest" maxlength="190"></div>
                    <div class="col-md-5"><label class="form-label help req">Location (town)</label><input class="form-control form-control-sm" id="f_loc" list="dl_dest" maxlength="120" placeholder="Kandy, Sigiriya, Dambulla…" autocomplete="off"></div>
                    <div class="col-md-5"><label class="form-label help">Cuisine type</label><input class="form-control form-control-sm" id="f_cuisine" list="dl_cuisine" maxlength="80" placeholder="Sri Lankan, Asian, Continental…"></div>
                    <div class="col-md-7"><label class="form-label help">Meal description</label><input class="form-control form-control-sm" id="f_desc" maxlength="1000" placeholder="Rice &amp; curry buffet, set menu…"></div>
                </div>
                <details class="tf-more"><summary>More details <small>— address and contact</small></summary>
                <div class="sec">Address &amp; contact <button class="btn btn-light btn-sm ms-2" id="f_fill" type="button" title="Copy the address and phone from the Supplier master">From the supplier</button></div>
                <div class="row g-2">
                    <div class="col-12"><label class="form-label help">Address</label><input class="form-control form-control-sm" id="f_addr" maxlength="300"></div>
                    <div class="col-md-4"><label class="form-label help">Contact person</label><input class="form-control form-control-sm" id="f_cname" maxlength="120"></div>
                    <div class="col-md-4"><label class="form-label help">Phone</label><input class="form-control form-control-sm" id="f_cphone" maxlength="60"></div>
                    <div class="col-md-4"><label class="form-label help">Booking email <span class="text-muted">(the voucher goes here)</span></label><input class="form-control form-control-sm" id="f_cemail" maxlength="190"></div>
                </div>
                </details>
            </div>
            <div class="col-lg-6">
                <div class="sec">Rates</div>
                <div class="row g-2">
                    <div class="col-12 help" style="margin-bottom:-4px"><b>Quotation rate (USD)</b> — what the quotation is costed on</div>
                    <div class="col-md-4"><label class="form-label help req">Adult USD</label><input class="form-control form-control-sm mono" id="f_adult" inputmode="decimal"></div>
                    <div class="col-md-4"><label class="form-label help">Child USD <span class="text-muted">(blank: as adult; 0: free)</span></label><input class="form-control form-control-sm mono" id="f_child" inputmode="decimal"></div>
                    <div class="col-md-4"><label class="form-label help">Currency</label><input class="form-control form-control-sm" id="f_cur" value="USD" readonly></div>
                    <div class="col-12 help" style="margin-bottom:-4px"><b>Actual rate (LKR)</b> — what the restaurant charges; the booking voucher states it after confirmation, no conversion</div>
                    <div class="col-md-4"><label class="form-label help">Adult LKR</label><input class="form-control form-control-sm mono" id="f_alkr" inputmode="decimal" placeholder="blank: to enter"></div>
                    <div class="col-md-4"><label class="form-label help">Child LKR <span class="text-muted">(blank: as adult; 0: free)</span></label><input class="form-control form-control-sm mono" id="f_clkr" inputmode="decimal"></div>
                    <div class="col-md-4"></div>
                    <div class="col-md-4"><label class="form-label help">Applicable taxes %</label><input class="form-control form-control-sm mono" id="f_tax" inputmode="decimal" placeholder="0"></div>
                    <div class="col-md-8"><label class="form-label help">&nbsp;</label><div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="f_taxinc"><label class="form-check-label" for="f_taxinc" style="font-size:12.5px">The rates already include the taxes</label></div></div>
                    <div class="col-12"><label class="form-label help">Tax note</label><input class="form-control form-control-sm" id="f_taxnote" maxlength="190" placeholder="e.g. 10% service charge + 18% VAT"></div>
                    <div class="col-12"><div class="example" id="f_example"></div></div>
                    <div class="col-12" id="f_highwrap" hidden><div class="form-check"><input class="form-check-input" type="checkbox" id="f_high"><label class="form-check-label" for="f_high" style="font-size:12.5px">The amount is right (a USD rate above 500)</label></div></div>
                </div>
                <div class="sec">Validity</div>
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label help req">Valid From</label><input type="date" class="form-control form-control-sm" id="f_from"></div>
                    <div class="col-md-6"><label class="form-label help">Valid To <span class="text-muted">(blank: open)</span></label><input type="date" class="form-control form-control-sm" id="f_to"></div>
                </div>
                <details class="tf-more"><summary>More details <small>— route legs and remarks</small></summary>
                <div class="sec">Route legs it serves <span class="help" style="text-transform:none;letter-spacing:0;font-weight:400">— in the direction travelled</span></div>
                <div id="f_legs"></div>
                <div class="d-flex gap-2"><button class="btn btn-light btn-sm" id="f_addleg" type="button"><i class="bi bi-plus-lg"></i> Add a leg</button>
                    <button class="btn btn-light btn-sm" id="f_back" type="button" title="Add the way back of every leg (Kandy → Colombo for Colombo → Kandy)">Add the way back</button></div>
                <!-- tf-lunch-r1: dietary options, the child age band, the rate's approval -->
                <div class="sec">Dietary options, child ages and rate approval</div>
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label help">Dietary options</label><input class="form-control form-control-sm" id="f_diet" maxlength="255" placeholder="e.g. vegetarian, vegan, halal, gluten-free"></div>
                    <div class="col-md-3"><label class="form-label help">Child rate applies from age … to …</label><div class="d-flex gap-1 align-items-center"><input class="form-control form-control-sm" id="f_cfrom" inputmode="numeric" maxlength="2" placeholder="from"><span>–</span><input class="form-control form-control-sm" id="f_cto" inputmode="numeric" maxlength="2" placeholder="to"></div></div>
                    <div class="col-md-3"><label class="form-label help">Rate status</label><select class="form-select form-select-sm" id="f_rstat"><option value="approved">Approved</option><option value="pending">Awaiting approval — not offered</option></select></div>
                </div>
                <div class="sec">Remarks</div>
                <textarea class="form-control form-control-sm" id="f_remarks" rows="2" maxlength="1000"></textarea>
                </details>
            </div>
        </div>
        <datalist id="dl_dest"></datalist>
        <datalist id="dl_cuisine"><option value="Sri Lankan"><option value="Asian"><option value="Continental"><option value="Indian"><option value="Chinese"><option value="Seafood"><option value="Vegetarian"><option value="Buffet"></datalist>
    </div>
    <div class="modal-footer"><span class="help me-auto">* required</span><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="f_save"><i class="bi bi-check2"></i> Save</button></div>
</div></div></div>

<div class="modal fade tf tf-skin" id="m_view" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="v_title">Meal</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="v_body"></div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" id="v_edit">Edit</button><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'master_files/meal_master') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2600 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const $ = id => document.getElementById(id);
    const show = sel => { const el = document.querySelector(sel); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : new bootstrap.Modal(el)).show();
        // tf-stage1: a folded "More details" with a value in it opens, so nothing saved is hidden
        el.querySelectorAll('details.tf-more').forEach(d => { d.open = [...d.querySelectorAll('input,textarea,select')].some(x => (x.type === 'checkbox' ? x.checked : String(x.value || '').trim() !== '')) || !!d.querySelector('[data-leg]'); }); };
    const hide = sel => { const i = bootstrap.Modal.getInstance(document.querySelector(sel)); if (i) i.hide(); };
    const money = v => v == null ? '—' : Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtDate = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
    const today = () => { const d = new Date(); d.setMinutes(d.getMinutes() - d.getTimezoneOffset()); return d.toISOString().slice(0, 10); };
    let D = { meals: [], suppliers: [], settings: {}, all_cats: {}, supplier_cats: [], destinations: [] };
    let CURRENT = null;   // the meal open in the form or the view
    const STATUS = { valid: ['Valid', 'pill-green'], future: ['Not yet valid', 'pill-blue'], ended: ['Ended', 'pill-grey'], off: ['Switched off', 'pill-grey'], supplier: ['Supplier unavailable', 'pill-red'] };

    // ---------------------------------------------------------------- the list
    function render() {
        const q = $('l_q').value.trim().toLowerCase();
        const type = $('l_type').value, loc = $('l_loc').value, sup = $('l_sup').value, valid = $('l_valid').value, st = $('l_status').value;
        const rows = D.meals.filter(m => (!type || m.meal_type === type) && (!loc || m.location === loc) && (!sup || String(m.supplier_id) === sup)
            && (!st || (st === 'in_use' ? m.active : !m.active))
            && (!valid || (valid === 'valid' ? m.status === 'valid' : m.status === valid))
            && (!q || [m.code, m.restaurant, m.location, m.cuisine, m.description, m.remarks, m.route_text, m.supplier && m.supplier.name].join(' ').toLowerCase().includes(q)));
        $('l_count').textContent = D.meals.length ? rows.length + ' of ' + D.meals.length : '';
        $('l_rows').innerHTML = rows.map(m => { const [pt, pc] = STATUS[m.status] || [m.status, 'pill-grey'];
            return `<tr class="${m.active ? '' : 'off'}" data-row="${m.id}">
            <td class="mono"><b>${esc(m.code)}</b></td>
            <td><span class="pill ${m.meal_type === 'lunch' ? 'pill-amber' : 'pill-blue'}">${esc(m.type_name)}</span> <b>${esc(m.restaurant)}</b>
                <div class="help">${m.supplier ? esc(m.supplier.name) + (m.supplier.acc ? ' · ' + esc(m.supplier.acc) : '') : (m.supplier_id ? '<span style="color:var(--tf-red)">supplier not in the Supplier master</span>' : '<span class="pill pill-amber" title="It can be booked; it is paid only once it is linked to its record of the Supplier master">supplier account to link</span>')}${m.cuisine ? ' · ' + esc(m.cuisine) : ''}</div>
                ${m.description ? `<div class="help">${esc(m.description.length > 120 ? m.description.slice(0, 120) + '…' : m.description)}</div>` : ''}</td>
            <td><b>${esc(m.location)}</b>${m.routes.length ? `<div class="help">${m.routes.map(r => esc(r.from) + ' → ' + esc(r.to) + (r.via ? ' <span title="passing through">via ' + esc(r.via) + '</span>' : '') + (r.default ? ' <b style="color:#3F7F1F">· default</b>' : '')).join('<br>')}</div>` : '<div class="help">no route legs</div>'}${m.rate_status === 'pending' ? '<div class="help" style="color:#C8101A;font-weight:700">Rate awaiting approval — not offered</div>' : ''}</td>
            <td class="mono">${esc(m.currency)} ${money(m.adult_rate)}<div class="help">${m.child_rate == null ? 'child as adult' : (Number(m.child_rate) === 0 ? 'children free' : 'child ' + money(m.child_rate))}<br>${esc(m.tax_text)}</div>
                ${m.adult_lkr != null ? `<div>LKR ${money(m.adult_lkr)}</div><div class="help">${m.child_lkr == null ? 'child as adult' : (Number(m.child_lkr) === 0 ? 'children free' : 'child ' + money(m.child_lkr))}</div>` : '<span class="pill pill-amber" title="The amount the restaurant charges, for the booking voucher">LKR rate to enter</span>'}</td>
            <td>${fmtDate(m.valid_from)} –<br>${m.valid_to ? fmtDate(m.valid_to) : '<span class="help">open</span>'}</td>
            <td><span class="pill ${pc}" title="${esc(m.status_text)}">${pt}</span></td>
            <td>${m.used && m.used.lines ? `<span class="pill pill-blue" title="Lines on inquiries linked to this meal">${m.used.lines} line${m.used.lines === 1 ? '' : 's'}</span>` : '<span class="help">—</span>'}</td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-view="${m.id}">View</button> <button class="btn btn-light btn-sm" data-edit="${m.id}">Edit</button>
                ${m.active ? `<button class="btn btn-light btn-sm" data-off="${m.id}" title="Switch off — kept, never deleted">Switch off</button>` : `<button class="btn btn-light btn-sm" data-on="${m.id}">Switch on</button>`}</td></tr>`; }).join('')
            || `<tr><td colspan="8" class="empty">${D.meals.length ? 'Nothing matches.' : 'No meals yet. Press Add a meal.'}</td></tr>`;
        document.querySelectorAll('#l_rows [data-view]').forEach(b => b.addEventListener('click', () => openView(Number(b.dataset.view))));
        document.querySelectorAll('#l_rows [data-edit]').forEach(b => b.addEventListener('click', () => openForm(D.meals.find(x => x.id === Number(b.dataset.edit)))));
        document.querySelectorAll('#l_rows [data-off]').forEach(b => b.addEventListener('click', () => setActive(Number(b.dataset.off), false)));
        document.querySelectorAll('#l_rows [data-on]').forEach(b => b.addEventListener('click', () => setActive(Number(b.dataset.on), true)));
    }
    async function setActive(id, on) {
        const m = D.meals.find(x => x.id === id);
        if (!on) {
            const ok = await Swal.fire({ title: 'Switch off ' + m.code + '?', text: m.title + ' is kept, never deleted, and no longer offered for a booking.' + (m.used && m.used.lines ? ' The ' + m.used.lines + ' line(s) linked to it on open inquiries are held until another meal is picked.' : ''), icon: 'warning', showCancelButton: true, confirmButtonText: 'Switch off' });
            if (!ok.isConfirmed) return;
        }
        const j = await post('/meal/' + id + '/active', { active: on ? 1 : 0 });
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); load();
    }
    function fillFilters() {
        const keep = { loc: $('l_loc').value, sup: $('l_sup').value };
        const locs = [...new Set(D.meals.map(m => m.location))].sort((a, b) => a.localeCompare(b));
        $('l_loc').innerHTML = '<option value="">Every location</option>' + locs.map(l => `<option value="${esc(l)}">${esc(l)}</option>`).join('');
        const sups = [...new Map(D.meals.filter(m => m.supplier).map(m => [m.supplier_id, m.supplier.name])).entries()].sort((a, b) => a[1].localeCompare(b[1]));
        $('l_sup').innerHTML = '<option value="">Every supplier</option>' + sups.map(([id, n]) => `<option value="${id}">${esc(n)}</option>`).join('');
        if (locs.includes(keep.loc)) $('l_loc').value = keep.loc;
        if (sups.some(([id]) => String(id) === keep.sup)) $('l_sup').value = keep.sup;
    }
    async function load() {
        const j = await api('/data?on=' + encodeURIComponent($('l_on').value || today()));
        if (j.status !== 200) { $('l_rows').innerHTML = `<tr><td colspan="8" class="empty">${esc(j.message || 'Could not load.')}</td></tr>`; return; }
        D = j.data;
        $('dl_dest').innerHTML = (D.destinations || []).map(x => `<option value="${esc(x)}">`).join('');
        fillFilters(); render(); fillSettings();
    }

    // ---------------------------------------------------------------- the form (Add / Edit)
    function supplierOptions(cur) {
        const cats = D.supplier_cats || [];
        let html = '<option value="">— not in the Supplier master yet (linked later, paid once linked) —</option>';
        cats.forEach(c => {
            const list = D.suppliers.filter(s => s.cat === c);
            if (!list.length) return;
            html += `<optgroup label="${esc(D.all_cats[c] || 'Category ' + c)}">` + list.map(s => `<option value="${s.id}" ${s.available || s.id === cur ? '' : 'disabled'}>${esc(s.name)}${s.acc ? ' · ' + esc(s.acc) : ''}${s.available ? '' : ' (unavailable)'}</option>`).join('') + '</optgroup>';
        });
        if (cur && !D.suppliers.some(s => s.id === cur)) html += `<option value="${cur}">(supplier ${cur} — not in an allowed category or no longer in the Supplier master)</option>`;
        return html;
    }
    function legRow(l) {
        const div = document.createElement('div');
        div.className = 'leg';
        div.innerHTML = `<input class="form-control form-control-sm" data-lf list="dl_dest" placeholder="From" value="${esc(l ? l.from : '')}" maxlength="120" autocomplete="off"><span>→</span>
            <input class="form-control form-control-sm" data-lt list="dl_dest" placeholder="To" value="${esc(l ? l.to : '')}" maxlength="120" autocomplete="off">
            <input class="form-control form-control-sm" data-lv placeholder="Passing through (e.g. Matale)" value="${esc(l && l.via ? l.via : '')}" maxlength="160" autocomplete="off" title="The stops this route passes, so the restaurant is offered only where it suits">
            <label class="legdef" title="The default restaurant of this route: put on a day automatically. One default per route; the others are the approved alternatives."><input type="checkbox" data-ld ${l && l.default ? 'checked' : ''}> Default</label>
            <button class="btn btn-light btn-sm" type="button" title="Take this leg off" data-lx>&times;</button>`;
        div.querySelector('[data-lx]').addEventListener('click', () => div.remove());
        $('f_legs').appendChild(div);
    }
    function legs() { return [...document.querySelectorAll('#f_legs .leg')].map(d => ({ from: d.querySelector('[data-lf]').value.trim(), to: d.querySelector('[data-lt]').value.trim(), via: d.querySelector('[data-lv]').value.trim(), default: d.querySelector('[data-ld]').checked ? 1 : 0 })).filter(l => l.from || l.to); }
    function example() {
        const a = parseFloat($('f_adult').value), cRaw = $('f_child').value.trim(), c = cRaw === '' ? a : parseFloat(cRaw), t = parseFloat($('f_tax').value) || 0, inc = $('f_taxinc').checked, cur = $('f_cur').value;
        $('f_highwrap').hidden = !(cur === 'USD' && (a > 500 || c > 500));
        if (!(a > 0)) { $('f_example').innerHTML = 'The cost of a meal on a booking: adults × adult rate + children × child rate + the taxes.'; return; }
        const sub = 2 * a + 1 * (isNaN(c) ? a : c), tax = inc ? 0 : Math.round(sub * t) / 100;
        const la = parseFloat($('f_alkr').value), lcRaw = $('f_clkr').value.trim(), lc = lcRaw === '' ? la : parseFloat(lcRaw);
        const ls = 2 * la + 1 * (isNaN(lc) ? la : lc), lt = inc ? 0 : Math.round(ls * t) / 100;
        $('f_example').innerHTML = `For 2 adults and 1 child — quotation: 2 × ${money(a)} + 1 × ${money(isNaN(c) ? a : c)}${tax ? ' + taxes ' + t + '% ' + money(tax) : (inc && t ? ' (taxes included)' : '')} = <b>${esc(cur)} ${money(sub + tax)}</b>`
            + (la > 0 ? `<br>Booking voucher to the restaurant: 2 × ${money(la)} + 1 × ${money(isNaN(lc) ? la : lc)}${lt ? ' + taxes ' + money(lt) : ''} = <b>LKR ${money(ls + lt)}</b>` : '<br><span style="color:var(--tf-amber)">No LKR rate yet: no booking voucher until it is entered.</span>');
    }
    function openForm(m) {
        CURRENT = m || null;
        $('f_title').textContent = m ? 'Edit ' + m.code + ' — ' + m.restaurant : 'Add a meal';
        $('f_id').value = m ? m.id : '';
        $('f_code').value = m ? m.code : '';
        $('f_type').value = m ? m.meal_type : 'lunch';
        $('f_active').checked = m ? !!m.active : true;
        $('f_sup').innerHTML = supplierOptions(m ? m.supplier_id : null);
        $('f_sup').value = m && m.supplier_id ? String(m.supplier_id) : '';
        $('f_rest').value = m ? m.restaurant : '';
        $('f_loc').value = m ? m.location : '';
        $('f_cuisine').value = m ? (m.cuisine || '') : '';
        $('f_desc').value = m ? (m.description || '') : '';
        $('f_addr').value = m ? (m.address || '') : '';
        $('f_cname').value = m ? (m.contact_name || '') : '';
        $('f_cphone').value = m ? (m.contact_phone || '') : '';
        $('f_cemail').value = m ? (m.contact_email || '') : '';
        $('f_adult').value = m ? Number(m.adult_rate).toFixed(2) : '';
        $('f_child').value = m && m.child_rate != null ? Number(m.child_rate).toFixed(2) : '';
        $('f_cur').value = 'USD';
        $('f_alkr').value = m && m.adult_lkr != null ? Number(m.adult_lkr).toFixed(2) : '';
        $('f_clkr').value = m && m.child_lkr != null ? Number(m.child_lkr).toFixed(2) : '';
        $('f_tax').value = m && Number(m.tax_pct) ? Number(m.tax_pct) : '';
        $('f_taxinc').checked = m ? !!m.tax_included : false;
        $('f_taxnote').value = m ? (m.tax_note || '') : '';
        $('f_from').value = m ? m.valid_from : '';
        $('f_to').value = m ? (m.valid_to || '') : '';
        $('f_remarks').value = m ? (m.remarks || '') : '';
        $('f_high').checked = false;
        $('f_legs').innerHTML = '';
        (m && m.routes.length ? m.routes : [null]).forEach(legRow);
        $('f_diet').value = (m && m.dietary) || ''; $('f_cfrom').value = m && m.child_from != null ? m.child_from : ''; $('f_cto').value = m && m.child_to != null ? m.child_to : ''; $('f_rstat').value = (m && m.rate_status) || 'approved';   // tf-lunch-r1
        supNote(); example();
        show('#m_meal');
    }
    function supNote() {
        const s = D.suppliers.find(x => String(x.id) === $('f_sup').value);
        $('f_supnote').innerHTML = s ? `${esc(s.cat_name)}${s.acc ? ' · account ' + esc(s.acc) : ''}${s.phone ? ' · ' + esc(s.phone) : ''}${s.available ? '' : ' · <span style="color:var(--tf-red)">marked unavailable in the Supplier master</span>'}` : 'Not in the Supplier master yet: give the restaurant\'s name below. It can be booked; it is paid only once it is linked here to its record (added under Master Files → Suppliers, category Restaurant &amp; Other Suppliers).';
    }
    $('f_sup')?.addEventListener('change', () => { supNote(); const s = D.suppliers.find(x => String(x.id) === $('f_sup').value); if (s && !$('f_rest').value.trim()) $('f_rest').placeholder = s.name; });
    $('f_fill')?.addEventListener('click', () => {
        const s = D.suppliers.find(x => String(x.id) === $('f_sup').value);
        if (!s) { toast('info', 'Pick the supplier first.'); return; }
        if (s.address) $('f_addr').value = s.address;
        if (s.phone) $('f_cphone').value = s.phone;
        if (s.email) $('f_cemail').value = s.email;
        if (!$('f_rest').value.trim()) $('f_rest').value = s.name;
    });
    ['f_adult', 'f_child', 'f_alkr', 'f_clkr', 'f_tax', 'f_taxinc', 'f_cur'].forEach(k => $(k)?.addEventListener('input', example));
    $('f_taxinc')?.addEventListener('change', example);
    $('f_cur')?.addEventListener('change', example);
    $('f_addleg')?.addEventListener('click', () => legRow(null));
    $('f_back')?.addEventListener('click', () => {
        const have = legs();
        const key = l => (l.from + '>' + l.to).toLowerCase();
        const seen = new Set(have.map(key));
        have.filter(l => l.from && l.to).forEach(l => { const r = { from: l.to, to: l.from }; if (!seen.has(key(r))) { seen.add(key(r)); legRow(r); } });
    });
    $('f_save')?.addEventListener('click', async () => {
        const btn = $('f_save');
        if (btn.disabled) return;
        btn.disabled = true;
        let j;
        try {
            j = await post('/save', {
                id: $('f_id').value || null, code: $('f_code').value, meal_type: $('f_type').value, supplier_id: $('f_sup').value || null, restaurant: $('f_rest').value,
                location: $('f_loc').value, cuisine: $('f_cuisine').value, description: $('f_desc').value, address: $('f_addr').value, contact_name: $('f_cname').value,
                contact_phone: $('f_cphone').value, contact_email: $('f_cemail').value, adult_rate: $('f_adult').value, child_rate: $('f_child').value, currency: 'USD', adult_lkr: $('f_alkr').value, child_lkr: $('f_clkr').value,
                tax_pct: $('f_tax').value, tax_included: $('f_taxinc').checked ? 1 : 0, tax_note: $('f_taxnote').value, valid_from: $('f_from').value, valid_to: $('f_to').value,
                active: $('f_active').checked ? 1 : 0, remarks: $('f_remarks').value, dietary: $('f_diet').value, child_from: $('f_cfrom').value, child_to: $('f_cto').value, rate_status: $('f_rstat').value, routes: legs(), confirm_high: $('f_high').checked ? 1 : 0,
            });
        } finally { btn.disabled = false; }
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_meal'); toast('success', j.message); load();
    });

    // ---------------------------------------------------------------- the view
    async function openView(id) {
        const j = await api('/meal/' + id);
        if (j.status !== 200) { toast('error', j.message); if (window.TfView) TfView.setParams({ meal: null }); return; }
        if (window.TfView) TfView.setParams({ meal: id }, { push: !TfView.param('meal') });   // tf-refresh-r1
        const m = j.data; CURRENT = m;
        const [pt, pc] = STATUS[m.status] || [m.status, 'pill-grey'];
        $('v_title').textContent = m.code + ' — ' + m.title;
        const s = m.supplier;
        $('v_body').innerHTML = `<dl class="kv">
            <dt>Meal ID / Code</dt><dd class="mono">#${m.id} · <b>${esc(m.code)}</b></dd>
            <dt>Meal Type</dt><dd>${esc(m.type_name)}</dd>
            <dt>Status</dt><dd><span class="pill ${pc}">${pt}</span> <span class="help">${esc(m.status_text)}</span></dd>
            <dt>Restaurant</dt><dd><b>${esc(m.restaurant)}</b></dd>
            <dt>Supplier</dt><dd>${s ? esc(s.name) + (s.acc ? ' · <span class="mono">' + esc(s.acc) + '</span>' : '') + ' · ' + esc(s.cat_name) + (s.available ? '' : ' · <span class="pill pill-red">unavailable</span>') : '<span class="pill pill-red">not in the Supplier master</span>'}</dd>
            <dt>Location</dt><dd>${esc(m.location)}</dd>
            <dt>Address</dt><dd>${esc(m.address || (s && s.address) || '—')}</dd>
            <dt>Contact</dt><dd>${esc([m.contact_name, m.contact_phone, m.contact_email].filter(Boolean).join(' · ') || '—')}</dd>
            <dt>Cuisine</dt><dd>${esc(m.cuisine || '—')}</dd>
            <dt>Meal description</dt><dd>${esc(m.description || '—')}</dd>
            <dt>Quotation rate</dt><dd class="mono">${esc(m.currency)} ${money(m.adult_rate)} an adult · ${m.child_rate == null ? 'a child as an adult' : (Number(m.child_rate) === 0 ? 'children free' : esc(m.currency) + ' ' + money(m.child_rate) + ' a child')}</dd>
            <dt>Actual rate (LKR)</dt><dd class="mono">${m.adult_lkr != null ? 'LKR ' + money(m.adult_lkr) + ' an adult · ' + (m.child_lkr == null ? 'a child as an adult' : (Number(m.child_lkr) === 0 ? 'children free' : 'LKR ' + money(m.child_lkr) + ' a child')) : '<span class="pill pill-amber">to enter — no booking voucher until it is</span>'}</dd>
            <dt>Booking email</dt><dd>${m.contact_email ? esc(m.contact_email) : '<span class="help">none — the Supplier master\'s, if it has one</span>'}</dd>
            <dt>Applicable taxes</dt><dd>${esc(m.tax_text)}</dd>
            <dt>Valid</dt><dd>${fmtDate(m.valid_from)} – ${m.valid_to ? fmtDate(m.valid_to) : 'open'}</dd>
            <dt>Route legs</dt><dd>${m.routes.length ? m.routes.map(r => esc(r.from) + ' → ' + esc(r.to)).join('<br>') : '<span class="help">none — offered by its location only</span>'}</dd>
            <dt>Remarks</dt><dd style="white-space:pre-line">${esc(m.remarks || '—')}</dd>
            <dt>On bookings</dt><dd>${m.used && m.used.lines ? m.used.lines + ' line(s) on inquiries' + (m.used.confirmed ? ', ' + m.used.confirmed + ' on confirmed tours' : '') : 'not used yet'}</dd>
            <dt>Added</dt><dd class="help">${esc(m.created_by || '—')} ${m.created_at ? '· ' + esc(m.created_at) : ''}</dd>
            <dt>Last changed</dt><dd class="help">${esc(m.updated_by || '—')} ${m.updated_at ? '· ' + esc(m.updated_at) : ''}</dd>
        </dl>
        ${(m.history || []).length ? `<div class="sec mt-3">History</div><table class="tbl"><tbody>${m.history.map(h => `<tr><td class="help" style="white-space:nowrap">${esc(h.at)}</td><td class="help">${esc(h.by || '')}</td><td>${esc(h.summary || '')}</td></tr>`).join('')}</tbody></table>` : ''}`;
        show('#m_view');
    }
    $('v_edit')?.addEventListener('click', () => { const m = CURRENT; hide('#m_view'); setTimeout(() => openForm(D.meals.find(x => x.id === m.id) || m), 250); });

    // ---------------------------------------------------------------- settings
    function fillSettings() {
        const s = D.settings || {};
        $('s_hb').value = s.meal_hb_default || 'dinner';
        $('s_lunch').value = s.meal_lunch_time || '';
        $('s_dinner').value = s.meal_dinner_time || '';
        $('s_auto').checked = String(s.meal_auto_lunch ?? '1') === '1';
        $('s_cats').innerHTML = Object.entries(D.all_cats || {}).map(([c, n]) => `<label class="help me-2" style="display:inline-flex;gap:4px;align-items:center"><input type="checkbox" data-cat="${c}" ${(D.supplier_cats || []).map(String).includes(String(c)) ? 'checked' : ''}> ${esc(n)}</label>`).join('');
    }
    $('s_save')?.addEventListener('click', async () => {
        const j = await post('/settings', { meal_hb_default: $('s_hb').value, meal_lunch_time: $('s_lunch').value, meal_dinner_time: $('s_dinner').value, meal_auto_lunch: $('s_auto').checked ? 1 : 0,
            meal_supplier_cats: [...document.querySelectorAll('#s_cats [data-cat]:checked')].map(x => Number(x.dataset.cat)) });
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); load();
    });

    $('b_add')?.addEventListener('click', () => openForm(null));
    ['l_q'].forEach(k => $(k).addEventListener('input', render));
    ['l_type', 'l_loc', 'l_sup', 'l_valid', 'l_status'].forEach(k => $(k).addEventListener('change', render));
    $('l_on').addEventListener('change', load);
    $('l_on').value = today();
    // tf-refresh-r1: the record open in the window is in the address (?meal=N) — a refresh opens it again, read fresh; Back closes it
    document.getElementById('m_view').addEventListener('hidden.bs.modal', () => { if (window.TfView && TfView.param('meal')) TfView.setParams({ meal: null }, { push: true }); });
    window.addEventListener('popstate', () => {
        const v = Number(new URLSearchParams(location.search).get('meal') || 0);
        const m = document.getElementById('m_view');
        if (v) { openView(v); } else if (m.classList.contains('show')) { bootstrap.Modal.getInstance(m)?.hide(); }
    });
    Promise.resolve(load()).then(() => { const v = Number(new URLSearchParams(location.search).get('meal') || 0); if (v) openView(v); });
})();
</script>
</body>
</html>
