<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Controllers\MasterFiles\TransferMilageController;
use App\Controllers\Tour\ItineraryController;

use App\Libraries\MenuLoader;

use App\Models\RoomTypeModel;
use App\Models\SupplierModel;
use App\Models\HotelRateModel;
use App\Models\TourQuotationsModel;
use App\Models\TransferMilageModel;
use App\Models\InvoiceNumberModel;
use App\Models\CountryCodeModel;
use App\Models\ConsigneeModel;
use App\Models\VehicleModel;
use App\Models\CustomerModel;
use App\Models\PassengerDetailsModel;
use App\Models\ExtraKilometersModels;
use App\Models\EntranceFeeModel;
use App\Models\InquiryModel;
use App\Models\TourActivityModel;
use App\Models\TourSubLocationsModel;
use App\Models\TourItineraryModel;
use CodeIgniter\HTTP\Message;

class TourController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/tour/tour
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // $hotelRateModel = new HotelRateModel();
        // $data['hotel'] = $hotelRateModel->select('supplier.Name, hotel_rate.hotel')->join('supplier', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')->orderBy('supplier.Name', 'ASC')->findAll();
        $consigneeModel = new ConsigneeModel();
        // $data['pending_quotations'] = $consigneeModel->where('finalize', 0)->orderBy('tour_id', 'ASC')->findAll();
        $consigneeModel->where('finalize', 0)->where('start_date !=', null)->where('end_date !=', null);
        $data['rowCount'] = $consigneeModel->countAllResults();

        $data['pending_quotations'] = $consigneeModel
            ->where('finalize', 0)
            ->where('start_date !=', null)
            ->where('end_date !=', null)
            ->orderBy('tour_id', 'ASC')->findAll();

        $supplierModel = new SupplierModel();
        $data['hotel'] = $supplierModel->where('sup_cat', 1)->orderBy('Name', 'ASC')->findAll();

        $data['hotel_location'] = $supplierModel->select('Address')->where('sup_cat', 1)->groupBy('Address')->orderBy('Address', 'ASC')->findAll();

        $hotelRateModel = new HotelRateModel();
        $data['hotel_code'] = $hotelRateModel->select('hotel_code')->orderBy('hotel_code', 'ASC')->findAll();

        $roomTypeModel = new RoomTypeModel();
        $data['roomtype'] = $roomTypeModel->findAll();

        $transferMilageModel = new TransferMilageModel();
        $data['transfer_code'] = $transferMilageModel->findAll();

        $countryCodeModel = new CountryCodeModel();
        $data['countries'] = $countryCodeModel->findAll();

        // the agent list: the customers marked as agents (Master Files → Consignees); everyone
        // until the first is marked, and everyone behind "show all customers" on the form
        $customerModel = new CustomerModel();
        $data['agents'] = $customerModel->agentsForQuotation();
        $data['agents_marked'] = $customerModel->agentsMarked();
        $data['agents_all'] = $data['agents_marked'] > 0 ? $customerModel->orderBy('Name', 'ASC')->findAll() : [];

        $entranceFeeModel = new EntranceFeeModel();
        $data['places'] = $entranceFeeModel->findAll();

        $supplierModel = new SupplierModel();
        $data['restaurant_suppliers'] = $supplierModel->where('sup_cat', 4)->findAll();

        $id = $this->request->getGet('tour_id');
        $start_date = $this->request->getGet('start');
        $end_date = $this->request->getGet('end');

        $data['quotation_id'] = $id;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        // get is_accepted == 1 inquiries
        $model = new InquiryModel();
        $data['inquiries'] = $model->where('is_accepted', 1)->findAll();

        if (!empty($id)) {
            $TourQuotationsModel = new TourQuotationsModel();
            // $data['quotation_details'] = $TourQuotationsModel->where('quotation_id', $id)->findAll();
            $data['quotation_details'] = $TourQuotationsModel->select('tour_quotations.*, transfer_milage.transfer_code as code')->join('transfer_milage', 'transfer_milage.id = tour_quotations.transfer_code', 'left')->where('tour_quotations.quotation_id', $id)->findAll();
        }

        return view('pages/tour/tour', $data);
    }

    // route : base_url/tour/tour/url_call
    public function url_call()
    {
        $tour = $this->request->getGet('tour');
        $consigneeModel = new ConsigneeModel();
        $tour_date = $consigneeModel->select('start_date,end_date')->where('finalize', 0)->where('tour_id', $tour)->first();
        return $this->response->setJSON(['status' => 200, 'data' => $tour_date]);
    }

    // route : base_url/tour/tour/show_hotel/{id}/{date}
    public function show_hotel($id, $date)
    {
        // Create a new HotelRateModel
        $hotelrateMOdel = new HotelRateModel();
        $supplier_details = $hotelrateMOdel->select('hotel_rate.hotel_code, room_type.rtype, room_type.id')->join('room_type', 'room_type.id = hotel_rate.room_type', 'left')->where('hotel_rate.hotel', $id)->where('hotel_rate.sdate <=', $date)->where('hotel_rate.edate >=', $date)->findAll();
        return json_encode($supplier_details);
    }

    // route : base_url/tour/tour/search_hotel/{tour}/{row}/{date}
    public function search_hotel($tour, $row, $date)
    {
        $TourQuotationsModel = new TourQuotationsModel();
        $exist_data = $TourQuotationsModel->select("*")
            ->where('quotation_id', $tour)
            ->where('quotation_day', $row)
            ->findAll();

        $supplierModel = new SupplierModel();
        $hotel_details = $supplierModel->join('hotel_rate', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')->where('supplier.sup_cat', 1)->where('supplier.is_avalible', 1)->where('hotel_rate.sdate <=', $date)->where('hotel_rate.edate >=', $date)->orderBy('supplier.Name', 'ASC');

        $hotel_results = [];
        $hotel_results['search_location'] = '';

        if (isset($exist_data[0]['to_location']) && !empty($exist_data[0]['to_location'])) {
            $location_parts = explode(',', $exist_data[0]['to_location']);
            $location = trim($location_parts[0]);

            $hotel_details->like('supplier.Address', $location);
            $hotel_results['search_location'] = $location;
        }

        if (isset($exist_data[0]['transfer']) && !empty($exist_data[0]['transfer'])) {
            $pattern = '/\s+to\s+/i';
            $transfer_parts = preg_split($pattern, $exist_data[0]['transfer']);

            if (count($transfer_parts) > 1) {
                $destination = trim($transfer_parts[1]);

                $hotel_details->like('supplier.Address', $destination);
                $hotel_results['search_location'] = $destination;
            }
        }

        $hotel_results['hotels'] = $hotel_details->findAll();

        return json_encode($hotel_results);
    }

    public function searchHotellocation($tour, $location, $date)
    {
        $supplierModel = new SupplierModel();
        $hotel_details = $supplierModel->join('hotel_rate', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')->where('supplier.sup_cat', 1)->where('supplier.is_avalible', 1)->where('hotel_rate.sdate <=', $date)->where('hotel_rate.edate >=', $date)->where('supplier.Address', $location)->orderBy('supplier.Name', 'ASC');

        $hotel_results['hotels'] = $hotel_details->findAll();

        return json_encode($hotel_results);
    }


    // route : base_url/tour/tour/search_hotel_code/{tour}/{row}/{date}
    public function search_hotel_code($tour, $row, $date)
    {
        $TourQuotationsModel = new TourQuotationsModel();
        $exist_data = $TourQuotationsModel->select("*")->where('quotation_id', $tour)->where('quotation_day', $row)->findAll();

        $hotelrateMOdel = new HotelRateModel();
        $supplier_details = $hotelrateMOdel->select('hotel_code')->where('sdate <=', $date)->where('edate >=', $date);

        if (isset($exist_data[0]['to_location']) && ($exist_data[0]['to_location'] != '') && ($exist_data[0]['to_location'] != null)) {

            $location_parts = explode(',', $exist_data[0]['to_location']);
            $location = trim($location_parts[0]);


            $supplier_details->like('location', $location);
        }

        if (isset($exist_data[0]['transfer']) && ($exist_data[0]['transfer'] != '') && ($exist_data[0]['transfer'] != null)) {

            $pattern = '/\s+to\s+/i';
            $transfer_parts = preg_split($pattern, $exist_data[0]['transfer']);

            if (count($transfer_parts) > 1) {
                $destination = trim($transfer_parts[1]);

                $supplier_details->Like('location', $destination);
            }
        }

        $supplier_results = $supplier_details->findAll();

        return $this->response->setJSON($supplier_results);
    }

    public function search_hotel_code_loc($tour, $loc, $date)
    {

        $hotelrateMOdel = new HotelRateModel();
        $supplier_details = $hotelrateMOdel->select('hotel_rate.hotel_code, hotel_rate.hotel')->join('supplier', 'supplier.Sup_Acc_No = hotel_rate.hotel', 'left')->where('hotel_rate.sdate <=', $date)->where('hotel_rate.edate >=', $date)->where('hotel_rate.location', $loc)->where('supplier.is_avalible', 1);

        $supplier_results = $supplier_details->findAll();

        return $this->response->setJSON($supplier_results);
    }


    // route : base_url/tour/tour/show_transfer/{id}
    public function show_transfer($id)
    {
        try {
            // Create a new HotelRateModel
            $transferMilageModel = new TransferMilageModel();
            $transfer_details = $transferMilageModel->where('id', $id)->first();

            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' => $transfer_details]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
    // route : base_url/tour/tour/show/{id}
    public function show($id)
    {
        try {
            // Create a new HotelRateModel
            $hotelrateMOdel = new HotelRateModel();
            $supplier_details = $hotelrateMOdel->select('supplier.Name,supplier.Sup_Acc_No,room_type.id,room_type.rtype')->join('supplier', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')->join('room_type', 'hotel_rate.room_type = room_type.id', 'left')->where('hotel_rate.hotel_code', $id)->first();

            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' => $supplier_details]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }



    // route : base_url/tour/tour/get_meal_prices
    public function get_meal_prices()
    {
        $hotel_code = $this->request->getGet('h_code');
        $room_type = $this->request->getGet('room_type');
        $meal_plan = $this->request->getGet('meal_plan');

        $hotelrateModel = new HotelRateModel();
        $data = $hotelrateModel->select('*')->where('hotel', $hotel_code)->where('room_type', $room_type)->first();

        if (!empty($data)) {
            $SGL = 0;
            $DBL = 0;
            $TPL = 0;

            switch ($meal_plan) {
                case "fb":
                    $SGL = $data['sfb'];
                    $DBL = $data['dfb'];
                    $TPL = $data['tfb'];
                    break;
                case "hb":
                    $SGL = $data['shb'];
                    $DBL = $data['dhb'];
                    $TPL = $data['thb'];
                    break;
                case "bb":
                    $SGL = $data['sbb'];
                    $DBL = $data['dbb'];
                    $TPL = $data['tbb'];
                    break;
            }

            if ((float) $SGL <= 0 && (float) $DBL <= 0 && (float) $TPL <= 0) {
                $this->registerLegacyFormGap((string) $hotel_code, $room_type, (string) $meal_plan, 'zero');
            }

            return $this->response->setJSON(['status' => 200, 'SGL' => $SGL, 'DBL' => $DBL, 'TPL' => $TPL]);
        } else {
            $this->registerLegacyFormGap((string) $hotel_code, $room_type, (string) $meal_plan, 'none');
            return $this->response->setJSON(['status' => 404, 'message' => 'No data found']);
        }
    }

    /**
     * Tell the Hotel Incomplete Work panel this hotel/room/meal has no usable legacy rate.
     * This screen has no date, so the Hotel Rates panel itself can't be checked here — only
     * this visibility is added. Never blocks the form; nothing above this call changes.
     */
    private function registerLegacyFormGap(string $hotelAcc, $room, string $meal, string $reason): void
    {
        if ($hotelAcc === '') {
            return;
        }
        try {
            (new \App\Libraries\HotelRates\WorkQueueService(\Config\Database::connect(), (string) session()->get('username')))
                ->registerLegacyFormGap($hotelAcc, $room, $meal, $reason);
        } catch (\Throwable $e) {
            log_message('error', 'Hotel Incomplete Work could not record the New Tour form rate gap for ' . $hotelAcc . ': ' . $e->getMessage());
        }
    }

    // route : base_url/tour/tour/get_hotel_details
    public function get_hotel_details()
    {
        $destination = $this->request->getGet('search_location');
        $date = $this->request->getGet('date');

        if (empty($destination)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Search location is required']);
        }

        $supplierModel = new SupplierModel();
        $data['hotels'] = $supplierModel->join('hotel_rate', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')->where('supplier.sup_cat', 1)->where('supplier.is_avalible', 1)->where('hotel_rate.sdate <=', $date)->where('hotel_rate.edate >=', $date)->like('supplier.Address', $destination)->groupBy('supplier.Name')->orderBy('supplier.Name', 'ASC')->findAll();

        if (!empty($data)) {
            return $this->response->setJSON(['status' => 200, 'data' => $data]);
        } else {
            return $this->response->setJSON(['status' => 404, 'message' => 'No data found']);
        }
    }

    public function get_hotel_type()
    {
        $destination = $this->request->getGet('hotelId');
        $date = $this->request->getGet('date');

        if (empty($destination)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Search location is required']);
        }

        $hotelrateModel = new HotelRateModel();
        $data['hotels_type'] = $hotelrateModel->select('room_type.*')->join('room_type', 'room_type.id = hotel_rate.room_type')->where('hotel_rate.sdate <=', $date)->where('hotel_rate.edate >=', $date)->where('hotel_rate.hotel', $destination)->findAll();

        if (!empty($data)) {
            return $this->response->setJSON(['status' => 200, 'data' => $data]);
        } else {
            return $this->response->setJSON(['status' => 404, 'message' => 'No data found']);
        }
    }


    // route : base_url/tour/tour/finalize_quotation
    public function finalize_quotation()
    {
        $list = $this->request->getPost('list');
        $list = json_decode(strval($list), true);

        if (count($list) > 0) {
            $TourQuotationsModel = new TourQuotationsModel();
            $quotation_id = 'QT' . date('YmdHis');
            $data = array();
            foreach ($list as $key => $value) {
                $data[] = array(
                    'quotation_id' => $quotation_id,
                    'hotel_code' => $value['hotel_code'],
                    'meal_code' => $value['meal_code'],
                    'hotel_acc' => $value['hotel_acc'],
                    'room_type' => $value['room_type_id'],
                    'tour_date' => $value['tour_date'],
                    'start_date' => $value['start_date'],
                    'end_date' => $value['end_date'],
                    'sgl' => $value['sgl'],
                    'dbl' => $value['dbl'],
                    'tpl' => $value['tpl'],
                    'created_time' => date('Y-m-d H:i:s'),
                    'created_user' => session()->get('username')
                );
            }

            $TourQuotationsModel->insertBatch($data);
            return $this->response->setJSON(['status' => 200, 'message' => 'Data saved successfully']);
        } else {
            return $this->response->setJSON(['status' => 404, 'message' => 'No data found']);
        }
    }

    // route : base_url/tour/tour/getTourId
    public function getTourId()
    {

        try {
            // Create a new HotelRateModel
            $invoiceNumberModel = new InvoiceNumberModel();
            $tourId = $invoiceNumberModel->incrementTourNo();

            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' => $tourId]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tour/tour/getTourId
    public function get_tour_data($t_id)
    {
        $TourQuotationsModel = new TourQuotationsModel();
        $consigneeModel = new ConsigneeModel();

        $data['quotation_details'] = $TourQuotationsModel->select('tour_quotations.*, transfer_milage.transfer_code as code, room_type.rtype')->join('transfer_milage', 'transfer_milage.id = tour_quotations.transfer_code', 'left')->join('room_type', 'room_type.id = tour_quotations.room_type', 'left')->where('tour_quotations.quotation_id', $t_id)->findAll();

        // Step 2: Get the quotation_id from the first result (assuming there is at least one result)
        if (!empty($data['quotation_details'])) {
            $quotation_id = $data['quotation_details'][0]['quotation_id'];

            // Step 3: Get results from the consignee table where tour_id equals quotation_id
            $consigneeResults = $consigneeModel->where('tour_id', $quotation_id)->findAll();

            // Step 4: Append the consignee results to the quotation details
            foreach ($data['quotation_details'] as &$detail) {
                $detail['consignee'] = $consigneeResults[0];
            }
        }

        return $this->response->setJSON(['data' => $data['quotation_details']]);
    }

    // route : base_url/tour/tour/insertQuatation
    public function insertConsignee()
    {

        $validation = \Config\Services::validation();

        $validation->setRules([
            'Tour_Id' => 'required|strip_tags',
            'start_date' => 'required|strip_tags',
            'end_date' => 'required|strip_tags',
            'consignee' => 'required|strip_tags',
            'email' => 'required|strip_tags',
            'country' => 'strip_tags',
            'con_code' => 'strip_tags',
            'contact' => 'required|strip_tags',

            'vehicle' => 'permit_empty|strip_tags',
            'vehicle_name' => 'permit_empty|strip_tags',
            'vehicle_rate' => 'permit_empty|strip_tags',

            'pass_count' => 'required|strip_tags',
            'agent' => 'required|strip_tags',
            'arrival_flight' => 'strip_tags',
            'departure_flight' => 'strip_tags',
            'manual_tour_id' => 'strip_tags',
            'hotel_only' => 'required|strip_tags',
            'sql' => 'permit_empty|strip_tags',
            'dbl' => 'permit_empty|strip_tags',
            'tpl' => 'permit_empty|strip_tags',
            'is_twin' => 'permit_empty|strip_tags',
        ]);

        if ($validation->withRequest($this->request)->run()) {

            $tour_id = $this->request->getVar('Tour_Id');
            $start_date = $this->request->getVar('start_date');
            $end_date = $this->request->getVar('end_date');
            $consignee = $this->request->getVar('consignee');
            $email = $this->request->getVar('email');
            $country = $this->request->getVar('country');
            $con_code = $this->request->getVar('con_code');
            $contact = $this->request->getVar('contact');
            $vehicle = $this->request->getVar('vehicle');
            $vehicle_name = $this->request->getVar('vehicle_name');
            $vehicle_rate = $this->request->getVar('vehicle_rate');
            $pass_count = $this->request->getVar('pass_count');
            $manual_id = $this->request->getVar('manual_tour_id');
            $agent = $this->request->getVar('agent');
            $agent_name = $this->request->getVar('agent_name');
            $arr_flight = $this->request->getVar('arrival_flight');
            $arr_flight_time = $this->request->getVar('arrival_flight_time');
            $depart_flight = $this->request->getVar('departure_flight');
            $depart_flight_time = $this->request->getVar('departure_flight_time');
            $is_hotel = $this->request->getVar('hotel_only');
            $sgl = $this->request->getVar('sgl');
            $dbl = $this->request->getVar('dbl');
            $tpl = $this->request->getVar('tpl');
            $is_twin = $this->request->getVar('is_twin');


            if ($is_hotel == 0) {
                if (empty($vehicle) || empty($vehicle_name) || empty($vehicle_rate)) {
                    return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data."]);
                }
            }

            $session = session();
            $username = $session->get('username');

            $ConsigneeModel = new ConsigneeModel();
            $exist_data = $ConsigneeModel->select("*")->where('tour_id', $tour_id)->get()->getRowArray();
            $data = [
                'tour_id' => $tour_id,
                'consignee' => $consignee,
                'country' => $country,
                'k_code' => $con_code,
                'mobile' => $contact,
                'email' => $email,
                'vehicle' => $vehicle,
                'vehicle_name' => $vehicle_name,
                'vehicle_rate' => $vehicle_rate,
                'passenger_count' => $pass_count,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'create_user' => $username,
                'create_time' => date('Y-m-d H:i:s'),
                'manual_id' => $manual_id,
                'agent' => $agent,
                'agent_name' => $agent_name,
                'arrival_flight' => $arr_flight,
                'departure_flight' => $depart_flight,
                'arrival_flight_time' => $arr_flight_time,
                'departure_flight_time' => $depart_flight_time,
                'sgl' => $sgl,
                'dbl' => $dbl,
                'tpl' => $tpl,
                'dbl_twin' => $is_twin,
                'hotel_only' => $is_hotel
            ];

            if (empty($exist_data)) {

                if ($ConsigneeModel->insert($data)) {
                    return $this->response->setJSON(["status" => 200, "message" => "Successfully Added"]);
                } else {
                    return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                }
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } else {
            // Bad Request
            return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data."]);
        }
    }

    public function Quatation()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'trans_tour_id' => 'required',
            'trans_row_id' => 'required',
            't_date' => 'required',
            // 'transfer_code' => 'permit_empty',
            'pick_location' => 'required',
            'drop_location' => 'required',
            'gdistance' => 'required',
            'distance' => 'required',
            'hotel' => 'permit_empty',
            // 'hotel_code' => 'permit_empty',
            'room_type' => 'permit_empty',
            'room_type_id' => 'permit_empty',
            'meal_code' => 'permit_empty',
            'sgl' => 'permit_empty',
            'dbl' => 'permit_empty',
            'tpl' => 'permit_empty',
            'up_flag' => 'required',
            'slct_htl' => 'permit_empty',
            'slct_htl_c' => 'permit_empty',
            'extrakm' => 'permit_empty',
            'drop_city' => 'permit_empty',
        ]);

        if ($validation->withRequest($this->request)->run()) {
            $request = $this->request->getPost();

            $tour_id = $request['trans_tour_id'];
            $row_id = $request['trans_row_id'];
            $t_date = $request['t_date'];
            $transfer_code = isset($request['transfer_code']) ? strip_tags($request['transfer_code']) : null;
            $pick_location = strip_tags($request['pick_location']);
            $drop_location = strip_tags($request['drop_location']);
            $gdistance = strip_tags($request['gdistance']);
            $distance = strip_tags($request['distance']);
            $extrakm = strip_tags($request['extrakm']);
            $hotel = isset($request['hotel']) ? strip_tags($request['hotel']) : null;
            $hotel_code = isset($request['hotel_code']) ? strip_tags($request['hotel_code']) : null;

            $room_type = isset($request['room_type']) ? strip_tags($request['room_type']) : null;
            $room_type_id = isset($request['room_type_id']) ? strip_tags($request['room_type_id'])  : null;

            $meal_code = isset($request['meal_code']) ? strip_tags($request['meal_code']) : null;
            $sgl = isset($request['sgl']) ? strip_tags($request['sgl']) : null;
            $dbl = isset($request['dbl']) ? strip_tags($request['dbl']) : null;
            $tpl = isset($request['tpl']) ? strip_tags($request['tpl']) : null;

            $sglcount = isset($request['count_sgl']) ? strip_tags($request['count_sgl']) : null;
            $dblcount = isset($request['count_dbl']) ? strip_tags($request['count_dbl']) : null;
            $tplcount = isset($request['count_tpl']) ? strip_tags($request['count_tpl']) : null;

            $flag = strip_tags($request['up_flag']);
            $slct_htl = strip_tags($request['slct_htl']);
            $slct_htl_c = strip_tags($request['slct_htl_c']);

            $hotel_only = strip_tags($request['is_hotel']);

            $drop_city = strip_tags($request['drop_city']);

            $real_hotel = ($hotel !== null && $hotel !== '') ? $hotel : $slct_htl;
            $real_hotel_code = ($hotel_code !== null && $hotel_code !== '') ? $hotel_code : $slct_htl_c;
            $room_type = ($room_type !== null && $room_type !== '') ? $room_type : $room_type_id;

            $quotation_id = $tour_id;
            $quotation_day = $row_id;

            $session = session();
            $username = $session->get('username');

            if (strpos($pick_location, ', Sri Lanka') !== false) {
                $pick_location = str_replace(', Sri Lanka', '', $pick_location);
            }

            if (strpos($drop_location, ', Sri Lanka') !== false) {
                $drop_location = str_replace(', Sri Lanka', '', $drop_location);
            }

            if (!empty($real_hotel)) {
                if (!empty($sglcount) && $sgl == 0.00) {
                    return $this->response->setJSON(["status" => 400, "message" => "Please provide valid SGL Rate."]);
                }
                if (!empty($dblcount) && $dbl == 0.00) {
                    return $this->response->setJSON(["status" => 400, "message" => "Please provide valid DBL Rate."]);
                }
                if (!empty($tplcount) && $tpl == 0.00) {
                    return $this->response->setJSON(["status" => 400, "message" => "Please provide valid TPL Rate."]);
                }
            }

            $supplierModel = new SupplierModel();
            $hotel_name = $supplierModel->select('Name')->where('Sup_Acc_No', $real_hotel)->find();

            $TourQuotationsModel = new TourQuotationsModel();
            $exist_data = $TourQuotationsModel->select("*")->where('quotation_id', $quotation_id)->where('quotation_day', $quotation_day)->where('hotel_acc !=', '')->get()->getRowArray();

            $tourSubLocationsModel = new \App\Models\TourSubLocationsModel();
            $sub_location = $tourSubLocationsModel->where('quotation_id', $tour_id)->where('tour_row', $quotation_day)->where('date_id', $t_date)->findAll();

            $km_sum = 0;
            $extra_km_sum = 0;

            $distance = floatval($distance);
            $extrakm = floatval($extrakm);

            foreach ($sub_location as $sub) {
                $km_sum += $sub['km'];
                $extra_km_sum += $sub['extra_km'];
            }

            $hot_name = isset($hotel_name[0]['Name']) ? $hotel_name[0]['Name'] : '';
            $data = [
                'quotation_id' => $tour_id,
                'quotation_day' => $quotation_day,
                'distance_type' => 2,
                'transfer_code' => $transfer_code,
                'from_location' => $pick_location,
                'to_location' => $drop_location,
                'gdistance' => $gdistance,
                // 'distance' => $distance,
                // 'extra_kilo' => $extrakm,
                'hotel_code' => $real_hotel_code,
                'meal_code' => $meal_code,
                'hotel_acc' => $real_hotel,
                'hotel_name' => $hot_name,
                'room_type' => $room_type,
                'room_type_id' => $room_type_id,
                'tour_date' => $t_date,
                'sgl' => $sgl,
                'dbl' => $dbl,
                'tpl' => $tpl,
                "created_time" => date('Y-m-d H:i:s'),
                "created_user" => $username,
                "det_flag" => 1,
            ];

            // if ($km_sum > 0) {
            $data['distance'] = $km_sum + $distance;
            // }

            // if ($extra_km_sum > 0) {
            $data['extra_kilo'] = $extra_km_sum + $extrakm;
            // }

            if (empty($exist_data) && ($flag == 0)) {
                if ($TourQuotationsModel->insert($data)) {
                    // Call the new_create new_create method in Touting Registry after inserting
                    if ($hotel_only == 0 && $distance > 0) {
                        $TransferMilageController = new TransferMilageController();
                        $cxf = $TransferMilageController->new_create($pick_location, $drop_location, $gdistance, $distance);
                    }

                    $ItineraryController = new ItineraryController();
                    $ItineraryController->add_itinerary($tour_id, $quotation_day, $t_date, $pick_location, $drop_location, $drop_city, $hot_name, $room_type, $meal_code, 0);

                    return $this->response->setJSON(["status" => 200, "message" => "Successfully Added", "tour_id" => $tour_id, "row_id" => $row_id]);
                } else {
                    return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                }
            } else if (!empty($exist_data) && ($flag == 1)) {
                if ($TourQuotationsModel->where('quotation_id', $quotation_id)->where('quotation_day', $quotation_day)->set($data)->update()) {
                    // Call the new_create new_create method in Touting Registry after inserting
                    if ($hotel_only == 0 && $distance > 0) {
                        $TransferMilageController = new TransferMilageController();
                        $TransferMilageController->new_create($pick_location, $drop_location, $gdistance, $distance);
                    }
                    return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated", "tour_id" => $tour_id, "row_id" => $row_id]);
                } else {
                    return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                }
            }
        } else {
            // Bad Request
            return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data."]);
        }
    }


    public function delete()
    {
        try {
            $date = $this->request->getGet('date');
            $tour = $this->request->getGet('tour');
            $row = $this->request->getGet('row');

            $tourQuotationsModel = new TourQuotationsModel();
            $tourSubLocationsModel = new TourSubLocationsModel();
            $tourActivityModel = new TourActivityModel();
            $tourItineraryModel = new TourItineraryModel();

            if ($tourQuotationsModel->where('quotation_id', $tour)->where('quotation_day', $row)->where('tour_date', $date)->delete()) {

                $tourSubLocationsModel->where('quotation_id', $tour)->where('tour_row', $row)->where('date_id', $date)->delete();
                $tourActivityModel->where('tour_id', $tour)->where('tour_row', $row)->where('tour_date', $date)->delete();
                $tourItineraryModel->where('tour_id', $tour)->where('tour_row', $row)->where('tour_date', $date)->delete();
                return $this->response->setJSON(["status" => 200, "message" => "Delete successfully!"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    // route : base_url/tour/tour/room_count_add
    public function room_count_add()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'room_tour_id' => 'required|strip_tags'
        ]);

        if ($validation->withRequest($this->request)->run()) {
            $tour_id = $this->request->getVar('room_tour_id');
            $sgl = $this->request->getVar('sgl_room');
            $dbl = $this->request->getVar('dbl_room');
            $tpl = $this->request->getVar('tpl_room');
            $twn = $this->request->getVar('is_twn');

            $consigneeModel = new ConsigneeModel();

            $data = [
                'sgl' => $sgl,
                'dbl' => $dbl,
                'tpl' => $tpl,
                'dbl_twin' => $twn
            ];

            if ($consigneeModel->where('tour_id', $tour_id)->set($data)->update()) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated", "tour_id" => $tour_id]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } else {
            // Bad Request
            return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data."]);
        }
    }

    // route : base_url/tour/tour/hotel_only
    public function hotel_only()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'tour_id' => 'required|strip_tags',
            'hotel_only' => 'required|strip_tags'
        ]);

        if ($validation->withRequest($this->request)->run()) {
            $tour_id = $this->request->getVar('tour_id');
            $hotelonly = $this->request->getVar('hotel_only');

            $consigneeModel = new ConsigneeModel();

            $data = [
                'hotel_only' => $hotelonly
            ];

            if ($consigneeModel->where('tour_id', $tour_id)->set($data)->update()) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated", "tour_id" => $tour_id]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } else {
            // Bad Request
            return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data."]);
        }
    }

    // route : base_url/tour/tour/edit_info
    public function edit_info($date, $tour, $row)
    {
        try {

            $TourQuotationsModel = new TourQuotationsModel();
            $quotation_details = $TourQuotationsModel->select('tour_quotations.*, room_type.rtype')->join('room_type', 'room_type.id = tour_quotations.room_type_id', 'left')
                ->where('tour_quotations.quotation_id', $tour)->where('tour_quotations.quotation_day', $row)->where('tour_quotations.tour_date', $date)->first();

            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' => $quotation_details]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tour/tour/finalize
    public function finalize($tour)
    {
        try {
            $data = [
                'finalize' => 1,
            ];

            $TourQuotationsModel = new TourQuotationsModel();
            $ConsigneeModel = new ConsigneeModel();
            if (($TourQuotationsModel->set($data)->where('quotation_id', $tour)->update()) && ($ConsigneeModel->set($data)->where('tour_id', $tour)->update())) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Finalized!", "tour_id" => $tour]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tour/tour/load_details
    public function load_details()
    {
        try {

            $ConsigneeModel = new ConsigneeModel();
            $quotation_details = $ConsigneeModel->select('*')
                ->where('tour_id', $this->request->getGet('tourId'))->first();


            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' => $quotation_details]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tour/tour/load_vehicles
    public function load_vehicles()
    {
        try {

            $VehicleModel = new VehicleModel();
            $vehicle_details = $VehicleModel->select('*')
                ->orderBy('passenger_count', 'ASC')->findAll();


            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' => $vehicle_details]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tour/tour/searchFull
    public function searchFull($tour, $selectDate)
    {
        try {

            $TourQuotationsModel = new TourQuotationsModel();
            $Full_details = $TourQuotationsModel->select('tour_quotations.*, room_type.rtype')->join('room_type', 'room_type.id = tour_quotations.room_type', 'left')
                ->where('tour_quotations.quotation_id', $tour)->where('tour_quotations.tour_date', $selectDate)->findAll();

            $date = new \DateTime($selectDate);
            $date->modify('-1 day');
            $previousDate = $date->format('Y-m-d');

            $previousLocation = $TourQuotationsModel->select('to_location')->where('tour_quotations.quotation_id', $tour)->where('tour_quotations.tour_date', $previousDate)->find();

            $Full_details['previous_destination'] = isset($previousLocation[0]['to_location']) ? $previousLocation[0]['to_location'] : '';

            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' => $Full_details]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tour/tour/updateEndDate
    public function updateEndDate($date, $tour)
    {

        try {
            $data = [
                'end_date' => $date,
            ];

            $ConsigneeModel = new ConsigneeModel();
            if ($ConsigneeModel->set($data)->where('tour_id', $tour)->update()) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Added One Day!"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tour/tour/tour_details_copy
    public function tour_copy()
    {
        try {

            $date = $this->request->getPost('date');
            $tour = $this->request->getPost('tour');
            $row = $this->request->getPost('row');
            $nextd = $this->request->getPost('nextd');

            $TourQuotationsModel = new TourQuotationsModel();
            $quotation_details = $TourQuotationsModel->select('*')
                ->where('quotation_id', $tour)->where('quotation_day', $row)->where('tour_date', $date)->first();

            $data = [
                'quotation_id' => $tour,
                'quotation_day' => $quotation_details['quotation_day'] + 1,
                'meal_code' => $quotation_details['meal_code'],
                'hotel_acc' => $quotation_details['hotel_acc'],
                'hotel_name' => $quotation_details['hotel_name'],
                'room_type' => $quotation_details['room_type'],
                'room_type_id' => $quotation_details['room_type_id'],
                'tour_date' => $nextd,
                'sgl' => $quotation_details['sgl'],
                'dbl' => $quotation_details['dbl'],
                'tpl' => $quotation_details['tpl'],
                'created_time' => date('Y-m-d H:i:s'),
                'created_user' => $quotation_details['created_user'],
                'distance_type' => $quotation_details['distance_type'],
                'det_flag' => 1,
                'distance' => 0,
                'from_location' => $quotation_details['to_location'],
                'to_location' => $quotation_details['to_location'],
                'gdistance' => $quotation_details['gdistance'],
                'extra_kilo' => 0,
                'added_time' => date('Y-m-d H:i:s'),
                'added_by' => $quotation_details['added_by'],
            ];

            if ($TourQuotationsModel->insert($data)) {
                $itineraryController = new ItineraryController();
                $re = $itineraryController->add_itinerary($tour, $quotation_details['quotation_day'] + 1, $nextd, 1, '', '', '', '', '', '');
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Added One Day!"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tour/tour/getConsigneeDetailsByKeyword
    public function getConsigneeDetailsByKeyword()
    {
        $keyword = $this->request->getGet('term');

        $sugg_json = array();
        $json_row = array();
        $key = preg_replace('/\s+/', ' ', $keyword);

        $ConsigneeModel = new ConsigneeModel();
        $bankArr = $ConsigneeModel->like('consignee', $key)->groupBy('consignee, email')->orderBy('ID')->findAll();

        foreach ($bankArr as $recResult) {
            $json_row["value"] = $recResult['consignee'];
            $json_row["label"] = $recResult['consignee'];
            $json_row["label0"] = $recResult['email'];
            $json_row["label1"] = $recResult['mobile'];
            $json_row["label2"] = $recResult['k_code'];
            $json_row["label3"] = $recResult['country'];
            array_push($sugg_json, $json_row);
        }

        return json_encode($sugg_json, JSON_UNESCAPED_SLASHES);
    }

    public function getConsigneeDetails($agent)
    {
        try {
            // Create a new EntranceFeeModel instance
            $CustomerModel = new CustomerModel();

            // Get the entrance fee data
            $agentdetail = $CustomerModel->where('Sup_Acc_No', $agent)->first();

            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' => $agentdetail]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function getPassengerDetails($tour)
    {
        try {
            // Create a new EntranceFeeModel instance
            $PassengerModel = new PassengerDetailsModel();

            // Get the entrance fee data
            $passengercount = $PassengerModel->where('tour_id', $tour)->countAllResults();

            $consigneeModel = new ConsigneeModel();
            $pax_count = $consigneeModel->select('*')->where('tour_id', $tour)->first();

            $combined_result = [
                'pax_count' => $pax_count,
                'passenger' => $passengercount
            ];

            // Return the entrance fee data
            return $this->response->setJSON(['data' => $combined_result]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function pax_create()
    {
        try {
            $pax = $this->request->getPost('passenger');
            $tour_id = $this->request->getPost('modal_tour_id');
            $pax_count = $this->request->getPost('pax_coun');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'passenger' => 'required',
                'modal_tour_id' => 'required'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Create a new RoomTypeModel instance
            $PaxModel = new PassengerDetailsModel();

            if ($pax_select = $PaxModel->where(['tour_id' => $tour_id])->countAllResults()) {
                // Return an error response
                if ($pax_select >= $pax_count) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Passenger Count Exceded']);
                }
            }

            // Set the data for the new entrance fee
            $data = [
                'pass_name' => $pax,
                'tour_id' => $tour_id
            ];

            // Insert
            if ($PaxModel->insert($data)) {
                // Return a success response
                return $this->response->setJSON(['status' => 201, 'message' => 'Passenger created successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // Update Passenger Details
    public function pax_update()
    {
        try {
            $pax_id = $this->request->getPost('pax_id');
            $pax_name = $this->request->getPost('passenger');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'passenger' => 'required',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Create a new RoomTypeModel instance
            $PaxModel = new PassengerDetailsModel();

            // Update the passenger details
            $data = [
                'pass_name' => $pax_name,

            ];

            if ($PaxModel->update($pax_id, $data)) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Passenger updated successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function activity_create()
    {
        try {
            $place = $this->request->getPost('place');
            $place_name = $this->request->getPost('place_name');
            $place_fee = $this->request->getPost('place_fee');
            $place_code = $this->request->getPost('place_code');
            $activity_tour_id = $this->request->getPost('activity_tour_id');
            $activity_date = $this->request->getPost('activity_date');
            $activity_row = $this->request->getPost('activity_row');
            $pax = $this->request->getPost('pax_counts');
            $tot_fee = $place_fee * $pax;

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'place' => 'required',
                'activity_tour_id' => 'required',
                'activity_date' => 'required',
                'activity_row' => 'required'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Get Ref No in Consignee Table
            $consigneeModel = new \App\Models\ConsigneeModel();
            $Ref_no = $consigneeModel->incrementRefno($activity_tour_id);

            // Create a new RoomTypeModel instance
            $ActivityModel = new TourActivityModel();

            $data = [
                'tour_id' => $activity_tour_id,
                'tour_row' => $activity_row,
                'tour_date' => $activity_date,
                'name' => $place,
                'fee' => $tot_fee,
                'code' => $place_code,
                'ref_no' => $Ref_no
            ];

            // Insert
            if ($ActivityModel->insert($data)) {

                $TourQuotationsModel = new TourQuotationsModel();
                $current = $TourQuotationsModel->select('extra_act_fee')->where('quotation_id', $activity_tour_id)->where('quotation_day', $activity_row)->where('tour_date', $activity_date)->first();

                $current_fee = 0;

                if (!empty($current)) {
                    $current_fee = $current['extra_act_fee'];
                }

                $new_fee = $current_fee + $tot_fee;

                $data = [
                    'extra_act_fee' => $new_fee
                ];

                $TourQuotationsModel->set($data)->where('quotation_id', $activity_tour_id)->where('quotation_day', $activity_row)->where('tour_date', $activity_date)->update();

                $ItineraryController = new ItineraryController();
                $ItineraryController->add_itinerary_activity($activity_tour_id, $activity_row, $activity_date, '', $place_name, $place_name, '', '', '', $Ref_no);
                // Return a success response
                return $this->response->setJSON(['status' => 201, 'message' => 'Activity created successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function activity_create_loc()
    {
        try {
            $place = $this->request->getPost('place');
            $place_name = $this->request->getPost('place_name');
            $place_code = $this->request->getPost('place_code');
            $activity_tour_id = $this->request->getPost('activity_tour_id');
            $activity_date = $this->request->getPost('activity_date');
            $activity_row = $this->request->getPost('activity_row');
            $location_ref = $this->request->getPost('location_ref');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'place' => 'required',
                'activity_tour_id' => 'required',
                'activity_date' => 'required',
                'activity_row' => 'required'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Get Ref No in Consignee Table
            $consigneeModel = new \App\Models\ConsigneeModel();
            $Ref_no = $consigneeModel->incrementRefno($activity_tour_id);

            // Create a new RoomTypeModel instance
            $ActivityModel = new TourActivityModel();

            $data = [
                'tour_id' => $activity_tour_id,
                'tour_row' => $activity_row,
                'tour_date' => $activity_date,
                'name' => $place,
                'code' => $place_code,
                'ref_no' => $Ref_no
            ];

            // Insert
            if ($ActivityModel->insert($data)) {

                $ItineraryController = new ItineraryController();
                $ItineraryController->add_itinerary_activity_location($activity_tour_id, $activity_row, $activity_date, $location_ref, $place_name, '', '', '', '', $Ref_no);
                // Return a success response
                return $this->response->setJSON(['status' => 201, 'message' => 'Activity created successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }



    public function pax_all($tour)
    {
        try {
            // Create a new EntranceFeeModel instance
            $PassengerModel = new PassengerDetailsModel();
            // Get the entrance fee data
            $passenger = $PassengerModel->where('tour_id', $tour)->findAll();


            // Return the entrance fee data
            return json_encode($passenger);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function activity_all($tour, $row, $date)
    {
        try {
            // Create a new EntranceFeeModel instance
            $TouractivityModel = new TourActivityModel();
            // Get the entrance fee data
            $activity = $TouractivityModel->select('tour_activity.*, entrance_fee.name as place_name')->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'left')->where('tour_id', $tour)->where('tour_row', $row)->where('tour_date', $date)->findAll();


            // Return the entrance fee data
            return json_encode($activity);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function itinerary_all($tour, $row, $date)
    {
        try {
            // Create a new EntranceFeeModel instance
            $tourItineraryModel = new TourItineraryModel();
            $itinerary = $tourItineraryModel->select('*')
                ->where('tour_id', $tour)
                ->where('tour_row', $row)
                ->where('tour_date', $date)
                ->orderBy('order_id', 'ASC')
                ->findAll();

            // log_message('critical', 'Itinerary Data: ' . json_encode($mergedData));
            return json_encode($itinerary);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
    public function activity_delete()
    {
        try {
            $id = $this->request->getGet('id');
            $ref_no = $this->request->getGet('ref_no');

            $tourActivityModel = new TourActivityModel();
            $activity = $tourActivityModel->where('id', $id)->first();


            $tour_id = $activity['tour_id'];
            $tour_row_id = $activity['tour_row'];
            $amount = $activity['fee'];

            $tourQuotationsModel = new TourQuotationsModel();
            $quotation = $tourQuotationsModel->select('extra_act_fee')->where('quotation_id', $tour_id)->where('quotation_day', $tour_row_id)->first();

            $activity_fee = $quotation['extra_act_fee'];
            $new_amount = $activity_fee - $amount;

            if ($tourActivityModel->delete($id)) {
                $tourQuotationsModel->where('quotation_id', $tour_id)->where('quotation_day', $tour_row_id)->set('extra_act_fee', $new_amount)->update();
                $tourItineraryModel = new TourItineraryModel();
                $tourItineraryModel->where('tour_id', $tour_id)->where('tour_row', $tour_row_id)->where('tour_date', $activity['tour_date'])->where('ref_no', $ref_no)->delete();
                return $this->response->setJSON(["status" => 200, "message" => "Activity Delete successfully!"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }
    
    public function activity_loc_delete()
    {
        try {
            $id = $this->request->getGet('id');
            $ref_no = $this->request->getGet('ref_no');

            $tourItineraryModel = new TourItineraryModel();
            $tourActivityModel = new TourActivityModel();

            $activity = $tourItineraryModel->where('id', $id)->first();


            $tour_id = $activity['tour_id'];
            $tour_row_id = $activity['tour_row'];
            $tour_date = $activity['tour_date'];

            if ($tourItineraryModel->delete($id)) {
                $tourActivityModel->where('tour_id', $tour_id)->where('tour_row', $tour_row_id)->where('tour_date', $tour_date)->where('ref_no', $ref_no)->delete();
                return $this->response->setJSON(["status" => 200, "message" => "Activity Delete successfully!"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    public function pax_delete()
    {
        try {
            $id = $this->request->getGet('id');

            $PassengerModel = new PassengerDetailsModel();
            if ($PassengerModel->delete($id)) {

                return $this->response->setJSON(["status" => 200, "message" => "Passenger Delete successfully!"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }
    // route : base_url/tour/tour/getEmailDetailsByKeyword
    public function getEmailDetailsByKeyword()
    {
        $keyword = $this->request->getGet('term');

        $sugg_json = array();
        $json_row = array();
        $key = preg_replace('/\s+/', ' ', $keyword);

        $ConsigneeModel = new ConsigneeModel();
        $bankArr = $ConsigneeModel->like('email', $key)->groupBy('consignee, email')->orderBy('ID')->findAll();

        foreach ($bankArr as $recResult) {
            $json_row["value"] = $recResult['email'];
            $json_row["label"] = $recResult['email'];
            $json_row["label0"] = $recResult['consignee'];
            $json_row["label1"] = $recResult['mobile'];
            $json_row["label2"] = $recResult['k_code'];
            $json_row["label3"] = $recResult['country'];
            array_push($sugg_json, $json_row);
        }

        return json_encode($sugg_json, JSON_UNESCAPED_SLASHES);
    }

    public function actual_distance()
    {

        $from = $this->request->getVar('from');
        $to = $this->request->getVar('to');
        $pick = $this->request->getVar('frompick');
        $drop = $this->request->getVar('todrop');

        $transferMilageModel = new TransferMilageModel();
        $Full_details = $transferMilageModel->like('from', $pick . '%')->like('to', $drop . '%')->findAll();

        $extraKilometersModel = new ExtraKilometersModels();
        $extra_details = $extraKilometersModel->where('city', $to)->findAll();

        // if ($Full_details || $extra_details) {
        $merged_data = [
            'Full_details' => $Full_details,
            'Extra_details' => $extra_details
        ];
        // }
        if ($merged_data) {
            return $this->response->setJSON(["status" => 200, "message" => "", "data" => $merged_data]);
        } else {
            return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
        }
    }
}
