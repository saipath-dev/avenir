<?php

namespace App\Controllers\NewDashboard;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\ConsigneeModel;
use App\Models\SupplierModel;
use App\Models\DriverAllocationModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\PaymentRequestModel;
use App\Models\VoucherPaymentModel;
use App\Models\VoucherInvoiceModel;
use App\Models\InvoiceNumberModel;
use App\Models\TourQuotationsModel;
use App\Models\TourSubLocationsModel;
use App\Models\CustomerModel;
use App\Models\TourShopAllocationModel;
use App\Controllers\SmsController;


class ShopAllocationController extends BaseController
{

    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('tour_id');

        $data['tile_dashboard'] = true;

        $tourConsigneeModel = new ConsigneeModel();

        $tour_condition = $tourConsigneeModel->select('hotel_only')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();
        $data['tour_id'] = $tour_id;
        // $data['type'] = $type;
        $data['tour_main_details'] = $tour_main_details;

        $tourQuotationsModel = new TourQuotationsModel();
        $tour_details = $tourQuotationsModel->select('tour_quotations.*, room_type.rtype')->join('room_type', 'tour_quotations.room_type = room_type.id', 'left')->where('quotation_id', $tour_id)->orderBy('quotation_day', 'ASC')->findAll();
        $data['tour_details'] = $tour_details;

        $tourSubLocationsModel = new TourSubLocationsModel();
        $sub_locations = $tourSubLocationsModel->where('quotation_id', $tour_id)->findAll();
        $data['sub_locations'] = $sub_locations;

        return view('pages/dashboard2/transport/shop_allocation', $data);
    }

    // Get all registered shops
    public function get_shops()
    {
        try {
            $customerModel = new CustomerModel();
            $shops = $customerModel->where('flag', '1')->findAll();

            return $this->response->setJSON(['status' => 200, 'shops' => $shops]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // Save selected shops for a tour date
    public function save_shops()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');
            $manual_id = $this->request->getPost('manual_id');
            $tour_date = $this->request->getPost('tour_date');
            $shop_ids_json = $this->request->getPost('shop_ids');

            if (empty($tour_id) || empty($tour_date) || empty($shop_ids_json)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Missing required fields']);
            }

            // Decode the JSON string to get shop data array
            $shop_ids = json_decode($shop_ids_json, true);


            if (empty($shop_ids) || !is_array($shop_ids)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid shop data']);
            }

            // Extract shop IDs for querying
            $shopIdList = array_column($shop_ids, 'id');

            // Get shop details
            $customerModel = new CustomerModel();
            $shops = $customerModel->whereIn('ID', $shopIdList)->findAll();

            $shopallocationModel = new TourShopAllocationModel();

            // Get tour details for SMS (including transport_agent for fallback)
            $consigneeModel = new ConsigneeModel();
            $tourDetails = $consigneeModel->select('tour_id, guide, guide_name, transport_agent, passenger_count')
                ->where('tour_id', $tour_id)
                ->first();

            if (!$tourDetails) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
            }

            // Extract guide info
            $guideName = $tourDetails['guide_name'] ?? 'TBA';
            $guidePhone = '';
            $contactName = '';
            $contactPhone = '';

            if (!empty($tourDetails['guide'])) {
                $supplierModel = new SupplierModel();
                $guideData = $supplierModel->where('Sup_Acc_No', $tourDetails['guide'])->first();
                if ($guideData) {
                    $guidePhone = $this->formatPhoneNumber($guideData['Mobile']);
                }
            }

            // Check if guide is available (not empty, not TBA, and has valid phone)
            $isGuideAvailable = !empty($tourDetails['guide']) &&
                                !empty($guideName) &&
                                $guideName !== 'TBA' &&
                                !empty($guidePhone);

            if ($isGuideAvailable) {
                // Use guide details
                $contactName = $guideName;
                $contactPhone = $guidePhone;
            } else {
                // Guide not available - use primary driver instead
                if (!empty($tourDetails['transport_agent']) && $tourDetails['transport_agent'] !== '6-9-11-10691') {
                    $supplierModel = new SupplierModel();
                    $driverData = $supplierModel->where('Sup_Acc_No', $tourDetails['transport_agent'])->first();
                    if ($driverData) {
                        $contactName = $driverData['Name'];
                        $contactPhone = $this->formatPhoneNumber($driverData['Mobile']);
                    }
                }
            }

            $paxCount = $tourDetails['passenger_count'] ?? 'N/A';
            $smsController = new SmsController();
            $username = session()->get('username');

            // Insert new allocations and send SMS
            $insertCount = 0;
            $smsSuccessCount = 0;
            $smsFailureCount = 0;

            foreach ($shop_ids as $shop_id) {
                $data = [
                    'tour_id' => $tour_id,
                    'manual_id' => $manual_id,
                    'tour_date' => $tour_date,
                    'shop_id' => $shop_id['id'],
                    'shop_name' => $shop_id['name'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'allocated_by' => $username
                ];

                if ($shopallocationModel->insert($data)) {
                    $insertCount++;

                    $shopPhone = $this->getShopPhone($shop_id['id']);

                    if ($shopPhone) {

                        // Build SMS message
                        $message = $this->buildShopSmsMessage(
                            $shop_id['name'],
                            $manual_id,
                            $tour_date,
                            $paxCount,
                            $contactName,
                            $contactPhone
                        );

                        try {
                            $smsResult = $smsController->sendSms(
                                $shopPhone,
                                $message,
                                'Shop Allocation',
                                $tour_id,
                                null,  // No token
                                null   // No expiry
                            );


                            if ($smsResult) {
                                $smsSuccessCount++;
                            } else {
                                $smsFailureCount++;
                            }
                        } catch (\Exception $e) {
                            $smsFailureCount++;
                        }
                    } else {
                        log_message('critical', 'SHOP ALLOCATION DEBUG - No valid phone number for shop ID: ' . $shop_id['id']);
                    }
                } else {
                    log_message('critical', 'SHOP ALLOCATION DEBUG - Failed to insert allocation for shop ID: ' . $shop_id['id']);
                }
            }

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Shops saved successfully',
                'shops' => $shops,
                'sms_sent' => $smsSuccessCount,
                'sms_failed' => $smsFailureCount
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get shop phone with priority: rep_mobile → Mobile → TP
     */
    private function getShopPhone($shopId)
    {
        $customerModel = new CustomerModel();
        $shop = $customerModel->where('ID', $shopId)->first();

        if (!$shop) {
            return null;
        }

        // Priority 1: rep_mobile
        if (!empty($shop['per_mobile'])) {
            $phone = $this->formatPhoneNumber($shop['per_mobile']);
            if ($phone) {
                return $phone;
            }
        }

        // Priority 2: Mobile
        if (!empty($shop['Mobile'])) {
            $phone = $this->formatPhoneNumber($shop['Mobile']);
            if ($phone) {
                return $phone;
            }
        }

        // Priority 3: TP
        if (!empty($shop['TP'])) {
            $phone = $this->formatPhoneNumber($shop['TP']);
            if ($phone) {
                return $phone;
            }
        }

        return null;
    }

    /**
     * Format phone to 9 digits (pattern from DriverAllocationController lines 88-91)
     */
    private function formatPhoneNumber($phone)
    {
        // log_message('critical', 'SHOP ALLOCATION DEBUG - formatPhoneNumber input: ' . $phone);

        if (empty($phone)) {
            // log_message('critical', 'SHOP ALLOCATION DEBUG - formatPhoneNumber: empty input');
            return null;
        }

        $originalPhone = $phone;

        // Remove spaces
        $phone = str_replace(' ', '', $phone);

        // Remove leading 0 or +94
        $phone = preg_replace('/^(0|\+94)/', '', $phone);

        // Extract last 9 digits
        $phone = substr($phone, -9);

        // log_message('critical', 'SHOP ALLOCATION DEBUG - formatPhoneNumber: original=' . $originalPhone . ', processed=' . $phone . ', length=' . strlen($phone) . ', is_digit=' . (ctype_digit($phone) ? 'yes' : 'no'));

        // Validate
        if (strlen($phone) === 9 && ctype_digit($phone)) {
            // log_message('critical', 'SHOP ALLOCATION DEBUG - formatPhoneNumber: Valid phone returned: ' . $phone);
            return $phone;
        }

        // log_message('critical', 'SHOP ALLOCATION DEBUG - formatPhoneNumber: Invalid phone, returning null');
        return null;
    }

    /**
     * Build SMS message for shop allocation
     */
    private function buildShopSmsMessage($shopName, $manualId, $tourDate, $paxCount, $contactName, $contactPhone)
    {
        $message = "Dear " . $shopName . ",";
        $message .= "\n\nTour " . $manualId . " scheduled for visit.";
        $message .= "\n\nDate: " . date('d M Y', strtotime($tourDate));

        // Add contact info if available (Guide or Driver)
        if (!empty($contactName) && $contactName !== 'TBA') {
            $message .= "\nContact: " . $contactName;

            if (!empty($contactPhone)) {
                $message .= " (0" . $contactPhone . ")";
            }
        }

        return $message;
    }

    // Get allocated shops for a tour
    public function get_allocated_shops()
    {
        try {
            $tour_id = $this->request->getGet('tour_id');

            if (empty($tour_id)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour ID is required']);
            }
            $shopallocationModel = new TourShopAllocationModel();
            $allocations = $shopallocationModel->where('tour_id', $tour_id)->findAll();

            // Group allocations by tour_date
            $grouped = [];
            foreach ($allocations as $allocation) {
                $date = $allocation['tour_date'];
                if (!isset($grouped[$date])) {
                    $grouped[$date] = [];
                }
                $grouped[$date][] = $allocation;
            }

            return $this->response->setJSON(['status' => 200, 'data' => $grouped]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
