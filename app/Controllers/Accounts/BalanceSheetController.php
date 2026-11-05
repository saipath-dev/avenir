<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountRunningQ2Model;

class BalanceSheetController extends BaseController
{
	protected $AccountRunningQ2Model;

	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
		$this->AccountRunningQ2Model = new AccountRunningQ2Model();
	}

	// route : base_url/accounts/balance_sheet
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$date1 = $this->request->getGet('date1');
		$date2 = $this->request->getGet('date2');

		$data['is_search'] = false;

		if (!empty($date1) && !empty($date2)) {

			$data['is_search'] = true;

			$data['date1'] = $date1;
			$data['date2'] = $date2;

			$data['load_data'] = $this->load_data($date1, $date2);
			$data['income'] = $this->load_income($date1, $date2);
			$data['expenses'] = $this->load_expenses($date1, $date2);
			$data['cost_of_sale'] = $this->load_cost_of_sale($date1, $date2);
			$data['other_expenses'] = $this->load_other_expenses($date1, $date2);
			$data['other_income'] = $this->load_other_income($date1, $date2);

			$t = 0;
			foreach($data['load_data'] as $key => $value) {
				if ($value['HName'] == 'Deposits in Transit') {
					$t += $value['bal'];
				}
			}
			
			// var_dump($t);
			
			// 		die();
		}

		// var_dump($data['load_data']);
		// die();

		return view('pages/accounts/balance_sheet', $data);
	}

	public function load_data($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, TName, HName, Dr, Cr, SUM(bal) AS bal')
			->groupBy('BName, TName, HName')
			->where("date(Date) BETWEEN '$date1' AND '$date2'")
			->findAll();

		// log_message('critical', $this->AccountRunningQ2Model->getLastQuery());

		return $result != null ? $result : null;
	}

	public function load_income($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, SUM(bal) AS bal')
			->groupBy('BName')
			->where("date(Date) BETWEEN '$date1' AND '$date2'")
			->where('BName', 'Income')
			->findAll();

		return $result != null ? $result[0] : array('BName' => 'Income', 'bal' => '0.00');
	}

	public function load_expenses($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, SUM(bal) AS bal')
			->groupBy('BName')
			->where("date(Date) BETWEEN '$date1' AND '$date2'")
			->where('BName', 'Expenses')
			->findAll();

		return $result != null ? $result[0] : null;
	}

	public function load_cost_of_sale($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, SUM(bal) AS bal')
			->groupBy('BName')
			->where("date(Date) BETWEEN '$date1' AND '$date2'")
			->where('BName', 'Cost Of Sale')
			->findAll();

		return $result != null ? $result[0] : null;
	}

	public function load_other_expenses($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, SUM(bal) AS bal')
			->groupBy('BName')
			->where("date(Date) BETWEEN '$date1' AND '$date2'")
			->where('BName', 'Other Expenses')
			->findAll();

		return $result != null ? $result[0] : null;
	}

	public function load_other_income($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, SUM(bal) AS bal')
			->groupBy('BName')
			->where("date(Date) BETWEEN '$date1' AND '$date2'")
			->where('BName', 'Other Income')
			->findAll();

		return $result != null ? $result[0] : null;
	}
}
