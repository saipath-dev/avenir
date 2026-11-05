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
use App\Models\UtilityBillModel;
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
      // Liability account (B_Code=6)
      $data = $accselectmodel->Where('B_Code', 6)
        ->groupStart()
        ->orLike('PName', $query)
        ->orLike('TName', $query)
        ->orLike('HName', $query)
        ->orLike('BName', $query)
        ->groupEnd()
        ->findAll();

    } elseif ($type == 2) {
      // Expenses account (B_Code=4 or 1)
      $data1 = $accselectmodel->Where('B_Code', 4)
        ->groupStart()
        ->orLike('PName', $query)
        ->orLike('TName', $query)
        ->orLike('HName', $query)
        ->orLike('BName', $query)
        ->groupEnd()
        ->findAll();

      $data2 = $accselectmodel->Where('B_Code', 1)
        ->groupStart()
        ->orLike('PName', $query)
        ->orLike('TName', $query)
        ->orLike('HName', $query)
        ->orLike('BName', $query)
        ->groupEnd()
        ->findAll();

      $data = array_merge($data1, $data2);

    } else {
      // type=0 — all accounts (for special payment / petty cash, same as voucher_payments)
      $data = $accselectmodel
        ->groupStart()
        ->orLike('L_Code', $query)
        ->orLike('PName', $query)
        ->orLike('TName', $query)
        ->orLike('HName', $query)
        ->orLike('BName', $query)
        ->groupEnd()
        ->findAll();
    }

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
      $itemArray          = $this->request->getVar('itemArray');
      $payment_type       = $this->request->getVar('payment_type') ?? 'utility_bill';
      $acc_liability      = $this->request->getVar('acc_liability');
      $acc_name_liability = $this->request->getVar('acc_name_liability');
      $acc_expenses       = $this->request->getVar('acc_expenses');
      $acc_name_expenses  = $this->request->getVar('acc_name_expenses');

      if (!$itemArray || !$acc_liability || !$acc_expenses) {
        return $this->response->setJSON(["status" => 500, "message" => "Please fill all required fields"]);
      }

      $itemArray = is_array($itemArray) ? array_unique($itemArray, SORT_REGULAR) : [];

      $utilityBillModel = new UtilityBillModel();
      $rows = [];

      foreach ($itemArray as $arr) {
        $rows[] = [
          'payment_type'       => $payment_type,
          'acc_liability'      => $acc_liability,
          'acc_name_liability' => $acc_name_liability,
          'acc_expenses'       => $acc_expenses,
          'acc_name_expenses'  => $acc_name_expenses,
          'date'               => $arr['date'],
          'inv_no'             => $arr['inv_no'],
          'description'        => $arr['desc'],
          'units'              => $arr['units'],
          'rate'               => $arr['rate'],
          'amount'             => $arr['amount'],
          'cashier'            => session()->get('username'),
          'created_at'         => date('Y-m-d H:i:s'),
          'status'             => 0,
        ];
      }

      $utilityBillModel->insertBatch($rows);

      return $this->response->setJSON(["status" => 200, "message" => "Invoice saved successfully!"]);
    } catch (\Exception $e) {
      return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
    }
  }

  public function update_status()
  {
    try {
      $id        = $this->request->getVar('id');
      $status    = (int) $this->request->getVar('status');
      $bank      = strip_tags((string) $this->request->getVar('bank'));
      $bank_name = strip_tags((string) $this->request->getVar('bank_name'));

      if (!$id || !in_array($status, [1, 2, 3, 4, 5])) {
        return $this->response->setJSON(["status" => 500, "message" => "Invalid request"]);
      }

      $utilityBillModel = new UtilityBillModel();
      $bill = $utilityBillModel->find($id);

      if (!$bill) {
        return $this->response->setJSON(["status" => 500, "message" => "Record not found"]);
      }

      $updateData = [];

      if ($status === 1) {
        $is_petty_cash = ($bill['payment_type'] === 'petty_cash');
        $p_method = $is_petty_cash ? 'Petty Cash' : 'Bank Transfer';

        if ($is_petty_cash) {
          $bank = '';
          $bank_name = '';
        } elseif (!$bank) {
          return $this->response->setJSON(["status" => 500, "message" => "Bank account is required to accept this payment"]);
        }

        $updateData['status']      = $status;
        $updateData['accepted_by'] = session()->get('username');
        $updateData['accepted_at'] = date('Y-m-d H:i:s');

        $invoiceNoModel = new InvoiceNumberModel();
        $acc_id = $invoiceNoModel->incrementAccId();

        $accountRunningModel = new AccountRunningModel();
        $accountRunningModel->insertBatch([
          [
            'ID'          => $acc_id,
            'L_Code'      => $bill['acc_expenses'],
            'Date'        => $bill['date'],
            'Discription' => $bill['description'] . ' - ' . $bill['inv_no'] . ' Utility Bill Payment',
            'Dr'          => $bill['amount'],
            'Cr'          => 0.00,
            'Invoice_Num' => $bill['inv_no'],
            'Linkacc'     => $bill['acc_expenses'],
            'index_no'    => '',
            'dollarrate'  => $bill['rate'],
            'dollar'      => $bill['units'],
            'job'         => '',
            'fine_date'   => date('Y-m-d'),
            'Cashier'     => session()->get('username'),
            'Dt'          => date('Y-m-d H:i:s'),
          ],
          [
            'ID'          => $acc_id,
            'L_Code'      => $bill['acc_liability'],
            'Date'        => $bill['date'],
            'Discription' => $bill['description'] . ' - ' . $bill['inv_no'] . ' Utility Bill Payment',
            'Dr'          => 0.00,
            'Cr'          => $bill['amount'],
            'Invoice_Num' => $bill['inv_no'],
            'Linkacc'     => $bill['acc_liability'],
            'index_no'    => '',
            'dollarrate'  => $bill['rate'],
            'dollar'      => $bill['units'],
            'job'         => '',
            'fine_date'   => date('Y-m-d'),
            'Cashier'     => session()->get('username'),
            'Dt'          => date('Y-m-d H:i:s'),
          ],
        ]);

        $v_num = $this->createUtilityVoucher($bill, $bill['inv_no'], $p_method, $bank, $bank_name);
        $updateData['voucher_token'] = $v_num;
        $updateData['pay_status']    = 3;
        $updateData['pay_status_by'] = session()->get('username');
        $updateData['pay_status_at'] = date('Y-m-d H:i:s');

      } elseif ($status === 2) {
        $updateData['status']      = $status;
        $updateData['rejected_by'] = session()->get('username');
        $updateData['rejected_at'] = date('Y-m-d H:i:s');

      } elseif (in_array($status, [3, 4, 5])) {
        // 3=Paid, 4=Complete, 5=Cancelled — stored in pay_status, not status
        $updateData['pay_status']    = $status;
        $updateData['pay_status_by'] = session()->get('username');
        $updateData['pay_status_at'] = date('Y-m-d H:i:s');
      }

      $utilityBillModel->update($id, $updateData);

      return $this->response->setJSON(["status" => 200, "message" => "Updated successfully"]);
    } catch (\Exception $e) {
      return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
    }
  }

  /**
   * Create a voucher + v_inv record for a utility bill.
   * Returns the voucher token (Vnum).
   */
  private function createUtilityVoucher($bill, $ref_no, $p_method, $bank, $bank_name, $v_type = 0)
  {
    $is_special = ($bill['payment_type'] === 'special_payment') ? 1 : 0;

    $invoiceNoModel = new InvoiceNumberModel();
    $v_num = $invoiceNoModel->incrementVoucherToken();

    $voucherData = [
      'Pay'        => $bill['acc_name_liability'],
      'emp_code'   => $bill['acc_liability'],
      'S_name'     => $bill['acc_name_liability'],
      'Acc_No'     => $bill['acc_liability'],
      'sup'        => $v_type,
      'type'       => $p_method,
      'Amount'     => $bill['amount'],
      'VAT'        => '0',
      'VAT_Amt'    => '0',
      'vat_m'      => '0',
      'nbt'        => '0',
      'nbt_amt'    => '0',
      'nbt_m'      => '0',
      'g_ttl'      => $bill['amount'],
      'Address'    => '0',
      'print'      => '0',
      'des'        => $bill['description'] . ' - ' . $bill['inv_no'] . ' Utility Bill Payment',
      'W_Date'     => $bill['date'],
      'dv'         => 1,
      'app_user'   => $bill['cashier'],
      'login'      => session()->get('username'),
      'Aflag'      => '0',
      'v_typ'      => $p_method,
      'ref_po'     => $ref_no,
      'insflag'    => 1,
      'inv'        => $bill['inv_no'],
      'Vnum'       => $v_num,
      'Bank'       => $bank_name,
      'Bank_acc'   => $bank,
      'C_Date'     => date('Y-m-d H:i:s'),
      'C_Number'   => $bill['inv_no'],
      'is_special' => $is_special,
    ];

    $voucherPaymentModel = new VoucherPaymentModel();
    $voucherPaymentModel->insert($voucherData);

    $voucherInvoiceData = [
      'Date'        => $bill['date'],
      'Invoice'     => $bill['inv_no'],
      'Description' => $bill['description'] . ' (' . $bill['inv_no'] . ')',
      'Units'       => $bill['units'],
      'Rate'        => $bill['rate'],
      'Amount'      => $bill['amount'],
      'InvAmount'   => $bill['amount'],
      'account'     => $bill['acc_liability'],
      'V_tkn'       => $v_num,
      'ityp'        => '1',
      'link_code'   => $bill['acc_liability'],
      'employee'    => $bill['acc_name_liability'],
      'login'       => session()->get('username'),
      'INV_Date'    => $bill['date'],
      'Payee'       => $bill['acc_name_liability'],
      'RCType'      => 0,
    ];

    $voucherInvoiceModel = new VoucherInvoiceModel();
    $voucherInvoiceModel->insert($voucherInvoiceData);

    return $v_num;
  }

  public function pay()
  {
    try {
      $id        = $this->request->getVar('id');
      $v_type    = (int) $this->request->getVar('v_type');
      $ref_no    = strip_tags($this->request->getVar('ref_no'));
      $p_method  = strip_tags($this->request->getVar('p_method'));
      $bank      = strip_tags($this->request->getVar('bank'));
      $bank_name = strip_tags($this->request->getVar('bank_name'));
      $amount    = $this->request->getVar('amount');

      if (!$id || !$ref_no) {
        return $this->response->setJSON(["status" => 500, "message" => "Please fill all required fields"]);
      }

      $utilityBillModel = new UtilityBillModel();
      $bill = $utilityBillModel->find($id);

      if (!$bill) {
        return $this->response->setJSON(["status" => 500, "message" => "Record not found"]);
      }

      $is_petty_cash = ($bill['payment_type'] === 'petty_cash');

      if ($is_petty_cash) {
        $p_method  = 'Petty Cash';
        $bank      = '';
        $bank_name = '';
      } elseif (!$p_method || !$bank) {
        return $this->response->setJSON(["status" => 500, "message" => "Please fill all required fields"]);
      }

      $v_num = $this->createUtilityVoucher($bill, $ref_no, $p_method, $bank, $bank_name, $v_type);

      // Update utility_bill pay_status to Paid (3) and store voucher token
      $utilityBillModel->update($id, [
        'pay_status'    => 3,
        'pay_status_by' => session()->get('username'),
        'pay_status_at' => date('Y-m-d H:i:s'),
        'voucher_token' => $v_num,
      ]);

      return $this->response->setJSON(["status" => 200, "message" => "Payment voucher created successfully!"]);
    } catch (\Exception $e) {
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
      $id = $this->request->getPost('id');
      
      $accountRunningModel = new AccountRunningModel();
      $accountRunningModel->where('ID', $id)->delete();
      return $this->response->setJSON(["status" => 200, "message" => "Record deleted successfully!"]);
    } catch (\Exception $e) {
      return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
    }

  }
}
