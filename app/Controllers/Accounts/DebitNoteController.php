<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountRunningModel;
use App\Models\CustomerModel;
use App\Models\DebitNoteModel;
use App\Models\DebtorInvoiceLogModel;
use App\Models\InvoiceNumberModel;
use App\Models\TourQuotationsModel;
use App\Models\AccountPostingModel;
use App\Models\AccSelectModel;

class DebitNoteController extends BaseController
{
    protected $db;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
        $this->db = \Config\Database::connect();
    }

    // route : base_url/accounts/debit_note
    public function index()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountPostingModel = new AccountPostingModel();
        $data['suppliers'] = $accountPostingModel->where('B_Code', 6)->where('T_Code', 9)->where('H_Code', 11)->orWhere('H_Code', 23)->findAll();

        $data['methods'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();


        $supplier = $this->request->getGet('supplier');
        $from = $this->request->getGet('from');
        $to = $this->request->getGet('to');
        $rate = $this->request->getGet('rate');
        $doller_amt = $this->request->getGet('doller_amt');
        $lkr_amt = $this->request->getGet('lkr_amt');

        $data['is_search'] = false;


        if ($supplier != null) {

            $data['is_search'] = true;

            $data['supplier'] = $supplier;
            $data['from'] = $from;
            $data['to'] = $to;
            $data['rate'] = $rate;
            $data['doller_amt'] = $doller_amt;
            $data['lkr_amt'] = $lkr_amt;

            // Use $supplier as $id for query
            $query = $this->db->table('account_runing')
                ->select('
                    SUM(Dr) as dr, 
                    SUM(Cr) as cr, 
                    MAX(Discription) as Discription, 
                    SUM(Cr) - SUM(Dr) as bal, 
                    MAX(Recpt_No) as voucher_no, 
                    L_Code as lcode, 
                    MAX(Date) as date, 
                    supplier.Name as supplier_name, 
                    Invoice_Num,
                    consignee.manual_id,
                    account_runing.index_no
                ')
                ->join('supplier', 'supplier.Sup_Acc_No = account_runing.L_Code')
                ->join('consignee', 'consignee.tour_id = account_runing.index_no', 'left')
                ->where('L_Code', $supplier)
                ->having('bal >', 0);

            if ($from) {
                $query = $query->where('Date >=', $from);
            }
            if ($to) {
                $query = $query->where('Date <=', $to);
            }

            $query = $query->groupBy('account_runing.Invoice_Num, L_Code')
                ->orderBy('date', 'DESC')
                ->get()
                ->getResultArray();

                // log_message('critical', 'Debit Note Query: ' . $this->db->getLastQuery());

            $results = [];
            $total_dr = 0;
            $total_cr = 0;

            foreach ($query as $row) {
                $voucherQuery = $this->db->table('v_inv')
                    ->select('SUM(Amount) as total_amount')
                    ->where('account', $row['lcode'])
                    ->where('Invoice', $row['Invoice_Num'])
                    ->where('Date', $row['date'])
                    ->where('RCType', 0)
                    ->get()
                    ->getRowArray();

                $row['total_amount'] = $voucherQuery['total_amount'] ?? 0;

                $total_dr += $row['dr'];
                $total_cr += $row['cr'];

                $results[] = $row;
            }

            $total = $total_dr - $total_cr;
            $data['transactions'] = $results;
            $data['total'] = number_format($total, 2, '.', ',');
        }

        return view('pages/accounts/debit_note', $data);
    }

    public function fetch($query)
    {
        $accselectmodel = new AccSelectModel();

        $data = $accselectmodel
            ->groupStart()
            ->orLike('PName', $query)
            ->orLike('TName', $query)
            ->orLike('HName', $query)
            ->orLike('BName', $query)
            ->groupEnd()
            ->findAll();

        return json_encode($data);
    }

    public function complete_payment()
    {
        try {
            $supplier = $this->request->getPost('supplier');
            $pay_acc = $this->request->getPost('pay_acc');
            $transactions = $this->request->getPost('transactions');

            if (empty($transactions)) {
                return $this->response->setJSON([
                    "status" => 400,
                    "message" => "No transactions selected"
                ]);
            }

            foreach ($transactions as $transaction) {
                //  date: date,
                //     tour_id: tour_id,
                //     invoice_no: invoice_no,
                //     description: description,
                //     due: due,
                //     payment_received: payment_received

                $tour_manu = $transaction['tour_id_manu'];
                $payment = floatval($transaction['payment_received']);
                $date = date('Y-m-d');
                $confirmation_reference = $transaction['invoice_no'];
                $supplier = $supplier;
                $bal = $transaction['due'];
                $description = $transaction['description'];
                $tour = $transaction['tour_id'];

                // if (strpos($description, 'VOUCHER PAYMENT') !== false) {
                //     $is_voucher = 1;
                // } else {
                //     $is_voucher = 0;
                // }

                // log_message('critical', 'Tour: ' . $tour . ' Payment: ' . $payment . ' Date: ' . $date . ' Confirmation Reference: ' . $confirmation_reference . ' Dollar Rate: ' . $doller_rate . ' Dollar Count: ' . $doller_count . ' Hotel: ' . $hotel . ' Tour Manu: ' . $tour_manu . ' vouvher: ' . $is_voucher);


                if (empty($payment) || $payment == 0) {
                    continue;
                }

                $session = session();
                $username = $session->get('username');

                $invoiceNoModel = new InvoiceNumberModel();
                $acc_id = $invoiceNoModel->incrementAccId();

                $accountRunningModel = new AccountRunningModel();

                $accountRunningModel->insertBatch([
                    [
                        'ID' => $acc_id,
                        'L_Code' => $supplier,
                        'Date' => $date,
                        'Discription' => 'Debit Note ' . $tour_manu . ' / ' . $date,
                        'Dr' => $payment,
                        'Cr' => 0.00,
                        'Cashier' => $username,
                        'index_no' => $tour,
                        'job' => $pay_acc,
                        'Invoice_Num' => $confirmation_reference,
                        'advances_pay' => '',
                        'Dt' => date('Y-m-d H:i:s')
                    ],
                    [
                        'ID' => $acc_id,
                        'L_Code' => $pay_acc,
                        'Date' => $date,
                        'Discription' => 'Debit Note ' . $tour_manu . ' / ' . $date,
                        'Dr' => 0.00,
                        'Cr' => $payment,
                        'Cashier' => $username,
                        'index_no' => $tour,
                        'job' => $supplier,
                        'Invoice_Num' => $confirmation_reference,
                        'advances_pay' => '',
                        'Dt' => date('Y-m-d H:i:s'),
                    ]
                ]);
            }
            return $this->response->setJSON([
                "status" => 200,
                "message" => "Successfully processed debit note payments."
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                "status" => 500,
                "message" => "An error occurred: " . $e->getMessage()
            ]);
        }
    }
}
