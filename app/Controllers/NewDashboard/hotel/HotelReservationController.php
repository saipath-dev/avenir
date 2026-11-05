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


class HotelReservationController extends BaseController
{

    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $data['tile_dashboard'] = true;

        $tour_id = $this->request->getGet('tour_id');
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

        return view('pages/dashboard2/hotel/hotel_reservation', $data);
    }

    public function send_email()
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

}
