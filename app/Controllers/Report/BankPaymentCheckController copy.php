<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;

class BankPaymentCheckController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $date = $this->request->getGet('date') ?: date('Y-m-d');
        $data['filter_date'] = $date;

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_accounts'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $db = \Config\Database::connect();

        // Hotel payments (advances_pay = 2) — joined via hotel_acc
        $hotelSql = "
            SELECT
                v.id, v.Vnum, v.C_Number AS tour_id, v.S_name AS payee,
                v.Acc_No AS hotel_acc, v.Amount, v.Bank, v.Bank_acc,
                v.W_Date AS pay_date, v.Aflag AS status,
                c.manual_id, c.consignee AS tour_name, c.agent_name,
                pr.id AS pr_id,
                pr.complete_date, pr.complete_user,
                pr.payment_request_date, pr.payment_request_user,
                pr.finalized, pr.finalized_user, pr.finalized_date,
                'Hotel' AS payment_type
            FROM voucher v
            LEFT JOIN consignee c ON c.tour_id = v.C_Number
            LEFT JOIN payment_request pr ON pr.tour_id = v.C_Number AND pr.hotel_acc = v.Acc_No AND pr.payment_request = 2
            WHERE v.advances_pay = 2
              AND DATE(pr.complete_date) = ?
        ";

        // Transport payments (advances_pay = 1) — joined via hotel_acc = v.Acc_No where create_user = 'Transport Agent'
        $transportSql = "
            SELECT
                v.id, v.Vnum, v.C_Number AS tour_id, v.S_name AS payee,
                v.Acc_No AS hotel_acc, v.Amount, v.Bank, v.Bank_acc,
                v.W_Date AS pay_date, v.Aflag AS status,
                c.manual_id, c.consignee AS tour_name, c.agent_name,
                pr.id AS pr_id,
                pr.complete_date, pr.complete_user,
                pr.payment_request_date, pr.payment_request_user,
                pr.finalized, pr.finalized_user, pr.finalized_date,
                'Transport' AS payment_type
            FROM voucher v
            LEFT JOIN consignee c ON c.tour_id = v.C_Number
            LEFT JOIN payment_request pr ON pr.tour_id = v.C_Number AND pr.hotel_acc = v.Acc_No AND pr.payment_request = 2 AND pr.create_user = 'Transport Agent'
            WHERE v.advances_pay = 1
              AND DATE(pr.complete_date) = ?
        ";

        $sql = "SELECT * FROM (($hotelSql) UNION ALL ($transportSql)) AS combined WHERE finalized != 1 OR finalized IS NULL ORDER BY tour_id ASC, Vnum ASC";

        $payments = $db->query($sql, [$date, $date])->getResultArray();

        $data['payments'] = $payments;

        return view('pages/report/bank_payment_check', $data);
    }

    public function mark_finalized()
    {
        try {
            $pr_id = $this->request->getPost('pr_id');

            if (empty($pr_id)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid request']);
            }

            $db = \Config\Database::connect();
            $db->table('payment_request')
                ->where('id', $pr_id)
                ->update([
                    'finalized'      => 1,
                    'finalized_user' => session()->get('username'),
                    'finalized_date' => date('Y-m-d H:i:s'),
                ]);

            if ($db->affectedRows() > 0) {
                return $this->response->setJSON(['status' => 200, 'message' => 'Marked as finalized']);
            }

            return $this->response->setJSON(['status' => 404, 'message' => 'Record not found']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
