<?php
/**
 * Transport & Guide Services — the strip shared by the four connected masters (Shantha, 5 Oct 2026):
 *
 *   Transport Rates                  the general LKR per kilometre by vehicle class, with effective dates
 *   Mileage & Allowances             the mileage and allowance figures of the schedule (garage, positioning, extra km)
 *   Drivers & Guides                 the Driver & Guide Master — JSD / JSCG / JSNTG, vehicles, documents, the rates and
 *                                    the standard / agreed figures ACTUALLY PAID, bank details behind their privilege
 *   Crew Roles & Quotation Costing   the quotation's crew roles and their COSTING figures — what is quoted to the
 *                                    customer, never what is paid
 *
 * One strip, the same on every one of the four screens, under the Tour Desk bar. Each section keeps its own URL and
 * its own privilege — a tab is shown only to a sign-in whose group holds that URL ($allowed_menu_list, shared across
 * views as the Tour Desk bar reads it); the route filter still has the last word. Transport Operations is not here:
 * it is the desk's operational work, kept apart from the masters.
 *
 *   <?= view('layouts/tgs_bar') ?>
 */
$tgsItems = [
    ['url' => 'master_files/transport_rates',    'label' => 'Transport Rates',                'sub' => 'general LKR / km by vehicle class', 'icon' => 'bi-cash-coin'],
    ['url' => 'master_files/mileage_allowances', 'label' => 'Mileage & Allowances',           'sub' => 'garage, positioning, extra km',     'icon' => 'bi-signpost-2'],
    ['url' => 'master_files/crew_people',        'label' => 'Drivers & Guides',               'sub' => 'the people · what is PAID',         'icon' => 'bi-people'],
    ['url' => 'master_files/crew_bata',          'label' => 'Crew Roles & Quotation Costing', 'sub' => 'what is QUOTED — not what is paid', 'icon' => 'bi-calculator', 'costing' => true],
];
$tgsAllowed = null;
if (isset($allowed_menu_list) && is_array($allowed_menu_list)) {
    $tgsAllowed = [];
    foreach ($allowed_menu_list as $m) {
        $u = trim((string) (is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '')), "/ ");
        if ($u !== '') {
            $tgsAllowed[$u] = true;
        }
    }
}
$tgsHere = trim((string) (function_exists('uri_string') ? uri_string() : ''), '/');
$tgsShown = array_values(array_filter($tgsItems, static fn ($it) => $tgsAllowed === null || isset($tgsAllowed[$it['url']])));
?>
<style>
    .tgsbar { --tg-purple:#842484; --tg-magenta:#e90c8b; --tg-ink:#2c3e50; --tg-muted:#6c757d; --tg-line:rgba(132,36,132,.18); --tg-tint:rgba(132,36,132,.07);
              --tg-amber:#1a1a1a; --tg-amber-soft:#ffffff; --tg-teal:#0f8b7e; --tg-teal-soft:#e3f4f1;
              display:flex; align-items:stretch; gap:8px; flex-wrap:wrap; margin:-8px 0 16px; padding:8px 10px; background:#fff; border:1px solid var(--tg-line);
              border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.05); font-family:inherit; color:var(--tg-ink); }
    .tgsbar .tg-brand { display:flex; align-items:center; gap:10px; padding:4px 14px 4px 4px; border-right:1px solid var(--tg-line); min-width:0; }
    .tgsbar .tg-brand .mark { width:34px; height:34px; border-radius:10px; display:grid; place-items:center; color:#fff; font-size:16px; flex:none;
              background:linear-gradient(135deg, var(--tg-magenta) 0%, var(--tg-purple) 100%); box-shadow:0 3px 8px rgba(143,42,141,.3); }
    .tgsbar .tg-brand b { display:block; font-size:13.5px; font-weight:700; line-height:1.15; white-space:nowrap; }
    .tgsbar .tg-brand small { display:block; font-size:11px; color:var(--tg-muted); line-height:1.2; margin-top:2px; white-space:nowrap; }
    .tgsbar .tg-nav { display:flex; align-items:stretch; gap:6px; flex-wrap:wrap; flex:1 1 auto; min-width:0; }
    .tgsbar .tg-item { display:flex; align-items:center; gap:9px; padding:5px 13px; border-radius:10px; border:1px solid transparent; text-decoration:none; color:var(--tg-ink); min-width:0;
              transition:background-color .2s ease, border-color .2s ease; }
    .tgsbar .tg-item i { font-size:15px; color:var(--tg-purple); flex:none; }
    .tgsbar .tg-item b { display:block; font-size:12.5px; font-weight:600; line-height:1.2; white-space:nowrap; }
    .tgsbar .tg-item small { display:block; font-size:10.5px; color:var(--tg-muted); line-height:1.2; margin-top:1px; white-space:nowrap; }
    .tgsbar .tg-item:hover { background:var(--tg-tint); border-color:var(--tg-line); color:var(--tg-ink); }
    .tgsbar .tg-item.on { background:var(--tg-tint); border-color:var(--tg-purple); }
    .tgsbar .tg-item.costing i { color:var(--tg-amber); }
    .tgsbar .tg-item.costing small { color:var(--tg-amber); font-weight:600; }
    .tgsbar .tg-item.costing.on { border-color:var(--tg-amber); background:var(--tg-amber-soft); }
    .tgsbar .tg-item.paid small { color:var(--tg-teal); font-weight:600; }
    .tgsbar .tg-note { display:flex; align-items:center; font-size:11px; color:var(--tg-muted); padding:0 6px; margin-left:auto; max-width:360px; line-height:1.3; }
    @media (max-width: 991px) { .tgsbar .tg-note { display:none; } }
</style>
<nav class="tgsbar" aria-label="Transport &amp; Guide Services">
    <div class="tg-brand">
        <span class="mark"><i class="bi bi-truck-front"></i></span>
        <div><b>Transport &amp; Guide Services</b><small>four connected masters · one place</small></div>
    </div>
    <div class="tg-nav">
        <?php foreach ($tgsShown as $it): $on = $tgsHere === $it['url'] || str_starts_with($tgsHere, $it['url'] . '/'); ?>
        <a class="tg-item<?= $on ? ' on' : '' ?><?= !empty($it['costing']) ? ' costing' : '' ?><?= $it['url'] === 'master_files/crew_people' ? ' paid' : '' ?>" href="<?= base_url($it['url']) ?>"<?= $on ? ' aria-current="page"' : '' ?> data-tgs="<?= esc($it['url']) ?>">
            <i class="bi <?= esc($it['icon']) ?>"></i><div><b><?= esc($it['label']) ?></b><small><?= esc($it['sub']) ?></small></div>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="tg-note">Quotation costing and actual crew payments are kept apart: the costing figures live under Crew Roles &amp; Quotation Costing, the figures paid under Drivers &amp; Guides.</div>
</nav>
