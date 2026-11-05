<?php

namespace App\Libraries\HotelRates;

/**
 * Hotel Rates — the one place that reads and writes the hr2_* tables.
 *
 *  - hotels, supplier codes, locations, markets, room inventory
 *  - rate plans: save / approve / supersede / delete / copy, always through the Sheet shape
 *  - lookup (via RateLookup) and coverage for the panel and the quotation timeline
 *  - legacy write-through (via LegacyProjector) so old screens keep working
 *  - merge duplicate hotels, audit trail
 *
 * $db is a CodeIgniter\Database\BaseConnection; only query(), insertID(),
 * transStart(), transComplete() and transStatus() are used, so the class also
 * runs under the test harness.
 */
class HotelRateService
{
    /** @var object */
    protected $db;
    protected string $user;
    private ?RateLookup $lookup = null;
    private ?Settings $settings = null;
    private ?bool $frequentReady = null;

    public function __construct($db, ?string $user = null)
    {
        $this->db = $db;
        $this->user = $user ?: 'system';
    }

    public function lookupEngine(): RateLookup
    {
        return $this->lookup ??= new RateLookup($this->db, $this->settings());
    }

    /** All tunable rules (hr2_meta) — see Settings::DEFINITIONS. */
    public function settings(): Settings
    {
        return $this->settings ??= new Settings($this->db);
    }

    // =====================================================================
    //  Meta, markets, locations
    // =====================================================================

    public function meta(string $key, ?string $default = null): ?string
    {
        $r = $this->row('SELECT v FROM hr2_meta WHERE k = ?', [$key]);
        return $r ? $r['v'] : $default;
    }

    public function setMeta(string $key, ?string $value): void
    {
        $this->db->query('INSERT INTO hr2_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $value]);
        $this->settings = null;
        $this->lookup = null;
    }

    /** Market code for a quotation country (ISO-2, as consignee.country stores it). */
    public function marketForCountry(?string $iso): string
    {
        return $this->settings()->marketForCountry($iso);
    }

    /** @return array<string, string> code => name */
    public function markets(): array
    {
        $out = [];
        foreach ($this->rows('SELECT code, name FROM hr2_market ORDER BY sort_order, code') as $m) {
            $out[$m['code']] = $m['name'];
        }
        return $out;
    }

    public function ensureLocation(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }
        $alias = $this->row('SELECT location_id FROM hr2_location_alias WHERE alias = ?', [mb_strtolower($name)]);
        if ($alias) {
            return (int) $alias['location_id'];
        }
        $row = $this->row('SELECT id FROM hr2_location WHERE LOWER(name) = ?', [mb_strtolower($name)]);
        if ($row) {
            return (int) $row['id'];
        }
        $this->db->query('INSERT INTO hr2_location (name) VALUES (?)', [self::titleCase($name)]);
        return (int) $this->db->insertID();
    }

    public function locations(): array
    {
        return $this->rows("SELECT l.*, (SELECT COUNT(*) FROM hr2_hotel h WHERE h.location_id = l.id AND h.status <> 'merged') AS hotels FROM hr2_location l ORDER BY l.name");
    }

    /**
     * The name of a location that already exists and that $text names — exactly, through an alias, or as the last
     * comma-separated part of an address ("…, Vihara Junction, Dambulla" → Dambulla). Never creates one: a street address
     * that names no known town leaves the hotel without a location, for a person to set, instead of becoming a junk location.
     */
    public function matchLocation(?string $text): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) $text));
        if ($text === '') {
            return null;
        }
        $candidates = [$text];
        $parts = array_values(array_filter(array_map('trim', explode(',', $text)), static fn ($p) => $p !== ''));
        if (count($parts) > 1) {
            $candidates[] = end($parts);
            if (preg_match('/^(.*?)(?:,\s*Sri Lanka)$/i', $text, $m)) {   // "…, Kandy, Sri Lanka" → Kandy
                $p2 = array_values(array_filter(array_map('trim', explode(',', $m[1]))));
                if ($p2) {
                    $candidates[] = end($p2);
                }
            }
        }
        foreach ($candidates as $c) {
            $lc = mb_strtolower($c);
            $alias = $this->row('SELECT l.name FROM hr2_location_alias a JOIN hr2_location l ON l.id = a.location_id WHERE a.alias = ?', [$lc]);
            if ($alias) {
                return (string) $alias['name'];
            }
            $row = $this->row('SELECT name FROM hr2_location WHERE LOWER(name) = ?', [$lc]);
            if ($row) {
                return (string) $row['name'];
            }
        }
        return null;
    }

    // =====================================================================
    //  Hotels
    // =====================================================================

    private ?HotelCodes $codes = null;

    public function findHotelByAcc(string $supAccNo): ?array
    {
        $row = $this->row('SELECT h.* FROM hr2_hotel_account a JOIN hr2_hotel h ON h.id = a.hotel_id WHERE a.sup_acc_no = ?', [trim($supAccNo)]);
        if ($row && $row['status'] === 'merged' && !empty($row['merged_into_id'])) {
            return $this->getHotel((int) $row['merged_into_id']);
        }
        return $row ?: null;
    }

    public function getHotel(int $id): ?array
    {
        $h = $this->row('SELECT h.*, l.name AS location_name FROM hr2_hotel h LEFT JOIN hr2_location l ON l.id = h.location_id WHERE h.id = ?', [$id]);
        if (!$h) {
            return null;
        }
        $h['accounts'] = $this->rows('SELECT sup_acc_no, legacy_name, is_primary FROM hr2_hotel_account WHERE hotel_id = ? ORDER BY is_primary DESC, sup_acc_no', [$id]);
        $h['absorbed'] = $this->rows("SELECT m.id, m.name, " . ($this->codes()->ready() ? 'm.hotel_code' : 'NULL AS hotel_code') . ", m.primary_sup_acc_no, l.name AS location_name, m.updated_at, m.updated_by FROM hr2_hotel m LEFT JOIN hr2_location l ON l.id = m.location_id WHERE m.status = 'merged' AND m.merged_into_id = ? ORDER BY m.updated_at DESC", [$id]);
        return $h;
    }

    /**
     * Hotel list with a coverage summary. The aggregates are fetched with one
     * small GROUP BY query each and merged in PHP: a correlated subquery per
     * hotel took MySQL 8 well over a minute on the real tour_quotations table,
     * and derived-table joins were not much better.
     */
    public function searchHotels(string $q = '', int $limit = 600, ?string $today = null): array
    {
        $today = $today ?: date('Y-m-d');
        $codeCol = $this->codes()->ready() ? 'h.hotel_code' : 'NULL AS hotel_code';
        $sql = "SELECT h.id, h.name, {$codeCol}, h.primary_sup_acc_no, h.status, h.currency, h.chain, l.name AS location_name
                FROM hr2_hotel h LEFT JOIN hr2_location l ON l.id = h.location_id
                WHERE h.status <> 'merged'";
        $binds = [];
        if ($q !== '') {
            $sql .= ' AND (h.name LIKE ? OR l.name LIKE ? OR h.primary_sup_acc_no LIKE ? OR h.chain LIKE ?';
            $like = '%' . $q . '%';
            array_push($binds, $like, $like, $like, $like);
            $no = $this->codes()->ready() ? HotelCodes::parse($q) : null;
            if ($no !== null) {                              // "HTL-017", "htl 17", "H017" or "17" all find the hotel with that number
                $sql .= ' OR h.hotel_code = ?';
                $binds[] = HotelCodes::format($no);
            }
            $sql .= ')';
        }
        $sql .= ' ORDER BY h.name ASC LIMIT ' . (int) $limit;
        $hotels = $this->rows($sql, $binds);
        if (!$hotels) {
            return [];
        }
        $byId = static function (array $rows, string $key = 'hotel_id'): array {
            $out = [];
            foreach ($rows as $r) {
                $out[(int) $r[$key]] = $r;
            }
            return $out;
        };
        $approved = $byId($this->rows("SELECT hotel_id, COUNT(*) AS n, MAX(valid_to) AS latest_valid_to FROM hr2_contract WHERE status = 'approved' GROUP BY hotel_id"));
        // "Incomplete — rates pending" (Shantha, 6 Oct 2026): a hotel with no approved quotation rate plan at all — on the master for
        // identification and preparation, never a quotation price; payment-only plans do not count
        $quotePlans = $byId($this->rows("SELECT hotel_id, COUNT(*) AS n FROM hr2_contract WHERE status = 'approved' AND applies_to <> 'payment' GROUP BY hotel_id"));
        $next = $byId($this->rows("SELECT hotel_id, MIN(valid_from) AS next_valid_from FROM hr2_contract WHERE status = 'approved' AND valid_from > ? GROUP BY hotel_id", [$today]));
        $seasonsToday = $byId($this->rows(
            "SELECT c.hotel_id, COUNT(*) AS n FROM hr2_contract c JOIN hr2_season s ON s.contract_id = c.id JOIN hr2_season_date d ON d.season_id = s.id
             WHERE c.status = 'approved' AND d.date_from <= ? AND d.date_to >= ? GROUP BY c.hotel_id",
            [$today, $today]
        ));
        $drafts = $byId($this->rows("SELECT hotel_id, COUNT(*) AS n FROM hr2_contract WHERE status = 'draft' GROUP BY hotel_id"));
        $roomTypes = $byId($this->rows("SELECT hotel_id, COUNT(*) AS n FROM hr2_room_type WHERE status = 'active' GROUP BY hotel_id"));
        $accounts = [];
        $accHotel = [];
        foreach ($this->rows('SELECT hotel_id, sup_acc_no, is_primary FROM hr2_hotel_account ORDER BY is_primary DESC, sup_acc_no') as $a) {
            $accounts[(int) $a['hotel_id']][] = $a['sup_acc_no'];
            $accHotel[trim((string) $a['sup_acc_no'])] = (int) $a['hotel_id'];
        }
        $nights = [];
        try {
            foreach ($this->rows("SELECT hotel_acc, COUNT(*) AS n FROM tour_quotations WHERE hotel_acc <> '' AND tour_date >= DATE_SUB(?, INTERVAL 365 DAY) GROUP BY hotel_acc", [$today]) as $r) {
                $hid = $accHotel[trim((string) $r['hotel_acc'])] ?? null;
                if ($hid !== null) {
                    $nights[$hid] = ($nights[$hid] ?? 0) + (int) $r['n'];
                }
            }
        } catch (\Throwable $e) {
            // the legacy table is optional for the panel
        }
        foreach ($hotels as &$h) {
            $id = (int) $h['id'];
            $h['approved_plans'] = (int) ($approved[$id]['n'] ?? 0);
            $h['rates_pending'] = ((int) ($quotePlans[$id]['n'] ?? 0)) === 0 ? 1 : 0;
            $h['latest_valid_to'] = $approved[$id]['latest_valid_to'] ?? null;
            $h['next_valid_from'] = $next[$id]['next_valid_from'] ?? null;
            $h['seasons_today'] = (int) ($seasonsToday[$id]['n'] ?? 0);
            $h['draft_plans'] = (int) ($drafts[$id]['n'] ?? 0);
            $h['room_types'] = (int) ($roomTypes[$id]['n'] ?? 0);
            $h['sup_acc_nos'] = implode(',', $accounts[$id] ?? []);
            $h['nights_last_year'] = (int) ($nights[$id] ?? 0);
        }
        unset($h);
        // the Frequently Used mark (schema 41): read on its own, so the list works the same before Install now adds it
        $frequent = [];
        if ($this->frequentReady()) {
            foreach ($this->rows('SELECT id FROM hr2_hotel WHERE frequently_used = 1') as $r) {
                $frequent[(int) $r['id']] = true;
            }
        }
        foreach ($hotels as &$h) {
            $h['frequently_used'] = isset($frequent[(int) $h['id']]) ? 1 : 0;
        }
        unset($h);
        return $hotels;
    }

    /** True once Install now (TourFlow schema 41) has given the Hotel Master its Frequently Used mark. */
    public function frequentReady(): bool
    {
        if ($this->frequentReady === null) {
            try {
                $this->frequentReady = (bool) $this->row("SHOW COLUMNS FROM hr2_hotel LIKE 'frequently_used'");
            } catch (\Throwable $e) {
                $this->frequentReady = false;
            }
        }
        return $this->frequentReady;
    }

    /**
     * Tick or untick "Frequently Used" on a hotel of the Hotel Master. Only that flag changes:
     * the hotel stays in the full list, nothing is copied, and no rate, plan, room type or old
     * Hotel Rate row is touched. Each change is written to the hotel's history (who, when).
     *
     * @return array{id:int, name:string, frequently_used:int, changed:bool}
     */
    public function setFrequentlyUsed(int $hotelId, bool $on): array
    {
        if (!$this->frequentReady()) {
            throw new \RuntimeException('The Frequently Used mark is not installed yet: the System Administrator presses Install now (Tour Desk Admin) once.');
        }
        $h = $this->row('SELECT id, name, status, frequently_used FROM hr2_hotel WHERE id = ?', [$hotelId]);
        if (!$h || $h['status'] === 'merged') {
            throw new \RuntimeException('Hotel not found.');
        }
        $old = (int) $h['frequently_used'];
        $new = $on ? 1 : 0;
        if ($old !== $new) {
            $this->db->query('UPDATE hr2_hotel SET frequently_used = ? WHERE id = ?', [$new, $hotelId]);
            $this->audit('hotel', $hotelId, 'update', ['frequently_used' => $old], ['frequently_used' => $new]);
        }
        return ['id' => $hotelId, 'name' => (string) $h['name'], 'frequently_used' => $new, 'changed' => $old !== $new];
    }

    public const HOTEL_FIELDS = ['name', 'location_id', 'chain', 'star_category', 'grade', 'address', 'website', 'reservations_email', 'reservations_phone', 'sales_contact', 'accounts_email', 'phone', 'check_in_time', 'check_out_time', 'currency', 'tax_treatment', 'tax_note', 'bank_details', 'status', 'notes'];

    public function updateHotel(int $id, array $fields): void
    {
        $set = []; $binds = [];
        foreach (self::HOTEL_FIELDS as $f) {
            if (array_key_exists($f, $fields)) {
                $set[] = "`{$f}` = ?";
                $binds[] = $fields[$f] === '' ? null : $fields[$f];
            }
        }
        if (!$set) {
            return;
        }
        $old = $this->getHotel($id);
        $set[] = '`updated_at` = ?'; $binds[] = date('Y-m-d H:i:s');
        $set[] = '`updated_by` = ?'; $binds[] = $this->user;
        $binds[] = $id;
        $this->db->query('UPDATE hr2_hotel SET ' . implode(', ', $set) . ' WHERE id = ?', $binds);
        $this->audit('hotel', $id, 'update', $old, $fields);
        if (empty($old['hotel_code'])) {
            $this->ensureHotelCode($id, 'fix');              // a record completed or fixed gets its code if it had none
        }
    }

    /** The hotel's code (HTL-###), given now if it has none; null only when the code tables are not installed yet. */
    public function ensureHotelCode(int $hotelId, string $how = 'auto'): ?string
    {
        $codes = $this->codes();
        if (!$codes->ready()) {
            return null;
        }
        return $codes->assign($hotelId, $how);
    }

    public function codes(): HotelCodes
    {
        return $this->codes ??= new HotelCodes($this->db, $this->user);
    }

    /** Create a hotel record that has no supplier yet (rare: the supplier is normally created first in Master Files). */
    public function createHotel(string $name, string $supAccNo, ?string $location = null, array $fields = []): int
    {
        $name = trim($name);
        $supAccNo = trim($supAccNo);
        if ($name === '' || $supAccNo === '') {
            throw new \RuntimeException('Hotel name and supplier code are required.');
        }
        if ($this->row('SELECT hotel_id FROM hr2_hotel_account WHERE sup_acc_no = ?', [$supAccNo])) {
            throw new \RuntimeException("Supplier code {$supAccNo} already belongs to a hotel.");
        }
        $this->db->query(
            'INSERT INTO hr2_hotel (name, primary_sup_acc_no, location_id, chain, currency, status, created_at, created_by) VALUES (?, ?, ?, ?, ?, \'active\', ?, ?)',
            [$name, $supAccNo, $this->ensureLocation($location), $fields['chain'] ?? null, $fields['currency'] ?? $this->settings()->get('default_currency'), date('Y-m-d H:i:s'), $this->user]
        );
        $id = (int) $this->db->insertID();
        $this->db->query('INSERT INTO hr2_hotel_account (sup_acc_no, hotel_id, legacy_name, is_primary) VALUES (?, ?, ?, 1)', [$supAccNo, $id, $name]);
        $code = $this->ensureHotelCode($id, 'create');       // HTL-### at birth: nobody types it
        $this->audit('hotel', $id, 'insert', null, ['name' => $name, 'sup_acc_no' => $supAccNo, 'hotel_code' => $code]);
        // what else is known at birth (address, e-mails, phone — from the supplier record), kept on the hotel too
        $more = array_intersect_key($fields, array_flip(['address', 'reservations_email', 'reservations_phone', 'accounts_email', 'phone', 'website', 'notes']));
        $more = array_filter($more, static fn ($v) => trim((string) $v) !== '');
        if ($more) {
            $this->db->query('UPDATE hr2_hotel SET ' . implode(', ', array_map(static fn ($f) => "`{$f}` = ?", array_keys($more))) . ' WHERE id = ?', array_merge(array_values($more), [$id]));
        }
        return $id;
    }

    // =====================================================================
    //  Room types
    // =====================================================================

    public function roomTypes(int $hotelId, bool $activeOnly = false): array
    {
        $rows = $this->rows('SELECT rt.*, b.name AS base_room_type_name FROM hr2_room_type rt LEFT JOIN hr2_room_type b ON b.id = rt.base_room_type_id WHERE rt.hotel_id = ?' . ($activeOnly ? " AND rt.status = 'active'" : '') . ' ORDER BY rt.sort_order, rt.name', [$hotelId]);
        return $rows;
    }

    /** Find or create a room type (case-insensitive on the name). */
    public function ensureRoomType(int $hotelId, string $name, ?int $legacyId = null, array $extra = []): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            throw new \RuntimeException('Room type name is empty.');
        }
        $row = $this->row('SELECT * FROM hr2_room_type WHERE hotel_id = ? AND LOWER(name) = ?', [$hotelId, mb_strtolower($name)]);
        if ($row) {
            if (empty($row['legacy_room_type_id'])) {
                $legacyId = $legacyId ?: $this->ensureLegacyRoomTypeId($name);
                $this->db->query('UPDATE hr2_room_type SET legacy_room_type_id = ? WHERE id = ?', [$legacyId, $row['id']]);
                $row['legacy_room_type_id'] = $legacyId;
            }
            if ($extra) {
                $this->updateRoomType((int) $row['id'], $extra);
                $row = $this->row('SELECT * FROM hr2_room_type WHERE id = ?', [$row['id']]);
            }
            return $row;
        }
        $legacyId = $legacyId ?: $this->ensureLegacyRoomTypeId($name);
        $this->db->query(
            'INSERT INTO hr2_room_type (hotel_id, name, legacy_room_type_id, sort_order) VALUES (?, ?, ?, (SELECT COALESCE(MAX(t.sort_order), 0) + 1 FROM hr2_room_type t WHERE t.hotel_id = ?))',
            [$hotelId, $name, $legacyId, $hotelId]
        );
        $id = (int) $this->db->insertID();
        $this->audit('room_type', $id, 'insert', null, ['hotel_id' => $hotelId, 'name' => $name, 'legacy_room_type_id' => $legacyId]);
        if ($extra) {
            $this->updateRoomType($id, $extra);
        }
        return $this->row('SELECT * FROM hr2_room_type WHERE id = ?', [$id]);
    }

    public const ROOM_FIELDS = ['name', 'code', 'priced_as', 'base_room_type_id', 'room_count', 'size_sqm', 'bed_config', 'view', 'max_adults', 'max_children', 'max_occupancy', 'extra_bed_allowed', 'unit_pax_min', 'unit_pax_max', 'description', 'sort_order', 'status'];

    public function updateRoomType(int $id, array $fields): void
    {
        $old = $this->row('SELECT * FROM hr2_room_type WHERE id = ?', [$id]);
        if (!$old) {
            throw new \RuntimeException("Room type {$id} not found.");
        }
        if (array_key_exists('base_room_type', $fields) && !array_key_exists('base_room_type_id', $fields)) {
            $base = trim((string) $fields['base_room_type']);
            $fields['base_room_type_id'] = $base === '' ? null : (int) $this->ensureRoomType((int) $old['hotel_id'], $base)['id'];
        }
        if (!empty($fields['base_room_type_id']) && (int) $fields['base_room_type_id'] === $id) {
            throw new \RuntimeException('A room type cannot be its own base room.');
        }
        $set = []; $binds = [];
        foreach (self::ROOM_FIELDS as $f) {
            if (!array_key_exists($f, $fields)) {
                continue;
            }
            $v = $fields[$f];
            if ($v === '' || $v === null) {
                if (in_array($f, ['name', 'priced_as', 'status', 'max_adults', 'max_children', 'extra_bed_allowed', 'sort_order'], true)) {
                    continue; // NOT NULL columns keep their value
                }
                $v = null;
            }
            if ($f === 'name') {
                $v = trim(preg_replace('/\s+/', ' ', (string) $v));
                if ($v === '') {
                    continue;
                }
                $dup = $this->row('SELECT id FROM hr2_room_type WHERE hotel_id = ? AND LOWER(name) = ? AND id <> ?', [$old['hotel_id'], mb_strtolower($v), $id]);
                if ($dup) {
                    throw new \RuntimeException("Another room type of this hotel is already called '{$v}'.");
                }
            }
            if ($f === 'priced_as') {
                $v = $v === 'supplement' ? 'supplement' : 'base';
            }
            $set[] = "`{$f}` = ?"; $binds[] = $v;
        }
        if (!$set) {
            return;
        }
        $binds[] = $id;
        $this->db->query('UPDATE hr2_room_type SET ' . implode(', ', $set) . ' WHERE id = ?', $binds);
        if (isset($fields['name']) && $fields['name'] !== $old['name'] && !empty($old['legacy_room_type_id'])) {
            // keep the global legacy spelling in step so old screens show the new name
            $this->db->query('UPDATE room_type SET rtype = ? WHERE id = ?', [$fields['name'], $old['legacy_room_type_id']]);
        }
        $this->audit('room_type', $id, 'update', $old, $fields);
    }

    /** The global legacy room_type table still feeds tour_quotations.room_type. */
    public function ensureLegacyRoomTypeId(string $name): int
    {
        $row = $this->row('SELECT id FROM room_type WHERE LOWER(TRIM(rtype)) = ? ORDER BY id LIMIT 1', [mb_strtolower(trim($name))]);
        if ($row) {
            return (int) $row['id'];
        }
        $this->db->query('INSERT INTO room_type (rtype) VALUES (?)', [trim($name)]);
        return (int) $this->db->insertID();
    }

    // =====================================================================
    //  Rate plans
    // =====================================================================

    /** Plan list for a hotel with counts. */
    public function contracts(int $hotelId): array
    {
        $rows = $this->rows(
            "SELECT c.*, p.name AS parent_name,
                (SELECT COUNT(*) FROM hr2_season s WHERE s.contract_id = c.id) AS seasons,
                (SELECT COUNT(*) FROM hr2_season s JOIN hr2_rate r ON r.season_id = s.id WHERE s.contract_id = c.id) AS rate_rows,
                (SELECT COUNT(*) FROM hr2_supplement x WHERE x.contract_id = c.id) AS supplements,
                (SELECT COUNT(*) FROM hr2_child_rate x WHERE x.contract_id = c.id) AS child_rules,
                (SELECT COUNT(*) FROM hr2_cancellation_rule x WHERE x.contract_id = c.id) AS cancellation_rules,
                (SELECT GROUP_CONCAT(CONCAT(IF(m.mode = 'exclude', '-', ''), m.market_code) ORDER BY m.mode, m.market_code SEPARATOR ',') FROM hr2_contract_market m WHERE m.contract_id = c.id) AS market_list
             FROM hr2_contract c LEFT JOIN hr2_contract p ON p.id = c.parent_contract_id
             WHERE c.hotel_id = ? ORDER BY FIELD(c.status, 'draft', 'approved', 'superseded', 'cancelled'), c.valid_from DESC, c.id DESC",
            [$hotelId]
        );
        return $rows;
    }

    /** Whole plan as a Sheet (with ids) plus hotel and status info. */
    public function getContract(int $id): ?array
    {
        $c = $this->row('SELECT * FROM hr2_contract WHERE id = ?', [$id]);
        if (!$c) {
            return null;
        }
        $hotel = $this->getHotel((int) $c['hotel_id']);
        $roomNames = [];
        foreach ($this->roomTypes((int) $c['hotel_id']) as $rt) {
            $roomNames[(int) $rt['id']] = $rt['name'];
        }
        $contract = array_intersect_key($c, array_flip(Sheet::CONTRACT_KEYS));
        $contract['markets'] = array_map(static fn ($m) => ['code' => $m['market_code'], 'mode' => $m['mode']], $this->rows('SELECT market_code, mode FROM hr2_contract_market WHERE contract_id = ? ORDER BY mode, market_code', [$id]));
        $seasons = [];
        $seasonName = []; // id => name for references
        foreach ($this->rows('SELECT * FROM hr2_season WHERE contract_id = ? ORDER BY sort_order, id', [$id]) as $s) {
            $dates = array_map(static fn ($d) => ['from' => $d['date_from'], 'to' => $d['date_to']], $this->rows('SELECT date_from, date_to FROM hr2_season_date WHERE season_id = ? ORDER BY date_from', [$s['id']]));
            $rates = [];
            foreach ($this->rows("SELECT r.* FROM hr2_rate r JOIN hr2_room_type rt ON rt.id = r.room_type_id WHERE r.season_id = ? ORDER BY rt.sort_order, rt.name, FIELD(r.meal_plan,'RO','BB','HB','FB','AI')", [$s['id']]) as $r) {
                $rates[] = ['id' => (int) $r['id'], 'room_type' => $roomNames[(int) $r['room_type_id']] ?? '', 'room_type_id' => (int) $r['room_type_id'], 'meal_plan' => $r['meal_plan'], 'sgl' => $r['sgl'], 'dbl' => $r['dbl'], 'tpl' => $r['tpl'], 'quad' => $r['quad'], 'unit' => $r['unit'], 'child_with_bed' => $r['child_with_bed'], 'child_no_bed' => $r['child_no_bed'], 'note' => $r['note'], 'quality_flags' => $r['quality_flags']];
            }
            $label = $s['name'] ?: Sheet::seasonLabel(['name' => null, 'dates' => $dates], count($seasons));
            $seasonName[(int) $s['id']] = $label;
            $seasons[] = ['id' => (int) $s['id'], 'name' => $s['name'], 'kind' => $s['kind'], 'min_stay' => $s['min_stay'], 'max_rooms' => $s['max_rooms'], 'notes' => $s['notes'], 'dates' => $dates, 'rates' => $rates];
        }
        $sref = static fn ($sid) => $sid === null ? null : ($seasonName[(int) $sid] ?? null);
        $rref = static fn ($rid) => $rid === null ? null : ($roomNames[(int) $rid] ?? null);
        $sheet = [
            'hotel' => ['id' => (int) $c['hotel_id'], 'name' => $hotel['name'] ?? '', 'sup_acc_no' => $hotel['primary_sup_acc_no'] ?? null],
            'contract' => $contract,
            'seasons' => $seasons,
            'room_supplements' => array_map(fn ($r) => ['room_type' => $rref($r['room_type_id']), 'room_type_id' => (int) $r['room_type_id'], 'season' => $sref($r['season_id']), 'amount' => $r['amount'], 'per' => $r['per'], 'note' => $r['note']], $this->rows('SELECT * FROM hr2_room_supplement WHERE contract_id = ? ORDER BY id', [$id])),
            'meal_deltas' => array_map(fn ($r) => ['from' => $r['from_plan'], 'to' => $r['to_plan'], 'adult' => $r['adult'], 'child' => $r['child'], 'season' => $sref($r['season_id']), 'note' => $r['note']], $this->rows('SELECT * FROM hr2_meal_delta WHERE contract_id = ? ORDER BY id', [$id])),
            'meal_prices' => array_map(fn ($r) => ['meal' => $r['meal'], 'name' => $r['name'], 'adult' => $r['adult'], 'child' => $r['child'], 'note' => $r['note']], $this->rows('SELECT * FROM hr2_meal_price WHERE contract_id = ? ORDER BY id', [$id])),
            'supplements' => array_map(fn ($r) => ['type' => $r['type'], 'name' => $r['name'], 'date_from' => $r['date_from'], 'date_to' => $r['date_to'], 'season' => $sref($r['season_id']), 'meal_plans' => $r['applies_meal_plans'], 'room_type' => $rref($r['room_type_id']), 'room_type_id' => $r['room_type_id'], 'per' => $r['per'], 'amount' => $r['amount'], 'amount_sgl' => $r['amount_sgl'], 'amount_dbl' => $r['amount_dbl'], 'amount_tpl' => $r['amount_tpl'], 'child_amount' => $r['child_amount'], 'child_pct' => $r['child_pct'], 'pct_of_rate' => $r['pct_of_rate'], 'compulsory' => $r['compulsory'], 'note' => $r['note']], $this->rows('SELECT * FROM hr2_supplement WHERE contract_id = ? ORDER BY id', [$id])),
            'child_rates' => array_map(fn ($r) => ['age_from' => $r['age_from'], 'age_to' => $r['age_to'], 'bed' => $r['bed'], 'meal_plan' => $r['meal_plan'], 'amount' => $r['amount'], 'pct' => $r['pct'], 'pct_basis' => $r['pct_basis'], 'max_children' => $r['max_children'], 'room_type' => $rref($r['room_type_id']), 'note' => $r['note']], $this->rows('SELECT * FROM hr2_child_rate WHERE contract_id = ? ORDER BY age_from, id', [$id])),
            'guide_policies' => array_map(fn ($r) => ['role' => $r['role'], 'meal_plan' => $r['meal_plan'], 'amount' => $r['amount'], 'currency' => $r['currency'], 'pct_of' => $r['pct_of'], 'pct' => $r['pct'], 'per' => $r['per'], 'free_from_pax' => $r['free_from_pax'], 'min_rooms' => $r['min_rooms'], 'accommodation' => $r['accommodation'], 'note' => $r['note']], $this->rows('SELECT * FROM hr2_guide_policy WHERE contract_id = ? ORDER BY id', [$id])),
            'foc_rules' => array_map(fn ($r) => ['basis' => $r['basis'], 'every_n' => $r['every_n'], 'free_n' => $r['free_n'], 'discount_pct' => $r['discount_pct'], 'as_what' => $r['as_what'], 'note' => $r['note']], $this->rows('SELECT * FROM hr2_foc_rule WHERE contract_id = ? ORDER BY id', [$id])),
            'offers' => array_map(fn ($r) => array_diff_key($r, ['id' => 1, 'contract_id' => 1]), $this->rows('SELECT * FROM hr2_offer WHERE contract_id = ? ORDER BY id', [$id])),
            'cancellation_rules' => array_map(fn ($r) => array_diff_key($r, ['id' => 1, 'contract_id' => 1, 'sort_order' => 1]), $this->rows('SELECT * FROM hr2_cancellation_rule WHERE contract_id = ? ORDER BY sort_order, id', [$id])),
            'payment_rules' => array_map(fn ($r) => array_diff_key($r, ['id' => 1, 'contract_id' => 1]), $this->rows('SELECT * FROM hr2_payment_rule WHERE contract_id = ? ORDER BY id', [$id])),
            'restrictions' => array_map(fn ($r) => array_diff_key($r, ['id' => 1, 'contract_id' => 1]), $this->rows('SELECT * FROM hr2_restriction WHERE contract_id = ? ORDER BY date_from, id', [$id])),
            'extras' => array_map(fn ($r) => array_diff_key($r, ['id' => 1, 'contract_id' => 1]), $this->rows('SELECT * FROM hr2_extra WHERE contract_id = ? ORDER BY id', [$id])),
        ];
        $sheet = Sheet::normalise($sheet);
        $sheet['meta'] = ['status' => $c['status'], 'entered_by' => $c['entered_by'], 'checked_by' => $c['checked_by'], 'approved_by' => $c['approved_by'], 'approved_at' => $c['approved_at'], 'created_at' => $c['created_at'], 'updated_at' => $c['updated_at'], 'import_id' => $c['import_id'], 'superseded_by' => $c['superseded_by'], 'parent_name' => $c['parent_contract_id'] ? ($this->row('SELECT name FROM hr2_contract WHERE id = ?', [$c['parent_contract_id']])['name'] ?? null) : null];
        return $sheet;
    }

    /** Approved plans of a hotel (for the validator's overlap warning). */
    public function existingPlans(int $hotelId, ?int $excludeContractId = null): array
    {
        $binds = [$hotelId];
        $sql = "SELECT c.id, c.name, c.plan_type, c.tier, c.applies_to FROM hr2_contract c WHERE c.hotel_id = ? AND c.status = 'approved'";
        if ($excludeContractId) {
            $sql .= ' AND c.id <> ?';
            $binds[] = $excludeContractId;
        }
        $out = [];
        foreach ($this->rows($sql, $binds) as $c) {
            $ranges = array_map(static fn ($d) => [$d['date_from'], $d['date_to']], $this->rows('SELECT d.date_from, d.date_to FROM hr2_season s JOIN hr2_season_date d ON d.season_id = s.id WHERE s.contract_id = ?', [$c['id']]));
            $rooms = array_column($this->rows('SELECT DISTINCT rt.name FROM hr2_season s JOIN hr2_rate r ON r.season_id = s.id JOIN hr2_room_type rt ON rt.id = r.room_type_id WHERE s.contract_id = ?', [$c['id']]), 'name');
            $markets = array_column($this->rows("SELECT market_code FROM hr2_contract_market WHERE contract_id = ? AND mode = 'include'", [$c['id']]), 'market_code');
            $out[] = ['contract_id' => (int) $c['id'], 'contract_name' => $c['name'], 'plan_type' => $c['plan_type'], 'tier' => $c['tier'], 'applies_to' => $c['applies_to'] ?? 'both', 'markets' => $markets, 'ranges' => $ranges, 'room_types' => $rooms];
        }
        return $out;
    }

    /** Latest approved/superseded rates per room|meal for the change warning. */
    public function previousRates(int $hotelId, ?int $excludeContractId = null): array
    {
        $binds = [$hotelId];
        $sql = "SELECT LOWER(rt.name) AS room, r.meal_plan, r.sgl, r.dbl, r.tpl, c.approved_at
                FROM hr2_contract c JOIN hr2_season s ON s.contract_id = c.id JOIN hr2_rate r ON r.season_id = s.id JOIN hr2_room_type rt ON rt.id = r.room_type_id
                WHERE c.hotel_id = ? AND c.status IN ('approved','superseded') AND c.plan_type IN ('contract','migrated') AND c.applies_to <> 'payment'";
        if ($excludeContractId) {
            $sql .= ' AND c.id <> ?';
            $binds[] = $excludeContractId;
        }
        $sql .= ' ORDER BY c.approved_at DESC, s.sort_order';
        $map = [];
        foreach ($this->rows($sql, $binds) as $r) {
            $k = $r['room'] . '|' . $r['meal_plan'];
            if (!isset($map[$k])) {
                $map[$k] = ['sgl' => $r['sgl'], 'dbl' => $r['dbl'], 'tpl' => $r['tpl']];
            }
        }
        return $map;
    }

    /** Validate a sheet against the database context (room types, other plans, markets). */
    public function validateSheet(array $sheet, array $opts = []): array
    {
        $sheet = Sheet::normalise($sheet);
        $hotelId = (int) ($sheet['hotel']['id'] ?? 0);
        if (!$hotelId && !empty($sheet['hotel']['sup_acc_no'])) {
            $h = $this->findHotelByAcc($sheet['hotel']['sup_acc_no']);
            $hotelId = $h ? (int) $h['id'] : 0;
            $sheet['hotel']['id'] = $hotelId ?: null;
        }
        $contractId = $sheet['contract']['id'];
        $known = [];
        if ($hotelId) {
            foreach ($this->roomTypes($hotelId) as $rt) {
                $known[mb_strtolower($rt['name'])] = $rt;
            }
        }
        $parent = $sheet['contract']['parent_contract_id'] ? $this->row('SELECT id, hotel_id, name, status FROM hr2_contract WHERE id = ?', [$sheet['contract']['parent_contract_id']]) : null;
        $st = $this->settings();
        $options = [
            'known_room_types' => $known,
            'previous_rates' => $hotelId ? $this->previousRates($hotelId, $contractId) : [],
            'today' => $opts['today'] ?? date('Y-m-d'),
            'markets' => $this->markets(),
            'parent' => $parent,
            'settings' => ['tiny_rate' => $st->get('tiny_rate'), 'huge_rate' => $st->get('huge_rate'), 'change_warning_pct' => $st->get('change_warning_pct'), 'expiry_warning_days' => $st->get('expiry_warning_days'), 'gala_dates' => $st->get('gala_dates'), 'gala_child_pct' => $st->get('gala_child_pct'), 'child_max_age' => $st->get('child_max_age')],
        ];
        $issues = (new RateValidator())->validate($sheet, $hotelId ? $this->existingPlans($hotelId, $contractId) : [], $options);
        if ($sheet['contract']['parent_contract_id'] && !$parent) {
            $issues[] = ['level' => 'error', 'code' => 'E17', 'message' => 'The parent plan does not exist (it may have been deleted).', 'section' => 'header', 'season' => null, 'row' => null];
        }
        return $issues;
    }

    /**
     * Validate and save a sheet as a plan (draft unless $opts['approve']).
     * Drafts may be saved with errors so staff can fix them in the panel;
     * approval requires zero errors and acknowledged warnings.
     *
     * @return array{contract_id:int|null, issues:array, saved:bool, approved:bool, needs_acknowledgement?:bool}
     */
    public function saveContract(array $sheet, array $opts = []): array
    {
        $sheet = Sheet::normalise($sheet);
        $hotelId = (int) ($sheet['hotel']['id'] ?? 0);
        if (!$hotelId && !empty($sheet['hotel']['sup_acc_no'])) {
            $h = $this->findHotelByAcc($sheet['hotel']['sup_acc_no']);
            $hotelId = $h ? (int) $h['id'] : 0;
        }
        $sheet['hotel']['id'] = $hotelId ?: null;
        $c = $sheet['contract'];
        $contractId = $c['id'];

        $issues = $this->validateSheet($sheet, $opts);
        $hasErrors = RateValidator::hasErrors($issues);
        if (!$hotelId) {
            return ['contract_id' => null, 'issues' => $issues, 'saved' => false, 'approved' => false];
        }
        if (!empty($opts['approve']) && $hasErrors) {
            return ['contract_id' => $contractId, 'issues' => $issues, 'saved' => false, 'approved' => false];
        }
        if (!empty($opts['approve']) && empty($opts['acknowledge_warnings']) && !empty($issues)) {
            return ['contract_id' => $contractId, 'issues' => $issues, 'saved' => false, 'approved' => false, 'needs_acknowledgement' => true];
        }

        $now = date('Y-m-d H:i:s');
        // derive validity from seasons when missing so a draft can be stored
        $vf = $c['valid_from']; $vt = $c['valid_to'];
        $ranges = Sheet::seasonRanges($sheet);
        if (!$vf || !$vt) {
            $froms = array_column($ranges, 0); $tos = array_column($ranges, 1);
            $vf = $vf ?: ($froms ? min($froms) : date('Y-m-d'));
            $vt = $vt ?: ($tos ? max($tos) : $vf);
        }
        if ($vf > $vt) {
            [$vf, $vt] = [$vt, $vf];
        }
        $header = [
            'hotel_id' => $hotelId,
            'name' => $c['name'] !== '' ? $c['name'] : ('Rates ' . $vf . ' to ' . $vt),
            'plan_type' => $c['plan_type'], 'rate_code' => $c['rate_code'], 'tier' => $c['tier'],
            'tier_min_rooms' => $c['tier_min_rooms'], 'tier_max_rooms' => $c['tier_max_rooms'], 'tier_min_pax' => $c['tier_min_pax'], 'tier_max_pax' => $c['tier_max_pax'],
            'priority' => $c['priority'], 'applies_to' => $c['applies_to'], 'parent_contract_id' => $c['parent_contract_id'],
            'valid_from' => $vf, 'valid_to' => $vt, 'book_from' => $c['book_from'], 'book_to' => $c['book_to'],
            'currency' => $c['currency'], 'billing_currency' => $c['billing_currency'], 'pricing_mode' => $c['pricing_mode'], 'default_meal_plan' => $c['default_meal_plan'],
            'taxes_included' => $c['taxes_included'], 'tax_pct' => $c['tax_pct'], 'tax_note' => $c['tax_note'],
            'source_type' => $c['source_type'], 'source_file' => $c['source_file'], 'received_at' => $c['received_at'],
            'contract_date' => $c['contract_date'], 'contract_type' => $c['contract_type'], 'hotel_reference' => $c['hotel_reference'], 'credit_facility' => $c['credit_facility'],
            'min_stay' => $c['min_stay'], 'max_rooms_per_booking' => $c['max_rooms_per_booking'], 'release_days' => $c['release_days'], 'tentative_hold_hours' => $c['tentative_hold_hours'],
            'extra_bed_adult' => $c['extra_bed_adult'], 'max_children_free' => $c['max_children_free'],
            'child_policy_text' => $c['child_policy_text'], 'guide_policy_text' => $c['guide_policy_text'], 'foc_policy_text' => $c['foc_policy_text'],
            'cancellation_text' => $c['cancellation_text'], 'payment_text' => $c['payment_text'], 'terms_text' => $c['terms_text'], 'voucher_note' => $c['voucher_note'], 'notes' => $c['notes'],
        ];
        if (!empty($opts['import_id'])) {
            $header['import_id'] = (int) $opts['import_id'];
        }

        $this->db->transStart();
        $wasApproved = false;
        $wasExisting = (bool) $contractId;
        if ($contractId) {
            $old = $this->row('SELECT * FROM hr2_contract WHERE id = ?', [$contractId]);
            if (!$old) {
                throw new \RuntimeException("Plan {$contractId} not found.");
            }
            if ($old['status'] === 'approved' && empty($opts['edit_approved'])) {
                throw new \RuntimeException('An approved plan cannot be edited. Copy it to a new draft, or mark it as superseded first.');
            }
            if ($old['status'] === 'superseded' || $old['status'] === 'cancelled') {
                throw new \RuntimeException('A superseded or cancelled plan cannot be edited. Copy it to a new draft instead.');
            }
            $wasApproved = $old['status'] === 'approved';
            $set = []; $binds = [];
            foreach ($header as $k => $v) { $set[] = "`{$k}` = ?"; $binds[] = $v; }
            $set[] = '`updated_at` = ?'; $binds[] = $now;
            $binds[] = $contractId;
            $this->db->query('UPDATE hr2_contract SET ' . implode(', ', $set) . ' WHERE id = ?', $binds);
            $this->audit('contract', $contractId, 'update', $old, $header, $opts['import_id'] ?? null);
        } else {
            $cols = array_keys($header);
            $this->db->query(
                'INSERT INTO hr2_contract (`' . implode('`, `', $cols) . '`, `status`, `entered_by`, `created_at`, `updated_at`) VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ', \'draft\', ?, ?, ?)',
                array_merge(array_values($header), [$this->user, $now, $now])
            );
            $contractId = (int) $this->db->insertID();
            $this->audit('contract', $contractId, 'insert', null, $header, $opts['import_id'] ?? null);
        }

        // ---- markets ---------------------------------------------------------
        $this->db->query('DELETE FROM hr2_contract_market WHERE contract_id = ?', [$contractId]);
        foreach ($c['markets'] as $m) {
            $this->db->query('INSERT IGNORE INTO hr2_contract_market (contract_id, market_code, mode) VALUES (?, ?, ?)', [$contractId, $m['code'], $m['mode']]);
        }

        // ---- room types declared on the sheet --------------------------------
        $roomIds = []; // lower name => id
        foreach ($sheet['room_types'] as $rt) {
            $extra = array_filter([
                'code' => $rt['code'], 'priced_as' => $rt['priced_as'], 'room_count' => $rt['room_count'], 'size_sqm' => $rt['size_sqm'], 'bed_config' => $rt['bed_config'], 'view' => $rt['view'],
                'max_adults' => $rt['max_adults'], 'max_children' => $rt['max_children'], 'max_occupancy' => $rt['max_occupancy'], 'extra_bed_allowed' => $rt['extra_bed_allowed'],
                'unit_pax_min' => $rt['unit_pax_min'], 'unit_pax_max' => $rt['unit_pax_max'], 'description' => $rt['description'], 'status' => $rt['status'],
            ], static fn ($v) => $v !== null);
            $row = $this->ensureRoomType($hotelId, $rt['name'], null, $extra);
            $roomIds[mb_strtolower($rt['name'])] = (int) $row['id'];
        }
        foreach ($sheet['room_types'] as $rt) {
            if ($rt['base_room_type']) {
                $this->updateRoomType($roomIds[mb_strtolower($rt['name'])], ['base_room_type' => $rt['base_room_type'], 'priced_as' => 'supplement']);
            }
        }
        $roomId = function (string $name, ?int $id = null) use ($hotelId, &$roomIds): ?int {
            $name = trim($name);
            if ($id) {
                $r = $this->row('SELECT id, name FROM hr2_room_type WHERE id = ? AND hotel_id = ?', [$id, $hotelId]);
                if ($r) {
                    $roomIds[mb_strtolower($r['name'])] = (int) $r['id'];
                    return (int) $r['id'];
                }
            }
            if ($name === '') {
                return null;
            }
            $k = mb_strtolower($name);
            if (!isset($roomIds[$k])) {
                $roomIds[$k] = (int) $this->ensureRoomType($hotelId, $name)['id'];
            }
            return $roomIds[$k];
        };

        // ---- seasons, dates, rates: replace the whole set -----------------------
        // the grid as it stood, so the values that change are on the audit trail (a rate
        // going from USD 125 to 135 is the change Management most wants to see); read-only
        $gridBefore = $wasExisting ? $this->rateGridSnapshot($contractId) : [];
        $this->db->query('DELETE FROM hr2_season WHERE contract_id = ?', [$contractId]); // cascades dates + rates
        $seasonIds = []; // index => id ; lower name => id
        foreach ($sheet['seasons'] as $si => $s) {
            $this->db->query('INSERT INTO hr2_season (contract_id, name, kind, min_stay, max_rooms, sort_order, notes) VALUES (?, ?, ?, ?, ?, ?, ?)', [$contractId, $s['name'], $s['kind'], $s['min_stay'], $s['max_rooms'], $si, $s['notes']]);
            $sid = (int) $this->db->insertID();
            $seasonIds[$si] = $sid;
            if ($s['name'] !== null) {
                $seasonIds['n:' . mb_strtolower($s['name'])] = $sid;
            }
            foreach ($s['dates'] as $d) {
                if (!$d['from'] || !$d['to']) {
                    continue; // reported by the validator
                }
                $f = $d['from']; $t = $d['to'];
                if ($f > $t) {
                    [$f, $t] = [$t, $f];
                }
                $this->db->query('INSERT INTO hr2_season_date (season_id, date_from, date_to) VALUES (?, ?, ?)', [$sid, $f, $t]);
            }
            $seen = [];
            foreach ($s['rates'] as $r) {
                $meal = $r['meal_plan'];
                if (!in_array($meal, Sheet::MEAL_PLANS, true)) {
                    if ($c['default_meal_plan'] && ($meal === '' || $meal === null)) {
                        $meal = $c['default_meal_plan'];
                    } else {
                        continue;
                    }
                }
                $rid = $roomId($r['room_type'], $r['room_type_id']);
                if (!$rid) {
                    continue;
                }
                $vals = [];
                foreach (['sgl', 'dbl', 'tpl', 'quad', 'unit', 'child_with_bed', 'child_no_bed'] as $k) {
                    $v = $r[$k];
                    $vals[$k] = ($v === null || $v <= 0) ? null : round($v, 2);
                }
                if ($vals['sgl'] === null && $vals['dbl'] === null && $vals['tpl'] === null && $vals['quad'] === null && $vals['unit'] === null) {
                    continue; // empty row = not offered
                }
                $key = $rid . '|' . $meal;
                if (isset($seen[$key])) {
                    continue; // duplicate row; flagged by the validator
                }
                $seen[$key] = true;
                $flags = self::rowFlags($vals, $c['pricing_mode'], (float) $this->settings()->get('tiny_rate'), (float) $this->settings()->get('huge_rate'));
                $this->db->query(
                    'INSERT INTO hr2_rate (season_id, room_type_id, meal_plan, sgl, dbl, tpl, quad, unit, child_with_bed, child_no_bed, quality_flags, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$sid, $rid, $meal, $vals['sgl'], $vals['dbl'], $vals['tpl'], $vals['quad'], $vals['unit'], $vals['child_with_bed'], $vals['child_no_bed'], $flags ?: null, $r['note']]
                );
            }
        }
        $seasonRef = static function ($ref) use ($seasonIds): ?int {
            if ($ref === null || $ref === '') {
                return null;
            }
            if (is_numeric($ref) && isset($seasonIds[(int) $ref])) {
                return $seasonIds[(int) $ref];
            }
            return $seasonIds['n:' . mb_strtolower((string) $ref)] ?? null;
        };

        // ---- child tables: replace -------------------------------------------
        foreach (['hr2_room_supplement', 'hr2_meal_delta', 'hr2_meal_price', 'hr2_supplement', 'hr2_child_rate', 'hr2_guide_policy', 'hr2_foc_rule', 'hr2_offer', 'hr2_cancellation_rule', 'hr2_payment_rule', 'hr2_restriction', 'hr2_extra'] as $t) {
            $this->db->query("DELETE FROM {$t} WHERE contract_id = ?", [$contractId]);
        }
        foreach ($sheet['room_supplements'] as $rs) {
            $rid = $roomId($rs['room_type'], $rs['room_type_id']);
            if (!$rid || $rs['amount'] === null) {
                continue;
            }
            $this->db->query('INSERT INTO hr2_room_supplement (contract_id, room_type_id, season_id, amount, per, note) VALUES (?, ?, ?, ?, ?, ?)', [$contractId, $rid, $seasonRef($rs['season']), round($rs['amount'], 2), $rs['per'], $rs['note']]);
            // a room priced through a supplement is marked as such (its base is the plan's base room if not yet set)
            $rt = $this->row('SELECT priced_as, base_room_type_id FROM hr2_room_type WHERE id = ?', [$rid]);
            if ($rt && $rt['priced_as'] !== 'supplement' && empty($rt['base_room_type_id'])) {
                $base = $this->guessBaseRoom($contractId, $rid);
                if ($base) {
                    $this->db->query("UPDATE hr2_room_type SET priced_as = 'supplement', base_room_type_id = ? WHERE id = ?", [$base, $rid]);
                }
            }
        }
        foreach ($sheet['meal_deltas'] as $d) {
            if (!$d['from'] || !$d['to'] || $d['adult'] === null || $d['from'] === $d['to']) {
                continue;
            }
            $this->db->query('INSERT INTO hr2_meal_delta (contract_id, from_plan, to_plan, adult, child, season_id, note) VALUES (?, ?, ?, ?, ?, ?, ?)', [$contractId, $d['from'], $d['to'], round($d['adult'], 2), $d['child'] === null ? null : round($d['child'], 2), $seasonRef($d['season']), $d['note']]);
        }
        foreach ($sheet['meal_prices'] as $m) {
            if ($m['adult'] === null) {
                continue;
            }
            $this->db->query('INSERT INTO hr2_meal_price (contract_id, meal, name, adult, child, note) VALUES (?, ?, ?, ?, ?, ?)', [$contractId, $m['meal'], $m['name'], round($m['adult'], 2), $m['child'] === null ? null : round($m['child'], 2), $m['note']]);
        }
        foreach ($sheet['supplements'] as $s) {
            $hasAmount = $s['amount'] !== null || $s['amount_sgl'] !== null || $s['amount_dbl'] !== null || $s['amount_tpl'] !== null || $s['pct_of_rate'] !== null;
            if (!$hasAmount) {
                continue;
            }
            $rid = $s['room_type'] ? $roomId($s['room_type'], $s['room_type_id']) : ($s['room_type_id'] ? $roomId('', $s['room_type_id']) : null);
            $this->db->query(
                'INSERT INTO hr2_supplement (contract_id, type, name, date_from, date_to, season_id, applies_meal_plans, room_type_id, per, amount, amount_sgl, amount_dbl, amount_tpl, child_amount, child_pct, pct_of_rate, compulsory, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$contractId, $s['type'], $s['name'] !== '' ? $s['name'] : ucfirst(str_replace('_', ' ', $s['type'])), $s['date_from'], $s['date_to'], $seasonRef($s['season']), $s['meal_plans'], $rid, $s['per'], $s['amount'], $s['amount_sgl'], $s['amount_dbl'], $s['amount_tpl'], $s['child_amount'], $s['child_pct'], $s['pct_of_rate'], $s['compulsory'], $s['note']]
            );
        }
        foreach ($sheet['child_rates'] as $ch) {
            if ($ch['age_to'] === null || ($ch['amount'] === null && $ch['pct'] === null)) {
                continue;
            }
            $rid = $ch['room_type'] ? $roomId($ch['room_type']) : null;
            $this->db->query('INSERT INTO hr2_child_rate (contract_id, age_from, age_to, bed, meal_plan, amount, pct, pct_basis, max_children, room_type_id, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$contractId, $ch['age_from'], $ch['age_to'], $ch['bed'], $ch['meal_plan'], $ch['amount'], $ch['pct'], $ch['pct_basis'], $ch['max_children'], $rid, $ch['note']]);
        }
        foreach ($sheet['guide_policies'] as $g) {
            $this->db->query('INSERT INTO hr2_guide_policy (contract_id, role, meal_plan, amount, currency, pct_of, pct, per, free_from_pax, min_rooms, accommodation, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$contractId, $g['role'], $g['meal_plan'], $g['amount'], $g['currency'], $g['pct_of'], $g['pct'], $g['per'], $g['free_from_pax'], $g['min_rooms'], $g['accommodation'], $g['note']]);
        }
        foreach ($sheet['foc_rules'] as $f) {
            if (!$f['every_n']) {
                continue;
            }
            $this->db->query('INSERT INTO hr2_foc_rule (contract_id, basis, every_n, free_n, discount_pct, as_what, note) VALUES (?, ?, ?, ?, ?, ?, ?)', [$contractId, $f['basis'], $f['every_n'], $f['free_n'], $f['discount_pct'], $f['as_what'], $f['note']]);
        }
        foreach ($sheet['offers'] as $o) {
            if ($o['name'] === '') {
                continue;
            }
            $this->db->query('INSERT INTO hr2_offer (contract_id, kind, name, book_from, book_to, stay_from, stay_to, min_nights, min_pax, min_rooms, value_pct, value_amount, free_nights, per, combinable, excludes_peak, conditions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$contractId, $o['kind'], $o['name'], $o['book_from'], $o['book_to'], $o['stay_from'], $o['stay_to'], $o['min_nights'], $o['min_pax'], $o['min_rooms'], $o['value_pct'], $o['value_amount'], $o['free_nights'], $o['per'], $o['combinable'], $o['excludes_peak'], $o['conditions']]);
        }
        foreach ($sheet['cancellation_rules'] as $i => $r) {
            if ($r['charge_pct'] === null && $r['charge_nights'] === null) {
                continue;
            }
            $this->db->query('INSERT INTO hr2_cancellation_rule (contract_id, date_from, date_to, tier, min_rooms, max_rooms, min_pax, max_pax, days_from, days_to, charge_pct, charge_nights, note, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$contractId, $r['date_from'], $r['date_to'], $r['tier'], $r['min_rooms'], $r['max_rooms'], $r['min_pax'], $r['max_pax'], $r['days_from'], $r['days_to'], $r['charge_pct'], $r['charge_nights'], $r['note'], $i]);
        }
        foreach ($sheet['payment_rules'] as $p) {
            $this->db->query('INSERT INTO hr2_payment_rule (contract_id, applies, kind, days_before, pct, date_from, date_to, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$contractId, $p['applies'], $p['kind'], $p['days_before'], $p['pct'], $p['date_from'], $p['date_to'], $p['note']]);
        }
        foreach ($sheet['restrictions'] as $r) {
            if (!$r['date_from'] || !$r['date_to']) {
                continue;
            }
            $value = $r['kind'] === 'meal_plan_required' ? Sheet::mealList($r['value']) : $r['value'];
            $this->db->query('INSERT INTO hr2_restriction (contract_id, kind, date_from, date_to, value, note) VALUES (?, ?, ?, ?, ?, ?)', [$contractId, $r['kind'], min($r['date_from'], $r['date_to']), max($r['date_from'], $r['date_to']), $value, $r['note']]);
        }
        foreach ($sheet['extras'] as $x) {
            $this->db->query('INSERT INTO hr2_extra (contract_id, category, name, per, adult, child, currency, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$contractId, $x['category'], $x['name'], $x['per'], $x['adult'], $x['child'], $x['currency'], $x['note']]);
        }

        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('Saving the plan failed; nothing was written.');
        }
        $this->lookupEngine()->forget($hotelId);
        if ($wasExisting) {
            $this->auditRateChanges($contractId, $hotelId, $gridBefore, $opts['import_id'] ?? null);
        }

        $approved = false;
        if (!empty($opts['approve']) && !$hasErrors) {
            $res = $this->approveContract($contractId, $opts['import_id'] ?? null, true);
            $approved = $res['approved'];
            $issues = $res['issues'];
        } elseif ($wasApproved) {
            $this->projectHotel($hotelId);
        }
        return ['contract_id' => $contractId, 'issues' => $issues, 'saved' => true, 'approved' => $approved];
    }

    /** For a room supplement row without a base: the plan's most common base room (first room with rates). */
    private function guessBaseRoom(int $contractId, int $roomId): ?int
    {
        $r = $this->row("SELECT r.room_type_id, COUNT(*) AS n FROM hr2_rate r JOIN hr2_season s ON s.id = r.season_id JOIN hr2_room_type rt ON rt.id = r.room_type_id
                          WHERE s.contract_id = ? AND r.room_type_id <> ? AND rt.priced_as = 'base' GROUP BY r.room_type_id ORDER BY n DESC, r.room_type_id LIMIT 1", [$contractId, $roomId]);
        return $r ? (int) $r['room_type_id'] : null;
    }

    /** Approve a draft: final validation, status change, legacy write-through. */
    public function approveContract(int $contractId, ?int $importId = null, bool $acknowledged = false): array
    {
        $sheet = $this->getContract($contractId);
        if (!$sheet) {
            throw new \RuntimeException("Plan {$contractId} not found.");
        }
        $issues = $this->validateSheet($sheet);
        if (RateValidator::hasErrors($issues)) {
            return ['approved' => false, 'issues' => $issues];
        }
        if (!$acknowledged && $issues) {
            return ['approved' => false, 'issues' => $issues, 'needs_acknowledgement' => true];
        }
        $old = $sheet['meta']['status'];
        $this->db->query('UPDATE hr2_contract SET status = \'approved\', approved_at = ?, approved_by = ?, checked_by = ?, updated_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $this->user, $this->user, date('Y-m-d H:i:s'), $contractId]);
        $this->audit('contract', $contractId, 'approve', ['status' => $old], ['status' => 'approved'], $importId);
        $hotelId = (int) $sheet['hotel']['id'];
        $this->lookupEngine()->forget($hotelId);
        $written = $this->projectHotel($hotelId);
        try {
            $wq = new WorkQueueService($this->db, $this->user, $this);
            $wq->closeByKey('draft:' . $contractId, 'Approved by ' . $this->user);
            $wq->scanIfDue((int) ($this->settings()->get('work_scan_minutes') ?? 10));
        } catch (\Throwable $e) {
            // the work queue never blocks an approval
        }
        return ['approved' => true, 'issues' => $issues, 'legacy' => $written, 'applies_to' => $sheet['contract']['applies_to'] ?? 'both'];
    }

    /** Retire an approved plan; the hotel's legacy rows are rebuilt without it. */
    public function supersedeContract(int $contractId, ?int $byContractId = null): void
    {
        $c = $this->row('SELECT * FROM hr2_contract WHERE id = ?', [$contractId]);
        if (!$c) {
            throw new \RuntimeException("Plan {$contractId} not found.");
        }
        $this->db->query('UPDATE hr2_contract SET status = \'superseded\', superseded_by = ?, updated_at = ? WHERE id = ?', [$byContractId, date('Y-m-d H:i:s'), $contractId]);
        $this->audit('contract', $contractId, 'supersede', ['status' => $c['status']], ['status' => 'superseded', 'by' => $byContractId]);
        $this->lookupEngine()->forget((int) $c['hotel_id']);
        $this->projectHotel((int) $c['hotel_id']);
    }

    public function deleteContract(int $contractId): void
    {
        $c = $this->row('SELECT * FROM hr2_contract WHERE id = ?', [$contractId]);
        if (!$c) {
            return;
        }
        if ($c['status'] === 'approved') {
            throw new \RuntimeException('Approved plans are never deleted. Mark it as superseded instead.');
        }
        if ($this->row('SELECT id FROM hr2_contract WHERE parent_contract_id = ?', [$contractId])) {
            throw new \RuntimeException('Other plans use this one as their parent. Re-point them first.');
        }
        $this->db->query('DELETE FROM hr2_contract WHERE id = ?', [$contractId]); // children cascade
        $this->audit('contract', $contractId, 'delete', $c, null);
        $this->lookupEngine()->forget((int) $c['hotel_id']);
        try {
            (new WorkQueueService($this->db, $this->user, $this))->closeByKey('draft:' . $contractId, 'Draft deleted by ' . $this->user);
        } catch (\Throwable $e) {
            // never blocks
        }
    }

    /** Copy a plan into a new draft, optionally shifting every date by N years (next season's sheet). */
    public function copyContract(int $contractId, int $shiftYears = 0, ?string $newName = null): int
    {
        $sheet = $this->getContract($contractId);
        if (!$sheet) {
            throw new \RuntimeException("Plan {$contractId} not found.");
        }
        unset($sheet['meta']);
        $sheet['contract']['id'] = null;
        $sheet['contract']['status'] = 'draft';
        $sheet['contract']['source_type'] = 'manual';
        $sheet['contract']['name'] = $newName ?: ($sheet['contract']['name'] . ' (copy)');
        $sheet['contract']['received_at'] = null;
        $sheet['contract']['contract_date'] = null;
        if ($shiftYears !== 0) {
            $shift = static function ($d) use ($shiftYears) {
                if (!$d) {
                    return $d;
                }
                $dt = new \DateTime($d);
                $dt->modify(($shiftYears > 0 ? '+' : '') . $shiftYears . ' years');
                return $dt->format('Y-m-d');
            };
            foreach (['valid_from', 'valid_to', 'book_from', 'book_to'] as $k) {
                $sheet['contract'][$k] = $shift($sheet['contract'][$k]);
            }
            foreach ($sheet['seasons'] as &$s) {
                foreach ($s['dates'] as &$d) {
                    $d['from'] = $shift($d['from']); $d['to'] = $shift($d['to']);
                }
                unset($d);
                if ($s['name']) {
                    $s['name'] = preg_replace_callback('/\b(20\d\d)(?:\/(\d\d))?\b/', static function ($m) use ($shiftYears) {
                        $y = (int) $m[1] + $shiftYears;
                        return isset($m[2]) ? $y . '/' . substr((string) ($y + 1), 2) : (string) $y;
                    }, $s['name']);
                }
            }
            unset($s);
            foreach (['supplements' => ['date_from', 'date_to'], 'offers' => ['book_from', 'book_to', 'stay_from', 'stay_to'], 'cancellation_rules' => ['date_from', 'date_to'], 'payment_rules' => ['date_from', 'date_to'], 'restrictions' => ['date_from', 'date_to']] as $list => $keys) {
                foreach ($sheet[$list] as &$r) {
                    foreach ($keys as $k) {
                        $r[$k] = $shift($r[$k]);
                    }
                }
                unset($r);
            }
            $sheet['contract']['name'] = preg_replace_callback('/\b(20\d\d)(?:\/(\d\d))?\b/', static function ($m) use ($shiftYears) {
                $y = (int) $m[1] + $shiftYears;
                return isset($m[2]) ? $y . '/' . substr((string) ($y + 1), 2) : (string) $y;
            }, $newName ?: $sheet['contract']['name']);
        }
        $res = $this->saveContract($sheet, ['approve' => false]);
        if (!$res['saved']) {
            throw new \RuntimeException('Copy failed.');
        }
        return (int) $res['contract_id'];
    }

    // =====================================================================
    //  Lookup, coverage, legacy projection
    // =====================================================================

    /**
     * Rate for one night. $hotelAcc is the legacy supplier code (tour_quotations.hotel_acc).
     * $roomRef: legacy room_type.id, 'hr2:<id>' or a room name. Options: see RateLookup::price().
     */
    public function lookup(string $hotelAcc, $roomRef, string $mealPlan, string $date, array $opts = []): ?array
    {
        $hotel = $this->findHotelByAcc($hotelAcc);
        if (!$hotel) {
            return null;
        }
        return $this->lookupEngine()->price((int) $hotel['id'], $date, $roomRef, $mealPlan, $opts);
    }

    public function lookupById(int $hotelId, $roomRef, string $mealPlan, string $date, array $opts = []): ?array
    {
        return $this->lookupEngine()->price($hotelId, $date, $roomRef, $mealPlan, $opts);
    }

    /**
     * Coverage per room type between two dates: which plan prices each elementary
     * interval (for the default market), plus the gaps.
     */
    public function coverage(int $hotelId, string $from, string $to, array $opts = []): array
    {
        $engine = $this->lookupEngine();
        $engine->forget($hotelId);
        $b = $engine->bundle($hotelId);
        $intervals = $engine->intervals($b, $from, $to);
        $rooms = [];
        $roomIds = [];
        foreach ($b['plans'] as $p) {
            if ($p['status'] !== 'approved') {
                continue;
            }
            foreach ($p['rates'] as $bySeason) {
                foreach ($bySeason as $rid => $x) {
                    $roomIds[$rid] = true;
                }
            }
            foreach ($p['room_supplements'] as $rs) {
                $roomIds[(int) $rs['room_type_id']] = true;
            }
        }
        foreach (array_keys($roomIds) as $rid) {
            if (!isset($b['room_types'][$rid])) {
                continue;
            }
            $name = $b['room_types'][$rid]['name'];
            $segments = [];
            $covered = [];
            foreach ($intervals as [$f, $t]) {
                $best = null;
                foreach (['HB', 'BB', 'FB', 'RO', 'AI'] as $meal) {
                    $r = $engine->price($hotelId, $f, 'hr2:' . $rid, $meal, $opts);
                    if ($r) {
                        $best = $r;
                        break;
                    }
                }
                if ($best) {
                    $covered[] = [$f, $t];
                    $meals = [];
                    foreach (Sheet::MEAL_PLANS as $meal) {
                        if ($engine->price($hotelId, $f, 'hr2:' . $rid, $meal, $opts)) {
                            $meals[] = $meal;
                        }
                    }
                    $segments[] = ['date_from' => $f, 'date_to' => $t, 'plan' => $best['plan']['name'], 'plan_id' => $best['plan']['id'], 'plan_type' => $best['plan']['plan_type'], 'meal_plans' => implode(',', $meals), 'dbl' => $best['dbl'] ?? $best['unit'], 'derived' => $best['derived']];
                }
            }
            // merge adjacent segments of the same plan
            $merged = [];
            foreach ($segments as $seg) {
                $last = $merged ? $merged[count($merged) - 1] : null;
                if ($last && $last['plan_id'] === $seg['plan_id'] && $last['meal_plans'] === $seg['meal_plans'] && Dates::next($last['date_to']) === $seg['date_from']) {
                    $merged[count($merged) - 1]['date_to'] = $seg['date_to'];
                } else {
                    $merged[] = $seg;
                }
            }
            $rooms[$name] = ['room_type_id' => $rid, 'priced_as' => $b['room_types'][$rid]['priced_as'], 'segments' => $merged, 'gaps' => Dates::gaps($covered, $from, $to)];
        }
        return ['from' => $from, 'to' => $to, 'market' => $opts['market'] ?? $engine->defaultMarket(), 'rooms' => $rooms, 'plans' => array_values(array_map(static fn ($p) => ['id' => (int) $p['id'], 'name' => $p['name'], 'plan_type' => $p['plan_type'], 'tier' => $p['tier'], 'valid_from' => $p['valid_from'], 'valid_to' => $p['valid_to'], 'book_to' => $p['book_to']], array_filter($b['plans'], static fn ($p) => $p['status'] === 'approved' && ($p['applies_to'] ?? 'both') !== 'payment')))];
    }

    /**
     * Every rate the hotel has for ONE night: room type x meal plan -> SGL/DBL/TPL/QUAD/UNIT,
     * the plan and season each cell comes from, the supplements and restrictions of that
     * night, and — when nothing is approved for the date — the nearest approved plans and
     * any draft that would cover it. Options: market | country, booking_date, rooms, pax, tier.
     */
    public function ratesOn(int $hotelId, string $date, array $opts = []): array
    {
        $engine = $this->lookupEngine();
        $engine->forget($hotelId);
        $b = $engine->bundle($hotelId, true);
        $market = $engine->resolveMarket($opts);
        $bookingDate = $opts['booking_date'] ?? date('Y-m-d');
        $out = ['date' => $date, 'market' => $market, 'booking_date' => $bookingDate, 'rooms' => (int) ($opts['rooms'] ?? 1), 'available' => false,
            'grid' => [], 'plans' => [], 'supplements' => [], 'restrictions' => [], 'meal_prices' => [], 'child_rates' => [], 'guide_policies' => [],
            'pricing_mode' => null, 'currency' => null, 'taxes_included' => null, 'tax_pct' => null, 'unpriced_rooms' => [], 'nearest' => ['previous' => null, 'next' => null], 'drafts' => [], 'other_market_plans' => [], 'adhoc' => []];
        if (!$b['hotel']) {
            return $out;
        }
        $lookupOpts = $opts;
        unset($lookupOpts['include_drafts']);
        $plansUsed = [];
        $suppSeen = [];
        $restrSeen = [];
        $primary = null; // the first priced cell: its plan gives the night's policies
        $rooms = array_values($b['room_types']);
        usort($rooms, static fn ($x, $y) => [(int) $x['sort_order'], $x['name']] <=> [(int) $y['sort_order'], $y['name']]);
        foreach ($rooms as $rt) {
            if (($rt['status'] ?? 'active') !== 'active') {
                continue;
            }
            $rid = (int) $rt['id'];
            $row = ['room_type_id' => $rid, 'name' => $rt['name'], 'priced_as' => $rt['priced_as'], 'base_room_type' => $rt['base_room_type_id'] ? ($b['room_types'][(int) $rt['base_room_type_id']]['name'] ?? null) : null,
                'max_adults' => $rt['max_adults'], 'unit_pax_max' => $rt['unit_pax_max'], 'rates' => []];
            $any = false;
            foreach (Sheet::MEAL_PLANS as $meal) {
                $r = $engine->price($hotelId, $date, 'hr2:' . $rid, $meal, $lookupOpts);
                if (!$r) {
                    $row['rates'][$meal] = null;
                    continue;
                }
                $any = true;
                $pid = (int) $r['plan']['id'];
                $row['rates'][$meal] = ['sgl' => $r['sgl'], 'dbl' => $r['dbl'], 'tpl' => $r['tpl'], 'quad' => $r['quad'], 'unit' => $r['unit'],
                    'child_with_bed' => $r['child_with_bed'] ?? null, 'child_no_bed' => $r['child_no_bed'] ?? null,
                    'derived' => $r['derived'], 'components' => $r['components'], 'plan_id' => $pid, 'season' => $r['season']['name'], 'pricing_mode' => $r['pricing_mode'], 'quality_flags' => $r['quality_flags'] ?? null];
                if (!isset($plansUsed[$pid])) {
                    $plansUsed[$pid] = ['id' => $pid, 'name' => $r['plan']['name'], 'plan_type' => $r['plan']['plan_type'], 'rate_code' => $r['plan']['rate_code'], 'tier' => $r['plan']['tier'], 'priority' => $r['plan']['priority'], 'voucher_note' => $r['plan']['voucher_note'],
                        'season' => $r['season']['name'], 'season_from' => $r['season']['date_from'], 'season_to' => $r['season']['date_to'], 'season_kind' => $r['season']['kind'], 'min_stay' => $r['season']['min_stay'], 'seasons' => [],
                        'pricing_mode' => $r['pricing_mode'], 'currency' => $r['currency'], 'taxes_included' => $r['taxes_included'], 'tax_pct' => $r['tax_pct'],
                        'valid_from' => $b['plans'][$pid]['valid_from'], 'valid_to' => $b['plans'][$pid]['valid_to'], 'book_to' => $b['plans'][$pid]['book_to'], 'markets' => $b['plans'][$pid]['markets'], 'other_plans' => $r['other_plans']];
                }
                $sk = (int) $r['season']['id'];
                if (!isset($plansUsed[$pid]['seasons'][$sk])) {
                    // several seasons of one plan can price the same night (legacy data copied as it was); list each once
                    $plansUsed[$pid]['seasons'][$sk] = ['name' => $r['season']['name'], 'date_from' => $r['season']['date_from'], 'date_to' => $r['season']['date_to'], 'kind' => $r['season']['kind'], 'min_stay' => $r['season']['min_stay']];
                }
                foreach ($r['supplements'] as $s) {
                    $k = $s['id'] ?? ($s['name'] . '|' . $s['applies_meal_plans'] . '|' . $s['amount']);
                    if (!isset($suppSeen[$k])) {
                        $suppSeen[$k] = true;
                        $out['supplements'][] = ['name' => $s['name'], 'type' => $s['type'], 'meal_plans' => $s['applies_meal_plans'], 'per' => $s['per'], 'amount' => $s['amount'], 'amount_sgl' => $s['amount_sgl'], 'amount_dbl' => $s['amount_dbl'], 'amount_tpl' => $s['amount_tpl'],
                            'child_amount' => $s['child_amount'], 'child_pct' => $s['child_pct'], 'pct_of_rate' => $s['pct_of_rate'], 'compulsory' => (int) $s['compulsory'], 'room_type' => $s['room_type_id'] ? ($b['room_types'][(int) $s['room_type_id']]['name'] ?? null) : null, 'note' => $s['note'], 'plan_id' => $pid];
                    }
                }
                foreach ($r['restrictions'] as $x) {
                    $k = ($x['id'] ?? '') . '|' . $x['kind'] . '|' . $x['date_from'] . '|' . $x['value'];
                    if (!isset($restrSeen[$k])) {
                        $restrSeen[$k] = true;
                        $out['restrictions'][] = ['kind' => $x['kind'], 'date_from' => $x['date_from'], 'date_to' => $x['date_to'], 'value' => $x['value'], 'note' => $x['note'], 'plan_id' => $pid];
                    }
                }
                if ($primary === null) {
                    $primary = $r;
                }
            }
            if ($any) {
                $out['grid'][] = $row;
            } else {
                $out['unpriced_rooms'][] = $rt['name'];
            }
        }
        // ad hoc (payment-only) rates for the same night: what the hotel is paid when it differs from the quotation rate.
        // The payment side sees every plan whatever kinds the quotation was kept to.
        $payOpts = $lookupOpts + ['purpose' => 'payment'];
        unset($payOpts['plan_types']);
        // tf-offers-r1: a tactical or early bird is never paid by itself (an authorized person applies it at payment),
        // so this strip shows the ad hoc payment rates only
        if ((new OfferRates($this->db, $this))->ready()) {
            $payOpts['plan_types'] = array_values(array_diff(Sheet::PLAN_TYPES, ['tactical', 'early_bird']));
        }
        foreach ($out['grid'] as &$row) {
            foreach (Sheet::MEAL_PLANS as $meal) {
                $q = $row['rates'][$meal];
                $pr = $engine->price($hotelId, $date, 'hr2:' . $row['room_type_id'], $meal, $payOpts);
                if (!$pr || ($pr['plan']['applies_to'] ?? 'both') !== 'payment' || OfferRates::kindOf($pr['plan']) !== null) {
                    continue; // paid at the same rate the quotation uses
                }
                $entry = ['room_type' => $row['name'], 'room_type_id' => $row['room_type_id'], 'meal_plan' => $meal, 'plan_id' => (int) $pr['plan']['id'], 'plan' => $pr['plan']['name'], 'season' => $pr['season']['name'],
                    'pay' => ['sgl' => $pr['sgl'], 'dbl' => $pr['dbl'], 'tpl' => $pr['tpl'], 'quad' => $pr['quad'], 'unit' => $pr['unit']],
                    'quote' => $q ? ['sgl' => $q['sgl'], 'dbl' => $q['dbl'], 'tpl' => $q['tpl'], 'quad' => $q['quad'], 'unit' => $q['unit']] : null,
                    'margin' => null];
                if ($q) {
                    $entry['margin'] = [];
                    foreach (['sgl', 'dbl', 'tpl', 'quad', 'unit'] as $occ) {
                        $entry['margin'][$occ] = ($q[$occ] !== null && $pr[$occ] !== null) ? round($q[$occ] - $pr[$occ], 2) : null;
                    }
                }
                $out['adhoc'][] = $entry;
                if ($row['rates'][$meal] !== null) {
                    $row['rates'][$meal]['adhoc'] = ['sgl' => $pr['sgl'], 'dbl' => $pr['dbl'], 'tpl' => $pr['tpl'], 'quad' => $pr['quad'], 'unit' => $pr['unit'], 'plan' => $pr['plan']['name']];
                }
            }
        }
        unset($row);
        // a payment-only plan can also price rooms the quotation has no rate for at all
        foreach ($out['unpriced_rooms'] as $i => $roomName) {
            $rid = $b['room_by_name'][mb_strtolower($roomName)] ?? null;
            if (!$rid) {
                continue;
            }
            foreach (Sheet::MEAL_PLANS as $meal) {
                $pr = $engine->price($hotelId, $date, 'hr2:' . $rid, $meal, $payOpts);
                if ($pr && ($pr['plan']['applies_to'] ?? 'both') === 'payment' && OfferRates::kindOf($pr['plan']) === null) {
                    $out['adhoc'][] = ['room_type' => $roomName, 'room_type_id' => $rid, 'meal_plan' => $meal, 'plan_id' => (int) $pr['plan']['id'], 'plan' => $pr['plan']['name'], 'season' => $pr['season']['name'],
                        'pay' => ['sgl' => $pr['sgl'], 'dbl' => $pr['dbl'], 'tpl' => $pr['tpl'], 'quad' => $pr['quad'], 'unit' => $pr['unit']], 'quote' => null, 'margin' => null];
                }
            }
        }
        if ($primary) {
            $out['available'] = true;
            $out['pricing_mode'] = $primary['pricing_mode'];
            $out['currency'] = $primary['currency'];
            $out['taxes_included'] = $primary['taxes_included'];
            $out['tax_pct'] = $primary['tax_pct'];
            $out['meal_prices'] = $primary['meal_prices'];
            $out['child_rates'] = $primary['child_rates'];
            $out['guide_policies'] = $primary['guide_policies'];
        }
        foreach ($plansUsed as &$pu) {
            $pu['seasons'] = array_values($pu['seasons']);
        }
        unset($pu);
        $out['plans'] = array_values($plansUsed);

        // what else touches this date: drafts, plans for other markets, and the nearest approved plans around the date
        $prev = null; $next = null;
        foreach ($b['plans'] as $pid => $p) {
            $covers = $p['valid_from'] <= $date && $p['valid_to'] >= $date;
            if ($p['status'] === 'draft') {
                if ($covers) {
                    $out['drafts'][] = ['id' => (int) $pid, 'name' => $p['name'], 'plan_type' => $p['plan_type'], 'valid_from' => $p['valid_from'], 'valid_to' => $p['valid_to']];
                }
                continue;
            }
            if ($p['status'] !== 'approved') {
                continue;
            }
            if ($covers && !isset($plansUsed[(int) $pid])) {
                $why = [];
                $types = isset($lookupOpts['plan_types']) && is_array($lookupOpts['plan_types']) && $lookupOpts['plan_types'] !== [] ? array_map('strtolower', $lookupOpts['plan_types']) : null;
                if ($types !== null && !in_array(strtolower((string) $p['plan_type']), $types, true)) {
                    $why[] = 'a ' . str_replace('_', ' ', (string) $p['plan_type']) . ' plan, not quoted';
                }
                $inc = $p['markets']['include'] ?? []; $exc = $p['markets']['exclude'] ?? [];
                if (($inc && !in_array($market, $inc, true)) || in_array($market, $exc, true)) {
                    $why[] = 'markets ' . ($inc ? implode(', ', $inc) : 'all') . ($exc ? ' −' . implode(', −', $exc) : '');
                }
                if (($p['book_from'] && $bookingDate < $p['book_from']) || ($p['book_to'] && $bookingDate > $p['book_to'])) {
                    $why[] = 'booking window ' . ($p['book_from'] ?: '…') . ' → ' . ($p['book_to'] ?: '…');
                }
                if ($p['tier'] !== 'all') {
                    $why[] = 'tier ' . $p['tier'] . ($p['tier_min_rooms'] || $p['tier_max_rooms'] ? ' (' . ($p['tier_min_rooms'] ?: 1) . '–' . ($p['tier_max_rooms'] ?: '∞') . ' rooms)' : '');
                }
                $inSeason = false;
                foreach ($p['seasons'] as $s) {
                    foreach ($s['dates'] as [$f, $t]) {
                        if ($f <= $date && $t >= $date) {
                            $inSeason = true;
                        }
                    }
                }
                if (!$inSeason) {
                    $why[] = 'no season covers the date';
                } elseif (!$why) {
                    $why[] = $primary ? 'a higher-priority plan wins' : 'no rate row for these room types';
                }
                $out['other_market_plans'][] = ['id' => (int) $pid, 'name' => $p['name'], 'plan_type' => $p['plan_type'], 'why' => implode('; ', $why)];
            }
            foreach ($p['seasons'] as $s) {
                foreach ($s['dates'] as [$f, $t]) {
                    if ($t < $date && ($prev === null || $t > $prev['date'])) {
                        $prev = ['date' => $t, 'plan' => $p['name'], 'plan_id' => (int) $pid];
                    }
                    if ($f > $date && ($next === null || $f < $next['date'])) {
                        $next = ['date' => $f, 'plan' => $p['name'], 'plan_id' => (int) $pid];
                    }
                }
            }
        }
        $out['nearest'] = ['previous' => $prev, 'next' => $next];
        $out['flags'] = $this->nightFlags($date, $out['plans'], $out['supplements'], $out['restrictions'], $out['available']);
        foreach ($out['adhoc'] as $a) {
            $pay = $a['pay']['dbl'] ?? $a['pay']['unit'] ?? $a['pay']['sgl'];
            $quote = $a['quote'] ? ($a['quote']['dbl'] ?? $a['quote']['unit'] ?? $a['quote']['sgl']) : null;
            $out['flags'][] = ['kind' => 'adhoc', 'level' => 'green', 'name' => 'Ad hoc payment rate: ' . $a['room_type'] . ' ' . $a['meal_plan'], 'detail' => 'hotel is paid ' . number_format((float) $pay, 2) . ($quote !== null ? ' instead of ' . number_format((float) $quote, 2) . ' (margin ' . number_format((float) $quote - (float) $pay, 2) . ')' : '') . ' · ' . $a['plan'] . ' · customer price unchanged', 'amount' => $pay, 'per' => null, 'meal_plans' => $a['meal_plan'], 'compulsory' => null, 'key' => 'adhoc:' . $a['room_type_id'] . ':' . $a['meal_plan'] . ':' . $pay];
        }
        return $out;
    }

    /**
     * The things a person must not miss about one night, read from the data itself: gala
     * dinners, peak / festival surcharges and other compulsory supplements, festive or peak
     * seasons, minimum stays and meal-plan rules, nights with no rate, and the gala dates
     * from Settings when the plan records no gala for them. Each flag:
     *   ['kind' => gala|peak|festival|supplement|season|restriction|no_rate|gala_unrecorded,
     *    'level' => 'red'|'amber'|'blue', 'name', 'detail', 'amount', 'per', 'meal_plans', 'compulsory', 'key']
     * 'key' identifies the same flag on consecutive nights so a range can merge them.
     */
    public function nightFlags(string $date, array $plans, array $supplements, array $restrictions, bool $available): array
    {
        $flags = [];
        if (!$available) {
            $flags[] = ['kind' => 'no_rate', 'level' => 'red', 'name' => 'No approved rate', 'detail' => 'nothing prices this night', 'amount' => null, 'per' => null, 'meal_plans' => null, 'compulsory' => null, 'key' => 'no_rate'];
            return $flags;
        }
        $galaSeen = false;
        foreach ($supplements as $sp) {
            $kind = in_array($sp['type'], ['gala', 'peak', 'festival'], true) ? $sp['type'] : 'supplement';
            if ($kind === 'gala') {
                $galaSeen = true;
            }
            $amount = $sp['amount'] !== null ? (float) $sp['amount'] : null;
            $detail = [];
            if ($amount !== null) {
                $detail[] = number_format($amount, 2) . ' per ' . $sp['per'];
            } elseif ($sp['amount_sgl'] !== null || $sp['amount_dbl'] !== null || $sp['amount_tpl'] !== null) {
                $detail[] = 'SGL ' . ($sp['amount_sgl'] ?? '—') . ' / DBL ' . ($sp['amount_dbl'] ?? '—') . ' / TPL ' . ($sp['amount_tpl'] ?? '—') . ' per ' . $sp['per'];
            } elseif ($sp['pct_of_rate'] !== null) {
                $detail[] = $sp['pct_of_rate'] . ' % of the rate';
            }
            if ($sp['meal_plans']) {
                $detail[] = str_replace(',', ', ', $sp['meal_plans']) . ' guests';
            }
            if ($sp['child_pct'] !== null) {
                $detail[] = 'child ' . (float) $sp['child_pct'] . ' %';
            } elseif ($sp['child_amount'] !== null) {
                $detail[] = 'child ' . number_format((float) $sp['child_amount'], 2);
            }
            if ($sp['room_type']) {
                $detail[] = $sp['room_type'] . ' only';
            }
            $flags[] = ['kind' => $kind, 'level' => (int) $sp['compulsory'] ? 'red' : 'blue', 'name' => $sp['name'], 'detail' => implode(' · ', $detail), 'amount' => $amount, 'per' => $sp['per'], 'meal_plans' => $sp['meal_plans'], 'compulsory' => (int) $sp['compulsory'], 'key' => 'supp:' . $kind . ':' . mb_strtolower($sp['name']) . ':' . ($amount ?? '') . ':' . ($sp['meal_plans'] ?? '')];
        }
        foreach ($plans as $p) {
            foreach ($p['seasons'] ?? [] as $se) {
                if (in_array($se['kind'], ['peak', 'festive'], true)) {
                    $flags[] = ['kind' => 'season', 'level' => 'amber', 'name' => ucfirst($se['kind']) . ' season: ' . $se['name'], 'detail' => $se['date_from'] . ' → ' . $se['date_to'] . ($se['min_stay'] ? ' · minimum stay ' . $se['min_stay'] . ' nights' : '') . ' (' . $p['name'] . ')', 'amount' => null, 'per' => null, 'meal_plans' => null, 'compulsory' => null, 'key' => 'season:' . $p['id'] . ':' . $se['name']];
                } elseif ($se['min_stay']) {
                    $flags[] = ['kind' => 'restriction', 'level' => 'amber', 'name' => 'Minimum stay ' . $se['min_stay'] . ' nights', 'detail' => $se['name'] . ' ' . $se['date_from'] . ' → ' . $se['date_to'], 'amount' => null, 'per' => null, 'meal_plans' => null, 'compulsory' => null, 'key' => 'minstay:' . $p['id'] . ':' . $se['name']];
                }
            }
        }
        $labels = ['min_stay' => 'Minimum stay', 'closed_to_arrival' => 'Closed to arrival', 'closed_to_departure' => 'Closed to departure', 'meal_plan_required' => 'Meal plan required', 'stop_sale' => 'Stop sale', 'other' => 'Rule'];
        foreach ($restrictions as $x) {
            $flags[] = ['kind' => 'restriction', 'level' => $x['kind'] === 'stop_sale' ? 'red' : 'amber', 'name' => ($labels[$x['kind']] ?? $x['kind']) . ($x['value'] ? ' ' . $x['value'] : ''), 'detail' => $x['date_from'] . ' → ' . $x['date_to'] . ($x['note'] ? ' · ' . $x['note'] : ''), 'amount' => null, 'per' => null, 'meal_plans' => null, 'compulsory' => null, 'key' => 'restr:' . $x['kind'] . ':' . $x['value'] . ':' . $x['date_from']];
        }
        if (!$galaSeen && in_array(substr($date, 5), $this->settings()->get('gala_dates'), true)) {
            $flags[] = ['kind' => 'gala_unrecorded', 'level' => 'amber', 'name' => 'Gala night — no gala dinner recorded', 'detail' => 'hotels normally charge a compulsory dinner on this date; check the contract', 'amount' => null, 'per' => null, 'meal_plans' => null, 'compulsory' => null, 'key' => 'gala_unrecorded'];
        }
        $level = ['red' => 0, 'amber' => 1, 'blue' => 2, 'green' => 3];
        $order = ['no_rate' => 0, 'gala' => 1, 'festival' => 2, 'peak' => 3, 'season' => 4, 'gala_unrecorded' => 5, 'restriction' => 6, 'supplement' => 7, 'adhoc' => 8];
        usort($flags, static fn ($a, $b) => [$level[$a['level']] ?? 3, $order[$a['kind']] ?? 9, $a['name']] <=> [$level[$b['level']] ?? 3, $order[$b['kind']] ?? 9, $b['name']]);
        return $flags;
    }

    /** Flags for one priced night as lookup() returns it (the quotation timeline's answer). */
    public function lookupFlags(?array $r, string $date): array
    {
        if (!$r) {
            return $this->nightFlags($date, [], [], [], false);
        }
        $b = $this->lookupEngine()->bundle((int) $r['hotel_id']);
        $supps = array_map(fn ($s) => ['name' => $s['name'], 'type' => $s['type'], 'meal_plans' => $s['applies_meal_plans'], 'per' => $s['per'], 'amount' => $s['amount'], 'amount_sgl' => $s['amount_sgl'], 'amount_dbl' => $s['amount_dbl'], 'amount_tpl' => $s['amount_tpl'], 'child_amount' => $s['child_amount'], 'child_pct' => $s['child_pct'], 'pct_of_rate' => $s['pct_of_rate'], 'compulsory' => (int) $s['compulsory'], 'room_type' => $s['room_type_id'] ? ($b['room_types'][(int) $s['room_type_id']]['name'] ?? null) : null], $r['supplements']);
        $plans = [['id' => $r['plan']['id'], 'name' => $r['plan']['name'], 'seasons' => [['name' => $r['season']['name'], 'date_from' => $r['season']['date_from'], 'date_to' => $r['season']['date_to'], 'kind' => $r['season']['kind'], 'min_stay' => $r['season']['min_stay']]]]];
        return $this->nightFlags($date, $plans, $supps, $r['restrictions'], true);
    }

    /**
     * ratesOn() for every night from $from to $to (inclusive, max 62 nights): the nights are
     * folded into segments with identical rates, every room type x meal plan gets a stay
     * total, and the flags of all nights are merged into date ranges — so a person sees at
     * once which nights of a stay fall into a gala, peak, festive or Perahera period.
     */
    public function ratesForRange(int $hotelId, string $from, string $to, array $opts = []): array
    {
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        if (Dates::diffDays($from, $to) > 61) {
            $to = date('Y-m-d', strtotime($from . ' +61 days'));
        }
        $nights = [];
        $segments = [];
        $totals = [];
        $flagRanges = [];
        $plansSeen = [];
        $missing = [];
        $first = null;
        for ($d = $from; $d <= $to; $d = Dates::next($d)) {
            $r = $this->ratesOn($hotelId, $d, $opts);
            $first = $first ?? $r;
            $nights[] = ['date' => $d, 'available' => $r['available'], 'plans' => array_map(static fn ($p) => $p['id'], $r['plans']), 'flags' => $r['flags']];
            if (!$r['available']) {
                $missing[] = $d;
            }
            // signature of the grid for this night: same values and plans => same segment
            $sig = [];
            foreach ($r['grid'] as $row) {
                foreach ($row['rates'] as $meal => $c) {
                    if ($c) {
                        $sig[] = $row['room_type_id'] . '|' . $meal . '|' . $c['sgl'] . '|' . $c['dbl'] . '|' . $c['tpl'] . '|' . $c['quad'] . '|' . $c['unit'] . '|' . $c['plan_id'];
                        $k = $row['name'] . '|' . $meal;
                        $totals[$k] = $totals[$k] ?? ['room_type' => $row['name'], 'room_type_id' => $row['room_type_id'], 'meal_plan' => $meal, 'sgl' => 0.0, 'dbl' => 0.0, 'tpl' => 0.0, 'quad' => 0.0, 'unit' => 0.0, 'nights' => 0, 'has' => ['sgl' => true, 'dbl' => true, 'tpl' => true, 'quad' => true, 'unit' => true]];
                        foreach (['sgl', 'dbl', 'tpl', 'quad', 'unit'] as $occ) {
                            if ($c[$occ] === null) {
                                $totals[$k]['has'][$occ] = false;
                            } else {
                                $totals[$k][$occ] += (float) $c[$occ];
                            }
                        }
                        $totals[$k]['nights']++;
                    }
                }
            }
            $sigStr = md5(implode(';', $sig));
            $last = $segments ? $segments[count($segments) - 1] : null;
            if ($last && $last['sig'] === $sigStr && Dates::next($last['to']) === $d) {
                $segments[count($segments) - 1]['to'] = $d;
                $segments[count($segments) - 1]['nights']++;
            } else {
                $segments[] = ['from' => $d, 'to' => $d, 'nights' => 1, 'sig' => $sigStr, 'available' => $r['available'], 'grid' => $r['grid'], 'plans' => $r['plans'], 'pricing_mode' => $r['pricing_mode'], 'currency' => $r['currency'], 'taxes_included' => $r['taxes_included'], 'tax_pct' => $r['tax_pct'], 'unpriced_rooms' => $r['unpriced_rooms'], 'nearest' => $r['nearest'], 'drafts' => $r['drafts'], 'other_market_plans' => $r['other_market_plans']];
            }
            foreach ($r['plans'] as $p) {
                $plansSeen[$p['id']] = ['id' => $p['id'], 'name' => $p['name'], 'plan_type' => $p['plan_type'], 'valid_from' => $p['valid_from'], 'valid_to' => $p['valid_to'], 'book_to' => $p['book_to']];
            }
            foreach ($r['flags'] as $f) {
                $lastF = isset($flagRanges[$f['key']]) ? $flagRanges[$f['key']][count($flagRanges[$f['key']]) - 1] : null;
                if ($lastF && Dates::next($lastF['to']) === $d) {
                    $flagRanges[$f['key']][count($flagRanges[$f['key']]) - 1]['to'] = $d;
                    $flagRanges[$f['key']][count($flagRanges[$f['key']]) - 1]['nights']++;
                } else {
                    $flagRanges[$f['key']][] = $f + ['from' => $d, 'to' => $d, 'nights' => 1];
                }
            }
        }
        $flags = [];
        foreach ($flagRanges as $list) {
            foreach ($list as $f) {
                $flags[] = $f;
            }
        }
        // compulsory / missing first, then seasons and rules, then optional extras — each in date order
        $level = ['red' => 0, 'amber' => 1, 'blue' => 2, 'green' => 3];
        $order = ['no_rate' => 0, 'gala' => 1, 'festival' => 2, 'peak' => 3, 'season' => 4, 'gala_unrecorded' => 5, 'restriction' => 6, 'supplement' => 7, 'adhoc' => 8];
        usort($flags, static fn ($a, $b) => [$level[$a['level']] ?? 3, $a['from'], $order[$a['kind']] ?? 9, $a['name']] <=> [$level[$b['level']] ?? 3, $b['from'], $order[$b['kind']] ?? 9, $b['name']]);
        foreach ($segments as &$seg) {
            unset($seg['sig']);
        }
        unset($seg);
        $totalsOut = [];
        $nightCount = count($nights);
        foreach ($totals as $t) {
            foreach (['sgl', 'dbl', 'tpl', 'quad', 'unit'] as $occ) {
                $t[$occ] = ($t['has'][$occ] && $t['nights'] === $nightCount) ? round($t[$occ], 2) : null;
            }
            $t['complete'] = $t['nights'] === $nightCount;
            unset($t['has']);
            $totalsOut[] = $t;
        }
        return ['mode' => 'range', 'from' => $from, 'to' => $to, 'nights' => $nightCount, 'market' => $first['market'] ?? null, 'booking_date' => $first['booking_date'] ?? null, 'rooms' => $first['rooms'] ?? 1,
            'available_nights' => $nightCount - count($missing), 'missing_nights' => $missing, 'segments' => $segments, 'totals' => $totalsOut, 'flags' => $flags, 'plans' => array_values($plansSeen), 'nights_detail' => $nights,
            'pricing_mode' => $first['pricing_mode'] ?? null, 'currency' => $first['currency'] ?? null, 'taxes_included' => $first['taxes_included'] ?? null, 'tax_pct' => $first['tax_pct'] ?? null];
    }

    /**
     * Rebuild the legacy hotel_rate rows of one hotel from its approved plans — and, because
     * every caller of this is a change to what the hotel is paid, refresh the stored hotel
     * costs of the confirmed tours that still have nights there.
     */
    public function projectHotel(int $hotelId): array
    {
        $this->lookupEngine()->forget($hotelId);
        $projector = new LegacyProjector($this->db, $this->lookupEngine(), $this->user);
        $res = $projector->projectHotel($hotelId);
        $this->audit('hotel', $hotelId, 'project', null, $res);
        try {
            $res['tours_recosted'] = (new HotelCostService($this->db, $this->user, $this))->refreshHotel($hotelId);
        } catch (\Throwable $e) {
            $res['tours_recosted'] = 0; // the tables may not exist yet on an old layout
        }
        return $res;
    }

    // =====================================================================
    //  Merging duplicate hotels
    // =====================================================================

    public function mergeSuggestions(): array
    {
        $hotels = $this->rows("SELECT h.id, h.name, h.primary_sup_acc_no, l.name AS location_name,
                COALESCE(c.n, 0) AS contracts, COALESCE(tq.n, 0) AS timeline_rows
            FROM hr2_hotel h LEFT JOIN hr2_location l ON l.id = h.location_id
            LEFT JOIN (SELECT hotel_id, COUNT(*) AS n FROM hr2_contract GROUP BY hotel_id) c ON c.hotel_id = h.id
            LEFT JOIN (SELECT hotel_acc, COUNT(*) AS n FROM tour_quotations WHERE hotel_acc <> '' GROUP BY hotel_acc) tq ON tq.hotel_acc = h.primary_sup_acc_no
            WHERE h.status <> 'merged' ORDER BY h.name");
        $groups = [];
        foreach ($hotels as $h) {
            $groups[self::nameKey($h['name'])][] = $h;
        }
        $out = [];
        $keys = array_keys($groups);
        // two hotels in different towns are usually two properties of one brand (Wild Glamping Gal Oya /
        // Knuckles, Anantaya Chilaw / Passikudah): the suggestion carries a warning the screen must show
        $locations = static fn (array $list) => array_values(array_unique(array_filter(array_map(static fn ($h) => mb_strtolower(trim((string) $h['location_name'])), $list), static fn ($l) => $l !== '' && $l !== 'unknown')));
        foreach ($groups as $key => $list) {
            if (count($list) > 1 && $key !== '') {
                $locs = $locations($list);
                $out[] = ['key' => $key, 'reason' => 'same name after normalising', 'hotels' => $list, 'location_mismatch' => count($locs) > 1, 'locations' => $locs];
            }
        }
        for ($i = 0; $i < count($keys); $i++) {
            for ($j = $i + 1; $j < count($keys); $j++) {
                $a = $keys[$i]; $b = $keys[$j];
                if ($a === '' || $b === '' || abs(strlen($a) - strlen($b)) > 3) {
                    continue;
                }
                if (preg_match_all('/\d/', $a) > strlen($a) / 3 || preg_match_all('/\d/', $b) > strlen($b) / 3) {
                    continue;
                }
                $len = max(strlen($a), strlen($b));
                if ($len >= 6 && levenshtein($a, $b) <= ($len >= 12 ? 2 : 1)) {
                    $list = array_merge($groups[$a], $groups[$b]);
                    $locs = $locations($list);
                    $out[] = ['key' => "{$a} ~ {$b}", 'reason' => 'names differ by one or two letters', 'hotels' => $list, 'location_mismatch' => count($locs) > 1, 'locations' => $locs];
                }
            }
        }
        return $out;
    }

    /** Fold $mergeId into $keepId. Accounts, room types, plans and history move across. */
    public function mergeHotels(int $keepId, int $mergeId): void
    {
        if ($keepId === $mergeId) {
            throw new \RuntimeException('Pick two different hotels.');
        }
        $keep = $this->getHotel($keepId);
        $merge = $this->getHotel($mergeId);
        if (!$keep || !$merge || $keep['status'] === 'merged' || $merge['status'] === 'merged') {
            throw new \RuntimeException('One of the hotels does not exist or was already merged.');
        }
        // everything that moves is written to the audit row so that unmergeHotel() can put it back
        $moved = ['merged_into' => $keepId, 'keep_name' => $keep['name'], 'accounts' => array_column($merge['accounts'], 'sup_acc_no'), 'primary' => $merge['primary_sup_acc_no'],
            'contracts' => array_map('intval', array_column($this->rows('SELECT id FROM hr2_contract WHERE hotel_id = ?', [$mergeId]), 'id')),
            'imports' => array_map('intval', array_column($this->rows('SELECT id FROM hr2_import WHERE hotel_id = ?', [$mergeId]), 'id')),
            'room_types_moved' => [], 'room_types_folded' => []];
        $this->db->transStart();
        $this->db->query('UPDATE hr2_hotel_account SET hotel_id = ?, is_primary = 0 WHERE hotel_id = ?', [$keepId, $mergeId]);
        foreach ($this->roomTypes($mergeId) as $rt) {
            $same = $this->row('SELECT id FROM hr2_room_type WHERE hotel_id = ? AND LOWER(name) = ?', [$keepId, mb_strtolower($rt['name'])]);
            if ($same) {
                foreach (['hr2_rate', 'hr2_room_supplement', 'hr2_supplement', 'hr2_child_rate'] as $t) {
                    $this->db->query("UPDATE {$t} SET room_type_id = ? WHERE room_type_id = ?", [$same['id'], $rt['id']]);
                }
                $this->db->query('UPDATE hr2_room_type SET base_room_type_id = ? WHERE base_room_type_id = ?', [$same['id'], $rt['id']]);
                $this->db->query('DELETE FROM hr2_room_type WHERE id = ?', [$rt['id']]);
                $moved['room_types_folded'][] = ['from_id' => (int) $rt['id'], 'name' => $rt['name'], 'into_id' => (int) $same['id'], 'legacy_room_type_id' => $rt['legacy_room_type_id'], 'priced_as' => $rt['priced_as'], 'base_room_type_id' => $rt['base_room_type_id']];
            } else {
                $this->db->query('UPDATE hr2_room_type SET hotel_id = ? WHERE id = ?', [$keepId, $rt['id']]);
                $moved['room_types_moved'][] = (int) $rt['id'];
            }
        }
        $this->db->query('UPDATE hr2_contract SET hotel_id = ? WHERE hotel_id = ?', [$keepId, $mergeId]);
        $this->db->query('UPDATE hr2_import SET hotel_id = ? WHERE hotel_id = ?', [$keepId, $mergeId]);
        $this->db->query('UPDATE hr2_legacy_map SET hotel_id = ? WHERE hotel_id = ?', [$keepId, $mergeId]);
        $this->db->query("UPDATE hr2_hotel SET status = 'merged', merged_into_id = ?, updated_at = ?, updated_by = ? WHERE id = ?", [$keepId, date('Y-m-d H:i:s'), $this->user, $mergeId]);
        foreach ($merge['accounts'] as $a) {
            $this->db->query('UPDATE supplier SET is_avalible = 0 WHERE Sup_Acc_No = ?', [$a['sup_acc_no']]);
        }
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('Merge failed; nothing was changed.');
        }
        $this->audit('hotel', $mergeId, 'merge', ['name' => $merge['name']], $moved);
        $this->lookupEngine()->forget();
        $this->projectHotel($keepId);
    }

    /**
     * What unmergeHotel() would put back: the hotel, the accounts, plans, imports and
     * room types that belonged to it. Exact when the merge wrote its details to the
     * audit log; for earlier merges the plans are inferred (see mergedContractsFor()).
     */
    public function unmergePreview(int $mergedId): array
    {
        $m = $this->getHotel($mergedId);
        if (!$m || $m['status'] !== 'merged' || empty($m['merged_into_id'])) {
            throw new \RuntimeException('This hotel is not a merged duplicate.');
        }
        $keepId = (int) $m['merged_into_id'];
        $k = $this->getHotel($keepId);
        if (!$k) {
            throw new \RuntimeException('The hotel it was merged into no longer exists.');
        }
        $auditRow = $this->row("SELECT new_json FROM hr2_audit WHERE entity = 'hotel' AND entity_id = ? AND action = 'merge' ORDER BY id DESC LIMIT 1", [$mergedId]);
        $details = $auditRow ? (json_decode((string) $auditRow['new_json'], true) ?: []) : [];
        $exact = isset($details['contracts']);
        $keepAccounts = array_column($k['accounts'], 'sup_acc_no');
        $accounts = $exact ? array_values(array_intersect((array) $details['accounts'], $keepAccounts)) : array_values(array_intersect([$m['primary_sup_acc_no']], $keepAccounts));
        $contractIds = $exact ? array_values(array_filter(array_map('intval', (array) $details['contracts']), fn ($id) => (bool) $this->row('SELECT id FROM hr2_contract WHERE id = ? AND hotel_id = ?', [$id, $keepId]))) : $this->mergedContractsFor($m, $k);
        $importIds = $exact ? array_values(array_filter(array_map('intval', (array) ($details['imports'] ?? [])), fn ($id) => (bool) $this->row('SELECT id FROM hr2_import WHERE id = ? AND hotel_id = ?', [$id, $keepId]))) : [];
        // room types the returning plans use
        $rooms = [];
        if ($contractIds) {
            $in = implode(',', $contractIds);
            $sql = "SELECT DISTINCT r.room_type_id AS id FROM hr2_rate r JOIN hr2_season s ON s.id = r.season_id WHERE s.contract_id IN ({$in})
                    UNION SELECT DISTINCT room_type_id FROM hr2_room_supplement WHERE contract_id IN ({$in})
                    UNION SELECT DISTINCT room_type_id FROM hr2_supplement WHERE contract_id IN ({$in}) AND room_type_id IS NOT NULL
                    UNION SELECT DISTINCT room_type_id FROM hr2_child_rate WHERE contract_id IN ({$in}) AND room_type_id IS NOT NULL";
            foreach ($this->rows($sql) as $r) {
                $rooms[(int) $r['id']] = true;
            }
        }
        foreach ((array) ($details['room_types_moved'] ?? []) as $rid) {
            if ($this->row('SELECT id FROM hr2_room_type WHERE id = ? AND hotel_id = ?', [(int) $rid, $keepId])) {
                $rooms[(int) $rid] = true;
            }
        }
        $roomPlan = [];
        foreach (array_keys($rooms) as $rid) {
            $rt = $this->row('SELECT id, name FROM hr2_room_type WHERE id = ?', [$rid]);
            if (!$rt) {
                continue;
            }
            // still used by a plan that stays with the kept hotel? then it was folded: the returning hotel gets a copy
            $stays = $contractIds ? $this->row("SELECT 1 AS x FROM hr2_rate r JOIN hr2_season s ON s.id = r.season_id WHERE r.room_type_id = ? AND s.contract_id NOT IN (" . implode(',', $contractIds) . ") LIMIT 1", [$rid])
                                  : $this->row('SELECT 1 AS x FROM hr2_rate r WHERE r.room_type_id = ? LIMIT 1', [$rid]);
            $roomPlan[] = ['id' => (int) $rt['id'], 'name' => $rt['name'], 'action' => $stays ? 'copy' : 'move'];
        }
        return ['hotel' => ['id' => (int) $m['id'], 'name' => $m['name'], 'primary_sup_acc_no' => $m['primary_sup_acc_no'], 'location_name' => $m['location_name']],
            'kept' => ['id' => $keepId, 'name' => $k['name'], 'primary_sup_acc_no' => $k['primary_sup_acc_no']],
            'exact' => $exact, 'accounts' => $accounts,
            'contracts' => array_map(function ($id) { $c = $this->row('SELECT id, name, plan_type, status, valid_from, valid_to FROM hr2_contract WHERE id = ?', [$id]); $c['id'] = (int) $c['id']; return $c; }, $contractIds),
            'imports' => $importIds, 'room_types' => $roomPlan,
            'warning' => $exact ? null : ($contractIds ? 'This merge was made before the log recorded details; the plans listed were inferred from the order in which the migration created them. Check them before undoing.' : 'This merge was made before the log recorded details and no plan could be attributed to the hotel with confidence; only the supplier code goes back.')];
    }

    /**
     * Plans that belonged to a hotel merged before the audit row carried details.
     * The migration created exactly one plan per hotel in hotel-id order, so the
     * kept hotel's migrated plans, sorted by id, line up with the hotel ids of the
     * group (kept hotel + everything merged into it). Manually entered plans are
     * found through their own audit row (the insert records the hotel_id).
     */
    private function mergedContractsFor(array $m, array $k): array
    {
        $keepId = (int) $k['id'];
        $out = [];
        foreach ($this->rows("SELECT entity_id, new_json FROM hr2_audit WHERE entity = 'contract' AND action = 'insert' AND new_json LIKE ?", ['%"hotel_id":' . (int) $m['id'] . '%']) as $a) {
            $j = json_decode((string) $a['new_json'], true);
            if (($j['hotel_id'] ?? null) == $m['id'] && $this->row('SELECT id FROM hr2_contract WHERE id = ? AND hotel_id = ?', [(int) $a['entity_id'], $keepId])) {
                $out[] = (int) $a['entity_id'];
            }
        }
        $group = array_map('intval', array_column($this->rows("SELECT id FROM hr2_hotel WHERE id = ? OR (status = 'merged' AND merged_into_id = ?) ORDER BY id", [$keepId, $keepId]), 'id'));
        $migrated = array_map('intval', array_column($this->rows("SELECT id FROM hr2_contract WHERE hotel_id = ? AND plan_type = 'migrated' ORDER BY id", [$keepId]), 'id'));
        if ($migrated && count($migrated) === count($group)) {
            $pos = array_search((int) $m['id'], $group, true);
            if ($pos !== false) {
                $out[] = $migrated[$pos];
            }
        } elseif ($migrated) {
            // neighbours outside the group bound the ids the hotel's plan can have
            $lo = $this->row("SELECT MAX(c.id) AS v FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.plan_type = 'migrated' AND h.id < ? AND h.id <> ? AND h.status <> 'merged'", [(int) $m['id'], $keepId])['v'] ?? 0;
            $hi = $this->row("SELECT MIN(c.id) AS v FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.plan_type = 'migrated' AND h.id > ? AND h.id <> ? AND h.status <> 'merged'", [(int) $m['id'], $keepId])['v'] ?? PHP_INT_MAX;
            $inRange = array_values(array_filter($migrated, static fn ($id) => $id > (int) $lo && $id < (int) $hi));
            if (count($inRange) === 1) {
                $out[] = $inRange[0];
            }
        }
        return array_values(array_unique($out));
    }

    /** Undo mergeHotels(): the duplicate becomes a hotel again with its accounts, plans, imports and room types. */
    public function unmergeHotel(int $mergedId): array
    {
        $p = $this->unmergePreview($mergedId);
        $mId = (int) $p['hotel']['id'];
        $keepId = (int) $p['kept']['id'];
        $contractIds = array_map('intval', array_column($p['contracts'], 'id'));
        $this->db->transStart();
        $this->db->query("UPDATE hr2_hotel SET status = 'active', merged_into_id = NULL, updated_at = ?, updated_by = ? WHERE id = ?", [date('Y-m-d H:i:s'), $this->user, $mId]);
        foreach ($p['accounts'] as $acc) {
            $this->db->query('UPDATE hr2_hotel_account SET hotel_id = ?, is_primary = ? WHERE sup_acc_no = ? AND hotel_id = ?', [$mId, $acc === $p['hotel']['primary_sup_acc_no'] ? 1 : 0, $acc, $keepId]);
            $this->db->query('UPDATE supplier SET is_avalible = 1 WHERE Sup_Acc_No = ?', [$acc]);
        }
        if (!$this->row('SELECT 1 AS x FROM hr2_hotel_account WHERE hotel_id = ? AND is_primary = 1', [$mId])) {
            $this->db->query('UPDATE hr2_hotel_account SET is_primary = 1 WHERE hotel_id = ? ORDER BY sup_acc_no LIMIT 1', [$mId]);
        }
        $roomMap = []; // old id => id under the returning hotel
        foreach ($p['room_types'] as $rt) {
            if ($rt['action'] === 'move') {
                $this->db->query('UPDATE hr2_room_type SET hotel_id = ? WHERE id = ?', [$mId, $rt['id']]);
                $roomMap[$rt['id']] = $rt['id'];
            } else {
                $src = $this->row('SELECT * FROM hr2_room_type WHERE id = ?', [$rt['id']]);
                $this->db->query('INSERT INTO hr2_room_type (hotel_id, name, code, legacy_room_type_id, priced_as, base_room_type_id, room_count, size_sqm, bed_config, view, max_adults, max_children, max_occupancy, extra_bed_allowed, unit_pax_min, unit_pax_max, description, sort_order, status) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$mId, $src['name'], $src['code'], $src['legacy_room_type_id'], $src['priced_as'], $src['room_count'], $src['size_sqm'], $src['bed_config'], $src['view'], $src['max_adults'], $src['max_children'], $src['max_occupancy'], $src['extra_bed_allowed'], $src['unit_pax_min'], $src['unit_pax_max'], $src['description'], $src['sort_order'], $src['status']]);
                $newId = (int) $this->db->insertID();
                $roomMap[$rt['id']] = $newId;
                if ($contractIds) {
                    $in = implode(',', $contractIds);
                    $this->db->query("UPDATE hr2_rate r JOIN hr2_season s ON s.id = r.season_id SET r.room_type_id = ? WHERE r.room_type_id = ? AND s.contract_id IN ({$in})", [$newId, $rt['id']]);
                    foreach (['hr2_room_supplement', 'hr2_supplement', 'hr2_child_rate'] as $t) {
                        $this->db->query("UPDATE {$t} SET room_type_id = ? WHERE room_type_id = ? AND contract_id IN ({$in})", [$newId, $rt['id']]);
                    }
                }
            }
        }
        // base rooms: keep the link when the base came back too, drop it otherwise
        foreach ($roomMap as $oldId => $newId) {
            $rt = $this->row('SELECT base_room_type_id FROM hr2_room_type WHERE id = ?', [$newId]);
            $src = $this->row('SELECT base_room_type_id FROM hr2_room_type WHERE id = ?', [$oldId]);
            $base = $src['base_room_type_id'] ?? ($rt['base_room_type_id'] ?? null);
            if ($base !== null) {
                $this->db->query('UPDATE hr2_room_type SET base_room_type_id = ? WHERE id = ?', [isset($roomMap[(int) $base]) ? $roomMap[(int) $base] : null, $newId]);
            }
        }
        if ($contractIds) {
            $in = implode(',', $contractIds);
            $this->db->query("UPDATE hr2_contract SET hotel_id = ? WHERE id IN ({$in})", [$mId]);
            $this->db->query("UPDATE hr2_import SET hotel_id = ? WHERE contract_id IN ({$in}) AND hotel_id = ?", [$mId, $keepId]);
        }
        if ($p['imports']) {
            $this->db->query('UPDATE hr2_import SET hotel_id = ? WHERE id IN (' . implode(',', $p['imports']) . ')', [$mId]);
        }
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('Undo failed; nothing was changed.');
        }
        $this->audit('hotel', $mId, 'unmerge', ['merged_into' => $keepId], ['accounts' => $p['accounts'], 'contracts' => $contractIds, 'room_types' => $roomMap, 'exact' => $p['exact']]);
        $this->lookupEngine()->forget();
        $resK = $this->projectHotel($keepId);
        $resM = $this->projectHotel($mId);
        return ['hotel_id' => $mId, 'kept_id' => $keepId, 'accounts' => $p['accounts'], 'contracts' => $contractIds, 'room_types' => count($roomMap), 'exact' => $p['exact'], 'projected' => ['kept' => $resK['rows_written'], 'restored' => $resM['rows_written']]];
    }

    // =====================================================================
    //  hotel-dup-r1 (27 Oct 2026, Shantha): similar names, dependency check, safe delete
    // =====================================================================

    /**
     * Hotels whose name is the same as, or close to, $name — shown before a hotel is registered
     * and before a hotel is renamed, so that staff review the existing record first.
     * Includes archived and merged hotels (flagged by status) because a record that was taken
     * out of use is still the one to restore rather than to create again.
     */
    public function similarHotels(string $name, int $excludeId = 0, int $limit = 8): array
    {
        $key = self::nameKey($name);
        $core = self::nameCore($name);
        if ($key === '' && $core === '') {
            return [];
        }
        $rows = $this->rows("SELECT h.id, h.name, h.hotel_code, h.status, h.primary_sup_acc_no, h.merged_into_id, l.name AS location_name,
                COALESCE(ca.n, 0) AS plans_approved, COALESCE(cd.n, 0) AS plans_draft, COALESCE(tq.n, 0) AS nights_last_year
            FROM hr2_hotel h LEFT JOIN hr2_location l ON l.id = h.location_id
            LEFT JOIN (SELECT hotel_id, COUNT(*) AS n FROM hr2_contract WHERE status = 'approved' GROUP BY hotel_id) ca ON ca.hotel_id = h.id
            LEFT JOIN (SELECT hotel_id, COUNT(*) AS n FROM hr2_contract WHERE status = 'draft' GROUP BY hotel_id) cd ON cd.hotel_id = h.id
            LEFT JOIN (SELECT a.hotel_id, COUNT(*) AS n FROM tour_quotations q JOIN hr2_hotel_account a ON a.sup_acc_no = q.hotel_acc WHERE q.tour_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY) GROUP BY a.hotel_id) tq ON tq.hotel_id = h.id" . ($excludeId ? ' WHERE h.id <> ' . (int) $excludeId : ''));
        $out = [];
        foreach ($rows as $h) {
            $k = self::nameKey($h['name']);
            $c = self::nameCore($h['name']);
            $why = null;
            if ($key !== '' && $k === $key) {
                $why = 'same name';
            } elseif ($core !== '' && $c !== '' && $c === $core) {
                $why = 'same name apart from words like "hotel" or "resort"';
            } elseif ($key !== '' && $k !== '' && strlen($key) >= 5 && strlen($k) >= 5 && abs(strlen($key) - strlen($k)) <= 3 && (str_contains($k, $key) || str_contains($key, $k))) {
                $why = 'one name contains the other';   // "sigiriya" / "sigiriyaaya" — not every hotel in the same town
            } elseif ($key !== '' && $k !== '' && max(strlen($key), strlen($k)) >= 6) {
                $len = max(strlen($key), strlen($k));
                $lev = levenshtein($key, $k);
                similar_text($key, $k, $pct);
                if ($lev <= ($len >= 12 ? 3 : 2)) {
                    $why = 'names differ by a few letters';
                } elseif ($pct >= 85) {
                    $why = 'names are ' . (int) round($pct) . '% alike';
                }
            }
            if ($why !== null) {
                $h['why'] = $why;
                $out[] = $h;
            }
        }
        $rank = static fn (string $why): int => $why === 'same name' ? 0 : (str_starts_with($why, 'same name') ? 1 : 2);
        usort($out, static fn ($a, $b) => [$rank($a['why']), $a['status'] !== 'active', -$a['nights_last_year']] <=> [$rank($b['why']), $b['status'] !== 'active', -$b['nights_last_year']]);
        return array_slice($out, 0, $limit);
    }

    /** nameKey() with the generic words removed and the rest sorted, so "Hotel Sigiriya" and "Sigiriya Hotel" meet. */
    public static function nameCore(string $name): string
    {
        $k = mb_strtolower(trim($name));
        $k = preg_replace('/[^a-z0-9 ]+/', ' ', str_replace(['&', '’', "'"], [' and ', '', ''], $k));
        $k = preg_replace('/\b(the|hotel|hotels|resort|resorts|and|spa|by|pvt|ltd|villa|villas|beach|lake|garden|gardens|house|inn|lodge)\b/', ' ', $k);
        $words = array_filter(explode(' ', trim(preg_replace('/\s+/', ' ', $k))));
        sort($words);
        return implode('', $words);
    }

    /**
     * Everything that depends on a hotel record, counted — the review before Management removes a
     * duplicate or deletes an entry. can_delete is true only when nothing at all refers to it.
     */
    public function hotelDependencies(int $hotelId): array
    {
        $h = $this->getHotel($hotelId);
        if (!$h) {
            throw new \RuntimeException('Hotel not found.');
        }
        $n = function (string $sql, array $b = []): int {
            try {
                return (int) ($this->row($sql, $b)['n'] ?? 0);
            } catch (\Throwable $e) {
                return 0;
            }
        };
        $accs = array_column($h['accounts'], 'sup_acc_no');
        $in = $accs ? implode(',', array_fill(0, count($accs), '?')) : "''";
        $d = [
            'id' => (int) $h['id'], 'name' => $h['name'], 'hotel_code' => $h['hotel_code'] ?? null, 'status' => $h['status'], 'accounts' => $accs,
            'plans_approved' => $n("SELECT COUNT(*) n FROM hr2_contract WHERE hotel_id = ? AND status = 'approved'", [$hotelId]),
            'plans_draft' => $n("SELECT COUNT(*) n FROM hr2_contract WHERE hotel_id = ? AND status = 'draft'", [$hotelId]),
            'plans_other' => $n("SELECT COUNT(*) n FROM hr2_contract WHERE hotel_id = ? AND status NOT IN ('approved','draft')", [$hotelId]),
            'room_types' => $n('SELECT COUNT(*) n FROM hr2_room_type WHERE hotel_id = ?', [$hotelId]),
            'rate_rows' => $n('SELECT COUNT(*) n FROM hr2_rate r JOIN hr2_season s ON s.id = r.season_id JOIN hr2_contract c ON c.id = s.contract_id WHERE c.hotel_id = ?', [$hotelId]),
            'quotation_nights' => $accs ? $n("SELECT COUNT(*) n FROM tour_quotations WHERE hotel_acc IN ({$in})", $accs) : 0,
            'quotation_nights_upcoming' => $accs ? $n("SELECT COUNT(*) n FROM tour_quotations WHERE hotel_acc IN ({$in}) AND tour_date >= CURDATE()", $accs) : 0,
            'tours' => $accs ? $n("SELECT COUNT(DISTINCT quotation_id) n FROM tour_quotations WHERE hotel_acc IN ({$in})", $accs) : 0,
            'inquiry_nights' => $n('SELECT COUNT(*) n FROM tf_intake_day WHERE hotel_id = ?', [$hotelId]),
            'desk_cards' => $n('SELECT COUNT(*) n FROM tf_hotel_job WHERE hotel_id = ?', [$hotelId]),
            'hotel_costs' => $n('SELECT COUNT(*) n FROM hr2_tour_hotel_cost WHERE hotel_id = ?', [$hotelId]),
            'payment_requests' => $accs ? $n("SELECT COUNT(*) n FROM payment_request WHERE hotel_acc IN ({$in})", $accs) : 0,
            'vouchers' => $accs ? $n("SELECT COUNT(*) n FROM voucher WHERE Acc_No IN ({$in})", $accs) : 0,
            'payment_holds' => $n('SELECT COUNT(*) n FROM hr2_payment_hold WHERE hotel_id = ?', [$hotelId]),
            'imports' => $n('SELECT COUNT(*) n FROM hr2_import WHERE hotel_id = ?', [$hotelId]),
            'legacy_rows' => $n('SELECT COUNT(*) n FROM hr2_legacy_map WHERE hotel_id = ?', [$hotelId]),
            'old_screen_rows' => $accs ? $n("SELECT COUNT(*) n FROM hotel_rate WHERE hotel IN ({$in})", $accs) : 0,   // hotel_rate.hotel = the supplier account
            'work_items_open' => $n("SELECT COUNT(*) n FROM hr2_work_item WHERE hotel_id = ? AND status <> 'done'", [$hotelId]),
            'absorbed' => count($h['absorbed'] ?? []),
        ];
        // what makes the record part of the books — operational or financial history: these block a deletion outright.
        // Rate plans (approved or not), old Hotel Rate screen rows, room types and work-queue items are the hotel's own
        // set-up data: a wrong or test entry may be deleted with them, shown and included explicitly (removable below).
        $blocks = [];
        if ($d['quotation_nights']) { $blocks[] = $d['quotation_nights'] . ' night(s) on ' . $d['tours'] . ' quotation(s) / tour(s)'; }
        if ($d['payment_requests']) { $blocks[] = $d['payment_requests'] . ' payment request(s)'; }
        if ($d['vouchers']) { $blocks[] = $d['vouchers'] . ' payment voucher(s) in Accounts'; }
        if ($d['payment_holds']) { $blocks[] = $d['payment_holds'] . ' payment hold(s)'; }
        if ($d['hotel_costs']) { $blocks[] = $d['hotel_costs'] . ' hotel cost line(s) on tours'; }
        if ($d['inquiry_nights']) { $blocks[] = $d['inquiry_nights'] . ' night(s) on Tour Inquiries'; }
        if ($d['desk_cards']) { $blocks[] = $d['desk_cards'] . ' card(s) on the Hotel Desk'; }
        if ($d['absorbed']) { $blocks[] = $d['absorbed'] . ' hotel(s) merged into it (undo those merges first)'; }
        if ($d['status'] === 'merged') { $blocks[] = 'it was merged into another hotel (undo the merge first)'; }
        $removable = [];
        if ($d['plans_approved']) { $removable[] = $d['plans_approved'] . ' approved rate plan(s)'; }
        if ($d['plans_draft']) { $removable[] = $d['plans_draft'] . ' draft rate plan(s)'; }
        if ($d['plans_other']) { $removable[] = $d['plans_other'] . ' superseded / cancelled plan(s)'; }
        if ($d['rate_rows']) { $removable[] = $d['rate_rows'] . ' rate row(s)'; }
        if ($d['legacy_rows'] || $d['old_screen_rows']) { $removable[] = ($d['legacy_rows'] + $d['old_screen_rows']) . ' row(s) on the old Hotel Rate screen'; }
        if ($d['room_types']) { $removable[] = $d['room_types'] . ' room type(s)'; }
        if ($d['imports']) { $removable[] = $d['imports'] . ' rate sheet import record(s) (kept, unlinked)'; }
        $d['removable'] = $removable;
        $d['blocks_delete'] = $blocks;
        $d['can_delete'] = !$blocks;
        $d['can_merge'] = $d['status'] !== 'merged';
        return $d;
    }

    /** hotel-dup-r1 (27 Oct 2026, Shantha): the accounts allowed to add or delete a hotel — kept in hr2_meta, usernames comma-separated. */
    public const OWNERS_KEY = 'hotel_master_owners';

    public function hotelMasterOwners(): array
    {
        $v = (string) $this->meta(self::OWNERS_KEY, '');
        return array_values(array_filter(array_map(static fn ($x) => trim(mb_strtolower($x)), explode(',', $v))));
    }

    public function isHotelMasterOwner(?string $user): bool
    {
        $user = trim(mb_strtolower((string) $user));
        return $user !== '' && in_array($user, $this->hotelMasterOwners(), true);
    }

    /** Exactly what deleteHotel() would remove and what stays — shown before the typed confirmation. */
    public function removalPreview(int $hotelId): array
    {
        $d = $this->hotelDependencies($hotelId);
        $h = $this->getHotel($hotelId);
        $rooms = $this->roomTypes($hotelId);
        $work = (int) ($this->row('SELECT COUNT(*) n FROM hr2_work_item WHERE hotel_id = ?', [$hotelId])['n'] ?? 0);
        $plans = $this->rows('SELECT id, name, status, valid_from, valid_to FROM hr2_contract WHERE hotel_id = ? ORDER BY id', [$hotelId]);
        $planLine = $plans ? implode('; ', array_map(static fn ($p) => "#{$p['id']} {$p['name']} ({$p['status']}, {$p['valid_from']} – {$p['valid_to']})", $plans)) : 'none';
        $oldRows = (int) $d['legacy_rows'] + (int) $d['old_screen_rows'];
        return [
            'can_delete' => $d['can_delete'], 'blocks' => $d['blocks_delete'], 'removable' => $d['removable'], 'dependencies' => $d,
            'has_rates' => (bool) ($plans || $oldRows),
            'removed' => [
                ['what' => 'Hotel Master record', 'detail' => ($h['hotel_code'] ?? '') . ' ' . $h['name'] . ' (written to the audit log first)'],
                ['what' => 'Rate plans', 'detail' => $planLine . ($d['rate_rows'] ? " — {$d['rate_rows']} rate row(s), with seasons, supplements and policies" : ''), 'needs_include' => (bool) $plans],
                ['what' => 'Old Hotel Rate screen rows', 'detail' => $oldRows ? "{$oldRows} row(s) for account(s) " . implode(', ', $d['accounts']) : 'none', 'needs_include' => $oldRows > 0],
                ['what' => 'Room types', 'detail' => $rooms ? implode(', ', array_column($rooms, 'name')) : 'none'],
                ['what' => 'Supplier links', 'detail' => $d['accounts'] ? implode(', ', $d['accounts']) : 'none'],
                ['what' => 'Work-queue items', 'detail' => $work . ' item(s)'],
                ['what' => 'Rate sheet import records', 'detail' => $d['imports'] ? "{$d['imports']} record(s) kept, no longer linked to this hotel" : 'none'],
            ],
            'kept' => [
                ['what' => 'Supplier account in Master Files', 'detail' => ($d['accounts'] ? implode(', ', $d['accounts']) : '—') . ' — kept for Accounts, marked not available'],
                ['what' => 'Hotel code number', 'detail' => ($h['hotel_code'] ?? '') . ' — reserved in the register, never reused'],
                ['what' => 'Audit log', 'detail' => 'the whole record, every rate plan as a full sheet, the old-screen rows and this deletion with your reason'],
                ['what' => 'Bookings, quotations, vouchers, payments, inquiries', 'detail' => 'none exist on this record (that is why it may be deleted); where any exist, deletion is refused and Archive is the way'],
            ],
        ];
    }

    /**
     * Rename a hotel on the master, on its supplier record(s) and on the ledger name, in one audited step
     * (hotel-dup-r1). An exact duplicate of another hotel's name is refused; a near-match is the screen's
     * business (it shows the review first). Returns the supplier accounts renamed with it.
     */
    public function renameHotel(int $id, string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        $cur = $this->getHotel($id);
        if (!$cur) {
            throw new \RuntimeException('Hotel not found.');
        }
        if ($name === '') {
            throw new \RuntimeException('Hotel name cannot be empty.');
        }
        if ($name === (string) $cur['name']) {
            return ['renamed' => false, 'name' => $name, 'accounts' => []];
        }
        foreach ($this->similarHotels($name, $id) as $m) {
            if ($m['why'] === 'same name' && $m['status'] !== 'merged') {
                throw new \RuntimeException("Another hotel is already named \"{$m['name']}\" ({$m['hotel_code']}" . ($m['location_name'] ? ', ' . $m['location_name'] : '') . '). If the two are the same hotel, remove the duplicate on the Hotel Master instead of renaming.');
            }
        }
        $accs = array_column($cur['accounts'] ?? [], 'sup_acc_no');
        $this->updateHotel($id, ['name' => $name]);
        foreach ($accs as $acc) {
            $this->db->query('UPDATE supplier SET Name = ? WHERE Sup_Acc_No = ? AND sup_cat = 1', [$name, $acc]);
            try {
                $this->db->query('UPDATE account_posting SET PName = ? WHERE L_Code = ?', [$name, $acc]);   // the ledger name, as Master Files → Supplier → Update does
            } catch (\Throwable $e) {
                // no ledger table on this install
            }
        }
        $this->audit('hotel', $id, 'rename', ['name' => $cur['name'], 'hotel_code' => $cur['hotel_code'] ?? null], ['name' => $name, 'hotel_code' => $cur['hotel_code'] ?? null, 'supplier_accounts_renamed' => $accs, 'tables' => ['hr2_hotel', 'supplier', 'account_posting']]);
        return ['renamed' => true, 'name' => $name, 'accounts' => $accs, 'old_name' => $cur['name']];
    }

    /** Archive (set inactive) or restore a hotel, audited as such; the dependency review is the caller's. */
    public function setHotelArchived(int $id, bool $archived, string $note = ''): array
    {
        $cur = $this->getHotel($id);
        if (!$cur) {
            throw new \RuntimeException('Hotel not found.');
        }
        if ($cur['status'] === 'merged') {
            throw new \RuntimeException('This hotel was merged into another; undo the merge first.');
        }
        $to = $archived ? 'inactive' : 'active';
        if ($cur['status'] === $to) {
            return ['changed' => false, 'status' => $to];
        }
        $this->updateHotel($id, ['status' => $to]);
        $this->audit('hotel', $id, $archived ? 'archive' : 'restore', ['status' => $cur['status']], ['status' => $to, 'note' => $note]);
        return ['changed' => true, 'status' => $to, 'name' => $cur['name']];
    }

    /**
     * Remove a room type nothing refers to (no rate, supplement, child rule, base-room link, legacy row
     * or inquiry night). Management only (the controller checks); the row is written to the audit log first.
     */
    public function deleteRoomType(int $roomTypeId, string $note = ''): array
    {
        $rt = $this->row('SELECT * FROM hr2_room_type WHERE id = ?', [$roomTypeId]);
        if (!$rt) {
            throw new \RuntimeException('Room type not found.');
        }
        $n = function (string $sql, array $b): int {
            try {
                return (int) ($this->row($sql, $b)['n'] ?? 0);
            } catch (\Throwable $e) {
                return 0;
            }
        };
        $refs = [
            'rate row(s)' => $n('SELECT COUNT(*) n FROM hr2_rate WHERE room_type_id = ?', [$roomTypeId]),
            'room supplement(s)' => $n('SELECT COUNT(*) n FROM hr2_room_supplement WHERE room_type_id = ?', [$roomTypeId]),
            'supplement(s)' => $n('SELECT COUNT(*) n FROM hr2_supplement WHERE room_type_id = ?', [$roomTypeId]),
            'child rule(s)' => $n('SELECT COUNT(*) n FROM hr2_child_rate WHERE room_type_id = ?', [$roomTypeId]),
            'room type(s) priced on it' => $n('SELECT COUNT(*) n FROM hr2_room_type WHERE base_room_type_id = ?', [$roomTypeId]),
            'old-screen row(s)' => $n('SELECT COUNT(*) n FROM hr2_legacy_map WHERE room_type_id = ?', [$roomTypeId]),
            'inquiry night(s)' => $n('SELECT COUNT(*) n FROM tf_intake_day WHERE room_type_id = ?', [$roomTypeId]),
        ];
        $blocks = [];
        foreach ($refs as $what => $cnt) {
            if ($cnt) { $blocks[] = "{$cnt} {$what}"; }
        }
        if ($blocks) {
            throw new \RuntimeException("Room type \"{$rt['name']}\" is in use (" . implode(', ', $blocks) . ') and cannot be deleted. Set it inactive instead.');
        }
        $this->audit('room_type', $roomTypeId, 'delete', $rt, ['deleted_by' => $this->user, 'note' => $note]);
        $this->audit('hotel', (int) $rt['hotel_id'], 'update', null, ['room_type_deleted' => ['id' => $roomTypeId, 'name' => $rt['name']], 'note' => $note]);
        $this->db->query('DELETE FROM hr2_room_type WHERE id = ?', [$roomTypeId]);
        $this->lookupEngine()->forget((int) $rt['hotel_id']);
        return ['id' => $roomTypeId, 'name' => $rt['name'], 'hotel_id' => (int) $rt['hotel_id']];
    }

    /**
     * Remove a hotel record that nothing depends on (hotelDependencies()->can_delete). The hotel row,
     * its supplier-account links, unused room types and its work items go; the hotel code number
     * stays reserved in the register so it is never given to another hotel; the supplier account in
     * Master Files is marked not available but kept (Accounts' record). The whole record is written
     * to the audit log first.
     */
    public function deleteHotel(int $hotelId, string $note = '', bool $includeRates = false): array
    {
        $d = $this->hotelDependencies($hotelId);
        if (!$d['can_delete']) {
            throw new \RuntimeException('This hotel cannot be deleted: ' . implode('; ', $d['blocks_delete']) . '. Archive it instead, or remove it as a duplicate.');
        }
        $plans = $this->rows('SELECT id FROM hr2_contract WHERE hotel_id = ?', [$hotelId]);
        $oldRows = (int) $d['legacy_rows'] + (int) $d['old_screen_rows'];
        if (($plans || $oldRows) && !$includeRates) {
            throw new \RuntimeException('This hotel has ' . implode(', ', $d['removable']) . '. Tick "include its rate plans and old-screen rows" to delete them with it, or keep the hotel and archive it.');
        }
        $h = $this->getHotel($hotelId);
        $sheets = [];
        foreach ($plans as $p) {
            try {
                $sheets[] = $this->getContract((int) $p['id']);
            } catch (\Throwable $e) {
                $sheets[] = ['id' => (int) $p['id'], 'error' => $e->getMessage()];
            }
        }
        $accs = $d['accounts'];
        $in = $accs ? implode(',', array_fill(0, count($accs), '?')) : "''";
        $legacy = $this->rows('SELECT * FROM hr2_legacy_map WHERE hotel_id = ?', [$hotelId]);
        $oldScreen = [];
        try {
            $oldScreen = $accs ? $this->rows("SELECT * FROM hotel_rate WHERE hotel IN ({$in})", $accs) : [];
        } catch (\Throwable $e) {
            $oldScreen = [];
        }
        $snapshot = ['hotel' => $h, 'room_types' => $this->roomTypes($hotelId), 'rate_plans' => $sheets, 'legacy_map' => $legacy, 'old_screen_rows' => $oldScreen, 'dependencies' => $d, 'note' => $note];
        $this->audit('hotel', $hotelId, 'delete', $snapshot, ['deleted_by' => $this->user, 'note' => $note, 'rate_plans_deleted' => array_map('intval', array_column($plans, 'id')), 'old_screen_rows_deleted' => count($oldScreen) + count($legacy)]);
        $this->db->transStart();
        foreach ($plans as $p) {
            $this->db->query('DELETE FROM hr2_contract WHERE id = ?', [(int) $p['id']]);   // seasons, rates, supplements, policies cascade
        }
        $this->db->query('DELETE FROM hr2_legacy_map WHERE hotel_id = ?', [$hotelId]);
        if ($accs) {
            try {
                $this->db->query("DELETE FROM hotel_rate WHERE hotel IN ({$in})", $accs);
            } catch (\Throwable $e) {
                // no old screen table on this install
            }
        }
        $this->db->query('UPDATE hr2_import SET hotel_id = NULL WHERE hotel_id = ?', [$hotelId]);
        $this->db->query('DELETE FROM hr2_work_item WHERE hotel_id = ?', [$hotelId]);
        $this->db->query('DELETE FROM hr2_room_type WHERE hotel_id = ?', [$hotelId]);
        $this->db->query('DELETE FROM hr2_hotel_account WHERE hotel_id = ?', [$hotelId]);
        try {
            $this->db->query('UPDATE hr2_hotel_code SET hotel_id = NULL WHERE hotel_id = ?', [$hotelId]);   // the number stays reserved
        } catch (\Throwable $e) {
            // no code register on this install
        }
        $this->db->query('DELETE FROM hr2_hotel WHERE id = ?', [$hotelId]);
        foreach ($d['accounts'] as $acc) {
            $this->db->query('UPDATE supplier SET is_avalible = 0 WHERE Sup_Acc_No = ?', [$acc]);
        }
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('Delete failed; nothing was changed.');
        }
        $this->lookupEngine()->forget();
        return ['id' => $hotelId, 'name' => $h['name'], 'hotel_code' => $h['hotel_code'] ?? null, 'accounts' => $d['accounts']];
    }

    // =====================================================================
    //  Audit & helpers
    // =====================================================================

    /** The actions hr2_audit.action accepts (schema 9); anything else is stored as 'update' with the intended name inside new_json — never a blank. */
    public const AUDIT_ACTIONS = ['insert', 'update', 'delete', 'merge', 'unmerge', 'approve', 'supersede', 'project', 'migrate', 'rename', 'override', 'archive', 'restore'];

    public function audit(string $entity, int $entityId, string $action, $old, $new, ?int $importId = null): void
    {
        if (!in_array($action, self::AUDIT_ACTIONS, true)) {
            $new = ['_intended_action' => $action] + (is_array($new) ? $new : ($new === null ? [] : ['value' => $new]));
            $action = 'update';
        }
        $this->db->query(
            'INSERT INTO hr2_audit (entity, entity_id, action, old_json, new_json, import_id, user, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$entity, $entityId, $action, $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE), $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE), $importId, $this->user, date('Y-m-d H:i:s')]
        );
    }

    /**
     * The rate grid of a plan as values, keyed season · room · meal plan, for comparing
     * before and after a save. Read-only.
     *
     * @return array<string, array<string, ?string>>
     */
    private function rateGridSnapshot(int $contractId): array
    {
        $out = [];
        try {
            foreach ($this->rows(
                'SELECT s.name AS season, rt.name AS room, r.meal_plan, r.sgl, r.dbl, r.tpl, r.quad, r.unit, r.child_with_bed, r.child_no_bed
                 FROM hr2_rate r JOIN hr2_season s ON s.id = r.season_id JOIN hr2_room_type rt ON rt.id = r.room_type_id
                 WHERE s.contract_id = ? ORDER BY s.sort_order, s.id, rt.name, r.meal_plan',
                [$contractId]
            ) as $r) {
                $out[$r['season'] . ' · ' . $r['room'] . ' · ' . $r['meal_plan']] = ['sgl' => $r['sgl'], 'dbl' => $r['dbl'], 'tpl' => $r['tpl'], 'quad' => $r['quad'], 'unit' => $r['unit'], 'child_with_bed' => $r['child_with_bed'], 'child_no_bed' => $r['child_no_bed']];
            }
        } catch (\Throwable $e) {
            // a snapshot that cannot be read costs nothing; the save goes ahead
        }
        return $out;
    }

    /**
     * Write the rate values that changed on a saved plan — each one as "season · room ·
     * meal plan, single: 125 → 135" — to the plan's own audit trail and, where the Tour
     * Desk's management log is installed, to that too, so one screen shows every change
     * anybody made. It never interferes with the save: a failure here is swallowed.
     */
    private function auditRateChanges(int $contractId, int $hotelId, array $before, ?int $importId = null): void
    {
        try {
            $after = $this->rateGridSnapshot($contractId);
            $labels = ['sgl' => 'single', 'dbl' => 'double', 'tpl' => 'triple', 'quad' => 'quad', 'unit' => 'per unit', 'child_with_bed' => 'child with bed', 'child_no_bed' => 'child no bed'];
            $norm = static fn ($v) => $v === null || $v === '' ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
            $changes = [];
            foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $k) {
                if (!isset($before[$k])) {
                    $vals = array_filter($after[$k], static fn ($v) => $v !== null);
                    $changes[] = ['what' => $k . ' added', 'from' => null, 'to' => implode(', ', array_map(static fn ($c, $v) => $labels[$c] . ' ' . $norm($v), array_keys($vals), $vals)) ?: 'no figures'];
                    continue;
                }
                if (!isset($after[$k])) {
                    $vals = array_filter($before[$k], static fn ($v) => $v !== null);
                    $changes[] = ['what' => $k . ' removed', 'from' => implode(', ', array_map(static fn ($c, $v) => $labels[$c] . ' ' . $norm($v), array_keys($vals), $vals)) ?: 'no figures', 'to' => null];
                    continue;
                }
                foreach ($labels as $col => $label) {
                    $x = $norm($before[$k][$col] ?? null);
                    $y = $norm($after[$k][$col] ?? null);
                    if ($x !== $y) {
                        $changes[] = ['what' => $k . ', ' . $label, 'from' => $x, 'to' => $y];
                    }
                }
            }
            if (!$changes) {
                return;
            }
            $this->audit('rates', $contractId, 'update', array_column($changes, 'from', 'what'), array_column($changes, 'to', 'what'), $importId);
            // the management-wide log, when the Tour Desk is installed beside this module
            if (class_exists(\App\Libraries\TourFlow\Activity::class) && $this->db->query("SHOW TABLES LIKE 'tf_activity'")->getResultArray()) {
                $h = $this->row('SELECT h.name, c.name AS plan FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.id = ?', [$contractId]);
                $summary = ($h['name'] ?? 'Hotel') . ' — ' . ($h['plan'] ?? 'plan') . ': ' . count($changes) . ' rate value' . (count($changes) === 1 ? '' : 's') . ' changed';
                (new \App\Libraries\TourFlow\Activity($this->db, $this->user))->record('hotel_rate', $contractId, null, null, 'rates', $summary, $changes);
            }
        } catch (\Throwable $e) {
            // the audit line is a record, never a gate: the save has already happened
        }
    }

    public function auditTrail(string $entity, int $entityId, int $limit = 50): array
    {
        // flag: a Frequently Used tick / untick on a hotel, so the history can say so ({"frequently_used":1|0}, else empty)
        return $this->rows('SELECT id, action, user, created_at, import_id, CASE WHEN new_json IN (\'{"frequently_used":1}\', \'{"frequently_used":0}\') THEN new_json ELSE NULL END AS flag FROM hr2_audit WHERE entity = ? AND entity_id = ? ORDER BY id DESC LIMIT ' . (int) $limit, [$entity, $entityId]);
    }

    /** Quality flags stored with a rate row; thresholds come from Settings (tiny_rate / huge_rate). */
    public static function rowFlags(array $vals, string $mode = 'per_room', float $tiny = 10.0, float $huge = 3000.0): string
    {
        $flags = [];
        foreach (['sgl', 'dbl', 'tpl', 'quad', 'unit'] as $occ) {
            $v = $vals[$occ] ?? null;
            if ($v !== null && $v < $tiny) { $flags['tiny'] = 1; }
            if ($v !== null && $v > $huge) { $flags['huge'] = 1; }
        }
        $s = $vals['sgl'] ?? null; $d = $vals['dbl'] ?? null; $t = $vals['tpl'] ?? null; $q = $vals['quad'] ?? null;
        if ($mode === 'per_room' && (($s !== null && $d !== null && $s > $d) || ($d !== null && $t !== null && $d > $t) || ($t !== null && $q !== null && $t > $q))) {
            $flags['occupancy_order'] = 1;
        }
        if ($mode === 'per_person' && $s !== null && $d !== null && $s < $d) {
            $flags['occupancy_order'] = 1;
        }
        return implode(',', array_keys($flags));
    }

    public static function nameKey(string $name): string
    {
        $k = mb_strtolower(trim($name));
        $k = str_replace(['&', '’', "'"], [' and ', '', ''], $k);
        $k = preg_replace('/[^a-z0-9 ]+/', ' ', $k);
        $k = preg_replace('/\b(the|hotel|hotels|resort|resorts|and|spa|by|pvt|ltd)\b/', ' ', $k);
        return trim(preg_replace('/\s+/', '', $k));
    }

    public static function titleCase(string $s): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        if ($s === mb_strtoupper($s) || $s === mb_strtolower($s)) {
            return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
        }
        return $s;
    }

    protected function row(string $sql, array $binds = []): ?array
    {
        $res = $this->db->query($sql, $binds);
        if ($res === false || $res === true) {
            return null;
        }
        $row = $res->getRowArray();
        return $row ?: null;
    }

    protected function rows(string $sql, array $binds = []): array
    {
        $res = $this->db->query($sql, $binds);
        if ($res === false || $res === true) {
            return [];
        }
        return $res->getResultArray();
    }
}
