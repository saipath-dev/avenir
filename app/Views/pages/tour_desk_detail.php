<?= view('layouts/head') ?>
<?php
/**
 * tf-tourdesk-r2 (25 Oct 2026) — the Tour Desk detail views, read-only:
 *   home/tour_desk/upcoming — Upcoming Tours: the next one month as a calendar, then day by day
 *   home/tour_desk/arrivals — Next Arrivals: the next seven days with arrival flight, time, tour number, client, pax, driver and vehicle
 * Both read home/tour_desk/data (confirmed tours as they stand) and change nothing. The banner photograph is the design picture's own tile.
 */
$esc = static fn ($s) => esc((string) $s);
$img = static fn (string $f) => base_url('assets/images/tour_desk/' . $f);
$isArr = ($mode ?? 'upcoming') === 'arrivals';
$colour = $isArr ? '#ba3b2b' : '#2559a7';
$title = $isArr ? 'Next Arrivals' : 'Upcoming Tours';
$sub = $isArr ? 'Arriving tours for the next 7 days — flight, time, tour number, pax, driver and vehicle once allocated' : 'All scheduled tours for the next 1 month';
$open = $td_open ?? null;   // ['url' => ..., 'label' => ...] the existing page behind this view, when the sign-in may open it
?>
<body class="tdk-body">
<style>
    :root { --tdk-p:#842484; --tdk-g:#78b848; --tdk-gold:#e0a526; --tdk-ink:#2b2b33; --tdk-mut:#6b6f7b; --tdk-line:#e3e6ea; --c:<?= $colour ?>; --tdk-font:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; }
    body.tdk-body { background:#f6f7f9 !important; color:var(--tdk-ink); font-family:var(--tdk-font); }
    .tdk-top { position:sticky; top:0; z-index:1030; display:flex; align-items:center; gap:14px; padding:10px 28px; }
    .tdk-top .sp { flex:1; }
    .tdk-top .hd-user { display:flex; align-items:center; gap:9px; font-weight:600; color:#fff; }
    .tdk-top .hd-user img { width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid var(--tdk-gold); }
    .tdk-top .hd-user small { display:block; font-weight:400; font-size:.75rem; line-height:1.1; color:rgba(255,255,255,.8); }
    .tdk-top .hd-avatar-fallback { font-size:34px; line-height:1; color:#fff; }
    .av-nav-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:38px; padding:0 15px; border-radius:10px; border:0; font-size:13.5px; font-weight:600; line-height:1; white-space:nowrap; cursor:pointer; }
    .av-nav-out { background:transparent; border:1.5px solid rgba(255,255,255,.55); color:#fff !important; }

    .tdd-banner { position:relative; min-height:168px; background:#333 url("<?= $img($isArr ? 'tile_arrivals.jpg' : 'tile_upcoming.jpg') ?>") center / cover no-repeat; display:flex; align-items:flex-end; }
    .tdd-banner::after { content:""; position:absolute; inset:0; background:linear-gradient(90deg,rgba(20,20,30,.72) 0%,rgba(20,20,30,.5) 50%,rgba(20,20,30,.3) 100%); }
    .tdd-banner .in { position:relative; z-index:1; width:100%; max-width:1500px; margin:0 auto; padding:18px clamp(16px,2.6vw,40px); display:flex; align-items:center; gap:16px; color:#fff; flex-wrap:wrap; }
    .tdd-banner .bd { width:64px; height:64px; border-radius:50%; background:var(--c); display:grid; place-items:center; font-size:30px; box-shadow:0 3px 10px rgba(0,0,0,.3); flex:0 0 auto; }
    .tdd-banner h1 { margin:0; color:#fff !important; font-size:clamp(1.5rem,2.6vw,2.2rem); font-weight:800; letter-spacing:-.02em; text-shadow:0 2px 10px rgba(0,0,0,.45); }
    .tdd-banner p { margin:3px 0 0; color:#fff !important; font-size:.95rem; opacity:.95; text-shadow:0 1px 6px rgba(0,0,0,.5); }
    .tdd-banner .range { margin-left:auto; display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .tdd-banner .range span { background:rgba(255,255,255,.92); color:var(--tdk-ink); border-radius:999px; padding:7px 14px; font-weight:700; font-size:.9rem; }
    .tdd-banner .range a { background:var(--c); color:#fff; border-radius:999px; padding:8px 16px; font-weight:700; font-size:.9rem; text-decoration:none; }
    .tdd-banner .range a.home { background:var(--tdk-p); }
    .tdd-wrap { max-width:1500px; margin:0 auto; padding:20px clamp(16px,2.6vw,40px) 40px; }
    .tdd-card { background:#fff; border:1px solid var(--tdk-line); border-radius:16px; box-shadow:0 2px 8px rgba(20,20,30,.05); padding:16px 18px 18px; margin-bottom:20px; }
    .tdd-card h2 { margin:0 0 12px; font-size:1.05rem; font-weight:800; color:var(--c); display:flex; align-items:center; gap:8px; }
    .tdd-card h2 .n { margin-left:auto; font-size:.8rem; font-weight:700; color:var(--tdk-mut); }
    .tdd-load { color:var(--tdk-mut); font-size:.9rem; display:flex; align-items:center; gap:8px; padding:8px 0; }
    .tdd-load .sp { width:14px; height:14px; border:2px solid rgba(0,0,0,.12); border-top-color:var(--c); border-radius:50%; animation:tdd-spin 1s linear infinite; }
    @keyframes tdd-spin { to { transform:rotate(360deg); } }
    .tdd-none { color:var(--tdk-mut); font-style:italic; padding:8px 0; }

    /* month calendar */
    .tdd-cal { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:6px; }
    .tdd-cal .dow { font-size:.78rem; font-weight:800; color:var(--tdk-mut); text-transform:uppercase; letter-spacing:.04em; padding:2px 6px; }
    .tdd-cal .day { min-height:104px; border:1px solid var(--tdk-line); border-radius:10px; padding:6px 8px; background:#fff; position:relative; }
    .tdd-cal .day.out { background:#f6f7f9; color:#b5b9c2; }
    .tdd-cal .day.today { border-color:var(--c); box-shadow:inset 0 0 0 1.5px var(--c); }
    .tdd-cal .day .n { font-weight:800; font-size:1rem; }
    .tdd-cal .day .m { font-size:.72rem; font-weight:700; color:var(--tdk-mut); margin-left:4px; }
    .tdd-cal .day .cnt { position:absolute; top:6px; right:8px; min-width:24px; height:24px; border-radius:12px; background:var(--c); color:#fff; font-size:.78rem; font-weight:800; display:grid; place-items:center; padding:0 7px; }
    .tdd-cal .day .ids { margin-top:6px; display:flex; flex-direction:column; gap:3px; }
    .tdd-cal .day .ids span { display:block; font-size:.78rem; line-height:1.25; padding:3px 6px; border-radius:6px; background:rgba(37,89,167,.09); color:var(--tdk-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .tdd-cal .day .ids span b { color:var(--c); }
    .tdd-cal .day .ids span.more { background:none; color:var(--tdk-mut); font-weight:700; }

    /* the arrivals week */
    .tdd-week { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:10px; }
    .tdd-week .col { border:1px solid var(--tdk-line); border-radius:12px; background:#fbfbfc; overflow:hidden; min-height:140px; }
    .tdd-week .col.today { border-color:var(--c); box-shadow:inset 0 0 0 1.5px var(--c); }
    .tdd-week .col h3 { margin:0; padding:9px 11px; background:var(--c); color:#fff; font-size:.92rem; font-weight:800; display:flex; justify-content:space-between; align-items:baseline; }
    .tdd-week .col h3 small { font-weight:600; font-size:.8rem; opacity:.92; }
    .tdd-week .col.today h3::after { content:"today"; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; background:#fff; color:var(--c); border-radius:999px; padding:1px 7px; margin-left:6px; }
    .tdd-week .arr { margin:8px; padding:9px 10px; background:#fff; border:1px solid var(--tdk-line); border-radius:10px; font-size:.86rem; line-height:1.35; }
    .tdd-week .arr .id { display:flex; align-items:center; gap:6px; }
    .tdd-week .arr .id b { color:var(--c); font-size:1.02rem; }
    .tdd-week .arr .pax { margin-left:auto; background:var(--tdk-g); color:#2b2b33; border-radius:10px; padding:1px 8px; font-weight:800; font-size:.76rem; white-space:nowrap; }
    .tdd-week .arr .fl { display:flex; align-items:center; gap:6px; font-weight:800; color:var(--tdk-ink); margin-top:4px; }
    .tdd-week .arr .fl .t { font-weight:800; background:rgba(0,0,0,.06); border-radius:6px; padding:0 6px; }
    .tdd-week .arr .cl { margin-top:4px; font-weight:600; }
    .tdd-week .arr .ag { color:var(--tdk-mut); font-size:.78rem; }
    .tdd-week .arr .drv { margin-top:5px; padding-top:5px; border-top:1px dashed var(--tdk-line); color:#3b3f49; font-size:.82rem; }
    .tdd-week .arr .na { color:var(--tdk-mut); font-style:italic; font-weight:400; }
    .tdd-week .none { padding:16px 11px; color:var(--tdk-mut); font-size:.84rem; font-style:italic; }

    /* the lists */
    .tdd-table { width:100%; border-collapse:separate; border-spacing:0; font-size:.88rem; }
    .tdd-table th { text-align:left; background:var(--c); color:#fff; font-weight:700; padding:9px 10px; white-space:nowrap; }
    .tdd-table th:first-child { border-radius:8px 0 0 8px; } .tdd-table th:last-child { border-radius:0 8px 8px 0; }
    .tdd-table td { padding:9px 10px; border-bottom:1px solid var(--tdk-line); vertical-align:top; }
    .tdd-table tr.day td { background:#f1f3f6; font-weight:800; color:var(--tdk-ink); }
    .tdd-table td.id { font-weight:800; color:var(--c); white-space:nowrap; }
    .tdd-table .na { color:var(--tdk-mut); font-style:italic; }
    .tdd-table .num { text-align:right; white-space:nowrap; }
    @media (max-width:1399px) { .tdd-week { grid-template-columns:repeat(4,minmax(0,1fr)); } }
    @media (max-width:991px)  { .tdd-week { grid-template-columns:repeat(2,minmax(0,1fr)); } .tdd-cal .day { min-height:80px; } .tdd-cal .day .ids span { display:none; } .tdd-cal .day .ids span:first-child { display:block; } }
    @media (max-width:767px)  { .tdd-week { grid-template-columns:1fr; } .tdd-week .col { min-height:0; } .tdd-table { font-size:.8rem; } .tdd-table th, .tdd-table td { padding:7px 6px; } .tdd-wrap { padding-left:10px; padding-right:10px; }
        .tdd-top { padding:8px 12px; gap:6px; } .tdk-top .hd-user span, .av-nav-out span { display:none; } .tdd-cal { gap:3px; } .tdd-cal .day { padding:4px; min-height:56px; } .tdd-cal .day .ids { display:none; } }
</style>

<header class="tdk-top hd-top">
    <?= view('layouts/nav_bar', ['nb_back' => 'window.history.back()']) ?>
    <div class="sp"></div>
    <?= view('layouts/page_search') ?>
    <div class="hd-user">
        <img src="<?= $esc($profile_picture ?? base_url('assets/images/users/no_profile.jpg')) ?>" alt="" onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'bi bi-person-circle hd-avatar-fallback'}))">
        <span><?= $esc($sidebar_employee_name ?? '') ?><small><?= $esc($sidebar_employee_name_profile_name ?? '') ?></small></span>
    </div>
    <button type="button" class="av-nav-btn av-nav-out" onclick="logout()" title="Logout" aria-label="Logout"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></button>
</header>

<section class="tdd-banner">
    <div class="in">
        <div class="bd" aria-hidden="true"><i class="bi <?= $isArr ? 'bi-airplane-fill' : 'bi-calendar2-check' ?>"></i></div>
        <div><h1><?= $title ?></h1><p><?= $sub ?></p></div>
        <div class="range">
            <span id="tdd-range">—</span>
            <?php $hm = $td_home ?? ['url' => 'home/tour_desk', 'label' => 'Tour Desk']; /* tf-resdesk-r1: back to the desk this view was opened from */ ?>
            <a class="home" href="<?= $esc(base_url($hm['url'])) ?>"><i class="bi bi-grid-fill"></i> <?= $esc($hm['label']) ?></a>
            <?php if ($open): ?><a href="<?= $esc(base_url($open['url'])) ?>"><?= $esc($open['label']) ?> <i class="bi bi-arrow-right"></i></a><?php endif; ?>
        </div>
    </div>
</section>

<main class="tdd-wrap">
<?php if (empty($td_can_calendar)): ?>
    <div class="tdd-card"><div class="tdd-none">This view shows for a sign-in that may open the Tour Desk pages. The Super Admin assigns access.</div></div>
<?php elseif ($isArr): ?>
    <div class="tdd-card">
        <h2><i class="bi bi-calendar-week"></i> The next seven days <span class="n" id="tdd-n1"></span></h2>
        <div class="tdd-load" id="tdd-load"><span class="sp"></span>Loading the next seven days…</div>
        <div class="tdd-week" id="tdd-week" hidden></div>
    </div>
    <div class="tdd-card">
        <h2><i class="bi bi-list-ul"></i> Arrivals list</h2>
        <div style="overflow:auto"><table class="tdd-table" id="tdd-list" hidden>
            <thead><tr><th>Date</th><th>Flight</th><th>Time</th><th>Tour No</th><th>Client</th><th>Agent</th><th>Country</th><th class="num">Pax</th><th>Driver</th><th>Vehicle</th><th>Tour ends</th></tr></thead><tbody></tbody>
        </table></div>
    </div>
<?php else: ?>
    <div class="tdd-card">
        <h2><i class="bi bi-calendar3"></i> Calendar <span class="n" id="tdd-n1"></span></h2>
        <div class="tdd-load" id="tdd-load"><span class="sp"></span>Loading the next month's tours…</div>
        <div class="tdd-cal" id="tdd-cal" hidden></div>
    </div>
    <div class="tdd-card">
        <h2><i class="bi bi-list-ul"></i> Day by day</h2>
        <div style="overflow:auto"><table class="tdd-table" id="tdd-list" hidden>
            <thead><tr><th>Tour No</th><th>Client</th><th>Agent</th><th>Country</th><th class="num">Pax</th><th>Arrival</th><th>Tour ends</th><th>Flight</th><th>Driver</th><th>Vehicle</th></tr></thead><tbody></tbody>
        </table></div>
    </div>
<?php endif; ?>
</main>

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = (d, o) => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-GB', o) : '';
    const na = t => '<span class="na">' + t + '</span>';
    const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');   // local date, never the UTC day
    const hm = t => /^\d{1,2}:\d{2}:\d{2}$/.test(t || '') ? t.slice(0, 5) : (t || '');
    const isArr = <?= $isArr ? 'true' : 'false' ?>;
    const load = document.getElementById('tdd-load');
    if (!load) return;
    fetch('<?= base_url('home/tour_desk/data') ?>', { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json()).then(j => {
            if (j.status !== 200) throw new Error(j.message || 'error');
            const today = j.today, list = document.getElementById('tdd-list'), tb = list.querySelector('tbody');
            if (isArr) {
                document.getElementById('tdd-range').textContent = fmt(today, { day: '2-digit', month: 'short' }) + ' – ' + fmt(j.week_to, { day: '2-digit', month: 'short', year: 'numeric' });
                document.getElementById('tdd-n1').textContent = j.arrivals.length + ' arrival' + (j.arrivals.length === 1 ? '' : 's');
                let w = '', rows = '';
                for (let i = 0; i < 7; i++) {
                    const d = new Date(today + 'T00:00:00'); d.setDate(d.getDate() + i); const key = iso(d);
                    const items = j.arrivals.filter(a => a.start === key).sort((a, b) => (a.time || '99').localeCompare(b.time || '99'));
                    w += '<div class="col' + (i === 0 ? ' today' : '') + '"><h3>' + d.toLocaleDateString('en-GB', { weekday: 'long' }) + ' <small>' + d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) + '</small></h3>'
                        + (items.length ? items.map(a => '<div class="arr"><div class="id"><b>' + esc(a.manual_id) + '</b><span class="pax">' + a.pax + ' pax</span></div>'
                            + '<div class="fl"><i class="bi bi-airplane-fill"></i> ' + (a.flight ? esc(a.flight) : na('flight not entered')) + (a.time ? ' <span class="t">' + esc(hm(a.time)) + '</span>' : '') + '</div>'
                            + '<div class="cl">' + esc(a.client) + '</div>' + ((a.agent || a.country) ? '<div class="ag">' + esc([a.agent, a.country].filter(Boolean).join(' · ')) + '</div>' : '')
                            + '<div class="drv"><i class="bi bi-person-fill"></i> ' + (a.driver ? esc(a.driver) : na('driver not yet allocated')) + '<br><i class="bi bi-truck"></i> ' + (a.vehicle ? esc(a.vehicle) : na(a.driver ? 'vehicle not on record' : '—')) + '</div></div>').join('')
                        : '<div class="none">No arrivals</div>') + '</div>';
                    if (items.length) {
                        rows += '<tr class="day"><td colspan="11">' + d.toLocaleDateString('en-GB', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }) + (i === 0 ? ' — today' : '') + '</td></tr>'
                            + items.map(a => '<tr><td>' + fmt(a.start, { day: '2-digit', month: 'short' }) + '</td><td>' + (a.flight ? esc(a.flight) : na('not entered')) + '</td><td>' + (a.time ? esc(hm(a.time)) : na('—')) + '</td><td class="id">' + esc(a.manual_id) + '</td><td>' + esc(a.client) + '</td><td>' + esc(a.agent) + '</td><td>' + esc(a.country) + '</td><td class="num">' + a.pax + '</td><td>' + (a.driver ? esc(a.driver) : na('not yet allocated')) + '</td><td>' + (a.vehicle ? esc(a.vehicle) : na('—')) + '</td><td>' + fmt(a.end, { day: '2-digit', month: 'short' }) + '</td></tr>').join('');
                    }
                }
                document.getElementById('tdd-week').innerHTML = w; document.getElementById('tdd-week').hidden = false;
                tb.innerHTML = rows || '<tr><td colspan="11" class="na">No arrivals in the next seven days.</td></tr>';
            } else {
                document.getElementById('tdd-range').textContent = fmt(today, { day: '2-digit', month: 'short' }) + ' – ' + fmt(j.month_to, { day: '2-digit', month: 'short', year: 'numeric' });
                document.getElementById('tdd-n1').textContent = j.tours + ' tour' + (j.tours === 1 ? '' : 's');
                const start = new Date(today + 'T00:00:00'); const dow = (start.getDay() + 6) % 7; start.setDate(start.getDate() - dow);
                let h = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(d => '<div class="dow">' + d + '</div>').join('');
                const end = new Date(j.month_to + 'T00:00:00');
                const span = Math.round((end - start) / 86400000) + 1, days = Math.ceil(span / 7) * 7;   // whole weeks up to the end of the month ahead
                for (let i = 0; i < days; i++) {
                    const d = new Date(start); d.setDate(start.getDate() + i);
                    const key = iso(d); const items = j.by_day[key] || []; const out = key < today || d > end;
                    h += '<div class="day' + (out ? ' out' : '') + (key === today ? ' today' : '') + '"><span class="n">' + d.getDate() + '</span>' + (d.getDate() === 1 || i === 0 ? '<span class="m">' + d.toLocaleDateString('en-GB', { month: 'short' }) + '</span>' : '')
                        + (items.length && !out ? '<span class="cnt" title="' + items.length + ' tour(s) arriving">' + items.length + '</span><div class="ids">' + items.slice(0, 4).map(t => '<span title="' + esc(t.client) + ' · ' + t.pax + ' pax"><b>' + esc(t.manual_id) + '</b> ' + esc(t.client) + '</span>').join('') + (items.length > 4 ? '<span class="more">+' + (items.length - 4) + ' more</span>' : '') + '</div>' : '') + '</div>';
                }
                document.getElementById('tdd-cal').innerHTML = h; document.getElementById('tdd-cal').hidden = false;
                let rows = '';
                Object.keys(j.by_day).sort().forEach(key => {
                    const items = j.by_day[key];
                    rows += '<tr class="day"><td colspan="10">' + fmt(key, { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }) + (key === today ? ' — today' : '') + ' · ' + items.length + ' tour' + (items.length === 1 ? '' : 's') + '</td></tr>'
                        + items.map(t => '<tr><td class="id">' + esc(t.manual_id) + '</td><td>' + esc(t.client) + '</td><td>' + esc(t.agent) + '</td><td>' + esc(t.country) + '</td><td class="num">' + t.pax + '</td><td>' + fmt(t.start, { day: '2-digit', month: 'short' }) + '</td><td>' + fmt(t.end, { day: '2-digit', month: 'short' }) + '</td><td>' + (t.flight ? esc(t.flight) + (t.time ? ' · ' + esc(hm(t.time)) : '') : na('not entered')) + '</td><td>' + (t.driver ? esc(t.driver) : na('not yet allocated')) + '</td><td>' + (t.vehicle ? esc(t.vehicle) : na('—')) + '</td></tr>').join('');
                });
                tb.innerHTML = rows || '<tr><td colspan="10" class="na">No tours scheduled in the next month.</td></tr>';
            }
            list.hidden = false; load.remove();
        }).catch(e => { load.innerHTML = 'Could not load: ' + esc(e.message); });

    if (typeof window.logout !== 'function') {
        window.logout = function () {
            Swal.fire({ title: 'Are you sure?', text: 'You want to logout from the system?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#78b848', cancelButtonColor: '#d33', confirmButtonText: 'Yes, Logout!' }).then(r => {
                if (!r.isConfirmed) return;
                $.ajax({ url: '<?= base_url() ?>logout', method: 'GET', success: function (res) { if (res.status == 200) { localStorage.removeItem('previousNotifications'); window.location.href = '<?= session()->get('is_admin') ?>' == 1 ? '<?= base_url() ?>admin' : '<?= base_url() ?>'; } } });
            });
        };
    }
})();
</script>
</body>
</html>
