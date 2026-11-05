<?= view('layouts/head') ?>
<?php
/**
 * tf-tourdesk-r2 (25 Oct 2026) — the Tour Desk page (home/tour_desk), built to the approved design picture.
 * The scenery, the hero and every tile photograph are the supplied design image itself (public/assets/images/tour_desk/,
 * design_source.jpg is the untouched original); the captions, badges and arrows are live HTML on top of it, so the page reads
 * at any width and every tile opens an EXISTING page: Tour Inquiries, Confirmed Tours, Tour Board, Upcoming Tours (the next
 * month — its own calendar view) and Next Arrivals (the next seven days — its own arrivals view).
 * Read-only: nothing here changes a record. A tile is linked only when the sign-in may open its page.
 */
$esc = static fn ($s) => esc((string) $s);
$img = static fn (string $f) => base_url('assets/images/tour_desk/' . $f);
$tiles = [];
foreach (($td['tiles'] ?? []) as $t) {
    $tiles[$t['key']] = $t;
}
// caption text, badge colour and icon per tile — as on the design picture
$cap = [
    'inquiries' => ['Manage all tour inquiries, quotations and customer communications.', '#861b62', 'bi-people-fill',   'tile_inquiries.jpg'],
    'confirmed' => ['View and manage all confirmed tours and upcoming departures.',      '#6e9e3e', 'bi-calendar2-check', 'tile_confirmed.jpg'],
    'board'     => ['Check tour schedules, vehicle allocation and tour progress.',       '#d59b35', 'bi-map-fill',       'tile_board.jpg'],
    'upcoming'  => ['View all scheduled tours for the next 1 month.',                    '#2559a7', 'bi-calendar2-check', 'tile_upcoming.jpg'],
    'arrivals'  => ['View arriving tours for the next 7 days with flight details and arrival arrangements.', '#ba3b2b', 'bi-airplane-fill', 'tile_arrivals.jpg'],
];
// where each card sits on the picture (percent of the stage: left, top, width, height), the design's own positions regularised
$pos = [
    'inquiries' => [3.6, 26.4, 29.6, 33.5], 'confirmed' => [35.2, 26.4, 29.6, 33.5], 'board' => [66.8, 26.4, 29.6, 33.5],
    'upcoming'  => [3.6, 60.4, 29.6, 33.5], 'arrivals'  => [35.2, 60.4, 29.6, 33.5], 'scenery' => [66.8, 60.4, 29.6, 33.5],
];
?>
<body class="tdk-body">
<style>
    :root { --tdk-p:#842484; --tdk-g:#78b848; --tdk-gold:#e0a526; --tdk-ink:#2b2b33; --tdk-mut:#6b6f7b; --tdk-line:#e8e3df; --tdk-font:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; }
    body.tdk-body { background:#fff !important; color:var(--tdk-ink); font-family:var(--tdk-font); }
    .tdk-top { position:sticky; top:0; z-index:1030; display:flex; align-items:center; gap:14px; padding:10px 28px; }
    .tdk-top .sp { flex:1; }
    .tdk-top .hd-user { display:flex; align-items:center; gap:9px; font-weight:600; color:#fff; }
    .tdk-top .hd-user img { width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid var(--tdk-gold); }
    .tdk-top .hd-user small { display:block; font-weight:400; font-size:.75rem; line-height:1.1; color:rgba(255,255,255,.8); }
    .tdk-top .hd-avatar-fallback { font-size:34px; line-height:1; color:#fff; }
    .av-nav-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:38px; padding:0 15px; border-radius:10px; border:0; font-size:13.5px; font-weight:600; line-height:1; white-space:nowrap; cursor:pointer; }
    .av-nav-out { background:transparent; border:1.5px solid rgba(255,255,255,.55); color:#fff !important; }
    .visually-hidden { position:absolute !important; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }

    /* ---- the stage: the design picture (scenery, hero text) scaled to the page width; the six cards sit on it at the picture's own positions ---- */
    .tdk-stage { position:relative; width:100%; max-width:1920px; margin:0 auto; aspect-ratio:1320 / 677; container-type:inline-size;
      background:#dfe9ee url("<?= $img('stage_bg.jpg') ?>") center top / 100% 100% no-repeat; }
    .tdk-hero-sm { display:none; }
    .tdk-card { position:absolute; left:var(--l); top:var(--t); width:var(--w); height:var(--h); display:flex; flex-direction:column; background:#fff; border-radius:1.5cqw; padding:.38cqw;
      box-shadow:0 .3cqw .9cqw rgba(20,20,30,.22), 0 .08cqw .25cqw rgba(20,20,30,.18); overflow:hidden; text-decoration:none !important; color:var(--tdk-ink);
      transition:transform .22s ease, box-shadow .22s ease; animation:tdk-rise .6s ease both; }
    .tdk-card:nth-child(2) { animation-delay:.07s; } .tdk-card:nth-child(3) { animation-delay:.14s; } .tdk-card:nth-child(4) { animation-delay:.21s; } .tdk-card:nth-child(5) { animation-delay:.28s; } .tdk-card:nth-child(6) { animation-delay:.35s; }
    @keyframes tdk-rise { from { transform:translateY(12px); } to { transform:none; } }
    a.tdk-card:hover { transform:translateY(-.4cqw); box-shadow:0 .9cqw 1.8cqw rgba(20,20,30,.28); }
    a.tdk-card:focus-visible { outline:.3cqw solid var(--tdk-gold); outline-offset:.2cqw; }
    .tdk-card.off { cursor:not-allowed; }
    .tdk-card.off .cap { opacity:.6; }
    .tdk-card .ph { display:block; height:56%; border-radius:1.15cqw; overflow:hidden; background:#cfd8dc; }
    .tdk-card .ph img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .5s ease; }
    a.tdk-card:hover .ph img { transform:scale(1.04); }
    .tdk-card.photo .ph { height:100%; }
    .tdk-card .cap { flex:1; display:flex; align-items:center; gap:1.1cqw; padding:.4cqw 1cqw .4cqw 1.1cqw; }
    .tdk-card .bd { flex:0 0 auto; width:5.5cqw; height:5.5cqw; border-radius:50%; background:var(--c); color:#fff; display:grid; place-items:center; font-size:2.6cqw; box-shadow:0 .2cqw .5cqw rgba(0,0,0,.18); }
    .tdk-card .tx { flex:1; min-width:0; }
    .tdk-card .tx b { display:block; color:var(--c); font-size:1.7cqw; font-weight:800; line-height:1.15; letter-spacing:-.01em; }
    .tdk-card .tx small { display:block; color:#5b5f6a; font-size:1.02cqw; line-height:1.32; margin-top:.3cqw; }
    .tdk-card .go { flex:0 0 auto; width:3.1cqw; height:3.1cqw; border-radius:50%; background:var(--c); color:#fff; display:grid; place-items:center; font-size:1.5cqw; filter:brightness(.82); }
    .tdk-card .go i { font-weight:900; }
    /* live touches on top of the design: a count chip on the two calendar tiles, "today at a glance" on the scenery tile */
    .tdk-card .ph { position:relative; }
    .tdk-card .chip { position:absolute; right:.8cqw; top:.8cqw; background:rgba(255,255,255,.93); color:var(--c); border-radius:999px; padding:.3cqw .9cqw; font-size:1cqw; font-weight:800; box-shadow:0 .1cqw .4cqw rgba(0,0,0,.2); display:none; }
    .tdk-card .chip.on { display:inline-block; animation:tdk-rise .5s ease both; }
    .tdk-card.photo .glance { position:absolute; left:1cqw; right:1cqw; bottom:1cqw; background:rgba(255,255,255,.9); backdrop-filter:blur(4px); border-radius:1cqw; padding:.8cqw 1.1cqw; display:none; grid-template-columns:1fr 1fr; gap:.5cqw 1cqw; color:var(--tdk-ink); }
    .tdk-card.photo .glance.on { display:grid; animation:tdk-rise .6s ease both; }
    .tdk-card.photo .glance .d { grid-column:1 / -1; font-weight:800; font-size:1.15cqw; color:var(--tdk-p); display:flex; align-items:center; gap:.5cqw; }
    .tdk-card.photo .glance .d small { margin-left:auto; font-weight:600; color:var(--tdk-mut); font-size:.9cqw; }
    .tdk-card.photo .glance a { text-decoration:none; color:inherit; display:flex; align-items:center; gap:.6cqw; font-size:.95cqw; line-height:1.2; }
    .tdk-card.photo .glance a b { font-size:1.6cqw; font-weight:800; color:var(--k); }
    @media (max-width:767px) { .tdk-card .chip { right:10px; top:10px; padding:3px 10px; font-size:.8rem; } .tdk-card.photo .ph { height:170px; } .tdk-card.photo .glance { left:10px; right:10px; bottom:10px; border-radius:12px; padding:9px 12px; gap:4px 10px; }
        .tdk-card.photo .glance .d { font-size:.95rem; } .tdk-card.photo .glance .d small { font-size:.75rem; } .tdk-card.photo .glance a { font-size:.8rem; } .tdk-card.photo .glance a b { font-size:1.3rem; } }
    .tdk-below { max-width:1320px; margin:0 auto; padding:14px clamp(16px,2.6vw,40px) 30px; color:var(--tdk-mut); font-size:.84rem; }
    @media (max-width:767px) {
        .tdk-stage { aspect-ratio:auto; background:none; display:grid; gap:14px; padding:0 14px 14px; }
        .tdk-hero-sm { display:block; width:calc(100% + 28px); margin:0 -14px 6px; height:auto; }
        .tdk-card { position:static; width:auto; height:auto; border-radius:18px; padding:5px; box-shadow:0 2px 6px rgba(20,20,30,.12),0 10px 24px rgba(20,20,30,.12); }
        .tdk-card .ph { height:150px; border-radius:14px; }
        .tdk-card .cap { gap:12px; padding:10px 12px; }
        .tdk-card .bd { width:56px; height:56px; font-size:26px; }
        .tdk-card .tx b { font-size:1.15rem; } .tdk-card .tx small { font-size:.82rem; margin-top:3px; }
        .tdk-card .go { width:34px; height:34px; font-size:16px; }
        .tdk-top { padding:8px 12px; gap:6px; } .tdk-top .hd-user span, .av-nav-out span { display:none; }
    }
    @media (prefers-reduced-motion:reduce) { .tdk-card, .tdk-card .ph img { animation:none !important; transition:none !important; } }
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

<main>
    <h1 class="visually-hidden">Tour Desk — manage inquiries, tours and daily operations with outstanding service</h1>
    <div class="tdk-stage" role="region" aria-label="Tour Desk">
        <img class="tdk-hero-sm" src="<?= $img('hero_sm.jpg') ?>" alt="Tour Desk — Explore Sri Lanka">
        <?php foreach (['inquiries', 'confirmed', 'board', 'upcoming', 'arrivals'] as $k):
            $t = $tiles[$k] ?? null; if (!$t) { continue; }
            [$text, $colour, $icon, $photo] = $cap[$k]; [$l, $tp, $w, $h] = $pos[$k];
            $tag = $t['ok'] ? 'a' : 'div'; ?>
        <<?= $tag ?> class="tdk-card<?= $t['ok'] ? '' : ' off' ?>" style="--l:<?= $l ?>%;--t:<?= $tp ?>%;--w:<?= $w ?>%;--h:<?= $h ?>%;--c:<?= $colour ?>" data-tile="<?= $esc($k) ?>"
            <?= $t['ok'] ? 'href="' . $esc(base_url($t['url'])) . '"' : 'title="No access yet — the Super Admin assigns access" aria-disabled="true"' ?>>
            <span class="ph"><img src="<?= $img($photo) ?>" alt=""><?php if (in_array($k, ['upcoming', 'arrivals'], true)): ?><span class="chip" id="tdk-chip-<?= $k ?>"></span><?php endif; ?></span>
            <span class="cap">
                <span class="bd" aria-hidden="true"><i class="bi <?= $icon ?>"></i></span>
                <span class="tx"><b><?= $esc($t['title']) ?></b><small><?= $esc($text) ?></small></span>
                <?php if ($t['ok']): ?><span class="go" aria-hidden="true"><i class="bi bi-chevron-right"></i></span><?php endif; ?>
            </span>
        </<?= $tag ?>>
        <?php endforeach; [$l, $tp, $w, $h] = $pos['scenery']; ?>
        <div class="tdk-card photo" style="--l:<?= $l ?>%;--t:<?= $tp ?>%;--w:<?= $w ?>%;--h:<?= $h ?>%"><span class="ph"><img src="<?= $img('tile_scenery.jpg') ?>" alt="">
            <?php if (!empty($td_can_calendar)): ?><span class="glance" id="tdk-glance" aria-live="polite">
                <span class="d"><i class="bi bi-sun-fill" style="color:var(--tdk-gold)"></i> <span id="tdk-g-date">Today</span><small>at a glance</small></span>
                <a href="<?= base_url('home/tour_desk/arrivals') ?>" style="--k:#ba3b2b"><b id="tdk-g-today">–</b><span>arriving<br>today</span></a>
                <a href="<?= base_url('home/tour_desk/arrivals') ?>" style="--k:#ba3b2b"><b id="tdk-g-week">–</b><span>arrivals<br>next 7 days</span></a>
                <a href="<?= base_url('home/tour_desk/upcoming') ?>" style="--k:#2559a7"><b id="tdk-g-month">–</b><span>tours<br>next 1 month</span></a>
                <a href="<?= base_url('home/tour_desk/upcoming') ?>" style="--k:#2559a7"><b id="tdk-g-pax">–</b><span>pax<br>next 1 month</span></a>
            </span><?php endif; ?></span></div>
    </div>
    <p class="tdk-below">Development build: every tile opens the existing page; Upcoming Tours and Next Arrivals open their own calendar and arrivals views, which read the confirmed tours as they stand and change nothing.</p>
</main>

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    // live counts on the design: the two calendar tiles and the "today at a glance" panel (read-only data)
    const glance = document.getElementById('tdk-glance');
    if (glance) {
        fetch('<?= base_url('home/tour_desk/data') ?>', { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json()).then(j => {
                if (j.status !== 200) return;
                const n = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
                const todayN = j.arrivals.filter(a => a.start === j.today).length;
                let pax = 0; Object.values(j.by_day).forEach(l => l.forEach(t => { pax += Number(t.pax) || 0; }));
                n('tdk-g-date', new Date(j.today + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'long', day: '2-digit', month: 'long' }));
                n('tdk-g-today', todayN); n('tdk-g-week', j.arrivals.length); n('tdk-g-month', j.tours); n('tdk-g-pax', pax);
                glance.classList.add('on');
                const c1 = document.getElementById('tdk-chip-upcoming'), c2 = document.getElementById('tdk-chip-arrivals');
                if (c1) { c1.textContent = j.tours + ' tour' + (j.tours === 1 ? '' : 's'); c1.classList.add('on'); }
                if (c2) { c2.textContent = j.arrivals.length + ' arrival' + (j.arrivals.length === 1 ? '' : 's'); c2.classList.add('on'); }
            }).catch(() => {});
    }

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
