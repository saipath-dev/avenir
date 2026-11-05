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



class DriverExpensesController extends BaseController
{

    public function index()
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

        return view('pages/dashboard2/transport/transport_expenses', $data);
    }

    public function add_expenses()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $agent = $this->request->getPost('tagent');
            $agent_name = $this->request->getPost('agent_name');
            $category = $this->request->getPost('category');
            $supplier = $this->request->getPost('supplier');
            $supplier_name = $this->request->getPost('supplier_name');
            $inv = $this->request->getPost('inv');
            $date = $this->request->getPost('date');
            $dollerrate = $this->request->getPost('dollerrate');
            $amount = $this->request->getPost('amount');
            $rsamount = $this->request->getPost('rsamount');

            $ConsigneeModel = new ConsigneeModel();
            $consigneeData = $ConsigneeModel->select('tour_cancel')->where('tour_id', $tour)->first();

            if ($consigneeData && $consigneeData['tour_cancel'] == 1) {
                return json_encode(['status' => 400, 'message' => 'Cannot add payment. The tour has been cancelled.']);
            }

            if ($supplier == null || $tour == null || $amount == null || $date == null || $inv == null || $rsamount == null || $agent == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($amount)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            // get tour
            $consigneeModel = new ConsigneeModel();
            $tourx = $consigneeModel->where('tour_id', $tour)->first();
            $current_t_agent = $tourx['transport_agent'];

            if ($current_t_agent != $agent) {
                $consigneeModel->where('tour_id', $tour)->set(['transport_agent' => $agent, 'transport_agent_name' => $agent_name])->update();
            }

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $accountRunningModel = new AccountRunningModel();

            if ($category == '6-9-23-10640' || $category == '6-9-23-10641') { // Entrance Fee Expenses, Other Expenses

                if ($category == '6-9-23-10640') {
                    $description = 'Entrance Fee Expenses';
                } else {
                    $description = 'Other Expenses';
                }

                $accountRunningData = [
                    [
                        'ID' => $acc_id,
                        'L_Code' => $supplier,
                        'Date' => $date,
                        'Discription' => $description,
                        'Dr' => $rsamount,
                        'Cr' => 0.00,
                        'Invoice_Num' => $inv,
                        'Linkacc' => $supplier,
                        'index_no' => $tour,
                        'dollarrate' => $dollerrate,
                        'dollar' => $amount,
                        'job' => $category,
                        'advances_pay' => 111,
                        'fine_date' => date('Y-m-d'),
                        'Cashier' => session()->get('username'),
                        'Dt' => date('Y-m-d H:i:s')
                    ],
                    [
                        'ID' => $acc_id,
                        'L_Code' => $agent,
                        'Date' => $date,
                        'Discription' => $description,
                        'Dr' => 0.00,
                        'Cr' => $rsamount,
                        'Invoice_Num' => $inv,
                        'Linkacc' => $supplier,
                        'index_no' => $tour,
                        'dollarrate' => $dollerrate,
                        'dollar' => $amount,
                        'job' => $category,
                        'advances_pay' => 111,
                        'fine_date' => date('Y-m-d'),
                        'Cashier' => session()->get('username'),
                        'Dt' => date('Y-m-d H:i:s')
                    ]
                ];

                $insertResult = $accountRunningModel->insertBatch($accountRunningData);
            }
            if ($insertResult) {
                return json_encode(['status' => 200, 'message' => 'Payment completed successfully']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Payment failed']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function get_tour_expenses()
    {
        $tour = $this->request->getPost('tour_id');
        $agent = $this->request->getPost('agent');

        $accountRunningModel = new AccountRunningModel();
        $consigneeModel = new ConsigneeModel();

        if ($tour == null) {
            return json_encode(['status' => 400, 'message' => 'Please provide tour ID']);
        }

        if ($accountRunningModel->where('index_no', $tour)->countAllResults() > 0) {
            // Prepare the query
            $accountRunningModel->where('index_no', $tour);
        } else {
            $tr = $consigneeModel->select('tour_id')->where('manual_id', $tour)->first();
            // Use the retrieved tour_id in the query
            if ($tr) {
                $accountRunningModel->where('index_no', $tr['tour_id']);
            } else {
                return json_encode(['status' => 400, 'message' => 'No records found']);
            }
        }

        $details = $accountRunningModel->select('account_runing.*, consignee.consignee, consignee.agent_name, consignee.tour_id, supplier.Name, entrance_fee.name as sup_name, consignee.agent, consignee.bank, consignee.bank_name')
            ->join('consignee', 'consignee.tour_id = account_runing.index_no')
            ->join('supplier', 'supplier.Sup_Acc_No = account_runing.L_Code', 'left')
            ->join('entrance_fee', 'entrance_fee.l_code = account_runing.Linkacc', 'left')
            ->join('supplier as supplier_1', 'supplier_1.Sup_Acc_No = account_runing.Linkacc', 'left')
            ->where('account_runing.index_no', $tour)
            ->where('account_runing.L_Code', $agent)
            ->where('account_runing.Cr >', 0.00)
            ->notLike('account_runing.Discription', 'VOUCHER PAYMENT%')
            ->groupStart()
            ->orWhere('account_runing.advances_pay', 111)
            ->groupEnd()
            ->findAll();

        return json_encode(['status' => 200, 'data' => $details]);
    }

    public function delete_expenses()
    {
        $id = $this->request->getPost('id');
        $tour_id = $this->request->getPost('tour_id');
        $is_settled = $this->request->getPost('is_settled');

        $accountRunningModel = new AccountRunningModel();

        if ($is_settled == 1) {

            $record_id = $accountRunningModel->select('ID')->where('advances_pay', 11)->where('index_no', $tour_id)->where('Discription LIKE', 'Transport Payment Settled%')->first();
            log_message('critical', 'Record ID: ' . $accountRunningModel->getLastQuery());
            $accrun_id = $record_id['ID'];

            $voucherpaymentmodel  = new VoucherPaymentModel();
            $voucherpayment = $voucherpaymentmodel->where('ref_po', $tour_id)->where('advances_pay', 11)->where('acc_run_id', $accrun_id)->findAll();
            $aflag = $voucherpayment[0]['Aflag'];

            if ($aflag == 1) {
                return json_encode(['status' => 400, 'message' => 'You can not delete this record because the payment has been settled']);
            }
        }

        if ($id == null) {
            return json_encode(['status' => 400, 'message' => 'Please provide an ID']);
        }

        $deleteResult = $accountRunningModel->delete($id);

        if ($deleteResult) {
            return json_encode(['status' => 200, 'message' => 'Record deleted successfully']);
        } else {
            return json_encode(['status' => 400, 'message' => 'Record deletion failed']);
        }
    }
}
