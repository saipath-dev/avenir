<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\CustomerModel;
use App\Models\InvoiceNumberModel;
use App\Models\SupplierModel;

class DebtorPaymentController extends BaseController
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

        // load customers
        $accountPostingModel = new AccountPostingModel();
        $data['customers'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 2)->where('H_Code', 5)->findAll();

        $data['methods'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $customer = $this->request->getGet('customer');
        $from = $this->request->getGet('from');
        $to = $this->request->getGet('to');
        $rate = $this->request->getGet('rate');
        $doller_amt = $this->request->getGet('doller_amt');
        $lkr_amt = $this->request->getGet('lkr_amt');

        $data['is_search'] = false;

        if ($customer != null) {

            $data['is_search'] = true;

            $data['customer'] = $customer;
            $data['from'] = $from;
            $data['to'] = $to;
            $data['rate'] = $rate;
            $data['doller_amt'] = $doller_amt;
            $data['lkr_amt'] = $lkr_amt;

            $accountRunningModel = new AccountRunningModel();
            $accRecords = $accountRunningModel->select('sum(Dr) as Dr, sum(Cr) As Cr,max(L_Code) as L_Code, Discription, Date, Invoice_Num, dollarrate, dollar');
            $accRecords = $accRecords->where('L_Code', $customer);
            $accRecords = $accRecords->orderBy('Date', 'DESC');
            $accRecords = $accRecords->groupBy('Invoice_Num');
            $transactions = $accRecords->findAll();

            // get transactions total by trasactions[Dr] - transactions[Cr]
            $total = 0;
            $total_dr = 0;
            $total_cr = 0;
            foreach ($transactions as $transaction) {
                $total_dr += $transaction['Dr'];
                $total_cr += $transaction['Cr'];
            }
            $total = $total_dr - $total_cr;
            $accRecords = $accountRunningModel->select('sum(Dr) as Dr, sum(Cr) As Cr,max(L_Code) as L_Code, Discription, Date, Invoice_Num, dollarrate, dollar');
            $accRecords = $accRecords->where('L_Code', $customer);
            if ($from != null) $accRecords = $accRecords->where('Date >=', $from);
            if ($to != null) $accRecords = $accRecords->where('Date <=', $to);
            $accRecords = $accRecords->orderBy('Date', 'DESC');
            $accRecords = $accRecords->groupBy('Invoice_Num');
            $transactions = $accRecords->findAll();

            log_message('critical', 'Transactions: ' . $accRecords->getLastQuery());

            // get last 20 transactions
            $data['transactions'] = $transactions;
            // total in 0.00 format
            $data['total'] = number_format($total, 2, '.', ',');

            // var_dump($data['total']); die();
        }

        return view('pages/accounts/debtor_payment', $data);
    }

    // route : base_url/accounts/creditor_payment/complete_payment
    public function complete_payment()
    {
        try {
            $customer = $this->request->getPost('customer');
            $amount = $this->request->getPost('amount');
            $date = $this->request->getPost('date');
            $payment_method = $this->request->getPost('payment_method');
            $description = $this->request->getPost('description');
            $transactions = $this->request->getPost('transactions');
            // foreach the transactions array and get the values
            if ($customer == null || $amount == null || $date == null || $payment_method == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($amount)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            foreach ($transactions as $transaction) {

                $transaction_date = $transaction['date'];
                $transaction_id = $transaction['invoice_no'];
                $transaction_description = $transaction['description'];
                $transaction_dr = $transaction['dr'];
                $transaction_cr = $transaction['cr'];
                $transaction_bal = $transaction['bal'];
                $transaction_status = $transaction['status'];

                if ($transaction_id == null || $transaction_cr == null || $transaction_cr == '' || $transaction_date == null || $transaction_bal == null) {
                    return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
                }

                // insert the values to the database
                $accountRunningModel = new AccountRunningModel();

                if ($transaction_cr == 0 && ($transaction_bal == '0' || $transaction_bal == 0)) {
                    continue;
                }

                // if (empty($transaction_bal) || $transaction_bal == '0' || $transaction_bal == 0) {
                //     continue;
                // }

                $invoiceNoModel = new InvoiceNumberModel();
                $acc_id = $invoiceNoModel->incrementAccId();

                $commonData = [
                    'ID' => $acc_id,
                    'Date' => $transaction_date,
                    'Discription' => $transaction_description,
                    'P_Month' => date('Y-m-d'),
                    'Invoice_Num' => $transaction_id,
                    'Cashier' => session()->get('acc_no'),
                    'Recpt_No' => $description,
                    'Dt' => date('Y-m-d H:i:s'),
                ];

                $dataToInsert = [
                    array_merge($commonData, [
                        'L_Code' => '1-2-5-10779', // Debtor Control Account
                        'Dr' => $transaction_dr,
                        'Cr' => 0.00,
                    ]),
                    array_merge($commonData, [
                        'L_Code' => $customer, // Customer
                        'Dr' => 0.00,
                        'Cr' => $transaction_dr,
                    ]),
                ];

                if ($transaction_status == 'Loss') { // income acc dr, controll acc cr
                    $dataexchangepnl = [
                        array_merge($commonData, ['L_Code' => '8-35-111-10780', 'Dr' => $transaction_bal, 'Cr' => 0.00]), // Income Foreign Exchange Gain-Loss
                        array_merge($commonData, ['L_Code' => '1-2-5-10779', 'Dr' => 0.00, 'Cr' => $transaction_bal]), // Debtor Control Account
                    ];
                } else if ($transaction_status == 'Profit') { // controll acc dr, income acc cr
                    $dataexchangepnl = [
                        array_merge($commonData, ['L_Code' => '1-2-5-10779', 'Dr' => $transaction_bal, 'Cr' => 0.00]), // Debtor Control Account
                        array_merge($commonData, ['L_Code' => '8-35-111-10780', 'Dr' => 0.00, 'Cr' => $transaction_bal]), // Income Foreign Exchange Gain-Loss
                    ];
                }

                $accountRunningModel->insertBatch($dataToInsert);
                $accountRunningModel->insertBatch($dataexchangepnl);
            }

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $accountRunningModel = new AccountRunningModel();

            $dataInsertToBank = [
                [
                    'ID' => $acc_id,
                    'L_Code' => $payment_method, // Bank Account
                    'Date' => $date,
                    'Discription' => $description,
                    'Dr' => $amount,
                    'Cr' => 0.00,
                    'P_Month' => date('Y-m-d'),
                    'Invoice_Num' => $transaction_id,
                    'Cashier' => session()->get('acc_no'),
                    'Recpt_No' => $description,
                    'Dt' => date('Y-m-d H:i:s'),
                ],
                [
                    'ID' => $acc_id,
                    'L_Code' => $customer, // Customer
                    'Date' => $date,
                    'Discription' => $description,
                    'Dr' => 0.00,
                    'Cr' => $amount,
                    'P_Month' => date('Y-m-d'),
                    'Invoice_Num' => $transaction_id,
                    'Cashier' => session()->get('acc_no'),
                    'Recpt_No' => $description,
                    'Dt' => date('Y-m-d H:i:s'),
                ]
            ];

            $accountRunningModel->insertBatch($dataInsertToBank);

            return json_encode(['status' => 200, 'message' => 'Payment completed successfully']);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return json_encode(['status' => 500, 'message' => 'An error occurred while processing the payment']);
        }
    }
}
