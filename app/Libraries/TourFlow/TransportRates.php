<?php

namespace App\Libraries\TourFlow;

/**
 * What a kilometre costs, by vehicle size — Master Files → Transport Rates.
 *
 * Three things this class is careful about, because each is a way a quotation goes
 * wrong quietly:
 *
 * Nothing here changes a rate on its own. Every figure is typed by a person, and the
 * only automatic thing that ever happens to it is that a newer rate, typed by a person,
 * takes over on the date that person chose. There is no feed, no import, no adjustment.
 *
 * A rate is never overwritten. Setting a new one closes the old period and opens a new
 * one, so the history says what was charged when and who decided it. Putting a typo
 * right is a different action from changing a price, and is logged as one, so the
 * history never shows a rate that was never actually charged.
 *
 * Old quotations keep their rate — but not because of this table. The inquiry copies
 * the figure onto itself the moment the vehicle is settled (tf_intake_vehicle.rate_id
 * and master_rate), so a quotation carries its own number and nothing done here can
 * move it. This table is the record and the source for NEW quotations, no more.
 *
 * The rate is per SIZE — sedan, van, mini coach — not per registered vehicle. The
 * vehicle master stays what it is, a register of the vehicles the company holds.
 *
 * Kilometre-based only. There is no hourly figure, no waiting charge and no minimum:
 * a size has one rate per kilometre and that is the whole of it. Kilometres charged
 * without being driven — a stay day, the garage run, a city tour — are the Mileage
 * Allowances master's business, and the driver's bata is the crew bata master's.
 */
final class TransportRates
{
    private $db;
    private string $user;

    public function __construct($db, string $user = '')
    {
        $this->db   = $db;
        $this->user = $user;
    }

    // =====================================================================
    //  The rate per kilometre
    // =====================================================================

    /**
     * tf-kmrate-r1 (13 Nov 2026, Shantha): every size carries TWO rates per kilometre, kept and revised apart —
     *   quote   USD per km — what an inquiry and a quotation are costed on;
     *   driver  LKR per km — what a driver's tour contract and payment are worked out on.
     * Neither is ever converted into the other. Each stays in force until a person sets a new one from a date; the
     * one it replaces is closed the day before and kept. A lane with no rate is "Not set" — the other lane is never
     * used in its place.
     */
    public const LANE_QUOTE  = 'quote';
    public const LANE_DRIVER = 'driver';
    public const LANES = [self::LANE_QUOTE => 'USD', self::LANE_DRIVER => 'LKR'];

    /** The LKR rate per kilometre a driver is paid on, in force for a size on a date. */
    public function currentDriver(string $classCode, ?string $on = null): ?array
    {
        return $this->current($classCode, $on, self::LANE_DRIVER);
    }

    /**
     * The rate in force for a size on a date. Today when no date is given.
     *
     * @return array{id:int, class_code:string, rate_km:float, currency:string, valid_from:string, valid_to:?string, reason:?string, set_by:?string}|null
     */
    public function current(string $classCode, ?string $on = null, string $lane = self::LANE_QUOTE): ?array
    {
        $on = self::dateOrNull($on) ?? date('Y-m-d');
        try {
            $r = $this->db->query(
                'SELECT * FROM tf_transport_rate
                 WHERE class_code = ? AND currency = ? AND valid_from <= ? AND (valid_to IS NULL OR valid_to >= ?)
                 ORDER BY valid_from DESC, id DESC LIMIT 1',
                [trim($classCode), self::LANES[$lane] ?? 'USD', $on, $on]
            )->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        return $r ? self::row($r) : null;
    }

    /**
     * Every size, with whatever rate is current, what the vehicle master says for
     * comparison, and how many quotations have used the current rate.
     *
     * @return array<int, array<string, mixed>>
     */
    public function board(): array
    {
        $classes = $this->db->query('SELECT * FROM tf_vehicle_class ORDER BY sort_order, max_pax')->getResultArray();
        $legacy  = $this->legacyRates();
        $out     = [];
        foreach ($classes as $c) {
            $code = (string) $c['code'];
            $cur  = $this->current($code);
            $next = $this->db->query(
                "SELECT * FROM tf_transport_rate WHERE class_code = ? AND currency = 'USD' AND valid_from > CURDATE() ORDER BY valid_from LIMIT 1",
                [$code]
            )->getRowArray();
            $curD  = $this->currentDriver($code);
            $nextD = $this->db->query(
                "SELECT * FROM tf_transport_rate WHERE class_code = ? AND currency = 'LKR' AND valid_from > CURDATE() ORDER BY valid_from LIMIT 1",
                [$code]
            )->getRowArray();
            $out[] = [
                'code'        => $code,
                'name'        => (string) $c['name'],
                'seats'       => ($c['seats'] === null || $c['seats'] === '') ? null : (int) $c['seats'],
                'max_pax'     => (int) $c['max_pax'],
                'active'      => (int) $c['active'] === 1,
                'current'     => $cur,
                'upcoming'    => $next ? self::row($next) : null,
                // tf-kmrate-r1: the driver's lane, LKR per km — null is "Not set", never the USD figure
                'driver'          => $curD,
                'driver_upcoming' => $nextD ? self::row($nextD) : null,
                'used_on'     => $cur ? $this->usedOn((int) $cur['id']) : 0,
                'history_n'   => (int) ($this->db->query('SELECT COUNT(*) n FROM tf_transport_rate WHERE class_code = ?', [$code])->getRowArray()['n'] ?? 0),
                // what the vehicle master holds for a vehicle of this kind, shown as a hint
                // and never copied in — a person decides whether that figure is the rate
                'vehicle_master' => $this->legacyFor($legacy, (string) ($c['match_words'] ?? '')),
            ];
        }
        return $out;
    }

    /**
     * Every rate a size has ever had, newest first, with how many quotations each was
     * used on.
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(string $classCode): array
    {
        $rows = $this->db->query(
            'SELECT * FROM tf_transport_rate WHERE class_code = ? ORDER BY valid_from DESC, id DESC',
            [trim($classCode)]
        )->getResultArray();
        $today = date('Y-m-d');
        $out   = [];
        foreach ($rows as $r) {
            $row = self::row($r);
            $row['lane']    = $row['currency'] === 'LKR' ? self::LANE_DRIVER : self::LANE_QUOTE;
            $row['used_on'] = $this->usedOn((int) $r['id']);
            $row['state']   = $row['valid_from'] > $today ? 'upcoming'
                : (($row['valid_to'] === null || $row['valid_to'] >= $today) ? 'current' : 'superseded');
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Set a size's rate from a date. The rate it replaces is closed the day before,
     * never deleted. A date in the future is allowed — next season's rate loaded now
     * takes over on its own when the day comes, with no further action from anybody.
     *
     * @return array<string, mixed> the new row
     */
    public function set(string $classCode, $rate, string $currency, ?string $from, ?string $reason): array
    {
        $code = trim($classCode);
        if (!$this->db->query('SELECT code FROM tf_vehicle_class WHERE code = ?', [$code])->getRowArray()) {
            throw new \InvalidArgumentException('That is not a vehicle size this system holds.');
        }
        $r = self::money($rate);
        if ($r === null) {
            throw new \InvalidArgumentException('Enter the rate per kilometre.');
        }
        if ($r <= 0) {
            throw new \InvalidArgumentException('A rate per kilometre has to be more than nothing.');
        }
        $cur = strtoupper(trim($currency)) ?: 'LKR';
        if (!in_array($cur, ['LKR', 'USD'], true)) {
            throw new \InvalidArgumentException('The currency is LKR or USD.');
        }
        $from = self::dateOrNull($from) ?? date('Y-m-d');

        // A rate cannot start before one that already starts later: the periods would
        // overlap and two rates would be "current" on the same day.
        // (tf-kmrate-r1: within its own lane only — the USD and the LKR rate of a size never block each other)
        $later = $this->db->query(
            'SELECT valid_from FROM tf_transport_rate WHERE class_code = ? AND currency = ? AND valid_from >= ? ORDER BY valid_from LIMIT 1',
            [$code, $cur, $from]
        )->getRowArray();
        if ($later) {
            throw new \InvalidArgumentException(
                'A ' . $cur . ' rate for this size already starts on ' . $later['valid_from'] . '. Choose a later date, or use Correct to put a mistake right.'
            );
        }

        // the rate this one follows, for the history line
        $prev = $this->db->query('SELECT rate_km, valid_from FROM tf_transport_rate WHERE class_code = ? AND currency = ? AND valid_from < ? ORDER BY valid_from DESC LIMIT 1', [$code, $cur, $from])->getRowArray();

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        // close whatever is open, the day before the new one starts
        $this->db->query(
            'UPDATE tf_transport_rate SET valid_to = DATE_SUB(?, INTERVAL 1 DAY)
             WHERE class_code = ? AND currency = ? AND (valid_to IS NULL OR valid_to >= ?) AND valid_from < ?',
            [$from, $code, $cur, $from, $from]
        );
        $this->db->query(
            'INSERT INTO tf_transport_rate (class_code, rate_km, currency, valid_from, valid_to, reason, set_by, set_at)
             VALUES (?,?,?,?,NULL,?,?,?)',
            [$code, $r, $cur, $from, self::cut($reason, 200), $this->user, $now]
        );
        $id = (int) $this->db->insertID();
        // tf-kmrate-r1: a new rate saved on a retired size puts the size back in use — its history is untouched
        $wasOff = (int) ($this->db->query('SELECT active FROM tf_vehicle_class WHERE code = ?', [$code])->getRowArray()['active'] ?? 1) !== 1;
        if ($wasOff) {
            $this->db->query('UPDATE tf_vehicle_class SET active = 1 WHERE code = ?', [$code]);
        }
        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('The rate could not be saved; nothing was changed.');
        }
        if ($wasOff) {
            Activity::master($this->db, $this->user, 'vehicle_class', 0, 'Vehicle sizes: ' . $code . ' reinstated (a new rate was saved on it)', [['what' => $code . ' active', 'from' => 0, 'to' => 1]]);
        }
        Activity::master($this->db, $this->user, 'transport_rate', $id, 'Transport Rates: ' . $code . ' ' . ($prev ? (float) $prev['rate_km'] . ' → ' : 'set at ') . $r . ' ' . $cur . ' per km from ' . $from . ($reason ? ' — ' . trim($reason) : ''),
            [['what' => $code . ' rate per km', 'from' => $prev['rate_km'] ?? null, 'to' => $r], ['what' => $code . ' valid from', 'from' => $prev['valid_from'] ?? null, 'to' => $from]]);
        foreach ($this->history($code) as $h) {
            if ((int) $h['id'] === $id) {
                return $h + ['reinstated' => $wasOff];
            }
        }
        return ['id' => $id, 'valid_from' => $from, 'currency' => $cur, 'reinstated' => $wasOff];
    }

    /**
     * Put a typo right, in place. Logged as a correction — the figure it replaced, who
     * fixed it and when — so it never reads as a price change. Quotations that already
     * copied the wrong figure are told about, not touched: their number is theirs.
     *
     * @return array<string, mixed>
     */
    public function correct(int $id, $rate, ?string $reason = null): array
    {
        $row = $this->db->query('SELECT * FROM tf_transport_rate WHERE id = ?', [$id])->getRowArray();
        if (!$row) {
            throw new \InvalidArgumentException('That rate is not on file.');
        }
        $r = self::money($rate);
        if ($r === null || $r <= 0) {
            throw new \InvalidArgumentException('Enter the corrected rate per kilometre.');
        }
        $this->db->query(
            'UPDATE tf_transport_rate SET corrected_from = ?, rate_km = ?, corrected_by = ?, corrected_at = ?,
                    reason = COALESCE(?, reason)
             WHERE id = ?',
            [(float) $row['rate_km'], $r, $this->user, date('Y-m-d H:i:s'), self::cut($reason, 200), $id]
        );
        Activity::master($this->db, $this->user, 'transport_rate', $id, 'Transport Rates: ' . $row['class_code'] . ' corrected ' . (float) $row['rate_km'] . ' → ' . $r . ' (from ' . $row['valid_from'] . ')' . ($reason ? ' — ' . trim($reason) : ''),
            [['what' => $row['class_code'] . ' rate per km (correction)', 'from' => $row['rate_km'], 'to' => $r]]);
        $out = $this->history((string) $row['class_code']);
        foreach ($out as $h) {
            if ((int) $h['id'] === $id) {
                return $h;
            }
        }
        return $out[0] ?? [];
    }

    /**
     * How many passengers a size carries on the ladder ("carries up to") — the band the vehicle
     * planner and the pax matrix read to choose a size for a party (schema 31). Management's
     * figure, changed on purpose and logged with what it was; never more than the seats the size
     * has, since the spare seats are the legroom, the luggage and the guide. Vehicles already on
     * inquiries keep what they have; the next vehicle chosen follows the new band.
     *
     * @return array<int, array<string, mixed>> the board
     */
    public function setMaxPax(string $classCode, $maxPax): array
    {
        $code = trim($classCode);
        $row  = $this->db->query('SELECT * FROM tf_vehicle_class WHERE code = ?', [$code])->getRowArray();
        if (!$row) {
            throw new \InvalidArgumentException('That is not a vehicle size this system holds.');
        }
        if ($maxPax === null || $maxPax === '' || !is_numeric($maxPax) || (float) $maxPax !== (float) (int) $maxPax || (int) $maxPax < 1 || (int) $maxPax > 99) {
            throw new \InvalidArgumentException('"Carries up to" is a whole number of passengers, from 1 to 99.');
        }
        $n     = (int) $maxPax;
        $seats = ($row['seats'] === null || $row['seats'] === '') ? null : (int) $row['seats'];
        if ($seats !== null && $n > $seats) {
            throw new \InvalidArgumentException('A ' . $row['name'] . ' seats ' . $seats . ', so it cannot carry ' . $n . ' passengers.');
        }
        $was = (int) $row['max_pax'];
        if ($was !== $n) {
            $this->db->query('UPDATE tf_vehicle_class SET max_pax = ? WHERE code = ?', [$n, $code]);
            Activity::master($this->db, $this->user, 'vehicle_class', 0, 'Vehicle sizes: ' . $code . ' (' . $row['name'] . ') carries up to ' . $was . ' → ' . $n . ' passengers',
                [['what' => $code . ' carries up to', 'from' => $was, 'to' => $n]]);
        }
        return $this->board();
    }

    /** Stop offering a size, or start again. Rates and past quotations are untouched. */
    public function setActive(string $classCode, bool $active): void
    {
        $was = $this->db->query('SELECT active FROM tf_vehicle_class WHERE code = ?', [trim($classCode)])->getRowArray();
        $this->db->query('UPDATE tf_vehicle_class SET active = ? WHERE code = ?', [$active ? 1 : 0, trim($classCode)]);
        Activity::master($this->db, $this->user, 'vehicle_class', 0, 'Vehicle sizes: ' . trim($classCode) . ($active ? ' reinstated' : ' retired'), [['what' => trim($classCode) . ' active', 'from' => $was['active'] ?? null, 'to' => $active ? 1 : 0]]);
    }

    /** How many inquiry vehicle lines copied this exact rate. */
    public function usedOn(int $rateId): int
    {
        try {
            return (int) ($this->db->query('SELECT COUNT(*) n FROM tf_intake_vehicle WHERE rate_id = ?', [$rateId])->getRowArray()['n'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Open inquiries still carrying a rate that is no longer current for their size.
     * Reported so a person can decide to re-price them; nothing is re-priced by itself.
     *
     * @return array<int, array{id:int, inquiry_no:string, class_code:string, km_rate:?float, current:?float}>
     */
    public function staleOpen(): array
    {
        try {
            $rows = $this->db->query(
                "SELECT i.id, i.inquiry_no, v.class_code, v.km_rate, v.rate_id
                 FROM tf_intake_vehicle v JOIN tf_intake i ON i.id = v.intake_id
                 WHERE i.status IN ('new','working','quoted') AND v.rate_by = 'master'"
            )->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $cur = $this->current((string) $r['class_code']);
            // tf-kmrate-r2: a rate re-saved at the same amount is not an "earlier rate" — only a different figure is reported
            if ($cur !== null && (int) $cur['id'] !== (int) $r['rate_id'] && ($r['km_rate'] === null || abs((float) $cur['rate_km'] - (float) $r['km_rate']) > 0.004)) {
                $out[] = ['id' => (int) $r['id'], 'inquiry_no' => (string) $r['inquiry_no'], 'class_code' => (string) $r['class_code'],
                          'km_rate' => $r['km_rate'] === null ? null : (float) $r['km_rate'], 'current' => (float) $cur['rate_km']];
            }
        }
        return $out;
    }

    // =====================================================================

    /**
     * What the vehicle master says, per kind of vehicle. Shown beside an empty rate as
     * a hint — "your vehicle master holds 80 for a sedan" — and never copied in. Its
     * column is labelled Km/Hour Rate, so whether a figure there is per kilometre is
     * for a person to say.
     *
     * @return array<int, array{type:string, km_rate:?float, seats:int}>
     */
    private function legacyRates(): array
    {
        try {
            if (!$this->db->query("SHOW TABLES LIKE 'vehicle'")->getResultArray()) {
                return [];
            }
            $rows = $this->db->query('SELECT type, passenger_count, km_rate FROM vehicle')->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        return array_map(static fn ($r) => [
            'type' => trim((string) $r['type']), 'seats' => (int) ($r['passenger_count'] ?? 0),
            'km_rate' => ($r['km_rate'] === null || $r['km_rate'] === '' || (float) $r['km_rate'] <= 0) ? null : (float) $r['km_rate'],
        ], $rows);
    }

    /** @param array<int, array{type:string, km_rate:?float, seats:int}> $legacy */
    private function legacyFor(array $legacy, string $words): ?array
    {
        $ws = array_values(array_filter(array_map('trim', explode(',', mb_strtolower($words)))));
        $hits = [];
        foreach ($legacy as $v) {
            $t = mb_strtolower($v['type']);
            foreach ($ws as $w) {
                if ($w !== '' && str_contains($t, $w) && $v['km_rate'] !== null) {
                    $hits[] = $v;
                    break;
                }
            }
        }
        if ($hits === []) {
            return null;
        }
        $rates = array_unique(array_map(static fn ($h) => $h['km_rate'], $hits));
        return ['types' => array_values(array_unique(array_column($hits, 'type'))), 'rates' => array_values($rates)];
    }

    private function meta(string $k): ?string
    {
        try {
            $r = $this->db->query('SELECT v FROM tf_meta WHERE k = ?', [$k])->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        return $r['v'] ?? null;
    }

    /** @param array<string, mixed> $r */
    private static function row(array $r): array
    {
        return [
            'id'             => (int) $r['id'],
            'class_code'     => (string) $r['class_code'],
            'rate_km'        => (float) $r['rate_km'],
            'currency'       => (string) $r['currency'],
            'valid_from'     => (string) $r['valid_from'],
            'valid_to'       => $r['valid_to'] ?: null,
            'reason'         => $r['reason'] ?: null,
            'set_by'         => $r['set_by'] ?: null,
            'set_at'         => $r['set_at'] ?: null,
            'corrected_from' => ($r['corrected_from'] === null || $r['corrected_from'] === '') ? null : (float) $r['corrected_from'],
            'corrected_by'   => $r['corrected_by'] ?: null,
            'corrected_at'   => $r['corrected_at'] ?: null,
        ];
    }

    private static function money($v): ?float
    {
        if ($v === null || $v === '' || !is_numeric($v)) {
            return null;
        }
        return round((float) $v, 2);
    }

    private static function dateOrNull($v): ?string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : null;
    }

    private static function cut($v, int $len): ?string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        return $s === '' ? null : mb_substr($s, 0, $len);
    }
}
