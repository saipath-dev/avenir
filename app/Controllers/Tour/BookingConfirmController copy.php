<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

use App\Controllers\Mail\InboxController;
use App\Libraries\MenuLoader;
use App\Models\TourQuotationsModel;
use App\Models\SupplierModel;
use App\Models\ConsigneeModel;
use App\Models\AccountRunningModel;
use App\Models\InvoiceNumberModel;
use App\Models\ChangesLogModel;
use App\Models\AccountPostingModel;
use App\Models\StaffMemberModel;
use App\Models\UserModel;
use App\Models\VoucherInvoiceModel;
use App\Models\VoucherPaymentModel;
use App\Models\LogSheetMileModel;
use App\Models\PassengerDetailsModel;
use DateInterval;
use DatePeriod;
use DateTime;
use PhpParser\Node\Expr\FuncCall;

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

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $faxName = $this->request->getGet('consignee');
        $email = $this->request->getGet('email');
        $drange = $this->request->getGet('drange');


        $TourQuotationsModel = new TourQuotationsModel();
        $tourDetailsQuery = $TourQuotationsModel->db->table('tour_quotations')
            ->select('tour_quotations.quotation_id AS tour_id, Max(consignee.end_date) AS end_date, Min(tour_quotations.tour_date) AS start_date, Max(tour_quotations.confirm) As Confirm, Max(consignee.consignee) AS consignee, consignee.mobile as mobile, consignee.email AS email, consignee.passenger_count AS passenger_count, consignee.vehicle_name AS vehicle_name, Max(consignee.manual_id) AS manual_id')
            ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
            ->where('tour_quotations.confirm', 1)
            ->where('tour_quotations.finalize', 1)
            ->groupBy('tour_quotations.quotation_id');

        if (empty($faxName) && empty($email) && empty($drange)) {
            $tourDetails = $tourDetailsQuery->get()->getResult();
        } else {
            $queries = [];

            if (!empty($faxName) && !empty($drange)) {
                $date_parts = explode(' to ', strval($drange));
                if (count($date_parts) == 2) {
                    $start_date = $date_parts[0];
                    $end_date = $date_parts[1];

                    $comQuery1  = clone $tourDetailsQuery;
                    $comQuery1 = $comQuery1->like('consignee.consignee', strval($faxName))
                        ->groupStart()
                        ->where('consignee.start_date >=', $start_date)
                        ->where('consignee.start_date <=', $end_date)
                        ->orWhere('consignee.end_date >=', $start_date)
                        ->where('consignee.end_date <=', $end_date)
                        ->groupEnd();
                    $queries[] = $comQuery1;
                }
            } else if (!empty($email) && !empty($drange)) {
                $date_parts = explode(' to ', strval($drange));
                if (count($date_parts) == 2) {
                    $start_date = $date_parts[0];
                    $end_date = $date_parts[1];

                    $comQuery2  = clone $tourDetailsQuery;
                    $comQuery2 = $comQuery2->like('consignee.email', strval($email))
                        ->groupStart()
                        ->where('consignee.start_date >=', $start_date)
                        ->where('consignee.start_date <=', $end_date)
                        ->orWhere('consignee.end_date >=', $start_date)
                        ->where('consignee.end_date <=', $end_date)
                        ->groupEnd();
                    $queries[] = $comQuery2;
                }
            } else if (!empty($faxName)) {
                $faxQuery = clone $tourDetailsQuery;
                $faxQuery = $faxQuery->like('consignee.consignee', strval($faxName));
                $queries[] = $faxQuery;
            } else if (!empty($email)) {
                $emailQuery = clone $tourDetailsQuery;
                $emailQuery = $emailQuery->like('consignee.email', strval($email));
                $queries[] = $emailQuery;
            } else if (!empty($drange)) {
                $date_parts = explode(' to ', strval($drange));
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

        return view('pages/tour/booking_confirm', $data);
    }

    // Hotel Management Page 
    public function findAllDetails()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $user_emp_id = session()->get('emp_id');
        $staffMemberModel = new StaffMemberModel();
        $userModel = new UserModel();

        //class UserModel extends Model
        //{
        //    protected $table = 'user_acc_tbl';
        //    protected $primaryKey = 'id';
        //    protected $allowedFields = ['emp_id', 'user', 'pass', 'acc_no', 'group_id', 'access', 'login_type', 'last_logged', 'host', 'email', 'email_password', 'agent' ];
        //    protected $returnType = 'array';
        //}

        $staff = $staffMemberModel->where('emp_num', $user_emp_id)->first();
        $data['user_fullname'] = $staff['fullname'];
        $data['user_telephone'] = $staff['Tp'];

        $user = $userModel->where('emp_id', $user_emp_id)->first();
        $data['user_email'] = $user['email'];

        $supplierModel = new SupplierModel();
        $data['hotel'] = $supplierModel->where('sup_cat', 1)->orderBy('Name', 'ASC')->findAll();
        $data['hotel_location'] = $supplierModel->select('Address')->where('sup_cat', 1)->groupBy('Address')->orderBy('Address', 'ASC')->findAll();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $tour_id = $this->request->getGet('id');
        $start_date = $this->request->getGet('start');
        $end_date = $this->request->getGet('end');
        $type = $this->request->getGet('type');

        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        $TourQuotationsModel = new TourQuotationsModel();
        $tourConsigneeModel = new ConsigneeModel();

        $tour_condition = $tourConsigneeModel->select('transport_only, finalize_txt')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $tour_saved = $TourQuotationsModel->select('MAX(saved) AS save_flag')->where('quotation_id', $tour_id)->groupBy('quotation_id')->findAll();
        $data['tour_saved'] = $tour_saved;

        $tour_details = $TourQuotationsModel->select('tour_quotations.id, tour_quotations.quotation_id, MIN(tour_quotations.tour_date) as min_date, MAX(tour_quotations.tour_date) as max_date, tour_quotations.hotel_code, tour_quotations.hotel_acc, tour_quotations.from_location, tour_quotations.to_location, tour_quotations.sglcount, tour_quotations.dblcount, tour_quotations.tplcount, tour_quotations.sgl, tour_quotations.dbl, tour_quotations.tpl, tour_quotations.meal_code, tour_quotations.booking_confirmed_flag, tour_quotations.hotel_payment_flag, supplier.Name as hotel_name, room_type.rtype, transfer_milage.transfer_code as transfer_name, tour_quotations.total_hotel_doller, tour_quotations.pay_doller_count')
            ->where('tour_quotations.quotation_id', $tour_id)
            ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc')
            ->join('room_type', 'room_type.id = tour_quotations.room_type', 'left')
            ->join('transfer_milage', 'transfer_milage.id = tour_quotations.transfer_code', 'left')
            ->groupBy('tour_quotations.hotel_acc')
            ->orderBy('tour_quotations.tour_date')
            ->findAll();

        $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();

        $passengerDetailsModel = new PassengerDetailsModel();
        $passenger_details = $passengerDetailsModel->where('tour_id', $tour_id)->findAll();
        $data['passengers'] = $passenger_details;

        // i wanna loop tour_details result 
        foreach ($tour_details as $key => $value) {
            $hotel_acc = $value['hotel_acc'];

            $get_date_and_no = $TourQuotationsModel->select('tour_quotations.tour_date, tour_quotations.quotation_day')
                ->where('tour_quotations.hotel_acc', $hotel_acc)
                ->where('tour_quotations.quotation_id', $tour_id)
                ->groupBy('tour_quotations.hotel_acc')
                ->findAll();
        }

        log_message('critical', 'Tour Details: ' . print_r($tour_details, true));

        $data['tour_details'] =  $tour_details;

        $data['type'] = $type;
        $data['tour_main_details'] = $tour_main_details;

        return view('pages/tour/booking_details', $data);
    }

    // route : base_url/tour/show_details
    // Transport Management Page
    public function findDetails()
    {

        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $SupplierModel = new SupplierModel();
        $data['transport_agent'] = $SupplierModel->where('sup_cat', 2)->findAll();

        $supplierModel = new SupplierModel();
        $data['hotel'] = $supplierModel->where('sup_cat', 1)->orderBy('Name', 'ASC')->findAll();

        $data['hotel_location'] = $supplierModel->select('Address')->where('sup_cat', 1)->groupBy('Address')->orderBy('Address', 'ASC')->findAll();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $tour_id = $this->request->getGet('id');
        $start_date = $this->request->getGet('start');
        $end_date = $this->request->getGet('end');
        $type = $this->request->getGet('type');

        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        // $voucherpaymentModel = new VoucherPaymentModel();
        // $data['agents'] = $voucherpaymentModel->select('Acc_No,Pay')->where('ref_po', $tour_id)->where('advances_pay', 1)->groupStart()->where('Aflag', 0)->orWhere('Aflag', 1)->groupEnd()->findAll();
        $data['agents'] = [];

        $accountrunningModel = new AccountRunningModel();
        $tourConsigneeModel = new ConsigneeModel();

        $tour_condition = $tourConsigneeModel->select('hotel_only')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();
        $data['tour_id'] = $tour_id;
        $data['type'] = $type;
        $data['tour_main_details'] = $tour_main_details;

        return view('pages/tour/transport_details', $data);
    }

    // route : base_url/tour/booking_confirm/get_agent_vehicle
    // ransport agent Advance Payments History
    public function payment_history()
    {
        $agent = $this->request->getGet('agent');
        $tour_id = $this->request->getGet('tour_id');

        $voucherpaymentmodel  = new VoucherPaymentModel();
        $voucherpayment_accept = $voucherpaymentmodel->where('ref_po', $tour_id)->where('Acc_No', $agent)->where('Aflag', 1)->findAll();


        $tour_details = [];

        foreach ($voucherpayment_accept as $key => $value) {
            $recpt_no = $value['Vnum'];

            $accountrunningModel = new AccountRunningModel();
            $tour_details[$key] = $accountrunningModel->where('account_runing.index_no', $tour_id)
                ->where('account_runing.L_Code', $agent)
                ->where('account_runing.Discription LIKE', 'VOUCHER PAYMENT - Voucher Payment - Transport%')
                ->where('Dr >', 0.00)
                ->where('account_runing.Recpt_No', $recpt_no)
                ->groupStart()
                ->orWhere('account_runing.advances_pay', 1)
                // ->orWhere('account_runing.advances_pay', 11)
                ->groupEnd()
                ->findAll();

            // log_message('critical', $accountrunningModel->getLastQuery());
        }

        // $accountrunningModel = new AccountRunningModel();
        // $tour_details = $accountrunningModel->where('account_runing.index_no', $tour_id)
        //     ->where('account_runing.L_Code', $agent)
        //     ->where('account_runing.Discription LIKE', 'VOUCHER PAYMENT - Voucher Payment - Transport%')
        //     ->where('Dr >', 0.00)
        //     ->groupStart()
        //     ->orWhere('account_runing.advances_pay', 1)
        //     // ->orWhere('account_runing.advances_pay', 11)
        //     ->groupEnd()
        //     ->findAll();

        $voucherpaymentmodel  = new VoucherPaymentModel();
        $voucherpayment = $voucherpaymentmodel->where('ref_po', $tour_id)->where('Acc_No', $agent)->where('advances_pay', 1)->where('Aflag <>', 1)->findAll();

        $data['tour_details'] = $tour_details;
        $data['voucherpayment'] = $voucherpayment;

        return $this->response->setJSON(['status' => 200, 'data' => $data]);
    }

    public function get_t_agent_absolute_balance()
    {
        $tagent = $this->request->getGet('tagent');
        $accountRunningModel = new AccountRunningModel();
        $balance = $accountRunningModel->select('SUM(Dr) - SUM(Cr) AS balance')
            ->where('L_Code', $tagent)
            ->first()['balance'];

        return $this->response->setJSON(['status' => 200, 'data' => $balance]);
    }

    // route : base_url/tour/booking_confirm/tour_transport
    // Transport Advance Payment Add
    public function tour_transport()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $ref = $this->request->getPost('ref_no');
            $agent = $this->request->getPost('agent');
            $agent_name = $this->request->getPost('agent_name');
            $pay = $this->request->getPost('payment');
            $bank = $this->request->getPost('bank_acc');
            $bank_name = $this->request->getPost('bank_name');
            $tourname = $this->request->getPost('tour_name');
            $date = $this->request->getPost('advance_date');
            $manu_id = $this->request->getPost('manu_id');

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

            if ($ConsigneeModel->set($data)->where('tour_id', $tour)->update()) {

                if ($pay > 0) {
                    $invoiceNoModel = new InvoiceNumberModel();
                    $acc_id = $invoiceNoModel->incrementAccId();

                    $accountRunningModel = new AccountRunningModel();
                    // $accountRunningData = [
                    //     [
                    //         'ID' => $acc_id,
                    //         'L_Code' => '4-8-106-10385', // transport Cost Expenses Account
                    //         'Date' => date('Y-m-d'),
                    //         'Discription' => 'Transport Advance Payment ' . $tourname,
                    //         'Dr' => $pay,
                    //         'Cr' => 0.00,
                    //         'Invoice_Num' => $ref,
                    //         'Linkacc' => $agent,
                    //         'index_no' => $tour,
                    //         'advances_pay' => 1,
                    //         'fine_date' => date('Y-m-d'),
                    //         'Dt' => date('Y-m-d H:i:s'),
                    //     ],
                    //     [
                    //         'ID' => $acc_id,
                    //         'L_Code' => $agent, // Transport Agent
                    //         'Date' => date('Y-m-d'),
                    //         'Discription' => 'Transport Advance Payment ' . $tourname,
                    //         'Dr' => 0.00,
                    //         'Cr' => $pay,
                    //         'Invoice_Num' => $ref,
                    //         'Linkacc' => $agent,
                    //         'index_no' => $tour,
                    //         'advances_pay' => 1,
                    //         'fine_date' => date('Y-m-d'),
                    //         'Dt' => date('Y-m-d H:i:s'),
                    //     ]
                    // ];

                    // $accountRunningModel->insertBatch($accountRunningData);

                    //Voucher Authentification Record
                    $model_inv = new InvoiceNumberModel();
                    $inv = $model_inv->incrementVoucherToken();
                    $v_num = $inv;

                    $data = [
                        'Pay' => $agent_name,  //Transport Agent name 
                        'emp_code' => $agent,  //Transport Agent acc_no
                        'S_name' => $agent_name, //Transport Agent name 
                        'Acc_No' => $agent, //Transport Agent acc_no
                        'sup' => '1',
                        'type' => 'Bank Transfer', //BT
                        'Amount' => $pay,
                        'VAT' => '0',
                        'VAT_Amt' => '0',
                        'vat_m' => '0',
                        'nbt' => '0',
                        'nbt_amt' => '0',
                        'nbt_m' => '0',
                        'g_ttl' => $pay,
                        'Address' => '0',
                        'print' => '0',
                        'des' => '0',
                        'W_Date' => $date,
                        'dv' => '0',
                        'app_user' => session()->get('acc_no'),
                        'login' =>  session()->get('username'),
                        'Aflag' => '0',
                        'v_typ' => 'Bank Transfer',
                        'ref_po' => $tour,
                        'insflag' => 1,
                        'inv' => '0',
                        'Vnum' => $v_num,   // voucher token
                        'Bank' => $bank_name,
                        'Bank_acc' => $bank,
                        'C_Date' => date('Y-m-d'),
                        'C_Number' => $tour,
                        'acc_run_id' => $acc_id,
                        'is_expenses' => 1,
                        'advances_pay' => 1,
                        'doller_amt' => $pay
                    ];


                    $data_add = [
                        'Date' =>  $date,
                        'Invoice' => $tour,
                        'Description' => 'Transport Advance ' . $manu_id,
                        'Units' =>  0,
                        'Rate' => 0,
                        'Amount' => $pay,
                        'InvAmount' => $pay,
                        'V_tkn' => $v_num,   // voucher_token
                        'ityp' => '1',
                        'link_code' => $agent,
                        'employee' => $agent_name,
                        'login' =>  session()->get('username'),
                        'INV_Date' => date('Y-m-d'),
                        'Payee' => $agent_name,
                        'acc_run_id' => $acc_id,
                        'account' => $agent,
                    ];
                    // $this->db->insert("v_inv", $data_add);
                    $voucherpaymentmodel  = new VoucherPaymentModel();
                    $voucherdetails = $voucherpaymentmodel->where('ref_po', $tour)->where('advances_pay', 1)->findAll();

                    // log_message('critical', $voucherpaymentmodel->getLastQuery());

                    if (empty($voucherdetails)) {
                        $v_invmodel = new VoucherInvoiceModel();
                        $v_invmodel->insert($data_add);
                        $insertResult = $voucherpaymentmodel->insert($data);
                        $consigneeModel = new ConsigneeModel();
                        $consigneeModel->where('tour_id', $tour)->set(['transport_agent_name' => $agent_name, 'transport_agent' => $agent])->update();
                    } else {
                        $status = $voucherdetails[0]['Aflag'];
                        $transahent = $voucherdetails[0]['Acc_No'];

                        // if ($transahent == $agent) {
                        //     return $this->response->setJSON(["status" => 500, "message" => "You Can't Add This Advance!"]);
                        // } else {
                        //     $v_invmodel = new VoucherInvoiceModel();
                        //     $v_invmodel->insert($data_add);
                        //     $insertResult = $voucherpaymentmodel->insert($data);
                        // }

                        // if ($status == 1 || $status == 0) {
                        //     return $this->response->setJSON(["status" => 500, "message" => "You can't add this advance!"]);
                        // } else {
                        $v_invmodel = new VoucherInvoiceModel();
                        $v_invmodel->insert($data_add);
                        $insertResult = $voucherpaymentmodel->insert($data);
                        $consigneeModel = new ConsigneeModel();
                        $consigneeModel->where('tour_id', $tour)->set(['transport_agent_name' => $agent_name, 'transport_agent' => $agent])->update();
                        // }
                    }

                    // $v_invmodel = new VoucherInvoiceModel();
                    // $v_invmodel->insert($data_add);

                    // $insertResult = $voucherpaymentmodel->insert($data);
                }
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Added!"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tour/booking_confirm/add_expenses
    // Transport Expenses Add
    public function add_expenses()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            // $description = $this->request->getPost('description');
            $agent = $this->request->getPost('tagent');
            $agent_name = $this->request->getPost('agent_name');
            // $bank = $this->request->getPost('bank_acc');
            // $bank_name = $this->request->getPost('bank_name');
            $category = $this->request->getPost('category');
            $supplier = $this->request->getPost('supplier');
            $supplier_name = $this->request->getPost('supplier_name');
            // $payment_method = $this->request->getPost('pay_type');
            $inv = $this->request->getPost('inv');
            $date = $this->request->getPost('date');
            $dollerrate = $this->request->getPost('dollerrate');
            $amount = $this->request->getPost('amount');
            $rsamount = $this->request->getPost('rsamount');

            if ($supplier == null || $tour == null || $amount == null || $date == null || $inv == null || $rsamount == null || $agent == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($amount)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            // get tour
            $consigneeModel = new ConsigneeModel();
            $tourx = $consigneeModel->where('tour_id', $tour)->first();
            $current_t_agent = $tourx['transport_agent'];

            if ($current_t_agent != $agent) {
                $consigneeModel->where('tour_id', $tour)->set(['transport_agent' => $agent, 'transport_agent_name' => $agent_name])->update();
            }

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $accountRunningModel = new AccountRunningModel();

            if ($category == '6-9-23-10640' || $category == '6-9-23-10641') { // Entrance Fee Expenses, Other Expenses

                if ($category == '6-9-23-10640') {
                    $description = 'Entrance Fee Expenses';
                } else {
                    $description = 'Other Expenses';
                }

                $accountRunningData = [
                    [
                        'ID' => $acc_id,
                        'L_Code' => $supplier,
                        'Date' => $date,
                        'Discription' => $description,
                        'Dr' => $rsamount,
                        'Cr' => 0.00,
                        'Invoice_Num' => $inv,
                        'Linkacc' => $supplier,
                        'index_no' => $tour,
                        'dollarrate' => $dollerrate,
                        'dollar' => $amount,
                        'job' => $category,
                        'advances_pay' => 111,
                        'fine_date' => date('Y-m-d'),
                        'Cashier' => session()->get('username'),
                        'Dt' => date('Y-m-d H:i:s')
                    ],
                    [
                        'ID' => $acc_id,
                        'L_Code' => $agent,
                        'Date' => $date,
                        'Discription' => $description,
                        'Dr' => 0.00,
                        'Cr' => $rsamount,
                        'Invoice_Num' => $inv,
                        'Linkacc' => $supplier,
                        'index_no' => $tour,
                        'dollarrate' => $dollerrate,
                        'dollar' => $amount,
                        'job' => $category,
                        'advances_pay' => 111,
                        'fine_date' => date('Y-m-d'),
                        'Cashier' => session()->get('username'),
                        'Dt' => date('Y-m-d H:i:s')
                    ]
                ];

                $insertResult = $accountRunningModel->insertBatch($accountRunningData);
            }
            if ($insertResult) {
                return json_encode(['status' => 200, 'message' => 'Payment completed successfully']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Payment failed']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    // route : base_url/accounts/creditor_invoice/get_tour_expenses
    // Get Transport Expenses Details
    public function get_tour_expenses()
    {
        $tour = $this->request->getPost('tour_id');
        $agent = $this->request->getPost('agent');

        $accountRunningModel = new AccountRunningModel();
        $consigneeModel = new ConsigneeModel();

        if ($tour == null) {
            return json_encode(['status' => 400, 'message' => 'Please provide tour ID']);
        }

        if ($accountRunningModel->where('index_no', $tour)->countAllResults() > 0) {
            // Prepare the query
            $accountRunningModel->where('index_no', $tour);
        } else {
            $tr = $consigneeModel->select('tour_id')->where('manual_id', $tour)->first();
            // Use the retrieved tour_id in the query
            if ($tr) {
                $accountRunningModel->where('index_no', $tr['tour_id']);
            } else {
                return json_encode(['status' => 400, 'message' => 'No records found']);
            }
        }

        // Build and execute the query
        $details = $accountRunningModel->select('account_runing.*, consignee.consignee, consignee.agent_name, consignee.tour_id, supplier.Name, entrance_fee.name as sup_name, consignee.agent, consignee.bank, consignee.bank_name')
            ->join('consignee', 'consignee.tour_id = account_runing.index_no')
            ->join('supplier', 'supplier.Sup_Acc_No = account_runing.L_Code', 'left')
            ->join('entrance_fee', 'entrance_fee.l_code = account_runing.Linkacc', 'left')
            ->join('supplier as supplier_1', 'supplier_1.Sup_Acc_No = account_runing.Linkacc', 'left')
            ->where('account_runing.index_no', $tour)
            ->where('account_runing.L_Code', $agent)
            ->where('account_runing.Cr >', 0.00)
            ->notLike('account_runing.Discription', 'VOUCHER PAYMENT%')
            ->groupStart()
            ->orWhere('account_runing.advances_pay', 111)
            ->groupEnd()
            ->findAll();

        // log_message('critical', $accountRunningModel->getLastQuery());
        return json_encode(['status' => 200, 'data' => $details]);
    }

    // route : base_url/tour/booking_confirm/delete_expenses
    // Transport Expenses Delete
    public function delete_expenses()
    {
        $id = $this->request->getPost('id');
        $tour_id = $this->request->getPost('tour_id');
        $is_settled = $this->request->getPost('is_settled');

        $accountRunningModel = new AccountRunningModel();

        if ($is_settled == 1) {

            $record_id = $accountRunningModel->select('ID')->where('advances_pay', 11)->where('index_no', $tour_id)->where('Discription LIKE', 'Transport Payment Settled%')->first();
            $accrun_id = $record_id['ID'];

            $voucherpaymentmodel  = new VoucherPaymentModel();
            $voucherpayment = $voucherpaymentmodel->where('ref_po', $tour_id)->where('advances_pay', 11)->where('acc_run_id', $accrun_id)->findAll();
            $aflag = $voucherpayment[0]['Aflag'];

            if ($aflag == 1) {
                return json_encode(['status' => 400, 'message' => 'You can not delete this record because the payment has been settled']);
            }
        }
        // $record_count = $accountRunningModel->where('advances_pay', 11)->where('index_no', $tour_id)->where('Discription LIKE', 'Transport Payment Settled%')->countAllResults();

        // if ($record_count > 0) {
        //     return json_encode(['status' => 400, 'message' => 'You can not delete this record because the payment has been settled']);
        // }

        if ($id == null) {
            return json_encode(['status' => 400, 'message' => 'Please provide an ID']);
        }

        $deleteResult = $accountRunningModel->delete($id);

        if ($deleteResult) {
            return json_encode(['status' => 200, 'message' => 'Record deleted successfully']);
        } else {
            return json_encode(['status' => 400, 'message' => 'Record deletion failed']);
        }
    }


    // route : base_url/tour/booking_confirm/get_balance_payment_details
    // Get Transport Balance Payment Details
    public function get_balance_payment_details()
    {
        try {
            $tour_id = $this->request->getPost('tour_id');
            $agent = $this->request->getPost('agent');
            $agent_name = $this->request->getPost('agent_name');

            $accountrunningModel = new AccountRunningModel();
            $voucherpaymentmodel  = new VoucherPaymentModel();
            $logsheetmileModel = new LogSheetMileModel();
            $logsheetmile = $logsheetmileModel->where('tour_id', $tour_id)->first();

            $advance_payment = $voucherpaymentmodel->select('sum(Amount) as advance_dr')->where('ref_po', $tour_id)->where('Acc_No', $agent)->where('Aflag', 1)->where('advances_pay', 1)->first();


            $total_settled = $accountrunningModel->select('sum(account_runing.Cr) as ad_remain_cr')
                ->where('index_no', $tour_id)
                ->where('L_Code', $agent)
                ->where('account_runing.Cr >', 0.00)
                ->groupStart()
                ->Like('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->orLike('account_runing.Discription', 'JOURNAL ENTRY%')
                ->groupEnd()
                ->Where('account_runing.advances_pay', 11)
                ->first();

            // log_message('critical', $accountrunningModel->getLastQuery());

            $total_settled_voucher = $accountrunningModel->select('sum(account_runing.Cr) as total_pay_cr')
                ->where('index_no', $tour_id)
                ->where('payee', $agent_name)
                ->where('account_runing.Cr >', 0.00)
                // ->Like('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->groupStart()
                ->Like('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->orLike('account_runing.Discription', 'JOURNAL ENTRY%')
                ->groupEnd()
                ->Where('account_runing.advances_pay', 11)
                ->first();

            // log_message('critical', $accountrunningModel->getLastQuery());

            $total_expenses = $accountrunningModel->select('sum(account_runing.Cr) as expenses_cr')
                ->where('index_no', $tour_id)
                ->where('L_Code', $agent)
                ->where('account_runing.Cr >', 0.00)
                ->notLike('account_runing.Discription', 'VOUCHER PAYMENT%')
                ->Where('account_runing.advances_pay', 111)
                ->first();

            if ($logsheetmile == null) {

                $consigneeModel = new ConsigneeModel();
                $consignee = $consigneeModel->select('total_transport_cost, total_mileage, vehicle_rate, bata_day_rate, bata_days, log_sheet_mileage')->where('tour_id', $tour_id)->first();
            } else {

                $logsheetdetails = $logsheetmileModel->select('log_sheet_mileage,estimate_mileage,vehicle_rate,per_day_rate,no_of_dates as no_of_dates')->where('tour_id', $tour_id)->where('transport_agent', $agent)->orderby('id', 'DESC')->first();

                $consignee = [
                    'total_transport_cost' => $logsheetdetails['vehicle_rate'] * $logsheetdetails['log_sheet_mileage'] + ($logsheetdetails['per_day_rate'] * $logsheetdetails['no_of_dates']),
                    'total_mileage' => $logsheetdetails['log_sheet_mileage'],
                    'vehicle_rate' => $logsheetdetails['vehicle_rate'],
                    'bata_day_rate' => $logsheetdetails['per_day_rate'],
                    'bata_days' => $logsheetdetails['no_of_dates'],
                    'log_sheet_mileage' => $logsheetdetails['log_sheet_mileage'],
                    'estimate_mileage' => $logsheetdetails['estimate_mileage']
                ];
            }


            $voucherpaymentmodel  = new VoucherPaymentModel();
            $voucherpayment = $voucherpaymentmodel->where('ref_po', $tour_id)->where('advances_pay', 11)->where('Acc_No', $agent)->findAll();

            // merge the two arrays
            $data = array_merge($advance_payment, $total_expenses, $consignee, $total_settled, $total_settled_voucher);
            // log_message('critical', print_r($data, true));

            return $this->response->setJSON(['status' => 200, 'data' => $data, 'voucherpayment' => $voucherpayment]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/tour/booking_confirm/complete_payment
    // Transport Balance Payment Settle
    public function complete_payment()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $transagent = $this->request->getPost('transagent');
            $transagent_name = $this->request->getPost('transagent_name');
            $bank_account = $this->request->getPost('bank_account');
            $pay_metghod = $this->request->getPost('pay_metghod');
            $tot_trans_cost = $this->request->getPost('tot_trans_cost');
            $remaining_balance = $this->request->getPost('remaining_balance');
            $mamual_id = $this->request->getPost('mamual_id');
            // $remaining_balance = abs($remaining_balance);

            $amount = $this->request->getPost('cash');
            $pay_date = $this->request->getPost('pay_date');


            if ($transagent == null || $tour == null || $amount == null || $bank_account == null || $pay_date == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($amount)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            // get tour
            $consigneeModel = new ConsigneeModel();
            $tourx = $consigneeModel->where('tour_id', $tour)->first();
            $current_t_agent = $tourx['transport_agent'];

            if ($current_t_agent != $transagent) {
                $consigneeModel->where('tour_id', $tour)->set(['transport_agent' => $transagent, 'transport_agent_name' => $transagent_name])->update();
            }

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();

            $accountRunningModel = new AccountRunningModel();

            $accountRunningData = [
                [
                    'ID' => $acc_id,
                    'L_Code' => '4-8-106-10385', // transport Cost Expenses Account
                    'Dr' => $remaining_balance,
                    'Cr' => 0.00,
                    'Discription' => 'Transport Payment Settled ' . $mamual_id,
                    'Date' => $pay_date,
                    'status' => '',
                    'Cashier' => session()->get('username'),
                    'Invoice_Num' => '',
                    'index_no' => $tour, // customer id
                    'advances_pay' => 11,
                    'fine_date' => date('Y-m-d'),
                    'Dt' => date('Y-m-d H:i:s')
                ],
                [
                    'ID' => $acc_id,
                    'L_Code' => $transagent, // Transport Agent
                    //'L_Code' => $bank_account, // Bank Account
                    'Dr' => 0.00,
                    'Cr' => $remaining_balance,
                    'Discription' => 'Transport Payment Settled ' . $mamual_id,
                    'Date' => $pay_date,
                    'status' => '',
                    'Cashier' => session()->get('username'),
                    'Invoice_Num' => '',
                    'index_no' => $tour, // customer id
                    'advances_pay' => 11,
                    'fine_date' => date('Y-m-d'),
                    'Dt' => date('Y-m-d H:i:s')
                ]
            ];

            $insertResult = $accountRunningModel->insertBatch($accountRunningData);
            if ($insertResult) {

                //Voucher Authentification Record
                $model_inv = new InvoiceNumberModel();
                $inv = $model_inv->incrementVoucherToken();
                $v_num = $inv;

                $data = [
                    'Pay' => $transagent_name,  //Transport Agent name 
                    'emp_code' => $transagent,  //Transport Agent acc_no
                    'S_name' => $transagent_name, //Transport Agent name 
                    'Acc_No' => $transagent, //Transport Agent acc_no
                    'sup' => '1',
                    'type' => 'Bank Transfer', //BT
                    'Amount' => $amount,
                    'VAT' => '0',
                    'VAT_Amt' => '0',
                    'vat_m' => '0',
                    'nbt' => '0',
                    'nbt_amt' => '0',
                    'nbt_m' => '0',
                    'g_ttl' => $amount,
                    'Address' => '0',
                    'print' => '0',
                    'des' => '0',
                    'W_Date' => $pay_date,
                    'dv' => '0',
                    'app_user' => session()->get('acc_no'),
                    'login' =>  session()->get('username'),
                    'Aflag' => '0',
                    'v_typ' => 'Bank Transfer',
                    'ref_po' => $tour,
                    'insflag' => 1,
                    'inv' => '0',
                    'Vnum' => $v_num,   // voucher token
                    'Bank' => $pay_metghod,
                    'Bank_acc' => $bank_account,
                    'C_Date' => date('Y-m-d'),
                    'C_Number' => $tour,
                    'acc_run_id' => $acc_id,
                    'is_expenses' => 1,
                    'advances_pay' => 11,
                    'doller_amt' => $amount
                ];


                $data_add = [
                    'Date' => $pay_date,
                    'Invoice' => $tour,
                    'Description' => 'Transport Confimation ' . $mamual_id,
                    'Units' =>  0,
                    'Rate' => 0,
                    'Amount' => $amount,
                    'InvAmount' => $amount,
                    'V_tkn' => $v_num,   // voucher_token
                    'ityp' => '1',
                    'link_code' => $transagent,
                    'employee' => $transagent_name,
                    'login' =>  session()->get('username'),
                    'INV_Date' => date('Y-m-d'),
                    'Payee' => $transagent_name,
                    'acc_run_id' => $acc_id,
                    'account' => '4-8-106-10385',
                ];

                $voucherpaymentmodel  = new VoucherPaymentModel();
                $v_invmodel = new VoucherInvoiceModel();

                $v_invmodel->insert($data_add);
                $voucherpaymentmodel->insert($data);

                return json_encode(['status' => 200, 'message' => 'Payment completed successfully']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Payment failed']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    // route : base_url/tour/booking_confirm/edit_total_transport
    // Edit Total Transport Cost
    public function edit_total_transport()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $total_transport_cost = $this->request->getPost('total_transport_cost2');
            $total_mileage = $this->request->getPost('total_mile');
            $vehicle_rate = $this->request->getPost('vehicle_rate');
            $bata_day_rate = $this->request->getPost('per_dat_rate');
            $bata_days = $this->request->getPost('no_od_date');

            if ($tour == null || $total_transport_cost == null || $total_mileage == null || $vehicle_rate == null || $bata_day_rate == null || $bata_days == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($total_transport_cost) || !is_numeric($total_mileage) || !is_numeric($vehicle_rate) || !is_numeric($bata_day_rate) || !is_numeric($bata_days)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            $consigneeModel = new ConsigneeModel();
            $data = [
                'total_transport_cost' => $total_transport_cost
            ];

            $updateResult = $consigneeModel->where('tour_id', $tour)->set($data)->update();
            if ($updateResult) {
                return json_encode(['status' => 200, 'message' => 'Successfully updated']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Update failed']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    // route : base_url/tour/booking_confirm/add_logsheet_mileage
    // Add Logsheet Mileage
    public function add_logsheet_mileage()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $agent = $this->request->getPost('agent');
            $estimatedmileage = $this->request->getPost('estimatedmileage');
            $v_rate = $this->request->getPost('v_rate');
            $mileage = $this->request->getPost('totalactualmileage');
            $dara_rate = $this->request->getPost('dara_rate');
            $noofdate = $this->request->getPost('noofdate');
            $new_vehi_rate = $this->request->getPost('v_rate_new');


            if ($tour == null || $mileage == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($mileage)) {
                return json_encode(['status' => 400, 'message' => 'Mileage must be a number']);
            }

            $ConsigneeModel = new ConsigneeModel();
            $logsheetmileModel = new LogSheetMileModel();

            if ($new_vehi_rate != null && $new_vehi_rate != '') {
                $v_rate = $new_vehi_rate;
            }

            $updatedmile = (float)$mileage * (float)$v_rate + (float)$dara_rate * (float)$noofdate;

            $data = [
                'log_sheet_mileage' => $mileage,
                'total_transport_cost' => $updatedmile,
                'bata_day_rate' => $dara_rate,
                'vehicle_rate' => $v_rate,
            ];

            $data_logsheet = [
                'tour_id' => $tour,
                'transport_agent' => $agent,
                'log_sheet_mileage' => $mileage,
                'estimate_mileage' => $estimatedmileage,
                'vehicle_rate' => $v_rate,
                'per_day_rate' => $dara_rate,
                'no_of_dates' => $noofdate,
                'login' => session()->get('username'),
                'date' => date('Y-m-d H:i:s')
            ];

            $updateResult = $ConsigneeModel->where('tour_id', $tour)->set($data)->update();

            if ($updateResult) {
                $xx = $logsheetmileModel->insert($data_logsheet, true);
                // log_message('critical', $xx);
                return json_encode(['status' => 200, 'message' => 'Successfully added']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Addition failed']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function get_totday_log_sheet()
    {
        $tour = $this->request->getPost('tour_id');
        $agent = $this->request->getPost('agent');

        $logsheetmileModel = new LogSheetMileModel();
        $result = $logsheetmileModel->select('no_of_dates')
            ->where('tour_id', $tour)
            ->where('transport_agent', $agent)
            ->orderBy('id', 'desc')
            ->first();

        return $this->response->setJSON([
            'status' => 200,
            'total_days' => $result['no_of_dates'] ?? 0
        ]);
    }

    public function get_logsheet_mileage($tour_id, $agent)
    {
        $logsheetmileModel = new LogSheetMileModel();
        $logsheetmile = $logsheetmileModel->where('tour_id', $tour_id)->where('transport_agent', $agent)->orderBy('id', 'desc')->first();
        return json_encode($logsheetmile);
    }


    public function getAllDetails($tour, $date, $end_date = '', $hotel, $tdaymin, $tdaymax)
    {
        try {

            // log_message('critical', $tour . ' ' . $date . ' ' . $end_date);
            $dates = [];
            // if date and end_date is not equal get dates between the two dates
            if ($date != $end_date) {
                $start_date = new DateTime($date);
                $end_date = new DateTime($end_date);
                // get all dates between the two dates
                $interval = new DateInterval('P1D');
                $daterange = new DatePeriod($start_date, $interval, $end_date);

                foreach ($daterange as $date) {
                    $dates[] = $date->format("Y-m-d");
                }
                // append end date to the dates array
                $dates[] = $end_date->format("Y-m-d");

                // log_message('critical', implode(', ', $dates));
            } else {
                $dates[] = $date;
            }

            /**
                            $('#slct_hotel').text(response.data.quotationDetails.hotel_name);
                            $('#slct_hotel_name').val(response.data.quotationDetails.hotel_name);
                            $('#slct_hotel_id').val(response.data.quotationDetails.hotel_acc);
                            $('#slct_room_type').text(response.data.quotationDetails.room);
                            $('#slct_room_type_id').val(response.data.quotationDetails.room_type);
                            $('#room_type_id').val(response.data.quotationDetails.room_type);
                            $("#meal_code").val(response.data.quotationDetails.meal_code);
                            $("#sgl").val(response.data.quotationDetails.sgl);
                            $("#dbl").val(response.data.quotationDetails.dbl);
                            $("#tpl").val(response.data.quotationDetails.tpl);
                            $("#sglcount").val(response.data.quotationDetails.r_sgl);
                            $("#dblcount").val(response.data.quotationDetails.r_dbl);
                            $("#tplcount").val(response.data.quotationDetails.r_tpl);
             */

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

            $TourQuotationsModel = new TourQuotationsModel();
            // $quotationDetails = $TourQuotationsModel->select('tour_quotations.*, room_type.rtype as room, supplier.Name as hotel_name, consignee.sgl as r_sgl, consignee.dbl as r_dbl, consignee.tpl as r_tpl, consignee.start_date, consignee.end_date')
            //     ->join('room_type', 'room_type.id = tour_quotations.room_type', 'left')
            //     ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc', 'left')
            //     ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'left')
            //     ->where('tour_quotations.quotation_id', $tour)
            //     ->where('tour_quotations.tour_date', $date)->first();
            // log_message('critical', json_encode($dates));

            foreach ($dates as $date) {
                $quotationDetails = $TourQuotationsModel->select('tour_quotations.*, room_type.rtype as room, supplier.Name as hotel_name, consignee.sgl as r_sgl, consignee.dbl as r_dbl, consignee.tpl as r_tpl, consignee.start_date, consignee.end_date, consignee.finalize_txt')
                    ->join('room_type', 'room_type.id = tour_quotations.room_type', 'left')
                    ->join('supplier', 'supplier.Sup_Acc_No = tour_quotations.hotel_acc', 'left')
                    ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'left')
                    ->where('tour_quotations.quotation_id', $tour)
                    ->where('tour_quotations.tour_date', $date)
                    ->where('tour_quotations.hotel_acc', $hotel)
                    // ->where('tour_quotations.quotation_day >=', $tdaymin)
                    // ->where('tour_quotations.quotation_day <=', $tdaymax)
                    ->orderBy('tour_quotations.quotation_day')
                    ->first();
                // }

                // log_message('critical', $TourQuotationsModel->getLastQuery());

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
                    $day_num = $quotationDetails['quotation_day'];

                    // $finalize_txt = $quotationDetails['finalize_txt'];
                }
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
                    // 'finalize_txt' => $finalize_txt
                    'day_num' => $day_num,
                ],
            ];

            // var_dump($data['quotationDetails']); die();

            // Return the data
            return $this->response->setJSON(['status' => 200, 'data' => $data]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
