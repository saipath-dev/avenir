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


class HotelPaymentController extends BaseController
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

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

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

        return view('pages/dashboard2/hotel/hotel_payment', $data);
    }

    public function get_room_rates()
    {
        $tour_id = $this->request->getPost('tour_id');
        $dates = $this->request->getPost('dates');
        $hotel = $this->request->getPost('hotel');
        $date_no = $this->request->getPost('date_no');
        // $date_count = $this->request->getPost('date_count');

        $datesArray = explode(',', strval($dates));



        $consigneeModal = new ConsigneeModel();
        $consigneeData = $consigneeModal->select('sgl, dbl, tpl')->where('tour_id', $tour_id)->first();

        $sglcount = $consigneeData['sgl'];
        $dblcount = $consigneeData['dbl'];
        $tplcount = $consigneeData['tpl'];

        $sgl_total = 0;
        $dbl_total = 0;
        $tpl_total = 0;

        $hotel_acc = "";
        $hotel_name = "";



        $TourQuotationsModel = new TourQuotationsModel();
        foreach ($datesArray as $date) {
            $date = date('Y-m-d', strtotime(trim($date)));
            $roomRates = $TourQuotationsModel
                ->select('hotel_acc, hotel_name, sgl, dbl, tpl, total_hotel_doller, pay_doller_count, extra_hotel_fee')
                ->where('tour_date', $date)
                ->where('quotation_id', $tour_id)
                // ->where('quotation_day', $date_no)
                ->where('hotel_acc', $hotel)
                ->first();

            // log_message('critical', $TourQuotationsModel->getLastQuery());
            // log_message('critical', json_encode($roomRates));

            if (empty($roomRates)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'No data found for the selected date']);
            }

            $sgl_total += ($roomRates['sgl'] ?? 0) * $sglcount;
            $dbl_total += ($roomRates['dbl'] ?? 0) * $dblcount;
            $tpl_total += ($roomRates['tpl'] ?? 0) * $tplcount;

            $hotel_acc = $roomRates['hotel_acc'];
            $hotel_name = $roomRates['hotel_name'];
            $extra = $roomRates['extra_hotel_fee'];
        }
        // log_message('critical', json_encode(['status' => 200, 'sgl_total' => $sgl_total, 'dbl_total' => $dbl_total, 'tpl_total' => $tpl_total, 'hotel_acc' => $hotel_acc, 'hotel_name' => $hotel_name, 'total_hotel_doller' => $roomRates['total_hotel_doller'], 'pay_doller_count' => $roomRates['pay_doller_count'], 'extra_fee_hotel' => $extra]));

        return $this->response->setJSON(['status' => 200, 'sgl_total' => $sgl_total, 'dbl_total' => $dbl_total, 'tpl_total' => $tpl_total, 'hotel_acc' => $hotel_acc, 'hotel_name' => $hotel_name, 'total_hotel_doller' => $roomRates['total_hotel_doller'], 'pay_doller_count' => $roomRates['pay_doller_count'], 'extra_fee_hotel' => $extra]);
    }

    public function hotel_payment()
    {
        try {

            $request = $this->request->getPost();

            $tour = strip_tags($request['tour_id']);
            $manual_id = strip_tags($request['m_tour_id']);
            $hotel = strip_tags($request['hotel']);
            $hotel_name = strip_tags($request['hotel_name']);
            $doller_rate = strip_tags($request['rate']);
            $day_no = strip_tags($request['p_date_number']);
            $extra = isset($request['extra']) ? strip_tags($request['extra']) : null;
            $bank = strip_tags($request['bank_acc']);
            $bank_name = strip_tags($request['bank_name']);
            $payment = isset($request['advance_payment']) ? strip_tags($request['advance_payment']) : null;
            $ref = isset($request['hotel_ref']) ? strip_tags($request['hotel_ref']) : null;
            $pay_date = isset($request['pay_date']) ? strip_tags($request['pay_date']) : null;
            $tot_doller = isset($request['advance_payment_doller']) ? strip_tags($request['advance_payment_doller']) : null;
            $part_pay_doller = isset($request['part_pay']) ? strip_tags($request['part_pay']) : null;
            $part_pay1 = isset($request['part_pay']) ? strip_tags($request['part_pay']) : null;
            // number formate $part_pay1
            $part_pay1 = number_format($part_pay1, 2, '.', '');

            $dates = isset($request['dates']) ? strip_tags($request['dates']) : null;
            $datesArray = explode(',', $dates);

            $consigneeModal = new ConsigneeModel();
            $consigneeData = $consigneeModal->select('tour_cancel')->where('tour_id', $tour)->first();
            if ($consigneeData['tour_cancel'] == 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'This tour has been cancelled. You cannot confirm the reservation.']);
            }

            $session = session();
            $username = $session->get('username');

            $TourQuotationsModel = new TourQuotationsModel();

            if (empty($hotel) || empty($payment) || empty($ref) || empty($bank)) {
                return json_encode(['status' => 400, 'message' => 'Required field are empty']);
            }

            if (!is_numeric($payment) || $payment < 0) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number and greater than 0']);
            }


            foreach ($datesArray as $date) {

                $date = date('Y-m-d', strtotime(trim($date)));

                // check whether the booking_confirmed_flag is 1 if not return an error
                $booking_confirmed_flag = $TourQuotationsModel
                    ->select('booking_confirmed_flag')
                    ->where('tour_date', $date)
                    ->where('quotation_id', $tour)
                    ->where('hotel_acc', $hotel)
                    //  ->where('quotation_day',$day_no)
                    ->first();
                if ($booking_confirmed_flag['booking_confirmed_flag'] != 1 || $booking_confirmed_flag == null) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Please confirm the reservation first for the date ' . $date . ' before making a payment']);
                }

                // check whether the hotel_payment_flag is 0 if not return an error
                $hotel_payment_flag = $TourQuotationsModel
                    ->select('hotel_payment_flag,total_hotel_doller,pay_doller_count')
                    ->where('tour_date', $date)
                    ->where('quotation_id', $tour)
                    ->where('hotel_acc', $hotel)
                    //    ->where('quotation_day',$day_no)
                    ->first();
                if ($hotel_payment_flag['hotel_payment_flag'] == 1 && $hotel_payment_flag['pay_doller_count'] >= $hotel_payment_flag['total_hotel_doller']) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Payment already done for the date ' . $date]);
                }
            }

            $tour_up = 0;
            foreach ($datesArray as $date) {

                $date = date('Y-m-d', strtotime(trim($date)));
                // select pay_doller_count in tour_quotations table where tour_date = $datesArray[0] and quotation_id = $tour
                $pay_doller = $TourQuotationsModel
                    ->select('pay_doller_count')
                    ->where('tour_date', $date)
                    ->where('quotation_id', $tour)
                    ->where('hotel_acc', $hotel)
                    // ->where('quotation_day',$day_no)
                    ->first();
                $part_payment = $pay_doller['pay_doller_count'];
                // $part_pay_doller convert to number
                $part_payment = (float)$part_payment;
                $part_pay_doller = $part_payment + $part_pay_doller;


                $data = [
                    // 'hotel_advance' => $payment,
                    'hotel_dollar_rate' => $doller_rate,
                    'advance_ref' => $ref,
                    'hotel_payment_flag' => 10,
                    'pay_doller_count' => $part_pay_doller
                ];

                $tour_up = $TourQuotationsModel
                    ->set($data)
                    ->where('tour_date', $date)
                    ->where('quotation_id', $tour)
                    ->where('hotel_acc', $hotel)
                    // ->where('quotation_day',$day_no)
                    ->update();
            }

            if ($tour_up) {
                //Voucher Authentification Record

                $model_inv = new InvoiceNumberModel();
                $inv = $model_inv->incrementVoucherToken();
                $v_num = $inv;

                // get $datesArray length
                // $datesArrayLength = count($datesArray);
                // $amt = $payment * $datesArrayLength;

                $data = [
                    'Pay' => $hotel_name,  //hotel name 
                    'emp_code' => $hotel,  //hotel acc_no
                    'S_name' => $hotel_name, //hotel name 
                    'Acc_No' => $hotel, //hotel acc_no
                    'sup' => '1',
                    'type' => 'Bank Transfer', //BT
                    'Amount' => $payment,
                    'VAT' => '0',
                    'VAT_Amt' => '0',
                    'vat_m' => '0',
                    'nbt' => '0',
                    'nbt_amt' => '0',
                    'nbt_m' => '0',
                    'g_ttl' => $payment,
                    'Address' => '0',
                    'print' => '0',
                    'des' => '0',
                    'W_Date' => $pay_date,
                    'dv' => '0',
                    'app_user' => session()->get('acc_no'),
                    'login' => session()->get('username'),
                    'Aflag' => '0',
                    'v_typ' => 'Bank Transfer',
                    'ref_po' => $tour, // $tour $ref
                    'insflag' => 1,
                    'inv' => '0',
                    'Vnum' => $v_num,   // voucher token
                    'Bank' => $bank_name,
                    'Bank_acc' => $bank,
                    'C_Date' => date('Y-m-d H:i:s'),
                    'C_Number' => $tour,
                    'W_Date' => $pay_date,
                    // 'acc_run_id' => $acc_id,
                    'is_expenses' => 1,
                    'advances_pay' => 2,
                    'V_num' => $dates,
                    'doller_amt' => $part_pay1
                ];

                $data_add = [
                    'Date' => $pay_date,
                    'Invoice' => $tour,
                    'Description' => 'Hotel Reservation ' . $manual_id . ' / ' . $date,
                    'Units' => 0,
                    'Rate' => 0,
                    'Amount' => $payment,
                    'InvAmount' => $payment,
                    'V_tkn' => $v_num,   // voucher_token
                    'ityp' => '1',
                    'link_code' => $hotel,
                    'employee' => $hotel_name,
                    'login' => session()->get('username'),
                    'INV_Date' => date('Y-m-d'),
                    'Payee' => $hotel_name,
                    // 'acc_run_id' => $acc_id,
                    'account' => $hotel
                ];
                // $this->db->insert("v_inv", $data_add);
                $v_invmodel = new VoucherInvoiceModel();
                $v_invmodel->insert($data_add);

                $voucherpaymentmodel = new VoucherPaymentModel();
                $voucherpaymentmodel->insert($data);

                // log message for $data

                // log_message('critical', 'Voucher Payment Data: ' . json_encode($data));

                return $this->response->setJSON(["status" => 200, "message" => "Successfully Updated the Payment"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (Exception $e) {
            // Return an error response
            log_message('critical', 'Hotel Payment Error: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
