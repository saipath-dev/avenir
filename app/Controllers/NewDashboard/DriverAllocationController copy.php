<?php

namespace App\Controllers\NewDashboard;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\ConsigneeModel;
use App\Models\DriverAllocationAuditLogModel;
use App\Models\SupplierModel;
use App\Models\DriverAllocationModel;
use App\Models\LogSheetMileModel;
use App\Models\VoucherPaymentModel;
use App\Models\AccountPostingModel;
use App\Models\PaymentRequestModel;
use App\Models\DriverAdvanceRequestModel;
use App\Controllers\SmsController;
use App\Controllers\WhatsAppController;
use Exception;

class DriverAllocationController extends BaseController
{

    public function tourTransport()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('tour_id');

        $data['tile_dashboard'] = true;

        $consigneeModel = new ConsigneeModel();
        $data['recent_tours'] = $consigneeModel
            ->where('tour_id', $tour_id)
            ->findAll();

        $supplierModel = new SupplierModel();
        $data['drivers'] = $supplierModel->where('sup_cat', 2)->findAll();

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
        $driver_name = $this->request->getPost('driver_name');

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
            'driver' => $driver
        ])->first();

        if ($exists) {
            return json_encode(['status' => 409, 'message' => 'Driver already allocated for this tour']);
        }

        $data = [
            'tour_id' => $tour_id,
            'manual_id' => $manual_id,
            'driver' => $driver,
            'driver_name' => $driver_name
        ];

        if ($model->insert($data)) {

            // Generate token and message for both SMS and WhatsApp
            $smsController = new SmsController();
            $whatsAppController = new WhatsAppController();

            $token = $smsController->generateToken($tour_id, $driver);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

            $message = "Dear " .$driver_name . ",";
            $message .= "\n\nYou have been allocated to tour " . $manual_id . ".";
            $message .= "\n\nTour starts on: " . $tour_start_date;
            $message .= "\n\nView your itinerary at:";
            $message .= "\n" . base_url('driver/' . $token);

            // Send via SMS (existing)
            $smsController->sendSms($driver_phone, $message, "Driver Allocation", $tour_id, $token, $expiresAt);

            // Send via WhatsApp (new)
            $whatsAppController->sendWhatsApp($driver_phone, $message, "Driver Allocation", $tour_id, $token, $expiresAt);

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
            
            if (empty($tour_id) || empty($driver)) {
                return json_encode(['status' => 400, 'message' => 'Tour ID and Driver are required']);
            }

            $driverAllocationModel = new DriverAllocationModel();
            $consigneeModel = new ConsigneeModel();
            $supplierModel = new SupplierModel();

            // First, check if driver exists in driver_allocation table (additional drivers)
            $existsInAllocation = $driverAllocationModel->where([
                'tour_id' => $tour_id,
                'driver' => $driver
            ])->delete();

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
                    $whatsAppController = new WhatsAppController();

                    $message = "Dear " . $driver_name . ",";
                    $message .= "\n\nYou have been removed from tour " . $manual_id . ".";
                    $message .= "\n\nThank you for your service.";

                    // Send via SMS
                    $smsController->sendSms($driver_phone, $message, "Driver Removal", $tour_id, null, null);

                    // Send via WhatsApp
                    $whatsAppController->sendWhatsApp($driver_phone, $message, "Driver Removal", $tour_id, null, null);
            ])->first();

            if ($existsInAllocation) {
                // Remove from driver_allocation table
                $deleteDriver = $driverAllocationModel->where([
                    'tour_id' => $tour_id,
                    'driver' => $driver
                ])->delete();

                if ($deleteDriver) {
                    return json_encode(['status' => 200, 'message' => 'Additional driver removed successfully']);
                } else {
                    return json_encode(['status' => 500, 'message' => 'Failed to remove additional driver']);
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


        $allocations = $model->where([
            'tour_id' => $tour_id
        ])->findAll();

        return json_encode(['status' => 200, 'data' => $allocations, 'transport_agent' => $transport_agent]);
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
        $allDrivers = $supplierModel->where('sup_cat', 2)->findAll();

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

        // Get tours without assigned drivers that are verified and not past
        $availableTours = $consigneeModel
            ->select('consignee.tour_id, consignee.manual_id, consignee.consignee, consignee.agent_name, consignee.start_date, consignee.end_date, consignee.passenger_count')
            ->where('consignee.verify', 1)
            ->where('consignee.end_date >=', date('Y-m-d'))
            ->groupStart()
                ->where('consignee.transport_agent IS NULL')
                ->orWhere('consignee.transport_agent', '6-9-11-10691')
            ->groupEnd()
            ->orderBy('consignee.start_date', 'ASC')
            ->findAll();

        if (empty($availableTours)) {
            return json_encode(['status' => 404, 'message' => 'No available tours found']);
        }

        return json_encode(['status' => 200, 'data' => $availableTours]);
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

        // Get detailed tour information
        $tourDetails = $consigneeModel->select('tour_id, manual_id')
            ->whereIn('tour_id', $uniqueTourIds)
            ->findAll();

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
        $voucherModel = new VoucherPaymentModel();
        $toursWithVoucherStatus = [];

        foreach ($tourDetails as $tour) {
            $tourId = $tour['tour_id'];

            // Get vouchers for this tour
            $vouchers = $voucherModel->select('Vnum, Aflag, rej, Amount, S_name, des, C_Date, is_confirm, ref_po')
                ->where('ref_po', $tourId)
                ->where('Acc_No', $driverId)
                ->findAll();

            // Analyze voucher status for this tour
            $tourVoucherSummary = [
                'pending_vouchers' => 0
            ];

            foreach ($vouchers as $voucher) {

                if ($voucher['Aflag'] == 0) {
                    $tourVoucherSummary['pending_vouchers']++;
                }
            }
        }

        return json_encode([
            'status' => 200,
            'driver_id' => $driverId,
            'pending_payments_count' => count($tourVoucherSummary)
        ]);
    } // End of getDriverPaymentStatus function

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
        $data['transport_agent'] = $SupplierModel->where('sup_cat', 2)->findAll();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $data['agents'] = [];

        $tourConsigneeModel = new ConsigneeModel();

        $tour_condition = $tourConsigneeModel->select('hotel_only')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $driverallocationModel = new DriverAllocationModel();
        $data['drivers'] = $driverallocationModel->where('tour_id', $tour_id)->findAll();

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
}
