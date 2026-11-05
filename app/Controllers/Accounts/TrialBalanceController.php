<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountRunningQ2Model;

class TrialBalanceController extends BaseController
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
		}

		return view('pages/accounts/trial_balance', $data);
	}


	public function load_accountant_report()
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

			$data['load_data'] = $this->load_data2($date1, $date2);
			$data['ar_details'] = $this->load_ar_details($date1, $date2); // AR
			$data['ap_details'] = $this->load_ap_details($date1, $date2); // AP
			// $data['income'] = $this->load_income($date1, $date2);
			// $data['expenses'] = $this->load_expenses($date1, $date2);
			// $data['cost_of_sale'] = $this->load_cost_of_sale($date1, $date2);
			// $data['other_expenses'] = $this->load_other_expenses($date1, $date2);
			// $data['other_income'] = $this->load_other_income($date1, $date2);
		}

		return view('pages/accounts/trial_balance_report', $data);
	}



	public function load_report()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$date1 = $this->request->getGet('date1');
		$date2 = $this->request->getGet('date2');

		$data['is_search'] = false;

		if (!empty($date2)) {
			// If only end date is selected and start is empty
			if (empty($date1) || $date1 == '0000-00-00') {
				// Calculate start of financial year based on date2
				$year = (int)date('Y', strtotime($date2));
				$month = (int)date('m', strtotime($date2));
				if ($month >= 4) {
					$date1 = $year . '-04-01';
				} else {
					$date1 = ($year - 1) . '-04-01';
				}
			}

			$data['is_search'] = true;
			$data['date1'] = $date1;
			$data['date2'] = $date2;

			$data['load_data'] = $this->load_data2($date1, $date2);
			$data['ar_details'] = $this->load_ar_details($date1, $date2); // AR
			$data['ap_details'] = $this->load_ap_details($date1, $date2); // AP
		}

		return view('pages/accounts/trial_balance_report_account', $data);
	}

	public function load_data($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, TName, HName, SUM(Dr) as DR, SUM(Cr) as CR, SUM(bal) AS bal')
			->groupBy('BName, TName, HName')
			->where("Date >=", $date1)
			->where("Date <=", $date2)
			->findAll();

		return $result != null ? $result : null;
	}

	public function load_ar_details($date1, $date2)
	{
		$results = $this->AccountRunningQ2Model
			->select('PName, SUM(Dr) as DR, SUM(Cr) as CR')
			->groupBy('PName')
			->where("Date >=", $date1)
			->where("Date <=", $date2)
			->where('BName', 'Asset')
			->where('TName', 'Current Assets')
			->where('HName', 'Trade Debtors')
			->findAll();

		$final = [];
		foreach ($results as $row) {
			$balance = $row['DR'] - $row['CR'];
			if ($balance > 0) {
				$final[] = [
					'PName' => $row['PName'],
					'DR' => $balance,
					'CR' => 0
				];
			} elseif ($balance < 0) {
				$final[] = [
					'PName' => $row['PName'],
					'DR' => 0,
					'CR' => abs($balance)
				];
			}
			// If balance == 0, skip
		}
		return $final;
	}

	public function load_ap_details($date1, $date2)
	{
		$results = $this->AccountRunningQ2Model
			->select('PName, SUM(Dr) as DR, SUM(Cr) as CR')
			->groupBy('PName')
			->where("Date >=", $date1)
			->where("Date <=", $date2)
			->where('BName', 'Liability')
			->where('TName', 'Current Liabilities')
			->where('HName', 'Supplier Accounts')
			->findAll();

		$final = [];
		foreach ($results as $row) {
			$balance = $row['CR'] - $row['DR'];
			if ($balance > 0) {
				$final[] = [
					'PName' => $row['PName'],
					'CR' => $balance,
					'DR' => 0
				];
			} elseif ($balance < 0) {
				$final[] = [
					'PName' => $row['PName'],
					'CR' => 0,
					'DR' => abs($balance)
				];
			}
			// If balance == 0, skip
		}
		return $final;
	}


	// public function load_data2($date1, $date2)
	// {
	// 	$all = $this->AccountRunningQ2Model
	// 		->select('BName, TName, HName, PName, SUM(Dr) as Dr, L_Code, SUM(Cr) as Cr, SUM(bal) as bal, balflag')
	// 		->where("date(Date) BETWEEN '$date1' AND '$date2'")
	// 		->groupBy('BName, TName, HName, PName, balflag,L_Code')
	// 		->orderBy('BName')
	// 		->findAll();

	// 	$final = [];

	// 	$ar = [
	// 		'BName' => 'Asset',
	// 		'TName' => 'Current Assets',
	// 		'HName' => 'Trade Debtors',
	// 		'PName' => 'Accounts Receivable',
	// 		'DR' => 0,
	// 		'CR' => 0,
	// 		'L_Code' => '', // Include L_Code if available
	// 	];

	// 	$ap_bal = 0;

	// 	foreach ($all as $row) {
	// 		$dr = 0;
	// 		$cr = 0;

	// 		// Aggregate AR separately
	// 		if (
	// 			$row['BName'] === 'Asset' &&
	// 			$row['TName'] === 'Current Assets' &&
	// 			$row['HName'] === 'Trade Debtors'
	// 		) {
	// 			// Use balflag logic for AR
	// 			if ($row['balflag'] == 1) {
	// 				$balance = $row['Dr'] - $row['Cr'];
	// 				if ($balance >= 0) {
	// 					$ar['DR'] += $balance;
	// 				} else {
	// 					$ar['CR'] += abs($balance);
	// 				}
	// 			} elseif ($row['balflag'] == 2) {
	// 				$balance = $row['Cr'] - $row['Dr'];
	// 				if ($balance >= 0) {
	// 					$ar['CR'] += $balance;
	// 				} else {
	// 					$ar['DR'] += abs($balance);
	// 				}
	// 			}
	// 		}
	// 		// Aggregate AP using SUM(bal)
	// 		elseif (
	// 			$row['BName'] === 'Liability' &&
	// 			$row['TName'] === 'Current Liabilities' &&
	// 			$row['HName'] === 'Supplier Accounts'
	// 		) {
	// 			$ap_bal += $row['bal'];
	// 		}
	// 		// All other rows
	// 		else {
	// 			// Use balflag logic for other rows
	// 			if ($row['balflag'] == 1) {
	// 				$balance = $row['Dr'] - $row['Cr'];
	// 				if ($balance >= 0) {
	// 					$dr = $balance;
	// 				} else {
	// 					$cr = abs($balance);
	// 				}
	// 			} elseif ($row['balflag'] == 2) {
	// 				$balance = $row['Cr'] - $row['Dr'];
	// 				if ($balance >= 0) {
	// 					$cr = $balance;
	// 				} else {
	// 					$dr = abs($balance);
	// 				}
	// 			}
	// 			$final[] = [
	// 				'BName' => $row['BName'],
	// 				'TName' => $row['TName'],
	// 				'HName' => $row['HName'],
	// 				'PName' => $row['PName'],
	// 				'DR' => $dr,
	// 				'CR' => $cr,
	// 				'L_Code' => $row['L_Code'], // Include L_Code if available
	// 			];
	// 		}
	// 	}

	// 	// Add AR if non-zero
	// 	if ($ar['DR'] > 0 || $ar['CR'] > 0) {
	// 		$final[] = $ar;
	// 	}

	// 	// Add AP using bal logic
	// 	if ($ap_bal != 0) {
	// 		$ap = [
	// 			'BName' => 'Liability',
	// 			'TName' => 'Current Liabilities',
	// 			'HName' => 'Supplier Accounts',
	// 			'PName' => 'Accounts Payable',
	// 			'DR' => $ap_bal < 0 ? abs($ap_bal) : 0,
	// 			'CR' => $ap_bal > 0 ? $ap_bal : 0,
	// 			'L_Code' => '',
	// 		];
	// 		$final[] = $ap;
	// 	}

	// 	// Optional: sort for display
	// 	usort($final, function ($a, $b) {
	// 		return strcmp($a['BName'], $b['BName']);
	// 	});

	// 	return $final;
	// }


	public function load_data2($date1, $date2)
	{
		$all = $this->AccountRunningQ2Model
			->select('BName, TName, HName, PName, SUM(Dr) as Dr, L_Code, SUM(Cr) as Cr, SUM(bal) as bal, balflag')
			->where("Date >=", $date1)
			->where("Date <=", $date2)
			->groupBy('BName, TName, HName, PName, balflag,L_Code')
			->orderBy('BName')
			->findAll();

		$final = [];

		$ar_total_dr = 0;
		$ar_total_cr = 0;
		$ap_bal = 0;

		foreach ($all as $row) {
			$dr = 0;
			$cr = 0;

			// Aggregate AR separately - collect totals first
			if (
				$row['BName'] === 'Asset' &&
				$row['TName'] === 'Current Assets' &&
				$row['HName'] === 'Trade Debtors'
			) {
				$ar_total_dr += $row['Dr'];
				$ar_total_cr += $row['Cr'];
			}
			// Aggregate AP using QuickBooks standard: Cr-Dr balance
			elseif (
				$row['BName'] === 'Liability' &&
				$row['TName'] === 'Current Liabilities' &&
				$row['HName'] === 'Supplier Accounts'
			) {
				$balance = $row['Cr'] - $row['Dr'];
				$ap_bal += $balance;
			}
			// All other rows
			else {
				// Use balflag logic for other rows
				if ($row['balflag'] == 1) {
					$balance = $row['Dr'] - $row['Cr'];
					if ($balance >= 0) {
						$dr = $balance;
					} else {
						$cr = abs($balance);
					}
				} elseif ($row['balflag'] == 2) {
					$balance = $row['Cr'] - $row['Dr'];
					if ($balance >= 0) {
						$cr = $balance;
					} else {
						$dr = abs($balance);
					}
				}
				$final[] = [
					'BName' => $row['BName'],
					'TName' => $row['TName'],
					'HName' => $row['HName'],
					'PName' => $row['PName'],
					'DR' => $dr,
					'CR' => $cr,
					'L_Code' => $row['L_Code'], // Include L_Code if available
				];
			}
		}

		// Add AR using QuickBooks standard: if Dr-Cr > 0 show in Dr side, else show in Cr side
		$ar_balance = $ar_total_dr - $ar_total_cr;
		if ($ar_balance != 0) {
			$ar = [
				'BName' => 'Asset',
				'TName' => 'Current Assets',
				'HName' => 'Trade Debtors',
				'PName' => 'Accounts Receivable',
				'DR' => $ar_balance > 0 ? $ar_balance : 0,
				'CR' => $ar_balance < 0 ? abs($ar_balance) : 0,
				'L_Code' => '',
			];
			$final[] = $ar;
		}

		// Add AP using QuickBooks standard: if Cr-Dr > 0 show in Cr side, else show in Dr side
		if ($ap_bal != 0) {
			$ap = [
				'BName' => 'Liability',
				'TName' => 'Current Liabilities',
				'HName' => 'Supplier Accounts',
				'PName' => 'Accounts Payable',
				'DR' => $ap_bal < 0 ? abs($ap_bal) : 0,
				'CR' => $ap_bal > 0 ? $ap_bal : 0,
				'L_Code' => '',
			];
			$final[] = $ap;
		}

		// Optional: sort for display
		usort($final, function ($a, $b) {
			return strcmp($a['BName'], $b['BName']);
		});

		return $final;
	}





	public function load_income($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, TName, HName, Dr, Cr, SUM(bal) AS bal')
			->groupBy('BName, TName, HName, Dr, Cr')
			->where("Date >=", $date1)
			->where("Date <=", $date2)
			->where('BName', 'Income')
			->findAll();

		return $result != null ? $result[0] : null;
	}

	public function load_expenses($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, TName, HName, Dr, Cr, SUM(bal) AS bal')
			->groupBy('BName, TName, HName, Dr, Cr')
			->where("Date >=", $date1)
			->where("Date <=", $date2)
			->where('BName', 'Expenses')
			->findAll();

		return $result != null ? $result[0] : null;
	}

	public function load_cost_of_sale($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, TName, HName, Dr, Cr, SUM(bal) AS bal')
			->groupBy('BName, TName, HName, Dr, Cr')
			->where("Date >=", $date1)
			->where("Date <", $date2)
			->where('BName', 'Cost Of Sale')
			->findAll();

		return $result != null ? $result[0] : null;
	}

	public function load_other_expenses($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, TName, HName, Dr, Cr, SUM(bal) AS bal')
			->groupBy('BName, TName, HName, Dr, Cr')
			->where("Date >=", $date1)
			->where("Date <=", $date2)
			->where('BName', 'Other Expenses')
			->findAll();

		return $result != null ? $result[0] : null;
	}

	public function load_other_income($date1, $date2)
	{
		$result = $this->AccountRunningQ2Model->select('BName, TName, HName, Dr, Cr, SUM(bal) AS bal')
			->groupBy('BName, TName, HName, Dr, Cr')
			->where("Date >=", $date1)
			->where("Date <=", $date2)
			->where('BName', 'Other Income')
			->findAll();

		return $result != null ? $result[0] : null;
	}
	public function trial_balance_details($lcode)
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();
		$date = $this->request->getGet('date2');
		$data['date2'] = $date;

		// Calculate start date of financial year based on $date
		$date1 = null;
		if (!empty($date)) {
			$year = date('Y', strtotime($date));
			$month = date('m', strtotime($date));
			if ((int)$month >= 4) {
				$date1 = $year . '-04-01';
			} else {
				$date1 = ($year - 1) . '-04-01';
			}
		}
		$data['date1'] = $date1;

		$data['is_search'] = false;

		if (!empty($lcode) && !empty($date1) && !empty($date)) {

			$data['is_search'] = true;

			$data['lcode'] = $lcode;

			$data['load_data'] = $this->AccountRunningQ2Model
				->select('BName, Date, TName, HName, PName, Discription, Dr as DR, Cr as CR, bal AS bal')
				->where("L_Code", $lcode)
				->where("Date >=", $date1)
				->where("Date <=", $date)
				->findAll();
		}

		return view('pages/accounts/trial_balance_details', $data);
	}
}

/**
(income - cost of sale) - expenses = gross profit
(gross_p + other income) - other expenses = net profit
[PROFIT & LOSS]
 */
