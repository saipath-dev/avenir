<?php

/**
 * tf-help-r1 — the in-system User Manual. Included from app/Config/Routes.php with one line:
 *     require __DIR__ . '/Routes.help.php';
 *
 * Open to every signed-in person: UserSessionFilter applies as on every page, and "help" /
 * "help/*" are on UserPermissionFilter's exception list in app/Config/Filters.php, so no menu
 * grant is needed (and help use is not written to a_log). GET only — help never changes data.
 */

use App\Controllers\HelpController;

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('help', static function ($routes) {
    $routes->get('/', [HelpController::class, 'index']);
    $routes->get('page', [HelpController::class, 'page']);
    $routes->get('ask', [HelpController::class, 'ask']);
    $routes->get('topic/(:segment)', [HelpController::class, 'topic']);
    $routes->get('img/(:segment)/(:segment)', [HelpController::class, 'img']);
    $routes->get('manual', [HelpController::class, 'manualPdf']);
    $routes->get('check', [HelpController::class, 'check']);
});
