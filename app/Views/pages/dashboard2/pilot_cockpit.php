<?php
/**
 * tf-cockpit-r1 (8 Nov 2026, Shantha) — the Super Admin's Pilot Panel, in the approved cockpit design.
 * Shown on dashboard2/tiles for the System Administrator only (Home: "Pilot Report"); staff keep their own page.
 * Everything comes from the application: the counters, shortcuts and reports from App\Libraries\Pilot::forUser(),
 * the department functions from the user's own menu list (each opens the existing department window), the Pilot Report
 * entries from menu category 27, and the USD rate from UsdRate::status(). Nothing here grants anything.
 */
$e = static fn ($s): string => esc((string) ($s ?? ''));
$p = $pilot ?? ['tasks' => [], 'shortcuts' => [], 'reports' => []];
$tasks = array_values((array) ($p['tasks'] ?? []));
$shorts = array_values((array) ($p['shortcuts'] ?? []));
$reports = array_values((array) ($p['reports'] ?? []));
$waiting = array_sum(array_map(static fn ($t) => (int) $t['count'], $tasks));
$PILOT_CAT = 27;
// the function menu: every category the user holds, in the approved order, except Pilot Report (it has its own control)
$order = ['Ground Handling', 'Accounts', 'Accounts Reports', 'Master Files', 'Transport & Guide Services', 'Tour', 'Inventory Management', 'Chat', 'Settings'];
$cats = [];
foreach ((array) ($allowed_categories ?? []) as $c) {
    if ((int) $c['cat_id'] === $PILOT_CAT) {
        continue;
    }
    $n = count(array_filter((array) ($allowed_menu_list ?? []), static fn ($m) => (int) $m['cat_id'] === (int) $c['cat_id'] && (int) $m['show_in_menu_bar'] !== 1));
    if (!$n) {
        continue;
    }
    $pos = array_search(trim($c['category']), $order, true);
    $cats[] = ['id' => (int) $c['cat_id'], 'name' => trim($c['category']), 'n' => $n, 'o' => $pos === false ? 99 : $pos];
}
usort($cats, static fn ($a, $b) => $a['o'] <=> $b['o'] ?: strcmp($a['name'], $b['name']));
$icon = [
    'Ground Handling' => 'gh', 'Accounts' => 'coins', 'Accounts Reports' => 'rep', 'Master Files' => 'folder', 'Transport & Guide Services' => 'van',
    'Tour' => 'palm', 'Inventory Management' => 'box', 'Chat' => 'chat', 'Settings' => 'gear',
];
// tf-cockpit-r2: each department's functions as large cards in the centre. Same entries, same destinations as the old
// department windows (menu rows not hidden from the menu bar); Ground Handling's six action tiles keep their behaviour
// (the shared search window or their redirect), and Ground Handling gets Confirmed Tours as its first, prominent card.
$GH = 25;
// tf-cockpit-r3 (8 Nov 2026, Shantha): every Pilot Panel link opens the newly designed screen. Ground Handling's tiles
// no longer open the old search window; each goes to its new screen (the subtitle says which).
$ghAction = [
    'Tour Search'      => ['tour/board', 'opens the Tour Board'],
    'New Inquiry'      => ['tour/intake/guided', 'opens the Guided Inquiry'],
    'New Booking'      => ['tour/intake/guided', 'a booking starts as an inquiry: opens the Guided Inquiry'],
    'Hotel Management' => ['tour/hotel_bookings', 'opens Hotel reservations & bookings'],
    'Transport'        => ['tour/transport_ops', 'opens Transport Operations'],
    'Transport Portal' => ['tour/transport_ops', 'opens Transport Operations'],
];
$ghOrder = ['Tour Search', 'New Inquiry', 'New Booking', 'Hotel Management', 'Transport', 'Transport Portal', 'Ongoing Tours', 'Ongoing Tour Transport'];
$cardIcon = static function (string $name): string {
    $n = strtolower($name);
    foreach ([
        'confirmed' => 'bi-calendar2-check', 'search' => 'bi-search', 'inquiry' => 'bi-chat-left-text', 'booking' => 'bi-journal-plus', 'quotation' => 'bi-file-earmark-text',
        'hotel' => 'bi-building', 'portal' => 'bi-door-open', 'ongoing' => 'bi-signpost-2', 'transport' => 'bi-bus-front', 'driver' => 'bi-person-badge', 'guide' => 'bi-people',
        'crew' => 'bi-people', 'vehicle' => 'bi-truck', 'mileage' => 'bi-speedometer', 'rate' => 'bi-tags', 'usd' => 'bi-currency-dollar', 'bank' => 'bi-bank',
        'voucher' => 'bi-receipt', 'journal' => 'bi-journal-text', 'invoice' => 'bi-receipt-cutoff', 'payment' => 'bi-cash-coin', 'debit' => 'bi-dash-circle', 'credit' => 'bi-plus-circle',
        'balance' => 'bi-bar-chart', 'trial' => 'bi-bar-chart', 'profit' => 'bi-graph-up', 'report' => 'bi-file-earmark-bar-graph', 'ledger' => 'bi-book', 'cash' => 'bi-wallet2',
        'supplier' => 'bi-shop', 'customer' => 'bi-person-vcard', 'conginee' => 'bi-person-vcard', 'room' => 'bi-door-closed', 'place' => 'bi-geo-alt', 'entrance' => 'bi-ticket-perforated',
        'ticket' => 'bi-ticket-perforated', 'meal' => 'bi-cup-hot', 'photo' => 'bi-images', 'visit' => 'bi-geo', 'excursion' => 'bi-compass', 'email' => 'bi-envelope',
        'stock' => 'bi-box-seam', 'inventory' => 'bi-box-seam', 'item' => 'bi-tag', 'grn' => 'bi-box-arrow-in-down', 'usage' => 'bi-clipboard-check', 'chat' => 'bi-chat-dots',
        'user' => 'bi-person-gear', 'group' => 'bi-people', 'menu' => 'bi-list', 'backup' => 'bi-cloud-arrow-down', 'staff' => 'bi-person-lines-fill', 'tour' => 'bi-map',
        'calendar' => 'bi-calendar3', 'calender' => 'bi-calendar3', 'board' => 'bi-kanban', 'amendment' => 'bi-pencil-square', 'cancel' => 'bi-x-circle', 'advance' => 'bi-wallet',
        'settlement' => 'bi-check2-square', 'expense' => 'bi-receipt', 'shop' => 'bi-shop-window', 'withdrawal' => 'bi-cash-stack', 'utility' => 'bi-lightning', 'visa' => 'bi-passport',
        'reconcil' => 'bi-journal-check', 'account' => 'bi-journal-bookmark', 'carrier' => 'bi-airplane', 'route' => 'bi-signpost', 'kilomet' => 'bi-speedometer2', 'charge' => 'bi-plus-slash-minus',
    ] as $k => $i) {
        if (str_contains($n, $k)) {
            return $i;
        }
    }
    return 'bi-grid';
};
// tf-cockpit-r3: old screens that have a new replacement open the replacement; old screens whose replacement is not built
// yet show as "New screen in progress" with no link (no old screen as a fallback); superseded duplicates are left out.
// Screens that were never redesigned (their current screen is the only version) are kept as they are. The old screens
// themselves are untouched on the server, only unlinked from this panel.
$NEW = [
    'inquiries' => 'tour/intake/guided', 'tour/intake' => 'tour/intake/guided', 'tour/quotation' => 'tour/intake/guided',
    'tour/tour' => 'tour/intake/guided', 'tour/tour_dashboard' => 'tour/intake/guided', 'dashboard2/tour_search/new_quotation' => 'tour/intake/guided',
    'tour/tour_accounts/hotel_rate_confirm' => 'tour/hotel_bookings',
    'dashboard2/transport/transport_portal' => 'tour/transport_ops', 'dashboard2/transport/driver_allow/pending_tours' => 'tour/transport_ops',
    'tour/tour_details_search' => 'tour/board', 'tour/tour_details' => 'tour/board',
    'accounts/bank_reconcil' => 'accounts/daily_bank_rec/prepare',
];
$NEW_NAME = [
    'tour/intake/guided' => 'Tour Inquiry (Guided)', 'tour/hotel_bookings' => 'Hotel Bookings', 'tour/transport_ops' => 'Transport Operations',
    'tour/board' => 'Tour Board', 'accounts/daily_bank_rec/prepare' => 'Daily Bank Reconciliation — prepare (import, match, propose)',
];
$NOT_READY = [
    'dashboard2/transport/transport_ongoing', 'tour/calendar', 'dashboard2/transport/driver_report', 'report/driver_ledger',
    'master_files/vehicle', 'tour/timeline', 'dashboard', 'accounts/bank_reconcil/details', 'accounts/bank_reconcil/summary',
];
$DROP = ['tour/tour_accounts/payment_requested_dashboard_new'];
// returns [url, state]: 'ok' unchanged, 'new' remapped, 'nr' not ready (no link), 'drop' left out
$route = static function (string $url) use ($NEW, $NOT_READY, $DROP): array {
    $path = trim((string) strtok($url, '?#'), '/');
    if (in_array($path, $DROP, true)) {
        return [null, 'drop'];
    }
    if (in_array($path, $NOT_READY, true)) {
        return [null, 'nr'];
    }
    if (isset($NEW[$path])) {
        return [$NEW[$path], 'new'];
    }
    return [$url, 'ok'];
};
$fixList = static function (array $list) use ($route): array {
    $out = [];
    $seen = [];
    foreach ($list as $x) {
        [$u, $st] = $route((string) ($x['url'] ?? ''));
        if ($st === 'drop') {
            continue;
        }
        $x['url'] = $u;
        $x['nr'] = $st === 'nr';
        $out[] = $x;
    }
    return $out;
};
$tasks = $fixList($tasks);
$shorts = array_values(array_filter($fixList($shorts), static fn ($x) => !$x['nr']));
$reports = $fixList($reports);
$depts = [];
foreach ($cats as $c) {
    $items = array_values(array_filter((array) ($allowed_menu_list ?? []), static fn ($m) => (int) $m['cat_id'] === $c['id'] && (int) $m['show_in_menu_bar'] !== 1));
    $cards = [];
    if ($c['id'] === $GH) {
        $cards[] = ['name' => 'Confirmed Tours', 'url' => 'tour/intake/confirmed', 'sub' => 'tours agreed with the agent', 'big' => true];
        usort($items, static function ($a, $b) use ($ghOrder) {
            $x = array_search($a['menu'], $ghOrder, true); $y = array_search($b['menu'], $ghOrder, true);
            return ($x === false ? 99 : $x) <=> ($y === false ? 99 : $y) ?: strcmp($a['menu'], $b['menu']);
        });
    }
    $seen = [];
    foreach ($items as $m) {
        $act = $c['id'] === $GH ? ($ghAction[$m['menu']] ?? null) : null;
        if ($act) {   // Ground Handling keeps the names Shantha asked for, each on its new screen
            $cards[] = ['name' => $m['menu'], 'url' => $act[0], 'sub' => $act[1]];
            continue;
        }
        [$u, $st] = $route((string) $m['url']);
        if ($st === 'drop') {
            continue;
        }
        if ($st === 'nr') {
            $cards[] = ['name' => $m['menu'], 'url' => null, 'nr' => true, 'sub' => 'New screen in progress'];
            continue;
        }
        $key = trim((string) strtok($u, '?#'), '/');
        if (isset($seen[$key])) {
            continue;   // a superseded screen whose replacement is already on this page
        }
        $seen[$key] = 1;
        $cards[] = ['name' => $st === 'new' ? ($NEW_NAME[$key] ?? $m['menu']) : $m['menu'], 'url' => $u];
    }
    $depts[$c['id']] = ['name' => $c['name'], 'cards' => $cards];
}
foreach ($cats as &$c) {   // the left menu counts what the centre shows
    $c['n'] = count($depts[$c['id']]['cards'] ?? []);
}
unset($c);
$pr = $fixList(array_values((array) ($pilot_report_items ?? [])));
usort($pr, static function ($a, $b) {   // Staff access and work descriptions first
    $w = static fn ($x) => str_contains((string) $x['url'], 'master_files/staff') ? 0 : (str_contains((string) $x['url'], 'staff_work_descriptions') ? 1 : 2);
    return $w($a) <=> $w($b);
});
$prDesc = ['master_files/staff' => 'Staff Access Control Panel: positions, accounts, permissions, history', 'report/staff_work_descriptions' => 'Staff numbers, job titles and duties · export'];
$u = $usd ?? null;
?>
<style>
    .app-topbar, #avh-root .avh-fab { display:none !important; }   /* the cockpit has its own header with User Manual and Logout */
    body { background:#ece6ee !important; }
    .ck { --p:#842484; --pd:#5e1a63; --pdd:#3a0f40; --g:#78b848; --gd:#5a9a2e; --gl:#9ad86a; --cream:#fdf9f2; --cream2:#f3eadc; --ink:#2b2230; --mut:#6f6574; --ln:#e6dacd;
          font-family:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; color:var(--ink); }
    .ck a { text-decoration:none; color:inherit; }
    .ck svg { display:block; }
    .ck-hdr { background:#fff; display:flex; align-items:center; gap:16px; padding:9px 22px; box-shadow:0 1px 0 #eadfe9; position:sticky; top:0; z-index:1030; }
    .ck-hdr .logo img { height:38px; display:block; }
    .ck-hdr .vr { width:1px; height:30px; background:#e2d8e3; }
    .ck-hdr .ttl { display:flex; align-items:center; gap:9px; font-size:1.35rem; font-weight:800; color:var(--p); white-space:nowrap; }
    .ck-hdr .badge2 { background:var(--g); color:#fff; font-size:.78rem; font-weight:700; border-radius:999px; padding:5px 13px; white-space:nowrap; }
    .ck-hdr .sp { flex:1; }
    .ck-hdr .hb { display:inline-flex; align-items:center; gap:7px; border-radius:10px; padding:8px 14px; font-weight:700; font-size:.86rem; border:1.5px solid var(--p); color:var(--p); background:#fff; white-space:nowrap; cursor:pointer; }
    .ck-hdr .hb.solid { background:var(--pd); border-color:var(--pd); color:#fff; }
    .ck-frame { max-width:1340px; margin:12px auto 0; border-radius:30px; padding:3px; background:linear-gradient(180deg,#c9a3cf,#6d2a74 30%,#2e0b33); box-shadow:0 18px 40px rgba(46,11,51,.3); }
    .ck-in { border-radius:27px; background:radial-gradient(ellipse 70% 120px at 50% 0,rgba(255,255,255,.16),transparent 70%),linear-gradient(180deg,#7b2b82,#4f1456 60%,#3c0f43); padding:12px 18px 0; position:relative; }
    .ck-rv { position:absolute; width:9px; height:9px; border-radius:50%; background:radial-gradient(circle at 35% 35%,#f3e6f5,#9c7aa1 60%,#4a2550); }
    .ck-top { position:relative; text-align:center; margin:0 0 10px; }
    .ck-top .wel { color:#fff; font-size:1.5rem; font-weight:800; }
    .ck-top .wel em { font-style:normal; color:var(--gl); }
    .ck-top .cmp { position:absolute; left:20px; top:-2px; color:#e9d7ec; }
    .ck-top .dt { position:absolute; right:26px; top:6px; color:#e9d7ec; font-size:.8rem; font-weight:600; }
    .ck-grid { display:grid; grid-template-columns:236px minmax(0,1fr); gap:16px; }
    .ck-side { background:rgba(0,0,0,.2); border:1px solid rgba(255,255,255,.08); border-radius:18px; padding:10px; display:flex; flex-direction:column; gap:7px; align-self:start; }
    .ck-side .cap { color:#e3cfe6; font-size:.66rem; letter-spacing:.14em; text-transform:uppercase; font-weight:700; padding:2px 6px 2px; }
    .ck-fb { display:flex; align-items:center; gap:10px; background:linear-gradient(180deg,#fffdf9,var(--cream2)); border:0; border-left:6px solid var(--g); border-radius:11px; padding:8px 10px; cursor:pointer; text-align:left; color:var(--ink); font-weight:700; font-size:.85rem; box-shadow:0 2px 4px rgba(0,0,0,.22); width:100%; }
    .ck-fb.gh { border-left-color:#f4c56a; }
    .ck-fb .ic { width:30px; height:30px; flex:none; color:var(--pd); }
    .ck-fb .ch { margin-left:auto; color:#9a8f9e; }
    .ck-fb:hover, .ck-fb:focus-visible { background:#fff; outline:2px solid var(--gl); }
    .ck-main { display:flex; flex-direction:column; gap:12px; min-width:0; }
    .ck-ctrls { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
    .ck-ctl { display:flex; align-items:center; gap:12px; background:linear-gradient(180deg,#fffdf9,var(--cream2)); border:2px solid transparent; border-radius:16px; padding:10px 14px; cursor:pointer; text-align:left; color:var(--ink); box-shadow:0 3px 7px rgba(0,0,0,.25); }
    .ck-ctl .ic { width:44px; height:44px; flex:none; color:var(--pd); }
    .ck-ctl b { display:block; font-size:1.18rem; font-weight:800; }
    .ck-ctl small { font-size:.78rem; color:var(--mut); }
    .ck-ctl .ch { margin-left:auto; font-size:1.25rem; color:#9a8f9e; }
    .ck-ctl[aria-pressed=true] { background:linear-gradient(180deg,#8fcb5e,var(--gd)); color:#fff; border-color:#e2f6b8; box-shadow:0 0 0 3px rgba(154,216,106,.35),0 3px 7px rgba(0,0,0,.3); }
    .ck-ctl[aria-pressed=true] .ic { color:#fff; } .ck-ctl[aria-pressed=true] small, .ck-ctl[aria-pressed=true] .ch { color:#effbe4; }
    .ck-disp { background:linear-gradient(180deg,#fffdf9,var(--cream)); border-radius:20px; padding:14px 18px 16px; box-shadow:inset 0 1px 0 #fff,0 3px 8px rgba(0,0,0,.25); }
    .ck-dh { display:flex; align-items:center; gap:12px; margin:0 0 10px; }
    .ck-dh .ic { width:32px; height:32px; color:var(--pd); padding-right:12px; border-right:2px solid var(--ln); box-sizing:content-box; }
    .ck-dh h2 { margin:0; font-size:1.45rem; font-weight:800; color:var(--ink); }
    .ck-dh .pill { margin-left:auto; border-radius:999px; padding:4px 11px; font-size:.75rem; font-weight:700; background:#eaf5df; color:#4e8a26; }
    .ck-dh .pill.on { background:#fdf0d5; color:#9a6a12; }
    .ck-cg { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:9px; }
    .ck-c { display:flex; align-items:center; gap:14px; background:#fff; border:1.5px solid var(--ln); border-radius:12px; padding:7px 14px; min-height:48px; font-weight:600; font-size:.82rem; line-height:1.25; transition:border-color .12s, box-shadow .12s; }
    .ck-c:hover { border-color:var(--g); box-shadow:0 3px 8px rgba(90,154,46,.15); }
    .ck-c .v { font-size:1.4rem; font-weight:800; color:var(--gd); min-width:30px; text-align:center; padding-right:14px; border-right:2px solid var(--ln); font-variant-numeric:tabular-nums; }
    .ck-c.has { border-color:#d9d3dd; background:#ffffff; } .ck-c.has .v { color:#1a1a1a; }
    .ck-sc { display:flex; flex-wrap:wrap; gap:7px; margin-top:12px; padding-top:10px; border-top:1.5px dashed var(--ln); }
    .ck-sc a { display:inline-flex; align-items:center; gap:6px; background:#fff; border:1.5px solid #d9cbc0; border-radius:9px; padding:5px 9px; font-size:.74rem; font-weight:700; color:var(--pd); white-space:nowrap; }
    .ck-sc a:hover { border-color:var(--p); background:#fbf2fb; }
    .ck-sc a i { font-size:.95rem; }
    .ck-rq { display:flex; align-items:center; gap:8px; border:1.5px solid var(--ln); border-radius:999px; padding:8px 16px; background:#fff; margin-bottom:10px; }
    .ck-rq input { border:0; outline:0; flex:1; font:inherit; font-size:.9rem; background:transparent; }
    .ck-rl { columns:3; column-gap:18px; max-height:360px; overflow:auto; }
    .ck-rl a { display:block; padding:6px 10px; border-radius:8px; font-size:.83rem; font-weight:500; break-inside:avoid; }
    .ck-rl a:hover { background:#f3ebf3; color:var(--p); }
    .ck-pr { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
    .ck-pr a { display:flex; align-items:center; gap:12px; background:#fff; border:1.5px solid var(--ln); border-radius:12px; padding:11px 14px; font-weight:700; font-size:.9rem; }
    .ck-pr a:hover { border-color:var(--p); }
    .ck-pr a .i { width:36px; height:36px; border-radius:10px; background:var(--pdd); color:#fff; display:grid; place-items:center; flex:none; font-size:1rem; }
    .ck-pr a small { display:block; font-weight:500; color:var(--mut); font-size:.75rem; }
    .ck-lock { margin-left:auto; background:var(--pdd); color:#fff; border-radius:999px; padding:4px 11px; font-size:.72rem; font-weight:700; }
    .ck-con { display:flex; justify-content:center; align-items:center; gap:14px; padding:10px 0 14px; position:relative; }
    .ck-con .lt { display:flex; gap:10px; } .ck-con .lt i { width:6px; height:6px; border-radius:50%; background:var(--gl); box-shadow:0 0 6px var(--gl); opacity:.85; }
    .ck-usd { display:flex; align-items:center; gap:12px; background:linear-gradient(180deg,#5d2264,#3c0f43); border:1.5px solid rgba(255,255,255,.35); color:#fff; border-radius:999px; padding:6px 7px 6px 16px; font-size:.84rem; font-weight:600; flex-wrap:wrap; justify-content:center; }
    .ck-usd .r { font-weight:800; font-size:.95rem; font-variant-numeric:tabular-nums; }
    .ck-usd .dot { width:9px; height:9px; border-radius:50%; display:inline-block; margin-right:6px; background:#f0b429; box-shadow:0 0 6px #f0b429; }
    .ck-usd .dot.ok { background:var(--gl); box-shadow:0 0 6px var(--gl); }
    .ck-usd .sep { width:1px; height:18px; background:rgba(255,255,255,.3); }
    .ck-usd button { background:var(--gl); color:#24401a; border:0; border-radius:999px; padding:5px 14px; font-weight:800; cursor:pointer; }
    .ck-udd { display:none; position:absolute; bottom:60px; left:50%; transform:translateX(-50%); width:min(560px,92vw); background:#fff; border-radius:14px; padding:12px 16px; font-size:.84rem; color:var(--ink); box-shadow:0 12px 28px rgba(0,0,0,.35); z-index:20; }
    .ck-udd.show { display:block; }
    .ck-udd a { color:var(--p); font-weight:700; }
    /* tf-cockpit-r2: the function menu and each department's functions as large cards in the centre */
    .ck-ctrls { grid-template-columns:repeat(4,minmax(0,1fr)); }
    .ck-ctl { padding:10px 12px; gap:10px; } .ck-ctl .ic { width:40px; height:40px; } .ck-ctl b { font-size:1.08rem; }
    .ck-cards { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; max-height:430px; overflow:auto; padding:2px; }
    .ck-card { display:flex; align-items:center; gap:0; min-height:84px; border-radius:16px; padding:12px 14px; color:#fff; box-shadow:0 5px 12px rgba(43,27,48,.14); transition:transform .12s, box-shadow .12s; cursor:pointer; border:0; text-align:left; font:inherit; width:100%; }
    .ck-card:hover { transform:translateY(-2px); box-shadow:0 10px 20px rgba(43,27,48,.2); color:#fff; }
    .ck-card:focus-visible { outline:3px solid #f4c56a; outline-offset:2px; }
    .ck-card.g { background:linear-gradient(145deg,#7cba4c,#69a83e); } .ck-card.p { background:linear-gradient(145deg,#8a2a8a,#7a1f7a); }
    .ck-card .ci { width:50px; height:50px; border-radius:50%; background:rgba(255,255,255,.9); display:grid; place-items:center; flex:none; font-size:1.35rem; }
    .ck-card.g .ci { color:#4f8a2a; } .ck-card.p .ci { color:#7a1f7a; }
    .ck-card .ct { flex:1; min-width:0; margin-left:14px; padding-left:14px; border-left:1.5px solid rgba(255,255,255,.55); }
    .ck-card .ct b { display:block; font-size:1.06rem; font-weight:800; line-height:1.2; text-shadow:0 1px 1px rgba(0,0,0,.12); }
    a.ck-card, a.ck-card:hover, a.ck-card:focus, .ck-card .ct, .ck-card .ct b, .ck-card .ct small, .ck-card .ca { color:#fff !important; text-decoration:none !important; }
    .ck-card.nr { background:#e9e2ea; color:#6f6574 !important; box-shadow:none; cursor:default; border:1.5px dashed #c9b9cc; }
    .ck-card.nr:hover { transform:none; box-shadow:none; }
    .ck-card.nr .ci { color:#9b8ea0; } .ck-card.nr .ct { border-left-color:#c9b9cc; } .ck-card.nr .ct, .ck-card.nr .ct b, .ck-card.nr .ct small, .ck-card.nr .ca { color:#6f6574 !important; }
    .ck .nr[aria-disabled], .ck .ck-c.nr { opacity:.7; cursor:default; }
    .ck-card .ct small { display:block; font-size:.78rem; opacity:.92; margin-top:3px; }
    .ck-card .ca { width:34px; height:34px; border-radius:50%; background:rgba(255,255,255,.22); display:grid; place-items:center; flex:none; margin-left:8px; font-size:.95rem; }
    .ck-card.big { grid-column:1 / -1; min-height:96px; } .ck-card.big .ci { width:60px; height:60px; font-size:1.6rem; } .ck-card.big .ct b { font-size:1.35rem; }
    .ck-back { display:inline-flex; align-items:center; gap:6px; margin-left:auto; border:1.5px solid var(--p); color:var(--p); background:#fff; border-radius:10px; padding:6px 12px; font-weight:700; font-size:.82rem; cursor:pointer; }
    .ck-back:hover { background:#fbf2fb; }
    .ck-fb[aria-current=true] { background:#fff; outline:2px solid var(--gl); }
    @media (max-width:1240px) { .ck-ctl b { font-size:1.05rem; } .ck-rl { columns:2; } .ck-cards { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:980px) { .ck-ctrls { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:640px) { .ck-cards { grid-template-columns:1fr; } }
    @media (max-width:980px) { .ck-grid { grid-template-columns:1fr; } .ck-side { flex-direction:row; flex-wrap:wrap; } .ck-side .cap { width:100%; } .ck-fb { flex:1 1 200px; width:auto; } .ck-cg { grid-template-columns:repeat(2,minmax(0,1fr)); } .ck-hdr .srchw { display:none; } .ck-top .dt { display:none; } }
    @media (max-width:640px) { .ck-ctrls, .ck-cg, .ck-pr { grid-template-columns:1fr; } .ck-rl { columns:1; } .ck-hdr { flex-wrap:wrap; } .ck-top .wel { font-size:1.15rem; } .ck-top .cmp { display:none; } .ck-usd { border-radius:16px; } }
</style>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
 <symbol id="ck-plane" viewBox="0 0 24 24"><path fill="currentColor" d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z"/></symbol>
 <symbol id="ck-gauge" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M8 34a16 16 0 1 1 32 0"/><path d="M24 34l9-11"/><path d="M12 30h3M33 30h3M24 16v3M15.5 21l2 2M32.5 21l-2 2"/></g><circle cx="24" cy="34" r="3" fill="currentColor"/></symbol>
 <symbol id="ck-nav" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"><circle cx="24" cy="24" r="18"/><path d="M24 10l5 14-5 14-5-14z" fill="currentColor" fill-opacity=".25"/><path d="M24 6v4M24 38v4M6 24h4M38 24h4"/></g></symbol>
 <symbol id="ck-auto" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><rect x="8" y="6" width="24" height="32" rx="3"/><path d="M14 14h12M14 20h12M14 26h7"/><circle cx="32" cy="32" r="7"/><path d="M37 37l5 5"/></g></symbol>
 <symbol id="ck-gh" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 30h22l6-8h6v8h2v4H6z"/><circle cx="14" cy="36" r="3"/><circle cx="34" cy="36" r="3"/><path d="M20 22l8-10 3 1-4 9"/></g></symbol>
 <symbol id="ck-coins" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-width="2.6"><ellipse cx="24" cy="12" rx="12" ry="5"/><path d="M12 12v8c0 3 5 5 12 5s12-2 12-5v-8M12 20v8c0 3 5 5 12 5s12-2 12-5v-8M12 28v8c0 3 5 5 12 5s12-2 12-5v-8"/></g></symbol>
 <symbol id="ck-rep" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><rect x="9" y="6" width="30" height="36" rx="3"/><path d="M16 34v-6M24 34V20M32 34V25"/></g></symbol>
 <symbol id="ck-folder" viewBox="0 0 48 48"><path fill="none" stroke="currentColor" stroke-width="2.6" stroke-linejoin="round" d="M6 14a3 3 0 0 1 3-3h10l4 4h16a3 3 0 0 1 3 3v19a3 3 0 0 1-3 3H9a3 3 0 0 1-3-3z"/></symbol>
 <symbol id="ck-van" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linejoin="round"><path d="M5 32V16h26l8 8v8z"/><circle cx="13" cy="34" r="3"/><circle cx="33" cy="34" r="3"/><path d="M31 16v8h8"/></g><circle cx="38" cy="10" r="4" fill="currentColor"/></symbol>
 <symbol id="ck-palm" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M22 40c-2-9-1-17 3-24"/><path d="M25 16c-5-4-12-3-15 1 5-1 10 0 15-1zM25 16c3-6 10-8 15-5-5 1-10 3-15 5z"/><path d="M6 41c8-4 28-4 36 0"/></g></symbol>
 <symbol id="ck-box" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linejoin="round"><rect x="6" y="22" width="16" height="16" rx="2"/><rect x="26" y="22" width="16" height="16" rx="2"/><rect x="16" y="6" width="16" height="16" rx="2"/></g></symbol>
 <symbol id="ck-chat" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linejoin="round"><path d="M6 10h26v18H16l-6 6v-6H6z"/><path d="M36 18h6v16h-4v5l-5-5H22v-4"/></g></symbol>
 <symbol id="ck-gear" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-width="2.6"><circle cx="24" cy="24" r="6"/><path d="M24 4v6M24 38v6M4 24h6M38 24h6M9.9 9.9l4.2 4.2M33.9 33.9l4.2 4.2M9.9 38.1l4.2-4.2M33.9 14.1l4.2-4.2"/></g></symbol>
 <symbol id="ck-arrow" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" d="M9 6l6 6-6 6"/></symbol>
</svg>

<div class="ck" id="ck">
<header class="ck-hdr">
    <a class="logo" href="<?= base_url('home') ?>" title="Home"><img src="<?= base_url('assets/images/Logo-new.png') ?>" alt="Avenir Holidays — Home"></a><span class="vr"></span>
    <span class="ttl"><svg width="24" height="24"><use href="#ck-plane"/></svg>Pilot Panel</span><span class="badge2">Super Admin</span>
    <span class="sp"></span>
    <span class="srchw"><?= view('layouts/page_search') ?></span>
    <span class="vr"></span>
    <button type="button" class="hb" id="ck_manual"><i class="bi bi-book"></i> User Manual</button>
    <button type="button" class="hb solid" onclick="logout()"><i class="bi bi-box-arrow-right"></i> Logout</button>
</header>

<div class="ck-clock" style="max-width:1340px;margin:12px auto 0;padding:0 4px"><?= view('partials/world_clock') ?></div><?php /* tf-clock-r1 */ ?>
<div class="ck-frame"><div class="ck-in">
    <span class="ck-rv" style="left:14px;top:14px"></span><span class="ck-rv" style="right:14px;top:14px"></span><span class="ck-rv" style="left:14px;bottom:14px"></span><span class="ck-rv" style="right:14px;bottom:14px"></span>
    <div class="ck-top">
        <svg class="cmp" width="42" height="42"><use href="#ck-nav"/></svg>
        <div class="wel">Welcome aboard, <em>Shantha</em></div>
        <span class="dt"><?= $e(date('l, j F Y')) ?></span>
    </div>
    <div class="ck-grid">
        <nav class="ck-side" aria-label="Function menu">
            <div class="cap">Function menu</div>
            <?php foreach ($cats as $c): ?>
                <button type="button" class="ck-fb<?= $c['name'] === 'Ground Handling' ? ' gh' : '' ?>" data-dept="<?= (int) $c['id'] ?>" title="<?= $c['n'] ?> function<?= $c['n'] === 1 ? '' : 's' ?>">
                    <svg class="ic"><use href="#ck-<?= $icon[$c['name']] ?? 'folder' ?>"/></svg><?= $e($c['name']) ?><svg class="ch" width="16" height="16"><use href="#ck-arrow"/></svg>
                </button>
            <?php endforeach; ?>
        </nav>
        <div class="ck-main">
            <div class="ck-ctrls" role="group" aria-label="Cockpit controls">
                <button type="button" class="ck-ctl" data-p="report" aria-pressed="false"><svg class="ic"><use href="#ck-gauge"/></svg><span><b>Pilot Report</b><small>Staff access &amp; work descriptions</small></span><span class="ch">›</span></button>
                <button type="button" class="ck-ctl" data-p="nav" aria-pressed="true"><svg class="ic"><use href="#ck-nav"/></svg><span><b>Navigation</b><small><?= count($tasks) ?> workload counters</small></span><span class="ch">›</span></button>
                <button type="button" class="ck-ctl" data-p="auto" aria-pressed="false"><svg class="ic"><use href="#ck-auto"/></svg><span><b>Autopilot</b><small>Your reports (<?= count($reports) ?>)</small></span><span class="ch">›</span></button>
                <button type="button" class="ck-ctl" data-p="func" aria-pressed="false"><svg class="ic"><use href="#ck-gear"/></svg><span><b>Function Menu</b><small><?= count($depts) ?> departments</small></span><span class="ch">›</span></button>
            </div>

            <section class="ck-disp" id="ck-func" hidden>
                <div class="ck-dh"><svg class="ic"><use href="#ck-gear"/></svg><h2>Function Menu</h2><span class="pill" style="background:#efe6dc;color:#6b5f55">Choose a department</span></div>
                <div class="ck-cards">
                    <?php $i = 0; foreach ($depts as $id => $d): $tone = ($i % 4 === 0 || $i % 4 === 3) ? 'g' : 'p'; $i++; ?>
                        <button type="button" class="ck-card <?= $tone ?>" data-dept="<?= (int) $id ?>"><span class="ci"><svg width="28" height="28"><use href="#ck-<?= $icon[$d['name']] ?? 'folder' ?>"/></svg></span><span class="ct"><b><?= $e($d['name']) ?></b><small><?= count($d['cards']) ?> function<?= count($d['cards']) === 1 ? '' : 's' ?></small></span><span class="ca">›</span></button>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php foreach ($depts as $id => $d): ?>
            <section class="ck-disp ck-dept" id="ck-dept-<?= (int) $id ?>" hidden>
                <div class="ck-dh"><svg class="ic"><use href="#ck-<?= $icon[$d['name']] ?? 'folder' ?>"/></svg><h2><?= $e($d['name']) ?></h2><button type="button" class="ck-back" data-p="func">‹ Back to Function Menu</button></div>
                <div class="ck-cards">
                    <?php $j = 0; foreach ($d['cards'] as $c): $tone = ($j % 6 < 3) ? (($j % 2) ? 'p' : 'g') : (($j % 2) ? 'g' : 'p'); if (!empty($c['big'])) { $tone = 'g'; } $j++; ?>
                        <?php if (!empty($c['nr'])): ?>
                        <div class="ck-card nr" title="The new screen is not finished yet; the old one is not linked"><span class="ci"><i class="bi <?= $cardIcon($c['name']) ?>"></i></span><span class="ct"><b><?= $e($c['name']) ?></b><small>New screen in progress</small></span><span class="ca"><i class="bi bi-hourglass-split"></i></span></div>
                        <?php else: ?>
                        <a href="<?= $e(base_url($c['url'])) ?>" class="ck-card <?= $tone ?><?= !empty($c['big']) ? ' big' : '' ?>"><span class="ci"><i class="bi <?= $cardIcon($c['name']) ?>"></i></span><span class="ct"><b><?= $e($c['name']) ?></b><?php if (!empty($c['sub'])): ?><small><?= $e($c['sub']) ?></small><?php endif; ?></span><span class="ca">›</span></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endforeach; ?>

            <section class="ck-disp" id="ck-nav">
                <div class="ck-dh"><svg class="ic"><use href="#ck-nav"/></svg><h2>Navigation</h2><span class="pill<?= $waiting ? ' on' : '' ?>"><?= $waiting ? $waiting . ' waiting' : 'Nothing is waiting' ?></span></div>
                <div class="ck-cg">
                    <?php foreach ($tasks as $t): ?>
                        <?php if (!empty($t['nr'])): ?><span class="ck-c nr" title="The new screen is not finished yet"><span class="v"><?= (int) $t['count'] ?></span><span><?= $e($t['label']) ?><small style="display:block;color:#8a7f8f">New screen in progress</small></span></span><?php else: ?><a class="ck-c<?= (int) $t['count'] > 0 ? ' has' : '' ?>" href="<?= $e(base_url($t['url'])) ?>"><span class="v"><?= (int) $t['count'] ?></span><span><?= $e($t['label']) ?></span></a><?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($shorts): ?>
                <div class="ck-sc">
                    <?php foreach ($shorts as $s): ?><a href="<?= $e(base_url($s['url'])) ?>"><i class="bi <?= $e($s['icon']) ?>"></i><?= $e($s['label']) ?></a><?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <section class="ck-disp" id="ck-report" hidden>
                <div class="ck-dh"><svg class="ic"><use href="#ck-gauge"/></svg><h2>Pilot Report</h2><span class="ck-lock">🔒 Super Admin only</span></div>
                <div class="ck-pr">
                    <?php foreach ($pr as $it): $d = !empty($it['nr']) ? 'New screen in progress' : ($prDesc[trim((string) $it['url'], '/')] ?? ''); ?>
                        <a <?= !empty($it['nr']) ? 'class="nr" aria-disabled="true"' : 'href="' . $e(base_url($it['url'])) . '"' ?>><span class="i"><i class="bi <?= str_contains((string) $it['url'], 'staff') ? 'bi-people' : 'bi-file-earmark-text' ?>"></i></span><span><?= $e(trim((string) $it['url'], '/') === 'master_files/staff' ? 'Staff Access Control Panel' : $it['name']) ?><?php if ($d): ?><small><?= $e($d) ?></small><?php endif; ?></span></a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="ck-disp" id="ck-auto" hidden>
                <div class="ck-dh"><svg class="ic"><use href="#ck-auto"/></svg><h2>Autopilot</h2><span class="pill" style="background:#efe6dc;color:#6b5f55">Your reports (<?= count($reports) ?>)</span></div>
                <label class="ck-rq"><i class="bi bi-search"></i><input type="search" id="ck_rq" placeholder="Search your <?= count($reports) ?> reports…" aria-label="Search reports"></label>
                <div class="ck-rl" id="ck_rl">
                    <?php foreach ($reports as $r): ?><a <?= !empty($r['nr']) ? 'class="nr" aria-disabled="true" title="New screen in progress"' : 'href="' . $e(base_url($r['url'])) . '"' ?> data-n="<?= $e(strtolower($r['label'])) ?>"><?= $e($r['label']) ?><?= !empty($r['nr']) ? ' <small>· new screen in progress</small>' : '' ?></a><?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
    <div class="ck-con">
        <div class="lt"><i></i><i></i><i></i></div>
        <?php if ($u): $rate = $u['entered'] ? $u['rate'] : $u['last_rate']; $rdate = $u['entered'] ? $u['today'] : $u['last_date']; ?>
        <div class="ck-usd" role="status">
            <span>⇄ USD / LKR <span class="r"><?= $rate !== null ? $e(number_format($rate, 2)) : '—' ?></span></span><span class="sep"></span>
            <span><?= $rdate ? 'rate of ' . $e(date('j M Y', strtotime($rdate))) : 'no rate yet' ?></span><span class="sep"></span>
            <span><span class="dot<?= $u['entered'] && empty($u['test']) ? ' ok' : '' ?>"></span><?= $u['entered'] ? (!empty($u['test']) ? 'Today\'s figure is marked TEST' : 'Entered today') : 'Today\'s rate not entered' ?></span>
            <button type="button" id="ck_ud" aria-expanded="false" aria-controls="ck_udd">Details</button>
        </div>
        <div class="ck-udd" id="ck_udd">
            <b>Today's USD rate · <?= $e(date('j M Y')) ?></b><br>
            <?php if ($u['entered']): ?>Entered by <?= $e($u['by']) ?> at <?= $e(date('H:i', strtotime($u['at']))) ?><?= $u['corrections'] ? ' · ' . (int) $u['corrections'] . ' correction(s) today' : '' ?>.<?php else: ?><?= $e($u['warning']) ?><?= $u['last_by'] ? ' Entered by ' . $e($u['last_by']) . '.' : '' ?> Until today's rate is entered, hotel payment requests wait and costings use the last rate with its own date.<?php endif; ?>
            <br><a href="<?= base_url('home#usd') ?>"><?= $u['entered'] ? 'Correct today\'s rate' : 'Enter today\'s rate' ?> →</a> · <a href="<?= base_url('master_files/daily_usd_rate') ?>">Rate history</a>
        </div>
        <?php endif; ?>
        <div class="lt"><i></i><i></i><i></i></div>
    </div>
</div></div>
</div>

<script>
(function () {
    'use strict';
    // one centre area: Navigation, Pilot Report, Autopilot, the Function Menu, or one department's cards
    var show = function (p) {
        var dept = /^dept-\d+$/.test(p) ? p : null, ctl = dept ? 'func' : p;
        if (!document.getElementById('ck-' + p)) { p = 'nav'; ctl = 'nav'; dept = null; }
        document.querySelectorAll('#ck .ck-ctl').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.p === ctl ? 'true' : 'false'); });
        document.querySelectorAll('#ck .ck-disp').forEach(function (s) { s.hidden = s.id !== 'ck-' + p; });
        document.querySelectorAll('#ck .ck-fb').forEach(function (b) { b.setAttribute('aria-current', dept && ('dept-' + b.dataset.dept) === dept ? 'true' : 'false'); });
        try { sessionStorage.setItem('ck-panel', p); } catch (e) {}
    };
    document.querySelectorAll('#ck .ck-ctl, #ck .ck-back').forEach(function (b) { b.addEventListener('click', function () { show(b.dataset.p); }); });
    document.querySelectorAll('#ck [data-dept]').forEach(function (b) { b.addEventListener('click', function () { show('dept-' + b.dataset.dept); }); });
    try { var last = sessionStorage.getItem('ck-panel'); if (last) { show(last); } } catch (e) {}
    var rq = document.getElementById('ck_rq');
    if (rq) { rq.addEventListener('input', function () { var q = rq.value.trim().toLowerCase(); document.querySelectorAll('#ck_rl a').forEach(function (a) { a.hidden = q && a.dataset.n.indexOf(q) < 0; }); }); }
    var ud = document.getElementById('ck_ud'), udd = document.getElementById('ck_udd');
    if (ud) {
        ud.addEventListener('click', function (e) { e.stopPropagation(); var o = !udd.classList.contains('show'); udd.classList.toggle('show', o); ud.setAttribute('aria-expanded', o ? 'true' : 'false'); });
        document.addEventListener('click', function (e) { if (!udd.contains(e.target)) { udd.classList.remove('show'); ud.setAttribute('aria-expanded', 'false'); } });
    }
    // User Manual in the header opens the system's own User Manual window (the floating button is hidden on this page)
    document.getElementById('ck_manual').addEventListener('click', function () { var f = document.querySelector('#avh-root .avh-fab'); if (f) { f.click(); } else { window.location.href = '<?= base_url('help') ?>'; } });
    if (typeof window.logout !== 'function') {
        window.logout = function () { window.location.href = '<?= base_url('logout') ?>'; };
    }
})();
</script>
