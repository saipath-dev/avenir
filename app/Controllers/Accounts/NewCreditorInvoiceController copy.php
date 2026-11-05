<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\InvoiceNumberModel;
use App\Models\SupplierModel;
use App\Models\CustomerModel;
use App\Models\ConsigneeModel;
use App\Models\VoucherInvoiceModel;
use App\Models\VoucherPaymentModel;

class NewCreditorInvoiceController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/accounts/debtor_receipt
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // load auctions
        $supplierModel = new SupplierModel();
        $data['supplier'] = $supplierModel->findAll();

        $customerModel = new CustomerModel();
        $data['agent'] = $customerModel->findAll();

        $accountPostingModel = new AccountPostingModel();
        $data['methods'] = $accountPostingModel->where('B_Code', 4)->where('T_Code', 8)->where('H_Code', 106)->findAll();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $tour_id = $this->request->getGet('tour_id');
        $data['tour_id'] = $tour_id;

        return view('pages/accounts/new_creditor_invoice', $data);
    }

    // route : base_url/accounts/creditor_invoice/get_details
    public function get_details()
    {
        $category = $this->request->getPost('category');

        if ($category == null) {
            return json_encode(['status' => 400, 'message' => 'Please provide supplier ID and invoice number']);
        }

        if ($category == '4-8-106-10386') {
            $supplierModel = new SupplierModel();
            $details = $supplierModel->select('account_posting.L_Code,account_posting.PName')->join('account_posting', 'account_posting.L_Code = supplier.Sup_Acc_No')->where('sup_cat', 1)->findAll();
        } else if ($category == '4-8-106-10385') {

            $supplierModel = new SupplierModel();
            $details = $supplierModel->select('account_posting.L_Code,account_posting.PName')->join('account_posting', 'account_posting.L_Code = supplier.Sup_Acc_No')->where('sup_cat', 2)->findAll();
        } else if ($category == '6-9-23-10640') {

            $accountPostingModel = new AccountPostingModel();
            $details = $accountPostingModel->where('B_Code', 4)->where('T_Code', 8)->where('H_Code', 109)->where('type', 1)->findAll();
        } else if ($category == '6-9-23-10641') {

            $accountPostingModel = new AccountPostingModel();
            $details = $accountPostingModel->where('B_Code', 4)->where('T_Code', 8)->where('H_Code', 109)->where('type', 2)->findAll();
        }

        return json_encode(['status' => 200, 'data' => $details]);
    }

    // route : base_url/accounts/creditor_invoice/get_tour_details
    public function get_tour_details()
    {
        $tour = $this->request->getPost('tour_id');

        $accountRunningModel = new AccountRunningModel();
        $consigneeModel = new ConsigneeModel();

        if ($tour == null) {
            return json_encode(['status' => 400, 'message' => 'Please provide tour ID']);
        }

        if ($accountRunningModel->where('index_no', $tour)->countAllResults() > 0) {
            // return json_encode(['status' => 400, 'message' => 'No records found']);
            // log_message('critical', $accountRunningModel->getLastQuery());
            // Prepare the query
            $accountRunningModel->where('index_no', $tour);
        } else {
            $tr = $consigneeModel->select('tour_id')->where('manual_id', $tour)->first();
            // Log the last query for debugging
            // log_message('critical', $consigneeModel->getLastQuery());
            // Use the retrieved tour_id in the query
            if ($tr) {
                $accountRunningModel->where('index_no', $tr['tour_id']);
            } else {
                return json_encode(['status' => 400, 'message' => 'No records found']);
            }
        }

        // Build and execute the query
        $details = $accountRunningModel->select('account_runing.*, consignee.consignee, consignee.agent_name, consignee.tour_id, supplier.Name, entrance_fee.name, consignee.agent, consignee.bank, consignee.bank_name, voucher.Aflag')
            ->join('consignee', 'consignee.tour_id = account_runing.index_no')
            ->join('supplier', 'supplier.Sup_Acc_No = account_runing.L_Code', 'left')
            ->join('entrance_fee', 'entrance_fee.l_code = account_runing.Linkacc', 'left')
            ->join('voucher', 'voucher.acc_run_id = account_runing.ID', 'left')
            ->where('account_runing.Cr >', 0.00)
            ->notLike('account_runing.L_Code', '5-7-7-%')
            ->findAll();

        // log_message('critical', $accountRunningModel->getLastQuery());
        return json_encode(['status' => 200, 'data' => $details]);
    }


    // route : base_url/accounts/creditor_payment/complete_payment
    public function complete_payment()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $description = $this->request->getPost('description');
            $agent = $this->request->getPost('agent');
            $agent_name = $this->request->getPost('agent_name');
            $bank = $this->request->getPost('bank_acc');
            $bank_name = $this->request->getPost('bank_name');
            $category = $this->request->getPost('category');
            $supplier = $this->request->getPost('supplier');
            $supplier_name = $this->request->getPost('supplier_name');
            // $payment_method = $this->request->getPost('pay_type');
            $inv = $this->request->getPost('inv');
            $date = $this->request->getPost('date');
            $dollerrate = $this->request->getPost('dollerrate');
            $amount = $this->request->getPost('amount');
            $rsamount = $this->request->getPost('rsamount');

            if ($supplier == null || $tour == null || $amount == null || $date == null || $description == null || $inv == null || $rsamount == null || $agent == null || $bank == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($amount)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $accountRunningModel = new AccountRunningModel();

            if ($category == '4-8-106-10386' || $category == '4-8-106-10385') {

                $accountRunningModel->insertBatch([
                    [
                        'ID' => $acc_id,
                        'L_Code' => $category,
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
                        'Cashier' => session()->get('username'),
                        'Dt' => date('Y-m-d H:i:s')
                    ],
                    [
                        'ID' => $acc_id,
                        'L_Code' => $supplier,
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
                        'Cashier' => session()->get('username'),
                        'Dt' => date('Y-m-d H:i:s')
                    ]
                ]);
            } else if ($category == '6-9-23-10640' || $category == '6-9-23-10641') {
                $accountRunningModel->insertBatch([
                    [
                        'ID' => $acc_id,
                        'L_Code' => $supplier,
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
                        'P_Cat' => 1,
                        'Cashier' => session()->get('username'),
                        'Dt' => date('Y-m-d H:i:s')
                    ],
                    [
                        'ID' => $acc_id,
                        'L_Code' => $category,
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
                        'P_Cat' => 1,
                        'Cashier' => session()->get('username'),
                        'Dt' => date('Y-m-d H:i:s')
                    ]
                ]);
            }

            if ($category == '4-8-106-10386') {
                $vdes = 'Hotel Reservation ' . $tour . ' / ' . $date;
            } else if ($category == '4-8-106-10385') {
                $vdes = 'Transport Expenses' . $tour . ' / ' . $date;
            } else if ($category == '6-9-23-10640') {
                $vdes = 'Entrance Fee ' . $tour . ' / ' . $date;
            } else if ($category == '6-9-23-10641') {
                $vdes = 'Other Expenses' . $tour . ' / ' . $date;
            }

            //Voucher Authentification Record
            $model_inv = new InvoiceNumberModel();
            $inv = $model_inv->incrementVoucherToken();
            $v_num = $inv;

            $data = [
                'Pay' => $supplier_name,  //hotel name 
                'emp_code' => $supplier,  //hotel acc_no
                'S_name' => $supplier_name, //hotel name 
                'Acc_No' => $supplier, //hotel acc_no
                'sup' => '1',
                'type' => 'Bank Transfer', //BT
                'Amount' => $rsamount,
                'VAT' => '0',
                'VAT_Amt' => '0',
                'vat_m' => '0',
                'nbt' => '0',
                'nbt_amt' => '0',
                'nbt_m' => '0',
                'g_ttl' => $rsamount,
                'Address' => '0',
                'print' => '0',
                'des' => '0',
                'W_Date' => date('Y-m-d'),
                'dv' => '0',
                'app_user' => session()->get('acc_no'),
                'login' =>  session()->get('username'),
                'Aflag' => '0',
                'v_typ' => 'Bank Transfer',
                'ref_po' => $tour,
                'insflag' => 1,
                'inv' => '0',
                'Vnum' => $v_num,   // voucher token
                'Bank' => $bank_name,
                'Bank_acc' => $bank,
                'C_Date' => date('Y-m-d'),
                'C_Number' => $tour,
                'W_Date' => date('Y-m-d'),
                'acc_run_id' => $acc_id,
                'is_expenses' => 1
            ];

            $data_add = [
                'Date' =>  date('Y-m-d'),
                'Invoice' => $tour,
                'Description' => $vdes,
                'Units' =>  0,
                'Rate' => 0,
                'Amount' => $rsamount,
                'InvAmount' => $rsamount,
                'V_tkn' => $v_num,   // voucher_token
                'ityp' => '1',
                'link_code' => $supplier,
                'employee' => $supplier_name,
                'login' =>  session()->get('username'),
                'INV_Date' => date('Y-m-d'),
                'Payee' => $supplier_name,
                'acc_run_id' => $acc_id,
                'account' => $supplier,
            ];

            $v_invmodel = new VoucherInvoiceModel();
            $v_invmodel->insert($data_add);

            $voucherpaymentmodel  = new VoucherPaymentModel();
            $insertResult = $voucherpaymentmodel->insert($data);

            // Consignee Record
            $congineeModel = new ConsigneeModel();

            $row_count = $congineeModel->where('tour_id', $tour)->countAllResults();
            if ($row_count < 1) {
                // Insert into consignee table
                $congineeModel->insert([
                    'tour_id' => $tour,
                    'consignee' => $description,
                    'Date' => $date,
                    'created_time' => date('Y-m-d H:i:s'),
                    'created_user' => session()->get('username'),
                    'agent' => $agent,
                    'agent_name' => $agent_name,
                    'bank' => $bank,
                    'bank_name' => $bank_name
                ]);
            }

            return json_encode(['status' => 200, 'message' => 'Payment completed successfully']);
        } catch (\Exception $e) {
            return json_encode(['status' => 500, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    // route : base_url/accounts/creditor_invoice/report
    public function credit_invoice_report()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountRunningModel = new AccountRunningModel();

        // load auctions
        $supplierModel = new SupplierModel();
        $data['supplier'] = $supplierModel->findAll();

        $date1 = $this->request->getGet('startdate');
        $data['date1'] = $date1;
        $date2 = $this->request->getGet('enddate');
        $data['date2'] = $date2;
        $cus = $this->request->getGet('cus');
        $data['cus'] = $cus;

        $data['is_search'] = false;

        if (!empty($date1) || !empty($date2) || !empty($cus)) {
            $data['is_search'] = true;

            $date1 = date('Y-m-d', strtotime(strval($date1)));
            $date2 = date('Y-m-d', strtotime(strval($date2)));

            $result = $accountRunningModel->select('account_runing.index_no, consignee.consignee, consignee.agent_name, consignee.tour_id');
            // if (!empty($cus)) $result->where('account_runing.Linkacc', $cus);
            // if (empty($cus)) {
            if (!empty($date1)) $result->where('account_runing.Date >=', $date1);
            if (!empty($date2)) $result->where('account_runing.Date <=', $date2);
            // }
            $result->like('account_runing.L_Code', '4-8-106%');
            $result->orLike('account_runing.L_Code', '6-9-23%');
            $result->join('consignee', 'consignee.tour_id = account_runing.index_no', 'left');
            $result->groupBy('account_runing.index_no');

            $data['payments'] = $result->findAll();
            // log_message('critical', $accountRunningModel->getLastQuery());
        }

        return view('pages/accounts/credit_invoice_report', $data);
    }

    // route : base_url/accounts/creditor_invoice/voucher_authentication
    public function voucher_authentication()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountRunningModel = new AccountRunningModel();

        // load auctions
        $supplierModel = new SupplierModel();
        $data['supplier'] = $supplierModel->findAll();

        // $date1 = $this->request->getGet('startdate');
        // $data['date1'] = $date1;
        // $date2 = $this->request->getGet('enddate');
        // $data['date2'] = $date2;
        // $cus = $this->request->getGet('cus');
        // $data['cus'] = $cus;

        // $data['is_search'] = false;

        // if (!empty($date1) || !empty($date2) || !empty($cus)) {
        // $data['is_search'] = true;

        //     $date1 = date('Y-m-d', strtotime(strval($date1)));
        //     $date2 = date('Y-m-d', strtotime(strval($date2)));

        $result = $accountRunningModel->select('account_runing.index_no, consignee.consignee, consignee.agent_name, consignee.tour_id');
        // if (!empty($cus)) $result->where('account_runing.Linkacc', $cus);
        // if (empty($cus)) {
        // if (!empty($date1)) $result->where('account_runing.Date >=', $date1);
        // if (!empty($date2)) $result->where('account_runing.Date <=', $date2);
        // }
        $result->like('account_runing.L_Code', '4-8-106%');
        $result->orLike('account_runing.L_Code', '6-9-23%');
        $result->join('consignee', 'consignee.tour_id = account_runing.index_no', 'left');
        $result->groupBy('account_runing.index_no');

        $data['payments'] = $result->findAll();

        $voucherpaymentmodel  = new VoucherPaymentModel();

        foreach ($data['payments'] as &$payment) {
            $tour_id = $payment['tour_id'];

            $income = $voucherpaymentmodel->select('SUM(Amount) as tot_amount,COUNT(ID) as v_count,MAX(W_Date) as W_Date')
                ->where('C_Number', $tour_id)
                // ->where('account_runing.L_Code', '5-7-7-10388')
                ->first();


            $payment['tot_amount'] = $income['tot_amount'] ?? 0;
            $payment['v_count'] = $income['v_count'] ?? 0;
            $payment['W_Date'] = $income['W_Date'] ?? 0;
        }
        // log_message('critical', $accountRunningModel->getLastQuery());
        // }

        return view('pages/accounts/voucher_authentication', $data);
    }

    // route : base_url/accounts/creditor_invoice/report
    public function credited_invoice_report()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountRunningModel = new AccountRunningModel();

        // load auctions
        $supplierModel = new SupplierModel();
        $data['supplier'] = $supplierModel->findAll();

        $date1 = $this->request->getGet('startdate');
        $data['date1'] = $date1;
        $date2 = $this->request->getGet('enddate');
        $data['date2'] = $date2;
        $cus = $this->request->getGet('cus');
        $data['cus'] = $cus;

        $data['is_search'] = false;

        if (!empty($date1) || !empty($date2) || !empty($cus)) {
            $data['is_search'] = true;

            $date1 = date('Y-m-d', strtotime(strval($date1)));
            $date2 = date('Y-m-d', strtotime(strval($date2)));

            $result = $accountRunningModel->select('account_runing.*, supplier.Name');
            if (!empty($cus)) $result->where('account_runing.Linkacc', $cus);
            if (empty($cus)) {
                if (!empty($date1)) $result->where('account_runing.Date >=', $date1);
                if (!empty($date2)) $result->where('account_runing.Date <=', $date2);
            }
            $result->like('account_runing.L_Code', '4-8-106%');
            $result->join('supplier', 'supplier.Sup_Acc_No = account_runing.Linkacc', 'left');
            $result->orderBy('account_runing.Date', 'DESC');

            $data['payments'] = $result->findAll();
            // log_message('critical', $accountRunningModel->getLastQuery());
        }

        return view('pages/accounts/credit_invoice_report', $data);
    }


    // route : base_url/accounts/creditor_invoice/report
    public function expenses_report()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountRunningModel = new AccountRunningModel();

        // load auctions
        $supplierModel = new SupplierModel();
        $data['supplier'] = $supplierModel->findAll();

        $date1 = $this->request->getGet('startdate');
        $data['date1'] = $date1;
        $date2 = $this->request->getGet('enddate');
        $data['date2'] = $date2;
        $cus = $this->request->getGet('cus');
        $data['cus'] = $cus;

        $data['is_search'] = false;

        if (!empty($date1) || !empty($date2) || !empty($cus)) {
            $data['is_search'] = true;

            $date1 = date('Y-m-d', strtotime(strval($date1)));
            $date2 = date('Y-m-d', strtotime(strval($date2)));

            $result = $accountRunningModel->select('account_runing.index_no, SUM(account_runing.Dr) as expenses, consignee.consignee, consignee.agent_name, consignee.tour_id');
            // if (!empty($cus)) $result->where('account_runing.Linkacc', $cus);
            // if (empty($cus)) {
            if (!empty($date1)) $result->where('account_runing.Date >=', $date1);
            if (!empty($date2)) $result->where('account_runing.Date <=', $date2);
            // }
            $result->like('account_runing.L_Code', '4%');
            // $result->orLike('account_runing.L_Code', '6-9-23%');
            $result->join('consignee', 'consignee.tour_id = account_runing.index_no', 'left');
            $result->groupBy('account_runing.index_no');

            $data['payments'] = $result->findAll();

            // Assume $result->findAll() returns an array of payment records
            // $data['payments'] = $result->findAll();

            foreach ($data['payments'] as &$payment) {
                $tour_id = $payment['tour_id'];
                $income = $accountRunningModel->select('SUM(Cr) as income')
                    ->where('account_runing.index_no', $tour_id)
                    ->where('account_runing.L_Code', '5-7-7-10388')
                    ->first();

                // Check if income is retrieved
                $payment['income'] = $income['income'] ?? 0; // Default to 0 if no income found
            }
            unset($payment); // Break the reference with the last element

            // Make sure to unset the reference to avoid accidental modifications later
            // unset($payment);

            // log_message('critical', $accountRunningModel->getLastQuery());
        }

        return view('pages/accounts/expenses_report', $data);
    }


    public function view_operational_expences()
    {
        $data = array();
        $tour_id = urldecode(strval($this->request->getGet('tour_id')));
        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountRunningModel = new AccountRunningModel();
        $details = $accountRunningModel->select('account_runing.*, consignee.consignee, consignee.agent_name, consignee.tour_id, supplier.Name, entrance_fee.name, consignee.agent')->join('consignee', 'consignee.tour_id = account_runing.index_no')->join('supplier', 'supplier.Sup_Acc_No = account_runing.L_Code', 'left')->join('entrance_fee', 'entrance_fee.l_code = account_runing.Linkacc', 'left')->where('account_runing.index_no', $tour_id)->where('account_runing.Cr >', 0.00)->notlike('account_runing.L_Code', '5-7-7-%')->findAll();

        $data['expenses'] = $details;
        $data['income'] = $accountRunningModel->select('SUM(Cr) as income')->where('account_runing.index_no', $tour_id)->where('account_runing.L_Code', '5-7-7-10388')->first();

        // log_message('critical', $accountRunningModel->getLastQuery());

        return view('pages/accounts/income_expenses', $data);
    }

    public function get_expences()
    {
        $data = array();
        $tour_id = urldecode(strval($this->request->getGet('tour_id')));
        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountRunningModel = new AccountRunningModel();
        $details = $accountRunningModel->select('account_runing.*, consignee.consignee, consignee.agent_name, consignee.tour_id, supplier.Name, entrance_fee.name, consignee.agent')->join('consignee', 'consignee.tour_id = account_runing.index_no')->join('supplier', 'supplier.Sup_Acc_No = account_runing.L_Code', 'left')->join('entrance_fee', 'entrance_fee.l_code = account_runing.Linkacc', 'left')->where('account_runing.index_no', $tour_id)->where('account_runing.Cr >', 0.00)->notlike('account_runing.L_Code', '5-7-7-%')->findAll();

        $data['expenses'] = $details;

        // log_message('critical', $accountRunningModel->getLastQuery());

        return view('pages/accounts/tour_expences', $data);
    }

    public function expenses_delete()
    {

        try {
            $id = $this->request->getGet('id');

            $accountRunningModel = new AccountRunningModel();
            $voucherpaymentmodel  = new VoucherPaymentModel();
            $v_invmodel = new VoucherInvoiceModel();

            if ($voucherpaymentmodel->where('acc_run_id', $id)->where('Aflag', 0)->delete()) {
                $accountRunningModel->delete($id);
                $v_invmodel->where('acc_run_id', $id)->delete();
                return $this->response->setJSON(["status" => 200, "message" => "Delete successfully!"]);
            } else {
                return $this->response->setJSON(["status" => 400, "message" => "Voucher Already Finalized!"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    // route : base_url/accounts/creditor_payment/get_transaction_history
    public function get_transaction_history()
    {
        // $supplier = $this->request->getGet('supplier');
        $tour_id = $this->request->getGet('tour_id');

        $accountRunningModel = new AccountRunningModel();
        $accRecords = $accountRunningModel->select('account_runing.*, account_posting.PName as Account');
        $accRecords = $accRecords->join('account_posting', 'account_posting.L_Code = account_runing.L_Code');
        $accRecords = $accRecords->where('index_no', $tour_id);
        $accRecords = $accRecords->orderBy('Date', 'DESC');
        $transactions = $accRecords->findAll();

        $total = 0;
        $total_dr = 0;
        $total_cr = 0;

        foreach ($transactions as $transaction) {
            $total_dr += $transaction['Dr'];
            $total_cr += $transaction['Cr'];
        }

        $total = $total_cr - $total_dr;

        return json_encode(['status' => 200, 'transactions' => $transactions, 'total' => number_format($total, 2, '.', ',')]);
    }
}
