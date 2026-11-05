<?php

namespace App\Libraries\TourFlow;

/**
 * The road distance between two places according to Google, for the quotation.
 *
 * The company's own record (MileageLookup) says what was actually driven between two
 * places on past tours; this says what Google's map makes the road distance today. The
 * quotation is costed on Google's figure by preference — it exists for sectors nobody
 * has driven yet, and it tends to sit above the driven record, which is the buffer
 * management wants in the price — while the driven figure is kept beside it so the true
 * kilometres are never out of sight. Which of the two the quotation uses is the setting
 * tf_meta mileage_quote_source ('google' or 'actual'), changed on Mileage Allowances.
 *
 * Every answer is kept in tf_google_distance, so a pair of places is asked for once and
 * costs nothing after that — a re-plan of the same itinerary does not go back to Google.
 * A place Google cannot find is remembered too, so it is not asked again on every save;
 * a network fault or a refused key is NOT remembered, and is tried again next time.
 *
 * The key comes from .env (GOOGLE_MAPS_API_KEY, via Config\GoogleMaps), never from
 * source. Without one nothing is asked and every leg says so; the quotation then falls
 * back to the driven record, and the checklist carries one line about it.
 */
final class GoogleDistance
{
    public const TABLE = 'tf_google_distance';

    private $db;
    private object $cfg;
    /** @var callable|null  fn(array<string, string> $urlsByKey): array<string, array{code:int, body:string, error:?string}> */
    private $fetch;
    /** @var array<string, array<string, mixed>> what this request has already learned */
    private array $mem = [];
    private ?bool $haveTable = null;

    /**
     * @param object|null   $config a Config\GoogleMaps, or anything with the same properties
     * @param callable|null $fetch  replaces the HTTP call — tests answer here without a network
     */
    public function __construct($db, $config = null, ?callable $fetch = null)
    {
        $this->db    = $db;
        $this->cfg   = $config ?? self::defaultConfig();
        $this->fetch = $fetch;
    }

    private static function defaultConfig(): object
    {
        if (function_exists('config')) {
            $c = config('GoogleMaps');
            if (is_object($c)) {
                return $c;
            }
        }
        return (object) [
            'apiKey' => trim((string) (function_exists('env') ? env('GOOGLE_MAPS_API_KEY', '') : (getenv('GOOGLE_MAPS_API_KEY') ?: ''))),
            'distanceUrl' => 'https://maps.googleapis.com/maps/api/distancematrix/json',
            'region' => 'lk', 'country' => 'Sri Lanka', 'language' => 'en', 'timeout' => 15, 'parallel' => 6,
        ];
    }

    /** Whether a key is set at all. */
    public function hasKey(): bool
    {
        return trim((string) ($this->cfg->apiKey ?? '')) !== '';
    }

    /** What to tell the person when nothing can be asked. Null when a key is set. */
    public function keyProblem(): ?string
    {
        return $this->hasKey() ? null : 'No Google Maps key is set — add GOOGLE_MAPS_API_KEY to the project\'s .env file.';
    }

    /** Whether the cache table is on this install (the Tour Flow installer creates it). */
    public function available(): bool
    {
        if ($this->haveTable === null) {
            try {
                $this->haveTable = (bool) $this->db->query("SHOW TABLES LIKE '" . self::TABLE . "'")->getResultArray();
            } catch (\Throwable $e) {
                $this->haveTable = false;
            }
        }
        return $this->haveTable;
    }

    /** How many pairs of places have been asked for and kept. */
    public function cachedCount(): int
    {
        if (!$this->available()) {
            return 0;
        }
        try {
            return (int) ($this->db->query('SELECT COUNT(*) n FROM ' . self::TABLE)->getRowArray()['n'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * The place as Google is asked for it: the name as given, with the country on the end
     * unless it already says which country it is in.
     */
    public function place(?string $p): ?string
    {
        $s = trim(preg_replace('/\s+/u', ' ', (string) $p));
        if ($s === '') {
            return null;
        }
        $country = trim((string) ($this->cfg->country ?? 'Sri Lanka'));
        if ($country !== '' && !preg_match('/\b' . preg_quote($country, '/') . '\s*$/iu', $s)) {
            $s .= ', ' . $country;
        }
        return $s;
    }

    /**
     * Ask for many pairs at once. The ones already known are skipped; the rest go to
     * Google together (a few at a time), so an itinerary of a dozen legs is one short
     * wait and not twelve.
     *
     * @param array<int, array{0:?string, 1:?string}> $pairs
     */
    public function warm(array $pairs, bool $refresh = false): void
    {
        $todo = [];
        foreach ($pairs as [$from, $to]) {
            $fq = $this->place($from);
            $tq = $this->place($to);
            if ($fq === null || $tq === null || strcasecmp($fq, $tq) === 0) {
                continue;
            }
            $k = self::key($fq, $tq);
            if (isset($todo[$k]) || (!$refresh && isset($this->mem[$k]))) {
                continue;
            }
            if (!$refresh && ($hit = $this->fromCache($fq, $tq)) !== null) {
                $this->mem[$k] = $hit;
                continue;
            }
            if (!$this->hasKey()) {
                $this->mem[$k] = $this->noKey($fq, $tq);
                continue;
            }
            $todo[$k] = [$fq, $tq];
        }
        if ($todo === []) {
            return;
        }
        $urls = [];
        foreach ($todo as $k => [$fq, $tq]) {
            $urls[$k] = $this->url($fq, $tq);
        }
        $answers = $this->fetch !== null ? ($this->fetch)($urls) : $this->fetchMany($urls);
        foreach ($todo as $k => [$fq, $tq]) {
            $a = $answers[$k] ?? ['code' => 0, 'body' => '', 'error' => 'no answer'];
            $r = $this->parse($a, $fq, $tq);
            if (in_array($r['by'], ['google', 'not_found'], true)) {
                $this->toCache($fq, $tq, $r);
            }
            $this->mem[$k] = $r;
        }
    }

    /**
     * Google's road distance from one place to another.
     *
     * @return array{km:?float, by:string, why:string, asked:string, from_addr:?string, to_addr:?string, minutes:?int, fetched_at:?string}
     *   by: google | not_found | no_key | error | none
     */
    public function distance(?string $from, ?string $to, bool $refresh = false): array
    {
        $fq = $this->place($from);
        $tq = $this->place($to);
        $none = ['km' => null, 'by' => 'none', 'why' => '', 'asked' => '', 'from_addr' => null, 'to_addr' => null, 'minutes' => null, 'fetched_at' => null];
        if ($fq === null || $tq === null) {
            return ['why' => 'Both ends of the leg have to be named before Google can be asked.'] + $none;
        }
        if (strcasecmp($fq, $tq) === 0) {
            return ['why' => 'The leg starts and ends in the same place, so there is nothing to ask Google.'] + $none;
        }
        $k = self::key($fq, $tq);
        if ($refresh || !isset($this->mem[$k])) {
            $this->warm([[$from, $to]], $refresh);
        }
        return $this->mem[$k] ?? (['why' => 'Google was not asked.'] + $none);
    }

    /** Forget what was kept for one pair, so the next ask goes to Google again. */
    public function forget(?string $from, ?string $to): void
    {
        $fq = $this->place($from);
        $tq = $this->place($to);
        if ($fq === null || $tq === null) {
            return;
        }
        unset($this->mem[self::key($fq, $tq)]);
        if ($this->available()) {
            try {
                $this->db->query('DELETE FROM ' . self::TABLE . ' WHERE from_key = ? AND to_key = ?', [mb_strtolower($fq), mb_strtolower($tq)]);
            } catch (\Throwable $e) {
                // nothing kept, nothing to forget
            }
        }
    }

    // =====================================================================

    private static function key(string $fq, string $tq): string
    {
        return mb_strtolower($fq) . "\x00" . mb_strtolower($tq);
    }

    private function noKey(string $fq, string $tq): array
    {
        return ['km' => null, 'by' => 'no_key', 'why' => (string) $this->keyProblem(), 'asked' => $fq . ' → ' . $tq,
                'from_addr' => null, 'to_addr' => null, 'minutes' => null, 'fetched_at' => null];
    }

    private function url(string $fq, string $tq): string
    {
        return rtrim((string) $this->cfg->distanceUrl, '?') . '?' . http_build_query([
            'origins'      => $fq,
            'destinations' => $tq,
            'mode'         => 'driving',
            'units'        => 'metric',
            'region'       => (string) ($this->cfg->region ?? 'lk'),
            'language'     => (string) ($this->cfg->language ?? 'en'),
            'key'          => (string) $this->cfg->apiKey,
        ]);
    }

    private function fromCache(string $fq, string $tq): ?array
    {
        if (!$this->available()) {
            return null;
        }
        try {
            $r = $this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE from_key = ? AND to_key = ?', [mb_strtolower($fq), mb_strtolower($tq)])->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$r) {
            return null;
        }
        $ok = $r['status'] === 'ok' && $r['km'] !== null;
        return [
            'km'         => $ok ? (float) $r['km'] : null,
            'by'         => $ok ? 'google' : 'not_found',
            'why'        => $ok ? $this->okWhy((float) $r['km'], $r['minutes'] !== null ? (int) $r['minutes'] : null, (string) $r['from_addr'], (string) $r['to_addr'], (string) $r['fetched_at'])
                                : (string) ($r['note'] ?: 'Google could not place one end of this leg.'),
            'asked'      => $fq . ' → ' . $tq,
            'from_addr'  => $r['from_addr'] ?: null,
            'to_addr'    => $r['to_addr'] ?: null,
            'minutes'    => $r['minutes'] !== null ? (int) $r['minutes'] : null,
            'fetched_at' => $r['fetched_at'] ?: null,
        ];
    }

    private function toCache(string $fq, string $tq, array $r): void
    {
        if (!$this->available()) {
            return;
        }
        try {
            $this->db->query(
                'INSERT INTO ' . self::TABLE . ' (from_key, to_key, from_text, to_text, km, minutes, status, from_addr, to_addr, note, fetched_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE from_text = VALUES(from_text), to_text = VALUES(to_text), km = VALUES(km), minutes = VALUES(minutes),
                    status = VALUES(status), from_addr = VALUES(from_addr), to_addr = VALUES(to_addr), note = VALUES(note), fetched_at = VALUES(fetched_at)',
                [
                    mb_substr(mb_strtolower($fq), 0, 190), mb_substr(mb_strtolower($tq), 0, 190), mb_substr($fq, 0, 190), mb_substr($tq, 0, 190),
                    $r['km'], $r['minutes'], $r['by'] === 'google' ? 'ok' : 'not_found',
                    $r['from_addr'] !== null ? mb_substr($r['from_addr'], 0, 255) : null, $r['to_addr'] !== null ? mb_substr($r['to_addr'], 0, 255) : null,
                    $r['by'] === 'google' ? null : mb_substr($r['why'], 0, 255), $r['fetched_at'],
                ]
            );
        } catch (\Throwable $e) {
            // a figure that cannot be kept is still a figure for this plan
        }
    }

    private function okWhy(float $km, ?int $minutes, string $fromAddr, string $toAddr, string $fetchedAt): string
    {
        $s = 'Google: ' . rtrim(rtrim(number_format($km, 1, '.', ','), '0'), '.') . ' km by road';
        if ($minutes !== null && $minutes > 0) {
            $s .= ', about ' . ($minutes >= 60 ? intdiv($minutes, 60) . ' h ' . ($minutes % 60) . ' min' : $minutes . ' min');
        }
        if ($fromAddr !== '' || $toAddr !== '') {
            $s .= ' — ' . ($fromAddr !== '' ? $fromAddr : '?') . ' → ' . ($toAddr !== '' ? $toAddr : '?');
        }
        $when = $fetchedAt !== '' ? date('j M Y', strtotime($fetchedAt)) : '';
        return $s . ($when !== '' ? ' (fetched ' . $when . ').' : '.');
    }

    /**
     * One Distance Matrix answer, read strictly. Anything that is not a clear figure or a
     * clear "not found" is an error — tried again next time, never kept.
     *
     * @param array{code:int, body:string, error:?string} $a
     */
    private function parse(array $a, string $fq, string $tq): array
    {
        $base = ['km' => null, 'by' => 'error', 'asked' => $fq . ' → ' . $tq, 'from_addr' => null, 'to_addr' => null, 'minutes' => null, 'fetched_at' => null];
        if (!empty($a['error'])) {
            return ['why' => 'Google could not be reached: ' . $a['error'] . '.'] + $base;
        }
        $j = json_decode((string) ($a['body'] ?? ''), true);
        if (!is_array($j)) {
            return ['why' => 'Google answered HTTP ' . (int) ($a['code'] ?? 0) . ' with nothing readable.'] + $base;
        }
        $status = (string) ($j['status'] ?? '');
        if ($status !== 'OK') {
            $msg = trim((string) ($j['error_message'] ?? ''));
            return ['why' => 'Google refused the request (' . ($status !== '' ? $status : 'HTTP ' . (int) ($a['code'] ?? 0)) . ')' . ($msg !== '' ? ': ' . $msg : '') . '.'] + $base;
        }
        $fromAddr = trim((string) ($j['origin_addresses'][0] ?? ''));
        $toAddr   = trim((string) ($j['destination_addresses'][0] ?? ''));
        $el       = $j['rows'][0]['elements'][0] ?? null;
        $es       = (string) ($el['status'] ?? '');
        $now      = date('Y-m-d H:i:s');
        if ($es === 'NOT_FOUND') {
            $which = $fromAddr === '' && $toAddr === '' ? '“' . $fq . '” or “' . $tq . '”' : ($fromAddr === '' ? '“' . $fq . '”' : '“' . $tq . '”');
            return ['by' => 'not_found', 'why' => 'Google could not place ' . $which . '.', 'from_addr' => $fromAddr ?: null, 'to_addr' => $toAddr ?: null, 'fetched_at' => $now] + $base;
        }
        if ($es === 'ZERO_RESULTS') {
            return ['by' => 'not_found', 'why' => 'Google found no road between ' . $fq . ' and ' . $tq . '.', 'from_addr' => $fromAddr ?: null, 'to_addr' => $toAddr ?: null, 'fetched_at' => $now] + $base;
        }
        if ($es !== 'OK' || !isset($el['distance']['value'])) {
            return ['why' => 'Google answered ' . ($es !== '' ? $es : 'without a distance') . ' for this leg.'] + $base;
        }
        $km      = round(((float) $el['distance']['value']) / 1000, 1);
        $minutes = isset($el['duration']['value']) ? (int) round(((float) $el['duration']['value']) / 60) : null;
        return [
            'km' => $km, 'by' => 'google', 'why' => $this->okWhy($km, $minutes, $fromAddr, $toAddr, $now),
            'from_addr' => $fromAddr ?: null, 'to_addr' => $toAddr ?: null, 'minutes' => $minutes, 'fetched_at' => $now,
        ] + $base;
    }

    /**
     * The HTTP calls, a few at a time.
     *
     * @param array<string, string> $urls
     * @return array<string, array{code:int, body:string, error:?string}>
     */
    private function fetchMany(array $urls): array
    {
        $out = [];
        if (!function_exists('curl_multi_init')) {
            foreach ($urls as $k => $u) {
                $out[$k] = ['code' => 0, 'body' => '', 'error' => 'PHP has no curl extension'];
            }
            return $out;
        }
        $ca = null;
        if (class_exists('\App\Libraries\HotelRateExtractor') && method_exists('\App\Libraries\HotelRateExtractor', 'caBundle')) {
            try {
                $ca = \App\Libraries\HotelRateExtractor::caBundle();
            } catch (\Throwable $e) {
                $ca = null;
            }
        }
        $timeout  = max(3, (int) ($this->cfg->timeout ?? 15));
        $parallel = max(1, (int) ($this->cfg->parallel ?? 6));
        foreach (array_chunk($urls, $parallel, true) as $chunk) {
            $mh = curl_multi_init();
            $hs = [];
            foreach ($chunk as $k => $u) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => $u,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ]);
                if ($ca !== null) {
                    curl_setopt($ch, CURLOPT_CAINFO, $ca);
                }
                curl_multi_add_handle($mh, $ch);
                $hs[$k] = $ch;
            }
            do {
                $st = curl_multi_exec($mh, $running);
                if ($running) {
                    curl_multi_select($mh, 1.0);
                }
            } while ($running && $st === CURLM_OK);
            foreach ($hs as $k => $ch) {
                $err = curl_error($ch);
                $out[$k] = [
                    'code'  => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
                    'body'  => (string) curl_multi_getcontent($ch),
                    'error' => $err !== '' ? $err : null,
                ];
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
            curl_multi_close($mh);
        }
        return $out;
    }
}
