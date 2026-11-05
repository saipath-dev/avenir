<?= view('layouts/head') ?>
<?php
/**
 * Master Files, level one (home/master_files) — tf-stage3c (29 Oct 2026): the page as the approved mockup draws it
 * (Master Panel proposal, section B): one full-width search box with the green Add record button, compact door tiles with
 * an orchid top rule, a serif title, the record count in orchid, one line saying what is inside and a small Open button,
 * then the "Needs attention today" card as a plain What / Door / Why it matters table. The functions from Stage 3 are
 * unchanged: the search (home/master_files/search) opens the record on its existing page; the attention list
 * (home/master_files/attention) is the same list; every door opens the same level-two page; nothing else moved.
 */
$esc = static fn ($s) => esc((string) $s);
$sections = $mf_sections ?? [];
$counts   = $mf_counts ?? [];
$adds     = $mf_add ?? [];
?>
<body class="mfl-body">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Source+Serif+4:wght@600;700&display=swap">
<style>
    :root { --mf-bg:#f6f4f7; --mf-card:#fff; --mf-fg:#22202a; --mf-mut:#6b6577; --mf-line:#e3dde6; --mf-p:#842484; --mf-p-soft:#f3e6f3; --mf-g:#78b848; --mf-g-dark:#5d9634; --mf-g-soft:#eef6e6; --mf-amber:#b7791f; --mf-amber-soft:#fcf1dc; --mf-red:#b23a32; --mf-red-soft:#fbe9e7; --mf-gold:#e0a526;
            --mf-display:"Source Serif 4", Georgia, serif; --mf-body:"Source Sans 3", "Segoe UI", Arial, sans-serif; }
    body.mfl-body { background:var(--mf-bg) !important; color:var(--mf-fg); font-family:var(--mf-body); font-size:15px; line-height:1.45; }
    .tdk-top { position:sticky; top:0; z-index:1030; display:flex; align-items:center; gap:14px; padding:10px 28px; }
    .tdk-top .sp { flex:1; }
    .tdk-top .hd-user { display:flex; align-items:center; gap:9px; font-weight:600; color:#fff; }
    .tdk-top .hd-user img { width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid var(--mf-gold); }
    .tdk-top .hd-user small { display:block; font-weight:400; font-size:.75rem; line-height:1.1; color:rgba(255,255,255,.8); }
    .tdk-top .hd-avatar-fallback { font-size:34px; line-height:1; color:#fff; }
    .av-nav-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:38px; padding:0 15px; border-radius:10px; border:0; font-size:13.5px; font-weight:600; line-height:1; white-space:nowrap; cursor:pointer; }
    .av-nav-out { background:transparent; border:1.5px solid rgba(255,255,255,.55); color:#fff !important; }

    .mfl { max-width:1040px; margin:0 auto; padding:18px 16px 40px; }
    .mfl h1 { font-family:var(--mf-display); font-weight:600; font-size:20px; margin:0 0 8px; color:var(--mf-fg); }
    .mfl .lead { color:var(--mf-mut); margin:0 0 14px; max-width:66ch; }
    /* the search row */
    .mfl-search { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:14px; position:relative; }
    .mfl-search .box { position:relative; flex:1 1 320px; }
    .mfl-search input { width:100%; height:46px; border-radius:23px; border:1.5px solid var(--mf-line); background:var(--mf-card); color:var(--mf-fg); padding:0 18px; font:inherit; font-size:15px; }
    .mfl-search input:focus { outline:none; border-color:var(--mf-p); box-shadow:0 0 0 3px rgba(132,36,132,.18); }
    .mfl-btn { font:inherit; font-weight:600; border-radius:22px; padding:9px 18px; border:1px solid var(--mf-line); background:var(--mf-card); color:var(--mf-fg); cursor:pointer; text-decoration:none !important; display:inline-block; line-height:1.3; }
    .mfl-btn:focus-visible { outline:2px solid var(--mf-p); outline-offset:2px; }
    .mfl-btn-green { background:var(--mf-g); border-color:var(--mf-g); color:#fff !important; }
    .mfl-btn-sm { padding:5px 12px; font-size:13px; }
    .mfl-results { position:absolute; left:0; right:0; top:52px; z-index:20; background:var(--mf-card); border:1px solid var(--mf-line); border-radius:12px; box-shadow:0 14px 34px rgba(20,20,30,.14); max-height:420px; overflow:auto; }
    .mfl-results a { display:flex; gap:12px; align-items:center; padding:9px 16px; text-decoration:none; color:var(--mf-fg); border-bottom:1px solid var(--mf-line); }
    .mfl-results a:hover, .mfl-results a:focus { background:var(--mf-p-soft); outline:none; }
    .mfl-results .k { flex:0 0 150px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--mf-p); }
    .mfl-results .t { font-weight:600; } .mfl-results .s { font-size:12.5px; color:var(--mf-mut); }
    .mfl-results .none { padding:12px 16px; color:var(--mf-mut); font-size:13.5px; }
    .mfl-add { position:relative; }
    .mfl-add-menu { position:absolute; right:0; top:46px; z-index:21; min-width:240px; background:var(--mf-card); border:1px solid var(--mf-line); border-radius:12px; box-shadow:0 14px 34px rgba(20,20,30,.14); overflow:hidden; }
    .mfl-add-menu a { display:block; padding:9px 14px; color:var(--mf-fg); text-decoration:none; border-bottom:1px solid var(--mf-line); font-size:14px; }
    .mfl-add-menu a:last-child { border-bottom:0; } .mfl-add-menu a:hover { background:var(--mf-g-soft); }
    /* the doors */
    .hub { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:12px; margin-bottom:22px; }
    .tile { background:var(--mf-card); border:1px solid var(--mf-line); border-top:4px solid var(--mf-p); border-radius:12px; padding:14px 16px; display:flex; flex-direction:column; gap:6px; min-width:0; }
    .tile h3 { font-family:var(--mf-display); font-weight:600; font-size:16px; margin:0; color:var(--mf-fg); }
    .tile .n { font-size:26px; font-weight:700; color:var(--mf-p); font-variant-numeric:tabular-nums; line-height:1.1; }
    .tile .n small { display:block; font-size:12.5px; font-weight:400; color:var(--mf-mut); line-height:1.2; }
    .tile p { margin:0; color:var(--mf-mut); font-size:12.5px; }
    .tile .mfl-btn { align-self:flex-start; margin-top:4px; }
    .tile.coming { border-top-color:var(--mf-line); opacity:.75; }
    .tile.coming .n { color:var(--mf-mut); font-size:16px; font-weight:600; }
    .tile.coming .mfl-btn { color:var(--mf-mut); pointer-events:none; }
    /* Needs attention today */
    .card { background:var(--mf-card); border:1px solid var(--mf-line); border-radius:12px; }
    .card-h { padding:12px 16px; border-bottom:1px solid var(--mf-line); display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; }
    .card-h h3 { font-family:var(--mf-display); font-weight:600; font-size:16px; color:var(--mf-p); margin:0; }
    .card-h .muted { color:var(--mf-mut); font-size:12.5px; }
    .tbl-wrap { overflow-x:auto; }
    .card table { width:100%; border-collapse:collapse; font-size:13.5px; }
    .card th { text-align:left; font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--mf-mut); padding:8px 10px; border-bottom:1px solid var(--mf-line); white-space:nowrap; }
    .card td { padding:9px 10px; border-bottom:1px solid var(--mf-line); vertical-align:top; }
    .card tr:last-child td { border-bottom:0; }
    .card td a { color:var(--mf-fg); text-decoration:none; border-bottom:1px dotted var(--mf-p); }
    .card td a:hover { color:var(--mf-p); }
    .card td.lv { width:10px; padding-right:0; } .card td.lv i { display:inline-block; width:9px; height:9px; border-radius:50%; margin-top:5px; }
    .card tr.red td.lv i { background:var(--mf-red); } .card tr.amber td.lv i { background:var(--mf-gold); }
    .card .ok { padding:14px 16px; color:var(--mf-g-dark); font-weight:600; }
    @media (max-width:640px) { .mfl { padding:14px 16px 30px; } .mfl-results .k { flex-basis:90px; } .tdk-top { padding:8px 12px; gap:6px; } .tdk-top .hd-user span, .av-nav-out span { display:none; } }
</style>

<header class="tdk-top hd-top">
    <?= view('layouts/nav_bar', ['nb_back' => "window.location.href='" . base_url('home') . "'"]) ?>
    <div class="sp"></div>
    <?= view('layouts/page_search') ?>
    <div class="hd-user">
        <img src="<?= $esc($profile_picture ?? base_url('assets/images/users/no_profile.jpg')) ?>" alt="" onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'bi bi-person-circle hd-avatar-fallback'}))">
        <span><?= $esc($sidebar_employee_name ?? '') ?><small><?= $esc($sidebar_employee_name_profile_name ?? '') ?></small></span>
    </div>
    <button type="button" class="av-nav-btn av-nav-out" onclick="logout()" title="Logout" aria-label="Logout"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></button>
</header>

<main class="mfl">
    <h1>Master Files</h1>
    <p class="lead">One search across every master; each door opens its own pages. The pages, records and IDs are the existing ones.</p>
    <div class="mfl-search" id="mfl_search">
        <div class="box">
            <input type="search" id="mfl_q" placeholder="Search anything in the masters: hotel, agent, driver, place, supplier, account code…" autocomplete="off" aria-label="Search the masters">
            <div class="mfl-results" id="mfl_res" hidden></div>
        </div>
        <?php if ($adds): ?>
        <div class="mfl-add" id="mfl_add">
            <button type="button" class="mfl-btn mfl-btn-green" id="mfl_add_b" aria-haspopup="true" aria-expanded="false">Add record</button>
            <div class="mfl-add-menu" id="mfl_add_m" hidden>
                <?php foreach ($adds as $a): ?><a href="<?= base_url($esc($a['url'])) ?>"><?= $esc($a['label']) ?></a><?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="hub">
        <?php foreach ($sections as $key => $sec): $has = $sec['count'] > 0; $c = $counts[$key] ?? null; ?>
        <div class="tile<?= $has ? '' : ' coming' ?>">
            <h3><?= $esc($sec['title']) ?></h3>
            <?php if ($c !== null && $has): ?>
                <span class="n"><?= number_format((int) $c['n']) ?><small><?= $esc($c['label']) ?></small></span>
            <?php elseif ($has): ?>
                <span class="n"><?= (int) $sec['count'] ?><small>page<?= $sec['count'] === 1 ? '' : 's' ?></small></span>
            <?php else: ?>
                <span class="n"><?= !empty($sec['coming']) ? 'coming in Stage 8' : 'no pages on this sign-in' ?></span>
            <?php endif; ?>
            <p><?= $has ? $esc(implode(' · ', array_map(static fn ($i) => $i['label'], $sec['items']))) : 'Block new work or hold payments for a supplier, hotel or account, with the reason and the record — authorised staff only.' ?></p>
            <?php if ($has): ?>
                <a class="mfl-btn mfl-btn-sm" href="<?= base_url('home/master_files/' . $esc($key)) ?>" aria-label="Open <?= $esc($sec['title']) ?>">Open</a>
            <?php else: ?>
                <span class="mfl-btn mfl-btn-sm">Open</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="card" id="mfl_att">
        <div class="card-h"><h3>Needs attention today</h3><span class="muted" id="mfl_att_n">Real conditions found on development, <?= $esc(date('j M')) ?></span></div>
        <div class="tbl-wrap" id="mfl_att_b"><div class="ok">Checking the masters…</div></div>
    </div>
</main>

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
// tf-stage3: the one search (results open the record on its own page) and the Needs attention list — unchanged behaviour
(function () {
    'use strict';
    var BASE = '<?= base_url() ?>', q = document.getElementById('mfl_q'), res = document.getElementById('mfl_res'), t = null, last = '';
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
    function run() {
        var v = q.value.trim(); if (v === last) return; last = v;
        if (v.length < 2) { res.hidden = true; res.innerHTML = ''; return; }
        fetch(BASE + 'home/master_files/search?q=' + encodeURIComponent(v), { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); }).then(function (j) {
            var rows = (j.data || []);
            res.innerHTML = rows.length ? rows.map(function (r) { return '<a href="' + esc(BASE + r.url) + '"><span class="k">' + esc(r.kind_label) + '</span><span><span class="t">' + esc(r.title) + '</span><br><span class="s">' + esc(r.sub) + '</span></span></a>'; }).join('') : '<div class="none">Nothing in the masters matches “' + esc(v) + '”.</div>';
            res.hidden = false;
        }).catch(function () { res.innerHTML = '<div class="none">The search could not run.</div>'; res.hidden = false; });
    }
    q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(run, 220); });
    q.addEventListener('keydown', function (e) { if (e.key === 'Escape') { res.hidden = true; } if (e.key === 'Enter') { var a = res.querySelector('a'); if (a) a.click(); } });
    document.addEventListener('click', function (e) {
        if (!document.getElementById('mfl_search').contains(e.target)) res.hidden = true;
        var add = document.getElementById('mfl_add'); if (add && !add.contains(e.target)) { document.getElementById('mfl_add_m').hidden = true; document.getElementById('mfl_add_b').setAttribute('aria-expanded', 'false'); }
    });
    var ab = document.getElementById('mfl_add_b');
    if (ab) ab.addEventListener('click', function () { var m = document.getElementById('mfl_add_m'); m.hidden = !m.hidden; ab.setAttribute('aria-expanded', m.hidden ? 'false' : 'true'); });
    if (window.TfView) { var v0 = TfView.param('q'); if (v0) { q.value = v0; run(); } }
    fetch(BASE + 'home/master_files/attention', { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); }).then(function (j) {
        var rows = j.data || [], b = document.getElementById('mfl_att_b'), n = document.getElementById('mfl_att_n');
        n.textContent = (rows.length ? rows.length + ' item' + (rows.length === 1 ? '' : 's') + ' · ' : '') + n.textContent;
        b.innerHTML = rows.length
            ? '<table><tr><th></th><th>What</th><th>Door</th><th>Why it matters</th></tr>' + rows.map(function (r) { return '<tr class="' + esc(r.level) + '"><td class="lv"><i></i></td><td><a href="' + esc(BASE + r.url) + '" title="Open">' + esc(r.what) + '</a></td><td>' + esc(r.door) + '</td><td>' + esc(r.why) + '</td></tr>'; }).join('') + '</table>'
            : '<div class="ok"><i class="bi bi-check2-circle"></i> Nothing needs attention today.</div>';
    }).catch(function () { document.getElementById('mfl_att_b').innerHTML = '<div class="ok" style="color:#b23a32">The list could not be read.</div>'; });
})();
(function () {
    'use strict';
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
