<?php

/**
 * tf-rotation-r1 (25 Oct 2026) — fair driver rotation: Pending Allocations, the driver registry's rotation section, the report.
 * Required from Config/Routes.home.php. Under tour/transport_ops/, so the route filter admits whoever holds Transport Operations;
 * every decision is checked again in the controller against the Driver Allocation privilege.
 */

use App\Controllers\Tour\TransportAllocationController;

$routes->group('tour/transport_ops/allocations', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [TransportAllocationController::class, 'index']);
    $routes->get('data', [TransportAllocationController::class, 'data']);
    $routes->get('assess/(:num)', [TransportAllocationController::class, 'assess']);
    $routes->get('review/(:num)', [TransportAllocationController::class, 'review']);
    $routes->post('review/(:num)', [TransportAllocationController::class, 'review']);
    $routes->post('accept/(:num)', [TransportAllocationController::class, 'accept']);
    $routes->post('bypass/(:num)', [TransportAllocationController::class, 'bypass']);
    $routes->post('not_available/(:num)', [TransportAllocationController::class, 'notAvailable']);
    // tf-taxiq-r1 (3 Nov 2026): the combined car-and-van taxi queue — one lane, read from the allocation record
    $routes->get('queue', [TransportAllocationController::class, 'queue']);
    $routes->get('queue/data', [TransportAllocationController::class, 'queueData']);
    $routes->post('queue/car_rate/(:num)', [TransportAllocationController::class, 'carRateAnswer']);
    $routes->post('queue/approval/(:num)', [TransportAllocationController::class, 'carRateDecide']);
    $routes->get('registry', [TransportAllocationController::class, 'registry']);
    $routes->get('registry/data', [TransportAllocationController::class, 'registryData']);
    $routes->post('registry/(:num)', [TransportAllocationController::class, 'registrySave']);
    $routes->post('unavailable', [TransportAllocationController::class, 'unavailableAdd']);
    $routes->post('unavailable/(:num)/clear', [TransportAllocationController::class, 'unavailableClear']);
    $routes->get('log', [TransportAllocationController::class, 'logPage']);
    $routes->get('log/data', [TransportAllocationController::class, 'logData']);
    $routes->get('legacy', [TransportAllocationController::class, 'legacy']);   // tf-rotation-r1b: the earlier page's tours, read only
});

// tf-taxiq-r3 (3 Nov 2026): the car-rate approvals under Pilot → Pricing, so the person who approves a rate reaches
// them with the Pricing privilege alone — nobody needs Transport Operations added to approve a figure.
$routes->group('tour/pricing/taxi_approvals', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [TransportAllocationController::class, 'approvalsPage']);
    $routes->get('data', [TransportAllocationController::class, 'approvalsData']);
    $routes->post('(:num)', [TransportAllocationController::class, 'carRateDecide']);
});
