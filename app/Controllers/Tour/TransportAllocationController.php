<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\CrewPeople;
use App\Libraries\TourFlow\Desks;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\IntakeService;
use App\Libraries\TourFlow\Rotation;
use App\Libraries\TourFlow\Staff;
use App\Libraries\TourFlow\TourService;

/**
 * tf-rotation-r1 (25 Oct 2026) — the Transport Desk's allocation workflow, under tour/transport_ops/allocations:
 *   /                 Pending Allocations: the cards sent on by staff, the rotation's proposal for each, Accept / Not Available /
 *                     Bypass Rotation; the other lanes (awaiting review, confirming, Upcoming Tours, On Tour) as tabs
 *   /data             the lanes and counts (JSON)
 *   /assess/(job)     the rotation's assessment of one card (JSON)
 *   /review/(job)     GET the review mark · POST send on / take back ("Driver Allocation" button on Transport Operations)
 *   /accept/(job)     POST {person_id, car_rate_accepted}            — the proposal engaged
 *   /not_available/(job) POST {person_id, reason, date_from, date_to} — recorded, the next in turn proposed
 *   /bypass/(job)     POST {person_id, reason, warning_ack, car_rate_accepted}
 *   /registry         the driver details & agreed rates section of the Drivers & Guides master · POST /registry/(person)
 *   /unavailable      POST add · POST /unavailable/(id)/clear
 *   /log              the report: every bypass and Not Available (and the acceptances)
 * Reading needs the Transport Operations page; deciding needs the Driver Allocation privilege, the same as engaging by hand.
 */
class TransportAllocationController extends BaseController
{
    public const URL = 'tour/transport_ops/allocations';

    private ?array $menuData = null;
    private ?string $actorName = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function userId(): ?int
    {
        $id = (int) session()->get('user_id');
        return $id > 0 ? $id : null;
    }

    private function actorName(): string
    {
        return $this->actorName ??= Staff::displayName($this->db(), $this->userId(), $this->user());
    }

    private function menuData(): array
    {
        if ($this->menuData === null) {
            try {
                $this->menuData = (new MenuLoader())->loadMenuData();
            } catch (\Throwable $e) {
                $this->menuData = ['user_role' => session()->get('group_id'), 'allowed_menu_list' => []];
            }
        }
        return $this->menuData;
    }

    private function holds(string $url): bool
    {
        $md = $this->menuData();
        if ((int) ($md['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ((array) ($md['allowed_menu_list'] ?? []) as $m) {
            $u = is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '');
            if (trim((string) $u, '/') === $url) {
                return true;
            }
        }
        return false;
    }

    /** The Driver Allocation privilege — the same test the Transport Desk makes before engaging a driver by hand. */
    private function canAllocate(): bool
    {
        return $this->holds(Desks::ALLOCATION_URL) || in_array((int) ($this->menuData()['user_role'] ?? 0), Desks::LEGACY_ALLOCATION_GROUPS, true);
    }

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : ($this->request->getPost() ?: []);
    }

    private function fail(\Throwable $e)
    {
        return $this->json(['status' => 400, 'message' => $e->getMessage()], 400);
    }

    private function rot(): Rotation
    {
        return new Rotation($this->db(), $this->user());
    }

    private function people(): CrewPeople
    {
        return new CrewPeople($this->db(), $this->user(), $this->userId(), $this->actorName());
    }

    private function svc(): TourService
    {
        $db = $this->db();
        return new TourService($db, $this->user(), (new IntakeService($db, $this->user(), new HotelRateService($db, $this->user())))->withActor($this->userId(), $this->actorName()));
    }

    private function page(string $view, array $extra = [])
    {
        $data = $this->menuData();
        $data['user'] = $this->user();
        $data['page_url'] = self::URL;
        $data['can_allocate'] = $this->canAllocate();
        $data['installed'] = false;
        try {
            $version = Installer::installedVersion($this->db());
            $data['installed'] = $version !== null;
            if ($data['installed']) {
                $this->rot()->ensure();
            }
        } catch (\Throwable $e) {
            $data['install_error'] = $e->getMessage();
        }
        $data['rest_days'] = Rotation::REST_DAYS;
        $data['warning_bypass'] = Rotation::WARNING_BYPASS;
        $data['me_name'] = $this->actorName();
        return view($view, $data + $extra);
    }

    // ------------------------------------------------------------------ pages

    public function index()
    {
        return $this->page('pages/tour/transport_allocations', ['lane' => (string) ($this->request->getGet('lane') ?? 'pending')]);
    }

    public function registry()
    {
        return $this->page('pages/tour/transport_rotation_registry');
    }

    public function logPage()
    {
        return $this->page('pages/tour/transport_rotation_log');
    }


    // ------------------------------------------------------------- the combined taxi queue (tf-taxiq-r1)

    public function queue()
    {
        return $this->page('pages/tour/transport_taxi_queue');
    }

    /**
     * One lane for cars and vans together: who is next, and why. Positions come from the record — the day each
     * driver's last tour ended — never from a hand-sorted list. Reads only; allocation stays where it is.
     */
    public function queueData()
    {
        try {
            $today = date('Y-m-d');
            $rot   = $this->rot();
            $rows  = $rot->registry($this->people());
            // tf-taxiq-r4: the tours each driver is ENGAGED on and has not finished — a driver given a tour has had
            // his turn, whether the tour has run yet or not, and must not stand in the same place as somebody waiting.
            $engaged = [];
            try {
                foreach ($this->db()->query(
                    "SELECT c.person_id, t.tour_no, t.arrival_date, t.departure_date
                       FROM tf_transport_crew c
                       JOIN tf_transport_job j ON j.id = c.job_id AND j.archived_at IS NULL
                       JOIN tf_tour t ON t.id = j.tour_id AND t.stage <> 'cancelled' AND t.archived_at IS NULL
                      WHERE c.status = 'engaged' AND c.person_id IS NOT NULL AND (t.departure_date IS NULL OR t.departure_date >= ?)
                      ORDER BY t.arrival_date", [$today])->getResultArray() as $e) {
                    $pid = (int) $e['person_id'];
                    if (!isset($engaged[$pid]) || $e['arrival_date'] < $engaged[$pid]['arrival_date']) {
                        // the nearest tour he holds is the one the lane names
                        $engaged[$pid] = $e + ['holds' => 0, 'queue_from' => null];
                    }
                    $engaged[$pid]['holds']++;
                    // his place in the line is measured from the END of the LAST tour he holds, as the rotation measures it
                    $end = (string) ($e['departure_date'] ?: $e['arrival_date']);
                    if ($end > (string) $engaged[$pid]['queue_from']) {
                        $engaged[$pid]['queue_from'] = $end;
                    }
                }
            } catch (\Throwable $e) {
            }
            $queue = [];
            foreach ($rows as $r) {
                if ((int) $r['in_pool'] !== 1 || (string) ($r['status'] ?? 'active') !== 'active') {
                    continue;   // the Super Administrator's selection decides who is in the lane
                }
                $veh = null;
                foreach ($r['vehicles'] as $v) {
                    if ((int) $v['current'] === 1) {
                        $veh = $v;
                        break;
                    }
                }
                $veh = $veh ?: ($r['vehicles'][0] ?? null);
                // where he stands today: on a tour, resting the five days after one, away, or ready
                $state = 'ready';
                $note  = 'Ready';
                $until = null;
                foreach ((array) $r['unavailable'] as $u) {
                    if ($u['date_from'] <= $today && $u['date_to'] >= $today) {
                        $state = 'away';
                        $note  = trim((string) ($u['reason'] ?? '')) ?: 'Not available';
                        $until = $u['date_to'];
                    }
                }
                if ($state === 'ready' && !empty($r['free_from']) && $r['free_from'] > $today) {
                    $state = 'resting';
                    $note  = 'Rest through ' . date('j M', strtotime((string) $r['free_from']) - 86400);
                    $until = $r['free_from'];
                }
                $job = $engaged[(int) $r['id']] ?? null;
                if ($state === 'ready' && $job !== null) {
                    $running = ($job['arrival_date'] ?? null) !== null && $job['arrival_date'] <= $today;
                    $state = $running ? 'on_tour' : 'allocated';
                    $note  = ($running ? 'On tour' : 'Allocated')
                        . ($job['tour_no'] ? ' — ' . $job['tour_no'] : '')
                        . ((int) $job['holds'] > 1 ? ' (+' . ((int) $job['holds'] - 1) . ' more)' : '')
                        . ($job['departure_date'] ? ($running ? ', ends ' : ', from ' . date('j M', strtotime((string) $job['arrival_date'])) . ' to ') . date('j M', strtotime((string) $job['departure_date'])) : '');
                }
                $blocked = array_merge((array) $r['vehicle_missing'], (array) $r['payment_missing']);
                if ($state === 'ready' && $blocked) {
                    $state = 'blocked';
                    $note  = 'Cannot be allocated: ' . implode('; ', $blocked);
                }
                $queue[] = [
                    'person_id' => $r['id'], 'name' => $r['name'], 'nickname' => $r['nickname'],
                    'vehicle' => $veh ? ['reg' => $veh['reg_no'], 'type' => $veh['type'], 'type_name' => $veh['type_name'], 'guests' => $veh['capacity'] ?? $veh['guests']] : null,
                    'kind' => $veh ? ((string) $veh['type'] === 'van' ? 'van' : 'car') : null,
                    'state' => $state, 'note' => $note, 'until' => $until,
                    'last_tour_end' => $r['last_tour_end'], 'last_tour_no' => $r['last_tour_no'], 'free_from' => $r['free_from'],
                    // his turn is counted from the later of: the tour he last finished, and the next tour he already holds
                    'last_served' => max((string) ($r['last_tour_end'] ?? ''), (string) ($job['queue_from'] ?? '')),
                    'holds_tour' => $job['tour_no'] ?? null, 'holds_count' => (int) ($job['holds'] ?? 0),
                    'car_rate_optin' => (int) $r['car_rate_optin'],
                    'agreed_car_rate_km' => $r['agreed_car_rate_km'], 'agreed_van_rate_km' => $r['agreed_van_rate_km'],
                    'master_rate_km' => $r['master_rate_km'], 'mobile' => $r['mobile'],
                    'blocked_why' => $blocked,
                ];
            }
            // fair order: the driver whose last tour ended longest ago is next; a driver who has had none comes first
            usort($queue, static function ($a, $b) {
                $ra = $a['state'] === 'ready' ? 0 : 1;
                $rb = $b['state'] === 'ready' ? 0 : 1;
                if ($ra !== $rb) {
                    return $ra <=> $rb;
                }
                $la = ($a['last_served'] ?? '') !== '' ? $a['last_served'] : '0000-00-00';
                $lb = ($b['last_served'] ?? '') !== '' ? $b['last_served'] : '0000-00-00';
                return $la === $lb ? strcmp((string) $a['name'], (string) $b['name']) : strcmp($la, $lb);
            });
            foreach ($queue as $i => $_) {
                $queue[$i]['position'] = $i + 1;
            }
            // the tour waiting for a driver, if there is one, so the lane can say what it is next FOR
            $next = null;
            foreach ($rot->cards() as $c) {
                if (($c['lane'] ?? '') === 'pending') {
                    $next = ['job_id' => $c['job_id'] ?? ($c['id'] ?? null), 'tour_id' => $c['tour_id'] ?? null, 'tour_no' => $c['tour_no'] ?? null,
                             'tour_name' => $c['tour_name'] ?? null, 'guests' => $c['guests'] ?? ($c['pax_total'] ?? null),
                             'required_class' => $c['required_class_name'] ?? ($c['required_class'] ?? null),
                             'arrival' => $c['arrival_date'] ?? null, 'departure' => $c['departure_date'] ?? null];
                    break;
                }
            }
            $first = null;
            foreach ($queue as $q) {
                if ($q['state'] === 'ready') {
                    $first = $q;
                    break;
                }
            }
            // the van-at-car-rate question: the next driver drives a van, and the tour is small enough for a car
            $carRate = null;
            if ($first && $next && ($first['kind'] ?? '') === 'van' && (int) ($next['guests'] ?? 0) > 0 && (int) $next['guests'] <= 3) {
                $carRate = [
                    'person_id' => $first['person_id'], 'name' => $first['name'], 'guests' => (int) $next['guests'],
                    'van_rate_km' => $first['agreed_van_rate_km'] !== null && $first['agreed_van_rate_km'] !== '' ? (float) $first['agreed_van_rate_km'] : ($first['master_rate_km'] !== null ? (float) $first['master_rate_km'] : null),
                    'car_rate_km' => $first['agreed_car_rate_km'] !== null && $first['agreed_car_rate_km'] !== '' ? (float) $first['agreed_car_rate_km'] : null,
                    'already_opted_in' => (int) $first['car_rate_optin'] === 1,
                ];
            }
            $requests = [];
            try {
                $requests = $this->db()->query("SELECT * FROM tf_rot_car_rate WHERE state = 'requested' ORDER BY id DESC LIMIT 20")->getResultArray();
            } catch (\Throwable $e) {
            }
            return $this->json(['status' => 200, 'data' => [
                'today' => $today, 'rest_days' => Rotation::REST_DAYS,
                'queue' => $queue, 'next_tour' => $next, 'car_rate' => $carRate,
                'requests' => $requests, 'me_id' => $this->userId(),
                'can_approve' => $this->holds('tour/pricing'),
                'in_pool' => count($queue), 'can_allocate' => $this->canAllocate(),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }



    // ---- tf-taxiq-r3: the approvals, where a Pricing holder can actually reach them (Pilot → Pricing) ----

    public function approvalsPage()
    {
        $data = $this->menuData();
        $data['user'] = $this->user();
        $data['page_url'] = 'tour/pricing/taxi_approvals';
        $data['can_approve'] = $this->holds('tour/pricing');
        $data['me_name'] = $this->actorName();
        return view('pages/tour/taxi_rate_approvals', $data);
    }

    public function approvalsData()
    {
        try {
            if (!$this->holds('tour/pricing')) {
                return $this->json(['status' => 403, 'message' => 'Car-rate approvals need the Pricing privilege.'], 403);
            }
            $rows = $this->db()->query("SELECT * FROM tf_rot_car_rate ORDER BY state = 'requested' DESC, id DESC LIMIT 60")->getResultArray();
            return $this->json(['status' => 200, 'data' => ['requests' => $rows, 'me_id' => $this->userId(), 'me' => $this->actorName()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /** null when a car-rate allocation may go ahead; otherwise what is still missing, in the staff's own words. */
    private function carRateApproved(int $personId, int $jobId): ?string
    {
        try {
            $r = $this->db()->query("SELECT * FROM tf_rot_car_rate WHERE person_id = ? AND (job_id = ? OR job_id IS NULL) ORDER BY id DESC LIMIT 1",
                [$personId, $jobId])->getRowArray();
        } catch (\Throwable $e) {
            return 'The car-rate approval record could not be read, so the allocation was not made.';
        }
        if (!$r) {
            return 'This van is being allocated at the car rate, and nothing is on the record: ask the driver first, then request approval on the Taxi queue.';
        }
        if ((string) $r['state'] === 'approved') {
            return null;
        }
        if ((string) $r['state'] === 'requested') {
            return 'The car rate for ' . $r['person_name'] . ' is waiting for approval (requested by ' . $r['requested_by'] . ' on ' . substr((string) $r['requested_at'], 0, 16) . '). The allocation waits for it.';
        }
        return 'The car rate for ' . $r['person_name'] . ' was ' . $r['state'] . ($r['decision_note'] ? ' — ' . $r['decision_note'] : '') . '. Allocate him at the van rate, or ask the next driver.';
    }

    /** POST queue/approval/(:num) — the approver's decision. Never the person who raised it. */
    public function carRateDecide($id)
    {
        try {
            if (!$this->holds('tour/pricing')) {
                return $this->json(['status' => 403, 'message' => 'Approving a car rate needs the Pricing privilege (Pilot → Pricing).'], 403);
            }
            $b   = $this->body();
            $yes = !empty($b['approve']);
            $note = mb_substr(trim((string) ($b['note'] ?? '')), 0, 400);
            $db  = $this->db();
            $r   = $db->query('SELECT * FROM tf_rot_car_rate WHERE id = ?', [(int) $id])->getRowArray();
            if (!$r) {
                return $this->json(['status' => 404, 'message' => 'That request is not on the record.']);
            }
            if ((string) $r['state'] !== 'requested') {
                return $this->json(['status' => 409, 'message' => 'This request is already ' . $r['state'] . '. Nothing was changed.']);
            }
            if ((int) ($r['requested_by_id'] ?? 0) === (int) $this->userId() && (int) $this->userId() !== 0) {
                return $this->json(['status' => 403, 'message' => 'A car rate is approved by somebody other than the person who asked for it. This one is yours.']);
            }
            $db->query("UPDATE tf_rot_car_rate SET state = ?, decided_by = ?, decided_by_id = ?, decided_at = ?, decision_note = ? WHERE id = ? AND state = 'requested'",
                [$yes ? 'approved' : 'declined', $this->actorName(), $this->userId(), date('Y-m-d H:i:s'), $note ?: null, (int) $id]);
            if ((int) $db->affectedRows() === 0) {
                return $this->json(['status' => 409, 'message' => 'Somebody decided this a moment ago. Nothing was changed.']);
            }
            $this->rot()->log(['job_id' => $r['job_id'], 'tour_id' => $r['tour_id'], 'tour_no' => $r['tour_no'],
                'person_id' => (int) $r['person_id'], 'person_name' => $r['person_name'],
                'event' => $yes ? 'car_rate_approved' : 'car_rate_refused', 'reason' => $note ?: null,
                'details' => ['rate_km' => $r['agreed_rate_km'], 'by' => $this->actorName()]]);
            return $this->json(['status' => 200, 'message' => $yes
                ? 'Approved at LKR ' . number_format((float) $r['agreed_rate_km'], 2) . ' per km. The tour may now be allocated to ' . $r['person_name'] . ' on Pending Allocations.'
                : 'Refused. ' . $r['person_name'] . ' keeps his place in the queue; allocate him at the van rate or consider the next driver.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * The driver's own answer to a car-rate offer on a van, and the agreed figure. It records the answer and
     * raises the approval that must come before the allocation; it allocates nothing and moves nobody's turn.
     */
    public function carRateAnswer($personId)
    {
        try {
            if (!$this->canAllocate()) {
                return $this->json(['status' => 403, 'message' => 'Recording a car-rate agreement needs the Driver Allocation privilege.'], 403);
            }
            $b      = $this->body();
            $agrees = !empty($b['agrees']);
            $rate   = isset($b['rate']) && $b['rate'] !== '' ? (float) $b['rate'] : null;
            $why    = trim((string) ($b['note'] ?? ''));
            if ($agrees && ($rate === null || $rate <= 0)) {
                return $this->json(['status' => 400, 'message' => 'Enter the rate per kilometre he agreed to.']);
            }
            $rot = $this->rot();
            $was = $this->db()->query('SELECT * FROM tf_rot_driver WHERE person_id = ?', [(int) $personId])->getRowArray() ?: [];
            $who = $this->db()->query('SELECT name FROM tf_crew_person WHERE id = ?', [(int) $personId])->getRowArray();
            if ($agrees) {
                // everything else on his row stays as it was — his place in the pool is not touched by an answer
                $rot->saveDriver((int) $personId, [
                    'in_pool' => (int) ($was['in_pool'] ?? 1),
                    'car_rate_optin' => 1,
                    'agreed_car_rate_km' => $rate,
                    'agreed_van_rate_km' => $was['agreed_van_rate_km'] ?? null,
                    'rate_note' => $why !== '' ? $why : ($was['rate_note'] ?? 'Agreed to take a small tour at the car rate.'),
                    'notes' => $was['notes'] ?? null,
                ]);
            }
            $requestId = null;
            if ($agrees) {
                // the agreement raises the approval that must come before the allocation, as a discount does
                $open = $this->db()->query("SELECT id FROM tf_rot_car_rate WHERE person_id = ? AND state = 'requested' AND (job_id <=> ?) LIMIT 1",
                    [(int) $personId, $b['job_id'] ?? null])->getRowArray();
                if ($open) {
                    $requestId = (int) $open['id'];
                } else {
                    $this->db()->query('INSERT INTO tf_rot_car_rate (job_id, tour_id, tour_no, person_id, person_name, guests, van_rate_km, car_rate_km, agreed_rate_km, driver_said, state, requested_by, requested_by_id, requested_at)
                                        VALUES (?,?,?,?,?,?,?,?,?,?,\'requested\',?,?,?)',
                        [$b['job_id'] ?? null, $b['tour_id'] ?? null, $b['tour_no'] ?? null, (int) $personId, $who['name'] ?? null,
                         isset($b['guests']) ? (int) $b['guests'] : null,
                         isset($b['van_rate']) && $b['van_rate'] !== '' ? (float) $b['van_rate'] : null,
                         isset($b['car_rate']) && $b['car_rate'] !== '' ? (float) $b['car_rate'] : null,
                         $rate, $why !== '' ? $why : null, $this->actorName(), $this->userId(), date('Y-m-d H:i:s')]);
                    $requestId = (int) $this->db()->insertID();
                }
            }
            $rot->log([
                'job_id' => $b['job_id'] ?? null, 'tour_id' => $b['tour_id'] ?? null, 'tour_no' => $b['tour_no'] ?? null,
                'person_id' => (int) $personId, 'person_name' => $who['name'] ?? null,
                'event' => $agrees ? 'car_rate_agreed' : 'car_rate_declined',
                'reason' => $why !== '' ? $why : null,
                'details' => $agrees
                    ? ['agreed_rate_km' => $rate, 'request_id' => $requestId, 'needs' => 'approval before the allocation']
                    : ['kept' => 'his position in the queue is unchanged'],
            ]);
            return $this->json(['status' => 200, 'request_id' => $requestId, 'message' => $agrees
                ? 'Recorded: agreed at LKR ' . number_format((float) $rate, 2) . ' per km. Request #' . $requestId . ' is now waiting for approval by somebody holding Pricing — the tour cannot be allocated to him until it is approved.'
                : 'Recorded: declined. His position in the queue is unchanged, and the next suitable driver is considered.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // ------------------------------------------------------------------ JSON

    public function data()
    {
        try {
            $rot = $this->rot();
            $cards = $rot->cards();
            $lanes = ['awaiting_review' => [], 'pending' => [], 'confirming' => [], 'upcoming' => [], 'on_tour' => [], 'done' => []];
            $counts = array_fill_keys(array_keys($lanes), 0);
            foreach ($cards as $c) {
                $lanes[$c['lane']][] = $c;
                $counts[$c['lane']]++;
            }
            return $this->json(['status' => 200, 'data' => ['lanes' => $lanes, 'counts' => $counts, 'today' => date('Y-m-d'), 'can_allocate' => $this->canAllocate()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function assess($jobId)
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->rot()->assess((int) $jobId, $this->people())]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function review($jobId)
    {
        try {
            $rot = $this->rot();
            if ($this->request->getMethod() === 'post' || strtoupper($this->request->getMethod()) === 'POST') {
                if (!$this->canAllocate()) {
                    return $this->json(['status' => 403, 'message' => 'Sending a card for driver allocation needs the Driver Allocation privilege (Operations).'], 403);
                }
                $b = $this->body();
                $rot->markReviewed((int) $jobId, !isset($b['on']) || (int) $b['on'] === 1, isset($b['note']) ? mb_substr(trim((string) $b['note']), 0, 400) : null);
            }
            $r = $rot->review((int) $jobId);
            return $this->json(['status' => 200, 'data' => ['reviewed' => (bool) $r, 'review' => $r ?: null, 'counts' => $rot->counts(), 'url' => base_url(self::URL)]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function accept($jobId)
    {
        try {
            if (!$this->canAllocate()) {
                return $this->json(['status' => 403, 'message' => 'Allocating a driver needs the Driver Allocation privilege (Operations).'], 403);
            }
            $b = $this->body();
            // tf-taxiq-r2: a van taking a small tour at the car rate is allocated only once the driver has agreed
            // AND somebody else has approved it. Every other allocation is untouched.
            if (!empty($b['car_rate_accepted'])) {
                $ok = $this->carRateApproved((int) ($b['person_id'] ?? 0), (int) $jobId);
                if ($ok !== null) {
                    return $this->json(['status' => 409, 'message' => $ok]);
                }
            }
            $card = $this->rot()->allocate($this->svc(), $this->people(), (int) $jobId, (int) ($b['person_id'] ?? 0), ['car_rate_accepted' => !empty($b['car_rate_accepted'])]);
            return $this->json(['status' => 200, 'message' => 'Driver allocated by rotation.', 'data' => ['card' => $card]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function bypass($jobId)
    {
        try {
            if (!$this->canAllocate()) {
                return $this->json(['status' => 403, 'message' => 'Allocating a driver needs the Driver Allocation privilege (Operations).'], 403);
            }
            $b = $this->body();
            $card = $this->rot()->allocate($this->svc(), $this->people(), (int) $jobId, (int) ($b['person_id'] ?? 0),
                ['bypass' => true, 'reason' => (string) ($b['reason'] ?? ''), 'warning_ack' => !empty($b['warning_ack']), 'car_rate_accepted' => !empty($b['car_rate_accepted'])]);
            return $this->json(['status' => 200, 'message' => 'Driver allocated — rotation bypassed and recorded.', 'data' => ['card' => $card]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function notAvailable($jobId)
    {
        try {
            if (!$this->canAllocate()) {
                return $this->json(['status' => 403, 'message' => 'Recording Not Available needs the Driver Allocation privilege (Operations).'], 403);
            }
            $b = $this->body();
            if (trim((string) ($b['reason'] ?? '')) === '') {
                return $this->json(['status' => 400, 'message' => 'The reason is required.'], 400);
            }
            $a = $this->rot()->notAvailable($this->people(), (int) $jobId, (int) ($b['person_id'] ?? 0), trim((string) $b['reason']), $b['date_from'] ?? null, $b['date_to'] ?? null);
            return $this->json(['status' => 200, 'message' => 'Not Available recorded; the next driver in turn is proposed.', 'data' => $a]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function registryData()
    {
        try {
            $rot = $this->rot();
            $rot->ensure();
            return $this->json(['status' => 200, 'data' => ['drivers' => $rot->registry($this->people()), 'can_edit' => $this->canAllocate()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function registrySave($personId)
    {
        try {
            if (!$this->canAllocate()) {
                return $this->json(['status' => 403, 'message' => 'Changing the rotation details needs the Driver Allocation privilege (Operations).'], 403);
            }
            $this->rot()->saveDriver((int) $personId, $this->body());
            return $this->json(['status' => 200, 'message' => 'Saved.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function unavailableAdd()
    {
        try {
            if (!$this->canAllocate()) {
                return $this->json(['status' => 403, 'message' => 'Recording Not Available needs the Driver Allocation privilege (Operations).'], 403);
            }
            $b = $this->body();
            $id = $this->rot()->addUnavailable((int) ($b['person_id'] ?? 0), (string) ($b['date_from'] ?? ''), (string) ($b['date_to'] ?? ''), (string) ($b['reason'] ?? ''));
            $this->rot()->log(['person_id' => (int) ($b['person_id'] ?? 0), 'event' => 'not_available', 'reason' => (string) ($b['reason'] ?? ''), 'details' => ['date_from' => $b['date_from'] ?? null, 'date_to' => $b['date_to'] ?? null, 'from_registry' => true]]);
            return $this->json(['status' => 200, 'message' => 'Not Available period recorded.', 'id' => $id]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function unavailableClear($id)
    {
        try {
            if (!$this->canAllocate()) {
                return $this->json(['status' => 403, 'message' => 'Needs the Driver Allocation privilege (Operations).'], 403);
            }
            $this->rot()->clearUnavailable((int) $id);
            return $this->json(['status' => 200, 'message' => 'Cleared.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET legacy — how the earlier Pending Driver Allocation page's tours relate to the Transport Desk (read only)
    public function legacy()
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->rot()->legacySummary()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function logData()
    {
        try {
            $g = fn (string $k) => (string) ($this->request->getGet($k) ?? '');
            return $this->json(['status' => 200, 'data' => $this->rot()->report(['event' => $g('event'), 'from' => $g('from'), 'to' => $g('to'), 'q' => $g('q')])]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
