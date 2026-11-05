<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\VoucherPaymentModel;
use App\Models\AccountPostingModel;
use App\Models\UserModel;
use App\Filters\StripTagsFilter;
use App\Models\InvoiceNumberModel;
use App\Models\VoucherInvoiceModel;
use App\Models\AccountRunningModel;
use App\Models\StaffDivisionModel;
use App\Models\InvDescriptionModel;
use App\Models\EntertainmentExpensModel;
use App\Models\EntertainmentExpensDetailsModel;

class UtilityBillEntertainmentController extends BaseController
{
  protected $VoucherPaymentModel;

  public function __construct()
  {
    date_default_timezone_set('Asia/Colombo');
  }

  public function index()
  {
    $data = array();

    $menuLoader = new MenuLoader();
    $data = $menuLoader->loadMenuData();

    $usermodel = new UserModel();
    $data['req_user'] = $usermodel->select('acc_no,user,emp_id')
      ->findAll();

    $paidtomodel = new EntertainmentExpensModel();
    $data['paidto'] = $paidtomodel->select('card_name')->findAll();

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

    $invdescriptionmodel = new InvDescriptionModel();
    $data['description'] = $invdescriptionmodel
      ->where('del', 0)
      ->findAll();

      $entermodel = new EntertainmentExpensModel();
      $data['entertainment'] = $entermodel
        ->select('entertainment_expenses_details.id, entertainment_expenses_details.Date, entertainment_expenses_details.Description, entertainment_expenses_details.Amount, entertainment_expenses_details.invoice_img_path as document_path, entertainment_expenses.card_name as PaidBy, entertainment_expenses_details.ref_no, entertainment_expenses_details.currency')
        ->join('entertainment_expenses_details', 'entertainment_expenses.ref_no = entertainment_expenses_details.ref_no')
        ->where('entertainment_expenses.flag', 0)
        ->where('entertainment_expenses.user', session()->get('user_id'))
        ->orderBy('entertainment_expenses_details.Date', 'DESC')
        ->findAll();

    return view('pages/accounts/utility_bill_entertainment_expenses', $data);
  }

  /********************@@ GET ***********************/
  public function getSupplierDetails()
  {
    $segment4 = $this->request->getGet('l_code');

    $vouchermode = new VoucherPaymentModel();

    $data = $vouchermode->getSupplierDetails($segment4);

    echo json_encode($data);
  }

  /******************** GET PREVIOUS ENTERTAINMENT EXPENSES ***********************/
  public function getPreviousEntertainmentExpenses()
  {
    $user_id = $this->request->getGet('user_id') ?? session()->get('user_id');
    $limit = $this->request->getGet('limit') ?? 10;

    $entermodel = new EntertainmentExpensModel();
    $expenses = $entermodel->select('entertainment_expenses.*, entertainment_expenses_details.*')
      ->join('entertainment_expenses_details', 'entertainment_expenses.ref_no = entertainment_expenses_details.ref_no')
      ->where('entertainment_expenses.flag', 0)
      ->where('entertainment_expenses.user', $user_id)
      ->orderBy('entertainment_expenses_details.Date', 'DESC')
      ->limit($limit)
      ->findAll();

    echo json_encode($expenses);
  }

  /******************** SAVE ***********************/
  public function save($id = null)
  {
    try {

      $itemArray = $this->request->getVar('itemArray');
      $grnd_tot = $this->request->getVar('grnd_tot');

      $bank = $this->request->getVar('bank');
      $bank_name = $this->request->getVar('bank_name');
      $p_method = $this->request->getVar('p_method');


      if (!$itemArray) {
        return $this->response->setJSON(["status" => 500, "message" => "Please fill all required fields"]);
      }

      $accountRunningModel = new AccountRunningModel();
      $EntExpenseModel = new EntertainmentExpensModel();
      $EntExpenseDetailsModel = new EntertainmentExpensDetailsModel();

      $itemArray = is_array($itemArray) ? array_unique($itemArray, SORT_REGULAR) : [];

      if (is_array($itemArray)) {
        $itemArray = array_unique($itemArray, SORT_REGULAR);
      } else {
        $itemArray = [];
      }

      $invoiceNoModel = new InvoiceNumberModel();
      $ref_no = $invoiceNoModel->incrementEntertainmentExpensesInv();

      foreach ($itemArray as $key => $arr) {
        $date = $arr['date'];
        $descrip = $arr['desc'];
        $amount = $arr['amount'];
        $currency = $arr['currency'];
        $document_path = isset($arr['document_path']) ? $arr['document_path'] : null; // Get document path


        $EntExpenseData = [
          'Date' => $date,
          'EntryDate' => date('Y-m-d'),
          'Description' => $descrip,
          'Amount' => $amount,
          'currency' => $currency,
          'invoice_img_path' => $document_path, // Save document path
          'ref_no' => $ref_no,
          'user' => session()->get('user_id'),
          'username' => session()->get('username')
        ];

        $insertResult = $EntExpenseDetailsModel->insert($EntExpenseData);
      }

      if ($insertResult) {

        $EntExpenseMainData = [
          'grand_total' => $grnd_tot,
          'card_acc' => $bank,
          'card_name' => $bank_name,
          'ref_no' => $ref_no,
          'user' => session()->get('user_id'),
          'username' => session()->get('username'),
          'payment_method' => $p_method
        ];

        $EntExpenseModel->insert($EntExpenseMainData);

        return $this->response->setJSON([
          "status" => 200,
          "message" => "Invoice saved successfully!"
        ]);
      } else {
        return $this->response->setJSON(["status" => 500, "message" => "Failed to save invoice!"]);
      }
    } catch (\Exception $e) {
      // Handle exceptions
      return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
    }
  }

  /******************** GET ***********************/
  public function report()
  {
    $menuLoader = new MenuLoader();
    $data = $menuLoader->loadMenuData();

      $entermodel = new EntertainmentExpensModel();
      $data['entertainment'] = $entermodel->select('*')->join('entertainment_expenses_details', 'entertainment_expenses.ref_no = entertainment_expenses_details.ref_no')->where('entertainment_expenses.flag', 0)->findAll();
    
    return view('pages/accounts/entertainment_expenses_report', $data);
  }


  /******************** UPLOAD INVOICE ***********************/
  public function upload_invoice()
  {
    try {
      $file = $this->request->getFile('invoice_file');

      if (!$file->isValid()) {
        return json_encode([
          'status' => 500,
          'message' => 'No file uploaded or invalid file'
        ]);
      }

      $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
      if (!in_array($file->getClientMimeType(), $allowedTypes)) {
        return json_encode([
          'status' => 500,
          'message' => 'Invalid file type. Only JPG, PNG, and PDF files are allowed.'
        ]);
      }

      if ($file->getSize() > 5242880) {
        return json_encode([
          'status' => 500,
          'message' => 'File size exceeds limit. Maximum 5MB allowed.'
        ]);
      }

      $uploadPath = 'uploads/Entertainment_Expenses/';
      $directory = FCPATH . $uploadPath;

      if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
      }

      $newName = uniqid() . '_' . $file->getClientName();

      if ($file->move($directory, $newName)) {
        return json_encode([
          'status' => 200,
          'message' => 'File uploaded successfully',
          'file_path' => $uploadPath . $newName,
          'file_name' => $file->getClientName()
        ]);
      } else {
        return json_encode([
          'status' => 500,
          'message' => 'Failed to move uploaded file'
        ]);
      }
    } catch (\Exception $e) {
      return json_encode([
        'status' => 500,
        'message' => $e->getMessage()
      ]);
    }
  }

  /******************** UPDATE EXPENSE ***********************/
  public function update_expense()
  {
    try {
      $expense_id = $this->request->getPost('expense_id');
      $date = $this->request->getPost('date');
      $description = $this->request->getPost('description');
      $amount = $this->request->getPost('amount');
      $currency = $this->request->getPost('currency');
      $paidby = $this->request->getPost('paidby');

      if (!$expense_id || !$date || !$description || !$amount || !$paidby || !$currency) {
        return $this->response->setJSON([
          'status' => 500,
          'message' => 'All fields are required'
        ]);
      }

      $EntExpenseDetailsModel = new EntertainmentExpensDetailsModel();

      // Get current expense details
      $currentExpense = $EntExpenseDetailsModel->find($expense_id);

      if (!$currentExpense) {
        return $this->response->setJSON([
          'status' => 500,
          'message' => 'Expense not found'
        ]);
      }

      // Prepare update data
      $updateData = [
        'Date' => $date,
        'Description' => $description,
        'Amount' => $amount,
        'currency' => $currency,
      ];

      // Handle file upload if provided
      $file = $this->request->getFile('invoice_file');
      if ($file && $file->isValid()) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

        if (!in_array($file->getClientMimeType(), $allowedTypes)) {
          return $this->response->setJSON([
            'status' => 500,
            'message' => 'Invalid file type. Only JPG, PNG, and PDF files are allowed.'
          ]);
        }

        if ($file->getSize() > 5242880) {
          return $this->response->setJSON([
            'status' => 500,
            'message' => 'File size exceeds limit. Maximum 5MB allowed.'
          ]);
        }

        $uploadPath = 'uploads/Entertainment_Expenses/';
        $directory = FCPATH . $uploadPath;

        if (!is_dir($directory)) {
          mkdir($directory, 0777, true);
        }

        $newName = uniqid() . '_' . $file->getClientName();

        if ($file->move($directory, $newName)) {
          // Delete old file if exists
          if (!empty($currentExpense['invoice_img_path'])) {
            $oldFile = FCPATH . $currentExpense['invoice_img_path'];
            if (file_exists($oldFile)) {
              unlink($oldFile);
            }
          }

          $updateData['invoice_img_path'] = $uploadPath . $newName;
        }
      }

      // Update expense details
      $EntExpenseDetailsModel->update($expense_id, $updateData);

      // Update main expense table with paid by
      $EntExpenseModel = new EntertainmentExpensModel();
      $ref_no = $currentExpense['ref_no'];
      $EntExpenseModel->where('ref_no', $ref_no)->set(['card_name' => $paidby])->update();

      return $this->response->setJSON([
        'status' => 200,
        'message' => 'Expense updated successfully'
      ]);

    } catch (\Exception $e) {
      return $this->response->setJSON([
        'status' => 500,
        'message' => $e->getMessage()
      ]);
    }
  }

  /******************** DELETE EXPENSE ***********************/
  public function delete_expense()
  {
    try {
      $expense_id = $this->request->getPost('expense_id');

      if (!$expense_id) {
        return $this->response->setJSON([
          'status' => 500,
          'message' => 'Expense ID is required'
        ]);
      }

      $EntExpenseDetailsModel = new EntertainmentExpensDetailsModel();

      // Get expense details to delete file
      $expense = $EntExpenseDetailsModel->find($expense_id);

      if (!$expense) {
        return $this->response->setJSON([
          'status' => 500,
          'message' => 'Expense not found'
        ]);
      }

      // Delete file if exists
      if (!empty($expense['invoice_img_path'])) {
        $filePath = FCPATH . $expense['invoice_img_path'];
        if (file_exists($filePath)) {
          unlink($filePath);
        }
      }

      // Delete the expense record
      $EntExpenseDetailsModel->delete($expense_id);

      // Check if there are any more details for this ref_no
      $ref_no = $expense['ref_no'];
      $remainingDetails = $EntExpenseDetailsModel->where('ref_no', $ref_no)->countAllResults();

      // If no more details, delete the main expense record
      if ($remainingDetails == 0) {
        $EntExpenseModel = new EntertainmentExpensModel();
        $EntExpenseModel->where('ref_no', $ref_no)->delete();
      }

      return $this->response->setJSON([
        'status' => 200,
        'message' => 'Expense deleted successfully'
      ]);

    } catch (\Exception $e) {
      return $this->response->setJSON([
        'status' => 500,
        'message' => $e->getMessage()
      ]);
    }
  }
}
