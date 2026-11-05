<?php

namespace App\Controllers\NewDashboard\hotel;

use App\Controllers\BaseController;
use App\Controllers\Mail\InboxController;
use App\Controllers\Mail\SendController;
use App\Libraries\MenuLoader;
use App\Models\ConsigneeModel;
use App\Models\TourQuotationsModel;
use App\Models\StaffMemberModel;
use App\Models\UserModel;
use App\Models\PassengerDetailsModel;
use App\Models\EmailModel;
use Exception;
use App\Models\SupplierModel;
use App\Models\DriverAllocationModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\PaymentRequestModel;
use App\Models\VoucherPaymentModel;
use App\Models\VoucherInvoiceModel;
use App\Models\InvoiceNumberModel;
use App\Models\TourSubLocationsModel;
use App\Models\CustomerModel;
use App\Models\TourShopAllocationModel;


class HotelConfirmationController extends BaseController
{

    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $data['tile_dashboard'] = true;

        $tour_id = $this->request->getGet('tour_id');
        $filter_hotel_id = $this->request->getGet('hotel_id');
        $data['filter_hotel_id'] = $filter_hotel_id;
        $data['isActiveHotel'] = true;
        $user_emp_id = session()->get('emp_id');
        $staffMemberModel = new StaffMemberModel();
        $userModel = new UserModel();

        $staff = $staffMemberModel->where('emp_num', $user_emp_id)->first();
        $data['user_fullname'] = $staff['fullname'];
        $data['user_telephone'] = $staff['Tp'];

        $user = $userModel->where('emp_id', $user_emp_id)->first();
        $data['user_email'] = $user['email'];


        $tourConsigneeModel = new ConsigneeModel();

        $tour_condition = $tourConsigneeModel->select('hotel_only')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();
        $data['tour_id'] = $tour_id;
        // $data['type'] = $type;
        $data['tour_main_details'] = $tour_main_details;

        $TourQuotationsModel = new TourQuotationsModel();
        $tourConsigneeModel = new ConsigneeModel();

        $tour_condition = $tourConsigneeModel->select('transport_only, finalize_txt')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $tour_saved = $TourQuotationsModel->select('MAX(saved) AS save_flag')->where('quotation_id', $tour_id)->groupBy('quotation_id')->findAll();
        $data['tour_saved'] = $tour_saved;

        $tour_details2 = $TourQuotationsModel->query("
    SELECT 
        id,
        quotation_id,
        hotel_acc,
        hotel_name,
        hotel_code,
        MIN(tour_date) as start_date,
        MAX(tour_date) as end_date,
        COUNT(*) as nights,
        GROUP_CONCAT(tour_date ORDER BY tour_date) as all_dates,
        SUM(sglcount) as sglcount,
        SUM(dblcount) as dblcount, 
        SUM(tplcount) as tplcount,
        AVG(sgl) as sgl,
        AVG(dbl) as dbl,
        AVG(tpl) as tpl,
        SUM(total_hotel_doller) as total_cost,
        from_location,
        to_location,
        meal_code,
        booking_confirmed_flag,
        hotel_payment_flag,
        rtype,
        transfer_name
    FROM (
        SELECT 
            tq.*,
            s.Name as hotel_names,
            rt.rtype,
            tm.transfer_code as transfer_name,
            ROW_NUMBER() OVER (PARTITION BY tq.quotation_id ORDER BY tq.tour_date) - 
            ROW_NUMBER() OVER (PARTITION BY tq.quotation_id, tq.hotel_acc ORDER BY tq.tour_date) as grp
        FROM tour_quotations tq
        JOIN supplier s ON s.Sup_Acc_No = tq.hotel_acc
        LEFT JOIN room_type rt ON rt.id = tq.room_type
        LEFT JOIN transfer_milage tm ON tm.id = tq.transfer_code
        WHERE tq.quotation_id = ?
    ) grouped_data
    GROUP BY quotation_id, hotel_acc, grp
    ORDER BY MIN(tour_date)", [$tour_id])->getResultArray();


        $tour_details = $TourQuotationsModel->query("
    SELECT 
        MIN(id) as id,
        quotation_id,
        hotel_acc,
        hotel_code,
        hotel_name,
        MIN(tour_date) as start_date,
        MAX(tour_date) as end_date,
        COUNT(*) as nights,
        GROUP_CONCAT(tour_date ORDER BY tour_date) as date_range,
        from_location,
        to_location,
        SUM(sglcount) as sglcount,
        SUM(dblcount) as dblcount,
        SUM(tplcount) as tplcount,
        AVG(sgl) as sgl,
        AVG(dbl) as dbl,
        AVG(tpl) as tpl,
        meal_code,
        booking_confirmed_flag,
        hotel_payment_flag,
        rtype,
        transfer_name,
        total_hotel_doller as total_hotel_doller,
        pay_doller_count as pay_doller_count,
        quotation_day
    FROM (
        SELECT 
            tq.*,
            s.Name as hotel_names,
            rt.rtype,
            tm.transfer_code as transfer_name,
            ROW_NUMBER() OVER (PARTITION BY tq.quotation_id ORDER BY tq.tour_date) - 
            ROW_NUMBER() OVER (PARTITION BY tq.quotation_id, tq.hotel_acc ORDER BY tq.tour_date) as consecutive_group
        FROM tour_quotations tq
        JOIN supplier s ON s.Sup_Acc_No = tq.hotel_acc
        LEFT JOIN room_type rt ON rt.id = tq.room_type
        LEFT JOIN transfer_milage tm ON tm.id = tq.transfer_code
        WHERE tq.quotation_id = ?
    ) grouped_data
    GROUP BY quotation_id, hotel_acc, consecutive_group
    ORDER BY MIN(tour_date)
", [$tour_id])->getResultArray();

        $data['tour_details'] =  $tour_details;
        $data['tour_details2'] =  $tour_details2;

        $passengerDetailsModel = new PassengerDetailsModel();
        $passenger_details = $passengerDetailsModel->where('tour_id', $tour_id)->findAll();
        $data['passengers'] = $passenger_details;

        $supplierModel = new SupplierModel();
        $data['hotel'] = $supplierModel->where('sup_cat', 1)->orderBy('Name', 'ASC')->findAll();
        $data['hotel_location'] = $supplierModel->select('Address')->where('sup_cat', 1)->groupBy('Address')->orderBy('Address', 'ASC')->findAll();

        return view('pages/dashboard2/hotel/hotel_confirmation', $data);
    }

    public function getAllDetails($tour, $date, $hotel)
    {
        try {
            $date = str_replace('.', ',', $date);
            // Now split the dates
            $dates = explode(',', $date);

            $hotel_name = '';
            $hotel_acc = '';
            $room = '';
            $room_type = '';
            $meal_code = '';
            $sgl = 0;
            $dbl = 0;
            $tpl = 0;
            $r_sgl = '';
            $r_dbl = '';
            $r_tpl = '';
            $sglfoc = 0;
            $dblfoc = 0;
            $tplfoc = 0;
            $foc_count = 0;

            $TourQuotationsModel = new TourQuotationsModel();

            foreach ($dates as $single_date) {
                $single_date = trim($single_date);
                if ($single_date === '') continue;
                $quotationDetails = $TourQuotationsModel->select('tour_quotations.*, room_type.rtype as room, supplier.Name as hotel_name, consignee.sgl as r_sgl, consignee.dbl as r_dbl, consignee.tpl as r_tpl, consignee.start_date, consignee.end_date, consignee.finalize_txt, payment_request.requestAmount')
                    ->join('room_type', 'room_type.id = tour_quotations.room_type', 'left')
                    ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc', 'left')
                    ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'left')
                    ->join('payment_request', 'payment_request.tour_id = tour_quotations.quotation_id', 'left')
                    ->where('tour_quotations.quotation_id', $tour)
                    ->where('tour_quotations.tour_date', $single_date)
                    ->where('payment_request.hotel_acc', $hotel)
                    ->where('payment_request.payment_request', 0)
                    // ->where('tour_quotations.quotation_day', $datecount)
                    ->orderBy('tour_quotations.quotation_day')
                    ->first();

                if ($quotationDetails) {
                    $hotel_name = $quotationDetails['hotel_name'];
                    $hotel_acc = $quotationDetails['hotel_acc'];
                    $room = $quotationDetails['room'];
                    $room_type = $quotationDetails['room_type'];
                    $meal_code = $quotationDetails['meal_code'];
                    $sgl += $quotationDetails['sgl'] ?? 0;
                    $dbl += $quotationDetails['dbl'] ?? 0;
                    $tpl += $quotationDetails['tpl'] ?? 0;
                    $r_sgl = $quotationDetails['r_sgl'];
                    $r_dbl = $quotationDetails['r_dbl'];
                    $r_tpl = $quotationDetails['r_tpl'];
                    // $finalize_txt = $quotationDetails['finalize_txt'];
                    $pay_request_amount = $quotationDetails['requestAmount'];

                    // Accumulate FOC values for averaging
                    $sglfoc += $quotationDetails['sglfoc'] ?? 0;
                    $dblfoc += $quotationDetails['dblfoc'] ?? 0;
                    $tplfoc += $quotationDetails['tplfoc'] ?? 0;
                    $foc_count++;
                }
            }

            // Calculate average FOC if multiple dates
            if ($foc_count > 0) {
                $sglfoc = $sglfoc / $foc_count;
                $dblfoc = $dblfoc / $foc_count;
                $tplfoc = $tplfoc / $foc_count;
            }

            $data = [
                'quotationDetails' => [
                    'hotel_name' => $hotel_name,
                    'hotel_acc' => $hotel_acc,
                    'room' => $room,
                    'room_type' => $room_type,
                    'meal_code' => $meal_code,
                    'sgl' => $sgl,
                    'dbl' => $dbl,
                    'tpl' => $tpl,
                    'r_sgl' => $r_sgl,
                    'r_dbl' => $r_dbl,
                    'r_tpl' => $r_tpl,
                    'sglfoc' => $sglfoc,
                    'dblfoc' => $dblfoc,
                    'tplfoc' => $tplfoc,
                    'pay_request_amount' => $pay_request_amount
                    // 'finalize_txt' => $finalize_txt
                ],
            ];

            // Return the data
            return $this->response->setJSON(['status' => 200, 'data' => $data]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function confirm_reservation()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $tour_date = $this->request->getPost('tour_date');
            $date_number = $this->request->getPost('date_number');

            $confirmation_email = $this->request->getFile('attachment');
            $confirmation_reference = $this->request->getPost('reference');

            $hotel = $this->request->getPost('old_hotel');
            $payment = $this->request->getPost('confirm_lkr');
            $doller_rate = $this->request->getPost('confirm_p_rate');
            $doller_count = $this->request->getPost('confirm_doller');
            $extra = $this->request->getPost('extra');

            $sglfoc = $this->request->getPost('sglfoc') ?? 0;
            $dblfoc = $this->request->getPost('dblfoc') ?? 0;
            $tplfoc = $this->request->getPost('tplfoc') ?? 0;

            $dates = $this->request->getPost('dates');
            $datesArray = explode(',', strval($dates));

            $consigneeModal = new ConsigneeModel();
            $consigneeData = $consigneeModal->select('tour_cancel')->where('tour_id', $tour)->first();
            if ($consigneeData['tour_cancel'] == 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'This tour has been cancelled. You cannot confirm the reservation.']);
            }

            // $username = session()->get('username');

            // confirmation_email and confirmation_reference both empty return error. at least one should be filled
            if ((empty($confirmation_email) || !$confirmation_email->isValid()) && empty($confirmation_reference)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Please upload the confirmation email or enter the reference number']);
            }

            // if confirmation_email is not empty, check if it is a valid file and upload it to WRITEPATH
            if (!empty($confirmation_email)) {
                if ($confirmation_email->isValid() && !$confirmation_email->hasMoved()) {
                    $newName = $confirmation_email->getRandomName();
                    $confirmation_email->move(WRITEPATH . 'uploads/confirmation_emails', $newName);
                    $confirmation_email = $newName;
                }
            }

            $TourQuotationsModel = new TourQuotationsModel();

            if (empty($tour) || empty($tour_date)) {
                return json_encode(['status' => 400, 'message' => 'Required field are empty']);
            }

            $data = [
                'hotel_advance' => $payment,
                'booking_confirmed_flag' => 1,
                'confirmation_email' => $confirmation_email,
                'confirmation_reference' => $confirmation_reference,
                'total_hotel_doller' => $doller_count,
                'extra_hotel_fee' => $extra,
                'sglfoc' => $sglfoc,
                'dblfoc' => $dblfoc,
                'tplfoc' => $tplfoc,
                'hotel_dollar_rate' => $doller_rate,
            ];

            foreach ($datesArray as $date) {
                $date = date('Y-m-d', strtotime(trim($date)));
                $TourQuotationsModel->set($data)
                    ->where('tour_date', $date)
                    ->where('quotation_id', $tour)
                    ->where('hotel_acc', $hotel)
                    // ->where('quotation_day', $date_number)
                    ->update();
            }

            $paymentRequestModel = new PaymentRequestModel();
            $affectedRows = $paymentRequestModel->where('tour_id', $tour)->where('hotel_acc', $hotel)
                ->set([
                    'payment_request' => 1
                ])
                ->update();

            $session = session();
            $username = $session->get('username');

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $accountRunningModel = new AccountRunningModel();

            // Creditor Invoice Record
            $accountRunningModel->insertBatch([
                [
                    'ID' => $acc_id,
                    'L_Code' => '4-8-106-10386', // Hotel Charges
                    'Date' => $date,
                    'Discription' => 'Hotel Reservation ' . $tour . ' / ' . $date,
                    'Dr' => $payment,
                    'Cr' => 0.00,
                    'Cashier' => $username,
                    'index_no' => $tour,
                    'job' => '4-8-106-10386',
                    'Invoice_Num' => $tour,
                    'advances_pay' => 22,
                    'Dt' => date('Y-m-d H:i:s'),
                    'dollarrate' => $doller_rate,
                    'dollar' => $doller_count,
                    'P_Cap' => $date_number
                ],
                [
                    'ID' => $acc_id,
                    'L_Code' => $hotel,
                    'Date' => $date,
                    'Discription' => 'Hotel Reservation ' . $tour . ' / ' . $date,
                    'Dr' => 0.00,
                    'Cr' => $payment,
                    'Cashier' => $username,
                    'index_no' => $tour,
                    'job' => '4-8-106-10386',
                    'Invoice_Num' => $tour,
                    'advances_pay' => 22,
                    'Dt' => date('Y-m-d H:i:s'),
                    'dollarrate' => $doller_rate,
                    'dollar' => $doller_count,
                    'P_Cap' => $date_number
                ]
            ]);

            return $this->response->setJSON(["status" => 200, "message" => "Successfully Confirmed the Reservation"]);
        } catch (Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // cancel reservation
    public function cancel_reservation()
    {

        try {
            $tour = $this->request->getPost('tour');
            $hotel_id = $this->request->getPost('hotel');
            $date = $this->request->getPost('date');
            $date_number = $this->request->getPost('date_number');
            // $min_date = $this->request->getPost('min_date');
            // $max_date = $this->request->getPost('max_date');

            // $min_date = date('Y-m-d', strtotime($min_date));
            // $max_date = date('Y-m-d', strtotime($max_date));

            // get all dates between min_date & max_date 
            // $period = new \DatePeriod(
            //     new \DateTime($min_date),
            //     new \DateInterval('P1D'),
            //     (new \DateTime($max_date))->modify('+1 day')
            // );

            // $datesArray = [];
            // foreach ($period as $date) {
            //     $datesArray[] = $date->format('Y-m-d');
            // }


            $TourQuotationsModel = new TourQuotationsModel();

            if (empty($tour) || empty($hotel_id)) {
                return json_encode(['status' => 400, 'message' => 'Required field are empty']);
            }

            $data = [
                'booking_confirmed_flag' => 3,
                'confirmation_email' => null,
                'confirmation_reference' => null,
                'total_hotel_doller' => 0,
                'extra_hotel_fee' => 0
            ];


            // foreach ($datesArray as $date) {
            $date = date('Y-m-d', strtotime(trim($date)));

            $hotel_payment_flag = $TourQuotationsModel->select('hotel_payment_flag')->where('tour_date', $date)->where('quotation_id', $tour)->where('hotel_acc', $hotel_id)->where('quotation_day', $date_number)->first();

            if ($hotel_payment_flag['hotel_payment_flag'] == 1) {

                return $this->response->setJSON(['status' => 400, 'message' => 'Payment already done for the date ' . $date]);
            } else if ($hotel_payment_flag['hotel_payment_flag'] == 10) {

                return $this->response->setJSON(['status' => 400, 'message' => 'Please reject the voucher first for the date ' . $date]);
            } else {

                $TourQuotationsModel->set($data)->where('tour_date', $date)->where('quotation_id', $tour)->where('hotel_acc', $hotel_id)->where('quotation_day', $date_number)->update();

                $accountRunningModel = new AccountRunningModel();
                $get_acc_id = $accountRunningModel->select('ID')->where('index_no', $tour)->where('Discription', 'Hotel Reservation ' . $tour . ' / ' . $date)->where('L_Code', $hotel_id)->first();

                if ($get_acc_id) {
                    $acc_id = $get_acc_id['ID'];
                } else {
                    return $this->response->setJSON(['status' => 400, 'message' => 'No account running record found for the given tour and date. Please Contact the Developer']);
                }

                $dis = 'Hotel Reservation ' . $tour . ' / ' . $date;
                $accountRunningModel->where('index_no', $tour)->where('Discription', $dis)->where('ID', $acc_id)->delete();
            }
            // }

            return $this->response->setJSON(["status" => 200, "message" => "Successfully Cancelled the Reservation"]);
        } catch (Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    //hotelrateconfirm
    public function hotelrateconfirm()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $data['tile_dashboard'] = true;

        $paymentRequestModel = new PaymentRequestModel();

        $data['paymentRequests'] = $paymentRequestModel
            ->select('payment_request.*, consignee.tour_cancel, consignee.manual_id AS tour_manual_id, consignee.consignee AS tour_name, consignee.agent_name, consignee.start_date AS tour_start_date, consignee.end_date AS tour_end_date, tq_dates.date_range')
            ->join('consignee', 'consignee.tour_id = payment_request.tour_id')
            ->join('(SELECT quotation_id, hotel_acc, GROUP_CONCAT(tour_date ORDER BY tour_date) as date_range FROM tour_quotations GROUP BY quotation_id, hotel_acc) tq_dates', 'tq_dates.quotation_id = payment_request.tour_id AND tq_dates.hotel_acc = payment_request.hotel_acc', 'left')
            ->where('payment_request.type', 'Hotel')
            ->where('payment_request.payment_request', 0)
            ->where('consignee.tour_cancel <>', 1)
            ->orderBy('consignee.start_date', 'ASC')
            ->orderBy('payment_request.tour_id', 'ASC')
            ->findAll();

            // log_message('critical', $paymentRequestModel->getLastQuery());

        $accountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        return view('pages/dashboard2/hotel/hotel_rate_confirm', $data);
    }

    public function updateRequestAmount()
    {
        $id = $this->request->getPost('id');
        $amount = $this->request->getPost('amount');

        if (!$id || !is_numeric($amount)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Invalid data']);
        }

        $paymentRequestModel = new PaymentRequestModel();
        $updated = $paymentRequestModel->update($id, ['requestAmount' => $amount]);

        if ($updated) {
            return $this->response->setJSON(['status' => 200, 'message' => 'Updated successfully']);
        }

        return $this->response->setJSON(['status' => 500, 'message' => 'Update failed']);
    }
}
