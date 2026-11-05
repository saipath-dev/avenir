<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\VoucherPaymentModel;
use App\Models\AccountPostingModel;
use App\Models\UserModel;
use App\Models\InvoiceNumberModel;
use App\Models\VoucherInvoiceModel;
use App\Models\AccountRunningModel;
use App\Models\StaffDivisionModel;
use App\Models\SupplierModel;
use App\Models\ConsigneeModel;
use App\Models\AccSelectModel;

class UtilityBillPaymentController extends BaseController
{
  protected $VoucherPaymentModel;

  public function __construct()
  {
    date_default_timezone_set('Asia/Colombo');
    // $this->VoucherPaymentModel = new VoucherPaymentModel();
  }

  // route : base_url/accounts/balance_sheet
  public function index()
  {
    $data = array();

    // Load menu data using the custom library
    $menuLoader = new MenuLoader();
    $data = $menuLoader->loadMenuData();

    $usermodel = new UserModel();
    $data['req_user'] = $usermodel->select('acc_no,user,emp_id')
      ->findAll();

    // Load auctions
    $supplierModel = new SupplierModel();
    $data['auctions'] = $supplierModel->findAll();

    $StaffDivisionModel = new StaffDivisionModel();
    $data['division'] = $StaffDivisionModel->select('*')
      ->where('del', 0)
      ->findAll();

    return view('pages/accounts/utility_bill', $data);
  }

  public function fetch($type, $query)
  {
    $accselectmodel = new AccSelectModel();

    if ($type == 1) {
      $data = $accselectmodel->Where('B_Code', 6)
        ->groupStart()
        ->orLike('PName', $query)
        ->orLike('TName', $query)
        ->orLike('HName', $query)
        ->orLike('BName', $query)
        ->groupEnd()
        ->findAll();
    } else {
      $data1 = $accselectmodel->Where('B_Code', 4)
        // ->orWhere('B_Code', 6)
        ->groupStart()
        ->orLike('PName', $query)
        ->orLike('TName', $query)
        ->orLike('HName', $query)
        ->orLike('BName', $query)
        ->groupEnd()
        ->findAll();

      $data2 = $accselectmodel->Where('B_Code', 1)
        // ->orWhere('B_Code', 6)
        ->groupStart()
        ->orLike('PName', $query)
        ->orLike('TName', $query)
        ->orLike('HName', $query)
        ->orLike('BName', $query)
        ->groupEnd()
        ->findAll();

      $data = array_merge($data1, $data2);
    }
    // log_message('critical', $accselectmodel->getLastQuery());
    return json_encode($data);
  }

  public function getSupplierDetails()
  {
    $segment4 = $this->request->getGet('l_code');

    $vouchermode = new VoucherPaymentModel();

    // Assuming your model method returns data
    $data = $vouchermode->getSupplierDetails($segment4);

    // Convert data to JSON and output it
    echo json_encode($data);
  }



  public function getVoucherDetails()
  {
    $v_num = $this->request->getGet('v_num');

    if (!$v_num) {
      echo json_encode(['error' => 'Voucher number not provided']);
      return;
    }

    $voucherinvmodel = new VoucherInvoiceModel();

    // Assuming your model method returns data
    $data = $voucherinvmodel->select('*')->where('V_tkn', $v_num)->findAll();

    // Check if data is found
    if (empty($data)) {
      echo json_encode(['error' => 'No data found for the given voucher number']);
      return;
    }

    // Convert data to JSON and output it
    echo json_encode($data);
  }

  /******************** SAVE ***********************/
  public function save($id = null)
  {
    try {
      // Extract POST data
      $itemArray = $this->request->getVar('itemArray');
      $acc_liability = $this->request->getVar('acc_liability');
      $acc_name_liability = $this->request->getVar('acc_name_liability');
      $acc_expenses = $this->request->getVar('acc_expenses');
      $acc_name_expenses = $this->request->getVar('acc_name_expenses');
      $grnd_tot = $this->request->getVar('grnd_tot');
      $date1 = $this->request->getVar('date1');
      $description = $this->request->getVar('description');

      if(!$itemArray || !$acc_liability || !$acc_expenses) {
        return $this->response->setJSON(["status" => 500, "message" => "Please fill all required fields"]);
      }

      $accountRunningModel = new AccountRunningModel();
      
      // Ensure $itemArray is an array and remove duplicate entries
      $itemArray = is_array($itemArray) ? array_unique($itemArray, SORT_REGULAR) : [];
      
      foreach ($itemArray as $arr) {
        // Extract values from $arr
        $date = $arr['date'];
        $inv_no = $arr['inv_no'];
        $descrip = $arr['desc'];
        $unit = $arr['units'];
        $rate = $arr['rate'];
        $amount = $arr['amount'];
        
        $invoiceNoModel = new InvoiceNumberModel();
        $acc_id = $invoiceNoModel->incrementAccId();

        $accountRunningData = [
          [
            'ID' => $acc_id,
            'L_Code' => $acc_expenses,
            'Date' => $date,
            'Discription' => $descrip . ' - ' . $inv_no . ' Utility Bill Payment',
            'Dr' => $amount,
            'Cr' => 0.00,
            'Invoice_Num' => $inv_no,
            'Linkacc' => $acc_expenses,
            'index_no' => '',
            'dollarrate' => $rate,
            'dollar' => $unit,
            'job' => '',
            'fine_date' => date('Y-m-d'),
            'Cashier' => session()->get('username'),
            'Dt' => date('Y-m-d H:i:s')
          ],
          [
            'ID' => $acc_id,
            'L_Code' => $acc_liability,
            'Date' => $date,
            'Discription' => $descrip . ' - ' . $inv_no . ' Utility Bill Payment',
            'Dr' => 0.00,
            'Cr' => $amount,
            'Invoice_Num' => $inv_no,
            'Linkacc' => $acc_liability,
            'index_no' => '',
            'dollarrate' => $rate,
            'dollar' => $unit,
            'job' => '',
            'fine_date' => date('Y-m-d'),
            'Cashier' => session()->get('username'),
            'Dt' => date('Y-m-d H:i:s')
          ]
        ];
        $insertResult = $accountRunningModel->insertBatch($accountRunningData);
      }

      // $accountpostingmodel = new AccountPostingModel();
      // $getlink = $accountpostingmodel->select('Link')
      //   ->where('L_Code', $acc_liability)
      //   ->findAll();

      // $link = $getlink[0]['Link'];

      // if ($link != $acc_expenses) {
      //   // update link in account posting
      //   $accountpostingmodel->set('Link', $acc_expenses)
      //     ->where('L_Code', $acc_liability)
      //     ->update();
      // }

      // Return success message or handle response as needed
      return $this->response->setJSON(["status" => 200, "message" => "Invoice saved successfully!"]);
    } catch (\Exception $e) {
      // Handle exceptions
      return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
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
      $query = $accountRunningModel->select('account_runing.*, acc_selct.PName, acc_selct.BName, acc_selct.TName, user_priv_q.name, user_priv_q.user')
        ->join('acc_selct', 'acc_selct.L_Code = account_runing.L_Code', 'LEFT')
        ->join('user_priv_q', 'user_priv_q.account_no = account_runing.Cashier', 'LEFT')
        ->LIKE('account_runing.Discription', 'Utility Bill Payment');

      if ($from != null) {
        $query->where('account_runing.Date >=', $from);
      }

      if ($to != null) {
        $query->where('account_runing.Date <=', $to);
      }
      $query->groupBy('account_runing.ID');
      $query->groupBy('account_runing.Dr');
      $query->groupBy('account_runing.Cr');
      $data['transactions'] = $query->findAll();

    }
    return view('pages/accounts/utility_bill_report', $data);
  }

  // delete record
  public function delete()
  {
    try {
      $id = $this->request->getVar('id');
      
      $accountRunningModel = new AccountRunningModel();
      $accountRunningModel->where('ID', $id)->delete();
      return $this->response->setJSON(["status" => 200, "message" => "Record deleted successfully!"]);
    } catch (\Exception $e) {
      return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
    }

  }
}
