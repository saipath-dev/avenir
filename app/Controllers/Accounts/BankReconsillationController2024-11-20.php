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

class BankReconsillationController extends BaseController
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



		return view('pages/accounts/bank_reconcillation', $data);
	}


	public function get_req_items(){
        $date1 = strip_tags($this->request->getVar('date1'));
        $date2 = strip_tags($this->request->getVar('date2'));
        $bank = strip_tags($this->request->getVar('bank'));
        $menu = strip_tags($this->request->getVar('menu'));
        $flag = strip_tags($this->request->getVar('flag'));

		// $date1 = substr($daterange, 0, 10);
		// $date2 = substr($daterange, 13, 23);
        $BankReconcilModel = new BankReconcilModel();
        $get_dataset = $BankReconcilModel->load_bank_details($date1, $date2,$bank,$flag);
        $data['result'] = $get_dataset;

        echo json_encode($data);
    }


    public function get_opening_balance() { 
      $date1 = strip_tags($this->request->getVar('date1'));
      $date2 = strip_tags($this->request->getVar('date2'));
      $bank = strip_tags($this->request->getVar('bank'));

      $BankReconcilModel = new BankReconcilModel();
      $result = $BankReconcilModel->load_opening_balance($date1, $date2, $bank);

    // Prepare the response data with only the opening balance
      $data = [
        'opening_balance' => $result->bal ?? 0 // Default to 0 if no result
    ];

    return $this->response->setJSON($data);
}


public function get_realised_sum(){
 $date1 = strip_tags($this->request->getVar('date1'));
 $date2 = strip_tags($this->request->getVar('date2'));
 $bank = strip_tags($this->request->getVar('bank'));
 $menu = strip_tags($this->request->getVar('menu'));
 $flag = strip_tags($this->request->getVar('flag'));

		// $date1 = substr($daterange, 0, 10);
		// $date2 = substr($daterange, 17, 10);

 $BankReconcilModel = new BankReconcilModel();
 $get_dataset = $BankReconcilModel->get_realised_sum($date1, $date2,$bank,$flag);
 $data['result'] = $get_dataset;

 echo json_encode($data);
}



/******************** UPDATE ***********************/
public function update()
{

 $id = strip_tags($this->request->getVar('id'));
 $flag = strip_tags($this->request->getVar('flag'));


 if($flag==0){
  $bnkst_flg=1;
}else{
  $bnkst_flg=0;
}


$accRunningmodel = new AccountRunningModel();


$data = [
  'bankst_flag' => $bnkst_flg
];
$where = ['ID' => $id];


if($accRunningmodel->update($where,$data)){
  return $this->response->setJSON(["status" => 200, "message" => "Invoice saved successfully!"]);
}else{
  return $this->response->setJSON(["status" => 400, "message" => "Insert Error!"]);
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

    	$output1 = $accRunningmodel->where('ID',$id)->where('L_Code',$lcode)->where('bankst_flag',0)->set($data)->update();

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

    	$output1 = $accRunningmodel->where('ID',$id)->where('L_Code',$lcode)->where('bankst_flag',0)->set($data2)->update();


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



public function get_post_data(){
	$date1 = strip_tags($this->request->getVar('date1'));
	$date2 = strip_tags($this->request->getVar('date2'));
	$bank = strip_tags($this->request->getVar('bank'));
	$menu = strip_tags($this->request->getVar('menu'));
	// $flag = $this->request->getVar('flag');

		// $date1 = substr($daterange, 0, 10);
		// $date2 = substr($daterange, 13, 23);
	$BankReconcilModel = new BankReconcilModel();
	$get_dataset = $BankReconcilModel->load_post_data($date1, $date2,$bank);
	$data['result'] = $get_dataset;
	// $lastQuery = $BankReconcilModel->getLastQuery();
	 // log_message('critical', $lastQuery ? $lastQuery : 'No last query available');

	echo json_encode($data);
}



public function load_printview(){
    $data = array();

        // Load menu data using the custom library
    $menuLoader = new MenuLoader();
    $data = $menuLoader->loadMenuData();


    $date1 = strip_tags($this->request->getGet('date1'));
    $date2 = strip_tags($this->request->getGet('date2'));
    $bank = strip_tags($this->request->getGet('bank'));
    $data['date'] = $date2;




    $BankReconcilModel = new BankReconcilModel();
    $get_dataset = $BankReconcilModel->load_bank_details_summary($date1, $date2,$bank,1);
    $data['result'] = $get_dataset;

    $get_dataset = $BankReconcilModel->load_uncleared_summary($date1, $date2,$bank,1);
    $data['uncleared_entries'] = $get_dataset;


    $get_dataset = $BankReconcilModel->load_cleared_entries($date1, $date2,$bank,1);
    $data['details'] = $get_dataset;


    $get_dataset = $BankReconcilModel->load_cleared_entries($date1, $date2,$bank,0);
    $data['uncleared_details'] = $get_dataset;

    // $get_dataset = $BankReconcilModel->load_bank_details($date1, $date2,$bank,1);
    // $data['details'] = $get_dataset;



    $res = $BankReconcilModel->load_opening_balance($date1, $date2, $bank);

    // Prepare the response data with only the opening balance
    $data['opening_balance']= $res->bal ?? 0;




    // return view('pages/accounts/bank_reconcillation_details', $data);
    return view('pages/accounts/bank_reconcillation_summary', $data);
}



public function load_details_print(){
    $data = array();

        // Load menu data using the custom library
    $menuLoader = new MenuLoader();
    $data = $menuLoader->loadMenuData();


    $date1 = strip_tags($this->request->getGet('date1'));
    $date2 = strip_tags($this->request->getGet('date2'));
    $bank = strip_tags($this->request->getGet('bank'));
    $data['date'] = $date2;




    $BankReconcilModel = new BankReconcilModel();
    $get_dataset = $BankReconcilModel->load_bank_details_summary($date1, $date2,$bank,1);
    $data['result'] = $get_dataset;

    $get_dataset = $BankReconcilModel->load_uncleared_summary($date1, $date2,$bank,1);
    $data['uncleared_entries'] = $get_dataset;

    $res = $BankReconcilModel->load_opening_balance($date1, $date2, $bank);

    // Prepare the response data with only the opening balance
    $data['opening_balance']= $res->bal ?? 0;




    return view('pages/accounts/bank_reconcillation_details', $data);
}









}
