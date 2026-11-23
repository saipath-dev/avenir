<?php
/**
 * tf-navbar-r1 (22 Oct 2026) — the shared header bar of the Avenir Holidays Handling System (design concept):
 * a full-width burgundy bar with the "Avenir Holidays / Handling System" name, white Back, Home and Dashboard links,
 * and the shared "Search pages…" box (layouts/page_search) on the right.
 *
 *   <?= view('layouts/nav_bar', ['nb_back' => "window.history.back()", 'nb_home' => true, 'nb_dash' => true]) ?>
 *
 * Prints the brand and the three links. The bar colour for the three header layouts (layouts/header.php #page-topbar,
 * layouts/topbar.php .app-topbar, pages/home_desks.php .hd-top) is set here once. Links and behaviour are unchanged:
 * Back = the caller's action, Home = home, Dashboard = dashboard2/tiles (Pilot).
 */
$nbBack = $nb_back ?? 'window.history.back()';
$nbHome = $nb_home ?? true;
$nbDash = $nb_dash ?? true;
$nbBrand = $nb_brand ?? true;
if (!defined('AV_NAV_BAR_CSS')):
    define('AV_NAV_BAR_CSS', true); ?>
<style id="av-nav-bar-css">
/* the bar */
html body #page-topbar, html body .app-topbar, html body .hd-top {
  --vz-header-bg:#842484; --vz-header-border:#842484; --vz-header-item-color:#fff; --vz-header-item-sub-color:rgba(255,255,255,.80); --vz-header-item-bg:rgba(255,255,255,.12);
  background:#842484 !important; border-bottom:1px solid #842484 !important; box-shadow:0 2px 14px rgba(132,36,132,.25) !important;
  -webkit-backdrop-filter:none !important; backdrop-filter:none !important; }
html body #page-topbar .btn-topbar, html body #page-topbar .btn-topbar i, html body #page-topbar .user-name-text { color:#fff !important; }
html body #page-topbar .user-name-sub-text { color:rgba(255,255,255,.80) !important; }
html body #page-topbar .btn-ghost-secondary:hover, html body #page-topbar .topbar-user .btn:hover { background:rgba(255,255,255,.12) !important; }
html body #page-topbar .topbar-user { background:transparent !important; }
html body #page-topbar #page-header-search-dropdown { display:none !important; }   /* the old placeholder search; the page search replaces it */
html body .hd-top .hd-user, html body .hd-top .hd-user small { color:#fff; }
html body .hd-top .hd-user small { color:rgba(255,255,255,.80); }
html body .hd-top .hd-avatar-fallback, html body .hd-top .hd-user img { color:#fff; border-color:#e0a526; }
html body .app-topbar .av-nav-out, html body .hd-top .av-nav-out { background:transparent; border:1.5px solid rgba(255,255,255,.55); box-shadow:none; color:#fff !important; }
html body .app-topbar .av-nav-out:hover, html body .hd-top .av-nav-out:hover { background:rgba(255,255,255,.12); }
/* the name */
.avbar-brand { display:inline-flex; flex-direction:column; justify-content:center; line-height:1.05; text-decoration:none !important; padding-right:18px; margin-right:6px;
  border-right:1px solid rgba(255,255,255,.28); font-family:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; white-space:nowrap; }
.avbar-brand b { color:#fff; font-size:20px; font-weight:700; letter-spacing:-.01em; }
.avbar-brand small { color:#e9c46a; font-size:10.5px; font-weight:600; letter-spacing:.28em; margin-top:4px; }
/* the links */
.avbar-links { display:inline-flex; align-items:center; gap:4px; }
.avbar-link { display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 12px; border:0; border-radius:10px; background:transparent;
  color:#fff !important; font:500 15px/1 "Inter Tight","Inter","Segoe UI",system-ui,sans-serif; letter-spacing:.005em; text-decoration:none !important; cursor:pointer; white-space:nowrap; }
.avbar-link i { font-size:18px; line-height:1; }
.avbar-link .ic-back { color:#fff; } .avbar-link .ic-home, .avbar-link .ic-dash { color:#e9c46a; }
.avbar-link:hover { background:rgba(255,255,255,.12); }
.avbar-link:focus-visible { outline:2px solid #e9c46a; outline-offset:2px; }
.avbar-link[aria-current="page"] { background:rgba(255,255,255,.14); }
/* the page search on the burgundy bar */
html body #page-topbar .avps-box, html body .app-topbar .avps-box, html body .hd-top .avps-box { border-color:transparent; box-shadow:0 2px 10px rgba(0,0,0,.18); width:340px; height:40px; }
html body #page-topbar .avps-box:focus-within, html body .app-topbar .avps-box:focus-within, html body .hd-top .avps-box:focus-within { width:400px; border-color:#e9c46a; box-shadow:0 0 0 .2rem rgba(233,196,106,.35); }
html body #page-topbar .avps-toggle, html body .app-topbar .avps-toggle, html body .hd-top .avps-toggle { background:transparent; border-color:rgba(255,255,255,.45); color:#fff; box-shadow:none; }
@media (max-width:1399px) { .avbar-brand b { font-size:18px; } html body #page-topbar .avps-box, html body .app-topbar .avps-box, html body .hd-top .avps-box { width:260px; } }
@media (max-width:1199px) { .avbar-brand { display:none; } }
@media (max-width:991px) { .avbar-link span { display:none; } .avbar-link { padding:0 10px; } }
@media print { .avbar-links, .avbar-brand { display:none !important; } }
</style>
<?php endif; ?>
<?php if ($nbBrand): ?><a class="avbar-brand" href="<?= base_url('home') ?>" title="Home"><b>Avenir Holidays</b><small>HANDLING SYSTEM</small></a><?php endif; ?>
<nav class="avbar-links" aria-label="Main">
    <?php if ($nbBack !== false): ?><button type="button" class="avbar-link" onclick="<?= esc($nbBack, 'attr') ?>" title="Back" aria-label="Back"><i class="bi bi-arrow-left ic-back"></i><span>Back</span></button><?php endif; ?>
    <?php if ($nbHome): ?><a class="avbar-link" href="<?= base_url('home') ?>" title="Home" aria-label="Home"<?= uri_string() === 'home' ? ' aria-current="page"' : '' ?>><i class="bi bi-house-door-fill ic-home"></i><span>Home</span></a><?php endif; ?>
    <?php if ($nbDash): ?><a class="avbar-link" href="<?= base_url('dashboard2/tiles') ?>" title="Dashboard" aria-label="Dashboard"<?= uri_string() === 'dashboard2/tiles' ? ' aria-current="page"' : '' ?>><i class="bi bi-grid-1x2-fill ic-dash"></i><span>Dashboard</span></a><?php endif; ?>
</nav>
