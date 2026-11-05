<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

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
        $consigneeModel->where('finalize', 0);
        $data['rowCount'] = $consigneeModel->countAllResults();

        $data['pending_quotations'] = $consigneeModel->orderBy('tour_id', 'ASC')->findAll();

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

        $vehicleModel = new VehicleModel();
        $data['vehicles'] = $vehicleModel->findAll();

        $id = $this->request->getGet('tour_id');
        $start_date = $this->request->getGet('start');
        $end_date = $this->request->getGet('end');

        $data['quotation_id'] = $id;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        if (!empty($id)) {
            $TourQuotationsModel = new TourQuotationsModel();
            // $data['quotation_details'] = $TourQuotationsModel->where('quotation_id', $id)->findAll();
            $data['quotation_details'] = $TourQuotationsModel->select('tour_quotations.*, transfer_milage.transfer_code as code')->join('transfer_milage', 'transfer_milage.id = tour_quotations.transfer_code', 'left')->where('tour_quotations.quotation_id', $id)->findAll();
            // log_message('critical',$TourQuotationsModel->getLastQuery());
        }

        return view('pages/tour/tour', $data);
    }

    // route : base_url/tour/tour/Test_map
    // public function Test_map()
    // {
    //     $data = array();

    //     // Load menu data using the custom library
    //     $menuLoader = new MenuLoader();
    //     $data = $menuLoader->loadMenuData();

    //     $hotelRateModel = new HotelRateModel();
    //     $data['hotel'] = $hotelRateModel->select('supplier.Name, hotel_rate.hotel')->join('supplier', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')->orderBy('supplier.Name', 'ASC')->findAll();

    //     $hotelRateModel = new HotelRateModel();
    //     $data['hotel_code'] = $hotelRateModel->select('hotel_code')->orderBy('hotel_code', 'ASC')->findAll();

    //     $supplierModel = new SupplierModel();
    //     $data['load_data'] = $supplierModel->where('sup_cat', 1)->findAll();

    //     $roomTypeModel = new RoomTypeModel();
    //     $data['roomtype'] = $roomTypeModel->findAll();

    //     $transferMilageModel = new TransferMilageModel();
    //     $data['transfer_code'] = $transferMilageModel->findAll();

    //     // $id = $this->request->getGet('id');
    //     // if (!empty($id)) {
    //     // 	$data['customer'] = $CustomerModel->where('ID', $id)->first();
    //     // }

    //     return view('pages/tour/test_map', $data);
    // }

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
        $hotel_details = $supplierModel->join('hotel_rate', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')->where('supplier.sup_cat', 1)->where('hotel_rate.sdate <=', $date)->where('hotel_rate.edate >=', $date)->orderBy('supplier.Name', 'ASC');

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

        // log_message('critical', 'Search Location: ' . $hotel_results['search_location']);
        // log_message('critical', 'Last Query: ' . $supplierModel->getLastQuery());
        // log_message('critical', 'Hotel Results: ' . json_encode($hotel_results['hotels']));

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

        // log_message('critical', $hotelrateMOdel->getLastQuery());
        // log_message('critical', json_encode($supplier_results)); 
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
        $data = $hotelrateModel->select('*')->where('hotel_code', $hotel_code)->where('room_type', $room_type)->first();

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

            return $this->response->setJSON(['status' => 200, 'SGL' => $SGL, 'DBL' => $DBL, 'TPL' => $TPL]);
        } else {
            return $this->response->setJSON(['status' => 404, 'message' => 'No data found']);
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

        $hotelrateModel = new HotelRateModel();
        $data['hotel_code'] = $hotelrateModel->select('hotel_code')->where('sdate <=', $date)->where('edate >=', $date)->like('location', $destination)->findAll();

        // log_message('critical', 'Last Query2: ' . $hotelrateModel->getLastQuery());

        $supplierModel = new SupplierModel();
        $data['hotels'] = $supplierModel->join('hotel_rate', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')->where('supplier.sup_cat', 1)->where('hotel_rate.sdate <=', $date)->where('hotel_rate.edate >=', $date)->like('supplier.Address', $destination)->orderBy('supplier.Name', 'ASC')->findAll();

        // log_message('critical', 'Last Query3: ' . $supplierModel->getLastQuery());

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

        $data['quotation_details'] = $TourQuotationsModel->select('tour_quotations.*, transfer_milage.transfer_code as code')->join('transfer_milage', 'transfer_milage.id = tour_quotations.transfer_code', 'left')->where('tour_quotations.quotation_id', $t_id)->findAll();

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
        // log_message('critical',$TourQuotationsModel->getLastQuery());

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
            'vehicle' => 'required|strip_tags',
            'vehicle_name' => 'required|strip_tags',
            'vehicle_rate' => 'required|strip_tags',
            'pass_count' => 'required|strip_tags',

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
                "created_user" => $username,
                "created_time" => date('Y-m-d H:i:s')
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

    // route : base_url/tour/tour/insertQuatation
    public function insertQuatation()
    {

        $validation = \Config\Services::validation();
        $validation->setRules([
            'tour_id' => 'required|strip_tags',
            'row_id' => 'required|strip_tags',
            'hotel' => 'strip_tags',
            'hotel_code' => 'strip_tags',
            'date' => 'required|strip_tags',
            'room_type' => 'required|strip_tags',
            'room_type_id' => 'required|strip_tags',
            'meal_code' => 'required|strip_tags',
            'sgl' => 'strip_tags',
            'dbl' => 'strip_tags',
            'tpl' => 'strip_tags',
            'up_flag' => 'strip_tags',
            'slct_htl' => 'required|strip_tags',
            'slct_htl_c' => 'required|strip_tags',

        ]);

        if ($validation->withRequest($this->request)->run()) {

            $tour_id = $this->request->getVar('tour_id');
            $row_id = $this->request->getVar('row_id');
            $hotel = $this->request->getVar('hotel');
            $hotel_code = $this->request->getVar('hotel_code');
            $date = $this->request->getVar('date');
            $room_type = $this->request->getVar('$room_type');
            $room_type_id = $this->request->getVar('room_type_id');
            $meal_code = $this->request->getVar('meal_code');
            $sgl = $this->request->getVar('sgl');
            $dbl = $this->request->getVar('dbl');
            $tpl = $this->request->getVar('tpl');
            $flag = $this->request->getVar('up_flag');
            $slct_htl = $this->request->getVar('slct_htl');
            $slct_htl_c = $this->request->getVar('slct_htl_c');


            if ($hotel == '' || $hotel == null) {
                $real_hotel = $slct_htl;
            } else {
                $real_hotel = $hotel;
            }

            if ($hotel_code == '' || $hotel_code == null) {
                $real_hotel_code = $slct_htl_c;
            } else {
                $real_hotel_code = $hotel_code;
            }

            $quotation_id = $tour_id;
            $quotation_day = $row_id;

            $session = session();
            $username = $session->get('username');

            $supplierModel = new SupplierModel();
            $hotel_name = $supplierModel->select('Name')->where('Sup_Acc_No', $real_hotel)->find();

            $TourQuotationsModel = new TourQuotationsModel();
            $exist_data = $TourQuotationsModel->select("*")->where('quotation_id', $quotation_id)->where('quotation_day', $quotation_day)->where('hotel_acc !=', '')->get()->getRowArray();


            $data = [
                'hotel_code' => $real_hotel_code,
                'meal_code' => $meal_code,
                'hotel_acc' => $real_hotel,
                'hotel_name' => $hotel_name[0]['Name'],
                'room_type' => $room_type,
                'room_type_id' => $room_type_id,
                'sgl' => $sgl,
                'dbl' => $dbl,
                'tpl' => $tpl,
                "created_time" => date('Y-m-d H:i:s'),
                "created_user" => $username,
                "det_flag" => 1,
            ];

            if (empty($exist_data)) {

                if ($TourQuotationsModel->where('quotation_id', $quotation_id)->where('quotation_day', $quotation_day)->set($data)->update()) {
                    return $this->response->setJSON(["status" => 200, "message" => "Successfully Added", "tour_id" => $tour_id, "row_id" => $row_id]);
                } else {
                    return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                }
            } else {

                if ($TourQuotationsModel->where('quotation_id', $quotation_id)->where('quotation_day', $quotation_day)->set($data)->update()) {
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

    // route : base_url/tour/insertTransportQuatation
    public function insertTransportQuatation()
    {

        $validation = \Config\Services::validation();
        $validation->setRules([
            'trans_tour_id' => 'required|strip_tags',
            'trans_row_id' => 'required|strip_tags',
            'distance_type1' => 'required|strip_tags',
            'trans_date' => 'required|strip_tags',
        ]);

        if ($validation->withRequest($this->request)->run()) {

            $tour_id = $this->request->getVar('trans_tour_id');
            $row_id = $this->request->getVar('trans_row_id');
            $distance_type = $this->request->getVar('distance_type1');
            $transfer_code = $this->request->getVar('transfer_code');
            $transfer = $this->request->getVar('transfer');
            $distance = $this->request->getVar('distance');
            $from_location = $this->request->getVar('pick_location');
            $to_location = $this->request->getVar('drop_location');
            $gdistance = $this->request->getVar('gdistance');
            $date = $this->request->getVar('trans_date');

            // $quotation_id = 'QT-'. $tour_id;
            // $quotation_day = $tour_id.'-'.$row_id;

            // $address = $this->request->getVar('address');
            // if address has ', Sri Lanka' remove it
            if (strpos($from_location, ', Sri Lanka') !== false) {
                $from_location = str_replace(', Sri Lanka', '', $from_location);
            }

            if (strpos($to_location, ', Sri Lanka') !== false) {
                $to_location = str_replace(', Sri Lanka', '', $to_location);
            }

            $session = session();
            $username = $session->get('username');

            $TourQuotationsModel = new TourQuotationsModel();
            $exist_data = $TourQuotationsModel->select("*")->where('quotation_id', $tour_id)->where('quotation_day', $row_id)->where('distance_type !=', '')->get()->getRowArray();

            $data = [
                'quotation_id' => $tour_id,
                'quotation_day' => $row_id,
                'tour_date' => $date,
                'distance_type' => $distance_type,
                'transfer_code' => $transfer_code,
                'transfer' => $transfer,
                'distance' => $distance,
                'from_location' => $from_location,
                'to_location' => $to_location,
                'gdistance' => $gdistance,
                "added_time" => date('Y-m-d H:i:s'),
                "added_by" => $username,
                "trans_flag" => 1,
            ];

            if (empty($exist_data)) {

                if ($TourQuotationsModel->insert($data)) {
                    return $this->response->setJSON(["status" => 200, "message" => "Successfully Added", "tour_id" => $tour_id, "row_id" => $row_id]);
                } else {
                    return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                }
            } else {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour Id already exists']);
            }
        } else {
            // Bad Request
            return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data."]);
        }
    }

    // route : base_url/tour/tour/editTransportQuatation
    public function editTransportQuatation($trour_id, $tro_id)
    {

        $validation = \Config\Services::validation();
        $validation->setRules([
            'trans_tour_id' => 'required|strip_tags',
            'trans_row_id' => 'required|strip_tags',
            'distance_type1' => 'required|strip_tags',
            'trans_date' => 'required|strip_tags',
        ]);

        if ($validation->withRequest($this->request)->run()) {

            $tour_id = $this->request->getVar('trans_tour_id');
            $row_id = $this->request->getVar('trans_row_id');
            $distance_type = $this->request->getVar('distance_type1');
            $transfer_code = $this->request->getVar('transfer_code');
            $transfer = $this->request->getVar('transfer');
            $distance = $this->request->getVar('distance');
            $from_location = $this->request->getVar('pick_location');
            $to_location = $this->request->getVar('drop_location');
            $gdistance = $this->request->getVar('gdistance');
            $date = $this->request->getVar('trans_date');

            // $quotation_id = 'QT-'. $tour_id;
            // $quotation_day = $tour_id.'-'.$row_id;
            if (strpos($from_location, ', Sri Lanka') !== false) {
                $from_location = str_replace(', Sri Lanka', '', $from_location);
            }

            if (strpos($to_location, ', Sri Lanka') !== false) {
                $to_location = str_replace(', Sri Lanka', '', $to_location);
            }

            $session = session();
            $username = $session->get('username');

            $TourQuotationsModel = new TourQuotationsModel();
            // $exist_data = $TourQuotationsModel->select("*")->where('quotation_id', $tour_id)->where('quotation_day', $row_id)->where('distance_type !=', '')->get()->getRowArray();

            $data = [

                'tour_date' => $date,
                'distance_type' => $distance_type,
                'transfer_code' => $transfer_code,
                'transfer' => $transfer,
                'distance' => $distance,
                'from_location' => $from_location,
                'to_location' => $to_location,
                'gdistance' => $gdistance,
                "added_time" => date('Y-m-d H:i:s'),
                "added_by" => $username
            ];

            // if (empty($exist_data)) {

            if ($TourQuotationsModel->set($data)->where('quotation_id', $tour_id)->where('quotation_day', $row_id)->update()) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Edit", "tour_id" => $tour_id, "row_id" => $row_id]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
            // } else {
            // }
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
}
