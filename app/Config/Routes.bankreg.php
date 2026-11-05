<?php

/**
 * tf-bankreg-r1 (22 Oct 2026) — the Supplier Bank Account Registry and the account carried by a payment.
 * Required from the end of Config/Routes.home.php.
 */

use App\Controllers\MasterFiles\SupplierAccountsController;
use App\Controllers\Tour\TourPaymentDeskController;

$routes->group('master_files/supplier_accounts', static function ($routes) {
    $routes->get('/', [SupplierAccountsController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
    $routes->get('list', [SupplierAccountsController::class, 'list'], ['filter' => ['UserSessionFilter']]);
    $routes->get('history', [SupplierAccountsController::class, 'history'], ['filter' => ['UserSessionFilter']]);
    $routes->post('save', [SupplierAccountsController::class, 'save'], ['filter' => ['UserSessionFilter']]);
    $routes->post('set_default', [SupplierAccountsController::class, 'set_default'], ['filter' => ['UserSessionFilter']]);
    $routes->post('set_active', [SupplierAccountsController::class, 'set_active'], ['filter' => ['UserSessionFilter']]);
    $routes->post('verify', [SupplierAccountsController::class, 'verify'], ['filter' => ['UserSessionFilter']]);
});

// the payment desk: the account options of a payee, an explicit selection, the renewed review of a flagged payment (privileges checked inside)
$routes->get('tour/tour_payment_dashboard/account_options', [TourPaymentDeskController::class, 'account_options'], ['filter' => ['UserSessionFilter']]);
$routes->get('tour/tour_payment_dashboard/account_of', [TourPaymentDeskController::class, 'account_of'], ['filter' => ['UserSessionFilter']]);
$routes->post('tour/tour_payment_dashboard/account_select', [TourPaymentDeskController::class, 'account_select'], ['filter' => ['UserSessionFilter']]);
$routes->post('tour/tour_payment_dashboard/account_review', [TourPaymentDeskController::class, 'account_review'], ['filter' => ['UserSessionFilter']]);
