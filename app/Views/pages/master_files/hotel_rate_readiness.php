<?= view('layouts/head') ?>
<style>
    /* ---------- Quotation readiness (tf-hr-ready-r1) — the Hotel Rates panel's tokens, read-only ---------- */
    .hrq { --q-navy:#1e3553; --q-teal:#0f8b7e; --q-amber:#b7791f; --q-amber-soft:#fdf3e1; --q-red:#b23a32; --q-red-soft:#fbe9e7;
           --q-green:#1f8a4c; --q-green-soft:#e4f4ea; --q-blue-soft:#e6eef8; --q-ink:#1d2733; --q-muted:#5f6f80; --q-line:#dfe5ec;
           --q-bg:#f4f6f9; --q-card:#fff; --q-radius:10px; color:var(--q-ink); }
    .hrq .q-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .hrq .q-title h4 { margin:0; font-weight:600; color:var(--q-navy); }
    .hrq .q-title .sub { color:var(--q-muted); font-size:12.5px; max-width:860px; }
    .hrq .q-links { display:flex; gap:6px; flex-wrap:wrap; }
    .hrq .q-card { background:var(--q-card); border:1px solid var(--q-line); border-radius:var(--q-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); margin-bottom:14px; }
    .hrq .q-card-h { padding:11px 14px; border-bottom:1px solid var(--q-line); display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .hrq .q-card-h h6 { margin:0; font-size:14px; font-weight:600; }
    .hrq .q-card-b { padding:12px 14px; }
    .hrq .q-alerts { border-color:var(--q-red); }
    .hrq .q-alerts .q-card-h { background:var(--q-red-soft); border-bottom-color:#f1cfca; border-radius:var(--q-radius) var(--q-radius) 0 0; }
    .hrq .q-alerts .q-card-h h6 { color:var(--q-red); letter-spacing:.02em; }
    .hrq .help { font-size:12px; color:var(--q-muted); }
    .hrq .mono { font-family:ui-monospace, Menlo, monospace; font-size:11.5px; white-space:nowrap; }
    .hrq .kpis { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px; margin-bottom:14px; }
    @media (max-width: 900px) { .hrq .kpis { grid-template-columns:1fr; } }
    .hrq .kpi { background:#fff; border:1px solid var(--q-line); border-left:5px solid var(--q-line); border-radius:var(--q-radius); padding:12px 14px; cursor:pointer; text-decoration:none; color:inherit; display:block; }
    .hrq .kpi:hover { background:var(--q-bg); }
    .hrq .kpi .v { font-size:28px; font-weight:700; line-height:1.1; }
    .hrq .kpi .l { font-size:13px; font-weight:600; }
    .hrq .kpi .s { font-size:11.5px; color:var(--q-muted); margin-top:2px; }
    .hrq .kpi.ok { border-left-color:var(--q-green); } .hrq .kpi.ok .v { color:var(--q-green); }
    .hrq .kpi.no { border-left-color:var(--q-red); } .hrq .kpi.no .v { color:var(--q-red); }
    .hrq .kpi.warn { border-left-color:var(--q-amber); } .hrq .kpi.warn .v { color:var(--q-amber); }
    .hrq .controls { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .hrq .controls label { font-size:12.5px; font-weight:600; margin:0; }
    .hrq .chips { display:flex; gap:6px; flex-wrap:wrap; }
    .hrq .chip { border:1px solid var(--q-line); background:#fff; border-radius:9px; padding:4px 10px; font-size:12px; font-weight:500; cursor:pointer; color:var(--q-ink); white-space:nowrap; }
    .hrq .chip .n { font-family:ui-monospace, Menlo, monospace; font-size:11px; color:var(--q-muted); margin-left:4px; }
    .hrq .chip.on { background:var(--q-blue-soft); border-color:transparent; color:var(--q-navy); font-weight:600; }
    .hrq .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .hrq .tbl th { text-align:left; font-weight:600; color:var(--q-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--q-line); white-space:nowrap; }
    .hrq .tbl td { padding:9px 10px; border-bottom:1px solid var(--q-line); vertical-align:top; }
    .hrq .tbl tr:last-child td { border-bottom:0; }
    .hrq .tbl tr:hover td { background:var(--q-bg); }
    .hrq .tbl td.num, .hrq .tbl th.num { text-align:right; }
    .hrq .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .hrq .pill-green { background:var(--q-green-soft); color:var(--q-green); }
    .hrq .pill-red { background:var(--q-red-soft); color:var(--q-red); }
    .hrq .pill-amber { background:var(--q-amber-soft); color:var(--q-amber); }
    .hrq .pill-blue { background:var(--q-blue-soft); color:var(--q-navy); }
    .hrq .pill-grey { background:#eef1f5; color:var(--q-muted); }
    .hrq .why { font-weight:600; }
    .hrq .why-d { font-size:12px; color:var(--q-muted); margin-top:2px; max-width:520px; }
    .hrq .notes { display:flex; flex-direction:column; gap:3px; align-items:flex-start; }
    .hrq .notes .pill { white-space:normal; text-align:left; }
    .hrq .empty { color:var(--q-muted); font-size:12.5px; padding:16px 0; text-align:center; }
    .hrq .not-installed { padding:14px 16px; border-radius:8px; background:var(--q-amber-soft); color:var(--q-amber); font-size:13px; }
    .hrq .loading { color:var(--q-muted); font-size:12px; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content hrq" id="hrq">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>

                <div class="q-title">
                    <div>
                        <h4>Quotation readiness</h4>
                        <div class="sub">Which hotels the quotation engine can price <b>by itself</b> on a date, and which it cannot — with the reason. Only an approved contract rate counts (contract, legacy, addendum or series): tactical, promotional and special offers are chosen by a person and never make a hotel quote-ready, and drafts count only once approved. A view only: nothing is changed from this page.</div>
                    </div>
                    <div class="q-links">
                        <a class="btn btn-light btn-sm" href="<?= base_url($panel_url ?? 'master_files/hotel_rate_v2') ?>"><i class="bi bi-arrow-left"></i> Hotel Rates</a>
                        <?php if (!empty($can_work)): ?><a class="btn btn-light btn-sm" href="<?= base_url($work_url ?? 'master_files/hotel_work') ?>"><i class="bi bi-list-check"></i> Hotel Incomplete Work</a><?php endif; ?>
                        <?php if (!empty($can_approve)): ?><a class="btn btn-light btn-sm" href="<?= base_url($approval_url ?? 'master_files/hotel_rate_approval') ?>"><i class="bi bi-inbox"></i> Approval queue</a><?php endif; ?>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Hotel Rates tables are not installed or not at the current version. Open <a href="<?= base_url($panel_url ?? 'master_files/hotel_rate_v2') ?>">Hotel Rates</a> first; this screen then works.</div>
                <?php else: ?>

                <div id="q_alerts" class="q-card q-alerts" style="display:none"></div>

                <div class="q-card"><div class="q-card-b controls">
                    <label for="q_date">Check date</label>
                    <input type="date" class="form-control form-control-sm" id="q_date" style="width:auto" value="<?= esc($today) ?>">
                    <div class="chips" id="q_quick">
                        <span class="chip" data-days="0">Today</span>
                        <span class="chip" data-days="30">+30 days</span>
                        <span class="chip" data-days="60">+60 days</span>
                        <span class="chip" data-days="90">+90 days</span>
                    </div>
                    <input type="text" class="form-control form-control-sm" id="q_search" placeholder="Search hotel, location or code…" style="width:240px">
                    <span class="help">Quotation as the engine asks it: approved plans only, booked today, one room for two, market <b><?= esc($default_market ?? 'JP') ?></b> (and every market a plan names).</span>
                    <span class="loading ms-auto" id="q_state"></span>
                </div></div>

                <div class="kpis">
                    <a class="kpi ok" href="#sec_ready"><div class="v" id="k_ready">—</div><div class="l">Hotels with quotation rates</div><div class="s" id="k_ready_s">&nbsp;</div></a>
                    <a class="kpi no" href="#sec_not"><div class="v" id="k_not">—</div><div class="l">Hotels without quotation rates</div><div class="s" id="k_not_s">&nbsp;</div></a>
                    <a class="kpi warn" href="#q_alerts"><div class="v" id="k_alerts">—</div><div class="l">Rate expiry warnings</div><div class="s" id="k_alerts_s">&nbsp;</div></a>
                </div>

                <div class="q-card" id="sec_ready">
                    <div class="q-card-h"><h6>A. Hotels with quotation rates <span class="pill pill-green" id="n_ready">0</span></h6><span class="help">The quotation engine prices these on <b class="q-on"></b> from an approved contract rate, with no one choosing a plan.</span></div>
                    <div class="table-responsive"><table class="tbl">
                        <thead><tr><th>Hotel</th><th>Quotation rate from</th><th>Markets</th><th>Covered until</th><th class="num" title="Hotel nights on quotations and tours over the next 180 days">Nights ahead</th><th>Also</th><th></th></tr></thead>
                        <tbody id="rows_ready"><tr><td colspan="7" class="empty">Loading…</td></tr></tbody>
                    </table></div>
                </div>

                <div class="q-card" id="sec_not">
                    <div class="q-card-h"><h6>B. Hotels without quotation rates <span class="pill pill-red" id="n_not">0</span></h6><span class="help">The quotation engine cannot price these on <b class="q-on"></b> by itself — the reason is on each line. Most-used hotels first.</span>
                        <div class="chips ms-auto" id="why_chips"></div></div>
                    <div class="table-responsive"><table class="tbl">
                        <thead><tr><th>Hotel</th><th>Why not</th><th>Also on file</th><th>Last rate ended / next starts</th><th class="num" title="Hotel nights on quotations and tours over the next 180 days">Nights ahead</th><th></th></tr></thead>
                        <tbody id="rows_not"><tr><td colspan="6" class="empty">Loading…</td></tr></tbody>
                    </table></div>
                </div>

                <div class="help mb-4">
                    <b>How this is decided.</b> A hotel is in list A when the quotation engine itself — asked exactly as a customer quotation asks it — returns a price for at least one room on the check date.
                    "Some markets only" means its contract names markets and a quotation from another market is not priced.
                    The rate-expiry warning starts <?= (int) ($alert_days ?? 14) ?> days before the last night an approved contract rate covers, stays as <i>Expired</i> after that night, shows when replacement rates have been received (a draft, or a sheet waiting to be loaded), and clears by itself once an approved contract rate covers the night after.
                </div>
                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(() => {
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;
    const BASE = '<?= base_url($page_url ?? 'master_files/hotel_rate_v2/readiness') ?>';
    const PANEL = '<?= base_url($panel_url ?? 'master_files/hotel_rate_v2') ?>';
    const WORK = '<?= base_url($work_url ?? 'master_files/hotel_work') ?>';
    const CAN_WORK = <?= !empty($can_work) ? 'true' : 'false' ?>;
    const TODAY = '<?= esc($today) ?>';
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }); };
    const pill = (lvl, t, title) => `<span class="pill pill-${esc(lvl || 'grey')}"${title ? ` title="${esc(title)}"` : ''}>${esc(t)}</span>`;
    const addDays = (d, n) => { const x = new Date(d + 'T00:00:00'); x.setDate(x.getDate() + n); return x.toISOString().slice(0, 10); };
    const S = { data: null, why: 'all', q: '' };
    const WHY_ORDER = ['replacement_draft', 'draft', 'sheet_waiting', 'no_supplier', 'expired', 'gap', 'not_started', 'offer_only', 'group_only', 'no_prices', 'no_contract'];
    const WHY_LABEL = { replacement_draft: 'Replacement awaiting approval', draft: 'Draft awaiting approval', sheet_waiting: 'Sheet waiting', no_supplier: 'No supplier record', expired: 'Expired', gap: 'Gap in dates', not_started: 'Not started yet', offer_only: 'Offer only', group_only: 'Groups only', no_prices: 'No usable rate', no_contract: 'No contract rate' };
    const open = (h, date) => h.id ? `<a class="btn btn-light btn-sm" href="${PANEL}?hotel=${encodeURIComponent(h.acc || '')}&date=${encodeURIComponent(date)}" title="Open this hotel in Hotel Rates on the check date">Open</a>`
        : (CAN_WORK ? `<a class="btn btn-light btn-sm" href="${WORK}" title="The sheet waits in Hotel Incomplete Work">Incomplete Work</a>` : '');
    const hotelCell = h => `<div class="fw-semibold">${esc(h.name)}</div><div class="help">${esc(h.location || '')}${h.location && h.acc ? ' · ' : ''}<span class="mono">${esc(h.acc || 'no supplier code')}</span></div>`;
    const match = h => !S.q || [h.name, h.acc, h.location, h.chain].join(' ').toLowerCase().includes(S.q);

    function renderAlerts(d) {
        const box = document.getElementById('q_alerts'); const list = d.alerts || [];
        if (!list.length) { box.style.display = 'none'; return; }
        box.innerHTML = `<div class="q-card-h"><h6><i class="bi bi-exclamation-octagon"></i> RATE EXPIRY WARNING <span class="pill pill-red">${list.length}</span></h6><span class="help">Starts ${d.alert_days} days before an approved contract rate ends; clears by itself when an approved rate covers the night after. Hotel Reservations: contact the hotel for the next contract.</span></div>
            <div class="table-responsive"><table class="tbl"><thead><tr><th>Status</th><th>Hotel</th><th>Contract rate ends</th><th>Replacement</th><th class="num">Nights ahead</th><th></th></tr></thead><tbody>
            ${list.map(a => `<tr><td>${pill(a.state === 'expired' ? 'red' : 'amber', a.state_text)}</td>
                <td><div class="fw-semibold">${esc(a.hotel)}</div><div class="help">${esc(a.location || '')} · <span class="mono">${esc(a.acc || '')}</span></div>${a.possible_duplicate ? `<div class="help">Possible duplicate — ${esc(a.possible_duplicate)} is quote-ready</div>` : ''}</td>
                <td><b>${esc(a.ends_text || a.ends)}</b><div class="help">${a.days > 0 ? 'in ' + a.days + ' day' + (a.days === 1 ? '' : 's') : (a.days === 0 ? 'today' : (-a.days) + ' day' + (a.days === -1 ? '' : 's') + ' ago')}</div></td>
                <td>${pill(a.replacement.key === 'required' ? 'red' : (a.replacement.key === 'gap' ? 'amber' : 'blue'), a.replacement.text)}<div class="why-d">${esc(a.replacement.detail || '')}</div></td>
                <td class="num">${a.upcoming_nights || 0}</td>
                <td class="text-end"><a class="btn btn-light btn-sm" href="${PANEL}?hotel=${encodeURIComponent(a.acc || '')}">Open</a></td></tr>`).join('')}
            </tbody></table></div>`;
        box.style.display = '';
    }

    function renderReady(d) {
        const rows = d.ready.filter(match);
        document.getElementById('rows_ready').innerHTML = rows.map(h => `<tr>
            <td>${hotelCell(h)}</td>
            <td>${pill(h.source && h.source.plan_type === 'migrated' ? 'grey' : 'green', h.source ? h.source.type_label : 'Contract')}<div class="why-d">${esc(h.source ? h.source.plan : '')}${h.source && h.source.season ? ' · ' + esc(h.source.season) : ''}</div></td>
            <td>${h.ready === 'all' ? pill('green', 'All markets') : pill('amber', h.markets.text)}</td>
            <td>${h.covered_until ? fmt(h.covered_until) : '<span class="help">beyond the next 400 days</span>'}${h.alert ? '<div>' + pill(h.alert.state === 'expired' ? 'red' : 'amber', h.alert.state_text) + '</div>' : ''}</td>
            <td class="num">${h.upcoming_nights || 0}</td>
            <td><div class="notes">${(h.notes || []).filter(n => n.key !== 'markets').map(n => pill(n.level, n.text)).join('')}</div></td>
            <td class="text-end">${open(h, d.date)}</td></tr>`).join('') || '<tr><td colspan="7" class="empty">No hotel here.</td></tr>';
    }

    function renderNot(d) {
        const counts = {}; d.not_ready.forEach(h => { counts[h.reason.key] = (counts[h.reason.key] || 0) + 1; });
        const keys = WHY_ORDER.filter(k => counts[k]);
        document.getElementById('why_chips').innerHTML = [`<span class="chip${S.why === 'all' ? ' on' : ''}" data-why="all">All<span class="n">${d.not_ready.length}</span></span>`]
            .concat(keys.map(k => `<span class="chip${S.why === k ? ' on' : ''}" data-why="${k}">${esc(WHY_LABEL[k] || k)}<span class="n">${counts[k]}</span></span>`)).join('');
        document.querySelectorAll('#why_chips [data-why]').forEach(c => c.addEventListener('click', () => { S.why = c.dataset.why; renderNot(S.data); }));
        const rows = d.not_ready.filter(h => (S.why === 'all' || h.reason.key === S.why) && match(h))
            .sort((a, b) => (b.upcoming_nights || 0) - (a.upcoming_nights || 0) || String(a.name).localeCompare(String(b.name)));
        document.getElementById('rows_not').innerHTML = rows.map(h => `<tr>
            <td>${hotelCell(h)}</td>
            <td>${pill(h.reason.level, h.reason.text)}<div class="why-d">${esc(h.reason.detail || '')}</div>${h.alert ? '<div class="mt-1">' + pill(h.alert.state === 'expired' ? 'red' : 'amber', 'Rate expiry warning: ' + h.alert.state_text) + '</div>' : ''}</td>
            <td><div class="notes">${(h.notes || []).map(n => pill(n.level, n.under ? 'Underlying: ' + n.text + (n.detail ? ' — ' + n.detail : '') : n.text)).join('') || '<span class="help">—</span>'}</div></td>
            <td class="text-nowrap">${h.prev_end ? 'ended ' + fmt(h.prev_end) : ''}${h.prev_end && h.next_start ? '<br>' : ''}${h.next_start ? 'starts ' + fmt(h.next_start) : ''}${!h.prev_end && !h.next_start ? '<span class="help">—</span>' : ''}</td>
            <td class="num">${h.upcoming_nights || 0}</td>
            <td class="text-end">${open(h, d.date)}</td></tr>`).join('') || '<tr><td colspan="6" class="empty">No hotel here.</td></tr>';
    }

    function render() {
        const d = S.data; if (!d) return;
        const t = d.totals;
        document.querySelectorAll('.q-on').forEach(e => { e.textContent = fmt(d.date); });
        document.getElementById('k_ready').textContent = t.ready;
        document.getElementById('k_ready_s').textContent = t.ready_some ? `${t.ready_all} for every market · ${t.ready_some} for some markets only` : 'every one for every market';
        document.getElementById('k_not').textContent = t.not_ready;
        document.getElementById('k_not_s').textContent = `${t.not_ready_hotels} hotel${t.not_ready_hotels === 1 ? '' : 's'}` + (t.sheets_without_supplier ? ` · ${t.sheets_without_supplier} rate sheet${t.sheets_without_supplier === 1 ? '' : 's'} without a supplier record` : '');
        document.getElementById('k_alerts').textContent = t.alerts;
        document.getElementById('k_alerts_s').textContent = `${t.expiring} expiring within ${d.alert_days} days · ${t.expired} expired`;
        document.getElementById('n_ready').textContent = t.ready;
        document.getElementById('n_not').textContent = t.not_ready;
        renderAlerts(d); renderReady(d); renderNot(d);
    }

    async function load() {
        const date = document.getElementById('q_date').value || TODAY;
        document.querySelectorAll('#q_quick .chip').forEach(c => c.classList.toggle('on', addDays(TODAY, Number(c.dataset.days)) === date));
        document.getElementById('q_state').textContent = 'Checking every hotel with the quotation engine…';
        try {
            const r = await fetch(BASE + '/data?date=' + encodeURIComponent(date), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await r.json();
            if (j.status !== 200) { document.getElementById('q_state').textContent = j.message || 'Could not load.'; return; }
            S.data = j.data; render();
            document.getElementById('q_state').textContent = `Checked ${j.data.totals.ready + j.data.totals.not_ready} hotels and sheets in ${(j.data.took_ms / 1000).toFixed(1)} s`;
        } catch (e) { document.getElementById('q_state').textContent = 'Could not load: ' + e.message; }
    }

    document.getElementById('q_date').addEventListener('change', load);
    document.querySelectorAll('#q_quick .chip').forEach(c => c.addEventListener('click', () => { document.getElementById('q_date').value = addDays(TODAY, Number(c.dataset.days)); load(); }));
    let t = null; document.getElementById('q_search').addEventListener('input', e => { clearTimeout(t); t = setTimeout(() => { S.q = e.target.value.trim().toLowerCase(); render(); }, 150); });
    load();
})();
</script>
</body>
</html>
