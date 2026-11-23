<?= view('layouts/head') ?>
<?php
/**
 * tf-masters-r2 (25 Oct 2026) — Master Files, level two (home/master_files/{category}): one category's master pages as small
 * buttons in three columns, each an EXISTING page the sign-in holds. Back and "All categories" return to the five-card landing
 * (home/master_files). Navigation only — nothing behind a button changes.
 */
$esc = static fn ($s) => esc((string) $s);
$sections = $mf_sections ?? [];
$cat = $mf_category ?? null;
?>
<body class="mfd-body">
<style>
    :root { --mf-p:#842484; --mf-g:#78b848; --mf-gold:#e0a526; --mf-ink:#2b2b33; --mf-mut:#6b6f7b; --mf-line:#e8e3df; --mf-font:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; }
    body.mfd-body { background:#fff !important; color:var(--mf-ink); font-family:var(--mf-font); }
    .tdk-top { position:sticky; top:0; z-index:1030; display:flex; align-items:center; gap:14px; padding:10px 28px; }
    .tdk-top .sp { flex:1; }
    .tdk-top .hd-user { display:flex; align-items:center; gap:9px; font-weight:600; color:#fff; }
    .tdk-top .hd-user img { width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid var(--mf-gold); }
    .tdk-top .hd-user small { display:block; font-weight:400; font-size:.75rem; line-height:1.1; color:rgba(255,255,255,.8); }
    .tdk-top .hd-avatar-fallback { font-size:34px; line-height:1; color:#fff; }
    .av-nav-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:38px; padding:0 15px; border-radius:10px; border:0; font-size:13.5px; font-weight:600; line-height:1; white-space:nowrap; cursor:pointer; }
    .av-nav-out { background:transparent; border:1.5px solid rgba(255,255,255,.55); color:#fff !important; }

    .mfd { max-width:1320px; margin:0 auto; padding:18px clamp(14px,2.4vw,30px) 36px; }
    .mfd-head { display:flex; align-items:center; gap:18px; margin:4px 0 16px; }
    .mfd-head .ic { width:76px; height:76px; border-radius:16px; background:rgba(132,36,132,.1); color:var(--mf-p); display:grid; place-items:center; font-size:36px; flex:0 0 auto; }
    .mfd-head h1 { margin:0; font-size:clamp(1.6rem,2.8vw,2.4rem); font-weight:800; letter-spacing:-.02em; color:var(--mf-p); line-height:1.05; }
    .mfd-head p { margin:4px 0 0; color:var(--mf-mut); font-size:1.05rem; }
    .mfd-head .crumb { margin:0 0 2px; font-size:.82rem; font-weight:600; color:var(--mf-mut); }
    .mfd-head .crumb a { color:var(--mf-p); text-decoration:none; }
    .mfd-head .all { margin-left:auto; display:inline-flex; align-items:center; gap:8px; padding:9px 16px; border-radius:999px; background:var(--mf-g); color:#1f2a14; font-weight:700; font-size:.88rem; text-decoration:none !important; white-space:nowrap; box-shadow:0 3px 10px rgba(0,0,0,.1); }
    .mfd-head .all:hover { filter:brightness(1.06); }
    .mfd-sec { border:1px solid var(--mf-line); border-radius:14px; background:#fff; box-shadow:0 2px 10px rgba(20,20,30,.05); margin-bottom:16px; overflow:hidden; }
    .mfd-sec > h2 { margin:0; padding:9px 18px; color:#fff; font-size:1.1rem; font-weight:700; display:flex; align-items:center; gap:12px; }
    .mfd-sec.purple > h2 { background:var(--mf-p); } .mfd-sec.green > h2 { background:var(--mf-g); color:#1f2a14; }
    .mfd-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px 12px; padding:12px 14px 14px; }
    .mfd-btn { display:flex; align-items:center; gap:12px; min-height:50px; padding:6px 12px 6px 8px; border:1px solid var(--mf-line); border-radius:12px; background:#fff; color:var(--mf-ink); text-decoration:none !important; font-weight:700; font-size:.95rem; line-height:1.2; cursor:pointer; text-align:left; width:100%;
      box-shadow:0 1px 3px rgba(20,20,30,.05); transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
    .mfd-btn:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(132,36,132,.14); border-color:rgba(132,36,132,.35); color:var(--mf-ink); }
    .mfd-btn:focus-visible { outline:3px solid var(--mf-gold); outline-offset:2px; }
    .mfd-btn .ic { width:38px; height:38px; border-radius:50%; display:grid; place-items:center; font-size:17px; flex:0 0 auto; background:rgba(120,184,72,.18); color:var(--mf-p); }
    .mfd-sec.green .mfd-btn .ic { background:rgba(132,36,132,.1); color:#78b848; }   /* tf-masters-r2b: the approved Avenir light green */
    .mfd-btn .ch { margin-left:auto; color:var(--mf-mut); font-size:.9rem; transition:transform .2s ease; }
    .mfd-btn[aria-expanded="true"] .ch { transform:rotate(90deg); color:var(--mf-p); }
    .mfd-btn[aria-expanded="true"] { border-color:var(--mf-p); box-shadow:inset 0 0 0 1px var(--mf-p); }
    .mfd-btn small { display:block; font-weight:500; color:var(--mf-mut); font-size:.75rem; margin-top:1px; }
    .mfd-sub { grid-column:1 / -1; display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px 12px; padding:10px 12px 12px; margin:-2px 0 2px; border:1px dashed rgba(132,36,132,.35); border-radius:12px; background:rgba(132,36,132,.04); }
    .mfd-sub[hidden] { display:none; }
    .mfd-sub .t { grid-column:1 / -1; font-size:.75rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; color:var(--mf-p); }
    .mfd-sub .mfd-btn { min-height:42px; font-size:.9rem; }
    .mfd-sub .mfd-btn .ic { width:32px; height:32px; font-size:14px; }
    .mfd-note { color:var(--mf-mut); font-size:.84rem; padding:4px 2px 0; }
    /* tf-stage7: the door's search and its Needs attention (the same look as the Master Files page) */
    .mfd-search { position:relative; max-width:760px; margin:0 0 16px; }
    .mfd-search input { width:100%; height:44px; border-radius:22px; border:1.5px solid var(--mf-line); padding:0 18px; font:inherit; font-size:15px; background:#fff; color:var(--mf-ink); }
    .mfd-search input:focus { outline:none; border-color:var(--mf-p); box-shadow:0 0 0 3px rgba(132,36,132,.18); }
    .mfd-results { position:absolute; left:0; right:0; top:50px; z-index:20; background:#fff; border:1px solid var(--mf-line); border-radius:12px; box-shadow:0 14px 34px rgba(20,20,30,.14); max-height:420px; overflow:auto; }
    .mfd-results a { display:flex; gap:12px; align-items:center; padding:9px 16px; text-decoration:none; color:var(--mf-ink); border-bottom:1px solid var(--mf-line); }
    .mfd-results a:hover { background:rgba(132,36,132,.06); }
    .mfd-results .k { flex:0 0 150px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--mf-p); }
    .mfd-results .t { font-weight:600; } .mfd-results .s { font-size:12.5px; color:var(--mf-mut); } .mfd-results .none { padding:12px 16px; color:var(--mf-mut); }
    .mfd-att { border:1px solid var(--mf-line); border-radius:14px; background:#fff; margin:6px 0 16px; overflow:hidden; }
    .mfd-att .h { padding:10px 16px; border-bottom:1px solid var(--mf-line); display:flex; justify-content:space-between; gap:8px; font-weight:700; color:var(--mf-p); }
    .mfd-att table { width:100%; border-collapse:collapse; font-size:13.5px; } .mfd-att td { padding:9px 10px; border-bottom:1px solid var(--mf-line); vertical-align:top; } .mfd-att tr:last-child td { border-bottom:0; }
    .mfd-att td.lv { width:10px; padding-right:0; } .mfd-att td.lv i { display:inline-block; width:9px; height:9px; border-radius:50%; margin-top:5px; } .mfd-att tr.red td.lv i { background:#b23a32; } .mfd-att tr.amber td.lv i { background:var(--mf-gold); }
    .mfd-att td a { color:var(--mf-ink); text-decoration:none; border-bottom:1px dotted var(--mf-p); } .mfd-att .ok { padding:12px 16px; color:#4e8a26; font-weight:600; }
    @media (max-width:1100px) { .mfd-grid, .mfd-sub { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:640px) { .mfd-grid, .mfd-sub { grid-template-columns:1fr; } .mfd-head .ic { width:56px; height:56px; font-size:26px; border-radius:12px; } .mfd-head .all span { display:none; }
        .tdk-top { padding:8px 12px; gap:6px; } .tdk-top .hd-user span, .av-nav-out span { display:none; } }
    @media (prefers-reduced-motion:reduce) { .mfd-btn { transition:none; } }
</style>

<header class="tdk-top hd-top">
    <?= view('layouts/nav_bar', ['nb_back' => "window.location.href='" . base_url('home/master_files') . "'"]) ?>
    <div class="sp"></div>
    <?= view('layouts/page_search') ?>
    <div class="hd-user">
        <img src="<?= $esc($profile_picture ?? base_url('assets/images/users/no_profile.jpg')) ?>" alt="" onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'bi bi-person-circle hd-avatar-fallback'}))">
        <span><?= $esc($sidebar_employee_name ?? '') ?><small><?= $esc($sidebar_employee_name_profile_name ?? '') ?></small></span>
    </div>
    <button type="button" class="av-nav-btn av-nav-out" onclick="logout()" title="Logout" aria-label="Logout"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></button>
</header>

<main class="mfd">
    <div class="mfd-head">
        <div class="ic" aria-hidden="true"><i class="bi <?= $esc($cat['icon'] ?? 'bi-folder2') ?>"></i></div>
        <div><p class="crumb"><a href="<?= base_url('home/master_files') ?>"><i class="bi bi-folder2"></i> Master Files</a> <i class="bi bi-chevron-right"></i></p><h1><?= $esc($cat['title'] ?? 'Master Files') ?></h1><p><?= $cat ? (int) $cat['count'] . ' master page' . ($cat['count'] === 1 ? '' : 's') . ' in this category' : 'System master records' ?></p></div>
        <a class="all" href="<?= base_url('home/master_files') ?>"><i class="bi bi-grid-3x3-gap-fill"></i> All categories</a>
    </div>

    <?php if (!$sections): ?>
    <div class="mfd-sec purple"><h2><i class="bi bi-folder2-open"></i> Master Files</h2><div class="mfd-grid"><div class="mfd-note">No access yet — the Super Admin assigns access to the master files.</div></div></div>
    <?php endif; ?>

        <?php if (!empty($mf_door_key) && isset(\App\Libraries\TourFlow\MasterSearch::DOORS[$mf_door_key])): ?>
    <div class="mfd-search" id="mfd_search"><input type="search" id="mfd_q" autocomplete="off" placeholder="Search within <?= $esc($mf_category['title'] ?? 'this door') ?><?= $mf_door_key === 'transport' ? ': a driver or guide, a language, a vehicle, or seats (7 seats)' : ($mf_door_key === 'hotels' ? ': a hotel, a town, an account code' : ($mf_door_key === 'activities' ? ': a place, a meal, an activity, a supplier' : ($mf_door_key === 'customers' ? ': a customer or agent' : ': a bank account or supplier'))) ?>…" aria-label="Search within this door"><div class="mfd-results" id="mfd_res" hidden></div></div>
    <?php endif; ?>
<?php foreach ($sections as $key => $sec): ?>
    <section class="mfd-sec <?= $esc($sec['tone']) ?>" aria-labelledby="mfd-h-<?= $esc($key) ?>">
        <h2 id="mfd-h-<?= $esc($key) ?>"><i class="bi <?= $esc($sec['icon']) ?>"></i> <?= $esc($sec['title']) ?></h2>
        <div class="mfd-grid">
            <?php foreach ($sec['items'] as $i => $it): ?>
                <?php if (isset($it['group'])): $gid = 'mfd-g-' . $esc($it['group']); ?>
                <button type="button" class="mfd-btn" aria-expanded="false" aria-controls="<?= $gid ?>" data-group="<?= $gid ?>">
                    <span class="ic"><i class="bi <?= $esc($it['icon']) ?>"></i></span>
                    <span><?= $esc($it['label']) ?><small><?= count($it['members']) ?> pages · <?= $esc($it['sub']) ?></small></span>
                    <i class="bi bi-chevron-right ch"></i>
                </button>
                <div class="mfd-sub" id="<?= $gid ?>" hidden data-after="<?= $i ?>">
                    <span class="t"><?= $esc($it['label']) ?></span>
                    <?php foreach ($it['members'] as $m): ?>
                    <a class="mfd-btn" href="<?= $esc(base_url($m['url'])) ?>"><span class="ic"><i class="bi bi-building"></i></span><span><?= $esc($m['label']) ?></span><i class="bi bi-chevron-right ch"></i></a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <a class="mfd-btn" href="<?= $esc(base_url($it['url'])) ?>">
                    <span class="ic"><i class="bi <?= $esc($it['icon']) ?>"></i></span>
                    <span><?= $esc($it['label']) ?></span>
                    <i class="bi bi-chevron-right ch"></i>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
    <?php if ($cat && !$cat['count']): ?><div class="mfd-sec green"><h2><i class="bi <?= $esc($cat['icon']) ?>"></i> <?= $esc($cat['title']) ?></h2><div class="mfd-grid"><div class="mfd-note">No master pages in this category on this sign-in yet.</div></div></div><?php endif; ?>
    <?php if (!empty($mf_door_key) && isset(\App\Libraries\TourFlow\MasterSearch::DOORS[$mf_door_key])): ?>
    <div class="mfd-att" id="mfd_att"><div class="h"><span>Needs attention — <?= $esc($mf_category['title'] ?? '') ?></span><span id="mfd_att_n" style="font-weight:400;color:var(--mf-mut);font-size:.85rem">checking…</span></div><div id="mfd_att_b"><div class="ok">Checking…</div></div></div>
    <?php endif; ?>
    <p class="mfd-note">Every button opens the existing master page; the list follows the sign-in's own privileges, exactly as the Master Files desk on Home does.</p>
</main>

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    // Hotel Master folds its pages open in place, directly under the row the button sits in
    document.querySelectorAll('.mfd-btn[data-group]').forEach(btn => {
        const panel = document.getElementById(btn.dataset.group);
        const grid = btn.closest('.mfd-grid');
        const place = () => {   // move the panel after the last button of the row the Hotel Master button is on
            const cells = [...grid.children].filter(c => !c.classList.contains('mfd-sub'));
            const top = btn.getBoundingClientRect().top;
            let last = btn;
            cells.forEach(c => { if (Math.abs(c.getBoundingClientRect().top - top) < 4) last = c; });
            last.after(panel);
        };
        btn.addEventListener('click', () => {
            const open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', open ? 'false' : 'true');
            if (!open) place();
            panel.hidden = open;
        });
    });
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
<script>
// tf-stage7: the door's own search and Needs attention (the same endpoints as the Master Files page, limited to this door)
(function () {
    'use strict';
    var DOOR = <?= json_encode((string) ($mf_door_key ?? '')) ?>, BASE = '<?= base_url() ?>';
    var q = document.getElementById('mfd_q'), res = document.getElementById('mfd_res');
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
    if (q) {
        var t = null, last = '';
        var run = function () {
            var v = q.value.trim(); if (v === last) return; last = v;
            if (v.length < 2) { res.hidden = true; res.innerHTML = ''; return; }
            fetch(BASE + 'home/master_files/search?door=' + encodeURIComponent(DOOR) + '&q=' + encodeURIComponent(v), { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); }).then(function (j) {
                var rows = j.data || [];
                res.innerHTML = rows.length ? rows.map(function (r) { return '<a href="' + esc(BASE + r.url) + '"><span class="k">' + esc(r.kind_label) + '</span><span><span class="t">' + esc(r.title) + '</span><br><span class="s">' + esc(r.sub) + '</span></span></a>'; }).join('') : '<div class="none">Nothing in this door matches “' + esc(v) + '”.</div>';
                res.hidden = false;
            });
        };
        q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(run, 220); });
        q.addEventListener('keydown', function (e) { if (e.key === 'Escape') res.hidden = true; if (e.key === 'Enter') { var a = res.querySelector('a'); if (a) a.click(); } });
        document.addEventListener('click', function (e) { if (!document.getElementById('mfd_search').contains(e.target)) res.hidden = true; });
    }
    var ab = document.getElementById('mfd_att_b');
    if (ab) {
        fetch(BASE + 'home/master_files/attention?door=' + encodeURIComponent(DOOR), { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); }).then(function (j) {
            var rows = j.data || [];
            document.getElementById('mfd_att_n').textContent = rows.length ? rows.length + ' item' + (rows.length === 1 ? '' : 's') : '';
            ab.innerHTML = rows.length ? '<table>' + rows.map(function (r) { return '<tr class="' + esc(r.level) + '"><td class="lv"><i></i></td><td><a href="' + esc(BASE + r.url) + '">' + esc(r.what) + '</a></td><td style="color:var(--mf-mut)">' + esc(r.why) + '</td></tr>'; }).join('') + '</table>' : '<div class="ok"><i class="bi bi-check2-circle"></i> Nothing needs attention in this door today.</div>';
        }).catch(function () { ab.innerHTML = '<div class="ok" style="color:#b23a32">The list could not be read.</div>'; });
    }
})();
</script>
</body>
</html>
