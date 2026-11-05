<?php

use App\Controllers\Mail\ComposeController;
use App\Controllers\Mail\InboxController;
use CodeIgniter\Router\RouteCollection;
use App\Controllers\LoginController;
use App\Controllers\DashboardController;
use App\Controllers\InquiryController;
use App\Controllers\ProfileController;
use App\Controllers\UtilityDataController;
use App\Controllers\NavigationController;

use App\Controllers\Accounts\BalanceSheetController;
use App\Controllers\Accounts\BankReconsillationController;
use App\Controllers\Accounts\ChartOfAccountsController;
use App\Controllers\Accounts\CreditorPaymentController;
use App\Controllers\Accounts\CreditorInvoiceController;
use App\Controllers\Accounts\DebtorPaymentController;
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
use App\Controllers\API\V1\Inquiry\ApiInquiryController;

use App\Controllers\Chat\ChatController;

use App\Controllers\MasterFiles\CustomerController;
use App\Controllers\MasterFiles\SupplierController;
use App\Controllers\MasterFiles\EntranceFeeController;
use App\Controllers\MasterFiles\HotelRateController;
use App\Controllers\MasterFiles\RoomTypeController;
use App\Controllers\MasterFiles\TransferMilageController;
use App\Controllers\MasterFiles\VehicleController;
use App\Controllers\MasterFiles\ExtraKilometeresController;
use App\Controllers\NewDashboardController;

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
use App\Controllers\Tour\TourDocumentationController;

use App\Controllers\Report\VoucherReportController;
use App\Controllers\Report\VoucherCancelReportController;

use App\Controllers\Grn\StockCardController;
use App\Controllers\Grn\GrnController;
use App\Controllers\Grn\ManualGrnController;
use App\Controllers\Grn\UsageNoteController;

use App\Models\TourActivityModel;

/**
 * @var RouteCollection $routes
 */


/************ PUBLIC ROUTES *************/

//LoginController
$routes->get('/', [LoginController::class, 'index']); // route : base_url/
$routes->post('login', [LoginController::class, 'login']); // route : base_url/login
$routes->get('logout', [LoginController::class, 'logout']); // route : base_url/logout
$routes->get('403', [LoginController::class, 'forbidden']); // route : base_url/forbidden

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
    $routes->get('tiles', [NewDashboardController::class, 'index']); // route : base_url/dashboard2/tiles
    $routes->get('searchTours', [NewDashboardController::class, 'searchTours']); // route : base_url/dashboard2/searchTours
    $routes->get('tour_quotation', [NewDashboardController::class, 'printQuotation']); // route : base_url/dashboard2/tour_quotation
    $routes->get('new_quotation', [NewDashboardController::class, 'newQuotation']); // route : base_url/dashboard2/new_quotation
    $routes->get('tour_transport', [NewDashboardController::class, 'tourTransport']); // route : base_url/dashboard2/tour_transport
    $routes->post('driver_allocation', [NewDashboardController::class, 'driverAllocation']); // route : base_url/dashboard2/driver_allocation
    $routes->post('remove_driver', [NewDashboardController::class, 'removeDriverAllocation']); // route : base_url/dashboard2/remove_driver
    $routes->get('driver_allocation_details', [NewDashboardController::class, 'driverAllocationDetails']); // route : base_url/dashboard2/driver_allocation_details
    $routes->get('get_driver_tours', [NewDashboardController::class, 'getDriverTours']); // route : base_url/dashboard2/driver_allocation_details
    $routes->get('get_driver_log', [NewDashboardController::class, 'getDriverLog']); // route : base_url/dashboard2/get_driver_log

});

// navigation - route : base_url/navigation
$routes->group('navigation', static function ($routes) {
    $routes->get('/', [NavigationController::class, 'index']); // route : base_url/dashboard
});

$routes->get('my_profile', [ProfileController::class, 'index']); // route : base_url/my_profile
$routes->post('my_profile/change_password', [ProfileController::class, 'change_password']); // route : base_url/my_profile
$routes->get('my_profile/update_contact_info', [ProfileController::class, 'update_contact_info']); // route : base_url/my_profile
$routes->post('my_profile/upload_profile_picture', [ProfileController::class, 'upload_profile_picture']); // route : base_url/upload_profile_picture


// Settings Routes - route : base_url/settings
$routes->group('settings', static function ($routes) {
    //MenuCategoryController - route : base_url/settings/menu_category
    $routes->group('menu_category', static function ($routes) {
        $routes->get('/', [MenuCategoryController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_category
        $routes->post('create', [MenuCategoryController::class, 'create']); // route : base_url/settings/menu_category/create
        $routes->get('show/(:num)', [MenuCategoryController::class, 'show']); // route : base_url/settings/menu_category/show/$1
        $routes->put('update/(:num)', [MenuCategoryController::class, 'update']); // route : base_url/settings/menu_category/update/$1
        $routes->delete('delete/(:num)', [MenuCategoryController::class, 'delete']); // route : base_url/settings/menu_category/delete/$1
    });

    //MenuListController - route : base_url/settings/menu_list
    $routes->group('menu_list', static function ($routes) {
        $routes->get('/', [MenuListController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/menu_list
        $routes->post('create', [MenuListController::class, 'create']); // route : base_url/settings/menu_list/create
        $routes->get('show/(:num)', [MenuListController::class, 'show']); // route : base_url/settings/menu_list/show/$1
        $routes->put('update/(:num)', [MenuListController::class, 'update']); // route : base_url/settings/menu_list/update/$1
        $routes->delete('delete/(:num)', [MenuListController::class, 'delete']); // route : base_url/settings/menu_list/delete/$1
    });

    //GroupProfileController - route : base_url/settings/group_profiles
    $routes->group('group_profile', static function ($routes) {
        $routes->get('/', [GroupProfileController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/group_profiles
        $routes->post('create', [GroupProfileController::class, 'create']); // route : base_url/settings/group_profiles/create
        $routes->get('show/(:num)', [GroupProfileController::class, 'show']); // route : base_url/settings/group_profiles/show/$1
        $routes->put('update/(:num)', [GroupProfileController::class, 'update']); // route : base_url/settings/group_profiles/update/$1
        $routes->delete('delete/(:num)', [GroupProfileController::class, 'delete']); // route : base_url/settings/group_profiles/delete/$1
    });

    //UserAccountController - route : base_url/settings/user_account
    $routes->group('user_account', static function ($routes) {
        $routes->get('/', [UserAccountController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/user_account
        $routes->post('create', [UserAccountController::class, 'create']); // route : base_url/settings/user_account/create
        $routes->get('show/(:num)', [UserAccountController::class, 'show']); // route : base_url/settings/user_account/show/$1
        $routes->put('update/(:num)', [UserAccountController::class, 'update']); // route : base_url/settings/user_account/update/$1
        $routes->delete('delete/(:num)', [UserAccountController::class, 'delete']); // route : base_url/settings/user_account/delete/$1
        $routes->get('update_access/(:num)', [UserAccountController::class, 'update_access']); // route : base_url/settings/user_account/update_access/$1
    });

    //GroupPrivilegeController - route : base_url/settings/group_privilege
    $routes->group('group_privilege', static function ($routes) {
        $routes->get('(:num)', [GroupPrivilegeController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/group_privilege/$1
        $routes->get('add/(:num)/(:num)', [GroupPrivilegeController::class, 'add']); // route : base_url/settings/group_privilege/add/$group_id/$menu_id
        $routes->get('remove/(:num)', [GroupPrivilegeController::class, 'remove']); // route : base_url/settings/group_privilege/remove/$id
    });

    //SettingsController - route : base_url/settings/backup_database
    $routes->group('backup_database', static function ($routes) {
        $routes->get('/', [UtilityDataController::class, 'db_backup_view'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/backup_database
    });

    //SettingsController - route : base_url/settings/manual_update
    $routes->group('manual_update', static function ($routes) {
        $routes->get('/', [ManualUpdateController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/settings/backup_database
        $routes->post('update', [ManualUpdateController::class, 'update']); // route : base_url/settings/manual_update/update
        $routes->post('des_update', [ManualUpdateController::class, 'des_update']); // route : base_url/settings/manual_update/des_update
        $routes->post('des_other_update', [ManualUpdateController::class, 'des_other_update']); // route : base_url/settings/manual_update/des_update
        $routes->post('hotel_voucher_accept', [ManualUpdateController::class, 'hotel_voucher_accept']); // route : base_url/settings/manual_update/des_update
        $routes->post('entrance_update', [ManualUpdateController::class, 'entrance_update']); // route : base_url/settings/manual_update/entrance_update
        $routes->post('other_update', [ManualUpdateController::class, 'other_update']); // route : base_url/settings/manual_update/other_update
        $routes->post('transport_update', [ManualUpdateController::class, 'transport_update']); // route : base_url/settings/manual_update/transport_update
        $routes->post('voucher_date_update', [ManualUpdateController::class, 'voucher_date_update']); // route : base_url/settings/manual_update/voucher_date_update
        $routes->post('verify_tour', [ManualUpdateController::class, 'verify_tour']);
        $routes->post('debtor_receipt_generate', [ManualUpdateController::class, 'debtor_receipt_generate']);
    });

    // Year end process - route : base_url/settings/year_end_process
    $routes->group('year_end_process', static function ($routes) {
        $routes->get('/', [YearEndProcessController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('fetch/(:any)', [YearEndProcessController::class, 'fetch']);
        $routes->post('submit', [YearEndProcessController::class, 'process']);
        $routes->post('submit_last', [YearEndProcessController::class, 'process_last']);
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
        $routes->get('new_driver_index', [SupplierController::class, 'new_driver_index']);
        $routes->get('all_drivers', [SupplierController::class, 'all_drivers']);
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
        $routes->get('voucher_authentication', [CreditorInvoiceController::class, 'voucher_authentication']); // route : base_url/accounts/creditor_invoice/voucher_authentication

        $routes->get('credited_report', [CreditorInvoiceController::class, 'credited_invoice_report']); // route : base_url/accounts/creditor_invoice/report
        $routes->post('get_details', [CreditorInvoiceController::class, 'get_details']); // route : base_url/accounts/creditor_invoice/get_details
        $routes->post('get_tour_paymets', [CreditorInvoiceController::class, 'get_tour_paymets']); // route : base_url/accounts/creditor_invoice/get_tour_details
        $routes->post('get_tour_details', [CreditorInvoiceController::class, 'get_tour_details']); // route : base_url/accounts/creditor_invoice/get_tour_details
        $routes->get('view_expences', [CreditorInvoiceController::class, 'get_expences']); // route : base_url/report/location_wise_stock/view_stock/:location/:transport_flag
        $routes->get('expenses_delete', [CreditorInvoiceController::class, 'expenses_delete']); // route : base_url/accounts/creditor_invoice/expenses_delete
        $routes->get('expenses_report', [CreditorInvoiceController::class, 'expenses_report']); // route : base_url/accounts/creditor_invoice/expenses_report
        $routes->get('view_operational_expences', [CreditorInvoiceController::class, 'view_operational_expences']);
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

    // route : base_url/accounts/debtor_payment
    $routes->group('debtor_payment', static function ($routes) {
        $routes->get('/', [DebtorPaymentController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
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
        // $routes->get('voucher_details', [VoucherPaymentController::class, 'voucher_details']); // route : base_url/accounts/voucher_payments/voucher_delete
    });
    $routes->group('voucher_auth', static function ($routes) {
        $routes->get('/', [VoucherPaymentController::class, 'auth']); // route : base_url/accounts/update/posting_account
        $routes->post('update', [VoucherPaymentController::class, 'update']);
        $routes->get('getVoucherDetails', [VoucherPaymentController::class, 'getVoucherDetails']); // route : base_url/accounts/voucher_auth/getVoucherDetails
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
    });
});

$routes->group('report', static function ($routes) {
    $routes->group('voucher_report', static function ($routes) {
        $routes->get('/', [VoucherReportController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/voucher_report
        $routes->get('cancel', [VoucherReportController::class, 'voucher_cancel']); // route : base_url/report/voucher_report/cancel
        $routes->get('voucher_details', [VoucherReportController::class, 'voucher_details']); // route : base_url/report/voucher_report/cancel
        $routes->get('voucher_details_new', [VoucherReportController::class, 'voucher_details_new']); // route : base_url/report/voucher_report/cancel
        $routes->post('update', [VoucherReportController::class, 'update']);
    });

    $routes->group('voucher_cancel_report', static function ($routes) {
        $routes->get('/', [VoucherCancelReportController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/report/voucher_report
        $routes->get('voucher_details', [VoucherCancelReportController::class, 'voucher_details']); // route : base_url/report/voucher_report/cancel
    });
});

// accounts - route : base_url/tour
$routes->group('tour', static function ($routes) {

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

    $routes->group('tour_dashboard', static function ($routes) {
        $routes->get('/', [TourDashboardController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/tour_dashboard
        $routes->get('monthly_dashboard', [TourDashboardController::class, 'monthly_dashboard']); // route : base_url/tour/tour_dashboard/monthly_dashboard
        $routes->get('delete', [TourDashboardController::class, 'delete']); // route : base_url/tour/tour_dashboard/delete
        $routes->post('search_debtor_invoice', [TourDashboardController::class, 'search_debtor_invoice']); // route : base_url/tour/tour_dashboard/search_debtor_invoice
        $routes->post('add_manual_id', [TourDashboardController::class, 'add_manual_id']); // route : base_url/tour/tour_dashboard/add_manual_id
        $routes->get('tour_templates', [TourDashboardController::class, 'tour_templates']); // route : base_url/tour/tour_dashboard/tour_templates
        $routes->post('get_manual_id', [TourDashboardController::class, 'get_manual_id']); // route : base_url/tour/tour_dashboard/get_manual_id
        $routes->post('edit_manual_id', [TourDashboardController::class, 'edit_manual_id']); // route : base_url/tour/tour_dashboard/edit_manual_id
        $routes->post('add_agent_ref', [TourDashboardController::class, 'add_agent_ref']); // route : base_url/tour/tour_dashboard/add_agent_ref
        $routes->post('get_agent_ref', [TourDashboardController::class, 'get_agent_ref']); // route : base_url/tour/tour_dashboard/get_agent_ref
        $routes->post('edit_agent_ref', [TourDashboardController::class, 'edit_agent_ref']); // route : base_url/tour/tour_dashboard/edit_agent_ref

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

    // $routes->group('booking', static function ($routes) {
    //     $routes->get('/', [BookingNewController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/newbooking
    //     $routes->post('create_new_booking', [BookingNewController::class, 'createNewBooking']); // route : base_url/newbooking/createNewBooking
    //     $routes->get('all_farebase', [BookingNewController::class, 'all_farebase']); // route : base_url/newbooking/all_carriers
    //     $routes->get('delete_farebase', [BookingNewController::class, 'delete_farebase']); // route : base_url/newbooking/delete_carrier
    //     $routes->post('add_farebase', [BookingNewController::class, 'add_farebase']); // route : base_url/newbooking/add_carrier
    //     $routes->post('get', [BookingNewController::class, 'get_details']); // route : base_url/newbooking/get_details
    //     $routes->post('finalize_itinenary', [BookingNewController::class, 'finalize_itinenary']); // route : base_url/newbooking/finalize_itinenary
    //     $routes->get('fetchBookingId', [BookingNewController::class, 'fetchBookingId']); // route : base_url/newbooking/fetchBookingId
    //     $routes->get('fetchDetails', [BookingNewController::class, 'fetchDetails']); // route : base_url/newbooking/fetchDetails
    //     $routes->get('fetchAirport', [BookingNewController::class, 'fetchAirport']); // route : base_url/newbooking/fetchAirport
    //     $routes->get('itinenaryDetails', [BookingNewController::class, 'itinenaryDetails']); // route : base_url/newbooking/itinenaryDetails
    //     $routes->post('get_itinerary', [BookingNewController::class, 'get_itinerary']); // route : base_url/newbooking/get_itinerary
    //     $routes->post('scan_passport', [BookingNewController::class, 'scanPassport']); // route : base_url/newbooking/scan_passport

    //     $routes->group('passenger', static function ($routes) {
    //         $routes->post('get_passengers', [BookingNewController::class, 'get_passengers']); // route : base_url/booking/passenger/get_passengers
    //         // $routes->post('save_passengers', [BookingNewController::class, 'save_passengers']); // route : base_url/booking/passenger/save_passengers
    //         $routes->post('more_details', [BookingNewController::class, 'more_details']); // route : base_url/reservation/more_details
    //         $routes->post('add_passenger_tempary', [BookingNewController::class, 'add_passenger_tempary']); // route : base_url/booking/passenger/add_passenger_tempary
    //         $routes->post('get_passenger_status', [BookingNewController::class, 'get_passenger_status']); // route : base_url/booking/passenger/get_passenger_status
    //         $routes->post('delete_passenger', [BookingNewController::class, 'delete_passenger']); // route : base_url/booking/passenger/delete_passenger
    //     });

    // });

    $routes->group('tour_arrival', static function ($routes) {
        $routes->get('/', [TourArrivalNotificationController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/tour/tour_arrival
        $routes->get('filter', [TourArrivalNotificationController::class, 'get_tour']); // route : base_url/tour/tour_dashboard/monthly_dashboard
    });

    $routes->group('tour_accounts', static function ($routes) {
        $routes->get('/', [TourAccountsController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->get('filter', [TourAccountsController::class, 'get_tour']);
        $routes->post('update', [TourAccountsController::class, 'update']);
        $routes->post('payment_request', [TourAccountsController::class, 'payment_request']);
        $routes->post('update_transport', [TourAccountsController::class, 'update_transport']);
        $routes->get('payment_requested_dashboard', [TourAccountsController::class, 'payment_requested_dashboard']);
        $routes->get('payment_requested_report/(:any)', [TourAccountsController::class, 'payment_requested_report']);
        $routes->post('transport_payment_request', [TourAccountsController::class, 'transport_payment_request']);
        $routes->post('payment_request_final', [TourAccountsController::class, 'payment_request_final']);
        $routes->post('cancel_payment_request', [TourAccountsController::class, 'cancel_payment_request']);
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

//grn - route : base_url/inventory
$routes->group('inventory', static function ($routes) {
    $routes->group('item_registry', static function ($routes) {
        $routes->get('/', [StockCardController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/inventory/item_registry
        $routes->post('create', [StockCardController::class, 'create']); // route : base_url/inventory/item_registry/create
        $routes->get('all', [StockCardController::class, 'all_items']); // route : base_url/inventory/item_registry/all
        $routes->get('get_item/(:num)', [StockCardController::class, 'getItem']); // route : base_url/inventory/item_registry/getItem/:ID
        $routes->post('update', [StockCardController::class, 'update']); // route : base_url/inventory/item_registry/update
        $routes->get('readCsv', [StockCardController::class, 'readCsv']); // route : base_url/inventory/item_registry/readCsv
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
        $routes->post('update_qty', [ManualGrnController::class, 'update_qty']);
        $routes->post('remove_item', [ManualGrnController::class, 'remove_item']); 
        $routes->post('finalize_grn/(:any)/(:any)/(:any)', [ManualGrnController::class, 'finalize_grn']);

    });

    $routes->group('usage_note', static function ($routes) {
        $routes->get('/', [UsageNoteController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]);
        $routes->post('search_item', [UsageNoteController::class, 'search_item']);
        $routes->post('get_store_quantities', [UsageNoteController::class, 'get_store_quantities']);
        $routes->post('add_temp_item', [UsageNoteController::class, 'add_temp_item']);
        $routes->post('delete_temp_item', [UsageNoteController::class, 'delete_temp_item']);
        $routes->get('get_temp_items', [UsageNoteController::class, 'get_temp_items']);
        $routes->post('finalize_usage_note', [UsageNoteController::class, 'finalize_usage_note']);
        $routes->get('print/(:any)', [UsageNoteController::class, 'print']);
    });

});

// INQUIRY ENDPOINT 
$routes->group('inquiries', static function ($routes) {
    $routes->get('/', [InquiryController::class, 'index'], ['filter' => ['UserSessionFilter', 'UserPermissionFilter']]); // route : base_url/inquiries
    $routes->get('all', [InquiryController::class, 'all']); // route : base_url/inquiries/all
    $routes->get('get_inquiry/(:num)', [InquiryController::class, 'get_inquiry']); // route : base_url/inquiries/get_inquiry/:id
    $routes->post('new', [InquiryController::class, 'new']); // route : base_url/inquiries/new
    $routes->delete('delete/(:num)', [InquiryController::class, 'delete_inquiry']); // route : base_url/inquiries/delete
    $routes->get('accept/(:num)', [InquiryController::class, 'accept_inquiry']); // route : base_url/inquiries/accept/:id
    $routes->get('reject/(:num)', [InquiryController::class, 'reject_inquiry']); // route : base_url/inquiries/reject/:id
});

$routes->get('run1', [NewTourController::class, 'manualRun1']);
$routes->get('run2', [NewTourController::class, 'manualRun2']);
$routes->get('run3', [NewTourController::class, 'manualRun3']);

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
