<?php

namespace App\Controllers\NewDashboard;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\ConsigneeModel;
use App\Models\SupplierModel;
use App\Models\DriverAllocationModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\PaymentRequestModel;
use App\Models\VoucherPaymentModel;
use App\Models\LogSheetMileModel;
use App\Models\DriverAllocationAuditLogModel;
use App\Models\DriverSettlementRequestModel;
use App\Models\VoucherInvoiceModel;
use App\Models\InvoiceNumberModel;



class DriverPaymentSettlementController extends BaseController
{

    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('tour_id');
        $driver = $this->request->getGet('driver');

        $data['tile_dashboard'] = true;
        $data['selected_driver'] = $driver;

        $SupplierModel = new SupplierModel();
        $data['transport_agent'] = $SupplierModel->where('sup_cat', 2)->findAll();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $data['agents'] = [];

        $tourConsigneeModel = new ConsigneeModel();

        $tour_condition = $tourConsigneeModel->select('hotel_only')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $driverallocationModel = new DriverAllocationModel();
        $data['drivers'] = $driverallocationModel->where('tour_id', $tour_id)->findAll();

        $PaymentRequestModel = new PaymentRequestModel();
        $data['advance_payment_requests'] = $PaymentRequestModel->where('tour_id', $tour_id)->where('hotel_acc', 'TRANSPORT')->findAll();

        $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();
        $data['tour_id'] = $tour_id;
        // $data['type'] = $type;
        $data['tour_main_details'] = $tour_main_details;

        return view('pages/dashboard2/transport/transport_settlement', $data);
    }

    // Get Transport Balance Payment Details
    public function get_balance_payment_details()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');
            $agent = $this->request->getPost('agent');
            $agent_name = $this->request->getPost('agent_name');

            $accountrunningModel = new AccountRunningModel();
            $voucherpaymentmodel  = new VoucherPaymentModel();
            $logsheetmileModel = new LogSheetMileModel();
            $logsheetmile = $logsheetmileModel->where('tour_id', $tour_id)->first();

            $advance_payment = $voucherpaymentmodel->select('sum(Amount) as advance_dr')->where('ref_po', $tour_id)->where('Acc_No', $agent)->where('Aflag', 1)->where('advances_pay', 1)->first();

            $advance_cr = $accountrunningModel->select('sum(account_runing.Cr) as advance_cr')
                ->where('index_no', $tour_id)
                ->where('L_Code', $agent)
                ->where('advances_pay', 1111)
                ->first();

            // log_message('critical', $voucherpaymentmodel->getLastQuery());
            // $balance = ($advance_payment['advance_dr'] ?? 0) - ($advance_cr['advance_cr'] ?? 0);

            $total_settled = $accountrunningModel->select('sum(account_runing.Cr) as ad_remain_cr')
                ->where('index_no', $tour_id)
                ->where('L_Code', $agent)
                ->where('account_runing.Cr >', 0.00)
                ->groupStart()
                ->Like('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->orLike('account_runing.Discription', 'JOURNAL ENTRY%')
                ->groupEnd()
                ->Where('account_runing.advances_pay', 11)
                ->first();

            // log_message('critical', $accountrunningModel->getLastQuery());

            $total_settled_voucher = $accountrunningModel->select('sum(account_runing.Cr) as total_pay_cr')
                ->where('index_no', $tour_id)
                ->where('payee', $agent_name)
                ->where('account_runing.Cr >', 0.00)
                // ->Like('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->groupStart()
                ->Like('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->orLike('account_runing.Discription', 'JOURNAL ENTRY%')
                ->groupEnd()
                ->Where('account_runing.advances_pay', 11)
                ->first();

            // log_message('critical', $accountrunningModel->getLastQuery());

            $total_expenses = $accountrunningModel->select('sum(account_runing.Cr) as expenses_cr')
                ->where('index_no', $tour_id)
                ->where('L_Code', $agent)
                ->where('account_runing.Cr >', 0.00)
                ->notLike('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->Where('account_runing.advances_pay', 111)
                ->first();

            if ($logsheetmile == null) {

                $consigneeModel = new ConsigneeModel();
                $consignee = $consigneeModel->select('total_transport_cost, total_mileage, vehicle_rate, bata_day_rate, bata_days, log_sheet_mileage')->where('tour_id', $tour_id)->first();
            } else {

                $logsheetdetails = $logsheetmileModel->select('log_sheet_mileage,estimate_mileage,vehicle_rate,per_day_rate,no_of_dates as no_of_dates')->where('tour_id', $tour_id)->where('transport_agent', $agent)->orderby('id', 'DESC')->first();
                // log_message('critical', $logsheetmileModel->getLastQuery());

                $consignee = [
                    'total_transport_cost' => $logsheetdetails['vehicle_rate'] * $logsheetdetails['log_sheet_mileage'] + ($logsheetdetails['per_day_rate'] * $logsheetdetails['no_of_dates']),
                    'total_mileage' => $logsheetdetails['log_sheet_mileage'],
                    'vehicle_rate' => $logsheetdetails['vehicle_rate'],
                    'bata_day_rate' => $logsheetdetails['per_day_rate'],
                    'bata_days' => $logsheetdetails['no_of_dates'],
                    'log_sheet_mileage' => $logsheetdetails['log_sheet_mileage'],
                    'estimate_mileage' => $logsheetdetails['estimate_mileage']
                ];
            }


            $voucherpaymentmodel  = new VoucherPaymentModel();
            $voucherpayment = $voucherpaymentmodel->where('ref_po', $tour_id)->where('advances_pay', 11)->where('Acc_No', $agent)->findAll();

            // merge the two arrays
            $data = array_merge($advance_payment, $advance_cr, $total_expenses, $consignee, $total_settled, $total_settled_voucher);
            // log_message('critical', print_r($data, true));

            return $this->response->setJSON(['status' => 200, 'data' => $data, 'voucherpayment' => $voucherpayment]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }


    public function complete_payment()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $transagent = $this->request->getPost('transagent');
            $transagent_name = $this->request->getPost('transagent_name');
            $bank_account = $this->request->getPost('bank_account');
            $pay_metghod = $this->request->getPost('pay_metghod');
            $tot_trans_cost = $this->request->getPost('tot_trans_cost');
            $remaining_balance = $this->request->getPost('remaining_balance');
            $mamual_id = $this->request->getPost('mamual_id');
            // $remaining_balance = abs($remaining_balance);

            $amount = $this->request->getPost('cash');
            $pay_date = $this->request->getPost('pay_date');

            $ConsigneeModel = new ConsigneeModel();
            $consigneeData = $ConsigneeModel->select('tour_cancel')->where('tour_id', $tour)->first();

            if ($consigneeData && $consigneeData['tour_cancel'] == 1) {
                return json_encode(['status' => 400, 'message' => 'Cannot add payment. The tour has been cancelled.']);
            }


            if ($transagent == null || $tour == null || $amount == null || $bank_account == null || $pay_date == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($amount)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            // get tour
            $consigneeModel = new ConsigneeModel();
            $tourx = $consigneeModel->where('tour_id', $tour)->first();
            $current_t_agent = $tourx['transport_agent'];

            if ($current_t_agent != $transagent) {
                $consigneeModel->where('tour_id', $tour)->set(['transport_agent' => $transagent, 'transport_agent_name' => $transagent_name])->update();
            }

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $accountRunningModel = new AccountRunningModel();

            $accountRunningData = [
                [
                    'ID' => $acc_id,
                    'L_Code' => '4-8-106-10385', // transport Cost Expenses Account
                    'Dr' => $remaining_balance,
                    'Cr' => 0.00,
                    'Discription' => 'Transport Payment Settled ' . $mamual_id,
                    'Date' => $pay_date,
                    'status' => '',
                    'Cashier' => session()->get('username'),
                    'Invoice_Num' => '',
                    'index_no' => $tour, // customer id
                    'advances_pay' => 11,
                    'fine_date' => date('Y-m-d'),
                    'Dt' => date('Y-m-d H:i:s')
                ],
                [
                    'ID' => $acc_id,
                    'L_Code' => $transagent, // Transport Agent
                    //'L_Code' => $bank_account, // Bank Account
                    'Dr' => 0.00,
                    'Cr' => $remaining_balance,
                    'Discription' => 'Transport Payment Settled ' . $mamual_id,
                    'Date' => $pay_date,
                    'status' => '',
                    'Cashier' => session()->get('username'),
                    'Invoice_Num' => '',
                    'index_no' => $tour, // customer id
                    'advances_pay' => 11,
                    'fine_date' => date('Y-m-d'),
                    'Dt' => date('Y-m-d H:i:s')
                ]
            ];

            $insertResult = $accountRunningModel->insertBatch($accountRunningData);
            if ($insertResult) {

                //Voucher Authentification Record
                $model_inv = new InvoiceNumberModel();
                $inv = $model_inv->incrementVoucherToken();
                $v_num = $inv;

                $data = [
                    'Pay' => $transagent_name,  //Transport Agent name 
                    'emp_code' => $transagent,  //Transport Agent acc_no
                    'S_name' => $transagent_name, //Transport Agent name 
                    'Acc_No' => $transagent, //Transport Agent acc_no
                    'sup' => '1',
                    'type' => 'Bank Transfer', //BT
                    'Amount' => $amount,
                    'VAT' => '0',
                    'VAT_Amt' => '0',
                    'vat_m' => '0',
                    'nbt' => '0',
                    'nbt_amt' => '0',
                    'nbt_m' => '0',
                    'g_ttl' => $amount,
                    'Address' => '0',
                    'print' => '0',
                    'des' => '0',
                    'W_Date' => $pay_date,
                    'dv' => '0',
                    'app_user' => session()->get('acc_no'),
                    'login' =>  session()->get('username'),
                    'Aflag' => '0',
                    'v_typ' => 'Bank Transfer',
                    'ref_po' => $tour,
                    'insflag' => 1,
                    'inv' => '0',
                    'Vnum' => $v_num,   // voucher token
                    'Bank' => $pay_metghod,
                    'Bank_acc' => $bank_account,
                    'C_Date' => date('Y-m-d H:i:s'),
                    'C_Number' => $tour,
                    'acc_run_id' => $acc_id,
                    'is_expenses' => 1,
                    'advances_pay' => 11,
                    'doller_amt' => $amount
                ];


                $data_add = [
                    'Date' => $pay_date,
                    'Invoice' => $tour,
                    'Description' => 'Transport Confimation ' . $mamual_id,
                    'Units' =>  0,
                    'Rate' => 0,
                    'Amount' => $amount,
                    'InvAmount' => $amount,
                    'V_tkn' => $v_num,   // voucher_token
                    'ityp' => '1',
                    'link_code' => $transagent,
                    'employee' => $transagent_name,
                    'login' =>  session()->get('username'),
                    'INV_Date' => date('Y-m-d'),
                    'Payee' => $transagent_name,
                    'acc_run_id' => $acc_id,
                    'account' => '4-8-106-10385',
                ];

                $voucherpaymentmodel  = new VoucherPaymentModel();
                $v_invmodel = new VoucherInvoiceModel();

                $v_invmodel->insert($data_add);
                $voucherpaymentmodel->insert($data);

                return json_encode(['status' => 200, 'message' => 'Payment completed successfully']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Payment failed']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function edit_total_transport()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $total_transport_cost = $this->request->getPost('total_transport_cost2');
            $total_mileage = $this->request->getPost('total_mile');
            $vehicle_rate = $this->request->getPost('vehicle_rate');
            $bata_day_rate = $this->request->getPost('per_dat_rate');
            $bata_days = $this->request->getPost('no_od_date');

            if ($tour == null || $total_transport_cost == null || $total_mileage == null || $vehicle_rate == null || $bata_day_rate == null || $bata_days == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($total_transport_cost) || !is_numeric($total_mileage) || !is_numeric($vehicle_rate) || !is_numeric($bata_day_rate) || !is_numeric($bata_days)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            $consigneeModel = new ConsigneeModel();
            $data = [
                'total_transport_cost' => $total_transport_cost
            ];

            $updateResult = $consigneeModel->where('tour_id', $tour)->set($data)->update();
            if ($updateResult) {
                return json_encode(['status' => 200, 'message' => 'Successfully updated']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Update failed']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function driversettlementrequest()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('tour_id');

        $data['tile_dashboard'] = true;

        $SupplierModel = new SupplierModel();
        $data['transport_agent'] = $SupplierModel->where('sup_cat', 2)->findAll();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $tourConsigneeModel = new ConsigneeModel();

        $tour_condition = $tourConsigneeModel->select('hotel_only')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $driverallocationModel = new DriverAllocationModel();
        $data['drivers'] = $driverallocationModel->where('tour_id', $tour_id)->findAll();

        $PaymentRequestModel = new PaymentRequestModel();
        $data['advance_payment_requests'] = $PaymentRequestModel->where('tour_id', $tour_id)->where('hotel_acc', 'TRANSPORT')->findAll();

        $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();
        $data['tour_id'] = $tour_id;
        // $data['type'] = $type;
        $data['tour_main_details'] = $tour_main_details;

        return view('pages/dashboard2/transport/transport_settlement_request', $data);
    }

    public function driversettlementrequestsend()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $transagent = $this->request->getPost('transagent');
            $transagent_name = $this->request->getPost('transagent_name');
            $bank_account = $this->request->getPost('bank_account');
            $pay_metghod = $this->request->getPost('pay_metghod');
            $tot_trans_cost = $this->request->getPost('tot_trans_cost');
            $remaining_balance = $this->request->getPost('remaining_balance');
            $mamual_id = $this->request->getPost('mamual_id');
            // $remaining_balance = abs($remaining_balance);

            $amount = $this->request->getPost('cash');
            $pay_date = $this->request->getPost('pay_date');

            $ConsigneeModel = new ConsigneeModel();
            $consigneeData = $ConsigneeModel->select('tour_cancel')->where('tour_id', $tour)->first();

            if ($consigneeData && $consigneeData['tour_cancel'] == 1) {
                return json_encode(['status' => 400, 'message' => 'Cannot add payment. The tour has been cancelled.']);
            }


            if ($transagent == null || $tour == null || $amount == null || $pay_date == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($amount)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            // get tour
            $consigneeModel = new ConsigneeModel();
            $tourx = $consigneeModel->where('tour_id', $tour)->first();
            $current_t_agent = $tourx['transport_agent'];

            if ($current_t_agent != $transagent) {
                $consigneeModel->where('tour_id', $tour)->set(['transport_agent' => $transagent, 'transport_agent_name' => $transagent_name])->update();
            }

            $data_add = [
                'Dr_L_Code' => '4-8-106-10385',  // transport Cost Expenses Account
                'Cr_L_Code' => $transagent, // Transport Agent
                'tour_id' => $tour,
                'manual_id' => $mamual_id,
                'pay_date' => $pay_date,
                'Dt' => date('Y-m-d H:i:s'),
                'remaining_balance' => $remaining_balance,
                'transagent_name' => $transagent_name,   // voucher_token
                'transagent' => $transagent,
                'amount' => $amount,
                'status' => 0,
                'request_user' =>  session()->get('username')
            ];

            $driversettlementrequestmodel  = new DriverSettlementRequestModel();

            if ($driversettlementrequestmodel->insert($data_add)) {
                return json_encode(['status' => 200, 'message' => 'Settlement request sent successfully']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Settlement request failed']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }
}
