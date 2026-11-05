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
use App\Models\TourQuotationsModel;

class VoucherPaymentController extends BaseController
{
  protected $VoucherPaymentModel;

  public function __construct()
  {
    date_default_timezone_set('Asia/Colombo');
    $this->VoucherPaymentModel = new VoucherPaymentModel();
  }

  // route : base_url/accounts/balance_sheet
  public function index($type = null)
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

    // Access the URI segment correctly
    $uri = service('uri');
    $data['payment_type'] = $uri->getSegment(3);

    $accountposting = new AccountPostingModel();
    $data['load_bank'] = $accountposting->select('PName,L_Code')
    ->where('B_Code', 1)
    ->where('T_Code', 6)
    ->where('H_Code', 8)
    ->findAll();

    $StaffDivisionModel = new StaffDivisionModel();
    $data['division'] = $StaffDivisionModel->select('*')
    ->where('del', 0)
    ->findAll();

    return view('pages/accounts/voucher_request', $data);
  }

  public function fetch()
  {

    $uri = service('uri');
    // $data['payment_type'] = $uri->getSegment(3);
    $segment4 = $uri->getSegment(4); // Assuming this is an ID or parameter
    $segment5 = $uri->getSegment(5); // Decode the query part

    $vouchermode = new VoucherPaymentModel();

    // Assuming your model method returns data
    $data = $vouchermode->fetch_data($segment4, $segment5);

    // Convert data to JSON and output it
    echo json_encode($data);
  }



  public function getSupplierDetails()
  {
    $segment4 = strip_tags($this->request->getGet('l_code'));

    $vouchermode = new VoucherPaymentModel();

    // Assuming your model method returns data
    $data = $vouchermode->getSupplierDetails($segment4);

    // Convert data to JSON and output it
    echo json_encode($data);
  }



  public function getVoucherDetails()
  {
    $v_num = strip_tags($this->request->getGet('v_num'));

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


      $model_inv = new InvoiceNumberModel();
      $inv = $model_inv->incrementVoucherToken();
      $v_num = $inv;

      // Extract POST data
      $itemArray = $this->request->getVar('itemArray');
      $acc = strip_tags($this->request->getVar('acc'));
      $acc_name = strip_tags($this->request->getVar('acc_name'));
      $p_method = strip_tags($this->request->getVar('p_method'));
      $v_type = strip_tags($this->request->getVar('v_type'));
      $ref_no = strip_tags($this->request->getVar('ref_no'));
      $grnd_tot = strip_tags($this->request->getVar('grnd_tot'));
      $div = strip_tags($this->request->getVar('div'));
      $req = strip_tags($this->request->getVar('req'));
      $date1 = strip_tags($this->request->getVar('date1'));
      $inv_date = strip_tags($this->request->getVar('inv_date'));
      $ref_supplier = strip_tags($this->request->getVar('ref_supplier'));
      $description = strip_tags($this->request->getVar('description'));
      $bnk = '';
      $bnk_name = '';
      $chq_date = '';
      $chq_no = '';
      $chq_date = strip_tags($this->request->getVar('chq_date'));
      $chq_no = strip_tags($this->request->getVar('chq_no'));

      // Populate optional fields for cheque payment
      if (($p_method === 'BT') || ($p_method === 'Bank Transfer')) {
        $bnk = strip_tags($this->request->getVar('bank'));
        $bnk_name = strip_tags($this->request->getVar('bank_name'));
      }

      // Prepare data for updating or inserting
      $data = [
        'Pay' => $acc_name,
        'emp_code' => $acc,
        'S_name' => $acc_name,
        'Acc_No' => $acc,
        'sup' =>  $v_type,
        'type' => $p_method,
        'Amount' => $grnd_tot,
        'VAT' => '0',
        'VAT_Amt' => '0',
        'vat_m' => '0',
        'nbt' => '0',
        'nbt_amt' => '0',
        'nbt_m' => '0',
        'g_ttl' => $grnd_tot,
        'Address' => '0',
        'print' => '0',
        'des' => $description,
        'W_Date' => $chq_date,
        'dv' => $div,
        'app_user' => $req,
        'login' =>  session()->get('username'), // Assuming $user is defined somewhere
        'Aflag' => '0',
        'v_typ' => $p_method,
        'ref_po' => $ref_no,
        'insflag' => 1,
        'inv' => '0',
        'Vnum' => $v_num,
        'Bank' => $bnk_name,
        'Bank_acc' => $bnk,
        'C_Date' => $chq_date,
        'C_Number' => $chq_no,
        'ref_acc_no' => $ref_supplier,
        // 'W_Date' => date('Y-m-d')
      ];

      $voucherpaymentmodel  = new VoucherPaymentModel();
      $insertResult = $voucherpaymentmodel->insert($data);
      // log_message('critical', $voucherpaymentmodel->getLastQuery());

      // Insert items related to the invoice (assuming 'v_inv' is your item table)
      foreach ($itemArray as $arr) {
        if ($v_type == 2) {
          $amt = $grnd_tot;
        } else {
          $amt = strip_tags($arr['amount']);
        }
        $data_add = [
          'Date' => date('Y-m-d'),
          'Invoice' => strip_tags($arr['inv_no']),
          'Description' => strip_tags($arr['desc']),
          'Units' =>  strip_tags($arr['units']),
          'Rate' => strip_tags($arr['rate']),
          'Amount' => strip_tags($arr['amount']),
          'InvAmount' => $amt,
          'account' => strip_tags($arr['sup_lcode']),
          'V_tkn' => $v_num,
          'ityp' => '1',
          'link_code' => $acc,
          'employee' =>  strip_tags($arr['supplier_name']),
          'login' =>  session()->get('username'), // Assuming $user is defined somewhere
          'INV_Date' => strip_tags($arr['date']),
          'Payee' =>  strip_tags($arr['supplier_name']),
        ];
        // $this->db->insert("v_inv", $data_add);
        $v_invmodel = new VoucherInvoiceModel();
        $v_invmodel->insert($data_add);
      }

      // Return success message or handle response as needed
      return $this->response->setJSON(["status" => 200, "message" => "Invoice saved successfully!"]);
    } catch (\Exception $e) {
      // Handle exceptions
      return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
    }
  }

  public function auth()
  {
    $data = array();

    // Load menu data using the custom library
    $menuLoader = new MenuLoader();
    $data = $menuLoader->loadMenuData();

    $tour_id = urldecode($this->request->getGet('tour_id'));
    $tour = $this->request->getGet('tour_id');

    $consigneemodel = new ConsigneeModel();


    $data['tour_details'] = $consigneemodel->where('tour_id', $tour)->first();


    $voucherpaymentmodel  = new VoucherPaymentModel();
    $data['voucher_request'] =  $voucherpaymentmodel->load_vouchers($tour_id);

    return view('pages/accounts/voucher_auth', $data);
  }


  public function print($id = NULL)
  {
    $data = array();

    // Load menu data using the custom library
    // $menuLoader = new MenuLoader();
    // $data = $menuLoader->loadMenuData();


    $data['id'] = isset($_GET['id']) ? $_GET['id'] : null;
    $data['status'] = isset($_GET['status']) ? $_GET['status'] : null;
    $data['bank_acc'] = isset($_GET['bank_acc']) ? $_GET['bank_acc'] : null;
    $data['type'] = isset($_GET['type']) ? $_GET['type'] : null;
    $data['amount'] = isset($_GET['amount']) ? $_GET['amount'] : null;
    $data['sup'] = isset($_GET['sup']) ? $_GET['sup'] : null;
    $data['payee'] = isset($_GET['payee']) ? $_GET['payee'] : null;
    $data['menu'] = isset($_GET['menu']) ? $_GET['menu'] : null;

    $voucherpaymentmodel  = new VoucherPaymentModel();
    $data['voucher_details'] =  $voucherpaymentmodel->load_voucher_details($id);


    return view('pages/accounts/voucher_print', $data);
  }


  /******************** SAVE ***********************/
  public function update()
  {
    $id = strip_tags($this->request->getVar('id'));
    $status = strip_tags($this->request->getVar('status'));
    $bank_acc = strip_tags($this->request->getVar('bank_acc'));
    $pay_type = strip_tags($this->request->getVar('type'));
    $amount = strip_tags($this->request->getVar('amount'));
    $sup = strip_tags($this->request->getVar('sup'));
    $payee = strip_tags($this->request->getVar('payee'));
    $datesArray = [];

    $vouchermodel = new VoucherPaymentModel();
    $tourquotationsModel = new TourQuotationsModel();

    $voucher_details = $vouchermodel->where('ID', $id)->first();

    $model_inv = new InvoiceNumberModel();
    $voucher_no = $model_inv->incrementVoucher();
    $voucher_no = $voucher_no;

    if($voucher_details['rej'] == 1){
     return $this->response->setJSON(["status" => 301, "message" => "Voucher doesn't Exist!"]);
   }else{

    if ($sup == 2) {

      if (($pay_type == 'BT') || ($pay_type == 'Bank Transfer')) {
        $cr = $bank_acc;
        // $cr = '1-6-8-9750';
        // $chk = $voucher->C_Number;
      } else if ($pay_type == 'Cash') {
        $cr = '1-2-3-1000';
        // $chk = '';
      } else if ($pay_type == 'Petty Cash') {
        $cr = '1-2-4-1000';
        // $chk = '';
      } else if ($pay_type == 'Cheque') {
        $cr = '1-6-8-9352';
      }



      $voucher_num = $voucher_details['Vnum'];
      $record = $vouchermodel->select('ref_po, V_num')->where('Vnum', $voucher_num)->first();
      $datesArray = explode(',', $record['V_num']);
      $tour = $record['ref_po'];

      $this->acc_array(
        '1-6-8-9750',
        $cr,
        $amount,
        'VOUCHER PAYMENT',
        '0',
        '0',
        $voucher_details['W_Date'],
        '',
        $voucher_details['ref_acc_no'],
        '3',
        '0',
        $voucher_details['ref_acc_no'],
        $voucher_details['W_Date'],
        '0',
        $voucher_details['Vnum'],
        $payee,
        $voucher_details['advances_pay']
      );
    }

    if ($status == 1) {
      // Retrieve voucher details
      $vouchers = $vouchermodel->voucher($id);

      $vouchers_record = $vouchermodel->where('ID', $id)->first();
      $voucher_num =  $vouchers_record['Vnum'];

      $record = $vouchermodel->select('ref_po, V_num')->where('Vnum', $voucher_num)->first();

      $datesArray = explode(',', $record['V_num']);
      $tour = $record['ref_po'];

      // Check if $vouchers is an array or collection
      // if (is_array($vouchers) || $vouchers instanceof Traversable) {
      foreach ($vouchers as $voucher) {

        $type = $voucher->type;
        $V_tkn = isset($voucher->V_tkn) ? $voucher->V_tkn : '';

        if (($type == 'BT') || ($type == 'Bank Transfer')) {

          if ($sup == 1) {
            $cr = $voucher->Bank_acc;
          } else {
            $cr = '1-6-8-9750';
          }
          // $cr = $voucher->Bank_acc;

          $chk = $voucher->C_Number;
        } else if ($type == 'Cash') {
          if ($sup == 1) {
            $cr = '1-2-3-1000';
          } else {
            $cr = '1-6-8-9750';
          }
          $chk = '';
          $chk = $voucher->C_Number;
        } else if ($type == 'Petty Cash') {
          if ($sup == 1) {
            $cr = '1-2-4-1000';
          } else {
            $cr = '1-6-8-9750';
          }
          $chk = '';
          $chk = $voucher->C_Number;
        } else if ($type == 'Cheque') {
          if ($sup == 1) {
            $cr = '1-6-8-9352';
          } else {
            $cr = '1-6-8-9750';
          }
        }

        $this->acc_array(
          $voucher->account,
          $cr,
          $voucher->sup_amount,
          'Voucher Payment - ' . $voucher->Description,
          '0',
          '0',
          $voucher->W_Date,
          $chk,
          $voucher->ref_acc_no,
          '3',
          '0',
          $voucher->ref_acc_no,
          $voucher->W_Date,
          '0',
          $voucher->Vnum,
          $payee,
          $voucher->advances_pay
        );

        // Update voucher status
        $data = [
          'Aflag' => $status,
          'Aut_user' => session()->get('username'),
          'aut_date' => date('Y-m-d')
        ];

        $where = ['ID' => $voucher->ID];

        if (!$vouchermodel->update($where, $data)) {
          // If any update fails, return an error response
          return $this->response->setJSON([
            "status" => 400,
            "message" => "Insert Error!"
          ]);
        }
      }

      $data = [
        'Aflag' => $status,
        'Voucher_No' => $voucher_no
      ];
      $where = ['ID' => $id];
      $vouchermodel->update($where, $data);

      foreach ($datesArray as $date) {
        $date = date('Y-m-d', strtotime(trim($date)));
        $data2 = [
          'hotel_payment_flag' => $status,

        ];
        $tourquotationsModel->set($data2)->where('tour_date', $date)->where('quotation_id', $tour)->update();
      }


      // If all updates succeed, return a success response
      return $this->response->setJSON([
        "status" => 200,
        "message" => "Invoice saved successfully!",
        "v_inv" => $V_tkn
      ]);

      //   } else {
      // // Handle case where $vouchers is not an array or Traversable
      //     return $this->response->setJSON([
      //       "status" => 400, 
      //       "message" => "Invalid voucher data!"
      //     ]);
      //   }

    } else if ($status == 2) {

      $data = ['Aflag' => $status, 'rej_user' => session()->get('username'), 'aut_date' => date('Y-m-d')];
      $where = ['ID' => $id];

      $vouchers_record = $vouchermodel->where('ID', $id)->first();
      $voucher_num =  $vouchers_record['Vnum'];

      $record = $vouchermodel->select('ref_po, V_num')->where('Vnum', $voucher_num)->first();

      $datesArray = explode(',', $record['V_num']);
      $tour = $record['ref_po'];

      foreach ($datesArray as $date) {
        $date = date('Y-m-d', strtotime(trim($date)));
        $data1 = [
          'booking_confirmed_flag' => $status,
          'hotel_payment_flag' => $status
        ];
        $tourquotationsModel->set($data1)->where('tour_date', $date)->where('quotation_id', $tour)->update();
      }

      // Delete voucher
      if ($vouchermodel->update($where, $data)) {
        return $this->response->setJSON(["status" => 200, "message" => "Voucher Rejected successfully!"]);
      } else {
        return $this->response->setJSON(["status" => 400, "message" => "Deletion Error!"]);
      }
    }

  }




}

public function acc_array($Dr, $Cr, $amoun, $des, $st, $inv, $date, $chk_no, $link_acc, $aflag, $job, $index_no, $dt, $card_no, $rect_no, $payee, $advance_pay)
{

    // require_once(APPPATH . 'libraries/Cashier_session.php');
  $menuLoader = new MenuLoader();
  $data = $menuLoader->loadMenuData();

  $Cashier = strip_tags(session()->get('username'));
  $shift_id = "";
  $acc_no = "";
  $divID = 1;
  $store = "";
  $today = date('Y-m-d');

  $invoiceNoModel = new InvoiceNumberModel();
  $acc_id = $invoiceNoModel->incrementAccId();
    // $user = strip_tags(session()->get('emp_id'));

  $acc = array(
    array(
      'ID' => $acc_id,
      'Invoice_Num' => $inv,
      'L_Code' => $Dr,
      'Dr' => $amoun,
      'Cr' => '0',
      'Discription' => 'VOUCHER PAYMENT - ' . $des,
      'Date' => $date,
      'status' => $st,
      'Cashier' => $Cashier,
      'shift_id' => $shift_id,
      'Chk_No' => $chk_no,
      'DV' => $divID,
      'Linkacc' => $link_acc,
      'aflag' => $aflag,
      'job' => $job,
      'index_no' => $chk_no,
      'P_Cap' => '',
      'P_Month' => $date,
      'Tax' => '',
      'srvc_no' => '',
      'Dt' => $dt . ' ' . date("H:i:s"),
      'Fee' => '',
      'Effective_Date' => '',
      'Card_No' => $card_no,
      'Recpt_No' => $rect_no,
      'payee' => $payee,
      'advances_pay' => $advance_pay
    ),
    array(
      'ID' => $acc_id,
      'Invoice_Num' => $inv,
      'L_Code' => $Cr,
      'Dr' => 0,
      'Cr' => $amoun,
      'Discription' => 'VOUCHER PAYMENT - ' . $des,
      'Date' => $date,
      'status' => $st,
      'Cashier' => $Cashier,
      'shift_id' => $shift_id,
      'Chk_No' => $chk_no,
      'DV' => $divID,
      'Linkacc' => $link_acc,
      'aflag' => $aflag,
      'job' => $job,
      'index_no' => $chk_no,
      'P_Cap' => '',
      'P_Month' => $date,
      'Tax' => '',
      'srvc_no' => '',
      'Dt' => $dt . ' ' . date("H:i:s"),
      'Fee' => '',
      'Effective_Date' => '',
      'Card_No' => $card_no,
      'Recpt_No' => $rect_no,
      'payee' => $payee,
      'advances_pay' => $advance_pay
    )
  );

    // $output = $this->Payment_model->accountFinish($acc);
  $accrunningmodel = new AccountRunningModel();
  if ($accrunningmodel->insertBatch($acc)) {

    $congineeModel = new ConsigneeModel();
    if ($advance_pay == 1) {
      $congineeModel->set('transport_advance', 1)->where('tour_id', $chk_no)->update();
    }

    if ($advance_pay == 11) {
      $congineeModel->set('transport_settled', 1)->where('tour_id', $chk_no)->update();
    }

    return "success";
  } else {
    return "error";
  }
}



public function voucher_cancel()
{
  $data = array();

    // Load menu data using the custom library
  $menuLoader = new MenuLoader();
  $data = $menuLoader->loadMenuData();


  $voucherpaymentmodel  = new VoucherPaymentModel();
  $data['voucher_request'] =  $voucherpaymentmodel->load_vouchers_new();

  return view('pages/accounts/voucher_cancel', $data);
}


public function voucher_delete()
{
  $data = array();


  $id = strip_tags($this->request->getVar('id'));

  $data = ['rej' => 1, 'rej_user' => session()->get('username'), 'rejdate' => date('Y-m-d')];
  $where = ['ID' => $id];


  $vouchermodel = new VoucherPaymentModel();

     // Delete voucher
  if ($vouchermodel->update($where, $data)) {
    return $this->response->setJSON(["status" => 200, "message" => "Voucher Rejected successfully!"]);
  } else {
    return $this->response->setJSON(["status" => 400, "message" => "Deletion Error!"]);
  }


    // $voucherpaymentmodel  = new VoucherPaymentModel();
    // $data['voucher_request'] =  $voucherpaymentmodel->load_vouchers_new();

    // return view('pages/accounts/voucher_cancel', $data);
}






}
