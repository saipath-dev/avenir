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
use App\Models\DriverAllocationModel;

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
    // $uri = service('uri');
    $data['payment_type'] = $type;

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


  public function pcaccumulatedbalance()
  {
    try {
      $accountruning  = new AccountRunningModel();

      // Calculate totals directly from DB
      $db = \Config\Database::connect();
      $row = $db->query("
          SELECT COALESCE(SUM(Dr), 0) AS total_dr, COALESCE(SUM(Cr), 0) AS total_cr
          FROM account_runing
          WHERE L_Code = '1-2-3-10900'
      ")->getRow();

      // log_message('critical', $row->getLastQuery());

      $total_dr = floatval($row->total_dr ?? 0);
      $total_cr = floatval($row->total_cr ?? 0);

      $data = [
        'total_dr' => $total_dr,
        'total_cr' => $total_cr,
        'balance' => $total_dr - $total_cr
      ];

      return $this->response->setJSON(['status' => 200, 'data' => $data]);
    } catch (\Exception $e) {
      return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
    }
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
      $invoice_no = strip_tags($this->request->getVar('invoice_no'));
      $is_special = strip_tags($this->request->getVar('is_special'));
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
        'inv' => $invoice_no,
        'Vnum' => $v_num,
        'Bank' => $bnk_name,
        'Bank_acc' => $bnk,
        'C_Date' => date('Y-m-d H:i:s'),
        'C_Number' => $chq_no,
        'ref_acc_no' => $ref_supplier,
        'is_special' => $is_special,
        // 'W_Date' => date('Y-m-d')
      ];

      $voucherpaymentmodel  = new VoucherPaymentModel();
      $insertResult = $voucherpaymentmodel->insert($data);
      // log_message('critical', $voucherpaymentmodel->getLastQuery());

      // Insert items related to the invoice (assuming 'v_inv' is your item table)
      $x = 0;
      foreach ($itemArray as $arr) {
        if (isset($arr['amount']) && $arr['amount'] > 0) {
          $amt = $v_type == 2 ? $grnd_tot : $arr['amount'];

          $data_add = [
            'Date' => date('Y-m-d'),
            'Invoice' => $arr['inv_no'],
            'Description' => $arr['desc'] . '(' . $arr['inv_no'] . ')',
            'Units' => $arr['units'],
            'Rate' => $arr['rate'],
            'Amount' => $arr['amount'],
            'InvAmount' => $amt,
            'account' => $arr['sup_lcode'],
            'V_tkn' => $v_num,
            'ityp' => '1',
            'link_code' => $acc,
            'employee' => $arr['supplier_name'],
            'login' => session()->get('username'), // Assuming $user is defined somewhere
            'INV_Date' => $arr['date'],
            'Payee' => $arr['supplier_name'],
            'RCType' => 0
          ];

          // Only insert if the data_add array has been populated
          $v_invmodel = new VoucherInvoiceModel();
          $v_invmodel->insert($data_add);
        }
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
    $vouchernumber = $this->request->getGet('voucher_number');

    $consigneemodel = new ConsigneeModel();


    $data['tour_details'] = $consigneemodel->where('tour_id', $tour)->first();


    $voucherpaymentmodel  = new VoucherPaymentModel();
    $data['voucher_request'] =  $voucherpaymentmodel->load_vouchers($tour_id, $vouchernumber);

    // Make tour_id available to view for conditional display
    $data['tour_id'] = $tour_id;

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
    $data['payee'] =  isset($_GET['payee']) ? urldecode($_GET['payee']) : null;
    $data['tourid'] = isset($_GET['tourid']) ? $_GET['tourid'] : null;
    $tourid = isset($_GET['tourid']) ? $_GET['tourid'] : null;

    $consigneemodel = new ConsigneeModel();
    // $TourQuotationsModel = new TourQuotationsModel();

    if ($tourid != 0) {
      $data['tour_details'] = $consigneemodel->where('tour_id', $tourid)->first();
      // $data['room_details'] = $TourQuotationsModel->select('tour_quotations.room_type,room_type.rtype')->join('room_type','tour_quotations.room_type = room_type.id','left outer')->where('quotation_id',$tourid)->first();
      // log_message('critical', $TourQuotationsModel->getLastQuery());


    }

    $voucherpaymentmodel  = new VoucherPaymentModel();
    $VoucherInvoiceModel  = new VoucherInvoiceModel();
    $data['voucher_details'] =  $voucherpaymentmodel->select('voucher.*,supplier.bank_name,branch_name,sup_bank_acc')->join('supplier', 'voucher.Acc_no = supplier.Sup_Acc_No', 'left')->where('Vnum', $id)->first();
    $data['voucher_data'] =  $VoucherInvoiceModel->where('V_tkn', $id)->findAll();


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
    $v_inv_no = strip_tags($this->request->getVar('voucher_no'));   //voucher number
    $datesArray = [];

    $vouchermodel = new VoucherPaymentModel();
    $VoucherInvoiceModel = new VoucherInvoiceModel();
    $tourquotationsModel = new TourQuotationsModel();

    $voucher_details = $vouchermodel->where('ID', $id)->first();

    $model_inv = new InvoiceNumberModel();
    $voucher_no = $model_inv->incrementVoucher();
    $voucher_no = $voucher_no;

    if ($voucher_details['rej'] == 1) {
      return $this->response->setJSON(["status" => 301, "message" => "Voucher doesn't Exist!"]);
    } else {

      if ($sup == 2) {

        if (($pay_type == 'BT') || ($pay_type == 'Bank Transfer')) {
          $cr = $bank_acc;
          // $cr = '1-6-8-9750';
          // $chk = $voucher->C_Number;
        } else if ($pay_type == 'Cash') {
          $cr = '1-2-3-1000';
          // $chk = '';
        } else if ($pay_type == 'Petty Cash') {
          // $cr = '1-2-4-1000';
          $cr = '1-2-3-10900';
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
          $voucher_details['inv'],
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
          $voucher_details['advances_pay'],

        );
      }

      if ($status == 1) { // Approve
        // Retrieve voucher details
        $vouchers = $vouchermodel->voucher($id);

        $vouchers_record = $vouchermodel->where('ID', $id)->first();
        // log_message('critical', $vouchermodel->getLastQuery());

        $voucher_num =  $vouchers_record['Vnum'];


        $record = $vouchermodel->select('ref_po, V_num, Acc_No')->where('Vnum', $voucher_num)->first();
        // log_message('critical', $vouchermodel->getLastQuery());

        $datesArray = explode(',', $record['V_num']);
        // $tour = $record['ref_po'];
        $payee_acc = $vouchers_record['Acc_No'];

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

            // $chk = $voucher->C_Number;
            $chk = $voucher->Invoice;
          } else if ($type == 'Cash') {
            if ($sup == 1) {
              $cr = '1-2-3-1000';
            } else {
              $cr = '1-6-8-9750';
            }
            $chk = '';
            //$chk = $voucher->C_Number;
            $chk = $voucher->Invoice;
          } else if ($type == 'Petty Cash') {
            if ($sup == 1) {
              // $cr = '1-2-4-1000';
              $cr = '1-2-3-10900';
            } else {
              $cr = '1-6-8-9750';
            }
            $chk = '';
            //$chk = $voucher->C_Number;
            $chk = $voucher->Invoice;
          } else if ($type == 'Cheque') {
            if ($sup == 1) {
              $cr = '1-6-8-9352';
            } else {
              $cr = '1-6-8-9750';
            }
          }

          // log_message('critical', 'acc_array() called: {params}', [
          //   'params' => json_encode([
          //     $voucher->account,
          //     $cr,
          //     $voucher->sup_amount,
          //     'Voucher Payment - ' . $voucher->Description,
          //     '0',
          //     $voucher->Invoice,
          //     $voucher->W_Date,
          //     $chk,
          //     $voucher->ref_acc_no,
          //     '3',
          //     '0',
          //     $voucher->ref_acc_no,
          //     $voucher->W_Date,
          //     '0',
          //     $voucher->Vnum,
          //     $payee,
          //     $voucher->advances_pay,
          //   ]),
          // ]);

          $this->acc_array(
            $voucher->account,
            $cr,
            $voucher->sup_amount,
            'Voucher Payment - ' . $voucher->Description,
            '0',
            $voucher->Invoice,
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
            $voucher->advances_pay,

          );

          //Update voucher status
          $data = [
            'Aflag' => $status,
            'Aut_user' => session()->get('username'),
            'aut_date' => date('Y-m-d')
          ];

          $where = ['ID' => $voucher->ID];


          $data2 = [
            'RCType' => $status

          ];

          //$where2 = ['V_tkn' => $voucher->Vnum,'Invoice'=>$voucher->Invoice,'account'=>$voucher->account];


          if ((!$vouchermodel->update($where, $data)) || (!$VoucherInvoiceModel->set($data2)->where('V_tkn', $voucher->Vnum)->where('Invoice', $voucher->Invoice)->where('account', $voucher->account)->update())) {
            // If any update fails, return an error response
            return $this->response->setJSON([
              "status" => 400,
              "message" => "Insert Error!"
            ]);
          }

          log_message('critical', $VoucherInvoiceModel->getLastQuery());
        }

        $data = [
          'Aflag' => $status,
          'Voucher_No' => $voucher_no
        ];
        $where = ['ID' => $id];
        $vouchermodel->update($where, $data);

        // foreach ($datesArray as $date) {
        //   $date = date('Y-m-d', strtotime(trim($date)));
        //   $data2 = [
        //     'hotel_payment_flag' => $status,

        //   ];
        //   $tourquotationsModel->set($data2)->where('tour_date', $date)->where('quotation_id', $tour)->where('hotel_acc',$payee_acc)->update();

        // //   log_message('critical', $tourquotationsModel->getLastQuery());

        // }


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

      } else if ($status == 2) { // Reject

        $data = ['Aflag' => $status, 'rej_user' => session()->get('username'), 'aut_date' => date('Y-m-d')];
        $where = ['ID' => $id];

        $vouchers_record = $vouchermodel->where('ID', $id)->first();
        $voucher_num =  $vouchers_record['Vnum'];

        $record = $vouchermodel->select('ref_po, V_num, advances_pay, Acc_No, Amount, doller_amt')->where('Vnum', $voucher_num)->first();
        // log_message('critical', $vouchermodel->getLastQuery());

        $datesArray = explode(',', $record['V_num']);
        $tour = $record['ref_po'];
        $amt_status = $record['advances_pay'];
        $hotel_code = $record['Acc_No'];
        $vamount = $record['Amount'];
        $dollaramt = $record['doller_amt'];

        if ($amt_status == 1 || $amt_status == 11 || $amt_status == 111) { // Transport

        } else if ($amt_status == 2 || $amt_status == 22 || $amt_status == 222) { // Hotel


          foreach ($datesArray as $date) {
            // get pay_doller_amt from tour_quotations
            $date = date('Y-m-d', strtotime(trim($date)));

            $record = $tourquotationsModel->select('pay_doller_count')->where('tour_date', $date)->where('quotation_id', $tour)->first();

            $pay_doller_amt = $record['pay_doller_count'] ?? 0;
            $updateddollaramt = $pay_doller_amt - $dollaramt;

            $data1 = [
              'hotel_payment_flag' => $status,
              'pay_doller_count' => $updateddollaramt
            ];

            $tourquotationsModel->set($data1)->where('tour_date', $date)->where('quotation_id', $tour)->update();

            // if($date == $get_date){
            //   // Delete account_runing data for acc_id
            //   $accrunningmodel->where('ID', $acc_id)->delete();
            // }
          }
        }
        $data2 = ['RCType' => $status];

        // Delete voucher
        if ($vouchermodel->update($where, $data)) {

          $VoucherInvoiceModel->set($data2)->where('V_tkn', $v_inv_no)->update();
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
        'Dt' => $today . ' ' . date("H:i:s"),
        'Fee' => '',
        'Effective_Date' => '',
        'Card_No' => $card_no,
        'Recpt_No' => $rect_no,
        'payee' => $payee,
        'advances_pay' => $advance_pay,
        // 'Invoice_Num'=>$inv
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
        'Dt' => $today . ' ' . date("H:i:s"),
        'Fee' => '',
        'Effective_Date' => '',
        'Card_No' => $card_no,
        'Recpt_No' => $rect_no,
        'payee' => $payee,
        'advances_pay' => $advance_pay,
        // 'Invoice_Num'=>$inv
      )
    );

    // $output = $this->Payment_model->accountFinish($acc);
    $accrunningmodel = new AccountRunningModel();
    $driverallowmodel = new DriverAllocationModel();

    // log_message('critical', 'Acc running entries - {query}', [
    //     'query' => $accrunningmodel->getLastQuery(),
    //   ]);

    if ($accrunningmodel->insertBatch($acc)) {

      $tourquotationsModel = new TourQuotationsModel();

      $data2 = [
        'hotel_payment_flag' => 1,

      ];

      $tourquotationsModel->set($data2)->where('quotation_id', $chk_no)->where('hotel_acc', $Dr)->update();


      $congineeModel = new ConsigneeModel();
      if ($advance_pay == 1) {
        $congineeModel->set('transport_advance', 1)->where('tour_id', $chk_no)->update();
      }

      if ($advance_pay == 11) {
        $congineeModel->set('transport_settled', 1)->where('tour_id', $chk_no)->update();
        $driverallowmodel->set('payment_settlement', 1)->where('tour_id', $chk_no)->where('driver_name', $payee)->update();
      }

      return "success";
    } else {
      // log_message('critical', 'Acc running entries insert failed - {query}', [
      //   'query' => $accrunningmodel->getLastQuery(),
      // ]);
      return "error";
    }
  }



  public function voucher_cancel()
  {
    $data = array();

    // Load menu data using the custom library
    $menuLoader = new MenuLoader();
    $data = $menuLoader->loadMenuData();
    $VoucherPaymentModel = new VoucherPaymentModel();

    $result = $VoucherPaymentModel->select('voucher.C_Number, consignee.consignee, consignee.agent_name, consignee.tour_id,consignee.manual_id');

    $result->join('consignee', 'consignee.tour_id = voucher.C_Number',);
    $result->where('voucher.Aflag', 0);
    $result->groupBy('voucher.C_Number');


    $data['payments'] = $result->findAll();
    $voucherpaymentmodel  = new VoucherPaymentModel();

    foreach ($data['payments'] as &$payment) {
      $tour_id = $payment['tour_id'];

      $income = $voucherpaymentmodel->select('SUM(Amount) as tot_amount,COUNT(ID) as v_count,MAX(W_Date) as W_Date')
        ->where('C_Number', $tour_id)
        ->where('voucher.Aflag', 0)
        // ->where('account_runing.L_Code', '5-7-7-10388')
        ->first();


      $payment['tot_amount'] = $income['tot_amount'] ?? 0;
      $payment['v_count'] = $income['v_count'] ?? 0;
      $payment['W_Date'] = $income['W_Date'] ?? 0;
    }

    $data['other_vcount'] =  $voucherpaymentmodel->select('count(ID) as other_count,SUM(Amount) as g_tot')->where('Aflag', 0)->where('is_expenses !=', 1)->first();



    return view('pages/accounts/pending_vouchers', $data);
  }


  public function voucher_reject()
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
    $data['voucher_request'] =  $voucherpaymentmodel->load_vouchers_pending($tour_id);

    return view('pages/accounts/pending_voucher_reject', $data);
  }
}
