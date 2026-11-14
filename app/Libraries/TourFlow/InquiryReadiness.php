<?php

namespace App\Libraries\TourFlow;

/**
 * The inquiry's traffic light (tf-signal-r1, 14 Nov 2026): is everything a quotation needs on the inquiry?
 *
 *   red     Not started  — nothing a quotation needs has been entered yet
 *   yellow  Incomplete   — partly done, or required information is missing
 *   green   Ready        — every required item is there; the quotation may be sent
 *
 * What is REQUIRED is exactly what the costing already refuses to quote without (QuoteService::costing()['blockers']:
 * the inquiry's details, every night's hotel and valid rate, the meal plans, the rooms, the vehicle, the legs and their
 * mileage, the transfers, the excursions, the priced activities, the markup), plus one thing only a person can say:
 * that each day's activities were looked at. The costing's warnings are OPTIONAL and never stop the light going green.
 *
 * A day's activities:
 *   red     Not started  — nothing entered and nobody has said none is needed
 *   yellow  Incomplete   — activities entered but not checked, or one of them has no rate / is not placed
 *   green   Checked      — a person checked the day's activities, or ticked "No activities required" on an empty day
 * The tick is kept with a signature of the day's lines: add, change or remove an activity and the day is yellow again.
 * Opening a panel never changes a status. Nothing here sends anything: green only ALLOWS Send Quotation.
 */
class InquiryReadiness
{
    private const MEAL_KINDS = ['lunch', 'dinner'];

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
        $this->db->query('CREATE TABLE IF NOT EXISTS tf_intake_day_check (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            intake_id INT UNSIGNED NOT NULL,
            day_no INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            sig CHAR(32) NOT NULL,
            by_user_id INT NULL,
            by_name VARCHAR(190) NULL,
            `at` DATETIME NOT NULL,
            UNIQUE KEY uq_intake_day (intake_id, day_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    /** Which panel a costing blocker is fixed on (the tab the "missing items" link opens). */
    private static function panelOf(string $code, array $svcMeal): array
    {
        if (preg_match('/^svc_(?:rate_|master_|rupees_|dup_)?(\d+)$/', $code, $m)) {
            return !empty($svcMeal[(int) $m[1]]) ? ['acts', 'Activities · Meals'] : ['acts', 'Activities'];
        }
        if ($code === 'svc_total' || preg_match('/^meal_/', $code)) {
            return ['acts', 'Activities'];
        }
        if (preg_match('/^exc_/', $code)) {
            return ['trans', 'Airport Transfers & Excursions'];
        }
        if (preg_match('/^(rate_|hotel_|group_rooms_|departure_row_)/', $code) || in_array($code, ['rooms_mix', 'nights_missing', 'no_nights', 'rates_unreviewed'], true)) {
            return ['itin', 'Hotels'];
        }
        if (preg_match('/^(km_|transfer_|crew_|accom_)/', $code) || in_array($code, ['no_vehicle', 'no_legs', 'no_usd_rate'], true)) {
            return ['trans', 'Airport Transfers & Excursions'];
        }
        if (in_array($code, ['pax', 'child_review', 'child_count', 'groups_pax'], true)) {
            return ['pax', 'Passengers'];
        }
        if ($code === 'pricing' || preg_match('/^pax_matrix/', $code)) {
            return ['quote', 'Quotation & Costs'];
        }
        return ['hotels', 'Itinerary & Services'];
    }

    /** The days of the programme: every night, and the departure day when it is a day of its own. */
    private static function programmeDays(array $it): array
    {
        $out = [];
        foreach ((array) ($it['days'] ?? []) as $d) {
            $out[(int) $d['day_no']] = ['day_no' => (int) $d['day_no'], 'date' => $d['day_date'] ?? null, 'departure' => false];
        }
        $dep = $it['departure_day'] ?? null;
        if (is_array($dep) && !empty($dep['day_no']) && !isset($out[(int) $dep['day_no']])) {
            $out[(int) $dep['day_no']] = ['day_no' => (int) $dep['day_no'], 'date' => $dep['date'] ?? null, 'departure' => true];
        }
        ksort($out);
        return $out;
    }

    /** Each day's activity lines (entrances, visits, safaris, excursions…) as the costing reads them; lunches and dinners are Meals. */
    private static function linesByDay(array $cost): array
    {
        $svcMeal = [];
        $byDay   = [];
        foreach ((array) ($cost['services'] ?? []) as $s) {
            $meal = in_array((string) ($s['kind'] ?? ''), self::MEAL_KINDS, true) || !empty($s['meal']);
            $svcMeal[(int) $s['id']] = $meal;
            if ($meal) {
                continue;   // lunches and dinners are Meals; they are costed (and block) through the costing, not ticked per day
            }
            $free = ($s['pay_mode'] ?? '') === 'no_charge' || !empty($s['free']);
            $byDay[(int) $s['day_no']][] = ['t' => 'svc', 'id' => (int) $s['id'], 'title' => (string) $s['title'], 'kind' => (string) ($s['kind'] ?? ''), 'pay' => (string) ($s['pay_mode'] ?? ''),
                'qty' => $s['qty'] ?? null, 'unit' => $s['unit_usd'] ?? null, 'amount' => $free ? 0.0 : ($s['amount_usd'] ?? null), 'free' => $free,
                'missing' => ($s['state'] ?? 'ok') !== 'ok' || (!$free && ($s['amount_usd'] ?? null) === null), 'why' => $s['why'] ?? null];
        }
        foreach ((array) ($cost['excursions'] ?? []) as $x) {
            $byDay[(int) $x['day_no']][] = ['t' => 'exc', 'id' => 0, 'title' => (string) $x['name'], 'kind' => 'excursion', 'pay' => '', 'qty' => $x['qty'] ?? null, 'unit' => $x['rate'] ?? null,
                'amount' => $x['amount_usd'] ?? null, 'free' => false, 'missing' => ($x['state'] ?? 'ok') !== 'ok' || ($x['amount_usd'] ?? null) === null, 'why' => $x['why'] ?? null];
        }
        return [$byDay, $svcMeal];
    }

    public function state(array $it, array $cost): array
    {
        $id = (int) $it['id'];
        [$byDay, $svcMeal] = self::linesByDay($cost);

        $stored = [];
        foreach ($this->db->query('SELECT * FROM tf_intake_day_check WHERE intake_id = ?', [$id])->getResultArray() as $r) {
            $stored[(int) $r['day_no']] = $r;
        }

        $days  = [];
        $items = [];
        $count = ['red' => 0, 'yellow' => 0, 'green' => 0];
        foreach (self::programmeDays($it) as $n => $d) {
            $lines   = $byDay[$n] ?? [];
            $missing = count(array_filter($lines, static fn ($l) => $l['missing']));
            $sig     = self::sign($n, $lines);
            $row     = $stored[$n] ?? null;
            $ticked  = $row && hash_equals((string) $row['sig'], $sig) ? (string) $row['status'] : null;
            $changed = $row && $ticked === null;
            if ($ticked !== null && $missing === 0 && (($ticked === 'checked' && $lines) || ($ticked === 'none' && !$lines))) {
                $light = 'green';
                $word  = $ticked === 'none' ? 'No activities required' : 'Checked';
                $text  = $ticked === 'none' ? 'Checked: nothing is needed on this day' : count($lines) . ' activit' . (count($lines) === 1 ? 'y' : 'ies') . ' checked';
            } elseif (!$lines) {
                $light = 'red';
                $word  = 'Not started';
                $text  = trim((string) ($it['source_text'] ?? '')) !== '' ? 'Activities not imported' : 'No activities entered';
            } else {
                $light = 'yellow';
                $word  = 'Incomplete';
                $text  = $missing ? $missing . ' of ' . count($lines) . ' without a rate or not placed' : count($lines) . ' activit' . (count($lines) === 1 ? 'y' : 'ies') . ' entered — not checked yet';
            }
            if ($changed) {
                $text .= ' (changed since it was checked)';
            }
            $count[$light]++;
            $days[] = ['day_no' => $n, 'date' => $d['date'], 'departure' => $d['departure'], 'light' => $light, 'word' => $word, 'text' => $text, 'count' => count($lines), 'missing' => $missing,
                'titles' => array_map(static fn ($l) => $l['title'], $lines), 'changed' => $changed,
                'was' => $row ? ['status' => $row['status'], 'by' => $row['by_name'], 'at' => $row['at']] : null,
                'can_check' => count($lines) > 0 && $missing === 0, 'can_none' => count($lines) === 0];
            if ($light !== 'green') {
                $items[] = ['code' => 'act_day_' . $n, 'panel' => 'acts', 'panel_label' => 'Activities', 'day_no' => $n,
                    'label' => 'Day ' . $n . ($d['date'] ? ' (' . date('j M', strtotime((string) $d['date'])) . ')' : '') . ': activities — ' . ($lines
                        ? ($missing ? $missing . ' without a rate or not placed; complete them, then check the day' : 'entered but not checked')
                        : 'none entered; add them, or tick "No activities required"')];
            }
        }
        $actLight = $count['yellow'] > 0 || ($count['red'] > 0 && $count['green'] > 0) ? 'yellow' : ($count['red'] > 0 ? 'red' : 'green');
        if (!$days) {
            $actLight = 'red';
        }

        $req = [];
        foreach ((array) ($cost['blockers'] ?? []) as $b) {
            [$panel, $label] = self::panelOf((string) ($b['code'] ?? ''), $svcMeal);
            $req[] = ['code' => (string) $b['code'], 'label' => (string) $b['label'], 'panel' => $panel, 'panel_label' => $label];
        }
        $items = array_merge($req, $items);

        $started = false;
        foreach ((array) ($it['days'] ?? []) as $d) {
            if (trim((string) ($d['hotel_name_raw'] ?? '')) !== '') {
                $started = true;
            }
        }
        $started = $started || !empty($it['vehicles']) || !empty($it['services']) || !empty($it['excursions']) || $count['green'] > 0 || $count['yellow'] > 0;

        $light = !$items ? 'green' : ($started ? 'yellow' : 'red');
        return [
            'light' => $light,
            'title' => $light === 'green' ? 'Ready — quotation can be sent' : ($light === 'red' ? 'Not started — quotation blocked' : 'Incomplete — quotation blocked'),
            'ok'    => $light === 'green',
            'items' => $items, 'missing' => count($items),
            'days'  => $days, 'activities' => $actLight,
            'optional' => array_values(array_map(static fn ($w) => ['code' => (string) ($w['code'] ?? ''), 'label' => (string) ($w['label'] ?? '')], array_filter((array) ($cost['warnings'] ?? []), static fn ($w) => empty($w['internal'])))),
            'locked' => !empty($it['locked']),
        ];
    }

    private static function sign(int $dayNo, array $lines): string
    {
        return md5((string) json_encode([$dayNo, $lines], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    /** A person ticks a day's activities: checked, none (No activities required), or clear. Never changes anything else. */
    public function setDay(array $it, array $cost, int $dayNo, string $status): array
    {
        if (!in_array($status, ['checked', 'none', 'clear'], true)) {
            throw new \InvalidArgumentException('Unknown status.');
        }
        if (!empty($it['locked'])) {
            throw new \RuntimeException('This inquiry is read-only: it is the record of what was sold.');
        }
        $id  = (int) $it['id'];
        $st  = $this->state($it, $cost);
        $day = null;
        foreach ($st['days'] as $d) {
            if ((int) $d['day_no'] === $dayNo) {
                $day = $d;
            }
        }
        if (!$day) {
            throw new \InvalidArgumentException('Day ' . $dayNo . ' is not on this inquiry.');
        }
        if ($status === 'checked') {
            if ($day['count'] === 0) {
                throw new \RuntimeException('Day ' . $dayNo . ' has no activities to check. Add them, or tick "No activities required".');
            }
            if ($day['missing'] > 0) {
                throw new \RuntimeException('Day ' . $dayNo . ' has ' . $day['missing'] . ' activit' . ($day['missing'] === 1 ? 'y' : 'ies') . ' without a rate or not placed. Complete ' . ($day['missing'] === 1 ? 'it' : 'them') . ' first.');
            }
        }
        if ($status === 'none' && $day['count'] > 0) {
            throw new \RuntimeException('Day ' . $dayNo . ' has ' . $day['count'] . ' activit' . ($day['count'] === 1 ? 'y' : 'ies') . ' on it, so it cannot be "No activities required". Check the day instead, or remove them.');
        }
        if ($status === 'clear') {
            $this->db->query('DELETE FROM tf_intake_day_check WHERE intake_id = ? AND day_no = ?', [$id, $dayNo]);
        } else {
            [$byDay] = self::linesByDay($cost);
            $lines = $byDay[$dayNo] ?? [];
            $this->db->query(
                'INSERT INTO tf_intake_day_check (intake_id, day_no, status, sig, by_user_id, by_name, `at`) VALUES (?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), sig = VALUES(sig), by_user_id = VALUES(by_user_id), by_name = VALUES(by_name), `at` = VALUES(`at`)',
                [$id, $dayNo, $status, self::sign($dayNo, $lines), $this->userId, mb_substr((string) ($this->name ?? $this->user), 0, 190), date('Y-m-d H:i:s')]
            );
        }
        $words = ['checked' => 'Checked', 'none' => 'No activities required', 'clear' => 'Not checked'];
        try {
            (new Activity($this->db, $this->user, $this->userId, $this->name))->record('inquiry', $id, $id, null, 'day_check',
                'Activities, day ' . $dayNo . ': ' . $day['word'] . ' → ' . $words[$status], [['what' => 'Day ' . $dayNo . ' activities', 'from' => $day['word'], 'to' => $words[$status]]]);
        } catch (\Throwable $e) {
            // best effort
        }
        return $this->state($it, $cost);
    }

    /** Send Quotation is allowed only on green. Called again on the server immediately before anything is sent. */
    public function assertReady(array $it, array $cost): void
    {
        $st = $this->state($it, $cost);
        if (!$st['ok']) {
            $first = array_slice(array_map(static fn ($i) => $i['label'], $st['items']), 0, 3);
            throw new NotReadyToQuote('The quotation cannot be sent: this inquiry is ' . ($st['light'] === 'red' ? 'not started' : 'incomplete') . ' (' . $st['missing'] . ' item' . ($st['missing'] === 1 ? '' : 's')
                . ' still needed). ' . implode(' · ', $first) . ($st['missing'] > 3 ? ' · …' : '') . ' Nothing was sent.');
        }
    }
}
