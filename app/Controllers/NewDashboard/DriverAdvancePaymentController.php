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
use App\Models\DriverAdvanceRequestModel;

class DriverAdvancePaymentController extends BaseController
{

    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('tour_id');
        $driver_id = $this->request->getGet('driver_id');
        $amount = $this->request->getGet('amount');

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
        $data['driver_id'] = $driver_id;
        $data['amount'] = $amount;
        $data['tour_main_details'] = $tour_main_details;

        return view('pages/dashboard2/transport/transport_advance', $data);
    }

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

            // Get all entries (both Dr and Cr) for this voucher and driver
            $entries = $accountrunningModel->where('account_runing.index_no', $tour_id)
                ->where('account_runing.L_Code', $agent)
                ->where('account_runing.Discription LIKE', 'VOUCHER PAYMENT - Voucher Payment - Transport%')
                ->where('account_runing.Recpt_No', $recpt_no)
                ->groupStart()
                ->orWhere('account_runing.advances_pay', 1)
                // ->orWhere('account_runing.advances_pay', 11)
                ->groupEnd()
                ->findAll();

            // log_message("critical", $accountrunningModel->db->getLastQuery());      

            // Calculate net balance (Dr - Cr)
            if (!empty($entries)) {
                $total_dr = 0;
                $total_cr = 0;
                foreach ($entries as $entry) {
                    $total_dr += floatval($entry['Dr']);
                    $total_cr += floatval($entry['Cr']);
                }
                $net_balance = $total_dr - $total_cr;

                // Only include if there's a remaining balance
                if ($net_balance > 0) {
                    // Modify the first entry to show net balance
                    $entries[0]['Dr'] = $net_balance;
                    $tour_details[$key] = [$entries[0]];
                }
            }
        }

        $diveradvancerequestmodel  = new DriverAdvanceRequestModel();
        $advance_payment_requests = $diveradvancerequestmodel->where('tour_id', $tour_id)->where('driver', $agent)->where('flag', 1)->findAll();

        $voucherpaymentmodel  = new VoucherPaymentModel();
        $voucherpayment = $voucherpaymentmodel->where('ref_po', $tour_id)->where('Acc_No', $agent)->where('advances_pay', 1)->where('Aflag <>', 1)->findAll();

        // Get transfer history for this tour (both transfers IN and OUT)
        $transfer_history = [];
        $accountrunningModel = new AccountRunningModel();

        // Get all transfer entries (Cr entries for transfers OUT)
        $transfers_out = $accountrunningModel
            ->select('account_runing.*, supplier.Name as from_driver_name')
            ->join('supplier', 'supplier.Sup_Acc_No = account_runing.L_Code', 'left')
            ->where('account_runing.index_no', $tour_id)
            ->where('account_runing.advances_pay', 1)
            ->where('account_runing.Cr >', 0)
            ->where('account_runing.L_Code', $agent)
            ->findAll();

        // log_message("critical", $accountrunningModel->db->getLastQuery());

        // Get all transfer entries (Dr entries for transfers IN)
        $transfers_in = $accountrunningModel
            ->select('account_runing.*, supplier.Name as to_driver_name')
            ->join('supplier', 'supplier.Sup_Acc_No = account_runing.L_Code', 'left')
            ->where('account_runing.index_no', $tour_id)
            ->where('account_runing.advances_pay', 1)
            ->where('account_runing.Dr >', 0)
            ->where('account_runing.payee !=', '')
            ->where('account_runing.L_Code', $agent)
            ->findAll();

        // log_message("critical", $accountrunningModel->db->getLastQuery());

        // Combine and format transfer history
        foreach ($transfers_out as $transfer) {
            $transfer_history[] = [
                'date' => $transfer['Date'],
                'from_driver' => $agent,
                'from_driver_name' => $transfer['from_driver_name'],
                'to_driver' => '',
                'to_driver_name' => $transfer['payee'],
                'amount' => $transfer['Cr'],
                'voucher_no' => $transfer['Recpt_No'],
                'type' => 'out'
            ];
        }

        foreach ($transfers_in as $transfer) {
            // Skip original advance payments (where payee matches the account holder)
            if ($transfer['payee'] === $transfer['to_driver_name']) {
                continue;
            }
            $transfer_history[] = [
                'date' => $transfer['Date'],
                'from_driver' => '',
                'from_driver_name' => 'Transfer In',
                'to_driver' => $agent,
                'to_driver_name' => $transfer['to_driver_name'],
                'amount' => $transfer['Dr'],
                'voucher_no' => $transfer['Recpt_No'],
                'type' => 'in'
            ];
        }

        // Sort by date
        usort($transfer_history, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        $data['tour_details'] = $tour_details;

        // log_message("critical", json_encode($tour_details));

        $data['voucherpayment'] = $voucherpayment;
        $data['advance_payment_requests'] = $advance_payment_requests;
        $data['transfer_history'] = $transfer_history;

        return $this->response->setJSON(['status' => 200, 'data' => $data]);
    }

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

            $ConsigneeModel = new ConsigneeModel();
            $consigneeData = $ConsigneeModel->select('tour_cancel')->where('tour_id', $tour)->first();

            if ($consigneeData && $consigneeData['tour_cancel'] == 1) {
                return json_encode(['status' => 400, 'message' => 'Cannot add payment. The tour has been cancelled.']);
            }

            if (!is_numeric($pay)) {
                return json_encode(['status' => 400, 'message' => 'Amount must be a number']);
            }

            $session = session();
            $username = $session->get('username');

            $data = [
                'transport_agent' => $agent,
                'reference_no' => $ref,
                'transport_advance' => $pay
            ];

            if ($ConsigneeModel->set($data)->where('tour_id', $tour)->update()) {

                if ($pay > 0) {
                    $invoiceNoModel = new InvoiceNumberModel();
                    $acc_id = $invoiceNoModel->incrementAccId();

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
                        'C_Date' => date('Y-m-d H:i:s'),
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

                    if (empty($voucherdetails)) {
                        $v_invmodel = new VoucherInvoiceModel();
                        $v_invmodel->insert($data_add);
                        $voucherpaymentmodel->insert($data);
                        $consigneeModel = new ConsigneeModel();
                        $consigneeModel->where('tour_id', $tour)->set(['transport_agent_name' => $agent_name, 'transport_agent' => $agent])->update();
                    } else {
                        $status = $voucherdetails[0]['Aflag'];
                        $transahent = $voucherdetails[0]['Acc_No'];

                        $v_invmodel = new VoucherInvoiceModel();
                        $v_invmodel->insert($data_add);
                        $voucherpaymentmodel->insert($data);
                        $consigneeModel = new ConsigneeModel();
                        $consigneeModel->where('tour_id', $tour)->set(['transport_agent_name' => $agent_name, 'transport_agent' => $agent])->update();
                    }
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

    public function update_advance()
    {
        try {
            $voucher_id = $this->request->getPost('voucher_id');
            $new_amount = $this->request->getPost('new_amount');
            $tour_id = $this->request->getPost('tour_id');

            if (!is_numeric($new_amount)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Amount must be a number']);
            }

            if ($new_amount <= 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Amount must be greater than zero']);
            }

            $voucherpaymentmodel = new VoucherPaymentModel();
            $voucherPayment = $voucherpaymentmodel->where('Vnum', $voucher_id)->first();

            if (!$voucherPayment) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Voucher payment not found']);
            }

            if ($voucherPayment['Aflag'] != 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot update. Payment status is not PENDING']);
            }

            $ConsigneeModel = new ConsigneeModel();
            $consigneeData = $ConsigneeModel->select('tour_cancel')->where('tour_id', $tour_id)->first();

            if ($consigneeData && $consigneeData['tour_cancel'] == 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot update payment. The tour has been cancelled.']);
            }

            $updateData = [
                'Amount' => $new_amount,
                'g_ttl' => $new_amount,
                'doller_amt' => $new_amount
            ];

            if ($voucherpaymentmodel->where('Vnum', $voucher_id)->set($updateData)->update()) {
                $voucherinvoicemodel = new VoucherInvoiceModel();
                $invoiceUpdateData = [
                    'Amount' => $new_amount,
                    'InvAmount' => $new_amount
                ];
                $voucherinvoicemodel->where('V_tkn', $voucher_id)->set($invoiceUpdateData)->update();

                $ConsigneeModel->where('tour_id', $tour_id)->set(['transport_advance' => $new_amount])->update();

                return $this->response->setJSON(['status' => 200, 'message' => 'Advance amount updated successfully!']);
            } else {
                return $this->response->setJSON(['status' => 500, 'message' => 'Failed to update advance amount']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function delete_advance()
    {
        try {
            $voucher_id = $this->request->getPost('voucher_id');
            $tour_id = $this->request->getPost('tour_id');

            $voucherpaymentmodel = new VoucherPaymentModel();
            $voucherPayment = $voucherpaymentmodel->where('Vnum', $voucher_id)->first();

            if (!$voucherPayment) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Voucher payment not found']);
            }

            if ($voucherPayment['Aflag'] != 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot delete. Payment status is not PENDING']);
            }

            $ConsigneeModel = new ConsigneeModel();
            $consigneeData = $ConsigneeModel->select('tour_cancel')->where('tour_id', $tour_id)->first();

            if ($consigneeData && $consigneeData['tour_cancel'] == 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot delete payment. The tour has been cancelled.']);
            }

            if ($voucherpaymentmodel->where('Vnum', $voucher_id)->delete()) {
                $voucherinvoicemodel = new VoucherInvoiceModel();
                $voucherinvoicemodel->where('V_tkn', $voucher_id)->delete();

                $ConsigneeModel->where('tour_id', $tour_id)->set(['transport_advance' => 0])->update();

                return $this->response->setJSON(['status' => 200, 'message' => 'Advance deleted successfully!']);
            } else {
                return $this->response->setJSON(['status' => 500, 'message' => 'Failed to delete advance']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function transfer_amount()
    {
        // tf-payguard-r1: this route posts a ledger pair under the original advance voucher's number, reduces that
        // voucher, and creates a new voucher already authorised. It is guarded like an authorisation: a named lock on
        // the original voucher number, checks read from the database (not from the browser), an operation key against
        // double submission, and an undo of everything written if a later step fails (the tables are MyISAM).
        $db = \Config\Database::connect();
        $lock = null;
        $written = ['acc_id' => null, 'amount_changed' => false, 'v_inv_id' => null, 'v_num' => null, 'orig' => null];
        $opKey = trim((string) $this->request->getPost('op_key'));
        try {
            $tour = $this->request->getPost('tour_id');
            $transfered_driver = $this->request->getPost('allocated_driver');
            $driver_name = $this->request->getPost('driver_name');
            $current_driver = $this->request->getPost('current_driver');
            $amount = $this->request->getPost('transfer_amount');
            $voucher = (int) $this->request->getPost('voucher_no');
            $username = session()->get('username');

            $ConsigneeModel = new ConsigneeModel();
            $consigneeData = $ConsigneeModel->select('tour_cancel')->where('tour_id', $tour)->first();
            if ($consigneeData && $consigneeData['tour_cancel'] == 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot transfer amount. The tour has been cancelled.']);
            }
            $amount = floatval(str_replace(',', '', (string) $amount));
            if (!is_numeric($amount) || $amount <= 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Amount must be a number above zero.']);
            }
            if ($voucher <= 0 || $transfered_driver === null || $transfered_driver === '' || $current_driver === null || $current_driver === '') {
                return $this->response->setJSON(['status' => 400, 'message' => 'Voucher number, current driver and allocated driver are required.']);
            }
            if ((string) $transfered_driver === (string) $current_driver) {
                return $this->response->setJSON(['status' => 400, 'message' => 'The allocated driver must differ from the current driver. Nothing was transferred.']);
            }

            $lock = \App\Libraries\PayGuard\AuthGuard::LOCK_PREFIX . $voucher;
            $got = $db->query('SELECT GET_LOCK(?, ?) AS g', [$lock, \App\Libraries\PayGuard\AuthGuard::LOCK_WAIT])->getRowArray();
            if (!$got || (int) $got['g'] !== 1) {
                $lock = null;
                $this->pgAudit($db, 'auth_refused', $voucher, 'transfer refused: lock busy');
                return $this->response->setJSON(['status' => 409, 'message' => 'Voucher ' . $voucher . ' is being changed by another request at this moment. Wait, then reload. Nothing was transferred.']);
            }

            // Facts read under the lock, from the database
            $orig = $db->query('SELECT ID, Vnum, Aflag, rej, Amount, g_ttl FROM voucher WHERE Vnum = ?', [$voucher])->getRowArray();
            if (!$orig) {
                $this->pgAudit($db, 'auth_refused', $voucher, 'transfer refused: original voucher not found');
                return $this->response->setJSON(['status' => 404, 'message' => 'Voucher ' . $voucher . ' was not found. Nothing was transferred.']);
            }
            if ((int) $orig['rej'] === 1 || (int) $orig['Aflag'] !== 1) {
                $this->pgAudit($db, 'auth_refused', $voucher, 'transfer refused: original voucher Aflag=' . (int) $orig['Aflag'] . ' rej=' . (int) $orig['rej']);
                return $this->response->setJSON(['status' => 409, 'message' => 'Voucher ' . $voucher . ' is not an authorised advance (status ' . (int) $orig['Aflag'] . '); an amount can only be transferred from an authorised voucher. Nothing was transferred.']);
            }
            $current_amount = (float) $orig['Amount'];
            if ($amount > $current_amount + 0.005) {
                $this->pgAudit($db, 'auth_refused', $voucher, 'transfer refused: amount ' . $amount . ' exceeds voucher amount ' . $current_amount);
                return $this->response->setJSON(['status' => 409, 'message' => 'The transfer (' . number_format($amount, 2) . ') exceeds the voucher balance (' . number_format($current_amount, 2) . '). Nothing was transferred.']);
            }
            if ($opKey !== '') {
                if (!preg_match('/^[A-Za-z0-9\-]{8,64}$/', $opKey)) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Invalid operation key.']);
                }
                $db->query("CREATE TABLE IF NOT EXISTS pg_op (op_key VARCHAR(64) NOT NULL, route VARCHAR(60) NOT NULL, vnum INT NULL, user VARCHAR(100) NULL, at DATETIME(3) NOT NULL, PRIMARY KEY (op_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                // r1.1: with DBDebug off the duplicate-key failure returns false and throws nothing (test G-11 posted twice);
                // the result is checked, and only a duplicate key (1062) is reported as a repeated submission
                $opErr = null;
                try {
                    $ins = $db->query('INSERT INTO pg_op (op_key, route, vnum, user, at) VALUES (?, ?, ?, ?, NOW(3))', [$opKey, 'V5 transfer_amount', $voucher, (string) $username]);
                    if ($ins === false) {
                        $opErr = $db->error();
                    }
                } catch (\Throwable $e) {
                    $opErr = ['code' => (strpos($e->getMessage(), 'Duplicate') !== false ? 1062 : (int) $e->getCode()), 'message' => $e->getMessage()];
                }
                if ($opErr !== null) {
                    if ((int) ($opErr['code'] ?? 0) === 1062) {
                        $this->pgAudit($db, 'auth_refused', $voucher, 'transfer refused: repeated submission (operation key already used)');
                        return $this->response->setJSON(['status' => 409, 'message' => 'This transfer was already submitted (same operation). It was not transferred a second time. Reload the page to see the current balance.']);
                    }
                    $this->pgAudit($db, 'auth_refused', $voucher, 'transfer refused: operation key could not be recorded: ' . mb_substr((string) ($opErr['message'] ?? ''), 0, 200));
                    return $this->response->setJSON(['status' => 409, 'message' => 'The operation could not be recorded (' . mb_substr((string) ($opErr['message'] ?? ''), 0, 160) . '). Nothing was transferred.']);
                }
            }

            $AccountRunningModel = new AccountRunningModel();
            $currentDriverEntry = $AccountRunningModel->where('index_no', $tour)
                ->where('L_Code', $current_driver)
                ->where('Discription LIKE', 'VOUCHER PAYMENT - Voucher Payment - Transport Advance%')
                ->where('Dr >', 0.00)
                ->where('Recpt_No', $voucher)
                ->where('advances_pay', 1)
                ->first();
            if (!$currentDriverEntry) {
                $this->pgAudit($db, 'auth_refused', $voucher, 'transfer refused: no advance ledger entry for the current driver under this voucher');
                return $this->response->setJSON(['status' => 409, 'message' => 'No transport-advance ledger entry for the current driver was found under voucher ' . $voucher . '. Nothing was transferred.']);
            }
            $this->pgAudit($db, 'auth_claimed', $voucher, 'transfer claimed: ' . number_format($amount, 2) . ' from ' . $current_driver . ' to ' . $transfered_driver);

            $invoiceNoModel = new InvoiceNumberModel();
            $acc_id = $invoiceNoModel->incrementAccId();
            $written['acc_id'] = (int) $acc_id;
            $written['orig'] = $orig;
            $now = date('Y-m-d H:i:s');
            $rows = [];
            foreach ([[$transfered_driver, $amount, 0.00], [$current_driver, 0.00, $amount]] as $leg) {
                $rows[] = [
                    'ID' => $acc_id, 'L_Code' => $leg[0], 'Dr' => $leg[1], 'Cr' => $leg[2],
                    'Discription' => $currentDriverEntry['Discription'], 'Cashier' => $username, 'Date' => date('Y-m-d'),
                    'Invoice_Num' => $currentDriverEntry['Invoice_Num'], 'DV' => 1, 'aflag' => 3, 'index_no' => $tour,
                    'P_Month' => $currentDriverEntry['P_Month'], 'Dt' => $now, 'Recpt_No' => $voucher, 'advances_pay' => 1, 'payee' => $driver_name,
                ];
            }
            if (!$AccountRunningModel->insertBatch($rows)) {
                throw new \RuntimeException('ledger insert failed');
            }

            $voucherpaymentmodel = new VoucherPaymentModel();
            $v_invmodel = new VoucherInvoiceModel();
            $updated_amount = $current_amount - $amount;
            // r1.1: results checked; a silent failure here would leave the transfer half written
            if (!$voucherpaymentmodel->where('Vnum', $voucher)->set(['Amount' => $updated_amount, 'g_ttl' => $updated_amount])->update()) {
                $written['amount_changed'] = true;   // the undo restores the original amounts whatever was written
                throw new \RuntimeException('voucher amount update failed');
            }
            $written['amount_changed'] = true;
            if (!$v_invmodel->where('V_tkn', $voucher)->set(['Amount' => $updated_amount, 'InvAmount' => $updated_amount])->update()) {
                throw new \RuntimeException('voucher line amount update failed');
            }

            $model_inv = new InvoiceNumberModel();
            $v_num = $model_inv->incrementVoucherToken();
            $written['v_num'] = (int) $v_num;
            $data_add = [
                'Date' =>  date('Y-m-d'), 'Invoice' => $tour, 'Description' => 'Transport Advance Transfer ' . $voucher,
                'Units' =>  0, 'Rate' => 0, 'Amount' => $amount, 'InvAmount' => $amount, 'V_tkn' => $v_num, 'ityp' => '1',
                'link_code' => $transfered_driver, 'employee' => $driver_name, 'login' =>  $username, 'INV_Date' => date('Y-m-d'),
                'Payee' => $driver_name, 'account' => $transfered_driver,
            ];
            if ($v_invmodel->insert($data_add) === false) {
                throw new \RuntimeException('new voucher line insert failed');   // r1.1
            }
            $written['v_inv_id'] = (int) $v_invmodel->getInsertID();

            $data = [
                'Pay' => $driver_name, 'emp_code' => $transfered_driver, 'S_name' => $driver_name, 'Acc_No' => $transfered_driver,
                'sup' => '1', 'type' => 'Bank Transfer', 'Amount' => $amount, 'VAT' => '0', 'VAT_Amt' => '0', 'vat_m' => '0',
                'nbt' => '0', 'nbt_amt' => '0', 'nbt_m' => '0', 'g_ttl' => $amount, 'Address' => '0', 'print' => '0', 'des' => '0',
                'W_Date' => date('Y-m-d'), 'dv' => '0', 'app_user' => session()->get('acc_no'), 'login' =>  $username, 'Aflag' => '1',
                'v_typ' => 'Bank Transfer', 'ref_po' => $tour, 'insflag' => 1, 'inv' => $voucher . " T", 'Vnum' => $v_num, 'Bank' => '',
                'Bank_acc' => '', 'C_Date' => $now, 'C_Number' => $tour, 'is_expenses' => 1, 'advances_pay' => 1,
                'doller_amt' => $amount, 'C_reated_By' => $tour, 'Aut_user' => $username, 'aut_date' => $now, 'is_confirm' => 0,
            ];
            if ($voucherpaymentmodel->insert($data) === false) {
                throw new \RuntimeException('new voucher insert failed');   // r1.1
            }

            $this->pgAudit($db, 'auth_posted', $voucher, 'transfer posted: journal ' . $acc_id . ', new voucher ' . $v_num . ' for ' . number_format($amount, 2), (string) $acc_id);
            return $this->response->setJSON(['status' => 200, 'message' => 'Amount transferred successfully! New voucher ' . $v_num . '.']);
        } catch (\Throwable $e) {
            // undo whatever this attempt wrote (MyISAM: by hand)
            $undo = [];
            $must = [\App\Libraries\PayGuard\AuthGuard::class, 'must'];   // r1.1: every undo statement checked
            try {
                if ($written['v_num']) {
                    $must($db, $db->query('DELETE FROM voucher WHERE Vnum = ? AND Aflag = 1 AND inv = ?', [$written['v_num'], ((int) $this->request->getPost('voucher_no')) . ' T']), 'new voucher delete');
                    $undo[] = 'new voucher ' . $written['v_num'] . ' removed (' . $db->affectedRows() . ')';
                }
                if ($written['v_inv_id']) {
                    $must($db, $db->query('DELETE FROM v_inv WHERE ID = ?', [$written['v_inv_id']]), 'new line delete');
                    $undo[] = 'new line removed';
                }
                if ($written['amount_changed'] && $written['orig']) {
                    $must($db, $db->query('UPDATE voucher SET Amount = ?, g_ttl = ? WHERE Vnum = ?', [$written['orig']['Amount'], $written['orig']['g_ttl'], $written['orig']['Vnum']]), 'voucher amount restore');
                    $must($db, $db->query('UPDATE v_inv SET Amount = ?, InvAmount = ? WHERE V_tkn = ?', [$written['orig']['Amount'], $written['orig']['Amount'], $written['orig']['Vnum']]), 'line amount restore');
                    $undo[] = 'original voucher amount restored';
                }
                if ($written['acc_id']) {
                    $must($db, $db->query('DELETE FROM account_runing WHERE ID = ? AND Recpt_No = ? AND advances_pay = 1', [$written['acc_id'], (int) $this->request->getPost('voucher_no')]), 'ledger delete');
                    $undo[] = $db->affectedRows() . ' ledger rows removed (journal ' . $written['acc_id'] . ')';
                }
                if ($opKey !== '') {
                    $must($db, $db->query('DELETE FROM pg_op WHERE op_key = ?', [$opKey]), 'operation key delete');
                }
            } catch (\Throwable $u) {
                $undo[] = 'UNDO INCOMPLETE: ' . $u->getMessage();
            }
            $this->pgAudit($db, 'auth_failed_undone', (int) $this->request->getPost('voucher_no'), 'transfer failed: ' . $e->getMessage() . ' | undo: ' . implode('; ', $undo));
            return $this->response->setJSON(['status' => 500, 'message' => 'The transfer failed (' . $e->getMessage() . ') and was undone: ' . implode('; ', $undo) . '. Nothing was transferred.']);
        } finally {
            if ($lock) {
                try {
                    $db->query('SELECT RELEASE_LOCK(?)', [$lock]);
                } catch (\Throwable $e) {
                }
            }
        }
    }

    private function pgAudit($db, string $action, int $vnum, string $reason, ?string $new = null): void
    {
        \App\Libraries\TourPayments\Audit::log($db, [
            'action' => $action, 'ref_type' => 'voucher', 'ref_id' => (string) $vnum, 'vnum' => $vnum,
            'prev' => 'V5 dashboard2/transport/driver_advance/transfer_amount', 'new' => $new,
            'reason' => \App\Libraries\PayGuard\AuthGuard::VERSION . ': ' . mb_substr($reason, 0, 400),
        ]);
    }
}
