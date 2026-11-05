<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\CustomerModel;
use App\Models\InvoiceNumberModel;
use App\Models\SupplierModel;
use App\Models\DebtorReceiptPaymentModel;
use App\Models\DebtorsqqModel;
use App\Models\ConsigneeModel;
use App\Models\DebtorsqqdollarModel;
use App\Models\AccountRunningUsdModel;

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

        $data['methods'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->like('PName', 'LKR', 'both')->findAll();
        $data['usd_methods'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->like('PName', 'USD', 'both')->findAll();

        $customer = $this->request->getGet('customer');
        $from = $this->request->getGet('from');
        $to = $this->request->getGet('to');
        // $rate = $this->request->getGet('rate');
        // $doller_amt = $this->request->getGet('doller_amt');
        // $lkr_amt = $this->request->getGet('lkr_amt');

        $data['is_search'] = false;

        if ($customer != null) {

            $data['is_search'] = true;

            $data['customer'] = $customer;

            $data['from'] = $from;
            $data['to'] = $to;
            // $data['rate'] = $rate;
            // $data['doller_amt'] = $doller_amt;
            // $data['lkr_amt'] = $lkr_amt;

            $accountRunningModel = new AccountRunningModel();
            // $accRecords = $accountRunningModel->select('sum(Dr) as Dr, sum(Cr) As Cr,max(L_Code) as L_Code, Discription, Date, Invoice_Num, dollarrate, dollar, index_no, consignee.manual_id, (SUM(Dr) - SUM(Cr)) AS bal');
            // $accRecords = $accRecords->join('consignee', 'consignee.tour_id = account_runing.index_no', 'left');
            // $accRecords = $accRecords->where('L_Code', $customer);
            // $accRecords = $accRecords->groupBy('Invoice_Num');
            // $accRecords = $accRecords->having('bal >', 0.00);
            // $accRecords = $accRecords->orderBy('Date', 'ASC');
            // $transactions = $accRecords->findAll();
            
            if ($from == null && $to == null) {
                $debtorQQDollarModel = new DebtorsqqdollarModel();
                $accRecords = $debtorQQDollarModel->select('HName, SDr as Dr, SCr as Cr, Invoice_Num, deptors_qq_dollar.status as Discription, Bal, L_Code, FirstOfDate, PName, index_no, consignee.manual_id, debtor_invoice_log.doller_rate as dollarrate, debtor_invoice_log.dollar_amount as dollar, Discription, dollarrate_cr, dollar_cr, DBal, other_invoice_log.amount_jpy as oi_amount_jpy, other_invoice_log.conversion_rate as oi_conversion_rate, other_invoice_log.amount_lkr as oi_amount_lkr');
                $accRecords = $debtorQQDollarModel->where('L_Code', $customer)->groupStart()->where('DBal >', 0.00)->orGroupStart()->where('Bal >', 0.00)->where('DDr', 0)->groupEnd()->groupEnd()->groupStart()->where('debtor_invoice_log.cancel', 0)->orWhere('debtor_invoice_log.cancel IS NULL')->groupEnd();
                $accRecords = $accRecords->join('consignee', 'consignee.tour_id = deptors_qq_dollar.index_no', 'left');
                $accRecords = $accRecords->join('debtor_invoice_log', 'debtor_invoice_log.invoice_no = deptors_qq_dollar.Invoice_Num', 'left');
                $accRecords = $accRecords->join('other_invoice_log', 'other_invoice_log.invoice_no = deptors_qq_dollar.Invoice_Num', 'left');
                $accRecords = $accRecords->orderBy('FirstOfDate', 'ASC');
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

                $data['total'] = number_format($total, 2, '.', ',');
                $data['transactions'] = $transactions;

                // $accRecords = $accountRunningModel->select('sum(Dr) as Dr, sum(Cr) As Cr,max(L_Code) as L_Code, Discription, Date, Invoice_Num, dollarrate, dollar, index_no, consignee.manual_id, (SUM(Dr) - SUM(Cr)) AS bal');
                // $accRecords = $accRecords->join('consignee', 'consignee.tour_id = account_runing.index_no', 'left');
                // $accRecords = $accRecords->where('L_Code', $customer);
                // if ($from != null) $accRecords = $accRecords->where('Date >=', $from);
                // if ($to != null) $accRecords = $accRecords->where('Date <=', $to);
                // $accRecords = $accRecords->groupBy('Invoice_Num');
                // $accRecords = $accRecords->having('bal >', 0.00);
                // $accRecords = $accRecords->orderBy('Date', 'ASC');
                // $transactions = $accRecords->findAll();

                // log_message('critical', 'Transactions: ' . $accRecords->getLastQuery());
            } else {
                // i need to select consignee table startdate and enddate between from and to
                $consigneeModel = new ConsigneeModel();
                $tourdetails = $consigneeModel->select('tour_id')->where('start_date >=', $from)->where('end_date <=', $to)->findAll();
                $tour_ids = array_column($tourdetails, 'tour_id');

                $debtorQQDollarModel = new DebtorsqqdollarModel();
                $accRecords = $debtorQQDollarModel->select('HName, SDr as Dr, SCr as Cr, Invoice_Num, deptors_qq_dollar.status as Discription, Bal, L_Code, FirstOfDate, PName, index_no, consignee.manual_id, debtor_invoice_log.doller_rate as dollarrate, debtor_invoice_log.dollar_amount as dollar, Discription, dollarrate_cr, dollar_cr, DBal, other_invoice_log.amount_jpy as oi_amount_jpy, other_invoice_log.conversion_rate as oi_conversion_rate, other_invoice_log.amount_lkr as oi_amount_lkr');
                $accRecords = $debtorQQDollarModel->where('L_Code', $customer)->groupStart()->where('DBal >', 0.00)->orGroupStart()->where('Bal >', 0.00)->where('DDr', 0)->groupEnd()->groupEnd()->groupStart()->where('debtor_invoice_log.cancel', 0)->orWhere('debtor_invoice_log.cancel IS NULL')->groupEnd()->groupStart();
                if (!empty($tour_ids)) {
                    $accRecords = $accRecords->whereIn('index_no', $tour_ids)->orGroupStart()->where('index_no', '0')->where('FirstOfDate >=', $from)->where('FirstOfDate <=', $to)->groupEnd();
                } else {
                    $accRecords = $accRecords->where('index_no', '0')->where('FirstOfDate >=', $from)->where('FirstOfDate <=', $to);
                }
                $accRecords = $accRecords->groupEnd();
                $accRecords = $accRecords->join('consignee', 'consignee.tour_id = deptors_qq_dollar.index_no', 'left');
                $accRecords = $accRecords->join('debtor_invoice_log', 'debtor_invoice_log.invoice_no = deptors_qq_dollar.Invoice_Num', 'left');
                $accRecords = $accRecords->join('other_invoice_log', 'other_invoice_log.invoice_no = deptors_qq_dollar.Invoice_Num', 'left');
                $accRecords = $accRecords->orderBy('consignee.start_date', 'ASC');
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

                $data['total'] = number_format($total, 2, '.', ',');
                $data['transactions'] = $transactions;

                // log_message('critical', 'Transactions: ' . $accRecords->getLastQuery());
            }
        }
        return view('pages/accounts/debtor_payment', $data);
    }

    public function date_filter() {}

    // route : base_url/accounts/creditor_payment/complete_payment
    public function complete_payment()
    {
        try {
            $customer = $this->request->getPost('customer');
            $amount = $this->request->getPost('amount');
            $date = $this->request->getPost('date');
            $payment_method = $this->request->getPost('payment_method');
            $usd_payment_method = $this->request->getPost('usd_payment_method');
            $description = $this->request->getPost('ref');
            $transactions = $this->request->getPost('transactions') ?? [];

            if ($customer == null || $amount == null || $date == null || $payment_method == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($amount)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            foreach ($transactions as $transaction) {

                $transaction_date = $transaction['date'];
                $transaction_tour_manu = $transaction['tour_manu'];
                $transaction_id = $transaction['invoice_no'];
                $transaction_description = $transaction['description'];
                $transaction_d_amt = $transaction['d_amt'];
                $transaction_d_rate = $transaction['d_rate'];
                $transaction_dr = $transaction['dr'];
                $transaction_cr = $transaction['cr'];
                $transaction_bal = $transaction['bal'];
                $transaction_status = $transaction['status'];
                $transaction_tour_id = $transaction['tour_id'];


                if ($transaction_id == null || $transaction_cr == null || $transaction_cr == '' || $transaction_date == null || $transaction_bal == null) {
                    return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
                }


                $accountRunningModel = new AccountRunningModel();
                $DebtorReceiptModel = new DebtorReceiptPaymentModel();

                if (($transaction_cr == 0) && ($transaction_bal == '0' || $transaction_bal == 0)) {
                    continue;
                }


                $invoiceNoModel = new InvoiceNumberModel();
                $acc_id = $invoiceNoModel->incrementAccId();
                $debtor_receiptNo = $invoiceNoModel->incrementDebtorReceiptNo();

                $receiptInsert = [
                    'receipt_no' => $debtor_receiptNo,
                    'customer_id' => $customer,
                    'payment_date' => $date,
                    'Date' => date('Y-m-d H:i:s'),
                    'bank_amount' => $transaction_cr,
                    'slip_no' => $transaction_id,
                    'booking_no' => $transaction_tour_manu,
                    'created_by' => session()->get('username'),
                    'tour_id' => $transaction_tour_id,
                    'dollar' => $transaction_d_amt,
                    'dollarrate' => $transaction_d_rate,
                ];

                $commonData = [
                    'ID' => $acc_id,
                    'Date' => $transaction_date,
                    'Discription' => $transaction_description . ' - ' . 'debtor payment ',
                    'P_Month' => date('Y-m-d'),
                    'Invoice_Num' => $transaction_id,
                    'Cashier' => session()->get('username'),
                    'Recpt_No' => $description,
                    'Dt' => date('Y-m-d H:i:s'),
                    'index_no' => $transaction_tour_id,
                    'dollar_cr' => $transaction_d_amt,
                    'dollarrate_cr' => $transaction_d_rate,
                ];

                $dataToInsert = [
                    array_merge($commonData, [
                        'L_Code' => '1-2-5-10779', // Debtor Control Account
                        'Dr' => $transaction_cr,
                        'Cr' => 0.00,
                    ]),
                    array_merge($commonData, [
                        'L_Code' => $customer, // Customer
                        'Dr' => 0.00,
                        'Cr' => $transaction_cr,
                    ]),
                ];

                $accountRunningModel->insertBatch($dataToInsert);
                $DebtorReceiptModel->insert($receiptInsert);

                if ($transaction_status == 'Loss') { // income acc dr, controll acc cr
                    $dataexchangepnl = [
                        array_merge($commonData, ['L_Code' => '8-35-111-10780', 'Dr' => $transaction_bal, 'Cr' => 0.00, 'dollar_cr' => 0, 'dollarrate_cr' => 0]), // Income Foreign Exchange Gain-Loss
                        array_merge($commonData, ['L_Code' => '1-2-5-10779', 'Dr' => 0.00, 'Cr' => $transaction_bal, 'dollar_cr' => 0, 'dollarrate_cr' => 0]), // Debtor Control Account
                    ];
                } else if ($transaction_status == 'Profit') { // controll acc dr, income acc cr
                    $dataexchangepnl = [
                        array_merge($commonData, ['L_Code' => '1-2-5-10779', 'Dr' => $transaction_bal, 'Cr' => 0.00, 'dollar_cr' => 0, 'dollarrate_cr' => 0]), // Debtor Control Account
                        array_merge($commonData, ['L_Code' => '8-35-111-10780', 'Dr' => 0.00, 'Cr' => $transaction_bal, 'dollar_cr' => 0, 'dollarrate_cr' => 0]), // Income Foreign Exchange Gain-Loss
                    ];
                } else {
                    continue;
                }

                $accountRunningModel->insertBatch($dataexchangepnl);
            }

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $accountRunningModel = new AccountRunningModel();

            $DebtorReceiptModel = new DebtorReceiptPaymentModel();
            $debtor_receiptNo = $invoiceNoModel->incrementDebtorReceiptNo();

            $dataInsertToBank = [
                [
                    'ID' => $acc_id,
                    'L_Code' => $payment_method, // Bank Account
                    'Date' => $date,
                    'Discription' => $description . ' - ' . 'debtor payment',
                    'Dr' => $amount,
                    'Cr' => 0.00,
                    'P_Month' => date('Y-m-d'),
                    'Invoice_Num' => 'DP' . '-' . $debtor_receiptNo,
                    'Cashier' => session()->get('username'),
                    'Recpt_No' => $debtor_receiptNo,
                    'Dt' => date('Y-m-d H:i:s'),

                ],
                [
                    'ID' => $acc_id,
                    // 'L_Code' => $customer, // Customer
                    'L_Code' => '1-2-5-10779', // Debtor Control Account
                    'Date' => $date,
                    'Discription' => $description . ' - ' . 'debtor payment',
                    'Dr' => 0.00,
                    'Cr' => $amount,
                    'P_Month' => date('Y-m-d'),
                    'Invoice_Num' => 'DP' . '-' . $debtor_receiptNo,
                    'Cashier' => session()->get('username'),
                    'Recpt_No' => $debtor_receiptNo,
                    'Dt' => date('Y-m-d H:i:s'),
                ]
            ];

            $accountRunningModel->insertBatch($dataInsertToBank);

            // Insert total dollar amount into account_runing_usd
            if ($usd_payment_method && $usd_payment_method != '0') {
                $total_d_amt = 0;
                $last_d_rate = 0;
                foreach ($transactions as $t) {
                    if (!empty($t['d_amt']) && floatval($t['d_amt']) > 0) {
                        $total_d_amt += floatval($t['d_amt']);
                        $last_d_rate = floatval($t['d_rate']);
                    }
                }

                if ($total_d_amt > 0) {
                    $accountRunningUsdModel = new AccountRunningUsdModel();
                    $accountRunningUsdModel->insert([
                        'ID' => $acc_id,
                        'L_Code' => $usd_payment_method, // USD Bank Account DR only
                        'Date' => $date,
                        'Discription' => $description . ' - debtor payment',
                        'Dr' => $total_d_amt,
                        'Cr' => 0.00,
                        'P_Month' => date('Y-m-d'),
                        'Invoice_Num' => 'DP' . '-' . $debtor_receiptNo,
                        'Cashier' => session()->get('username'),
                        'Recpt_No' => $debtor_receiptNo,
                        'Dt' => date('Y-m-d H:i:s'),
                        'dollarrate_cr' => $last_d_rate,
                        'dollar_cr' => $total_d_amt,
                    ]);
                }
            }

            return json_encode(['status' => 200, 'message' => 'Payment completed successfully']);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return json_encode(['status' => 500, 'message' => 'An error occurred while processing the payment']);
        }
    }

    public function debtor_receipt_report()
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

            $DebtorreceiptModel = new DebtorReceiptPaymentModel();

            $result = $DebtorreceiptModel->select('debtor_receipts.*, customers.Name as cus_name, consignee.manual_id as manual_id, consignee.consignee as consignee, consignee.start_date as start_date');
            // if (!empty($cus) && !empty($date1) && !empty($date2)) {
            //     $result->where('debtor_receipt_payment.agent', $cus);
            //     // $result->where('debtor_receipt_payment.date >=', $date1);
            //     // $result->where('debtor_invoice_log.date <=', $date2);
            //     $result->where('consignee.start_date >=', $date1);
            //     $result->where('consignee.start_date <=', $date2);
            // }

            // if (empty($cus) && !empty($date1) && !empty($date2)) {
            //     // $result->where('debtor_invoice_log.date >=', $date1);
            //     // $result->where('debtor_invoice_log.date <=', $date2);
            //     $result->where('consignee.start_date >=', $date1);
            //     $result->where('consignee.start_date <=', $date2);
            // }

            if (!empty($cus) && empty($date1) && empty($date2)) {
                $result->where('debtor_receipts.customer_id', $cus);
            }

            // $result->where('debtor_receipts.cancel', 0);
            // $result->orwhere('debtor_receipts.cancel', 3);
            $result->join('customers', 'customers.Sup_Acc_No = debtor_receipts.customer_id', 'left');
            $result->join('consignee', 'consignee.tour_id = debtor_receipts.booking_no', 'left');
            $result->groupBy('debtor_receipts.slip_no');

            $data['payments'] = $result->findAll();

            $data['cus'] = $cus;
            $data['date1'] = $date1;
            $data['date2'] = $date2;
        }


        // log_message('critical', $accountRunningModel->getLastQuery());
        return view('pages/accounts/debtor_receipt_report', $data);
    }
}
