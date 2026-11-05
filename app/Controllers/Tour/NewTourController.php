<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Controllers\MasterFiles\TransferMilageController;
use App\Libraries\MenuLoader;
use App\Models\RoomTypeModel;
use App\Models\SupplierModel;
use App\Models\HotelRateModel;
use App\Models\TourQuotationsModel;
use App\Models\TransferMilageModel;
use App\Models\CountryCodeModel;
use App\Models\ConsigneeModel;
use App\Models\CustomerModel;
use App\Models\EntranceFeeModel;
use App\Models\ExtraKilometersModels;
use App\Models\InvoiceNumberModel;
use App\Models\TourActivityModel;
use App\Models\TourItineraryModel;
use App\Models\TourSubLocationsModel;
use App\Models\VehicleModel;
use App\Models\InquiryModel;
use App\Models\PassengerDetailsModel;
use App\Models\InquiryConsigneeModel;
use App\Models\InquiryTimelineModel;
use App\Models\InquirySubLocationsModel;
use App\Models\InquiryActivityModel;
use App\Models\InquiryItineraryModel;

class NewTourController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/tour/quotation
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $consigneeModel = new ConsigneeModel();
        $consigneeModel->where('finalize', 0)->where('start_date !=', null)->where('end_date !=', null);
        $data['rowCount'] = $consigneeModel->countAllResults();

        $data['pending_quotations'] = $consigneeModel
            ->where('finalize', 0)
            ->where('start_date !=', null)
            ->where('end_date !=', null)
            ->orderBy('tour_id', 'ASC')->findAll();

        $data['templates'] = $consigneeModel
            ->where('is_template', 1)
            ->where('start_date !=', null)
            ->where('end_date !=', null)
            ->orderBy('tour_id', 'DESC')->findAll();

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

        $SupplierModel = new SupplierModel();
        $data['transport_agents'] = $SupplierModel->where('sup_cat', 2)->findAll();

        // get inquiries
        $model = new InquiryModel();
        $data['inquiries'] = $model->findAll();
        $data['rowCountitinerary'] = $model->countAllResults();

        $id = $this->request->getGet('tour_id');
        $start_date = $this->request->getGet('start');
        $end_date = $this->request->getGet('end');

        $data['quotation_id'] = $id;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        if (!empty($id)) {
            $TourQuotationsModel = new TourQuotationsModel();
            $data['quotation_details'] = $TourQuotationsModel->select('tour_quotations.*, transfer_milage.transfer_code as code')->join('transfer_milage', 'transfer_milage.id = tour_quotations.transfer_code', 'left')->where('tour_quotations.quotation_id', $id)->findAll();
        }

        return view('pages/tour/quotation/new_quotation', $data);
    }

    public function insert_consignee()
    {
        try {
            $validation = \Config\Services::validation();

            $validation->setRules([
                'inquiry_id' => 'permit_empty|strip_tags',
                'pending_inquiry_id' => 'permit_empty|strip_tags',

                'start_date' => 'required|strip_tags',
                'end_date' => 'required|strip_tags',
                'consignee' => 'required|strip_tags',
                'email' => 'required|strip_tags',
                'agent_ref' => 'required|strip_tags',
                'country' => 'strip_tags',
                'con_code' => 'strip_tags',
                'contact' => 'required|strip_tags',

                // 't_agent' => 'required|strip_tags',
                // 'guide' => 'permit_empty|strip_tags',
                'vehicle' => 'permit_empty|strip_tags',
                'vehicle_name' => 'permit_empty|strip_tags',
                'vehicle_rate' => 'permit_empty|strip_tags',

                'pass_count' => 'required|strip_tags',
                'agent' => 'required|strip_tags',
                'arrival_flight' => 'strip_tags',
                'departure_flight' => 'strip_tags',
                'manual_tour_id' => 'strip_tags',
                'tour_code' => 'permit_empty|strip_tags',
                'hotel_only' => 'permit_empty|strip_tags',
                'transport_only' => 'permit_empty|strip_tags',
                'sgl' => 'permit_empty|strip_tags',
                'dbl' => 'permit_empty|strip_tags',
                'tpl' => 'permit_empty|strip_tags',
                'is_twin' => 'permit_empty|strip_tags',
                'template_id' => 'permit_empty|strip_tags',
                'template_name' => 'permit_empty|strip_tags',
            ]);

            if ($validation->withRequest($this->request)->run()) {

                $inquary_id = $this->request->getVar('inquiry_id');
                $pending_inquiry_id = $this->request->getVar('pending_inquiry_id');
                $start_date = $this->request->getVar('start_date');
                $end_date = $this->request->getVar('end_date');
                $consignee = $this->request->getVar('consignee');
                $email = $this->request->getVar('email');
                $agent_ref = $this->request->getVar('agent_ref');
                $country = $this->request->getVar('country');
                $con_code = $this->request->getVar('con_code');
                $contact = $this->request->getVar('contact');
                // $t_agent = $this->request->getVar('t_agent');
                // $t_agent_name = $this->request->getVar('t_agent_name');
                // $guide = $this->request->getVar('guide');
                // $guide_name = $this->request->getVar('guide_name');
                $vehicle = $this->request->getVar('vehicle');
                $vehicle_name = $this->request->getVar('vehicle_name');
                $vehicle_rate = $this->request->getVar('vehicle_rate');
                $pass_count = $this->request->getVar('pass_count');
                // $manual_id = $this->request->getVar('manual_tour_id');
                $tour_code = $this->request->getVar('tour_code');
                $agent = $this->request->getVar('agent');
                $agent_name = $this->request->getVar('agent_name');
                $prefix = $this->request->getVar('agent_prefix');
                $arr_flight = $this->request->getVar('arrival_flight');
                $arr_flight_time = $this->request->getVar('arrival_flight_time');
                $depart_flight = $this->request->getVar('departure_flight');
                $depart_flight_time = $this->request->getVar('departure_flight_time');
                $is_hotel = $this->request->getVar('hotel_only');
                $is_transport = $this->request->getVar('transport_only');
                $sgl = $this->request->getVar('sgl');
                $dbl = $this->request->getVar('dbl');
                $tpl = $this->request->getVar('tpl');
                $is_twin = $this->request->getVar('is_twin');
                $template_id = $this->request->getVar('template_id');
                $template_name = $this->request->getVar('template_name');

                $invoiceNumberModel = new InvoiceNumberModel();
                $tour_id = $invoiceNumberModel->incrementTourNo();
                $manual_id = $invoiceNumberModel->incrementManualTourId();

                // add $manual id to $prefix
                $manual_id = "AH" . " " . $manual_id;

                if ($is_hotel == 0) {
                    if (empty($vehicle) || empty($vehicle_name) || empty($vehicle_rate)) {
                        return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data."]);
                    }
                }

                // both is_hotel and is_transport can not be 0
                if ($is_hotel == 1 && $is_transport == 1) {
                    return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data. Both Hotel and Transport Only can not be true."]);
                }

                // manual id must be unique
                $consigneeModel = new ConsigneeModel();
                $manual_id_exists = $consigneeModel->where('manual_id', $manual_id)->first();
                if (!empty($manual_id_exists)) {
                    return $this->response->setJSON(["status" => 400, "message" => "Manual ID already exists. Please provide a unique manual ID."]);
                }

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

                // validate template
                if ($template_id != 0 && !empty($template_id)) {
                    $temp_validated = $this->validate_template($template_id, $start_date, $end_date);
                    if (!$temp_validated) {
                        return $this->response->setJSON(["status" => 400, "message" => "The template you selected is not applicable. Template tour dates count and the new tour dates count must be same to apply the template."]);
                    }
                }

                $session = session();
                $username = $session->get('username');

                $ConsigneeModel = new ConsigneeModel();
                $data = [
                    'tour_id' => $tour_id,
                    'consignee' => $consignee,
                    'country' => $country,
                    'k_code' => $con_code,
                    'mobile' => $contact,
                    'email' => $email,
                    'agent_ref' => $agent_ref,
                    // 'transport_agent' => $t_agent,
                    // 'transport_agent_name' => $t_agent_name,
                    // 'guide' => $guide,
                    // 'guide_name' => $guide_name,
                    'vehicle' => $vehicle,
                    'vehicle_name' => $vehicle_name,
                    'vehicle_rate' => $vehicle_rate,
                    'passenger_count' => $pass_count,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'create_user' => $username,
                    'create_time' => date('Y-m-d H:i:s'),
                    'manual_id' => $manual_id,
                    'tour_code' => $tour_code,
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
                    'template_id' => $template_id,
                    'template_name' => $template_name
                ];

                if ($ConsigneeModel->insert($data)) {

                    // Audit Log: Tour Creation
                    $auditLogger = new \App\Libraries\AuditLogger();
                    $auditLogger->logTourCreation($tour_id, $data, session()->get('user_id'));

                    if ($template_id != 0 && !empty($template_id)) {
                        $this->generate_timeline_on_template($template_id, $tour_id);
                    }

                    // If this is a pending inquiry conversion, copy the inquiry timeline to tour
                    if (!empty($pending_inquiry_id) && $pending_inquiry_id != '0') {
                        $this->copy_inquiry_timeline_to_tour($pending_inquiry_id, $tour_id);
                    } else {
                        // Only add empty days if not copying from inquiry
                        $this->add_remove_days_in_qt_table($tour_id);
                    }

                    $inquaryModel = new InquiryModel();
                    $inquaryModel->where('id', $inquary_id)->set(['is_created' => 1])->update();

                    return $this->response->setJSON(["status" => 200, "message" => "Successfully Added", "tour_id" => $tour_id]);
                } else {
                    return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                }
            } else {
                // Bad Request

                return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data.", "errors" => $validation->getErrors()]);
            }
        } catch (\Exception $e) {
            log_message('critical', 'NewTourController::insert_consignee exception: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    private function validate_template($template_id, $start_date, $end_date)
    {
        $consigneeModel = new ConsigneeModel();
        $template = $consigneeModel->where('tour_id', $template_id)->first();
        $t_start_date = $template['start_date'];
        $t_end_date = $template['end_date'];

        // the date difference between the template and the new tour dates must be the same
        $t_diff = abs(strtotime($t_start_date) - strtotime($t_end_date));
        $t_days = floor($t_diff / (60 * 60 * 24));

        $c_diff = abs(strtotime($start_date) - strtotime($end_date));
        $c_days = floor($c_diff / (60 * 60 * 24));

        if ($c_days == $t_days) {
            return true;
        }

        return false;
    }

    private function generate_timeline_on_template($template_id, $tour_id)
    {
        try {
            $consigneeModel = new ConsigneeModel();

            $consignee = $consigneeModel->where('tour_id', $tour_id)->first();
            if (empty($consignee)) return false;
            $c_start_date = $consignee['start_date'];
            $c_end_date = $consignee['end_date'];

            $template = $consigneeModel->where('tour_id', $template_id)->first();
            if (empty($template)) return false;
            $t_start_date = $template['start_date'];
            $t_end_date = $template['end_date'];

            // the days difference between the template and the new tour dates must be the same
            $c_diff = abs(strtotime($c_start_date) - strtotime($c_end_date));
            $c_days = floor($c_diff / (60 * 60 * 24));

            $t_diff = abs(strtotime($t_start_date) - strtotime($t_end_date));
            $t_days = floor($t_diff / (60 * 60 * 24));

            if ($c_days != $t_days) return false;

            // get dates between t_start_date and t_end_date
            $dates = [];
            $start = strtotime($c_start_date);
            $end = strtotime($c_end_date);
            while ($start <= $end) {
                $dates[] = date('Y-m-d', $start);
                $start = strtotime("+1 day", $start);
            }

            // tour quotations table
            $TourQuotationsModel = new TourQuotationsModel();
            $template_details = $TourQuotationsModel->where('quotation_id', $template_id)
                ->orderBy('quotation_day', 'ASC')
                ->findAll();
            if (!empty($template_details)) {
                foreach ($template_details as $key => $template) {

                    if (!isset($dates[$key])) continue;

                    $data = $template;
                    $data['quotation_id'] = $tour_id;
                    $data['tour_date'] = $dates[$key];
                    $data['created_time'] = date('Y-m-d H:i:s');
                    $data['created_user'] = session()->get('username');
                    $data['confirm'] = 0;
                    $data['finalize'] = 0;
                    $data['hotel_advance'] = 0;
                    $data['hotel_booking_flag'] = 0;
                    $data['booking_confirmed_flag'] = 0;
                    $data['hotel_payment_flag'] = 0;
                    $data['extra_hotel_fee'] = 0;
                    $data['hotel_dollar_rate'] = 0;
                    $data['confirmation_email'] = 0;
                    $data['confirmation_reference'] = 0;
                    $data['total_hotel_dollars'] = 0;
                    $data['pay_doller_count'] = 0;
                    $data['payment_complete'] = 0;
                    $data['payment_complete_user'] = '';
                    $data['payment_complete_date'] = '';
                    $data['payment_request'] = 0;
                    $data['payment_request_user'] = '';
                    $data['payment_request_date'] = '';

                    $TourQuotationsModel->insert($data);

                    // log_message('critical', $TourQuotationsModel->getLastQuery());

                    $supplierModel = new SupplierModel();
                    $hotel_name = $supplierModel->select('Name')->where('Sup_Acc_No', $template['hotel_acc'])->find();

                    $hotel = isset($hotel_name[0]['Name']) ? $hotel_name[0]['Name'] : '';

                    $ItineraryController = new ItineraryController();
                    $ItineraryController->add_itinerary($tour_id, $template['quotation_day'], $dates[$key], $template['from_location'], $template['to_location'], 0, $hotel, $template['room_type'], $template['meal_code'], 0);
                }
            }

            // sub locations table
            $tourSubLocationsController = new TourSubLocationsController();
            $tourSubLocationsModel = new TourSubLocationsModel();
            $entranceFeeModel = new EntranceFeeModel();
            $template_sub_locations = $tourSubLocationsModel->where('quotation_id', $template_id)->findAll();
            if (!empty($template_sub_locations)) {
                foreach ($template_sub_locations as $key => $sub) {
                    $data = $sub;
                    $data['tour'] = $tour_id;
                    $data['row'] = $sub['tour_row'];
                    $data['date'] = $dates[$sub['tour_row'] - 1];
                    $tourSubLocationsController->add_sub_location($data);
                }
            }

            // activities table
            $tourActivityController = new TourActivityController();
            $tourActivityModel = new TourActivityModel();
            $template_activities = $tourActivityModel->where('tour_id', $template_id)->findAll();
            if (!empty($template_activities)) {
                foreach ($template_activities as $key => $activity) {
                    $data = $activity;
                    $data['tour_id'] = $tour_id;
                    $data['tour_date'] = $dates[$activity['tour_row'] - 1];

                    $place_id = $activity['name'];
                    $place = $entranceFeeModel->select('name')->where('id', $place_id)->first();
                    $data['place_name'] = isset($place['name']) ? $place['name'] : '';
                    $tourActivityController->create_activity($data);
                }
            }

            return true;
        } catch (\Exception $e) {
            log_message('critical', 'error: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return false;
        }
    }

    /**
     * Copy inquiry timeline data to tour quotations when converting a pending inquiry to a tour
     */
    private function copy_inquiry_timeline_to_tour($inquiry_id, $tour_id)
    {
        try {
            $consigneeModel = new ConsigneeModel();
            $consignee = $consigneeModel->where('tour_id', $tour_id)->first();
            if (empty($consignee)) return false;

            $c_start_date = $consignee['start_date'];
            $c_end_date = $consignee['end_date'];

            // Get dates for the new tour
            $dates = [];
            $start = strtotime($c_start_date);
            $end = strtotime($c_end_date);
            while ($start <= $end) {
                $dates[] = date('Y-m-d', $start);
                $start = strtotime("+1 day", $start);
            }

            // Copy inquiry_timeline to tour_quotations
            $InquiryTimelineModel = new InquiryTimelineModel();
            $TourQuotationsModel = new TourQuotationsModel();

            $inquiry_timeline = $InquiryTimelineModel
                ->where('inquiry_id', $inquiry_id)
                ->where('saved', 1)
                ->orderBy('inquiry_day', 'ASC')
                ->findAll();

            if (!empty($inquiry_timeline)) {
                $hotelRateModel = new HotelRateModel();

                foreach ($inquiry_timeline as $key => $day) {
                    // Calculate the corresponding date for this day
                    $day_index = $day['inquiry_day'] - 1;
                    $tour_date = isset($dates[$day_index]) ? $dates[$day_index] : $day['inquiry_date'];

                    // Look up rates from hotel_rate table
                    $sgl = null;
                    $dbl = null;
                    $tpl = null;

                    if (!empty($day['hotel_acc']) && !empty($day['room_type'])) {
                        $hotel_rate = $hotelRateModel
                            ->where('hotel', $day['hotel_acc'])
                            ->where('room_type', $day['room_type'])
                            ->where('sdate <=', $tour_date)
                            ->where('edate >=', $tour_date)
                            ->first();

                        if ($hotel_rate) {
                            $meal = $day['meal_code'] ?? 'bb';
                            $sgl = $hotel_rate['s' . $meal] ?? null;
                            $dbl = $hotel_rate['d' . $meal] ?? null;
                            $tpl = $hotel_rate['t' . $meal] ?? null;
                        }
                    }

                    $data = [
                        'quotation_id' => $tour_id,
                        'quotation_day' => $day['inquiry_day'],
                        'tour_date' => $tour_date,
                        'from_location' => $day['from_location'],
                        'to_location' => $day['to_location'],
                        'hotel_acc' => $day['hotel_acc'],
                        'hotel_name' => $day['hotel_name'],
                        'room_type' => $day['room_type'],
                        'meal_code' => $day['meal_code'],
                        'distance' => $day['distance'] ?? 0,
                        'gdistance' => $day['gdistance'] ?? 0,
                        'extra_kilo' => $day['extra_kilo'] ?? 0,
                        'transfer_code' => $day['transfer_code'],
                        'sgl' => $sgl,
                        'dbl' => $dbl,
                        'tpl' => $tpl,
                        'created_time' => date('Y-m-d H:i:s'),
                        'created_user' => session()->get('username'),
                        'det_flag' => 1,
                        'saved' => 1,
                        'confirm' => 0,
                        'finalize' => 0,
                        'hotel_advance' => 0,
                        'hotel_booking_flag' => 0,
                        'booking_confirmed_flag' => 0,
                        'hotel_payment_flag' => 0,
                        'extra_hotel_fee' => 0,
                        'hotel_dollar_rate' => 0,
                        'confirmation_email' => 0,
                        'confirmation_reference' => 0,
                        'total_hotel_dollars' => 0,
                        'pay_doller_count' => 0,
                        'payment_complete' => 0,
                        'payment_complete_user' => '',
                        'payment_complete_date' => '',
                        'payment_request' => 0,
                        'payment_request_user' => '',
                        'payment_request_date' => '',
                    ];
                    $TourQuotationsModel->insert($data);

                    // Add itinerary entry
                    $supplierModel = new SupplierModel();
                    $hotel_name = $supplierModel->select('Name')->where('Sup_Acc_No', $day['hotel_acc'])->first();
                    $hotel = isset($hotel_name['Name']) ? $hotel_name['Name'] : '';

                    $ItineraryController = new ItineraryController();
                    $ItineraryController->add_itinerary($tour_id, $day['inquiry_day'], $tour_date, $day['from_location'], $day['to_location'], 0, $hotel, $day['room_type'], $day['meal_code'], 0);
                }
            }

            // Copy sub_locations from inquiry_sub_locations to tour_sub_locations
            $InquirySubLocationsModel = new InquirySubLocationsModel();
            $tourSubLocationsModel = new TourSubLocationsModel();
            $inquiry_sub_locations = $InquirySubLocationsModel->where('inquiry_id', $inquiry_id)->findAll();

            if (!empty($inquiry_sub_locations)) {
                foreach ($inquiry_sub_locations as $sub) {
                    $row_index = ($sub['inquiry_row'] ?? $sub['tour_row'] ?? 1) - 1;
                    $sub_date = isset($dates[$row_index]) ? $dates[$row_index] : $c_start_date;

                    // Insert directly into tour_sub_locations table
                    $data = [
                        'quotation_id' => $tour_id,
                        'tour_row' => $sub['inquiry_row'] ?? $sub['tour_row'] ?? 1,
                        'date_id' => $sub_date,
                        'from' => $sub['from'] ?? '',
                        'to' => $sub['to'] ?? '',
                        'estimate_km' => $sub['estimate_km'] ?? 0,
                        'km' => $sub['km'] ?? 0,
                        'extra_km' => $sub['extra_km'] ?? 0,
                        'ref_no' => $sub['ref_no'] ?? null,
                    ];
                    $tourSubLocationsModel->insert($data);
                }
            }

            // Copy activities from inquiry_activities to tour_activity
            $InquiryActivityModel = new InquiryActivityModel();
            $tourActivityModel = new TourActivityModel();
            $entranceFeeModel = new EntranceFeeModel();
            $inquiry_activities = $InquiryActivityModel->where('inquiry_id', $inquiry_id)->findAll();

            if (!empty($inquiry_activities)) {
                foreach ($inquiry_activities as $activity) {
                    $row_index = ($activity['inquiry_row'] ?? $activity['tour_row'] ?? 1) - 1;
                    $activity_date = isset($dates[$row_index]) ? $dates[$row_index] : $c_start_date;

                    // Look up fee from entrance_fee table based on activity name (place_id)
                    $place_id = $activity['name'] ?? 0;
                    $entrance = $entranceFeeModel->where('id', $place_id)->first();
                    $fee = isset($entrance['entranceFee']) ? $entrance['entranceFee'] : 0;

                    // Insert directly into tour_activity table
                    $data = [
                        'tour_id' => $tour_id,
                        'tour_row' => $activity['inquiry_row'] ?? $activity['tour_row'] ?? 1,
                        'tour_date' => $activity_date,
                        'name' => $place_id,
                        'code' => $activity['code'] ?? '',
                        'fee' => $fee,
                        'ref_no' => $activity['ref_no'] ?? null,
                    ];
                    $tourActivityModel->insert($data);
                }
            }

            // Mark inquiry as converted (finalized)
            $inquiryConsigneeModel = new InquiryConsigneeModel();
            $inquiryConsigneeModel->where('inquiry_id', $inquiry_id)
                ->set(['finalize' => 1])
                ->update();

            return true;
        } catch (\Exception $e) {
            log_message('critical', 'copy_inquiry_timeline_to_tour error: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return false;
        }
    }

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

    public function update_hotel_only($id, $hotel_only)
    {
        $ConsigneeModel = new ConsigneeModel();

        // if transport only is true, hotel only can not be true
        if ($hotel_only == 1) {
            // check if transport only is true
            $consignee = $ConsigneeModel->where('tour_id', $id)->first();
            if ($consignee['transport_only'] == 1) {
                return $this->response->setJSON(["status" => 400, "message" => "Transport Only is already selected. Please unselect it first."]);
            }
        }

        $data = [
            'hotel_only' => $hotel_only
        ];

        if ($ConsigneeModel->where('tour_id', $id)->set($data)->update()) {
            return $this->response->setJSON(["status" => 200, "message" => "Hotel Only status updated successfully."]);
        } else {
            return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
        }
    }

    public function update_transport_only($id, $transport_only)
    {
        $ConsigneeModel = new ConsigneeModel();

        // if hotel only is true, transport only can not be true
        if ($transport_only == 1) {
            // check if hotel only is true
            $consignee = $ConsigneeModel->where('tour_id', $id)->first();
            if ($consignee['hotel_only'] == 1) {
                return $this->response->setJSON(["status" => 400, "message" => "Hotel Only is already selected. Please unselect it first."]);
            }
        }

        $data = [
            'transport_only' => $transport_only
        ];

        if ($ConsigneeModel->where('tour_id', $id)->set($data)->update()) {
            return $this->response->setJSON(["status" => 200, "message" => "Transport Only status updated successfully."]);
        } else {
            return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
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

    public function timeline($tour_id = 0)
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();


        $data['tour_id'] = $tour_id;
        $data['valid_tour'] = false;

        // get url last segment
        $last_segment = $this->request->getVar('edit');
        // $last_segment = end($url_segments);

        if (!empty($tour_id) && $tour_id != 0) {

            $data['valid_tour'] = true;

            $consigneeModel = new ConsigneeModel();
            $data['consignee'] = $consigneeModel->where('tour_id', $tour_id)->first();
            // log_message('critical', 'consignee: ' . json_encode($data['consignee']));

            if (!empty($data['consignee'])) {
                $start_date = $data['consignee']['start_date'];
                $end_date = $data['consignee']['end_date'];

                // log_message('critical', 'start date: ' . $start_date . ' end date: ' . $end_date);
                // if start date > end date
                if ($start_date > $end_date) {
                    $data['valid_tour'] = false;
                }
            } else {
                $data['valid_tour'] = false;
            }

            $entranceFeeModel = new EntranceFeeModel();
            $data['places'] = $entranceFeeModel->findAll();

            $supplierModel = new SupplierModel();
            $data['hotel_location'] = $supplierModel->select('Address')->where('sup_cat', 1)->groupBy('Address')->orderBy('Address', 'ASC')->findAll();

            $countryCodeModel = new CountryCodeModel();
            $data['countries'] = $countryCodeModel->findAll();

            $customerModel = new CustomerModel();
            $data['agents'] = $customerModel->findAll();

            $SupplierModel = new SupplierModel();
            $data['transport_agents'] = $SupplierModel->where('sup_cat', 2)->where('is_avalible', 1)->findAll();
            $data['restaurant_suppliers'] = (new SupplierModel())->where('sup_cat', 4)->where('is_avalible', 1)->findAll();

            $data['last_segment'] = $last_segment;



            // log_message('critical', $debtorInvoiceLogModel->getLastQuery());
        }

        return view('pages/tour/quotation/quotation_timeline', $data);
    }

    public function get_tour_details($tour_id)
    {
        if (!empty($tour_id) && $tour_id != 0) {
            $consigneeModel = new ConsigneeModel();
            $consignee = $consigneeModel
                ->where('tour_id', $tour_id)
                ->first();

            $debtorInvoiceLogModel = new \App\Models\DebtorInvoiceLogModel();
            // Include cancel = 0 (normal) and cancel = 3 (rejected) to allow re-requesting after rejection
            $invoice_log = $debtorInvoiceLogModel->select('id,invoice_no')->where('tour_id', $tour_id)->whereIn('cancel', [0, 3])->orderBy('id', 'DESC')->first();

            return $this->response->setJSON(['status' => 200, 'data' => $consignee, 'invoice_log' => $invoice_log]);
        }

        return $this->response->setJSON(['status' => 404, 'message' => 'Unable to find the tour details']);
    }

    public function getPassengerDetails()
    {
        try {
            $tour = $this->request->getVar('tour_id');
            // Create a new EntranceFeeModel instance
            $PassengerModel = new PassengerDetailsModel();
            // Get the entrance fee data
            $passengercount = $PassengerModel->where('tour_id', $tour)->countAllResults();

            $consigneeModel = new ConsigneeModel();
            $pax_count = $consigneeModel->select('passenger_count')->where('tour_id', $tour)->first();
            $pssenger = $pax_count['passenger_count'];

            $combined_result = [
                'pax_count' => $pssenger,
                'passenger' => $passengercount
            ];

            // Return the entrance fee data
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

    public function pax_delete()
    {
        try {
            $id = $this->request->getGet('id');

            $PassengerModel = new PassengerDetailsModel();

            // Get passenger data before deletion
            $oldData = $PassengerModel->find($id);
            $tour_id = $oldData['tour_id'] ?? null;

            if ($PassengerModel->delete($id)) {
                // Audit Log: Passenger Deletion
                if ($tour_id) {
                    $auditLogger = new \App\Libraries\AuditLogger();
                    $auditLogger->logPassengerChange($tour_id, 'DELETE', $oldData, null, session()->get('user_id'));
                }

                return $this->response->setJSON(["status" => 200, "message" => "Passenger Delete successfully!"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
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

    public function get_hotel_locations()
    {
        $supplierModel = new SupplierModel();
        $hotel_location = $supplierModel->select('Address')->where('sup_cat', 1)->groupBy('Address')->orderBy('Address', 'ASC')->findAll();

        return $this->response->setJSON(['status' => 200, 'data' => $hotel_location]);
    }

    public function get_tour_timeline()
    {
        $tour_id = $this->request->getVar('tour_id');
        $TourQuotationsModel = new TourQuotationsModel();
        $data['quotation_details'] = $TourQuotationsModel->select('tour_quotations.*, transfer_milage.transfer_code as code')->join('transfer_milage', 'transfer_milage.id = tour_quotations.transfer_code', 'left')->where('tour_quotations.quotation_id', $tour_id)->findAll();
        return $this->response->setJSON(['status' => 200, 'data' => $data]);
    }

    public function get_hotel_details()
    {
        // $destination = $this->request->getGet('destination_city');
        $date = $this->request->getGet('date');

        // if (empty($destination)) {
        //     return $this->response->setJSON(['status' => 400, 'message' => 'Search location is required']);
        // }

        $supplierModel = new SupplierModel();
        $data['hotels'] = $supplierModel
            ->join('hotel_rate', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')
            ->where('supplier.sup_cat', 1)
            ->where('supplier.is_avalible', 1)
            // ->where('hotel_rate.sdate <=', $date)
            // ->where('hotel_rate.edate >=', $date)
            ->groupBy('supplier.Name')
            ->orderBy('supplier.Name', 'ASC')
            ->findAll();

        if (!empty($data)) {
            return $this->response->setJSON(['status' => 200, 'data' => \App\Libraries\TourPayments\Mask::bank($data)]);   // tf-pilot-r3: hotels' bank numbers masked unless the sign-in pays or verifies
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

    public function get_meal_prices()
    {
        $hotel_code = $this->request->getGet('h_code');
        $room_type = $this->request->getGet('room_type');
        $meal_plan = $this->request->getGet('meal_plan');
        $date = $this->request->getGet('date');
        $tour_id = (string) ($this->request->getGet('tour_id') ?? '');

        // ---- Hotel Rates panel first -------------------------------------------------
        // The approved plan for this hotel / room type / meal plan / night, priced for the
        // quotation (market from the agent's country, ad hoc payment rates never included),
        // with the night's compulsory supplements and rules as "watch out" flags. When no
        // plan covers the night we fall through to the old hotel_rate rows below and the gap
        // is registered in Hotel Incomplete Work — nothing is guessed here.
        $country = null;
        $rooms = null;
        if ($tour_id !== '') {
            $cons = (new ConsigneeModel())->select('country, sgl, dbl, tpl')->where('tour_id', $tour_id)->first();
            if ($cons) {
                $country = $cons['country'] ?? null;
                $rooms = (int) ($cons['sgl'] ?? 0) + (int) ($cons['dbl'] ?? 0) + (int) ($cons['tpl'] ?? 0);
            }
        }
        try {
            $hrSvc = new \App\Libraries\HotelRates\HotelRateService(\Config\Database::connect(), (string) session()->get('username'));
            // published contract rates only: a tactical or special-offer plan is never what the
            // customer is quoted, so the engine is kept to the quotable kinds (tf_meta.quote_plan_types)
            $opts = array_filter(['country' => $country, 'rooms' => $rooms ?: null]) + ['plan_types' => \App\Libraries\HotelRates\RateLookup::quotationPlanTypes(\Config\Database::connect())];
            $hr = $hrSvc->lookup((string) $hotel_code, $room_type, (string) $meal_plan, (string) $date, $opts);
            $hrMarket = $hrSvc->lookupEngine()->resolveMarket($opts);
            if ($hr && ((float) ($hr['sgl'] ?? 0) > 0 || (float) ($hr['dbl'] ?? 0) > 0 || (float) ($hr['tpl'] ?? 0) > 0)) {
                return $this->response->setJSON([
                    'status' => 200,
                    'SGL' => $hr['sgl'] ?? 0,
                    'DBL' => $hr['dbl'] ?? 0,
                    'TPL' => $hr['tpl'] ?? 0,
                    'source' => 'panel',
                    'plan' => $hr['plan']['name'] ?? null,
                    'season' => $hr['season']['name'] ?? null,
                    'market' => $hrMarket,
                    'derived' => $hr['derived'] ?? null,
                    'flags' => $hrSvc->lookupFlags($hr, (string) $date),
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Hotel Rates lookup failed for ' . $hotel_code . ' ' . $date . ': ' . $e->getMessage());
        }

        $hotelrateModel = new HotelRateModel();

        // Match the rate window actually covering this date (sdate <= date <= edate).
        // Ties (overlapping windows) fall back to the most recently created row.
        $data = $hotelrateModel
            ->select('*')
            ->where('hotel', $hotel_code)
            ->where('room_type', $room_type)
            ->where('hotel_rate.sdate <=', $date)
            ->where('hotel_rate.edate >=', $date)
            ->orderBy('hotel_rate.id', 'DESC')
            ->first();

        $SGL = 0;
        $DBL = 0;
        $TPL = 0;

        if (!empty($data)) {
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
                case "ro":
                    $SGL = $data['sro'];
                    $DBL = $data['dro'];
                    $TPL = $data['tro'];
                    break;
            }
        }

        // A row was found but the selected meal plan has no rate set (all zero) is the
        // same "nothing usable" case as no row at all — both should block manual entry
        // and send staff to Hotel Rate rather than silently accepting a $0 rate.
        if ((float) $SGL <= 0 && (float) $DBL <= 0 && (float) $TPL <= 0) {
            $this->registerRateGap($tour_id, (string) $hotel_code, (string) $date, $room_type, (string) $meal_plan, 'none');
            return $this->response->setJSON(['status' => 404, 'message' => 'No Hotel Rate found for this hotel, room type, meal plan, and date.', 'flags' => $this->rateGapFlags((string) $date)]);
        }

        // priced from the old Hotel Rate rows: usable, but no approved plan covers the night
        $this->registerRateGap($tour_id, (string) $hotel_code, (string) $date, $room_type, (string) $meal_plan, 'legacy');
        return $this->response->setJSON(['status' => 200, 'SGL' => $SGL, 'DBL' => $DBL, 'TPL' => $TPL, 'source' => 'legacy', 'flags' => $this->rateGapFlags((string) $date)]);
    }

    /** The night's own flags (gala dates etc.) when no plan priced it — for the timeline's watch-out line. */
    private function rateGapFlags(string $date): array
    {
        try {
            return (new \App\Libraries\HotelRates\HotelRateService(\Config\Database::connect(), (string) session()->get('username')))->lookupFlags(null, $date);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Tell the Hotel Incomplete Work panel that this night had no approved plan. Never blocks quoting. */
    private function registerRateGap(string $tourId, string $hotelAcc, string $date, $room, string $meal, string $found): void
    {
        if ($tourId === '' || $hotelAcc === '' || $date === '') {
            return;
        }
        try {
            (new \App\Libraries\HotelRates\WorkQueueService(\Config\Database::connect(), (string) session()->get('username')))
                ->registerQuoteGap($tourId, $hotelAcc, $date, $room, $meal, $found);
        } catch (\Throwable $e) {
            log_message('error', 'Hotel Incomplete Work could not record the rate gap for ' . $hotelAcc . ' ' . $date . ': ' . $e->getMessage());
        }
    }

    public function search_hotel_location($tour, $location, $date)
    {
        $supplierModel = new SupplierModel();
        $hotel_details = $supplierModel->join('hotel_rate', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')->where('supplier.sup_cat', 1)->where('supplier.is_avalible', 1)->where('hotel_rate.sdate <=', $date)->where('hotel_rate.edate >=', $date)->where('supplier.Address', $location)->orderBy('supplier.Name', 'ASC');

        $hotel_results['hotels'] = $hotel_details->findAll();

        return json_encode($hotel_results);
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
                'sgl' => 'permit_empty',
                'dbl' => 'permit_empty',
                'tpl' => 'permit_empty',
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
                $room_type_id = isset($request['room_type_id']) ? strip_tags($request['room_type_id'])  : null;

                $meal_code = isset($request['meal_code']) ? strip_tags($request['meal_code']) : null;
                $sgl = isset($request['sgl']) ? strip_tags($request['sgl']) : null;
                $dbl = isset($request['dbl']) ? strip_tags($request['dbl']) : null;
                $tpl = isset($request['tpl']) ? strip_tags($request['tpl']) : null;

                $sglcount = isset($request['count_sgl']) ? strip_tags($request['count_sgl']) : null;
                $dblcount = isset($request['count_dbl']) ? strip_tags($request['count_dbl']) : null;
                $tplcount = isset($request['count_tpl']) ? strip_tags($request['count_tpl']) : null;

                $sglfoc = isset($request['sglfoc']) ? strip_tags($request['sglfoc']) : 0;
                $dblfoc = isset($request['dblfoc']) ? strip_tags($request['dblfoc']) : 0;
                $tplfoc = isset($request['tplfoc']) ? strip_tags($request['tplfoc']) : 0;
                $q_extra_hotel_fee = isset($request['q_extra_hotel_fee']) ? strip_tags($request['q_extra_hotel_fee']) : 0;

                $flag = strip_tags($request['up_flag']);
                // $slct_htl = strip_tags($request['slct_htl']);
                // $slct_htl_c = strip_tags($request['slct_htl_c']);
                $slct_htl = $hotel;
                $slct_htl_c = $hotel_code;

                $hotel_only = strip_tags($request['is_hotel']);

                $real_hotel = ($hotel !== null && $hotel !== '') ? $hotel : $slct_htl;
                $real_hotel_code = ($hotel_code !== null && $hotel_code !== '') ? $hotel_code : $slct_htl_c;
                $room_type = $room_type ?? '0';

                $quotation_id = $tour_id;
                $quotation_day = $row_id;

                $session = session();
                $username = $session->get('username');

                // real hotel is required
                if (empty($real_hotel)) {
                    // return $this->response->setJSON(["status" => 400, "message" => "Please provide valid hotel details."]);
                    $real_hotel = '';
                }

                $tourSubLocationsModel = new TourSubLocationsModel();
                $sub_locations = $tourSubLocationsModel->where('quotation_id', $tour_id)->where('tour_row', $row_id)->where('date_id', $t_date)->findAll();

                if (empty($sub_locations) && $hotel_only == 0) {
                    // return $this->response->setJSON(["status" => 400, "message" => "Please provide valid sub locations. At least one sub location is required."]);
                    $sub_locations = [];
                }

                // get from of first sub location
                $pick_location = $sub_locations[0]['from'] ?? '';
                // get to of last sub location
                $drop_location = $sub_locations[count($sub_locations) - 1]['to']  ?? '';

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

                if (!empty($real_hotel)) {
                    if (!empty($sglcount)) {
                        if ($sgl == 0.00) {
                            return $this->response->setJSON(["status" => 400, "message" => "Please provide valid SGL Rate."]);
                        }
                    }
                    if (!empty($dblcount)) {
                        if ($dbl == 0.00) {
                            return $this->response->setJSON(["status" => 400, "message" => "Please provide valid DBL Rate."]);
                        }
                    }
                    if (!empty($tplcount)) {
                        if ($tpl == 0.00) {
                            return $this->response->setJSON(["status" => 400, "message" => "Please provide valid TPL Rate."]);
                        }
                    }
                }

                // A rate can only be entered once Hotel, Room Type, and Meal Code are all
                // selected — without all three there's no Hotel Rate row to validate a
                // manually typed value against. Mirrors the frontend's hotel_selection_complete()
                // gate on the .sgl_txt/.dbl_txt/.tpl_txt click handlers in quotation_timeline.php.
                $roomOrMealMissing = empty($room_type) || $room_type == '0' || empty($meal_code) || $meal_code == '0';
                if (!empty($real_hotel) && $roomOrMealMissing) {
                    if ((float) $sgl > 0 || (float) $dbl > 0 || (float) $tpl > 0) {
                        return $this->response->setJSON(["status" => 400, "message" => "Please select Hotel, Room Type, and Meal Code before entering a rate."]);
                    }
                }

                // NOTE: business rule as of 2026-09-03 — every SGL/DBL/TPL rate must trace back
                // to a real Hotel Rate entry for this hotel/room type/meal plan/date (mirrors
                // get_meal_prices()'s own sdate<=date<=edate lookup):
                //   - No matching Hotel Rate row, or one exists but that meal plan's rate for
                //     this room size is 0/unset → ANY nonzero submitted value is rejected. This
                //     also covers editing a pre-existing legacy rate that was saved before this
                //     rule existed — once touched, it's held to the same standard.
                //   - A usable Hotel Rate value does exist → the submitted value may not exceed
                //     it (equal is fine — that's the untouched value get_meal_prices() auto-fills).
                // Re-derived server-side rather than trusting a client-sent ceiling, since this is
                // the actual persistence point. This may change in the future — if the rule shifts,
                // update this block and the mirrored frontend check in quotation_timeline.php's
                // save_date() (added to the SAVE path only — no separate enforcement elsewhere).
                if (!empty($real_hotel) && !empty($room_type) && $room_type != '0' && !empty($meal_code) && $meal_code != '0') {
                    $rateRow = (new HotelRateModel())
                        ->where('hotel', $real_hotel)
                        ->where('room_type', $room_type)
                        ->where('sdate <=', $t_date)
                        ->where('edate >=', $t_date)
                        ->orderBy('id', 'DESC')
                        ->first();

                    $mealColumnMap = ['ro' => 'ro', 'bb' => 'bb', 'hb' => 'hb', 'fb' => 'fb'];
                    $mealCodeLower = strtolower($meal_code);

                    $systemSgl = 0;
                    $systemDbl = 0;
                    $systemTpl = 0;
                    if (!empty($rateRow) && isset($mealColumnMap[$mealCodeLower])) {
                        $systemSgl = (float) ($rateRow['s' . $mealColumnMap[$mealCodeLower]] ?? 0);
                        $systemDbl = (float) ($rateRow['d' . $mealColumnMap[$mealCodeLower]] ?? 0);
                        $systemTpl = (float) ($rateRow['t' . $mealColumnMap[$mealCodeLower]] ?? 0);
                    }

                    if ($systemSgl <= 0 && (float) $sgl > 0) {
                        return $this->response->setJSON(["status" => 400, "message" => "Cannot save this rate — no SGL rate found in the system for this day."]);
                    }
                    if ($systemSgl > 0 && (float) $sgl > $systemSgl) {
                        return $this->response->setJSON(["status" => 400, "message" => "Cannot save this rate — SGL exceeds the system rate for this day."]);
                    }
                    if ($systemDbl <= 0 && (float) $dbl > 0) {
                        return $this->response->setJSON(["status" => 400, "message" => "Cannot save this rate — no DBL rate found in the system for this day."]);
                    }
                    if ($systemDbl > 0 && (float) $dbl > $systemDbl) {
                        return $this->response->setJSON(["status" => 400, "message" => "Cannot save this rate — DBL exceeds the system rate for this day."]);
                    }
                    if ($systemTpl <= 0 && (float) $tpl > 0) {
                        return $this->response->setJSON(["status" => 400, "message" => "Cannot save this rate — no TPL rate found in the system for this day."]);
                    }
                    if ($systemTpl > 0 && (float) $tpl > $systemTpl) {
                        return $this->response->setJSON(["status" => 400, "message" => "Cannot save this rate — TPL exceeds the system rate for this day."]);
                    }
                }

                $TourQuotationsModel = new TourQuotationsModel();
                $exist_data = $TourQuotationsModel->select("*")->where('quotation_id', $quotation_id)->where('quotation_day', $quotation_day)->get()->getRowArray();

                if (!empty($exist_data) && $exist_data['payment_request'] == 1) {
                    return $this->response->setJSON(["status" => 400, "message" => "This date cannot be changed because its hotel payment request has already been accepted."]);
                }

                // If hotel_acc is empty, preserve existing hotel_acc from database
                if (empty($real_hotel) && !empty($exist_data['hotel_acc'])) {
                    $real_hotel = $exist_data['hotel_acc'];
                }

                $supplierModel = new SupplierModel();
                $hotel_name_result = $supplierModel->select('Name')->where('Sup_Acc_No', $real_hotel)->find();

                $hotel = isset($hotel_name_result[0]['Name']) ? $hotel_name_result[0]['Name'] : '';

                // If hotel not in supplier table, preserve existing hotel_name or get from hotel_rate
                if (empty($hotel) && !empty($exist_data['hotel_name'])) {
                    $hotel = $exist_data['hotel_name'];
                } elseif (empty($hotel) && !empty($real_hotel)) {
                    // Try hotel_rate table as fallback
                    $hotelRateModel = new HotelRateModel();
                    $hotel_rate = $hotelRateModel->select('location')->where('hotel', $real_hotel)->first();
                    $hotel = isset($hotel_rate['location']) ? $hotel_rate['location'] : '';
                }
                $data = [
                    'quotation_id' => $tour_id,
                    'quotation_day' => $quotation_day,
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
                    'hotel_name' => $hotel,
                    'room_type' => $room_type,
                    'room_type_id' => $room_type_id,
                    'tour_date' => $t_date,
                    'sgl' => $sgl,
                    'dbl' => $dbl,
                    'tpl' => $tpl,
                    'sglfoc' => $sglfoc,
                    'dblfoc' => $dblfoc,
                    'tplfoc' => $tplfoc,
                    'extra_hotel_fee' => $q_extra_hotel_fee,
                    "created_time" => date('Y-m-d H:i:s'),
                    "created_user" => $username,
                    "det_flag" => 1,
                    'saved' => 1
                ];

                $consigneeModel = new ConsigneeModel();

                // log_message('critical', 'data: ' . $TourQuotationsModel->getLastQuery());

                if (empty($exist_data) && ($flag == 0)) {

                    if ($TourQuotationsModel->insert($data)) {
                       

                        // Audit Log: Hotel date INSERT
                        $auditLogger = new \App\Libraries\AuditLogger();
                        $auditLogger->logHotelDateChange($tour_id, 'INSERT', null, $request, session()->get('user_id'));

                        // Call the new_create new_create method in Touting Registry after inserting
                        if ($hotel_only == 0 && $distance > 0) {
                            $TransferMilageController = new TransferMilageController();
                            $cxf = $TransferMilageController->new_create($pick_location, $drop_location, $gdistance, $distance);
                        }

                        $ItineraryController = new ItineraryController();
                        $ItineraryController->add_itinerary($tour_id, $quotation_day, $t_date, $pick_location, $drop_location, 0, $hotel, $room_type, $meal_code, 0);


                        return $this->response->setJSON(["status" => 200, "message" => "Successfully Added", "tour_id" => $tour_id, "row_id" => $row_id]);
                    } else {
                        log_message('critical', 'NewTourController::save_date insert failed for tour_id=' . $tour_id);
                        return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                    }
                } else if (!empty($exist_data) && ($flag == 1)) { // Edit Quatation
                    log_message('critical', 'NewTourController::save_date updating existing record with data: ' . json_encode($data));

                    if ($TourQuotationsModel->where('quotation_id', $quotation_id)->where('quotation_day', $quotation_day)->set($data)->update()) {
                        

                        // Audit Log: Hotel date UPDATE
                        $auditLogger = new \App\Libraries\AuditLogger();
                        $auditLogger->logHotelDateChange($tour_id, 'UPDATE', $exist_data, $request, session()->get('user_id'));

                        // Call the new_create new_create method in Touting Registry after inserting
                        if ($hotel_only == 0 && $distance > 0) {
                            $TransferMilageController = new TransferMilageController();
                            $TransferMilageController->new_create($pick_location, $drop_location, $gdistance, $distance);
                        }

                        $ItineraryController = new ItineraryController();
                        $ItineraryController->add_itinerary($tour_id, $quotation_day, $t_date, $pick_location, $drop_location, 0, $hotel, $room_type, $meal_code, 0);

                        $con_up = $consigneeModel->where('tour_id', $tour_id)->set(['finalize_txt' => 1])->update();
                        // log_message('critical', 'con_up: ' . $con_up);

                        if ($con_up) {

                            $max_confirm = $TourQuotationsModel->selectMax('confirm')->where('quotation_id', $tour_id)->first();
                            if ($max_confirm['confirm'] == 1) {
                                return $this->response->setJSON(["status" => 201, "message" => "Successfully updated! Then, Cancel the Previous Invoice.", "tour_id" => $tour_id]);
                            } else {

                                return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated", "tour_id" => $tour_id, "row_id" => $row_id, "sub_locations" => $sub_locations]);
                            }
                        }
                    } else {
                        log_message('critical', 'NewTourController::save_date update failed for tour_id=' . $tour_id);
                        return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                    }
                }

                
                return $this->response->setJSON(["status" => 400, "message" => "Something went wrong. Please try again. ERROR : NO UPFLAG FOUND"]);
            } else {
                // Bad Request
                return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data.", "errors" => $validation->getErrors()]);
            }
        } catch (\Exception $e) {
            log_message('critical', 'NewTourController::save_date exception: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    public function add_sub_row()
    {
        $tour_id = $this->request->getVar('tour_id');
        $row_id = $this->request->getVar('row_id');
        $date = $this->request->getVar('date');

        $TourQuotationsModel = new TourQuotationsModel();
        $data = $TourQuotationsModel
            ->where('tour_quotations.quotation_id', $tour_id)
            ->where('tour_quotations.tour_date', $date)
            ->countAllResults();

        if ($data == 0) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Please save the main row first.']);
        }

        $sub_row_id = $row_id . '.' . ($data);

        // insert a new row with the sub_row_id
        $TourQuotationsModel->insert([
            'quotation_id' => $tour_id,
            'quotation_day' => $sub_row_id,
            'tour_date' => $date,
            'created_time' => date('Y-m-d H:i:s'),
            'created_user' => session()->get('username'),
            'is_sub' => 1,
            'saved' => 0
        ]);

        return $this->response->setJSON(['status' => 200, 'sub_row_id' => $sub_row_id]);
    }

    public function get_tour_data($t_id)
    {
        $TourQuotationsModel = new TourQuotationsModel();
        $consigneeModel = new ConsigneeModel();

        $data['quotation_details'] = $TourQuotationsModel
            ->select('tour_quotations.*, transfer_milage.transfer_code as code, room_type.rtype, supplier.Address, user_acc_tbl.emp_id, employee.Name as emp_name')
            ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc', 'left')
            ->join('transfer_milage', 'transfer_milage.id = tour_quotations.transfer_code', 'left')
            ->join('room_type', 'room_type.id = tour_quotations.room_type', 'left')
            ->join('user_acc_tbl', 'user_acc_tbl.user = tour_quotations.created_user', 'left')
            ->join('employee', 'CAST(employee.emp_num AS CHAR) = CAST(user_acc_tbl.emp_id AS CHAR)', 'left')
            ->where('tour_quotations.quotation_id', $t_id)
            ->orderBy('tour_quotations.quotation_day', 'ASC')
            ->findAll();

        // log_message('critical', 'data: ' . $TourQuotationsModel->getLastQuery());

        // Step 2: Get the quotation_id from the first result (assuming there is at least one result)
        if (!empty($data['quotation_details'])) {

            $quotation_id = $data['quotation_details'][0]['quotation_id'];

            // Step 3: Get results from the consignee table where tour_id equals quotation_id
            $consigneeResults = $consigneeModel->where('tour_id', $quotation_id)->findAll();

            $tourSubLocationsController = new TourSubLocationsController();
            $tourActivityController = new TourActivityController();

            $last_saved_date = $this->get_last_saved_date($quotation_id);

            // Step 4: Append the consignee results to the quotation details
            foreach ($data['quotation_details'] as $key => $detail) {
                // consignee -- NOT REQUIRED
                $data['quotation_details'][$key]['consignee'] = $consigneeResults[0];

                // if $detail['tour_date'] == $last_saved_date
                $data['quotation_details'][$key]['last_saved_date'] = $detail['tour_date'] == $last_saved_date ? true : false;

                // sub locations
                $sub_locations = $tourSubLocationsController->get_sub_locations($quotation_id, $detail['tour_date']);
                if (!empty($sub_locations)) {
                    $sub_locations = json_decode($sub_locations, true);

                    // foreach($sub_locations as $key => $s_loc){
                    //     $ref = $s_loc['ref_no'];
                    //     $activities = $tourSubLocationsController->get_sub_location_activities($ref, $quotation_id, $detail['quotation_day'], $detail['tour_date']);
                    //     $sub_locations[$key]['sub_activities'] = $activities;
                    // }
                }
                $data['quotation_details'][$key]['sub_locations'] = $sub_locations;

                // tour activities
                $tourActivities = $tourActivityController->get_tour_activities($quotation_id, $detail['tour_date']);
                if (!empty($tourActivities)) {
                    $tourActivities = json_decode($tourActivities, true);
                    if ($tourActivities['status'] == 200) {
                        $tourActivities = $tourActivities['activities'];
                    } else {
                        $tourActivities = [];
                    }
                }
                $data['quotation_details'][$key]['tour_activities'] = $tourActivities;

                // get itenaries
                $itenaries = $this->itinerary_all($quotation_id, $detail['quotation_day'], $detail['tour_date']);
                if (!empty($itenaries)) {
                    $itenaries = json_decode($itenaries, true);
                }
                $data['quotation_details'][$key]['itenaries'] = $itenaries;
            }
        }

        return $this->response->setJSON(['data' => $data['quotation_details']]);
    }

    public function get_last_saved_date($tour_id)
    {
        $TourQuotationsModel = new TourQuotationsModel();
        $last_date = $TourQuotationsModel
            ->select('tour_date')
            ->where('quotation_id', $tour_id)
            ->where('saved', 1)
            ->orderBy('tour_date', 'DESC')
            ->first();

        return $last_date ? json_encode($last_date['tour_date']) : json_encode('');
    }

    public function get_mileage()
    {
        $tour_id = $this->request->getGet('tour_id');
        $date = $this->request->getGet('date');

        $TourQuotationsModel = new TourQuotationsModel();
        $data = $TourQuotationsModel->select('distance, extra_kilo')
            ->where('quotation_id', $tour_id)
            ->where('tour_date', $date)
            ->findAll();
        return $this->response->setJSON(['status' => 200, 'data' => $data]);
    }

    public function copy_tour()
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
                'meal_code' => $quotation_details['meal_code'],
                'hotel_acc' => $quotation_details['hotel_acc'],
                'hotel_name' => $quotation_details['hotel_name'],
                'room_type' => $quotation_details['room_type'],
                'room_type_id' => $quotation_details['room_type_id'],
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
                'saved' => 1
            ];

            $updated = $TourQuotationsModel
                ->where('quotation_id', $tour)
                ->where('quotation_day', $quotation_details['quotation_day'] + 1)
                ->where('tour_date', $nextd)
                ->set($data)
                ->update();

            if ($updated) {
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

            // Get existing data for audit log before deletion
            $existingData = $tourQuotationsModel->where('quotation_id', $tour)->where('tour_date', $date)->first();

            if (!empty($existingData) && $existingData['payment_request'] == 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'This date cannot be deleted because its hotel payment request has already been accepted.']);
            }

            if ($tourQuotationsModel->where('quotation_id', $tour)->where('tour_date', $date)->delete()) {

                // Audit Log: Hotel date DELETE
                $auditLogger = new \App\Libraries\AuditLogger();
                $auditLogger->logHotelDateChange($tour, 'DELETE', $existingData, ['tour_date' => $date, 'row_id' => $row], session()->get('user_id'));

                $tourSubLocationsModel->where('quotation_id', $tour)->where('tour_row', $row)->where('date_id', $date)->delete();
                $tourActivityModel->where('tour_id', $tour)->where('tour_row', $row)->where('tour_date', $date)->delete();
                // log_message('critical', $tourActivityModel->getLastQuery());
                $tourItineraryModel->where('tour_id', $tour)->where('tour_row', $row)->where('tour_date', $date)->delete();

                // add new empty row to the quotation table
                $tourQuotationsModel->insert([
                    'quotation_id' => $tour,
                    'quotation_day' => $row,
                    'tour_date' => $date,
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

        // log_message('critical', $extraKilometersModel->getLastQuery());

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

            // Get old data before update
            $oldData = $consigneeModel->where('tour_id', $tour_id)->first();
            $oldValues = [
                'sgl' => $oldData['sgl'] ?? null,
                'dbl' => $oldData['dbl'] ?? null,
                'tpl' => $oldData['tpl'] ?? null,
                'dbl_twin' => $oldData['dbl_twin'] ?? null
            ];

            $data = [
                'sgl' => $sgl,
                'dbl' => $dbl,
                'tpl' => $tpl,
                'dbl_twin' => $twn
            ];

            if ($consigneeModel->where('tour_id', $tour_id)->set($data)->update()) {
                // Audit Log: Room Count Update
                $auditLogger = new \App\Libraries\AuditLogger();
                $auditLogger->logRoomCountChange($tour_id, $oldValues, $data, session()->get('user_id'));

                return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated", "tour_id" => $tour_id]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } else {
            // Bad Request
            return $this->response->setJSON(["status" => 400, "message" => "Please provide valid data."]);
        }
    }

    public function finalize($tour)
    {
        try {

            $total_room_cost = $this->request->getVar('room_cost');
            $total_transport_cost = $this->request->getVar('transport_cost');
            $total_mileage = $this->request->getVar('total_mileage');
            $total_days = $this->request->getVar('t_days');
            $total_bata_rate = $this->request->getVar('t_bata_rate');
            $activity_fee_total = $this->request->getVar('activity_fee_total');
            $is_template = $this->request->getVar('is_template');
            $tot_per_person_cost = $this->request->getVar('tot_per_person_cost');

            $ConsigneeModel = new ConsigneeModel();

            // get hotel_only and transport_only status from consignee table
            $consignee = $ConsigneeModel->where('tour_id', $tour)->first();
            $hotel_only = $consignee['hotel_only'];
            $transport_only = $consignee['transport_only'];
            $c_sgl = $consignee['sgl'];
            $c_dbl = $consignee['dbl'];
            $c_tpl = $consignee['tpl'];

            if ($hotel_only == 1 || ($hotel_only == 0 && $transport_only == 0)) {

                if ($c_sgl == 0 && $c_dbl == 0 && $c_tpl == 0) {
                    return $this->response->setJSON(["status" => 400, "message" => "Please provide valid room counts."]);
                }

                if ($total_room_cost == 0 || !is_numeric($total_room_cost)) {
                    return $this->response->setJSON(["status" => 400, "message" => "Total Room cost required and must be valid numbers."]);
                }
            }

            if ($transport_only == 1) {
                if ($total_mileage == 0 || !is_numeric($total_mileage) || $total_transport_cost == 0 || !is_numeric($total_transport_cost)) {
                    return $this->response->setJSON(["status" => 400, "message" => "Total Mileage and Total Transport Cost required and must be valid numbers."]);
                }
            }

            // validate total days and total bata rate
            // if ($total_days == 0 || !is_numeric($total_days) || $total_bata_rate == 0 || !is_numeric($total_bata_rate)) {
            //     return $this->response->setJSON(["status" => 400, "message" => "Total Days and Total Bata Rate are required and must be valid numbers."]);
            // }

            $total_days = is_numeric($total_days) ? $total_days : 0;
            $total_bata_rate = is_numeric($total_bata_rate) ? $total_bata_rate : 0;

            $data = [
                'finalize' => 1,
                'finalize_txt' => 0
            ];

            $TourQuotationsModel = new TourQuotationsModel();

            // get records from tour_quotations table
            $quotation_details = $TourQuotationsModel->where('quotation_id', $tour)->findAll();
            // if empty return error
            if (empty($quotation_details)) {
                return $this->response->setJSON(["status" => 400, "message" => "No valid data found for the tour dates."]);
            }

            if (($TourQuotationsModel->set($data)->where('quotation_id', $tour)->update()) && ($ConsigneeModel->set($data)->where('tour_id', $tour)->update())) {

                $data = [
                    'total_room_cost' => $total_room_cost,
                    'total_transport_cost' => $total_transport_cost,
                    'total_mileage' => $total_mileage,
                    'bata_days' => $total_days,
                    'bata_day_rate' => $total_bata_rate,
                    'activity_fee_total' => $activity_fee_total,
                    'is_template' => $is_template,
                    'per_person_cost' => $tot_per_person_cost
                ];
                $ConsigneeModel->set($data)->where('tour_id', $tour)->update();

                return $this->response->setJSON(["status" => 200, "message" => "Successfully Finalized!", "tour_id" => $tour]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function add_days($tour_id, $days_count)
    {
        // update the end date of the consignee table
        $ConsigneeModel = new ConsigneeModel();
        $consignee = $ConsigneeModel->where('tour_id', $tour_id)->first();
        $end_date = date('Y-m-d', strtotime($consignee['end_date'] . ' + ' . $days_count . ' days'));

        $data = [
            'end_date' => $end_date
        ];

        if ($ConsigneeModel->where('tour_id', $tour_id)->set($data)->update()) {
            $this->add_remove_days_in_qt_table($tour_id);
            return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated", "tour_id" => $tour_id]);
        } else {
            return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
        }
    }

    public function remove_days($tour_id, $days_count)
    {
        // update the end date of the consignee table
        $ConsigneeModel = new ConsigneeModel();
        $consignee = $ConsigneeModel->where('tour_id', $tour_id)->first();
        $end_date = date('Y-m-d', strtotime($consignee['end_date'] . ' - ' . $days_count . ' days'));

        $data = [
            'end_date' => $end_date
        ];

        if ($ConsigneeModel->where('tour_id', $tour_id)->set($data)->update()) {
            $this->add_remove_days_in_qt_table($tour_id);
            return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated", "tour_id" => $tour_id]);
        } else {
            return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
        }
    }

    private function add_remove_days_in_qt_table($tour_id)
    {
        // add empty rows to the tour_quotations table
        $TourQuotationsModel = new TourQuotationsModel();
        $ConsigneeModel = new ConsigneeModel();
        $consignee = $ConsigneeModel->where('tour_id', $tour_id)->first();
        $start_date = $consignee['start_date'];
        $end_date = $consignee['end_date'];

        $days = [];

        // loop thourgh start date to end date
        $x = 0;
        for ($i = strtotime($start_date); $i <= strtotime($end_date); $i = strtotime('+1 day', $i)) {
            $date = date('Y-m-d', $i);
            $days[] = $date;
            $x++;

            // check if the date exists in the tour_quotations table
            $exist = $TourQuotationsModel->where('quotation_id', $tour_id)->where('tour_date', $date)->countAllResults();

            if (!$exist) {
                // insert a new row with the sub_row_id
                $TourQuotationsModel->insert([
                    'quotation_id' => $tour_id,
                    'quotation_day' => $x,
                    'tour_date' => $date,
                    'created_time' => date('Y-m-d H:i:s'),
                    'created_user' => session()->get('username'),
                    'is_sub' => 0,
                    'saved' => 0
                ]);
            }
        }
        // if there any days with tour_id in quotation table where tour_date not in days array
        $TourQuotationsModel->where('quotation_id', $tour_id)->whereNotIn('tour_date', $days)->delete();

        // remove itinerary rows for days no longer within the tour's date range
        $TourItineraryModel = new TourItineraryModel();
        $TourItineraryModel->where('tour_id', $tour_id)->whereNotIn('tour_date', $days)->delete();
    }

    // edit itenary description and area
    public function edit_itenary()
    {
        try {
            $id = $this->request->getPost('id');
            $description = $this->request->getPost('description');
            $area = $this->request->getPost('area');

            $tourItineraryModel = new TourItineraryModel();
            $data = [
                'description' => $description,
                'area' => $area
            ];

            if ($tourItineraryModel->where('id', $id)->set($data)->update()) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated", "id" => $id]);
            }

            return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage(), "line" => $e->getLine()]);
        }
    }

    public function manualRun1()
    {

        $tourQuotationsModel = new TourQuotationsModel();
        // update all records in quotation table to saved = 1
        $tourQuotationsModel->set(['saved' => 1])
            ->where('saved', 0)
            ->update();

        // get all records in consignee table
        $ConsigneeModel = new ConsigneeModel();
        $consignee = $ConsigneeModel->findAll();

        foreach ($consignee as $c) {
            $tour_id = $c['tour_id'];
            $this->add_remove_days_in_qt_table($tour_id);
        }

        echo 'done';
        die();
    }

    public function manualRun2()
    {
        $TourQuotationsModel = new TourQuotationsModel();
        $ConsigneeModel = new ConsigneeModel();

        // get all records in consignee table
        $consignee = $ConsigneeModel->findAll();

        foreach ($consignee as $c) {
            $tour_id = $c['tour_id'];
            $start_date = $c['start_date'];
            $end_date = $c['end_date'];

            if (empty($start_date) && empty($end_date)) {
                // delete records from quotation table where tour_id = $tour_id
                $TourQuotationsModel->where('quotation_id', $tour_id)->delete();
            }
        }

        echo 'done';
        die();
    }

    public function manualRun3()
    {
        $TourQuotationsModel = new TourQuotationsModel();
        $ConsigneeModel = new ConsigneeModel();

        $consignee = $ConsigneeModel->findAll();

        $array = [];
        foreach ($consignee as $c) {

            $tour_id = $c['tour_id'];
            $start_date = $c['start_date'];

            $tours = $TourQuotationsModel
                ->where('quotation_id', $tour_id)
                ->where('tour_date', $start_date)
                ->where('quotation_day !=', 1.0)
                ->findAll();
            if (!empty($tours)) {
                $array[] = $tour_id;
            }
        }

        var_dump($array);
        die();
    }

    public function update_vehicle_rate()
    {
        $tour_id = $this->request->getVar('tour_id');
        $rate = $this->request->getVar('rate');
        $consigneeModel = new ConsigneeModel();

        // Get old data before update
        $oldData = $consigneeModel->where('tour_id', $tour_id)->first();
        $oldValues = [
            'vehicle_rate' => $oldData['vehicle_rate'] ?? null
        ];

        $data = [
            'vehicle_rate' => $rate
        ];

        if ($consigneeModel->where('tour_id', $tour_id)->set($data)->update()) {
            // Audit Log: Vehicle Rate Update
            $auditLogger = new \App\Libraries\AuditLogger();
            $auditLogger->logVehicleRateChange($tour_id, $oldValues, $data, session()->get('user_id'));

            return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated", "tour_id" => $tour_id]);
        } else {
            return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
        }
    }

    // fuction for gen_update use try catch
    public function gen_update()
    {
        try {
            $tour_id = $this->request->getVar('tour_id');
            $driver = $this->request->getVar('driver');
            $driver_txt = $this->request->getVar('driver_txt');
            $guide = $this->request->getVar('guide');
            $guide_txt = $this->request->getVar('guide_txt');

            // Get old data before update
            $consigneeModel = new ConsigneeModel();
            $oldData = $consigneeModel->where('tour_id', $tour_id)->first();
            $oldValues = [
                'transport_agent' => $oldData['transport_agent'] ?? null,
                'transport_agent_name' => $oldData['transport_agent_name'] ?? null,
                'guide' => $oldData['guide'] ?? null,
                'guide_name' => $oldData['guide_name'] ?? null
            ];

            $data = [
                'transport_agent' => $driver,
                'transport_agent_name' => $driver_txt,
                'guide' => $guide,
                'guide_name' => $guide_txt
            ];

            if ($consigneeModel->where('tour_id', $tour_id)->set($data)->update()) {
                // Audit Log: General Update (Driver/Guide)
                $auditLogger = new \App\Libraries\AuditLogger();
                $auditLogger->logGeneralUpdate($tour_id, $oldValues, $data, session()->get('user_id'));

                return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated", "tour_id" => $tour_id]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage(), "line" => $e->getLine()]);
        }
    }

    public function update_passenger_count()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');
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

            $consigneeModel = new ConsigneeModel();
            
            // Check if tour exists
            $tour = $consigneeModel->where('tour_id', $tour_id)->first();
            if (!$tour) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tour not found'
                ]);
            }

            // Update passenger count
            $data = ['passenger_count' => $passenger_count];
            $result = $consigneeModel->where('tour_id', $tour_id)->set($data)->update();

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
}
