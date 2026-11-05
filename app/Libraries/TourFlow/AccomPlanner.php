<?php

namespace App\Libraries\TourFlow;

use App\Libraries\HotelRates\HotelRateService;

/**
 * What it costs to put the driver and the guide up for the night.
 *
 * The one rule that matters: never assume. A hotel that gives a complimentary guide room
 * and a hotel that charges eighty dollars a night look identical on a quotation until
 * somebody checks, and over ten nights the difference is real money. So a night is only
 * ever reported as free when a contract actually says so, and a night with nothing on
 * file is reported as unknown — not as nought.
 *
 * Nothing new is invented to do this. The hotel contracts already carry hr2_guide_policy:
 * a per-night amount that varies by meal plan, and free_from_pax, which is how a contract
 * records "complimentary for groups of fifteen and over". This reads that, through the
 * same ratesOn() call the itinerary already makes, so the contract rules that apply to a
 * room rate — market, season, and published contracts only — apply here too.
 *
 * Drivers are the awkward case and the code says so rather than papering over it: on this
 * install every one of those policies is for a guide, so a driver's room falls back to the
 * company's own "Drivers Accommodation" figure in the entrance_fee list, and where even
 * that is missing the night is unknown.
 */
final class AccomPlanner
{
    public const KINDS = ['driver', 'guide'];

    /** What the company charges itself for a driver's room when no hotel policy exists. */
    private const COMPANY_DRIVER_ITEM = 'Drivers Accommodation';

    private $db;
    private HotelRateService $rates;
    private ServiceLookup $services;

    /** @var callable(string):bool */
    private $quotable;

    public function __construct($db, HotelRateService $rates, ServiceLookup $services, callable $quotable)
    {
        $this->db       = $db;
        $this->rates    = $rates;
        $this->services = $services;
        $this->quotable = $quotable;
    }

    /**
     * Work through the itinerary a night at a time.
     *
     * @param array<int, array<string, mixed>> $days
     * @return array{nights:int, rate:?float, currency:string, rate_by:string, amount:?float,
     *               complimentary:int, unknown:int, lines:array<int,array<string,mixed>>, why:string}
     */
    public function plan(array $days, string $kind, ?int $pax, ?string $market): array
    {
        $lines         = [];
        $charged       = [];
        $complimentary = 0;
        $unknown       = 0;
        $currency      = 'USD';
        $rateBy        = 'none';

        foreach ($days as $d) {
            $night = [
                'day_no'   => (int) ($d['day_no'] ?? 0),
                'date'     => $d['day_date'] ?? null,
                'hotel'    => $d['hotel_name_raw'] ?? null,
                'meal'     => $d['meal_plan'] ?? null,
                'state'    => 'unknown',
                'rate'     => null,
                'currency' => null,
                'why'      => '',
            ];

            $named = trim((string) ($d['hotel_name_raw'] ?? ''));
            if ($named === '') {
                $night['state'] = 'no_hotel';
                $night['why']   = 'No hotel named for this night yet.';
                $lines[]        = $night;
                $unknown++;
                continue;
            }
            if (empty($d['hotel_id'])) {
                // a hotel IS named — it is just not one the panel holds, which is a
                // different problem from nobody having decided where they sleep
                $night['state'] = 'no_hotel';
                $night['why']   = $named . ' is not in the rates panel, so its contract cannot be read.';
                $lines[]        = $night;
                $unknown++;
                continue;
            }
            // A night whose room rate cannot be quoted cannot have a quotable guide room
            // either — the policy belongs to the same contract.
            if (($d['rate_state'] ?? '') !== 'rate') {
                $night['state'] = 'unknown';
                $night['why']   = 'No published contract covers this night, so what a room costs here is not known.';
                $lines[]        = $night;
                $unknown++;
                continue;
            }

            $hit = $this->policyNight((int) $d['hotel_id'], (string) $d['day_date'], $d['meal_plan'] ?? null, $kind, $pax, $market);
            $night = array_merge($night, $hit);
            $lines[] = $night;

            if ($night['state'] === 'complimentary') {
                $complimentary++;
            } elseif ($night['state'] === 'charged' && $night['rate'] !== null) {
                $charged[] = (float) $night['rate'];
                $currency  = $night['currency'] ?: $currency;
                $rateBy    = $night['rate_by'] ?? 'hotel_policy';
            } else {
                $unknown++;
            }
        }

        // The rate shown for the whole tour is the one that actually applies on the nights
        // that are charged. Where they differ the highest is used and the difference is
        // said out loud, because quoting the cheapest night across ten is how money is lost.
        $rate = $charged === [] ? null : max($charged);
        $spread = (count(array_unique($charged)) > 1);

        $nights = count($charged);
        $amount = ($rate !== null && $nights > 0) ? round($rate * $nights, 2) : null;

        $why = $this->summarise($kind, count($lines), $nights, $complimentary, $unknown, $spread, $charged, $currency);

        return [
            'nights'        => $nights,
            'rate'          => $rate,
            'currency'      => $currency,
            'rate_by'       => $rate === null ? 'none' : $rateBy,
            'amount'        => $amount,
            'complimentary' => $complimentary,
            'unknown'       => $unknown,
            'lines'         => $lines,
            'why'           => $why,
        ];
    }

    /**
     * One night at one hotel.
     *
     * @return array<string, mixed>
     */
    private function policyNight(int $hotelId, string $date, ?string $meal, string $kind, ?int $pax, ?string $market): array
    {
        try {
            $on = $this->rates->ratesOn($hotelId, $date, ($market ? ['market' => $market] : []) + ['plan_types' => \App\Libraries\HotelRates\RateLookup::quotationPlanTypes($this->db)]);
        } catch (\Throwable $e) {
            return ['state' => 'unknown', 'why' => 'The rate panel could not be asked about this night.'];
        }

        $policies = (array) ($on['guide_policies'] ?? []);
        $role     = $kind === 'guide' ? 'guide' : 'driver';

        // the policy for this role, preferring one that names this night's meal plan
        $match = null;
        foreach ($policies as $p) {
            if (!in_array((string) ($p['role'] ?? ''), [$role, 'any'], true)) {
                continue;
            }
            $pm = strtoupper(trim((string) ($p['meal_plan'] ?? '')));
            if ($meal !== null && $pm !== '' && $pm !== strtoupper((string) $meal)) {
                continue;
            }
            if ($match === null || ($pm !== '' && $meal !== null)) {
                $match = $p;
            }
        }

        if ($match !== null) {
            $free = $match['free_from_pax'];
            if ($free !== null && $free !== '' && $pax !== null && $pax >= (int) $free) {
                return [
                    'state' => 'complimentary', 'rate' => 0.0, 'currency' => null, 'rate_by' => 'hotel_policy',
                    'why'   => 'The contract gives this room free from ' . (int) $free . ' passengers, and there are ' . $pax . '.',
                ];
            }
            $amount = ($match['amount'] === null || $match['amount'] === '') ? null : (float) $match['amount'];
            if ($amount !== null && $amount > 0) {
                $where = trim((string) ($match['accommodation'] ?? '')) ?: 'unknown';
                return [
                    'state' => 'charged', 'rate' => $amount, 'currency' => trim((string) ($match['currency'] ?? '')) ?: 'USD',
                    'rate_by' => 'hotel_policy',
                    'why' => 'The contract charges for this room'
                        . ($meal ? ' on ' . strtoupper((string) $meal) : '')
                        . ($where !== 'unknown' ? ' (' . str_replace('_', ' ', $where) . ')' : '')
                        . ($free !== null && $free !== '' ? ', free only from ' . (int) $free . ' passengers' : '') . '.',
                ];
            }
            if ($amount !== null && $amount === 0.0) {
                return ['state' => 'complimentary', 'rate' => 0.0, 'currency' => null, 'rate_by' => 'hotel_policy',
                        'why' => 'The contract records this room at no charge.'];
            }
        }

        // No hotel policy. For a driver the company has its own figure; for a guide there
        // is nothing to fall back on, and saying so beats quoting a nought.
        if ($kind === 'driver') {
            $company = $this->companyDriverRate();
            if ($company !== null) {
                return ['state' => 'charged', 'rate' => $company['rate'], 'currency' => $company['currency'], 'rate_by' => 'company_rate',
                        'why' => 'No driver policy in this hotel\'s contract, so the company\'s own "' . self::COMPANY_DRIVER_ITEM . '" figure is used.'];
            }
        }

        return ['state' => 'unknown', 'rate' => null, 'currency' => null, 'rate_by' => 'none',
                'why' => 'This hotel\'s contract says nothing about a ' . $kind . ' room, so whether it is free is not known.'];
    }

    /** @return array{rate:float, currency:string}|null */
    private function companyDriverRate(): ?array
    {
        $m = $this->services->match(self::COMPANY_DRIVER_ITEM);
        if (($m['state'] ?? '') !== 'matched' || $m['unit_usd'] === null || $m['unit_usd'] <= 0) {
            return null;
        }
        return ['rate' => (float) $m['unit_usd'], 'currency' => 'USD'];
    }

    /** @param array<int, float> $charged */
    private function summarise(string $kind, int $total, int $nights, int $free, int $unknown, bool $spread, array $charged, string $cur): string
    {
        if ($total === 0) {
            return 'There is no itinerary yet, so there are no nights to work out.';
        }
        $bits = [];
        $bits[] = $nights === 0 ? 'No night is charged for' : "{$nights} of {$total} night(s) charged";
        if ($free > 0) {
            $bits[] = "{$free} complimentary by contract";
        }
        if ($unknown > 0) {
            $bits[] = "{$unknown} not known — nothing on file, so they are NOT assumed free";
        }
        $s = ucfirst(implode('; ', $bits)) . '.';
        if ($spread) {
            $s .= ' The nightly figure varies (' . $cur . ' ' . implode(', ', array_unique(array_map(static fn ($v) => (string) $v, $charged)))
                . '); the highest is used so the quotation is not short.';
        }
        return $s;
    }
}
