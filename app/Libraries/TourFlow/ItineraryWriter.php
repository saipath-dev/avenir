<?php

namespace App\Libraries\TourFlow;

/**
 * The itinerary written to Avenir's professional standard (schema 28): a paragraph for each
 * day that reads as a person wrote it, built from the day's own data and nothing else.
 *
 *   Day 1  Upon arrival at Bandaranaike International Airport on SriLankan Airlines flight
 *          UL 504, you will be met and assisted by our local representative. Thereafter,
 *          transfer to your hotel in Negombo.
 *   Day 2  After breakfast, proceed to Kandy. En route, visit the Dambulla Cave Temple,
 *          followed by a visit to the Spice Garden. Thereafter, continue to Kandy. Upon
 *          arrival, commence sightseeing in Kandy before proceeding to the hotel.
 *   Day 3  After breakfast, visit the Peradeniya Botanical Gardens. Upon completion of the
 *          sightseeing programme, return to the hotel.
 *   Day 4  Transfer to Bandaranaike International Airport for your departure flight to
 *          Tokyo aboard SriLankan Airlines UL 454.
 *
 * The flow is always arrival → meet and assist → hotel; breakfast → leave for the destination
 * → visits on the way → sightseeing at the destination → hotel; transfer → departure flight.
 * The sentences are settings (tf_meta itin_*, Admin → Settings), and a setting may hold
 * several alternatives separated by " | ": the writer takes them in turn from one day to the
 * next, so the wording varies naturally instead of repeating. The places, visits, hotels and
 * flights come from the inquiry, in the order the desk put them; a visit is told "en route",
 * "on arrival" or "before leaving" by where it is (the priced list's location for a matched
 * entry, else its own words against the two areas). Nothing is invented: an unknown flight
 * or airline is left out or printed as given, a day with no programme is a day at leisure,
 * and a person's own wording for a day (tf_intake_day.client_text) stands as written.
 */
final class ItineraryWriter
{
    /** The sentences, and what may be filled into each. Every one can be changed under Admin → Settings; " | " separates alternatives. */
    public const DEFAULTS = [
        'itin_airport'          => 'Bandaranaike International Airport',
        'itin_representative'   => 'our local representative',
        'itin_airlines'         => "UL = SriLankan Airlines\nEK = Emirates\nQR = Qatar Airways\nEY = Etihad Airways\nSQ = Singapore Airlines\nMH = Malaysia Airlines\nCX = Cathay Pacific\nAI = Air India\n6E = IndiGo\nTK = Turkish Airlines\nBA = British Airways\nLH = Lufthansa\nAF = Air France\nKL = KLM\nSU = Aeroflot\nTG = Thai Airways\nFZ = flydubai\nG9 = Air Arabia\nWY = Oman Air\nGF = Gulf Air\nSV = Saudia\nKU = Kuwait Airways\nJL = Japan Airlines\nNH = ANA\nKE = Korean Air\nOZ = Asiana Airlines\nCZ = China Southern\nCA = Air China\nMU = China Eastern\nUK = Vistara\n8D = FitsAir\nVJ = VietJet Air\nAK = AirAsia\nD7 = AirAsia X\nOD = Batik Air\nJ2 = Azerbaijan Airlines\nPG = Bangkok Airways",
        'itin_morning'          => 'After breakfast',
        'itin_morning_no_bf'    => 'In the morning',
        'itin_flight_in'        => ' on {airline} flight {number}',
        'itin_flight_in_raw'    => ' on {flight}',
        'itin_flight_out'       => ' aboard {airline} {number}',
        'itin_flight_out_raw'   => ' aboard {flight}',
        'itin_arrival'          => 'Upon arrival at {airport}{flight}, you will be met and assisted by {representative}. Thereafter, transfer to your hotel{in_area}.',
        'itin_arrival_visits'   => 'En route, {visits}.',
        'itin_move'             => '{morning}, leave for {area}. | {morning}, proceed to {area}.',
        'itin_move_before'      => '{morning}, {visits}. Thereafter, leave for {area}. | {morning}, {visits}, then proceed to {area}.',
        'itin_enroute'          => 'En route, {visits}. Thereafter, continue to {area}.',
        'itin_on_arrival'       => 'Upon arrival, {visits} before proceeding to the hotel. | Upon arrival in {area}, {visits}. Thereafter, proceed to the hotel.',
        'itin_checkin'          => 'Check in to {hotel} and spend the remainder of the day at leisure. | On arrival, check in to {hotel}; the rest of the day is at leisure.',
        'itin_same_area'        => '{morning}, transfer to {hotel} and check in. The remainder of the day is at leisure.',
        'itin_same_area_visits' => '{morning}, {visits}. Thereafter, transfer to {hotel} and check in.',
        'itin_stay'             => '{morning}, {visits}. Upon completion of the sightseeing programme, return to the hotel. | {morning}, {visits}. Thereafter, return to the hotel and spend the remainder of the day at leisure.',
        'itin_stay_free'        => 'Day at leisure{in_area}. | Spend the day at leisure{in_area}.',
        'itin_departure'        => 'Transfer to {airport} for your departure flight{to}{flight}.',
        'itin_departure_visits' => '{morning}, {visits}. Thereafter, transfer to {airport} for your departure flight{to}{flight}.',
        // schema 40: a lunch / dinner place of the Meal Master, said after the day (names only — never a price)
        'itin_meal_lunch'       => 'Lunch at {place}.',
        'itin_meal_dinner'      => 'Dinner at {place}.',
    ];

    /** What each sentence is for, for the settings screen. */
    public const LABELS = [
        'itin_airport'          => 'The airport',
        'itin_representative'   => 'Who meets the party',
        'itin_airlines'         => 'Airlines by code, one per line (CODE = Name) — the code in the flight the agent gave is written out; an unknown code is printed as given',
        'itin_morning'          => 'How a day starts when breakfast is included',
        'itin_morning_no_bf'    => 'How a day starts when it is not (room only)',
        'itin_flight_in'        => 'The arrival flight when its airline is known — {airline} {number}',
        'itin_flight_in_raw'    => 'The arrival flight as the agent wrote it, when the airline is not — {flight}',
        'itin_flight_out'       => 'The departure flight when its airline is known — {airline} {number}',
        'itin_flight_out_raw'   => 'The departure flight as written — {flight}',
        'itin_arrival'          => 'Arrival day — {airport} {flight} {representative} {in_area}',
        'itin_arrival_visits'   => 'Arrival day with visits on the way — {visits}',
        'itin_move'             => 'A day moving to a new area — {morning} {area}',
        'itin_move_before'      => 'Moving, with visits before leaving — {morning} {visits} {area}',
        'itin_enroute'          => 'Visits on the way, then on to the area — {visits} {area}',
        'itin_on_arrival'       => 'Sightseeing at the new area, then the hotel — {area} {visits}',
        'itin_checkin'          => 'The hotel when the day ends with the journey — {hotel}',
        'itin_same_area'        => 'A change of hotel in the same area — {morning} {hotel}',
        'itin_same_area_visits' => 'The same, with visits — {morning} {visits} {hotel}',
        'itin_stay'             => 'A day staying put, with a programme — {morning} {visits}',
        'itin_stay_free'        => 'A day staying put, with no programme — {in_area}',
        'itin_departure'        => 'Departure day — {airport} {to} {flight}',
        'itin_departure_visits' => 'Departure day with visits first — {morning} {visits} {airport} {to} {flight}',
        'itin_meal_lunch'       => 'The lunch place of a day, from the Meal Master, after the day\'s words — {place}',
        'itin_meal_dinner'      => 'The dinner place of a day, from the Meal Master, after the day\'s words — {place}',
    ];

    /** Settings shown as a box of lines rather than one line. */
    public const MULTILINE = ['itin_airlines'];

    private const BREAKFAST = ['BB', 'HB', 'FB', 'AI'];
    private const SMALL_WORDS = ['of', 'the', 'and', 'at', 'in', 'to', 'for', 'on', 'a', 'an', 'de', 'by', 'or', 'with'];
    private const GENERIC_AREA_WORDS = ['hotel', 'resort', 'area', 'city', 'town', 'and', 'the', 'via'];
    private const ACTIVITY_WORDS = ['safari', 'tour', 'ride', 'show', 'cruise', 'walk', 'trek', 'session', 'treatment', 'excursion', 'experience', 'hike', 'drive', 'dance', 'journey', 'trip', 'climb', 'trail'];
    /** A visit to one of these takes "the": the Dambulla Cave Temple, the Pinnawala Elephant Orphanage. */
    private const LANDMARK_WORDS = ['temple', 'fort', 'fortress', 'museum', 'garden', 'gardens', 'orphanage', 'rock', 'palace', 'lake', 'falls', 'park', 'sanctuary', 'church', 'mosque', 'kovil', 'dagoba', 'stupa', 'factory', 'estate', 'plantation', 'cave', 'caves', 'monastery', 'ruins', 'lighthouse', 'bridge', 'viewpoint', 'gap', 'reserve', 'centre', 'center', 'foundation', 'hatchery', 'farm', 'mine', 'mines', 'shrine', 'vihara', 'vihare', 'tank', 'reservoir', 'peak', 'plains', 'end', 'zoo', 'aquarium', 'harbour', 'market', 'bazaar', 'village'];

    private $db;
    private ?ServiceLookup $lookup;
    private ?array $settings = null;
    /** How many times each sentence has been used on the itinerary being written, so the alternatives take turns. */
    private array $turn = [];

    public function __construct($db, ?ServiceLookup $lookup = null)
    {
        $this->db     = $db;
        $this->lookup = $lookup;
    }

    /** The sentences in force: the defaults, with whatever Admin has changed under Settings. */
    public function settings(): array
    {
        if ($this->settings === null) {
            $this->settings = self::DEFAULTS;
            try {
                $keys = array_keys(self::DEFAULTS);
                $rows = $this->db->query('SELECT k, v FROM tf_meta WHERE k IN (' . implode(',', array_fill(0, count($keys), '?')) . ')', $keys)->getResultArray();
                foreach ($rows as $r) {
                    if (isset(self::DEFAULTS[$r['k']]) && trim((string) $r['v']) !== '') {
                        $this->settings[$r['k']] = (string) $r['v'];
                    }
                }
            } catch (\Throwable $e) {
                // no settings table: the defaults
            }
        }
        return $this->settings;
    }

    /**
     * The paragraph for each day of an inquiry, and the departure day after the last night.
     *
     * @param array<string, mixed> $it arrival_flight, departure_flight, nights, days (day_no, area, hotel_name_raw or hotel,
     *                                 meal_plan, client_text, services, excursions), and services / excursions (for the departure day)
     * @return array{days: array<int, array{text:string, standard:string, source:string, kind:string}>, departure: ?array{day_no:int, text:string, standard:string, source:string, kind:string}}
     */
    public function write(array $it): array
    {
        $this->turn = [];
        $s = $this->settings();
        // the nights, and (schema 32) any line kept apart because it falls on the departure day: it reads as the departure
        $days = array_values(array_merge((array) ($it['days'] ?? []), (array) ($it['departure_rows'] ?? [])));
        usort($days, static fn ($a, $b) => (int) $a['day_no'] <=> (int) $b['day_no']);
        // the nights the dates give; a day row past them is the departure day written as a night
        // (an email's "Day 9: shopping, then the airport"), and reads as the departure, not a night
        $nights = (int) ($it['tour_dates']['nights'] ?? ($it['nights'] ?? 0));   // schema 32: the dates decide
        if ($nights <= 0) {
            $nights = $days ? (int) end($days)['day_no'] : 0;
        }
        $extra = array_values(array_filter($days, static fn ($d) => (int) $d['day_no'] > $nights));
        $days  = array_values(array_filter($days, static fn ($d) => (int) $d['day_no'] <= $nights));
        $out  = ['days' => [], 'departure' => null];
        $prev = null;
        foreach ($days as $i => $d) {
            $dayNo = (int) $d['day_no'];
            $hotel = self::tidyName((string) ($d['hotel_name_raw'] ?? $d['hotel'] ?? ''));
            $area  = self::tidyName((string) ($d['area'] ?? ''));
            $place = self::placeName($area, $hotel);   // the area as a sentence says it
            $items = $this->items((array) ($d['services'] ?? []), (array) ($d['excursions'] ?? []));
            // schema 40: the day's lunch place of the Meal Master is said where it falls among the day's stops — on the way on
            // the arrival day and on a day moving to a new area, after the visits on a day with a programme; on a quiet day it
            // is its own sentence after the day ("Lunch at …."), as a dinner always is (mealWords)
            $lunches   = $this->mealItems((array) ($d['services'] ?? []));
            $mealsSaid = false;
            if ($i === 0) {
                $kind = 'arrival';
                $txA = (array) (($it['hotel_transfers'] ?? [])['arrival'] ?? []);
                if (($txA['by'] ?? '') === 'hotel' && $hotel !== '') {
                    // tf-journey-r1: the hotel collects them — the sentence says so, never "our representative"
                    $text = 'Arrive at ' . $s['itin_airport'] . $this->flight($it['arrival_flight'] ?? null, 'in') . ' and transfer to ' . $hotel . '. Your airport pick-up is arranged by '
                        . (!empty($txA['hotel']) && (int) ($txA['night'] ?? 0) === $dayNo ? 'the hotel' : (!empty($txA['hotel']) ? self::tidyName((string) $txA['hotel']) : 'the hotel')) . '.';
                } else {
                    $text = $this->say('itin_arrival', ['airport' => $s['itin_airport'], 'flight' => $this->flight($it['arrival_flight'] ?? null, 'in'), 'representative' => $s['itin_representative'], 'in_area' => $place !== '' ? ' in ' . $place : ($hotel !== '' ? ', ' . $hotel : '')]);
                }
                if ($lunches) {
                    $items = array_merge($items, $lunches);
                    $mealsSaid = true;
                }
                if ($items) {
                    $text .= ' ' . $this->say('itin_arrival_visits', ['visits' => $this->phrase($items)]);
                }
            } else {
                $morning   = in_array(strtoupper((string) ($prev['meal_plan'] ?? '')), self::BREAKFAST, true) ? $s['itin_morning'] : $s['itin_morning_no_bf'];
                $prevHotel = self::tidyName((string) ($prev['hotel_name_raw'] ?? $prev['hotel'] ?? ''));
                $prevArea  = self::tidyName((string) ($prev['area'] ?? ''));
                $sameHotel = $hotel !== '' && self::key($hotel) === self::key($prevHotel);
                $sameArea  = self::areaWords($area) === self::areaWords($prevArea) && self::areaWords($area) !== [];
                $hotelWord = $hotel !== '' ? $hotel : 'the hotel';
                if ($items && $lunches && ($sameHotel || $sameArea)) {
                    $items = array_merge($items, $lunches);
                    $mealsSaid = true;
                }
                if ($sameHotel || ($hotel === '' && $sameArea)) {
                    $kind = 'stay';
                    $text = $items
                        ? $this->say('itin_stay', ['morning' => $morning, 'visits' => $this->phrase($items)])
                        : $this->say('itin_stay_free', ['morning' => $morning, 'in_area' => $place !== '' ? ' in ' . $place : '']);
                } elseif ($sameArea) {
                    $kind = 'move';
                    $text = $items
                        ? $this->say('itin_same_area_visits', ['morning' => $morning, 'visits' => $this->phrase($items), 'hotel' => $hotelWord])
                        : $this->say('itin_same_area', ['morning' => $morning, 'hotel' => $hotelWord]);
                } else {
                    $kind = 'move';
                    [$before, $enRoute, $after] = $this->buckets($items, $prevArea, $area);
                    // schema 40: a lunch / dinner place of the Meal Master where it falls on the day — still in the old
                    // area, on the way, or on arrival in the new one — after the visits of that part of the day
                    $fromW = self::areaWords($prevArea);
                    $toW   = self::areaWords($area);
                    foreach ($lunches as $mi) {
                        $inFrom = self::mentions($mi['words'], $fromW);
                        $inTo   = self::mentions($mi['words'], $toW);
                        if ($inFrom && !$inTo) {
                            $before[] = $mi;
                        } elseif ($inTo && !$inFrom) {
                            $after[] = $mi;
                        } else {
                            $enRoute[] = $mi;
                        }
                        $mealsSaid = true;
                    }
                    $to   = $place !== '' ? $place : ($hotel !== '' ? $hotel : 'the next hotel');
                    $text = $before
                        ? $this->say('itin_move_before', ['morning' => $morning, 'visits' => $this->phrase($before), 'area' => $to])
                        : $this->say('itin_move', ['morning' => $morning, 'area' => $to]);
                    if ($enRoute) {
                        $text .= ' ' . $this->say('itin_enroute', ['visits' => $this->phrase($enRoute), 'area' => $to]);
                    }
                    if ($after) {
                        // the day ends with the sightseeing at the new area, then the hotel
                        $text .= ' ' . $this->say('itin_on_arrival', ['area' => $to, 'visits' => $this->phrase($after)]);
                    } else {
                        $text .= ' ' . $this->say('itin_checkin', ['hotel' => $hotelWord]);
                    }
                }
            }
            $text .= $this->mealWords((array) ($d['services'] ?? []), $mealsSaid);
            $own = trim((string) ($d['client_text'] ?? ''));
            $out['days'][$dayNo] = ['text' => $own !== '' ? $own : $text, 'standard' => $text, 'source' => $own !== '' ? 'own' : 'standard', 'kind' => $kind];
            $prev = $d;
        }
        // the departure day: after the last night, with anything the desk put on that day first
        if ($nights > 0) {
            $depNo = $nights + 1;
            $svcs  = array_values(array_filter((array) ($it['services'] ?? []), static fn ($x) => (int) ($x['day_no'] ?? -1) === $depNo));
            $excs  = array_values(array_filter((array) ($it['excursions'] ?? []), static fn ($x) => (int) ($x['day_no'] ?? -1) === $depNo));
            $seen  = array_map(static fn ($x) => (int) ($x['id'] ?? 0), $svcs);
            foreach ($extra as $e) {
                // the departure day's own row: its stops belong to the departure sentence
                foreach ((array) ($e['services'] ?? []) as $x) {
                    if (!in_array((int) ($x['id'] ?? 0), $seen, true)) {
                        $svcs[] = $x;
                    }
                }
                foreach ((array) ($e['excursions'] ?? []) as $x) {
                    $excs[] = $x;
                }
            }
            // schema 40: a lunch place on the departure day is had before the transfer
            $items = array_merge($this->items($svcs, $excs), $this->mealItems($svcs));
            $morning = in_array(strtoupper((string) ($prev['meal_plan'] ?? '')), self::BREAKFAST, true) ? $s['itin_morning'] : $s['itin_morning_no_bf'];
            $dest   = self::destination((string) ($it['departure_flight'] ?? ''));
            $flight = $this->flight($it['departure_flight'] ?? null, 'out');
            // the city is said once: not again when the flight, printed as written, already names it
            $vars = ['morning' => $morning, 'airport' => $s['itin_airport'], 'flight' => $flight, 'to' => $dest !== '' && !str_contains($flight, ' to ' . $dest) ? ' to ' . $dest : ''];
            $text = $items
                ? $this->say('itin_departure_visits', $vars + ['visits' => $this->phrase($items)])
                : $this->say('itin_departure', $vars);
            $txD = (array) (($it['hotel_transfers'] ?? [])['departure'] ?? []);
            $lastHotel = self::tidyName((string) ($prev['hotel_name_raw'] ?? $prev['hotel'] ?? ''));
            if (($txD['by'] ?? '') === 'hotel') {
                // tf-journey-r1: the hotel takes them to the airport — from the last hotel, said so
                $by   = !empty($txD['hotel']) && (int) ($txD['night'] ?? 0) === (int) ($prev['day_no'] ?? -1) ? 'the hotel' : (!empty($txD['hotel']) ? self::tidyName((string) $txD['hotel']) : 'the hotel');
                $from = $lastHotel !== '' ? ' from ' . $lastHotel : '';
                $text = $items
                    ? $morning . ', ' . $this->phrase($items) . '. Thereafter, transfer' . $from . ' to ' . $s['itin_airport'] . ' for your departure flight' . $vars['to'] . $flight . '.'
                    : 'Transfer' . $from . ' to ' . $s['itin_airport'] . ' for your departure flight' . $vars['to'] . $flight . '.';
                $text .= ' Your airport drop-off is arranged by ' . $by . '.';
            }
            $text .= $this->mealWords($svcs, true);
            $own = trim((string) ($it['departure_text'] ?? ''));
            foreach ($extra as $e) {
                if (trim((string) ($e['client_text'] ?? '')) !== '') {
                    $own = trim((string) $e['client_text']);   // a person's words on the departure day's row
                }
            }
            $out['departure'] = ['day_no' => $depNo, 'text' => $own !== '' ? $own : $text, 'standard' => $text, 'source' => $own !== '' ? 'own' : 'standard', 'kind' => 'departure'];
            foreach ($extra as $e) {
                // the row reads as the departure day too, so a screen listing the rows shows the same words once
                $out['days'][(int) $e['day_no']] = $out['departure'];
            }
        }
        return $out;
    }

    // =====================================================================
    //  The sentences
    // =====================================================================

    /** A sentence from its setting, the alternatives ("a | b | c") taken in turn across the itinerary, filled in and tidied. */
    private function say(string $key, array $vars): string
    {
        $alts = self::alternatives($this->settings()[$key] ?? self::DEFAULTS[$key]);
        $n = $this->turn[$key] ?? 0;
        $this->turn[$key] = $n + 1;
        return $this->fill($alts[$n % count($alts)], $vars);
    }

    /** The alternatives a setting holds, separated by " | ". */
    public static function alternatives(string $setting): array
    {
        $alts = array_values(array_filter(array_map('trim', explode(' | ', $setting)), static fn ($a) => $a !== ''));
        return $alts ?: [''];
    }

    // =====================================================================
    //  Flights
    // =====================================================================

    /** The airlines Admin has listed, code => name. */
    public function airlines(): array
    {
        $out = [];
        foreach (preg_split('/[\r\n;]+/', (string) ($this->settings()['itin_airlines'] ?? '')) ?: [] as $line) {
            if (preg_match('/^\s*([A-Z0-9]{2})\s*=\s*(.+?)\s*$/i', $line, $m)) {
                $out[strtoupper($m[1])] = $m[2];
            }
        }
        return $out;
    }

    /**
     * The flight as the agent wrote it, read for its airline and number: "UL 504", "UL504 arr 06:30",
     * "SriLankan UL-504". A code that is on the airline list is written out; anything else is
     * printed as given. Nothing when no flight is known.
     *
     * @return array{airline: ?string, code: ?string, number: ?string, raw: string}
     */
    public function readFlight(?string $raw): array
    {
        $raw = trim((string) preg_replace('/\s+/', ' ', (string) $raw));
        $raw = trim((string) preg_replace('/^flight\s+/i', '', $raw));   // "Flight UL 504" is UL 504
        if ($raw === '') {
            return ['airline' => null, 'code' => null, 'number' => null, 'raw' => ''];
        }
        $airlines = $this->airlines();
        if (preg_match_all('/\b([A-Z][A-Z0-9]|[0-9][A-Z])\s?-?\s?(\d{2,4})\b/', strtoupper($raw), $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                if (isset($airlines[$hit[1]])) {
                    // the number as the agent wrote it (UL454 or UL 454), in capitals, one space at most
                    $number = (string) preg_replace('/\s*-\s*/', ' ', trim($hit[0]));
                    return ['airline' => $airlines[$hit[1]], 'code' => $hit[1], 'number' => $number, 'raw' => $raw];
                }
            }
        }
        return ['airline' => null, 'code' => null, 'number' => null, 'raw' => $raw];
    }

    /** The flight words for a sentence: with the airline written out when it is known, as given when not, nothing when there is no flight. */
    private function flight($raw, string $way): string
    {
        $f = $this->readFlight($raw);
        if ($f['raw'] === '') {
            return '';
        }
        $s = $this->settings();
        $t = $f['airline'] !== null
            ? $this->fill($s['itin_flight_' . $way], ['airline' => $f['airline'], 'number' => $f['number']], false)
            : $this->fill($s['itin_flight_' . $way . '_raw'], ['flight' => $f['raw']], false);
        return $t === '' ? '' : ' ' . ltrim($t);
    }

    /** The city a departure flight goes to, when the agent wrote it ("UL 454 to Tokyo"); nothing otherwise. */
    public static function destination(string $raw): string
    {
        if (preg_match('/\bto\s+([A-Z][A-Za-z\'\-]+(?:\s+[A-Z][A-Za-z\'\-]+){0,2})/', $raw, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    // =====================================================================
    //  The stops of a day
    // =====================================================================

    /**
     * The day's stops as things to say, in the order the desk put them: each with the words to
     * say it with (visit the …, enjoy the …, have lunch at …). Guides and transport are not stops.
     *
     * @return array<int, array{text:string, verb:string, words:string}>
     */
    private function items(array $services, array $excursions): array
    {
        $out = [];
        foreach ($services as $sv) {
            $kind = (string) ($sv['kind'] ?? 'other');
            if (in_array($kind, ['guide', 'transport'], true) || (string) ($sv['pay_mode'] ?? '') === 'not_arranged') {
                continue;
            }
            if (!empty($sv['meal_id'])) {
                continue;   // schema 40: a lunch / dinner place of the Meal Master is said by mealItems / mealWords, where it falls on the day
            }
            $title = trim((string) ($sv['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $place = '';
            if (!empty($sv['free_visit_id']) && !empty($sv['free_destination'])) {
                // a free visit from the master: its destination says where on the day it falls (schema 29)
                $place = (string) $sv['free_destination'];
            } elseif ($this->lookup !== null && !empty($sv['place_id']) && ($pl = $this->lookup->placeEntry((int) $sv['place_id'])) !== null) {
                // schema 39: the place of the Place to Visit / Entrance Fee Master — its destination, else its location
                $place = trim((string) ($pl['destination'] ?? '')) !== '' ? (string) $pl['destination'] : (string) ($pl['location'] ?? '');
            } elseif ($this->lookup !== null && !empty($sv['fee_id'])) {
                try {
                    // the destination a person put the entry under (Paid Activities & Services), else its address as before
                    $entry = $this->lookup->entry((int) $sv['fee_id']);
                    $place = trim((string) ($entry['dest_set'] ?? '')) !== '' ? (string) $entry['dest_set'] : (string) ($entry['location'] ?? '');
                } catch (\Throwable $e) {
                    $place = '';
                }
            }
            $said = !empty($sv['free_visit_id']) ? self::freeItem($title, (string) ($sv['free_kind'] ?? '')) : null;
            $out[] = ($said ?? self::item($title, $kind)) + ['words' => trim(ServiceLookup::words($place) . ' ' . ServiceLookup::words($title))];
        }
        foreach ($excursions as $ex) {
            $name = trim((string) ($ex['name'] ?? ''));
            if ($name !== '') {
                $out[] = self::item($name, 'excursion') + ['words' => ServiceLookup::words($name)];
            }
        }
        return $out;
    }

    /**
     * Schema 40: the lunch and dinner places of the Meal Master on a day, one sentence each after the day's words
     * ("Lunch at Kadugannawa Rest House.") — the restaurant's name only, never a price.
     */
    /** Schema 40: the Meal Master's lunch places of a day as stops ("have lunch at …"); a dinner is never a stop of the day. */
    private function mealItems(array $services): array
    {
        $out = [];
        foreach ($services as $sv) {
            $kind = (string) ($sv['kind'] ?? '');
            if (empty($sv['meal_id']) || $kind !== 'lunch' || in_array((string) ($sv['pay_mode'] ?? ''), ['not_arranged', 'own_arrangement'], true)) {
                continue;
            }
            $title = trim((string) ($sv['title'] ?? ''));
            if ($title !== '') {
                $out[] = self::item($title, $kind) + ['words' => ServiceLookup::words($title)];
            }
        }
        return $out;
    }

    private function mealWords(array $services, bool $lunchSaid = false): string
    {
        $said = ['lunch' => [], 'dinner' => []];
        foreach ($services as $sv) {
            $kind = (string) ($sv['kind'] ?? '');
            if (empty($sv['meal_id']) || !isset($said[$kind]) || ($kind === 'lunch' && $lunchSaid) || in_array((string) ($sv['pay_mode'] ?? ''), ['not_arranged', 'own_arrangement'], true)) {
                continue;
            }
            $place = trim((string) preg_replace('/^(lunch|dinner)\b\s*(at\b\s*)?/i', '', trim((string) ($sv['title'] ?? ''))));
            if ($place !== '' && !in_array($place, $said[$kind], true)) {
                $said[$kind][] = $place;
            }
        }
        $out = '';
        foreach ($said as $kind => $places) {
            if ($places) {
                $out .= ' ' . $this->say('itin_meal_' . $kind, ['place' => self::joinAnd($places)]);
            }
        }
        return $out;
    }

    /** One stop as words: the verb for its kind and the name tidied — S/S becomes sightseeing in the place, capitals become a name, a landmark takes "the". */
    public static function item(string $title, string $kind): array
    {
        $words = ServiceLookup::words($title);
        // "Colombo shopping", "shopping": time for shopping in the place
        if (preg_match('/^(?:(.+?)\s+)?shopping(?:\s+(.+))?$/', $words, $m)) {
            $place = trim(($m[1] ?? '') . ' ' . ($m[2] ?? ''));
            return ['text' => 'some time for shopping' . ($place !== '' ? ' in ' . self::titleCase($place) : ''), 'verb' => 'enjoy'];
        }
        // "Kandy S/S", "Colombo city tour", "sightseeing": sightseeing in the place
        if (preg_match('/^(?:(.+?)\s+)?(ss|city tour)(?:\s+(.+))?$/', $words, $m)) {
            $place = trim(($m[1] ?? '') . ' ' . ($m[3] ?? ''));
            $what  = $m[2] === 'ss' ? 'sightseeing' : 'a city tour';
            return ['text' => $what . ($place !== '' ? ($m[2] === 'ss' ? ' in ' : ' of ') . self::titleCase($place) : ''), 'verb' => 'commence'];
        }
        $text = self::tidyName($title);
        switch ($kind) {
            case 'lunch':
            case 'dinner':
                // "Lunch at the Tea Factory Hotel" is had at the Tea Factory Hotel; a bare "Lunch" is just had
                $rest = trim((string) preg_replace('/^(lunch|dinner)\b\s*(at\b\s*)?/i', '', $text));
                return $rest === '' ? ['text' => '', 'verb' => 'have ' . $kind] : ['text' => $rest, 'verb' => 'have ' . $kind . ' at'];
            case 'train':
                return ['text' => self::withThe($text), 'verb' => 'take'];
            case 'safari':
            case 'ayurveda':
            case 'excursion':
                return ['text' => self::withThe($text, true), 'verb' => 'enjoy'];
            default:
                // a show, a dance, a walk: enjoyed, not visited
                if (preg_match('/\b(' . implode('|', self::ACTIVITY_WORDS) . ')\b/i', $text)) {
                    return ['text' => self::withThe($text, true), 'verb' => 'enjoy'];
                }
                // "Visit to the temple" and "Tea factory visit" are visits to the place itself
                if (preg_match('/^visit\s+(?:to\s+)?(.+)$/i', $text, $m)) {
                    $text = $m[1];
                } elseif (preg_match('/^(.+?)\s+visit$/i', $text, $m)) {
                    $text = $m[1];
                }
                return ['text' => self::withThe($text, false, true), 'verb' => 'visit'];
        }
    }

    /**
     * A free visit said by its sort in the Free Visits master: a photo stop is stopped at for
     * photographs, a walk is taken; any other sort reads like any visit. Null for "like any visit".
     */
    public static function freeItem(string $title, string $freeKind): ?array
    {
        $text = self::tidyName($title);
        if ($freeKind === 'photo') {
            $text = trim((string) preg_replace('/^(?:a\s+)?photo(?:graph)?s?(?:\s+stop)?\s*(?:at|of|on)?\s+/i', '', $text));
            $text = trim((string) preg_replace('/\s+photo(?:graph)?s?(?:\s+stop)?$/i', '', $text));
            return $text === '' ? null : ['text' => self::withThe($text, false, true), 'verb' => 'stop for photographs at'];
        }
        if ($freeKind === 'walk') {
            if (preg_match('/^(?:a\s+)?(?:walk|stroll)\s+(round|around|along|through|in|on|across|to|up)\s+(.+)$/i', $text, $m)) {
                return ['text' => self::withThe(trim($m[2]), false, true), 'verb' => 'take a walk ' . strtolower($m[1])];
            }
            if (!preg_match('/\b(walk|stroll|trail|trek|hike)\b/i', $text)) {
                return ['text' => self::withThe($text, false, true), 'verb' => 'take a walk around'];
            }
        }
        return null;
    }

    /** "the" before an activity or a landmark ("the Minneriya jeep safari", "the Dambulla Cave Temple") unless the name already carries an article. */
    private static function withThe(string $text, bool $onlyActivities = false, bool $onlyLandmarks = false): string
    {
        if ($text === '' || preg_match('/^(a|an|the|your|some)\s/i', $text)) {
            return $text;
        }
        if ($onlyActivities && !preg_match('/\b(' . implode('|', self::ACTIVITY_WORDS) . ')\b/i', $text)) {
            return $text;
        }
        if ($onlyLandmarks && !preg_match('/^(' . implode('|', self::LANDMARK_WORDS) . ')\b/i', $text) && !preg_match('/\b(' . implode('|', self::LANDMARK_WORDS) . ')$/i', $text)) {
            return $text;
        }
        return 'the ' . $text;
    }

    /**
     * The stops as one phrase, in order, the verb said once for a run of the same kind: two
     * visits read "visit A, followed by a visit to B"; more read "visit A, B and C"; a run of
     * another kind follows with "then".
     */
    private function phrase(array $items): string
    {
        $runs = [];
        foreach ($items as $it) {
            $last = $runs ? count($runs) - 1 : -1;
            if ($last >= 0 && $runs[$last]['verb'] === $it['verb']) {
                $runs[$last]['texts'][] = $it['text'];
            } else {
                $runs[] = ['verb' => $it['verb'], 'texts' => [$it['text']]];
            }
        }
        $parts = [];
        foreach ($runs as $r) {
            $texts = array_values(array_filter(array_map('trim', $r['texts']), static fn ($t) => $t !== ''));
            if ($r['verb'] === 'visit' && count($texts) === 2) {
                $parts[] = 'visit ' . $texts[0] . ', followed by a visit to ' . $texts[1];
            } else {
                $parts[] = trim($r['verb'] . ' ' . self::joinAnd($texts));
            }
        }
        return implode(', then ', $parts);
    }

    /** "A", "A and B", "A, B and C". */
    private static function joinAnd(array $texts): string
    {
        $texts = array_values(array_filter(array_map('trim', $texts), static fn ($t) => $t !== ''));
        if (count($texts) <= 1) {
            return $texts[0] ?? '';
        }
        $last = array_pop($texts);
        return implode(', ', $texts) . ' and ' . $last;
    }

    /**
     * Where each stop falls on a day that moves from one area to another. The desk's order is
     * the travelling order, so it is never shuffled: the stops at the area being left come
     * first (before leaving), then the way, and from the first stop at the new area on,
     * everything is on arrival. A stop's words are the priced list's location for it and its
     * own name; an area's words are the places in it.
     *
     * @return array{0: array, 1: array, 2: array} before, en route, on arrival
     */
    private function buckets(array $items, string $fromArea, string $toArea): array
    {
        $from = self::areaWords($fromArea);
        $to   = self::areaWords($toArea);
        $before = $enRoute = $after = [];
        $stage = 'before';
        foreach ($items as $it) {
            $inFrom = self::mentions($it['words'], $from);
            $inTo   = self::mentions($it['words'], $to);
            if ($stage === 'before' && !($inFrom && !$inTo)) {
                $stage = 'route';
            }
            if ($stage === 'route' && $inTo && !$inFrom) {
                $stage = 'after';
            }
            if ($stage === 'before') {
                $before[] = $it;
            } elseif ($stage === 'route') {
                $enRoute[] = $it;
            } else {
                $after[] = $it;
            }
        }
        return [$before, $enRoute, $after];
    }

    /**
     * The place an area stands for in a sentence: the town at the end of an address ("Kynrock
     * Airport Hotel, Canada Friendship Road, Katunayake" is Katunayake), and of "Sigiriya /
     * Dambulla / Kandalama" the part the hotel's name carries (Heritance Kandalama: Kandalama),
     * else the first. The whole area still counts when a visit is placed (areaWords).
     */
    /**
     * tf-journey-r1: the day headings of "Your Sri Lanka journey" — from the booking itself, never invented:
     * the first day "Arrival · Kandalama", a night at the same hotel "Kandalama · At leisure" (or the day's own
     * visits by name), a move "Kandalama to Negombo", and the last "Departure · Airport transfer". Also the hotel
     * names the text is to show in bold, and the hotel each night stays at.
     *
     * @param array $days each with day_no, area, a hotel name (hotel_name_raw or hotel), services and excursions
     * @return array{headings: array<int,string>, hotels: string[], overnight: array<int,string>}
     */
    public static function journey(array $days, ?int $depNo, array $transfers = []): array
    {
        usort($days, static fn ($a, $b) => (int) $a['day_no'] <=> (int) $b['day_no']);
        $short = static function (array $d): string {
            $hotel = self::tidyName((string) ($d['hotel_name_raw'] ?? $d['hotel'] ?? ''));
            $place = self::placeName((string) ($d['area'] ?? ''), $hotel);
            if ($place !== '') {
                return $place;
            }
            $w = preg_split('/\s+/', $hotel) ?: [];
            if (count($w) > 1 && in_array(strtolower($w[0]), ['heritance', 'jetwing', 'cinnamon', 'amaya', 'hotel', 'the', 'avani', 'anantara', 'uga', 'mandara'], true)) {
                array_shift($w);
            }
            return trim(implode(' ', $w));
        };
        $out = ['headings' => [], 'hotels' => [], 'overnight' => [], 'transfers' => ['arrival' => $transfers['arrival']['by'] ?? 'car', 'departure' => $transfers['departure']['by'] ?? 'car']];
        $prev = null;
        foreach ($days as $i => $d) {
            $n = (int) $d['day_no'];
            if ($depNo !== null && $n >= $depNo) {
                continue;
            }
            $hotel = self::tidyName((string) ($d['hotel_name_raw'] ?? $d['hotel'] ?? ''));
            $place = $short($d);
            if ($hotel !== '') {
                $out['hotels'][] = $hotel;
                $out['overnight'][$n] = $hotel;
            }
            $visits = [];
            foreach ((array) ($d['services'] ?? []) as $sv) {
                $k = (string) ($sv['kind'] ?? '');
                if (in_array($k, ['lunch', 'dinner', 'guide', 'transport', 'meal'], true) || in_array((string) ($sv['pay_mode'] ?? ''), ['not_arranged'], true) || !empty($sv['meal_id'])) {
                    continue;
                }
                $visits[] = self::tidyName((string) ($sv['title'] ?? ''));
            }
            foreach ((array) ($d['excursions'] ?? []) as $x) {
                $visits[] = self::tidyName((string) ($x['name'] ?? ''));
            }
            $visits = array_values(array_unique(array_filter($visits)));
            $prevHotel = $prev ? self::tidyName((string) ($prev['hotel_name_raw'] ?? $prev['hotel'] ?? '')) : null;
            if ($i === 0 || $prev === null) {
                $h = 'Arrival' . ($place !== '' ? ' · ' . $place : '');
            } elseif ($hotel !== '' && $prevHotel !== null && strcasecmp($hotel, $prevHotel) === 0) {
                $h = ($place !== '' ? $place . ' · ' : '') . ($visits ? implode(' & ', array_slice($visits, 0, 2)) : 'At leisure');
            } else {
                $from = $short($prev);
                $h = $from !== '' && $place !== '' && strcasecmp($from, $place) !== 0 ? $from . ' to ' . $place : ($place !== '' ? $place : 'Day ' . $n);
            }
            $out['headings'][$n] = $h;
            $prev = $d;
        }
        if ($depNo !== null) {
            $out['headings'][$depNo] = 'Departure · Airport transfer';
        }
        foreach ($transfers as $t) {
            if (($t['by'] ?? '') === 'hotel' && !empty($t['hotel'])) {
                $out['hotels'][] = self::tidyName((string) $t['hotel']);
            }
        }
        $out['hotels'] = array_values(array_unique(array_filter($out['hotels'])));
        usort($out['hotels'], static fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));   // the longer name first, so a short one never splits it
        return $out;
    }

    /** tf-journey-r1: escaped text with each hotel name in bold (HTML), for the journey on screen and on paper. */
    public static function boldHotels(string $text, array $hotels): string
    {
        $html = esc($text);
        foreach ($hotels as $h) {
            $e = esc((string) $h);
            if ($e === '') {
                continue;
            }
            $html = (string) preg_replace('/(?<![>\w])' . preg_quote($e, '/') . '(?![\w<])/u', '<b>' . $e . '</b>', $html);
        }
        return $html;
    }

    public static function placeName(string $area, string $hotel = ''): string
    {
        $area = self::tidyName($area);
        if ($area === '') {
            return '';
        }
        if (str_contains($area, ',')) {
            $parts = array_values(array_filter(array_map('trim', explode(',', $area)), static fn ($p) => $p !== ''));
            $area  = $parts ? end($parts) : $area;
        }
        if (preg_match('/[\/|]/', $area)) {
            $parts = array_values(array_filter(array_map('trim', preg_split('/\s*[\/|]\s*/', $area) ?: []), static fn ($p) => $p !== ''));
            $hw = ' ' . ServiceLookup::words($hotel) . ' ';
            foreach ($parts as $p) {
                $pw = ServiceLookup::words($p);
                if ($hotel !== '' && $pw !== '' && str_contains($hw, ' ' . $pw . ' ')) {
                    return $p;
                }
            }
            return $parts[0] ?? $area;
        }
        return $area;
    }

    /** The place words of an area: "Anuradhapura / Sigiriya" gives anuradhapura and sigiriya; hotel, resort and the like are not places. */
    public static function areaWords(string $area): array
    {
        $out = [];
        foreach (explode(' ', ServiceLookup::words($area)) as $w) {
            if (strlen($w) >= 3 && !in_array($w, self::GENERIC_AREA_WORDS, true) && !in_array($w, $out, true)) {
                $out[] = $w;
            }
        }
        sort($out);
        return $out;
    }

    private static function mentions(string $words, array $areaWords): bool
    {
        $w = ' ' . $words . ' ';
        foreach ($areaWords as $a) {
            if (str_contains($w, ' ' . $a . ' ')) {
                return true;
            }
        }
        return false;
    }

    // =====================================================================
    //  Words
    // =====================================================================

    /** The sentence with its places filled in and its punctuation tidied; a sentence starts with a capital and ends with a full stop. */
    private function fill(string $tpl, array $vars, bool $sentence = true): string
    {
        $text = $tpl;
        foreach ($vars as $k => $v) {
            $text = str_replace('{' . $k . '}', (string) $v, $text);
        }
        $text = (string) preg_replace('/\{[a-z_]+\}/', '', $text);   // a placeholder the sentence has no value for
        $text = (string) preg_replace('/\s+/', ' ', $text);
        $text = str_replace([' ,', ' .', ',.', ',,', '..', ' ;'], [',', '.', '.', ',', '.', ';'], $text);
        $text = trim($text);
        if ($sentence && $text !== '') {
            $text = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
            if (!preg_match('/[.!?]$/', $text)) {
                $text .= '.';
            }
        }
        return $text;
    }

    /** A name as it is written on paper: a name typed in capitals becomes a name, one typed properly is kept. */
    public static function tidyName(string $name): string
    {
        $name = trim((string) preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            return '';
        }
        if (mb_strtoupper($name) === $name && preg_match('/[A-Z]{3,}/', $name)) {
            return self::titleCase($name);
        }
        return $name;
    }

    private static function titleCase(string $s): string
    {
        $words = explode(' ', mb_strtolower(trim($s)));
        foreach ($words as $i => $w) {
            if ($w === '') {
                continue;
            }
            if ($i > 0 && in_array($w, self::SMALL_WORDS, true)) {
                continue;
            }
            // Ella-Wellawaya, O'Brien: each part capitalised
            $words[$i] = implode('-', array_map(static fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)) . mb_substr($p, 1), explode('-', $w)));
        }
        return implode(' ', $words);
    }

    private static function key(string $s): string
    {
        return ServiceLookup::words($s);
    }
}
