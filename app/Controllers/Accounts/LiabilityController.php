<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountRunningModel;
use App\Models\ConsigneeModel;
use App\Models\SupplierModel;

class LiabilityController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/accounts/liability
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $date1 = $this->request->getGet('date1');
        $date2 = $this->request->getGet('date2');
        $supplier = $this->request->getGet('supplier');
        $tour = $this->request->getGet('tour');

        $data['date1'] = $date1;
        $data['date2'] = $date2;
        $data['supplier'] = $supplier;
        $data['tour'] = $tour;

        $SupplierModel = new SupplierModel();
        $data['suppliers']  = $SupplierModel->orderBy('Name', 'ASC')->findAll();

        $accountRunningModel = new AccountRunningModel();

        $data['is_search'] = false;
        $data['in_detail'] = false;
        $data['type'] = 0;

        if (!empty($date1) && !empty($date2)) {

            $data['is_search'] = true;
            $data['in_detail'] = false;

            if ($tour != '') {

                $data['in_detail'] = true;
                $data['type'] = 3;

                // supplier records for the selected date range
                $data['records'] = $accountRunningModel
                    ->where('L_Code', $supplier);
                if ($tour != 0) {
                    $data['records'] = $data['records']->where('index_no', $tour);
                }
                $data['records'] = $data['records']->where('Date >=', $date1)
                    ->where('Date <=', $date2);

                $data['records'] = $data['records']->orderBy('Date', 'ASC')->findAll();
            } else if (!empty($supplier)) {

                $data['in_detail'] = true;
                $data['type'] = 2;

                // get distinct tours for the selected supplier
                // and then get the total dr and cr for each tour

                $data['tours'] = $accountRunningModel
                    ->select('index_no')
                    ->where('L_Code', $supplier)
                    ->where('Date >=', $date1)
                    ->where('Date <=', $date2)
                    ->distinct()
                    ->findAll();

                // $ff = array();
                foreach ($data['tours'] as $key => $tour) {
                    $consigneeModel = new ConsigneeModel();
                    $data['tours'][$key]['tour'] = $consigneeModel->where('tour_id', $tour['index_no'])->first();

                    if (empty($data['tours'][$key]['tour'])) {
                        // unset($data['tours'][$key]);
                        // continue;
                        $data['tours'][$key]['tour'] = [
                            'tour_id' => 0,
                            'manual_id' => '',
                            'consignee' => 'Other'
                        ];
                    }

                    $records = $accountRunningModel->where('L_Code', $supplier)->where('index_no', $tour['index_no'])->where('Date <=', $date2)->where('Date >=', $date1);
                    $records = $records->orderBy('Date', 'ASC')->findAll();

                    $total_dr = 0;
                    $total_cr = 0;
                    foreach ($records as $record) {
                        $total_dr += $record['Dr'];
                        $total_cr += $record['Cr'];
                    }
                    $balance = $total_dr - $total_cr;

                    $data['tours'][$key]['total_dr'] = $total_dr;
                    $data['tours'][$key]['total_cr'] = $total_cr;
                    $data['tours'][$key]['balance'] = $balance;
                }
                // var_dump($ff); exit;

            } else {
                // $ff = array();
                $data['type'] = 1;
                // foreach supplier, get his total dr and cr and then balance
                foreach ($data['suppliers'] as $key => $supplier) {
                    $records = $accountRunningModel->where('L_Code', $supplier['Sup_Acc_No'])->where('Date <=', $date2)->where('Date >=', $date1);
                    // if (!empty($tour)) {
                    //     $records = $records->where('index_no', $tour);
                    // }
                    $records = $records->orderBy('Date', 'ASC')->findAll();

                    $total_dr = 0;
                    $total_cr = 0;
                    foreach ($records as $record) {
                        $total_dr += $record['Dr'];
                        $total_cr += $record['Cr'];
                    }

                    $balance = $total_dr - $total_cr;

                    // if balance is 0, remove the supplier from the list
                    if (!empty($records)) {
                        $data['suppliers'][$key]['total_dr'] = $total_dr;
                        $data['suppliers'][$key]['total_cr'] = $total_cr;
                        $data['suppliers'][$key]['balance'] = $balance;
                    } else {
                        unset($data['suppliers'][$key]);
                    }
                }


                ///////////

                // $unique_ff = array();
                // $unique_records = array();
                // foreach ($ff as $record) {
                //     $index = $record['index_no'];
                //     if (!in_array($index, $unique_ff)) {
                //         $unique_ff[] = $index;
                //         $unique_records[] = [
                //             'index_no' => $index,
                //             'date' => $record['Date'],
                //             'supplier' => $record['L_Code'],
                //             'dr' => $record['Dr'],
                //             'cr' => $record['Cr']
                //         ];
                //     }
                // }

                // var_dump(json_encode($unique_records));
                // exit;
            }
        }

        return view('pages/accounts/liability_report', $data);
    }
}
