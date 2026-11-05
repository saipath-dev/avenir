<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\InvoiceNumberModel;
use App\Models\OtherInvoiceLogModel;
use App\Models\OtherInvoicePaymentModel;

class ServiceProviderPaymentController extends BaseController
{
    protected $liontours_account = '1-2-5-10635';

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/accounts/service_provider_payment
    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountPostingModel = new AccountPostingModel();
        $data['methods'] = $accountPostingModel
            ->where('B_Code', 1)
            ->where('T_Code', 6)
            ->where('H_Code', 8)
            ->like('PName', 'LKR', 'both')
            ->findAll();

        $otherInvoiceLogModel = new OtherInvoiceLogModel();

        // Outstanding invoices: remaining JPY balance > 0 and not cancelled
        $invoices = $otherInvoiceLogModel
            ->where('cancel', 0)
            ->where('(amount_jpy - COALESCE(pay_jpy_amount, 0)) >', 0)
            ->orderBy('date', 'ASC')
            ->findAll();

        $data['invoices']              = $invoices;
        $data['total_outstanding_jpy'] = array_sum(array_map(fn($i) => (float)$i['amount_jpy'] - (float)($i['pay_jpy_amount'] ?? 0), $invoices));
        $data['total_outstanding_lkr'] = array_sum(array_map(fn($i) => (float)$i['amount_lkr'], $invoices));
        $data['total_invoices']        = count($invoices);

        return view('pages/accounts/service_provider_payment', $data);
    }

    // route : base_url/accounts/service_provider_payment/complete_payment
    public function complete_payment()
    {
        try {
            $payment_method = $this->request->getPost('payment_method');
            $date           = $this->request->getPost('date');
            $ref            = $this->request->getPost('ref');
            $transactions   = $this->request->getPost('transactions') ?? [];

            if (empty($payment_method) || empty($date) || empty($transactions)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Please fill all required fields']);
            }

            $invoiceNoModel           = new InvoiceNumberModel();
            $accountRunningModel      = new AccountRunningModel();
            $otherInvoiceLogModel     = new OtherInvoiceLogModel();
            $otherInvoicePaymentModel = new OtherInvoicePaymentModel();
            $now                      = date('Y-m-d H:i:s');

            foreach ($transactions as $t) {
                $invoice_no = $t['invoice_no'];
                $cr         = floatval($t['cr']);

                if ($cr <= 0) {
                    continue;
                }

                $acc_id      = $invoiceNoModel->incrementAccId();
                $description = ($ref ? $ref . ' - ' : '') . 'SP Payment - ' . $invoice_no;

                // DR: Bank / Payment Method (cash received)
                $accountRunningModel->insert([
                    'ID'          => $acc_id,
                    'L_Code'      => $payment_method,
                    'Date'        => $date,
                    'Discription' => $description,
                    'Dr'          => $cr,
                    'Cr'          => 0.00,
                    'Invoice_Num' => $invoice_no,
                    'Cashier'     => session()->get('username'),
                    'Recpt_No'    => $ref,
                    'Dt'          => $now,
                ]);

                // CR: LionTours Account (settle the receivable)
                $accountRunningModel->insert([
                    'ID'          => $acc_id,
                    'L_Code'      => $this->liontours_account,
                    'Date'        => $date,
                    'Discription' => $description,
                    'Dr'          => 0.00,
                    'Cr'          => $cr,
                    'Invoice_Num' => $invoice_no,
                    'Cashier'     => session()->get('username'),
                    'Recpt_No'    => $ref,
                    'Dt'          => $now,
                ]);

                // Insert payment record into other_invoice_payment
                $otherInvoicePaymentModel->insert([
                    'invoice_no'     => $invoice_no,
                    'payment_date'   => $date,
                    'payment_method' => $payment_method,
                    'pay_jpy_amount' => floatval($t['pay_jpy_amount'] ?? 0),
                    'pay_rate'       => floatval($t['pay_rate'] ?? 0),
                    'pay_lkr_amount' => $cr,
                    'ref'            => $ref,
                    'created_by'     => session()->get('username'),
                    'created_at'     => $now,
                ]);

                // Update other_invoice_log: accumulate pay_jpy_amount on top of existing value
                $new_jpy = floatval($t['pay_jpy_amount'] ?? 0);
                $otherInvoiceLogModel
                    ->where('invoice_no', $invoice_no)
                    ->set('pay_status',       1)
                    ->set('payment_by',       session()->get('username'))
                    ->set('payment_datetime', $now)
                    ->set('pay_jpy_amount',   'COALESCE(pay_jpy_amount, 0) + ' . $new_jpy, false)
                    ->set('pay_rate',         floatval($t['pay_rate'] ?? 0))
                    ->update();
            }

            return $this->response->setJSON(['status' => 200, 'message' => 'Payment completed successfully']);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->response->setJSON(['status' => 500, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    // route : base_url/accounts/service_provider_payment/report
    public function report()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $date1 = $this->request->getGet('date1');
        $date2 = $this->request->getGet('date2');

        $data['date1']     = $date1 ? date('Y-m-d', strtotime($date1)) : date('Y-m-d');
        $data['date2']     = $date2 ? date('Y-m-d', strtotime($date2)) : date('Y-m-d');
        $data['is_search'] = false;

        if ($date1 && $date2) {
            $data['is_search'] = true;

            $db      = \Config\Database::connect();
            $builder = $db->table('other_invoice_payment oip');
            $builder->select('
                oip.id,
                oip.invoice_no,
                oip.payment_date,
                oip.pay_jpy_amount,
                oip.pay_rate,
                oip.pay_lkr_amount,
                oip.ref,
                oip.created_by,
                oip.created_at,
                oil.date        as invoice_date,
                oil.description,
                ap.PName        as payment_method_name
            ');
            $builder->join('other_invoice_log oil', 'oil.invoice_no = oip.invoice_no', 'left');
            $builder->join('account_posting ap',    'ap.L_Code = oip.payment_method',  'left');
            $builder->where('oip.payment_date >=', $data['date1']);
            $builder->where('oip.payment_date <=', $data['date2']);
            $builder->orderBy('oip.payment_date', 'ASC');
            $builder->orderBy('oip.id', 'ASC');

            $records = $builder->get()->getResultArray();

            $data['records']   = $records;
            $data['total_jpy'] = array_sum(array_column($records, 'pay_jpy_amount'));
            $data['total_lkr'] = array_sum(array_column($records, 'pay_lkr_amount'));
        }

        return view('pages/accounts/service_provider_payment_report', $data);
    }
}
