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
use App\Models\VoucherInvoiceModel;
use App\Models\InvoiceNumberModel;
use App\Models\SupplierModel;
use App\Models\AccountPostingModel;
use App\Models\UtilityBillModel;
use App\Models\DriverMeterReadingModel;
use App\Models\TourSubLocationsModel;

class TourAccountsController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/tour/tour_accounts_note forecast report
    public function index()
    {
        $data = array();
        if ($this->request->getGet('view_all')) {
            $v_all = true;
        } else {
            $v_all = false;
        }

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();
        $today = date('Y-m-d');
        // $today = date('Y-m-d', strtotime('2026-05-01'));

        $week3 = date('Y-m-d', strtotime($today . ' + 20 days'));
        $week2 = date('Y-m-d', strtotime($today . ' + 13 days'));
        $week1 = date('Y-m-d', strtotime($today . ' + 6 days'));
        $day4 = date('Y-m-d', strtotime($today . ' + 4 days'));
        $day3 = date('Y-m-d', strtotime($today . ' + 3 days'));
        $day2 = date('Y-m-d', strtotime($today . ' + 2 days'));
        $day1 = date('Y-m-d', strtotime($today . ' + 1 days'));

        $consigneeModel = new ConsigneeModel();

        // Date range filter — use GET params if provided, otherwise use the default forecast window
        $filterStartDate = $this->request->getGet('start_date');
        $filterEndDate   = $this->request->getGet('end_date');

        $data['filter_start_date'] = $filterStartDate;
        $data['filter_end_date']   = $filterEndDate;
        $data['hideOperationsButtons'] = true;;

        if (!empty($filterStartDate) && !empty($filterEndDate)) {
            $minDate = date('Y-m-d', strtotime($filterStartDate));
            $maxDate = date('Y-m-d', strtotime($filterEndDate));
        } else {
            $dateRanges = [
                'threeweeks' => [$week2, $week3, '>', '<='],
                'twoweeks' => [$week1, $week2, '>', '<='],
                'oneweek' => [$day4, $week1, '>=', '<='],
                'threedays' => [$day2, $day3, '>=', '<='],
                'twodays' => [$day1, $day2, '>=', '<='],
                'oneday' => [$day1, $day1, '=', '<='],
                'consignee' => [date('Y-m-d', strtotime($today)), date('Y-m-d', strtotime($today)), '=', '=']
            ];

            $allDates = [];
            foreach ($dateRanges as $range) {
                $allDates[] = $range[0];
                $allDates[] = $range[1];
            }

            $minDate = min($allDates);
            $maxDate = max($allDates);
        }

        $data['consignee'] = $consigneeModel
            ->select('
    consignee.*,
    supplier.Name as sup_name,
    supplier.Address as address,
    supplier.TP as TP,
    supplier.bank_name,
    supplier.sup_bank_acc,
    supplier.branch_name,
    debtor_invoice_log.invoice_no,
    consignee.inv_alert_status
    ')
            ->join('supplier', 'consignee.transport_agent = supplier.Sup_Acc_No', 'left')
            ->join('debtor_invoice_log', 'consignee.tour_id = debtor_invoice_log.tour_id', 'left')
            ->where('consignee.verify', 1)
            ->where('consignee.start_date >=', $minDate)
            ->where('consignee.start_date <=', $maxDate)
            ->where('consignee.is_template', 0)
            ->where('consignee.tour_cancel', 0)
            ->where('debtor_invoice_log.cancel', 0);

        if ($v_all == false) {
            $consigneeModel->where('consignee.payment_complete', 0);
        }

        $data['consignee'] = $consigneeModel
            ->orderBy('consignee.start_date', 'ASC')
            ->findAll();

        // log_message('critical',$consigneeModel->getLastQuery());

        // Get pending tour count (distinct tours with sent pending expenses) for the button badge
        $driverExpensesModel = new DriverExpensesModel();
        $pendingTourCount = $driverExpensesModel->builder()
            ->select('tour_id')
            ->where('request_send', 1)
            ->where('status', 'pending')
            ->groupBy('tour_id')
            ->countAllResults();
        $data['pending_expenses_count'] = $pendingTourCount;

        $accountrunningModel = new AccountRunningModel();
        $voucherpaymentmodel = new VoucherPaymentModel();

        // Iterate through consignees to append additional data
        foreach ($data['consignee'] as &$consignee) {
            $tour_id = $consignee['tour_id']; // Adjust key based on your data
            $agent = $consignee['transport_agent']; // Adjust key based on your data
            $agent_name = $consignee['agent_name']; // Adjust key based on your data


            $logsheetmidemodel = new LogSheetMileModel();
            $rowCount = $logsheetmidemodel
                ->where('tour_id', $tour_id)
                ->where('transport_agent', $agent)
                ->countAllResults();

            if ($rowCount > 0) {
                $row = $logsheetmidemodel
                    ->where('tour_id', $tour_id)
                    ->where('transport_agent', $agent)
                    ->first();

                $consignee['total_transport_cost'] = ($row['log_sheet_mileage'] * $row['vehicle_rate']) + ($row['per_day_rate'] * $row['no_of_dates']);
            }

            // Fetch advance payment
            // $advance_payment = $accountrunningModel->select('sum(Dr) as advance_dr')
            // ->where('index_no', $tour_id)
            // ->where('account_runing.L_Code', $agent)
            // ->like('Discription', 'VOUCHER PAYMENT - Voucher Payment - Transport%')
            // ->where('account_runing.advances_pay', 1)
            // ->first();
            $advance_payment = $voucherpaymentmodel->select('sum(Amount) as advance_dr')->where('ref_po', $tour_id)->where('Acc_No', $agent)->where('Aflag', 1)->where('advances_pay', 1)->first();

            // Fetch total settled voucher
            $total_settled_voucher = $accountrunningModel->select('sum(account_runing.Cr) as total_pay_cr')
                ->where('index_no', $tour_id)
                ->where('payee', $agent_name)
                ->where('account_runing.Cr >', 0.00)
                ->like('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->where('account_runing.advances_pay', 11)
                ->first();

            // Fetch total expenses
            $total_expenses = $accountrunningModel->select('sum(account_runing.Cr) as expenses_cr')
                ->where('index_no', $tour_id)
                ->where('L_Code', $agent)
                ->where('account_runing.Cr >', 0.00)
                ->notLike('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->where('account_runing.advances_pay', 111)
                ->first();

            $DriveradvanceRequestModel = new DriverAdvanceRequestModel();
            $advance_payment_requests = $DriveradvanceRequestModel->where('tour_id', $tour_id)->where('flag', 0)->groupBy('id')->findAll();

            $driverallocationmodel = new DriverAllocationModel();
            $driverallocationdata = $driverallocationmodel->where('tour_id', $tour_id)->findAll();

            // log_message('critical', $DriveradvanceRequestModel->getLastQuery());

            // Add additional data to consignee
            $consignee['advance_dr'] = $advance_payment['advance_dr'] ?? 0;
            $consignee['total_pay_cr'] = $total_settled_voucher['total_pay_cr'] ?? 0;
            $consignee['expenses_cr'] = $total_expenses['expenses_cr'] ?? 0;
            $consignee['advance_payment_requests'] = $advance_payment_requests ?? [];
            $consignee['driverallocationdata'] = $driverallocationdata ?? [];
        }

        // Load hotel details from the tour_quotations table for each tour
        $tourQuotationModel = new TourQuotationsModel(); // Assuming you have a TourQuotationModel
        foreach ($data['consignee'] as &$tour) {
            // Whether this tour has ANY hotel booked at all (unfiltered by payment_request/
            // payment_complete) — used only to tell "no hotel component" apart from "hotels
            // exist but are all already requested/completed" for the empty-state message below.
            $tour['has_any_hotel'] = $tourQuotationModel
                ->where('tour_quotations.quotation_id', $tour['tour_id'])
                ->where('tour_quotations.hotel_acc !=', '')
                ->where('tour_quotations.confirm !=', 3)
                ->countAllResults() > 0;

            // Assuming 'tour_id' in the consignee table is the foreign key to link with tour_quotations table
            $tour['hotels'] = $tourQuotationModel
                ->select('
            tour_quotations.hotel_name,
            tour_quotations.hotel_acc,
            MAX(tour_quotations.hotel_advance) as hotel_advance,
            MAX(tour_quotations.pay_doller_count) as pay_doller_count,
            MAX(tour_quotations.hotel_dollar_rate) as hotel_dollar_rate,
            MAX(tour_quotations.total_hotel_doller) as total_hotel_doller,
            SUM(tour_quotations.sgl) as sgl,
            SUM(tour_quotations.dbl) as dbl,
            SUM(tour_quotations.tpl) as tpl,
            SUM(tour_quotations.quotation_day) as quotation_day,
            MAX(tour_quotations.payment_complete) as payment_complete,
            MAX(tour_quotations.payment_request) as payment_request,
            MAX(tour_quotations.hotel_payment_flag) as hotel_payment_flag,
            MAX(supplier.bank_name) AS bank_name,
            MAX(supplier.sup_bank_acc) AS sup_bank_acc,
            MAX(supplier.branch_name) AS branch_name,
            MAX(tour_quotations.tour_date) AS tour_date,
            MAX(tour_quotations.confirm) AS confirm,
            ')
                ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc', 'left')
                ->where('tour_quotations.quotation_id', $tour['tour_id'])
                // ->where('tour_quotations.hotel_payment_flag', 1)
                ->where('tour_quotations.hotel_acc !=', '')
                ->where('tour_quotations.confirm !=', 3);

            if ($v_all == false) {
                $tourQuotationModel->where('tour_quotations.payment_complete !=', 1);
            }

            // Hide hotel rows that already have a payment request raised
            $tourQuotationModel->where('tour_quotations.payment_request !=', 1);

            $tour['hotels'] = $tourQuotationModel
                ->groupBy('tour_quotations.hotel_name, tour_quotations.hotel_acc')
                ->orderBy('tour_quotations.tour_date', 'ASC')
                ->findAll();

            // Attach requested amount from payment_request table for each hotel
            $db = \Config\Database::connect();
            // Hotel cost handoff: what the Hotel Rates panel says we PAY each hotel
            // (ad hoc rate → contract rate → quoted rate flagged), as stored when the
            // tour was confirmed / last recalculated. Empty when nothing is stored yet.
            $hr2Costs = [];
            try {
                $hr2Costs = (new \App\Libraries\HotelRates\HotelCostService($db, (string) session()->get('username')))->summary((string) $tour['tour_id'])['hotels'];
                    $hr2Costs = array_map(static fn ($hc) => \App\Libraries\HotelRates\HotelCostService::forViewer($hc, \App\Libraries\HotelRates\OfferRates::currentUserCanDecide()), $hr2Costs);   // tf-offers-r1: special rates for authorized sign-ins only
            } catch (\Throwable $e) {
                $hr2Costs = [];
            }
            foreach ($tour['hotels'] as &$hotel) {
                $pr = $db->table('payment_request')
                    ->select('requestAmount, payment_request AS pr_status')
                    ->where('tour_id', $tour['tour_id'])
                    ->where('hotel_acc', $hotel['hotel_acc'])
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get()->getRowArray();
                $hotel['requested_amount'] = $pr['requestAmount'] ?? null;
                $hotel['pr_status']        = $pr['pr_status'] ?? null;
                $hotel['hr2_cost']         = $hr2Costs[$hotel['hotel_acc']] ?? null;
            }
            unset($hotel);

            // Load direct payment (lunch) activities for this tour — hide ones already requested
            $tour['lunch_payments'] = $db->table('tour_activity')
                ->select('
                    tour_activity.id,
                    tour_activity.tour_date,
                    tour_activity.fee,
                    tour_activity.lkr_fee,
                    tour_activity.supplier_id,
                    tour_activity.supplier_name,
                    tour_activity.payment_request,
                    supplier.Name AS sup_name,
                    supplier.bank_name,
                    supplier.sup_bank_acc,
                    supplier.branch_name,
                    supplier.TP,
                    supplier.Mobile
                ')
                ->join('supplier', 'supplier.Sup_Acc_No = tour_activity.supplier_id', 'left')
                ->where('tour_activity.tour_id', $tour['tour_id'])
                ->where('tour_activity.supplier_id IS NOT NULL', null, false)
                ->where('tour_activity.supplier_id !=', '')
                ->where('tour_activity.payment_request !=', 1)
                ->orderBy('tour_activity.tour_date', 'ASC')
                ->get()->getResultArray();

            // Attach requested amount for each lunch/activity
            foreach ($tour['lunch_payments'] as &$lunch) {
                $pr = $db->table('payment_request')
                    ->select('requestAmount, payment_request AS pr_status')
                    ->where('tour_id', $tour['tour_id'])
                    ->where('hotel_acc', $lunch['supplier_id'])
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get()->getRowArray();
                $lunch['requested_amount'] = $pr['requestAmount'] ?? null;
                $lunch['pr_status']        = $pr['pr_status'] ?? null;
            }
            unset($lunch);

            // log_message('critical',$tourQuotationModel->getLastQuery());
        }

        // Get approved special payment requests with FULL tour details
        $specialPaymentRequestModel = new SpecialPaymentRequestModel();
        $approved_special_tour_ids = $specialPaymentRequestModel
            ->select('tour_id')
            ->where('status', 'approved')
            ->findColumn('tour_id');

        $data['special_consignee'] = [];

        if (!empty($approved_special_tour_ids)) {
            // Fetch complete consignee data for approved special requests
            $data['special_consignee'] = $consigneeModel
                ->select('
    consignee.*,
    supplier.Name as sup_name,
    supplier.Address as address,
    supplier.TP as TP,
    supplier.bank_name,
    supplier.sup_bank_acc,
    supplier.branch_name,
    debtor_invoice_log.invoice_no,
    consignee.inv_alert_status
    ')
                ->join('supplier', 'consignee.transport_agent = supplier.Sup_Acc_No', 'left')
                ->join('debtor_invoice_log', 'consignee.tour_id = debtor_invoice_log.tour_id', 'left')
                ->whereIn('consignee.tour_id', $approved_special_tour_ids)
                ->where('consignee.verify', 1)
                ->where('consignee.is_template', 0)
                ->where('consignee.tour_cancel', 0)
                ->where('debtor_invoice_log.cancel', 0)
                ->orderBy('consignee.start_date', 'ASC')
                ->findAll();

            // Process additional data for each special tour (same as main forecast)
            foreach ($data['special_consignee'] as &$consignee) {
                $tour_id = $consignee['tour_id'];
                $agent = $consignee['transport_agent'];
                $agent_name = $consignee['agent_name'];

                $logsheetmidemodel = new LogSheetMileModel();
                $rowCount = $logsheetmidemodel
                    ->where('tour_id', $tour_id)
                    ->where('transport_agent', $agent)
                    ->countAllResults();

                if ($rowCount > 0) {
                    $row = $logsheetmidemodel
                        ->where('tour_id', $tour_id)
                        ->where('transport_agent', $agent)
                        ->first();

                    $consignee['total_transport_cost'] = ($row['log_sheet_mileage'] * $row['vehicle_rate']) + ($row['per_day_rate'] * $row['no_of_dates']);
                }

                $advance_payment = $voucherpaymentmodel->select('sum(Amount) as advance_dr')->where('ref_po', $tour_id)->where('Acc_No', $agent)->where('Aflag', 1)->where('advances_pay', 1)->first();

                $total_settled_voucher = $accountrunningModel->select('sum(account_runing.Cr) as total_pay_cr')
                    ->where('index_no', $tour_id)
                    ->where('payee', $agent_name)
                    ->where('account_runing.Cr >', 0.00)
                    ->like('account_runing.Discription', 'VOUCHER PAYMENT%')
                    ->where('account_runing.advances_pay', 11)
                    ->first();

                $total_expenses = $accountrunningModel->select('sum(account_runing.Cr) as expenses_cr')
                    ->where('index_no', $tour_id)
                    ->where('L_Code', $agent)
                    ->where('account_runing.Cr >', 0.00)
                    ->notLike('account_runing.Discription', 'VOUCHER PAYMENT%')
                    ->where('account_runing.advances_pay', 111)
                    ->first();

                $DriveradvanceRequestModel = new DriverAdvanceRequestModel();
                $advance_payment_requests = $DriveradvanceRequestModel->where('tour_id', $tour_id)->where('flag', 0)->groupBy('id')->findAll();

                $consignee['advance_dr'] = $advance_payment['advance_dr'] ?? 0;
                $consignee['total_pay_cr'] = $total_settled_voucher['total_pay_cr'] ?? 0;
                $consignee['expenses_cr'] = $total_expenses['expenses_cr'] ?? 0;
                $consignee['advance_payment_requests'] = $advance_payment_requests ?? [];
            }

            // Load hotel details for each special tour
            $tourQuotationModel = new TourQuotationsModel();
            foreach ($data['special_consignee'] as &$tour) {
                $tour['hotels'] = $tourQuotationModel
                    ->select('
            tour_quotations.hotel_name,
            tour_quotations.hotel_acc,
            MAX(tour_quotations.hotel_advance) as hotel_advance,
            MAX(tour_quotations.pay_doller_count) as pay_doller_count,
            MAX(tour_quotations.hotel_dollar_rate) as hotel_dollar_rate,
            MAX(tour_quotations.total_hotel_doller) as total_hotel_doller,
            SUM(tour_quotations.sgl) as sgl,
            SUM(tour_quotations.dbl) as dbl,
            SUM(tour_quotations.tpl) as tpl,
            SUM(tour_quotations.quotation_day) as quotation_day,
            MAX(tour_quotations.payment_complete) as payment_complete,
            MAX(tour_quotations.payment_request) as payment_request,
            MAX(tour_quotations.hotel_payment_flag) as hotel_payment_flag,
            MAX(supplier.bank_name) AS bank_name,
            MAX(supplier.sup_bank_acc) AS sup_bank_acc,
            MAX(supplier.branch_name) AS branch_name,
            MAX(tour_quotations.tour_date) AS tour_date,
            MAX(tour_quotations.confirm) AS confirm,
            ')
                    ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc', 'left')
                    ->where('tour_quotations.quotation_id', $tour['tour_id'])
                    ->where('tour_quotations.hotel_acc !=', '')
                    ->where('tour_quotations.confirm !=', 3)
                    ->groupBy('tour_quotations.hotel_name, tour_quotations.hotel_acc')
                    ->orderBy('tour_quotations.tour_date', 'ASC')
                    ->findAll();

                $db2 = \Config\Database::connect();
                // Hotel cost handoff: what the Hotel Rates panel says we PAY each hotel
                // (ad hoc rate → contract rate → quoted rate flagged), as stored when the
                // tour was confirmed / last recalculated. Empty when nothing is stored yet.
                $hr2Costs = [];
                try {
                    $hr2Costs = (new \App\Libraries\HotelRates\HotelCostService($db2, (string) session()->get('username')))->summary((string) $tour['tour_id'])['hotels'];
                    $hr2Costs = array_map(static fn ($hc) => \App\Libraries\HotelRates\HotelCostService::forViewer($hc, \App\Libraries\HotelRates\OfferRates::currentUserCanDecide()), $hr2Costs);   // tf-offers-r1: special rates for authorized sign-ins only
                } catch (\Throwable $e) {
                    $hr2Costs = [];
                }
                foreach ($tour['hotels'] as &$hotel) {
                    $pr = $db2->table('payment_request')
                        ->select('requestAmount, payment_request AS pr_status')
                        ->where('tour_id', $tour['tour_id'])
                        ->where('hotel_acc', $hotel['hotel_acc'])
                        ->orderBy('id', 'DESC')
                        ->limit(1)
                        ->get()->getRowArray();
                    $hotel['requested_amount'] = $pr['requestAmount'] ?? null;
                    $hotel['pr_status']        = $pr['pr_status'] ?? null;
                    $hotel['hr2_cost']         = $hr2Costs[$hotel['hotel_acc']] ?? null;
                }
                unset($hotel);
            }
        }

        return view('pages/tour/tour_accounts_note', $data);
    }

    // route : base_url/tour/tour_accounts/all
    // Same forecast report as index(), but shows every hotel/lunch row regardless of
    // whether a payment request has already been raised for it (index() hides those).
    public function indexAll()
    {
        $data = array();
        if ($this->request->getGet('view_all')) {
            $v_all = true;
        } else {
            $v_all = false;
        }

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();
        $today = date('Y-m-d');

        $week3 = date('Y-m-d', strtotime($today . ' + 20 days'));
        $week2 = date('Y-m-d', strtotime($today . ' + 13 days'));
        $week1 = date('Y-m-d', strtotime($today . ' + 6 days'));
        $day4 = date('Y-m-d', strtotime($today . ' + 4 days'));
        $day3 = date('Y-m-d', strtotime($today . ' + 3 days'));
        $day2 = date('Y-m-d', strtotime($today . ' + 2 days'));
        $day1 = date('Y-m-d', strtotime($today . ' + 1 days'));

        $consigneeModel = new ConsigneeModel();

        // Date range filter — use GET params if provided, otherwise use the default forecast window
        $filterStartDate = $this->request->getGet('start_date');
        $filterEndDate   = $this->request->getGet('end_date');

        $data['filter_start_date'] = $filterStartDate;
        $data['filter_end_date']   = $filterEndDate;

        if (!empty($filterStartDate) && !empty($filterEndDate)) {
            $minDate = date('Y-m-d', strtotime($filterStartDate));
            $maxDate = date('Y-m-d', strtotime($filterEndDate));
        } else {
            $dateRanges = [
                'threeweeks' => [$week2, $week3, '>', '<='],
                'twoweeks' => [$week1, $week2, '>', '<='],
                'oneweek' => [$day4, $week1, '>=', '<='],
                'threedays' => [$day2, $day3, '>=', '<='],
                'twodays' => [$day1, $day2, '>=', '<='],
                'oneday' => [$day1, $day1, '=', '<='],
                'consignee' => [date('Y-m-d', strtotime($today)), date('Y-m-d', strtotime($today)), '=', '=']
            ];

            $allDates = [];
            foreach ($dateRanges as $range) {
                $allDates[] = $range[0];
                $allDates[] = $range[1];
            }

            $minDate = min($allDates);
            $maxDate = max($allDates);
        }

        $data['consignee'] = $consigneeModel
            ->select('
    consignee.*,
    supplier.Name as sup_name,
    supplier.Address as address,
    supplier.TP as TP,
    supplier.bank_name,
    supplier.sup_bank_acc,
    supplier.branch_name,
    debtor_invoice_log.invoice_no,
    consignee.inv_alert_status
    ')
            ->join('supplier', 'consignee.transport_agent = supplier.Sup_Acc_No', 'left')
            ->join('debtor_invoice_log', 'consignee.tour_id = debtor_invoice_log.tour_id', 'left')
            ->where('consignee.verify', 1)
            ->where('consignee.start_date >=', $minDate)
            ->where('consignee.start_date <=', $maxDate)
            ->where('consignee.is_template', 0)
            ->where('consignee.tour_cancel', 0)
            ->where('debtor_invoice_log.cancel', 0);

        if ($v_all == false) {
            $consigneeModel->where('consignee.payment_complete', 0);
        }

        $data['consignee'] = $consigneeModel
            ->orderBy('consignee.start_date', 'ASC')
            ->findAll();

        // Get pending tour count (distinct tours with sent pending expenses) for the button badge
        $driverExpensesModel = new DriverExpensesModel();
        $pendingTourCount = $driverExpensesModel->builder()
            ->select('tour_id')
            ->where('request_send', 1)
            ->where('status', 'pending')
            ->groupBy('tour_id')
            ->countAllResults();
        $data['pending_expenses_count'] = $pendingTourCount;

        $accountrunningModel = new AccountRunningModel();
        $voucherpaymentmodel = new VoucherPaymentModel();

        // Iterate through consignees to append additional data
        foreach ($data['consignee'] as &$consignee) {
            $tour_id = $consignee['tour_id'];
            $agent = $consignee['transport_agent'];
            $agent_name = $consignee['agent_name'];

            $logsheetmidemodel = new LogSheetMileModel();
            $rowCount = $logsheetmidemodel
                ->where('tour_id', $tour_id)
                ->where('transport_agent', $agent)
                ->countAllResults();

            if ($rowCount > 0) {
                $row = $logsheetmidemodel
                    ->where('tour_id', $tour_id)
                    ->where('transport_agent', $agent)
                    ->first();

                $consignee['total_transport_cost'] = ($row['log_sheet_mileage'] * $row['vehicle_rate']) + ($row['per_day_rate'] * $row['no_of_dates']);
            }

            $advance_payment = $voucherpaymentmodel->select('sum(Amount) as advance_dr')->where('ref_po', $tour_id)->where('Acc_No', $agent)->where('Aflag', 1)->where('advances_pay', 1)->first();

            $total_settled_voucher = $accountrunningModel->select('sum(account_runing.Cr) as total_pay_cr')
                ->where('index_no', $tour_id)
                ->where('payee', $agent_name)
                ->where('account_runing.Cr >', 0.00)
                ->like('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->where('account_runing.advances_pay', 11)
                ->first();

            $total_expenses = $accountrunningModel->select('sum(account_runing.Cr) as expenses_cr')
                ->where('index_no', $tour_id)
                ->where('L_Code', $agent)
                ->where('account_runing.Cr >', 0.00)
                ->notLike('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->where('account_runing.advances_pay', 111)
                ->first();

            $DriveradvanceRequestModel = new DriverAdvanceRequestModel();
            $advance_payment_requests = $DriveradvanceRequestModel->where('tour_id', $tour_id)->where('flag', 0)->groupBy('id')->findAll();

            $driverallocationmodel = new DriverAllocationModel();
            $driverallocationdata = $driverallocationmodel->where('tour_id', $tour_id)->findAll();

            // Add additional data to consignee
            $consignee['advance_dr'] = $advance_payment['advance_dr'] ?? 0;
            $consignee['total_pay_cr'] = $total_settled_voucher['total_pay_cr'] ?? 0;
            $consignee['expenses_cr'] = $total_expenses['expenses_cr'] ?? 0;
            $consignee['advance_payment_requests'] = $advance_payment_requests ?? [];
            $consignee['driverallocationdata'] = $driverallocationdata ?? [];
        }

        // Load hotel details from the tour_quotations table for each tour — no
        // payment_request filter here, so already-requested rows still show.
        $tourQuotationModel = new TourQuotationsModel();
        foreach ($data['consignee'] as &$tour) {
            $tour['hotels'] = $tourQuotationModel
                ->select('
            tour_quotations.hotel_name,
            tour_quotations.hotel_acc,
            MAX(tour_quotations.hotel_advance) as hotel_advance,
            MAX(tour_quotations.pay_doller_count) as pay_doller_count,
            MAX(tour_quotations.hotel_dollar_rate) as hotel_dollar_rate,
            MAX(tour_quotations.total_hotel_doller) as total_hotel_doller,
            SUM(tour_quotations.sgl) as sgl,
            SUM(tour_quotations.dbl) as dbl,
            SUM(tour_quotations.tpl) as tpl,
            SUM(tour_quotations.quotation_day) as quotation_day,
            MAX(tour_quotations.payment_complete) as payment_complete,
            MAX(tour_quotations.payment_request) as payment_request,
            MAX(tour_quotations.hotel_payment_flag) as hotel_payment_flag,
            MAX(supplier.bank_name) AS bank_name,
            MAX(supplier.sup_bank_acc) AS sup_bank_acc,
            MAX(supplier.branch_name) AS branch_name,
            MAX(tour_quotations.tour_date) AS tour_date,
            MAX(tour_quotations.confirm) AS confirm,
            ')
                ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc', 'left')
                ->where('tour_quotations.quotation_id', $tour['tour_id'])
                ->where('tour_quotations.hotel_acc !=', '')
                ->where('tour_quotations.confirm !=', 3);

            if ($v_all == false) {
                $tourQuotationModel->where('tour_quotations.payment_complete !=', 1);
            }

            $tour['hotels'] = $tourQuotationModel
                ->groupBy('tour_quotations.hotel_name, tour_quotations.hotel_acc')
                ->orderBy('tour_quotations.tour_date', 'ASC')
                ->findAll();

            // Attach requested amount from payment_request table for each hotel
            $db = \Config\Database::connect();
            // Hotel cost handoff: what the Hotel Rates panel says we PAY each hotel
            // (ad hoc rate → contract rate → quoted rate flagged), as stored when the
            // tour was confirmed / last recalculated. Empty when nothing is stored yet.
            $hr2Costs = [];
            try {
                $hr2Costs = (new \App\Libraries\HotelRates\HotelCostService($db, (string) session()->get('username')))->summary((string) $tour['tour_id'])['hotels'];
                    $hr2Costs = array_map(static fn ($hc) => \App\Libraries\HotelRates\HotelCostService::forViewer($hc, \App\Libraries\HotelRates\OfferRates::currentUserCanDecide()), $hr2Costs);   // tf-offers-r1: special rates for authorized sign-ins only
            } catch (\Throwable $e) {
                $hr2Costs = [];
            }
            foreach ($tour['hotels'] as &$hotel) {
                $pr = $db->table('payment_request')
                    ->select('requestAmount, payment_request AS pr_status')
                    ->where('tour_id', $tour['tour_id'])
                    ->where('hotel_acc', $hotel['hotel_acc'])
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get()->getRowArray();
                $hotel['requested_amount'] = $pr['requestAmount'] ?? null;
                $hotel['pr_status']        = $pr['pr_status'] ?? null;
                $hotel['hr2_cost']         = $hr2Costs[$hotel['hotel_acc']] ?? null;
            }
            unset($hotel);

            // Load direct payment (lunch) activities for this tour — no requested-filter
            $tour['lunch_payments'] = $db->table('tour_activity')
                ->select('
                    tour_activity.id,
                    tour_activity.tour_date,
                    tour_activity.fee,
                    tour_activity.lkr_fee,
                    tour_activity.supplier_id,
                    tour_activity.supplier_name,
                    tour_activity.payment_request,
                    supplier.Name AS sup_name,
                    supplier.bank_name,
                    supplier.sup_bank_acc,
                    supplier.branch_name,
                    supplier.TP,
                    supplier.Mobile
                ')
                ->join('supplier', 'supplier.Sup_Acc_No = tour_activity.supplier_id', 'left')
                ->where('tour_activity.tour_id', $tour['tour_id'])
                ->where('tour_activity.supplier_id IS NOT NULL', null, false)
                ->where('tour_activity.supplier_id !=', '')
                ->orderBy('tour_activity.tour_date', 'ASC')
                ->get()->getResultArray();

            // Attach requested amount for each lunch/activity
            foreach ($tour['lunch_payments'] as &$lunch) {
                $pr = $db->table('payment_request')
                    ->select('requestAmount, payment_request AS pr_status')
                    ->where('tour_id', $tour['tour_id'])
                    ->where('hotel_acc', $lunch['supplier_id'])
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get()->getRowArray();
                $lunch['requested_amount'] = $pr['requestAmount'] ?? null;
                $lunch['pr_status']        = $pr['pr_status'] ?? null;
            }
            unset($lunch);
        }

        // Get approved special payment requests with FULL tour details
        $specialPaymentRequestModel = new SpecialPaymentRequestModel();
        $approved_special_tour_ids = $specialPaymentRequestModel
            ->select('tour_id')
            ->where('status', 'approved')
            ->findColumn('tour_id');

        $data['special_consignee'] = [];

        if (!empty($approved_special_tour_ids)) {
            // Fetch complete consignee data for approved special requests
            $data['special_consignee'] = $consigneeModel
                ->select('
    consignee.*,
    supplier.Name as sup_name,
    supplier.Address as address,
    supplier.TP as TP,
    supplier.bank_name,
    supplier.sup_bank_acc,
    supplier.branch_name,
    debtor_invoice_log.invoice_no,
    consignee.inv_alert_status
    ')
                ->join('supplier', 'consignee.transport_agent = supplier.Sup_Acc_No', 'left')
                ->join('debtor_invoice_log', 'consignee.tour_id = debtor_invoice_log.tour_id', 'left')
                ->whereIn('consignee.tour_id', $approved_special_tour_ids)
                ->where('consignee.verify', 1)
                ->where('consignee.is_template', 0)
                ->where('consignee.tour_cancel', 0)
                ->where('debtor_invoice_log.cancel', 0)
                ->orderBy('consignee.start_date', 'ASC')
                ->findAll();

            // Process additional data for each special tour (same as main forecast)
            foreach ($data['special_consignee'] as &$consignee) {
                $tour_id = $consignee['tour_id'];
                $agent = $consignee['transport_agent'];
                $agent_name = $consignee['agent_name'];

                $logsheetmidemodel = new LogSheetMileModel();
                $rowCount = $logsheetmidemodel
                    ->where('tour_id', $tour_id)
                    ->where('transport_agent', $agent)
                    ->countAllResults();

                if ($rowCount > 0) {
                    $row = $logsheetmidemodel
                        ->where('tour_id', $tour_id)
                        ->where('transport_agent', $agent)
                        ->first();

                    $consignee['total_transport_cost'] = ($row['log_sheet_mileage'] * $row['vehicle_rate']) + ($row['per_day_rate'] * $row['no_of_dates']);
                }

                $advance_payment = $voucherpaymentmodel->select('sum(Amount) as advance_dr')->where('ref_po', $tour_id)->where('Acc_No', $agent)->where('Aflag', 1)->where('advances_pay', 1)->first();

                $total_settled_voucher = $accountrunningModel->select('sum(account_runing.Cr) as total_pay_cr')
                    ->where('index_no', $tour_id)
                    ->where('payee', $agent_name)
                    ->where('account_runing.Cr >', 0.00)
                    ->like('account_runing.Discription', 'VOUCHER PAYMENT%')
                    ->where('account_runing.advances_pay', 11)
                    ->first();

                $total_expenses = $accountrunningModel->select('sum(account_runing.Cr) as expenses_cr')
                    ->where('index_no', $tour_id)
                    ->where('L_Code', $agent)
                    ->where('account_runing.Cr >', 0.00)
                    ->notLike('account_runing.Discription', 'VOUCHER PAYMENT%')
                    ->where('account_runing.advances_pay', 111)
                    ->first();

                $DriveradvanceRequestModel = new DriverAdvanceRequestModel();
                $advance_payment_requests = $DriveradvanceRequestModel->where('tour_id', $tour_id)->where('flag', 0)->groupBy('id')->findAll();

                $consignee['advance_dr'] = $advance_payment['advance_dr'] ?? 0;
                $consignee['total_pay_cr'] = $total_settled_voucher['total_pay_cr'] ?? 0;
                $consignee['expenses_cr'] = $total_expenses['expenses_cr'] ?? 0;
                $consignee['advance_payment_requests'] = $advance_payment_requests ?? [];
            }

            // Load hotel details for each special tour
            $tourQuotationModel = new TourQuotationsModel();
            foreach ($data['special_consignee'] as &$tour) {
                $tour['hotels'] = $tourQuotationModel
                    ->select('
            tour_quotations.hotel_name,
            tour_quotations.hotel_acc,
            MAX(tour_quotations.hotel_advance) as hotel_advance,
            MAX(tour_quotations.pay_doller_count) as pay_doller_count,
            MAX(tour_quotations.hotel_dollar_rate) as hotel_dollar_rate,
            MAX(tour_quotations.total_hotel_doller) as total_hotel_doller,
            SUM(tour_quotations.sgl) as sgl,
            SUM(tour_quotations.dbl) as dbl,
            SUM(tour_quotations.tpl) as tpl,
            SUM(tour_quotations.quotation_day) as quotation_day,
            MAX(tour_quotations.payment_complete) as payment_complete,
            MAX(tour_quotations.payment_request) as payment_request,
            MAX(tour_quotations.hotel_payment_flag) as hotel_payment_flag,
            MAX(supplier.bank_name) AS bank_name,
            MAX(supplier.sup_bank_acc) AS sup_bank_acc,
            MAX(supplier.branch_name) AS branch_name,
            MAX(tour_quotations.tour_date) AS tour_date,
            MAX(tour_quotations.confirm) AS confirm,
            ')
                    ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc', 'left')
                    ->where('tour_quotations.quotation_id', $tour['tour_id'])
                    ->where('tour_quotations.hotel_acc !=', '')
                    ->where('tour_quotations.confirm !=', 3)
                    ->groupBy('tour_quotations.hotel_name, tour_quotations.hotel_acc')
                    ->orderBy('tour_quotations.tour_date', 'ASC')
                    ->findAll();

                $db2 = \Config\Database::connect();
                foreach ($tour['hotels'] as &$hotel) {
                    $pr = $db2->table('payment_request')
                        ->select('requestAmount, payment_request AS pr_status')
                        ->where('tour_id', $tour['tour_id'])
                        ->where('hotel_acc', $hotel['hotel_acc'])
                        ->orderBy('id', 'DESC')
                        ->limit(1)
                        ->get()->getRowArray();
                    $hotel['requested_amount'] = $pr['requestAmount'] ?? null;
                    $hotel['pr_status']        = $pr['pr_status'] ?? null;
                }
                unset($hotel);
            }
        }

        return view('pages/tour/tour_accounts_note_all', $data);
    }


    public function update()
    {

        $tour_id = urldecode($this->request->getVar('tourId'));
        $hotelAcc = urldecode($this->request->getVar('hotelAcc'));

        // Load the model
        $TourQuotationsModel = new TourQuotationsModel();

        // Attempt to update records where Aflag = '1'
        try {
            // Perform the update
            $affectedRows = $TourQuotationsModel->where('quotation_id', $tour_id)->where('hotel_acc', $hotelAcc)
                ->set([
                    'payment_complete' => 1,
                    'payment_complete_user' => session()->get('username'),
                    'payment_complete_date' => date('Y-m-d H:i:s') // Correctly using PHP's date() function
                ])
                ->update();


            // Check if any rows were updated
            if ($affectedRows > 0) {
                // If successful, return a success message
                return json_encode(['status' => 200, 'message' => 'Payment Request saved successfully']);
            } else {
                // If no rows were updated, return a different message
                return json_encode(['status' => 404, 'message' => 'No records found to update']);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and return an error message
            return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function payment_request()
    {
        // tf-paydesk-r7e: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'payment_request')) {
            return $tfCsrf;
        }
        // tf-offers-r3: the request as posted, by the person signed in (the handler itself is unchanged below)
        // tf-paydesk-r7h build 5 (V-3): one request at a time per item (tour, supplier, kind) — the duplicate check and the insert are not split
        $tfType = (string) $this->request->getVar('type');
        $tfKey = 'req|' . (string) $this->request->getVar('tourId') . '|' . (string) $this->request->getVar('hotelAcc') . '|' . ($tfType === 'Transport Agent' ? 'Transport Advance' : $tfType);
        return \App\Libraries\TourPayments\PayDesk::withItemLock(\Config\Database::connect(), $tfKey, fn () => $this->paymentRequestFrom(fn ($k) => $this->request->getVar($k), (string) session()->get('username'), true))
            ?? json_encode(['status' => 409, 'message' => 'Another request for this item is being saved at this moment. Reload the page and check before trying again. Nothing was written.']);
    }

    /**
     * tf-offers-r3 — POST master_files/offer_selection/release {hold_id, choice: published|<plan id>, amount, note}
     * The System Administrator (a holder of "Tactical / Early Bird rate selection") releases a hotel payment held for
     * the rate review: his decision is recorded (who, when, which rate), then the payment request is made exactly as the
     * prepared one would have been — the same handler, the preparer as the requester — at the final amount, and goes to
     * Payment approval. The amount may not exceed the quoted figure / the cost with the rate chosen.
     */
    public function release_rate_review()
    {
        // tf-paydesk-r7e: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'release_rate_review')) {
            return $tfCsrf;
        }
        $db = \Config\Database::connect();
        try {
            if (!\App\Libraries\HotelRates\OfferRates::currentUserCanDecide()) {
                return $this->response->setStatusCode(403)->setJSON(['status' => 403, 'message' => 'Only a holder of "Tactical / Early Bird rate selection" can release a hotel payment held for the rate review.']);
            }
            $b = $this->request->getJSON(true);
            $b = is_array($b) ? $b : ($this->request->getPost() ?: []);
            $hold = \App\Libraries\HotelRates\PaymentHold::get($db, (int) ($b['hold_id'] ?? 0));
            if (!$hold || $hold['status'] !== 'awaiting') {
                return $this->response->setJSON(['status' => 400, 'message' => 'That hotel payment is not waiting for a rate review any more.']);
            }
            $choice = trim((string) ($b['choice'] ?? ''));
            $amount = round((float) ($b['amount'] ?? 0), 2);
            $note = isset($b['note']) ? (string) $b['note'] : null;
            if ($choice === '' || $amount <= 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Choose the rate and enter the final amount to pay the hotel.']);
            }
            $ceiling = \App\Libraries\HotelRates\PaymentHold::ceiling($db, $hold, $choice);
            if ($ceiling === null) {
                return $this->response->setJSON(['status' => 400, 'message' => 'That rate does not fit this stay.']);
            }
            if ($amount > $ceiling + 0.005) {
                return $this->response->setJSON(['status' => 400, 'message' => 'The amount ($ ' . number_format($amount, 2) . ') cannot exceed the quoted amount / hotel cost ($ ' . number_format($ceiling, 2) . ').']);
            }
            helper('daily_usd_rate');
            if (empty(get_usd_rate())) {
                return $this->response->setJSON(['status' => 400, 'message' => "Today's USD rate hasn't been entered yet. Please set it before releasing a hotel payment."]);
            }
            $user = (string) session()->get('username');
            // the decision, with who and when; the stay's payable rows follow
            $res = (new \App\Libraries\HotelRates\OfferRates($db))->choose((string) $hold['tour_id'], (string) $hold['hotel_acc'], $choice, $note, $user);
            $payload = json_decode((string) $hold['payload'], true) ?: [];
            $payload['requestAmount'] = (string) $amount;
            // tf-paydesk-r7h build 5 (V-3): under the item's lock, as payment_request()
            $out = \App\Libraries\TourPayments\PayDesk::withItemLock($db, 'req|' . (string) ($payload['tourId'] ?? '') . '|' . (string) ($payload['hotelAcc'] ?? '') . '|Hotel',
                fn () => $this->paymentRequestFrom(static fn ($k) => $payload[$k] ?? null, (string) $hold['prepared_by'], false)) ?? json_encode(['status' => 409, 'message' => 'Another request for this item is being saved at this moment. Reload the page and check before trying again. Nothing was written.']);
            $r = json_decode((string) $out, true) ?: [];
            if ((int) ($r['status'] ?? 0) !== 200) {
                return $this->response->setJSON(['status' => (int) ($r['status'] ?? 500), 'message' => 'The rate decision is recorded, but the payment request was not made: ' . ($r['message'] ?? 'unknown error') . ' It is still waiting for release.']);
            }
            $pr = $db->query("SELECT id FROM payment_request WHERE tour_id = ? AND hotel_acc = ? AND type = 'Hotel' AND payment_request <> 3 ORDER BY id DESC LIMIT 1", [$hold['tour_id'], $hold['hotel_acc']])->getRowArray();
            $prId = $pr ? (int) $pr['id'] : null;
            \App\Libraries\HotelRates\PaymentHold::markReleased($db, (int) $hold['id'], $user, $amount, $choice, $res['contract_id'] ?? null, $prId, $note);
            (new \App\Libraries\HotelRates\OfferRates($db))->log(['tour_id' => $hold['tour_id'], 'manual_id' => $hold['manual_id'], 'hotel_acc' => $hold['hotel_acc'], 'hotel_id' => $hold['hotel_id'], 'event' => 'released',
                'contract_id' => $res['contract_id'] ?? null, 'offer_name' => $res['offer_name'] ?? null, 'nights' => (int) ($res['hotel']['nights'] ?? 0),
                'published_total' => $res['hotel']['pub_total'] ?? null, 'used_total' => $amount,
                'note' => 'Payment request' . ($prId ? ' #' . $prId : '') . ' made for ' . $hold['prepared_by'] . ' at $ ' . number_format($amount, 2), 'by_user' => $user]);
            return $this->response->setJSON(['status' => 200, 'payment_request_id' => $prId,
                'message' => 'Released to Payment approval: $ ' . number_format($amount, 2) . ' requested for ' . ($hold['hotel_name'] ?: $hold['hotel_acc']) . ' (prepared by ' . $hold['prepared_by'] . ').']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    private function paymentRequestFrom(callable $in, string $user, bool $mayHold)
    {

        // Retrieve data from AJAX request
        $tourId = $in('tourId');
        $hotelAcc = $in('hotelAcc');
        $manualId = $in('manualId');
        $consignee = $in('consignee');
        $createUser = $in('createUser');
        $startDate = $in('startDate');
        $endDate = $in('endDate');
        $hotelName = $in('hotelName');
        $bankName = $in('bankName');
        $branchName = $in('branchName');
        $supBankAcc = $in('supBankAcc');
        // tf-pilot-r2: for a sign-in that may not see a supplier's bank details (the dashboard shows them masked), or when the
        // browser sent a masked number, the request takes the bank details from the supplier master on the server — never as sent
        $bankFromMaster = str_contains((string) $supBankAcc, '•');
        try {
            $bankFromMaster = $bankFromMaster || (class_exists(\App\Libraries\TourPayments\Perms::class) && !\App\Libraries\TourPayments\Perms::bankDetails());
        } catch (\Throwable $e) {
        }
        if ($bankFromMaster) {
            $m = (new \App\Models\SupplierModel())->select('bank_name, branch_name, sup_bank_acc')->where('Sup_Acc_No', (string) $hotelAcc)->first();
            $bankName = (string) ($m['bank_name'] ?? '');     // the columns are NOT NULL: an empty master field is stored as '' (as the page sends it)
            $branchName = (string) ($m['branch_name'] ?? '');
            $supBankAcc = (string) ($m['sup_bank_acc'] ?? '');
        }
        $totalHotelPay = $in('totalHotelPay');
        $totalHotelAdvance = $in('totalHotelAdvance');
        $dueAmount = $in('dueAmount');
        $dueAmount = ($dueAmount === null || $dueAmount === '') ? 0 : $dueAmount;
        $requestAmount = $in('requestAmount');
        $tot_doller = $in('tot_amt_doller');
        $type = $in('type');

        if ($type === 'Transport Agent') {
            $type = 'Transport Advance';
            $prflag = 1;
        }
        if ($type === 'lunch') {
            $type = 'lunch';
            $prflag = 1;
        }

        if ($type === 'Hotel') {
            $type = 'Hotel';
            $prflag = 1; // Hotel requests no longer wait on a separate Hotel Confirmation step

            helper('daily_usd_rate');
            $hotelDollarRate = get_usd_rate();
            if (empty($hotelDollarRate)) {
                return json_encode(['status' => 400, 'message' => "Today's USD rate hasn't been entered yet. Please set it before requesting a hotel payment."]);
            }
        }


        $activityId = $in('activityId');

        // Block a duplicate request: any non-cancelled (payment_request != 3) row
        // already exists for this tour+supplier+type means one is already in
        // flight (requested/accepted/paid), so don't insert another.
        // tf-paydesk-r1: a request still in flight blocks another (requested, approved, vouchered but not paid); one that was
        // paid (its voucher finalised), completed, cancelled or rejected does not — the balance of a payment made in parts
        // is requested as a new request with the rate of its own day (PayDesk::openRequest)
        $existingRequest = \App\Libraries\TourPayments\PayDesk::openRequest(\Config\Database::connect(), (string) $tourId, (string) $hotelAcc, (string) $type);
        if ($existingRequest && !empty($existingRequest['tpd_review'])) {
            // tf-paydesk-r7h build 5: an association with no stored link is reviewed before any further payment of this item
            return json_encode(['status' => 409, 'review_needed' => true, 'message' => ($existingRequest['tpd_review'] === 'invalid'
                ? 'Request #' . (int) $existingRequest['id'] . ' is held: ' . $existingRequest['tpd_basis'] . '.'
                : 'Payment Voucher No. ' . implode(', ', $existingRequest['tpd_vnums']) . ' may already pay this item (' . $existingRequest['tpd_basis'] . ').')
                . ' It is not linked, so a holder of the batch privilege must review it first (Tour Payment Dashboard or Supplier Payables). Nothing was written.']);
        }
        if ($existingRequest) {
            return json_encode(['status' => 409, 'message' => 'A payment request has already been made for this item.']);
        }

        // tf-offers-r3: a hotel stay with a tactical / early bird that fits and no decision yet is not requested here —
        // it waits for the System Administrator's rate review (PaymentHold): no payment request, no accounting entry
        // until he releases it. A released request comes back through here ($mayHold = false) at its final amount.
        if ($type === 'Hotel' && $mayHold) {
            $held = \App\Libraries\HotelRates\PaymentHold::intercept(\Config\Database::connect(), (string) $tourId, (string) $hotelAcc, $in, $user);
            if ($held !== null) {
                return json_encode($held);
            }
        }

        // Attempt to update records where Aflag = '1'
        try {
            if ($type === 'lunch') {
                // Update tour_activity payment_request flag
                $TourActivityModel = new \App\Models\TourActivityModel();
                $TourActivityModel->update($activityId, [
                    'payment_request' => 1,
                    'payment_request_user' => $user,
                    'payment_request_date' => date('Y-m-d'),
                ]);
            } else {
                // Load the model
                $TourQuotationsModel = new TourQuotationsModel();
                // Perform the update
                $affectedRows = $TourQuotationsModel->where('quotation_id', $tourId)->where('hotel_acc', $hotelAcc)
                    ->set([
                        'payment_request' => 1,
                        'payment_request_user' => $user,
                        'payment_request_date' => date('Y-m-d H:i:s'),
                        // 'total_hotel_doller' => $requestAmount,
                    ])
                    ->update();
            }


            // Load the payment_request model
            $PaymentRequestModel = new PaymentRequestModel();

            // get supplier tbl has bulk_payment is 1 or not where $hotelAcc = Sup_Acc_No 
            $SupplierModel = new SupplierModel();
            $isBulk = $SupplierModel->select('bulk_payment')->where('Sup_Acc_No', $hotelAcc)->first();

            // Insert new payment request record
            $data = [
                'tour_id' => $tourId,
                'hotel_acc' => $hotelAcc,
                'manual_id' => $manualId,
                'consignee' => $consignee,
                'create_user' => $createUser,
                'start_date' => date('Y-m-d', strtotime($startDate)),
                'end_date' => date('Y-m-d', strtotime($endDate)),
                'hotel_name' => $hotelName,
                'bank_name' => $bankName,
                'branch_name' => $branchName,
                'sup_bank_acc' => $supBankAcc,
                'total_hotel_pay' => $totalHotelPay,
                'total_hotel_advance' => $totalHotelAdvance,
                'requestAmount' => $requestAmount,
                'due_amount' => $dueAmount,
                'payment_request' => $prflag,
                'payment_request_user' => $user,
                'payment_request_date' => date('Y-m-d H:i:s'),
                'type' => $type ?: null,
                'is_bulk' => $isBulk['bulk_payment'] ?? 0,
            ];

            // Insert into database
            $PaymentRequestModel->insert($data);
            $newRequestId = (int) $PaymentRequestModel->getInsertID();   // tf-payrev-r1: needed for the journal link below
            // tf-paydesk-r1: the rate locked to this request (today's, for a hotel), kept with it for good
            \App\Libraries\TourPayments\PayDesk::recordRequest(\Config\Database::connect(), $newRequestId, [
                'usd_rate' => $type === 'Hotel' ? $hotelDollarRate : null, 'rate_user' => $user,
                'invoice_no' => $in('invoiceNo') ?: null, 'booking_ref' => $in('bookingRef') ?: null, 'due_date' => $in('dueDate') ?: null, 'note' => $in('note') ?: null,
                'source_type' => $type === 'Hotel' ? 'hotel' : ($type === 'lunch' ? 'activity' : 'driver_advance'), 'source_id' => $type === 'lunch' ? $activityId : $hotelAcc,
            ]);

            // Hotel requests: write the same tour_quotations/account_runing data the old
            // Hotel Confirmation step used to write, but now at request time (no separate
            // confirmation step, no confirmation email/reference in this automated flow).
            if ($type === 'Hotel') {
                $hotelDates = $TourQuotationsModel
                    ->select('tour_date')
                    ->where('quotation_id', $tourId)
                    ->where('hotel_acc', $hotelAcc)
                    ->orderBy('tour_date', 'ASC')
                    ->findAll();
                $hotelDates = array_column($hotelDates, 'tour_date');

                $lkrPayment = (float) $requestAmount * (float) $hotelDollarRate;

                $this->confirmHotelReservation(
                    $tourId,
                    $hotelAcc,
                    $hotelDates,
                    $lkrPayment,
                    (float) $hotelDollarRate,
                    (float) $requestAmount,
                    $user,
                    null,
                    $newRequestId
                );
            }

            // Check if any rows were updated
            if ($type === 'lunch' || $affectedRows > 0) {
                // If successful, return a success message
                return json_encode(['status' => 200, 'message' => 'Payment Request saved successfully']);
            } else {
                // If no rows were updated, return a different message
                return json_encode(['status' => 404, 'message' => 'No records found to update']);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and return an error message
            return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }



    public function payment_request_final()
    {
        // tf-paydesk-r7f: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'payment_request_final')) {
            return $tfCsrf;
        }
        // tf-payauth-r1: a payment action — the payment role is checked here, not only by the menu
        if ($tfR = $this->tfPayRole(['approver', 'accountant', 'bank_approver'], 'Marking a payment request complete')) {
            return $tfR;
        }
        if ($tfR = $this->tfNotOwn([(int) $this->request->getVar('id')], 'paid')) {
            return $tfR;
        }

        // Retrieve data from AJAX request
        $id = $this->request->getVar('id');

        $PaymentRequestModel = new PaymentRequestModel();


        // Attempt to update records where Aflag = '1'
        try {
            // Perform the update
            $affectedRows = $PaymentRequestModel->where('id', $id)
                ->set([
                    'payment_request' => 2,
                    'complete_date' => date('Y-m-d H:i:s'),
                    'complete_user' => session()->get('username')
                ])
                ->update();



            // Check if any rows were updated
            if ($affectedRows > 0) {
                // If successful, return a success message
                return json_encode(['status' => 200, 'message' => 'Payment request updated successfully']);
            } else {
                // If no rows were updated, return a different message
                return json_encode(['status' => 404, 'message' => 'No records found to update']);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and return an error message
            return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // }



    public function update_transport()
    {

        $tour_id = urldecode($this->request->getVar('tourId'));


        // Load the model
        $ConsigneeModel = new ConsigneeModel();

        // Attempt to update records where Aflag = '1'
        try {
            // Perform the update
            $affectedRows = $ConsigneeModel->where('tour_id', $tour_id)
                ->set([
                    'payment_complete' => 1,
                    'payment_complete_user' => session()->get('username'),
                    'payment_complete_date' => date('Y-m-d H:i:s') // Correctly using PHP's date() function
                ])
                ->update();


            // Check if any rows were updated
            if ($affectedRows > 0) {
                // If successful, return a success message
                return json_encode(['status' => 200, 'message' => 'Payment Request saved successfully']);
            } else {
                // If no rows were updated, return a different message
                return json_encode(['status' => 404, 'message' => 'No records found to update']);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and return an error message
            return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }


    public function transport_payment_request()
    {
        // tf-paydesk-r7h build 5 (V-3): one transport request at a time per tour (the duplicate check and the insert are not split)
        return \App\Libraries\TourPayments\PayDesk::withItemLock(\Config\Database::connect(), 'transport|' . (string) $this->request->getVar('tourId'), fn () => $this->transportPaymentRequestOnce())
            ?? json_encode(['status' => 409, 'message' => 'Another request for this item is being saved at this moment. Reload the page and check before trying again. Nothing was written.']);
    }

    private function transportPaymentRequestOnce()
    {
        // tf-paydesk-r7f: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'transport_payment_request')) {
            return $tfCsrf;
        }

        $tourId = $this->request->getVar('tourId');
        $hotelAcc = "TRANSPORT"; // A static identifier for transport requests
        $manualId = $this->request->getVar('manualId');
        $consignee = $this->request->getVar('consignee');
        $createUser = "Transport Agent";
        $startDate = ""; // Transport requests don't have a start date
        $endDate = ""; // Transport requests don't have an end date
        $hotelName =  "Transport Agent";
        $bankName = $this->request->getVar('bankName');
        $branchName = $this->request->getVar('branchName');
        $supBankAcc = $this->request->getVar('supBankAcc');
        $totalHotelPay = $this->request->getVar('totalHotelPay');
        $totalHotelAdvance = $this->request->getVar('totalHotelAdvance');
        $dueAmount = $this->request->getVar('dueAmount');
        $dueAmount = ($dueAmount === null || $dueAmount === '') ? 0 : $dueAmount;
        $requestAmount = $this->request->getVar('requestAmount');


        // Load the model
        $ConsigneeModel = new ConsigneeModel();

        $PaymentRequestModel = new PaymentRequestModel();

        // Block a duplicate request: any non-cancelled (payment_request != 3) row
        // already exists for this tour's transport settlement.
        $existingRequest = $PaymentRequestModel
            ->where('tour_id', $tourId)
            ->where('hotel_acc', $hotelAcc)
            ->where('payment_request !=', 3)
            ->first();
        if ($existingRequest) {
            return json_encode(['status' => 409, 'message' => 'A payment request has already been made for this item.']);
        }

        // Attempt to update records where Aflag = '1'
        try {


            $data = [
                'tour_id' => $tourId,
                'hotel_acc' => $hotelAcc, // Differentiates transport from hotel requests
                'manual_id' => $manualId,
                'consignee' => $consignee,
                'create_user' => $createUser,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'hotel_name' => $hotelName,
                'bank_name' => $bankName,
                'branch_name' => $branchName,
                'sup_bank_acc' => $supBankAcc,
                'total_hotel_pay' => $totalHotelPay,
                'total_hotel_advance' => $totalHotelAdvance,
                'due_amount' => $dueAmount,
                'payment_request' => 1,
                'payment_request_user' => session()->get('username'),
                'payment_request_date' => date('Y-m-d H:i:s'),
                'requestAmount' => $requestAmount
            ];
            // Insert into database
            $PaymentRequestModel->insert($data);
            // Perform the update
            $affectedRows = $ConsigneeModel->where('tour_id', $tourId)
                ->set([
                    'payment_request' => 1,
                    'payment_request_user' => session()->get('username'),
                    'payment_request_date' => date('Y-m-d H:i:s') // Correctly using PHP's date() function
                ])
                ->update();


            // Check if any rows were updated
            if ($affectedRows > 0) {
                // If successful, return a success message
                return json_encode(['status' => 200, 'message' => 'Payment Request saved successfully']);
            } else {
                // If no rows were updated, return a different message
                return json_encode(['status' => 404, 'message' => 'No records found to update']);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and return an error message
            return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function driver_advance_payment_request()
    {
        // tf-paydesk-r7h build 5 (V-3): one request at a time per driver advance row
        return \App\Libraries\TourPayments\PayDesk::withItemLock(\Config\Database::connect(), 'advance|' . (string) $this->request->getVar('requestId'), fn () => $this->driverAdvancePaymentRequestOnce())
            ?? json_encode(['status' => 409, 'message' => 'Another request for this item is being saved at this moment. Reload the page and check before trying again. Nothing was written.']);
    }

    private function driverAdvancePaymentRequestOnce()
    {
        // tf-paydesk-r7e: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'driver_advance_payment_request')) {
            return $tfCsrf;
        }
        $req_row_id = $this->request->getVar('requestId');
        $req_amount = $this->request->getVar('requestAmount');
        $tourId = $this->request->getVar('tourId');
        $driver = $this->request->getVar('hotelAcc');
        $manualId = $this->request->getVar('manualId');
        $consignee = $this->request->getVar('consignee');
        $createUser = "Transport Agent";
        $hotelName =  $this->request->getVar('hotelName');
        $bankName = $this->request->getVar('bankName');
        $branchName = $this->request->getVar('branchName');
        $supBankAcc = $this->request->getVar('supBankAcc');
        $totalHotelPay = $this->request->getVar('totalHotelPay');
        $totalHotelAdvance = $this->request->getVar('totalHotelAdvance');
        $dueAmount = $this->request->getVar('dueAmount');
        $dueAmount = ($dueAmount === null || $dueAmount === '') ? 0 : $dueAmount;
        $requestAmount = $this->request->getVar('requestAmount');
        $type = $this->request->getVar('type');
        
        if($type === 'Transport Agent'){
            $type = 'Transport Advance';
        }

        $DriveradvanceRequestModel = new DriverAdvanceRequestModel();
        $advanceReqDetails = $DriveradvanceRequestModel->where('id', $req_row_id)->first();

        // Block a duplicate request: this specific advance row was already marked
        // requested (flag=1), so don't request it again.
        if ($advanceReqDetails && (int) $advanceReqDetails['flag'] === 1) {
            return json_encode(['status' => 409, 'message' => 'This advance request has already been requested.']);
        }

        // Load the model
        $ConsigneeModel = new ConsigneeModel();

        $PaymentRequestModel = new PaymentRequestModel();

        // Attempt to update records where Aflag = '1'
        try {


            $data = [
                'tour_id' => $tourId,
                'hotel_acc' => $driver, // Differentiates transport from hotel requests
                'manual_id' => $advanceReqDetails['manual_id'],
                'consignee' => $consignee,
                'create_user' => $createUser,
                'start_date' => '0000-00-00',
                'end_date' => '0000-00-00',
                'hotel_name' => $hotelName,
                'bank_name' => $bankName,
                'branch_name' => $branchName,
                'sup_bank_acc' => $supBankAcc,
                'total_hotel_pay' => empty($totalHotelPay) ? '0.00' : $totalHotelPay,
                'total_hotel_advance' => empty($totalHotelAdvance) ? '0.00' : $totalHotelAdvance,
                'due_amount' => empty($dueAmount) ? '0.00' : $dueAmount,
                'payment_request' => 1,
                'payment_request_user' => session()->get('username'),
                'payment_request_date' => date('Y-m-d H:i:s'),
                'requestAmount' => $requestAmount,
                'type' => $type
            ];
            // Insert into database
            $PaymentRequestModel->insert($data);
            // tf-paydesk-r1: the desk's record of the driver's advance request (LKR, no rate)
            \App\Libraries\TourPayments\PayDesk::recordRequest(\Config\Database::connect(), (int) $PaymentRequestModel->getInsertID(), ['source_type' => 'driver_advance', 'source_id' => $driver, 'rate_user' => session()->get('username')]);

            // log_message('critical', $PaymentRequestModel->getLastQuery());
            // Perform the update
            $affectedRows = $ConsigneeModel->where('tour_id', $tourId)
                ->set([
                    'payment_request' => 1,
                    'payment_request_user' => session()->get('username'),
                    'payment_request_date' => date('Y-m-d H:i:s') // Correctly using PHP's date() function
                ])
                ->update();

            // Update the driver advance request status
            $DriveradvanceRequestModel->where('id', $req_row_id)
                ->set([
                    'flag' => 1
                ])
                ->update();

            if ($advanceReqDetails['amount'] != $req_amount) {
                // insert a new record for the remaining amount
                $newAdvanceData = [
                    'tour_id' => $tourId,
                    'manual_id' => $advanceReqDetails['manual_id'],
                    'driver' => $advanceReqDetails['driver'],
                    'driver_name' => $advanceReqDetails['driver_name'],
                    'amount' => $req_amount,
                    'date' => date('Y-m-d H:i:s'),
                    'flag' => 1,
                    'username' => session()->get('username')
                ];
                $DriveradvanceRequestModel->insert($newAdvanceData);
            }


            // Check if any rows were updated
            if ($affectedRows > 0) {
                // If successful, return a success message
                return json_encode(['status' => 200, 'message' => 'Payment Request saved successfully']);
            } else {
                // If no rows were updated, return a different message
                return json_encode(['status' => 404, 'message' => 'No records found to update']);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and return an error message
            return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // payment_requested_dashboard function
    public function payment_requested_dashboard()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $PaymentRequestModel = new PaymentRequestModel();

        $data['paymentRequests'] = $PaymentRequestModel
            ->select('payment_request.*,consignee.tour_cancel, consignee.start_date, consignee.end_date')
            ->join('consignee', 'consignee.tour_id = payment_request.tour_id')
            ->where('payment_request.payment_request', '4')
            ->where('consignee.tour_cancel <>', 1)
            ->groupBy('consignee.manual_id')
            ->orderBy('consignee.start_date', 'ASC')
            ->findAll();

        // Get posted expenses grouped by tour
        $db = \Config\Database::connect();
        $data['postedExpenses'] = $db->query("
            SELECT driver_expenses.tour_id, driver_expenses.agent, driver_expenses.agent_name,
                   consignee.consignee, consignee.manual_id, consignee.start_date, consignee.end_date, consignee.tour_cancel,
                   SUM(driver_expenses.lkr_amount) as total_lkr,
                   SUM(driver_expenses.dollar_amount) as total_dollar,
                   COUNT(driver_expenses.id) as expense_count,
                   MAX(driver_expenses.posted_at) as posted_at,
                   MAX(driver_expenses.posted_by) as posted_by
            FROM driver_expenses
            LEFT JOIN consignee ON consignee.tour_id COLLATE utf8mb4_general_ci = driver_expenses.tour_id COLLATE utf8mb4_general_ci
            WHERE driver_expenses.status = 'posted'
            GROUP BY driver_expenses.tour_id
            ORDER BY consignee.start_date ASC
        ")->getResultArray();

        $utilityBillModel = new UtilityBillModel();
        $data['acceptedUtilityBills'] = $utilityBillModel
            ->where('status', 1)
            ->groupStart()
            ->where('pay_status !=', 4)
            ->orWhere('pay_status IS NULL')
            ->groupEnd()
            ->findAll();

        $accountpostingModel = new AccountPostingModel();
        $data['load_bank'] = $accountpostingModel->select('PName, L_Code')
            ->where('B_Code', 1)
            ->where('T_Code', 6)
            ->where('H_Code', 8)
            ->findAll();

        return view('pages/tour/payment_requested_dashboard', $data);
    }


    // route : base_url/tour/ArraivalNotification
    public function payment_requested_report()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Check if 'view_all' is set in the GET request
        $v_all = $this->request->getGet('view_all') ? true : false;

        $lastSegment = $this->request->getUri()->getSegment(4);
        $tour = urldecode($lastSegment);
        // Fetch results with hotel_payment_flag and transport_advance joined
        $db = \Config\Database::connect();
        $baseQuery = $db->table('payment_request pr')
            ->select('pr.*, MAX(tq.hotel_payment_flag) as hotel_payment_flag, MAX(c.transport_advance) as transport_advance')
            ->join('tour_quotations tq', 'tq.quotation_id = pr.tour_id AND tq.hotel_acc = pr.hotel_acc', 'left')
            ->join('consignee c', 'c.tour_id = pr.tour_id', 'left')
            ->where('pr.manual_id', $tour)
            ->groupBy('pr.id')
            ->orderBy('pr.accept_date', 'ASC');

        if (!$v_all) {
            $baseQuery->where('pr.payment_request', '4')->where('pr.payment_request !=', 2);
        }

        $data['paymentRequests'] = \App\Libraries\TourPayments\Mask::bank($baseQuery->get()->getResultArray());   // tf-pilot-r3: payees' bank numbers masked unless the sign-in pays or verifies

        // Get tour info from consignee table for accurate dates
        $consigneeModel = new \App\Models\ConsigneeModel();
        $data['tourInfo'] = $consigneeModel->where('manual_id', $tour)->first();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        return view('pages/tour/payment_requested_report', $data);
    }

    public function cancel_payment_request()
    {
        // tf-paydesk-r7f: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'cancel_payment_request')) {
            return $tfCsrf;
        }

        $id = $this->request->getVar('id');
        $tour_id = $this->request->getVar('tour_id');
        $hotel_acc = $this->request->getVar('hotel_acc');

        $PaymentRequestModel = new PaymentRequestModel();


        try {
            // Perform the update
            $affectedRows = $PaymentRequestModel->where('id', $id)
                ->set([
                    'payment_request' => 3,
                    'payment_cancel_user' => session()->get('username'),
                    'payment_cancel_date' => date('Y-m-d H:i:s') // Correctly using PHP's date() function
                ])
                ->update();

            if ($affectedRows > 0) {

                if ($hotel_acc != 'TRANSPORT') {
                    $TourQuotationModel = new TourQuotationsModel();
                    $TourQuotationModel->where('quotation_id', $tour_id)
                        ->where('hotel_acc', $hotel_acc)
                        ->set(['payment_request' => 0])
                        ->update();
                } else {
                    $consigneeModel = new ConsigneeModel();
                    $consigneeModel->where('tour_id', $tour_id)
                        ->set(['payment_request' => 0])
                        ->update();
                }
                // log_message('critical', $TourQuotationModel->getLastQuery());

                return json_encode(['status' => 200, 'message' => 'Successfully']);
            } else {
                return json_encode(['status' => 404, 'message' => 'No records found to update']);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and return an error message
            return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function accept_invoice()
    {
        $tourid = $this->request->getVar('tourId');
        $manualId = $this->request->getVar('manualId');
        $invNo = $this->request->getVar('invNo');

        try {
            $ConsigneeModel = new ConsigneeModel();
            $invalertlog = new InvalertlogModel();

            // Perform the update
            $affectedRows = $ConsigneeModel->where('tour_id', $tourid)->where('manual_id', $manualId)->where('amend_inv', $invNo)
                ->set([
                    'inv_alert_status' => 1,
                    'accepted_user' => session()->get('username'),
                    'accepted_date' => date('Y-m-d H:i:s')
                ])
                ->update();

            $data = [
                'tour_id' => $tourid,
                'manual_id' => $manualId,
                'inv_no' => $invNo,
                'action' => 'accepted',
                'action_user' => session()->get('username'),
                'action_date' => date('Y-m-d H:i:s')
            ];

            // Insert into database
            $invalertlog->insert($data);

            if ($affectedRows > 0) {
                return json_encode(['status' => 200, 'message' => 'Successfully']);
            } else {
                return json_encode(['status' => 404, 'message' => 'No records found to update']);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and return an error message
            return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // reject invoice function 
    public function reject_invoice()
    {
        $tourid = $this->request->getVar('tourId');
        $manualId = $this->request->getVar('manualId');
        $invNo = $this->request->getVar('invNo');

        $ConsigneeModel = new ConsigneeModel();
        $invalertlog = new InvalertlogModel();

        try {
            // Perform the update
            $affectedRows = $ConsigneeModel->where('tour_id', $tourid)->where('manual_id', $manualId)->where('amend_inv', $invNo)
                ->set([
                    'inv_alert_status' => 2,
                    'accepted_user' => session()->get('username'),
                    'accepted_date' => date('Y-m-d H:i:s')
                ])
                ->update();

            $data = [
                'tour_id' => $tourid,
                'manual_id' => $manualId,
                'inv_no' => $invNo,
                'action' => 'rejected',
                'action_user' => session()->get('username'),
                'action_date' => date('Y-m-d H:i:s')
            ];

            // Insert into database
            $invalertlog->insert($data);

            if ($affectedRows > 0) {
                return json_encode(['status' => 200, 'message' => 'Successfully']);
            } else {
                return json_encode(['status' => 404, 'message' => 'No records found to update']);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and return an error message
            return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function inv_send_report()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $ConsigneeModel = new ConsigneeModel();
        $customerModel = new CustomerModel();

        $data['acceptedinvoices'] = $ConsigneeModel->where('inv_alert_status', '1')->findAll();

        $data['customers'] = $customerModel->select('Sup_Acc_No, Name')->findAll();

        return view('pages/tour/accepted_invoices', $data);
    }

    /**
     * Pending Expenses Dashboard - Shows tours with sent expenses grouped by tour and driver with approve/reject buttons
     */
    public function pending_expenses_dashboard()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $driverExpensesModel = new DriverExpensesModel();
        $consigneeModel = new ConsigneeModel();
        $logSheetMileModel = new LogSheetMileModel();
        $voucherPaymentModel = new VoucherPaymentModel();

        // Get all individual sent expenses that are pending
        $pendingExpenses = $driverExpensesModel
            ->where('request_send', 1)
            ->where('status', 'pending')
            ->orderBy('tour_id', 'ASC')
            ->orderBy('agent', 'ASC')
            ->orderBy('expense_date', 'ASC')
            ->findAll();

        // Group expenses by tour_id
        $grouped_expenses = [];
        $total_dollar_amount = 0;
        $total_lkr_amount = 0;
        $total_expenses = 0;

        foreach ($pendingExpenses as $expense) {
            $tour_id = $expense['tour_id'];
            $agent_code = $expense['agent'];

            if (!isset($grouped_expenses[$tour_id])) {
                $tourInfo = $consigneeModel->where('tour_id', $tour_id)->first();

                // Predictable Mileage Range: same rule as the Driver Log Report
                // (DriverPortalController::driverReport()) — lower bound is the plain
                // tour_sub_locations estimate_km total (no extras), upper bound adds extra_km
                // only for legs whose extra_km is exactly 40 (the office's marker for a genuine
                // activity/sightseeing detour), plus a flat 30km Garage Mileage allowance applied
                // once per tour. tour_sub_locations is tour-wide, not per-driver, so this figure
                // is computed once per tour here, not per driver below.
                $predictableRangeMin = 0.0;
                $predictableRangeMax = 0.0;
                $subLocationRows = (new TourSubLocationsModel())
                    ->select('estimate_km, extra_km')
                    ->where('quotation_id', $tour_id)
                    ->findAll();
                foreach ($subLocationRows as $sl) {
                    $legEstimateKm = $sl['estimate_km'] !== null ? (float) $sl['estimate_km'] : 0.0;
                    $legExtraKm = $sl['extra_km'] !== null ? (float) $sl['extra_km'] : 0.0;
                    $isActivityLeg = $sl['extra_km'] !== null && (float) $sl['extra_km'] === 40.0;

                    $predictableRangeMin += $legEstimateKm;
                    $predictableRangeMax += $legEstimateKm + ($isActivityLeg ? $legExtraKm : 0.0);
                }
                if (!empty($tourInfo['start_date'])) {
                    $predictableRangeMin += 30.0;
                    $predictableRangeMax += 30.0;
                }

                $grouped_expenses[$tour_id] = [
                    'tour_id' => $tour_id,
                    'manual_id' => $tourInfo['manual_id'] ?? '-',
                    'consignee' => $tourInfo['consignee'] ?? '-',
                    'start_date' => $tourInfo['start_date'] ?? null,
                    'end_date' => $tourInfo['end_date'] ?? null,
                    'total_transport_cost' => $tourInfo['total_transport_cost'] ?? 0,
                    'predictable_range_min' => round($predictableRangeMin, 2),
                    'predictable_range_max' => round($predictableRangeMax, 2),
                    'drivers' => [],
                    'total_dollar' => 0,
                    'total_lkr' => 0
                ];
            }

            // Initialize driver info if not exists
            if (!isset($grouped_expenses[$tour_id]['drivers'][$agent_code])) {
                // Has this driver already been settled for this tour? accept_approved_expenses()
                // creates exactly one voucher per tour+agent with advances_pay=11 when it pays out
                // the log sheet mileage/bata amount (and nets off the advance against it). If that
                // voucher already exists, both the mileage amount and the old advance were already
                // reconciled — a later top-up (e.g. a late "UNM" expense) must not re-show the full
                // logSheetAmount or deduct the old advance again, or it double-counts money already
                // sent. Same check as approved_expenses_report() / payment_approval_v2().
                $priorSettlementVoucher = $voucherPaymentModel
                    ->where('ref_po', $tour_id)
                    ->where('Acc_No', $agent_code)
                    ->where('advances_pay', 11)
                    ->first();
                $alreadySettled = $priorSettlementVoucher !== null;

                // Get advance payment for this driver on this tour
                $advancePayment = null;
                if (!$alreadySettled) {
                    $advancePayment = $voucherPaymentModel
                        ->select('SUM(Amount) as advance_amount')
                        ->where('ref_po', $tour_id)
                        ->where('Acc_No', $agent_code)
                        ->where('Aflag', 1)
                        ->where('advances_pay', 1)
                        ->first();
                }

                // Get log sheet mileage for this driver on this tour
                $logSheet = $logSheetMileModel
                    ->where('tour_id', $tour_id)
                    ->where('transport_agent', $agent_code)
                    ->orderBy('id', 'DESC')
                    ->first();

                $logSheetAmount = 0;
                $logSheetMileage = $logSheet['log_sheet_mileage'] ?? 0;
                $hasLogSheet = false;
                if ($logSheet && $logSheet['log_sheet_mileage'] > 0) {
                    $hasLogSheet = true;
                    if (!$alreadySettled) {
                        // Calculate from log sheet data
                        $logSheetAmount = ($logSheet['log_sheet_mileage'] * $logSheet['vehicle_rate']) +
                            ($logSheet['per_day_rate'] * $logSheet['no_of_dates']);
                    }
                }

                $grouped_expenses[$tour_id]['drivers'][$agent_code] = [
                    'driver_code' => $agent_code,
                    'driver_name' => $expense['agent_name'] ?? '-',
                    'advance_amount' => $advancePayment['advance_amount'] ?? 0,
                    'log_sheet_amount' => $logSheetAmount,
                    'log_sheet_mileage' => $logSheetMileage,
                    'vehicle_rate' => $logSheet['vehicle_rate'] ?? ($tourInfo['vehicle_rate'] ?? 0),
                    'bata_day_rate' => $logSheet['per_day_rate'] ?? ($tourInfo['bata_day_rate'] ?? 0),
                    'bata_days' => $logSheet['no_of_dates'] ?? ($tourInfo['bata_days'] ?? 0),
                    'bata_days_max' => $tourInfo['bata_days'] ?? 0,
                    'has_log_sheet' => $hasLogSheet,
                    'already_settled' => $alreadySettled,
                    'settled_amount' => $alreadySettled ? (float) ($priorSettlementVoucher['Amount'] ?? 0) : 0.0,
                    'settled_date' => $alreadySettled ? ($priorSettlementVoucher['C_Date'] ?? null) : null,
                    'expenses' => [],
                    'total_dollar' => 0,
                    'total_lkr' => 0
                ];
            }

            // Build comma-separated expense details
            $details = [];
            if (!empty($expense['expense_date'])) {
                $details[] = date('d M Y', strtotime($expense['expense_date']));
            }
            if (!empty($expense['invoice_no'])) {
                $details[] = 'Inv: ' . $expense['invoice_no'];
            }
            if (!empty($expense['supplier_name'])) {
                $details[] = $expense['supplier_name'];
            }
            if (!empty($expense['description'])) {
                $details[] = $expense['description'];
            }
            if (!empty($expense['dollar_rate']) && $expense['dollar_rate'] > 0) {
                $details[] = 'Rate: ' . number_format($expense['dollar_rate'], 2);
            }
            // Add dollar and LKR amounts
            if (!empty($expense['dollar_amount']) && $expense['dollar_amount'] > 0) {
                $details[] = '$ ' . number_format($expense['dollar_amount'], 2);
            }
            if (!empty($expense['lkr_amount']) && $expense['lkr_amount'] > 0) {
                $details[] = 'Rs. ' . number_format($expense['lkr_amount'], 2);
            }

            // Add expense to driver's expenses list
            $grouped_expenses[$tour_id]['drivers'][$agent_code]['expenses'][] = [
                'id' => $expense['id'],
                'details' => implode(', ', $details),
                'expense_date' => $expense['expense_date'] ?? '-',
                'invoice_no' => $expense['invoice_no'] ?? '-',
                'category' => $expense['category'] ?? '',
                'supplier' => $expense['supplier'] ?? '',
                'supplier_name' => $expense['supplier_name'] ?? '-',
                'description' => $expense['description'] ?? '-',
                'dollar_amount' => $expense['dollar_amount'] ?? 0,
                'lkr_amount' => $expense['lkr_amount'] ?? 0,
                'request_send_by' => $expense['request_send_by'] ?? '-',
                'request_send_at' => $expense['request_send_at'] ?? '-'
            ];

            // Update driver totals
            $grouped_expenses[$tour_id]['drivers'][$agent_code]['total_dollar'] += ($expense['dollar_amount'] ?? 0);
            $grouped_expenses[$tour_id]['drivers'][$agent_code]['total_lkr'] += ($expense['lkr_amount'] ?? 0);

            // Update tour totals
            $grouped_expenses[$tour_id]['total_dollar'] += ($expense['dollar_amount'] ?? 0);
            $grouped_expenses[$tour_id]['total_lkr'] += ($expense['lkr_amount'] ?? 0);
            $total_dollar_amount += ($expense['dollar_amount'] ?? 0);
            $total_lkr_amount += ($expense['lkr_amount'] ?? 0);
            $total_expenses++;
        }

        // Convert drivers array to indexed array for each tour and check if all have log sheets
        foreach ($grouped_expenses as $tour_id => &$tour) {
            $hasAllLogSheets = true;
            foreach ($tour['drivers'] as $agent_code => &$driver) {
                if (!$driver['has_log_sheet']) {
                    $hasAllLogSheets = false;
                }
                // Payment breakdown: Bata + Logsheet (already combined in log_sheet_amount) +
                // this driver's pending expenses (total_lkr, summed above) - Advance Paid.
                // Same formula as approved_expenses_report()'s Driver Payment Summary, but scoped
                // to pending/sent expenses since that's what this dashboard is reviewing.
                $driver['payable_amount'] = $driver['log_sheet_amount'] + $driver['total_lkr'] - $driver['advance_amount'];
            }
            unset($driver);
            $tour['has_all_log_sheets'] = $hasAllLogSheets;
            $tour['drivers'] = array_values($tour['drivers']);
        }

        $data['grouped_expenses'] = array_values($grouped_expenses);
        $data['total_tours'] = count($grouped_expenses);
        $data['total_expenses'] = $total_expenses;
        $data['total_dollar_amount'] = $total_dollar_amount;
        $data['total_lkr_amount'] = $total_lkr_amount;

        return view('pages/tour/pending_expenses_dashboard', $data);
    }

    /**
     * Approved Expenses Dashboard - Shows tours with approved expenses grouped by tour
     */
    public function approved_expenses_dashboard()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $driverExpensesModel = new DriverExpensesModel();
        $consigneeModel = new ConsigneeModel();

        // Get all approved expenses grouped by tour
        $db = \Config\Database::connect();
        $approvedExpenses = $db->query("
            SELECT driver_expenses.*, consignee.consignee, consignee.manual_id, consignee.start_date, consignee.end_date, consignee.tour_cancel
            FROM driver_expenses
            LEFT JOIN consignee ON consignee.tour_id COLLATE utf8mb4_general_ci = driver_expenses.tour_id COLLATE utf8mb4_general_ci
            WHERE driver_expenses.status = 'approved'
            GROUP BY driver_expenses.tour_id
            ORDER BY consignee.start_date ASC
        ")->getResultArray();

        $data['approvedExpenses'] = $approvedExpenses;

        // LKR bank balances (exclude USD, Control, Yen)
        $accountPostingModel = new AccountPostingModel();
        $banks = $accountPostingModel
            ->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)
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

        return view('pages/tour/approved_expenses_dashboard', $data);
    }

    /**
     * Approved Expenses Report - Shows approved expenses for a specific tour
     */
    public function approved_expenses_report($manual_id = null, $driver_code = null)
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        if (empty($manual_id)) {
            $manual_id = $this->request->getUri()->getSegment(4);
        }
        $manual_id = urldecode($manual_id);

        if (empty($driver_code)) {
            $driver_code = $this->request->getUri()->getSegment(5);
        }
        $driver_code = $driver_code !== null && $driver_code !== '' ? urldecode($driver_code) : null;
        $data['filterDriverCode'] = $driver_code;

        $consigneeModel = new ConsigneeModel();
        $driverExpensesModel = new DriverExpensesModel();
        $voucherPaymentModel = new VoucherPaymentModel();
        $logSheetMileModel = new LogSheetMileModel();
        $driverAllocationModel = new DriverAllocationModel();
        $driverMeterReadingModel = new DriverMeterReadingModel();

        // Get tour info
        $data['tourInfo'] = $consigneeModel->where('manual_id', $manual_id)->first();

        if (!$data['tourInfo']) {
            return redirect()->to(base_url('tour/tour_accounts/approved_expenses_dashboard'));
        }

        $tour_id = $data['tourInfo']['tour_id'];

        // A tour can have more than one driver — when $driver_code is set (link from a
        // per-driver row in payment_approval_v2/Driver Settlement Requests), scope this report
        // down to just that one driver's expenses/log sheet/allocation instead of the whole tour.
        $expensesQuery = $driverExpensesModel
            ->where('tour_id', $tour_id)
            ->where('status', 'approved');
        if ($driver_code !== null) {
            $expensesQuery->where('agent', $driver_code);
        }
        $data['approvedExpenses'] = $expensesQuery
            ->orderBy('expense_date', 'ASC')
            ->findAll();

        // Get driver allocations for this tour
        $allocationsQuery = $driverAllocationModel->where('tour_id', $tour_id);
        if ($driver_code !== null) {
            $allocationsQuery->where('driver', $driver_code);
        }
        $driverAllocations = $allocationsQuery->findAll();

        // Group expenses and calculations by driver
        $driverSummary = [];
        $totalLogSheetAmount = 0;
        $totalDriverEnteredTransportCost = 0;
        $totalLogSheetMileage = 0;
        $totalAmendedMileage = 0;
        $totalAdvanceAmount = 0;
        $totalExpensesAmount = 0;
        $totalPayableAmount = 0;
        $hasLogSheetMileage = false; // Track if any driver has log sheet mileage

        foreach ($driverAllocations as $allocation) {
            $agent_code = $allocation['driver']; // driver column contains the agent code
            $driver_name = $allocation['driver_name'] ?? '-';

            // Has this driver already been settled for this tour? accept_approved_expenses()
            // creates exactly one voucher per tour+agent with advances_pay=11 when it pays out
            // the log sheet mileage/bata amount. If that voucher already exists, the mileage
            // portion has already been paid — a later top-up (e.g. a late "UNM" expense) must
            // NOT re-show the full logSheetAmount again, or it double-counts money already sent.
            $priorSettlementVoucher = $voucherPaymentModel
                ->where('ref_po', $tour_id)
                ->where('Acc_No', $agent_code)
                ->where('advances_pay', 11)
                ->first();
            $alreadySettled = $priorSettlementVoucher !== null;

            // Get advance payment for this driver on this tour — once already settled, this
            // advance was already reconciled into the prior settlement's payout (logSheetAmount
            // and expenses at that time were netted against it), so counting it again here would
            // deduct it a second time from a late top-up that has nothing to do with it.
            $advancePayment = null;
            if (!$alreadySettled) {
                $advancePayment = $voucherPaymentModel
                    ->select('SUM(Amount) as advance_amount')
                    ->where('ref_po', $tour_id)
                    ->where('Acc_No', $agent_code)
                    ->where('Aflag', 1)
                    ->where('advances_pay', 1)
                    ->first();
            }

            // Get log sheet mileage for this driver on this tour
            $logSheet = $logSheetMileModel
                ->where('tour_id', $tour_id)
                ->where('transport_agent', $agent_code)
                ->orderBy('id', 'DESC')
                ->first();

            // Actual mileage — sourced from driver meter readings (start/end km per date), since
            // these are logged during the tour before the log sheet is finalized
            $meterReadings = $driverMeterReadingModel
                ->select('reading_date, type, km')
                ->where('tour_id', $tour_id)
                ->where('driver_acc', $agent_code)
                ->orderBy('reading_date', 'ASC')
                ->findAll();

            $readingsByDate = [];
            foreach ($meterReadings as $reading) {
                $readingDate = (string) ($reading['reading_date'] ?? '');
                $readingType = (string) ($reading['type'] ?? '');
                if ($readingDate === '' || $readingType === '') {
                    continue;
                }
                $readingsByDate[$readingDate][$readingType] = (float) ($reading['km'] ?? 0);
            }

            $meterMileage = 0.0;
            foreach ($readingsByDate as $dayReadings) {
                if (!isset($dayReadings['start'], $dayReadings['end'])) {
                    continue;
                }
                $meterMileage += max(0, $dayReadings['end'] - $dayReadings['start']);
            }
            $meterMileage = round($meterMileage, 2);

            $logSheetAmount = 0;
            $driverEnteredTransportCost = 0;
            $logSheetMileage = $meterMileage;
            // Amended Mileage — the staff-corrected distance in log_sheet_mile.log_sheet_mileage,
            // as opposed to $meterMileage above (the driver's raw, un-reviewed meter-reading claim).
            // Staff walk each day of the tour and cut back mileage they judge inflated before this
            // gets finalized, so this can legitimately be lower than the driver's own submission.
            $amendedMileage = ($logSheet && $logSheet['log_sheet_mileage'] > 0) ? (float) $logSheet['log_sheet_mileage'] : 0.0;
            $driverHasLogSheet = false;
            if ($logSheet && $logSheet['log_sheet_mileage'] > 0) {
                $logSheetAmount = ($logSheet['log_sheet_mileage'] * $logSheet['vehicle_rate']) +
                    ($logSheet['per_day_rate'] * $logSheet['no_of_dates']);
                // What the transport cost would have been at the driver's own (un-amended) mileage
                // claim, same rate/bata — shows the Rs. impact of the staff mileage correction.
                $driverEnteredTransportCost = ($meterMileage * $logSheet['vehicle_rate']) +
                    ($logSheet['per_day_rate'] * $logSheet['no_of_dates']);
                $hasLogSheetMileage = true;
                $driverHasLogSheet = true;
            } elseif ($meterMileage > 0) {
                // No finalized log sheet yet, but meter readings exist — show them as pending actual mileage
                $driverHasLogSheet = true;
            }

            // Get total expenses for this driver
            $driverExpenses = $driverExpensesModel
                ->select('SUM(lkr_amount) as total_lkr, SUM(dollar_amount) as total_dollar')
                ->where('tour_id', $tour_id)
                ->where('agent', $agent_code)
                ->where('status', 'approved')
                ->first();

            $expensesLKR = $driverExpenses['total_lkr'] ?? 0;
            $advanceAmt = $advancePayment['advance_amount'] ?? 0;

            // Once already settled, the mileage/bata portion was paid out by the prior voucher —
            // exclude it from this payable calc so a late top-up (new approved expenses only)
            // isn't shown on top of the full mileage amount again.
            $payableLogSheetAmount = $alreadySettled ? 0.0 : $logSheetAmount;

            // Payable = Log Sheet Amount - (Advance - Expenses)
            $payableAmount = $payableLogSheetAmount - ($advanceAmt - $expensesLKR);

            $driverSummary[$agent_code] = [
                'driver_code' => $agent_code,
                'driver_name' => $driver_name,
                'log_sheet_amount' => $logSheetAmount,
                'amended_mileage' => $amendedMileage,
                'vehicle_rate' => (float) ($logSheet['vehicle_rate'] ?? 0),
                'bata_rate' => (float) ($logSheet['per_day_rate'] ?? 0),
                'bata_days' => (int) ($logSheet['no_of_dates'] ?? 0),
                'advance_amount' => $advanceAmt,
                'expenses_amount' => $expensesLKR,
                'payable_amount' => $payableAmount,
                'has_log_sheet' => $driverHasLogSheet,
                'already_settled' => $alreadySettled,
                'settled_amount' => $alreadySettled ? (float) ($priorSettlementVoucher['Amount'] ?? 0) : 0.0,
                'settled_date' => $alreadySettled ? ($priorSettlementVoucher['C_Date'] ?? null) : null,
            ];

            $totalLogSheetAmount += $payableLogSheetAmount;
            $totalDriverEnteredTransportCost += $driverEnteredTransportCost;
            $totalLogSheetMileage += $logSheetMileage;
            $totalAmendedMileage += $amendedMileage;
            $totalAdvanceAmount += $advanceAmt;
            $totalExpensesAmount += $expensesLKR;
            $totalPayableAmount += $payableAmount;
        }

        $data['driverSummary'] = $driverSummary;
        $data['totalLogSheetAmount'] = $totalLogSheetAmount;
        $data['totalDriverEnteredTransportCost'] = $totalDriverEnteredTransportCost;
        $data['totalLogSheetMileage'] = $totalLogSheetMileage;
        $data['totalAmendedMileage'] = $totalAmendedMileage;
        $data['totalAdvanceAmount'] = $totalAdvanceAmount;
        $data['totalExpensesAmount'] = $totalExpensesAmount;
        $data['totalPayableAmount'] = $totalPayableAmount;
        $data['hasLogSheetMileage'] = $hasLogSheetMileage;

        // Total estimated mileage from quotation (sum of gdistance across tour_quotations rows)
        // gdistance = google-estimated main-leg km; extra_kilo = separate additive extra km (e.g. sightseeing detours)
        $tourQuotationsModel = new TourQuotationsModel();
        $quotationTotals = $tourQuotationsModel
            ->selectSum('gdistance')
            ->selectSum('extra_kilo')
            ->where('quotation_id', $tour_id)
            ->first();
        $totalEstimatedMileageNoExtra = (float) ($quotationTotals['gdistance'] ?? 0);
        $totalEstimatedExtraMileage = (float) ($quotationTotals['extra_kilo'] ?? 0);
        $totalEstimatedMileageWithExtra = $totalEstimatedMileageNoExtra + $totalEstimatedExtraMileage;

        // Rates used for the estimated cost projection — taken from the latest log sheet entry per driver
        // (falls back to 0 if a driver has no log sheet row yet). A driver whose log sheet has
        // vehicle_rate <= 0 (e.g. a back-office bata-only correction, like N M Thushara Sampath's
        // manual entry) would silently zero out the mileage term on BOTH sides of the variance,
        // making Estimated == Actual by coincidence and hiding a real mileage shortfall/overage.
        // Flag those drivers instead of quietly computing a misleading 0.00 variance.
        $totalEstimatedTransportCost = 0;
        $driversWithZeroRate = [];
        foreach ($driverAllocations as $allocation) {
            $agent_code = $allocation['driver'];
            $logSheet = $logSheetMileModel
                ->where('tour_id', $tour_id)
                ->where('transport_agent', $agent_code)
                ->orderBy('id', 'DESC')
                ->first();

            if ($logSheet) {
                $totalEstimatedTransportCost += ($totalEstimatedMileageWithExtra * ($logSheet['vehicle_rate'] ?? 0))
                    + (($logSheet['per_day_rate'] ?? 0) * ($logSheet['no_of_dates'] ?? 0));

                if ((float) ($logSheet['vehicle_rate'] ?? 0) <= 0) {
                    $driversWithZeroRate[] = $allocation['driver_name'] ?? $agent_code;
                }
            }
        }

        // Variance: staff-amended transport cost vs what the driver's own (un-amended) mileage
        // claim would have cost — i.e. the Rs. impact of the staff correction.
        $transportCostVariance = $totalLogSheetAmount - $totalDriverEnteredTransportCost;

        // Variance: staff-amended mileage vs the driver's own raw meter-reading claim.
        $mileageVariance = $totalAmendedMileage - $totalLogSheetMileage;

        $data['totalEstimatedMileageNoExtra'] = $totalEstimatedMileageNoExtra;
        $data['totalEstimatedExtraMileage'] = $totalEstimatedExtraMileage;
        $data['totalEstimatedMileageWithExtra'] = $totalEstimatedMileageWithExtra;
        $data['totalEstimatedTransportCost'] = $totalEstimatedTransportCost;
        $data['transportCostVariance'] = $transportCostVariance;
        $data['mileageVariance'] = $mileageVariance;
        $data['driversWithZeroRate'] = $driversWithZeroRate;

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

        return view('pages/tour/approved_expenses_report', $data);
    }

    /**
     * Accept approved expenses and post to account_running table
     */
    public function accept_approved_expenses()
    {
        // tf-paydesk-r7f: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'accept_approved_expenses')) {
            return $tfCsrf;
        }
        // tf-payauth-r1: a payment action — the payment role is checked here, not only by the menu
        if ($tfR = $this->tfPayRole(['approver'], 'Accepting approved expenses (posting their payment)')) {
            return $tfR;
        }
        try {
            $tour_id = $this->request->getPost('tour_id');
            $bank = $this->request->getPost('bank_acc');
            $bank_name = $this->request->getPost('bank_name');
            $payable_amount = $this->request->getPost('payable_amount');

            if (empty($tour_id)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour ID is required']);
            }

            if (empty($bank)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Bank account is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();
            $voucherPaymentModel = new VoucherPaymentModel();
            $logSheetMileModel = new LogSheetMileModel();
            $driverAllocationModel = new DriverAllocationModel();
            $accountRunningModel = new AccountRunningModel();
            $invoiceNoModel = new \App\Models\InvoiceNumberModel();

            // Get all approved expenses for this tour
            $approvedExpenses = $driverExpensesModel
                ->where('tour_id', $tour_id)
                ->where('status', 'approved')
                ->findAll();

            if (empty($approvedExpenses)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'No approved expenses found for this tour']);
            }

            // Get driver allocations
            $driverAllocations = $driverAllocationModel
                ->where('tour_id', $tour_id)
                ->findAll();

            // First, validate that all drivers have log sheet mileage
            $driversWithoutLogSheet = [];
            foreach ($driverAllocations as $allocation) {
                $agent_code = $allocation['driver'];
                $driver_name = $allocation['driver_name'] ?? '-';

                $logSheet = $logSheetMileModel
                    ->where('tour_id', $tour_id)
                    ->where('transport_agent', $agent_code)
                    ->orderBy('id', 'DESC')
                    ->first();

                if (!$logSheet || $logSheet['log_sheet_mileage'] <= 0) {
                    $driversWithoutLogSheet[] = $driver_name;
                }
            }

            if (!empty($driversWithoutLogSheet)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Cannot accept: Log sheet mileage is missing for driver(s): ' . implode(', ', $driversWithoutLogSheet)
                ]);
            }

            // Get tour info (display purposes only — no longer used to pick a single "the"
            // transport agent, since a tour can have more than one driver)
            $consigneeModel = new ConsigneeModel();
            $tourInfo = $consigneeModel->where('tour_id', $tour_id)->first();

            $postedCount = 0;

            // Post each expense individually to account_running
            foreach ($approvedExpenses as $expense) {
                // Post to account_running
                $acc_id = $invoiceNoModel->incrementAccId();

                $accountRunningData = [
                    [
                        'ID' => $acc_id,
                        'L_Code' => $expense['supplier'],
                        'Date' => $expense['expense_date'],
                        'Discription' => $expense['description'],
                        'Dr' => $expense['lkr_amount'],
                        'Cr' => 0.00,
                        'Invoice_Num' => $expense['invoice_no'],
                        'Linkacc' => $expense['supplier'],
                        'index_no' => $expense['tour_id'],
                        'dollarrate' => $expense['dollar_rate'],
                        'dollar' => $expense['dollar_amount'],
                        'job' => $expense['category'],
                        'advances_pay' => 111,
                        'fine_date' => date('Y-m-d'),
                        'Cashier' => session()->get('username'),
                        'Dt' => date('Y-m-d H:i:s')
                    ],
                    [
                        'ID' => $acc_id,
                        'L_Code' => $expense['agent'],
                        'Date' => $expense['expense_date'],
                        'Discription' => $expense['description'],
                        'Dr' => 0.00,
                        'Cr' => $expense['lkr_amount'],
                        'Invoice_Num' => $expense['invoice_no'],
                        'Linkacc' => $expense['supplier'],
                        'index_no' => $expense['tour_id'],
                        'dollarrate' => $expense['dollar_rate'],
                        'dollar' => $expense['dollar_amount'],
                        'job' => $expense['category'],
                        'advances_pay' => 111,
                        'fine_date' => date('Y-m-d'),
                        'Cashier' => session()->get('username'),
                        'Dt' => date('Y-m-d H:i:s')
                    ]
                ];

                $insertResult = $accountRunningModel->insertBatch($accountRunningData);

                if ($insertResult) {
                    // Update expense status to posted
                    $driverExpensesModel->update($expense['id'], [
                        'status' => 'posted',
                        'posted_by' => session()->get('username'),
                        'posted_at' => date('Y-m-d H:i:s'),
                        'acc_run_id' => $acc_id
                    ]);
                    $postedCount++;
                }
            }

            // Create one voucher PER DRIVER for their own payable amount — a tour can have more
            // than one driver, each with their own log sheet / advance / expenses, so a single
            // combined voucher to "the" transport agent would either pay the wrong driver or pay
            // one driver the sum of everyone's money. Recompute each driver's payable the same
            // way approved_expenses_report() does (Payable = Log Sheet Amount - (Advance - Expenses)).
            $vouchersCreated = 0;
            if ($postedCount > 0) {
                $model_inv = new InvoiceNumberModel();
                $voucherInvoiceModel = new VoucherInvoiceModel();

                foreach ($driverAllocations as $allocation) {
                    $agent = $allocation['driver'];
                    $agent_name = $allocation['driver_name'] ?? '-';

                    // If this driver+tour was already settled (an advances_pay=11 voucher already
                    // exists), the mileage/bata amount was already paid out — do not include it
                    // again for a late top-up settle, or this creates a second voucher paying the
                    // full mileage amount a second time. See same check in approved_expenses_report().
                    $priorSettlementVoucher = $voucherPaymentModel
                        ->where('ref_po', $tour_id)
                        ->where('Acc_No', $agent)
                        ->where('advances_pay', 11)
                        ->first();
                    $alreadySettled = $priorSettlementVoucher !== null;

                    $logSheet = $logSheetMileModel
                        ->where('tour_id', $tour_id)
                        ->where('transport_agent', $agent)
                        ->orderBy('id', 'DESC')
                        ->first();

                    $logSheetAmount = 0;
                    if (!$alreadySettled && $logSheet && $logSheet['log_sheet_mileage'] > 0) {
                        $logSheetAmount = ($logSheet['log_sheet_mileage'] * $logSheet['vehicle_rate']) +
                            ($logSheet['per_day_rate'] * $logSheet['no_of_dates']);
                    }

                    // Already-settled drivers had this advance reconciled into their prior payout —
                    // do not deduct it again against a late top-up. See same check above.
                    $advanceAmt = 0;
                    if (!$alreadySettled) {
                        $advancePayment = $voucherPaymentModel
                            ->select('SUM(Amount) as advance_amount')
                            ->where('ref_po', $tour_id)
                            ->where('Acc_No', $agent)
                            ->where('Aflag', 1)
                            ->where('advances_pay', 1)
                            ->first();
                        $advanceAmt = $advancePayment['advance_amount'] ?? 0;
                    }

                    $driverExpensesTotal = $driverExpensesModel
                        ->select('SUM(lkr_amount) as total_lkr')
                        ->where('tour_id', $tour_id)
                        ->where('agent', $agent)
                        ->where('status', 'posted')
                        ->first();
                    $expensesLKR = $driverExpensesTotal['total_lkr'] ?? 0;

                    $driverPayable = round($logSheetAmount - ($advanceAmt - $expensesLKR), 2);

                    if ($driverPayable <= 0) {
                        continue;
                    }

                    $v_num = $model_inv->incrementVoucherToken();

                    $voucherData = [
                        'Pay' => $agent_name,
                        'emp_code' => $agent,
                        'S_name' => $agent_name,
                        'Acc_No' => $agent,
                        'sup' => '1',
                        'type' => 'Bank Transfer',
                        'Amount' => $driverPayable,
                        'VAT' => '0',
                        'VAT_Amt' => '0',
                        'vat_m' => '0',
                        'nbt' => '0',
                        'nbt_amt' => '0',
                        'nbt_m' => '0',
                        'g_ttl' => $driverPayable,
                        'Address' => '0',
                        'print' => '0',
                        'des' => '0',
                        'W_Date' => date('Y-m-d'),
                        'dv' => '0',
                        'app_user' => session()->get('acc_no'),
                        'login' => session()->get('username'),
                        'Aflag' => '0',
                        'v_typ' => 'Bank Transfer',
                        'ref_po' => $tour_id,
                        'insflag' => 1,
                        'inv' => '0',
                        'Vnum' => $v_num,
                        'Bank' => $bank_name,
                        'Bank_acc' => $bank,
                        'C_Date' => date('Y-m-d H:i:s'),
                        'C_Number' => $tour_id,
                        'is_expenses' => 1,
                        'advances_pay' => 11,
                        'doller_amt' => $driverPayable
                    ];

                    $voucherInvoiceData = [
                        'Date' => date('Y-m-d'),
                        'Invoice' => $tour_id,
                        'Description' => 'Transport Payment Settled ' . ($tourInfo['manual_id'] ?? '') . ' / ' . $agent_name,
                        'Units' => 0,
                        'Rate' => 0,
                        'Amount' => $driverPayable,
                        'InvAmount' => $driverPayable,
                        'V_tkn' => $v_num,
                        'ityp' => '1',
                        'link_code' => $agent,
                        'employee' => $agent_name,
                        'login' => session()->get('username'),
                        'INV_Date' => date('Y-m-d'),
                        'Payee' => $agent_name,
                        'account' => $agent
                    ];

                    $voucherPaymentModel->insert($voucherData);
                    $voucherInvoiceModel->insert($voucherInvoiceData);
                    $vouchersCreated++;
                }
            }

            return $this->response->setJSON(['status' => 200, 'message' => $postedCount . ' expense(s) accepted and posted to accounts; ' . $vouchersCreated . ' driver payment voucher(s) created.']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject approved expenses and send back for review
     */
    public function reject_approved_expenses()
    {
        // tf-paydesk-r7f: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'reject_approved_expenses')) {
            return $tfCsrf;
        }
        // tf-payauth-r1: a payment action — the payment role is checked here, not only by the menu
        if ($tfR = $this->tfPayRole(['approver'], 'Rejecting approved expenses')) {
            return $tfR;
        }
        try {
            $tour_id = $this->request->getPost('tour_id');
            $reason = $this->request->getPost('reason');

            if (empty($tour_id)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour ID is required']);
            }

            if (empty($reason)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Rejection reason is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();

            // Get all approved expenses for this tour
            $approvedExpenses = $driverExpensesModel
                ->where('tour_id', $tour_id)
                ->where('status', 'approved')
                ->findAll();

            if (empty($approvedExpenses)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'No approved expenses found for this tour']);
            }

            // Update all approved expenses back to pending
            $rejectedCount = $driverExpensesModel
                ->where('tour_id', $tour_id)
                ->where('status', 'approved')
                ->set([
                    'status' => 'rejected',
                    'rejected_by' => session()->get('username'),
                    'rejected_at' => date('Y-m-d H:i:s'),
                    'rejection_reason' => $reason
                ])
                ->update();

            return $this->response->setJSON(['status' => 200, 'message' => count($approvedExpenses) . ' expense(s) rejected successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Get sent expenses for a specific tour (AJAX)
     */
    public function get_tour_sent_expenses()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');

            if (empty($tour_id)) {
                return json_encode(['status' => 400, 'message' => 'Tour ID is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();
            $expenses = $driverExpensesModel
                ->where('tour_id', $tour_id)
                ->where('request_send', 1)
                ->orderBy('expense_date', 'ASC')
                ->findAll();

            return json_encode(['status' => 200, 'data' => $expenses]);
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Posted Expenses Report - Shows posted expenses for a specific tour
     */
    public function posted_expenses_report($manual_id = null)
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        if (empty($manual_id)) {
            $manual_id = $this->request->getUri()->getSegment(4);
        }
        $manual_id = urldecode($manual_id);

        $consigneeModel = new ConsigneeModel();
        $driverExpensesModel = new DriverExpensesModel();
        $voucherPaymentModel = new VoucherPaymentModel();
        $logSheetMileModel = new LogSheetMileModel();
        $driverAllocationModel = new DriverAllocationModel();

        // Get tour info
        $data['tourInfo'] = $consigneeModel->where('manual_id', $manual_id)->first();

        if (!$data['tourInfo']) {
            return redirect()->to(base_url('tour/tour_accounts/payment_requested_dashboard'));
        }

        $tour_id = $data['tourInfo']['tour_id'];

        // Get all posted expenses for this tour
        $data['postedExpenses'] = $driverExpensesModel
            ->where('tour_id', $tour_id)
            ->where('status', 'posted')
            ->orderBy('expense_date', 'ASC')
            ->findAll();

        // Calculate total posted expenses directly from the posted expenses
        $totalPostedExpenses = $driverExpensesModel
            ->select('SUM(lkr_amount) as total_lkr')
            ->where('tour_id', $tour_id)
            ->where('status', 'posted')
            ->first();
        $data['totalPostedExpenses'] = $totalPostedExpenses['total_lkr'] ?? 0;

        // Get posted expenses grouped by agent (driver) directly from driver_expenses table
        $db = \Config\Database::connect();

        // First check if there are any posted expenses for this tour (for debugging)
        $allPostedExpenses = $db->query("
            SELECT * FROM driver_expenses
            WHERE tour_id = ? AND status = 'posted'
        ", [$tour_id])->getResultArray();

        // If no expenses found with exact tour_id, try with manual_id match
        if (empty($allPostedExpenses)) {
            $allPostedExpenses = $db->query("
                SELECT de.* FROM driver_expenses de
                INNER JOIN consignee c ON c.tour_id COLLATE utf8mb4_general_ci = de.tour_id COLLATE utf8mb4_general_ci
                WHERE c.manual_id = ? AND de.status = 'posted'
            ", [$manual_id])->getResultArray();
        }

        $postedExpensesByAgent = $db->query("
            SELECT agent, agent_name,
                   SUM(lkr_amount) as total_lkr,
                   SUM(dollar_amount) as total_dollar
            FROM driver_expenses
            WHERE tour_id = ? AND status = 'posted'
            GROUP BY agent
        ", [$tour_id])->getResultArray();

        // Group expenses and calculations by driver
        $driverSummary = [];
        $totalLogSheetAmount = 0;
        $totalAdvanceAmount = 0;
        $totalExpensesAmount = 0;
        $totalPayableAmount = 0;

        foreach ($postedExpensesByAgent as $expenseRow) {
            $agent_code = $expenseRow['agent'];
            $driver_name = $expenseRow['agent_name'] ?? '-';
            $expensesLKR = $expenseRow['total_lkr'] ?? 0;

            // Get advance payment for this driver on this tour
            $advancePayment = $voucherPaymentModel
                ->select('SUM(Amount) as advance_amount')
                ->where('ref_po', $tour_id)
                ->where('Acc_No', $agent_code)
                ->where('Aflag', 1)
                ->where('advances_pay', 1)
                ->first();

            // Get log sheet mileage for this driver on this tour
            $logSheet = $logSheetMileModel
                ->where('tour_id', $tour_id)
                ->where('transport_agent', $agent_code)
                ->orderBy('id', 'DESC')
                ->first();

            $logSheetAmount = 0;
            if ($logSheet && $logSheet['log_sheet_mileage'] > 0) {
                $logSheetAmount = ($logSheet['log_sheet_mileage'] * $logSheet['vehicle_rate']) +
                    ($logSheet['per_day_rate'] * $logSheet['no_of_dates']);
            }

            $advanceAmt = $advancePayment['advance_amount'] ?? 0;

            // Payable = Log Sheet Amount - (Advance - Expenses)
            $payableAmount = $logSheetAmount - ($advanceAmt - $expensesLKR);

            // Check if this driver has any paid voucher payment for this tour
            $hasPaidVoucher = $voucherPaymentModel
                ->where('ref_po', $tour_id)
                ->where('Acc_No', $agent_code)
                ->where('advances_pay', 11)
                ->where('Aflag', 1)
                ->countAllResults() > 0;

            $driverSummary[$agent_code] = [
                'driver_code' => $agent_code,
                'driver_name' => $driver_name,
                'log_sheet_amount' => $logSheetAmount,
                'advance_amount' => $advanceAmt,
                'expenses_amount' => $expensesLKR,
                'payable_amount' => $payableAmount,
                'has_paid_voucher' => $hasPaidVoucher
            ];

            $totalLogSheetAmount += $logSheetAmount;
            $totalAdvanceAmount += $advanceAmt;
            $totalExpensesAmount += $expensesLKR;
            $totalPayableAmount += $payableAmount;
        }

        $data['driverSummary'] = $driverSummary;
        $data['totalLogSheetAmount'] = $totalLogSheetAmount;
        $data['totalAdvanceAmount'] = $totalAdvanceAmount;
        $data['totalExpensesAmount'] = $totalExpensesAmount;
        $data['totalPayableAmount'] = $totalPayableAmount;
        $data['user_group'] = session()->get('group_id');

        return view('pages/tour/posted_expenses_report', $data);
    }

    public function finalize_driver_expenses()
    {
        // tf-paydesk-r7f: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'finalize_driver_expenses')) {
            return $tfCsrf;
        }
        // tf-payauth-r1: a payment action — the payment role is checked here, not only by the menu
        if ($tfR = $this->tfPayRole(['approver', 'accountant', 'bank_approver'], 'Finalising a driver expense payment')) {
            return $tfR;
        }
        try {
            $tour_id  = $this->request->getPost('tour_id');
            $driver   = $this->request->getPost('driver');

            if (empty($tour_id) || empty($driver)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Missing required parameters.']);
            }

            $driverExpensesModel = new DriverExpensesModel();

            $affected = $driverExpensesModel
                ->where('tour_id', $tour_id)
                ->where('agent', $driver)
                ->where('status', 'posted')
                ->set([
                    'status'       => 'completed',
                    'finalized_by' => session()->get('username'),
                    'finalized_at' => date('Y-m-d H:i:s')
                ])
                ->update();

            if ($affected) {
                return $this->response->setJSON(['status' => 200, 'message' => 'Payment finalized successfully.']);
            }

            return $this->response->setJSON(['status' => 404, 'message' => 'No posted expenses found to finalize.']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // route : base_url/tour/tour_accounts/lunch_pay
    public function lunch_pay()
    {
        // tf-paydesk-r7f: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'lunch_pay')) {
            return $tfCsrf;
        }
        // tf-payauth-r1: a payment action — the payment role is checked here, not only by the menu
        if ($tfR = $this->tfPayRole(['approver'], 'Paying a lunch request')) {
            return $tfR;
        }
        if ($tfR = $this->tfNotOwn([(int) $this->request->getPost('id')], 'approve')) {
            return $tfR;
        }
        try {
            $id = $this->request->getPost('id');
            $tourId = $this->request->getPost('tour_id');
            $amount = $this->request->getPost('amount');
            $payDate = $this->request->getPost('pay_date') ?: date('Y-m-d');

            $PaymentRequestModel = new PaymentRequestModel();
            $request = $PaymentRequestModel->find($id);

            if (!$request) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Payment request not found']);
            }

            // tf-paydesk-r7g: everything is checked before anything is written. Lunch Pay pays an accepted lunch request (status 4)
            // once, for the request's own tour, supplier account and amount, and never a request that is on the Payment Desk.
            if ((string) ($request['type'] ?? '') !== 'lunch') {
                return $this->response->setJSON(['status' => 409, 'message' => 'Request #' . (int) $id . ' is not a lunch request. Nothing was written.']);
            }
            if ((int) $request['payment_request'] !== 4) {
                return $this->response->setJSON(['status' => 409, 'message' => (int) $request['payment_request'] === 2
                    ? 'Request #' . (int) $id . ' is already paid (completed ' . ($request['complete_date'] ?? '') . '). It is never paid twice. Nothing was written.'
                    : 'Request #' . (int) $id . ' is not an accepted request (status ' . (int) $request['payment_request'] . '). Nothing was written.']);
            }
            if ($tourId !== null && $tourId !== '' && (string) $tourId !== (string) $request['tour_id']) {
                return $this->response->setJSON(['status' => 409, 'message' => 'The tour sent by the page does not match request #' . (int) $id . '. Reload the page. Nothing was written.']);
            }
            $tourId = (string) $request['tour_id'];
            $amount = round((float) str_replace(',', '', (string) $amount), 2);
            if ($amount <= 0 || abs($amount - round((float) $request['requestAmount'], 2)) > 0.009) {
                return $this->response->setJSON(['status' => 409, 'message' => 'The amount (' . number_format($amount, 2) . ') must equal the accepted request amount (LKR ' . number_format((float) $request['requestAmount'], 2) . '). Nothing was written.']);
            }
            $tfDb = \Config\Database::connect();
            try {
                $tfDesk = $tfDb->query("SELECT approved_by, batch_id, voucher_vnum FROM tpd_request_ext WHERE pr_id = ?", [(int) $id])->getRowArray();
            } catch (\Throwable $e) {
                $tfDesk = null;   // no Payment Desk tables on this system
            }
            if ($tfDesk && ($tfDesk['approved_by'] !== null || $tfDesk['batch_id'] !== null || $tfDesk['voucher_vnum'] !== null)) {
                return $this->response->setJSON(['status' => 409, 'message' => 'Request #' . (int) $id . ' is on the Payment Desk' . ($tfDesk['batch_id'] !== null ? ' (in a supplier batch)' : ' (approved into Supplier Payables)') . ': it is paid from the supplier\'s batch there, not with Lunch Pay. Nothing was written.']);
            }

            // tf-paydesk-r7h build 5 (N-5): a voucher of this supplier on the tour, issued after the approval and linked to no request, may already
            // be this payment: it is reviewed (IS / is NOT the payment) before Lunch Pay raises another voucher; a linked request is never paid twice
            try {
                $tfX = $tfDesk ? $tfDb->query("SELECT * FROM tpd_request_ext WHERE pr_id = ?", [(int) $id])->getRowArray() : null;
                $tfSt = \App\Libraries\TourPayments\RequestLink::earlierPayments($tfDb, [$request], $tfX ? [(int) $id => $tfX] : [])[(int) $id] ?? null;
            } catch (\Throwable $e) {
                $tfSt = null;
            }
            if ($tfSt && $tfSt['state'] === 'linked') {
                return $this->response->setJSON(['status' => 409, 'message' => 'Request #' . (int) $id . ' is already linked to Payment Voucher No. ' . implode(', ', $tfSt['vnums']) . ' (' . $tfSt['basis'] . '). It is never paid twice. Nothing was written.']);
            }
            if ($tfSt && in_array($tfSt['state'], ['possible', 'invalid'], true)) {
                return $this->response->setJSON(['status' => 409, 'review_needed' => true, 'message' => 'Request #' . (int) $id . ' may already be paid by Payment Voucher No. ' . implode(', ', $tfSt['vnums']) . ' (' . $tfSt['basis'] . '). A holder of the batch privilege must review it on the Tour Payment Dashboard first. Nothing was written.']);
            }

            $supplierAcc = $request['hotel_acc'];
            $supplierName = $request['hotel_name'];
            $bankName = $this->request->getPost('bank_name') ?: $request['bank_name'];
            $bankAcc = $this->request->getPost('bank_acc') ?: $request['sup_bank_acc'];
            $manualId = $request['manual_id'];

            // tf-paydesk-r7g: the supplier is found by its ledger account code (payment_request.hotel_acc holds the code, e.g.
            // 6-9-11-11616). The former lookup by the numeric ID made MySQL read "6-9-11-11616" as 6 and the voucher paid supplier
            // ID 6's account (6-9-11-10553) instead (r7f B-3). A purely numeric value is still taken as an ID. Not found: nothing is written.
            $supplierModel = new SupplierModel();
            $supplier = $supplierModel->where('Sup_Acc_No', (string) $supplierAcc)->first();
            if (!$supplier && ctype_digit((string) $supplierAcc)) {
                $supplier = $supplierModel->where('ID', (int) $supplierAcc)->first();
            }
            if (!$supplier || trim((string) ($supplier['Sup_Acc_No'] ?? '')) === '') {
                return $this->response->setJSON(['status' => 409, 'message' => 'The supplier account of request #' . (int) $id . ' (' . $supplierAcc . ') is not in the supplier master. No voucher was created.']);
            }
            $supplierLCode = (string) $supplier['Sup_Acc_No'];

            // tf-paydesk-r7g: the request is claimed first (accepted -> paid) so that a repeated or simultaneous click cannot raise a
            // second voucher; the second click finds nothing to claim and is refused before a voucher number is used
            $tfDb->query("UPDATE payment_request SET payment_request = 2, complete_user = ?, complete_date = ? WHERE id = ? AND payment_request = 4 AND type = 'lunch'", [session()->get('username'), date('Y-m-d H:i:s'), (int) $id]);
            if ($tfDb->affectedRows() !== 1) {
                return $this->response->setJSON(['status' => 409, 'message' => 'Request #' . (int) $id . ' was paid by another request at this moment. It is never paid twice. Nothing was written.']);
            }
            $tfUnclaim = static function () use ($tfDb, $id) {
                $tfDb->query("UPDATE payment_request SET payment_request = 4, complete_user = NULL, complete_date = NULL WHERE id = ? AND payment_request = 2", [(int) $id]);
            };

            $invoiceNoModel = new InvoiceNumberModel();
            $vNum = $invoiceNoModel->incrementVoucherToken();

            $voucherData = [
                'Pay'         => $supplierName,
                'emp_code'    => $supplierLCode,
                'S_name'      => $supplierName,
                'Acc_No'      => $supplierLCode,
                'sup'         => '1',
                'type'        => 'Bank Transfer',
                'Amount'      => $amount,
                'VAT'         => '0',
                'VAT_Amt' => '0',
                'vat_m' => '0',
                'nbt'         => '0',
                'nbt_amt' => '0',
                'nbt_m' => '0',
                'g_ttl'       => $amount,
                'Address'     => '0',
                'print'       => '0',
                'des'         => '0',
                'W_Date'      => $payDate,
                'dv'          => '0',
                'app_user'    => session()->get('acc_no'),
                'login'       => session()->get('username'),
                'Aflag'       => '0',
                'v_typ'       => 'Bank Transfer',
                'ref_po'      => $tourId,
                'insflag'     => 1,
                'inv'         => '0',
                'Vnum'        => $vNum,
                'Bank'        => $bankName,
                'Bank_acc'    => $bankAcc,
                'C_Date'      => date('Y-m-d H:i:s'),
                'C_Number'    => $tourId,
                'is_expenses' => 1,
                'advances_pay' => 2,
                'V_num'       => $payDate,
                'doller_amt'  => $amount,
            ];

            $voucherInvData = [
                'Date'       => $payDate,
                'Invoice'    => $tourId,
                'Description' => 'Lunch Payment ' . $manualId,
                'Units'      => 0,
                'Rate'       => 0,
                'Amount'     => $amount,
                'InvAmount'  => $amount,
                'V_tkn'      => $vNum,
                'ityp'       => '1',
                'link_code'  => $supplierLCode,
                'employee'   => $supplierName,
                'login'      => session()->get('username'),
                'INV_Date'   => date('Y-m-d'),
                'Payee'      => $supplierName,
                'account'    => $supplierLCode,
                'ref'        => \App\Libraries\TourPayments\RequestLink::REF_PREFIX . (int) $id,   // tf-paydesk-r7h: the request this line pays
            ];

            $voucherPaymentModel = new VoucherPaymentModel();
            if ($voucherPaymentModel->insert($voucherData) === false) {   // tf-paydesk-r7g: a failed write is reported, and undone
                $tfUnclaim();
                return $this->response->setJSON(['status' => 500, 'message' => 'The voucher could not be written; request #' . (int) $id . ' is back to accepted. Voucher number ' . $vNum . ' is not used.']);
            }
            $tfVoucherWritten = true;

            $voucherInvoiceModel = new VoucherInvoiceModel();
            if ($voucherInvoiceModel->insert($voucherInvData) === false) {
                $tfDb->query("DELETE FROM voucher WHERE Vnum = ? AND Aflag = 0 AND ref_po = ?", [$vNum, $tourId]);
                $tfUnclaim();
                return $this->response->setJSON(['status' => 500, 'message' => 'The voucher line could not be written; the voucher was removed and request #' . (int) $id . ' is back to accepted. Voucher number ' . $vNum . ' is not used.']);
            }
            // tf-paydesk-r7g: the request was marked paid (payment_request = 2) by the claim above

            return $this->response->setJSON(['status' => 200, 'message' => 'Lunch payment voucher created successfully for ' . $supplierName . ' (' . $supplierLCode . ') — Payment Voucher No. ' . $vNum . '.']);
        } catch (\Exception $e) {
            if (isset($tfUnclaim) && empty($tfVoucherWritten)) {
                $tfUnclaim();   // tf-paydesk-r7g: claimed but no voucher written — the request goes back to accepted
            }
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // Duplicated from HotelConfirmationController::confirm_reservation() and adjusted to take
    // plain parameters instead of reading $this->request (this flow has no uploaded file/reference —
    // confirmation proof stays a manual-page-only concept, so those two fields are left null here).
    // Writes the same tour_quotations columns, flips payment_request, and posts the same
    // account_runing Dr/Cr pair (4-8-106-10386 / hotel L_Code, advances_pay=22) so every accept
    // path (manual confirmation page and the automated "Request" click here) leaves identical data.
    private function confirmHotelReservation($tourId, $hotel, $dates, $lkrPayment, $dollarRate, $dollarCount, $username, $dateNumber = null, $paymentRequestId = null)
    {
        $tourQuotationsModel = new TourQuotationsModel();

        $data = [
            'hotel_advance' => $lkrPayment,
            'booking_confirmed_flag' => 1,
            'confirmation_email' => null,
            'confirmation_reference' => null,
            'total_hotel_doller' => $dollarCount,
            'extra_hotel_fee' => 0,
            'sglfoc' => 0,
            'dblfoc' => 0,
            'tplfoc' => 0,
            'hotel_dollar_rate' => $dollarRate,
        ];

        $lastDate = null;
        foreach ($dates as $date) {
            $date = date('Y-m-d', strtotime(trim($date)));
            $lastDate = $date;
            $tourQuotationsModel->set($data)
                ->where('tour_date', $date)
                ->where('quotation_id', $tourId)
                ->where('hotel_acc', $hotel)
                ->update();
        }

        $invoiceNoModel = new InvoiceNumberModel();
        $acc_id = $invoiceNoModel->incrementAccId();

        $accountRunningModel = new AccountRunningModel();

        $accountRunningModel->insertBatch([
            [
                'ID' => $acc_id,
                'L_Code' => '4-8-106-10386', // Hotel Charges
                'Date' => $lastDate,
                'Discription' => 'Hotel Reservation ' . $tourId . ' / ' . $lastDate,
                'Dr' => $lkrPayment,
                'Cr' => 0.00,
                'Cashier' => $username,
                'index_no' => $tourId,
                'job' => '4-8-106-10386',
                'Invoice_Num' => $tourId,
                'advances_pay' => 22,
                'Dt' => date('Y-m-d H:i:s'),
                'dollarrate' => $dollarRate,
                'dollar' => $dollarCount,
                'P_Cap' => $dateNumber,
            ],
            [
                'ID' => $acc_id,
                'L_Code' => $hotel,
                'Date' => $lastDate,
                'Discription' => 'Hotel Reservation ' . $tourId . ' / ' . $lastDate,
                'Dr' => 0.00,
                'Cr' => $lkrPayment,
                'Cashier' => $username,
                'index_no' => $tourId,
                'job' => '4-8-106-10386',
                'Invoice_Num' => $tourId,
                'advances_pay' => 22,
                'Dt' => date('Y-m-d H:i:s'),
                'dollarrate' => $dollarRate,
                'dollar' => $dollarCount,
                'P_Cap' => $dateNumber,
            ],
        ]);

        // tf-payrev-r1 (5 Nov 2026, Shantha): the journal this request posted is recorded ON the request
        // at the moment it is written. A rejection reverses that journal and no other. Nothing is ever
        // matched back by tour, hotel, position or amount.
        if ($paymentRequestId) {
            \Config\Database::connect()->query(
                'UPDATE payment_request SET ledger_journal_id = ? WHERE id = ? AND ledger_journal_id IS NULL',
                [(int) $acc_id, (int) $paymentRequestId]
            );
        }

        return (int) $acc_id;
    }

    /**
     * tf-payauth-r1 (13 Oct 2026): the older payment actions check the payment role themselves, as the payment desk does.
     * A menu, the old "starts with" menu rule or a hidden link is not enough: a direct request without the role is refused
     * (403, nothing changed) and recorded like the payment desk's own refusals. $roles: any one of them is enough.
     */
    private function tfPayRole(array $roles, string $what)
    {
        foreach ($roles as $r) {
            if (\App\Libraries\TourPayments\Perms::can($r)) {
                return null;
            }
        }
        $msg = $what . ' needs a payment role (' . implode(' or ', $roles) . '). Nothing was changed.';
        try {
            \App\Libraries\TourPayments\Audit::log(\Config\Database::connect(), ['action' => 'access_denied', 'ref_type' => 'action',
                'ref_id' => mb_substr((string) service('uri')->getRoutePath(), 0, 80), 'reason' => mb_substr('tf-payauth-r1: ' . $msg, 0, 500),
                'new' => ['method' => strtoupper($this->request->getMethod()), 'roles' => $roles]]);
        } catch (\Throwable $e) {
        }
        return $this->response->setStatusCode(403)->setJSON(['status' => 403, 'message' => $msg . ' The attempt was recorded.', 'rule' => 'payment_role']);
    }

    /**
     * tf-payauth-r1: never on the user's own payment request — the payment desk's separation of duties (Separation::check;
     * a refusal is recorded there as separation_blocked). $step: approve | batch | paid.
     */
    private function tfNotOwn(array $ids, string $step = 'approve')
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return null;
        }
        $sep = \App\Libraries\TourPayments\Separation::check(\Config\Database::connect(), $step, $ids);
        return $sep ? $this->response->setStatusCode(403)->setJSON(['status' => 403, 'message' => $sep, 'separation' => true, 'rule' => 'separation']) : null;
    }
}
