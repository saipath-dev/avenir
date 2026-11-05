<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\PaymentRequestModel;
use App\Models\UtilityBillModel;
use App\Models\VoucherPaymentModel;

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

        // One row per v_inv line so bulk vouchers show each tour separately
        $sql = "
            SELECT
                v.id, v.Vnum, v.S_name AS payee,
                v.Acc_No AS hotel_acc, v.Amount AS voucher_total, v.Bank, v.Bank_acc,
                v.W_Date AS pay_date, v.Aflag AS status,
                v.sup, v.v_typ,
                v.finalized, v.finalized_user, v.finalized_date, v.bank_date,
                s.sup_bank_acc, s.bank_name AS sup_bank_name, s.branch_name AS sup_branch_name,
                s.acc_holder AS sup_acc_holder, s.hotel_code,
                vi.ID        AS vi_id,
                vi.Invoice   AS tour_id,
                vi.Description AS vi_description,
                vi.Rate      AS dollar_rate,
                vi.Amount    AS vi_amount,
                vi.INV_Date  AS inv_date,
                c.manual_id, c.consignee AS tour_name
            FROM voucher v
            LEFT JOIN v_inv vi ON vi.V_tkn = v.Vnum
            LEFT JOIN consignee c ON c.tour_id = vi.Invoice
            LEFT JOIN supplier s ON s.Sup_Acc_No = v.Acc_No
            WHERE v.finalized = 0 AND v.Aflag IN (0) AND v.v_typ != 'Petty Cash'
            ORDER BY v.S_name ASC, v.Vnum ASC, vi.ID ASC
        ";

        // Transport payments (advances_pay = 1) — joined via hotel_acc = v.Acc_No where create_user = 'Transport Agent'
        // $transportSql = "
        //     SELECT
        //         v.id, v.Vnum, v.C_Number AS tour_id, v.S_name AS payee,
        //         v.Acc_No AS hotel_acc, v.Amount, v.Bank, v.Bank_acc,
        //         v.W_Date AS pay_date, v.Aflag AS status,
        //         c.manual_id, c.consignee AS tour_name, c.agent_name,
        //         pr.id AS pr_id,
        //         pr.complete_date, pr.complete_user,
        //         pr.payment_request_date, pr.payment_request_user,
        //         pr.finalized, pr.finalized_user, pr.finalized_date,
        //         'Transport' AS payment_type
        //     FROM voucher v
        //     LEFT JOIN consignee c ON c.tour_id = v.C_Number
        //     LEFT JOIN payment_request pr ON pr.tour_id = v.C_Number AND pr.hotel_acc = v.Acc_No AND pr.payment_request = 2 AND pr.create_user = 'Transport Agent'
        //     WHERE v.advances_pay = 1
        //       AND DATE(pr.complete_date) = ?
        // ";

        // $sql = "SELECT * FROM (($hotelSql) UNION ALL ($transportSql)) AS combined WHERE finalized != 1 OR finalized IS NULL ORDER BY tour_id ASC, Vnum ASC";

        $payments = $db->query($sql)->getResultArray();

        $data['payments'] = \App\Libraries\TourPayments\Mask::bank($payments);   // tf-pilot-r3: masked unless the sign-in pays or verifies (or is a profile older than the departments)
        // tf-bankreg-r1: the registered payee account each voucher carries (copied at selection), with its review state; the master's columns stay as the fallback
        $data['tpd_pay_accounts'] = [];
        $data['tpd_can_review_account'] = false;
        try {
            $data['tpd_pay_accounts'] = \App\Libraries\TourPayments\SupplierAccounts::displayForVouchers($db, array_column($payments, 'Vnum'));
            $data['tpd_can_review_account'] = \App\Libraries\TourPayments\Perms::can('verifier') || \App\Libraries\TourPayments\Perms::can('batch');
            $data['tpd_registry'] = \App\Libraries\TourPayments\SupplierAccounts::installed($db);
        } catch (\Throwable $tpdE) {
        }

        // Pending Petty Cash vouchers awaiting confirmation
        $voucherPaymentModel = new VoucherPaymentModel();
        $data['petty_cash_vouchers'] = $voucherPaymentModel->load_vouchers('petty');

        // Get petty cash account balance details
        $pcRow = $db->query("
            SELECT COALESCE(SUM(Dr), 0) AS total_dr, COALESCE(SUM(Cr), 0) AS total_cr
            FROM account_runing
            WHERE L_Code = '1-2-3-10900'
        ")->getRow();
        $data['pc_total_dr'] = floatval($pcRow->total_dr ?? 0);
        $data['pc_total_cr'] = floatval($pcRow->total_cr ?? 0);
        $data['pc_balance']  = $data['pc_total_dr'] - $data['pc_total_cr'];



        // Counts from payment_requested_dashboard
        $PaymentRequestModel = new PaymentRequestModel();
        $count_payment_requests = $PaymentRequestModel
            ->join('consignee', 'consignee.tour_id = payment_request.tour_id')
            ->where('payment_request.payment_request', '4')
            ->where('consignee.tour_cancel <>', 1)
            ->countAllResults();

        $UtilityBillModel = new UtilityBillModel();
        $count_utility_bills = $UtilityBillModel
            ->where('status', 1)
            ->groupStart()
                ->where('pay_status !=', 4)
                ->orWhere('pay_status IS NULL')
            ->groupEnd()
            ->countAllResults();

        $count_transport_expenses = $db->query("
            SELECT COUNT(DISTINCT driver_expenses.tour_id) AS cnt
            FROM driver_expenses
            LEFT JOIN consignee ON consignee.tour_id COLLATE utf8mb4_general_ci = driver_expenses.tour_id COLLATE utf8mb4_general_ci
            WHERE driver_expenses.status = 'posted'
        ")->getRow()->cnt ?? 0;

        $data['count_pending'] = $count_payment_requests + $count_utility_bills + $count_transport_expenses;

        // tf-paydesk-r1: the bank approver's privilege decides who sees Mark Finalized; the accountant sees the initiations recorded
        $data['tpd_ready'] = false;
        // tf-mfguard-r2: finalize controls are shown only when the desk is definitely absent (installed, or unreadable, closes them)
        $data['tpd_desk_state'] = $this->deskState();
        $data['tpd_closed'] = $data['tpd_desk_state'] !== 'absent';
        $data['tpd_can_pay'] = true;
        $data['tpd_can_initiate'] = false;
        $data['tpd_initiations'] = [];
        $data['tpd_batches'] = [];
        try {
            $data['tpd_ready'] = \App\Libraries\TourPayments\Schema::installed($db);
            if ($data['tpd_ready']) {
                $data['tpd_can_pay'] = \App\Libraries\TourPayments\Perms::can('bank_approver');
                $data['tpd_can_initiate'] = \App\Libraries\TourPayments\Perms::can('initiator');   // tf-paydesk-r7f: the explicit initiation privilege
                foreach ($db->query("SELECT i.vnum, i.initiated_at, i.initiated_by, i.batch_ref, i.bank_ref, i.amount, a.label FROM tpd_bank_initiation i LEFT JOIN tpd_bank_account a ON a.id = i.bank_account_id")->getResultArray() as $tpdI) {
                    $data['tpd_initiations'][(string) $tpdI['vnum']] = $tpdI;
                }
                // tf-paydesk-r2: the supplier batch each voucher belongs to (its page carries it to Paid at the payment-date rate)
                $data['tpd_batches'] = [];
                // tf-paydesk-r5: a bulk child carries its parent (one transfer for the whole batch; initiated and paid on the bulk page)
                foreach ($db->query("SELECT b.id, b.batch_no, b.vnum, b.status, b.item_count, b.tour_count, b.usd_total, b.approval_lkr_total, b.parent_id, p.batch_no AS parent_no, p.voucher_count AS parent_count FROM tpd_batch b LEFT JOIN tpd_batch p ON p.id = b.parent_id WHERE b.vnum IS NOT NULL")->getResultArray() as $tpdB) {
                    $data['tpd_batches'][(string) $tpdB['vnum']] = $tpdB;
                }
            }
        } catch (\Throwable $tpdE) {
        }

        return view('pages/report/bank_payment_check', $data);
    }

    // tf-paydesk-r1: once the Tour Payment Desk is set up, Mark Finalized (= Paid) is the bank approver's action alone;
    // the accountant's step is to record the bank initiation. Before setup nothing changes.
    private function tpdPaidGuard(array $vnums = []): ?string
    {
        try {
            $db = \Config\Database::connect();
            if (!\App\Libraries\TourPayments\Schema::installed($db)) {
                return null;
            }
            if (!\App\Libraries\TourPayments\Perms::can('bank_approver')) {
                return 'Marking a payment finalized (Paid) needs the Bank payment approver privilege. The accountant records the bank initiation; the approver confirms the transfer and clicks Paid.';
            }
            // tf-paydesk-r2: a supplier batch voucher is paid on its batch page, where the rate of the actual payment date is
            // confirmed and locked — Mark Finalized here would skip that
            foreach ($vnums as $tpdV) {
                $b = \App\Libraries\TourPayments\Batches::byVnum($db, (int) $tpdV);
                if ($b) {
                    return 'Voucher ' . (int) $tpdV . ' belongs to supplier payment batch ' . $b['batch_no'] . ': confirm the final payment date and exchange rate on its batch page (Supplier Payables → ' . $b['batch_no'] . ' → Paid).';
                }
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    /**
     * tf-mfguard-r1/r2 (16–17 Oct 2026): whether the Tour Payment Desk governs Paid here.
     *   'absent'      the desk libraries are not on this system, or tpd_meta does not exist, or its schema is older than the desk
     *                 needs — the older Mark Finalized behaviour below applies unchanged;
     *   'installed'   the desk is installed — Mark Finalized and Finalize Selected are closed on this page;
     *   'unreadable'  the desk's status could not be read (a database error) — closed as well: the page refuses rather than
     *                 finalize past a desk it cannot see (r2: Schema::installed() reads such an error as "not installed").
     */
    private function deskState(): string
    {
        if (!class_exists(\App\Libraries\TourPayments\Schema::class)) {
            return 'absent';
        }
        try {
            $db = \Config\Database::connect();
            $t = $db->query("SELECT COUNT(*) AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tpd_meta'")->getRowArray();
            if ((int) ($t['n'] ?? 0) === 0) {
                return 'absent';
            }
            $r = $db->query("SELECT v FROM tpd_meta WHERE k = 'schema_version'")->getRowArray();
            return (int) ($r['v'] ?? 0) >= \App\Libraries\TourPayments\Schema::VERSION ? 'installed' : 'absent';
        } catch (\Throwable $e) {
            return 'unreadable';
        }
    }

    /**
     * tf-mfguard-r1: once the Tour Payment Desk is installed, Mark Finalized and Finalize Selected on this page are closed for
     * everyone, the System Administrator included. A payment is then completed on the Payment Desk only: the accountant records
     * the bank initiation, a different person with the bank-approver privilege marks it Paid, and the desk posts the bank charges
     * and the exchange difference and reads the ledger back. The refusal comes before anything is written — no voucher, ledger,
     * counter, Paid lock or remittance change; only the refusal itself is audited (finalize_refused). The authorisation guard
     * (accounts/voucher_auth/update, tf-payguard) is not touched by this.
     */
    private function deskClosed(array $vnums, string $route): ?array
    {
        $state = $this->deskState();
        if ($state === 'absent') {
            return null;   // the older behaviour stands
        }
        $vnums = array_values(array_filter(array_map('intval', $vnums)));
        $msg = $state === 'installed'
            ? 'Mark Finalized is closed on this page because the Tour Payment Desk is installed. Complete the payment on the Payment Desk: the accountant records the bank initiation, then a different person with the bank-approver privilege marks it Paid there (Payment Desk, or the Tour Payment Dashboard of the tour, or the batch page of a supplier batch). Nothing was changed.'
            : 'Mark Finalized is closed on this page: the Tour Payment Desk\'s status could not be read, so this page refuses rather than finalize past it. Ask the administrator to check the Payment Desk. Nothing was changed.';
        try {
            \App\Libraries\TourPayments\Audit::log(\Config\Database::connect(), ['action' => 'finalize_refused', 'ref_type' => 'voucher', 'ref_id' => mb_substr(implode(',', array_slice($vnums, 0, 20)), 0, 80), 'vnum' => count($vnums) === 1 ? $vnums[0] : null,
                'prev' => 'POST ' . $route, 'new' => ['vouchers' => array_slice($vnums, 0, 50), 'bank_date' => (string) ($this->request->getPost('bank_date') ?? ''), 'desk_state' => $state], 'reason' => 'tf-mfguard-r2: ' . $msg]);
        } catch (\Throwable $e) {
        }
        return ['status' => 409, 'message' => $msg, 'desk' => true, 'desk_state' => $state, 'desk_url' => base_url('tour/payment_desk')];
    }

    public function mark_finalized()
    {
        // tf-paydesk-r7e: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'mark_finalized')) {
            return $tfCsrf;
        }
        // tf-mfguard-r1: closed once the Tour Payment Desk is installed — answered before any input is used or anything written
        if ($closed = $this->deskClosed([(int) ($this->request->getPost('pr_id') ?? 0)], 'report/bank_payment_check/mark_finalized')) {
            return $this->response->setJSON($closed);
        }
        try {
            $pr_id   = $this->request->getPost('pr_id');
            $bank_date = $this->request->getPost('bank_date');

            if (empty($pr_id)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid request']);
            }

            if (empty($bank_date)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Bank date is required']);
            }
            if ($why = $this->tpdPaidGuard([(int) $pr_id])) {
                return $this->response->setStatusCode(403)->setJSON(['status' => 403, 'message' => $why]);
            }

            $db = \Config\Database::connect();
            $db->table('voucher')
                ->where('Vnum', $pr_id)
                ->update([
                    'finalized'      => 1,
                    'finalized_user' => session()->get('username'),
                    'finalized_date' => date('Y-m-d H:i:s'),
                    'bank_date'      => $bank_date,
                ]);

            if ($db->affectedRows() > 0) {
                // tf-paydesk-r1: the Paid lock and the supplier's pending advice, as the dashboard's Paid writes them
                \App\Libraries\TourPayments\PayDesk::lockPaid($db, (int) $pr_id, date('Y-m-d', strtotime($bank_date)), (string) ($this->request->getPost('bank_ref') ?? ''), (string) session()->get('username'), 'Marked finalized on Bank Payment Check');
                return $this->response->setJSON(['status' => 200, 'message' => 'Marked as finalized']);
            }

            return $this->response->setJSON(['status' => 404, 'message' => 'Record not found']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function finalized_records()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $from = $this->request->getGet('from') ?: date('Y-m-01');
        $to   = $this->request->getGet('to')   ?: date('Y-m-d');
        $data['filter_from'] = $from;
        $data['filter_to']   = $to;

        $db = \Config\Database::connect();

        $sql = "
            SELECT
                v.id, v.Vnum, v.S_name AS payee,
                v.Acc_No AS hotel_acc, v.Amount AS voucher_total, v.Bank, v.Bank_acc,
                v.W_Date AS pay_date, v.Aflag AS status,
                v.sup, v.v_typ,
                v.finalized, v.finalized_user, v.finalized_date, v.bank_date,
                s.sup_bank_acc, s.bank_name AS sup_bank_name, s.branch_name AS sup_branch_name,
                s.acc_holder AS sup_acc_holder,
                vi.ID        AS vi_id,
                vi.Invoice   AS tour_id,
                vi.Description AS vi_description,
                vi.Rate      AS dollar_rate,
                vi.Amount    AS vi_amount,
                vi.INV_Date  AS inv_date,
                c.manual_id, c.consignee AS tour_name
            FROM voucher v
            LEFT JOIN v_inv vi ON vi.V_tkn = v.Vnum
            LEFT JOIN consignee c ON c.tour_id = vi.Invoice
            LEFT JOIN supplier s ON s.Sup_Acc_No = v.Acc_No
            WHERE v.finalized = 1
              AND v.Aflag = 1
              AND DATE(v.bank_date) BETWEEN ? AND ?
            ORDER BY v.bank_date ASC, v.Vnum ASC, vi.ID ASC
        ";

        $payments = $db->query($sql, [$from, $to])->getResultArray();

        // tf-pilot-r3: the payees' bank account numbers to their last four digits for a sign-in that neither pays nor verifies
        // (the Management Approver, the Auditor); Accounts, the Bank Authoriser and the administrator see them in full
        $data['payments'] = \App\Libraries\TourPayments\Mask::bank($payments);

        return view('pages/report/bank_payment_check_finalized', $data);
    }

    public function bulk_finalize()
    {
        // tf-paydesk-r7e: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'bulk_finalize')) {
            return $tfCsrf;
        }
        // tf-mfguard-r1: closed once the Tour Payment Desk is installed — answered before any input is used or anything written
        if ($closed = $this->deskClosed((array) ($this->request->getPost('vnums') ?? []), 'report/bank_payment_check/bulk_finalize')) {
            return $this->response->setJSON($closed);
        }
        try {
            $vnums     = $this->request->getPost('vnums');
            $bank_date = $this->request->getPost('bank_date');

            if (empty($vnums) || !is_array($vnums)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'No records selected']);
            }

            if (empty($bank_date)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Bank date is required']);
            }
            if ($why = $this->tpdPaidGuard(array_map('intval', (array) $vnums))) {
                return $this->response->setStatusCode(403)->setJSON(['status' => 403, 'message' => $why]);
            }

            $db = \Config\Database::connect();
            $db->table('voucher')
                ->whereIn('Vnum', $vnums)
                ->update([
                    'finalized'      => 1,
                    'finalized_user' => session()->get('username'),
                    'finalized_date' => date('Y-m-d H:i:s'),
                    'bank_date'      => $bank_date,
                ]);

            $affected = $db->affectedRows();
            // tf-paydesk-r1: each voucher's Paid lock and pending advice (one per voucher, per supplier — never combined)
            foreach ($vnums as $tpdV) {
                \App\Libraries\TourPayments\PayDesk::lockPaid($db, (int) $tpdV, date('Y-m-d', strtotime($bank_date)), (string) ($this->request->getPost('bank_ref') ?? ''), (string) session()->get('username'), 'Marked finalized on Bank Payment Check (bulk)');
            }

            return $this->response->setJSON(['status' => 200, 'message' => $affected . ' record(s) finalized successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
