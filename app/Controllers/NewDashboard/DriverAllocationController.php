<?php

namespace App\Controllers\NewDashboard;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\ConsigneeModel;
use App\Models\DriverAllocationAuditLogModel;
use App\Models\SupplierModel;
use App\Models\DriverAllocationModel;
use App\Models\VehicleModel;
use App\Models\LogSheetMileModel;
use App\Models\VoucherPaymentModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\PaymentRequestModel;
use App\Models\DriverAdvanceRequestModel;
use App\Controllers\SmsController;
// use App\Controllers\WhatsAppController;
use Exception;

class DriverAllocationController extends BaseController
{

    // public function __construct()
    // {
    //     date_default_timezone_set('Asia/Colombo');
    // }

    public function tourTransport()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('tour_id');

        $data['tile_dashboard'] = true;

        $consigneeModel = new ConsigneeModel();
        $data['recent_tours'] = $tour_id !== null && $tour_id !== '' ? $consigneeModel->where('tour_id', $tour_id)->findAll() : [];
        // tf-pilot-r2: this page allocates ONE tour (?tour_id=). Opened without one — from the menu entry or a bookmark — it used
        // to fail with an error; it now goes to the existing "Tours pending driver allocation" list, where each tour links here.
        if (empty($data['recent_tours'])) {
            return redirect()->to(base_url('dashboard2/transport/driver_allow/pending_tours') . ($tour_id !== null && $tour_id !== '' ? '?missing=' . urlencode((string) $tour_id) : ''));
        }

        $supplierModel = new SupplierModel();
        $data['drivers'] = $supplierModel->where('sup_cat', 2)->where('is_avalible', 1)->findAll();

        return view('pages/dashboard2/transport/tour_transport', $data);
    }

    public function getDriverLog()
    {

        $driverAccNo = $this->request->getGet('driver_acc_no');

        $driverAllocationAuditLogModel = new DriverAllocationAuditLogModel();
        $logs = $driverAllocationAuditLogModel->where('driver', $driverAccNo)
            ->orderBy('changed_at', 'DESC')
            ->findAll();

        if (empty($logs)) {
            return json_encode(['status' => 404, 'message' => 'No logs found for this driver']);
        }
        return json_encode(['status' => 200, 'data' => $logs]);
    }

    public function driverAllocation()
    {
        $tour_id = $this->request->getPost('tour_id');
        $manual_id = $this->request->getPost('manual_id');
        $driver = $this->request->getPost('driver');
        if ($why = \App\Libraries\TourFlow\Blocks::workRefusalForSupplierAcc(\Config\Database::connect(), (string) $driver, 'driver allocation (legacy)', (string) session()->get('username'))) {   // tf-stage8
            return $this->response->setJSON(['status' => 428, 'message' => $why, 'blocked' => true]);
        }
        $driver_name = $this->request->getPost('driver_name');
        $username = session()->get('username');

        //get tour start date in consignee table
        $consigneeModel = new ConsigneeModel();
        $tour = $consigneeModel->where('tour_id', $tour_id)->first();
        if ($tour) {
            $tour_start_date = $tour['start_date'];
        } else {
            return json_encode(['status' => 404, 'message' => 'Tour not found']);
        }

        //get driver phone number from supplier table
        $supplierModel = new SupplierModel();
        $driverData = $supplierModel->where('Sup_Acc_No', $driver)->first();
        if ($driverData) {
            $driver_phone = $driverData['Mobile'];
            // Remove spaces, leading 0 or +94, and get last 9 digits
            $driver_phone = str_replace(' ', '', $driver_phone);
            $driver_phone = preg_replace('/^(0|\+94)/', '', $driver_phone);
            $driver_phone = substr($driver_phone, -9);
        } else {
            return json_encode(['status' => 404, 'message' => 'Driver not found']);
        }

        if (empty($tour_id) || empty($manual_id) || empty($driver) || empty($driver_name)) {
            return json_encode(['status' => 400, 'message' => 'All fields are required']);
        }

        $model = new DriverAllocationModel();

        $exists = $model->where([
            'tour_id' => $tour_id,
            'manual_id' => $manual_id,
            'driver' => $driver,
        ])->where('request_allocation !=', 1)->first();

        if ($exists) {
            return json_encode(['status' => 409, 'message' => 'Driver already allocated for this tour']);
        }

        // tf-rotation-r1b (26 Oct 2026): shared protection with the Transport Desk — the same tour whose driver is already engaged
        // there, or a driver already engaged there on an overlapping tour, is refused here too. Nothing else on this page changes.
        $tdGuard = \App\Libraries\TourFlow\Rotation::legacyGuard(\Config\Database::connect(), (string) $tour_id, (string) $manual_id, (string) $driver, $tour['start_date'] ?? null, $tour['end_date'] ?? null);
        if ($tdGuard !== null) {
            return json_encode(['status' => 409, 'message' => $tdGuard]);
        }

        // If a pending allocation request exists for this driver+tour, convert it into a real allocation
        $pendingRequest = $model->where([
            'tour_id' => $tour_id,
            'manual_id' => $manual_id,
            'driver' => $driver,
            'request_allocation' => 1
        ])->first();

        $data = [
            'tour_id' => $tour_id,
            'manual_id' => $manual_id,
            'driver' => $driver,
            'driver_name' => $driver_name,
            'allow_date' => date('Y-m-d H:i:s'),
            'allow_user' => $username,
            'request_allocation' => 0
        ];

        if ($pendingRequest) {
            $saved = $model->update($pendingRequest['id'], $data);
        } else {
            $saved = $model->insert($data);
        }

        if ($saved) {
            // Generate token and message for both SMS and WhatsApp
            $smsController = new SmsController();
            // $whatsAppController = new WhatsAppController();

            $token = $smsController->generateToken($tour_id, $driver);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

            $message = "Dear " . $driver_name . ",";
            $message .= "\n\nYou have been allocated to tour " . $manual_id . ".";
            $message .= "\n\nTour starts on: " . $tour_start_date;
            $message .= "\n\nView your itinerary at:";
            $message .= "\n" . base_url('driver/' . $token);

            //Send via SMS (existing)
            // $smsController->sendSms($driver_phone, $message, "Driver Allocation", $tour_id, $token, $expiresAt);

            // Send via WhatsApp (new)
            // $whatsAppController->sendWhatsApp($driver_phone, $message, "Driver Allocation", $tour_id, $token, $expiresAt);

            return json_encode(['status' => 200, 'message' => 'Driver allocated successfully']);
        } else {
            return json_encode(['status' => 500, 'message' => 'Failed to allocate driver']);
        }
    }

    public function removeDriverAllocation()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');
            $driver = $this->request->getPost('driver_id');
            $username = session()->get('username');

            if (empty($tour_id) || empty($driver)) {
                return json_encode(['status' => 400, 'message' => 'Tour ID and Driver are required']);
            }

            $driverAllocationModel = new DriverAllocationModel();
            $consigneeModel = new ConsigneeModel();
            $supplierModel = new SupplierModel();

            // First, check if driver exists in driver_allocation table (additional drivers)
            $existsInAllocation = $driverAllocationModel->where([
                'tour_id' => $tour_id,
                'driver' => $driver,
            ])->where('request_allocation !=', 1)->first();

            if ($existsInAllocation) {
                // Remove from driver_allocation table
                $deleteDriver = $driverAllocationModel->where([
                    'tour_id' => $tour_id,
                    'driver' => $driver,
                ])->where('request_allocation !=', 1)->delete();

                if ($deleteDriver) {
                    return json_encode(['status' => 200, 'message' => 'Additional driver removed successfully']);
                } else {
                    return json_encode(['status' => 500, 'message' => 'Failed to remove additional driver']);
                }
            }

            // If not found in driver_allocation, check if it's the primary transport agent
            $consignee = $consigneeModel->where('tour_id', $tour_id)->first();

            if ($consignee && $consignee['transport_agent'] === $driver) {
                // This is the primary transport agent - update to 'NA' value
                $updateResult = $consigneeModel->where('tour_id', $tour_id)
                    ->set(['transport_agent' => '6-9-11-10691']) // NA value
                    ->update();

                if ($updateResult) {
                    // Get driver details for SMS notification
                    $driverData = $supplierModel->where('Sup_Acc_No', $driver)->first();

                    if ($driverData) {
                        $driver_name = $driverData['Name'];
                        $driver_phone = $driverData['Mobile'];

                        // Format phone number (remove spaces, leading 0 or +94, get last 9 digits)
                        $driver_phone = str_replace(' ', '', $driver_phone);
                        $driver_phone = preg_replace('/^(0|\+94)/', '', $driver_phone);
                        $driver_phone = substr($driver_phone, -9);

                        // Get tour details
                        $manual_id = $consignee['manual_id'];

                        // Send notifications via SMS and WhatsApp
                        $smsController = new SmsController();
                        // $whatsAppController = new WhatsAppController();

                        $message = "Dear " . $driver_name . ",";
                        $message .= "\n\nYou have been removed from tour " . $manual_id . ".";
                        $message .= "\n\nThank you for your service.";

                        // Send via SMS
                        $smsController->sendSms($driver_phone, $message, "Driver Removal", $tour_id, null, null);

                        // Send via WhatsApp
                        // $whatsAppController->sendWhatsApp($driver_phone, $message, "Driver Removal", $tour_id, null, null);
                    }

                    return json_encode(['status' => 200, 'message' => 'Primary transport agent removed successfully']);
                } else {
                    return json_encode(['status' => 500, 'message' => 'Failed to remove primary transport agent']);
                }
            }

            // Driver not found in either location
            return json_encode(['status' => 404, 'message' => 'Driver allocation not found']);
        } catch (Exception $e) {
            log_message('error', 'Error in removeDriverAllocation: ' . $e->getMessage());
            return json_encode(['status' => 500, 'message' => 'An error occurred while removing driver allocation']);
        }
    }



    public function driverAllocationDetails()
    {
        $tour_id = $this->request->getGet('tour_id');
        // $manual_id = $this->request->getGet('manual_id');

        if (empty($tour_id)) {
            return json_encode(['status' => 400, 'message' => 'Tour ID and Manual ID are required']);
        }

        $model = new DriverAllocationModel();
        $consigneeModel = new ConsigneeModel();

        $tourExists = $consigneeModel->where('tour_id', $tour_id)->first();
        if (!$tourExists) {
            return json_encode(['status' => 404, 'message' => 'Tour not found']);
        }

        $transport_agent = $consigneeModel->select('transport_agent as driver,manual_id,supplier.Name as driver_name, tour_id')->join('supplier', 'supplier.Sup_Acc_No = consignee.transport_agent')->where('tour_id', $tour_id)->where('transport_agent <>', '6-9-11-10691')->findAll();


        $allocations = $model->where('tour_id', $tour_id)->where('request_allocation !=', 1)->findAll();

        $pendingRequests = $model->where([
            'tour_id' => $tour_id,
            'request_allocation' => 1
        ])->findAll();

        return json_encode(['status' => 200, 'data' => $allocations, 'transport_agent' => $transport_agent, 'pending_requests' => $pendingRequests]);
    }

    public function requestDriverAllocation()
    {
        $tour_id = $this->request->getPost('tour_id');
        $manual_id = $this->request->getPost('manual_id');
        $driver = $this->request->getPost('driver');
        if ($why = \App\Libraries\TourFlow\Blocks::workRefusalForSupplierAcc(\Config\Database::connect(), (string) $driver, 'driver allocation (legacy)', (string) session()->get('username'))) {   // tf-stage8
            return $this->response->setJSON(['status' => 428, 'message' => $why, 'blocked' => true]);
        }
        $driver_name = $this->request->getPost('driver_name');
        $username = session()->get('username');

        if (empty($tour_id) || empty($manual_id) || empty($driver) || empty($driver_name)) {
            return json_encode(['status' => 400, 'message' => 'All fields are required']);
        }

        $model = new DriverAllocationModel();

        $exists = $model->where([
            'tour_id' => $tour_id,
            'manual_id' => $manual_id,
            'driver' => $driver
        ])->first();

        if ($exists) {
            if ((int) $exists['request_allocation'] === 1) {
                return json_encode(['status' => 409, 'message' => 'Allocation request already pending for this driver']);
            }
            return json_encode(['status' => 409, 'message' => 'Driver already allocated for this tour']);
        }

        $data = [
            'tour_id' => $tour_id,
            'manual_id' => $manual_id,
            'driver' => $driver,
            'driver_name' => $driver_name,
            'request_allocation' => 1,
            'requested_user' => $username,
            'requested_date' => date('Y-m-d H:i:s')
        ];

        if ($model->insert($data)) {
            return json_encode(['status' => 200, 'message' => 'Allocation request submitted successfully']);
        } else {
            return json_encode(['status' => 500, 'message' => 'Failed to submit allocation request']);
        }
    }

    public function approveDriverAllocationRequest()
    {
        $id = $this->request->getPost('id');
        $username = session()->get('username');

        if (empty($id)) {
            return json_encode(['status' => 400, 'message' => 'Request ID is required']);
        }

        $model = new DriverAllocationModel();
        $request = $model->where('id', $id)->first();

        if (!$request) {
            return json_encode(['status' => 404, 'message' => 'Allocation request not found']);
        }

        if ((int) $request['request_allocation'] !== 1) {
            return json_encode(['status' => 400, 'message' => 'This request is not pending approval']);
        }

        // Another allocation for the same driver+tour may already exist (e.g. added directly
        // via the normal Add Driver flow after the request was submitted) — don't create a duplicate.
        $alreadyAllocated = $model->where([
            'tour_id' => $request['tour_id'],
            'driver' => $request['driver'],
        ])->where('id !=', $id)->where('request_allocation !=', 1)->first();

        if ($alreadyAllocated) {
            return json_encode(['status' => 409, 'message' => 'Driver is already allocated for this tour']);
        }

        $data = [
            'request_allocation' => 2,
            'approved_user' => $username,
            'approved_date' => date('Y-m-d H:i:s'),
            'allow_date' => date('Y-m-d H:i:s'),
            'allow_user' => $username
        ];

        if ($model->update($id, $data)) {
            return json_encode(['status' => 200, 'message' => 'Allocation request approved and driver allocated']);
        } else {
            return json_encode(['status' => 500, 'message' => 'Failed to approve allocation request']);
        }
    }

    public function getAvailableDrivers()
    {
        $tour_id = $this->request->getGet('tour_id');

        if (empty($tour_id)) {
            return json_encode(['status' => 400, 'message' => 'Tour ID is required']);
        }

        $supplierModel = new SupplierModel();
        $driverAllocationModel = new DriverAllocationModel();
        $consigneeModel = new ConsigneeModel();

        // Get all drivers (sup_cat = 2)
        $allDrivers = $supplierModel->where('sup_cat', 2)->where('is_avalible', 1)->findAll();

        // Get already assigned drivers from driver_allocation table
        $assignedDriversFromAllocation = $driverAllocationModel
            ->select('driver')
            ->where('tour_id', $tour_id)
            ->findAll();

        // Get transport agent from consignee table (excluding 'NA')
        $transportAgent = $consigneeModel
            ->select('transport_agent')
            ->where('tour_id', $tour_id)
            ->where('transport_agent <>', '6-9-11-10691')
            ->first();

        // Create array of assigned driver IDs
        $assignedDriverIds = [];
        foreach ($assignedDriversFromAllocation as $assigned) {
            $assignedDriverIds[] = $assigned['driver'];
        }

        // Add transport agent to assigned drivers list if exists
        if ($transportAgent && !empty($transportAgent['transport_agent'])) {
            $assignedDriverIds[] = $transportAgent['transport_agent'];
        }

        // Filter out assigned drivers and drivers with 0 tour count
        $availableDrivers = [];
        foreach ($allDrivers as $driver) {
            // Skip if driver is already assigned
            if (in_array($driver['Sup_Acc_No'], $assignedDriverIds)) {
                continue;
            }

            // Get tour count for this driver
            $tourCount = $consigneeModel
                ->where('transport_agent', $driver['Sup_Acc_No'])
                ->countAllResults();

            // Only include drivers with at least 1 tour (optional - remove this condition if you want all drivers)
            // if ($tourCount > 0) {
            $availableDrivers[] = [
                'Sup_Acc_No' => $driver['Sup_Acc_No'],
                'Name' => $driver['Name'],
                'Mobile' => $driver['Mobile'],
                'tour_count' => $tourCount
            ];
            // }
        }

        return json_encode(['status' => 200, 'data' => $availableDrivers]);
    }

    public function getAvailableTours()
    {
        $consigneeModel = new ConsigneeModel();
        $driverAllocationAuditLogModel = new DriverAllocationAuditLogModel();

        // Get tours without assigned drivers that are verified and not past
        $Tours = $consigneeModel
            ->select('consignee.tour_id, consignee.manual_id, consignee.consignee, consignee.agent_name, consignee.start_date, consignee.end_date, consignee.passenger_count')
            ->where('consignee.verify', 1)
            ->where('consignee.start_date >=', date('Y-m-d'))
            ->where('consignee.tour_cancel', 0)
            ->where('consignee.hotel_only', 0)
            // ->groupStart()
            // ->where('consignee.transport_agent IS NULL')
            // ->orWhere('consignee.transport_agent', '6-9-11-10691')
            // ->groupEnd()
            ->orderBy('consignee.start_date', 'ASC')
            ->findAll();

            // log_message('critical', 'Available Tours Query: ' . $consigneeModel->getLastQuery());

        $availableTours = [];
        foreach ($Tours as $tour) {
            $auditLog = $driverAllocationAuditLogModel
                ->where('tour_id', $tour['tour_id'])
                ->where('manual_id', $tour['manual_id'])
                ->first();

            // Only include tours that are NOT in audit log
            if (empty($auditLog)) {
                $availableTours[] = $tour;
            }
        }

        if (empty($availableTours)) {
            return json_encode(['status' => 404, 'message' => 'No available tours found']);
        }

        return json_encode(['status' => 200, 'data' => $availableTours]);
    }

    public function getAllocatedTours()
    {
        $consigneeModel = new ConsigneeModel();
        $driverAllocationModel = new DriverAllocationModel();

        // Get tours with primary driver (transport_agent != 'NA')
        $toursWithPrimaryDriver = $consigneeModel
            ->distinct()
            ->select('consignee.tour_id, consignee.manual_id, consignee.consignee,
                      consignee.agent_name, consignee.start_date, consignee.end_date,
                      consignee.passenger_count, consignee.transport_agent,
                      supplier.Name as driver_name')
            ->join('supplier', 'supplier.Sup_Acc_No = consignee.transport_agent', 'left')
            ->where('consignee.start_date >=', date('Y-m-d'))
            ->where('consignee.tour_cancel', 0)
            ->where('consignee.hotel_only', 0)
            ->where('consignee.transport_agent <>', '6-9-11-10691')
            ->orderBy('consignee.start_date', 'ASC')
            ->findAll();

        // Get tours with additional drivers from driver_allocation table
        $toursWithAdditionalDrivers = $consigneeModel
            ->distinct()
            ->select('consignee.tour_id, consignee.manual_id, consignee.consignee,
                      consignee.agent_name, consignee.start_date, consignee.end_date,
                      consignee.passenger_count, consignee.transport_agent,
                      GROUP_CONCAT(DISTINCT driver_allocation.driver_name SEPARATOR ", ") as driver_name')
            ->join('driver_allocation', 'consignee.tour_id = driver_allocation.tour_id', 'inner')
            ->where('driver_allocation.request_allocation !=', 1)
            ->where('consignee.start_date >=', date('Y-m-d'))
            ->where('consignee.tour_cancel', 0)
            ->where('consignee.hotel_only', 0)
            ->groupBy('consignee.tour_id')
            ->orderBy('consignee.start_date', 'ASC')
            ->findAll();

        // Merge and remove duplicates based on tour_id
        $allAllocatedTours = [];
        $tourIds = [];

        foreach ($toursWithPrimaryDriver as $tour) {
            if (!in_array($tour['tour_id'], $tourIds)) {
                $allAllocatedTours[] = $tour;
                $tourIds[] = $tour['tour_id'];
            }
        }

        foreach ($toursWithAdditionalDrivers as $tour) {
            if (!in_array($tour['tour_id'], $tourIds)) {
                $allAllocatedTours[] = $tour;
                $tourIds[] = $tour['tour_id'];
            }
        }

        // Sort by start_date
        usort($allAllocatedTours, function($a, $b) {
            return strtotime($a['start_date']) - strtotime($b['start_date']);
        });

        if (empty($allAllocatedTours)) {
            return json_encode(['status' => 404, 'message' => 'No allocated tours found']);
        }

        return json_encode(['status' => 200, 'data' => $allAllocatedTours]);
    }

    public function pendingTours()
    {
        $data = [];

        // Load menu data
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();
        $data['tile_dashboard'] = true;

        // Get pending tours (reuse logic from getAvailableTours)
        $consigneeModel = new ConsigneeModel();
        $driverAllocationAuditLogModel = new DriverAllocationAuditLogModel();

        $tours = $consigneeModel
            ->select('consignee.tour_id, consignee.manual_id, consignee.consignee, consignee.agent_name, consignee.start_date, consignee.end_date, consignee.passenger_count')
            ->where('consignee.verify', 1)
            ->where('consignee.start_date >=', date('Y-m-d'))
            ->where('consignee.tour_cancel', 0)
            ->where('consignee.hotel_only', 0)
            ->orderBy('consignee.start_date', 'ASC')
            ->findAll();

        // Filter out tours in audit log
        $pendingTours = [];
        foreach ($tours as $tour) {
            $auditLog = $driverAllocationAuditLogModel
                ->where('tour_id', $tour['tour_id'])
                ->where('manual_id', $tour['manual_id'])
                ->first();

            if (empty($auditLog)) {
                $pendingTours[] = $tour;
            }
        }

        $data['pending_tours'] = $pendingTours;

        return view('pages/dashboard2/transport/pending_tours', $data);
    }

    public function getDriverTours()
    {
        $driverId = $this->request->getGet('driver_id');
        $startMonth = $this->request->getGet('start_month');
        $startYear = $this->request->getGet('start_year');
        $endMonth = $this->request->getGet('end_month');
        $endYear = $this->request->getGet('end_year');

        $dateConditions = [];


        if ($startMonth && $startYear) {
            $startDate = date('Y-m-01', strtotime("$startYear-$startMonth-01"));
            $dateConditions[] = "start_date >= '$startDate'";
        }

        if ($endMonth && $endYear) {
            $lastDay = date('t', strtotime("$endYear-$endMonth-01"));
            $endDate = "$endYear-$endMonth-$lastDay";
            $dateConditions[] = "end_date <= '$endDate'";
        }

        $consigneeModel = new ConsigneeModel();
        $builder = $consigneeModel->select('consignee.tour_id,consignee.consignee,consignee.start_date,consignee.end_date,consignee.vehicle_name,consignee.manual_id,driver_allocation.driver')
            ->join('driver_allocation', 'consignee.tour_id = driver_allocation.tour_id', 'left')
            ->where('consignee.transport_agent', $driverId);
        foreach ($dateConditions as $condition) {
            $builder = $builder->where($condition, null, false);
        }

        $tours = $builder->orderBy('consignee.start_date', 'ASC')->findAll();

        // log_message('critical', $consigneeModel->getLastQuery());

        if (empty($tours)) {
            return json_encode([
                'status' => 404,
                'message' => 'No tours found for this driver in the specified date range'
            ]);
        }

        return json_encode([
            'status' => 200,
            'tours' => $tours
        ]);
    }

    public function getDriverPaymentStatus()
    {
        $driverId = $this->request->getGet('driver_id');

        if (empty($driverId)) {
            return json_encode(['status' => 400, 'message' => 'Driver ID is required']);
        }

        // Get tour IDs from driver_allocation table
        $driverAllocationModel = new DriverAllocationModel();
        $allocationTours = $driverAllocationModel->select('tour_id')
            ->where('driver', $driverId)
            ->where('request_allocation !=', 1)
            ->findAll();

        // Get tour IDs from consignee table where transport_agent = driverId
        $consigneeModel = new ConsigneeModel();
        $consigneeTours = $consigneeModel->select('tour_id')
            ->where('transport_agent', $driverId)
            ->findAll();

        // Get tour IDs from log_sheet_mile table
        $logSheetMileModel = new LogSheetMileModel();
        $logSheetTours = $logSheetMileModel->select('tour_id')
            ->where('transport_agent', $driverId)
            ->findAll();

        // Combine all tour IDs and remove duplicates
        $allTourIds = [];

        foreach ($allocationTours as $tour) {
            $allTourIds[] = $tour['tour_id'];
        }

        foreach ($consigneeTours as $tour) {
            $allTourIds[] = $tour['tour_id'];
        }

        foreach ($logSheetTours as $tour) {
            $allTourIds[] = $tour['tour_id'];
        }

        // Remove duplicates
        $uniqueTourIds = array_unique($allTourIds);

        if (empty($uniqueTourIds)) {
            return json_encode(['status' => 404, 'message' => 'No tours found for this driver']);
        }

        // Get detailed tour information — only tours whose end_date is before today
        $today = date('Y-m-d');
        $tourDetails = $consigneeModel->select('tour_id, manual_id, end_date')
            ->whereIn('tour_id', $uniqueTourIds)
            ->where('end_date <', $today)
            ->where('tour_cancel', 0)
            ->findAll();

        // Restrict uniqueTourIds to only past-ended tours
        $uniqueTourIds = array_column($tourDetails, 'tour_id');

        // Initialize overall payment statistics
        $overallStats = [
            'total_tours' => count($tourDetails),
            'tours_with_vouchers' => 0,
            'tours_without_vouchers' => 0,
            'tours_all_accepted' => 0,
            'tours_has_pending' => 0,
            'tours_has_rejected' => 0,
            'total_voucher_amount' => 0,
            'total_accepted_amount' => 0,
            'total_pending_amount' => 0
        ];

        // Add voucher check process for each tour
        // $voucherModel = new VoucherPaymentModel();
        // $toursWithVoucherStatus = [];

        // foreach ($tourDetails as $tour) {
        //     $tourId = $tour['tour_id'];

            // // Get vouchers for this tour
            // $vouchers = $voucherModel->select('Vnum, Aflag, rej, Amount, S_name, des, C_Date, is_confirm, ref_po')
            //     ->where('ref_po', $tourId)
            //     ->where('Acc_No', $driverId)
            //     ->findAll();

            // // Analyze voucher status for this tour
            // $tourVoucherSummary = [
            //     'pending_vouchers' => 0
            // ];

            // foreach ($vouchers as $voucher) {

            //     if ($voucher['Aflag'] == 0) {
            //         $tourVoucherSummary['pending_vouchers']++;
            //     }
            // }

            
        // }

        $accountRunningModel = new AccountRunningModel();

        // Build per-tour breakdown
        $tourBreakdown = [];
        $totalPendingCount = 0;

        // Build a map of tour_id -> manual_id for quick lookup
        $manualIdMap = [];
        foreach ($tourDetails as $td) {
            $manualIdMap[$td['tour_id']] = $td['manual_id'] ?? $td['tour_id'];
        }

        foreach ($uniqueTourIds as $tourId) {
            $count = $driverAllocationModel
                ->where('tour_id', $tourId)
                ->where('driver', $driverId)
                ->where('payment_settlement', 0)
                ->where('request_allocation !=', 1)
                ->countAllResults();

            if ($count > 0) {
                $tourBreakdown[] = [
                    'tour_id'              => $tourId,
                    'manual_id'            => $manualIdMap[$tourId] ?? $tourId,
                    'pending_payments_count' => $count
                ];
            }

            $totalPendingCount += $count;
        }

        return json_encode([
            'status' => 200,
            'driver_id' => $driverId,
            'pending_payments_count' => $totalPendingCount,
            'tours' => $tourBreakdown
        ]);
    } // End of getDriverPaymentStatus function

    public function getDriverAvailabilityChecker()
    {
        $tour_id = $this->request->getGet('tour_id');

        if (empty($tour_id)) {
            return json_encode(['status' => 400, 'message' => 'Tour ID is required']);
        }

        $consigneeModel = new ConsigneeModel();
        $tour = $consigneeModel->select('tour_id, start_date, end_date')->where('tour_id', $tour_id)->first();

        if (!$tour) {
            return json_encode(['status' => 404, 'message' => 'Tour not found']);
        }

        $tourStart = $tour['start_date'];
        $tourEnd = $tour['end_date'];

        $supplierModel = new SupplierModel();
        $vehicleModel = new VehicleModel();
        $driverAllocationModel = new DriverAllocationModel();

        $drivers = $supplierModel->where('sup_cat', 2)->where('is_avalible', 1)->orderBy('Name', 'ASC')->findAll();

        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $today = date('Y-m-d');

        $result = [];

        foreach ($drivers as $driver) {
            $driverAcc = $driver['Sup_Acc_No'];

            // Vehicle Type - active vehicle registered to this driver
            $vehicle = $vehicleModel
                ->where('owner', $driverAcc)
                ->where('action', 1)
                ->first();

            // Availability - any other tour (excluding this one) overlapping the tour date range
            $conflict = $consigneeModel
                ->where('transport_agent', $driverAcc)
                ->where('tour_id !=', $tour_id)
                ->where('tour_cancel', 0)
                ->where('start_date <=', $tourEnd)
                ->where('end_date >=', $tourStart)
                ->first();

            // Tours completed this month (end_date within current calendar month)
            $toursThisMonth = $consigneeModel
                ->where('transport_agent', $driverAcc)
                ->where('tour_cancel', 0)
                ->where('end_date >=', $monthStart)
                ->where('end_date <=', $monthEnd)
                ->countAllResults();

            // Unpaid settlements - allocations with payment_settlement = 0 for tours that have already ended
            $pastTourIds = $consigneeModel
                ->select('tour_id')
                ->where('transport_agent', $driverAcc)
                ->where('end_date <', $today)
                ->where('tour_cancel', 0)
                ->findColumn('tour_id');

            $unpaidSettlements = 0;
            if (!empty($pastTourIds)) {
                $unpaidSettlements = $driverAllocationModel
                    ->where('driver', $driverAcc)
                    ->whereIn('tour_id', $pastTourIds)
                    ->where('payment_settlement', 0)
                    ->where('request_allocation !=', 1)
                    ->countAllResults();
            }

            $result[] = [
                'driver_id' => $driverAcc,
                'driver_name' => $driver['Name'],
                'language' => $driver['language'] ?? null,
                'mobile' => $driver['Mobile'],
                'vehicle_type' => $vehicle['type'] ?? null,
                'unpaid_settlements' => $unpaidSettlements,
                'tours_this_month' => $toursThisMonth,
                'is_available' => $conflict ? false : true,
            ];
        }

        usort($result, static function ($a, $b) {
            return $b['tours_this_month'] <=> $a['tours_this_month'];
        });

        return json_encode(['status' => 200, 'tour_id' => $tour_id, 'data' => $result]);
    }

    public function advanceRequest()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('tour_id');

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('tour_id');

        $data['tile_dashboard'] = true;

        $SupplierModel = new SupplierModel();
        $data['transport_agent'] = $SupplierModel->where('sup_cat', 2)->where('is_avalible', 1)->findAll();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $data['agents'] = [];

        $tourConsigneeModel = new ConsigneeModel();

        $tour_condition = $tourConsigneeModel->select('hotel_only')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $driverallocationModel = new DriverAllocationModel();
        $data['drivers'] = $driverallocationModel->where('tour_id', $tour_id)->where('request_allocation !=', 1)->findAll();

        $PaymentRequestModel = new PaymentRequestModel();
        $data['advance_payment_requests'] = $PaymentRequestModel->where('tour_id', $tour_id)->where('hotel_acc', 'TRANSPORT')->findAll();

        $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();
        $data['tour_id'] = $tour_id;
        // $data['type'] = $type;
        $data['tour_main_details'] = $tour_main_details;

        return view('pages/dashboard2/transport/driver_advance_request', $data);
    }

    //advancerequesthistory function
    public function advanceRequestHistory()
    {
        $agent = $this->request->getGet('agent');
        $tour_id = $this->request->getGet('tour_id');

        $DriveradvanceRequestModel = new DriverAdvanceRequestModel();
        $advance_payment_requests = $DriveradvanceRequestModel->where('tour_id', $tour_id)->findAll();

        if (empty($advance_payment_requests)) {
            return json_encode(['status' => 404, 'message' => 'No advance payment requests found for this agent and tour']);
        }

        $voucherPaymentModel = new VoucherPaymentModel();
        foreach ($advance_payment_requests as &$req) {
            $voucher = $voucherPaymentModel
                ->where('ref_po', $req['tour_id'])
                ->where('Acc_No', $req['driver'])
                ->where('advances_pay', 1)
                ->orderBy('ID', 'DESC')
                ->first();

            $req['voucher_no'] = $voucher['Vnum'] ?? null;
        }
        unset($req);

        return json_encode(['status' => 200, 'data' => $advance_payment_requests]);
    }

    public function advanceRequestSubmit()
    {
        $tour_id = $this->request->getPost('tour_id');
        $agent = $this->request->getPost('agent');
        $agent_name = $this->request->getPost('agent_name');
        $payment = $this->request->getPost('payment');
        $manu_id = $this->request->getPost('manu_id');
        $username = session()->get('username');


        if (empty($tour_id) || empty($agent) || empty($payment) || empty($manu_id)) {
            return json_encode(['status' => 400, 'message' => 'Tour ID, Agent, Payment Amount, and Consignee ID are required']);
        }

        $DriveradvanceRequestModel = new DriverAdvanceRequestModel();

        $data = [
            'tour_id' => $tour_id,
            'manual_id' => $manu_id,
            'driver' => $agent,
            'driver_name' => $agent_name,
            'amount' => $payment,
            'username' => $username,
            'date' => date('Y-m-d H:i:s'),
            'flag' => 0
        ];

        if ($DriveradvanceRequestModel->insert($data)) {

            return json_encode(['status' => 200, 'message' => 'Advance payment request submitted successfully']);
        } else {
            return json_encode(['status' => 500, 'message' => 'Failed to submit advance payment request']);
        }
    }

    public function updateAdvanceRequest()
    {
        try {
            $request_id = $this->request->getPost('request_id');
            $new_amount = $this->request->getPost('new_amount');
            $tour_id = $this->request->getPost('tour_id');

            if (!is_numeric($new_amount)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Amount must be a number']);
            }

            if ($new_amount <= 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Amount must be greater than zero']);
            }

            $DriveradvanceRequestModel = new DriverAdvanceRequestModel();
            $advanceRequest = $DriveradvanceRequestModel->where('id', $request_id)->first();

            if (!$advanceRequest) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Advance request not found']);
            }

            if ($advanceRequest['flag'] != 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot update. Request status is not PENDING']);
            }

            $ConsigneeModel = new ConsigneeModel();
            $consigneeData = $ConsigneeModel->select('tour_cancel')->where('tour_id', $tour_id)->first();

            if ($consigneeData && $consigneeData['tour_cancel'] == 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot update request. The tour has been cancelled.']);
            }

            $updateData = [
                'amount' => $new_amount
            ];

            if ($DriveradvanceRequestModel->where('id', $request_id)->set($updateData)->update()) {
                return $this->response->setJSON(['status' => 200, 'message' => 'Advance request updated successfully!']);
            } else {
                return $this->response->setJSON(['status' => 500, 'message' => 'Failed to update advance request']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function deleteAdvanceRequest()
    {
        try {
            $request_id = $this->request->getPost('request_id');
            $tour_id = $this->request->getPost('tour_id');

            $DriveradvanceRequestModel = new DriverAdvanceRequestModel();
            $advanceRequest = $DriveradvanceRequestModel->where('id', $request_id)->first();

            if (!$advanceRequest) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Advance request not found']);
            }

            if ($advanceRequest['flag'] != 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot delete. Request status is not PENDING']);
            }

            $ConsigneeModel = new ConsigneeModel();
            $consigneeData = $ConsigneeModel->select('tour_cancel')->where('tour_id', $tour_id)->first();

            if ($consigneeData && $consigneeData['tour_cancel'] == 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot delete request. The tour has been cancelled.']);
            }

            if ($DriveradvanceRequestModel->where('id', $request_id)->delete()) {
                return $this->response->setJSON(['status' => 200, 'message' => 'Advance request deleted successfully!']);
            } else {
                return $this->response->setJSON(['status' => 500, 'message' => 'Failed to delete advance request']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function allocatedToursReport()
    {
        $data = [];

        // Load menu data
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();
        $data['tile_dashboard'] = true;

        // Get date parameters from request
        $startDateFrom = $this->request->getGet('start_date_from');
        $startDateTo = $this->request->getGet('start_date_to');

        // Reuse getAllocatedTours logic
        $consigneeModel = new ConsigneeModel();
        $driverAllocationModel = new DriverAllocationModel();

        // Build query for tours with primary driver
        $query1 = $consigneeModel
            ->distinct()
            ->select('consignee.tour_id, consignee.manual_id, consignee.consignee,
                      consignee.agent_name, consignee.start_date, consignee.end_date,
                      consignee.passenger_count, consignee.transport_agent,
                      supplier.Name as driver_name')
            ->join('supplier', 'supplier.Sup_Acc_No = consignee.transport_agent', 'left');

        // Apply date filters if provided, otherwise default to today onwards
        if ($startDateFrom) {
            $query1->where('consignee.start_date >=', $startDateFrom);
        } else {
            $query1->where('consignee.start_date >=', date('Y-m-d'));
        }

        if ($startDateTo) {
            $query1->where('consignee.start_date <=', $startDateTo);
        }

        $toursWithPrimaryDriver = $query1
            ->where('consignee.tour_cancel', 0)
            ->where('consignee.hotel_only', 0)
            ->where('consignee.transport_agent <>', '6-9-11-10691')
            ->orderBy('consignee.start_date', 'ASC')
            ->findAll();

        // Build query for tours with additional drivers
        $query2 = $consigneeModel
            ->distinct()
            ->select('consignee.tour_id, consignee.manual_id, consignee.consignee,
                      consignee.agent_name, consignee.start_date, consignee.end_date,
                      consignee.passenger_count, consignee.transport_agent,
                      GROUP_CONCAT(DISTINCT driver_allocation.driver_name SEPARATOR ", ") as driver_name')
            ->join('driver_allocation', 'consignee.tour_id = driver_allocation.tour_id', 'inner')
            ->where('driver_allocation.request_allocation !=', 1);

        // Apply date filters if provided, otherwise default to today onwards
        if ($startDateFrom) {
            $query2->where('consignee.start_date >=', $startDateFrom);
        } else {
            $query2->where('consignee.start_date >=', date('Y-m-d'));
        }

        if ($startDateTo) {
            $query2->where('consignee.start_date <=', $startDateTo);
        }

        $toursWithAdditionalDrivers = $query2
            ->where('consignee.tour_cancel', 0)
            ->where('consignee.hotel_only', 0)
            ->groupBy('consignee.tour_id')
            ->orderBy('consignee.start_date', 'ASC')
            ->findAll();

        // Merge and remove duplicates
        $allAllocatedTours = [];
        $tourIds = [];

        foreach ($toursWithPrimaryDriver as $tour) {
            if (!in_array($tour['tour_id'], $tourIds)) {
                $allAllocatedTours[] = $tour;
                $tourIds[] = $tour['tour_id'];
            }
        }

        foreach ($toursWithAdditionalDrivers as $tour) {
            if (!in_array($tour['tour_id'], $tourIds)) {
                $allAllocatedTours[] = $tour;
                $tourIds[] = $tour['tour_id'];
            }
        }

        // Sort by start_date
        usort($allAllocatedTours, function($a, $b) {
            return strtotime($a['start_date']) - strtotime($b['start_date']);
        });

        $data['allocated_tours'] = $allAllocatedTours;

        return view('pages/dashboard2/transport/allocated_tours', $data);
    }

    // route : base_url/dashboard2/transport/driver_allow/suggested_advance?tour_id=X
    // Suggested driver advance = 30% of (quoted transport cost + driver-borne activity
    // costs). An activity is excluded from "driver-borne" (i.e. the driver did not front
    // the cash) when ANY of the following is true:
    //   - entrance_fee.direct_payment=1 (this place is always paid by the office directly,
    //     e.g. Lunch, Jeep Safaris, Train Tickets)
    //   - entrance_fee.is_prepaid=1 (place-level default: normally prepaid by the office)
    //   - tour_activity.is_prepaid=1 (per-tour override: the office prepaid this specific
    //     occurrence, regardless of the place's defaults)
    public function suggestedAdvance()
    {
        $tour_id = $this->request->getGet('tour_id');

        if (empty($tour_id)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Tour ID is required']);
        }

        $consigneeModel = new ConsigneeModel();
        $tour = $consigneeModel->select('tour_id, manual_id, total_transport_cost')->where('tour_id', $tour_id)->first();

        if (!$tour) {
            return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
        }

        $db = \Config\Database::connect();
        $activityRow = $db->table('tour_activity')
            ->select('
                SUM(CASE WHEN entrance_fee.direct_payment = 1 OR entrance_fee.is_prepaid = 1 OR tour_activity.is_prepaid = 1 THEN 0 ELSE tour_activity.lkr_fee END) AS driver_borne_lkr,
                SUM(CASE WHEN entrance_fee.direct_payment = 1 OR entrance_fee.is_prepaid = 1 OR tour_activity.is_prepaid = 1 THEN tour_activity.lkr_fee ELSE 0 END) AS prepaid_lkr_excluded
            ')
            ->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'left')
            ->where('tour_activity.tour_id', $tour_id)
            ->get()->getRowArray();

        // Per-activity breakdown so the UI can show exactly which lines make up the total
        $activityLines = $db->table('tour_activity')
            ->select('
                tour_activity.id,
                tour_activity.tour_date,
                tour_activity.lkr_fee,
                tour_activity.is_prepaid AS tour_is_prepaid,
                COALESCE(NULLIF(entrance_fee.name, \'\'), tour_activity.supplier_name, tour_activity.name) AS place_name,
                COALESCE(entrance_fee.direct_payment, 0) AS direct_payment,
                COALESCE(entrance_fee.is_prepaid, 0) AS place_is_prepaid
            ')
            ->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'left')
            ->where('tour_activity.tour_id', $tour_id)
            ->orderBy('tour_activity.tour_date', 'ASC')
            ->get()->getResultArray();

        foreach ($activityLines as &$line) {
            $line['lkr_fee'] = (float) $line['lkr_fee'];
            $line['excluded'] = ($line['direct_payment'] == 1 || $line['place_is_prepaid'] == 1 || $line['tour_is_prepaid'] == 1);

            if ($line['tour_is_prepaid'] == 1) {
                $line['exclude_reason'] = 'Prepaid (this tour)';
            } elseif ($line['direct_payment'] == 1) {
                $line['exclude_reason'] = 'Direct Payment (place default)';
            } elseif ($line['place_is_prepaid'] == 1) {
                $line['exclude_reason'] = 'Prepaid (place default)';
            } else {
                $line['exclude_reason'] = null;
            }
        }
        unset($line);

        $transportCost = (float) ($tour['total_transport_cost'] ?? 0);
        $driverBorneActivityCost = (float) ($activityRow['driver_borne_lkr'] ?? 0);
        $prepaidExcluded = (float) ($activityRow['prepaid_lkr_excluded'] ?? 0);

        // Flat buffer for Parking/Toll + Airport Expense — these are near-universal driver
        // out-of-pocket costs (avg LKR 2,269 + LKR 1,174 per tour respectively, per live
        // driver_expenses data) but are only ever recorded AFTER the tour as a reimbursement
        // claim, so they can't be read from tour_activity at advance-request time like other
        // activity costs.
        $miscExpenseBuffer = 3500.00;

        // Base advance is 30% of transport cost alone. Activity cost + the misc buffer are
        // only added on top when they're large enough to matter — if they're small relative
        // to the transport advance, that advance already covers them and a top-up would just
        // overpay the driver. Threshold: extras must exceed half of the transport advance.
        $transportAdvance = round($transportCost * 0.30, 2);
        $extras = $driverBorneActivityCost + $miscExpenseBuffer;
        $extrasIncluded = $extras > ($transportAdvance * 0.5);

        $suggestedAdvance = $extrasIncluded
            ? round($transportAdvance + $extras, 2)
            : $transportAdvance;

        return $this->response->setJSON([
            'status' => 200,
            'tour_id' => $tour['tour_id'],
            'manual_id' => $tour['manual_id'],
            'transport_cost' => $transportCost,
            'transport_advance' => $transportAdvance,
            'driver_borne_activity_cost' => $driverBorneActivityCost,
            'prepaid_activity_cost_excluded' => $prepaidExcluded,
            'misc_expense_buffer' => $miscExpenseBuffer,
            'extras_included' => $extrasIncluded,
            'suggested_advance' => $suggestedAdvance,
            'activity_lines' => $activityLines,
        ]);
    }
}
