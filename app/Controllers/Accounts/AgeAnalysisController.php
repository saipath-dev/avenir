<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\DebtorsqqModel;
use App\Models\Debtorsqq2Model;
use App\Models\SupplierqqModel;
use App\Models\Supplierqq2Model;
use App\Models\CustomerModel;

class AgeAnalysisController extends BaseController
{
  public function __construct()
  {
    date_default_timezone_set('Asia/Colombo');
  }





  public function index()
  {
    $data = array();

    $menuLoader = new MenuLoader();
    $data = $menuLoader->loadMenuData();

    $Debtorsqq2Model = new Debtorsqq2Model();

        // load auctions
    $CustomerModel = new CustomerModel();
    $data['customers'] = $CustomerModel->findAll();


    $cus = $this->request->getGet('cus');
    $data['cus'] = $cus;

    $data['is_search'] = false;

    if (!empty($cus)) {
      $data['is_search'] = true;



      $result = $Debtorsqq2Model->where('Bal>',0)->orderBy('FirstOfDate','ASC');

      if (!empty($cus)) {
       $result->where('L_Code', $cus);
     }
       log_message('critical',$Debtorsqq2Model->getLastQuery());

     $data['payments'] = $result->findAll();

   

   }else{
     $data['is_search'] = true;
     $result = $Debtorsqq2Model->where('Bal>',0)->orderBy('FirstOfDate','ASC')->findAll();
     $data['payments'] = $result;
     log_message('critical', $Debtorsqq2Model->getLastQuery());


   }

   return view('pages/accounts/age_analysis', $data);
 }



 public function creditor_analysis()
 {
  $data = array();

  $menuLoader = new MenuLoader();
  $data = $menuLoader->loadMenuData();

  $SupplierqqModel = new Supplierqq2Model();

        // load auctions
  // $CustomerModel = new CustomerModel();
  // $data['customers'] = $CustomerModel->findAll();


  // $cus = $this->request->getGet('cus');
  // $data['cus'] = $cus;

  $data['is_search'] = true;

  // if (!empty($cus)) {
  //   $data['is_search'] = true;

 

  $result = $SupplierqqModel->where('bal>',0)->orderBy('FirstOfDate','ASC');

   //  if (!empty($cus)) {
   //   $result->where('L_Code', $cus);
   // }

   $data['payments'] = $result->findAll();

 // }else{
 //   $data['is_search'] = true;
 //   $result = $DebtorsqqModel->where('bal>',0)->orderBy('FirstOfDate','ASC')->findAll();
 //   $data['payments'] = $result;

 // }

 return view('pages/accounts/creditor_age_analysis', $data);
}

}
