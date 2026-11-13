<?php

namespace App\Libraries\TourFlow;

/**
 * The Driver & Guide Master (schema 44, tf-crew-master-r1), grown from the Drivers & Guides master of schema 36:
 * one record per person the company engages, in one of three categories —
 *
 *   JSD    Japanese speaking driver           drives the tour, speaks Japanese, is not the licensed guide
 *   JSCG   Japanese speaking chauffeur guide  drives, speaks Japanese, holds the guide licence (one person, both roles)
 *   JSNTG  Japanese speaking national tourist guide — the licensed guide; no vehicle; a driver is allocated separately
 *
 * — with their personal details (legal name, nickname, home address, NIC or passport, mobile, WhatsApp, e-mail,
 * photo), the garage / vehicle base address (or "same as home"), the driving licence and, for a JSCG, the guide
 * licence (number, expiry, a copy of each), the vehicle normally supplied by a JSD / JSCG (registration, make,
 * model, year, type with the van's roof type, ownership, seats, passengers with luggage, photographs, insurance and
 * revenue licence expiry with copies), the LKR-per-kilometre rate with the date it takes effect (every rate kept;
 * the tour copies the figure the day the person is engaged), and the bank details for payment — bank, branch,
 * account number, the account name exactly as the bank has it, verified yes / no with the date, payment remarks —
 * which only the holders of "Driver & Guide bank details" see.
 *
 * The record keeps everything schema 36 had: the categories served (Crew Bata codes), languages, the ledger account
 * accounts keeps for the person (6-9-11, never invented here), the own agreed bata, active / inactive, archive. The
 * driver registration (Supplier → New Driver, category 2) is brought onto the master once at the schema 44 install,
 * each record linked to its ledger account and marked "category to confirm" — the registration does not say who is a
 * chauffeur guide. Transport Operations picks the person from this master, strictly by the line's category.
 *
 * Expired licences and documents are flagged everywhere the person is offered. Nothing is deleted here.
 */
final class CrewPeople
{
    public const FIELDS = ['name', 'nickname', 'kind', 'category', 'category_confirmed', 'role_codes', 'languages', 'japanese_note', 'phone', 'mobile', 'whatsapp', 'email',
        'home_address', 'garage_address', 'garage_same', 'nic', 'id_type', 'licence_no', 'licence_expiry', 'guide_licence_no', 'guide_licence_expiry',
        'supplier_acc', 'bata', 'bata_currency', 'overnight_bata', 'vehicle_reg', 'note', 'status',
        'bank_name', 'bank_branch', 'bank_account_no', 'bank_account_name', 'bank_verified', 'bank_verified_at', 'payment_remarks'];

    /** The fields only the bank privilege sees; stripped from every answer for everybody else. */
    public const BANK_FIELDS = ['bank_name', 'bank_branch', 'bank_account_no', 'bank_account_name', 'bank_verified', 'bank_verified_at', 'bank_verified_by', 'payment_remarks'];

    public const CATEGORIES = [
        'JSD'   => ['name' => 'Japanese Speaking Driver',                 'kind' => 'driver', 'vehicle' => true,  'guide_licence' => false, 'short' => 'JSD'],
        'JSCG'  => ['name' => 'Japanese Speaking Chauffeur Guide',        'kind' => 'both',   'vehicle' => true,  'guide_licence' => true,  'short' => 'JSCG'],
        'JSNTG' => ['name' => 'Japanese Speaking National Tourist Guide', 'kind' => 'guide',  'vehicle' => false, 'guide_licence' => true,  'short' => 'JSNTG'],
    ];

    /** A crew line's category code → the master's category it must be filled from (strict: nobody else is offered). */
    public const LINE_CATEGORY = ['JSD' => 'JSD', 'JSCG' => 'JSCG', 'JSNTG' => 'JSNTG', 'GUIDE_JP' => 'JSNTG'];

    /**
     * The STANDARD figures paid to the crew (tf_crew_standard), each with the date it took effect (Shantha, 5 Oct 2026):
     * the standard bata per day of a JSD / JSCG (LKR 4,000), the standard guide fee per day of a JSNTG (LKR 7,500), and
     * the overnight allowance per night of a JSNTG — no figure until it is confirmed; nothing is invented. A person's
     * own agreed figure (tf_crew_bata) overrides the standard for that person only; with none, the standard in force
     * the day they are engaged is copied onto the tour. A later standard never moves a tour already allocated.
     */
    public const STANDARD_ITEMS = [
        'bata'      => ['name' => 'Standard bata',       'unit' => 'per day',   'for' => ['JSD', 'JSCG'], 'what' => 'the bata per day of a JSD / JSCG'],
        'guide_fee' => ['name' => 'Standard guide fee',  'unit' => 'per day',   'for' => ['JSNTG'],       'what' => 'the guide fee per day of a JSNTG'],
        'overnight' => ['name' => 'Overnight allowance', 'unit' => 'per night', 'for' => ['JSNTG'],       'what' => 'the overnight allowance per night of a JSNTG'],
    ];

    public const VEHICLE_TYPES = ['car' => 'Car', 'van' => 'Van', 'minibus' => 'Minibus', 'bus' => 'Bus'];
    public const ROOF_TYPES    = ['high' => 'High roof', 'flat' => 'Flat / low roof'];
    public const OWNERSHIP     = ['own' => 'Own', 'rented' => 'Rented', 'other_supplier' => 'Other supplier'];
    public const FILE_KINDS    = ['photo' => 'photo_file', 'licence' => 'licence_file', 'guide_licence' => 'guide_licence_file'];
    public const VEHICLE_FILE_KINDS = ['insurance' => 'insurance_file', 'licence' => 'licence_file'];
    public const ALLOWED_EXT   = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    public const MAX_FILE_MB   = 8;

    private $db;
    private string $user;
    private ?array $rateClasses = null;
    private ?array $standardRows = null;
    private ?int $userId;
    private string $actor;
    private ?bool $hasTable = null;
    private ?bool $hasMaster = null;
    private ?string $filesDir;
    private ?string $publicRoot;

    public function __construct($db, string $user, ?int $userId = null, ?string $actor = null, ?string $filesDir = null, ?string $publicRoot = null)
    {
        $this->db         = $db;
        $this->user       = $user;
        $this->userId     = $userId;
        $this->actor      = $actor ?: $user;
        $this->filesDir   = $filesDir;
        $this->publicRoot = $publicRoot;
    }

    /** Whether the master is installed (schema 36). */
    public function available(): bool
    {
        if ($this->hasTable === null) {
            try {
                $this->hasTable = (bool) $this->db->query("SHOW TABLES LIKE 'tf_crew_person'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasTable = false;
            }
        }
        return $this->hasTable;
    }

    /** Whether the Driver & Guide Master's fields are there (schema 44). */
    public function masterReady(): bool
    {
        if ($this->hasMaster === null) {
            try {
                $this->hasMaster = $this->available() && (bool) $this->db->query("SHOW COLUMNS FROM `tf_crew_person` LIKE 'category'")->getResultArray()
                    && (bool) $this->db->query("SHOW TABLES LIKE 'tf_crew_vehicle'")->getResultArray()
                    && (bool) $this->db->query("SHOW TABLES LIKE 'tf_crew_bata'")->getResultArray()
                    && (bool) $this->db->query("SHOW TABLES LIKE 'tf_crew_standard'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasMaster = false;
            }
        }
        return $this->hasMaster;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private static function cut($v, int $len): ?string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        return $s === '' ? null : mb_substr($s, 0, $len);
    }

    private static function dateOrNull($v): ?string
    {
        $s = trim((string) ($v ?? ''));
        if ($s === '' || str_starts_with($s, '0000')) {
            return null;
        }
        $ts = strtotime($s);
        return $ts === false ? null : date('Y-m-d', $ts);
    }

    private static function moneyOrNull($v): ?float
    {
        if ($v === null || $v === '' || (is_string($v) && trim($v) === '')) {
            return null;
        }
        return round((float) $v, 2);
    }

    /** The category codes of the Crew Bata master, keyed by code, with kind and name. */
    public function roles(): array
    {
        $out = [];
        try {
            foreach ($this->db->query('SELECT code, name, kind, language, category, bata, currency, active FROM tf_crew_role ORDER BY kind, language, category, code')->getResultArray() as $r) {
                $out[(string) $r['code']] = $r;
            }
        } catch (\Throwable $e) {
            // an older install: no categories to offer
        }
        return $out;
    }

    /** The three categories with the roles they imply, for the screen. */
    public static function categories(): array
    {
        $out = [];
        foreach (self::CATEGORIES as $code => $c) {
            $out[] = $c + ['code' => $code];
        }
        return $out;
    }

    /** The master's category a crew line of this code must be filled from; null for lines outside the three (ESD …). */
    public static function lineCategory(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));
        return self::LINE_CATEGORY[$code] ?? null;
    }

    /** The codes a person serves, as a clean list validated against the Crew Bata master. */
    private function cleanCodes($codes, string $kind): array
    {
        $list = is_array($codes) ? $codes : preg_split('/[,;\s]+/', (string) ($codes ?? ''));
        $roles = $this->roles();
        $out = [];
        foreach ($list as $c) {
            $c = strtoupper(trim((string) $c));
            if ($c === '' || !isset($roles[$c]) || in_array($c, $out, true)) {
                continue;
            }
            $rk = (string) $roles[$c]['kind'];
            if ($kind !== 'both' && $rk !== $kind) {
                throw new \InvalidArgumentException($roles[$c]['name'] . ' is a ' . $rk . ' category; this person is a ' . $kind . '. Make them "driver and guide" to carry both.');
            }
            $out[] = $c;
        }
        return $out;
    }

    /** The codes a category always serves on the Crew Bata master (so the right lines list the person). */
    private static function codesForCategory(string $cat): array
    {
        switch ($cat) {
            case 'JSD':   return ['JSD'];
            case 'JSCG':  return ['JSCG'];
            case 'JSNTG': return ['GUIDE_JP', 'JSNTG'];   // the quotation's Japanese guide line is GUIDE_JP; a JSNTG role, if one is ever added, counts too
        }
        return [];
    }

    // =====================================================================
    //  Reading
    // =====================================================================

    private function row(array $r, bool $withBank = true): array
    {
        $roles = $this->roles();
        $codes = array_values(array_filter(array_map('trim', explode(',', (string) ($r['role_codes'] ?? '')))));
        $today = date('Y-m-d');
        $r['id']    = (int) $r['id'];
        $r['bata']  = self::moneyOrNull($r['bata'] ?? null);
        $r['overnight_bata'] = self::moneyOrNull($r['overnight_bata'] ?? null);
        $r['codes'] = $codes;
        $r['categories'] = array_map(static fn ($c) => ['code' => $c, 'name' => $roles[$c]['name'] ?? $c, 'kind' => $roles[$c]['kind'] ?? null, 'language' => $roles[$c]['language'] ?? null], $codes);
        $r['category'] = isset($r['category']) && isset(self::CATEGORIES[(string) $r['category']]) ? (string) $r['category'] : null;
        $r['category_name'] = $r['category'] ? self::CATEGORIES[$r['category']]['name'] : null;
        $r['category_confirmed'] = !array_key_exists('category_confirmed', $r) || (int) $r['category_confirmed'] === 1;
        $r['garage_same'] = (int) ($r['garage_same'] ?? 0) === 1;
        $r['garage_address_effective'] = $r['garage_same'] ? ($r['home_address'] ?? null) : ($r['garage_address'] ?? null);
        $r['is_driver'] = in_array($r['kind'], ['driver', 'both'], true);
        $r['is_guide']  = in_array($r['kind'], ['guide', 'both'], true);
        $r['archived']  = !empty($r['archived_at']);
        $r['licence_expired'] = !empty($r['licence_expiry']) && $r['licence_expiry'] < $today;
        $r['guide_licence_expired'] = !empty($r['guide_licence_expiry']) && $r['guide_licence_expiry'] < $today;
        $r['bank_verified'] = (int) ($r['bank_verified'] ?? 0) === 1;
        $r['has_bank'] = trim((string) ($r['bank_account_no'] ?? '')) !== '';
        $r['migrated'] = !empty($r['migrated_from']);
        foreach (self::FILE_KINDS as $kind => $k) {
            $r[$k . '_url'] = !empty($r[$k]) ? $this->fileUrl($r['id'], $kind) : null;
        }
        if (!$withBank) {
            foreach (self::BANK_FIELDS as $k) {
                unset($r[$k]);
            }
        }
        return $r;
    }

    /** Everything a record carries beyond the row: the vehicle, its photos, the rates, the warnings. */
    private function enrich(array $p, bool $withBank): array
    {
        if ($this->masterReady()) {
            $p['vehicle'] = $p['category'] !== 'JSNTG' ? $this->vehicle($p['id']) : null;
            $p['rates']   = $this->rates($p['id']);
            $p['rate']    = $this->currentRate($p['id']);
            $p['batas']   = $this->batas($p['id']);
            $p['bata_row'] = $this->currentBata($p['id']);
            $p['rate_effective'] = $this->effectiveRate($p);
            $p['bata_effective'] = $this->effectiveBata($p);
        } else {
            $p['vehicle'] = null;
            $p['rates']   = [];
            $p['rate']    = null;
            $p['batas']   = [];
            $p['bata_row'] = null;
            $p['rate_effective'] = ['rate_km' => null, 'source' => null, 'general' => null, 'special' => null, 'from' => null, 'class' => null, 'class_name' => null];
            $p['bata_effective'] = self::noBata();
        }
        $p['warnings'] = $this->warningsFor($p);
        return $p;
    }

    /**
     * The people. `kind` narrows to those who drive or guide; `active` leaves out the archived and the inactive;
     * `category` to one of the three; `code` marks who serves a Crew Bata code. Bank details only with `withBank`.
     */
    public function all(?string $kind = null, bool $activeOnly = true, ?string $code = null, ?string $category = null, bool $withBank = false, bool $full = false): array
    {
        if (!$this->available()) {
            return [];
        }
        $where = [];
        $bind  = [];
        if ($activeOnly) {
            $where[] = "archived_at IS NULL AND status = 'active'";
        }
        if ($kind === 'driver' || $kind === 'guide') {
            $where[] = 'kind IN (?, ?)';
            $bind[]  = $kind;
            $bind[]  = 'both';
        }
        if ($category !== null && $category !== '' && $this->masterReady()) {
            $where[] = 'category = ?';
            $bind[]  = strtoupper($category);
        }
        $rows = $this->db->query('SELECT * FROM tf_crew_person' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY name, id', $bind)->getResultArray();
        $out  = array_map(fn ($r) => $this->row($r, $withBank), $rows);
        if ($code !== null && $code !== '') {
            $code = strtoupper($code);
            foreach ($out as &$p) {
                $p['serves'] = in_array($code, $p['codes'], true);
            }
            unset($p);
            usort($out, static fn ($a, $b) => [(int) !$a['serves'], $a['name']] <=> [(int) !$b['serves'], $b['name']]);
        }
        // how many tours each has been engaged on, from the crew lines (a figure, nothing more)
        try {
            $used = [];
            foreach ($this->db->query("SELECT person_id, COUNT(*) n FROM tf_transport_crew WHERE person_id IS NOT NULL GROUP BY person_id")->getResultArray() as $u) {
                $used[(int) $u['person_id']] = (int) $u['n'];
            }
            foreach ($out as &$p) {
                $p['engagements'] = $used[$p['id']] ?? 0;
            }
            unset($p);
        } catch (\Throwable $e) {
            // the crew column not there yet
        }
        // the vehicle normally supplied and the rate in force, for everyone at once (three queries, not three per person)
        $this->attachVehiclesAndRates($out);
        if ($full) {
            foreach ($out as &$p) {
                $p['rates'] = $this->rates($p['id']);
            }
            unset($p);
        }
        foreach ($out as &$p) {
            $p['warnings'] = $this->warningsFor($p);
        }
        unset($p);
        return $out;
    }

    /** The current vehicle (with its photographs) and the rate in force today on every person in the list. */
    private function attachVehiclesAndRates(array &$people): void
    {
        foreach ($people as &$p) {
            $p['vehicle'] = null;
            $p['rate']    = null;
            $p['bata_row'] = null;
            $p['bata_effective'] = self::noBata();
        }
        unset($p);
        if (!$people || !$this->masterReady()) {
            return;
        }
        $ids   = array_map(static fn ($p) => (int) $p['id'], $people);
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $vehicles = [];
        foreach ($this->db->query("SELECT * FROM tf_crew_vehicle WHERE is_current = 1 AND person_id IN ($marks) ORDER BY id DESC", $ids)->getResultArray() as $v) {
            if (!isset($vehicles[(int) $v['person_id']])) {
                $vehicles[(int) $v['person_id']] = $this->vehicleRow($v, false);
            }
        }
        if ($vehicles) {
            $vids = array_map(static fn ($v) => $v['id'], $vehicles);
            try {
                foreach ($this->db->query('SELECT * FROM tf_crew_vehicle_photo WHERE vehicle_id IN (' . implode(',', array_fill(0, count($vids), '?')) . ') ORDER BY sort_order, id', array_values($vids))->getResultArray() as $ph) {
                    foreach ($vehicles as &$v) {
                        if ($v['id'] === (int) $ph['vehicle_id']) {
                            $ph['id']  = (int) $ph['id'];
                            $ph['url'] = $this->fileUrl($v['person_id'], 'vehicle_photo', $ph['id']);
                            $v['photos'][] = $ph;
                        }
                    }
                    unset($v);
                }
            } catch (\Throwable $e) {
                // no photo table
            }
        }
        $rates = [];
        foreach ($this->db->query("SELECT * FROM tf_crew_rate WHERE effective_from <= ? AND person_id IN ($marks) ORDER BY effective_from DESC, id DESC", array_merge([date('Y-m-d')], $ids))->getResultArray() as $r) {
            if (!isset($rates[(int) $r['person_id']])) {
                $r['id'] = (int) $r['id'];
                $r['rate_km'] = (float) $r['rate_km'];
                $rates[(int) $r['person_id']] = $r;
            }
        }
        $batas = [];
        try {
            foreach ($this->db->query("SELECT * FROM tf_crew_bata WHERE effective_from <= ? AND person_id IN ($marks) ORDER BY effective_from DESC, id DESC", array_merge([date('Y-m-d')], $ids))->getResultArray() as $b) {
                if (!isset($batas[(int) $b['person_id']])) {
                    $batas[(int) $b['person_id']] = self::bataRow($b);
                }
            }
        } catch (\Throwable $e) {
            // no bata history table
        }
        foreach ($people as &$p) {
            $p['vehicle'] = ($p['category'] ?? null) !== 'JSNTG' ? ($vehicles[$p['id']] ?? null) : null;
            $p['rate']    = $rates[$p['id']] ?? null;
            $p['bata_row'] = $batas[$p['id']] ?? null;
            $p['rate_effective'] = $this->effectiveRate($p);
            $p['bata_effective'] = $this->effectiveBata($p);
        }
        unset($p);
    }

    /** The bata figures of a record before the schema 44 upgrade: nothing in force, nothing to show side by side. */
    private static function noBata(): array
    {
        return ['item' => null, 'item_name' => null, 'agreed' => null, 'standard' => null, 'agreed_overnight' => null, 'standard_overnight' => null,
            'bata' => null, 'source' => null, 'from' => null, 'overnight' => null, 'overnight_source' => null, 'overnight_from' => null];
    }

    // =====================================================================
    //  The GENERAL rate (the vehicle class on the Transport Rates master) and the SPECIAL rate (this driver)
    // =====================================================================

    /** The vehicle classes of the Transport Rates master (Master Files → Transport Rates): code, name, seats, the general rate today. */
    public function rateClasses(): array
    {
        if ($this->rateClasses === null) {
            $this->rateClasses = [];
            try {
                foreach ($this->db->query('SELECT code, name, max_pax, seats, active FROM tf_vehicle_class WHERE active = 1 ORDER BY sort_order, max_pax')->getResultArray() as $c) {
                    $this->rateClasses[(string) $c['code']] = ['code' => (string) $c['code'], 'name' => (string) $c['name'], 'seats' => $c['seats'] !== null ? (int) $c['seats'] : null, 'max_pax' => (int) $c['max_pax']];
                }
            } catch (\Throwable $e) {
                // no Transport Rates master on this database
            }
        }
        // the general rate today, read fresh each time (a rate set a moment ago counts)
        $out = $this->rateClasses;
        try {
            $tr = new TransportRates($this->db, $this->user);
            foreach ($out as $code => &$c) {
                $cur = $tr->currentDriver($code);   // tf-kmrate-r1: the driver's lane — LKR per km; the USD quotation rate is never a driver's rate
                $c['rate_km'] = $cur ? (float) $cur['rate_km'] : null;
                $c['currency'] = $cur['currency'] ?? 'LKR';
                $c['valid_from'] = $cur['valid_from'] ?? null;
            }
            unset($c);
        } catch (\Throwable $e) {
            // no rates
        }
        return $out;
    }

    /** The class the vehicle's type, roof and seats point at, when the master has one that fits (a suggestion; the record keeps what a person chose). */
    public function suggestRateClass(?string $type, ?string $roof, ?int $seats): ?string
    {
        $classes = $this->rateClasses();
        $want = match ($type) {
            'car' => ['SEDAN'],
            'van' => $roof === 'high' ? ['HIROOF', 'VAN'] : ['VAN', 'HIROOF'],
            'minibus' => ['MINICOACH'],
            'bus' => $seats !== null && $seats > 45 ? ['BUS55', 'BUS45', 'COACH33'] : ($seats !== null && $seats > 33 ? ['BUS45', 'COACH33', 'BUS55'] : ['COACH33', 'BUS45', 'BUS55']),
            default => [],
        };
        foreach ($want as $w) {
            if (isset($classes[$w])) {
                return $w;
            }
        }
        return null;
    }

    /** The general rate per kilometre of a vehicle class on a date (today when none): the Transport Rates master's figure. */
    public function generalRate(?string $classCode, ?string $on = null): ?array
    {
        $classCode = trim((string) $classCode);
        if ($classCode === '') {
            return null;
        }
        try {
            $cur = (new TransportRates($this->db, $this->user))->currentDriver($classCode, $on);   // tf-kmrate-r1: LKR per km
        } catch (\Throwable $e) {
            return null;
        }
        if (!$cur) {
            return null;
        }
        $classes = $this->rateClasses();
        return ['class' => $classCode, 'class_name' => $classes[$classCode]['name'] ?? $classCode, 'rate_km' => (float) $cur['rate_km'], 'currency' => (string) $cur['currency'], 'valid_from' => (string) $cur['valid_from']];
    }

    /**
     * The rate per kilometre in force for a driver: their SPECIAL rate when one is on file (it overrides the general
     * rate for that person only), else the GENERAL rate of their vehicle's class on the Transport Rates master, else
     * none (the card's quoted class rate applies then). Both figures come back side by side, so every screen shows them.
     */
    public function effectiveRate(array $p, ?string $on = null): array
    {
        $v = $p['vehicle'] ?? null;
        $general = $v && !empty($v['rate_class']) ? $this->generalRate($v['rate_class'], $on) : null;
        $special = $on === null ? ($p['rate'] ?? null) : $this->currentRate((int) $p['id'], $on);
        $out = ['general' => $general, 'special' => $special ? ['rate_km' => (float) $special['rate_km'], 'effective_from' => $special['effective_from']] : null];
        if ($special) {
            $out += ['rate_km' => (float) $special['rate_km'], 'source' => 'special', 'from' => $special['effective_from'], 'class' => $general['class'] ?? ($v['rate_class'] ?? null), 'class_name' => $general['class_name'] ?? null];
        } elseif ($general) {
            $out += ['rate_km' => $general['rate_km'], 'source' => 'general', 'from' => $general['valid_from'], 'class' => $general['class'], 'class_name' => $general['class_name']];
        } else {
            $out += ['rate_km' => null, 'source' => null, 'from' => null, 'class' => $v['rate_class'] ?? null, 'class_name' => null];
        }
        return $out;
    }

    // =====================================================================
    //  The agreed bata, with its history
    // =====================================================================

    private static function bataRow(array $b): array
    {
        $b['id'] = (int) $b['id'];
        $b['bata'] = self::moneyOrNull($b['bata']);
        $b['overnight'] = self::moneyOrNull($b['overnight']);
        $b['in_force'] = $b['effective_from'] <= date('Y-m-d');
        return $b;
    }

    /** Every agreed bata a person has had, newest first. */
    public function batas(int $personId): array
    {
        if (!$this->masterReady()) {
            return [];
        }
        return array_map(static fn ($b) => self::bataRow($b), $this->db->query('SELECT * FROM tf_crew_bata WHERE person_id = ? ORDER BY effective_from DESC, id DESC', [$personId])->getResultArray());
    }

    /** The agreed bata in force on a date (today when none): the latest effective on or before it; null when none. */
    public function currentBata(int $personId, ?string $on = null): ?array
    {
        if (!$this->masterReady()) {
            return null;
        }
        $b = $this->db->query('SELECT * FROM tf_crew_bata WHERE person_id = ? AND effective_from <= ? ORDER BY effective_from DESC, id DESC LIMIT 1', [$personId, $on ?: date('Y-m-d')])->getRowArray();
        return $b ? self::bataRow($b) : null;
    }

    /**
     * A new agreed bata from a date — for a JSD / JSCG the bata per day; for a JSNTG the guide rate per day and the
     * bata / overnight allowance per night. The earlier figures stay; the person row mirrors the figure in force today.
     */
    public function addBata(int $personId, $bata, $overnight, $from, ?string $note = null): array
    {
        if (!$this->masterReady()) {
            throw new \RuntimeException('The Driver & Guide Master needs the schema 44 upgrade.');
        }
        $p = $this->get($personId, true);
        if (!$p) {
            throw new \RuntimeException('No such person.');
        }
        $amount = self::moneyOrNull($bata);
        $night  = self::moneyOrNull($overnight);
        if ($amount === null && $night === null) {
            throw new \InvalidArgumentException($p['category'] === 'JSNTG' ? 'Give the agreed guide rate per day, the bata / overnight allowance, or both.' : 'Give the agreed bata per day in LKR.');
        }
        if (($amount !== null && $amount < 0) || ($night !== null && $night < 0)) {
            throw new \InvalidArgumentException('A bata cannot be negative.');
        }
        $this->recordBata($p, $amount, $night, self::dateOrNull($from) ?: date('Y-m-d'), $note);
        return $this->batas($personId);
    }

    /** One line of the bata history (the same date again corrects that line); the person row follows the figure in force today. */
    private function recordBata(array $p, ?float $amount, ?float $night, string $date, ?string $note): void
    {
        $personId = (int) $p['id'];
        $what = ($p['category'] ?? null) === 'JSNTG' ? 'guide rate / allowance' : 'bata';
        $dup = $this->db->query('SELECT id FROM tf_crew_bata WHERE person_id = ? AND effective_from = ?', [$personId, $date])->getRowArray();
        if ($dup) {
            $this->db->query('UPDATE tf_crew_bata SET bata = ?, overnight = ?, note = ?, set_at = ?, set_by = ? WHERE id = ?', [$amount, $night, self::cut($note, 255), $this->now(), $this->user, (int) $dup['id']]);
            $this->log($personId, 'bata', $p['name'] . ': the agreed ' . $what . ' from ' . $date . ' corrected to ' . self::bataText($amount, $night) . '.');
        } else {
            $this->db->query('INSERT INTO tf_crew_bata (person_id, bata, overnight, currency, effective_from, note, set_at, set_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$personId, $amount, $night, 'LKR', $date, self::cut($note, 255), $this->now(), $this->user]);
            $this->log($personId, 'bata', $p['name'] . ': agreed ' . $what . ' ' . self::bataText($amount, $night) . ' from ' . $date . ($note ? ' — ' . $note : '') . '.');
        }
        $this->syncBata($personId);
    }

    private static function bataText(?float $amount, ?float $night): string
    {
        $bits = [];
        if ($amount !== null) {
            $bits[] = 'LKR ' . number_format($amount, 2) . ' per day';
        }
        if ($night !== null) {
            $bits[] = 'overnight LKR ' . number_format($night, 2);
        }
        return implode(', ', $bits) ?: 'none';
    }

    /** The person row's bata / overnight_bata follow the history row in force today (the figure the old screens and the lines read). */
    private function syncBata(int $personId): void
    {
        $cur = $this->currentBata($personId);
        $this->db->query('UPDATE tf_crew_person SET bata = ?, bata_currency = ?, overnight_bata = ? WHERE id = ?', [$cur['bata'] ?? null, $cur ? 'LKR' : null, $cur['overnight'] ?? null, $personId]);
    }

    // =====================================================================
    //  The STANDARD bata, guide fee and overnight allowance, each with its history
    // =====================================================================

    private static function standardRow(array $r): array
    {
        $r['id'] = (int) $r['id'];
        $r['amount'] = self::moneyOrNull($r['amount']);
        $r['in_force'] = $r['effective_from'] <= date('Y-m-d');
        $r['name'] = self::STANDARD_ITEMS[$r['item']]['name'] ?? $r['item'];
        $r['unit'] = self::STANDARD_ITEMS[$r['item']]['unit'] ?? 'per day';
        return $r;
    }

    /** Every standard figure ever set, by item, newest first (an item with no row has an empty list: nothing invented). */
    public function standards(bool $fresh = false): array
    {
        if ($this->standardRows === null || $fresh) {
            $this->standardRows = array_fill_keys(array_keys(self::STANDARD_ITEMS), []);
            if ($this->masterReady()) {
                foreach ($this->db->query('SELECT * FROM tf_crew_standard ORDER BY item, effective_from DESC, id DESC')->getResultArray() as $r) {
                    if (isset(self::STANDARD_ITEMS[(string) $r['item']])) {
                        $this->standardRows[(string) $r['item']][] = self::standardRow($r);
                    }
                }
            }
        }
        return $this->standardRows;
    }

    /** The standards as the screens show them: each item with its figure in force today, the date, and its history. */
    public function standardsSummary(): array
    {
        $out = [];
        foreach (self::STANDARD_ITEMS as $item => $def) {
            $cur = $this->currentStandard($item);
            $out[] = $def + ['item' => $item, 'amount' => $cur['amount'] ?? null, 'effective_from' => $cur['effective_from'] ?? null, 'history' => $this->standards()[$item]];
        }
        return $out;
    }

    /**
     * The standard in force for an item on a date (today when none): the latest row effective on or before it. Null
     * when none is set — the overnight allowance until Shantha confirms it — or when the row in force carries no figure.
     */
    public function currentStandard(string $item, ?string $on = null): ?array
    {
        $on = $on ?: date('Y-m-d');
        foreach ($this->standards()[$item] ?? [] as $r) {
            if ($r['effective_from'] <= $on) {
                return $r['amount'] === null ? null : $r;
            }
        }
        return null;
    }

    /**
     * A new standard figure from a date (the System Administrator). The earlier rows stay, every person's own agreed
     * figure stays, and no tour already allocated moves: the lines copied the figure in force the day they were engaged.
     * The same date again corrects that line. No figure at all (null) from a date withdraws the standard from then on.
     */
    public function addStandard(string $item, $amount, $from, ?string $note = null, bool $allowNone = false): array
    {
        if (!$this->masterReady()) {
            throw new \RuntimeException('The Driver & Guide Master needs the schema 44 upgrade.');
        }
        if (!isset(self::STANDARD_ITEMS[$item])) {
            throw new \InvalidArgumentException('Unknown standard figure.');
        }
        $def = self::STANDARD_ITEMS[$item];
        $amt = self::moneyOrNull($amount);
        if ($amt === null && !$allowNone) {
            throw new \InvalidArgumentException('Give ' . lcfirst($def['name']) . ' in LKR ' . $def['unit'] . '.');
        }
        if ($amt !== null && $amt < 0) {
            throw new \InvalidArgumentException('A standard figure cannot be negative.');
        }
        $date = self::dateOrNull($from) ?: date('Y-m-d');
        $text = $amt === null ? 'no figure' : 'LKR ' . number_format($amt, 2) . ' ' . $def['unit'];
        $dup = $this->db->query('SELECT id FROM tf_crew_standard WHERE item = ? AND effective_from = ?', [$item, $date])->getRowArray();
        if ($dup) {
            $this->db->query('UPDATE tf_crew_standard SET amount = ?, note = ?, set_at = ?, set_by = ? WHERE id = ?', [$amt, self::cut($note, 255), $this->now(), $this->user, (int) $dup['id']]);
            $this->log(0, 'standard', $def['name'] . ' from ' . $date . ' corrected to ' . $text . ($note ? ' — ' . $note : '') . '.');
        } else {
            $this->db->query('INSERT INTO tf_crew_standard (item, amount, currency, effective_from, note, set_at, set_by) VALUES (?, ?, ?, ?, ?, ?, ?)', [$item, $amt, 'LKR', $date, self::cut($note, 255), $this->now(), $this->user]);
            $this->log(0, 'standard', $def['name'] . ' ' . $text . ' from ' . $date . ($note ? ' — ' . $note : '') . '.');
        }
        $this->standardRows = null;
        return $this->standards()[$item];
    }

    /** What the standards have been set to, newest first (the master's history of the standard figures). */
    public function standardHistory(int $limit = 40): array
    {
        try {
            return $this->db->query("SELECT action, summary, by_name, `at` FROM tf_activity WHERE entity = 'crew_person' AND entity_id = 0 AND action = 'standard' ORDER BY id DESC LIMIT " . (int) $limit)->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * The bata (for a JSNTG the guide fee) and the overnight allowance in force for a person on a date (today when
     * none): their own AGREED figure when one is on file — it overrides the standard for that person only — else the
     * STANDARD of their category in force that day, else none (the line's Crew Bata figure applies then, as before).
     * Both come back side by side, so every screen shows the standard, the agreed figure, and which one is in force.
     * The overnight allowance is kept apart: agreed, else the standard overnight allowance when one is confirmed, else
     * none — and it is never added into a settlement by itself.
     */
    public function effectiveBata(array $p, ?string $on = null): array
    {
        $cat = $p['category'] ?? null;
        if ($on !== null) {
            $agreed = $this->currentBata((int) $p['id'], $on);
        } elseif (array_key_exists('bata_row', $p)) {
            $agreed = $p['bata_row'];
        } else {
            $agreed = $this->masterReady() && isset($p['id']) ? $this->currentBata((int) $p['id']) : null;
        }
        $item = $cat === 'JSNTG' ? 'guide_fee' : (($cat === 'JSD' || $cat === 'JSCG') ? 'bata' : null);
        $standard = $item !== null ? $this->currentStandard($item, $on) : null;
        $stdNight = $cat === 'JSNTG' ? $this->currentStandard('overnight', $on) : null;
        $out = [
            'item' => $item, 'item_name' => $item !== null ? self::STANDARD_ITEMS[$item]['name'] : null,
            'agreed' => $agreed && $agreed['bata'] !== null ? ['bata' => (float) $agreed['bata'], 'effective_from' => $agreed['effective_from']] : null,
            'standard' => $standard ? ['bata' => (float) $standard['amount'], 'effective_from' => $standard['effective_from']] : null,
            'agreed_overnight' => $agreed && $agreed['overnight'] !== null ? ['overnight' => (float) $agreed['overnight'], 'effective_from' => $agreed['effective_from']] : null,
            'standard_overnight' => $stdNight ? ['overnight' => (float) $stdNight['amount'], 'effective_from' => $stdNight['effective_from']] : null,
        ];
        if ($out['agreed']) {
            $out += ['bata' => $out['agreed']['bata'], 'source' => 'agreed', 'from' => $out['agreed']['effective_from']];
        } elseif ($out['standard']) {
            $out += ['bata' => $out['standard']['bata'], 'source' => 'standard', 'from' => $out['standard']['effective_from']];
        } else {
            $out += ['bata' => null, 'source' => null, 'from' => null];
        }
        if ($out['agreed_overnight']) {
            $out += ['overnight' => $out['agreed_overnight']['overnight'], 'overnight_source' => 'agreed', 'overnight_from' => $out['agreed_overnight']['effective_from']];
        } elseif ($out['standard_overnight']) {
            $out += ['overnight' => $out['standard_overnight']['overnight'], 'overnight_source' => 'standard', 'overnight_from' => $out['standard_overnight']['effective_from']];
        } else {
            $out += ['overnight' => null, 'overnight_source' => null, 'overnight_from' => null];
        }
        return $out;
    }

    /**
     * STRICT: the people a crew line of this code may be given (Shantha, 5 Oct 2026). A JSCG line lists JSCGs only, a
     * JSD line JSDs only, a JSNTG (or GUIDE_JP) line JSNTGs only; a line outside the three (ESD, GUIDE_EN …) lists the
     * people serving that code, as before. Active people only. Never a visual sort: nobody else is in the list.
     */
    public function forLine(?string $code, ?string $kind = null): array
    {
        $cat = self::lineCategory($code);
        if ($cat !== null && $this->masterReady()) {
            return $this->all(null, true, null, $cat);
        }
        $people = $this->all($kind, true, $code);
        return array_values(array_filter($people, static fn ($p) => !empty($p['serves'])));
    }

    public function get(int $id, bool $withBank = true): ?array
    {
        if (!$this->available()) {
            return null;
        }
        $r = $this->db->query('SELECT * FROM tf_crew_person WHERE id = ?', [$id])->getRowArray();
        return $r ? $this->enrich($this->row($r, $withBank), $withBank) : null;
    }

    /** The master record carrying this ledger account (the Tour Payment Dashboard's driver rows key on it). */
    public function byAccount(?string $acc, bool $withBank = false): ?array
    {
        $acc = trim((string) $acc);
        if ($acc === '' || !$this->available()) {
            return null;
        }
        $r = $this->db->query("SELECT * FROM tf_crew_person WHERE supplier_acc = ? AND archived_at IS NULL ORDER BY status = 'active' DESC, id LIMIT 1", [$acc])->getRowArray();
        return $r ? $this->enrich($this->row($r, $withBank), $withBank) : null;
    }

    /**
     * What a payment screen shows so the right person is paid: nickname, legal name, category, and — for the bank
     * privilege — the account name exactly as the bank has it, the bank, the branch, the account number, verified.
     */
    public function payee(?int $personId, ?string $acc, bool $withBank): ?array
    {
        $p = $personId ? $this->get($personId, $withBank) : null;
        if (!$p && $acc) {
            $p = $this->byAccount($acc, $withBank);
        }
        if (!$p) {
            return null;
        }
        $v = $p['vehicle'] ?? null;
        $out = [
            'person_id' => $p['id'], 'name' => $p['name'], 'nickname' => $p['nickname'] ?? null, 'category' => $p['category'], 'category_name' => $p['category_name'],
            'category_confirmed' => $p['category_confirmed'], 'status' => $p['status'], 'archived' => $p['archived'], 'supplier_acc' => $p['supplier_acc'],
            'photo' => $p['photo_file_url'] ?? null, 'phone' => trim((string) ($p['mobile'] ?: ($p['phone'] ?? ''))) ?: null, 'whatsapp' => $p['whatsapp'] ?? null,
            'vehicle' => $v ? ['reg_no' => $v['reg_no'], 'label' => $v['label'], 'vehicle_type' => $v['vehicle_type'], 'type_name' => $v['type_name'], 'roof_type' => $v['roof_type'], 'roof_name' => $v['roof_name'], 'seats' => $v['seats'], 'max_pax_luggage' => $v['max_pax_luggage'], 'rate_class' => $v['rate_class'] ?? null, 'rate_class_name' => $v['rate_class_name'] ?? null] : null,
            'km_rate' => $p['rate_effective']['rate_km'] ?? null, 'rate_from' => $p['rate_effective']['from'] ?? null, 'rate_source' => $p['rate_effective']['source'] ?? null,
            // the bata (guide fee) in force today: the person's own agreed figure, else the standard — and which it is
            'bata' => $p['bata_effective']['bata'] ?? null, 'bata_source' => $p['bata_effective']['source'] ?? null, 'bata_from' => $p['bata_effective']['from'] ?? null,
            'agreed_bata' => $p['bata'] ?? null, 'standard_bata' => $p['bata_effective']['standard']['bata'] ?? null,
            'overnight' => $p['bata_effective']['overnight'] ?? null, 'overnight_source' => $p['bata_effective']['overnight_source'] ?? null,
            'bank_visible' => $withBank, 'warnings' => $p['warnings'],
        ];
        if ($withBank) {
            $out += ['bank_name' => $p['bank_name'] ?? null, 'bank_branch' => $p['bank_branch'] ?? null, 'bank_account_no' => $p['bank_account_no'] ?? null,
                'bank_account_name' => $p['bank_account_name'] ?? null, 'bank_verified' => $p['bank_verified'] ?? false, 'bank_verified_at' => $p['bank_verified_at'] ?? null, 'payment_remarks' => $p['payment_remarks'] ?? null];
        }
        return $out;
    }

    /**
     * The clear warnings a person carries: inactive, archived, category to confirm, an expired driving or guide
     * licence, expired vehicle insurance or revenue licence, no vehicle on file for a driver, no bank details.
     * Shown on the master, offered with the person on the Transport desk, and asked about before an engagement.
     */
    public function warningsFor(array $p): array
    {
        $w = [];
        $today = date('Y-m-d');
        if (!empty($p['archived_at'])) {
            $w[] = ['code' => 'archived', 'level' => 'block', 'text' => 'Archived on the master.'];
        } elseif (($p['status'] ?? 'active') !== 'active') {
            $w[] = ['code' => 'inactive', 'level' => 'block', 'text' => 'Marked inactive on the master.'];
        }
        if (empty($p['category_confirmed']) && array_key_exists('category_confirmed', $p)) {
            $w[] = ['code' => 'category', 'level' => 'warn', 'text' => 'Category not confirmed yet (proposed from the driver registration).'];
        }
        $cat = $p['category'] ?? null;
        $drives = $cat !== null ? self::CATEGORIES[$cat]['vehicle'] : in_array($p['kind'] ?? '', ['driver', 'both'], true);
        $guides = $cat !== null ? self::CATEGORIES[$cat]['guide_licence'] : in_array($p['kind'] ?? '', ['guide', 'both'], true);
        if ($drives) {
            if (!empty($p['licence_expiry']) && $p['licence_expiry'] < $today) {
                $w[] = ['code' => 'licence', 'level' => 'warn', 'text' => 'Driving licence expired on ' . $p['licence_expiry'] . '.'];
            } elseif (empty($p['licence_expiry'])) {
                $w[] = ['code' => 'licence_missing', 'level' => 'info', 'text' => 'Driving licence expiry not on file.'];
            }
        }
        if ($guides) {
            if (!empty($p['guide_licence_expiry']) && $p['guide_licence_expiry'] < $today) {
                $w[] = ['code' => 'guide_licence', 'level' => 'warn', 'text' => 'Guide licence expired on ' . $p['guide_licence_expiry'] . '.'];
            } elseif (empty($p['guide_licence_no']) && $cat !== null) {
                $w[] = ['code' => 'guide_licence_missing', 'level' => 'info', 'text' => 'Guide licence number not on file.'];
            }
        }
        if ($drives && $this->masterReady()) {
            $v = array_key_exists('vehicle', $p) ? $p['vehicle'] : $this->vehicle((int) $p['id']);
            if (!$v) {
                $w[] = ['code' => 'vehicle_missing', 'level' => 'info', 'text' => 'No vehicle on file.'];
            } else {
                if (!empty($v['insurance_expiry']) && $v['insurance_expiry'] < $today) {
                    $w[] = ['code' => 'insurance', 'level' => 'warn', 'text' => 'Vehicle insurance expired on ' . $v['insurance_expiry'] . ' (' . ($v['reg_no'] ?: 'vehicle') . ').'];
                }
                if (!empty($v['licence_expiry']) && $v['licence_expiry'] < $today) {
                    $w[] = ['code' => 'revenue_licence', 'level' => 'warn', 'text' => 'Revenue licence expired on ' . $v['licence_expiry'] . ' (' . ($v['reg_no'] ?: 'vehicle') . ').'];
                }
                if ($v['vehicle_type'] === 'van' && empty($v['roof_type'])) {
                    $w[] = ['code' => 'roof', 'level' => 'warn', 'text' => 'Van roof type not set.'];
                }
            }
        }
        if ($this->masterReady() && array_key_exists('has_bank', $p) && !$p['has_bank']) {
            $w[] = ['code' => 'bank_missing', 'level' => 'info', 'text' => 'No bank account on file.'];
        } elseif ($this->masterReady() && array_key_exists('bank_verified', $p) && !empty($p['has_bank']) && !$p['bank_verified']) {
            $w[] = ['code' => 'bank_unverified', 'level' => 'info', 'text' => 'Bank details not verified.'];
        }
        return $w;
    }

    // =====================================================================
    //  Writing the person
    // =====================================================================

    /** Add or change a person. The ledger account is checked against the ledger when given, never made up. */
    public function save(array $d, ?int $id = null, bool $withBank = false): array
    {
        if (!$this->available()) {
            throw new \RuntimeException('The Drivers & Guides master needs the schema 36 upgrade: open Tour Inquiry as the administrator and press Install now.');
        }
        $ready = $this->masterReady();
        $was = null;
        if ($id !== null) {
            $was = $this->get($id, true);
            if (!$was) {
                throw new \RuntimeException('No such person.');
            }
            if ($was['archived']) {
                throw new \RuntimeException($was['name'] . ' is archived. Restore the record first.');
            }
            // a change says only what changes: every field it does not mention stays as it was (the bank fields too)
            foreach (self::FIELDS as $k) {
                if (!array_key_exists($k, $d) && array_key_exists($k, $was)) {
                    $d[$k] = is_bool($was[$k]) ? (int) $was[$k] : ($k === 'role_codes' ? $was['codes'] : $was[$k]);
                }
            }
        }
        $name = self::cut($d['name'] ?? null, 190);
        if ($name === null) {
            throw new \InvalidArgumentException('The person needs their full legal name.');
        }
        $cat = null;
        if ($ready && !empty($d['category'])) {
            $cat = strtoupper(trim((string) $d['category']));
            if (!isset(self::CATEGORIES[$cat])) {
                throw new \InvalidArgumentException('The category must be JSD, JSCG or JSNTG.');
            }
        } elseif ($ready && $was !== null && !array_key_exists('category', $d)) {
            // a change that says nothing about the category keeps it (the category is confirmed or changed on its own)
            $cat = $was['category'];
        }
        $kind = $cat !== null ? self::CATEGORIES[$cat]['kind'] : (in_array($d['kind'] ?? '', ['driver', 'guide', 'both'], true) ? $d['kind'] : 'driver');
        $codes = $this->cleanCodes($d['role_codes'] ?? [], $kind);
        if ($cat !== null) {
            foreach (self::codesForCategory($cat) as $c) {
                if (isset($this->roles()[$c]) && !in_array($c, $codes, true)) {
                    $codes[] = $c;
                }
            }
        }
        $acc = self::cut($d['supplier_acc'] ?? null, 50);
        if ($acc !== null) {
            try {
                if (!$this->db->query('SELECT Sup_Acc_No FROM supplier WHERE Sup_Acc_No = ?', [$acc])->getRowArray()) {
                    throw new \InvalidArgumentException('No ledger account ' . $acc . ' exists. Accounts opens the account; leave it blank until then.');
                }
            } catch (\InvalidArgumentException $e) {
                throw $e;
            } catch (\Throwable $e) {
                // an install without the ledger table: kept as typed
            }
        }
        $bata = self::moneyOrNull($d['bata'] ?? null);
        if ($bata !== null && $bata < 0) {
            throw new \InvalidArgumentException('A rate cannot be negative.');
        }
        $overnight = self::moneyOrNull($d['overnight_bata'] ?? null);
        if ($overnight !== null && $overnight < 0) {
            throw new \InvalidArgumentException('The overnight / bata amount cannot be negative.');
        }
        $email = self::cut($d['email'] ?? null, 190);
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('That e-mail address does not look right.');
        }
        $vals = [
            'name' => $name, 'kind' => $kind, 'role_codes' => $codes ? implode(',', $codes) : null,
            'languages' => self::cut($d['languages'] ?? null, 190), 'phone' => self::cut($d['phone'] ?? null, 60), 'mobile' => self::cut($d['mobile'] ?? null, 60),
            'email' => $email, 'nic' => self::cut($d['nic'] ?? null, 40),
            'licence_no' => self::cut($d['licence_no'] ?? null, 60), 'licence_expiry' => self::dateOrNull($d['licence_expiry'] ?? null),
            'guide_licence_no' => self::cut($d['guide_licence_no'] ?? null, 60), 'guide_licence_expiry' => self::dateOrNull($d['guide_licence_expiry'] ?? null),
            'supplier_acc' => $acc, 'bata' => $bata, 'bata_currency' => $bata !== null ? (self::cut($d['bata_currency'] ?? null, 3) ?: 'LKR') : null,
            'vehicle_reg' => self::cut($d['vehicle_reg'] ?? null, 40), 'note' => self::cut($d['note'] ?? null, 500),
            'status' => ($d['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        ];
        if ($ready) {
            $garageSame = !empty($d['garage_same']) && !in_array($d['garage_same'], ['0', 'false', false], true);
            $vals += [
                'category' => $cat, 'category_confirmed' => array_key_exists('category_confirmed', $d) ? (int) !empty($d['category_confirmed']) : ($was !== null && !array_key_exists('category', $d) ? (int) $was['category_confirmed'] : 1),
                'nickname' => self::cut($d['nickname'] ?? null, 120), 'whatsapp' => self::cut($d['whatsapp'] ?? null, 60),
                'home_address' => self::cut($d['home_address'] ?? null, 400),
                'garage_address' => $garageSame ? null : self::cut($d['garage_address'] ?? null, 400), 'garage_same' => (int) $garageSame,
                'id_type' => ($d['id_type'] ?? 'nic') === 'passport' ? 'passport' : 'nic',
                'japanese_note' => self::cut($d['japanese_note'] ?? null, 400), 'overnight_bata' => $overnight,
            ];
            if ($cat === 'JSNTG') {
                // a national tourist guide drives nobody: the vehicle fields are not theirs
                $vals['vehicle_reg'] = null;
            }
            if ($withBank) {
                $verified = !empty($d['bank_verified']) && !in_array($d['bank_verified'], ['0', 'false', false], true);
                $vals += [
                    'bank_name' => self::cut($d['bank_name'] ?? null, 120), 'bank_branch' => self::cut($d['bank_branch'] ?? null, 120),
                    'bank_account_no' => self::cut($d['bank_account_no'] ?? null, 60), 'bank_account_name' => self::cut($d['bank_account_name'] ?? null, 190),
                    'bank_verified' => (int) $verified, 'bank_verified_at' => $verified ? (self::dateOrNull($d['bank_verified_at'] ?? null) ?: date('Y-m-d')) : null,
                    'bank_verified_by' => $verified ? (($was['bank_verified'] ?? false) && (string) ($was['bank_account_no'] ?? '') === (string) self::cut($d['bank_account_no'] ?? null, 60) ? ($was['bank_verified_by'] ?? $this->user) : $this->user) : null,
                    'payment_remarks' => self::cut($d['payment_remarks'] ?? null, 400),
                ];
                if ($verified && $vals['bank_account_no'] === null) {
                    throw new \InvalidArgumentException('Bank details cannot be marked verified without an account number.');
                }
                if ($verified && $was !== null && ($was['bank_verified'] ?? false) && (string) ($was['bank_account_no'] ?? '') !== (string) $vals['bank_account_no'] && empty($d['bank_reverified'])) {
                    // the account number changed under a verified mark: the mark comes off until somebody verifies the new number
                    $vals['bank_verified'] = 0;
                    $vals['bank_verified_at'] = null;
                    $vals['bank_verified_by'] = null;
                }
            }
        }
        $now = $this->now();
        if ($id === null) {
            // the same person twice is a mistake waiting to happen: the name (and the account, when given) is checked
            $dup = $this->db->query('SELECT id FROM tf_crew_person WHERE LOWER(name) = LOWER(?) AND archived_at IS NULL' . ($acc !== null ? ' AND (supplier_acc IS NULL OR supplier_acc = ?)' : ''), $acc !== null ? [$name, $acc] : [$name])->getRowArray();
            if ($dup) {
                throw new \InvalidArgumentException($name . ' is on the master already (record ' . $dup['id'] . '). Change that record, or add a distinguishing word to the name.');
            }
            if ($acc !== null) {
                $dupAcc = $this->db->query('SELECT id, name FROM tf_crew_person WHERE supplier_acc = ? AND archived_at IS NULL', [$acc])->getRowArray();
                if ($dupAcc) {
                    throw new \InvalidArgumentException('Ledger account ' . $acc . ' is already on the master as ' . $dupAcc['name'] . ' (record ' . $dupAcc['id'] . ') — one record per person.');
                }
            }
            $cols = array_keys($vals);
            $this->db->query('INSERT INTO tf_crew_person (' . implode(', ', $cols) . ', created_at, created_by) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ', ?, ?)', array_merge(array_values($vals), [$now, $this->user]));
            $id = (int) $this->db->insertID();
            $this->log($id, 'added', $name . ' added to the Driver & Guide Master (' . ($cat ?: $kind) . ($codes ? ' · ' . implode(', ', $codes) : '') . ').');
            if ($ready && ($bata !== null || $overnight !== null)) {
                // the agreed bata typed with the record: the first line of its history, from today
                $this->recordBata(['id' => $id, 'name' => $name, 'category' => $cat], $bata, $overnight, self::dateOrNull($d['bata_from'] ?? null) ?: date('Y-m-d'), self::cut($d['bata_note'] ?? null, 255) ?: 'set on the record');
            }
            return $this->get($id, $withBank);
        }
        if ($acc !== null && $acc !== (string) ($was['supplier_acc'] ?? '')) {
            $dupAcc = $this->db->query('SELECT id, name FROM tf_crew_person WHERE supplier_acc = ? AND archived_at IS NULL AND id <> ?', [$acc, $id])->getRowArray();
            if ($dupAcc) {
                throw new \InvalidArgumentException('Ledger account ' . $acc . ' is already on the master as ' . $dupAcc['name'] . ' (record ' . $dupAcc['id'] . ').');
            }
        }
        $sets = [];
        $bind = [];
        $changed = [];
        foreach ($vals as $k => $v) {
            $sets[] = "`{$k}` = ?";
            $bind[] = $v;
            $old = $was[$k] ?? null;
            if (is_bool($old)) {
                $old = (int) $old;
            }
            if ((string) ($old ?? '') !== (string) ($v ?? '')) {
                $changed[] = $k;
            }
        }
        $sets[] = '`updated_at` = ?';
        $bind[] = $now;
        $sets[] = '`updated_by` = ?';
        $bind[] = $this->user;
        $bind[] = $id;
        $this->db->query('UPDATE tf_crew_person SET ' . implode(', ', $sets) . ' WHERE id = ?', $bind);
        if ($ready && (in_array('bata', $changed, true) || in_array('overnight_bata', $changed, true))) {
            // a figure changed on the record is a new line of the bata history from today (or the date given); the old
            // line stays, and a tour that copied the old figure keeps it
            $this->recordBata(['id' => $id, 'name' => $name, 'category' => $cat], $bata, $overnight, self::dateOrNull($d['bata_from'] ?? null) ?: date('Y-m-d'), self::cut($d['bata_note'] ?? null, 255) ?: 'changed on the record');
        }
        if ($changed) {
            $shown = array_values(array_diff($changed, ['bank_verified_by']));
            $this->log($id, 'changed', $name . ': ' . implode(', ', $shown) . ' changed.');
            // the name and the phone travel to the crew lines that carry this person, so the pack reads right
            try {
                $this->db->query('UPDATE tf_transport_crew SET person_name = ?, person_phone = ? WHERE person_id = ? AND status IN (\'to_engage\', \'engaged\')', [$name, $vals['mobile'] ?: $vals['phone'], $id]);
            } catch (\Throwable $e) {
                // the crew columns not there yet
            }
        }
        return $this->get($id, $withBank);
    }

    /** The category proposed at migration confirmed (or changed) by a person who knows. */
    public function confirmCategory(int $id, string $cat): array
    {
        $cat = strtoupper(trim($cat));
        if (!isset(self::CATEGORIES[$cat])) {
            throw new \InvalidArgumentException('The category must be JSD, JSCG or JSNTG.');
        }
        $p = $this->get($id, true);
        if (!$p) {
            throw new \RuntimeException('No such person.');
        }
        $kind  = self::CATEGORIES[$cat]['kind'];
        $roles = $this->roles();
        // the quotation codes follow the category: its own codes, plus any code outside the three categories the person
        // still serves (an ESD, say) when it fits the new kind; the other categories' codes go
        $codes = [];
        foreach ($p['codes'] as $c) {
            if (isset(self::LINE_CATEGORY[$c]) || !isset($roles[$c])) {
                continue;
            }
            if ($kind === 'both' || $roles[$c]['kind'] === $kind) {
                $codes[] = $c;
            }
        }
        foreach (self::codesForCategory($cat) as $c) {
            if (isset($roles[$c]) && !in_array($c, $codes, true)) {
                array_unshift($codes, $c);
            }
        }
        $codes = array_values(array_unique($codes));
        // a national tourist guide drives nobody: the registration on the person row goes; a driver takes the vehicle on file
        $reg = $cat === 'JSNTG' ? null : (($this->vehicleRowFor($id)['reg_no'] ?? null) ?: $p['vehicle_reg']);
        $this->db->query('UPDATE tf_crew_person SET category = ?, category_confirmed = 1, kind = ?, role_codes = ?, vehicle_reg = ?, updated_at = ?, updated_by = ? WHERE id = ?',
            [$cat, $kind, implode(',', $codes), $reg, $this->now(), $this->user, $id]);
        $this->log($id, 'category', $p['name'] . ': category confirmed as ' . $cat . ($p['category'] !== $cat ? ' (was ' . ($p['category'] ?: 'none') . ')' : '') . '.');
        return $this->get($id, true);
    }

    public function archive(int $id, ?string $note = null): array
    {
        $p = $this->get($id, true);
        if (!$p) {
            throw new \RuntimeException('No such person.');
        }
        if ($p['archived']) {
            return $p;
        }
        $this->db->query('UPDATE tf_crew_person SET archived_at = ?, archived_by = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $this->now(), $this->user, $id]);
        $this->log($id, 'archived', $p['name'] . ' archived' . ($note ? ' — ' . $note : '') . '. The tours they were engaged on keep their name.');
        return $this->get($id, true);
    }

    public function restore(int $id): array
    {
        $p = $this->get($id, true);
        if (!$p) {
            throw new \RuntimeException('No such person.');
        }
        $this->db->query('UPDATE tf_crew_person SET archived_at = NULL, archived_by = NULL, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $id]);
        $this->log($id, 'restored', $p['name'] . ' restored to the master.');
        return $this->get($id, true);
    }

    // =====================================================================
    //  The vehicle normally supplied (JSD / JSCG)
    // =====================================================================

    private function vehicleRow(array $v, bool $withPhotos = true): array
    {
        $today = date('Y-m-d');
        $v['id'] = (int) $v['id'];
        $v['person_id'] = (int) $v['person_id'];
        $v['year'] = $v['year'] !== null ? (int) $v['year'] : null;
        $v['seats'] = $v['seats'] !== null ? (int) $v['seats'] : null;
        $v['max_pax_luggage'] = $v['max_pax_luggage'] !== null ? (int) $v['max_pax_luggage'] : null;
        $v['is_current'] = (int) $v['is_current'] === 1;
        $v['type_name'] = $v['vehicle_type'] ? (self::VEHICLE_TYPES[$v['vehicle_type']] ?? $v['vehicle_type']) : null;
        $v['roof_name'] = $v['roof_type'] ? (self::ROOF_TYPES[$v['roof_type']] ?? $v['roof_type']) : null;
        $v['ownership_name'] = $v['ownership'] ? (self::OWNERSHIP[$v['ownership']] ?? $v['ownership']) : null;
        $v['rate_class'] = array_key_exists('rate_class', $v) ? ($v['rate_class'] ?: null) : null;
        $classes = $this->rateClasses();
        $v['rate_class_name'] = $v['rate_class'] ? ($classes[$v['rate_class']]['name'] ?? $v['rate_class']) : null;
        $v['general_rate'] = $v['rate_class'] ? ($classes[$v['rate_class']]['rate_km'] ?? null) : null;
        $v['general_rate_from'] = $v['rate_class'] ? ($classes[$v['rate_class']]['valid_from'] ?? null) : null;
        $v['insurance_expired'] = !empty($v['insurance_expiry']) && $v['insurance_expiry'] < $today;
        $v['licence_expired']   = !empty($v['licence_expiry']) && $v['licence_expiry'] < $today;
        $v['insurance_file_url'] = !empty($v['insurance_file']) ? $this->fileUrl($v['person_id'], 'vehicle_insurance', $v['id']) : null;
        $v['licence_file_url']   = !empty($v['licence_file']) ? $this->fileUrl($v['person_id'], 'vehicle_licence', $v['id']) : null;
        $v['photos'] = [];
        if ($withPhotos) {
            try {
                foreach ($this->db->query('SELECT * FROM tf_crew_vehicle_photo WHERE vehicle_id = ? ORDER BY sort_order, id', [$v['id']])->getResultArray() as $ph) {
                    $ph['id'] = (int) $ph['id'];
                    $ph['url'] = $this->fileUrl($v['person_id'], 'vehicle_photo', $ph['id']);
                    $v['photos'][] = $ph;
                }
            } catch (\Throwable $e) {
                // no photo table
            }
        }
        $v['label'] = trim(implode(' ', array_filter([$v['make'], $v['model'], $v['year'] ? '(' . $v['year'] . ')' : null]))) ?: null;
        return $v;
    }

    /** The current vehicle row of a person whatever their category (a guide's vehicle stays on file, unoffered). */
    private function vehicleRowFor(int $personId): ?array
    {
        if (!$this->masterReady()) {
            return null;
        }
        return $this->db->query('SELECT * FROM tf_crew_vehicle WHERE person_id = ? AND is_current = 1 ORDER BY id DESC LIMIT 1', [$personId])->getRowArray() ?: null;
    }

    /** The current vehicle of a person, with its photos; null when none is on file. */
    public function vehicle(int $personId): ?array
    {
        if (!$this->masterReady()) {
            return null;
        }
        $v = $this->db->query('SELECT * FROM tf_crew_vehicle WHERE person_id = ? AND is_current = 1 ORDER BY id DESC LIMIT 1', [$personId])->getRowArray();
        return $v ? $this->vehicleRow($v) : null;
    }

    /** Every vehicle a person has had on file, current first. */
    public function vehicles(int $personId): array
    {
        if (!$this->masterReady()) {
            return [];
        }
        return array_map(fn ($v) => $this->vehicleRow($v), $this->db->query('SELECT * FROM tf_crew_vehicle WHERE person_id = ? ORDER BY is_current DESC, id DESC', [$personId])->getResultArray());
    }

    /** Add or change the vehicle normally supplied. A van needs its roof type. A JSNTG has no vehicle. */
    public function saveVehicle(int $personId, array $d, ?int $vehicleId = null): array
    {
        if (!$this->masterReady()) {
            throw new \RuntimeException('The Driver & Guide Master needs the schema 44 upgrade: open Tour Inquiry as the administrator and press Install now.');
        }
        $p = $this->get($personId, true);
        if (!$p) {
            throw new \RuntimeException('No such person.');
        }
        if ($p['category'] === 'JSNTG') {
            throw new \InvalidArgumentException('A national tourist guide carries no vehicle on this master; a driver and vehicle are allocated to the tour separately.');
        }
        $type = self::cut($d['vehicle_type'] ?? null, 10);
        if ($type !== null && !isset(self::VEHICLE_TYPES[$type])) {
            throw new \InvalidArgumentException('The vehicle type must be car, van, minibus or bus.');
        }
        $roof = self::cut($d['roof_type'] ?? null, 10);
        if ($type === 'van') {
            if ($roof === null || !isset(self::ROOF_TYPES[$roof])) {
                throw new \InvalidArgumentException('A van needs its roof type: high roof, or flat / low roof.');
            }
        } else {
            $roof = null;
        }
        $own = self::cut($d['ownership'] ?? null, 20);
        if ($own !== null && !isset(self::OWNERSHIP[$own])) {
            throw new \InvalidArgumentException('Ownership must be own, rented or other supplier.');
        }
        // the class on the Transport Rates master, which gives the vehicle its GENERAL rate per kilometre: as chosen, else
        // suggested from the type, the roof and the seats (a person can change it on the record)
        $class = strtoupper((string) self::cut($d['rate_class'] ?? null, 20)) ?: null;
        if ($class !== null && !isset($this->rateClasses()[$class])) {
            throw new \InvalidArgumentException('That vehicle class is not on the Transport Rates master.');
        }
        $year = (int) ($d['year'] ?? 0) ?: null;
        if ($year !== null && ($year < 1950 || $year > (int) date('Y') + 1)) {
            throw new \InvalidArgumentException('That vehicle year does not look right.');
        }
        $seats = (int) ($d['seats'] ?? 0) ?: null;
        $maxPax = (int) ($d['max_pax_luggage'] ?? 0) ?: null;
        if ($seats !== null && $maxPax !== null && $maxPax > $seats) {
            throw new \InvalidArgumentException('Passengers with luggage cannot exceed the seating capacity.');
        }
        if ($class === null && $type !== null && empty($d['rate_class_cleared'])) {
            $class = $this->suggestRateClass($type, $roof, $seats);
        }
        $vals = [
            'reg_no' => self::cut($d['reg_no'] ?? null, 40), 'make' => self::cut($d['make'] ?? null, 80), 'model' => self::cut($d['model'] ?? null, 80), 'year' => $year,
            'vehicle_type' => $type, 'roof_type' => $roof, 'rate_class' => $class, 'ownership' => $own, 'seats' => $seats, 'max_pax_luggage' => $maxPax,
            'insurance_expiry' => self::dateOrNull($d['insurance_expiry'] ?? null), 'licence_expiry' => self::dateOrNull($d['licence_expiry'] ?? null),
            'note' => self::cut($d['note'] ?? null, 400),
        ];
        if ($vals['reg_no'] === null && $vals['make'] === null && $type === null) {
            throw new \InvalidArgumentException('Give at least the registration number or the make and type.');
        }
        $now = $this->now();
        if ($vehicleId === null) {
            $cur = $this->vehicle($personId);
            if ($cur) {
                // the vehicle normally supplied changes: the old one stays on file, no longer current
                $this->db->query('UPDATE tf_crew_vehicle SET is_current = 0, updated_at = ?, updated_by = ? WHERE person_id = ? AND is_current = 1', [$now, $this->user, $personId]);
            }
            $cols = array_keys($vals);
            $this->db->query('INSERT INTO tf_crew_vehicle (person_id, ' . implode(', ', $cols) . ', is_current, created_at, created_by) VALUES (?, ' . implode(',', array_fill(0, count($cols), '?')) . ', 1, ?, ?)', array_merge([$personId], array_values($vals), [$now, $this->user]));
            $vehicleId = (int) $this->db->insertID();
            $this->log($personId, 'vehicle', $p['name'] . ': vehicle ' . ($vals['reg_no'] ?: ($vals['make'] ?: 'vehicle')) . ' put on file' . ($cur ? ' (replaces ' . ($cur['reg_no'] ?: 'the earlier one') . ')' : '') . '.');
        } else {
            $was = $this->db->query('SELECT * FROM tf_crew_vehicle WHERE id = ? AND person_id = ?', [$vehicleId, $personId])->getRowArray();
            if (!$was) {
                throw new \RuntimeException('No such vehicle on this record.');
            }
            $sets = [];
            $bind = [];
            $changed = [];
            foreach ($vals as $k => $v) {
                $sets[] = "`{$k}` = ?";
                $bind[] = $v;
                if ((string) ($was[$k] ?? '') !== (string) ($v ?? '')) {
                    $changed[] = $k;
                }
            }
            $bind[] = $now;
            $bind[] = $this->user;
            $bind[] = $vehicleId;
            $this->db->query('UPDATE tf_crew_vehicle SET ' . implode(', ', $sets) . ', updated_at = ?, updated_by = ? WHERE id = ?', $bind);
            if ($changed) {
                $this->log($personId, 'vehicle', $p['name'] . ': vehicle ' . ($vals['reg_no'] ?: $vehicleId) . ' — ' . implode(', ', $changed) . ' changed.');
            }
        }
        // the registration travels to the person's row too, as the pack and voucher always read it from there
        if ($vals['reg_no'] !== null) {
            $this->db->query('UPDATE tf_crew_person SET vehicle_reg = ? WHERE id = ?', [$vals['reg_no'], $personId]);
        }
        return $this->vehicleRow($this->db->query('SELECT * FROM tf_crew_vehicle WHERE id = ?', [$vehicleId])->getRowArray());
    }

    public function removeVehiclePhoto(int $personId, int $photoId): void
    {
        $ph = $this->db->query('SELECT p.*, v.person_id FROM tf_crew_vehicle_photo p JOIN tf_crew_vehicle v ON v.id = p.vehicle_id WHERE p.id = ?', [$photoId])->getRowArray();
        if (!$ph || (int) $ph['person_id'] !== $personId) {
            throw new \RuntimeException('No such photograph.');
        }
        $this->db->query('DELETE FROM tf_crew_vehicle_photo WHERE id = ?', [$photoId]);
        $path = $this->filePath($ph['file']);
        if ($path && is_file($path) && !str_starts_with((string) $ph['file'], 'legacy:')) {
            @unlink($path);
        }
        $this->log($personId, 'vehicle', 'A vehicle photograph removed.');
    }

    // =====================================================================
    //  The rate per kilometre (JSD / JSCG), LKR, with its history
    // =====================================================================

    public function rates(int $personId): array
    {
        if (!$this->masterReady()) {
            return [];
        }
        return array_map(static function ($r) {
            $r['id'] = (int) $r['id'];
            $r['rate_km'] = (float) $r['rate_km'];
            $r['in_force'] = $r['effective_from'] <= date('Y-m-d');
            return $r;
        }, $this->db->query('SELECT * FROM tf_crew_rate WHERE person_id = ? ORDER BY effective_from DESC, id DESC', [$personId])->getResultArray());
    }

    /** The rate in force today (the latest effective on or before today); null when none. */
    public function currentRate(int $personId, ?string $on = null): ?array
    {
        if (!$this->masterReady()) {
            return null;
        }
        $r = $this->db->query('SELECT * FROM tf_crew_rate WHERE person_id = ? AND effective_from <= ? ORDER BY effective_from DESC, id DESC LIMIT 1', [$personId, $on ?: date('Y-m-d')])->getRowArray();
        if (!$r) {
            return null;
        }
        $r['id'] = (int) $r['id'];
        $r['rate_km'] = (float) $r['rate_km'];
        return $r;
    }

    /** A new rate per kilometre from a date; the earlier rates stay. LKR only. */
    public function addRate(int $personId, $rateKm, $from, ?string $note = null): array
    {
        if (!$this->masterReady()) {
            throw new \RuntimeException('The Driver & Guide Master needs the schema 44 upgrade.');
        }
        $p = $this->get($personId, true);
        if (!$p) {
            throw new \RuntimeException('No such person.');
        }
        if ($p['category'] === 'JSNTG') {
            throw new \InvalidArgumentException('A national tourist guide has no rate per kilometre; their guide rate is on the record.');
        }
        $rate = self::moneyOrNull($rateKm);
        if ($rate === null || $rate <= 0) {
            throw new \InvalidArgumentException('Give the rate per kilometre in LKR.');
        }
        $date = self::dateOrNull($from) ?: date('Y-m-d');
        $dup = $this->db->query('SELECT id FROM tf_crew_rate WHERE person_id = ? AND effective_from = ?', [$personId, $date])->getRowArray();
        if ($dup) {
            $this->db->query('UPDATE tf_crew_rate SET rate_km = ?, note = ?, set_at = ?, set_by = ? WHERE id = ?', [$rate, self::cut($note, 255), $this->now(), $this->user, (int) $dup['id']]);
            $this->log($personId, 'rate', $p['name'] . ': rate from ' . $date . ' corrected to LKR ' . number_format($rate, 2) . ' per km.');
        } else {
            $this->db->query('INSERT INTO tf_crew_rate (person_id, rate_km, currency, effective_from, note, set_at, set_by) VALUES (?, ?, ?, ?, ?, ?, ?)', [$personId, $rate, 'LKR', $date, self::cut($note, 255), $this->now(), $this->user]);
            $this->log($personId, 'rate', $p['name'] . ': LKR ' . number_format($rate, 2) . ' per km from ' . $date . '.');
        }
        return $this->rates($personId);
    }

    // =====================================================================
    //  Files: the photo, the licence copies, the vehicle documents and photographs
    // =====================================================================

    /** Where the files live: outside the web root, served by the application to signed-in users only. */
    public function filesDir(): string
    {
        $dir = $this->filesDir ?? ((defined('WRITEPATH') ? WRITEPATH : sys_get_temp_dir() . '/') . 'crew_files');
        return rtrim($dir, '/\\');
    }

    private function publicRoot(): string
    {
        return rtrim($this->publicRoot ?? (defined('FCPATH') ? FCPATH : sys_get_temp_dir() . '/'), '/\\');
    }

    /** The path on disk of a stored file name ('p12/licence_ab12.jpg', or 'legacy:driver/license/x.png' under public/uploads). */
    public function filePath(?string $file): ?string
    {
        $file = trim((string) $file);
        if ($file === '') {
            return null;
        }
        if (str_starts_with($file, 'legacy:')) {
            $rel = str_replace(['..', '\\'], ['', '/'], substr($file, 7));
            return $this->publicRoot() . '/uploads/' . ltrim($rel, '/');
        }
        $rel = str_replace(['..', '\\'], ['', '/'], $file);
        return $this->filesDir() . '/' . ltrim($rel, '/');
    }

    /** The URL the screen uses: master_files/crew_people/file/<person>/<kind>[/<id>] — served by the controller. */
    public function fileUrl(int $personId, string $kind, ?int $id = null): string
    {
        return 'master_files/crew_people/file/' . $personId . '/' . $kind . ($id !== null ? '/' . $id : '');
    }

    /** The stored file name of a person's / vehicle's file of this kind, with its content type; null when none. */
    public function fileFor(int $personId, string $kind, ?int $id = null): ?array
    {
        $file = null;
        if (isset(self::FILE_KINDS[$kind])) {
            $r = $this->db->query('SELECT ' . self::FILE_KINDS[$kind] . ' AS f FROM tf_crew_person WHERE id = ?', [$personId])->getRowArray();
            $file = $r['f'] ?? null;
        } elseif ($kind === 'vehicle_insurance' || $kind === 'vehicle_licence') {
            $col = $kind === 'vehicle_insurance' ? 'insurance_file' : 'licence_file';
            $r = $this->db->query("SELECT {$col} AS f FROM tf_crew_vehicle WHERE id = ? AND person_id = ?", [(int) $id, $personId])->getRowArray();
            $file = $r['f'] ?? null;
        } elseif ($kind === 'vehicle_photo') {
            $r = $this->db->query('SELECT p.file AS f FROM tf_crew_vehicle_photo p JOIN tf_crew_vehicle v ON v.id = p.vehicle_id WHERE p.id = ? AND v.person_id = ?', [(int) $id, $personId])->getRowArray();
            $file = $r['f'] ?? null;
        }
        if (!$file) {
            return null;
        }
        $path = $this->filePath($file);
        $ext = strtolower(pathinfo((string) $file, PATHINFO_EXTENSION));
        $types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
        return ['file' => $file, 'path' => $path, 'exists' => $path !== null && is_file($path), 'type' => $types[$ext] ?? 'application/octet-stream', 'name' => basename((string) $file)];
    }

    /** Store an uploaded file for a person (photo, licence, guide_licence), a vehicle (insurance, licence) or as a vehicle photograph. */
    public function storeFile(int $personId, string $kind, string $tmpPath, string $clientName, array $opts = []): array
    {
        if (!$this->masterReady()) {
            throw new \RuntimeException('The Driver & Guide Master needs the schema 44 upgrade.');
        }
        $p = $this->get($personId, true);
        if (!$p) {
            throw new \RuntimeException('No such person.');
        }
        $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            throw new \InvalidArgumentException('Only JPG, PNG, WEBP or PDF files can be stored here.');
        }
        $cat = $p['category'] ?? null;
        if ($kind === 'guide_licence' && $cat !== null && !self::CATEGORIES[$cat]['guide_licence']) {
            throw new \InvalidArgumentException('A ' . $cat . ' holds no guide licence on this master.');
        }
        if (in_array($kind, ['licence', 'vehicle_insurance', 'vehicle_licence', 'vehicle_photo'], true) && $cat === 'JSNTG') {
            throw new \InvalidArgumentException('A national tourist guide carries no vehicle or driving licence on this master.');
        }
        if (!is_file($tmpPath)) {
            throw new \InvalidArgumentException('The file did not arrive.');
        }
        if (filesize($tmpPath) > self::MAX_FILE_MB * 1024 * 1024) {
            throw new \InvalidArgumentException('The file is larger than ' . self::MAX_FILE_MB . ' MB.');
        }
        $dir = $this->filesDir() . '/p' . $personId;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('The folder for the files could not be created (' . $dir . ').');
        }
        $base = $this->filesDir();
        if (!is_file($base . '/.htaccess')) {
            @file_put_contents($base . '/.htaccess', "<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n<IfModule !authz_core_module>\n    Deny from all\n</IfModule>\n");
        }
        $stored = 'p' . $personId . '/' . preg_replace('/[^a-z_]/', '', $kind) . '_' . date('Ymd-His') . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
        $dest = $base . '/' . $stored;
        if (!(@rename($tmpPath, $dest) || @copy($tmpPath, $dest) || @move_uploaded_file($tmpPath, $dest))) {
            throw new \RuntimeException('The file could not be stored.');
        }
        $now = $this->now();
        if (isset(self::FILE_KINDS[$kind])) {
            $col = self::FILE_KINDS[$kind];
            $old = $p[$col] ?? null;
            $this->db->query("UPDATE tf_crew_person SET {$col} = ?, updated_at = ?, updated_by = ? WHERE id = ?", [$stored, $now, $this->user, $personId]);
            if ($old && !str_starts_with((string) $old, 'legacy:')) {
                $op = $this->filePath($old);
                if ($op && is_file($op)) {
                    @unlink($op);
                }
            }
            $this->log($personId, 'file', $p['name'] . ': ' . str_replace('_', ' ', $kind) . ' copy stored (' . $clientName . ').');
            return ['kind' => $kind, 'file' => $stored, 'url' => $this->fileUrl($personId, $kind)];
        }
        $vehicleId = (int) ($opts['vehicle_id'] ?? 0);
        if (!$vehicleId) {
            $v = $this->vehicle($personId);
            $vehicleId = $v ? (int) $v['id'] : 0;
        }
        if (!$vehicleId) {
            @unlink($dest);
            throw new \InvalidArgumentException('Put the vehicle on file first, then its documents and photographs.');
        }
        $v = $this->db->query('SELECT * FROM tf_crew_vehicle WHERE id = ? AND person_id = ?', [$vehicleId, $personId])->getRowArray();
        if (!$v) {
            @unlink($dest);
            throw new \RuntimeException('No such vehicle on this record.');
        }
        if ($kind === 'vehicle_insurance' || $kind === 'vehicle_licence') {
            $col = $kind === 'vehicle_insurance' ? 'insurance_file' : 'licence_file';
            $this->db->query("UPDATE tf_crew_vehicle SET {$col} = ?, updated_at = ?, updated_by = ? WHERE id = ?", [$stored, $now, $this->user, $vehicleId]);
            if (!empty($v[$col]) && !str_starts_with((string) $v[$col], 'legacy:')) {
                $op = $this->filePath($v[$col]);
                if ($op && is_file($op)) {
                    @unlink($op);
                }
            }
            $this->log($personId, 'file', $p['name'] . ': vehicle ' . ($kind === 'vehicle_insurance' ? 'insurance' : 'revenue licence') . ' copy stored (' . $clientName . ').');
            return ['kind' => $kind, 'file' => $stored, 'url' => $this->fileUrl($personId, $kind, $vehicleId), 'vehicle_id' => $vehicleId];
        }
        if ($kind === 'vehicle_photo') {
            if ($ext === 'pdf') {
                @unlink($dest);
                throw new \InvalidArgumentException('A vehicle photograph must be an image.');
            }
            $view = in_array($opts['view'] ?? '', ['exterior', 'interior', 'other'], true) ? $opts['view'] : 'exterior';
            $n = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_crew_vehicle_photo WHERE vehicle_id = ?', [$vehicleId])->getRowArray()['n'] ?? 0);
            $this->db->query('INSERT INTO tf_crew_vehicle_photo (vehicle_id, file, view, caption, sort_order, added_at, added_by) VALUES (?, ?, ?, ?, ?, ?, ?)', [$vehicleId, $stored, $view, self::cut($opts['caption'] ?? null, 120), $n + 1, $now, $this->user]);
            $id = (int) $this->db->insertID();
            $this->log($personId, 'file', $p['name'] . ': vehicle photograph (' . $view . ') added.');
            return ['kind' => $kind, 'file' => $stored, 'url' => $this->fileUrl($personId, 'vehicle_photo', $id), 'vehicle_id' => $vehicleId, 'photo_id' => $id];
        }
        @unlink($dest);
        throw new \InvalidArgumentException('Unknown file kind.');
    }

    // =====================================================================
    //  The ledger and the one-time migration of the driver registration
    // =====================================================================

    /**
     * The 6-9-11 ledger as a list to start a record from: name, phones, NIC, language, bata and the vehicle against
     * them, with whether a master record already carries the account. Read only.
     */
    public function ledger(): array
    {
        $out = [];
        try {
            $have = [];
            if ($this->available()) {
                foreach ($this->db->query('SELECT supplier_acc FROM tf_crew_person WHERE supplier_acc IS NOT NULL')->getResultArray() as $r) {
                    $have[(string) $r['supplier_acc']] = true;
                }
            }
            // the Driver registration: Supplier → New Driver / All Drivers keeps the drivers as supplier records of category 2
            // ("Transport Agency"), with their NIC, phones, language, bata, vehicle and licence dates; anyone else on the
            // ledger with a driving or guide licence date, or a vehicle, is offered too. Hotels and the other categories are not.
            $rows = $this->db->query("SELECT Sup_Acc_No AS acc, Name AS name, TP AS phone, Mobile AS mobile, email, NIC AS nic, language, bata, v_num, dled, gled, sup_cat FROM supplier WHERE Sup_Acc_No LIKE '6-9-11-%' AND Name IS NOT NULL AND Name <> '' AND (sup_cat = 2 OR dled > '1900-01-01' OR gled > '1900-01-01' OR (v_num IS NOT NULL AND TRIM(v_num) <> '')) ORDER BY Name")->getResultArray();
            $clean = static fn ($v) => trim((string) ($v ?? '')) !== '' && !in_array(strtoupper(trim((string) $v)), ['0', '-', 'N/A', 'NA', 'NIL', 'NONE', 'NULL', '0000-00-00'], true) ? trim((string) $v) : null;
            foreach ($rows as $r) {
                $out[] = [
                    'acc' => (string) $r['acc'], 'name' => trim((string) $r['name']), 'phone' => $clean($r['phone']), 'mobile' => $clean($r['mobile']), 'email' => $clean($r['email']),
                    'nic' => $clean($r['nic']), 'languages' => $clean($r['language']), 'bata' => ($r['bata'] === null || $r['bata'] === '' || (float) $r['bata'] <= 0) ? null : (float) $r['bata'],
                    'vehicle_reg' => $clean($r['v_num']), 'driver_licence' => $clean($r['dled']), 'guide_licence' => $clean($r['gled']),
                    'on_master' => isset($have[(string) $r['acc']]),
                ];
            }
        } catch (\Throwable $e) {
            // no ledger on this install
        }
        return $out;
    }

    /**
     * Schema 44, once: every driver on the registration (supplier category 2, ledger 6-9-11) not yet on the master
     * becomes a master record linked to its ledger account — name, NIC, phones, e-mail, address, language, licence
     * dates and copies, the vehicle (number, type, passengers, images, document dates), the km rate (in force from
     * the day of the migration), the bata, the bank details (not verified). The category is PROPOSED by rule — a
     * guide licence date or copy with no driving licence and no vehicle → JSNTG; a guide licence with either → JSCG;
     * otherwise JSD — and every migrated record is marked "category to confirm" until a person confirms it. A record
     * already carrying the account is left alone, so the step can run again and changes nothing the second time.
     * The registration itself is never changed: it stays the accounting record.
     */
    public static function migrateRegistration($db, string $user = 'installer'): array
    {
        $me = new self($db, $user);
        if (!$me->masterReady()) {
            return [];
        }
        try {
            if (!$db->query("SHOW TABLES LIKE 'supplier'")->getResultArray()) {
                return [];
            }
            $have = [];
            foreach ($db->query('SELECT supplier_acc FROM tf_crew_person WHERE supplier_acc IS NOT NULL')->getResultArray() as $r) {
                $have[(string) $r['supplier_acc']] = true;
            }
            $cols = array_map(static fn ($c) => strtolower((string) array_values($c)[0]), $db->query('SHOW COLUMNS FROM supplier')->getResultArray());
            $col = static fn (string $c) => in_array(strtolower($c), $cols, true) ? "`{$c}`" : 'NULL';
            $rows = $db->query("SELECT Sup_Acc_No AS acc, Name AS name, " . $col('Address') . " AS address, " . $col('NIC') . " AS nic, " . $col('TP') . " AS phone, " . $col('Mobile') . " AS mobile, " . $col('email') . " AS email,
                    " . $col('language') . " AS language, " . $col('bata') . " AS bata, " . $col('km_rate') . " AS km_rate, " . $col('v_num') . " AS v_num, " . $col('type') . " AS vtype, " . $col('passenger_count') . " AS pax,
                    " . $col('dled') . " AS dled, " . $col('gled') . " AS gled, " . $col('dlimg') . " AS dlimg, " . $col('glimg') . " AS glimg, " . $col('vimg') . " AS vimg, " . $col('vlimg') . " AS vlimg, " . $col('viimg') . " AS viimg,
                    " . $col('vehi_license_expire_date') . " AS vled, " . $col('vehi_insurance_expire_date') . " AS vied,
                    " . $col('bank_name') . " AS bank_name, " . $col('branch_name') . " AS branch_name, " . $col('sup_bank_acc') . " AS bank_acc, " . $col('acc_holder') . " AS acc_holder, " . $col('remark') . " AS remark, " . $col('is_avalible') . " AS available
                FROM supplier WHERE sup_cat = 2 AND Sup_Acc_No LIKE '6-9-11-%' AND Name IS NOT NULL AND TRIM(Name) <> '' ORDER BY ID")->getResultArray();
        } catch (\Throwable $e) {
            return ['The driver registration could not be read for the master (' . $e->getMessage() . '); add the people by hand or from the ledger.'];
        }
        $clean = static fn ($v) => trim((string) ($v ?? '')) !== '' && !in_array(strtoupper(trim((string) $v)), ['0', '-', 'N/A', 'NA', 'NIL', 'NONE', 'NULL', '0000-00-00'], true) ? trim((string) $v) : null;
        $date = static fn ($v) => self::dateOrNull($v);
        $roles = $me->roles();
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $n = ['JSD' => 0, 'JSCG' => 0, 'JSNTG' => 0, 'none' => 0];
        $skipped = 0;
        foreach ($rows as $r) {
            $acc = trim((string) $r['acc']);
            if ($acc === '' || isset($have[$acc])) {
                $skipped++;
                continue;
            }
            $name = trim(preg_replace('/\s+/', ' ', (string) $r['name']));
            $remark = strtoupper((string) $clean($r['remark']));
            $nameU  = strtoupper($name);
            // a nickname written on the name in brackets — "B.B.T Gunarathna (Buddhima)" — becomes the nickname; a
            // description in brackets ("(National Guide)") stays on the name
            $nickname = null;
            if (preg_match('/^(.*\S)\s*\(([A-Za-z]{2,30})\)\s*$/', $name, $m) && !preg_match('/GUIDE|DRIVER|NATIONAL|CHAUFF|PVT|LTD|KDH|PROVIDER/i', $m[2])) {
                $name = trim($m[1]);
                $nickname = $m[2];
            }
            $hasGuide = $date($r['gled']) !== null || $clean($r['glimg']) !== null;
            $hasDrive = $date($r['dled']) !== null || $clean($r['dlimg']) !== null;
            // the vehicle number: the registration's own field, else the one written on the remark ("VEHICLE NO - CAK 2243",
            // "VEHICLE WP KL 4104", "VEHICLE NO : CAX1235") — the first one only; a provider's list is left to be read
            $vnum = $clean($r['v_num']);
            if ($vnum === null && preg_match('/VEHICLE[^A-Z0-9]*(?:NO\'?S?\.?)?[^A-Z0-9]*((?:[A-Z]{2,3}[\s-]*){1,2}\d{4})\b/', $remark, $m)) {
                $vnum = trim(preg_replace('/\s+/', ' ', $m[1]));
            }
            $vtypeHint = $clean($r['vtype']) ?? (preg_match('/\bKDH\b|HIACE|\bVAN\b/', $remark) ? 'van' : null);
            $hasVehicle = $vnum !== null || $vtypeHint !== null || $clean($r['vimg']) !== null;
            $langU = strtoupper((string) $clean($r['language']));
            $japanese = str_contains($langU, 'JAPAN') || preg_match('/\bJS(D|CG|NTG)\b|JAPANESE/', $remark);
            $english  = str_contains($langU, 'ENGLISH');
            // not a person the desk engages: a vehicle provider, a company, a placeholder — kept on the master to be
            // classified or archived by hand, never offered on a tour until somebody decides
            $notPerson = (bool) preg_match('/PROVIDER|NO TRANSPORT|NEEDS TO BE REMOVED|\bTOURS?\b|TRANSPORT\b|COMPANY|PVT|LTD|STUDIO/', $remark . ' ' . $nameU);
            // the category PROPOSED from what the registration says — a word on the remark first, then the licences
            $cat = null;
            if ($notPerson) {
                $cat = null;
                $why = 'the registration reads as a company, a vehicle provider or a placeholder, not a driver or guide';
            } elseif (preg_match('/\bJSNTG\b/', $remark)) {
                $cat = 'JSNTG';
                $why = 'the remark says JSNTG';
            } elseif (preg_match('/\bJSCG\b/', $remark)) {
                $cat = 'JSCG';
                $why = 'the remark says JSCG';
            } elseif (preg_match('/\bJSD\b/', $remark)) {
                $cat = 'JSD';
                $why = 'the remark says JSD';
            } elseif (!$japanese || preg_match('/\bESD\b/', $remark)) {
                $cat = null;   // not Japanese speaking as far as the registration says: outside the three categories
                $why = preg_match('/\bESD\b/', $remark) ? 'the remark says ESD (English speaking driver)' : ($langU !== '' ? 'the language on the registration is ' . ucfirst(strtolower($langU)) . ', not Japanese' : 'no language on the registration, so not known to speak Japanese');
            } elseif (preg_match('/NATIONAL (TOURIST )?GUIDE|ONLY GUID/', $remark) && !preg_match('/CHAUFF/', $remark)) {
                $cat = 'JSNTG';
                $why = 'the remark says national guide / only guiding';
            } elseif (preg_match('/CHAUFF/', $remark)) {
                $cat = 'JSCG';
                $why = 'the remark says chauffeur guide';
            } else {
                $cat = $hasGuide ? (($hasDrive || $hasVehicle) ? 'JSCG' : 'JSNTG') : 'JSD';
                $why = $hasGuide ? (($hasDrive || $hasVehicle) ? 'a guide licence and a driving licence or vehicle on the registration' : 'a guide licence and no driving licence or vehicle') : ($hasDrive ? 'a driving licence and no guide licence' : 'Japanese speaking with no guide licence on the registration');
            }
            if ($cat === null) {
                // outside the three: an English driver or guide keeps the code the quotation uses; otherwise nothing is assumed
                $kind  = $hasGuide && !$hasDrive && !$hasVehicle ? 'guide' : 'driver';
                $codes = [];
                if (preg_match('/\bESD\b/', $remark) || ($english && ($hasDrive || $hasVehicle) && !$hasGuide)) {
                    $codes = isset($roles['ESD']) ? ['ESD'] : [];
                    $kind  = 'driver';
                } elseif ($english && $hasGuide && !$hasDrive && !$hasVehicle) {
                    $codes = isset($roles['GUIDE_EN']) ? ['GUIDE_EN'] : [];
                    $kind  = 'guide';
                }
            } else {
                $kind  = self::CATEGORIES[$cat]['kind'];
                $codes = array_values(array_filter(self::codesForCategory($cat), static fn ($c) => isset($roles[$c])));
            }
            $lang = $clean($r['language']);
            $langs = $lang !== null ? implode(', ', array_unique(array_map(static fn ($l) => ucfirst(strtolower(trim($l))), preg_split('/[,\s]+/', $lang)))) : ($cat !== null ? 'Japanese' : null);
            $bata = ($r['bata'] === null || (float) $r['bata'] <= 0) ? null : round((float) $r['bata'], 2);
            // a WhatsApp number written beside the mobile ("0761775654, +81806… (WHATS APP)")
            $mobile = $clean($r['mobile']);
            $whatsapp = null;
            if ($mobile !== null && preg_match('/^(.*?)[\s,\/]*([+\d][\d\s+-]{6,})\s*\(?\s*WHATS\s*APP\s*\)?\s*$/i', $mobile, $m)) {
                $whatsapp = trim($m[2]);
                $mobile = trim($m[1], " ,/") ?: $whatsapp;
            }
            $noteBits = [];
            if ($clean($r['remark']) !== null) {
                $noteBits[] = $clean($r['remark']);
            }
            if ($notPerson) {
                $noteBits[] = 'Registration record that may not be a driver or guide — classify or archive.';
            }
            $status = preg_match('/NEEDS TO BE REMOVED|NO TRANSPORT/', $remark . ' ' . $nameU) ? 'inactive' : 'active';
            $vals = [
                'name' => mb_substr($name, 0, 190), 'nickname' => $nickname, 'kind' => $kind, 'category' => $cat, 'category_confirmed' => 0, 'role_codes' => $codes ? implode(',', $codes) : null,
                'languages' => $langs !== null ? mb_substr($langs, 0, 190) : null, 'phone' => self::cut($clean($r['phone']), 60), 'mobile' => self::cut($mobile, 60), 'whatsapp' => self::cut($whatsapp, 60),
                'email' => filter_var($clean($r['email']), FILTER_VALIDATE_EMAIL) ? $clean($r['email']) : null,
                'home_address' => self::cut($clean($r['address']), 400), 'nic' => self::cut($clean($r['nic']), 40), 'id_type' => 'nic',
                'licence_expiry' => $date($r['dled']), 'licence_file' => $clean($r['dlimg']) !== null ? 'legacy:driver/license/' . basename((string) $r['dlimg']) : null,
                'guide_licence_expiry' => $date($r['gled']), 'guide_licence_file' => $clean($r['glimg']) !== null ? 'legacy:driver/license/guide/' . basename((string) $r['glimg']) : null,
                'supplier_acc' => $acc, 'bata' => $bata, 'bata_currency' => $bata !== null ? 'LKR' : null, 'vehicle_reg' => $cat !== 'JSNTG' ? self::cut($vnum, 40) : null,
                'note' => self::cut($noteBits ? implode(' · ', $noteBits) : null, 500), 'status' => $status,
                'bank_name' => self::cut($clean($r['bank_name']), 120), 'bank_branch' => self::cut($clean($r['branch_name']), 120), 'bank_account_no' => self::cut($clean($r['bank_acc']), 60),
                'bank_account_name' => self::cut($clean($r['acc_holder']), 190), 'bank_verified' => 0,
                'migrated_from' => $acc, 'migrated_at' => $now,
            ];
            try {
                $c = array_keys($vals);
                $db->query('INSERT INTO tf_crew_person (' . implode(', ', $c) . ', created_at, created_by) VALUES (' . implode(',', array_fill(0, count($c), '?')) . ', ?, ?)', array_merge(array_values($vals), [$now, $user]));
                $id = (int) $db->insertID();
                $have[$acc] = true;
                $n[$cat ?? 'none']++;
                // the vehicle normally supplied, from the registration
                if ($cat !== 'JSNTG' && $hasVehicle) {
                    $t = strtolower((string) $vtypeHint);
                    $type = $t === '' ? null : (str_contains($t, 'bus') || str_contains($t, 'coach') ? 'bus' : (str_contains($t, 'van') || str_contains($t, 'kdh') || str_contains($t, 'hiace') ? 'van' : (str_contains($t, 'mini') ? 'minibus' : 'car')));
                    $roof = $type === 'van' ? (str_contains($t, 'high') ? 'high' : (str_contains($t, 'flat') || str_contains($t, 'low') ? 'flat' : null)) : null;
                    $db->query('INSERT INTO tf_crew_vehicle (person_id, reg_no, make, model, vehicle_type, roof_type, rate_class, seats, insurance_expiry, insurance_file, licence_expiry, licence_file, note, is_current, created_at, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,?)', [
                        $id, self::cut($vnum, 40), null, null, $type, $roof, $type !== null ? $me->suggestRateClass($type, $roof, (int) ($r['pax'] ?? 0) ?: null) : null, (int) ($r['pax'] ?? 0) ?: null,
                        $date($r['vied']), $clean($r['viimg']) !== null ? 'legacy:vehicle/insurance/' . basename((string) $r['viimg']) : null,
                        $date($r['vled']), $clean($r['vlimg']) !== null ? 'legacy:vehicle/license/' . basename((string) $r['vlimg']) : null,
                        $clean($r['vtype']) !== null ? 'Registered as "' . $clean($r['vtype']) . '"' : ($vnum !== null && $clean($r['v_num']) === null ? 'Vehicle number read from the registration remark' : null), $now, $user,
                    ]);
                    $vid = (int) $db->insertID();
                    if ($clean($r['vimg']) !== null) {
                        $db->query('INSERT INTO tf_crew_vehicle_photo (vehicle_id, file, view, caption, sort_order, added_at, added_by) VALUES (?, ?, ?, ?, 1, ?, ?)', [$vid, 'legacy:vehicle/' . basename((string) $r['vimg']), 'exterior', 'From the driver registration', $now, $user]);
                    }
                }
                // the rate per kilometre on the registration, in force from the day of the migration — the driver's SPECIAL rate
                if ($cat !== 'JSNTG' && $r['km_rate'] !== null && (float) $r['km_rate'] > 0) {
                    $db->query('INSERT INTO tf_crew_rate (person_id, rate_km, currency, effective_from, note, set_at, set_by) VALUES (?, ?, ?, ?, ?, ?, ?)', [$id, round((float) $r['km_rate'], 2), 'LKR', $today, 'From the driver registration (Supplier → New Driver)', $now, $user]);
                }
                // the agreed bata on the registration: the first line of the person's bata history, in force from today
                if ($bata !== null) {
                    $db->query('INSERT INTO tf_crew_bata (person_id, bata, overnight, currency, effective_from, note, set_at, set_by) VALUES (?, ?, NULL, ?, ?, ?, ?, ?)', [$id, $bata, 'LKR', $today, 'From the driver registration (Supplier → New Driver)', $now, $user]);
                }
                $me->log($id, 'migrated', $name . ' brought onto the Driver & Guide Master from the driver registration (' . $acc . ')' . ($cat ? ' as ' . $cat . ' — proposed because ' . $why . '; category to confirm.' : ' — no category: ' . $why . '; to classify by hand.'));
            } catch (\Throwable $e) {
                $skipped++;
            }
        }
        $total = array_sum($n);
        if ($total === 0) {
            return $skipped ? ['The driver registration is already on the Driver & Guide Master (' . $skipped . ' records); nothing migrated again.'] : [];
        }
        return ['Driver registration brought onto the Driver & Guide Master: ' . $total . ' records — ' . $n['JSD'] . ' proposed JSD, ' . $n['JSCG'] . ' JSCG, ' . $n['JSNTG'] . ' JSNTG (from the licences, languages and remarks), ' . $n['none'] . ' with no category to classify by hand; every one marked "to confirm" until somebody confirms it. The registration itself is unchanged.' . ($skipped ? ' ' . $skipped . ' already on the master.' : '')];
    }

    private function log(int $id, string $action, string $summary): void
    {
        try {
            if (!$this->db->query("SHOW TABLES LIKE 'tf_activity'")->getResultArray()) {
                return;
            }
            $this->db->query(
                'INSERT INTO tf_activity (entity, entity_id, intake_id, tour_id, action, summary, changes_json, by_user, by_name, `at`) VALUES (?,?,?,?,?,?,?,?,?,?)',
                ['crew_person', $id, null, null, $action, mb_substr($summary, 0, 400), null, $this->user, $this->actor, $this->now()]
            );
        } catch (\Throwable $e) {
            // the log never stops the work
        }
    }

    /** The record's own history, from the activity log. */
    public function history(int $id, int $limit = 40): array
    {
        try {
            return $this->db->query("SELECT action, summary, by_name, `at` FROM tf_activity WHERE entity = 'crew_person' AND entity_id = ? ORDER BY id DESC LIMIT " . (int) $limit, [$id])->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
