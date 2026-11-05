<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountRunningModel;
use App\Models\CustomerModel;
use App\Models\TourQuotationsModel;
use App\Models\PassengerDetailsModel;
use App\Models\ConsigneeModel;
use App\Models\DebtorInvoiceLogModel;
use App\Models\TourSubLocationsModel;
use App\Models\TourActivityModel;
use App\Models\TourItineraryModel;


class TourDashboardController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/tour/tour_dashboard
    public function index()
    {

        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();


        $TourQuotationsModel = new TourQuotationsModel();
        $data['tour_years'] = $TourQuotationsModel->select('YEAR(consignee.start_date) as year')
            ->distinct()
            ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
            ->where('consignee.start_date IS NOT NULL')
            ->where('consignee.start_date !=', '0000-00-00')
            ->where('consignee.start_date !=', '1970-01-01')
            ->where('tour_quotations.saved', 1)
            ->where('tour_quotations.confirm', 1)
            ->orwhere('tour_quotations.confirm', 4)
            ->where('tour_quotations.finalize', 1)
            ->where('tour_quotations.verify', 1)
            ->orderBy('year', 'DESC')
            ->findAll();

        $selectedYear = $this->request->getGet('year');

        if (!empty($selectedYear)) {
            $year = $selectedYear;
        } else {
            $year = date('Y');
        }

        $data['s_year'] = $year;

        $data['tour_details'] = $TourQuotationsModel->select('MONTH(consignee.start_date) as month, MONTHNAME(consignee.start_date) as month_name')
            ->distinct()
            ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
            ->where(['YEAR(consignee.start_date)' => $year, 'tour_quotations.saved' => 1, 'tour_quotations.confirm' => 1, 'tour_quotations.finalize' => 1, 'tour_quotations.verify' => 1])
            ->orwhere('tour_quotations.confirm', 4)
            ->orderBy('month', 'DESC')
            ->findAll();

        $keyword = $this->request->getGet('keyword');

        $keyword = urldecode($keyword);

        $TourQuotationsModel = new TourQuotationsModel();

        $tourDetailsQuery = $TourQuotationsModel->select('tour_quotations.quotation_id AS tour_id, Max(consignee.end_date) AS end_date, Min(tour_quotations.tour_date) AS start_date, Max(tour_quotations.confirm) As Confirm, Max(tour_quotations.finalize) As Finalize ,Max(consignee.consignee) AS consignee, Max(consignee.agent_name) AS agent_name,Max(consignee.create_user) AS create_user,Max(consignee.create_time) AS create_time, consignee.mobile as mobile,consignee.email AS email, consignee.passenger_count AS passenger_count, Sum(tour_quotations.extra_act_fee) as activity_fee, Sum(tour_quotations.extra_kilo) as extra_km, Sum(tour_quotations.distance) As fullkm, Max(consignee.vehicle_rate) AS vehicle_rate, consignee.manual_id, consignee.create_user, MAX(consignee.amend_inv) as amend_inv')->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')->groupBy('tour_quotations.quotation_id');

        $queries = [];

        if (!empty($keyword)) {
            $keywordQuery = clone $tourDetailsQuery;
            $keywordQuery = $keywordQuery
                ->orWhere('consignee.tour_id like', "%$keyword%")
                ->orWhere('consignee.consignee like', "%$keyword%")
                ->orWhere('consignee.manual_id like', "%$keyword%");
            $queries[] = $keywordQuery;
        }

        if (!empty($queries)) {
            $tourDetailsQuery = array_shift($queries);
            foreach ($queries as $query) {
                $tourDetailsQuery = $tourDetailsQuery->union($query, false);
            }
            $tourDetails = $tourDetailsQuery->get()->getResult();
            $tourid = array_column($tourDetails, 'tour_id');

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

        $data['tour_details_search'] = $tourDetails;

        return view('pages/tour/tour_dashboard', $data);
    }

    //route : base_url/tour/tour_dashboard/monthly_dashboard

    public function monthly_dashboard()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $year = $this->request->getGet('year');
        $month = $this->request->getGet('month');
        $tour = $this->request->getGet('tour_id');

        $data['year'] = $year;
        $data['month'] = $month;

        $customerModel = new CustomerModel();

        $data['customers'] = $customerModel->select('Sup_Acc_No, Name')->findAll();

        $TourQuotationsModel = new TourQuotationsModel();

        if (empty($tour)) {
            $tourDetailsQuery = $TourQuotationsModel
                ->select([
                    'tour_quotations.quotation_id AS tour_id',
                    'MAX(consignee.agent) AS agent',
                    'MAX(consignee.agent_ref) AS agent_ref',
                    'MAX(consignee.end_date) AS end_date',
                    'MIN(consignee.start_date) AS start_date',
                    'MAX(consignee.consignee) AS consignee',
                    'MAX(consignee.passenger_count) AS pax_count',
                    'MAX(consignee.manual_id) AS manual_id',
                    'MAX(consignee.transport_advance) AS transport_advance',
                    'MAX(consignee.transport_settled) AS transport_settled',
                    'MAX(tour_quotations.confirm) AS Confirm',
                    'MAX(tour_quotations.finalize) AS Finalize',
                    'MAX(tour_quotations.quotation_day) AS quotation_days',
                    'SUM(CASE WHEN tour_quotations.hotel_booking_flag = 1 THEN 1 ELSE 0 END) AS hotel_booking_cnt',
                    'SUM(CASE WHEN tour_quotations.booking_confirmed_flag = 1 THEN 1 ELSE 0 END) AS booking_confirmed_cnt',
                    'SUM(CASE WHEN tour_quotations.hotel_payment_flag = 1 THEN 1 ELSE 0 END) AS hotel_payment_cnt',
                    'MAX(consignee.create_user) AS create_user',
                    'MAX(consignee.hotel_only) AS hotel_only',
                    'MAX(consignee.transport_only) AS transport_only',
                    'SUM(tour_quotations.total_hotel_doller) AS total_hotel_doller',
                    'SUM(tour_quotations.pay_doller_count) AS pay_doller_count',
                    'COUNT(tour_quotations.hotel_acc) AS hotel_count',
                    'COUNT(CASE WHEN tour_quotations.hotel_acc IS NOT NULL AND tour_quotations.hotel_acc <> "" THEN 1 ELSE NULL END) AS hotel_count_new',
                    'MAX(consignee.amend_inv) AS amend_inv',
                ])
                ->join(
                    '(SELECT DISTINCT tour_id, 
            agent, 
            agent_ref, 
            end_date, 
            consignee, 
            passenger_count, 
            manual_id, 
            transport_advance, 
            transport_settled, 
            create_user, 
            hotel_only, 
            transport_only,start_date, amend_inv
            FROM consignee 
            WHERE is_template = 0 AND verify = 1) AS consignee',
                    'consignee.tour_id = tour_quotations.quotation_id',
                    'LEFT'
                )
                ->where('YEAR(consignee.start_date)', $year)
                ->where('MONTH(consignee.start_date)', $month)
                ->where('tour_quotations.finalize', 1)
                ->groupStart()
                ->where('tour_quotations.confirm', 1)
                ->orWhere('tour_quotations.confirm', 3)
                ->orWhere('tour_quotations.confirm', 4)
                ->groupEnd()
                ->where('tour_quotations.saved', 1)
                ->groupBy('tour_quotations.quotation_id')
                ->get(); // Execute the query

            $tourDetails = $tourDetailsQuery->getResult();
        } else {
            $tourDetailsQuery = $TourQuotationsModel->select([
                'tour_quotations.quotation_id AS tour_id',
                'consignee.agent',
                'consignee.agent_ref',
                'MAX(consignee.end_date) AS end_date',
                'MIN(tour_quotations.tour_date) AS start_date',
                'MAX(consignee.consignee) AS consignee',
                'MAX(consignee.passenger_count) AS pax_count',
                'MAX(consignee.manual_id) AS manual_id',
                'MAX(consignee.transport_advance) AS transport_advance',
                'MAX(consignee.transport_settled) AS transport_settled',
                'MAX(tour_quotations.confirm) AS Confirm',
                'MAX(tour_quotations.finalize) AS Finalize',
                'MAX(tour_quotations.quotation_day) AS quotation_days',
                'SUM(CASE WHEN tour_quotations.hotel_booking_flag = 1 THEN 1 ELSE 0 END) AS hotel_booking_cnt',
                'SUM(CASE WHEN tour_quotations.booking_confirmed_flag = 1 THEN 1 ELSE 0 END) AS booking_confirmed_cnt',
                'SUM(CASE WHEN tour_quotations.hotel_payment_flag = 1 THEN 1 ELSE 0 END) AS hotel_payment_cnt',
                'MAX(consignee.create_user) AS create_user',
                'MAX(consignee.hotel_only) AS hotel_only',
                'MAX(consignee.transport_only) AS transport_only',
                'SUM(tour_quotations.total_hotel_doller) AS total_hotel_doller',
                'SUM(tour_quotations.pay_doller_count) AS pay_doller_count',
                'COUNT(tour_quotations.hotel_acc) AS hotel_count',
                'COUNT(CASE WHEN tour_quotations.hotel_acc IS NOT NULL AND tour_quotations.hotel_acc <> "" THEN 1 ELSE NULL END) AS hotel_count_new',
                'MAX(consignee.amend_inv) AS amend_inv',

            ])
                ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
                ->where('consignee.tour_id', $tour)
                ->where('consignee.is_template', 0)
                ->where('tour_quotations.finalize', 1)
                ->groupStart()
                ->where('tour_quotations.confirm', 1)
                ->orWhere('tour_quotations.confirm', 3)
                ->orWhere('tour_quotations.confirm', 4)
                ->groupEnd()
                ->where('tour_quotations.saved', 1)
                ->where('consignee.verify', 1)
                // ->where('tour_quotations.hotel_acc <>', '')
                ->groupBy('tour_quotations.quotation_id');

            $tourDetails = $tourDetailsQuery->get()->getResult();
        }
        $data['tour_details'] = $tourDetails;

        foreach ($tourDetails as $tour) {
            $tour->t_expense_added = $this->get_tour_expenses_status($tour->tour_id);
        }

        $tourid = [];

        if (!empty($data['tour_details'])) {

            $tourid = array_column($data['tour_details'], 'tour_id');

            $tourid = array_filter($tourid, function ($value) {
                return !is_null($value);
            });
        }

        if (!empty($tourid)) {
            $passengerDetailsModel = new PassengerDetailsModel();
            $passengerDetails = $passengerDetailsModel->whereIn('tour_id', $tourid)->findAll();
            $tourPassengerCount = [];

            foreach ($tourid as $id) {
                $count = 0;
                foreach ($passengerDetails as $detail) {
                    if ($detail['tour_id'] == $id) {
                        $count++;
                    }
                }
                $tourPassengerCount[] = [
                    'tour_id' => $id,
                    'count' => $count
                ];
            }

            $data['differecePassengerCount'] = $tourPassengerCount;

            $data['pax_details'] = $passengerDetails;
        } else {
            $data['pax_details'] = [];
            $data['differecePassengerCount'] = [];
        }

        return view('pages/tour/monthly_dashboard', $data);
    }

    private function get_tour_expenses_status($tour_id)
    {
        $accountRunningModel = new AccountRunningModel();
        $accountRunning = $accountRunningModel
            ->where('index_no', $tour_id)
            ->where('advances_pay', 111)
            ->like('Discription', '%Expenses')
            ->countAllResults();

        return $accountRunning > 0 ? true : false;
    }

    // routes : base_url/tour/tour_dashboard/tour_templates
    public function tour_templates()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $TourQuotationsModel = new TourQuotationsModel();

        $tourDetailsQuery = $TourQuotationsModel->db->table('tour_quotations')
            ->select([
                'tour_quotations.quotation_id AS tour_id',
                'MAX(consignee.end_date) AS end_date',
                'MIN(tour_quotations.tour_date) AS start_date',
                'MAX(consignee.consignee) AS consignee',
                'MAX(consignee.manual_id) AS manual_id'
            ])
            ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
            ->where('consignee.is_template', 1)
            // ->where('tour_quotations.finalize', 1)
            ->groupBy('tour_quotations.quotation_id');

        $tourDetails = $tourDetailsQuery->get()->getResult();

        $data['tour_details'] = $tourDetails;

        $tourid = [];

        if (!empty($tourDetails)) {

            $tourid = array_column($tourDetails, 'tour_id');

 
            $tourid = array_filter($tourid, function ($value) {
                return !is_null($value);
            });
        }

        if (!empty($tourid)) {
            $passengerDetailsModel = new PassengerDetailsModel();
            $passengerDetails = $passengerDetailsModel->whereIn('tour_id', $tourid)->findAll();
            $data['pax_details'] = $passengerDetails;
        } else {

            $data['pax_details'] = [];
        }

        return view('pages/tour/monthly_dashboard_templates', $data);
    }

    //routes : base_url/tour/tour_dashboard/delete
    public function delete()
    {
        $tour_id = $this->request->getGet('tour_id');

        try {
            $TourQuotationsModel = new TourQuotationsModel();
            $ConsigneeModel = new ConsigneeModel();
            $tourActivityModel = new TourActivityModel();
            $tourItineraryModel = new TourItineraryModel();
            $tourSubLocationsModel = new TourSubLocationsModel();
            $passengerDetailsModel = new PassengerDetailsModel();

            $rowcount_tour = $this->countResult($TourQuotationsModel, $tour_id, 'quotation_id');
            $rowcount_consignee = $this->countResult($ConsigneeModel, $tour_id, 'tour_id');
            $rowcount_activity = $this->countResult($tourActivityModel, $tour_id, 'tour_id');
            $rowcount_itinerary = $this->countResult($tourItineraryModel, $tour_id, 'tour_id');
            $rowcount_sublocations = $this->countResult($tourSubLocationsModel, $tour_id, 'quotation_id');
            $rowcount_passenger = $this->countResult($passengerDetailsModel, $tour_id, 'tour_id');

            if ($rowcount_tour == 'Error' || $rowcount_consignee == 'Error' || $rowcount_activity == 'Error' || $rowcount_itinerary == 'Error' || $rowcount_sublocations == 'Error' || $rowcount_passenger == 'Error') {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong. Deletion failed from one or more tables"]);
            }

            if ($rowcount_tour > 0 || $rowcount_consignee > 0 || $rowcount_activity > 0 || $rowcount_itinerary > 0 || $rowcount_sublocations > 0 || $rowcount_passenger > 0) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Deleted"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    public function countResult($model, $tour_id, $column)
    {
        $count = $model->where($column, $tour_id)->countAllResults();

        if ($count > 0) {
            if ($model->where($column, $tour_id)->delete()) {
                return $count;
            } else {
                return 'Error';
            }
        } else {
            return 0;
        }
    }


    // route :base_url/tour/tour_dashboard/search_debtor_invoice
    public function search_debtor_invoice()
    {

        try {
            $tour_id = $this->request->getPost('tour_id');

            $debtorinvoiceLogModel = new DebtorInvoiceLogModel();
            $debtorInvoiceLog = $debtorinvoiceLogModel->where('tour_id', $tour_id)->countAllResults();

            if ($debtorInvoiceLog > 0) {
                return $this->response->setJSON(["status" => 200, "message" => "Debtor Invoice Found"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Debtor Invoice Not Found"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tout/tour_dashboard/add_manual_id
    public function add_manual_id()
    {
        $tour_id = strip_tags($this->request->getPost('order_id'));
        $manual_id = strip_tags($this->request->getPost('manual_id'));

        try {
            $ConsigneeModel = new ConsigneeModel();
            $existingManualId = $ConsigneeModel->select('manual_id')->where('manual_id', $manual_id)->first();

            if ($existingManualId) {
                return $this->response->setJSON(["status" => 400, "message" => "Manual ID already exists"]);
            }

            $add = $ConsigneeModel->where('tour_id', $tour_id)->set(['manual_id' => $manual_id])->update();

            // log_message('critical', $ConsigneeModel->getLastQuery());
            if ($add) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // get manual id
    public function get_manual_id()
    {
        $tour_id = $this->request->getPost('tour_id');

        try {
            $ConsigneeModel = new ConsigneeModel();
            $manual_id = $ConsigneeModel->select('manual_id')->where('tour_id', $tour_id)->first();
            // log_message('critical', $ConsigneeModel->getLastQuery());

            if (!empty($manual_id)) {
                return $this->response->setJSON(["status" => 200, "manual_id" => $manual_id['manual_id']]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Manual ID Not Found"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // edit manual id 
    public function edit_manual_id()
    {
        $tour_id = strip_tags($this->request->getPost('order_id'));
        $manual_id = strip_tags($this->request->getPost('manual_id'));

        try {
            $ConsigneeModel = new ConsigneeModel();
            $existingManualId = $ConsigneeModel->select('manual_id')->where('manual_id', $manual_id)->where('tour_id !=', $tour_id)->first();

            if ($existingManualId) {
                return $this->response->setJSON(["status" => 400, "message" => "Manual ID already exists"]);
            }

            $update = $ConsigneeModel->where('tour_id', $tour_id)->set(['manual_id' => $manual_id])->update();

            if ($update) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tout/tour_dashboard/add_manual_id
    public function add_agent_ref()
    {
        $tour_id = strip_tags($this->request->getPost('order_id'));
        $agent = strip_tags($this->request->getPost('agent'));

        try {
            $ConsigneeModel = new ConsigneeModel();
            $existingManualId = $ConsigneeModel->select('agent_ref')->where('agent_ref', $agent)->first();

            if ($existingManualId) {
                return $this->response->setJSON(["status" => 400, "message" => "Agent Reference already exists"]);
            }

            $add = $ConsigneeModel->where('tour_id', $tour_id)->set(['agent_ref' => $agent])->update();

            // log_message('critical', $ConsigneeModel->getLastQuery());
            if ($add) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // get manual id
    public function get_agent_ref()
    {
        $tour_id = $this->request->getPost('tour_id');

        try {
            $ConsigneeModel = new ConsigneeModel();
            $agent_ref = $ConsigneeModel->select('agent_ref')->where('tour_id', $tour_id)->first();
            // log_message('critical', $ConsigneeModel->getLastQuery());

            if (!empty($agent_ref)) {
                return $this->response->setJSON(["status" => 200, "agent_ref" => $agent_ref['agent_ref']]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Agent Reference Not Found"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // edit manual id 
    public function edit_agent_ref()
    {
        $tour_id = strip_tags($this->request->getPost('order_id'));
        $agent_ref = strip_tags($this->request->getPost('agent'));

        try {
            $ConsigneeModel = new ConsigneeModel();
            $existingagentId = $ConsigneeModel->select('agent_ref')->where('agent_ref', $agent_ref)->where('tour_id !=', $tour_id)->first();

            if ($existingagentId) {
                return $this->response->setJSON(["status" => 400, "message" => "Agent Reference already exists"]);
            }

            $update = $ConsigneeModel->where('tour_id', $tour_id)->set(['agent_ref' => $agent_ref])->update();

            if ($update) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }
}
