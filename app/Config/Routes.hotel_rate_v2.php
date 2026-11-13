<?php

/**
 * Hotel Rates (new) routes.
 *
 * Included from app/Config/Routes.php with one line at the end of that file:
 *     require __DIR__ . '/Routes.hotel_rate_v2.php';
 *
 * Permission follows the system's own procedure (menu_list + group
 * privileges, enforced by UserPermissionFilter, which allows a request when
 * its path equals or is a sub-path of a granted menu URL):
 *
 *   menu URL master_files/hotel_rate_v2        → everything in group 1 (the panel)
 *   menu URL master_files/hotel_rate_approval  → everything in group 2 (approvals & settings)
 *   menu URL master_files/hotel_work           → group 3 (Hotel Incomplete Work — the queue of what is missing)
 *
 * Grant the first to the staff who type in rate sheets, the second to whoever
 * signs rates off. System Administrator (group 1) always has both.
 */

use App\Controllers\MasterFiles\HotelRateV2Controller;
use App\Controllers\MasterFiles\HotelWorkController;
use App\Controllers\MasterFiles\HotelReadinessController;
use App\Controllers\MasterFiles\HotelOffersController;
use App\Controllers\MasterFiles\HotelMasterController;
use App\Controllers\MasterFiles\OfferSelectionController;
use App\Controllers\Tour\TourAccountsController;

/** @var \CodeIgniter\Router\RouteCollection $routes */

// ---- group 1: the panel ----------------------------------------------------
$routes->group('master_files/hotel_rate_v2', static function ($routes) {
    $routes->get('/', [HotelRateV2Controller::class, 'index']);
    $routes->get('hotels', [HotelRateV2Controller::class, 'hotels']);
    $routes->get('hotel/(:num)', [HotelRateV2Controller::class, 'hotel']);
    $routes->post('hotel/(:num)/update', [HotelRateV2Controller::class, 'updateHotel']);
    $routes->post('hotel/(:num)/frequent', [HotelRateV2Controller::class, 'frequent']);   // the Frequently Used mark only
    // tf-offers-r1: Published rates · Tactical rates · Early Bird offers
    $routes->get('hotel/(:num)/offers', [HotelRateV2Controller::class, 'hotelOffers']);
    $routes->post('contract/(:num)/withdraw_offer', [HotelRateV2Controller::class, 'withdrawOffer']);
    $routes->get('offers', [HotelOffersController::class, 'index']);
    $routes->get('offers/data', [HotelOffersController::class, 'data']);
    $routes->post('hotel/(:num)/room_type', [HotelRateV2Controller::class, 'roomType']);
    $routes->post('hotel/(:num)/project', [HotelRateV2Controller::class, 'project']);
    $routes->get('hotel/(:num)/audit', [HotelRateV2Controller::class, 'hotelAudit']);
    $routes->get('contract/(:num)', [HotelRateV2Controller::class, 'contract']);
    $routes->post('contract/validate', [HotelRateV2Controller::class, 'validateContract']);
    $routes->post('contract/save', [HotelRateV2Controller::class, 'saveContract']);      // drafts only here (approve / edit_approved need group 2)
    $routes->post('contract/(:num)/delete', [HotelRateV2Controller::class, 'deleteContract']);
    $routes->post('contract/(:num)/copy', [HotelRateV2Controller::class, 'copyContract']);
    $routes->get('contract/(:num)/audit', [HotelRateV2Controller::class, 'contractAudit']);
    $routes->get('coverage/(:num)', [HotelRateV2Controller::class, 'coverage']);
    $routes->get('rates_on', [HotelRateV2Controller::class, 'ratesOn']);
    $routes->get('lookup', [HotelRateV2Controller::class, 'lookup']);
    $routes->get('quote', [HotelRateV2Controller::class, 'quote']);
    $routes->post('import/preview', [HotelRateV2Controller::class, 'importPreview']);
    $routes->post('import/extract', [HotelRateV2Controller::class, 'importExtract']);
    $routes->post('import/(:num)/commit', [HotelRateV2Controller::class, 'importCommit']);   // as drafts here
    $routes->get('import/(:num)', [HotelRateV2Controller::class, 'importGet']);
    $routes->get('imports', [HotelRateV2Controller::class, 'imports']);
    // tf-hr-docs-r1: supporting documents of a hotel / rate plan (attach, open, remove — never hard-deleted)
    $routes->get('hotel/(:num)/documents', [HotelRateV2Controller::class, 'hotelDocuments']);
    $routes->get('contract/(:num)/documents', [HotelRateV2Controller::class, 'contractDocuments']);
    $routes->post('documents/upload', [HotelRateV2Controller::class, 'uploadDocument']);
    $routes->get('document/(:num)', [HotelRateV2Controller::class, 'document']);
    $routes->post('document/(:num)/remove', [HotelRateV2Controller::class, 'removeDocument']);
    $routes->get('duplicates', [HotelRateV2Controller::class, 'duplicates']);
    $routes->get('similar', [HotelRateV2Controller::class, 'similar']);                   // hotel-dup-r1: near-duplicate names (read-only)
    $routes->get('hotel/(:num)/deps', [HotelRateV2Controller::class, 'hotelDeps']);     // hotel-dup-r1: dependency review (read-only)
    $routes->get('hotel/(:num)/unmerge_preview', [HotelRateV2Controller::class, 'unmergePreview']);
    $routes->get('locations', [HotelRateV2Controller::class, 'locations']);
    $routes->get('markets', [HotelRateV2Controller::class, 'markets']);
    $routes->get('settings', [HotelRateV2Controller::class, 'settings']);                // read-only
    $routes->get('check', [HotelRateV2Controller::class, 'check']);
    $routes->get('queue', [HotelRateV2Controller::class, 'queue']);
    $routes->get('summary', [HotelRateV2Controller::class, 'summary']);                  // for dashboard tiles / widgets
    // tf-hr-ready-r1: quotation readiness (hotels the quotation engine can / cannot price, and why) and the rate-expiry warnings —
    // read-only, under this panel's privilege (no menu entry of its own)
    $routes->get('readiness', [HotelReadinessController::class, 'index']);
    $routes->get('readiness/data', [HotelReadinessController::class, 'data']);
    $routes->get('readiness/alerts', [HotelReadinessController::class, 'alerts']);
});

// ---- group 2: approvals & settings -----------------------------------------
$routes->group('master_files/hotel_rate_approval', static function ($routes) {
    $routes->get('/', [HotelRateV2Controller::class, 'approvalIndex']);
    $routes->post('contract/save', [HotelRateV2Controller::class, 'saveContract']);      // approve / correct approved plans
    $routes->post('contract/(:num)/approve', [HotelRateV2Controller::class, 'approve']);
    $routes->post('contract/(:num)/supersede', [HotelRateV2Controller::class, 'supersede']);
    $routes->post('import/(:num)/commit', [HotelRateV2Controller::class, 'importCommit']);   // with approve / supersede
    $routes->post('merge', [HotelRateV2Controller::class, 'merge']);
    $routes->post('unmerge', [HotelRateV2Controller::class, 'unmerge']);
    $routes->post('settings', [HotelRateV2Controller::class, 'saveSettings']);
    $routes->post('setup', [HotelRateV2Controller::class, 'setup']);                // System Administrator only: install / migrate without spark
    $routes->get('queue', [HotelRateV2Controller::class, 'queue']);
});

// ---- group 3: Hotel Incomplete Work ----------------------------------------
// ---- group 4 (hotel-dup-r1, 27 Oct 2026): Hotel Master — add, edit or remove hotels --------
//   menu URL master_files/hotel_master: everyone with the entry may look; changes need the Tour Desk Manage privilege
$routes->group('master_files/hotel_master', static function ($routes) {
    $routes->get('/', [HotelMasterController::class, 'index']);
    $routes->get('list', [HotelMasterController::class, 'list']);
    $routes->get('hotel/(:num)', [HotelMasterController::class, 'hotel']);
    $routes->get('similar', [HotelMasterController::class, 'similar']);
    $routes->post('hotel/(:num)/update', [HotelMasterController::class, 'update']);
    $routes->post('hotel/(:num)/merge', [HotelMasterController::class, 'merge']);
    $routes->post('hotel/(:num)/unmerge', [HotelMasterController::class, 'unmerge']);
    $routes->post('hotel/(:num)/archive', [HotelMasterController::class, 'archive']);
    $routes->post('hotel/(:num)/restore', [HotelMasterController::class, 'restore']);
    $routes->get('hotel/(:num)/removal', [HotelMasterController::class, 'removal']);      // what a deletion would remove (read-only)
    $routes->post('owners', [HotelMasterController::class, 'owners']);                       // System Administrator: who may add / delete hotels
    $routes->post('hotel/(:num)/delete', [HotelMasterController::class, 'delete']);
    $routes->post('hotel/(:num)/room_type/(:num)/delete', [HotelMasterController::class, 'deleteRoomType']);
});

$routes->group('master_files/hotel_work', static function ($routes) {
    $routes->get('/', [HotelWorkController::class, 'index']);
    $routes->get('list', [HotelWorkController::class, 'list']);
    $routes->post('scan', [HotelWorkController::class, 'scan']);
    $routes->post('item', [HotelWorkController::class, 'add']);
    $routes->post('item/(:num)/status', [HotelWorkController::class, 'status']);
    $routes->get('suppliers', [HotelWorkController::class, 'suppliers']);          // tf-hotel-codes-r1: pick the supplier account by name / hotel code
    $routes->post('item/(:num)/attach_supplier', [HotelWorkController::class, 'attachSupplier']);
    $routes->post('item/(:num)/supplier_details', [HotelWorkController::class, 'supplierDetails']);
    $routes->get('export', [HotelWorkController::class, 'export']);
    $routes->post('load_seed', [HotelWorkController::class, 'loadSeed']);
});

// ---- tf-offers-r1: the Tactical / Early Bird rate selection privilege (one menu URL = one privilege) ----
$routes->group('master_files/offer_selection', static function ($routes) {
    $routes->post('choose', [OfferSelectionController::class, 'choose']);
    $routes->post('reviewed', [OfferSelectionController::class, 'reviewed']);      // tf-offers-r3: the review before a hotel payment, logged
    $routes->get('offer/(:num)', [OfferSelectionController::class, 'offer']);   // tf-offers-r3: the terms of one tactical / early bird
    // tf-offers-r3: hotel payments held for the rate review — the list, release (→ the payment request), return to staff
    $routes->get('holds', [OfferSelectionController::class, 'holds']);
    $routes->post('release', [TourAccountsController::class, 'release_rate_review']);
    $routes->post('return', [OfferSelectionController::class, 'returnHold']);
});
