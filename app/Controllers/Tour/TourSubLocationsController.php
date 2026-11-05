<?php

namespace App\Controllers\Tour;

use App\Libraries\MenuLoader;
use App\Controllers\BaseController;

class TourSubLocationsController extends BaseController
{

    public function index($quotation_id = 0)
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $data['quotation_id'] = $quotation_id;
        $data['dates'] = array();

        if ($quotation_id > 0) {
            $tourQuotationModel = new \App\Models\TourQuotationsModel();
            $data['dates'] = $tourQuotationModel->where('quotation_id', $quotation_id)->findAll();

            foreach ($data['dates'] as $key => $date) {
                $tourSubLocationsModel = new \App\Models\TourSubLocationsModel();
                $data['dates'][$key]['sub_locations'] = $tourSubLocationsModel->where('date_id', $date['id'])->findAll();
            }
        }

        return view('pages/tour/tour_sub_locations', $data);
    }

    public function add_sub_location($sub_location = null)
    {
        try {
            log_message('critical', '=== TourSubLocationsController::add_sub_location START ===');
            log_message('critical', 'Request POST data: ' . json_encode($this->request ? $this->request->getPost() : null));
            log_message('critical', 'Function parameter $sub_location: ' . json_encode($sub_location));

            // Get the form data
            $quotation_id = '';
            $date_id = '';
            $t_row = 0;
            $from = '';
            $to = '';
            $estimate_km = 0;
            $actual_km = 0;
            $extra_km = 0;

            if (!empty($sub_location)) {
                $quotation_id = $sub_location['tour'];
                $date_id = $sub_location['date'];
                $t_row = $sub_location['row'];
                $from = $sub_location['from'];
                $to = $sub_location['to'];
                $estimate_km = $sub_location['estimate_km'];
                $actual_km = $sub_location['km'];
                $extra_km = $sub_location['extra_km'];
                log_message('critical', 'Using sub_location parameter');
            } else {
                $quotation_id = $this->request->getPost('tour');
                $date_id = $this->request->getPost('date');
                $t_row = $this->request->getPost('row');
                $from = $this->request->getPost('pick_location');
                $to = $this->request->getPost('drop_location');
                $estimate_km = $this->request->getPost('gdistance');
                $actual_km = $this->request->getPost('distance');
                $extra_km = $this->request->getPost('extrakm');
                log_message('critical', 'Using POST data');

                // validation
                $validation = \Config\Services::validation();
                $validation->setRules([
                    'pick_location' => 'required',
                    'drop_location' => 'required',
                    'distance' => 'required',
                    'tour' => 'required',
                    'date' => 'required'
                ]);

                if ($validation->withRequest($this->request)->run() == false) {
                    log_message('critical', 'Validation failed: ' . json_encode($validation->getErrors()));
                    return json_encode(array('status' => 400, 'message' => 'Please fill all the fields'));
                }
            }

            log_message('critical', 'Parsed data - quotation_id: ' . $quotation_id . ', date_id: ' . $date_id . ', t_row: ' . $t_row . ', from: ' . $from . ', to: ' . $to);

            $pick_location = $from;
            if (strpos(strval($from), ', Sri Lanka') !== false) {
                $pick_location = str_replace(', Sri Lanka', '', $from);
            }

            $drop_location = $to;
            if (strpos(strval($to), ', Sri Lanka') !== false) {
                $drop_location = str_replace(', Sri Lanka', '', $to);
            }

            // Get Ref No in Consignee Table
            log_message('critical', 'About to get Ref_no from ConsigneeModel for quotation_id: ' . $quotation_id);
            $consigneeModel = new \App\Models\ConsigneeModel();
            $Ref_no = $consigneeModel->incrementRefno($quotation_id);
            log_message('critical', 'Ref_no obtained: ' . $Ref_no);

            log_message('critical', 'About to insert sub_location - quotation_id: ' . $quotation_id . ', date_id: ' . $date_id . ', tour_row: ' . $t_row);
            $tourSubLocationsModel = new \App\Models\TourSubLocationsModel();
            $tourSubLocationsModel->insert([
                'quotation_id' => $quotation_id,
                'date_id' => $date_id,
                'tour_row' => $t_row,
                'from' => $pick_location,
                'to' => $drop_location,
                'km' => $actual_km,
                'extra_km' => $extra_km,
                'estimate_km' => $estimate_km,
                'ref_no' => $Ref_no
            ]);
            log_message('critical', 'Sub_location inserted successfully with ID: ' . $tourSubLocationsModel->getInsertID());

            // get hotel_only value from consignee table
            log_message('critical', 'About to fetch consignee for quotation_id: ' . $quotation_id);
            $consignee = $consigneeModel->where('tour_id', $quotation_id)->first();
            log_message('critical', 'Consignee result: ' . json_encode($consignee));

            // Check if consignee exists before accessing array
            if (empty($consignee)) {
                log_message('critical', 'No consignee found for quotation_id: ' . $quotation_id . ' - Using default hotel_only = 0');
                $hotel_only = 0; // Default value
            } else {
                $hotel_only = $consignee['hotel_only'];
                log_message('critical', 'hotel_only value from consignee: ' . $hotel_only);
            }

            // get all sub locations
            log_message('critical', 'Fetching all sub_locations for date_id: ' . $date_id . ', quotation_id: ' . $quotation_id . ', tour_row: ' . $t_row);
            $sub_locations = $tourSubLocationsModel->where('date_id', $date_id)->where('quotation_id', $quotation_id)->where('tour_row', $t_row)->findAll();
            log_message('critical', 'Found ' . count($sub_locations) . ' sub_locations');

            // sum of all km
            $total_km = 0;
            $total_extra_km = 0;
            if ($hotel_only == 0) {
                foreach ($sub_locations as $sub_location) {
                    $total_km += $sub_location['km'];
                    $total_extra_km += $sub_location['extra_km'];
                }
                log_message('critical', 'Calculated total_km: ' . $total_km . ', total_extra_km: ' . $total_extra_km);
            } else {
                log_message('critical', 'Skipping km calculation - hotel_only is 1');
            }

            // update total km in tour_quotations table
            log_message('critical', 'Updating tour_quotations with date_id: ' . $date_id . ', distance: ' . $total_km . ', extra_kilo: ' . $total_extra_km);
            $tourQuotationModel = new \App\Models\TourQuotationsModel();
            $tourQuotationModel->update($date_id, [
                'distance' => $total_km,
                'extra_kilo' => $total_extra_km
            ]);
            log_message('critical', 'tour_quotations updated');

            log_message('critical', 'Calling ItineraryController::add_itinerary_location');
            $itineraryController = new ItineraryController();
            $itineraryController->add_itinerary_location($quotation_id, $t_row, $date_id, $from, $to, $to, '', '', '', $Ref_no);
            log_message('critical', 'Itinerary location added');

            log_message('critical', 'Fetching final sub_locations to return');
            $sub_locations = $this->get_sub_locations($quotation_id, $date_id);
            if (!empty($sub_locations)) {
                $sub_locations = json_decode($sub_locations, true);
            }
            log_message('critical', 'Final sub_locations count: ' . (is_array($sub_locations) ? count($sub_locations) : 'null'));

            log_message('critical', '=== TourSubLocationsController::add_sub_location SUCCESS ===');
            return json_encode(array('status' => 200, 'message' => 'Sub location added successfully', 'sub_locations' => $sub_locations));
        } catch (\Exception $e) {
            log_message('critical', '=== TourSubLocationsController::add_sub_location ERROR ===');
            log_message('critical', 'Exception message: ' . $e->getMessage());
            log_message('critical', 'Exception trace: ' . $e->getTraceAsString());
            return json_encode(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    public function get_sub_locations($tour_id, $date)
    {
        $tourSubLocationsModel = new \App\Models\TourSubLocationsModel();
        $sub_locations = $tourSubLocationsModel
        ->where('quotation_id', $tour_id)
        ->where('date_id', $date)
        ->orderBy('id', 'asc')
        ->findAll();
        foreach ($sub_locations as $key => $s_loc) {
            $ref = $s_loc['ref_no'];
            $activities = $this->get_sub_location_activities($ref, $tour_id, $date);
            $sub_locations[$key]['sub_activities'] = $activities;
        }
        return json_encode($sub_locations);
    }

    public function get_next_location()
    {
        $quotation_id = $this->request->getPost('tour');
        $quotation_row = $this->request->getPost('row');
        $quotation_day = $this->request->getPost('date');

        $tourSubLocationsModel = new \App\Models\TourSubLocationsModel();
        $sub_locations = $tourSubLocationsModel
            ->where('quotation_id', $quotation_id)
            ->where('date_id', $quotation_day)
            ->where('tour_row', $quotation_row)
            ->orderBy('id', 'asc')
            ->findAll();

        $mergedData = [];
        $mergedData = $sub_locations;

        foreach ($sub_locations as $key => $sub_location) {
            $ref = $sub_location['ref_no'];

            $tourItineraryModel = new \App\Models\TourItineraryModel();
            $location_wise_activities = $tourItineraryModel->where('loc_ref', $ref)
                ->where('tour_id', $quotation_id)
                ->where('tour_row', $quotation_row)
                ->where('tour_date', $quotation_day)
                ->findAll();

            // foreach ($location_wise_activities as $activity) {
            //     // Add sub_location details to each activity record
            //     $activity['sub_location'] = $sub_location;
            //     $all_location_wise_activities[$ref][] = $activity;
            // }
            $mergedData[$key]['activity'] = $location_wise_activities ?? null;
        }
        // $data['sub_locations'] = $sub_locations;
        // $data['all_location_wise_activities'] = $all_location_wise_activities;

        // $dates_array = array();
        // foreach ($sub_locations as $sub_location) {
        //     $dates_array[] = $sub_location['date_id'];
        // }

        // $tour_rows = array();
        // foreach ($sub_locations as $sub_location) {
        //     $tour_rows[] = $sub_location['tour_row'];
        // }

        // // unique dates
        // $dates_array = array_unique($dates_array);
        // // unique tour rows
        // $tour_rows = array_unique($tour_rows);


        // return json_encode(array('status' => 200, 'dates' => $dates_array, 'rows' => $tour_rows, 'sub_locations' => $sub_locations));
        return json_encode(array('status' => 200, 'sub_locations' => $mergedData));
    }

    public function get_sub_location_activities($ref, $quotation_id, $quotation_day)
    {
        $tourItineraryModel = new \App\Models\TourItineraryModel();
        $location_wise_activities = $tourItineraryModel->where('loc_ref', $ref)
            ->where('tour_id', $quotation_id)
            ->where('tour_date', $quotation_day)
            ->orderBy('id', 'asc')
            ->findAll();

        return $location_wise_activities ?? [];
    }

    public function location_delete()
    {
        try {
            $id = $this->request->getGet('id');
            $ref_no = $this->request->getGet('ref_no');

            $tourSubLocationsModel = new \App\Models\TourSubLocationsModel();

            $sub_locations = $tourSubLocationsModel->where('id', $id)->findAll();

            // Check if sub-location exists
            if (empty($sub_locations)) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Sub-location not found']);
            }

            $km = $sub_locations[0]['km'];
            $extra_km = $sub_locations[0]['extra_km'];
            $date_id = $sub_locations[0]['date_id'];
            $quotation_id = $sub_locations[0]['quotation_id'];
            $tour_row = $sub_locations[0]['tour_row'];


            $tourQuotationModel = new \App\Models\TourQuotationsModel();
            $quotation = $tourQuotationModel->select('distance,extra_kilo')->where('quotation_id', $quotation_id)->where('quotation_day', $tour_row)->where('tour_date', $date_id)->findAll();

            // get hotel_only value from consignee table
            $consigneeModel = new \App\Models\ConsigneeModel();
            $consignee = $consigneeModel->where('tour_id', $quotation_id)->first();

            // Check if consignee exists before accessing array
            if (empty($consignee)) {
                log_message('warning', 'TourSubLocationsController::location_delete - No consignee found for quotation_id: ' . $quotation_id);
                $hotel_only = 0; // Default value
            } else {
                $hotel_only = $consignee['hotel_only'];
            }

            if (count($quotation) != 0) {

                $total_km = 0;
                $extra_kilo = 0;

                if ($hotel_only == 0) {
                    $total_km = $quotation[0]['distance'] - $km;
                    $extra_kilo = $quotation[0]['extra_kilo'] - $extra_km;
                }

                $tourQuotationModel->where('quotation_id', $quotation_id)->where('quotation_day', $tour_row)->where('tour_date', $date_id)->set(['distance' => $total_km, 'extra_kilo' => $extra_kilo])->update();
            }

            if ($tourSubLocationsModel->delete($id)) {
                $tourItineraryModel = new \App\Models\TourItineraryModel();
                $tourItineraryModel->where('tour_id', $quotation_id)->where('tour_row', $tour_row)->where('tour_date', $date_id)->where('ref_no', $ref_no)->delete();
                $tourItineraryModel->where('tour_id', $quotation_id)->where('tour_row', $tour_row)->where('tour_date', $date_id)->where('loc_ref', $ref_no)->delete();
                return $this->response->setJSON(["status" => 200, "message" => "Location Delete successfully!"]);
            }
            // log_message('critical', $tourSubLocationsModel->getLastQuery());
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    public function get_last_sublocation_of_date($tour_id, $date)
    {
        // get date before the given date
        $date = date('Y-m-d', strtotime($date . ' -1 day'));
        $tourSubLocationsModel = new \App\Models\TourSubLocationsModel();
        $sub_locations = $tourSubLocationsModel->where('quotation_id', $tour_id)->where('date_id', $date)->orderBy('id', 'desc')->first();
        return json_encode($sub_locations);
    }
}
