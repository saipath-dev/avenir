<?php

namespace App\Libraries\TourFlow;

/**
 * tf-safarirate-r1 (27 Nov 2026, Shantha): the Safari Rate Master — Tour Desk → Masters → Safari Rate Master
 * (master_files/entrance_master/safari, under the Places & Entrance Fees privilege).
 *
 * One record per safari park (Minneriya, Yala), tied to the park's place in the Place to Visit / Entrance Fee Master:
 *
 *   entrance   USD per visitor from Gazette 2292/11: foreign adult (13+), foreign child (6–12), visiting SAARC adult and
 *              child, each for the tourist season and the regular period; under 6 free (but takes a jeep seat)
 *   gate       per jeep: the foreign-group service charge (USD 10) and the vehicle entry (LKR 300); VAT (18%) on the gate
 *              subtotal = guest tickets + the per-jeep gate charges
 *   jeep       the operator's hire per jeep (LKR), up to six guests a jeep; its source, contract dates and whether the
 *              supplier's agreement or invoice has confirmed it ("Awaiting supplier evidence" until then)
 *   quotation  the selling price per adult (USD 75) and per child (left open for Management), and the fixed quotation
 *              exchange rate (USD 1 = LKR 320) — for safari quotation costing only; payments keep the Daily USD Rate
 *
 * Internal cost of one safari = (tickets + jeeps × (service + vehicle entry)) × (1 + VAT) + jeeps × hire, all at the fixed
 * rate, rounded once at the end. The agent sees one all-inclusive line ("Minneriya Jeep Safari — 2 adults × USD 75 =
 * USD 150"); the breakdown stays internal. A safari whose cost is above its selling price is flagged for Management.
 */
final class SafariRates
{
    public const URL   = 'master_files/entrance_master/safari';
    public const TABLE = 'tf_safari_rate';

    public const CATS = [
        'adult'       => 'Foreign adult · age 13+',
        'child'       => 'Foreign child · age 6–12',
        'saarc_adult' => 'Visiting SAARC adult · age 13+',
        'saarc_child' => 'Visiting SAARC child · age 6–12',
    ];
    public const SEASONS  = ['tourist' => 'Tourist season', 'regular' => 'Regular period'];
    public const EVIDENCE = ['awaiting' => 'Awaiting supplier evidence', 'confirmed' => 'Confirmed by supplier agreement / invoice'];

    /** The two parks set up on first use, with the figures of the Avenir safari survey (27 Nov 2026). */
    public const SEED = [
        'minneriya' => ['match' => 'minneriya', 'label' => 'Minneriya Jeep Safari', 'park' => 'Minneriya National Park', 'operator' => 'Lucky Jeep Safari', 'hire_lkr' => 8000],
        'yala'      => ['match' => 'yala',      'label' => 'Yala Jeep Safari',      'park' => 'Yala National Park',      'operator' => 'Karu Jeep Safari',  'hire_lkr' => 13000],
    ];
    public const SEED_SOURCE = 'Avenir safari survey, 27 Nov 2026 (reported hire rate)';
    public const SEED_LEGACY = 'Places & Entrance Fees still holds an entrance of LKR 12,500 per guest (LKR 14,500 for one guest alone, in its note) beside the jeep per vehicle. That is above the gazette gate fee (about LKR 10,100–13,600 per guest at LKR 320) and may already include the operator\'s ticket-buying charge or part of the jeep. Quotations now cost this park only from the Safari Rate Master, so nothing is counted twice there. Payment slips still read Places & Entrance Fees until the invoice and park receipt are checked.';

    private const MONEY = ['ts_adult', 'ts_child', 'ts_saarc_adult', 'ts_saarc_child', 'rp_adult', 'rp_child', 'rp_saarc_adult', 'rp_saarc_child', 'service_usd', 'vehicle_lkr', 'vat_pct', 'hire_lkr', 'sell_adult_usd', 'sell_child_usd', 'fx_lkr'];

    private static array $cache = [];
    private static array $ready = [];

    // =====================================================================
    //  table
    // =====================================================================

    public static function ensure($db): bool
    {
        $k = is_object($db) ? spl_object_id($db) : 0;
        if (isset(self::$ready[$k])) {
            return self::$ready[$k];
        }
        try {
            $db->query("CREATE TABLE IF NOT EXISTS `tf_safari_rate` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `slug` VARCHAR(40) NOT NULL,
                `place_id` INT UNSIGNED NULL,
                `label` VARCHAR(120) NOT NULL,
                `park` VARCHAR(120) NOT NULL,
                `category` VARCHAR(40) NOT NULL DEFAULT 'Category I',
                `ts_adult` DECIMAL(8,2) NULL, `ts_child` DECIMAL(8,2) NULL, `ts_saarc_adult` DECIMAL(8,2) NULL, `ts_saarc_child` DECIMAL(8,2) NULL,
                `rp_adult` DECIMAL(8,2) NULL, `rp_child` DECIMAL(8,2) NULL, `rp_saarc_adult` DECIMAL(8,2) NULL, `rp_saarc_child` DECIMAL(8,2) NULL,
                `free_under_age` DECIMAL(4,1) NOT NULL DEFAULT 6.0,
                `child_to_age` DECIMAL(4,1) NOT NULL DEFAULT 12.0,
                `service_usd` DECIMAL(8,2) NOT NULL DEFAULT 10.00,
                `vehicle_lkr` DECIMAL(10,2) NOT NULL DEFAULT 300.00,
                `vat_pct` DECIMAL(5,2) NOT NULL DEFAULT 18.00,
                `operator` VARCHAR(120) NULL,
                `hire_lkr` DECIMAL(10,2) NULL,
                `seats` TINYINT UNSIGNED NOT NULL DEFAULT 6,
                `source` VARCHAR(255) NULL,
                `valid_from` DATE NULL,
                `valid_to` DATE NULL,
                `evidence` VARCHAR(20) NOT NULL DEFAULT 'awaiting',
                `season` VARCHAR(20) NOT NULL DEFAULT 'tourist',
                `sell_adult_usd` DECIMAL(8,2) NULL,
                `sell_child_usd` DECIMAL(8,2) NULL,
                `fx_lkr` DECIMAL(8,2) NOT NULL DEFAULT 320.00,
                `legacy_note` TEXT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `updated_by` VARCHAR(100) NULL,
                `updated_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`), UNIQUE KEY `u_slug` (`slug`), KEY `k_place` (`place_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->query("CREATE TABLE IF NOT EXISTS `tf_safari_rate_log` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `rate_id` INT UNSIGNED NOT NULL,
                `summary` VARCHAR(500) NOT NULL,
                `changes` TEXT NULL,
                `by_user` VARCHAR(100) NULL,
                `at` DATETIME NOT NULL,
                PRIMARY KEY (`id`), KEY `k_rate` (`rate_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            self::seed($db);
            self::$ready[$k] = true;
        } catch (\Throwable $e) {
            log_message('error', 'SafariRates::ensure: ' . $e->getMessage());
            self::$ready[$k] = false;
        }
        return self::$ready[$k];
    }

    private static function seed($db): void
    {
        $now = date('Y-m-d H:i:s');
        foreach (self::SEED as $slug => $s) {
            $have = $db->query('SELECT id, place_id FROM tf_safari_rate WHERE slug = ?', [$slug])->getRowArray();
            if ($have) {
                if ($have['place_id'] === null) {
                    // the park was not in Places & Entrance Fees when the record was set up: tie it as soon as it is
                    $pl = $db->query('SELECT id FROM tf_place WHERE jeep = 1 AND LOWER(name) LIKE ? ORDER BY id LIMIT 1', ['%' . $s['match'] . '%'])->getRowArray();
                    if ($pl) {
                        $db->query('UPDATE tf_safari_rate SET place_id = ? WHERE id = ? AND place_id IS NULL', [(int) $pl['id'], (int) $have['id']]);
                        self::log($db, (int) $have['id'], 'Tied to the safari park of Places & Entrance Fees (place ' . (int) $pl['id'] . ')', [], 'installer');
                        self::syncPlace($db, (int) $pl['id'], 6, 6.0, 12.0);
                    }
                }
                continue;
            }
            $place = null;
            try {
                $place = $db->query('SELECT id FROM tf_place WHERE jeep = 1 AND LOWER(name) LIKE ? ORDER BY id LIMIT 1', ['%' . $s['match'] . '%'])->getRowArray();
            } catch (\Throwable $e) {
                $place = null;
            }
            $db->query('INSERT INTO tf_safari_rate (slug, place_id, label, park, category, ts_adult, ts_child, ts_saarc_adult, ts_saarc_child, rp_adult, rp_child, rp_saarc_adult, rp_saarc_child,
                    free_under_age, child_to_age, service_usd, vehicle_lkr, vat_pct, operator, hire_lkr, seats, source, evidence, season, sell_adult_usd, sell_child_usd, fx_lkr, legacy_note, active, updated_by, updated_at, created_at)
                VALUES (?, ?, ?, ?, ?, 25, 15, 20, 10, 20, 10, 15, 8, 6, 12, 10, 300, 18, ?, ?, 6, ?, ?, ?, 75, NULL, 320, ?, 1, ?, ?, ?)', [
                $slug, $place ? (int) $place['id'] : null, $s['label'], $s['park'], 'Category I', $s['operator'], $s['hire_lkr'], self::SEED_SOURCE, 'awaiting', 'tourist',
                self::SEED_LEGACY, 'installer', $now, $now,
            ]);
            $id = (int) $db->insertID();
            self::log($db, $id, 'Set up from the Avenir safari survey (27 Nov 2026) and Gazette 2292/11', [], 'installer');
            if ($place) {
                self::syncPlace($db, (int) $place['id'], 6, 6.0, 12.0);
            }
        }
    }

    /** The park's jeep seats and child ages, written to its place too — the inquiry's jeep count and children follow them. */
    private static function syncPlace($db, int $placeId, int $seats, float $free, float $childTo): void
    {
        try {
            if ($db->query("SHOW COLUMNS FROM tf_place LIKE 'jeep_seats'")->getResultArray()) {
                $db->query('UPDATE tf_place SET jeep_seats = ?, child_age_from = ?, child_age_to = ? WHERE id = ?', [$seats, $free, $childTo, $placeId]);
            }
        } catch (\Throwable $e) {
        }
    }

    private static function log($db, int $id, string $summary, array $changes, string $by): void
    {
        try {
            $db->query('INSERT INTO tf_safari_rate_log (rate_id, summary, changes, by_user, at) VALUES (?, ?, ?, ?, ?)',
                [$id, mb_substr($summary, 0, 500), $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null, $by, date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
        }
    }

    public static function forget(): void
    {
        self::$cache = [];
    }

    // =====================================================================
    //  reading
    // =====================================================================

    private static function row(array $r): array
    {
        foreach (self::MONEY as $m) {
            $r[$m] = $r[$m] === null || $r[$m] === '' ? null : (float) $r[$m];
        }
        foreach (['id', 'place_id', 'seats', 'active'] as $i) {
            $r[$i] = $r[$i] === null ? null : (int) $r[$i];
        }
        $r['free_under_age'] = (float) $r['free_under_age'];
        $r['child_to_age']   = (float) $r['child_to_age'];
        return $r;
    }

    /** Every safari park record, Minneriya first. */
    public static function all($db): array
    {
        if (!self::ensure($db)) {
            return [];
        }
        return array_map([self::class, 'row'], $db->query("SELECT * FROM tf_safari_rate ORDER BY CASE slug WHEN 'minneriya' THEN 0 WHEN 'yala' THEN 1 ELSE 2 END, id")->getResultArray());
    }

    public static function get($db, int $id): ?array
    {
        if (!self::ensure($db)) {
            return null;
        }
        $r = $db->query('SELECT * FROM tf_safari_rate WHERE id = ?', [$id])->getRowArray();
        return $r ? self::row($r) : null;
    }

    /** The active record of a park's place, or null — a park without one is costed by Places & Entrance Fees as before. */
    public static function forPlace($db, $placeId): ?array
    {
        $placeId = (int) $placeId;
        if ($placeId <= 0) {
            return null;
        }
        $k = (is_object($db) ? spl_object_id($db) : 0) . ':' . $placeId;
        if (!array_key_exists($k, self::$cache)) {
            $r = null;
            if (self::ensure($db)) {
                try {
                    $r = $db->query('SELECT * FROM tf_safari_rate WHERE place_id = ? AND active = 1 ORDER BY id LIMIT 1', [$placeId])->getRowArray();
                } catch (\Throwable $e) {
                    $r = null;
                }
            }
            self::$cache[$k] = $r ? self::row($r) : null;
        }
        return self::$cache[$k];
    }

    public static function history($db, int $id): array
    {
        if (!self::ensure($db)) {
            return [];
        }
        return $db->query('SELECT summary, changes, by_user, at FROM tf_safari_rate_log WHERE rate_id = ? ORDER BY id DESC LIMIT 30', [$id])->getResultArray();
    }

    // =====================================================================
    //  arithmetic
    // =====================================================================

    /** The ticket prices (USD per visitor) of a season. */
    public static function tickets(array $r, ?string $season = null): array
    {
        $p = ($season ?? $r['season']) === 'regular' ? 'rp_' : 'ts_';
        return ['adult' => $r[$p . 'adult'], 'child' => $r[$p . 'child'], 'saarc_adult' => $r[$p . 'saarc_adult'], 'saarc_child' => $r[$p . 'saarc_child']];
    }

    /** What is missing before a quotation can be costed from the record (empty = ready). */
    public static function gaps(array $r, ?string $season = null): array
    {
        $t = self::tickets($r, $season);
        $g = [];
        if ($t['adult'] === null) {
            $g[] = 'the foreign adult entrance for the ' . strtolower(self::SEASONS[$season ?? $r['season']] ?? 'season');
        }
        if ($r['hire_lkr'] === null) {
            $g[] = 'the jeep hire per jeep';
        }
        if (($r['fx_lkr'] ?? 0) <= 0) {
            $g[] = 'the quotation exchange rate (LKR per USD)';
        }
        if ($r['sell_adult_usd'] === null) {
            $g[] = 'the selling price per adult';
        }
        return $g;
    }

    /**
     * The figures a quotation line is costed from, per head and per jeep, at the fixed rate (rounded to the cent, as the
     * line's columns hold them): adult and child entrance incl. VAT; per jeep the gate charges incl. VAT plus the hire.
     */
    public static function units(array $r, ?string $season = null): array
    {
        $t    = self::tickets($r, $season);
        $vat  = 1 + (float) $r['vat_pct'] / 100;
        $fx   = (float) $r['fx_lkr'];
        $gate = ((float) $r['service_usd'] + ($fx > 0 ? (float) $r['vehicle_lkr'] / $fx : 0)) * $vat;
        $hire = $fx > 0 && $r['hire_lkr'] !== null ? (float) $r['hire_lkr'] / $fx : null;
        return [
            'ticket_adult' => $t['adult'], 'ticket_child' => $t['child'],
            'adult_usd' => $t['adult'] !== null ? round($t['adult'] * $vat, 2) : null,
            'child_usd' => $t['child'] !== null ? round($t['child'] * $vat, 2) : null,
            'gate_per_jeep_usd' => round($gate, 4),
            'hire_usd' => $hire !== null ? round($hire, 4) : null,
            'per_jeep_usd' => $hire !== null ? round($gate + $hire, 2) : null,
        ];
    }

    public static function jeeps(array $r, int $guests): int
    {
        $s = max(1, (int) $r['seats']);
        return $guests > 0 ? (int) ceil($guests / $s) : 0;
    }

    /**
     * The internal cost of one safari for a party, exactly: guest tickets + per-jeep gate charges, then VAT on that gate
     * subtotal, then the hire per jeep; rounded once at the end. Children by age: under the free age nothing (a seat all the
     * same), within the child ages the child ticket, older the adult ticket; a child of unknown age the child ticket.
     */
    public static function cost(array $r, int $adults, array $childAges = [], int $saarcAdults = 0, ?string $season = null): array
    {
        $t       = self::tickets($r, $season);
        $fx      = (float) $r['fx_lkr'];
        $kids    = count($childAges);
        $guests  = $adults + $saarcAdults + $kids;
        $jeeps   = self::jeeps($r, $guests);
        $tickets = $adults * (float) $t['adult'] + $saarcAdults * (float) ($t['saarc_adult'] ?? $t['adult']);
        foreach ($childAges as $age) {
            if ($age !== null && $age !== '' && (float) $age < $r['free_under_age']) {
                continue;
            }
            $tickets += ($age !== null && $age !== '' && (float) $age > $r['child_to_age']) ? (float) $t['adult'] : (float) ($t['child'] ?? $t['adult']);
        }
        $service = $jeeps * (float) $r['service_usd'];
        $vehicle = $fx > 0 ? $jeeps * (float) $r['vehicle_lkr'] / $fx : 0.0;
        $sub     = $tickets + $service + $vehicle;
        $vat     = $sub * (float) $r['vat_pct'] / 100;
        $gate    = $sub + $vat;
        $hire    = $fx > 0 ? $jeeps * (float) $r['hire_lkr'] / $fx : 0.0;
        $total   = $gate + $hire;
        return [
            'guests' => $guests, 'jeeps' => $jeeps, 'season' => $season ?? $r['season'], 'fx' => $fx,
            'tickets_usd' => round($tickets, 2), 'service_usd' => round($service, 2), 'vehicle_usd' => round($vehicle, 4),
            'gate_subtotal_usd' => round($sub, 4), 'vat_usd' => round($vat, 4), 'gate_usd' => round($gate, 2),
            'gate_pp_usd' => $guests > 0 ? round($gate / $guests, 2) : null,
            'hire_usd' => round($hire, 3), 'hire_lkr' => $jeeps * (float) $r['hire_lkr'],
            'total_usd' => round($total, 2), 'per_person_usd' => $guests > 0 ? round($total / $guests, 2) : null,
        ];
    }

    /** What the agent pays for the safari: adults × the adult price (+ children × the child price, open until Management sets it). */
    public static function sell(array $r, int $adults, int $children = 0): array
    {
        $a    = $r['sell_adult_usd'];
        $c    = $r['sell_child_usd'];
        $open = $children > 0 && $c === null;
        $usd  = ($a === null || $open) ? null : round($adults * $a + $children * (float) $c, 2);
        $n    = static fn (float $x): string => fmod($x, 1.0) == 0.0 ? number_format($x, 0) : number_format($x, 2);
        $words = null;
        if ($usd !== null) {
            $words = $adults . ' adult' . ($adults === 1 ? '' : 's') . ' × USD ' . $n((float) $a)
                . ($children > 0 ? ' + ' . $children . ' child' . ($children === 1 ? '' : 'ren') . ' × USD ' . $n((float) $c) : '')
                . ' = USD ' . $n($usd);
        }
        return ['usd' => $usd, 'child_open' => $open, 'words' => $words];
    }

    /** The cost checks by party size (foreign adults): gate per person, the park's cost per person, against the selling price. */
    public static function checks(array $r, array $sizes = [1, 2, 6, 7], ?string $season = 'tourist'): array
    {
        $out = [];
        foreach ($sizes as $n) {
            $c    = self::cost($r, (int) $n, [], 0, $season);
            $sell = $r['sell_adult_usd'];
            $out[] = ['guests' => (int) $n, 'jeeps' => $c['jeeps'], 'gate_pp_usd' => $c['gate_pp_usd'], 'cost_pp_usd' => $c['per_person_usd'], 'total_usd' => $c['total_usd'],
                'gate_usd' => $c['gate_usd'], 'hire_usd' => round($c['hire_usd'], 2),
                'sell_usd' => $sell !== null ? round($sell * $n, 2) : null,
                'margin_usd' => $sell !== null ? round($sell * $n - $c['total_usd'], 2) : null,
                'short_pp_usd' => $sell !== null && $c['per_person_usd'] > $sell ? round($c['per_person_usd'] - $sell, 2) : null];
        }
        return $out;
    }

    // =====================================================================
    //  saving
    // =====================================================================

    public static function save($db, array $v, string $by): array
    {
        if (!self::ensure($db)) {
            throw new \RuntimeException('The Safari Rate Master table could not be set up.');
        }
        $id  = (int) ($v['id'] ?? 0);
        $old = $id ? self::get($db, $id) : null;
        if (!$old) {
            throw new \InvalidArgumentException('No such safari park record.');
        }
        $money = static function ($x, string $what, bool $required, float $max) {
            $x = is_string($x) ? str_replace([',', ' '], '', trim($x)) : $x;
            if ($x === null || $x === '') {
                if ($required) {
                    throw new \InvalidArgumentException($what . ' is required.');
                }
                return null;
            }
            if (!is_numeric($x) || (float) $x < 0 || (float) $x > $max) {
                throw new \InvalidArgumentException($what . ' must be a number between 0 and ' . number_format($max) . '.');
            }
            return round((float) $x, 2);
        };
        $date = static function ($x, string $what) {
            $x = trim((string) ($x ?? ''));
            if ($x === '') {
                return null;
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $x) || !strtotime($x)) {
                throw new \InvalidArgumentException($what . ' must be a date.');
            }
            return $x;
        };
        $pick = static fn (string $k) => array_key_exists($k, $v) ? $v[$k] : $old[$k];
        $n = [];
        foreach (['ts', 'rp'] as $p) {
            foreach (array_keys(self::CATS) as $c) {
                $n[$p . '_' . $c] = $money($pick($p . '_' . $c), ($p === 'ts' ? 'Tourist season' : 'Regular period') . ' — ' . self::CATS[$c], $c === 'adult', 500);
            }
        }
        $n['service_usd']    = $money($pick('service_usd'), 'Service per jeep (USD)', true, 500);
        $n['vehicle_lkr']    = $money($pick('vehicle_lkr'), 'Vehicle entry per jeep (LKR)', true, 100000);
        $n['vat_pct']        = $money($pick('vat_pct'), 'VAT on the gate subtotal (%)', true, 100);
        $n['hire_lkr']       = $money($pick('hire_lkr'), 'Hire per jeep (LKR)', true, 1000000);
        $n['sell_adult_usd'] = $money($pick('sell_adult_usd'), 'Adult selling price per person (USD)', true, 5000);
        $n['sell_child_usd'] = $money($pick('sell_child_usd'), 'Child selling price per person (USD)', false, 5000);
        $n['fx_lkr']         = $money($pick('fx_lkr'), 'LKR per USD', true, 10000);
        if ((float) $n['fx_lkr'] < 50) {
            throw new \InvalidArgumentException('LKR per USD looks wrong (' . $n['fx_lkr'] . ').');
        }
        $seats = (int) $pick('seats');
        if ($seats < 1 || $seats > 12) {
            throw new \InvalidArgumentException('Guests per jeep must be between 1 and 12.');
        }
        $n['seats']      = $seats;
        $n['operator']   = mb_substr(trim((string) ($pick('operator') ?? '')), 0, 120) ?: null;
        $n['source']     = mb_substr(trim((string) ($pick('source') ?? '')), 0, 255) ?: null;
        $n['valid_from'] = $date($pick('valid_from'), 'Valid from');
        $n['valid_to']   = $date($pick('valid_to'), 'Valid to');
        if ($n['valid_from'] && $n['valid_to'] && $n['valid_to'] < $n['valid_from']) {
            throw new \InvalidArgumentException('Valid to is before Valid from.');
        }
        $ev = (string) $pick('evidence');
        $n['evidence'] = isset(self::EVIDENCE[$ev]) ? $ev : 'awaiting';
        if ($n['evidence'] === 'confirmed' && ($n['source'] === null || $n['valid_from'] === null)) {
            throw new \InvalidArgumentException('A confirmed hire rate needs its source (agreement or invoice reference) and the date it is valid from.');
        }
        $se = (string) $pick('season');
        $n['season'] = isset(self::SEASONS[$se]) ? $se : 'tourist';
        $free = $pick('free_under_age');
        $to   = $pick('child_to_age');
        if (!is_numeric($free) || !is_numeric($to) || (float) $free < 0 || (float) $to < (float) $free || (float) $to > 17) {
            throw new \InvalidArgumentException('The child ages must read: free under N, child rate up to M (M at least N, at most 17).');
        }
        $n['free_under_age'] = round((float) $free, 1);
        $n['child_to_age']   = round((float) $to, 1);

        $changes = [];
        foreach ($n as $k => $val) {
            $was = $old[$k];
            $num = is_float($val) || is_float($was) || is_int($val);
            $diff = $num ? (($val === null) !== ($was === null) || round((float) $val, 2) !== round((float) $was, 2)) : (string) $val !== (string) $was;
            if ($diff) {
                $changes[$k] = ['from' => $was, 'to' => $val];
            }
        }
        if ($changes === []) {
            return ['changed' => false, 'changes' => [], 'record' => $old];
        }
        $sets = [];
        $bind = [];
        foreach ($n as $k => $val) {
            $sets[] = "`{$k}` = ?";
            $bind[] = $val;
        }
        $bind[] = $by;
        $bind[] = date('Y-m-d H:i:s');
        $bind[] = $id;
        $db->query('UPDATE tf_safari_rate SET ' . implode(', ', $sets) . ', updated_by = ?, updated_at = ? WHERE id = ?', $bind);
        self::log($db, $id, 'Changed: ' . implode(', ', array_keys($changes)), $changes, $by);
        if ($old['place_id']) {
            self::syncPlace($db, (int) $old['place_id'], $seats, $n['free_under_age'], $n['child_to_age']);
        }
        self::forget();
        return ['changed' => true, 'changes' => $changes, 'record' => self::get($db, $id)];
    }
}
