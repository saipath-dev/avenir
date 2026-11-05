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
		if (empty($date1) || $date1=='0000-00-00') {
            // Get minimum date from the transactions table
			$minDateRow = $this->AccountRunningQ2Model
			->selectMin('Date')
			->first();

            $date1 = $minDateRow['Date'] ?? date('Y-m-01'); // fallback just in case
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
	->where("date(Date) BETWEEN '$date1' AND '$date2'")
	->findAll();

	return $result != null ? $result : null;
}

public function load_ar_details($date1, $date2)
{
	return $this->AccountRunningQ2Model
	->select('PName, SUM(Dr) as DR, SUM(Cr) as CR')
	->groupBy('PName')
	->where("date(Date) BETWEEN '$date1' AND '$date2'")
	->where('BName', 'Asset')
	->where('TName', 'Current Assets')
	->where('HName', 'Trade Debtors')
	->findAll();
}

public function load_ap_details($date1, $date2)
{
	return $this->AccountRunningQ2Model
	->select('PName, SUM(Dr) as DR, SUM(Cr) as CR')
	->groupBy('PName')
	->where("date(Date) BETWEEN '$date1' AND '$date2'")
	->where('BName', 'Liability')
	->where('TName', 'Current Liabilities')
	->where('HName', 'Supplier Accounts')
	->findAll();
}
// public function load_data2($date1, $date2)
// {
//     // Load all grouped balances
// 	$all = $this->AccountRunningQ2Model
// 	->select('BName, TName, HName, PName, SUM(Dr) as DR, SUM(Cr) as CR, SUM(bal) AS bal')
// 	->where("date(Date) BETWEEN '$date1' AND '$date2'")
// 	->groupBy('BName, TName, HName, PName')
// 	->orderBy('BName')
// 	->findAll();

// 	$final = [];

//     // Placeholders for AR and AP, now with BName included
// 	$ar = ['BName' => 'Asset', 'PName' => 'Accounts Receivable', 'DR' => 0, 'CR' => 0];
// 	$ap = ['BName' => 'Liability', 'PName' => 'Accounts Payable', 'DR' => 0, 'CR' => 0];

// 	foreach ($all as $row) {
// 		if (
// 			$row['BName'] === 'Asset' &&
// 			$row['TName'] === 'Current Assets' &&
// 			$row['HName'] === 'Trade Debtors'
// 		) {
// 			$ar['DR'] += $row['DR'];
// 			$ar['CR'] += $row['CR'];
// 		} elseif (
// 			$row['BName'] === 'Liability' &&
// 			$row['TName'] === 'Current Liabilities' &&
// 			$row['HName'] === 'Supplier Accounts'
// 		) {
// 			$ap['DR'] += $row['DR'];
// 			$ap['CR'] += $row['CR'];
// 		} else {
//             // Include BName so sorting can apply later
// 			$final[] = [
// 				'BName' => $row['BName'],
// 				'PName' => $row['PName'],
// 				'DR' => $row['DR'],
// 				'CR' => $row['CR'],
// 			];
// 		}
// 	}

//     // Only add if non-zero
// 	if ($ar['DR'] > 0 || $ar['CR'] > 0) {
// 		$final[] = $ar;
// 	}

// 	if ($ap['DR'] > 0 || $ap['CR'] > 0) {
// 		$final[] = $ap;
// 	}

//     // Sort the final array by BName
// 	usort($final, function ($a, $b) {
// 		return strcmp($a['BName'], $b['BName']);
// 	});

// 	return $final;
// }

public function load_data2($date1, $date2)
{
    $all = $this->AccountRunningQ2Model
        ->select('BName, TName, HName, PName, SUM(Dr) as Dr, SUM(Cr) as Cr, balflag')
        ->where("date(Date) BETWEEN '$date1' AND '$date2'")
        ->groupBy('BName, TName, HName, PName, balflag')
        ->orderBy('BName')
        ->findAll();

    $final = [];

       $ar = [
        'BName' => 'Asset',
        'TName' => 'Current Assets',
        'HName' => 'Trade Debtors',
        'PName' => 'Accounts Receivable',
        'DR' => 0,
        'CR' => 0
    ];

    $ap = [
        'BName' => 'Liability',
        'TName' => 'Current Liabilities',
        'HName' => 'Supplier Accounts',
        'PName' => 'Accounts Payable',
        'DR' => 0,
        'CR' => 0
    ];

    foreach ($all as $row) {
        $dr = 0;
        $cr = 0;

        // Compute balance using balflag
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

        // Aggregate AR/AP separately
        if (
            $row['BName'] === 'Asset' &&
            $row['TName'] === 'Current Assets' &&
            $row['HName'] === 'Trade Debtors'
        ) {
            $ar['DR'] += $dr;
            $ar['CR'] += $cr;
        } elseif (
            $row['BName'] === 'Liability' &&
            $row['TName'] === 'Current Liabilities' &&
            $row['HName'] === 'Supplier Accounts'
        ) {
            $ap['DR'] += $dr;
            $ap['CR'] += $cr;
        } else {
            $final[] = [
                'BName' => $row['BName'],
                'TName' => $row['TName'],
                'HName' => $row['HName'],
                'PName' => $row['PName'],
                'DR' => $dr,
                'CR' => $cr,
            ];
        }
    }

    if ($ar['DR'] > 0 || $ar['CR'] > 0) {
        $final[] = $ar;
    }

    if ($ap['DR'] > 0 || $ap['CR'] > 0) {
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
	->where("date(Date) BETWEEN '$date1' AND '$date2'")
	->where('BName', 'Income')
	->findAll();

	return $result != null ? $result[0] : null;
}

public function load_expenses($date1, $date2)
{
	$result = $this->AccountRunningQ2Model->select('BName, TName, HName, Dr, Cr, SUM(bal) AS bal')
	->groupBy('BName, TName, HName, Dr, Cr')
	->where("date(Date) BETWEEN '$date1' AND '$date2'")
	->where('BName', 'Expenses')
	->findAll();

	return $result != null ? $result[0] : null;
}

public function load_cost_of_sale($date1, $date2)
{
	$result = $this->AccountRunningQ2Model->select('BName, TName, HName, Dr, Cr, SUM(bal) AS bal')
	->groupBy('BName, TName, HName, Dr, Cr')
	->where("date(Date) BETWEEN '$date1' AND '$date2'")
	->where('BName', 'Cost Of Sale')
	->findAll();

	return $result != null ? $result[0] : null;
}

public function load_other_expenses($date1, $date2)
{
	$result = $this->AccountRunningQ2Model->select('BName, TName, HName, Dr, Cr, SUM(bal) AS bal')
	->groupBy('BName, TName, HName, Dr, Cr')
	->where("date(Date) BETWEEN '$date1' AND '$date2'")
	->where('BName', 'Other Expenses')
	->findAll();

	return $result != null ? $result[0] : null;
}

public function load_other_income($date1, $date2)
{
	$result = $this->AccountRunningQ2Model->select('BName, TName, HName, Dr, Cr, SUM(bal) AS bal')
	->groupBy('BName, TName, HName, Dr, Cr')
	->where("date(Date) BETWEEN '$date1' AND '$date2'")
	->where('BName', 'Other Income')
	->findAll();

	return $result != null ? $result[0] : null;
}
}

/**
(income - cost of sale) - expenses = gross profit
(gross_p + other income) - other expenses = net profit
[PROFIT & LOSS]
 */
