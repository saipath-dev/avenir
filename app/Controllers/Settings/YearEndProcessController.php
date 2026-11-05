<?php

namespace App\Controllers\Settings;

use App\Controllers\BaseController;
use App\Models\GroupProfileModel;
use App\Libraries\MenuLoader;
use App\Models\AccountRunningModel;
use App\Models\BasementAccountsModel;
use App\Models\UserModel;
use App\Models\AccSelectModel;
use App\Models\InvoiceNumberModel;


class YearEndProcessController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountBasement = new BasementAccountsModel();
        $data['basement_accounts'] = $accountBasement->whereIn('B_Code', [4, 5, 7, 8])->findAll();

        $data['basement_accounts_last'] = $accountBasement->whereIn('B_Code', [1, 2, 3, 6])->findAll();

        // $accountPosting = new AccountRunningModel();
        // $data['account_posting'] = $accountPosting->select('*')->where('del', 0)->orderBy('id', 'ASC')->findAll();

        $model = new GroupProfileModel();
        $data['profiles'] = $model->select('*')->where('del', 0)->orderBy('id', 'ASC')->findAll();

        return view('pages/settings/year_end_process', $data);
    }

    public function fetch($query)
    {
        $accselectmodel = new AccSelectModel();

        $data = $accselectmodel->where('B_Code', 3)->where('T_Code', 15)->where('H_Code', 33)
            ->groupStart()
            ->orLike('PName', $query)
            ->orLike('TName', $query)
            ->orLike('HName', $query)
            ->orLike('BName', $query)
            ->groupEnd()
            ->findAll();

        return json_encode($data);
    }

    public function process()
    {
        try {
            $baseAccount = $this->request->getPost('base_account');
            $transferAccount = $this->request->getPost('acc');
            $date = $this->request->getPost('date');
            $date2 = $this->request->getPost('date2');
            $description = $this->request->getPost('description');

            // Validate inputs
            if (empty($baseAccount) || empty($transferAccount) || empty($date) || empty($description)) {
                return json_encode(['status' => 400, 'message' => 'All fields are required.']);
            }

            $accountRunningModel = new AccountRunningModel();
            $acc_details = $accountRunningModel->select('*')
                ->where('Date <=', $date)
                ->like('L_Code', $baseAccount, 'after')
                ->findAll();

            // log_message('critical', $accountRunningModel->getLastQuery());

            if (empty($acc_details)) {
                return json_encode(['status' => 404, 'message' => 'No account details found for the given date and base account.']);
            }

            $Dr = 0;
            $Cr = 0;
            $bal = 0;
            foreach ($acc_details as $detail) {
                $L_Code = $detail['L_Code'];
                $Dr = $detail['Dr'];
                $Cr = $detail['Cr'];

                if ($baseAccount === '4') { // Expenses
                    $bal = $Dr - $Cr;

                    if ($bal == 0) {
                        continue;
                    }

                    if ($bal < 0) {
                        $bal = abs($bal);
                        $L_code_dr = ['L_Code' => $L_Code];
                        $L_code_cr = ['L_Code' => $transferAccount];
                    } else {
                        $L_code_dr = ['L_Code' => $transferAccount];
                        $L_code_cr = ['L_Code' => $L_Code];
                    }
                } else if ($baseAccount === '5') { // Income
                    $bal = $Cr - $Dr;

                    if ($bal == 0) {
                        continue;
                    }

                    if ($bal < 0) {
                        $bal = abs($bal);
                        $L_code_dr = ['L_Code' => $transferAccount];
                        $L_code_cr = ['L_Code' => $L_Code];
                    } else {
                        $L_code_dr = ['L_Code' => $L_Code];
                        $L_code_cr = ['L_Code' => $transferAccount];
                    }
                } else if ($baseAccount === '7') { // Other Expenses
                    $bal = $Dr - $Cr;

                    if ($bal == 0) {
                        continue;
                    }

                    if ($bal < 0) {
                        $bal = abs($bal);
                        $L_code_dr = ['L_Code' => $L_Code];
                        $L_code_cr = ['L_Code' => $transferAccount];
                    } else {
                        $L_code_dr = ['L_Code' => $transferAccount];
                        $L_code_cr = ['L_Code' => $L_Code];
                    }
                } else if ($baseAccount === '8') { // Other Income
                    $bal = $Cr - $Dr;

                    if ($bal == 0) {
                        continue;
                    }

                    if ($bal < 0) {
                        $bal = abs($bal);
                        $L_code_dr = ['L_Code' => $transferAccount];
                        $L_code_cr = ['L_Code' => $L_Code];
                    } else {
                        $L_code_dr = ['L_Code' => $L_Code];
                        $L_code_cr = ['L_Code' => $transferAccount];
                    }
                }



                // if ($bal < 0) {
                //     $bal = abs($bal);
                //     $L_code_dr = ['L_Code' => $L_Code];
                //     $L_code_cr = ['L_Code' => $transferAccount];
                // } else {
                //     $L_code_dr = ['L_Code' => $transferAccount];
                //     $L_code_cr = ['L_Code' => $L_Code];
                // }

                $invoiceNoModel = new InvoiceNumberModel();
                $acc_id = $invoiceNoModel->incrementAccId();

                $commonData = [
                    'ID' => $acc_id,
                    'Date' => $date2,
                    'Discription' => $description . ' - ' . 'Year End Process',
                    'P_Month' => date('Y-m-d'),
                    'Invoice_Num' => 'YEP-' . date('Y', strtotime($date)),
                    'Cashier' => session()->get('username'),
                    'Dt' => date('Y-m-d H:i:s')
                ];

                // $dataToInsert = [
                //     array_merge($commonData, [
                //         'L_Code' => $transferAccount,
                //         'Dr' => $bal,
                //         'Cr' => 0.00,
                //     ]),
                //     array_merge($commonData, [
                //         'L_Code' => $L_Code,
                //         'Dr' => 0.00,
                //         'Cr' => $bal,
                //     ]),
                // ];

                $dataToInsert = [
                    array_merge($commonData, $L_code_dr, [
                        'Dr' => $bal,
                        'Cr' => 0.00,
                    ]),
                    array_merge($commonData, $L_code_cr, [
                        'Dr' => 0.00,
                        'Cr' => $bal,
                    ]),
                ];

                $accountRunningModel->insertBatch($dataToInsert);
            }

            return json_encode(['status' => 200, 'message' => 'Year-end process completed successfully.']);
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => $e->getMessage()]);
        }
    }


    public function process_last()
    {
        try {
            $baseAccount = $this->request->getPost('base_account_last');
            $transferAccount = $this->request->getPost('acc');
            $date = $this->request->getPost('date');
            $date2 = $this->request->getPost('date2');
            $description = $this->request->getPost('description');

            // Validate inputs
            if (empty($baseAccount) || empty($transferAccount) || empty($date) || empty($description)) {
                return json_encode(['status' => 400, 'message' => 'All fields are required.']);
            }

            $accountRunningModel = new AccountRunningModel();
            $acc_details = $accountRunningModel->select('*')
                ->where('Date <=', $date)
                ->like('L_Code', $baseAccount, 'after')
                ->findAll();

            // log_message('critical', $accountRunningModel->getLastQuery());

            if (empty($acc_details)) {
                return json_encode(['status' => 404, 'message' => 'No account details found for the given date and base account.']);
            }

            $Dr = 0;
            $Cr = 0;
            $bal = 0;
            foreach ($acc_details as $detail) {
                $L_Code = $detail['L_Code'];
                $Dr = $detail['Dr'];
                $Cr = $detail['Cr'];

                if ($baseAccount === '1') { // Assets
                    $bal = $Dr - $Cr;

                    if ($bal == 0) {
                        continue;
                    }

                    if ($bal < 0) {
                        $bal = abs($bal);
                        $L_code_dr = ['L_Code' => $transferAccount];
                        $L_code_cr = ['L_Code' => $L_Code];
                    } else {
                        $L_code_dr = ['L_Code' => $L_Code];
                        $L_code_cr = ['L_Code' => $transferAccount];
                    }
                } else if ($baseAccount === '3') { // Equity
                    $bal = $Cr - $Dr;

                    if ($bal == 0) {
                        continue;
                    }

                    if ($bal < 0) {
                        $bal = abs($bal);
                        $L_code_dr = ['L_Code' => $L_Code];
                        $L_code_cr = ['L_Code' => $transferAccount];
                    } else {
                        $L_code_dr = ['L_Code' => $transferAccount];
                        $L_code_cr = ['L_Code' => $L_Code];
                    }
                } else if ($baseAccount === '2') { // Cost of Sales
                    $bal = $Dr - $Cr;

                    if ($bal == 0) {
                        continue;
                    }

                    if ($bal < 0) {
                        $bal = abs($bal);
                        $L_code_dr = ['L_Code' => $transferAccount];
                        $L_code_cr = ['L_Code' => $L_Code];
                    } else {

                        $L_code_dr = ['L_Code' => $L_Code];
                        $L_code_cr = ['L_Code' => $transferAccount];
                    }
                } else if ($baseAccount === '6') { // Liabilities
                    $bal = $Cr - $Dr;

                    if ($bal == 0) {
                        continue;
                    }

                    if ($bal < 0) {
                        $bal = abs($bal);
                        $L_code_dr = ['L_Code' => $L_Code];
                        $L_code_cr = ['L_Code' => $transferAccount];
                    } else {
                        $L_code_dr = ['L_Code' => $transferAccount];
                        $L_code_cr = ['L_Code' => $L_Code];
                    }
                }



                // if ($bal < 0) {
                //     $bal = abs($bal);
                //     $L_code_dr = ['L_Code' => $L_Code];
                //     $L_code_cr = ['L_Code' => $transferAccount];
                // } else {
                //     $L_code_dr = ['L_Code' => $transferAccount];
                //     $L_code_cr = ['L_Code' => $L_Code];
                // }

                $invoiceNoModel = new InvoiceNumberModel();
                $acc_id = $invoiceNoModel->incrementAccId();

                $commonData = [
                    'ID' => $acc_id,
                    'Date' => $date2,
                    'Discription' => $description . ' - ' . 'Year End Process',
                    'P_Month' => date('Y-m-d'),
                    'Invoice_Num' => 'YEP-' . date('Y', strtotime($date)),
                    'Cashier' => session()->get('username'),
                    'Dt' => date('Y-m-d H:i:s')
                ];

                // $dataToInsert = [
                //     array_merge($commonData, [
                //         'L_Code' => $transferAccount,
                //         'Dr' => $bal,
                //         'Cr' => 0.00,
                //     ]),
                //     array_merge($commonData, [
                //         'L_Code' => $L_Code,
                //         'Dr' => 0.00,
                //         'Cr' => $bal,
                //     ]),
                // ];

                $dataToInsert = [
                    array_merge($commonData, $L_code_dr, [
                        'Dr' => $bal,
                        'Cr' => 0.00,
                    ]),
                    array_merge($commonData, $L_code_cr, [
                        'Dr' => 0.00,
                        'Cr' => $bal,
                    ]),
                ];

                $accountRunningModel->insertBatch($dataToInsert);
            }

            return json_encode(['status' => 200, 'message' => 'Year-end process completed successfully.']);
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => $e->getMessage()]);
        }
    }
}
