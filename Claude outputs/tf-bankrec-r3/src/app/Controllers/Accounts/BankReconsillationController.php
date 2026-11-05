<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
// use App\Models\BankReconcilModel;
use App\Models\AccountPostingModel;
use App\Models\UserModel;
use App\Models\InvoiceNumberModel;
use App\Models\VoucherInvoiceModel;
use App\Models\AccountRunningModel;
use App\Models\BankReconcilModel;
use App\Libraries\BankRec\LegacyGuard; // tf-bankrec-r3 (D7)

class BankReconsillationController extends BaseController
{
    protected $BankReconcilModel;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    /** tf-bankrec-r3 (D7): the answer when the guard refuses a change in a period the Daily Bank Reconciliation covers. */
    private function legacyRefused(array $r)
    {
        return $this->response->setStatusCode($r[0])->setJSON(['status' => $r[0], 'message' => $r[1], 'protected_period' => true]);
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



        return view('pages/accounts/bank_reconcillation', $data);
    }


    public function get_req_items()
    {
        // $date1 = strip_tags($this->request->getVar('date1'));
        $date2 = strip_tags($this->request->getVar('date2'));
        $bank = strip_tags($this->request->getVar('bank'));
        $menu = strip_tags($this->request->getVar('menu'));
        $flag = strip_tags($this->request->getVar('flag'));


        // $date1 = substr($daterange, 0, 10);
        // $date2 = substr($daterange, 13, 23);
        $BankReconcilModel = new BankReconcilModel();
        $get_dataset = $BankReconcilModel->load_bank_details($date2, $bank, $flag);
        $data['result'] = $get_dataset;

        echo json_encode($data);
    }


    public function get_opening_balance()
    {
        // $date1 = strip_tags($this->request->getVar('date1'));
        $date2 = strip_tags($this->request->getVar('date2'));
        $bank = strip_tags($this->request->getVar('bank'));

        $BankReconcilModel = new BankReconcilModel();
        $result = $BankReconcilModel->load_opening_balance($date2, $bank);

        // Prepare the response data with only the opening balance
        $data = [
            'opening_balance' => $result->bal ?? 0 // Default to 0 if no result
        ];

        return $this->response->setJSON($data);
    }


    public function get_realised_sum()
    {
        // $date1 = strip_tags($this->request->getVar('date1'));
        $date2 = strip_tags($this->request->getVar('date2'));
        $bank = strip_tags($this->request->getVar('bank'));
        $menu = strip_tags($this->request->getVar('menu'));
        $flag = strip_tags($this->request->getVar('flag'));

        // $date1 = substr($daterange, 0, 10);
        // $date2 = substr($daterange, 17, 10);

        $BankReconcilModel = new BankReconcilModel();
        $get_dataset = $BankReconcilModel->get_realised_sum($date2, $bank, $flag);
        $data['result'] = $get_dataset;

        echo json_encode($data);
    }



    /******************** UPDATE ***********************/
    public function update()
    {

        $id = strip_tags($this->request->getVar('id'));
        $flag = strip_tags($this->request->getVar('flag'));
        $bank = strip_tags($this->request->getVar('bank')); // chethana
        if ($r = LegacyGuard::checkRows(\Config\Database::connect(), [$id], 'update')) { // tf-bankrec-r3 (D7)
            return $this->legacyRefused($r);
        }


        // chethana
        //  if($flag==0){
        //   $bnkst_flg=1;
        // }else{
        //   $bnkst_flg=0;
        // }  

        if ($bank == 0) {
            $bank_rec = 1;
        } else {
            $bank_rec = 0;
        }

        $accRunningmodel = new AccountRunningModel();


        // $data = [
        //   'bankst_flag' => $bnkst_flg //chethana
        // ];

        $data = [
            'bank_rec' => $bank_rec  //chethana
        ];
        $where = ['ID' => $id];


        if ($accRunningmodel->update($where, $data)) {
            return $this->response->setJSON(["status" => 200, "message" => "Invoice saved successfully!"]);
        } else {
            return $this->response->setJSON(["status" => 400, "message" => "Insert Error!"]);
        }
    }




    /******************** UPDATE ***********************/
    public function finalize_selected()
    {
        $records = $this->request->getVar('records'); // Expecting an array of ['id' => ..., 'flag' => ..., 'bank' => ...]
        if (is_array($records) && ($r = LegacyGuard::checkRows(\Config\Database::connect(), array_map(static fn ($x) => is_array($x) ? ($x['id'] ?? 0) : ($x->id ?? 0), $records), 'finalize_selected'))) { // tf-bankrec-r3 (D7)
            return $this->legacyRefused($r);
        }
        $accRunningmodel = new AccountRunningModel();
        $errors = [];
        $successCount = 0;

        if (is_array($records)) {
            foreach ($records as $record) {
                $id = isset($record['id']) ? strip_tags($record['id']) : null;
                $flag = isset($record['flag']) ? strip_tags($record['flag']) : null;
                $bank = isset($record['bank']) ? strip_tags($record['bank']) : null;

                if ($id === null || $bank === null) {
                    $errors[] = ['id' => $id, 'error' => 'Missing id or bank'];
                    continue;
                }

                // Determine bank_rec value
                // $bank_rec = ($bank == 0) ? 1 : 0;

                $data = [
                    'bank_rec' => 1
                ];
                $where = ['ID' => $id];

                if ($accRunningmodel->update($where, $data)) {
                    $successCount++;
                } else {
                    $errors[] = ['id' => $id, 'error' => 'Update failed'];
                }
            }

            if (empty($errors)) {
                return $this->response->setJSON(["status" => 200, "message" => "All records updated successfully!", "updated" => $successCount]);
            } else {
                return $this->response->setJSON([
                    "status" => 207,
                    "message" => "Some records failed to update.",
                    "updated" => $successCount,
                    "errors" => $errors
                ]);
            }
        } else {
            return $this->response->setJSON(["status" => 400, "message" => "Invalid records data!"]);
        }
    }

    // reconciliation
    public function reconciliation()
    {

        $date2 = strip_tags($this->request->getVar('date2'));
        $bank = strip_tags($this->request->getVar('bank'));
        if ($r = LegacyGuard::checkRange(\Config\Database::connect(), (string) $bank, null, (string) $date2, 'reconciliation')) { // tf-bankrec-r3 (D7)
            return $this->legacyRefused($r);
        }

        $accRunningmodel = new AccountRunningModel();

        $result = $accRunningmodel->select('ID')->where('L_Code', $bank)->where('Date <=', $date2)->where('bank_rec', 1)->findAll();

        $data = [
            'bankst_flag' => 1
        ];

        $error = [];

        // Update the records
        foreach ($result as $row) {
            $accRunningmodel->update($row['ID'], $data);

            if (!$accRunningmodel->update($row['ID'], $data)) {
                $error[] = $row['ID'];
            }
        }

        if (empty($error)) {
            return $this->response->setJSON(["status" => 200, "message" => "Reconciliation successful!"]);
        } else {
            return $this->response->setJSON(["status" => 400, "message" => "Reconciliation failed!", "errors" => $error]);
        }
    }

    /******************** post_unrealised_entries ***********************/
    public function post_unrealised_entries()
    {
        $itemArray = $this->request->getVar('itemArray');
        $depositArray = $this->request->getVar('depositArray');

        // Ensure the arrays are valid arrays and unique
        $itemArray = is_array($itemArray) ? array_unique($itemArray, SORT_REGULAR) : [];
        $depositArray = is_array($depositArray) ? array_unique($depositArray, SORT_REGULAR) : [];
        $ids = array_map(static fn ($x) => is_array($x) ? ($x['ID'] ?? 0) : ($x->ID ?? 0), array_merge($itemArray, $depositArray));
        if ($r = LegacyGuard::checkRows(\Config\Database::connect(), $ids, 'post_unrealised_entries')) { // tf-bankrec-r3 (D7)
            return $this->legacyRefused($r);
        }

        $output = "error"; // Default to error
        $errorMessages = []; // Array to store error messages
        $accRunningmodel = new AccountRunningModel();

        // Process items
        foreach ($itemArray as $arr) {
            $lcode = $arr['lcode'];
            $id = $arr['ID'];


            $data = [
                'New_L_Code' => $lcode,
                'L_Code' => '1-6-112-10782',
            ];

            $output1 = $accRunningmodel->where('ID', $id)->where('L_Code', $lcode)->where('bankst_flag', 0)->set($data)->update();

            if ($output1 == 'success') {
                $output = 'success'; // Update output only if successful
            } else if ($output1 == 'data exists') {
                return $this->response->setJSON(["status" => 500, "message" => "Already Exists!"]);
            } else {
                // Capture error messages
                $errorMessages[] = "Failed to update item with ID $id: $output1";
            }
        }

        // Process deposits
        foreach ($depositArray as $arr2) {
            $lcode = $arr2['lcode'];
            $id = $arr2['ID'];

            $data2 = [
                'New_L_Code' => $lcode,
                'L_Code' => '1-6-112-10782',
            ];

            $output1 = $accRunningmodel->where('ID', $id)->where('L_Code', $lcode)->where('bankst_flag', 0)->set($data2)->update();


            if ($output1 == 'success') {
                $output = 'success'; // Update output only if successful
            } else if ($output1 == 'data exists') {
                return $this->response->setJSON(["status" => 500, "message" => "Already Exists!"]);
            } else {
                // Capture error messages
                $errorMessages[] = "Failed to update deposit with ID $id: $output1";
            }
        }

        // Log the last query

        // log_message('critical', $lastQuery ? $lastQuery : 'No last query available');

        // Return final response based on the last output
        if ($output == 'success') {
            return $this->response->setJSON(["status" => 200, "message" => "successfully Posted!"]);
        } else {
            return $this->response->setJSON(["status" => 400, "message" => "Insert Error!"]);
        }
    }


    public function repost_unrealised_entries()
    {
        $date1 = strip_tags($this->request->getVar('date1'));
        $date2 = strip_tags($this->request->getVar('date2'));
        $bank = strip_tags($this->request->getVar('bank'));
        if ($r = LegacyGuard::checkRange(\Config\Database::connect(), (string) $bank, (string) $date1, (string) $date2, 'repost_unrealised_entries')) { // tf-bankrec-r3 (D7)
            return $this->legacyRefused($r);
        }
        $output = "error"; // Default to error
        $AccountRunningModel = new AccountRunningModel();

        // Make sure to replace 'your_table_name' with the actual table name
        $sql = "UPDATE account_runing 
    SET L_Code = New_L_Code 
    WHERE L_Code = '1-6-112-10782' 
    AND bankst_flag = 0 
    AND New_L_Code = ? 
    AND date(Date) >= ? 
    AND date(Date) <= ?";

        // Execute the query
        $output1 = $AccountRunningModel->query($sql, [$bank, $date1, $date2]);

        // Check for any affected rows
        if ($output1) {
            // Check how many rows were affected
            $affectedRows = $AccountRunningModel->affectedRows();
            if ($affectedRows > 0) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Reposted!"]);
            } else {
                return $this->response->setJSON(["status" => 204, "message" => "No rows updated."]);
            }
        } else {
            // Get the error message
            $error = $AccountRunningModel->error();
            return $this->response->setJSON(["status" => 400, "message" => "Update Error: " . $error['message']]);
        }
    }


    public function repost_single_entry()
    {
        // $date1 = $this->request->getVar('date1');
        // $date2 = $this->request->getVar('date2');
        $rowId = strip_tags($this->request->getVar('rowId'));
        if ($r = LegacyGuard::checkRows(\Config\Database::connect(), [$rowId], 'repost_single_entry')) { // tf-bankrec-r3 (D7)
            return $this->legacyRefused($r);
        }

        $output = "error"; // Default to error
        $AccountRunningModel = new AccountRunningModel();

        // Make sure to replace 'your_table_name' with the actual table name
        $sql = "UPDATE account_runing 
    SET L_Code = New_L_Code 
    WHERE L_Code = '1-6-112-10782' 
    AND bankst_flag = 0 
    AND ID = ? 
    ";

        // Execute the query
        $output1 = $AccountRunningModel->query($sql, [$rowId]);

        // Check for any affected rows
        if ($output1) {
            // Check how many rows were affected
            $affectedRows = $AccountRunningModel->affectedRows();
            if ($affectedRows > 0) {
                return $this->response->setJSON(["status" => 200, "message" => "successfully Reposted!"]);
            } else {
                return $this->response->setJSON(["status" => 204, "message" => "No rows updated."]);
            }
        } else {
            // Get the error message
            $error = $AccountRunningModel->error();
            return $this->response->setJSON(["status" => 400, "message" => "Update Error: " . $error['message']]);
        }
    }



    public function get_post_data()
    {
        // $date1 = strip_tags($this->request->getVar('date1'));
        $date2 = strip_tags($this->request->getVar('date2'));
        $bank = strip_tags($this->request->getVar('bank'));
        $menu = strip_tags($this->request->getVar('menu'));
        // $flag = $this->request->getVar('flag');

        // $date1 = substr($daterange, 0, 10);
        // $date2 = substr($daterange, 13, 23);
        $BankReconcilModel = new BankReconcilModel();
        $get_dataset = $BankReconcilModel->load_post_data($date2, $bank);
        $data['result'] = $get_dataset;


        echo json_encode($data);
    }



    public function load_printview()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();


        // $date1 = strip_tags($this->request->getGet('date1'));
        $date2 = strip_tags($this->request->getGet('date2'));
        $bank = strip_tags($this->request->getGet('bank'));
        $data['date'] = $date2;




        $BankReconcilModel = new BankReconcilModel();
        $get_dataset = $BankReconcilModel->load_bank_details_summary($date2, $bank, 1);
        $data['result'] = $get_dataset;

        $get_dataset = $BankReconcilModel->load_uncleared_summary($date2, $bank, 1);
        $data['uncleared_entries'] = $get_dataset;


        $get_dataset = $BankReconcilModel->load_cleared_entries($date2, $bank, 1);
        $data['details'] = $get_dataset;


        $get_dataset = $BankReconcilModel->load_cleared_entries($date2, $bank, 0);
        $data['uncleared_details'] = $get_dataset;

        // $get_dataset = $BankReconcilModel->load_bank_details($date1, $date2,$bank,1);
        // $data['details'] = $get_dataset;



        $res = $BankReconcilModel->load_opening_balance($date2, $bank);

        // Prepare the response data with only the opening balance
        $data['opening_balance'] = $res->bal ?? 0;




        // return view('pages/accounts/bank_reconcillation_details', $data);
        return view('pages/accounts/bank_reconcillation_summary', $data);
    }



    public function load_details_print()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();


        // Assuming you have this in your controller method:
        $date2 = strip_tags($this->request->getGet('date2'));
        $bank = strip_tags($this->request->getGet('bank'));
        $data['date'] = $date2;

        // Use global DateTime class by adding a backslash before DateTime
        $date = new \DateTime($date2);  // Note the backslash before DateTime
        $date->modify('first day of this month');

        // Get the modified date in the desired format
        $first_day_of_month = $date->format('Y-m-d');
        $data['first_date'] = $first_day_of_month;

        // You can then use $first_day_of_month in your logic





        $BankReconcilModel = new BankReconcilModel();
        $get_dataset = $BankReconcilModel->load_bank_details_summary($date2, $bank, 1);
        $data['result'] = $get_dataset;

        $get_dataset = $BankReconcilModel->load_uncleared_summary($date2, $bank, 1);
        $data['uncleared_entries'] = $get_dataset;

        $res = $BankReconcilModel->load_opening_balance2($first_day_of_month, $bank);
        $ob = $BankReconcilModel->load_opening_balance($date2, $bank);
        $data['ending_balance'] = $ob->bal ?? 0;
        // Prepare the response data with only the opening balance
        $data['opening_balance'] = $res->bal ?? 0;

        // return view('pages/accounts/bank_reconcillation_details', $data);
        return view('pages/accounts/reconcillation_report', $data);
    }


    public function load_summary()
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


        $date2 = strip_tags($this->request->getGet('drange2'));
        $bank = strip_tags($this->request->getGet('bank'));
        $data['date'] = $date2;
        $data['bank_lcode'] = $bank;



        if (!empty($date2) && !empty($bank)) {


            $BankReconcilModel = new BankReconcilModel();
            $get_dataset = $BankReconcilModel->load_bank_details_summary($date2, $bank, 1);
            $data['result'] = $get_dataset;

            $get_dataset = $BankReconcilModel->load_uncleared_summary($date2, $bank, 1);
            $data['uncleared_entries'] = $get_dataset;

            $res = $BankReconcilModel->load_opening_balance($date2, $bank);

            // Prepare the response data with only the opening balance
            $data['opening_balance'] = $res->bal ?? 0;
        }


        return view('pages/accounts/bank_reconsil_summary', $data);
    }



    public function load_details()
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


        $date2 = strip_tags($this->request->getGet('drange2'));
        $bank = strip_tags($this->request->getGet('bank'));
        $data['date'] = $date2;
        $data['bank_lcode'] = $bank;



        if (!empty($date2) && !empty($bank)) {


            $BankReconcilModel = new BankReconcilModel();
            $get_dataset = $BankReconcilModel->load_bank_details_summary($date2, $bank, 1);
            $data['result'] = $get_dataset;

            $get_dataset = $BankReconcilModel->load_uncleared_summary($date2, $bank, 1);
            $data['uncleared_entries'] = $get_dataset;


            $get_dataset = $BankReconcilModel->load_cleared_entries($date2, $bank, 1);
            $data['details'] = $get_dataset;


            $get_dataset = $BankReconcilModel->load_cleared_entries($date2, $bank, 0);
            $data['uncleared_details'] = $get_dataset;

            // $get_dataset = $BankReconcilModel->load_bank_details($date1, $date2,$bank,1);
            // $data['details'] = $get_dataset;



            $res = $BankReconcilModel->load_opening_balance($date2, $bank);

            // Prepare the response data with only the opening balance
            $data['opening_balance'] = $res->bal ?? 0;
        }


        return view('pages/accounts/bank_reconcil_details', $data);
    }
}
