<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

use App\Libraries\MenuLoader;

class UserPermissionFilter implements FilterInterface
{
    // Backend routes that don't have their own menu entry but inherit permission
    // from a parent menu URL. Key = menu URL, value = backend routes it covers.
    private const MENU_ROUTE_ALIASES = [
        'settings/backup_database' => ['data/backup_database', 'data/db_backup_view'],
        'tour/tour_accounts/payment_requested_dashboard_new' => ['accounts/utility_bill/update_status'],
        // Tour timeline (Tour Edit) page AJAX endpoints - no menu entry of their own.
        'dashboard2/tour_search/timeline' => ['tour/sub_locations', 'tour/itinerary'],
        // Invoice Cancellation tile action - no menu entry of its own.
        'dashboard2/tour_search/inv_cancellation' => ['accounts/debtor_invoice/cancellation_request'],
        // Amend Invoice button on Tour/Monthly Dashboard - no menu entry of its own.
        'tour/tour_dashboard' => ['accounts/debtor_invoice_new'],
        // Transport Details page (under Tour Management) and Driver Expense page
        // both call the creditor invoice category dropdown AJAX endpoint, which
        // otherwise requires the separate Payment Amendment menu permission.
        'tour/booking_confirm' => [
            'accounts/creditor_invoice/get_details',
            'master_files/supplier/get_supplier_data',
            'tour/reserve_hotel',
            'tour/tour/get_hotel_details',
            'tour/tour/get_meal_prices',
            'tour/tour_details_search/confirm_tour',
        ],
        'dashboard2/transport/driver_expense' => ['accounts/creditor_invoice/get_details'],
        // Accepted Invoices / Monthly Dashboard "Amend Invoice" & invoice search actions.
        'tour/tour_accounts' => [
            'tour/tour_dashboard/search_debtor_invoice',
            'accounts/debtor_invoice_new',
            'dashboard2/transport/driver_logsheet',
            'dashboard2/transport/driver_expense/approve_tour_expenses',
            'dashboard2/transport/driver_expense/reject_tour_expenses',
        ],
        'tour/tour_details' => [
            'tour/tour_dashboard/search_debtor_invoice',
            'accounts/debtor_invoice_new',
        ],
        // Voucher Authorization / Audit pages call report & payment endpoints
        // that belong to other menu areas.
        'accounts/voucher_auth' => ['accounts/voucher_payments/get_pc_accumulated_balance'],
        'accounts/voucher_audit' => ['report/voucher_report/update'],
        // Debtor invoice arrival print sends receipts via the debtor_invoice_new controller.
        'accounts/debtor_invoice' => ['accounts/debtor_invoice_new/send_receipt'],
        // Tile dashboard driver-allocation widgets.
        'dashboard2/tiles' => [
            'dashboard2/searchTours',
            'dashboard2/getRecentTours',
            'dashboard2/deleteTour',
            'dashboard2/transport/driver_allow/available_tours',
            'dashboard2/transport/driver_allow/allocated_tours_json',
        ],
        // Log sheet page checks driver settlement balance.
        'dashboard2/transport/driver_logsheet' => ['dashboard2/transport/driver_settlement/get_balance_payment_details'],
        // Ongoing tours page drives the transport portal's admin actions.
        'dashboard2/transport/transport_ongoing' => ['dashboard2/transport/transport_portal'],
        // Hotel reservation page looks up supplier data.
        'dashboard2/hotel_management/hotel_reservation' => ['master_files/supplier/get_supplier_data'],
        // Inquiry timeline reuses the tour timeline's sub-location/itinerary/cancellation widgets,
        // and the menu entry is plural ("inquiries") while the whole route group is singular
        // ("inquiry/*"), so the normal prefix match never fires for any inquiry route.
        'inquiries' => [
            'inquiry',
            'tour/sub_locations',
            'tour/itinerary',
            'accounts/debtor_invoice/cancellation_request',
        ],
        // Driver Allocation page's tour detail modal/widgets - siblings under driver_allow/,
        // not sub-paths of the "tour_transport" menu URL, so not covered by prefix match.
        'dashboard2/transport/driver_allow/tour_transport' => [
            'dashboard2/transport/driver_allow/driver_allocation',
            'dashboard2/transport/driver_allow/remove_driver',
            'dashboard2/transport/driver_allow/driver_allocation_details',
            'dashboard2/transport/driver_allow/driver_availability_checker',
            'dashboard2/transport/driver_allow/suggested_advance',
            'dashboard2/transport/driver_allow/advance_request_history',
            'dashboard2/transport/driver_allow/available_drivers',
            'dashboard2/transport/driver_allow/get_driver_tours',
            'dashboard2/transport/driver_allow/get_driver_payment_status',
            'dashboard2/transport/driver_allow/get_driver_log',
        ],
        // Driver Advance Request page's own submit/update/delete actions - siblings
        // under driver_allow/, not sub-paths of the "advance_request" menu URL.
        'dashboard2/transport/driver_allow/advance_request' => [
            'dashboard2/transport/driver_allow/suggested_advance',
            'dashboard2/transport/driver_allow/advance_request_history',
            'dashboard2/transport/driver_allow/advance_request_add',
            'dashboard2/transport/driver_allow/update_advance_request',
            'dashboard2/transport/driver_allow/delete_advance_request',
        ],
        // Inventory Stock index/list page and its actions have no menu_list row of
        // their own - only "Stock Summary Report" exists for this feature area.
        'inventory/stock/summary_report' => [
            'inventory/stock',
            'inventory/stock/low_stock',
            'inventory/stock/search',
            'inventory/stock/process_return',
        ],
        // Staff Registration page's own create/update form submissions - siblings
        // under staff/, not sub-paths of the "staff/registration" menu URL.
        'staff/registration' => ['staff/create', 'staff/update'],
        // tf-paydesk-r1: the Tour Payment Dashboard makes and cancels payment requests through tour_accounts' own
        // endpoints (the same ones the Payment Forecast page uses), so a group holding the dashboard alone can request.
        'tour/tour_payment_dashboard' => [
            'master_files/bank_accounts/list',
            'master_files/supplier_bank/get',
        ],
        // tf-paydesk-r5: making / cancelling a payment request is its own privilege — seeing the dashboard does not grant it
        'tour/tour_payment_dashboard/request' => [
            'tour/tour_accounts/payment_request',
            'tour/tour_accounts/driver_advance_payment_request',
            'tour/tour_accounts/cancel_payment_request',
        ],
        // tf-pilot-r1: a payment role that does NOT hold the Tour Payment Dashboard menu (the Bank Authoriser, the Auditor,
        // an Accounts profile without the dashboard) still reaches the desk pages its own privilege is about — the pages are
        // read-only by route; every action inside is checked again by the controller against the role (Perms) and the
        // separation of duties (Separation).
        'tour/tour_payment_dashboard/payables' => [
            'tour/tour_payment_dashboard/payables_data', 'tour/tour_payment_dashboard/batch_items', 'tour/tour_payment_dashboard/bulk_eligibility',
            'tour/tour_payment_dashboard/batch', 'tour/tour_payment_dashboard/batch_data', 'tour/tour_payment_dashboard/bulk', 'tour/tour_payment_dashboard/bulk_data',
            'tour/tour_payment_dashboard/voucher', 'tour/tour_payment_dashboard/rate_for', 'tour/tour_payment_dashboard/sheet', 'tour/tour_payment_dashboard/file',
            'master_files/bank_accounts/list',
        ],
        'report/bank_payment_check' => [
            'accounts/voucher_payments/get_pc_accumulated_balance',
            'tour/tour_payment_dashboard/batch', 'tour/tour_payment_dashboard/batch_data', 'tour/tour_payment_dashboard/bulk', 'tour/tour_payment_dashboard/bulk_data',
            'tour/tour_payment_dashboard/voucher', 'tour/tour_payment_dashboard/rate_for', 'tour/tour_payment_dashboard/file', 'tour/tour_payment_dashboard/document',
            'master_files/bank_accounts/list',
        ],
        'report/bank_payment_check/initiate' => ['tour/tour_payment_dashboard/initiate', 'tour/tour_payment_dashboard/bulk_initiate', 'tour/tour_payment_dashboard/dispute'],
        'report/bank_payment_check/approve' => ['tour/tour_payment_dashboard/paid', 'tour/tour_payment_dashboard/bulk_paid'],
        'tour/tour_payment_dashboard/batch' => ['tour/tour_payment_dashboard/batch_create', 'tour/tour_payment_dashboard/bulk_create'],
        'tour/tour_payment_dashboard/approve' => ['tour/tour_payment_dashboard/approve', 'tour/tour_payment_dashboard/reject', 'tour/tour_payment_dashboard/dispute', 'tour/tour_payment_dashboard/issue_voucher'],
    ];

    // tf-pilot-r1: the common starting page (Pilot) is open to every signed-in user; what it shows is decided by the
    // user's own privileges, and its data widgets keep their own menu requirements (the aliases above).
    private const LANDING_PATHS = ['dashboard2/tiles'];

    // tf-pilot-r1: READ-ONLY groups (the Auditor profile and any profile whose name says read-only / auditor) may open what
    // they are granted but never change anything: no POST / PUT / DELETE, and none of the GET routes that act.
    private const READ_ONLY_GROUPS = [34];
    private const MUTATING_GET = '~(^|/)(add|remove|delete|destroy|update|create|save|store|cancel|approve|reject|finalize|finalized|mark|send|post|insert|confirm|verify|setup|initiate|paid|release|allocate|assign|upload|import|generate|reset|edit)(/|$)~i';

    // Pages with no menu_list entry of their own (so no group can be granted
    // access via Group Privilege) that are restricted to System Administrator
    // (group_id 1, already fully bypassed below) and Admin (group_id 25) only.
    private const ADMIN_ONLY_ORPHAN_PATHS = [
        'accounts/service_invoice',
        'accounts/voucher_print',
        'dashboard2/voucher_auth',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Super admin has full access
        if ($data['user_role'] == 1) {
            return $request;
        }

        // get request uri
        $uri = service('uri');
        $path = $uri->getRoutePath();

        // tf-pilot-r1: read-only groups never act
        if ($this->readOnly($data)) {
            $method = strtoupper($request->getMethod());
            if ($method !== 'GET' || preg_match(self::MUTATING_GET, $path)) {
                return $this->deny($request, $path, 'read-only profile: ' . $method . ' ' . $path);
            }
        }

        // tf-pilot-r1: the common starting page
        foreach (self::LANDING_PATHS as $landing) {
            if ($path === $landing) {
                return $request;
            }
        }

        if ($data['user_role'] == 25) {
            foreach (self::ADMIN_ONLY_ORPHAN_PATHS as $orphan) {
                if ($path === $orphan || strpos($path, rtrim($orphan, '/') . '/') === 0) {
                    return $request;
                }
            }
        }

        $allowed_urls = array();
        foreach ($data['allowed_menu_list'] as $menu) {
            $allowed_urls[] = [
                'url' => $menu['url'],
                'menu' => $menu['menu'],
            ];
        }

        $is_allowed = false;

        // url with query params / dynamic urls
        $urls_with_query_params = array(
            'settings/group_privilege',
            'accounts/voucher_payments',
        );

        foreach ($urls_with_query_params as $url) {
            if (strpos($path, $url) !== false) {
                $path = $url . '/0';
                break;
            }
        }

        foreach ($allowed_urls as $url) {
            // exact match, or sub-path of an allowed url (e.g. settings/user_account/create)
            if ($path == $url['url'] || strpos($path, rtrim($url['url'], '/') . '/') === 0) {
                $is_allowed = true;

                $db = \Config\Database::connect();
                $db->table('a_log')->insert([
                    'Usr' => session()->get('username'),
                    'Url' => $url['url'],
                    'Panel' => $url['menu'],
                    'Date' => date('Y-m-d H:i:s'),
                    'Tym' => date('Y-m-d H:i:s'),
                    'ip' => $request->getIPAddress(),
                ]);
                // do NOT close the shared DB connection - the DB session handler needs it at shutdown
                break;
            }

            // Check route aliases — backend routes that inherit a menu entry's permission
            foreach (self::MENU_ROUTE_ALIASES[$url['url']] ?? [] as $alias) {
                if ($path === $alias || strpos($path, rtrim($alias, '/') . '/') === 0) {
                    $is_allowed = true;
                    break 2;
                }
            }
        }

        if ($is_allowed) {
            return $request;
        }
        return $this->deny($request, $path, 'no privilege covers ' . $path);
    }

    /** tf-pilot-r1: the group is read-only (the Auditor profile, or any profile named read-only / auditor). */
    private function readOnly(array $data): bool
    {
        $gid = (int) ($data['user_role'] ?? 0);
        if ($gid === 1) {
            return false;
        }
        if (in_array($gid, self::READ_ONLY_GROUPS, true)) {
            return true;
        }
        $name = strtolower((string) ($data['sidebar_employee_name_profile_name'] ?? ''));
        return str_contains($name, 'read-only') || str_contains($name, 'read only') || str_contains($name, 'auditor');
    }

    /**
     * tf-pilot-r1: every refusal is recorded (tpd_audit when the payment desk is installed, else the access log a_log with
     * Panel = DENIED) — who, what, how, from where — then answered with 403 (JSON for AJAX, the 403 page otherwise).
     */
    private function deny(RequestInterface $request, string $path, string $why)
    {
        try {
            $db = \Config\Database::connect();
            $user = (string) session()->get('username');
            $row = ['user' => $user, 'path' => $path, 'method' => strtoupper($request->getMethod()), 'ip' => $request->getIPAddress(), 'group_id' => (int) session()->get('group_id'), 'why' => $why];
            if ($db->query("SHOW TABLES LIKE 'tpd_audit'")->getResultArray()) {
                $db->table('tpd_audit')->insert(['at' => date('Y-m-d H:i:s') . '.000', 'user' => $user, 'user_id' => (int) session()->get('user_id'), 'action' => 'access_denied', 'ref_type' => 'route', 'ref_id' => mb_substr($path, 0, 80), 'reason' => mb_substr($why, 0, 500), 'new_value' => json_encode($row), 'ip' => $request->getIPAddress()]);
            } else {
                $db->table('a_log')->insert(['Usr' => $user, 'Url' => mb_substr($path, 0, 250), 'Panel' => 'DENIED', 'Date' => date('Y-m-d H:i:s'), 'Tym' => date('Y-m-d H:i:s'), 'ip' => $request->getIPAddress()]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'access denied (unlogged): ' . $why . ' — ' . $e->getMessage());
        }
        // AJAX / JSON requests get a 403 JSON response; page requests get the 403 view
        if ($request->isAJAX() || str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json')) {
            return service('response')->setStatusCode(403)->setJSON(['status' => 403, 'msg' => 'Forbidden', 'message' => 'You do not have the privilege for this action. The attempt was recorded.']);
        }
        return redirect()->to(base_url('403'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something after the route has executed
    }
}
