<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\InvoiceNumberModel;
use App\Models\CustomerModel;
use App\Models\ConsigneeModel;
use App\Models\DebtorInvoiceLogModel;
use App\Models\TourQuotationsModel;

class ServiceInvoiceController extends BaseController
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
        $scustomerModel = new CustomerModel();
        $data['customer'] = $scustomerModel->findAll();

        // load tours
        // Load available tours excluding those in account_running
        $consigneeModel = new ConsigneeModel();
        $data['consignee'] = $consigneeModel->getAvailableTours();

        $accountPostingModel = new AccountPostingModel();
        $data['methods'] = $accountPostingModel->where('B_Code', 5)->where('T_Code', 7)->where('H_Code', 7)->findAll();

        return view('pages/accounts/service_invoice', $data);
    }

    // route : base_url/accounts/debtor_invoice/complete_payment
    public function complete_payment()
    {
        $customer = $this->request->getPost('customer');
        $tour = $this->request->getPost('tour_id');
        $payment_method = $this->request->getPost('pay_type');
        $inv = $this->request->getPost('inv');
        $date = $this->request->getPost('date');
        $description = $this->request->getPost('description');
        $advance = $this->request->getPost('advance');
        $credit_balance = $this->request->getPost('credit_balance');
        $doller_rate = $this->request->getPost('drate');
        $amount = $this->request->getPost('amount');

        if ($customer == null || $tour == null || $amount == null || $date == null || $payment_method == null || $description == null || $inv == null || $credit_balance == null || $credit_balance == 0) {
            return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
        }

        if (!is_numeric($amount)) {
            return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
        }

        $invoiceNoModel = new InvoiceNumberModel();
        $acc_id = $invoiceNoModel->incrementAccId();

        $accountRunningModel = new AccountRunningModel();
        // Credit balance Record Insert
        // DR Recrord
        $accountRunningModel->insert([
            'ID' => $acc_id,
            'L_Code' => $customer, // Customer
            'Date' => $date,
            'Discription' => $description,
            'Dr' => $credit_balance,
            'Cr' => 0.00,
            'Invoice_Num' => $inv,
            'Linkacc' => $customer,
            'index_no' => $tour,
            'dollarrate' => $doller_rate,
            'dollar' => $amount,
            'Dt' => date('Y-m-d H:i:s')
        ]);

        // CR Record
        $accountRunningModel->insert([
            'ID' => $acc_id,
            'L_Code' => $payment_method, //Income
            'Date' => $date,
            'Discription' => $description,
            'Dr' => 0.00,
            'Cr' => $credit_balance,
            'Invoice_Num' => $inv,
            'Linkacc' => $customer,
            'index_no' => $tour,
            'dollarrate' => $doller_rate,
            'dollar' => $amount,
            'Dt' => date('Y-m-d H:i:s')
        ]);

        // Advance Payment Setle Record
        if ($advance != '') {
            // DR Recrord
            $accountRunningModel->insert([
                'ID' => $acc_id,
                'L_Code' => '6-11-107-9663', // advance(over) payment account
                'Date' => $date,
                'Discription' => $description,
                'Dr' => $advance,
                'Cr' => 0.00,
                'Invoice_Num' => $inv,
                'Linkacc' => $customer,
                'index_no' => $tour,
                'Dt' => date('Y-m-d H:i:s')
            ]);

            // CR Record
            $accountRunningModel->insert([
                'ID' => $acc_id,
                'L_Code' => $payment_method, //Income 
                'Date' => $date,
                'Discription' => $description,
                'Dr' => 0.00,
                'Cr' => $advance,
                'Invoice_Num' => $inv,
                'Linkacc' => $customer,
                'index_no' => $tour,
                'Dt' => date('Y-m-d H:i:s')
            ]);
        }

        return json_encode(['status' => 200, 'message' => 'Payment completed successfully']);
    }

    public function get_advance_payment()
    {
        $customer = $this->request->getPost('cus');

        if (!empty($customer)) {

            $CustomerModel = new CustomerModel();
            $customerx = $CustomerModel->where('Sup_Acc_No', $customer)->first();

            // $sup_cat = $customerx['sup_cat'];
            // if ($sup_cat == 1) {
            //     $customer = $customerx['dealer_acc'];
            // }

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

    public function get_details()
    {
        $tour_id = $this->request->getGet('id');

        if (!empty($tour_id)) {
            $ConsigneeModel = new ConsigneeModel();
            $customers = $ConsigneeModel->getConsignee($tour_id);

            // Return a JSON response
            return $this->response->setJSON($customers);
        } else {
            return $this->response->setJSON([]);
        }
    }

    // route : base_url/accounts/debtor_invoice_report/report
    public function debtor_invoice_report()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountRunningModel = new AccountRunningModel();

        // load auctions
        $CustomerModel = new CustomerModel();
        $data['customers'] = $CustomerModel->findAll();

        $date1 = urldecode($this->request->getGet('startdate'));
        $date2 = $this->request->getGet('enddate');
        $cus = $this->request->getGet('cus');


        $tour_id = $this->request->getGet('tour_id');
        $data['tour_id'] = $tour_id;


        $data['is_search'] = false;

        if (!empty($date1) || !empty($date2) || !empty($cus)) {
            $data['is_search'] = true;

            // convert $date1 and $date2 to date format
            if ($date1) {
                $date1 = date('Y-m-d', strtotime($date1));
            }

            if ($date2) {
                $date2 = date('Y-m-d', strtotime($date2));
            }

            // $result = $accountRunningModel->select('`account_runing`.`index_no`,SUM(`account_runing`.`Cr`) AS Cr,SUM(`account_runing`.`Dr`) AS Dr,`customers`.`Name`,`account_runing`.`Linkacc`, consignee.manual_id');
            // // $result = $accountRunningModel->select('`account_runing`.`index_no`,`account_runing`.`Date`,`account_runing`.`Invoice_Num`,`account_runing`.`dollarrate`,`account_runing`.`dollar`,`account_runing`.`ID`,SUM(`account_runing`.`Cr`) AS Cr,SUM(`account_runing`.`Dr`) AS Dr,`customers`.`Name`,`account_runing`.`Linkacc`, consignee.manual_id');
            // // if (!empty($cus)) $result->where('account_runing.Linkacc', $cus);
            // if (!empty($cus) && !empty($date1) && !empty($date2)) {
            //     $result->where('account_runing.Linkacc', $cus);
            //     $result->where('account_runing.Date >=', $date1);
            //     $result->where('account_runing.Date <=', $date2);
            // }

            // if (empty($cus) && !empty($date1) && !empty($date2)) {
            //     $result->where('account_runing.Date >=', $date1);
            //     $result->where('account_runing.Date <=', $date2);
            // }

            // if (!empty($cus) && empty($date1) && empty($date2)) {
            //     $result->where('account_runing.Linkacc', $cus);
            // }


            // $result->like('account_runing.L_Code', '5-7-7%');
            // $result->join('customers', 'customers.Sup_Acc_No = account_runing.Linkacc', 'left');
            // $result->join('consignee', 'consignee.tour_id = account_runing.index_no', 'left');
            // $result->groupBy('`account_runing`.`index_no`,`account_runing`.`L_Code`');
            // // $result->having('SUM(`account_runing`.`Cr`) - SUM(`account_runing`.`Dr`) > 0');
            // $result->orderBy('account_runing.Date', 'DESC');

            // $data['payments'] = $result->findAll();
            // // get index_no in the $result and append to array
            // $index_no = array_column($data['payments'], 'index_no');
            // // loop through the index_no array and get the consignee details
            // foreach ($index_no as $key => $value) {
            //     $data['payments'][$key]['abc'] = $accountRunningModel->select('`account_runing`.`Date`,`account_runing`.`Invoice_Num`,`account_runing`.`dollarrate`,`account_runing`.`dollar`,`account_runing`.`ID`')->where('index_no', $value)->like('account_runing.L_Code', '5-7-7%')->orderBy('account_runing.ID', 'DESC')->first();
            //     // log_message('critical', $accountRunningModel->getLastQuery());
            // }

            $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();

            $result = $DebtorInvoiceLogModel->select('debtor_invoice_log.date,debtor_invoice_log.doller_rate,debtor_invoice_log.dollar_amount,debtor_invoice_log.credit_balance, customers.Name as cus_name, consignee.manual_id as manual_id, debtor_invoice_log.invoice_no, debtor_invoice_log.cancel, consignee.consignee as consignee, consignee.start_date as start_date');
            if (!empty($cus) && !empty($date1) && !empty($date2)) {
                $result->where('debtor_invoice_log.agent', $cus);
                $result->where('debtor_invoice_log.date >=', $date1);
                $result->where('debtor_invoice_log.date <=', $date2);
            }

            if (empty($cus) && !empty($date1) && !empty($date2)) {
                $result->where('debtor_invoice_log.date >=', $date1);
                $result->where('debtor_invoice_log.date <=', $date2);
            }

            if (!empty($cus) && empty($date1) && empty($date2)) {
                $result->where('debtor_invoice_log.agent', $cus);
            }

            $result->where('debtor_invoice_log.cancel', 0);
            $result->orwhere('debtor_invoice_log.cancel', 3);
            $result->join('customers', 'customers.Sup_Acc_No = debtor_invoice_log.agent', 'left');
            $result->join('consignee', 'consignee.tour_id = debtor_invoice_log.tour_id', 'left');
            $result->groupBy('debtor_invoice_log.invoice_no');

            $data['payments'] = $result->findAll();

            $data['cus'] = $cus;
            $data['date1'] = $date1;
            $data['date2'] = $date2;
        } else if (!empty($tour_id)) {
            $result = $accountRunningModel->select('`account_runing`.`index_no`,`account_runing`.`Date`,`account_runing`.`Invoice_Num`,`account_runing`.`dollarrate`,`account_runing`.`dollar`,`account_runing`.`ID`,SUM(`account_runing`.`Cr`) AS Cr,SUM(`account_runing`.`Dr`) AS Dr,`customers`.`Name`,`account_runing`.`Linkacc`, consignee.manual_id');
            $result->where('account_runing.index_no', $tour_id);
            $result->like('account_runing.L_Code', '5-7-7%');
            $result->join('customers', 'customers.Sup_Acc_No = account_runing.Linkacc', 'left');
            $result->join('consignee', 'consignee.tour_id = account_runing.index_no', 'left');
            $result->groupBy('`account_runing`.`index_no`,`account_runing`.`L_Code`');
            // $result->having('SUM(`account_runing`.`Cr`) - SUM(`account_runing`.`Dr`) > 0');
            $result->orderBy('account_runing.Date', 'DESC');

            $data['payments'] = $result->findAll();
            $key = 0;
            $data['payments'][$key]['abc'] = $accountRunningModel->select('`account_runing`.`Date`,`account_runing`.`Invoice_Num`,`account_runing`.`dollarrate`,`account_runing`.`dollar`,`account_runing`.`ID`')->where('index_no', $tour_id)->like('account_runing.L_Code', '5-7-7%')->orderBy('account_runing.ID', 'DESC')->first();
        }

        // log_message('critical', $accountRunningModel->getLastQuery());
        return view('pages/accounts/debtor_invoice_report', $data);
    }

    public function debtor_cancellation_request()
    {
        try {
            $id = $this->request->getGet('id');
            $status = $this->request->getGet('status');
            $type = $this->request->getGet('type');

            $user = session()->get('username');

            $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();
            $data_request = [
                'cancel' => $type,
                'requested_flag' => $type,
                'requested_user' => session()->get('username'),
                'requested_date' => date('Y-m-d'),
            ];
            $DebtorInvoiceLogModel->where('invoice_no', $id)->set($data_request)->update();

            return $this->response->setJSON(["status" => 200, "message" => "Requested Successfully!"]);
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    public function debtor_cancellation()
    {

        try {
            $id = $this->request->getGet('id');
            $status = $this->request->getGet('status');
            $type = $this->request->getGet('type');
            $flag = $this->request->getGet('flag');

            $user = session()->get('username');

            if ($flag == 1) { // accept request

                if ($type == 2) {  // cancellation request

                    $accountRunningModel = new AccountRunningModel();
                    $detail = $accountRunningModel->where('Invoice_Num', $id)->orderBy('Dr', 'DESC')->findAll();

                    // log_message('critical', $accountRunningModel->getLastQuery());

                    // Dr Record
                    $customer = $detail[0]['L_Code'];
                    $dr = $detail[0]['Dr'];

                    $description = $detail[0]['Discription'];
                    $date = $detail[0]['Date'];
                    $inv = $detail[0]['Invoice_Num'];
                    $link_acc = $detail[0]['Linkacc'];
                    $tour = $detail[0]['index_no'];
                    $doller_rate = $detail[0]['dollarrate'];
                    $amount = $detail[0]['dollar'];

                    // Cr Record
                    $payment_method = $detail[1]['L_Code'];
                    $cr = $detail[1]['Cr'];

                    $invoiceNoModel = new InvoiceNumberModel();
                    $acc_id = $invoiceNoModel->incrementAccId();

                    // log message for $customer, $dr, $description, $date, $inv, $link_acc, $tour, $doller_rate, $amount, $payment_method, $cr

                    // check nor empty in this case
                    if (empty($customer) || empty($dr) || empty($description) || empty($date) || empty($inv) || empty($link_acc) || empty($tour) || empty($doller_rate) || empty($amount) || empty($payment_method) || empty($cr)) {

                        log_message('critical', $customer . ' / ' . $dr . ' / ' . $description . ' / ' . $date . ' / ' . $inv . ' / ' . $link_acc . ' / ' . $tour . ' / ' . $doller_rate . ' / ' . $amount . ' / ' . $payment_method . ' / ' . $cr);

                        return $this->response->setJSON(["status" => 400, "message" => "Please fill all the fields"]);
                    }

                    $data = [
                        [
                            'ID' => $acc_id,
                            'L_Code' => $payment_method, // Income 
                            'Date' => $date,
                            'Discription' => $description,
                            'Dr' => $dr,
                            'Cr' => 0.00,
                            'Invoice_Num' => $inv,
                            'Linkacc' => $link_acc,
                            'index_no' => $tour,
                            'dollarrate' => $doller_rate,
                            'dollar' => $amount,
                            'Cashier' => session()->get('username'),
                            'InvCancel' => $id,
                            'InvCancelNote' => date('Y-m-d'),
                            'Dt' => date('Y-m-d H:i:s')
                        ],
                        [
                            'ID' => $acc_id,
                            'L_Code' => $customer, // Customer
                            'Date' => $date,
                            'Discription' => $description,
                            'Dr' => 0.00,
                            'Cr' => $cr,
                            'Invoice_Num' => $inv,
                            'Linkacc' => $link_acc,
                            'index_no' => $tour,
                            'dollarrate' => $doller_rate,
                            'dollar' => $amount,
                            'Cashier' => session()->get('username'),
                            'InvCancel' => $id,
                            'InvCancelNote' => date('Y-m-d'),
                            'Dt' => date('Y-m-d H:i:s')
                        ]
                    ];

                    if ($accountRunningModel->insertBatch($data)) {
                        $data_cancel = [
                            'cancel' => 1,
                            'cancel_user' => session()->get('username'),
                            'cancel_date' => date('Y-m-d'),
                        ];

                        $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();
                        $DebtorInvoiceLogModel->where('tour_id', $tour)->where('invoice_no', $inv)->set($data_cancel)->update();

                        $debtorInvresult = $DebtorInvoiceLogModel->where('tour_id', $tour)->where('invoice_no <>', $inv)->where('cancel', 0)->findAll();
                        $count = count($debtorInvresult);

                        if ($count == 0) {
                            $tourQuatitionModel = new TourQuotationsModel();
                            $tourQuatitionModel->where('quotation_id', $tour)->set(['confirm' => 3])->update();
                        }
                        // $tourQuatitionModel = new TourQuotationsModel();
                        // $tourQuatitionModel->where('quotation_id', $tour)->set(['confirm' => 0])->update();

                        return $this->response->setJSON(["status" => 200, "message" => "Cancellation Successfully!"]);
                    } else {
                        return $this->response->setJSON(["status" => 400, "message" => "Error !"]);
                    }
                } else if ($type == 5) { // amendment request accept
                    $accountRunningModel = new AccountRunningModel();
                    $detail = $accountRunningModel->where('Invoice_Num', $id)->orderBy('Dr', 'DESC')->findAll();

                    // log_message('critical', $accountRunningModel->getLastQuery());

                    // Dr Record
                    $customer = $detail[0]['L_Code'];
                    $dr = $detail[0]['Dr'];

                    $description = $detail[0]['Discription'];
                    $date = $detail[0]['Date'];
                    $inv = $detail[0]['Invoice_Num'];
                    $link_acc = $detail[0]['Linkacc'];
                    $tour = $detail[0]['index_no'];
                    $doller_rate = $detail[0]['dollarrate'];
                    $amount = $detail[0]['dollar'];

                    // Cr Record
                    $payment_method = $detail[1]['L_Code'];
                    $cr = $detail[1]['Cr'];

                    $invoiceNoModel = new InvoiceNumberModel();
                    $acc_id = $invoiceNoModel->incrementAccId();

                    // log message for $customer, $dr, $description, $date, $inv, $link_acc, $tour, $doller_rate, $amount, $payment_method, $cr

                    // check nor empty in this case
                    if (empty($customer) || empty($description) || empty($date) || empty($inv) || empty($link_acc) || empty($tour) || empty($doller_rate) || empty($payment_method)) {

                        log_message('critical', $customer . ' / ' . $dr . ' / ' . $description . ' / ' . $date . ' / ' . $inv . ' / ' . $link_acc . ' / ' . $tour . ' / ' . $doller_rate . ' / ' . $amount . ' / ' . $payment_method . ' / ' . $cr);

                        return $this->response->setJSON(["status" => 400, "message" => "Please fill all the fields"]);
                    }

                    $data = [
                        [
                            'ID' => $acc_id,
                            'L_Code' => $payment_method, // Income 
                            'Date' => $date,
                            'Discription' => $description,
                            'Dr' => $dr,
                            'Cr' => 0.00,
                            'Invoice_Num' => $inv,
                            'Linkacc' => $link_acc,
                            'index_no' => $tour,
                            'dollarrate' => $doller_rate,
                            'dollar' => $amount,
                            'Cashier' => session()->get('username'),
                            'InvCancel' => $id,
                            'InvCancelNote' => date('Y-m-d'),
                            'Dt' => date('Y-m-d H:i:s')
                        ],
                        [
                            'ID' => $acc_id,
                            'L_Code' => $customer, // Customer
                            'Date' => $date,
                            'Discription' => $description,
                            'Dr' => 0.00,
                            'Cr' => $cr,
                            'Invoice_Num' => $inv,
                            'Linkacc' => $link_acc,
                            'index_no' => $tour,
                            'dollarrate' => $doller_rate,
                            'dollar' => $amount,
                            'Cashier' => session()->get('username'),
                            'InvCancel' => $id,
                            'InvCancelNote' => date('Y-m-d'),
                            'Dt' => date('Y-m-d H:i:s')
                        ]
                    ];

                    if ($accountRunningModel->insertBatch($data)) {
                        $data_cancel = [
                            'cancel' => 1,
                            'cancel_user' => session()->get('username'),
                            'cancel_date' => date('Y-m-d'),
                        ];

                        $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();
                        $DebtorInvoiceLogModel->where('tour_id', $tour)->where('invoice_no', $inv)->set($data_cancel)->update();

                        $debtorInvresult = $DebtorInvoiceLogModel->where('tour_id', $tour)->where('cancel', 0)->findAll();
                        $count = count($debtorInvresult);

                        if ($count == 0) {
                            $tourQuatitionModel = new TourQuotationsModel();
                            $tourQuatitionModel->where('quotation_id', $tour)->set(['confirm' => 4])->update();

                            // consignee
                            $consigneeModel = new ConsigneeModel();
                            $consigneeModel->where('tour_id', $tour)->set(['amend_inv' => $inv])->update();
                        }
                        // $tourQuatitionModel = new TourQuotationsModel();
                        // $tourQuatitionModel->where('quotation_id', $tour)->set(['confirm' => 0])->update();

                        return $this->response->setJSON(["status" => 200, "message" => "Cancellation Successfully!"]);
                    } else {
                        return $this->response->setJSON(["status" => 400, "message" => "Error !"]);
                    }
                }
            } else { // reject request
                $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();
                $data_cancel = [
                    'cancel' => 3,
                    'cancel_user' => session()->get('username'),
                    'cancel_date' => date('Y-m-d'),
                ];
                $DebtorInvoiceLogModel->where('invoice_no', $id)->set($data_cancel)->update();

                return $this->response->setJSON(["status" => 200, "message" => "Reject Successfully!"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    // cancellation_request_report
    public function cancellation_request_report()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // load auctions
        $scustomerModel = new CustomerModel();
        $data['customers'] = $scustomerModel->findAll();

        $data['is_search'] = true;


        $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();

        $result = $DebtorInvoiceLogModel->select('debtor_invoice_log.date,debtor_invoice_log.doller_rate,debtor_invoice_log.dollar_amount,debtor_invoice_log.credit_balance, customers.Name as cus_name, consignee.manual_id as manual_id, debtor_invoice_log.invoice_no, debtor_invoice_log.requested_user, debtor_invoice_log.requested_date, debtor_invoice_log.cancel,debtor_invoice_log.requested_flag');

        $result->where('debtor_invoice_log.cancel', 2);
        $result->orWhere('debtor_invoice_log.cancel', 5);
        $result->join('customers', 'customers.Sup_Acc_No = debtor_invoice_log.agent', 'left');
        $result->join('consignee', 'consignee.tour_id = debtor_invoice_log.tour_id', 'left');
        $result->groupBy('debtor_invoice_log.invoice_no');

        $data['payments'] = $result->findAll();

        return view('pages/accounts/debtor_invoice_cancellation_req', $data);
    }

    // cancellation_report
    public function cancellation_report()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // load auctions
        $scustomerModel = new CustomerModel();
        $data['customers'] = $scustomerModel->findAll();

        $date1 = urldecode($this->request->getGet('startdate'));
        $date2 = $this->request->getGet('enddate');
        $tour_id = urldecode($this->request->getGet('tour_id'));
        $inv = $this->request->getGet('inv');

        // Array to hold conditions
        $conditions = [];

        if ($date1) {
            $date1 = date('Y-m-d', strtotime($date1));
        }

        if ($date2) {
            $date2 = date('Y-m-d', strtotime($date2));
        }

        // Add conditions dynamically
        if (!empty($date1)) {
            $conditions['debtor_invoice_log.date >='] = date('Y-m-d', strtotime($date1));
        }

        if (!empty($date2)) {
            $conditions['debtor_invoice_log.date <='] = date('Y-m-d', strtotime($date2));
        }

        if (!empty($tour_id)) {
            $conditions['consignee.manual_id'] = $tour_id;
        }

        if (!empty($inv)) {
            $conditions['debtor_invoice_log.invoice_no'] = $inv;
        }

        $data['is_search'] = false;

        if (!empty($conditions)) {
            $data['is_search'] = true;

            // convert $date1 and $date2 to date format

            $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();

            $result = $DebtorInvoiceLogModel->select('debtor_invoice_log.date,debtor_invoice_log.doller_rate,debtor_invoice_log.dollar_amount,debtor_invoice_log.credit_balance, customers.Name as cus_name, consignee.manual_id as manual_id, debtor_invoice_log.invoice_no, debtor_invoice_log.cancel,debtor_invoice_log.requested_flag, debtor_invoice_log.requested_user, debtor_invoice_log.requested_date, debtor_invoice_log.cancel_user, debtor_invoice_log.cancel_date, consignee.consignee as consignee, consignee.start_date as start_date');

            // Apply conditions
            $result->where($conditions);

            $result->join('customers', 'customers.Sup_Acc_No = debtor_invoice_log.agent', 'left');
            $result->join('consignee', 'consignee.tour_id = debtor_invoice_log.tour_id', 'left');
            $result->groupBy('debtor_invoice_log.invoice_no');

            $data['payments'] = $result->findAll();

            // log_message('critical', $DebtorInvoiceLogModel->getLastQuery());
        }
        return view('pages/accounts/debtor_invoice_cancellation', $data);
    }

    // route : base_url/accounts/arrival_report
    public function arrival_report()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $scustomerModel = new CustomerModel();
        $data['customers'] = $scustomerModel->findAll();

        $date1 = urldecode($this->request->getGet('startdate'));
        $date2 = $this->request->getGet('enddate');
        $cus = $this->request->getGet('cus');

        $accountRunningModel = new AccountRunningModel();

        $data['is_search'] = false;

        if (!empty($date1) || !empty($date2) || !empty($cus)) {
            $data['is_search'] = true;

            // convert $date1 and $date2 to date format
            if ($date1) {
                $date1 = date('Y-m-d', strtotime($date1));
            }

            if ($date2) {
                $date2 = date('Y-m-d', strtotime($date2));
            }


            $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();

            $result = $DebtorInvoiceLogModel->select('debtor_invoice_log.date,debtor_invoice_log.doller_rate,debtor_invoice_log.dollar_amount,debtor_invoice_log.credit_balance, customers.Name as cus_name, consignee.manual_id as manual_id, debtor_invoice_log.invoice_no, debtor_invoice_log.cancel, consignee.consignee as consignee, consignee.start_date as start_date');
            if (!empty($cus) && !empty($date1) && !empty($date2)) {
                $result->where('debtor_invoice_log.agent', $cus);
                $result->where('consignee.start_date >=', $date1);
                $result->where('consignee.start_date <=', $date2);

                $data['cus'] = $cus;
                $data['date1'] = $date1;
                $data['date2'] = $date2;
            }

            if (empty($cus) && !empty($date1) && !empty($date2)) {
                $result->where('consignee.start_date >=', $date1);
                $result->where('consignee.start_date <=', $date2);

                $data['cus'] = '';
                $data['date1'] = $date1;
                $data['date2'] = $date2;
            }

            if (!empty($cus) && empty($date1) && empty($date2)) {
                $result->where('debtor_invoice_log.agent', $cus);

                $data['cus'] = $cus;
                $data['date1'] = '';
                $data['date2'] = '';
            }

            $result->where('debtor_invoice_log.cancel', 0);
            $result->orwhere('debtor_invoice_log.cancel', 3);
            $result->join('customers', 'customers.Sup_Acc_No = debtor_invoice_log.agent', 'left');
            $result->join('consignee', 'consignee.tour_id = debtor_invoice_log.tour_id', 'left');
            $result->groupBy('debtor_invoice_log.invoice_no');

            $data['payments'] = $result->findAll();
        } else {

            $data['cus'] = '';
            $data['date1'] = '';
            $data['date2'] = '';
        }


        return view('pages/accounts/debtor_arrival_report', $data);
    }


    // route : base_url/accounts/debtor_invoice_report/print
    public function debtor_invoice_report_print()
    {
        $data = array();


        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountRunningModel = new AccountRunningModel();

        $CustomerModel = new CustomerModel();
        $data['customers'] = $CustomerModel->findAll();

        $date1 = urldecode($this->request->getGet('startdate'));
        $date2 = urldecode($this->request->getGet('enddate'));
        $cus = urldecode($this->request->getGet('cus'));

        if (!empty($date1) || !empty($date2) || !empty($cus)) {


            if ($date1) {
                $date1 = date('Y-m-d', strtotime($date1));
            }

            if ($date2) {
                $date2 = date('Y-m-d', strtotime($date2));
            }

            $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();

            $result = $DebtorInvoiceLogModel->select('debtor_invoice_log.date,debtor_invoice_log.doller_rate,debtor_invoice_log.dollar_amount,debtor_invoice_log.credit_balance, customers.Name as cus_name, consignee.manual_id as manual_id, debtor_invoice_log.invoice_no, debtor_invoice_log.cancel, consignee.consignee as consignee, consignee.start_date as start_date');
            if (!empty($cus) && !empty($date1) && !empty($date2)) {
                $result->where('debtor_invoice_log.agent', $cus);
                $result->where('consignee.start_date >=', $date1);
                $result->where('consignee.start_date <=', $date2);

                $data['cus'] = $cus;
                $data['date1'] = $date1;
                $data['date2'] = $date2;
            }

            if (empty($cus) && !empty($date1) && !empty($date2)) {
                $result->where('consignee.start_date >=', $date1);
                $result->where('consignee.start_date <=', $date2);

                $data['cus'] = '';
                $data['date1'] = $date1;
                $data['date2'] = $date2;
            }

            if (!empty($cus) && empty($date1) && empty($date2)) {
                $result->where('debtor_invoice_log.agent', $cus);

                $data['cus'] = $cus;
                $data['date1'] = '';
                $data['date2'] = '';
            }

            $result->where('debtor_invoice_log.cancel', 0);
            $result->orwhere('debtor_invoice_log.cancel', 3);
            $result->join('customers', 'customers.Sup_Acc_No = debtor_invoice_log.agent', 'left');
            $result->join('consignee', 'consignee.tour_id = debtor_invoice_log.tour_id', 'left');
            $result->groupBy('debtor_invoice_log.invoice_no');

            $data['payments'] = $result->findAll();
        }

        return view('pages/accounts/debtor_invoice_arrival_print', $data);
    }
}
