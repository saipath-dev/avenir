<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\InvoiceNumberModel;
use App\Models\SupplierModel;

class CreditorPaymentController extends BaseController
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
        $data['auctions'] = $supplierModel->findAll();

        $accountPostingModel = new AccountPostingModel();
        $data['methods'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $auction = $this->request->getGet('auction');
        $from = $this->request->getGet('from');
        $to = $this->request->getGet('to');

        $data['is_search'] = false;

        if ($auction != null) {

            $data['is_search'] = true;

            $data['auction'] = $auction;
            $data['from'] = $from;
            $data['to'] = $to;

            $accountRunningModel = new AccountRunningModel();

            $accRecords = $accountRunningModel->where('L_Code', $auction);
            $accRecords = $accRecords->orderBy('Date', 'DESC');
            $transactions = $accRecords->findAll();

            // get transactions total by trasactions[Dr] - transactions[Cr]
            $total = 0;
            $total_dr = 0;
            $total_cr = 0;
            // transaction total for given date range
            $daterange_total = 0;
            $daterange_total_dr = 0;
            $daterange_total_cr = 0;
            foreach ($transactions as $transaction) {
                $total_dr += $transaction['Dr'];
                $total_cr += $transaction['Cr'];

                if ($from != null && $to != null) {
                    if (strtotime($transaction['Date']) >= strtotime(strval($from)) && strtotime($transaction['Date']) <= strtotime(strval($to))) {
                        $daterange_total_dr += $transaction['Dr'];
                        $daterange_total_cr += $transaction['Cr'];
                    }
                }
            }
            $total = $total_cr - $total_dr;
            $daterange_total = $daterange_total_cr - $daterange_total_dr;

            $accRecords = $accountRunningModel->where('L_Code', $auction);
            if ($from != null) $accRecords = $accRecords->where('Date >=', $from);
            if ($to != null) $accRecords = $accRecords->where('Date <=', $to);
            $accRecords = $accRecords->orderBy('Date', 'DESC');
            $transactions = $accRecords->findAll();

            // get last 20 transactions
            $data['transactions'] = $transactions;
            // total in 0.00 format
            $data['total'] = number_format($total, 2, '.', ',');
            $data['daterange_total'] = number_format($daterange_total, 2, '.', ',');

            // var_dump($data['daterange_total']); 
            // die();
        }

        return view('pages/accounts/creditor_payment', $data);
    }

    // route : base_url/accounts/creditor_payment/complete_payment
    public function complete_payment()
    {
        $auction = $this->request->getPost('auction');
        $amount = $this->request->getPost('amount');
        $date = $this->request->getPost('date');
        $tour_id = $this->request->getPost('tour_id');
        $payment_method = $this->request->getPost('payment_method');
        $description = $this->request->getPost('description');
        $inv = $this->request->getPost('inv');

        if ($auction == null || $tour_id == null || $amount == null || $date == null || $payment_method == null || $inv == null) {
            return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
        }

        if (!is_numeric($amount)) {
            return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
        }

        $invoiceNoModel = new InvoiceNumberModel();
        $acc_id = $invoiceNoModel->incrementAccId();

        $accountRunningModel = new AccountRunningModel();

        // DR Recrord
        $accountRunningModel->insert([
            'ID' => $acc_id,
            'L_Code' => $auction,
            'Date' => $date,
            'Discription' => $description,
            'Dr' => $amount,
            'Cr' => 0.00,
            'index_no' => $tour_id,
            'Dt' => date('Y-m-d H:i:s'),
            'Invoice_Num' => $inv
        ]);

        // CR Record
        $accountRunningModel->insert([
            'ID' => $acc_id,
            'L_Code' => $payment_method,
            'Date' => $date,
            'Discription' => $description,
            'Dr' => 0.00,
            'Cr' => $amount,
            'index_no' => $tour_id,
            'Dt' => date('Y-m-d H:i:s'),
            'Invoice_Num' => $inv
        ]);

        return json_encode(['status' => 200, 'message' => 'Payment completed successfully']);
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
