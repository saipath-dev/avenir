<?php

namespace App\Libraries\TourFlow;

/**
 * How far it is between two places, according to the company's own record of driving it.
 *
 * Nothing here consults a map or an API — Google's road distance is GoogleDistance's job,
 * and the two are shown side by side. tour_sub_locations holds 18,056 legs across 1,729
 * tours, and that is the only source here: a sector takes the MEDIAN of what the company
 * recorded as driven between those two towns, and says how many journeys stand behind the
 * figure so a leg backed by two is not mistaken for one backed by four hundred.
 *
 * Three things about that record decided the shape of this class.
 *
 * km, not estimate_km. The table carries both, and the old quotation editor is what says
 * which is which: estimate_km is the figure Google gave on the day the leg was planned
 * (posted as gdistance), km is the actual mileage the office put on the leg (posted as
 * distance — the Transfer Mileage master's actual where it had one, Google's figure where
 * it had not, and corrected by hand after). They differ on 11,304 of the 18,056 legs, so
 * km is the company's own record and estimate_km is only yesterday's map. This class reads
 * km. It is noisier — a whole day's running sometimes lands on one leg — which is what the
 * median and the impossible-row count below are for.
 *
 * The town is the last part of the name. The history writes places as "Place, Town":
 * "Heritance Kandalama, Dambulla", "Sigiriya Lion Rock, Sigiriya". Matching on the town
 * turns 971 exact Sigiriya legs into 3,414 usable ones, and — the reason it is done this
 * way rather than by prefix — it correctly files "Galle Face Hotel, Galle Road, Colombo"
 * under Colombo, which is where the hotel is. A prefix match would have called it Galle
 * and quoted a journey a hundred kilometres out.
 *
 * Some of the record is wrong. There is a Yala to Galle leg on file at 7,220 km. Outliers
 * like that are left out of the median and COUNTED, so the panel can say "nine journeys on
 * file, five ignored as impossible" instead of quietly quoting a fantasy.
 */
final class MileageLookup
{
    /** The table the legs live in. Absent on an install that has never run a tour. */
    private const LEGS = 'tour_sub_locations';
    /** The transport desk's legs: the kilometres actually driven, entered after each tour (schema 18). */
    private const DRIVEN = 'tf_transport_leg';

    private $db;
    private ?bool $haveLegs = null;
    private ?bool $haveDriven = null;
    private ?array $cities  = null;
    private ?array $towns   = null;
    private array $names    = [];
    private array $cache    = [];
    private float $saneMax;
    private int $minSamples;

    public function __construct($db, float $saneMax = 450.0, int $minSamples = 3)
    {
        $this->db         = $db;
        $this->saneMax    = $saneMax > 0 ? $saneMax : 450.0;
        $this->minSamples = max(1, $minSamples);
    }

    /** Whether there is any driving history on this install at all. */
    public function available(): bool
    {
        if ($this->haveLegs === null) {
            try {
                $this->haveLegs = (bool) $this->db->query("SHOW TABLES LIKE '" . self::LEGS . "'")->getResultArray();
            } catch (\Throwable $e) {
                $this->haveLegs = false;
            }
        }
        return $this->haveLegs || $this->drivenAvailable();
    }

    /** Whether the transport desk's legs can hold the kilometres actually driven (schema 18). */
    private function drivenAvailable(): bool
    {
        if ($this->haveDriven === null) {
            try {
                $this->haveDriven = (bool) $this->db->query("SHOW COLUMNS FROM `" . self::DRIVEN . "` LIKE 'actual_km'")->getResultArray();
            } catch (\Throwable $e) {
                $this->haveDriven = false;
            }
        }
        return $this->haveDriven;
    }

    /**
     * What the company has driven between two places.
     *
     * @return array{km:?float, extra_km:?float, km_by:string, extra_by:string, samples:int,
     *               dropped:int, low:?float, high:?float, thin:bool, why:string}
     */
    public function between(?string $from, ?string $to): array
    {
        $f = self::clean($from);
        $t = self::clean($to);
        $none = [
            'km' => null, 'extra_km' => null, 'km_by' => 'none', 'extra_by' => 'none',
            'samples' => 0, 'samples_post' => 0, 'dropped' => 0, 'low' => null, 'high' => null, 'thin' => false, 'learned' => false, 'older' => null, 'why' => '',
        ];
        if ($f === null || $t === null) {
            return ['why' => 'Both ends of the leg have to be named before a distance can be looked up.'] + $none;
        }
        if (self::townOf($f) === self::townOf($t) && strcasecmp($f, $t) === 0) {
            return ['why' => 'The leg starts and ends in the same place.'] + $none;
        }
        if (!$this->available()) {
            return ['why' => 'This install has no driving history to look the distance up in.'] + $none;
        }

        $key = strtolower($f . "\x00" . $t);
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        // The name as given, first. When a day names a specific hotel and the company has
        // driven to that exact hotel, that is a better answer than the town's.
        $hit = $this->sample('{from} = ? AND {to} = ?', [$f, $t], 'history');
        if ($hit['samples'] < $this->minSamples) {
            $town = $this->sample(
                "TRIM(SUBSTRING_INDEX({from}, ',', -1)) = ? AND TRIM(SUBSTRING_INDEX({to}, ',', -1)) = ?",
                [self::townOf($f), self::townOf($t)],
                'town_history'
            );
            if ($town['samples'] > $hit['samples']) {
                $hit = $town;
            }
        }

        if ($hit['samples'] === 0) {
            $hit = ['why' => 'The company has no record of driving ' . $f . ' to ' . $t . ', so no distance is offered. Type one in.'] + $none;
        } else {
            $hit['thin'] = $hit['samples'] < $this->minSamples;
            $hit['why']  = $this->explain($f, $t, $hit);
        }

        // The local-running allowance the office puts on a leg. Taken from the same history
        // where it exists, and from the extra_kilometers master for the destination town
        // where it does not — which is where the office's own figures come from anyway.
        if ($hit['extra_km'] === null) {
            $city = $this->cityAllowance($t);
            if ($city !== null) {
                $hit['extra_km'] = $city;
                $hit['extra_by'] = 'city_allowance';
            }
        }

        return $this->cache[$key] = $hit;
    }

    /**
     * How many legs the history has ever run to or from this place.
     *
     * Used to choose between the several names one night can go by. An agent writes
     * "Ella / Nuwaraeliya" or "Galle or near Galle" — their own words, which are kept
     * word for word everywhere else in this panel and are no use at all as a place. The
     * night usually also carries a hotel the rates panel has matched, and the panel knows
     * which area that hotel is in. Asking the history which of those names it recognises
     * is how the right one gets picked, without rewriting anything the agent said.
     */
    public function knows(?string $place, bool $exactOnly = false): int
    {
        $p = self::clean($place);
        if ($p === null || !$this->available()) {
            return 0;
        }
        $town  = self::townOf($p);
        $sql   = 'SELECT COUNT(*) n FROM ' . self::LEGS . ' WHERE km > 0 AND (`from` = ? OR `to` = ?';
        $binds = [$p, $p];
        if (!$exactOnly) {
            $sql .= " OR TRIM(SUBSTRING_INDEX(`from`, ',', -1)) = ? OR TRIM(SUBSTRING_INDEX(`to`, ',', -1)) = ?";
            array_push($binds, $town, $town);
        }
        try {
            $r = $this->db->query($sql . ')', $binds)->getRowArray();
        } catch (\Throwable $e) {
            return 0;
        }
        return (int) ($r['n'] ?? 0);
    }

    /**
     * The local running a day in one place is allowed, from the extra_kilometers master.
     * Used for a day with no transfer on it — two nights in Kandy still means driving.
     */
    public function cityAllowance(?string $place): ?float
    {
        $town = self::townOf(self::clean($place) ?? '');
        if ($town === '') {
            return null;
        }
        if ($this->cities === null) {
            $this->cities = [];
            try {
                foreach ($this->db->query('SELECT city, kilometers FROM extra_kilometers')->getResultArray() as $r) {
                    $c = strtolower(trim((string) $r['city']));
                    if ($c !== '' && is_numeric($r['kilometers'])) {
                        $this->cities[$c] = (float) $r['kilometers'];
                    }
                }
            } catch (\Throwable $e) {
                $this->cities = [];
            }
        }
        return $this->cities[strtolower($town)] ?? null;
    }

    /** Every town the master holds an allowance for, for the screen to offer. */
    public function allowanceTowns(): array
    {
        $this->cityAllowance('x');
        return $this->cities ?? [];
    }

    // =====================================================================

    /**
     * One pass over the record. Medians, not averages, and impossible rows counted out.
     *
     * Two sources, in order. The kilometres actually driven, entered on the transport card
     * after a tour ran, come first: once a sector has enough of them (minSamples) the figure
     * is their median and the older record is only mentioned — that is how the record
     * learns from every tour that runs. Until then the two are pooled, and the figure says
     * how many of its journeys were entered after a tour.
     *
     * `$where` names the two place columns as {from} and {to}, because the old table calls
     * them `from`/`to` and the transport card from_place/to_place.
     *
     * @return array<string, mixed>
     */
    private function sample(string $where, array $binds, string $by): array
    {
        $blank = ['km' => null, 'extra_km' => null, 'km_by' => 'none', 'extra_by' => 'none',
                  'samples' => 0, 'samples_post' => 0, 'dropped' => 0, 'low' => null, 'high' => null, 'thin' => false, 'learned' => false, 'older' => null, 'why' => ''];
        foreach ($binds as $b) {
            if (trim((string) $b) === '') {
                return $blank;
            }
        }
        $old = $post = [];
        $this->available();
        if ($this->haveLegs) {
            try {
                $old = $this->db->query(
                    'SELECT km, extra_km FROM ' . self::LEGS . ' WHERE km > 0 AND ' . str_replace(['{from}', '{to}'], ['`from`', '`to`'], $where),
                    $binds
                )->getResultArray();
            } catch (\Throwable $e) {
                $old = [];
            }
        }
        if ($this->drivenAvailable()) {
            try {
                $post = $this->db->query(
                    'SELECT actual_km AS km, NULL AS extra_km FROM ' . self::DRIVEN . ' WHERE actual_km > 0 AND ' . str_replace(['{from}', '{to}'], ['from_place', 'to_place'], $where),
                    $binds
                )->getResultArray();
            } catch (\Throwable $e) {
                $post = [];
            }
        }

        $take = static function (array $rows, float $max): array {
            $km = $extra = [];
            $dropped = 0;
            foreach ($rows as $r) {
                $v = (float) $r['km'];
                if ($v <= 0 || $v > $max) {
                    $dropped++;
                    continue;
                }
                $km[] = $v;
                if ($r['extra_km'] !== null && (float) $r['extra_km'] > 0 && (float) $r['extra_km'] <= $max) {
                    $extra[] = (float) $r['extra_km'];
                }
            }
            sort($km);
            return [$km, $extra, $dropped];
        };
        [$kmPost, , $dropPost] = $take($post, $this->saneMax);
        [$kmOld, $extraOld, $dropOld] = $take($old, $this->saneMax);

        $learned = count($kmPost) >= $this->minSamples;
        $km      = $learned ? $kmPost : array_merge($kmPost, $kmOld);
        sort($km);
        if ($km === []) {
            $blank['dropped'] = $dropPost + $dropOld;
            return $blank;
        }
        return [
            'km'           => round(self::median($km), 1),
            // the local running comes from the old record either way; the desk enters the run, not the running about
            'extra_km'     => $extraOld === [] ? null : round(self::median($extraOld), 1),
            'km_by'        => $by,
            'extra_by'     => $extraOld === [] ? 'none' : $by,
            'samples'      => count($km),
            'samples_post' => count($kmPost),
            'dropped'      => $learned ? $dropPost : $dropPost + $dropOld,
            'low'          => round($km[0], 1),
            'high'         => round($km[count($km) - 1], 1),
            'thin'         => false,
            'learned'      => $learned,
            'older'        => ($learned && $kmOld !== []) ? ['km' => round(self::median($kmOld), 1), 'samples' => count($kmOld)] : null,
            'why'          => '',
        ];
    }

    /** @param array<string, mixed> $hit */
    private function explain(string $f, string $t, array $hit): string
    {
        $n    = (int) $hit['samples'];
        $post = (int) ($hit['samples_post'] ?? 0);
        $how  = $hit['km_by'] === 'town_history'
            ? 'driven between ' . self::townOf($f) . ' and ' . self::townOf($t)
            : 'driven ' . $f . ' to ' . $t;

        if (!empty($hit['learned'])) {
            // the figure is what was actually driven on tours that ran, entered afterwards
            $s = $n . ' actual run' . ($n === 1 ? '' : 's') . ' entered after tours, ' . $how . '. The middle one is ' . $hit['km'] . ' km'
                . (($hit['low'] < $hit['high']) ? ' (they run ' . $hit['low'] . ' to ' . $hit['high'] . ').' : '.');
            if (!empty($hit['older'])) {
                $s .= ' The older record put it at ' . $hit['older']['km'] . ' km over ' . (int) $hit['older']['samples'] . ' journey' . ((int) $hit['older']['samples'] === 1 ? '' : 's') . '.';
            }
        } else {
            $s = $n === 1
                ? 'One journey on file, ' . $how . ', at ' . $hit['km'] . ' km.'
                : $n . ' journeys on file, ' . $how . '. The middle one is ' . $hit['km'] . ' km'
                  . (($hit['low'] < $hit['high']) ? ' (they run ' . $hit['low'] . ' to ' . $hit['high'] . ').' : '.');
            if ($post > 0) {
                $s .= ' ' . $post . ' of them ' . ($post === 1 ? 'was' : 'were') . ' entered after a tour ran.';
            }
        }

        if ((int) $hit['dropped'] > 0) {
            $s .= ' ' . (int) $hit['dropped'] . ' more ignored as impossible — over ' . (int) $this->saneMax . ' km.';
        }
        if ($n < $this->minSamples) {
            $s .= ' Thin history — worth a look before it is quoted.';
        }
        return $s;
    }

    /** @param array<int, float> $sorted */
    private static function median(array $sorted): float
    {
        sort($sorted);
        $c = count($sorted);
        if ($c === 0) {
            return 0.0;
        }
        return $c % 2 ? $sorted[intdiv($c, 2)] : ($sorted[$c / 2 - 1] + $sorted[$c / 2]) / 2;
    }

    /**
     * The history's own name for a hotel. The record writes "Heritance Kandalama, Dambulla"
     * where the panel says "Heritance Kandalama"; a leg looked up under the record's own
     * spelling finds the journeys actually driven to that door. The most-driven spelling
     * wins; null when the company has never driven to a place of that name.
     */
    public function historyName(?string $hotel): ?string
    {
        $h = self::clean($hotel);
        if ($h === null || !$this->available()) {
            return null;
        }
        $key = strtolower($h);
        if (array_key_exists($key, $this->names)) {
            return $this->names[$key];
        }
        $like = self::likeEscape($h) . ',%';
        try {
            $rows = $this->db->query(
                'SELECT place, SUM(n) AS n FROM (
                    SELECT `to` AS place, COUNT(*) AS n FROM ' . self::LEGS . ' WHERE km > 0 AND (`to` = ? OR `to` LIKE ?) GROUP BY `to`
                    UNION ALL
                    SELECT `from` AS place, COUNT(*) AS n FROM ' . self::LEGS . ' WHERE km > 0 AND (`from` = ? OR `from` LIKE ?) GROUP BY `from`
                 ) x GROUP BY place ORDER BY n DESC LIMIT 1',
                [$h, $like, $h, $like]
            )->getResultArray();
        } catch (\Throwable $e) {
            $rows = [];
        }
        return $this->names[$key] = $rows ? (string) $rows[0]['place'] : null;
    }

    /**
     * A town the history knows, from a word the agent used: the town as spelt in the
     * record (any case), or one letter off it — "Anuradapura" is Anuradhapura, and the
     * record says so on 155 legs. Two letters off is not matched: that is a different
     * place, or a guess, and neither belongs on a leg.
     *
     * @return array{town:string, slip:bool, legs:int}|null
     */
    public function town(?string $text): ?array
    {
        $t = self::clean($text);
        if ($t === null || !$this->available()) {
            return null;
        }
        $towns = $this->towns();
        $key   = mb_strtolower($t);
        if (isset($towns[$key])) {
            return ['town' => $towns[$key]['name'], 'slip' => false, 'legs' => $towns[$key]['legs']];
        }
        if (mb_strlen($key) < 6) {
            return null;
        }
        $best = null;
        foreach ($towns as $k => $tw) {
            if (abs(mb_strlen($k) - mb_strlen($key)) > 1 || $tw['legs'] < 3) {
                continue;
            }
            if (levenshtein($key, $k) === 1 && ($best === null || $tw['legs'] > $best['legs'])) {
                $best = $tw;
            }
        }
        return $best ? ['town' => $best['name'], 'slip' => true, 'legs' => $best['legs']] : null;
    }

    /**
     * The town named inside a line of the agent's — "Sigiriya rock" names Sigiriya, "AM
     * Polonnaruwa S/S" names Polonnaruwa. Whole words only, the longest town name first,
     * so "Nuwara Eliya" is not read as "Eliya". Null when no known town is in it.
     *
     * @return array{town:string, slip:bool, legs:int}|null
     */
    public function townIn(?string $text): ?array
    {
        $t = self::clean($text);
        if ($t === null || !$this->available()) {
            return null;
        }
        $hay = ' ' . mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $t)) . ' ';
        $hit = null;
        foreach ($this->towns() as $k => $tw) {
            if ($tw['legs'] < 3 || mb_strlen($k) < 4) {
                continue;
            }
            if (str_contains($hay, ' ' . $k . ' ') && ($hit === null || mb_strlen($k) > mb_strlen($hit['key']))) {
                $hit = ['key' => $k, 'town' => $tw['name'], 'legs' => $tw['legs']];
            }
        }
        if ($hit) {
            return ['town' => $hit['town'], 'slip' => false, 'legs' => $hit['legs']];
        }
        // No town spelt right in it: the whole line, then each long word of it, one letter off.
        if ($found = $this->town($t)) {
            return $found;
        }
        foreach (preg_split('/\s+/u', trim($hay)) ?: [] as $word) {
            if (mb_strlen($word) >= 6 && ($found = $this->town($word)) && $found['slip']) {
                return $found;
            }
        }
        return null;
    }

    /** "Sigiriya/Polonnaruwa", "Ella & Nuwara Eliya", "Galle, Mirissa and Weligama" → the places named, in order. */
    public static function stopsIn(?string $text): array
    {
        $t = self::clean($text);
        if ($t === null) {
            return [];
        }
        $parts = preg_split('/\s*(?:\/|&|\+|,|\band\b)\s*/iu', $t) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '' && !in_array(mb_strtolower($p), array_map('mb_strtolower', $out), true)) {
                $out[] = $p;
            }
        }
        return $out;
    }

    /** Every town in the record with how many legs touch it: lower-cased name => [name, legs]. */
    private function towns(): array
    {
        if ($this->towns === null) {
            $this->towns = [];
            try {
                $rows = $this->db->query(
                    'SELECT town, SUM(n) AS legs FROM (
                        SELECT TRIM(SUBSTRING_INDEX(`to`, \',\', -1)) AS town, COUNT(*) AS n FROM ' . self::LEGS . ' WHERE km > 0 GROUP BY town
                        UNION ALL
                        SELECT TRIM(SUBSTRING_INDEX(`from`, \',\', -1)) AS town, COUNT(*) AS n FROM ' . self::LEGS . ' WHERE km > 0 GROUP BY town
                     ) x GROUP BY town'
                )->getResultArray();
            } catch (\Throwable $e) {
                $rows = [];
            }
            foreach ($rows as $r) {
                $name = trim((string) $r['town']);
                if ($name === '') {
                    continue;
                }
                $k = mb_strtolower($name);
                if (!isset($this->towns[$k]) || (int) $r['legs'] > $this->towns[$k]['legs']) {
                    $this->towns[$k] = ['name' => $name, 'legs' => (int) $r['legs']];
                }
            }
        }
        return $this->towns;
    }

    private static function likeEscape(string $s): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
    }

    /** "Heritance Kandalama, Dambulla" is in Dambulla. "Galle Face Hotel, ..., Colombo" is in Colombo. */
    public static function townOf(string $place): string
    {
        $parts = explode(',', $place);
        return trim((string) end($parts));
    }

    private static function clean($v): ?string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        // the office strips this before saving a leg, so the history never carries it
        $s = trim(str_ireplace(', Sri Lanka', '', $s));
        return $s === '' ? null : mb_substr($s, 0, 190);
    }
}
