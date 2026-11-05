<?php
/**
 * Tour Desk — the menu bar shared by the new panels.
 *
 * One strip, the same on every panel it sits on. The work tabs — Tour Inquiry (Quotation
 * Desk), Confirmed Tours (whoever holds Tour Inquiry), Hotel Bookings (Hotel Desk), Transport Operations (Transport Desk), Tour Board (Management),
 * Admin (Management: the one place for cancel, delete, restore, removal and the settings)
 * — then a thin divider and the master files: Hotel Rates, Incomplete Work, Transport
 * Rates, Mileage Allowances, Excursion Rates, Pricing; and a New quote button. Somebody holding more than six tabs
 * gets the masters folded into one "Masters" menu so the bar keeps to one row. It is its
 * own thing, separate from the tile dashboard's bar, but cut from the same cloth as the
 * Avenir logo and the dashboard: its 12px cards and 10px buttons, its soft shadow.
 *
 * Only the entries the signed-in group may open are shown. The list comes from the same
 * privileges the sidebar reads ($allowed_menu_list, shared across views by CodeIgniter's
 * saveData); when it is not to hand every entry is shown and the route filter still has
 * the last word.
 *
 *   <?= view('layouts/tourdesk_bar') ?>
 *
 * Optional: $tourdesk_active — a URL to highlight when the current URL is not the one to
 * match (a sub-page, say). Otherwise the current path decides.
 */
// The work tabs first — one per desk, then the Board — and the master files after a thin
// divider. A person holding more than six tabs (Management) gets the masters folded into
// one "Masters" menu so the bar keeps to a single row; every desk keeps the flat layout.
$tdItems = [
    ['url' => 'tour/intake',                     'label' => 'Tour Inquiry',       'icon' => 'bi-inbox',       'zone' => 'work'],   // 1c: the tab carries the screen's own name (the sidebar entry is 'Tour Inquiry' too)
    // tf-confirmed-r1: every confirmed tour on one list — a page under Inquiries, so it is shown to (and opens for)
    // exactly the groups that hold Inquiries; 'gate' names the privilege it rides on, it has no menu entry of its own
    ['url' => 'tour/intake/confirmed',           'label' => 'Confirmed Tours',    'icon' => 'bi-calendar2-check', 'zone' => 'work', 'gate' => 'tour/intake'],
    ['url' => 'tour/hotel_bookings',             'label' => 'Hotel Bookings',     'icon' => 'bi-door-open',   'zone' => 'work'],
    ['url' => 'tour/transport_ops',              'label' => 'Transport Operations', 'icon' => 'bi-truck',     'zone' => 'work'],   // 1c: the same name as the screen and the sidebar entry
    ['url' => 'tour/board',                      'label' => 'Tour Board',         'icon' => 'bi-kanban',      'zone' => 'work'],
    // the entrance ticket stock (schema 37): a work tab with a count — the attractions low on or out of tickets
    ['url' => 'tour/ticket_stock',               'label' => 'Ticket Stock',       'icon' => 'bi-ticket-detailed', 'zone' => 'work'],
    // 1c (6 Oct 2026): the folded Masters menu reads in four groups — hotels; places, activities & meals; Transport & Guide
    // Services; pricing — each master keeps its own URL, privilege and label; 'group' only decides where it sits in the menu
    ['url' => 'master_files/hotel_rate_v2',      'label' => 'Hotel Rates',        'icon' => 'bi-building',    'zone' => 'master', 'group' => 'hotels', 'sub' => 'contracts, seasons, what a night costs'],
    ['url' => 'master_files/hotel_work',         'label' => 'Incomplete Work',    'icon' => 'bi-list-check',  'zone' => 'master', 'group' => 'hotels', 'sub' => 'what the system found missing, nothing guessed'],
    // Transport & Guide Services (Shantha, 5 Oct 2026): the four connected masters as one group in the Masters menu —
    // the rates, the mileage, the people (what is paid) and the quotation's crew roles (what is costed); each keeps its own
    // URL and privilege; Transport Operations is the desk's work and stays a work tab, outside the group
    ['url' => 'master_files/transport_rates',    'label' => 'Transport Rates',    'icon' => 'bi-cash-coin',   'zone' => 'master', 'group' => 'tgs', 'sub' => 'general LKR / km by vehicle class'],
    ['url' => 'master_files/mileage_allowances', 'label' => 'Mileage & Allowances', 'icon' => 'bi-signpost-2', 'zone' => 'master', 'group' => 'tgs', 'sub' => 'garage, positioning, extra km'],
    ['url' => 'master_files/crew_people',        'label' => 'Drivers & Guides',   'icon' => 'bi-people',      'zone' => 'master', 'group' => 'tgs', 'sub' => 'the people · what is paid'],
    ['url' => 'master_files/crew_bata',          'label' => 'Crew Roles & Quotation Costing', 'icon' => 'bi-calculator', 'zone' => 'master', 'group' => 'tgs', 'sub' => 'what is quoted — not what is paid'],
    // schema 39 (tf-entrance-r1): the one master of places and their entrance fees
    ['url' => 'master_files/entrance_master',    'label' => 'Places & Entrance Fees', 'icon' => 'bi-bank',    'zone' => 'master', 'group' => 'places', 'sub' => 'the one master of places and their fees'],
    // schema 40 (tf-meals-r1): lunches and dinners outside the hotel for Half Board tours
    ['url' => 'master_files/meal_master',        'label' => 'Meal Master',        'icon' => 'bi-cup-hot',     'zone' => 'master', 'group' => 'places', 'sub' => 'lunches and dinners outside the hotel'],
    // the two programme masters (schema 29): free visits (never costed) and paid activities & services (costed)
    ['url' => 'master_files/free_visits',        'label' => 'Free Visits',        'icon' => 'bi-geo-alt',     'zone' => 'master', 'group' => 'places', 'sub' => 'never costed'],
    ['url' => 'master_files/paid_services',      'label' => 'Paid Activities & Services', 'icon' => 'bi-ticket-perforated', 'zone' => 'master', 'group' => 'places', 'sub' => 'costed on the quotation'],
    // stays in use until UAT of Paid Activities & Services is signed off; nothing retires it automatically
    // tf-stage1 (29 Oct 2026): off the menu; the page still opens by address
    // ['url' => 'master_files/excursion_rates',    'label' => 'Excursion Rates',    'icon' => 'bi-compass',     'zone' => 'master', 'group' => 'places', 'sub' => 'until Paid Activities & Services is signed off'],
    // pinned: a master that keeps its own tab when the others fold into the Masters menu — the library is opened often, by staff as well as Admin
    ['url' => 'master_files/tour_photos',        'label' => 'Tour Photos',        'icon' => 'bi-images',      'zone' => 'master', 'pin' => true, 'group' => 'pricing', 'sub' => 'the picture library'],
    ['url' => 'tour/pricing',                    'label' => 'Pricing',            'icon' => 'bi-percent',     'zone' => 'master', 'group' => 'pricing', 'sub' => 'margins and the selling price'],
    // tf-mailtpl-r1 (4 Nov 2026): the saved wording of every email the system sends — agents, hotels, restaurants, drivers, guides and other suppliers
    ['url' => 'master_files/email_templates',    'label' => 'Email Templates',    'icon' => 'bi-envelope-paper', 'zone' => 'master', 'group' => 'letters', 'sub' => 'the saved wording of every email'],
    // the Admin tab: the privilege behind every ⋯ menu, and the one place for cancel, delete, restore, removal and the settings.
    // A work tab, never folded, shown only to a group that holds it (Management / the System Administrator).
    ['url' => 'tour/manage',                     'label' => 'Admin',              'icon' => 'bi-shield-lock', 'zone' => 'work'],
];

// tf-tdbar-r1 (25 Oct 2026, Shantha): a panel may ask for the Tour Desk's own five functions only — the same five as the Tour Desk
// dashboard — by passing ['tourdesk_desk_only' => true] to view('layouts/tourdesk_bar'). The other shortcuts (Hotel Bookings,
// Transport Operations, Ticket Stock, Admin, Tour Photos, the Masters) are then not drawn on that panel; nothing behind them
// changes — every page, route, privilege and count stays, and each remains reachable from its own desk and the sidebar.
$tdBrand = ['url' => 'tour/intake', 'title' => 'Tour Desk', 'sub' => 'one tour, three desks', 'icon' => 'bi-compass'];
// tf-resdesk-r1 (25 Oct 2026): the Hotel Reservation Desk context — ['tourdesk_desk_only' => 'reservation'] — on the pages opened
// from the Reservation Desk: the heading reads Hotel Reservation Desk and the strip carries that desk's six functions only;
// the New quote button (a Tour Inquiry function) is not drawn there. Nothing behind the other shortcuts changes.
if (($tourdesk_desk_only ?? null) === 'reservation') {
    $tdBrand = ['url' => 'home/reservation_desk', 'title' => 'Hotel Reservation Desk', 'sub' => 'hotels, tickets, arrivals', 'icon' => 'bi-building'];
    $rdAny = ['tour/hotel_bookings', 'tour/ticket_stock', 'tour/intake'];   // the calendars and the glance show to whoever holds one of these
    $tdItems = [
        ['url' => 'tour/hotel_bookings',                      'label' => 'Hotel Reservations & Bookings', 'icon' => 'bi-door-open',       'zone' => 'work'],
        ['url' => 'tour/ticket_stock',                        'label' => 'Ticket Stock',    'icon' => 'bi-ticket-detailed', 'zone' => 'work'],
        ['url' => 'tour/intake/confirmed',                    'label' => 'Confirmed Tours', 'icon' => 'bi-calendar2-check', 'zone' => 'work', 'gate' => 'tour/intake'],
        ['url' => 'home/tour_desk/upcoming?desk=reservation', 'label' => 'Upcoming Tours',  'icon' => 'bi-calendar3',       'zone' => 'work', 'gate' => $rdAny],
        ['url' => 'home/tour_desk/arrivals?desk=reservation', 'label' => 'Next Arrivals',   'icon' => 'bi-airplane-fill',   'zone' => 'work', 'gate' => $rdAny],
        ['url' => 'home/reservation_desk#tdk-glance',         'label' => 'At a Glance',     'icon' => 'bi-sun',             'zone' => 'work', 'gate' => $rdAny],
    ];
} elseif (($tourdesk_desk_only ?? null) === 'transport') {
    // tf-transdesk-r1 (25 Oct 2026): the Transport Operations Desk context on the pages opened from that desk — its functions only
    $tdBrand = ['url' => 'home/transport_desk', 'title' => 'Transport Operations Desk', 'sub' => 'vehicles, drivers, guides', 'icon' => 'bi-bus-front'];
    $xdAny = ['tour/transport_ops', 'dashboard2/transport/driver_allow/pending_tours', 'dashboard2/transport/driver_allow/tour_transport', 'tour/intake'];
    $tdItems = [
        ['url' => 'tour/transport_ops',                              'label' => 'Transport Operations',    'icon' => 'bi-truck',           'zone' => 'work'],
        ['url' => 'tour/transport_ops/allocations',                 'label' => 'Pending Allocations',     'icon' => 'bi-person-badge',    'zone' => 'work', 'gate' => $xdAny],   // tf-rotation-r1: the rotation's page
        ['url' => 'master_files/crew_people?desk=transport',         'label' => 'Driver & Guide Handling', 'icon' => 'bi-people',          'zone' => 'work'],
        // no railway booking page exists yet: the function is shown in its place, not linked (tf-transdesk-r1 design stage)
        ['url' => 'home/transport_desk#rail',                        'label' => 'Railway Bookings',        'icon' => 'bi-train-front',     'zone' => 'work', 'gate' => $xdAny, 'off' => 'Booking page not built yet'],
        ['url' => 'home/tour_desk/upcoming?desk=transport',          'label' => 'Upcoming Tours',          'icon' => 'bi-calendar3',       'zone' => 'work', 'gate' => $xdAny],
        ['url' => 'master_files/mileage_allowances?desk=transport',  'label' => 'Route Maps & Mileage',    'icon' => 'bi-signpost-2',      'zone' => 'work'],
        ['url' => 'home/transport_desk#tdk-glance',                  'label' => 'Today at a Glance',       'icon' => 'bi-eye',             'zone' => 'work', 'gate' => $xdAny],
    ];
} elseif (!empty($tourdesk_desk_only)) {
    $tdItems = [
        ['url' => 'tour/intake',               'label' => 'Tour Inquiries',  'icon' => 'bi-inbox',           'zone' => 'work'],
        ['url' => 'tour/intake/confirmed',     'label' => 'Confirmed Tours', 'icon' => 'bi-calendar2-check', 'zone' => 'work', 'gate' => 'tour/intake'],
        ['url' => 'tour/board',                'label' => 'Tour Board',      'icon' => 'bi-kanban',          'zone' => 'work'],
        // the two Tour Desk views (tf-tourdesk-r2): open to every signed-in user, so they ride on the Inquiries privilege here
        ['url' => 'home/tour_desk/upcoming',   'label' => 'Upcoming Tours',  'icon' => 'bi-calendar3',       'zone' => 'work', 'gate' => 'tour/intake'],
        ['url' => 'home/tour_desk/arrivals',   'label' => 'Next Arrivals',   'icon' => 'bi-airplane-fill',   'zone' => 'work', 'gate' => 'tour/intake'],
    ];
}

$tdAllowed = null;
if (isset($allowed_menu_list) && is_array($allowed_menu_list)) {
    $tdAllowed = [];
    foreach ($allowed_menu_list as $m) {
        $u = is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '');
        $u = trim((string) $u, "/ ");
        if ($u !== '') {
            $tdAllowed[$u] = true;
        }
    }
}
$tdMay = static function ($url) use ($tdAllowed): bool {
    foreach ((array) $url as $u) {   // tf-resdesk-r1: a gate may name several pages — any one held is enough
        if ($tdAllowed === null || isset($tdAllowed[trim(preg_replace('/[?#].*$/', '', (string) $u), '/')])) {
            return true;
        }
    }
    return false;
};

$tdHere = trim((string) ($tourdesk_active ?? (function_exists('uri_string') ? uri_string() : '')), '/');
$tdMatches = static function (string $url) use ($tdHere): bool {
    $u = trim($url, '/');
    return $tdHere === $u || str_starts_with($tdHere, $u . '/');
};
// the most specific entry wins, so Confirmed Tours (tour/intake/confirmed) lights its own tab and not Inquiries as well
$tdBest = '';
foreach ($tdItems as $it) {
    $u = trim(preg_replace('/[?#].*$/', '', $it['url']), '/');
    if ($tdMatches($u) && strlen($u) > strlen($tdBest)) {
        $tdBest = $u;
    }
}
$tdIsActive = static function (string $url) use ($tdBest): bool {
    return $tdBest !== '' && trim(preg_replace('/[?#].*$/', '', $url), '/') === $tdBest;
};
$tdQuote = $tdMay('tour/intake') && !in_array($tourdesk_desk_only ?? null, ['reservation', 'transport'], true);
// Counts on the tabs come from one place (Badges::all — the same queries the screens
// use); a page may still pass $tourdesk_badges to override one. Shown only above nought.
$tdBadges = \App\Libraries\TourFlow\Badges::all();
if (isset($tourdesk_badges) && is_array($tourdesk_badges)) {
    foreach ($tourdesk_badges as $k => $v) {
        if ($v !== null) {
            $tdBadges[$k] = $v;
        }
    }
}
$tdShown  = array_values(array_filter($tdItems, static fn ($it) => $tdMay($it['gate'] ?? $it['url'])));
$tdWork   = array_values(array_filter($tdShown, static fn ($it) => $it['zone'] === 'work'));
$tdMaster = array_values(array_filter($tdShown, static fn ($it) => $it['zone'] === 'master'));
$tdFold   = count($tdShown) > 6 && count($tdMaster) > 1;
// a pinned master keeps its own tab beside the folded Masters menu
$tdPinned = $tdFold ? array_values(array_filter($tdMaster, static fn ($it) => !empty($it['pin']))) : [];
if ($tdPinned) {
    $tdMaster = array_values(array_filter($tdMaster, static fn ($it) => empty($it['pin'])));
}
$tdMasterBadge = 0;
$tdMasterOn = false;
foreach ($tdMaster as $it) {
    $tdMasterBadge += (int) ($tdBadges[$it['url']] ?? 0);
    $tdMasterOn = $tdMasterOn || $tdIsActive($it['url']);
}
// 1c: the folded Masters menu in groups, in this order; a group is shown only when the signed-in group may open something in it
$tdGroupNames = ['hotels' => ['Hotels', 'bi-building'], 'places' => ['Places, activities & meals', 'bi-geo-alt'], 'tgs' => ['Transport & Guide Services', 'bi-truck-front'], 'pricing' => ['Pricing', 'bi-percent'], 'letters' => ['Letters & emails', 'bi-envelope-paper']];
$tdGroups = [];
foreach ($tdGroupNames as $g => $gn) {
    $in = array_values(array_filter($tdMaster, static fn ($it) => ($it['group'] ?? '') === $g));
    if ($in) {
        $tdGroups[$g] = $in;
    }
}
$tdUngrouped = array_values(array_filter($tdMaster, static fn ($it) => !isset($tdGroupNames[$it['group'] ?? ''])));
?>
<style>
    /* Tour Desk bar — the Avenir colours, taken from the logo itself and the sign-in page:
       purple #842484 and magenta #e40c84 (the script and the leaf; the sign-in gradient is
       #e90c8b→#8f2a8d), green #78b448 (the palm — the same family as the dashboard's Home
       button), with the dashboard's 12px cards, 0 4px 12px shadow, 10px buttons, #2c3e50 ink
       and #6c757d muted, and HK Grotesk inherited from the theme. */
    .tdbar { --td-purple:#842484; --td-magenta:#e90c8b; --td-purple-2:#8f2a8d; --td-green:#84C155; --td-green-2:#78b448;
             --td-ink:#2c3e50; --td-muted:#6c757d; --td-line:rgba(132,36,132,.18); --td-tint:rgba(132,36,132,.09); --td-card:#fff;
             --td-brand:linear-gradient(135deg, var(--td-magenta) 0%, var(--td-purple-2) 100%);
             display:flex; align-items:center; gap:10px; flex-wrap:wrap; min-height:58px; padding:9px 10px 9px 12px; margin:2px 0 18px;
             background:var(--td-card); border:1px solid var(--td-line); border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.08);
             font-family:inherit; color:var(--td-ink); }
    .tdbar a, .tdbar button { font-family:inherit; }
    .tdbar .td-brand { display:flex; align-items:center; gap:11px; padding-right:14px; margin-right:2px; border-right:1px solid var(--td-line); text-decoration:none; color:var(--td-ink); }
    .tdbar .td-brand .mark { width:38px; height:38px; border-radius:50%; display:grid; place-items:center; color:#fff; font-size:17px; flex:none;
             background:var(--td-brand); box-shadow:0 3px 10px rgba(143,42,141,.35);
             transition:transform .3s ease; }
    .tdbar .td-brand:hover .mark { transform:scale(1.08); }
    .tdbar .td-brand b { display:block; font-size:15px; font-weight:700; line-height:1.15; color:var(--td-ink); }
    .tdbar .td-brand small { display:block; font-size:11px; color:var(--td-muted); line-height:1.2; margin-top:2px; letter-spacing:.2px; }
    /* a desk context (Tour / Hotel Reservation / Transport Operations): the desk heading leads, larger than the page's own title below */
    .tdbar.td-desk .td-brand b { font-size:19px; letter-spacing:-.01em; }
    .tdbar .td-item.td-off { opacity:.55; cursor:default; }
    @media (max-width:1500px) { .tdbar.td-desk .td-item { padding:0 9px; gap:5px; font-size:12.5px; } .tdbar.td-desk .td-nav { gap:2px; } .tdbar.td-desk .td-brand { padding-right:10px; } }   /* seven desk functions on one row of an office laptop */
    .tdbar .td-nav { display:flex; align-items:center; gap:6px; flex-wrap:wrap; flex:1 1 auto; min-width:0; }
    .tdbar .td-item { display:inline-flex; align-items:center; gap:8px; height:38px; padding:0 16px; border-radius:10px; border:1px solid transparent;
             color:var(--td-ink); font-size:13.5px; font-weight:500; letter-spacing:.2px; line-height:1; white-space:nowrap; text-decoration:none; cursor:pointer; background:transparent;
             transition:background-color .2s ease, color .2s ease, box-shadow .2s ease, transform .2s ease; }
    .tdbar .td-item i { font-size:15px; color:var(--td-purple); transition:color .2s ease; }
    .tdbar .td-item:hover { background:var(--td-tint); color:var(--td-ink); }
    .tdbar .td-item:active { transform:translateY(1px) scale(.98); transition-duration:.1s; }
    .tdbar .td-item:focus-visible { outline:none; box-shadow:0 0 0 .2rem rgba(132,36,132,.22); }
    .tdbar .td-item.on { background:var(--td-brand); color:#fff; font-weight:600;
             box-shadow:0 3px 10px rgba(143,42,141,.35); }
    .tdbar .td-item.on i { color:#fff; }
    .tdbar .td-item.on:hover { color:#fff; box-shadow:0 5px 14px rgba(143,42,141,.45); }
    .tdbar .td-cta { margin-left:auto; position:relative; }
    .tdbar .td-quote { display:inline-flex; align-items:center; gap:8px; height:38px; padding:0 14px 0 16px; border-radius:10px; border:0;
             color:#fff; font-size:13.5px; font-weight:600; letter-spacing:.2px; line-height:1; white-space:nowrap; cursor:pointer;
             background:linear-gradient(135deg, var(--td-green) 0%, var(--td-green-2) 100%); box-shadow:0 3px 10px rgba(120,180,72,.35);
             transition:box-shadow .3s ease, transform .3s ease; }
    .tdbar .td-quote i { font-size:15px; }
    .tdbar .td-quote .chev { font-size:11px; opacity:.9; }
    .tdbar .td-quote:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(120,180,72,.4); }
    .tdbar .td-quote:active { transform:translateY(-1px) scale(.97); transition-duration:.1s; }
    .tdbar .td-quote:focus-visible, .tdbar .td-quote[aria-expanded="true"] { outline:none; box-shadow:0 0 0 .2rem rgba(120,180,72,.25), 0 3px 10px rgba(120,180,72,.35); }
    .tdbar .dropdown-menu { border:1px solid var(--td-line); border-radius:12px; padding:6px; min-width:300px; margin-top:8px !important;
             box-shadow:0 8px 32px rgba(0,0,0,.15); }
    .tdbar .dropdown-item { display:flex; gap:12px; align-items:flex-start; padding:10px 12px; border-radius:10px; white-space:normal; color:var(--td-ink); transition:background-color .2s ease; }
    .tdbar .dropdown-item .ic { width:36px; height:36px; border-radius:50%; display:grid; place-items:center; flex:none; background:#fff; color:var(--td-purple); font-size:15px;
             box-shadow:0 4px 12px rgba(0,0,0,.1); transition:transform .3s ease; }
    .tdbar .dropdown-item b { display:block; font-size:13.5px; font-weight:600; line-height:1.25; color:var(--td-ink); }
    .tdbar .dropdown-item span { display:block; font-size:11.5px; color:var(--td-muted); line-height:1.35; margin-top:2px; }
    .tdbar .dropdown-item:hover, .tdbar .dropdown-item:focus, .tdbar .dropdown-item:active { background:var(--td-tint); color:var(--td-ink); }
    .tdbar .dropdown-item:hover .ic { transform:scale(1.1); }
    .tdbar .td-sep { width:1px; height:24px; background:var(--td-line); margin:0 4px; flex:none; }
    .tdbar .td-item .chev { font-size:11px; color:inherit; opacity:.75; margin-left:-2px; }
    .tdbar .td-item.on .chev { color:#fff; }
    .tdbar .dropdown-item.active { background:var(--td-tint); color:var(--td-ink); }
    .tdbar .td-group { display:flex; align-items:center; gap:8px; padding:8px 12px 4px; font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--td-purple); }
    .tdbar .td-in-group { padding-left:18px; }
    .tdbar .dropdown-divider { border-color:var(--td-line); margin:6px 4px; }
    .tdbar .dropdown-item .td-badge { display:inline-grid; width:auto; vertical-align:middle; margin:0 0 0 4px; color:#fff; font-size:11px; line-height:1; }   /* 1c: the count reads white on red inside a menu entry too (the entry's span rule used to grey it out) */
    /* 1c: the Masters menu in two columns, a group never split across them, the dividers gone (the group headings do that work) */
    .tdbar .dropdown-menu.td-masters.show { column-count:2; column-gap:4px; min-width:660px; }
    .tdbar .td-masters .td-mgroup { break-inside:avoid; -webkit-column-break-inside:avoid; page-break-inside:avoid; display:inline-block; width:100%; }
    .tdbar .td-masters .dropdown-divider { display:none; }
    .tdbar .td-badge { display:inline-grid; place-items:center; min-width:20px; height:20px; padding:0 6px; border-radius:10px; font-size:11px; font-weight:700; line-height:1;
             background:#e53935; color:#fff; box-shadow:0 1px 3px rgba(0,0,0,.15); }
    .tdbar .td-item.on .td-badge { background:#fff; color:var(--td-purple); }

    /* ---- Tour Desk theme: the same colours and shapes on every panel that carries the bar. ----
       The panels define their look through custom properties (--tf-* on the Tour Inquiry, Transport
       Rates and Mileage Allowances pages; --hr-* on Hotel Rates and Incomplete Work). Re-pointing
       those here, after the page's own stylesheet, recolours every button, pill, heading and card
       in one place — no markup and no behaviour changes. Purple/magenta is the brand accent and
       the selected state; green is "make something"; amber and red keep their meanings. */
    .tf, .hr2 { --td-p:#842484; --td-p2:#8f2a8d; --td-m:#e90c8b; --td-g:#84C155; --td-g2:#78b448; }
    .tf { --tf-navy:#842484; --tf-navy-2:#8f2a8d; --tf-teal:#842484; --tf-teal-soft:#f3e7f3; --tf-blue-soft:#f3e7f3;
          --tf-ink:#2c3e50; --tf-muted:#6c757d; --tf-line:#e4e8ee; --tf-bg:#f6f7fa; --tf-radius:12px; }
    .hr2 { --hr-navy:#842484; --hr-navy-2:#8f2a8d; --hr-teal:#842484; --hr-teal-soft:#f3e7f3; --hr-blue-soft:#f3e7f3;
           --hr-ink:#2c3e50; --hr-muted:#6c757d; --hr-line:#e4e8ee; --hr-bg:#f6f7fa; --hr-radius:12px; }
    .tf .tf-card, .hr2 .hr-card { box-shadow:0 4px 12px rgba(0,0,0,.06); }
    .tf .tf-title h4, .hr2 .hr-title h4 { color:#2c3e50; letter-spacing:-.005em; }
    .tf .btn, .hr2 .btn { border-radius:10px; letter-spacing:.2px; }
    .tf .btn-primary, .hr2 .btn-primary { background:linear-gradient(135deg, #e90c8b 0%, #8f2a8d 100%); border-color:transparent; color:#fff; font-weight:600;
        box-shadow:0 3px 10px rgba(143,42,141,.3); transition:box-shadow .2s ease, transform .2s ease; }
    .tf .btn-primary:hover, .hr2 .btn-primary:hover { background:linear-gradient(135deg, #ec2a99 0%, #9a3398 100%); border-color:transparent; color:#fff; transform:translateY(-1px); box-shadow:0 6px 16px rgba(143,42,141,.38); }
    .tf .btn-primary:focus-visible, .hr2 .btn-primary:focus-visible { box-shadow:0 0 0 .2rem rgba(132,36,132,.25), 0 3px 10px rgba(143,42,141,.3); }
    .hr2 .btn-success { background:linear-gradient(135deg, #84C155 0%, #78b448 100%); border-color:transparent; color:#fff; font-weight:600; box-shadow:0 3px 10px rgba(120,180,72,.3); }
    .hr2 .btn-success:hover { background:linear-gradient(135deg, #8fca60 0%, #78b448 100%); border-color:transparent; color:#fff; }
    .tf .btn-light, .hr2 .btn-light { border-color:#e4e8ee; color:#2c3e50; }
    .tf .btn-light:hover, .hr2 .btn-light:hover { background:rgba(132,36,132,.08); border-color:rgba(132,36,132,.22); color:#2c3e50; }
    .tf .btn-light i, .hr2 .btn-light i { color:#842484; }
    .tf .pill-blue, .hr2 .pill-blue { background:#f3e7f3; color:#842484; }
    .tf .form-control:focus, .tf .form-select:focus, .hr2 .form-control:focus, .hr2 .form-select:focus { border-color:rgba(132,36,132,.45); box-shadow:0 0 0 .2rem rgba(132,36,132,.15); }
    .tf .form-check-input:checked, .hr2 .form-check-input:checked { background-color:#842484; border-color:#842484; }

    /* 1c: the two longer tab names (Tour Inquiry, Transport Operations) still fit one row on an office screen — tighter tabs under 1600px */
    @media (max-width: 1600px) { .tdbar .td-item { padding:0 11px; gap:6px; } .tdbar .td-nav { gap:3px; } .tdbar .td-brand { padding-right:10px; } }
    @media (max-width: 900px) { .tdbar .td-item .lbl { display:none; } .tdbar .td-item { padding:0 12px; } .tdbar .td-brand small { display:none; } }
    @media (max-width: 520px) { .tdbar .td-brand b { display:none; } .tdbar .td-brand { padding-right:8px; } .tdbar { padding:8px; } }
</style>
<nav class="tdbar<?= !empty($tourdesk_desk_only) ? ' td-desk' : '' ?>" aria-label="<?= esc($tdBrand['title']) ?>">
    <a class="td-brand" href="<?= base_url($tdBrand['url']) ?>" title="<?= esc($tdBrand['title']) ?>">
        <span class="mark"><i class="bi <?= esc($tdBrand['icon']) ?>"></i></span>
        <span><b><?= esc($tdBrand['title']) ?></b><small><?= esc($tdBrand['sub']) ?></small></span>
    </a>
    <div class="td-nav">
        <?php foreach ($tdWork as $it): ?>
        <?php if (!empty($it['off'])): ?>
        <span class="td-item td-off" title="<?= esc($it['off']) ?>" aria-disabled="true"><i class="bi <?= esc($it['icon']) ?>"></i><span class="lbl"><?= esc($it['label']) ?></span></span>
        <?php else: ?>
        <a class="td-item<?= $tdIsActive($it['url']) ? ' on' : '' ?>" href="<?= base_url($it['url']) ?>"<?= $tdIsActive($it['url']) ? ' aria-current="page"' : '' ?>>
            <i class="bi <?= esc($it['icon']) ?>"></i><span class="lbl"><?= esc($it['label']) ?></span><?php $n = $tdBadges[$it['url']] ?? null; if ($n !== null && (int) $n > 0): ?><span class="td-badge"><?= (int) $n ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($tdWork && ($tdMaster || $tdPinned)): ?><span class="td-sep" aria-hidden="true"></span><?php endif; ?>
        <?php foreach ($tdPinned as $it): ?>
        <a class="td-item<?= $tdIsActive($it['url']) ? ' on' : '' ?>" href="<?= base_url($it['url']) ?>"<?= $tdIsActive($it['url']) ? ' aria-current="page"' : '' ?>>
            <i class="bi <?= esc($it['icon']) ?>"></i><span class="lbl"><?= esc($it['label']) ?></span>
        </a>
        <?php endforeach; ?>
        <?php if ($tdFold): ?>
        <div class="dropdown">
            <button class="td-item<?= $tdMasterOn ? ' on' : '' ?>" type="button" id="td_masters" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                <i class="bi bi-collection"></i><span class="lbl">Masters</span><?php if ($tdMasterBadge > 0): ?><span class="td-badge"><?= (int) $tdMasterBadge ?></span><?php endif; ?><i class="bi bi-chevron-down chev"></i>
            </button>
            <div class="dropdown-menu td-masters">
                <?php foreach ($tdGroups as $g => $in): ?>
                <div class="td-mgroup">
                <div class="td-group"><i class="bi <?= esc($tdGroupNames[$g][1]) ?>"></i> <?= esc($tdGroupNames[$g][0]) ?></div>
                <?php foreach ($in as $it): ?>
                <a class="dropdown-item td-in-group<?= $tdIsActive($it['url']) ? ' active' : '' ?>" href="<?= base_url($it['url']) ?>">
                    <span class="ic"><i class="bi <?= esc($it['icon']) ?>"></i></span>
                    <div><b><?= esc($it['label']) ?><?php $n = $tdBadges[$it['url']] ?? null; if ($n !== null && (int) $n > 0): ?> <span class="td-badge"><?= (int) $n ?></span><?php endif; ?></b><?php if (!empty($it['sub'])): ?><span><?= esc($it['sub']) ?></span><?php endif; ?></div>
                </a>
                <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                <?php foreach ($tdUngrouped as $it): ?>
                <a class="dropdown-item<?= $tdIsActive($it['url']) ? ' active' : '' ?>" href="<?= base_url($it['url']) ?>">
                    <span class="ic"><i class="bi <?= esc($it['icon']) ?>"></i></span>
                    <div><b><?= esc($it['label']) ?><?php $n = $tdBadges[$it['url']] ?? null; if ($n !== null && (int) $n > 0): ?> <span class="td-badge"><?= (int) $n ?></span><?php endif; ?></b></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: foreach ($tdMaster as $it): ?>
        <a class="td-item<?= $tdIsActive($it['url']) ? ' on' : '' ?>" href="<?= base_url($it['url']) ?>"<?= $tdIsActive($it['url']) ? ' aria-current="page"' : '' ?>>
            <i class="bi <?= esc($it['icon']) ?>"></i><span class="lbl"><?= esc($it['label']) ?></span><?php $n = $tdBadges[$it['url']] ?? null; if ($n !== null && (int) $n > 0): ?><span class="td-badge"><?= (int) $n ?></span><?php endif; ?>
        </a>
        <?php endforeach; endif; ?>
    </div>
    <?php if ($tdQuote): ?>
    <div class="td-cta dropdown">
        <button class="td-quote" type="button" id="td_quote" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
            <i class="bi bi-plus-lg"></i><span>New quote</span><i class="bi bi-chevron-down chev"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item" href="<?= base_url('tour/intake?start=paste') ?>" data-start="paste">
                <span class="ic"><i class="bi bi-envelope"></i></span>
                <div><b>Paste an email</b><span>The agent's email is read for you — every value editable, nothing guessed.</span></div>
            </a>
            <a class="dropdown-item" href="<?= base_url('tour/intake?start=manual') ?>" data-start="manual">
                <span class="ic"><i class="bi bi-pencil"></i></span>
                <div><b>Start by hand</b><span>Type the same fields yourself. Same record either way.</span></div>
            </a>
        </div>
    </div>
    <?php endif; ?>
</nav>
<?= view('layouts/tf_ux') ?>
