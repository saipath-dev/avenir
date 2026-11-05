<?php

use App\Controllers\Mail\ComposeController;
use App\Controllers\Mail\InboxController;
use CodeIgniter\Router\RouteCollection;
use App\Controllers\LoginController;
use App\Controllers\DashboardController;
use App\Controllers\InquiryController;
use App\Controllers\ProfileController;
use App\Controllers\UtilityDataController;
use App\Controllers\Inquiry\InquirySubLocationsController;
use App\Controllers\Inquiry\InquiryActivityController;
use App\Controllers\Inquiry\InquiryItineraryController;
use App\Controllers\NavigationController;

use App\Controllers\Accounts\BalanceSheetController;
use App\Controllers\Accounts\BankReconsillationController;
use App\Controllers\Accounts\ChartOfAccountsController;
use App\Controllers\Accounts\CreditorPaymentController;
use App\Controllers\Accounts\CreditorInvoiceController;
use App\Controllers\Accounts\UtilityBillEntertainmentController;
use App\Controllers\Accounts\DebtorPaymentController;
use App\Controllers\Accounts\UsdWithdrawalController;
use App\Controllers\Accounts\CashBookController;
use App\Controllers\Accounts\DebtorInvoiceController;
use App\Controllers\Accounts\DebtorInvoiceNewController;
use App\Controllers\Accounts\ExpensesChartController;
use App\Controllers\Accounts\JournalEntryController;
use App\Controllers\Accounts\LiabilityController;
use App\Controllers\Accounts\NewCreditorInvoiceController;
use App\Controllers\Accounts\VoucherPaymentController;
use App\Controllers\Accounts\VoucherAuditController;
use App\Controllers\Accounts\PaymentController;
use App\Controllers\Accounts\ProfitAndLossController;
use App\Controllers\Accounts\TrialBalanceController;
use App\Controllers\Accounts\AgeAnalysisController;
use App\Controllers\Accounts\BankStatementController;
use App\Controllers\Accounts\AccountFilterController;
use App\Controllers\Accounts\AccRunningController;
use App\Controllers\Accounts\DebitNoteController;
use App\Controllers\Accounts\UtilityBillPaymentController;
use App\Controllers\Accounts\CreditNoteController;
use App\Controllers\Accounts\OutstandingController;
use App\Controllers\Accounts\ServiceInvoiceController;
use App\Controllers\Accounts\OtherInvoiceController;
use App\Controllers\Accounts\ServiceProviderPaymentController;
use App\Controllers\Accounts\BankUnrealisedValueController;
use App\Controllers\API\V1\Inquiry\ApiInquiryController;

use App\Controllers\Chat\ChatController;
use App\Controllers\NotificationController;
use App\Controllers\SmsController;

use App\Controllers\MasterFiles\CustomerController;
use App\Controllers\MasterFiles\SupplierController;
use App\Controllers\MasterFiles\EntranceFeeController;
use App\Controllers\MasterFiles\HotelRateController;
use App\Controllers\MasterFiles\RoomTypeController;
use App\Controllers\MasterFiles\TransferMilageController;
use App\Controllers\MasterFiles\VehicleController;
use App\Controllers\MasterFiles\ExtraKilometeresController;
use App\Controllers\MasterFiles\ShopRegistrationController;
use App\Controllers\MasterFiles\JobRoleController;
use App\Controllers\MasterFiles\DailyUsdRateController;
use App\Controllers\MasterFiles\TourPaymentMastersController;   // tf-paydesk-r1

use App\Controllers\NewDashboardController;
use App\Controllers\NewDashboard\DriverAllocationController;
use App\Controllers\NewDashboard\DriverAdvancePaymentController;
use App\Controllers\NewDashboard\DriverExpensesController;
use App\Controllers\NewDashboard\DriverLogsheetController;
use App\Controllers\NewDashboard\DriverPaymentSettlementController;
use App\Controllers\NewDashboard\ShopAllocationController;
use App\Controllers\NewDashboard\TransportPortalController;
use App\Controllers\NewDashboard\DriverFundReturnController;
use App\Controllers\NewDashboard\hotel\HotelReservationController;
use App\Controllers\NewDashboard\hotel\HotelConfirmationController;
use App\Controllers\NewDashboard\hotel\HotelPaymentController;
use App\Controllers\NewDashboard\VoucherAuthenticationController;

use App\Controllers\Settings\GroupProfileController;
use App\Controllers\Settings\MenuCategoryController;
use App\Controllers\Settings\MenuListController;
use App\Controllers\Settings\UserAccountController;
use App\Controllers\Settings\GroupPrivilegeController;
use App\Controllers\Settings\ManualUpdateController;
use App\Controllers\Settings\YearEndProcessController;

use App\Controllers\Staff\StaffMemberController;

use App\Controllers\Tour\TourController;
use App\Controllers\Tour\CopyTourController;
use App\Controllers\Tour\TourDetailsController;
use App\Controllers\Tour\BookingConfirmController;
use App\Controllers\Tour\NewTourController;
use App\Controllers\Tour\TourSubLocationsController;
use App\Controllers\Tour\TourCalendarController;
use App\Controllers\Tour\ItineraryController;
use App\Controllers\Tour\printController;
use App\Controllers\Tour\TourDashboardController;
use App\Controllers\Tour\TourDetailsSearchController;
use App\Controllers\Tour\HotelManagementController;
use App\Controllers\Tour\TourActivityController;
use App\Controllers\Tour\TourArrivalNotificationController;
use App\Controllers\Tour\TourPaymentForcastController;
use App\Controllers\Tour\TourAccountsController;
use App\Controllers\Tour\TourAccountsCheckController;
use App\Controllers\Tour\TourTimelineController;
use App\Controllers\Tour\TourDocumentationController;
use App\Controllers\Tour\SpecialPaymentRequestController;
use App\Controllers\Tour\TourPaymentDashboardController;
use App\Controllers\Tour\TourPaymentDeskController;   // tf-paydesk-r1

use App\Controllers\Report\VoucherReportController;
use App\Controllers\Report\VoucherCancelReportController;
use App\Controllers\Report\DriverLedgerController;
use App\Controllers\Report\ConfirmedTourReportController;
use App\Controllers\Report\PettyCashReportController;
use App\Controllers\Report\BankPaymentCheckController;
use App\Controllers\Report\AccountReservationCardReportController;
use App\Controllers\Reports\ChangeLogReportController;
use App\Controllers\Reports\EmployeeActivityController;

use App\Controllers\Grn\StockCardController;
use App\Controllers\Grn\GrnController;
use App\Controllers\Grn\ManualGrnController;
use App\Controllers\Grn\UsageNoteController;
use App\Controllers\Grn\InventoryStockController;

use App\Controllers\Driver\DriverLoginController;

use App\Models\TourActivityModel;
use Clockwork\Support\Doctrine\Driver;

/**
 * @var RouteCollection $routes
 */


/************ PUBLIC ROUTES *************/

//LoginController
$routes->get('/', [LoginController::class, 'index']); // route : base_url/
$routes->post('login', [LoginController::class, 'login']); // route : base_url/login
$routes->get('logout', [LoginController::class, 'logout']); // route : base_url/logout
$routes->get('403', [LoginController::class, 'forbidden']); // route : base_url/forbidden

//SmsController - Public driver itinerary access
$routes->get('driver/(:any)/pdf', [SmsController::class, 'downloadPdf']);
$routes->get('driver/(:any)', [SmsController::class, 'viewItinerary']);

$routes->group('driver_login', static function ($routes) {
    $routes->get('mileage', [DriverLoginController::class, 'Mileage']);
});

$routes->group('driver_portal', static function ($routes) {
    $routes->get('/', [\App\Controllers\Driver\DriverPortalController::class, 'index']);
    $routes->post('save_meter_reading', [\App\Controllers\Driver\DriverPortalController::class, 'save_meter_reading']);
    $routes->post('save_expense', [\App\Controllers\Driver\DriverPortalController::class, 'save_expense']);
    $routes->post('tour_finalize', [\App\Controllers\Driver\DriverPortalController::class, 'TourFinalize']);
    $routes->post('update_expense', [\App\Controllers\Driver\DriverPortalController::class, 'update_expense']);
    $routes->post('delete_expense', [\App\Controllers\Driver\DriverPortalController::class, 'delete_expense']);
    $routes->post('get_expenses', [\App\Controllers\Driver\DriverPortalController::class, 'get_expenses']);
    $routes->get('settlement_invoice/(:num)', [\App\Controllers\Driver\DriverPortalController::class, 'settlementInvoice/$1']);
    $routes->get('settlement_invoice', [\App\Controllers\Driver\DriverPortalController::class, 'settlementInvoice']);
});


/************ PROTECTED ROUTES *************/

//UtilityDataController - route : base_url/data
$routes->group('data', static function ($routes) {
    $routes->get('backup_database', [UtilityDataController::class, 'backup_database']);
});

//DashboardController - route : base_url/dashboard
$routes->group('dashboard', static function ($routes) {
    $routes->get('/', [DashboardController::class, 'index']);
    $routes->get('get_bank_balances', [DashboardController::class, 'get_bank_balances']);
    $routes->get('get_agent_list', [DashboardController::class, 'get_agent_list']);
    $routes->get('get_transport_agent_list', [DashboardController::class, 'get_transport_agent_list']);
    $routes->get('get_all_transport_agent_list', [DashboardController::class, 'get_all_transport_agent_list']);
    $routes->get('get_all_NA_tours', [DashboardController::class, 'get_all_NA_tours']);
    $routes->get('get_liabilities/(:any)/(:any)', [DashboardController::class, 'get_liabilities']);
    $routes->get('get_monthly_data', [DashboardController::class, 'get_monthly_data']);
});

$routes->group('dashboard2', static function ($routes) {
    $routes->get('tiles', [NewDashboardController::class, 'index'],  ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
    $routes->get('searchTours', [NewDashboardController::class, 'searchTours']);
    $routes->get('getRecentTours', [NewDashboardController::class, 'getRecentTours']);
    $routes->post('deleteTour', [NewDashboardController::class, 'deleteTour']);

    $routes->group('tour_search', static function ($routes) {
        $routes->get('tour_quotation', [NewDashboardController::class, 'printQuotation'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('new_quotation', [NewDashboardController::class, 'newQuotation'], ['filter' => ['UserSessionFilter']]);
        $routes->get('reservation_card', [NewDashboardController::class, 'reservationCard'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('itinerary_print', [NewDashboardController::class, 'itineraryPrint'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('timeline', [NewDashboardController::class, 'timeline'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
    });

    $routes->group('transport', static function ($routes) {

        $routes->group('driver_allow', static function ($routes) {
            $routes->get('tour_transport', [DriverAllocationController::class, 'tourTransport'],  ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('pending_tours', [DriverAllocationController::class, 'pendingTours'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('driver_allocation', [DriverAllocationController::class, 'driverAllocation']);
            $routes->post('request_driver_allocation', [DriverAllocationController::class, 'requestDriverAllocation']);
            $routes->post('approve_driver_allocation_request', [DriverAllocationController::class, 'approveDriverAllocationRequest']);
            $routes->post('remove_driver', [DriverAllocationController::class, 'removeDriverAllocation']);
            $routes->get('driver_allocation_details', [DriverAllocationController::class, 'driverAllocationDetails']);
            $routes->get('available_drivers', [DriverAllocationController::class, 'getAvailableDrivers']);
            $routes->get('available_tours', [DriverAllocationController::class, 'getAvailableTours']);
            $routes->get('allocated_tours_json', [DriverAllocationController::class, 'getAllocatedTours']);
            $routes->get('allocated_tours', [DriverAllocationController::class, 'allocatedToursReport'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('get_driver_tours', [DriverAllocationController::class, 'getDriverTours']);
            $routes->get('get_driver_payment_status', [DriverAllocationController::class, 'getDriverPaymentStatus']);
            $routes->get('driver_availability_checker', [DriverAllocationController::class, 'getDriverAvailabilityChecker']);
            $routes->get('get_driver_log', [DriverAllocationController::class, 'getDriverLog']);
            $routes->get('advance_request', [DriverAllocationController::class, 'advanceRequest'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('suggested_advance', [DriverAllocationController::class, 'suggestedAdvance']);
            $routes->get('advance_request_history', [DriverAllocationController::class, 'advancerequesthistory']);
            $routes->post('advance_request_add', [DriverAllocationController::class, 'advanceRequestSubmit']);
            $routes->post('update_advance_request', [DriverAllocationController::class, 'updateAdvanceRequest']);
            $routes->post('delete_advance_request', [DriverAllocationController::class, 'deleteAdvanceRequest']);
        });

        $routes->group('driver_advance', static function ($routes) {
            $routes->get('/', [DriverAdvancePaymentController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('payment_history', [DriverAdvancePaymentController::class, 'payment_history']);
            $routes->post('tour_transport', [DriverAdvancePaymentController::class, 'tour_transport']);
            $routes->post('update_advance', [DriverAdvancePaymentController::class, 'update_advance']);
            $routes->post('delete_advance', [DriverAdvancePaymentController::class, 'delete_advance']);
            $routes->post('transfer_amount', [DriverAdvancePaymentController::class, 'transfer_amount']);
        });

        $routes->group('transport_portal', static function ($routes) {
            $routes->get('/', [TransportPortalController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('upcoming_tours', [TransportPortalController::class, 'upcomingTours'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('ongoing_tours', [TransportPortalController::class, 'ongoingTours'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('completed_tours', [TransportPortalController::class, 'completedTours'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('expense_accounts', [TransportPortalController::class, 'getExpenseAccounts'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('admin_save_meter_reading', [TransportPortalController::class, 'adminSaveMeterReading'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('add_meter_reading_photo', [TransportPortalController::class, 'addMeterReadingPhoto'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('admin_save_expense', [TransportPortalController::class, 'adminSaveExpense'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('admin_update_expense', [TransportPortalController::class, 'adminUpdateExpense'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('admin_delete_expense', [TransportPortalController::class, 'adminDeleteExpense'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('delete_meter_reading', [TransportPortalController::class, 'deleteMeterReading'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('admin_finalize_tour', [TransportPortalController::class, 'adminFinalizeTour'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('pending_settlement_count', [TransportPortalController::class, 'pendingSettlementCount'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('send_reminders', [TransportPortalController::class, 'sendReminders'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('send_reminder_to_driver', [TransportPortalController::class, 'sendReminderToDriver'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        });

        $routes->get('transport_ongoing', [TransportPortalController::class, 'ongoingPanel'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);

        $routes->group('shop_allow', static function ($routes) {
            $routes->get('/', [ShopAllocationController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('get_shops', [ShopAllocationController::class, 'get_shops']);
            $routes->post('save_shops', [ShopAllocationController::class, 'save_shops']);
            $routes->get('get_allocated_shops', [ShopAllocationController::class, 'get_allocated_shops']);
        });

        $routes->group('driver_expense', static function ($routes) {
            $routes->get('/', [DriverExpensesController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('add_expenses', [DriverExpensesController::class, 'add_expenses']);
            $routes->post('delete_expenses', [DriverExpensesController::class, 'delete_expenses']);
            $routes->post('get_tour_expenses_details', [DriverExpensesController::class, 'get_tour_expenses']);
            $routes->post('get_tour_pending_expenses', [DriverExpensesController::class, 'get_tour_pending_expenses']);
            $routes->post('delete_pending_expense', [DriverExpensesController::class, 'delete_pending_expense']);
            $routes->post('admin_delete_expense', [DriverExpensesController::class, 'admin_delete_expense']);
            $routes->post('admin_update_expense', [DriverExpensesController::class, 'admin_update_expense']);
            $routes->post('send_request', [DriverExpensesController::class, 'send_request']);
            $routes->post('send_all_requests', [DriverExpensesController::class, 'send_all_requests']);
            $routes->post('approve_expense', [DriverExpensesController::class, 'approve_expense']);
            $routes->post('reject_expense', [DriverExpensesController::class, 'reject_expense']);
            $routes->post('approve_tour_expenses', [DriverExpensesController::class, 'approve_tour_expenses']);
            $routes->post('reject_tour_expenses', [DriverExpensesController::class, 'reject_tour_expenses']);
        });

        $routes->group('driver_logsheet', static function ($routes) {
            $routes->get('/', [DriverLogsheetController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('get_logsheet_mileage/(:num)/(:any)', [DriverLogsheetController::class, 'get_logsheet_mileage']);
            $routes->post('get_tot_day', [DriverLogsheetController::class, 'get_totday_log_sheet']);
            $routes->get('get_t_agent_absolute_balance', [DriverLogsheetController::class, 'get_t_agent_absolute_balance']);
            $routes->post('add_logsheet_mileage', [DriverLogsheetController::class, 'add_logsheet_mileage']);
        });

        $routes->group('driver_settlement', static function ($routes) {
            $routes->get('/', [DriverPaymentSettlementController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('get_balance_payment_details', [DriverPaymentSettlementController::class, 'get_balance_payment_details']);
            $routes->post('setteled_payment', [DriverPaymentSettlementController::class, 'complete_payment']);
            $routes->post('edit_total_transport', [DriverPaymentSettlementController::class, 'edit_total_transport']);
            $routes->get('driver_settlement_request', [DriverPaymentSettlementController::class, 'driversettlementrequest']);
            $routes->post('driver_settlement_request_send', [DriverPaymentSettlementController::class, 'driversettlementrequestsend']);
        });


        $routes->group('fund_return', static function ($routes) {
            $routes->get('/', [DriverFundReturnController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('submit_fund_return', [DriverFundReturnController::class, 'submit_fund_return']);
            $routes->post('get_refund_history', [DriverFundReturnController::class, 'get_refund_history']);
        });

        $routes->group('driver_report', static function ($routes) {
            $routes->get('/', [App\Controllers\Driver\DriverPortalController::class, 'driverReport'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('override_meter_reading', [App\Controllers\Driver\DriverPortalController::class, 'overrideMeterReading'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('save_mileage_amendment', [App\Controllers\Driver\DriverPortalController::class, 'saveMileageAmendment'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        });
    });

    $routes->group('hotel_management', static function ($routes) {

        $routes->group('hotel_reservation', static function ($routes) {
            $routes->get('/', [HotelReservationController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('send_email', [HotelReservationController::class, 'send_email']);
            $routes->post('get_mail', [HotelReservationController::class, 'get_mail']);
        });

        $routes->group('hotel_booking', static function ($routes) {
            $routes->get('/', [HotelConfirmationController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->get('getAllDetails/(:any)/(:any)/(:any)/(:any)', [HotelConfirmationController::class, 'getAllDetails']);
            // confirm_reservation / cancel_booking routes removed: the manual Hotel Confirmation
            // step is bypassed — payment_request() now flags Hotel requests visible immediately,
            // and booking_confirmed_flag is set at approval time in TourAccountsCheckController.
            // $routes->post('save_booking', [HotelManagementController::class, 'save_booking']);
            // $routes->post('confirm_booking', [HotelManagementController::class, 'confirm_booking']);
            // $routes->post('get_booking_details', [HotelManagementController::class, 'get_booking_details']);
        });

        $routes->group('hotel_payment', static function ($routes) {
            $routes->get('/', [HotelPaymentController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
            $routes->post('get_room_rates', [HotelPaymentController::class, 'get_room_rates']);
            $routes->post('hotel_pay', [HotelPaymentController::class, 'hotel_payment']);
        });
    });

    $routes->group('voucher_auth', static function ($routes) {
        $routes->get('/', [VoucherAuthenticationController::class, 'auth'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('getVoucherDetails', [VoucherAuthenticationController::class, 'getVoucherDetails']);
        $routes->post('update', [VoucherAuthenticationController::class, 'update']);
        $routes->post('update_tour', [VoucherAuthenticationController::class, 'update_tour']);
    });
});

// navigation - route : base_url/navigation
// $routes->group('navigation', static function ($routes) {
//     $routes->get('/', [NavigationController::class, 'index']); // route : base_url/dashboard
// });

$routes->get('my_profile', [ProfileController::class, 'index'], ['filter' => 'UserSessionFilter']); // route : base_url/my_profile
$routes->post('my_profile/change_password', [ProfileController::class, 'change_password'], ['filter' => 'UserSessionFilter']); // route : base_url/my_profile
$routes->get('my_profile/update_contact_info', [ProfileController::class, 'update_contact_info'], ['filter' => 'UserSessionFilter']); // route : base_url/my_profile
$routes->post('my_profile/upload_profile_picture', [ProfileController::class, 'upload_profile_picture'], ['filter' => 'UserSessionFilter']); // route : base_url/upload_profile_picture


// Settings Routes - route : base_url/settings
$routes->group('settings', static function ($routes) {
    //MenuCategoryController - route : base_url/settings/menu_category
    $routes->group('menu_category', static function ($routes) {
        $routes->get('/', [MenuCategoryController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_category
        $routes->post('create', [MenuCategoryController::class, 'create'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_category/create
        $routes->get('show/(:num)', [MenuCategoryController::class, 'show'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_category/show/$1
        $routes->put('update/(:num)', [MenuCategoryController::class, 'update'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_category/update/$1
        $routes->delete('delete/(:num)', [MenuCategoryController::class, 'delete'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_category/delete/$1
    });

    //MenuListController - route : base_url/settings/menu_list
    $routes->group('menu_list', static function ($routes) {
        $routes->get('/', [MenuListController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_list
        $routes->post('create', [MenuListController::class, 'create'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_list/create
        $routes->get('show/(:num)', [MenuListController::class, 'show'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_list/show/$1
        $routes->put('update/(:num)', [MenuListController::class, 'update'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_list/update/$1
        $routes->delete('delete/(:num)', [MenuListController::class, 'delete'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_list/delete/$1
    });

    //GroupProfileController - route : base_url/settings/group_profiles
    $routes->group('group_profile', static function ($routes) {
        $routes->get('/', [GroupProfileController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/group_profiles
        $routes->post('create', [GroupProfileController::class, 'create'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/group_profiles/create
        $routes->get('show/(:num)', [GroupProfileController::class, 'show'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/group_profiles/show/$1
        $routes->put('update/(:num)', [GroupProfileController::class, 'update'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/group_profiles/update/$1
        $routes->delete('delete/(:num)', [GroupProfileController::class, 'delete'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/group_profiles/delete/$1
    });

    //UserAccountController - route : base_url/settings/user_account
    $routes->group('user_account', static function ($routes) {
        $routes->get('/', [UserAccountController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/user_account
        $routes->post('create', [UserAccountController::class, 'create'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/user_account/create
        $routes->get('show/(:num)', [UserAccountController::class, 'show'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/user_account/show/$1
        $routes->put('update/(:num)', [UserAccountController::class, 'update'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/user_account/update/$1
        $routes->delete('delete/(:num)', [UserAccountController::class, 'delete'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/user_account/delete/$1
        $routes->get('update_access/(:num)', [UserAccountController::class, 'update_access'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/user_account/update_access/$1
    });

    //GroupPrivilegeController - route : base_url/settings/group_privilege
    $routes->group('group_privilege', static function ($routes) {
        $routes->get('(:num)', [GroupPrivilegeController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/group_privilege/$1
        $routes->get('add/(:num)/(:num)', [GroupPrivilegeController::class, 'add'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/group_privilege/add/$group_id/$menu_id
        $routes->get('remove/(:num)', [GroupPrivilegeController::class, 'remove'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/group_privilege/remove/$id
    });

    //SettingsController - route : base_url/settings/backup_database
    $routes->group('backup_database', static function ($routes) {
        $routes->get('/', [UtilityDataController::class, 'db_backup_view'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/backup_database
    });

    //SettingsController - route : base_url/settings/manual_update
    $routes->group('manual_update', static function ($routes) {
        $routes->get('/', [ManualUpdateController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/backup_database
        $routes->post('update', [ManualUpdateController::class, 'update'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/manual_update/update
        $routes->post('des_update', [ManualUpdateController::class, 'des_update'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/manual_update/des_update
        $routes->post('des_other_update', [ManualUpdateController::class, 'des_other_update'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/manual_update/des_update
        $routes->post('hotel_voucher_accept', [ManualUpdateController::class, 'hotel_voucher_accept'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/manual_update/des_update
        $routes->post('entrance_update', [ManualUpdateController::class, 'entrance_update'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/manual_update/entrance_update
        $routes->post('other_update', [ManualUpdateController::class, 'other_update'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/manual_update/other_update
        $routes->post('transport_update', [ManualUpdateController::class, 'transport_update'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/manual_update/transport_update
        $routes->post('voucher_date_update', [ManualUpdateController::class, 'voucher_date_update'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/manual_update/voucher_date_update
        $routes->post('verify_tour', [ManualUpdateController::class, 'verify_tour'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('debtor_receipt_generate', [ManualUpdateController::class, 'debtor_receipt_generate'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('bank_recon_record_insert', [ManualUpdateController::class, 'bank_recon_record_insert'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('lunch_outstanding_insert', [ManualUpdateController::class, 'lunch_outstanding_insert']);
    });

    // Year end process - route : base_url/settings/year_end_process
    $routes->group('year_end_process', static function ($routes) {
        $routes->get('/', [YearEndProcessController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('fetch/(:any)', [YearEndProcessController::class, 'fetch'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('submit', [YearEndProcessController::class, 'process'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('submit_last', [YearEndProcessController::class, 'process_last'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
    });
});


// staff - route : base_url/staff
$routes->group('staff', static function ($routes) {
    $routes->get('registration', [StaffMemberController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/staff/registration
    $routes->post('create', [StaffMemberController::class, 'create']); // route : base_url/staff/create
    $routes->get('directory', [StaffMemberController::class, 'all']); // route : base_url/staff/directory
    $routes->post('update', [StaffMemberController::class, 'update']); // route : base_url/staff/update
});

// master files - route : base_url/master_files
$routes->group('master_files', static function ($routes) {
    $routes->group('supplier', static function ($routes) {
        $routes->get('/', [SupplierController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/supplier
        $routes->get('all', [SupplierController::class, 'all'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/supplier/all
        $routes->get('get_branches', [SupplierController::class, 'get_branches']); // route : base_url/master_files/supplier/get_branches
        $routes->post('create', [SupplierController::class, 'create']); // route : base_url/master_files/supplier/create
        $routes->post('bulk_add', [SupplierController::class, 'bulk_add']); // route : base_url/master_files/supplier/bulk_add
        $routes->post('update/(:num)', [SupplierController::class, 'update']); // route : base_url/master_files/supplier/update/:id
        $routes->get('delete', [SupplierController::class, 'delete']); // route : base_url/master_files/supplier/delete
        $routes->get('get_supplier_data/(:any)', [SupplierController::class, 'get_supplier_data']);
        $routes->get('next_hotel_code', [SupplierController::class, 'next_hotel_code']); // route : base_url/master_files/supplier/next_hotel_code
        $routes->get('new_driver_index', [SupplierController::class, 'new_driver_index']);
        $routes->get('all_drivers', [SupplierController::class, 'all_drivers']);
        $routes->post('toggle_login', [SupplierController::class, 'toggle_login']);
        $routes->post('toggle_availability', [SupplierController::class, 'toggle_availability']);
    });
    $routes->group('entrance_fee', static function ($routes) {
        $routes->get('/', [EntranceFeeController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/entrance_fee
        $routes->post('create', [EntranceFeeController::class, 'create']); // route : base_url/master_files/entrance_fee/create
        $routes->post('bulk_add', [EntranceFeeController::class, 'bulk_add']); // route : base_url/master_files/entrance_fee/create
        $routes->get('show/(:num)', [EntranceFeeController::class, 'show']); // route : base_url/master_files/entrance_fee/show/$1
        $routes->post('update/(:num)',  [EntranceFeeController::class, 'update']); // route : base_url/master_files/entrance_fee/update
        $routes->get('delete',  [EntranceFeeController::class, 'delete']); // route : base_url/master_files/entrance_fee/delete
    });

    $routes->group('customer', static function ($routes) {
        $routes->get('/', [CustomerController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/customer
        $routes->get('all', [CustomerController::class, 'all']); // route : base_url/master_files/customer/all
        $routes->post('create', [CustomerController::class, 'create']); // route : base_url/master_files/customer/create
        $routes->post('update/(:num)/(:any)', [CustomerController::class, 'update']); // route : base_url/master_files/customer/update/:id
        $routes->get('delete', [CustomerController::class, 'delete']); // route : base_url/master_files/customer/delete
        $routes->get('get', [CustomerController::class, 'getCustomerDetailsById']); // route : base_url/master_files/customer/get
    });

    $routes->group('shop_registration', static function ($routes) {
        $routes->get('/', [ShopRegistrationController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/customer
        $routes->get('all', [ShopRegistrationController::class, 'all']); // route : base_url/master_files/customer/all
        $routes->post('create', [ShopRegistrationController::class, 'create']); // route : base_url/master_files/customer/create
        $routes->post('update/(:num)/(:any)', [ShopRegistrationController::class, 'update']); // route : base_url/master_files/customer/update/:id
        $routes->get('delete', [ShopRegistrationController::class, 'delete']); // route : base_url/master_files/customer/delete
        $routes->get('get', [ShopRegistrationController::class, 'getCustomerDetailsById']); // route : base_url/master_files/customer/get
    });

    $routes->group('room_type', static function ($routes) {
        $routes->get('/', [RoomTypeController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/room_type
        $routes->get('all', [RoomTypeController::class, 'all_room_types']); // route : base_url/master_files/room_type/all_room_types
        $routes->post('create', [RoomTypeController::class, 'create']); // route : base_url/master_files/room_type/create
        $routes->get('show/(:num)', [RoomTypeController::class, 'show']); // route : base_url/master_files/room_type/show/$1
        $routes->post('update',  [RoomTypeController::class, 'update']); // route : base_url/master_files/room_type/update
        $routes->get('delete',  [RoomTypeController::class, 'delete']); // route : base_url/master_files/room_type/delete
    });

    $routes->group('hotel_rate', static function ($routes) {
        $routes->get('/', [HotelRateController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/hotel_rate
        $routes->get('all', [HotelRateController::class, 'all'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/hotel_rate/all
        $routes->get('old_hotel_rates/(:any)', [HotelRateController::class, 'old_hotel_rates']); // route : base_url/master_files/hotel_rate/selected_hotel_rate
        $routes->get('show/(:any)', [HotelRateController::class, 'show']); // route : base_url/master_files/hotel_rate/show
        $routes->post('create', [HotelRateController::class, 'create']); // route : base_url/master_files/hotel_rate/create
        $routes->post('bulk_add', [HotelRateController::class, 'bulk_add']); // route : base_url/master_files/hotel_rate/bulk_add
        $routes->post('update/(:num)', [HotelRateController::class, 'update']); // route : base_url/master_files/hotel_rate/update/:id
        $routes->get('delete', [HotelRateController::class, 'delete']); // route : base_url/master_files/hotel_rate/delete
        $routes->get('get_location', [HotelRateController::class, 'get_location']); // route : base_url/master_files/hotel_rate/get_location
        $routes->get('get_hotels', [HotelRateController::class, 'get_hotels']); // route : base_url/master_files/hotel_rate/get_hotels
        $routes->get('date_overlap', [HotelRateController::class, 'date_overlap']); // route : base_url/master_files/hotel_rate/date_overlap
        $routes->get('get_start_date', [HotelRateController::class, 'get_start_date']); // route : base_url/master_files/hotel_rate/date_overlap
        $routes->post('ai_extract', [HotelRateController::class, 'ai_extract']); // route : base_url/master_files/hotel_rate/ai_extract
        $routes->post('ai_confirm', [HotelRateController::class, 'ai_confirm']); // route : base_url/master_files/hotel_rate/ai_confirm
    });

    $routes->group('transfer_milages', static function ($routes) {
        $routes->get('/', [TransferMilageController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/transfer_milages
        $routes->post('create', [TransferMilageController::class, 'create']); // route : base_url/master_files/transfer_milages/create
        $routes->post('bulk_add', [TransferMilageController::class, 'bulk_add']); // route : base_url/master_files/transfer_milages/bulk_add
        $routes->get('show/(:num)', [TransferMilageController::class, 'show']); // route : base_url/master_files/transfer_milages/show/$1
        $routes->post('update/(:num)',  [TransferMilageController::class, 'update']); // route : base_url/master_files/transfer_milages/update
        $routes->get('delete',  [TransferMilageController::class, 'delete']); // route : base_url/master_files/transfer_milages/delete
    });

    $routes->group('vehicle', static function ($routes) {
        $routes->get('/', [VehicleController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/vehicle
        $routes->post('create', [VehicleController::class, 'create']); // route : base_url/master_files/vehicle/create
        $routes->get('show/(:num)', [VehicleController::class, 'show']); // route : base_url/master_files/room_type/show/$1
        $routes->post('update',  [VehicleController::class, 'update']); // route : base_url/master_files/room_type/update
        $routes->get('deleteVehicle', [VehicleController::class, 'deleteVehicle']); // route : base_url/master_files/vehicle/deleteVehicle
    });

    $routes->group('extra_kilometers', static function ($routes) {
        $routes->get('/', [ExtraKilometeresController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/extra_kilometers
        $routes->post('create', [ExtraKilometeresController::class, 'create']); // route : base_url/master_files/extra_kilometers/create
        $routes->post('bulk_add', [ExtraKilometeresController::class, 'bulk_add']); // route : base_url/master_files/extra_kilometers/bulk_add
        $routes->get('show/(:num)', [ExtraKilometeresController::class, 'show']); // route : base_url/master_files/extra_kilometers/show/$1
        $routes->post('update/(:num)',  [ExtraKilometeresController::class, 'update']); // route : base_url/master_files/extra_kilometers/update
        $routes->get('delete',  [ExtraKilometeresController::class, 'delete']); // route : base_url/master_files/extra_kilometers/delete
    });

    $routes->group('job_role', static function ($routes) {
        $routes->get('/', [JobRoleController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/job_role
        $routes->post('create', [JobRoleController::class, 'create']); // route : base_url/master_files/job_role/create
        $routes->get('show/(:num)', [JobRoleController::class, 'show']); // route : base_url/master_files/job_role/show/$1
        $routes->post('update/(:num)', [JobRoleController::class, 'update']); // route : base_url/master_files/job_role/update/$1
        $routes->get('delete', [JobRoleController::class, 'delete']); // route : base_url/master_files/job_role/delete

        // Assignment routes
        $routes->get('assignments', [JobRoleController::class, 'assignments'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/job_role/assignments
        $routes->post('assignments/create', [JobRoleController::class, 'createAssignment']); // route : base_url/master_files/job_role/assignments/create
        $routes->get('assignments/show/(:num)', [JobRoleController::class, 'showAssignment']); // route : base_url/master_files/job_role/assignments/show/$1
        $routes->post('assignments/update/(:num)', [JobRoleController::class, 'updateAssignment']); // route : base_url/master_files/job_role/assignments/update/$1
        $routes->get('assignments/delete', [JobRoleController::class, 'deleteAssignment']); // route : base_url/master_files/job_role/assignments/delete
        $routes->get('get_user_employees', [JobRoleController::class, 'getUserEmployees']); // route : base_url/master_files/job_role/get_user_employees
    });

    // tf-paydesk-r1: the Avenir Bank Account Master and the suppliers' bank details
    $routes->group('bank_accounts', static function ($routes) {
        $routes->get('/', [TourPaymentMastersController::class, 'bank_accounts'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/bank_accounts
        $routes->post('save', [TourPaymentMastersController::class, 'bank_accounts_save']);
        $routes->get('list', [TourPaymentMastersController::class, 'bank_accounts_list']);
    });
    $routes->group('supplier_bank', static function ($routes) {
        $routes->get('/', [TourPaymentMastersController::class, 'supplier_bank'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/supplier_bank
        $routes->get('get', [TourPaymentMastersController::class, 'supplier_bank_get']);
        $routes->post('save', [TourPaymentMastersController::class, 'supplier_bank_save']);
        $routes->post('verify', [TourPaymentMastersController::class, 'supplier_bank_verify']);
    });

    $routes->group('daily_usd_rate', static function ($routes) {
        $routes->get('/', [DailyUsdRateController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/master_files/daily_usd_rate
        $routes->post('create', [DailyUsdRateController::class, 'create']); // route : base_url/master_files/daily_usd_rate/create
        $routes->get('show/(:num)', [DailyUsdRateController::class, 'show']); // route : base_url/master_files/daily_usd_rate/show/$1
        $routes->post('update/(:num)', [DailyUsdRateController::class, 'update']); // route : base_url/master_files/daily_usd_rate/update/$1
        $routes->get('delete', [DailyUsdRateController::class, 'delete']); // route : base_url/master_files/daily_usd_rate/delete
        $routes->post('quick_save', [DailyUsdRateController::class, 'quick_save']); // route : base_url/master_files/daily_usd_rate/quick_save
        $routes->get('today', [DailyUsdRateController::class, 'today']); // route : base_url/master_files/daily_usd_rate/today
    });
});

// accounts - route : base_url/accounts
$routes->group('accounts', static function ($routes) {
    $routes->group('acc_running_records', static function ($routes) {
        $routes->get('/', [AccRunningController::class, 'index']); // route : base_url/accounts/acc_running_records
    });

    $routes->get('/', [ChartOfAccountsController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts
    $routes->get('title_accounts', [ChartOfAccountsController::class, 'title_accounts']); // route : base_url/accounts/title_accounts
    $routes->get('heading_accounts', [ChartOfAccountsController::class, 'heading_accounts']); // route : base_url/accounts/heading_accounts
    $routes->get('get_accounts_list', [ChartOfAccountsController::class, 'getChartOfAccounts']); // route : base_url/accounts/get_accounts_list

    $routes->get('heading_acc_transactions', [ChartOfAccountsController::class, 'heading_acc_transactions']); // route : base_url/accounts/heading_acc_transactions
    $routes->get('posting_acc_transactions', [ChartOfAccountsController::class, 'posting_acc_transactions']); // route : base_url/accounts/posting_acc_transactions

    $routes->group('create', static function ($routes) {
        $routes->post('title_account', [ChartOfAccountsController::class, 'createTitleAccount']); // route : base_url/accounts/create/title_account
        $routes->post('heading_account', [ChartOfAccountsController::class, 'createHeadingAccount']); // route : base_url/accounts/create/heading_account
        $routes->post('posting_account', [ChartOfAccountsController::class, 'createPostingAccount']); // route : base_url/accounts/create/posting_account
    });
    $routes->group('update', static function ($routes) {
        $routes->post('title_account', [ChartOfAccountsController::class, 'updateTitleAccount']); // route : base_url/accounts/update/title_account
        $routes->post('heading_account', [ChartOfAccountsController::class, 'updateHeadingAccount']); // route : base_url/accounts/update/heading_account
        $routes->post('posting_account', [ChartOfAccountsController::class, 'updatePostingAccount']); // route : base_url/accounts/update/posting_account
    });
    $routes->group('delete', static function ($routes) {
        $routes->post('title_account', [ChartOfAccountsController::class, 'deleteTitleAccount']); // route : base_url/accounts/delete/title_account
        $routes->post('heading_account', [ChartOfAccountsController::class, 'deleteHeadingAccount']); // route : base_url/accounts/delete/heading_account
        $routes->post('posting_account', [ChartOfAccountsController::class, 'deletePostingAccount']); // route : base_url/accounts/delete/posting_account
    });

    $routes->group('balance_sheet', static function ($routes) {
        $routes->get('/', [BalanceSheetController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/balance_sheet
    });

    $routes->group('outstanding_report', static function ($routes) {
        $routes->get('/', [OutstandingController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/balance_sheet
    });

    $routes->group('trial_balance', static function ($routes) {
        $routes->get('/', [TrialBalanceController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/trial_balance
        $routes->get('report', [TrialBalanceController::class, 'load_report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/trial_balance
        $routes->get('load_accountant_report', [TrialBalanceController::class, 'load_accountant_report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/trial_balance
        $routes->get('trial_balance_details/(:any)', [TrialBalanceController::class, 'trial_balance_details']); // route : base_url/accounts/trial_balance
    });

    $routes->group('profit_loss', static function ($routes) {
        $routes->get('/', [ProfitAndLossController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/profit_loss
    });

    // route : base_url/accounts/creditor_invoice
    $routes->group('creditor_invoice', static function ($routes) {
        $routes->get('/', [CreditorInvoiceController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/creditor_payment
        $routes->post('complete_payment', [CreditorInvoiceController::class, 'complete_payment']); // route : base_url/accounts/creditor_payment/complete_payment
        $routes->get('report', [CreditorInvoiceController::class, 'credit_invoice_report']); // route : base_url/accounts/creditor_invoice/report
        $routes->get('voucher_authentication', [CreditorInvoiceController::class, 'voucher_authentication'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/creditor_invoice/voucher_authentication

        $routes->get('credited_report', [CreditorInvoiceController::class, 'credited_invoice_report']); // route : base_url/accounts/creditor_invoice/report
        $routes->post('get_details', [CreditorInvoiceController::class, 'get_details']); // route : base_url/accounts/creditor_invoice/get_details
        $routes->post('get_tour_paymets', [CreditorInvoiceController::class, 'get_tour_paymets']); // route : base_url/accounts/creditor_invoice/get_tour_details
        $routes->post('get_tour_details', [CreditorInvoiceController::class, 'get_tour_details']); // route : base_url/accounts/creditor_invoice/get_tour_details
        $routes->get('view_expences', [CreditorInvoiceController::class, 'get_expences']); // route : base_url/report/location_wise_stock/view_stock/:location/:transport_flag
        $routes->get('expenses_delete', [CreditorInvoiceController::class, 'expenses_delete']); // route : base_url/accounts/creditor_invoice/expenses_delete
        $routes->get('expenses_report', [CreditorInvoiceController::class, 'expenses_report']); // route : base_url/accounts/creditor_invoice/expenses_report
        $routes->get('view_operational_expences', [CreditorInvoiceController::class, 'view_operational_expences']);
        $routes->post('search_voucher_number', [CreditorInvoiceController::class, 'search_voucher_number']); // route : base_url/accounts/creditor_payment/get_transaction_history
    });

    // route : base_url/accounts/new_creditor_invoice
    $routes->group('new_creditor_invoice', static function ($routes) {
        $routes->get('/', [NewCreditorInvoiceController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/new_creditor_invoice
        $routes->post('complete_payment', [NewCreditorInvoiceController::class, 'complete_payment']); // route : base_url/accounts/new_creditor_invoice/complete_payment
        $routes->get('report', [NewCreditorInvoiceController::class, 'credit_invoice_report']); // route : base_url/accounts/new_creditor_invoice/voucher_authentication
        $routes->get('credited_report', [NewCreditorInvoiceController::class, 'credited_invoice_report']); // route : base_url/accounts/new_creditor_invoice/report
        $routes->post('get_details', [NewCreditorInvoiceController::class, 'get_details']); // route : base_url/accounts/new_creditor_invoice/get_details
        $routes->post('get_tour_details', [NewCreditorInvoiceController::class, 'get_tour_details']); // route : base_url/accounts/new_creditor_invoice/get_tour_details
        $routes->get('view_expences', [NewCreditorInvoiceController::class, 'get_expences']); // route : base_url/report/location_wise_stock/view_stock/:location/:transport_flag
        $routes->get('expenses_delete', [NewCreditorInvoiceController::class, 'expenses_delete']); // route : base_url/accounts/new_creditor_invoice/expenses_delete
        $routes->get('expenses_report', [NewCreditorInvoiceController::class, 'expenses_report']); // route : base_url/accounts/new_creditor_invoice/expenses_report
        $routes->get('view_operational_expences', [NewCreditorInvoiceController::class, 'view_operational_expences']);
        $routes->get('get_transaction_history', [NewCreditorInvoiceController::class, 'get_transaction_history']); // route : base_url/accounts/creditor_payment/get_transaction_history
    });

    // route : base_url/accounts/creditor_payment
    $routes->group('creditor_payment', static function ($routes) {
        $routes->get('/', [CreditorPaymentController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/creditor_payment
        $routes->post('complete_payment', [CreditorPaymentController::class, 'complete_payment']); // route : base_url/accounts/creditor_payment/complete_payment
        $routes->get('get_transaction_history', [CreditorPaymentController::class, 'get_transaction_history']); // route : base_url/accounts/creditor_payment/get_transaction_history
    });

    // route : base_url/accounts/usd_withdrawal
    $routes->group('usd_withdrawal', static function ($routes) {
        $routes->get('/', [UsdWithdrawalController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('process', [UsdWithdrawalController::class, 'process']);
        $routes->get('print', [UsdWithdrawalController::class, 'print']);
        $routes->get('history', [UsdWithdrawalController::class, 'history'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
    });


    // route : base_url/accounts/debtor_payment
    $routes->group('debtor_payment', static function ($routes) {
        $routes->get('/', [DebtorPaymentController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('date_filter', [DebtorPaymentController::class, 'date_filter']);
        $routes->post('complete_payment', [DebtorPaymentController::class, 'complete_payment']);
        $routes->get('report', [DebtorPaymentController::class, 'debtor_receipt_report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
    });

    // route : base_url/accounts/debtor_invoice
    $routes->group('debtor_invoice', static function ($routes) {
        $routes->get('/', [DebtorInvoiceController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        // $routes->post('complete_payment', [DebtorInvoiceController::class, 'complete_payment']); // route : base_url/accounts/creditor_payment/complete_payment
        $routes->post('get_advance_payment', [DebtorInvoiceController::class, 'get_advance_payment']);
        $routes->get('report', [DebtorInvoiceController::class, 'debtor_invoice_report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('cancellation_request', [DebtorInvoiceController::class, 'debtor_cancellation_request']);
        $routes->get('cancellation', [DebtorInvoiceController::class, 'debtor_cancellation']);
        $routes->post('get', [DebtorInvoiceController::class, 'get_details']);
        $routes->get('get_cancellation_request', [DebtorInvoiceController::class, 'cancellation_request_report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('get_cancellation_request_final', [DebtorInvoiceController::class, 'cancellation_request_report_final'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('get_cancellation', [DebtorInvoiceController::class, 'cancellation_report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('arrival_report', [DebtorInvoiceController::class, 'arrival_report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('arrival_report_print', [DebtorInvoiceController::class, 'debtor_invoice_report_print']);
        $routes->get('print_deptor_invoice_report', [DebtorInvoiceController::class, 'debtor_invoice_report_print_n']); // route : base_url/accounts/debtor_invoice/get_tour_details
        $routes->get('cancellation_fee_print', [DebtorInvoiceController::class, 'cancellation_fee_print']); // route : base_url/accounts/debtor_invoice/cancellation_fee_print
    });

    // route : base_url/accounts/service_invoice
    $routes->group('service_invoice', static function ($routes) {
        $routes->get('/', [ServiceInvoiceController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
    });

    // route : base_url/accounts/debtor_invoice_new
    $routes->group('debtor_invoice_new', static function ($routes) {
        $routes->get('/', [DebtorInvoiceNewController::class, 'index']);
        $routes->post('complete_payment', [DebtorInvoiceNewController::class, 'complete_payment']);
        $routes->get('tour_details', [DebtorInvoiceNewController::class, 'get_tour_details']);
        $routes->get('print', [DebtorInvoiceNewController::class, 'print']);
        $routes->get('sent_mail', [DebtorInvoiceNewController::class, 'sent']); // route : base_url/accounts/debtor_invoice_new/sent_mail
        $routes->get('print', [DebtorInvoiceNewController::class, 'print']);
        $routes->post('send_receipt', [DebtorInvoiceNewController::class, 'send_receipt']); // route : base_url/accounts/debtor_invoice_new/sent_mail

        $routes->get('additional_inv', [DebtorInvoiceNewController::class, 'additional_inv']);
    });

    // route : base_url/accounts/                               
    $routes->group('debit_note', static function ($routes) {
        $routes->get('/', [DebitNoteController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);;
        $routes->get('fetch/(:any)', [DebitNoteController::class, 'fetch']);
        $routes->post('complete_payment', [DebitNoteController::class, 'complete_payment']);
    });

    // $routes->group('debtor_payment', static function ($routes) {
    //     $routes->get('/', [DebtorPaymentController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/debtor_payment
    //     $routes->post('complete_payment', [DebtorPaymentController::class, 'complete_payment']); // route : base_url/accounts/debtor_payment/complete_payment
    // });

    // route : base_url/accounts/cred                             
    $routes->group('credit_note', static function ($routes) {
        $routes->get('/', [CreditNoteController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('complete_payment', [CreditNoteController::class, 'complete_payment']);
    });

    $routes->group('bank_statement', static function ($routes) {
        $routes->get('/', [BankStatementController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/bank_statement
    });

    $routes->group('bank_reconcil', static function ($routes) {
        $routes->get('/', [BankReconsillationController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/bank_reconcil
        $routes->post('get_req_items', [BankReconsillationController::class, 'get_req_items']); // route : base_url/accounts/get_req_items
        $routes->post('get_realised_sum', [BankReconsillationController::class, 'get_realised_sum']); // route : base_url/accounts/get_realised_sum
        $routes->post('update', [BankReconsillationController::class, 'update']); // route : base_url/accounts/bank_reconcil/update
        $routes->post('post_unrealised_entries', [BankReconsillationController::class, 'post_unrealised_entries']); // route : base_url/accounts/bank_reconcil/post_unrealised_entries
        $routes->post('get_post_data', [BankReconsillationController::class, 'get_post_data']); // route : base_url/accounts/bank_reconcil/get_post_data
        $routes->post('repost_unrealised_entries', [BankReconsillationController::class, 'repost_unrealised_entries']); // route : base_url/accounts/bank_reconcil/repost_unrealised_entries
        $routes->post('get_opening_balance', [BankReconsillationController::class, 'get_opening_balance']); // route : base_url/accounts/bank_reconcil/get_opening_balance
        $routes->post('repost_single_entry', [BankReconsillationController::class, 'repost_single_entry']); // route : base_url/accounts/bank_reconcil/repost_single_entry
        $routes->get('load_printview', [BankReconsillationController::class, 'load_printview']); // route : base_url/accounts/bank_reconcil/load_printview
        $routes->get('load_details_print', [BankReconsillationController::class, 'load_details_print']); // route : base_url/accounts/bank_reconcil/load_details_print
        $routes->post('reconciliation', [BankReconsillationController::class, 'reconciliation']); // route : base_url/accounts/bank_reconcil/reconciliation
        $routes->get('summary', [BankReconsillationController::class, 'load_summary']); // route : base_url/accounts/bank_reconcil/reconciliation
        $routes->get('details', [BankReconsillationController::class, 'load_details']); // route : base_url/accounts/bank_reconcil/reconciliation
        $routes->post('finalize_selected', [BankReconsillationController::class, 'finalize_selected']); // route : base_url/accounts/bank_reconcil/update
    });

    $routes->group('bank_unrealised_value', static function ($routes) {
        $routes->get('/', [BankUnrealisedValueController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/bank_unrealised_value
        $routes->post('process', [BankUnrealisedValueController::class, 'process_unrealised_value_change']); // route : base_url/accounts/bank_unrealised_value/process
    });

    $routes->group('journal_entry', static function ($routes) {
        $routes->get('/', [JournalEntryController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/journal_entry
        $routes->get('fetch/(:any)', [JournalEntryController::class, 'fetch']);
        $routes->post('save', [JournalEntryController::class, 'save']);
        $routes->get('report', [JournalEntryController::class, 'report']);
        $routes->get('edit_report', [JournalEntryController::class, 'edit_report']);
        $routes->post('reverse', [JournalEntryController::class, 'reverse']);
        $routes->post('edit_date', [JournalEntryController::class, 'edit_date']);
    });
    $routes->group('voucher_payments', static function ($routes) {
        $routes->get('(:num)', [VoucherPaymentController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('voucher_cancel', [VoucherPaymentController::class, 'voucher_cancel']);
        $routes->get('voucher_reject', [VoucherPaymentController::class, 'voucher_reject']);
        $routes->get('fetch/(:num)/(:any)', [VoucherPaymentController::class, 'fetch']);
        $routes->post('save', [VoucherPaymentController::class, 'save']); // route : base_url/accounts/voucher_payments/save
        $routes->get('getSupplierDetails', [VoucherPaymentController::class, 'getSupplierDetails']); // route : base_url/accounts/voucher_payments/getSupplierDetails
        $routes->post('voucher_delete', [VoucherPaymentController::class, 'voucher_delete']); // route : base_url/accounts/voucher_payments/voucher_delete
        $routes->post('get_pc_accumulated_balance', [VoucherPaymentController::class, 'pcaccumulatedbalance']); // route : base_url/accounts/voucher_payments/report
        // $routes->get('voucher_details', [VoucherPaymentController::class, 'voucher_details']); // route : base_url/accounts/voucher_payments/voucher_delete
    });
    $routes->group('voucher_auth', static function ($routes) {
        $routes->get('/', [VoucherPaymentController::class, 'auth']);
        $routes->post('update', [VoucherPaymentController::class, 'update']);
        $routes->get('getVoucherDetails', [VoucherPaymentController::class, 'getVoucherDetails']);
    });

    $routes->group('voucher_audit', static function ($routes) {
        $routes->get('/', [VoucherAuditController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/voucher_audit
        $routes->get('auth', [VoucherAuditController::class, 'auth']); // route : base_url/accounts/voucher_audit/auth
        $routes->post('update', [VoucherAuditController::class, 'update']);
        $routes->post('update_tour', [VoucherAuditController::class, 'update_tour']);
        // $routes->get('getVoucherDetails', [VoucherPaymentController::class, 'getVoucherDetails']); // route : base_url/accounts/voucher_auth/getVoucherDetails
    });

    $routes->group('voucher_print', static function ($routes) {
        $routes->get('(:any)', [VoucherPaymentController::class, 'print']); // route : base_url/accounts/voucher_print
    });

    $routes->group('account_filter', static function ($routes) {
        $routes->get('/', [AccountFilterController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/account_filter
        $routes->get('fetch/(:any)', [AccountFilterController::class, 'fetch']); // route : base_url/accounts/account_filter/fetch
    });

    $routes->group('utility_bill', static function ($routes) {
        $routes->get('/', [UtilityBillPaymentController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/account/utility_bill
        $routes->get('fetch/(:num)/(:any)', [UtilityBillPaymentController::class, 'fetch']);
        $routes->post('save', [UtilityBillPaymentController::class, 'save']);
        $routes->get('report', [UtilityBillPaymentController::class, 'report']);
        $routes->get('delete', [UtilityBillPaymentController::class, 'delete']);
        $routes->post('update_status', [UtilityBillPaymentController::class, 'update_status']);
        $routes->post('pay', [UtilityBillPaymentController::class, 'pay']);
    });


    $routes->group('utility_bill_entertainment', static function ($routes) {
        $routes->get('/', [UtilityBillEntertainmentController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('save', [UtilityBillEntertainmentController::class, 'save']);
        $routes->get('report', [UtilityBillEntertainmentController::class, 'report']);
        $routes->get('delete', [UtilityBillEntertainmentController::class, 'delete']);
        $routes->post('upload_invoice', [UtilityBillEntertainmentController::class, 'upload_invoice']);
        $routes->post('update_expense', [UtilityBillEntertainmentController::class, 'update_expense']);
        $routes->post('delete_expense', [UtilityBillEntertainmentController::class, 'delete_expense']);
    });


    $routes->group('cash_book', static function ($routes) {
        $routes->get('/', [CashBookController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/cash_book
        $routes->get('petty_cash', [CashBookController::class, 'petty_cash']); // route : base_url/accounts/liability
    });

    $routes->group('age_analysis', static function ($routes) {
        $routes->get('/', [AgeAnalysisController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/age_analysis
        $routes->get('creditor', [AgeAnalysisController::class, 'creditor_analysis']); // route : base_url/accounts/age_analysis

    });

    $routes->group('liability', static function ($routes) {
        $routes->get('/', [LiabilityController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/liability
    });

    //advance_payment - route : base_url/payment
    $routes->group('advance_payment', static function ($routes) {
        $routes->get('/', [PaymentController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/payment/advance_payment
        $routes->post('get', [PaymentController::class, 'get_details']); //routes : payment/advance_payment/get
        $routes->post('add', [PaymentController::class, 'add_amount']); //routes : payment/advance_payment/add
        $routes->post('payment_details', [PaymentController::class, 'get_payment_details']); //routes : payment/advance_payment/payment_details
        $routes->get('report', [PaymentController::class, 'advance_payment_report']); // route : base_url/payment/advance_payment/report
    });

    $routes->group('expenses_chart', static function ($routes) {
        $routes->get('/', [ExpensesChartController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/expenses_chart
        $routes->get('report', [ExpensesChartController::class, 'report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/expenses_chart/report
    });

    $routes->group('other_invoice', static function ($routes) {
        $routes->get('/', [OtherInvoiceController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('add_invoice', [OtherInvoiceController::class, 'add_invoice']);
        $routes->get('report', [OtherInvoiceController::class, 'report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('print', [OtherInvoiceController::class, 'print']);
    });

    $routes->group('service_provider_payment', static function ($routes) {
        $routes->get('/', [ServiceProviderPaymentController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/service_provider_payment
        $routes->post('complete_payment', [ServiceProviderPaymentController::class, 'complete_payment']); // route : base_url/accounts/service_provider_payment/complete_payment
        $routes->get('report', [ServiceProviderPaymentController::class, 'report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/accounts/service_provider_payment/report
    });
});

$routes->group('report', static function ($routes) {
    $routes->group('voucher_report', static function ($routes) {
        $routes->get('/', [VoucherReportController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/voucher_report
        $routes->get('cancel', [VoucherReportController::class, 'voucher_cancel']); // route : base_url/report/voucher_report/cancel
        $routes->get('voucher_details', [VoucherReportController::class, 'voucher_details']); // route : base_url/report/voucher_report/cancel
        $routes->get('voucher_details_new', [VoucherReportController::class, 'voucher_details_new']); // route : base_url/report/voucher_report/cancel
        $routes->get('export_pdf', [VoucherReportController::class, 'exportPdf']); // route : base_url/report/voucher_report/export_pdf
        $routes->post('update', [VoucherReportController::class, 'update']);
    });

    $routes->group('voucher_cancel_report', static function ($routes) {
        $routes->get('/', [VoucherCancelReportController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/voucher_report
        $routes->get('voucher_details', [VoucherCancelReportController::class, 'voucher_details']); // route : base_url/report/voucher_report/cancel
    });

    $routes->group('driver_ledger', static function ($routes) {
        $routes->get('/', [DriverLedgerController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/driver_ledger
        $routes->get('driver_details', [DriverLedgerController::class, 'driver_details']); // route : base_url/report/driver_ledger/driver_details
    });

    // $routes->get('confirmed_tours', [ConfirmedTourReportController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/confirmed_tours

    $routes->group('confirmed_tours', static function ($routes) {
        $routes->get('/', [ConfirmedTourReportController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/confirmed_tours
        $routes->get('export_excel', [ConfirmedTourReportController::class, 'exportExcel'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/confirmed_tours/export_excel
        $routes->get('export_pdf', [ConfirmedTourReportController::class, 'exportPdf'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/confirmed_tours/export_pdf
    });

    $routes->get('petty_cash', [PettyCashReportController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/petty_cash

    $routes->get('bank_payment_check', [BankPaymentCheckController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/bank_payment_check
    $routes->get('bank_payment_check/finalized_records', [BankPaymentCheckController::class, 'finalized_records'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/bank_payment_check/finalized_records
    $routes->post('bank_payment_check/mark_finalized', [BankPaymentCheckController::class, 'mark_finalized']);
    $routes->post('bank_payment_check/bulk_finalize', [BankPaymentCheckController::class, 'bulk_finalize']);

    $routes->get('acc_reservation_card_report', [AccountReservationCardReportController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/acc_reservation_card_report
});

// accounts - route : base_url/tour
$routes->group('tour', static function ($routes) {

    // Tour Timeline Route


    $routes->group('tour', static function ($routes) {
        $routes->get('/', [TourController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/tour
        $routes->post('insertConsignee', [TourController::class, 'insertConsignee']); // route : base_url/tour/tour/insertConsignee
        //$routes->get('test_map', [TourController::class, 'Test_map'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/tour/test_map
        $routes->get('show_hotel/(:any)/(:any)', [TourController::class, 'show_hotel']); // route : base_url/tour/tour/show_hotel
        $routes->get('search_hotel/(:any)/(:any)/(:any)', [TourController::class, 'search_hotel']); // route : base_url/tour/tour/search_hotel
        $routes->get('searchHotellocation/(:any)/(:any)/(:any)', [TourController::class, 'searchHotellocation']); // route : base_url/tour/tour/search_hotel
        $routes->get('search_hotel_code/(:any)/(:any)/(:any)', [TourController::class, 'search_hotel_code']); // route : base_url/tour/tour/search_hotel_code
        $routes->get('search_hotel_code_loc/(:any)/(:any)/(:any)', [TourController::class, 'search_hotel_code_loc']); // route : base_url/tour/tour/search_hotel_code
        $routes->get('show/(:any)', [TourController::class, 'show']); // route : base_url/tour/tour/show
        $routes->get('show_transfer/(:any)', [TourController::class, 'show_transfer']); // route : base_url/tour/tour/show_transfer
        $routes->get('get_meal_prices', [TourController::class, 'get_meal_prices']); // route : base_url/tour/tour/get_meal_prices
        $routes->get('get_hotel_details', [TourController::class, 'get_hotel_details']); // route : base_url/tour/tour/get_hotel_details
        $routes->get('get_hotel_type', [TourController::class, 'get_hotel_type']); // route : base_url/tour/tour/get_hotel_details
        $routes->post('finalize_quotation', [TourController::class, 'finalize_quotation']); // route : base_url/tour/tour/finalize_quotation
        $routes->get('getTourId', [TourController::class, 'getTourId']); // route : base_url/tour/tour/getTourId
        // $routes->post('insertQuatation', [TourController::class, 'insertQuatation']); // route : base_url/tour/tour/insertQuatation
        $routes->post('Quatation', [TourController::class, 'Quatation']); // route : base_url/tour/tour/Quatation
        // $routes->post('insertTransportQuatation', [TourController::class, 'insertTransportQuatation']); // route : base_url/tour/tour/insertTransportQuatation
        $routes->get('get_tour_data/(:num)', [TourController::class, 'get_tour_data']); // route : base_url/tour/tour/get_tour_data
        // $routes->get('edit_info/(:any)/(:any)/(:any)', [TourController::class, 'edit_info']); // route : base_url/tour/tour/edit_info
        $routes->post('editTransportQuatation/(:any)/(:any)', [TourController::class, 'editTransportQuatation']); // route : base_url/tour/tour/editTransportQuatation
        $routes->get('load_details', [TourController::class, 'load_details']); // route : base_url/tour/tour/load_details
        $routes->get('load_vehicles', [TourController::class, 'load_vehicles']); // route : base_url/tour/tour/load_vehicles
        $routes->get('finalize/(:any)', [TourController::class, 'finalize']); // route : base_url/tour/tour/finalize
        $routes->post('updateEndDate/(:any)/(:any)', [TourController::class, 'updateEndDate']); // route : base_url/tour/tour/updateEndDate
        $routes->post('searchFull/(:any)/(:any)', [TourController::class, 'searchFull']); // route : base_url/tour/tour/searchFull
        $routes->get('getConsigneeDetails/(:any)', [TourController::class, 'getConsigneeDetails']); // route : base_url/tour/tour/getConsigneeDetailsByKeyword
        $routes->get('getPassengerDetails/(:any)', [TourController::class, 'getPassengerDetails']); // route : base_url/tour/tour/getConsigneeDetailsByKeyword
        $routes->post('pax_create', [TourController::class, 'pax_create']); // route : base_url/tour/tour/pax_create
        $routes->post('pax_update', [TourController::class, 'pax_update']); // route : base_url/tour/tour/pax_create_loc
        $routes->get('pax_delete', [TourController::class, 'pax_delete']); // route : base_url/tour/tour/pax_delete
        $routes->get('pax_all/(:any)', [TourController::class, 'pax_all']); // route : base_url/tour/tour/pax_all
        $routes->get('getEmailDetailsByKeyword', [TourController::class, 'getEmailDetailsByKeyword']); // route : base_url/tour/tour/getEmailDetailsByKeyword
        $routes->post('activity_create', [TourController::class, 'activity_create']); // route : base_url/tour/tour/activity_create
        $routes->post('activity_create_loc', [TourController::class, 'activity_create_loc']); // route : base_url/tour/tour/activity_create_loc
        $routes->get('activity_all/(:any)/(:any)/(:any)', [TourController::class, 'activity_all']); // route : base_url/tour/tour/pax_create
        $routes->get('activity_delete', [TourController::class, 'activity_delete']); // route : base_url/tour/tour/activity_delete
        $routes->get('activity_loc_delete', [TourController::class, 'activity_loc_delete']); // route : base_url/tour/tour/activity_loc_delete
        $routes->post('actual_distance', [TourController::class, 'actual_distance']); // route : base_url/tour/tour/getEmailDetailsByKeyword
        $routes->post('tour_details_copy', [TourController::class, 'tour_copy']); // route : base_url/tour/tour/updateEndDate
        $routes->get('delete', [TourController::class, 'delete']); // route : base_url/tour/tour/updateEndDate
        $routes->get('url_call', [TourController::class, 'url_call']); // route : base_url/tour/tour/
        $routes->post('room_count_add', [TourController::class, 'room_count_add']); // route : base_url/tour/tour/room_count_add
        $routes->post('hotel_only', [TourController::class, 'hotel_only']); // route : base_url/tour/tour/hotel_only
        // $routes->post('itinerary_create', [TourController::class, 'itinerary_create']); // route : base_url/tour/tour/itinerary_create
        $routes->get('itinerary_all/(:any)/(:any)/(:any)', [TourController::class, 'itinerary_all']); // route : base_url/tour/tour/itinerary
        $routes->get('itinerary_delete', [TourController::class, 'itinerary_delete']); // route : base_url/tour/tour/itinerary_delete
        $routes->get('timeline', [TourTimelineController::class, 'index']); // route : base_url/tour/timeline
    });

    $routes->group('print', static function ($routes) {
        $routes->get('/', [printController::class, 'index']);
        $routes->get('print_quotation_with_amounts', [printController::class, 'print_quotation_with_amounts']);
        $routes->get('itinerary', [printController::class, 'itinerary_print']);
        $routes->get('q_email/(:num)/(:num)', [printController::class, 'get_print_view_data']);
        $routes->get('account_reservation_card', [printController::class, 'account_reservation_card']);
    });

    $routes->group('itinerary', static function ($routes) {
        $routes->post('create', [ItineraryController::class, 'create']); // route : base_url/tour/itinerary/create
        $routes->post('delete',  [ItineraryController::class, 'delete_itinerary']); // route : base_url/tour/itinerary/delete
    });

    $routes->group('sub_locations', static function ($routes) {
        // $routes->get('(:num)', [TourSubLocationsController::class, 'index']); // route : base_url/tour/sub_locations
        $routes->post('create', [TourSubLocationsController::class, 'add_sub_location']); // route : base_url/tour/sub_locations/create
        $routes->post('get_next_location', [TourSubLocationsController::class, 'get_next_location']); // route : base_url/tour/sub_locations/get_next_locations
        $routes->get('location_delete', [TourSubLocationsController::class, 'location_delete']); // route : base_url/tour/sub_locations/location_delete
        $routes->get('get_sub_locations/(:num)/(:any)', [TourSubLocationsController::class, 'get_sub_locations']); // route : base_url/tour/sub_locations/get_sub_locations/(:num)/(:any)
        $routes->get('get_last_sublocation_of_date/(:num)/(:any)', [TourSubLocationsController::class, 'get_last_sublocation_of_date']); // route : base_url/tour/sub_locations/get_last_sublocation_of_date/(:num)/(:any)
    });

    $routes->group('calendar', static function ($routes) {
        $routes->get('/', [TourCalendarController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/calender
        $routes->get('getevents', [TourCalendarController::class, 'get_tour_data']); // route : base_url/tour/calender/get_tour_data
        $routes->get('details', [TourCalendarController::class, 'details']); // route : base_url/tour/calender/get_tour_data
    });

    $routes->group('tour_details_search', static function ($routes) {
        $routes->get('/', [TourDetailsSearchController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/tour_details
        $routes->get('show_details', [TourDetailsSearchController::class, 'findAllDetails']); // route : base_url/tour/show_details
        $routes->get('confirm_tour', [TourDetailsSearchController::class, 'confirm_tour']); // route : base_url/tour/tour_details/confirm_tour   
        $routes->post('payment_details', [TourDetailsSearchController::class, 'payment_details']); // route : base_url/tour/tour_details/payment_details
        $routes->get('advance_details/(:num)', [TourDetailsSearchController::class, 'advance_details']); // route : base_url/tour/tour_details/advance_details
        $routes->get('advance_delete', [TourDetailsSearchController::class, 'advance_delete']); // route : base_url/tour/tour_details/advance_details
    });

    $routes->group('tour_details', static function ($routes) {
        $routes->get('/', [TourDetailsController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/tour_details
        $routes->get('monthly_dashboard', [TourDetailsController::class, 'monthly_dashboard']); // route : base_url/tour/tour_details/monthly_dashboard
        $routes->get('delete', [TourDetailsController::class, 'delete']); // route : base_url/tour/tour_details/delete
        $routes->post('search_debtor_invoice', [TourDetailsController::class, 'search_debtor_invoice']); // route : base_url/tour/tour_details/search_debtor_invoice
        $routes->post('add_manual_id', [TourDetailsController::class, 'add_manual_id']); // route : base_url/tour/tour_details/add_manual_id
        $routes->get('tour_templates', [TourDetailsController::class, 'tour_templates']); // route : base_url/tour/tour_details/tour_templates
        $routes->post('override_tour', [TourDetailsController::class, 'overrideTour']); // route : base_url/tour/tour_details/override_tour
        $routes->post('verify_tour', [TourDetailsController::class, 'verifyTour']); // route : base_url/tour/tour_details/verify_tour
    });

    $routes->group('booking_confirm', static function ($routes) {
        $routes->get('/', [BookingConfirmController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('show_details', [BookingConfirmController::class, 'findAllDetails']);
        $routes->get('show_transport', [BookingConfirmController::class, 'findDetails']);
        $routes->get('payment_history', [BookingConfirmController::class, 'payment_history']);
        $routes->post('tour_transport', [BookingConfirmController::class, 'tour_transport']);
        $routes->post('hotelAdvanced', [BookingConfirmController::class, 'hotelAdvanced']);
        $routes->get('getAllDetails/(:any)/(:any)/(:any)/(:any)', [BookingConfirmController::class, 'getAllDetails']);
        // $routes->get('get_agent_vehicle', [BookingConfirmController::class, 'get_agent_vehicle']);
        $routes->post('add_expenses', [BookingConfirmController::class, 'add_expenses']);
        $routes->post('get_tour_expenses_details', [BookingConfirmController::class, 'get_tour_expenses']);
        $routes->post('delete_expenses', [BookingConfirmController::class, 'delete_expenses']);
        $routes->post('get_balance_payment_details', [BookingConfirmController::class, 'get_balance_payment_details']);
        $routes->post('setteled_payment', [BookingConfirmController::class, 'complete_payment']);
        $routes->post('edit_total_transport', [BookingConfirmController::class, 'edit_total_transport']);
        $routes->get('get_t_agent_absolute_balance', [BookingConfirmController::class, 'get_t_agent_absolute_balance']);
        $routes->post('add_logsheet_mileage', [BookingConfirmController::class, 'add_logsheet_mileage']);
        $routes->get('get_logsheet_mileage/(:num)/(:any)', [BookingConfirmController::class, 'get_logsheet_mileage']);
        $routes->post('get_tot_day', [BookingConfirmController::class, 'get_totday_log_sheet']);
    });

    $routes->group('reserve_hotel', static function ($routes) {
        $routes->post('hotel_reservation', [HotelManagementController::class, 'hotel_reservation']);
        $routes->post('get_mail', [HotelManagementController::class, 'get_mail']);
        $routes->post('reply', [HotelManagementController::class, 'reply']);
        $routes->post('confirm_reservation', [HotelManagementController::class, 'confirm_reservation']);
        $routes->post('hotel_payment', [HotelManagementController::class, 'hotel_payment']);
        $routes->post('get_room_rates', [HotelManagementController::class, 'get_room_rates']);
        $routes->post('cancel_booking', [HotelManagementController::class, 'cancel_reservation']);
    });

    $routes->group('quotation', static function ($routes) {
        $routes->get('/', [NewTourController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/quotation
        $routes->post('insert_consignee', [NewTourController::class, 'insert_consignee']); // route : base_url/tour/quotation/insert_consignee
        $routes->post('update_hotel_only/(:alphanum)/(:num)', [NewTourController::class, 'update_hotel_only']); // route : base_url/tour/quotation/update_hotel_only
        $routes->post('update_transport_only/(:alphanum)/(:num)', [NewTourController::class, 'update_transport_only']); // route : base_url/tour/quotation/update_transport_only
        $routes->get('load_vehicles', [NewTourController::class, 'load_vehicles']); // route : base_url/tour/quotation/load_vehicles
        $routes->get('get_consignee_details/(:any)', [NewTourController::class, 'get_consignee_details']); // route : base_url/tour/quotation/get_consignee_details
        $routes->get('timeline/(:any)', [NewTourController::class, 'timeline']); // route : base_url/tour/quotation/timeline
        $routes->get('get_tour_timeline', [NewTourController::class, 'get_tour_timeline']); // route : base_url/tour/quotation/get_tour_timeline
        $routes->get('get_tour_details/(:num)', [NewTourController::class, 'get_tour_details']); // route : base_url/tour/quotation/get_tour_details
        $routes->get('get_passenger_details', [NewTourController::class, 'getPassengerDetails']); // route : base_url/tour/tour/getConsigneeDetailsByKeyword
        $routes->post('pax_create', [NewTourController::class, 'pax_create']); // route : base_url/tour/tour/pax_create
        $routes->get('pax_delete', [NewTourController::class, 'pax_delete']); // route : base_url/tour/tour/pax_delete
        $routes->get('pax_all/(:any)', [NewTourController::class, 'pax_all']); // route : base_url/tour/tour/pax_all
        $routes->get('get_hotel_locations', [NewTourController::class, 'get_hotel_locations']); // route : base_url/tour/quotation/get_hotel_locations
        $routes->get('get_hotel_details', [NewTourController::class, 'get_hotel_details']); // route : base_url/tour/quotation/get_hotel_details
        $routes->get('get_hotel_type', [NewTourController::class, 'get_hotel_type']); // route : base_url/tour/quotation/get_hotel_details
        $routes->get('get_meal_prices', [NewTourController::class, 'get_meal_prices']); // route : base_url/tour/quotation/get_meal_prices
        $routes->get('search_hotel_location/(:any)/(:any)/(:any)', [NewTourController::class, 'search_hotel_location']); // route : base_url/tour/quotation/search_hotel_location
        $routes->post('save_date', [NewTourController::class, 'save_date']); // route : base_url/tour/quotation/save_date
        $routes->get('get_tour_data/(:num)', [NewTourController::class, 'get_tour_data']); // route : base_url/tour/quotation/get_tour_data
        $routes->post('tour_copy', [NewTourController::class, 'copy_tour']); // route : base_url/tour/quotation/tour_copy
        $routes->get('delete', [NewTourController::class, 'delete']); // route : base_url/tour/quotation/delete
        $routes->get('itinerary_all/(:any)/(:any)/(:any)', [NewTourController::class, 'itinerary_all']); // route : base_url/tour/quotation/itinerary_all
        $routes->get('get_last_saved_date/(:num)', [NewTourController::class, 'get_last_saved_date']); // route : base_url/tour/quotation/get_last_saved_date
        $routes->post('actual_distance', [NewTourController::class, 'actual_distance']); // route : base_url/tour/quotation/actual_distance
        $routes->get('get_mileage', [NewTourController::class, 'get_mileage']); // route : base_url/tour/quotation/get_mileage
        $routes->post('room_count_add', [NewTourController::class, 'room_count_add']); // route : base_url/tour/quotation/room_count_add
        $routes->post('finalize/(:any)', [NewTourController::class, 'finalize']); // route : base_url/tour/quotation/finalize
        $routes->post('add_days/(:alphanum)/(:num)', [NewTourController::class, 'add_days']); // route : base_url/tour/quotation/add_days
        $routes->post('remove_days/(:alphanum)/(:num)', [NewTourController::class, 'remove_days']); // route : base_url/tour/quotation/remove_days
        $routes->post('edit_itenary', [NewTourController::class, 'edit_itenary']); // route : base_url/tour/quotation/edit_itenary
        $routes->post('add_sub_row', [NewTourController::class, 'add_sub_row']); // route : base_url/tour/quotation/add_sub_row
        $routes->post('update_vehicle_rate', [NewTourController::class, 'update_vehicle_rate']); // route : base_url/tour/quotation/add_sub_row
        $routes->post('gen_update', [NewTourController::class, 'gen_update']); // route : base_url/tour/quotation/add_sub_row
        $routes->post('update_passenger_count', [NewTourController::class, 'update_passenger_count']); // route : base_url/tour/quotation/update_passenger_count

        $routes->group('activity', static function ($routes) {

            $routes->post('create', [TourActivityController::class, 'create_activity']); // route : base_url/tour/quotation/activity/create
            $routes->get('get_activities/(:num)/(:any)', [TourActivityController::class, 'get_tour_activities']); // route : base_url/tour/quotation/activity/get_activities
            $routes->get('delete_activity', [TourActivityController::class, 'delete_activity']); // route : base_url/tour/quotation/activity/delete_activity

            //=====================================================================================

            $routes->group('sub_location_activity', static function ($routes) {
                $routes->post('create', [TourActivityController::class, 'create_sub_location_activity']); // route : base_url/tour/quotation/activity/sub_location_activity/create
                $routes->get('get_activities/(:num)/(:any)', [TourActivityController::class, 'get_tour_activities']); // route : base_url/tour/quotation/activity/sub_location_activity/get_activities
                $routes->get('delete_activity', [TourActivityController::class, 'delete_activity']); // route : base_url/tour/quotation/activity/sub_location_activity/delete_activity
            });
        });
    });

    // Special Payment Requests Routes
    $routes->group('special_payment_requests', static function ($routes) {
        $routes->get('/', [SpecialPaymentRequestController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/special_payment_requests
        $routes->post('autocomplete', [SpecialPaymentRequestController::class, 'autocomplete_search']); // route : base_url/tour/special_payment_requests/autocomplete
        $routes->post('get_details', [SpecialPaymentRequestController::class, 'get_tour_details']); // route : base_url/tour/special_payment_requests/get_details
        $routes->post('create', [SpecialPaymentRequestController::class, 'create_request']); // route : base_url/tour/special_payment_requests/create
        $routes->post('approve', [SpecialPaymentRequestController::class, 'approve_request']); // route : base_url/tour/special_payment_requests/approve
        $routes->post('reject', [SpecialPaymentRequestController::class, 'reject_request']); // route : base_url/tour/special_payment_requests/reject
    });

    $routes->group('tour_dashboard', static function ($routes) {
        $routes->get('/', [TourDashboardController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/tour_dashboard
        $routes->get('monthly_dashboard', [TourDashboardController::class, 'monthly_dashboard']); // route : base_url/tour/tour_dashboard/monthly_dashboard
        $routes->post('delete', [TourDashboardController::class, 'delete']); // route : base_url/tour/tour_dashboard/delete
        $routes->post('mark_complete', [TourDashboardController::class, 'markComplete']); // route : base_url/tour/tour_dashboard/mark_complete
        $routes->post('search_debtor_invoice', [TourDashboardController::class, 'search_debtor_invoice']); // route : base_url/tour/tour_dashboard/search_debtor_invoice
        $routes->post('add_manual_id', [TourDashboardController::class, 'add_manual_id']); // route : base_url/tour/tour_dashboard/add_manual_id
        $routes->get('tour_templates', [TourDashboardController::class, 'tour_templates']); // route : base_url/tour/tour_dashboard/tour_templates
        $routes->post('get_manual_id', [TourDashboardController::class, 'get_manual_id']); // route : base_url/tour/tour_dashboard/get_manual_id
        $routes->post('edit_manual_id', [TourDashboardController::class, 'edit_manual_id']); // route : base_url/tour/tour_dashboard/edit_manual_id
        $routes->post('add_agent_ref', [TourDashboardController::class, 'add_agent_ref']); // route : base_url/tour/tour_dashboard/add_agent_ref
        $routes->post('get_agent_ref', [TourDashboardController::class, 'get_agent_ref']); // route : base_url/tour/tour_dashboard/get_agent_ref
        $routes->post('edit_agent_ref', [TourDashboardController::class, 'edit_agent_ref']); // route : base_url/tour/tour_dashboard/edit_agent_ref
        $routes->post('set_template', [TourDashboardController::class, 'set_template']); // route : base_url/tour/tour_dashboard/set_template

        $routes->group('documentation', static function ($routes) {
            $routes->get('/', [TourDocumentationController::class, 'index']);
            $routes->post('logsheet_upload', [TourDocumentationController::class, 'upload_log']);
            $routes->get('get_image_base64/(:num)/(:num)', [TourDocumentationController::class, 'get_image_base64']);
            $routes->post('toursettlement_upload', [TourDocumentationController::class, 'upload_tour_settlemet']);
            $routes->post('multipleoption_upload', [TourDocumentationController::class, 'upload_multiple_option']);
            $routes->get('delete/(:num)', [TourDocumentationController::class, 'delete_image']);
            $routes->get('get_image_base64/(:num)/(:num)', [TourDocumentationController::class, 'get_image_base64']);

            $routes->group('documentation_report', static function ($routes) {
                $routes->get('/', [TourDocumentationController::class, 'report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
                $routes->get('get_image_base64/(:num)/(:num)', [TourDocumentationController::class, 'get_image_base64']);
                $routes->get('get_tour_documentation', [TourDocumentationController::class, 'get_tour_documentation']);
                $routes->get('accept_image/(:num)/(:num)', [TourDocumentationController::class, 'accept_image']);
                $routes->get('reject_image/(:num)/(:num)', [TourDocumentationController::class, 'reject_image']);
            });
        });
    });

    $routes->group('tour_arrival', static function ($routes) {
        $routes->get('/', [TourArrivalNotificationController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/tour_arrival
        $routes->get('filter', [TourArrivalNotificationController::class, 'get_tour']); // route : base_url/tour/tour_dashboard/monthly_dashboard
    });

    $routes->group('tour_accounts', static function ($routes) {
        $routes->get('/', [TourAccountsController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('all', [TourAccountsController::class, 'indexAll'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('filter', [TourAccountsController::class, 'get_tour']);
        $routes->post('update', [TourAccountsController::class, 'update']);
        $routes->post('payment_request', [TourAccountsController::class, 'payment_request']);
        $routes->post('update_transport', [TourAccountsController::class, 'update_transport']);
        $routes->get('payment_requested_dashboard', [TourAccountsController::class, 'payment_requested_dashboard']);
        $routes->get('payment_requested_report/(:any)', [TourAccountsController::class, 'payment_requested_report']);
        $routes->post('transport_payment_request', [TourAccountsController::class, 'transport_payment_request']);
        $routes->post('driver_advance_payment_request', [TourAccountsController::class, 'driver_advance_payment_request']);
        $routes->post('payment_request_final', [TourAccountsController::class, 'payment_request_final']);
        $routes->post('cancel_payment_request', [TourAccountsController::class, 'cancel_payment_request']);
        $routes->post('lunch_pay', [TourAccountsController::class, 'lunch_pay']);
        $routes->post('accept_invoice', [TourAccountsController::class, 'accept_invoice']);
        $routes->post('reject_invoice', [TourAccountsController::class, 'reject_invoice']);
        $routes->get('inv_send_report', [TourAccountsController::class, 'inv_send_report']);
        $routes->get('pending_expenses_dashboard', [TourAccountsController::class, 'pending_expenses_dashboard']);
        $routes->post('get_tour_sent_expenses', [TourAccountsController::class, 'get_tour_sent_expenses']);
        $routes->get('approved_expenses_dashboard', [TourAccountsController::class, 'approved_expenses_dashboard']);
        $routes->get('approved_expenses_report/(:any)', [TourAccountsController::class, 'approved_expenses_report']);
        $routes->get('approved_expenses_report/(:any)/(:any)', [TourAccountsController::class, 'approved_expenses_report']);
        $routes->post('accept_approved_expenses', [TourAccountsController::class, 'accept_approved_expenses']);
        $routes->post('reject_approved_expenses', [TourAccountsController::class, 'reject_approved_expenses']);
        $routes->get('posted_expenses_report/(:any)', [TourAccountsController::class, 'posted_expenses_report']);
        $routes->post('finalize_driver_expenses', [TourAccountsController::class, 'finalize_driver_expenses']);

        $routes->get('payment_requested_dashboard_new', [TourAccountsCheckController::class, 'payment_requested_dashboard_new'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('payment_requested_report_new/(:any)', [TourAccountsCheckController::class, 'payment_requested_report_new']);
        $routes->get('payment_approval_v2', [TourAccountsCheckController::class, 'payment_approval_v2'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('update_payment_request_status', [TourAccountsCheckController::class, 'update_payment_request_status']);
        $routes->post('bulk_update_payment_request_status', [TourAccountsCheckController::class, 'bulk_update_payment_request_status']);
        $routes->post('create_bulk_voucher', [TourAccountsCheckController::class, 'create_bulk_voucher']);

        $routes->get('hotel_rate_confirm', [HotelConfirmationController::class, 'hotelrateconfirm'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('hotel_rate_confirm/update_request_amount', [HotelConfirmationController::class, 'updateRequestAmount']);
    });

    $routes->group('tour_payment_dashboard', static function ($routes) {
        $routes->get('/', [TourPaymentDashboardController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('search_tour', [TourPaymentDashboardController::class, 'search_tour']);
        $routes->get('get_bank_accounts', [TourPaymentDashboardController::class, 'get_bank_accounts']);
        $routes->get('get_usd_rate', [TourPaymentDashboardController::class, 'get_usd_rate']);
        $routes->get('list_upcoming', [TourPaymentDashboardController::class, 'list_upcoming']);
        $routes->post('save_payment', [TourPaymentDashboardController::class, 'save_payment']);
        $routes->post('recalc_hotel_cost', [TourPaymentDashboardController::class, 'recalc_hotel_cost']); // Hotel Rates panel → payable hotel cost
        // tf-paydesk-r1: the Tour Payment & Accounting Workflow (privileges checked inside each endpoint)
        $routes->get('desk', [TourPaymentDeskController::class, 'desk']);
        $routes->post('confirm_rate', [TourPaymentDeskController::class, 'confirm_rate']);
        $routes->post('request_meta', [TourPaymentDeskController::class, 'request_meta']);
        $routes->post('expense_request', [TourPaymentDeskController::class, 'expense_request']);
        $routes->post('cancel_request', [TourPaymentDeskController::class, 'cancel_request']);
        $routes->post('approve', [TourPaymentDeskController::class, 'approve']);
        $routes->post('reject', [TourPaymentDeskController::class, 'reject']);
        $routes->post('issue_voucher', [TourPaymentDeskController::class, 'issue_voucher']);
        $routes->post('initiate', [TourPaymentDeskController::class, 'initiate']);
        $routes->post('paid', [TourPaymentDeskController::class, 'paid']);
        $routes->post('remittance_prepare', [TourPaymentDeskController::class, 'remittance_prepare']);
        $routes->post('remittance_send', [TourPaymentDeskController::class, 'remittance_send']);
        $routes->get('remittance/(:num)', [TourPaymentDeskController::class, 'remittance/$1']);
        $routes->get('remittance_pdf/(:num)', [TourPaymentDeskController::class, 'remittance_pdf/$1']);
        $routes->get('voucher/(:num)', [TourPaymentDeskController::class, 'voucher/$1']);
        $routes->post('expense', [TourPaymentDeskController::class, 'expense']);
        $routes->post('expense_cancel', [TourPaymentDeskController::class, 'expense_cancel']);
        $routes->post('invoice', [TourPaymentDeskController::class, 'invoice']);
        $routes->post('document', [TourPaymentDeskController::class, 'document']);
        $routes->get('file/(:num)', [TourPaymentDeskController::class, 'file/$1']);
        $routes->post('confirm_supplier_bank', [TourPaymentDeskController::class, 'confirm_supplier_bank']);
        $routes->get('suppliers', [TourPaymentDeskController::class, 'suppliers']);
        $routes->get('sheet', [TourPaymentDeskController::class, 'sheet']);
        $routes->match(['get', 'post'], 'setup', [TourPaymentDeskController::class, 'setup']);
        // tf-paydesk-r2: Supplier Payables → supplier payment batch → the bank payment page (payment-date rate)
        $routes->get('payables', [TourPaymentDeskController::class, 'payables'], ['filter' => ['UserSessionFilter']]);
        $routes->get('payables_data', [TourPaymentDeskController::class, 'payables_data']);
        $routes->get('batch_items', [TourPaymentDeskController::class, 'batch_items']);
        $routes->post('batch_create', [TourPaymentDeskController::class, 'batch_create']);
        $routes->get('batch/(:num)', [TourPaymentDeskController::class, 'batch/$1'], ['filter' => ['UserSessionFilter']]);
        $routes->get('batch_data/(:num)', [TourPaymentDeskController::class, 'batch_data/$1']);
        // tf-paydesk-r5: bulk supplier payment — one transfer, one voucher per item
        $routes->get('bulk_eligibility', [TourPaymentDeskController::class, 'bulk_eligibility']);
        $routes->post('dispute', [TourPaymentDeskController::class, 'dispute']);
        $routes->post('bulk_create', [TourPaymentDeskController::class, 'bulk_create']);
        $routes->get('bulk/(:num)', [TourPaymentDeskController::class, 'bulk/$1'], ['filter' => ['UserSessionFilter']]);
        $routes->get('bulk_data/(:num)', [TourPaymentDeskController::class, 'bulk_data/$1']);
        $routes->post('bulk_initiate', [TourPaymentDeskController::class, 'bulk_initiate']);
        $routes->post('bulk_paid', [TourPaymentDeskController::class, 'bulk_paid']);
        $routes->post('bulk_remittance_prepare', [TourPaymentDeskController::class, 'bulk_remittance_prepare']);
        $routes->post('bulk_remittance_send', [TourPaymentDeskController::class, 'bulk_remittance_send']);
        $routes->get('bulk_remittance/(:num)', [TourPaymentDeskController::class, 'bulk_remittance/$1']);
        $routes->get('rate_for', [TourPaymentDeskController::class, 'rate_for']);
        $routes->get('settings', [TourPaymentDeskController::class, 'settings'], ['filter' => ['UserSessionFilter']]);   // tf-paydesk-r3: ledger mapping
    });

    $routes->group('tour_payment_forcast', static function ($routes) {
        $routes->get('/view_all/(:any)', [TourPaymentForcastController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/tour_payment
        $routes->get('get_tour_payment', [TourPaymentForcastController::class, 'get_tour_payment']); // route : base_url/tour/tour_payment/get_tour_payment
        $routes->post('add_payment', [TourPaymentForcastController::class, 'add_payment']); // route : base_url/tour/tour_payment/add_payment
        $routes->post('get_payment_details', [TourPaymentForcastController::class, 'get_payment_details']); // route : base_url/tour/tour_payment/get_payment_details
        $routes->post('delete_payment', [TourPaymentForcastController::class, 'delete_payment']); // route : base_url/tour/tour_payment/delete_payment
        $routes->post('get_balance_payment_details', [TourPaymentForcastController::class, 'get_balance_payment_details']); // route : base_url/tour/tour_payment/balance_payment
        $routes->post('setteled_payment', [TourPaymentForcastController::class, 'complete_payment']); // route : base_url/tour/tour_payment/balance_payment
    });
});

// Reports Routes
$routes->group('reports', static function ($routes) {
    $routes->get('change-log', [ChangeLogReportController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
    $routes->post('change-log/get-data', [ChangeLogReportController::class, 'getChanges']);
    $routes->get('change-log/export-excel', [ChangeLogReportController::class, 'exportExcel']);
    $routes->get('change-log/statistics', [ChangeLogReportController::class, 'getStatistics']);
    $routes->get('change-log/tour-timeline/(:any)', [ChangeLogReportController::class, 'getTourTimeline/$1']);

    // Employee Activity Report
    $routes->get('employee_activity', [EmployeeActivityController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
    $routes->post('employee_activity/get_report', [EmployeeActivityController::class, 'getActivityReport']);
    $routes->post('employee_activity/get_details', [EmployeeActivityController::class, 'getActivityDetails']);
});

// $routes->group('tour', static function ($routes) {   
// });

//grn - route : base_url/inventory
$routes->group('inventory', static function ($routes) {
    $routes->group('item_registry', static function ($routes) {
        $routes->get('/', [StockCardController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/inventory/item_registry
        $routes->post('create', [StockCardController::class, 'create']); // route : base_url/inventory/item_registry/create
        $routes->get('all', [StockCardController::class, 'all_items']); // route : base_url/inventory/item_registry/all
        $routes->get('get_item/(:num)', [StockCardController::class, 'getItem']); // route : base_url/inventory/item_registry/getItem/:ID
        $routes->post('update', [StockCardController::class, 'update']); // route : base_url/inventory/item_registry/update
        $routes->get('readCsv', [StockCardController::class, 'readCsv']); // route : base_url/inventory/item_registry/readCsv
        $routes->get('get_entrance_fees', [StockCardController::class, 'get_entrance_fees']); // route : base_url/inventory/item_registry/get_entrance_fees
    });
    // $routes->group('main_category', static function ($routes) {
    //     $routes->get('/', [StockBookAndCatController::class, 'index_main'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/inventory/main_category
    //     $routes->post('create', [StockBookAndCatController::class, 'create_main_category']); // route : base_url/inventory/main_category/create
    //     $routes->get('all', [StockBookAndCatController::class, 'getStockBooks']); // route : base_url/inventory/main_category/all
    //     $routes->get('get/(:num)', [StockBookAndCatController::class, 'get_main']); // route : base_url/inventory/main_category/get/:id
    //     $routes->post('update/(:num)', [StockBookAndCatController::class, 'update_main']); // route : base_url/inventory/main_category/update/:id
    //     $routes->get('delete/(:num)', [StockBookAndCatController::class, 'delete_main']); // route : base_url/inventory/main_category/delete
    // });
    // $routes->group('sub_category', static function ($routes) {
    //     $routes->get('/', [StockBookAndCatController::class, 'index_sub'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/inventory/sub_category
    //     $routes->post('create', [StockBookAndCatController::class, 'create_sub_category']); // route : base_url/inventory/sub_category/create
    //     $routes->get('all', [StockBookAndCatController::class, 'getStockCats']); // route : base_url/inventory/sub_category/all
    //     $routes->get('get/(:num)', [StockBookAndCatController::class, 'get_cat']); // route : base_url/inventory/sub_category/get/:id
    //     $routes->post('update/(:num)', [StockBookAndCatController::class, 'update_sub']); // route : base_url/inventory/sub_category/update/:id
    //     $routes->get('delete/(:num)', [StockBookAndCatController::class, 'delete_sub']); // route : base_url/inventory/sub_category/delete
    // });
    // $routes->group('grn', static function ($routes) {
    //     $routes->get('/', [GrnController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/inventory/grn
    //     $routes->get('validate_supplier', [GrnController::class, 'validate_supplier']); // route : base_url/inventory/grn/validate_supplier
    //     $routes->post('scan_document', [GrnController::class, 'scan_document']); // route : base_url/inventory/grn/scan_document
    //     $routes->post('submit_table_data', [GrnController::class, 'submit_table_data']); // route : base_url/inventory/grn/scan_document
    //     $routes->post('check_product_code', [GrnController::class, 'check_product_code']); // route : base_url/inventory/grn/scan_document
    // });

    $routes->group('manual_grn', static function ($routes) {
        $routes->get('/', [ManualGrnController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('get_pending', [ManualGrnController::class, 'getPendingGrnInvoices']);
        $routes->post('addGrnInvoice', [ManualGrnController::class, 'addGrnInvoice']);
        $routes->get('getSearch', [ManualGrnController::class, 'getSearch']);
        $routes->get('get_dataset', [ManualGrnController::class, 'get_dataset']);
        $routes->post('add_itm', [ManualGrnController::class, 'add_item']);
        $routes->get('get_grn_items/(:any)/(:any)/(:any)', [ManualGrnController::class, 'get_grn_items']);
        $routes->get('get_grn_items_by_params', [ManualGrnController::class, 'get_grn_items_by_params']);
        $routes->post('update_qty', [ManualGrnController::class, 'update_qty']);
        $routes->post('update_foc_qty', [ManualGrnController::class, 'update_foc_qty']);
        $routes->post('remove_item', [ManualGrnController::class, 'remove_item']);
        $routes->post('finalize_grn/(:any)/(:any)/(:any)', [ManualGrnController::class, 'finalize_grn']);
        $routes->post('finalize_grn_by_params', [ManualGrnController::class, 'finalize_grn_by_params']);
    });

    $routes->group('usage_note', static function ($routes) {
        $routes->get('/', [UsageNoteController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('list', [UsageNoteController::class, 'list'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('create', [UsageNoteController::class, 'create']);
        $routes->get('get_details', [UsageNoteController::class, 'get_details']);
        $routes->post('search_item', [UsageNoteController::class, 'search_item']);
        $routes->get('search_tours', [UsageNoteController::class, 'search_tours']);
        $routes->get('get_tour_activities', [UsageNoteController::class, 'get_tour_activities']);
        $routes->post('delete_temp_item', [UsageNoteController::class, 'delete_temp_item']);
        $routes->get('get_temp_items', [UsageNoteController::class, 'get_temp_items']);
        $routes->post('finalize_usage_note', [UsageNoteController::class, 'finalize_usage_note']);
        $routes->get('print/(:any)', [UsageNoteController::class, 'print']);

        // Report routes
        $routes->get('report', [UsageNoteController::class, 'report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('report_export_excel', [UsageNoteController::class, 'report_export_excel'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('report_export_pdf', [UsageNoteController::class, 'report_export_pdf'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);

        // Authorization routes
        $routes->get('authorization', [UsageNoteController::class, 'authorization_panel'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('get_pending_usage_notes', [UsageNoteController::class, 'get_pending_usage_notes'], ['filter' => ['UserSessionFilter']]);
        $routes->get('get_usage_note_details/(:any)', [UsageNoteController::class, 'get_usage_note_details/$1'], ['filter' => ['UserSessionFilter']]);
        $routes->post('approve_usage_note', [UsageNoteController::class, 'approve_usage_note'], ['filter' => ['UserSessionFilter']]);
        $routes->post('reject_usage_note', [UsageNoteController::class, 'reject_usage_note'], ['filter' => ['UserSessionFilter']]);

        // Tour-based return routes
        $routes->post('process_tour_return', [UsageNoteController::class, 'process_tour_return'], ['filter' => ['UserSessionFilter']]);
    });

    $routes->group('stock', static function ($routes) {
        $routes->get('/', [InventoryStockController::class, 'index']); // route : base_url/inventory/stock
        $routes->get('low_stock', [InventoryStockController::class, 'low_stock']); // route : base_url/inventory/stock/low_stock
        $routes->get('search', [InventoryStockController::class, 'search']); // route : base_url/inventory/stock/search
        $routes->get('header_stock_data', [InventoryStockController::class, 'getHeaderStockData']); // route : base_url/inventory/stock/header_stock_data
        $routes->post('process_return', [InventoryStockController::class, 'process_return']); // route : base_url/inventory/stock/process_return
        $routes->get('return_report', [InventoryStockController::class, 'return_report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/inventory/stock/return_report
        $routes->get('summary_report', [InventoryStockController::class, 'summary_report'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/inventory/stock/summary_report
        $routes->get('summary_report_export_excel', [InventoryStockController::class, 'summary_report_export_excel'], ['filter' => ['UserSessionFilter']]); // route : base_url/inventory/stock/summary_report_export_excel
    });
});

// INQUIRY ENDPOINT
$routes->group('inquiry', static function ($routes) {
    // Main inquiry routes
    $routes->get('/', [InquiryController::class, 'form'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/inquiry
    $routes->post('insert_consignee', [InquiryController::class, 'insert_consignee']); // route : base_url/inquiry/insert_consignee
    $routes->get('timeline/(:num)', [InquiryController::class, 'timeline']); // route : base_url/inquiry/timeline
    $routes->get('get_tour_details/(:num)', [InquiryController::class, 'get_tour_details']); // route : base_url/inquiry/get_tour_details
    $routes->get('get_tour_data/(:num)', [InquiryController::class, 'get_tour_data']); // route : base_url/inquiry/get_tour_data
    $routes->get('get_inquiry_consignee/(:num)', [InquiryController::class, 'get_inquiry_consignee']); // route : base_url/inquiry/get_inquiry_consignee
    $routes->post('save_date', [InquiryController::class, 'save_date']); // route : base_url/inquiry/save_date
    $routes->post('delete', [InquiryController::class, 'delete_date']); // route : base_url/inquiry/delete
    $routes->post('finalize', [InquiryController::class, 'finalize']); // route : base_url/inquiry/finalize
    $routes->get('get_manual_id', [InquiryController::class, 'get_manual_id']); // route : base_url/inquiry/get_manual_id
    $routes->get('get_template/(:num)', [InquiryController::class, 'get_template']); // route : base_url/inquiry/get_template
    $routes->get('get_last_saved_date/(:num)', [InquiryController::class, 'get_last_saved_date']); // route : base_url/inquiry/get_last_saved_date
    $routes->get('itinerary_all/(:any)/(:any)/(:any)', [InquiryController::class, 'itinerary_all']); // route : base_url/inquiry/itinerary_all
    $routes->post('tour_copy', [InquiryController::class, 'tour_copy']); // route : base_url/inquiry/tour_copy

    // Vehicle and agent routes
    $routes->get('load_vehicles', [InquiryController::class, 'load_vehicles']); // route : base_url/inquiry/load_vehicles
    $routes->get('get_consignee_details/(:any)', [InquiryController::class, 'get_consignee_details']); // route : base_url/inquiry/get_consignee_details

    // Hotel routes
    $routes->get('get_hotel_details', [InquiryController::class, 'get_hotel_details']); // route : base_url/inquiry/get_hotel_details
    $routes->get('get_hotel_type', [InquiryController::class, 'get_hotel_type']); // route : base_url/inquiry/get_hotel_type
    $routes->get('get_meal_prices', [InquiryController::class, 'get_meal_prices']); // route : base_url/inquiry/get_meal_prices
    $routes->get('get_mileage', [InquiryController::class, 'get_mileage']); // route : base_url/inquiry/get_mileage
    $routes->post('actual_distance', [InquiryController::class, 'actual_distance']); // route : base_url/inquiry/actual_distance

    // Passenger management
    $routes->get('get_passenger_details', [InquiryController::class, 'getPassengerDetails']); // route : base_url/inquiry/getPassengerDetails
    $routes->post('pax_create', [InquiryController::class, 'pax_create']); // route : base_url/inquiry/pax_create
    $routes->get('pax_delete', [InquiryController::class, 'pax_delete']); // route : base_url/inquiry/pax_delete
    $routes->get('pax_all/(:any)', [InquiryController::class, 'pax_all']); // route : base_url/inquiry/pax_all
    $routes->post('update_passenger_count', [InquiryController::class, 'update_passenger_count']); // route : base_url/inquiry/update_passenger_count

    // Add/remove days
    $routes->post('add_days', [InquiryController::class, 'add_days']); // route : base_url/inquiry/add_days
    $routes->post('remove_days', [InquiryController::class, 'remove_days']); // route : base_url/inquiry/remove_days

    // Sub-locations routes
    $routes->group('sub_locations', static function ($routes) {
        $routes->get('index/(:num)', [InquirySubLocationsController::class, 'index']); // route : base_url/inquiry/sub_locations/index
        $routes->post('add_sub_location', [InquirySubLocationsController::class, 'add_sub_location']); // route : base_url/inquiry/sub_locations/add_sub_location
        $routes->get('get_sub_locations/(:num)/(:any)', [InquirySubLocationsController::class, 'get_sub_locations']); // route : base_url/inquiry/sub_locations/get_sub_locations
        $routes->post('get_next_location', [InquirySubLocationsController::class, 'get_next_location']); // route : base_url/inquiry/sub_locations/get_next_location
        $routes->get('location_delete', [InquirySubLocationsController::class, 'location_delete']); // route : base_url/inquiry/sub_locations/location_delete
        $routes->get('get_last_sublocation_of_date/(:num)/(:any)', [InquirySubLocationsController::class, 'get_last_sublocation_of_date']); // route : base_url/inquiry/sub_locations/get_last_sublocation_of_date
    });

    // Activity routes
    $routes->group('activity', static function ($routes) {
        $routes->post('create', [InquiryActivityController::class, 'create_activity']); // route : base_url/inquiry/activity/create
        $routes->get('get_activities/(:num)/(:any)', [InquiryActivityController::class, 'get_tour_activities']); // route : base_url/inquiry/activity/get_activities
        $routes->get('get_tour_activities/(:num)/(:any)', [InquiryActivityController::class, 'get_tour_activities']); // route : base_url/inquiry/activity/get_tour_activities (alias)
        $routes->get('delete_activity', [InquiryActivityController::class, 'delete_activity']); // route : base_url/inquiry/activity/delete_activity
        $routes->post('create_sub_location_activity', [InquiryActivityController::class, 'create_sub_location_activity']); // route : base_url/inquiry/activity/create_sub_location_activity
        $routes->get('delete_sub_activity', [InquiryActivityController::class, 'delete_sub_activity']); // route : base_url/inquiry/activity/delete_sub_activity
    });

    // Itinerary routes
    $routes->group('itinerary', static function ($routes) {
        $routes->post('add_itinerary', [InquiryItineraryController::class, 'add_itinerary']); // route : base_url/inquiry/itinerary/add_itinerary
        $routes->post('add_itinerary_location', [InquiryItineraryController::class, 'add_itinerary_location']); // route : base_url/inquiry/itinerary/add_itinerary_location
        $routes->post('add_itinerary_activity', [InquiryItineraryController::class, 'add_itinerary_activity']); // route : base_url/inquiry/itinerary/add_itinerary_activity
        $routes->post('add_itinerary_activity_location', [InquiryItineraryController::class, 'add_itinerary_activity_location']); // route : base_url/inquiry/itinerary/add_itinerary_activity_location
        $routes->get('delete_itinerary', [InquiryItineraryController::class, 'delete_itinerary']); // route : base_url/inquiry/itinerary/delete_itinerary
    });

    // Print routes
    $routes->get('print', [InquiryController::class, 'print']); // route : base_url/inquiry/print
    $routes->get('print/print_inquiry', [InquiryController::class, 'print_inquiry']); // route : base_url/inquiry/print/print_inquiry
    $routes->get('print/print_timeline', [InquiryController::class, 'print_timeline']); // route : base_url/inquiry/print/print_timeline
});

// Legacy inquiries endpoint - kept for backward compatibility
$routes->group('inquiries', static function ($routes) {
    $routes->get('/', [InquiryController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/inquiries
    $routes->get('all', [InquiryController::class, 'all']); // route : base_url/inquiries/all
    $routes->get('get_inquiry/(:num)', [InquiryController::class, 'get_inquiry']); // route : base_url/inquiries/get_inquiry/:id
    $routes->post('new', [InquiryController::class, 'new']); // route : base_url/inquiries/new
    $routes->delete('delete/(:num)', [InquiryController::class, 'delete_inquiry']); // route : base_url/inquiries/delete
    $routes->get('accept/(:num)', [InquiryController::class, 'accept_inquiry']); // route : base_url/inquiries/accept/:id
    $routes->get('reject/(:num)', [InquiryController::class, 'reject_inquiry']); // route : base_url/inquiries/reject/:id
});

// One-off data-repair scripts from an earlier migration. They were reachable at
// base_url/run1|run2|run3 with NO login or permission filter, and they are not
// harmless: manualRun1 mass-updates every tour_quotations row and regenerates the
// day rows for every tour, manualRun2 deletes tour_quotations rows. Routes closed
// 15 Sep 2026 — the methods remain in NewTourController but can no longer be
// reached from a browser. If one is ever needed again, run it from a controller
// action behind ['UserSessionFilter', 'UserPermissionFilter'] instead.
// $routes->get('run1', [NewTourController::class, 'manualRun1']);
// $routes->get('run2', [NewTourController::class, 'manualRun2']);
// $routes->get('run3', [NewTourController::class, 'manualRun3']);

$routes->group('mail', static function ($routes) {
    $routes->group('mailbox', static function ($routes) {
        $routes->get('/', [InboxController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route: base_url/mail/inbox
        $routes->get('downloadAttachment/(:num)/(:num)', [InboxController::class, 'downloadAttachment']);
        $routes->get('unread_emails', [InboxController::class, 'getUnreadEmails']);
    });

    $routes->group('inbox', static function ($routes) {
        $routes->get(' ', [InboxController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route: base_url/mail/inbox
        $routes->get('downloadAttachment/(:num)/(:num)', [InboxController::class, 'downloadAttachment']);
        $routes->get('unread_emails', [InboxController::class, 'getUnreadEmails']);
    });
});

$routes->group('chat', static function ($routes) {
    $routes->get('/', [ChatController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
    $routes->get('open/(:num)/(:any)', [ChatController::class, 'open']); // route : base_url/chat/open/(:tourId)/(:agentAcc)
    $routes->post('start_conversation', [ChatController::class, 'start_conversation']);
    $routes->get('get_agent_conversation_list/(:any)', [ChatController::class, 'get_agent_conversation_list']);

    $routes->get('get_conversation_with_messages/(:num)', [ChatController::class, 'get_conversation_with_messages']);
    $routes->get('get_recent_conversations_for_header', [ChatController::class, 'get_recent_conversations_for_header']);
    $routes->post('send_message', [ChatController::class, 'send_message']);
    $routes->post('tag_tour/(:num)/(:any)', [ChatController::class, 'tag_tour']);
    $routes->post('tag_inquiry/(:num)/(:num)', [ChatController::class, 'tag_inquiry']);
    $routes->post('close_chat/(:num)', [ChatController::class, 'close_chat']);
    $routes->get('download/attachment/(:num)', [ChatController::class, 'download_attachment']);

    // Chat Quotation Routes
    $routes->post('quotation/start', [ChatController::class, 'startQuotationInChat']);
    $routes->post('quotation/save-phase', [ChatController::class, 'saveQuotationPhase']);
    $routes->get('quotation/progress', [ChatController::class, 'getQuotationProgress']);
    $routes->get('quotation/phase2-data', [ChatController::class, 'getPhase2Data']);
    $routes->post('quotation/save-activities', [ChatController::class, 'saveActivities']);
    $routes->post('quotation/add-single-activity', [ChatController::class, 'addSingleActivity']);
    $routes->delete('quotation/remove-single-activity', [ChatController::class, 'removeSingleActivity']);

    // Quotation Request Routes
    $routes->post('process-japanese-request', [ChatController::class, 'processJapaneseRequest']);
    $routes->post('insert-request-message', [ChatController::class, 'insertRequestMessage']);
});

// Notification routes
$routes->group('notification', static function ($routes) {
    $routes->get('unallocated_driver_tours', [NotificationController::class, 'getUnallocatedDriverTours']);
});

$routes->group('api', ['filter' => ['corsFilter']], function ($routes) {
    $routes->group('v1', function ($routes) {
        $routes->group('inquiry', function ($routes) {
            $routes->post('create', [ApiInquiryController::class, 'createInquiry']); // route : api/v1/settings/groupProfile/create
        });
    });
});

$routes->options('(:any)', static function () {
    $response = response();
    $response->setStatusCode(204);
    $response->setHeader('Allow:', 'OPTIONS, GET, POST, PUT, PATCH, DELETE');
    // $response->setHeader('Access-Control-Allow-Origin', '192.168.1.127');

    return $response;
});

// Hotel Rates v2 (contracts, seasons, supplements, validated import) — see app/Libraries/HotelRates/README.md
require __DIR__ . '/Routes.hotel_rate_v2.php';
require __DIR__ . '/Routes.help.php'; // tf-help-r1: the in-system User Manual
require __DIR__ . '/Routes.mailbox.php'; // tf-mailfix-r2: My Profile -> Mailbox
require __DIR__ . '/Routes.tourmail.php'; // tf-tourmail-r1: the tour's hotel and restaurant mails

// Tour Flow — inquiry intake (tour/intake) — see app/Libraries/TourFlow/
require __DIR__ . '/Routes.tour_flow.php';
