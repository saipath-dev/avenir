<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\InvoiceNumberModel;
use App\Models\SupplierModel;
use App\Models\CustomerModel;
use App\Models\ConsigneeModel;

class ExpensesChartController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/accounts/expenses_chart
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $fromDate = $this->request->getGet('fromDate');
        if ($fromDate == null) {
            $fromDate = date('Y-m-d');
        }

        $toDate = $this->request->getGet('toDate');
        if ($toDate == null) {
            $toDate = date('Y-m-d');
        }

        $data['expenses'] = $this->get_expeneses($fromDate, $toDate);
        $data['fromDate'] = $fromDate;
        $data['toDate'] = $toDate;
        log_message('critical', json_encode($data['expenses']));

        return view('pages/accounts/expenses_chart', $data);
    }

    // route : base_url/accounts/expenses_chart/report
    public function report()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Get date range parameters
        $fromDate = $this->request->getGet('fromDate');
        if ($fromDate == null) {
            $fromDate = date('Y-m-d');
        }

        $toDate = $this->request->getGet('toDate');
        if ($toDate == null) {
            $toDate = date('Y-m-d');
        }

        // Get account filter parameter
        $accountFilter = $this->request->getGet('account');

        // Fetch expense accounts list
        $data['expenseAccounts'] = $this->getExpenseAccounts();

        // Fetch expense data
        $data['expenses'] = $this->getExpenseReportData($fromDate, $toDate, $accountFilter);
        $data['fromDate'] = $fromDate;
        $data['toDate'] = $toDate;
        $data['selectedAccount'] = $accountFilter;

        return view('pages/accounts/expenses_detailed_report', $data);
    }

    private function get_expeneses($fromDate, $toDate)
    {
        $accountRunningModel = new AccountRunningModel();

        // get Dr records between two dates

        // 4-8-106-10386 - HOTEL
        $hotel_total = $accountRunningModel->select('SUM(Dr) as total')
            ->where('L_Code', '4-8-106-10386')
            ->where('Date >=', $fromDate)
            ->where('Date <=', $toDate)
            ->first();
        if ($hotel_total['total'] == null) {
            $hotel_total['total'] = 0;
        }


        // 4-8-106-10385 - TRANSPORT
        $transport_total = $accountRunningModel->select('SUM(Dr) as total')
            ->where('L_Code', '4-8-106-10385')
            ->where('Date >=', $fromDate)
            ->where('Date <=', $toDate)
            ->first();
        if ($transport_total['total'] == null) {
            $transport_total['total'] = 0;
        }

        // get Cr records between two dates

        // 6-9-23-10640 - ENTRANCE FEE
        $entrance_fee_total = $accountRunningModel->select('SUM(Cr) as total')
            ->where('L_Code', '6-9-23-10640')
            ->where('Date >=', $fromDate)
            ->where('Date <=', $toDate)
            ->first();
        if ($entrance_fee_total['total'] == null) {
            $entrance_fee_total['total'] = 0;
        }

        // 6-9-23-10641 - OTHER
        $other_total = $accountRunningModel->select('SUM(Cr) as total')
            ->where('L_Code', '6-9-23-10641')
            ->where('Date >=', $fromDate)
            ->where('Date <=', $toDate)
            ->first();
        if ($other_total['total'] == null) {
            $other_total['total'] = 0;
        }

        return array(
            'hotel' => $hotel_total['total'],
            'transport' => $transport_total['total'],
            'entrance_fee' => $entrance_fee_total['total'],
            'other' => $other_total['total'],
            'total' => $hotel_total['total'] + $transport_total['total'] + $entrance_fee_total['total'] + $other_total['total']
        );
    }

    private function getExpenseReportData($fromDate, $toDate, $accountFilter = null)
    {
        $accountRunningModel = new AccountRunningModel();

        // Query to get expense transactions with account details
        $builder = $accountRunningModel->builder();
        $builder->select([
            'account_runing.Date',
            'account_runing.L_Code',
            'acc_selct.PName as ExpenseAccount',
            'account_runing.Discription',
            'account_runing.Dr',
            'account_runing.Cr',
            '(account_runing.Dr - account_runing.Cr) as Balance'
        ])
        ->join('acc_selct', 'account_runing.L_Code = acc_selct.L_Code', 'left')
        ->where('account_runing.Date >=', $fromDate)
        ->where('account_runing.Date <=', $toDate)
        ->groupStart()
            ->like('account_runing.L_Code', '4-', 'after')  // Starts with '4-'
            ->orLike('account_runing.L_Code', '7-', 'after') // Starts with '7-'
        ->groupEnd();

        // Apply account filter if provided
        if ($accountFilter != null && $accountFilter != '') {
            $builder->where('account_runing.L_Code', $accountFilter);
        }

        $builder->orderBy('account_runing.Date', 'ASC')
                ->orderBy('account_runing.L_Code', 'ASC');

        $results = $builder->get()->getResultArray();

        // Calculate running total
        $totalBalance = 0;
        foreach ($results as &$row) {
            $balance = (float)$row['Balance'];
            $totalBalance += $balance;
            $row['RunningTotal'] = $totalBalance;
        }

        return [
            'records' => $results,
            'totalBalance' => $totalBalance
        ];
    }

    private function getExpenseAccounts()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('acc_selct');

        $builder->select('L_Code, PName')
                ->groupStart()
                    ->like('L_Code', '4-', 'after')
                    ->orLike('L_Code', '7-', 'after')
                ->groupEnd()
                ->orderBy('PName', 'ASC');

        return $builder->get()->getResultArray();
    }
}
