<?= view('layouts/head') ?>

<body>

    <style>
        :root {
            --mantis: #7ABC49;
            --white: #FFFFFF;
            --carolina-blue: #64ADD4;
            --hunyadi-yellow: #E7B34E;
            --mantis-2: #84C155;
            --text-dark: #2c3e50;
            --text-muted: #6c757d;
        }

        body {
            background-color: transparent;
            position: relative;
        }

        /* Wallpaper lives on a fixed pseudo-layer behind everything instead of
           body's background-attachment:fixed — that property forces the browser
           to repaint the whole background on every scroll tick, which was
           making the fixed topbar visibly lag/slide during real (wheel/touch)
           scrolling. A separate composited layer scrolls at 0 cost. */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: -1;
            background-image: url('<?= base_url('assets/images/dashboard-wallpaper.png') ?>?v=<?= @filemtime(FCPATH . 'assets/images/dashboard-wallpaper.png') ?: 1 ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        @media (max-width: 767px) {
            body {
                padding-top: 60px;
            }

            .container-fluid {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }

            .container-fluid .row {
                margin-left: -0.25rem;
                margin-right: -0.25rem;
            }

            .container-fluid .row > [class*="col-"] {
                padding-left: 0.25rem;
                padding-right: 0.25rem;
                margin-bottom: 0.5rem;
            }
        }

        /* Custom column class for 9 tiles per row on desktop, responsive on mobile */
        .col-md-1-2 {
            flex: 0 0 11.11%;
            max-width: 11.11%;
        }

        /* Mobile responsive tile sizing */
        @media (max-width: 767px) {
            .col-md-1-2 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .col-md-1-2 {
                flex: 0 0 25%;
                max-width: 25%;
            }
        }

        @media (min-width: 992px) and (max-width: 1199px) {
            .col-md-1-2 {
                flex: 0 0 20%;
                max-width: 20%;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        @media (max-width: 767px) {
            .app-topbar .d-flex.gap-2 {
                gap: 0.5rem !important;
                flex-wrap: wrap;
            }

            /* New badge style - similar to the image with text + count */
            .app-topbar .position-relative a {
                display: inline-flex;
                align-items: center;
                gap: 2px;
                padding: 4px 8px;
                border-radius: 6px;
                font-size: 8px;
                font-weight: 500;
                min-width: auto;
                white-space: nowrap;
            }

            /* Keep the text visible on mobile for driver badges */
            .app-topbar .position-relative a span:not(.badge) {
                display: inline;
            }

            /* Style the count badge */
            .app-topbar .position-relative a .badge {
                background-color: rgba(255, 255, 255, 0.9) !important;
                padding: 2px 6px;
                border-radius: 10px;
                font-size: 8px;
                font-weight: 600;
                margin-left: 2px;
            }

            /* Different colored text for badges based on button type */
            .app-topbar .btn-info .badge {
                color: #17a2b8 !important;
            }

            .app-topbar .btn-success .badge {
                color: #28a745 !important;
            }

            /* Specific styling for the count badges */
            #tours_pending_count_tile,
            #tours_allocated_count_tile {
                background-color: rgba(255, 255, 255, 0.95) !important;
                padding: 2px 2px !important;
                border-radius: 2px !important;
                font-size: 7px !important;
                font-weight: 700 !important;
                border: 1px solid rgba(255, 255, 255, 0.5);
            }

            #tours_pending_count_tile {
                color: #17a2b8 !important;
            }

            #tours_allocated_count_tile {
                color: #28a745 !important;
            }

        }

        .t-card {
            background: var(--white);
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            height: 150px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease-in-out;
            border: 2px solid transparent;
        }

        @media (max-width: 767px) {
            .t-card {
                height: 130px;
                padding: 10px;
            }

            .t-card h6 {
                font-size: 0.85rem;
            }
        }

        .t-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .t-card:active {
            transform: translateY(-1px) scale(0.97);
            transition-duration: 0.1s;
        }

        /* Tile Color 1 - Tour Search (Mantis Green) */
        .t-card-color-1 {
            border-color: #7ABC49;
            background: rgba(122, 188, 73, 0.03);
        }

        .t-card-color-1:hover {
            background: rgba(122, 188, 73, 0.15);
            box-shadow: 0 8px 24px rgba(122, 188, 73, 0.7);
        }

        /* Tile Color 2 - New Inquiry (Deep Orange) */
        .t-card-color-2 {
            border-color: #FF5722;
            background: rgba(255, 87, 34, 0.03);
        }

        .t-card-color-2:hover {
            background: rgba(255, 87, 34, 0.15);
            box-shadow: 0 8px 24px rgba(255, 87, 34, 0.7);
        }

        /* Tile Color 3 - New Booking (Indigo) */
        .t-card-color-3 {
            border-color: #3F51B5;
            background: rgba(63, 81, 181, 0.03);
        }

        .t-card-color-3:hover {
            background: rgba(63, 81, 181, 0.15);
            box-shadow: 0 8px 24px rgba(63, 81, 181, 0.7);
        }

        /* Tile Color 4 - Hotel Management (Purple) */
        .t-card-color-4 {
            border-color: #9C27B0;
            background: rgba(156, 39, 176, 0.03);
        }

        .t-card-color-4:hover {
            background: rgba(156, 39, 176, 0.15);
            box-shadow: 0 8px 24px rgba(156, 39, 176, 0.7);
        }

        /* Tile Color 5 - Transport (Orange) */
        .t-card-color-5 {
            border-color: #FF9800;
            background: rgba(255, 152, 0, 0.03);
        }

        .t-card-color-5:hover {
            background: rgba(255, 152, 0, 0.15);
            box-shadow: 0 8px 24px rgba(255, 152, 0, 0.7);
        }

        /* Tile Color 6 - Driver Login (Red) */
        .t-card-color-6 {
            border-color: #F44336;
            background: rgba(244, 67, 54, 0.03);
        }

        .t-card-color-6:hover {
            background: rgba(244, 67, 54, 0.15);
            box-shadow: 0 8px 24px rgba(244, 67, 54, 0.7);
        }

        /* Tile Color 7 - Arrivals (Pink) */
        .t-card-color-7 {
            border-color: #E91E63;
            background: rgba(233, 30, 99, 0.03);
        }

        .t-card-color-7:hover {
            background: rgba(233, 30, 99, 0.15);
            box-shadow: 0 8px 24px rgba(233, 30, 99, 0.7);
        }

        /* Tile Color 8 - Confirmation (Cyan) */
        .t-card-color-8 {
            border-color: #00BCD4;
            background: rgba(0, 188, 212, 0.03);
        }

        .t-card-color-8:hover {
            background: rgba(0, 188, 212, 0.15);
            box-shadow: 0 8px 24px rgba(0, 188, 212, 0.7);
        }

        /* Tile Color 9 - Payment Receivable (Yellow) */
        .t-card-color-9 {
            border-color: #FFEB3B;
            background: rgba(255, 235, 59, 0.03);
        }

        .t-card-color-9:hover {
            background: rgba(255, 235, 59, 0.15);
            box-shadow: 0 8px 24px rgba(255, 235, 59, 0.7);
        }

        /* Tile Color 10 - Payment Payable (Brown) */
        .t-card-color-10 {
            border-color: #795548;
            background: rgba(121, 85, 72, 0.03);
        }

        .t-card-color-10:hover {
            background: rgba(121, 85, 72, 0.15);
            box-shadow: 0 8px 24px rgba(121, 85, 72, 0.7);
        }

        .t-card-2 {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            height: 220px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .icon-wrapper {
            background-color: var(--white);
            border-radius: 100%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        @media (max-width: 767px) {
            .icon-wrapper {
                width: 35px;
                height: 35px;
            }

            .icon-wrapper img {
                width: 25px !important;
            }
        }

        .t-card:hover .icon-wrapper {
            transform: scale(1.1);
        }

        .icon-mantis {
            color: var(--mantis);
        }

        .icon-blue {
            color: var(--carolina-blue);
        }

        .icon-yellow {
            color: var(--hunyadi-yellow);
        }

        .icon-mantis-2 {
            color: var(--mantis-2);
        }

        .rct_td {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .section-header {
            background: linear-gradient(135deg, var(--carolina-blue) 0%, #5a9ec8 100%);
            color: var(--white);
            padding: 9px 18px;
            border-radius: 10px;
            width: fit-content;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(100, 173, 212, 0.35);
            letter-spacing: 0.2px;
        }

        #menu_tile_search:focus {
            border-color: var(--carolina-blue) !important;
            box-shadow: 0 0 0 0.2rem rgba(100, 173, 212, 0.2) !important;
        }

        .cancelled-badge {
            display: inline-block;
            width: 70%;
            min-width: 220px;
            padding: 8px 0;
            font-size: 0.85rem;
            letter-spacing: 1px;
            border-radius: 6px;
        }

        @media (max-width: 767px) {
            .section-header {
                font-size: 0.85rem;
                padding: 6px 12px;
            }

            .t-card-2 {
                height: auto !important;
                min-height: 180px;
                padding: 10px !important;
            }
        }

        .badge-success-custom {
            background-color: var(--mantis) !important;
        }

        .badge-warning-custom {
            background-color: var(--hunyadi-yellow) !important;
        }

        .menu-cat-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
        }

        .menu-cat-card:active {
            transform: translateY(-1px) scale(0.97);
            transition-duration: 0.1s;
        }

    </style>

    <?php ob_start(); ?>
    <?php if (false && (session()->get('group_id') == 1 || session()->get('group_id') == 23)): // hidden: Pending/Allocated Drivers topbar buttons ?>
    <!-- Tours Pending Driver Allocation Notification -->
    <div class="position-relative" id="toursPendingNotificationTile">
        <a href="<?= base_url('dashboard2/transport/driver_allow/pending_tours') ?>"
           class="btn btn-sm btn-info d-flex align-items-center gap-2 text-decoration-none">
            <span>Pending Drivers</span>
            <span id="tours_pending_count_tile" class="badge bg-white text-info d-none">0</span>
        </a>
    </div>
    <!-- Tours with Allocated Drivers Notification -->
    <div class="position-relative" id="toursAllocatedNotificationTile">
        <a href="<?= base_url('dashboard2/transport/driver_allow/allocated_tours') ?>"
           class="btn btn-sm btn-success d-flex align-items-center gap-2 text-decoration-none">
            <span>Allocated Drivers</span>
            <span id="tours_allocated_count_tile" class="badge bg-white text-success d-none">0</span>
        </a>
    </div>
    <?php endif; ?>
    <?= view('layouts/topbar', ['topbar_extra' => ob_get_clean()]) ?>
    <div class="container-fluid p-4 mt-5">
        <?php
        // Action-tile behavior map, keyed by menu name. The 6 action tiles
        // (seeded under the Ground Handling category) render as icon cards
        // inside the Ground Handling modal. Behavior is view-owned here, not
        // derived from the row url:
        //   action=modal    -> open the shared tourSearchModal client-side
        //   action=redirect -> navigate to the given path (base_url-relative)
        // Any granted menu item NOT in this map falls back to a plain link
        // to its own url (see the modal loop below).
        // 'modal_value' preserves the original per-tile context that the shared
        // tourSearchModal reads (Tour Search=1, Transport=2, Hotel Management=3).
        // Keyed by "cat_id|menu name" — the same menu name can exist under
        // different categories (e.g. "Tour Search" under both Tour and Ground
        // Handling) and must route differently, so name alone is not a safe key.
        $actionTileMeta = [
            '25|Tour Search'   => ['action' => 'modal',    'target' => 'tourSearchModal',                         'modal_value' => 1, 'icon' => 'https://img.icons8.com/office/80/search--v1.png'],
            '25|New Inquiry'   => ['action' => 'redirect', 'target' => 'inquiries',                               'icon' => 'https://img.icons8.com/office/80/ask-question.png'],
            '25|New Booking'   => ['action' => 'redirect', 'target' => 'dashboard2/tour_search/new_quotation',    'icon' => 'https://img.icons8.com/office/80/add--v1.png'],
            '25|Hotel Management' => ['action' => 'modal', 'target' => 'tourSearchModal',                         'modal_value' => 3, 'icon' => 'https://img.icons8.com/office/80/building.png'],
            '25|Transport'     => ['action' => 'modal',    'target' => 'tourSearchModal',                         'modal_value' => 2, 'icon' => 'https://img.icons8.com/office/80/bus--v1.png'],
            '25|Transport Portal' => ['action' => 'redirect', 'target' => 'dashboard2/transport/transport_portal', 'icon' => 'https://img.icons8.com/office/80/steering-wheel.png'],
            '25|Ongoing Tour Transport' => ['action' => 'redirect', 'target' => 'dashboard2/transport/transport_ongoing', 'icon' => 'https://img.icons8.com/office/80/road.png'],
        ];
        ?>
        <?= view('pages/dashboard2/pilot_panel', ['pilot' => $pilot ?? null]) /* tf-pilot-r1: the department panel */ ?>
        <?php if (!empty($allowed_categories)): ?>
        <!-- Menu Categories Section -->
        <div class="row animate-fade-in mt-3">
            <div class="col-12 mb-3 d-flex align-items-center gap-3 flex-wrap">
                <span class="section-header"><i class="bi bi-grid-fill me-2"></i>Menu</span>
                <div class="position-relative" style="min-width:240px; max-width:340px; flex:1;">
                    <i class="bi bi-search position-absolute" style="left:14px;top:50%;transform:translateY(-50%);color:var(--carolina-blue);font-size:0.85rem;"></i>
                    <input type="text" id="menu_tile_search" class="form-control form-control-sm ps-5"
                           placeholder="Search menu..."
                           style="border-radius:20px;border:1.5px solid rgba(100,173,212,0.3);font-size:0.85rem;padding-top:9px;padding-bottom:9px;box-shadow:0 2px 6px rgba(100,173,212,0.12);transition:all 0.2s ease;">
                </div>
                <?php
                // Tour Desk — one button straight to the new quotation panels. Shown only when
                // the signed-in group holds tour/intake, the same privilege the route filter
                // checks; the grant lives in Group Privileges like every other entry.
                $tdHeld = false;
                foreach (($allowed_menu_list ?? []) as $tdM) {
                    $tdU = is_array($tdM) ? ($tdM['url'] ?? '') : ($tdM->url ?? '');
                    if (trim((string) $tdU, '/') === 'tour/intake') { $tdHeld = true; break; }
                }
                if ($tdHeld):
                ?>
                <a href="<?= base_url('tour/intake') ?>" class="tourdesk-btn ms-auto" title="Open the Tour Desk">
                    <i class="bi bi-compass"></i><span>Tour Desk</span>
                </a>
                <style>
                    .tourdesk-btn { display:inline-flex; align-items:center; gap:8px; height:38px; padding:0 16px; border-radius:10px; color:#fff; font-weight:600; font-size:0.9rem; letter-spacing:.2px; white-space:nowrap; text-decoration:none;
                                    background:linear-gradient(135deg, #e90c8b 0%, #8f2a8d 100%); box-shadow:0 3px 10px rgba(143,42,141,.35); transition:transform .3s ease, box-shadow .3s ease; }
                    .tourdesk-btn i { font-size:1rem; }
                    .tourdesk-btn:hover, .tourdesk-btn:focus { color:#fff; text-decoration:none; transform:translateY(-2px); box-shadow:0 8px 24px rgba(143,42,141,.4); }
                    .tourdesk-btn:active { transform:translateY(-1px) scale(.97); transition-duration:.1s; }
                    @media (max-width: 767px) { .tourdesk-btn { height:32px; padding:0 12px; font-size:0.8rem; } }
                </style>
                <?php endif; ?>
            </div>
            <div id="menu_tile_no_results" class="col-12 d-none text-muted small ps-1">
                <i class="bi bi-search me-1"></i> No menu items found.
            </div>
            <div id="menu_search_results" class="col-12 d-none mt-2"></div>
            <?php
            $catColorMap = ['#3F51B5','#7ABC49','#E7B34E','#00BCD4','#FF9800','#E91E63','#9C27B0','#FF5722','#795548','#F44336'];
            foreach ($allowed_categories as $catIndex => $cat):
                $catColor = $catColorMap[$catIndex % count($catColorMap)];
                $catAlpha = str_replace('#', '', $catColor);
                $catMenuItems = array_filter($allowed_menu_list, fn($m) => $m['cat_id'] == $cat['cat_id'] && $m['show_in_menu_bar'] != 1);
                $catMenuItems = array_values($catMenuItems);
                if (empty($catMenuItems)) continue;
            ?>
            <div class="col-md-1-2 mb-3 menu-cat-col"
                 data-cat-name="<?= esc(strtolower($cat['category']), 'attr') ?>"
                 data-menus="<?= esc(strtolower(implode('|', array_column($catMenuItems, 'menu'))), 'attr') ?>">
                <div class="card t-card menu-cat-card" data-cat-id="<?= $cat['cat_id'] ?>"
                     style="border-color: <?= $catColor ?>; background: rgba(<?= hexdec(substr($catAlpha,0,2)) ?>,<?= hexdec(substr($catAlpha,2,2)) ?>,<?= hexdec(substr($catAlpha,4,2)) ?>,0.03); cursor:pointer;"
                     onclick="openMenuCatModal(<?= $cat['cat_id'] ?>)">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="icon-wrapper">
                                <i class="bi bi-grid" style="font-size:1rem; color:<?= $catColor ?>;"></i>
                            </div>
                            <div class="text-center mt-2">
                                <h6 class="fw-bold" style="color: var(--text-dark); font-size:1rem;"><?= esc($cat['category']) ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Per-category menu modals -->
        <?php
        $catColorMap2 = ['#3F51B5','#7ABC49','#E7B34E','#00BCD4','#FF9800','#E91E63','#9C27B0','#FF5722','#795548','#F44336'];
        if (!empty($allowed_categories)):
        foreach ($allowed_categories as $catIndex2 => $cat2):
            $catColor2 = $catColorMap2[$catIndex2 % count($catColorMap2)];
            $catMenuItems2 = array_filter($allowed_menu_list, fn($m) => $m['cat_id'] == $cat2['cat_id'] && $m['show_in_menu_bar'] != 1);
            $catMenuItems2 = array_values($catMenuItems2);
            if (empty($catMenuItems2)) continue;
        ?>
        <div class="modal fade" id="menuCatModal_<?= $cat2['cat_id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:620px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="menuCatModalLabel_<?= $cat2['cat_id'] ?>">
                            <?= esc($cat2['category']) ?>
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="pilot-grid">
                            <?php
                            // Fallback cycle for any menu_id not present in $menuIconsById below
                            // (e.g. a newly added menu item not yet mapped to a specific icon).
                            $menuIcons = [
                                'https://img.icons8.com/office/80/workstation.png',
                                'https://img.icons8.com/office/80/approval.png',
                                'https://img.icons8.com/office/80/bank-building.png',
                                'https://img.icons8.com/office/80/document.png',
                                'https://img.icons8.com/office/80/combo-chart.png',
                                'https://img.icons8.com/office/80/inspection.png',
                                'https://img.icons8.com/office/80/settings.png',
                                'https://img.icons8.com/office/80/briefcase.png',
                                'https://img.icons8.com/office/80/user.png',
                                'https://img.icons8.com/office/80/report-card.png',
                            ];
                            // Per-menu icon map, keyed by menu_id — one Icons8 "office" icon
                            // chosen to match each specific menu item's meaning.
                            $menuIconsById = [
                                184 => 'https://img.icons8.com/office/80/data-in-both-directions.png',
                                107 => 'https://img.icons8.com/office/80/refresh.png',
                                87  => 'https://img.icons8.com/office/80/data-backup.png',
                                36  => 'https://img.icons8.com/office/80/edit-user-male.png',
                                37  => 'https://img.icons8.com/office/80/contacts.png',
                                1   => 'https://img.icons8.com/office/80/user-group-man-woman.png',
                                2   => 'https://img.icons8.com/office/80/category.png',
                                3   => 'https://img.icons8.com/office/80/list.png',
                                4   => 'https://img.icons8.com/office/80/privacy.png',
                                5   => 'https://img.icons8.com/office/80/user-group-man-man.png',
                                148 => 'https://img.icons8.com/office/80/calendar-plus.png',
                                140 => 'https://img.icons8.com/office/80/price-tag.png',
                                131 => 'https://img.icons8.com/office/80/airplane-front-view.png',
                                29  => 'https://img.icons8.com/office/80/supplier.png',
                                32  => 'https://img.icons8.com/office/80/document.png',
                                133 => 'https://img.icons8.com/office/80/driver.png',
                                68  => 'https://img.icons8.com/office/80/hotel-information.png',
                                134 => 'https://img.icons8.com/office/80/report-card.png',
                                67  => 'https://img.icons8.com/office/80/4-star-hotel.png',
                                70  => 'https://img.icons8.com/office/80/combo-chart.png',
                                66  => 'https://img.icons8.com/office/80/place-marker.png',
                                73  => 'https://img.icons8.com/office/80/route.png',
                                85  => 'https://img.icons8.com/office/80/gender-neutral-user.png',
                                77  => 'https://img.icons8.com/office/80/bus.png',
                                93  => 'https://img.icons8.com/office/80/social-distance.png',
                                164 => 'https://img.icons8.com/office/80/shop.png',
                                194 => 'https://img.icons8.com/office/80/online-payment-.png',
                                192 => 'https://img.icons8.com/office/80/withdrawal.png',
                                181 => 'https://img.icons8.com/office/80/bill.png',
                                172 => 'https://img.icons8.com/office/80/proforma-invoice.png',
                                139 => 'https://img.icons8.com/office/80/credit-note.png',
                                115 => 'https://img.icons8.com/office/80/check.png',
                                112 => 'https://img.icons8.com/office/80/bank-building.png',
                                146 => 'https://img.icons8.com/office/80/entrance-visa.png',
                                49  => 'https://img.icons8.com/office/80/accounting.png',
                                81  => 'https://img.icons8.com/office/80/moleskine.png',
                                90  => 'https://img.icons8.com/office/80/receive-cash.png',
                                56  => 'https://img.icons8.com/office/80/card-in-use.png',
                                84  => 'https://img.icons8.com/office/80/edit-property.png',
                                78  => 'https://img.icons8.com/office/80/cash-in-hand.png',
                                86  => 'https://img.icons8.com/office/80/invoice.png',
                                82  => 'https://img.icons8.com/office/80/transaction-list.png',
                                113 => 'https://img.icons8.com/office/80/purchase-order.png',
                                200 => 'https://img.icons8.com/office/80/statistics.png',
                                199 => 'https://img.icons8.com/office/80/approval.png',
                                198 => 'https://img.icons8.com/office/80/report-card.png',
                                183 => 'https://img.icons8.com/office/80/timeline.png',
                                158 => 'https://img.icons8.com/office/80/order-history--v2.png',
                                132 => 'https://img.icons8.com/office/80/bar-chart.png',
                                117 => 'https://img.icons8.com/office/80/control-panel.png',
                                110 => 'https://img.icons8.com/office/80/dashboard.png',
                                106 => 'https://img.icons8.com/office/80/quote-right.png',
                                79  => 'https://img.icons8.com/office/80/why-us-male.png',
                                130 => 'https://img.icons8.com/office/80/booking.png',
                                129 => 'https://img.icons8.com/office/80/add-ticket.png',
                                71  => 'https://img.icons8.com/office/80/quote.png',
                                74  => 'https://img.icons8.com/office/80/search.png',
                                76  => 'https://img.icons8.com/office/80/client-management.png',
                                98  => 'https://img.icons8.com/office/80/calendar.png',
                                121 => 'https://img.icons8.com/office/80/appointment-reminders.png',
                                126 => 'https://img.icons8.com/office/80/doughnut-chart.png',
                                137 => 'https://img.icons8.com/office/80/documents-folder.png',
                                163 => 'https://img.icons8.com/office/80/receipt-approved.png',
                                165 => 'https://img.icons8.com/office/80/star.png',
                                166 => 'https://img.icons8.com/office/80/checked.png',
                                196 => 'https://img.icons8.com/office/80/booking.png',
                                195 => 'https://img.icons8.com/office/80/receipt.png',
                                193 => 'https://img.icons8.com/office/80/payment-history.png',
                                182 => 'https://img.icons8.com/office/80/bar-chart.png',
                                180 => 'https://img.icons8.com/office/80/debt.png',
                                179 => 'https://img.icons8.com/office/80/cashbook.png',
                                178 => 'https://img.icons8.com/office/80/ledger.png',
                                174 => 'https://img.icons8.com/office/80/combo-chart.png',
                                143 => 'https://img.icons8.com/office/80/data-pending.png',
                                141 => 'https://img.icons8.com/office/80/scales.png',
                                135 => 'https://img.icons8.com/office/80/transaction-list.png',
                                125 => 'https://img.icons8.com/office/80/cancel.png',
                                124 => 'https://img.icons8.com/office/80/fine-print.png',
                                122 => 'https://img.icons8.com/office/80/bank-cards.png',
                                123 => 'https://img.icons8.com/office/80/bank.png',
                                120 => 'https://img.icons8.com/office/80/edit-property.png',
                                119 => 'https://img.icons8.com/office/80/alarm-clock.png',
                                116 => 'https://img.icons8.com/office/80/close-window.png',
                                111 => 'https://img.icons8.com/office/80/combo-chart.png',
                                109 => 'https://img.icons8.com/office/80/bank-building.png',
                                108 => 'https://img.icons8.com/office/80/general-ledger.png',
                                105 => 'https://img.icons8.com/office/80/age.png',
                                104 => 'https://img.icons8.com/office/80/age.png',
                                103 => 'https://img.icons8.com/office/80/economic-improvement.png',
                                102 => 'https://img.icons8.com/office/80/doughnut-chart.png',
                                101 => 'https://img.icons8.com/office/80/cash-in-hand.png',
                                100 => 'https://img.icons8.com/office/80/trial-version.png',
                                142 => 'https://img.icons8.com/office/80/scale.png',
                                88  => 'https://img.icons8.com/office/80/debt.png',
                                94  => 'https://img.icons8.com/office/80/bar-chart.png',
                                51  => 'https://img.icons8.com/office/80/general-ledger.png',
                                95  => 'https://img.icons8.com/office/80/sales-performance.png',
                                89  => 'https://img.icons8.com/office/80/receive-cash.png',
                                91  => 'https://img.icons8.com/office/80/purchase-order.png',
                                92  => 'https://img.icons8.com/office/80/invoice.png',
                                96  => 'https://img.icons8.com/office/80/refund.png',
                                114 => 'https://img.icons8.com/office/80/bill.png',
                                147 => 'https://img.icons8.com/office/80/checked-checkbox.png',
                                145 => 'https://img.icons8.com/office/80/receipt.png',
                                127 => 'https://img.icons8.com/office/80/approval.png',
                                128 => 'https://img.icons8.com/office/80/edit-property.png',
                                138 => 'https://img.icons8.com/office/80/airplane-landing.png',
                                118 => 'https://img.icons8.com/office/80/speech-bubble.png',
                                176 => 'https://img.icons8.com/office/80/approve.png',
                                175 => 'https://img.icons8.com/office/80/document.png',
                                159 => 'https://img.icons8.com/office/80/product.png',
                                160 => 'https://img.icons8.com/office/80/warehouse.png',
                                161 => 'https://img.icons8.com/office/80/note.png',
                                214 => 'https://img.icons8.com/office/80/combo-chart.png',
                                190 => 'https://img.icons8.com/office/80/maintenance.png',
                                185 => 'https://img.icons8.com/office/80/approve.png',
                                189 => 'https://img.icons8.com/office/80/check-book.png',
                                191 => 'https://img.icons8.com/office/80/checked-user-male.png',
                            ];
                            foreach ($catMenuItems2 as $mIdx => $mItem):
                                $mColor = $mIdx % 6;
                                // Action tiles (Tour Search, New Inquiry, ...) use their mapped
                                // icon + behavior; every other menu item keeps the default icon
                                // cycle and a plain link to its own url.
                                $meta = $actionTileMeta[$cat2['cat_id'] . '|' . $mItem['menu']] ?? null;
                                $mIcon = $meta['icon'] ?? $menuIconsById[$mItem['menu_id']] ?? $menuIcons[$mIdx % count($menuIcons)];
                                if ($meta && $meta['action'] === 'modal'):
                                    // Modal tile: no navigation; JS delegation opens the target modal.
                            ?>
                            <a href="javascript:void(0)" class="pilot-card pc-<?= $mColor ?> action-tile"
                               data-action="modal" data-target="<?= esc($meta['target'], 'attr') ?>"
                               data-modal-value="<?= esc((string)($meta['modal_value'] ?? ''), 'attr') ?>">
                                <div class="icon-wrapper">
                                    <img src="<?= $mIcon ?>" alt="<?= esc($mItem['menu'], 'attr') ?>" width="35"/>
                                </div>
                                <h6><?= esc($mItem['menu']) ?></h6>
                            </a>
                            <?php elseif ($meta && $meta['action'] === 'redirect'): ?>
                            <a href="<?= base_url($meta['target']) ?>" class="pilot-card pc-<?= $mColor ?> action-tile"
                               data-action="redirect" data-url="<?= esc($meta['target'], 'attr') ?>">
                                <div class="icon-wrapper">
                                    <img src="<?= $mIcon ?>" alt="<?= esc($mItem['menu'], 'attr') ?>" width="35"/>
                                </div>
                                <h6><?= esc($mItem['menu']) ?></h6>
                            </a>
                            <?php else: ?>
                            <a href="<?= base_url($mItem['url']) ?>" class="pilot-card pc-<?= $mColor ?>">
                                <div class="icon-wrapper">
                                    <img src="<?= $mIcon ?>" alt="<?= esc($mItem['menu'], 'attr') ?>" width="35"/>
                                </div>
                                <h6><?= esc($mItem['menu']) ?></h6>
                            </a>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>

        <?php if (session()->get('group_id') == 1): ?>
        <div class="row animate-fade-in mt-3">
            <div class="col-md-12">
                <div class="card t-card-2 p-1" style="cursor: default;">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-start">
                            <div class="section-header">
                                <i class="bi bi-arrow-counterclockwise me-2"></i> Recent Bookings
                            </div>
                            <div class="d-flex flex-column mt-2" id="recent_bookings">
                                <style>
                                    #recent_bookings .table th,
                                    #recent_bookings .table td {
                                        vertical-align: middle;
                                        border-color: rgba(100, 173, 212, 0.15);
                                    }

                                    #recent_bookings .table th {
                                        color: var(--text-dark);
                                        font-weight: 600;
                                        background-color: rgba(100, 173, 212, 0.08);
                                    }

                                    #recent_bookings .table td {
                                        color: var(--text-dark);
                                    }

                                    #recent_bookings .table-striped tbody tr:nth-of-type(odd) {
                                        background-color: rgba(100, 173, 212, 0.03);
                                    }

                                    #recent_bookings .table-hover tbody tr {
                                        transition: background-color 0.2s ease;
                                    }

                                    #recent_bookings .table-hover tbody tr:hover {
                                        background-color: rgba(100, 173, 212, 0.15) !important;
                                    }

                                    @media (max-width: 767px) {
                                        #recent_bookings .table {
                                            font-size: 0.75rem;
                                        }

                                        #recent_bookings .table th,
                                        #recent_bookings .table td {
                                            padding: 0.5rem 0.25rem;
                                        }

                                        #recent_bookings .badge {
                                            font-size: 0.65rem;
                                            padding: 0.2rem 0.4rem;
                                        }

                                        #recent_bookings_pagination {
                                            font-size: 0.75rem;
                                        }
                                    }

                                    /* Pagination styles */
                                    #recent_bookings_pagination .page-link {
                                        color: var(--carolina-blue);
                                        border-color: rgba(100, 173, 212, 0.3);
                                        transition: all 0.3s ease;
                                    }

                                    #recent_bookings_pagination .page-link:hover {
                                        background-color: rgba(100, 173, 212, 0.1);
                                        border-color: var(--carolina-blue);
                                        color: var(--carolina-blue);
                                    }

                                    #recent_bookings_pagination .page-item.active .page-link {
                                        background-color: var(--carolina-blue);
                                        border-color: var(--carolina-blue);
                                        color: white;
                                    }

                                    #recent_bookings_pagination .page-item.disabled .page-link {
                                        color: #6c757d;
                                        border-color: rgba(100, 173, 212, 0.15);
                                    }

                                    #recent_bookings_search_wrapper {
                                        margin-bottom: 15px;
                                    }

                                    #recent_bookings_search_wrapper .search-icon {
                                        position: absolute;
                                        left: 12px;
                                        top: 50%;
                                        transform: translateY(-50%);
                                        color: #6c757d;
                                        z-index: 10;
                                    }

                                    #recent_bookings_search {
                                        padding-left: 35px;
                                        border-radius: 6px;
                                        border: 1px solid rgba(100, 173, 212, 0.25);
                                        transition: all 0.3s ease;
                                    }

                                    #recent_bookings_search:focus {
                                        border-color: var(--carolina-blue);
                                        box-shadow: 0 0 0 0.2rem rgba(100, 173, 212, 0.15);
                                    }
                                </style>

                                <!-- Search Input -->
                                <div id="recent_bookings_search_wrapper" class="position-relative">
                                    <i class="bi bi-search search-icon"></i>
                                    <input type="text" id="recent_bookings_search" class="form-control form-control-sm"
                                           placeholder="Search by Manual ID, Tour Name, or Agent...">
                                </div>

                                <table class="table table-sm table-hover table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th rowspan="2" style="width: 8%; vertical-align: middle;">Manual Id</th>
                                            <th rowspan="2" style="width: 15%; vertical-align: middle;">Tour Name</th>
                                            <th rowspan="2" style="width: 5%; vertical-align: middle;" class="text-center">Pax</th>
                                            <th rowspan="2" style="width: 10%; vertical-align: middle;">Start & End Date</th>
                                            <th rowspan="2" style="width: 8%; vertical-align: middle;" class="text-center">Invoice</th>
                                            <th colspan="2" class="text-center" style="border-bottom: 1px solid #dee2e6;">Hotel Reservation</th>
                                            <th colspan="2" class="text-center" style="border-bottom: 1px solid #dee2e6;">Transport</th>
                                            <th rowspan="2" style="width: 5%; vertical-align: middle;" class="text-center">Action</th>
                                        </tr>
                                        <tr>
                                            <th class="text-center" style="width: 7%">Confirmation</th>
                                            <th class="text-center" style="width: 7%">Payment</th>
                                            <th class="text-center" style="width: 7%">Advance</th>
                                            <th class="text-center" style="width: 7%">Settled</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recent_bookings_tbody">
                                        <?php if (empty($recent_tours)) { ?>
                                            <tr>
                                                <td colspan="10" class="text-center text-muted">
                                                    <i class="bi bi-search me-2"></i> No recent bookings found.
                                                </td>
                                            </tr>
                                        <?php } else { ?>
                                            <?php foreach ($recent_tours as $index => $tour) { ?>
                                                <tr class="rct_td" data-id="<?= $tour['tour_id']; ?>" style="cursor: pointer;">
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <strong class="text-primary"><?php echo $tour['manual_id']; ?></strong>
                                                            <!-- <small class="text-muted">K-<?//php echo $tour['tour_id']; ?></small> -->
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <strong><?php echo $tour['consignee']; ?></strong>
                                                            <small class="text-muted">Agent: <?php echo $tour['agent_name']; ?></small>
                                                            <small class="text-muted">Booking Operator: <?php echo $tour['create_user']; ?></small>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-secondary"><?php echo $tour['passenger_count'] ?? '-'; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <small><?php echo !empty($tour['start_date']) ? date('Y-m-d', strtotime($tour['start_date'])) : '-'; ?></small>
                                                            <small><?php echo !empty($tour['end_date']) ? date('Y-m-d', strtotime($tour['end_date'])) : '-'; ?></small>
                                                        </div>
                                                    </td>
                                                    <!-- Agent Invoice -->
                                                    <td class="text-center">
                                                        <?php
                                                        $agentInvoiceClass = !empty($tour['agent_invoice_no']) ? 'btn-success' : 'btn-danger';
                                                        $agentInvoiceIcon = !empty($tour['agent_invoice_no']) ? '✓' : '✗';
                                                        ?>
                                                        <button class="btn <?= $agentInvoiceClass ?> btn-sm" style="min-width: 40px;">
                                                            <?= $agentInvoiceIcon ?>
                                                        </button>
                                                    </td>
                                                    <?php if ($tour['tour_cancel'] == 1): ?>
                                                        <!-- Cancelled Tour - Show message across remaining columns -->
                                                        <td colspan="5" class="text-center">
                                                            <span class="badge bg-danger cancelled-badge">CANCELLED</span>
                                                        </td>
                                                    <?php else: ?>
                                                        <?php
                                                        $transportOnly = $tour['transport_only'] ?? 0;
                                                        $hotelOnly = $tour['hotel_only'] ?? 0;
                                                        $hotelDisabled = $transportOnly == 1;
                                                        $transportDisabled = $hotelOnly == 1;
                                                        ?>
                                                        <!-- Hotel Confirmation -->
                                                        <td class="text-center" <?= $hotelDisabled ? 'style="background-color: #e9ecef; opacity: 0.5;"' : '' ?>>
                                                            <?php
                                                            $bookingConfirmedCnt = $tour['booking_confirmed_cnt'] ?? 0;
                                                            $hotelCountNew = $tour['hotel_count_new'] ?? 0;
                                                            $confirmClass = ($bookingConfirmedCnt == $hotelCountNew && $hotelCountNew > 0) ? 'btn-success' : 'btn-info';
                                                            ?>
                                                            <button class="btn <?= $confirmClass ?> btn-sm" style="min-width: 50px;" <?= $hotelDisabled ? 'disabled' : '' ?>>
                                                                <?= $bookingConfirmedCnt ?>/<?= $hotelCountNew ?>
                                                            </button>
                                                        </td>
                                                        <!-- Hotel Payment -->
                                                        <td class="text-center" <?= $hotelDisabled ? 'style="background-color: #e9ecef; opacity: 0.5;"' : '' ?>>
                                                            <?php
                                                            $hotelPaymentCnt = $tour['hotel_payment_cnt'] ?? 0;
                                                            $hotelCountNew = $tour['hotel_count_new'] ?? 0;
                                                            $hotelPaymentClass = ($hotelPaymentCnt == $hotelCountNew && $hotelCountNew > 0) ? 'btn-success' : 'btn-info';
                                                            ?>
                                                            <button class="btn <?= $hotelPaymentClass ?> btn-sm" style="min-width: 50px;" <?= $hotelDisabled ? 'disabled' : '' ?>>
                                                                <?= $hotelPaymentCnt ?>/<?= $hotelCountNew ?>
                                                            </button>
                                                        </td>
                                                        <!-- Transport Advance -->
                                                        <td class="text-center" <?= $transportDisabled ? 'style="background-color: #e9ecef; opacity: 0.5;"' : '' ?>>
                                                            <?php
                                                            $transportAdvanceClass = ($tour['transport_advance'] == 1) ? 'btn-success' : 'btn-danger';
                                                            $transportAdvanceIcon = ($tour['transport_advance'] == 1) ? '✓' : '✗';
                                                            ?>
                                                            <button class="btn <?= $transportAdvanceClass ?> btn-sm" style="min-width: 40px;" <?= $transportDisabled ? 'disabled' : '' ?>>
                                                                <?= $transportAdvanceIcon ?>
                                                            </button>
                                                        </td>
                                                        <!-- Transport Settled -->
                                                        <td class="text-center" <?= $transportDisabled ? 'style="background-color: #e9ecef; opacity: 0.5;"' : '' ?>>
                                                            <?php
                                                            $driverSettledClass = ($tour['driver_settled'] == 1) ? 'btn-success' : 'btn-danger';
                                                            $driverSettledIcon = ($tour['driver_settled'] == 1) ? '✓' : '✗';
                                                            ?>
                                                            <button class="btn <?= $driverSettledClass ?> btn-sm" style="min-width: 40px;" <?= $transportDisabled ? 'disabled' : '' ?>>
                                                                <?= $driverSettledIcon ?>
                                                            </button>
                                                        </td>
                                                        <!-- Action - Delete Button (only if no invoice) -->
                                                        <td class="text-center">
                                                            <?php if (empty($tour['agent_invoice_no'])): ?>
                                                                <button type="button" class="btn btn-danger btn-sm delete-tour-btn" title="Delete Tour"
                                                                    data-tour-id="<?= $tour['tour_id'] ?>"
                                                                    data-manual-id="<?= $tour['manual_id'] ?>">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php } ?>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <!-- Pagination Controls -->
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted small">
                                        Showing <span id="showing_start">1</span> to <span id="showing_end">10</span> of <span id="total_records"><?= $total_tours ?? 0 ?></span> bookings
                                    </div>
                                    <nav aria-label="Recent bookings pagination">
                                        <ul class="pagination pagination-sm mb-0" id="recent_bookings_pagination">
                                            <!-- Pagination buttons will be dynamically generated -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <style>
        .modal-content {
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            border: none;
        }

        @media (max-width: 767px) {
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            .modal-content {

            }

            .modal-header {
                padding: 0.75rem;
            }

            .modal-header .modal-title {
                font-size: 1rem;
            }

            .modal-body {
                padding: 0.75rem;
            }

            .modal-footer {
                padding: 0.5rem;
            }
        }

        .modal-header {
            background-color: var(--carolina-blue);
            color: var(--white);
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .btn-search-custom {
            background: linear-gradient(135deg, var(--mantis) 0%, var(--mantis-2) 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(122, 188, 73, 0.2);
        }

        .btn-search-custom:hover {
            background: linear-gradient(135deg, var(--mantis-2) 0%, var(--mantis) 100%);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(122, 188, 73, 0.4);
        }

        .btn-search-custom:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(122, 188, 73, 0.7);
        }

        .btn-logout-custom {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
        }

        .btn-logout-custom:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(220, 53, 69, 0.4);
        }

        .btn-logout-custom:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.7);
        }

        .btn-outline-secondary {
            border: 2px solid var(--text-muted);
            color: var(--text-muted);
            font-weight: 600;
            border-radius: 8px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background-color: var(--text-muted);
            border-color: var(--text-muted);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.7);
        }

        .search-input:focus {
            border-color: var(--carolina-blue);
            box-shadow: 0 0 0 0.2rem rgba(100, 173, 212, 0.25);
        }

        .search-table-wrapper {
            border: 1px solid rgba(100, 173, 212, 0.2);
            border-radius: 8px;
            background-color: rgba(100, 173, 212, 0.02);
        }

        @media (max-width: 767px) {
            .search-table-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .search-table-wrapper .table {
                font-size: 0.75rem;
                min-width: 600px;
            }

            .search-table-wrapper .table th,
            .search-table-wrapper .table td {
                padding: 0.5rem 0.25rem;
            }

            .search-table-wrapper .btn {
                font-size: 0.65rem;
                padding: 3px 6px;
                white-space: nowrap;
            }

            .search-table-wrapper .btn i {
                display: none;
            }
        }

        .search-table-wrapper .table thead th {
            background-color: rgba(100, 173, 212, 0.08);
            color: var(--text-dark);
            font-weight: 600;
            border-color: rgba(100, 173, 212, 0.15);
        }

        .search-table-wrapper .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .search-table-wrapper .table tbody tr:nth-of-type(odd) {
            background-color: rgba(100, 173, 212, 0.03);
        }

        .search-table-wrapper .table tbody tr:hover {
            background-color: rgba(100, 173, 212, 0.12) !important;
        }

        .search-table-wrapper .table td {
            border-color: rgba(100, 173, 212, 0.15);
        }

        .btn-view-details {
            background: linear-gradient(135deg, var(--carolina-blue) 0%, #5a9ec8 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(100, 173, 212, 0.25);
        }

        .btn-view-details:hover {
            background: linear-gradient(135deg, #5a9ec8 0%, var(--carolina-blue) 100%);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(100, 173, 212, 0.4);
        }

        .btn-reservation {
            background: linear-gradient(135deg, var(--mantis) 0%, var(--mantis-2) 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(122, 188, 73, 0.25);
        }

        .btn-reservation:hover {
            background: linear-gradient(135deg, var(--mantis-2) 0%, var(--mantis) 100%);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(122, 188, 73, 0.4);
        }

        .btn-itinerary {
            background: linear-gradient(135deg, var(--hunyadi-yellow) 0%, #d9a03d 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(231, 179, 78, 0.25);
        }

        .btn-itinerary:hover {
            background: linear-gradient(135deg, #d9a03d 0%, var(--hunyadi-yellow) 100%);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 179, 78, 0.4);
        }


        .btn-timeline {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(108, 117, 125, 0.25);
        }

        .btn-timeline:hover {
            background: linear-gradient(135deg, #5a6268 0%, #6c757d 100%);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
        }


        .btn-inv-amendment {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(23, 162, 184, 0.25);
        }

        .btn-inv-amendment:hover {
            background: linear-gradient(135deg, #138496 0%, #17a2b8 100%);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
        }


        .btn-inv-cancellation {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-inv-cancellation::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-inv-cancellation:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-inv-cancellation:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.5);
        }

        .btn-inv-cancellation:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.4);
        }
    </style>

    <div class="modal fade w-100" id="tourSearchModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="tourSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 mb-2" id="tourSearchModalLabel">
                        <i class="bi bi-search me-2"></i> Search Tours
                    </h1>
                    <button type="button" class="btn-close" aria-label="Close" id="tourSearchModalCloseBtn"></button>
                </div>
                <input type="hidden" id="modal_value">
                <div class="modal-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <input type="text" class="form-control search-input" id="tour_search_input" placeholder="Search by Manual ID or Tour Name">
                        </div>
                        <div class="col-md-2 col-6">
                            <label for="tour_search_start_date" class="form-label small mb-1">Start Date From</label>
                            <input type="date" class="form-control search-input" id="tour_search_start_date">
                        </div>
                        <div class="col-md-2 col-6">
                            <label for="tour_search_end_date" class="form-label small mb-1">Start Date To</label>
                            <input type="date" class="form-control search-input" id="tour_search_end_date">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-search-custom w-100" id="tour_search_btn">
                                <i class="bi bi-search me-1"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="search-table-wrapper p-3 mt-3">
                        <table class="table table-sm table-hover table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 10%">ID</th>
                                    <th>Name</th>
                                    <th style="width: 10%">Dates</th>
                                    <th style="width: 10%"></th>
                                    <th style="width: 45%"></th>
                                </tr>
                            </thead>
                            <tbody id="tour_search_results">
                                <tr>
                                    <!-- <td>1. </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span>ID: <strong>1234</strong></span>
                                            <span>Manual ID: <strong>LR-256</strong></span>
                                        </div>
                                    </td>
                                    <td>John Doe</td>
                                    <td>
                                        <button class="btn btn-success btn-sm">View Details</button>
                                    </td> -->
                                    <td colspan="5">
                                        <div class="text-center text-muted">
                                            <i class="bi bi-search me-2"></i> Enter a keyword to search for tours.
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="tourSearchModalCloseBtn2">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Authentication Modal Mobile Responsive Styles */
        #authenticationModal .modal-dialog {
            margin: 1rem;
        }

        @media (max-width: 767px) {
            #authenticationModal .modal-dialog {
                margin: 0.5rem auto;
                max-width: 300px; /* Smaller fixed width for mobile */
            }

            #authenticationModal .modal-content {
             
            }

            #authenticationModal .modal-header {
                padding: 0.5rem 0.75rem;
            }

            #authenticationModal .modal-header .modal-title {
                font-size: 0.9rem;
            }

            #authenticationModal .modal-header .modal-title i {
                font-size: 0.9rem;
            }

            #authenticationModal .modal-body {
                padding: 0.75rem;
            }

            #authenticationModal .modal-body .d-flex {
                gap: 0.5rem !important; /* Reduce gap between buttons */
            }

            #authenticationModal .modal-body .btn-lg {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }

            #authenticationModal .modal-body .btn-lg i {
                font-size: 1rem;
            }

            #authenticationModal .modal-footer {
                padding: 0.5rem 0.75rem;
            }

            #authenticationModal .modal-footer .btn {
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
            }
        }

        /* Additional touch-friendly styles for mobile */
        @media (max-width: 767px) {
            #authenticationModal .btn {
                min-height: 40px; /* Slightly smaller but still touch-friendly */
            }
        }
    </style>

    <!-- <div class="modal fade" id="authenticationModal" tabindex="-1" aria-labelledby="authenticationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="authenticationModalLabel">
                        <i class="bi bi-shield-check me-2"></i> Authentication
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-column gap-3">
                        <a href="<?= base_url('accounts/voucher_auth?tour_id=petty') ?>" class="btn btn-lg btn-primary d-flex align-items-center justify-content-center gap-2 text-decoration-none">
                            <i class="bi bi-cash-coin"></i>
                            <span>Petty Cash</span>
                        </a>
                        <a href="<?= base_url('accounts/voucher_auth?tour_id=special') ?>" class="btn btn-lg btn-success d-flex align-items-center justify-content-center gap-2 text-decoration-none">
                            <i class="bi bi-star-fill"></i>
                            <span>Special Payments</span>
                        </a>
                        <a href="<?= base_url('/tour/tour_accounts/payment_requested_dashboard_new') ?>" class="btn btn-lg btn-warning d-flex align-items-center justify-content-center gap-2 text-decoration-none">
                            <i class="bi bi-calendar-check"></i>
                            <span>Second Approval</span>
                        </a>
                         <a href="<?= base_url('/report/bank_payment_check') ?>" class="btn btn-lg btn-info d-flex align-items-center justify-content-center gap-2 text-decoration-none">
                            <i class="bi bi-bank"></i>
                            <span>Bank Payment Check</span>
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div> -->

    <style>
        /* Even card spacing inside category modals: fixed 3-column CSS grid with
           a single gap value, so horizontal gaps, vertical gaps and the outer
           margins (modal-body padding) all read as consistent — regardless of
           how many cards land in the last row. */
        .pilot-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        @media (max-width: 575px) {
            .pilot-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Sub-cards inside category modals share the main card (.t-card) look
           so a card is visually identical whether on the main grid or in a modal.
           Rendered for ALL groups — every category modal uses .pilot-card. */
        .pilot-card {
            background: var(--white);
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            height: 136px;
            width: 136px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s ease, background-color 0.25s ease;
            border: 2px solid transparent;
        }

        .pilot-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .pilot-card:active {
            transform: translateY(-1px) scale(0.97);
            transition-duration: 0.1s;
        }

        .pilot-card .icon-wrapper {
            background-color: var(--white);
            border-radius: 100%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.25s ease;
            margin-bottom: 10px;
        }

        .pilot-card:hover .icon-wrapper {
            transform: scale(1.1);
        }

        .pilot-card:active .icon-wrapper {
            transform: scale(0.96);
        }

        .pilot-card h6 {
            color: var(--text-dark);
            font-weight: bold;
            text-align: center;
            margin: 0;
            line-height: 1.2;
        }

        /* Rotating accent colors — same rest/hover treatment as .t-card-color-* */
        .pilot-card.pc-0 { border-color: #3F51B5; background: rgba(63,81,181,0.03); }
        .pilot-card.pc-0:hover { background: rgba(63,81,181,0.15); box-shadow: 0 8px 24px rgba(63,81,181,0.7); }
        .pilot-card.pc-1 { border-color: #7ABC49; background: rgba(122,188,73,0.03); }
        .pilot-card.pc-1:hover { background: rgba(122,188,73,0.15); box-shadow: 0 8px 24px rgba(122,188,73,0.7); }
        .pilot-card.pc-2 { border-color: #E7B34E; background: rgba(231,179,78,0.03); }
        .pilot-card.pc-2:hover { background: rgba(231,179,78,0.15); box-shadow: 0 8px 24px rgba(231,179,78,0.7); }
        .pilot-card.pc-3 { border-color: #00BCD4; background: rgba(0,188,212,0.03); }
        .pilot-card.pc-3:hover { background: rgba(0,188,212,0.15); box-shadow: 0 8px 24px rgba(0,188,212,0.7); }
        .pilot-card.pc-4 { border-color: #FF9800; background: rgba(255,152,0,0.03); }
        .pilot-card.pc-4:hover { background: rgba(255,152,0,0.15); box-shadow: 0 8px 24px rgba(255,152,0,0.7); }
        .pilot-card.pc-5 { border-color: #E91E63; background: rgba(233,30,99,0.03); }
        .pilot-card.pc-5:hover { background: rgba(233,30,99,0.15); box-shadow: 0 8px 24px rgba(233,30,99,0.7); }

        @media (max-width: 767px) {
            .pilot-card { height: 140px; width: 130px; padding: 10px; }
            .pilot-card h6 { font-size: 0.85rem; }
        }

        /* Menu search results reuse .pilot-card exactly (same size, colors,
           icon, hover) — this modifier only adds room for the category
           subtitle so cards stay visually identical to every other card. */
        #menu_search_results .pilot-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .pilot-card--search {
            height: 148px;
        }

        /* Subtle staggered entrance — each card fades/lifts in slightly after
           the previous one, driven by the inline --i custom property set per
           card (see menu_tile_search handler). Kept short so it reads as a
           smooth settle-in, not a showy effect. */
        @keyframes cardPopIn {
            from { opacity: 0; transform: translateY(8px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .pilot-card--search {
            animation: cardPopIn 0.35s ease-out both;
            animation-delay: calc(var(--i, 0) * 35ms);
        }

        #menu_tile_no_results {
            animation: cardPopIn 0.3s ease-out both;
        }

        .menu-cat-col {
            transition: opacity 0.2s ease;
        }

        .pilot-card--search .cat-label {
            font-size: 0.7rem;
            color: #6c757d;
            font-weight: 600;
            text-align: center;
            margin-top: 2px;
            line-height: 1.1;
        }
    </style>
    <?php if (session()->get('group_id') == 1): ?>
    <div class="modal fade" id="pilotReportModal" tabindex="-1" aria-labelledby="pilotReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="pilotReportModalLabel">
                        Pilot Report
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($pilot_report_items)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox me-2"></i> No reports available.
                        </div>
                    <?php else: ?>
                        <div class="pilot-grid">
                            <?php
                            $pilotIcons = [
                                'https://img.icons8.com/office/80/workstation.png',
                                'https://img.icons8.com/office/80/approval.png',
                                'https://img.icons8.com/office/80/bank-building.png',
                                'https://img.icons8.com/office/80/document.png',
                                'https://img.icons8.com/office/80/combo-chart.png',
                                'https://img.icons8.com/office/80/inspection.png',
                            ];
                            foreach ($pilot_report_items as $pIndex => $pItem):
                                $pColor = $pIndex % 6;
                                $pIcon = $pilotIcons[$pIndex % count($pilotIcons)];
                            ?>
                            <a href="<?= base_url($pItem['url']) ?>" class="pilot-card pc-<?= $pColor ?>">
                                <div class="icon-wrapper">
                                    <img src="<?= $pIcon ?>" alt="<?= esc($pItem['name'], 'attr') ?>" width="35"/>
                                </div>
                                <h6><?= esc($pItem['name']) ?></h6>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="modal" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="width: 50px; border-radius: 12px; border: none; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);">
                <div class="modal-body p-4">
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <img src="<?php echo base_url() ?>assets/images/loading.gif" width="30px">
                        <label class="form-label mt-2" style="color: var(--text-dark);">Just a moment...</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>
    <script src="<?php echo base_url(); ?>assets/bundles/morrisscripts.bundle.js"></script>
    <script src="<?php echo base_url(); ?>assets/vendor/echart/echarts.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/pages/charts/echart.js"></script>

    <script>
        // User privilege URLs from PHP
        const userPrivilegeUrls = <?php echo json_encode($user_privilege_urls ?? []); ?>;

        // Function to check if user has access to a URL
        function hasPrivilege(url) {
            // Check if the URL path matches any privilege URL
            return userPrivilegeUrls.some(privilegeUrl => {
                // Clean URLs by removing base_url if present, but keep the path intact
                const cleanUrl = url.replace(/^https?:\/\/[^\/]+\//, '').replace(/^\//, '');
                const cleanPrivilegeUrl = privilegeUrl.replace(/^https?:\/\/[^\/]+\//, '').replace(/^\//, '');
                return cleanUrl.includes(cleanPrivilegeUrl) || cleanPrivilegeUrl.includes(cleanUrl);
            });
        }

        // Action-tile delegation. The 6 action tiles are rendered inside the
        // Ground Handling category modal (see $actionTileMeta in the view).
        // modal   -> open the target modal, carry modal_value into #modal_value
        // redirect-> navigate to data-url (the <a href> also points there, so
        //            this mainly closes the open category modal first).
        $(document).on('click', '.action-tile', function(e) {
            const $tile = $(this);
            const action = $tile.data('action');

            if (action === 'modal') {
                e.preventDefault();
                const target = $tile.data('target');
                const modalValue = $tile.data('modal-value');
                // Close the Ground Handling category modal this tile lives in,
                // then open the shared search modal on top.
                $tile.closest('.modal').modal('hide');
                if (modalValue !== undefined && modalValue !== '') {
                    $('#modal_value').val(modalValue);
                }
                $('#' + target).modal('show');
            } else if (action === 'redirect') {
                const url = $tile.data('url');
                if (url) {
                    e.preventDefault();
                    window.location.href = '<?php echo base_url(); ?>' + url;
                }
            }
        });

        // #tour_search_input keyup event
        $('#tour_search_input').on('keyup', function(event) {
            if (event.key === 'Enter') {
                $('#tour_search_btn').trigger('click');
            }
        });

        // #tour_search_btn click event
        $('#tour_search_btn').on('click', function() {
            const keyword = $('#tour_search_input').val()?.trim();
            const startDate = $('#tour_search_start_date').val();
            const endDate = $('#tour_search_end_date').val();
            var modalValue = $('#modal_value').val();

            if (!keyword && !startDate && !endDate) {
                toastr.error('Enter a keyword or a date range to search.');
                return;
            }

            $.ajax({
                url: '<?php echo base_url(); ?>/dashboard2/searchTours',
                type: 'GET',
                data: {
                    keyword: keyword,
                    start_date: startDate,
                    end_date: endDate
                },
                dataType: 'json',
                beforeSend: function() {
                    $('#loadingModal').modal('show');
                },
                success: function(response) {
                    console.log(response);
                    const tbody = $('#tour_search_results');
                    tbody.empty();
                    if (response.status === 200) {
                        response.data?.forEach((tour, index) => {
                            const isCancelled = tour.quotation && tour.quotation[0] && tour.quotation[0].confirm == 3;
                            tbody.append(`
                                <tr style="${isCancelled ? 'background-color: #fdd; ' : ''}">
                                    <td>${index + 1}.</td>
                                    <td> 
                                        <div class="d-flex flex-column">
                                            <strong class="text-danger">${tour.manual_id}</strong>
                                            <span>ID: <strong>${tour.tour_id}</strong></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-secondary"><strong>${tour.consignee}</strong></span>
                                            <span class="text-muted">Agent: <strong>${tour.agent_name}</strong></span>
                                            <span class="text-muted">Booking Operator: <strong>${tour.create_user}</strong></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-secondary"> <strong>${tour.start_date}</strong></span>
                                            <span class="text-secondary"> <strong>${tour.end_date}</strong></span>
                                        </div>
                                    <td>
                                        <div class="d-flex flex-column">
                                            ${tour.quotation && tour.quotation[0] && [1, 3, 4].includes(tour.quotation[0].confirm) ? '<small class="badge badge-success-custom mb-1">Confirmed</small>' : '<small class="badge badge-warning-custom mb-1">Not Confirmed</small>'}
                                            ${tour.quotation && tour.quotation[0] && tour.quotation[0].finalize == 1 ? '<small class="badge badge-success-custom">Finalized</small>' : '<small class="badge badge-warning-custom">Not Finalized</small>'}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="ms-2">
                                            ${(() => {
                                                // Check if tour is cancelled
                                                if (tour.tour_cancel == 1) {
                                                    return '<span class="badge bg-danger">CANCELLED</span>';
                                                }
                                                if (modalValue == 1) {
                                                    if (tour.quotation && tour.quotation[0] && [1, 3, 4]) {
                                                        let buttons = '';

                                                        if (hasPrivilege('dashboard2/tour_search/tour_quotation')) {
                                                            buttons += `<a href="<?= base_url('dashboard2/tour_search/tour_quotation?tour_id=') ?>${tour.tour_id}" class="btn btn-view-details btn-sm me-1" target="_blank"><i class="bi bi-eye me-1"></i>View Details</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/tour_search/reservation_card')) {
                                                            buttons += `<a href="<?= base_url('dashboard2/tour_search/reservation_card?tour_id=') ?>${tour.tour_id}" class="btn btn-reservation btn-sm me-1" target="_blank"><i class="bi bi-calendar-check me-1"></i>Reservation</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/tour_search/itinerary_print')) {
                                                            buttons += `<a href="<?= base_url('dashboard2/tour_search/itinerary_print?tour_id=') ?>${tour.tour_id}" class="btn btn-itinerary btn-sm" target="_blank"><i class="bi bi-printer me-1"></i>Itinerary</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/tour_search/timeline')) {
                                                            buttons += `<a href="<?= base_url('dashboard2/tour_search/timeline?tour_id=') ?>${tour.tour_id}&edit=21" class="btn btn-timeline btn-sm" target="_blank"><i class="bi bi-clock me-1"></i>Edit Tour</a>`;
                                                        }

                                                        if (tour.amend_inv) {
                                                            buttons += `<a href="<?= base_url('tour/quotation/timeline/') ?>${tour.tour_id}?edit=2" class="btn btn-timeline btn-sm" target="_blank"><i class="bi bi-pencil-square me-1"></i>Amend Tour</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/tour_search/inv_amendment')) {
                                                            buttons += `<button type="button" class="btn btn-inv-amendment btn-sm request-btn" data-tour-id="${tour.tour_id}" data-inv-no="${tour.amend_inv}" data-request-type="amendment"><i class="bi bi-pencil me-1"></i>Amendment Request</button>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/tour_search/inv_cancellation')) {
                                                            buttons += `<button type="button" class="btn btn-inv-cancellation btn-sm request-btn" data-tour-id="${tour.tour_id}" data-inv-no="${tour.amend_inv}" data-request-type="cancel"><i class="bi bi-x-circle me-1"></i> CXLD</button>`;
                                                        }

                                                        return buttons || '<span class="text-muted">No access</span>';
                                                    }
                                                    return '';
                                                } else if (modalValue == 2) {
                                                    if (tour.quotation && tour.quotation[0] && [1, 3, 4].includes(tour.quotation[0].confirm)) {
                                                        let buttons = '';

                                                        if (hasPrivilege('dashboard2/transport/driver_allow/tour_transport')) {
                                                            buttons += `<a href="<?= base_url('dashboard2/transport/driver_allow/tour_transport?tour_id=') ?>${tour.tour_id}" class="btn btn-view-details btn-sm me-1" target="_blank"><i class="bi bi-truck me-1"></i>Driver Allocation</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/transport/driver_allow/advance_request')) {
                                                            buttons += `<a href="<?= base_url('dashboard2/transport/driver_allow/advance_request?tour_id=') ?>${tour.tour_id}" class="btn btn-view-details btn-sm me-1" target="_blank"><i class="bi bi-truck me-1"></i>Driver Advance Request</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/transport/shop_allow') && tour.has_driver_allocation && tour.allocated_driver !== '6-9-11-10691') {
                                                            buttons += `<a href="<?= base_url('dashboard2/transport/shop_allow?tour_id=') ?>${tour.tour_id}" class="btn btn-info btn-sm me-1" target="_blank"><i class="bi bi-shop me-1"></i>Shop Allocation</a>`;
                                                        }

                                                        let subButtons = '';

                                                        // if (hasPrivilege('dashboard2/transport/driver_advance')) {
                                                        //     subButtons += `<a href="<?//= base_url('dashboard2/transport/driver_advance?tour_id=') ?>${tour.tour_id}" class="btn btn-primary btn-sm" target="_blank"><i class="bi bi-cash-stack me-1"></i>Advance</a>`;
                                                        // }

                                                        if (hasPrivilege('dashboard2/transport/driver_expense')) {
                                                            subButtons += `<a href="<?= base_url('dashboard2/transport/driver_expense?tour_id=') ?>${tour.tour_id}" class="btn btn-success btn-sm" target="_blank"><i class="bi bi-receipt me-1"></i>Expense</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/transport/driver_logsheet')) {
                                                            subButtons += `<a href="<?= base_url('dashboard2/transport/driver_logsheet?tour_id=') ?>${tour.tour_id}" class="btn btn-info btn-sm" target="_blank"><i class="bi bi-clipboard-data me-1"></i>Log Sheet</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/transport/driver_settlement/driver_settlement_request')) {
                                                            subButtons += `<a href="<?= base_url('dashboard2/transport/driver_settlement/driver_settlement_request?tour_id=') ?>${tour.tour_id}" class="btn btn-secondary btn-sm" target="_blank"><i class="bi bi-invoice me-1"></i>Settlement Request</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/transport/driver_settlement')) {
                                                            subButtons += `<a href="<?= base_url('dashboard2/transport/driver_settlement?tour_id=') ?>${tour.tour_id}" class="btn btn-warning btn-sm" target="_blank"><i class="bi bi-check-circle me-1"></i>Settlement</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/transport/fund_return')) {
                                                            subButtons += `<a href="<?= base_url('dashboard2/transport/fund_return?tour_id=') ?>${tour.tour_id}" class="btn btn-danger btn-sm" target="_blank" data-toggle="tooltip"><i class="bi bi-transfer me-1"></i>Fund Return</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/transport/driver_report')) {
                                                            subButtons += `<a href="<?= base_url('dashboard2/transport/driver_report?tour_id=') ?>${tour.tour_id}" class="btn btn-dark btn-sm" target="_blank"><i class="bi bi-file-earmark-text me-1"></i>Driver Log Report</a>`;
                                                        }

                                                        if (hasPrivilege('dashboard2/transport/driver_report')) {
                                                            subButtons += `<a href="<?= base_url('driver_portal/settlement_invoice?tour_id=') ?>${tour.tour_id}" class="btn btn-dark btn-sm" target="_blank"><i class="bi bi-receipt-cutoff me-1"></i>Settlement Invoice</a>`;
                                                        }

                                                        if (subButtons) {
                                                            buttons += `<div class="d-flex flex-wrap gap-2 mt-2">${subButtons}</div>`;
                                                        }

                                                        return buttons || '<span class="text-muted">No access</span>';
                                                    }
                                                    return '';
                                                } else if (modalValue == 3) {
                                                    if (tour.quotation && tour.quotation[0] && [1, 3, 4].includes(tour.quotation[0].confirm)) {
                                                        let buttons = '';

                                                        if (hasPrivilege('dashboard2/hotel_management/hotel_reservation')) {
                                                            buttons += `<a href="<?= base_url('dashboard2/hotel_management/hotel_reservation?tour_id=') ?>${tour.tour_id}" class="btn btn-view-details btn-sm me-1" target="_blank"><i class="bi bi-building me-1"></i>Hotel Reservation</a>`;
                                                        }

                                                        // if (hasPrivilege('dashboard2/hotel_management/hotel_booking')) {
                                                        //     buttons += `<a href="<?//= base_url('dashboard2/hotel_management/hotel_booking?tour_id=') ?>${tour.tour_id}" class="btn btn-info btn-sm me-1" target="_blank"><i class="bi bi-file-earmark-check me-1"></i>Hotel Confirmation</a>`;
                                                        // }

                                                        // if (hasPrivilege('dashboard2/hotel_management/hotel_payment')) {
                                                        //     buttons += `<a href="<?//= base_url('dashboard2/hotel_management/hotel_payment?tour_id=') ?>${tour.tour_id}" class="btn btn-success btn-sm me-1" target="_blank"><i class="bi bi-cash-coin me-1"></i>Hotel Payment</a>`;
                                                        // }

                                                        if (hasPrivilege('tour/print/account_reservation_card')) {
                                                            buttons += `<a href="<?= base_url('tour/print/account_reservation_card?tour_id=') ?>${tour.tour_id}" class="btn btn-danger btn-sm me-1" target="_blank" data-toggle="tooltip" title="Print Without Amounts"><i class="bi bi-card-text me-1"></i>Account Reservation Card</a>`;
                                                        }

                                                        return buttons || '<span class="text-muted">No access</span>';
                                                    }
                                                    return '';
                                                }
                                                return '';
                                            })()}
                                        </div>
                                    </td>
                                </tr>
                            `);
                        });
                    } else {
                        toastr.error(response.message || 'No tours found for the given keyword.');
                        tbody.append(`
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        <i class="bi bi-search me-2"></i> No tours found for the given keyword.
                                    </td>
                                </tr>
                            `);
                    }
                },
                error: function() {
                    toastr.error('An error occurred while searching for tours. Please try again later.');
                },
                complete: function() {
                    $('#loadingModal').modal('hide');
                }
            });
        });

         $('#tourSearchModalCloseBtn').on('click', function() {
            location.reload();
        });
        
        $('#tourSearchModalCloseBtn2').on('click', function() {
            location.reload();
        });

        // New Inquiry / New Booking / Transport Portal / Pilot Report tiles were
        // removed from the top row. New Inquiry, New Booking and Transport Portal
        // now render as action tiles inside the Ground Handling modal and are
        // handled by the .action-tile delegation above.

        // #authentication_tile click event
        $('#authentication_tile').on('click', function() {
            $('#authenticationModal').modal('show');
        });

        // Open menu category modal
        function openMenuCatModal(catId) {
            $('#menuCatModal_' + catId).modal('show');
        }

        // Menu tile search
        $('#menu_tile_search').on('input', function() {
            const q = $(this).val().trim().toLowerCase();

            if (!q) {
                // Restore category cards, hide results list
                $('.menu-cat-col').show();
                $('#menu_tile_no_results').addClass('d-none');
                $('#menu_search_results').addClass('d-none').empty();
                return;
            }

            // Hide category cards while searching
            $('.menu-cat-col').hide();

            // Collect all matching menu items across all modals
            let results = [];
            $('[id^="menuCatModal_"]').each(function() {
                const catName = $(this).find('.modal-title').text().trim();
                $(this).find('a.pilot-card').each(function() {
                    const menuName = $(this).find('h6').text().trim();
                    const menuUrl = $(this).attr('href');
                    const imgSrc = $(this).find('img').attr('src');
                    const colorClass = $(this).attr('class').match(/pc-\d/)?.[0] || 'pc-0';
                    const isActionTile = $(this).hasClass('action-tile');
                    const dataAction = $(this).data('action');
                    const dataTarget = $(this).data('target');
                    const dataModalValue = $(this).data('modal-value');
                    const dataUrl = $(this).data('url');
                    if (menuName.toLowerCase().includes(q) || catName.toLowerCase().includes(q)) {
                        results.push({ catName, menuName, menuUrl, imgSrc, colorClass, isActionTile, dataAction, dataTarget, dataModalValue, dataUrl });
                    }
                });
            });

            // Build results grid
            const $resultsEl = $('#menu_search_results');
            $resultsEl.empty();

            if (results.length === 0) {
                $('#menu_tile_no_results').removeClass('d-none');
                $resultsEl.addClass('d-none');
            } else {
                $('#menu_tile_no_results').addClass('d-none');
                let html = '<div class="pilot-grid">';
                results.forEach(function(r, i) {
                    const actionAttrs = r.isActionTile
                        ? ` data-action="${r.dataAction ?? ''}" data-target="${r.dataTarget ?? ''}" data-modal-value="${r.dataModalValue ?? ''}" data-url="${r.dataUrl ?? ''}"`
                        : '';
                    html += `
                        <a href="${r.menuUrl}" class="pilot-card pilot-card--search ${r.colorClass}${r.isActionTile ? ' action-tile' : ''}" style="--i:${i};"${actionAttrs}>
                            <div class="icon-wrapper">
                                <img src="${r.imgSrc}" alt="${r.menuName}" width="35"/>
                            </div>
                            <h6>${r.menuName}</h6>
                            <div class="cat-label">${r.catName}</div>
                        </a>
                    `;
                });
                html += '</div>';
                $resultsEl.html(html).removeClass('d-none');
            }
        });


        // .rct_td click event
        $(document).on('click', '.rct_td', function(e) {
            // Don't navigate if clicking on delete button
            if ($(e.target).closest('.delete-tour-btn').length) {
                return;
            }
            const tourId = $(this).data('id');
            window.location.href = '<?php echo base_url(); ?>dashboard2/tour_search/itinerary_print?tour_id=' + tourId;
        });

        // Delete tour button click event
        $(document).on('click', '.delete-tour-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const tourId = $(this).data('tour-id');
            const manualId = $(this).data('manual-id');

            Swal.fire({
                title: 'Delete Tour?',
                html: `Are you sure you want to delete tour <strong>${manualId}</strong>?<br><small class="text-muted">Tour ID: ${tourId}</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= base_url('tour/tour_dashboard/delete') ?>',
                        method: 'POST',
                        data: {
                            tour_id: tourId
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            $('#loadingModal').modal('show');
                        },
                        success: function(response) {
                            $('#loadingModal').modal('hide');
                            if (response.status === 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message || 'Tour has been deleted successfully.',
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Failed to delete tour.',
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#loadingModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while deleting the tour.',
                            });
                        }
                    });
                }
            });
        });

        // Mark complete button click event
        $(document).on('click', '.mark-complete-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const tourId = $(this).data('tour-id');
            const manualId = $(this).data('manual-id');

            Swal.fire({
                title: 'Mark as Complete?',
                html: `Mark tour <strong>${manualId}</strong> as complete?<br><small class="text-muted">It will be removed from this dashboard.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Mark Complete!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= base_url('tour/tour_dashboard/mark_complete') ?>',
                        method: 'POST',
                        data: { tour_id: tourId },
                        dataType: 'json',
                        beforeSend: function() {
                            $('#loadingModal').modal('show');
                        },
                        success: function(response) {
                            $('#loadingModal').modal('hide');
                            if (response.status === 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Completed!',
                                    text: response.message || 'Tour marked as complete.',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    loadRecentBookings(currentPage, $('#recent_bookings_search').val());
                                });
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'Failed to mark tour as complete.' });
                            }
                        },
                        error: function() {
                            $('#loadingModal').modal('hide');
                            Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred. Please try again.' });
                        }
                    });
                }
            });
        });

        // Logout function
        function logout() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to logout from the system?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#00a65a',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Logout!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?php echo base_url(); ?>logout",
                        method: "GET",
                        dataType: null,
                        success: function (response) {
                            if (response.status == 200) {
                                let timerInterval;
                                Swal.fire({
                                    title: "Please Wait...",
                                    html: response.msg,
                                    timer: 2000,
                                    timerProgressBar: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    },
                                    willClose: () => {
                                        clearInterval(timerInterval);
                                    }
                                }).then((result) => {
                                    localStorage.removeItem('previousNotifications');
                                    if (result.dismiss === Swal.DismissReason.timer) {
                                        var is_admin = "<?php echo session()->get('is_admin'); ?>"
                                        if (is_admin == 1) {
                                            window.location.href = "<?php echo base_url() ?>admin";
                                        } else {
                                            window.location.href = "<?php echo base_url() ?>";
                                        }
                                    }
                                });
                            }
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                title: 'Error!',
                                text: errorThrown,
                                icon: 'error',
                                confirmButtonColor: '#00a65a',
                            });
                        }
                    });
                }
            });
        }

        // Function to load tours pending driver allocation badge count only (for tile dashboard)
        <?php if (session()->get('group_id') == 1 || session()->get('group_id') == 23): ?>
        function loadToursPendingNotificationsTile() {
            $.ajax({
                url: "<?php echo base_url(); ?>dashboard2/transport/driver_allow/available_tours",
                method: "GET",
                dataType: "json",
                success: function (response) {
                    if (response.status === 200) {
                        const count = response.data ? response.data.length : 0;

                        // Show/hide badge
                        if (count > 0) {
                            $('#tours_pending_count_tile').removeClass('d-none').text(count);
                        } else {
                            $('#tours_pending_count_tile').addClass('d-none');
                        }
                    } else {
                        $('#tours_pending_count_tile').addClass('d-none');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('Error loading tours pending count:', errorThrown);
                    $('#tours_pending_count_tile').addClass('d-none');
                }
            });
        }

        // Function to load tours with allocated drivers badge count (for tile dashboard)
        function loadToursAllocatedNotificationsTile() {
            $.ajax({
                url: "<?php echo base_url(); ?>dashboard2/transport/driver_allow/allocated_tours_json",
                method: "GET",
                dataType: "json",
                success: function (response) {
                    if (response.status === 200) {
                        const count = response.data ? response.data.length : 0;

                        // Show/hide badge
                        if (count > 0) {
                            $('#tours_allocated_count_tile').removeClass('d-none').text(count);
                        } else {
                            $('#tours_allocated_count_tile').addClass('d-none');
                        }
                    } else {
                        $('#tours_allocated_count_tile').addClass('d-none');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('Error loading allocated tours count:', errorThrown);
                    $('#tours_allocated_count_tile').addClass('d-none');
                }
            });
        }

        // Load count on page load and refresh every 20 seconds
        $(document).ready(function() {
            loadToursPendingNotificationsTile();
            loadToursAllocatedNotificationsTile();
            setInterval(loadToursPendingNotificationsTile, 20000);
            setInterval(loadToursAllocatedNotificationsTile, 20000);
        });
        <?php endif; ?>

        <?php if (session()->get('group_id') == 1): ?>
        // Recent Bookings Pagination
        let currentPage = 1;
        let totalPages = 1;

        function loadRecentBookings(page = 1, search = '') {
            $.ajax({
                url: '<?= base_url('dashboard2/getRecentTours') ?>',
                method: 'GET',
                data: {
                    page: page,
                    search: search
                },
                dataType: 'json',
                beforeSend: function() {
                    $('#recent_bookings_tbody').html(`
                        <tr>
                            <td colspan="10" class="text-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    `);
                },
                success: function(response) {
                    if (response.status === 200 && response.data) {
                        const tours = response.data;
                        const pagination = response.pagination;
                        currentPage = pagination.current_page;
                        totalPages = pagination.total_pages;

                        // Update table body
                        let html = '';
                        if (tours.length === 0) {
                            html = `
                                <tr>
                                    <td colspan="10" class="text-center text-muted">
                                        <i class="bi bi-search me-2"></i> No recent bookings found.
                                    </td>
                                </tr>
                            `;
                        } else {
                            tours.forEach((tour, index) => {
                                // Format dates
                                const startDate = tour.start_date ? new Date(tour.start_date).toISOString().split('T')[0] : '-';
                                const endDate = tour.end_date ? new Date(tour.end_date).toISOString().split('T')[0] : '-';

                                // Agent Invoice
                                const agentInvoiceClass = tour.agent_invoice_no ? 'btn-success' : 'btn-danger';
                                const agentInvoiceIcon = tour.agent_invoice_no ? '✓' : '✗';
                                const agentInvoiceBtn = `<button class="btn ${agentInvoiceClass} btn-sm" style="min-width: 40px;">${agentInvoiceIcon}</button>`;

                                // Hotel counts
                                const hotelCountNew = tour.hotel_count_new || 0;

                                // Hotel Confirmation
                                const bookingConfirmedCnt = tour.booking_confirmed_cnt || 0;
                                const confirmClass = (bookingConfirmedCnt == hotelCountNew && hotelCountNew > 0) ? 'btn-success' : 'btn-info';
                                const confirmBtn = `<button class="btn ${confirmClass} btn-sm" style="min-width: 50px;">${bookingConfirmedCnt}/${hotelCountNew}</button>`;

                                // Hotel Payment
                                const hotelPaymentCnt = tour.hotel_payment_cnt || 0;
                                const hotelPaymentClass = (hotelPaymentCnt == hotelCountNew && hotelCountNew > 0) ? 'btn-success' : 'btn-info';
                                const hotelPaymentBtn = `<button class="btn ${hotelPaymentClass} btn-sm" style="min-width: 50px;">${hotelPaymentCnt}/${hotelCountNew}</button>`;

                                // Transport Advance
                                const transportAdvanceClass = (tour.transport_advance == 1) ? 'btn-success' : 'btn-danger';
                                const transportAdvanceIcon = (tour.transport_advance == 1) ? '✓' : '✗';
                                const transportAdvanceBtn = `<button class="btn ${transportAdvanceClass} btn-sm" style="min-width: 40px;">${transportAdvanceIcon}</button>`;

                                // Transport Settled
                                const driverSettledClass = (tour.driver_settled == 1) ? 'btn-success' : 'btn-danger';
                                const driverSettledIcon = (tour.driver_settled == 1) ? '✓' : '✗';
                                const driverSettledBtn = `<button class="btn ${driverSettledClass} btn-sm" style="min-width: 40px;">${driverSettledIcon}</button>`;

                                // Passenger count
                                const passengerCount = tour.passenger_count || '-';

                                // Check transport_only and hotel_only flags
                                const transportOnly = tour.transport_only == 1;
                                const hotelOnly = tour.hotel_only == 1;

                                // Disabled styles
                                const disabledStyle = 'background-color: #e9ecef; opacity: 0.5;';

                                // Hotel columns - disable if transport_only
                                const hotelConfirmCol = transportOnly
                                    ? `<td class="text-center" style="${disabledStyle}"><button class="btn ${confirmClass} btn-sm" style="min-width: 50px;" disabled>${bookingConfirmedCnt}/${hotelCountNew}</button></td>`
                                    : `<td class="text-center">${confirmBtn}</td>`;
                                const hotelPaymentCol = transportOnly
                                    ? `<td class="text-center" style="${disabledStyle}"><button class="btn ${hotelPaymentClass} btn-sm" style="min-width: 50px;" disabled>${hotelPaymentCnt}/${hotelCountNew}</button></td>`
                                    : `<td class="text-center">${hotelPaymentBtn}</td>`;

                                // Transport columns - disable if hotel_only
                                const transportAdvanceCol = hotelOnly
                                    ? `<td class="text-center" style="${disabledStyle}"><button class="btn ${transportAdvanceClass} btn-sm" style="min-width: 40px;" disabled>${transportAdvanceIcon}</button></td>`
                                    : `<td class="text-center">${transportAdvanceBtn}</td>`;
                                const transportSettledCol = hotelOnly
                                    ? `<td class="text-center" style="${disabledStyle}"><button class="btn ${driverSettledClass} btn-sm" style="min-width: 40px;" disabled>${driverSettledIcon}</button></td>`
                                    : `<td class="text-center">${driverSettledBtn}</td>`;

                                // Check if all steps complete (for Mark Complete button)
                                const hotelDone = transportOnly || (bookingConfirmedCnt == hotelCountNew && hotelCountNew > 0 && hotelPaymentCnt == hotelCountNew);
                                const transportDone = hotelOnly || (tour.transport_advance == 1 && tour.driver_settled == 1);
                                const allComplete = tour.agent_invoice_no && hotelDone && transportDone;

                                // Check if tour is cancelled
                                const isCancelled = tour.tour_cancel == 1;
                                const statusColumns = isCancelled
                                    ? `<td colspan="5" class="text-center"><span class="badge bg-danger cancelled-badge">CANCELLED</span></td>`
                                    : `${hotelConfirmCol}
                                       ${hotelPaymentCol}
                                       ${transportAdvanceCol}
                                       ${transportSettledCol}
                                       <td class="text-center">
                                           ${allComplete ? `<button class="btn btn-success btn-sm mark-complete-btn" title="Mark Complete" data-tour-id="${tour.tour_id}" data-manual-id="${tour.manual_id}"><i class="bi bi-check-circle-fill"></i></button>` : ''}
                                           ${!tour.agent_invoice_no ? `<button class="btn btn-danger btn-sm delete-tour-btn" title="Delete Tour" data-tour-id="${tour.tour_id}" data-manual-id="${tour.manual_id}"><i class="bi bi-trash"></i></button>` : ''}
                                       </td>`;

                                html += `
                                    <tr class="rct_td" data-id="${tour.tour_id}" style="cursor: pointer;">
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong class="text-primary">${tour.manual_id}</strong>

                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong>${tour.consignee}</strong>
                                                <small class="text-muted">Agent: ${tour.agent_name}</small>
                                                <small class="text-muted">Booking Operator: ${tour.create_user}</small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">${passengerCount}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <small>${startDate}</small>
                                                <small>${endDate}</small>
                                            </div>
                                        </td>
                                        <td class="text-center">${agentInvoiceBtn}</td>
                                        ${statusColumns}
                                    </tr>
                                `;
                            });
                        }
                        $('#recent_bookings_tbody').html(html);

                        // Update pagination info
                        const showingStart = tours.length > 0 ? ((currentPage - 1) * pagination.per_page) + 1 : 0;
                        const showingEnd = Math.min(currentPage * pagination.per_page, pagination.total);
                        $('#showing_start').text(showingStart);
                        $('#showing_end').text(showingEnd);
                        $('#total_records').text(pagination.total);

                        // Render pagination buttons
                        renderPagination(pagination);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    $('#recent_bookings_tbody').html(`
                        <tr>
                            <td colspan="10" class="text-center text-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i> Error loading bookings
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function renderPagination(pagination) {
            const paginationEl = $('#recent_bookings_pagination');
            paginationEl.empty();

            if (pagination.total_pages <= 1) return;

            // Previous button
            paginationEl.append(`
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            `);

            // Page numbers
            const maxButtons = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
            let endPage = Math.min(pagination.total_pages, startPage + maxButtons - 1);

            if (endPage - startPage < maxButtons - 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }

            if (startPage > 1) {
                paginationEl.append(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
                if (startPage > 2) {
                    paginationEl.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                paginationEl.append(`
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }

            if (endPage < pagination.total_pages) {
                if (endPage < pagination.total_pages - 1) {
                    paginationEl.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
                }
                paginationEl.append(`<li class="page-item"><a class="page-link" href="#" data-page="${pagination.total_pages}">${pagination.total_pages}</a></li>`);
            }

            // Next button
            paginationEl.append(`
                <li class="page-item ${currentPage === pagination.total_pages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            `);

            // Attach click handlers
            paginationEl.find('a.page-link').on('click', function(e) {
                e.preventDefault();
                const page = parseInt($(this).data('page'));
                const search = $('#recent_bookings_search').val() || '';
                if (page && page !== currentPage && page >= 1 && page <= pagination.total_pages) {
                    loadRecentBookings(page, search);
                }
            });
        }

        // Initialize pagination on page load
        $(document).ready(function() {
            const totalRecords = parseInt($('#total_records').text());
            if (totalRecords > 10) {
                loadRecentBookings(1);
            }

            // Search input handler
            let searchTimeout;
            $('#recent_bookings_search').on('keyup', function() {
                clearTimeout(searchTimeout);
                const search = $(this).val();
                searchTimeout = setTimeout(function() {
                    loadRecentBookings(1, search);
                }, 500); // 500ms delay for debouncing
            });
        });
        <?php endif; ?>
    </script>

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="requestModalLabel">Request Actions</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="request_tour_id">
                    <input type="hidden" id="request_type_hidden">
                    <input type="hidden" id="inv_num">

                    <div class="mb-3">
                        <label class="form-label">Request Type</label>
                        <div>
                            <h6 id="request_type_display"></h6>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="requestReason" class="form-label">Reason</label>
                        <textarea class="form-control" id="requestReason" rows="4" placeholder="Please provide a reason for your request..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitRequest">Submit Request</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle request modal opening
        $(document).on('click', '.request-btn', function(e) {
            e.preventDefault();
            var tourId = $(this).data('tour-id');
            var requestType = $(this).data('request-type');
            var inv = $(this).data('inv-no');

            $('#request_tour_id').val(tourId);
            $('#inv_num').val(inv);
            $('#request_type_hidden').val(requestType);
            $('#request_type_display').text(requestType === 'amendment' ? 'Amendment Request' : 'Cancellation Request');
            $('#requestReason').val('');
            $('#requestModal').modal('show');
        });

        // Handle request submission
        $(document).on('click', '#submitRequest', function() {
            var requestType = $('#request_type_hidden').val();
            var reason = $('#requestReason').val();
            var tour_id = $('#request_tour_id').val();
            var invno = $('#inv_num').val();

            if (!reason.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Warning',
                    text: 'Please provide a reason for your request.',
                });
                return;
            }

            var flag = requestType === 'cancel' ? 2 : 5;

            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to submit a " + (requestType === 'cancel' ? 'cancellation' : 'amendment') + " request.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, submit it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "GET",
                        url: "<?php echo base_url(); ?>accounts/debtor_invoice/cancellation_request",
                        data: {
                            id: invno,
                            tour_id: tour_id,
                            status: 0,
                            type: flag,
                            reason: reason
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        $('#requestModal').modal('hide');
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: response.message,
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                            });
                        }
                    });
                }
            });
        });
    </script>
</body>


</html>