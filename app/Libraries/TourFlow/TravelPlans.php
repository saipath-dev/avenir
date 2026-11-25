<?php

namespace App\Libraries\TourFlow;

/**
 * tf-travel-r1 (24 Nov 2026, Shantha's Passenger Travel Plans mock-up) — passengers of one inquiry who leave before the rest.
 *
 * A travel plan does not keep a second copy of the party, the rooms or the vehicles. It records what only it knows (who leaves,
 * the flight, the pickup) and sets up the inquiry's own machinery from it:
 *   tf_intake_group     the departing passengers become their own group, ending on their last hotel night; the others are the
 *                       continuing group. Hotel nights are costed from the groups present that night (IntakeService::roomMix).
 *   tf_intake_pax       the departing passengers' group_id points at the plan's group (one plan per passenger).
 *   tf_intake_vehicle   the plan's own airport transfer is a vehicle line for its group; the main vehicle, driver and guide stay
 *                       with the continuing party and are never cut in proportion.
 *   tf_intake_service   from the departure day, per-person lines get an attendance override for the people still there; a meal
 *   tf_intake_excursion keeps its own adults; anything set by hand is listed for review, not changed. Every figure changed is
 *                       remembered in tf_travel_adjust and put back before the plans are applied again or removed.
 *
 * Rooms are never guessed: each group's room mix is the person's, and the plan is not ready until somebody confirms the room
 * allocation. Confirmed hotel bookings are listed for amendment and never changed here. Issued quotation versions are frozen
 * and are not touched. Every save and removal is written to the inquiry history.
 */
final class TravelPlans
{
    private const REASON = 'Travel plan';
    private static bool $ensured = false;

    private $db;
    private IntakeService $take;
    private string $user;

    public function __construct($db, IntakeService $take, string $user = '')
    {
        $this->db = $db;
        $this->take = $take;
        $this->user = $user !== '' ? $user : 'system';
        self::ensure($db);
        PaxRooms::ensure($db);   // its tables first: a CREATE inside the preview's trial would end the trial early
        $take->ensureDayRooms();   // tf-hotelpick-r1: the same for the room rows of a night
        $take->ensureDayPlans();   // tf-dayplan-r1: and the day plans
        $take->ensureRoomOptions();   // tf-roomopt-r1
    }

    public static function ensure($db): void
    {
        if (self::$ensured) {
            return;
        }
        self::$ensured = true;
        $db->query("CREATE TABLE IF NOT EXISTS `tf_travel_plan` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `intake_id` INT UNSIGNED NOT NULL, `group_id` INT UNSIGNED NULL, `vehicle_line_id` INT UNSIGNED NULL,
            `depart_date` DATE NOT NULL, `flight` VARCHAR(40) NULL, `flight_time` VARCHAR(5) NULL, `airport` VARCHAR(60) NULL,
            `pickup_date` DATE NULL, `pickup_time` VARCHAR(5) NULL, `pickup_place` VARCHAR(160) NULL, `vehicle_class` VARCHAR(20) NULL,
            `rooms_sgl` SMALLINT NOT NULL DEFAULT 0, `rooms_dbl` SMALLINT NOT NULL DEFAULT 0, `rooms_twn` SMALLINT NOT NULL DEFAULT 0, `rooms_tpl` SMALLINT NOT NULL DEFAULT 0,
            `join_json` VARCHAR(2000) NULL, `note` VARCHAR(500) NULL, `rooms_reviewed_by` VARCHAR(100) NULL, `rooms_reviewed_at` DATETIME NULL,
            `created_at` DATETIME NULL, `created_by` VARCHAR(100) NULL, `updated_at` DATETIME NULL, `updated_by` VARCHAR(100) NULL,
            PRIMARY KEY (`id`), KEY `k_intake` (`intake_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS `tf_travel_member` (`plan_id` INT UNSIGNED NOT NULL, `pax_id` INT UNSIGNED NOT NULL, `intake_id` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`plan_id`, `pax_id`), UNIQUE KEY `u_pax` (`pax_id`), KEY `k_intake` (`intake_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS `tf_travel_adjust` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `intake_id` INT UNSIGNED NOT NULL, `ref_type` VARCHAR(12) NOT NULL,
            `ref_id` INT UNSIGNED NOT NULL, `old_json` VARCHAR(1000) NOT NULL, PRIMARY KEY (`id`), KEY `k_intake` (`intake_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS `tf_travel_state` (`intake_id` INT UNSIGNED NOT NULL, `cont_group_id` INT UNSIGNED NULL, `cont_created` TINYINT(1) NOT NULL DEFAULT 0,
            `cont_base_pax` SMALLINT NULL, `cont_rooms_json` VARCHAR(200) NULL, `extra_group_id` INT UNSIGNED NULL, `extra_rooms_json` VARCHAR(200) NULL,
            PRIMARY KEY (`intake_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function installed($db): bool
    {
        try {
            return (bool) $db->query("SHOW TABLES LIKE 'tf_travel_plan'")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    // =====================================================================
    //  Reading
    // =====================================================================

    private function rows(string $sql, array $b = []): array
    {
        return $this->db->query($sql, $b)->getResultArray();
    }

    private function row(string $sql, array $b = []): ?array
    {
        return $this->db->query($sql, $b)->getRowArray() ?: null;
    }

    private static function rooms(array $r, string $p = 'rooms_'): array
    {
        return ['sgl' => (int) ($r[$p . 'sgl'] ?? $r['sgl'] ?? 0), 'dbl' => (int) ($r[$p . 'dbl'] ?? $r['dbl'] ?? 0), 'twn' => (int) ($r[$p . 'twn'] ?? $r['twn'] ?? 0), 'tpl' => (int) ($r[$p . 'tpl'] ?? $r['tpl'] ?? 0)];
    }

    public static function roomsText(array $m): string
    {
        $w = ['sgl' => 'single', 'dbl' => 'double', 'twn' => 'twin', 'tpl' => 'triple'];
        $t = [];
        foreach ($w as $k => $n) {
            if ((int) ($m[$k] ?? 0) > 0) {
                $t[] = (int) $m[$k] . ' ' . $n . ((int) $m[$k] === 1 ? '' : 's');
            }
        }
        return $t ? implode(' + ', $t) : 'no rooms set';
    }

    private static function beds(array $m): int
    {
        return (int) $m['sgl'] + 2 * ((int) $m['dbl'] + (int) $m['twn']) + 3 * (int) $m['tpl'];
    }

    private function state(int $id): array
    {
        return $this->row('SELECT * FROM tf_travel_state WHERE intake_id = ?', [$id]) ?: ['intake_id' => $id, 'cont_group_id' => null, 'cont_created' => 0, 'cont_base_pax' => null, 'cont_rooms_json' => null, 'extra_group_id' => null, 'extra_rooms_json' => null];
    }

    private function saveState(array $s): void
    {
        $this->db->query('REPLACE INTO tf_travel_state (intake_id, cont_group_id, cont_created, cont_base_pax, cont_rooms_json, extra_group_id, extra_rooms_json) VALUES (?,?,?,?,?,?,?)',
            [$s['intake_id'], $s['cont_group_id'], (int) $s['cont_created'], $s['cont_base_pax'], $s['cont_rooms_json'], $s['extra_group_id'], $s['extra_rooms_json']]);
    }

    /** The plans of one inquiry, with their members, in departure order. */
    public function plans(int $id): array
    {
        $out = [];
        foreach ($this->rows('SELECT * FROM tf_travel_plan WHERE intake_id = ? ORDER BY depart_date, pickup_date, id', [$id]) as $p) {
            $p['members'] = array_map('intval', array_column($this->rows('SELECT pax_id FROM tf_travel_member WHERE plan_id = ?', [(int) $p['id']]), 'pax_id'));
            $p['join'] = array_values(array_map('intval', json_decode((string) ($p['join_json'] ?? ''), true) ?: []));
            $out[] = $p;
        }
        return $out;
    }

    /** The day number of a date on this inquiry (arrival = day 1). */
    private static function dayNo(?string $arrival, ?string $date): ?int
    {
        if (!$arrival || !$date) {
            return null;
        }
        return (int) round((strtotime($date) - strtotime($arrival)) / 86400) + 1;
    }

    /** last hotel night and the day they go, from the pickup (the night before a flight means that night is not slept). */
    private static function nightsOf(array $p, ?string $arrival): array
    {
        $pick = $p['pickup_date'] ?: $p['depart_date'];
        $pickDay = self::dayNo($arrival, $pick);
        $last = $pickDay !== null ? $pickDay - 1 : null;
        return ['last_night' => $last, 'leaving_day' => $pickDay, 'night_before' => $p['pickup_date'] && $p['pickup_date'] < $p['depart_date']];
    }

    public function data(int $id): array
    {
        $it = $this->take->get($id);
        if (!$it) {
            throw new \RuntimeException('No such inquiry.');
        }
        $plans = $this->plans($id);
        $st = $this->state($id);
        $arrival = $it['arrival_date'] ?? null;
        $nights = (int) (($it['tour_dates']['nights'] ?? null) ?? ($it['nights'] ?? 0));
        $paxTotal = (int) ($it['pax_total'] ?? 0);
        $planOf = [];
        foreach ($plans as $p) {
            foreach ($p['members'] as $m) {
                $planOf[$m] = (int) $p['id'];
            }
        }
        $names = [];
        $pax = [];
        foreach (($it['passengers'] ?? []) as $r) {
            $names[(int) $r['id']] = (string) $r['full_name'];
            $pax[] = ['id' => (int) $r['id'], 'name' => (string) $r['full_name'], 'type' => $r['pax_type'] ?? null, 'group_id' => $r['group_id'] !== null ? (int) $r['group_id'] : null, 'plan_id' => $planOf[(int) $r['id']] ?? null];
        }
        $groups = [];
        foreach (($it['groups'] ?? []) as $g) {
            $groups[(int) $g['id']] = $g;
        }
        $hotelOf = [];
        foreach (($it['days'] ?? []) as $d) {
            $hotelOf[(int) $d['day_no']] = trim((string) ($d['hotel_name_raw'] ?? '')) ?: null;
        }
        $vehicles = [];
        foreach (($it['vehicles'] ?? []) as $v) {
            $vehicles[(int) $v['id']] = $v;
        }
        $mil = $it['mileage']['by_vehicle'] ?? [];
        $milBy = [];
        foreach ($mil as $bv) {
            $milBy[(int) $bv['vehicle_seq']] = $bv;
        }
        $outPlans = [];
        $leavingByDay = [];
        foreach ($plans as $i => $p) {
            $n = self::nightsOf($p, $arrival);
            $v = $p['vehicle_line_id'] ? ($vehicles[(int) $p['vehicle_line_id']] ?? null) : null;
            $bv = $v ? ($milBy[(int) $v['sort_order']] ?? null) : null;
            $legs = $v ? array_values(array_filter($it['sectors'] ?? [], static fn ($s) => (int) $s['vehicle_seq'] === (int) $v['sort_order'] && empty($s['removed_at']))) : [];
            $depServices = array_values(array_map(static fn ($s) => ['id' => (int) $s['id'], 'title' => (string) $s['title'], 'kind' => $s['kind']],
                array_filter($it['services'] ?? [], static fn ($s) => (int) $s['day_no'] === (int) $n['leaving_day'])));
            foreach ($p['members'] as $m) {
                $leavingByDay[(int) $n['leaving_day']][] = $names[$m] ?? ('#' . $m);
            }
            $outPlans[] = [
                'id' => (int) $p['id'], 'no' => $i + 1, 'group_id' => $p['group_id'] !== null ? (int) $p['group_id'] : null,
                'depart_date' => $p['depart_date'], 'depart_day' => self::dayNo($arrival, $p['depart_date']), 'flight' => $p['flight'], 'flight_time' => $p['flight_time'], 'airport' => $p['airport'],
                'pickup_date' => $p['pickup_date'], 'pickup_time' => $p['pickup_time'], 'pickup_place' => $p['pickup_place'], 'vehicle_class' => $p['vehicle_class'],
                'rooms' => self::rooms($p), 'members' => $p['members'], 'member_names' => array_map(static fn ($m) => $names[$m] ?? ('#' . $m), $p['members']),
                'join' => $p['join'], 'note' => $p['note'], 'last_night' => $n['last_night'], 'leaving_day' => $n['leaving_day'], 'night_before' => $n['night_before'],
                'last_hotel' => $hotelOf[(int) $n['last_night']] ?? null,
                'rooms_reviewed_by' => $p['rooms_reviewed_by'], 'rooms_reviewed_at' => $p['rooms_reviewed_at'],
                'transfer' => $v ? ['line_id' => (int) $v['id'], 'label' => $v['label'], 'type' => $v['type'], 'class' => $v['class_code'], 'seats' => $v['seats'],
                    'legs' => array_map(static fn ($s) => ['day' => (int) $s['day_no'], 'from' => $s['from_place'], 'to' => $s['to_place'], 'km' => $s['km'] !== null ? (float) $s['km'] : null, 'kind' => $s['kind']], $legs),
                    'km' => $bv ? (float) $bv['charged_km'] : null, 'unpriced' => $bv ? (int) $bv['unpriced'] : null, 'amount' => $bv['amount'] ?? null, 'currency' => $v['currency']] : null,
                'dep_day_services' => $depServices,
            ];
        }
        // the party by itinerary day and by hotel night, from the groups present
        $days = [];
        for ($n = 1; $n <= max(1, $nights + 1); $n++) {
            $date = $arrival ? date('Y-m-d', strtotime($arrival . ' +' . ($n - 1) . ' day')) : null;
            $mix = $n <= $nights ? IntakeService::roomMix($it, $n) : null;
            $nightGuests = $n <= $nights ? ($mix['pax'] !== null ? (int) $mix['pax'] : $paxTotal) : 0;
            $dayGuests = $paxTotal - array_sum(array_map(static fn ($p) => count($p['members']), array_filter($outPlans, static fn ($p) => $p['leaving_day'] !== null && $n > $p['leaving_day'])));
            $days[] = ['day_no' => $n, 'date' => $date, 'hotel' => $n <= $nights ? ($hotelOf[$n] ?? null) : null, 'night' => $n <= $nights,
                'night_guests' => $nightGuests, 'day_guests' => $dayGuests, 'leaving' => $leavingByDay[$n] ?? [],
                'mix' => $mix ? ['sgl' => (int) $mix['sgl'], 'dbl' => (int) $mix['dbl'], 'twn' => (int) $mix['twn'], 'tpl' => (int) $mix['tpl'], 'rooms' => (int) $mix['rooms'], 'beds' => self::beds($mix)] : null];
        }
        // the room plan: consecutive nights with the same guests and rooms, as one line
        $plan = [];
        foreach ($days as $d) {
            if (!$d['night']) {
                continue;
            }
            $k = $d['night_guests'] . '|' . json_encode($d['mix']);
            $lastI = count($plan) - 1;
            if ($lastI >= 0 && $plan[$lastI]['k'] === $k && $plan[$lastI]['to_day'] === $d['day_no'] - 1) {
                $plan[$lastI]['to_day'] = $d['day_no'];
                $plan[$lastI]['to'] = $d['date'];
                continue;
            }
            $plan[] = ['k' => $k, 'from_day' => $d['day_no'], 'to_day' => $d['day_no'], 'from' => $d['date'], 'to' => $d['date'], 'guests' => $d['night_guests'], 'mix' => $d['mix'],
                'mix_text' => self::roomsText($d['mix'] ?? []), 'fits' => $d['mix'] ? $d['mix']['beds'] >= $d['night_guests'] : false];
        }
        $reviewNeed = array_filter($outPlans, static fn ($p) => !$p['rooms_reviewed_at']);
        foreach ($plan as &$r) {
            $changed = false;
            foreach ($outPlans as $p) {
                if ($p['last_night'] !== null && $r['to_day'] > $p['last_night']) {
                    $changed = true;
                }
            }
            $r['status'] = !$r['fits'] ? 'short' : ($changed && $reviewNeed ? 'review' : ($outPlans ? 'confirmed' : 'whole'));
            unset($r['k']);
        }
        unset($r);
        $cont = $st['cont_group_id'] && isset($groups[(int) $st['cont_group_id']]) ? $groups[(int) $st['cont_group_id']] : null;
        $extra = $st['extra_group_id'] && isset($groups[(int) $st['extra_group_id']]) ? $groups[(int) $st['extra_group_id']] : null;
        $leavingN = array_sum(array_map(static fn ($p) => count($p['members']), $outPlans));
        $classes = array_map(static fn ($c) => ['code' => $c['code'], 'name' => $c['name'] ?? $c['code'], 'seats' => (int) ($c['seats'] ?? 0)], $this->take->planner()->classes());
        return [
            'intake' => ['id' => $id, 'inquiry_no' => $it['inquiry_no'], 'tour_name' => $it['tour_name'] ?? null, 'arrival' => $arrival, 'departure' => $it['departure_date'] ?? null,
                'nights' => $nights, 'pax_total' => $paxTotal, 'status' => $it['status'], 'locked' => $this->lockedWhy($id), 'rooms' => self::rooms($it)],
            'passengers' => $pax, 'plans' => $outPlans, 'days' => $days, 'room_plan' => $plan,
            'continuing' => ['pax' => $paxTotal - $leavingN, 'group_id' => $cont ? (int) $cont['id'] : null, 'rooms' => $cont ? self::rooms($cont) : (json_decode((string) $st['cont_rooms_json'], true) ?: self::rooms($it))],
            'extra' => $extra ? ['from_night' => $extra['day_from'] !== null ? (int) $extra['day_from'] : null, 'rooms' => self::rooms($extra)] : null,
            'leaving' => $leavingN, 'classes' => $classes, 'bookings' => $this->bookings($id, $outPlans),
            'open' => $this->issues($id, $it, $outPlans), 'history' => $this->history($id),
            'nights_hotels' => array_map(static fn ($d) => ['day_no' => (int) $d['day_no'], 'hotel' => trim((string) ($d['hotel_name_raw'] ?? '')) ?: null, 'date' => $d['day_date']], $it['days'] ?? []),
        ];
    }

    public function lockedWhy(int $id): ?string
    {
        try {
            $this->take->assertOpen($id);
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    /** Hotel bookings of the inquiry's tour that a travel plan touches — listed for amendment, never changed here. */
    private function bookings(int $id, array $plans): array
    {
        if (!$plans) {
            return [];
        }
        try {
            $tour = $this->row('SELECT id, tour_no, amending FROM tf_tour WHERE intake_id = ? ORDER BY id DESC LIMIT 1', [$id]);
            if (!$tour) {
                return [];
            }
            $first = min(array_map(static fn ($p) => (int) $p['last_night'], $plans));
            $out = [];
            foreach ($this->rows("SELECT id, hotel_name, day_from, day_to, check_in, check_out, status, confirmation_ref FROM tf_hotel_job WHERE tour_id = ? AND archived_at IS NULL AND status NOT IN ('to_book','cancelled') ORDER BY day_from", [(int) $tour['id']]) as $j) {
                $out[] = ['id' => (int) $j['id'], 'hotel' => $j['hotel_name'], 'check_in' => $j['check_in'], 'check_out' => $j['check_out'], 'status' => $j['status'], 'ref' => $j['confirmation_ref'],
                    'why' => (int) $j['day_to'] > $first ? 'Fewer guests from night ' . ($first + 1) . ' — amend the rooms with the hotel' : 'Rooming changes on these nights — check with the hotel'];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function history(int $id): array
    {
        try {
            return $this->rows("SELECT at, by_name, by_user, summary FROM tf_activity WHERE intake_id = ? AND action LIKE 'travel_%' ORDER BY id DESC LIMIT 20", [$id]);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * What is still open, for the Travel Plans tab, the costing and the readiness list: blockers stop the quotation, warnings
     * are shown beside it. Codes start travel_ so the readiness list sends them to the Travel Plans tab.
     */
    public function issues(int $id, ?array $it = null, ?array $plans = null): array
    {
        $out = ['blockers' => [], 'warnings' => []];
        $raw = $this->plans($id);
        if (!$raw) {
            return $out;
        }
        $arrival = $it['arrival_date'] ?? ($this->row('SELECT arrival_date FROM tf_intake WHERE id = ?', [$id])['arrival_date'] ?? null);
        $lines = [];
        foreach ($this->rows('SELECT id FROM tf_intake_vehicle WHERE intake_id = ?', [$id]) as $v) {
            $lines[(int) $v['id']] = true;
        }
        foreach ($raw as $i => $p) {
            $n = self::nightsOf($p, $arrival);
            $who = 'Travel plan ' . ($i + 1) . ' (' . count($p['members']) . ' leaving ' . date('j M', strtotime((string) $p['depart_date'])) . ')';
            if (!$p['pickup_time'] || !$p['pickup_place']) {
                $out['blockers'][] = ['code' => 'travel_pickup_' . $p['id'], 'label' => $who . ': the airport pickup time and hotel are not set.'];
            }
            if (!$p['rooms_reviewed_at']) {
                $out['blockers'][] = ['code' => 'travel_rooms_' . $p['id'], 'label' => $who . ': confirm the room allocation of the departing and the continuing guests (Travel Plans → Room plan).'];
            }
            if (!$p['vehicle_line_id'] || !isset($lines[(int) $p['vehicle_line_id']])) {
                $out['blockers'][] = ['code' => 'travel_vehicle_' . $p['id'], 'label' => $who . ': the separate airport transfer is missing from the vehicles — save the travel plan again.'];
            }
            if (!$p['flight'] || !$p['flight_time']) {
                $out['warnings'][] = ['code' => 'travel_flight_' . $p['id'], 'label' => $who . ': the flight number or time is still to be entered.'];
            }
            if ($n['night_before']) {
                $out['warnings'][] = ['code' => 'travel_dayuse_' . $p['id'], 'label' => $who . ': collected the evening before the flight — arrange a late check-out or day use on ' . date('j M', strtotime((string) $p['pickup_date'])) . ' and add any charge.'];
            }
            // named rooming: a departing passenger still placed in a room of a hotel stay that runs past their last night
            if (isset($stays) === false) {
                $stays = [];
                try {
                    foreach ((new PaxRooms($this->db, $this->user))->stays($id) as $sy) {
                        $stays[(string) $sy['key']] = $sy;
                    }
                } catch (\Throwable $e) {
                }
            }
            if ($stays && $n['last_night'] !== null) {
                foreach ($this->rows('SELECT r.stay_key, r.room_no, x.full_name FROM tf_room_pax rp JOIN tf_room r ON r.id = rp.room_id JOIN tf_intake_pax x ON x.id = rp.pax_id
                                      WHERE r.intake_id = ? AND rp.pax_id IN (' . ($p['members'] ? implode(',', array_map('intval', $p['members'])) : '0') . ')', [$id]) as $rr) {
                    $sy = $stays[(string) $rr['stay_key']] ?? null;
                    if ($sy && (int) $sy['day_to'] > (int) $n['last_night']) {
                        $out['blockers'][] = ['code' => 'travel_rooming_' . $p['id'] . '_' . md5($rr['stay_key'] . $rr['full_name']), 'label' => $who . ': ' . $rr['full_name'] . ' is in room ' . $rr['room_no'] . ' at ' . $sy['hotel']
                            . ' (nights ' . $sy['day_from'] . '–' . $sy['day_to'] . ') but leaves after night ' . $n['last_night'] . ' — review the rooming of that stay (Passengers tab).'];
                    }
                }
            }
        }
        return $out;
    }

    // =====================================================================
    //  Saving
    // =====================================================================

    /**
     * Check a plan as typed. Returns the clean row; throws with the person's words when something does not fit.
     */
    private function clean(int $id, array $in, ?int $planId): array
    {
        $it = $this->row('SELECT arrival_date, departure_date, pax_total, nights FROM tf_intake WHERE id = ?', [$id]);
        if (!$it || !$it['arrival_date'] || !$it['departure_date']) {
            throw new \InvalidArgumentException('Set the tour\'s arrival and departure dates first.');
        }
        $d = static fn ($x) => ($x = trim((string) ($x ?? ''))) !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $x) ? $x : null;
        $t = static fn ($x) => ($x = trim((string) ($x ?? ''))) !== '' && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $x) ? $x : null;
        $dep = $d($in['depart_date'] ?? null);
        if (!$dep) {
            throw new \InvalidArgumentException('Enter the date the group flies out.');
        }
        if ($dep <= $it['arrival_date'] || $dep >= $it['departure_date']) {
            throw new \InvalidArgumentException('An early departure is between the arrival (' . date('j M Y', strtotime($it['arrival_date'])) . ') and the tour\'s own departure (' . date('j M Y', strtotime($it['departure_date'])) . ').');
        }
        $pick = $d($in['pickup_date'] ?? null) ?? $dep;
        $eve = date('Y-m-d', strtotime($dep . ' -1 day'));
        if ($pick !== $dep && $pick !== $eve) {
            throw new \InvalidArgumentException('The pickup is on the day of the flight, or the evening before it.');
        }
        if ($pick <= $it['arrival_date']) {
            throw new \InvalidArgumentException('The pickup cannot be on the arrival day: the group would have no hotel night.');
        }
        $members = array_values(array_unique(array_filter(array_map('intval', (array) ($in['members'] ?? [])))));
        if (!$members) {
            throw new \InvalidArgumentException('Tick the passengers who leave early.');
        }
        $own = array_map('intval', array_column($this->rows('SELECT id FROM tf_intake_pax WHERE intake_id = ?', [$id]), 'id'));
        foreach ($members as $m) {
            if (!in_array($m, $own, true)) {
                throw new \InvalidArgumentException('A ticked passenger is not on this inquiry.');
            }
        }
        if (count($members) >= (int) $it['pax_total']) {
            throw new \InvalidArgumentException('Everybody would leave early. Change the tour\'s departure date instead.');
        }
        $taken = $this->rows('SELECT m.pax_id, m.plan_id, p.full_name FROM tf_travel_member m JOIN tf_intake_pax p ON p.id = m.pax_id WHERE m.intake_id = ?' . ($planId ? ' AND m.plan_id <> ' . (int) $planId : ''), [$id]);
        foreach ($taken as $tk) {
            if (in_array((int) $tk['pax_id'], $members, true)) {
                throw new \InvalidArgumentException($tk['full_name'] . ' is already in another departure group. A passenger leaves once.');
            }
        }
        $st = $this->state($id);
        foreach ($this->rows('SELECT id, full_name, group_id FROM tf_intake_pax WHERE intake_id = ? AND group_id IS NOT NULL', [$id]) as $px) {
            if (!in_array((int) $px['id'], $members, true)) {
                continue;
            }
            $g = (int) $px['group_id'];
            $plansG = array_map('intval', array_column($this->rows('SELECT group_id FROM tf_travel_plan WHERE intake_id = ? AND group_id IS NOT NULL', [$id]), 'group_id'));
            if ($g !== (int) $st['cont_group_id'] && !in_array($g, $plansG, true)) {
                throw new \InvalidArgumentException($px['full_name'] . ' is in a passenger group of its own (Passengers tab). Take them out of that group first.');
            }
        }
        $pt = $t($in['pickup_time'] ?? null);
        $place = trim((string) ($in['pickup_place'] ?? '')) ?: null;
        // the same transfer twice: the same pickup day, time and place as another plan
        foreach ($this->rows('SELECT id, pickup_date, pickup_time, pickup_place FROM tf_travel_plan WHERE intake_id = ?' . ($planId ? ' AND id <> ' . (int) $planId : ''), [$id]) as $o) {
            if ($pt && $o['pickup_time'] === $pt && $o['pickup_date'] === $pick && mb_strtolower((string) $o['pickup_place']) === mb_strtolower((string) $place)) {
                throw new \InvalidArgumentException('Another departure group is already collected at the same time and place. Add these passengers to that group instead of a second transfer.');
            }
        }
        $classes = array_column($this->take->planner()->classes(), 'code');
        $vc = trim((string) ($in['vehicle_class'] ?? ''));
        if ($vc === '' || !in_array($vc, $classes, true)) {
            throw new \InvalidArgumentException('Choose the vehicle for the airport transfer.');
        }
        $r = [];
        foreach (['sgl', 'dbl', 'twn', 'tpl'] as $k) {
            $r[$k] = max(0, min(99, (int) ($in['rooms'][$k] ?? 0)));
        }
        if (self::beds($r) < count($members)) {
            throw new \InvalidArgumentException('The rooms of the departing group sleep ' . self::beds($r) . ', but ' . count($members) . ' leave. Set their rooms (Room plan).');
        }
        $join = array_values(array_unique(array_map('intval', (array) ($in['join'] ?? []))));
        if (isset($in['cont_rooms']) && is_array($in['cont_rooms'])) {
            $leavingOthers = (int) ($this->row('SELECT COUNT(*) n FROM tf_travel_member WHERE intake_id = ?' . ($planId ? ' AND plan_id <> ' . (int) $planId : ''), [$id])['n'] ?? 0);
            $contN = (int) $it['pax_total'] - $leavingOthers - count($members);
            $cr = [];
            foreach (['sgl', 'dbl', 'twn', 'tpl'] as $k) {
                $cr[$k] = max(0, (int) ($in['cont_rooms'][$k] ?? 0));
            }
            if (self::beds($cr) < $contN) {
                throw new \InvalidArgumentException('The continuing guests\' rooms sleep ' . self::beds($cr) . ', but ' . $contN . ' continue. Set their rooms (Room plan).');
            }
        }
        return ['depart_date' => $dep, 'flight' => mb_substr(trim((string) ($in['flight'] ?? '')), 0, 40) ?: null, 'flight_time' => $t($in['flight_time'] ?? null),
            'airport' => mb_substr(trim((string) ($in['airport'] ?? '')), 0, 60) ?: 'Colombo / CMB', 'pickup_date' => $pick, 'pickup_time' => $pt, 'pickup_place' => $place ? mb_substr($place, 0, 160) : null,
            'vehicle_class' => $vc, 'rooms' => $r, 'members' => $members, 'join' => $join, 'note' => mb_substr(trim((string) ($in['note'] ?? '')), 0, 500) ?: null];
    }

    /** Write one plan row (insert or update) and its members; the inquiry's own records follow in apply(). */
    private function write(int $id, ?int $planId, array $c, array $extraIn): int
    {
        $now = date('Y-m-d H:i:s');
        $was = $planId ? $this->row('SELECT * FROM tf_travel_plan WHERE id = ? AND intake_id = ?', [$planId, $id]) : null;
        if ($planId && !$was) {
            throw new \InvalidArgumentException('That travel plan is not on this inquiry.');
        }
        $wasMembers = $planId ? array_map('intval', array_column($this->rows('SELECT pax_id FROM tf_travel_member WHERE plan_id = ?', [$planId]), 'pax_id')) : [];
        sort($wasMembers);
        $nowMembers = $c['members'];
        sort($nowMembers);
        // the room allocation stays confirmed only if nothing it rests on moved
        $keepReview = $was && $was['rooms_reviewed_at'] && $wasMembers === $nowMembers && self::rooms($was) === $c['rooms'] && $was['pickup_date'] === $c['pickup_date'] && empty($extraIn['rooms_changed']);
        $vals = [$c['depart_date'], $c['flight'], $c['flight_time'], $c['airport'], $c['pickup_date'], $c['pickup_time'], $c['pickup_place'], $c['vehicle_class'],
            $c['rooms']['sgl'], $c['rooms']['dbl'], $c['rooms']['twn'], $c['rooms']['tpl'], json_encode($c['join']), $c['note']];
        if ($was) {
            $this->db->query('UPDATE tf_travel_plan SET depart_date = ?, flight = ?, flight_time = ?, airport = ?, pickup_date = ?, pickup_time = ?, pickup_place = ?, vehicle_class = ?,
                rooms_sgl = ?, rooms_dbl = ?, rooms_twn = ?, rooms_tpl = ?, join_json = ?, note = ?, updated_at = ?, updated_by = ?' . ($keepReview ? '' : ', rooms_reviewed_by = NULL, rooms_reviewed_at = NULL') . ' WHERE id = ?',
                array_merge($vals, [$now, $this->user, $planId]));
        } else {
            $this->db->query('INSERT INTO tf_travel_plan (intake_id, depart_date, flight, flight_time, airport, pickup_date, pickup_time, pickup_place, vehicle_class, rooms_sgl, rooms_dbl, rooms_twn, rooms_tpl, join_json, note, created_at, created_by, updated_at, updated_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', array_merge([$id], $vals, [$now, $this->user, $now, $this->user]));
            $planId = (int) $this->db->insertID();
        }
        $this->db->query('DELETE FROM tf_travel_member WHERE plan_id = ?', [$planId]);
        foreach ($c['members'] as $m) {
            $this->db->query('INSERT INTO tf_travel_member (plan_id, pax_id, intake_id) VALUES (?,?,?)', [$planId, $m, $id]);
        }
        if (!empty($extraIn['review_rooms'])) {
            $this->db->query('UPDATE tf_travel_plan SET rooms_reviewed_by = ?, rooms_reviewed_at = ? WHERE intake_id = ?', [$this->user, $now, $id]);
        }
        return $planId;
    }

    /** The continuing guests' rooms and the extra rooms they need after a departure (a guest left alone in a twin, say). */
    private function writeShared(int $id, array $in): bool
    {
        $st = $this->state($id);
        $changed = false;
        if (isset($in['cont_rooms']) && is_array($in['cont_rooms'])) {
            $r = [];
            foreach (['sgl', 'dbl', 'twn', 'tpl'] as $k) {
                $r[$k] = max(0, min(99, (int) ($in['cont_rooms'][$k] ?? 0)));
            }
            $changed = $changed || json_encode($r) !== (string) $st['cont_rooms_json'];
            $st['cont_rooms_json'] = json_encode($r);
        }
        if (array_key_exists('extra_rooms', $in)) {
            $r = null;
            if (is_array($in['extra_rooms'])) {
                $r = [];
                foreach (['sgl', 'dbl', 'twn', 'tpl'] as $k) {
                    $r[$k] = max(0, min(99, (int) ($in['extra_rooms'][$k] ?? 0)));
                }
                $r = array_sum($r) > 0 ? $r : null;
            }
            $enc = $r ? json_encode($r) : null;
            $changed = $changed || $enc !== $st['extra_rooms_json'];
            $st['extra_rooms_json'] = $enc;
        }
        $this->saveState($st);
        return $changed;
    }

    /** Save (insert or update) one plan and apply every plan of the inquiry again. */
    public function save(int $id, ?int $planId, array $in): array
    {
        $this->take->assertOpen($id);
        $c = $this->clean($id, $in, $planId);
        $this->db->transBegin();
        try {
            $roomsChanged = $this->writeShared($id, $in);
            $planId = $this->write($id, $planId, $c, ['review_rooms' => !empty($in['review_rooms']), 'rooms_changed' => $roomsChanged]);
            $sum = $this->apply($id);
            $this->take->activity()->record('intake', $id, $id, null, 'travel_plan_saved', 'Travel plan saved: ' . count($c['members']) . ' passenger(s) leave on ' . date('j M Y', strtotime($c['depart_date']))
                . ($c['flight'] ? ' (' . $c['flight'] . ($c['flight_time'] ? ' ' . $c['flight_time'] : '') . ')' : '') . '; pickup ' . date('j M', strtotime($c['pickup_date'])) . ($c['pickup_time'] ? ' ' . $c['pickup_time'] : '')
                . ($c['pickup_place'] ? ' at ' . $c['pickup_place'] : '') . '. ' . $sum, null);
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
        return ['plan_id' => $planId, 'data' => $this->data($id)];
    }

    /** Confirm the room allocation of every plan (the person has looked at it). */
    public function reviewRooms(int $id): array
    {
        $this->take->assertOpen($id);
        $this->db->query('UPDATE tf_travel_plan SET rooms_reviewed_by = ?, rooms_reviewed_at = ? WHERE intake_id = ?', [$this->user, date('Y-m-d H:i:s'), $id]);
        $this->take->activity()->record('intake', $id, $id, null, 'travel_rooms_ok', 'Travel plans: room allocation confirmed for the departing and the continuing guests.', null);
        return $this->data($id);
    }

    public function delete(int $id, int $planId): array
    {
        $this->take->assertOpen($id);
        $p = $this->row('SELECT * FROM tf_travel_plan WHERE id = ? AND intake_id = ?', [$planId, $id]);
        if (!$p) {
            throw new \InvalidArgumentException('That travel plan is not on this inquiry.');
        }
        $this->db->transBegin();
        try {
            $n = count($this->rows('SELECT pax_id FROM tf_travel_member WHERE plan_id = ?', [$planId]));
            $this->unapply($id);
            $this->db->query('DELETE FROM tf_travel_member WHERE plan_id = ?', [$planId]);
            $this->db->query('DELETE FROM tf_travel_plan WHERE id = ?', [$planId]);
            $sum = $this->apply($id);
            $this->take->activity()->record('intake', $id, $id, null, 'travel_plan_removed', 'Travel plan removed: the ' . $n . ' passenger(s) leaving ' . date('j M Y', strtotime((string) $p['depart_date'])) . ' stay with the tour again. ' . $sum, null);
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
        return $this->data($id);
    }

    /**
     * Preview: the plan applied in a trial that is rolled back. Before and after: guests and rooms per night, the service
     * quantities, the vehicles and the costing. Nothing is kept.
     */
    public function preview(int $id, ?int $planId, array $in, QuoteService $quotes, bool $seesCost = true): array
    {
        $this->take->assertOpen($id);
        $c = $this->clean($id, $in, $planId);
        $ids = array_map('intval', array_column($this->rows('SELECT id FROM tf_travel_plan WHERE intake_id = ?', [$id]), 'id'));
        $this->db->transBegin();
        try {
            // the inquiry as it stands, with the route and its lunches laid as of now — so "before" and "after" differ by the plan alone
            $this->take->planSectors($id);
            $before = $this->snapshot($id, $quotes);
            $roomsChanged = $this->writeShared($id, $in);
            $this->write($id, $planId, $c, ['review_rooms' => !empty($in['review_rooms']), 'rooms_changed' => $roomsChanged]);
            $this->apply($id);
            $after = $this->snapshot($id, $quotes);
        } finally {
            $this->db->transRollback();
        }
        // the trial must leave nothing behind; if the database ended it early, take the trial plan out again
        $left = array_diff(array_map('intval', array_column($this->rows('SELECT id FROM tf_travel_plan WHERE intake_id = ?', [$id]), 'id')), $ids);
        if ($left) {
            $this->db->query('DELETE FROM tf_travel_member WHERE plan_id IN (' . implode(',', $left) . ')');
            $this->db->query('DELETE FROM tf_travel_plan WHERE id IN (' . implode(',', $left) . ')');
            $this->apply($id);
            log_message('error', 'travel plan preview: the trial was committed early on inquiry ' . $id . '; the trial plan was removed again.');
        }
        $changes = $this->compare($before, $after);
        // tf-margin-r1: the tour's cost totals (and anything that adds up to them) are for a group holding Pricing only;
        // the figures each line carries stay, as on the rest of the inquiry
        if (!$seesCost) {
            $changes['cost_before'] = $changes['cost_after'] = null;
            foreach ($changes['parts'] as &$p) {
                $p['before'] = $p['after'] = null;
            }
            unset($p);
            $changes['by_group'] = [];
            $changes['cost_hidden'] = true;
        }
        return ['changes' => $changes];
    }

    /** What the preview compares: nights, services, vehicles and the costing. */
    private function snapshot(int $id, QuoteService $quotes): array
    {
        $d = $this->data($id);
        $cost = null;
        try {
            $cost = $quotes->costing($id);
        } catch (\Throwable $e) {
            $cost = null;
        }
        $hotels = [];
        $byGroup = [];
        if ($cost) {
            $groups = [];
            foreach (($cost['groups'] ?? []) as $g) {
                $groups[(int) $g['id']] = $g;
            }
            foreach (($cost['hotels'] ?? []) as $h) {
                $rate = [];
                foreach (($h['lines'] ?? []) as $l) {
                    $rate[$l['kind']] = (float) $l['rate'];
                }
                $hotels[] = ['day_no' => (int) $h['day_no'], 'date' => $h['date'], 'hotel' => $h['hotel'], 'mix' => $h['mix'] ?? null, 'amount_usd' => $h['amount_usd'] ?? null, 'state' => $h['state'] ?? null, 'why' => $h['why'] ?? null];
                // each group's own rooms that night at the night's rates (a twin is charged at the double rate)
                foreach ($groups as $gid => $g) {
                    $from = $g['day_from'] !== null ? (int) $g['day_from'] : 1;
                    $to = $g['day_to'] !== null ? (int) $g['day_to'] : PHP_INT_MAX;
                    if ((int) $h['day_no'] < $from || (int) $h['day_no'] > $to) {
                        continue;
                    }
                    $amt = 0.0;
                    $dr = $rate['dbl'] ?? $rate['twn'] ?? null;
                    $amt += (int) $g['sgl'] * ($rate['sgl'] ?? 0) + ((int) $g['dbl'] + (int) $g['twn']) * ($dr ?? 0) + (int) $g['tpl'] * ($rate['tpl'] ?? 0);
                    $cur = $h['currency'] ?? 'USD';
                    $usd = strtoupper((string) $cur) === 'USD' ? $amt : (($cost['usd_rate'] ?? 0) > 0 ? $amt / (float) $cost['usd_rate'] : 0);
                    $byGroup[$gid]['hotel'] = round(($byGroup[$gid]['hotel'] ?? 0) + $usd, 2);
                    $byGroup[$gid]['name'] = $g['name'];
                    $byGroup[$gid]['pax'] = (int) $g['pax'];
                }
            }
            foreach (($cost['transport'] ?? []) as $t) {
                if ($t['group_id'] !== null && isset($groups[(int) $t['group_id']])) {
                    $byGroup[(int) $t['group_id']]['transport'] = round(($byGroup[(int) $t['group_id']]['transport'] ?? 0) + (float) ($t['amount_usd'] ?? 0), 2);
                }
            }
        }
        $svc = [];
        foreach ($this->rows('SELECT id, day_no, title, kind, qty, qty_override, qty_reason, meal_id, qty_adult, qty_child FROM tf_intake_service WHERE intake_id = ? ORDER BY day_no, sort_order, id', [$id]) as $s) {
            $svc[(int) $s['id']] = $s;
        }
        $exc = [];
        foreach ($this->rows('SELECT id, day_no, name, qty, group_id FROM tf_intake_excursion WHERE intake_id = ?', [$id]) as $x) {
            $exc[(int) $x['id']] = $x;
        }
        return [
            'days' => $d['days'], 'room_plan' => $d['room_plan'], 'services' => $svc, 'excursions' => $exc,
            'vehicles' => array_map(static fn ($t) => ['label' => $t['label'], 'type' => $t['type'], 'group_id' => $t['group_id'], 'km' => $t['charged_km'], 'amount_usd' => $t['amount_usd'], 'state' => $t['state'], 'why' => $t['why']], $cost['transport'] ?? []),
            'crew' => array_map(static fn ($c) => ['role' => $c['role'], 'days' => $c['days'], 'amount_usd' => $c['amount_usd'] ?? null], $cost['crew'] ?? []),
            'hotels' => $hotels, 'by_group' => $byGroup,
            'svc_cost' => array_map(static fn ($l) => ['id' => $l['id'] ?? null, 'day_no' => $l['day_no'] ?? null, 'title' => $l['title'] ?? '', 'qty' => $l['qty'] ?? null, 'amount_usd' => $l['amount_usd'] ?? null], $cost['services'] ?? []),
            'cost' => $cost ? ['total_usd' => $cost['cost_usd'], 'parts' => $cost['parts'] ?? [], 'per_person_usd' => $cost['per_person_cost_usd'], 'pax' => $cost['pax'],
                'blockers' => array_map(static fn ($b) => $b['label'], $cost['blockers'] ?? []), 'ready' => (bool) ($cost['ready'] ?? false)] : null,
            'bookings' => $d['bookings'], 'open' => $d['open'],
        ];
    }

    private function compare(array $a, array $b): array
    {
        $nights = [];
        foreach ($b['days'] as $i => $d) {
            $o = $a['days'][$i] ?? null;
            if (!$d['night']) {
                continue;
            }
            $nights[] = ['day_no' => $d['day_no'], 'date' => $d['date'], 'hotel' => $d['hotel'], 'guests_before' => $o['night_guests'] ?? null, 'guests_after' => $d['night_guests'],
                'rooms_before' => self::roomsText($o['mix'] ?? []), 'rooms_after' => self::roomsText($d['mix'] ?? []), 'changed' => ($o['night_guests'] ?? null) !== $d['night_guests'] || ($o['mix'] ?? null) != $d['mix']];
        }
        $svc = [];
        foreach ($b['services'] as $sid => $s) {
            $o = $a['services'][$sid] ?? null;
            if (!$o) {
                continue;
            }
            $qo = $o['meal_id'] ? $o['qty_adult'] : ($o['qty_override'] ? $o['qty'] : 'party');
            $qn = $s['meal_id'] ? $s['qty_adult'] : ($s['qty_override'] ? $s['qty'] : 'party');
            if ((string) $qo !== (string) $qn) {
                $svc[] = ['day_no' => (int) $s['day_no'], 'title' => $s['title'], 'from' => $qo === 'party' ? 'whole party' : $qo, 'to' => $qn === 'party' ? 'whole party' : $qn, 'reason' => $s['qty_reason']];
            }
        }
        foreach ($b['excursions'] as $xid => $x) {
            $o = $a['excursions'][$xid] ?? null;
            if ($o && (string) $o['qty'] !== (string) $x['qty']) {
                $svc[] = ['day_no' => (int) $x['day_no'], 'title' => $x['name'], 'from' => $o['qty'], 'to' => $x['qty'], 'reason' => 'excursion'];
            }
        }
        $sa = [];
        foreach (($a['svc_cost'] ?? []) as $l) {
            $sa[(string) $l['id']] = $l;
        }
        $money = [];
        foreach (($b['svc_cost'] ?? []) as $l) {
            $o = $sa[(string) $l['id']] ?? null;
            unset($sa[(string) $l['id']]);
            if (!$o || round((float) $o['amount_usd'], 2) !== round((float) $l['amount_usd'], 2)) {
                $money[] = ['day_no' => $l['day_no'], 'title' => $l['title'], 'before' => $o['amount_usd'] ?? null, 'after' => $l['amount_usd'], 'new' => $o === null];
            }
        }
        foreach ($sa as $o) {
            $money[] = ['day_no' => $o['day_no'], 'title' => $o['title'], 'before' => $o['amount_usd'], 'after' => null, 'gone' => true];
        }
        $ca = $a['cost']['total_usd'] ?? null;
        $cb = $b['cost']['total_usd'] ?? null;
        $parts = [];
        foreach (($b['cost']['parts'] ?? []) as $k => $p) {
            $parts[] = ['label' => $p['label'] ?? $k, 'before' => $a['cost']['parts'][$k]['usd'] ?? ($a['cost']['parts'][$k]['total_usd'] ?? null), 'after' => $p['usd'] ?? ($p['total_usd'] ?? null)];
        }
        return ['nights' => $nights, 'services' => $svc, 'service_money' => $money, 'vehicles_before' => $a['vehicles'], 'vehicles_after' => $b['vehicles'], 'crew' => $b['crew'],
            'cost_before' => $ca, 'cost_after' => $cb, 'parts' => $parts, 'by_group' => $b['by_group'], 'blockers_after' => $b['cost']['blockers'] ?? [], 'bookings' => $b['bookings']];
    }

    // =====================================================================
    //  Applying the plans to the inquiry's own records
    // =====================================================================

    /** Put back every figure the plans changed (services, meals, excursions) and the passengers' groups. */
    private function unapply(int $id): void
    {
        foreach ($this->rows('SELECT * FROM tf_travel_adjust WHERE intake_id = ? ORDER BY id DESC', [$id]) as $a) {
            $o = json_decode((string) $a['old_json'], true) ?: [];
            if ($a['ref_type'] === 'service') {
                $this->db->query('UPDATE tf_intake_service SET qty = ?, qty_override = ?, qty_reason = ?, qty_set_by = ?, qty_set_at = ? WHERE id = ? AND intake_id = ?',
                    [$o['qty'] ?? null, (int) ($o['qty_override'] ?? 0), $o['qty_reason'] ?? null, $o['qty_set_by'] ?? null, $o['qty_set_at'] ?? null, (int) $a['ref_id'], $id]);
            } elseif ($a['ref_type'] === 'meal') {
                $this->db->query('UPDATE tf_intake_service SET qty_adult = ?, qty_child = ? WHERE id = ? AND intake_id = ?', [$o['qty_adult'] ?? null, $o['qty_child'] ?? null, (int) $a['ref_id'], $id]);
            } elseif ($a['ref_type'] === 'excursion') {
                $this->db->query('UPDATE tf_intake_excursion SET qty = ? WHERE id = ? AND intake_id = ?', [$o['qty'] ?? null, (int) $a['ref_id'], $id]);
            }
        }
        $this->db->query('DELETE FROM tf_travel_adjust WHERE intake_id = ?', [$id]);
    }

    private function remember(int $id, string $type, int $refId, array $old): void
    {
        $this->db->query('INSERT INTO tf_travel_adjust (intake_id, ref_type, ref_id, old_json) VALUES (?,?,?,?)', [$id, $type, $refId, json_encode($old)]);
    }

    /**
     * Apply every plan of the inquiry: the groups, the passengers' groups, the airport transfers and the service quantities.
     * Safe to run again: the figures it changed are put back first. Returns a one-line summary for the history.
     */
    private function apply(int $id): string
    {
        $this->unapply($id);
        $it = $this->row('SELECT * FROM tf_intake WHERE id = ?', [$id]);
        $plans = $this->plans($id);
        $st = $this->state($id);
        $arrival = $it['arrival_date'];
        $paxTotal = (int) $it['pax_total'];
        $groupIds = array_map('intval', array_column($this->rows('SELECT id FROM tf_intake_group WHERE intake_id = ?', [$id]), 'id'));
        $planGroups = array_values(array_filter(array_map(static fn ($p) => $p['group_id'] !== null ? (int) $p['group_id'] : null, $plans)));
        $nextSort = fn () => (int) ($this->row('SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM tf_intake_group WHERE intake_id = ?', [$id])['n'] ?? 0);
        $nextCode = function () use ($id) {
            $used = array_column($this->rows('SELECT code FROM tf_intake_group WHERE intake_id = ?', [$id]), 'code');
            for ($i = 0; $i < 26; $i++) {
                if (!in_array(chr(65 + $i), $used, true)) {
                    return chr(65 + $i);
                }
            }
            return 'Z';
        };

        // ---- no plans left: the inquiry goes back to one party --------------------------------------------------------
        if (!$plans) {
            $this->db->query('UPDATE tf_intake_pax SET group_id = ' . ($st['cont_group_id'] && !$st['cont_created'] ? (int) $st['cont_group_id'] : 'NULL') . ' WHERE intake_id = ? AND group_id IN (' . ($planGroups ? implode(',', $planGroups) : '0') . ')', [$id]);
            // groups made by plans that are gone
            $orphans = array_map('intval', array_column($this->rows('SELECT g.id FROM tf_intake_group g WHERE g.intake_id = ? AND g.name LIKE ? AND NOT EXISTS (SELECT 1 FROM tf_travel_plan p WHERE p.group_id = g.id)', [$id, 'Early departure%']), 'id'));
            foreach ($orphans as $g) {
                $this->db->query('UPDATE tf_intake_pax SET group_id = NULL WHERE group_id = ?', [$g]);
                $this->db->query('DELETE FROM tf_intake_group WHERE id = ?', [$g]);
            }
            $this->dropOrphanVehicles($id, []);
            if ($st['extra_group_id']) {
                $this->db->query('DELETE FROM tf_intake_group WHERE id = ? AND intake_id = ?', [(int) $st['extra_group_id'], $id]);
            }
            if ($st['cont_group_id']) {
                if ($st['cont_created']) {
                    $this->db->query('UPDATE tf_intake_pax SET group_id = NULL WHERE group_id = ?', [(int) $st['cont_group_id']]);
                    $this->db->query('DELETE FROM tf_intake_group WHERE id = ? AND intake_id = ?', [(int) $st['cont_group_id'], $id]);
                } elseif ($st['cont_base_pax'] !== null) {
                    $this->db->query('UPDATE tf_intake_group SET pax = ? WHERE id = ? AND intake_id = ?', [(int) $st['cont_base_pax'], (int) $st['cont_group_id'], $id]);
                }
            }
            $this->db->query('DELETE FROM tf_travel_state WHERE intake_id = ?', [$id]);
            $this->take->planSectors($id);
            return 'The whole party travels together again.';
        }

        // ---- the continuing group ------------------------------------------------------------------------------------
        $cont = $st['cont_group_id'] && in_array((int) $st['cont_group_id'], $groupIds, true) ? (int) $st['cont_group_id'] : null;
        if ($cont === null) {
            $own = $this->row('SELECT * FROM tf_intake_group WHERE intake_id = ? AND day_to IS NULL' . ($planGroups ? ' AND id NOT IN (' . implode(',', $planGroups) . ')' : '') . ' ORDER BY sort_order, id LIMIT 1', [$id]);
            if ($own) {
                $cont = (int) $own['id'];
                $st['cont_created'] = 0;
                $st['cont_base_pax'] = (int) $own['pax'];
                if (!$st['cont_rooms_json']) {
                    $st['cont_rooms_json'] = json_encode(self::rooms($own));
                }
            } else {
                $others = (int) ($this->row('SELECT COALESCE(SUM(pax),0) n FROM tf_intake_group WHERE intake_id = ?', [$id])['n'] ?? 0);
                $r = json_decode((string) $st['cont_rooms_json'], true) ?: self::rooms($it);
                $this->db->query('INSERT INTO tf_intake_group (intake_id, code, name, pax, day_from, day_to, rooms_sgl, rooms_dbl, rooms_twn, rooms_tpl, note, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$id, $nextCode(), 'Continuing guests', max(0, $paxTotal - $others), null, null, $r['sgl'], $r['dbl'], $r['twn'], $r['tpl'], 'Made by the travel plans: the guests who stay to the end of the tour.', $nextSort()]);
                $cont = (int) $this->db->insertID();
                $st['cont_created'] = 1;
                $st['cont_base_pax'] = max(0, $paxTotal - $others);
            }
            $st['cont_group_id'] = $cont;
        }
        $leaving = 0;
        $made = [];
        foreach ($plans as $i => $p) {
            $n = self::nightsOf($p, $arrival);
            $last = max(1, (int) $n['last_night']);
            $name = 'Early departure — ' . date('j M', strtotime((string) $p['depart_date']));
            $gid = $p['group_id'] !== null && in_array((int) $p['group_id'], $groupIds, true) ? (int) $p['group_id'] : null;
            if ($gid === null) {
                $this->db->query('INSERT INTO tf_intake_group (intake_id, code, name, pax, day_from, day_to, rooms_sgl, rooms_dbl, rooms_twn, rooms_tpl, note, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$id, $nextCode(), $name, count($p['members']), null, $last, (int) $p['rooms_sgl'], (int) $p['rooms_dbl'], (int) $p['rooms_twn'], (int) $p['rooms_tpl'], 'Made by travel plan ' . ($i + 1) . '.', $nextSort()]);
                $gid = (int) $this->db->insertID();
                $this->db->query('UPDATE tf_travel_plan SET group_id = ? WHERE id = ?', [$gid, (int) $p['id']]);
                $groupIds[] = $gid;
            } else {
                $this->db->query('UPDATE tf_intake_group SET name = ?, pax = ?, day_from = NULL, day_to = ?, rooms_sgl = ?, rooms_dbl = ?, rooms_twn = ?, rooms_tpl = ? WHERE id = ?',
                    [$name, count($p['members']), $last, (int) $p['rooms_sgl'], (int) $p['rooms_dbl'], (int) $p['rooms_twn'], (int) $p['rooms_tpl'], $gid]);
            }
            $plans[$i]['group_id'] = $gid;
            $made[] = $gid;
            $leaving += count($p['members']);
            // the passengers: theirs is the plan's group; anyone the plan no longer names goes back to the continuing party
            $this->db->query('UPDATE tf_intake_pax SET group_id = ? WHERE intake_id = ? AND group_id = ?', [$st['cont_created'] ? null : $cont, $id, $gid]);
            $this->db->query('UPDATE tf_intake_pax SET group_id = ? WHERE intake_id = ? AND id IN (' . implode(',', array_map('intval', $p['members'])) . ')', [$gid, $id]);
        }
        // groups of plans removed since: back to the continuing party
        foreach ($this->rows('SELECT g.id FROM tf_intake_group g WHERE g.intake_id = ? AND g.name LIKE ? AND NOT EXISTS (SELECT 1 FROM tf_travel_plan p WHERE p.group_id = g.id)', [$id, 'Early departure%']) as $o) {
            $this->db->query('UPDATE tf_intake_pax SET group_id = NULL WHERE group_id = ?', [(int) $o['id']]);
            $this->db->query('DELETE FROM tf_intake_group WHERE id = ?', [(int) $o['id']]);
        }
        $r = json_decode((string) $st['cont_rooms_json'], true);
        $this->db->query('UPDATE tf_intake_group SET pax = ?' . ($r ? ', rooms_sgl = ?, rooms_dbl = ?, rooms_twn = ?, rooms_tpl = ?' : '') . ' WHERE id = ?',
            $r ? [max(0, (int) $st['cont_base_pax'] - $leaving), $r['sgl'], $r['dbl'], $r['twn'], $r['tpl'], $cont] : [max(0, (int) $st['cont_base_pax'] - $leaving), $cont]);
        // extra rooms for the continuing guests from the first departure on (a guest left alone in a twin, say)
        $firstLeave = min(array_map(fn ($p) => (int) self::nightsOf($p, $arrival)['last_night'], $plans)) + 1;
        $x = json_decode((string) $st['extra_rooms_json'], true);
        if ($x) {
            if ($st['extra_group_id'] && in_array((int) $st['extra_group_id'], $groupIds, true)) {
                $this->db->query('UPDATE tf_intake_group SET day_from = ?, day_to = NULL, pax = 0, rooms_sgl = ?, rooms_dbl = ?, rooms_twn = ?, rooms_tpl = ? WHERE id = ?', [$firstLeave, $x['sgl'], $x['dbl'], $x['twn'], $x['tpl'], (int) $st['extra_group_id']]);
            } else {
                $this->db->query('INSERT INTO tf_intake_group (intake_id, code, name, pax, day_from, day_to, rooms_sgl, rooms_dbl, rooms_twn, rooms_tpl, note, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$id, $nextCode(), 'Room change after departure', 0, $firstLeave, null, $x['sgl'], $x['dbl'], $x['twn'], $x['tpl'], 'Extra rooms for the continuing guests (travel plans) — no passengers of its own.', $nextSort()]);
                $st['extra_group_id'] = (int) $this->db->insertID();
            }
        } elseif ($st['extra_group_id']) {
            $this->db->query('DELETE FROM tf_intake_group WHERE id = ? AND intake_id = ?', [(int) $st['extra_group_id'], $id]);
            $st['extra_group_id'] = null;
        }
        $this->saveState($st);

        // ---- the airport transfer of each plan: its own vehicle line for its group --------------------------------------
        if (!$this->rows('SELECT id FROM tf_intake_vehicle WHERE intake_id = ? LIMIT 1', [$id])) {
            $this->take->planVehicle($id, true);   // the main vehicle first, so the transfer is beside it and not instead of it
        }
        $keep = [];
        foreach ($plans as $i => $p) {
            $n = self::nightsOf($p, $arrival);
            $last = max(1, (int) $n['last_night']);
            $label = 'Early departure transfer ' . date('j M', strtotime((string) $p['depart_date']));
            $why = 'Travel plan ' . ($i + 1) . ': ' . count($p['members']) . ' passenger(s) collected ' . date('j M', strtotime((string) ($p['pickup_date'] ?: $p['depart_date']))) . ($p['pickup_time'] ? ' ' . $p['pickup_time'] : '') . ($p['pickup_place'] ? ' at ' . $p['pickup_place'] : '') . ' for ' . ($p['flight'] ?: 'their flight') . '.';
            $line = $p['vehicle_line_id'] ? $this->row('SELECT id, class_code FROM tf_intake_vehicle WHERE id = ? AND intake_id = ?', [(int) $p['vehicle_line_id'], $id]) : null;
            if ($line && $line['class_code'] !== $p['vehicle_class']) {
                $this->dropVehicle($id, (int) $line['id']);
                $line = null;
            }
            if ($line) {
                $this->db->query('UPDATE tf_intake_vehicle SET group_id = ?, day_from = ?, day_to = ?, label = ?, why = ? WHERE id = ?', [$p['group_id'], $last, $last, mb_substr($label, 0, 40), mb_substr($why, 0, 255), (int) $line['id']]);
                $lid = (int) $line['id'];
            } else {
                $lid = $this->take->addVehicleLine($id, (string) $p['vehicle_class'], 1, (int) $p['group_id'], $last, $last, mb_substr($label, 0, 40), $why);
                $this->db->query('UPDATE tf_travel_plan SET vehicle_line_id = ? WHERE id = ?', [$lid, (int) $p['id']]);
            }
            $keep[] = $lid;
        }
        $this->dropOrphanVehicles($id, $keep);
        $this->take->planSectors($id);

        // ---- the services from each departure day on ---------------------------------------------------------------------
        $party = (int) ($it['pax_adults'] ?: $paxTotal);
        $absent = static function (int $dayNo, int $serviceId) use ($plans, $arrival): int {
            $n = 0;
            foreach ($plans as $p) {
                $lv = self::nightsOf($p, $arrival)['leaving_day'];
                if ($lv === null) {
                    continue;
                }
                if ($dayNo > $lv || ($dayNo === $lv && !in_array($serviceId, $p['join'], true))) {
                    $n += count($p['members']);
                }
            }
            return $n;
        };
        $changed = 0;
        $review = 0;
        foreach ($this->rows('SELECT * FROM tf_intake_service WHERE intake_id = ? AND day_no > 0', [$id]) as $s) {
            $gone = $absent((int) $s['day_no'], (int) $s['id']);
            if ($gone <= 0) {
                continue;
            }
            $now = max(0, $paxTotal - $gone);
            if (!empty($s['meal_id'])) {
                if ($s['qty_adult'] !== null && (int) $s['qty_adult'] === $party) {
                    $this->remember($id, 'meal', (int) $s['id'], ['qty_adult' => $s['qty_adult'], 'qty_child' => $s['qty_child']]);
                    $this->db->query('UPDATE tf_intake_service SET qty_adult = ? WHERE id = ?', [max(0, $party - $gone), (int) $s['id']]);
                    $changed++;
                } else {
                    $review++;
                }
                continue;
            }
            if (ServiceLookup::followsParty($s)) {
                if (!empty($s['qty_override']) && strpos((string) $s['qty_reason'], self::REASON) !== 0) {
                    $review++;
                    continue;
                }
                $this->remember($id, 'service', (int) $s['id'], ['qty' => $s['qty'], 'qty_override' => $s['qty_override'], 'qty_reason' => $s['qty_reason'], 'qty_set_by' => $s['qty_set_by'], 'qty_set_at' => $s['qty_set_at']]);
                $this->db->query('UPDATE tf_intake_service SET qty = ?, qty_override = 1, qty_reason = ?, qty_set_by = ?, qty_set_at = ? WHERE id = ?',
                    [$now, self::REASON . ': ' . $now . ' of ' . $paxTotal . ' on day ' . (int) $s['day_no'] . ' (' . $gone . ' left early)', $this->user, date('Y-m-d H:i:s'), (int) $s['id']]);
                $changed++;
            } elseif ($s['qty'] !== null && (int) $s['qty'] === $paxTotal) {
                $this->remember($id, 'service', (int) $s['id'], ['qty' => $s['qty'], 'qty_override' => $s['qty_override'], 'qty_reason' => $s['qty_reason'], 'qty_set_by' => $s['qty_set_by'], 'qty_set_at' => $s['qty_set_at']]);
                $this->db->query('UPDATE tf_intake_service SET qty = ? WHERE id = ?', [$now, (int) $s['id']]);
                $changed++;
            } else {
                $review++;
            }
        }
        foreach ($this->rows('SELECT * FROM tf_intake_excursion WHERE intake_id = ? AND day_no > 0 AND group_id IS NULL', [$id]) as $x) {
            $gone = $absent((int) $x['day_no'], 0);
            if ($gone <= 0) {
                continue;
            }
            if ($x['qty'] !== null && (int) round((float) $x['qty']) === $paxTotal) {
                $this->remember($id, 'excursion', (int) $x['id'], ['qty' => $x['qty']]);
                $this->db->query('UPDATE tf_intake_excursion SET qty = ? WHERE id = ?', [max(0, $paxTotal - $gone), (int) $x['id']]);
                $changed++;
            } else {
                $review++;
            }
        }
        $this->db->query('UPDATE tf_intake SET updated_at = ?, updated_by = ? WHERE id = ?', [date('Y-m-d H:i:s'), $this->user, $id]);
        return $leaving . ' leave early, ' . max(0, $paxTotal - $leaving) . ' continue; ' . count($plans) . ' airport transfer(s); ' . $changed . ' service quantit' . ($changed === 1 ? 'y' : 'ies') . ' follow the people present'
            . ($review ? ', ' . $review . ' set by hand left for review' : '') . '.';
    }

    private function dropVehicle(int $id, int $lineId): void
    {
        $v = $this->row('SELECT sort_order FROM tf_intake_vehicle WHERE id = ? AND intake_id = ?', [$lineId, $id]);
        if (!$v) {
            return;
        }
        $this->db->query('DELETE FROM tf_intake_sector WHERE intake_id = ? AND vehicle_seq = ?', [$id, (int) $v['sort_order']]);
        $this->db->query('DELETE FROM tf_intake_vehicle WHERE id = ?', [$lineId]);
    }

    /** Transfer lines made by plans that are gone (or replaced). */
    private function dropOrphanVehicles(int $id, array $keep): void
    {
        foreach ($this->rows("SELECT id FROM tf_intake_vehicle WHERE intake_id = ? AND (label LIKE 'Early departure transfer%' OR label LIKE 'Airport transfer — early departure%')", [$id]) as $v) {
            if (!in_array((int) $v['id'], $keep, true)) {
                $this->dropVehicle($id, (int) $v['id']);
            }
        }
    }
}
