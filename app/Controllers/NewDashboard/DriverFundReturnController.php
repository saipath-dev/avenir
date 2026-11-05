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
use App\Models\VoucherInvoiceModel;
use App\Models\InvoiceNumberModel;
use App\Models\DriverAdvanceRequestModel;

class DriverFundReturnController extends BaseController
{

    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('tour_id');
        $driver_id = $this->request->getGet('driver_id');
        $amount = $this->request->getGet('amount');

        $data['tile_dashboard'] = true;

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
        $data['driver_id'] = $driver_id;
        $data['amount'] = $amount;
        $data['tour_main_details'] = $tour_main_details;

        return view('pages/dashboard2/transport/transport_fund_return', $data);
    }

    public function get_refund_history()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');
            $agent = $this->request->getPost('agent');

            $accountRunningModel = new AccountRunningModel();

            // Get refund records for changing transport agent
            $refund_history = $accountRunningModel
                ->select('account_runing.*, account_runing.Dr as amount, account_runing.Date as payment_date')
                ->where('index_no', $tour_id)
                ->where('L_Code', $agent)
                ->where('account_runing.Cr >', 0)
                ->groupStart()
                    ->where('advances_pay', 1111)
                    ->orWhere('advances_pay', 11111)
                ->groupEnd()
                ->Like('Discription', 'Driver Fund Return%')
                ->orderBy('Dt', 'DESC')
                ->findAll();

                // log_message('critical', $accountRunningModel->getLastQuery());

            return $this->response->setJSON(['status' => 200, 'data' => $refund_history]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function submit_fund_return()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $ref = $this->request->getPost('ref_no');
            $agent = $this->request->getPost('agent');
            $agent_name = $this->request->getPost('agent_name');
            $pay = $this->request->getPost('payment');
            $reason = $this->request->getPost('reason');
            $payment_type = $this->request->getPost('payment_type');
            $bank = $this->request->getPost('bank_acc');
            $bank_name = $this->request->getPost('bank_name');
            $date = $this->request->getPost('advance_date');
            $manu_id = $this->request->getPost('manu_id');

            $ConsigneeModel = new ConsigneeModel();
            $consigneeData = $ConsigneeModel->select('tour_cancel')->where('tour_id', $tour)->first();

            if ($consigneeData && $consigneeData['tour_cancel'] == 1) {
                return json_encode(['status' => 400, 'message' => 'Cannot add fund return. The tour has been cancelled.']);
            }

            if (!is_numeric($pay)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            if ($pay <= 0) {
                return json_encode(['status' => 400, 'message' => 'Amount must be greater than zero']);
            }

            // Determine Dr L_Code based on payment type
            $dr_lcode = '';
            switch ($payment_type) {
                case 'Cash':
                    $dr_lcode = '1-2-3-10900';
                    $advances_pay = 1111;
                    break;
                case 'Bank Transfer':
                    $dr_lcode = $bank; // Use selected bank account
                    $advances_pay = 1111;
                    break;
                case 'Over Payment':
                    $dr_lcode = '1-2-108-10382';
                    $advances_pay = 11111;
                    break;
                default:
                    return json_encode(['status' => 400, 'message' => 'Invalid payment type']);
            }

            $session = session();
            $username = $session->get('username');

            if ($pay > 0) {
                $invoiceNoModel = new InvoiceNumberModel();
                $acc_id = $invoiceNoModel->incrementAccId();

                $AccountRunningModel = new AccountRunningModel();

                // Dr Record - Cash/Bank/Over Payment account
                $AccountRunningModel->insert([
                    'ID' => $acc_id,
                    'L_Code' => $dr_lcode,
                    'Dr' => $pay,
                    'Cr' => 0.00,
                    'Discription' => 'Driver Fund Return - ' . $payment_type . ' - ' . $manu_id . ' - ' . $reason,
                    'Cashier' => session()->get('username'),
                    'Date' => $date,
                    'Invoice_Num' => $tour,
                    'DV' => 1,
                    'aflag' => 1,
                    'index_no' => $tour,
                    'P_Month' => date('Y-m'),
                    'Dt' => date('Y-m-d H:i:s'),
                    'Recpt_No' => $ref,
                    'advances_pay' => $advances_pay,
                    'payee' => $agent_name
                ]);

                // log_message("critical", $AccountRunningModel->db->getLastQuery());

                // Cr Record - Driver account
                $AccountRunningModel->insert([
                    'ID' => $acc_id,
                    'L_Code' => $agent, // Driver account
                    'Dr' => 0.00,
                    'Cr' => $pay,
                    'Discription' => 'Driver Fund Return - ' . $payment_type . ' - ' . $manu_id . ' - ' . $reason,
                    'Cashier' => session()->get('username'),
                    'Date' => $date,
                    'Invoice_Num' => $tour,
                    'DV' => 1,
                    'aflag' => 1,
                    'index_no' => $tour,
                    'P_Month' => date('Y-m'),
                    'Dt' => date('Y-m-d H:i:s'),
                    'Recpt_No' => $ref,
                    'advances_pay' => $advances_pay,
                    'payee' => $agent_name
                ]);

                // log_message("critical", $AccountRunningModel->db->getLastQuery());

                if ($AccountRunningModel->db->affectedRows() > 0) {
                    return $this->response->setJSON(["status" => 200, "message" => "Fund return recorded successfully!"]);
                } else {
                    return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                }
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

}
