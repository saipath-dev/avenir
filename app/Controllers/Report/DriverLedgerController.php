<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\SupplierModel;
use App\Models\AccountRunningModel;
use App\Models\ConsigneeModel;

class DriverLedgerController extends BaseController
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
		
		$supplierModel = new SupplierModel();
		
		// Load drivers (suppliers with sup_cat = 2 for Transport Agency)
		$data['drivers'] = $supplierModel->select('Sup_Acc_No, Name')
			->where('sup_cat', 2)
			->findAll();

		$startdate = $this->request->getGet('startdate');
		$data['date1'] = $startdate;

		$enddate = $this->request->getGet('enddate');
		$data['date2'] = $enddate;

		$data['is_search'] = false;
		$data['resultlist'] = [];

		return view('pages/report/driver_ledger', $data);
	}

	public function driver_details()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$supplierModel = new SupplierModel();
		
		// Load drivers for dropdown (suppliers with sup_cat = 2 for Transport Agency)
		$data['drivers'] = $supplierModel->select('Sup_Acc_No, Name')
			->where('sup_cat', 2)
			->findAll();

		$driver_acc = $this->request->getGet('driver');
		$data['driver'] = $driver_acc;

		$startdate = $this->request->getGet('startdate');
		$data['date1'] = $startdate;

		$enddate = $this->request->getGet('enddate');
		$data['date2'] = $enddate;

		$data['is_search'] = false;
		$data['resultlist'] = [];
		$data['driver_info'] = null;

		if (!empty($driver_acc)) {
			$data['is_search'] = true;

			// Get driver information
			$data['driver_info'] = $supplierModel->select('Sup_Acc_No, Name, Address, Mobile, TP')
				->where('Sup_Acc_No', $driver_acc)
				->where('sup_cat', 2)
				->first();

			$accountRunningModel = new AccountRunningModel();
			
			$result = $accountRunningModel->select('account_runing.ID, account_runing.L_Code, account_runing.Dr, account_runing.Cr, account_runing.Discription, account_runing.Date, account_runing.index_no, supplier.Name as driver_name, consignee.manual_id')
				->join('supplier', 'account_runing.L_Code = supplier.Sup_Acc_No', 'left')
				->join('consignee', 'account_runing.index_no = consignee.tour_id', 'left')
				->where('account_runing.L_Code', $driver_acc)
				->where('supplier.sup_cat', 2);

			// Add date range filter if provided
			if (!empty($startdate) && !empty($enddate)) {
				$result->where("account_runing.Date >=", $startdate)
					->where("account_runing.Date <=", $enddate);
				$data['selected_startdate'] = $startdate;
				$data['selected_enddate'] = $enddate;
			}

			$result->orderBy('account_runing.Date', 'ASC')
				->orderBy('account_runing.ID', 'ASC');

			$data['resultlist'] = $result->findAll();
			$data['selected_driver'] = $driver_acc;
		}

		return view('pages/report/driver_ledger', $data);
	}
}