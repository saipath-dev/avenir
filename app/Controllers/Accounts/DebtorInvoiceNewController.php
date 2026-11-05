<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Controllers\Mail\SendController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\AccountRunningUsdModel;
use App\Models\InvoiceNumberModel;
use App\Models\CustomerModel;
use App\Models\ConsigneeModel;
use App\Models\DebtorInvoiceLogModel;
use App\Models\TourActivityModel;
use App\Models\TourQuotationsModel;
use App\Models\InvalertlogModel;

class debtorInvoiceNewController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/accounts/debtor_receipt
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // load auctions
        $scustomerModel = new CustomerModel();
        $data['customer'] = $scustomerModel->findAll();

        // $data['i']

        $user_id = session()->get('username');
        $group_id = session()->get('group_id');

        $tour_id = $this->request->getGet('tour_id');
        $inv_id = urldecode($this->request->getGet('inv_id'));

        if (!empty($tour_id)) {

            $consigneeModel = new ConsigneeModel();
            $data['consignee'] = $consigneeModel->where('tour_id', $tour_id)->first();
            // get $data['consignee'] created_user
            $created_user = $data['consignee']['create_user'];
            $start_date = $data['consignee']['start_date'];
            $end_date = $data['consignee']['end_date'];

            // get $start_date year and month
            $date_y = date('Y-m', strtotime(strval($start_date)));

            $agent = $data['consignee']['agent'];
            $customerModel = new CustomerModel();
            $data['customer_margin'] = $customerModel->select('margin')->where('Sup_Acc_No', $agent)->first();

            $touractivityModel = new TourActivityModel();
            $data['activity_details'] = $touractivityModel->getActivityDetails($tour_id);

            $TourQuotationsModel = new TourQuotationsModel();
            $data['hotel_details'] = $TourQuotationsModel->select('MAX(meal_code) as meal_code,MAX(tour_date) as first_date, MIN(tour_date) AS last_date,COUNT(tour_date) as nights')->where('quotation_id', $tour_id)->first();


            // $data['consignee'] = 0;
            // load tours
            // Load available tours excluding those in account_running
            // $consigneeModel = new ConsigneeModel();
            // $data['consignee'] = $consigneeModel->getAvailableTours();

            $accountPostingModel = new AccountPostingModel();
            $data['methods'] = $accountPostingModel->where('B_Code', 5)->where('T_Code', 7)->where('H_Code', 7)->findAll();

            if ($created_user == $user_id || $group_id == 1 || $date_y <= date('2024-10')) {
                $is_permission = 1;
            } else {
                $is_permission = 0;
            }

            $data['is_permission'] = $is_permission;
            $data['inv_id'] = $inv_id;
            return view('pages/accounts/debtor_invoice_new', $data);
        }
    }

    // route : base_url/accounts/debtor_invoice/complete_payment
    public function complete_payment()
    {
        try {
            $customer = $this->request->getPost('customer');
            $tour = $this->request->getPost('tour_id');
            $payment_method = $this->request->getPost('pay_type');
            $date = $this->request->getPost('date');
            $description = $this->request->getPost('description');
            $credit_balance = $this->request->getPost('credit_balance');
            $doller_rate = $this->request->getPost('drate');
            $amount = $this->request->getPost('amount');
            $room_cost = $this->request->getPost('room_cost');
            $transport_cost = $this->request->getPost('transport_cost');
            $consignee = $this->request->getPost('consignee');
            $hotel_amount = $this->request->getPost('amount2');  // hotel amount
            $activity_amount = $this->request->getPost('amount1');  // activity amount
            $activity_des = $this->request->getPost('activity_des');  // activity amount
            $hotel_des = $this->request->getPost('hotel_des');  // activity amount
            $inv = $this->request->getPost('inv');

            $consigneeModel = new ConsigneeModel();
            $tour_details = $consigneeModel->where('tour_id', $tour)->first();
            // get start date of the tour
            $start_date = $tour_details['start_date'];

            if ($inv == '') {
                $invoiceNoModel = new InvoiceNumberModel();
                $inv_no = $invoiceNoModel->incremenDebtorInvocieNo();

                // get $date year and month
                $date_y = date('y', strtotime(strval($date)));
                $date_m = date('m', strtotime(strval($date)));

                // $date_y = date('y', strtotime(strval($start_date)));
                // $date_m = date('m', strtotime(strval($start_date)));

                $new_inv_no = 'AH-' . $inv_no . '-' . $date_m . '-' . $date_y;
            } else {
                $new_inv_no = $inv;
            }
            $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();

            $DebtorInvoiceLogModel->insert([
                'invoice_no' => $new_inv_no,
                'agent' => $customer,
                'tour_id' => $tour,
                'income_type' => $date,
                'date' => $date,
                'description' => $description,
                'room_cost' => $room_cost,
                'transport_cost' => $transport_cost,
                'doller_rate' => $doller_rate,
                'dollar_amount' => $amount,
                'credit_balance' => $credit_balance,
                'consignee' => $consignee,
                'hotel_amount' => $hotel_amount,
                'activity_amount' => $activity_amount,
                'activity_des' => $activity_des,
                'hotel_des' => $hotel_des,
                'payment_lcode' => $payment_method,

            ]);

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $accountRunningModel = new AccountRunningModel();
            $accountRunningUsdModel = new AccountRunningUsdModel();
            // Credit balance Record Insert
            // DR Recrord
            $accountRunningModel->insert([
                'ID' => $acc_id,
                'L_Code' => $customer, // Customer
                'Date' => $date,
                'Discription' => $tour . "- Invoice",
                'Dr' => $credit_balance,
                'Cr' => 0.00,
                'Invoice_Num' => $new_inv_no,
                'Linkacc' => $customer,
                'index_no' => $tour,
                'dollarrate' => $doller_rate,
                'dollar' => $amount,
                'Cashier' => session()->get('username'),
                'Dt' => date('Y-m-d H:i:s')
            ]);

            // CR Record
            $accountRunningModel->insert([
                'ID' => $acc_id,
                'L_Code' => $payment_method, //Income
                'Date' => $date,
                'Discription' => $tour . "- Invoice",
                'Dr' => 0.00,
                'Cr' => $credit_balance,
                'Invoice_Num' => $new_inv_no,
                'Linkacc' => $customer,
                'index_no' => $tour,
                'dollarrate' => $doller_rate,
                'dollar' => $amount,
                'Cashier' => session()->get('username'),
                'Dt' => date('Y-m-d H:i:s')
            ]);

            $data = [
                'confirm' => 1,
                'verify' => 1,
            ];

            $TourQuotationsModel = new TourQuotationsModel();
            $consignmentmodel = new ConsigneeModel();

            if ($TourQuotationsModel->where('quotation_id', $tour)->set($data)->update()) {
                $consignmentmodel->set('verify', 1)->set('amend_inv', $new_inv_no)->where('tour_id', $tour)->update();
                // The tour is confirmed: hand its hotel nights to the payment side — what we PAY each
                // hotel per the Hotel Rates panel (ad hoc → contract → quoted flagged). Never blocks invoicing.
                try {
                    (new \App\Libraries\HotelRates\HotelCostService(\Config\Database::connect(), (string) session()->get('username')))->computeTour((string) $tour);
                } catch (\Throwable $e) {
                    log_message('error', 'Hotel cost handoff failed for tour ' . $tour . ': ' . $e->getMessage());
                }
                return json_encode(['status' => 200, 'message' => 'Payment completed successfully', 'tour' => $tour]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {

            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/accounts/debtor_invoice_report/report
    public function print()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();

        $tour = $this->request->getGet('tour');

        $data['invoice_details'] = $DebtorInvoiceLogModel->select('debtor_invoice_log.*,consignee.manual_id,consignee.agent_ref')->join('consignee', 'consignee.tour_id = debtor_invoice_log.tour_id', 'left')->where('debtor_invoice_log.tour_id', $tour)->where('cancel_user', '',)->where('cancel_date', '0000-00-00')->first();

        $consigneeModel = new ConsigneeModel();

        $data['consignee_details'] = $consigneeModel->select('customers.*')
            ->join('customers', 'customers.Sup_Acc_No = consignee.agent', 'left')
            ->where('tour_id', $tour)
            ->first();


        return view('pages/accounts/debtor_invoice_print', $data);
    }

    public function sent()
    {
        $data = array();

        $DebtorInvoiceLogModel = new DebtorInvoiceLogModel();

        $tour = $this->request->getGet('tour_id');

        $data['invoice_details'] = $DebtorInvoiceLogModel->select('debtor_invoice_log.*,consignee.manual_id')->join('consignee', 'consignee.tour_id = debtor_invoice_log.tour_id', 'left')->where('debtor_invoice_log.tour_id', $tour)->where('cancel_user', '',)->where('cancel_date', '0000-00-00')->first();

        // tf-e8-access-r2: the SQL text of the query is no longer returned to the browser (was: $data['last_query'])

        //echo 'debtor_invoice_log query: ' . $data['last_query'];

        $consigneeModel = new ConsigneeModel();
        $data['consignee_details'] = $consigneeModel->select('customers.* , consignee.ID as consignee_id')
            ->join('customers', 'customers.Sup_Acc_No = consignee.agent', 'left')
            ->where('tour_id', $tour)
            ->first();

        // tf-e8-access-r2: (the second query's SQL text is not returned either)

        //echo 'consignee query: ' . $data['last_query'];

        //set json header
        $this->response->setHeader('Content-Type', 'application/json');

        return json_encode($data);
    }

    public function sent_mail()
    {
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

        // Call the send method from SendController with the email data
        $emailController = new SendController();
        $email_sent = $emailController->send($em_to, $em_subject, $em_body, $em_cc, $attachments, $tour_quotation_id, $tour_id);

        // log_message('critical', 'Email sent status: ' . $email_sent);

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
            return $this->response->setJSON(['status' => 500, 'message' => 'Failed to send email!']);
        }
    }

    public function send_receipt()
    {
        //return "send_receipt";

        $pdfFile = $this->request->getFile('pdf');
        $customerACC = $this->request->getPost('customer_id');
        $invNo = $this->request->getPost('invoice_no');
        $manualId = $this->request->getPost('manual_id');
        $tourid = $this->request->getPost('tour_id');
        //$ccEmails = $this->request->getPost('cc'); // Get CC emails from the request

        //append cc emails to deafult accounts@avenirholidays.com, nf@avenirholidays
        $ccEmails = ['accounts@avenirholidays.com', 'nf@avenirholidays.com'];

        // Append additional CC emails from the request
        $additionalCcEmails = $this->request->getPost('cc');
        if (!empty($additionalCcEmails)) {
            $additionalCcEmailsArray = explode(',', $additionalCcEmails);
            $ccEmails = array_merge($ccEmails, $additionalCcEmailsArray);
        }

        //set as array by dividing ,
        //$ccEmails = explode(',', $ccEmails);

        // Validate file and consignee ID
        if (!$pdfFile || !$pdfFile->isValid() || empty($customerACC)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Invalid file or missing consignee ID']);
        }

        // Define upload path
        $uploadPath = WRITEPATH . 'uploads/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true); // Create the directory if it doesn't exist
        }

        // Save the file
        $newFileName = $pdfFile->getRandomName();
        if (!$pdfFile->move($uploadPath, $newFileName)) {
            return $this->response->setJSON(['status' => 500, 'message' => 'Failed to save the uploaded file']);
        }

        // File successfully moved, continue with email logic
        $tempFilePath = $uploadPath . $newFileName;

        //{
        //    protected $table = 'customers';
        //    protected $primaryKey = 'ID';
        //    protected $allowedFields = ['Sup_Acc_No', 'Name', 'Address', 'NIC', 'TP', 'Mobile', 'flag', 'cr_lmt', 'cr_dyz', 'csh', 'chq', 'chq_fvr', 'rep_offce', 'rep_mobile', 'ac', 'sup_cat', 'ref_no', 'dealer_acc', 'email', 'country', 't_code_1', 't_code_2', 'zipcode', 'margin', 'long_name', 'prefix'];
        //    protected $returnType = 'array';
        //}

        // Retrieve customer email (similar to previous logic)
        $customerModel = new CustomerModel();

        //?find using sup_acc_no
        $customer = $customerModel->where('Sup_Acc_No', $customerACC)->first();

        //echo last query
        $last_query = $customerModel->db->getLastQuery();
        //echo 'last query: ' . $last_query;

        //print_r($consignee['email']);

        if (!$customer || empty($customer['email'] || $customer['email'] == 'null')) {
            return $this->response->setJSON(['status' => 404, 'message' => 'Customer not found or email missing']);
        }

        $recipientEmail = $customer['email'];

        // Prepare email
        $attachments = [
            ['path' => $tempFilePath, 'name' => 'receipt.pdf']
        ];

        $emailController = new SendController();

        //send($to, $subject, $body, $cc = null, $attachments = [], $tour_quotation_id = null, $tour_id = null)
        $emailSent = $emailController->send(
            $recipientEmail,
            'Receipt for your payment',
            'Please find attached the receipt for your payment',
            $ccEmails,
            $attachments
        );

        // Cleanup temporary file
        unlink($tempFilePath);

        // Handle email response
        if ($emailSent == 200) {

            $ConsigneeModel = new ConsigneeModel();
            $invalertlog = new InvalertlogModel();

            $affectedRows = $ConsigneeModel->where('tour_id', $tourid)->where('manual_id', $manualId)->where('amend_inv', $invNo)
                ->set([
                    'inv_alert_status' => 3,
                    'accepted_user' => session()->get('username'),
                    'accepted_date' => date('Y-m-d H:i:s')
                ])
                ->update();

            $data = [
                'tour_id' => $tourid,
                'manual_id' => $manualId,
                'inv_no' => $invNo,
                'action' => 'Receipt Sent',
                'action_user' => session()->get('username'),
                'action_date' => date('Y-m-d H:i:s')
            ];

            // Insert into database
            $invalertlog->insert($data);

            return $this->response->setJSON(['status' => 200, 'message' => 'Receipt sent successfully']);
        }
        return $this->response->setJSON(['status' => 500, 'message' => 'Failed to send email' . $emailSent]);
    }

    // route : base_url/accounts/debtor_receipt/additional_inv
    public function additional_inv()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // load auctions
        $scustomerModel = new CustomerModel();
        $data['customer'] = $scustomerModel->findAll();

        // $data['i']


        $tour_id = $this->request->getGet('tour_id');
        if (!empty($tour_id)) {

            $consigneeModel = new ConsigneeModel();
            $data['consignee'] = $consigneeModel->where('tour_id', $tour_id)->first();

            $agent = $data['consignee']['agent'];
            $customerModel = new CustomerModel();
            $data['customer_margin'] = $customerModel->select('margin')->where('Sup_Acc_No', $agent)->first();

            $touractivityModel = new TourActivityModel();
            $data['activity_details'] = $touractivityModel->getActivityDetails($tour_id);

            $TourQuotationsModel = new TourQuotationsModel();
            $data['hotel_details'] = $TourQuotationsModel->select('MAX(meal_code) as meal_code,MAX(tour_date) as first_date, MIN(tour_date) AS last_date,COUNT(tour_date) as nights')->where('quotation_id', $tour_id)->first();
        }

        // $data['consignee'] = 0;
        // load tours
        // Load available tours excluding those in account_running
        // $consigneeModel = new ConsigneeModel();
        // $data['consignee'] = $consigneeModel->getAvailableTours();

        $accountPostingModel = new AccountPostingModel();
        $data['methods'] = $accountPostingModel->where('B_Code', 5)->where('T_Code', 7)->where('H_Code', 7)->findAll();

        return view('pages/accounts/additional_inv', $data);
    }
}
