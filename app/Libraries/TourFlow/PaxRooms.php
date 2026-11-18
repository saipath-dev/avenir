<?php

namespace App\Libraries\TourFlow;

/**
 * tf-paxrooms-r1 (19 Nov 2026, Shantha's Passenger Management mock-up) — passengers, the room requirements of each hotel
 * stay, the rooms and who shares them.
 *
 * Kept apart on purpose:
 *   tf_intake_pax   the passengers (name; type, nationality, passport, gender, date of birth — all optional)
 *   tf_room_req     what staff entered for a hotel stay: Room Type + Room Category + number of rooms (names not needed)
 *   tf_room         the room entries of a hotel stay (R01 …), each with its own type and optional category
 *   tf_room_pax     who is in which room of which stay — a passenger may share differently at every hotel
 *   tf_room_log     every change, who and when
 *
 * Nothing here writes the inquiry's Single/Double/Twin/Triple counts, a quotation, a price or a hotel booking: differences
 * are flagged for review only. Categories come from the stay hotel's own room types in the Hotel Rate Master.
 */
final class PaxRooms
{
    public const TYPES = ['SGL' => 'Single', 'DBL' => 'Double', 'TWN' => 'Twin', 'TPL' => 'Triple'];
    public const BEDS  = ['SGL' => 1, 'DBL' => 2, 'TWN' => 2, 'TPL' => 3];
    public const GENERAL = ['Standard', 'Superior', 'Deluxe', 'Luxury', 'Suite'];
    private const INQ = ['SGL' => 'rooms_sgl', 'DBL' => 'rooms_dbl', 'TWN' => 'rooms_twn', 'TPL' => 'rooms_tpl'];

    private static bool $ensured = false;
    private $db;
    private string $user;

    public function __construct($db, string $user = '')
    {
        $this->db = $db;
        $this->user = $user !== '' ? $user : 'system';
        self::ensure($db);
    }

    public static function ensure($db): void
    {
        if (self::$ensured) {
            return;
        }
        self::$ensured = true;
        try {
            $have = array_column($db->query('SHOW COLUMNS FROM tf_intake_pax')->getResultArray(), 'Field');
            foreach ([
                'pax_type'    => "ADD COLUMN `pax_type` VARCHAR(10) NULL",
                'nationality' => "ADD COLUMN `nationality` VARCHAR(60) NULL",
                'passport_no' => "ADD COLUMN `passport_no` VARCHAR(40) NULL",
                'gender'      => "ADD COLUMN `gender` CHAR(1) NULL",
                'dob'         => "ADD COLUMN `dob` DATE NULL",
            ] as $c => $sql) {
                if (!in_array($c, $have, true)) {
                    $db->query('ALTER TABLE `tf_intake_pax` ' . $sql);
                }
            }
            $db->query("CREATE TABLE IF NOT EXISTS `tf_room_req` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `intake_id` INT UNSIGNED NOT NULL, `stay_key` VARCHAR(60) NOT NULL,
                `room_type` CHAR(4) NOT NULL, `category` VARCHAR(160) NULL, `rooms` SMALLINT UNSIGNED NOT NULL DEFAULT 0, `sort_order` SMALLINT NOT NULL DEFAULT 0,
                `updated_at` DATETIME NULL, `updated_by` VARCHAR(100) NULL, PRIMARY KEY (`id`), KEY `k_stay` (`intake_id`, `stay_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->query("CREATE TABLE IF NOT EXISTS `tf_room` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `intake_id` INT UNSIGNED NOT NULL, `stay_key` VARCHAR(60) NOT NULL,
                `room_no` VARCHAR(12) NOT NULL, `room_type` CHAR(4) NOT NULL, `category` VARCHAR(160) NULL, `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` DATETIME NULL, `created_by` VARCHAR(100) NULL, PRIMARY KEY (`id`), KEY `k_stay` (`intake_id`, `stay_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->query("CREATE TABLE IF NOT EXISTS `tf_room_pax` (`room_id` INT UNSIGNED NOT NULL, `pax_id` INT UNSIGNED NOT NULL, PRIMARY KEY (`room_id`, `pax_id`), KEY `k_pax` (`pax_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->query("CREATE TABLE IF NOT EXISTS `tf_room_log` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `intake_id` INT UNSIGNED NOT NULL, `stay_key` VARCHAR(60) NULL,
                `at` DATETIME NOT NULL, `by` VARCHAR(100) NULL, `action` VARCHAR(40) NOT NULL, `detail` VARCHAR(600) NULL, PRIMARY KEY (`id`), KEY `k_intake` (`intake_id`, `stay_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {
            log_message('error', 'PaxRooms::ensure ' . $e->getMessage());
        }
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function rows(string $sql, array $b = []): array
    {
        return $this->db->query($sql, $b)->getResultArray();
    }

    private function log(int $intakeId, ?string $stay, string $action, string $detail): void
    {
        $this->db->query('INSERT INTO tf_room_log (intake_id, stay_key, `at`, `by`, action, detail) VALUES (?,?,?,?,?,?)', [$intakeId, $stay, $this->now(), $this->user, $action, mb_substr($detail, 0, 600)]);
    }

    private static function identity($hotelId, $name): string
    {
        return !empty($hotelId) ? 'h' . (int) $hotelId : 'n' . substr(md5(mb_strtolower(trim((string) $name))), 0, 10);
    }

    /** The hotel stays of the inquiry, in order: consecutive nights at the same hotel are one stay. */
    public function stays(int $intakeId): array
    {
        $days = $this->rows('SELECT d.day_no, d.day_date, d.hotel_id, d.hotel_name_raw, d.meal_plan, h.name AS hname FROM tf_intake_day d LEFT JOIN hr2_hotel h ON h.id = d.hotel_id WHERE d.intake_id = ? ORDER BY d.day_no', [$intakeId]);
        $out = [];
        $seen = [];
        $last = null;
        foreach ($days as $d) {
            $name = trim((string) ($d['hname'] ?: $d['hotel_name_raw']));
            if ($name === '' && empty($d['hotel_id'])) {
                $last = null;
                continue;
            }
            $idn = self::identity($d['hotel_id'], $name);
            if ($last !== null && $out[$last]['idn'] === $idn && (int) $out[$last]['day_to'] === (int) $d['day_no'] - 1) {
                $out[$last]['day_to'] = (int) $d['day_no'];
                $out[$last]['nights']++;
                $out[$last]['check_out'] = $d['day_date'] ? date('Y-m-d', strtotime($d['day_date'] . ' +1 day')) : null;
                continue;
            }
            $seen[$idn] = ($seen[$idn] ?? 0) + 1;
            $out[] = ['key' => $idn . '-' . $seen[$idn], 'idn' => $idn, 'hotel_id' => !empty($d['hotel_id']) ? (int) $d['hotel_id'] : null, 'hotel' => $name,
                'day_from' => (int) $d['day_no'], 'day_to' => (int) $d['day_no'], 'nights' => 1, 'meal_plan' => $d['meal_plan'],
                'check_in' => $d['day_date'], 'check_out' => $d['day_date'] ? date('Y-m-d', strtotime($d['day_date'] . ' +1 day')) : null];
            $last = count($out) - 1;
        }
        return $out;
    }

    /** The room categories of a hotel, from the Hotel Rate Master; the general list only where the hotel has none there. */
    public function categories(?int $hotelId): array
    {
        $names = [];
        if ($hotelId) {
            foreach ($this->rows("SELECT name FROM hr2_room_type WHERE hotel_id = ? AND status = 'active' ORDER BY sort_order, name", [$hotelId]) as $r) {
                $names[] = trim((string) $r['name']);
            }
        }
        $names = array_values(array_unique(array_filter($names)));
        return ['list' => $names ?: self::GENERAL, 'from_master' => (bool) $names];
    }

    private function intake(int $intakeId): array
    {
        $r = $this->db->query('SELECT id, inquiry_no, status, archived_at, pax_total, pax_adults, pax_children, rooms_sgl, rooms_dbl, rooms_twn, rooms_tpl FROM tf_intake WHERE id = ?', [$intakeId])->getRowArray();
        if (!$r) {
            throw new \InvalidArgumentException('Inquiry not found.');
        }
        return $r;
    }

    private function tour(int $intakeId): ?array
    {
        try {
            return $this->db->query('SELECT id, tour_no, stage FROM tf_tour WHERE intake_id = ? ORDER BY id DESC LIMIT 1', [$intakeId])->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Editable to the end of the tour: not on an archived or cancelled inquiry, nor once the tour is completed. */
    public function lockedWhy(int $intakeId): ?string
    {
        $i = $this->intake($intakeId);
        if (!empty($i['archived_at'])) {
            return 'This inquiry is archived and read-only.';
        }
        if ((string) $i['status'] === 'cancelled') {
            return 'This tour was cancelled; its passengers and rooms are read-only.';
        }
        $t = $this->tour($intakeId);
        if ($t && in_array(strtolower((string) $t['stage']), ['completed', 'complete', 'closed', 'finished', 'cancelled'], true)) {
            return 'This tour is completed; its passengers and rooms are read-only.';
        }
        return null;
    }

    private function assertEditable(int $intakeId): void
    {
        $why = $this->lockedWhy($intakeId);
        if ($why !== null) {
            throw new \RuntimeException($why);
        }
    }

    private function stay(int $intakeId, string $key): array
    {
        foreach ($this->stays($intakeId) as $s) {
            if ($s['key'] === $key) {
                return $s;
            }
        }
        throw new \InvalidArgumentException('That hotel stay is not on this inquiry.');
    }

    private function cleanCategory(array $stay, $cat): ?string
    {
        $cat = trim((string) $cat);
        if ($cat === '') {
            return null;
        }
        foreach ($this->categories($stay['hotel_id'])['list'] as $c) {
            if (mb_strtolower($c) === mb_strtolower($cat)) {
                return $c;
            }
        }
        throw new \InvalidArgumentException('"' . $cat . '" is not a room category of ' . $stay['hotel'] . ' in the Hotel Rate Master.');
    }

    private static function type($t): string
    {
        $t = strtoupper(trim((string) $t));
        $t = ['SINGLE' => 'SGL', 'DOUBLE' => 'DBL', 'TWIN' => 'TWN', 'TRIPLE' => 'TPL'][$t] ?? $t;
        if (!isset(self::TYPES[$t])) {
            throw new \InvalidArgumentException('Choose the room type: Single, Double, Twin or Triple.');
        }
        return $t;
    }

    /** The hotel bookings (jobs) of the confirmed tour, by stay key. */
    private function jobs(int $intakeId): array
    {
        $t = $this->tour($intakeId);
        if (!$t) {
            return [];
        }
        $out = [];
        $seen = [];
        try {
            foreach ($this->rows("SELECT id, hotel_id, hotel_name, status, requested_at, confirmed_at, confirmation_ref, rooms_note, room_type_text FROM tf_hotel_job WHERE tour_id = ? AND archived_at IS NULL AND status <> 'cancelled' ORDER BY day_from, seq, id", [(int) $t['id']]) as $j) {
                $idn = self::identity($j['hotel_id'], $j['hotel_name']);
                $seen[$idn] = ($seen[$idn] ?? 0) + 1;
                $out[$idn . '-' . $seen[$idn]] = $j;
            }
        } catch (\Throwable $e) {
            return [];
        }
        return $out;
    }

    public function stayKeyOfJob(int $intakeId, int $jobId): ?string
    {
        foreach ($this->jobs($intakeId) as $k => $j) {
            if ((int) $j['id'] === $jobId) {
                return $k;
            }
        }
        return null;
    }

    private static function norm(string $n): string
    {
        return preg_replace('/[^A-Z0-9]+/', '', strtoupper($n)) ?? '';
    }

    /** Everything the Passengers tab shows. */
    public function data(int $intakeId): array
    {
        $inq = $this->intake($intakeId);
        // allocations of passengers who are no longer on the inquiry go with them
        $this->db->query('DELETE rp FROM tf_room_pax rp JOIN tf_room r ON r.id = rp.room_id LEFT JOIN tf_intake_pax p ON p.id = rp.pax_id WHERE r.intake_id = ? AND p.id IS NULL', [$intakeId]);
        $pax = $this->rows('SELECT id, full_name, room_type, note, group_id, pax_type, nationality, passport_no, gender, dob FROM tf_intake_pax WHERE intake_id = ? ORDER BY sort_order, id', [$intakeId]);
        $byNorm = [];
        foreach ($pax as $p) {
            $byNorm[self::norm((string) $p['full_name'])][] = (int) $p['id'];
        }
        $dupes = [];
        foreach ($byNorm as $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $i) {
                    $dupes[] = $i;
                }
            }
        }
        $stays = $this->stays($intakeId);
        $keys = array_column($stays, 'key');
        $reqs = [];
        foreach ($this->rows('SELECT id, stay_key, room_type, category, rooms FROM tf_room_req WHERE intake_id = ? ORDER BY sort_order, id', [$intakeId]) as $r) {
            $reqs[$r['stay_key']][] = ['id' => (int) $r['id'], 'room_type' => $r['room_type'], 'category' => $r['category'], 'rooms' => (int) $r['rooms']];
        }
        $rooms = [];
        $alloc = [];
        foreach ($this->rows('SELECT rp.room_id, rp.pax_id FROM tf_room_pax rp JOIN tf_room r ON r.id = rp.room_id WHERE r.intake_id = ?', [$intakeId]) as $a) {
            $alloc[(int) $a['room_id']][] = (int) $a['pax_id'];
        }
        foreach ($this->rows('SELECT id, stay_key, room_no, room_type, category FROM tf_room WHERE intake_id = ? ORDER BY sort_order, id', [$intakeId]) as $r) {
            $rooms[$r['stay_key']][] = ['id' => (int) $r['id'], 'room_no' => $r['room_no'], 'room_type' => $r['room_type'], 'category' => $r['category'], 'pax' => $alloc[(int) $r['id']] ?? []];
        }
        $last = [];
        foreach ($this->rows('SELECT stay_key, MAX(`at`) m FROM tf_room_log WHERE intake_id = ? AND stay_key IS NOT NULL GROUP BY stay_key', [$intakeId]) as $l) {
            $last[$l['stay_key']] = $l['m'];
        }
        $jobs = $this->jobs($intakeId);
        $ptype = [];
        foreach ($pax as $p) {
            $ptype[(int) $p['id']] = strtolower((string) ($p['pax_type'] ?: 'adult'));
        }
        $priced = [];
        foreach (self::INQ as $t => $col) {
            $priced[$t] = (int) $inq[$col];
        }
        foreach ($stays as &$s) {
            $k = $s['key'];
            $s['categories'] = $this->categories($s['hotel_id']);
            $s['reqs'] = $reqs[$k] ?? [];
            $s['rooms'] = $rooms[$k] ?? [];
            $s['changed_at'] = $last[$k] ?? null;
            $j = $jobs[$k] ?? null;
            $s['booking'] = $j ? ['job_id' => (int) $j['id'], 'status' => $j['status'], 'requested_at' => $j['requested_at'], 'confirmed_at' => $j['confirmed_at'], 'ref' => $j['confirmation_ref'], 'rooms_note' => $j['rooms_note'], 'room_type_text' => $j['room_type_text'] ?? null] : null;
            $s['booking_differs'] = $j ? self::differsFromBooking($s, $j) : false;
            $s['flags'] = $this->flags($s, $priced, $pax, $ptype);
            $ref = $j ? ($j['confirmed_at'] ?: $j['requested_at']) : null;
            $s['state'] = !$j || !$ref ? 'requested' : ($s['booking_differs'] ? 'amend' : (!empty($j['confirmed_at']) ? 'confirmed' : 'sent'));
        }
        unset($s);
        // arrangements whose hotel is no longer on the itinerary: kept, shown for review
        $orphans = [];
        foreach (array_unique(array_merge(array_keys($reqs), array_keys($rooms))) as $k) {
            if (!in_array($k, $keys, true)) {
                $orphans[] = ['key' => $k, 'reqs' => $reqs[$k] ?? [], 'rooms' => count($rooms[$k] ?? [])];
            }
        }
        return [
            'inquiry' => ['id' => (int) $inq['id'], 'inquiry_no' => $inq['inquiry_no'], 'status' => $inq['status'], 'pax_total' => (int) $inq['pax_total'], 'pax_adults' => $inq['pax_adults'], 'pax_children' => $inq['pax_children'], 'priced' => $priced],
            'tour' => $this->tour($intakeId),
            'locked' => $this->lockedWhy($intakeId),
            'types' => self::TYPES, 'beds' => self::BEDS,
            'passengers' => $pax, 'dupes' => $dupes, 'stays' => $stays, 'orphans' => $orphans,
            'history' => $this->rows('SELECT stay_key, `at`, `by`, action, detail FROM tf_room_log WHERE intake_id = ? ORDER BY id DESC LIMIT 60', [$intakeId]),
        ];
    }

    private function flags(array $s, array $priced, array $pax, array $ptype): array
    {
        $f = [];
        $word = static fn (string $t, ?string $c) => self::TYPES[$t] . ($c ? ' ' . $c : '');
        $reqT = [];
        $reqTC = [];
        foreach ($s['reqs'] as $r) {
            $reqT[$r['room_type']] = ($reqT[$r['room_type']] ?? 0) + $r['rooms'];
            $reqTC[$r['room_type'] . '|' . mb_strtolower((string) $r['category'])] = ['n' => ($reqTC[$r['room_type'] . '|' . mb_strtolower((string) $r['category'])]['n'] ?? 0) + $r['rooms'], 'w' => $word($r['room_type'], $r['category'])];
        }
        $roomT = [];
        $roomTC = [];
        foreach ($s['rooms'] as $r) {
            $roomT[$r['room_type']] = ($roomT[$r['room_type']] ?? 0) + 1;
            $k = $r['room_type'] . '|' . mb_strtolower((string) $r['category']);
            $roomTC[$k] = ['n' => ($roomTC[$k]['n'] ?? 0) + 1, 'w' => $word($r['room_type'], $r['category'])];
        }
        $basis = $s['reqs'] ? $reqT : $roomT;
        if (($s['reqs'] || $s['rooms']) && array_sum($priced) > 0) {
            $diff = [];
            foreach (self::TYPES as $t => $w) {
                if (($basis[$t] ?? 0) !== ($priced[$t] ?? 0)) {
                    $diff[] = $w . ' ' . ($basis[$t] ?? 0) . ' (quotation ' . ($priced[$t] ?? 0) . ')';
                }
            }
            if ($diff) {
                $f[] = ['kind' => 'price', 'level' => 'warn', 'text' => 'Rooms differ from the rooms the quotation is priced on: ' . implode(', ', $diff) . '. Price difference to review — the quotation is not changed.'];
            }
        }
        if ($s['reqs'] && $s['rooms']) {
            $d = [];
            foreach ($reqTC + $roomTC as $k => $x) {
                $a = $roomTC[$k]['n'] ?? 0;
                $b = $reqTC[$k]['n'] ?? 0;
                if ($a !== $b) {
                    $d[] = $x['w'] . ': ' . $a . ' room' . ($a === 1 ? '' : 's') . ' entered, ' . $b . ' required';
                }
            }
            if ($d) {
                $f[] = ['kind' => 'req', 'level' => 'warn', 'text' => 'Rooms differ from the room requirements — ' . implode('; ', $d) . '.'];
            }
        }
        $in = [];
        $under = [];
        $over = [];
        foreach ($s['rooms'] as $r) {
            $n = 0;
            foreach ($r['pax'] as $pid) {
                $in[$pid] = true;
                if (($ptype[$pid] ?? 'adult') !== 'infant') {
                    $n++;
                }
            }
            $beds = self::BEDS[$r['room_type']];
            if ($n > $beds) {
                $over[] = $r['room_no'];
            } elseif ($n < $beds && $n > 0) {
                $under[] = $r['room_no'];
            }
        }
        if ($over) {
            $f[] = ['kind' => 'over', 'level' => 'bad', 'text' => 'Over capacity: ' . implode(', ', $over) . '.'];
        }
        if ($under) {
            $f[] = ['kind' => 'under', 'level' => 'warn', 'text' => 'Not full: ' . implode(', ', $under) . '.'];
        }
        if ($s['rooms']) {
            $un = count($pax) - count($in);
            if ($un > 0) {
                $f[] = ['kind' => 'unalloc', 'level' => 'warn', 'text' => $un . ' passenger' . ($un === 1 ? '' : 's') . ' not yet in a room at this hotel.'];
            }
        }
        $j = $s['booking'];
        $ref = $j ? ($j['confirmed_at'] ?: $j['requested_at']) : null;
        if ($j && $ref && !empty($s['booking_differs'])) {
            $f[] = ['kind' => 'booking', 'level' => 'bad', 'text' => 'Differs from the hotel booking ' . (!empty($j['confirmed_at']) ? 'confirmed' : 'requested') . ' on ' . date('j M Y', strtotime($ref))
                . ' (' . trim((string) $j['rooms_note']) . ')' . ($s['changed_at'] ? ', last changed ' . date('j M Y H:i', strtotime($s['changed_at'])) : '') . '. Booking amendment to review — the hotel booking is not changed automatically.'];
        }
        return $f;
    }

    /** Does the arrangement staff entered differ from what the hotel booking carries (its room mix and room type)? */
    private static function differsFromBooking(array $s, array $j): bool
    {
        $src = $s['rooms'] ?: $s['reqs'];
        if (!$src) {
            return false;
        }
        $mine = [];
        $cats = [];
        foreach ($src as $r) {
            $mine[$r['room_type']] = ($mine[$r['room_type']] ?? 0) + (isset($r['rooms']) ? (int) $r['rooms'] : 1);
            $cats[mb_strtolower(trim((string) $r['category']))] = true;
        }
        [$mix, $typeFromNote] = RoomingList::mix($j['rooms_note'] ?? null);
        $theirs = [];
        foreach ($mix as [$c, $n]) {
            $theirs[$c] = ($theirs[$c] ?? 0) + $n;
        }
        ksort($mine);
        ksort($theirs);
        if ($mine !== $theirs) {
            return true;
        }
        $type = mb_strtolower(trim((string) (($j['room_type_text'] ?? '') ?: $typeFromNote)));
        unset($cats['']);
        return $cats && array_keys($cats) !== [$type];
    }

    // ---------- room requirements ----------

    /** Replace the requirement rows of one stay: [[room_type, category, rooms]]. */
    public function saveReqs(int $intakeId, string $key, array $rows): void
    {
        $this->assertEditable($intakeId);
        $stay = $this->stay($intakeId, $key);
        $clean = [];
        foreach ($rows as $r) {
            $n = (int) ($r['rooms'] ?? 0);
            if ($n <= 0) {
                continue;
            }
            if ($n > 500) {
                throw new \InvalidArgumentException('The number of rooms looks wrong.');
            }
            $t = self::type($r['room_type'] ?? '');
            $c = $this->cleanCategory($stay, $r['category'] ?? '');
            $k = $t . '|' . mb_strtolower((string) $c);
            $clean[$k] = ['t' => $t, 'c' => $c, 'n' => ($clean[$k]['n'] ?? 0) + $n];
        }
        $old = $this->describeReqs($this->rows('SELECT room_type, category, rooms FROM tf_room_req WHERE intake_id = ? AND stay_key = ? ORDER BY sort_order, id', [$intakeId, $key]));
        $this->db->query('DELETE FROM tf_room_req WHERE intake_id = ? AND stay_key = ?', [$intakeId, $key]);
        $i = 0;
        foreach ($clean as $c) {
            $this->db->query('INSERT INTO tf_room_req (intake_id, stay_key, room_type, category, rooms, sort_order, updated_at, updated_by) VALUES (?,?,?,?,?,?,?,?)', [$intakeId, $key, $c['t'], $c['c'], $c['n'], $i++, $this->now(), $this->user]);
        }
        $new = $this->describeReqs(array_map(static fn ($c) => ['room_type' => $c['t'], 'category' => $c['c'], 'rooms' => $c['n']], array_values($clean)));
        if ($old !== $new) {
            $this->log($intakeId, $key, 'requirements', $stay['hotel'] . ': ' . ($old ?: 'none') . ' → ' . ($new ?: 'none'));
        }
    }

    private function describeReqs(array $rows): string
    {
        return implode(', ', array_map(static fn ($r) => (int) $r['rooms'] . ' ' . self::TYPES[$r['room_type']] . ($r['category'] ? ' ' . $r['category'] : ''), $rows));
    }

    private function nextNo(int $intakeId, string $key): int
    {
        $max = 0;
        foreach ($this->rows('SELECT room_no FROM tf_room WHERE intake_id = ? AND stay_key = ?', [$intakeId, $key]) as $r) {
            if (preg_match('/(\d+)$/', (string) $r['room_no'], $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        return $max + 1;
    }

    /** Room entries from the entered requirements. Only the missing ones are added, so repeating it adds nothing twice. */
    public function createRooms(int $intakeId, string $key): int
    {
        $this->assertEditable($intakeId);
        $stay = $this->stay($intakeId, $key);
        $reqs = $this->rows('SELECT room_type, category, rooms FROM tf_room_req WHERE intake_id = ? AND stay_key = ? ORDER BY sort_order, id', [$intakeId, $key]);
        if (!$reqs) {
            throw new \InvalidArgumentException('Enter the room requirements of ' . $stay['hotel'] . ' first (room type, category and number of rooms).');
        }
        $have = [];
        foreach ($this->rows('SELECT room_type, category FROM tf_room WHERE intake_id = ? AND stay_key = ?', [$intakeId, $key]) as $r) {
            $k = $r['room_type'] . '|' . mb_strtolower((string) $r['category']);
            $have[$k] = ($have[$k] ?? 0) + 1;
        }
        $no = $this->nextNo($intakeId, $key);
        $made = 0;
        foreach ($reqs as $r) {
            $k = $r['room_type'] . '|' . mb_strtolower((string) $r['category']);
            for ($i = $have[$k] ?? 0; $i < (int) $r['rooms']; $i++) {
                $this->db->query('INSERT INTO tf_room (intake_id, stay_key, room_no, room_type, category, sort_order, created_at, created_by) VALUES (?,?,?,?,?,?,?,?)',
                    [$intakeId, $key, 'R' . str_pad((string) $no, 2, '0', STR_PAD_LEFT), $r['room_type'], $r['category'], $no, $this->now(), $this->user]);
                $no++;
                $made++;
            }
        }
        if ($made) {
            $this->log($intakeId, $key, 'rooms created', $stay['hotel'] . ': ' . $made . ' room entr' . ($made === 1 ? 'y' : 'ies') . ' from the requirements');
        }
        return $made;
    }

    private function room(int $intakeId, int $roomId): array
    {
        $r = $this->db->query('SELECT * FROM tf_room WHERE id = ? AND intake_id = ?', [$roomId, $intakeId])->getRowArray();
        if (!$r) {
            throw new \InvalidArgumentException('That room is not on this inquiry.');
        }
        return $r;
    }

    /** Add a room to a stay, or change one: number, type, category. */
    public function saveRoom(int $intakeId, string $key, ?int $roomId, array $p): int
    {
        $this->assertEditable($intakeId);
        $stay = $this->stay($intakeId, $key);
        $t = self::type($p['room_type'] ?? '');
        $c = $this->cleanCategory($stay, $p['category'] ?? '');
        $no = strtoupper(trim((string) ($p['room_no'] ?? '')));
        if ($roomId) {
            $old = $this->room($intakeId, $roomId);
            $no = $no !== '' ? mb_substr($no, 0, 12) : $old['room_no'];
            if ($this->db->query('SELECT id FROM tf_room WHERE intake_id = ? AND stay_key = ? AND room_no = ? AND id <> ?', [$intakeId, $old['stay_key'], $no, $roomId])->getRowArray()) {
                throw new \InvalidArgumentException('Room ' . $no . ' is already on this hotel stay.');
            }
            $this->db->query('UPDATE tf_room SET room_no = ?, room_type = ?, category = ? WHERE id = ?', [$no, $t, $c, $roomId]);
            $was = $old['room_no'] . ' ' . self::TYPES[$old['room_type']] . ($old['category'] ? ' ' . $old['category'] : '');
            $is = $no . ' ' . self::TYPES[$t] . ($c ? ' ' . $c : '');
            if ($was !== $is) {
                $this->log($intakeId, $old['stay_key'], 'room changed', $stay['hotel'] . ': ' . $was . ' → ' . $is);
            }
            return $roomId;
        }
        $n = $this->nextNo($intakeId, $key);
        $no = $no !== '' ? mb_substr($no, 0, 12) : 'R' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
        if ($this->db->query('SELECT id FROM tf_room WHERE intake_id = ? AND stay_key = ? AND room_no = ?', [$intakeId, $key, $no])->getRowArray()) {
            throw new \InvalidArgumentException('Room ' . $no . ' is already on this hotel stay.');
        }
        $this->db->query('INSERT INTO tf_room (intake_id, stay_key, room_no, room_type, category, sort_order, created_at, created_by) VALUES (?,?,?,?,?,?,?,?)', [$intakeId, $key, $no, $t, $c, $n, $this->now(), $this->user]);
        $id = (int) $this->db->insertID();
        $this->log($intakeId, $key, 'room added', $stay['hotel'] . ': ' . $no . ' ' . self::TYPES[$t] . ($c ? ' ' . $c : ''));
        return $id;
    }

    public function deleteRoom(int $intakeId, int $roomId): void
    {
        $this->assertEditable($intakeId);
        $r = $this->room($intakeId, $roomId);
        $this->db->query('DELETE FROM tf_room_pax WHERE room_id = ?', [$roomId]);
        $this->db->query('DELETE FROM tf_room WHERE id = ?', [$roomId]);
        $this->log($intakeId, $r['stay_key'], 'room removed', $r['room_no'] . ' ' . self::TYPES[$r['room_type']] . ($r['category'] ? ' ' . $r['category'] : ''));
    }

    /** The same category on several rooms of one stay. */
    public function roomsCategory(int $intakeId, string $key, array $roomIds, $category): int
    {
        $this->assertEditable($intakeId);
        $stay = $this->stay($intakeId, $key);
        $c = $this->cleanCategory($stay, $category);
        $n = 0;
        $nos = [];
        foreach (array_unique(array_map('intval', $roomIds)) as $id) {
            $r = $this->room($intakeId, $id);
            if ($r['stay_key'] !== $key) {
                continue;
            }
            $this->db->query('UPDATE tf_room SET category = ? WHERE id = ?', [$c, $id]);
            $nos[] = $r['room_no'];
            $n++;
        }
        if ($n) {
            $this->log($intakeId, $key, 'category changed', $stay['hotel'] . ': ' . implode(', ', $nos) . ' → ' . ($c ?: 'no category'));
        }
        return $n;
    }

    /** Put passengers in a room of a stay (room 0: out of their room at this stay). Sharing is whatever staff choose. */
    public function assign(int $intakeId, string $key, int $roomId, array $paxIds): void
    {
        $this->assertEditable($intakeId);
        $stay = $this->stay($intakeId, $key);
        $ids = array_values(array_unique(array_filter(array_map('intval', $paxIds))));
        if (!$ids) {
            throw new \InvalidArgumentException('Choose the passengers first.');
        }
        $names = [];
        foreach ($ids as $pid) {
            $p = $this->db->query('SELECT full_name FROM tf_intake_pax WHERE id = ? AND intake_id = ?', [$pid, $intakeId])->getRowArray();
            if (!$p) {
                throw new \InvalidArgumentException('A chosen passenger is not on this inquiry.');
            }
            $names[] = $p['full_name'];
        }
        $room = $roomId ? $this->room($intakeId, $roomId) : null;
        if ($room && $room['stay_key'] !== $key) {
            throw new \InvalidArgumentException('That room belongs to another hotel stay.');
        }
        $in = implode(',', $ids);
        $this->db->query("DELETE rp FROM tf_room_pax rp JOIN tf_room r ON r.id = rp.room_id WHERE r.intake_id = ? AND r.stay_key = ? AND rp.pax_id IN ($in)", [$intakeId, $key]);
        if ($room) {
            foreach ($ids as $pid) {
                $this->db->query('INSERT IGNORE INTO tf_room_pax (room_id, pax_id) VALUES (?,?)', [$roomId, $pid]);
            }
        }
        // a name staff have placed in a room is theirs: reading the agent's e-mail again must not replace it
        $this->db->query("UPDATE tf_intake_pax SET origin = 'human' WHERE intake_id = ? AND id IN ($in)", [$intakeId]);
        $this->log($intakeId, $key, $room ? 'allocated' : 'unallocated', $stay['hotel'] . ': ' . implode(', ', $names) . ($room ? ' → ' . $room['room_no'] : ' out of their room'));
    }

    /** The arrangement of one hotel copied to another that has none yet (a category is kept only where that hotel has it). */
    public function copyStay(int $intakeId, string $from, string $to): void
    {
        $this->assertEditable($intakeId);
        $a = $this->stay($intakeId, $from);
        $b = $this->stay($intakeId, $to);
        if ($this->db->query('SELECT 1 FROM tf_room WHERE intake_id = ? AND stay_key = ? LIMIT 1', [$intakeId, $to])->getRowArray()
            || $this->db->query('SELECT 1 FROM tf_room_req WHERE intake_id = ? AND stay_key = ? LIMIT 1', [$intakeId, $to])->getRowArray()) {
            throw new \RuntimeException($b['hotel'] . ' already has room requirements or rooms. Nothing was overwritten.');
        }
        $cats = array_map('mb_strtolower', $this->categories($b['hotel_id'])['list']);
        $keep = fn ($c) => $c !== null && in_array(mb_strtolower((string) $c), $cats, true) ? $this->cleanCategory($b, $c) : null;
        $merged = [];
        foreach ($this->rows('SELECT room_type, category, rooms FROM tf_room_req WHERE intake_id = ? AND stay_key = ? ORDER BY sort_order, id', [$intakeId, $from]) as $r) {
            $c = $keep($r['category']);
            $k = $r['room_type'] . '|' . mb_strtolower((string) $c);
            $merged[$k] = ['t' => $r['room_type'], 'c' => $c, 'n' => ($merged[$k]['n'] ?? 0) + (int) $r['rooms']];
        }
        $i = 0;
        foreach ($merged as $m) {
            $this->db->query('INSERT INTO tf_room_req (intake_id, stay_key, room_type, category, rooms, sort_order, updated_at, updated_by) VALUES (?,?,?,?,?,?,?,?)', [$intakeId, $to, $m['t'], $m['c'], $m['n'], $i++, $this->now(), $this->user]);
        }
        foreach ($this->rows('SELECT id, room_no, room_type, category, sort_order FROM tf_room WHERE intake_id = ? AND stay_key = ? ORDER BY sort_order, id', [$intakeId, $from]) as $r) {
            $this->db->query('INSERT INTO tf_room (intake_id, stay_key, room_no, room_type, category, sort_order, created_at, created_by) VALUES (?,?,?,?,?,?,?,?)', [$intakeId, $to, $r['room_no'], $r['room_type'], $keep($r['category']), $r['sort_order'], $this->now(), $this->user]);
            $new = (int) $this->db->insertID();
            $this->db->query('INSERT INTO tf_room_pax (room_id, pax_id) SELECT ?, pax_id FROM tf_room_pax WHERE room_id = ?', [$new, (int) $r['id']]);
        }
        $this->log($intakeId, $to, 'copied', $b['hotel'] . ': arrangement copied from ' . $a['hotel'] . ' (categories kept only where this hotel has them)');
    }

    // ---------- passengers ----------

    private static function cut($v, int $n): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : mb_substr($v, 0, $n);
    }

    private static function paxFields(array $p): array
    {
        $type = strtolower(trim((string) ($p['pax_type'] ?? '')));
        $type = in_array($type, ['adult', 'child', 'infant'], true) ? $type : ($type === '' ? null : 'adult');
        $g = strtoupper(substr(trim((string) ($p['gender'] ?? '')), 0, 1));
        $dob = trim((string) ($p['dob'] ?? ''));
        $ts = $dob !== '' ? strtotime($dob) : false;
        if ($dob !== '' && $ts === false) {
            throw new \InvalidArgumentException('The date of birth "' . $dob . '" is not a date.');
        }
        return [$type, self::cut($p['nationality'] ?? null, 60), self::cut($p['passport_no'] ?? null, 40), in_array($g, ['M', 'F'], true) ? $g : null, $ts ? date('Y-m-d', $ts) : null];
    }

    /** Add a passenger or change one. Only the name is required; passport details are never demanded. */
    public function savePax(int $intakeId, ?int $paxId, array $p): int
    {
        $this->assertEditable($intakeId);
        $name = self::cut($p['full_name'] ?? null, 160);
        if ($name === null) {
            throw new \InvalidArgumentException('A passenger needs a name.');
        }
        [$type, $nat, $pp, $g, $dob] = self::paxFields($p);
        if ($paxId) {
            $old = $this->db->query('SELECT full_name FROM tf_intake_pax WHERE id = ? AND intake_id = ?', [$paxId, $intakeId])->getRowArray();
            if (!$old) {
                throw new \InvalidArgumentException('That passenger is not on this inquiry.');
            }
            $this->db->query("UPDATE tf_intake_pax SET full_name = ?, pax_type = ?, nationality = ?, passport_no = ?, gender = ?, dob = ?, origin = 'human' WHERE id = ?", [$name, $type, $nat, $pp, $g, $dob, $paxId]);
            $this->log($intakeId, null, 'passenger changed', $old['full_name'] === $name ? $name . ': details updated' : $old['full_name'] . ' → ' . $name);
        } else {
            $next = (int) ($this->db->query('SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM tf_intake_pax WHERE intake_id = ?', [$intakeId])->getRowArray()['n'] ?? 0);
            $this->db->query("INSERT INTO tf_intake_pax (intake_id, full_name, pax_type, nationality, passport_no, gender, dob, origin, sort_order) VALUES (?,?,?,?,?,?,?,'human',?)", [$intakeId, $name, $type, $nat, $pp, $g, $dob, $next]);
            $paxId = (int) $this->db->insertID();
            $this->log($intakeId, null, 'passenger added', $name);
        }
        $this->db->query('UPDATE tf_intake SET updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $intakeId]);
        return $paxId;
    }

    public function deletePax(int $intakeId, array $paxIds): int
    {
        $this->assertEditable($intakeId);
        $n = 0;
        $names = [];
        foreach (array_unique(array_map('intval', $paxIds)) as $pid) {
            $p = $this->db->query('SELECT full_name FROM tf_intake_pax WHERE id = ? AND intake_id = ?', [$pid, $intakeId])->getRowArray();
            if (!$p) {
                continue;
            }
            $this->db->query('DELETE rp FROM tf_room_pax rp JOIN tf_room r ON r.id = rp.room_id WHERE r.intake_id = ? AND rp.pax_id = ?', [$intakeId, $pid]);
            $this->db->query('DELETE FROM tf_intake_pax WHERE id = ? AND intake_id = ?', [$pid, $intakeId]);
            $names[] = $p['full_name'];
            $n++;
        }
        if ($n) {
            $this->log($intakeId, null, 'passenger deleted', implode(', ', $names));
            $this->db->query('UPDATE tf_intake SET updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $intakeId]);
        }
        return $n;
    }

    /** The rows staff ticked in the import preview. Matching names are added and flagged for review, never skipped silently. */
    public function importPax(int $intakeId, array $rows): array
    {
        $this->assertEditable($intakeId);
        $added = 0;
        $bad = [];
        foreach ($rows as $i => $r) {
            try {
                if (self::cut($r['full_name'] ?? null, 160) === null) {
                    throw new \InvalidArgumentException('no name');
                }
                $this->savePax($intakeId, null, $r);
                $added++;
            } catch (\Throwable $e) {
                $bad[] = 'Row ' . ($i + 1) . ': ' . $e->getMessage();
            }
        }
        return ['added' => $added, 'errors' => $bad];
    }

    /** A passenger list file (CSV or .xlsx) read into rows; nothing is saved. */
    public static function parseFile(string $path, string $name): array
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $grid = [];
        if ($ext === 'xlsx') {
            if (!class_exists(\ZipArchive::class)) {
                throw new \RuntimeException('This server cannot open .xlsx files; save the list as CSV and import that.');
            }
            $z = new \ZipArchive();
            if ($z->open($path) !== true) {
                throw new \RuntimeException('The Excel file could not be opened.');
            }
            $shared = [];
            $ss = $z->getFromName('xl/sharedStrings.xml');
            if ($ss !== false) {
                $x = simplexml_load_string($ss);
                foreach ($x->si as $si) {
                    $t = (string) $si->t;
                    foreach ($si->r as $run) {
                        $t .= (string) $run->t;
                    }
                    $shared[] = $t;
                }
            }
            $sheet = $z->getFromName('xl/worksheets/sheet1.xml');
            $z->close();
            if ($sheet === false) {
                throw new \RuntimeException('The Excel file has no first sheet.');
            }
            $x = simplexml_load_string($sheet);
            foreach ($x->sheetData->row as $row) {
                $line = [];
                foreach ($row->c as $c) {
                    $ref = (string) $c['r'];
                    $col = 0;
                    foreach (str_split(preg_replace('/\d+/', '', $ref)) as $ch) {
                        $col = $col * 26 + (ord($ch) - 64);
                    }
                    $v = (string) $c->v;
                    if ((string) $c['t'] === 's') {
                        $v = $shared[(int) $v] ?? '';
                    } elseif ((string) $c['t'] === 'inlineStr') {
                        $v = (string) $c->is->t;
                    }
                    $line[$col - 1] = $v;
                }
                if ($line) {
                    $max = max(array_keys($line));
                    $full = [];
                    for ($i = 0; $i <= $max; $i++) {
                        $full[] = $line[$i] ?? '';
                    }
                    $grid[] = $full;
                }
            }
        } elseif (in_array($ext, ['csv', 'txt'], true)) {
            $raw = (string) file_get_contents($path);
            $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
            if (!mb_check_encoding($raw, 'UTF-8')) {
                $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
            }
            $first = strtok($raw, "\n");
            $sep = substr_count((string) $first, ';') > substr_count((string) $first, ',') ? ';' : (substr_count((string) $first, "\t") > substr_count((string) $first, ',') ? "\t" : ',');
            $h = fopen('php://memory', 'w+');
            fwrite($h, $raw);
            rewind($h);
            while (($line = fgetcsv($h, 0, $sep)) !== false) {
                $grid[] = $line;
            }
            fclose($h);
        } else {
            throw new \InvalidArgumentException('Choose an Excel (.xlsx) or CSV file.');
        }
        $grid = array_values(array_filter($grid, static fn ($l) => trim(implode('', array_map('strval', $l))) !== ''));
        if (!$grid) {
            throw new \InvalidArgumentException('The file is empty.');
        }
        // the header row names the columns; without one the first column is the name
        $map = [];
        $head = array_map(static fn ($v) => strtolower(trim((string) $v)), $grid[0]);
        foreach ($head as $i => $hname) {
            foreach (['full_name' => ['name', 'passenger'], 'pax_type' => ['type'], 'nationality' => ['national'], 'passport_no' => ['passport'], 'gender' => ['gender', 'sex'], 'dob' => ['birth', 'dob']] as $f => $words) {
                foreach ($words as $w) {
                    if (!isset($map[$f]) && !in_array($i, $map, true) && str_contains($hname, $w) && !($f === 'passport_no' && str_contains($hname, 'name'))) {
                        $map[$f] = $i;
                    }
                }
            }
        }
        $hasHead = isset($map['full_name']);
        if (!$hasHead) {
            $map = ['full_name' => 0];
        }
        $out = [];
        foreach (array_slice($grid, $hasHead ? 1 : 0) as $l) {
            $row = [];
            foreach ($map as $f => $i) {
                $row[$f] = trim((string) ($l[$i] ?? ''));
            }
            if (($row['dob'] ?? '') !== '' && is_numeric($row['dob'])) {
                $row['dob'] = date('Y-m-d', (int) round(((float) $row['dob'] - 25569) * 86400));   // an Excel date number
            }
            if (($row['full_name'] ?? '') !== '') {
                $out[] = $row;
            }
            if (count($out) >= 1000) {
                break;
            }
        }
        return $out;
    }

    // ---------- the rooming list of a stay, for the rooming-list documents ----------

    /**
     * The rooms of a stay in the shape RoomingList::build() returns, or null where staff have entered nothing for it
     * (the earlier automatic placing then stands). Rooms come from the room entries; where only requirements exist,
     * unnamed rooms stand for them.
     */
    public function rooming(int $intakeId, string $key, string $meal = ''): ?array
    {
        $rooms = $this->rows('SELECT id, room_no, room_type, category FROM tf_room WHERE intake_id = ? AND stay_key = ? ORDER BY sort_order, id', [$intakeId, $key]);
        $out = [];
        $placed = [];
        if ($rooms) {
            $names = [];
            foreach ($this->rows("SELECT rp.room_id, p.id, p.full_name, p.pax_type, p.note FROM tf_room_pax rp JOIN tf_room r ON r.id = rp.room_id JOIN tf_intake_pax p ON p.id = rp.pax_id WHERE r.intake_id = ? AND r.stay_key = ? ORDER BY p.sort_order, p.id", [$intakeId, $key]) as $a) {
                $names[(int) $a['room_id']][] = ['name' => trim((string) $a['full_name']), 'no_bed' => strtolower((string) $a['pax_type']) === 'infant'];
                $placed[(int) $a['id']] = true;
            }
            foreach ($rooms as $r) {
                $out[] = ['code' => $r['room_type'], 'beds' => self::BEDS[$r['room_type']], 'guests' => $names[(int) $r['id']] ?? [], 'meal' => $meal, 'category' => $r['category'], 'room_no' => $r['room_no']];
            }
        } else {
            $n = 1;
            foreach ($this->rows('SELECT room_type, category, rooms FROM tf_room_req WHERE intake_id = ? AND stay_key = ? ORDER BY sort_order, id', [$intakeId, $key]) as $q) {
                for ($i = 0; $i < (int) $q['rooms']; $i++) {
                    $out[] = ['code' => $q['room_type'], 'beds' => self::BEDS[$q['room_type']], 'guests' => [], 'meal' => $meal, 'category' => $q['category'], 'room_no' => 'R' . str_pad((string) $n++, 2, '0', STR_PAD_LEFT)];
                }
            }
        }
        if (!$out) {
            return null;
        }
        $un = [];
        if ($rooms) {
            foreach ($this->rows('SELECT id, full_name FROM tf_intake_pax WHERE intake_id = ? ORDER BY sort_order, id', [$intakeId]) as $p) {
                if (!isset($placed[(int) $p['id']]) && trim((string) $p['full_name']) !== '') {
                    $un[] = trim((string) $p['full_name']);
                }
            }
        }
        $guests = 0;
        $counts = [];
        foreach ($out as $r) {
            $guests += count($r['guests']);
            $w = self::TYPES[$r['code']] . ($r['category'] ? ' ' . $r['category'] : '');
            $counts[$w] = ($counts[$w] ?? 0) + 1;
        }
        return [
            'rooms' => $out, 'unallocated' => $un, 'total_rooms' => count($out), 'total_guests' => $guests + count($un), 'room_type_text' => '',
            'summary' => implode(', ', array_map(static fn ($w, $n) => $n . ' ' . $w, array_keys($counts), $counts)),
            'meals' => array_values(array_filter([$meal])), 'from_allocation' => true,
        ];
    }

    /** For the hotel-booking documents and the Confirmed Tour screen: the staff arrangement of the stay behind a hotel job, if any. */
    public static function roomingForJob($db, int $intakeId, int $jobId, string $meal = ''): ?array
    {
        try {
            $me = new self($db);
            $key = $me->stayKeyOfJob($intakeId, $jobId);
            return $key === null ? null : $me->rooming($intakeId, $key, $meal);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
