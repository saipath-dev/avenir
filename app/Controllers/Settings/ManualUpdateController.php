<?php

namespace App\Controllers\Settings;

use App\Controllers\BaseController;
use App\Models\GroupProfileModel;
use App\Models\UserModel;

use App\Libraries\MenuLoader;
use App\Models\AccountRunningModel;
use App\Models\VoucherPaymentModel;
use App\Models\VoucherInvoiceModel;
use App\Models\InvoiceNumberModel;
use App\Models\TourQuotationsModel;
use App\Models\ConsigneeModel;
use App\Models\DebtorReceiptPaymentModel;

class ManualUpdateController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/settings/group_profiles
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $model = new GroupProfileModel();
        $data['profiles'] = $model->select('*')->where('del', 0)->orderBy('id', 'ASC')->findAll();

        return view('pages/settings/manual_update', $data);
    }

    // create new profile - route : base_url/settings/group_profiles/create
    // public function update()
    // {
    //     try {
    //         $model = new VoucherPaymentModel();
    //         $data = $model->findAll();

    //         foreach ($data as $key => $value) {
    //             $acc_id = $value['acc_run_id'];
    //             $acc_no = $value['Acc_No'];

    //             // get every thing before the last dash in the $acc_no
    //             $acc_no = substr($acc_no, 0, strrpos($acc_no, '-'));

    //             $v_nummodel = new VoucherInvoiceModel();
    //             $v_num_record = $v_nummodel->where('acc_run_id', $acc_id)->first();

    //             $discription = $v_num_record['Description'];

    //             if($acc_no == '4-8-109'){
    //                 $discription = str_replace('Hotel Reservation', 'Other Expenses', $v_num_record['Description']);
    //             }

    //             //Voucher Authentification Record
    //             $model_inv = new InvoiceNumberModel();
    //             $inv = $model_inv->incrementVoucherToken();
    //             $v_num = $inv;

    //             $voucher_data = [
    //                 'Vnum' => $v_num
    //             ];

    //             $voucher_inv_data = [
    //                 'V_tkn' => $v_num,
    //                 'Description' => $discription
    //             ];

    //             $v_nummodel->set($voucher_inv_data)->where('acc_run_id', $acc_id)->update();
    //             $model->set($voucher_data)->where('acc_run_id', $acc_id)->update();
    //         }

    //         // return json_encode(['status' => 200, 'message' => 'Successfully']);
    //         return $this->response->setJSON(['status' => 200, 'message' => 'Successfully']);

    //     } catch (\Exception $e) {
    //         // Handle the exception here
    //         return $e->getMessage();
    //     }
    // }

    public function des_update()
    {
        try {
            $v_nummodel = new VoucherInvoiceModel();
            $data = $v_nummodel->where('Description LIKE', 'Hotel Reservation%')->findAll();
            // log_message('critical', $v_nummodel->getLastQuery());


            foreach ($data as $key => $value) {
                $acc_id = $value['acc_run_id'];
                // $acc_no = $value['Acc_No'];
                // continue;

                $accountrunningmodel = new AccountRunningModel();
                $acc_record = $accountrunningmodel->where('ID', $acc_id)->where('Dr >', 0.00)->first();

                if ($acc_record == null) {
                    continue;
                }

                $lcode = $acc_record['L_Code'];

                // log_message('critical', $acc_id);
                if ($lcode == '4-8-106-10385') {
                    $discription = str_replace('Hotel Reservation', 'Transport Expenses', $value['Description']);
                    $voucher_inv_data = [
                        'Description' => $discription
                    ];
                    $v_nummodel->set($voucher_inv_data)->where('acc_run_id', $acc_id)->update();
                }
            }

            // return json_encode(['status' => 200, 'message' => 'Successfully']);
            return $this->response->setJSON(['status' => 200, 'message' => 'Successfully']);
        } catch (\Exception $e) {
            // Handle the exception here
            return $e->getMessage();
        }
    }

    public function des_other_update()
    {
        try {
            $v_nummodel = new VoucherInvoiceModel();
            $data = $v_nummodel->where('Description LIKE', 'Other Expenses%')->findAll();
            // log_message('critical', $v_nummodel->getLastQuery());


            foreach ($data as $key => $value) {
                $acc_id = $value['acc_run_id'];
                // $acc_no = $value['Acc_No'];

                $accountrunningmodel = new AccountRunningModel();
                $acc_record = $accountrunningmodel->where('ID', $acc_id)->where('Cr >', 0.00)->first();

                if ($acc_record == null) {
                    continue;
                }

                $lcode = $acc_record['L_Code'];
                // get every thing before the last dash in the $acc_no
                // $lcode = substr($lcode, 0, strrpos($lcode, '-'));
                if ($lcode == '6-9-23-10640') {
                    $discription = str_replace('Other Expenses', 'Entrance Fee', $value['Description']);
                    $voucher_inv_data = [
                        'Description' => $discription
                    ];
                    $v_nummodel->set($voucher_inv_data)->where('acc_run_id', $acc_id)->update();
                }
            }

            // return json_encode(['status' => 200, 'message' => 'Successfully']);
            return $this->response->setJSON(['status' => 200, 'message' => 'Successfully']);
        } catch (\Exception $e) {
            // Handle the exception here
            return $e->getMessage();
        }
    }

    public function hotel_voucher_accept()
    {
        // tf-payguard-r1: this bulk route authorised every pending hotel-reservation voucher at once, posting a voucher with
        // lines on k tours k times, with no status check and no lock. No screen calls it (its button is commented out).
        // It is withdrawn: every voucher is authorised one at a time through Voucher Print, Bank Payment Check or the
        // Tour Payment Desk, where the authorisation guard applies.
        try {
            \App\Libraries\TourPayments\Audit::log(\Config\Database::connect(), [
                'action' => 'auth_refused', 'ref_type' => 'route', 'ref_id' => 'settings/manual_update/hotel_voucher_accept',
                'prev' => 'V4 bulk hotel voucher accept', 'reason' => \App\Libraries\PayGuard\AuthGuard::VERSION . ': route withdrawn; nothing posted',
            ]);
        } catch (\Throwable $e) {
        }
        return $this->response->setJSON(['status' => 403, 'message' => 'Bulk hotel-voucher acceptance is withdrawn by the payment guard. Authorise each voucher through Voucher Print, Bank Payment Check or the Tour Payment Desk. Nothing was posted.']);
    }

    /** tf-payguard-r1: the former body of hotel_voucher_accept(), kept unrouted for reference; not callable from any URL. */
    private function hotel_voucher_accept_withdrawn()
    {
        try {
            $v_nummodel = new VoucherInvoiceModel();
            $vouchermodel = new VoucherPaymentModel();
            // $data = $v_nummodel->where('Description LIKE', 'Hotel Reservation%')->findAll();
            $data = $vouchermodel->load_voucher_details_manual();

            // log_message('critical', $v_nummodel->getLastQuery());

            foreach ($data as $key => $value) {
                // log_message('critical', $vouchermodelgetLastQuery());
                $status = 1;
                $bank_acc = $value['Bank_acc'];
                $pay_type = $value['Pay_Type'];
                $amount = $value['Amount'];
                $sup = $value['sup'];
                $payee = $value['Pay'];
                $id = $value['id'];
                // $payee = $this->request->getVar('payee');


                $model_inv = new InvoiceNumberModel();
                $voucher_no = $model_inv->incrementVoucher();
                $voucher_no = $voucher_no;

                if ($sup == 2) {

                    if (($pay_type == 'BT') || ($pay_type == 'Bank Transfer')) {
                        $cr = $bank_acc;
                        // $cr = '1-6-8-9750';
                        // $chk = $voucher->C_Number;
                    } else if ($pay_type == 'Cash') {
                        $cr = '1-2-3-1000';
                        // $chk = '';
                    } else if ($pay_type == 'Petty Cash') {
                        $cr = '1-2-4-1000';
                        // $chk = '';
                    } else if ($pay_type == 'Cheque') {
                        $cr = '1-6-8-9352';
                    }


                    $voucher_details = $vouchermodel->where('ID', $id)->first();


                    $this->acc_array(
                        '1-6-8-9750',
                        $cr,
                        $amount,
                        'VOUCHER PAYMENT',
                        '0',
                        '0',
                        $voucher_details['W_Date'],
                        '',
                        $voucher_details['ref_acc_no'],
                        '3',
                        '0',
                        $voucher_details['ref_acc_no'],
                        $voucher_details['W_Date'],
                        '0',
                        $voucher_details['Vnum'],
                        $payee
                    );
                }

                if ($status == 1) {
                    // Retrieve voucher details
                    $vouchers = $vouchermodel->voucher($id);

                    // Check if $vouchers is an array or collection
                    // if (is_array($vouchers) || $vouchers instanceof Traversable) {
                    foreach ($vouchers as $voucher) {


                        $type = $voucher->type;
                        $V_tkn = isset($voucher->V_tkn) ? $voucher->V_tkn : '';

                        if (($type == 'BT') || ($type == 'Bank Transfer')) {

                            if ($sup == 1) {
                                $cr = $voucher->Bank_acc;
                            } else {
                                $cr = '1-6-8-9750';
                            }
                            // $cr = $voucher->Bank_acc;

                            $chk = $voucher->C_Number;
                        } else if ($type == 'Cash') {
                            if ($sup == 1) {
                                $cr = '1-2-3-1000';
                            } else {
                                $cr = '1-6-8-9750';
                            }
                            $chk = '';
                            $chk = $voucher->C_Number;
                        } else if ($type == 'Petty Cash') {
                            if ($sup == 1) {
                                $cr = '1-2-4-1000';
                            } else {
                                $cr = '1-6-8-9750';
                            }
                            $chk = '';
                            $chk = $voucher->C_Number;
                        } else if ($type == 'Cheque') {
                            if ($sup == 1) {
                                $cr = '1-6-8-9352';
                            } else {
                                $cr = '1-6-8-9750';
                            }
                        }

                        $this->acc_array(
                            $voucher->account, // dr
                            $cr,
                            $voucher->sup_amount,
                            'Voucher Payment - ' . $voucher->Description,
                            '0',
                            '0',
                            $voucher->W_Date,
                            $chk,
                            $voucher->ref_acc_no,
                            '3',
                            '0',
                            $voucher->ref_acc_no,
                            $voucher->W_Date,
                            '0',
                            $voucher->Vnum,
                            $payee
                        );

                        // Update voucher status
                        $data = [
                            'Aflag' => $status,
                            'Aut_user' => session()->get('username'),
                            'aut_date' => date('Y-m-d')
                        ];

                        $where = ['ID' => $voucher->ID];

                        if (!$vouchermodel->update($where, $data)) {
                            // If any update fails, return an error response
                            return $this->response->setJSON([
                                "status" => 400,
                                "message" => "Insert Error!"
                            ]);
                        }
                    }


                    $data = [
                        'Aflag' => $status,
                        'Voucher_No' => $voucher_no
                    ];
                    $where = ['ID' => $id];


                    $vouchermodel->update($where, $data);


                    // If all updates succeed, return a success response
                    // return $this->response->setJSON([
                    //     "status" => 200,
                    //     "message" => "Invoice saved successfully!",
                    //     "v_inv" => $V_tkn
                    // ]);

                    //   } else {
                    // // Handle case where $vouchers is not an array or Traversable
                    //     return $this->response->setJSON([
                    //       "status" => 400, 
                    //       "message" => "Invalid voucher data!"
                    //     ]);
                    //   }

                } else if ($status == 2) {

                    $data = ['Aflag' => $status, 'rej_user' => session()->get('username'), 'aut_date' => date('Y-m-d')];

                    $where = ['ID' => $id];
                    // Delete voucher
                    $vouchermodel->update($where, $data);
                }
            }
            return json_encode(['status' => 200, 'message' => 'Successfully']);
        } catch (\Exception $e) {
            // Handle the exception here
            return $e->getMessage();
        }
    }

    public function acc_array($Dr, $Cr, $amoun, $des, $st, $inv, $date, $chk_no, $link_acc, $aflag, $job, $index_no, $dt, $card_no, $rect_no, $payee)
    {

        // require_once(APPPATH . 'libraries/Cashier_session.php');
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $Cashier = strip_tags(session()->get('username'));
        $shift_id = "";
        $acc_no = "";
        $divID = 1;
        $store = "";
        $today = date('Y-m-d');



        $invoiceNoModel = new InvoiceNumberModel();
        $acc_id = $invoiceNoModel->incrementAccId();
        // $user = strip_tags(session()->get('emp_id'));

        $acc = array(
            array(
                'ID' => $acc_id,
                'Invoice_Num' => $inv,
                'L_Code' => $Dr,
                'Dr' => $amoun,
                'Cr' => '0',
                'Discription' => 'VOUCHER PAYMENT - ' . $des,
                'Date' => $date,
                'status' => $st,
                'Cashier' => $Cashier,
                'shift_id' => $shift_id,
                'Chk_No' => $chk_no,
                'DV' => $divID,
                'Linkacc' => $link_acc,
                'aflag' => $aflag,
                'job' => $job,
                'index_no' => $chk_no,
                'P_Cap' => '',
                'P_Month' => $date,
                'Tax' => '',
                'srvc_no' => '',
                'Dt' => $dt . ' ' . date("H:i:s"),
                'Fee' => '',
                'Effective_Date' => '',
                'Card_No' => $card_no,
                'Recpt_No' => $rect_no,
                'New_L_Code' => $payee
            ),
            array(
                'ID' => $acc_id,
                'Invoice_Num' => $inv,
                'L_Code' => $Cr,
                'Dr' => 0,
                'Cr' => $amoun,
                'Discription' => 'VOUCHER PAYMENT - ' . $des,
                'Date' => $date,
                'status' => $st,
                'Cashier' => $Cashier,
                'shift_id' => $shift_id,
                'Chk_No' => $chk_no,
                'DV' => $divID,
                'Linkacc' => $link_acc,
                'aflag' => $aflag,
                'job' => $job,
                'index_no' => $chk_no,
                'P_Cap' => '',
                'P_Month' => $date,
                'Tax' => '',
                'srvc_no' => '',
                'Dt' => $dt . ' ' . date("H:i:s"),
                'Fee' => '',
                'Effective_Date' => '',
                'Card_No' => $card_no,
                'Recpt_No' => $rect_no,
                'New_L_Code' => $payee
            )
        );

        // $output = $this->Payment_model->accountFinish($acc);
        $accrunningmodel = new AccountRunningModel();


        if ($accrunningmodel->insertBatch($acc)) {
            return "success";
        } else {
            return "error";
        }
    }

    public function entrance_update()
    {
        try {
            $v_nummodel = new VoucherInvoiceModel();
            $data = $v_nummodel->where('Description LIKE', 'Entrance Fee%')->findAll();
            // log_message('critical', $v_nummodel->getLastQuery());


            foreach ($data as $key => $value) {
                $acc_id = $value['acc_run_id'];
                $voucher_num = $value['V_tkn'];
                // $acc_no = $value['Acc_No'];
                $v_nummodel->set('account', '6-9-23-10640')->where('V_tkn', $voucher_num)->update();
                $vouchermodel = new VoucherPaymentModel();
                $vouchermodel->set('Bank_acc', '3-15-33-10749')->where('Vnum', $voucher_num)->update();
            }

            // return json_encode(['status' => 200, 'message' => 'Successfully']);
            return $this->response->setJSON(['status' => 200, 'message' => 'Successfully']);
        } catch (\Exception $e) {
            // Handle the exception here
            return $e->getMessage();
        }
    }


    public function other_update()
    {
        try {
            $v_nummodel = new VoucherInvoiceModel();
            $data = $v_nummodel->where('Description LIKE', 'Other Expenses%')->findAll();
            // log_message('critical', $v_nummodel->getLastQuery());


            foreach ($data as $key => $value) {
                $acc_id = $value['acc_run_id'];
                $voucher_num = $value['V_tkn'];
                // $acc_no = $value['Acc_No'];
                $v_nummodel->set('account', '6-9-23-10641')->where('V_tkn', $voucher_num)->update();
                $vouchermodel = new VoucherPaymentModel();
                $vouchermodel->set('Bank_acc', '3-15-33-10749')->where('Vnum', $voucher_num)->update();
            }

            // return json_encode(['status' => 200, 'message' => 'Successfully']);
            return $this->response->setJSON(['status' => 200, 'message' => 'Successfully']);
        } catch (\Exception $e) {
            // Handle the exception here
            return $e->getMessage();
        }
    }


    public function transport_update()
    {
        try {
            $v_nummodel = new VoucherInvoiceModel();
            $data = $v_nummodel->where('Description LIKE', 'Transport Expenses%')->findAll();
            // log_message('critical', $v_nummodel->getLastQuery());


            foreach ($data as $key => $value) {
                $acc_id = $value['acc_run_id'];
                $voucher_num = $value['V_tkn'];
                // $acc_no = $value['Acc_No'];
                // $v_nummodel->set('account' , '6-9-23-10641')->where('V_tkn', $voucher_num)->update();
                $vouchermodel = new VoucherPaymentModel();
                $vouchermodel->set('Bank_acc', '3-15-33-10749')->where('Vnum', $voucher_num)->update();
            }

            // return json_encode(['status' => 200, 'message' => 'Successfully']);
            return $this->response->setJSON(['status' => 200, 'message' => 'Successfully']);
        } catch (\Exception $e) {
            // Handle the exception here
            return $e->getMessage();
        }
    }

    public function voucher_date_update()
    {
        $v_nummodel = new VoucherPaymentModel();
        // $data = $v_nummodel->where('Aflag', 1)->findAll();
        $data = $v_nummodel->findAll();

        // log_message('critical', $v_nummodel->getLastQuery());

        foreach ($data as $key => $value) {
            $acc_id = $value['acc_run_id'];
            $voucher_num = $value['Vnum'];

            if ($acc_id == null) {
                continue;
            }

            $accountrunningmodel = new AccountRunningModel();
            $acc_record = $accountrunningmodel->where('ID', $acc_id)->first();

            if ($acc_record == null) {
                continue;
            }

            // log_message('critical', $accountrunningmodel->getLastQuery());

            $date = $acc_record['Date'];

            // log_message('critical', $date);

            $v_nummodel->set('W_Date', $date)->where('Vnum', $voucher_num)->update();

            $accountrunningmodel->set('Date', $date)->where('Recpt_No', $voucher_num)->update();
        }
        return $this->response->setJSON(['status' => 200, 'message' => 'Successfully']);
    }

    public function verify_tour()
    {
        try {
            $tourquotesmodel = new TourQuotationsModel();
            $consignmentmodel = new ConsigneeModel();
            $data = $tourquotesmodel->where('confirm', 1)->where('finalize', 1)->where('verify', 0)->groupBy('quotation_id')->findAll();
            // log_message('critical', $tourquotesmodel->getLastQuery());

            foreach ($data as $key => $value) {
                $quotation_id = $value['quotation_id'];
                $tourquotesmodel->set('verify', 1)->where('quotation_id', $quotation_id)->update();
                $consignmentmodel->set('verify', 1)->where('tour_id', $quotation_id)->update();
            }

            return $this->response->setJSON(['status' => 200, 'message' => 'Successfully']);
        } catch (\Exception $e) {
            // Handle the exception here
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function debtor_receipt_generate()
    {
        $accrunningmodel = new AccountRunningModel();

        $debtor_receipt_model = new DebtorReceiptPaymentModel();

        $debtor_pay_details = $accrunningmodel->select('sum(Dr) as Dr,Invoice_Num,index_no')
            // ->where('P_Month', '2025-06-05')
            // ->where('P_Month', '2025-06-12')
            ->where('P_Month', '2025-06-30')
            // ->where('P_Month', '2025-07-02')
            ->where('Invoice_Num LIKE', 'AH%')
            ->groupBy('Invoice_Num')
            ->findAll();

        // loop $debtor_pay_details 
        foreach ($debtor_pay_details as $key => $value) {
            $inv = $value['Invoice_Num'];
            $tour_id = $value['index_no'];
            $dr_amount = $value['Dr'];

            $invoiceNoModel = new InvoiceNumberModel();
            $debtor_receiptNo = $invoiceNoModel->incrementDebtorReceiptNo();

            $insertdata = [
                'receipt_no' => $debtor_receiptNo,
                'customer_id' => '1-2-5-10635', // hardcoded customer id
                // 'payment_date' => '2024-04-18',
                // 'payment_date' => '2024-05-27',
                'payment_date' => '2024-06-25',
                // 'payment_date' => '2025-07-02',
                'cash_amount' => 0.00,
                'bank_amount' => $dr_amount,
                'card_amount' => 0.00,
                'slip_no' => $inv,
                'booking_no' => $tour_id,
                'customer_name' => '',
                'payment_method' => '',
                'created_by' => session()->get('username'),
                'Date' => date('Y-m-d H:i:s')
            ];

            $existing_receipt = $debtor_receipt_model->where('receipt_no', $inv)->first();
            if ($existing_receipt) {

                $debtor_receipt_model->set($insertdata)->where('receipt_no', $inv)->update();
                // return $this->response->setJSON(['status' => 200, 'message' => 'Successfully Updated']);
            } else {

                $debtor_receipt_model->insert($insertdata);
                // return $this->response->setJSON(['status' => 200, 'message' => 'Successfully']);
            }
        }

        return $this->response->setJSON(['status' => 200, 'message' => 'Successfully']);
    }

    public function bank_recon_record_insert()
    {
        // tf-legacy-retire-r1 (12 Oct 2026): retired. Refused before anything else runs: the security token first (403), then
        // the retirement (409); every attempt is audited. The original code below is kept for the record and is never reached.
        [$code, $message] = \App\Libraries\BankRec\LegacyGuard::refuseRetiredTool('bank_recon_record_insert');
        return $this->response->setStatusCode($code)->setJSON(['status' => $code, 'message' => $message, 'retired' => true, 'csrf' => csrf_hash()]);

        try {
            $record = [
                '12340',
                '12341',
                '7054','7055','7056','7057','7058','7059','7208','7209','7210','7211','7212','7213','7214','7215','7216','7217','7218','7219','7220','7221','7222','7223','7224','7227','7228','7229','7230','7231','7232','7233','7234','7235','7236','7237','7238','7240','7241','7242','7243','7244','7245','7246','7247','11719','11600','11601','11602','11603','11604','11605','11606','11607','11608','11609','11610','11611','12112','11720','11487','11636','11637','11638','11639','11640','11641','11642','11643','10595','10596','12111','12304','5334','12336','2031','2245','3219','3221','3559','3567','3610','3612','3614','3616','3619','3621','3628','3630','3632','3634','3636','3638','3640','3642','3645','3651','3661','3698','3702','3719','3720','3722','3724','3727','3729','3732','3733','3734','3751','4537','4538','4842','4843','4846','4877','4884','4902','4904','5061','5075','5076','5079','5117','5221','5257','5260','5262','5264','5266','5267','5292','5343','5350','5367','5369','5371','5372','5373','5375','5382','5388','5390','5392','5394','5420','5422','5466','5486','5488','5541','5544','5589','5604','5658','5660','5833','5834','5846','5897','5898','5900','5918','5921','6060','6070','6073','6112','6115','6117','6232','6234','6242','6256','6257','6259','6262','6264','6266','6268','6271','6278','6282','6474','6476','6478','6480','6482','6738','6740','6841','6844','6847','7061','7062','7064','12227','12228','12229','12230','12231','11721','11714','11707','11737','12132','12131','12130','12129','12128','12127','12126','12125','12124','12123','12122','12121','12112','12097','12096','12091','12077','12071','12070','12059','12058','12047','12046','12041','12040','12025','12024','12004','12003','11983','11982','11973','11972','11971','11957','11936','11935','11925','11924','11887','11886','11854','11853','11850','11849','11848','11847','11843','10955','11838','11836','11835','11834','11833','11832','11831','11830','11829','11828','11827','11826','11825','11824','11823','11822','11811','11802','11786','11777','11770','11760','11085','11086','11087','11740','11358','11653','11657',
                '11658',
                '11659',
                '11660'
            ];

            // Connect to another database
            $secondDB = \Config\Database::connect('secondDatabase');

            foreach ($record as $key => $value) {
                $accountrunningmodel = new AccountRunningModel($secondDB);
                $old_rec = $accountrunningmodel->where('ID', $value)->findAll();

                // log_message('critical', $accountrunningmodel->getLastQuery());

                $invoiceNoModel = new InvoiceNumberModel();
                $acc_id = $invoiceNoModel->incrementAccId();

                foreach ($old_rec as $key2 => $value2) {

                    $insertdata = [
                        'ID' => $acc_id,
                        'Invoice_Num' => $value2['Invoice_Num'],
                        'L_Code' => $value2['L_Code'],
                        'Dr' => $value2['Dr'],
                        'Cr' => $value2['Cr'],
                        'Discription' => $value2['Discription'],
                        'Date' => $value2['Date'],
                        'status' => $value2['status'],
                        'Cashier' => $value2['Cashier'],
                        'shift_id' => $value2['shift_id'],
                        'Chk_No' => $value2['Chk_No'],
                        'DV' => $value2['DV'],
                        'Linkacc' => $value2['Linkacc'],
                        'aflag' => $value2['aflag'],
                        'job' => $value2['job'],
                        'index_no' => $value2['index_no'],
                        'P_Cap' => $value2['P_Cap'],
                        'P_Month' => $value2['P_Month'],
                        'Tax' => $value2['Tax'],
                        'srvc_no' => $value2['srvc_no'],
                        'Dt' => $value2['Dt'],
                        'Fee' => $value2['Fee'],
                        'Effective_Date' => $value2['Effective_Date'],
                        'Card_No' => $value2['Card_No'],
                        'Recpt_No' => $value2['Recpt_No'],
                        'New_L_Code' => $value2['New_L_Code']
                    ];

                    $accountrunningdefamodel = new AccountRunningModel();
                    // log_message('critical', print_r($insertdata, true));
                    $accountrunningdefamodel->insert($insertdata);
                    // $acc_id++;
                }
            }

            return $this->response->setJSON(['status' => 200, 'message' => 'Successfully']);
        } catch (\Exception $e) {
            // Handle the exception here
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
