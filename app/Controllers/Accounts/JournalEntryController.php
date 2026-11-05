<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
// use App\Models\BankReconcilModel;
use App\Models\AccountPostingModel;
use App\Models\UserModel;
use App\Models\JournalEntryModel;
use App\Models\InvoiceNumberModel;
use App\Models\AccountRunningModel;

class JournalEntryController extends BaseController
{
    protected $BankReconcilModel;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/accounts/balance_sheet
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();


        $accountposting = new AccountPostingModel();
        $data['load_bank'] =  $accountposting->select('PName,L_Code')
            ->where('B_Code', '1')
            ->where('T_Code', '6')
            ->findAll();

        return view('pages/accounts/journal_entry', $data);
    }

    public function fetch($segment4)
    {
        //$segment4 = $this->request->uri->getSegment(4); // Assuming this is an ID or parameter
        // $segment5 = $this->request->uri->getSegment(5); // Assuming this is another ID or parameter

        $journalentrymodel = new JournalEntryModel();

        // Assuming your model method returns data
        $data = $journalentrymodel->fetch_data($segment4);
        // log_message('critical', $this->journalentrymodel->getLastQuery());

        // Convert data to JSON and output it
        echo json_encode($data);
    }



    /******************** GET DATASET ********************/
    function save()
    {
        // Retrieve itemArray from request
        $itemArray = $this->request->getVar('itemArray');

        // Ensure $itemArray is an array and remove duplicate entries
        $itemArray = is_array($itemArray) ? array_unique($itemArray, SORT_REGULAR) : [];

        // Get JE_inv from InvoiceNumberModel
        $model_inv = new InvoiceNumberModel();
        $JE_inv = $model_inv->incrementJInv();

        $invoiceNoModel = new InvoiceNumberModel();
        $acc_id = $invoiceNoModel->incrementAccId();

        // Initialize success flag
        $success = true;

        foreach ($itemArray as $arr) {
            // Sanitize input values with strip_tags
            $debit = strip_tags($arr['debit']);
            $date = strip_tags($arr['date']);
            $dv = strip_tags($arr['dv']);
            $inv_no = strip_tags($arr['inv_no']);
            $descrip = strip_tags($arr['descrip']);
            $sup_inv = strip_tags($arr['sup_inv']);
            $amount = strip_tags($arr['amount']);
            $transaction_type = strip_tags($arr['transaction_type']);

            // Create debit and credit entries for the transaction
            $dr = 0;
            $cr = 0;

            // Set values based on transaction type
            if ($transaction_type === 'dr') {
                $dr = $amount;
            } elseif ($transaction_type === 'cr') {
                $cr = $amount;
            }

            // Create entries for the transaction
            $acc = [
                'ID' => $acc_id,
                'Invoice_Num' => $inv_no,
                'Cashier' => strip_tags(session()->get('username')),
                'L_Code' => $debit,
                'Dr' => $dr,
                'Cr' => $cr,
                'Date' => $date,
                'status' => '',
                'Chk_No' => '',
                'Linkacc' => '',
                'aflag' => 3,
                'job' => '',
                'JE_INV' => $JE_inv,
                'Discription' => 'JOURNAL ENTRY - ' . $descrip,
                'Sup_Invoice' => $sup_inv,
                'Dt' => date('Y-m-d H:i:s'),
            ];

            // Insert batch into database
            $accrunningmodel = new AccountRunningModel();
            if (!$accrunningmodel->insert($acc)) {
                $success = false;
                break; // Exit loop on first failure
            }
        }

        // Check overall success
        if ($success) {
            // Return success response
            return $this->response->setJSON(["status" => 200, "message" => "Invoice saved successfully!"]);
        } else {
            // Return error response
            return $this->response->setJSON(["status" => 400, "message" => "Insert Error!"]);
        }
    }




    public function report()
    {

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();


        $date1 = $this->request->getGet('from');
        $date2 = $this->request->getGet('to');
        $id = $this->request->getGet('id');
        $data['date1'] = $date1;
        $data['date2'] = $date2;
        $data['id'] = $id;

        $data['is_search'] = false;
        $data['in_detail'] = false;
        $accountRunningModel = new AccountRunningModel();

        if (!empty($date1) && !empty($date2)) {

            $data['is_search'] = true;
            $data['in_detail'] = false;

            if (!empty($id)) {

                $data['in_detail'] = true;



                $query = $accountRunningModel->select('account_runing.*,account_posting.PName')
                    ->like('Discription', 'JOURNAL ENTRY')
                    ->where('account_runing.JE_INV', $id)
                    ->join('account_posting', 'account_runing.L_Code = account_posting.L_Code');
                $query->where('Date >=', $date1);
                $query->where('Date <=', $date2);
                $data['transactions'] = $query->findAll();
            } else {


                $data['is_search'] = true;
                $data['from'] = $date1;
                $data['to'] = $date2;

                $query = $accountRunningModel->select('SUM(Dr) as Dr,SUM(Cr) as Cr,MAX(Cashier) as Cashier,MAX(Discription) as Discription,MAX(Date) as Date, JE_INV,MAX(InvCancel) as InvCancel')
                    ->like('Discription', 'JOURNAL ENTRY');

                $query->where('Date >=', $date1);

                $query->where('Date <=', $date2);

                $query->groupBy('JE_INV');
                $query->orderBy('Date');
                // $query->groupBy('L_Code');

                $data['transactions'] = $query->findAll();
            }
        }

        return view('pages/accounts/journal_entry_report', $data);
    }



    public function reverse()
    {
        $id = strip_tags($this->request->getVar('id'));
        $date = strip_tags($this->request->getVar('transactionDate'));

        // Load models
        // $vouchermodel = new VoucherPaymentModel();
        // $voucherInvoiceModel = new VoucherInvoiceModel();
        $accountRunningModel = new AccountRunningModel();
        // $accountRunningDelModel = new AccountRunningDelModel();


        $data = [
            'InvCancel' => 1,
        ];

        // Perform the update
        $updated = $accountRunningModel->where('JE_INV', $id)->set($data)->update();

        $accountRunningEntries = $accountRunningModel->where('JE_INV', $id)->findAll();

        $invoiceNoModel = new InvoiceNumberModel();
        $acc_id = $invoiceNoModel->incrementAccId();
        $accountRunningEntries = $accountRunningModel->where('JE_INV', $id)->findAll();

        $JE_inv = $invoiceNoModel->incrementJInv();

        foreach ($accountRunningEntries as $entry) {

            // Insert debit entry if credit amount is greater than 0
            if ($entry['Cr'] > 0) {
                $drEntry = [
                    'ID' => $acc_id,
                    'Invoice_Num' => $entry['Invoice_Num'],
                    'Cashier' => strip_tags(session()->get('username')),
                    'L_Code' => $entry['L_Code'], // Use the same L_Code
                    'Dr' => $entry['Cr'], // Set Dr to the Cr amount
                    'Cr' => 0, // Cr should be 0 for the debit entry
                    'Date' => $date,
                    'P_Month' => date('Y-m-d'),
                    'status' => '',
                    'Chk_No' => '',
                    'Linkacc' => '',
                    'aflag' => 3,
                    'Dt' => date('Y-m-d H:i:s'),
                    'job' => '',
                    'JE_INV' => $JE_inv,
                    'Discription' => 'REVERSE JOURNAL ENTRY',
                    'Sup_Invoice' => $entry['Sup_Invoice'],
                    'InvCancel' => 1
                ];

                // Insert the debit entry
                $accountRunningModel->insert($drEntry);
            }

            // Insert credit entry if debit amount is greater than 0
            if ($entry['Dr'] > 0) {
                $crEntry = [
                    'ID' => $acc_id,
                    'Invoice_Num' => $entry['Invoice_Num'],
                    'Cashier' => strip_tags(session()->get('username')),
                    'L_Code' => $entry['L_Code'], // Use the same L_Code
                    'Dr' => 0, // Dr should be 0 for the credit entry
                    'Cr' => $entry['Dr'], // Set Cr to the Dr amount
                    'Date' => $date,
                    'status' => '',
                    'Chk_No' => '',
                    'Linkacc' => '',
                    'P_Month' => date('Y-m-d'),
                    'Dt' => date('Y-m-d H:i:s'),
                    'aflag' => 3,
                    'job' => '',
                    'JE_INV' => $JE_inv,
                    'Discription' => 'REVERSE JOURNAL ENTRY',
                    'Sup_Invoice' => $entry['Sup_Invoice'],
                    'InvCancel' => 1
                ];

                // Insert the credit entry
                $accountRunningModel->insert($crEntry);
            }
        }


        // Complete the transaction
        // $db->transComplete();

        // if ($db->transStatus() === FALSE) {
        //          // Transaction failed
        //  return $this->response->setJSON(["status" => 400, "message" => "Transaction failed!"]);
        // } else {
        // Transaction successful
        return $this->response->setJSON(["status" => 200, "message" => "Voucher and related records processed successfully!"]);
        // }

    }


    public function edit_date()
    {
        // Sanitize and validate input
        $id = strip_tags($this->request->getVar('id'));
        $date = strip_tags($this->request->getVar('transactionDate'));

        if (empty($id) || empty($date)) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'ID and Transaction Date are required.'
            ]);
        }

        // Load models
        $accountRunningModel = new AccountRunningModel();

        // Check if the record exists
        $existingRecord = $accountRunningModel->where('JE_INV', $id)->first();
        if (!$existingRecord) {
            return $this->response->setJSON([
                'status' => 404,
                'message' => 'Record not found for the provided ID.'
            ]);
        }

        // Prepare data to update
        $data = [
            'date_changed' => 1,
            'Date' => $date,
            'date_changed_user' => strip_tags(session()->get('username'))
        ];

        // Perform the update
        $updated = $accountRunningModel->where('JE_INV', $id)->set($data)->update();

        // Check if the update was successful
        if ($updated === false) {
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Failed to update the record. Please try again later.'
            ]);
        }


        // Return success response
        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Voucher and related records processed successfully!'
        ]);
    }



    public function edit_report()
    {

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();


        $date1 = $this->request->getGet('from');
        $date2 = $this->request->getGet('to');
        $id = $this->request->getGet('id');
        $data['date1'] = $date1;
        $data['date2'] = $date2;
        $data['id'] = $id;

        $data['is_search'] = false;
        $data['in_detail'] = false;
        $accountRunningModel = new AccountRunningModel();

        if (!empty($date1) && !empty($date2)) {

            $data['is_search'] = true;
            $data['in_detail'] = false;

            if (!empty($id)) {

                $data['in_detail'] = true;



                $query = $accountRunningModel->select('account_runing.*,account_posting.PName')
                    ->like('Discription', 'JOURNAL ENTRY')
                    ->where('account_runing.JE_INV', $id)
                    ->where('account_runing.InvCancel', 0)
                    ->join('account_posting', 'account_runing.L_Code = account_posting.L_Code');
                $query->where('Date >=', $date1);
                $query->where('Date <=', $date2);
                $data['transactions'] = $query->findAll();
            } else {


                $data['is_search'] = true;
                $data['from'] = $date1;
                $data['to'] = $date2;

                $query = $accountRunningModel->select('SUM(Dr) as Dr,SUM(Cr) as Cr,MAX(Cashier) as Cashier,MAX(Discription) as Discription,MAX(Date) as Date, JE_INV,MAX(InvCancel) as InvCancel')
                    ->like('Discription', 'JOURNAL ENTRY')->where('account_runing.InvCancel', 0);

                $query->where('Date >=', $date1);

                $query->where('Date <=', $date2);

                $query->groupBy('JE_INV');
                $query->orderBy('Date');
                // $query->groupBy('L_Code');

                $data['transactions'] = $query->findAll();
            }
        }

        return view('pages/accounts/journal_entry_edit', $data);
    }
}
