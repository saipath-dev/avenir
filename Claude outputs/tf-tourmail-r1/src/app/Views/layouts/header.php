<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="index.html" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="<?= base_url() ?><?= base_url() ?>assets/images/logo-sm.png" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="<?= base_url() ?>assets/images/logo-dark.png" alt="" height="17">
                        </span>
                    </a>

                    <a href="index.html" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="<?= base_url() ?>assets/images/logo-sm.png" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="<?= base_url() ?>assets/images/logo-light.png" alt="" height="17">
                        </span>
                    </a>
                </div>

                <!-- <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger"
                        id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button> -->

                <!-- Back Button -->
                <div class="ms-1 header-item">
                    <button type="button" class="btn btn-sm btn-danger d-flex align-items-center gap-1 text-decoration-none"
                            onclick="window.history.back()"
                            style="border-radius: 20px; font-size: 1rem; padding: 4px 10px; font-weight: 500;">
                        <i class="ri-arrow-left-line"></i>
                        <span>Back</span>
                    </button>
                </div>

                <!-- Ground Handling Button -->
                <div class="ms-1 header-item">
                    <a href="<?= base_url('dashboard2/tiles') ?>"
                       class="btn btn-sm d-flex align-items-center gap-1 text-decoration-none"
                       style="border-radius: 20px; font-size: 1.2rem; padding: 4px 10px; font-weight: 500; background-color: #1a237e; color: #fff;">
                        <i class="ri-dashboard-2-line"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <!-- App Search-->
                <!-- <form class="app-search d-none d-md-block">
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search..." autocomplete="off" id="search-options" value="">
                        <span class="mdi mdi-magnify search-widget-icon"></span>
                        <span class="mdi mdi-close-circle search-widget-icon search-widget-icon-close d-none" id="search-close-options"></span>
                    </div>
                    <div class="dropdown-menu dropdown-menu-lg" id="search-dropdown">
                        <div data-simplebar style="max-height: 320px;">

                            <div class="dropdown-header">
                                <h6 class="text-overflow text-muted mb-0 text-uppercase">Recent Searches</h6>
                            </div>

                            <div class="dropdown-item bg-transparent text-wrap">
                                <a href="index.html" class="btn btn-soft-secondary btn-sm rounded-pill">how to setup <i class="mdi mdi-magnify ms-1"></i></a>
                                <a href="index.html" class="btn btn-soft-secondary btn-sm rounded-pill">buttons <i class="mdi mdi-magnify ms-1"></i></a>
                            </div>

                            <div class="dropdown-header mt-2">
                                <h6 class="text-overflow text-muted mb-1 text-uppercase">Pages</h6>
                            </div>


                            <a href="javascript:void(0);" class="dropdown-item notify-item">
                                <i class="ri-bubble-chart-line align-middle fs-18 text-muted me-2"></i>
                                <span>Analytics Dashboard</span>
                            </a>


                            <a href="javascript:void(0);" class="dropdown-item notify-item">
                                <i class="ri-lifebuoy-line align-middle fs-18 text-muted me-2"></i>
                                <span>Help Center</span>
                            </a>


                            <a href="javascript:void(0);" class="dropdown-item notify-item">
                                <i class="ri-user-settings-line align-middle fs-18 text-muted me-2"></i>
                                <span>My account settings</span>
                            </a>


                            <div class="dropdown-header mt-2">
                                <h6 class="text-overflow text-muted mb-2 text-uppercase">Members</h6>
                            </div>

                            <div class="notification-list">

                                <a href="javascript:void(0);" class="dropdown-item notify-item py-2">
                                    <div class="d-flex">
                                        <img src="<?//= base_url() ?>assets/images/users/avatar-2.jpg" class="me-3 rounded-circle avatar-xs" alt="user-pic">
                                        <div class="flex-grow-1">
                                            <h6 class="m-0">Angela Bernier</h6>
                                            <span class="fs-11 mb-0 text-muted">Manager</span>
                                        </div>
                                    </div>
                                </a>

                                <a href="javascript:void(0);" class="dropdown-item notify-item py-2">
                                    <div class="d-flex">
                                        <img src="<?//= $profile_picture; ?>" class="me-3 rounded-circle avatar-xs" alt="user-pic">
                                        <div class="flex-grow-1">
                                            <h6 class="m-0">David Grasso</h6>
                                            <span class="fs-11 mb-0 text-muted">Web Designer</span>
                                        </div>
                                    </div>
                                </a>

                                <a href="javascript:void(0);" class="dropdown-item notify-item py-2">
                                    <div class="d-flex">
                                        <img src="<?//= base_url() ?>assets/images/users/avatar-5.jpg" class="me-3 rounded-circle avatar-xs" alt="user-pic">
                                        <div class="flex-grow-1">
                                            <h6 class="m-0">Mike Bunch</h6>
                                            <span class="fs-11 mb-0 text-muted">React Developer</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="text-center pt-3 pb-1">
                            <a href="pages-search-results.html" class="btn btn-primary btn-sm">View All Results <i class="ri-arrow-right-line ms-1"></i></a>
                        </div>
                    </div>
                </form> -->
            </div>

            <div class="d-flex align-items-center">

                <div class="dropdown d-md-none topbar-head-dropdown header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                            id="page-header-search-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false">
                        <i class="bx bx-search fs-22"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                         aria-labelledby="page-header-search-dropdown">
                        <form class="p-3">
                            <div class="form-group m-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search ..."
                                           aria-label="Recipient's username">
                                    <button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button"
                            class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode">
                        <i class='bx bx-moon fs-22'></i>
                    </button>
                </div>

                <?php // Messages dropdown - commented out per request ?>
                <?php if (false && session()->get('user_role') != 1): ?>
                <div class="dropdown topbar-head-dropdown ms-1 header-item" id="messageDropdown">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                            id="page-header-message-dropdown" data-bs-toggle="dropdown"
                            data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                        <i class='bx bx-chat fs-22'></i>
                        <span id="message_unread_count" class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger d-none">0<span
                                    class="visually-hidden">unread messages</span></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                         aria-labelledby="page-header-message-dropdown">

                        <div class="dropdown-head bg-primary bg-pattern rounded-top">
                            <div class="p-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="m-0 fs-16 fw-semibold text-white"> Messages </h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-content position-relative" id="messageItemsTabContent">
                            <div class="tab-pane fade show active py-2 ps-2" id="messages-content" role="tabpanel">
                                <div data-simplebar style="max-height: 300px;" class="pe-2" id="message-list">
                                    <div class="text-center p-3" id="message-loading">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center pt-3 pb-1">
                                    <a href="<?= base_url('chat') ?>" class="btn btn-primary btn-sm">View All Messages <i class="ri-arrow-right-line ms-1"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php // Driver Allocation Pending dropdown - commented out per request ?>
                <?php if (false && session()->get('user_role') != 1): ?>
                <div class="dropdown topbar-head-dropdown ms-1 header-item" id="driverNotificationDropdown">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                            id="page-header-driver-dropdown" data-bs-toggle="dropdown"
                            data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                        <i class='bx bx-car fs-22'></i>
                        <span id="driver_unallocated_count" class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-warning d-none">0<span
                                    class="visually-hidden">unallocated drivers</span></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                         aria-labelledby="page-header-driver-dropdown">

                        <div class="dropdown-head bg-warning bg-pattern rounded-top">
                            <div class="p-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="m-0 fs-16 fw-semibold text-white"> Driver Allocation Pending </h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-content position-relative" id="driverNotificationContent">
                            <div class="py-2 ps-2" role="tabpanel">
                                <div data-simplebar style="max-height: 300px;" class="pe-2" id="driver-notification-list">
                                    <div class="text-center p-3" id="driver-loading">
                                        <div class="spinner-border text-warning" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center pt-3 pb-1">
                                    <a href="<?= base_url('dashboard2/transport/driver_allow/tour_transport') ?>" class="btn btn-warning btn-sm">View All Tours <i class="ri-arrow-right-line ms-1"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?= view('layouts/_mail_alert') ?><?php // tf-tourmail-r1: the red "Mails not sent" alert ?>
                <div class="dropdown topbar-head-dropdown ms-1 header-item" id="stockNotificationDropdown">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                            id="page-header-stock-dropdown" data-bs-toggle="dropdown"
                            data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                        <i class='bx bx-package fs-22'></i>
                        <span id="stock_item_count" class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-info d-none">0<span
                                    class="visually-hidden">stock items</span></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                         aria-labelledby="page-header-stock-dropdown">

                        <div class="dropdown-head bg-info bg-pattern rounded-top">
                            <div class="p-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="m-0 fs-16 fw-semibold text-white"> Stock Inventory </h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-content position-relative" id="stockNotificationContent">
                            <div class="py-2 ps-2" role="tabpanel">
                                <div data-simplebar style="max-height: 300px;" class="pe-2" id="stock-notification-list">
                                    <div class="text-center p-3" id="stock-loading">
                                        <div class="spinner-border text-info" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center pt-3 pb-1">
                                    <a href="<?= base_url('inventory/stock') ?>" class="btn btn-info btn-sm">View All Stock <i class="ri-arrow-right-line ms-1"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (session()->get('group_id') == 1 || session()->get('group_id') == 23): ?>
                <?php // Pending Drivers button - commented out per request ?>
                <?php if (false): ?>
                <div class="ms-1 header-item" id="toursPendingNotification">
                    <a href="<?= base_url('dashboard2/transport/driver_allow/pending_tours') ?>"
                       class="btn btn-sm btn-info d-flex align-items-center gap-2 text-decoration-none">
                        <span>Pending Drivers</span>
                        <span id="tours_pending_count" class="badge bg-white text-info d-none">0</span>
                    </a>
                </div>
                <?php endif; ?>
                <?php // Allocated Drivers button - commented out per request ?>
                <?php if (false): ?>
                <div class="ms-1 header-item" id="toursAllocatedNotification">
                    <a href="<?= base_url('dashboard2/transport/driver_allow/allocated_tours') ?>"
                       class="btn btn-sm btn-success d-flex align-items-center gap-2 text-decoration-none">
                        <span>Allocated Drivers</span>
                        <span id="tours_allocated_count" class="badge bg-white text-success d-none">0</span>
                    </a>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (session()->get('user_role') != 1): ?>
                <div class="dropdown topbar-head-dropdown ms-1 header-item" id="notificationDropdown">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                            id="page-header-mail-dropdown" data-bs-toggle="dropdown"
                            data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                        <i class='bx bx-envelope fs-22'></i>
                        <span id="unread_count" class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger d-none">0<span
                                    class="visually-hidden">unread messages</span></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                         aria-labelledby="page-header-notifications-dropdown">

                        <div class="dropdown-head bg-primary bg-pattern rounded-top">
                            <div class="p-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="m-0 fs-16 fw-semibold text-white"> Emails </h6>
                                    </div>
                                    <div class="col-auto dropdown-tabs">
                                        <!--<span class="badge bg-light-subtle text-body fs-13"> 4 New</span>-->
                                    </div>
                                </div>
                            </div>

                            <div class="px-2 pt-2">
                                <ul class="nav nav-tabs dropdown-tabs nav-tabs-custom" data-dropdown-tabs="true"
                                    id="notificationItemsTab" role="tablist">
                                    <li class="nav-item waves-effect waves-light">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#all-noti-tab" role="tab"
                                           aria-selected="true">
                                            All (4)
                                        </a>
                                    </li>
                                    <li class="nav-item waves-effect waves-light">
                                        <a class="nav-link" data-bs-toggle="tab" href="#messages-tab" role="tab"
                                           aria-selected="false">
                                            Tour Mails
                                        </a>
                                    </li>
                                    <li class="nav-item waves-effect waves-light">
                                        <a class="nav-link" data-bs-toggle="tab" href="#alerts-tab" role="tab"
                                           aria-selected="false">
                                            Alerts
                                        </a>
                                    </li>
                                </ul>
                            </div>

                        </div>

                        <div class="tab-content position-relative" id="notificationItemsTabContent">
                            <div class="tab-pane fade show active py-2 ps-2" id="all-noti-tab" role="tabpanel">
                                <div data-simplebar style="max-height: 300px;" class="pe-2">
                                    <div class="text-reset notification-item d-block dropdown-item position-relative">
                                        <div class="d-flex">
                                            <div class="avatar-xs me-3 flex-shrink-0">
                                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-16">
                                                    <i class="bx bx-badge-check"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="#!" class="stretched-link">
                                                    <h6 class="mt-0 mb-2 lh-base">Your <b>Elite</b> author Graphic
                                                        Optimization <span class="text-secondary">reward</span> is
                                                        ready!
                                                    </h6>
                                                </a>
                                                <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                    <span><i class="mdi mdi-clock-outline"></i> Just 30 sec ago</span>
                                                </p>
                                            </div>
                                            <div class="px-2 fs-15">
                                                <div class="form-check notification-check">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                           id="all-notification-check01">
                                                    <label class="form-check-label"
                                                           for="all-notification-check01"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="my-3 text-center view-all">
                                        <button type="button" class="btn btn-soft-success waves-effect waves-light">View
                                            All Notifications <i class="ri-arrow-right-line align-middle"></i></button>
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane fade py-2 ps-2" id="messages-tab" role="tabpanel"
                                 aria-labelledby="messages-tab">
                                <div data-simplebar style="max-height: 300px;" class="pe-2">
                                    <div class="text-reset notification-item d-block dropdown-item">
                                        <div class="d-flex">
                                            <img src="<?= $profile_picture; ?>"
                                                 class="me-3 rounded-circle avatar-xs" alt="user-pic">
                                            <div class="flex-grow-1">
                                                <a href="#!" class="stretched-link">
                                                    <h6 class="mt-0 mb-1 fs-13 fw-semibold">James Lemire</h6>
                                                </a>
                                                <div class="fs-13 text-muted">
                                                    <p class="mb-1">We talked about a project on linkedin.</p>
                                                </div>
                                                <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                    <span><i class="mdi mdi-clock-outline"></i> 30 min ago</span>
                                                </p>
                                            </div>
                                            <div class="px-2 fs-15">
                                                <div class="form-check notification-check">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                           id="messages-notification-check01">
                                                    <label class="form-check-label"
                                                           for="messages-notification-check01"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="my-3 text-center view-all">
                                        <button type="button" class="btn btn-soft-success waves-effect waves-light">View
                                            All Messages <i class="ri-arrow-right-line align-middle"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade p-4" id="alerts-tab" role="tabpanel"
                                 aria-labelledby="alerts-tab"></div>

                            <div class="notification-actions" id="notification-actions">
                                <div class="d-flex text-muted justify-content-center">
                                    Select
                                    <div id="select-content" class="text-body fw-semibold px-1">0</div>
                                    Result
                                    <button type="button" class="btn btn-link link-danger p-0 ms-3"
                                            data-bs-toggle="modal" data-bs-target="#removeNotificationModal">Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle header-profile-user"
                                 src="<?= $profile_picture; ?>" alt="Header Avatar">
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text"> <?= $sidebar_employee_name ?></span>
                                <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text"> <?= $sidebar_employee_name_profile_name ?></span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <h6 class="dropdown-header">Welcome <?= $sidebar_employee_name ?>!</h6>
                        <a class="dropdown-item" href="<?= base_url('my_profile') ?>"><i
                                    class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span
                                    class="align-middle">Profile</span></a>
                        <a id="logoutBtn" class="dropdown-item text-danger" href="#"><i
                                    class="mdi mdi-logout fs-16 align-middle me-1 text-danger"></i> <span
                                    class="align-middle" data-key="t-logout">Logout</span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
    /* Mobile optimizations for header */
    @media (max-width: 767px) {
        /* Hide text labels on mobile for driver notification buttons */
        #toursPendingNotification .btn span:not(.badge),
        #toursAllocatedNotification .btn span:not(.badge) {
            display: none;
        }

        /* Make buttons more compact */
        #toursPendingNotification .btn,
        #toursAllocatedNotification .btn {
            padding: 6px 10px;
            font-size: 0.75rem;
            min-width: auto;
        }

        /* Show only badges with icons */
        #toursPendingNotification .btn::before {
            content: "\F0186"; /* Bootstrap icon for car/driver */
            font-family: 'bootstrap-icons';
            margin-right: 4px;
        }

        #toursAllocatedNotification .btn::before {
            content: "\F26A"; /* Bootstrap icon for check-circle */
            font-family: 'bootstrap-icons';
            margin-right: 4px;
        }

        /* Adjust badge positioning */
        #toursPendingNotification .badge,
        #toursAllocatedNotification .badge {
            font-size: 0.65rem;
            padding: 2px 6px;
        }

        /* Reduce spacing between header items */
        .header-item {
            margin-left: 0.25rem !important;
        }

        /* Make notification dropdowns more compact */
        .topbar-head-dropdown .btn {
            padding: 6px;
        }

        /* Profile dropdown optimization */
        .topbar-user .header-profile-user {
            width: 30px;
            height: 30px;
        }

        /* Hide profile name on very small screens */
        .user-name-text,
        .user-name-sub-text {
            display: none !important;
        }
    }

    /* Tablet optimization */
    @media (min-width: 768px) and (max-width: 991px) {
        #toursPendingNotification .btn,
        #toursAllocatedNotification .btn {
            font-size: 0.8rem;
            padding: 8px 12px;
        }
    }

    /* Mobile dropdown optimization */
    @media (max-width: 767px) {
        /* Notification dropdowns */
        .dropdown-menu-lg {
            width: calc(100vw - 1rem) !important;
            max-width: 350px;
            font-size: 0.85rem;
        }

        .dropdown-menu-lg .dropdown-header h6 {
            font-size: 0.9rem;
        }

        .dropdown-menu-lg .notification-item,
        .dropdown-menu-lg .message-item,
        .dropdown-menu-lg .driver-notification-item {
            padding: 0.5rem;
        }

        .dropdown-menu-lg .notification-item h6,
        .dropdown-menu-lg .message-item h6,
        .dropdown-menu-lg .driver-notification-item h6 {
            font-size: 0.8rem;
        }

        .dropdown-menu-lg .fs-13 {
            font-size: 0.75rem !important;
        }

        .dropdown-menu-lg .fs-11 {
            font-size: 0.7rem !important;
        }

        .dropdown-menu-lg .avatar-xs {
            width: 28px;
            height: 28px;
        }

        .dropdown-menu-lg .avatar-title {
            font-size: 0.85rem;
        }

        /* Hamburger menu button */
        .topnav-hamburger {
            padding: 8px !important;
        }

        /* Logo optimization */
        .navbar-brand-box .logo-lg img {
            height: 14px !important;
        }

        /* Dark mode toggle */
        .light-dark-mode {
            padding: 6px !important;
            width: 32px;
            height: 32px;
        }

        .light-dark-mode i {
            font-size: 1.1rem !important;
        }
    }
</style>

<!-- removeNotificationModal -->
<div id="removeNotificationModal" class="modal fade zoomIn" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        id="NotificationModalbtn-close"></button>
            </div>
            <div class="modal-body">
                <div class="mt-2 text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                               colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                    <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                        <h4>Are you sure ?</h4>
                        <p class="text-muted mx-4 mb-0">Are you sure you want to remove this Notification ?</p>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                    <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn w-sm btn-danger" id="delete-notification">Yes, Delete It!</button>
                </div>
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<script src="<?= base_url(); ?>assets/jQuery/jQuery-2.1.4.min.js"></script>
<script>

    // logout btn on click - logout
    $('#logoutBtn').on('click', function () {
        logout();
    });

    // console.log("Hello World")

    // Function to load conversations
    function loadConversations() {
        $.ajax({
            url: "<?php echo base_url(); ?>chat/get_recent_conversations_for_header",
            method: "GET",
            dataType: "json",
            success: function (response) {
            console.log('Messages response:', response);

            if (response.status === 200) {
                // Show/hide unread badge
                if (response.total_unread > 0) {
                    $('#message_unread_count').removeClass('d-none');
                    $('#message_unread_count').text(response.total_unread);
                } else {
                    $('#message_unread_count').addClass('d-none');
                }

                // Hide loading spinner
                $('#message-loading').hide();

                // Clear existing content
                $('#message-list .message-item').remove();

                // Function to create HTML for a conversation item
                function createConversationItem(conversation) {
                    const lastMessage = conversation.last_message || {};
                    const messageText = lastMessage.message || 'No messages yet';
                    const messageTime = lastMessage.time ? new Date(lastMessage.time).toLocaleString() : '';
                    const agentName = conversation.agent_name || 'Unknown Agent';
                    const conversationTitle = conversation.title || 'Untitled Conversation';
                    
                    return `
                    <div class="text-reset message-item d-block dropdown-item position-relative">
                        <div class="d-flex">
                            <div class="avatar-xs me-3 flex-shrink-0">
                                <span class="avatar-title bg-success-subtle text-success rounded-circle fs-16">
                                    <i class="bx bx-chat"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <a href="<?php echo base_url(); ?>chat?conversation=${conversation.id}&agent=${conversation.agent}&mark_read=1" class="stretched-link">
                                    <h6 class="mt-0 mb-1 fs-13 fw-semibold">${conversationTitle}</h6>
                                </a>
                                <div class="fs-13 text-muted">
                                    <p class="mb-1">${messageText.length > 50 ? messageText.substring(0, 50) + '...' : messageText}</p>
                                    <small class="text-muted">with ${agentName}</small>
                                </div>
                                <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                    <span><i class="mdi mdi-clock-outline"></i> ${messageTime}</span>
                                </p>
                            </div>
                            ${conversation.unread_count > 0 ? `<div class="px-2 fs-11"><span class="badge bg-danger rounded-pill">${conversation.unread_count}</span></div>` : ''}
                        </div>
                    </div>
                `;
                }

                // Add conversations to the list
                if (response.conversations.length > 0) {
                    response.conversations.forEach(function(conversation) {
                        const conversationItem = createConversationItem(conversation);
                        $('#message-list').append(conversationItem);
                    });
                } else {
                    $('#message-list').append(`
                        <div class="text-center p-3 message-item">
                            <p class="text-muted mb-0">No recent conversations</p>
                        </div>
                    `);
                }
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Error loading messages:', errorThrown);
            $('#message-loading').hide();
            $('#message-list').append(`
                <div class="text-center p-3 message-item">
                    <p class="text-danger mb-0">Failed to load messages</p>
                </div>
            `);
        }
        });
    }

    // Load conversations on page load
    // Messages polling - commented out per request
    // loadConversations();

    // Update conversations every 10 seconds
    // setInterval(loadConversations, 10000);

    // Function to load driver allocation notifications
    function loadDriverNotifications() {
        $.ajax({
            url: "<?php echo base_url(); ?>notification/unallocated_driver_tours",
            method: "GET",
            dataType: "json",
            success: function (response) {
                console.log('Driver notifications response:', response);

                if (response.status === 200) {
                    // Show/hide unallocated badge
                    if (response.count > 0) {
                        $('#driver_unallocated_count').removeClass('d-none');
                        $('#driver_unallocated_count').text(response.count);
                    } else {
                        $('#driver_unallocated_count').addClass('d-none');
                    }

                    // Hide loading spinner
                    $('#driver-loading').hide();

                    // Clear existing content
                    $('#driver-notification-list .driver-notification-item').remove();

                    // Function to create HTML for a driver notification item
                    function createDriverNotificationItem(tour) {
                        const startDate = new Date(tour.start_date).toLocaleDateString();
                        const endDate = new Date(tour.end_date).toLocaleDateString();
                        const tourLink = `<?php echo base_url(); ?>dashboard2/transport/driver_allow/tour_transport?tour_id=${tour.tour_id}`;
                        const tourNumber = tour.manual_id || tour.tour_id;

                        return `
                        <div class="text-reset driver-notification-item d-block dropdown-item position-relative">
                            <div class="d-flex">
                                <div class="avatar-xs me-3 flex-shrink-0">
                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-16">
                                        <i class="bx bx-car"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <a href="${tourLink}" class="stretched-link">
                                        <h6 class="mt-0 mb-1 fs-13 fw-semibold">Tour: ${tourNumber}</h6>
                                    </a>
                                    <div class="fs-13 text-muted">
                                        <p class="mb-1"><strong>Client:</strong> ${tour.consignee || 'N/A'}</p>
                                        <p class="mb-1"><strong>Passengers:</strong> ${tour.passenger_count || 0}</p>
                                        <p class="mb-1"><strong>Date:</strong> ${startDate} - ${endDate}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    }

                    // Add driver notifications to the list
                    if (response.tours.length > 0) {
                        response.tours.forEach(function(tour) {
                            const notificationItem = createDriverNotificationItem(tour);
                            $('#driver-notification-list').append(notificationItem);
                        });
                    } else {
                        $('#driver-notification-list').append(`
                            <div class="text-center p-3 driver-notification-item">
                                <p class="text-muted mb-0">No pending driver allocations</p>
                            </div>
                        `);
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Error loading driver notifications:', errorThrown);
                $('#driver-loading').hide();
                $('#driver-notification-list').append(`
                    <div class="text-center p-3 driver-notification-item">
                        <p class="text-danger mb-0">Failed to load notifications</p>
                    </div>
                `);
            }
        });
    }

    // Load driver notifications on page load
    // Driver Allocation Pending polling - commented out per request
    // loadDriverNotifications();

    // Update driver notifications every 20 seconds
    // setInterval(loadDriverNotifications, 20000);

    // Function to load stock inventory notifications
    function loadStockNotifications() {
        $.ajax({
            url: "<?php echo base_url(); ?>inventory/stock/header_stock_data",
            method: "GET",
            dataType: "json",
            success: function (response) {
                console.log('Stock notifications response:', response);

                if (response.status === 200) {
                    // Show/hide badge with total item count
                    if (response.count > 0) {
                        $('#stock_item_count').removeClass('d-none');
                        $('#stock_item_count').text(response.count);
                    } else {
                        $('#stock_item_count').addClass('d-none');
                    }

                    // Hide loading spinner
                    $('#stock-loading').hide();

                    // Clear existing content
                    $('#stock-notification-list .stock-notification-item').remove();

                    // Function to create HTML for a stock item
                    function createStockNotificationItem(item) {
                        // Determine status badge color
                        let statusClass = 'bg-success';
                        let statusIcon = 'bx-check-circle';
                        if (item.stock_status === 'Out of Stock') {
                            statusClass = 'bg-danger';
                            statusIcon = 'bx-x-circle';
                        } else if (item.stock_status === 'Low Stock') {
                            statusClass = 'bg-warning';
                            statusIcon = 'bx-error';
                        }

                        return `
                        <div class="text-reset stock-notification-item d-block dropdown-item position-relative">
                            <div class="d-flex">
                             
                                <div class="flex-grow-1">
                                 
                                    <div class="fs-13 text-muted">
                                        <p class="mb-1">${item.item_description}</p>
                                        <p class="mb-0">
                                            <strong>Current Stock:</strong> ${parseFloat(item.current_balance).toFixed(0)} units
                                         
                                        </p>
                                    </div>
                                </div>
                              
                            </div>
                        </div>
                    `;
                    }

                    // Add stock items to the list
                    if (response.items.length > 0) {
                        response.items.forEach(function(item) {
                            const notificationItem = createStockNotificationItem(item);
                            $('#stock-notification-list').append(notificationItem);
                        });
                    } else {
                        $('#stock-notification-list').append(`
                            <div class="text-center p-3 stock-notification-item">
                                <p class="text-muted mb-0">No stock items available</p>
                            </div>
                        `);
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Error loading stock notifications:', errorThrown);
                $('#stock-loading').hide();
                $('#stock-notification-list').append(`
                    <div class="text-center p-3 stock-notification-item">
                        <p class="text-danger mb-0">Failed to load stock data</p>
                    </div>
                `);
            }
        });
    }

    // Load stock notifications on page load (visible to all logged in users)
    loadStockNotifications();
    setInterval(loadStockNotifications, 30000); // Update every 30 seconds

    // Function to load tours pending driver allocation (for super admin/transport manager)
    function loadToursPendingNotifications() {
        $.ajax({
            url: "<?php echo base_url(); ?>dashboard2/transport/driver_allow/available_tours",
            method: "GET",
            dataType: "json",
            success: function (response) {
                if (response.status === 200) {
                    const count = response.data ? response.data.length : 0;

                    // Show/hide badge
                    if (count > 0) {
                        $('#tours_pending_count').removeClass('d-none').text(count);
                    } else {
                        $('#tours_pending_count').addClass('d-none');
                    }

                    // Remove loading spinner
                    $('#tours-pending-loading').remove();

                    // Clear existing items
                    $('#tours-pending-list .tours-pending-item').remove();

                    // Create notification items
                    function createToursPendingItem(tour) {
                        const startDate = new Date(tour.start_date).toLocaleDateString();
                        const tourLink = `<?php echo base_url(); ?>dashboard2/transport/driver_allow/tour_transport?tour_id=${tour.tour_id}`;

                        return `
                        <div class="text-reset tours-pending-item d-block dropdown-item position-relative">
                            <div class="d-flex">
                                <div class="avatar-xs me-3 flex-shrink-0">
                                    <span class="avatar-title bg-info-subtle text-info rounded-circle fs-16">
                                        <i class="bx bx-bus"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <a href="${tourLink}" class="stretched-link">
                                        <h6 class="mt-0 mb-1 fs-13 fw-semibold">${tour.manual_id || tour.tour_id}</h6>
                                    </a>
                                    <div class="fs-13 text-muted">
                                        <p class="mb-1"><strong>Tour:</strong> ${tour.consignee || 'N/A'}</p>
                                        <p class="mb-1"><strong>Agent:</strong> ${tour.agent_name || 'N/A'}</p>
                                        <p class="mb-1"><strong>Start:</strong> ${startDate}</p>
                                        <p class="mb-0"><strong>Pax:</strong> ${tour.passenger_count || 0}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        `;
                    }

                    // Populate list
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(function(tour) {
                            $('#tours-pending-list').append(createToursPendingItem(tour));
                        });
                    } else {
                        $('#tours-pending-list').append(`
                            <div class="text-center p-3 tours-pending-item">
                                <p class="text-muted mb-0">No tours pending driver allocation</p>
                            </div>
                        `);
                    }
                } else {
                    $('#tours-pending-loading').remove();
                    $('#tours_pending_count').addClass('d-none');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Error loading tours pending:', errorThrown);
                $('#tours-pending-loading').remove();
                $('#tours-pending-list').append(`
                    <div class="text-center p-3 tours-pending-item">
                        <p class="text-danger mb-0">Failed to load tours</p>
                    </div>
                `);
            }
        });
    }

    // Load tours pending on page load and refresh every 20 seconds (only for group_id 1 and 23)
    // Pending Drivers polling - commented out per request
    <?php if (false && (session()->get('group_id') == 1 || session()->get('group_id') == 23)): ?>
    loadToursPendingNotifications();
    setInterval(loadToursPendingNotifications, 20000);
    <?php endif; ?>

    // Function to load tours with allocated drivers
    function loadToursAllocatedNotifications() {
        $.ajax({
            url: "<?php echo base_url(); ?>dashboard2/transport/driver_allow/allocated_tours_json",
            method: "GET",
            dataType: "json",
            success: function (response) {
                if (response.status === 200) {
                    const count = response.data ? response.data.length : 0;

                    // Show/hide badge
                    if (count > 0) {
                        $('#tours_allocated_count').removeClass('d-none').text(count);
                    } else {
                        $('#tours_allocated_count').addClass('d-none');
                    }
                } else {
                    $('#tours_allocated_count').addClass('d-none');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Error loading allocated tours:', errorThrown);
                $('#tours_allocated_count').addClass('d-none');
            }
        });
    }

    // Load allocated tours on page load and refresh every 20 seconds
  

    //ajax call to mail/inbox/unread_mails
    //$.ajax({
    //    url: "<?php //echo base_url(); ?>//mail/inbox/unread_emails",
    //    method: "GET",
    //    dataType: "json",
    //    success: function (response) {
    //        console.log(response);
    //
    //        if (response.status === 200) {
    //            $('#unread_count').removeClass('d-none');
    //            // $('#unread_count').text(response.unread_tour.length + response.unread.length);
    //            $('#unread_count').text(response.unread_tour.length);
    //
    //            // Clear previous content in #messages-tab and #all-noti-tab
    //            $('#messages-tab .notification-item').remove();
    //            $('#all-noti-tab .notification-item').remove();
    //
    //            // Function to create HTML for an email item
    //            function createEmailItem(email, tourLink = '#') {
    //                return `
    //                <div class="text-reset notification-item d-block dropdown-item">
    //                    <div class="d-flex">
    //                        <div class="avatar-xs me-3 flex-shrink-0">
    //                            <span class="avatar-title bg-info-subtle text-info rounded-circle fs-16">
    //                                <i class="bx bx-envelope"></i>
    //                            </span>
    //                        </div>
    //                        <div class="flex-grow-1">
    //                            <a href="${tourLink}" class="stretched-link">
    //                                <h6 class="mt-0 mb-1 fs-13 fw-semibold">${email.from}</h6>
    //                            </a>
    //                            <div class="fs-13 text-muted">
    //                                <p class="mb-1">${email.subject || 'No Subject'}</p>
    //                            </div>
    //                            <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
    //                                <span><i class="mdi mdi-clock-outline"></i> ${new Date(email.date).toLocaleString()}</span>
    //                            </p>
    //                        </div>
    //                        <div class="px-2 fs-15">
    //                            <div class="form-check notification-check">
    //                                <input class="form-check-input" type="checkbox" value=""
    //                                       id="notification-check-${email.uid}">
    //                                <label class="form-check-label"
    //                                       for="notification-check-${email.uid}"></label>
    //                            </div>
    //                        </div>
    //                    </div>
    //                </div>
    //            `;
    //            }
    //
    //            // Add unread_tour emails to both #all-noti-tab and #messages-tab
    //            response.unread_tour.forEach(function(email) {
    //                const tourLink = `<?php //echo base_url(); ?>//tour/booking_confirm/show_details?id=${email.tour_id}&start=${encodeURIComponent(email.start_date)}&end=${encodeURIComponent(email.end_date)}&type=1`;
    //                const emailItem = createEmailItem(email, tourLink);
    //
    //                // Add to #messages-tab and #all-noti-tab
    //                $('#messages-tab .view-all').before(emailItem);
    //                $('#all-noti-tab .view-all').before(emailItem);
    //            });
    //
    //            // Add unread emails (not part of unread_tour) to only #all-noti-tab
    //            // response.unread.forEach(function(email) {
    //            //     const emailItem = createEmailItem(email);
    //            //
    //            //     // Add to #all-noti-tab only
    //            //     $('#all-noti-tab .view-all').before(emailItem);
    //            // });
    //        }
    //    }
    //});

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
                                // remove local storge items
                                localStorage.removeItem('previousNotifications');
                                // redirect to login page
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
</script>