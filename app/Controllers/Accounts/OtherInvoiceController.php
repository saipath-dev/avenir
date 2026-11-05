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
use App\Models\OtherInvoiceLogModel;

class OtherInvoiceController extends BaseController
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

        $inv_id = urldecode($this->request->getGet('inv_id'));


        $accountPostingModel = new AccountPostingModel();
        $data['methods'] = $accountPostingModel->where('B_Code', 5)->where('T_Code', 7)->where('H_Code', 7)->findAll();

        $data['inv_id'] = $inv_id;
        return view('pages/accounts/other_invoice', $data);
    }

    // route : base_url/accounts/debtor_invoice/complete_payment
    public function add_invoice()
    {
        try {
            $customer = '1-2-5-10635'; // LionTours Account
            $payment_method = $this->request->getPost('pay_type');
            $date = $this->request->getPost('date');
            $description = $this->request->getPost('description');
            $inv = $this->request->getPost('inv');

            // Get currency conversion fields
            $amount_jpy = $this->request->getPost('amount_jpy');
            $conversion_rate = $this->request->getPost('conversion_rate');
            $amount_lkr = $this->request->getPost('amount_lkr');
            $is_staff_invoice = $this->request->getPost('is_staff_invoice') ? 1 : 0;

            // Validate all currency fields are provided
            if (empty($amount_jpy) || empty($conversion_rate) || empty($amount_lkr)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'All currency fields are required'
                ]);
            }

            // Verify calculation accuracy (prevent manipulation)
            $calculated_lkr = floatval($amount_jpy) * floatval($conversion_rate);
            $submitted_lkr = floatval($amount_lkr);
            if (abs($calculated_lkr - $submitted_lkr) > 0.01) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Invalid LKR calculation. Please refresh and try again.'
                ]);
            }

            $new_inv_no = 'AH-OI-' . $inv;

            $OtherInvoiceLogModel = new OtherInvoiceLogModel();

            $data = ([
                'invoice_no' => $new_inv_no,
                'agent' => $customer,
                'income_type' => $payment_method,  // FIXED BUG: was $date, now correct
                'date' => $date,
                'description' => $description,
                'amount' => $amount_lkr,           // Legacy field - keep for compatibility
                'amount_jpy' => $amount_jpy,       // NEW
                'conversion_rate' => $conversion_rate, // NEW
                'amount_lkr' => $amount_lkr,       // NEW
                'is_staff_invoice' => $is_staff_invoice, // NEW
                'status' => 0,
                'created_by' => session()->get('username'),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $accountRunningModel = new AccountRunningModel();
            // Credit balance Record Insert
            // DR Recrord
            $accountRunningModel->insert([
                'ID' => $acc_id,
                'L_Code' => $customer, // Customer
                'Date' => $date,
                'Discription' => $new_inv_no . "- SP Invoice",
                'Dr' => $amount_lkr,  // Use LKR amount
                'Cr' => 0.00,
                'Invoice_Num' => $new_inv_no,
                'Linkacc' => $customer,
                'Cashier' => session()->get('username'),
                'Dt' => date('Y-m-d H:i:s')
            ]);

            // CR Record
            $accountRunningModel->insert([
                'ID' => $acc_id,
                'L_Code' => $payment_method, //Income
                'Date' => $date,
                'Discription' => $new_inv_no . "- SP Invoice",
                'Dr' => 0.00,
                'Cr' => $amount_lkr,  // Use LKR amount
                'Invoice_Num' => $new_inv_no,
                'Linkacc' => $customer,
                'Cashier' => session()->get('username'),
                'Dt' => date('Y-m-d H:i:s')
            ]);

            if ($OtherInvoiceLogModel->insert($data)) {
               
                return json_encode(['status' => 200, 'message' => 'Payment added successfully', 'inv' => $new_inv_no]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {

            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/accounts/other_invoice/report
    public function report()
    {
        $data = array();

        // Load menu data
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Get date parameters
        $date1 = $this->request->getGet('date1');
        $date2 = $this->request->getGet('date2');

        // Set default dates or use provided dates
        $data['date1'] = $date1 ? date('Y-m-d', strtotime($date1)) : date('Y-m-d');
        $data['date2'] = $date2 ? date('Y-m-d', strtotime($date2)) : date('Y-m-d');

        // Always show results (default to all invoices)
        $data['is_search'] = true;

        $otherInvoiceLogModel = new OtherInvoiceLogModel();

        // If dates are provided, filter by date range
        if (!empty($date1) && !empty($date2)) {
            $data['records'] = $otherInvoiceLogModel
                ->select('other_invoice_log.invoice_no, other_invoice_log.date, account_posting.PName as income_type_name, other_invoice_log.description, other_invoice_log.amount_lkr, other_invoice_log.amount_jpy, other_invoice_log.is_staff_invoice')
                ->join('account_posting', 'other_invoice_log.income_type = account_posting.L_Code', 'left')
                ->where('other_invoice_log.date >=', $date1)
                ->where('other_invoice_log.date <=', $date2)
                ->orderBy('other_invoice_log.date', 'DESC')
                ->orderBy('other_invoice_log.invoice_no', 'DESC')
                ->findAll();
        } else {
            // Show all invoices by default
            $data['records'] = $otherInvoiceLogModel
                ->select('other_invoice_log.invoice_no, other_invoice_log.date, account_posting.PName as income_type_name, other_invoice_log.description, other_invoice_log.amount_lkr, other_invoice_log.amount_jpy, other_invoice_log.is_staff_invoice')
                ->join('account_posting', 'other_invoice_log.income_type = account_posting.L_Code', 'left')
                ->orderBy('other_invoice_log.date', 'DESC')
                ->orderBy('other_invoice_log.invoice_no', 'DESC')
                ->findAll();
        }

        return view('pages/accounts/other_invoice_report', $data);
    }

    // route : base_url/accounts/other_invoice/print
    public function print()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $OtherInvoiceLogModel = new OtherInvoiceLogModel();

        $inv = $this->request->getGet('inv');

        $data['invoice_details'] = $OtherInvoiceLogModel->where('invoice_no', $inv)->first();
        $consigneeModel = new ConsigneeModel();

        return view('pages/accounts/other_invoice_print_new', $data);
    }

}
