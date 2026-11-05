<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountRunningModel;
use App\Models\AccountPostingModel;
use App\Models\InvoiceNumberModel;
use App\Models\DebtorsqqdollarModel;
use App\Models\ConsigneeModel;

class CreditNoteController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/accounts/debit_note
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountPostingModel = new AccountPostingModel();
        $data['customers'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 2)->where('H_Code', 5)->findAll();

        $data['is_search'] = false;

        // $supplier = $this->request->getGet('supplier');
        $customer = $this->request->getGet('customer');
        $from = $this->request->getGet('from');
        $to = $this->request->getGet('to');

        if ($customer != null) {

            $data['is_search'] = true;

            $data['customer'] = $customer;
            $data['from'] = $from;
            $data['to'] = $to;

            $accountRunningModel = new AccountRunningModel();
            // $accRecords = $accountRunningModel->select('sum(Dr) as Dr, sum(Cr) As Cr,max(L_Code) as L_Code, min(Discription) as Discription, Date, Invoice_Num, dollarrate, dollar, index_no, consignee.consignee, consignee.manual_id');
            // $accRecords = $accRecords->join('consignee', 'consignee.tour_id = account_runing.index_no');
            // $accRecords = $accRecords->where('L_Code', $customer);
            // $accRecords = $accRecords->having('sum(Cr) - sum(Dr) !=', 0.00);
            // $accRecords = $accRecords->orderBy('Date', 'DESC');
            // $accRecords = $accRecords->groupBy('index_no');
            // $transactions = $accRecords->findAll();

            if ($from == null && $to == null) {

                $debtorQQDollarModel = new DebtorsqqdollarModel();
                $accRecords = $debtorQQDollarModel->select('HName, SDr as Dr, SCr as Cr, Invoice_Num, status as Discription, Bal, L_Code, FirstOfDate, PName, index_no, consignee.manual_id, dollarrate, dollar, Discription, dollarrate_cr, dollar_cr, DBal');
                $accRecords = $debtorQQDollarModel->where('L_Code', $customer)->where('DBal >', 0.00);
                $accRecords = $accRecords->join('consignee', 'consignee.tour_id = deptors_qq_dollar.index_no', 'left');
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

                // $accRecords = $accountRunningModel->select('sum(Dr) as Dr, sum(Cr) As Cr,max(L_Code) as L_Code, Discription, Date, Invoice_Num, dollarrate, dollar,  index_no, consignee.consignee, consignee.manual_id');
                // $accRecords = $accRecords->join('consignee', 'consignee.tour_id = account_runing.index_no');
                // $accRecords = $accRecords->where('L_Code', $customer);
                // if ($from != null) $accRecords = $accRecords->where('Date >=', $from);
                // if ($to != null) $accRecords = $accRecords->where('Date <=', $to);
                // $accRecords = $accRecords->having('sum(Cr) - sum(Dr) !=', 0.00);
                // $accRecords = $accRecords->orderBy('Date', 'DESC');
                // $accRecords = $accRecords->groupBy('index_no');
                // $transactions = $accRecords->findAll();

                // log_message('critical', 'Transactions: ' . $accRecords->getLastQuery());

                // get last 20 transactions
                $data['transactions'] = $transactions;
                // total in 0.00 format
                $data['total'] = number_format($total, 2, '.', ',');

            } else {

                $consigneeModel = new ConsigneeModel();
                $tourdetails = $consigneeModel->select('tour_id')->where('start_date >=', $from)->where('end_date <=', $to)->findAll();
                $tour_ids = array_column($tourdetails, 'tour_id');

                $debtorQQDollarModel = new DebtorsqqdollarModel();
                $accRecords = $debtorQQDollarModel->select('HName, SDr as Dr, SCr as Cr, Invoice_Num, status as Discription, Bal, L_Code, FirstOfDate, PName, index_no, consignee.manual_id, dollarrate, dollar, Discription, dollarrate_cr, dollar_cr, DBal');
                $accRecords = $debtorQQDollarModel->where('L_Code', $customer)->where('DBal >', 0.00)->whereIn('index_no', $tour_ids);
                $accRecords = $accRecords->join('consignee', 'consignee.tour_id = deptors_qq_dollar.index_no', 'left');
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
            }
            // var_dump($data['total']); die();
        }
        return view('pages/accounts/credit_note', $data);
    }

    public function complete_payment()
    {

        try {
            $customer = $this->request->getPost('customer');
            $transactions = $this->request->getPost('transactions');

            if (empty($transactions)) {
                return $this->response->setJSON([
                    "status" => 400,
                    "message" => "No transactions selected"
                ]);
            }

            foreach ($transactions as $transaction) {
                $tour_manu = $transaction['tour_id_manu'];
                $payment = floatval($transaction['cr']);
                $date = date('Y-m-d');
                $confirmation_reference = $transaction['invoice_no'];

                // Original dollar data from invoice (Dr side)
                $doller_rate = $transaction['dollarrate'] ?? 0;
                $doller_count = $transaction['dollar'] ?? 0;

                // User input dollar data for credit note (Cr side)
                $doller_rate_cr = floatval($transaction['d_rate'] ?? 0);
                $doller_count_cr = floatval($transaction['d_amt'] ?? 0);

                $customer = $customer;
                $tour = $transaction['tour_id'];
                $description = $transaction['description'];

                if (strpos($description, 'VOUCHER PAYMENT') !== false) {
                    $is_voucher = 1;
                } else {
                    $is_voucher = 0;
                }

                if (empty($payment) || $payment == 0 || $is_voucher == 1) {
                    continue;
                }

                $session = session();
                $username = $session->get('username');

                $invoiceNoModel = new InvoiceNumberModel();
                $acc_id = $invoiceNoModel->incrementAccId();

                $accountRunningModel = new AccountRunningModel();

                // Credit Note Entry: Dr Tour Income, Cr Agent Account
                $accountRunningModel->insertBatch([
                    [
                        'ID' => $acc_id,
                        'L_Code' => '5-7-7-10388', // Tour Income
                        'Date' => $date,
                        'Discription' => 'Credit Note ' . $tour_manu . ' / ' . $date,
                        'Dr' => $payment,
                        'Cr' => 0.00,
                        'Cashier' => $username,
                        'index_no' => $tour,
                        'job' => $customer,
                        'Invoice_Num' => $confirmation_reference,
                        'advances_pay' => 22,
                        'Dt' => date('Y-m-d H:i:s'),
                        'dollarrate' => $doller_rate,
                        'dollar' => $doller_count,
                        'dollarrate_cr' => $doller_rate_cr,
                        'dollar_cr' => $doller_count_cr
                    ],
                    [
                        'ID' => $acc_id,
                        'L_Code' => $customer, // Agent Account
                        'Date' => $date,
                        'Discription' => 'Credit Note ' . $tour_manu . ' / ' . $date,
                        'Dr' => 0.00,
                        'Cr' => $payment,
                        'Cashier' => $username,
                        'index_no' => $tour,
                        'job' => '5-7-7-10388',
                        'Invoice_Num' => $confirmation_reference,
                        'advances_pay' => 22,
                        'Dt' => date('Y-m-d H:i:s'),
                        'dollarrate' => $doller_rate,
                        'dollar' => $doller_count,
                        'dollarrate_cr' => $doller_rate_cr,
                        'dollar_cr' => $doller_count_cr
                    ]
                ]);
            }
            return $this->response->setJSON([
                "status" => 200,
                "message" => "Successfully Processed Credit Note"
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                "status" => 500,
                "message" => "An error occurred: " . $e->getMessage()
            ]);
        }
    }
}
