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
            'dashboard2/transport/driver_allow/pending_tours',   // tf-pilot-r2: the page without a tour goes to the pending list
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
            'tour/tour_payment_dashboard/audit_log', 'master_files/bank_accounts/list',
        ],
        'report/bank_payment_check' => [
            'accounts/voucher_payments/get_pc_accumulated_balance',
            'tour/tour_payment_dashboard/batch', 'tour/tour_payment_dashboard/batch_data', 'tour/tour_payment_dashboard/bulk', 'tour/tour_payment_dashboard/bulk_data',
            'tour/tour_payment_dashboard/voucher', 'tour/tour_payment_dashboard/rate_for', 'tour/tour_payment_dashboard/file', 'tour/tour_payment_dashboard/document',
            'tour/tour_payment_dashboard/audit_log', 'master_files/bank_accounts/list',
            'tour/tour_payment_dashboard/remittance_prepare', 'tour/tour_payment_dashboard/remittance_send', 'tour/tour_payment_dashboard/bulk_remittance_prepare', 'tour/tour_payment_dashboard/bulk_remittance_send', 'tour/tour_payment_dashboard/remittance', 'tour/tour_payment_dashboard/remittance_pdf', 'tour/tour_payment_dashboard/bulk_remittance',   // tf-paydesk-r7d: the Accountant (Bank Payment Check)
        ],
        // tf-paydesk-r7d: the remittance advice (prepare, send, view) is for the Accountant and the Bank payment approver — a group
        // holding only one of these privileges reaches the remittance routes; the controller checks the privilege again
        'report/bank_payment_check/initiate' => ['tour/tour_payment_dashboard/initiate', 'tour/tour_payment_dashboard/bulk_initiate', 'tour/tour_payment_dashboard/dispute', 'tour/tour_payment_dashboard/remittance_prepare', 'tour/tour_payment_dashboard/remittance_send', 'tour/tour_payment_dashboard/bulk_remittance_prepare', 'tour/tour_payment_dashboard/bulk_remittance_send', 'tour/tour_payment_dashboard/remittance', 'tour/tour_payment_dashboard/remittance_pdf', 'tour/tour_payment_dashboard/bulk_remittance'],
        'report/bank_payment_check/approve' => ['tour/tour_payment_dashboard/paid', 'tour/tour_payment_dashboard/bulk_paid', 'tour/tour_payment_dashboard/remittance_prepare', 'tour/tour_payment_dashboard/remittance_send', 'tour/tour_payment_dashboard/bulk_remittance_prepare', 'tour/tour_payment_dashboard/bulk_remittance_send', 'tour/tour_payment_dashboard/remittance', 'tour/tour_payment_dashboard/remittance_pdf', 'tour/tour_payment_dashboard/bulk_remittance'],
        'tour/tour_payment_dashboard/batch' => ['tour/tour_payment_dashboard/batch_create', 'tour/tour_payment_dashboard/bulk_create', 'tour/tour_payment_dashboard/payables_release', 'tour/tour_payment_dashboard/payment_review'],
        'tour/tour_payment_dashboard/approve' => ['tour/tour_payment_dashboard/approve', 'tour/tour_payment_dashboard/reject', 'tour/tour_payment_dashboard/dispute', 'tour/tour_payment_dashboard/issue_voucher'],
    ];

    // tf-pilot-r1: the common starting page (Pilot) is open to every signed-in user; what it shows is decided by the
    // user's own privileges, and its data widgets keep their own menu requirements (the aliases above).
    private const LANDING_PATHS = ['dashboard2/tiles', 'home', 'home/tour_desk', 'home/tour_desk/data', 'home/tour_desk/upcoming', 'home/tour_desk/arrivals', 'home/reservation_desk', 'home/transport_desk', 'home/master_files', 'home/master_files/hotel', 'home/master_files/transport', 'home/master_files/tour', 'home/master_files/finance', 'home/master_files/system', 'home/finance_desk', 'home/approver_desk'];   // tf-home-r1: Home (the five desks) is a starting page too; tf-tourdesk-r1: the Tour Desk page (its tiles follow the user's own privileges)

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
        // tf-e8-access-r1: in enforce mode the Accounts addresses are decided by AccessPolicyFilter (the route registry:
        // exact addresses, page and action privileges); the "starts with" rule and its aliases below no longer apply to them.
        // In legacy and report mode nothing changes here.
        if (\App\Filters\AccessPolicyFilter::mode() === 'enforce' && \App\Filters\AccessPolicyFilter::handles((string) service('uri')->getRoutePath())) {
            return $request;
        }
        // tf-e8-access-r2 (decision X11, 12 Oct 2026): the Admin group has no Accounts access in ANY mode — not through a menu,
        // an alias or the Admin-only orphan pages below either. (In enforce mode AccessPolicyFilter has already refused it.)
        $e8Path = trim((string) service('uri')->getRoutePath(), '/');
        if (\App\Filters\AccessPolicyFilter::handles($e8Path) && in_array((int) session()->get('group_id'), \App\Filters\AccessPolicyFilter::deniedGroupIds(), true)) {
            return $this->deny($request, $e8Path, 'no Accounts access for this group (decision X11)');
        }

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

        // tf-paydesk-r7 (17 Oct 2026): the unified Payment Desk is reached by anyone holding a payment role, its adoption endpoint only by
        // holders of the bank-initiation privilege (as for the initiation itself), its setup by the System Administrator only (group 1 never
        // reaches this line).
        // PaymentDeskController checks the exact privilege again. No privilege row is added or changed by this.
        if (!$is_allowed && ($path === 'tour/payment_desk' || str_starts_with($path, 'tour/payment_desk/'))) {
            $pdHeld = array_map(static fn ($u) => trim((string) $u['url'], '/'), $allowed_urls);
            $pdAny = ['tour/tour_payment_dashboard', 'tour/tour_payment_dashboard/request', 'tour/tour_payment_dashboard/approve', 'tour/tour_accounts/payment_approval_v2', 'tour/tour_payment_dashboard/payables', 'tour/tour_payment_dashboard/batch', 'report/bank_payment_check', 'report/bank_payment_check/initiate', 'report/bank_payment_check/approve'];
            $pdNeed = in_array($path, ['tour/payment_desk', 'tour/payment_desk/data', 'tour/payment_desk/adopt_check'], true) ? $pdAny : ($path === 'tour/payment_desk/adopt' ? ['report/bank_payment_check/initiate'] : []);
            if (array_intersect($pdHeld, $pdNeed)) {
                $is_allowed = true;
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
