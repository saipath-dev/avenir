<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;

class BankStatementController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/accounts/bank_statement
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountRunningModel = new AccountRunningModel();
        $accountPostingModel = new AccountPostingModel();
        $data['banks'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $date1 = strip_tags($this->request->getGet('date1'));
        $date2 = strip_tags($this->request->getGet('date2'));
        $bank = strip_tags($this->request->getGet('bank'));
        $data['date1'] = $date1;
        $data['date2'] = $date2;
        $data['bank'] = $bank;

        $data['is_search'] = false;
        $data['in_detail'] = false;

        if (!empty($date1) && !empty($date2)) {

            $data['is_search'] = true;
            $data['in_detail'] = false;

            if (!empty($bank)) {

                $data['in_detail'] = true;

                // bank records for the selected date range
                $data['records'] = $accountRunningModel->where('L_Code', $bank)->where('Date >=', $date1)->where('Date <=', $date2)->orderBy('Date', 'ASC')->findAll();
                log_message('critical', $accountRunningModel->getLastQuery());

                // foreach bank get opening balance where date < date1
                $data['opening_balance'] = $accountRunningModel->select('SUM(Dr) - SUM(Cr) as opening_bal')->where('L_Code', $bank)->where('Date <', $date1)->orderBy('Date', 'ASC')->findAll();
            } else {
                // foreach bank, get his total dr and cr and then balance
                foreach ($data['banks'] as $key => $bank) {
                    $records = $accountRunningModel->where('L_Code', $bank['L_Code'])->where('Date <=', $date2)->where('Date >=', $date1)->orderBy('Date', 'ASC')->findAll();
                    $total_dr = 0;
                    $total_cr = 0;
                    foreach ($records as $record) {
                        $total_dr += $record['Dr'];
                        $total_cr += $record['Cr'];
                    }
                    $balance = $total_dr - $total_cr;
                    $data['banks'][$key]['total_dr'] = $total_dr;
                    $data['banks'][$key]['total_cr'] = $total_cr;
                    $data['banks'][$key]['balance'] = $balance;
                }
            }
        }

        return view('pages/accounts/bank_statement', $data);
    }
}
