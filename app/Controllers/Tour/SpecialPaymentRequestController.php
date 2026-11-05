<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\SpecialPaymentRequestModel;
use App\Models\ConsigneeModel;
use App\Models\CustomerModel;

class SpecialPaymentRequestController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route: base_url/tour/special_payment_requests
    public function index()
    {
        $data = array();

        // Load menu data
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Get all requests for admin section (pending, approved, rejected)
        $specialPaymentRequestModel = new SpecialPaymentRequestModel();
        $data['all_requests'] = $specialPaymentRequestModel
            ->select('special_payment_requests.*, consignee.consignee as tour_name')
            ->join('consignee', 'consignee.tour_id = special_payment_requests.tour_id', 'left')
            ->orderBy('requested_date', 'DESC')
            ->findAll();

        return view('pages/tour/special_payment_requests', $data);
    }

    // route: base_url/tour/special_payment_requests/autocomplete
    public function autocomplete_search()
    {
        try {
            $searchTerm = $this->request->getPost('search');

            if (empty($searchTerm)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Search term is required']);
            }

            $consigneeModel = new ConsigneeModel();
            $results = $consigneeModel
                ->select('consignee.tour_id, consignee.manual_id, consignee.consignee, consignee.start_date, consignee.end_date, consignee.agent_name, consignee.passenger_count')
                ->groupStart()
                    ->like('consignee.manual_id', $searchTerm)
                    ->orLike('consignee.consignee', $searchTerm)
                ->groupEnd()
                ->where('consignee.verify', 1)
                ->where('consignee.is_template', 0)
                ->where('consignee.tour_cancel', 0)
                ->orderBy('consignee.start_date', 'DESC')
                ->limit(20)
                ->findAll();

            return $this->response->setJSON(['status' => 200, 'data' => $results]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route: base_url/tour/special_payment_requests/get_details
    public function get_tour_details()
    {
        try {
            $tourId = $this->request->getPost('tour_id');

            if (empty($tourId)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour ID is required']);
            }

            $consigneeModel = new ConsigneeModel();
            $customerModel = new CustomerModel();

            $tourDetails = $consigneeModel
                ->select('consignee.*, customers.Name as agent_full_name')
                ->join('customers', 'customers.Sup_Acc_No = consignee.agent', 'left')
                ->where('consignee.tour_id', $tourId)
                ->first();

            if (empty($tourDetails)) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
            }

            return $this->response->setJSON(['status' => 200, 'data' => $tourDetails]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route: base_url/tour/special_payment_requests/create
    public function create_request()
    {
        try {
            $tourId = $this->request->getPost('tour_id');

            if (empty($tourId)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour ID is required']);
            }

            // Check if request already exists and is pending
            $specialPaymentRequestModel = new SpecialPaymentRequestModel();
            $existingRequest = $specialPaymentRequestModel
                ->where('tour_id', $tourId)
                ->where('status', 'pending')
                ->first();

            if (!empty($existingRequest)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'A pending request already exists for this tour']);
            }

            // Get tour details
            $consigneeModel = new ConsigneeModel();
            $tourDetails = $consigneeModel
                ->select('consignee.*, customers.Name as agent_full_name')
                ->join('customers', 'customers.Sup_Acc_No = consignee.agent', 'left')
                ->where('consignee.tour_id', $tourId)
                ->first();

            if (empty($tourDetails)) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
            }

            // Create request
            $data = [
                'tour_id' => $tourId,
                'manual_id' => $tourDetails['manual_id'] ?? '',
                'agent_name' => $tourDetails['agent_full_name'] ?? $tourDetails['agent_name'] ?? '',
                'consignee' => $tourDetails['consignee'] ?? '',
                'start_date' => $tourDetails['start_date'] ?? null,
                'end_date' => $tourDetails['end_date'] ?? null,
                'pax_count' => $tourDetails['passenger_count'] ?? 0,
                'requested_by' => session()->get('username'),
                'requested_date' => date('Y-m-d H:i:s'),
                'status' => 'pending'
            ];

            $specialPaymentRequestModel->insert($data);

            return $this->response->setJSON(['status' => 200, 'message' => 'Special payment request created successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route: base_url/tour/special_payment_requests/approve
    public function approve_request()
    {
        try {
            // Check if user is admin
            if (session()->get('group_id') != 1) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Access denied. Admin privileges required.']);
            }

            $requestId = $this->request->getPost('request_id');

            if (empty($requestId)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Request ID is required']);
            }

            $specialPaymentRequestModel = new SpecialPaymentRequestModel();
            $request = $specialPaymentRequestModel->find($requestId);

            if (empty($request)) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Request not found']);
            }

            if ($request['status'] !== 'pending') {
                return $this->response->setJSON(['status' => 400, 'message' => 'This request has already been processed']);
            }

            // Approve the request
            $data = [
                'status' => 'approved',
                'admin_user' => session()->get('username'),
                'admin_action_date' => date('Y-m-d H:i:s')
            ];

            $specialPaymentRequestModel->update($requestId, $data);

            return $this->response->setJSON(['status' => 200, 'message' => 'Request approved successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route: base_url/tour/special_payment_requests/reject
    public function reject_request()
    {
        try {
            // Check if user is admin
            if (session()->get('group_id') != 1) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Access denied. Admin privileges required.']);
            }

            $requestId = $this->request->getPost('request_id');
            $notes = $this->request->getPost('notes') ?? '';

            if (empty($requestId)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Request ID is required']);
            }

            $specialPaymentRequestModel = new SpecialPaymentRequestModel();
            $request = $specialPaymentRequestModel->find($requestId);

            if (empty($request)) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Request not found']);
            }

            if ($request['status'] !== 'pending') {
                return $this->response->setJSON(['status' => 400, 'message' => 'This request has already been processed']);
            }

            // Reject the request
            $data = [
                'status' => 'rejected',
                'admin_user' => session()->get('username'),
                'admin_action_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ];

            $specialPaymentRequestModel->update($requestId, $data);

            return $this->response->setJSON(['status' => 200, 'message' => 'Request rejected successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
