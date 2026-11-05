<?php

/**
 * Tour Flow — inquiry intake.
 *
 * One menu URL = one privilege, the same rule the rest of the system uses: every
 * action below sits under tour/intake, so granting "Tour Inquiry" in Settings →
 * Group Privileges is all that is needed. No route aliases.
 *
 * Required from Config/Routes.php; see app/Libraries/TourFlow/README.md.
 */

use App\Controllers\MasterFiles\CrewBataController;
use App\Controllers\MasterFiles\CrewPeopleController;
use App\Controllers\MasterFiles\CustomerController;
use App\Controllers\MasterFiles\ExcursionRatesController;
use App\Controllers\MasterFiles\FreeVisitsController;
use App\Controllers\MasterFiles\PaidServicesController;
use App\Controllers\MasterFiles\EntranceMasterController;
use App\Controllers\MasterFiles\MealMasterController;
use App\Controllers\MasterFiles\TourPhotosController;
use App\Controllers\MasterFiles\TransportController;
use App\Controllers\Tour\ConfirmedToursController;
use App\Controllers\Tour\PricingController;
use App\Controllers\Tour\TicketStockController;
use App\Controllers\Tour\TourDeskController;
use App\Controllers\Tour\TourIntakeController;
use App\Controllers\Tour\TourManageController;

// Customer master (Master Files → Consignees): the agent mark. Which customer on the ledger
// is a booking agent — 1 or 0 in customers.agent — set from the Consignees list and read by
// every quotation screen. The routes sit under master_files/customer, the screen's own
// privilege (Routes.php holds the rest of that group); no new menu entry, no alias.
$routes->group('master_files/customer', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->post('agent', [CustomerController::class, 'agent']);
    $routes->post('agent_quoted', [CustomerController::class, 'agentQuoted']);
});

// Manage (Management): reassign a handling agent, archive and restore, correct a hotel card,
// remove an archived inquiry with nothing attached, archive a hotel in the master. One
// privilege; the ⋯ menus on every desk screen post here and show only to a group that holds it.
$routes->group('tour/manage', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [TourManageController::class, 'index']);
    $routes->get('data', [TourManageController::class, 'data']);
    $routes->post('reauth', [TourManageController::class, 'reauth']);
    $routes->get('handlers', [TourManageController::class, 'handlers']);
    $routes->get('intake/(:num)/dependencies', [TourManageController::class, 'dependencies']);
    $routes->post('intake/(:num)/reassign', [TourManageController::class, 'reassign']);
    $routes->post('intake/(:num)/archive', [TourManageController::class, 'archive']);
    $routes->post('intake/(:num)/restore', [TourManageController::class, 'restore']);
    $routes->post('intake/(:num)/delete', [TourManageController::class, 'delete']);
    $routes->post('hotel_card/(:num)/(:segment)', [TourManageController::class, 'hotelCard']);
    $routes->post('transport_card/(:num)/(:segment)', [TourManageController::class, 'transportCard']);
    $routes->post('service_card/(:num)/(:segment)', [TourManageController::class, 'serviceCard']);
    $routes->get('activity.csv', [TourManageController::class, 'export']);
    $routes->post('settings', [TourManageController::class, 'settings']);
    $routes->post('company', [TourManageController::class, 'company_save']);
    $routes->post('sightseeing', [TourManageController::class, 'sightseeing_save']);
    $routes->post('itinerary', [TourManageController::class, 'itinerary_save']);
    // schema 34: the ready-made emails — created, changed, archived and restored by Management
    $routes->get('email_templates', [TourManageController::class, 'emailTemplatesList']);
    $routes->post('email_template', [TourManageController::class, 'emailTemplateSave']);
    $routes->post('email_template/(:num)/(archive|restore)', [TourManageController::class, 'emailTemplateAction']);
    // schema 33: Edit Request Authority, switched on and off for one person at a time
    $routes->post('authority/(:num)', [TourManageController::class, 'authority']);
    // rollback protection (schema 30): the copies of the TourFlow tables, and one taken on demand
    $routes->get('backups', [TourManageController::class, 'backups']);
    $routes->post('backup', [TourManageController::class, 'backup']);
    $routes->post('excursion/(:segment)/restore', [TourManageController::class, 'excursionRestore']);
    $routes->post('vehicle_class/(:segment)/restore', [TourManageController::class, 'vehicleClassRestore']);
    $routes->post('tour/(:num)/reopen', [TourManageController::class, 'tourReopen']);
    // Admin: cancel, delete (archive, restorable), restore, remove for good — schema 22
    $routes->post('tour/(:num)/cancel', [TourManageController::class, 'tourCancel']);
    $routes->post('tour/(:num)/delete', [TourManageController::class, 'tourDelete']);
    $routes->post('tour/(:num)/restore', [TourManageController::class, 'tourRestore']);
    $routes->get('tour/(:num)/removal', [TourManageController::class, 'tourRemoval']);
    $routes->post('tour/(:num)/remove', [TourManageController::class, 'tourRemove']);
    $routes->get('hotel/(:num)/dependencies', [TourManageController::class, 'hotelDeps']);
    $routes->post('hotel/(:num)/(:segment)', [TourManageController::class, 'hotel']);
});

// The desks after Confirm. Each screen is one privilege; every action sits under it.
// A confirmed tour is one job on each desk (schema 21): the list is one row per tour, opened
// to its stays and activity lines (or its vehicle cards); the desk marks its work complete.
$routes->group('tour/hotel_bookings', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [TourDeskController::class, 'hotel']);
    $routes->get('tours', [TourDeskController::class, 'hotelTours']);
    $routes->get('tour/(:num)/work', [TourDeskController::class, 'hotelWork']);
    $routes->post('tour/(:num)/complete', [TourDeskController::class, 'reservationsConfirmed']);
    $routes->get('data', [TourDeskController::class, 'hotelData']);
    $routes->get('card/(:num)', [TourDeskController::class, 'hotelCard']);
    // the booking request and the voucher: a page to print, or the body that goes by email
    $routes->get('card/(:num)/document/(:segment)', [TourDeskController::class, 'hotelDocument']);
    $routes->post('card/(:num)/(:segment)', [TourDeskController::class, 'hotelAction']);
    // activities and tickets: the same desk, the same privilege (schema 20)
    $routes->get('activities', [TourDeskController::class, 'activityData']);
    $routes->get('activity/(:num)', [TourDeskController::class, 'activityCard']);
    $routes->post('activity/(:num)/(:segment)', [TourDeskController::class, 'activityAction']);
    // schema 40: a restaurant payment voucher (Meal Master) as a page to print
    $routes->get('meal_voucher/(:num)', [TourDeskController::class, 'mealVoucherPage']);
    $routes->post('tour/(:num)/activity', [TourDeskController::class, 'activityAdd']);
    $routes->post('tour/(:num)/confirmed', [TourDeskController::class, 'reservationsConfirmed']);
    $routes->get('strip/(:num)', [TourDeskController::class, 'strip']);
    // the tour as this desk needs it, and the same as a page to print
    $routes->get('tour/(:num)/details', [TourDeskController::class, 'details']);
    $routes->get('tour/(:num)/programme', [TourDeskController::class, 'programme']);
});

$routes->group('tour/transport_ops', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [TourDeskController::class, 'transport']);
    // tf-pilot-r2: the two privilege entries open the desk where their work is (they had no page: 404)
    $routes->get('crew_change', [TourDeskController::class, 'crewChangePage']);
    $routes->get('driver_advance', [TourDeskController::class, 'driverAdvancePage']);
    $routes->get('tours', [TourDeskController::class, 'transportTours']);
    $routes->get('tour/(:num)/work', [TourDeskController::class, 'transportWork']);
    $routes->post('tour/(:num)/complete', [TourDeskController::class, 'transportComplete']);
    $routes->get('data', [TourDeskController::class, 'transportData']);
    $routes->get('masters', [TourDeskController::class, 'transportMasters']);
    $routes->get('card/(:num)', [TourDeskController::class, 'transportCard']);
    $routes->post('card/(:num)/(:segment)', [TourDeskController::class, 'transportAction']);
    $routes->post('card/(:num)/(:segment)/(:num)', [TourDeskController::class, 'transportAction']);
    $routes->post('crew/(:num)', [TourDeskController::class, 'crew']);
    // schema 24: the days a person works, a driver or guide replaced mid-tour (the Crew
    // replacement privilege, tour/transport_ops/crew_change, checked in the controller), and a
    // payment voucher per person for the days actually worked
    $routes->post('crew/(:num)/dates', [TourDeskController::class, 'crewDates']);
    $routes->post('crew/(:num)/replace', [TourDeskController::class, 'crewReplace']);
    $routes->post('crew/(:num)/voucher', [TourDeskController::class, 'crewVoucher']);
    $routes->post('voucher/(:num)/cancel', [TourDeskController::class, 'crewVoucherCancel']);
    $routes->get('voucher/(:num)', [TourDeskController::class, 'crewVoucherPage']);
    $routes->post('doc/(:num)', [TourDeskController::class, 'doc']);
    $routes->post('doc_type', [TourDeskController::class, 'docType']);
    // the driver's pack: one document generated from the record, as a page or a PDF
    $routes->get('card/(:num)/pack/(:segment)', [TourDeskController::class, 'packDocument']);
    // schema 26: the driver's advance and the settlement adjustments (the Driver advance privilege,
    // tour/transport_ops/driver_advance, checked in the controller for approve / adjust / pay / settle)
    $routes->post('advance/(:num)/(:segment)', [TourDeskController::class, 'advance']);
    $routes->post('adjustment/(:num)/remove', [TourDeskController::class, 'adjustmentRemove']);
    // the tour as the Transport Desk needs it, and the driver's sheet to print
    $routes->get('tour/(:num)/details', [TourDeskController::class, 'details']);
    $routes->get('tour/(:num)/programme', [TourDeskController::class, 'programme']);
});

$routes->group('tour/board', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [TourDeskController::class, 'board']);
    $routes->get('data', [TourDeskController::class, 'boardData']);
    $routes->get('tour/(:num)', [TourDeskController::class, 'tour']);
    $routes->get('tour/(:num)/programme', [TourDeskController::class, 'programme']);
    $routes->get('tour/(:num)/record', [TourDeskController::class, 'record']);      // tf-record-r1: the complete record of a confirmed tour
    $routes->post('reauth', [TourDeskController::class, 'reauth']);
    $routes->post('tour/(:num)/(:segment)', [TourDeskController::class, 'tourAction']);
});

// Master Files → Transport Rates: what a kilometre costs, by vehicle size, with history.
$routes->group('master_files/transport_rates', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [TransportController::class, 'rates']);
    $routes->get('board', [TransportController::class, 'board']);
    $routes->get('history/(:segment)', [TransportController::class, 'history']);
    $routes->post('rate', [TransportController::class, 'setRate']);
    $routes->post('rate/(:num)/correct', [TransportController::class, 'correct']);
    $routes->post('class/(:segment)/active', [TransportController::class, 'active']);
    $routes->post('class/(:segment)/max_pax', [TransportController::class, 'maxPax']);
    // tf-masters-r3: the fleet — the vehicle master as the desk sees it, read-only (the registration is kept on Master Files → Vehicle)
    $routes->get('fleet', [TransportController::class, 'fleet']);
});

// Master Files → Mileage Allowances: the stay day, the garage run, the city tours.
$routes->group('master_files/mileage_allowances', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [TransportController::class, 'allowances']);
    $routes->get('data', [TransportController::class, 'allowanceData']);
    $routes->post('allowance/(:segment)', [TransportController::class, 'saveAllowance']);
    $routes->post('city', [TransportController::class, 'city']);
    $routes->post('city/(:num)', [TransportController::class, 'city']);
    // schema 17: which figure the quotation is costed on, and a live check of the Google key
    $routes->post('source', [TransportController::class, 'quoteSource']);
    $routes->post('google_test', [TransportController::class, 'googleTest']);
});

// Master Files → Excursion Rates: the excursions a quotation can carry, with their own rates.
$routes->group('master_files/excursion_rates', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [ExcursionRatesController::class, 'index']);
    $routes->get('data', [ExcursionRatesController::class, 'data']);
    $routes->post('save', [ExcursionRatesController::class, 'save']);
    $routes->post('delete/(:segment)', [ExcursionRatesController::class, 'delete']);
});

// Master Files → Crew Bata (schema 23): the driver and guide categories, languages and bata
// per day. The quotation screen reads it; it is maintained here, by Admin.
$routes->group('master_files/crew_bata', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [CrewBataController::class, 'index']);
    $routes->get('data', [CrewBataController::class, 'data']);
    $routes->post('save', [CrewBataController::class, 'save']);
    $routes->post('bata', [CrewBataController::class, 'bata']);
    $routes->post('delete/(:segment)', [CrewBataController::class, 'delete']);
});

// Master Files → Drivers & Guides (schema 36): the people the company engages, with the categories they serve,
// contacts, licences, agreed bata and ledger account. Transport Operations picks the person from here.
// Schema 44 (tf-crew-master-r1): the Driver & Guide Master — JSD / JSCG / JSNTG, the vehicle, documents, the rate per
// km, the bank details (behind master_files/crew_people/bank, a privilege of its own), files served to signed-in users.
$routes->group('master_files/crew_people', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [CrewPeopleController::class, 'index']);
    $routes->get('data', [CrewPeopleController::class, 'data']);
    $routes->get('person/(:num)', [CrewPeopleController::class, 'person']);
    $routes->post('save', [CrewPeopleController::class, 'save']);
    $routes->post('save/(:num)', [CrewPeopleController::class, 'save']);
    $routes->post('confirm/(:num)', [CrewPeopleController::class, 'confirm']);
    $routes->post('archive/(:num)', [CrewPeopleController::class, 'archive']);
    $routes->post('restore/(:num)', [CrewPeopleController::class, 'restore']);
    $routes->post('vehicle/(:num)', [CrewPeopleController::class, 'vehicle']);
    $routes->post('vehicle/(:num)/(:num)', [CrewPeopleController::class, 'vehicle']);
    $routes->post('rate/(:num)', [CrewPeopleController::class, 'rate']);
    $routes->post('bata/(:num)', [CrewPeopleController::class, 'bata']);
    $routes->post('standard/(:segment)', [CrewPeopleController::class, 'standard']);
    $routes->post('upload/(:num)/(:segment)', [CrewPeopleController::class, 'upload']);
    $routes->post('photo/(:num)/(:num)/remove', [CrewPeopleController::class, 'removePhoto']);
    $routes->get('file/(:num)/(:segment)', [CrewPeopleController::class, 'file']);
    $routes->get('file/(:num)/(:segment)/(:num)', [CrewPeopleController::class, 'file']);
});

// Tour Desk → Ticket Stock (schema 37): the entrance tickets the company holds, under control — attractions
// from the Entrance Fee master handled from numbered stock or paid at the gate, batches received, every
// ticket's status, the tours holding tickets. Allocation to a tour is an Activities-desk action (Hotel Bookings).
$routes->group('tour/ticket_stock', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [TicketStockController::class, 'index']);
    $routes->get('data', [TicketStockController::class, 'data']);
    $routes->get('attraction/(:num)', [TicketStockController::class, 'attraction']);
    $routes->post('attraction', [TicketStockController::class, 'addAttraction']);
    $routes->post('attraction/(:num)/update', [TicketStockController::class, 'updateAttraction']);
    $routes->post('attraction/(:num)/receive', [TicketStockController::class, 'receive']);
    $routes->post('ticket/(:num)/void', [TicketStockController::class, 'void']);
    $routes->post('ticket/(:num)/unvoid', [TicketStockController::class, 'unvoid']);
    $routes->post('tour/(:num)/used', [TicketStockController::class, 'tourUsed']);
});

// Master Files → Tour Photos (schema 27): the pictures the client quotation carries, tagged
// to destinations and attractions. Uploaded and kept here; the quotation only reads them.
$routes->group('master_files/tour_photos', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [TourPhotosController::class, 'index']);
    $routes->get('data', [TourPhotosController::class, 'data']);
    $routes->post('tag', [TourPhotosController::class, 'tag']);
    $routes->post('tag/delete/(:segment)', [TourPhotosController::class, 'tagDelete']);
    $routes->post('upload', [TourPhotosController::class, 'upload']);
    $routes->post('photo/(:num)/save', [TourPhotosController::class, 'save']);
    $routes->post('photo/(:num)/replace', [TourPhotosController::class, 'replace']);
    $routes->post('photo/(:num)/remove', [TourPhotosController::class, 'remove']);
    $routes->post('photo/(:num)/restore', [TourPhotosController::class, 'restore']);
});

// Master Files → Free Visits (schema 29): each destination's normal sightseeing and en-route
// stops, with no cost anywhere on them. Put on a day, a free visit is on the itinerary only.
$routes->group('master_files/free_visits', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [FreeVisitsController::class, 'index']);
    $routes->get('data', [FreeVisitsController::class, 'data']);
    $routes->post('save', [FreeVisitsController::class, 'save']);
    $routes->post('delete/(:num)', [FreeVisitsController::class, 'delete']);
});

// Master Files → Paid Activities & Services (schema 29): the entrances, tickets, activities and
// excursions by destination, each with its rate structure. Put on a day, a paid item is costed.
// Master Files → Place to Visit / Entrance Fee Master (schema 39, tf-entrance-r1): entrances and safaris only — the paid
// entrance places and the safari parks, each with its entrance rate (adult, child, USD / LKR, or Free) and at a park its jeep
// rate per vehicle, for a period; destination, in use or not, other names; duplicates merged; the one-time import from the
// old Place to Visit list shown read only. One menu URL = one privilege; granted to the System Administrator only.
$routes->group('master_files/entrance_master', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [EntranceMasterController::class, 'index']);
    $routes->get('data', [EntranceMasterController::class, 'data']);
    $routes->get('check', [EntranceMasterController::class, 'check']);
    $routes->post('place', [EntranceMasterController::class, 'place']);
    $routes->post('places/merge', [EntranceMasterController::class, 'mergePlaces']);
    $routes->post('rate', [EntranceMasterController::class, 'rate']);
    $routes->post('rate/(:num)/off', [EntranceMasterController::class, 'rateOff']);
    $routes->post('alias', [EntranceMasterController::class, 'alias']);
    $routes->post('alias/(:num)/off', [EntranceMasterController::class, 'aliasOff']);
});

// Master Files → Meal Master (schema 40, tf-meals-r1): lunches and dinners outside the hotel for passengers on Half Board —
// each restaurant meal (a supplier of the Supplier master) with its location, route legs, contact, cuisine, adult and child
// rate, currency, taxes and validity, in use or not; and the meal settings. One menu URL = one privilege; granted to the
// System Administrator only.
$routes->group('master_files/meal_master', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [MealMasterController::class, 'index']);
    $routes->get('data', [MealMasterController::class, 'data']);
    $routes->get('meal/(:num)', [MealMasterController::class, 'view']);
    $routes->post('save', [MealMasterController::class, 'save']);
    $routes->post('meal/(:num)/active', [MealMasterController::class, 'active']);
    $routes->post('settings', [MealMasterController::class, 'settings']);
});

$routes->group('master_files/paid_services', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [PaidServicesController::class, 'index']);
    $routes->get('data', [PaidServicesController::class, 'data']);
    $routes->post('entrance/(:num)', [PaidServicesController::class, 'entrance']);
    $routes->post('activity', [PaidServicesController::class, 'activity']);
    $routes->post('activity/delete/(:segment)', [PaidServicesController::class, 'activityDelete']);
});

// Pricing (Management): the markup, company-wide and per inquiry. Staff never open this.
$routes->group('tour/pricing', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [PricingController::class, 'index']);
    $routes->get('data', [PricingController::class, 'data']);
    $routes->post('settings', [PricingController::class, 'settings']);
    $routes->post('markup/(:num)', [PricingController::class, 'markup']);
    // the rooms each party size is costed on in the pax matrix (schema 31)
    $routes->post('pax_rooms/(:num)', [PricingController::class, 'paxRooms']);
});

$routes->group('tour/intake', ['filter' => ['UserSessionFilter', 'UserPermissionFilter']], static function ($routes) {
    $routes->get('/', [TourIntakeController::class, 'index']);

    // tf-confirmed-r1: Confirmed Tours — one line per confirmed tour, read-only, under the Inquiries privilege (no menu entry of its own)
    $routes->get('confirmed', [ConfirmedToursController::class, 'index']);
    $routes->get('confirmed/data', [ConfirmedToursController::class, 'data']);

    // the quotation engine: groups, the room mix, vehicles by day, excursions, and Go for Quotation
    $routes->get('excursions', [TourIntakeController::class, 'excursions']);
    $routes->post('intake/(:num)/rooms', [TourIntakeController::class, 'rooms']);
    $routes->post('intake/(:num)/group', [TourIntakeController::class, 'group']);
    $routes->post('intake/(:num)/group/(:num)', [TourIntakeController::class, 'group']);
    $routes->post('intake/(:num)/pax/(:num)/group', [TourIntakeController::class, 'paxGroup']);
    $routes->post('intake/(:num)/vehicle_day', [TourIntakeController::class, 'vehicleDay']);
    $routes->post('intake/(:num)/excursion', [TourIntakeController::class, 'excursion']);
    $routes->post('intake/(:num)/excursion/(:num)', [TourIntakeController::class, 'excursion']);
    $routes->get('intake/(:num)/costing', [TourIntakeController::class, 'costing']);
    $routes->post('intake/(:num)/markup', [TourIntakeController::class, 'markup']);
    $routes->post('intake/(:num)/go', [TourIntakeController::class, 'go']);
    // schema 34: the maximum number of travellers a version is quoted for, and the quotation emailed to the agent
    // (these sit before the (:segment) action route so they are matched first)
    $routes->post('intake/(:num)/quote/(:num)/max_pax', [TourIntakeController::class, 'quoteMaxPax']);
    $routes->get('intake/(:num)/quote/(:num)/email', [TourIntakeController::class, 'quoteEmail']);
    $routes->post('intake/(:num)/quote/(:num)/email/preview', [TourIntakeController::class, 'quoteEmailPreview']);
    $routes->post('intake/(:num)/quote/(:num)/email/send', [TourIntakeController::class, 'quoteEmailSend']);
    $routes->post('intake/(:num)/quote/(:num)/(:segment)', [TourIntakeController::class, 'quoteAction']);
    $routes->get('intake/(:num)/quote/(:num)', [TourIntakeController::class, 'quoteView']);
    $routes->get('intake/(:num)/preview', [TourIntakeController::class, 'preview']);
    // the pictures on the client quotation (schema 27): the day's automatic pick, or the one a person chose
    $routes->get('intake/(:num)/photos', [TourIntakeController::class, 'photos']);
    $routes->post('intake/(:num)/photo/(:num)', [TourIntakeController::class, 'photo']);
    // the day programme from the two masters (schema 29): a free visit (never costed), or a paid item (costed)
    $routes->get('programme_masters', [TourIntakeController::class, 'programmeMasters']);
    $routes->post('intake/(:num)/free_visit', [TourIntakeController::class, 'freeVisit']);
    // schema 40: the meals of a Half Board tour, from the Meal Master
    $routes->get('intake/(:num)/meals/options', [TourIntakeController::class, 'mealOptions']);
    $routes->post('intake/(:num)/meal', [TourIntakeController::class, 'meal']);
    $routes->post('intake/(:num)/meal/(:num)', [TourIntakeController::class, 'meal']);
    $routes->post('intake/(:num)/day/(:num)/hb_meal', [TourIntakeController::class, 'hbMeal']);
    $routes->post('intake/(:num)/meal_new', [TourIntakeController::class, 'mealNew']);
    $routes->post('intake/(:num)/meals/fill', [TourIntakeController::class, 'mealsFill']);

    $routes->get('list', [TourIntakeController::class, 'list']);
    $routes->get('handlers', [TourIntakeController::class, 'handlers']);
    $routes->get('intake/(:num)/activity', [TourIntakeController::class, 'activity']);
    $routes->get('services', [TourIntakeController::class, 'feelist']);
    $routes->get('masters', [TourIntakeController::class, 'masters']);
    $routes->post('intake/(:num)/agent', [TourIntakeController::class, 'agent']);
    $routes->get('crew', [TourIntakeController::class, 'crew']);
    $routes->post('crew/rate', [TourIntakeController::class, 'crewRate']);
    $routes->post('intake/(:num)/vehicle', [TourIntakeController::class, 'vehicle']);
    $routes->post('intake/(:num)/crew', [TourIntakeController::class, 'intakeCrew']);
    $routes->post('intake/(:num)/accom', [TourIntakeController::class, 'accom']);
    $routes->post('setup', [TourIntakeController::class, 'setup']);

    $routes->post('intake', [TourIntakeController::class, 'create']);
    $routes->get('intake/(:num)', [TourIntakeController::class, 'show']);
    $routes->post('intake/(:num)/read', [TourIntakeController::class, 'read']);
    $routes->post('intake/(:num)/field', [TourIntakeController::class, 'field']);
    $routes->post('intake/(:num)/email', [TourIntakeController::class, 'email']);
    // the review step: the pasted text is checked (dates, years) before anything reads it,
    // and the working copy can be corrected while the original stays as pasted
    $routes->post('check_text', [TourIntakeController::class, 'checkText']);
    $routes->post('intake/(:num)/email_edit', [TourIntakeController::class, 'emailEdit']);
    // schema 33: Edit Requests below the original email (Edit Request Authority, per person)
    $routes->get('intake/(:num)/edit_requests', [TourIntakeController::class, 'editRequests']);
    $routes->post('intake/(:num)/edit_request', [TourIntakeController::class, 'editRequest']);
    $routes->post('intake/(:num)/edit_request/(:num)/(check|confirm|cancel)', [TourIntakeController::class, 'editRequestAction']);
    $routes->post('intake/(:num)/day', [TourIntakeController::class, 'day']);
    $routes->post('intake/(:num)/day/(:num)', [TourIntakeController::class, 'day']);
    $routes->post('intake/(:num)/day/(:num)/reprice', [TourIntakeController::class, 'reprice']);
    // schema 32: the dates rule — the departure day, an extra night, the email's dates, the missing nights
    $routes->post('intake/(:num)/day/(:num)/departure', [TourIntakeController::class, 'departureDay']);
    $routes->post('intake/(:num)/day/(:num)/extra_night', [TourIntakeController::class, 'extraNight']);
    $routes->post('intake/(:num)/departure', [TourIntakeController::class, 'departure']);
    $routes->post('intake/(:num)/dates', [TourIntakeController::class, 'dates']);
    $routes->post('intake/(:num)/nights_missing', [TourIntakeController::class, 'nightsMissing']);
    $routes->post('intake/(:num)/pax', [TourIntakeController::class, 'pax']);
    $routes->post('intake/(:num)/pax/(:num)', [TourIntakeController::class, 'pax']);
    $routes->post('intake/(:num)/request', [TourIntakeController::class, 'request']);
    $routes->post('intake/(:num)/request/(:num)', [TourIntakeController::class, 'request']);
    $routes->post('intake/(:num)/service', [TourIntakeController::class, 'service']);
    $routes->post('intake/(:num)/service/(:num)', [TourIntakeController::class, 'service']);
    $routes->post('intake/(:num)/sector', [TourIntakeController::class, 'sector']);
    $routes->post('intake/(:num)/sector/(:num)', [TourIntakeController::class, 'sector']);
    $routes->post('intake/(:num)/google', [TourIntakeController::class, 'google']);
    $routes->post('intake/(:num)/sector/(:num)/google', [TourIntakeController::class, 'google']);
    $routes->post('intake/(:num)/sector/(:num)/actual', [TourIntakeController::class, 'actual']);
    $routes->post('intake/(:num)/sector/(:num)/extra', [TourIntakeController::class, 'extra']);
    $routes->post('intake/(:num)/recheck', [TourIntakeController::class, 'recheck']);
    $routes->post('intake/(:num)/status', [TourIntakeController::class, 'status']);
    $routes->post('intake/(:num)/reauth', [TourIntakeController::class, 'reauth']);
    $routes->post('intake/(:num)/confirm', [TourIntakeController::class, 'confirm']);
    $routes->post('intake/(:num)/follow_up', [TourIntakeController::class, 'followUp']);
});
