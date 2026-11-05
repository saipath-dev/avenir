<?php

namespace App\Controllers;

use App\Libraries\MenuLoader;
use App\Models\ApiInquiryModel;
use App\Models\CustomerModel;
use App\Models\InquiryModel;
use App\Models\InquiryConsigneeModel;
use App\Models\InquiryTimelineModel;
use App\Models\InquirySubLocationsModel;
use App\Models\InquiryActivityModel;
use App\Models\InquiryItineraryModel;
use App\Models\InquiryPassengerDetailsModel;
use App\Models\SupplierModel;
use App\Models\HotelRateModel;
use App\Models\RoomTypeModel;
use App\Models\TransferMilageModel;
use App\Models\CountryCodeModel;
use App\Models\EntranceFeeModel;
use App\Models\ExtraKilometersModels;
use App\Models\InvoiceNumberModel;
use App\Models\VehicleModel;
use App\Controllers\Inquiry\InquirySubLocationsController;
use App\Controllers\Inquiry\InquiryItineraryController;
use App\Controllers\Inquiry\InquiryActivityController;
use App\Controllers\MasterFiles\TransferMilageController;
use CodeIgniter\RESTful\ResourceController;

class InquiryController extends ResourceController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function generateInquiryCode()
    {
        $today = date('dmY'); // Format: DDMMYYYY
        $prefix = 'INQ';

        $model = new InquiryModel();

        // Find latest code for today with INQ prefix
        $latestInquiry = $model->like('inquiry_code', $prefix . $today)
                              ->orderBy('inquiry_code', 'DESC')
                              ->first();

        // Extract and increment sequence
        if ($latestInquiry && !empty($latestInquiry['inquiry_code'])) {
            $sequence = (int)substr($latestInquiry['inquiry_code'], -3) + 1;
        } else {
            $sequence = 1;
        }

        // Format as 3-digit sequence
        $paddedSequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);

        return $prefix . $today . $paddedSequence;
    }

    public function index()
    {
        // Redirect to form() method which has the proper inquiry management interface
        return $this->form();
    }

    public function all()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $model = new InquiryModel();
        // Get system inquiries (internal) - these have agent_name field
        $data['inquiries'] = $model
            ->select('inquiry.*, customers.Name as agent')
            ->join('customers', 'customers.Sup_Acc_No = inquiry.agent_name', 'left')
            ->where('inquiry.agent_name IS NOT NULL')
            ->where('inquiry.agent_name !=', '')
            ->orderBy('is_accepted', 'ASC')
            ->orderBy('record_time', 'DESC')
            ->findAll();

        // Get API inquiries (external) - these have Name, Email, etc. fields but no agent_name
        $data['api_inquiries'] = $model
            ->where('inquiry.agent_name IS NULL OR inquiry.agent_name = ""')
            ->where('inquiry.Name IS NOT NULL')
            ->orderBy('record_time', 'DESC')
            ->findAll();

        return view('pages/tour/inquiry_report', $data);
    }

    // New inquiry form view
    public function form()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $consigneeModel = new InquiryConsigneeModel();
        $consigneeModel->where('finalize', 0)->where('start_date !=', null)->where('end_date !=', null);
        $data['rowCount'] = $consigneeModel->countAllResults();

        $data['pending_quotations'] = $consigneeModel
            ->where('finalize', 0)
            ->where('start_date !=', null)
            ->where('end_date !=', null)
            ->orderBy('inquiry_id', 'ASC')->findAll();

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

        $customerModel = new CustomerModel();
        $data['agents'] = $customerModel->findAll();

        $SupplierModel = new SupplierModel();
        $data['transport_agents'] = $SupplierModel->where('sup_cat', 2)->findAll();

        // get inquiries from main inquiry table
        $model = new InquiryModel();
        $data['inquiries'] = $model->findAll();
        $data['rowCountitinerary'] = $model->countAllResults();

        $id = $this->request->getGet('inquiry_id');
        $start_date = $this->request->getGet('start');
        $end_date = $this->request->getGet('end');

        $data['inquiry_id'] = $id;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        if (!empty($id)) {
            $InquiryTimelineModel = new InquiryTimelineModel();
            $data['quotation_details'] = $InquiryTimelineModel
                ->select('inquiry_timeline.*, transfer_milage.transfer_code as code')
                ->join('transfer_milage', 'transfer_milage.id = inquiry_timeline.transfer_code', 'left')
                ->where('inquiry_timeline.inquiry_id', $id)
                ->findAll();
        }

        return view('pages/inquiry/inquiry_form', $data);
    }

    public function insert_consignee()
    {
        try {
            log_message('critical', 'InquiryController::insert_consignee - Method called');

            // Log all POST data
            $postData = $this->request->getPost();
            log_message('critical', 'InquiryController::insert_consignee - POST data: ' . json_encode($postData));

            $validation = \Config\Services::validation();

            $validation->setRules([
                'start_date' => 'required',
                'end_date' => 'required',
                'consignee' => 'required',
                'email' => 'required|valid_email',
                'agent_ref' => 'required',
                'contact' => 'required',
                'pass_count' => 'required|numeric',
                'agent' => 'required',
            ]);

            log_message('critical', 'InquiryController::insert_consignee - Running validation');

            if ($validation->withRequest($this->request)->run()) {
                log_message('critical', 'InquiryController::insert_consignee - Validation passed');
                $inquiry_id = $this->request->getVar('inquiry_id');
                $start_date = $this->request->getVar('start_date');
                $end_date = $this->request->getVar('end_date');
                $consignee = $this->request->getVar('consignee');
                $email = $this->request->getVar('email');
                $agent_ref = $this->request->getVar('agent_ref');
                $country = $this->request->getVar('country');
                $con_code = $this->request->getVar('con_code');
                $contact = $this->request->getVar('contact');
                $vehicle = $this->request->getVar('vehicle');
                $vehicle_name = $this->request->getVar('vehicle_name');
                $vehicle_rate = $this->request->getVar('vehicle_rate');
                $pass_count = $this->request->getVar('pass_count');
                $agent = $this->request->getVar('agent');
                $agent_name = $this->request->getVar('agent_name');
                $agent_prefix = $this->request->getVar('agent_prefix');
                $arr_flight = $this->request->getVar('arrival_flight');
                $arr_flight_time = $this->request->getVar('arrival_flight_time');
                $depart_flight = $this->request->getVar('departure_flight');
                $depart_flight_time = $this->request->getVar('departure_flight_time');
                $is_hotel = $this->request->getVar('hotel_only');
                $is_transport = $this->request->getVar('transport_only');

                $invoiceNumberModel = new InvoiceNumberModel();
                $inquiry_id = $invoiceNumberModel->incrementInquiryNo();

                if ($is_hotel == 0) {
                    if (empty($vehicle) || empty($vehicle_name) || empty($vehicle_rate)) {
                        return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data."]);
                    }
                }

                // both is_hotel and is_transport can not be 1
                if ($is_hotel == 1 && $is_transport == 1) {
                    return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data. Both Hotel and Transport Only can not be true."]);
                }

                $consigneeModel = new InquiryConsigneeModel();
                $agent_ref_exists = $consigneeModel->where('agent_ref', $agent_ref)->first();
                if (!empty($agent_ref_exists)) {
                    return $this->response->setJSON(["status" => 400, "message" => "Agent Reference is already exists. Please provide a unique Reference."]);
                }

                // end date must be greater than start date
                $start_date = date('Y-m-d', strtotime($start_date));
                $end_date = date('Y-m-d', strtotime($end_date));
                if ($start_date > $end_date) {
                    return $this->response->setJSON(["status" => 400, "message" => "End date must be greater than start date."]);
                }

                $session = session();
                $username = $session->get('username');

                $ConsigneeModel = new InquiryConsigneeModel();
                $data = [
                    'inquiry_id' => $inquiry_id,
                    'consignee' => $consignee,
                    'country' => $country,
                    'k_code' => $con_code,
                    'mobile' => $contact,
                    'email' => $email,
                    'agent_ref' => $agent_ref,
                    'vehicle' => $vehicle,
                    'vehicle_name' => $vehicle_name,
                    'vehicle_rate' => $vehicle_rate,
                    'passenger_count' => $pass_count,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'create_user' => $username,
                    'create_time' => date('Y-m-d H:i:s'),
                    'agent' => $agent,
                    'agent_name' => $agent_name,
                    'arrival_flight' => $arr_flight,
                    'departure_flight' => $depart_flight,
                    'arrival_flight_time' => $arr_flight_time,
                    'departure_flight_time' => $depart_flight_time,
                ];

                if ($ConsigneeModel->insert($data)) {
                    $this->add_remove_days_in_qt_table($inquiry_id);

                    log_message('critical', 'InquiryController::insert_consignee - Successfully created inquiry with ID: ' . $inquiry_id);

                    return $this->response->setJSON(["status" => 200, "message" => "Successfully Added", "inquiry_id" => $inquiry_id]);
                } else {
                    log_message('critical', 'InquiryController::insert_consignee - Failed to insert into consignee table');
                    return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                }
            } else {
                // Validation failed
                $errors = $validation->getErrors();
                log_message('critical', 'InquiryController::insert_consignee - Validation failed: ' . json_encode($errors));
                return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data.", "errors" => $errors]);
            }
        } catch (\Exception $e) {
            log_message('critical', 'InquiryController::insert_consignee exception: ' . $e->getMessage() . ' on line ' . $e->getLine() . ' in file ' . $e->getFile());
            log_message('critical', 'InquiryController::insert_consignee exception trace: ' . $e->getTraceAsString());
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    public function timeline($inquiry_id = 0)
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $data['inquiry_id'] = $inquiry_id;
        $data['valid_tour'] = false;

        // get url last segment
        $last_segment = $this->request->getVar('edit');

        if (!empty($inquiry_id) && $inquiry_id != 0 && is_numeric($inquiry_id)) {
            $data['valid_tour'] = true;

            $consigneeModel = new InquiryConsigneeModel();
            $data['consignee'] = $consigneeModel->where('inquiry_id', $inquiry_id)->first();

            if (!$data['consignee']) {
                $data['valid_tour'] = false;
                // Initialize empty arrays for view
                $data['places'] = [];
                $data['activity_places'] = [];
                $data['hotels'] = [];
                $data['room_types'] = [];
                $data['meal_codes'] = [];
                $data['hotel_location'] = [];
                $data['countries'] = [];
                $data['agents'] = [];
                $data['transport_agents'] = [];
                $data['last_segment'] = null;
                return view('pages/inquiry/inquiry_timeline', $data);
            }

            $start_date = $data['consignee']['start_date'] ?? null;
            $end_date = $data['consignee']['end_date'] ?? null;

            // if start date > end date
            if ($start_date > $end_date) {
                $data['valid_tour'] = false;
            }

            $entranceFeeModel = new EntranceFeeModel();
            $data['places'] = $entranceFeeModel->findAll();
            $data['activity_places'] = $entranceFeeModel->findAll();

            $supplierModel = new SupplierModel();
            $data['hotel_location'] = $supplierModel->select('Address')->where('sup_cat', 1)->groupBy('Address')->orderBy('Address', 'ASC')->findAll();
            $data['hotels'] = $supplierModel->select('Sup_Acc_No as Hotel_Code, Name as Hotel_Name')->where('sup_cat', 1)->orderBy('Name', 'ASC')->findAll();

            // Define meal codes
            $data['meal_codes'] = [
                ['meal_code' => 'RO'],
                ['meal_code' => 'BB'],
                ['meal_code' => 'HB'],
                ['meal_code' => 'FB']
            ];

            $roomTypeModel = new RoomTypeModel();
            $data['room_types'] = $roomTypeModel->findAll();

            $countryCodeModel = new CountryCodeModel();
            $data['countries'] = $countryCodeModel->findAll();

            $customerModel = new CustomerModel();
            $data['agents'] = $customerModel->findAll();

            $SupplierModel = new SupplierModel();
            $data['transport_agents'] = $SupplierModel->where('sup_cat', 2)->findAll();

            $data['last_segment'] = $last_segment;
        }

        return view('pages/inquiry/inquiry_timeline', $data);
    }

    public function get_tour_details($inquiry_id)
    {
        if (!empty($inquiry_id) && $inquiry_id != 0) {
            $consigneeModel = new InquiryConsigneeModel();
            $consignee = $consigneeModel
                ->where('inquiry_id', $inquiry_id)
                ->first();

            return $this->response->setJSON(['status' => 200, 'data' => $consignee]);
        }

        return $this->response->setJSON(['status' => 404, 'message' => 'Unable to find the inquiry details']);
    }

    public function get_tour_data($t_id)
    {
        log_message('critical', '=== InquiryController::get_tour_data START for inquiry_id: ' . $t_id . ' ===');

        $InquiryTimelineModel = new InquiryTimelineModel();
        $consigneeModel = new InquiryConsigneeModel();

        log_message('critical', 'Fetching quotation_details from inquiry_timeline');
        $data['quotation_details'] = $InquiryTimelineModel
            ->select('inquiry_timeline.*, transfer_milage.transfer_code as code, room_type.rtype, supplier.Address')
            ->join('supplier', 'supplier.Sup_Acc_No = inquiry_timeline.hotel_acc', 'left')
            ->join('transfer_milage', 'transfer_milage.id = inquiry_timeline.transfer_code', 'left')
            ->join('room_type', 'room_type.id = inquiry_timeline.room_type', 'left')
            ->where('inquiry_timeline.inquiry_id', $t_id)
            ->groupBy('inquiry_timeline.id')
            ->orderBy('inquiry_timeline.inquiry_day', 'ASC')
            ->findAll();

        log_message('critical', 'SQL Query: ' . $InquiryTimelineModel->getLastQuery());
        log_message('critical', 'Found ' . count($data['quotation_details']) . ' quotation_details records');

        // Step 2: Get the inquiry_id from the first result (assuming there is at least one result)
        if (!empty($data['quotation_details'])) {
            $inquiry_id = $data['quotation_details'][0]['inquiry_id'] ?? null;
            log_message('critical', 'Processing inquiry_id: ' . $inquiry_id);

            // Step 3: Get results from the consignee table where tour_id equals inquiry_id
            $consigneeResults = $consigneeModel->where('inquiry_id', $inquiry_id)->findAll();
            log_message('critical', 'Found ' . count($consigneeResults) . ' consignee records');

            $inquirySubLocationsController = new InquirySubLocationsController();
            $inquiryActivityController = new InquiryActivityController();

            $last_saved_date = $this->get_last_saved_date($inquiry_id);

            // Step 4: Append the consignee results to the quotation details
            foreach ($data['quotation_details'] as $key => $detail) {
                log_message('critical', 'Processing detail #' . $key . ' - inquiry_day: ' . $detail['inquiry_day'] . ', inquiry_date: ' . $detail['inquiry_date']);

                // consignee -- NOT REQUIRED
                $data['quotation_details'][$key]['consignee'] = $consigneeResults[0] ?? [];

                // if $detail['inquiry_date'] == $last_saved_date
                $data['quotation_details'][$key]['last_saved_date'] = $detail['inquiry_date'] == $last_saved_date ? true : false;

                // sub locations
                log_message('critical', 'Fetching sub_locations for date: ' . $detail['inquiry_date']);
                $sub_locations = $inquirySubLocationsController->get_sub_locations($inquiry_id, $detail['inquiry_date']);
                if (!empty($sub_locations)) {
                    $sub_locations = json_decode($sub_locations, true);
                }
                $data['quotation_details'][$key]['sub_locations'] = $sub_locations;
                log_message('critical', 'Sub_locations fetched: ' . count($sub_locations ?? []));

                // tour activities
                log_message('critical', 'Fetching tour_activities for date: ' . $detail['inquiry_date']);
                $tourActivities = $inquiryActivityController->get_tour_activities($inquiry_id, $detail['inquiry_date']);
                if (!empty($tourActivities)) {
                    $tourActivities = json_decode($tourActivities, true);
                    if ($tourActivities['status'] == 200) {
                        $tourActivities = $tourActivities['activities'];
                    } else {
                        $tourActivities = [];
                    }
                }
                $data['quotation_details'][$key]['tour_activities'] = $tourActivities;
                log_message('critical', 'Tour_activities fetched: ' . count($tourActivities ?? []));

                // get itenaries
                log_message('critical', 'Fetching itineraries for inquiry_day: ' . $detail['inquiry_day'] . ', date: ' . $detail['inquiry_date']);
                $itenaries = $this->itinerary_all($inquiry_id, $detail['inquiry_day'], $detail['inquiry_date']);
                if (!empty($itenaries)) {
                    $itenaries = json_decode($itenaries, true);
                }
                $data['quotation_details'][$key]['itenaries'] = $itenaries;
                log_message('critical', 'Itineraries fetched: ' . count($itenaries ?? []));
            }
        }

        log_message('critical', '=== InquiryController::get_tour_data COMPLETED ===');
        return $this->response->setJSON(['data' => $data['quotation_details']]);
    }

    public function get_last_saved_date($inquiry_id)
    {
        $InquiryTimelineModel = new InquiryTimelineModel();
        $last_date = $InquiryTimelineModel
            ->select('inquiry_date')
            ->where('inquiry_id', $inquiry_id)
            ->where('saved', 1)
            ->orderBy('inquiry_date', 'DESC')
            ->first();

        return $last_date ? json_encode($last_date['inquiry_date']) : json_encode('');
    }

    public function itinerary_all($inquiry_id, $inquiry_row, $inquiry_date)
    {
        try {
            // Create a new InquiryItineraryModel instance
            $inquiryItineraryModel = new InquiryItineraryModel();
            $itinerary = $inquiryItineraryModel->select('*')
                ->where('inquiry_id', $inquiry_id)
                ->where('inquiry_row', $inquiry_row)
                ->where('inquiry_date', $inquiry_date)
                ->orderBy('order_id', 'ASC')
                ->findAll();

            return json_encode($itinerary);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function save_date()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'trans_tour_id' => 'required',
                'trans_row_id' => 'required',
                't_date' => 'required',
                'hotel' => 'permit_empty',
                'room_type' => 'permit_empty',
                'room_type_id' => 'permit_empty',
                'meal_code' => 'permit_empty',
                'up_flag' => 'required',
                'slct_htl' => 'permit_empty',
                'slct_htl_c' => 'permit_empty'
            ]);

            if ($validation->withRequest($this->request)->run()) {
                $request = $this->request->getPost();

                $tour_id = $request['trans_tour_id'];
                $row_id = $request['trans_row_id'];
                $t_date = $request['t_date'];
                $transfer_code = isset($request['transfer_code']) ? strip_tags($request['transfer_code']) : null;
                $hotel = isset($request['hotel']) ? strip_tags($request['hotel']) : null;
                $hotel_code = isset($request['hotel_code']) ? strip_tags($request['hotel_code']) : null;

                $room_type = isset($request['room_type']) ? strip_tags($request['room_type']) : null;
                $room_type_id = isset($request['room_type_id']) ? strip_tags($request['room_type_id']) : null;

                $meal_code = isset($request['meal_code']) ? strip_tags($request['meal_code']) : null;

                $flag = strip_tags($request['up_flag']);
                $slct_htl = $hotel;
                $slct_htl_c = $hotel_code;

                $hotel_only = strip_tags($request['is_hotel']);

                $real_hotel = ($hotel !== null && $hotel !== '') ? $hotel : $slct_htl;
                $real_hotel_code = ($hotel_code !== null && $hotel_code !== '') ? $hotel_code : $slct_htl_c;
                $room_type = $room_type ?? '0';

                $inquiry_id = $tour_id;
                $inquiry_day = $row_id;

                $session = session();
                $username = $session->get('username');

                // real hotel is required
                if (empty($real_hotel)) {
                    $real_hotel = '';
                }

                $inquirySubLocationsModel = new InquirySubLocationsModel();
                $sub_locations = $inquirySubLocationsModel->where('inquiry_id', $tour_id)->where('inquiry_row', $row_id)->where('date_id', $t_date)->findAll();

                if (empty($sub_locations) && $hotel_only == 0) {
                    $sub_locations = [];
                }

                // get from of first sub location
                $pick_location = !empty($sub_locations) ? ($sub_locations[0]['from'] ?? '') : '';
                // get to of last sub location
                $drop_location = !empty($sub_locations) ? ($sub_locations[count($sub_locations) - 1]['to'] ?? '') : '';

                // get total distance of all sub locations
                $gdistance = 0;
                $distance = 0;
                $extrakm = 0;
                if ($hotel_only == 0) {
                    foreach ($sub_locations as $sub) {
                        $gdistance += $sub['estimate_km'];
                        $distance += $sub['km'];
                        $extrakm += $sub['extra_km'];
                    }
                }

                if (strpos($pick_location, ', Sri Lanka') !== false) {
                    $pick_location = str_replace(', Sri Lanka', '', $pick_location);
                }

                if (strpos($drop_location, ', Sri Lanka') !== false) {
                    $drop_location = str_replace(', Sri Lanka', '', $drop_location);
                }

                $supplierModel = new SupplierModel();
                $hotel_name = $supplierModel->select('Name')->where('Sup_Acc_No', $real_hotel)->find();

                $InquiryTimelineModel = new InquiryTimelineModel();
                $exist_data = $InquiryTimelineModel->select("*")->where('inquiry_id', $inquiry_id)->where('inquiry_day', $inquiry_day)->where('inquiry_date', $t_date)->first();

                log_message('critical', 'InquiryController::save_date - inquiry_id: ' . $inquiry_id . ', inquiry_day: ' . $inquiry_day . ', inquiry_date: ' . $t_date . ', flag: ' . $flag);
                log_message('critical', 'InquiryController::save_date - exist_data: ' . json_encode($exist_data));
                log_message('critical', 'InquiryController::save_date - empty check: ' . (empty($exist_data) ? 'true' : 'false'));

                $hotel = isset($hotel_name[0]['Name']) ? $hotel_name[0]['Name'] : '';
                $data = [
                    'inquiry_id' => $tour_id,
                    'inquiry_day' => $inquiry_day,
                    'distance_type' => 2,
                    'transfer_code' => $transfer_code,
                    'from_location' => $pick_location,
                    'to_location' => $drop_location,
                    'gdistance' => $gdistance,
                    'distance' => $distance,
                    'extra_kilo' => $extrakm,
                    'hotel_code' => $real_hotel_code,
                    'meal_code' => $meal_code,
                    'hotel_acc' => $real_hotel,
                    'hotel_name' => isset($hotel_name[0]['Name']) ? $hotel_name[0]['Name'] : '',
                    'room_type' => $room_type,
                    'room_type_id' => $room_type_id,
                    'inquiry_date' => $t_date,
                    "created_time" => date('Y-m-d H:i:s'),
                    "created_user" => $username,
                    "det_flag" => 1,
                    'saved' => 1
                ];

                $consigneeModel = new InquiryConsigneeModel();

                if (empty($exist_data) && ($flag == 0)) {
                    log_message('critical', 'InquiryController::save_date - INSERTING new record');
                    if ($InquiryTimelineModel->insert($data)) {
                        log_message('critical', 'InquiryController::save_date - Insert successful, ID: ' . $InquiryTimelineModel->getInsertID());
                        // Call the new_create new_create method in Touting Registry after inserting
                        if ($hotel_only == 0 && $distance > 0) {
                            $TransferMilageController = new TransferMilageController();
                            $cxf = $TransferMilageController->new_create($pick_location, $drop_location, $gdistance, $distance);
                        }

                        $ItineraryController = new InquiryItineraryController();
                        $ItineraryController->add_itinerary($tour_id, $inquiry_day, $t_date, $pick_location, $drop_location, 0, $hotel, $room_type, $meal_code, 0);

                        return $this->response->setJSON(["status" => 200, "message" => "Successfully Added", "inquiry_id" => $tour_id, "row_id" => $row_id]);
                    } else {
                        log_message('critical', 'InquiryController::save_date insert failed for tour_id=' . $tour_id);
                        return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                    }
                } else if (!empty($exist_data) && ($flag == 1)) { // Edit Quatation
                    log_message('critical', 'InquiryController::save_date - UPDATING existing record');
                    if ($InquiryTimelineModel->where('inquiry_id', $inquiry_id)->where('inquiry_day', $inquiry_day)->where('inquiry_date', $t_date)->set($data)->update()) {
                        log_message('critical', 'InquiryController::save_date - Update successful');
                        // Call the new_create new_create method in Touting Registry after inserting
                        if ($hotel_only == 0 && $distance > 0) {
                            $TransferMilageController = new TransferMilageController();
                            $TransferMilageController->new_create($pick_location, $drop_location, $gdistance, $distance);
                        }

                        $ItineraryController = new InquiryItineraryController();
                        $ItineraryController->add_itinerary($tour_id, $inquiry_day, $t_date, $pick_location, $drop_location, 0, $hotel, $room_type, $meal_code, 0);

                        $con_up = $consigneeModel->where('inquiry_id', $tour_id)->set(['finalize_txt' => 1])->update();

                        if ($con_up) {
                            return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated", "inquiry_id" => $tour_id, "row_id" => $row_id, "sub_locations" => $sub_locations]);
                        }
                    } else {
                        log_message('critical', 'InquiryController::save_date update failed for tour_id=' . $tour_id);
                        return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                    }
                }

                return $this->response->setJSON(["status" => 400, "message" => "Something went wrong. Please try again. ERROR : NO UPFLAG FOUND"]);
            } else {
                // Bad Request
                return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data.", "errors" => $validation->getErrors()]);
            }
        } catch (\Exception $e) {
            log_message('critical', 'InquiryController::save_date exception: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    private function add_remove_days_in_qt_table($tour_id)
    {
        // add empty rows to the inquiry_timeline table
        $InquiryTimelineModel = new InquiryTimelineModel();
        $ConsigneeModel = new InquiryConsigneeModel();
        $consignee = $ConsigneeModel->where('inquiry_id', $tour_id)->first();
        $start_date = $consignee['start_date'];
        $end_date = $consignee['end_date'];

        $days = [];

        // loop through start date to end date
        $x = 0;
        for ($i = strtotime($start_date); $i <= strtotime($end_date); $i = strtotime('+1 day', $i)) {
            $date = date('Y-m-d', $i);
            $days[] = $date;
            $x++;

            // check if the date exists in the inquiry_timeline table
            $exist = $InquiryTimelineModel->where('inquiry_id', $tour_id)->where('inquiry_date', $date)->countAllResults();

            if (!$exist) {
                // insert a new row
                $InquiryTimelineModel->insert([
                    'inquiry_id' => $tour_id,
                    'inquiry_day' => $x,
                    'inquiry_date' => $date,
                    'created_time' => date('Y-m-d H:i:s'),
                    'created_user' => session()->get('username'),
                    'is_sub' => 0,
                    'saved' => 0
                ]);
            }
        }
        // if there any days in inquiry_timeline table where inquiry_date not in days array, delete them
        $InquiryTimelineModel->where('inquiry_id', $tour_id)->whereNotIn('inquiry_date', $days)->delete();
    }

    // Additional methods for the inquiry functionality
    public function load_vehicles()
    {
        try {
            $VehicleModel = new VehicleModel();
            $vehicle_details = $VehicleModel->select('*')
                ->orderBy('passenger_count', 'ASC')->findAll();
            return $this->response->setJSON(['status' => 200, 'data' => $vehicle_details]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function get_consignee_details($agent)
    {
        try {
            $CustomerModel = new CustomerModel();
            $agentdetail = $CustomerModel->where('Sup_Acc_No', $agent)->first();
            return $this->response->setJSON(['status' => 200, 'data' => $agentdetail]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function get_hotel_details()
    {
        $date = $this->request->getGet('date');

        $supplierModel = new SupplierModel();
        $data['hotels'] = $supplierModel
            ->join('hotel_rate', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')
            ->where('supplier.sup_cat', 1)
            ->where('supplier.is_avalible', 1)
            ->groupBy('supplier.Name')
            ->orderBy('supplier.Name', 'ASC')
            ->findAll();

        if (!empty($data)) {
            return $this->response->setJSON(['status' => 200, 'data' => \App\Libraries\TourPayments\Mask::bank($data)]);   // tf-pilot-r3: hotels' bank numbers masked unless the sign-in pays or verifies
        } else {
            return $this->response->setJSON(['status' => 404, 'message' => 'No data found']);
        }
    }

    public function get_mileage()
    {
        $inquiry_id = $this->request->getGet('inquiry_id');
        $date = $this->request->getGet('date');

        $InquiryTimelineModel = new InquiryTimelineModel();
        $data = $InquiryTimelineModel->select('distance, extra_kilo')
            ->where('inquiry_id', $inquiry_id)
            ->where('inquiry_date', $date)
            ->findAll();

        return $this->response->setJSON(['status' => 200, 'data' => $data]);
    }

    public function actual_distance()
    {
        try {
            $from = $this->request->getVar('from');
            $to = $this->request->getVar('to');
            $pick = $this->request->getVar('frompick');
            $drop = $this->request->getVar('todrop');

            log_message('critical', 'InquiryController::actual_distance - Called with from=' . $from . ', to=' . $to . ', frompick=' . $pick . ', todrop=' . $drop);

            if (empty($pick) || empty($drop)) {
                log_message('critical', 'InquiryController::actual_distance - ERROR: Missing required parameters: frompick or todrop is empty');
                return $this->response->setJSON(["status" => 400, "message" => "Missing required parameters"]);
            }

            $transferMilageModel = new TransferMilageModel();
            $Full_details = $transferMilageModel->like('from', $pick . '%')->like('to', $drop . '%')->findAll();

            $extraKilometersModel = new ExtraKilometersModels();
            $extra_details = $extraKilometersModel->where('city', $to)->findAll();

            log_message('critical', 'InquiryController::actual_distance - Full_details count: ' . count($Full_details) . ', Extra_details count: ' . count($extra_details));

            if (empty($Full_details) && empty($extra_details)) {
                log_message('critical', 'InquiryController::actual_distance - WARNING: No distance data found for pick=' . $pick . ', drop=' . $drop . ', to=' . $to);
            }

            $merged_data = [
                'Full_details' => $Full_details,
                'Extra_details' => $extra_details
            ];

            return $this->response->setJSON(["status" => 200, "message" => "", "data" => $merged_data]);

        } catch (\Exception $e) {
            log_message('critical', 'InquiryController::actual_distance - EXCEPTION: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            return $this->response->setJSON(["status" => 500, "message" => "An error occurred: " . $e->getMessage()]);
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

    public function get_meal_prices()
    {
        $hotel_code = $this->request->getGet('h_code');
        $room_type = $this->request->getGet('room_type');
        $meal_plan = $this->request->getGet('meal_plan');
        $date = $this->request->getGet('date');

        $hotelrateModel = new HotelRateModel();
        $data = $hotelrateModel
            ->select('*')
            ->where('hotel', $hotel_code)
            ->where('room_type', $room_type)
            ->where('hotel_rate.sdate <=', $date)
            ->where('hotel_rate.edate >=', $date)
            ->first();

        if (!empty($data)) {
            return $this->response->setJSON(['status' => 200, 'data' => $data]);
        } else {
            return $this->response->setJSON(['status' => 404, 'message' => 'No data found']);
        }
    }

    public function finalize($tour)
    {
        try {
            $is_template = $this->request->getVar('is_template');

            $ConsigneeModel = new InquiryConsigneeModel();
            $consignee = $ConsigneeModel->where('inquiry_id', $tour)->first();

            $data = [
                'finalize' => 1,
                'finalize_txt' => 0,
                'is_template' => $is_template
            ];

            $InquiryTimelineModel = new InquiryTimelineModel();

            // get records from inquiry_timeline table
            $quotation_details = $InquiryTimelineModel->where('inquiry_id', $tour)->findAll();
            // if empty return error
            if (empty($quotation_details)) {
                return $this->response->setJSON(["status" => 400, "message" => "No valid data found for the inquiry dates."]);
            }

            if (($InquiryTimelineModel->set($data)->where('inquiry_id', $tour)->update()) && ($ConsigneeModel->set($data)->where('inquiry_id', $tour)->update())) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Finalized!", "inquiry_id" => $tour]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function delete_date()
    {
        try {
            $date = $this->request->getPost('date');
            $tour = $this->request->getPost('inquiry_id');
            $row = $this->request->getPost('row');

            $inquiryTimelineModel = new InquiryTimelineModel();
            $inquirySubLocationsModel = new InquirySubLocationsModel();
            $inquiryActivityModel = new InquiryActivityModel();
            $inquiryItineraryModel = new InquiryItineraryModel();

            // Get existing data before deletion
            $existingData = $inquiryTimelineModel->where('inquiry_id', $tour)->where('inquiry_date', $date)->first();

            if ($inquiryTimelineModel->where('inquiry_id', $tour)->where('inquiry_date', $date)->delete()) {
                $inquirySubLocationsModel->where('inquiry_id', $tour)->where('inquiry_row', $row)->where('date_id', $date)->delete();
                $inquiryActivityModel->where('inquiry_id', $tour)->where('inquiry_row', $row)->where('inquiry_date', $date)->delete();
                $inquiryItineraryModel->where('inquiry_id', $tour)->where('inquiry_row', $row)->where('inquiry_date', $date)->delete();

                // add new empty row to the quotation table
                $inquiryTimelineModel->insert([
                    'inquiry_id' => $tour,
                    'inquiry_day' => $row,
                    'inquiry_date' => $date,
                    'created_time' => date('Y-m-d H:i:s'),
                    'created_user' => session()->get('username'),
                    'is_sub' => 0
                ]);

                return $this->response->setJSON(["status" => 200, "message" => "Delete successfully!"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    public function update_passenger_count()
    {
        try {
            $tour_id = $this->request->getPost('inquiry_id');
            $passenger_count = $this->request->getPost('passenger_count');

            // Validate inputs
            if (empty($tour_id) || empty($passenger_count)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tour ID and passenger count are required'
                ]);
            }

            if (!is_numeric($passenger_count) || $passenger_count < 1) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Passenger count must be a positive number'
                ]);
            }

            $consigneeModel = new InquiryConsigneeModel();

            // Check if tour exists
            $tour = $consigneeModel->where('inquiry_id', $tour_id)->first();
            if (!$tour) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tour not found'
                ]);
            }

            // Update passenger count
            $data = ['passenger_count' => $passenger_count];
            $result = $consigneeModel->where('inquiry_id', $tour_id)->set($data)->update();

            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Passenger count updated successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to update passenger count'
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // Passenger management methods
    public function getPassengerDetails()
    {
        try {
            $inquiry_id = $this->request->getVar('inquiry_id');
            // Create a new PassengerDetailsModel instance
            $PassengerModel = new InquiryPassengerDetailsModel();
            // Get the passenger count
            $passengercount = $PassengerModel->where('inquiry_id', $inquiry_id)->countAllResults();

            $consigneeModel = new InquiryConsigneeModel();
            $pax_count = $consigneeModel->select('passenger_count')->where('inquiry_id', $inquiry_id)->first();
            $pssenger = $pax_count['passenger_count'];

            $combined_result = [
                'pax_count' => $pssenger,
                'passenger' => $passengercount
            ];

            // Return the passenger data
            return $this->response->setJSON(['status' => 200, 'data' => $combined_result]);
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

            // Create a new PassengerDetailsModel instance
            $PaxModel = new InquiryPassengerDetailsModel();

            // Removed passenger count validation - users can now add any number of passengers
            // Only minimum of 1 passenger is required (validated in frontend)

            // Set the data for the new passenger
            $data = [
                'pass_name' => $pax,
                'inquiry_id' => $tour_id
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

    public function pax_delete()
    {
        try {
            $id = $this->request->getGet('id');

            $PassengerModel = new InquiryPassengerDetailsModel();

            // Get passenger data before deletion
            $oldData = $PassengerModel->find($id);

            if ($PassengerModel->delete($id)) {
                return $this->response->setJSON(["status" => 200, "message" => "Passenger Delete successfully!"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    public function pax_all($inquiry_id)
    {
        try {
            // Create a new PassengerDetailsModel instance
            $PassengerModel = new InquiryPassengerDetailsModel();
            // Get the passenger data
            $passenger = $PassengerModel->where('inquiry_id', $inquiry_id)->findAll();

            // Return the passenger data
            return json_encode($passenger);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function tour_copy()
    {
        try {
            $date = $this->request->getPost('date');
            $tour = $this->request->getPost('tour');
            $row = $this->request->getPost('row');
            $nextd = $this->request->getPost('nextd');

            $InquiryTimelineModel = new InquiryTimelineModel();
            $quotation_details = $InquiryTimelineModel->select('*')
                ->where('inquiry_id', $tour)
                ->where('inquiry_day', $row)
                ->where('inquiry_date', $date)
                ->first();

            if (!$quotation_details) {
                return $this->response->setJSON(["status" => 404, "message" => "Source day not found"]);
            }

            $data = [
                'meal_code' => $quotation_details['meal_code'] ?? null,
                'hotel_acc' => $quotation_details['hotel_acc'] ?? null,
                'hotel_name' => $quotation_details['hotel_name'] ?? null,
                'room_type' => $quotation_details['room_type'] ?? null,
                'created_time' => date('Y-m-d H:i:s'),
                'created_user' => $quotation_details['created_user'] ?? null,
                'distance' => 0,
                'from_location' => $quotation_details['to_location'] ?? '',
                'to_location' => $quotation_details['to_location'] ?? '',
                'gdistance' => $quotation_details['gdistance'] ?? 0,
                'extra_kilo' => 0,
                'transfer_code' => $quotation_details['transfer_code'] ?? null,
                'saved' => 1
            ];

            $updated = $InquiryTimelineModel
                ->where('inquiry_id', $tour)
                ->where('inquiry_day', $quotation_details['inquiry_day'] + 1)
                ->where('inquiry_date', $nextd)
                ->set($data)
                ->update();

            if ($updated) {
                $InquiryItineraryController = new InquiryItineraryController();
                $re = $InquiryItineraryController->add_itinerary($tour, $quotation_details['inquiry_day'] + 1, $nextd, 1, '', '', '', '', '', '');
                return $this->response->setJSON(["status" => 200, "message" => "Successfully copied to next day!"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    /**
     * Print inquiry timeline
     */
    public function print_timeline()
    {
        $inquiry_id = $this->request->getGet('inquiry_id');

        if (!$inquiry_id) {
            return redirect()->back()->with('error', 'Inquiry ID is required');
        }

        $data = $this->get_inquiry_print_data($inquiry_id);

        return view('pages/inquiry/inquiry_timeline_print', $data);
    }

    /**
     * Get inquiry print data
     */
    private function get_inquiry_print_data($inquiry_id)
    {
        $data = array();

        // Load menu data
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Get consignee details (main inquiry info)
        // Note: inquiry_consignee already has agent_name, transport_agent_name, guide_name, and vehicle_name stored
        $consigneeModel = new InquiryConsigneeModel();
        $data['consignee_details'] = $consigneeModel
            ->where('inquiry_id', $inquiry_id)
            ->findAll();

        // Get timeline details with relationships
        $timelineModel = new InquiryTimelineModel();
        $data['timeline_details'] = $timelineModel
            ->select('inquiry_timeline.*, transfer_milage.transfer_code as code, room_type.rtype, supplier.Name as hotel_name, supplier.Address')
            ->join('supplier', 'supplier.Sup_Acc_No = inquiry_timeline.hotel_acc', 'left')
            ->join('transfer_milage', 'transfer_milage.id = inquiry_timeline.transfer_code', 'left')
            ->join('room_type', 'room_type.id = inquiry_timeline.room_type', 'left')
            ->where('inquiry_timeline.inquiry_id', $inquiry_id)
            ->where('inquiry_timeline.saved', 1)
            ->orderBy('inquiry_timeline.inquiry_day', 'ASC')
            ->findAll();

        // Get sub locations
        $subLocationsModel = new InquirySubLocationsModel();
        $data['sub_locations'] = $subLocationsModel
            ->where('inquiry_id', $inquiry_id)
            ->orderBy('inquiry_row', 'ASC')
            ->findAll();

        // Get activities with entrance fee names
        $activityModel = new InquiryActivityModel();
        $data['activity_details'] = $activityModel
            ->select('inquiry_activities.*, entrance_fee.name as location_name')
            ->join('entrance_fee', 'entrance_fee.id = inquiry_activities.name', 'left')
            ->where('inquiry_activities.inquiry_id', $inquiry_id)
            ->findAll();

        // Get itinerary
        $itineraryModel = new InquiryItineraryModel();
        $itinerary = $itineraryModel
            ->where('inquiry_id', $inquiry_id)
            ->groupBy('inquiry_date')
            ->findAll();

        $itinerary_array = [];
        foreach ($itinerary as $item) {
            $itinerary_array[$item['inquiry_date']]['details'] = $itineraryModel
                ->where('inquiry_id', $inquiry_id)
                ->where('inquiry_date', $item['inquiry_date'])
                ->orderBy('order_id', 'ASC')
                ->findAll();
        }
        $data['itinerary'] = $itinerary_array;

        // Get passenger details
        $passengerModel = new InquiryPassengerDetailsModel();
        $data['passengers'] = $passengerModel
            ->where('inquiry_id', $inquiry_id)
            ->findAll();

        return $data;
    }

    /**
     * Get inquiry consignee data for pre-filling the tour form
     */
    public function get_inquiry_consignee($inquiry_id)
    {
        $consigneeModel = new InquiryConsigneeModel();
        $data = $consigneeModel->where('inquiry_id', $inquiry_id)->first();

        if ($data) {
            return $this->response->setJSON(['status' => 200, 'data' => $data]);
        }
        return $this->response->setJSON(['status' => 404, 'message' => 'Inquiry not found']);
    }
}