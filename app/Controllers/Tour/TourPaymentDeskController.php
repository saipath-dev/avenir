<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\TourPayments\Audit;
use App\Libraries\TourPayments\BankAccounts;
use App\Libraries\TourPayments\Batches;
use App\Libraries\TourPayments\Bulk;
use App\Libraries\TourPayments\Documents;
use App\Libraries\TourPayments\PayDesk;
use App\Libraries\TourPayments\Perms;
use App\Libraries\TourPayments\RequestLink;
use App\Libraries\TourPayments\Mask;
use App\Libraries\TourPayments\Separation;
use App\Libraries\TourPayments\Schema;
use App\Libraries\TourPayments\SupplierBank;
use App\Libraries\TourPayments\SupplierAccounts;   // tf-bankreg-r1
use App\Libraries\TourFlow\Blocks;   // tf-stage8

/**
 * tf-paydesk-r1 (10 Oct 2026) — the Tour Payment & Accounting Workflow's endpoints, under tour/tour_payment_dashboard/.
 *
 * Every step that the chain already has (request, approve, voucher, authorise) is done by calling the existing
 * controller method for it, in-process, with the privilege checked here first; this file adds only what the chain
 * never had (bank initiation, the Paid lock, remittance advices, expenses, documents, the audit trail). Nothing in
 * the chain's own code is reimplemented.
 */
class TourPaymentDeskController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    /**
     * tf-paydesk-r7d (17 Oct 2026): every state-changing request to the desk (POST, PUT, PATCH, DELETE) must carry the page's
     * CSRF token. It is checked here, before the action and its privilege check run; GET pages and data are unchanged. The
     * actions the desk runs for itself (callSelf, callExisting) do not come through here — the outer request was checked.
     */
    public function _remap(string $method, ...$params)
    {
        $ok = $method !== '' && $method[0] !== '_' && method_exists($this, $method);
        if ($ok) {
            $rm = new \ReflectionMethod($this, $method);
            $ok = $rm->isPublic() && !$rm->isStatic() && $rm->getDeclaringClass()->getName() === self::class;
        }
        if (!$ok) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forMethodNotFound($method);
        }
        if ($why = \App\Libraries\TourPayments\Csrf::problem($this->request)) {
            return \App\Libraries\TourPayments\Csrf::refuse($this->db(), $this->request, $this->response, $why, $method);
        }
        return $this->$method(...$params);
    }

    /** tf-paydesk-r7d: a refused payment step leaves one audit line (who, which voucher, when, why). It posts nothing. */
    private function refused($db, string $action, int $vnum, ?array $v, string $reason, array $new = []): void
    {
        Audit::log($db, ['action' => $action, 'tour_id' => $v ? (string) $v['ref_po'] : null, 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum,
            'reason' => mb_substr('tf-paydesk-r7d: ' . $reason, 0, 500), 'new' => $new ?: null]);
    }

    /** tf-paydesk-r7d: preparing and sending a remittance advice is for the Accountant or the Bank payment approver (and the administrator). */
    private function canRemit(): bool
    {
        return Perms::can('accountant') || Perms::can('bank_approver');
    }

    /**
     * tf-paydesk-r7d: hands a remittance e-mail to the mail server. On the isolated test copy only, a TEST mail sink
     * (App\Libraries\TourPayments\MailSink — not part of the release for the usual system) can take it instead, so no
     * e-mail leaves the computer during tests. Returns [answer, sunk].
     */
    private function mailOut($db, string $to, string $subject, string $body, ?array $cc, array $att): array
    {
        $sink = '\\App\\Libraries\\TourPayments\\MailSink';
        if (class_exists($sink) && ($dir = $sink::dir($db))) {
            return [$sink::write($dir, $to, $subject, $body, $cc, $att, Perms::user()), true];
        }
        try {
            $r = (new \App\Controllers\Mail\SendController())->send($to, $subject, $body, $cc, $att);
        } catch (\Throwable $e) {
            $r = 'Could not send: ' . $e->getMessage();
        }
        return [$r, false];
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function json(array $p, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($p);
    }

    private function deny(string $what): \CodeIgniter\HTTP\ResponseInterface
    {
        // tf-pilot-r1: a refused action is recorded (who, which route, why) like a refused route
        try {
            Audit::log($this->db(), ['action' => 'access_denied', 'ref_type' => 'action', 'ref_id' => mb_substr((string) service('uri')->getRoutePath(), 0, 80), 'reason' => mb_substr('privilege: ' . $what, 0, 500), 'new' => ['method' => strtoupper($this->request->getMethod()), 'pr_id' => $this->in('pr_id'), 'vnum' => $this->in('vnum'), 'bulk_id' => $this->in('bulk_id')]]);
        } catch (\Throwable $e) {
        }
        return $this->json(['status' => 403, 'message' => $what . ' The attempt was recorded.'], 403);
    }

    private ?array $jsonBody = null;
    private bool $bulkContext = false;   // tf-paydesk-r5: set while the bulk endpoints run the per-voucher actions for a bulk batch's children

    /** A posted field: form data, or the JSON body (a remittance e-mail's HTML travels as JSON, which the strip-tags filter leaves alone). */
    private function in(string $k, $d = null)
    {
        $v = $this->request->getPost($k);
        if ($v === null && stripos((string) $this->request->getHeaderLine('Content-Type'), 'json') !== false) {
            if ($this->jsonBody === null) {
                try {
                    $j = $this->request->getJSON(true);
                    $this->jsonBody = is_array($j) ? $j : [];
                } catch (\Throwable $e) {
                    $this->jsonBody = [];
                }
            }
            $v = $this->jsonBody[$k] ?? null;
        }
        return $v === null ? $d : $v;
    }

    /** The existing dashboard data, by manual id (exact first, then the old partial match). */
    private function legacy(string $manualId): ?array
    {
        $c = new TourPaymentDashboardController();
        $c->initController($this->request, $this->response, service('logger'));
        return $c->dashboardData($manualId);
    }

    /** Runs an existing controller method in-process with the given POST fields; returns its JSON answer. */
    private function callExisting(string $class, string $method, array $post): array
    {
        // tf-paydesk-r7f: the called action now checks the page's token itself (Csrf::guard); hand it the token this request
        // carried — only when this request passed the same check (Csrf::forward)
        $post = \App\Libraries\TourPayments\Csrf::forward($this->request, $post);
        $req = clone $this->request;
        $req->setGlobal('post', $post);
        $req->setGlobal('request', $post);
        $res = new \CodeIgniter\HTTP\Response(config('App'));
        $c = new $class();
        $c->initController($req, $res, service('logger'));
        // tf-paydesk-r7h build 5.2 (S-2): the existing action knows it is run by the desk (after the desk's own checks)
        $wasDesk = Batches::$deskCall;
        Batches::$deskCall = true;
        try {
            $out = $c->$method();
        } finally {
            Batches::$deskCall = $wasDesk;
        }
        $body = $out instanceof \CodeIgniter\HTTP\ResponseInterface ? (string) $out->getBody() : (string) $out;
        $j = json_decode($body, true);
        return is_array($j) ? $j : ['status' => 500, 'message' => 'Unexpected answer from ' . $method, 'raw' => mb_substr(strip_tags($body), 0, 300)];
    }

    /** tf-paydesk-r5: run one of this controller's own POST actions with a given body (the bulk endpoints run the per-voucher ones for every child). */
    private function callSelf(string $method, array $post): array
    {
        $saved = $this->request;
        $savedJson = $this->jsonBody;
        try {
            // tf-paydesk-r7f: the per-voucher action may run an existing action in-process (Paid -> accounts/voucher_auth/update),
            // which checks the page's token itself; keep the token this request carried (Csrf::forward: only when it passed the check)
            $post = \App\Libraries\TourPayments\Csrf::forward($this->request, $post);
            $req = clone $this->request;
            $req->setGlobal('post', $post);
            $req->setGlobal('request', $post);
            $this->request = $req;
            $this->jsonBody = null;
            $out = $this->$method();
            $body = $out instanceof \CodeIgniter\HTTP\ResponseInterface ? (string) $out->getBody() : (string) $out;
            $j = json_decode($body, true);
            return is_array($j) ? $j : ['status' => 500, 'message' => 'Unexpected answer from ' . $method];
        } finally {
            $this->request = $saved;
            $this->jsonBody = $savedJson;
        }
    }

    // =====================================================================
    //  tf-paydesk-r5: bulk supplier payment — one transfer, one voucher per item
    // =====================================================================

    // GET bulk_eligibility?sup_acc= — the supplier's approved items with an eligibility verdict each, by currency
    public function bulk_eligibility()
    {
        if (!Perms::can('payables')) {
            return $this->deny('Needs a payment role.');
        }
        $sup = trim((string) $this->request->getGet('sup_acc'));
        if ($sup === '') {
            return $this->json(['status' => 400, 'message' => 'sup_acc is required']);
        }
        return $this->json(['status' => 200, 'data' => Bulk::eligibility($this->db(), $sup)]);
    }

    // POST dispute {pr_id, disputed (1|0), reason}
    public function dispute()
    {
        if (!Perms::can('approver') && !Perms::can('accountant')) {
            return $this->deny('Marking a payment as disputed needs the approval or the bank-initiation privilege.');
        }
        $r = Bulk::dispute($this->db(), (int) $this->in('pr_id', 0), (int) $this->in('disputed', 1) === 1, (string) $this->in('reason', ''), Perms::user());
        return $this->json($r['ok'] ? ['status' => 200, 'message' => 'Saved.'] : ['status' => 400, 'message' => $r['message']]);
    }

    // POST payables_release {pr_id, vnum, reason} — tf-paydesk-r7h: a request held in Supplier Payables because Payment Voucher No. vnum (same
    // supplier and tour, not linked to any request) may already be its payment. A holder of the batch privilege who checked that voucher
    // records, with a reason, that it is NOT this payment; the request can then go into a batch. Written once (tpd_meta), audited.
    public function payables_release()
    {
        if (!Perms::can('batch')) {
            return $this->deny('Releasing a held payment needs the batch-creation privilege (management).');
        }
        $db = $this->db();
        if (!Batches::accumulates($db)) {
            return $this->json(['status' => 409, 'message' => 'Supplier Payables is not set up on this install (run Setup).']);
        }
        $prId = (int) $this->in('pr_id', 0);
        if ($sep = Separation::check($db, 'release the hold on', [$prId])) {
            return $this->json(['status' => 403, 'message' => $sep, 'separation' => true], 403);
        }
        $r = RequestLink::release($db, $prId, (int) $this->in('vnum', 0), Perms::user(), (string) $this->in('reason', ''));
        return $this->json(['status' => $r['status']] + $r);
    }

    // POST payment_review {kind: request|line, decision: is|not, pr_id, vnum, tour_id, line_key, reason} — tf-paydesk-r7h build 5: a holder of the
    // batch privilege reviews an association that has no stored link — "this voucher IS the payment" or "this voucher is NOT the payment" —
    // with a reason. Written once (tpd_meta), audited; nothing else is changed (no voucher is created, no link is cleared, no ledger entry).
    public function payment_review()
    {
        if (!Perms::can('batch')) {
            return $this->deny('Reviewing a payment needs the batch-creation privilege (management).');
        }
        $db = $this->db();
        if (!Batches::accumulates($db)) {
            return $this->json(['status' => 409, 'message' => 'Supplier Payables is not set up on this install (run Setup).']);
        }
        $decision = (string) $this->in('decision', '');
        if (!in_array($decision, ['is', 'not'], true)) {
            return $this->json(['status' => 400, 'message' => 'Say whether the voucher IS or is NOT the payment.']);
        }
        $vnum = (int) $this->in('vnum', 0);
        $reason = (string) $this->in('reason', '');
        if ((string) $this->in('kind', 'request') === 'line') {
            $tourId = trim((string) $this->in('tour_id', ''));
            $lineKey = trim((string) $this->in('line_key', ''));
            // tf-paydesk-r7h build 5.2 (L-3): checked under the review lock, before anything is written
            $r = RequestLink::reviewLine($db, $tourId, $lineKey, $vnum, $decision, Perms::user(), $reason, fn () => $this->lineReviewTarget($db, $tourId, $lineKey, $vnum));
            return $this->json(['status' => $r['status']] + $r);
        }
        $prId = (int) $this->in('pr_id', 0);
        if ($sep = Separation::check($db, 'review the payment of', [$prId])) {
            return $this->json(['status' => 403, 'message' => $sep, 'separation' => true], 403);
        }
        $r = $decision === 'is' ? RequestLink::confirm($db, $prId, $vnum, Perms::user(), $reason) : RequestLink::release($db, $prId, $vnum, Perms::user(), $reason);
        return $this->json(['status' => $r['status']] + $r);
    }

    /**
     * tf-paydesk-r7h build 5.2 (L-3): null when the Tour Payment Dashboard of this tour — built now from the same data the dashboard
     * shows — has the line $lineKey with Payment Voucher No. $vnum as its unverified association; otherwise the reason. A fabricated
     * line key, a line of another tour, another supplier's line or a voucher the line does not list is refused.
     */
    private function lineReviewTarget($db, string $tourId, string $lineKey, int $vnum): ?string
    {
        $c = $tourId !== '' ? $db->query("SELECT tour_id, manual_id FROM consignee WHERE tour_id = ? LIMIT 1", [$tourId])->getRowArray() : null;
        if (!$c || trim((string) $c['manual_id']) === '') {
            return 'Tour ' . $tourId . ' was not found.';
        }
        $data = $this->legacy(trim((string) $c['manual_id']));
        if (!$data || (string) ($data['tour_info']['tour_id'] ?? '') !== (string) $c['tour_id']) {
            return 'The Tour Payment Dashboard of tour ' . $c['manual_id'] . ' could not be built.';
        }
        $desk = (new PayDesk($db, Perms::user()))->build($data);
        $line = null;
        foreach ($desk['lines'] ?? [] as $l) {
            if ((string) ($l['key'] ?? '') === $lineKey) {
                $line = $l;
                foreach ($l['transactions'] ?? [] as $t) {
                    $rv = $t['review'] ?? null;
                    if (!empty($t['unverified']) && is_array($rv) && ($rv['kind'] ?? '') === 'line' && (int) ($rv['vnum'] ?? 0) === $vnum && (string) ($rv['line_key'] ?? '') === $lineKey) {
                        return null;
                    }
                }
            }
        }
        return $line ? 'Payment Voucher No. ' . $vnum . ' is not shown as an unverified voucher of this line on the Tour Payment Dashboard of tour ' . $c['manual_id'] . '.'
            : 'The line reference "' . $lineKey . '" is not a line of the Tour Payment Dashboard of tour ' . $c['manual_id'] . '.';
    }

    /**
     * tf-paydesk-r7h build 5.2 (D-3): the requests a voucher pays, for the separation-of-duties checks — its desk request record(s) and
     * supplier batch (Separation) and every stored link RequestLink knows (a review that confirmed the voucher, a voucher line naming the
     * request). Before 5.2 a voucher linked by review or by a voucher line was checked against no request.
     */
    private function requestsPaidBy($db, int $vnum, ?array $v): array
    {
        $ids = Separation::requestsOfVoucher($db, $vnum);
        if ($v) {
            try {
                foreach (RequestLink::ofVoucher($db, $v, false)['requests'] as $q) {
                    $ids[] = (int) $q['id'];
                }
            } catch (\Throwable $e) {
            }
        }
        return array_values(array_unique(array_map('intval', $ids)));
    }

    // POST bulk_create {sup_acc, pr_ids[], currency, bank_account_id, submit_token, note}
    public function bulk_create()
    {
        if (!Perms::can('batch')) {
            return $this->deny('Creating a bulk supplier payment needs the batch-creation privilege (management).');
        }
        $db = $this->db();
        if (!Batches::accumulates($db) || Schema::version($db) < 7) {
            return $this->json(['status' => 409, 'message' => 'Bulk payment is not set up on this install (run Setup).']);
        }
        $sup = trim((string) $this->in('sup_acc', ''));
        $ids = (array) $this->in('pr_ids', []);
        $currency = strtoupper(trim((string) $this->in('currency', 'LKR')));
        $acct = $this->payingAccount();
        if (!$acct) {
            return $this->json(['status' => 400, 'message' => 'Suggest the Avenir bank account the transfer should be made from (the accountant confirms the actual account at initiation).']);
        }
        // the supplier's bank must be verified, or confirmed now by a holder of the verification privilege (as for a single batch)
        $el = Bulk::eligibility($db, $sup);
        $bank = $el['bank'];
        $hasBankBlocked = false;
        foreach ((is_array($el['currencies']) ? ($el['currencies'][$currency]['excluded'] ?? []) : []) as $e) {   // tf-paydesk-r7h build 5 (V-9)
            if (in_array($e['reason'], ['bank_unverified', 'bank_changed'], true) && in_array((int) $e['pr_id'], array_map('intval', $ids), true)) {
                $hasBankBlocked = true;
            }
        }
        // tf-stage5: an unverified (or changed) account no longer stops the bulk payment being created and is never "confirmed now" here —
        // the bank release refuses until a Finance & Approvals Management Approver verifies the exact account in the registry
        if ($hasBankBlocked) {
            Audit::log($db, ['action' => 'bulk_created_bank_unverified', 'ref_type' => 'supplier', 'ref_id' => $sup, 'reason' => 'Bank account ' . (($bank['status'] ?? '') === 'changed' ? 'changed since verification' : 'not verified') . ' at bulk creation; release waits for verification']);
        }
        if ($sep = Separation::check($db, 'bulk', array_map('intval', $ids))) {   // tf-paydesk-r5
            return $this->json(['status' => 403, 'message' => $sep, 'separation' => true], 403);
        }
        if ($why = Blocks::paymentRefusal($db, $sup, null, 'bulk payment creation', Perms::user(), 'requests ' . implode(',', array_map('intval', $ids)))) {   // tf-stage8
            Audit::log($db, ['action' => 'blocked_refused', 'ref_type' => 'supplier', 'ref_id' => $sup, 'reason' => mb_substr($why, 0, 500)]);
            return $this->json(['status' => 428, 'message' => $why, 'blocked' => true]);
        }
        $r = Bulk::create($db, $sup, $ids, $currency, $acct, (string) $this->in('submit_token', ''), Perms::user(), trim((string) $this->in('note', '')) ?: null,
            ['acc_no' => session()->get('acc_no'), 'username' => session()->get('username')]);
        return $this->json(['status' => $r['status']] + $r);
    }

    // GET bulk_data/{id}
    public function bulk_data(int $id)
    {
        if (!Perms::can('payables')) {
            return $this->deny('Needs a payment role.');
        }
        $d = Bulk::detail($this->db(), $id);
        if (!$d) {
            return $this->json(['status' => 404, 'message' => 'Bulk batch not found.']);
        }
        $d['perms'] = Perms::all();
        $d['today_rate'] = $d['is_usd'] ? Batches::rateFor($this->db(), date('Y-m-d')) : null;
        return $this->json(['status' => 200, 'data' => $d]);
    }

    // GET bulk/{id} — the bulk batch page
    public function bulk(int $id)
    {
        if (!Perms::can('payables')) {
            return $this->deny('Needs a payment role.');
        }
        $d = Bulk::detail($this->db(), $id);
        if (!$d) {
            return $this->response->setStatusCode(404)->setBody('Bulk batch not found.');
        }
        $data = (new \App\Libraries\MenuLoader())->loadMenuData();
        $data['tpd_perms'] = Perms::all();
        $data['bulk_id'] = $id;
        $data['batch_no'] = $d['batch']['batch_no'];
        $data['bank_accounts'] = BankAccounts::all($this->db());
        return view('pages/tour/tpd_bulk', $data);
    }

    // POST bulk_initiate {bulk_id, bank_account_id, payment_date, bank_ref, charges, note, account_reason}
    // The accountant initiates the ONE bank transfer: the same date, account, rate and reference for every voucher; the charges
    // entered once are allocated across the vouchers to the cent. Resumable: vouchers already initiated are skipped.
    public function bulk_initiate()
    {
        // tf-paydesk-r7f: recording a bank initiation needs the explicit initiation privilege (report/bank_payment_check/initiate);
        // the Bank Payment Check page alone (which the Bank payment approver also holds) no longer counts. Separation is unchanged.
        if (!Perms::can('initiator')) {
            return $this->deny('Recording a bank initiation needs the Bank payment initiation privilege (report/bank_payment_check/initiate).');
        }
        $db = $this->db();
        $id = (int) $this->in('bulk_id', 0);
        $d = Bulk::detail($db, $id);
        if (!$d) {
            return $this->json(['status' => 404, 'message' => 'Bulk batch not found.']);
        }
        $lock = 'tpd_bulk_' . $id;
        $got = $db->query("SELECT GET_LOCK(?, 20) AS g", [$lock])->getRowArray();
        if (!$got || (int) $got['g'] !== 1) {
            return $this->json(['status' => 409, 'message' => 'This bulk batch is being processed by someone else at this moment. Try again in a few seconds.']);
        }
        try {
            $pending = array_values(array_filter($d['children'], static fn ($c) => $c['stage'] === 'issued'));
            foreach ($pending as $tfC) {   // tf-bankreg-r1: the whole transfer waits while any payee account is incomplete or flagged
                if ($tfChk = SupplierAccounts::initiationCheck($db, (int) $tfC['vnum'])) {
                    return $this->json(['status' => 428, 'message' => 'Payment Voucher No. ' . (int) $tfC['vnum'] . ': ' . $tfChk['message'] . ' Nothing was written.', 'vnum' => (int) $tfC['vnum'], 'account_review' => $tfChk['review'] ?? null, 'needs_account' => true]);
                }
            }
            $p = $d['batch'];
            // a RESUMED initiation (some vouchers already initiated, or a first attempt that recorded the transfer) keeps the
            // transfer details recorded on the batch — date, reference, charges, rate — so every voucher carries the same ones
            $resume = !empty($p['initiated_at']) || ($d['counts']['initiated'] + $d['counts']['paid']) > 0;
            if ($resume && !$pending) {
                $d2 = Bulk::detail($db, $id);
                $complete = $d2['counts']['initiated'] + $d2['counts']['paid'] === $d2['count'];
                if ($complete && $p['status'] === 'issued') {
                    $db->query("UPDATE tpd_batch SET status = 'initiated', updated_at = ? WHERE id = ?", [date('Y-m-d H:i:s'), $id]);
                }
                return $this->json(['status' => 200, 'message' => 'Nothing to initiate: all ' . $d2['count'] . ' vouchers of batch ' . $p['batch_no'] . ' are already initiated (' . $d2['counts']['paid'] . ' Paid). The recorded transfer details were kept.',
                    'initiated' => [], 'errors' => [], 'complete' => $complete, 'transfer_lkr' => (float) ($p['transfer_lkr'] ?? 0), 'charges_total' => (float) ($p['charges_total'] ?? 0), 'shares' => [], 'already' => true]);
            }
            $paymentDate = $resume && !empty($p['payment_date']) ? (string) $p['payment_date'] : ($this->date($this->in('payment_date')) ?: date('Y-m-d'));
            $chargesIn = $this->in('charges');
            $charges = $resume && $p['charges_total'] !== null ? round((float) $p['charges_total'], 2) : ($chargesIn !== null && trim((string) $chargesIn) !== '' ? round((float) str_replace(',', '', (string) $chargesIn), 2) : 0.0);
            if ($charges < 0 || !is_finite($charges)) {
                return $this->json(['status' => 400, 'message' => 'Bank charges must be 0.00 or a positive LKR amount.']);
            }
            $bankRef = $resume && trim((string) ($p['bank_ref'] ?? '')) !== '' ? (string) $p['bank_ref'] : $this->in('bank_ref');
            $rate = null;
            if ($d['is_usd']) {
                if ($resume && !empty($p['usd_rate'])) {
                    $rate = (float) $p['usd_rate'];
                } else {
                    $pr = $this->paymentRate($db, $paymentDate, 'initiate the bulk transfer');
                    if (isset($pr['error'])) {
                        return $this->json($pr['error']);
                    }
                    $rate = $pr['rate'];
                }
            }
            $shares = Bulk::allocateCharges($db, $d['children'], $charges, $rate);
            $done = [];
            $errors = [];
            $this->bulkContext = true;
            foreach ($pending as $c) {
                $j = $this->callSelf('initiate', ['vnum' => $c['vnum'], 'bank_account_id' => $this->in('bank_account_id'), 'payment_date' => $paymentDate, 'bank_ref' => $bankRef, 'batch_ref' => $d['batch']['batch_no'],
                    'charges' => number_format((float) ($shares[(string) $c['vnum']] ?? 0), 2, '.', ''), 'note' => $this->in('note'), 'account_reason' => $this->in('account_reason'), 'initiated_at' => $this->in('initiated_at')]);
                if ((int) ($j['status'] ?? 0) === 200) {
                    $done[] = $c['vnum'];
                } else {
                    $errors[] = 'PV ' . $c['vnum'] . ': ' . ($j['message'] ?? 'failed');
                    if (!empty($j['needs_account_reason']) || !empty($j['needs_rate'])) {
                        return $this->json($j + ['bulk_done' => $done]);
                    }
                    break;   // stop at the first real error; what is done stays done and the batch shows "initiation incomplete"
                }
            }
            $d2 = Bulk::detail($db, $id);
            $complete = $d2['counts']['initiated'] + $d2['counts']['paid'] === $d2['count'];
            $transferLkr = round(array_sum(array_map(static fn ($c) => (float) $db->query("SELECT amount FROM tpd_bank_initiation WHERE vnum = ?", [$c['vnum']])->getRowArray()['amount'] ?? 0, $d2['children'])), 2);
            $acct = $this->payingAccount();
            $db->query("UPDATE tpd_batch SET status = ?, payment_date = ?, usd_rate = ?, bank_ref = ?, charges_total = ?, charges_allocated = ?, transfer_lkr = ?, total_debit = ?, bank_account_id = ?, ledger_code = ?, initiated_by = COALESCE(initiated_by, ?), initiated_at = COALESCE(initiated_at, ?), updated_at = ? WHERE id = ?",
                [$complete ? 'initiated' : 'issued', $paymentDate, $rate, trim((string) $bankRef) ?: null, $charges, round(array_sum($shares), 2), $transferLkr, round($transferLkr + $charges, 2), $acct['id'] ?? null, $acct['ledger_code'] ?? null, Perms::user(), date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
            Audit::log($db, ['action' => $complete ? 'bulk_initiated' : 'bulk_initiation_incomplete', 'ref_type' => 'batch', 'ref_id' => $d['batch']['batch_no'], 'bank_account' => $acct['label'] ?? null, 'bank_ref' => trim((string) $bankRef) ?: null, 'usd_rate' => $rate,
                'new' => ['vouchers_initiated_now' => $done, 'already' => $d['counts']['initiated'], 'payment_date' => $paymentDate, 'charges_total' => $charges, 'shares' => $shares, 'transfer_lkr' => $transferLkr, 'errors' => $errors]]);
            return $this->json(['status' => $complete ? 200 : 207, 'message' => ($complete ? 'Bank transfer initiated for the whole batch: ' : 'Initiation incomplete — ') . count($done) . ' voucher(s) initiated now' . ($d['counts']['initiated'] ? ', ' . $d['counts']['initiated'] . ' already' : '') . '; transfer LKR ' . number_format($transferLkr, 2) . ($charges > 0 ? ' + charges ' . number_format($charges, 2) . ' allocated to the cent' : '') . '.' . ($errors ? ' ' . implode(' ', $errors) : ''),
                'initiated' => $done, 'errors' => $errors, 'complete' => $complete, 'transfer_lkr' => $transferLkr, 'charges_total' => $charges, 'shares' => $shares]);
        } finally {
            $this->bulkContext = false;
            try {
                $db->query("SELECT RELEASE_LOCK(?)", [$lock]);
            } catch (\Throwable $e) {
            }
        }
    }

    // POST bulk_paid {bulk_id, paid_date, bank_ref, bank_account_id, confirm_account, note}
    // The bank approver marks the whole transfer Paid: every voucher goes through the r4 Paid (lock, final rate, FX pair, charge
    // share, legacy finalize, ledger check) under the batch lock; resumable; the batch is reconciled only when every voucher is.
    public function bulk_paid()
    {
        if (!Perms::can('bank_approver')) {
            return $this->deny('Only a holder of the Bank payment approver privilege can mark a payment Paid.');
        }
        $db = $this->db();
        $id = (int) $this->in('bulk_id', 0);
        $d = Bulk::detail($db, $id);
        if (!$d) {
            return $this->json(['status' => 404, 'message' => 'Bulk batch not found.']);
        }
        if ($d['counts']['issued'] > 0) {
            return $this->json(['status' => 409, 'message' => $d['counts']['issued'] . ' voucher(s) of this batch are not initiated yet. The accountant initiates the whole transfer first.']);
        }
        // tf-paydesk-r5 separation of duties, checked for the WHOLE batch before anything is marked: not the requester of any
        // item, and not the user who initiated the transfer (the per-voucher check inside Paid remains as the second line)
        $prIds = array_values(array_unique(array_filter(array_map(static fn ($c) => (int) ($c['pr_id'] ?? 0), $d['children']))));
        if ($sep = Separation::check($db, 'paid', $prIds)) {
            return $this->json(['status' => 403, 'message' => $sep, 'separation' => true], 403);
        }
        $me = trim((string) Perms::user());
        if ($me !== '' && strcasecmp(trim((string) ($d['batch']['initiated_by'] ?? '')), $me) === 0) {
            $why = 'Separation of duties: you initiated the bank transfer for bulk batch ' . $d['batch']['batch_no'] . ' yourself, so you cannot finally authorise (Paid) it. The bank authoriser must be a different user.';
            Audit::log($db, ['action' => 'separation_blocked', 'user' => $me, 'ref_type' => 'batch', 'ref_id' => $d['batch']['batch_no'], 'reason' => $why, 'new' => ['step' => 'paid', 'bulk_id' => $id]]);
            return $this->json(['status' => 403, 'message' => $why, 'separation' => true], 403);
        }
        $lock = 'tpd_bulk_' . $id;
        $got = $db->query("SELECT GET_LOCK(?, 30) AS g", [$lock])->getRowArray();
        if (!$got || (int) $got['g'] !== 1) {
            return $this->json(['status' => 409, 'message' => 'This bulk batch is being processed by someone else at this moment. Try again in a few seconds.']);
        }
        try {
            $pending = array_values(array_filter($d['children'], static fn ($c) => $c['stage'] === 'initiated'));
            $done = [];
            $errors = [];
            $this->bulkContext = true;
            foreach ($pending as $c) {
                $j = $this->callSelf('paid', ['vnum' => $c['vnum'], 'paid_date' => $this->in('paid_date'), 'bank_ref' => $this->in('bank_ref'), 'bank_account_id' => $this->in('bank_account_id'), 'confirm_account' => $this->in('confirm_account'), 'note' => $this->in('note'), 'account_reason' => $this->in('account_reason')]);
                if ((int) ($j['status'] ?? 0) === 200) {
                    $done[] = $c['vnum'];
                } else {
                    $errors[] = 'PV ' . $c['vnum'] . ': ' . ($j['message'] ?? 'failed');
                    if (!empty($j['needs_rate']) || !empty($j['needs_account_reason']) || !empty($j['config_warning'])) {
                        return $this->json($j + ['bulk_done' => $done]);
                    }
                    break;
                }
            }
            $rc = Bulk::reconcile($db, $id, Perms::user());
            $d2 = Bulk::detail($db, $id);
            $allPaid = $d2['counts']['paid'] === $d2['count'];
            $db->query("UPDATE tpd_batch SET status = ?, paid_by = COALESCE(paid_by, ?), paid_at = COALESCE(paid_at, ?), updated_at = ? WHERE id = ?", [$allPaid ? 'paid' : 'initiated', Perms::user(), date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
            Audit::log($db, ['action' => $allPaid ? 'bulk_paid' : 'bulk_paid_incomplete', 'ref_type' => 'batch', 'ref_id' => $d['batch']['batch_no'], 'bank_ref' => (string) $this->in('bank_ref', ''), 'new' => ['vouchers_paid_now' => $done, 'already' => $d['counts']['paid'], 'errors' => $errors, 'reconciled' => $rc['ok']]]);
            return $this->json(['status' => $allPaid ? 200 : 207, 'message' => ($allPaid ? 'Paid: all ' . $d2['count'] . ' vouchers of batch ' . $d['batch']['batch_no'] : 'Paid incomplete — ' . $d2['counts']['paid'] . ' of ' . $d2['count'] . ' vouchers') . ' (' . count($done) . ' marked now). ' . ($rc['ok'] ? 'Ledger balanced over the batch: credits to the paying account ' . number_format($rc['bank_credit'], 2) . ' = the bank debit.' : 'Ledger check not yet balanced — see the batch page; re-run Paid to post what is missing.') . ' This is not a bank-statement match (Bank Reconciliation Step 2).' . ($errors ? ' ' . implode(' ', $errors) : ''),
                'paid' => $done, 'errors' => $errors, 'complete' => $allPaid, 'reconciliation' => $rc]);
        } finally {
            $this->bulkContext = false;
            try {
                $db->query("SELECT RELEASE_LOCK(?)", [$lock]);
            } catch (\Throwable $e) {
            }
        }
    }

    // POST bulk_remittance_prepare {bulk_id} — ONE itemised advice for the whole transfer (every voucher listed)
    public function bulk_remittance_prepare()
    {
        if (!$this->canRemit()) {   // tf-paydesk-r7d: Accountant or Bank payment approver only (was: any viewer)
            return $this->deny('Preparing or sending a remittance advice needs the Accountant (Bank Payment Check) or the Bank payment approver privilege.');
        }
        $db = $this->db();
        $id = (int) $this->in('bulk_id', 0);
        $d = Bulk::detail($db, $id);
        if (!$d) {
            return $this->json(['status' => 404, 'message' => 'Bulk batch not found.']);
        }
        if ($d['counts']['paid'] !== $d['count'] || $d['count'] === 0) {
            return $this->json(['status' => 409, 'message' => 'The remittance advice is prepared only after every voucher of the batch is Paid (' . $d['counts']['paid'] . ' of ' . $d['count'] . ').']);
        }
        $rem = $d['remittance'];
        if ($rem && $rem['status'] === 'sent') {
            return $this->json(['status' => 409, 'message' => 'The advice for batch ' . $d['batch']['batch_no'] . ' was already sent on ' . $rem['sent_at'] . ' to ' . $rem['to_email'] . '. It is not sent twice.', 'remittance' => $rem]);
        }
        $docs = new Documents($db);
        if (!$rem) {
            $db->query("INSERT INTO tpd_remittance (vnum, batch_id, tour_id, sup_acc, supplier_name, status, created_at) VALUES (NULL, ?, ?, ?, ?, 'pending', ?)", [$id, implode(',', array_slice($d['tours'], 0, 10)), (string) $d['batch']['sup_acc'], (string) $d['batch']['supplier_name'], date('Y-m-d H:i:s')]);
        }
        $sup = $d['supplier'];
        $name = trim((string) ($sup['supplier_name'] ?? $d['batch']['supplier_name']));
        $first = $d['children'][0]['vnum'];
        $last = end($d['children'])['vnum'];
        $subject = 'Payment completed — ' . count($d['children']) . ' invoices · Payment Vouchers No. ' . $first . '–' . $last . ' · bulk batch ' . $d['batch']['batch_no'];
        $lkr = $d['totals']['paid_lkr'] > 0 ? $d['totals']['paid_lkr'] : ($d['totals']['transfer_lkr'] ?? $d['totals']['voucher_total']);
        $body = '<p>Dear ' . esc($name) . ',</p><p>We confirm that the ' . count($d['children']) . ' payments listed in the attached remittance advice have been settled by <b>one bank transfer</b> of <b>LKR ' . number_format((float) $lkr, 2) . '</b>' . ($d['is_usd'] ? ' (USD ' . number_format((float) $d['totals']['transfer_total'], 2) . ')' : '') . ' on ' . esc((string) $d['batch']['payment_date']) . ($d['batch']['bank_ref'] ? ' (bank reference ' . esc((string) $d['batch']['bank_ref']) . ')' : '') . '. Each item carries its own Payment Voucher number (' . $first . '–' . $last . ') for your reconciliation.</p>'
            . '<table cellpadding="4" style="border-collapse:collapse;font-size:12.5px;border:1px solid #ddd;"><tr style="background:#f3e7f3;"><th align="left">Voucher</th><th align="left">Tour</th><th align="left">Guest</th><th align="left">Service</th><th align="left">Invoice</th><th align="right">Amount</th></tr>';
        foreach ($d['children'] as $c) {
            $body .= '<tr><td><b>' . (int) $c['vnum'] . '</b></td><td>' . esc((string) $c['manual_id']) . '</td><td>' . esc((string) $c['guest']) . '</td><td>' . esc((string) $c['kind']) . ($c['service_dates'] ? ' · ' . esc($docs->dateSpan($c['service_dates'])) : '') . '</td><td>' . esc((string) ($c['invoice_no'] ?: '—')) . '</td><td align="right">' . ($c['currency'] === 'USD' ? 'USD ' . number_format((float) $c['usd_amount'], 2) : 'LKR ' . number_format((float) $c['lkr_amount'], 2)) . '</td></tr>';
        }
        $body .= '<tr style="background:#f3e7f3;font-weight:bold;"><td colspan="5" align="right">Total</td><td align="right">' . ($d['is_usd'] ? 'USD ' . number_format((float) $d['totals']['transfer_total'], 2) : 'LKR ' . number_format((float) $d['totals']['voucher_total'], 2)) . '</td></tr></table><p>Kind regards,<br>Avenir Holidays</p>';
        $html = view('pages/tour/tpd_bulk_remittance', ['d' => $d, 'company' => $docs->company(), 'for_pdf' => true]);
        $pdf = $docs->pdf($html, 'Remittance-advice-' . $d['batch']['batch_no']);
        $path = null;
        if ($pdf !== null) {
            $path = Documents::dir('remittance') . 'remittance-bulk-' . $id . '.pdf';
            file_put_contents($path, $pdf);
        }
        $to = $sup['remittance_email'] ?? ($sup['master_email'] ?? '');
        $db->query("UPDATE tpd_remittance SET status = 'prepared', to_email = COALESCE(to_email, ?), subject = ?, body = ?, pdf_path = ?, prepared_by = ?, prepared_at = ? WHERE batch_id = ? AND vnum IS NULL AND status <> 'sent'", [$to ?: null, $subject, $body, $path, Perms::user(), date('Y-m-d H:i:s'), $id]);
        $rem = $db->query("SELECT * FROM tpd_remittance WHERE batch_id = ? AND vnum IS NULL ORDER BY id DESC LIMIT 1", [$id])->getRowArray();
        Audit::log($db, ['action' => 'remittance_prepared', 'ref_type' => 'batch', 'ref_id' => $d['batch']['batch_no'], 'new' => ['to' => $rem['to_email'], 'subject' => $subject, 'vouchers' => array_column($d['children'], 'vnum'), 'pdf' => $path ? basename($path) : null]]);
        return $this->json(['status' => 200, 'remittance' => ['id' => (int) $rem['id'], 'bulk_id' => $id, 'status' => $rem['status'], 'to' => $rem['to_email'] ?: $to, 'subject' => $subject, 'body' => $body, 'pdf' => $path ? base_url('tour/tour_payment_dashboard/bulk_remittance/' . $id . '?pdf=1') : null, 'preview' => base_url('tour/tour_payment_dashboard/bulk_remittance/' . $id), 'pdf_missing' => $pdf === null, 'items' => count($d['children'])]]);
    }

    // POST bulk_remittance_send {bulk_id, to, cc, subject, body}
    public function bulk_remittance_send()
    {
        if (!$this->canRemit()) {   // tf-paydesk-r7d: Accountant or Bank payment approver only (was: any viewer)
            return $this->deny('Preparing or sending a remittance advice needs the Accountant (Bank Payment Check) or the Bank payment approver privilege.');
        }
        // tf-paydesk-r7h build 5 (V-6): one send at a time per bulk advice
        return PayDesk::withItemLock($this->db(), 'bulk-remit|' . (int) $this->in('bulk_id', 0), fn () => $this->bulkRemittanceSendOnce())
            ?? $this->json(['status' => 409, 'message' => 'This advice is being sent at this moment. Reload the page. Nothing was sent.']);
    }

    private function bulkRemittanceSendOnce()
    {
        $db = $this->db();
        $id = (int) $this->in('bulk_id', 0);
        $rem = $db->query("SELECT * FROM tpd_remittance WHERE batch_id = ? AND vnum IS NULL ORDER BY id DESC LIMIT 1", [$id])->getRowArray();
        if (!$rem) {
            return $this->json(['status' => 404, 'message' => 'Prepare the advice first.']);
        }
        if ($rem['status'] === 'sent') {
            return $this->json(['status' => 409, 'message' => 'Already sent on ' . $rem['sent_at'] . ' to ' . $rem['to_email'] . '. It is not sent twice.']);
        }
        $to = SupplierBank::email($this->in('to', $rem['to_email']));
        if (!$to) {
            return $this->json(['status' => 400, 'message' => 'Enter the supplier\'s e-mail address.']);
        }
        $cc = array_values(array_filter(array_map([SupplierBank::class, 'email'], preg_split('/[,;\s]+/', (string) $this->in('cc', '')))));
        $subject = trim((string) $this->in('subject', $rem['subject'])) ?: (string) $rem['subject'];
        $body = (string) $this->in('body', $rem['body']) ?: (string) $rem['body'];
        $att = $rem['pdf_path'] && is_file($rem['pdf_path']) ? [['path' => $rem['pdf_path'], 'name' => 'Remittance-advice-bulk-' . $id . '.pdf']] : [];
        $db->query("UPDATE tpd_remittance SET to_email = ?, cc = ?, subject = ?, body = ? WHERE id = ?", [$to, $cc ? implode(', ', $cc) : null, $subject, $body, (int) $rem['id']]);
        [$r, $sunk] = $this->mailOut($db, $to, $subject, $body, $cc ?: null, $att);   // tf-paydesk-r7d
        $d = Bulk::detail($db, $id);
        if ($r === 200 || $r === '200') {
            $db->query("UPDATE tpd_remittance SET status = 'sent', sent_by = ?, sent_at = ?, error = NULL WHERE id = ?", [Perms::user(), date('Y-m-d H:i:s'), (int) $rem['id']]);
            // every child voucher is now notified too (the per-voucher record points at the batch advice)
            foreach ($d['children'] as $c) {
                // the per-voucher record (Paid leaves one 'pending') now points at the batch advice; a record already sent is left as it is
                $db->query("INSERT INTO tpd_remittance (vnum, batch_id, tour_id, sup_acc, supplier_name, status, to_email, subject, pdf_path, sent_by, sent_at, created_at) VALUES (?,?,?,?,?,'sent',?,?,?,?,?,?)
                            ON DUPLICATE KEY UPDATE batch_id = IF(status = 'sent', batch_id, VALUES(batch_id)), to_email = IF(status = 'sent', to_email, VALUES(to_email)), subject = IF(status = 'sent', subject, VALUES(subject)), pdf_path = IF(status = 'sent', pdf_path, VALUES(pdf_path)),
                            sent_by = IF(status = 'sent', sent_by, VALUES(sent_by)), sent_at = IF(status = 'sent', sent_at, VALUES(sent_at)), error = IF(status = 'sent', error, NULL), status = 'sent'",
                    [$c['vnum'], $id, (string) $c['tour_id'], (string) $d['batch']['sup_acc'], (string) $d['batch']['supplier_name'], $to, $subject, $rem['pdf_path'], Perms::user(), date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
                try {
                    $db->table('tpd_document')->insert(['tour_id' => (string) $c['tour_id'], 'kind' => 'remittance', 'ref_type' => 'voucher', 'ref_id' => (string) $c['vnum'], 'vnum' => $c['vnum'], 'number' => $d['batch']['batch_no'], 'title' => 'Consolidated remittance advice sent to ' . $to, 'file_path' => $rem['pdf_path'], 'uploaded_by' => Perms::user(), 'uploaded_at' => date('Y-m-d H:i:s')]);
                } catch (\Throwable $e) {
                }
            }
            Batches::setStatus($db, $id, 'notified');
            Audit::log($db, ['action' => 'remittance_sent', 'ref_type' => 'batch', 'ref_id' => $d['batch']['batch_no'], 'new' => ['to' => $to, 'cc' => $cc, 'subject' => $subject, 'vouchers' => array_column($d['children'], 'vnum')] + ($sunk ? ['test_mail_sink' => true] : [])]);
            return $this->json(['status' => 200, 'test_mail_sink' => $sunk, 'message' => ($sunk ? '[TEST mail sink — no e-mail left the computer] ' : '') . 'Sent to ' . $to . ' — one advice covering ' . count($d['children']) . ' vouchers. The supplier is now marked Notified for all of them.']);
        }
        $err = $r === 404 || $r === '404' ? 'Your account has no mailbox set up to send from (e-mail, password and host on your user profile).' : (is_string($r) ? $r : 'The mail server did not accept the message.');
        $db->query("UPDATE tpd_remittance SET status = 'failed', error = ? WHERE id = ?", [mb_substr($err, 0, 500), (int) $rem['id']]);
        Audit::log($db, ['action' => 'remittance_failed', 'ref_type' => 'batch', 'ref_id' => $d['batch']['batch_no'] ?? $id, 'new' => ['to' => $to, 'error' => $err]]);
        return $this->json(['status' => 500, 'message' => 'Not sent: ' . $err . ' The advice stays pending.']);
    }

    // GET audit_log?action=&user=&since=&limit= — tf-pilot-r1: the payment desk's audit trail, newest first, READ-ONLY.
    // The administrator, the Accounts manager (Avenir Bank Accounts privilege) and any read-only / auditor profile.
    public function audit_log()
    {
        $profile = strtolower((string) (Perms::menu()['sidebar_employee_name_profile_name'] ?? ''));
        if (!Perms::isAdmin() && !Perms::can('bank_master') && !str_contains($profile, 'auditor') && !str_contains($profile, 'read-only')) {
            return $this->deny('The audit trail is for the administrator, the Accounts manager and the auditor.');
        }
        $db = $this->db();
        $where = [];
        $bind = [];
        foreach (['action', 'user', 'ref_type', 'manual_id'] as $k) {
            $v = trim((string) ($this->request->getGet($k) ?? ''));
            if ($v !== '') {
                $where[] = "`{$k}` = ?";
                $bind[] = $v;
            }
        }
        if ($since = $this->date($this->request->getGet('since'))) {
            $where[] = '`at` >= ?';
            $bind[] = $since . ' 00:00:00';
        }
        $limit = max(1, min(500, (int) ($this->request->getGet('limit') ?? 100)));
        $rows = $db->query("SELECT id, at, user, action, ref_type, ref_id, tour_id, manual_id, vnum, reason, ip, new_value FROM tpd_audit" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY id DESC LIMIT {$limit}", $bind)->getResultArray();
        foreach ($rows as &$r) {
            $r['new_value'] = $r['new_value'] ? (json_decode((string) $r['new_value'], true) ?? $r['new_value']) : null;
        }
        unset($r);
        // tf-pilot-r3: account numbers inside the recorded values to their last four digits, except for the roles that pay or verify
        return $this->json(['status' => 200, 'count' => count($rows), 'rows' => \App\Libraries\TourPayments\Mask::bank($rows, true)]);
    }

    // GET bulk_remittance/{id}[?pdf=1]
    public function bulk_remittance(int $id)
    {
        if (!Perms::can('viewer')) {
            return $this->deny('Needs a Tour Payment Dashboard role.');
        }
        $db = $this->db();
        $d = Bulk::detail($db, $id);
        if (!$d) {
            return $this->response->setStatusCode(404)->setBody('Bulk batch not found.');
        }
        $docs = new Documents($db);
        if ($this->request->getGet('pdf')) {
            $html = view('pages/tour/tpd_bulk_remittance', ['d' => $d, 'company' => $docs->company(), 'for_pdf' => true]);
            $pdf = $docs->pdf($html, 'Remittance-advice-' . $d['batch']['batch_no']);
            if ($pdf === null) {
                return $this->response->setStatusCode(503)->setBody('The PDF library is not installed on this server; open the advice as a page and print it.');
            }
            return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'inline; filename="Remittance-advice-' . $d['batch']['batch_no'] . '.pdf"')->setBody($pdf);
        }
        return $this->response->setBody(view('pages/tour/tpd_bulk_remittance', ['d' => $d, 'company' => $docs->company(), 'for_pdf' => false]));
    }

    // =====================================================================
    //  The dashboard's data
    // =====================================================================

    // GET tour/tour_payment_dashboard/desk?manual_id=AH 2218
    public function desk()
    {
        $mid = trim((string) ($this->request->getGet('manual_id') ?? ''));
        if ($mid === '') {
            return $this->json(['status' => 400, 'message' => 'Manual ID is required']);
        }
        $data = $this->legacy($mid);
        if (!$data) {
            return $this->json(['status' => 404, 'message' => 'Tour not found for Manual ID: ' . $mid]);
        }
        try {
            $desk = (new PayDesk($this->db(), Perms::user()))->build($data);
        } catch (\Throwable $e) {
            log_message('error', 'tpd desk: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return $this->json(['status' => 500, 'message' => 'The payment desk could not be built: ' . $e->getMessage()]);
        }
        return $this->json(['status' => 200, 'data' => Mask::bank($desk)]);   // tf-pilot-r2: bank account numbers masked for a sign-in that may not see them
    }

    // =====================================================================
    //  Today's USD exchange rate
    // =====================================================================

    // POST confirm_rate {rate}
    public function confirm_rate()
    {
        if (!Perms::can('rate')) {
            return $this->deny("Entering Today's USD Exchange Rate needs the Daily USD Rate privilege (or the Admin group).");
        }
        $rate = (float) str_replace(',', '', (string) $this->in('rate', ''));
        if ($rate < 50 || $rate > 2000) {
            return $this->json(['status' => 400, 'message' => 'Enter the rate as LKR per 1 USD (for example 327.25).']);
        }
        $rate = round($rate, 2);
        $db = $this->db();
        $today = date('Y-m-d');
        $prev = $db->query("SELECT * FROM daily_usd_rate WHERE rate_date = ?", [$today])->getRowArray();
        $row = ['rate_date' => $today, 'rate' => $rate, 'created_user' => Perms::user(), 'created_at' => date('Y-m-d H:i:s')];
        if ($prev) {
            $db->table('daily_usd_rate')->where('id', (int) $prev['id'])->update($row);
        } else {
            $db->table('daily_usd_rate')->insert($row);
        }
        Audit::log($db, ['action' => 'rate_confirmed', 'ref_type' => 'usd_rate', 'ref_id' => $today, 'prev' => $prev ? ['rate' => $prev['rate'], 'by' => $prev['created_user'], 'at' => $prev['created_at']] : null, 'new' => ['rate' => $rate], 'usd_rate' => $rate,
            'reason' => $prev ? 'Rate for today changed. Payments already requested keep the rate locked to them.' : null]);
        return $this->json(['status' => 200, 'message' => ($prev ? 'Today\'s rate changed to ' : 'Today\'s rate confirmed: ') . 'LKR ' . number_format($rate, 2) . ' per USD. Payments already requested keep their own locked rate.', 'rate' => (new PayDesk($db, Perms::user()))->todayRate()]);
    }

    // =====================================================================
    //  Requests (the chain's own endpoints, plus what the desk records)
    // =====================================================================

    // POST request_meta {tour_id, acc, type, invoice_no, booking_ref, due_date, note, parent_pr_id} — right after a request was made
    public function request_meta()
    {
        if (!Perms::can('requester')) {
            return $this->deny('Payment requests need the Tour Payment Dashboard privilege.');
        }
        $db = $this->db();
        $tourId = (string) $this->in('tour_id', '');
        $acc = (string) $this->in('acc', '');
        $type = (string) $this->in('type', '');
        $r = $db->query("SELECT * FROM payment_request WHERE tour_id = ? AND hotel_acc = ? AND type = ? ORDER BY id DESC LIMIT 1", [$tourId, $acc, $type])->getRowArray();
        if (!$r) {
            return $this->json(['status' => 404, 'message' => 'No request found to annotate.']);
        }
        $prId = (int) $r['id'];
        $f = ['invoice_no' => trim((string) $this->in('invoice_no', '')) ?: null, 'booking_ref' => trim((string) $this->in('booking_ref', '')) ?: null, 'due_date' => $this->date($this->in('due_date')), 'note' => trim((string) $this->in('note', '')) ?: null, 'parent_pr_id' => (int) $this->in('parent_pr_id', 0) ?: null];
        try {
            $has = $db->query("SELECT id FROM tpd_request_ext WHERE pr_id = ?", [$prId])->getRowArray();
            if (!$has) {
                helper('daily_usd_rate');
                PayDesk::recordRequest($db, $prId, $f + ['usd_rate' => (string) $type === 'Hotel' ? get_usd_rate() : null, 'rate_user' => Perms::user()]);
            } else {
                $db->query("UPDATE tpd_request_ext SET invoice_no = COALESCE(?, invoice_no), booking_ref = COALESCE(?, booking_ref), due_date = COALESCE(?, due_date), note = COALESCE(?, note), parent_pr_id = COALESCE(?, parent_pr_id) WHERE pr_id = ?",
                    [$f['invoice_no'], $f['booking_ref'], $f['due_date'], $f['note'], $f['parent_pr_id'], $prId]);
            }
        } catch (\Throwable $e) {
            return $this->json(['status' => 500, 'message' => $e->getMessage()]);
        }
        $x = $db->query("SELECT * FROM tpd_request_ext WHERE pr_id = ?", [$prId])->getRowArray();
        Audit::log($db, ['action' => $f['parent_pr_id'] ? 'request_balance' : 'request_made', 'tour_id' => $tourId, 'manual_id' => $r['manual_id'], 'ref_type' => 'request', 'ref_id' => $prId, 'invoice_no' => $f['invoice_no'],
            'new' => ['supplier' => $r['hotel_name'], 'type' => $type, 'amount' => $r['requestAmount'], 'currency' => $x['currency'] ?? null, 'usd_rate' => $x['usd_rate'] ?? null, 'lkr' => $x['lkr_amount'] ?? null, 'due' => $f['due_date'], 'note' => $f['note']],
            'usd_rate' => $x['usd_rate'] ?? null]);
        return $this->json(['status' => 200, 'pr_id' => $prId]);
    }

    // POST expense_request {expense_id, note, invoice_no} — a manual expense enters the same chain as a service fee (type lunch)
    public function expense_request()
    {
        if (!Perms::can('requester')) {
            return $this->deny('Payment requests need the Tour Payment Dashboard privilege.');
        }
        // tf-paydesk-r7h build 5 (V-2): one request at a time per expense — two sessions at the same moment had made two requests
        return PayDesk::withItemLock($this->db(), 'expense|' . (int) $this->in('expense_id', 0), fn () => $this->expenseRequestOnce())
            ?? $this->json(['status' => 409, 'message' => 'Another request for this expense is being saved at this moment. Reload the page. Nothing was written.']);
    }

    private function expenseRequestOnce()
    {
        $db = $this->db();
        $e = $db->query("SELECT * FROM tpd_expense WHERE id = ? AND status = 'open'", [(int) $this->in('expense_id', 0)])->getRowArray();
        if (!$e) {
            return $this->json(['status' => 404, 'message' => 'Expense not found.']);
        }
        if (trim((string) $e['supplier_acc']) === '') {
            return $this->json(['status' => 400, 'message' => 'This expense has no supplier account, so it cannot enter the payment chain. Edit it and choose the supplier from the master.']);
        }
        $open = $db->query("SELECT pr.id FROM payment_request pr JOIN tpd_request_ext x ON x.pr_id = pr.id WHERE x.source_type = 'expense' AND x.source_id = ? AND pr.payment_request NOT IN (3,5)", [(string) $e['id']])->getResultArray();
        foreach ($open as $o) {
            $r = $db->query("SELECT * FROM payment_request WHERE id = ?", [(int) $o['id']])->getRowArray();
            $v = PayDesk::voucherOfRequest($db, $r);
            if (!$v || ((int) $v['finalized'] !== 1 && !PayDesk::isCancelled($v))) {
                return $this->json(['status' => 409, 'message' => 'A payment request for this expense is already in the chain.']);
            }
        }
        $t = $db->query("SELECT * FROM consignee WHERE tour_id = ? LIMIT 1", [(string) $e['tour_id']])->getRowArray();
        $sup = SupplierBank::forPayment($db, (string) $e['supplier_acc']);
        $isBulk = $db->query("SELECT bulk_payment FROM supplier WHERE Sup_Acc_No = ?", [(string) $e['supplier_acc']])->getRowArray();
        $usd = strtoupper((string) $e['currency']) === 'USD';
        $rate = null;
        $amountLkr = (float) $e['amount'];
        if ($usd) {
            helper('daily_usd_rate');
            $rate = get_usd_rate();
            if (empty($rate)) {
                return $this->json(['status' => 400, 'message' => "Today's USD rate hasn't been entered yet. Please set it before requesting a USD payment."]);
            }
            $amountLkr = PayDesk::lkr((float) $e['amount'], (float) $rate);
        }
        $db->table('payment_request')->insert([
            'tour_id' => (string) $e['tour_id'], 'hotel_acc' => (string) $e['supplier_acc'], 'manual_id' => (string) ($t['manual_id'] ?? $e['manual_id']), 'consignee' => (string) ($t['consignee'] ?? ''),
            'create_user' => Perms::user(), 'start_date' => $e['service_date'] ?: ($t['start_date'] ?? date('Y-m-d')), 'end_date' => $e['service_date'] ?: ($t['end_date'] ?? date('Y-m-d')),
            'hotel_name' => (string) $e['supplier_name'], 'bank_name' => (string) $sup['bank_name'], 'branch_name' => (string) $sup['branch'], 'sup_bank_acc' => (string) $sup['account_no'],
            'total_hotel_pay' => (string) $amountLkr, 'total_hotel_advance' => '0', 'due_amount' => (string) $amountLkr, 'requestAmount' => $amountLkr,
            'payment_request' => 1, 'payment_request_user' => Perms::user(), 'payment_request_date' => date('Y-m-d H:i:s'), 'type' => 'lunch', 'is_bulk' => (int) ($isBulk['bulk_payment'] ?? 0), 'voucher_created' => 0,
        ]);
        $prId = (int) $db->insertID();
        PayDesk::recordRequest($db, $prId, ['source_type' => 'expense', 'source_id' => (string) $e['id'], 'currency' => $usd ? 'USD' : 'LKR', 'usd_rate' => $rate, 'rate_user' => Perms::user(), 'invoice_no' => trim((string) $this->in('invoice_no', '')) ?: $e['invoice_no'], 'due_date' => $e['due_date'], 'note' => trim((string) $this->in('note', '')) ?: null]);
        if ($usd) {
            // the request is kept in LKR in the chain (a service fee), the USD amount and rate on the desk's record
            $db->query("UPDATE tpd_request_ext SET currency = 'USD', amount = ?, usd_rate = ?, lkr_amount = ? WHERE pr_id = ?", [(float) $e['amount'], (float) $rate, $amountLkr, $prId]);
        }
        Audit::log($db, ['action' => 'request_made', 'tour_id' => (string) $e['tour_id'], 'manual_id' => $t['manual_id'] ?? null, 'ref_type' => 'request', 'ref_id' => $prId, 'invoice_no' => $e['invoice_no'],
            'new' => ['expense' => $e['description'], 'supplier' => $e['supplier_name'], 'amount' => $e['amount'], 'currency' => $e['currency'], 'lkr' => $amountLkr], 'usd_rate' => $rate]);
        return $this->json(['status' => 200, 'message' => 'Payment requested — it now waits for management approval.', 'pr_id' => $prId]);
    }

    // POST cancel_request {pr_id, reason}
    public function cancel_request()
    {
        if (!Perms::can('requester')) {
            return $this->deny('Needs the Tour Payment Dashboard privilege.');
        }
        $db = $this->db();
        $prId = (int) $this->in('pr_id', 0);
        $r = $db->query("SELECT * FROM payment_request WHERE id = ?", [$prId])->getRowArray();
        if (!$r || (int) $r['payment_request'] !== 1) {
            return $this->json(['status' => 400, 'message' => 'Only a request still awaiting approval can be cancelled here.']);
        }
        $reason = trim((string) $this->in('reason', ''));
        if ($reason === '') {
            return $this->json(['status' => 400, 'message' => 'A reason is required.']);
        }
        $j = $this->callExisting(TourAccountsController::class, 'cancel_payment_request', ['id' => $prId, 'tour_id' => $r['tour_id'], 'hotel_acc' => $r['hotel_acc']]);
        if ((int) ($j['status'] ?? 0) !== 200) {
            // the old endpoint's own words, or a plain update of the row as it does
            $db->query("UPDATE payment_request SET payment_request = 3, payment_cancel_date = NOW(), payment_cancel_user = ? WHERE id = ? AND payment_request = 1", [Perms::user(), $prId]);
        }
        Audit::log($db, ['action' => 'request_cancelled', 'tour_id' => $r['tour_id'], 'manual_id' => $r['manual_id'], 'ref_type' => 'request', 'ref_id' => $prId, 'prev' => ['status' => 1], 'new' => ['status' => 3], 'reason' => $reason]);
        return $this->json(['status' => 200, 'message' => 'Request cancelled.']);
    }

    // =====================================================================
    //  Management approval → payment instruction and voucher
    // =====================================================================

    // POST approve {pr_id, bank_account_id | ledger_code, note, confirm_bank}
    public function approve()
    {
        if (!Perms::can('approver')) {
            return $this->deny('Approving a payment needs the management approval privilege (Tour payment approval, or the Payment approval page).');
        }
        $db = $this->db();
        $prId = (int) $this->in('pr_id', 0);
        $r = $db->query("SELECT * FROM payment_request WHERE id = ?", [$prId])->getRowArray();
        if (!$r) {
            return $this->json(['status' => 404, 'message' => 'Request not found.']);
        }
        if ((int) $r['payment_request'] !== 1) {
            return $this->json(['status' => 409, 'message' => 'This request is not awaiting approval (status ' . $r['payment_request'] . ').']);
        }
        if ($sep = Separation::check($db, 'approve', [$prId])) {   // tf-paydesk-r5: never your own request
            return $this->json(['status' => 403, 'message' => $sep, 'separation' => true], 403);
        }
        if ($why = Blocks::paymentRefusal($db, (string) $r['hotel_acc'], (int) $this->in('supplier_account_id', 0) ?: null, 'payment approval', Perms::user(), 'request #' . $prId)) {   // tf-stage8
            Audit::log($db, ['action' => 'blocked_refused', 'tour_id' => $r['tour_id'], 'manual_id' => $r['manual_id'], 'ref_type' => 'request', 'ref_id' => $prId, 'reason' => mb_substr($why, 0, 500)]);
            return $this->json(['status' => 428, 'message' => $why, 'blocked' => true]);
        }
        // duplicate protection: an approved request already carrying a voucher is never approved again
        if ((int) $r['voucher_created'] === 1 || PayDesk::voucherOfRequest($db, $r)) {
            return $this->json(['status' => 409, 'message' => 'A voucher already exists for this request.']);
        }
        // tf-bankreg-r1: an explicit choice among the supplier's registered accounts (else the default is copied at approval)
        $tfSel = (int) $this->in('supplier_account_id', 0) ?: null;
        if ($tfSel !== null) {
            $tfA = SupplierAccounts::get($db, $tfSel);
            if (!$tfA || (string) $tfA['sup_acc'] !== (string) $r['hotel_acc'] || (int) $tfA['active'] !== 1) {
                return $this->json(['status' => 400, 'message' => 'The selected bank account is not an active account of this supplier.']);
            }
        }
        // the supplier's bank details must be verified, or confirmed now by a holder of the verification privilege
        $sup = SupplierBank::forPayment($db, (string) $r['hotel_acc'], $tfSel);
        $needsBank = (string) $r['type'] !== 'Transport Advance';
        // tf-stage5: approval may proceed on an unverified (or changed) account, and nothing is "confirmed now" at approval —
        // the bank release refuses until a Finance & Approvals Management Approver verifies the exact account in the registry
        $bankNote = '';
        if ($needsBank && $sup['status'] !== 'verified') {
            $bankNote = ' Note: the supplier\'s bank account is ' . ($sup['status'] === 'changed' ? 'changed since it was verified' : 'not verified') . ' — the bank release will wait until a Finance & Approvals Management Approver verifies it in the Supplier Bank Account Registry.';
            Audit::log($db, ['action' => 'approved_bank_unverified', 'tour_id' => $r['tour_id'], 'manual_id' => $r['manual_id'], 'ref_type' => 'request', 'ref_id' => $prId, 'reason' => 'Bank account ' . ($sup['status'] === 'changed' ? 'changed since verification' : 'not verified') . ' at approval; release waits for verification']);
        }
        // tf-paydesk-r2: with Supplier Payables in place the approval raises no voucher and names no paying account — the
        // account is chosen when management creates the supplier's batch. (An older install keeps the voucher-at-approval step.)
        $accum = Batches::accumulates($db);
        $acct = null;
        if (!$accum) {
            // the paying account: a mastered Avenir account (its ledger code goes on the voucher), or the ledger list while the master is empty
            $acct = $this->payingAccount();
            if (!$acct) {
                return $this->json(['status' => 400, 'message' => 'Choose the Avenir bank account to pay from.']);
            }
        } elseif ($x0 = $this->approvalRateCheck($db, $r)) {
            return $this->json($x0);
        }
        $j = $this->callExisting(TourAccountsCheckController::class, 'update_payment_request_status', ['id' => $prId, 'type' => (string) $r['type'], 'status' => '4', 'is_bulk' => (string) $r['is_bulk'], 'bank_acc' => $acct['ledger_code'] ?? '', 'bank_name' => $acct['label'] ?? '']);
        if ((int) ($j['status'] ?? 0) !== 200) {
            return $this->json(['status' => (int) ($j['status'] ?? 500), 'message' => $j['message'] ?? 'The approval was refused.']);
        }
        $vnum = PayDesk::recordApproval($db, $prId, Perms::user());
        if ($tfSel !== null) {   // tf-bankreg-r1: the chosen account replaces the default copied by recordApproval
            SupplierAccounts::attachRequest($db, $prId, Perms::user(), $tfSel, 'Selected at approval');
        }
        $after = $db->query("SELECT * FROM payment_request WHERE id = ?", [$prId])->getRowArray();
        $x = $db->query("SELECT * FROM tpd_request_ext WHERE pr_id = ?", [$prId])->getRowArray();
        $note = trim((string) $this->in('note', ''));
        if ($note !== '' && $x) {
            $db->query("UPDATE tpd_request_ext SET note = CONCAT(COALESCE(note, ''), ?) WHERE pr_id = ?", [($x['note'] ? "\n" : '') . 'Approval: ' . $note, $prId]);
        }
        Audit::log($db, ['action' => 'approved', 'tour_id' => $r['tour_id'], 'manual_id' => $r['manual_id'], 'ref_type' => 'request', 'ref_id' => $prId, 'vnum' => $vnum, 'invoice_no' => $x['invoice_no'] ?? null,
            'prev' => ['status' => 1], 'new' => ['status' => (int) $after['payment_request'], 'voucher' => $vnum, 'amount' => $x['amount'] ?? $r['requestAmount'], 'currency' => $x['currency'] ?? ((string) $r['type'] === 'Hotel' ? 'USD' : 'LKR'),
                'approval_rate' => $x['approval_rate'] ?? null, 'provisional_lkr' => $x['approval_lkr'] ?? ($x['lkr_amount'] ?? null), 'request_rate' => $x['usd_rate'] ?? null],
            'usd_rate' => $x['approval_rate'] ?? ($x['usd_rate'] ?? null), 'bank_account' => $acct['label'] ?? null, 'reason' => $note ?: null]);
        if ($vnum) {
            Audit::log($db, ['action' => 'voucher_issued', 'tour_id' => $r['tour_id'], 'manual_id' => $r['manual_id'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'bank_account' => $acct['label'] ?? null, 'new' => ['voucher' => $vnum]]);
        }
        if ($accum) {
            $msg = 'Approved for payment. It now waits under ' . ($r['hotel_name'] ?: $r['consignee']) . ' in Supplier Payables until management creates the supplier\'s payment batch'
                . (($x['approval_rate'] ?? null) ? ' — provisional LKR ' . number_format((float) $x['approval_lkr'], 2) . ' at ' . number_format((float) $x['approval_rate'], 2) . ' (the final LKR follows the rate of the actual payment date).' : '.');
        } else {
            $msg = $vnum ? 'Approved. Payment voucher ' . $vnum . ' issued and placed in Bank Payment Check for the accountant.' : 'Approved for payment' . ((int) $after['payment_request'] === 6 ? ' — a bulk-payment supplier: issue the voucher when the batch is ready.' : '.');
        }
        return $this->json(['status' => 200, 'message' => $msg . $bankNote, 'bank_release_waits' => $bankNote !== '', 'vnum' => $vnum, 'awaiting_batch' => $accum && !$vnum, 'approval_rate' => $x['approval_rate'] ?? null, 'approval_lkr' => $x['approval_lkr'] ?? null]);
    }

    /** A USD request is approved only with a rate to value it: Today's confirmed rate, or the rate locked when it was requested. */
    private function approvalRateCheck($db, array $r): ?array
    {
        $usd = (string) $r['type'] === 'Hotel';
        $x = null;
        try {
            $x = $db->query("SELECT currency, usd_rate FROM tpd_request_ext WHERE pr_id = ?", [(int) $r['id']])->getRowArray();
            $usd = $usd || (($x['currency'] ?? '') === 'USD');
        } catch (\Throwable $e) {
        }
        if (!$usd) {
            return null;
        }
        if (Batches::rateFor($db) || ($x && $x['usd_rate'] !== null)) {
            return null;
        }
        $q = $db->query("SELECT MAX(hotel_dollar_rate) AS r FROM tour_quotations WHERE quotation_id = ? AND hotel_acc = ?", [(string) $r['tour_id'], (string) $r['hotel_acc']])->getRowArray();
        if ((float) ($q['r'] ?? 0) > 0) {
            return null;
        }
        return ['status' => 428, 'message' => "Today's USD exchange rate is not confirmed and this request carries no locked rate. Confirm Today's rate first, then approve.", 'needs_rate' => true];
    }

    // POST reject {pr_id, reason}
    public function reject()
    {
        if (!Perms::can('approver')) {
            return $this->deny('Rejecting a payment needs the management approval privilege.');
        }
        $db = $this->db();
        $prId = (int) $this->in('pr_id', 0);
        $r = $db->query("SELECT * FROM payment_request WHERE id = ?", [$prId])->getRowArray();
        if (!$r || (int) $r['payment_request'] !== 1) {
            return $this->json(['status' => 409, 'message' => 'Only a request awaiting approval can be rejected.']);
        }
        $reason = trim((string) $this->in('reason', ''));
        if ($reason === '') {
            return $this->json(['status' => 400, 'message' => 'A reason is required to reject.']);
        }
        $j = $this->callExisting(TourAccountsCheckController::class, 'update_payment_request_status', ['id' => $prId, 'type' => (string) $r['type'], 'status' => '5', 'is_bulk' => (string) $r['is_bulk']]);
        if ((int) ($j['status'] ?? 0) !== 200) {
            return $this->json(['status' => (int) ($j['status'] ?? 500), 'message' => $j['message'] ?? 'Could not reject.']);
        }
        Audit::log($db, ['action' => 'rejected', 'tour_id' => $r['tour_id'], 'manual_id' => $r['manual_id'], 'ref_type' => 'request', 'ref_id' => $prId, 'prev' => ['status' => 1], 'new' => ['status' => 5], 'reason' => $reason]);
        return $this->json(['status' => 200, 'message' => 'Request rejected. The line can be requested again once corrected.']);
    }

    // =====================================================================
    //  Supplier Payables → a supplier's payment batch → the voucher (tf-paydesk-r2)
    // =====================================================================

    // GET payables — the page;  GET payables_data — its JSON (approved payments waiting, by supplier; the batches)
    public function payables()
    {
        if (!Perms::can('payables')) {
            return $this->deny('Supplier Payables needs a payment role (Supplier Payables, management approval, batch creation, accountant or bank approver).');
        }
        $data = (new \App\Libraries\MenuLoader())->loadMenuData();
        $data['tpd_perms'] = Perms::all();
        $data['tpd_ready'] = Schema::installed($this->db());
        $data['tpd_accumulates'] = Batches::accumulates($this->db());
        return view('pages/tour/tpd_payables', $data);
    }

    public function payables_data()
    {
        if (!Perms::can('payables')) {
            return $this->deny('Supplier Payables needs a payment role.');
        }
        $db = $this->db();
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $sup = trim((string) ($this->request->getGet('sup_acc') ?? ''));
        $awaiting = Batches::awaiting($db, $sup !== '' ? $sup : null);
        if ($q !== '') {
            $awaiting = array_values(array_filter($awaiting, static fn ($s) => stripos($s['supplier_name'], $q) !== false || stripos($s['sup_acc'], $q) !== false || array_filter($s['items'], static fn ($i) => stripos($i['manual_id'], $q) !== false || stripos($i['guest'], $q) !== false)));
        }
        return $this->json(['status' => 200, 'data' => [
            'awaiting' => $awaiting, 'batches' => Batches::listing($db, (string) ($this->request->getGet('status') ?? ''), $q),
            'bank_accounts' => BankAccounts::forSelection($db), 'perms' => Perms::all(), 'today' => date('Y-m-d'), 'today_rate' => Batches::rateFor($db),
            'totals' => ['suppliers' => count($awaiting), 'items' => array_sum(array_column($awaiting, 'item_count')), 'usd' => round(array_sum(array_column($awaiting, 'usd_total')), 2), 'provisional_lkr' => round(array_sum(array_column($awaiting, 'approval_lkr_total')), 2)],
            'fx_ledger' => Batches::fxLedger($db),
        ]]);
    }

    // GET batch_items?sup_acc= — the supplier's approved payments waiting (for the dashboard's batch window)
    public function batch_items()
    {
        if (!Perms::can('payables')) {
            return $this->deny('Needs a payment role.');
        }
        $sup = trim((string) ($this->request->getGet('sup_acc') ?? ''));
        $a = Batches::awaiting($this->db(), $sup);
        return $this->json(['status' => 200, 'data' => $a[0] ?? ['sup_acc' => $sup, 'items' => [], 'item_count' => 0], 'bank_accounts' => BankAccounts::forSelection($this->db()), 'today_rate' => Batches::rateFor($this->db())]);
    }

    // POST batch_create {sup_acc, pr_ids[], bank_account_id | ledger_code, note} — management: the selected approved payments of ONE supplier
    // become a batch; the chain raises their one voucher (create_bulk_voucher, batch mode), which lands in the accountant's queue
    public function batch_create()
    {
        if (!Perms::can('batch')) {
            return $this->deny('Creating a supplier payment batch needs the batch-creation privilege (management).');
        }
        $db = $this->db();
        if (!Batches::accumulates($db)) {
            return $this->json(['status' => 409, 'message' => 'Supplier Payables is not set up on this install (run Setup).']);
        }
        $sup = trim((string) $this->in('sup_acc', ''));
        $ids = (array) $this->in('pr_ids', []);
        if ($sup === '' && $ids) {
            $first = $db->query("SELECT hotel_acc FROM payment_request WHERE id = ?", [(int) reset($ids)])->getRowArray();
            $sup = (string) ($first['hotel_acc'] ?? '');
        }
        // tf-paydesk-r3: Pay Individually Now — one approved payment, a single-item batch, the bypass reason recorded
        $individual = (int) $this->in('individual', 0) === 1;
        $bypassReason = trim((string) $this->in('bypass_reason', ''));
        $bypassNote = trim((string) $this->in('bypass_note', ''));
        if ($individual) {
            if (!isset(Batches::BYPASS[$bypassReason])) {
                return $this->json(['status' => 400, 'message' => 'Pay Individually Now needs the reason the accumulation stage is bypassed: ' . implode(' · ', Batches::BYPASS) . '.']);
            }
            if ($bypassReason === 'other' && $bypassNote === '') {
                return $this->json(['status' => 400, 'message' => 'Say why this payment must not wait for a bulk batch.']);
            }
        }
        // tf-bankreg-r1: one registered account for the whole batch, chosen here when the items differ or management wants another account
        $tfSel = (int) $this->in('supplier_account_id', 0) ?: null;
        if ($tfSel !== null) {
            $tfA = SupplierAccounts::get($db, $tfSel);
            if (!$tfA || (string) $tfA['sup_acc'] !== $sup || (int) $tfA['active'] !== 1) {
                return $this->json(['status' => 400, 'message' => 'The selected bank account is not an active account of this supplier.']);
            }
            if (!SupplierAccounts::isComplete($tfA)) {
                return $this->json(['status' => 400, 'message' => 'The selected account is incomplete (' . implode(', ', SupplierAccounts::missing($tfA)) . ' missing). Complete it in the registry first.']);
            }
            foreach ($ids as $tfId) {
                SupplierAccounts::attachRequest($db, (int) $tfId, Perms::user(), $tfSel, 'Selected at batch creation' . ($this->in('account_reason') ? ': ' . $this->in('account_reason') : ''));
            }
        }
        $prep = Batches::prepare($db, $sup, $ids, $individual);
        if (!$prep['ok']) {
            return $this->json(['status' => 400, 'message' => $prep['message']]);
        }
        if (!empty($prep['account']) && !(int) $prep['account']['complete']) {   // tf-bankreg-r1: no batch without a complete registered account
            return $this->json(['status' => 428, 'message' => 'No complete registered bank account for ' . $prep['supplier'] . ' (' . implode(', ', SupplierAccounts::missing(['holder_name' => $prep['account']['holder_name'], 'bank_name' => $prep['account']['bank_name'], 'account_no' => $prep['account']['account_no']])) . ' missing). Complete it in the Supplier Bank Account Registry, then select it for these payments.', 'needs_account' => true, 'supplier_bank' => $prep['bank']]);
        }
        if ($sep = Separation::check($db, 'batch', $prep['pr_ids'])) {   // tf-paydesk-r5
            return $this->json(['status' => 403, 'message' => $sep, 'separation' => true], 403);
        }
        if ($why = Blocks::paymentRefusal($db, $sup, $tfSel ?: (!empty($prep['account']['account_id']) ? (int) $prep['account']['account_id'] : null), 'batch creation', Perms::user(), 'requests ' . implode(',', $prep['pr_ids']))) {   // tf-stage8
            Audit::log($db, ['action' => 'blocked_refused', 'ref_type' => 'supplier', 'ref_id' => $sup, 'reason' => mb_substr($why, 0, 500)]);
            return $this->json(['status' => 428, 'message' => $why, 'blocked' => true]);
        }
        // the supplier's bank details: verified, or confirmed now by a holder of the verification privilege
        $isDriver = $prep['items'][0]['type'] === 'Transport Advance';
        // tf-stage5: the batch is created on an unverified (or changed) account too, and nothing is "confirmed now" here —
        // the bank release refuses until a Finance & Approvals Management Approver verifies the exact account in the registry
        if (!$isDriver && $prep['bank'] && $prep['bank']['status'] !== 'verified') {
            Audit::log($db, ['action' => 'batch_created_bank_unverified', 'ref_type' => 'supplier', 'ref_id' => $sup, 'reason' => 'Bank account ' . ($prep['bank']['status'] === 'changed' ? 'changed since verification' : 'not verified') . ' at batch creation; release waits for verification']);
        }
        $acct = $this->payingAccount();
        if (!$acct) {
            return $this->json(['status' => 400, 'message' => 'Suggest the Avenir bank account the batch should be paid from (the accountant confirms the actual account at initiation).']);
        }
        if ($why = Batches::validateAccount($db, $acct)) {
            return $this->json(['status' => 400, 'message' => $why]);
        }
        $rates = [];
        $amounts = [];
        $usd = [];
        foreach ($prep['items'] as $it) {
            if ($it['currency'] === 'USD') {
                $rates[$it['pr_id']] = (string) $it['approval_rate'];
                $amounts[$it['pr_id']] = (string) $it['approval_lkr'];
                if ($it['type'] !== 'Hotel') {
                    $usd[$it['pr_id']] = (string) $it['usd_amount'];
                }
            } else {
                $amounts[$it['pr_id']] = (string) $it['lkr_amount'];
            }
        }
        // tf-paydesk-r4: one voucher is issued at a time (named lock), and the number the chain's counter would hand out
        // (invoice_no.V_Num + 1) must not be an issued number already. If the counter fell behind the table (a restored backup,
        // a manual edit), the database refuses the duplicate voucher (unique index) but the chain's own code would still attach
        // the lines to the EXISTING voucher of that number — so the case is refused here, before anything is written, and
        // Accounts is told how to put the counter right.
        $issueLock = 'tpd_voucher_issue';
        $got = $db->query("SELECT GET_LOCK(?, 15) AS g", [$issueLock])->getRowArray();
        if (!$got || (int) $got['g'] !== 1) {
            return $this->json(['status' => 409, 'message' => 'Another voucher is being issued at this moment. Try again in a few seconds.']);
        }
        try {
            $counter = (int) ($db->query("SELECT V_Num FROM invoice_no LIMIT 1")->getRowArray()['V_Num'] ?? 0);
            $next = $counter + 1;
            $maxV = (int) ($db->query("SELECT MAX(Vnum) AS m FROM voucher")->getRowArray()['m'] ?? 0);
            if ($db->query("SELECT ID FROM voucher WHERE Vnum = ? LIMIT 1", [$next])->getRowArray()) {
                return $this->json(['status' => 409, 'message' => 'The voucher could not be issued: the voucher-number counter (invoice_no.V_Num = ' . $counter . ') is behind the voucher table (last Payment Voucher No. ' . $maxV . '), so the next number ' . $next . ' is already issued. Nothing was written. Accounts must bring the counter back in step (set invoice_no.V_Num to ' . $maxV . ') and then create the batch again.', 'counter' => $counter, 'max_vnum' => $maxV]);
            }
            $vBefore = (int) ($db->query("SELECT COALESCE(MAX(ID), 0) AS m FROM voucher")->getRowArray()['m'] ?? 0);
            $viBefore = (int) ($db->query("SELECT COALESCE(MAX(ID), 0) AS m FROM v_inv")->getRowArray()['m'] ?? 0);
            $j = $this->callExisting(TourAccountsCheckController::class, 'create_bulk_voucher', ['ids' => array_map('strval', $prep['pr_ids']), 'bank_acc' => $acct['ledger_code'], 'bank_name' => $acct['label'], 'tpd_batch' => '1', 'rates' => $rates, 'amounts' => $amounts, 'usd' => $usd]);
            if ((int) ($j['status'] ?? 0) !== 200 || empty($j['vnum'])) {
                return $this->json(['status' => (int) ($j['status'] ?? 500), 'message' => $j['message'] ?? 'The voucher could not be issued.']);
            }
            $vnum = (int) $j['vnum'];
            // the voucher row this call issued must exist, for this supplier, newer than everything before the call — otherwise the
            // chain's unchecked insert failed: the lines it attached and the flags it set are taken back (this call's own writes
            // only, by id), the failure is recorded, and the user is told
            $issued = $db->query("SELECT ID FROM voucher WHERE Vnum = ? AND Acc_No = ? AND ID > ? LIMIT 1", [$vnum, $sup, $vBefore])->getRowArray();
            if (!$issued) {
                $db->query("DELETE FROM v_inv WHERE V_tkn = ? AND ID > ?", [$vnum, $viBefore]);
                $db->query("UPDATE payment_request SET voucher_created = 0 WHERE id IN (" . implode(',', array_map('intval', $prep['pr_ids'])) . ") AND voucher_created = 1");
                Audit::log($db, ['action' => 'voucher_issue_failed', 'ref_type' => 'supplier', 'ref_id' => $sup, 'new' => ['vnum_attempted' => $vnum, 'pr_ids' => $prep['pr_ids'], 'counter' => $counter, 'max_vnum' => $maxV], 'reason' => 'The chain did not insert the voucher row (database refused it); this call\'s lines and flags were taken back.']);
                return $this->json(['status' => 500, 'message' => 'The voucher could not be issued: the database refused Payment Voucher No. ' . $vnum . ' (' . ($vnum <= $maxV ? 'that number is already issued — the voucher-number counter is behind the table' : 'insert failed') . '). Nothing of this batch remains; the payments are still waiting. Ask the System Administrator to check the voucher-number counter (invoice_no.V_Num).']);
            }
        } finally {
            try {
                $db->query("SELECT RELEASE_LOCK(?)", [$issueLock]);
            } catch (\Throwable $e) {
            }
        }
        // an LKR-only batch carries no USD figure (the chain's bulk path copies the LKR total into doller_amt; a single LKR voucher never had one)
        if ($prep['currency'] === 'LKR') {
            $db->query("UPDATE voucher SET doller_amt = NULL WHERE Vnum = ?", [$vnum]);
        }
        // a single-tour hotel batch keeps the chain's hotel-dates convention on the voucher (the paid flag of the nights follows at Paid)
        if ($prep['tour_count'] === 1 && count(array_unique(array_column($prep['items'], 'type'))) === 1 && $prep['items'][0]['type'] === 'Hotel') {
            $db->query("UPDATE voucher SET V_num = ? WHERE Vnum = ? AND (V_num IS NULL OR V_num = '')", [implode(',', $prep['items'][0]['service_dates']), $vnum]);
        }
        $batch = Batches::finish($db, $prep, $vnum, $acct, trim((string) $this->in('note', '')) ?: null, Perms::user(), ['individual' => $individual, 'bypass_reason' => $bypassReason, 'bypass_note' => $bypassNote]);
        $msg = ($individual ? 'Paid individually (' . Batches::BYPASS[$bypassReason] . '): batch ' : 'Batch ') . $batch['batch_no'] . ' created — Payment Voucher No. ' . $vnum . ' for ' . $prep['supplier'] . ' (' . $prep['item_count'] . ' payment(s), ' . $prep['tour_count'] . ' tour(s)) is now in the accountant\'s queue. Suggested account: ' . $acct['label'] . ' — the accountant confirms the actual paying account at initiation.';
        return $this->json(['status' => 200, 'message' => $msg, 'vnum' => $vnum, 'payment_voucher_no' => $vnum, 'batch_id' => (int) $batch['id'], 'batch_no' => $batch['batch_no'], 'individual' => $individual]);
    }

    // GET batch/{id} — the batch page: the accountant's bank payment screen and the bank approver's final approval screen
    public function batch(int $id)
    {
        if (!Perms::can('payables')) {
            return $this->deny('Needs a payment role.');
        }
        $d = Batches::detail($this->db(), $id);
        if (!$d) {
            return $this->response->setStatusCode(404)->setBody('Batch not found');
        }
        $data = (new \App\Libraries\MenuLoader())->loadMenuData();
        $data['tpd_perms'] = Perms::all();
        $data['batch_id'] = $id;
        $data['batch_no'] = $d['batch']['batch_no'];
        return view('pages/tour/tpd_batch', $data);
    }

    // GET batch_data/{id}?date=YYYY-MM-DD — the batch with its figures at the rate of the given (payment) date
    public function batch_data(int $id)
    {
        if (!Perms::can('payables')) {
            return $this->deny('Needs a payment role.');
        }
        $db = $this->db();
        $d = Batches::detail($db, $id);
        if (!$d) {
            return $this->json(['status' => 404, 'message' => 'Batch not found.']);
        }
        $date = $this->date($this->request->getGet('date')) ?: date('Y-m-d');
        $r = $d['is_usd'] ? Batches::rateFor($db, $date) : null;
        $d['for_date'] = $date;
        $d['rate_for_date'] = $r;
        $d['at_date'] = Batches::figures($d['items'], $r['rate'] ?? null);
        $d['bank_accounts'] = BankAccounts::forSelection($db);
        $d['perms'] = Perms::all();
        $d['can_enter_rate'] = Perms::can('rate');
        unset($d['voucher']['Address']);
        return $this->json(['status' => 200, 'data' => $d]);
    }

    // GET rate_for?date= — the confirmed USD rate of a day (the bank payment page asks when the date changes)
    public function rate_for()
    {
        if (!Perms::can('payables') && !Perms::can('viewer')) {
            return $this->deny('Needs a payment role.');
        }
        $date = $this->date($this->request->getGet('date')) ?: date('Y-m-d');
        return $this->json(['status' => 200, 'date' => $date, 'rate' => Batches::rateFor($this->db(), $date)]);
    }

    /** Alias kept for the r1 dashboard: issuing a voucher is creating the supplier's batch. */
    public function issue_voucher()
    {
        if (Batches::accumulates($this->db())) {
            return $this->batch_create();
        }
        return $this->issue_voucher_r1();
    }

    // POST issue_voucher {pr_ids[]} — (r1, before Supplier Payables) bulk-payment suppliers: approved requests (status 6) get their voucher in one go
    private function issue_voucher_r1()
    {
        if (!Perms::can('approver')) {
            return $this->deny('Issuing a voucher needs the management approval privilege.');
        }
        $db = $this->db();
        $ids = array_values(array_filter(array_map('intval', (array) $this->in('pr_ids', []))));
        if (!$ids) {
            return $this->json(['status' => 400, 'message' => 'Choose the approved requests to voucher.']);
        }
        $rows = $db->query("SELECT * FROM payment_request WHERE id IN (" . implode(',', $ids) . ")")->getResultArray();
        foreach ($rows as $r) {
            if ((int) $r['payment_request'] !== 6 || (int) $r['voucher_created'] === 1) {
                return $this->json(['status' => 409, 'message' => 'Request #' . $r['id'] . ' is not an approved bulk request without a voucher.']);
            }
        }
        $accs = array_unique(array_column($rows, 'hotel_acc'));
        if (count($accs) !== 1) {
            return $this->json(['status' => 400, 'message' => 'One voucher is one supplier: choose requests of a single supplier.']);
        }
        $acct = $this->payingAccount();
        if (!$acct) {
            return $this->json(['status' => 400, 'message' => 'Choose the Avenir bank account to pay from.']);
        }
        // create_bulk_voucher expects requests at status 1; these were approved (6) already — it re-sets 6 and vouchers them
        $db->query("UPDATE payment_request SET payment_request = 1 WHERE id IN (" . implode(',', $ids) . ") AND payment_request = 6");
        $j = $this->callExisting(TourAccountsCheckController::class, 'create_bulk_voucher', ['ids' => $ids, 'bank_acc' => $acct['ledger_code'], 'bank_name' => $acct['label']]);
        if ((int) ($j['status'] ?? 0) !== 200) {
            $db->query("UPDATE payment_request SET payment_request = 6 WHERE id IN (" . implode(',', $ids) . ") AND payment_request = 1");
            return $this->json(['status' => (int) ($j['status'] ?? 500), 'message' => $j['message'] ?? 'The voucher could not be issued.']);
        }
        $vnum = null;
        foreach ($rows as $r) {
            $vnum = PayDesk::recordApproval($db, (int) $r['id'], Perms::user()) ?? $vnum;
        }
        Audit::log($db, ['action' => 'voucher_issued', 'tour_id' => $rows[0]['tour_id'], 'manual_id' => $rows[0]['manual_id'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'bank_account' => $acct['label'], 'new' => ['requests' => $ids, 'voucher' => $vnum]]);
        return $this->json(['status' => 200, 'message' => 'Voucher ' . $vnum . ' issued for ' . count($ids) . ' request(s).', 'vnum' => $vnum]);
    }

    /** The Avenir account chosen on the form: a master account by id, or a ledger code while the master is empty. */
    private function payingAccount(): ?array
    {
        $db = $this->db();
        $id = (int) $this->in('bank_account_id', 0);
        if ($id > 0) {
            $a = BankAccounts::get($db, $id);
            if (!$a || (int) $a['active'] !== 1) {
                return null;
            }
            if (!$a['ledger_code']) {
                return null;
            }
            return ['id' => $id, 'ledger_code' => $a['ledger_code'], 'label' => $a['label'], 'bank_name' => $a['bank_name'], 'account' => $a];
        }
        if (BankAccounts::all($db, true)) {
            return null;   // the master has accounts: no free choice outside it
        }
        $code = trim((string) $this->in('ledger_code', ''));
        foreach (BankAccounts::ledgerAccounts($db) as $l) {
            if ($l['L_Code'] === $code) {
                return ['id' => null, 'ledger_code' => $code, 'label' => $l['PName'], 'bank_name' => $l['PName'], 'account' => null];
            }
        }
        $d = BankAccounts::default($db);
        return null;
    }

    // =====================================================================
    //  The accountant: bank payment initiated — at the rate of the actual payment date (tf-paydesk-r2)
    // =====================================================================

    /**
     * The USD rate that values a bank payment made on $date: the day's confirmed rate (Daily USD Rate), or — when none
     * is confirmed for that day — the one typed on the page by a holder of the Daily USD Rate privilege, which is then
     * recorded as that day's rate. Anyone else is asked to have the rate confirmed first.
     */
    private function paymentRate($db, string $date, string $step): array
    {
        $daily = Batches::rateFor($db, $date);
        $posted = (float) str_replace(',', '', (string) $this->in('usd_rate', ''));
        if ($daily) {
            if ($posted > 0 && abs($posted - $daily['rate']) > 0.0001) {
                return ['error' => ['status' => 409, 'message' => 'The confirmed USD rate of ' . $date . ' is ' . number_format($daily['rate'], 2) . ' (entered by ' . $daily['by'] . '); the page must use it. Reload and try again.', 'rate' => $daily]];
            }
            return ['rate' => $daily['rate'], 'source' => 'daily', 'daily' => $daily];
        }
        if ($posted <= 0) {
            return ['error' => ['status' => 428, 'message' => 'No USD rate is confirmed for ' . $date . '. ' . (Perms::can('rate') ? 'Enter the rate of that day on the page to confirm it.' : 'Ask the holder of the Daily USD Rate privilege to confirm it, then ' . $step . '.'), 'needs_rate' => true, 'date' => $date]];
        }
        if (!Perms::can('rate')) {
            return ['error' => ['status' => 403, 'message' => 'Entering a USD rate needs the Daily USD Rate privilege. Ask the rate holder to confirm the rate of ' . $date . ', then ' . $step . '.', 'needs_rate' => true, 'date' => $date]];
        }
        if ($posted < 50 || $posted > 2000) {
            return ['error' => ['status' => 400, 'message' => 'Enter the rate as LKR per 1 USD (for example 327.25).']];
        }
        $rate = round($posted, 2);
        $db->query("INSERT IGNORE INTO daily_usd_rate (rate_date, rate, created_user, created_at) VALUES (?,?,?,?)", [$date, $rate, Perms::user(), date('Y-m-d H:i:s')]);
        Audit::log($db, ['action' => 'rate_entered', 'ref_type' => 'rate', 'ref_id' => $date, 'new' => ['rate_date' => $date, 'rate' => $rate], 'usd_rate' => $rate, 'reason' => 'Entered on the bank payment page (' . $step . ')']);
        return ['rate' => $rate, 'source' => 'entered', 'daily' => ['rate' => $rate, 'date' => $date, 'by' => Perms::user(), 'at' => date('Y-m-d H:i:s')]];
    }

    // POST initiate {vnum, bank_account_id, payment_date, usd_rate, initiated_at, batch_ref, bank_ref, charges, note, file}
    // The accountant confirms the actual payment date and that day's rate; the LKR is calculated: locked USD × payment-date
    // rate (per item, rounded to the cent). The approved USD amount, supplier, invoices and allocations cannot be changed here.
    public function initiate()
    {
        // tf-paydesk-r7f: recording a bank initiation needs the explicit initiation privilege (report/bank_payment_check/initiate);
        // the Bank Payment Check page alone (which the Bank payment approver also holds) no longer counts. Separation is unchanged.
        if (!Perms::can('initiator')) {
            return $this->deny('Recording a bank initiation needs the Bank payment initiation privilege (report/bank_payment_check/initiate).');
        }
        $db = $this->db();
        $vnum = (int) $this->in('vnum', 0);
        $v = $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        if (!$v) {
            return $this->json(['status' => 404, 'message' => 'Voucher not found.']);
        }
        if (PayDesk::isCancelled($v)) {
            return $this->json(['status' => 409, 'message' => 'This voucher was ' . ((int) $v['Aflag'] === 3 ? 'cancelled' : 'rejected') . '; it cannot be initiated.']);
        }
        if ((int) $v['finalized'] === 1) {
            $this->refused($db, 'initiation_refused', $vnum, $v, 'repeat — the voucher is already Paid (finalised ' . ($v['finalized_date'] ?? '') . '); nothing was written');
            return $this->json(['status' => 409, 'message' => 'This voucher is already paid.']);
        }
        if ($prevInit = $db->query("SELECT id, initiated_by, created_at FROM tpd_bank_initiation WHERE vnum = ?", [$vnum])->getRowArray()) {
            $this->refused($db, 'initiation_refused', $vnum, $v, 'repeat — a bank initiation is already recorded (by ' . $prevInit['initiated_by'] . ' at ' . $prevInit['created_at'] . '); nothing was written', ['existing_initiation' => (int) $prevInit['id']]);
            return $this->json(['status' => 409, 'message' => 'A bank initiation is already recorded for voucher ' . $vnum . '.']);
        }
        $acct = $this->payingAccount();
        if (!$acct) {
            return $this->json(['status' => 400, 'message' => 'Select or confirm the Avenir bank account the transfer is made from.']);
        }
        $batch = Batches::byVnum($db, $vnum);
        if ($batch && !empty($batch['parent_id']) && !$this->bulkContext) {
            return $this->json(['status' => 409, 'message' => 'Voucher ' . $vnum . ' belongs to bulk batch SB-' . str_pad((string) $batch['parent_id'], 5, '0', STR_PAD_LEFT) . ': the ONE bank transfer is initiated for the whole batch on its page, not voucher by voucher.', 'bulk_id' => (int) $batch['parent_id']]);
        }
        $items = $batch ? Batches::items($db, (int) $batch['id']) : [];
        if ($sep = Separation::check($db, 'initiate', $items ? array_column($items, 'pr_id') : $this->requestsPaidBy($db, $vnum, $v), $vnum)) {   // tf-paydesk-r5; r7h 5.2 (D-3): every stored link
            return $this->json(['status' => 403, 'message' => $sep, 'separation' => true], 403);
        }
        // tf-paydesk-r3: the accountant confirms the ACTUAL paying account — management's was a suggestion. A different account
        // needs a reason; the account is revalidated (active, ledger account, LKR) and the change is recorded in the audit history.
        if ($why = Batches::validateAccount($db, $acct)) {
            return $this->json(['status' => 400, 'message' => $why]);
        }
        // tf-bankreg-r1: no bank transfer without a complete registered payee account that is not awaiting renewed review
        if ($tfChk = SupplierAccounts::initiationCheck($db, $vnum)) {
            $this->refused($db, 'initiation_refused', $vnum, $v, 'payee account: ' . ($tfChk['review']['label'] ?? 'not registered'), ['payee_account' => $tfChk['account'] ?? null]);
            return $this->json(['status' => 428, 'message' => $tfChk['message'], 'payee_account' => $tfChk['account'] ?? null, 'account_review' => $tfChk['review'] ?? null, 'needs_account' => true]);
        }
        // tf-paydesk-r7 (17 Oct 2026): a voucher the desk has no record of (no desk request record, no batch) is initiated only once it
        // has been adopted on the Payment Desk, where it is checked for an earlier posting, payment, lock or initiation. An adopted
        // voucher that is already authorised is paid from the account it was posted from.
        if (!$batch && class_exists(\App\Libraries\TourPayments\Adoption::class)) {
            $onDesk = \App\Libraries\TourPayments\Adoption::onDesk($db, $vnum);
            if (!$onDesk['desk']) {
                // tf-paydesk-r7h build 5.2 (D-3): a desk record that names the voucher but does not agree with it no longer counts
                $held = ($onDesk['desk_record'] ?? 'none') === 'held';
                Audit::log($db, ['action' => 'initiation_refused', 'tour_id' => (string) $v['ref_po'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'reason' => 'tf-paydesk-r7: not on the Payment Desk (no desk request record, no batch, not adopted)'
                    . (($onDesk['desk_record'] ?? 'none') !== 'none' ? '; r7h 5.2: a desk record names it but does not agree with it (' . $onDesk['desk_record'] . ')' : '')]);
                return $this->json(['status' => 409, 'needs_adoption' => !$held, 'held' => $held, 'message' => $held
                    ? 'Payment Voucher No. ' . $vnum . ' is named by a desk request record that does not agree with it; that request is held until a holder of the batch privilege reviews the voucher (IS / is NOT the payment) in Supplier Payables. It cannot be initiated before the review, and then only after adoption on the Payment Desk. Nothing was written.'
                    : 'Payment Voucher No. ' . $vnum . ' is not on the Payment Desk yet: it has no desk request record that agrees with it, no batch and no adoption. Adopt it first on the Payment Desk (tour/payment_desk), where it is checked for an earlier posting, payment or initiation; then record the bank initiation. Nothing was written.']);
            }
            if ($onDesk['via'] === 'adoption' && (int) $v['Aflag'] === 1 && (string) $acct['ledger_code'] !== (string) $v['Bank_acc']) {
                $this->refused($db, 'initiation_refused', $vnum, $v, 'paying account — the voucher was authorised (posted) from ledger ' . $v['Bank_acc'] . ' before it came onto the desk; the initiation chose ledger ' . $acct['ledger_code'] . '; nothing was written', ['chosen_account_id' => $acct['id'] ?? null]);
                return $this->json(['status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' was authorised (posted) from ' . $v['Bank_acc'] . ' before it came onto the desk; the bank payment is initiated from that same account. Nothing was written.']);
            }
        }
        $prevAcct = Batches::currentAccount($db, $v, $batch, null);
        $accountChanged = $prevAcct && (string) $prevAcct['ledger_code'] !== (string) $acct['ledger_code'];
        $accountReason = trim((string) $this->in('account_reason', ''));
        if ($accountChanged && $accountReason === '') {
            return $this->json(['status' => 428, 'message' => 'You are paying from "' . $acct['label'] . '" instead of the suggested "' . $prevAcct['label'] . '". Give the reason for the change (it is kept in the audit history).', 'needs_account_reason' => true, 'suggested' => $prevAcct['label'], 'chosen' => $acct['label']]);
        }
        $paymentDate = $this->date($this->in('payment_date')) ?: date('Y-m-d');
        if ($paymentDate > date('Y-m-d')) {
            return $this->json(['status' => 400, 'message' => 'The actual payment date cannot be in the future.']);
        }
        // tf-paydesk-r7g: outside a supplier batch the currency comes from the voucher's source records (Currency), never from
        // doller_amt alone; when it cannot be established the initiation is refused before anything is written. A USD voucher
        // outside a batch keeps its own LKR amount and the rate recorded on it (no exchange difference is posted for it).
        $cur = $batch ? null : \App\Libraries\TourPayments\Currency::ofVoucher($db, $v);
        if ($cur !== null && !$cur['established']) {
            $this->refused($db, 'initiation_refused', $vnum, $v, 'currency not established — ' . $cur['reason'] . '; nothing was written');
            return $this->json(['status' => 409, 'message' => 'The currency of Payment Voucher No. ' . $vnum . ' cannot be established from its source records: ' . $cur['reason'] . '. It is not initiated. Nothing was written.', 'currency' => $cur]);
        }
        $isUsd = $batch ? $batch['currency'] !== 'LKR' : $cur['currency'] === 'USD';
        $rate = null;
        $rateSource = null;
        if ($isUsd && $batch) {
            $pr = $this->paymentRate($db, $paymentDate, 'record the bank initiation');
            if (isset($pr['error'])) {
                return $this->json($pr['error']);
            }
            $rate = $pr['rate'];
            $rateSource = $pr['source'];
        } elseif ($isUsd) {
            $rate = $cur['rate'];
            $rateSource = 'voucher';
        }
        $approvalLkr = null;
        $fx = null;
        $usdAmount = $isUsd ? ($batch ? (float) $v['doller_amt'] : (float) $cur['usd']) : null;
        if ($batch) {
            // the batch voucher is brought to the payment-date rate (while its ledger is not posted); the approval figures stay as they are
            $fig = Batches::applyRate($db, $batch, $items, $rate);
            $v = $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
            $amount = $fig['actual_lkr_total'] !== null ? (float) $fig['actual_lkr_total'] : (float) $v['Amount'];
            $approvalLkr = (float) $fig['provisional_lkr_total'];
            $fx = $fig['fx_diff'];
            $usdAmount = $fig['usd_total'] > 0 ? (float) $fig['usd_total'] : null;
            $posted = (string) $this->in('amount', '');
            if ($posted !== '' && abs(round((float) str_replace(',', '', $posted), 2) - $amount) > 0.009) {
                return $this->json(['status' => 409, 'message' => 'The LKR amount is calculated from the locked USD amount and the payment-date rate (' . number_format($amount, 2) . '); it is not typed. Reload the page.']);
            }
        } else {
            // a voucher outside the batch flow (older, or raised elsewhere): the amount is the voucher's
            $amount = round((float) str_replace(',', '', (string) $this->in('amount', $v['Amount'])), 2);
            if ($amount <= 0) {
                return $this->json(['status' => 400, 'message' => 'Enter the amount initiated.']);
            }
            if (abs($amount - (float) $v['Amount']) > 0.009) {
                return $this->json(['status' => 400, 'message' => 'The amount initiated (' . number_format($amount, 2) . ') must equal the voucher amount (' . number_format((float) $v['Amount'], 2) . '). A different amount needs a corrected voucher.']);
            }
        }
        $file = $this->upload('bank_proof', 'file');
        if (is_string($file) && str_starts_with($file, 'ERR:')) {
            return $this->json(['status' => 400, 'message' => substr($file, 4)]);
        }
        $charges = $this->in('charges') !== null && trim((string) $this->in('charges')) !== '' ? round((float) str_replace(',', '', (string) $this->in('charges')), 2) : null;
        if ($charges !== null && ($charges < 0 || !is_finite($charges))) {
            return $this->json(['status' => 400, 'message' => 'Bank charges must be 0.00 or a positive LKR amount.']);
        }
        // tf-paydesk-r4: charges recorded here are posted at Paid, to the mapped Bank Charges account — the accountant is told now if it is not mapped yet
        $chMap = Batches::chargeMapping($db);
        $chargeWarning = $charges !== null && $charges > 0 && !$chMap['complete'] ? $chMap['warning'] : null;
        $at = $this->in('initiated_at') ? date('Y-m-d H:i:s', strtotime((string) $this->in('initiated_at'))) : date('Y-m-d H:i:s');
        $row = ['vnum' => $vnum, 'voucher_id' => (int) $v['ID'], 'tour_id' => (string) $v['ref_po'], 'sup_acc' => (string) $v['Acc_No'], 'bank_account_id' => $acct['id'], 'ledger_code' => $acct['ledger_code'],
            'initiated_at' => $at, 'batch_ref' => trim((string) $this->in('batch_ref', '')) ?: null, 'bank_ref' => trim((string) $this->in('bank_ref', '')) ?: null,
            'amount' => $amount, 'currency' => 'LKR', 'charges' => $charges,
            'file_path' => $file ?: null, 'note' => trim((string) $this->in('note', '')) ?: null, 'initiated_by' => Perms::user(), 'created_at' => date('Y-m-d H:i:s'),
            'batch_id' => $batch['id'] ?? null, 'payment_date' => $paymentDate, 'usd_amount' => $usdAmount, 'usd_rate' => $rate, 'rate_source' => $rateSource,
            'approval_lkr' => $approvalLkr, 'fx_diff' => $fx, 'total_debit' => round($amount + (float) ($charges ?? 0), 2)];
        try {
            // tf-paydesk-r7h build 5 (verification V-8): a duplicate is refused by the unique key without an exception — check the result
            if ($db->table('tpd_bank_initiation')->insert($row) === false || $db->affectedRows() < 1) {
                throw new \RuntimeException('duplicate initiation');
            }
        } catch (\Throwable $e) {
            // tf-paydesk-r7d: two initiations at the same moment — the unique key keeps one; the other is refused and audited
            $this->refused($db, 'initiation_refused', $vnum, $v, 'repeat — another initiation of this voucher was recorded at the same moment (unique key); nothing else was written');
            return $this->json(['status' => 409, 'message' => 'A bank initiation is already recorded for voucher ' . $vnum . ' (by another request at the same moment). Nothing else was written.']);
        }
        // the voucher pays from the account confirmed here (its ledger code), as long as the ledger has not been posted yet
        $prevBank = ['Bank' => $v['Bank'], 'Bank_acc' => $v['Bank_acc']];
        if ((int) $v['Aflag'] === 0 && $acct['ledger_code'] !== $v['Bank_acc']) {
            $db->query("UPDATE voucher SET Bank = ?, Bank_acc = ? WHERE Vnum = ? AND Aflag = 0", [$acct['label'], $acct['ledger_code'], $vnum]);
        }
        if ($batch) {
            $db->query("UPDATE tpd_batch SET bank_account_id = ?, ledger_code = ?, updated_at = ? WHERE id = ?", [$acct['id'], $acct['ledger_code'], date('Y-m-d H:i:s'), (int) $batch['id']]);
        }
        if ($accountChanged) {
            Batches::recordAccountChange($db, $vnum, $batch, 'bank initiation (accountant)', $prevAcct, $acct, Perms::user(), $accountReason);
        } else {
            Audit::log($db, ['action' => 'account_confirmed', 'tour_id' => (string) $v['ref_po'], 'ref_type' => $batch ? 'batch' : 'voucher', 'ref_id' => $batch['batch_no'] ?? $vnum, 'vnum' => $vnum, 'bank_account' => $acct['label'], 'new' => ['account' => $acct['label'], 'ledger' => $acct['ledger_code'], 'stage' => 'bank initiation (accountant)']]);
        }
        if ($file) {
            $db->table('tpd_document')->insert(['tour_id' => (string) $v['ref_po'], 'kind' => 'bank_proof', 'ref_type' => 'voucher', 'ref_id' => (string) $vnum, 'vnum' => $vnum, 'number' => $row['bank_ref'], 'title' => 'Bank payment proof', 'file_path' => $file, 'uploaded_by' => Perms::user(), 'uploaded_at' => date('Y-m-d H:i:s')]);
        }
        if ($batch) {
            Batches::setStatus($db, (int) $batch['id'], 'initiated');
        }
        Audit::log($db, ['action' => 'bank_initiated', 'tour_id' => (string) $v['ref_po'], 'manual_id' => $batch ? implode(', ', array_unique(array_column($items, 'manual_id'))) : null, 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum,
            'prev' => ['bank' => $prevBank, 'provisional_lkr' => $approvalLkr], 'new' => array_diff_key($row, array_flip(['created_at', 'file_path'])) + ['batch' => $batch['batch_no'] ?? null],
            'usd_rate' => $rate, 'bank_account' => $acct['label'], 'bank_ref' => $row['bank_ref'] ?: $row['batch_ref'], 'reason' => $row['note']]);
        $msg = 'Bank payment initiated — awaiting bank approval. ' . ($isUsd ? 'USD ' . number_format((float) $usdAmount, 2) . ($batch ? ' × ' . number_format((float) $rate, 2) . ' (' . $paymentDate . ')' : ' at the voucher\'s rate ' . number_format((float) $rate, 2)) . ' = LKR ' . number_format($amount, 2) . ($fx !== null ? '; exchange-rate difference against the approval figures ' . ($fx >= 0 ? '+' : '−') . number_format(abs((float) $fx), 2) : '') . '. ' : 'LKR ' . number_format($amount, 2) . '. ')
            . ($charges !== null && $charges > 0 ? 'Bank charges LKR ' . number_format($charges, 2) . ' (total debit ' . number_format((float) $row['total_debit'], 2) . '). ' : '') . 'The bank approver can now confirm Paid.'
            . ($chargeWarning ? ' WARNING — ' . $chargeWarning : '');
        return $this->json(['status' => 200, 'message' => $msg, 'amount' => $amount, 'usd_rate' => $rate, 'fx_diff' => $fx, 'total_debit' => $row['total_debit'], 'charge_warning' => $chargeWarning]);
    }

    // =====================================================================
    //  The bank approver: Paid — the final date, the final rate, the final LKR, locked
    // =====================================================================

    // POST paid {vnum, paid_date, usd_rate, charges, bank_ref, note}
    public function paid()
    {
        if (!Perms::can('bank_approver')) {
            return $this->deny('Only a holder of the Bank payment approver privilege can mark a payment Paid.');
        }
        $db = $this->db();
        $vnum = (int) $this->in('vnum', 0);
        // tf-paydesk-r4: one Paid at a time per voucher — a repeated or double-clicked submission waits here, then meets the
        // "already Paid" guard below (the chain's tables are MyISAM, so this named lock stands in for a transaction)
        $lockName = 'tpd_paid_' . $vnum;
        $got = $db->query("SELECT GET_LOCK(?, 15) AS g", [$lockName])->getRowArray();
        if (!$got || (int) $got['g'] !== 1) {
            $this->refused($db, 'paid_refused', $vnum, null, 'concurrent — another Paid of this voucher was still running after 15 seconds; nothing was written');   // tf-paydesk-r7d
            return $this->json(['status' => 409, 'message' => 'Voucher ' . $vnum . ' is being marked Paid by another request at this moment. Reload the page.']);
        }
        try {
            return $this->paidInner($db, $vnum);
        } finally {
            try {
                $db->query("SELECT RELEASE_LOCK(?)", [$lockName]);
            } catch (\Throwable $e) {
            }
        }
    }

    private function paidInner($db, int $vnum)
    {
        $v = $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        if (!$v) {
            return $this->json(['status' => 404, 'message' => 'Voucher not found.']);
        }
        if (PayDesk::isCancelled($v)) {
            return $this->json(['status' => 409, 'message' => 'This voucher was ' . ((int) $v['Aflag'] === 3 ? 'cancelled (Voucher Cancellation screen)' : 'rejected') . '; it cannot be paid.']);
        }
        // tf-paydesk-r6 (16 Oct 2026): a Paid interrupted after its ledger posting is RESUMED on the next attempt, not refused —
        // the steps already done are recognised and skipped and the remaining ones completed, so no voucher stays half-Paid
        // (test D-6). A Paid is complete once its ledger has been read back (tpd_bank_approval.ledger_bank_credit set); only then
        // is a repeat refused. Resuming from the finalised flag alone is allowed only for a voucher the desk itself initiated
        // (a tpd_bank_initiation row exists); a voucher finalised by the older Mark Finalized screen is still refused as paid.
        $lockRow = $db->query("SELECT * FROM tpd_bank_approval WHERE vnum = ?", [$vnum])->getRowArray();
        if ($lockRow && $lockRow['ledger_bank_credit'] !== null) {
            $this->refused($db, 'paid_refused', $vnum, $v, 'repeat — already Paid (by ' . $lockRow['approved_by'] . ' at ' . $lockRow['approved_at'] . '); nothing was written');   // tf-paydesk-r7d
            return $this->json(['status' => 409, 'message' => 'Voucher ' . $vnum . ' is already Paid; a payment is never marked twice.']);
        }
        $resume = null;
        if ($lockRow) {
            $resume = 'after the Paid lock (the charge and difference postings and the ledger read-back were still to do)';
        } elseif ((int) $v['finalized'] === 1) {
            if (!$db->query("SELECT id FROM tpd_bank_initiation WHERE vnum = ?", [$vnum])->getRowArray()) {
                $this->refused($db, 'paid_refused', $vnum, $v, 'repeat — already finalised outside the desk (' . ($v['finalized_user'] ?? '') . ' ' . ($v['finalized_date'] ?? '') . '); nothing was written');   // tf-paydesk-r7d
                return $this->json(['status' => 409, 'message' => 'Voucher ' . $vnum . ' is already Paid; a payment is never marked twice.']);
            }
            $resume = 'after the finalised flag (the Paid lock, the postings and the ledger read-back were still to do)';
        }
        $paidDate = $this->date($this->in('paid_date')) ?: date('Y-m-d');
        if ($resume !== null && !empty($v['bank_date']) && $v['bank_date'] !== '0000-00-00') {
            $paidDate = (string) $v['bank_date'];   // r6: the date already written by the interrupted attempt stands
        }
        if ($paidDate > date('Y-m-d')) {
            return $this->json(['status' => 400, 'message' => 'The actual payment date cannot be in the future.']);
        }
        $bankRef = trim((string) $this->in('bank_ref', ''));
        $init = $db->query("SELECT * FROM tpd_bank_initiation WHERE vnum = ?", [$vnum])->getRowArray();
        // tf-paydesk-r6 (16 Oct 2026, D-12): a desk voucher is marked Paid only when a bank initiation is on record for it. Without
        // one the separation of duties (payer not the initiator) has nothing to check against, so the Paid is refused here, before
        // anything is written: no ledger posting, no voucher change, no Paid lock, no remittance row — only the refusal is audited.
        // Bulk Paid runs this same step for every voucher of the batch, so it is governed too.
        if (!$init) {
            $whyNoInit = 'No bank initiation is recorded for Payment Voucher No. ' . $vnum . '. The accountant records the bank initiation (Initiate) first; then the bank approver confirms Paid. Nothing was written.';
            Audit::log($db, ['action' => 'paid_refused', 'user' => trim((string) Perms::user()), 'tour_id' => (string) $v['ref_po'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'reason' => 'tf-paydesk-r6 (D-12): ' . $whyNoInit]);
            return $this->json(['status' => 409, 'message' => $whyNoInit, 'needs_initiation' => true]);
        }
        if ($bankRef === '') {
            $bankRef = (string) ($init['bank_ref'] ?? $init['batch_ref'] ?? '');
        }
        if ($bankRef === '') {
            return $this->json(['status' => 400, 'message' => 'Enter the final bank transaction reference.']);
        }
        $batch = Batches::byVnum($db, $vnum);
        if ($batch && !empty($batch['parent_id']) && !$this->bulkContext) {
            return $this->json(['status' => 409, 'message' => 'Voucher ' . $vnum . ' belongs to bulk batch SB-' . str_pad((string) $batch['parent_id'], 5, '0', STR_PAD_LEFT) . ': Paid is confirmed for the whole transfer on the bulk batch page, not voucher by voucher.', 'bulk_id' => (int) $batch['parent_id']]);
        }
        // tf-paydesk-r7g: outside a supplier batch the currency comes from the source records (Currency); not established → refused,
        // nothing written
        $cur = $batch ? null : \App\Libraries\TourPayments\Currency::ofVoucher($db, $v);
        if ($cur !== null && !$cur['established']) {
            $this->refused($db, 'paid_refused', $vnum, $v, 'currency not established — ' . $cur['reason'] . '; nothing was written');
            return $this->json(['status' => 409, 'message' => 'The currency of Payment Voucher No. ' . $vnum . ' cannot be established from its source records: ' . $cur['reason'] . '. It is not marked Paid. Nothing was written.', 'currency' => $cur]);
        }
        $items = $batch ? Batches::items($db, (int) $batch['id']) : [];
        if ($sep = Separation::check($db, 'paid', $items ? array_column($items, 'pr_id') : $this->requestsPaidBy($db, $vnum, $v), $vnum)) {   // tf-paydesk-r5: not your own request, not your own initiation; r7h 5.2 (D-3): every stored link
            return $this->json(['status' => 403, 'message' => $sep, 'separation' => true], 403);
        }
        // tf-stage8: the final authorisation re-checks the blocks on the payee and on the exact bank account carried
        $tfSnap = SupplierAccounts::snapshot($db, 'voucher', $vnum);
        if ($why = Blocks::paymentRefusal($db, (string) ($v['Acc_No'] ?? ''), !empty($tfSnap['account_id']) ? (int) $tfSnap['account_id'] : null, 'final authorisation (Paid)', Perms::user(), 'voucher ' . $vnum, $tfSnap['account_no'] ?? null, $tfSnap['bank_name'] ?? null)) {
            $this->refused($db, 'paid_refused', $vnum, $v, 'blocked — ' . mb_substr($why, 0, 300));
            return $this->json(['status' => 428, 'message' => $why, 'blocked' => true]);
        }
        $isUsd = $batch ? $batch['currency'] !== 'LKR' : $cur['currency'] === 'USD';   // tf-paydesk-r7g
        // tf-paydesk-r3: a foreign-currency payment is final only with the exchange gain/loss accounts mapped — no incomplete accounting entry
        $map = Batches::fxMapping($db);
        if ($batch && $isUsd && !$map['complete']) {
            return $this->json(['status' => 409, 'message' => 'Final Paid is blocked. ' . $map['warning'], 'config_warning' => true, 'fx_mapping' => $map]);
        }
        // tf-paydesk-r4: the bank charges — typed here by the bank approver, else the accountant's — must be a non-negative figure,
        // and when the bank charged anything the Bank Charges ledger account must be mapped BEFORE anything is written: a payment
        // with charges and no account to post them to is not completed, and no partial entry is made
        $chargesIn = $this->in('charges');
        $charges = $chargesIn !== null && trim((string) $chargesIn) !== '' ? round((float) str_replace(',', '', (string) $chargesIn), 2) : (isset($init['charges']) && $init['charges'] !== null ? round((float) $init['charges'], 2) : 0.0);
        if ($charges < 0 || !is_finite($charges)) {
            return $this->json(['status' => 400, 'message' => 'Bank charges must be 0.00 or a positive LKR amount.']);
        }
        $chMap = Batches::chargeMapping($db);
        if ($charges > 0 && !$chMap['complete']) {
            return $this->json(['status' => 409, 'message' => 'Final Paid is blocked: the bank charged LKR ' . number_format($charges, 2) . ' and there is no account to post it to. ' . $chMap['warning'], 'config_warning' => true, 'charge_mapping' => $chMap]);
        }
        // tf-paydesk-r3: the bank approver sees and CONFIRMS the paying account; a change needs a reason and is recorded
        $acctChangedAtPaid = false;
        $acctFinal = null;
        if ($batch) {
            if ((int) $this->in('confirm_account', 0) !== 1) {
                return $this->json(['status' => 428, 'message' => 'Confirm the Avenir paying account shown on the page before clicking Paid.', 'needs_account_confirmation' => true]);
            }
            $acctFinal = $this->payingAccount();
            if (!$acctFinal) {
                return $this->json(['status' => 400, 'message' => 'Select or confirm the Avenir bank account the payment was made from.']);
            }
            if ($why = Batches::validateAccount($db, $acctFinal)) {
                return $this->json(['status' => 400, 'message' => $why]);
            }
            $prevAcct = Batches::currentAccount($db, $v, $batch, $init);
            $acctChangedAtPaid = $prevAcct && (string) $prevAcct['ledger_code'] !== (string) $acctFinal['ledger_code'];
            $accountReason = trim((string) $this->in('account_reason', ''));
            if ($acctChangedAtPaid && $accountReason === '') {
                return $this->json(['status' => 428, 'message' => 'The payment was initiated from "' . $prevAcct['label'] . '"; you are confirming "' . $acctFinal['label'] . '". Give the reason for the change (kept in the audit history).', 'needs_account_reason' => true, 'initiated' => $prevAcct['label'], 'chosen' => $acctFinal['label']]);
            }
            if ($acctChangedAtPaid && (int) $v['Aflag'] === 1 && (string) $v['Bank_acc'] !== (string) $acctFinal['ledger_code']) {
                return $this->json(['status' => 409, 'message' => 'The ledger was already posted from "' . $prevAcct['label'] . '"; the paying account cannot be changed any more. Cancel the voucher and re-issue if it must change.']);
            }
        }
        // tf-paydesk-r4: with charges to post, the paying account's ledger account must exist (the Cr side of the charge posting) — checked before any write
        $bankLedgerFinal = $acctFinal ? (string) $acctFinal['ledger_code'] : (string) $v['Bank_acc'];
        if ($charges > 0 && !Batches::ledgerAccount($db, $bankLedgerFinal)) {
            return $this->json(['status' => 409, 'message' => 'Final Paid is blocked: the paying account\'s ledger account (' . ($bankLedgerFinal ?: 'none') . ') is not an active ledger account, so the bank charges cannot be posted. Fix the account on the Avenir Bank Account Master first.', 'config_warning' => true]);
        }
        $rate = null;
        $rateSource = null;
        if ($isUsd && $batch) {
            // the final approval loads the rate of the actual payment date — the initiation's if it is the same day, else that day's
            $pr = $this->paymentRate($db, $paidDate, 'confirm Paid');
            if (isset($pr['error'])) {
                return $this->json($pr['error']);
            }
            $rate = $pr['rate'];
            $rateSource = $pr['source'];
        }
        if ($acctFinal) {
            if ((int) $v['Aflag'] === 0 && (string) $v['Bank_acc'] !== (string) $acctFinal['ledger_code']) {
                $db->query("UPDATE voucher SET Bank = ?, Bank_acc = ? WHERE Vnum = ? AND Aflag = 0", [$acctFinal['label'], $acctFinal['ledger_code'], $vnum]);
                $v = $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
            }
            $db->query("UPDATE tpd_batch SET bank_account_id = ?, ledger_code = ?, updated_at = ? WHERE id = ?", [$acctFinal['id'], $acctFinal['ledger_code'], date('Y-m-d H:i:s'), (int) $batch['id']]);
            if ($acctChangedAtPaid) {
                Batches::recordAccountChange($db, $vnum, $batch, 'final bank approval (Paid)', $prevAcct, $acctFinal, Perms::user(), $accountReason);
            } else {
                Audit::log($db, ['action' => 'account_confirmed', 'tour_id' => (string) $v['ref_po'], 'ref_type' => 'batch', 'ref_id' => $batch['batch_no'], 'vnum' => $vnum, 'bank_account' => $acctFinal['label'], 'new' => ['account' => $acctFinal['label'], 'ledger' => $acctFinal['ledger_code'], 'stage' => 'final bank approval (Paid)']]);
            }
        }
        $final = ['batch_id' => $batch['id'] ?? null, 'bank_account_id' => $acctFinal['id'] ?? ($init['bank_account_id'] ?? null), 'rate_source' => $rateSource, 'account_confirmed_by' => $acctFinal ? Perms::user() : null];
        $fig = null;
        if ($batch) {
            $fig = Batches::applyRate($db, $batch, $items, $rate);   // the unposted voucher follows the final rate; a posted one keeps its ledger figure
            $v = $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
            $final['usd_rate'] = $rate;
            $final['approval_lkr'] = (float) $fig['provisional_lkr_total'];
            $final['lkr_amount'] = $fig['actual_lkr_total'] !== null ? (float) $fig['actual_lkr_total'] : (float) $v['Amount'];
            $final['fx_diff'] = $fig['fx_diff'];
            if ((int) $v['Aflag'] === 1 && abs((float) $v['Amount'] - (float) $final['lkr_amount']) > 0.009) {
                // the ledger was posted earlier at another figure (authorised outside this flow): the final figures are recorded, the ledger difference flagged
                $final['note_ledger'] = 'Ledger posted at LKR ' . number_format((float) $v['Amount'], 2) . ' before Paid; final LKR ' . number_format((float) $final['lkr_amount'], 2);
            }
        }
        $final['charges'] = $charges;
        // 1. the ledger: the voucher is authorised exactly as Bank Payment Check does it (the existing accounts step), at the final LKR
        if ((int) $v['Aflag'] === 0) {
            $j = $this->callExisting(\App\Controllers\Accounts\VoucherPaymentController::class, 'update', ['update' => 'data', 'id' => (string) $v['ID'], 'status' => '1', 'bank_acc' => (string) $v['Bank_acc'], 'type' => (string) $v['type'], 'amount' => (string) $v['Amount'], 'payee' => (string) $v['S_name'], 'voucher_no' => (string) $v['Vnum'], 'sup' => (string) $v['sup'], 'remark' => '']);
            if ((int) ($j['status'] ?? 0) !== 200) {
                return $this->json(['status' => (int) ($j['status'] ?? 500), 'message' => 'The voucher could not be authorised: ' . ($j['message'] ?? 'unknown error')]);
            }
            $v = $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        }
        // tf-paydesk-r7: an adopted voucher whose chain posting credits Bank Control only gets its bank leg here, once — Dr Bank Control /
        // Cr the paying account — so that the paying account carries the payment and the read-back can reconcile it. If it cannot be
        // posted, the Paid stops before the voucher is finalised (a later attempt resumes from here).
        $leg = null;
        if (class_exists(\App\Libraries\TourPayments\Adoption::class)) {
            $leg = \App\Libraries\TourPayments\Adoption::postBankLeg($db, $vnum, Perms::user(), $paidDate);
            if (!$leg['ok']) {
                return $this->json(['status' => 409, 'message' => 'Paid stopped before Payment Voucher No. ' . $vnum . ' was finalised: ' . $leg['message'] . '.']);
            }
        }
        // 2. finalised, with the bank date — the same columns Bank Payment Check's Mark Finalized writes
        $db->query("UPDATE voucher SET finalized = 1, finalized_user = ?, finalized_date = ?, bank_date = ? WHERE Vnum = ? AND finalized = 0", [Perms::user(), date('Y-m-d H:i:s'), $paidDate, $vnum]);
        // 3. the lock — final date, rate, LKR, charges, total debit, reference — and the supplier's advice set pending (one per voucher, per supplier)
        $note = trim((string) $this->in('note', '')) ?: null;
        if (!empty($final['note_ledger'])) {
            $note = trim(($note ? $note . ' · ' : '') . $final['note_ledger']);
        }
        if ($resume !== null) {
            Audit::log($db, ['action' => 'paid_resumed', 'tour_id' => (string) $v['ref_po'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'reason' => 'tf-paydesk-r6: an interrupted Paid resumed ' . $resume]);
        }
        $lock = $lockRow ?: PayDesk::lockPaid($db, $vnum, $paidDate, $bankRef, Perms::user(), $note, $final);   // r6: the lock of the interrupted attempt is reused
        if ($lock === null) {
            return $this->json(['status' => 409, 'message' => 'The Paid lock could not be written (already locked, or the desk tables are missing).']);
        }
        $fx = ['ok' => true, 'entries' => [], 'lines' => []];
        if ($batch) {
            // 4. each request's final figures (written once), the nights' paid flag, the batch's state, the difference posted to the ledger
            Batches::allocatePaid($db, $batch, $items, $rate, $paidDate);
            foreach ($items as $it) {
                if ($it['type'] === 'Hotel' && trim((string) $v['V_num']) === '') {
                    $db->query("UPDATE tour_quotations SET hotel_payment_flag = 1 WHERE quotation_id = ? AND hotel_acc = ?", [$it['tour_id'], $it['sup_acc']]);
                }
            }
            Batches::setStatus($db, (int) $batch['id'], 'paid');
            // tf-paydesk-r3: the exchange-rate difference posted per tour line to the mapped Realised Exchange Gain / Loss accounts
            $fx = ['ok' => true, 'entries' => [], 'lines' => []];
            if ($fig && $isUsd) {
                $fx = Batches::postFx($db, $vnum, $items, $fig, Perms::user());
            }
        }
        // 5. tf-paydesk-r4: the bank charges posted ONCE — Dr Bank Charges / Cr the paying account, one balanced pair per tour line
        //    (allocated pro rata to each line's final LKR, to the cent) — then the ledger read back and reconciled to the bank debit
        $v = $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        $chargeLines = Batches::chargeLines($db, $v, $batch, $items, $fig);
        $ch = Batches::postCharges($db, $vnum, $chargeLines, $charges, Perms::user());
        $rec = Batches::recordReconciliation($db, $vnum, Perms::user());
        $amt = (float) $lock['lkr_amount'];
        $msg = 'Paid. Payment Voucher No. ' . $vnum . ' is locked: ' . ($isUsd && $rate ? 'USD ' . number_format((float) ($lock['usd_amount'] ?? 0), 2) . ' × ' . number_format((float) $rate, 2) . ' (' . $paidDate . ') = ' : '') . 'LKR ' . number_format($amt, 2)
            . ($lock['fx_diff'] !== null && abs((float) $lock['fx_diff']) >= 0.005 ? '; exchange-rate ' . ((float) $lock['fx_diff'] > 0 ? 'loss' : 'gain') . ' ' . number_format(abs((float) $lock['fx_diff']), 2) . ' against the approval figures' . (!empty($fx['entries']) ? ' — posted to the ' . ((float) $lock['fx_diff'] > 0 ? 'loss' : 'gain') . ' account (' . count($fx['entries']) . ' tour line(s))' : '') : '')
            . ($charges > 0 ? '; bank charges ' . number_format($charges, 2) . ($ch['posted'] ? ' posted to ' . $chMap['account']['code'] . ' ' . $chMap['account']['name'] . ' (' . count($ch['entries']) . ' tour line(s))' : ' NOT posted — ' . $ch['message']) : '; no bank charges')
            . '; total debited ' . number_format((float) $lock['total_debit'], 2)
            . ($rec ? ($rec['reconciled'] ? '; ledger reconciled: credits to ' . $rec['bank_ledger'] . ' = LKR ' . number_format((float) $rec['totals']['bank_credit'], 2) . ' = the bank debit' : '; LEDGER DOES NOT RECONCILE — credits to ' . $rec['bank_ledger'] . ' LKR ' . number_format((float) $rec['totals']['bank_credit'], 2) . ' against a bank debit of ' . number_format((float) $rec['totals']['total_debit'], 2) . ' (see the batch page)') : '')
            . ($acctFinal ? '; paying account confirmed: ' . $acctFinal['label'] : '')
            . '. The supplier\'s remittance advice is pending — prepare and send it when ready.';
        if (!empty($leg['posted'])) {
            $msg .= ' Bank leg: ' . $leg['message'] . '.';   // tf-paydesk-r7
        }
        return $this->json(['status' => 200, 'message' => $msg, 'lock' => $lock, 'payment_voucher_no' => $vnum, 'fx_postings' => $fx['lines'] ?? [], 'charge_postings' => $ch['lines'] ?? [], 'reconciliation' => $rec, 'bank_leg' => $leg]);
    }
    // =====================================================================
    //  Remittance advice
    // =====================================================================

    // POST remittance_prepare {vnum}
    public function remittance_prepare()
    {
        if (!$this->canRemit()) {   // tf-paydesk-r7d: Accountant or Bank payment approver only (was: any viewer)
            return $this->deny('Preparing or sending a remittance advice needs the Accountant (Bank Payment Check) or the Bank payment approver privilege.');
        }
        $db = $this->db();
        $vnum = (int) $this->in('vnum', 0);
        $docs = new Documents($db);
        $d = $docs->voucher($vnum);
        if (!$d) {
            return $this->json(['status' => 404, 'message' => 'Voucher not found.']);
        }
        if ((int) $d['voucher']['finalized'] !== 1) {
            return $this->json(['status' => 409, 'message' => 'The remittance advice is prepared only after the payment is Paid.']);
        }
        if ($pb = Bulk::parentOfVnum($db, $vnum)) {   // tf-paydesk-r5: a bulk child is covered by the batch's itemised advice
            return $this->json(['status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' was paid by the bulk transfer of batch ' . $pb['batch_no'] . '. The supplier receives ONE itemised remittance advice for the whole batch — prepare it from the bulk batch page.', 'bulk_id' => (int) $pb['id']]);
        }
        $rem = $d['remittance'];
        if ($rem && $rem['status'] === 'sent') {
            return $this->json(['status' => 409, 'message' => 'The advice for voucher ' . $vnum . ' was already sent on ' . $rem['sent_at'] . ' to ' . $rem['to_email'] . '. It is not sent twice.', 'remittance' => $rem]);
        }
        if (!$rem) {
            $db->query("INSERT IGNORE INTO tpd_remittance (vnum, tour_id, sup_acc, supplier_name, status, created_at) VALUES (?,?,?,?, 'pending', ?)", [$vnum, (string) ($d['tour']['tour_id'] ?? $d['voucher']['ref_po']), (string) $d['voucher']['Acc_No'], (string) $d['voucher']['S_name'], date('Y-m-d H:i:s')]);
        }
        $draft = $docs->remittanceDraft($d);
        $html = view('pages/tour/tpd_remittance', ['d' => $d, 'company' => $d['company'], 'for_pdf' => true]);
        $pdf = $docs->pdf($html, 'Remittance-advice-' . $vnum);
        $path = null;
        if ($pdf !== null) {
            $path = Documents::dir('remittance') . 'remittance-' . $vnum . '.pdf';
            file_put_contents($path, $pdf);
        }
        $db->query("UPDATE tpd_remittance SET status = 'prepared', to_email = COALESCE(to_email, ?), subject = ?, body = ?, pdf_path = ?, prepared_by = ?, prepared_at = ? WHERE vnum = ? AND status <> 'sent'",
            [$draft['to'] ?: null, $draft['subject'], $draft['body'], $path, Perms::user(), date('Y-m-d H:i:s'), $vnum]);
        $rem = $db->query("SELECT * FROM tpd_remittance WHERE vnum = ?", [$vnum])->getRowArray();
        Audit::log($db, ['action' => 'remittance_prepared', 'tour_id' => (string) $rem['tour_id'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'new' => ['to' => $rem['to_email'], 'subject' => $rem['subject'], 'pdf' => $path ? basename($path) : null]]);
        return $this->json(['status' => 200, 'remittance' => ['id' => (int) $rem['id'], 'vnum' => $vnum, 'status' => $rem['status'], 'to' => $rem['to_email'] ?: $draft['to'], 'subject' => $rem['subject'], 'body' => $rem['body'], 'pdf' => $path ? base_url('tour/tour_payment_dashboard/remittance_pdf/' . $vnum) : null, 'preview' => base_url('tour/tour_payment_dashboard/remittance/' . $vnum),
            'supplier' => $d['supplier']['supplier_name'], 'supplier_email_source' => $d['supplier']['remittance_email'] ? 'remittance e-mail on the supplier bank record' : ($d['supplier']['master_email'] ? 'the supplier master\'s e-mail' : 'none on file'), 'pdf_missing' => $pdf === null]]);
    }

    // POST remittance_send {vnum, to, cc, subject, body}
    public function remittance_send()
    {
        if (!$this->canRemit()) {   // tf-paydesk-r7d: Accountant or Bank payment approver only (was: any viewer)
            return $this->deny('Preparing or sending a remittance advice needs the Accountant (Bank Payment Check) or the Bank payment approver privilege.');
        }
        // tf-paydesk-r7h build 5 (verification V-6): one send at a time per advice — two sessions at the same moment had sent it twice
        return PayDesk::withItemLock($this->db(), 'remit|' . (int) $this->in('vnum', 0), fn () => $this->remittanceSendOnce())
            ?? $this->json(['status' => 409, 'message' => 'This advice is being sent at this moment. Reload the page. Nothing was sent.']);
    }

    private function remittanceSendOnce()
    {
        $db = $this->db();
        $vnum = (int) $this->in('vnum', 0);
        $rem = $db->query("SELECT * FROM tpd_remittance WHERE vnum = ?", [$vnum])->getRowArray();
        if (!$rem) {
            return $this->json(['status' => 404, 'message' => 'Prepare the advice first.']);
        }
        if ($rem['status'] === 'sent') {
            return $this->json(['status' => 409, 'message' => 'Already sent on ' . $rem['sent_at'] . ' to ' . $rem['to_email'] . '. It is not sent twice.']);
        }
        $to = SupplierBank::email($this->in('to', $rem['to_email']));
        if (!$to) {
            return $this->json(['status' => 400, 'message' => 'Enter the supplier\'s e-mail address.']);
        }
        $cc = array_values(array_filter(array_map([SupplierBank::class, 'email'], preg_split('/[,;\s]+/', (string) $this->in('cc', '')))));
        $subject = trim((string) $this->in('subject', $rem['subject'])) ?: (string) $rem['subject'];
        $body = (string) $this->in('body', $rem['body']) ?: (string) $rem['body'];
        $att = [];
        if ($rem['pdf_path'] && is_file($rem['pdf_path'])) {
            $att[] = ['path' => $rem['pdf_path'], 'name' => 'Remittance-advice-' . $vnum . '.pdf'];
        }
        $db->query("UPDATE tpd_remittance SET to_email = ?, cc = ?, subject = ?, body = ? WHERE vnum = ?", [$to, $cc ? implode(', ', $cc) : null, $subject, $body, $vnum]);
        [$r, $sunk] = $this->mailOut($db, $to, $subject, $body, $cc ?: null, $att);   // tf-paydesk-r7d
        if ($r === 200 || $r === '200') {
            $db->query("UPDATE tpd_remittance SET status = 'sent', sent_by = ?, sent_at = ?, error = NULL WHERE vnum = ?", [Perms::user(), date('Y-m-d H:i:s'), $vnum]);
            // a copy of the advice beside the voucher and on the tour's documents
            try {
                $db->table('tpd_document')->insert(['tour_id' => (string) $rem['tour_id'], 'kind' => 'remittance', 'ref_type' => 'voucher', 'ref_id' => (string) $vnum, 'vnum' => $vnum, 'number' => (string) $vnum, 'title' => 'Remittance advice sent to ' . $to, 'file_path' => $rem['pdf_path'], 'uploaded_by' => Perms::user(), 'uploaded_at' => date('Y-m-d H:i:s')]);
            } catch (\Throwable $e) {
            }
            Audit::log($db, ['action' => 'remittance_sent', 'tour_id' => (string) $rem['tour_id'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'new' => ['to' => $to, 'cc' => $cc, 'subject' => $subject] + ($sunk ? ['test_mail_sink' => true] : [])]);
            return $this->json(['status' => 200, 'test_mail_sink' => $sunk, 'message' => ($sunk ? '[TEST mail sink — no e-mail left the computer] ' : '') . 'Sent to ' . $to . '. The supplier is now marked Notified.']);
        }
        $err = $r === 404 || $r === '404' ? 'Your account has no mailbox set up to send from (e-mail, password and host on your user profile).' : (is_string($r) ? $r : 'The mail server did not accept the message.');
        $db->query("UPDATE tpd_remittance SET status = 'failed', error = ? WHERE vnum = ?", [mb_substr($err, 0, 500), $vnum]);
        Audit::log($db, ['action' => 'remittance_failed', 'tour_id' => (string) $rem['tour_id'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'new' => ['to' => $to, 'error' => $err]]);
        return $this->json(['status' => 500, 'message' => 'Not sent: ' . $err . ' The advice stays pending.']);
    }

    // GET remittance/{vnum} — the advice as a page;  remittance_pdf/{vnum} — the PDF
    public function remittance(int $vnum)
    {
        if (!Perms::can('viewer')) {
            return $this->deny('Needs a Tour Payment Dashboard role.');
        }
        $d = (new Documents($this->db()))->voucher($vnum);
        if (!$d) {
            return $this->response->setStatusCode(404)->setBody('Voucher not found');
        }
        return view('pages/tour/tpd_remittance', ['d' => $d, 'company' => $d['company'], 'for_pdf' => false]);
    }

    public function remittance_pdf(int $vnum)
    {
        if (!Perms::can('viewer')) {
            return $this->deny('Needs a Tour Payment Dashboard role.');
        }
        $db = $this->db();
        $rem = $db->query("SELECT * FROM tpd_remittance WHERE vnum = ?", [$vnum])->getRowArray();
        if ($rem && $rem['pdf_path'] && is_file($rem['pdf_path'])) {
            return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'inline; filename="Remittance-advice-' . $vnum . '.pdf"')->setBody(file_get_contents($rem['pdf_path']));
        }
        $docs = new Documents($db);
        $d = $docs->voucher($vnum);
        if (!$d) {
            return $this->response->setStatusCode(404)->setBody('Voucher not found');
        }
        $pdf = $docs->pdf(view('pages/tour/tpd_remittance', ['d' => $d, 'company' => $d['company'], 'for_pdf' => true]), 'Remittance-advice-' . $vnum);
        if ($pdf === null) {
            return view('pages/tour/tpd_remittance', ['d' => $d, 'company' => $d['company'], 'for_pdf' => false, 'no_pdf_lib' => true]);
        }
        return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'inline; filename="Remittance-advice-' . $vnum . '.pdf"')->setBody($pdf);
    }

    // =====================================================================
    //  The payment voucher / instruction
    // =====================================================================

    // GET voucher/{vnum}  (?pdf=1)
    public function voucher(int $vnum)
    {
        if (!Perms::can('viewer') && !Perms::can('accountant')) {
            return $this->deny('Needs the Tour Payment Dashboard or Bank Payment Check privilege.');
        }
        $db = $this->db();
        $docs = new Documents($db);
        $d = $docs->voucher($vnum);
        if (!$d) {
            return $this->response->setStatusCode(404)->setBody('Voucher not found');
        }
        $data = ['d' => $d, 'company' => $d['company'], 'perms' => Perms::all(), 'bank_visible' => Perms::can('accountant') || Perms::can('bank_approver') || Perms::can('approver')];
        if (!$this->request->getGet('pdf')) {
            return view('pages/tour/tpd_voucher', $data + ['for_pdf' => false]);
        }
        $pdf = $docs->pdf(view('pages/tour/tpd_voucher', $data + ['for_pdf' => true]), 'Payment-voucher-' . $vnum);
        if ($pdf === null) {
            return view('pages/tour/tpd_voucher', $data + ['for_pdf' => false, 'no_pdf_lib' => true]);
        }
        return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'inline; filename="Payment-voucher-' . $vnum . '.pdf"')->setBody($pdf);
    }

    // =====================================================================
    //  Expenses, invoices, documents
    // =====================================================================

    // POST expense {tour_id, service_date, category, supplier_acc, supplier_name, description, currency, amount, invoice_no, due_date, note}
    public function expense()
    {
        if (!Perms::can('requester')) {
            return $this->deny('Adding an expense needs the Tour Payment Dashboard privilege.');
        }
        // tf-paydesk-r7h build 5 (V-2): one expense added at a time per tour, so the duplicate check holds for two sessions as well
        return PayDesk::withItemLock($this->db(), 'expense-add|' . (string) $this->in('tour_id', ''), fn () => $this->expenseOnce())
            ?? $this->json(['status' => 409, 'message' => 'Another expense is being added to this tour at this moment. Reload the page and check before trying again. Nothing was written.']);
    }

    private function expenseOnce()
    {
        $db = $this->db();
        $tourId = (string) $this->in('tour_id', '');
        $t = $db->query("SELECT tour_id, manual_id, start_date, end_date FROM consignee WHERE tour_id = ? LIMIT 1", [$tourId])->getRowArray();
        if (!$t) {
            return $this->json(['status' => 404, 'message' => 'Tour not found.']);
        }
        $acc = trim((string) $this->in('supplier_acc', ''));
        $name = trim((string) $this->in('supplier_name', ''));
        if ($acc !== '') {
            $s = $db->query("SELECT Name FROM supplier WHERE Sup_Acc_No = ?", [$acc])->getRowArray();
            if (!$s) {
                return $this->json(['status' => 400, 'message' => 'Unknown supplier account ' . $acc . '.']);
            }
            $name = $name ?: (string) $s['Name'];
        }
        $amount = round((float) str_replace(',', '', (string) $this->in('amount', '0')), 2);
        $desc = trim((string) $this->in('description', ''));
        if ($name === '' || $desc === '' || $amount <= 0) {
            return $this->json(['status' => 400, 'message' => 'Supplier, description and an amount above zero are required.']);
        }
        $row = ['tour_id' => $tourId, 'manual_id' => (string) $t['manual_id'], 'service_date' => $this->date($this->in('service_date')), 'category' => trim((string) $this->in('category', 'Other')) ?: 'Other',
            'supplier_acc' => $acc ?: null, 'supplier_name' => $name, 'description' => mb_substr($desc, 0, 255), 'currency' => strtoupper((string) $this->in('currency', 'LKR')) === 'USD' ? 'USD' : 'LKR', 'amount' => $amount,
            'invoice_no' => trim((string) $this->in('invoice_no', '')) ?: null, 'due_date' => $this->date($this->in('due_date')), 'note' => trim((string) $this->in('note', '')) ?: null,
            'status' => 'open', 'created_by' => Perms::user(), 'created_at' => date('Y-m-d H:i:s')];
        // duplicate protection: the same supplier, description, date and amount is not added twice
        $dup = $db->query("SELECT id FROM tpd_expense WHERE tour_id = ? AND status = 'open' AND supplier_name = ? AND description = ? AND amount = ? AND COALESCE(service_date,'') = COALESCE(?, '')", [$tourId, $name, $row['description'], $amount, $row['service_date']])->getRowArray();
        if ($dup) {
            return $this->json(['status' => 409, 'message' => 'That expense is already on the tour (#' . $dup['id'] . ').']);
        }
        $db->table('tpd_expense')->insert($row);
        $id = (int) $db->insertID();
        Audit::log($db, ['action' => 'expense_added', 'tour_id' => $tourId, 'manual_id' => $t['manual_id'], 'ref_type' => 'expense', 'ref_id' => $id, 'invoice_no' => $row['invoice_no'], 'new' => $row]);
        return $this->json(['status' => 200, 'message' => 'Expense added.', 'id' => $id]);
    }

    // POST expense_cancel {id, reason}
    public function expense_cancel()
    {
        if (!Perms::can('requester')) {
            return $this->deny('Needs the Tour Payment Dashboard privilege.');
        }
        $db = $this->db();
        $id = (int) $this->in('id', 0);
        $e = $db->query("SELECT * FROM tpd_expense WHERE id = ?", [$id])->getRowArray();
        if (!$e || $e['status'] !== 'open') {
            return $this->json(['status' => 404, 'message' => 'Expense not found or already cancelled.']);
        }
        $reason = trim((string) $this->in('reason', ''));
        if ($reason === '') {
            return $this->json(['status' => 400, 'message' => 'A reason is required.']);
        }
        $open = $db->query("SELECT pr.id, pr.payment_request FROM payment_request pr JOIN tpd_request_ext x ON x.pr_id = pr.id WHERE x.source_type = 'expense' AND x.source_id = ? AND pr.payment_request NOT IN (3,5)", [(string) $id])->getResultArray();
        if ($open) {
            return $this->json(['status' => 409, 'message' => 'This expense has a payment request in the chain (#' . $open[0]['id'] . '). Cancel or reverse that first; a paid expense is never removed.']);
        }
        $db->query("UPDATE tpd_expense SET status = 'cancelled', cancelled_by = ?, cancelled_at = ?, cancel_reason = ? WHERE id = ?", [Perms::user(), date('Y-m-d H:i:s'), $reason, $id]);
        Audit::log($db, ['action' => 'expense_cancelled', 'tour_id' => $e['tour_id'], 'manual_id' => $e['manual_id'], 'ref_type' => 'expense', 'ref_id' => $id, 'prev' => ['status' => 'open'], 'new' => ['status' => 'cancelled'], 'reason' => $reason]);
        return $this->json(['status' => 200, 'message' => 'Expense cancelled (kept in the history).']);
    }

    // POST invoice {tour_id, line_key, pr_id, invoice_no, booking_ref}
    public function invoice()
    {
        if (!Perms::can('requester')) {
            return $this->deny('Needs the Tour Payment Dashboard privilege.');
        }
        $db = $this->db();
        $tourId = (string) $this->in('tour_id', '');
        $key = (string) $this->in('line_key', '');
        $no = trim((string) $this->in('invoice_no', ''));
        $ref = trim((string) $this->in('booking_ref', ''));
        if ($tourId === '' || $key === '' || ($no === '' && $ref === '')) {
            return $this->json(['status' => 400, 'message' => 'Enter the supplier invoice number or booking reference.']);
        }
        $prev = $db->query("SELECT number, title FROM tpd_document WHERE tour_id = ? AND kind = 'invoice' AND ref_type = 'line' AND ref_id = ? ORDER BY id DESC LIMIT 1", [$tourId, $key])->getRowArray();
        if ($no !== '') {
            $db->table('tpd_document')->insert(['tour_id' => $tourId, 'kind' => 'invoice', 'ref_type' => 'line', 'ref_id' => $key, 'number' => mb_substr($no, 0, 100), 'title' => 'Supplier invoice ' . $no, 'uploaded_by' => Perms::user(), 'uploaded_at' => date('Y-m-d H:i:s')]);
        }
        if ($ref !== '') {
            $db->table('tpd_document')->insert(['tour_id' => $tourId, 'kind' => 'booking', 'ref_type' => 'line', 'ref_id' => $key, 'number' => mb_substr($ref, 0, 100), 'title' => 'Booking reference ' . $ref, 'uploaded_by' => Perms::user(), 'uploaded_at' => date('Y-m-d H:i:s')]);
        }
        $prId = (int) $this->in('pr_id', 0);
        if ($prId) {
            try {
                $db->query("UPDATE tpd_request_ext SET invoice_no = COALESCE(NULLIF(?, ''), invoice_no), booking_ref = COALESCE(NULLIF(?, ''), booking_ref) WHERE pr_id = ?", [$no, $ref, $prId]);
            } catch (\Throwable $e) {
            }
        }
        Audit::log($db, ['action' => 'invoice_set', 'tour_id' => $tourId, 'ref_type' => 'line', 'ref_id' => $key, 'invoice_no' => $no ?: null, 'prev' => $prev ? ['invoice_no' => $prev['number']] : null, 'new' => ['invoice_no' => $no ?: null, 'booking_ref' => $ref ?: null]]);
        return $this->json(['status' => 200, 'message' => 'Recorded.']);
    }

    // POST document {tour_id, line_key, vnum, kind, number, title, file}
    public function document()
    {
        if (!Perms::can('requester') && !Perms::can('accountant')) {
            return $this->deny('Needs the Tour Payment Dashboard privilege.');
        }
        $db = $this->db();
        $tourId = (string) $this->in('tour_id', '');
        $kind = in_array($this->in('kind'), ['invoice', 'booking', 'bank_proof', 'other'], true) ? (string) $this->in('kind') : 'other';
        $file = $this->upload($kind === 'bank_proof' ? 'bank_proof' : 'documents', 'file');
        if (is_string($file) && str_starts_with($file, 'ERR:')) {
            return $this->json(['status' => 400, 'message' => substr($file, 4)]);
        }
        $number = trim((string) $this->in('number', ''));
        if (!$file && $number === '') {
            return $this->json(['status' => 400, 'message' => 'Attach a file or enter a number.']);
        }
        $vnum = (int) $this->in('vnum', 0) ?: null;
        $key = (string) $this->in('line_key', '');
        $row = ['tour_id' => $tourId, 'kind' => $kind, 'ref_type' => $vnum ? 'voucher' : 'line', 'ref_id' => $vnum ? (string) $vnum : $key, 'vnum' => $vnum, 'number' => $number ?: null, 'title' => trim((string) $this->in('title', '')) ?: ucfirst(str_replace('_', ' ', $kind)) . ($number ? ' ' . $number : ''), 'file_path' => $file ?: null, 'uploaded_by' => Perms::user(), 'uploaded_at' => date('Y-m-d H:i:s')];
        $db->table('tpd_document')->insert($row);
        Audit::log($db, ['action' => 'document_added', 'tour_id' => $tourId, 'ref_type' => $row['ref_type'], 'ref_id' => $row['ref_id'], 'vnum' => $vnum, 'invoice_no' => $kind === 'invoice' ? $number : null, 'new' => ['kind' => $kind, 'number' => $number, 'file' => $file ? basename($file) : null]]);
        return $this->json(['status' => 200, 'message' => 'Attached.']);
    }

    // GET file/{id} — a stored document, to whoever may see the dashboard
    public function file(int $id)
    {
        if (!Perms::can('viewer') && !Perms::can('accountant')) {
            return $this->deny('Needs the Tour Payment Dashboard privilege.');
        }
        $d = $this->db()->query("SELECT * FROM tpd_document WHERE id = ?", [$id])->getRowArray();
        if (!$d || !$d['file_path'] || !is_file($d['file_path'])) {
            return $this->response->setStatusCode(404)->setBody('No file');
        }
        $mime = function_exists('mime_content_type') ? (mime_content_type($d['file_path']) ?: 'application/octet-stream') : 'application/octet-stream';
        return $this->response->setHeader('Content-Type', $mime)->setHeader('Content-Disposition', 'inline; filename="' . basename($d['file_path']) . '"')->setBody(file_get_contents($d['file_path']));
    }

    /** A PDF / image up to 8 MB into writable/tpd/<sub>/. Returns the path, '' when no file, 'ERR:…' when refused. */
    private function upload(string $sub, string $field): string
    {
        $f = $this->request->getFile($field);
        if (!$f || !$f->isValid()) {
            return '';
        }
        if ($f->getSize() > 8 * 1024 * 1024) {
            return 'ERR:The file is larger than 8 MB.';
        }
        $ext = strtolower($f->getClientExtension() ?: $f->guessExtension() ?: '');
        if (!in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            return 'ERR:Only PDF, PNG and JPG files are accepted.';
        }
        $dir = Documents::dir($sub);
        $name = date('Ymd-His') . '-' . substr(sha1(uniqid('', true)), 0, 8) . '.' . $ext;
        $f->move($dir, $name);
        return $dir . $name;
    }

    private function date($v): ?string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        $t = strtotime($v);
        return $t ? date('Y-m-d', $t) : null;
    }

    // =====================================================================
    //  Supplier bank confirmation from the dashboard
    // =====================================================================

    // POST confirm_supplier_bank {sup_acc, reason}
    public function confirm_supplier_bank()
    {
        if (!Perms::can('verifier')) {
            return $this->deny('Confirming a supplier\'s bank account needs the Supplier bank verification privilege.');
        }
        $r = SupplierBank::verify($this->db(), (string) $this->in('sup_acc', ''), Perms::user(), (string) $this->in('reason', ''));
        if ($r['ok']) {
            Audit::log($this->db(), ['action' => 'supplier_bank_confirmed', 'ref_type' => 'supplier', 'ref_id' => (string) $this->in('sup_acc', ''), 'reason' => (string) $this->in('reason', '')]);
        }
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message']]);
    }

    // GET suppliers?q= — for Add Expense
    public function suppliers()
    {
        if (!Perms::can('viewer')) {
            return $this->deny('Needs a Tour Payment Dashboard role.');
        }
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $rows = $this->db()->query("SELECT Sup_Acc_No, Name, sup_cat, bank_name FROM supplier WHERE (Name LIKE ? OR Sup_Acc_No LIKE ?) ORDER BY Name LIMIT 30", ['%' . $q . '%', '%' . $q . '%'])->getResultArray();
        return $this->json(['status' => 200, 'data' => $rows]);
    }

    // =====================================================================
    //  Export
    // =====================================================================

    // GET sheet?manual_id=…(&pdf=1) — the payment sheet to print or send
    public function sheet()
    {
        if (!Perms::can('viewer')) {
            return $this->deny('Needs a Tour Payment Dashboard role.');
        }
        $mid = trim((string) ($this->request->getGet('manual_id') ?? ''));
        $data = $mid !== '' ? $this->legacy($mid) : null;
        if (!$data) {
            return $this->response->setStatusCode(404)->setBody('Tour not found');
        }
        $desk = (new PayDesk($this->db(), Perms::user()))->build($data);
        $docs = new Documents($this->db());
        $vars = ['desk' => $desk, 'company' => $docs->company(), 'user' => Perms::user(), 'bank_visible' => Perms::can('accountant') || Perms::can('bank_approver')];
        if (!$this->request->getGet('pdf')) {
            return view('pages/tour/tpd_sheet', $vars + ['for_pdf' => false]);
        }
        $pdf = $docs->pdf(view('pages/tour/tpd_sheet', $vars + ['for_pdf' => true]), 'Payment-sheet-' . preg_replace('/[^A-Za-z0-9]+/', '-', $mid));
        if ($pdf === null) {
            return view('pages/tour/tpd_sheet', $vars + ['for_pdf' => false, 'no_pdf_lib' => true]);
        }
        return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'inline; filename="Payment-sheet-' . preg_replace('/[^A-Za-z0-9]+/', '-', $mid) . '.pdf"')->setBody($pdf);
    }

    // =====================================================================
    //  Setup (System Administrator)
    // =====================================================================

    // GET setup — what is in place;  POST setup — create what is missing
    public function setup()
    {
        $db = $this->db();
        $canMap = Perms::isAdmin() || Perms::can('bank_master');   // the System Administrator or the Accounts manager (Avenir Bank Accounts)
        if ($this->request->getMethod() === 'POST' || $this->request->getMethod() === 'post') {
            try {
                // tf-paydesk-r3: the Realised Exchange Gain and Realised Exchange Loss ledger accounts — chosen from the chart of accounts
                // (Accounts creates or approves them first; nothing is invented here). Both are needed before a foreign-currency Paid.
                // tf-paydesk-r4: … and the Bank Charges account (bank_charge_ledger_code). Only the keys posted are changed; each must be an
                // existing, active account; the three must be different accounts.
                $keys = ['fx_gain_ledger_code' => 'Realised Exchange Gain', 'fx_loss_ledger_code' => 'Realised Exchange Loss', 'bank_charge_ledger_code' => 'Bank Charges'];
                $posted = array_filter(array_keys($keys), fn ($k) => $this->in($k) !== null);
                if ($posted) {
                    if (!$canMap) {
                        return $this->deny('Mapping the ledger accounts is for the System Administrator or the Accounts manager (Avenir Bank Accounts privilege).');
                    }
                    $prevFx = Batches::fxMapping($db);
                    $prevCh = Batches::chargeMapping($db);
                    $cur = ['fx_gain_ledger_code' => $prevFx['gain_code'], 'fx_loss_ledger_code' => $prevFx['loss_code'], 'bank_charge_ledger_code' => $prevCh['code']];
                    $new = $cur;
                    foreach ($posted as $k) {
                        $code = trim((string) $this->in($k, ''));
                        if ($code !== '' && !Batches::ledgerAccount($db, $code)) {
                            return $this->json(['status' => 400, 'message' => $keys[$k] . ': no active ledger account with code ' . $code . ' in the chart of accounts.']);
                        }
                        $new[$k] = $code;
                    }
                    $set = array_filter($new, static fn ($c) => $c !== '');
                    if (count($set) !== count(array_unique($set))) {
                        return $this->json(['status' => 400, 'message' => 'The Realised Exchange Gain, Realised Exchange Loss and Bank Charges accounts must be three different ledger accounts.']);
                    }
                    foreach ($posted as $k) {
                        $db->query("INSERT INTO tpd_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [$k, $new[$k]]);
                    }
                    $now = Batches::fxMapping($db);
                    $nowCh = Batches::chargeMapping($db);
                    $label = static fn (?array $a, string $code) => $code . ($a ? ' ' . $a['name'] : '');
                    $fxTouched = in_array('fx_gain_ledger_code', $posted, true) || in_array('fx_loss_ledger_code', $posted, true);
                    Audit::log($db, ['action' => $fxTouched && !in_array('bank_charge_ledger_code', $posted, true) ? 'fx_mapping_saved' : 'ledger_mapping_saved', 'ref_type' => 'setup', 'ref_id' => 'ledger_mapping',
                        'prev' => ['gain' => $cur['fx_gain_ledger_code'], 'loss' => $cur['fx_loss_ledger_code'], 'bank_charges' => $cur['bank_charge_ledger_code']],
                        'new' => ['gain' => $label($now['gain'], $new['fx_gain_ledger_code']), 'loss' => $label($now['loss'], $new['fx_loss_ledger_code']), 'bank_charges' => $label($nowCh['account'], $new['bank_charge_ledger_code']), 'changed' => array_values($posted)], 'reason' => trim((string) $this->in('reason', '')) ?: null]);
                    $msgs = [];
                    $msgs[] = $now['complete'] ? 'Exchange-rate differences will be posted: gains to ' . $now['gain']['code'] . ' ' . $now['gain']['name'] . ', losses to ' . $now['loss']['code'] . ' ' . $now['loss']['name'] . '.' : 'Exchange mapping incomplete — ' . implode('; ', $now['problems']) . '. Foreign-currency Paid stays blocked.';
                    $msgs[] = $nowCh['complete'] ? 'Bank charges will be posted to ' . $nowCh['account']['code'] . ' ' . $nowCh['account']['name'] . ' (Dr) against the paying account (Cr), per tour line.' : 'Bank-charge mapping incomplete — ' . implode('; ', $nowCh['problems']) . '. Paid with bank charges stays blocked.';
                    return $this->json(['status' => 200, 'message' => implode(' ', $msgs), 'fx_mapping' => $now, 'charge_mapping' => $nowCh, 'complete' => $now['complete'] && $nowCh['complete']]);
                }
                if (!Perms::isAdmin()) {
                    return $this->deny('Setup is for the System Administrator.');
                }
                $notes = Schema::ensure($db, Perms::user());
                Audit::log($db, ['action' => 'setup', 'new' => $notes]);
                return $this->json(['status' => 200, 'notes' => $notes, 'state' => Schema::status($db)]);
            } catch (\Throwable $e) {
                return $this->json(['status' => 500, 'message' => $e->getMessage()]);
            }
        }
        if (!$canMap) {
            return $this->deny('Setup is for the System Administrator or the Accounts manager.');
        }
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $accounts = $q !== '' ? $db->query("SELECT L_Code, PName FROM account_posting WHERE (PName LIKE ? OR L_Code LIKE ?) AND COALESCE(deact,0) = 0 ORDER BY PName LIMIT 40", ['%' . $q . '%', '%' . $q . '%'])->getResultArray() : [];
        return $this->json(['status' => 200, 'state' => Schema::status($db), 'fx_mapping' => Batches::fxMapping($db), 'charge_mapping' => Batches::chargeMapping($db), 'fx_ledger' => Batches::fxLedger($db), 'accounts' => $accounts]);
    }

    // GET settings — tf-paydesk-r3: the Tour Payment Desk settings page (ledger mapping of the exchange gain / loss accounts; the schema state)
    public function settings()
    {
        if (!Perms::isAdmin() && !Perms::can('bank_master')) {
            return $this->deny('Settings are for the System Administrator or the Accounts manager (Avenir Bank Accounts privilege).');
        }
        $db = $this->db();
        $data = (new \App\Libraries\MenuLoader())->loadMenuData();
        $data['tpd_perms'] = Perms::all();
        $data['fx_mapping'] = Batches::fxMapping($db);
        $data['charge_mapping'] = Batches::chargeMapping($db);
        $data['schema'] = Schema::status($db);
        return view('pages/tour/tpd_settings', $data);
    }

    // =====================================================================
    //  tf-bankreg-r1: the registered account a payment pays to
    // =====================================================================

    // GET account_options?sup_acc= — the supplier's registered accounts (for a selection), masked for a sign-in that may not see numbers
    public function account_options()
    {
        $db = $this->db();
        $acc = trim((string) ($this->request->getGet('sup_acc') ?? $this->in('sup_acc', '')));
        $out = [];
        foreach (SupplierAccounts::forSupplier($db, $acc) as $a) {
            $out[] = SupplierAccounts::present($db, $acc, $a);
        }
        return $this->json(['status' => 200, 'data' => $out, 'can_edit' => Perms::can('supplier_bank'), 'registry_url' => base_url(SupplierAccounts::MENU_URL)]);
    }

    // POST account_select {ref_type: request|batch, ref_id, account_id, reason} — an explicit selection, recorded; a batch takes it for every request
    public function account_select()
    {
        if (!Perms::can('approver') && !Perms::can('batch') && !Perms::can('verifier')) {
            return $this->deny('Selecting the bank account of a payment needs the approval, batch or verification privilege.');
        }
        $db = $this->db();
        $type = (string) $this->in('ref_type', 'request');
        $id = (string) $this->in('ref_id', '');
        $accountId = (int) $this->in('account_id', 0);
        $reason = trim((string) $this->in('reason', ''));
        $a = SupplierAccounts::get($db, $accountId);
        if (!$a || (int) $a['active'] !== 1) {
            return $this->json(['status' => 400, 'message' => 'Choose an active registered account.']);
        }
        if ($type === 'request') {
            $r = $db->query("SELECT id, hotel_acc, payment_request FROM payment_request WHERE id = ?", [(int) $id])->getRowArray();
            if (!$r || (string) $r['hotel_acc'] !== (string) $a['sup_acc']) {
                return $this->json(['status' => 400, 'message' => 'That account does not belong to the payee of this request.']);
            }
            $x = $db->query("SELECT batch_id FROM tpd_request_ext WHERE pr_id = ?", [(int) $id])->getRowArray();
            if ($x && $x['batch_id']) {
                return $this->json(['status' => 409, 'message' => 'This request is already in batch ' . $x['batch_id'] . ': its account is reviewed on the batch, not changed here.']);
            }
            $snap = SupplierAccounts::attachRequest($db, (int) $id, Perms::user(), $accountId, $reason !== '' ? $reason : 'Selected by ' . Perms::user());
            return $this->json(['status' => 200, 'message' => 'Account selected for the request.', 'account' => SupplierAccounts::display($db, $snap)]);
        }
        if ($type === 'batch') {
            $b = Batches::get($db, (int) $id);
            if (!$b || (string) $b['sup_acc'] !== (string) $a['sup_acc']) {
                return $this->json(['status' => 400, 'message' => 'That account does not belong to the batch\'s supplier.']);
            }
            if (in_array((string) $b['status'], ['initiated', 'paid', 'notified'], true)) {
                return $this->json(['status' => 409, 'message' => 'The bank transfer of this batch is already initiated or paid; its account cannot change.']);
            }
            if ($reason === '') {
                return $this->json(['status' => 400, 'message' => 'Give the reason for changing the account of an issued batch.']);
            }
            foreach (['batch' => (string) $b['id'], 'voucher' => $b['vnum'] ? (string) $b['vnum'] : null] as $t => $rid) {
                if ($rid === null) {
                    continue;
                }
                $db->query("DELETE FROM tpd_payment_account WHERE ref_type = ? AND ref_id = ?", [$t, $rid]);
            }
            $snap = null;
            foreach ($db->query("SELECT pr_id FROM tpd_request_ext WHERE batch_id = ?", [(int) $b['id']])->getResultArray() as $x) {
                $snap = SupplierAccounts::attachRequest($db, (int) $x['pr_id'], Perms::user(), $accountId, 'Selected on batch ' . $b['batch_no'] . ': ' . $reason);
            }
            SupplierAccounts::attachBatch($db, (int) $b['id'], $b['vnum'] ? (int) $b['vnum'] : null, (string) $b['sup_acc'], $snap, Perms::user());
            return $this->json(['status' => 200, 'message' => 'Account selected for batch ' . $b['batch_no'] . ' and its voucher.', 'account' => SupplierAccounts::display($db, SupplierAccounts::snapshot($db, 'batch', (int) $b['id']))]);
        }
        return $this->json(['status' => 400, 'message' => 'ref_type must be request or batch.']);
    }

    // POST account_review {ref_type, ref_id, decision: keep|update, note} — a flagged payment reviewed by a holder of the verification or batch privilege
    public function account_review()
    {
        if (!Perms::can('verifier') && !Perms::can('batch')) {
            return $this->deny('Reviewing a payment\'s bank account needs the Supplier bank verification or the batch privilege.');
        }
        $db = $this->db();
        $r = SupplierAccounts::reviewDecision($db, (string) $this->in('ref_type', 'voucher'), (string) $this->in('ref_id', ''), (string) $this->in('decision', ''), Perms::user(), (string) $this->in('note', ''));
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message']]);
    }

    // GET account_of?ref_type=&ref_id= — the account carried by a request, batch or voucher, with its review state
    public function account_of()
    {
        $db = $this->db();
        return $this->json(['status' => 200, 'account' => SupplierAccounts::display($db, SupplierAccounts::snapshot($db, (string) ($this->request->getGet('ref_type') ?? $this->in('ref_type', 'voucher')), (string) ($this->request->getGet('ref_id') ?? $this->in('ref_id', ''))))]);
    }
}
