<?php

namespace App\Controllers\NewDashboard;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\ConsigneeModel;
use App\Models\SupplierModel;
use App\Models\DriverAllocationModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\PaymentRequestModel;
use App\Models\VoucherPaymentModel;
use App\Models\VoucherInvoiceModel;
use App\Models\InvoiceNumberModel;
use App\Models\TourActivityModel;
use App\Models\TourQuotationsModel;
use App\Models\DriverExpensesModel;



class DriverExpensesController extends BaseController
{

    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('tour_id');

        $data['tile_dashboard'] = true;

        $SupplierModel = new SupplierModel();
        $data['transport_agent'] = $SupplierModel->where('sup_cat', 2)->findAll();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $data['agents'] = [];

        $tourConsigneeModel = new ConsigneeModel();

        $tour_condition = $tourConsigneeModel->select('hotel_only')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $driverallocationModel = new DriverAllocationModel();
        $data['drivers'] = $driverallocationModel->where('tour_id', $tour_id)->findAll();

        $PaymentRequestModel = new PaymentRequestModel();
        $data['advance_payment_requests'] = $PaymentRequestModel->where('tour_id', $tour_id)->where('hotel_acc', 'TRANSPORT')->findAll();

        $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();
        $data['tour_id'] = $tour_id;
        // $data['type'] = $type;
        $data['tour_main_details'] = $tour_main_details;

        // Fetch activities for the tour
        $tourActivityModel = new TourActivityModel();
        $data['tour_activities'] = $tourActivityModel->select('tour_activity.*, entrance_fee.name as place_name')
            ->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'left')
            ->where('tour_activity.tour_id', $tour_id)
            ->orderBy('tour_activity.tour_date', 'ASC')
            ->findAll();

        // Fetch extra fees from tour_quotations
        $tourQuotationsModel = new TourQuotationsModel();
        try {
            $data['extra_fees'] = $tourQuotationsModel->select('tour_date, extra_act_fee, extra_hotel_fee')
                ->where('quotation_id', $tour_id)
                ->groupStart()
                    ->where('extra_act_fee >', 0)
                    ->orWhere('extra_hotel_fee >', 0)
                ->groupEnd()
                ->orderBy('tour_date', 'ASC')
                ->findAll();
        } catch (\Exception $e) {
            $data['extra_fees'] = [];
        }

        // Fetch pending/requested expenses from driver_expenses table
        $driverExpensesModel = new DriverExpensesModel();
        $data['pending_expenses'] = $driverExpensesModel->where('tour_id', $tour_id)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        // Whether adding a new expense is locked is a per-driver decision (see add_expenses()),
        // not tour-wide — a tour can be split across multiple drivers, and one driver's already-
        // sent request must not disable the form for another driver who hasn't submitted anything
        // yet. The form stays enabled here; add_expenses() rejects per-agent on submit.
        $data['is_request_sent'] = false;

        return view('pages/dashboard2/transport/transport_expenses', $data);
    }

    public function add_expenses()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $agent = $this->request->getPost('tagent');
            $agent_name = $this->request->getPost('agent_name');
            $category = $this->request->getPost('category');
            $supplier = $this->request->getPost('supplier');
            $supplier_name = $this->request->getPost('supplier_name');
            $inv = $this->request->getPost('inv');
            $date = $this->request->getPost('date');
            $dollerrate = $this->request->getPost('dollerrate');
            $amount = $this->request->getPost('amount');
            $rsamount = $this->request->getPost('rsamount');

            $ConsigneeModel = new ConsigneeModel();
            $consigneeData = $ConsigneeModel->select('tour_cancel')->where('tour_id', $tour)->first();

            if ($consigneeData && $consigneeData['tour_cancel'] == 1) {
                return json_encode(['status' => 400, 'message' => 'Cannot add payment. The tour has been cancelled.']);
            }

            // Check if THIS driver's request has already been sent and is still pending - lock
            // adding new expenses for them. Scoped per-agent, not tour-wide: a tour can be split
            // across multiple drivers, and one driver's finalized/sent request must not block
            // another driver who hasn't submitted anything yet.
            $driverExpensesModel = new DriverExpensesModel();
            $sentPendingCount = $driverExpensesModel->where('tour_id', $tour)
                ->where('agent', $agent)
                ->where('request_send', 1)
                ->where('status', 'pending')
                ->countAllResults();

            if ($sentPendingCount > 0) {
                return json_encode(['status' => 400, 'message' => 'Cannot add expenses. A request has already been sent for approval.']);
            }

            if ($supplier == null || $tour == null || $amount == null || $date == null || $inv == null || $rsamount == null || $agent == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($amount)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            // Determine description based on category
            if ($category == '6-9-23-10640') {
                $description = 'Entrance Fee Expenses';
            } else {
                $description = 'Other Expenses';
            }

            // Save to driver_expenses table with pending status
            $expenseData = [
                'tour_id' => $tour,
                'agent' => $agent,
                'agent_name' => $agent_name,
                'category' => $category,
                'supplier' => $supplier,
                'supplier_name' => $supplier_name,
                'invoice_no' => $inv,
                'expense_date' => $date,
                'dollar_rate' => $dollerrate,
                'dollar_amount' => $amount,
                'lkr_amount' => $rsamount,
                'description' => $description,
                'status' => 'pending',
                'created_by' => session()->get('username'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $insertResult = $driverExpensesModel->insert($expenseData);

            if ($insertResult) {
                return json_encode(['status' => 200, 'message' => 'Expense added successfully. Pending approval.']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Failed to add expense']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve driver expense and post to accounts
     */
    public function approve_expense()
    {
        try {
            $expense_id = $this->request->getPost('expense_id');

            if (empty($expense_id)) {
                return json_encode(['status' => 400, 'message' => 'Expense ID is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();
            $expense = $driverExpensesModel->find($expense_id);

            if (!$expense) {
                return json_encode(['status' => 400, 'message' => 'Expense not found']);
            }

            if ($expense['status'] !== 'pending') {
                return json_encode(['status' => 400, 'message' => 'Expense is already ' . $expense['status']]);
            }

            // Update transport agent if different
            $consigneeModel = new ConsigneeModel();
            $tourx = $consigneeModel->where('tour_id', $expense['tour_id'])->first();
            $current_t_agent = $tourx['transport_agent'];

            if ($current_t_agent != $expense['agent']) {
                $consigneeModel->where('tour_id', $expense['tour_id'])->set([
                    'transport_agent' => $expense['agent'],
                    'transport_agent_name' => $expense['agent_name']
                ])->update();
            }

            // Post to account_runing
            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $accountRunningModel = new AccountRunningModel();

            $accountRunningData = [
                [
                    'ID' => $acc_id,
                    'L_Code' => $expense['supplier'],
                    'Date' => $expense['expense_date'],
                    'Discription' => $expense['description'],
                    'Dr' => $expense['lkr_amount'],
                    'Cr' => 0.00,
                    'Invoice_Num' => $expense['invoice_no'],
                    'Linkacc' => $expense['supplier'],
                    'index_no' => $expense['tour_id'],
                    'dollarrate' => $expense['dollar_rate'],
                    'dollar' => $expense['dollar_amount'],
                    'job' => $expense['category'],
                    'advances_pay' => 111,
                    'fine_date' => date('Y-m-d'),
                    'Cashier' => session()->get('username'),
                    'Dt' => date('Y-m-d H:i:s')
                ],
                [
                    'ID' => $acc_id,
                    'L_Code' => $expense['agent'],
                    'Date' => $expense['expense_date'],
                    'Discription' => $expense['description'],
                    'Dr' => 0.00,
                    'Cr' => $expense['lkr_amount'],
                    'Invoice_Num' => $expense['invoice_no'],
                    'Linkacc' => $expense['supplier'],
                    'index_no' => $expense['tour_id'],
                    'dollarrate' => $expense['dollar_rate'],
                    'dollar' => $expense['dollar_amount'],
                    'job' => $expense['category'],
                    'advances_pay' => 111,
                    'fine_date' => date('Y-m-d'),
                    'Cashier' => session()->get('username'),
                    'Dt' => date('Y-m-d H:i:s')
                ]
            ];

            $insertResult = $accountRunningModel->insertBatch($accountRunningData);

            if ($insertResult) {
                // Update expense status to approved
                $driverExpensesModel->update($expense_id, [
                    'status' => 'approved',
                    'approved_by' => session()->get('username'),
                    'approved_at' => date('Y-m-d H:i:s'),
                    'acc_run_id' => $acc_id
                ]);

                return json_encode(['status' => 200, 'message' => 'Expense approved and posted to accounts successfully']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Failed to post expense to accounts']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject driver expense
     */
    public function reject_expense()
    {
        try {
            $expense_id = $this->request->getPost('expense_id');
            $reason = $this->request->getPost('reason');

            if (empty($expense_id)) {
                return json_encode(['status' => 400, 'message' => 'Expense ID is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();
            $expense = $driverExpensesModel->find($expense_id);

            if (!$expense) {
                return json_encode(['status' => 400, 'message' => 'Expense not found']);
            }

            if ($expense['status'] !== 'pending') {
                return json_encode(['status' => 400, 'message' => 'Expense is already ' . $expense['status']]);
            }

            // Update expense status to rejected
            $driverExpensesModel->update($expense_id, [
                'status' => 'rejected',
                'rejected_by' => session()->get('username'),
                'rejected_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason
            ]);

            return json_encode(['status' => 200, 'message' => 'Expense rejected successfully']);
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Get pending expenses for approval page
     */
    public function get_pending_expenses()
    {
        try {
            $driverExpensesModel = new DriverExpensesModel();
            $expenses = $driverExpensesModel->getPendingExpenses();

            return json_encode(['status' => 200, 'data' => $expenses]);
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Get pending expenses for a specific tour (AJAX)
     */
    public function get_tour_pending_expenses()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');

            if (empty($tour_id)) {
                return json_encode(['status' => 400, 'message' => 'Tour ID is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();
            $expenses = $driverExpensesModel->where('tour_id', $tour_id)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            return json_encode(['status' => 200, 'data' => $expenses]);
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete pending or rejected expense
     */
    public function delete_pending_expense()
    {
        try {
            $expense_id = $this->request->getPost('expense_id');

            if (empty($expense_id)) {
                return json_encode(['status' => 400, 'message' => 'Expense ID is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();
            $expense = $driverExpensesModel->find($expense_id);

            if (!$expense) {
                return json_encode(['status' => 400, 'message' => 'Expense not found']);
            }

            // Only allow deletion of pending or rejected expenses (not approved)
            if ($expense['status'] === 'approved') {
                return json_encode(['status' => 400, 'message' => 'Cannot delete. Expense has already been approved.']);
            }

            // Don't allow deletion if request has been sent and is still pending
            if ($expense['request_send'] == 1 && $expense['status'] === 'pending') {
                return json_encode(['status' => 400, 'message' => 'Cannot delete. Request has already been sent for approval.']);
            }

            $deleteResult = $driverExpensesModel->delete($expense_id);

            if ($deleteResult) {
                return json_encode(['status' => 200, 'message' => 'Expense deleted successfully']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Failed to delete expense']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Back-office delete for an expense already sent for approval (request_send=1,
     * status=pending) — unlike delete_pending_expense(), this does NOT block on request_send,
     * since pending_expenses_dashboard.php is itself the back-office review screen for exactly
     * these rows. Still blocked once approved, matching the existing rule everywhere else.
     */
    public function admin_delete_expense()
    {
        try {
            $expense_id = $this->request->getPost('expense_id');

            if (empty($expense_id)) {
                return json_encode(['status' => 400, 'message' => 'Expense ID is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();
            $expense = $driverExpensesModel->find($expense_id);

            if (!$expense) {
                return json_encode(['status' => 400, 'message' => 'Expense not found']);
            }

            if ($expense['status'] === 'approved') {
                return json_encode(['status' => 400, 'message' => 'Cannot delete. Expense has already been approved.']);
            }

            $deleteResult = $driverExpensesModel->delete($expense_id);

            if ($deleteResult) {
                return json_encode(['status' => 200, 'message' => 'Expense deleted successfully']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Failed to delete expense']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Back-office edit for an expense already sent for approval — same scope as
     * admin_delete_expense() above. Only invoice_no, description, and lkr_amount are editable;
     * category/supplier/date are left untouched to avoid re-triggering account-posting logic
     * that assumes those fields matched at send time.
     */
    public function admin_update_expense()
    {
        try {
            $expense_id = $this->request->getPost('expense_id');
            $invoice_no = trim((string) $this->request->getPost('invoice_no'));
            $description = trim((string) $this->request->getPost('description'));
            $lkr_amount = trim((string) $this->request->getPost('lkr_amount'));
            $category = trim((string) $this->request->getPost('category'));
            $supplier = trim((string) $this->request->getPost('supplier'));

            if (empty($expense_id) || $invoice_no === '' || $lkr_amount === '') {
                return json_encode(['status' => 400, 'message' => 'Invoice No and LKR Amount are required']);
            }

            if (!is_numeric($lkr_amount) || (float) $lkr_amount < 0) {
                return json_encode(['status' => 400, 'message' => 'LKR Amount must be a non-negative number']);
            }

            $driverExpensesModel = new DriverExpensesModel();
            $expense = $driverExpensesModel->find($expense_id);

            if (!$expense) {
                return json_encode(['status' => 400, 'message' => 'Expense not found']);
            }

            if ($expense['status'] === 'approved') {
                return json_encode(['status' => 400, 'message' => 'Cannot edit. Expense has already been approved.']);
            }

            $updateData = [
                'invoice_no' => $invoice_no,
                'description' => $description,
                'lkr_amount' => round((float) $lkr_amount, 2),
            ];

            // Category/Supplier are optional on edit — the row already has valid values from when
            // it was created, so only touch them if the admin actually changed the dropdowns.
            // Supplier name is always re-looked-up server-side, same as add_expenses()/
            // adminSaveExpense() — never trust a client-supplied display name for the account.
            if ($category !== '' && $supplier !== '') {
                $expenseCategories = ['6-9-23-10640' => '1', '6-9-23-10641' => '2'];
                if (!isset($expenseCategories[$category])) {
                    return json_encode(['status' => 400, 'message' => 'Invalid expense category']);
                }

                $account = (new AccountPostingModel())
                    ->select('L_Code, PName')
                    ->where('B_Code', 4)
                    ->where('T_Code', 8)
                    ->where('H_Code', 109)
                    ->where('type', $expenseCategories[$category])
                    ->where('L_Code', $supplier)
                    ->first();
                if (!$account) {
                    return json_encode(['status' => 400, 'message' => 'Invalid expense account']);
                }

                $updateData['category'] = $category;
                $updateData['supplier'] = $supplier;
                $updateData['supplier_name'] = $account['PName'] ?? $supplier;
            }

            $updateResult = $driverExpensesModel->update($expense_id, $updateData);

            if ($updateResult) {
                return json_encode(['status' => 200, 'message' => 'Expense updated successfully']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Failed to update expense']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Send request for pending expense approval
     */
    public function send_request()
    {
        try {
            $expense_id = $this->request->getPost('expense_id');

            if (empty($expense_id)) {
                return json_encode(['status' => 400, 'message' => 'Expense ID is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();
            $expense = $driverExpensesModel->find($expense_id);

            if (!$expense) {
                return json_encode(['status' => 400, 'message' => 'Expense not found']);
            }

            // Only allow sending request for pending expenses
            if ($expense['status'] !== 'pending') {
                return json_encode(['status' => 400, 'message' => 'Cannot send request. Expense is already ' . $expense['status']]);
            }

            // Check if request already sent
            if ($expense['request_send'] == 1) {
                return json_encode(['status' => 400, 'message' => 'Request already sent']);
            }

            // Update request_send fields
            $updateResult = $driverExpensesModel->update($expense_id, [
                'request_send' => 1,
                'request_send_by' => session()->get('username'),
                'request_send_at' => date('Y-m-d H:i:s')
            ]);

            if ($updateResult) {
                return json_encode(['status' => 200, 'message' => 'Request sent successfully']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Failed to send request']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Send all pending expense requests for a tour
     */
    public function send_all_requests()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');

            if (empty($tour_id)) {
                return json_encode(['status' => 400, 'message' => 'Tour ID is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();

            // Get all pending expenses that haven't been sent yet
            $pendingExpenses = $driverExpensesModel
                ->where('tour_id', $tour_id)
                ->where('status', 'pending')
                ->where('request_send', 0)
                ->findAll();

            if (empty($pendingExpenses)) {
                return json_encode(['status' => 400, 'message' => 'No pending expenses to send']);
            }

            // Update all pending expenses
            $updateCount = 0;
            foreach ($pendingExpenses as $expense) {
                $result = $driverExpensesModel->update($expense['id'], [
                    'request_send' => 1,
                    'request_send_by' => session()->get('username'),
                    'request_send_at' => date('Y-m-d H:i:s')
                ]);
                if ($result) {
                    $updateCount++;
                }
            }

            if ($updateCount > 0) {
                return json_encode(['status' => 200, 'message' => $updateCount . ' request(s) sent successfully']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Failed to send requests']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function get_tour_expenses()
    {
        $tour = $this->request->getPost('tour_id');
        $agent = $this->request->getPost('agent');

        $accountRunningModel = new AccountRunningModel();
        $consigneeModel = new ConsigneeModel();

        if ($tour == null) {
            return json_encode(['status' => 400, 'message' => 'Please provide tour ID']);
        }

        if ($accountRunningModel->where('index_no', $tour)->countAllResults() > 0) {
            // Prepare the query
            $accountRunningModel->where('index_no', $tour);
        } else {
            $tr = $consigneeModel->select('tour_id')->where('manual_id', $tour)->first();
            // Use the retrieved tour_id in the query
            if ($tr) {
                $accountRunningModel->where('index_no', $tr['tour_id']);
            } else {
                return json_encode(['status' => 400, 'message' => 'No records found']);
            }
        }

        $details = $accountRunningModel->select('account_runing.*, consignee.consignee, consignee.agent_name, consignee.tour_id, supplier.Name, entrance_fee.name as sup_name, consignee.agent, consignee.bank, consignee.bank_name')
            ->join('consignee', 'consignee.tour_id = account_runing.index_no')
            ->join('supplier', 'supplier.Sup_Acc_No = account_runing.L_Code', 'left')
            ->join('entrance_fee', 'entrance_fee.l_code = account_runing.Linkacc', 'left')
            ->join('supplier as supplier_1', 'supplier_1.Sup_Acc_No = account_runing.Linkacc', 'left')
            ->where('account_runing.index_no', $tour)
            ->where('account_runing.L_Code', $agent)
            ->where('account_runing.Cr >', 0.00)
            ->notLike('account_runing.Discription', 'VOUCHER PAYMENT%')
            ->groupStart()
            ->orWhere('account_runing.advances_pay', 111)
            ->groupEnd()
            ->findAll();

        return json_encode(['status' => 200, 'data' => $details]);
    }

    /**
     * Approve all pending expenses for a tour (only updates DriverExpensesModel)
     */
    public function approve_tour_expenses()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');

            if (empty($tour_id)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour ID is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();

            $expenses = $driverExpensesModel
                ->where('tour_id', $tour_id)
                ->where('request_send', 1)
                ->where('status', 'pending')
                ->findAll();

            if (empty($expenses)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'No pending expenses found for this tour']);
            }

            $approvedCount = 0;

            foreach ($expenses as $expense) {
                // Update expense status to approved
                $driverExpensesModel->update($expense['id'], [
                    'status' => 'approved',
                    'approved_by' => session()->get('username'),
                    'approved_at' => date('Y-m-d H:i:s')
                ]);
                $approvedCount++;
            }

            return $this->response->setJSON(['status' => 200, 'message' => $approvedCount . ' expense(s) approved successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject all pending expenses for a tour
     */
    public function reject_tour_expenses()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');
            $reason = $this->request->getPost('reason');

            if (empty($tour_id)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour ID is required']);
            }

            if (empty($reason)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Rejection reason is required']);
            }

            $driverExpensesModel = new DriverExpensesModel();
            $expenses = $driverExpensesModel
                ->where('tour_id', $tour_id)
                ->where('request_send', 1)
                ->where('status', 'pending')
                ->findAll();

            if (empty($expenses)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'No pending expenses found for this tour']);
            }

            $rejectedCount = 0;

            foreach ($expenses as $expense) {
                $driverExpensesModel->update($expense['id'], [
                    'status' => 'rejected',
                    'rejected_by' => session()->get('username'),
                    'rejected_at' => date('Y-m-d H:i:s'),
                    'rejection_reason' => $reason
                ]);
                $rejectedCount++;
            }

            return $this->response->setJSON(['status' => 200, 'message' => $rejectedCount . ' expense(s) rejected successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function delete_expenses()
    {
        $id = $this->request->getPost('id');
        $tour_id = $this->request->getPost('tour_id');
        $is_settled = $this->request->getPost('is_settled');

        $accountRunningModel = new AccountRunningModel();

        if ($is_settled == 1) {

            $record_id = $accountRunningModel->select('ID')->where('advances_pay', 11)->where('index_no', $tour_id)->where('Discription LIKE', 'Transport Payment Settled%')->first();
            log_message('critical', 'Record ID: ' . $accountRunningModel->getLastQuery());
            $accrun_id = $record_id['ID'];

            $voucherpaymentmodel  = new VoucherPaymentModel();
            $voucherpayment = $voucherpaymentmodel->where('ref_po', $tour_id)->where('advances_pay', 11)->where('acc_run_id', $accrun_id)->findAll();
            $aflag = $voucherpayment[0]['Aflag'];

            if ($aflag == 1) {
                return json_encode(['status' => 400, 'message' => 'You can not delete this record because the payment has been settled']);
            }
        }

        if ($id == null) {
            return json_encode(['status' => 400, 'message' => 'Please provide an ID']);
        }

        $deleteResult = $accountRunningModel->delete($id);

        if ($deleteResult) {
            return json_encode(['status' => 200, 'message' => 'Record deleted successfully']);
        } else {
            return json_encode(['status' => 400, 'message' => 'Record deletion failed']);
        }
    }
}
