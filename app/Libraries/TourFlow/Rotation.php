<?php

namespace App\Libraries\TourFlow;

/**
 * tf-rotation-r1 / r1b (25–26 Oct 2026) — fair driver rotation for the Transport Desk.
 *
 * Built on the existing TourFlow records, nothing duplicated: the tours (tf_tour), the transport cards a confirmation writes
 * (tf_transport_job, one per vehicle), the driver / guide lines on each card (tf_transport_crew) and the Drivers & Guides
 * master (tf_crew_person, tf_crew_vehicle). The rotation keeps five small tables of its own and changes existing rows only
 * through the desk's own calls — TourService::engageCrew (the driver) and TourService::allocateVehicle (his actual vehicle),
 * the same two calls staff make by hand, so the card's own desk step moves to "Allocated" by the desk's own rule.
 *
 *   tf_rot_driver       rotation details per driver: in the pool, the general opt-in to 1–2 pax tours at the car rate, the
 *                       agreed car rate and van rate per km, notes
 *   tf_rot_unavailable  "Not Available": a period, the reason, who recorded it
 *   tf_rot_review       a card reviewed by staff and sent on to Pending Allocations ("Driver Allocation" button)
 *   tf_rot_allocation   the acceptance record of each allocation: required class, the actual vehicle and registration, the
 *                       payment basis (rate, source, route + local km, vehicles, amount) and — for a van at the car rate —
 *                       the driver's acceptance for THIS tour
 *   tf_rot_log          every review, acceptance, car-rate acceptance, Not Available and Bypass — the report
 *
 * The rules (Shantha, 25–26 Oct 2026):
 *   - the vehicle class from the booking's guests on the ladder (tf_vehicle_class), with two fixed limits the ladder cannot
 *     loosen: a van never carries more than 7 guests, and from 8 guests a mini coach or larger AND a separate guide;
 *   - each vehicle's RECORDED guest capacity (max_pax_luggage) is respected; a vehicle with none recorded is not used;
 *   - the quotation's driver line decides the role, never substituted: a JSD line takes a JSD, a JSCG line a JSCG; any other
 *     driver line (e.g. ESD, English-speaking) is outside automatic allocation and is done by hand; only Japanese-speaking
 *     drivers (JSD / JSCG, category confirmed) are ever allocated automatically; guides stay manual;
 *   - a van driver takes a 1–2 guest tour only when opted in, at his agreed car rate, accepted by him for that tour;
 *   - five full calendar days at home after a tour (ends 1 Nov → rest 2–6 Nov → free from 7 Nov), checked before and after
 *     the proposed tour against every engagement — on the Transport Desk AND on the earlier Pending Driver Allocation page;
 *   - the one waiting longest since his last completed tour; nobody suitable → the tour stays pending with the reasons;
 *   - Bypass Rotation changes only whose turn it is and pool membership — nothing else.
 */
final class Rotation
{
    public const REST_DAYS = 5;          // full calendar days at home after a tour
    public const MAX_VAN_GUESTS = 7;     // a van never carries more, whatever the ladder or the record says
    public const GUIDE_FROM_GUESTS = 8;  // from this many guests: mini coach or larger + a separate guide

    public const WARNING_BYPASS = 'Bypassing the rotation gives this tour to a driver out of turn. Doing so without a sound operational reason may be seen as favouritism towards that driver. Your name, the reason you give and the driver passed over are recorded and reported to management.';

    /** the ladder's class → the vehicle type(s) a driver's record must hold; null roof = any */
    private const CLASS_VEHICLE = [
        'SEDAN'     => [['car', null]],
        'VAN'       => [['van', null]],
        'HIROOF'    => [['van', 'high']],
        'MINICOACH' => [['minibus', null]],
        'COACH33'   => [['bus', null], ['minibus', null]],
        'BUS45'     => [['bus', null]],
        'BUS55'     => [['bus', null]],
    ];
    private const VAN_CLASSES = ['VAN', 'HIROOF'];
    private const AUTO_LINES = ['JSD', 'JSCG'];   // the driver lines the rotation fills; every other line is manual

    /** the only reasons Bypass Rotation may set aside */
    private const BYPASSABLE = ['not in the rotation pool'];

    private $db;
    private string $user;
    private ?bool $installed = null;
    private array $cols = [];
    private array $tables = [];

    public function __construct($db, string $user)
    {
        $this->db   = $db;
        $this->user = $user;
    }

    // ------------------------------------------------------------------ install

    public function installed(): bool
    {
        if ($this->installed === null) {
            $this->installed = $this->hasTable('tf_rot_allocation');
        }
        return $this->installed;
    }

    /** Creates the rotation's own tables when missing (each CREATE IF NOT EXISTS). Nothing else is touched. */
    public function ensure(): void
    {
        if ($this->installed()) {
            return;
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS tf_rot_driver (
            person_id INT NOT NULL PRIMARY KEY, in_pool TINYINT(1) NOT NULL DEFAULT 0, car_rate_optin TINYINT(1) NOT NULL DEFAULT 0,
            agreed_car_rate_km DECIMAL(10,2) NULL, agreed_van_rate_km DECIMAL(10,2) NULL, rate_note VARCHAR(400) NULL, notes VARCHAR(400) NULL,
            updated_at DATETIME NULL, updated_by VARCHAR(100) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS tf_rot_unavailable (
            id INT AUTO_INCREMENT PRIMARY KEY, person_id INT NOT NULL, date_from DATE NOT NULL, date_to DATE NOT NULL, reason VARCHAR(400) NOT NULL,
            tour_id INT NULL, job_id INT NULL, recorded_at DATETIME NOT NULL, recorded_by VARCHAR(100) NOT NULL, cleared_at DATETIME NULL, cleared_by VARCHAR(100) NULL,
            KEY (person_id, date_from, date_to)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS tf_rot_review (
            job_id INT NOT NULL PRIMARY KEY, tour_id INT NOT NULL, reviewed_at DATETIME NOT NULL, reviewed_by VARCHAR(100) NOT NULL, note VARCHAR(400) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS tf_rot_allocation (
            id INT AUTO_INCREMENT PRIMARY KEY, job_id INT NOT NULL, tour_id INT NOT NULL, crew_id INT NOT NULL, person_id INT NOT NULL, person_name VARCHAR(190) NULL,
            line_category VARCHAR(10) NULL, guests SMALLINT NULL, required_class VARCHAR(20) NULL, required_class_name VARCHAR(80) NULL, quoted_class VARCHAR(20) NULL,
            vehicle_id INT NULL, vehicle_reg VARCHAR(60) NULL, vehicle_type VARCHAR(10) NULL, vehicle_roof VARCHAR(10) NULL, vehicle_guests SMALLINT NULL,
            car_rate_tour TINYINT(1) NOT NULL DEFAULT 0, pay_basis VARCHAR(20) NULL, pay_rate_km DECIMAL(10,2) NULL, pay_rate_source VARCHAR(60) NULL,
            km_route DECIMAL(10,2) NULL, km_local DECIMAL(10,2) NULL, km_payable DECIMAL(10,2) NULL, vehicles SMALLINT NULL, pay_amount DECIMAL(12,2) NULL, pay_currency CHAR(3) NULL,
            driver_accepted TINYINT(1) NOT NULL DEFAULT 0, accepted_text VARCHAR(600) NULL, how VARCHAR(10) NOT NULL, reason VARCHAR(500) NULL,
            allocated_at DATETIME NOT NULL, allocated_by VARCHAR(100) NOT NULL, KEY (job_id), KEY (person_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS tf_rot_log (
            id INT AUTO_INCREMENT PRIMARY KEY, job_id INT NULL, tour_id INT NULL, tour_no VARCHAR(20) NULL, crew_id INT NULL, person_id INT NULL, person_name VARCHAR(190) NULL,
            event VARCHAR(30) NOT NULL, reason VARCHAR(500) NULL, details TEXT NULL, at DATETIME NOT NULL, by_user VARCHAR(100) NOT NULL,
            KEY (event, at), KEY (tour_id), KEY (person_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->installed = null;
        $this->tables = [];
        $this->cols = [];
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function hasTable(string $t): bool
    {
        return $this->tables[$t] ??= (bool) $this->db->query("SHOW TABLES LIKE '" . $t . "'")->getResultArray();
    }

    private function hasCol(string $t, string $c): bool
    {
        return $this->cols[$t . '.' . $c] ??= $this->hasTable($t) && (bool) $this->db->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'")->getResultArray();
    }

    private function addDays(string $date, int $days): string
    {
        return date('Y-m-d', strtotime($date . ' +' . $days . ' days'));
    }

    // ------------------------------------------------------------------ vehicles and capacity

    /** The vehicle class the booking needs: the ladder, except that a van class is never chosen above 7 guests. */
    public function neededClass(int $pax, string $cardClass): array
    {
        $ladder = $this->db->query('SELECT code, name, max_pax, seats FROM tf_vehicle_class WHERE active = 1 ORDER BY max_pax, sort_order')->getResultArray();
        $need = null;
        foreach ($ladder as $cl) {
            if ($pax > self::MAX_VAN_GUESTS && in_array($cl['code'], self::VAN_CLASSES, true)) {
                continue;   // fixed limit: no van above 7 guests, whatever the ladder says
            }
            if ($pax >= self::GUIDE_FROM_GUESTS && $cl['code'] === 'SEDAN') {
                continue;
            }
            if ($pax <= (int) $cl['max_pax']) {
                $need = $cl;
                break;
            }
        }
        $card = null;
        foreach ($ladder as $cl) {
            if ($cl['code'] === $cardClass) {
                $card = $cl;
            }
        }
        return ['code' => $need['code'] ?? null, 'name' => $need['name'] ?? null, 'max_pax' => $need ? min((int) $need['max_pax'], in_array($need['code'], self::VAN_CLASSES, true) ? self::MAX_VAN_GUESTS : 999) : null,
            'card_code' => $cardClass, 'card_name' => $card['name'] ?? $cardClass, 'agrees' => $need !== null && $need['code'] === $cardClass,
            'car_rate_tour' => ($need['code'] ?? null) === 'SEDAN', 'guide_required' => $pax >= self::GUIDE_FROM_GUESTS];
    }

    /** The guests a vehicle may carry: its recorded guest capacity, never above 7 for a van; null when nothing is recorded. */
    public static function guestCapacity(array $v): ?int
    {
        $rec = (int) ($v['max_pax_luggage'] ?? 0);
        if ($rec <= 0) {
            return null;
        }
        return ($v['vehicle_type'] ?? null) === 'van' ? min($rec, self::MAX_VAN_GUESTS) : $rec;
    }

    // ------------------------------------------------------------------ the card as the rotation sees it

    /** One card with its tour, its driver and guide lines, and the facts that decide whether the rotation may fill it. */
    private function card(int $jobId): array
    {
        $j = $this->db->query('SELECT j.*, t.tour_no, t.arrival_date, t.departure_date, t.pax_total, t.agent_name, t.tour_name' . ($this->hasCol('tf_tour', 'legacy_tour_id') ? ', t.legacy_tour_id' : ', NULL AS legacy_tour_id')
            . ' FROM tf_transport_job j JOIN tf_tour t ON t.id = j.tour_id WHERE j.id = ?', [$jobId])->getRowArray();
        if (!$j) {
            throw new \RuntimeException('Transport card not found.');
        }
        $lines = $this->db->query("SELECT id, kind, role_code, status, person_name FROM tf_transport_crew WHERE job_id = ? AND status <> 'replaced' ORDER BY id", [$jobId])->getResultArray();
        $driverLine = null;
        $guideLines = [];
        foreach ($lines as $l) {
            if ($l['kind'] === 'driver') {
                $driverLine = $l;   // the latest driver line
            } elseif ($l['kind'] === 'guide') {
                $guideLines[] = $l;
            }
        }
        $j['driver_line'] = $driverLine;
        $j['guide_lines'] = $guideLines;
        $j['line_category'] = $driverLine ? CrewPeople::lineCategory((string) $driverLine['role_code']) : null;
        $j['line_code'] = $driverLine['role_code'] ?? null;
        return $j;
    }

    /**
     * What stops the rotation filling a card at all, whoever the driver: no driver line; a driver line that is not a JSD / JSCG
     * line; 8+ guests with no guide line on the card; the same tour already allocated on the earlier page.
     */
    private function cardBlocks(array $j): array
    {
        $b = [];
        if (!$j['driver_line']) {
            $b[] = 'This card has no driver line — add it on the card (Transport Operations) first.';
        } elseif (!in_array($j['line_category'], self::AUTO_LINES, true)) {
            $b[] = 'The quotation\'s driver line is ' . ($j['line_code'] ?: '?') . ' — automatic allocation covers only Japanese-speaking driver lines (JSD, JSCG). Allocate this line by hand on the card; no category is substituted.';
        }
        if ((int) $j['pax_total'] >= self::GUIDE_FROM_GUESTS && !$j['guide_lines']) {
            $b[] = (int) $j['pax_total'] . ' guests need a separate guide, and this card has no guide line. Add the guide line on the card first; the driver is not allocated until then.';
        }
        $legacy = $this->legacyAllocationFor($j);
        if ($legacy) {
            $b[] = 'This tour is already allocated on the earlier Pending Driver Allocation page (' . $legacy['driver_name'] . ', ' . $legacy['manual_id'] . '). It is not allocated twice.';
        }
        return $b;
    }

    // ------------------------------------------------------------------ the registry section

    /** The rotation details of every Japanese-speaking driver on the master (JSD / JSCG), with what is still missing flagged. */
    public function registry(CrewPeople $people): array
    {
        $ext = [];
        $unav = [];
        if ($this->installed()) {
            foreach ($this->db->query('SELECT * FROM tf_rot_driver')->getResultArray() as $r) {
                $ext[(int) $r['person_id']] = $r;
            }
            foreach ($this->db->query('SELECT * FROM tf_rot_unavailable WHERE cleared_at IS NULL AND date_to >= ? ORDER BY date_from', [date('Y-m-d')])->getResultArray() as $u) {
                $unav[(int) $u['person_id']][] = $u;
            }
        }
        $out = [];
        foreach ($people->all(null, false) as $p) {
            if (!in_array((string) ($p['category'] ?? ''), self::AUTO_LINES, true) || !empty($p['archived_at'])) {
                continue;
            }
            $e = $ext[(int) $p['id']] ?? [];
            $vehicles = $people->vehicles((int) $p['id']);
            $last = $this->lastCompleted((int) $p['id'], (string) ($p['supplier_acc'] ?? ''));
            // two separate questions: can he be matched to a tour (vehicle details), and can an allocation be paid (payment details)
            $vehMissing = [];
            if (!$vehicles) {
                $vehMissing[] = 'no vehicle on the master';
            }
            foreach ($vehicles as $v) {
                $reg = $v['reg_no'] ?: 'vehicle';
                if (empty($v['vehicle_type'])) {
                    $vehMissing[] = 'vehicle type (' . $reg . ')';
                }
                if ($v['vehicle_type'] === 'van' && empty($v['roof_type'])) {
                    $vehMissing[] = 'roof type (' . $reg . ')';
                }
                if ((int) ($v['max_pax_luggage'] ?? 0) <= 0) {
                    $vehMissing[] = 'guest capacity (' . $reg . ')';
                }
            }
            if (isset($p['category_confirmed']) && (int) $p['category_confirmed'] === 0) {
                $vehMissing[] = 'category not confirmed';
            }
            $hasVan = (bool) array_filter($vehicles, static fn ($v) => $v['vehicle_type'] === 'van');
            $master = $p['rate_effective']['rate_km'] ?? null;
            $payMissing = [];
            $payWarn = [];
            if ($master === null || $master === '') {
                $payMissing[] = 'rate per km on the Drivers & Guides master (needed to pay any tour)';
            }
            if (!empty($e['car_rate_optin']) && ($e['agreed_car_rate_km'] === null || $e['agreed_car_rate_km'] === '')) {
                $payMissing[] = 'agreed car rate (he has opted in to car-rate tours)';
            }
            if ($hasVan && !empty($e['agreed_van_rate_km']) && $master !== null && $master !== '' && abs((float) $e['agreed_van_rate_km'] - (float) $master) > 0.009) {
                $payWarn[] = 'agreed van rate LKR ' . number_format((float) $e['agreed_van_rate_km'], 2) . ' differs from the master rate LKR ' . number_format((float) $master, 2) . ' that a normal tour is paid at — correct one of them';
            }
            $out[] = [
                'id' => (int) $p['id'], 'name' => $p['name'], 'nickname' => $p['nickname'] ?? null, 'category' => $p['category'], 'status' => $p['status'] ?? 'active',
                'category_confirmed' => (int) ($p['category_confirmed'] ?? 1), 'languages' => $p['languages'] ?? null, 'mobile' => ($p['mobile'] ?? null) ?: ($p['phone'] ?? null),
                'vehicles' => array_map(static fn ($v) => ['id' => $v['id'], 'reg_no' => $v['reg_no'], 'type' => $v['vehicle_type'], 'type_name' => $v['type_name'] ?? $v['vehicle_type'], 'roof' => $v['roof_type'], 'seats' => $v['seats'], 'guests' => $v['max_pax_luggage'], 'capacity' => self::guestCapacity($v), 'current' => (int) $v['is_current']], $vehicles),
                'has_van' => $hasVan, 'has_car' => (bool) array_filter($vehicles, static fn ($v) => $v['vehicle_type'] === 'car'),
                'in_pool' => (int) ($e['in_pool'] ?? 0), 'car_rate_optin' => (int) ($e['car_rate_optin'] ?? 0),
                'agreed_car_rate_km' => $e['agreed_car_rate_km'] ?? null, 'agreed_van_rate_km' => $e['agreed_van_rate_km'] ?? null, 'rate_note' => $e['rate_note'] ?? null, 'notes' => $e['notes'] ?? null,
                'master_rate_km' => $master, 'master_rate_source' => $p['rate_effective']['source'] ?? null,
                'last_tour_end' => $last['end'] ?? null, 'last_tour_no' => $last['tour_no'] ?? null, 'free_from' => $last ? $this->addDays($last['end'], self::REST_DAYS + 1) : null,
                'unavailable' => $unav[(int) $p['id']] ?? [],
                'vehicle_missing' => $vehMissing, 'payment_missing' => $payMissing, 'payment_warnings' => $payWarn, 'entered' => array_key_exists('in_pool', $e),
                'vehicle_ready' => !$vehMissing, 'payment_ready' => !$payMissing,
                'missing' => array_merge($vehMissing, $payMissing),   // kept for older screens
                'updated_at' => $e['updated_at'] ?? null, 'updated_by' => $e['updated_by'] ?? null,
            ];
        }
        return $out;
    }

    public function saveDriver(int $personId, array $d): void
    {
        $this->ensure();
        $num = static function ($v) {
            $v = trim((string) $v);
            if ($v === '') {
                return null;
            }
            if (!is_numeric($v) || (float) $v < 0) {
                throw new \InvalidArgumentException('A rate is a number of rupees per kilometre.');
            }
            return round((float) $v, 2);
        };
        $row = ['in_pool' => !empty($d['in_pool']) ? 1 : 0, 'car_rate_optin' => !empty($d['car_rate_optin']) ? 1 : 0,
            'agreed_car_rate_km' => $num($d['agreed_car_rate_km'] ?? null), 'agreed_van_rate_km' => $num($d['agreed_van_rate_km'] ?? null),
            'rate_note' => mb_substr(trim((string) ($d['rate_note'] ?? '')), 0, 400) ?: null, 'notes' => mb_substr(trim((string) ($d['notes'] ?? '')), 0, 400) ?: null,
            'updated_at' => $this->now(), 'updated_by' => $this->user];
        $was = $this->db->query('SELECT * FROM tf_rot_driver WHERE person_id = ?', [$personId])->getRowArray();
        if ($was) {
            $this->db->query('UPDATE tf_rot_driver SET in_pool = ?, car_rate_optin = ?, agreed_car_rate_km = ?, agreed_van_rate_km = ?, rate_note = ?, notes = ?, updated_at = ?, updated_by = ? WHERE person_id = ?',
                array_merge(array_values($row), [$personId]));
        } else {
            $this->db->query('INSERT INTO tf_rot_driver (person_id, in_pool, car_rate_optin, agreed_car_rate_km, agreed_van_rate_km, rate_note, notes, updated_at, updated_by) VALUES (?,?,?,?,?,?,?,?,?)',
                array_merge([$personId], array_values($row)));
        }
        $this->log(['person_id' => $personId, 'event' => 'registry', 'reason' => 'Rotation details saved', 'details' => ['was' => $was, 'now' => $row]]);
    }

    public function addUnavailable(int $personId, string $from, string $to, string $reason, ?int $tourId = null, ?int $jobId = null): int
    {
        $this->ensure();
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || $to < $from) {
            throw new \InvalidArgumentException('Give the first and last day of the period (last day on or after the first).');
        }
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('The reason is required.');
        }
        $this->db->query('INSERT INTO tf_rot_unavailable (person_id, date_from, date_to, reason, tour_id, job_id, recorded_at, recorded_by) VALUES (?,?,?,?,?,?,?,?)',
            [$personId, $from, $to, mb_substr(trim($reason), 0, 400), $tourId, $jobId, $this->now(), $this->user]);
        return (int) $this->db->insertID();
    }

    public function clearUnavailable(int $id): void
    {
        $this->db->query('UPDATE tf_rot_unavailable SET cleared_at = ?, cleared_by = ? WHERE id = ? AND cleared_at IS NULL', [$this->now(), $this->user, $id]);
    }

    // ------------------------------------------------------------------ review (Transport Operations → Pending Allocations)

    public function review(int $jobId): array
    {
        if (!$this->installed()) {
            return [];
        }
        return $this->db->query('SELECT * FROM tf_rot_review WHERE job_id = ?', [$jobId])->getRowArray() ?: [];
    }

    public function markReviewed(int $jobId, bool $on, ?string $note = null): void
    {
        $this->ensure();
        $j = $this->db->query('SELECT j.id, j.tour_id, j.status, t.tour_no FROM tf_transport_job j JOIN tf_tour t ON t.id = j.tour_id WHERE j.id = ?', [$jobId])->getRowArray();
        if (!$j) {
            throw new \RuntimeException('Transport card not found.');
        }
        if ($on) {
            if ((string) $j['status'] === 'cancelled') {
                throw new \RuntimeException('A cancelled card is not sent for allocation.');
            }
            $this->db->query('INSERT INTO tf_rot_review (job_id, tour_id, reviewed_at, reviewed_by, note) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE reviewed_at = VALUES(reviewed_at), reviewed_by = VALUES(reviewed_by), note = VALUES(note)',
                [$jobId, (int) $j['tour_id'], $this->now(), $this->user, $note]);
        } else {
            $this->db->query('DELETE FROM tf_rot_review WHERE job_id = ?', [$jobId]);
        }
        $this->log(['job_id' => $jobId, 'tour_id' => (int) $j['tour_id'], 'tour_no' => $j['tour_no'], 'event' => $on ? 'review' : 'unreview', 'reason' => $note]);
    }

    // ------------------------------------------------------------------ the lanes and the counts

    /** The card's desk step (tf_transport_job.status) in words — a separate field from the allocation stage. */
    public const DESK_STEP = ['to_allocate' => 'To allocate', 'allocated' => 'Allocated', 'scheduled' => 'Scheduled', 'pack_issued' => 'Pack issued',
        'running' => 'Running', 'returned' => 'Returned', 'settled' => 'Settled', 'cancelled' => 'Cancelled'];

    /**
     * Every live card with two separate answers, worked out the same way for the lanes and the counts:
     *   allocation  not_reviewed · awaiting_driver · driver_no_vehicle · guide_open · complete
     *               (complete = a driver engaged AND a vehicle on the card AND every other crew line on the card engaged)
     *   tour        not_started · on_tour (arrival ≤ today ≤ departure) · finished
     * and the lane they put it in: awaiting_review · pending · confirming · upcoming · on_tour · done.
     */
    public function cards(): array
    {
        $today = date('Y-m-d');
        $reviews = [];
        $alloc = [];
        if ($this->installed()) {
            foreach ($this->db->query('SELECT * FROM tf_rot_review')->getResultArray() as $r) {
                $reviews[(int) $r['job_id']] = $r;
            }
            foreach ($this->db->query('SELECT * FROM tf_rot_allocation ORDER BY id')->getResultArray() as $a) {
                $alloc[(int) $a['job_id']] = $a;   // the latest allocation record of the card
            }
        }
        $rows = $this->db->query(
            "SELECT j.id, j.tour_id, j.seq, j.class_code, j.vehicle_type, j.seats, j.qty, j.days, j.km_total, j.extra_km_total, j.vehicle_reg, j.status,
                    t.tour_no, t.agent_name, t.tour_name, t.arrival_date, t.departure_date, t.pax_total, t.arrival_flight
             FROM tf_transport_job j JOIN tf_tour t ON t.id = j.tour_id
             WHERE t.stage <> 'cancelled' AND j.archived_at IS NULL AND j.status NOT IN ('settled','cancelled')
             ORDER BY t.arrival_date IS NULL, t.arrival_date, t.id, j.seq LIMIT 800"
        )->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $crew = $this->db->query("SELECT id, kind, role_code, role_name, status, person_name, date_from, date_to" . ($this->hasCol('tf_transport_crew', 'person_id') ? ', person_id' : '') . " FROM tf_transport_crew WHERE job_id = ? AND status <> 'replaced' ORDER BY kind = 'driver' DESC, id", [(int) $r['id']])->getResultArray();
            $driver = null;
            $open = [];
            foreach ($crew as $c) {
                if ($c['kind'] === 'driver' && $driver === null) {
                    $driver = $c;
                }
                if ((string) $c['status'] !== 'engaged') {
                    $open[] = $c;
                }
            }
            $pax = (int) $r['pax_total'];
            $r['crew'] = $crew;
            $r['driver'] = $driver;
            $r['open_lines'] = $open;
            $r['review'] = $reviews[(int) $r['id']] ?? null;
            $r['pax'] = $pax;
            $r['guide_required'] = $pax >= self::GUIDE_FROM_GUESTS;
            $r['guide_line_missing'] = $r['guide_required'] && !array_filter($crew, static fn ($c) => $c['kind'] === 'guide');
            $r['needed'] = $this->neededClass($pax, (string) $r['class_code']);
            $r['allocation'] = $alloc[(int) $r['id']] ?? null;
            $r['desk_step'] = self::DESK_STEP[$r['status']] ?? $r['status'];
            $driverIn = $driver && (string) $driver['status'] === 'engaged';
            $vehicleIn = trim((string) $r['vehicle_reg']) !== '';
            if (!$driverIn) {
                $r['alloc_stage'] = $r['review'] ? 'awaiting_driver' : 'not_reviewed';
            } elseif (!$vehicleIn) {
                $r['alloc_stage'] = 'driver_no_vehicle';
            } elseif ($open || $r['guide_line_missing']) {
                $r['alloc_stage'] = 'guide_open';
            } else {
                $r['alloc_stage'] = 'complete';
            }
            $r['alloc_label'] = ['not_reviewed' => 'Not reviewed yet', 'awaiting_driver' => 'Awaiting driver', 'driver_no_vehicle' => 'Driver engaged — vehicle not set',
                'guide_open' => $r['guide_line_missing'] ? 'Driver engaged — guide line missing' : 'Driver engaged — ' . implode(', ', array_map(static fn ($c) => $c['kind'] . ' ' . ($c['role_code'] ?? ''), $open)) . ' to confirm',
                'complete' => 'Allocation complete'][$r['alloc_stage']];
            if ($r['departure_date'] && $r['departure_date'] < $today) {
                $r['tour_stage'] = 'finished';
                $r['tour_label'] = 'Finished ' . date('d M', strtotime($r['departure_date']));
            } elseif ($r['arrival_date'] && $r['arrival_date'] <= $today) {
                $r['tour_stage'] = 'on_tour';
                $total = $r['departure_date'] ? (int) ((strtotime($r['departure_date']) - strtotime($r['arrival_date'])) / 86400) + 1 : null;
                $r['tour_label'] = 'On tour — day ' . ((int) ((strtotime($today) - strtotime($r['arrival_date'])) / 86400) + 1) . ($total ? ' of ' . $total : '');
            } else {
                $r['tour_stage'] = 'not_started';
                $d = $r['arrival_date'] ? (int) ((strtotime($r['arrival_date']) - strtotime($today)) / 86400) : null;
                $r['tour_label'] = $d === null ? 'No dates' : ($d === 1 ? 'Starts tomorrow' : 'Starts in ' . $d . ' days');
            }
            // the lane: allocation first — a tour is Upcoming / On Tour only once its allocation is complete
            if ($r['alloc_stage'] === 'complete') {
                $r['lane'] = ['finished' => 'done', 'on_tour' => 'on_tour', 'not_started' => 'upcoming'][$r['tour_stage']];
            } elseif ($r['alloc_stage'] === 'not_reviewed') {
                $r['lane'] = 'awaiting_review';
            } elseif ($r['alloc_stage'] === 'awaiting_driver') {
                $r['lane'] = 'pending';
            } else {
                $r['lane'] = 'confirming';
            }
            $r['urgent'] = $r['alloc_stage'] !== 'complete' && $r['tour_stage'] !== 'not_started';   // started but allocation incomplete
            $out[] = $r;
        }
        return $out;
    }

    public function counts(): array
    {
        $n = ['awaiting_review' => 0, 'pending' => 0, 'confirming' => 0, 'upcoming' => 0, 'on_tour' => 0, 'done' => 0];
        foreach ($this->cards() as $c) {
            $n[$c['lane']]++;
        }
        return $n;
    }

    // ------------------------------------------------------------------ eligibility and the proposal

    /** Every Japanese-speaking (and every other) driver assessed for one card: the eligible in rotation order, the rest with why. */
    public function assess(int $jobId, CrewPeople $people): array
    {
        $this->ensure();
        $j = $this->card($jobId);
        if (!$j['arrival_date'] || !$j['departure_date']) {
            throw new \RuntimeException('The tour has no dates yet; the rotation needs the arrival and departure dates.');
        }
        $pax = (int) $j['pax_total'];
        $need = $this->neededClass($pax, (string) $j['class_code']);
        $class = $need['code'] ?? (string) $j['class_code'];
        $carRateTour = $class === 'SEDAN';
        $lineCat = $j['line_category'];
        $blocks = $this->cardBlocks($j);
        $ext = [];
        foreach ($this->db->query('SELECT * FROM tf_rot_driver')->getResultArray() as $r) {
            $ext[(int) $r['person_id']] = $r;
        }
        $poolSize = count(array_filter($ext, static fn ($e) => (int) $e['in_pool'] === 1));
        $from = (string) $j['arrival_date'];
        $to = (string) $j['departure_date'];
        $eligible = [];
        $out = [];
        foreach ($people->all(null, true) as $p) {
            if (!in_array((string) ($p['kind'] ?? 'driver'), ['driver', 'both'], true)) {
                continue;   // guides (JSNTG) are not drivers; guide allocation stays manual
            }
            $pid = (int) $p['id'];
            $cat = (string) ($p['category'] ?? '');
            $e = $ext[$pid] ?? null;
            $why = [];
            // language and role: only Japanese-speaking drivers, only the category the quotation's line asks for, never substituted
            if (!in_array($cat, self::AUTO_LINES, true)) {
                $why[] = 'not a Japanese-speaking driver on the master (category ' . ($cat !== '' ? $cat : 'not set') . ')';
            } elseif ($lineCat !== null && in_array($lineCat, self::AUTO_LINES, true) && $cat !== $lineCat) {
                $why[] = 'this tour\'s line needs a ' . $lineCat;
            }
            if (isset($p['category_confirmed']) && (int) $p['category_confirmed'] === 0) {
                $why[] = 'category not confirmed on the master';
            }
            if (!$e || (int) $e['in_pool'] !== 1) {
                $why[] = 'not in the rotation pool';
            }
            // the actual vehicle: of the class needed AND carrying the guests by its recorded capacity (a van never above 7)
            $vehicles = $people->vehicles($pid);
            $match = null;
            $carRateVan = false;
            $capIssue = null;
            $candidates = [];
            foreach ($vehicles as $v) {
                foreach (self::CLASS_VEHICLE[$class] ?? [] as [$type, $roof]) {
                    if ($v['vehicle_type'] === $type && ($roof === null || $v['roof_type'] === $roof)) {
                        $candidates[] = [$v, false];
                        break;
                    }
                }
            }
            if (!$candidates && $carRateTour && $e && (int) $e['car_rate_optin'] === 1) {
                foreach ($vehicles as $v) {
                    if ($v['vehicle_type'] === 'van') {
                        $candidates[] = [$v, true];
                    }
                }
            }
            usort($candidates, static fn ($a, $b) => (int) $b[0]['is_current'] <=> (int) $a[0]['is_current']);
            foreach ($candidates as [$v, $isCarRateVan]) {
                $cap = self::guestCapacity($v);
                if ($cap === null) {
                    $capIssue ??= 'guest capacity not recorded for ' . ($v['reg_no'] ?: 'his vehicle');
                    continue;
                }
                if ($pax > $cap) {
                    $capIssue ??= 'vehicle ' . ($v['reg_no'] ?: '') . ' carries ' . $cap . ' guests' . ($v['vehicle_type'] === 'van' && (int) $v['max_pax_luggage'] > self::MAX_VAN_GUESTS ? ' (a van never more than ' . self::MAX_VAN_GUESTS . ')' : '') . ', the booking has ' . $pax;
                    continue;
                }
                $match = $v;
                $carRateVan = $isCarRateVan;
                break;
            }
            if (!$candidates) {
                $hasVan = (bool) array_filter($vehicles, static fn ($v) => $v['vehicle_type'] === 'van');
                $why[] = $carRateTour && $hasVan ? 'van driver not opted in to car-rate tours' : 'no ' . strtolower((string) ($need['name'] ?: $class)) . ' on his record';
            } elseif (!$match) {
                $why[] = $capIssue;
            }
            if ($carRateVan && ($e['agreed_car_rate_km'] === null || $e['agreed_car_rate_km'] === '')) {
                $why[] = 'agreed car rate not on record';
            }
            foreach ($p['warnings'] ?? [] as $w) {
                if ($w['level'] === 'block') {
                    $why[] = strtolower(rtrim($w['text'], '.'));
                }
            }
            // availability: Not Available, every engagement (Transport Desk and the earlier page) with the five-day rest either side
            $acc = (string) ($p['supplier_acc'] ?? '');
            foreach ($this->db->query('SELECT date_from, date_to, reason FROM tf_rot_unavailable WHERE person_id = ? AND cleared_at IS NULL AND date_from <= ? AND date_to >= ?', [$pid, $to, $from])->getResultArray() as $u) {
                $why[] = 'not available ' . $u['date_from'] . ' – ' . $u['date_to'] . ' (' . $u['reason'] . ')';
            }
            foreach ($this->engagements($pid, $acc) as $g) {
                if ($g['job_id'] === $jobId) {
                    continue;
                }
                if ($g['from'] <= $to && $g['to'] >= $from) {
                    $why[] = 'already engaged on ' . $g['tour_no'] . ' (' . $g['from'] . ' – ' . $g['to'] . ')' . ($g['legacy'] ? ' on the earlier allocation page' : '');
                } elseif ($g['to'] < $from && $this->addDays($g['to'], self::REST_DAYS + 1) > $from) {
                    $why[] = 'rest after ' . $g['tour_no'] . ' (ends ' . $g['to'] . ', free from ' . $this->addDays($g['to'], self::REST_DAYS + 1) . ')';
                } elseif ($g['from'] > $to && $this->addDays($to, self::REST_DAYS + 1) > $g['from']) {
                    $why[] = 'rest before ' . $g['tour_no'] . ' (starts ' . $g['from'] . ', this tour would end ' . $to . ')';
                }
            }
            $why = array_values(array_unique($why));
            $last = $this->lastCompleted($pid, $acc);
            // the queue position: the end of his LATEST engagement — completed, running or already allocated — so a driver who
            // already holds a tour goes to the back of the line; never engaged at all = front of the line
            $latest = null;
            foreach ($this->engagements($pid, $acc) as $g) {
                if ($g['job_id'] !== $jobId && ($latest === null || $g['to'] > $latest['end'])) {
                    $latest = ['end' => $g['to'], 'tour_no' => $g['tour_no']];
                }
            }
            $pay = $match ? $this->paymentBasis($j, $carRateVan, $e, $p) : null;
            $row = [
                'id' => $pid, 'name' => $p['name'], 'nickname' => $p['nickname'] ?? null, 'category' => $cat !== '' ? $cat : null, 'mobile' => ($p['mobile'] ?? null) ?: ($p['phone'] ?? null), 'acc' => $acc,
                'vehicle' => $match ? ['id' => $match['id'], 'reg_no' => $match['reg_no'], 'type' => $match['vehicle_type'], 'type_name' => $match['type_name'] ?? $match['vehicle_type'], 'roof' => $match['roof_type'], 'guests' => self::guestCapacity($match)] : null,
                'car_rate_van' => $carRateVan, 'in_pool' => $e ? (int) $e['in_pool'] : 0, 'car_rate_optin' => $e ? (int) $e['car_rate_optin'] : 0,
                'agreed_car_rate_km' => $e['agreed_car_rate_km'] ?? null, 'agreed_van_rate_km' => $e['agreed_van_rate_km'] ?? null, 'master_rate_km' => $p['rate_effective']['rate_km'] ?? null,
                'last_tour_end' => $last['end'] ?? null, 'last_tour_no' => $last['tour_no'] ?? null, 'free_from' => $last ? $this->addDays($last['end'], self::REST_DAYS + 1) : null,
                'waiting_days' => $last ? max(0, (int) ((strtotime(date('Y-m-d')) - strtotime($last['end'])) / 86400)) : null,
                'queue_from' => $latest['end'] ?? null, 'queue_tour' => $latest['tour_no'] ?? null,
                'warnings' => array_values(array_filter($p['warnings'] ?? [], static fn ($w) => $w['level'] === 'warn')),
                'payment' => $pay,
                'why_out' => $why, 'eligible' => !$why && !$blocks,
                'bypassable' => !$blocks && $why && !array_diff($why, self::BYPASSABLE),
            ];
            if ($row['eligible']) {
                $eligible[] = $row;
            }
            $out[] = $row;
        }
        // the one waiting longest first: ordered by the end of each driver's latest engagement (completed, running or already
        // allocated); never engaged at all comes first; equal dates by name
        usort($eligible, static fn ($a, $b) => [($a['queue_from'] ?? '0000-00-00'), $a['name']] <=> [($b['queue_from'] ?? '0000-00-00'), $b['name']]);
        $proposal = $eligible[0] ?? null;
        return ['job' => ['id' => (int) $j['id'], 'tour_id' => (int) $j['tour_id'], 'tour_no' => $j['tour_no'], 'tour_name' => $j['tour_name'], 'agent_name' => $j['agent_name'], 'arrival_date' => $from, 'departure_date' => $to, 'pax' => $pax,
            'km_total' => $j['km_total'], 'extra_km_total' => $j['extra_km_total'], 'qty' => max(1, (int) ($j['qty'] ?? 1)), 'days' => $j['days'], 'class_code' => $j['class_code'], 'vehicle_type' => $j['vehicle_type'],
            'line_code' => $j['line_code'], 'guide_lines' => count($j['guide_lines'])],
            'need' => $need, 'line_category' => $lineCat, 'blocks' => $blocks, 'car_rate_tour' => $carRateTour, 'pool_size' => $poolSize,
            'eligible' => $eligible, 'proposal' => $proposal, 'car_rate' => $proposal && $proposal['car_rate_van'] ? $proposal['payment'] : null,
            'bypass_candidates' => array_values(array_filter($out, static fn ($r) => $r['bypassable'] && (!$proposal || $r['id'] !== $proposal['id']))),
            'all' => $out, 'warning_bypass' => self::WARNING_BYPASS];
    }

    /**
     * The payment basis an allocation is paid on — the same one the card, the acceptance record and the report show:
     *   a van at the car rate      → the driver's agreed car rate (tf_rot_driver), never the van rate
     *   any other allocation       → the rate per km on the Drivers & Guides master in force (what the desk copies onto the line)
     * × payable km = route km (km_total) + local km (extra_km_total), the desk's own scheduled basis, × the number of vehicles.
     */
    public function paymentBasis(array $j, bool $carRateVan, ?array $e, array $p): array
    {
        $route = (float) ($j['km_total'] ?? 0);
        $local = (float) ($j['extra_km_total'] ?? 0);
        $qty = max(1, (int) ($j['qty'] ?? 1));
        if ($carRateVan) {
            $rate = isset($e['agreed_car_rate_km']) && $e['agreed_car_rate_km'] !== '' && $e['agreed_car_rate_km'] !== null ? (float) $e['agreed_car_rate_km'] : null;
            $basis = 'car_rate';
            $source = 'agreed car rate (van at the car rate)';
        } else {
            $m = $p['rate_effective']['rate_km'] ?? null;
            $rate = $m !== null && $m !== '' ? (float) $m : null;
            $basis = 'master';
            $source = 'Drivers & Guides master rate' . (!empty($p['rate_effective']['source']) ? ' (' . $p['rate_effective']['source'] . ')' : '');
        }
        $payable = $route + $local;
        $amount = $rate !== null ? round($rate * $payable * $qty, 2) : null;
        $kmText = number_format($route, 0) . ' km route + ' . number_format($local, 0) . ' km local = ' . number_format($payable, 0) . ' payable km' . ($qty > 1 ? ' × ' . $qty . ' vehicles' : '');
        return ['basis' => $basis, 'rate_km' => $rate, 'source' => $source, 'km_route' => $route, 'km_local' => $local, 'km_payable' => $payable, 'km' => $payable, 'vehicles' => $qty,
            'amount' => $amount, 'currency' => 'LKR', 'km_text' => $kmText, 'missing' => $rate === null,
            'text' => $rate === null
                ? 'Payment basis: ' . $source . ' — NOT ON FILE. ' . $kmText . '. The rate must be supplied before this allocation can be paid.'
                : ($basis === 'car_rate'
                    ? 'Car-rate tour (1–2 guests) in a van: paid at the agreed car rate LKR ' . number_format($rate, 2) . '/km × ' . $kmText . ' = LKR ' . number_format((float) $amount, 2) . '. The van rate does not apply to this tour.'
                    : 'Paid at the ' . $source . ' LKR ' . number_format($rate, 2) . '/km × ' . $kmText . ' = LKR ' . number_format((float) $amount, 2) . ' (scheduled; settled on actual km).')];
    }

    /** Kept for older callers: the car-rate terms for a card at a given rate. */
    public function carRateTerms(array $j, float $rateKm): array
    {
        return $this->paymentBasis($j, true, ['agreed_car_rate_km' => $rateKm], []);
    }

    /**
     * The driver's engagements with their dates: on the Transport Desk (tf_transport_crew, by person or ledger account) and on
     * the earlier Pending Driver Allocation page (driver_allocation by ledger account, dates from the consignee). Shared by both.
     */
    private function engagements(int $personId, string $acc): array
    {
        $out = [];
        $hasPid = $this->hasCol('tf_transport_crew', 'person_id');
        if ($hasPid || $acc !== '') {
            $where = $hasPid ? '(c.person_id = ?' . ($acc !== '' ? ' OR c.supplier_acc = ?' : '') . ')' : 'c.supplier_acc = ?';
            $bind = $hasPid ? ($acc !== '' ? [$personId, $acc] : [$personId]) : [$acc];
            $rows = $this->db->query(
                "SELECT c.job_id, t.tour_no, COALESCE(c.date_from, t.arrival_date) AS dfrom, COALESCE(c.date_to, t.departure_date) AS dto, j.returned_at
                 FROM tf_transport_crew c JOIN tf_transport_job j ON j.id = c.job_id JOIN tf_tour t ON t.id = j.tour_id
                 WHERE {$where} AND c.kind = 'driver' AND c.status = 'engaged' AND j.status <> 'cancelled' AND j.archived_at IS NULL AND t.stage <> 'cancelled'",
                $bind
            )->getResultArray();
            foreach ($rows as $r) {
                if (!$r['dfrom'] || !$r['dto']) {
                    continue;
                }
                $to = $r['returned_at'] ? min((string) $r['dto'], substr((string) $r['returned_at'], 0, 10)) : (string) $r['dto'];   // actual completion when known
                $out[] = ['job_id' => (int) $r['job_id'], 'tour_no' => $r['tour_no'], 'from' => (string) $r['dfrom'], 'to' => $to, 'legacy' => false];
            }
        }
        if ($acc !== '' && $this->hasTable('driver_allocation') && $this->hasTable('consignee')) {
            foreach ($this->db->query("SELECT da.manual_id, c.start_date, c.end_date FROM driver_allocation da JOIN consignee c ON c.tour_id = da.tour_id
                    WHERE da.driver = ? AND COALESCE(da.request_allocation, 0) <> 1 AND COALESCE(c.tour_cancel, 0) <> 1", [$acc])->getResultArray() as $r) {
                if (!$r['start_date'] || !$r['end_date']) {
                    continue;
                }
                $out[] = ['job_id' => 0, 'tour_no' => (string) $r['manual_id'], 'from' => substr((string) $r['start_date'], 0, 10), 'to' => substr((string) $r['end_date'], 0, 10), 'legacy' => true];
            }
        }
        return $out;
    }

    /** The legacy allocation of the same tour, when the TourFlow tour is also on the earlier page (legacy tour id or the same tour number). */
    private function legacyAllocationFor(array $j): ?array
    {
        if (!$this->hasTable('driver_allocation')) {
            return null;
        }
        $row = $this->db->query("SELECT manual_id, driver, driver_name FROM driver_allocation WHERE (tour_id = ? OR manual_id = ?) AND COALESCE(request_allocation, 0) <> 1 ORDER BY id DESC LIMIT 1",
            [(string) ($j['legacy_tour_id'] ?? '') !== '' ? (string) $j['legacy_tour_id'] : '-none-', (string) $j['tour_no']])->getRowArray();
        return $row ?: null;
    }

    /** The last tour the driver finished (ended before today), for the waiting order and the rest days. */
    private function lastCompleted(int $personId, string $acc): ?array
    {
        $today = date('Y-m-d');
        $best = null;
        foreach ($this->engagements($personId, $acc) as $g) {
            if ($g['to'] < $today && ($best === null || $g['to'] > $best['end'])) {
                $best = ['end' => $g['to'], 'tour_no' => $g['tour_no']];
            }
        }
        return $best;
    }

    // ------------------------------------------------------------------ shared protection for the earlier page

    /**
     * Called by the earlier Pending Driver Allocation page before it saves an allocation (DriverAllocationController): refuses the
     * same tour when its driver is already engaged on the Transport Desk, and a driver already engaged on an overlapping Transport
     * Desk tour. Returns the refusal message, or null to let the page go on exactly as before.
     */
    public static function legacyGuard($db, string $tourId, string $manualId, string $driverAcc, ?string $start, ?string $end): ?string
    {
        try {
            if (!(bool) $db->query("SHOW TABLES LIKE 'tf_transport_crew'")->getResultArray()) {
                return null;
            }
            $hasLegacy = (bool) $db->query("SHOW COLUMNS FROM `tf_tour` LIKE 'legacy_tour_id'")->getResultArray();
            $same = $db->query("SELECT t.tour_no, c.person_name FROM tf_tour t JOIN tf_transport_job j ON j.tour_id = t.id JOIN tf_transport_crew c ON c.job_id = j.id
                    WHERE (" . ($hasLegacy ? 't.legacy_tour_id = ? OR ' : '') . "t.tour_no = ?) AND t.stage <> 'cancelled' AND j.status <> 'cancelled' AND c.kind = 'driver' AND c.status = 'engaged' LIMIT 1",
                $hasLegacy ? [$tourId, $manualId] : [$manualId])->getRowArray();
            if ($same) {
                return 'This tour (' . $same['tour_no'] . ') already has its driver allocated on the Transport Desk: ' . $same['person_name'] . '. It is not allocated twice.';
            }
            if ($start && $end && $driverAcc !== '') {
                $hasPid = (bool) $db->query("SHOW COLUMNS FROM `tf_transport_crew` LIKE 'person_id'")->getResultArray();
                $clash = $db->query("SELECT t.tour_no, COALESCE(c.date_from, t.arrival_date) AS dfrom, COALESCE(c.date_to, t.departure_date) AS dto
                        FROM tf_transport_crew c JOIN tf_transport_job j ON j.id = c.job_id JOIN tf_tour t ON t.id = j.tour_id
                        WHERE (c.supplier_acc = ?" . ($hasPid ? ' OR c.person_id IN (SELECT id FROM tf_crew_person WHERE supplier_acc = ?)' : '') . ")
                          AND c.kind = 'driver' AND c.status = 'engaged' AND j.status <> 'cancelled' AND t.stage <> 'cancelled'
                          AND COALESCE(c.date_from, t.arrival_date) <= ? AND COALESCE(c.date_to, t.departure_date) >= ? LIMIT 1",
                    $hasPid ? [$driverAcc, $driverAcc, substr($end, 0, 10), substr($start, 0, 10)] : [$driverAcc, substr($end, 0, 10), substr($start, 0, 10)])->getRowArray();
                if ($clash) {
                    return 'This driver is already engaged on the Transport Desk for ' . $clash['tour_no'] . ' (' . $clash['dfrom'] . ' – ' . $clash['dto'] . '), which overlaps this tour. He is not allocated twice.';
                }
            }
        } catch (\Throwable $e) {
            return null;   // the earlier page keeps working exactly as before if the Transport Desk tables cannot be read
        }
        return null;
    }

    /** How the earlier page's pending tours relate to the Transport Desk — read only, for the proposed handling. */
    public function legacySummary(): array
    {
        if (!$this->hasTable('consignee')) {
            return ['available' => false];
        }
        $audit = $this->hasTable('driver_allocation_audit');
        $rows = $this->db->query("SELECT c.tour_id, c.manual_id, c.start_date, c.end_date, c.passenger_count FROM consignee c
                WHERE c.verify = 1 AND c.start_date >= ? AND COALESCE(c.tour_cancel, 0) = 0 AND COALESCE(c.hotel_only, 0) = 0"
            . ($audit ? ' AND NOT EXISTS (SELECT 1 FROM driver_allocation_audit a WHERE a.tour_id = c.tour_id AND a.manual_id = c.manual_id)' : '') . ' ORDER BY c.start_date', [date('Y-m-d')])->getResultArray();
        $hasLegacy = $this->hasCol('tf_tour', 'legacy_tour_id');
        $s = ['available' => true, 'pending' => count($rows), 'on_transport_desk' => 0, 'desk_driver_engaged' => 0, 'legacy_driver_allocated' => 0, 'only_on_earlier_page' => 0, 'examples' => []];
        foreach ($rows as $r) {
            $tf = $this->db->query('SELECT t.id, t.tour_no, (SELECT COUNT(*) FROM tf_transport_job j WHERE j.tour_id = t.id) AS cards,
                    (SELECT COUNT(*) FROM tf_transport_job j JOIN tf_transport_crew c ON c.job_id = j.id WHERE j.tour_id = t.id AND c.kind = \'driver\' AND c.status = \'engaged\') AS drivers
                    FROM tf_tour t WHERE (' . ($hasLegacy ? 't.legacy_tour_id = ? OR ' : '') . "t.tour_no = ?) AND t.stage <> 'cancelled' LIMIT 1",
                $hasLegacy ? [(string) $r['tour_id'], (string) $r['manual_id']] : [(string) $r['manual_id']])->getRowArray();
            $la = $this->hasTable('driver_allocation') ? (int) ($this->db->query('SELECT COUNT(*) n FROM driver_allocation WHERE tour_id = ? AND COALESCE(request_allocation, 0) <> 1', [$r['tour_id']])->getRowArray()['n'] ?? 0) : 0;
            if ($la) {
                $s['legacy_driver_allocated']++;
            }
            if ($tf && (int) $tf['cards'] > 0) {
                $s['on_transport_desk']++;
                if ((int) $tf['drivers'] > 0) {
                    $s['desk_driver_engaged']++;
                }
                if (count($s['examples']) < 10) {
                    $s['examples'][] = ['manual_id' => $r['manual_id'], 'tour_no' => $tf['tour_no'], 'start' => $r['start_date'], 'desk_driver' => (int) $tf['drivers'] > 0, 'legacy_driver' => $la > 0];
                }
            } else {
                $s['only_on_earlier_page']++;
            }
        }
        return $s;
    }

    // ------------------------------------------------------------------ the decisions

    /**
     * Accept the rotation's proposal (or, with $bypass, a driver chosen out of turn): the driver line is engaged through
     * TourService::engageCrew and his actual vehicle set through TourService::allocateVehicle, under a row lock; the allocation
     * record keeps the required class, the actual vehicle and the payment basis; the log keeps the decision.
     */
    public function allocate(TourService $svc, CrewPeople $people, int $jobId, int $personId, array $opt = []): array
    {
        $this->ensure();
        $a = $this->assess($jobId, $people);
        if ($a['blocks']) {
            throw new \RuntimeException(implode(' ', $a['blocks']));
        }
        $cand = null;
        foreach ($a['all'] as $r) {
            if ($r['id'] === $personId) {
                $cand = $r;
            }
        }
        if (!$cand) {
            throw new \RuntimeException('That driver is not on the master as an active driver.');
        }
        $bypass = !empty($opt['bypass']);
        if (!$bypass) {
            if (!$a['proposal'] || $a['proposal']['id'] !== $personId) {
                throw new \RuntimeException('The rotation proposes ' . ($a['proposal']['name'] ?? 'nobody') . ' for this tour. To give it to another suitable driver use Bypass Rotation and give the reason.');
            }
        } else {
            if (trim((string) ($opt['reason'] ?? '')) === '') {
                throw new \RuntimeException('Bypass Rotation needs the reason; it is recorded and reported.');
            }
            if (empty($opt['warning_ack'])) {
                throw new \RuntimeException('Confirm that you have read the warning before bypassing the rotation.');
            }
            // Bypass sets aside whose turn it is and pool membership ONLY
            $hard = array_values(array_diff($cand['why_out'], self::BYPASSABLE));
            if ($hard) {
                throw new \RuntimeException(ucfirst(implode('; ', $hard)) . ' — Bypass Rotation changes only rotation order or pool membership; it cannot override vehicle suitability, capacity, language/category, availability, overlap, rest days or car-rate consent.');
            }
            if ($a['proposal'] && $a['proposal']['id'] === $personId) {
                throw new \RuntimeException($cand['name'] . ' is the rotation\'s own proposal — use Accept allocation, not Bypass.');
            }
        }
        if ($cand['car_rate_van'] && empty($opt['car_rate_accepted'])) {
            throw new \RuntimeException('Record the driver\'s acceptance of the car-rate terms for this tour before confirming.');
        }
        $pay = $cand['payment'];
        $this->db->transStart();
        $line = $this->db->query("SELECT id, status, person_name FROM tf_transport_crew WHERE job_id = ? AND kind = 'driver' AND status <> 'replaced' ORDER BY id DESC LIMIT 1 FOR UPDATE", [$jobId])->getRowArray();
        if (!$line) {
            $this->db->transComplete();
            throw new \RuntimeException('This card has no driver line.');
        }
        if ((string) $line['status'] === 'engaged') {
            $this->db->transComplete();
            throw new \RuntimeException('A driver was engaged on this card a moment ago (' . $line['person_name'] . ') — nothing changed.');
        }
        // the same rules once more under the lock: another desk may have engaged this driver elsewhere meanwhile
        $again = null;
        foreach ($this->assess($jobId, $people)['all'] as $r) {
            if ($r['id'] === $personId) {
                $again = $r;
            }
        }
        $overlap = array_filter($again['why_out'] ?? [], static fn ($w) => str_starts_with($w, 'already engaged'));
        if ($overlap) {
            $this->db->transComplete();
            throw new \RuntimeException(ucfirst(implode('; ', $overlap)) . ' — engaged elsewhere meanwhile.');
        }
        $note = ($bypass ? 'Rotation bypassed: ' . trim((string) $opt['reason']) : 'Fair rotation: waiting longest' . ($cand['queue_from'] ? ' (his last tour ' . ($cand['queue_tour'] ? $cand['queue_tour'] . ' ' : '') . 'ends ' . $cand['queue_from'] . ')' : ' (no tour on his record)'))
            . ($cand['car_rate_van'] ? ' · car-rate terms accepted' : '');
        // tf-taxiq-r7: the rotation's own note ("Fair rotation: …", or "Rotation bypassed: <the reason given>")
        // is the reason for this change, so an allocation on a tour already confirmed to the agent is recorded
        // with it rather than refused for want of one.
        $svc->engageCrew((int) $line['id'], null, null, null, 'engaged', mb_substr($note, 0, 250), $personId, true, mb_substr($note, 0, 400));
        if ($cand['car_rate_van']) {
            // the car-rate payment basis stays on the line: the agreed car rate per km, never the van rate
            $this->db->query('UPDATE tf_transport_crew SET person_km_rate = ?, person_rate_source = ?, note = ? WHERE id = ?',
                [$pay['rate_km'], 'special', mb_substr('Car-rate tour accepted by the driver — LKR ' . number_format((float) $pay['rate_km'], 2) . '/km (van ' . ($cand['vehicle']['reg_no'] ?? '') . '). ' . $note, 0, 250), (int) $line['id']]);
        }
        // his actual vehicle on the card: the desk's own call, which moves the card to "Allocated" (a driver engaged + a vehicle)
        $card = $svc->allocateVehicle($jobId, null, (string) ($cand['vehicle']['reg_no'] ?: ($cand['vehicle']['type_name'] ?? 'vehicle')),
            mb_substr('Driver\'s own ' . strtolower((string) ($cand['vehicle']['type_name'] ?? '')) . ($cand['vehicle']['roof'] ? ' (' . $cand['vehicle']['roof'] . ' roof)' : '') . ', ' . (int) $cand['vehicle']['guests'] . ' guests' . ($cand['car_rate_van'] ? ' — van at the car rate' : '') . ' · required: ' . ($a['need']['name'] ?? ''), 0, 250));
        $accepted = $cand['car_rate_van'] ? $pay['text'] : null;
        $this->db->query('INSERT INTO tf_rot_allocation (job_id, tour_id, crew_id, person_id, person_name, line_category, guests, required_class, required_class_name, quoted_class, vehicle_id, vehicle_reg, vehicle_type, vehicle_roof, vehicle_guests,
                car_rate_tour, pay_basis, pay_rate_km, pay_rate_source, km_route, km_local, km_payable, vehicles, pay_amount, pay_currency, driver_accepted, accepted_text, how, reason, allocated_at, allocated_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$jobId, $a['job']['tour_id'], (int) $line['id'], $personId, $cand['name'], $a['line_category'], $a['job']['pax'], $a['need']['code'], $a['need']['name'], $a['job']['class_code'],
                $cand['vehicle']['id'] ?? null, $cand['vehicle']['reg_no'] ?? null, $cand['vehicle']['type'] ?? null, $cand['vehicle']['roof'] ?? null, $cand['vehicle']['guests'] ?? null,
                $cand['car_rate_van'] ? 1 : 0, $pay['basis'], $pay['rate_km'], $pay['source'], $pay['km_route'], $pay['km_local'], $pay['km_payable'], $pay['vehicles'], $pay['amount'], $pay['currency'],
                $cand['car_rate_van'] ? 1 : 0, $accepted, $bypass ? 'bypass' : 'rotation', $bypass ? trim((string) $opt['reason']) : null, $this->now(), $this->user]);
        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('The allocation could not be saved.');
        }
        $base = ['job_id' => $jobId, 'tour_id' => $a['job']['tour_id'], 'tour_no' => $a['job']['tour_no'], 'crew_id' => (int) $line['id'], 'person_id' => $personId, 'person_name' => $cand['name']];
        $vehicleDetail = ['required_class' => $a['need']['name'], 'actual_vehicle' => trim(($cand['vehicle']['type_name'] ?? '') . ' ' . ($cand['vehicle']['reg_no'] ?? '')), 'payment' => $pay];
        if ($bypass) {
            $this->log($base + ['event' => 'bypass', 'reason' => trim((string) $opt['reason']), 'details' => $vehicleDetail + ['passed_over' => $a['proposal'] ? ['id' => $a['proposal']['id'], 'name' => $a['proposal']['name']] : null, 'warning_acknowledged' => true, 'warning' => self::WARNING_BYPASS, 'why_out' => $cand['why_out']]]);
        } else {
            $this->log($base + ['event' => 'accepted', 'reason' => $note, 'details' => $vehicleDetail + ['eligible' => array_map(static fn ($r) => $r['name'], $a['eligible'])]]);
        }
        if ($cand['car_rate_van']) {
            $this->log($base + ['event' => 'car_rate_accepted', 'reason' => $pay['text'], 'details' => $pay + ['accepted_by_driver' => true, 'recorded_by' => $this->user, 'actual_vehicle' => $vehicleDetail['actual_vehicle']]]);
        }
        return $card;
    }

    /** "Not Available": the driver proposed cannot take it — the reason and period are kept, and the next in turn is proposed. */
    public function notAvailable(CrewPeople $people, int $jobId, int $personId, string $reason, ?string $from = null, ?string $to = null): array
    {
        $this->ensure();
        $a = $this->assess($jobId, $people);
        $from = $from ?: $a['job']['arrival_date'];
        $to = $to ?: $a['job']['departure_date'];
        $name = null;
        foreach ($a['all'] as $r) {
            if ($r['id'] === $personId) {
                $name = $r['name'];
            }
        }
        $this->addUnavailable($personId, $from, $to, $reason, $a['job']['tour_id'], $jobId);
        $this->log(['job_id' => $jobId, 'tour_id' => $a['job']['tour_id'], 'tour_no' => $a['job']['tour_no'], 'person_id' => $personId, 'person_name' => $name, 'event' => 'not_available', 'reason' => $reason, 'details' => ['date_from' => $from, 'date_to' => $to]]);
        return $this->assess($jobId, $people);   // the automatic replacement: the next eligible driver in turn
    }

    // ------------------------------------------------------------------ the log

    public function log(array $e): void
    {
        $this->ensure();
        $this->db->query('INSERT INTO tf_rot_log (job_id, tour_id, tour_no, crew_id, person_id, person_name, event, reason, details, at, by_user) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$e['job_id'] ?? null, $e['tour_id'] ?? null, $e['tour_no'] ?? null, $e['crew_id'] ?? null, $e['person_id'] ?? null, $e['person_name'] ?? null, (string) $e['event'],
                isset($e['reason']) ? mb_substr((string) $e['reason'], 0, 500) : null, isset($e['details']) ? json_encode($e['details'], JSON_UNESCAPED_UNICODE) : null, $this->now(), $this->user]);
    }

    public function report(array $f = []): array
    {
        if (!$this->installed()) {
            return [];
        }
        $where = ["event IN ('bypass','not_available','accepted','car_rate_accepted')"];
        $bind = [];
        if (!empty($f['event'])) {
            $where[] = 'event = ?';
            $bind[] = $f['event'];
        }
        if (!empty($f['from'])) {
            $where[] = 'at >= ?';
            $bind[] = $f['from'] . ' 00:00:00';
        }
        if (!empty($f['to'])) {
            $where[] = 'at <= ?';
            $bind[] = $f['to'] . ' 23:59:59';
        }
        if (!empty($f['q'])) {
            $where[] = '(tour_no LIKE ? OR person_name LIKE ? OR reason LIKE ? OR by_user LIKE ?)';
            $q = '%' . $f['q'] . '%';
            array_push($bind, $q, $q, $q, $q);
        }
        $rows = $this->db->query('SELECT * FROM tf_rot_log WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 500', $bind)->getResultArray();
        foreach ($rows as &$r) {
            $r['details'] = $r['details'] ? json_decode($r['details'], true) : null;
        }
        return $rows;
    }
}
