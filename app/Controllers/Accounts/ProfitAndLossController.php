<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountRunningQ2Model;

class ProfitAndLossController extends BaseController
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

			// var_dump($data['load_data']); die();
		}

		return view('pages/accounts/profit_loss_report', $data);
	}

	public function load_data($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, TName, HName, SUM(Dr) as DR, SUM(Cr) as CR, SUM(bal) AS bal')
			->groupBy('BName, TName, HName')
			->where("date(Date) BETWEEN '$date1' AND '$date2'")
			->whereIn('BName', ['Income', 'Cost of Sale', 'Expenses', 'Other Income', 'Other Expenses'])
			->findAll();

		return $result != null ? $result : null;
	}
}

/**
(income - cost of sale) - expenses = gross profit
(gross_p + other income) - other expenses = net profit
[PROFIT & LOSS]
 */
