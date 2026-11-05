<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\AccountRunningUsdModel;
use App\Models\InvoiceNumberModel;

class UsdWithdrawalController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/accounts/usd_withdrawal
    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountPostingModel = new AccountPostingModel();
        $data['usd_accounts'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->like('PName', 'USD', 'both')->findAll();
        $data['lkr_accounts'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->like('PName', 'LKR', 'both')->findAll();

        return view('pages/accounts/usd_withdrawal', $data);
    }

    // route : base_url/accounts/usd_withdrawal/process
    public function process()
    {
        try {
            $usd_account   = $this->request->getPost('usd_account');
            $lkr_account   = $this->request->getPost('lkr_account');
            $usd_amount    = $this->request->getPost('usd_amount');
            $exchange_rate = $this->request->getPost('exchange_rate');
            $date          = $this->request->getPost('date');
            $description   = $this->request->getPost('description');

            if (!$usd_account || !$lkr_account || !$usd_amount || !$exchange_rate || !$date) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Please fill all required fields']);
            }

            if (!is_numeric($usd_amount) || !is_numeric($exchange_rate)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'USD Amount and Exchange Rate must be numbers']);
            }

            $usd_amount    = floatval($usd_amount);
            $exchange_rate = floatval($exchange_rate);
            $lkr_amount    = $usd_amount * $exchange_rate;

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $desc = $description ? $description . ' - USD Withdrawal' : 'USD Withdrawal';

            $commonData = [
                'ID'          => $acc_id,
                'Date'        => $date,
                'Discription' => $desc,
                'P_Month'     => date('Y-m-d'),
                'Cashier'     => session()->get('username'),
                'Dt'          => date('Y-m-d H:i:s'),
            ];

            // account_runing — LKR entries
            // LKR Bank DR (money coming in as LKR)
            // USD Bank CR (USD going out)
            $accountRunningModel = new AccountRunningModel();
            $accountRunningModel->insertBatch([
                array_merge($commonData, [
                    'L_Code'        => $lkr_account,
                    'Dr'            => $lkr_amount,
                    'Cr'            => 0.00,
                    'dollarrate'    => $exchange_rate,
                    'dollar'        => $usd_amount,
                ]),
                array_merge($commonData, [
                    'L_Code'        => $usd_account,
                    'Dr'            => 0.00,
                    'Cr'            => $lkr_amount,
                    'dollarrate'    => $exchange_rate,
                    'dollar'        => $usd_amount,
                ]),
            ]);

            // account_runing_usd — CR record only (USD withdrawn from USD account)
            $accountRunningUsdModel = new AccountRunningUsdModel();
            $accountRunningUsdModel->insert(array_merge($commonData, [
                'L_Code'        => $usd_account,
                'Dr'            => 0.00,
                'Cr'            => $usd_amount,
                'dollarrate_cr' => $exchange_rate,
                'dollar_cr'     => $usd_amount,
            ]));

            return $this->response->setJSON([
                'status'        => 200,
                'message'       => 'USD Withdrawal processed successfully',
                'print_data'    => [
                    'date'          => $date,
                    'usd_amount'    => $usd_amount,
                    'exchange_rate' => $exchange_rate,
                    'lkr_amount'    => $lkr_amount,
                    'description'   => $description,
                    'usd_account'   => $usd_account,
                    'lkr_account'   => $lkr_account,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->response->setJSON(['status' => 500, 'message' => 'An error occurred while processing the withdrawal']);
        }
    }

    // route : base_url/accounts/usd_withdrawal/history
    public function history()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $from = $this->request->getGet('from');
        $to   = $this->request->getGet('to');

        $data['from'] = $from;
        $data['to']   = $to;
        $data['is_search'] = false;

        if ($from || $to) {
            $data['is_search'] = true;

            $accountRunningUsdModel = new AccountRunningUsdModel();
            $q = $accountRunningUsdModel
                ->select('account_runing_usd.*, account_posting.PName as account_name')
                ->join('account_posting', 'account_posting.L_Code = account_runing_usd.L_Code', 'left')
                ->like('Discription', 'USD Withdrawal')
                ->where('Cr >', 0);

            if ($from) $q->where('Date >=', $from);
            if ($to)   $q->where('Date <=', $to);

            $q->orderBy('Date', 'DESC');
            $data['records'] = $q->findAll();
        } else {
            $data['is_search'] = true;

            $accountRunningUsdModel = new AccountRunningUsdModel();
            $data['records'] = $accountRunningUsdModel
                ->select('account_runing_usd.*, account_posting.PName as account_name')
                ->join('account_posting', 'account_posting.L_Code = account_runing_usd.L_Code', 'left')
                ->like('Discription', 'USD Withdrawal')
                ->where('Cr >', 0)
                ->orderBy('Date', 'DESC')
                ->findAll();
        }

        return view('pages/accounts/usd_withdrawal_history', $data);
    }

    // route : base_url/accounts/usd_withdrawal/print
    public function print()
    {
        $accountPostingModel = new AccountPostingModel();

        $usd_account   = $this->request->getGet('usd_account');
        $lkr_account   = $this->request->getGet('lkr_account');
        $usd_amount    = $this->request->getGet('usd_amount');
        $exchange_rate = $this->request->getGet('exchange_rate');
        $lkr_amount    = $this->request->getGet('lkr_amount');
        $date          = $this->request->getGet('date');
        $description   = $this->request->getGet('description');

        $usd_acc_name = $accountPostingModel->where('L_Code', $usd_account)->first()['PName'] ?? $usd_account;
        $lkr_acc_name = $accountPostingModel->where('L_Code', $lkr_account)->first()['PName'] ?? $lkr_account;

        return view('pages/accounts/usd_withdrawal_print', [
            'date'          => $date,
            'usd_amount'    => $usd_amount,
            'exchange_rate' => $exchange_rate,
            'lkr_amount'    => $lkr_amount,
            'description'   => $description,
            'usd_acc_name'  => '500-002-8818',
            'lkr_acc_name'  => '114-502-0620',
        ]);
    }
}
