<?php

namespace App\Controllers\Inquiry;

use App\Controllers\BaseController;
use App\Models\InquiryActivityModel;
use App\Models\InquiryQuotationsModel;
use App\Models\InquiryItineraryModel;
use App\Models\InquiryConsigneeModel;

class InquiryActivityController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function create_activity($activity = null)
    {
        try {
            $place = '';
            $place_name = '';
            $place_code = '';
            $activity_inquiry_id = '';
            $activity_date = '';
            $activity_row = '';

            if (!empty($activity)) {
                $place = $activity['name'];
                $place_name = $activity['place_name'];
                $place_code = $activity['code'];
                $activity_inquiry_id = $activity['inquiry_id'];
                $activity_date = $activity['inquiry_date'];
                $activity_row = $activity['inquiry_row'];
            } else {
                $place = $this->request->getPost('place');
                $place_name = $this->request->getPost('place_name');
                $place_code = $this->request->getPost('place_code');
                $activity_inquiry_id = $this->request->getPost('activity_inquiry_id');
                $activity_date = $this->request->getPost('activity_date');
                $activity_row = $this->request->getPost('activity_row');

                // Validate the input data
                $validation = service('validation');
                $validation->setRules([
                    'place' => 'required',
                    'activity_inquiry_id' => 'required',
                    'activity_date' => 'required',
                    'activity_row' => 'required'
                ]);

                if (!$validation->withRequest($this->request)->run()) {
                    // Validation failed, return the validation errors
                    return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
                }
            }

            // Get Ref No in Consignee Table
            $consigneeModel = new InquiryConsigneeModel();
            $Ref_no = $consigneeModel->incrementRefno($activity_inquiry_id);

            // Create a new InquiryActivityModel instance
            $ActivityModel = new InquiryActivityModel();

            $data = [
                'inquiry_id' => $activity_inquiry_id,
                'inquiry_row' => $activity_row,
                'inquiry_date' => $activity_date,
                'name' => $place,
                'code' => $place_code,
                'ref_no' => $Ref_no,
                'place_name' => $place_name
            ];

            // Insert
            if ($ActivityModel->insert($data)) {
                $ItineraryController = new InquiryItineraryController();
                $ItineraryController->add_itinerary_activity($activity_inquiry_id, $activity_row, $activity_date, '', $place_name, $place_name, '', '', '', $Ref_no);

                // Return a success response
                return json_encode(['status' => 201, 'message' => 'Activity created successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return json_encode(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function get_tour_activities($tour, $date)
    {
        try {
            // Create a new InquiryActivityModel instance
            $InquiryActivityModel = new InquiryActivityModel();
            // Get the activity data
            $activities = $InquiryActivityModel->select('inquiry_activities.*, entrance_fee.name as place_name')
                ->join('entrance_fee', 'entrance_fee.id = inquiry_activities.name', 'left')
                ->where('inquiry_id', $tour)
                ->where('inquiry_date', $date)
                ->findAll();

            return json_encode(['status' => 200, 'activities' => $activities]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function delete_activity()
    {
        try {
            $id = $this->request->getGet('id');
            $ref_no = $this->request->getGet('ref_no');
            $inquiry_id = $this->request->getGet('inquiry_id');
            $inquiry_date = $this->request->getGet('inquiry_date');
            $inquiry_row = $this->request->getGet('inquiry_row');

            $inquiryActivityModel = new InquiryActivityModel();
            $activity = $inquiryActivityModel
                ->where('inquiry_id', $inquiry_id)
                ->where('ref_no', $ref_no)
                ->where('inquiry_date', $inquiry_date)
                ->first();

            $inquiry_id = $activity['inquiry_id'];
            $inquiry_row_id = $activity['inquiry_row'];
            $tour_day = $activity['inquiry_date'];

            $act_deleted = $inquiryActivityModel->where('inquiry_id', $inquiry_id)
                ->where('ref_no', $ref_no)
                ->where('inquiry_date', $tour_day)
                ->delete();

            if ($act_deleted) {
                $inquiryItineraryModel = new InquiryItineraryModel();
                $inquiryItineraryModel->where('inquiry_id', $inquiry_id)
                    ->where('inquiry_date', $activity['inquiry_date'])
                    ->where('ref_no', $ref_no)
                    ->delete();

                return $this->response->setJSON(["status" => 200, "message" => "Activity Delete successfully!"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage(), "line" => $e->getLine()]);
        }
    }

    public function create_sub_location_activity()
    {
        try {
            $place = $this->request->getPost('place');
            $place_name = $this->request->getPost('place_name');
            $place_code = $this->request->getPost('place_code');
            $activity_inquiry_id = $this->request->getPost('activity_inquiry_id');
            $activity_date = $this->request->getPost('activity_date');
            $activity_row = $this->request->getPost('activity_row');
            $location_ref = $this->request->getPost('location_ref');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'place' => 'required',
                'activity_inquiry_id' => 'required',
                'activity_date' => 'required',
                'activity_row' => 'required'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Get Ref No in Consignee Table
            $consigneeModel = new InquiryConsigneeModel();
            $Ref_no = $consigneeModel->incrementRefno($activity_inquiry_id);

            // Create a new InquiryActivityModel instance
            $ActivityModel = new InquiryActivityModel();

            $data = [
                'inquiry_id' => $activity_inquiry_id,
                'inquiry_row' => $activity_row,
                'inquiry_date' => $activity_date,
                'name' => $place,
                'code' => $place_code,
                'ref_no' => $Ref_no,
                'place_name' => $place_name
            ];

            // Insert
            if ($ActivityModel->insert($data)) {
                $ItineraryController = new InquiryItineraryController();
                $ItineraryController->add_itinerary_activity_location($activity_inquiry_id, $activity_row, $activity_date, $location_ref, $place_name, '', '', '', '', $Ref_no);

                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Activity created successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}