<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\InvoiceNumberModel;
use App\Models\SupplierModel;
use Exception;

class CashBookController extends BaseController
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


        $supplierModel = new SupplierModel();
        

        $accountPostingModel = new AccountPostingModel();
        $data['methods'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $from = $this->request->getGet('from');
        $to = $this->request->getGet('to');
        
        $data['is_search'] = false;
        if ($from != null || $to != null) {

            $data['is_search'] = true;
            $data['from'] = $from;
            $data['to'] = $to;

            $accountRunningModel = new AccountRunningModel();


            $query = $accountRunningModel->select('SUM(Dr) as Dr, SUM(Cr) as Cr, Invoice_Num, Discription, P_Month, Date')
            ->where('L_Code', '1-2-3-1000');


            if ($from != null) {
                $query->where('Date >=', $from);
            }


            if ($to != null) {
                $query->where('Date <=', $to);
            }

            $data['transactions'] = $query->findAll();

        }

        return view('pages/accounts/cash_book', $data);
    }



    public function petty_cash()
    {
        $data = array();

        
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        
        $supplierModel = new SupplierModel();
        
        $from = $this->request->getGet('from');
        $to = $this->request->getGet('to');
        
        $data['is_search'] = false;
        

        if ($from != null || $to != null) {

            $data['is_search'] = true;
            $data['from'] = $from;
            $data['to'] = $to;



            $accountRunningModel = new AccountRunningModel();


            $query = $accountRunningModel->select('SUM(Dr) as Dr, SUM(Cr) as Cr, Invoice_Num, Discription, P_Month, Date')
            ->where('L_Code', '1-2-4-1000');

            
            if ($from != null) {
                $query->where('Date >=', $from);
            }


            if ($to != null) {
                $query->where('Date <=', $to);
            }

            
            $data['transactions'] = $query->findAll();




        }

        return view('pages/accounts/petty_cash', $data);
    }


}
