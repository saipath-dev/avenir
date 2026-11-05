<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Models\TourActivityModel;
use App\Models\TourQuotationsModel;
use App\Models\TourItineraryModel;

class TourActivityController extends BaseController
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
            $place_fee = 0;
            $place_code = '';
            $activity_tour_id = '';
            $activity_date = '';
            $activity_row = '';
            $pax = 0;
            $tot_fee = 0;

            if (!empty($activity)) {
                $place = $activity['name'];
                $place_name = $activity['place_name']; /////////////////////
                $place_code = $activity['code'];
                $activity_tour_id = $activity['tour_id'];
                $activity_date = $activity['tour_date'];
                $activity_row = $activity['tour_row'];
                $tot_fee = $activity['fee'];


            } else {

                $place = $this->request->getPost('place');
                $place_name = $this->request->getPost('place_name');
                $place_fee = $this->request->getPost('place_fee');
                $place_code = $this->request->getPost('place_code');
                $activity_tour_id = $this->request->getPost('activity_tour_id');
                $activity_date = $this->request->getPost('activity_date');
                $activity_row = $this->request->getPost('activity_row');
                $pax = $this->request->getPost('pax_counts');
                $tot_fee = $place_fee * $pax;
                $lkr_fee = $this->request->getPost('lkr_fee') ?: null;
                $supplier_id = $this->request->getPost('supplier_id') ?: null;
                $supplier_name = $this->request->getPost('supplier_name') ?: null;
                $is_prepaid = $this->request->getPost('is_prepaid') ? 1 : 0;

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
                'lkr_fee' => $lkr_fee,
                'code' => $place_code,
                'ref_no' => $Ref_no,
                'supplier_id' => $supplier_id,
                'supplier_name' => $supplier_name,
                'is_prepaid' => $is_prepaid ?? 0,
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
                $ItineraryController->add_itinerary_activity($activity_tour_id, $activity_row, $activity_date, '', $place_name, $place_name, '', '', '', $Ref_no, $supplier_name);

                // Audit Log: Activity INSERT
                $auditLogger = new \App\Libraries\AuditLogger();
                $auditLogger->logActivityChange($activity_tour_id, 'INSERT', null, [
                    'id' => $ActivityModel->getInsertID(),
                    'tour_id' => $activity_tour_id,
                    'tour_row' => $activity_row,
                    'tour_date' => $activity_date,
                    'name' => $place,
                    'place_name' => $place_name,
                    'fee' => $tot_fee,
                    'code' => $place_code,
                    'ref_no' => $Ref_no
                ], session()->get('user_id'));

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
            // Create a new EntranceFeeModel instance
            $TouractivityModel = new TourActivityModel();
            // Get the entrance fee data
            $activities = $TouractivityModel->select('tour_activity.*, entrance_fee.name as place_name')->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'left')->where('tour_id', $tour)->where('tour_date', $date)->findAll();

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
            $tour_id = $this->request->getGet('tour_id');
            $tour_date = $this->request->getGet('tour_date');
            $tour_row = $this->request->getGet('tour_row');

            $tourActivityModel = new TourActivityModel();
            $activity = $tourActivityModel
                ->where('tour_id', $tour_id)
                ->where('ref_no', $ref_no)
                ->where('tour_date', $tour_date)
                // ->where('tour_row', $tour_row)
                ->first();

            if (!empty($activity) && $activity['payment_request'] == 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'This activity cannot be deleted because its payment request has already been accepted.']);
            }

            $tour_id = $activity['tour_id'];
            $tour_row_id = $activity['tour_row'];
            $tour_day = $activity['tour_date'];
            $amount = $activity['fee'];

            $tourQuotationsModel = new TourQuotationsModel();
            $quotation = $tourQuotationsModel->where('quotation_id', $tour_id)->where('tour_date', $tour_day)->first();

            // log_message('critical', $tourQuotationsModel->getLastQuery());

            $activity_fee = $quotation['extra_act_fee'];
            $new_amount = $activity_fee - $amount;

            $act_deleted = $tourActivityModel->where('tour_id', $tour_id)->where('ref_no', $ref_no)->where('tour_date', $tour_day)
            // ->where('tour_row', $tour_row_id)
            ->delete();

            if ($act_deleted) {
                $tourQuotationsModel->where('quotation_id', $tour_id)->where('tour_date', $tour_day)->set('extra_act_fee', $new_amount)->update();
                $tourItineraryModel = new TourItineraryModel();
                $tourItineraryModel->where('tour_id', $tour_id)
                // ->where('tour_row', $tour_row_id)
                ->where('tour_date', $activity['tour_date'])->where('ref_no', $ref_no)->delete();

                // Audit Log: Activity DELETE
                $auditLogger = new \App\Libraries\AuditLogger();
                $auditLogger->logActivityChange($tour_id, 'DELETE', $activity, [
                    'id' => $id,
                    'ref_no' => $ref_no,
                    'tour_date' => $tour_date
                ], session()->get('user_id'));

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
            $activity_tour_id = $this->request->getPost('activity_tour_id');
            $activity_date = $this->request->getPost('activity_date');
            $activity_row = $this->request->getPost('activity_row');
            $location_ref = $this->request->getPost('location_ref');
            $supplier_id = $this->request->getPost('supplier_id') ?: null;
            $supplier_name = $this->request->getPost('supplier_name') ?: null;

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
                'ref_no' => $Ref_no,
                'supplier_id' => $supplier_id,
                'supplier_name' => $supplier_name,
            ];

            // Insert
            if ($ActivityModel->insert($data)) {

                $ItineraryController = new ItineraryController();
                $ItineraryController->add_itinerary_activity_location($activity_tour_id, $activity_row, $activity_date, $location_ref, $place_name, '', '', '', '', $Ref_no, $supplier_name);

                // Audit Log: Sub-location Activity INSERT
                $auditLogger = new \App\Libraries\AuditLogger();
                $auditLogger->logActivityChange($activity_tour_id, 'INSERT', null, [
                    'id' => $ActivityModel->getInsertID(),
                    'tour_id' => $activity_tour_id,
                    'tour_row' => $activity_row,
                    'tour_date' => $activity_date,
                    'name' => $place,
                    'place_name' => $place_name,
                    'code' => $place_code,
                    'ref_no' => $Ref_no,
                    'location_ref' => $location_ref,
                    'type' => 'sub_location_activity'
                ], session()->get('user_id'));

                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Activity created successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
