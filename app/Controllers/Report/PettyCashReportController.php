<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountRunningModel;
use App\Models\AccountPostingModel;

class PettyCashReportController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function index()
    {
        $data = [];

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Get filter parameters
        $startdate = $this->request->getGet('startdate');
        $data['date1'] = $startdate;

        $enddate = $this->request->getGet('enddate');
        $data['date2'] = $enddate;

        $data['is_search'] = false;
        $data['resultlist'] = [];
        $data['summary'] = [
            'total_debit' => 0,
            'total_credit' => 0,
            'balance' => 0
        ];

        $accountRunningModel = new AccountRunningModel();

        // Only execute query if date filters are provided
        if (!empty($startdate) && !empty($enddate)) {
            $data['is_search'] = true;

            // Query for petty cash transactions using L_Code 1-2-3-10900
            $result = $accountRunningModel
                ->select('account_runing.*, account_runing.Date as transaction_date')
                ->where('L_Code', '1-2-3-10900')
                ->where('aflag', 3)
                ->where('Date >=', $startdate)
                ->where('Date <=', $enddate)
                ->orderBy('Date', 'ASC')
                ->orderBy('Dt', 'ASC')
                ->findAll();

            $data['resultlist'] = $result;

            // Calculate summary
            $total_debit = 0;
            $total_credit = 0;

            foreach ($result as $row) {
                $total_debit += floatval($row['Dr']);
                $total_credit += floatval($row['Cr']);
            }

            $data['summary'] = [
                'total_debit' => $total_debit,
                'total_credit' => $total_credit,
                'balance' => $total_debit - $total_credit
            ];

            $data['selected_startdate'] = $startdate;
            $data['selected_enddate'] = $enddate;
        }

        // Get petty cash account details
        $accountPostingModel = new AccountPostingModel();
        $petty_cash_account = $accountPostingModel
            ->select('PName, L_Code')
            ->where('L_Code', '1-2-3-10900')
            ->first();

        $data['account_name'] = $petty_cash_account ? $petty_cash_account['PName'] : 'Petty Cash';

        return view('pages/report/petty_cash_report', $data);
    }

    /**
     * Get opening balance for petty cash before start date
     */
    public function getOpeningBalance($startdate)
    {
        $accountRunningModel = new AccountRunningModel();

        $result = $accountRunningModel
            ->selectSum('Dr', 'total_debit')
            ->selectSum('Cr', 'total_credit')
            ->where('L_Code', '1-2-3-10900')
            ->where('aflag', 3)
            ->where('Date <', $startdate)
            ->first();

        $opening_debit = floatval($result['total_debit'] ?? 0);
        $opening_credit = floatval($result['total_credit'] ?? 0);

        return $opening_debit - $opening_credit;
    }
}
