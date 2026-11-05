<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\CustomerModel;
use App\Models\InvoiceNumberModel;


class PaymentController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/proforma/advance_payment
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $CustomerModel = new CustomerModel();
        $data['customers'] = $CustomerModel->findAll();

        $accountPostingModel = new AccountPostingModel();
        $data['methods'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        return view('pages/accounts/advance_payments', $data);
    }

    public function get_details()
    {

        $customer = $this->request->getGet('id');

        if (!empty($customer)) {

            $CustomerModel = new CustomerModel();
            $customerx = $CustomerModel->where('Sup_Acc_No', $customer)->first();

            $sup_cat = $customerx['sup_cat'];
            if ($sup_cat == 1) {
                $customer = $customerx['dealer_acc'];
            }

            $accountRunningModel = new AccountRunningModel();
            // where L_Code = 6-11-107-9663 (advance payment account) and Linkacc = customer id
            $transactions = $accountRunningModel
                ->where('account_runing.L_Code', '6-11-107-9663')
                ->where('account_runing.Linkacc', $customer)
                ->orderBy('account_runing.Date', 'DESC')
                ->orderBy('account_runing.ID', 'DESC')
                ->findAll();

            $total_dr = 0;
            $total_cr = 0;

            foreach ($transactions as $transaction) {
                $total_dr += $transaction['Dr'];
                $total_cr += $transaction['Cr'];
            }

            $data = [
                'transactions' => $transactions,
                'total_dr' => $total_dr,
                'total_cr' => $total_cr,
                'balance' => $total_cr - $total_dr,
            ];

            if (!empty($transactions)) {
                return $this->response->setJSON(["status" => 200, "message" => "Data retrieved successfully.", "data" => $data]);
            }
        }

        return $this->response->setJSON(["status" => 400, "message" => "No previous payments found."]);
    }

    public function add_amount()
    {

        $validation = \Config\Services::validation();

        $validation->setRules([
            'customer' => 'required|strip_tags',
            'cash' => 'required|strip_tags',
            'payment_date' => 'required|strip_tags',
            'bank_account' => 'required|strip_tags'
        ]);

        if ($validation->withRequest($this->request)->run()) {

            $customer = $this->request->getVar('customer');
            $cash = $this->request->getVar('cash');
            $payment_date = $this->request->getVar('payment_date');
            $bank_account = $this->request->getVar('bank_account');

            $accountRunningModel = new AccountRunningModel();
            $username = session()->get('username');

            $model_inv = new InvoiceNumberModel();
            $inv = $model_inv->incrementNo();

            // description
            $description = "Advanced Payment by : " . $customer;

            // amount in numeric format
            $subTotal = is_numeric($cash) ? $cash : 0.00;

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            // DR Record
            $dr = [
                'ID' => $acc_id,
                'L_Code' => $bank_account, // payment method
                'Dr' => $subTotal,
                'Cr' => 0.00,
                'Discription' => $description,
                'Date' => $payment_date,
                'status' => '',
                'Cashier' => $username,
                'Invoice_Num' => $inv,
                'Linkacc' => $customer, // customer id
                'Dt' => date('Y-m-d H:i:s')
            ];

            $accountRunningModel->insert($dr);

            // CR Record
            $cr = [
                'ID' => $acc_id,
                'L_Code' => '6-11-107-9663', // advance payment account
                'Dr' => 0.00,
                'Cr' => $subTotal,
                'Discription' => $description,
                'Date' => $payment_date,
                'status' => '',
                'Cashier' => $username,
                'Invoice_Num' => $inv,
                'Linkacc' => $customer,
                'Dt' => date('Y-m-d H:i:s')

            ];

            $accountRunningModel->insert($cr);

            return $this->response->setJSON(["status" => 200, "message" => "Payment Added successfully!"]);
        } else {
            // Bad Request
            return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data."]);
        }
    }

    public function get_payment_details()
    {

        $order = $this->request->getGet('order_id');

        if (!empty($order)) {

            $accountRunningModel = new AccountRunningModel();
            $data = $accountRunningModel->where('job', $order)->where('L_Code', '6-11-107-9663')->orderBy('Date', 'DESC')->findAll();

            if (!empty($data)) {
                $responseData = [
                    'status' => 200,
                    'error' => null,
                    'message' => 'Data retrieved successfully.',
                    'data' => $data
                ];
            } else {
                $responseData = [
                    'status' => 400,
                    'error' => null,
                    'message' => 'Not found.'
                ];
            }
        } else {
            $responseData = [
                'status' => 409,
                'error' => null,
                'message' => ' Please Provide Valid Data.'
            ];
        }

        return $this->response->setJSON($responseData);
    }

    // route : base_url/payment/advance_payment/report
    public function advance_payment_report()
    {
        $data = array();


        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountRunningModel = new AccountRunningModel();

        $CustomerModel = new CustomerModel();
        $data['customers'] = $CustomerModel->findAll();

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

            $result = $accountRunningModel->select('account_runing.*, customers.Name');
            if (!empty($cus)) $result->where('account_runing.Linkacc', $cus);
            if (empty($cus)) {
                if (!empty($date1)) $result->where('account_runing.Date >=', $date1);
                if (!empty($date2)) $result->where('account_runing.Date <=', $date2);
            }
            $result->where('account_runing.L_Code', '6-11-107-9663');
            $result->join('customers', 'customers.Sup_Acc_No = account_runing.Linkacc', 'left');
            $result->orderBy('account_runing.Date', 'DESC');

            $data['payments'] = $result->findAll();
            // log_message('critical', $accountRunningModel->getLastQuery());
        }

        return view('pages/accounts/advance_payment_report', $data);
    }
}
