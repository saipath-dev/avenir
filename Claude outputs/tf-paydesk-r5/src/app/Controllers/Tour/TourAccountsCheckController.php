<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\TourQuotationsModel;
use App\Models\ConsigneeModel;
use App\Models\AccountRunningModel;
use App\Models\LogSheetMileModel;
use App\Models\VoucherPaymentModel;
use App\Models\PaymentRequestModel;
use App\Models\DriverAdvanceRequestModel;
use App\Models\InvalertlogModel;
use App\Models\CustomerModel;
use App\Models\SpecialPaymentRequestModel;
use App\Models\DriverAllocationModel;
use App\Models\DriverExpensesModel;
use App\Models\UtilityBillModel;
use App\Models\AccountPostingModel;
use App\Models\InvoiceNumberModel;
use App\Models\SupplierModel;
use App\Models\VoucherInvoiceModel;
use App\Models\HotelRateModel;
use App\Models\RoomTypeModel;
// use App\Models\TourQuotationsModel;

class TourAccountsCheckController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // payment_requested_dashboard function
    // Kept as an alias of payment_approval_v2() — same view, same data shape (paymentRequestsByPayee).
    // This used to build its own un-grouped $paymentRequests array that the view never actually
    // reads (the view loops over $paymentRequestsByPayee), so this URL always showed an empty
    // table regardless of what was in payment_request. Delegating avoids that drift permanently.
    public function payment_requested_dashboard_new()
    {
        return $this->payment_approval_v2();
    }

    /**
     * Payment Approval (New) — Payment Requests grouped by Payee (vendor), collapsible per payee,
     * same style as the Bank Payment Check report. Driver Settlement Requests and Utility Bill
     * Payments sections are unchanged from payment_requested_dashboard_new().
     */
    public function payment_approval_v2()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        helper('daily_usd_rate');
        $data['today_usd_rate'] = get_usd_rate();

        $PaymentRequestModel = new PaymentRequestModel();

        // Individual pending payment request rows (not grouped by tour) so they can be
        // grouped by Payee in the view — same source data as payment_requested_report_new.
        $paymentRequests = $PaymentRequestModel
            ->select('payment_request.*, consignee.tour_cancel, consignee.start_date, consignee.end_date, consignee.passenger_count')
            ->join('consignee', 'consignee.tour_id = payment_request.tour_id')
            ->where('payment_request.payment_request', '1')
            ->where('consignee.tour_cancel <>', 1)
            ->orderBy('payment_request.payment_request_date', 'ASC')
            ->findAll();

        // Group by Payee (hotel_name if set, else consignee/tour name — same rule the
        // existing report view uses to display the payee column)
        $paymentRequestsByPayee = [];
        foreach ($paymentRequests as $request) {
            $payee = !empty($request['hotel_name']) ? $request['hotel_name'] : $request['consignee'];
            $payee = $payee !== '' && $payee !== null ? $payee : 'Unknown Payee';

            // Check-in date: all hotel stay dates (or single activity date), depending on request type
            $dateInfo = $this->getCheckinDate($request['tour_id'], $request['hotel_acc'], $request['type'] ?? '');
            $request['checkin_date'] = $dateInfo['checkin_date'];
            $request['all_dates']    = $dateInfo['all_dates'];
            $request['night_count']  = $dateInfo['night_count'];

            // For Hotel type, compute LKR equivalent using the confirmed dollar rate, the
            // booked room type(s), and a per-room-type hotel_rate system-rate breakdown
            // (a cross-check against the requested amount, not used in any total).
            if (($request['type'] ?? '') === 'Hotel') {
                $rate = $this->getHotelDollarRate($request['tour_id'], $request['hotel_acc']);
                $request['lkr_amount'] = (!empty($rate)) ? round((float)$request['requestAmount'] * (float)$rate, 2) : null;
                $request['dollar_rate'] = $rate;
                $request['room_types'] = $this->getRoomTypeSummary($request['tour_id'], $request['hotel_acc'], $request['checkin_date']);
                $request['quoted_breakdown'] = $this->getQuotedRoomBreakdown($request['tour_id'], $request['hotel_acc'], $request['checkin_date']);
                $request['system_rates'] = $this->getSystemRateBreakdown($request['tour_id'], $request['hotel_acc'], $request['checkin_date']);
                // Hotel cost handoff: the payable amount per the Hotel Rates panel (ad hoc → contract →
                // quoted flagged), night by night, with its source — replaces the single check-in-date
                // lookup above whenever the tour has been costed.
                $request['hotel_cost'] = $this->getHotelCost($request['tour_id'], $request['hotel_acc']);
            }

            if (!isset($paymentRequestsByPayee[$payee])) {
                $paymentRequestsByPayee[$payee] = [
                    'payee'           => $payee,
                    'requests'        => [],
                    'total_payable'   => 0,
                    'total_payable_usd' => 0,
                    'total_payable_lkr' => 0,
                    'pax_count'       => 0,
                    'pax_tour_ids'    => [],
                ];
            }

            // Count passengers once per tour so a tour with multiple payment
            // requests (hotel + lunch etc.) doesn't inflate the pax total.
            if (!in_array($request['tour_id'], $paymentRequestsByPayee[$payee]['pax_tour_ids'], true)) {
                $paymentRequestsByPayee[$payee]['pax_tour_ids'][] = $request['tour_id'];
                $paymentRequestsByPayee[$payee]['pax_count'] += (int) ($request['passenger_count'] ?? 0);
            }

            $isLkr = ($request['type'] ?? '') === 'lunch' || ($request['create_user'] ?? '') === 'Transport Agent';
            $paymentRequestsByPayee[$payee]['requests'][] = $request;
            $paymentRequestsByPayee[$payee]['total_payable'] += (float) ($request['requestAmount'] ?? 0);
            if ($isLkr) {
                $paymentRequestsByPayee[$payee]['total_payable_lkr'] += (float) ($request['requestAmount'] ?? 0);
            } else {
                $paymentRequestsByPayee[$payee]['total_payable_usd'] += (float) ($request['requestAmount'] ?? 0);
                // Also accumulate LKR equivalent for Hotel requests
                if (($request['type'] ?? '') === 'Hotel' && !empty($request['lkr_amount'])) {
                    $paymentRequestsByPayee[$payee]['total_payable_lkr'] += $request['lkr_amount'];
                }
            }
        }

        // Within each payee group, order requests by check-in/activity date (soonest first).
        // Requests with no check-in date (Transport Advance, etc.) sort to the end.
        foreach ($paymentRequestsByPayee as &$group) {
            usort($group['requests'], function ($a, $b) {
                $dateA = $a['checkin_date'] ?? null;
                $dateB = $b['checkin_date'] ?? null;
                if (empty($dateA) && empty($dateB)) {
                    return 0;
                }
                if (empty($dateA)) {
                    return 1;
                }
                if (empty($dateB)) {
                    return -1;
                }
                return strtotime($dateA) <=> strtotime($dateB);
            });
        }
        unset($group);

        // Sort payee groups by total payable, highest first
        usort($paymentRequestsByPayee, function ($a, $b) {
            return $b['total_payable'] <=> $a['total_payable'];
        });

        $data['paymentRequestsByPayee'] = $paymentRequestsByPayee;

        // Total USD and LKR payable across all payment requests
        $db = \Config\Database::connect();
        $totalsRow = $db->query("
            SELECT
                SUM(CASE WHEN pr.type = 'lunch' OR pr.create_user = 'Transport Agent' THEN pr.requestAmount ELSE 0 END) AS total_lkr,
                SUM(CASE WHEN pr.type != 'lunch' AND pr.create_user != 'Transport Agent' THEN pr.requestAmount ELSE 0 END) AS total_usd
            FROM payment_request pr
            JOIN consignee c ON c.tour_id = pr.tour_id
            WHERE pr.payment_request = '1'
              AND c.tour_cancel <> 1
        ")->getRow();
        $data['payment_requests_total_lkr'] = $totalsRow->total_lkr ?? 0;
        $data['payment_requests_total_usd'] = $totalsRow->total_usd ?? 0;

        // Get approved expenses per driver per tour so we can group by driver in the view.
        // The `de` join is scoped by (tour_id, agent), not just tour_id — a tour can have more
        // than one driver, and a driver must only show up here once THEIR OWN expenses are
        // approved, netted against only THEIR OWN advance/expenses — not another driver's.
        $approvedExpensesRaw = $db->query("
            SELECT
                c.consignee,
                c.manual_id,
                c.start_date,
                c.end_date,
                c.tour_cancel,
                c.create_user,
                c.passenger_count,
                da.tour_id,
                da.driver,
                COALESCE(NULLIF(s.Name,''), NULLIF(da.driver_name,''), 'Unknown Driver') AS driver_name,
                (CASE WHEN settled.ref_po IS NULL
                    THEN COALESCE((lsm.log_sheet_mileage * lsm.vehicle_rate) + (lsm.per_day_rate * lsm.no_of_dates), 0)
                    ELSE 0 END)
                    - ((CASE WHEN settled.ref_po IS NULL THEN COALESCE(vp.advance_amount, 0) ELSE 0 END) - COALESCE(de.expenses_lkr, 0)) AS total_lkr_payable
            FROM driver_allocation da
            JOIN (
                SELECT tour_id, agent, SUM(lkr_amount) AS expenses_lkr
                FROM driver_expenses
                WHERE status = 'approved'
                GROUP BY tour_id, agent
            ) de ON de.tour_id COLLATE utf8mb4_0900_ai_ci = da.tour_id COLLATE utf8mb4_0900_ai_ci
                 AND de.agent COLLATE utf8mb4_0900_ai_ci = da.driver COLLATE utf8mb4_0900_ai_ci
            LEFT JOIN consignee c ON c.tour_id COLLATE utf8mb4_0900_ai_ci = da.tour_id COLLATE utf8mb4_0900_ai_ci
            LEFT JOIN supplier s ON s.Sup_Acc_No = da.driver AND s.sup_cat = 2
            LEFT JOIN (
                SELECT tour_id, transport_agent,
                    log_sheet_mileage, vehicle_rate, per_day_rate, no_of_dates
                FROM log_sheet_mile lsm_inner
                WHERE id = (
                    SELECT MAX(id) FROM log_sheet_mile
                    WHERE tour_id = lsm_inner.tour_id AND transport_agent = lsm_inner.transport_agent
                )
            ) lsm ON lsm.tour_id COLLATE utf8mb4_0900_ai_ci = da.tour_id COLLATE utf8mb4_0900_ai_ci
                  AND lsm.transport_agent COLLATE utf8mb4_0900_ai_ci = da.driver COLLATE utf8mb4_0900_ai_ci
            LEFT JOIN (
                SELECT CONVERT(ref_po USING utf8mb4) AS ref_po,
                       CONVERT(Acc_No USING utf8mb4) AS Acc_No,
                       SUM(Amount) AS advance_amount
                FROM voucher
                WHERE Aflag = 1 AND advances_pay = 1
                GROUP BY ref_po, Acc_No
            ) vp ON vp.ref_po COLLATE utf8mb4_0900_ai_ci = da.tour_id COLLATE utf8mb4_0900_ai_ci
                 AND vp.Acc_No COLLATE utf8mb4_0900_ai_ci = da.driver COLLATE utf8mb4_0900_ai_ci
            LEFT JOIN (
                SELECT DISTINCT CONVERT(ref_po USING utf8mb4) AS ref_po,
                       CONVERT(Acc_No USING utf8mb4) AS Acc_No
                FROM voucher
                WHERE advances_pay = 11
            ) settled ON settled.ref_po COLLATE utf8mb4_0900_ai_ci = da.tour_id COLLATE utf8mb4_0900_ai_ci
                 AND settled.Acc_No COLLATE utf8mb4_0900_ai_ci = da.driver COLLATE utf8mb4_0900_ai_ci
            WHERE c.tour_cancel <> 1
            ORDER BY driver_name ASC, c.start_date ASC
        ")->getResultArray();

        // Group by driver
        $approvedExpensesByDriver = [];
        foreach ($approvedExpensesRaw as $row) {
            $driverKey = $row['driver_name'];
            if (!isset($approvedExpensesByDriver[$driverKey])) {
                $approvedExpensesByDriver[$driverKey] = [
                    'driver_name'     => $row['driver_name'],
                    'tours'           => [],
                    'total_lkr_payable' => 0,
                    'pax_count'       => 0,
                    'pax_tour_ids'    => [],
                ];
            }

            // Count passengers once per tour in case a driver has multiple rows for the same tour.
            if (!in_array($row['tour_id'], $approvedExpensesByDriver[$driverKey]['pax_tour_ids'], true)) {
                $approvedExpensesByDriver[$driverKey]['pax_tour_ids'][] = $row['tour_id'];
                $approvedExpensesByDriver[$driverKey]['pax_count'] += (int) ($row['passenger_count'] ?? 0);
            }

            $approvedExpensesByDriver[$driverKey]['tours'][] = $row;
            $approvedExpensesByDriver[$driverKey]['total_lkr_payable'] += (float) ($row['total_lkr_payable'] ?? 0);
        }
        // Re-index
        $data['approvedExpensesByDriver'] = array_values($approvedExpensesByDriver);

        // Keep flat list for grand total
        $data['approvedExpenses'] = $approvedExpensesRaw;

        $utilityBillModel = new UtilityBillModel();
        $data['utilityBills'] = $utilityBillModel->where('status', 0)->findAll();

        // Driver Allocation Requests — pending overrides where a driver with an unpaid past
        // settlement was requested for a new tour anyway (request_allocation = 1).
        $data['driverAllocationRequests'] = $db->query("
            SELECT
                da.id,
                da.tour_id,
                da.manual_id,
                da.driver,
                da.driver_name,
                da.requested_user,
                da.requested_date,
                c.consignee,
                c.start_date,
                c.end_date,
                c.passenger_count
            FROM driver_allocation da
            LEFT JOIN consignee c ON c.tour_id = da.tour_id
            WHERE da.request_allocation = 1
            ORDER BY da.requested_date ASC
        ")->getResultArray();

        // Bank balances (exclude USD and Control accounts)
        $accountPostingModel = new AccountPostingModel();
        $banks = $accountPostingModel
            ->where('B_Code', 1)
            ->where('T_Code', 6)
            ->where('H_Code', 8)
            ->notLike('PName', 'USD')
            ->notLike('PName', 'Control')
            ->notLike('PName', 'Yen')
            ->findAll();

        $accountRunningModel = new AccountRunningModel();
        foreach ($banks as $key => $bank) {
            $row = $db->query(
                "SELECT COALESCE(SUM(Dr), 0) - COALESCE(SUM(Cr), 0) AS balance FROM account_runing WHERE L_Code = ? AND Date >= '2026-04-01'",
                [$bank['L_Code']]
            )->getRow();
            $banks[$key]['balance'] = $row->balance ?? 0;
        }
        $data['bank_balances'] = $banks;
        $data['bank_acc'] = $banks;

        return view('pages/tour/payment_approval_v2', $data);
    }


    // route : base_url/tour/ArraivalNotification
    public function payment_requested_report_new()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Check if 'view_all' is set in the GET request
        $v_all = $this->request->getGet('view_all') ? true : false;

        $lastSegment = $this->request->getUri()->getSegment(4);
        $tour = urldecode($lastSegment);
        // Initialize the model
        $PaymentRequestModel = new PaymentRequestModel();

        // Build query conditionally
        if (!$v_all) {
            $PaymentRequestModel = $PaymentRequestModel->where('payment_request', '1');
        }

        // Fetch results
        $paymentRequests = $PaymentRequestModel->where('manual_id', $tour)->orderBy('payment_request_date', 'ASC')->findAll();

        // For Hotel type, compute the LKR equivalent using the confirmed dollar rate
        foreach ($paymentRequests as &$req) {
            if (($req['type'] ?? '') === 'Hotel') {
                $rate = $this->getHotelDollarRate($req['tour_id'], $req['hotel_acc']);
                $req['lkr_amount'] = (!empty($rate)) ? round((float)$req['requestAmount'] * (float)$rate, 2) : null;
                $req['dollar_rate'] = $rate;
            }
        }
        unset($req);
        $data['paymentRequests'] = $paymentRequests;

        // Get tour info from consignee table for accurate dates
        $consigneeModel = new \App\Models\ConsigneeModel();
        $data['tourInfo'] = $consigneeModel->where('manual_id', $tour)->first();

        // LKR bank balances (exclude USD, Control, Yen)
        $accountPostingModel = new AccountPostingModel();
        $banks = $accountPostingModel
            ->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)
            ->notLike('PName', 'USD')
            ->notLike('PName', 'Control')
            ->notLike('PName', 'Yen')
            ->findAll();

        $db = \Config\Database::connect();
        foreach ($banks as $key => $bank) {
            $row = $db->query(
                "SELECT COALESCE(SUM(Dr), 0) - COALESCE(SUM(Cr), 0) AS balance FROM account_runing WHERE L_Code = ? AND Date >= '2026-04-01'",
                [$bank['L_Code']]
            )->getRow();
            $banks[$key]['balance'] = $row->balance ?? 0;
        }
        $data['bank_balances'] = $banks;
        $data['bank_acc'] = $banks;

        return view('pages/tour/payment_requested_report_new', $data);
    }

    // Accept or Reject payment request
    public function update_payment_request_status()
    {
        $id = $this->request->getPost('id');
        $type = $this->request->getPost('type');
        $status = $this->request->getPost('status'); // 4 = accept, 5 = reject
        $isBulk = $this->request->getPost('is_bulk');
        $bank = $this->request->getPost('bank_acc');
        $bank_name = $this->request->getPost('bank_name');

        if (!$id || !in_array($status, ['4', '5'])) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Invalid request']);
        }

        $PaymentRequestModel = new PaymentRequestModel();

        if ($isBulk == 1 && $status == '4') {
            $status = 6;
        }

        // tf-paydesk-r2: with Supplier Payables in place an approval raises NO voucher — the approved request waits under its
        // supplier until management creates the supplier's payment batch (create_bulk_voucher, batch mode). The accrual
        // entries of a service fee are still posted here, as before; no paying bank account is needed at this step.
        $tpdAccumulate = \App\Libraries\TourPayments\Batches::accumulates();

        $updateData = [
            'payment_request' => $status,
            'accept_user' => session()->get('username'),
            'accept_date' => date('Y-m-d H:i:s')
        ];

        $result = $PaymentRequestModel->update($id, $updateData);

        if ($result) {
            // Post outstanding account_running record on accept

            if ($type == 'lunch' && ($status == '4' || $status == '6')) {
                $request = $PaymentRequestModel->find($id);

                if (empty($bank) && !$tpdAccumulate) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Bank account is required to accept a lunch payment request']);
                }

                // If is_bulk = 1, set all other pending requests for same hotel_acc to 6
                if ($request['is_bulk'] == 1 && !$tpdAccumulate) {
                    $PaymentRequestModel->where('hotel_acc', $request['hotel_acc'])
                        // ->where('payment_request', '1')
                        ->where('id !=', $id)
                        ->set(['payment_request' => '6'])
                        ->update();
                }

                $supplierModel = new SupplierModel();
                $supplier = $supplierModel->where('Sup_Acc_No', $request['hotel_acc'])->first();
                $supplierLCode = $supplier['Sup_Acc_No'] ?? $request['hotel_acc'];

                $invoiceNoModel = new InvoiceNumberModel();
                $acc_id = $invoiceNoModel->incrementAccId();

                $accountRunningModel = new AccountRunningModel();
                $description = 'Lunch or Other Fee ' . $request['manual_id'] . ' / ' . $request['hotel_name'];

                $accountRunningModel->insertBatch([
                    [ // Dr — expense account
                        'ID'           => $acc_id,
                        'L_Code'       => '4-8-109-10653',
                        'Date'         => date('Y-m-d'),
                        'Discription'  => $description,
                        'Dr'           => $request['requestAmount'],
                        'Cr'           => 0.00,
                        'Invoice_Num'  => $request['tour_id'],
                        'Linkacc'      => $supplierLCode,
                        'index_no'     => $request['tour_id'],
                        'job'          => '4-8-109-10653',
                        'advances_pay' => 22,
                        'fine_date'    => date('Y-m-d'),
                        'Dt'           => date('Y-m-d H:i:s'),
                        'Cashier'      => session()->get('username'),
                    ],
                    [ // Cr — supplier creditor account
                        'ID'           => $acc_id,
                        'L_Code'       => $supplierLCode,
                        'Date'         => date('Y-m-d'),
                        'Discription'  => $description,
                        'Dr'           => 0.00,
                        'Cr'           => $request['requestAmount'],
                        'Invoice_Num'  => $request['tour_id'],
                        'Linkacc'      => $supplierLCode,
                        'index_no'     => $request['tour_id'],
                        'job'          => '4-8-109-10653',
                        'advances_pay' => 22,
                        'fine_date'    => date('Y-m-d'),
                        'Dt'           => date('Y-m-d H:i:s'),
                        'Cashier'      => session()->get('username'),
                    ],
                ]);

                if ($tpdAccumulate) {
                    // the voucher comes with the supplier's batch (Supplier Payables); the request now waits there
                    $request = null;
                } else {
                $invoiceNoModel2 = new InvoiceNumberModel();
                $v_num_lunch = $invoiceNoModel2->incrementVoucherToken();
                $pay_date_lunch = date('Y-m-d H:i:s');

                $lunchVoucherData = [
                    'Pay' => $request['hotel_name'],
                    'emp_code' => $request['hotel_acc'],
                    'S_name' => $request['hotel_name'],
                    'Acc_No' => $request['hotel_acc'],
                    'sup' => '1',
                    'type' => 'Bank Transfer',
                    'Amount' => $request['requestAmount'],
                    'VAT' => '0',
                    'VAT_Amt' => '0',
                    'vat_m' => '0',
                    'nbt' => '0',
                    'nbt_amt' => '0',
                    'nbt_m' => '0',
                    'g_ttl' => $request['requestAmount'],
                    'Address' => '0',
                    'print' => '0',
                    'des' => '0',
                    'dv' => '0',
                    'app_user' => session()->get('acc_no'),
                    'login' => session()->get('username'),
                    'Aflag' => '0',
                    'v_typ' => 'Bank Transfer',
                    'ref_po' => $request['tour_id'],
                    'insflag' => 1,
                    'inv' => '0',
                    'Vnum' => $v_num_lunch,
                    'Bank' => $bank_name,
                    'Bank_acc' => $bank,
                    'C_Date' => $pay_date_lunch,
                    'C_Number' => $request['tour_id'],
                    'W_Date' => $pay_date_lunch,
                    'is_expenses' => 1,
                    'advances_pay' => 2,
                ];

                $lunchVoucherInvoiceData = [
                    'Date' => $pay_date_lunch,
                    'Invoice' => $request['tour_id'],
                    'Description' => $description,
                    'Units' => 0,
                    'Rate' => 0,
                    'Amount' => $request['requestAmount'],
                    'InvAmount' => $request['requestAmount'],
                    'V_tkn' => $v_num_lunch,
                    'ityp' => '1',
                    'link_code' => $supplierLCode,
                    'employee' => $request['hotel_name'],
                    'login' => session()->get('username'),
                    'INV_Date' => date('Y-m-d'),
                    'Payee' => $request['hotel_name'],
                    'account' => $supplierLCode
                ];

                $voucherPaymentModel = new VoucherPaymentModel();
                $voucherPaymentModel->insert($lunchVoucherData);

                $voucherInvoiceModel = new VoucherInvoiceModel();
                $voucherInvoiceModel->insert($lunchVoucherInvoiceData);

                $PaymentRequestModel->update($id, ['voucher_created' => 1]);
                }   // tf-paydesk-r2 (!$tpdAccumulate)
            }

            if ($type == 'lunch' && ($status == '5')) {
                $request = $PaymentRequestModel->find($id);
                

                $tourActivityModel = new \App\Models\TourActivityModel();
                $tourActivityModel->where('tour_id', $request['tour_id'])
                    ->where('supplier_id', $request['hotel_acc'])
                    ->set([
                        'payment_request' => 0,
                        'payment_request_user' => null,
                        'payment_request_date' => null,
                    ])
                    ->update();

                    log_message('critical', $tourActivityModel->getLastQuery());
            }

            if ($type == 'Hotel' && ($status == '4' || ($tpdAccumulate && $status == '6'))) {
                $request = $PaymentRequestModel->find($id);

                if (empty($bank) && !$tpdAccumulate) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Bank account is required to accept a hotel payment request']);
                }

                $doller_rate = $this->getHotelDollarRate($request['tour_id'], $request['hotel_acc']);

                if (empty($doller_rate)) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'No dollar rate found for this hotel reservation. Please confirm the hotel payment rate first.']);
                }
            }

            if ($type == 'Hotel' && ($status == '4') && !$tpdAccumulate) {   // tf-paydesk-r2: the voucher comes with the supplier's batch
                $lkr_amount = $request['requestAmount'] * $doller_rate;

                $invoiceNoModel = new InvoiceNumberModel();
                $v_num = $invoiceNoModel->incrementVoucherToken();
                $pay_date = date('Y-m-d H:i:s');
                $hotel_dates = $this->getHotelDates($request['tour_id'], $request['hotel_acc']);

                $voucherData = [
                    'Pay' => $request['hotel_name'],
                    'emp_code' => $request['hotel_acc'],
                    'S_name' => $request['hotel_name'],
                    'Acc_No' => $request['hotel_acc'],
                    'sup' => '1',
                    'type' => 'Bank Transfer',
                    'Amount' => $lkr_amount,
                    'VAT' => '0',
                    'VAT_Amt' => '0',
                    'vat_m' => '0',
                    'nbt' => '0',
                    'nbt_amt' => '0',
                    'nbt_m' => '0',
                    'g_ttl' => $lkr_amount,
                    'Address' => '0',
                    'print' => '0',
                    'des' => '0',
                    'dv' => '0',
                    'app_user' => session()->get('acc_no'),
                    'login' => session()->get('username'),
                    'Aflag' => '0',
                    'v_typ' => 'Bank Transfer',
                    'ref_po' => $request['tour_id'],
                    'insflag' => 1,
                    'inv' => '0',
                    'Vnum' => $v_num,
                    'Bank' => $bank_name,
                    'Bank_acc' => $bank,
                    'C_Date' => $pay_date,
                    'C_Number' => $request['tour_id'],
                    'W_Date' => $pay_date,
                    'is_expenses' => 1,
                    'advances_pay' => 2,
                    'V_num' => $hotel_dates,
                    'doller_amt' => $request['requestAmount']
                ];

                $voucherInvoiceData = [
                    'Date' => $pay_date,
                    'Invoice' => $request['tour_id'],
                    'Description' => 'Hotel Reservation ' . $request['manual_id'] . ' / ' . $request['hotel_name'],
                    'Units' => 0,
                    'Rate' => $doller_rate,
                    'Amount' => $lkr_amount,
                    'InvAmount' => $lkr_amount,
                    'V_tkn' => $v_num,
                    'ityp' => '1',
                    'link_code' => $request['hotel_acc'],
                    'employee' => $request['hotel_name'],
                    'login' => session()->get('username'),
                    'INV_Date' => date('Y-m-d'),
                    'Payee' => $request['hotel_name'],
                    'account' => $request['hotel_acc']
                ];

                $voucherPaymentModel = new VoucherPaymentModel();
                $voucherPaymentModel->insert($voucherData);

                $voucherInvoiceModel = new VoucherInvoiceModel();
                $voucherInvoiceModel->insert($voucherInvoiceData);

                $PaymentRequestModel->update($id, ['voucher_created' => 1]);
            }

            if ($type == 'Hotel' && ($status == '5')) {
                $request = $PaymentRequestModel->find($id);

                $tourQuotationsModel = new TourQuotationsModel();
                $tourQuotationsModel->where('quotation_id', $request['tour_id'])
                    ->where('hotel_acc', $request['hotel_acc'])
                    ->set([
                        'payment_request' => 0,
                        'payment_request_user' => '',
                        'hotel_advance' => 0.00,
                        'booking_confirmed_flag' => 0,
                    ])
                    ->update();

                // Remove the Dr/Cr account_running double entry for this hotel reservation, if any
                $accountRunningModel = new AccountRunningModel();
                $accountRunningModel->where('Invoice_Num', $request['tour_id'])
                    ->whereIn('L_Code', ['4-8-106-10386', $request['hotel_acc']])
                    ->delete();
            }

            if ($type == 'Transport Advance' && ($status == '4' || ($tpdAccumulate && $status == '6'))) {
                $request = $PaymentRequestModel->find($id);

                if (empty($bank) && !$tpdAccumulate) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Bank account is required to accept a transport advance payment request']);
                }
                if ($tpdAccumulate) {
                    // tf-paydesk-r2: the driver is the payee of record from approval on; the advance voucher comes with the batch
                    $consigneeModel = new ConsigneeModel();
                    $consigneeModel->where('tour_id', $request['tour_id'])->set([
                        'transport_agent_name' => $request['consignee'],
                        'transport_agent' => $request['hotel_acc']
                    ])->update();
                }
            }

            if ($type == 'Transport Advance' && ($status == '4') && !$tpdAccumulate) {
                $invoiceNoModel3 = new InvoiceNumberModel();
                $v_num_transport = $invoiceNoModel3->incrementVoucherToken();
                $pay_date_transport = date('Y-m-d H:i:s');

                $transportVoucherData = [
                    'Pay' => $request['consignee'],
                    'emp_code' => $request['hotel_acc'],
                    'S_name' => $request['consignee'],
                    'Acc_No' => $request['hotel_acc'],
                    'sup' => '1',
                    'type' => 'Bank Transfer',
                    'Amount' => $request['requestAmount'],
                    'VAT' => '0',
                    'VAT_Amt' => '0',
                    'vat_m' => '0',
                    'nbt' => '0',
                    'nbt_amt' => '0',
                    'nbt_m' => '0',
                    'g_ttl' => $request['requestAmount'],
                    'Address' => '0',
                    'print' => '0',
                    'des' => '0',
                    'dv' => '0',
                    'app_user' => session()->get('acc_no'),
                    'login' => session()->get('username'),
                    'Aflag' => '0',
                    'v_typ' => 'Bank Transfer',
                    'ref_po' => $request['tour_id'],
                    'insflag' => 1,
                    'inv' => '0',
                    'Vnum' => $v_num_transport,
                    'Bank' => $bank_name,
                    'Bank_acc' => $bank,
                    'C_Date' => $pay_date_transport,
                    'C_Number' => $request['tour_id'],
                    'W_Date' => $pay_date_transport,
                    'is_expenses' => 1,
                    'advances_pay' => 1,
                    'doller_amt' => $request['requestAmount']
                ];

                $transportVoucherInvoiceData = [
                    'Date' => $pay_date_transport,
                    'Invoice' => $request['tour_id'],
                    'Description' => 'Transport Advance ' . $request['manual_id'],
                    'Units' => 0,
                    'Rate' => 0,
                    'Amount' => $request['requestAmount'],
                    'InvAmount' => $request['requestAmount'],
                    'V_tkn' => $v_num_transport,
                    'ityp' => '1',
                    'link_code' => $request['hotel_acc'],
                    'employee' => $request['consignee'],
                    'login' => session()->get('username'),
                    'INV_Date' => date('Y-m-d'),
                    'Payee' => $request['consignee'],
                    'account' => $request['hotel_acc']
                ];

                $voucherPaymentModel = new VoucherPaymentModel();
                $voucherPaymentModel->insert($transportVoucherData);

                $voucherInvoiceModel = new VoucherInvoiceModel();
                $voucherInvoiceModel->insert($transportVoucherInvoiceData);

                $consigneeModel = new ConsigneeModel();
                $consigneeModel->where('tour_id', $request['tour_id'])->set([
                    'transport_agent_name' => $request['consignee'],
                    'transport_agent' => $request['hotel_acc']
                ])->update();

                $PaymentRequestModel->update($id, ['voucher_created' => 1]);
            }

            if ($type == 'Transport Advance' && ($status == '5')) {
                $request = $PaymentRequestModel->find($id);

                $driverAdvanceRequestModel = new DriverAdvanceRequestModel();
                $driverAdvanceRequestModel->where('tour_id', $request['tour_id'])
                    ->where('driver', $request['hotel_acc'])
                    ->set(['flag' => 0])
                    ->update();
            }

            $statusText = $status == '4' ? 'accepted' : 'rejected';
            // tf-paydesk-r1: the desk keeps who approved, when, and the voucher raised — whichever screen the approval came from
            try {
                $tpdDb = \Config\Database::connect();
                $tpdReq = $PaymentRequestModel->find($id);
                if ($status == '5') {
                    \App\Libraries\TourPayments\Audit::log($tpdDb, ['action' => 'rejected', 'tour_id' => $tpdReq['tour_id'] ?? null, 'manual_id' => $tpdReq['manual_id'] ?? null, 'ref_type' => 'request', 'ref_id' => $id, 'prev' => ['status' => 1], 'new' => ['status' => 5], 'reason' => 'Rejected on the Payment approval page']);
                } else {
                    $tpdVnum = \App\Libraries\TourPayments\PayDesk::recordApproval($tpdDb, (int) $id, (string) session()->get('username'));
                    \App\Libraries\TourPayments\Audit::log($tpdDb, ['action' => 'approved', 'tour_id' => $tpdReq['tour_id'] ?? null, 'manual_id' => $tpdReq['manual_id'] ?? null, 'ref_type' => 'request', 'ref_id' => $id, 'vnum' => $tpdVnum, 'prev' => ['status' => 1], 'new' => ['status' => (int) ($tpdReq['payment_request'] ?? $status), 'voucher' => $tpdVnum, 'amount' => $tpdReq['requestAmount'] ?? null], 'bank_account' => $bank_name ?: $bank, 'reason' => 'Approved on the Payment approval page']);
                    if ($tpdVnum) {
                        \App\Libraries\TourPayments\Audit::log($tpdDb, ['action' => 'voucher_issued', 'tour_id' => $tpdReq['tour_id'] ?? null, 'manual_id' => $tpdReq['manual_id'] ?? null, 'ref_type' => 'voucher', 'ref_id' => $tpdVnum, 'vnum' => $tpdVnum, 'bank_account' => $bank_name ?: $bank]);
                    }
                }
            } catch (\Throwable $tpdE) {
                log_message('error', 'tf-paydesk-r1 approval record: ' . $tpdE->getMessage());
            }
            return $this->response->setJSON(['status' => 200, 'message' => "Payment request {$statusText} successfully"]);
        } else {
            return $this->response->setJSON(['status' => 400, 'message' => 'Failed to update payment request']);
        }
    }

    // Get the applicable USD->LKR rate for a hotel reservation.
    // Prefers the global daily_usd_rate table (pegged to the stay's check-in date); falls back
    // to a manually-typed tour_quotations.hotel_dollar_rate for bookings that go through other
    // controllers (HotelManagementController, HotelPaymentController, NewTourController) that
    // still write that field directly.
    // Stored payable hotel cost for this tour + hotel (hr2_tour_hotel_cost), computed when the tour
    // was confirmed or last recalculated; null when nothing is stored (or the tables are absent).
    private function getHotelCost($tourId, $hotelAcc)
    {
        try {
            $svc = new \App\Libraries\HotelRates\HotelCostService(\Config\Database::connect(), (string) session()->get('username'));
            $sum = $svc->ensureTour((string) $tourId);
            // tf-offers-r1: tacticals / early birds only for a sign-in that may decide them
            $h = \App\Libraries\HotelRates\HotelCostService::forViewer($sum['hotels'][$hotelAcc] ?? null, \App\Libraries\HotelRates\OfferRates::currentUserCanDecide());
            if (!$h) {
                return null;
            }
            return ['pay_total' => (float) $h['pay_total'], 'quote_total' => (float) $h['quote_total'], 'margin' => (float) $h['margin'], 'supplements' => (float) $h['supplements_total'],
                'source' => $h['source'], 'source_label' => $h['source_label'], 'needs_check' => (bool) $h['needs_check'], 'check_notes' => $h['check_notes'], 'plans' => $h['plans'], 'nights' => $h['nights'],
                'rows' => array_map(static fn ($r) => ['date' => $r['date'], 'room_type' => $r['room_type'], 'meal_plan' => $r['meal_plan'], 'pay' => $r['pay'], 'quote' => $r['quote'], 'supplements' => $r['supplements'], 'pay_total' => $r['pay_total'], 'source' => $r['source'], 'plan' => $r['plan'], 'check_note' => $r['check_note']], $h['rows']),
                // tf-offers-r1: published rate beside the rate used, the tactical / early bird and the choice behind it
                'offer' => \App\Libraries\HotelRates\HotelCostService::offerFields($h)];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getHotelDollarRate($tourId, $hotelAcc)
    {
        // hotel_dollar_rate is written at Request time (TourAccountsController::payment_request())
        // using that day's exact daily_usd_rate — read it back here rather than re-resolving
        // "today's" rate again, since by Accept time "today" may no longer be the request date.
        $tourQuotationsModel = new TourQuotationsModel();
        $row = $tourQuotationsModel
            ->select('MAX(hotel_dollar_rate) as hotel_dollar_rate')
            ->where('quotation_id', $tourId)
            ->where('hotel_acc', $hotelAcc)
            ->first();

        return $row['hotel_dollar_rate'] ?? null;
    }

    // Get the comma-separated list of tour_dates for a hotel reservation from tour_quotations
    // (mirrors the 'dates' field the older hotel_payment() flows collect from the frontend;
    // VoucherPaymentController::update() needs this in voucher.V_num to flip hotel_payment_flag)
    private function getHotelDates($tourId, $hotelAcc)
    {
        $tourQuotationsModel = new TourQuotationsModel();
        $rows = $tourQuotationsModel
            ->select('tour_date')
            ->where('quotation_id', $tourId)
            ->where('hotel_acc', $hotelAcc)
            ->orderBy('tour_date', 'ASC')
            ->findAll();

        return implode(',', array_column($rows, 'tour_date'));
    }

    // Get the check-in / activity date for a payment request row.
    // Hotel: earliest tour_date in tour_quotations for this tour_id + hotel_acc (first night of stay).
    // lunch: the single tour_activity.tour_date for this tour_id + supplier_id (payment_request.hotel_acc
    // doubles as the activity's supplier_id for lunch-type requests).
    // Anything else (Transport Advance, etc.) has no per-item date to show.
    // Room type(s) booked for a hotel reservation — SGL/DBL/TPL combined with the booked
    // meal plan (e.g. "SHB", "DBB") and room count. No hotel_rate lookup here — this is
    // purely what was booked on the tour, not a rate cross-check.
    // tour_quotations.*count is the per-hotel count but is rarely populated by the
    // booking save flow, so when it's all zero this falls back to consignee.sgl/dbl/tpl —
    // the per-TOUR room counts set at booking time. That fallback is only an approximation
    // for a tour with multiple hotels (same count shown for each), but it's real stored
    // data rather than a guess, and is what's actually available in practice.
    private function getRoomTypeSummary($tourId, $hotelAcc, $checkinDate = null)
    {
        $tourQuotationsModel = new TourQuotationsModel();
        $row = $tourQuotationsModel
            ->select('SUM(sglcount) as sglcount, SUM(dblcount) as dblcount, SUM(tplcount) as tplcount, AVG(sgl) as sgl, AVG(dbl) as dbl, AVG(tpl) as tpl')
            ->where('quotation_id', $tourId)
            ->where('hotel_acc', $hotelAcc)
            ->first();

        if (empty($row)) {
            return [];
        }

        $sglCount = (int) ($row['sglcount'] ?? 0);
        $dblCount = (int) ($row['dblcount'] ?? 0);
        $tplCount = (int) ($row['tplcount'] ?? 0);

        // Per-hotel counts are all zero — fall back to the tour-level counts.
        if ($sglCount <= 0 && $dblCount <= 0 && $tplCount <= 0) {
            $consignee = (new ConsigneeModel())
                ->select('sgl, dbl, tpl')
                ->where('tour_id', $tourId)
                ->first();
            $sglCount = (int) ($consignee['sgl'] ?? 0);
            $dblCount = (int) ($consignee['dbl'] ?? 0);
            $tplCount = (int) ($consignee['tpl'] ?? 0);
        }

        $counts = ['sgl' => $sglCount, 'dbl' => $dblCount, 'tpl' => $tplCount];

        // Meal plan booked for this hotel — combined with room size for the label
        // (e.g. SGL + HB = "SHB"). '0'/blank means no meal plan was set.
        $mealCode = null;
        if (!empty($checkinDate)) {
            $qRow = (new TourQuotationsModel())
                ->select('meal_code')
                ->where('quotation_id', $tourId)
                ->where('hotel_acc', $hotelAcc)
                ->where('tour_date', $checkinDate)
                ->first();
            $mealCode = strtolower(trim((string) ($qRow['meal_code'] ?? '')));
        }
        $validMealCodes = ['ro', 'bb', 'hb', 'fb'];
        $mealSuffix = in_array($mealCode, $validMealCodes, true) ? strtoupper($mealCode) : '';

        // Only room sizes with an actual booked count are shown — tour_quotations.sgl/
        // dbl/tpl (rate) can be non-zero even when that size wasn't booked at all
        // (count = 0), so a rate alone isn't enough to say the room type was requested.
        $types = [];
        foreach (['sgl' => 'S', 'dbl' => 'D', 'tpl' => 'T'] as $field => $sizeLetter) {
            $count = $counts[$field];
            if ($count <= 0) {
                continue;
            }

            $label = $mealSuffix !== '' ? "{$sizeLetter}{$mealSuffix}" : strtoupper($field);
            $types[] = "{$label} x {$count}";
        }

        return $types;
    }

    // Per-room-type QUOTED amount for this booking — count × tour_quotations.sgl/dbl/tpl
    // (the rate actually quoted for this tour, not the hotel_rate master table). Returns
    // a list of ['label'=>'SBB x 2', 'amount'=>110.0] for display under Quoted Payment.
    // Same room-size/count resolution AND meal-code label (SGL+BB="SBB") as
    // getRoomTypeSummary(), so the two stay consistent.
    private function getQuotedRoomBreakdown($tourId, $hotelAcc, $checkinDate = null)
    {
        $row = (new TourQuotationsModel())
            ->select('SUM(sglcount) as sglcount, SUM(dblcount) as dblcount, SUM(tplcount) as tplcount, AVG(sgl) as sgl, AVG(dbl) as dbl, AVG(tpl) as tpl')
            ->where('quotation_id', $tourId)
            ->where('hotel_acc', $hotelAcc)
            ->first();

        if (empty($row)) {
            return [];
        }

        $sglCount = (int) ($row['sglcount'] ?? 0);
        $dblCount = (int) ($row['dblcount'] ?? 0);
        $tplCount = (int) ($row['tplcount'] ?? 0);

        if ($sglCount <= 0 && $dblCount <= 0 && $tplCount <= 0) {
            $consignee = (new ConsigneeModel())
                ->select('sgl, dbl, tpl')
                ->where('tour_id', $tourId)
                ->first();
            $sglCount = (int) ($consignee['sgl'] ?? 0);
            $dblCount = (int) ($consignee['dbl'] ?? 0);
            $tplCount = (int) ($consignee['tpl'] ?? 0);
        }

        $counts = ['sgl' => $sglCount, 'dbl' => $dblCount, 'tpl' => $tplCount];

        // Meal plan booked for this hotel — combined with room size for the label
        // (e.g. SGL + BB = "SBB"), same convention as getRoomTypeSummary().
        $mealCode = null;
        if (!empty($checkinDate)) {
            $qRow = (new TourQuotationsModel())
                ->select('meal_code')
                ->where('quotation_id', $tourId)
                ->where('hotel_acc', $hotelAcc)
                ->where('tour_date', $checkinDate)
                ->first();
            $mealCode = strtolower(trim((string) ($qRow['meal_code'] ?? '')));
        }
        $validMealCodes = ['ro', 'bb', 'hb', 'fb'];
        $mealSuffix = in_array($mealCode, $validMealCodes, true) ? strtoupper($mealCode) : '';

        // Only room sizes with an actual booked count are shown — see getRoomTypeSummary().
        $breakdown = [];
        foreach (['sgl' => 'S', 'dbl' => 'D', 'tpl' => 'T'] as $field => $sizeLetter) {
            $count = $counts[$field];
            if ($count <= 0) {
                continue;
            }
            $rate = (float) ($row[$field] ?? 0);

            $label = $mealSuffix !== '' ? "{$sizeLetter}{$mealSuffix}" : strtoupper($field);

            $breakdown[] = [
                'label'  => $label,
                'rate'   => $rate,
                'count'  => $count,
                'amount' => $rate * $count,
            ];
        }

        return $breakdown;
    }

    // Per-room-type hotel_rate system rate for this booking — a cross-check against the
    // Quoted Payment, not used in any total. Returns a list of ['label'=>'SHB',
    // 'room_type_name'=>'Deluxe', 'amount'=>60.0] | ['amount'=>null] (no matching
    // hotel_rate row) — one entry per booked room size, same set as getRoomTypeSummary().
    private function getSystemRateBreakdown($tourId, $hotelAcc, $checkinDate)
    {
        if (empty($checkinDate)) {
            return [];
        }

        $tourQuotationsModel = new TourQuotationsModel();
        $countRow = $tourQuotationsModel
            ->select('SUM(sglcount) as sglcount, SUM(dblcount) as dblcount, SUM(tplcount) as tplcount, AVG(sgl) as sgl, AVG(dbl) as dbl, AVG(tpl) as tpl')
            ->where('quotation_id', $tourId)
            ->where('hotel_acc', $hotelAcc)
            ->first();

        if (empty($countRow)) {
            return [];
        }

        $sglCount = (int) ($countRow['sglcount'] ?? 0);
        $dblCount = (int) ($countRow['dblcount'] ?? 0);
        $tplCount = (int) ($countRow['tplcount'] ?? 0);

        if ($sglCount <= 0 && $dblCount <= 0 && $tplCount <= 0) {
            $consignee = (new ConsigneeModel())
                ->select('sgl, dbl, tpl')
                ->where('tour_id', $tourId)
                ->first();
            $sglCount = (int) ($consignee['sgl'] ?? 0);
            $dblCount = (int) ($consignee['dbl'] ?? 0);
            $tplCount = (int) ($consignee['tpl'] ?? 0);
        }

        $counts = ['sgl' => $sglCount, 'dbl' => $dblCount, 'tpl' => $tplCount];

        $qRow = (new TourQuotationsModel())
            ->select('room_type, meal_code')
            ->where('quotation_id', $tourId)
            ->where('hotel_acc', $hotelAcc)
            ->where('tour_date', $checkinDate)
            ->first();

        $roomTypeId = (int) ($qRow['room_type'] ?? 0);
        $mealCode   = strtolower(trim((string) ($qRow['meal_code'] ?? '')));

        $roomTypeName = null;
        if ($roomTypeId > 0) {
            $roomTypeRow  = (new RoomTypeModel())->select('rtype')->where('id', $roomTypeId)->first();
            $roomTypeName = $roomTypeRow['rtype'] ?? null;
        }

        $mealColumnMap = ['ro' => 'ro', 'bb' => 'bb', 'hb' => 'hb', 'fb' => 'fb'];
        $canLookupRate = $roomTypeId > 0 && isset($mealColumnMap[$mealCode]);

        $rateRow = null;
        if ($canLookupRate) {
            $rateRow = (new HotelRateModel())
                ->where('hotel', $hotelAcc)
                ->where('room_type', $roomTypeId)
                ->where('sdate <=', $checkinDate)
                ->where('edate >=', $checkinDate)
                ->orderBy('id', 'DESC')
                ->first();
        }

        $sizePrefixes = ['sgl' => 's', 'dbl' => 'd', 'tpl' => 't'];
        $mealSuffix   = $canLookupRate ? strtoupper($mealCode) : '';

        // Only room sizes with an actual booked count are shown — see getRoomTypeSummary().
        $breakdown = [];
        foreach (['sgl' => 'S', 'dbl' => 'D', 'tpl' => 'T'] as $field => $sizeLetter) {
            $count = $counts[$field];
            if ($count <= 0) {
                continue;
            }

            $label = $mealSuffix !== '' ? "{$sizeLetter}{$mealSuffix}" : strtoupper($field);

            $amount = null;
            if ($canLookupRate && !empty($rateRow)) {
                $column = $sizePrefixes[$field] . $mealColumnMap[$mealCode];
                $value  = (float) ($rateRow[$column] ?? 0);
                if ($value > 0) {
                    $amount = $value;
                }
            }

            $breakdown[] = ['label' => $label, 'room_type_name' => $roomTypeName, 'amount' => $amount];
        }

        return $breakdown;
    }

    // Returns ['checkin_date' => earliest date (for sorting), 'all_dates' => every
    // dated row's date, 'night_count' => count] so a multi-night hotel stay lists
    // every night instead of collapsing to just the first one.
    private function getCheckinDate($tourId, $hotelAcc, $type)
    {
        if ($type === 'Hotel') {
            $tourQuotationsModel = new TourQuotationsModel();
            $rows = $tourQuotationsModel
                ->select('tour_date')
                ->where('quotation_id', $tourId)
                ->where('hotel_acc', $hotelAcc)
                ->orderBy('tour_date', 'ASC')
                ->findAll();

            $dates = array_values(array_filter(array_column($rows, 'tour_date')));

            if (empty($dates)) {
                return ['checkin_date' => null, 'all_dates' => [], 'night_count' => 0];
            }

            return [
                'checkin_date' => $dates[0],
                'all_dates'    => $dates,
                'night_count'  => count($dates),
            ];
        }

        if ($type === 'lunch') {
            $db = \Config\Database::connect();
            $row = $db->table('tour_activity')
                ->select('tour_date')
                ->where('tour_id', $tourId)
                ->where('supplier_id', $hotelAcc)
                ->orderBy('tour_date', 'ASC')
                ->get()->getRowArray();

            $date = $row['tour_date'] ?? null;
            return ['checkin_date' => $date, 'all_dates' => $date ? [$date] : [], 'night_count' => $date ? 1 : 0];
        }

        return ['checkin_date' => null, 'all_dates' => [], 'night_count' => 0];
    }

    // Create a voucher + v_inv record for an accepted payment request
    private function createPaymentVoucher($request, $lkrAmount, $rate, $bank, $bankName)
    {
        $invoiceNoModel = new InvoiceNumberModel();
        $v_num = $invoiceNoModel->incrementVoucherToken();
        $pay_date = date('Y-m-d H:i:s');

        $isHotel = $request['type'] == 'Hotel';
        $isTransport = $request['type'] == 'Transport Advance';
        $hotel_dates = $isHotel ? $this->getHotelDates($request['tour_id'], $request['hotel_acc']) : null;

        if ($isTransport) {
            $description = 'Transport Advance ' . $request['manual_id'];
        } elseif ($isHotel) {
            $description = 'Hotel Reservation ' . $request['manual_id'] . ' / ' . $request['hotel_name'];
        } else {
            $description = 'Lunch or Other Fee ' . $request['manual_id'] . ' / ' . $request['hotel_name'];
        }

        $voucherData = [
            'Pay' => $isTransport ? $request['consignee'] : $request['hotel_name'],
            'emp_code' => $request['hotel_acc'],
            'S_name' => $isTransport ? $request['consignee'] : $request['hotel_name'],
            'Acc_No' => $request['hotel_acc'],
            'sup' => '1',
            'type' => 'Bank Transfer',
            'Amount' => $lkrAmount,
            'VAT' => '0',
            'VAT_Amt' => '0',
            'vat_m' => '0',
            'nbt' => '0',
            'nbt_amt' => '0',
            'nbt_m' => '0',
            'g_ttl' => $lkrAmount,
            'Address' => '0',
            'print' => '0',
            'des' => '0',
            'dv' => '0',
            'app_user' => session()->get('acc_no'),
            'login' => session()->get('username'),
            'Aflag' => '0',
            'v_typ' => 'Bank Transfer',
            'ref_po' => $request['tour_id'],
            'insflag' => 1,
            'inv' => '0',
            'Vnum' => $v_num,
            'Bank' => $bankName,
            'Bank_acc' => $bank,
            'C_Date' => $pay_date,
            'C_Number' => $request['tour_id'],
            'W_Date' => $pay_date,
            'is_expenses' => 1,
            'advances_pay' => $isTransport ? 1 : 2,
            'V_num' => $hotel_dates,
            'doller_amt' => $isHotel ? $request['requestAmount'] : $lkrAmount
        ];

        $voucherInvoiceData = [
            'Date' => $pay_date,
            'Invoice' => $request['tour_id'],
            'Description' => $description,
            'Units' => 0,
            'Rate' => $isHotel ? $rate : 0,
            'Amount' => $lkrAmount,
            'InvAmount' => $lkrAmount,
            'V_tkn' => $v_num,
            'ityp' => '1',
            'link_code' => $request['hotel_acc'],
            'employee' => $request['hotel_name'],
            'login' => session()->get('username'),
            'INV_Date' => date('Y-m-d'),
            'Payee' => $request['hotel_name'],
            'account' => $request['hotel_acc']
        ];

        $voucherPaymentModel = new VoucherPaymentModel();
        $voucherPaymentModel->insert($voucherData);

        $voucherInvoiceModel = new VoucherInvoiceModel();
        $voucherInvoiceModel->insert($voucherInvoiceData);

        if ($isTransport) {
            $consigneeModel = new ConsigneeModel();
            $consigneeModel->where('tour_id', $request['tour_id'])->set([
                'transport_agent_name' => $request['hotel_name'],
                'transport_agent' => $request['hotel_acc']
            ])->update();
        }

        (new PaymentRequestModel())->update($request['id'], ['voucher_created' => 1]);
    }

    /**
     * Create one voucher (voucher table) + one v_inv row per selected invoice (v_inv table).
     * Called from payment_approval_v2 when the user picks multiple invoices for the same payee
     * and clicks "Create Voucher".
     *
     * POST params:
     *   ids[]       — payment_request IDs to include
     *   bank_acc    — bank L_Code
     *   bank_name   — bank display name
     */
    public function create_bulk_voucher()
    {
        $ids       = $this->request->getPost('ids');
        $bank      = $this->request->getPost('bank_acc');
        $bank_name = $this->request->getPost('bank_name');

        if (empty($ids) || !is_array($ids) || empty($bank)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Invalid request: ids and bank_acc are required']);
        }

        $PaymentRequestModel = new PaymentRequestModel();
        $requests = $PaymentRequestModel->whereIn('id', $ids)->orderBy('id', 'ASC')->findAll();

        if (empty($requests)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'No matching payment requests found']);
        }

        // tf-paydesk-r2 — batch mode (Supplier Payables): the requests were APPROVED already (status 4/6, no voucher); this
        // call only raises their one voucher. The approval user/date are kept, the service-fee accruals were posted at
        // approval and are not posted again, and each USD line is valued at the rate the desk passes (the approval-date
        // rate it recorded) rather than re-read from the quotation.
        $tpdBatch = $this->request->getPost('tpd_batch') == 1 && \App\Libraries\TourPayments\Batches::accumulates();
        $tpdRates = (array) ($this->request->getPost('rates') ?: []);     // pr_id => USD rate
        $tpdLkr   = (array) ($this->request->getPost('amounts') ?: []);   // pr_id => LKR amount of the line
        $tpdUsd   = (array) ($this->request->getPost('usd') ?: []);       // pr_id => USD amount of the line (a USD expense kept in LKR by the chain)

        // Guard against creating a duplicate voucher for a request already vouchered/processed
        if ($tpdBatch) {
            $alreadyDone = array_filter($requests, fn($r) => !in_array((string) $r['payment_request'], ['4', '6'], true) || (int) $r['voucher_created'] === 1);
        } else {
            $alreadyDone = array_filter($requests, fn($r) => $r['payment_request'] != '1' || (int) $r['voucher_created'] === 1);
        }
        if (!empty($alreadyDone)) {
            $ids = implode(', ', array_column($alreadyDone, 'manual_id'));
            return $this->response->setJSON(['status' => 400, 'message' => $tpdBatch ? "Not an approved request without a voucher: {$ids}" : "Voucher already created / request already processed for: {$ids}"]);
        }

        $hotelAcc  = $requests[0]['hotel_acc'];
        $hotelName = $requests[0]['hotel_name'] ?: $requests[0]['consignee'];

        // Validate all Hotel rates BEFORE touching the DB
        $totalAmount = 0;
        $totalDollar = 0;
        $isUsd       = false;
        $ratesCache  = [];

        foreach ($requests as $req) {
            if ($req['type'] === 'Hotel') {
                $isUsd = true;
                $rate  = $tpdBatch && !empty($tpdRates[$req['id']]) ? (float) $tpdRates[$req['id']] : $this->getHotelDollarRate($req['tour_id'], $req['hotel_acc']);
                if (empty($rate)) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'No dollar rate found for ' . $req['manual_id'] . '. Please confirm the hotel payment rate first.']);
                }
                $ratesCache[$req['id']] = $rate;
                $totalAmount += $tpdBatch && isset($tpdLkr[$req['id']]) ? (float) $tpdLkr[$req['id']] : (float)$req['requestAmount'] * (float)$rate;
                $totalDollar += (float)$req['requestAmount'];
            } elseif ($tpdBatch && !empty($tpdUsd[$req['id']])) {
                $isUsd = true;
                $ratesCache[$req['id']] = (float) ($tpdRates[$req['id']] ?? 0);
                $totalAmount += isset($tpdLkr[$req['id']]) ? (float) $tpdLkr[$req['id']] : (float)$req['requestAmount'];
                $totalDollar += (float) $tpdUsd[$req['id']];
            } else {
                $totalAmount += (float)$req['requestAmount'];
            }
        }
        $totalAmount = round($totalAmount, 2);

        // --- 1. Update payment_request status ---
        foreach ($requests as $req) {
            if ($tpdBatch) {
                continue;   // approved already: the approval user and date stay as they are
            }
            $newStatus = ($req['is_bulk'] == 1) ? '6' : '4';
            $PaymentRequestModel->update($req['id'], [
                'payment_request' => $newStatus,
                'accept_user'     => session()->get('username'),
                'accept_date'     => date('Y-m-d H:i:s'),
            ]);
        }

        // --- 2. Lunch: account_running Dr/Cr entries ---
        $supplierModel       = new SupplierModel();
        $invoiceNoModel      = new InvoiceNumberModel();
        $accountRunningModel = new AccountRunningModel();

        foreach ($requests as $req) {
            if ($req['type'] === 'lunch' && !$tpdBatch) {
                $supplier      = $supplierModel->where('Sup_Acc_No', $req['hotel_acc'])->first();
                $supplierLCode = $supplier['Sup_Acc_No'] ?? $req['hotel_acc'];
                $acc_id        = $invoiceNoModel->incrementAccId();
                $description   = 'Lunch or Other Fee ' . $req['manual_id'] . ' / ' . $req['hotel_name'];

                $accountRunningModel->insertBatch([
                    [
                        'ID'           => $acc_id,
                        'L_Code'       => '4-8-109-10653',
                        'Date'         => date('Y-m-d'),
                        'Discription'  => $description,
                        'Dr'           => $req['requestAmount'],
                        'Cr'           => 0.00,
                        'Invoice_Num'  => $req['tour_id'],
                        'Linkacc'      => $supplierLCode,
                        'index_no'     => $req['tour_id'],
                        'job'          => '4-8-109-10653',
                        'advances_pay' => 22,
                        'fine_date'    => date('Y-m-d'),
                        'Dt'           => date('Y-m-d H:i:s'),
                        'Cashier'      => session()->get('username'),
                    ],
                    [
                        'ID'           => $acc_id,
                        'L_Code'       => $supplierLCode,
                        'Date'         => date('Y-m-d'),
                        'Discription'  => $description,
                        'Dr'           => 0.00,
                        'Cr'           => $req['requestAmount'],
                        'Invoice_Num'  => $req['tour_id'],
                        'Linkacc'      => $supplierLCode,
                        'index_no'     => $req['tour_id'],
                        'job'          => '4-8-109-10653',
                        'advances_pay' => 22,
                        'fine_date'    => date('Y-m-d'),
                        'Dt'           => date('Y-m-d H:i:s'),
                        'Cashier'      => session()->get('username'),
                    ],
                ]);
            }
        }

        // tf-paydesk-r2: the Payment approval page's Accept comes through here; with Supplier Payables in place it approves only —
        // the requests now wait under their supplier, and the voucher is issued from the supplier's batch (batch mode above)
        if (!$tpdBatch && \App\Libraries\TourPayments\Batches::accumulates()) {
            foreach ($requests as $req) {
                if ($req['type'] === 'Transport Advance') {
                    $consigneeModel = new ConsigneeModel();
                    $consigneeModel->where('tour_id', $req['tour_id'])->set(['transport_agent_name' => $req['consignee'], 'transport_agent' => $req['hotel_acc']])->update();
                }
            }
            try {
                $tpdDb = \Config\Database::connect();
                foreach ($requests as $req) {
                    \App\Libraries\TourPayments\PayDesk::recordApproval($tpdDb, (int) $req['id'], (string) session()->get('username'));
                    \App\Libraries\TourPayments\Audit::log($tpdDb, ['action' => 'approved', 'tour_id' => $req['tour_id'], 'manual_id' => $req['manual_id'], 'ref_type' => 'request', 'ref_id' => $req['id'], 'prev' => ['status' => 1], 'new' => ['status' => ($req['is_bulk'] == 1) ? 6 : 4, 'amount' => $req['requestAmount']], 'bank_account' => $bank_name ?: $bank, 'reason' => 'Approved on the Payment approval page']);
                }
            } catch (\Throwable $tpdE) {
                log_message('error', 'tf-paydesk-r2 approval record: ' . $tpdE->getMessage());
            }
            return $this->response->setJSON([
                'status'  => 200,
                'message' => count($requests) . ' request(s) approved for ' . $hotelName . ' — now waiting under the supplier in Supplier Payables. Management creates the supplier\'s batch there; that issues the voucher for the accountant.',
                'vnum'    => null,
                'awaiting_batch' => true,
            ]);
        }

        // --- 3. One combined voucher + one v_inv row per request ---
        $v_num    = $invoiceNoModel->incrementVoucherToken();
        $pay_date = date('Y-m-d H:i:s');

        // advances_pay: 1 = driver/transport advance, 2 = everything else (hotel, lunch).
        // All requests in a batch share one payee (grouped by hotel_acc from the UI), so
        // the type of the first request tells us what the whole voucher is for.
        $isTransport = $requests[0]['type'] === 'Transport Advance';

        $voucherPaymentModel = new VoucherPaymentModel();
        $voucherPaymentModel->insert([
            'Pay'         => $hotelName,
            'emp_code'    => $hotelAcc,
            'S_name'      => $hotelName,
            'Acc_No'      => $hotelAcc,
            'sup'         => '1',
            'type'        => 'Bank Transfer',
            'Amount'      => $totalAmount,
            'VAT'         => '0',
            'VAT_Amt'     => '0',
            'vat_m'       => '0',
            'nbt'         => '0',
            'nbt_amt'     => '0',
            'nbt_m'       => '0',
            'g_ttl'       => $totalAmount,
            'Address'     => '0',
            'print'       => '0',
            'des'         => '0',
            'dv'          => '0',
            'app_user'    => session()->get('acc_no'),
            'login'       => session()->get('username'),
            'Aflag'       => '0',
            'v_typ'       => 'Bank Transfer',
            'ref_po'      => $requests[0]['tour_id'],
            'insflag'     => 1,
            'inv'         => '0',
            'Vnum'        => $v_num,
            'Bank'        => $bank_name,
            'Bank_acc'    => $bank,
            'C_Date'      => $pay_date,
            'C_Number'    => $requests[0]['tour_id'],
            'W_Date'      => $pay_date,
            'is_expenses' => 1,
            'advances_pay'=> $isTransport ? 1 : 2,
            'doller_amt'  => $isUsd ? $totalDollar : $totalAmount,
        ]);

        $voucherInvoiceModel = new VoucherInvoiceModel();

        foreach ($requests as $req) {
            if ($req['type'] === 'Hotel') {
                $rate      = $ratesCache[$req['id']];
                $rowAmount = $tpdBatch && isset($tpdLkr[$req['id']]) ? (float) $tpdLkr[$req['id']] : (float)$req['requestAmount'] * (float)$rate;
                $rowRate   = $rate;
                $desc      = 'Hotel Reservation ' . $req['manual_id'] . ' / ' . $req['hotel_name'];
            } elseif ($req['type'] === 'Transport Advance') {
                $rowAmount = (float)$req['requestAmount'];
                $rowRate   = 0;
                $desc      = 'Transport Advance ' . $req['manual_id'];
            } else {
                $rowAmount = $tpdBatch && isset($tpdLkr[$req['id']]) ? (float) $tpdLkr[$req['id']] : (float)$req['requestAmount'];
                $rowRate   = $tpdBatch && !empty($tpdUsd[$req['id']]) ? (float) ($ratesCache[$req['id']] ?? 0) : 0;
                $desc      = 'Lunch or Other Fee ' . $req['manual_id'] . ' / ' . $req['hotel_name'];
            }
            $rowAmount = round($rowAmount, 2);

            $voucherInvoiceModel->insert([
                'Date'        => $pay_date,
                'Invoice'     => $req['tour_id'],
                'Description' => $desc,
                'Units'       => 0,
                'Rate'        => $rowRate,
                'Amount'      => $rowAmount,
                'InvAmount'   => $rowAmount,
                'V_tkn'       => $v_num,
                'ityp'        => '1',
                'link_code'   => $req['hotel_acc'],
                'employee'    => $hotelName,
                'login'       => session()->get('username'),
                'INV_Date'    => date('Y-m-d'),
                'Payee'       => $hotelName,
                'account'     => $req['hotel_acc'],
            ]);

            $PaymentRequestModel->update($req['id'], ['voucher_created' => 1]);
        }

        // --- 4. Transport Advance: update consignee ---
        foreach ($requests as $req) {
            if ($req['type'] === 'Transport Advance') {
                $consigneeModel = new ConsigneeModel();
                $consigneeModel->where('tour_id', $req['tour_id'])->set([
                    'transport_agent_name' => $req['consignee'],
                    'transport_agent'      => $req['hotel_acc'],
                ])->update();
            }
        }

        return $this->response->setJSON([
            'status'  => 200,
            'message' => 'Voucher ' . $v_num . ' created with ' . count($requests) . ' invoice(s)',
            'vnum'    => $v_num,
        ]);
    }

    // Bulk Accept or Reject payment requests
    public function bulk_update_payment_request_status()
    {
        $ids    = $this->request->getPost('ids');
        $status = $this->request->getPost('status'); // 4 = accept, 5 = reject
        $bank      = $this->request->getPost('bank_acc');
        $bank_name = $this->request->getPost('bank_name');

        if (empty($ids) || !is_array($ids) || !in_array($status, ['4', '5'])) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Invalid request']);
        }

        $PaymentRequestModel = new PaymentRequestModel();

        $requests = $PaymentRequestModel->whereIn('id', $ids)->findAll();

        // Guard against re-processing requests already actioned/vouchered (e.g. double submit, stale page)
        $requests = array_filter($requests, fn($r) => $r['payment_request'] == '1' && (int) $r['voucher_created'] === 0);

        if (empty($requests)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'These payment request(s) have already been processed']);
        }

        $voucherTypes = ['lunch', 'Hotel', 'Transport Advance'];
        $needsVoucher = array_filter($requests, fn($r) => in_array($r['type'], $voucherTypes));

        // tf-paydesk-r2: with Supplier Payables in place an approval raises no voucher (see update_payment_request_status)
        $tpdAccumulate = \App\Libraries\TourPayments\Batches::accumulates();

        if ($status == '4' && !empty($needsVoucher) && empty($bank) && !$tpdAccumulate) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Bank account is required to accept these payment requests']);
        }

        // Update each record individually based on is_bulk value
        foreach ($requests as $req) {
            $newStatus = ($req['is_bulk'] == 1) ? '6' : $status;
            $PaymentRequestModel->update($req['id'], [
                'payment_request' => $newStatus,
                'accept_user'     => session()->get('username'),
                'accept_date'     => date('Y-m-d H:i:s'),
            ]);
        }


        if ($status == '4' || $status == '6') {

            $supplierModel       = new SupplierModel();
            $invoiceNoModel      = new InvoiceNumberModel();
            $accountRunningModel = new AccountRunningModel();

            foreach ($requests as $request) {

                if ($request['type'] == 'lunch') {
                    $supplier      = $supplierModel->where('Sup_Acc_No', $request['hotel_acc'])->first();
                    $supplierLCode = $supplier['Sup_Acc_No'] ?? $request['hotel_acc'];
                    $acc_id        = $invoiceNoModel->incrementAccId();
                    $description   = 'Lunch or Other Fee ' . $request['manual_id'] . ' / ' . $request['hotel_name'];

                    $accountRunningModel->insertBatch([
                        [ // Dr — expense account
                            'ID'           => $acc_id,
                            'L_Code'       => '4-8-109-10653',
                            'Date'         => date('Y-m-d'),
                            'Discription'  => $description,
                            'Dr'           => $request['requestAmount'],
                            'Cr'           => 0.00,
                            'Invoice_Num'  => $request['tour_id'],
                            'Linkacc'      => $supplierLCode,
                            'index_no'     => $request['tour_id'],
                            'job'          => '4-8-109-10653',
                            'advances_pay' => 22,
                            'fine_date'    => date('Y-m-d'),
                            'Dt'           => date('Y-m-d H:i:s'),
                            'Cashier'      => session()->get('username'),
                        ],
                        [ // Cr — supplier creditor account
                            'ID'           => $acc_id,
                            'L_Code'       => $supplierLCode,
                            'Date'         => date('Y-m-d'),
                            'Discription'  => $description,
                            'Dr'           => 0.00,
                            'Cr'           => $request['requestAmount'],
                            'Invoice_Num'  => $request['tour_id'],
                            'Linkacc'      => $supplierLCode,
                            'index_no'     => $request['tour_id'],
                            'job'          => '4-8-109-10653',
                            'advances_pay' => 22,
                            'fine_date'    => date('Y-m-d'),
                            'Dt'           => date('Y-m-d H:i:s'),
                            'Cashier'      => session()->get('username'),
                        ],
                    ]);

                    if ($request['is_bulk'] != 1 && !$tpdAccumulate) {
                        $this->createPaymentVoucher($request, $request['requestAmount'], 0, $bank, $bank_name);
                    }
                } elseif ($request['type'] == 'Hotel') {
                    $doller_rate = $this->getHotelDollarRate($request['tour_id'], $request['hotel_acc']);
                    if (!empty($doller_rate) && !$tpdAccumulate) {
                        $lkrAmount = $request['requestAmount'] * $doller_rate;
                        $this->createPaymentVoucher($request, $lkrAmount, $doller_rate, $bank, $bank_name);
                    }
                } elseif ($request['type'] == 'Transport Advance') {
                    if (!$tpdAccumulate) {
                        $this->createPaymentVoucher($request, $request['requestAmount'], 0, $bank, $bank_name);
                    } else {
                        $consigneeModel = new ConsigneeModel();
                        $consigneeModel->where('tour_id', $request['tour_id'])->set(['transport_agent_name' => $request['consignee'], 'transport_agent' => $request['hotel_acc']])->update();
                    }
                }
            }
            // tf-paydesk-r1/r2: the desk keeps who approved and when, and the approval figures (Payment approval page, bulk accept)
            try {
                $tpdDb = \Config\Database::connect();
                foreach ($requests as $request) {
                    \App\Libraries\TourPayments\PayDesk::recordApproval($tpdDb, (int) $request['id'], (string) session()->get('username'));
                    \App\Libraries\TourPayments\Audit::log($tpdDb, ['action' => 'approved', 'tour_id' => $request['tour_id'], 'manual_id' => $request['manual_id'], 'ref_type' => 'request', 'ref_id' => $request['id'], 'prev' => ['status' => 1], 'new' => ['status' => ($request['is_bulk'] == 1) ? 6 : 4, 'amount' => $request['requestAmount']], 'reason' => 'Approved on the Payment approval page (bulk accept)']);
                }
            } catch (\Throwable $tpdE) {
                log_message('error', 'tf-paydesk-r2 bulk approval record: ' . $tpdE->getMessage());
            }
        }

        if ($status == '5') {
            $tourQuotationsModel = new TourQuotationsModel();
            $tourActivityModel = new \App\Models\TourActivityModel();
            $driverAdvanceRequestModel = new DriverAdvanceRequestModel();

            foreach ($requests as $request) {
                if ($request['type'] == 'Hotel') {
                    $tourQuotationsModel->where('quotation_id', $request['tour_id'])
                        ->where('hotel_acc', $request['hotel_acc'])
                        ->set([
                            'payment_request' => 0,
                            'payment_request_user' => '',
                            'hotel_advance' => 0.00,
                            'booking_confirmed_flag' => 0,
                        ])
                        ->update();

                        // Remove the Dr/Cr account_running double entry for this hotel reservation, if any
                        $accountRunningModel = new AccountRunningModel();
                        $accountRunningModel->where('index_no', $request['tour_id'])
                            ->whereIn('L_Code', ['4-8-106-10386', $request['hotel_acc']])
                            ->delete();
                } elseif ($request['type'] == 'lunch') {
                    $tourActivityModel->where('tour_id', $request['tour_id'])
                        ->where('supplier_id', $request['hotel_acc'])
                        ->set([
                            'payment_request' => 0,
                            'payment_request_user' => null,
                            'payment_request_date' => null,
                        ])
                        ->update();
                } elseif ($request['type'] == 'Transport Advance') {
                    $driverAdvanceRequestModel->where('tour_id', $request['tour_id'])
                        ->where('driver', $request['hotel_acc'])
                        ->set(['flag' => 0])
                        ->update();
                }
            }
        }

        $statusText = $status == '4' ? 'accepted' : 'rejected';
        return $this->response->setJSON(['status' => 200, 'message' => count($requests) . " request(s) {$statusText} successfully"]);
    }
}
