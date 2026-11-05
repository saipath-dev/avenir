<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\TourQuotationsModel;
use App\Models\SupplierModel;
use App\Models\ConsigneeModel;
use App\Models\AccountRunningModel;
use App\Models\InvoiceNumberModel;
use App\Models\HotelRateModel;
use App\Models\ChangesLogModel;

class BookingConfirmController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/tour/tour_details
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $SupplierModel = new SupplierModel();
        $data['transport_agent'] = $SupplierModel->where('sup_cat', 2)->findAll();

        $faxName = $this->request->getGet('consignee');
        $email = $this->request->getGet('email');
        $drange = $this->request->getGet('drange');
        

        $TourQuotationsModel = new TourQuotationsModel();
        $tourDetailsQuery = $TourQuotationsModel->db->table('tour_quotations')
            ->select('tour_quotations.quotation_id AS tour_id, Max(consignee.end_date) AS end_date, Min(tour_quotations.tour_date) AS start_date, Max(tour_quotations.confirm) As Confirm, Max(consignee.consignee) AS consignee, consignee.mobile as mobile, consignee.email AS email')
            ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
            ->where('tour_quotations.confirm', 1)
            ->where('tour_quotations.finalize', 1)
            ->groupBy('tour_quotations.quotation_id');

        if (empty($faxName) && empty($email) && empty($drange)) {
            $tourDetails = $tourDetailsQuery->get()->getResult();
        } else {
            $queries = [];

            if(!empty($faxName) && !empty($drange)){
                $date_parts = explode(' to ', $drange);
                if (count($date_parts) == 2) {
                    $start_date = $date_parts[0];
                    $end_date = $date_parts[1];

                    $comQuery1  = clone $tourDetailsQuery;
                    $comQuery1 = $comQuery1->like('consignee.consignee', $faxName)
                                        ->groupStart()
                                        ->where('consignee.start_date >=', $start_date)
                                        ->where('consignee.start_date <=', $end_date)
                                        ->orWhere('consignee.end_date >=', $start_date)
                                        ->where('consignee.end_date <=', $end_date)
                                        ->groupEnd();
                    $queries[] = $comQuery1;
                }
            }else if(!empty($email) && !empty($drange)){
                $date_parts = explode(' to ', $drange);
                if (count($date_parts) == 2) {
                    $start_date = $date_parts[0];
                    $end_date = $date_parts[1];

                    $comQuery2  = clone $tourDetailsQuery;
                    $comQuery2 = $comQuery2->like('consignee.email', $email)
                                        ->groupStart()
                                        ->where('consignee.start_date >=', $start_date)
                                        ->where('consignee.start_date <=', $end_date)
                                        ->orWhere('consignee.end_date >=', $start_date)
                                        ->where('consignee.end_date <=', $end_date)
                                        ->groupEnd();
                    $queries[] = $comQuery2;
                }
            }else if(!empty($faxName)) {
                $faxQuery = clone $tourDetailsQuery;
                $faxQuery = $faxQuery->like('consignee.consignee', $faxName);
                $queries[] = $faxQuery;
            }else if (!empty($email)) {
                $emailQuery = clone $tourDetailsQuery;
                $emailQuery = $emailQuery->like('consignee.email', $email);
                $queries[] = $emailQuery;
            }else if (!empty($drange)) {
                $date_parts = explode(' to ', $drange);
                if (count($date_parts) == 2) {
                    $start_date = $date_parts[0];
                    $end_date = $date_parts[1];

                    $dateRangeQuery = clone $tourDetailsQuery;
                    $dateRangeQuery = $dateRangeQuery->groupStart()
                        ->where('consignee.start_date >=', $start_date)
                        ->where('consignee.start_date <=', $end_date)
                        ->orWhere('consignee.end_date >=', $start_date)
                        ->where('consignee.end_date <=', $end_date)
                        ->groupEnd();
                    $queries[] = $dateRangeQuery;
                } else {
                    log_message('critical', 'Invalid date range format.');
                }
            }

            

            if (!empty($queries)) {
                $tourDetailsQuery = array_shift($queries);
                foreach ($queries as $query) {
                    $tourDetailsQuery = $tourDetailsQuery->union($query, false);
                }
                $tourDetails = $tourDetailsQuery->get()->getResult();
            } else {
                $tourDetails = [];
            }
        }

        $data['tour_details'] = $tourDetails;
        
        // log_message('critical', $TourQuotationsModel->getLastQuery());
        return view('pages/tour/booking_confirm', $data);
    }




        // route : base_url/tour/show_details
    public function findAllDetails() {

        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $supplierModel = new SupplierModel();
        $data['hotel'] = $supplierModel->where('sup_cat', 1)->orderBy('Name', 'ASC')->findAll();

        $data['hotel_location'] = $supplierModel->select('Address')->where('sup_cat', 1)->groupBy('Address')->orderBy('Address', 'ASC')->findAll();

        $tour_id = $this->request->getGet('id');
        $start_date = $this->request->getGet('start');
        $end_date = $this->request->getGet('end');

        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        $TourQuotationsModel = new TourQuotationsModel();

        $tour_details = $TourQuotationsModel->select('tour_quotations.*, supplier.Name as hotel_name, room_type.rtype,transfer_milage.transfer_code as transfer_name')->where('tour_quotations.quotation_id', $tour_id)
        ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc','left')
        ->join('room_type', 'room_type.id = tour_quotations.room_type_id','left')
        ->join('transfer_milage', 'transfer_milage.id = tour_quotations.transfer_code','left')
        ->orderBy('tour_quotations.tour_date')
        ->findAll();

        
        $data['tour_details'] = $tour_details;

        return view('pages/tour/booking_details', $data);
    }

    // route : base_url/tour/booking_confirm/tour_transport
    public function tour_transport() {

        try{
            $tour = $this->request->getPost('tour_id');
            $ref = $this->request->getPost('ref_no');
            $agent = $this->request->getPost('agent');
            $pay = $this->request->getPost('payment');

            
            if (!is_numeric($pay)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            $session = session();
            $username = $session->get('username');

            $ConsigneeModel = new ConsigneeModel();

            $data = [
                'transport_agent' => $agent,
                'reference_no' => $ref,
                'transport_advance' => $pay
            ];

            if($ConsigneeModel->set($data)->where('tour_id', $tour)->update()){

                if($pay > 0) {
                    $invoiceNoModel = new InvoiceNumberModel();
                    $acc_id = $invoiceNoModel->incrementAccId();

                    $accountRunningModel = new AccountRunningModel();

                    // DR Recrord
                    $accountRunningModel->insert([
                        'ID' => $acc_id,
                        'L_Code' => '1-2-108-10382',
                        'Date' => date('Y-m-d'),
                        'Discription' => 'Transport Advance Payment',
                        'Dr' => $pay,
                        'Cr' => 0.00,
                        'Cashier' => $username,
                        'Dt' => date('Y-m-d H:i:s')
                    ]);

                    // CR Record
                    $accountRunningModel->insert([
                        'ID' => $acc_id,
                        'L_Code' => $agent,
                        'Date' => date('Y-m-d'),
                        'Discription' => 'Transport Advance Payment',
                        'Dr' => 0.00,
                        'Cr' => $pay,
                        'Cashier' => $username,
                        'Dt' => date('Y-m-d H:i:s')
                    ]);
                }
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Added!"]);
            }else{
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        }catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
        
    }

    public function getAllDetails($tour, $date){

        try {
            $TourQuotationsModel = new TourQuotationsModel();
            $quotationDetails = $TourQuotationsModel->select('tour_quotations.*, room_type.rtype as room')->join('room_type', 'room_type.id = tour_quotations.room_type', 'left')->where('tour_quotations.quotation_id', $tour)->where('tour_quotations.tour_date', $date)->first();

            // $quotationDetails = $TourQuotationsModel->select('tour_quotations.*, room_type.rtype as room')->join('room_type', 'room_type.id = tour_quotations.room_type')->where('tour_quotations.quotation_id', $tour)->where('<tour_quotations class=""></tour_quotations>tour_date', $date)->first();
            // Return the data
            return $this->response->setJSON(['status' => 200, 'data' => $quotationDetails]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tour/booking_confirm/hotelAdvanced
    public function hotelAdvanced(){
        try{

            $request = $this->request->getPost();
           
            $tour =strip_tags($request['tour_id']);
            $old_hotel = strip_tags($request['old_hotel']);
            $old_hotel_code = strip_tags($request['old_hotel_code']);
            $tour_date = strip_tags($request['tour_date']);
            $select_hotel = strip_tags($request['select_hotel']);
            $select_hotel_id = strip_tags($request['slct_hotel_id']);
            $slct_hotel_name = strip_tags($request['slct_hotel_name']);
            // $select_hotel_code = strip_tags($request['select_hotel_code']);
            
            $room_type = strip_tags($request['room_type']);
            $room_type_id = strip_tags($request['room_type_id']);
            
            $meal_code = strip_tags($request['meal_code']);
            $sgl = isset($request['sgl']) ? strip_tags($request['sgl']) : null;
            $dbl = isset($request['dbl']) ? strip_tags($request['dbl']) : null;
            $tpl = isset($request['tpl']) ? strip_tags($request['tpl']) : null;

            $payment = isset($request['advance_payment']) ? strip_tags($request['advance_payment']) : null;
            $ref = isset($request['hotel_ref']) ? strip_tags($request['hotel_ref']) : null;

            if(empty($tour) || empty($tour_date) || empty($old_hotel)) {
                return json_encode(['status' => 400, 'message' => 'Required field are empty']);
            }
            
            if (!is_numeric($payment)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            $hotel = $select_hotel_id;

            if ($select_hotel != 0) {
                $hotel = $select_hotel;
            }

            if (!empty($room_type)) {
                $data['room_type'] = $room_type;
            } else {
                $data['room_type'] = $room_type_id;
            }

            $supplierModel = new SupplierModel();
            $hotel_name = $supplierModel->select('Name')->where('Sup_Acc_No', $hotel)->find();

            $TourQuotationsModel = new TourQuotationsModel();
            $existData = $TourQuotationsModel->select('id, quotation_id, quotation_day, hotel_code, meal_code, hotel_acc, hotel_name, room_type, room_type_id, tour_date, start_date, end_date, sgl, dbl, tpl')->where('quotation_id', $tour)->where('tour_date', $tour_date)->first();

            $session = session();
            $username = $session->get('username');

            $existData['new_hotel_acc'] = $hotel;
            $existData['created_time'] = date('Y-m-d H:i:s');
            $existData['created_user'] = $username;

            $ChangesLogModel = new ChangesLogModel();
            $ChangesLogModel->insert($existData);

            $data = [
                'meal_code' => $meal_code,
                'hotel_acc' => $hotel,
                // 'hotel_name' => isset($hotel_name[0]['Name']) ? $hotel_name[0]['Name'] : '',
                'hotel_name' => $hotel_name,
                'room_type' => $data['room_type'],
                'room_type_id' => $room_type_id,
                'sgl' => $sgl,
                'dbl' => $dbl,
                'tpl' => $tpl,
                'hotel_advance' => $payment,
                'advance_ref' => $ref,
            ];
            $session = session();
            $username = $session->get('username');

            $TourQuotationsModel = new TourQuotationsModel();
            if(($old_hotel != $select_hotel)){
                $hotel = $select_hotel;

                $supplierModel = new SupplierModel();
                $hotel_name = $supplierModel->select('Name')->where('Sup_Acc_No', $hotel)->find();

                $existData = $TourQuotationsModel->select('id, quotation_id, quotation_day, hotel_code, meal_code, hotel_acc, hotel_name, room_type, room_type_id, tour_date, start_date, end_date, sgl, dbl, tpl')->where('quotation_id' ,$tour)->where('tour_date',$tour_date)->first();

                $existData['new_hotel_acc'] = $hotel;
                $existData['created_time'] = date('Y-m-d H:i:s');
                $existData['created_user'] = $username;

                $ChangesLogModel = new ChangesLogModel();
                $ChangesLogModel->insert($existData);

                $data = [
                    // 'hotel_code' => $select_hotel,
                    'meal_code' => $meal_code,
                    'hotel_acc' => $hotel,
                    'hotel_name' => isset($hotel_name[0]['Name']) ? $hotel_name[0]['Name'] : '',
                    'room_type' => $data['room_type'],
                    'room_type_id' => $room_type_id,
                    'sgl' => $sgl,
                    'dbl' => $dbl,
                    'tpl' => $tpl,
                    'hotel_advance' => $payment,
                    'advance_ref' => $ref,
                ];
                
            }else{
                $hotel = $old_hotel;
                $hotel_code = $old_hotel_code;

                $data = [
                    'meal_code' => $meal_code,
                    'sgl' => $sgl,
                    'dbl' => $dbl,
                    'tpl' => $tpl,
                    'hotel_advance' => $payment,
                    'advance_ref' => $ref,
                ];
            }

                if($TourQuotationsModel->set($data)->where('tour_date', $tour_date)->where('quotation_id', $tour)->update()){
                    if($payment > 0) {
                        $invoiceNoModel = new InvoiceNumberModel();
                        $acc_id = $invoiceNoModel->incrementAccId();

                        $accountRunningModel = new AccountRunningModel();

                        // DR Recrord
                        $accountRunningModel->insert([
                            'ID' => $acc_id,
                            'L_Code' => '1-2-108-10382',
                            'Date' => date('Y-m-d'),
                            'Discription' => 'Hotel Advance Payment',
                            'Dr' => $payment,
                            'Cr' => 0.00,
                            'Cashier' => $username,
                            'Dt' => date('Y-m-d H:i:s')
                        ]);

                        // CR Record
                        $accountRunningModel->insert([
                            'ID' => $acc_id,
                            'L_Code' => $hotel,
                            'Date' => date('Y-m-d'),
                            'Discription' => 'Hotel Advance Payment',
                            'Dr' => 0.00,
                            'Cr' => $payment,
                            'Cashier' => $username,
                            'Dt' => date('Y-m-d H:i:s')
                        ]);
                    }

                    return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated!"]);
                }else{
                    return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
                }
            

        }catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

}