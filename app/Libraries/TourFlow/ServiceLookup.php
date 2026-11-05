<?php

namespace App\Libraries\TourFlow;

/**
 * Prices a day service against the lists the company already keeps.
 *
 * Two existing tables do the work, and neither is written to from here:
 *
 *   entrance_fee    the 119-row master of visits, safaris, treatments, guide and
 *                   driver charges, each with a code and a USD amount, plus the
 *                   direct_payment / is_prepaid flags that say who settles it.
 *   daily_usd_rate  one USD rate per date, which is what turns that USD amount
 *                   into rupees — the same pair tour_activity keeps as fee/lkr_fee.
 *
 * The link to the master is deliberately soft: a code and a name, never a foreign
 * key, so nothing here can damage the master list or the live tours built on it.
 *
 * Matching is exact, by code or by a normalised name. Anything else is reported as
 * not in the list, with the closest entry offered only as a suggestion for a person
 * to accept — the module never quietly decides that one thing is another.
 *
 * Schema 39 (tf-entrance-r1): once the Place to Visit / Entrance Fee Master is installed it
 * is the one authority on entrances and safaris. A line that is a place of the master is
 * charged from its rates for the day (the entrance a head, adult and child; at a safari park
 * the jeep per vehicle too; USD or LKR); a rate marked Free is free; a place with no valid
 * rate for the day (none, ended, or 0.00) is held. An entrance or safari line that is not in
 * the master is held — never free. Ordinary sightseeing is outside the master: no entrance
 * charge. Meals, guides, trains, treatments and other activities are outside it too: the old
 * list goes on pricing them exactly as before (it never prices an entrance or a safari again).
 * Until Install now, everything reads as before.
 */
final class ServiceLookup
{
    public const KINDS = ['visit', 'entrance', 'safari', 'train', 'ayurveda', 'lunch', 'dinner', 'guide', 'transport', 'other'];

    /** The kinds charged per head, for which the party is the ceiling on the quantity. A jeep, a guide or a vehicle is counted in units, not people. */
    public const PER_PERSON = ['visit', 'entrance', 'train', 'ayurveda', 'lunch', 'dinner'];

    public static function perPerson(?string $kind): bool
    {
        return in_array(strtolower(trim((string) $kind)), self::PER_PERSON, true);
    }

    /**
     * tf-attend-r1 (28 Oct 2026): how many a line is charged for. A per-person line (an entrance, a visit, a train,
     * a treatment — not a Meal Master meal, which keeps its own adults and children) follows the party: a number on it
     * counts only when staff set it as an ATTENDANCE OVERRIDE (qty_override = 1, with a reason). Any other number on a
     * per-person line — typed into the old "How many" box, or read from an e-mail — is not applied. Lines that are not
     * per person (tuk tuks, jeeps, transport, other) keep their number as before. null = the party.
     */
    public static function effectiveQty(array $s): ?float
    {
        $qty = isset($s['qty']) && $s['qty'] !== null && $s['qty'] !== '' ? (float) $s['qty'] : null;
        if ($qty === null) {
            return null;
        }
        if (self::perPerson($s['kind'] ?? null) && empty($s['meal_id']) && empty($s['qty_override'])) {
            return null;
        }
        return $qty;
    }

    /** Is this line one whose count follows the party unless an attendance override is set? */
    public static function followsParty(array $s): bool
    {
        return self::perPerson($s['kind'] ?? null) && empty($s['meal_id']);
    }

    /** How the money is settled; taken from the master, or stated by the agent. no_charge (schema 25): a stop with nothing to pay. */
    public const PAY_MODES = ['in_tour', 'prepaid', 'direct', 'own_arrangement', 'not_arranged', 'unknown', 'no_charge'];

    /** The pay modes that cost nothing: declined by the agent, not happening, or nothing to pay. */
    public const FREE_MODES = ['own_arrangement', 'not_arranged', 'no_charge'];

    private ?array $noChargeWords = null;
    private ?array $aliases = null;

    /** A master amount above this is almost certainly rupees typed into the USD column. */
    public const USD_SANITY = 500.0;

    private $db;
    private ?bool $masterOn = null;   // schema 39: the Place to Visit / Entrance Fee Master is installed
    private ?array $ix = null;        // its places, codes, names, old records, other names and rates (EntranceMaster::index)
    private ?array $byCode = null;
    private ?array $byName = null;
    private ?array $byId = null;
    private array $rateCache = [];
    private ?bool $hasMaster = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =====================================================================
    //  Names
    // =====================================================================

    /**
     * The comparison key for a service name. Much lighter than the hotel one, because
     * here the words carry the meaning: "temple", "garden", "tour", "fee" and "SS" are
     * all part of what the thing IS, and the master list is full of them — "Colombo SS",
     * "Galle City Tour", "Guide Fee". Strip those and different services collapse onto
     * each other. So only true filler comes out, plus the trade's "S/S", which is written
     * both ways and must land on the same key either way.
     */
    public static function nameKey(string $name): string
    {
        $k = mb_strtolower(trim($name));
        $k = str_replace(['&', '’', "'"], [' and ', '', ''], $k);
        $k = self::tradeSpelling($k);
        $k = str_replace('/', ' ', (string) $k);
        $k = preg_replace('/[^a-z0-9 ]+/', ' ', $k);
        $k = preg_replace('/\b(the|and|of|at|in|a)\b/', ' ', (string) $k);
        return trim(preg_replace('/\s+/', '', (string) $k));
    }

    /**
     * Which of the kinds a service belongs to, from its own words. This only decides how
     * the line is grouped and labelled on screen — it makes no claim about the email, so
     * an honest "other" is always available and nothing is inferred about price or pax.
     */
    public static function kindFor(?string $title, ?string $given = null): string
    {
        $g = strtolower(trim((string) $given));
        if (in_array($g, self::KINDS, true)) {
            return $g;
        }
        $t = ' ' . mb_strtolower(trim((string) $title)) . ' ';
        // a boat or river "safari" is a boat ride, not a national-park safari (schema 39: a safari must be a park of the master)
        foreach (['boat safari', 'river safari', 'boat ride'] as $w) {
            if (str_contains($t, $w)) {
                return 'transport';
            }
        }
        $map = [
            'safari'    => ['safari', 'jeep'],
            'train'     => ['train', 'railway'],
            'ayurveda'  => ['ayurved', 'spa ', 'treatment', 'massage'],
            'lunch'     => ['lunch'],
            'dinner'    => ['dinner'],
            'guide'     => ['guide', 'jsg', 'chaperone'],
            'transport' => ['tuk tuk', 'tuktuk', 'tuk-tuk', 'transfer', 'vehicle', 'taxi', 'boat ride', 'domestic air', 'air ticket'],
            'entrance'  => ['entrance', 'ticket', 'temple', 'museum', 'garden', 'fort', 'rock', 'park', 'maligawa', 'viharaya', 'zoo'],
            'visit'     => ['s/s', ' ss ', ' s s ', 'sightseeing', 'sight seeing', 'city tour', 'walking', 'shopping', 'dance', 'factory', 'plantation'],
        ];
        foreach ($map as $kind => $words) {
            foreach ($words as $w) {
                if (str_contains($t, $w)) {
                    return $kind;
                }
            }
        }
        return 'other';
    }

    // =====================================================================
    //  Sightseeing with nothing to pay, and the list's other names (schema 25)
    // =====================================================================

    /**
     * The trade writes sightseeing five ways — S/S, S.S, S-S, S S, SS and the word itself —
     * and the priced list uses "SS" (POLONNARUWA SS, Colombo SS). One spelling for all of
     * them, so "Polonnaruwa sightseeing" finds POLONNARUWA SS in the list and "Kandy S S"
     * is the same stop as "Kandy S/S". Lower-case input.
     */
    private static function tradeSpelling(string $k): string
    {
        $k = preg_replace('/\bsight\s*-?\s*seeing\b/', ' ss ', $k);
        $k = preg_replace('/\bs\s*[\/.\-]\s*s\b/', ' ss ', (string) $k);
        $k = preg_replace('/\bs\s+s\b/', ' ss ', (string) $k);
        // tf-place-r1 (27 Oct 2026): place names the trade spells more than one way are read as one spelling, so an
        // agent's "Pinnawala" and the master's "PINNAWELA" are the same word (names, other names and lines alike)
        foreach (self::PLACE_SPELLINGS as $variant => $one) {
            $k = preg_replace('/\b' . $variant . '\b/', $one, (string) $k);
        }
        return (string) $k;
    }

    /** Spelling variants of Sri Lankan place names (lower case, as regular expressions) → the one spelling used for matching. */
    public const PLACE_SPELLINGS = [
        'pin+aw[ae]l+a'        => 'pinnawala',
        'sigiri+y?a'           => 'sigiriya',
        'polon+aru[wv]a'       => 'polonnaruwa',
        'anuradhapur[ae]'      => 'anuradhapura',
        'dambul+a'             => 'dambulla',
        'nuwara\\s*eliya'       => 'nuwara eliya',
        'min+eriya?'           => 'minneriya',
        'ka[uw]dul+a'          => 'kaudulla',
        'udawal+awe'           => 'udawalawe',
        'wilpat+u'             => 'wilpattu',
        'hik+aduwa'            => 'hikkaduwa',
        'kata?ragama'          => 'kataragama',
        'ratnapur[ae]'         => 'ratnapura',
    ];

    /** A title as words: lower case, sightseeing in every spelling written as ss, punctuation gone, one space between words. */
    public static function words(?string $title): string
    {
        $k = mb_strtolower(trim((string) $title));
        $k = str_replace(['&', '’', "'"], [' and ', '', ''], $k);
        $k = self::tradeSpelling($k);
        $k = str_replace('/', ' ', (string) $k);
        $k = preg_replace('/[^a-z0-9 ]+/', ' ', (string) $k);
        return trim((string) preg_replace('/\s+/', ' ', (string) $k));
    }

    private function setting(string $k): ?string
    {
        try {
            $r = $this->db->query('SELECT v FROM tf_meta WHERE k = ?', [$k])->getRowArray();
            return $r['v'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The words that say a stop costs nothing (Admin → Settings), as word strings; empty when the setting is blank. */
    public function noChargeWords(): array
    {
        if ($this->noChargeWords === null) {
            $this->noChargeWords = [];
            foreach (preg_split('/[,\n]+/', (string) $this->setting('service_no_charge_words')) ?: [] as $w) {
                $w = self::words($w);
                if ($w !== '') {
                    $this->noChargeWords[] = $w;
                }
            }
        }
        return $this->noChargeWords;
    }

    /** The phrase in the title that says it is no charge, or null. Whole words only: "market" is not "supermarket". */
    public function noChargeWord(?string $title): ?string
    {
        $t = ' ' . self::words($title) . ' ';
        if (trim($t) === '') {
            return null;
        }
        foreach ($this->noChargeWords() as $w) {
            if (str_contains($t, ' ' . $w . ' ')) {
                return $w;
            }
        }
        return null;
    }

    /** The phrases the priced list is known by (Admin → Settings): "phrase => CODE", one per line, as words => code. */
    public function aliases(): array
    {
        if ($this->aliases === null && $this->master()) {
            // schema 39: the other names live on the master's places; phrase => the place's code, for readers that want codes
            $this->aliases = [];
            foreach ($this->aliasEntries() as $phrase => $p) {
                $this->aliases[$phrase] = $p['code'];
            }
            // and the old setting's names for what the old list still prices (a meal, a service — no place has the code): read for those lines as before
            foreach (preg_split('/\r?\n/', (string) $this->setting('service_aliases')) ?: [] as $line) {
                if (!str_contains($line, '=>')) {
                    continue;
                }
                [$phrase, $code] = array_map('trim', explode('=>', $line, 2));
                $phrase = self::words($phrase);
                if ($phrase !== '' && $code !== '' && !isset($this->aliases[$phrase]) && !isset($this->ix()['by_code'][mb_strtolower($code)])) {
                    $this->aliases[$phrase] = $code;
                }
            }
            uksort($this->aliases, static fn ($a, $b) => strlen($b) <=> strlen($a));
            return $this->aliases;
        }
        if ($this->aliases === null) {
            $this->aliases = [];
            foreach (preg_split('/\r?\n/', (string) $this->setting('service_aliases')) ?: [] as $line) {
                if (!str_contains($line, '=>')) {
                    continue;
                }
                [$phrase, $code] = array_map('trim', explode('=>', $line, 2));
                $phrase = self::words($phrase);
                if ($phrase !== '' && $code !== '') {
                    $this->aliases[$phrase] = $code;
                }
            }
            // the longest phrase first, so "temple of the tooth" is tried before "temple"
            uksort($this->aliases, static fn ($a, $b) => strlen($b) <=> strlen($a));
        }
        return $this->aliases;
    }

    /** The master code an alias in the title points at, or null. Whole phrases only. */
    public function aliasCode(?string $title): ?string
    {
        $t = ' ' . self::words($title) . ' ';
        if (trim($t) === '') {
            return null;
        }
        foreach ($this->aliases() as $phrase => $code) {
            if (str_contains($t, ' ' . $phrase . ' ')) {
                return $code;
            }
        }
        return null;
    }

    // =====================================================================
    //  The master list
    // =====================================================================

    /**
     * Forget what was read of the master and the settings, so the next question reads them
     * again. The Entrance & Activities sync asks this before it prices, so a rate changed in
     * the master a moment ago is what the lines follow — the reads are a few small queries.
     */
    public function refresh(): void
    {
        $this->byCode = null;
        $this->byName = null;
        $this->byId   = null;
        $this->noChargeWords = null;
        $this->aliases       = null;
        $this->rateCache     = [];
        $this->ix            = null;
        $this->masterOn      = null;
        $this->meals         = null;   // schema 40: the Meal Master and the suppliers read afresh too
    }

    /** False when this install has no entrance_fee table; the panel then keeps names only. */
    public function available(): bool
    {
        if ($this->hasMaster === null) {
            try {
                $this->hasMaster = (bool) $this->db->query("SHOW TABLES LIKE 'entrance_fee'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasMaster = false;
            }
        }
        return $this->hasMaster;
    }

    /** True once the Place to Visit / Entrance Fee Master is installed (schema 39): it is then the authority on entrances and safaris. */
    public function master(): bool
    {
        if ($this->masterOn === null) {
            $this->masterOn = EntranceMaster::installed($this->db);
        }
        return $this->masterOn;
    }

    private const NO_IX = ['places' => [], 'by_code' => [], 'by_name' => [], 'by_fee' => [], 'aliases' => [], 'off_names' => [], 'rates' => [], 'old_out' => [], 'services' => []];

    /** The master's index, read once per refresh. */
    private function ix(): array
    {
        if ($this->ix === null) {
            try {
                $this->ix = $this->master() ? EntranceMaster::index($this->db) : self::NO_IX;
            } catch (\Throwable $e) {
                $this->ix = self::NO_IX;
            }
        }
        return $this->ix;
    }

    /** A place of the master by id, following a merged place to the one it was merged into; null if not there. */
    public function placeEntry(?int $placeId): ?array
    {
        if ($placeId === null || $placeId <= 0) {
            return null;
        }
        $ix = $this->ix();
        $guard = 0;
        $p = $ix['places'][$placeId] ?? null;
        while ($p !== null && $p['merged_into'] !== null && $guard++ < 5) {
            $p = $ix['places'][(int) $p['merged_into']] ?? null;
        }
        return $p;
    }

    /** The place an old record of the list was imported into (in use or not), or null. */
    public function placeForFee(?int $feeId): ?int
    {
        if ($feeId === null || $feeId <= 0 || !$this->master()) {
            return null;
        }
        $pid = $this->ix()['by_fee'][$feeId] ?? null;
        return $pid !== null ? (int) $pid : null;
    }

    /** The master's rate of one charge of a place (the entrance, or the jeep) on a date, or null. */
    public function rateOn(int $placeId, ?string $date, string $charge = 'entrance'): ?array
    {
        return EntranceMaster::pick($this->ix()['rates'][$placeId] ?? [], $date ?: date('Y-m-d'), $charge);
    }

    /** The kinds of line that are never a place of the master by a code or an other name (schema 39): "Lunch at Sigiriya Village" is not Sigiriya. */
    private const SERVICE_ONLY_KINDS = ['lunch', 'dinner', 'train', 'guide', 'transport', 'ayurveda'];

    /** The master's other names, as words => the place each points at (with its code and name); empty before the master is installed. */
    public function aliasEntries(): array
    {
        $out = [];
        $ix = $this->ix();
        foreach ($ix['aliases'] as $phrase => $pid) {
            $p = $this->placeEntry((int) $pid);
            if ($p !== null && (int) $p['active'] === 1) {
                $out[$phrase] = ['id' => (int) $p['id'], 'code' => (string) ($p['code'] ?? ''), 'name' => (string) $p['name']];
            }
        }
        return $out;
    }

    /**
     * The place of the master a line is, if any: the place picked for it, the place the old
     * record it was priced from was imported into, its code, its own name, or an other name of
     * a place in its words — exactly, never the nearest. 'off' is set when the line is tied to
     * a place taken out of use.
     *
     * @return array{place:?array, off:?array}
     */
    private function findPlace(?string $title, ?string $code, ?int $feeId, ?int $placeId, bool $attraction = true): array
    {
        $ix  = $this->ix();
        $on  = static fn ($p) => $p !== null && (int) $p['active'] === 1 && $p['merged_into'] === null;
        foreach ([$placeId !== null && $placeId > 0 ? $this->placeEntry($placeId) : null,
                  $feeId !== null && $feeId > 0 && isset($ix['by_fee'][$feeId]) ? $this->placeEntry((int) $ix['by_fee'][$feeId]) : null] as $p) {
            if ($p === null) {
                continue;
            }
            // the line is tied to this place: in use, it is the answer; out of use, that is the answer too — never another place found by its words
            return $on($p) ? ['place' => $p, 'off' => null] : ['place' => null, 'off' => $p];
        }
        // a code: only for a line not priced from another record of the old list (its code is that record's), and never for a meal, a train …
        $code = mb_strtolower(trim((string) $code));
        if ($attraction && $code !== '' && !($feeId !== null && $feeId > 0) && isset($ix['by_code'][$code]) && $on($p = $this->placeEntry((int) $ix['by_code'][$code]))) {
            return ['place' => $p, 'off' => null];
        }
        $key = self::nameKey((string) $title);
        if ($key !== '' && isset($ix['by_name'][$key]) && $on($p = $this->placeEntry((int) $ix['by_name'][$key]))) {
            return ['place' => $p, 'off' => null];
        }
        // an other name inside the words — never for a meal, a train, a guide, a transfer or a treatment; an other name of one
        // word only as the whole name ("Polonnaruwa", not "Polonnaruwa village tour"). A place's own name only as the whole
        // name: "Hindu Temple" is not every Hindu temple, "Geoffrey bawa" not every Bawa house — the place is offered instead
        $t = $attraction ? ' ' . self::words($title) . ' ' : '';
        if (trim($t) !== '') {
            foreach ($ix['aliases'] as $phrase => $pid) {
                if ((str_contains($phrase, ' ') ? str_contains($t, ' ' . $phrase . ' ') : $t === ' ' . $phrase . ' ') && $on($p = $this->placeEntry((int) $pid))) {
                    return ['place' => $p, 'off' => null];
                }
            }
        }
        // tf-place-r1: a name one letter away from exactly one place's own name (both at least seven letters, "Pinawala" for
        // PINNAWELA, "Sigirya" for SIGIRIYA) is that place; two letters or two candidates is a question for a person
        if ($attraction && $key !== '' && mb_strlen($key) >= 7) {
            $near = null;
            $count = 0;
            foreach ($ix['by_name'] as $k => $pid) {
                if (mb_strlen($k) >= 7 && abs(mb_strlen($k) - mb_strlen($key)) <= 1 && levenshtein(substr($key, 0, 255), substr($k, 0, 255)) === 1) {
                    $count++;
                    $near = (int) $pid;
                }
            }
            if ($count === 1 && $on($p = $this->placeEntry($near))) {
                return ['place' => $p, 'off' => null, 'how' => 'spelling'];
            }
        }
        // the very name of a place taken out of use: that is the answer (held), never another place
        if ($key !== '' && isset($ix['off_names'][$key]) && ($p = $this->placeEntry((int) $ix['off_names'][$key])) !== null && !$on($p)) {
            return ['place' => null, 'off' => $p];
        }
        return ['place' => null, 'off' => null];
    }

    /**
     * The place whose name is spelt closest to a title (or whose whole name, or one of its other
     * names, is in it), offered on a held or unsure line for a person to pick with one click —
     * never applied. @return array{code:?string, name:?string, id:?int}
     */
    private function nearestPlace(string $title): array
    {
        $key = self::nameKey($title);
        $t   = ' ' . self::words($title) . ' ';
        $best = null;
        $bestD = PHP_INT_MAX;
        $within = null;
        $codes = $this->ix()['by_code'];
        foreach ($this->ix()['places'] as $p) {
            // offered by its code (the one-click "use it" on the line): a place with no code of its own is picked by name instead
            if ((int) $p['active'] !== 1 || $p['merged_into'] !== null || ($codes[mb_strtolower(trim((string) ($p['code'] ?? '')))] ?? null) !== (int) $p['id']) {
                continue;
            }
            $k = self::nameKey((string) $p['name']);
            $w = self::words((string) $p['name']);
            if ($k !== '' && $key !== '') {
                $d = levenshtein(substr($key, 0, 255), substr($k, 0, 255));
                if ($d < $bestD) {
                    $bestD = $d;
                    $best = $p;
                }
            }
            if ($w !== '' && (str_contains($w, ' ') || mb_strlen($w) >= 5) && str_contains($t, ' ' . $w . ' ') && ($within === null || strlen($w) > strlen(self::words((string) $within['name'])))) {
                $within = $p;
            }
        }
        // an other name of the place inside the words ("Polonnaruwa ancient city" → the Polonnaruwa place), the longest wins
        $withinLen = $within !== null ? strlen(self::words((string) $within['name'])) : 0;
        foreach ($this->ix()['aliases'] as $phrase => $pid) {
            $p = $this->placeEntry((int) $pid);
            if ($p === null || (int) $p['active'] !== 1 || ($codes[mb_strtolower(trim((string) ($p['code'] ?? '')))] ?? null) !== (int) $p['id']) {
                continue;
            }
            if ((str_contains($phrase, ' ') || mb_strlen($phrase) >= 5) && str_contains($t, ' ' . $phrase . ' ') && strlen($phrase) > $withinLen) {
                $within = $p;
                $withinLen = strlen($phrase);
            }
        }
        $hit = ($best !== null && $bestD <= max(2, (int) floor(mb_strlen($key) / 5))) ? $best : $within;
        return $hit ? ['code' => trim((string) ($hit['code'] ?? '')) ?: null, 'name' => (string) $hit['name'], 'id' => (int) $hit['id']] : ['code' => null, 'name' => null, 'id' => null];
    }

    private function load(): void
    {
        if ($this->byCode !== null) {
            return;
        }
        $this->byCode = [];
        $this->byName = [];
        $this->byId   = [];
        if (!$this->available()) {
            return;
        }
        try {
            // schema 29: an entry switched off under Paid Activities & Services (tf_fee_meta.active = 0)
            // stays readable by its id, so a line priced from it keeps its entry and figure, but it
            // is never matched afresh by name or code. The priced list itself is read as it is.
            $hasMeta = (bool) $this->db->query("SHOW TABLES LIKE 'tf_fee_meta'")->getResultArray();
            $rows = $hasMeta
                ? $this->db->query('SELECT e.id, e.name, e.code, e.location, e.entranceFee, e.direct_payment, e.is_prepaid, COALESCE(m.active, 1) AS active, m.destination AS dest_set FROM entrance_fee e LEFT JOIN tf_fee_meta m ON m.fee_id = e.id')->getResultArray()
                : $this->db->query('SELECT id, name, code, location, entranceFee, direct_payment, is_prepaid, 1 AS active, NULL AS dest_set FROM entrance_fee')->getResultArray();
        } catch (\Throwable $e) {
            $rows = [];
        }
        // schema 39: the records imported into the master, and the general sightseeing and places held for review,
        // are never priced from the old list again — it keeps its meals, services and activities
        $out = $this->master() ? $this->ix()['old_out'] : [];
        foreach ($rows as $r) {
            if (isset($out[(int) $r['id']])) {
                continue;
            }
            $this->byId[(int) $r['id']] = $r;
            if ((int) ($r['active'] ?? 1) !== 1) {
                continue;
            }
            $code = trim((string) ($r['code'] ?? ''));
            if ($code !== '' && !isset($this->byCode[mb_strtolower($code)])) {
                $this->byCode[mb_strtolower($code)] = $r;
            }
            $key = self::nameKey((string) ($r['name'] ?? ''));
            if ($key !== '' && !isset($this->byName[$key])) {
                $this->byName[$key] = $r;
            }
        }
    }

    /**
     * Find one service in the master list.
     *
     * The entry a line was priced from before (its id) is looked up first: a person's pick
     * of an entry stays that entry when the master renames it, and follows the master's
     * rate when that changes — the pick is the entry, never the figure.
     *
     * Schema 39: a place of the Place to Visit / Entrance Fee Master is found first (place =>
     * true); otherwise the old list is read for what it still prices (meals, services, activities).
     *
     * @return array{state:string, fee_id:?int, fee_code:?string, name:?string, unit_usd:?float, pay_mode:string, note:?string, suggest_code:?string, suggest_name:?string, gone:?string}
     */
    public function match(?string $title, ?string $code = null, ?int $feeId = null, ?int $placeId = null, bool $attraction = true): array
    {
        $miss = ['state' => 'not_in_list', 'fee_id' => null, 'fee_code' => null, 'name' => null, 'unit_usd' => null, 'pay_mode' => 'unknown', 'note' => null, 'suggest_code' => null, 'suggest_name' => null, 'gone' => null];

        $title = trim((string) $title);
        if ($title === '' && !($placeId > 0)) {
            return ['state' => 'nothing_named'] + $miss;
        }
        $this->load();
        if ($this->master()) {
            $f = $this->findPlace($title, $code, $feeId, $placeId, $attraction);
            if ($f['place'] !== null) {
                $p = $f['place'];
                return [
                    'state' => 'matched', 'place' => true, 'place_id' => (int) $p['id'], 'fee_id' => null,
                    'fee_code' => trim((string) ($p['code'] ?? '')) !== '' ? trim((string) $p['code']) : null,
                    'name' => (string) $p['name'], 'unit_usd' => null, 'pay_mode' => (string) $p['pay_mode'],
                    'note' => ($f['how'] ?? null) === 'spelling' ? 'Read as ' . trim((string) $p['name']) . ' (a spelling of the same name).' : null, 'suggest_code' => null, 'suggest_name' => null, 'gone' => null,
                ];
            }
            $miss['place_off'] = $f['off'];
            if ($f['off'] !== null) {
                // tied to a place taken out of use: said, and never priced from the old list either
                $miss['gone'] = '"' . trim((string) $f['off']['name']) . '" is not in use in the Place to Visit / Entrance Fee Master.';
                $miss['note'] = $miss['gone'];
                return $miss;
            }
        }
        if (!$this->available()) {
            return ['note' => 'There is no priced list on this install, so the name is kept as written.'] + $miss;
        }

        $code = trim((string) $code);
        $hit  = null;
        if ($feeId !== null && $feeId > 0 && isset($this->byId[$feeId])) {
            $hit = $this->byId[$feeId];
        }
        if ($hit === null && $code !== '' && isset($this->byCode[mb_strtolower($code)])) {
            $hit = $this->byCode[mb_strtolower($code)];
        }
        if ($hit === null) {
            $key = self::nameKey($title);
            if ($key !== '' && isset($this->byName[$key])) {
                $hit = $this->byName[$key];
            }
        }
        if ($hit === null) {
            // the list's other names (Admin → Settings): "temple of the tooth" is the Kandy temple entrance
            $alias = $this->aliasCode($title);
            if ($alias !== null && isset($this->byCode[mb_strtolower($alias)])) {
                $hit = $this->byCode[mb_strtolower($alias)];
            }
        }

        if ($hit === null) {
            $sug = $this->suggestion($title);
            if ($sug['code'] === null) {
                // an entry whose whole name sits inside the title ("Colombo SS" in "Colombo S/S & shopping")
                // is offered too — offered, never taken: the title may say more than the entry does
                $sug = $this->contained($title) ?? $sug;
            }
            $miss['note']         = $sug['note'];
            $miss['suggest_code'] = $sug['code'];
            $miss['suggest_name'] = $sug['name'];
            if ($this->master() && $feeId !== null && $feeId > 0 && isset($this->ix()['old_out'][$feeId])) {
                // schema 39: the record this line was priced from is no longer priced from the old list
                $miss['gone'] = 'Its old Place to Visit record' . ($code !== '' ? ' (' . $code . ')' : '') . ' is no longer priced from the old list.';
                $miss['note'] = $miss['gone'] . ' ' . $miss['note'];
            } elseif (($feeId !== null && $feeId > 0) || $code !== '') {
                // the entry this line was priced from has gone from the master: said, never silently re-guessed
                $miss['gone'] = 'The entry this was priced from' . ($code !== '' ? ' (' . $code . ')' : '') . ' is no longer in the priced list.';
                $miss['note'] = $miss['gone'] . ' ' . $miss['note'];
            }
            return $miss;
        }

        $fee  = $hit['entranceFee'] === null || $hit['entranceFee'] === '' ? null : (float) $hit['entranceFee'];
        $note = null;
        if ($fee !== null && $fee > self::USD_SANITY) {
            // Dalada Maligawa at 2000 and Guide Fee at 4000 are rupees sitting in a USD
            // column. Flag rather than convert: a wrong figure in a quotation is worse
            // than a blank one, and only a person can say which it was meant to be.
            $note = 'The list holds ' . rtrim(rtrim(number_format($fee, 2), '0'), '.') . ' for this, which looks like rupees in the USD column. Check it before quoting.';
        } elseif ($fee === null || $fee === 0.0) {
            $note = 'The list holds no amount for this one yet.';
        }

        return [
            'state'    => 'matched',
            'place'    => false,
            'place_id' => null,
            'fee_id'   => (int) $hit['id'],
            'fee_code' => trim((string) $hit['code']) !== '' ? (string) $hit['code'] : null,
            'name'     => (string) $hit['name'],
            'unit_usd' => $fee,
            'pay_mode' => ((int) ($hit['is_prepaid'] ?? 0) === 1) ? 'prepaid' : (((int) ($hit['direct_payment'] ?? 0) === 1) ? 'direct' : 'in_tour'),
            'note'     => $note,
            'suggest_code' => null,
            'suggest_name' => null,
            'gone'     => null,
        ];
    }

    /** One entry of the priced list by its id, or null. */
    public function entry(?int $feeId): ?array
    {
        $this->load();
        return $feeId !== null && isset($this->byId[$feeId]) ? $this->byId[$feeId] : null;
    }

    /**
     * The whole priced list, for the type-ahead on the panel. Read only, and only the
     * columns the screen shows — picking a name from here is what makes a line match.
     * Schema 39: the places of the master first (with their entrance rate today), then the
     * old list's meals, services and activities.
     *
     * @return array<int, array{name:string, code:string, location:string, usd:?float}>
     */
    public function all(): array
    {
        $this->load();
        $out = [];
        if ($this->master()) {
            foreach ($this->ix()['places'] as $p) {
                if ((int) $p['active'] !== 1 || $p['merged_into'] !== null) {
                    continue;
                }
                $rate = $this->rateOn((int) $p['id'], date('Y-m-d'));
                $k = EntranceMaster::rateKind($rate);
                $out[] = [
                    'place_id'  => (int) $p['id'],
                    'name'      => (string) $p['name'],
                    'code'      => (string) ($p['code'] ?? ''),
                    'location'  => (string) ($p['destination'] ?: ($p['location'] ?? '')),
                    'usd'       => $k === 'charged' && $rate['currency'] === 'USD' ? $rate['adult_rate'] : null,
                    'rate_text' => $k === 'charged' || $k === 'free' ? EntranceMaster::rateText($rate) : 'no rate — held',
                    'jeep'      => (int) $p['jeep'] === 1,
                    'master'    => true,
                ];
            }
        }
        foreach ($this->byName as $r) {
            $out[] = [
                'name'     => (string) $r['name'],
                'code'     => (string) $r['code'],
                'location' => (string) $r['location'],
                'usd'      => $r['entranceFee'] === null ? null : (float) $r['entranceFee'],
            ];
        }
        usort($out, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        return $out;
    }

    /**
     * The nearest name in the list, offered for a person to accept — never applied on its
     * own. Returns the note, and the entry's code and name for a one-click accept.
     *
     * @return array{note:string, code:?string, name:?string}
     */
    private function suggestion(string $title): array
    {
        $key = self::nameKey($title);
        if ($key === '' || !$this->byName) {
            return ['note' => 'Not in the priced list.', 'code' => null, 'name' => null];
        }
        $best = null;
        $bestD = PHP_INT_MAX;
        foreach ($this->byName as $k => $row) {
            $d = levenshtein($key, $k);
            if ($d < $bestD) {
                $bestD = $d;
                $best  = $row;
            }
        }
        $tolerance = max(2, (int) floor(mb_strlen($key) / 5));
        if ($best === null || $bestD > $tolerance || trim((string) $best['code']) === '') {
            return ['note' => 'Not in the priced list.', 'code' => null, 'name' => null];
        }
        return [
            'note' => 'Not in the priced list. Closest entry: ' . $best['name'] . ' (' . $best['code'] . ') — accept it only if it is the same thing.',
            'code' => (string) $best['code'],
            'name' => (string) $best['name'],
        ];
    }

    /** A list entry whose whole name (two words or more) sits inside the title, offered as the closest entry. */
    private function contained(string $title): ?array
    {
        $t = ' ' . self::words($title) . ' ';
        $best = null;
        foreach ($this->byName as $row) {
            $w = self::words((string) $row['name']);
            if ($w === '' || trim((string) $row['code']) === '' || !str_contains($w, ' ') || !str_contains($t, ' ' . $w . ' ')) {
                continue;
            }
            if ($best === null || strlen($w) > strlen(self::words((string) $best['name']))) {
                $best = $row;
            }
        }
        if ($best === null) {
            return null;
        }
        return [
            'note' => 'Not in the priced list. Closest entry: ' . $best['name'] . ' (' . $best['code'] . ') — accept it only if it is the same thing.',
            'code' => (string) $best['code'],
            'name' => (string) $best['name'],
        ];
    }

    /** Whether the closest entry offered carries a positive amount — then the words alone do not make the line no charge. */
    public function suggestedIsPriced(?string $code): bool
    {
        $this->load();
        $code = mb_strtolower(trim((string) $code));
        if ($code === '' || !isset($this->byCode[$code])) {
            return false;
        }
        $fee = $this->byCode[$code]['entranceFee'] ?? null;
        return $fee !== null && $fee !== '' && (float) $fee > 0;
    }

    // =====================================================================
    //  Money
    // =====================================================================

    /**
     * The USD rate to use for a date: the one set for that day, or failing that the most
     * recent one set before it. A tour in the future has no rate of its own, so the latest
     * known rate is the honest answer — and the date it came from is returned with it so
     * the screen can say which day's rate was used.
     *
     * @return array{rate:?float, rate_date:?string}
     */
    public function usdRate(?string $onDate): array
    {
        $key = (string) $onDate;
        if (array_key_exists($key, $this->rateCache)) {
            return $this->rateCache[$key];
        }
        $out = ['rate' => null, 'rate_date' => null];
        try {
            if (!$this->db->query("SHOW TABLES LIKE 'daily_usd_rate'")->getResultArray()) {
                return $this->rateCache[$key] = $out;
            }
            $row = $onDate
                ? $this->db->query('SELECT rate, rate_date FROM daily_usd_rate WHERE rate_date <= ? ORDER BY rate_date DESC LIMIT 1', [$onDate])->getRowArray()
                : null;
            $row ??= $this->db->query('SELECT rate, rate_date FROM daily_usd_rate ORDER BY rate_date DESC LIMIT 1')->getRowArray();
            if ($row && (float) $row['rate'] > 0) {
                $out = ['rate' => (float) $row['rate'], 'rate_date' => (string) $row['rate_date']];
            }
        } catch (\Throwable $e) {
            // no rate table, or it will not read: the service keeps its USD figure and no rupees
        }
        return $this->rateCache[$key] = $out;
    }

    /**
     * Fill in the money on one service row.
     *
     * qty null means "one per person", which is how nearly every entrance is charged;
     * a number means that many of the thing, for the tuk tuks and jeeps that are not
     * charged per head. Multiplying a known unit by a known count is arithmetic, not a
     * guess — but where either is unknown the amount stays empty rather than inventing one.
     *
     * Schema 39, a place of the master: the children at the child rate when the line is for
     * the whole party and the inquiry says who the children are; at a safari park the jeep
     * (per vehicle × the jeeps on the line) is added — no number of jeeps, no amount.
     *
     * @param array<string, mixed> $svc
     * @return array<string, mixed>
     */
    public function price(array $svc, ?int $pax, ?string $dayDate, ?array $party = null): array
    {
        $unit  = isset($svc['unit_usd']) && $svc['unit_usd'] !== null && $svc['unit_usd'] !== '' ? (float) $svc['unit_usd'] : null;
        $qty   = self::effectiveQty($svc);   // tf-attend-r1: a per-person line follows the party unless an attendance override is set
        $count = $qty ?? ($pax !== null && $pax > 0 ? (float) $pax : null);
        // a per-person service is never costed for more people than the inquiry has, whatever
        // quantity a line carries — the party is the ceiling
        if ($count !== null && $pax !== null && $pax > 0 && $count > $pax && self::perPerson($svc['kind'] ?? null)) {
            $count = (float) $pax;
        }

        $svc['amount_usd'] = ($unit !== null && $count !== null) ? round($unit * $count, 2) : null;
        $svc['party_split'] = null;

        // tf-child-r1: a party with children — a line charged per person counts the children only as the Child Fare Review
        // decided: "applicable" at the recorded child rate, "not applicable" at no charge. Until it is decided the children are
        // left out of the figure and the line says so (never the adult rate, never free by default); the quotation waits.
        // tf-safari-r1: at a safari park whose jeep capacity is known, the jeeps the party needs (every passenger takes a seat)
        // when the line does not give a number; the children's age rules of the place for the entrance
        $rules = !empty($svc['place_id']) ? EntranceMaster::rules($this->db, (int) $svc['place_id']) : null;
        $jeeps = $qty;
        if ($jeeps === null && $rules !== null && isset($svc['jeep_usd']) && $svc['jeep_usd'] !== null && $svc['jeep_usd'] !== '') {
            $need = EntranceMaster::jeepsNeeded($pax, $rules['seats']);
            if ($need !== null) {
                $jeeps = (float) $need;
                $svc['jeeps_auto'] = $need;
                $svc['jeep_seats'] = $rules['seats'];
            }
        }
        $reviewed = array_key_exists('child_decision', $svc);
        $kidsN = isset($party['children']) && $party['children'] !== null && $party['children'] !== '' ? (int) $party['children'] : 0;
        $adultsN = isset($party['adults']) && $party['adults'] !== null && $party['adults'] !== '' ? (int) $party['adults'] : null;
        $parkLine = !empty($svc['place_id']) && isset($svc['jeep_usd']) && $svc['jeep_usd'] !== null && $svc['jeep_usd'] !== '';
        $perHead = $parkLine || (!empty($svc['place_id']) || self::followsParty($svc)) && $qty === null;
        if ($reviewed && $kidsN > 0 && $adultsN !== null && $pax !== null && $pax > 0 && $adultsN + $kidsN === $pax && $perHead && $unit !== null && empty($svc['meal_id'])) {
            $dec = (string) $svc['child_decision'];
            $childRate = !empty($svc['place_id']) && isset($svc['child_usd']) && $svc['child_usd'] !== null && $svc['child_usd'] !== '' ? (float) $svc['child_usd'] : null;
            $split = ($dec === 'applicable' && !empty($svc['place_id']))
                ? EntranceMaster::childSplit($unit, $childRate, (array) ($party['child_ages'] ?? []), $kidsN, $rules['age_from'] ?? null, $rules['age_to'] ?? null) : null;
            if ($split !== null && $split['total'] !== null) {
                // tf-safari-r1: each child by the place's age rules — free under the age, the child rate within it, the adult rate above
                $svc['amount_usd'] = round($adultsN * $unit + $split['total'], 2);
                $svc['child_note'] = $adultsN . ' adult(s) × ' . number_format($unit, 2) . ' + ' . implode('; ', $split['parts']) . ' (Child Fare Review: applicable)';
            } elseif ($dec === 'applicable' && $childRate !== null) {
                $svc['amount_usd'] = round($adultsN * $unit + $kidsN * $childRate, 2);
                $svc['child_note'] = $adultsN . ' adult(s) × ' . number_format($unit, 2) . ' + ' . $kidsN . ' child(ren) × ' . number_format($childRate, 2) . ' (Child Fare Review: applicable)';
            } elseif ($dec === 'not_applicable') {
                $svc['amount_usd'] = round($adultsN * $unit, 2);
                $svc['child_note'] = $adultsN . ' adult(s) × ' . number_format($unit, 2) . ' · child: no charge (Child Fare Review)';
            } else {
                $svc['amount_usd'] = round($adultsN * $unit, 2);
                $svc['child_note'] = $adultsN . ' adult(s) × ' . number_format($unit, 2) . ' · ' . $kidsN . ' child(ren): needs checking in the Child Fare Review — not costed yet';
                $svc['child_pending'] = 1;
            }
            $svc['party_split'] = ['adults' => $adultsN, 'children' => $kidsN];
            if ($parkLine) {
                $svc['amount_usd'] = ($jeeps !== null && $jeeps >= 0) ? round($svc['amount_usd'] + (float) $svc['jeep_usd'] * $jeeps, 2) : null;
            }
            $rate = $this->usdRate($dayDate);
            $svc['usd_rate']   = $rate['rate'];
            $svc['rate_date']  = $rate['rate_date'];
            $svc['amount_lkr'] = ($svc['amount_usd'] !== null && $rate['rate'] !== null) ? round($svc['amount_usd'] * $rate['rate'], 2) : null;
            return $svc;
        }

        if (!empty($svc['place_id'])) {
            // a safari park (the line carries the jeep rate): the entrance is for the party, and the number on
            // the line is the jeeps — as the inquiry reader is told ("two jeeps"); no number of jeeps, no amount
            $park = isset($svc['jeep_usd']) && $svc['jeep_usd'] !== null && $svc['jeep_usd'] !== '';
            if ($park) {
                $count = $pax !== null && $pax > 0 ? (float) $pax : null;
            } elseif ($count !== null && $pax !== null && $pax > 0 && $count > $pax) {
                $count = (float) $pax;   // an entrance is a head, whatever kind the line was given
            }
            $svc['amount_usd'] = ($unit !== null && $count !== null) ? round($unit * $count, 2) : null;
            $child    = isset($svc['child_usd']) && $svc['child_usd'] !== null && $svc['child_usd'] !== '' ? (float) $svc['child_usd'] : null;
            $adults   = isset($party['adults']) && $party['adults'] !== null && $party['adults'] !== '' ? (int) $party['adults'] : null;
            $children = isset($party['children']) && $party['children'] !== null && $party['children'] !== '' ? (int) $party['children'] : null;
            if ($unit !== null && $child !== null && $children !== null && $children > 0 && $adults !== null && $pax !== null && $pax > 0
                && $adults + $children === $pax && ($park || $qty === null || $qty >= $pax)) {
                $svc['amount_usd']  = round($adults * $unit + $children * $child, 2);
                $svc['party_split'] = ['adults' => $adults, 'children' => $children];
            }
            if ($park) {
                $svc['amount_usd'] = ($svc['amount_usd'] !== null && $jeeps !== null && $jeeps >= 0) ? round($svc['amount_usd'] + (float) $svc['jeep_usd'] * $jeeps, 2) : null;
            }
        }

        $rate = $this->usdRate($dayDate);
        $svc['usd_rate']   = $rate['rate'];
        $svc['rate_date']  = $rate['rate_date'];
        $svc['amount_lkr'] = ($svc['amount_usd'] !== null && $rate['rate'] !== null)
            ? round($svc['amount_usd'] * $rate['rate'], 2)
            : null;

        return $svc;
    }

    /**
     * Match a service and price it in one step, returning the columns tf_intake_service
     * holds. Anything a person has set by hand is left exactly as it is.
     *
     * @param array<string, mixed> $svc
     * @return array<string, mixed>
     */
    public function apply(array $svc, ?int $pax, ?string $dayDate, ?array $party = null): array
    {
        // schema 40: a lunch or dinner line linked to the Meal Master is priced from the meal, and from nothing else
        if (!empty($svc['meal_id']) && MealMaster::installed($this->db)) {
            return $this->applyMeal($svc, $dayDate);
        }
        if ($this->master()) {
            return $this->applyMaster($svc, $pax, $dayDate, $party);
        }
        $svc = $this->applyList($svc, $pax, $dayDate, $party);
        unset($svc['party_split']);
        return $svc;
    }

    /**
     * A line read against the old list: before the master, and once it is there for the lines
     * outside it (sightseeing, meals, services, activities) — exactly as before.
     */
    private function applyList(array $svc, ?int $pax, ?string $dayDate, ?array $party = null): array   // tf-child-r1: the party, so the Child Fare Review applies to list-priced lines too
    {
        $svc['kind'] = self::kindFor($svc['title'] ?? null, $svc['kind'] ?? null);
        $svc['suggest_code'] = $svc['suggest_code'] ?? null;

        // A free visit from the Free Visits master (schema 29) is on the programme and nowhere
        // else: no list is read for it, no entry, no figure, no amount — whatever its words are.
        if (!empty($svc['free_visit_id'])) {
            $svc['pay_mode']    = 'no_charge';
            $svc['match_state'] = 'no_charge';
            $svc['match_note']  = 'Free visit from the master — on the programme, nothing to pay.';
            $svc['fee_id']      = null;
            $svc['fee_code']    = null;
            $svc['unit_usd']    = null;
            $svc['amount_usd']  = null;
            $svc['amount_lkr']  = null;
            $svc['usd_rate']    = null;
            $svc['rate_date']   = null;
            $svc['checked_at']  = date('Y-m-d H:i:s');
            return $svc;
        }

        $given = (string) ($svc['pay_mode'] ?? 'unknown');   // who pays, as the line held it before the list had its say

        // The list is read every time (the Entrance & Activities section is the one source
        // of these figures, and it follows the master): the entry a line was priced from is
        // kept by its id, so a person's pick stays their pick and only the rate moves.
        $feeId = isset($svc['fee_id']) && $svc['fee_id'] !== null && $svc['fee_id'] !== '' ? (int) $svc['fee_id'] : null;
        $m = $this->match($svc['title'] ?? null, $svc['fee_code'] ?? null, $feeId, null, false);
        $svc['match_state']  = $m['state'];
        $svc['match_note']   = $m['note'];
        $svc['fee_id']       = $m['fee_id'];
        $svc['fee_code']     = $m['fee_code'];
        $svc['unit_usd']     = $m['unit_usd'];
        $svc['suggest_code'] = $m['suggest_code'];
        // the agent's own words win over the master's spelling for what is shown,
        // but a matched line gains the master name so staff recognise the entry
        if ($m['state'] === 'matched' && empty($svc['title'])) {
            $svc['title'] = $m['name'];
        }
        // an arrangement the agent has explicitly declined is not a cost at all
        if ($given === 'unknown' || $given === '') {
            $svc['pay_mode'] = $m['pay_mode'];
        }

        // Three outcomes, and never a forced price (schema 25). Priced: the list holds a
        // positive amount for it. No charge: a person said so (pay mode No charge, which
        // sticks), or the words say it is a sightseeing stop and the list has no positive
        // amount for it (the state alone, re-read every time from the list and the words).
        // Unsure: everything else — said as such, with No charge, the closest entry and the
        // master on offer. A person's own word on who pays is never overruled by the words:
        // a stop they put in the tour price, or one anyone says is paid direct or prepaid,
        // is a thing to price, so it stays unsure until it is priced or marked No charge.
        $mode     = (string) ($svc['pay_mode'] ?? 'unknown');
        $unit     = ($svc['unit_usd'] ?? null) === null || $svc['unit_usd'] === '' ? null : (float) $svc['unit_usd'];
        $priced   = ($svc['match_state'] ?? '') === 'matched' && $unit !== null && $unit > 0;
        $offer    = 'Mark it No charge if nothing is paid, pick the priced entry, or add it under Master Files → Entrance Fee.';
        $decided  = ($svc['origin'] ?? 'ai') === 'human' ? !in_array($given, ['unknown', ''], true) : in_array($given, ['direct', 'prepaid', 'own_arrangement', 'not_arranged'], true);
        if ($given === 'no_charge') {
            $svc['match_state'] = 'no_charge';
            $svc['match_note']  = $priced ? 'No charge, as decided here; the list holds ' . rtrim(rtrim(number_format($unit, 2), '0'), '.') . ' USD for it.' : 'No charge — nothing to pay for this stop.';
        } elseif (!$priced && !$decided && ($w = $this->noChargeWord($svc['title'] ?? null)) !== null
            && in_array((string) ($svc['match_state'] ?? ''), ['not_in_list', 'matched', 'not_checked', 'nothing_named'], true)
            && !$this->suggestedIsPriced($svc['suggest_code'] ?? null)) {
            // a stop the words say costs nothing, and the list does not say otherwise
            $svc['match_state'] = 'no_charge';
            $svc['match_note']  = 'No charge — sightseeing with nothing to pay ("' . $w . '").' . (($svc['fee_id'] ?? null) ? ' The list holds no amount for it either.' : '');
        } elseif (($svc['match_state'] ?? '') === 'not_in_list' && !in_array($mode, ['own_arrangement', 'not_arranged'], true)) {
            $svc['match_note'] = rtrim((string) ($svc['match_note'] ?: 'Not in the priced list.')) . ' ' . $offer;
        } elseif (($svc['match_state'] ?? '') === 'matched' && !$priced && !in_array($mode, ['own_arrangement', 'not_arranged'], true)) {
            $svc['match_note'] = 'The list holds no amount for this one. Mark it No charge if nothing is paid, or set the amount under Master Files → Entrance Fee.';
        }

        $svc = $this->price($svc, $pax, $dayDate, $party);

        // Something the agent has declined, or a stop with nothing to pay, costs nothing,
        // whatever the list says it is worth. The unit price stays visible so a person can
        // see what it would cost if they took it back on, but the amount is cleared —
        // otherwise a lunch the agent explicitly refused sits there with a figure on it and
        // turns up in a quotation.
        if (in_array($svc['pay_mode'] ?? '', self::FREE_MODES, true) || ($svc['match_state'] ?? '') === 'no_charge') {
            $svc['amount_usd'] = null;
            $svc['amount_lkr'] = null;
            $svc['usd_rate']   = null;
            $svc['rate_date']  = null;
        }

        $svc['checked_at'] = date('Y-m-d H:i:s');

        return $svc;
    }

    private ?MealMaster $meals = null;

    private function meals(): MealMaster
    {
        return $this->meals ??= new MealMaster($this->db);
    }

    /**
     * Schema 40: a lunch or dinner line linked to a meal of the Meal Master. Its adults and children are the line's own;
     * the cost is the meal's — (adults × adult rate + children × child rate) + taxes, in the meal's currency, kept on the
     * line (rate_currency, rate_adult, rate_child, tax_cur, amount_cur) — and in USD for the costing (LKR at the day's USD
     * rate). Held, never re-priced from a guess, when the meal is switched off, its supplier is unavailable or gone, or the
     * day is outside its dates: another record of the same restaurant valid that day is only offered (suggest_code).
     *
     * @param array<string, mixed> $svc
     * @return array<string, mixed>
     */
    private function applyMeal(array $svc, ?string $dayDate): array
    {
        foreach (['fee_id', 'fee_code', 'place_id', 'rate_id', 'jeep_rate_id', 'jeep_usd', 'suggest_code', 'unit_usd', 'child_usd', 'rate_currency', 'rate_adult', 'rate_child', 'tax_pct', 'tax_cur', 'amount_cur', 'amount_usd', 'amount_lkr', 'usd_rate', 'rate_date'] as $k) {
            $svc[$k] = null;
        }
        $svc['free_visit_id'] = null;
        $svc['checked_at']    = date('Y-m-d H:i:s');
        if (($svc['pay_mode'] ?? 'unknown') === 'unknown' || $svc['pay_mode'] === '' || $svc['pay_mode'] === 'no_charge') {
            $svc['pay_mode'] = 'in_tour';   // a meal of the master is arranged and paid by the company
        }
        $m = $this->meals()->forPricing((int) $svc['meal_id']);
        if ($m === null) {
            $svc['match_state'] = 'rate_missing';
            $svc['match_note']  = 'The Meal Master record this line was linked to is not there any more — pick the meal again under Meals.';
            return $svc;
        }
        $svc['kind']     = $m['meal_type'];
        $svc['fee_code'] = null;
        $st = MealMaster::standing($m, $dayDate);
        if ($dayDate === null || $dayDate === '') {
            $svc['match_state'] = 'rate_missing';
            $svc['match_note']  = self::note('Meal Master ' . $m['code'] . ': the day has no date, so the meal\'s validity cannot be read — set the arrival date.');
            return $svc;
        }
        if ($st['status'] !== 'valid') {
            $next = $st['status'] === 'future' || $st['status'] === 'ended' ? $this->meals()->successor($m, $dayDate) : null;
            $svc['suggest_code'] = $next['code'] ?? null;
            $svc['match_state']  = 'rate_missing';
            $svc['match_note']   = self::note('Meal Master ' . $m['code'] . ' (' . $m['restaurant'] . ') — held: ' . lcfirst($st['text']) . ($st['status'] === 'ended' || $st['status'] === 'future' ? ', not ' . MealMaster::dateText($dayDate) : '') . '.',
                $next ? 'The same restaurant has ' . $next['code'] . ' for that day — pick it under Meals.' : 'Pick another meal for the day under Meals. Nothing is guessed.');
            return $svc;
        }
        $a = $svc['qty_adult'] ?? null;
        $c = $svc['qty_child'] ?? null;
        $a = $a === null || $a === '' ? null : max(0, (int) $a);
        $c = $c === null || $c === '' ? null : max(0, (int) $c);
        $tax = (float) $m['tax_pct'];
        $svc['rate_currency'] = $m['currency'];
        $svc['rate_adult']    = $m['adult_rate'];
        $svc['rate_child']    = $m['child_rate'] ?? $m['adult_rate'];
        $svc['tax_pct']       = $m['tax_included'] ? null : $tax;
        if ($a === null && $c === null) {
            $svc['match_state'] = 'matched';
            $svc['match_note']  = self::note('Meal Master ' . $m['code'] . ': say how many adults and children the ' . $m['meal_type'] . ' is for (under Meals).');
            return $svc;
        }
        $a ??= 0;
        $c ??= 0;
        if ($a + $c < 1) {
            $svc['match_state'] = 'matched';
            $svc['match_note']  = self::note('Meal Master ' . $m['code'] . ': the line is for nobody — give the adults and children under Meals.');
            return $svc;
        }
        // tf-child-r1: children at a meal only as the Child Fare Review decided — applicable at the meal's own child price,
        // otherwise not charged (not applicable), or left out until it is decided (the quotation waits for the review)
        if (array_key_exists('child_decision', $svc) && $c > 0) {
            $dec = (string) $svc['child_decision'];
            if (!($dec === 'applicable' && $m['child_rate'] !== null && $m['child_rate'] !== '')) {
                $svc['child_note'] = $dec === 'not_applicable' ? 'child: no charge (Child Fare Review)' : $c . ' child(ren): needs checking in the Child Fare Review — not costed yet';
                if ($dec !== 'not_applicable') {
                    $svc['child_pending'] = 1;
                }
                $c = 0;
            }
        }
        $cost = MealMaster::cost($m, $a, $c);
        $svc['tax_cur']    = $cost['tax'];
        $svc['amount_cur'] = $cost['total'];
        $rate = $this->usdRate($dayDate);
        $svc['usd_rate']  = $rate['rate'];
        $svc['rate_date'] = $rate['rate_date'];
        if ($m['currency'] === 'USD') {
            $svc['unit_usd']   = $cost['adult_incl'];
            $svc['child_usd']  = $cost['child_incl'];
            $svc['amount_usd'] = $cost['total'];
            $svc['amount_lkr'] = $rate['rate'] !== null ? round($cost['total'] * $rate['rate'], 2) : null;
        } elseif ($rate['rate'] !== null && $rate['rate'] > 0) {
            $svc['unit_usd']   = round($cost['adult_incl'] / $rate['rate'], 2);
            $svc['child_usd']  = round($cost['child_incl'] / $rate['rate'], 2);
            $svc['amount_usd'] = round($cost['total'] / $rate['rate'], 2);
            $svc['amount_lkr'] = $cost['total'];
        } else {
            $svc['match_state'] = 'matched';
            $svc['match_note']  = self::note('Meal Master ' . $m['code'] . ': LKR ' . number_format($cost['total'], 2) . ', but no USD rate is on file to put it in USD — enter the Daily USD Rate.');
            return $svc;
        }
        $fmt = static fn (float $v) => number_format($v, 2);
        $svc['match_state'] = 'matched';
        $svc['match_note']  = self::note('Meal Master ' . $m['code'] . ': ' . $a . ' adult' . ($a === 1 ? '' : 's') . ' × ' . $m['currency'] . ' ' . $fmt($m['adult_rate'])
            . ($c ? ' + ' . $c . ' child' . ($c === 1 ? '' : 'ren') . ' × ' . $m['currency'] . ' ' . $fmt($cost['child_rate']) : '')
            . ($cost['tax'] > 0 ? ' + taxes ' . rtrim(rtrim(number_format($tax, 2), '0'), '.') . '% ' . $m['currency'] . ' ' . $fmt($cost['tax']) : ($m['tax_included'] ? ' (taxes included)' : ''))
            . ' = ' . $m['currency'] . ' ' . $fmt($cost['total']) . ($m['currency'] !== 'USD' ? ' (USD ' . $fmt((float) $svc['amount_usd']) . ' at ' . $fmt((float) $rate['rate']) . ')' : '') . '.');
        if (in_array($svc['pay_mode'] ?? '', self::FREE_MODES, true)) {
            $svc['amount_usd'] = null;
            $svc['amount_lkr'] = null;
        }
        return $svc;
    }

    /** Clear the money and finish a line that is held or not costed. */
    private function unpriced(array $svc, ?int $pax, ?string $dayDate, ?array $party): array
    {
        $svc = $this->price($svc, $pax, $dayDate, $party);
        $svc['amount_usd'] = null;
        $svc['amount_lkr'] = null;
        $svc['usd_rate']   = null;
        $svc['rate_date']  = null;
        unset($svc['party_split']);
        $svc['checked_at'] = date('Y-m-d H:i:s');
        return $svc;
    }

    /**
     * Schema 39: one line read with the Place to Visit / Entrance Fee Master installed.
     *
     *   a place of the master        the entrance a head for the day (and at a safari park the
     *                                jeep per vehicle): charged / Free / no rate → held
     *   an entrance or a safari line not in the master                  → held (never free)
     *   a line naming a meal, service or activity the old list prices   → the old list, as before
     *   ordinary sightseeing not priced by the old list                 → no entrance charge
     *   any other line                                                  → the old list, as before
     *
     * Nothing is guessed: a place spelt like the line is only offered, on a held line.
     *
     * @param array<string, mixed> $svc
     * @return array<string, mixed>
     */
    private function applyMaster(array $svc, ?int $pax, ?string $dayDate, ?array $party): array
    {
        $svc['kind'] = self::kindFor($svc['title'] ?? null, $svc['kind'] ?? null);
        foreach (['rate_id', 'child_usd', 'rate_currency', 'rate_adult', 'rate_child', 'jeep_rate_id', 'jeep_usd'] as $k) {
            $svc[$k] = null;
        }
        $date = $dayDate ?: date('Y-m-d');

        if (!empty($svc['free_visit_id'])) {
            // a free visit from the Free Visits master (which refuses a name the Place to Visit master holds)
            $svc['place_id'] = null;
            $svc = $this->applyList($svc, $pax, $dayDate, $party);
            unset($svc['party_split']);
            return $svc;
        }

        $given   = (string) ($svc['pay_mode'] ?? 'unknown');
        $feeId   = isset($svc['fee_id']) && $svc['fee_id'] !== null && $svc['fee_id'] !== '' ? (int) $svc['fee_id'] : null;
        $placeId = isset($svc['place_id']) && $svc['place_id'] !== null && $svc['place_id'] !== '' ? (int) $svc['place_id'] : null;
        $scope   = in_array($svc['kind'], EntranceMaster::SCOPE_KINDS, true);
        $this->load();
        $f = $this->findPlace($svc['title'] ?? null, $svc['fee_code'] ?? null, $feeId, $placeId, !in_array($svc['kind'], self::SERVICE_ONLY_KINDS, true));

        if ($f['place'] === null && $f['off'] !== null) {
            // tied to a place (picked, or priced from its old record) that is now out of use: held, never freed or re-guessed
            $off = $f['off'];
            $offCode = trim((string) ($off['code'] ?? ''));
            $svc['place_id']     = (int) $off['id'];
            $svc['fee_id']       = null;
            $svc['fee_code']     = $offCode !== '' ? $offCode : null;
            $svc['unit_usd']     = null;
            $svc['suggest_code'] = null;
            $svc['match_state']  = 'rate_missing';
            $svc['match_note']   = self::note('"' . trim((string) $off['name']) . '"' . ($offCode !== '' ? ' (' . $offCode . ')' : '') . ' is switched off in the Place to Visit / Entrance Fee Master — held: put it back in use there, or pick the place this line is. Nothing is guessed.');
            return $this->unpriced($svc, $pax, $dayDate, $party);
        }

        if ($f['place'] === null) {
            $svc['place_id'] = null;
            // a meal, a service or an activity the old list prices by this line's record, code or name: the old list, as before —
            // for an entrance or a safari line only a record the approved list left with it as one (never a record added there since)
            $m = $this->match($svc['title'] ?? null, $svc['fee_code'] ?? null, $feeId, null, false);
            if ($m['state'] === 'matched' && empty($m['place']) && (!$scope || isset($this->ix()['services'][(int) $m['fee_id']]))) {
                $svc = $this->applyList($svc, $pax, $dayDate, $party);
                unset($svc['party_split']);
                return $svc;
            }
            if ($scope && !in_array($given, ['own_arrangement', 'not_arranged'], true)) {
                // the rule: an entrance or a safari must be a place of the master — not there, it is held, never free
                $np = $this->nearestPlace((string) ($svc['title'] ?? ''));
                $svc['fee_id']       = null;
                $svc['fee_code']     = null;
                $svc['unit_usd']     = null;
                $svc['suggest_code'] = $np['code'];
                $svc['match_state']  = 'not_in_master';
                $svc['match_note']   = self::note($svc['kind'] === 'safari'
                    ? 'Not in the Place to Visit / Entrance Fee Master — a safari is held until it is: pick the park, or add it there (entrance and jeep rates). Not a park safari? Change the kind.'
                    : 'Not in the Place to Visit / Entrance Fee Master — an entrance is held until it is: pick the place, or add it there (with its rate, or as Free). Not a paid entrance? Change the kind.',
                    $given === 'no_charge' ? '"No charge" does not free it: add it to the master as Free.' : null,
                    $np['name'] !== null ? 'Closest: ' . $np['name'] . ($np['code'] ? ' (' . $np['code'] . ')' : '') . '.' : null);
                return $this->unpriced($svc, $pax, $dayDate, $party);
            }
            // ordinary sightseeing, or any other line: outside the master — never charged or held by it; read as before
            // (the no-charge words and the Free Visits master make a stop no charge; anything else a person decides)
            $svc = $this->applyList($svc, $pax, $dayDate, $party);
            unset($svc['party_split']);
            if (($svc['match_state'] ?? '') === 'not_in_list' && empty($svc['suggest_code']) && $svc['kind'] === 'visit') {
                // a line for a person to decide that names a paid place of the master ("Sigiriya Rock Fortress" read as
                // sightseeing): the place is offered beside No charge, so its entrance is not dropped by a click — never applied
                $np = $this->nearestPlace((string) ($svc['title'] ?? ''));
                if ($np['code'] !== null) {
                    $svc['suggest_code'] = $np['code'];
                    $svc['match_note']   = self::note((string) ($svc['match_note'] ?? ''), 'It names a paid place of the master: ' . $np['name'] . ' (' . $np['code'] . ') — use it if the entrance is paid.');
                }
            }
            return $svc;
        }

        $p     = $f['place'];
        $code  = trim((string) ($p['code'] ?? ''));
        $label = trim((string) $p['name']) . ($code !== '' ? ' (' . $code . ')' : '');
        $svc['place_id']     = (int) $p['id'];
        $svc['fee_id']       = null;
        $svc['fee_code']     = $code !== '' ? $code : null;
        $svc['unit_usd']     = null;
        $svc['suggest_code'] = null;
        $svc['match_note']   = null;
        $overruled = false;
        if ($given === 'unknown' || $given === '') {
            $svc['pay_mode'] = (string) $p['pay_mode'];
        } elseif ($given === 'no_charge') {
            // a "no charge" said on a place of the master: the master decides, not the line
            $svc['pay_mode'] = (string) $p['pay_mode'];
            $overruled = true;
        }

        $toUsd = function (array $rate) use ($dayDate): ?float {
            if ($rate['currency'] === 'USD') {
                return 1.0;
            }
            $conv = $this->usdRate($dayDate);
            return $conv['rate'] !== null && $conv['rate'] > 0 ? 1 / $conv['rate'] : null;
        };
        $held  = [];
        $notes = [];
        $rate  = $this->rateOn((int) $p['id'], $date, 'entrance');
        $kind  = EntranceMaster::rateKind($rate);
        if ($kind === 'none' || $kind === 'zero') {
            $held[] = $kind === 'zero' ? 'its entrance rate for ' . EntranceMaster::fmtDate($date) . ' is 0.00 without the Free mark' : 'no entrance rate for ' . EntranceMaster::fmtDate($date);
        } else {
            $svc['rate_id']       = $rate['id'];
            $svc['rate_currency'] = $rate['currency'];
            $svc['rate_adult']    = $rate['adult_rate'];
            $svc['rate_child']    = $rate['child_rate'];
            if ($kind === 'free') {
                $svc['unit_usd'] = 0.0;
            } elseif (($k = $toUsd($rate)) === null) {
                $held[] = 'the entrance is ' . EntranceMaster::rateText($rate) . ' but there is no USD rate for ' . EntranceMaster::fmtDate($date) . ' to turn it into dollars';
            } else {
                $svc['unit_usd']  = round($rate['adult_rate'] * $k, 2);
                $svc['child_usd'] = $rate['child_rate'] !== null ? round($rate['child_rate'] * $k, 2) : null;
                if ($rate['currency'] !== 'USD') {
                    $conv = $this->usdRate($dayDate);
                    $notes[] = 'Entrance ' . EntranceMaster::rateText($rate) . ' at ' . number_format((float) $conv['rate'], 2) . ' (' . EntranceMaster::fmtDate($conv['rate_date']) . ').';
                }
            }
        }
        $jeepKind = null;
        if ((int) $p['jeep'] === 1) {
            $jr = $this->rateOn((int) $p['id'], $date, 'jeep');
            $jeepKind = EntranceMaster::rateKind($jr);
            if ($jeepKind === 'none' || $jeepKind === 'zero') {
                $held[] = $jeepKind === 'zero' ? 'its jeep rate for ' . EntranceMaster::fmtDate($date) . ' is 0.00 without the Free mark' : 'no jeep rate for ' . EntranceMaster::fmtDate($date);
            } else {
                $svc['jeep_rate_id'] = $jr['id'];
                if ($jeepKind === 'free') {
                    $svc['jeep_usd'] = 0.0;
                } elseif (($k = $toUsd($jr)) === null) {
                    $held[] = 'the jeep is ' . EntranceMaster::rateText($jr) . ' but there is no USD rate for ' . EntranceMaster::fmtDate($date) . ' to turn it into dollars';
                } else {
                    $svc['jeep_usd'] = round($jr['adult_rate'] * $k, 2);
                    if ($jr['currency'] !== 'USD') {
                        $conv = $this->usdRate($dayDate);
                        $notes[] = 'Jeep ' . EntranceMaster::rateText($jr) . ' at ' . number_format((float) $conv['rate'], 2) . ' (' . EntranceMaster::fmtDate($conv['rate_date']) . ').';
                    }
                }
            }
        }

        if ($held) {
            $svc['match_state'] = 'rate_missing';
            $svc['unit_usd']    = null;
            $svc['child_usd']   = null;
            $svc['jeep_usd']    = null;
            $svc['match_note']  = self::note('In the Place to Visit / Entrance Fee Master as ' . $label . ' but ' . implode('; ', $held) . ' — enter the rate there, or mark it Free. Nothing is guessed.',
                $overruled ? '"No charge" does not apply to a place of the master.' : null);
            return $this->unpriced($svc, $pax, $dayDate, $party);
        }
        $free = $kind === 'free' && ($jeepKind === null || $jeepKind === 'free');
        if ($free) {
            $svc['match_state'] = 'no_charge';
            $svc['match_note']  = self::note('Free in the Place to Visit / Entrance Fee Master (' . $label . ').');
            return $this->unpriced($svc, $pax, $dayDate, $party);
        }

        $svc['match_state'] = 'matched';
        if ($overruled) {
            $notes[] = 'Charged from the master — "No charge" does not apply to a place the master prices. If the clients hold their own tickets, mark it Arranged by the agent.';
        }
        $svc = $this->price($svc, $pax, $dayDate, $party);
        if (in_array($svc['pay_mode'] ?? '', self::FREE_MODES, true)) {
            $svc['amount_usd'] = null;
            $svc['amount_lkr'] = null;
            $svc['usd_rate']   = null;
            $svc['rate_date']  = null;
        }
        // the amounts in words: the entrance (adults and children), and at a safari park the jeep, each shown
        $ps    = $svc['party_split'] ?? null;
        $qty   = self::effectiveQty($svc);
        $num   = static fn (float $x): string => rtrim(rtrim(number_format($x, 2), '0'), '.');
        if ($ps !== null && !empty($svc['child_note'])) {
            // tf-child-r1: the children as the Child Fare Review decided (or still open) — never "at 0.00" when nothing was decided
            $words = 'Entrance (USD): ' . $svc['child_note'] . '.';
        } elseif ($ps !== null) {
            $words = 'Entrance: ' . $ps['adults'] . ' adult(s) at ' . number_format((float) $svc['unit_usd'], 2) . ', ' . $ps['children'] . ' child(ren) at ' . number_format((float) $svc['child_usd'], 2) . ' USD.';
        } elseif ($kind === 'free') {
            $words = 'Entrance: Free.';
        } else {
            $heads = $jeepKind !== null ? ($pax ?: null) : ($qty !== null ? ($pax ? min($qty, (float) $pax) : $qty) : ($pax ?: null));
            $words = 'Entrance: USD ' . number_format((float) $svc['unit_usd'], 2) . ' a head' . ($heads ? ' × ' . $num((float) $heads) : '') . '.';
        }
        if ($jeepKind !== null && $qty === null && isset($svc['jeeps_auto'])) {
            $qty = (float) $svc['jeeps_auto'];   // tf-safari-r1: the jeeps the party needs, from the jeep capacity
            $notes[] = 'Jeeps: ' . (int) $pax . ' passenger(s) ÷ ' . (int) $svc['jeep_seats'] . ' seats = ' . (int) $qty . ' jeep(s).';
        }
        if ($jeepKind !== null) {
            if ($qty === null) {
                $words .= ' Jeep: ' . ($jeepKind === 'free' ? 'Free' : 'USD ' . number_format((float) $svc['jeep_usd'], 2) . ' per vehicle') . ' — give the number of jeeps on the line (How many; 0 if this line needs none). No amount until then.';
            } else {
                $words .= ' Jeep: ' . ($jeepKind === 'free' ? 'Free' : 'USD ' . number_format((float) $svc['jeep_usd'], 2) . ' × ' . $num($qty) . ' jeep(s) = ' . number_format((float) $svc['jeep_usd'] * $qty, 2)) . '.';
            }
        }
        $svc['match_note'] = self::note($words, ...$notes);
        unset($svc['party_split']);
        $svc['checked_at'] = date('Y-m-d H:i:s');
        return $svc;
    }
    /** A line's note (the column holds 255 characters): the first part always, each further part only while it fits. */
    private static function note(?string ...$parts): string
    {
        $out = '';
        foreach ($parts as $i => $p) {
            $p = trim((string) $p);
            if ($p === '') {
                continue;
            }
            $try = $out === '' ? $p : $out . ' ' . $p;
            if ($i === 0 || mb_strlen($try) <= 255) {
                $out = $try;
            }
        }
        return mb_substr($out, 0, 255);
    }

    // =====================================================================
    //  What a line is, for the section and for the costing (one reading, used by both)
    // =====================================================================

    /** A line with nothing to pay: declined by the agent, not happening, or a stop at no charge. */
    public static function isFree(array $s): bool
    {
        $mode  = (string) ($s['pay_mode'] ?? '');
        $state = (string) ($s['match_state'] ?? '');
        // schema 39: "No charge" does not free an entrance or a safari that is not in the master
        return in_array($mode, ['own_arrangement', 'not_arranged'], true) || ($mode === 'no_charge' && $state !== 'not_in_master') || $state === 'no_charge';
    }

    /**
     * What one line of the Entrance & Activities section is, from the columns it holds:
     *
     *   priced     in the list with a positive amount — costed, goes to the quotation
     *   no_charge  a stop with nothing to pay — on the programme, nothing costed
     *   declined   the agent arranges it, or it is not happening — nothing costed
     *   direct     paid by the clients on the day — shown, not costed
     *   unsure     not in the priced list — stops the quotation until a person decides
     *   no_amount  in the list, but the list holds no amount — stops the quotation
     *   rupees     the list's figure looks like rupees in the USD column — stops the quotation
     *   rate_missing   (schema 39) a place of the Entrance Fee Master with no rate for the day — stops the quotation
     *   not_in_master  (schema 39) an entrance or a safari that is not in the master — stops the quotation;
     *                  "No charge" does not free it (the master does, with the place marked Free)
     *
     * @return array{state:string, costed:bool, blocks:bool, why:?string}
     */
    public static function classify(array $s): array
    {
        $mode  = (string) ($s['pay_mode'] ?? 'unknown');
        $state = (string) ($s['match_state'] ?? '');
        if (in_array($mode, ['own_arrangement', 'not_arranged'], true)) {
            return ['state' => 'declined', 'costed' => false, 'blocks' => false, 'why' => $mode === 'own_arrangement' ? 'Arranged by the agent.' : 'Not happening.'];
        }
        if (!empty($s['free_visit_id'])) {
            return ['state' => 'no_charge', 'costed' => false, 'blocks' => false, 'why' => 'Free visit from the master — nothing to pay.'];
        }
        // schema 39: an entrance or a safari that is not in the Place to Visit / Entrance Fee Master — held, never free
        if ($state === 'not_in_master' && $mode !== 'direct') {
            return ['state' => 'not_in_master', 'costed' => false, 'blocks' => true, 'why' => 'An entrance or safari not in the Place to Visit / Entrance Fee Master.'];
        }
        if ($mode === 'no_charge' || $state === 'no_charge') {
            return ['state' => 'no_charge', 'costed' => false, 'blocks' => false, 'why' => !empty($s['place_id']) ? 'Free in the Place to Visit / Entrance Fee Master.' : 'No charge — sightseeing, nothing to pay.'];
        }
        if ($mode === 'direct') {
            return ['state' => 'direct', 'costed' => false, 'blocks' => false, 'why' => 'Paid direct by the clients on the day; shown, not costed.'];
        }
        // schema 39: a place of the master with no rate for the day — held until the master has one
        if ($state === 'rate_missing') {
            return ['state' => 'rate_missing', 'costed' => false, 'blocks' => true, 'why' => !empty($s['meal_id'])
                ? 'From the Meal Master but held: the meal is switched off, out of its dates, or its supplier is unavailable.'
                : 'In the Place to Visit / Entrance Fee Master but held: no rate for the day, or the place is out of use.'];
        }
        if ($state === 'not_in_list' || $state === 'nothing_named' || $state === 'not_checked') {
            return ['state' => 'unsure', 'costed' => false, 'blocks' => true, 'why' => 'Not in the priced list.'];
        }
        if (($s['amount_usd'] ?? null) === null || $s['amount_usd'] === '') {
            return ['state' => 'no_amount', 'costed' => false, 'blocks' => true, 'why' => 'No amount.'];
        }
        if (empty($s['place_id']) && empty($s['meal_id']) && str_contains((string) ($s['match_note'] ?? ''), 'rupees')) {
            return ['state' => 'rupees', 'costed' => false, 'blocks' => true, 'why' => 'The list\'s figure looks like rupees in the USD column.'];
        }
        return ['state' => 'priced', 'costed' => true, 'blocks' => false, 'why' => null];
    }
}
