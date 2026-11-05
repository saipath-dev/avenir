<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
// use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\InvoiceNumberModel;
use App\Models\SupplierModel;
// use App\Models\CustomerModel;
use App\Models\ConsigneeModel;
// use App\Models\VoucherInvoiceModel;
use App\Models\VoucherPaymentModel;

class VoucherAuditController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/accounts/debtor_receipt
    public function index()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountRunningModel = new AccountRunningModel();
        $VoucherPaymentModel = new VoucherPaymentModel();

        // load auctions
        $supplierModel = new SupplierModel();
        $data['supplier'] = $supplierModel->findAll();

        $result = $VoucherPaymentModel->select('voucher.C_Number, consignee.consignee, consignee.agent_name, consignee.tour_id,consignee.manual_id');

        $result->join('consignee', 'consignee.tour_id = voucher.C_Number',);
        $result->where('voucher.Aflag', 1);
        $result->where('voucher.is_confirm', 0);
        $result->groupBy('voucher.C_Number');


        $data['payments'] = $result->findAll();
        // log_message('critical', $accountRunningModel->getLastQuery());

        $voucherpaymentmodel  = new VoucherPaymentModel();

        foreach ($data['payments'] as &$payment) {
            $tour_id = $payment['tour_id'];

            $income = $voucherpaymentmodel->select('SUM(Amount) as tot_amount,COUNT(ID) as v_count,MAX(W_Date) as W_Date')
            ->where('C_Number', $tour_id)
            ->where('voucher.Aflag', 1)
            ->where('voucher.is_confirm', 0)
                // ->where('account_runing.L_Code', '5-7-7-10388')
            ->first();


            $payment['tot_amount'] = $income['tot_amount'] ?? 0;
            $payment['v_count'] = $income['v_count'] ?? 0;
            $payment['W_Date'] = $income['W_Date'] ?? 0;
        }

        $data['other_vcount'] =  $voucherpaymentmodel->select('count(ID) as other_count,SUM(Amount) as g_total')->where('Aflag',1)->where('is_confirm',0)->where('is_expenses !=',1)->first();
        // log_message('critical', $accountRunningModel->getLastQuery());
        // }

        return view('pages/accounts/voucher_audit_details', $data);
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
        $data['voucher_request'] =  $voucherpaymentmodel->confirmed_vouchers($tour_id);

        return view('pages/accounts/voucher_audit', $data);
    }


    public function update()
    {
    // Load the model
        $voucherpaymentmodel = new VoucherPaymentModel();

    // Attempt to update records where Aflag = '1'
        try {
        // Perform the update
           $affectedRows = $voucherpaymentmodel->where('Aflag', '1')
           ->set([
            'is_confirm' => 1,
            'confirm_user' => session()->get('username'),
        'confirm_date' => date('Y-m-d H:i:s') // Correctly using PHP's date() function
    ])
           ->update();


        // Check if any rows were updated
           if ($affectedRows > 0) {
            // If successful, return a success message
            return json_encode(['status' => 200, 'message' => 'Debit note saved successfully']);
        } else {
            // If no rows were updated, return a different message
            return json_encode(['status' => 404, 'message' => 'No records found to update']);
        }
    } catch (\Exception $e) {
        // Catch any exceptions and return an error message
        return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

public function update_tour()
{
    // Load the model

    $tour_id = strip_tags($this->request->getVar('tour_id'));
    $voucherpaymentmodel = new VoucherPaymentModel();


    
    // Attempt to update records where Aflag = '1'
    try {

      if($tour_id=='other'){
                // Perform the update
          $affectedRows = $voucherpaymentmodel->where('Aflag', '1')
          ->where('is_expenses!=', 1)
          ->set([
            'is_confirm' => 1,
            'confirm_user' => session()->get('username'),
        'confirm_date' => date('Y-m-d H:i:s') // Correctly set the date and time
    ])
          ->update();

      }else{
                // Perform the update
          $affectedRows = $voucherpaymentmodel->where('Aflag', '1')
          ->where('ref_po', $tour_id)
          ->set([
            'is_confirm' => 1,
            'confirm_user' => session()->get('username'),
        'confirm_date' => date('Y-m-d H:i:s') // Correctly set the date and time
    ])
          ->update();

      }



        // Check if any rows were updated
      if ($affectedRows > 0) {
            // If successful, return a success message
        return json_encode(['status' => 200, 'message' => 'Debit note saved successfully']);
    } else {
            // If no rows were updated, return a different message
        return json_encode(['status' => 404, 'message' => 'No records found to update']);
    }
} catch (\Exception $e) {
        // Catch any exceptions and return an error message
    return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
}
}




}
