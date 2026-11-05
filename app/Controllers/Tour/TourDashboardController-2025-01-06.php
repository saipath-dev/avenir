<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
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

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();


        $TourQuotationsModel = new TourQuotationsModel();
        $data['tour_years'] = $TourQuotationsModel->select('YEAR(tour_date) as year')
            ->distinct()
            ->where('tour_date IS NOT NULL')
            ->where('tour_date !=', '0000-00-00')
            ->where('tour_date !=', '1970-01-01')
            ->where('saved', 1)
            ->where('confirm', 1)
            ->where('finalize', 1)
            ->where('verify', 1)
            ->orderBy('year', 'DESC')
            ->findAll();

        $selectedYear = $this->request->getGet('year');

        if (!empty($selectedYear)) {
            $year = $selectedYear;
        } else {
            $year = date('Y');
        }

        $data['s_year'] = $year;

        $data['tour_details'] = $TourQuotationsModel->select('MONTH(tour_date) as month, MONTHNAME(tour_date) as month_name')
            ->distinct()->where(['YEAR(tour_date)' => $year, 'saved' => 1, 'confirm' => 1, 'finalize' => 1, 'verify' => 1])->orderBy('month', 'DESC')->findAll();

        // Search
        $keyword = $this->request->getGet('keyword');

        // uncode the keyword
        $keyword = urldecode($keyword);

        $TourQuotationsModel = new TourQuotationsModel();

        $tourDetailsQuery = $TourQuotationsModel->select('tour_quotations.quotation_id AS tour_id, Max(consignee.end_date) AS end_date, Min(tour_quotations.tour_date) AS start_date, Max(tour_quotations.confirm) As Confirm, Max(tour_quotations.finalize) As Finalize ,Max(consignee.consignee) AS consignee, Max(consignee.agent_name) AS agent_name,Max(consignee.create_user) AS create_user,Max(consignee.create_time) AS create_time, consignee.mobile as mobile,consignee.email AS email, consignee.passenger_count AS passenger_count, Sum(tour_quotations.extra_act_fee) as activity_fee, Sum(tour_quotations.extra_kilo) as extra_km, Sum(tour_quotations.distance) As fullkm, Max(consignee.vehicle_rate) AS vehicle_rate, consignee.manual_id, consignee.create_user')->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')->groupBy('tour_quotations.quotation_id');

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

        //class CustomerModel extends Model
        //{
        //    protected $table = 'customers';
        //    protected $primaryKey = 'ID';
        //    protected $allowedFields = ['Sup_Acc_No', 'Name', 'Address', 'NIC', 'TP', 'Mobile', 'flag', 'cr_lmt', 'cr_dyz', 'csh', 'chq', 'chq_fvr', 'rep_offce', 'rep_mobile', 'ac', 'sup_cat', 'ref_no', 'dealer_acc', 'email', 'country', 't_code_1', 't_code_2', 'zipcode', 'margin', 'long_name', 'prefix'];
        //    protected $returnType = 'array';
        //}

        // Initialize the ConsigneeModel
        $customerModel = new CustomerModel();

        //all consignees
        $data['customers'] = $customerModel->select('Sup_Acc_No, Name')->findAll();

        // Initialize the TourQuotationsModel
        $TourQuotationsModel = new TourQuotationsModel();

        // Create the query
        if (empty($tour)) {
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
                'SUM(tour_quotations.hotel_booking_flag = 1) AS hotel_booking_cnt',
                'SUM(tour_quotations.booking_confirmed_flag = 1) AS booking_confirmed_cnt',
                'SUM(tour_quotations.hotel_payment_flag = 1) AS hotel_payment_cnt',
                'MAX(consignee.create_user) AS create_user',
                'MAX(consignee.hotel_only) AS hotel_only',
                'MAX(consignee.transport_only) AS transport_only',
                'SUM(tour_quotations.total_hotel_doller) AS total_hotel_doller',
                'SUM(tour_quotations.pay_doller_count) AS pay_doller_count'
            ])
                ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
                ->where('YEAR(tour_quotations.tour_date)', $year)
                ->where('MONTH(tour_quotations.tour_date)', $month)
                ->where('consignee.is_template', 0)
                ->where('tour_quotations.finalize', 1)
                ->where('tour_quotations.confirm', 1)
                ->where('tour_quotations.saved', 1)
                ->where('consignee.verify', 1)
                // ->where('tour_quotations.hotel_acc <>', '')
                ->groupBy('tour_quotations.quotation_id');

            // Execute the query and get the result
            $tourDetails = $tourDetailsQuery->get()->getResult();
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
                'SUM(tour_quotations.hotel_booking_flag = 1) AS hotel_booking_cnt',
                'SUM(tour_quotations.booking_confirmed_flag = 1) AS booking_confirmed_cnt',
                'SUM(tour_quotations.hotel_payment_flag = 1) AS hotel_payment_cnt',
                'MAX(consignee.create_user) AS create_user',
                'MAX(consignee.hotel_only) AS hotel_only',
                'MAX(consignee.transport_only) AS transport_only',
                'SUM(tour_quotations.total_hotel_doller) AS total_hotel_doller',
                'SUM(tour_quotations.pay_doller_count) AS pay_doller_count'
            ])
                ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
                ->where('consignee.tour_id', $tour)
                ->where('consignee.is_template', 0)
                ->where('tour_quotations.finalize', 1)
                ->where('tour_quotations.confirm', 1)
                ->where('tour_quotations.saved', 1)
                ->where('consignee.verify', 1)
                // ->where('tour_quotations.hotel_acc <>', '')
                ->groupBy('tour_quotations.quotation_id');

            // Execute the query and get the result
            $tourDetails = $tourDetailsQuery->get()->getResult();
        }
        // log_message('critical', $TourQuotationsModel->getLastQuery());
        $data['tour_details'] = $tourDetails;

        // Initialize an empty array for tour_id
        $tourid = [];

        // Check if $data['tour_details'] is not empty and contains valid tour_id
        if (!empty($data['tour_details'])) {
            // Extract tour_id from $data['tour_details']
            $tourid = array_column($data['tour_details'], 'tour_id');

            // If any tour_id is null, filter it out
            $tourid = array_filter($tourid, function ($value) {
                return !is_null($value);
            });
        }

        // If tour_id array is empty, handle it gracefully
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
            // If no tour_id found or all are null, return an empty pax_details array
            $data['pax_details'] = [];
            $data['differecePassengerCount'] = [];
        }

        return view('pages/tour/monthly_dashboard', $data);
    }

    // routes : base_url/tour/tour_dashboard/tour_templates
    public function tour_templates()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Initialize the TourQuotationsModel
        $TourQuotationsModel = new TourQuotationsModel();

        // Create the query
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


        // Execute the query and get the result
        $tourDetails = $tourDetailsQuery->get()->getResult();

        $data['tour_details'] = $tourDetails;

        // Initialize an empty array for tour_id
        $tourid = [];

        // Check if $tourDetails is not empty and contains valid tour_id
        if (!empty($tourDetails)) {
            // Extract tour_id from $tourDetails
            $tourid = array_column($tourDetails, 'tour_id');

            // If any tour_id is null, filter it out
            $tourid = array_filter($tourid, function ($value) {
                return !is_null($value);
            });
        }

        // If tour_id array is empty, handle it gracefully
        if (!empty($tourid)) {
            $passengerDetailsModel = new PassengerDetailsModel();
            $passengerDetails = $passengerDetailsModel->whereIn('tour_id', $tourid)->findAll();
            $data['pax_details'] = $passengerDetails;
        } else {
            // If no tour_id found or all are null, return an empty pax_details array
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
            log_message('critical', $ConsigneeModel->getLastQuery());

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
