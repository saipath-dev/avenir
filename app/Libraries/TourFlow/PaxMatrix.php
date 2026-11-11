<?php

namespace App\Libraries\TourFlow;

/**
 * The pax matrix (schema 31): what the tour costs, and what the client pays per person, for
 * each party size from one to ten — each size worked out on its own, from the masters. No
 * size is ever one total divided by another number of people.
 *
 * For each size:
 *
 *   rooms      the rooms Management keeps for that size (Pricing → Room basis by party size,
 *              tf_pax_rooms), priced night by night at the published single, double and triple
 *              rates copied beside each night — the same figures the detailed costing uses
 *   paid       each priced entrance and activity by how it is charged: per person × the party,
 *              per group once, per vehicle × the vehicles that size travels in
 *   transport  the vehicle the Transport Rates ladder gives for that size ("carries up to"),
 *              on the tour's charged kilometres, at the rate this inquiry already carries for
 *              that vehicle size or else the master's rate in force; the driver and guide, and
 *              their rooms, as on the inquiry — they do not change with the size of the party
 *
 * The inquiry's own party size is priced on the inquiry's own rooms and vehicle, so that row
 * is the detailed costing worked out a second time: QuoteService compares the two, and a
 * difference stops the quotation. Nothing is guessed: a size that needs a rate the masters do
 * not hold is left without a figure, with the reason, and the agent's table says "on request".
 *
 * Read-only: nothing here writes, except saveBasis, which Management uses to change a size's rooms.
 */
final class PaxMatrix
{
    /** The largest party size the room basis may hold a row for. */
    public const MAX_PAX = 20;

    private $db;
    private VehiclePlanner $planner;
    private ?array $basis = null;
    private ?bool $available = null;
    /** @var array<string, array|null> the master's rate in force, per vehicle size, read once */
    private array $masterRates = [];

    public function __construct($db, VehiclePlanner $planner)
    {
        $this->db      = $db;
        $this->planner = $planner;
    }

    public function available(): bool
    {
        if ($this->available !== null) {
            return $this->available;
        }
        try {
            return $this->available = (bool) $this->db->query("SHOW TABLES LIKE 'tf_pax_rooms'")->getResultArray();
        } catch (\Throwable $e) {
            return $this->available = false;
        }
    }

    // =====================================================================
    //  The room basis: Management's, one row a party size
    // =====================================================================

    /**
     * The rooms each party size is costed on, smallest size first, keyed by the size.
     *
     * @return array<int, array{pax:int, sgl:int, dbl:int, twn:int, tpl:int, rooms:int, sleeps:int, text:string, updated_by:?string, updated_at:?string}>
     */
    public function basis(): array
    {
        if ($this->basis !== null) {
            return $this->basis;
        }
        $this->basis = [];
        if (!$this->available()) {
            return $this->basis;
        }
        foreach ($this->db->query('SELECT * FROM tf_pax_rooms WHERE pax BETWEEN 1 AND ? ORDER BY pax', [self::MAX_PAX])->getResultArray() as $r) {
            $mix = ['sgl' => (int) $r['rooms_sgl'], 'dbl' => (int) $r['rooms_dbl'], 'twn' => (int) $r['rooms_twn'], 'tpl' => (int) $r['rooms_tpl']];
            $this->basis[(int) $r['pax']] = ['pax' => (int) $r['pax']] + $mix + [
                'rooms' => array_sum($mix), 'sleeps' => self::sleeps($mix), 'text' => self::roomsText($mix),
                'updated_by' => ($r['updated_by'] ?? '') !== '' ? (string) $r['updated_by'] : null,
                'updated_at' => ($r['updated_at'] ?? '') !== '' ? (string) $r['updated_at'] : null,
            ];
        }
        return $this->basis;
    }

    /**
     * Change the rooms one party size is costed on — Management's decision (the caller checks
     * the Pricing privilege). The rooms must sleep exactly that many people. Logged with what
     * it was. Quotations already frozen keep the matrix they were frozen with.
     *
     * @return array<int, array<string, mixed>> the basis as it now stands
     */
    public function saveBasis(int $pax, array $r, string $user, ?int $actorId = null, ?string $actorName = null): array
    {
        if (!$this->available()) {
            throw new \RuntimeException('The room basis is not installed yet: open Tour Inquiry once as the administrator so the installer adds it.');
        }
        $row = $this->db->query('SELECT * FROM tf_pax_rooms WHERE pax = ?', [$pax])->getRowArray();
        if (!$row) {
            throw new \InvalidArgumentException('The matrix has no row for ' . $pax . ' pax.');
        }
        $mix = [];
        foreach (['sgl', 'dbl', 'twn', 'tpl'] as $k) {
            $v = $r['rooms_' . $k] ?? ($r[$k] ?? 0);
            if ($v === null || $v === '') {
                $v = 0;
            }
            if (!is_numeric($v) || (float) $v < 0 || (float) $v !== (float) (int) $v || (int) $v > self::MAX_PAX) {
                throw new \InvalidArgumentException('Each room count is a whole number, nought or more.');
            }
            $mix[$k] = (int) $v;
        }
        $sleeps = self::sleeps($mix);
        if ($sleeps !== $pax) {
            throw new \InvalidArgumentException(ucfirst(self::roomsText($mix)) . ' sleep' . ($sleeps === 1 && array_sum($mix) === 1 ? 's' : '') . ' ' . $sleeps . ', not ' . $pax
                . '. The rooms for ' . $pax . ' pax must sleep exactly ' . $pax . ': a single sleeps 1, a double or a twin 2, a triple 3.');
        }
        $was = ['sgl' => (int) $row['rooms_sgl'], 'dbl' => (int) $row['rooms_dbl'], 'twn' => (int) $row['rooms_twn'], 'tpl' => (int) $row['rooms_tpl']];
        if ($was !== $mix) {
            $this->db->query(
                'UPDATE tf_pax_rooms SET rooms_sgl = ?, rooms_dbl = ?, rooms_twn = ?, rooms_tpl = ?, updated_at = ?, updated_by = ? WHERE pax = ?',
                [$mix['sgl'], $mix['dbl'], $mix['twn'], $mix['tpl'], date('Y-m-d H:i:s'), mb_substr($user, 0, 100), $pax]
            );
            Activity::master($this->db, $user, 'pax_rooms', $pax, 'Pax matrix room basis: ' . $pax . ' pax ' . self::roomsText($was) . ' → ' . self::roomsText($mix),
                [['what' => $pax . ' pax rooms', 'from' => self::roomsText($was), 'to' => self::roomsText($mix)]], $actorId, $actorName);
        }
        $this->basis = null;
        return $this->basis();
    }

    /** How many a set of rooms sleeps: a single 1, a double or twin 2, a triple 3. */
    public static function sleeps(array $m): int
    {
        return (int) ($m['sgl'] ?? 0) + 2 * (int) ($m['dbl'] ?? 0) + 2 * (int) ($m['twn'] ?? 0) + 3 * (int) ($m['tpl'] ?? 0);
    }

    /** The rooms in words, as the agent reads them: "1 double + 1 triple". */
    public static function roomsText(array $m): string
    {
        $out = [];
        foreach ([['dbl', 'double', 'doubles'], ['twn', 'twin', 'twins'], ['tpl', 'triple', 'triples'], ['sgl', 'single', 'singles']] as [$k, $one, $many]) {
            $n = (int) ($m[$k] ?? 0);
            if ($n > 0) {
                $out[] = $n . ' ' . ($n === 1 ? $one : $many);
            }
        }
        return $out !== [] ? implode(' + ', $out) : 'no rooms';
    }

    // =====================================================================
    //  The matrix
    // =====================================================================

    /**
     * Every party size of the basis, each costed on its own.
     *
     * @param array<string, mixed> $it   the inquiry as IntakeService::get() returns it
     * @param float|null           $rate rupees per dollar (the costing's own)
     * @param float|null           $pct  the markup on cost (the costing's own), null when none is set
     * @return array<string, mixed>
     */
    public function build(array $it, ?float $rate, ?float $pct): array
    {
        $basis = $this->basis();
        if ($basis === []) {
            return ['available' => false, 'levels' => [], 'common' => [], 'notes' => [], 'own_pax' => null, 'markup_set' => $pct !== null];
        }
        $toUsd  = static fn (?float $lkr) => ($lkr === null || $rate === null || $rate <= 0) ? null : round($lkr / $rate, 2);
        $N      = (int) ($it['pax_total'] ?? 0);
        $nights = (int) ($it['nights'] ?? 0);
        $notes  = [];
        $common = [];   // what stops every size alike — the detailed costing's own holes, in short

        // tf-child-r1: at the inquiry's own party the children's hotel charge a night, as the Child Fare Review confirmed it —
        // the same figure the detailed costing adds, so the two agree; other sizes are adults only, as before
        $childNight = [];
        if ((int) ($it['pax_children'] ?? 0) > 0) {
            try {
                foreach (((new ChildFares($this->db))->review($it))['items'] as $ci) {
                    if ($ci['kind'] === 'hotel' && in_array($ci['status'], ['confirmed', 'review_again'], true) && $ci['decision'] === 'applicable') {
                        $perN = $ci['manual_amount'] ?? $ci['child_rate'];
                        if ($perN !== null) {
                            for ($dn = (int) $ci['day_from']; $dn <= (int) $ci['day_to']; $dn++) {
                                $childNight[$dn] = (float) $perN;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                $childNight = [];
            }
        }

        // ---- what does not change with the party: the crew and their rooms, said once ----------
        [$crewLkr, $crewGaps] = self::crew($it, $nights);
        [$crewRoomsUsd, $crewRoomGaps] = self::crewRooms($it, $toUsd);
        foreach (array_merge($crewGaps, $crewRoomGaps) as $g) {
            $common[] = ['part' => 'transport', 'text' => $g];
        }
        if (($it['days'] ?? []) === []) {
            $common[] = ['part' => 'hotels', 'text' => 'The itinerary has no nights on it.'];
        }
        if ($rate === null || $rate <= 0) {
            $common[] = ['part' => 'transport', 'text' => 'No USD exchange rate is on file, so the rupee costs cannot be shown in dollars.'];
        }

        // the route a party travelling together drives: the tour's charged kilometres. With no
        // legs at all no size has a mileage; a route that cannot be told (a party that splits
        // across routes) stops only the sizes priced on the ladder, not the inquiry's own
        [$routeKm, $routeGap, $routeNote] = self::route($it);
        if ($routeNote !== null) {
            $notes[] = $routeNote;
        }
        if ((int) ($it['mileage']['legs'] ?? 0) === 0 && ($it['days'] ?? []) !== []) {
            $common[] = ['part' => 'transport', 'text' => 'The itinerary has not been broken into legs, so there is no mileage to cost.'];
            $routeGap = null;
        }
        $className = [];
        foreach ($this->planner->classes() as $c) {
            $className[$c['code']] = $c['name'];
        }

        // what each paid line does as the party grows, said once for the lines that do not simply follow it
        $svcNotes = [];
        $excNotes = [];
        $this->paidNotes($it, $N, $svcNotes, $excNotes);
        $notes = array_merge($notes, $svcNotes, $excNotes);

        $levels  = [];
        $prev    = null;
        $drivers = count(array_filter($it['crew'] ?? [], static fn ($c) => ($c['kind'] ?? '') === 'driver'));
        foreach ($basis as $p => $b) {
            $own  = $N > 0 && $p === $N;
            $gaps = [];   // this size's own: [part, text]

            // ---- rooms, night by night --------------------------------------------------------
            $hotelUsd = 0.0;
            foreach (($it['days'] ?? []) as $d) {
                $mix      = $own ? ($d['room_mix'] ?? IntakeService::roomMix($it, (int) $d['day_no'])) : ['sgl' => $b['sgl'], 'dbl' => $b['dbl'], 'twn' => $b['twn'], 'tpl' => $b['tpl'], 'pax' => $p];
                $paxNight = $own ? (($mix['pax'] ?? null) !== null && (int) $mix['pax'] > 0 ? (int) $mix['pax'] : $N) : $p;
                $n        = self::night($d, $mix, $paxNight, $toUsd);
                if ($n['gap'] !== null) {
                    if ($n['common']) {
                        $common[] = ['part' => 'hotels', 'text' => $n['gap']];
                    } else {
                        $gaps[] = ['part' => 'hotels', 'text' => $n['gap']];
                    }
                    continue;
                }
                if ($n['usd'] !== null) {
                    $hotelUsd += $n['usd'];
                    if ($own && isset($childNight[(int) $d['day_no']]) && strtoupper((string) ($d['rate_currency'] ?? 'USD')) === 'USD') {
                        $hotelUsd += round($childNight[(int) $d['day_no']], 2);
                    }
                }
            }

            // ---- the vehicles for this size, and what they cost to drive ------------------------
            $vehicles = [];
            $lkr      = 0.0;
            $usdKm    = 0.0;   // tf-kmusd-r1: a rate per km kept in USD, taken as it is
            $vehCount = 0;
            if ($own) {
                foreach (($it['mileage']['by_vehicle'] ?? []) as $bv) {
                    $qty = max(1, (int) ($bv['qty'] ?? 1));
                    $vehCount += $qty;
                    $line = self::vehicleLine($it, (int) $bv['vehicle_seq']);
                    $code = $line['class_code'] ?? null;
                    $vehicles[] = [
                        'code' => $code, 'name' => ($code !== null ? ($className[$code] ?? null) : null) ?: (($line['type'] ?? null) ?: (($bv['type'] ?? null) ?: ($bv['label'] ?? 'Vehicle'))),
                        'label' => $bv['label'] ?? null, 'qty' => $qty, 'km' => (float) $bv['charged_km'],
                        'km_rate' => $bv['km_rate'], 'currency' => $bv['currency'] ?: 'LKR',
                        'rate_from' => ($line['rate_by'] ?? '') === 'manual' ? 'special' : 'inquiry',
                    ];
                    if ((int) ($bv['unpriced'] ?? 0) > 0) {
                        $common[] = ['part' => 'transport', 'text' => ($bv['label'] ?: 'The vehicle') . ': ' . (int) $bv['unpriced'] . ' leg(s) have no distance.'];
                        continue;
                    }
                    if ($bv['km_rate'] === null) {
                        if ((int) ($bv['legs'] ?? 0) > 0 && ($it['vehicles'] ?? []) !== []) {
                            $gaps[] = ['part' => 'transport', 'text' => ($bv['label'] ?: 'The vehicle') . ' (' . (($bv['type'] ?? '') ?: 'size not set') . ') has no rate per kilometre.'];
                        }
                        continue;
                    }
                    if (strtoupper((string) ($bv['currency'] ?: 'LKR')) === 'USD') {
                        $usdKm += round((float) $bv['charged_km'] * (float) $bv['km_rate'] * $qty, 2);
                    } else {
                        $lkr += round((float) $bv['charged_km'] * (float) $bv['km_rate'] * $qty, 2);
                    }
                }
                if (($it['vehicles'] ?? []) === []) {
                    $gaps[] = ['part' => 'transport', 'text' => 'No vehicle has been chosen on this inquiry.'];
                }
            } else {
                $plan = $this->planner->suggest($p);
                if (!$plan['ok']) {
                    $gaps[] = ['part' => 'transport', 'text' => $plan['why']];
                } elseif ($routeGap !== null) {
                    $gaps[] = ['part' => 'transport', 'text' => $routeGap];
                }
                foreach ($plan['ok'] ? $plan['vehicles'] : [] as $v) {
                    $qty = max(1, (int) ($v['qty'] ?? 1));
                    $vehCount += $qty;
                    [$kmRate, $cur, $from] = $this->rateFor($it, (string) $v['class_code']);
                    $vehicles[] = ['code' => (string) $v['class_code'], 'name' => (string) ($v['class_name'] ?? $v['type']), 'label' => null, 'qty' => $qty, 'km' => $routeKm,
                                   'km_rate' => $kmRate, 'currency' => $cur ?? 'LKR', 'rate_from' => $from];
                    if ($kmRate === null) {
                        $gaps[] = ['part' => 'transport', 'text' => 'No rate per kilometre for the ' . ($v['class_name'] ?? $v['class_code']) . ' in Master Files → Transport Rates.'];
                        continue;
                    }
                    if (!in_array(strtoupper((string) $cur), ['LKR', 'USD'], true)) {
                        // the costing reads a rate per kilometre in rupees or dollars; one kept in another currency is said, never converted by guess
                        $gaps[] = ['part' => 'transport', 'text' => 'The rate per kilometre for the ' . ($v['class_name'] ?? $v['class_code']) . ' is in ' . $cur . '; the costing reads transport in rupees or US dollars.'];
                        continue;
                    }
                    if ($routeKm !== null) {
                        if (strtoupper((string) $cur) === 'USD') {
                            $usdKm += round($routeKm * $kmRate * $qty, 2);
                        } else {
                            $lkr += round($routeKm * $kmRate * $qty, 2);
                        }
                    }
                }
                if ($plan['ok'] && $drivers > 0 && $vehCount > $drivers) {
                    $notes[] = $p . ' pax travel in ' . $vehCount . ' vehicles on the ladder; the driver and guide are costed as on this inquiry (' . $drivers . ' driver' . ($drivers === 1 ? '' : 's') . '). Add a driver on the inquiry if each vehicle needs its own.';
                }
            }

            // ---- paid activities and services ------------------------------------------------
            [$svcUsd, $svcGaps] = self::services($it, $p, $N);
            [$excUsd, $excGaps] = $this->excursions($it, $p, $N, $own, $vehCount, $toUsd);
            foreach (array_merge($svcGaps, $excGaps) as $g) {
                $common[] = ['part' => 'paid', 'text' => $g];
            }

            $sections = [
                'hotels'     => round($hotelUsd, 2),
                'transport'  => round(($toUsd($lkr) ?? 0.0) + $usdKm, 2),
                'crew'       => round($toUsd($crewLkr) ?? 0.0, 2),
                'crew_rooms' => round($crewRoomsUsd, 2),
                'services'   => round($svcUsd, 2),
                'excursions' => round($excUsd, 2),
            ];
            $levels[$p] = [
                'pax' => $p, 'own' => $own,
                'rooms' => $own ? self::mixOf($it['rooms'] ?? IntakeService::roomMix($it, null)) : ['sgl' => $b['sgl'], 'dbl' => $b['dbl'], 'twn' => $b['twn'], 'tpl' => $b['tpl']],
                'vehicles' => $vehicles,
                'gaps' => $gaps,
                'sections' => $sections,
            ];
        }

        // one entry per hole, and the parts each size could not cost
        $common = self::uniqueGaps($common);
        $blocked = ['hotels' => false, 'paid' => false, 'transport' => false];
        foreach ($common as $g) {
            $blocked[$g['part']] = true;
        }
        $out = [];
        foreach ($levels as $p => $L) {
            $L['gaps'] = self::uniqueGaps($L['gaps']);
            $own = $blocked;
            foreach ($L['gaps'] as $g) {
                $own[$g['part']] = true;
            }
            $s = $L['sections'];
            $parts = [
                'hotels'    => $own['hotels'] ? null : round($s['hotels'], 2),
                'paid'      => $own['paid'] ? null : round($s['services'] + $s['excursions'], 2),
                'transport' => $own['transport'] ? null : round($s['transport'] + $s['crew'] + $s['crew_rooms'], 2),
            ];
            $complete = !in_array(null, $parts, true);
            // the three parts add up to the cost, exactly as the detailed costing adds its sections
            $cost = $complete ? round(array_sum($s), 2) : null;
            $sell = ($cost !== null && $pct !== null) ? round($cost * (1 + $pct / 100), 2) : null;
            $rooms = $L['rooms'];
            $veh   = array_map(static fn ($v) => $v['code'] . 'x' . $v['qty'], $L['vehicles']);
            $out[] = [
                'pax'          => $p,
                'own'          => $L['own'],
                'rooms'        => $rooms,
                'rooms_text'   => self::roomsText($rooms),
                'vehicles'     => $L['vehicles'],
                'vehicle_text' => self::vehicleText($L['vehicles']),
                'sections'     => $complete ? $s : null,
                'parts'        => $parts,
                'cost_usd'     => $cost,
                'cost_pp_usd'  => $cost !== null ? round($cost / $p, 2) : null,
                'sell_usd'     => $sell,
                'sell_pp_usd'  => $sell !== null ? round($sell / $p, 2) : null,
                'profit_usd'   => $sell !== null ? round($sell - $cost, 2) : null,
                'complete'     => $complete,
                'gaps'         => array_column($L['gaps'], 'text'),
                'vehicle_changed' => $prev !== null && $veh !== $prev['veh'],
                'rooms_changed'   => $prev !== null && self::kinds($rooms) !== self::kinds($prev['rooms']),
            ];
            $prev = ['veh' => $veh, 'rooms' => $rooms];
        }

        $sizes = array_keys($basis);
        $ownRow = null;
        foreach ($out as $L) {
            if ($L['own']) {
                $ownRow = $L;
            }
        }
        if ($N > 0 && $ownRow === null) {
            $notes[] = 'This inquiry is for ' . $N . ' pax, ' . ($N > max($sizes) ? 'beyond' : 'outside') . ' the sizes in the matrix; its own figure is the detailed costing below.';
        } elseif ($ownRow !== null) {
            $std  = $basis[$N];
            $plan = $this->planner->suggest($N);
            $stdCodes = $plan['ok'] ? array_map(static fn ($v) => $v['class_code'] . 'x' . max(1, (int) $v['qty']), $plan['vehicles']) : null;
            $ownCodes = array_map(static fn ($v) => $v['code'] . 'x' . $v['qty'], $ownRow['vehicles']);
            $diff = [];
            if (self::mixOf($std) !== $ownRow['rooms']) {
                $diff[] = 'its own rooms (' . $ownRow['rooms_text'] . '; the basis for ' . $N . ' pax is ' . $std['text'] . ')';
            }
            if ($stdCodes !== null && $stdCodes !== $ownCodes) {
                $diff[] = 'its own vehicle (' . $ownRow['vehicle_text'] . '; the ladder gives ' . self::vehicleText(array_map(static fn ($v) => ['name' => $v['class_name'] ?? $v['type'], 'qty' => (int) $v['qty']], $plan['vehicles'])) . ')';
            }
            if (array_filter($ownRow['vehicles'], static fn ($v) => $v['rate_from'] === 'special')) {
                $diff[] = 'the special rate per kilometre on it';
            }
            $notes[] = 'The ' . $N . '-pax row is this inquiry, priced on ' . ($diff !== [] ? implode(', ', $diff) : 'its own rooms and vehicle, which are the basis and the ladder\'s for that size') . '; the other sizes use the room basis and the ladder.';
        }

        return [
            'available'  => true,
            'own_pax'    => $ownRow !== null ? $N : null,
            'markup_set' => $pct !== null,
            'levels'     => $out,
            'common'     => array_values(array_unique(array_column($common, 'text'))),
            'notes'      => array_values(array_unique($notes)),
            'basis_note' => 'Rooms: Pricing → Room basis by party size, at the published rates on each night. Vehicle: the Transport Rates ladder, at the rate this inquiry carries for that size or the master\'s rate in force. Paid: per person × the party, per group once, per vehicle × the vehicles. Driver and guide as on the inquiry.',
        ];
    }

    /**
     * The inquiry's own row against the detailed costing: the same figures twice, or a
     * difference in words. Null when there is nothing to compare (no own row, or the own row
     * could not be costed — the costing then has its own blockers).
     *
     * @return array{pax:int, matrix_usd:float, costing_usd:float, equal:bool, parts:array<string, array{matrix:?float, costing:float}>}|null
     */
    public static function check(array $matrix, array $parts, float $costUsd): ?array
    {
        $own = null;
        foreach ($matrix['levels'] ?? [] as $L) {
            if (!empty($L['own'])) {
                $own = $L;
            }
        }
        if ($own === null || !$own['complete']) {
            return null;
        }
        $equal = abs((float) $own['cost_usd'] - $costUsd) < 0.005;
        $by = [];
        foreach (['hotels', 'paid', 'transport'] as $k) {
            $m = $own['parts'][$k];
            $c = round((float) ($parts[$k]['usd'] ?? 0), 2);
            $by[$k] = ['matrix' => $m, 'costing' => $c];
            if ($m === null || abs($m - $c) >= 0.005) {
                $equal = false;
            }
        }
        return ['pax' => (int) $own['pax'], 'matrix_usd' => (float) $own['cost_usd'], 'costing_usd' => round($costUsd, 2), 'equal' => $equal, 'parts' => $by];
    }

    /**
     * The table the agent receives: the party size, the rooms it is based on, the price per
     * person. Nothing else — no cost, no markup, no profit, no rate. A size that could not be
     * priced says so ("on request"); with no markup set there is no table at all.
     *
     * @return array<int, array{pax:int, rooms:string, per_person_usd:?float}>
     */
    public static function forAgent(?array $matrix): array
    {
        if (!$matrix || empty($matrix['available']) || empty($matrix['markup_set'])) {
            return [];
        }
        $out = [];
        foreach ($matrix['levels'] as $L) {
            $out[] = ['pax' => (int) $L['pax'], 'rooms' => (string) $L['rooms_text'], 'per_person_usd' => $L['complete'] && $L['sell_pp_usd'] !== null ? (float) $L['sell_pp_usd'] : null];
        }
        return $out;
    }

    /** The matrix as a person without the Pricing privilege may see it: no profit on any row. */
    public static function forStaff(?array $matrix): ?array
    {
        if (!$matrix || empty($matrix['levels'])) {
            return $matrix;
        }
        foreach ($matrix['levels'] as $i => $L) {
            $matrix['levels'][$i]['profit_usd'] = null;
        }
        $matrix['profit_hidden'] = true;
        return $matrix;
    }

    // =====================================================================
    //  One night, one service line, one excursion — each as the detailed costing reads it
    // =====================================================================

    /**
     * A night at a given set of rooms: the rooms × the published rates copied beside the night,
     * and its compulsory supplements, exactly as QuoteService::costing works a night out. A gap
     * that does not depend on the rooms (no hotel, no rate for the date) is "common": no size
     * can be costed. A rate the rooms need that the night does not carry belongs to the size.
     *
     * @return array{usd:?float, gap:?string, common:bool}
     */
    public static function night(array $d, array $mix, int $paxNight, callable $toUsd): array
    {
        $n     = (int) $d['day_no'];
        $hotel = trim((string) ($d['hotel_name_raw'] ?? ''));
        $fail  = static fn (string $why, bool $common = true) => ['usd' => null, 'gap' => $why, 'common' => $common];
        if ($hotel === '') {
            return $fail("Night {$n}: no hotel named.");
        }
        if (empty($d['day_date'])) {
            return $fail("Night {$n} ({$hotel}): no date.");
        }
        if (($d['meal_plan'] ?? null) === null) {
            return $fail("Night {$n} ({$hotel}): meal plan not stated.");
        }
        $state = (string) ($d['rate_state'] ?? '');
        if ($state !== 'rate') {
            return $fail("Night {$n} ({$hotel}): no published rate for the night.");
        }
        if (($d['room_types_json'] ?? null) === null) {
            return $fail("Night {$n} ({$hotel}): the published rate has not been read onto the night yet.");
        }
        if (($d['room_type_id'] ?? null) === null) {
            return $fail("Night {$n} ({$hotel}): no room type chosen.");
        }
        $cur = strtoupper((string) ($d['rate_currency'] ?? 'USD'));
        if (!in_array($cur, ['USD', 'LKR'], true)) {
            return $fail("Night {$n} ({$hotel}): the rate is in {$cur}, which the costing cannot convert.");
        }
        $rooms  = (int) ($mix['sgl'] ?? 0) + (int) ($mix['dbl'] ?? 0) + (int) ($mix['twn'] ?? 0) + (int) ($mix['tpl'] ?? 0);
        $amount = 0.0;
        $unit   = ($d['rate_unit'] ?? null) !== null && $d['rate_unit'] !== '' ? (float) $d['rate_unit'] : null;
        if ($unit !== null && ($d['rate_dbl'] ?? null) === null && ($d['rate_sgl'] ?? null) === null) {
            // a tent or a villa priced as one unit: one unit per room, as the detailed costing counts it
            $amount += max(1, $rooms) * $unit;
        } else {
            $short = [];
            foreach ([['sgl', 'single', $d['rate_sgl'] ?? null], ['dbl', 'double', $d['rate_dbl'] ?? null], ['twn', 'twin', $d['rate_dbl'] ?? null], ['tpl', 'triple', $d['rate_tpl'] ?? null]] as [$k, $word, $r]) {
                $qty = (int) ($mix[$k] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                if ($r === null || $r === '') {
                    $short[] = $word;
                    continue;
                }
                $amount += $qty * (float) $r;
            }
            if ($short !== []) {
                return $fail("Night {$n}: {$hotel} publishes no " . implode(' / ', $short) . ' rate for ' . ($d['room_type_name'] ?? 'the room type') . ' on ' . $d['meal_plan'] . '.', false);
            }
            if ($rooms === 0) {
                return $fail("Night {$n} ({$hotel}): nobody is in a room that night.", false);
            }
        }
        foreach ((array) ($d['rate_supp'] ?? []) as $s) {
            $per = strtolower((string) ($s['per'] ?? 'person'));
            $qty = $per === 'room' ? max(1, $rooms) : ($per === 'person' ? $paxNight : 1);
            $amount += round($qty * (float) $s['amount'], 2);
        }
        $amount = round($amount, 2);
        return ['usd' => $cur === 'USD' ? $amount : $toUsd($amount), 'gap' => null, 'common' => false];
    }

    /**
     * The Entrance & Activities lines for a party of $p: a priced line per person follows the
     * party (capped at the people it is for), a line counted in units (a jeep, a guide) keeps its
     * units. The same reading ServiceLookup::price gives the inquiry's own party.
     *
     * @return array{0: float, 1: array<int, string>}
     */
    public static function services(array $it, int $p, int $N): array
    {
        $usd  = 0.0;
        $gaps = [];
        foreach (($it['services'] ?? []) as $s) {
            if (!empty($s['free_visit_id'])) {
                continue;   // a free visit: on the itinerary only, never costed
            }
            $c = ServiceLookup::classify($s);
            if ($c['state'] !== 'priced') {
                if ($c['blocks']) {
                    $gaps[] = ((int) $s['day_no'] ? 'Day ' . (int) $s['day_no'] : 'Tour') . ': "' . $s['title'] . '" is not priced yet (' . rtrim((string) $c['why'], '.') . ').';
                }
                continue;
            }
            if (!empty($s['meal_id'])) {
                // schema 40: a lunch or dinner from the Meal Master — at the inquiry's own party the line's own amount (its adults
                // and children at the meal's rates, with the taxes); at another size the adult rate with the taxes a head, for the
                // party (up to the people the line is for)
                if ($N > 0 && $p === $N) {
                    $usd += round((float) ($s['amount_usd'] ?? 0), 2);
                    continue;
                }
                $usd += round((float) $s['unit_usd'] * self::serviceCount($s, $p, $N), 2);
                continue;
            }
            if (!empty($s['place_id'])) {
                // schema 39: a place of the Place to Visit / Entrance Fee Master — at the inquiry's own party the line's own
                // amount (adults and children at the master's rates); at another size the entrance a head for the party (up to
                // the people the line is for), and at a safari park the jeeps on the line, the same at every size
                if ($N > 0 && $p === $N) {
                    $usd += round((float) ($s['amount_usd'] ?? 0), 2);
                    continue;
                }
                $park = ($s['jeep_usd'] ?? null) !== null && $s['jeep_usd'] !== '';
                $heads = $park ? (float) $p : self::serviceCount(['kind' => 'entrance'] + $s, $p, $N);
                // tf-safari-r1: with the jeep capacity known, the jeeps this size needs; otherwise the jeeps on the line, as before
                $jp = $park ? EntranceMaster::jeepsNeeded($p, EntranceMaster::rules(\Config\Database::connect(), (int) $s['place_id'])['seats']) : null;
                $usd += round((float) $s['unit_usd'] * $heads + ($park ? (float) $s['jeep_usd'] * (float) ($jp ?? ($s['qty'] ?? 0)) : 0), 2);
                continue;
            }
            if ($N > 0 && $p === $N && (int) ($it['pax_children'] ?? 0) > 0 && ($s['amount_usd'] ?? null) !== null && $s['amount_usd'] !== '') {
                $usd += round((float) $s['amount_usd'], 2);   // tf-child-r1: the party's own line, children as the Child Fare Review decided
                continue;
            }
            $usd += round((float) $s['unit_usd'] * self::serviceCount($s, $p, $N), 2);
        }
        return [$usd, $gaps];
    }

    /** How many a service line is costed for at a party of $p. */
    public static function serviceCount(array $s, int $p, int $N): float
    {
        $qty = ($s['qty'] ?? null) === null || $s['qty'] === '' ? null : (float) $s['qty'];
        if ($qty === null) {
            return (float) $p;   // one per person
        }
        if (ServiceLookup::perPerson($s['kind'] ?? null)) {
            return ($N > 0 && $qty >= $N) ? (float) $p : min($qty, (float) $p);   // the whole party, or the people it is for
        }
        return $qty;   // units: the same number at every size
    }

    /**
     * The activities and excursions for a party of $p, at the rate each line copied from the
     * master: per person × the people going, per group once, per vehicle × the vehicles.
     *
     * @return array{0: float, 1: array<int, string>}
     */
    private function excursions(array $it, int $p, int $N, bool $own, int $vehCount, callable $toUsd): array
    {
        $usd  = 0.0;
        $gaps = [];
        foreach (($it['excursions'] ?? []) as $x) {
            $cur = strtoupper((string) (($x['currency'] ?? '') ?: 'USD'));
            if ((float) $x['qty'] <= 0) {
                $gaps[] = $x['name'] . ' on day ' . (int) $x['day_no'] . ' has a quantity of nought.';
                continue;
            }
            if (!in_array($cur, ['USD', 'LKR'], true)) {
                $gaps[] = $x['name'] . " is priced in {$cur}, which the costing cannot convert.";
                continue;
            }
            $qty    = $own ? (float) $x['qty'] : $this->excursionQty($it, $x, $p, $N, $vehCount);
            $amount = round($qty * (float) $x['rate'], 2);
            $u      = $cur === 'USD' ? $amount : $toUsd($amount);
            if ($u !== null) {
                $usd += $u;
            }
        }
        return [$usd, $gaps];
    }

    /** How many an excursion line is for at a party of $p (not the inquiry's own size). */
    private function excursionQty(array $it, array $x, int $p, int $N, int $vehCount): float
    {
        $qty = (float) $x['qty'];
        switch ((string) $x['unit']) {
            case 'per_group':
                return $qty;   // one lot, whatever the size
            case 'per_vehicle':
                return abs($qty - (float) self::vehiclesServing($it, $x)) < 0.001 ? (float) max(1, $vehCount) : $qty;
            default:   // per person
                if (($x['group_id'] ?? null) === null && $N > 0 && $qty >= $N) {
                    return (float) $p;
                }
                return min($qty, (float) $p);
        }
    }

    /** The vehicles on the inquiry serving an excursion line — what its quantity starts from when it is charged per vehicle. */
    private static function vehiclesServing(array $it, array $x): int
    {
        $g = ($x['group_id'] ?? null) === null || $x['group_id'] === '' ? null : (int) $x['group_id'];
        $n = 0;
        foreach (($it['vehicles'] ?? []) as $v) {
            $lg = ($v['group_id'] ?? null) === null || $v['group_id'] === '' ? null : (int) $v['group_id'];
            if ($g === null || $lg === null || $lg === $g) {
                $n += max(1, (int) $v['qty']);
            }
        }
        return max(1, $n);
    }

    /**
     * Said once, for the paid lines that do not simply follow the party: a line for some of the
     * party, a line in units, a line for one group, a per-vehicle line typed by hand.
     *
     * @param array<int, string> $svc
     * @param array<int, string> $exc
     */
    private function paidNotes(array $it, int $N, array &$svc, array &$exc): void
    {
        foreach (($it['services'] ?? []) as $s) {
            if (!empty($s['free_visit_id']) || ServiceLookup::classify($s)['state'] !== 'priced' || ($s['qty'] ?? null) === null || $s['qty'] === '') {
                continue;
            }
            $qty = (float) $s['qty'];
            $day = (int) $s['day_no'] ? 'Day ' . (int) $s['day_no'] : 'Tour';
            if (!empty($s['place_id']) && ($s['jeep_usd'] ?? null) !== null && $s['jeep_usd'] !== '') {
                // schema 39: a safari park — the entrance follows the party, the jeeps stay as typed
                $svc[] = $day . ': "' . $s['title'] . '" — the entrance follows the party; the jeeps (' . self::num($qty) . ') are the same at every size: check the jeeps for the larger sizes.';
            } elseif (ServiceLookup::perPerson(!empty($s['place_id']) ? 'entrance' : ($s['kind'] ?? null))) {
                if ($N <= 0 || $qty < $N) {
                    $svc[] = $day . ': "' . $s['title'] . '" is for ' . self::num($qty) . ($N > 0 ? ' of the ' . $N : '') . ' — every size is costed for up to ' . self::num($qty) . '.';
                }
            } else {
                $svc[] = $day . ': "' . $s['title'] . '" is counted in units (' . self::num($qty) . ') — the same at every size; the list does not say how many people one takes, so check the larger sizes.';
            }
        }
        foreach (($it['excursions'] ?? []) as $x) {
            $qty = (float) $x['qty'];
            $day = 'Day ' . (int) $x['day_no'];
            if ((string) $x['unit'] === 'per_vehicle' && abs($qty - (float) self::vehiclesServing($it, $x)) >= 0.001) {
                $exc[] = $day . ': ' . $x['name'] . ' — ' . self::num($qty) . ' vehicle(s) as typed on the inquiry, the same at every size.';
            } elseif ((string) $x['unit'] === 'per_person' && ((($x['group_id'] ?? null) !== null && $x['group_id'] !== '') || $N <= 0 || $qty < $N)) {
                $exc[] = $day . ': ' . $x['name'] . ' is for ' . self::num($qty) . ($N > 0 ? ' of the ' . $N : '') . ' — every size is costed for up to ' . self::num($qty) . '.';
            }
        }
    }

    // =====================================================================
    //  What does not change with the size of the party
    // =====================================================================

    /** The driver's and guide's bata, in rupees, as the detailed costing adds it. @return array{0: float, 1: array<int, string>} */
    private static function crew(array $it, int $nights): array
    {
        $lkr  = 0.0;
        $gaps = [];
        foreach (($it['crew'] ?? []) as $c) {
            $bata = ($c['bata'] ?? null) === null || $c['bata'] === '' ? null : (float) $c['bata'];
            $days = (int) ($c['days'] ?? 0) ?: $nights;
            if ($bata === null) {
                $gaps[] = $c['role_name'] . ' has no bata (Master Files → Crew Bata).';
            } elseif ($days <= 0) {
                $gaps[] = $c['role_name'] . ': the number of days is not set.';
            } else {
                $lkr += round($days * $bata, 2);
            }
        }
        return [$lkr, $gaps];
    }

    /** Their rooms, in dollars, as the detailed costing adds them. @return array{0: float, 1: array<int, string>} */
    private static function crewRooms(array $it, callable $toUsd): array
    {
        $usd  = 0.0;
        $gaps = [];
        foreach (($it['accom'] ?? []) as $kind => $a) {
            if ((int) ($a['required'] ?? 0) !== 1) {
                continue;
            }
            if ((int) ($a['unknown_nights'] ?? 0) > 0) {
                $gaps[] = ucfirst((string) $kind) . ' accommodation: ' . (int) $a['unknown_nights'] . ' night(s) have nothing on file.';
                continue;
            }
            if (($a['amount'] ?? null) === null) {
                $gaps[] = ucfirst((string) $kind) . ' accommodation is required but has no figure.';
                continue;
            }
            $cur = strtoupper((string) (($a['currency'] ?? '') ?: 'USD'));
            $u   = $cur === 'USD' ? (float) $a['amount'] : ($cur === 'LKR' ? $toUsd((float) $a['amount']) : null);
            if ($u === null && $cur !== 'LKR') {
                $gaps[] = ucfirst((string) $kind) . " accommodation is in {$cur}; only USD and LKR can be costed.";
            } elseif ($u !== null) {
                $usd += $u;
            }
        }
        return [$usd, $gaps];
    }

    /**
     * The kilometres a party travelling together is charged for: the tour's own route, as its
     * legs stand (stay days, garage runs and waived days included). When the inquiry's vehicles
     * drive different routes — a party that splits — there is no one route to price another
     * size on, and that is said rather than chosen.
     *
     * @return array{0: ?float, 1: ?string, 2: ?string} km, the gap, a note
     */
    private static function route(array $it): array
    {
        $mil = $it['mileage'] ?? [];
        $by  = $mil['by_vehicle'] ?? [];
        if ((int) ($mil['legs'] ?? 0) === 0 || $by === []) {
            return [null, 'The itinerary has not been broken into legs, so there is no mileage to cost.', null];
        }
        $pick = null;
        $note = null;
        if (count($by) === 1) {
            $pick = $by[0];
        } else {
            $whole = array_values(array_filter($by, static fn ($b) => ($b['group_id'] ?? null) === null || $b['group_id'] === ''));
            $kms   = array_unique(array_map(static fn ($b) => (string) round((float) $b['charged_km'], 1), $by));
            if (count($kms) === 1) {
                $pick = $by[0];
            } elseif (count($whole) === 1) {
                $pick = $whole[0];
                $note = 'The party splits on this inquiry; the other sizes are priced as one party on the route of ' . (($pick['label'] ?? '') ?: 'the vehicle serving the whole party') . '.';
            } else {
                return [null, 'The vehicles on this inquiry drive different routes, so no one route can be priced for the other party sizes.', null];
            }
        }
        if ((int) ($pick['unpriced'] ?? 0) > 0) {
            return [null, (($pick['label'] ?? '') ?: 'The vehicle') . ': ' . (int) $pick['unpriced'] . ' leg(s) have no distance.', $note];
        }
        return [(float) $pick['charged_km'], null, $note];
    }

    /**
     * The rate per kilometre for a vehicle size: the figure this inquiry already copied from the
     * master for that size (so a quoted rate stays quoted), else the master's rate in force. A
     * special rate typed for this inquiry is its own decision and is not carried to other sizes.
     *
     * @return array{0: ?float, 1: ?string, 2: ?string} rate, currency, where it came from
     */
    private function rateFor(array $it, string $code): array
    {
        foreach (($it['vehicles'] ?? []) as $v) {
            if ((string) $v['class_code'] === $code && ($v['rate_by'] ?? '') === 'master' && ($v['km_rate'] ?? null) !== null && $v['km_rate'] !== '') {
                return [(float) $v['km_rate'], (string) (($v['currency'] ?? '') ?: 'LKR'), 'inquiry'];
            }
        }
        if (!array_key_exists($code, $this->masterRates)) {
            $this->masterRates[$code] = $this->planner->rates()->current($code);
        }
        $r = $this->masterRates[$code];
        return $r ? [(float) $r['rate_km'], (string) $r['currency'], 'master'] : [null, null, null];
    }

    /** The inquiry's vehicle line at a position. */
    private static function vehicleLine(array $it, int $seq): ?array
    {
        foreach (($it['vehicles'] ?? []) as $v) {
            if ((int) ($v['sort_order'] ?? 0) === $seq) {
                return $v;
            }
        }
        return null;
    }

    /** "High roof van", "2 × Mini coach", "Van + Sedan car". */
    public static function vehicleText(array $vehicles): string
    {
        $out = [];
        foreach ($vehicles as $v) {
            $name  = trim((string) ($v['name'] ?? '')) ?: 'Vehicle';
            $out[] = ((int) ($v['qty'] ?? 1) > 1 ? (int) $v['qty'] . ' × ' : '') . $name;
        }
        return $out !== [] ? implode(' + ', $out) : '—';
    }

    /** @return array{sgl:int, dbl:int, twn:int, tpl:int} */
    private static function mixOf(array $m): array
    {
        return ['sgl' => (int) ($m['sgl'] ?? 0), 'dbl' => (int) ($m['dbl'] ?? 0), 'twn' => (int) ($m['twn'] ?? 0), 'tpl' => (int) ($m['tpl'] ?? 0)];
    }

    /** Which kinds of room a set uses — a change of kind (a triple appears, the single goes) is worth marking. */
    private static function kinds(array $m): string
    {
        $k = [];
        foreach (['sgl', 'dbl', 'twn', 'tpl'] as $x) {
            if ((int) ($m[$x] ?? 0) > 0) {
                $k[] = $x;
            }
        }
        return implode(',', $k);
    }

    /** @param array<int, array{part:string, text:string}> $gaps */
    private static function uniqueGaps(array $gaps): array
    {
        $seen = [];
        $out  = [];
        foreach ($gaps as $g) {
            $k = $g['part'] . '|' . $g['text'];
            if (!isset($seen[$k])) {
                $seen[$k] = true;
                $out[] = $g;
            }
        }
        return $out;
    }

    private static function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }
}
