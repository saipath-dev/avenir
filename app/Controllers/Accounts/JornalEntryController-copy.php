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

        // Initialize arrays
        $acc = [];

        // Get JE_inv from InvoiceNumberModel
        $model_inv = new InvoiceNumberModel();
        $JE_inv = $model_inv->incrementJInv();

        // Initialize success flag
        $success = true;
        foreach ($itemArray as $arr) {

            // Initialize $acc array for each iteration
            $acc = [];

            // Extract values from $arr
            $credit = $arr['credit'];
            $debit = $arr['debit'];
            $date = $arr['date'];
            $dv = $arr['dv'];
            $inv_no = $arr['inv_no'];
            $descrip = $arr['descrip'];
            $sup_inv = $arr['sup_inv'];
            $amount = $arr['amount'];

            // Generate unique ID for each transaction
            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            // Create debit and credit entries for the transaction
            $acc[] = [
                'ID' => $acc_id,
                'Invoice_Num' => $inv_no,
                'Cashier' =>strip_tags(session()->get('username')),
                'L_Code' => $debit,
                'Dr' => $amount,
                'Cr' => 0,
                'Date' => $date, // Assuming date format is YYYY-MM-DD
                'status' => '',
                'Chk_No' => '',
                'Linkacc' => '',
                'aflag' => 3,
                'job' => '',
                'JE_INV' => $JE_inv,
                'Discription' => 'JOURNAL ENTRY - ' . $descrip,
                'Sup_Invoice' => $sup_inv,
                'Dt' => date('Y-m-d H:i:s')
            ];

            $acc[] = [
                'ID' => $acc_id,
                'Invoice_Num' => $inv_no,
                'Cashier' =>strip_tags(session()->get('username')),
                'L_Code' => $credit,
                'Dr' => 0,
                'Cr' => $amount,
                'Date' => $date,
                'status' => '',
                'Chk_No' => '',
                'Linkacc' => '',
                'aflag' => 3,
                'job' => '',
                'JE_INV' => $JE_inv,
                'Discription' => 'JOURNAL ENTRY - ' . $descrip,
                'Sup_Invoice' => $sup_inv,
                'Dt' => date('Y-m-d H:i:s')
            ];

            // Insert batch into database
            $accrunningmodel = new AccountRunningModel();
            if (!$accrunningmodel->insertBatch($acc)) {
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


        $from = $this->request->getGet('from');
        $to = $this->request->getGet('to');

        $data['is_search'] = false;
        if ($from != null || $to != null) {

            $data['is_search'] = true;
            $data['from'] = $from;
            $data['to'] = $to;

            $accountRunningModel = new AccountRunningModel();


            $query = $accountRunningModel->select('*')
            ->like('Discription', 'JOURNAL ENTRY');



            if ($from != null) {
                $query->where('Date >=', $from);
            }


            if ($to != null) {
                $query->where('Date <=', $to);
            }

            $data['transactions'] = $query->findAll();

        }

        return view('pages/accounts/journal_entry_report', $data);
    }
}
