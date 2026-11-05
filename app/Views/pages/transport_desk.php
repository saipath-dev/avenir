<?= view('layouts/head') ?>
<?php
/**
 * tf-transdesk-r1 (25 Oct 2026) — the Transport Operations Desk page (home/transport_desk), built to the approved hill-country
 * artwork. Design stage only: the scenery, the hero with the graphical Avenir logo and every tile photograph are the supplied design
 * image itself (public/assets/images/transport_desk/, design_source.jpg is the untouched original); captions, badges, arrows, the
 * count chip and the "today at a glance" strip are live HTML on top. Seven sections: Transport Operations, Pending Allocations,
 * Driver & Guide Handling, Railway Bookings (visual only — no booking page exists yet), Upcoming Tours, Route Maps & Mileage and
 * Today at a Glance. Read-only; a tile is linked only when the sign-in may open its page.
 */
$esc = static fn ($s) => esc((string) $s);
$img = static fn (string $f) => base_url('assets/images/transport_desk/' . $f);
$tiles = [];
foreach (($xd['tiles'] ?? []) as $t) {
    $tiles[$t['key']] = $t;
}
// caption text, badge colour and icon per tile — as on the design picture
$cap = [
    'ops'      => ['Manage every tour movement.',                     '#6b1b6e', 'bi-bus-front-fill',  'tile_ops.jpg'],
    'pending'  => ['Assign vehicles, drivers and guides.',            '#566936', 'bi-people-fill',     'tile_pending.jpg'],
    'crew'     => ['Coordinate people for each journey.',             '#ad803e', 'bi-person-fill-gear', 'tile_crew.jpg'],
    'rail'     => ['Arrange scenic train journeys.',                  '#22469d', 'bi-train-front-fill', 'tile_rail.jpg'],
    'upcoming' => ['View scheduled tours for the next 1 month.',      '#274c9f', 'bi-calendar2-check', 'tile_upcoming.jpg'],
    'routes'   => ['Track routes, distance and driver packs.',        '#a53a2c', 'bi-geo-alt-fill',    'tile_routes.jpg'],
];
// where each card sits on the picture (percent of the stage: left, top, width, height), the design's own positions regularised
$pos = [
    'ops'  => [3.6, 27.3, 29.6, 32.6], 'pending'  => [35.2, 27.3, 29.6, 32.6], 'crew'   => [66.8, 27.3, 29.6, 32.6],
    'rail' => [3.6, 61.8, 29.6, 29.1], 'upcoming' => [35.2, 61.8, 29.6, 29.1], 'routes' => [66.8, 61.8, 29.6, 29.1],
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
    .tdk-stage { position:relative; width:100%; max-width:1920px; margin:0 auto; aspect-ratio:1320 / 685; container-type:inline-size;
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
    .tdk-card.rail.off { cursor:default; } .tdk-card.rail.off .cap { opacity:1; }   /* the visual section awaiting its booking page */
    .tdk-card .ph { display:block; height:56%; border-radius:1.15cqw; overflow:hidden; background:#cfd8dc; }
    .tdk-card .ph img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .5s ease; }
    a.tdk-card:hover .ph img { transform:scale(1.04); }
    .tdk-card.photo .ph { height:100%; }
    .tdk-card .cap { flex:1; display:flex; align-items:center; gap:1.1cqw; padding:.4cqw 1cqw .4cqw 1.1cqw; }
    .tdk-card .bd { flex:0 0 auto; width:5.5cqw; height:5.5cqw; border-radius:50%; background:var(--c); color:#fff; display:grid; place-items:center; font-size:2.6cqw; box-shadow:0 .2cqw .5cqw rgba(0,0,0,.18); }
    .tdk-card .tx { flex:1; min-width:0; }
    .tdk-card .tx b { display:block; color:var(--c); font-size:1.5cqw; font-weight:800; line-height:1.12; letter-spacing:-.015em; }
    .tdk-card .tx small { display:block; color:#5b5f6a; font-size:.98cqw; line-height:1.32; margin-top:.3cqw; }
    .tdk-card .go { flex:0 0 auto; width:3.1cqw; height:3.1cqw; border-radius:50%; background:var(--c); color:#fff; display:grid; place-items:center; font-size:1.5cqw; filter:brightness(.82); }
    .tdk-card .go i { font-weight:900; }
    /* live touches on top of the design: a count chip on the two calendar tiles, "today at a glance" on the scenery tile */
    .tdk-card .ph { position:relative; }
    .tdk-card .chip { position:absolute; right:.8cqw; top:.8cqw; background:rgba(255,255,255,.93); color:var(--c); border-radius:999px; padding:.3cqw .9cqw; font-size:1cqw; font-weight:800; box-shadow:0 .1cqw .4cqw rgba(0,0,0,.2); display:none; }
    .tdk-card .chip.on { display:inline-block; animation:tdk-rise .5s ease both; }
    .tdk-glance { position:absolute; left:3.2%; right:3.2%; top:92%; height:7.2%; background:#fff; border-radius:1.1cqw; box-shadow:0 .25cqw .8cqw rgba(20,20,30,.2); display:flex; align-items:center; padding:0 1.2cqw; gap:.6cqw; opacity:0; }
    .tdk-glance.on { opacity:1; animation:tdk-rise .6s ease both; }
    .tdk-glance .d { display:flex; align-items:center; gap:.7cqw; font-weight:800; font-size:1.35cqw; color:var(--tdk-p); padding-right:1.2cqw; border-right:1px solid var(--tdk-line); flex:0 0 auto; }
    .tdk-glance .d i { width:2.2cqw; height:2.2cqw; border-radius:50%; background:rgba(132,36,132,.1); display:grid; place-items:center; font-size:1.1cqw; }
    .tdk-glance .d small { display:block; font-weight:600; font-size:.8cqw; color:var(--tdk-mut); line-height:1.1; }
    .tdk-glance a { flex:1; display:flex; align-items:center; justify-content:center; gap:.6cqw; text-decoration:none; color:var(--tdk-ink); font-size:.95cqw; line-height:1.15; border-left:1px solid var(--tdk-line); }
    .tdk-glance a:first-of-type { border-left:0; }
    .tdk-glance a i { width:2.4cqw; height:2.4cqw; border-radius:50%; background:var(--k); color:#fff; display:grid; place-items:center; font-size:1.15cqw; flex:0 0 auto; }
    .tdk-glance a b { font-size:1.6cqw; font-weight:800; color:var(--k); min-width:1.4cqw; text-align:right; }
    .tdk-glance a span { color:#4b4f5a; }
    .tdk-card.rail .cap .tx small em { display:block; font-style:normal; color:var(--tdk-mut); font-size:.85cqw; margin-top:.15cqw; }
    @media (max-width:767px) { .tdk-card .chip { right:10px; top:10px; padding:3px 10px; font-size:.8rem; } .tdk-card.photo .ph { height:170px; } .tdk-card.photo .glance { left:10px; right:10px; bottom:10px; border-radius:12px; padding:9px 12px; gap:4px 10px; }
        .tdk-glance { position:static; height:auto; border-radius:16px; flex-wrap:wrap; padding:10px 12px; gap:8px; }
        .tdk-glance .d { width:100%; border-right:0; font-size:1rem; padding:0 0 6px; border-bottom:1px solid var(--tdk-line); } .tdk-glance .d i { width:28px; height:28px; font-size:14px; } .tdk-glance .d small { font-size:.72rem; }
        .tdk-glance a { flex:1 1 45%; justify-content:flex-start; border-left:0; font-size:.8rem; gap:8px; } .tdk-glance a i { width:30px; height:30px; font-size:14px; } .tdk-glance a b { font-size:1.3rem; }
        .tdk-card.rail .cap .tx small em { font-size:.75rem; } }
    .tdk-more { max-width:1320px; margin:14px auto 0; padding:0 clamp(16px,2.6vw,40px); display:flex; flex-wrap:wrap; gap:8px 10px; align-items:center; font-size:.84rem; }
    .tdk-more .lk { color:var(--tdk-mut); font-weight:700; margin-right:4px; }
    .tdk-more a { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border:1px solid var(--tdk-line); border-radius:999px; color:var(--tdk-p); background:#fff; text-decoration:none; font-weight:600; }
    .tdk-more a:hover { background:rgba(132,36,132,.08); }
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
    <h1 class="visually-hidden">Transport Operations Desk — coordinate vehicles, drivers, guides and every journey</h1>
    <div class="tdk-stage" role="region" aria-label="Transport Operations Desk">
        <img class="tdk-hero-sm" src="<?= $img('hero_sm.jpg') ?>" alt="Transport Operations Desk">
        <?php foreach (['ops', 'pending', 'crew', 'rail', 'upcoming', 'routes'] as $k):
            $t = $tiles[$k] ?? null; if (!$t) { continue; }
            [$text, $colour, $icon, $photo] = $cap[$k]; [$l, $tp, $w, $h] = $pos[$k];
            $tag = $t['ok'] ? 'a' : 'div'; ?>
        <<?= $tag ?> class="tdk-card<?= $t['ok'] ? '' : ' off' ?><?= $k === 'rail' ? ' rail' : '' ?>" style="--l:<?= $l ?>%;--t:<?= $tp ?>%;--w:<?= $w ?>%;--h:<?= $h ?>%;--c:<?= $colour ?>" data-tile="<?= $esc($k) ?>"
            <?= $t['ok'] ? 'href="' . $esc(base_url($t['url'])) . '"' : 'title="' . $esc($t['note'] ?? 'No access yet — the Super Admin assigns access') . '" aria-disabled="true"' ?>>
            <span class="ph"><img src="<?= $img($photo) ?>" alt=""><?php if (in_array($k, ['upcoming', 'ops', 'pending'], true)): ?><span class="chip" id="tdk-chip-<?= $k ?>"></span><?php endif; ?></span>
            <span class="cap">
                <span class="bd" aria-hidden="true"><i class="bi <?= $icon ?>"></i></span>
                <span class="tx"><b><?= $esc($t['title']) ?></b><small><?= $esc($text) ?><?php if (!empty($t['note'])): ?><em><?= $esc($t['note']) ?></em><?php endif; ?></small></span>
                <?php if ($t['ok']): ?><span class="go" aria-hidden="true"><i class="bi bi-chevron-right"></i></span><?php endif; ?>
            </span>
        </<?= $tag ?>>
        <?php endforeach; ?>
        <?php if (!empty($td_can_calendar)): ?>
        <div class="tdk-glance" id="tdk-glance" aria-live="polite">
            <span class="d"><i class="bi bi-eye-fill"></i><span>Today at a Glance<small id="tdk-g-date">today</small></span></span>
            <a href="<?= base_url('home/tour_desk/arrivals?desk=transport') ?>" style="--k:#274c9f"><i class="bi bi-bus-front-fill"></i><b id="tdk-g-today">–</b><span>departures<br>today</span></a>
            <a href="<?= base_url('home/tour_desk/arrivals?desk=transport') ?>" style="--k:#566936"><i class="bi bi-truck-front-fill"></i><b id="tdk-g-veh">–</b><span>vehicles<br>assigned</span></a>
            <a href="<?= base_url('home/tour_desk/arrivals?desk=transport') ?>" style="--k:#a53a2c"><i class="bi bi-people-fill"></i><b id="tdk-g-drv">–</b><span>drivers<br>pending</span></a>
            <a href="<?= base_url('home/tour_desk/arrivals?desk=transport') ?>" style="--k:#ad803e"><i class="bi bi-calendar2-week-fill"></i><b id="tdk-g-week">–</b><span>tours<br>next 7 days</span></a>
        </div>
        <?php endif; ?>
    </div>
    <?php if (!empty($xd_more)): ?>
    <nav class="tdk-more" aria-label="Other Transport Desk pages"><span class="lk"><i class="bi bi-three-dots"></i> Also on this desk</span>
        <?php foreach ($xd_more as $m): ?><a href="<?= $esc(base_url($m['url'])) ?>"><i class="bi <?= $esc($m['icon']) ?>"></i> <?= $esc($m['label']) ?></a><?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <p class="tdk-below">Development build, design stage: every linked tile opens the existing page; Upcoming Tours opens the one-month calendar view. The glance figures read the confirmed tours and their allocations for the next seven days as they stand and change nothing. Railway Bookings has no booking page yet and is shown as a visual section only.</p>
</main>

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    // live figures on the design: the Upcoming Tours chip and the "today at a glance" strip, from the same read-only feed as the Tour Desk
    const glance = document.getElementById('tdk-glance');
    if (glance) {
        fetch('<?= base_url('home/tour_desk/data') ?>', { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json()).then(j => {
                if (j.status !== 200) return;
                const n = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
                n('tdk-g-date', new Date(j.today + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'long', day: '2-digit', month: 'long' }));
                n('tdk-g-today', j.arrivals.filter(a => a.start === j.today).length);                 // tours starting today
                n('tdk-g-veh', j.arrivals.filter(a => a.vehicle).length);                             // next 7 days with a vehicle on record
                n('tdk-g-drv', j.arrivals.filter(a => !a.driver).length);                             // next 7 days with no driver allocated yet
                n('tdk-g-week', j.arrivals.length);
                glance.classList.add('on');
                const c1 = document.getElementById('tdk-chip-upcoming');
                if (c1) { c1.textContent = j.tours + ' tour' + (j.tours === 1 ? '' : 's'); c1.classList.add('on'); }
            }).catch(() => {});
    }

    // tf-rotation-r1: tours awaiting transport review (Transport Operations) and awaiting a driver (Pending Allocations)
    fetch('<?= base_url('tour/transport_ops/allocations/data') ?>', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json()).then(j => {
            if (j.status !== 200) return;
            const set = (id, n, word) => { const el = document.getElementById(id); if (el) { el.textContent = n + ' ' + word; el.classList.add('on'); } };
            set('tdk-chip-ops', j.data.counts.awaiting_review, 'to review');
            set('tdk-chip-pending', j.data.counts.pending, 'pending');
        }).catch(() => {});

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
