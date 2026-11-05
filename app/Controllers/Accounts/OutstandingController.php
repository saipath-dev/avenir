<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountRunningModel;
use App\Models\CustomerModel;
// use App\Models\SupplierModel;
//get customer model
// use App\Models\CustomerModel;

class OutstandingController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/accounts/liability
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $date1 = $this->request->getGet('date1');
        $date2 = $this->request->getGet('date2');
        $customer = $this->request->getGet('customer');
        // $tour = $this->request->getGet('tour');

        $data['date1'] = $date1 ? date('Y-m-d', strtotime($date1)) : date('Y-m-d');
        $data['date2'] = $date2 ? date('Y-m-d', strtotime($date2)) : date('Y-m-d');

        $data['is_search'] = false;
        $data['in_detail'] = false;
        if (!empty($date1) && !empty($date2)) {

            $data['is_search'] = true;
            $data['in_detail'] = false;

            if (!empty($customer)) {
                $data['in_detail'] = true;

                // customer records for the selected date range
                $accountRunningModel = new AccountRunningModel();
                $data['records'] = $accountRunningModel
                    ->select('SUM(Cr) as cr, SUM(Dr) as dr, account_runing.index_no,account_runing.Invoice_Num, customers.Name as customerName')
                    ->join('customers', 'customers.Sup_Acc_No = account_runing.L_Code')
                    ->where('account_runing.L_Code', $customer)
                    ->where('account_runing.Date >=', $date1)
                    ->where('account_runing.Date <=', $date2)
                    ->groupBy(['account_runing.L_Code', 'customers.Name', 'account_runing.index_no','account_runing.Invoice_Num'])
                    ->findAll();

                // log_message('critical', $accountRunningModel->getLastQuery());
            } else {
                // all customer records for the selected date range
                $customerModel = new CustomerModel();
                $data['customers'] = $customerModel->orderBy('Name', 'ASC')->findAll();

                $accountRunningModel = new AccountRunningModel();
                $data['records'] = $accountRunningModel
                    ->select('SUM(account_runing.Dr)- (account_runing.Cr) as bal,SUM(Cr) as cr, SUM(Dr) as dr,account_runing.L_Code, customers.Name as customerName')
                    ->join('customers', 'customers.Sup_Acc_No = account_runing.L_Code')
                    ->like('account_runing.L_Code', '1-2-5', 'after')
                    ->where('account_runing.Date >=', $date1)
                    ->where('account_runing.Date <=', $date2)
                    ->groupBy(['account_runing.L_Code', 'customers.Name'])
                    ->findAll();
                    // log_message('critical', $accountRunningModel->getLastQuery());
            }
        }



        return view('pages/accounts/outstanding_report', $data);
    }
}
