<?= view('layouts/head') ?>
<style>
    /* tf-confirmed-r4 (19 Nov 2026, Shantha): Previous Tours — the completed tours, searched and viewed here. Read only. */
    .tf { --line:#e7dfe3; --mut:#5f6470; --ink:#1A1A1A; --p:#8b1d40; --g:#78b848; color:var(--ink); }
    .tf .ct-top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin:2px 2px 14px; } .tf .ct-top h2 { margin:0; font-size:23px; font-weight:800; }
    .tf .ct-prev { display:inline-flex; align-items:center; gap:8px; height:38px; padding:0 15px; border:1.5px solid var(--p); border-radius:9px; color:var(--p); font-size:13.5px; font-weight:700; text-decoration:none; background:#fff; } .tf .ct-prev:hover { background:var(--p); color:#fff; }
    .tf .tq-card { background:#fff; border-radius:14px; box-shadow:0 1px 5px rgba(53,7,22,.08); overflow:hidden; }
    .tf .tq-fl { display:flex; gap:8px; flex-wrap:wrap; align-items:center; padding:16px 20px; }
    .tf .tq-search { display:flex; align-items:center; gap:10px; flex:1 1 300px; max-width:460px; height:44px; padding:0 14px; margin:0; border:1.5px solid var(--line); border-radius:10px; background:#fff; } .tf .tq-search i { color:var(--mut); }
    .tf .tq-search input { border:0; outline:0; flex:1 1 auto; min-width:0; font-size:14.5px; background:transparent; } .tf .tq-search:focus-within, .tf .tq-sel:focus-within { border-color:var(--p); box-shadow:0 0 0 3px rgba(139,29,64,.14); }
    .tf .tq-sel { display:inline-flex; align-items:center; gap:6px; height:36px; padding:0 12px; margin:0; border:1.5px solid var(--line); border-radius:18px; background:#fff; font-size:13.5px; font-weight:700; white-space:nowrap; }
    .tf .tq-sel input { border:0; outline:0; background:transparent; font:inherit; font-weight:600; font-size:12.5px; height:30px; max-width:124px; } .tf .tq-sel i { font-style:normal; color:var(--mut); }
    .tf .tq-clear { height:36px; padding:0 12px; border:0; background:none; color:var(--mut); font-size:13px; font-weight:700; cursor:pointer; } .tf .tq-clear:hover { color:var(--p); }
    .tf table.tq-tbl { width:100%; border-collapse:collapse; } .tf table.tq-tbl th { text-align:left; font-size:11.5px; letter-spacing:.07em; text-transform:uppercase; font-weight:700; padding:12px 16px; white-space:nowrap; background:var(--p); color:#fff; }
    .tf table.tq-tbl td { padding:14px 16px; border-top:1px solid var(--line); font-size:14.5px; vertical-align:top; } .tf table.tq-tbl td.empty { text-align:center; color:var(--mut); padding:30px 20px; }
    .tf table.tq-tbl td > b { display:block; font-weight:800; } .tf table.tq-tbl td small { display:block; font-size:12.5px; color:var(--mut); margin-top:2px; } .tf table.tq-tbl td.ac { text-align:right; white-space:nowrap; }
    .tf .ct-st { display:inline-block; padding:7px 11px; border-radius:8px; font-size:13px; font-weight:700; background:#eef7e6; color:#3f7a17; border:1px solid #d5e9c2; }
    .tf .tq-open { display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 18px; border-radius:9px; background:var(--g); color:#fff !important; font-size:14.5px; font-weight:700; text-decoration:none; }
    .tf .tq-open.alt { background:#fff; color:var(--p) !important; border:1.5px solid var(--p); margin-left:6px; }
    .tf .tq-foot { padding:12px 20px 14px; border-top:1px solid var(--line); font-size:13px; color:var(--mut); }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">
                <div class="ct-top"><h2>Previous Tours</h2><a class="ct-prev" href="<?= base_url($page_url ?? 'tour/intake/confirmed') ?>"><i class="bi bi-arrow-left"></i> Confirmed Tours</a></div>
                <?php if (empty($installed)): ?>
                <div class="alert alert-light border">The Tour Desk tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Inquiries</a> and press Install; this screen then works.</div>
                <?php else: ?>
                <div class="tq-card">
                    <div class="tq-fl">
                        <label class="tq-search"><i class="bi bi-search"></i><input id="f_q" placeholder="Search completed tours by tour no., customer or tour"></label>
                        <label class="tq-sel"><span>Travel date:</span><input type="date" id="f_from" title="Arriving from"><i>–</i><input type="date" id="f_to" title="Arriving to"></label>
                        <button class="tq-clear" id="f_clear" type="button"><i class="bi bi-x-lg"></i> Clear</button>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="tq-tbl">
                            <thead><tr><th>Tour</th><th>Customer &amp; Tour</th><th>Travel dates</th><th>Pax</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
                            <tbody id="rows"><tr><td colspan="6" class="empty">Loading…</td></tr></tbody>
                        </table>
                    </div>
                    <div class="tq-foot" id="list_foot"></div>
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
(function () {
    'use strict';
    if (!document.getElementById('rows')) return;
    const PAGE = '<?= base_url($page_url ?? 'tour/intake/confirmed') ?>';
    const BOARD = '<?= base_url('tour/board') ?>', CAN_BOARD = <?= !empty($can_board) ? 'true' : 'false' ?>;
    const AS_OF = (m => m ? '&as_of=' + m[1] : '')(/[?&]as_of=(\d{4}-\d{2}-\d{2})/.exec(location.search));
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmtY = d => { if (!d) return '—'; const x = new Date(String(d).slice(0, 10) + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); };
    const $ = id => document.getElementById(id);
    async function load() {
        const qs = new URLSearchParams({ previous: '1', q: $('f_q').value || '', from: $('f_from').value || '', to: $('f_to').value || '' }).toString();
        let j = null;
        try { j = await (await fetch(PAGE + '/data?' + qs + AS_OF, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).json(); } catch (e) { j = null; }
        if (!j || j.status !== 200) { $('rows').innerHTML = `<tr><td colspan="6" class="empty">${esc((j && j.message) || 'Could not load the tours.')}</td></tr>`; return; }
        const tours = (j.data.tours || []).slice().sort((a, b) => String((b.finalized || {}).at || '').localeCompare(String((a.finalized || {}).at || '')));
        $('list_foot').textContent = tours.length + ' completed tour' + (tours.length === 1 ? '' : 's');
        if (!tours.length) { $('rows').innerHTML = '<tr><td colspan="6" class="empty">No completed tours found.</td></tr>'; return; }
        $('rows').innerHTML = tours.map(t => { const f = t.finalized || {}; return `<tr>
            <td><b>${esc(t.tour_no)}</b>${t.inquiry_no ? `<small>Inquiry ${esc(t.inquiry_no)}</small>` : ''}${t.handler_name ? `<small>${esc(t.handler_name)}</small>` : ''}</td>
            <td><b>${esc(t.agent_name || '—')}</b><small>${esc(t.tour_name || '')}</small></td>
            <td style="white-space:nowrap">${fmtY(t.arrival_date)} – ${fmtY(t.departure_date)}${t.nights != null ? `<small>${t.nights} night${Number(t.nights) === 1 ? '' : 's'}</small>` : ''}</td>
            <td><b>${t.pax_total ?? '—'}</b></td>
            <td><span class="ct-st"><i class="bi bi-check-circle-fill"></i> Completed</span><small>finalized ${esc(String(f.at || '').slice(0, 16))}${f.by ? ' · ' + esc(f.by) : ''}</small></td>
            <td class="ac"><a class="tq-open" href="${PAGE}/${t.id}/control">Open <i class="bi bi-arrow-right"></i></a>${CAN_BOARD ? `<a class="tq-open alt" href="${BOARD}/tour/${t.id}/record">Complete record</a>` : ''}</td></tr>`; }).join('');
    }
    $('f_q').addEventListener('input', () => { clearTimeout(window.__pq); window.__pq = setTimeout(load, 250); });
    ['f_from', 'f_to'].forEach(id => $(id).addEventListener('change', load));
    $('f_clear').addEventListener('click', () => { ['f_q', 'f_from', 'f_to'].forEach(id => $(id).value = ''); load(); });
    load();
})();
</script>
</body>
</html>
