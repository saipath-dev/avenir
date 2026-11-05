<?php

namespace App\Libraries\TourFlow;

/**
 * The two programme masters (schema 29), each kept by destination.
 *
 * Free Visits — the normal sightseeing and en-route stops of a place (a photo stop at the
 * Ella Gap, a walk round the Galle Fort ramparts, a spice garden on the way). There is no
 * cost anywhere on a free visit: not a field, not a figure. A free visit put on a day is a
 * no-charge line on the programme, on the itinerary the client reads, never on the costing.
 *
 * Paid Activities & Services — the priced lists the company already keeps, on one screen by
 * destination: entrances, tickets and what is priced a head (the entrance_fee list, USD a
 * head, who settles it — read as it is; its entries are added and changed under Master Files
 * → Entrance Fee, which opens their accounts posting, and only TourFlow's own destination and
 * on/off for an entry are kept here, in tf_fee_meta) and activities and excursions
 * (tf_excursion_rate: per person, per group or per vehicle, with or without transport, with a
 * validity — kept on this screen). A paid item put on a day pulls its rate and goes straight
 * to the costing, as the Entrance & Activities section always has.
 *
 * A destination is a person's word, never worked out (schema 30): an entry of the priced list
 * sits under a destination only once somebody puts it there — its address is shown beside it,
 * never read for a town — and an entry, activity or free visit nobody has placed is "not
 * placed yet". Nothing here is deleted either: taking something off a master switches it off
 * and keeps it, so it can be switched on again, and every change is on the Activity log.
 *
 * Nothing here decides a price for a line: the lists hold the figures, the costing reads them.
 */
final class ProgrammeMasters
{
    public const FREE_KINDS = ['sightseeing', 'stop', 'walk', 'photo', 'other'];
    public const FREE_KIND_WORDS = ['sightseeing' => 'Sightseeing', 'stop' => 'En-route stop', 'walk' => 'Walk', 'photo' => 'Photo stop', 'other' => 'Other'];

    private $db;
    private string $user;
    private ?int $actorId;
    private ?string $actorName;
    private ?bool $available = null;
    private ?bool $feeActive = null;   // whether tf_fee_meta is there
    private ?bool $excLocation = null;

    public function __construct($db, string $user = '', ?int $actorId = null, ?string $actorName = null)
    {
        $this->db        = $db;
        $this->user      = $user;
        $this->actorId   = $actorId;
        $this->actorName = $actorName;
    }

    /** False until the installer has run schema 29. */
    public function available(): bool
    {
        if ($this->available === null) {
            try {
                $this->available = (bool) $this->db->query("SHOW TABLES LIKE 'tf_free_visit'")->getResultArray();
            } catch (\Throwable $e) {
                $this->available = false;
            }
        }
        return $this->available;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private static function cut($v, int $len): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim((string) preg_replace('/\s+/', ' ', (string) $v));
        return $v === '' ? null : mb_substr($v, 0, $len);
    }

    /** A destination as it is kept: trimmed, one space between words, the first letters capitals when typed in lower case. */
    public static function destination($v): string
    {
        $v = trim((string) preg_replace('/\s+/', ' ', (string) $v));
        if ($v === '') {
            return '';
        }
        return mb_strtolower($v) === $v ? mb_convert_case($v, MB_CASE_TITLE, 'UTF-8') : $v;
    }

    private function log(string $which, int $id, string $summary, array $changes): void
    {
        Activity::master($this->db, $this->user, $which, $id, $summary, $changes, $this->actorId, $this->actorName);
    }

    // =====================================================================
    //  Destinations
    // =====================================================================

    /** Every destination the masters know, from every list: the hotel areas, the priced entries, the activities, the free visits. */
    public function destinations(): array
    {
        $out = [];
        $add = static function (?string $d) use (&$out): void {
            $d = self::destination((string) $d);
            if ($d !== '' && !isset($out[mb_strtolower($d)])) {
                $out[mb_strtolower($d)] = $d;
            }
        };
        foreach ($this->entrances(null, true) as $e) {
            $add($e['destination']);
        }
        foreach ([
            "SELECT DISTINCT location AS d FROM tf_excursion_rate WHERE location IS NOT NULL AND location <> ''",
            "SELECT DISTINCT destination AS d FROM tf_free_visit WHERE destination <> ''",
            "SELECT DISTINCT name AS d FROM hr2_location WHERE name IS NOT NULL AND name <> '' AND name NOT LIKE '%,%'",
        ] as $sql) {
            try {
                foreach ($this->db->query($sql)->getResultArray() as $r) {
                    $add($r['d']);
                }
            } catch (\Throwable $e) {
                // a list that is not on this install
            }
        }
        $vals = array_values($out);
        usort($vals, static fn ($a, $b) => strcasecmp($a, $b));
        return $vals;
    }

    // =====================================================================
    //  Free visits
    // =====================================================================

    /** @return array<int, array<string, mixed>> */
    public function freeVisits(?string $destination = null, bool $all = false): array
    {
        if (!$this->available()) {
            return [];
        }
        $where = [];
        $bind  = [];
        if (!$all) {
            $where[] = 'active = 1';
        }
        if ($destination !== null && $destination !== '') {
            $where[] = 'LOWER(destination) = ?';
            $bind[]  = mb_strtolower(self::destination($destination));
        }
        $rows = $this->db->query('SELECT * FROM tf_free_visit' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY destination = '', destination, sort_order, name", $bind)->getResultArray();
        $used = [];
        try {
            foreach ($this->db->query('SELECT free_visit_id, COUNT(*) n FROM tf_intake_service WHERE free_visit_id IS NOT NULL GROUP BY free_visit_id')->getResultArray() as $u) {
                $used[(int) $u['free_visit_id']] = (int) $u['n'];
            }
        } catch (\Throwable $e) {
            $used = [];
        }
        foreach ($rows as &$r) {
            $r['id']     = (int) $r['id'];
            $r['active'] = (int) $r['active'];
            $r['used']   = $used[$r['id']] ?? 0;
            $r['kind_word'] = self::FREE_KIND_WORDS[$r['kind']] ?? $r['kind'];
            $r['placed'] = trim((string) $r['destination']) !== '' ? 'set' : 'none';
            $r['origin'] = $r['origin'] ?? null;
        }
        unset($r);
        return $rows;
    }

    public function freeVisit(int $id): ?array
    {
        if (!$this->available()) {
            return null;
        }
        $r = $this->db->query('SELECT * FROM tf_free_visit WHERE id = ?', [$id])->getRowArray();
        if ($r) {
            $r['id'] = (int) $r['id'];
            $r['active'] = (int) $r['active'];
        }
        return $r ?: null;
    }

    /**
     * Add a free visit, or change one. No cost can be given: there is no field for one.
     *
     * @param array{id?:mixed, destination?:mixed, name?:mixed, kind?:mixed, description?:mixed, active?:mixed, sort_order?:mixed} $v
     */
    public function saveFreeVisit(array $v): array
    {
        $this->assertAvailable();
        $dest = self::destination((string) ($v['destination'] ?? ''));
        $name = self::cut($v['name'] ?? null, 190);
        if ($dest === '' || $name === null) {
            throw new \InvalidArgumentException('A free visit needs a destination and a name (Galle — a walk round the Fort ramparts).');
        }
        foreach (['cost', 'rate', 'price', 'amount', 'usd', 'fee'] as $k) {
            if (array_key_exists($k, $v) && trim((string) $v[$k]) !== '') {
                throw new \InvalidArgumentException('A free visit carries no cost. Anything with a rate belongs under Paid Activities & Services.');
            }
        }
        // schema 39: the Entrance Fee Master is the one authority on entrance charges — a place it holds is not also a free visit
        if ((array_key_exists('active', $v) ? !empty($v['active']) : true) && ($item = (new EntranceMaster($this->db))->placeNamed($name)) !== null) {
            throw new \InvalidArgumentException('"' . $name . '" is ' . $item['name'] . ($item['code'] !== '' ? ' (' . $item['code'] . ')' : '') . ' in the Place to Visit / Entrance Fee Master, which decides its entrance charge. It cannot also be a free visit — put it on the day from the master instead.');
        }
        $kind = in_array((string) ($v['kind'] ?? ''), self::FREE_KINDS, true) ? (string) $v['kind'] : 'sightseeing';
        // what the client reads about it under the day; copied onto a line when the visit is put on a day
        $desc = array_key_exists('description', $v) ? self::cut($v['description'], 1000) : null;
        $sort = isset($v['sort_order']) && $v['sort_order'] !== '' ? (int) $v['sort_order'] : null;
        $id   = (int) ($v['id'] ?? 0);
        $was  = $id > 0 ? $this->freeVisit($id) : null;
        if ($id > 0 && !$was) {
            throw new \RuntimeException('That free visit is not in the master.');
        }
        $active = array_key_exists('active', $v) ? (int) !empty($v['active']) : (int) ($was['active'] ?? 1);
        if ($was) {
            $desc ??= array_key_exists('description', $v) ? null : ($was['description'] ?? null);
            $this->db->query('UPDATE tf_free_visit SET destination = ?, name = ?, kind = ?, description = ?, active = ?, sort_order = ?, updated_at = ?, updated_by = ? WHERE id = ?',
                [$dest, $name, $kind, $desc, $active, $sort ?? (int) $was['sort_order'], $this->now(), $this->user, $id]);
        } else {
            $dup = $this->db->query('SELECT id FROM tf_free_visit WHERE LOWER(destination) = ? AND LOWER(name) = ?', [mb_strtolower($dest), mb_strtolower($name)])->getRowArray();
            if ($dup) {
                throw new \InvalidArgumentException('"' . $name . '" is already under ' . $dest . '.');
            }
            $this->db->query('INSERT INTO tf_free_visit (destination, name, kind, description, active, sort_order, source, created_at, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
                [$dest, $name, $kind, $desc, $active, $sort ?? 0, 'manual', $this->now(), $this->user]);
            $id = (int) $this->db->insertID();
        }
        $this->log('free_visit', $id, 'Free Visits: ' . $dest . ' — ' . $name . ($was ? ' changed' : ' added'), [
            ['what' => 'destination', 'from' => $was['destination'] ?? null, 'to' => $dest],
            ['what' => 'name', 'from' => $was['name'] ?? null, 'to' => $name],
            ['what' => 'kind', 'from' => $was['kind'] ?? null, 'to' => $kind],
            ['what' => 'description', 'from' => $was['description'] ?? null, 'to' => $desc],
            ['what' => 'active', 'from' => isset($was['active']) ? (string) $was['active'] : null, 'to' => (string) $active],
        ]);
        return $this->freeVisit($id);
    }

    /**
     * Off the master: switched off and kept, never deleted — the days that carry it keep it as
     * written, and it can be switched on again from "show switched off".
     */
    public function deleteFreeVisit(int $id): array
    {
        $this->assertAvailable();
        $was = $this->freeVisit($id);
        if (!$was) {
            throw new \RuntimeException('That free visit is not in the master.');
        }
        $used = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_intake_service WHERE free_visit_id = ?', [$id])->getRowArray()['n'] ?? 0);
        if ((int) $was['active'] === 1) {
            $this->db->query('UPDATE tf_free_visit SET active = 0, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $id]);
            $this->log('free_visit', $id, 'Free Visits: ' . $was['name'] . ' switched off and kept' . ($used ? ' (' . $used . ' day line(s) carry it)' : ''), [['what' => 'active', 'from' => '1', 'to' => '0']]);
        }
        return ['deleted' => false, 'deactivated' => true, 'used' => $used];
    }

    // =====================================================================
    //  Paid: entrances, tickets and what is priced a head (entrance_fee)
    // =====================================================================

    /** False until the installer has made TourFlow's side table for the priced list. */
    private function hasFeeMeta(): bool
    {
        if ($this->feeActive === null) {
            try {
                $this->feeActive = (bool) $this->db->query("SHOW TABLES LIKE 'tf_fee_meta'")->getResultArray();
            } catch (\Throwable $e) {
                $this->feeActive = false;
            }
        }
        return $this->feeActive;
    }

    /**
     * The priced entrances and tickets (and whatever else the list prices a head: a jeep
     * safari, a village tour, a dance), each under the destination a person put it under,
     * with how many day lines are priced from it. An entry nobody has placed has no
     * destination ('placed' is 'none'): its address is shown for the person placing it, and
     * never read for a town.
     *
     * @return array<int, array<string, mixed>>
     */
    public function entrances(?string $destination = null, bool $all = false): array
    {
        try {
            $meta = $this->hasFeeMeta();
            $rows = $this->db->query('SELECT e.id, e.name, e.code, e.location, e.entranceFee, e.direct_payment, e.is_prepaid, e.description'
                . ($meta ? ', m.destination AS dest_set, COALESCE(m.active, 1) AS active' : ', NULL AS dest_set, 1 AS active')
                . ' FROM entrance_fee e' . ($meta ? ' LEFT JOIN tf_fee_meta m ON m.fee_id = e.id' : '') . ' ORDER BY e.name')->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $used = [];
        try {
            foreach ($this->db->query('SELECT fee_id, COUNT(*) n FROM tf_intake_service WHERE fee_id IS NOT NULL GROUP BY fee_id')->getResultArray() as $u) {
                $used[(int) $u['fee_id']] = (int) $u['n'];
            }
        } catch (\Throwable $e) {
            $used = [];
        }
        $want = $destination !== null && $destination !== '' ? mb_strtolower(self::destination($destination)) : null;
        // schema 39: the places of the Place to Visit / Entrance Fee Master, with their rate today (kept there, read only here);
        // then the old list's meals, services and activities, priced from it as before. The old records imported into the
        // master, and those it left out as general sightseeing or held for review, are not offered from the old list any more.
        $master = EntranceMaster::installed($this->db);
        $out  = [];
        $gone = [];
        if ($master) {
            try {
                foreach ($this->db->query("SELECT source_id, outcome FROM tf_place_import WHERE source = 'old_list' AND outcome IN ('imported','merged','split','sightseeing','review')")->getResultArray() as $t) {
                    $gone[(int) $t['source_id']] = (string) $t['outcome'];
                }
            } catch (\Throwable $e) {
                $gone = [];
            }
            foreach ((new EntranceMaster($this->db))->places() as $p) {
                if ($p['merged_into'] !== null || (!$all && $p['active'] !== 1)) {
                    continue;
                }
                if ($want !== null && mb_strtolower($p['destination']) !== $want) {
                    continue;
                }
                $cur = $p['current'];
                $out[] = [
                    'id'          => 0,
                    'place_id'    => $p['id'],
                    'master'      => true,
                    'name'        => $p['name'],
                    'code'        => $p['code'],
                    'location'    => $p['location'],
                    'destination' => $p['destination'],
                    'placed'      => $p['destination'] !== '' ? 'set' : 'none',
                    'usd'         => EntranceMaster::rateKind($cur) === 'charged' && $cur['currency'] === 'USD' ? $cur['adult_rate'] : null,
                    'rate_state'  => $p['status'] === 'priced' ? 'priced' : ($p['status'] === 'free' ? 'free' : ($p['status'] === 'off' ? 'off' : 'no_rate')),
                    // the master's word for the day; a place held for figures to decide carries them, so the reason is in view here too
                    'rate_text'   => $p['status_text'] . ($p['status'] === 'check' && trim((string) $p['review_note']) !== '' ? ' ' . trim((string) $p['review_note']) : ''),
                    'jeep'        => $p['jeep'] === 1,
                    'pay_mode'    => $p['pay_mode'],
                    'description' => $p['description'],
                    'active'      => $p['active'],
                    'used'        => $p['used'],
                ];
            }
        }
        foreach ($rows as $r) {
            if (isset($gone[(int) $r['id']])) {
                continue;
            }
            $dest = self::destination((string) ($r['dest_set'] ?? ''));
            $active = (int) $r['active'];
            if (!$all && $active !== 1) {
                continue;
            }
            if ($want !== null && mb_strtolower($dest) !== $want) {
                continue;
            }
            $usd = $r['entranceFee'] === null || $r['entranceFee'] === '' ? null : (float) $r['entranceFee'];
            $out[] = [
                'id'          => (int) $r['id'],
                'name'        => trim((string) $r['name']),
                'code'        => trim((string) $r['code']),
                'location'    => (string) $r['location'],
                'destination' => $dest,
                'placed'      => $dest !== '' ? 'set' : 'none',
                'usd'         => $usd,
                // what the costing makes of the figure: a head, none yet, or rupees sitting in the USD column
                'rate_state'  => $usd === null || $usd <= 0 ? 'no_amount' : ($usd > ServiceLookup::USD_SANITY ? 'rupees' : 'priced'),
                // schema 39: a meal, a service or an activity of the old list — outside the Place to Visit / Entrance Fee Master
                'master'      => false,
                'place_id'    => null,
                'pay_mode'    => ((int) ($r['is_prepaid'] ?? 0) === 1) ? 'prepaid' : (((int) ($r['direct_payment'] ?? 0) === 1) ? 'direct' : 'in_tour'),
                'description' => $r['description'],
                'active'      => $active,
                'used'        => $used[(int) $r['id']] ?? 0,
            ];
        }
        // placed ones by destination and name; the ones nobody has placed after them, by their
        // address as written, so entries at one place stand together for the person placing them
        usort($out, static fn ($a, $b) => [$a['destination'] === '' ? 1 : 0, mb_strtolower($a['destination']), $a['destination'] === '' ? mb_strtolower((string) $a['location']) : '', mb_strtolower($a['name'])]
            <=> [$b['destination'] === '' ? 1 : 0, mb_strtolower($b['destination']), $b['destination'] === '' ? mb_strtolower((string) $b['location']) : '', mb_strtolower($b['name'])]);
        return $out;
    }

    public function entrance(int $id): ?array
    {
        foreach ($this->entrances(null, true) as $r) {
            if ($r['id'] === $id && empty($r['master'])) {
                return $r;
            }
        }
        return null;
    }

    /**
     * TourFlow's own word on an entry of the priced list: the destination a person puts it
     * under (blank takes it back to "not placed yet") and whether it is still offered and
     * matched. The entry itself — its name, code, rate, who settles it, and its accounts
     * posting — is never touched here; that is Master Files → Entrance Fee.
     *
     * @param array{destination?:mixed, active?:mixed} $v
     */
    public function placeEntrance(int $id, array $v): array
    {
        if (EntranceMaster::installed($this->db)) {
            // schema 39: an entrance or a safari is kept in the Place to Visit / Entrance Fee Master alone — read only here
            $pid = (int) ($v['place_id'] ?? 0);
            $t = null;
            if ($pid <= 0 && $id > 0) {
                try {
                    $t = $this->db->query("SELECT outcome FROM tf_place_import WHERE source = 'old_list' AND source_id = ? AND outcome IN ('imported','merged','split','sightseeing','review')", [(string) $id])->getRowArray();
                } catch (\Throwable $e) {
                    $t = null;
                }
            }
            if ($pid > 0 || ($t && in_array($t['outcome'], ['imported', 'merged', 'split'], true))) {
                throw new \InvalidArgumentException('This is a place of the Place to Visit / Entrance Fee Master: its destination, rates and whether it is in use are kept there (Master Files → Place to Visit / Entrance Fee Master), not here.');
            }
            if ($t) {
                throw new \InvalidArgumentException('This old record is ' . ($t['outcome'] === 'sightseeing' ? 'general sightseeing' : 'held for review') . ' — outside the Place to Visit / Entrance Fee Master and no longer offered from the old list.');
            }
        }
        if (!$this->hasFeeMeta()) {
            throw new \RuntimeException('The programme masters are not on this install yet. Open Tour Inquiry and press Install first.');
        }
        $was = $this->entrance($id);
        if (!$was) {
            throw new \RuntimeException('That entry is not in the priced list.');
        }
        $row = $this->db->query('SELECT * FROM tf_fee_meta WHERE fee_id = ?', [$id])->getRowArray();
        $dest = array_key_exists('destination', $v) ? self::destination((string) $v['destination']) : self::destination((string) ($row['destination'] ?? ''));
        $dest = mb_substr($dest, 0, 120);
        $active = array_key_exists('active', $v) ? (int) !empty($v['active']) : (int) ($row['active'] ?? 1);
        $this->db->query('INSERT INTO tf_fee_meta (fee_id, destination, active, updated_at, updated_by) VALUES (?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE destination = VALUES(destination), active = VALUES(active), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)',
            [$id, $dest !== '' ? $dest : null, $active, $this->now(), $this->user]);
        $now = $this->entrance($id);
        $changes = [];
        if ($was['destination'] !== $now['destination']) {
            $changes[] = ['what' => ($was['code'] ?: $was['name']) . ' destination', 'from' => $was['destination'] ?: null, 'to' => $now['destination'] ?: null];
        }
        if ($was['active'] !== $now['active']) {
            $changes[] = ['what' => ($was['code'] ?: $was['name']) . ' offered', 'from' => $was['active'] ? 'on' : 'off', 'to' => $now['active'] ? 'on' : 'off'];
        }
        if ($changes) {
            $this->log('paid_entrance', $id, 'Paid Activities & Services: ' . $was['name'] . ' — ' . implode(', ', array_map(static fn ($c) => $c['what'] . ' ' . ($c['from'] ?? '—') . ' → ' . ($c['to'] ?? '—'), $changes)), $changes);
        }
        return $now;
    }

    // =====================================================================
    //  Paid: activities and excursions (tf_excursion_rate)
    // =====================================================================

    private function excHasLocation(): bool
    {
        if ($this->excLocation === null) {
            try {
                $this->excLocation = (bool) $this->db->query("SHOW COLUMNS FROM tf_excursion_rate LIKE 'location'")->getResultArray();
            } catch (\Throwable $e) {
                $this->excLocation = false;
            }
        }
        return $this->excLocation;
    }

    /** @return array<int, array<string, mixed>> the activities and excursions with their rate structure, by destination */
    public function activities(?string $location = null, bool $all = false): array
    {
        try {
            $hasLoc = $this->excHasLocation();
            $where = [];
            $bind  = [];
            if (!$all) {
                $where[] = 'active = 1';
            }
            if ($location !== null && $location !== '' && $hasLoc) {
                $where[] = 'LOWER(location) = ?';
                $bind[]  = mb_strtolower(self::destination($location));
            }
            $rows = $this->db->query('SELECT *' . ($hasLoc ? '' : ', NULL AS location') . ' FROM tf_excursion_rate' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY location, name', $bind)->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $used = [];
        try {
            foreach ($this->db->query('SELECT code, COUNT(*) n FROM tf_intake_excursion GROUP BY code')->getResultArray() as $u) {
                $used[strtoupper((string) $u['code'])] = (int) $u['n'];
            }
        } catch (\Throwable $e) {
            $used = [];
        }
        foreach ($rows as &$r) {
            $r['rate']     = (float) $r['rate'];
            $r['active']   = (int) $r['active'];
            $r['includes_transport'] = (int) $r['includes_transport'];
            $r['location'] = self::destination((string) ($r['location'] ?? ''));
            $r['used']     = $used[strtoupper((string) $r['code'])] ?? 0;
        }
        unset($r);
        return $rows;
    }

    /** Add an activity or excursion with its rate structure, or change one: through the excursion master's own writer, plus its destination. */
    public function saveActivity(array $a, IntakeService $take): array
    {
        $loc = self::destination((string) ($a['location'] ?? ''));
        if ($loc === '') {
            throw new \InvalidArgumentException('An activity needs a destination (Sigiriya — Minneriya jeep safari).');
        }
        $code = (string) preg_replace('/[^A-Z0-9_\-]/', '', strtoupper(trim((string) ($a['code'] ?? ''))));
        $before = null;
        foreach ($this->activities(null, true) as $r) {
            if (strtoupper((string) $r['code']) === $code) {
                $before = $r['location'];
            }
        }
        $row = $take->saveExcursionRate($a);   // the rate structure, through the master's own writer (it writes its own history)
        if ($this->excHasLocation()) {
            $this->db->query('UPDATE tf_excursion_rate SET location = ? WHERE code = ?', [$loc, $row['code']]);
            if ((string) $before !== $loc) {
                $this->log('paid_activity', 0, 'Paid Activities & Services: ' . $row['code'] . ' ' . $row['name'] . ' under ' . $loc, [['what' => $row['code'] . ' destination', 'from' => $before ?: null, 'to' => $loc]]);
            }
        }
        foreach ($this->activities(null, true) as $r) {
            if ($r['code'] === $row['code']) {
                return $r;
            }
        }
        return $row;
    }

    /**
     * Off the activities master: switched off and kept, never deleted — a quotation line that
     * carries it keeps its copied figures, and it can be switched on again from "show switched
     * off". (The legacy Excursion Rates screen keeps its own behaviour until it is retired.)
     */
    public function deleteActivity(string $code, IntakeService $take): array
    {
        $code = strtoupper(trim($code));
        $was  = null;
        foreach ($this->activities(null, true) as $r) {
            if (strtoupper((string) $r['code']) === $code) {
                $was = $r;
            }
        }
        if (!$was) {
            throw new \RuntimeException('That activity is not in the master.');
        }
        $used = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_intake_excursion WHERE code = ?', [$code])->getRowArray()['n'] ?? 0);
        if ((int) $was['active'] === 1) {
            $this->db->query('UPDATE tf_excursion_rate SET active = 0, updated_by = ?, updated_at = ? WHERE code = ?', [$this->user, $this->now(), $code]);
            $this->log('paid_activity', 0, 'Paid Activities & Services: ' . $code . ' ' . $was['name'] . ' switched off and kept' . ($used ? ' (' . $used . ' quotation line(s) carry it)' : ''),
                [['what' => $code . ' active', 'from' => '1', 'to' => '0']]);
        }
        return ['deleted' => false, 'deactivated' => true, 'used' => $used];
    }

    private function assertAvailable(): void
    {
        if (!$this->available()) {
            throw new \RuntimeException('The programme masters are not on this install yet. Open Tour Inquiry and press Install first.');
        }
    }
}
