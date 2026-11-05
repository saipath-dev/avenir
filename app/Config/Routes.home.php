<?php

/**
 * tf-home-r1 (22 Oct 2026) — Home: the Avenir Holidays Handling System dashboard (five desks).
 * Required from the end of Config/Routes.tour_flow.php. Open to every signed-in user, like the Pilot page
 * (UserPermissionFilter::LANDING_PATHS); each desk lists only what the user's own privileges allow.
 */

use App\Controllers\HomeDeskController;

$routes->get('home', [HomeDeskController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
// tf-stage2 (29 Oct 2026): the Home-page USD rate box — saving needs the "Enter the daily USD rate" right (checked in the controller,
// so the right grants nothing else); the status is for anyone signed in
$routes->post('home/usd_rate/save', [HomeDeskController::class, 'usdRateSave'], ['filter' => ['UserSessionFilter']]);
$routes->get('home/usd_rate/status', [HomeDeskController::class, 'usdRateStatus'], ['filter' => ['UserSessionFilter']]);
// tf-tourdesk-r1 (25 Oct 2026): the Tour Desk page and its read-only data (demonstration build)
$routes->get('home/tour_desk', [HomeDeskController::class, 'tourDesk'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
$routes->get('home/tour_desk/data', [HomeDeskController::class, 'tourDeskData'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
// tf-tourdesk-r2 (25 Oct 2026): the Upcoming Tours and Next Arrivals detail views (read-only)
$routes->get('home/tour_desk/upcoming', [HomeDeskController::class, 'tourDeskUpcoming'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
$routes->get('home/tour_desk/arrivals', [HomeDeskController::class, 'tourDeskArrivals'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
// tf-resdesk-r1 (25 Oct 2026): the Reservation Desk page (read-only; its tiles follow the sign-in's privileges)
$routes->get('home/reservation_desk', [HomeDeskController::class, 'reservationDesk'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
// tf-transdesk-r1 (25 Oct 2026): the Transport Operations Desk page (design stage, read-only)
$routes->get('home/transport_desk', [HomeDeskController::class, 'transportDesk'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
// tf-masters-r1 (25 Oct 2026): the Master Files page — one page of buttons to the existing master pages (navigation only)
$routes->get('home/master_files', [HomeDeskController::class, 'masterFiles'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
// tf-stage3 (29 Oct 2026): the Master panel's one search and its Needs attention list — read-only, only pages the sign-in holds
$routes->get('home/master_files/search', [HomeDeskController::class, 'masterFilesSearch'], ['filter' => ['UserSessionFilter']]);
$routes->get('home/master_files/attention', [HomeDeskController::class, 'masterFilesAttention'], ['filter' => ['UserSessionFilter']]);
$routes->get('home/master_files/(:segment)', [HomeDeskController::class, 'masterFilesCategory'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);   // tf-masters-r2: one category's buttons
// tf-findesk-r1 (25 Oct 2026): the Finance Desk page (navigation only; Payment Approval stays in the Approver Desk)
$routes->get('home/finance_desk', [HomeDeskController::class, 'financeDesk'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
// tf-appdesk-r1 (25 Oct 2026): the Approver Desk page (navigation only)
$routes->get('home/approver_desk', [HomeDeskController::class, 'approverDesk'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);

// tf-pa-upcoming-r1 (22 Oct 2026): Payment Approval status cards and Next 15 days review list (read-only)
require __DIR__ . '/Routes.pa_upcoming.php';

// tf-bankreg-r1 (22 Oct 2026): Supplier Bank Account Registry and the account carried by each payment
require __DIR__ . '/Routes.bankreg.php';

// tf-rotation-r1 (25 Oct 2026): fair driver rotation — Pending Allocations, the driver details & agreed rates section, the report
require __DIR__ . '/Routes.rotation.php';
