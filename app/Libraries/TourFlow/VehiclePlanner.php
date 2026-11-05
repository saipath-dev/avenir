<?php

namespace App\Libraries\TourFlow;

/**
 * Works out what a party travels in.
 *
 * The rule is the operator's own ladder, held in tf_vehicle_class:
 *
 *      up to  2 pax   sedan car
 *      up to  3 pax   van
 *      up to  7 pax   high roof van
 *      up to 13 pax   mini coach   (20 seats)
 *      up to 20 pax   coach        (33 seats)
 *      up to 30 pax   bus          (45 seats)
 *      up to 40 pax   bus          (55 seats)
 *
 * The important thing about that ladder is that max_pax is deliberately well below the
 * seat count. Twenty people go in a thirty-three seater because the spare seats are
 * legroom, luggage and the guide — filling a coach to capacity is what a bus company
 * does, not a tour operator. So the planner never reasons from seats; it reads the
 * ladder, which the business can edit.
 *
 * The rate per kilometre comes from Master Files → Transport Rates, one per size, with
 * history. The `vehicle` master is still read, but only to name a vehicle of that kind,
 * matched on the words in the class; the driver's bata is the crew bata master's alone.
 * Where the rates master holds no rate the class is still chosen and the rate is left
 * empty and flagged — a made-up running cost is worse than none.
 *
 * Above the largest class the party is split across the fewest vehicles that carry it.
 */
final class VehiclePlanner
{
    /** Beyond this many vehicles it is a fleet movement, not something to work out here. */
    private const MAX_VEHICLES = 4;

    private $db;
    private ?array $classes = null;
    private ?array $fleet = null;
    private ?TransportRates $rates = null;

    public function __construct($db, ?TransportRates $rates = null)
    {
        $this->db    = $db;
        $this->rates = $rates;
    }

    /** The Transport Rates master — the only place a rate per kilometre comes from. */
    public function rates(): TransportRates
    {
        return $this->rates ??= new TransportRates($this->db);
    }

    public function available(): bool
    {
        return $this->classes() !== [];
    }

    /**
     * The ladder, smallest first.
     *
     * @return array<int, array{code:string,name:string,max_pax:int,seats:?int,words:array<int,string>}>
     */
    public function classes(): array
    {
        if ($this->classes !== null) {
            return $this->classes;
        }
        $this->classes = [];
        try {
            $rows = $this->db->query('SELECT * FROM tf_vehicle_class WHERE active = 1 ORDER BY max_pax, sort_order')->getResultArray();
        } catch (\Throwable $e) {
            return $this->classes;
        }
        foreach ($rows as $r) {
            $this->classes[] = [
                'code'    => (string) $r['code'],
                'name'    => (string) $r['name'],
                'max_pax' => (int) $r['max_pax'],
                'seats'   => ($r['seats'] === null || $r['seats'] === '') ? null : (int) $r['seats'],
                'words'   => array_values(array_filter(array_map('trim', explode(',', mb_strtolower((string) ($r['match_words'] ?? '')))))),
            ];
        }
        return $this->classes;
    }

    /**
     * The vehicles actually held, for their running rates.
     *
     * @return array<int, array{id:int,type:string,seats:int,km_rate:float,bata:float,flagged:bool}>
     */
    public function fleet(): array
    {
        if ($this->fleet !== null) {
            return $this->fleet;
        }
        $this->fleet = [];
        try {
            if (!$this->db->query("SHOW TABLES LIKE 'vehicle'")->getResultArray()) {
                return $this->fleet;
            }
            $rows = $this->db->query('SELECT id, type, passenger_count, km_rate, bata, action FROM vehicle')->getResultArray();
        } catch (\Throwable $e) {
            return $this->fleet;
        }
        foreach ($rows as $r) {
            $this->fleet[] = [
                'id'      => (int) $r['id'],
                'type'    => trim((string) $r['type']) ?: 'Vehicle',
                'seats'   => (int) ($r['passenger_count'] ?? 0),
                'km_rate' => (float) ($r['km_rate'] ?? 0),
                'bata'    => (float) ($r['bata'] ?? 0),
                'flagged' => (int) ($r['action'] ?? 0) === 1,
            ];
        }
        return $this->fleet;
    }

    /**
     * The vehicle held that best represents a class: matched on the class's own words,
     * preferring one marked in service and, among those, the cheapest to run.
     *
     * @return array<string, mixed>|null
     */
    public function vehicleFor(array $class): ?array
    {
        $hits = [];
        foreach ($this->fleet() as $v) {
            $type = mb_strtolower($v['type']);
            foreach ($class['words'] as $w) {
                if ($w !== '' && str_contains($type, $w)) {
                    $hits[] = $v;
                    break;
                }
            }
        }
        if ($hits === []) {
            return null;
        }
        // a vehicle that cannot seat the class's own max_pax is the wrong one, however it is named
        $fits = array_values(array_filter($hits, static fn ($v) => $v['seats'] <= 0 || $v['seats'] >= $class['max_pax']));
        $pool = $fits !== [] ? $fits : $hits;
        usort($pool, static fn ($a, $b) => ($b['flagged'] <=> $a['flagged']) ?: ($a['bata'] <=> $b['bata']));
        return $pool[0];
    }

    /**
     * Choose for a party size. Returns the vehicles and, just as importantly, why.
     *
     * @return array{ok:bool, pax:?int, vehicles:array<int,array<string,mixed>>, why:string}
     */
    public function suggest(?int $pax): array
    {
        $classes = $this->classes();
        if ($pax === null || $pax <= 0) {
            return ['ok' => false, 'pax' => $pax, 'vehicles' => [], 'why' => 'The number of passengers is not known yet, so no vehicle can be chosen.'];
        }
        if ($classes === []) {
            return ['ok' => false, 'pax' => $pax, 'vehicles' => [], 'why' => 'No vehicle sizes are set up, so none can be chosen.'];
        }

        // One vehicle, if the ladder has a rung big enough.
        foreach ($classes as $c) {
            if ($pax <= $c['max_pax']) {
                $why = "{$pax} passenger(s): a {$c['name']} carries up to {$c['max_pax']}"
                    . ($c['seats'] ? " and seats {$c['seats']}, leaving room for luggage and the guide" : '') . '.';
                return ['ok' => true, 'pax' => $pax, 'vehicles' => [$this->line($c, 1, $why)], 'why' => $why];
            }
        }

        // Larger than anything on the ladder: split across the fewest vehicles that carry it.
        $largest = $classes[count($classes) - 1];
        $qty     = (int) ceil($pax / $largest['max_pax']);
        if ($qty <= self::MAX_VEHICLES) {
            $why = "{$pax} passenger(s) is more than one {$largest['name']} carries ({$largest['max_pax']}), so {$qty} of them.";
            return ['ok' => true, 'pax' => $pax, 'vehicles' => [$this->line($largest, $qty, $why)], 'why' => $why];
        }

        $why = "{$pax} passenger(s) needs more than " . self::MAX_VEHICLES . " {$largest['name']}s. A movement this size wants arranging by hand.";
        return ['ok' => false, 'pax' => $pax, 'vehicles' => [], 'why' => $why];
    }

    /**
     * One chosen line: the class and its rate per kilometre from the Transport Rates
     * master. The vehicle master is consulted only for the name of a vehicle of that
     * kind, never for a figure.
     *
     * The rate comes from the master and nowhere else. The vehicle master's own rate
     * column is deliberately not consulted — it is per vehicle, has no history, and is
     * labelled per hour — so a size with no rate in the master carries none, flagged,
     * rather than a figure nobody has confirmed.
     */
    public function line(array $class, int $qty, string $why): array
    {
        $v    = $this->vehicleFor($class);
        $rate = $this->rates()->current($class['code']);
        return [
            'class_code'  => $class['code'],
            'id'          => $v['id'] ?? null,
            'type'        => $v !== null ? $v['type'] : $class['name'],
            'class_name'  => $class['name'],
            'seats'       => $class['seats'] ?? ($v['seats'] ?? null),
            'max_pax'     => $class['max_pax'],
            'qty'         => $qty,
            'km_rate'     => $rate['rate_km'] ?? null,
            'rate_id'     => $rate['id'] ?? null,
            'master_rate' => $rate['rate_km'] ?? null,
            'currency'    => $rate['currency'] ?? 'LKR',
            // The driver's bata is the crew bata master's alone. It is not read from the
            // vehicle master, so it can never land in a quotation twice.
            'bata'        => null,
            'why'         => $why
                . ($rate === null ? ' No rate per kilometre for this size in Master Files → Transport Rates yet, so the driving cannot be costed.' : ''),
        ];
    }
}
