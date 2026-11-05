<?php

namespace App\Libraries\TourFlow;

/**
 * Meal Master (schema 40, tf-meals-r1) — lunches and dinners outside the hotel for passengers
 * travelling on Half Board (HB) packages. Two meal types only: lunch and dinner.
 *
 *   tf_meal         one restaurant's meal at one location for one period: the supplier (a record
 *                   of the Supplier master, never typed in), the restaurant's name as the meal is
 *                   sold, the location, address and contact, the cuisine and what the meal is, the
 *                   adult and child rate in USD or LKR, the taxes (included in the rates, or a
 *                   percentage added on top), Valid From / Valid To, in use or not, remarks
 *   tf_meal_route   the legs of the route the meal serves, in the direction travelled
 *
 * Nothing of the route, the passengers or the day is kept here. A meal on a booking is the
 * inquiry's own lunch or dinner line (tf_intake_service, kind lunch / dinner) linked to a meal
 * of this master by meal_id, with the adults and children it is for and the time; the day, its
 * date and its legs are the inquiry's own. ServiceLookup prices a linked line from here:
 *
 *   (adults × adult rate + children × child rate) + taxes = the meal's cost, in its currency
 *
 * kept on the line in that currency, and in USD for the costing (LKR at the day's USD rate), so
 * the line goes into Entrance & Activities, the costing's Paid activities & services, the pax
 * matrix, the quotation version, the itinerary and the Activities desk like any other line.
 *
 * Half Board: the hotel gives breakfast and one more meal — dinner unless the night says lunch
 * (the default is a setting). Full Board and All Inclusive give lunch and dinner; Room Only and
 * Bed & Breakfast neither. A meal the hotel's plan gives that night is not arranged outside
 * without a person saying so, with a reason; and the people having lunch (dinner) on a day can
 * never be more than the party that day, so the same people are not given the meal twice.
 *
 * Inactive meals, meals out of their dates and meals whose supplier is marked unavailable in the
 * Supplier master are never offered for a booking; a line already linked to one is held, never
 * re-priced from a guess (another record of the same restaurant valid that day is only offered).
 */
final class MealMaster
{
    public const URL = 'master_files/meal_master';

    public const TYPES      = ['lunch' => 'Lunch', 'dinner' => 'Dinner'];
    /** The quotation rates are in USD only (Shantha, 3 Oct); the rupee amount the restaurant charges is its own pair of rates (adult_lkr / child_lkr). */
    public const CURRENCIES = ['USD'];

    /** The Supplier master's categories, named as its own screen names them (Master Files → Suppliers). */
    public const SUPPLIER_CATS = [1 => 'Hotel', 3 => 'Utility Bills', 4 => 'Restaurant & Other Suppliers'];

    /** The settings and what they are when nobody has set them (seeded by the installer into tf_meta). */
    public const SETTINGS = ['meal_hb_default' => 'dinner', 'meal_lunch_time' => '12:30', 'meal_dinner_time' => '19:30', 'meal_supplier_cats' => '4,1', 'meal_auto_lunch' => '1'];

    /** A USD figure above this for one meal is almost certainly rupees typed into a USD record. */
    public const USD_SANITY = 500.0;

    /** The legs of a day the party is driven on (a garage run or a vehicle positioning is not the party travelling). */
    private const PARTY_LEGS = ['transfer', 'arrival', 'departure', 'local'];

    /** Lines the agent has said are not ours to arrange: they are not an arrangement we make. */
    private const NOT_OURS = ['own_arrangement', 'not_arranged'];

    private $db;
    private string $user;
    private ?int $userId;
    private ?string $actor;

    private static array $installed = [];
    private ?array $settings = null;
    private array $supplierCache = [];
    private array $usableCache = [];
    private ?array $inUse = null;

    public function __construct($db, string $user = '', ?int $userId = null, ?string $actor = null)
    {
        $this->db     = $db;
        $this->user   = $user !== '' ? $user : 'system';
        $this->userId = $userId;
        $this->actor  = $actor;
    }

    // =====================================================================
    //  Installed or not, and small helpers
    // =====================================================================

    /** True once Install now has made the master's tables and the line's columns (schema 40); until then everything reads as before. */
    public static function installed($db): bool
    {
        $k = is_object($db) ? spl_object_id($db) : 0;
        if (!array_key_exists($k, self::$installed)) {
            try {
                self::$installed[$k] = (bool) $db->query("SHOW TABLES LIKE 'tf_meal'")->getResultArray()
                    && (bool) $db->query("SHOW TABLES LIKE 'tf_meal_route'")->getResultArray()
                    && (bool) $db->query("SHOW COLUMNS FROM `tf_intake_service` LIKE 'meal_ok_note'")->getResultArray()
                    && (bool) $db->query("SHOW COLUMNS FROM `tf_intake_day` LIKE 'hb_meal'")->getResultArray()
                    && (bool) $db->query("SHOW COLUMNS FROM `tf_intake_service` LIKE 'meal_auto'")->getResultArray()
                    && (bool) $db->query("SHOW COLUMNS FROM `tf_intake` LIKE 'meal_skip'")->getResultArray()
                    && (bool) $db->query("SHOW COLUMNS FROM `tf_intake_service` LIKE 'meal_phone'")->getResultArray()
                    && (bool) $db->query("SHOW COLUMNS FROM `tf_meal` LIKE 'child_lkr'")->getResultArray();
            } catch (\Throwable $e) {
                self::$installed[$k] = false;
            }
        }
        return self::$installed[$k];
    }

    /** Read the meals and the suppliers afresh (a supplier switched off since, a meal changed): the plan and a save always do. */
    public function refresh(): void
    {
        $this->supplierCache = [];
        $this->usableCache   = [];
        $this->inUse         = null;
        $this->settings      = null;
    }

    /** Forget whether the tables are there (the installer and the tests ask again after creating them). */
    public static function forget(): void
    {
        self::$installed = [];
    }

    private function need(): void
    {
        if (!self::installed($this->db)) {
            throw new \RuntimeException('The Meal Master is not installed on this site yet. Open Tour Inquiry as the administrator and press Install now.');
        }
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function who(): string
    {
        return $this->actor !== null && trim($this->actor) !== '' ? trim($this->actor) : $this->user;
    }

    private function log(int $mealId, string $summary, array $changes = []): void
    {
        Activity::master($this->db, $this->user, 'meal_master', $mealId, $summary, $changes, $this->userId, $this->actor);
    }

    private static function cut($v, int $n): ?string
    {
        $t = trim((string) ($v ?? ''));
        return $t === '' ? null : mb_substr($t, 0, $n);
    }

    /** A typed amount as a number, or null when blank; refuses what is not a number. */
    private static function amount($v, string $what): ?float
    {
        if ($v === null) {
            return null;
        }
        $t = str_replace([',', ' '], '', trim((string) $v));
        if ($t === '') {
            return null;
        }
        if (!is_numeric($t)) {
            throw new \InvalidArgumentException($what . ' must be a number.');
        }
        return round((float) $t, 2);
    }

    private static function date($v): ?string
    {
        $t = trim((string) ($v ?? ''));
        if ($t === '') {
            return null;
        }
        $d = \DateTime::createFromFormat('!Y-m-d', $t);
        if (!$d || $d->format('Y-m-d') !== $t) {
            throw new \InvalidArgumentException('"' . $t . '" is not a date (YYYY-MM-DD).');
        }
        return $t;
    }

    private static function intOrNull($v): ?int
    {
        return $v === null || $v === '' ? null : (int) $v;
    }

    public static function money(?float $v): string
    {
        return $v === null ? '—' : number_format($v, 2);
    }

    public static function dateText(?string $d): string
    {
        return $d ? date('j M Y', strtotime($d)) : '—';
    }

    /** A place as words: lower case, letters and figures only, one space between. "Granbell Hotel, Colombo 3" → "granbell hotel colombo 3". */
    public static function words(?string $s): string
    {
        $k = mb_strtolower(trim((string) $s));
        $k = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $k);
        return trim((string) preg_replace('/\s+/', ' ', (string) $k));
    }

    /** Whether a meal's town is named, as whole words, in a place of the itinerary ("Kandy" in "Grand Serendib Hotel, Kandy"). */
    public static function placeMatch(?string $town, ?string $place): bool
    {
        $t = self::words($town);
        $p = self::words($place);
        return $t !== '' && $p !== '' && str_contains(' ' . $p . ' ', ' ' . $t . ' ');
    }

    // =====================================================================
    //  Settings
    // =====================================================================

    public function settings(): array
    {
        if ($this->settings === null) {
            $out = self::SETTINGS;
            try {
                $keys = array_keys(self::SETTINGS);
                $rows = $this->db->query('SELECT k, v FROM tf_meta WHERE k IN (' . implode(',', array_fill(0, count($keys), '?')) . ')', $keys)->getResultArray();
                foreach ($rows as $r) {
                    if (trim((string) $r['v']) !== '') {
                        $out[$r['k']] = trim((string) $r['v']);
                    }
                }
            } catch (\Throwable $e) {
                // no settings table: the defaults stand
            }
            if (!isset(self::TYPES[$out['meal_hb_default']])) {
                $out['meal_hb_default'] = self::SETTINGS['meal_hb_default'];
            }
            $this->settings = $out;
        }
        return $this->settings;
    }

    /** The Supplier master categories a meal supplier may come from. */
    public function supplierCats(): array
    {
        $cats = [];
        foreach (preg_split('/[,\s]+/', (string) $this->settings()['meal_supplier_cats']) ?: [] as $c) {
            if (ctype_digit($c) && (int) $c > 0) {
                $cats[(int) $c] = true;
            }
        }
        return array_keys($cats) ?: [4, 1];
    }

    public function hbDefault(): string
    {
        return (string) $this->settings()['meal_hb_default'];
    }

    public function defaultTime(string $type): string
    {
        return (string) ($this->settings()[$type === 'lunch' ? 'meal_lunch_time' : 'meal_dinner_time'] ?? '');
    }

    private static function time($v): ?string
    {
        $t = trim((string) ($v ?? ''));
        if ($t === '') {
            return null;
        }
        if (!preg_match('/^([01]?\d|2[0-3])[:.]([0-5]\d)$/', $t, $m)) {
            throw new \InvalidArgumentException('"' . $t . '" is not a time (HH:MM, 24-hour).');
        }
        return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2];
    }

    /** Save the settings (the Meal Master screen). */
    public function saveSettings(array $s): array
    {
        $this->need();
        $before = $this->settings();
        $new    = $before;
        if (array_key_exists('meal_hb_default', $s)) {
            $v = strtolower(trim((string) $s['meal_hb_default']));
            if (!isset(self::TYPES[$v])) {
                throw new \InvalidArgumentException('The meal an HB hotel gives besides breakfast is lunch or dinner.');
            }
            $new['meal_hb_default'] = $v;
        }
        foreach (['meal_lunch_time' => 'lunch', 'meal_dinner_time' => 'dinner'] as $k => $w) {
            if (array_key_exists($k, $s)) {
                $t = self::time($s[$k]);
                if ($t === null) {
                    throw new \InvalidArgumentException('Give the usual ' . $w . ' time (HH:MM).');
                }
                $new[$k] = $t;
            }
        }
        if (array_key_exists('meal_auto_lunch', $s)) {
            $new['meal_auto_lunch'] = !empty($s['meal_auto_lunch']) ? '1' : '0';
        }
        if (array_key_exists('meal_supplier_cats', $s)) {
            $cats = [];
            foreach ((array) (is_array($s['meal_supplier_cats']) ? $s['meal_supplier_cats'] : preg_split('/[,\s]+/', (string) $s['meal_supplier_cats'])) as $c) {
                $c = (int) $c;
                if (!isset(self::SUPPLIER_CATS[$c])) {
                    throw new \InvalidArgumentException('Unknown Supplier master category ' . $c . '.');
                }
                $cats[$c] = true;
            }
            if ($cats === []) {
                throw new \InvalidArgumentException('Choose at least one Supplier master category a meal supplier may come from.');
            }
            $new['meal_supplier_cats'] = implode(',', array_keys($cats));
        }
        $changes = [];
        foreach ($new as $k => $v) {
            if ((string) $v !== (string) $before[$k]) {
                $this->db->query('INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, (string) $v]);
                $changes[] = ['what' => $k, 'from' => $before[$k], 'to' => $v];
            }
        }
        if ($changes) {
            $this->log(0, 'Meal Master settings changed', $changes);
        }
        $this->settings = null;
        return $this->settings();
    }

    // =====================================================================
    //  Suppliers — read from the Supplier master, never written
    // =====================================================================

    /** The suppliers a meal may be bought from (the allowed categories), available or not. */
    public function suppliers(): array
    {
        $cats = $this->supplierCats();
        try {
            $rows = $this->db->query('SELECT ID, Sup_Acc_No, Name, Address, TP, Mobile, email, sup_cat, is_avalible FROM supplier WHERE sup_cat IN (' . implode(',', array_map('intval', $cats)) . ') ORDER BY Name')->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        return array_map([self::class, 'supplierRow'], $rows);
    }

    private static function supplierRow(array $r): array
    {
        $phone = trim(implode(' / ', array_filter([trim((string) ($r['TP'] ?? '')), trim((string) ($r['Mobile'] ?? ''))], static fn ($v) => $v !== '' && $v !== '0')));
        return [
            'id' => (int) $r['ID'], 'acc' => trim((string) ($r['Sup_Acc_No'] ?? '')) ?: null, 'name' => trim((string) $r['Name']),
            'address' => trim((string) ($r['Address'] ?? '')) !== '0' ? (trim((string) ($r['Address'] ?? '')) ?: null) : null,
            'phone' => $phone !== '' ? $phone : null, 'email' => trim((string) ($r['email'] ?? '')) ?: null,
            'cat' => (int) ($r['sup_cat'] ?? 0), 'cat_name' => self::SUPPLIER_CATS[(int) ($r['sup_cat'] ?? 0)] ?? ('category ' . (int) ($r['sup_cat'] ?? 0)),
            'available' => (int) ($r['is_avalible'] ?? 1) === 1,
        ];
    }

    /** One supplier by its id, or null when the Supplier master no longer has it. */
    public function supplier(?int $id): ?array
    {
        if ($id === null || $id <= 0) {
            return null;
        }
        if (!array_key_exists($id, $this->supplierCache)) {
            try {
                $r = $this->db->query('SELECT ID, Sup_Acc_No, Name, Address, TP, Mobile, email, sup_cat, is_avalible FROM supplier WHERE ID = ?', [$id])->getRowArray();
                $this->supplierCache[$id] = $r ? self::supplierRow($r) : null;
            } catch (\Throwable $e) {
                $this->supplierCache[$id] = null;
            }
        }
        return $this->supplierCache[$id];
    }

    // =====================================================================
    //  The meals
    // =====================================================================

    /**
     * Every meal with its route legs, its supplier as the Supplier master has it now, how it stands
     * on a date, and how many inquiry lines are linked to it. Filters: type, location, supplier_id,
     * on (a date: valid on it), status (in_use / off), q (any words).
     */
    public function meals(array $f = []): array
    {
        $this->need();
        $where = [];
        $bind  = [];
        if (!empty($f['type']) && isset(self::TYPES[$f['type']])) {
            $where[] = 'meal_type = ?';
            $bind[]  = $f['type'];
        }
        if (!empty($f['supplier_id'])) {
            $where[] = 'supplier_id = ?';
            $bind[]  = (int) $f['supplier_id'];
        }
        if (($f['status'] ?? '') === 'in_use') {
            $where[] = 'active = 1';
        } elseif (($f['status'] ?? '') === 'off') {
            $where[] = 'active = 0';
        }
        $rows = $this->db->query('SELECT * FROM tf_meal' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY meal_type, location, restaurant, valid_from, id', $bind)->getResultArray();
        $routes = [];
        foreach ($this->db->query('SELECT * FROM tf_meal_route ORDER BY meal_id, sort_order, id')->getResultArray() as $r) {
            $routes[(int) $r['meal_id']][] = ['from' => $r['from_place'], 'to' => $r['to_place']];
        }
        $used = [];
        try {
            foreach ($this->db->query('SELECT s.meal_id, COUNT(*) n, SUM(CASE WHEN i.status IN (\'confirmed\') THEN 1 ELSE 0 END) c FROM tf_intake_service s JOIN tf_intake i ON i.id = s.intake_id WHERE s.meal_id IS NOT NULL GROUP BY s.meal_id')->getResultArray() as $u) {
                $used[(int) $u['meal_id']] = ['lines' => (int) $u['n'], 'confirmed' => (int) $u['c']];
            }
        } catch (\Throwable $e) {
            $used = [];
        }
        $on  = !empty($f['on']) ? self::date($f['on']) : date('Y-m-d');
        $out = [];
        foreach ($rows as $r) {
            $m = $this->shape($r, $routes[(int) $r['id']] ?? [], $on);
            $m['used'] = $used[(int) $r['id']] ?? ['lines' => 0, 'confirmed' => 0];
            if (!empty($f['location']) && !self::placeMatch($f['location'], $m['location']) && !self::placeMatch($m['location'], $f['location'])) {
                continue;
            }
            if (!empty($f['valid_on']) && $m['status'] !== 'valid') {
                continue;
            }
            if (!empty($f['q'])) {
                $hay = self::words(implode(' ', [$m['code'], $m['restaurant'], $m['location'], $m['supplier']['name'] ?? '', $m['cuisine'], $m['description'], $m['remarks'], $m['route_text']]));
                foreach (explode(' ', self::words($f['q'])) as $w) {
                    if ($w !== '' && !str_contains($hay, $w)) {
                        continue 2;
                    }
                }
            }
            $out[] = $m;
        }
        return $out;
    }

    /** One meal, shaped as the screens read it; null when there is none. */
    public function meal(int $id, ?string $on = null): ?array
    {
        $this->need();
        $r = $this->db->query('SELECT * FROM tf_meal WHERE id = ?', [$id])->getRowArray();
        if (!$r) {
            return null;
        }
        $routes = array_map(static fn ($x) => ['from' => $x['from_place'], 'to' => $x['to_place']], $this->db->query('SELECT * FROM tf_meal_route WHERE meal_id = ? ORDER BY sort_order, id', [$id])->getResultArray());
        $m = $this->shape($r, $routes, $on ?? date('Y-m-d'));
        try {
            $u = $this->db->query('SELECT COUNT(*) n, SUM(CASE WHEN i.status = \'confirmed\' THEN 1 ELSE 0 END) c FROM tf_intake_service s JOIN tf_intake i ON i.id = s.intake_id WHERE s.meal_id = ?', [$id])->getRowArray();
            $m['used'] = ['lines' => (int) ($u['n'] ?? 0), 'confirmed' => (int) ($u['c'] ?? 0)];
        } catch (\Throwable $e) {
            $m['used'] = ['lines' => 0, 'confirmed' => 0];
        }
        return $m;
    }

    /** A meal row as the screens and the pricing read it: the figures as numbers, the supplier, the legs, and how it stands on a date. */
    private function shape(array $r, array $routes, ?string $on): array
    {
        $sup = $this->supplier(self::intOrNull($r['supplier_id'] ?? null));
        $m = [
            'id' => (int) $r['id'], 'code' => (string) $r['code'], 'meal_type' => (string) $r['meal_type'], 'type_name' => self::TYPES[$r['meal_type']] ?? $r['meal_type'],
            'supplier_id' => self::intOrNull($r['supplier_id'] ?? null), 'supplier_acc' => $r['supplier_acc'] ?? null, 'supplier' => $sup,
            'restaurant' => (string) $r['restaurant'], 'location' => (string) $r['location'], 'address' => $r['address'] ?? null,
            'contact_name' => $r['contact_name'] ?? null, 'contact_phone' => $r['contact_phone'] ?? null, 'contact_email' => $r['contact_email'] ?? null,
            'cuisine' => $r['cuisine'] ?? null, 'description' => $r['description'] ?? null,
            'adult_rate' => (float) $r['adult_rate'], 'child_rate' => $r['child_rate'] !== null ? (float) $r['child_rate'] : null, 'currency' => (string) $r['currency'],
            'adult_lkr' => isset($r['adult_lkr']) && $r['adult_lkr'] !== null ? (float) $r['adult_lkr'] : null, 'child_lkr' => isset($r['child_lkr']) && $r['child_lkr'] !== null ? (float) $r['child_lkr'] : null,
            'tax_pct' => (float) $r['tax_pct'], 'tax_included' => (int) $r['tax_included'] === 1, 'tax_note' => $r['tax_note'] ?? null,
            'valid_from' => $r['valid_from'], 'valid_to' => $r['valid_to'] ?? null, 'active' => (int) $r['active'] === 1, 'remarks' => $r['remarks'] ?? null,
            'routes' => $routes, 'route_text' => implode('; ', array_map(static fn ($x) => $x['from'] . ' → ' . $x['to'], $routes)),
            'created_at' => $r['created_at'] ?? null, 'created_by' => $r['created_by'] ?? null, 'updated_at' => $r['updated_at'] ?? null, 'updated_by' => $r['updated_by'] ?? null,
        ];
        $m['needs_supplier'] = $m['supplier_id'] === null;
        $m['needs_lkr']      = $m['adult_lkr'] === null;   // no rupee rate yet: no booking voucher until it is entered
        $m['lkr_text']       = self::lkrText($m);
        $m['title']       = self::lineTitle($m);
        $m['tax_text']    = self::taxText($m);
        $m['rate_text']   = self::rateText($m);
        $st = self::standing($m, $on);
        $m['status']      = $st['status'];
        $m['status_text'] = $st['text'];
        $m['usable']      = $st['status'] === 'valid';
        return $m;
    }

    /** What the meal line on a booking is called: "Lunch at Sigiriya Village Restaurant, Sigiriya" (the town only when the name does not say it). */
    public static function lineTitle(array $m): string
    {
        $t = (self::TYPES[$m['meal_type']] ?? 'Meal') . ' at ' . trim((string) $m['restaurant']);
        if (trim((string) $m['location']) !== '' && !self::placeMatch($m['location'], $m['restaurant'])) {
            $t .= ', ' . trim((string) $m['location']);
        }
        return mb_substr($t, 0, 255);
    }

    public static function taxText(array $m): string
    {
        $pct = (float) ($m['tax_pct'] ?? 0);
        $pctT = rtrim(rtrim(number_format($pct, 2), '0'), '.');
        if (!empty($m['tax_included'])) {
            return 'taxes included in the rates' . ($pct > 0 ? ' (' . $pctT . '%)' : '') . (!empty($m['tax_note']) ? ' — ' . $m['tax_note'] : '');
        }
        return $pct > 0 ? $pctT . '% added on top' . (!empty($m['tax_note']) ? ' — ' . $m['tax_note'] : '') : 'no taxes';
    }

    public static function rateText(array $m): string
    {
        $c = (string) $m['currency'];
        $child = $m['child_rate'] === null ? 'children as adults' : ((float) $m['child_rate'] == 0.0 ? 'children free' : 'child ' . $c . ' ' . number_format((float) $m['child_rate'], 2));
        return 'adult ' . $c . ' ' . number_format((float) $m['adult_rate'], 2) . ' · ' . $child;
    }

    /** The actual rates in LKR as the screens say them, or that they are still to be entered. */
    public static function lkrText(array $m): string
    {
        if (($m['adult_lkr'] ?? null) === null) {
            return 'LKR rate to enter';
        }
        $child = $m['child_lkr'] === null ? 'children as adults' : ((float) $m['child_lkr'] == 0.0 ? 'children free' : 'child LKR ' . number_format((float) $m['child_lkr'], 2));
        return 'adult LKR ' . number_format((float) $m['adult_lkr'], 2) . ' · ' . $child;
    }

    /**
     * How a meal stands on a date: valid (it may be booked), off (switched off), supplier (the Supplier
     * master marks the supplier unavailable, or no longer has it), future / ended (outside its dates).
     *
     * @return array{status:string, text:string}
     */
    public static function standing(array $m, ?string $on): array
    {
        if (empty($m['active'])) {
            return ['status' => 'off', 'text' => 'Switched off in the Meal Master'];
        }
        $sup = $m['supplier'] ?? null;
        if ($sup === null && !empty($m['supplier_id'])) {
            return ['status' => 'supplier', 'text' => 'Its supplier is no longer in the Supplier master'];
        }
        // not linked to a supplier account yet (added from an inquiry): it may be booked; it is paid once accounts link it
        if ($sup !== null && empty($sup['available'])) {
            return ['status' => 'supplier', 'text' => $sup['name'] . ' is marked unavailable in the Supplier master'];
        }
        if ($on === null) {
            return ['status' => 'valid', 'text' => 'In use'];
        }
        if ($on < (string) $m['valid_from']) {
            return ['status' => 'future', 'text' => 'Valid from ' . self::dateText($m['valid_from'])];
        }
        if (!empty($m['valid_to']) && $on > (string) $m['valid_to']) {
            return ['status' => 'ended', 'text' => 'Ended ' . self::dateText($m['valid_to'])];
        }
        return ['status' => 'valid', 'text' => 'Valid ' . self::dateText($m['valid_from']) . ' – ' . ($m['valid_to'] ? self::dateText($m['valid_to']) : 'open')];
    }

    /**
     * Save a meal (add, or change one). Every rule is checked here, for every screen:
     *   Meal Type is Lunch or Dinner; the code is unique; the supplier is a record of the Supplier master in an allowed
     *   category (and available, for a new meal or a new supplier); restaurant, location, adult rate, currency and
     *   Valid From are required; no rate is negative; Valid To is not before Valid From; taxes 0–100 %; the route legs
     *   have both ends and go somewhere; and no second meal in use for the same restaurant, type, location and menu
     *   overlaps it in time.
     */
    public function save(array $in): array
    {
        $this->need();
        $id  = !empty($in['id']) ? (int) $in['id'] : null;
        $old = $id ? $this->db->query('SELECT * FROM tf_meal WHERE id = ?', [$id])->getRowArray() : null;
        if ($id && !$old) {
            throw new \InvalidArgumentException('That meal is not in the Meal Master.');
        }

        $code = strtoupper((string) self::cut($in['code'] ?? null, 40));
        if ($code === '' && $old) {
            $code = (string) $old['code'];   // a meal keeps its code
        }
        if ($code === '') {
            $code = $this->nextCode();   // blank on a new meal: the next ML-0001, ML-0002 …
        }
        if (!preg_match('/^[A-Z0-9][A-Z0-9\-\/_.]*$/', $code)) {
            throw new \InvalidArgumentException('The Meal Code may hold letters, figures and - / _ . only (for example ML-KDY-01).');
        }
        if ($this->db->query('SELECT id FROM tf_meal WHERE code = ?' . ($id ? ' AND id <> ?' : ''), $id ? [$code, $id] : [$code])->getRowArray()) {
            throw new \InvalidArgumentException('The Meal Code ' . $code . ' is already used by another meal.');
        }
        $type = strtolower(trim((string) ($in['meal_type'] ?? '')));
        if (!isset(self::TYPES[$type])) {
            throw new \InvalidArgumentException('Meal Type can only be Lunch or Dinner.');
        }

        $supId = self::intOrNull($in['supplier_id'] ?? null);
        $sup   = $this->supplier($supId);
        if ($supId !== null && $sup === null) {
            throw new \InvalidArgumentException('That supplier is not in the Supplier master.');
        }
        // the supplier account may be linked later (a restaurant added from an inquiry): the meal can be booked, and it is
        // paid only once accounts link it here to its record of the Supplier master — never a code made up here
        if ($sup === null) {
            if (self::cut($in['restaurant'] ?? null, 190) === null) {
                throw new \InvalidArgumentException('Give the restaurant\'s name, or pick it from the Supplier master.');
            }
        } elseif (!in_array($sup['cat'], $this->supplierCats(), true)) {
            throw new \InvalidArgumentException($sup['name'] . ' is a ' . $sup['cat_name'] . ' supplier; a meal supplier comes from ' . implode(' or ', array_map(fn ($c) => self::SUPPLIER_CATS[$c] ?? $c, $this->supplierCats())) . '.');
        }
        $supChanged = $sup !== null && (!$old || (int) ($old['supplier_id'] ?? 0) !== $sup['id']);
        if ($sup !== null && !$sup['available'] && $supChanged) {
            throw new \InvalidArgumentException($sup['name'] . ' is marked unavailable in the Supplier master, so it cannot be chosen for a meal.');
        }

        $restaurant = self::cut($in['restaurant'] ?? null, 190) ?? $sup['name'];
        $location   = ProgrammeMasters::destination((string) self::cut($in['location'] ?? null, 120));
        if ($location === '') {
            throw new \InvalidArgumentException('The Location (town) is required — it is how the meal is found for a day of the itinerary.');
        }
        $email = self::cut($in['contact_email'] ?? null, 190);
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('"' . $email . '" is not an email address.');
        }

        $adult = self::amount($in['adult_rate'] ?? null, 'The adult rate');
        $child = self::amount($in['child_rate'] ?? null, 'The child rate');
        if ($adult === null) {
            throw new \InvalidArgumentException('The adult rate is required.');
        }
        if ($adult < 0 || ($child !== null && $child < 0)) {
            throw new \InvalidArgumentException('Rates cannot be negative.');
        }
        if ($adult == 0.0) {
            throw new \InvalidArgumentException('The adult rate cannot be 0.00 — a meal the company pays nothing for is not a meal to cost.');
        }
        $cur = strtoupper(trim((string) ($in['currency'] ?? ''))) ?: 'USD';
        if (!in_array($cur, self::CURRENCIES, true)) {
            throw new \InvalidArgumentException('The quotation rate is in USD only. The rupee amount the restaurant charges goes in the LKR rates (it is what the booking voucher states).');
        }
        if (max($adult, (float) $child) > self::USD_SANITY && empty($in['confirm_high'])) {
            throw new \InvalidArgumentException('USD ' . number_format(max($adult, (float) $child), 2) . ' for one meal looks like rupees. The rupee amount goes in the LKR rates; or tick "The amount is right" and save again.');
        }
        // the actual amount in LKR (the booking voucher's figures, no conversion): kept as it was when not given on an edit
        $hasLkr = array_key_exists('adult_lkr', $in) || array_key_exists('child_lkr', $in);
        $adultL = $hasLkr ? self::amount($in['adult_lkr'] ?? null, 'The adult LKR rate') : ($old && $old['adult_lkr'] !== null ? (float) $old['adult_lkr'] : null);
        $childL = $hasLkr ? self::amount($in['child_lkr'] ?? null, 'The child LKR rate') : ($old && $old['child_lkr'] !== null ? (float) $old['child_lkr'] : null);
        if (($adultL !== null && $adultL < 0) || ($childL !== null && $childL < 0)) {
            throw new \InvalidArgumentException('Rates cannot be negative.');
        }
        if ($adultL === null && $childL !== null) {
            throw new \InvalidArgumentException('Give the adult LKR rate as well (the child LKR rate alone is not a rate).');
        }
        if ($adultL !== null && $adultL == 0.0) {
            throw new \InvalidArgumentException('The adult LKR rate cannot be 0.00 — leave it blank until the restaurant\'s rupee rate is known.');
        }
        $tax = self::amount($in['tax_pct'] ?? 0, 'The tax percentage') ?? 0.0;
        if ($tax < 0 || $tax > 100) {
            throw new \InvalidArgumentException('Taxes are a percentage from 0 to 100.');
        }
        $taxIncl = !empty($in['tax_included']) ? 1 : 0;

        $from = self::date($in['valid_from'] ?? null);
        $to   = self::date($in['valid_to'] ?? null);
        if ($from === null) {
            throw new \InvalidArgumentException('Valid From is required.');
        }
        if ($to !== null && $to < $from) {
            throw new \InvalidArgumentException('Valid To (' . self::dateText($to) . ') cannot be before Valid From (' . self::dateText($from) . ').');
        }
        $active = array_key_exists('active', $in) ? (!empty($in['active']) ? 1 : 0) : ($old ? (int) $old['active'] : 1);

        // the route legs, both ends named, each once, never from a place to itself
        $legs = [];
        foreach ((array) ($in['routes'] ?? []) as $leg) {
            $f = ProgrammeMasters::destination((string) self::cut($leg['from'] ?? null, 120));
            $t = ProgrammeMasters::destination((string) self::cut($leg['to'] ?? null, 120));
            if ($f === '' && $t === '') {
                continue;
            }
            if ($f === '' || $t === '') {
                throw new \InvalidArgumentException('A route leg needs both ends (from → to).');
            }
            if (self::words($f) === self::words($t)) {
                throw new \InvalidArgumentException('A route leg goes from one place to another: ' . $f . ' → ' . $t . ' is not a leg.');
            }
            $legs[self::words($f) . '>' . self::words($t)] = ['from' => $f, 'to' => $t];
        }
        if (count($legs) > 30) {
            throw new \InvalidArgumentException('Thirty route legs at most for one meal.');
        }

        // one meal in use per restaurant, type, location and menu at a time — overlapping periods would price one line two ways
        if ($active) {
            $desc = self::cut($in['description'] ?? null, 1000);
            foreach ($this->db->query('SELECT * FROM tf_meal WHERE active = 1 AND meal_type = ?' . ($id ? ' AND id <> ?' : ''), $id ? [$type, $id] : [$type])->getResultArray() as $o) {
                if (self::intOrNull($o['supplier_id']) !== ($sup['id'] ?? null) || self::words($o['location']) !== self::words($location) || self::words($o['restaurant']) !== self::words($restaurant) || self::words($o['description'] ?? '') !== self::words($desc ?? '')) {
                    continue;
                }
                $oTo = $o['valid_to'] ?? null;
                if (($to === null || (string) $o['valid_from'] <= $to) && ($oTo === null || $from <= (string) $oTo)) {
                    throw new \InvalidArgumentException('The ' . $type . ' ' . $o['code'] . ' (' . $o['restaurant'] . ', ' . $o['location'] . ') is in use for ' . self::dateText($o['valid_from']) . ' – ' . ($oTo ? self::dateText($oTo) : 'open')
                        . ', which overlaps these dates. End that one the day before (Edit its Valid To), switch it off, or describe the meal differently if it is another menu.');
                }
            }
        }

        $row = [
            'code' => $code, 'meal_type' => $type, 'supplier_id' => $sup['id'] ?? null, 'supplier_acc' => $sup['acc'] ?? null, 'restaurant' => $restaurant, 'location' => $location,
            'address' => self::cut($in['address'] ?? null, 300), 'contact_name' => self::cut($in['contact_name'] ?? null, 120), 'contact_phone' => self::cut($in['contact_phone'] ?? null, 60),
            'contact_email' => $email, 'cuisine' => self::cut($in['cuisine'] ?? null, 80), 'description' => self::cut($in['description'] ?? null, 1000),
            'adult_rate' => $adult, 'child_rate' => $child, 'currency' => $cur, 'adult_lkr' => $adultL, 'child_lkr' => $childL, 'tax_pct' => $tax, 'tax_included' => $taxIncl, 'tax_note' => self::cut($in['tax_note'] ?? null, 190),
            'valid_from' => $from, 'valid_to' => $to, 'active' => $active, 'remarks' => self::cut($in['remarks'] ?? null, 1000),
        ];
        $oldLegs = $id ? array_map(static fn ($x) => $x['from_place'] . ' → ' . $x['to_place'], $this->db->query('SELECT * FROM tf_meal_route WHERE meal_id = ? ORDER BY sort_order, id', [$id])->getResultArray()) : [];
        if ($id) {
            $this->db->query('UPDATE tf_meal SET ' . implode(', ', array_map(static fn ($k) => "`{$k}` = ?", array_keys($row))) . ', updated_at = ?, updated_by = ? WHERE id = ?', array_merge(array_values($row), [$this->now(), $this->user, $id]));
        } else {
            $this->db->query('INSERT INTO tf_meal (' . implode(', ', array_map(static fn ($k) => "`{$k}`", array_keys($row))) . ', created_at, created_by, updated_at, updated_by) VALUES (' . implode(',', array_fill(0, count($row) + 4, '?')) . ')',
                array_merge(array_values($row), [$this->now(), $this->user, $this->now(), $this->user]));
            $id = (int) ($this->db->query('SELECT LAST_INSERT_ID() AS id')->getRowArray()['id'] ?? 0);
        }
        $this->db->query('DELETE FROM tf_meal_route WHERE meal_id = ?', [$id]);
        $i = 0;
        foreach ($legs as $leg) {
            $this->db->query('INSERT INTO tf_meal_route (meal_id, from_place, to_place, sort_order) VALUES (?,?,?,?)', [$id, $leg['from'], $leg['to'], $i++]);
        }

        $changes = [];
        foreach ($row as $k => $v) {
            $was = $old[$k] ?? null;
            if ($old === null || self::norm($was) !== self::norm($v)) {
                $changes[] = ['what' => $k, 'from' => $old === null ? null : $was, 'to' => $v];
            }
        }
        $newLegs = array_map(static fn ($x) => $x['from'] . ' → ' . $x['to'], array_values($legs));
        if ($oldLegs !== $newLegs) {
            $changes[] = ['what' => 'route legs', 'from' => implode('; ', $oldLegs) ?: null, 'to' => implode('; ', $newLegs) ?: null];
        }
        $this->log($id, ($old ? 'Meal changed: ' : 'Meal added: ') . $code . ' — ' . $restaurant . ', ' . $location, $changes);
        $this->refresh();
        return $this->meal($id);
    }

    /** The next free code for a meal given none: ML-0001, ML-0002 … (a meal's own code, never a supplier's). */
    private function nextCode(): string
    {
        $n = (int) ($this->db->query('SELECT COALESCE(MAX(id), 0) + 1 AS n FROM tf_meal')->getRowArray()['n'] ?? 1);
        do {
            $code = 'ML-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while ($this->db->query('SELECT id FROM tf_meal WHERE code = ?', [$code])->getRowArray());
        return $code;
    }

    private static function norm($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
        }
        return trim((string) $v);
    }

    /** Switch a meal on or off — kept either way, never deleted. A line already linked to a meal switched off is held until a person picks another. */
    public function setActive(int $id, bool $on): array
    {
        $this->need();
        $r = $this->db->query('SELECT * FROM tf_meal WHERE id = ?', [$id])->getRowArray();
        if (!$r) {
            throw new \InvalidArgumentException('That meal is not in the Meal Master.');
        }
        if ((int) $r['active'] === ($on ? 1 : 0)) {
            return $this->meal($id);
        }
        if ($on) {
            // switched on again: the overlap rule applies as for a save
            $m = $this->meal($id);
            $this->save(['id' => $id, 'active' => 1, 'routes' => $m['routes'], 'confirm_high' => 1] + array_intersect_key($r, array_flip(['code', 'meal_type', 'supplier_id', 'restaurant', 'location', 'address', 'contact_name', 'contact_phone', 'contact_email', 'cuisine', 'description', 'adult_rate', 'child_rate', 'currency', 'tax_pct', 'tax_included', 'tax_note', 'valid_from', 'valid_to', 'remarks'])));
            return $this->meal($id);
        }
        $this->db->query('UPDATE tf_meal SET active = 0, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $id]);
        $this->log($id, 'Meal switched off: ' . $r['code'] . ' — ' . $r['restaurant'] . ', ' . $r['location'], [['what' => 'active', 'from' => 1, 'to' => 0]]);
        $this->refresh();
        return $this->meal($id);
    }

    /**
     * The meals of a type a booking may take on a date: in use, valid that day, the supplier available. Each with its legs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function usable(string $type, ?string $date): array
    {
        if (!self::installed($this->db) || !isset(self::TYPES[$type])) {
            return [];
        }
        $key = $type . '|' . (string) $date;
        if (!isset($this->usableCache[$key])) {
            // the meals in use are read once; each date is a reading of them, not another query
            $this->inUse ??= $this->meals(['status' => 'in_use']);
            $out = [];
            foreach ($this->inUse as $m) {
                if ($m['meal_type'] !== $type) {
                    continue;
                }
                $st = self::standing($m, $date ?? date('Y-m-d'));
                if ($st['status'] === 'valid') {
                    $m['status'] = 'valid';
                    $m['status_text'] = $st['text'];
                    $m['usable'] = true;
                    $out[] = $m;
                }
            }
            $this->usableCache[$key] = $out;
        }
        return $this->usableCache[$key];
    }

    // =====================================================================
    //  The money of one meal line
    // =====================================================================

    /**
     * The cost of a meal for so many adults and children, in the meal's currency:
     * (adults × adult rate + children × child rate) + taxes. A blank child rate is the adult rate.
     *
     * @return array{sub:float, tax:float, total:float, adult_incl:float, child_incl:float, child_rate:float}
     */
    /**
     * What the restaurant is paid, in LKR, from the Meal Master's actual rates (adults × adult + children × child, the
     * taxes as for the quotation rate) — the booking voucher's figure, never converted from USD. Null without an LKR rate.
     */
    public static function costLkr(array $m, int $adults, int $children): ?array
    {
        if (($m['adult_lkr'] ?? null) === null) {
            return null;
        }
        return self::cost(['adult_rate' => $m['adult_lkr'], 'child_rate' => $m['child_lkr'], 'tax_pct' => $m['tax_pct'] ?? 0, 'tax_included' => $m['tax_included'] ?? false], $adults, $children);
    }

    public static function cost(array $m, int $adults, int $children): array
    {
        $ra  = (float) $m['adult_rate'];
        $rc  = $m['child_rate'] === null || $m['child_rate'] === '' ? $ra : (float) $m['child_rate'];
        $pct = (float) ($m['tax_pct'] ?? 0);
        $inc = !empty($m['tax_included']);
        $sub = round($adults * $ra + $children * $rc, 2);
        $tax = $inc ? 0.0 : round($sub * $pct / 100, 2);
        $f   = $inc ? 1.0 : 1 + $pct / 100;
        return ['sub' => $sub, 'tax' => $tax, 'total' => round($sub + $tax, 2), 'adult_incl' => round($ra * $f, 2), 'child_incl' => round($rc * $f, 2), 'child_rate' => $rc];
    }

    /** A meal record as the pricing needs it (with its supplier), or null. */
    public function forPricing(int $mealId): ?array
    {
        if (!self::installed($this->db)) {
            return null;
        }
        $r = $this->db->query('SELECT * FROM tf_meal WHERE id = ?', [$mealId])->getRowArray();
        return $r ? $this->shape($r, [], null) : null;
    }

    /** Another meal of the same restaurant, type and town that may be booked on the date — offered on a held line, never applied. */
    public function successor(array $m, ?string $date): ?array
    {
        foreach ($this->usable((string) $m['meal_type'], $date) as $o) {
            if ($o['id'] !== $m['id'] && $o['supplier_id'] === $m['supplier_id'] && self::words($o['location']) === self::words($m['location'])) {
                return $o;
            }
        }
        return null;
    }

    // =====================================================================
    //  Meals on a booking: the day-by-day plan from the itinerary
    // =====================================================================

    /** The meals the hotel's plan gives on a night: HB the HB meal, FB / AI lunch and dinner, RO / BB neither. */
    public function hotelGives(?string $mealPlan, ?string $hbMeal): array
    {
        $mp = strtoupper(trim((string) $mealPlan));
        if ($mp === 'HB') {
            return [isset(self::TYPES[(string) $hbMeal]) ? (string) $hbMeal : $this->hbDefault()];
        }
        if (in_array($mp, ['FB', 'AI'], true)) {
            return ['lunch', 'dinner'];
        }
        return [];
    }

    /** How many people a line on the day is for: a meal's adults and children; otherwise its quantity, else the party. */
    private static function heads(array $s, ?int $party): int
    {
        if (!empty($s['meal_id']) && ($s['qty_adult'] !== null || $s['qty_child'] !== null)) {
            return (int) ($s['qty_adult'] ?? 0) + (int) ($s['qty_child'] ?? 0);
        }
        if (($s['qty'] ?? null) !== null && $s['qty'] !== '') {
            return (int) round((float) $s['qty']);
        }
        return (int) ($party ?? 0);
    }

    /** The party on a day: the groups there that day when the party splits, else the inquiry's passengers. */
    private static function partyOn(array $it, int $dayNo): ?int
    {
        if (!empty($it['groups'])) {
            $n = 0;
            foreach ($it['groups'] as $g) {
                if (IntakeService::groupOn($g, $dayNo)) {
                    $n += (int) ($g['pax'] ?? 0);
                }
            }
            return $n;
        }
        $p = self::intOrNull($it['pax_total'] ?? null);
        return $p !== null && $p > 0 ? $p : null;
    }

    /** The adults and children a new meal line starts with, and whether the inquiry says who the children are. */
    public static function partySplit(array $it, ?int $party): array
    {
        $a = self::intOrNull($it['pax_adults'] ?? null);
        $c = self::intOrNull($it['pax_children'] ?? null);
        $p = self::intOrNull($it['pax_total'] ?? null);
        if ($a !== null && $c !== null && $p !== null && $a + $c === $p && ($party === null || $party === $p)) {
            return ['adults' => $a, 'children' => $c, 'known' => true, 'note' => null];
        }
        return ['adults' => $party ?? $p ?? 0, 'children' => 0, 'known' => false,
                'note' => 'The inquiry does not say how many of the ' . ($party ?? $p ?? 0) . ' are children — check the adults and children before saving.'];
    }

    /** The town names of a night: its area, the hotel's name and the hotel's location in the Hotel Rates panel. */
    private function nightPlaces(array $d, array $hotelTown): array
    {
        $out = [];
        foreach ([$d['area'] ?? null, $d['hotel_name_raw'] ?? null, $hotelTown[(int) ($d['hotel_id'] ?? 0)] ?? null] as $p) {
            if (trim((string) $p) !== '') {
                $out[] = (string) $p;
            }
        }
        return $out;
    }

    /**
     * The meals of a booking, day by day, from the inquiry as IntakeService::get gives it — nothing kept apart:
     * each night and the departure day with its date, the legs driven that day, the hotel and what its meal plan
     * gives, the party that day, and for lunch and for dinner what is there (the hotel, a meal of the master, an
     * old line, nothing) with the meals of the master that suit the day.
     */
    public function plan(array $it): array
    {
        if (!self::installed($this->db)) {
            return ['available' => false];
        }
        $this->refresh();
        $locked = !empty($it['locked']) || in_array((string) ($it['status'] ?? ''), ['confirmed', 'cancelled'], true) || !empty($it['archived_at']);
        $hotelTown = [];
        $ids = array_values(array_filter(array_map(static fn ($d) => (int) ($d['hotel_id'] ?? 0), $it['days'] ?? [])));
        if ($ids) {
            try {
                foreach ($this->db->query('SELECT h.id, l.name FROM hr2_hotel h LEFT JOIN hr2_location l ON l.id = h.location_id WHERE h.id IN (' . implode(',', array_map('intval', $ids)) . ')')->getResultArray() as $r) {
                    $hotelTown[(int) $r['id']] = $r['name'];
                }
            } catch (\Throwable $e) {
                $hotelTown = [];
            }
        }
        $byDay = [];
        foreach ($it['services'] ?? [] as $s) {
            if (in_array((string) $s['kind'], ['lunch', 'dinner'], true)) {
                $byDay[(int) $s['day_no']][(string) $s['kind']][] = $s;
            }
        }
        $mealsById = [];
        $rows = [];
        $nights = $it['days'] ?? [];
        $prev = null;
        foreach ($nights as $d) {
            $rows[] = $this->dayRow($it, $d, $d['sectors'] ?? [], $this->nightPlaces($d, $hotelTown), false, $byDay, $locked, $mealsById, $prev);
            $prev = $d;
        }
        $dep = $it['departure_day'] ?? null;
        if (is_array($dep) && !empty($dep['day_no'])) {
            $last = $prev ?? [];
            $rows[] = $this->dayRow($it, ['day_no' => (int) $dep['day_no'], 'day_date' => $dep['date'] ?? null, 'area' => $dep['from_area'] ?? null, 'hotel_name_raw' => null, 'meal_plan' => null, 'hb_meal' => null, 'id' => null],
                $dep['legs'] ?? [], $this->nightPlaces($last, $hotelTown), true, $byDay, $locked, $mealsById, $last);
        }
        // a meal line on a day that is neither a night nor the departure day (a night removed since): said, so it is not left costed unseen
        $known = array_flip(array_map(static fn ($r) => $r['day_no'], $rows));
        $stray = [];
        foreach ($byDay as $n => $kinds) {
            if (!isset($known[$n])) {
                foreach ($kinds as $lines) {
                    foreach ($lines as $s) {
                        if (!empty($s['meal_id'])) {
                            $stray[] = ['id' => (int) $s['id'], 'day_no' => (int) $n, 'title' => $s['title']];
                        }
                    }
                }
            }
        }
        $problems = [];
        foreach ($rows as $r) {
            foreach ($r['meals'] as $type => $c) {
                foreach ($c['problems'] as $p) {
                    $problems[] = $p;
                }
            }
        }
        foreach ($stray as $s) {
            $problems[] = ['code' => 'meal_day_' . $s['id'], 'label' => '"' . $s['title'] . '" is on day ' . $s['day_no'] . ', which is not a day of the tour any more — move it or remove it under Meals.'];
        }
        $count = ['hb_nights' => 0, 'arranged' => 0, 'hotel' => 0, 'open' => 0, 'old' => 0, 'held' => 0];
        foreach ($rows as $r) {
            if (!$r['departure'] && strtoupper((string) $r['meal_plan']) === 'HB') {
                $count['hb_nights']++;
            }
            foreach ($r['meals'] as $c) {
                $k = in_array($c['state'], ['arranged', 'hotel', 'open', 'old', 'held'], true) ? $c['state'] : null;
                if ($k !== null) {
                    $count[$k]++;
                }
            }
        }
        $party = self::partyOn($it, 1);
        // a lunch a person took off a day stays off: the automatic lunch place does not put it back
        $skip = self::skipList($it['meal_skip'] ?? null);
        $nowUsd = 0.0;
        foreach ($rows as &$r) {
            foreach ($r['meals'] as $type => &$c) {
                $c['skipped'] = isset($skip[$type][$r['day_no']]);
                foreach ($c['lines'] as $l) {
                    if ($l['meal_id'] !== null && $l['costed'] && $l['amount_usd'] !== null) {
                        $nowUsd += $l['amount_usd'];
                    }
                }
            }
            unset($c);
        }
        unset($r);
        return [
            'available' => true, 'locked' => $locked, 'days' => $rows, 'problems' => $problems, 'count' => $count, 'stray' => $stray,
            'hb_default' => $this->hbDefault(), 'times' => ['lunch' => $this->defaultTime('lunch'), 'dinner' => $this->defaultTime('dinner')],
            'split' => self::partySplit($it, $party), 'auto_lunch' => $this->autoOn(),
            // the meals as the quotation version the agent has carries them, beside what they come to now (in-house figures)
            'issued' => $this->issuedMeals((int) ($it['id'] ?? 0)), 'meals_usd' => round($nowUsd, 2),
        ];
    }

    public function autoOn(): bool
    {
        return (string) ($this->settings()['meal_auto_lunch'] ?? '1') === '1';
    }

    /** The days a person took a meal off: "lunch:3,lunch:9" → ['lunch' => [3 => true, 9 => true]]. */
    public static function skipList(?string $v): array
    {
        $out = ['lunch' => [], 'dinner' => []];
        foreach (preg_split('/[,\s]+/', (string) $v) ?: [] as $p) {
            if (preg_match('/^(lunch|dinner):(\d+)$/', $p, $m)) {
                $out[$m[1]][(int) $m[2]] = true;
            }
        }
        return $out;
    }

    /** Mark (or clear) that a person took the meal off a day, so the automatic lunch place leaves the day alone. */
    public function setSkip(int $intakeId, string $type, int $dayNo, bool $on): void
    {
        if (!self::installed($this->db) || !isset(self::TYPES[$type])) {
            return;
        }
        $r = $this->db->query('SELECT meal_skip FROM tf_intake WHERE id = ?', [$intakeId])->getRowArray();
        $list = self::skipList($r['meal_skip'] ?? null);
        if ($on) {
            $list[$type][$dayNo] = true;
        } else {
            unset($list[$type][$dayNo]);
        }
        $parts = [];
        foreach ($list as $t => $days) {
            ksort($days);
            foreach (array_keys($days) as $n) {
                $parts[] = $t . ':' . $n;
            }
        }
        $this->db->query('UPDATE tf_intake SET meal_skip = ? WHERE id = ?', [$parts ? mb_substr(implode(',', $parts), 0, 400) : null, $intakeId]);
    }

    /** The meal lines of the quotation version issued last, as it carries them (frozen): the version, the lines, the USD total. */
    public function issuedMeals(int $intakeId): ?array
    {
        if ($intakeId <= 0) {
            return null;
        }
        try {
            $q = $this->db->query("SELECT version, cost_json FROM tf_quote WHERE intake_id = ? AND status = 'issued' ORDER BY version DESC LIMIT 1", [$intakeId])->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$q) {
            return null;
        }
        $cost  = json_decode((string) $q['cost_json'], true) ?: [];
        $lines = [];
        $usd   = 0.0;
        foreach ($cost['services'] ?? [] as $sv) {
            if (!empty($sv['meal'])) {
                $lines[] = ['day_no' => (int) $sv['day_no'], 'title' => (string) $sv['title'], 'amount_usd' => $sv['amount_usd'] !== null ? (float) $sv['amount_usd'] : null];
                $usd += (float) ($sv['amount_usd'] ?? 0);
            }
        }
        return ['version' => (int) $q['version'], 'lines' => $lines, 'meals_usd' => round($usd, 2)];
    }

    private function dayRow(array $it, array $d, array $sectors, array $nightPlaces, bool $departure, array $byDay, bool $locked, array &$mealsById, ?array $prevNight): array
    {
        $n    = (int) $d['day_no'];
        $date = $d['day_date'] ?? null;
        $legs = [];
        foreach ($sectors as $s) {
            if (!empty($s['removed_at']) || !in_array((string) ($s['kind'] ?? ''), self::PARTY_LEGS, true)) {
                continue;
            }
            $legs[] = ['from' => (string) ($s['from_place'] ?? ''), 'to' => (string) ($s['to_place'] ?? ''), 'kind' => (string) $s['kind']];
        }
        $mp     = $departure ? null : (strtoupper(trim((string) ($d['meal_plan'] ?? ''))) ?: null);
        $hbSet  = !$departure && isset(self::TYPES[(string) ($d['hb_meal'] ?? '')]) ? (string) $d['hb_meal'] : null;
        $gives  = $departure ? [] : $this->hotelGives($mp, $hbSet);
        $party  = self::partyOn($it, $n);
        $row = [
            'day_no' => $n, 'day_id' => isset($d['id']) ? (int) $d['id'] : null, 'date' => $date, 'departure' => $departure,
            'label' => $departure ? 'Departure day' : 'Night ' . $n, 'area' => $d['area'] ?? null, 'hotel' => $departure ? null : ($d['hotel_name_raw'] ?? null),
            'meal_plan' => $mp, 'hb_meal' => $mp === 'HB' ? ($hbSet ?? $this->hbDefault()) : null, 'hb_meal_set' => $hbSet !== null,
            'hb_switchable' => !$locked && !$departure && $mp === 'HB' && !empty($d['id']),
            'hotel_gives' => $gives, 'legs' => $legs, 'route' => implode(' · ', array_map(static fn ($l) => $l['from'] . ' → ' . $l['to'], $legs)), 'party' => $party,
            'meals' => [],
        ];
        // where the party sleeps (the departure day: where the day starts), the towns driven through, the last place reached
        $towns = [];
        foreach ($legs as $l) {
            $towns[] = $l['from'];
            $towns[] = $l['to'];
        }
        $lastTo = $legs ? $legs[count($legs) - 1]['to'] : null;
        $day = 'Day ' . $n . ($date ? ' (' . self::dateText($date) . ')' : '');
        foreach (self::TYPES as $type => $word) {
            $lines = $byDay[$n][$type] ?? [];
            $people = 0;
            $mealLine = false;
            $held = false;
            $cells = [];
            foreach ($lines as $s) {
                $c = ServiceLookup::classify($s);
                $ours = !in_array((string) ($s['pay_mode'] ?? ''), self::NOT_OURS, true);
                if ($ours) {
                    $people += self::heads($s, $party);
                }
                if (!empty($s['meal_id'])) {
                    $mealLine = true;
                    if ($c['blocks']) {
                        $held = true;
                    }
                }
                $mid = !empty($s['meal_id']) ? (int) $s['meal_id'] : null;
                if ($mid !== null && !array_key_exists($mid, $mealsById)) {
                    $mealsById[$mid] = $this->forPricing($mid);
                }
                $m = $mid !== null ? $mealsById[$mid] : null;
                $cells[] = [
                    'id' => (int) $s['id'], 'title' => $s['title'], 'meal_id' => $mid, 'code' => $m['code'] ?? null, 'restaurant' => $m['restaurant'] ?? null, 'location' => $m['location'] ?? null,
                    'contact' => $m ? trim(implode(' · ', array_filter([$m['contact_name'], $m['contact_phone']]))) : null,
                    'qty_adult' => self::intOrNull($s['qty_adult'] ?? null), 'qty_child' => self::intOrNull($s['qty_child'] ?? null), 'qty' => $s['qty'] !== null ? (float) $s['qty'] : null,
                    'meal_time' => $s['meal_time'] ?? null, 'currency' => $s['rate_currency'] ?? null, 'amount_cur' => isset($s['amount_cur']) && $s['amount_cur'] !== null ? (float) $s['amount_cur'] : null,
                    'tax_cur' => isset($s['tax_cur']) && $s['tax_cur'] !== null ? (float) $s['tax_cur'] : null, 'amount_usd' => $s['amount_usd'] !== null ? (float) $s['amount_usd'] : null,
                    'pay_mode' => $s['pay_mode'], 'state' => $c['state'], 'costed' => $c['costed'], 'blocks' => $c['blocks'], 'why' => $c['why'], 'note' => $s['match_note'] ?? null,
                    'special_note' => $s['special_note'] ?? null, 'ok_note' => $s['meal_ok_note'] ?? null, 'ours' => $ours, 'suggest_code' => $s['suggest_code'] ?? null,
                    // picked by the system from the route (a person's own pick, or a change, makes it theirs)
                    'auto' => !empty($s['meal_auto']), 'source_title' => $s['source_title'] ?? null,
                    // where its booking voucher goes at Confirm Tour, and what it will state in LKR (the master's actual rates)
                    'book_email' => $s['meal_email'] ?? null, 'book_contact' => $s['meal_contact'] ?? null, 'book_phone' => $s['meal_phone'] ?? null,
                    'lkr' => $m !== null ? (self::costLkr($m, (int) ($s['qty_adult'] ?? 0), (int) ($s['qty_child'] ?? 0))['total'] ?? null) : null,
                ];
            }
            $byHotel = in_array($type, $gives, true);
            $problems = [];
            if ($mealLine && $party !== null && $people > $party) {
                $state = 'duplicate';
                $problems[] = ['code' => 'meal_dup_' . $n . '_' . $type, 'label' => $day . ': ' . $type . ' is arranged for ' . $people . ' people but the party that day is ' . $party
                    . ' — the same people would have ' . $type . ' twice. Remove the extra line or lower the adults and children under Meals.'];
            } elseif ($byHotel && $mealLine && array_filter($cells, static fn ($c) => $c['meal_id'] !== null && $c['ours'] && trim((string) $c['ok_note']) === '')) {
                $state = 'conflict';
                $problems[] = ['code' => 'meal_hotel_' . $n . '_' . $type, 'label' => $day . ': ' . $type . ' is arranged outside, but the hotel\'s ' . $mp . ' plan gives ' . $type . ' that night'
                    . ($mp === 'HB' ? ' — switch the night\'s HB meal to ' . ($type === 'dinner' ? 'lunch' : 'dinner') . ', remove the outside ' . $type . ', or confirm it with a reason under Meals.' : ' — remove the outside ' . $type . ' or confirm it with a reason under Meals.')];
            } elseif ($held) {
                $state = 'held';
            } elseif ($mealLine) {
                $state = 'arranged';
            } elseif (array_filter($cells, static fn ($c) => $c['ours'])) {
                $state = 'old';
            } elseif ($cells) {
                $state = 'declined';
            } elseif ($byHotel) {
                $state = 'hotel';
            } else {
                $state = 'open';
            }
            $sugg = [];
            if (!$locked && in_array($state, ['open', 'old'], true) && $date) {
                $sugg = array_slice($this->suggest($type, $date, $legs, $towns, $nightPlaces, $lastTo, $departure), 0, 3);
            }
            $row['meals'][$type] = [
                'state' => $state, 'by_hotel' => $byHotel, 'people' => $people, 'lines' => $cells, 'problems' => $problems, 'suggestions' => $sugg,
                'room' => $party !== null ? max(0, $party - $people) : null, 'skipped' => false,
            ];
        }
        // what the automatic lunch place reads the day by: the towns driven through, where the party sleeps, the last place reached
        $row['where'] = ['towns' => $towns, 'night' => $nightPlaces, 'last_to' => $lastTo];
        return $row;
    }

    /**
     * The meals of the master that suit a day, best first, each with why: for lunch the meals on a leg the day drives,
     * then those in a town on the day's route, then where the party sleeps; for dinner where the party sleeps that night,
     * then at the end of the day's last leg, then on a leg of the day. Only meals that may be booked that day.
     */
    public function suggest(string $type, ?string $date, array $legs, array $towns, array $nightPlaces, ?string $lastTo, bool $departure = false): array
    {
        $out = [];
        foreach ($this->usable($type, $date) as $m) {
            $score = 0;
            $why   = null;
            $onLeg = null;
            foreach ($m['routes'] as $r) {
                foreach ($legs as $l) {
                    if (self::placeMatch($r['from'], $l['from']) && self::placeMatch($r['to'], $l['to'])) {
                        $onLeg = $r['from'] . ' → ' . $r['to'];
                        break 2;
                    }
                }
            }
            $inNight = (bool) array_filter($nightPlaces, static fn ($p) => self::placeMatch($m['location'], $p));
            $inTown  = (bool) array_filter($towns, static fn ($p) => self::placeMatch($m['location'], $p));
            $atEnd   = $lastTo !== null && self::placeMatch($m['location'], $lastTo);
            $tonight = $departure ? 'where the day starts' : 'where the party stays that night';
            if ($type === 'lunch') {
                if ($onLeg !== null) {
                    [$score, $why] = [30, 'On the ' . $onLeg . ' route'];
                } elseif ($inTown) {
                    [$score, $why] = [20, 'In ' . $m['location'] . ', on the day\'s route'];
                } elseif ($inNight) {
                    [$score, $why] = [10, 'In ' . $m['location'] . ', ' . $tonight];
                }
            } else {
                if ($inNight && !$departure) {
                    [$score, $why] = [30, 'In ' . $m['location'] . ', ' . $tonight];
                } elseif ($atEnd) {
                    [$score, $why] = [20, 'In ' . $m['location'] . ', at the end of the day\'s drive'];
                } elseif ($onLeg !== null) {
                    [$score, $why] = [10, 'On the ' . $onLeg . ' route'];
                } elseif ($inNight) {
                    [$score, $why] = [5, 'In ' . $m['location'] . ', ' . $tonight];
                }
            }
            if ($score > 0) {
                $out[] = self::option($m) + ['score' => $score, 'why' => $why, 'adult_usd' => $this->adultUsd($m, $date)];
            }
        }
        // the route first (the leg, then a town on it, then where the party sleeps), then the cheapest an adult with the taxes
        usort($out, static fn ($a, $b) => [$b['score'], $a['adult_usd'] ?? PHP_FLOAT_MAX, $a['restaurant']] <=> [$a['score'], $b['adult_usd'] ?? PHP_FLOAT_MAX, $b['restaurant']]);
        return $out;
    }

    private array $rateCache = [];

    /** Rupees to the dollar for a date: the rate set for that day, else the latest before it, else the latest on file (the Daily USD Rate). */
    public function usdRate(?string $date): ?array
    {
        $key = (string) $date;
        if (!array_key_exists($key, $this->rateCache)) {
            $row = null;
            try {
                $row = $date ? $this->db->query('SELECT rate, rate_date FROM daily_usd_rate WHERE rate_date <= ? AND rate > 0 ORDER BY rate_date DESC LIMIT 1', [$date])->getRowArray() : null;
                $row ??= $this->db->query('SELECT rate, rate_date FROM daily_usd_rate WHERE rate > 0 ORDER BY rate_date DESC LIMIT 1')->getRowArray();
            } catch (\Throwable $e) {
                $row = null;
            }
            $this->rateCache[$key] = $row ? ['rate' => (float) $row['rate'], 'rate_date' => (string) $row['rate_date']] : null;
        }
        return $this->rateCache[$key];
    }

    /** What one adult costs with the taxes, in USD on a date (an LKR meal at that day's rate) — how meals are compared on price. */
    public function adultUsd(array $m, ?string $date): ?float
    {
        $one = self::cost($m, 1, 0)['total'];
        if ((string) $m['currency'] === 'USD') {
            return $one;
        }
        $r = $this->usdRate($date);
        return $r ? round($one / $r['rate'], 2) : null;
    }

    /** A meal as the inquiry's picker shows it. */
    public static function option(array $m): array
    {
        return [
            'id' => $m['id'], 'code' => $m['code'], 'meal_type' => $m['meal_type'], 'restaurant' => $m['restaurant'], 'location' => $m['location'], 'cuisine' => $m['cuisine'],
            'description' => $m['description'], 'currency' => $m['currency'], 'adult_rate' => $m['adult_rate'], 'child_rate' => $m['child_rate'], 'tax_pct' => $m['tax_pct'],
            'tax_included' => $m['tax_included'], 'tax_text' => $m['tax_text'], 'rate_text' => $m['rate_text'], 'route_text' => $m['route_text'], 'title' => $m['title'],
            'contact' => trim(implode(' · ', array_filter([$m['contact_name'], $m['contact_phone']]))), 'valid' => $m['status_text'], 'needs_supplier' => $m['needs_supplier'] ?? false,
            // where its booking voucher goes, and what it states in LKR
            'contact_email' => $m['contact_email'] ?: ($m['supplier']['email'] ?? null), 'contact_name' => $m['contact_name'], 'contact_phone' => $m['contact_phone'] ?: ($m['supplier']['phone'] ?? null),
            'adult_lkr' => $m['adult_lkr'] ?? null, 'child_lkr' => $m['child_lkr'] ?? null, 'lkr_text' => $m['lkr_text'] ?? self::lkrText($m), 'needs_lkr' => $m['needs_lkr'] ?? true,
        ];
    }

    /**
     * What the picker on an inquiry offers for a day and a meal type: the suggestions from the itinerary and every meal of
     * the type that may be booked that day, the party that day and the adults and children a new line starts with.
     */
    public function options(array $it, int $dayNo, string $type): array
    {
        $this->need();
        if (!isset(self::TYPES[$type])) {
            throw new \InvalidArgumentException('Meal Type can only be Lunch or Dinner.');
        }
        $plan = $this->plan($it);
        $row  = null;
        foreach ($plan['days'] as $r) {
            if ($r['day_no'] === $dayNo) {
                $row = $r;
                break;
            }
        }
        if ($row === null) {
            throw new \InvalidArgumentException('Day ' . $dayNo . ' is not a day of this tour.');
        }
        $towns = [];
        foreach ($row['legs'] as $l) {
            $towns[] = $l['from'];
            $towns[] = $l['to'];
        }
        $nightPlaces = array_values(array_filter([$row['area'], $row['hotel']]));
        $sugg = $this->suggest($type, $row['date'], $row['legs'], $towns, $nightPlaces, $row['legs'] ? $row['legs'][count($row['legs']) - 1]['to'] : null, $row['departure']);
        $seen = array_flip(array_column($sugg, 'id'));
        $all  = [];
        foreach ($this->usable($type, $row['date']) as $m) {
            if (!isset($seen[$m['id']])) {
                $all[] = self::option($m) + ['adult_usd' => $this->adultUsd($m, $row['date'])];
            }
        }
        usort($all, static fn ($a, $b) => [$a['adult_usd'] ?? PHP_FLOAT_MAX, $a['restaurant']] <=> [$b['adult_usd'] ?? PHP_FLOAT_MAX, $b['restaurant']]);
        $split = self::partySplit($it, $row['party']);
        return [
            'day' => $row, 'type' => $type, 'suggestions' => $sugg, 'others' => $all, 'split' => $split,
            'time' => $this->defaultTime($type), 'hotel_gives' => in_array($type, $row['hotel_gives'], true),
        ];
    }

    /**
     * Put a meal of the master on a day of a booking, or change one (the line's adults, children, time, note, or another meal) —
     * or take an old lunch / dinner line onto the master (its words kept as where it came from). The rules:
     *   the day is a night of the stay or the departure day; Meal Type is Lunch or Dinner and the meal is of that type;
     *   the meal may be booked that day (in use, within its dates, its supplier available) — inactive meals and suppliers
     *   cannot be selected; adults and children are given, not negative, at least one, and no more than the party that day;
     *   with the other lunch (dinner) lines of the day, no more people than the party (no meal twice for the same people);
     *   a meal the hotel's plan gives that night only with confirm_outside and a reason.
     *
     * Writes the line; the caller (IntakeService::saveMeal) prices it, re-plans the day and records it.
     *
     * @return array{service_id:int, note:string}
     */
    public function assign(array $it, ?int $serviceId, array $b): array
    {
        $this->need();
        $intakeId = (int) $it['id'];
        $type = strtolower(trim((string) ($b['meal_type'] ?? '')));
        if (!isset(self::TYPES[$type])) {
            throw new \InvalidArgumentException('Meal Type can only be Lunch or Dinner.');
        }
        $own = null;
        if ($serviceId) {
            $own = $this->db->query('SELECT * FROM tf_intake_service WHERE id = ? AND intake_id = ?', [$serviceId, $intakeId])->getRowArray();
            if (!$own) {
                throw new \InvalidArgumentException('That line is not on this inquiry.');
            }
            if (!in_array((string) $own['kind'], ['lunch', 'dinner'], true) && empty($own['meal_id'])) {
                throw new \InvalidArgumentException('Only a lunch or dinner line can be priced from the Meal Master.');
            }
        }
        $dayNo = (int) ($b['day_no'] ?? ($own['day_no'] ?? 0));
        $plan  = $this->plan($it);
        $row   = null;
        foreach ($plan['days'] as $r) {
            if ($r['day_no'] === $dayNo) {
                $row = $r;
                break;
            }
        }
        if ($row === null) {
            throw new \InvalidArgumentException('Day ' . $dayNo . ' is not a night of this tour or its departure day.');
        }
        $date = $row['date'];
        if (!$date) {
            throw new \InvalidArgumentException('Day ' . $dayNo . ' has no date yet — set the arrival date first, so the meal\'s validity can be checked.');
        }
        $mealId = self::intOrNull($b['meal_id'] ?? null);
        $m = $mealId ? $this->forPricing($mealId) : null;
        if ($m === null) {
            throw new \InvalidArgumentException('Pick the meal from the Meal Master.');
        }
        if ($m['meal_type'] !== $type) {
            throw new \InvalidArgumentException($m['code'] . ' is a ' . $m['meal_type'] . ', not a ' . $type . '.');
        }
        $st = self::standing($m, $date);
        if ($st['status'] !== 'valid') {
            throw new \InvalidArgumentException($m['code'] . ' (' . $m['restaurant'] . ') cannot be booked for ' . self::dateText($date) . ': ' . lcfirst($st['text']) . '. Inactive meals and suppliers cannot be selected.');
        }

        $a = $b['qty_adult'] ?? null;
        $c = $b['qty_child'] ?? null;
        if ($a === null || $a === '' || $c === null || $c === '') {
            throw new \InvalidArgumentException('Say how many adults and how many children the ' . $type . ' is for (0 when there are none).');
        }
        if (!is_numeric($a) || !is_numeric($c) || (int) $a != $a || (int) $c != $c || (int) $a < 0 || (int) $c < 0) {
            throw new \InvalidArgumentException('Adults and children are whole numbers, never negative.');
        }
        $a = (int) $a;
        $c = (int) $c;
        if ($a + $c < 1) {
            throw new \InvalidArgumentException('A meal is for at least one person.');
        }
        $party = $row['party'];
        if ($party !== null && $a + $c > $party) {
            throw new \InvalidArgumentException('The party on day ' . $dayNo . ' is ' . $party . '; a ' . $type . ' cannot be for ' . ($a + $c) . '.');
        }
        // the same people are not given the meal twice: the other lines of the day that are ours, with this one, stay within the party
        $others = 0;
        $names  = [];
        foreach ($row['meals'][$type]['lines'] as $l) {
            if ($l['id'] === (int) ($serviceId ?? 0) || !$l['ours']) {
                continue;
            }
            $h = $l['qty_adult'] !== null || $l['qty_child'] !== null ? (int) $l['qty_adult'] + (int) $l['qty_child'] : ($l['qty'] !== null ? (int) round($l['qty']) : (int) $party);
            $others += $h;
            $names[] = '"' . $l['title'] . '" (' . $h . ')';
        }
        if ($party !== null && $others + $a + $c > $party) {
            throw new \InvalidArgumentException('Day ' . $dayNo . ' already has ' . $type . ' for ' . $others . ' of the ' . $party . ' people: ' . implode(', ', $names)
                . '. The same people cannot have ' . $type . ' twice — change that line (or take it onto the Meal Master with "Price from the Meal Master") instead of adding another.');
        }
        // a meal the hotel's plan gives that night: only on purpose, with a reason
        $okNote = $own['meal_ok_note'] ?? null;
        if (in_array($type, $row['hotel_gives'], true)) {
            $reason = trim((string) ($b['outside_reason'] ?? ''));
            if (empty($b['confirm_outside']) && trim((string) $okNote) === '') {
                throw new MealConflict('The hotel\'s ' . $row['meal_plan'] . ' plan gives ' . $type . ' on ' . strtolower($row['label']) . ($row['meal_plan'] === 'HB' ? ' (the HB meal that night is ' . $row['hb_meal'] . ')' : '') . '. '
                    . ($row['meal_plan'] === 'HB' ? 'Switch the night\'s HB meal to ' . ($type === 'dinner' ? 'lunch' : 'dinner') . ', or ' : '') . 'confirm that the ' . $type . ' is arranged outside as well, with the reason.', $row['meal_plan'] === 'HB' && $row['hb_switchable']);
            }
            if (!empty($b['confirm_outside'])) {
                if (mb_strlen($reason) < 3) {
                    throw new \InvalidArgumentException('Give the reason the ' . $type . ' is arranged outside although the hotel gives it.');
                }
                $okNote = mb_substr($reason . ' — ' . $this->who() . ', ' . date('j M Y'), 0, 255);
            }
        } else {
            $okNote = null;
        }
        $time = self::time($b['meal_time'] ?? null) ?? $this->defaultTime($type);
        $note = array_key_exists('special_note', $b) ? self::cut($b['special_note'], 500) : ($own['special_note'] ?? null);
        $mode = in_array((string) ($b['pay_mode'] ?? ''), ['in_tour', 'prepaid'], true) ? (string) $b['pay_mode'] : 'in_tour';
        // where the booking voucher goes: as typed when the restaurant is chosen, else what the line had for this meal, else the master's
        $book = $this->bookingContact($m, $own, $b);
        $sid  = $this->writeLine($intakeId, $own, $dayNo, $type, $m, $a, $c, $time, $note, $okNote, $mode, false, $book);
        $title = self::lineTitle($m);
        $said = !$own ? 'Meal added: ' . $m['code'] . ' — ' . $title . '.' : (empty($own['meal_id']) ? 'Taken onto the Meal Master: ' . $m['code'] . ' — ' . $title . '.' : 'Meal updated: ' . $m['code'] . ' — ' . $title . '.');
        // a person put a meal on the day: it is theirs, and a lunch taken off the day before is wanted again
        $this->setSkip($intakeId, $type, $dayNo, false);
        $cost = self::cost($m, $a, $c);
        $said .= ' ' . $a . ' adult' . ($a === 1 ? '' : 's') . ($c ? ' and ' . $c . ' child' . ($c === 1 ? '' : 'ren') : '') . ' at ' . $time . ': ' . $m['currency'] . ' ' . number_format($cost['total'], 2)
            . ($cost['tax'] > 0 ? ' (taxes ' . $m['currency'] . ' ' . number_format($cost['tax'], 2) . ')' : '') . '.'
            // tf-stage1: with children on the line the figure counts them only once the Child Fare Review confirms it
            . ($c ? ' The ' . $c . ' child' . ($c === 1 ? '' : 'ren') . ' count' . ($c === 1 ? 's' : '') . ' once confirmed in the Child Fare Review; until then the line is costed for the adults.' : '');
        if ($okNote !== null && !empty($b['confirm_outside'])) {
            $said .= ' Arranged outside although the hotel gives it: ' . $reason . '.';
        }
        return ['service_id' => $sid, 'note' => $said];
    }

    /**
     * The restaurant's booking email and contact for a line (where its booking voucher goes at Confirm Tour): what was typed
     * when the restaurant was chosen (book_email / book_contact / book_phone), else what the line already had for the same
     * meal, else the Meal Master's (its contact, else the Supplier master's email and phone). An address that is not one is refused.
     *
     * @return array{email: ?string, contact: ?string, phone: ?string}
     */
    public function bookingContact(array $m, ?array $own, array $b): array
    {
        $same = $own && !empty($own['meal_id']) && (int) $own['meal_id'] === (int) $m['id'];
        $pick = static function (string $key, string $col, ?string $master) use ($b, $own, $same): ?string {
            if (array_key_exists($key, $b)) {
                return self::cut($b[$key], $col === 'meal_email' ? 190 : ($col === 'meal_contact' ? 120 : 60));
            }
            if ($same && trim((string) ($own[$col] ?? '')) !== '') {
                return (string) $own[$col];
            }
            return $master !== null && trim($master) !== '' ? trim($master) : null;
        };
        // the master's address, or the Supplier master's — only one that is an address (a supplier record may hold anything)
        $mEmail = null;
        foreach ([$m['contact_email'] ?? null, $m['supplier']['email'] ?? null] as $x) {
            if ($x !== null && filter_var(trim((string) $x), FILTER_VALIDATE_EMAIL)) {
                $mEmail = trim((string) $x);
                break;
            }
        }
        $out = [
            'email'   => $pick('book_email', 'meal_email', $mEmail),
            'contact' => $pick('book_contact', 'meal_contact', $m['contact_name'] ?? null),
            'phone'   => $pick('book_phone', 'meal_phone', $m['contact_phone'] ?: ($m['supplier']['phone'] ?? null)),
        ];
        if ($out['email'] !== null && !filter_var($out['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('"' . $out['email'] . '" is not an email address — the booking voucher goes to the restaurant by email.');
        }
        return $out;
    }

    /** A Meal Master holder's "save on the Meal Master": the restaurant's booking email and contact on its record, for the next booking. */
    public function saveContact(int $mealId, array $book): ?string
    {
        $m = $this->db->query('SELECT * FROM tf_meal WHERE id = ?', [$mealId])->getRowArray();
        if (!$m) {
            return null;
        }
        $set = [];
        $changes = [];
        foreach (['contact_email' => 'email', 'contact_name' => 'contact', 'contact_phone' => 'phone'] as $col => $k) {
            $v = $book[$k] ?? null;
            if ($v !== null && trim((string) $v) !== '' && (string) $v !== (string) ($m[$col] ?? '')) {
                $set[$col] = $v;
                $changes[] = ['what' => $col, 'from' => $m[$col], 'to' => $v];
            }
        }
        if (!$set) {
            return null;
        }
        $this->db->query('UPDATE tf_meal SET ' . implode(', ', array_map(static fn ($c) => "`{$c}` = ?", array_keys($set))) . ', updated_at = ?, updated_by = ? WHERE id = ?', array_merge(array_values($set), [$this->now(), $this->user, $mealId]));
        $this->log($mealId, 'Booking contact saved from an inquiry: ' . $m['code'] . ' — ' . $m['restaurant'], $changes);
        $this->refresh();
        return 'Saved on the Meal Master (' . $m['code'] . ') for the next booking.';
    }

    /**
     * Write one meal line: a new one, or the line given (a meal changed, an old lunch line taken onto the master). The
     * line keeps the agent's words as where it came from; it is the person's (auto = false) or the system's pick (auto = true).
     */
    private function writeLine(int $intakeId, ?array $own, int $dayNo, string $type, array $m, int $a, int $c, string $time, ?string $note, ?string $okNote, string $mode, bool $auto, ?array $book = null): int
    {
        $title = self::lineTitle($m);
        $desc  = $m['description'];
        $book ??= $this->bookingContact($m, $own, []);
        if ($own) {
            $src = trim((string) ($own['source_title'] ?? '')) !== '' ? $own['source_title'] : ($own['title'] !== $title && empty($own['meal_id']) ? $own['title'] : null);
            $keepDesc = trim((string) ($own['description'] ?? '')) !== '' && (empty($own['meal_id']) || (int) $own['meal_id'] === (int) $m['id']);
            $this->db->query(
                "UPDATE tf_intake_service SET day_no = ?, kind = ?, title = ?, source_title = ?, meal_id = ?, meal_auto = ?, qty_adult = ?, qty_child = ?, qty = ?, pax = ?, meal_time = ?, special_note = ?, meal_ok_note = ?,
                    meal_email = ?, meal_contact = ?, meal_phone = ?, pay_mode = ?, fee_id = NULL, fee_code = NULL, place_id = NULL, free_visit_id = NULL, origin = ?" . (!$keepDesc ? ', description = ?' : '') . ' WHERE id = ?',
                array_merge([$dayNo, $type, $title, self::cut($src, 255), $m['id'], $auto ? 1 : 0, $a, $c, $a + $c, $a + $c, $time, $note, $okNote, $book['email'], $book['contact'], $book['phone'], $mode, $auto ? 'ai' : 'human'], !$keepDesc ? [$desc] : [], [(int) $own['id']])
            );
            return (int) $own['id'];
        }
        $next = (int) ($this->db->query('SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM tf_intake_service WHERE intake_id = ? AND day_no = ?', [$intakeId, $dayNo])->getRowArray()['n'] ?? 0);
        $this->db->query(
            "INSERT INTO tf_intake_service (intake_id, day_no, kind, title, qty, pax, pay_mode, special_note, origin, sort_order, meal_id, meal_auto, qty_adult, qty_child, meal_time, meal_ok_note, meal_email, meal_contact, meal_phone, description)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$intakeId, $dayNo, $type, $title, $a + $c, $a + $c, $mode, $note, $auto ? 'ai' : 'human', $next, $m['id'], $auto ? 1 : 0, $a, $c, $time, $okNote, $book['email'], $book['contact'], $book['phone'], $desc]
        );
        return (int) ($this->db->query('SELECT LAST_INSERT_ID() AS id')->getRowArray()['id'] ?? 0);
    }

    /**
     * The lunch place of every day of the tour, decided by the system from the itinerary (Shantha, 3 Oct: "every day of the
     * tour"; "route first, then cheapest"). For each day whose hotel plan does not give lunch:
     *   the restaurant on the leg driven that day, else in a town on that day's route, else where the party sleeps (the
     *   departure day: where it starts) — and among those, the cheapest an adult with the taxes;
     *   only meals that may be booked that day (in use, within their dates, the supplier not unavailable);
     *   for the day's party (the inquiry's adults and children), at the usual lunch time.
     * A lunch stop already on the day (from the email, not yet priced from the master) is given the restaurant. A lunch
     * a person picked or changed is theirs and never replaced; a day a person took lunch off is left alone; a day the
     * agent said is their own arrangement is left alone. A lunch the system picked follows the route: when the route
     * changes it is picked again, and it goes when nothing suits the day any more. Nothing on a confirmed, cancelled or
     * archived inquiry. Switched off with the setting.
     *
     * @return array{changed: array<int, int>, said: array<int, string>}
     */
    public function autoLunch(array $it): array
    {
        $out = ['changed' => [], 'said' => []];
        if (!self::installed($this->db) || !$this->autoOn() || empty($it['id'])) {
            return $out;
        }
        $plan = $it['meals'] ?? $this->plan($it);
        if (empty($plan['available']) || !empty($plan['locked'])) {
            return $out;
        }
        $intakeId = (int) $it['id'];
        $time = $this->defaultTime('lunch');
        foreach ($plan['days'] as $r) {
            $c = $r['meals']['lunch'];
            $day = ($r['departure'] ? 'the departure day' : 'day ' . $r['day_no']);
            if (in_array('lunch', $r['hotel_gives'], true) || !empty($c['skipped'])) {
                // the hotel gives lunch that night now (or a person took lunch off the day): the system's own pick goes
                foreach ($c['lines'] as $l) {
                    if ($l['auto'] && $l['meal_id'] !== null) {
                        $this->db->query('DELETE FROM tf_intake_service WHERE id = ? AND meal_auto = 1', [$l['id']]);
                        $out['changed'][] = $l['id'];
                        $out['said'][] = 'Lunch place for ' . $day . ' taken off: ' . (!empty($c['skipped']) ? 'lunch was taken off that day.' : 'the hotel\'s ' . $r['meal_plan'] . ' plan gives lunch.');
                    }
                }
                continue;
            }
            if (!$r['date']) {
                continue;
            }
            $lines = $c['lines'];
            if (array_filter($lines, static fn ($l) => !$l['ours'])) {
                continue;   // the agent said lunch is their own arrangement (or not happening) that day
            }
            if (array_filter($lines, static fn ($l) => $l['meal_id'] !== null && !$l['auto'])) {
                continue;   // a person's pick stands
            }
            if (count($lines) > 1) {
                continue;   // several lunch lines on the day: for a person to settle
            }
            $line  = $lines[0] ?? null;
            $where = $r['where'] ?? ['towns' => [], 'night' => [], 'last_to' => null];
            $best  = $this->suggest('lunch', $r['date'], $r['legs'], $where['towns'], $where['night'], $where['last_to'], $r['departure'])[0] ?? null;
            $split = self::partySplit($it, $r['party']);
            $a = (int) $split['adults'];
            $k = (int) $split['children'];
            if ($line === null) {
                if ($best === null || $a + $k < 1) {
                    continue;
                }
                $m = $this->forPricing((int) $best['id']);
                $sid = $this->writeLine($intakeId, null, $r['day_no'], 'lunch', $m, $a, $k, $time, null, null, 'in_tour', true);
                $out['changed'][] = $sid;
                $out['said'][] = 'Lunch place for ' . $day . ': ' . $m['restaurant'] . ' (' . lcfirst($best['why']) . ').';
                continue;
            }
            $own = $this->db->query('SELECT * FROM tf_intake_service WHERE id = ?', [$line['id']])->getRowArray();
            if (!$own) {
                continue;
            }
            if ($line['meal_id'] === null) {
                // a lunch stop on the day (the email's "Lunch"): the restaurant is put on it, its words kept as where it came from
                if ($best === null || $a + $k < 1) {
                    continue;
                }
                $m = $this->forPricing((int) $best['id']);
                $this->writeLine($intakeId, $own, $r['day_no'], 'lunch', $m, $a, $k, $own['meal_time'] ?: $time, $own['special_note'] ?? null, null, 'in_tour', true);
                $out['changed'][] = (int) $own['id'];
                $out['said'][] = 'Lunch place for ' . $day . ': ' . $m['restaurant'] . ' on "' . $own['title'] . '" (' . lcfirst($best['why']) . ').';
                continue;
            }
            // the system's own pick: follows the route and the party
            if ($best === null) {
                $this->db->query('DELETE FROM tf_intake_service WHERE id = ? AND meal_auto = 1', [(int) $own['id']]);
                $out['changed'][] = (int) $own['id'];
                $out['said'][] = 'Lunch place for ' . $day . ' taken off: nothing in the Meal Master suits the day\'s route any more.';
                continue;
            }
            if ((int) $own['meal_id'] !== (int) $best['id'] || (int) $own['qty_adult'] !== $a || (int) $own['qty_child'] !== $k) {
                $m = $this->forPricing((int) $best['id']);
                $this->writeLine($intakeId, $own, $r['day_no'], 'lunch', $m, $a, $k, $own['meal_time'] ?: $time, $own['special_note'] ?? null, null, 'in_tour', true);
                $out['changed'][] = (int) $own['id'];
                $out['said'][] = (int) $own['meal_id'] !== (int) $best['id'] ? 'Lunch place for ' . $day . ' now ' . $m['restaurant'] . ' (' . lcfirst($best['why']) . ').' : 'Lunch place for ' . $day . ': ' . $a . ' adult(s), ' . $k . ' child(ren), as the party.';
            }
        }
        return $out;
    }

    /**
     * A restaurant added from an inquiry's lunch place (the "+"): a meal of the master in its own right, its code given,
     * the supplier account linked later by accounts (it is paid only once it is). Every rule of save() applies.
     */
    public function quickAdd(array $in, array $row): array
    {
        $this->need();
        $type = isset(self::TYPES[(string) ($in['meal_type'] ?? '')]) ? (string) $in['meal_type'] : 'lunch';
        $legs = [];
        if (!empty($in['route_from']) && !empty($in['route_to'])) {
            $legs[] = ['from' => $in['route_from'], 'to' => $in['route_to']];
        }
        $from = $row['date'] && $row['date'] < date('Y-m-d') ? $row['date'] : date('Y-m-d');
        return $this->save([
            'code' => $in['code'] ?? '', 'meal_type' => $type, 'supplier_id' => $in['supplier_id'] ?? null, 'restaurant' => $in['restaurant'] ?? '',
            'location' => $in['location'] ?? '', 'address' => $in['address'] ?? null, 'contact_name' => $in['contact_name'] ?? null, 'contact_phone' => $in['contact_phone'] ?? null,
            'contact_email' => $in['contact_email'] ?? null,
            'cuisine' => $in['cuisine'] ?? null, 'description' => $in['description'] ?? null, 'adult_rate' => $in['adult_rate'] ?? null, 'child_rate' => $in['child_rate'] ?? null,
            'adult_lkr' => $in['adult_lkr'] ?? null, 'child_lkr' => $in['child_lkr'] ?? null,
            'currency' => $in['currency'] ?? 'USD', 'tax_pct' => $in['tax_pct'] ?? 0, 'tax_included' => !empty($in['tax_included']) ? 1 : 0, 'tax_note' => $in['tax_note'] ?? null,
            'valid_from' => $in['valid_from'] ?? $from, 'valid_to' => $in['valid_to'] ?? null, 'active' => 1,
            'remarks' => trim('Added from an inquiry\'s lunch place by ' . $this->who() . ' on ' . date('j M Y') . '. ' . (string) ($in['remarks'] ?? '')),
            'routes' => $legs, 'confirm_high' => $in['confirm_high'] ?? 0,
        ]);
    }

    // =====================================================================
    //  Booking the restaurant: a booking voucher, emailed when the tour is confirmed
    // =====================================================================

    /** Whether the restaurant booking vouchers can be issued here (schema 40). */
    public function vouchersOn(): bool
    {
        try {
            return (bool) $this->db->query("SHOW TABLES LIKE 'tf_meal_voucher'")->getResultArray()
                && (bool) $this->db->query("SHOW COLUMNS FROM `tf_meal_voucher` LIKE 'mail_status'")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Every voucher of a desk line, newest first. */
    public function vouchers(int $jobId): array
    {
        if (!$this->vouchersOn()) {
            return [];
        }
        return $this->db->query('SELECT * FROM tf_meal_voucher WHERE job_id = ? ORDER BY id DESC', [$jobId])->getResultArray();
    }

    /** An address that is one, or null. */
    private static function email(?string $v): ?string
    {
        $v = trim((string) $v);
        return $v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
    }

    /**
     * What the booking voucher of a desk line says now: the line, the meal, the amount in LKR from the Meal Master's actual
     * rates (never converted from USD), where it goes, the live voucher and whether the line has moved away from it — and
     * why no voucher can be issued, when one cannot (nothing guessed).
     */
    public function booking(int $jobId): array
    {
        $j = $this->db->query('SELECT j.*, t.intake_id, t.tour_no, t.stage AS tour_stage FROM tf_service_job j JOIN tf_tour t ON t.id = j.tour_id WHERE j.id = ?', [$jobId])->getRowArray();
        if (!$j) {
            throw new \InvalidArgumentException('That activity line is not on any tour.');
        }
        $s = (string) $j['source'] === 'service' && !empty($j['source_id']) ? $this->db->query('SELECT * FROM tf_intake_service WHERE id = ?', [(int) $j['source_id']])->getRowArray() : null;
        if (!$s || empty($s['meal_id'])) {
            throw new \InvalidArgumentException('This line is not a meal of the Meal Master.');
        }
        $m = $this->forPricing((int) $s['meal_id']);
        $a = (int) ($s['qty_adult'] ?? 0);
        $c = (int) ($s['qty_child'] ?? 0);
        $lkr = $m !== null ? self::costLkr($m, $a, $c) : null;
        $to  = self::email($s['meal_email'] ?? null) ?? ($m ? (self::email($m['contact_email']) ?? self::email($m['supplier']['email'] ?? null)) : null);
        $contact = trim(implode(' · ', array_filter([$s['meal_contact'] ?? ($m['contact_name'] ?? null), $s['meal_phone'] ?? ($m['contact_phone'] ?? null)])));
        $live = $this->vouchersOn() ? ($this->db->query("SELECT * FROM tf_meal_voucher WHERE job_id = ? AND state = 'issued' ORDER BY id DESC LIMIT 1", [$jobId])->getRowArray() ?: null) : null;
        $why = null;
        if ($m === null) {
            $why = 'The meal this line was priced from is no longer in the Meal Master.';
        } elseif ($lkr === null) {
            $why = $m['restaurant'] . ' has no LKR rate in the Meal Master yet (' . $m['code'] . '). Enter the amount the restaurant charges, then issue the voucher.';
        } elseif ($a + $c < 1) {
            $why = 'The line is for nobody (no adults, no children): put it right on the inquiry first.';
        } elseif (in_array((string) $j['status'], ['cancelled', 'not_needed'], true) || !empty($j['archived_at']) || (string) $j['tour_stage'] === 'cancelled') {
            $why = 'This line is ' . ((string) $j['status'] === 'not_needed' ? 'marked "nothing to arrange"' : ((string) $j['tour_stage'] === 'cancelled' || (string) $j['status'] === 'cancelled' ? 'cancelled' : 'archived')) . ': nothing to book.';
        }
        return ['job' => $j, 'line' => $s, 'meal' => $m, 'adults' => $a, 'children' => $c, 'lkr' => $lkr, 'to' => $to, 'contact' => $contact !== '' ? $contact : null,
                'live' => $live, 'stale' => $live !== null && self::stale($live, $s, $j), 'why' => $why];
    }

    /** A live voucher the line has moved away from (another meal, other people, another day or time): it is replaced, not edited. */
    private static function stale(array $v, array $s, array $j): bool
    {
        return (int) $v['meal_id'] !== (int) $s['meal_id'] || (int) $v['adults'] !== (int) ($s['qty_adult'] ?? 0) || (int) $v['children'] !== (int) ($s['qty_child'] ?? 0)
            || (string) ($v['on_date'] ?? '') !== (string) ($j['on_date'] ?? '') || (string) ($v['meal_time'] ?? '') !== (string) ($s['meal_time'] ?? '');
    }

    private function nextVoucherNo(): string
    {
        $this->db->query("UPDATE tf_meta SET v = LAST_INSERT_ID(CAST(v AS UNSIGNED) + 1) WHERE k = 'meal_voucher_next'");
        $n = (int) $this->db->query('SELECT LAST_INSERT_ID() AS n')->getRowArray()['n'];
        if ($n <= 0) {
            $this->db->query("INSERT INTO tf_meta (k, v) VALUES ('meal_voucher_next', '1001') ON DUPLICATE KEY UPDATE v = v");
            $this->db->query("UPDATE tf_meta SET v = LAST_INSERT_ID(CAST(v AS UNSIGNED) + 1) WHERE k = 'meal_voucher_next'");
            $n = (int) $this->db->query('SELECT LAST_INSERT_ID() AS n')->getRowArray()['n'];
        }
        $prefix = trim((string) ($this->db->query("SELECT v FROM tf_meta WHERE k = 'meal_voucher_prefix'")->getRowArray()['v'] ?? 'RBV')) ?: 'RBV';
        return $prefix . ' ' . ($n - 1);
    }

    /**
     * Issue the booking voucher of a meal line: numbered, the Meal Master's LKR rates and the amount frozen on it, where it
     * goes. One live voucher a line; a wrong one is cancelled with a note and a fresh one issued.
     */
    public function issueVoucher(TourService $tour, int $jobId, ?string $note = null): array
    {
        if (!$this->vouchersOn()) {
            throw new \RuntimeException('Restaurant booking vouchers are not installed on this site yet. Open Tour Inquiry as the administrator and press Install now.');
        }
        $b = $this->booking($jobId);
        if ($b['why'] !== null) {
            throw new \RuntimeException($b['why']);
        }
        if ($b['live'] !== null) {
            throw new \RuntimeException('Voucher ' . $b['live']['voucher_no'] . ' is already issued for this meal' . ($b['stale'] ? ', and the line has changed since: cancel it with the reason, then issue a fresh one.' : '. Cancel it first if it is wrong, then issue a fresh one.'));
        }
        $j = $b['job'];
        $s = $b['line'];
        $m = $b['meal'];
        $l = $b['lkr'];
        $this->db->transStart();
        $no = $this->nextVoucherNo();
        $this->db->query(
            'INSERT INTO tf_meal_voucher (tour_id, job_id, service_id, meal_id, voucher_no, supplier_id, supplier_acc, restaurant, location, meal_type, on_date, meal_time, adults, children,
                adult_lkr, child_lkr, tax_pct, tax_included, tax_lkr, amount_lkr, special_note, to_email, contact, mail_status, state, note, issued_at, issued_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [(int) $j['tour_id'], $jobId, (int) $s['id'], (int) $m['id'], $no, $m['supplier_id'], $m['supplier']['acc'] ?? $m['supplier_acc'], $m['restaurant'], $m['location'], $m['meal_type'],
             $j['on_date'], $s['meal_time'], $b['adults'], $b['children'], $m['adult_lkr'], $m['child_lkr'], $m['tax_pct'] > 0 ? $m['tax_pct'] : null, $m['tax_included'] ? 1 : 0, $l['tax'] > 0 ? $l['tax'] : null,
             $l['total'], self::cut($s['special_note'] ?? null, 500), $b['to'], self::cut($b['contact'], 190), 'not_sent', 'issued', self::cut($note, 400), $this->now(), $this->user]
        );
        $tour->event((int) $j['intake_id'], (int) $j['tour_id'], 'service', $jobId, 'meal_voucher', null, null,
            $no . ': ' . $m['restaurant'] . ' — ' . strtolower(self::TYPES[$m['meal_type']]) . ' ' . self::dateText($j['on_date']) . ($s['meal_time'] ? ' at ' . $s['meal_time'] : '') . ', '
            . $b['adults'] . 'A' . ($b['children'] ? ' + ' . $b['children'] . 'C' : '') . ' — LKR ' . number_format($l['total'], 2) . ($note ? ' — ' . self::cut($note, 200) : ''));
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('The voucher could not be issued; nothing was changed.');
        }
        return $this->db->query('SELECT * FROM tf_meal_voucher WHERE voucher_no = ?', [$no])->getRowArray();
    }

    /** The words the restaurant booking voucher's ready-made email is filled with (EmailTemplates::WORDS). */
    public function mailWords(array $v, array $sender): array
    {
        $t = $this->db->query('SELECT tour_no, tour_name, agent_name, arrival_date, departure_date, nights FROM tf_tour WHERE id = ?', [(int) $v['tour_id']])->getRowArray() ?: [];
        $co = $this->db->query("SELECT v FROM tf_meta WHERE k = 'company_name'")->getRowArray()['v'] ?? 'Avenir Holidays';
        return [
            'restaurant' => $v['restaurant'], 'meal' => self::TYPES[$v['meal_type']] ?? 'Meal', 'meal_date' => $v['on_date'] ? date('D, d M Y', strtotime((string) $v['on_date'])) : '',
            'meal_time' => (string) ($v['meal_time'] ?? ''), 'adults' => (string) (int) $v['adults'], 'children' => (string) (int) $v['children'],
            'voucher_no' => (string) $v['voucher_no'], 'amount' => $v['amount_lkr'] !== null ? number_format((float) $v['amount_lkr'], 2) : '',
            'tour_no' => (string) ($t['tour_no'] ?? ''), 'tour_name' => (string) ($t['tour_name'] ?? ''), 'agent_name' => (string) ($t['agent_name'] ?? ''),
            'dates' => !empty($t['arrival_date']) ? self::dateText($t['arrival_date']) . ' – ' . self::dateText($t['departure_date'] ?? null) : '', 'arrival' => self::dateText($t['arrival_date'] ?? null),
            'departure' => self::dateText($t['departure_date'] ?? null), 'nights' => (string) ($t['nights'] ?? ''), 'pax' => (string) ((int) $v['adults'] + (int) $v['children']),
            'me' => (string) ($sender['name'] ?? ''), 'company' => (string) $co, 'today' => date('j M Y'),
        ];
    }

    /** The ready-made emails a send window offers for a voucher, filled in, and the one it starts with. */
    public function mailTemplates(array $v, array $sender): array
    {
        $et = new EmailTemplates($this->db);
        $list = $et->forWindow('restaurant_voucher');
        $vars = $this->mailWords($v, $sender);
        $filled = EmailTemplates::filled($list, $vars);
        $start = EmailTemplates::starting($list, 'restaurant_voucher');
        $first = $start ? (EmailTemplates::filled([$start], $vars)[0] ?? null) : null;
        if ($first === null) {
            // no ready-made email at all: a plain one, so the voucher still goes
            $first = ['id' => 0, 'name' => 'Plain', 'category' => 'restaurant_voucher', 'subject' => 'Booking voucher ' . $v['voucher_no'] . ' — ' . $vars['tour_no'] . ' — ' . $vars['meal'] . ' on ' . $vars['meal_date'], 'message' => ''];
        }
        return ['templates' => $filled, 'start' => $first];
    }

    /**
     * Email a voucher to the restaurant, from the signed-in person's mailbox (HotelMail, as the hotel letters go). $mail:
     * to, cc, subject, message (the opening; the voucher itself follows) — blank: the ready-made email's. The email as
     * sent is kept on the voucher, and a failure is kept too; a line still to book moves to "requested".
     *
     * @param callable $render fn(array $voucher, string $mode, ?string $message, array $sender): string — the voucher as HTML (MealMaster::voucherHtml)
     * @return array{ok: bool, error: ?string, voucher: array}
     */
    public function sendVoucher(TourService $tour, int $voucherId, array $mail, array $sender, callable $render): array
    {
        $v = $this->voucher($voucherId);
        if ((string) $v['state'] !== 'issued') {
            throw new \RuntimeException('Voucher ' . $v['voucher_no'] . ' is cancelled; issue a fresh one.');
        }
        $to = self::email($mail['to'] ?? null) ?? (array_key_exists('to', $mail) ? null : self::email($v['to_email']));
        $tpl = $this->mailTemplates($v, $sender)['start'];
        $subject = mb_substr(trim(str_replace(["\r", "\n"], ' ', (string) ($mail['subject'] ?? ''))), 0, 255) ?: $tpl['subject'];
        $message = trim(str_replace("\r", '', (string) ($mail['message'] ?? '')));
        $message = $message !== '' ? EmailTemplates::fill(mb_substr($message, 0, 4000), $this->mailWords($v, $sender)) : $tpl['message'];
        $subject = EmailTemplates::fill($subject, $this->mailWords($v, $sender));
        $cc = array_values(array_filter(array_map('trim', preg_split('/[;,\s]+/', (string) ($mail['cc'] ?? '')) ?: []), static fn ($x) => self::email($x) !== null));
        $error = null;
        $body = null;
        if ($to === null) {
            $error = trim((string) ($mail['to'] ?? '')) !== '' ? '"' . trim((string) $mail['to']) . '" is not an email address.' : 'No email address for ' . $v['restaurant'] . ' — enter it (and tick "save on the Meal Master" to keep it).';
        } elseif (empty($sender['can_send'])) {
            $error = 'Your account has no mailbox set up to send from (email, password and host on your user profile). Print the voucher and send it another way, or ask Management to set it up.';
        } else {
            $body = $render($v, 'email', $message, $sender);
            // the voucher goes in the body and, where the server can make one, as a PDF with the logo — the way it is filed
            $att = self::voucherPdf($v, $message, $sender, $this->who());
            $r = QuoteMail::send($to, $subject, $body, $cc, $att ? [$att] : []);
            $error = $r['ok'] ? null : (string) $r['error'];
            if ($att && is_file($att['path'])) {
                @unlink($att['path']);
            }
        }
        $now = $this->now();
        $ok = $error === null;
        $this->db->query('UPDATE tf_meal_voucher SET to_email = COALESCE(?, to_email), mail_status = ?, mail_error = ?, mail_cc = ?, mail_subject = ?, mail_body = COALESCE(?, mail_body), sent_at = ?, sent_by = ? WHERE id = ?',
            [$to, $ok ? 'sent' : ((string) $v['mail_status'] === 'sent' || (string) $v['mail_status'] === 'other' ? $v['mail_status'] : ($to === null || empty($sender['can_send']) ? 'not_sent' : 'failed')),
             $ok ? null : self::cut($error, 500), $cc ? implode(', ', $cc) : null, $subject, $ok ? $body : null, $ok ? $now : $v['sent_at'], $ok ? $this->user : $v['sent_by'], $voucherId]);
        $tour->event((int) $v['intake_id'], (int) $v['tour_id'], 'service', (int) $v['job_id'], $ok ? 'meal_voucher_sent' : 'meal_voucher_not_sent', null, null,
            $v['voucher_no'] . ($ok ? ' emailed to ' . $to . ($cc ? ' (cc ' . implode(', ', $cc) . ')' : '') : ' not sent: ' . $error));
        // tf-comms-r2: the voucher e-mail (sent or failed) joins the tour's Communications & History record
        if ($to !== null && !empty($sender['can_send'])) {
            try {
                (new Comms($this->db, $this->user))->captureMealVoucher($voucherId);
            } catch (\Throwable $e) {
                log_message('error', 'Communications capture (restaurant voucher ' . $voucherId . '): ' . $e->getMessage());
            }
        }
        if ($ok) {
            $this->requested($tour, (int) $v['job_id'], 'Booking voucher ' . $v['voucher_no'] . ' emailed to ' . $to . '.');
            // the address it went to is this booking's from now on (a fresh voucher goes there too); the price is not touched
            if (!empty($v['service_id'])) {
                $this->db->query('UPDATE tf_intake_service SET meal_email = ? WHERE id = ?', [$to, (int) $v['service_id']]);
            }
        }
        return ['ok' => $ok, 'error' => $error, 'voucher' => $this->voucher($voucherId)];
    }

    /** Sent another way (handed over, WhatsApp, read out on the phone): recorded, nothing mailed. */
    public function markSentOther(TourService $tour, int $voucherId, ?string $note): array
    {
        $v = $this->voucher($voucherId);
        if ((string) $v['state'] !== 'issued') {
            throw new \RuntimeException('Voucher ' . $v['voucher_no'] . ' is cancelled.');
        }
        $note = self::cut($note, 400);
        if ($note === null) {
            throw new \InvalidArgumentException('Say how voucher ' . $v['voucher_no'] . ' reached the restaurant (handed over, WhatsApp to …); it stays on the trail.');
        }
        $this->db->query("UPDATE tf_meal_voucher SET mail_status = 'other', mail_error = NULL, sent_at = ?, sent_by = ?, note = ? WHERE id = ?", [$this->now(), $this->user, $note, $voucherId]);
        $tour->event((int) $v['intake_id'], (int) $v['tour_id'], 'service', (int) $v['job_id'], 'meal_voucher_sent', null, null, $v['voucher_no'] . ' sent another way — ' . $note);
        $this->requested($tour, (int) $v['job_id'], 'Booking voucher ' . $v['voucher_no'] . ' sent another way — ' . $note);
        return $this->voucher($voucherId);
    }

    /** The booking went to the restaurant: a line still to book (or with a problem) reads "requested"; any other is left as it is. */
    private function requested(TourService $tour, int $jobId, string $note): void
    {
        try {
            $st = (string) ($this->db->query('SELECT status FROM tf_service_job WHERE id = ?', [$jobId])->getRowArray()['status'] ?? '');
            if (in_array($st, ['to_book', 'problem'], true)) {
                $tour->serviceRequest($jobId, $note);
            }
        } catch (\Throwable $e) {
            // the voucher went either way; the line's status is the desk's to set
        }
    }

    /**
     * Confirm Tour: each restaurant of the Meal Master on the tour is booked — its voucher issued and emailed to it at once
     * (Shantha, 3 Oct: "sent automatically"), from the confirming person's mailbox, with the ready-made email. Never in the
     * way of the confirmation: a meal with no LKR rate gets no voucher, a restaurant with no email address gets its voucher
     * unsent, a mailbox not set up or a refusal is kept on the voucher — each said, for the Activities desk to finish.
     * On re-confirmation after an amendment: a voucher whose line is unchanged is kept (sent again only if it never went);
     * one whose line changed is cancelled and a fresh one issued and sent.
     *
     * @param callable $render see sendVoucher()
     * @return array<int, array{job_id:int, restaurant:string, voucher_no:?string, status:string, to:?string, why:?string}>
     */
    public function bookOnConfirm(TourService $tour, int $tourId, array $sender, callable $render, bool $automatic = false): array
    {
        $out = [];
        if (!self::installed($this->db) || !$this->vouchersOn()) {
            return $out;
        }
        $jobs = $this->db->query("SELECT j.id, j.source_id FROM tf_service_job j JOIN tf_intake_service s ON s.id = j.source_id
                                   WHERE j.tour_id = ? AND j.source = 'service' AND s.meal_id IS NOT NULL AND j.status NOT IN ('cancelled','not_needed') AND j.archived_at IS NULL ORDER BY j.day_no, j.seq, j.id", [$tourId])->getResultArray();
        foreach ($jobs as $jr) {
            $jobId = (int) $jr['id'];
            try {
                // a voucher of this meal line left on a desk line the amendment rebuilt: it follows the line
                $orphan = $this->db->query("SELECT v.id FROM tf_meal_voucher v LEFT JOIN tf_service_job j ON j.id = v.job_id
                                             WHERE v.tour_id = ? AND v.service_id = ? AND v.state = 'issued' AND v.job_id <> ? AND (j.id IS NULL OR j.source_id <> v.service_id) ORDER BY v.id DESC LIMIT 1", [$tourId, (int) $jr['source_id'], $jobId])->getRowArray();
                if ($orphan && !$this->db->query("SELECT id FROM tf_meal_voucher WHERE job_id = ? AND state = 'issued'", [$jobId])->getRowArray()) {
                    $this->db->query('UPDATE tf_meal_voucher SET job_id = ? WHERE id = ?', [$jobId, (int) $orphan['id']]);
                }
                $b = $this->booking($jobId);
                $rest = $b['meal']['restaurant'] ?? 'the restaurant';
                $v = $b['live'];
                if ($v !== null && $b['stale']) {
                    $this->cancelVoucher($tour, (int) $v['id'], 'The meal changed on the amendment (restaurant, people, day or time) — replaced by a fresh voucher.');
                    $v = null;
                    $b = $this->booking($jobId);
                }
                if ($v !== null && in_array((string) $v['mail_status'], ['sent', 'other'], true)) {
                    $out[] = ['job_id' => $jobId, 'restaurant' => $rest, 'voucher_no' => $v['voucher_no'], 'status' => 'kept', 'to' => $v['to_email'], 'why' => null];
                    continue;
                }
                if ($v === null) {
                    if ($b['why'] !== null) {
                        $out[] = ['job_id' => $jobId, 'restaurant' => $rest, 'voucher_no' => null, 'status' => 'not_issued', 'to' => $b['to'], 'why' => $b['why']];
                        $tour->event((int) $b['job']['intake_id'], $tourId, 'service', $jobId, 'meal_voucher_not_issued', null, null, $b['why']);
                        continue;
                    }
                    $v = $this->issueVoucher($tour, $jobId, null);
                }
                // tf-autosend-r1: the voucher is issued either way; it goes out by itself only where automatic
                // sending is switched on. Held, it waits on the Activities desk with its reason and can be sent there.
                if ($automatic && !AutoSend::on($this->db)) {
                    $out[] = ['job_id' => $jobId, 'restaurant' => $rest, 'voucher_no' => $v['voucher_no'], 'status' => 'not_sent', 'to' => $v['to_email'] ?? null, 'why' => AutoSend::HELD];
                    continue;
                }
                $r = $this->sendVoucher($tour, (int) $v['id'], [], $sender, $render);
                $out[] = ['job_id' => $jobId, 'restaurant' => $rest, 'voucher_no' => $v['voucher_no'], 'status' => $r['ok'] ? 'sent' : 'not_sent', 'to' => $r['voucher']['to_email'] ?? null, 'why' => $r['error']];
            } catch (\Throwable $e) {
                $out[] = ['job_id' => $jobId, 'restaurant' => 'a restaurant', 'voucher_no' => null, 'status' => 'not_issued', 'to' => null, 'why' => $e->getMessage()];
            }
        }
        return $out;
    }

    /** A voucher is never edited or removed: it is cancelled with the reason, and a fresh one issued. */
    public function cancelVoucher(TourService $tour, int $voucherId, ?string $why): array
    {
        $v = $this->vouchersOn() ? $this->db->query('SELECT v.*, t.intake_id FROM tf_meal_voucher v JOIN tf_tour t ON t.id = v.tour_id WHERE v.id = ?', [$voucherId])->getRowArray() : null;
        if (!$v) {
            throw new \RuntimeException('Voucher not found.');
        }
        if ((string) $v['state'] === 'cancelled') {
            throw new \RuntimeException('Voucher ' . $v['voucher_no'] . ' is cancelled already.');
        }
        $why = self::cut($why, 400);
        if ($why === null) {
            throw new \InvalidArgumentException('Say why voucher ' . $v['voucher_no'] . ' is cancelled; it stays on the trail.');
        }
        $this->db->query("UPDATE tf_meal_voucher SET state = 'cancelled', cancelled_at = ?, cancelled_by = ?, cancel_note = ? WHERE id = ?", [$this->now(), $this->user, $why, $voucherId]);
        $tour->event((int) $v['intake_id'], (int) $v['tour_id'], 'service', (int) $v['job_id'], 'meal_voucher_cancelled', 'issued', 'cancelled',
            $v['voucher_no'] . ' (' . $v['restaurant'] . ', LKR ' . number_format((float) $v['amount_lkr'], 2) . ') cancelled — ' . $why
            . (in_array((string) $v['mail_status'], ['sent', 'other'], true) ? ' It had gone to the restaurant: tell them.' : ''));
        return $v;
    }

    /** The company's logo, as the quotation draws it (FCPATH/assets/images/Logo-new.png), when the file is there. */
    public static function logoFile(): ?string
    {
        $f = defined('FCPATH') ? FCPATH . 'assets/images/Logo-new.png' : null;
        return $f && is_file($f) ? $f : null;
    }

    /**
     * The voucher as HTML (Views/pages/tour/meal_voucher.php): $mode page (to print), email (the email body, plain tables)
     * or pdf (the same for the PDF). Without the view layer (the test runner) a plain rendering of the same content.
     */
    public static function voucherHtml(array $v, string $mode, ?string $message, array $sender, ?string $user = null): string
    {
        $logo = null;
        try {
            $f = defined('FCPATH') ? FCPATH . 'assets/images/email/logo.png' : null;
            $logo = $f && is_file($f) && function_exists('base_url') ? base_url('assets/images/email/logo.png') : null;
        } catch (\Throwable $e) {
            $logo = null;
        }
        if (function_exists('view')) {
            return (string) view('pages/tour/meal_voucher', ['voucher' => $v, 'mode' => $mode, 'message' => $message, 'sender' => $sender, 'logo' => $logo, 'logo_file' => self::logoFile(), 'user' => $user]);
        }
        return '<div>' . nl2br(htmlspecialchars((string) $message)) . '<table><tr><td>Voucher number</td><td>' . htmlspecialchars((string) $v['voucher_no']) . '</td></tr><tr><td>Amount</td><td>LKR '
            . number_format((float) $v['amount_lkr'], 2) . '</td></tr></table></div>';
    }

    /**
     * The voucher as a PDF to go with the email (the logo drawn in), written to a file: [path, name] — or null when the PDF
     * library is not on this server (the email then carries the voucher in its body alone, as it always does).
     */
    public static function voucherPdf(array $v, ?string $message, array $sender, ?string $user = null): ?array
    {
        if (!class_exists('\TCPDF')) {
            $lib = (defined('ROOTPATH') ? ROOTPATH : (defined('APPPATH') ? dirname(APPPATH) . DIRECTORY_SEPARATOR : '')) . 'vendor' . DIRECTORY_SEPARATOR . 'tecnickcom' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'tcpdf.php';
            if (is_file($lib)) {
                require_once $lib;
            }
        }
        if (!class_exists('\TCPDF') || !function_exists('view')) {
            return null;
        }
        try {
            $html = self::voucherHtml($v, 'pdf', $message, $sender, $user);
            $doc  = new \TCPDF(defined('PDF_PAGE_ORIENTATION') ? PDF_PAGE_ORIENTATION : 'P', defined('PDF_UNIT') ? PDF_UNIT : 'mm', defined('PDF_PAGE_FORMAT') ? PDF_PAGE_FORMAT : 'A4', true, 'UTF-8', false);
            $doc->SetCreator('Avenir Holidays');
            $doc->SetAuthor('Avenir Holidays');
            $doc->SetTitle('Booking voucher ' . $v['voucher_no']);
            $doc->setPrintHeader(false);
            $doc->setPrintFooter(false);
            $doc->SetMargins(14, 14, 14);
            $doc->SetAutoPageBreak(true, 14);
            $doc->AddPage();
            $doc->writeHTML($html, true, false, true, false, '');
            $dir = (defined('WRITEPATH') ? WRITEPATH : sys_get_temp_dir() . '/') . 'tf_vouchers';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $name = preg_replace('/[^A-Za-z0-9\-]+/', '-', $v['voucher_no'] . '-' . $v['restaurant']) . '.pdf';
            $path = $dir . DIRECTORY_SEPARATOR . $name;
            file_put_contents($path, $doc->Output($name, 'S'));
            return is_file($path) ? ['path' => $path, 'name' => $name] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** One voucher with what its page and its email need: the tour, the restaurant and its account and contact, the meal, the sum. */
    public function voucher(int $voucherId): array
    {
        $v = $this->vouchersOn() ? $this->db->query(
            'SELECT v.*, t.tour_no, t.tour_name, t.agent_name, t.market_code, t.arrival_date, t.departure_date, t.pax_total, t.handler_name, t.intake_id FROM tf_meal_voucher v JOIN tf_tour t ON t.id = v.tour_id WHERE v.id = ?',
            [$voucherId]
        )->getRowArray() : null;
        if (!$v) {
            throw new \RuntimeException('Voucher not found.');
        }
        $v['supplier'] = $this->supplier(self::intOrNull($v['supplier_id']));
        $m = $this->forPricing((int) $v['meal_id']);
        $v['address'] = ($m['address'] ?? null) ?: ($v['supplier']['address'] ?? null);
        $v['description'] = $m['description'] ?? null;
        $v['type_name'] = self::TYPES[$v['meal_type']] ?? 'Meal';
        // the client (the first passenger named on the inquiry) and the guide or driver allocated by Transport, when there is one
        $v['client'] = null;
        $v['guide']  = null;
        try {
            $p = $this->db->query("SELECT full_name FROM tf_intake_pax WHERE intake_id = ? AND TRIM(COALESCE(full_name, '')) <> '' ORDER BY sort_order, id LIMIT 1", [(int) $v['intake_id']])->getRowArray();
            $v['client'] = $p ? trim((string) $p['full_name']) : null;
            $crew = $this->db->query("SELECT c.kind, c.person_name, c.person_phone FROM tf_transport_crew c JOIN tf_transport_job j ON j.id = c.job_id
                                       WHERE j.tour_id = ? AND c.replaced_by IS NULL AND TRIM(COALESCE(c.person_name, '')) <> '' ORDER BY c.kind = 'guide' DESC, c.id", [(int) $v['tour_id']])->getResultArray();
            $g = array_values(array_filter($crew, static fn ($x) => (string) $x['kind'] === 'guide')) ?: $crew;
            $v['guide'] = $g ? implode('; ', array_map(static fn ($x) => trim($x['person_name'] . (trim((string) $x['person_phone']) !== '' ? ' — ' . trim((string) $x['person_phone']) : '') . ((string) $x['kind'] !== 'guide' ? ' (driver)' : '')), $g)) : null;
        } catch (\Throwable $e) {
            // a column this install lacks: the voucher goes without that line
        }
        // the company's lines as Admin → Settings keeps them (the same as on every letter and voucher)
        $v['company'] = [];
        foreach (['company_name', 'company_address', 'company_phone', 'company_email'] as $k) {
            try {
                $v['company'][substr($k, 8)] = $this->db->query('SELECT v FROM tf_meta WHERE k = ?', [$k])->getRowArray()['v'] ?? null;
            } catch (\Throwable $e) {
                $v['company'][substr($k, 8)] = null;
            }
        }
        return $v;
    }

    /** The meal an HB hotel gives on one night besides breakfast: lunch, dinner, or null for the setting's default. */
    public function setHbMeal(int $intakeId, int $dayId, ?string $meal): array
    {
        $this->need();
        $d = $this->db->query('SELECT id, day_no, meal_plan, hb_meal, hotel_name_raw FROM tf_intake_day WHERE id = ? AND intake_id = ?', [$dayId, $intakeId])->getRowArray();
        if (!$d) {
            throw new \InvalidArgumentException('That night is not on this inquiry.');
        }
        if (strtoupper(trim((string) $d['meal_plan'])) !== 'HB') {
            throw new \InvalidArgumentException('Night ' . (int) $d['day_no'] . ' is not on Half Board' . ($d['meal_plan'] ? ' (' . $d['meal_plan'] . ')' : '') . ' — the HB meal is chosen on an HB night only.');
        }
        $meal = $meal === null || $meal === '' ? null : strtolower(trim($meal));
        if ($meal !== null && !isset(self::TYPES[$meal])) {
            throw new \InvalidArgumentException('An HB hotel gives lunch or dinner besides breakfast.');
        }
        $this->db->query('UPDATE tf_intake_day SET hb_meal = ? WHERE id = ?', [$meal, $dayId]);
        $from = $d['hb_meal'] ?: $this->hbDefault() . ' (default)';
        $to   = $meal ?? $this->hbDefault() . ' (default)';
        return ['day_no' => (int) $d['day_no'], 'from' => $from, 'to' => $to, 'hotel' => $d['hotel_name_raw']];
    }

    /** What the Activities desk shows of a meal line: the restaurant, where, how to reach them, when, for whom and what it costs. */
    public function forLine(int $serviceId): ?array
    {
        if (!self::installed($this->db)) {
            return null;
        }
        $s = $this->db->query('SELECT meal_id, qty_adult, qty_child, meal_time, rate_currency, rate_adult, rate_child, tax_pct, tax_cur, amount_cur, amount_usd, meal_ok_note FROM tf_intake_service WHERE id = ?', [$serviceId])->getRowArray();
        if (!$s || empty($s['meal_id'])) {
            return null;
        }
        $m = $this->forPricing((int) $s['meal_id']);
        if ($m === null) {
            return null;
        }
        return [
            'code' => $m['code'], 'type' => $m['type_name'], 'restaurant' => $m['restaurant'], 'location' => $m['location'], 'address' => $m['address'] ?: ($m['supplier']['address'] ?? null),
            'contact' => trim(implode(' · ', array_filter([$m['contact_name'], $m['contact_phone'] ?: ($m['supplier']['phone'] ?? null), $m['contact_email'] ?: ($m['supplier']['email'] ?? null)]))),
            'supplier' => $m['supplier']['name'] ?? null, 'supplier_acc' => $m['supplier_acc'], 'cuisine' => $m['cuisine'], 'description' => $m['description'],
            'time' => $s['meal_time'], 'adults' => self::intOrNull($s['qty_adult']), 'children' => self::intOrNull($s['qty_child']),
            'currency' => $s['rate_currency'], 'rate_adult' => $s['rate_adult'] !== null ? (float) $s['rate_adult'] : null, 'rate_child' => $s['rate_child'] !== null ? (float) $s['rate_child'] : null,
            'tax_pct' => $s['tax_pct'] !== null ? (float) $s['tax_pct'] : null, 'tax' => $s['tax_cur'] !== null ? (float) $s['tax_cur'] : null,
            'total' => $s['amount_cur'] !== null ? (float) $s['amount_cur'] : null, 'total_usd' => $s['amount_usd'] !== null ? (float) $s['amount_usd'] : null, 'outside_note' => $s['meal_ok_note'],
            'standing' => $m['status_text'],
        ];
    }
}

