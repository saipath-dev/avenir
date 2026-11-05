<?php

namespace App\Libraries\HotelRates;

/**
 * Every tunable rule of the Hotel Rates module lives in hr2_meta (k / v) and
 * is edited from the panel's Settings screen — nothing about markets, tiers,
 * gala nights or check thresholds is hard-coded.
 *
 * DEFINITIONS lists the keys, their type and the value used until someone
 * changes it; the seed in hotel_rates_v2_schema.sql writes the same defaults
 * with INSERT IGNORE so a fresh install and an upgrade behave the same.
 */
final class Settings
{
    public const DEFINITIONS = [
        // ---- markets & currency ------------------------------------------------
        'default_market' => ['group' => 'Markets & currency', 'type' => 'market', 'default' => 'JP',
            'label' => 'Default market',
            'help' => 'Used when a quotation has no country, when the country is not mapped below, and when the old Hotel Rate rows are rebuilt.'],
        'default_currency' => ['group' => 'Markets & currency', 'type' => 'currency', 'default' => 'USD',
            'label' => 'Default contract currency',
            'help' => 'Pre-filled for new hotels, new plans and imports; each hotel and plan can still use its own.'],
        // ---- tiers -----------------------------------------------------------
        'fit_max_rooms' => ['group' => 'Tiers', 'type' => 'int', 'default' => 4, 'min' => 1, 'max' => 999,
            'label' => 'FIT: up to this many rooms',
            'help' => 'When a plan is marked FIT but the sheet gives no limit.'],
        'group_min_rooms' => ['group' => 'Tiers', 'type' => 'int', 'default' => 5, 'min' => 1, 'max' => 999,
            'label' => 'Group: from this many rooms',
            'help' => 'When a plan is marked Group but the sheet gives no limit.'],
        // ---- gala nights -----------------------------------------------------
        'gala_dates' => ['group' => 'Gala nights', 'type' => 'dates', 'default' => '12-24,12-31',
            'label' => 'Nights that need a gala supplement (MM-DD, comma separated)',
            'help' => 'The checks warn when a contract prices none of these nights; the editor pre-fills one gala line per night.'],
        'gala_child_pct' => ['group' => 'Gala nights', 'type' => 'number', 'default' => 50, 'min' => 0, 'max' => 100,
            'label' => 'Usual child % of the gala price',
            'help' => 'Pre-filled on new gala lines; the checks mention it when a gala has no child price.'],
        // ---- checks ----------------------------------------------------------
        'tiny_rate' => ['group' => 'Checks', 'type' => 'number', 'default' => 10, 'min' => 0,
            'label' => 'Warn when a nightly rate is below',
            'help' => 'Catches typos like 35 instead of 135 (in the plan currency).'],
        'huge_rate' => ['group' => 'Checks', 'type' => 'number', 'default' => 3000, 'min' => 1,
            'label' => 'Warn when a nightly rate is above',
            'help' => 'Villas and presidential suites may need a higher value.'],
        'change_warning_pct' => ['group' => 'Checks', 'type' => 'number', 'default' => 40, 'min' => 0, 'max' => 1000,
            'label' => 'Warn when a rate moves more than this % against the previous approved plan'],
        'expiry_warning_days' => ['group' => 'Checks', 'type' => 'int', 'default' => 90, 'min' => 1, 'max' => 730,
            'label' => 'Warn this many days before a plan expires',
            'help' => 'Also drives the "ends in … days" filter in the hotel list and the health check.'],
        'child_max_age' => ['group' => 'Checks', 'type' => 'number', 'default' => 18, 'min' => 1, 'max' => 30,
            'label' => 'Warn when a child rule goes above this age',
            'help' => 'Most sheets stop at 11.99 or 12; a few use 15 or 17.99.'],
        // ---- horizons --------------------------------------------------------
        'coverage_days' => ['group' => 'Horizons', 'type' => 'int', 'default' => 365, 'min' => 30, 'max' => 1095,
            'label' => 'Coverage chart: days ahead'],
        'check_days' => ['group' => 'Horizons', 'type' => 'int', 'default' => 120, 'min' => 7, 'max' => 365,
            'label' => 'Health check: look at tours this many days ahead'],
        // ---- incomplete work -------------------------------------------------
        'margin_alert_pct' => ['group' => 'Incomplete work', 'type' => 'number', 'default' => 40, 'min' => 0, 'max' => 1000,
            'label' => 'Flag a hotel margin above this % of the quoted amount',
            'help' => 'Contract nights only (an ad hoc rate is meant to leave a margin). A contract rate above the quoted rate is always flagged.'],
        'work_scan_minutes' => ['group' => 'Incomplete work', 'type' => 'int', 'default' => 10, 'min' => 1, 'max' => 1440,
            'label' => 'Re-scan for incomplete work at most every … minutes when a panel opens',
            'help' => 'Refresh on the Hotel Incomplete Work panel always re-scans; approvals, invoices and re-costing add their items at once.'],
        // ---- legacy ----------------------------------------------------------
        'legacy_booking_basis' => ['group' => 'Old Hotel Rate screen', 'type' => 'choice', 'default' => 'today', 'choices' => ['today' => 'a booking made today'],
            'label' => 'Rebuild the old rows as for',
            'help' => 'Tactical offers with booking windows start and stop applying as the calendar moves; run "php spark hotelrates:project" nightly.'],
        'legacy_screen' => ['group' => 'Old Hotel Rate screen', 'type' => 'choice', 'default' => 'all',
            'choices' => ['all' => 'locked for every hotel (once the migration has run)', 'migrated' => 'locked only for hotels that have an approved plan here', 'open' => 'open (not recommended)'],
            'label' => 'Old screen',
            'help' => 'Locked hotels can still be viewed there, but adding, editing, uploading and deleting rows must be done in this panel. Nothing is locked until hotelrates:migrate has copied the hotels across.'],
    ];

    /** ISO-2 country → market code used to seed hr2_market_country (editable afterwards). */
    public const COUNTRY_MARKET_SEED = [
        'JP' => 'JP',
        'CN' => 'CN', 'HK' => 'CN', 'MO' => 'CN', 'TW' => 'CN',
        'KR' => 'FE', 'SG' => 'FE', 'MY' => 'FE', 'TH' => 'FE', 'VN' => 'FE', 'ID' => 'FE', 'PH' => 'FE', 'KH' => 'FE', 'LA' => 'FE', 'MM' => 'FE', 'BN' => 'FE', 'MN' => 'FE',
        'IN' => 'IN',
        'PK' => 'SA', 'BD' => 'SA', 'NP' => 'SA', 'MV' => 'SA', 'BT' => 'SA', 'AF' => 'SA',
        'AE' => 'ME', 'SA' => 'ME', 'QA' => 'ME', 'KW' => 'ME', 'BH' => 'ME', 'OM' => 'ME', 'JO' => 'ME', 'LB' => 'ME', 'IQ' => 'ME', 'IR' => 'ME', 'IL' => 'ME', 'EG' => 'ME', 'SY' => 'ME', 'YE' => 'ME',
        'GB' => 'UK', 'NL' => 'NL', 'ES' => 'ES',
        'RU' => 'RU', 'BY' => 'RU', 'KZ' => 'RU', 'UZ' => 'RU', 'KG' => 'RU', 'TJ' => 'RU', 'TM' => 'RU', 'AM' => 'RU', 'AZ' => 'RU', 'GE' => 'RU',
        'PL' => 'EE', 'CZ' => 'EE', 'SK' => 'EE', 'HU' => 'EE', 'RO' => 'EE', 'BG' => 'EE', 'RS' => 'EE', 'HR' => 'EE', 'SI' => 'EE', 'BA' => 'EE', 'MK' => 'EE', 'AL' => 'EE', 'ME' => 'EE', 'LT' => 'EE', 'LV' => 'EE', 'EE' => 'EE', 'MD' => 'EE', 'UA' => 'EE',
        'DE' => 'EU', 'FR' => 'EU', 'IT' => 'EU', 'AT' => 'EU', 'CH' => 'EU', 'BE' => 'EU', 'LU' => 'EU', 'DK' => 'EU', 'SE' => 'EU', 'NO' => 'EU', 'FI' => 'EU', 'IS' => 'EU', 'IE' => 'EU', 'PT' => 'EU', 'GR' => 'EU', 'CY' => 'EU', 'MT' => 'EU', 'TR' => 'EU', 'LI' => 'EU', 'MC' => 'EU', 'AD' => 'EU', 'SM' => 'EU',
        'AU' => 'AU', 'NZ' => 'AU',
        'US' => 'US', 'CA' => 'US',
        'LK' => 'DOM',
    ];

    /** @var object */
    private $db;
    private ?array $values = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /** @return array<string, string> key => default (as stored) */
    public static function defaults(): array
    {
        $out = [];
        foreach (self::DEFINITIONS as $k => $d) {
            $out[$k] = (string) $d['default'];
        }
        return $out;
    }

    /** Raw stored values (defaults filled in). */
    public function values(): array
    {
        if ($this->values === null) {
            $this->values = self::defaults();
            $res = $this->db->query('SELECT k, v FROM hr2_meta');
            if ($res !== false && $res !== true) {
                foreach ($res->getResultArray() as $r) {
                    if (isset(self::DEFINITIONS[$r['k']]) && $r['v'] !== null && $r['v'] !== '') {
                        $this->values[$r['k']] = (string) $r['v'];
                    }
                }
            }
        }
        return $this->values;
    }

    /** Typed value of one setting. */
    public function get(string $key)
    {
        $v = $this->values()[$key] ?? (self::DEFINITIONS[$key]['default'] ?? null);
        return self::cast($key, $v);
    }

    public static function cast(string $key, $v)
    {
        $type = self::DEFINITIONS[$key]['type'] ?? 'string';
        switch ($type) {
            case 'int':
                return (int) $v;
            case 'number':
                return (float) $v;
            case 'dates':
                return self::parseDates((string) $v);
            case 'market':
            case 'currency':
                return strtoupper(trim((string) $v));
            default:
                return (string) $v;
        }
    }

    /** "12-24, 31/12" → ['12-24', '12-31'] (MM-DD; a day-first "24/12" is understood too). */
    public static function parseDates(string $s): array
    {
        $out = [];
        foreach (preg_split('/[,;\s]+/', trim($s)) as $p) {
            if ($p === '') {
                continue;
            }
            if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})$/', $p, $m)) {
                $a = (int) $m[1];
                $b = (int) $m[2];
                if ($a > 12 && $b <= 12) {
                    [$a, $b] = [$b, $a];   // written day-first
                }
                if ($a < 1 || $a > 12 || $b < 1 || $b > 31) {
                    continue;
                }
                $out[] = sprintf('%02d-%02d', $a, $b);
            }
        }
        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }

    /** Definitions + current values, grouped, for the Settings screen. */
    public function describe(array $markets = []): array
    {
        $vals = $this->values();
        $out = [];
        foreach (self::DEFINITIONS as $k => $d) {
            $row = $d + ['key' => $k, 'value' => $vals[$k]];
            if ($d['type'] === 'market') {
                $row['choices'] = $markets;
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Validate and store. Unknown keys are ignored.
     * @return array{saved:string[], changed:array<string,array{0:string,1:string}>, errors:array<string,string>}
     *         changed = key => [old, new] for the values that actually differ
     */
    public function save(array $input): array
    {
        $saved = [];
        $errors = [];
        $changed = [];
        $before = $this->values();
        foreach ($input as $k => $v) {
            if (!isset(self::DEFINITIONS[$k])) {
                continue;
            }
            $d = self::DEFINITIONS[$k];
            $v = is_string($v) ? trim($v) : $v;
            $store = null;
            switch ($d['type']) {
                case 'int':
                case 'number':
                    if ($v === '' || $v === null || !is_numeric($v)) {
                        $errors[$k] = 'Enter a number.';
                        continue 2;
                    }
                    $n = $d['type'] === 'int' ? (int) $v : (float) $v;
                    if (isset($d['min']) && $n < $d['min']) {
                        $errors[$k] = "Must be at least {$d['min']}.";
                        continue 2;
                    }
                    if (isset($d['max']) && $n > $d['max']) {
                        $errors[$k] = "Must be at most {$d['max']}.";
                        continue 2;
                    }
                    $store = (string) $n;
                    break;
                case 'dates':
                    $dates = self::parseDates((string) $v);
                    if (!$dates) {
                        $errors[$k] = 'Give at least one date as MM-DD (e.g. 12-24,12-31).';
                        continue 2;
                    }
                    $store = implode(',', $dates);
                    break;
                case 'market':
                    $store = strtoupper((string) $v);
                    if (!preg_match('/^[A-Z]{2,8}$/', $store)) {
                        $errors[$k] = 'Pick a market.';
                        continue 2;
                    }
                    break;
                case 'currency':
                    $store = strtoupper(trim((string) $v));
                    if (!preg_match('/^[A-Z]{3}$/', $store)) {
                        $errors[$k] = 'Use a 3-letter currency code (USD, LKR, EUR).';
                        continue 2;
                    }
                    break;
                case 'choice':
                    if (!isset($d['choices'][(string) $v])) {
                        $errors[$k] = 'Pick one of the options.';
                        continue 2;
                    }
                    $store = (string) $v;
                    break;
                default:
                    $store = (string) $v;
            }
            $this->db->query('INSERT INTO hr2_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $store]);
            $saved[] = $k;
            if (($before[$k] ?? null) !== $store) {
                $changed[$k] = [$before[$k] ?? null, $store];
            }
        }
        $this->values = null;
        if ($this->get('fit_max_rooms') >= $this->get('group_min_rooms')) {
            // not fatal — the engine still works — but tell the user
            $errors['group_min_rooms'] = $errors['group_min_rooms'] ?? 'Group should start above the FIT limit (a booking of ' . $this->get('group_min_rooms') . ' rooms matches both tiers).';
        }
        return ['saved' => $saved, 'changed' => $changed, 'errors' => $errors];
    }

    // ---------------------------------------------------------------------
    //  Country → market
    // ---------------------------------------------------------------------

    /**
     * Seed hr2_market_country from the system's country table (ISO codes the
     * quotation form stores in consignee.country). Existing rows are kept.
     * @return int rows added
     */
    public function seedMarketCountries(): int
    {
        $has = $this->db->query("SHOW TABLES LIKE 'country'");
        $rows = ($has === false || $has === true) ? [] : $has->getResultArray();
        if (!$rows) {
            return 0;
        }
        $countries = $this->db->query('SELECT iso, nicename FROM country ORDER BY nicename')->getResultArray();
        $n = 0;
        foreach ($countries as $c) {
            $iso = strtoupper(trim((string) $c['iso']));
            if ($iso === '') {
                continue;
            }
            $market = self::COUNTRY_MARKET_SEED[$iso] ?? 'OTH';
            $res = $this->db->query('INSERT IGNORE INTO hr2_market_country (country_iso, country_name, market_code) VALUES (?, ?, ?)', [$iso, (string) $c['nicename'], $market]);
            if ($res !== false && method_exists($this->db, 'affectedRows') && $this->db->affectedRows() > 0) {
                $n++;
            }
        }
        return $n;
    }

    /** Seed the country → market table when it is empty (covers installs done by pasting the SQL into phpMyAdmin). */
    public function ensureMarketCountriesSeeded(): int
    {
        $r = $this->db->query('SELECT COUNT(*) AS n FROM hr2_market_country');
        $n = ($r === false || $r === true) ? 0 : (int) ($r->getRowArray()['n'] ?? 0);
        return $n === 0 ? $this->seedMarketCountries() : 0;
    }

    /** @return array<int, array{country_iso:string, country_name:string, market_code:string, note:?string}> */
    public function marketCountries(): array
    {
        $res = $this->db->query('SELECT country_iso, country_name, market_code, note FROM hr2_market_country ORDER BY country_name');
        return ($res === false || $res === true) ? [] : $res->getResultArray();
    }

    /** Upsert a list of {country_iso, market_code, country_name?, note?}. */
    public function saveMarketCountries(array $rows, array $validMarkets, ?string $user = null): array
    {
        $saved = 0;
        $errors = [];
        foreach ($rows as $r) {
            $iso = strtoupper(trim((string) ($r['country_iso'] ?? '')));
            $market = strtoupper(trim((string) ($r['market_code'] ?? '')));
            if (!preg_match('/^[A-Z]{2}$/', $iso)) {
                $errors[] = "Country code '{$iso}' is not a 2-letter ISO code.";
                continue;
            }
            if ($market === '' || !isset($validMarkets[$market])) {
                $errors[] = "{$iso}: market '{$market}' is not in the market list.";
                continue;
            }
            $name = trim((string) ($r['country_name'] ?? ''));
            if ($name === '') {
                $cur = $this->db->query('SELECT country_name FROM hr2_market_country WHERE country_iso = ?', [$iso])->getRowArray();
                $name = $cur['country_name'] ?? $iso;
            }
            $note = isset($r['note']) ? trim((string) $r['note']) : null;
            $this->db->query(
                'INSERT INTO hr2_market_country (country_iso, country_name, market_code, note, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE country_name = VALUES(country_name), market_code = VALUES(market_code), note = VALUES(note), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
                [$iso, $name, $market, $note === '' ? null : $note, $user, date('Y-m-d H:i:s')]
            );
            $saved++;
        }
        return ['saved' => $saved, 'errors' => $errors];
    }

    /** Market for a quotation country (ISO-2 as stored in consignee.country); default market when unmapped. */
    public function marketForCountry(?string $iso): string
    {
        $iso = strtoupper(trim((string) $iso));
        if ($iso !== '' && strlen($iso) === 2) {
            $r = $this->db->query('SELECT market_code FROM hr2_market_country WHERE country_iso = ?', [$iso]);
            $row = ($r === false || $r === true) ? null : $r->getRowArray();
            if ($row && $row['market_code'] !== '') {
                return strtoupper($row['market_code']);
            }
        }
        return $this->get('default_market') ?: 'ALL';
    }
}
