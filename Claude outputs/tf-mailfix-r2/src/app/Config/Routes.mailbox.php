<?php

/**
 * tf-mailfix-r2 — My Profile → Mailbox. Included from app/Config/Routes.php with one line.
 * The signed-in person's own mailbox only; "my_profile/*" is already open to every signed-in person.
 */

use App\Controllers\MailboxController;

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('my_profile/mailbox', ['filter' => 'UserSessionFilter'], static function ($routes) {
    $routes->get('/', [MailboxController::class, 'show']);
    $routes->post('check', [MailboxController::class, 'check']);
    $routes->post('save', [MailboxController::class, 'save']);
});
