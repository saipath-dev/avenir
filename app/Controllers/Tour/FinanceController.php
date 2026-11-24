<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\TourPayments\Audit;
use App\Libraries\TourPayments\Mask;
use App\Libraries\TourPayments\Overview;
use App\Libraries\TourPayments\PayDesk;
use App\Libraries\TourPayments\Perms;
use App\Libraries\TourPayments\Schema;

/**
 * tf-finance-r1 (23 Nov 2026, Shantha) — the Finance page (tour/finance): the payment chain in three stages and the bank
 * reconciliation, one list at a time.
 *
 *   GET tour/finance        the page (any payment role)
 *   GET tour/finance/data   the three stages, read-only (any payment role)
 *
 * Nothing is written here. The records and the approvals stay as they are: Approve / Return go to
 * tour/tour_payment_dashboard/approve and /reject, the bank payment details to /initiate, the finalisation to /paid, and
 * each of those checks its own privilege and the separation of duties again. The voucher is opened as a preview at every
 * stage; printing and the PDF are unlocked only once the voucher is finalised (TourPaymentDeskController::voucher).
 */
class FinanceController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function json(array $p, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($p);
    }

    private function deny(string $what)
    {
        try {
            Audit::log($this->db(), ['action' => 'access_denied', 'ref_type' => 'action', 'ref_id' => mb_substr((string) service('uri')->getRoutePath(), 0, 80), 'reason' => mb_substr('privilege: ' . $what, 0, 500)]);
        } catch (\Throwable $e) {
        }
        if ($this->request->isAJAX() || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json')) {
            return $this->json(['status' => 403, 'message' => $what . ' The attempt was recorded.'], 403);
        }
        return redirect()->to(base_url('403'));
    }

    private function perms(): array
    {
        $dbr = false;
        try {
            $dbr = class_exists(\App\Libraries\BankRec\Access::class) && \App\Libraries\BankRec\Access::can('view');
        } catch (\Throwable $e) {
        }
        return Perms::all() + ['initiate' => Perms::isAdmin() || Perms::holds('report/bank_payment_check/initiate'), 'dbr' => $dbr];
    }

    private function installed(): bool
    {
        try {
            return Schema::installed($this->db());
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function index()
    {
        if (!Perms::can('viewer')) {
            return $this->deny('The Finance page needs a payment role.');
        }
        $data = (new \App\Libraries\MenuLoader())->loadMenuData();
        $data['tpd_perms'] = $this->perms();
        $data['tpd_user'] = Perms::user();
        $data['tpd_ready'] = $this->installed();
        return view('pages/tour/finance', $data);
    }

    /** HTL / DRV / RES / GDE … — the short tag of a request's type. */
    private static function cat(?string $type): string
    {
        $t = strtolower(trim((string) $type));
        foreach (['hotel' => 'HTL', 'accom' => 'HTL', 'driver' => 'DRV', 'transport' => 'DRV', 'vehicle' => 'DRV', 'restaurant' => 'RES', 'meal' => 'RES', 'lunch' => 'RES', 'dinner' => 'RES', 'guide' => 'GDE',
                  'entrance' => 'ENT', 'ticket' => 'ENT', 'safari' => 'ACT', 'jeep' => 'ACT', 'activity' => 'ACT', 'excursion' => 'ACT', 'train' => 'TRN', 'rail' => 'TRN'] as $k => $tag) {
            if ($t !== '' && str_contains($t, $k)) {
                return $tag;
            }
        }
        return $t === '' ? 'OTH' : strtoupper(substr(preg_replace('/[^a-z]/', '', $t) ?: 'oth', 0, 3));
    }

    public function data()
    {
        if (!Perms::can('viewer')) {
            return $this->deny('The Finance page needs a payment role.');
        }
        if (!$this->installed()) {
            return $this->json(['status' => 409, 'message' => 'The Tour Payment Desk is not installed on this system.']);
        }
        $db = $this->db();
        try {
            $o = Overview::build($db, $this->perms());
            $inq = static function ($manual) use ($db): string {
                try {
                    return $manual ? trim((string) preg_replace('/^INQ[\s\-:]*/i', '', PayDesk::inquiryNo($db, (string) $manual))) : '';
                } catch (\Throwable $e) {
                    return '';
                }
            };

            // ---- 1. Payment Approval: the requests waiting for management --------------------------------------------
            $req = $o['requests']['rows'] ?? [];
            $ext = [];
            if ($ids = array_map('intval', array_column($req, 'pr_id'))) {
                foreach ($db->query("SELECT * FROM tpd_request_ext WHERE pr_id IN (" . implode(',', $ids) . ")")->getResultArray() as $x) {
                    $ext[(int) $x['pr_id']] = $x;
                }
            }
            $start = [];   // no stored due date: the existing rule is the check-in date (PayDesk), shown as the due date here too
            if ($ids) {
                foreach ($db->query("SELECT id, start_date FROM payment_request WHERE id IN (" . implode(',', $ids) . ")")->getResultArray() as $s) {
                    $start[(int) $s['id']] = $s['start_date'];
                }
            }
            $hold = [];   // tf-finance-r2: Hold — the existing 'disputed' record of the request (reason, who, when)
            $holdOf = static function (array $prIds) use ($db, &$hold): array {
                $out = [];
                foreach ($prIds as $id) {
                    $id = (int) $id;
                    if (!array_key_exists($id, $hold)) {
                        $h = $db->query("SELECT disputed, dispute_reason, disputed_by, disputed_at FROM tpd_request_ext WHERE pr_id = ?", [$id])->getRowArray();
                        $hold[$id] = $h && (int) $h['disputed'] === 1 ? ['pr_id' => $id, 'reason' => $h['dispute_reason'], 'by' => $h['disputed_by'], 'at' => $h['disputed_at']] : null;
                    }
                    if ($hold[$id]) {
                        $out[] = $hold[$id];
                    }
                }
                return $out;
            };
            // tf-finance-r5 (24 Nov 2026): the payment deadline. A prepaid hotel is due three calendar days before arrival; a due date
            // stored on the request (an authorised exception) takes its place. Credit terms are not defined yet, so nothing else gets a deadline.
            $today = date('Y-m-d');
            $deadline = static function (?string $type, $checkIn, $stored) use ($today): array {
                $ok = static fn ($d) => ($d = substr(trim((string) ($d ?? '')), 0, 10)) !== '' && !str_starts_with($d, '0000') && strtotime($d) ? $d : null;
                $in = $ok($checkIn); $st = $ok($stored);
                $by = $st ?: ($in && stripos((string) $type, 'hotel') !== false ? date('Y-m-d', strtotime($in . ' -3 days')) : null);
                $days = $by ? (int) round((strtotime($by) - strtotime($today)) / 86400) : null;
                return ['pay_by' => $by, 'pay_by_basis' => $st ? 'set on the request' : ($by ? '3 days before arrival' : null), 'days_left' => $days,
                    'due_state' => $by === null ? null : ($days < 0 ? 'overdue' : ($days <= 3 ? 'soon' : 'ok'))];
            };
            $approval = [];
            foreach ($req as $r) {
                $x = $ext[$r['pr_id']] ?? [];
                $approval[] = ['pr_id' => $r['pr_id'], 'payee' => $r['supplier'], 'type' => $r['type'], 'cat' => self::cat($r['type']), 'tour' => $r['manual_id'], 'tour_id' => $r['tour_id'],
                    'inq' => $inq($r['manual_id']), 'due' => ($x['due_date'] ?? null) ?: ($start[$r['pr_id']] ?? null), 'currency' => $x['currency'] ?? null,
                    'amount' => isset($x['amount']) && $x['amount'] !== null ? (float) $x['amount'] : (float) $r['amount'], 'invoice_no' => $x['invoice_no'] ?? null, 'booking_ref' => $x['booking_ref'] ?? null,
                    'status' => $r['status'], 'by' => $r['by'], 'at' => $r['at'], 'tour_cancelled' => $r['tour_cancelled'], 'holds' => $holdOf([$r['pr_id']])]
                    + ['check_in' => $start[$r['pr_id']] ?? null] + $deadline($r['type'], $start[$r['pr_id']] ?? null, $x['due_date'] ?? null);
            }

            // ---- 2. Ready for Bank Payment: approved, not yet finalised ---------------------------------------------
            $ready = [];
            $waiting = [];   // the approved items of each supplier, for Hold / Release before the batch
            try {
                foreach (\App\Libraries\TourPayments\Batches::awaiting($db) as $sx) {
                    $waiting[(string) $sx['sup_acc']] = array_map(static fn ($i) => ['pr_id' => (int) $i['pr_id'], 'tour' => $i['manual_id'] ?? null, 'usd' => isset($i['usd_amount']) ? (float) $i['usd_amount'] : null, 'check_in' => $i['start_date'] ?? null, 'invoice_no' => $i['invoice_no'] ?? null,
                        'lkr' => isset($i['approval_lkr']) ? (float) $i['approval_lkr'] : null, 'held' => !empty($i['disputed']), 'hold_reason' => $i['dispute_reason'] ?? null], $sx['items'] ?? []);
                }
            } catch (\Throwable $e) {
            }
            foreach (($o['payables']['rows'] ?? []) as $p) {
                $ready[] = ['kind' => 'payable', 'key' => 'p' . $p['sup_acc'], 'payee' => $p['supplier'], 'sup_acc' => $p['sup_acc'], 'items' => $p['items'], 'tours' => $p['tours'], 'tour' => implode(', ', $p['tours']),
                    'inq' => implode(', ', array_filter(array_map($inq, $p['tours']))), 'usd' => $p['usd_total'], 'amount' => $p['approval_lkr_total'], 'stage' => 'batch', 'disputed' => $p['disputed'], 'rate_missing' => $p['rate_missing'],
                    'lines' => $waiting[(string) $p['sup_acc']] ?? [], 'holds' => $holdOf(array_column($waiting[(string) $p['sup_acc']] ?? [], 'pr_id'))];
            }
            foreach (array_merge($o['initiation']['rows'] ?? [], $o['authorisation']['rows'] ?? []) as $v) {
                $vp = array_map('intval', array_column($db->query("SELECT x.pr_id FROM tpd_request_ext x LEFT JOIN tpd_batch b ON b.id = x.batch_id WHERE x.voucher_vnum = ? OR b.vnum = ?", [(int) $v['vnum'], (int) $v['vnum']])->getResultArray(), 'pr_id'));
                $ready[] = ['pr_ids' => array_values(array_unique($vp)), 'holds' => $holdOf(array_values(array_unique($vp))), 'kind' => 'voucher', 'key' => 'v' . $v['vnum'], 'vnum' => $v['vnum'], 'payee' => $v['payee'], 'tour' => $v['manual_id'], 'inq' => $inq($v['manual_id']), 'usd' => $v['usd'], 'amount' => $v['amount'],
                    'stage' => $v['initiated'] ? 'finalize' : 'record', 'via' => $v['via'] ?? null, 'bank_leg' => !empty($v['bank_leg']), 'ref' => $v['ref'] ?? null, 'locked' => $v['locked'], 'bulk_parent' => $v['bulk_parent'], 'batch_id' => $v['batch_id'], 'batch_no' => $v['batch_no'], 'non_tour' => $v['kind'] === 'non_tour',
                    'bank' => $v['bank'], 'bank_ledger' => $v['bank_ledger'], 'init_ledger' => $v['init_ledger'], 'aflag' => $v['aflag'], 'initiated_by' => $v['initiated_by'], 'initiated_at' => $v['initiated_at'], 'payment_date' => $v['payment_date'],
                    'bank_ref' => $v['bank_ref'], 'charges' => $v['charges'], 'total_debit' => $v['total_debit'], 'created' => $v['created']];
            }

            // tf-finance-r3: vouchers raised outside the payment chain (utility bills, office costs, older screens) — checked and adopted here before their bank payment
            foreach (($o['not_on_desk']['rows'] ?? []) as $v) {
                $ready[] = ['kind' => 'adopt', 'key' => 'a' . $v['vnum'], 'vnum' => $v['vnum'], 'payee' => $v['payee'], 'tour' => $v['manual_id'], 'inq' => $inq($v['manual_id']), 'usd' => null, 'amount' => $v['amount'], 'stage' => 'adopt',
                    'non_tour' => ($v['kind'] ?? '') !== 'tour', 'mode' => $v['mode'], 'aflag' => $v['aflag'], 'bank' => $v['bank'], 'bank_ledger' => $v['bank_ledger'], 'ref' => $v['ref'], 'description' => $v['description'],
                    'created' => $v['created'], 'created_by' => $v['created_by'], 'desk_record' => $v['desk_record'] ?? 'none', 'holds' => [], 'pr_ids' => []];
            }

            // ---- 3. Finalized Payments: Paid — the voucher is finalised, its print is unlocked ------------------------
            $fin = $db->query("SELECT a.vnum, a.paid_date, a.lkr_amount, a.usd_amount, a.charges, a.total_debit, a.initiated_by, a.approved_by, a.approved_at, a.usd_rate, a.fx_diff, a.ledger_bank_credit, a.reconciled, a.note, v.S_name, v.type, i.bank_ref,
                                      (SELECT r.status FROM tpd_remittance r WHERE r.vnum = a.vnum ORDER BY r.id DESC LIMIT 1) AS remit_status,
                                      (SELECT b.id FROM tpd_batch b WHERE b.vnum = a.vnum LIMIT 1) AS batch_id,
                                      (SELECT pr.manual_id FROM tpd_request_ext x JOIN payment_request pr ON pr.id = x.pr_id WHERE x.voucher_vnum = a.vnum LIMIT 1) AS manual_id,
                                      (SELECT pr.type FROM tpd_request_ext x JOIN payment_request pr ON pr.id = x.pr_id WHERE x.voucher_vnum = a.vnum LIMIT 1) AS req_type,
                                      (SELECT pr.payment_request_user FROM tpd_request_ext x JOIN payment_request pr ON pr.id = x.pr_id WHERE x.voucher_vnum = a.vnum LIMIT 1) AS prepared_by,
                                      (SELECT pr.accept_user FROM tpd_request_ext x JOIN payment_request pr ON pr.id = x.pr_id WHERE x.voucher_vnum = a.vnum LIMIT 1) AS mgmt_by
                               FROM tpd_bank_approval a JOIN voucher v ON v.Vnum = a.vnum LEFT JOIN tpd_bank_initiation i ON i.vnum = a.vnum
                               WHERE v.finalized = 1 ORDER BY a.approved_at DESC, a.vnum DESC LIMIT 1000")->getResultArray();
            $final = array_map(static fn ($r) => ['vnum' => (int) $r['vnum'], 'payee' => $r['S_name'], 'cat' => self::cat($r['req_type'] ?: null), 'tour' => $r['manual_id'], 'inq' => $inq($r['manual_id']),
                'paid_date' => $r['paid_date'], 'amount' => (float) $r['lkr_amount'], 'usd' => $r['usd_amount'] !== null ? (float) $r['usd_amount'] : null, 'charges' => $r['charges'] !== null ? (float) $r['charges'] : null,
                'total_debit' => (float) $r['total_debit'], 'bank_ref' => $r['bank_ref'], 'prepared_by' => $r['prepared_by'], 'mgmt_by' => $r['mgmt_by'], 'recorded_by' => $r['initiated_by'], 'finalized_by' => $r['approved_by'],
                'finalized_at' => $r['approved_at'], 'rate' => $r['usd_rate'] !== null ? (float) $r['usd_rate'] : null, 'fx_diff' => $r['fx_diff'] !== null ? (float) $r['fx_diff'] : null,
                'read_back' => $r['ledger_bank_credit'] !== null ? (float) $r['ledger_bank_credit'] : null, 'reconciled' => (int) $r['reconciled'] === 1, 'remittance' => $r['remit_status'], 'batch_id' => $r['batch_id'] !== null ? (int) $r['batch_id'] : null,
                'old_route' => stripos((string) $r['note'], 'Marked finalized on Bank Payment Check') === 0], $fin);

            // tf-finance-r6: the three things the reconciliation overview card reports
            $tfSetup = ['paying_account' => !empty($o['bank_accounts']['accounts']), 'ledger_mapping' => false, 'reconciliation' => !empty($o['reconciliation']['rows'])];
            try {
                $tfSetup['ledger_mapping'] = !empty(\App\Libraries\TourPayments\Batches::fxMapping($db)['complete']) && !empty(\App\Libraries\TourPayments\Batches::chargeMapping($db)['complete']);
            } catch (\Throwable $e) {
            }
            $d = ['today' => date('Y-m-d'), 'generated_at' => date('Y-m-d H:i:s'), 'user' => Perms::user(), 'approval' => $approval, 'ready' => $ready, 'final' => $final,
                'recon' => $o['reconciliation'] ?? ['rows' => []], 'bank_accounts' => $o['bank_accounts'] ?? ['accounts' => []], 'attention' => $o['exceptions']['items'] ?? [], 'setup' => $tfSetup, 'adoption_ready' => !empty($o['adoption_ready']),
                'counts' => ['approval' => count($approval), 'ready' => count($ready), 'final' => count($final), 'recon' => count(($o['reconciliation']['rows'] ?? [])), 'attention' => (int) ($o['counts']['exceptions'] ?? 0)]];
        } catch (\Throwable $e) {
            log_message('error', 'finance page: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return $this->json(['status' => 500, 'message' => 'The Finance page could not be built: ' . $e->getMessage()]);
        }
        return $this->json(['status' => 200, 'data' => Mask::bank($d)]);
    }
}
