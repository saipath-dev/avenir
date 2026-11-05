<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Controllers\Mail\InboxController;
use App\Controllers\Mail\SendController;
use App\Models\AccountRunningModel;
use App\Models\ChangesLogModel;
use App\Models\ConsigneeModel;
use App\Models\EmailModel;
use App\Models\InvoiceNumberModel;
use App\Models\TourQuotationsModel;
use App\Models\VoucherInvoiceModel;
use App\Models\VoucherPaymentModel;
use Exception;

class HotelManagementController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function hotel_reservation()
    {

        try {
            $tour_id = $this->request->getPost('tour_id');
            $dates = $this->request->getPost('dates');
            $datesArray = explode(',', strval($dates));

            // Get email details from the request
            $em_to = $this->request->getPost('em_to');
            $em_subject = $this->request->getPost('em_subject');
            //$em_body = $this->request->getPost('em_body');
            $em_body = $this->request->getPost('em_body');
            $attachments = $this->request->getFiles();
            $tour_quotation_id = $this->request->getPost('id');
            // tf-stage8: no new hotel reservation with a blocked hotel
            $tqRow = \Config\Database::connect()->query('SELECT hotel_acc FROM tour_quotations WHERE id = ? LIMIT 1', [(int) $tour_quotation_id])->getRowArray();
            if ($tqRow && ($why = \App\Libraries\TourFlow\Blocks::workRefusalForSupplierAcc(\Config\Database::connect(), (string) $tqRow['hotel_acc'], 'hotel reservation (legacy)', (string) session()->get('username')))) {
                return $this->response->setJSON(['status' => 428, 'message' => $why, 'blocked' => true]);
            }
            $tour_id = $this->request->getPost('tour_id');
            $em_cc = $this->request->getPost('em_cc[]');
            if (!empty($em_cc)) {
                $em_cc = json_encode($em_cc);
                $em_cc = json_decode($em_cc, true);
            }

            // Check if the required data is missing
            if (empty($tour_id) || count($datesArray) == 0 || empty($em_to) || empty($em_subject) || empty($em_body)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Missing required data!']);
            }

            // Call the send method from SendController with the email data
            $emailController = new SendController();
            $email_sent = $emailController->send($em_to, $em_subject, $em_body, $em_cc, null, $tour_quotation_id, $tour_id);

            if ($email_sent == 200) {

                $TourQuotationsModel = new TourQuotationsModel();

                // Update hotel_booking_flag to 1
                foreach ($datesArray as $date) {
                    $formattedDate = date('Y-m-d', strtotime(trim($date)));
                    $TourQuotationsModel->set(['hotel_booking_flag' => 1])
                        ->where('tour_date', $formattedDate)
                        ->where('quotation_id', $tour_id)
                        ->update();
                }

                return $this->response->setJSON(['status' => 200, 'message' => 'Hotel Booking Confirmed and Email Sent!']);
            } else if ($email_sent == 404) {
                return $this->response->setJSON(['status' => 404, 'message' => 'You have no email account configured!']);
            } else {
                return $this->response->setJSON(['status' => 500, 'message' => 'Failed to send email!', 'error' => $email_sent]);
            }
        } catch (Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function get_mail()
    {
        try {
            $tour_quotation_id = $this->request->getPost('id');

            if (empty($tour_quotation_id)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Missing tour_quotation_id']);
            }

            $emailModel = new EmailModel();
            $emailData = $emailModel->where('tour_quotation_id', $tour_quotation_id)->orderBy('id', 'DESC')->first();

            if (empty($emailData)) {
                return $this->response->setJSON(['status' => 404, 'message' => 'No email found for the given tour_quotation_id']);
            }

            $mail_id = $emailData['mail_id'];

            $inboxController = new InboxController();
            $emails = $inboxController->filterEmailsByThread($mail_id);

            return $this->response->setJSON(['status' => 200, 'emails' => $emails]);
        } catch (Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // public function confirm_reservation()
    // {
    //     try {
    //         $tour = $this->request->getPost('tour_id');
    //         $tour_date = $this->request->getPost('tour_date');
    //         $date_number = $this->request->getPost('date_number');

    //         $confirmation_email = $this->request->getFile('attachment');
    //         $confirmation_reference = $this->request->getPost('reference');

    //         $hotel = $this->request->getPost('old_hotel');
    //         $payment = $this->request->getPost('confirm_lkr');
    //         $doller_rate = $this->request->getPost('confirm_p_rate');
    //         $doller_count = $this->request->getPost('confirm_doller');
    //         $extra = $this->request->getPost('extra');

    //         $dates = $this->request->getPost('dates');
    //         $datesArray = explode(',', strval($dates));

    //         // $username = session()->get('username');

    //         // confirmation_email and confirmation_reference both empty return error. at least one should be filled
    //         if ((empty($confirmation_email) || !$confirmation_email->isValid()) && empty($confirmation_reference)) {
    //             return $this->response->setJSON(['status' => 400, 'message' => 'Please upload the confirmation email or enter the reference number']);
    //         }

    //         // if confirmation_email is not empty, check if it is a valid file and upload it to WRITEPATH
    //         if (!empty($confirmation_email)) {
    //             if ($confirmation_email->isValid() && !$confirmation_email->hasMoved()) {
    //                 $newName = $confirmation_email->getRandomName();
    //                 $confirmation_email->move(WRITEPATH . 'uploads/confirmation_emails', $newName);
    //                 $confirmation_email = $newName;
    //             }
    //         }

    //         $TourQuotationsModel = new TourQuotationsModel();

    //         if (empty($tour) || empty($tour_date)) {
    //             return json_encode(['status' => 400, 'message' => 'Required field are empty']);
    //         }

    //         $data = [
    //             'hotel_advance' => $payment,
    //             'booking_confirmed_flag' => 1,
    //             'confirmation_email' => $confirmation_email,
    //             'confirmation_reference' => $confirmation_reference,
    //             'total_hotel_doller' => $doller_count,
    //             'extra_hotel_fee' => $extra
    //         ];

    //         foreach ($datesArray as $date) {
    //             $date = date('Y-m-d', strtotime(trim($date)));
    //             $TourQuotationsModel->set($data)->where('tour_date', $date)->where('quotation_id', $tour)->where('hotel_acc', $hotel)->where('quotation_day', $date_number)->update();
    //         }

    //         $session = session();
    //         $username = $session->get('username');

    //         $invoiceNoModel = new InvoiceNumberModel();
    //         $acc_id = $invoiceNoModel->incrementAccId();

    //         $accountRunningModel = new AccountRunningModel();

    //         // Creditor Invoice Record
    //         $accountRunningModel->insertBatch([
    //             [
    //                 'ID' => $acc_id,
    //                 'L_Code' => '4-8-106-10386', // Hotel Charges
    //                 'Date' => $date,
    //                 'Discription' => 'Hotel Reservation ' . $tour . ' / ' . $date,
    //                 'Dr' => $payment,
    //                 'Cr' => 0.00,
    //                 'Cashier' => $username,
    //                 'index_no' => $tour,
    //                 'job' => '4-8-106-10386',
    //                 'Invoice_Num' => $tour,
    //                 'advances_pay' => 22,
    //                 'Dt' => date('Y-m-d H:i:s'),
    //                 'dollarrate' => $doller_rate,
    //                 'dollar' => $doller_count,
    //                 'P_Cap' => $date_number
    //             ],
    //             [
    //                 'ID' => $acc_id,
    //                 'L_Code' => $hotel,
    //                 'Date' => $date,
    //                 'Discription' => 'Hotel Reservation ' . $tour . ' / ' . $date,
    //                 'Dr' => 0.00,
    //                 'Cr' => $payment,
    //                 'Cashier' => $username,
    //                 'index_no' => $tour,
    //                 'job' => '4-8-106-10386',
    //                 'Invoice_Num' => $tour,
    //                 'advances_pay' => 22,
    //                 'Dt' => date('Y-m-d H:i:s'),
    //                 'dollarrate' => $doller_rate,
    //                 'dollar' => $doller_count,
    //                 'P_Cap' => $date_number
    //             ]
    //         ]);

    //         return $this->response->setJSON(["status" => 200, "message" => "Successfully Confirmed the Reservation"]);
    //     } catch (Exception $e) {
    //         // Return an error response
    //         return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
    //     }
    // }
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
                'tplfoc' => $tplfoc
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
