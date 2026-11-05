<?php

/**
 * tf-pa-upcoming-r1 (22 Oct 2026) — Payment Approval: status cards and the "Next 15 days" review list (read-only).
 * Required from the end of Config/Routes.home.php. Under payment_approval_v2/, so UserPermissionFilter lets through only a
 * user who may open Payment Approval.
 */

use App\Controllers\Tour\PaymentApprovalUpcomingController;

$routes->get('tour/tour_accounts/payment_approval_v2/upcoming', [PaymentApprovalUpcomingController::class, 'summary'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
$routes->get('tour/tour_accounts/payment_approval_v2/upcoming_tour', [PaymentApprovalUpcomingController::class, 'tour'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
$routes->get('tour/tour_accounts/payment_approval_v2/tour_lookup', [PaymentApprovalUpcomingController::class, 'lookup'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
