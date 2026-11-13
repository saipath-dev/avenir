<?= view('layouts/head') ?>
<style>
    /* ---------- Tactical rates & Early Bird offers (tf-offers-r1) — the Hotel Rates panel's tokens, read-only ---------- */
    .hro { --o-navy:#1e3553; --o-teal:#0f8b7e; --o-amber:#1a1a1a; --o-amber-soft:#ffffff; --o-red:#b23a32; --o-red-soft:#fbe9e7;
           --o-green:#1f8a4c; --o-green-soft:#e4f4ea; --o-blue-soft:#e6eef8; --o-violet:#6d28d9; --o-violet-soft:#efe9fb; --o-ink:#1d2733; --o-muted:#5f6f80; --o-line:#dfe5ec;
           --o-bg:#f4f6f9; --o-card:#fff; --o-radius:10px; color:var(--o-ink); }
    .hro .o-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .hro .o-title h4 { margin:0; font-weight:600; color:var(--o-navy); }
    .hro .o-title .sub { color:var(--o-muted); font-size:12.5px; max-width:900px; }
    .hro .o-card { background:var(--o-card); border:1px solid var(--o-line); border-radius:var(--o-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); margin-bottom:14px; }
    .hro .o-card-h { padding:11px 14px; border-bottom:1px solid var(--o-line); display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .hro .o-card-h h6 { margin:0; font-size:14px; font-weight:600; }
    .hro .o-card-b { padding:12px 14px; }
    .hro .help { font-size:12px; color:var(--o-muted); }
    .hro .mono { font-family:ui-monospace, Menlo, monospace; font-size:11.5px; white-space:nowrap; }
    .hro .tabs { display:flex; gap:4px; border-bottom:1px solid var(--o-line); margin-bottom:14px; }
    .hro .tab { padding:9px 16px; font-size:14px; font-weight:600; color:var(--o-muted); border:0; background:none; border-bottom:3px solid transparent; cursor:pointer; }
    .hro .tab.on { color:var(--o-navy); border-bottom-color:var(--o-navy); }
    .hro .tab .n { font-family:ui-monospace, Menlo, monospace; font-size:11px; background:#eef1f5; border-radius:999px; padding:1px 7px; margin-left:6px; color:var(--o-muted); }
    .hro .kpis { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:12px; margin-bottom:14px; }
    @media (max-width: 900px) { .hro .kpis { grid-template-columns:1fr 1fr; } }
    .hro .kpi { background:#fff; border:1px solid var(--o-line); border-left:5px solid var(--o-line); border-radius:var(--o-radius); padding:12px 14px; cursor:pointer; color:inherit; }
    .hro .kpi:hover { background:var(--o-bg); }
    .hro .kpi.on { box-shadow:0 0 0 2px var(--o-navy) inset; }
    .hro .kpi .v { font-size:28px; font-weight:700; line-height:1.1; }
    .hro .kpi .l { font-size:13px; font-weight:600; }
    .hro .kpi .s { font-size:11.5px; color:var(--o-muted); margin-top:2px; }
    .hro .kpi.ok { border-left-color:var(--o-green); } .hro .kpi.ok .v { color:var(--o-green); }
    .hro .kpi.no { border-left-color:var(--o-red); } .hro .kpi.no .v { color:var(--o-red); }
    .hro .kpi.warn { border-left-color:var(--o-amber); } .hro .kpi.warn .v { color:var(--o-amber); }
    .hro .kpi.grey { border-left-color:#c3ccd6; } .hro .kpi.grey .v { color:var(--o-muted); }
    .hro .controls { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .hro .chips { display:flex; gap:6px; flex-wrap:wrap; }
    .hro .chip { border:1px solid var(--o-line); background:#fff; border-radius:9px; padding:4px 10px; font-size:12px; font-weight:500; cursor:pointer; color:var(--o-ink); white-space:nowrap; }
    .hro .chip .n { font-family:ui-monospace, Menlo, monospace; font-size:11px; color:var(--o-muted); margin-left:4px; }
    .hro .chip.on { background:var(--o-blue-soft); border-color:transparent; color:var(--o-navy); font-weight:600; }
    .hro .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .hro .tbl th { text-align:left; font-weight:600; color:var(--o-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--o-line); white-space:nowrap; }
    .hro .tbl td { padding:9px 10px; border-bottom:1px solid var(--o-line); vertical-align:top; }
    .hro .tbl tr:last-child td { border-bottom:0; }
    .hro .tbl tr:hover td { background:var(--o-bg); }
    .hro .tbl td.num, .hro .tbl th.num { text-align:right; }
    .hro .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .hro .pill-green { background:var(--o-green-soft); color:var(--o-green); }
    .hro .pill-red { background:var(--o-red-soft); color:var(--o-red); }
    .hro .pill-amber { background:var(--o-amber-soft); border:1px solid #d9d3dd; color:var(--o-amber); }
    .hro .pill-blue { background:var(--o-blue-soft); color:var(--o-navy); }
    .hro .pill-violet { background:var(--o-violet-soft); color:var(--o-violet); }
    .hro .pill-grey { background:#eef1f5; color:var(--o-muted); }
    .hro .empty { color:var(--o-muted); font-size:12.5px; padding:16px 0; text-align:center; }
    .hro .not-installed { padding:14px 16px; border-radius:8px; background:var(--o-amber-soft); border:1px solid #d9d3dd; color:var(--o-amber); font-size:13px; }
    .hro .loading { color:var(--o-muted); font-size:12px; }
    .hro .fav { color:#e0a100; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content hro" id="hro">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>

                <div class="o-title">
                    <div>
                        <h4>Tactical rates &amp; Early Bird offers</h4>
                        <div class="sub">Every hotel keeps three separate areas: <b>Published rates</b> (the seasonal contract rates — the only basis of a customer quotation), <b>Tactical rates</b> (special rates a hotel gives us selectively) and <b>Early Bird offers</b> (rates for booking ahead). Tactical and early-bird rates never change an inquiry, quotation or booking; at hotel payment an authorized person decides whether to apply one. This page watches them across hotels — and lists the hotels with none, so we can ask for special rates. A view only: add, correct or withdraw an offer on the hotel's own tab.</div>
                    </div>
                    <div><a class="btn btn-light btn-sm" href="<?= base_url($panel_url ?? 'master_files/hotel_rate_v2') ?>"><i class="bi bi-arrow-left"></i> Hotel Rates</a></div>
                </div>

                <?php if (empty($ready)): ?>
                <div class="not-installed">Tactical rates and Early Bird offers appear once the System Administrator presses <b>Install now</b> (Tour Desk Admin, schema 42).</div>
                <?php else: ?>

                <div class="tabs">
                    <button class="tab" data-kind="tactical">Tactical rates <span class="n" id="n_tactical">…</span></button>
                    <button class="tab" data-kind="early_bird">Early Bird offers <span class="n" id="n_early_bird">…</span></button>
                </div>

                <div class="kpis" id="kpis"></div>

                <div class="o-card"><div class="o-card-b controls">
                    <input type="text" class="form-control form-control-sm" id="o_search" placeholder="Search hotel, offer, location or code…" style="width:260px">
                    <div class="chips" id="o_chips"></div>
                    <span class="loading ms-auto" id="o_state"></span>
                </div></div>

                <div class="o-card" id="sec_offers">
                    <div class="o-card-h"><h6 id="h_offers">Offers</h6><span class="help" id="h_offers_help"></span></div>
                    <div class="table-responsive"><table class="tbl">
                        <thead><tr><th>Hotel</th><th>Offer</th><th>Status</th><th>Stay dates</th><th>Book by</th><th>Rooms · meal plans</th><th>Markets · conditions</th><th class="num">From</th><th></th></tr></thead>
                        <tbody id="rows_offers"><tr><td colspan="9" class="empty">Loading…</td></tr></tbody>
                    </table></div>
                </div>

                <div class="o-card" id="sec_none">
                    <div class="o-card-h"><h6 id="h_none">Hotels with none</h6><span class="help" id="h_none_help"></span>
                        <div class="chips ms-auto" id="none_chips"></div></div>
                    <div class="table-responsive"><table class="tbl">
                        <thead><tr><th>Hotel</th><th>Published rates</th><th class="num" title="Hotel nights on quotations and tours over the last 365 days">Nights last year</th><th id="th_other"></th><th></th></tr></thead>
                        <tbody id="rows_none"><tr><td colspan="5" class="empty">Loading…</td></tr></tbody>
                    </table></div>
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
    const READY = <?= !empty($ready) ? 'true' : 'false' ?>;
    if (!READY) return;
    const BASE = '<?= base_url($page_url ?? 'master_files/hotel_rate_v2/offers') ?>';
    const PANEL = '<?= base_url($panel_url ?? 'master_files/hotel_rate_v2') ?>';
    const SOON = <?= (int) ($deadline_soon_days ?? 14) ?>;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }); };
    const pill = (lvl, t, title) => `<span class="pill pill-${esc(lvl || 'grey')}"${title ? ` title="${esc(title)}"` : ''}>${esc(t)}</span>`;
    const money = v => v === null || v === undefined ? '—' : Number(v).toLocaleString('en', { maximumFractionDigits: 2 });
    const STATUS = { active: ['green', 'Active'], deadline_soon: ['amber', 'Booking deadline soon'], upcoming: ['blue', 'Booking opens later'], booking_closed: ['grey', 'Booking closed'], expired: ['grey', 'Expired'], withdrawn: ['grey', 'Withdrawn'], draft: ['blue', 'Draft'] };
    const ACTIVE = ['active', 'deadline_soon', 'upcoming'];
    const KIND = { tactical: 'Tactical rates', early_bird: 'Early Bird offers' };
    const S = { data: null, kind: '<?= esc($tab ?? 'tactical') ?>', filter: 'active', none: 'used', q: '' };

    const statusCell = o => {
        const [lvl, t] = STATUS[o.offer_status] || ['grey', o.offer_status];
        let extra = '';
        if (o.days_to_deadline !== null && ['active', 'deadline_soon', 'upcoming'].includes(o.offer_status)) extra = `<div class="help">${o.days_to_deadline === 0 ? 'last booking day today' : o.days_to_deadline + ' day' + (o.days_to_deadline === 1 ? '' : 's') + ' to book'}</div>`;
        return pill(lvl, t) + extra;
    };
    const match = (o) => !S.q || [o.hotel_name, o.name, o.rate_code, o.location, o.hotel_acc].join(' ').toLowerCase().includes(S.q);
    const open = (acc, tab) => `<a class="btn btn-light btn-sm" href="${PANEL}?hotel=${encodeURIComponent(acc || '')}&tab=${tab}">Open</a>`;

    function render() {
        const d = S.data; if (!d) return;
        const k = S.kind;
        document.querySelectorAll('.tab').forEach(t => t.classList.toggle('on', t.dataset.kind === k));
        const all = d.offers.filter(o => o.kind === k);
        ['tactical', 'early_bird'].forEach(kk => { document.getElementById('n_' + kk).textContent = d.offers.filter(o => o.kind === kk && ACTIVE.includes(o.offer_status)).length; });
        const cnt = st => all.filter(o => st.includes(o.offer_status)).length;
        const withAny = d.hotels.filter(h => h[k + '_active'] > 0).length;
        const without = d.hotels.filter(h => h[k + '_active'] === 0);
        const tiles = k === 'early_bird'
            ? [['active', 'ok', cnt(ACTIVE), 'Active early birds', `${withAny} hotel${withAny === 1 ? '' : 's'} with one`],
               ['deadline_soon', 'warn', cnt(['deadline_soon']), 'Booking deadline within ' + SOON + ' days', 'book before the date shown'],
               ['closed', 'grey', cnt(['booking_closed', 'expired']), 'Booking closed or expired', 'kept for history'],
               ['none', 'no', without.length, 'Hotels with no early bird', 'below — to ask for one']]
            : [['active', 'ok', cnt(ACTIVE), 'Active tactical rates', `${withAny} hotel${withAny === 1 ? '' : 's'} with one`],
               ['draft', 'grey', cnt(['draft']), 'Drafts', 'saved, not active yet'],
               ['closed', 'grey', cnt(['booking_closed', 'expired', 'withdrawn']), 'Ended or withdrawn', 'kept for history'],
               ['none', 'no', without.length, 'Hotels with no tactical rate', 'below — to approach for special rates']];
        document.getElementById('kpis').innerHTML = tiles.map(([f, cls, v, l, s]) => `<div class="kpi ${cls}${S.filter === f ? ' on' : ''}" data-f="${f}"><div class="v">${v}</div><div class="l">${esc(l)}</div><div class="s">${esc(s)}</div></div>`).join('');
        document.querySelectorAll('#kpis .kpi').forEach(t => t.addEventListener('click', () => { if (t.dataset.f === 'none') { document.getElementById('sec_none').scrollIntoView({ behavior: 'smooth' }); return; } S.filter = t.dataset.f; render(); }));
        const filters = k === 'early_bird'
            ? [['active', 'Active', ACTIVE], ['deadline_soon', 'Deadline soon', ['deadline_soon']], ['closed', 'Booking closed / expired', ['booking_closed', 'expired']], ['withdrawn', 'Withdrawn', ['withdrawn']], ['draft', 'Drafts', ['draft']], ['all', 'All', null]]
            : [['active', 'Active', ACTIVE], ['draft', 'Drafts', ['draft']], ['closed', 'Ended / withdrawn', ['booking_closed', 'expired', 'withdrawn']], ['all', 'All', null]];
        const cur = filters.find(f => f[0] === S.filter) || filters[0];
        document.getElementById('o_chips').innerHTML = filters.map(([f, l, st]) => `<span class="chip${cur[0] === f ? ' on' : ''}" data-f="${f}">${esc(l)}<span class="n">${st ? cnt(st) : all.length}</span></span>`).join('');
        document.querySelectorAll('#o_chips .chip').forEach(c => c.addEventListener('click', () => { S.filter = c.dataset.f; render(); }));
        const rows = all.filter(o => (!cur[2] || cur[2].includes(o.offer_status)) && match(o))
            .sort((a, b) => (a.offer_status === 'deadline_soon' ? -1 : 0) - (b.offer_status === 'deadline_soon' ? -1 : 0) || String(a.book_to || '9999').localeCompare(String(b.book_to || '9999')) || String(a.hotel_name).localeCompare(String(b.hotel_name)));
        document.getElementById('h_offers').innerHTML = `${KIND[k]} <span class="pill pill-${k === 'early_bird' ? 'violet' : 'blue'}">${rows.length}</span>`;
        document.getElementById('h_offers_help').textContent = k === 'early_bird' ? 'At hotel payment, a tour booked by the deadline is flagged to an authorized person, who applies the early bird or keeps the published rate.' : 'At hotel payment, the stays a tactical fits are flagged to an authorized person, who applies it or keeps the published rate.';
        document.getElementById('rows_offers').innerHTML = rows.map(o => `<tr>
            <td><div class="fw-semibold">${esc(o.hotel_name)}</div><div class="help">${esc(o.location || '')}${o.location ? ' · ' : ''}<span class="mono">${esc(o.hotel_acc || '')}</span></div></td>
            <td><div class="fw-semibold">${esc(o.name)}</div><div class="help">${o.rate_code ? '<span class="mono">' + esc(o.rate_code) + '</span> · ' : ''}${o.payment_only ? 'payment only · ' : ''}${esc(o.source_file || o.source_type || '')}</div></td>
            <td>${statusCell(o)}</td>
            <td class="mono">${esc(o.valid_from)} → ${esc(o.valid_to)}</td>
            <td class="mono">${o.book_to ? esc(o.book_to) : '<span class="help">—</span>'}</td>
            <td>${esc(o.rooms.slice(0, 4).join(', '))}${o.rooms.length > 4 ? ' +' + (o.rooms.length - 4) : ''}<div class="help">${esc(o.meal_plans.join(' · '))}</div></td>
            <td>${o.markets.length ? esc(o.markets.join(', ')) : '<span class="help">all markets</span>'}${o.min_stay ? `<div class="help">min ${o.min_stay} nights</div>` : ''}</td>
            <td class="num">${o.lowest ? `${esc(o.currency)} ${money(o.lowest.amount)}<div class="help">${esc(o.lowest.room)} ${esc(o.lowest.meal_plan)}</div>` : '—'}</td>
            <td class="text-end">${open(o.hotel_acc, k)}</td></tr>`).join('') || `<tr><td colspan="9" class="empty">No ${k === 'early_bird' ? 'early bird' : 'tactical rate'} here.</td></tr>`;

        // hotels without any: most-used first, so the team approaches the ones we use
        const noneF = [['used', 'Used last year', h => h.nights_last_year > 0], ['fav', '★ Frequently used', h => h.frequently_used], ['all', 'All hotels', () => true]];
        const nf = noneF.find(f => f[0] === S.none) || noneF[0];
        document.getElementById('none_chips').innerHTML = noneF.map(([f, l, fn]) => `<span class="chip${nf[0] === f ? ' on' : ''}" data-n="${f}">${esc(l)}<span class="n">${without.filter(fn).length}</span></span>`).join('');
        document.querySelectorAll('#none_chips .chip').forEach(c => c.addEventListener('click', () => { S.none = c.dataset.n; render(); }));
        const other = k === 'early_bird' ? 'tactical_active' : 'early_bird_active';
        document.getElementById('th_other').textContent = k === 'early_bird' ? 'Tactical rates' : 'Early birds';
        document.getElementById('h_none').innerHTML = `Hotels with no ${k === 'early_bird' ? 'early bird' : 'tactical rate'} <span class="pill pill-red">${without.length}</span>`;
        document.getElementById('h_none_help').textContent = k === 'early_bird' ? 'Early Birds: 0 — no early bird offer to book ahead.' : 'Tacticals: 0 — candidates to approach for special rates (most-used first).';
        const list = without.filter(nf[2]).filter(h => !S.q || [h.name, h.location, h.acc, h.chain].join(' ').toLowerCase().includes(S.q)).sort((a, b) => b.nights_last_year - a.nights_last_year || a.name.localeCompare(b.name));
        document.getElementById('rows_none').innerHTML = list.map(h => `<tr>
            <td><div class="fw-semibold">${h.frequently_used ? '<span class="fav" title="Frequently Used">★</span> ' : ''}${esc(h.name)}</div><div class="help">${esc(h.location || '')}${h.location ? ' · ' : ''}<span class="mono">${esc(h.acc || '')}</span></div></td>
            <td>${h.published_until ? pill('green', 'Available') + `<div class="help">until ${fmt(h.published_until)}</div>` : pill('red', 'None current')}</td>
            <td class="num">${h.nights_last_year}</td>
            <td>${h[other] ? pill(k === 'early_bird' ? 'blue' : 'violet', h[other] + ' active') : '<span class="help">0</span>'}</td>
            <td class="text-end">${open(h.acc, k)}</td></tr>`).join('') || '<tr><td colspan="5" class="empty">No hotel here.</td></tr>';
    }

    async function load() {
        document.getElementById('o_state').textContent = 'Loading…';
        try {
            const r = await fetch(BASE + '/data', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await r.json();
            if (j.status !== 200) { document.getElementById('o_state').textContent = j.message || 'Could not load.'; return; }
            S.data = j.data; render();
            document.getElementById('o_state').textContent = `${j.data.hotels.length} hotels · ${j.data.offers.length} offers on file · ${fmt(j.data.today)}`;
        } catch (e) { document.getElementById('o_state').textContent = 'Could not load: ' + e.message; }
    }
    document.querySelectorAll('.tab').forEach(t => t.addEventListener('click', () => { S.kind = t.dataset.kind; S.filter = 'active'; render(); history.replaceState(null, '', BASE + '?tab=' + S.kind); }));
    let tm = null; document.getElementById('o_search').addEventListener('input', e => { clearTimeout(tm); tm = setTimeout(() => { S.q = e.target.value.trim().toLowerCase(); render(); }, 150); });
    load();
})();
</script>
</body>
</html>
