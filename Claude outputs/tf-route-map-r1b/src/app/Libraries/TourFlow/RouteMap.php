<?php

namespace App\Libraries\TourFlow;

/**
 * tf-route-map-r1 (10 Oct 2026) — the driver's route map: the tour's journey drawn on an outline of Sri Lanka, with
 * the day-by-day planned mileage, for the Driver's Pack and for WhatsApp as one clear image or a PDF.
 *
 * Everything on it is the tour's own saved record — the transport card's legs (tf_transport_leg: the costed and
 * amended copy of the quotation's mileage, with each day's night hotel) and the card's header. Nothing is worked out
 * again here, nothing is written, and nothing a driver should not see is on it: no rate, no price, no margin, no
 * control. The distances are the PLANNED mileage; actual driving varies, and the sheet says so.
 *
 * The map is schematic: towns sit at their true position on a simplified coastline (Natural Earth 10m, public domain),
 * the route is straight lines between stops with the direction marked, and a place the gazetteer does not know is
 * listed under the map rather than guessed onto it.
 */
final class RouteMap
{
    /** The coastline, simplified: rings of [lon, lat]; the first is the island, the rest the Jaffna peninsula's islands and Mannar. */
    private const OUTLINE = [
        [[81.876,7.092], [81.89,7.02], [81.885,6.98], [81.839,6.821], [81.832,6.765], [81.78,6.664], [81.787,6.639], [81.776,6.61], [81.681,6.462], [81.65,6.431], [81.548,6.371], [81.362,6.225], [81.325,6.205], [81.204,6.163], [81.203,6.178], [81.188,6.182], [81.176,6.15], [81.113,6.12], [80.903,6.071], [80.877,6.061], [80.862,6.04], [80.835,6.045], [80.809,6.035], [80.714,5.958], [80.656,5.958], [80.591,5.924], [80.54,5.944], [80.465,5.941], [80.444,5.971], [80.412,5.96], [80.379,5.965], [80.358,5.978], [80.341,5.971], [80.327,5.985], [80.248,6.009], [80.211,6.034], [80.204,6.026], [80.094,6.136], [80.032,6.266], [80.012,6.361], [79.994,6.394], [79.978,6.458], [79.964,6.472], [79.978,6.479], [79.97,6.535], [79.863,6.807], [79.848,6.89], [79.848,6.962], [79.862,7.012], [79.814,7.181], [79.82,7.206], [79.848,7.123], [79.862,7.137], [79.862,7.167], [79.854,7.192], [79.826,7.21], [79.834,7.233], [79.779,7.603], [79.793,7.603], [79.802,7.659], [79.789,7.688], [79.76,7.868], [79.697,8.098], [79.697,8.13], [79.704,8.123], [79.707,8.142], [79.697,8.219], [79.729,8.25], [79.779,8.35], [79.744,8.281], [79.757,8.264], [79.779,8.26], [79.759,8.213], [79.746,8.206], [79.744,8.164], [79.728,8.111], [79.73,8.081], [79.752,8.055], [79.732,8.013], [79.764,7.99], [79.81,7.987], [79.834,8.003], [79.793,8.073], [79.827,8.137], [79.813,8.171], [79.807,8.24], [79.827,8.278], [79.848,8.438], [79.866,8.461], [79.874,8.538], [79.917,8.576], [79.942,8.642], [79.951,8.74], [79.926,8.802], [79.93,8.884], [79.916,8.939], [79.947,8.949], [80.048,9.026], [80.106,9.193], [80.118,9.3], [80.066,9.333], [80.053,9.392], [80.087,9.415], [80.141,9.43], [80.163,9.459], [80.197,9.473], [80.161,9.521], [80.068,9.578], [80.053,9.596], [80.085,9.595], [80.176,9.549], [80.233,9.534], [80.272,9.504], [80.278,9.493], [80.266,9.487], [80.272,9.473], [80.266,9.467], [80.287,9.453], [80.427,9.5], [80.615,9.446], [80.547,9.492], [80.471,9.528], [80.444,9.528], [80.429,9.518], [80.416,9.527], [80.369,9.53], [80.298,9.591], [80.186,9.649], [80.174,9.649], [80.176,9.624], [80.2,9.599], [80.186,9.584], [80.115,9.636], [80.094,9.645], [80.136,9.604], [80.087,9.618], [80.001,9.675], [79.959,9.693], [79.958,9.686], [79.91,9.768], [79.971,9.815], [79.988,9.823], [80.122,9.819], [80.13,9.777], [80.171,9.767], [80.18,9.756], [80.211,9.761], [80.222,9.754], [80.339,9.629], [80.444,9.576], [80.32,9.673], [80.248,9.755], [80.139,9.799], [80.137,9.814], [80.158,9.823], [80.247,9.826], [80.261,9.816], [80.279,9.77], [80.41,9.617], [80.468,9.581], [80.744,9.36], [80.799,9.295], [80.787,9.289], [80.762,9.308], [80.737,9.309], [80.794,9.251], [80.814,9.239], [80.804,9.272], [80.824,9.27], [80.882,9.145], [80.84,9.156], [80.834,9.142], [80.873,9.126], [80.875,9.104], [80.896,9.117], [80.916,9.062], [80.935,9.033], [80.958,9.018], [80.944,9.001], [80.931,9.021], [80.875,9.041], [80.923,8.992], [80.923,8.973], [80.935,8.965], [80.915,8.944], [80.931,8.932], [80.948,8.933], [80.979,8.98], [81.047,8.882], [81.093,8.852], [81.114,8.8], [81.15,8.777], [81.177,8.699], [81.182,8.712], [81.198,8.706], [81.195,8.696], [81.232,8.651], [81.232,8.633], [81.204,8.61], [81.225,8.617], [81.25,8.546], [81.225,8.542], [81.23,8.558], [81.212,8.573], [81.204,8.555], [81.218,8.523], [81.211,8.507], [81.159,8.535], [81.142,8.528], [81.129,8.51], [81.134,8.501], [81.17,8.521], [81.213,8.472], [81.281,8.467], [81.297,8.477], [81.286,8.5], [81.334,8.515], [81.366,8.482], [81.389,8.364], [81.352,8.405], [81.357,8.362], [81.362,8.35], [81.399,8.339], [81.448,8.128], [81.43,8.103], [81.404,8.192], [81.396,8.192], [81.396,8.137], [81.436,8.093], [81.452,8.095], [81.464,8.116], [81.481,8.065], [81.52,7.999], [81.534,8.014], [81.54,7.986], [81.561,7.999], [81.561,7.958], [81.595,7.917], [81.582,7.911], [81.576,7.889], [81.588,7.855], [81.608,7.832], [81.636,7.836], [81.654,7.798], [81.711,7.731], [81.713,7.712], [81.691,7.705], [81.643,7.754], [81.627,7.76], [81.603,7.748], [81.595,7.726], [81.619,7.719], [81.636,7.733], [81.659,7.723], [81.664,7.705], [81.712,7.651], [81.705,7.639], [81.712,7.623], [81.714,7.644], [81.749,7.623], [81.773,7.568], [81.766,7.541], [81.773,7.524], [81.758,7.505], [81.78,7.472], [81.771,7.457], [81.793,7.466], [81.801,7.41], [81.808,7.41], [81.819,7.473], [81.826,7.474], [81.856,7.412], [81.881,7.327], [81.876,7.092]],
        [[81.739,7.66], [81.725,7.699], [81.725,7.74], [81.806,7.599], [81.823,7.511], [81.823,7.491], [81.808,7.479], [81.787,7.497], [81.787,7.593], [81.739,7.66]],
        [[79.714,9.076], [79.698,9.09], [79.714,9.104], [79.759,9.104], [79.842,9.077], [79.917,9.021], [79.854,9.041], [79.91,8.973], [79.886,8.976], [79.8,9.049], [79.714,9.076]],
        [[79.731,9.5], [79.715,9.485], [79.666,9.491], [79.656,9.504], [79.659,9.555], [79.676,9.535], [79.719,9.524], [79.731,9.5]],
        [[79.896,9.617], [79.865,9.652], [79.841,9.699], [79.861,9.756], [79.882,9.761], [79.894,9.757], [79.895,9.688], [79.903,9.679], [79.926,9.679], [79.934,9.655], [79.978,9.624], [79.958,9.617], [79.927,9.629], [79.896,9.617]],
    ];

    /** Towns a tour stops at, [lat, lon]. The key is the town as it is usually written; ALIASES map other spellings and places onto them. */
    private const TOWNS = [
        'Colombo' => [6.9271, 79.8612], 'Katunayake' => [7.1808, 79.8841], 'Negombo' => [7.2083, 79.8358], 'Seeduwa' => [7.1333, 79.8833],
        'Waikkal' => [7.2833, 79.8500], 'Marawila' => [7.4167, 79.8333], 'Chilaw' => [7.5758, 79.7953], 'Kalpitiya' => [8.2297, 79.7675],
        'Puttalam' => [8.0362, 79.8283], 'Wilpattu' => [8.3000, 80.0200], 'Anuradhapura' => [8.3114, 80.4037], 'Mihintale' => [8.3503, 80.5111],
        'Mannar' => [8.9810, 79.9044], 'Jaffna' => [9.6615, 80.0255], 'Point Pedro' => [9.8167, 80.2333], 'Mullaitivu' => [9.2667, 80.8167],
        'Trincomalee' => [8.5874, 81.2152], 'Nilaveli' => [8.7000, 81.1900], 'Kuchchaveli' => [8.8167, 81.1000], 'Passikudah' => [7.9200, 81.5600],
        'Kalkudah' => [7.9167, 81.5667], 'Batticaloa' => [7.7102, 81.6924], 'Kalmunai' => [7.4167, 81.8167], 'Ampara' => [7.2917, 81.6724],
        'Arugam Bay' => [6.8400, 81.8360], 'Pottuvil' => [6.8750, 81.8280], 'Kumana' => [6.5500, 81.7500], 'Monaragala' => [6.8728, 81.3507],
        'Gal Oya' => [7.2000, 81.4300], 'Mahiyanganaya' => [7.3269, 80.9937], 'Dambana' => [7.4000, 81.0500], 'Wasgamuwa' => [7.7167, 80.9333],
        'Sigiriya' => [7.9570, 80.7603], 'Dambulla' => [7.8742, 80.6511], 'Kandalama' => [7.8650, 80.7160], 'Habarana' => [8.0383, 80.7533],
        'Polonnaruwa' => [7.9403, 81.0188], 'Minneriya' => [8.0333, 80.8833], 'Giritale' => [7.9858, 80.9078], 'Kaudulla' => [8.1500, 80.9000],
        'Ritigala' => [8.1167, 80.6500], 'Aukana' => [8.0083, 80.5083], 'Kekirawa' => [8.0400, 80.5900], 'Yapahuwa' => [7.8167, 80.3167],
        'Kurunegala' => [7.4863, 80.3647], 'Pinnawala' => [7.3007, 80.3884], 'Rambukkana' => [7.3243, 80.3934], 'Kegalle' => [7.2513, 80.3464],
        'Veyangoda' => [7.1548, 80.0583], 'Matale' => [7.4675, 80.6234], 'Nalanda' => [7.6667, 80.6333], 'Riverston' => [7.5167, 80.7500],
        'Knuckles' => [7.4500, 80.7833], 'Hunnasgiriya' => [7.4036, 80.7936], 'Elkaduwa' => [7.4100, 80.6900], 'Wattegama' => [7.3500, 80.6833],
        'Kandy' => [7.2906, 80.6337], 'Peradeniya' => [7.2597, 80.5979], 'Gampola' => [7.1644, 80.5696], 'Hatton' => [6.8917, 80.5958],
        'Kitulgala' => [6.9897, 80.4183], 'Avissawella' => [6.9519, 80.2103], 'Ratnapura' => [6.6828, 80.3992], 'Balangoda' => [6.6500, 80.7000],
        'Belihuloya' => [6.7167, 80.7833], 'Nuwara Eliya' => [6.9497, 80.7891], 'Ambewela' => [6.8700, 80.8000], 'Horton Plains' => [6.8000, 80.8000],
        'Mandaramnuwara' => [7.0800, 80.7300], 'Welimada' => [6.9064, 80.9128], 'Bandarawela' => [6.8333, 80.9833], 'Haputale' => [6.7667, 80.9583],
        'Diyatalawa' => [6.8167, 80.9667], 'Ella' => [6.8667, 81.0466], 'Badulla' => [6.9934, 81.0550], 'Wellawaya' => [6.7367, 81.1028],
        'Udawalawe' => [6.4333, 80.8833], 'Embilipitiya' => [6.3436, 80.8500], 'Deniyaya' => [6.3417, 80.5583], 'Sinharaja' => [6.4000, 80.5000],
        'Tissamaharama' => [6.2808, 81.2857], 'Kataragama' => [6.4135, 81.3328], 'Yala' => [6.3000, 81.4200], 'Kirinda' => [6.2167, 81.3333],
        'Weerawila' => [6.2667, 81.2333], 'Hambantota' => [6.1241, 81.1185], 'Mattala' => [6.2847, 81.1239], 'Tangalle' => [6.0240, 80.7940],
        'Rekawa' => [6.0500, 80.8500], 'Dickwella' => [5.9667, 80.7000], 'Hiriketiya' => [5.9667, 80.7167], 'Matara' => [5.9549, 80.5550],
        'Mirissa' => [5.9483, 80.4716], 'Weligama' => [5.9735, 80.4297], 'Ahangama' => [5.9733, 80.3617], 'Koggala' => [5.9900, 80.3300],
        'Talpe' => [5.9900, 80.2900], 'Unawatuna' => [6.0100, 80.2500], 'Galle' => [6.0535, 80.2210], 'Hikkaduwa' => [6.1395, 80.1063],
        'Ambalangoda' => [6.2355, 80.0538], 'Kosgoda' => [6.3333, 80.0333], 'Ahungalla' => [6.3160, 80.0400], 'Induruwa' => [6.3700, 80.0100],
        'Bentota' => [6.4219, 80.0011], 'Aluthgama' => [6.4300, 80.0000], 'Beruwala' => [6.4788, 79.9828], 'Kalutara' => [6.5854, 79.9607],
        'Wadduwa' => [6.6397, 79.9269], 'Panadura' => [6.7133, 79.9026], 'Mount Lavinia' => [6.8384, 79.8637], 'Ratmalana' => [6.8217, 79.8864],
        'Kotte' => [6.9000, 79.9100], 'Kelaniya' => [6.9553, 79.9220], 'Wattala' => [6.9897, 79.8914], 'Horana' => [6.7150, 80.0628],
    ];

    /** Other ways a place is written, and places inside a town's area: a word or phrase (lower case) → the town above. */
    private const ALIASES = [
        'airport' => 'Katunayake', 'bandaranaike' => 'Katunayake', 'bia' => 'Katunayake', 'cmb' => 'Katunayake', 'katunayaka' => 'Katunayake',
        'colombo airport' => 'Katunayake', 'negambo' => 'Negombo', 'sri jayawardenepura' => 'Kotte', 'sri jayawardenepura kotte' => 'Kotte',
        'nuwaraeliya' => 'Nuwara Eliya', 'nuwara-eliya' => 'Nuwara Eliya', 'n eliya' => 'Nuwara Eliya', 'nuwara eliya town' => 'Nuwara Eliya',
        'sigiriya rock' => 'Sigiriya', 'lion rock' => 'Sigiriya', 'pidurangala' => 'Sigiriya', 'heritance kandalama' => 'Kandalama',
        'dambulla cave temple' => 'Dambulla', 'anuradapura' => 'Anuradhapura', 'polonnaruva' => 'Polonnaruwa', 'pollonnaruwa' => 'Polonnaruwa',
        'trinco' => 'Trincomalee', 'pasikuda' => 'Passikudah', 'passikuda' => 'Passikudah', 'pasikudah' => 'Passikudah',
        'arugambay' => 'Arugam Bay', 'arugam' => 'Arugam Bay', 'galoya' => 'Gal Oya', 'gal-oya' => 'Gal Oya',
        'tissa' => 'Tissamaharama', 'thissamaharama' => 'Tissamaharama', 'yala national park' => 'Yala', 'palatupana' => 'Yala',
        'udawalawa' => 'Udawalawe', 'uda walawe' => 'Udawalawe', 'ambilipitiya' => 'Embilipitiya', 'mattala airport' => 'Mattala',
        'wilpattu national park' => 'Wilpattu', 'minneriya national park' => 'Minneriya', 'kaudulla national park' => 'Kaudulla',
        'horton plains national park' => 'Horton Plains', 'sinharaja forest' => 'Sinharaja', 'kitulgala rafting' => 'Kitulgala',
        'pinnawela' => 'Pinnawala', 'pinnawala elephant orphanage' => 'Pinnawala', 'peradeniya gardens' => 'Peradeniya',
        'hikkaduwa beach' => 'Hikkaduwa', 'unawatuna beach' => 'Unawatuna', 'galle fort' => 'Galle', 'mirissa beach' => 'Mirissa',
        'bentota beach' => 'Bentota', 'aluthgama' => 'Bentota', 'mt lavinia' => 'Mount Lavinia', 'mount lavinia' => 'Mount Lavinia',
        'heis' => 'Deniyaya', 'ella rock' => 'Ella', 'nine arch' => 'Ella', 'haputhale' => 'Haputale', 'hatton town' => 'Hatton',
        'nilaweli' => 'Nilaveli', 'kalpitiya peninsula' => 'Kalpitiya', 'pottuvil town' => 'Pottuvil',
        // airport hotels, as the desks write them
        'katunayeka' => 'Katunayake', 'katunayke' => 'Katunayake', 'good wood' => 'Katunayake', 'goodwood' => 'Katunayake', 'good wood plaza' => 'Katunayake',
        'kynrock' => 'Katunayake', 'canada friendship road' => 'Katunayake', 'seeduwa' => 'Seeduwa',
    ];

    private $db;
    private string $publicRoot;

    public function __construct($db, ?string $publicRoot = null)
    {
        $this->db = $db;
        $this->publicRoot = rtrim($publicRoot ?? (defined('FCPATH') ? FCPATH : ''), '/\\') . '/';
    }

    // =====================================================================
    //  Places
    // =====================================================================

    private static function words(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(['&', '’', "'"], [' and ', '', ''], $s);
        $s = preg_replace('/[^a-z0-9 ]+/u', ' ', $s) ?? '';
        return trim(preg_replace('/\s+/', ' ', $s) ?? '');
    }

    /**
     * Where a place is: the town it is in and the town's position, or null when the gazetteer does not know it.
     * The town is the last part of "Hotel, Road, Town"; failing that, a known alias or town named anywhere in the text.
     *
     * @return array{town:string, lat:float, lon:float}|null
     */
    public static function locate(?string $place): ?array
    {
        $p = trim((string) $place);
        if ($p === '' || preg_match('/\bgarage\b/i', $p)) {
            return null;
        }
        $byWords = [];
        foreach (self::TOWNS as $name => $ll) {
            $byWords[self::words($name)] = $name;
        }
        $parts = array_values(array_filter(array_map('trim', explode(',', $p)), static fn ($x) => $x !== ''));
        $tries = [];
        if ($parts) {
            $tries[] = $parts[count($parts) - 1];
        }
        $tries[] = $p;
        foreach ($tries as $t) {
            $w = self::words($t);
            if ($w === '') {
                continue;
            }
            $w = preg_replace('/\b(sri lanka|hotel|resort|town|city)\b/', ' ', $w);
            $w = trim(preg_replace('/\s+/', ' ', $w ?? '') ?? '');
            if (isset($byWords[$w])) {
                return self::town($byWords[$w]);
            }
            if (isset(self::ALIASES[$w])) {
                return self::town(self::ALIASES[$w]);
            }
        }
        // a known alias or town named anywhere in the text — the longest match wins ("nuwara eliya" over "ella")
        $all = ' ' . self::words($p) . ' ';
        $best = null;
        foreach (array_merge(array_keys(self::ALIASES), array_keys($byWords)) as $key) {
            if (str_contains($all, ' ' . $key . ' ') && ($best === null || strlen($key) > strlen($best))) {
                $best = $key;
            }
        }
        if ($best !== null) {
            return self::town(self::ALIASES[$best] ?? $byWords[$best]);
        }
        return null;
    }

    private static function town(string $name): array
    {
        [$lat, $lon] = self::TOWNS[$name];
        return ['town' => $name, 'lat' => $lat, 'lon' => $lon];
    }

    // =====================================================================
    //  The sheet's data — from the card's saved legs only
    // =====================================================================

    /**
     * @param array<string, mixed> $job the transport card (TourService::transportJob) with its legs
     * @param array<string, mixed> $details the tour as the desks read it
     * @param array<string, mixed> $company name / address / phone / email
     * @param array<string, mixed>|null $driver the driver on the card now
     * @return array<string, mixed>
     */
    public function build(array $job, array $details, array $company, ?array $driver): array
    {
        $legsIn = array_values(array_filter($job['legs'] ?? [], static fn ($l) => empty($l['removed_at'])));
        usort($legsIn, static fn ($a, $b) => [(int) $a['day_no'], (int) $a['seq'], (int) $a['id']] <=> [(int) $b['day_no'], (int) $b['seq'], (int) $b['id']]);
        $isAirport = static fn ($p) => (bool) preg_match('/\b(airport|katunayake|bandaranaike|mattala)\b/i', (string) $p);
        $isGarage  = static fn ($p) => (bool) preg_match('/\bgarage\b/i', (string) $p);
        $arrival   = (string) ($job['arrival_date'] ?? ($details['arrival_date'] ?? ''));
        $dateOf    = static function (array $l) use ($arrival): ?string {
            if (!empty($l['day_date'])) {
                return (string) $l['day_date'];
            }
            if ($arrival !== '' && (int) $l['day_no'] >= 1) {
                $t = strtotime($arrival . ' +' . ((int) $l['day_no'] - 1) . ' day');
                return $t ? date('Y-m-d', $t) : null;
            }
            return null;
        };
        $legs = [];
        foreach ($legsIn as $l) {
            $same = self::words((string) $l['from_place']) === self::words((string) $l['to_place']);
            $kind = (string) $l['kind'];
            $tags = [];
            if ($kind === 'garage' || $isGarage($l['from_place']) || $isGarage($l['to_place'])) {
                $tags[] = 'garage run';
            } elseif ($kind === 'arrival' || ($kind === 'positioning' && $isAirport($l['to_place']))) {
                $tags[] = $kind === 'arrival' && $same ? 'airport pickup (airport hotel)' : 'airport pickup';
            } elseif ($kind === 'departure') {
                $tags[] = 'airport drop-off';
            } elseif ($kind === 'idle') {
                $tags[] = 'stay day';
            } elseif ($kind === 'local') {
                $tags[] = $same ? 'city tour' : 'day out';
            }
            $legs[] = [
                'id' => (int) $l['id'], 'day' => (int) $l['day_no'], 'date' => $dateOf($l), 'seq' => (int) $l['seq'],
                'from' => (string) $l['from_place'], 'to' => (string) $l['to_place'], 'same' => $same,
                'km' => $l['km'] !== null ? (float) $l['km'] : null, 'extra' => $l['extra_km'] !== null ? (float) $l['extra_km'] : null,
                'kind' => $kind, 'tags' => $tags, 'note' => (string) ($l['note'] ?? ''),
                'night_hotel' => trim((string) ($l['night_hotel'] ?? '')), 'night_area' => trim((string) ($l['night_area'] ?? '')),
                'garage' => $kind === 'garage' || $isGarage($l['from_place']) || $isGarage($l['to_place']),
                'from_loc' => self::locate((string) $l['from_place']), 'to_loc' => self::locate((string) $l['to_place']),
            ];
        }
        // the days: the route, the planned kilometres, the extra allowance, the night
        $days = [];
        foreach ($legs as $i => $l) {
            $d = $l['day'];
            if (!isset($days[$d])) {
                $days[$d] = ['day' => $d, 'date' => $l['date'], 'legs' => [], 'km' => 0.0, 'extra' => 0.0, 'route' => [], 'night_hotel' => '', 'night_area' => '', 'ends_at' => '', 'unpriced' => 0];
            }
            $days[$d]['legs'][] = $i;
            if ($l['km'] !== null) {
                $days[$d]['km'] += $l['km'];
            } else {
                $days[$d]['unpriced']++;
            }
            $days[$d]['extra'] += (float) ($l['extra'] ?? 0);
            foreach ([[$l['from'], $l['from_loc']], [$l['to'], $l['to_loc']]] as [$p, $loc]) {
                $r = $days[$d]['route'];
                $k = $loc ? 'town:' . self::words($loc['town']) : self::words($p);
                $last = $r ? end($r) : null;
                $lk = $last === null ? null : (($ll = self::locate($last)) ? 'town:' . self::words($ll['town']) : self::words($last));
                if ($lk !== $k) {
                    $days[$d]['route'][] = $p;
                }
            }
            if ($l['night_hotel'] !== '' || $l['night_area'] !== '') {
                $days[$d]['night_hotel'] = $l['night_hotel'];
                // the area as the town where the gazetteer knows it: "Kynrock Airport Hotel, Canada Friendship Road, Katunayake" → Katunayake
                $days[$d]['night_area']  = self::locate($l['night_area'])['town'] ?? $l['night_area'];
            }
            if (!$l['garage']) {
                $days[$d]['ends_at'] = $l['to'];
            }
        }
        ksort($days);
        $days = array_values($days);
        // the stops along the route, in order, the garage left out: each with its visits and its nights
        $stops = [];
        $index = [];
        $nightsBy = [];
        foreach ($days as $dd) {
            if ($dd['night_hotel'] !== '' && $dd['ends_at'] !== '') {
                $nightsBy[self::words($dd['ends_at'])][] = ['day' => $dd['day'], 'date' => $dd['date'], 'hotel' => $dd['night_hotel'], 'area' => $dd['night_area']];
            }
        }
        // a stop is a town: two hotels in Katunayake are one pin, named for the town, with both names kept
        $keyOf = static fn (string $p, ?array $loc): string => $loc ? 'town:' . self::words($loc['town']) : self::words($p);
        foreach ($legs as $l) {
            foreach ([[$l['from'], $l['from_loc']], [$l['to'], $l['to_loc']]] as [$p, $loc]) {
                if ($isGarage($p)) {
                    continue;
                }
                $k = $keyOf($p, $loc);
                if (!isset($index[$k])) {
                    $index[$k] = count($stops);
                    $stops[] = ['key' => $k, 'name' => $loc['town'] ?? $p, 'names' => [], 'short' => $loc['town'] ?? self::shortName($p), 'loc' => $loc, 'days' => [], 'dates' => [], 'airport' => false, 'nights' => [], 'order' => count($stops) + 1];
                }
                $st = &$stops[$index[$k]];
                if (!in_array($p, $st['names'], true)) {
                    $st['names'][] = $p;
                }
                $st['airport'] = $st['airport'] || $isAirport($p);
                if (!in_array($l['day'], $st['days'], true)) {
                    $st['days'][] = $l['day'];
                    $st['dates'][] = $l['date'];
                }
                foreach ($nightsBy[self::words($p)] ?? [] as $n) {
                    if (!in_array($n, $st['nights'], true)) {
                        $st['nights'][] = $n;
                    }
                }
                unset($st);
            }
        }
        // the route as drawn: one segment for every driven leg between two known places, with its day and km
        $segments = [];
        foreach ($legs as $i => $l) {
            if ($l['garage'] || $l['same'] || !$l['from_loc'] || !$l['to_loc']) {
                continue;
            }
            $fk = $keyOf($l['from'], $l['from_loc']); $tk = $keyOf($l['to'], $l['to_loc']);
            if ($fk === $tk) {
                continue;   // two places in one town: no line to draw
            }
            $segments[] = ['leg' => $i, 'from' => $index[$fk], 'to' => $index[$tk], 'day' => $l['day'], 'km' => $l['km']];
        }
        $unknown = array_values(array_filter($stops, static fn ($s) => $s['loc'] === null));
        $totalKm = 0.0; $totalExtra = 0.0; $unpriced = 0; $garageKm = 0.0;
        foreach ($legs as $l) {
            if ($l['km'] !== null) {
                $totalKm += $l['km'];
                if ($l['garage']) {
                    $garageKm += $l['km'];
                }
            } else {
                $unpriced++;
            }
            $totalExtra += (float) ($l['extra'] ?? 0);
        }
        $rev = max(array_filter([(string) ($job['updated_at'] ?? ''), (string) ($job['scheduled_at'] ?? ''), (string) ($job['allocated_at'] ?? '')]) ?: ['']);
        $vehicle = trim(($job['vehicle_type'] ?? '') . ((int) ($job['qty'] ?? 1) > 1 ? ' × ' . (int) $job['qty'] : '') . (!empty($job['vehicle_reg']) && strtoupper((string) $job['vehicle_reg']) !== 'NA' ? ' · ' . $job['vehicle_reg'] : ''));
        return [
            'tour_no' => (string) ($job['tour_no'] ?? ''), 'tour_name' => (string) ($job['tour_name'] ?? ''), 'agent' => (string) ($job['agent_name'] ?? ''),
            'arrival_date' => $arrival ?: null, 'departure_date' => (string) ($job['departure_date'] ?? ($details['departure_date'] ?? '')) ?: null,
            'pax' => (int) ($details['pax_total'] ?? $job['tour_pax'] ?? 0), 'vehicle' => $vehicle,
            'driver' => $driver ? trim((string) $driver['person_name'] . (!empty($driver['person_phone']) ? ' · ' . $driver['person_phone'] : '')) : '',
            'arrival_flight' => (string) ($details['arrival_flight'] ?? ''), 'departure_flight' => (string) ($details['departure_flight'] ?? ''),
            'company' => $company, 'revision' => $rev ?: null, 'generated' => date('Y-m-d H:i'),
            'legs' => $legs, 'days' => $days, 'stops' => $stops, 'segments' => $segments, 'unknown' => $unknown,
            'total_km' => $totalKm, 'total_extra' => $totalExtra, 'garage_km' => $garageKm, 'unpriced' => $unpriced,
            'logo' => is_file($this->publicRoot . 'assets/images/email/logo.png') ? $this->publicRoot . 'assets/images/email/logo.png' : null,
        ];
    }

    private static function shortName(string $p): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $p)), static fn ($x) => $x !== ''));
        return $parts ? $parts[count($parts) - 1] : $p;
    }

    public static function fmtKm($v): string
    {
        return $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1, '.', ','), '0'), '.') . ' km';
    }

    public static function fmtDate(?string $ymd, string $f = 'D j M'): string
    {
        $t = $ymd ? strtotime($ymd) : false;
        return $t ? date($f, $t) : '';
    }

    // =====================================================================
    //  The picture — GD: the map alone (for the sheet and the PDF) or the whole card (for WhatsApp)
    // =====================================================================

    private array $fonts = [];
    private $im;
    private array $col = [];

    private function font(bool $bold): string
    {
        if (!$this->fonts) {
            foreach (['r' => 'DejaVuSans.ttf', 'b' => 'DejaVuSans-Bold.ttf'] as $k => $f) {
                $p = $this->publicRoot . 'assets/fonts/dejavu/' . $f;
                $this->fonts[$k] = is_file($p) ? $p : null;
            }
        }
        $f = $this->fonts[$bold ? 'b' : 'r'] ?? null;
        if ($f === null) {
            throw new \RuntimeException('The map needs the DejaVu font files under public/assets/fonts/dejavu/ (DejaVuSans.ttf, DejaVuSans-Bold.ttf).');
        }
        return $f;
    }

    /** @return array{0:int,1:int} width and height of a text */
    private function measure(float $size, string $text, bool $bold = false): array
    {
        $b = imagettfbbox($size, 0, $this->font($bold), $text);
        return [(int) abs($b[4] - $b[0]), (int) abs($b[5] - $b[1])];
    }

    /** Text with its top-left at (x, y); returns the width drawn. */
    private function text(float $x, float $y, float $size, string $color, string $text, bool $bold = false, string $align = 'left'): int
    {
        [$w] = $this->measure($size, $text, $bold);
        if ($align === 'right') {
            $x -= $w;
        } elseif ($align === 'center') {
            $x -= $w / 2;
        }
        imagettftext($this->im, $size, 0, (int) round($x), (int) round($y + $size), $this->col[$color], $this->font($bold), $text);
        return $w;
    }

    /** A text cut to a width, with an ellipsis. */
    private function fit(float $size, string $text, int $maxW, bool $bold = false): string
    {
        if ($this->measure($size, $text, $bold)[0] <= $maxW) {
            return $text;
        }
        $t = $text;
        while (mb_strlen($t) > 1 && $this->measure($size, $t . '…', $bold)[0] > $maxW) {
            $t = mb_substr($t, 0, -1);
        }
        return rtrim($t) . '…';
    }

    private function rect(int $x1, int $y1, int $x2, int $y2, string $fill, ?string $stroke = null): void
    {
        imagefilledrectangle($this->im, $x1, $y1, $x2, $y2, $this->col[$fill]);
        if ($stroke) {
            imagerectangle($this->im, $x1, $y1, $x2, $y2, $this->col[$stroke]);
        }
    }

    private function roundRect(int $x1, int $y1, int $x2, int $y2, int $r, string $fill, ?string $stroke = null): void
    {
        $im = $this->im; $c = $this->col[$fill];
        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $c);
        imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $c);
        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($im, $cx, $cy, $r * 2, $r * 2, $c);
        }
        if ($stroke) {
            $s = $this->col[$stroke];
            imageline($im, $x1 + $r, $y1, $x2 - $r, $y1, $s); imageline($im, $x1 + $r, $y2, $x2 - $r, $y2, $s);
            imageline($im, $x1, $y1 + $r, $x1, $y2 - $r, $s); imageline($im, $x2, $y1 + $r, $x2, $y2 - $r, $s);
            imagearc($im, $x1 + $r, $y1 + $r, $r * 2, $r * 2, 180, 270, $s); imagearc($im, $x2 - $r, $y1 + $r, $r * 2, $r * 2, 270, 360, $s);
            imagearc($im, $x1 + $r, $y2 - $r, $r * 2, $r * 2, 90, 180, $s); imagearc($im, $x2 - $r, $y2 - $r, $r * 2, $r * 2, 0, 90, $s);
        }
    }

    private function thickLine(float $x1, float $y1, float $x2, float $y2, float $w, string $color): void
    {
        $dx = $x2 - $x1; $dy = $y2 - $y1; $len = hypot($dx, $dy);
        if ($len < 0.5) {
            return;
        }
        $nx = -$dy / $len * $w / 2; $ny = $dx / $len * $w / 2;
        imagefilledpolygon($this->im, [(int) round($x1 + $nx), (int) round($y1 + $ny), (int) round($x2 + $nx), (int) round($y2 + $ny), (int) round($x2 - $nx), (int) round($y2 - $ny), (int) round($x1 - $nx), (int) round($y1 - $ny)], $this->col[$color]);
        imagefilledellipse($this->im, (int) round($x1), (int) round($y1), (int) $w, (int) $w, $this->col[$color]);
        imagefilledellipse($this->im, (int) round($x2), (int) round($y2), (int) $w, (int) $w, $this->col[$color]);
    }

    private function dashedLine(float $x1, float $y1, float $x2, float $y2, float $w, string $color, int $dash = 10, int $gap = 8): void
    {
        $len = hypot($x2 - $x1, $y2 - $y1);
        if ($len < 1) {
            return;
        }
        $ux = ($x2 - $x1) / $len; $uy = ($y2 - $y1) / $len;
        for ($t = 0; $t < $len; $t += $dash + $gap) {
            $e = min($len, $t + $dash);
            $this->thickLine($x1 + $ux * $t, $y1 + $uy * $t, $x1 + $ux * $e, $y1 + $uy * $e, $w, $color);
        }
    }

    private function arrow(float $x, float $y, float $ux, float $uy, float $size, string $color): void
    {
        $px = -$uy; $py = $ux;
        $pts = [
            (int) round($x + $ux * $size), (int) round($y + $uy * $size),
            (int) round($x - $ux * $size * 0.6 + $px * $size * 0.7), (int) round($y - $uy * $size * 0.6 + $py * $size * 0.7),
            (int) round($x - $ux * $size * 0.2), (int) round($y - $uy * $size * 0.2),
            (int) round($x - $ux * $size * 0.6 - $px * $size * 0.7), (int) round($y - $uy * $size * 0.6 - $py * $size * 0.7),
        ];
        imagefilledpolygon($this->im, $pts, $this->col[$color]);
    }

    private function colours(): void
    {
        $im = $this->im;
        $this->col = [];
        // the system's own colours (the Tour Desk theme: brand purple #842484, its soft tint #f3e7f3, ink #2c3e50, muted #6c757d);
        // the route stays blue and the stops red, as a map is read — Shantha's brief
        foreach (['white' => [255, 255, 255], 'ink' => [44, 62, 80], 'muted' => [108, 117, 125], 'line' => [228, 232, 238], 'soft' => [246, 247, 250],
                  'navy' => [132, 36, 132], 'blue' => [37, 99, 235], 'blue_soft' => [243, 231, 243], 'red' => [214, 40, 40], 'red_dark' => [160, 24, 24],
                  'green' => [31, 138, 76], 'amber' => [183, 121, 31], 'amber_soft' => [253, 243, 225], 'sea' => [226, 233, 243], 'land' => [245, 240, 229],
                  'coast' => [164, 150, 122], 'grey' => [138, 150, 163], 'day_band' => [243, 231, 243], 'night_soft' => [228, 244, 234]] as $k => [$r, $g, $b]) {
            $this->col[$k] = imagecolorallocate($im, $r, $g, $b);
        }
        $this->col['label_bg'] = imagecolorallocatealpha($im, 255, 255, 255, 28);
    }

    /**
     * The picture as PNG bytes, 1080 px wide. $part: 'card' — the whole thing (header, map, the day-by-day table, totals,
     * footer) for WhatsApp; 'sheet' — header, map and footer (the preview page shows it above its own table);
     * 'map' — the map panel alone (the PDF puts its own header and table round it).
     */
    public function png(array $d, $part = 'card'): string
    {
        $part = $part === true ? 'card' : ($part === false ? 'sheet' : (string) $part);
        $full = $part === 'card';
        $W = 1080;
        $headerH = $part === 'map' ? 0 : 190;
        $mapH = 1040;
        $tableH = 0;
        if ($full) {
            $tableH = 110;   // the caption and the heading
            foreach ($d['days'] as $day) {
                $tableH += 62 + 46 * count($day['legs']);
            }
            $tableH += 150;  // totals
            if ($d['unknown']) {
                $tableH += 40;
            }
        }
        $footH = $part === 'map' ? 0 : 90;
        $H = $headerH + $mapH + $tableH + $footH;
        $this->im = imagecreatetruecolor($W, $H);
        imagealphablending($this->im, true);
        $this->colours();
        $this->rect(0, 0, $W, $H, 'white');
        if ($headerH) {
            $this->header($d, $W, $headerH);
        }
        $this->map($d, 0, $headerH, $W, $mapH);
        $y = $headerH + $mapH;
        if ($full) {
            $y = $this->table($d, $y, $W);
        }
        if ($footH) {
            $this->footer($d, $y, $W, $H);
        }
        ob_start();
        imagepng($this->im, null, 6);
        $out = (string) ob_get_clean();
        imagedestroy($this->im);
        return $out;
    }

    private function header(array $d, int $W, int $H): void
    {
        $this->rect(0, 0, $W, $H, 'navy');
        $x = 36;
        if ($d['logo'] && ($logo = @imagecreatefrompng($d['logo']))) {
            $lw = imagesx($logo); $lh = imagesy($logo);
            $th = 56; $tw = (int) round($lw * $th / max(1, $lh));
            // the logo on a white tile so a dark-text logo stays readable
            $this->roundRect($x, 26, $x + $tw + 24, 26 + $th + 20, 10, 'white');
            imagecopyresampled($this->im, $logo, $x + 12, 36, 0, 0, $tw, $th, $lw, $lh);
            imagedestroy($logo);
            $x += $tw + 48;
        }
        $this->text($x, 20, 28, 'white', "Driver's route map", true);
        $this->text($x, 60, 21, 'white', $this->fit(21, trim('Tour ' . $d['tour_no'] . ($d['tour_name'] !== '' ? ' · ' . $d['tour_name'] : '')), $W - $x - 40, true), true);
        $line = array_filter([
            $d['arrival_date'] ? self::fmtDate($d['arrival_date'], 'j M Y') . ($d['departure_date'] ? ' – ' . self::fmtDate($d['departure_date'], 'j M Y') : '') : null,
            $d['pax'] ? $d['pax'] . ' pax' : null, $d['vehicle'] !== '' ? $d['vehicle'] : null,
        ]);
        $this->text($x, 94, 16, 'blue_soft', $this->fit(16, implode('  ·  ', $line), $W - $x - 40));
        $this->text($x, 120, 16, 'white', $this->fit(16, $d['driver'] !== '' ? 'Driver ' . $d['driver'] : 'Driver not yet engaged', $W - $x - 40));
        $rev = 'Revision ' . ($d['revision'] ? self::fmtDate($d['revision'], 'j M Y H:i') : '—') . '  ·  printed ' . self::fmtDate($d['generated'], 'j M Y H:i');
        $this->text($x, 148, 15, 'blue_soft', $this->fit(15, $rev, $W - $x - 40));
    }

    /** The map: the coastline, the route with its direction, the stops with their labels, the legend and the day list beside it. */
    private function map(array $d, int $X, int $Y, int $W, int $H): void
    {
        $this->rect($X, $Y, $X + $W, $Y + $H, 'sea');
        // the island fits the panel's left part; the route decides the zoom: its box, widened, never beyond the island's
        $panelX = $X + 24; $panelY = $Y + 24; $panelW = 640; $panelH = $H - 48;
        $known = array_values(array_filter($d['stops'], static fn ($s) => $s['loc'] !== null));
        $lonMin = 79.60; $lonMax = 81.95; $latMin = 5.88; $latMax = 9.90;
        if ($known) {
            $lats = array_map(static fn ($s) => $s['loc']['lat'], $known); $lons = array_map(static fn ($s) => $s['loc']['lon'], $known);
            $m = 0.32;
            $bLatMin = max($latMin, min($lats) - $m); $bLatMax = min($latMax, max($lats) + $m);
            $bLonMin = max($lonMin, min($lons) - $m); $bLonMax = min($lonMax, max($lons) + $m);
            // at least a decent window, so a short tour does not blow up to a blank sea
            if ($bLatMax - $bLatMin < 1.6) { $c = ($bLatMax + $bLatMin) / 2; $bLatMin = max($latMin, $c - 0.8); $bLatMax = min($latMax, $c + 0.8); }
            if ($bLonMax - $bLonMin < 1.2) { $c = ($bLonMax + $bLonMin) / 2; $bLonMin = max($lonMin, $c - 0.6); $bLonMax = min($lonMax, $c + 0.6); }
        } else {
            [$bLatMin, $bLatMax, $bLonMin, $bLonMax] = [$latMin, $latMax, $lonMin, $lonMax];
        }
        $k = cos(deg2rad(7.9));
        $scale = min($panelW / (($bLonMax - $bLonMin) * $k), $panelH / ($bLatMax - $bLatMin));
        $cx = ($bLonMin + $bLonMax) / 2; $cy = ($bLatMin + $bLatMax) / 2;
        $ox = $panelX + $panelW / 2; $oy = $panelY + $panelH / 2;
        $proj = static fn (float $lon, float $lat): array => [$ox + ($lon - $cx) * $k * $scale, $oy - ($lat - $cy) * $scale];
        // the land, drawn on a panel of its own so the sea stays within the frame
        $land = imagecreatetruecolor($panelW, $panelH);
        $lc = ['sea' => imagecolorallocate($land, 221, 234, 246), 'land' => imagecolorallocate($land, 245, 240, 229), 'coast' => imagecolorallocate($land, 164, 150, 122)];
        imagefilledrectangle($land, 0, 0, $panelW, $panelH, $lc['sea']);
        foreach (self::OUTLINE as $ring) {
            $pts = [];
            foreach ($ring as [$lon, $lat]) {
                [$px, $py] = $proj($lon, $lat);
                $pts[] = (int) round($px - $panelX); $pts[] = (int) round($py - $panelY);
            }
            if (count($pts) >= 6) {
                imagefilledpolygon($land, $pts, $lc['land']);
                imagepolygon($land, $pts, $lc['coast']);
            }
        }
        imagecopy($this->im, $land, $panelX, $panelY, 0, 0, $panelW, $panelH);
        imagedestroy($land);
        imagerectangle($this->im, $panelX, $panelY, $panelX + $panelW, $panelY + $panelH, $this->col['coast']);
        $cap = 'Schematic: straight lines, not the road';
        if ($d['unknown']) {
            $cap = 'Not on the map: ' . implode(', ', array_map(static fn ($s) => $s['name'], $d['unknown'])) . '  ·  ' . $cap;
        }
        $this->text($panelX + 10, $panelY + $panelH - 28, 15, 'muted', $this->fit(15, $cap, $panelW - 290));

        // the stops' pixel positions; stops in the same place are one pin
        $pos = [];
        foreach ($d['stops'] as $i => $s) {
            if ($s['loc']) {
                [$px, $py] = $proj($s['loc']['lon'], $s['loc']['lat']);
                foreach ($pos as [$qx, $qy]) {
                    $dd = hypot($px - $qx, $py - $qy);
                    if ($dd < 34) {
                        // two towns a few kilometres apart: the later pin steps aside, away from the first
                        $ang = $dd < 1 ? -M_PI / 4 : atan2($py - $qy, $px - $qx);
                        $px = $qx + cos($ang) * 36; $py = $qy + sin($ang) * 36;
                    }
                }
                $pos[$i] = [$px, $py];
            }
        }
        // the pins' boxes first, and the legend's: the km labels and the stop labels both keep clear of them
        $legX = $panelX + $panelW - 268; $legY = $panelY + $panelH - 152;
        $boxes = [[$legX, $legY, $panelX + $panelW, $panelY + $panelH]];
        foreach ($pos as $i => [$px, $py]) {
            $boxes[] = [$px - 22, $py - 22, $px + 22, $py + 22];
        }
        $clear = static function (array $box) use (&$boxes): bool {
            foreach ($boxes as $b) {
                if ($box[0] < $b[2] && $box[2] > $b[0] && $box[1] < $b[3] && $box[3] > $b[1]) {
                    return false;
                }
            }
            return true;
        };
        // the route: every driven leg between known stops, the second pass over the same pair set beside the first
        $drawn = [];
        $kmLabels = [];
        foreach ($d['segments'] as $seg) {
            if (!isset($pos[$seg['from']], $pos[$seg['to']])) {
                continue;
            }
            [$x1, $y1] = $pos[$seg['from']]; [$x2, $y2] = $pos[$seg['to']];
            $pair = min($seg['from'], $seg['to']) . '-' . max($seg['from'], $seg['to']);
            $n = $drawn[$pair] = ($drawn[$pair] ?? 0) + 1;
            $len = hypot($x2 - $x1, $y2 - $y1);
            if ($len < 1) {
                continue;
            }
            $ux = ($x2 - $x1) / $len; $uy = ($y2 - $y1) / $len;
            $off = ($n - 1) * 9 * (($n % 2) ? 1 : -1);
            $sx = -$uy * $off; $sy = $ux * $off;
            $this->thickLine($x1 + $sx, $y1 + $sy, $x2 + $sx, $y2 + $sy, 6, 'blue');
            $ax = $x1 + $sx + $ux * $len * 0.58; $ay = $y1 + $sy + $uy * $len * 0.58;
            $this->arrow($ax, $ay, $ux, $uy, 13, 'blue');
            if ($len > 90 && $seg['km'] !== null) {
                $lab = rtrim(rtrim(number_format($seg['km'], 1, '.', ''), '0'), '.') . ' km';
                [$tw] = $this->measure(16, $lab, true);
                foreach ([0.38, 0.5, 0.26, 0.7, 0.62] as $t) {
                    $lx = $x1 + $sx + $ux * $len * $t; $ly = $y1 + $sy + $uy * $len * $t;
                    $box = [$lx - $tw / 2 - 8, $ly - 14, $lx + $tw / 2 + 8, $ly + 14];
                    if ($clear($box)) {
                        $boxes[] = $box;
                        $kmLabels[] = [$box, $lab];
                        break;
                    }
                }
            }
        }
        foreach ($kmLabels as [$box, $lab]) {
            $this->roundRect((int) $box[0], (int) $box[1], (int) $box[2], (int) $box[3], 8, 'white', 'blue');
            $this->text(($box[0] + $box[2]) / 2, $box[1] + 3, 16, 'navy', $lab, true, 'center');
        }
        // the pins and their labels, placed clear of the pins, the km labels and one another
        foreach ($d['stops'] as $i => $s) {
            if (!isset($pos[$i])) {
                continue;
            }
            [$px, $py] = $pos[$i];
            $overnight = $s['nights'] !== [];
            $r = 17;
            imagefilledellipse($this->im, (int) $px, (int) $py, $r * 2 + 6, $r * 2 + 6, $this->col['white']);
            imagefilledellipse($this->im, (int) $px, (int) $py, $r * 2, $r * 2, $this->col[$overnight ? 'red' : 'white']);
            if (!$overnight) {
                imagesetthickness($this->im, 3);
                imageellipse($this->im, (int) $px, (int) $py, $r * 2 - 2, $r * 2 - 2, $this->col['red']);
                imagesetthickness($this->im, 1);
            }
            $this->text($px, $py - 10, 15, $overnight ? 'white' : 'red', (string) $s['order'], true, 'center');
            // the label: the town, then the days there
            $name = $s['short'] . ($s['airport'] ? ' ✈' : '');
            $daysTxt = self::dayRuns($s['days']);
            $size1 = 20; $size2 = 15;
            [$w1, $h1] = $this->measure($size1, $name, true);
            [$w2] = $this->measure($size2, $daysTxt);
            $bw = max($w1, $w2) + 14; $bh = 46;
            $cands = [];
            foreach ([26, 58, 92] as $dist) {
                $cands[] = [$px + $dist, $py - 22]; $cands[] = [$px - $dist - $bw, $py - 22];
                $cands[] = [$px - $bw / 2, $py - $dist - $bh + 4]; $cands[] = [$px - $bw / 2, $py + $dist + 2];
                $cands[] = [$px + $dist, $py - $dist - 20]; $cands[] = [$px - $dist - $bw, $py - $dist - 20];
                $cands[] = [$px + $dist, $py + $dist - 24]; $cands[] = [$px - $dist - $bw, $py + $dist - 24];
            }
            $placed = null;
            foreach ($cands as [$bx, $by]) {
                $box = [$bx, $by, $bx + $bw, $by + $bh];
                if ($box[0] < $panelX + 2 || $box[2] > $panelX + $panelW - 2 || $box[1] < $panelY + 2 || $box[3] > $panelY + $panelH - 36) {
                    continue;
                }
                if ($clear($box)) {
                    $placed = $box;
                    break;
                }
            }
            if ($placed === null) {
                $placed = [$cands[0][0], $cands[0][1], $cands[0][0] + $bw, $cands[0][1] + $bh];
            }
            $boxes[] = $placed;
            // a label that had to sit away from its pin gets a thin line to it
            $nx = max($placed[0], min($px, $placed[2])); $ny = max($placed[1], min($py, $placed[3]));
            if (hypot($nx - $px, $ny - $py) > 34) {
                imagesetthickness($this->im, 2);
                imageline($this->im, (int) $px, (int) $py, (int) $nx, (int) $ny, $this->col['grey']);
                imagesetthickness($this->im, 1);
            }
            imagefilledrectangle($this->im, (int) $placed[0], (int) $placed[1], (int) $placed[2], (int) $placed[3], $this->col['label_bg']);
            $this->text($placed[0] + 7, $placed[1] + 3, $size1, $overnight ? 'red_dark' : 'ink', $name, true);
            $this->text($placed[0] + 7, $placed[1] + 27, $size2, 'muted', $daysTxt);
        }
        // the legend, bottom-right of the panel
        $lx = $legX; $ly = $legY;
        $this->roundRect($lx, $ly, $panelX + $panelW - 10, $panelY + $panelH - 40, 8, 'white', 'line');
        imagefilledellipse($this->im, $lx + 18, $ly + 20, 18, 18, $this->col['red']);
        $this->text($lx + 34, $ly + 10, 15, 'ink', 'overnight stop');
        imagesetthickness($this->im, 3); imageellipse($this->im, $lx + 18, $ly + 46, 16, 16, $this->col['red']); imagesetthickness($this->im, 1);
        $this->text($lx + 34, $ly + 36, 15, 'ink', 'stop, no night');
        $this->thickLine($lx + 8, $ly + 72, $lx + 30, $ly + 72, 5, 'blue'); $this->arrow($lx + 26, $ly + 72, 1, 0, 8, 'blue');
        $this->text($lx + 34, $ly + 62, 15, 'ink', 'planned route, direction');
        $this->text($lx + 8, $ly + 86, 15, 'ink', '✈ airport pickup / drop-off');
        // the day list beside the map
        $this->dayList($d, $panelX + $panelW + 24, $panelY, $W - $panelW - 72, $panelH);
    }

    private static function dayRuns(array $days): string
    {
        $days = array_values(array_unique(array_map('intval', $days)));
        sort($days);
        if (!$days) {
            return '';
        }
        $runs = [];
        $start = $prev = $days[0];
        foreach (array_slice($days, 1) as $x) {
            if ($x === $prev + 1) {
                $prev = $x;
                continue;
            }
            $runs[] = $start === $prev ? (string) $start : $start . '–' . $prev;
            $start = $prev = $x;
        }
        $runs[] = $start === $prev ? (string) $start : $start . '–' . $prev;
        return 'Day ' . implode(', ', $runs);
    }

    /** The days beside the map: each day's route, planned kilometres and night, as compact as the tour's length needs. */
    private function dayList(array $d, int $x, int $y, int $w, int $h): void
    {
        $this->roundRect($x, $y, $x + $w, $y + $h, 10, 'white', 'line');
        $this->text($x + 16, $y + 14, 19, 'navy', 'Day by day — planned', true);
        $n = max(1, count($d['days']));
        $rowH = (int) min(96, max(54, ($h - 70) / $n));
        $three = $rowH >= 84;
        $yy = $y + 50;
        $fs = $rowH >= 84 ? 16 : ($rowH >= 66 ? 15 : 13);
        foreach ($d['days'] as $day) {
            if ($yy + $rowH > $y + $h - 8) {
                $this->text($x + 16, $yy, $fs, 'muted', '… and ' . (count($d['days']) - array_search($day, $d['days'], true)) . ' more day(s) in the table');
                break;
            }
            imageline($this->im, $x + 12, $yy - 4, $x + $w - 12, $yy - 4, $this->col['soft']);
            $kmTxt = self::fmtKm($day['km']);
            [$kw] = $this->measure($fs, $kmTxt, true);
            $this->text($x + 16, $yy + 2, $fs, 'navy', $this->fit($fs, 'Day ' . $day['day'] . ($day['date'] ? ' · ' . self::fmtDate($day['date'], 'j M') : ''), $w - 44 - $kw, true), true);
            $this->text($x + $w - 16, $yy + 2, $fs, 'ink', $kmTxt, true, 'right');
            $route = implode(' → ', array_map(static fn ($p) => self::locate($p)['town'] ?? self::shortName($p), $day['route']));
            $ex = $day['extra'] > 0 ? '+' . rtrim(rtrim(number_format($day['extra'], 1, '.', ''), '0'), '.') . ' extra' : '';
            [$ew] = $ex !== '' ? $this->measure($fs - 2, $ex) : [0];
            if ($ex !== '') {
                $this->text($x + $w - 16, $yy + 3 + $fs + 7, $fs - 2, 'amber', $ex, false, 'right');
            }
            $this->text($x + 16, $yy + 2 + $fs + 7, $fs - 1, 'ink', $this->fit($fs - 1, $route, $w - 32 - ($ew ? $ew + 12 : 0)));
            if ($three) {
                $night = $day['night_hotel'] !== '' ? 'Night: ' . $day['night_hotel'] . ($day['night_area'] !== '' ? ' (' . $day['night_area'] . ')' : '') : ($day['night_area'] !== '' ? 'Night: ' . $day['night_area'] : '');
                if ($night !== '') {
                    $this->text($x + 16, $yy + 2 + ($fs + 7) * 2, $fs - 2, 'green', $this->fit($fs - 2, $night, $w - 32));
                }
            }
            $yy += $rowH;
        }
    }

    /** The day-by-day table of the whole card: a band per day, a line per leg, the totals. Returns the y after it. */
    private function table(array $d, int $y, int $W): int
    {
        $x = 36; $w = $W - 72;
        $this->text($x, $y + 24, 24, 'navy', 'Day by day — planned mileage', true);
        $this->text($x, $y + 60, 16, 'muted', $this->fit(16, 'Extra = kilometres allowed for site visits and city tours on that leg, on top of the journey. Actual driving may vary.', $w));
        $y += 98;
        // the column heads
        $this->rect($x, $y, $x + $w, $y + 34, 'soft');
        $cols = ['day' => $x + 10, 'route' => $x + 150, 'km' => $x + $w - 340, 'extra' => $x + $w - 210, 'night' => $x + $w - 100];
        $this->text($cols['day'], $y + 8, 14, 'muted', 'DAY / DATE', true);
        $this->text($cols['route'], $y + 8, 14, 'muted', 'FROM → TO', true);
        $this->text($cols['km'] + 90, $y + 8, 14, 'muted', 'PLANNED KM', true, 'right');
        $this->text($cols['extra'] + 80, $y + 8, 14, 'muted', 'EXTRA', true, 'right');
        $this->text($cols['night'] + 90, $y + 8, 14, 'muted', 'NIGHT', true, 'right');
        $y += 34;
        foreach ($d['days'] as $day) {
            // the day's band: its route and its total
            $this->rect($x, $y, $x + $w, $y + 62, 'day_band');
            $this->text($cols['day'], $y + 10, 19, 'navy', 'Day ' . $day['day'], true);
            if ($day['date']) {
                $this->text($cols['day'], $y + 36, 15, 'muted', self::fmtDate($day['date'], 'D j M'));
            }
            $route = implode(' → ', array_map(static fn ($p) => self::locate($p)['town'] ?? self::shortName($p), $day['route']));
            $this->text($cols['route'], $y + 10, 18, 'ink', $this->fit(18, $route, $cols['km'] - $cols['route'] - 60, true), true);
            $night = $day['night_hotel'] !== '' ? 'Night: ' . $day['night_hotel'] . ($day['night_area'] !== '' ? ', ' . $day['night_area'] : '') : ($day['night_area'] !== '' ? 'Night: ' . $day['night_area'] . ' — hotel not named yet' : 'No hotel night');
            $this->text($cols['route'], $y + 37, 15, $day['night_hotel'] !== '' ? 'green' : 'muted', $this->fit(15, $night, $cols['km'] - $cols['route'] - 60));
            $this->text($cols['km'] + 90, $y + 10, 20, 'navy', self::fmtKm($day['km']) . ($day['unpriced'] ? ' +?' : ''), true, 'right');
            $this->text($cols['km'] + 90, $y + 38, 14, 'muted', 'day total', false, 'right');
            $this->text($cols['extra'] + 80, $y + 10, 18, $day['extra'] > 0 ? 'amber' : 'muted', $day['extra'] > 0 ? self::fmtKm($day['extra']) : '—', true, 'right');
            $this->text($cols['night'] + 90, $y + 14, 16, $day['night_hotel'] !== '' ? 'green' : 'muted', $day['night_hotel'] !== '' ? '● hotel' : '—', true, 'right');
            $y += 62;
            foreach ($day['legs'] as $li) {
                $l = $d['legs'][$li];
                $fromT = $l['from_loc']['town'] ?? self::shortName($l['from']);
                $toT = $l['to_loc']['town'] ?? self::shortName($l['to']);
                $what = $l['same'] ? $fromT : $fromT . ' → ' . $toT;
                if ($l['same'] && $l['kind'] === 'local') {
                    $what .= ' (city tour)';
                } elseif ($l['kind'] === 'idle') {
                    $what .= ' (stay day)';
                } elseif ($l['same'] && $l['kind'] === 'arrival') {
                    $what .= ' (airport hotel — no drive)';
                }
                $tag = $l['tags'] ? strtoupper($l['tags'][0]) : '';
                $colTag = $l['garage'] ? 'grey' : (str_starts_with($tag, 'AIRPORT') ? 'blue' : 'muted');
                $this->text($cols['route'], $y + 12, 17, $l['garage'] ? 'muted' : 'ink', $this->fit(17, $what, $cols['km'] - $cols['route'] - ($tag !== '' ? 190 : 40)));
                if ($tag !== '') {
                    $this->text($cols['km'] - 30, $y + 14, 12, $colTag, $tag, true, 'right');
                }
                $this->text($cols['km'] + 90, $y + 12, 17, $l['km'] === null ? 'red' : 'ink', $l['km'] === null ? 'not set' : self::fmtKm($l['km']), false, 'right');
                $this->text($cols['extra'] + 80, $y + 12, 16, 'amber', $l['extra'] ? self::fmtKm($l['extra']) : '—', false, 'right');
                imageline($this->im, $x, $y + 45, $x + $w, $y + 45, $this->col['soft']);
                $y += 46;
            }
        }
        // the totals
        $y += 16;
        $this->roundRect($x, $y, $x + $w, $y + 112, 10, 'blue_soft', 'line');
        $this->text($x + 20, $y + 14, 20, 'navy', 'Total planned mileage', true);
        $this->text($x + 20, $y + 46, 14, 'muted', $this->fit(14, count($d['legs']) . ' legs over ' . count($d['days']) . ' days' . ($d['garage_km'] > 0 ? ' · garage runs ' . self::fmtKm($d['garage_km']) . ' included' : '') . ($d['unpriced'] ? ' · ' . $d['unpriced'] . ' leg(s) without a figure' : ''), (int) ($w * 0.5)));
        $this->text($x + 20, $y + 68, 14, 'muted', $this->fit(14, 'Planned mileage — actual driving may vary.', (int) ($w * 0.5)));
        $this->text($x + 20, $y + 90, 14, 'muted', $this->fit(14, 'The log sheet records the kilometres driven.', (int) ($w * 0.5)));
        $this->text($x + $w - 20, $y + 10, 34, 'navy', self::fmtKm($d['total_km']), true, 'right');
        $this->text($x + $w - 20, $y + 58, 15, 'amber', 'Extra sightseeing allowance (separate)', true, 'right');
        $this->text($x + $w - 20, $y + 82, 18, 'amber', self::fmtKm($d['total_extra']), true, 'right');
        $y += 126;
        if ($d['unknown']) {
            $names = implode(', ', array_map(static fn ($s) => $s['name'], $d['unknown']));
            $this->text($x, $y, 14, 'muted', $this->fit(14, 'Not on the map (place not in the gazetteer): ' . $names, $w));
            $y += 30;
        }
        return $y;
    }

    private function footer(array $d, int $y, int $W, int $H): void
    {
        $y = $H - 76;
        imageline($this->im, 36, $y, $W - 36, $y, $this->col['line']);
        $co = $d['company'];
        $this->text(36, $y + 14, 15, 'ink', $this->fit(15, trim(($co['name'] ?: 'Avenir Holidays') . (!empty($co['phone']) ? ' · Tel ' . $co['phone'] : '')), (int) ($W * 0.42), true), true);
        $this->text(36, $y + 40, 13, 'muted', $this->fit(13, trim(($co['address'] ?? '') . (!empty($co['email']) ? ' · ' . $co['email'] : '')), (int) ($W * 0.5)));
        $this->text($W - 36, $y + 14, 14, 'navy', 'Tour ' . $d['tour_no'] . ' · rev. ' . ($d['revision'] ? self::fmtDate($d['revision'], 'j M Y H:i') : '—'), true, 'right');
        $this->text($W - 36, $y + 40, 13, 'muted', 'A newer revision replaces this sheet.', false, 'right');
    }
}
