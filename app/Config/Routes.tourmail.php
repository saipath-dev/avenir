<?php

/**
 * tf-tourmail-r1 (10 Oct 2026) — the tour's mails: the list before they go, send all from the Hotel Desk, Sent Mails,
 * the "Mails not sent" alert. Required from Config/Routes.php (one line). Each screen keeps the privilege it sits under:
 * the inquiry's preview under Tour Inquiry, the rest under Hotel Bookings; the alert only reads, for a signed-in person.
 */

use App\Controllers\Tour\TourMailController;

$routes->group('tour/intake', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('intake/(:num)/mail_preview', [TourMailController::class, 'intakePreview']);
});
$routes->group('tour/hotel_bookings', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('tour/(:num)/mail_plan', [TourMailController::class, 'tourPlan']);
    $routes->post('tour/(:num)/mail_all', [TourMailController::class, 'tourSendAll']);
    $routes->get('mails', [TourMailController::class, 'page']);
    $routes->get('mails/data', [TourMailController::class, 'data']);
    $routes->get('mails/copy/(hotel|restaurant)/(:num)', [TourMailController::class, 'copy']);
});
// the top bar asks on every screen: under notification/ (outside the privilege filter, as the other top-bar alerts are, so no
// access line is written per page); it reads only, and answers only a sign-in holding Hotel Bookings
$routes->get('notification/unsent_mails', [TourMailController::class, 'alert'], ['filter' => 'UserSessionFilter']);
