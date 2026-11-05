<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

/**
 * tf-pa-upcoming-r1 (22 Oct 2026) — Payment Approval: the status cards and the "Next 15 days" review list.
 *
 * Read-only. Nothing here calculates a payment or changes one: every status, amount and payee is the Tour Payment
 * Dashboard's own (TourPaymentDashboardController::list_upcoming for the batch, ::dashboardData for one tour), and every
 * action the page offers is a link to an existing screen, where that screen's own permissions and checks apply.
 * Routes (Config/Routes.pa_upcoming.php) sit under tour/tour_accounts/payment_approval_v2/, so only a user who may open
 * Payment Approval may call them (UserPermissionFilter: the held menu URL and the paths under it).
 *
 *   Review periods (arrival = the tour's start date; confirmed = consignee.verify 1, not a template, not cancelled):
 *     next15 — today and the next 14 days (15 calendar days, today included)
 *     h1     — the 1st to the 15th of the month        h2 — the 16th to the month end
 *
 *   Cards (Shantha's choice, 22 Oct 2026: the dashboard's status words, tours of the period):
 *     Fully paid       — every item of the tour 'Fully Paid'
 *     Pending          — at least one item 'Pending Payment'
 *     Advance payment  — at least one driver advance 'Advance Paid'
 *     Next 15 days / period — tours with at least one item not yet approved (below)
 *
 *   Not yet approved (the dashboard's words):  'Payment Not Requested' (not submitted) · 'Waiting Rate Confirmation' ·
 *   'Not Paid' / 'Requested' (submitted, awaiting approval).  Approved and later stages ('Pending Approval' = approved, rejected
 *   or cancelled without a voucher; 'Pending Payment', 'Payment on Process', 'Advance Paid', 'Paid', 'Partially Paid', 'Fully Paid')
 *   and cancelled tours are left out.
 *   Driver settlements and daily bata are reviewed in the page's "Approved driver expenses" section and are not listed.
 */
class PaymentApprovalUpcomingController extends BaseController
{
    public const UNAPPROVED = [
        'Payment Not Requested'     => ['not_submitted', 'Not submitted'],
        'Waiting Rate Confirmation' => ['rate', 'Awaiting rate confirmation'],
        'Not Paid'                  => ['awaiting', 'Awaiting approval'],
        'Requested'                 => ['awaiting', 'Awaiting approval'],
        // not listed: the dashboard's 'Pending Approval' covers a request already approved (waiting in Supplier Payables),
        // rejected or cancelled, with no voucher yet — all outside this list (checked on AH 2052, 22 Oct 2026: a rejected request)
    ];
    /** categories whose dashboard status speaks of the request / approval (driver settlement and bata speak of payment) */
    private const LISTED_CATEGORIES = ['hotel', 'activity', 'driver_advance'];

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');   // as the Tour Payment Dashboard
    }

    /** The period asked for: [key, from, to, label]. */
    private function period(): array
    {
        $p = (string) ($this->request->getGet('p') ?? 'next15');
        $m = (string) ($this->request->getGet('m') ?? '');
        if (!preg_match('/^\d{4}-\d{2}$/', $m)) {
            $m = date('Y-m');
        }
        $first = $m . '-01';
        if ($p === 'h1') {
            return ['h1', $first, $m . '-15', '1st–15th'];
        }
        if ($p === 'h2') {
            return ['h2', $m . '-16', date('Y-m-t', strtotime($first)), '16th–month end'];
        }
        $from = date('Y-m-d');
        return ['next15', $from, date('Y-m-d', strtotime($from . ' +14 days')), 'Next 15 days'];
    }

    private function dashboard(array $get = []): TourPaymentDashboardController
    {
        $req = clone $this->request;
        $req->setGlobal('get', $get);
        $c = new TourPaymentDashboardController();
        $c->initController($req, new \CodeIgniter\HTTP\Response(config('App')), service('logger'));
        return $c;
    }

    // GET tour/tour_accounts/payment_approval_v2/upcoming?p=next15|h1|h2&m=YYYY-MM — the cards and the tours to review
    public function summary()
    {
        [$key, $from, $to, $label] = $this->period();
        $res = $this->dashboard(['from' => $from, 'to' => $to])->list_upcoming();
        $j = json_decode((string) $res->getBody(), true);
        if (!is_array($j) || (int) ($j['status'] ?? 0) !== 200) {
            return $this->response->setJSON(['status' => 500, 'message' => 'The tour list could not be read.']);
        }
        $cards = ['fully_paid' => 0, 'pending' => 0, 'advance' => 0, 'unapproved' => 0];
        $tours = [];
        foreach ((array) $j['data'] as $t) {
            $all = array_merge($t['categories']['hotels'] ?? [], $t['categories']['activities'] ?? [], $t['categories']['driver_advance'] ?? []);
            if ($all && count(array_filter($all, static fn ($s) => $s === 'Fully Paid')) === count($all)) {
                $cards['fully_paid']++;
            }
            if (in_array('Pending Payment', $all, true)) {
                $cards['pending']++;
            }
            if (in_array('Advance Paid', $t['categories']['driver_advance'] ?? [], true)) {
                $cards['advance']++;
            }
            $open = count(array_filter($all, static fn ($s) => isset(self::UNAPPROVED[$s])));
            if ($open > 0) {
                $cards['unapproved']++;
                $tours[] = ['manual_id' => (string) $t['manual_id'], 'start_date' => (string) $t['start_date'], 'open' => $open];
            }
        }
        return $this->response->setJSON(['status' => 200, 'period' => $key, 'label' => $label, 'from' => $from, 'to' => $to,
            'month' => substr($from, 0, 7), 'tours_in_period' => count((array) $j['data']), 'cards' => $cards, 'tours' => $tours]);
    }

    // GET tour/tour_accounts/payment_approval_v2/upcoming_tour?manual_id= — one tour's items not yet approved
    public function tour()
    {
        $mid = trim((string) ($this->request->getGet('manual_id') ?? ''));
        if ($mid === '') {
            return $this->response->setJSON(['status' => 400, 'message' => 'Manual ID is required']);
        }
        $d = $this->dashboard()->dashboardData($mid);
        if (!$d || trim((string) ($d['tour_info']['manual_id'] ?? '')) !== $mid) {   // exact tour only (dashboardData falls back to a partial match)
            return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found: ' . $mid]);
        }
        $ti = $d['tour_info'];
        $items = [];
        foreach ((array) $d['payment_rows'] as $r) {
            $st = (string) ($r['status'] ?? '');
            if (!isset(self::UNAPPROVED[$st]) || !in_array($r['category_key'] ?? '', self::LISTED_CATEGORIES, true)) {
                continue;
            }
            $usd = ($r['category_key'] ?? '') === 'hotel' && (float) ($r['total_dollar'] ?? 0) > 0;
            $amount = $usd ? (float) $r['total_dollar'] : (float) ($r['total'] ?? 0);
            $items[] = [
                'type'     => (string) ($r['category'] ?? ''),
                'supplier' => trim((string) ($r['supplier'] ?? '')),
                'detail'   => self::detail($r),
                'date'     => (string) ($r['due_date'] ?? ''),
                'currency' => $usd ? 'USD' : 'LKR',
                'amount'   => $amount > 0 ? round($amount, 2) : null,
                'status'   => $st,
                'tab'      => self::tab($r),
                'state'    => self::UNAPPROVED[$st][0],
                'label'    => self::UNAPPROVED[$st][1],
            ];
        }
        return $this->response->setJSON(['status' => 200, 'tour' => [
            'manual_id' => trim((string) $ti['manual_id']), 'tour_id' => (string) $ti['tour_id'], 'start_date' => (string) $ti['start_date'],
            'end_date' => (string) $ti['end_date'], 'client' => trim((string) $ti['consignee']), 'agent_no' => trim((string) ($ti['agent'] ?? '')),
            'agent_name' => trim((string) ($ti['agent_name'] ?? '')), 'pax' => (int) ($ti['passenger_count'] ?? 0),
        ], 'items' => $items]);
    }

    /** tf-pa-tabs-r1: the page's category tab of a dashboard row — hotel / advance / restaurant / entrance (an activity by its name, PayDesk::activityGroup). */
    private static function tab(array $r): string
    {
        $k = (string) ($r['category_key'] ?? '');
        if ($k === 'hotel') {
            return 'hotel';
        }
        if ($k === 'driver_advance') {
            return 'advance';
        }
        return \App\Libraries\TourPayments\PayDesk::activityGroup((string) ($r['supplier'] ?? '') . ' ' . (string) ($r['description'] ?? '')) === 'meal' ? 'restaurant' : 'entrance';
    }

    /** Room type and nights for a hotel (the dashboard's description is the room-type id when no name is stored); the description otherwise. */
    private static function detail(array $r): string
    {
        $d = trim((string) ($r['description'] ?? ''));
        if (($r['category_key'] ?? '') !== 'hotel') {
            return $d;
        }
        $parts = [];
        if ($d !== '' && !ctype_digit($d)) {
            $parts[] = $d;
        }
        if (!empty($r['night_count'])) {
            $parts[] = (int) $r['night_count'] . ' night' . ((int) $r['night_count'] === 1 ? '' : 's');
        }
        return implode(' · ', $parts);
    }

    // GET tour/tour_accounts/payment_approval_v2/tour_lookup?ids=AH 1,AH 2 — agent and client of the tours already on the page (search)
    public function lookup()
    {
        $ids = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($this->request->getGet('ids') ?? ''))))));
        $ids = array_slice($ids, 0, 500);
        if (!$ids) {
            return $this->response->setJSON(['status' => 200, 'data' => []]);
        }
        $rows = \Config\Database::connect()->table('consignee')->select('manual_id, agent, agent_name, consignee')
            ->whereIn('manual_id', $ids)->orderBy('verify', 'DESC')->orderBy('ID', 'DESC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $k = trim((string) $r['manual_id']);
            if (!isset($out[$k])) {
                $out[$k] = ['agent_no' => trim((string) $r['agent']), 'agent_name' => trim((string) $r['agent_name']), 'client' => trim((string) $r['consignee'])];
            }
        }
        return $this->response->setJSON(['status' => 200, 'data' => $out]);
    }
}
