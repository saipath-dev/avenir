<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\TourQuotationsModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\InvoiceNumberModel;
use App\Models\TourActivityModel;
use App\Models\TourSubLocationsModel;
use App\Models\PassengerDetailsModel;

class TourDetailsSearchController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/tour/tour_details
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountPostingModel = new AccountPostingModel();
        $data['methods'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $keyword = $this->request->getGet('keyword');
        $date1 = $this->request->getGet('from');
        $date2 = $this->request->getGet('to');

        // uncode the keyword
        $keyword = urldecode($keyword);

        $TourQuotationsModel = new TourQuotationsModel();

        $tourDetailsQuery = $TourQuotationsModel->select('tour_quotations.quotation_id AS tour_id, Max(consignee.end_date) AS end_date, Min(tour_quotations.tour_date) AS start_date, Max(tour_quotations.confirm) As Confirm, Max(tour_quotations.finalize) As Finalize ,Max(consignee.consignee) AS consignee, Max(consignee.agent_name) AS agent_name,Max(consignee.create_user) AS create_user,Max(consignee.create_time) AS create_time, consignee.mobile as mobile,consignee.email AS email, consignee.passenger_count AS passenger_count, Sum(tour_quotations.extra_act_fee) as activity_fee, Sum(tour_quotations.extra_kilo) as extra_km, Sum(tour_quotations.distance) As fullkm, Max(consignee.vehicle_rate) AS vehicle_rate, consignee.manual_id, consignee.create_user')->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')->groupBy('tour_quotations.quotation_id');

        $queries = [];

        if (!empty($keyword)) {
            $keywordQuery = clone $tourDetailsQuery;
            $keywordQuery = $keywordQuery
                ->groupStart()
                ->where('consignee.manual_id like', "%$keyword%")
                ->orWhere('consignee.consignee like', "%$keyword%")
                ->groupEnd();
            $queries[] = $keywordQuery;
        }

        if (!empty($date1) || !empty($date2)) {
            $dateRangeQuery = clone $tourDetailsQuery;
            if (!empty($date1)) {
                $dateRangeQuery = $dateRangeQuery->where('consignee.start_date >=', $date1);
            }
            if (!empty($date2)) {
                $dateRangeQuery = $dateRangeQuery->where('consignee.start_date <=', $date2);
            }
            $queries[] = $dateRangeQuery;
        }

        // log_message('critical', $tourDetailsQuery->getLastQuery());

        if (!empty($queries)) {
            $tourDetailsQuery = array_shift($queries);
            foreach ($queries as $query) {
                $tourDetailsQuery = $tourDetailsQuery->union($query, false);
            }
            $tourDetails = $tourDetailsQuery->get()->getResult();
            $tourid = array_column($tourDetails, 'tour_id');
            // add if condition to check if the tour id is not empty
            if (!empty($tourid)) {
                $passengerDetailsModel = new PassengerDetailsModel();
                $passengerDetails = $passengerDetailsModel->whereIn('tour_id', $tourid)->findAll();
                $data['pax_details'] = $passengerDetails;
            } else {
                $tourDetails = [];
            }
        } else {
            $tourDetails = [];
        }

        $data['tour_details'] = $tourDetails;
        $data['keyword'] = $keyword;
        $data['date1'] = $date1;
        $data['date2'] = $date2;

        return view('pages/tour/tour_details_search', $data);
    }

    // route : base_url/tour/show_details
    public function findAllDetails()
    {

        $data = array();
        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();


        $tour_id = $this->request->getGet('id');
        $start_date = $this->request->getGet('start');
        $end_date = $this->request->getGet('end');

        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        $TourQuotationsModel = new TourQuotationsModel();

        $tour_details = $TourQuotationsModel->select('tour_quotations.*, supplier.Name as hotel_name, room_type.rtype,transfer_milage.transfer_code as transfer_name')->where('tour_quotations.quotation_id', $tour_id)
            ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc', 'left')
            ->join('room_type', 'room_type.id = tour_quotations.room_type_id', 'left')
            ->join('transfer_milage', 'transfer_milage.id = tour_quotations.transfer_code', 'left')
            ->orderBy('tour_quotations.tour_date')
            ->findAll();
        $data['tour_details'] = $tour_details;

        $tourActivityModel = new TourActivityModel();
        $tourSubLocationModel = new TourSubLocationsModel();

        // if (!empty($data['tour_details'])) {
        //     $quotation_id = $data['tour_details'][0]['quotation_id'];

        //     // Step 3: Get results from the consignee table where tour_id equals quotation_id
        //     $tour_activity = $tourActivityModel->where('tour_id',$quotation_id)->findAll();
        //     // log_message('critical',$tourActivityModel->getLastQuery());
        //     // Step 4: Append the consignee results to the quotation details
        //     foreach ($data['tour_details'] as &$detail) {
        //         $detail['activity'] = $tour_activity[0];
        //     }
        // }

        if (!empty($data['tour_details'])) {

            foreach ($data['tour_details'] as &$detail) {
                $quotation_id = $detail['quotation_id'];
                $quotation_row = $detail['quotation_day'];
                // Get results from the consignee table where tour_id equals quotation_id
                $tour_activity = $tourActivityModel->select('tour_activity.*, entrance_fee.name')->join('entrance_fee', 'entrance_fee.id = tour_activity.name')->where('tour_activity.tour_id', $quotation_id)->where('tour_activity.tour_row', $quotation_row)->findAll();
                $detail['activity'] = $tour_activity; // Store the array of activities
            }

            foreach ($data['tour_details'] as &$detail) {
                $quotation_id = $detail['quotation_id'];
                $quotation_row = $detail['quotation_day'];
                // Get results from the consignee table where tour_id equals quotation_id
                $tour_sunloctation = $tourSubLocationModel->where('quotation_id', $quotation_id)->where('tour_row', $quotation_row)->findAll();
                $detail['sub_location'] = $tour_sunloctation; // Store the array of activities

                // log_message('critical', $tourSubLocationModel->getLastQuery());
            }
        }

        return view('pages/tour/tour_more_details', $data);
    }

    // route : base_url/tour/tour_details/confirm_tour

    public function confirm_tour()
    {

        $tour_id = $this->request->getGet('tour_id');

        $data = [
            'confirm' => 1,
        ];

        try {

            $TourQuotationsModel = new TourQuotationsModel();
            if ($TourQuotationsModel->where('quotation_id',  $tour_id)->set($data)->update()) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Confirmed", "tour_id" => $tour_id]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {

            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    public function payment_details()
    {
        $validation = \Config\Services::validation();

        $validation->setRules([
            'customer' => 'required|strip_tags',
            'cash' => 'required|strip_tags',
            'pay_date' => 'required|strip_tags',
            'bank_account' => 'required|strip_tags',
            'order_id' => 'required|strip_tags',
            // 'ex_rate' => 'required|strip_tags',
            // 'd_amt' => 'required|strip_tags'
        ]);

        if ($validation->withRequest($this->request)->run()) {

            $customer = $this->request->getVar('customer');
            $cash = $this->request->getVar('cash');
            $payment_date = $this->request->getVar('pay_date');
            $bank_account = $this->request->getVar('bank_account');
            $tour = $this->request->getVar('order_id');

            $accountRunningModel = new AccountRunningModel();
            $username = session()->get('username');

            $model_inv = new InvoiceNumberModel();
            $inv = $model_inv->incrementNo();

            // description
            $description = "Advanced Payment by : " . $customer;

            // amount in numeric format
            $subTotal = is_numeric($cash) ? $cash : 0.00;

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            // DR Record
            $dr = [
                'ID' => $acc_id,
                'L_Code' => $bank_account, // payment method
                'Dr' => $subTotal,
                'Cr' => 0.00,
                'Discription' => $description,
                'Date' => $payment_date,
                'status' => '',
                'Cashier' => $username,
                'Invoice_Num' => $inv,
                'Linkacc' => $tour, // customer id
                'Dt' => date('Y-m-d H:i:s')
            ];

            $accountRunningModel->insert($dr);

            // CR Record
            $cr = [
                'ID' => $acc_id,
                'L_Code' => '6-11-107-10381', // advance payment account
                'Dr' => 0.00,
                'Cr' => $subTotal,
                'Discription' => $description,
                'Date' => $payment_date,
                'status' => '',
                'Cashier' => $username,
                'Invoice_Num' => $inv,
                'Linkacc' => $tour,
                'Dt' => date('Y-m-d H:i:s'),
            ];

            $accountRunningModel->insert($cr);

            return $this->response->setJSON(["status" => 200, "message" => "Payment Added successfully!", 'tour' => $tour]);
        } else {
            // Bad Request
            return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data."]);
        }
    }

    public function advance_details($id)
    {
        // $customer = $this->request->getGet('id');

        if (!empty($id)) {

            $accountRunningModel = new AccountRunningModel();
            // where L_Code = 6-11-107-9663 (advance payment account) and Linkacc = customer id
            $transactions = $accountRunningModel
                ->where('account_runing.L_Code', '6-11-107-10381')
                ->where('account_runing.Linkacc', $id)
                ->where('account_runing.aflag', 0)
                // ->orderBy('account_runing.Date', 'DESC')
                ->findAll();

            // $total_dr = 0;
            // $total_cr = 0;
            // $date = '';

            // foreach ($transactions as $transaction) {

            //     $data = [
            //         'transactions' => $transactions,
            //         'total_dr' => $transaction['Dr'],
            //         'total_cr' => $transaction['Cr'],
            //         'date' => $transaction['Date']
            //     ];
            // }


            if (!empty($transactions)) {
                return $this->response->setJSON(["status" => 200, "data" => $transactions]);
            }
        }

        return $this->response->setJSON(["status" => 400, "message" => "No previous payments found."]);
    }

    public function advance_delete()
    {
        $row_id = $this->request->getGet('id');
        $accountRunningModel = new AccountRunningModel();

        if ($accountRunningModel->where('ID', $row_id)->set('aflag', 1)->update()) {
            return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated"]);
        } else {
            return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
        }
    }
}
