<?= view('layouts/head') ?>
<style>
    /* ---------- Pricing (Management) — same tokens as the Tour Inquiry panel ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff;
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
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl td { padding:9px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr:last-child td { border-bottom:0; }
    .tf .tbl tbody tr:hover { background:var(--tf-bg); }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .mgmt { border-left:3px solid var(--tf-amber); }
    .tf .money { font-weight:600; }
    .tf .profit { color:var(--tf-green); font-weight:600; }
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
                        <h4>Pricing <span class="pill pill-amber align-middle">Management only</span></h4>
                        <div class="sub">The one place the markup lives. The client's price is the cost plus this percentage; the cost itself is every published rate, kilometre, bata and excursion added up on the inquiry, and nothing here changes any of those. Staff on the Quotation Desk see the client's price, the price per person and the party size — never the tour cost total, never the markup, never the profit. They do keep the supplier figure on each line, because the desks need it to do the work, so a determined person on the desk could still add those lines up and work the margin out; the figures are withheld, not proof against arithmetic. Set a company default here, and override it for one inquiry where you decide to. The rooms each party size is costed on in the pax matrix are kept here too.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Inquiry tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="tf-card mgmt">
                            <div class="tf-card-h"><h6>Company defaults</h6></div>
                            <div class="tf-card-b">
                                <label class="form-label help">Default markup on cost, %</label>
                                <div class="input-group input-group-sm"><input type="number" min="0" max="500" step="0.01" class="form-control" id="s_pct" placeholder="none set"><span class="input-group-text">%</span></div>
                                <div class="help mt-1">Blank means no default: every inquiry then needs its own markup before a client quotation can be produced.</div>
                                <label class="form-label help mt-3">A quotation is valid for</label>
                                <div class="input-group input-group-sm"><input type="number" min="1" max="365" class="form-control" id="s_days"><span class="input-group-text">days</span></div>
                                <label class="form-label help mt-3">Terms printed on the client quotation</label>
                                <textarea class="form-control form-control-sm" id="s_terms" rows="6"></textarea>
                                <div class="d-flex gap-2 mt-3"><button class="btn btn-primary btn-sm" id="s_save"><i class="bi bi-check2"></i> Save</button></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="tf-card mgmt">
                            <div class="tf-card-h"><h6>Open inquiries</h6><span class="help" id="l_note">Cost, markup and price, live from each inquiry</span></div>
                            <div class="tf-card-b" style="padding:0;overflow-x:auto">
                                <table class="tbl"><thead><tr><th>Inquiry</th><th>Agent · group</th><th>Pax</th><th>Cost USD</th><th>Markup</th><th>Price USD</th><th>Profit</th><th>Ready</th><th></th></tr></thead>
                                <tbody id="l_rows"><tr><td colspan="9" class="empty">Costing every open inquiry…</td></tr></tbody></table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-xl-7">
                        <div class="tf-card mgmt" id="px_card">
                            <div class="tf-card-h"><h6>Room basis by party size — the pax matrix</h6><span class="help">The rooms each size from 1 to 10 is costed on</span></div>
                            <div class="tf-card-b" style="padding:0;overflow-x:auto">
                                <table class="tbl"><thead><tr><th>Pax</th><th>Singles</th><th>Doubles</th><th>Twins</th><th>Triples</th><th>Sleeps</th><th>Last changed</th><th></th></tr></thead>
                                <tbody id="px_rows"><tr><td colspan="8" class="empty">Reading…</td></tr></tbody></table>
                            </div>
                            <div class="tf-card-b help" style="border-top:1px solid var(--tf-line)">Every size in the internal costing's pax matrix is priced night by night at the published single, double and triple rates of its hotels, on these rooms, and the agent's quotation shows the price per person for each size with the rooms in words. The rooms must sleep exactly the size: a single sleeps 1, a double or a twin 2, a triple 3. A hotel with no published rate for a room a size needs leaves that size "on request" — it is never guessed. The inquiry's own size is always priced on its own rooms. Quotations already issued keep the table they were sent with. Every change is logged.</div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade tf" id="m_markup" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mm_title">Markup</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help" id="mm_cost"></div>
        <label class="form-label help mt-2">Markup for this inquiry, %</label>
        <div class="input-group input-group-sm"><input type="number" min="0" max="500" step="0.01" class="form-control" id="mm_pct" placeholder="use the default"><span class="input-group-text">%</span></div>
        <label class="form-label help mt-2">Note</label>
        <input class="form-control form-control-sm" id="mm_note" placeholder="Why this one differs">
        <input type="hidden" id="mm_id">
        <div class="help mt-2">Blank puts the inquiry back on the company default. The figure is recorded with your name and the time.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="mm_ok">Save</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'tour/pricing') ?>';
    const INTAKE = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2200 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const show = sel => { const el = document.querySelector(sel); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : new bootstrap.Modal(el)).show(); };
    const hide = sel => { const i = bootstrap.Modal.getInstance(document.querySelector(sel)); if (i) i.hide(); };
    const $ = id => document.getElementById(id);
    const usd = v => v == null ? '—' : '$' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let ROWS = [];

    function render() {
        $('l_rows').innerHTML = ROWS.length ? ROWS.map(r => r.error ? `<tr><td class="mono">${esc(r.inquiry_no)}</td><td colspan="8" class="help">${esc(r.error)}</td></tr>` : `<tr>
            <td><a href="${INTAKE}?id=${r.id}" class="mono">${esc(r.inquiry_no)}</a><div class="help">${esc(r.status)}${r.arrival_date ? ' · ' + esc(r.arrival_date) : ''}${r.nights ? ' · ' + r.nights + ' n' : ''}</div></td>
            <td>${esc(r.agent_name || '—')}<div class="help">${esc(r.tour_name || '')}</div></td>
            <td>${r.pax || '—'}</td>
            <td class="mono money">${usd(r.cost_usd)}</td>
            <td>${r.markup.pct != null ? `<b>${Number(r.markup.pct).toFixed(2)}%</b> <span class="pill ${r.markup.source === 'inquiry' ? 'pill-blue' : 'pill-grey'}">${r.markup.source === 'inquiry' ? 'this inquiry' : 'default'}</span>${r.markup.set_by ? `<div class="help">${esc(r.markup.set_by)}</div>` : ''}${r.markup.note ? `<div class="help">“${esc(r.markup.note)}”</div>` : ''}` : '<span class="pill pill-red">not set</span>'}</td>
            <td class="mono money">${usd(r.markup.sell_usd)}${r.markup.per_person_usd != null ? `<div class="help">${usd(r.markup.per_person_usd)} pp</div>` : ''}</td>
            <td class="mono profit">${usd(r.markup.profit_usd)}</td>
            <td>${r.ready ? '<span class="pill pill-green">ready</span>' : `<span class="pill pill-amber">${r.blockers} to fix</span>`}</td>
            <td><button class="btn btn-light btn-sm" data-markup="${r.id}">Markup</button></td>
        </tr>`).join('') : '<tr><td colspan="9" class="empty">No open inquiries.</td></tr>';
        document.querySelectorAll('#l_rows [data-markup]').forEach(b => b.addEventListener('click', () => {
            const r = ROWS.find(x => Number(x.id) === Number(b.dataset.markup));
            $('mm_id').value = r.id; $('mm_title').textContent = 'Markup — ' + r.inquiry_no;
            $('mm_pct').value = r.markup.source === 'inquiry' ? r.markup.pct : '';
            $('mm_note').value = r.markup.note || '';
            $('mm_cost').textContent = 'Cost ' + usd(r.cost_usd) + (r.markup.pct != null ? ' · at ' + Number(r.markup.pct).toFixed(2) + '% the price is ' + usd(r.markup.sell_usd) : ' · no markup yet');
            show('#m_markup');
        }));
    }
    // ---------- the room basis by party size (schema 31) ----------
    let PX = [];
    const ROOMKEYS = ['sgl', 'dbl', 'twn', 'tpl'];
    const sleeps = r => Number(r.sgl || 0) + 2 * Number(r.dbl || 0) + 2 * Number(r.twn || 0) + 3 * Number(r.tpl || 0);
    function renderBasis() {
        $('px_rows').innerHTML = PX.length ? PX.map(b => `<tr data-pxrow="${b.pax}">
            <td class="mono"><b>${b.pax}</b></td>
            ${ROOMKEYS.map(k => `<td><input type="number" min="0" max="20" step="1" class="form-control form-control-sm" style="width:72px" data-px="${k}" value="${Number(b[k] || 0)}"></td>`).join('')}
            <td class="mono" data-pxsleeps>${sleeps(b)}</td>
            <td class="help">${b.updated_by ? esc(b.updated_by) + '<br>' + esc((b.updated_at || '').slice(0, 16)) : 'as installed'}</td>
            <td><button class="btn btn-light btn-sm" data-pxsave="${b.pax}">Save</button></td>
        </tr>`).join('') : '<tr><td colspan="8" class="empty">The room basis is not installed yet.</td></tr>';
        document.querySelectorAll('#px_rows tr[data-pxrow]').forEach(tr => {
            const pax = Number(tr.dataset.pxrow);
            const upd = () => {
                const r = {}; tr.querySelectorAll('[data-px]').forEach(i => { r[i.dataset.px] = Number(i.value || 0); });
                const n = sleeps(r), cell = tr.querySelector('[data-pxsleeps]');
                cell.textContent = n; cell.style.color = n === pax ? '' : 'var(--tf-red)'; cell.title = n === pax ? '' : 'The rooms must sleep exactly ' + pax;
            };
            tr.querySelectorAll('[data-px]').forEach(i => i.addEventListener('input', upd));
            tr.querySelector('[data-pxsave]').addEventListener('click', async () => {
                const body = {}; tr.querySelectorAll('[data-px]').forEach(i => { body['rooms_' + i.dataset.px] = i.value; });
                const j = await post('/pax_rooms/' + pax, body);
                if (j.status !== 200) { toast('error', j.message); return; }
                PX = j.data.pax_rooms || []; renderBasis(); toast('success', j.message);
            });
        });
    }

    async function load() {
        const j = await api('/data');
        if (j.status !== 200) { toast('error', j.message); return; }
        const s = j.data.settings;
        $('s_pct').value = s.markup_default_pct ?? ''; $('s_days').value = s.quote_validity_days; $('s_terms').value = s.quote_terms || '';
        ROWS = j.data.rows || [];
        render();
        PX = j.data.pax_rooms || [];
        renderBasis();
    }
    $('s_save').addEventListener('click', async () => {
        const j = await post('/settings', { markup_default_pct: $('s_pct').value, quote_validity_days: $('s_days').value, quote_terms: $('s_terms').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); load();
    });
    $('mm_ok').addEventListener('click', async () => {
        const j = await post('/markup/' + $('mm_id').value, { pct: $('mm_pct').value, note: $('mm_note').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_markup'); toast('success', j.message); load();
    });
    load();
})();
</script>
</body>
</html>
