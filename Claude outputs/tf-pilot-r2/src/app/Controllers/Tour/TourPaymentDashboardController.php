<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\HotelRates\HotelCostService;
use App\Models\ConsigneeModel;
use App\Models\TourQuotationsModel;
use App\Models\TourActivityModel;
use App\Models\VoucherPaymentModel;
use App\Models\VoucherInvoiceModel;
use App\Models\InvoiceNumberModel;
use App\Models\SupplierModel;
use App\Models\AccountPostingModel;
use App\Models\DriverExpensesModel;
use App\Models\DriverAllocationModel;
use App\Models\LogSheetMileModel;
use App\Models\PaymentRequestModel;
use App\Models\DriverAdvanceRequestModel;
use App\Models\AccountRunningModel;
use App\Models\UsageNoteModel;

class TourPaymentDashboardController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route: base_url/tour/tour_payment_dashboard
    public function index()
    {
            $data = array();
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();
        // tf-offers-r1: who sees and decides tactical / early bird rates at payment (one menu URL = one privilege)
        $data['can_choose_offer'] = \App\Libraries\HotelRates\OfferRates::canDecide($data);
        // tf-paydesk-r1: the workflow's privileges and whether its tables are in place
        $data['tpd_perms'] = \App\Libraries\TourPayments\Perms::all();
        $data['tpd_ready'] = \App\Libraries\TourPayments\Schema::installed(\Config\Database::connect());

        return view('pages/tour/tour_payment_dashboard', $data);
    }

    // tf-paydesk-r1: the dashboard's data for the payment desk (the same figures search_tour returns)
    public function dashboardData(string $manualId): ?array
    {
        return $this->_get_tour_dashboard_data($manualId);
    }

    // AJAX: get bank accounts for payment form
    public function get_bank_accounts()
    {
        $accountPostingModel = new AccountPostingModel();
        $banks = $accountPostingModel
            ->select('PName, L_Code')
            ->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)
            ->notLike('PName', 'USD')->notLike('PName', 'Control')->notLike('PName', 'Yen')
            ->findAll();

        return $this->response->setJSON(['status' => 200, 'data' => $banks]);
    }

    // AJAX: today's applied USD rate, for the Payment Request modal (Hotel $ -> LKR)
    public function get_usd_rate()
    {
        helper('daily_usd_rate');
        $rate = get_usd_rate();

        return $this->response->setJSON(['status' => 200, 'rate' => $rate]);
    }

    // AJAX: search tour by manual_id
    public function search_tour()
    {
        $manual_id = trim($this->request->getGet('manual_id') ?? $this->request->getPost('manual_id') ?? '');

        if (empty($manual_id)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Manual ID is required']);
        }

        $result = $this->_get_tour_dashboard_data($manual_id);

        if (!$result) {
            return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found for Manual ID: ' . $manual_id]);
        }

        return $this->response->setJSON(['status' => 200, 'data' => \App\Libraries\TourPayments\Mask::bank($result)]);   // tf-pilot-r2: bank account numbers masked for a sign-in that may not see them
    }

    // AJAX: list tours with start_date in [from, to], each carrying a collapsed
    // 3-bucket (green/yellow/red) status per payment category (Hotels/Activities/
    // Driver Advance/Driver Settlement). Batches every ingredient query across ALL
    // matching tours at once (WHERE tour_id IN (...)) instead of looping
    // _get_tour_dashboard_data() per tour, which would multiply its ~15 queries by
    // the tour count — this stays flat regardless of how many tours are in range.
    public function list_upcoming()
    {
        $from = trim($this->request->getGet('from') ?? '');
        $to   = trim($this->request->getGet('to') ?? '');

        if (empty($from)) $from = date('Y-m-d', strtotime('+1 day'));
        if (empty($to))   $to   = date('Y-m-d', strtotime('+14 days'));
        if ($to < $from) { [$from, $to] = [$to, $from]; }

        $result = $this->_get_upcoming_tours_data($from, $to);

        return $this->response->setJSON(['status' => 200, 'data' => $result, 'from' => $from, 'to' => $to]);
    }


    private function _get_upcoming_tours_data(string $from, string $to): array
    {
        $db = \Config\Database::connect();

        // ── 1. Base tour list ───────────────────────────────────────────────
        $tours = (new ConsigneeModel())
            ->select('
                consignee.tour_id,
                MAX(consignee.manual_id)             AS manual_id,
                MAX(consignee.consignee)             AS consignee,
                MIN(consignee.start_date)            AS start_date,
                MAX(consignee.end_date)               AS end_date,
                MAX(consignee.passenger_count)        AS passenger_count,
                MAX(consignee.arrival_flight)         AS arrival_flight,
                MAX(consignee.arrival_flight_time)    AS arrival_flight_time,
                MAX(consignee.departure_flight)       AS departure_flight,
                MAX(consignee.departure_flight_time)  AS departure_flight_time
            ')
            ->where('consignee.start_date >=', $from)
            ->where('consignee.start_date <=', $to)
            ->where('consignee.verify', 1)
            ->where('consignee.is_template', 0)
            ->where('consignee.tour_cancel <>', 1)
            ->groupBy('consignee.tour_id')
            ->orderBy('consignee.start_date', 'ASC')
            ->findAll();

        if (empty($tours)) {
            return [];
        }

        $tour_ids     = array_column($tours, 'tour_id');
        $placeholders = implode(',', array_fill(0, count($tour_ids), '?'));

        // ── 2. Hotels — batched from single-tour lines 290-313. Only the columns
        // the status decision actually reads; supplier bank details and per-row
        // display fields (room_type, tour_date) are dropped as dead weight here.
        $hotelRows = (new TourQuotationsModel())
            ->select('
                quotation_id as tour_id, hotel_acc,
                SUM(hotel_advance) as hotel_advance,
                SUM(total_hotel_doller) as total_hotel_doller,
                SUM(pay_doller_count) as pay_doller_count,
                MAX(hotel_dollar_rate) as hotel_dollar_rate,
                MAX(payment_complete) as payment_complete
            ')
            ->whereIn('quotation_id', $tour_ids)
            ->where('hotel_acc !=', '')
            ->where('confirm !=', 3)
            ->groupBy('quotation_id, hotel_acc')
            ->findAll();
        $hotelsByTour = [];
        foreach ($hotelRows as $row) {
            $hotelsByTour[$row['tour_id']][] = $row;
        }

        // ── 3. Hotel payment_request rows — batched from lines 324-334 ─────
        $hotelPRRows = (new PaymentRequestModel())
            ->select('
                tour_id, hotel_acc,
                MAX(voucher_created) as voucher_created,
                MAX(CASE WHEN payment_request IN (4,6) THEN 1 ELSE 0 END) as was_accepted,
                MAX(payment_request) as max_pr_status
            ')
            ->whereIn('tour_id', $tour_ids)
            ->where('type', 'Hotel')
            ->groupBy('tour_id, hotel_acc')
            ->findAll();
        $hotelVoucherCreated = [];
        $hotelHasPR          = [];
        $hotelPRStatus       = [];
        foreach ($hotelPRRows as $row) {
            $tid = $row['tour_id'];
            $acc = $row['hotel_acc'];
            $hotelVoucherCreated[$tid][$acc] = (bool) $row['voucher_created'] || (bool) $row['was_accepted'];
            $hotelHasPR[$tid][$acc]          = true;
            $hotelPRStatus[$tid][$acc]       = (int) $row['max_pr_status'];
        }

        // ── 4. Activity (lunch) payment_request rows — batched from lines 343-353
        $activityPRRows = (new PaymentRequestModel())
            ->select('
                tour_id, hotel_acc,
                MAX(voucher_created) as voucher_created,
                MAX(CASE WHEN payment_request IN (4,6) THEN 1 ELSE 0 END) as was_accepted,
                MAX(payment_request) as max_pr_status
            ')
            ->whereIn('tour_id', $tour_ids)
            ->where('type', 'lunch')
            ->groupBy('tour_id, hotel_acc')
            ->findAll();
        $activityVoucherCreated = [];
        $activityHasPR          = [];
        $activityPRStatus       = [];
        foreach ($activityPRRows as $row) {
            $tid = $row['tour_id'];
            $acc = $row['hotel_acc'];
            $activityVoucherCreated[$tid][$acc] = (bool) $row['voucher_created'] || (bool) $row['was_accepted'];
            $activityHasPR[$tid][$acc]          = true;
            $activityPRStatus[$tid][$acc]       = (int) $row['max_pr_status'];
        }

        // ── 5. Voucher finalized/pending/paid — batched from lines 366-405. Joined
        // through v_inv.Invoice, not voucher.C_Number — a bulk voucher spanning
        // multiple tours can only carry one C_Number, so v_inv.Invoice (set
        // correctly per line at voucher-creation time) is the reliable tour key.
        $voucherStatusRows = $db->query(
            "SELECT vi.Invoice AS tour_id, v.Acc_No, v.Aflag, v.finalized
             FROM v_inv vi
             JOIN voucher v ON v.Vnum = vi.V_tkn
             WHERE vi.Invoice IN ($placeholders)",
            $tour_ids
        )->getResultArray();
        $voucherFinalizedByTourAcc = [];
        $voucherPendingByTourAcc   = [];
        $voucherPaidByTourAcc      = [];
        foreach ($voucherStatusRows as $row) {
            $tid = $row['tour_id'];
            $acc = $row['Acc_No'];
            if (!empty($row['finalized']))    $voucherFinalizedByTourAcc[$tid][$acc] = true;
            if ((int) $row['Aflag'] === 0)    $voucherPendingByTourAcc[$tid][$acc]   = true;
            if ((int) $row['Aflag'] === 1)    $voucherPaidByTourAcc[$tid][$acc]      = true;
        }

        // ── 6. tour_activity flat rows — batched from lines 443-456. Only
        // supplier_id + lkr_fee are read by the status decision below.
        $activityRows = (new TourActivityModel())
            ->select('tour_id, supplier_id, lkr_fee')
            ->whereIn('tour_id', $tour_ids)
            ->where('supplier_id IS NOT NULL', null, false)
            ->where('supplier_id !=', '')
            ->findAll();
        $activitiesByTour = [];
        foreach ($activityRows as $row) {
            $activitiesByTour[$row['tour_id']][] = $row;
        }

        // ── 7. Transport Advance payment_request, both variants — lines 412-440
        $transportPendingRows = (new PaymentRequestModel())
            ->select('tour_id, hotel_acc, SUM(requestAmount) as requested_amount, MAX(payment_request) as max_pr_status')
            ->whereIn('tour_id', $tour_ids)
            ->where('type', 'Transport Advance')
            ->where('voucher_created', 0)
            ->groupBy('tour_id, hotel_acc')
            ->findAll();
        $pendingTransportAdvanceByTourDriver = [];
        $transportPRStatus                   = [];
        foreach ($transportPendingRows as $row) {
            $tid = $row['tour_id'];
            $acc = $row['hotel_acc'];
            $pendingTransportAdvanceByTourDriver[$tid][$acc] = floatval($row['requested_amount']);
            $transportPRStatus[$tid][$acc]                   = (int) $row['max_pr_status'];
        }

        $transportAllPRRows = (new PaymentRequestModel())
            ->select('tour_id, hotel_acc, MAX(payment_request) as max_pr_status')
            ->whereIn('tour_id', $tour_ids)
            ->where('type', 'Transport Advance')
            ->groupBy('tour_id, hotel_acc')
            ->findAll();
        $transportHasPR       = [];
        $transportAnyPRStatus = [];
        foreach ($transportAllPRRows as $row) {
            $tid = $row['tour_id'];
            $acc = $row['hotel_acc'];
            $transportHasPR[$tid][$acc]       = true;
            $transportAnyPRStatus[$tid][$acc] = (int) $row['max_pr_status'];
        }

        // ── 8. Driver allocation — batched from lines 459-461 ──────────────
        $driverAllocRows = (new DriverAllocationModel())
            ->select('tour_id, driver')
            ->whereIn('tour_id', $tour_ids)
            ->findAll();
        $driverAllocByTour = [];
        foreach ($driverAllocRows as $row) {
            $driverAllocByTour[$row['tour_id']][] = $row;
        }

        // ── 9. Latest log_sheet_mile per (tour, driver) — batched from lines
        // 465-475; inner GROUP BY gains tour_id to stay correct across many tours.
        $logSheetRows = $db->query(
            "SELECT ls.*
             FROM log_sheet_mile ls
             INNER JOIN (
                 SELECT tour_id, transport_agent, MAX(id) as max_id
                 FROM log_sheet_mile
                 WHERE tour_id IN ($placeholders)
                 GROUP BY tour_id, transport_agent
             ) latest ON ls.id = latest.max_id",
            $tour_ids
        )->getResultArray();
        $logSheetByTourDriver = [];
        foreach ($logSheetRows as $ls) {
            $logSheetByTourDriver[$ls['tour_id']][$ls['transport_agent']] = $ls;
        }

        // ── 10. Transport payment components — batched from lines 467-556. Note
        // the tour-linking column differs by table, same as the single-tour
        // method: voucher.ref_po here, account_runing.index_no below.
        $advanceRows = $db->query(
            "SELECT ref_po AS tour_id, Acc_No, SUM(Amount) as total
             FROM voucher WHERE ref_po IN ($placeholders) AND Aflag = 1 AND advances_pay = 1
             GROUP BY ref_po, Acc_No",
            $tour_ids
        )->getResultArray();
        $advanceByTourAcc = [];
        foreach ($advanceRows as $row) {
            $advanceByTourAcc[$row['tour_id']][$row['Acc_No']] = floatval($row['total']);
        }

        $unfinalizedAdvanceRows = $db->query(
            "SELECT ref_po AS tour_id, Acc_No, SUM(Amount) as total
             FROM voucher WHERE ref_po IN ($placeholders) AND advances_pay = 1 AND Aflag != 1
             GROUP BY ref_po, Acc_No",
            $tour_ids
        )->getResultArray();
        $unfinalizedAdvByTourAcc = [];
        foreach ($unfinalizedAdvanceRows as $row) {
            $unfinalizedAdvByTourAcc[$row['tour_id']][$row['Acc_No']] = floatval($row['total']);
        }

        $adCrRows = $db->query(
            "SELECT index_no AS tour_id, L_Code, SUM(Cr) as total
             FROM account_runing WHERE index_no IN ($placeholders) AND advances_pay = 1111
             GROUP BY index_no, L_Code",
            $tour_ids
        )->getResultArray();
        $adCrByTourAcc = [];
        foreach ($adCrRows as $row) {
            $adCrByTourAcc[$row['tour_id']][$row['L_Code']] = floatval($row['total']);
        }

        $expCrRows = $db->query(
            "SELECT index_no AS tour_id, L_Code, SUM(Cr) as total
             FROM account_runing
             WHERE index_no IN ($placeholders) AND Cr > 0 AND advances_pay = 111
               AND Discription NOT LIKE 'VOUCHER PAYMENT%'
             GROUP BY index_no, L_Code",
            $tour_ids
        )->getResultArray();
        $expCrByTourAcc = [];
        foreach ($expCrRows as $row) {
            $expCrByTourAcc[$row['tour_id']][$row['L_Code']] = floatval($row['total']);
        }

        $totalPayCrRows = $db->query(
            "SELECT index_no AS tour_id, L_Code, SUM(Cr) as total
             FROM account_runing
             WHERE index_no IN ($placeholders) AND Cr > 0 AND advances_pay = 11
               AND (Discription LIKE 'VOUCHER PAYMENT%' OR Discription LIKE 'JOURNAL ENTRY%')
             GROUP BY index_no, L_Code",
            $tour_ids
        )->getResultArray();
        $totalPayCrByTourAcc = [];
        foreach ($totalPayCrRows as $row) {
            $totalPayCrByTourAcc[$row['tour_id']][$row['L_Code']] = floatval($row['total']);
        }

        // ── Status derivation — same branches as _get_tour_dashboard_data()'s
        // Hotels (767-838), Activities (867-921), and Driver Advance/Settlement
        // (625-692) logic, reading from the batched arrays above instead of a
        // live per-tour query. Returns the raw status word per row (not a
        // collapsed bucket) — the frontend renders one square per row, colored
        // via the same statusClass() mapping already used by the detail view's
        // Status column, so a tour with 4 hotels shows 4 individually-colored
        // squares under Hotels.
        $out = [];
        foreach ($tours as $t) {
            $tid             = $t['tour_id'];
            $passenger_count = floatval($t['passenger_count'] ?? 0);

            // Hotels
            $hotelStatuses = [];
            foreach ($hotelsByTour[$tid] ?? [] as $h) {
                $acc  = $h['hotel_acc'];
                $rate = $h['hotel_dollar_rate'] ?? 125;
                $hasVoucher = $hotelVoucherCreated[$tid][$acc] ?? false;

                if (!isset($hotelHasPR[$tid][$acc])) {
                    $status = 'Payment Not Requested';
                } elseif (($hotelPRStatus[$tid][$acc] ?? -1) === 2) {
                    $status = 'Fully Paid';
                } elseif (($hotelPRStatus[$tid][$acc] ?? -1) === 0) {
                    $status = 'Waiting Rate Confirmation';
                } elseif (($hotelPRStatus[$tid][$acc] ?? -1) === 1) {
                    $status = ($voucherPaidByTourAcc[$tid][$acc] ?? false) ? 'Paid' : 'Not Paid';
                } elseif (($hotelPRStatus[$tid][$acc] ?? -1) === 4 && ($voucherPendingByTourAcc[$tid][$acc] ?? false)) {
                    $status = 'Payment on Process';
                } elseif (!$hasVoucher) {
                    $status = 'Pending Approval';
                } else {
                    $isFinalized = $voucherFinalizedByTourAcc[$tid][$acc] ?? false;
                    if (!$isFinalized) {
                        $status = 'Pending Payment';
                    } else {
                        $total      = floatval(($h['total_hotel_doller'] ?? 0) * $rate);
                        $advanceLkr = floatval($h['hotel_advance'] ?? 0);
                        $paid       = $advanceLkr > 0 ? $advanceLkr : floatval(($h['pay_doller_count'] ?? 0) * $rate);
                        if ($h['payment_complete'] || ($total > 0 && $paid >= $total)) {
                            $status = 'Fully Paid';
                        } elseif ($paid > 0) {
                            $status = 'Partially Paid';
                        } else {
                            $status = 'Pending Payment';
                        }
                    }
                }
                $hotelStatuses[] = $status;
            }

            // Activities
            $activityStatuses = [];
            foreach ($activitiesByTour[$tid] ?? [] as $a) {
                $acc        = $a['supplier_id'] ?? '';
                $hasVoucher = $activityVoucherCreated[$tid][$acc] ?? false;

                if (!isset($activityHasPR[$tid][$acc])) {
                    $status = 'Payment Not Requested';
                } elseif (($activityPRStatus[$tid][$acc] ?? -1) === 2) {
                    $status = 'Fully Paid';
                } elseif (($activityPRStatus[$tid][$acc] ?? -1) === 1) {
                    $status = 'Not Paid';
                } elseif (($activityPRStatus[$tid][$acc] ?? -1) === 4 && ($voucherPendingByTourAcc[$tid][$acc] ?? false)) {
                    $status = 'Payment on Process';
                } elseif (!$hasVoucher) {
                    $status = 'Pending Approval';
                } else {
                    $isFinalized = $voucherFinalizedByTourAcc[$tid][$acc] ?? false;
                    $total       = floatval($a['lkr_fee'] ?? 0) * $passenger_count;
                    $status      = $isFinalized && $total > 0 ? 'Fully Paid' : 'Pending Payment';
                }
                $activityStatuses[] = $status;
            }

            // Driver Advance — same classification the single-tour method uses
            // to distinguish an advance-request row from a log-sheet-driven
            // settlement row; settlement rows are skipped since that category
            // isn't shown on this landing list.
            $advanceStatuses = [];
            foreach ($driverAllocByTour[$tid] ?? [] as $alloc) {
                $agent_code     = $alloc['driver'];
                $ls             = $logSheetByTourDriver[$tid][$agent_code] ?? null;
                $logSheetAmount = 0;
                if ($ls && floatval($ls['log_sheet_mileage'] ?? 0) > 0) {
                    $logSheetAmount = ($ls['log_sheet_mileage'] * $ls['vehicle_rate'])
                        + ($ls['per_day_rate'] * $ls['no_of_dates']);
                }

                $dr           = $advanceByTourAcc[$tid][$agent_code]        ?? 0;
                $ad_cr        = $adCrByTourAcc[$tid][$agent_code]           ?? 0;
                $cr           = $expCrByTourAcc[$tid][$agent_code]          ?? 0;
                $total_pay_cr = $totalPayCrByTourAcc[$tid][$agent_code]     ?? 0;

                $advanceNet    = $dr - $ad_cr;
                $payableAmount = ($logSheetAmount - $advanceNet) - $cr - $total_pay_cr;
                $paid          = $logSheetAmount - $payableAmount;

                $pendingRequestAmount     = $pendingTransportAdvanceByTourDriver[$tid][$agent_code] ?? 0;
                $unfinalizedAdvanceAmount = $unfinalizedAdvByTourAcc[$tid][$agent_code] ?? 0;
                $paymentCategory          = $logSheetAmount > 0 ? 'Transport' : 'Driver Advance';

                if ($logSheetAmount <= 0 && $advanceNet <= 0 && !isset($transportHasPR[$tid][$agent_code])) {
                    $status = 'Payment Not Requested';
                } elseif ($logSheetAmount <= 0 && in_array(($transportAnyPRStatus[$tid][$agent_code] ?? -1), [2, 4], true)) {
                    $status = 'Advance Paid';
                } elseif ($logSheetAmount <= 0 && $advanceNet <= 0 && $pendingRequestAmount > 0 && ($transportPRStatus[$tid][$agent_code] ?? -1) === 1) {
                    $status = 'Not Paid';
                } elseif ($logSheetAmount <= 0 && $advanceNet <= 0 && $pendingRequestAmount > 0) {
                    $status = 'Requested';
                } elseif ($logSheetAmount <= 0 && $advanceNet <= 0 && $unfinalizedAdvanceAmount > 0 && ($transportAnyPRStatus[$tid][$agent_code] ?? -1) === 4 && ($voucherPendingByTourAcc[$tid][$agent_code] ?? false)) {
                    $status = 'Payment on Process';
                } elseif ($logSheetAmount <= 0 && $advanceNet <= 0 && $unfinalizedAdvanceAmount > 0) {
                    $status = 'Pending Payment';
                } elseif ($logSheetAmount <= 0 && $advanceNet > 0) {
                    $status = 'Advance Paid';
                } elseif ($logSheetAmount > 0 && $payableAmount <= 0) {
                    $status = 'Fully Paid';
                } elseif ($paid > 0) {
                    $status = 'Partially Paid';
                } else {
                    $status = 'Not Paid';
                }

                if ($paymentCategory !== 'Transport') {
                    $advanceStatuses[] = $status;
                }
            }

            $out[] = [
                'tour_id'         => $tid,
                'manual_id'       => $t['manual_id'],
                'consignee'       => $t['consignee'],
                'start_date'      => $t['start_date'],
                'end_date'        => $t['end_date'],
                'passenger_count' => $t['passenger_count'],
                'arrival_flight'          => $t['arrival_flight'] ?? '',
                'arrival_flight_time'     => $t['arrival_flight_time'] ?? '',
                'departure_flight'        => $t['departure_flight'] ?? '',
                'departure_flight_time'   => $t['departure_flight_time'] ?? '',
                'categories'      => [
                    'hotels'         => $hotelStatuses,
                    'activities'     => $activityStatuses,
                    'driver_advance' => $advanceStatuses,
                ],
            ];
        }

        return $out;
    }

    // POST: save a payment entry
    public function save_payment()
    {
        try {
            $tourId       = $this->request->getPost('tour_id');
            $manualId     = $this->request->getPost('manual_id');
            $supplierAcc  = $this->request->getPost('supplier_acc');
            $supplierName = $this->request->getPost('supplier_name');
            $amount       = $this->request->getPost('amount');
            $paymentDate  = $this->request->getPost('payment_date') ?: date('Y-m-d');
            $referenceNo  = $this->request->getPost('reference_no');
            $remarks      = $this->request->getPost('remarks');
            $category     = $this->request->getPost('category');
            $bankAcc      = $this->request->getPost('bank_acc');
            $bankName     = $this->request->getPost('bank_name');
            $isFullPay    = $this->request->getPost('is_full_pay');

            if (empty($tourId) || empty($amount) || empty($supplierAcc)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour ID, supplier and amount are required']);
            }

            $invoiceNoModel = new InvoiceNumberModel();
            $vNum = $invoiceNoModel->incrementVoucherToken();

            // advances_pay: 2 = hotel advance, 1 = transport advance
            $advancesPayType = ($category === 'Transport' || $category === 'Driver Advance') ? 1 : 2;

            $voucherData = [
                'Pay'         => $supplierName,
                'emp_code'    => $supplierAcc,
                'S_name'      => $supplierName,
                'Acc_No'      => $supplierAcc,
                'sup'         => '1',
                'type'        => 'Bank Transfer',
                'Amount'      => $amount,
                'VAT'         => '0',
                'VAT_Amt'     => '0',
                'vat_m'       => '0',
                'nbt'         => '0',
                'nbt_amt'     => '0',
                'nbt_m'       => '0',
                'g_ttl'       => $amount,
                'Address'     => '0',
                'print'       => '0',
                'des'         => $remarks ?: '0',
                'W_Date'      => $paymentDate,
                'dv'          => '0',
                'app_user'    => session()->get('acc_no'),
                'login'       => session()->get('username'),
                'Aflag'       => '0',
                'v_typ'       => 'Bank Transfer',
                'ref_po'      => $tourId,
                'insflag'     => 1,
                'inv'         => $referenceNo ?: '0',
                'Vnum'        => $vNum,
                'Bank'        => $bankName,
                'Bank_acc'    => $bankAcc,
                'C_Date'      => date('Y-m-d H:i:s'),
                'C_Number'    => $tourId,
                'is_expenses' => 1,
                'advances_pay' => $advancesPayType,
                'doller_amt'  => $amount,
            ];

            $voucherPaymentModel = new VoucherPaymentModel();
            $voucherPaymentModel->insert($voucherData);

            $voucherInvData = [
                'Date'        => $paymentDate,
                'Invoice'     => $tourId,
                'Description' => ($category ?? 'Payment') . ' Payment - ' . $manualId . ($referenceNo ? ' (' . $referenceNo . ')' : ''),
                'Units'       => 0,
                'Rate'        => 0,
                'Amount'      => $amount,
                'InvAmount'   => $amount,
                'V_tkn'       => $vNum,
                'ityp'        => '1',
                'link_code'   => $supplierAcc,
                'employee'    => $supplierName,
                'login'       => session()->get('username'),
                'INV_Date'    => date('Y-m-d'),
                'Payee'       => $supplierName,
                'account'     => $supplierAcc,
            ];

            $voucherInvoiceModel = new VoucherInvoiceModel();
            $voucherInvoiceModel->insert($voucherInvData);

            // Update hotel advance if it's a hotel payment
            if ($category === 'Hotel') {
                // $tourQuotationModel = new TourQuotationsModel();
                // $existing = $tourQuotationModel
                //     ->where('quotation_id', $tourId)
                //     ->where('hotel_acc', $supplierAcc)
                //     ->first();
                // if ($existing) {
                //     $newAdvance = ($existing['hotel_advance'] ?? 0) + $amount;
                //     $tourQuotationModel->where('quotation_id', $tourId)->where('hotel_acc', $supplierAcc)
                //         ->set(['hotel_advance' => $newAdvance])->update();

                //     if ($isFullPay) {
                //         $tourQuotationModel->where('quotation_id', $tourId)->where('hotel_acc', $supplierAcc)
                //             ->set([
                //                 'payment_complete'      => 1,
                //                 'payment_complete_user' => session()->get('username'),
                //                 'payment_complete_date' => date('Y-m-d H:i:s'),
                //             ])->update();
                //     }
                // }

                // update payment_request to 4 (fully paid) if is_full_pay is set
                if ($isFullPay) {
                    $paymentRequestModel = new PaymentRequestModel();
                    $paymentRequestModel->where('tour_id', $tourId)
                        ->where('hotel_acc', $supplierAcc)
                        ->where('type', 'Hotel')
                        ->set(['payment_request' => 4, 'voucher_created' => 1])
                        ->update();
                }
            } elseif ($category === 'Activity') {
                // Mirror the Hotel branch above — mark the lunch/activity payment
                // request as accepted (4) so the dashboard shows Payment on Process
                // once the bank payment voucher (Aflag=0) is picked up.
                if ($isFullPay) {
                    $paymentRequestModel = new PaymentRequestModel();
                    $paymentRequestModel->where('tour_id', $tourId)
                        ->where('hotel_acc', $supplierAcc)
                        ->where('type', 'lunch')
                        ->set(['payment_request' => 4, 'voucher_created' => 1])
                        ->update();

                    // Same Dr/Cr double-entry booked at accept time for lunch in
                    // TourAccountsCheckController::bulk_update_payment_request_status.
                    $supplierModel = new SupplierModel();
                    $supplier      = $supplierModel->where('Sup_Acc_No', $supplierAcc)->first();
                    $supplierLCode = $supplier['Sup_Acc_No'] ?? $supplierAcc;
                    $acc_id        = $invoiceNoModel->incrementAccId();
                    $description   = 'Lunch or Other Fee ' . $manualId . ' / ' . $supplierName;

                    $accountRunningModel = new AccountRunningModel();
                    $accountRunningModel->insertBatch([
                        [ // Dr — expense account
                            'ID'           => $acc_id,
                            'L_Code'       => '4-8-109-10653',
                            'Date'         => date('Y-m-d'),
                            'Discription'  => $description,
                            'Dr'           => $amount,
                            'Cr'           => 0.00,
                            'Invoice_Num'  => $tourId,
                            'Linkacc'      => $supplierLCode,
                            'index_no'     => $tourId,
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
                            'Cr'           => $amount,
                            'Invoice_Num'  => $tourId,
                            'Linkacc'      => $supplierLCode,
                            'index_no'     => $tourId,
                            'job'          => '4-8-109-10653',
                            'advances_pay' => 22,
                            'fine_date'    => date('Y-m-d'),
                            'Dt'           => date('Y-m-d H:i:s'),
                            'Cashier'      => session()->get('username'),
                        ],
                    ]);
                }
            }

            // Update transport advance payment_request to 4 (fully paid) if is_full_pay is set
            if ($category === 'Driver Advance') {
                if ($isFullPay) {
                    $paymentRequestModel = new PaymentRequestModel();
                    $paymentRequestModel->where('tour_id', $tourId)
                        ->where('hotel_acc', $supplierAcc)
                        ->where('type', 'Transport Advance')
                        // voucher_created must flip to 1 too, otherwise the dashboard's
                        // "Requested" check (voucher_created=0) still matches and wins
                        // over "Payment on Process" even though the voucher now exists.
                        ->set(['payment_request' => 4, 'voucher_created' => 1])
                        ->update();
                }
            }


            // category === 'Transport' 

            
            return $this->response->setJSON([
                'status'  => 200,
                'message' => 'Payment saved successfully. Voucher No: ' . $vNum,
                'voucher' => $vNum,
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // POST tour/tour_payment_dashboard/recalc_hotel_cost {tour_id}
    // Re-prices every hotel night of the tour from the Hotel Rates panel (ad hoc → contract → quoted).
    public function recalc_hotel_cost()
    {
        $tourId = (string) ($this->request->getPost('tour_id') ?? '');
        if ($tourId === '') {
            return $this->response->setJSON(['status' => 400, 'message' => 'tour_id is required']);
        }
        try {
            $db = \Config\Database::connect();
            $res = (new HotelCostService($db, (string) session()->get('username')))->computeTour($tourId);
            return $this->response->setJSON(['status' => 200, 'message' => 'Hotel costs recalculated for ' . $res['nights'] . ' night(s).', 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // Private: build all dashboard data for a given manual_id
    private function _get_tour_dashboard_data(string $manual_id): ?array
    {
        $db = \Config\Database::connect();

        // ── 1. Tour info ──────────────────────────────────────────────────────
        $consigneeModel = new ConsigneeModel();
        // tf-paydesk-r1: the exact manual id first ("AH 221" must not open "AH 2210"); the old partial match only when nothing is exact
        $tourInfo = $consigneeModel->where('manual_id', $manual_id)->orderBy('verify', 'DESC')->orderBy('ID', 'DESC')->first()
            ?: $consigneeModel->like('manual_id', $manual_id)->first();
        if (!$tourInfo) return null;
        

        $tour_id = $tourInfo['tour_id'];
        $passenger_count = floatval($tourInfo['passenger_count'] ?? 0);

        // ── 2. Hotels (single joined query) ───────────────────────────────────
        // GROUP_CONCAT collects every night's tour_date for a multi-night stay (e.g. a
        // 2-day hotel booking) so the payment table can show all dates instead of MySQL's
        // GROUP BY silently picking just one arbitrary row's tour_date.
        $hotels = (new TourQuotationsModel())
            ->select('
                tour_quotations.hotel_acc,
                tour_quotations.hotel_name,
                tour_quotations.room_type,
                MIN(tour_quotations.tour_date) as tour_date,
                GROUP_CONCAT(DISTINCT tour_quotations.tour_date ORDER BY tour_quotations.tour_date SEPARATOR \', \') as tour_dates,
                GROUP_CONCAT(DISTINCT tour_quotations.quotation_day ORDER BY tour_quotations.tour_date SEPARATOR \', \') as quotation_days,
                MAX(tour_quotations.payment_complete) as payment_complete,
                MAX(tour_quotations.payment_request) as payment_request,
                MAX(tour_quotations.hotel_advance)     as hotel_advance,
                MAX(tour_quotations.total_hotel_doller) as total_hotel_doller,
                MAX(tour_quotations.pay_doller_count)  as pay_doller_count,
                MAX(tour_quotations.hotel_dollar_rate) as hotel_dollar_rate,
                SUM(tour_quotations.sgl) as sgl,
                SUM(tour_quotations.dbl) as dbl,
                SUM(tour_quotations.tpl) as tpl,
                MAX(supplier.Name)        as sup_name,
                MAX(supplier.sup_bank_acc) as sup_bank_acc,
                MAX(supplier.bank_name)   as bank_name,
                MAX(supplier.branch_name) as branch_name
            ')
            ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc', 'left')
            ->where('tour_quotations.quotation_id', $tour_id)
            ->where('tour_quotations.hotel_acc !=', '')
            ->where('tour_quotations.confirm !=', 3)
            ->groupBy('tour_quotations.hotel_acc, tour_quotations.hotel_name')
            ->orderBy('tour_quotations.tour_date', 'ASC')
            ->findAll();

        // What each hotel is actually PAID, from the Hotel Rates panel (ad hoc rate → contract
        // rate → quoted rate flagged). Computed once the tour is confirmed and refreshed when a
        // plan changes; never touches the customer's quotation. Missing tables → no cost info.
        $hotelCosts = [];
        try {
            $hotelCosts = (new HotelCostService($db, (string) session()->get('username')))->ensureTour((string) $tour_id)['hotels'];
            // tf-offers-r1: tacticals / early birds only for a sign-in that may decide them
            $canDecide = \App\Libraries\HotelRates\OfferRates::currentUserCanDecide();
            $hotelCosts = array_map(static fn ($hc) => HotelCostService::forViewer($hc, $canDecide), $hotelCosts);
        } catch (\Throwable $e) {
            $hotelCosts = [];
        }
        // tf-offers-r3: hotel payments held for the System Administrator's rate review (everyone sees that one waits;
        // only a holder of the selection privilege gets what is needed to release or return it)
        $rateReviews = [];
        try {
            $rateReviews = \App\Libraries\HotelRates\PaymentHold::forTour($db, (string) $tour_id, \App\Libraries\HotelRates\OfferRates::currentUserCanDecide());
        } catch (\Throwable $e) {
            $rateReviews = [];
        }
        // tf-crew-master-r1: the Driver & Guide Master's record behind a driver's ledger account — nickname, legal name,
        // category, and the bank for the holders of "Driver & Guide bank details" — so the right person is paid
        $crewPayee = static function (?string $acc) use ($db): ?array {
            static $people = null, $bank = null;
            try {
                if ($people === null) {
                    $people = new \App\Libraries\TourFlow\CrewPeople($db, (string) session()->get('username'));
                    $md = (new MenuLoader())->loadMenuData();
                    $bank = (int) ($md['user_role'] ?? 0) === 1;
                    foreach ($md['allowed_menu_list'] ?? [] as $row) {
                        if (trim((string) ($row['url'] ?? ''), '/') === 'master_files/crew_people/bank') {
                            $bank = true;
                        }
                    }
                }
                return $people->masterReady() ? $people->payee(null, $acc, (bool) $bank) : null;
            } catch (\Throwable $e) {
                return null;
            }
        };

        // hotel_advance can be set outside the accept/voucher flow, so it isn't a
        // reliable "paid" signal on its own — only trust it once a voucher has
        // actually been created for that hotel via the approval pipeline.
        // Fallback: payment_request IN (4,6) is the accepted status itself — rows
        // accepted before voucher_created existed (migration 2026-07-28) still have
        // voucher_created=0, so status is checked too rather than requiring a backfill.
        $hotelVoucherCreated = [];
        $hotelHasPR = [];
        $hotelPRStatus = [];
        $hotelPRDate = [];
        $hotelPRRows = (new PaymentRequestModel())
            ->select('hotel_acc, MAX(voucher_created) as voucher_created, MAX(CASE WHEN payment_request IN (4,6) THEN 1 ELSE 0 END) as was_accepted, MAX(payment_request) as max_pr_status, MAX(payment_request_date) as payment_request_date')
            ->where('tour_id', $tour_id)
            ->where('type', 'Hotel')
            ->groupBy('hotel_acc')
            ->findAll();
        foreach ($hotelPRRows as $row) {
            $hotelVoucherCreated[$row['hotel_acc']] = (bool) $row['voucher_created'] || (bool) $row['was_accepted'];
            $hotelHasPR[$row['hotel_acc']] = true;
            $hotelPRStatus[$row['hotel_acc']] = (int) $row['max_pr_status'];
            $hotelPRDate[$row['hotel_acc']] = $row['payment_request_date'];
        }

        // Same reasoning as hotels: tour_activity.payment_request is set at request
        // *creation* time (see TourAccountsController::payment_request), not on accept,
        // so it can't be trusted as a paid signal either — only voucher_created (or the
        // was_accepted fallback above) can.
        $activityVoucherCreated = [];
        $activityHasPR = [];
        $activityPRStatus = [];
        $activityPRDate = [];
        $activityPRRows = (new PaymentRequestModel())
            ->select('hotel_acc, MAX(voucher_created) as voucher_created, MAX(CASE WHEN payment_request IN (4,6) THEN 1 ELSE 0 END) as was_accepted, MAX(payment_request) as max_pr_status, MAX(payment_request_date) as payment_request_date')
            ->where('tour_id', $tour_id)
            ->where('type', 'lunch')
            ->groupBy('hotel_acc')
            ->findAll();
        foreach ($activityPRRows as $row) {
            $activityVoucherCreated[$row['hotel_acc']] = (bool) $row['voucher_created'] || (bool) $row['was_accepted'];
            $activityHasPR[$row['hotel_acc']] = true;
            $activityPRStatus[$row['hotel_acc']] = (int) $row['max_pr_status'];
            $activityPRDate[$row['hotel_acc']] = $row['payment_request_date'];
        }

        // A voucher existing only means the payment request was accepted — the bank
        // payment itself isn't confirmed until it's finalized via Bank Payment Check.
        // Until then, treat it as "Pending Payment" rather than "Fully Paid".
        //
        // Joined through v_inv.Invoice rather than filtered on voucher.C_Number directly:
        // when payment_approval_v2's bulk "Create Voucher" bundles multiple tours' charges
        // to the same payee into one voucher, that voucher header can only hold a single
        // C_Number, arbitrarily set to one tour in the batch — so a tour whose charges rode
        // along in someone else's bulk voucher would never match C_Number = own tour_id and
        // would wrongly show as unpaid. v_inv.Invoice is set per line from the originating
        // payment_request row and is always correct, bulk or not.
        $voucherFinalizedByAcc = [];
        $voucherRows = $db->query(
            'SELECT v.Acc_No, MAX(v.finalized) as finalized
             FROM v_inv vi
             JOIN voucher v ON v.Vnum = vi.V_tkn
             WHERE vi.Invoice = ?
             GROUP BY v.Acc_No',
            [$tour_id]
        )->getResultArray();
        foreach ($voucherRows as $row) {
            $voucherFinalizedByAcc[$row['Acc_No']] = (bool) $row['finalized'];
        }

        // Voucher exists with Aflag=0 means payment was submitted but not yet approved —
        // used to show "Payment on Process" when payment_request=1 but payment already sent.
        $voucherPendingByAcc = [];
        $voucherPendingRows = $db->query(
            'SELECT DISTINCT v.Acc_No
             FROM v_inv vi
             JOIN voucher v ON v.Vnum = vi.V_tkn
             WHERE vi.Invoice = ? AND v.Aflag = 0',
            [$tour_id]
        )->getResultArray();
        foreach ($voucherPendingRows as $row) {
            $voucherPendingByAcc[$row['Acc_No']] = true;
        }

        // Voucher exists with Aflag=1 means payment was approved/paid —
        // used to show "Paid" when payment_request=1 and bank payment is confirmed.
        $voucherPaidByAcc = [];
        $voucherPaidRows = $db->query(
            'SELECT DISTINCT v.Acc_No
             FROM v_inv vi
             JOIN voucher v ON v.Vnum = vi.V_tkn
             WHERE vi.Invoice = ? AND v.Aflag = 1',
            [$tour_id]
        )->getResultArray();
        foreach ($voucherPaidRows as $row) {
            $voucherPaidByAcc[$row['Acc_No']] = true;
        }

        // Pending (not yet accepted) Transport Advance requests per driver — these
        // have no voucher/log-sheet data at all, so without this they're invisible
        // on this dashboard. Surfaced as a separate "Requested" row.
        $pendingTransportAdvanceByDriver = [];
        $transportPRStatus = [];
        $transportPRRows = (new PaymentRequestModel())
            ->select('hotel_acc, SUM(requestAmount) as requested_amount, MAX(payment_request) as max_pr_status')
            ->where('tour_id', $tour_id)
            ->where('type', 'Transport Advance')
            ->where('voucher_created', 0)
            ->groupBy('hotel_acc')
            ->findAll();
        foreach ($transportPRRows as $row) {
            $pendingTransportAdvanceByDriver[$row['hotel_acc']] = floatval($row['requested_amount']);
            $transportPRStatus[$row['hotel_acc']] = (int) $row['max_pr_status'];
        }

        // Whether ANY payment_request row exists at all for this driver (regardless
        // of voucher_created) — used to distinguish "Payment Not Requested" (no row)
        // from the log-sheet/advance-driven statuses below. Also tracks the max
        // payment_request value across ALL rows (not just voucher_created=0, unlike
        // $transportPRStatus above) so accepted (=4) requests can be detected too.
        $transportHasPR = [];
        $transportAnyPRStatus = [];
        $transportPRDate = [];
        $transportHasPRRows = (new PaymentRequestModel())
            ->select('hotel_acc, MAX(payment_request) as max_pr_status, MAX(payment_request_date) as payment_request_date')
            ->where('tour_id', $tour_id)
            ->where('type', 'Transport Advance')
            ->groupBy('hotel_acc')
            ->findAll();
        foreach ($transportHasPRRows as $row) {
            $transportHasPR[$row['hotel_acc']] = true;
            $transportAnyPRStatus[$row['hotel_acc']] = (int) $row['max_pr_status'];
            $transportPRDate[$row['hotel_acc']] = $row['payment_request_date'];
        }

        // Driver-submitted advance requests not yet pushed into payment_request
        // (flag=0) — this is the amount/id staff need to action via
        // driver_advance_payment_request(), same source tour_accounts_note uses.
        $pendingDriverAdvanceRequestByDriver = [];
        $driverAdvanceRequestRows = (new DriverAdvanceRequestModel())
            ->where('tour_id', $tour_id)
            ->where('flag', 0)
            ->orderBy('id', 'DESC')
            ->findAll();
        foreach ($driverAdvanceRequestRows as $row) {
            if (!isset($pendingDriverAdvanceRequestByDriver[$row['driver']])) {
                $pendingDriverAdvanceRequestByDriver[$row['driver']] = $row;
            }
        }

        // ── 3. Activities (single joined query) ───────────────────────────────
        $activities = (new TourActivityModel())
            ->select('
                tour_activity.*,
                supplier.Name       as sup_name,
                supplier.sup_bank_acc,
                supplier.bank_name,
                supplier.branch_name
            ')
            ->join('supplier', 'supplier.Sup_Acc_No = tour_activity.supplier_id', 'left')
            ->where('tour_activity.tour_id', $tour_id)
            ->where('tour_activity.supplier_id IS NOT NULL', null, false)
            ->where('tour_activity.supplier_id !=', '')
            ->orderBy('tour_activity.tour_date', 'ASC')
            ->findAll();

        // ── 4. Driver allocations ─────────────────────────────────────────────
        $driverAllocations = (new DriverAllocationModel())
            ->where('tour_id', $tour_id)
            ->findAll();

        // ── 5. Log sheet miles — ALL drivers for this tour in ONE query ────────
        //    Keep only the latest row per transport_agent (highest id).
        $logSheetRows = $db->query(
            'SELECT ls.*
             FROM log_sheet_mile ls
             INNER JOIN (
                 SELECT transport_agent, MAX(id) as max_id
                 FROM log_sheet_mile
                 WHERE tour_id = ?
                 GROUP BY transport_agent
             ) latest ON ls.id = latest.max_id',
            [$tour_id]
        )->getResultArray();

        // Index by transport_agent for O(1) lookup
        $logSheetByDriver = [];
        foreach ($logSheetRows as $ls) {
            $logSheetByDriver[$ls['transport_agent']] = $ls;
        }

        // ── 6. Transport payment components — ALL drivers for this tour ──────
        // dr: advance vouchers paid (advances_pay=1, Aflag=1)
        $advanceRows = $db->query(
            'SELECT Acc_No, SUM(Amount) as total, GROUP_CONCAT(Vnum ORDER BY Vnum) as vnums
             FROM voucher
             WHERE ref_po = ? AND Aflag = 1 AND advances_pay = 1
             GROUP BY Acc_No',
            [$tour_id]
        )->getResultArray();
        $advanceByDriver = [];
        $advanceVnumsByDriver = [];
        foreach ($advanceRows as $row) {
            $advanceByDriver[$row['Acc_No']] = floatval($row['total']);
            $advanceVnumsByDriver[$row['Acc_No']] = $row['vnums'];
        }

        // Vouchers that exist (accepted) but haven't been approved/finalized yet via
        // Bank Payment Check — without this, an accepted-but-unfinalized advance has
        // no dr amount (Aflag != 1) and no pending payment_request (voucher_created=1),
        // so it would otherwise disappear from the dashboard entirely.
        $unfinalizedAdvanceByDriver = [];
        $unfinalizedAdvanceVnumsByDriver = [];
        $unfinalizedAdvanceRows = $db->query(
            'SELECT Acc_No, SUM(Amount) as total, GROUP_CONCAT(Vnum ORDER BY Vnum) as vnums
             FROM voucher
             WHERE ref_po = ? AND advances_pay = 1 AND Aflag != 1
             GROUP BY Acc_No',
            [$tour_id]
        )->getResultArray();
        foreach ($unfinalizedAdvanceRows as $row) {
            $unfinalizedAdvanceByDriver[$row['Acc_No']] = floatval($row['total']);
            $unfinalizedAdvanceVnumsByDriver[$row['Acc_No']] = $row['vnums'];
        }

        // ad_cr: advance CR reversals (advances_pay=1111)
        $adCrRows = $db->query(
            'SELECT L_Code, SUM(Cr) as total
             FROM account_runing
             WHERE index_no = ? AND advances_pay = 1111
             GROUP BY L_Code',
            [$tour_id]
        )->getResultArray();
        $adCrByDriver = [];
        foreach ($adCrRows as $row) {
            $adCrByDriver[$row['L_Code']] = floatval($row['total']);
        }

        // cr: expenses posted against advance (advances_pay=111, NOT LIKE VOUCHER PAYMENT%)
        $expCrRows = $db->query(
            'SELECT L_Code, SUM(Cr) as total
             FROM account_runing
             WHERE index_no = ? AND Cr > 0 AND advances_pay = 111
               AND Discription NOT LIKE \'VOUCHER PAYMENT%\'
             GROUP BY L_Code',
            [$tour_id]
        )->getResultArray();
        $expCrByDriver = [];
        foreach ($expCrRows as $row) {
            $expCrByDriver[$row['L_Code']] = floatval($row['total']);
        }

        // total_pay_cr: settled payments (advances_pay=11, LIKE VOUCHER PAYMENT% or JOURNAL ENTRY%)
        $totalPayCrRows = $db->query(
            'SELECT L_Code, SUM(Cr) as total
             FROM account_runing
             WHERE index_no = ? AND Cr > 0 AND advances_pay = 11
               AND (Discription LIKE \'VOUCHER PAYMENT%\' OR Discription LIKE \'JOURNAL ENTRY%\')
             GROUP BY L_Code',
            [$tour_id]
        )->getResultArray();
        $totalPayCrByDriver = [];
        foreach ($totalPayCrRows as $row) {
            $totalPayCrByDriver[$row['L_Code']] = floatval($row['total']);
        }

        // ── 7. Driver expenses (single query) ─────────────────────────────────
        $driverExpensesAll = (new DriverExpensesModel())
            ->where('tour_id', $tour_id)
            ->whereIn('status', ['approved', 'posted', 'completed'])
            ->findAll();

        // ── 8. Supplier bank details for all driver codes — ONE query ──────────
        $driverCodes = array_column($driverAllocations, 'driver');
        // Also include driver expense agents
        foreach ($driverExpensesAll as $de) {
            $driverCodes[] = $de['agent'];
        }
        $driverCodes = array_unique(array_filter($driverCodes));

        $supplierByCode = [];
        if (!empty($driverCodes)) {
            $supRows = (new SupplierModel())
                ->select('Sup_Acc_No, sup_bank_acc, bank_name, branch_name')
                ->whereIn('Sup_Acc_No', $driverCodes)
                ->findAll();
            foreach ($supRows as $s) {
                $supplierByCode[$s['Sup_Acc_No']] = $s;
            }
        }

        // ── Build transport rows (no DB calls inside loop) ────────────────────
        $transport_rows = [];
        foreach ($driverAllocations as $alloc) {
            $agent_code = $alloc['driver'];
            $agent_name = $alloc['driver_name'] ?? '-';

            $ls             = $logSheetByDriver[$agent_code] ?? null;
            $logSheetAmount = 0;
            if ($ls && floatval($ls['log_sheet_mileage'] ?? 0) > 0) {
                $logSheetAmount = ($ls['log_sheet_mileage'] * $ls['vehicle_rate'])
                    + ($ls['per_day_rate'] * $ls['no_of_dates']);
            }

            // Advance Payment       = dr - ad_cr
            // Advance Remaining Bal = dr - ad_cr - cr
            // Payable Amount        = total_transport - (dr - ad_cr) - cr - total_pay_cr
            // Note: cr (expenses already paid out of the driver's advance) is SUBTRACTED here,
            // unlike the driver_settlement page it was originally copied from — that page adds
            // cr, which double-counts already-settled advance expenses as still payable. Fixed
            // only for this dashboard's calculation; the settlement page's copy is untouched.
            $dr           = $advanceByDriver[$agent_code]     ?? 0;
            $ad_cr        = $adCrByDriver[$agent_code]        ?? 0;
            $cr           = $expCrByDriver[$agent_code]       ?? 0;
            $total_pay_cr = $totalPayCrByDriver[$agent_code]  ?? 0;

            $advanceNet = $dr - $ad_cr;                                          // Advance Payment
            $advanceRemainingBalance = $advanceNet - $cr;                        // Advance Remaining Balance
            $payableAmount = ($logSheetAmount - $advanceNet) - $cr - $total_pay_cr; // Payable Amount

            $paid    = $logSheetAmount - $payableAmount;
            $balance = $payableAmount;

            $pendingRequestAmount     = $pendingTransportAdvanceByDriver[$agent_code] ?? 0;
            $unfinalizedAdvanceAmount = $unfinalizedAdvanceByDriver[$agent_code] ?? 0;
            $description = 'Vehicle + KM Charge';
            $voucherNo   = null;

            // Payment Category for "Pay Now": rows driven by an actual log_sheet_mile
            // entry are a Transport (settlement) payment; everything else here is a
            // Driver Advance request/payment, since no log sheet has been submitted yet.
            $paymentCategory = $logSheetAmount > 0 ? 'Transport' : 'Driver Advance';

            $driverAdvanceRequest   = $pendingDriverAdvanceRequestByDriver[$agent_code] ?? null;
            $driverAdvanceRequestId = null;

            $status = 'Not Paid';
            if ($logSheetAmount <= 0 && $advanceNet <= 0 && !isset($transportHasPR[$agent_code])) {
                // No payment_request row has ever been submitted for this driver at all.
                // If the driver already submitted an advance request (driver_advance_request,
                // flag=0), surface its amount/id so staff can push it through, same as
                // tour_accounts_note's "Advance Request" row.
                $status      = 'Payment Not Requested';
                $total       = $driverAdvanceRequest ? floatval($driverAdvanceRequest['amount']) : 0;
                $paid        = 0;
                $balance     = $total;
                $description = 'Driver Advance';
                $driverAdvanceRequestId = $driverAdvanceRequest['id'] ?? null;
            } elseif ($logSheetAmount <= 0 && in_array(($transportAnyPRStatus[$agent_code] ?? -1), [2, 4], true)) {
                // payment_request = 2 or 4 — advance has been paid/approved
                $requestedAmount = $pendingTransportAdvanceByDriver[$agent_code] ?? $unfinalizedAdvanceByDriver[$agent_code] ?? 0;
                $total       = $advanceNet > 0 ? $advanceNet : $requestedAmount;
                $paid        = $total;
                $balance     = 0;
                $status      = 'Advance Paid';
                $description = 'Driver Advance';
                $voucherNo   = $advanceVnumsByDriver[$agent_code] ?? ($unfinalizedAdvanceVnumsByDriver[$agent_code] ?? null);
            } elseif ($logSheetAmount <= 0 && $advanceNet <= 0 && $pendingRequestAmount > 0 && ($transportPRStatus[$agent_code] ?? -1) === 1) {
                // Payment request submitted (payment_request = 1) — ready to pay
                $status      = 'Not Paid';
                $total       = $pendingRequestAmount;
                $paid        = 0;
                $balance     = $pendingRequestAmount;
                $description = 'Driver Advance';
            } elseif ($logSheetAmount <= 0 && $advanceNet <= 0 && $pendingRequestAmount > 0) {
                // A Transport Advance request has been submitted but not yet accepted —
                // no voucher/log-sheet data exists yet, so surface the request itself.
                $status      = 'Requested';
                $total       = $pendingRequestAmount;
                $paid        = 0;
                $balance     = $pendingRequestAmount;
                $description = 'Driver Advance';
            } elseif ($logSheetAmount <= 0 && $advanceNet <= 0 && $unfinalizedAdvanceAmount > 0 && ($transportAnyPRStatus[$agent_code] ?? -1) === 4 && ($voucherPendingByAcc[$agent_code] ?? false)) {
                // Payment request accepted (payment_request = 4) and voucher Aflag=0 —
                // payment sent but awaiting bank approval
                $status      = 'Payment on Process';
                $total       = $unfinalizedAdvanceAmount;
                $paid        = 0;
                $balance     = $unfinalizedAdvanceAmount;
                $voucherNo   = $unfinalizedAdvanceVnumsByDriver[$agent_code] ?? null;
                $description = 'Driver Advance - Voucher ' . $voucherNo;
            } elseif ($logSheetAmount <= 0 && $advanceNet <= 0 && $unfinalizedAdvanceAmount > 0) {
                // Request was accepted (voucher exists) but hasn't been approved/
                // finalized via Bank Payment Check yet — still not payable/paid.
                $status      = 'Pending Payment';
                $total       = $unfinalizedAdvanceAmount;
                $paid        = 0;
                $balance     = $unfinalizedAdvanceAmount;
                $voucherNo   = $unfinalizedAdvanceVnumsByDriver[$agent_code] ?? null;
                $description = 'Driver Advance - Voucher ' . $voucherNo;
            } elseif ($logSheetAmount <= 0 && $advanceNet > 0) {
                // No log sheet submitted yet, so the real payable amount is unknown —
                // show the advance as given rather than a misleading negative balance.
                $status      = 'Advance Paid';
                $total       = $logSheetAmount;
                $paid        = $advanceNet;
                $balance     = 0;
                $voucherNo   = $advanceVnumsByDriver[$agent_code] ?? null;
                $description = 'Driver Advance - Voucher ' . $voucherNo;
            } elseif ($logSheetAmount > 0 && $payableAmount <= 0) {
                $status = 'Fully Paid';
                $total  = $logSheetAmount;
            } elseif ($paid > 0) {
                $status = 'Partially Paid';
                $total  = $logSheetAmount;
            } else {
                $total = $logSheetAmount;
            }

            $transport_rows[] = [
                'driver_code'              => $agent_code,
                'driver_name'              => $agent_name,
                'total'                    => $total,
                'request_date'             => $transportPRDate[$agent_code] ?? null,
                'paid'                     => $paid,
                'balance'                  => $balance,
                'due_date'                 => $tourInfo['end_date'] ?? '',
                'status'                   => $status,
                'description'              => $description,
                'voucher_no'               => $voucherNo,
                'advance_payment'          => $advanceNet,
                'advance_expenses'         => $cr,
                'advance_remaining'        => $advanceRemainingBalance,
                'total_pay_cr'             => $total_pay_cr,
                'payment_category'         => $paymentCategory,
                'driver_advance_request_id' => $driverAdvanceRequestId,
            ];
        }

        // ── Driver expense groups (no DB calls inside loop) ───────────────────
        $driverGroups = [];
        foreach ($driverExpensesAll as $d) {
            $key = $d['agent'];
            if (!isset($driverGroups[$key])) {
                $driverGroups[$key] = [
                    'agent'      => $key,
                    'agent_name' => $d['agent_name'] ?? '-',
                    'total'      => 0,
                    'paid'       => 0,
                    'date'       => $d['expense_date'] ?? '',
                ];
            }
            $amt = floatval($d['lkr_amount'] ?? 0);
            $driverGroups[$key]['total'] += $amt;
            if ($d['status'] === 'completed') {
                $driverGroups[$key]['paid'] += $amt;
            }
        }

        // ── Category totals (pure PHP, no queries) ────────────────────────────
        $activity_total = 0; $activity_paid = 0;
        foreach ($activities as $a) {
            $amt = floatval($a['lkr_fee'] ?? 0) * $passenger_count;
            $activity_total += $amt;
            $supAcc = $a['supplier_id'] ?? '';
            if (($activityVoucherCreated[$supAcc] ?? false) && ($voucherFinalizedByAcc[$supAcc] ?? false)) {
                $activity_paid += $amt;
            }
        }

        $transport_total = 0; $transport_paid = 0;
        $driver_advance_total = 0; $driver_advance_paid = 0;
        $driver_settlement_total = 0; $driver_settlement_paid = 0;
        foreach ($transport_rows as $t) {
            $transport_total += $t['total'];
            $transport_paid  += $t['paid'];
            if ($t['payment_category'] === 'Transport') {
                $driver_settlement_total += $t['total'];
                $driver_settlement_paid  += $t['paid'];
            } else {
                $driver_advance_total += $t['total'];
                $driver_advance_paid  += $t['paid'];
            }
        }

        $driver_total = 0; $driver_paid = 0;
        foreach ($driverGroups as $g) {
            $driver_total += $g['total'];
            $driver_paid  += $g['paid'];
        }

        // ── Build payment rows (no DB calls) ──────────────────────────────────
        $payment_rows = [];

        foreach ($hotels as $idx => $h) {
            $rate       = $h['hotel_dollar_rate'] ?? 125;
            $total      = floatval(($h['total_hotel_doller'] ?? 0) * $rate);
            $hasVoucher = $hotelVoucherCreated[$h['hotel_acc']] ?? false;

            // $ quote sum — same "Q Amount" figure tour_accounts shows/defaults the
            // Request Amount to (rate-per-room-type × booked room count), not the
            // LKR total above. Falls back to total_hotel_doller (C Amount) once that's
            // been confirmed and no sgl/dbl/tpl quote exists.
            $totalDollarQuote = (floatval($h['sgl'] ?? 0) * floatval($tourInfo['sgl'] ?? 0))
                + (floatval($h['dbl'] ?? 0) * floatval($tourInfo['dbl'] ?? 0))
                + (floatval($h['tpl'] ?? 0) * floatval($tourInfo['tpl'] ?? 0));
            if ($totalDollarQuote <= 0) {
                $totalDollarQuote = floatval($h['total_hotel_doller'] ?? 0);
            }

            if (!isset($hotelHasPR[$h['hotel_acc']])) {
                // No payment request has been submitted for this hotel at all
                $paid    = 0;
                $balance = $total;
                $status  = 'Payment Not Requested';
            } elseif (($hotelPRStatus[$h['hotel_acc']] ?? -1) === 2) {
                // payment_request = 2 — fully paid across all categories
                $paid    = $total;
                $balance = 0;
                $status  = 'Fully Paid';
            } elseif (($hotelPRStatus[$h['hotel_acc']] ?? -1) === 0) {
                // Payment request exists but payment_request = 0 — rate not yet confirmed
                $paid    = 0;
                $balance = $total;
                $status  = 'Waiting Rate Confirmation';
            } elseif (($hotelPRStatus[$h['hotel_acc']] ?? -1) === 1) {
                // Payment request submitted (payment_request = 1)
                if ($voucherPaidByAcc[$h['hotel_acc']] ?? false) {
                    // Voucher Aflag=1 — payment approved and confirmed
                    $paid    = $total;
                    $balance = 0;
                    $status  = 'Paid';
                } else {
                    // No voucher yet — ready to pay
                    $paid    = 0;
                    $balance = $total;
                    $status  = 'Not Paid';
                }
            } elseif (($hotelPRStatus[$h['hotel_acc']] ?? -1) === 4 && ($voucherPendingByAcc[$h['hotel_acc']] ?? false)) {
                // Payment request accepted (payment_request = 4) and voucher Aflag=0 —
                // payment sent but awaiting bank approval
                $paid    = 0;
                $balance = $total;
                $status  = 'Payment on Process';
            } elseif (!$hasVoucher) {
                // No payment_request for this hotel has ever been accepted (voucher
                // created), so hotel_advance/pay_doller_count can't be trusted as paid —
                // they may have been set by an older, separate flow.
                $paid    = 0;
                $balance = $total;
                $status  = 'Pending Approval';
            } else {
                // A voucher existing only means the request was accepted — the bank
                // payment isn't confirmed until finalized via Bank Payment Check, so
                // nothing counts as paid until then (consistent with Activities below).
                $isFinalized = $voucherFinalizedByAcc[$h['hotel_acc']] ?? false;

                if (!$isFinalized) {
                    $paid    = 0;
                    $balance = $total;
                    $status  = 'Pending Payment';
                } else {
                    // hotel_advance (LKR) is set by the payment_request/bank-check accept flow;
                    // pay_doller_count (USD) is set by the older direct hotel-payment flow.
                    // Prefer hotel_advance since it reflects the currently used approval pipeline.
                    $advanceLkr = floatval($h['hotel_advance'] ?? 0);
                    $paid    = $advanceLkr > 0 ? $advanceLkr : floatval(($h['pay_doller_count'] ?? 0) * $rate);
                    $balance = $total - $paid;
                    if ($h['payment_complete'] || ($total > 0 && $paid >= $total)) {
                        $status = 'Fully Paid';
                    } elseif ($paid > 0) {
                        $status = 'Partially Paid';
                    } else {
                        $status = 'Pending Payment';
                    }
                }
            }
            $stayDates  = !empty($h['tour_dates']) ? array_filter(array_map('trim', explode(',', $h['tour_dates']))) : [];
            $nightCount = count($stayDates);

            // Real tour day number(s) this hotel stay spans (e.g. "2, 3" for a 2-night
            // stay on days 2 and 3), not just this row's position in the hotels list.
            $dayLabel = 'Day ' . ($idx + 1);
            if (!empty($h['quotation_days'])) {
                $dayNums = array_filter(array_map(function ($d) {
                    return (int) round((float) trim($d));
                }, explode(',', $h['quotation_days'])));
                if (!empty($dayNums)) {
                    $dayLabel = 'Day ' . implode(', ', $dayNums);
                }
            }

            $payment_rows[] = [
                'day'               => $dayLabel,
                'category'          => 'Hotel',
                'category_key'      => 'hotel',
                'supplier'          => $h['hotel_name'] ?: ($h['sup_name'] ?? '-'),
                'supplier_acc'      => $h['hotel_acc'],
                'supplier_bank_acc' => $h['sup_bank_acc'] ?? '',
                'bank_name'         => $h['bank_name'] ?? '',
                'branch_name'       => $h['branch_name'] ?? '',
                'description'       => $h['room_type'] ?? 'Hotel Accommodation',
                'total'             => $total,
                'total_dollar'      => $totalDollarQuote,
                'usd_rate'          => $hotelHasPR[$h['hotel_acc']] ?? false ? $rate : null,
                'request_date'      => $hotelPRDate[$h['hotel_acc']] ?? null,
                'paid'              => $paid,
                'balance'           => $balance,
                'due_date'          => $h['tour_date'] ?? '',
                'stay_dates'        => $stayDates,
                'night_count'       => $nightCount,
                'status'            => $status,
                // hotel cost handoff (USD): what we pay the hotel per the Hotel Rates panel
                'cost_dollar'       => isset($hotelCosts[$h['hotel_acc']]) ? (float) $hotelCosts[$h['hotel_acc']]['pay_total'] : null,
                'cost_source'       => $hotelCosts[$h['hotel_acc']]['source'] ?? null,
                'cost_source_label' => $hotelCosts[$h['hotel_acc']]['source_label'] ?? null,
                'cost_needs_check'  => $hotelCosts[$h['hotel_acc']]['needs_check'] ?? null,
                'cost_check_notes'  => $hotelCosts[$h['hotel_acc']]['check_notes'] ?? [],
                'cost_plans'        => $hotelCosts[$h['hotel_acc']]['plans'] ?? [],
                'cost_supplements'  => isset($hotelCosts[$h['hotel_acc']]) ? (float) $hotelCosts[$h['hotel_acc']]['supplements_total'] : null,
                'cost_margin'       => isset($hotelCosts[$h['hotel_acc']]) ? round($totalDollarQuote - (float) $hotelCosts[$h['hotel_acc']]['pay_total'], 2) : null,
                'cost_nights'       => isset($hotelCosts[$h['hotel_acc']]) ? $hotelCosts[$h['hotel_acc']]['rows'] : [],
                'cost_computed_at'  => $hotelCosts[$h['hotel_acc']]['computed_at'] ?? null,
                // tf-offers-r1: published rate · tactical / early bird · rate used for the payment · saving · choice · history
                'cost_offer'        => HotelCostService::offerFields($hotelCosts[$h['hotel_acc']] ?? null),
                // tf-offers-r3: waiting for the rate review (or returned from it) — see PaymentHold::forTour
                'rate_review'       => $rateReviews[$h['hotel_acc']] ?? null,
            ];
        }

        // Derived from payment_rows (not recomputed) so the summary/category cards
        // never drift from the per-row status logic above.
        $hotel_total = 0; $hotel_paid = 0;
        foreach ($payment_rows as $row) {
            if ($row['category_key'] === 'hotel') {
                $hotel_total += $row['total'];
                $hotel_paid  += $row['paid'];
            }
        }

        foreach ($activities as $idx => $a) {
            $total      = floatval($a['lkr_fee'] ?? 0) * $passenger_count;
            $hasVoucher = $activityVoucherCreated[$a['supplier_id'] ?? ''] ?? false;

            if (!isset($activityHasPR[$a['supplier_id'] ?? ''])) {
                // No payment request has been submitted for this activity at all
                $paid    = 0;
                $balance = $total;
                $status  = 'Payment Not Requested';
            } elseif (($activityPRStatus[$a['supplier_id'] ?? ''] ?? -1) === 2) {
                // payment_request = 2 — fully paid across all categories
                $paid    = $total;
                $balance = 0;
                $status  = 'Fully Paid';
            } elseif (($activityPRStatus[$a['supplier_id'] ?? ''] ?? -1) === 1) {
                // Payment request submitted (payment_request = 1) — ready to pay
                $paid    = 0;
                $balance = $total;
                $status  = 'Not Paid';
            } elseif (($activityPRStatus[$a['supplier_id'] ?? ''] ?? -1) === 4 && ($voucherPendingByAcc[$a['supplier_id'] ?? ''] ?? false)) {
                // Payment request accepted (payment_request = 4) and voucher Aflag=0 —
                // payment sent but awaiting bank approval
                $paid    = 0;
                $balance = $total;
                $status  = 'Payment on Process';
            } elseif (!$hasVoucher) {
                $paid    = 0;
                $balance = $total;
                $status  = 'Pending Approval';
            } else {
                // As with hotels: a voucher existing means accepted, not bank-confirmed —
                // only finalized vouchers (Bank Payment Check) count as Fully Paid.
                $isFinalized = $voucherFinalizedByAcc[$a['supplier_id'] ?? ''] ?? false;
                $paid    = $isFinalized ? $total : 0;
                $balance = $total - $paid;
                $status  = $isFinalized && $total > 0 ? 'Fully Paid' : 'Pending Payment';
            }
            $payment_rows[] = [
                'day'               => 'Day ' . ($idx + 1),
                'category'          => 'Activity',
                'category_key'      => 'activity',
                'supplier'          => $a['supplier_name'] ?: ($a['sup_name'] ?? '-'),
                'supplier_acc'      => $a['supplier_id'] ?? '',
                'supplier_bank_acc' => $a['sup_bank_acc'] ?? '',
                'bank_name'         => $a['bank_name'] ?? '',
                'branch_name'       => $a['branch_name'] ?? '',
                'description'       => $a['name'] ?? 'Activity',
                'total'             => $total,
                'request_date'      => $activityPRDate[$a['supplier_id'] ?? ''] ?? null,
                'paid'              => $paid,
                'balance'           => $balance,
                'due_date'          => $a['tour_date'] ?? '',
                'status'            => $status,
                'activity_id'       => $a['id'],
            ];
        }

        foreach ($transport_rows as $idx => $t) {
            $sup = $supplierByCode[$t['driver_code']] ?? [];
            $isSettlement = ($t['payment_category'] === 'Transport');
            $payment_rows[] = [
                'day'               => 'Day ' . ($idx + 1),
                'category'          => $isSettlement ? 'Driver Settlement' : 'Driver Advance',
                'category_key'      => $isSettlement ? 'driver_settlement' : 'driver_advance',
                'supplier'          => $t['driver_name'],
                'supplier_acc'      => $t['driver_code'],
                'supplier_bank_acc' => $sup['sup_bank_acc'] ?? '',
                'bank_name'         => $sup['bank_name'] ?? '',
                'branch_name'       => $sup['branch_name'] ?? '',
                'description'       => $t['description'] ?? 'Vehicle + KM Charge',
                'voucher_no'        => $t['voucher_no'] ?? null,
                'total'             => $t['total'],
                'request_date'      => $t['request_date'] ?? null,
                'paid'              => $t['paid'],
                'balance'           => $t['balance'],
                'due_date'          => $t['due_date'],
                'status'            => $t['status'],
                'advance_payment'   => $t['advance_payment'],
                'advance_expenses'  => $t['advance_expenses'],
                'advance_remaining' => $t['advance_remaining'],
                'total_pay_cr'      => $t['total_pay_cr'],
                'driver_advance_request_id' => $t['driver_advance_request_id'] ?? null,
                'payee'             => $crewPayee($t['driver_code']),
            ];
        }

        $dIdx = 1;
        foreach ($driverGroups as $g) {
            $sup     = $supplierByCode[$g['agent']] ?? [];
            $balance = $g['total'] - $g['paid'];
            $status  = $g['total'] > 0 && $g['paid'] >= $g['total'] ? 'Fully Paid'
                     : ($g['paid'] > 0 ? 'Partially Paid' : 'Not Paid');
            $payment_rows[] = [
                'day'               => 'Day ' . $dIdx++,
                'category'          => 'Driver',
                'category_key'      => 'driver',
                'supplier'          => $g['agent_name'],
                'supplier_acc'      => $g['agent'],
                'supplier_bank_acc' => $sup['sup_bank_acc'] ?? '',
                'bank_name'         => $sup['bank_name'] ?? '',
                'branch_name'       => $sup['branch_name'] ?? '',
                'description'       => 'Daily Bata',
                'total'             => $g['total'],
                'paid'              => $g['paid'],
                'balance'           => $balance,
                'due_date'          => $g['date'],
                'status'            => $status,
                'payee'             => $crewPayee($g['agent']),
            ];
        }

        // ── Grand totals ──────────────────────────────────────────────────────
        $per_person_cost = floatval($tourInfo['per_person_cost'] ?? 0);
        $total_estimated = ($per_person_cost - 75) * $passenger_count * 275;
        $total_paid      = $hotel_paid  + $activity_paid  + $transport_paid  + $driver_paid;
        $outstanding     = $total_estimated - $total_paid;

        $today    = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $upcoming_due = 0;
        foreach ($payment_rows as $row) {
            if (!empty($row['due_date'])
                && $row['due_date'] >= $today
                && $row['due_date'] <= $nextWeek
                && $row['balance'] > 0
            ) {
                $upcoming_due += $row['balance'];
            }
        }

        $tourStatus = 'In Progress';
        if (!empty($tourInfo['tour_cancel']))       $tourStatus = 'Cancelled';
        elseif (!empty($tourInfo['payment_complete'])) $tourStatus = 'Completed';

        $allocated_drivers = array_values(array_unique(array_filter(array_column($transport_rows, 'driver_name'), function ($name) {
            return $name && $name !== '-';
        })));

        // ── Tickets (usage notes) — entrance-fee/activity items requested from stock,
        // grouped by usage_note_no (one note can carry multiple items/activities), showing
        // request/approval status. Not a money category — usage notes aren't tour payments,
        // they're stock issue requests, so this is its own section, not folded into `categories`.
        $ticketNotes = (new UsageNoteModel())
            ->select('
                usage_note.usage_note_no,
                usage_note.date,
                usage_note.status,
                usage_note.created_by,
                usage_note.approved_by,
                usage_note.approved_at,
                usage_note.rejection_reason,
                COUNT(*) as item_count,
                SUM(usage_note.quantity) as total_quantity,
                GROUP_CONCAT(DISTINCT stock_card.Descrip ORDER BY stock_card.Descrip SEPARATOR \', \') as item_names,
                creator.user as created_by_name,
                approver.user as approved_by_name
            ')
            ->join('stock_card', 'stock_card.Stock_Card = usage_note.item_code', 'left')
            // user_acc_tbl.acc_no is not unique (duplicate accounts exist), so join a
            // deduped one-row-per-acc_no subquery instead of the raw table — matches the
            // pattern already used in UsageNoteController to avoid fanning out rows.
            ->join('(SELECT acc_no, MIN(id) as min_id FROM user_acc_tbl GROUP BY acc_no) as creator_ids', 'creator_ids.acc_no = usage_note.created_by', 'left')
            ->join('user_acc_tbl as creator', 'creator.id = creator_ids.min_id', 'left')
            ->join('(SELECT acc_no, MIN(id) as min_id FROM user_acc_tbl GROUP BY acc_no) as approver_ids', 'approver_ids.acc_no = usage_note.approved_by', 'left')
            ->join('user_acc_tbl as approver', 'approver.id = approver_ids.min_id', 'left')
            ->where('usage_note.tour_id', $tour_id)
            ->groupBy('usage_note.usage_note_no')
            ->orderBy('usage_note.date', 'DESC')
            ->findAll();

        $tickets = array_map(function ($n) {
            return [
                'usage_note_no'     => $n['usage_note_no'],
                'date'              => $n['date'],
                'status'            => $n['status'],
                'item_names'        => $n['item_names'] ?? '-',
                'item_count'        => (int) $n['item_count'],
                'total_quantity'    => (int) $n['total_quantity'],
                'created_by'        => $n['created_by_name'] ?? $n['created_by'],
                'approved_by'       => $n['approved_by_name'] ?? $n['approved_by'],
                'approved_at'       => $n['approved_at'],
                'rejection_reason'  => $n['rejection_reason'],
            ];
        }, $ticketNotes);

        return [
            'tour_info'         => $tourInfo,
            'tour_status'       => $tourStatus,
            'allocated_drivers' => $allocated_drivers,
            'payment_rows'      => $payment_rows,
            'tickets'           => $tickets,
            'categories'   => [
                'hotels'             => ['total' => $hotel_total,            'paid' => $hotel_paid,            'balance' => $hotel_total            - $hotel_paid,            'label' => 'Hotels',             'icon' => 'ri-hotel-line',      'color' => 'primary'],
                'activities'         => ['total' => $activity_total,         'paid' => $activity_paid,         'balance' => $activity_total         - $activity_paid,         'label' => 'Activities',         'icon' => 'ri-run-line',        'color' => 'success'],
                'driver_advance'     => ['total' => $driver_advance_total,   'paid' => $driver_advance_paid,   'balance' => $driver_advance_total   - $driver_advance_paid,   'label' => 'Driver Advance',     'icon' => 'ri-money-dollar-circle-line', 'color' => 'warning'],
                'driver_settlement'  => ['total' => $driver_settlement_total,'paid' => $driver_settlement_paid,'balance' => $driver_settlement_total - $driver_settlement_paid,'label' => 'Driver Settlement',  'icon' => 'ri-bus-line',        'color' => 'info'],
            ],
            'totals' => [
                'estimated'    => $total_estimated,
                'paid'         => $total_paid,
                'outstanding'  => $outstanding,
                'upcoming_due' => $upcoming_due,
            ],
        ];
    }
}
