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
            $allDates[] = $range[0]; // Start date
            $allDates[] = $range[1]; // End date
        }

        // Find the minimum and maximum dates
        $minDate = min($allDates);
        $maxDate = max($allDates);




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

            $tour['hotels'] = $tourQuotationModel
                ->groupBy('tour_quotations.hotel_name, tour_quotations.hotel_acc')
                ->orderBy('tour_quotations.tour_date', 'ASC')
                ->findAll();

            // Load direct payment (lunch) activities for this tour
            $db = \Config\Database::connect();
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
                ->join('supplier', 'supplier.ID = tour_activity.supplier_id', 'left')
                ->where('tour_activity.tour_id', $tour['tour_id'])
                ->where('tour_activity.supplier_id IS NOT NULL', null, false)
                ->orderBy('tour_activity.tour_date', 'ASC')
                ->get()->getResultArray();

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
            }
        }

        return view('pages/tour/tour_accounts_note', $data);
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
                return json_encode(['status' => 200, 'message' => 'Debit note saved successfully']);
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

        // Retrieve data from AJAX request
        $tourId = $this->request->getVar('tourId');
        $hotelAcc = $this->request->getVar('hotelAcc');
        $manualId = $this->request->getVar('manualId');
        $consignee = $this->request->getVar('consignee');
        $createUser = $this->request->getVar('createUser');
        $startDate = $this->request->getVar('startDate');
        $endDate = $this->request->getVar('endDate');
        $hotelName = $this->request->getVar('hotelName');
        $bankName = $this->request->getVar('bankName');
        $branchName = $this->request->getVar('branchName');
        $supBankAcc = $this->request->getVar('supBankAcc');
        $totalHotelPay = $this->request->getVar('totalHotelPay');
        $totalHotelAdvance = $this->request->getVar('totalHotelAdvance');
        $dueAmount = $this->request->getVar('dueAmount');
        $requestAmount = $this->request->getVar('requestAmount');
        $tot_doller = $this->request->getVar('tot_amt_doller');

        $type = $this->request->getVar('type');
        $activityId = $this->request->getVar('activityId');

        // Attempt to update records where Aflag = '1'
        try {
            if ($type === 'lunch') {
                // Update tour_activity payment_request flag
                $TourActivityModel = new \App\Models\TourActivityModel();
                $TourActivityModel->update($activityId, [
                    'payment_request' => 1,
                    'payment_request_user' => session()->get('username'),
                    'payment_request_date' => date('Y-m-d'),
                ]);
            } else {
                // Load the model
                $TourQuotationsModel = new TourQuotationsModel();
                // Perform the update
                $affectedRows = $TourQuotationsModel->where('quotation_id', $tourId)->where('hotel_acc', $hotelAcc)
                    ->set([
                        'payment_request' => 1,
                        'payment_request_user' => session()->get('username'),
                        'payment_request_date' => date('Y-m-d H:i:s')
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
                'payment_request' => 1,
                'payment_request_user' => session()->get('username'),
                'payment_request_date' => date('Y-m-d H:i:s'),
                'type' => $type ?: null,
                'is_bulk' => $isBulk['bulk_payment'],
            ];

            // Insert into database
            $PaymentRequestModel->insert($data);

            // Check if any rows were updated
            if ($type === 'lunch' || $affectedRows > 0) {
                // If successful, return a success message
                return json_encode(['status' => 200, 'message' => 'Debit note saved successfully']);
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
                return json_encode(['status' => 200, 'message' => 'Debit note saved successfully']);
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
        $requestAmount = $this->request->getVar('requestAmount');


        // Load the model
        $ConsigneeModel = new ConsigneeModel();

        $PaymentRequestModel = new PaymentRequestModel();

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
                return json_encode(['status' => 200, 'message' => 'Debit note saved successfully']);
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
        $requestAmount = $this->request->getVar('requestAmount');

        $DriveradvanceRequestModel = new DriverAdvanceRequestModel();
        $advanceReqDetails = $DriveradvanceRequestModel->where('id', $req_row_id)->first();
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
                'requestAmount' => $requestAmount
            ];
            // Insert into database
            $PaymentRequestModel->insert($data);

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
                return json_encode(['status' => 200, 'message' => 'Debit note saved successfully']);
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

        $data['paymentRequests'] = $baseQuery->get()->getResultArray();

        // Get tour info from consignee table for accurate dates
        $consigneeModel = new \App\Models\ConsigneeModel();
        $data['tourInfo'] = $consigneeModel->where('manual_id', $tour)->first();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        return view('pages/tour/payment_requested_report', $data);
    }

    public function cancel_payment_request()
    {

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

                $grouped_expenses[$tour_id] = [
                    'tour_id' => $tour_id,
                    'manual_id' => $tourInfo['manual_id'] ?? '-',
                    'consignee' => $tourInfo['consignee'] ?? '-',
                    'start_date' => $tourInfo['start_date'] ?? null,
                    'end_date' => $tourInfo['end_date'] ?? null,
                    'total_transport_cost' => $tourInfo['total_transport_cost'] ?? 0,
                    'drivers' => [],
                    'total_dollar' => 0,
                    'total_lkr' => 0
                ];
            }

            // Initialize driver info if not exists
            if (!isset($grouped_expenses[$tour_id]['drivers'][$agent_code])) {
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
                $hasLogSheet = false;
                if ($logSheet && $logSheet['log_sheet_mileage'] > 0) {
                    // Calculate from log sheet data
                    $logSheetAmount = ($logSheet['log_sheet_mileage'] * $logSheet['vehicle_rate']) +
                                     ($logSheet['per_day_rate'] * $logSheet['no_of_dates']);
                    $hasLogSheet = true;
                }

                $grouped_expenses[$tour_id]['drivers'][$agent_code] = [
                    'driver_code' => $agent_code,
                    'driver_name' => $expense['agent_name'] ?? '-',
                    'advance_amount' => $advancePayment['advance_amount'] ?? 0,
                    'log_sheet_amount' => $logSheetAmount,
                    'has_log_sheet' => $hasLogSheet,
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
            foreach ($tour['drivers'] as $driver) {
                if (!$driver['has_log_sheet']) {
                    $hasAllLogSheets = false;
                    break;
                }
            }
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
    public function approved_expenses_report($manual_id = null)
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
            return redirect()->to(base_url('tour/tour_accounts/approved_expenses_dashboard'));
        }

        $tour_id = $data['tourInfo']['tour_id'];

        // Get all approved expenses for this tour
        $data['approvedExpenses'] = $driverExpensesModel
            ->where('tour_id', $tour_id)
            ->where('status', 'approved')
            ->orderBy('expense_date', 'ASC')
            ->findAll();

        // Get driver allocations for this tour
        $driverAllocations = $driverAllocationModel
            ->where('tour_id', $tour_id)
            ->findAll();

        // Group expenses and calculations by driver
        $driverSummary = [];
        $totalLogSheetAmount = 0;
        $totalAdvanceAmount = 0;
        $totalExpensesAmount = 0;
        $totalPayableAmount = 0;
        $hasLogSheetMileage = false; // Track if any driver has log sheet mileage

        foreach ($driverAllocations as $allocation) {
            $agent_code = $allocation['driver']; // driver column contains the agent code
            $driver_name = $allocation['driver_name'] ?? '-';

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
            $driverHasLogSheet = false;
            if ($logSheet && $logSheet['log_sheet_mileage'] > 0) {
                $logSheetAmount = ($logSheet['log_sheet_mileage'] * $logSheet['vehicle_rate']) +
                                 ($logSheet['per_day_rate'] * $logSheet['no_of_dates']);
                $hasLogSheetMileage = true;
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

            // Payable = Log Sheet Amount - (Advance - Expenses)
            $payableAmount = $logSheetAmount - ($advanceAmt - $expensesLKR);

            $driverSummary[$agent_code] = [
                'driver_code' => $agent_code,
                'driver_name' => $driver_name,
                'log_sheet_amount' => $logSheetAmount,
                'advance_amount' => $advanceAmt,
                'expenses_amount' => $expensesLKR,
                'payable_amount' => $payableAmount,
                'has_log_sheet' => $driverHasLogSheet
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
        $data['hasLogSheetMileage'] = $hasLogSheetMileage;

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

        return view('pages/tour/approved_expenses_report', $data);
    }

    /**
     * Accept approved expenses and post to account_running table
     */
    public function accept_approved_expenses()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');

            if (empty($tour_id)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour ID is required']);
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

            // Get tour info for transport agent update
            $consigneeModel = new ConsigneeModel();
            $tourInfo = $consigneeModel->where('tour_id', $tour_id)->first();

            $postedCount = 0;

            // Post each expense individually to account_running
            foreach ($approvedExpenses as $expense) {
                // Update transport agent if different
                $current_t_agent = $tourInfo['transport_agent'] ?? '';
                if ($current_t_agent != $expense['agent']) {
                    $consigneeModel->where('tour_id', $tour_id)->set([
                        'transport_agent' => $expense['agent'],
                        'transport_agent_name' => $expense['agent_name']
                    ])->update();
                }

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

            return $this->response->setJSON(['status' => 200, 'message' => $postedCount . ' expense(s) accepted and posted to accounts successfully.']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject approved expenses and send back for review
     */
    public function reject_approved_expenses()
    {
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

            $supplierAcc = $request['hotel_acc'];
            $supplierName = $request['hotel_name'];
            $bankName = $this->request->getPost('bank_name') ?: $request['bank_name'];
            $bankAcc = $this->request->getPost('bank_acc') ?: $request['sup_bank_acc'];
            $manualId = $request['manual_id'];

            // Get supplier L_Code
            $supplierModel = new SupplierModel();
            $supplier = $supplierModel->where('ID', $supplierAcc)->first();
            $supplierLCode = $supplier['Sup_Acc_No'] ?? $supplierAcc;

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
                'VAT'         => '0', 'VAT_Amt' => '0', 'vat_m' => '0',
                'nbt'         => '0', 'nbt_amt' => '0', 'nbt_m' => '0',
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
                'advances_pay'=> 2,
                'V_num'       => $payDate,
                'doller_amt'  => $amount,
            ];

            $voucherInvData = [
                'Date'       => $payDate,
                'Invoice'    => $tourId,
                'Description'=> 'Lunch Payment ' . $manualId,
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
            ];

            $voucherPaymentModel = new VoucherPaymentModel();
            $voucherPaymentModel->insert($voucherData);

            $voucherInvoiceModel = new VoucherInvoiceModel();
            $voucherInvoiceModel->insert($voucherInvData);

            // Mark payment_request as complete (payment_request = 2)
            $PaymentRequestModel->update($id, [
                'payment_request' => 2,
                'complete_user'   => session()->get('username'),
                'complete_date'   => date('Y-m-d H:i:s'),
            ]);

            return $this->response->setJSON(['status' => 200, 'message' => 'Lunch payment voucher created successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
