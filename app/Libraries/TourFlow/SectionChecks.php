<?php

namespace App\Libraries\TourFlow;

/**
 * Before Tour Confirmation — the check panel of the Tour Inquiry (tf-check-r1, 14 Nov 2026).
 *
 * The inquiry is worked on in the day-by-day itinerary. This reads the SAME records, grouped by section —
 * Hotels, Transfers, Entrance Fees, Activities — so a person can look each one over before the tour is
 * confirmed. Nothing is copied: every line and every figure here comes from QuoteService::costing(), the
 * one place the inquiry is costed, so nothing is entered twice and nothing is counted twice.
 *
 * Each section has a status a PERSON sets:
 *   needs_attention — the default. Reading an email, importing or typing never changes this.
 *   checked         — a person looked it over. Kept with a signature of the section as it was then;
 *                     when the section's records change, the signature no longer matches and the
 *                     section reads Needs attention again, by itself.
 *   not_required    — the tour has nothing in this section and needs nothing.
 *
 * Checking a section never confirms the tour: Confirm Tour is its own action and its own process.
 */
class SectionChecks
{
    public const SECTIONS = ['hotels' => 'Hotels', 'transfers' => 'Transfers', 'entrances' => 'Entrance Fees', 'activities' => 'Activities'];

    /** service kinds: what is an entrance, what is an activity; lunch and dinner belong to Meals and are in neither */
    private const ENTRANCE_KINDS = ['visit', 'entrance'];
    private const MEAL_KINDS     = ['lunch', 'dinner'];

    private $db;
    private string $user;
    private ?int $userId;
    private ?string $name;

    public function __construct($db, string $user = '', ?int $userId = null, ?string $name = null)
    {
        $this->db     = $db;
        $this->user   = $user;
        $this->userId = $userId;
        $this->name   = $name;
        $this->ensure();
    }

    private function ensure(): void
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS tf_intake_check (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            intake_id INT UNSIGNED NOT NULL,
            section VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            sig CHAR(32) NOT NULL,
            by_user_id INT NULL,
            by_name VARCHAR(190) NULL,
            `at` DATETIME NOT NULL,
            UNIQUE KEY uq_intake_section (intake_id, section)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    private static function bucket(array $s): string
    {
        $k = (string) ($s['kind'] ?? '');
        if (in_array($k, self::MEAL_KINDS, true) || !empty($s['meal'])) {
            return 'meals';
        }
        return in_array($k, self::ENTRANCE_KINDS, true) ? 'entrances' : 'activities';
    }

    /** Which section a costing blocker belongs to; '' = the inquiry as a whole. */
    private static function sectionOf(string $code, array $svcBucket): string
    {
        if (preg_match('/^svc_(?:rate_|master_|rupees_|dup_)?(\d+)$/', $code, $m)) {
            return $svcBucket[(int) $m[1]] ?? 'entrances';
        }
        if ($code === 'svc_total') {
            return 'entrances';
        }
        if (preg_match('/^exc_/', $code)) {
            return 'activities';
        }
        if (preg_match('/^(rate_|hotel_|group_rooms_|departure_row_)/', $code) || in_array($code, ['rooms_mix', 'nights_missing', 'no_nights'], true)) {
            return 'hotels';
        }
        if (preg_match('/^(km_|transfer_|crew_|accom_)/', $code) || in_array($code, ['no_vehicle', 'no_legs'], true)) {
            return 'transfers';
        }
        return '';
    }

    private static function num($v, int $dp = 2): string
    {
        return rtrim(rtrim(number_format((float) $v, $dp), '0'), '.');
    }

    /**
     * The four sections as they stand now, from the inquiry ($it, IntakeService::get) and its costing ($cost, unfiltered).
     * $seesTotals: the viewer holds Pricing — the totals are Management's, as everywhere else on the inquiry.
     */
    public function state(array $it, array $cost, bool $seesTotals): array
    {
        $id = (int) $it['id'];
        $svcBucket = [];
        $lines = ['hotels' => [], 'transfers' => [], 'entrances' => [], 'activities' => []];
        $sum   = ['hotels' => 0.0, 'transfers' => 0.0, 'entrances' => 0.0, 'activities' => 0.0];
        $open  = ['hotels' => 0, 'transfers' => 0, 'entrances' => 0, 'activities' => 0];   // lines without a figure

        foreach ((array) ($cost['hotels'] ?? []) as $h) {
            $mix = [];
            foreach ((array) ($h['mix'] ?? []) as $k => $n) {
                if (in_array($k, ['sgl', 'dbl', 'twn', 'tpl'], true) && (int) $n > 0) {
                    $mix[] = (int) $n . ' ' . strtoupper((string) $k);
                }
            }
            $missing = ($h['state'] ?? 'ok') !== 'ok' || ($h['amount_usd'] ?? null) === null;
            $lines['hotels'][] = ['day_no' => (int) $h['day_no'], 'date' => $h['date'] ?? null, 'title' => ($h['hotel'] ?? null) ?: null, 'sub' => (string) ($h['area'] ?? ''),
                'a' => $h['room_type'] ?? null, 'b' => $h['meal_plan'] ?? null, 'c' => implode(', ', $mix), 'amount_usd' => $h['amount_usd'] ?? null, 'missing' => $missing, 'why' => $h['why'] ?? null];
            $sum['hotels'] += (float) ($h['amount_usd'] ?? 0);
            $open['hotels'] += $missing ? 1 : 0;
        }
        foreach ((array) ($cost['transport'] ?? []) as $t) {
            $missing = ($t['state'] ?? 'ok') !== 'ok' || ($t['amount_usd'] ?? null) === null;
            $km = (float) ($t['charged_km'] ?? $t['km'] ?? 0);
            $lines['transfers'][] = ['title' => trim(($t['label'] ?? 'Vehicle') . ' · ' . (($t['type'] ?? '') ?: 'size not set')), 'sub' => 'Vehicle',
                'a' => self::num($km, 1) . ' km', 'b' => ($t['km_rate'] ?? null) !== null ? (($t['currency'] ?? '') . ' ' . number_format((float) $t['km_rate'], 2) . ' / km') : null, 'c' => (int) ($t['legs'] ?? 0) . ' leg(s)',
                'amount_usd' => $t['amount_usd'] ?? null, 'missing' => $missing, 'why' => $t['why'] ?? null];
            $sum['transfers'] += (float) ($t['amount_usd'] ?? 0);
            $open['transfers'] += $missing ? 1 : 0;
        }
        foreach ((array) ($cost['crew'] ?? []) as $c) {
            $missing = ($c['state'] ?? 'ok') !== 'ok' || ($c['amount_usd'] ?? null) === null;
            $lines['transfers'][] = ['title' => (string) ($c['role'] ?? 'Crew'), 'sub' => 'Driver / guide', 'a' => (int) ($c['days'] ?? 0) . ' day(s)',
                'b' => ($c['bata'] ?? null) !== null ? (($c['currency'] ?? '') . ' ' . number_format((float) $c['bata'], 2) . ' / day') : null, 'c' => ($c['person'] ?? null) ?: 'nobody engaged yet',
                'amount_usd' => $c['amount_usd'] ?? null, 'missing' => $missing, 'why' => $c['why'] ?? null];
            $sum['transfers'] += (float) ($c['amount_usd'] ?? 0);
            $open['transfers'] += $missing ? 1 : 0;
        }
        foreach ((array) ($cost['crew_rooms'] ?? []) as $r) {
            $missing = ($r['state'] ?? 'ok') !== 'ok';
            if (!$missing && ($r['amount_usd'] ?? null) === null) {
                continue;   // a room that is switched off is not a line
            }
            $lines['transfers'][] = ['title' => ucfirst((string) ($r['kind'] ?? 'crew')) . ' room', 'sub' => 'Accommodation', 'a' => isset($r['nights']) ? (int) $r['nights'] . ' night(s)' : null, 'b' => null, 'c' => null,
                'amount_usd' => $r['amount_usd'] ?? null, 'missing' => $missing, 'why' => $r['why'] ?? null];
            $sum['transfers'] += (float) ($r['amount_usd'] ?? 0);
            $open['transfers'] += $missing ? 1 : 0;
        }
        // the airport transfers are part of the section whether or not a vehicle is on the inquiry yet
        $airport = [];
        foreach (['arrival' => 'Arrival', 'departure' => 'Departure'] as $side => $word) {
            $by = (string) ($it[$side . '_by'] ?? '');
            if ($by !== '') {
                $airport[] = $word . ': ' . ($by === 'hotel' ? 'by the hotel' : ($by === 'none' ? 'not required' : 'our car'));
            }
        }

        foreach ((array) ($cost['services'] ?? []) as $s) {
            $b = self::bucket($s);
            $svcBucket[(int) $s['id']] = $b;
            if ($b === 'meals') {
                continue;
            }
            $free    = ($s['pay_mode'] ?? '') === 'no_charge' || !empty($s['free']);
            $missing = ($s['state'] ?? 'ok') !== 'ok' || (!$free && ($s['amount_usd'] ?? null) === null);
            $lines[$b][] = ['id' => (int) $s['id'], 'day_no' => (int) $s['day_no'], 'title' => (string) $s['title'], 'sub' => $b === 'activities' ? ucfirst((string) $s['kind']) : null,
                'a' => ($s['qty'] ?? null) !== null ? self::num($s['qty']) . ' ×' : null, 'b' => ($s['unit_usd'] ?? null) !== null ? 'USD ' . number_format((float) $s['unit_usd'], 2) : null,
                'c' => $free ? 'No charge' : null, 'amount_usd' => $free ? 0.0 : ($s['amount_usd'] ?? null), 'missing' => $missing, 'why' => $s['why'] ?? null];
            $sum[$b] += $free ? 0.0 : (float) ($s['amount_usd'] ?? 0);
            $open[$b] += $missing ? 1 : 0;
        }
        foreach ((array) ($cost['excursions'] ?? []) as $x) {
            $missing = ($x['state'] ?? 'ok') !== 'ok' || ($x['amount_usd'] ?? null) === null;
            $lines['activities'][] = ['day_no' => (int) $x['day_no'], 'title' => (string) $x['name'], 'sub' => 'Excursion', 'a' => self::num($x['qty']) . ' ×',
                'b' => ($x['currency'] ?? '') . ' ' . number_format((float) $x['rate'], 2), 'c' => str_replace('_', ' ', (string) ($x['unit'] ?? '')), 'amount_usd' => $x['amount_usd'] ?? null, 'missing' => $missing, 'why' => $x['why'] ?? null];
            $sum['activities'] += (float) ($x['amount_usd'] ?? 0);
            $open['activities'] += $missing ? 1 : 0;
        }

        $issues  = ['hotels' => [], 'transfers' => [], 'entrances' => [], 'activities' => []];
        $general = [];
        foreach ((array) ($cost['blockers'] ?? []) as $b) {
            $sec = self::sectionOf((string) ($b['code'] ?? ''), $svcBucket);
            if (isset($issues[$sec])) {
                $issues[$sec][] = ['code' => (string) $b['code'], 'label' => (string) $b['label']];
            } else {
                $general[] = ['code' => (string) $b['code'], 'label' => (string) $b['label']];
            }
        }

        $stored = [];
        foreach ($this->db->query('SELECT * FROM tf_intake_check WHERE intake_id = ?', [$id])->getResultArray() as $r) {
            $stored[$r['section']] = $r;
        }

        $out = [];
        foreach (self::SECTIONS as $key => $label) {
            $sig = self::sign($key, $lines[$key], $issues[$key], $key === 'transfers' ? $airport : []);
            $row = $stored[$key] ?? null;
            $status  = 'needs_attention';
            $changed = false;
            if ($row) {
                if (hash_equals((string) $row['sig'], $sig)) {
                    $status = (string) $row['status'];
                } else {
                    $changed = true;   // the records moved since a person looked: back to Needs attention
                }
            }
            $count      = count($lines[$key]);
            $incomplete = $open[$key] > 0 || count($issues[$key]) > 0;
            $sec = [
                'key' => $key, 'label' => $label, 'status' => $status, 'changed' => $changed,
                'was' => $row ? ['status' => $row['status'], 'by' => $row['by_name'], 'at' => $row['at']] : null,
                'count' => $count, 'lines' => $seesTotals ? $lines[$key] : array_map(static fn ($l) => array_merge($l, ['amount_usd' => null]), $lines[$key]), 'issues' => $issues[$key], 'open_lines' => $open[$key],
                'incomplete' => $incomplete, 'total_usd' => $seesTotals ? round($sum[$key], 2) : null, 'total_hidden' => !$seesTotals,
                'can_check' => $count > 0 && !$incomplete, 'can_skip' => $count === 0 && count($issues[$key]) === 0,
            ];
            if ($key === 'transfers') {
                $sec['airport'] = $airport;
            }
            $out[$key] = $sec;
        }

        $done = 0;
        foreach ($out as $s) {
            $done += $s['status'] === 'needs_attention' ? 0 : 1;
        }
        return [
            'sections' => $out, 'general' => $general, 'unplaced' => self::unplaced($it, $cost),
            'reviewed' => $done, 'of' => count($out), 'all_reviewed' => $done === count($out),
            'locked' => !empty($it['locked']), 'has_email' => trim((string) ($it['source_text'] ?? '')) !== '',
            'grand_usd' => $seesTotals ? round(array_sum($sum), 2) : null,
            'grand_incomplete' => (bool) array_filter($out, static fn ($s) => $s['incomplete']),
        ];
    }

    private static function sign(string $key, array $lines, array $issues, array $extra): string
    {
        $codes = array_map(static fn ($b) => (string) ($b['code'] ?? ''), $issues);
        sort($codes);
        return md5((string) json_encode([$key, $lines, $codes, $extra], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    /** What reading the email could not recognise or could not put anywhere: said, never guessed. */
    private static function unplaced(array $it, array $cost): array
    {
        $out = [];
        if (trim((string) ($it['source_text'] ?? '')) === '') {
            return $out;
        }
        foreach ((array) ($it['email_checks'] ?? []) as $c) {
            $out[] = ['kind' => 'email', 'label' => (string) ($c['label'] ?? '')];
        }
        foreach ((array) ($it['days'] ?? []) as $d) {
            $name = trim((string) ($d['hotel_name_raw'] ?? ''));
            if ($name !== '' && empty($d['hotel_id']) && empty($d['hotel_acc'])) {
                $out[] = ['kind' => 'hotel', 'label' => 'Night ' . (int) $d['day_no'] . ': "' . $name . '" was not matched to a hotel in the Hotel Master.'];
            } elseif ($name === '') {
                $out[] = ['kind' => 'hotel', 'label' => 'Night ' . (int) $d['day_no'] . ': no hotel was found for this night.'];
            }
        }
        foreach ((array) ($cost['services'] ?? []) as $s) {
            if (($s['state'] ?? 'ok') !== 'ok' && self::bucket($s) !== 'meals') {
                $out[] = ['kind' => 'service', 'label' => 'Day ' . (int) $s['day_no'] . ': "' . $s['title'] . '" could not be placed' . (!empty($s['why']) ? ' — ' . rtrim((string) $s['why'], '.') . '.' : '.')];
            }
        }
        foreach ((array) ($it['requests'] ?? []) as $r) {
            if (($r['status'] ?? '') === 'open') {
                $txt = trim((string) ($r['text'] ?? $r['body'] ?? $r['title'] ?? ''));
                $out[] = ['kind' => 'request', 'label' => (($r['kind'] ?? '') === 'question' ? 'Question in the email, still open: ' : 'Request in the email, not yet settled: ') . $txt];
            }
        }
        foreach ((array) ($it['fields'] ?? []) as $k => $f) {
            if (is_array($f) && !empty($f['missing'])) {
                $out[] = ['kind' => 'field', 'label' => ucfirst(str_replace('_', ' ', (string) $k)) . ' was not found in the email.'];
            }
        }
        return $out;
    }

    /** A person sets a section's status. Returns the new state. Never touches the tour's own status. */
    public function set(array $it, array $cost, string $section, string $status, bool $seesTotals): array
    {
        if (!isset(self::SECTIONS[$section])) {
            throw new \InvalidArgumentException('Unknown section.');
        }
        if (!in_array($status, ['checked', 'not_required', 'needs_attention'], true)) {
            throw new \InvalidArgumentException('Unknown status.');
        }
        if (!empty($it['locked'])) {
            throw new \RuntimeException('This inquiry is read-only: it is the record of what was sold.');
        }
        $id    = (int) $it['id'];
        $state = $this->state($it, $cost, true);
        $sec   = $state['sections'][$section];
        $label = self::SECTIONS[$section];
        if ($status === 'checked') {
            if ($sec['count'] === 0) {
                throw new \RuntimeException($label . ' has nothing on it to check. Add what the tour needs, or mark it Not required.');
            }
            if ($sec['incomplete']) {
                $n = count($sec['issues']) ?: $sec['open_lines'];
                throw new \RuntimeException($label . ' still has ' . $n . ' item' . ($n === 1 ? '' : 's') . ' to complete (missing details or rates). Complete them first, then mark it Checked.');
            }
        }
        if ($status === 'not_required' && $sec['count'] > 0) {
            throw new \RuntimeException($label . ' has ' . $sec['count'] . ' line' . ($sec['count'] === 1 ? '' : 's') . ' on this inquiry, so it cannot be Not required. Remove them first, or check the section.');
        }
        $before = $sec['status'];
        if ($status === 'needs_attention') {
            $this->db->query('DELETE FROM tf_intake_check WHERE intake_id = ? AND section = ?', [$id, $section]);
        } else {
            $sig = self::sign($section, $sec['lines'], $sec['issues'], $section === 'transfers' ? ($sec['airport'] ?? []) : []);
            $this->db->query(
                'INSERT INTO tf_intake_check (intake_id, section, status, sig, by_user_id, by_name, `at`) VALUES (?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), sig = VALUES(sig), by_user_id = VALUES(by_user_id), by_name = VALUES(by_name), `at` = VALUES(`at`)',
                [$id, $section, $status, $sig, $this->userId, mb_substr((string) ($this->name ?? $this->user), 0, 190), date('Y-m-d H:i:s')]
            );
        }
        $words = ['checked' => 'Checked', 'not_required' => 'Not required', 'needs_attention' => 'Needs attention'];
        try {
            (new Activity($this->db, $this->user, $this->userId, $this->name))->record('inquiry', $id, $id, null, 'section_check',
                'Before Tour Confirmation — ' . $label . ': ' . $words[$before] . ' → ' . $words[$status],
                [['field' => $label, 'from' => $words[$before], 'to' => $words[$status]]]);
        } catch (\Throwable $e) {
            // the history is best-effort here, as elsewhere; the status itself is saved
        }
        return $this->state($it, $cost, $seesTotals);
    }
}
