<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountNumbersModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\CountryModel;
use App\Models\CustomerModel;
use App\Models\RoomTypeModel;
use App\Models\TransferMilageModel;
use App\Models\TourQuotationsModel;
use Exception;
use PhpParser\JsonDecoder;

class ItineraryController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function index()
    {
        $data = array();
    }

    public function create()
    {
        try {
            $itinerary = $this->request->getPost('itinerary');
            $itinerary_tour_id = $this->request->getPost('itinerary_tour_id');
            $itinerary_date = $this->request->getPost('itinerary_date');
            $itinerary_row = $this->request->getPost('itinerary_row');
            $itinerary_order_id = $this->request->getPost('itinerary_order_id');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'itinerary' => 'required|strip_tags',
                'itinerary_tour_id' => 'required|strip_tags',
                'itinerary_date' => 'required|strip_tags',
                'itinerary_row' => 'required|strip_tags',
                'itinerary_order_id' => 'required|strip_tags'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Create a new RoomTypeModel instance
            $tourItineraryModel = new \App\Models\TourItineraryModel();
            $consigneeModel = new \App\Models\ConsigneeModel();
            $Ref_no = $consigneeModel->incrementRefno($itinerary_tour_id);

            $order_id = $itinerary_order_id + 0.01;

            // convert $order_id to float
            $order_id = floatval($order_id);

            // check order_id is already exist
            $order = $tourItineraryModel->where('tour_id', $itinerary_tour_id)->where('tour_row', $itinerary_row)->where('order_id', $order_id)->where('tour_date', $itinerary_date)->countAllResults();
            // log_message('critical', $tourItineraryModel->getLastQuery());
            if ($order > 0) {
               return $this->response->setJSON(['status' => 500, 'message' => 'Order ID already exist! Select another Main Itinerary']);
            }

            $data = [
                'tour_id' => $itinerary_tour_id,
                'tour_row' => $itinerary_row,
                'tour_date' => $itinerary_date,
                'order_id' => $order_id,
                'description' => $itinerary,
                'area' => '',
                'bf' => '',
                'lunch' => '',
                'dinner' => '',
                'insert_date' => date('Y-m-d H:i:s'),
                'ref_no' => $Ref_no
            ];

            // Insert
            if ($tourItineraryModel->insert($data)) {
                // Return a success response
                return $this->response->setJSON(['status' => 201, 'message' => 'Itinerary created successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function add_itinerary($tour_id, $quotation_day, $t_date, $pick_location, $drop_location, $drop_city, $hot_name, $room_type, $meal_code, $ref_no)
    {
        try {
            
            $tourItineraryModel = new \App\Models\TourItineraryModel();
            $ger_result = $tourItineraryModel->where('tour_id', $tour_id)->where('tour_row', $quotation_day)->findAll();

            // get ger_result count & check if the itinerary
            if (count($ger_result) > 0) {
                // delete itinerary table records where tour_id, tour_row, tour_date
                $tourItineraryModel->where('tour_id', $tour_id)->where('tour_row', $quotation_day)->where('order_id <', 20)->orWhere('order_id >', 59)->delete();
                // log_message('critical', $tourItineraryModel->getLastQuery());
            } 

            $data = array();

            if ($quotation_day == 1) { // first day
                $appended_data = array();

                $consigneeModel = new \App\Models\ConsigneeModel();
                $consignee = $consigneeModel->where('tour_id', $tour_id)->first();
                $arrival_flight = $consignee['arrival_flight'];
                $arrival_flight_time = $consignee['arrival_flight_time'];

                if ($meal_code == 'hb' || $meal_code == 'fb') { // Dinner included
                    $appended_data = array(
                        'tour_id' => $tour_id,
                        'tour_row' => $quotation_day,
                        'tour_date' => $t_date,
                        'order_id' => 60,
                        'description' => 'Dinner at ' . $hot_name,
                        'area' => '',
                        'bf' => '',
                        'lunch' => '',
                        'dinner' => 'HTL',
                        'insert_date' => date('Y-m-d H:i:s')
                    );
                } else {
                    $appended_data = array(
                        'tour_id' => $tour_id,
                        'tour_row' => $quotation_day,
                        'tour_date' => $t_date,
                        'order_id' => 60,
                        'description' => 'Dinner is not included this day',
                        'area' => '',
                        'bf' => '',
                        'lunch' => '',
                        'dinner' => '',
                        'insert_date' => date('Y-m-d H:i:s')
                    );
                }

                $data = [

                    array(
                        'tour_id' => $tour_id,
                        'tour_row' => $quotation_day,
                        'tour_date' => $t_date,
                        'order_id' => 0,
                        'description' => 'Arrival at Bandaranayake International Airport by ' . $arrival_flight . ' at ' . $arrival_flight_time,
                        'area' => 'Katunayake',
                        'bf' => '',
                        'lunch' => '',
                        'dinner' => '',
                        'insert_date' => date('Y-m-d H:i:s')
                    ),
                    array(
                        'tour_id' => $tour_id,
                        'tour_row' => $quotation_day,
                        'tour_date' => $t_date,
                        'order_id' => 1,
                        'description' => 'Attn Guide : Don’t forget to inform guest to put seatbelt during driving',
                        'area' => '',
                        'bf' => '',
                        'lunch' => '',
                        'dinner' => '',
                        'insert_date' => date('Y-m-d H:i:s')
                    ),
                    array(
                        'tour_id' => $tour_id,
                        'tour_row' => $quotation_day,
                        'tour_date' => $t_date,
                        'order_id' => 2,
                        'description' => 'Meet The guide & Transfer to Hotel',
                        'area' => '',
                        'bf' => '',
                        'lunch' => '',
                        'dinner' => '',
                        'insert_date' => date('Y-m-d H:i:s')
                    ),
                    array(
                        'tour_id' => $tour_id,
                        'tour_row' => $quotation_day,
                        'tour_date' => $t_date,
                        'order_id' => 70,
                        'description' => 'Stay @ Hotel',
                        'area' => $hot_name,
                        'bf' => '',
                        'lunch' => '',
                        'dinner' => '',
                        'insert_date' => date('Y-m-d H:i:s')
                    ),
                ];
                $data[] = $appended_data;
            } else { // other days

                $consigneeModel = new \App\Models\ConsigneeModel();
                $consignee = $consigneeModel->where('tour_id', $tour_id)->first();
                $enddate = $consignee['end_date'];
                $departure_flight = $consignee['departure_flight'];
                // log_message('critical', $consigneeModel->getLastQuery());

                if ($enddate == $t_date) { // last day
                    $data = [
                        array(
                            'tour_id' => $tour_id,
                            'tour_row' => $quotation_day,
                            'tour_date' => $t_date,
                            'order_id' => 67,
                            'description' => 'Arrival at APT Departure ' . $departure_flight,
                            'area' => $hot_name,
                            'bf' => '',
                            'lunch' => '',
                            'dinner' => '',
                            'insert_date' => date('Y-m-d H:i:s')
                        ),
                        array(
                            'tour_id' => $tour_id,
                            'tour_row' => $quotation_day,
                            'tour_date' => $t_date,
                            'order_id' => 68,
                            'description' => 'Transfer to Bandaranayake International Airport',
                            'area' => '',
                            'bf' => '',
                            'lunch' => '',
                            'dinner' => '',
                            'insert_date' => date('Y-m-d H:i:s')
                        ),
                        array(
                            'tour_id' => $tour_id,
                            'tour_row' => $quotation_day,
                            'tour_date' => $t_date,
                            'order_id' => 69,
                            'description' => 'Arrival @ Hotel',
                            'area' => '',
                            'bf' => '',
                            'lunch' => '',
                            'dinner' => '',
                            'insert_date' => date('Y-m-d H:i:s')
                        ),
                        array(
                            'tour_id' => $tour_id,
                            'tour_row' => $quotation_day,
                            'tour_date' => $t_date,
                            'order_id' => 70,
                            'description' => 'Departure SQ,TG,CX ex',
                            'area' => '',
                            'bf' => '',
                            'lunch' => '',
                            'dinner' => '',
                            'insert_date' => date('Y-m-d H:i:s')
                        ),
                    ];
                } else { // other days

                    if ($pick_location == 1) {
                        $data = [
                            array(
                                'tour_id' => $tour_id,
                                'tour_row' => $quotation_day,
                                'tour_date' => $t_date,
                                'order_id' => 0,
                                'description' => 'Breakfast at Hotel',
                                'area' => '',
                                'bf' => '',
                                'lunch' => '',
                                'dinner' => '',
                                'insert_date' => date('Y-m-d H:i:s')
                            ),
                            array(
                                'tour_id' => $tour_id,
                                'tour_row' => $quotation_day,
                                'tour_date' => $t_date,
                                'order_id' => 70,
                                'description' => 'Stay @ Hotel',
                                'area' => $hot_name,
                                'bf' => '',
                                'lunch' => '',
                                'dinner' => '',
                                'insert_date' => date('Y-m-d H:i:s')
                            ),
                        ];
                    } else {

                        $appended_data = array();

                        if ($meal_code == 'fb') { // Lunch included
                            $appended_data = array(
                                'tour_id' => $tour_id,
                                'tour_row' => $quotation_day,
                                'tour_date' => $t_date,
                                'order_id' => 60,
                                'description' => 'Lunch at ' . $hot_name,
                                'area' => '',
                                'bf' => '',
                                'lunch' => 'HTL',
                                'dinner' => '',
                                'insert_date' => date('Y-m-d H:i:s')
                            );
                        } else {
                            $appended_data = array(
                                'tour_id' => $tour_id,
                                'tour_row' => $quotation_day,
                                'tour_date' => $t_date,
                                'order_id' => 60,
                                'description' => 'Lunch is not included this day',
                                'area' => '',
                                'bf' => '',
                                'lunch' => '',
                                'dinner' => '',
                                'insert_date' => date('Y-m-d H:i:s')
                            );
                        }

                        $data = [
                            array(
                                'tour_id' => $tour_id,
                                'tour_row' => $quotation_day,
                                'tour_date' => $t_date,
                                'order_id' => 0,
                                'description' => 'Breakfast at Hotel',
                                'area' => '',
                                'bf' => 'HTL',
                                'lunch' => '',
                                'dinner' => '',
                                'insert_date' => date('Y-m-d H:i:s')
                            ),
                            array(
                                'tour_id' => $tour_id,
                                'tour_row' => $quotation_day,
                                'tour_date' => $t_date,
                                'order_id' => 70,
                                'description' => 'Stay @ Hotel',
                                'area' => $hot_name,
                                'bf' => '',
                                'lunch' => '',
                                'dinner' => '',
                                'insert_date' => date('Y-m-d H:i:s')
                            ),
                        ];
                        $data[] = $appended_data;
                    }
                }
            }

            $tourIteneraryModel = new \App\Models\TourItineraryModel();
            $tourIteneraryModel->insertBatch($data);

            // log_message('critical', $tourIteneraryModel->getLastQuery());
            return json_encode(array('status' => 'success', 'message' => 'Itinerary added successfully'));
        } catch (Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function add_itinerary_location($tour_id, $quotation_day, $t_date, $pick_location, $drop_location, $drop_city, $hot_name, $room_type, $meal_code, $ref_no)
    {
        // log_message('critical', 'add_itinerary_location');
        try {
            $tourIteneraryModel = new \App\Models\TourItineraryModel();
            $order_res = $tourIteneraryModel->select('max(order_id) as order_id')->where('tour_id', $tour_id)->where('tour_row', $quotation_day)->where('order_id >=', 20)->where('order_id <=', 39)->first();

            // $order_id = $order ? $order['order_id'] + 1 : 20;
            $order = $order_res['order_id'];

            if ($order == null) {
                $order_id = 20;
            } else {
                $order_id = $order + 1;
            }

            $des = 'Transfer to ' . $drop_location;

            $data = [
                'tour_id' => $tour_id,
                'tour_row' => $quotation_day,
                'tour_date' => $t_date,
                'order_id' => $order_id,
                'description' => $des,
                'area' => $drop_city,
                'bf' => '',
                'lunch' => '',
                'dinner' => '',
                'insert_date' => date('Y-m-d H:i:s'),
                'ref_no' => $ref_no
            ];

            $tourIteneraryModel->insert($data);

            // log_message('critical', $tourIteneraryModel->getLastQuery());

            return json_encode(['status' => 'success', 'message' => 'Itinerary added successfully']);
        } catch (Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function add_itinerary_activity_location($tour_id, $quotation_day, $t_date, $location_ref, $place_name, $drop_city, $hot_name, $room_type, $meal_code, $ref_no, $supplier_name = null)
    {
        // log_message('critical', 'add_itinerary_activity_location');
        try {

            $data = array();

            $tourIteneraryModel = new \App\Models\TourItineraryModel();
            $order_res = $tourIteneraryModel->select('max(order_id) as order_id')->where('tour_id', $tour_id)->where('tour_row', $quotation_day)->where('tour_date', $t_date)->where('loc_ref', $location_ref)->first();
            
            $order = $order_res['order_id'];

            if ($order == null) {
                // log_message('critical', 'order is null');
                $order_ref_id = $tourIteneraryModel->select('order_id')->where('tour_id', $tour_id)->where('tour_row', $quotation_day)->where('tour_date', $t_date)->where('ref_no', $location_ref)->first();

                $order_id = $order_ref_id['order_id'] + 0.1;
            } else {
                $order_id = $order + 0.1;

            }


            if ($place_name == 'LUNCH - LUNCH') { // Lunch

                $lunch_desc = !empty($supplier_name)
                    ? 'Lunch @ ' . $supplier_name
                    : 'Lunch @ Local restaurant On the way';

                $data = [
                    'tour_id' => $tour_id,
                    'tour_row' => $quotation_day,
                    'tour_date' => $t_date,
                    'order_id' => $order_id,
                    'description' => $lunch_desc,
                    'area' => $drop_city,
                    'bf' => '',
                    'lunch' => '',
                    'dinner' => '',
                    'insert_date' => date('Y-m-d H:i:s'),
                    'ref_no' => $ref_no,
                    'loc_ref' => $location_ref
                ];
            } else {
                $data = [
                    'tour_id' => $tour_id,
                    'tour_row' => $quotation_day,
                    'tour_date' => $t_date,
                    'order_id' => $order_id,
                    'description' => $place_name,
                    'area' => $drop_city,
                    'bf' => '',
                    'lunch' => '',
                    'dinner' => '',
                    'insert_date' => date('Y-m-d H:i:s'),
                    'ref_no' => $ref_no,
                    'loc_ref' => $location_ref
                ];
            }

            $tourIteneraryModel->insert($data);

            return json_encode(array('status' => 'success', 'message' => 'Itinerary added successfully'));
        } catch (Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function add_itinerary_activity($tour_id, $quotation_day, $t_date, $pick_location, $place_name, $drop_city, $hot_name, $room_type, $meal_code, $ref_no, $supplier_name = null)
    {
        try {
            $tourIteneraryModel = new \App\Models\TourItineraryModel();
            $order = $tourIteneraryModel->select('max(order_id) as order_id')->where('tour_id', $tour_id)->where('tour_row', $quotation_day)->where('order_id >=', 40)->where('order_id <=', 59)->first();

            // $order_id = $order ? $order['order_id'] + 1 : 40;
            if ($order['order_id'] == null) {
                $order_id = 40;
            } else {
                $order_id = $order['order_id'] + 1;
            }

            if ($place_name == 'LUNCH - LUNCH') { // Lunch

                $lunch_desc = !empty($supplier_name)
                    ? 'Lunch @ ' . $supplier_name
                    : 'Lunch @ Local restaurant On the way';

                $data = [
                    'tour_id' => $tour_id,
                    'tour_row' => $quotation_day,
                    'tour_date' => $t_date,
                    'order_id' => $order_id,
                    'description' => $lunch_desc,
                    'area' => '',
                    'bf' => '',
                    'lunch' => '',
                    'dinner' => '',
                    'insert_date' => date('Y-m-d H:i:s'),
                    'ref_no' => $ref_no
                ];
            } else {

                $data = [
                    'tour_id' => $tour_id,
                    'tour_row' => $quotation_day,
                    'tour_date' => $t_date,
                    'order_id' => $order_id,
                    'description' => $place_name,
                    'area' => $drop_city,
                    'bf' => '',
                    'lunch' => '',
                    'dinner' => '',
                    'insert_date' => date('Y-m-d H:i:s'),
                    'ref_no' => $ref_no
                ];
            }

            $tourIteneraryModel->insert($data);
            return json_encode(['status' => 'success', 'message' => 'Itinerary added successfully']);
        } catch (Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // delete itinerary
    public function delete_itinerary()
    {
        try {
            $id = $this->request->getPost('id');

            $tourItineraryModel = new \App\Models\TourItineraryModel();
            $tourItineraryModel->where('id', $id)->delete();
            // log_message('critical', $tourItineraryModel->getLastQuery());

            return json_encode(array('status' => 200, 'message' => 'Itinerary deleted successfully'));
        } catch (Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
