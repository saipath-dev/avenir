<?php

namespace App\Libraries\TourFlow;

/**
 * An Edit Request read against the inquiry (schema 33).
 *
 * A colleague pastes the words that are wrong or have changed — a line of the agent's email,
 * or a value as the inquiry shows it — and types what they should say now. This works out
 * which working values those words are, and what each becomes. It reads; it never writes.
 *
 * It works by matching, never by guessing:
 *  - the two texts are compared word by word, so only the words that actually changed count;
 *  - a changed word belongs to a working value only when that value's own text (or the email
 *    words the reader took it from) is exactly where the change is;
 *  - the new value is read from the new words with the same rules the paste check uses: a date
 *    needs its year, a hotel or a visit must be exactly one record of its master, a count must
 *    say what it counts;
 *  - where any of that is not certain — the words appear twice in the email for two different
 *    nights, a date has no year, a hotel name is only close to one in Hotel Rates, a change
 *    belongs to nothing on the inquiry — it asks, with the choices it can see, and changes
 *    nothing until it is answered.
 * Prices, bata, the markup, kilometres and the agent are never changed here: they belong to
 * their masters, to Management or to their own screens, and the answer says where.
 *
 * The result is a list of intents (one working value each, current → new, and how to apply it
 * through the same service call a person's edit uses), the questions still open, and refusals.
 */
final class EditMatcher
{
    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9, 'sept' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];
    private const MONTH_RX = '(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|june?|july?|aug(?:ust)?|sept(?:ember)?|sep|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)\.?';
    private const NUM_WORDS = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10, 'eleven' => 11, 'twelve' => 12];
    private const ARRIVE = '/\b(?:arriv\w*|arr\.?|check[\s-]?in|in\s*bound|eta|start\w*|from)\b/iu';
    private const DEPART = '/\b(?:depart\w*|dep\.?|check[\s-]?out|out\s*bound|etd|leav\w*|return\w*|until|till|to)\b/iu';
    /** Words that say a change is about money, the markup, kilometres or the agent: never an Edit Request's to make. */
    private const MONEY = '/\b(?:usd|lkr|rs\.?|us\$|rate|rates|price|prices|priced|cost|costs|tariff|fee|fees|bata|markup|mark-up|margin|profit|discount|commission)\b|\$|%/iu';
    private const KM    = '/\b(?:km|kms|kilomet\w*|mileage)\b/iu';

    public const FIELD_LABELS = [
        'arrival_date' => 'Arrival date', 'departure_date' => 'Departure date', 'arrival_flight' => 'Arrival flight', 'departure_flight' => 'Departure flight',
        'pax_total' => 'Party size', 'pax_adults' => 'Adults', 'pax_children' => 'Children', 'meal_basis' => 'Meal plan (the whole tour)',
        'hotel_class' => 'Hotel category', 'tour_name' => 'Group / tour name', 'agent_reference' => 'Agent\'s reference', 'rooms_note' => 'Rooms note',
    ];

    private array $it;
    private array $raw;
    private array $lk;
    private array $answers;
    private string $A;
    private string $B;
    /** @var array<int, array{0:string,1:int,2:int}> */
    private array $ta = [];
    private array $tb = [];
    /** @var array<int, array{0:string,1:int,2:int,3:int,4:int}> [type, aStart, aEnd, bStart, bEnd] */
    private array $segs = [];
    private array $emailTok = [];
    private string $emailText = '';
    private string $located = 'none';
    /** @var array<int, int> start token (in the email) of every place the pasted words were found */
    private array $occs = [];
    private ?int $occ = null;
    /** @var array<int, int> the places in the email the change is read at (more than one when "all of them" was chosen) */
    private array $occList = [];
    /** @var array<int, array{0:int,1:int}> day_no => [first token, last token + 1] of that day's part of the email */
    private array $windows = [];
    /** @var array<int, bool> the nights the chosen place in the email belongs to */
    private array $nightsHere = [];

    private array $intents = [];
    private array $questions = [];
    private array $refusals = [];
    private array $notes = [];
    /** @var array<int, bool> change segments some working value explains */
    private array $claimed = [];
    /** @var array<int, bool> change segments refused as figures (prices, bata, kilometres): read by nothing else */
    private array $refused = [];
    private array $newDates = [];
    private ?int $newNightsSaid = null;

    private function __construct(array $it, string $a, string $b, array $answers, array $lookups)
    {
        $this->it      = $it;
        $this->raw     = is_array($it['extract_raw'] ?? null) ? $it['extract_raw'] : (json_decode((string) ($it['extract_json'] ?? ''), true) ?: []);
        $this->answers = $answers;
        $this->lk      = $lookups;
        $this->A       = self::clean($a);
        $this->B       = self::clean($b);
    }

    /**
     * @return array{intents:array, questions:array, refusals:array, notes:array, located:string, occurrences:int}
     */
    public static function read(array $it, string $pasted, string $changeTo, array $answers, array $lookups): array
    {
        $m = new self($it, $pasted, $changeTo, $answers, $lookups);
        $m->run();
        return [
            'intents'     => array_values($m->intents),
            'questions'   => array_values($m->questions),
            'refusals'    => array_values(array_unique($m->refusals)),
            'notes'       => array_values(array_unique($m->notes)),
            'located'     => $m->located,
            'occurrences' => count($m->occs),
        ];
    }

    /** The last $chars characters of $s before byte $off — safe for Unicode, where a byte cut would break a letter. */
    private static function before(string $s, int $off, int $chars): string
    {
        return mb_substr(substr($s, 0, max(0, $off)), -$chars);
    }

    private static function clean(string $s): string
    {
        $s = str_replace(["\r\n", "\r", "\u{00A0}", "\u{3000}"], ["\n", "\n", ' ', ' '], $s);
        return trim($s);
    }

    // =====================================================================
    //  The words: tokens and the word-by-word comparison
    // =====================================================================

    /** @return array<int, array{0:string,1:int,2:int}> [normalised word, byte start, byte end] */
    public static function tokens(string $s): array
    {
        $out = [];
        if ($s === '' || !preg_match_all('/[\p{L}\p{N}]+|[^\s\p{L}\p{N}]/u', $s, $m, PREG_OFFSET_CAPTURE)) {
            return $out;
        }
        foreach ($m[0] as [$t, $off]) {
            $n = self::normWord($t);
            if (in_array($n, ['"', "'"], true)) {
                continue;   // quote marks are rewritten by mail clients; they are not words
            }
            $out[] = [$n, $off, $off + strlen($t)];
        }
        return $out;
    }

    private static function normWord(string $t): string
    {
        $t = str_replace(["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{2013}", "\u{2014}", "\u{2212}", "\u{2011}", "\u{2012}", "\u{FF5E}", '~'], ["'", "'", '"', '"', '-', '-', '-', '-', '-', '-', '-'], $t);
        return mb_strtolower($t);
    }

    /** The normalised words of a text, as one string (for "is this value in there"). */
    public static function normText(string $s): string
    {
        return implode(' ', array_column(self::tokens($s), 0));
    }

    /**
     * Word-by-word comparison of the two boxes: runs that are the same, and the changes between them.
     * @return array<int, array{0:string,1:int,2:int,3:int,4:int}>
     */
    public static function align(array $a, array $b): array
    {
        $n = count($a);
        $m = count($b);
        $L = [];
        for ($i = $n; $i >= 0; $i--) {
            for ($j = $m; $j >= 0; $j--) {
                if ($i === $n || $j === $m) {
                    $L[$i][$j] = 0;
                } elseif ($a[$i][0] === $b[$j][0]) {
                    $L[$i][$j] = $L[$i + 1][$j + 1] + 1;
                } else {
                    $L[$i][$j] = max($L[$i + 1][$j], $L[$i][$j + 1]);
                }
            }
        }
        $segs = [];
        $i = 0;
        $j = 0;
        $ci = 0;
        $cj = 0;
        $eqStart = null;
        while ($i < $n && $j < $m) {
            if ($a[$i][0] === $b[$j][0] && $L[$i][$j] === $L[$i + 1][$j + 1] + 1) {
                if ($ci < $i || $cj < $j) {
                    $segs[] = ['ch', $ci, $i, $cj, $j];
                }
                $segs[] = ['eq', $i, $i + 1, $j, $j + 1];
                $i++;
                $j++;
                $ci = $i;
                $cj = $j;
            } elseif ($L[$i + 1][$j] >= $L[$i][$j + 1]) {
                $i++;
            } else {
                $j++;
            }
        }
        if ($ci < $n || $cj < $m) {
            $segs[] = ['ch', $ci, $n, $cj, $m];
        }
        // merge neighbouring same-word runs
        $out = [];
        foreach ($segs as $s) {
            $k = count($out) - 1;
            if ($s[0] === 'eq' && $k >= 0 && $out[$k][0] === 'eq' && $out[$k][2] === $s[1] && $out[$k][4] === $s[3]) {
                $out[$k][2] = $s[2];
                $out[$k][4] = $s[4];
            } else {
                $out[] = $s;
            }
        }
        return $out;
    }

    /** Where a sequence of words appears inside another: every start index. */
    private static function find(array $hay, array $needle): array
    {
        $n = count($needle);
        $h = count($hay);
        $out = [];
        if ($n === 0 || $n > $h) {
            return $out;
        }
        $first = $needle[0][0];
        for ($i = 0; $i <= $h - $n; $i++) {
            if ($hay[$i][0] !== $first) {
                continue;
            }
            $ok = true;
            for ($k = 1; $k < $n; $k++) {
                if ($hay[$i + $k][0] !== $needle[$k][0]) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $out[] = $i;
            }
        }
        return $out;
    }

    /** Does the comparison change anything inside A's words [s, e)? Containers also count an insertion at either edge. */
    private function changedIn(int $s, int $e, bool $container = false): array
    {
        $hit = [];
        foreach ($this->segs as $k => [$type, $aS, $aE, $bS, $bE]) {
            if ($type !== 'ch' || !empty($this->refused[$k])) {
                continue;
            }
            if ($aE > $aS) {
                if ($aS < $e && $aE > $s) {
                    $hit[] = $k;
                }
            } elseif ($container ? ($aS >= $s && $aS <= $e) : ($aS > $s && $aS < $e)) {
                $hit[] = $k;
            }
        }
        return $hit;
    }

    /** The new words that stand where A's words [s, e) stood: [bStart, bEnd], or [null, null] when they were taken out. */
    private function bRange(int $s, int $e, bool $container = false): array
    {
        $bs = null;
        $be = null;
        foreach ($this->segs as [$type, $aS, $aE, $bS, $bE]) {
            if ($type === 'eq') {
                $lo = max($s, $aS);
                $hi = min($e, $aE);
                if ($lo < $hi) {
                    $x = $bS + ($lo - $aS);
                    $y = $bS + ($hi - $aS);
                    $bs = $bs === null ? $x : min($bs, $x);
                    $be = $be === null ? $y : max($be, $y);
                }
                continue;
            }
            $inside = $aE > $aS ? ($aS < $e && $aE > $s) : ($container ? ($aS >= $s && $aS <= $e) : ($aS > $s && $aS < $e));
            if ($inside && $bE > $bS) {
                $bs = $bs === null ? $bS : min($bs, $bS);
                $be = $be === null ? $bE : max($be, $bE);
            }
        }
        return [$bs, $be];
    }

    private function textA(int $s, int $e): string
    {
        return ($s < $e && isset($this->ta[$s], $this->ta[$e - 1])) ? substr($this->A, $this->ta[$s][1], $this->ta[$e - 1][2] - $this->ta[$s][1]) : '';
    }

    private function textB(?int $s, ?int $e): string
    {
        return ($s !== null && $e !== null && $s < $e && isset($this->tb[$s], $this->tb[$e - 1])) ? substr($this->B, $this->tb[$s][1], $this->tb[$e - 1][2] - $this->tb[$s][1]) : '';
    }

    /** The new words for A's words [s, e): the text, '' when taken out. */
    private function newText(int $s, int $e, bool $container = false): string
    {
        [$bs, $be] = $this->bRange($s, $e, $container);
        return trim($this->textB($bs, $be));
    }

    /** A byte range of A as a word range. */
    private function spanOf(int $byteStart, int $byteEnd): ?array
    {
        $s = null;
        $e = null;
        foreach ($this->ta as $i => [$w, $bs, $be]) {
            if ($be > $byteStart && $bs < $byteEnd) {
                $s = $s ?? $i;
                $e = $i + 1;
            }
        }
        return $s === null ? null : [$s, $e];
    }

    private function claim(array $segIdx): void
    {
        foreach ($segIdx as $k) {
            $this->claimed[$k] = true;
        }
    }

    // =====================================================================
    //  The run
    // =====================================================================

    private function run(): void
    {
        if ($this->A === '') {
            $this->refusals[] = 'Paste the words that need to change into the first box — a line of the email, or the value as the inquiry shows it.';
            return;
        }
        $this->ta = self::tokens($this->A);
        $this->tb = self::tokens($this->B);
        if ($this->ta === []) {
            $this->refusals[] = 'The first box has no words in it that could be matched.';
            return;
        }
        if (array_column($this->ta, 0) === array_column($this->tb, 0)) {
            $this->refusals[] = 'The two boxes say the same thing, so there is nothing to change. Type what it should say now in the Change to box.';
            return;
        }
        $this->segs = self::align($this->ta, $this->tb);
        $this->refuseFigures();
        $this->locate();
        if ($this->questions) {
            return;   // which place in the email: nothing else can be settled before that
        }

        $this->readDates();
        $this->readNights();
        $this->readCounts();
        $this->readFlights();
        $this->readRooms();
        $this->readMeals();
        $this->readNightValues();
        $this->readServices();
        $this->readContainers();
        $this->readPassengers();
        $this->readCrew();
        $this->readVehicle();
        $this->readTextFields();
        $this->readAdditions();
        $this->checkNightsAgree();
        $this->unclaimed();

        if (!$this->intents && !$this->questions && !$this->refusals) {
            $this->askTarget('target', 'I could not tell which part of the inquiry these words are. Choose what the change is about.');
        }
    }

    // =====================================================================
    //  Where the pasted words are
    // =====================================================================

    private function locate(): void
    {
        foreach ([['email', (string) ($this->it['source_text'] ?? '')], ['original', (string) ($this->it['source_text_original'] ?? '')]] as [$where, $text]) {
            if (trim($text) === '') {
                continue;
            }
            $tok  = self::tokens($text);
            $occs = self::find($tok, $this->ta);
            if ($occs) {
                $this->located   = $where;
                $this->emailTok  = $tok;
                $this->emailText = $text;
                $this->occs      = $occs;
                break;
            }
        }
        if ($this->located === 'none') {
            $text = (string) ($this->it['source_text'] ?? '');
            if (trim($text) !== '') {
                $this->emailTok  = self::tokens($text);
                $this->emailText = $text;
            }
            $this->located = 'values';
            return;
        }
        $this->buildWindows();
        // which nights each place belongs to; the same words for the same nights everywhere is one place
        $sets = [];
        foreach ($this->occs as $k => $o) {
            $sets[$k] = $this->nightsAt($o, $o + count($this->ta));
        }
        $distinct = array_values(array_unique(array_map(static fn ($s) => implode(',', array_keys($s)), $sets)));
        if (count($distinct) <= 1) {
            $this->occ = $this->occs[0];
            $this->occList = [$this->occ];
            $this->nightsHere = $sets[0];
            return;
        }
        $ans = $this->answers['occ'] ?? null;
        if ($ans === 'all') {
            $this->occ = $this->occs[0];
            $this->occList = $this->occs;
            foreach ($sets as $s) {
                $this->nightsHere += $s;
            }
            return;
        }
        if ($ans !== null && ctype_digit((string) $ans) && isset($this->occs[(int) $ans])) {
            $this->occ = $this->occs[(int) $ans];
            $this->occList = [$this->occ];
            $this->nightsHere = $sets[(int) $ans];
            return;
        }
        $opts = [];
        foreach ($this->occs as $k => $o) {
            $opts[] = ['value' => (string) $k, 'label' => $this->placeLabel($sets[$k], $o)];
        }
        $opts[] = ['value' => 'all', 'label' => 'All of them'];
        $this->questions['occ'] = ['id' => 'occ', 'kind' => 'choice', 'text' => 'These words are in the email ' . count($this->occs) . ' times, for different days. Which one is meant?', 'options' => $opts];
    }

    private function placeLabel(array $nights, int $o): string
    {
        $line = $this->emailLineAt($o);
        $where = $nights ? implode(', ', array_map(fn ($n) => $this->nightName((int) $n), array_keys($nights))) : 'outside the day-by-day part';
        return $where . ' — “' . mb_substr($line, 0, 90) . (mb_strlen($line) > 90 ? '…' : '') . '”';
    }

    private function emailLineAt(int $tok): string
    {
        if (!isset($this->emailTok[$tok])) {
            return '';
        }
        $off = $this->emailTok[$tok][1];
        $start = strrpos(substr($this->emailText, 0, $off), "\n");
        $start = $start === false ? 0 : $start + 1;
        $end = strpos($this->emailText, "\n", $off);
        return trim(substr($this->emailText, $start, ($end === false ? strlen($this->emailText) : $end) - $start));
    }

    /**
     * Each night's part of the email: from the line its words start on to the line the next
     * night's words start on (the last one: its own lines, a few at most). Found from the reader's
     * own quotes, in order, so a hotel named on two nights is told apart by where it stands.
     */
    private function buildWindows(): void
    {
        $starts = [];
        $from = 0;
        foreach ($this->dayRows() as $d) {
            $q = trim((string) ($d['quote'] ?? ''));
            if ($q === '') {
                continue;
            }
            $qt = self::tokens($q);
            $hits = array_values(array_filter(self::find($this->emailTok, $qt), static fn ($x) => $x >= $from));
            if (!$hits) {
                continue;
            }
            $starts[(int) $d['day_no']] = [$this->lineStartTok($hits[0]), $hits[0] + count($qt)];
            $from = $hits[0] + 1;
        }
        ksort($starts);
        $days = array_keys($starts);
        foreach ($days as $i => $n) {
            $s = $starts[$n][0];
            $e = isset($days[$i + 1]) ? $starts[$days[$i + 1]][0] : $this->lineEndTok($starts[$n][1] - 1, 4);
            $this->windows[$n] = [$s, max($e, $starts[$n][1])];
        }
    }

    private function lineStartTok(int $tok): int
    {
        $off = $this->emailTok[$tok][1];
        $nl = strrpos(substr($this->emailText, 0, $off), "\n");
        $lineStart = $nl === false ? 0 : $nl + 1;
        for ($k = $tok; $k > 0 && $this->emailTok[$k - 1][1] >= $lineStart; $k--) {
        }
        return $k;
    }

    /** The token just past the end of the line holding $tok, and up to $more following non-empty lines. */
    private function lineEndTok(int $tok, int $more = 0): int
    {
        $len = strlen($this->emailText);
        $nl  = strpos($this->emailText, "\n", $this->emailTok[$tok][2]);
        $end = $nl === false ? $len : $nl;
        for ($i = 0; $i < $more && $end < $len; $i++) {
            $next = strpos($this->emailText, "\n", $end + 1);
            $line = substr($this->emailText, $end + 1, ($next === false ? $len : $next) - $end - 1);
            if (trim($line) === '') {
                break;
            }
            $end = $next === false ? $len : $next;
        }
        foreach ($this->emailTok as $k => $t) {
            if ($t[1] >= $end) {
                return $k;
            }
        }
        return count($this->emailTok);
    }

    /** @return array<int, bool> the nights whose part of the email overlaps email tokens [s, e) */
    private function nightsAt(int $s, int $e): array
    {
        $out = [];
        foreach ($this->windows as $n => [$ws, $we]) {
            if ($s < $we && $e > $ws) {
                $out[$n] = true;
            }
        }
        return $out;
    }

    /** Is A's word $i, in the email, inside night $n's part? (Always yes when the words were not found in the email.) */
    private function inNight(int $i, int $n): bool
    {
        if ($this->occ === null) {
            return true;
        }
        if (!isset($this->windows[$n])) {
            return false;
        }
        [$ws, $we] = $this->windows[$n];
        foreach ($this->occList ?: [$this->occ] as $o) {
            if ($o + $i >= $ws && $o + $i < $we) {
                return true;
            }
        }
        return false;
    }

    /** Is A's word $i, in the email, inside any night's part? */
    private function inAnyNight(int $i): ?int
    {
        if ($this->occ === null) {
            return null;
        }
        foreach ($this->windows as $n => [$ws, $we]) {
            if ($this->occ + $i >= $ws && $this->occ + $i < $we) {
                return $n;
            }
        }
        return null;
    }

    // =====================================================================
    //  The inquiry's rows, with the email words behind each
    // =====================================================================

    /** Nights, lines on the departure day and the departure day itself, each with the email's own quote for it. */
    private function dayRows(): array
    {
        $rows = [];
        $rawDays = [];
        foreach ((array) ($this->raw['days'] ?? []) as $rd) {
            $rawDays[(int) ($rd['day_no'] ?? 0)] = $rd;
        }
        foreach (array_merge((array) ($this->it['days'] ?? []), (array) ($this->it['departure_rows'] ?? [])) as $d) {
            $n = (int) $d['day_no'];
            $rd = $rawDays[$n] ?? [];
            $rows[$n] = [
                'day_no' => $n, 'id' => (int) $d['id'], 'row' => $d, 'departure_row' => false,
                'quote' => trim((string) ($d['source_quote'] ?? '')) !== '' ? (string) $d['source_quote'] : (string) ($rd['quote'] ?? ''),
                'raw' => $rd,
            ];
        }
        foreach ((array) ($this->it['departure_rows'] ?? []) as $d) {
            $rows[(int) $d['day_no']]['departure_row'] = true;
        }
        $dep = $this->it['departure_day'] ?? null;
        if (is_array($dep) && isset($dep['day_no']) && !isset($rows[(int) $dep['day_no']])) {
            $n = (int) $dep['day_no'];
            $rd = $rawDays[$n] ?? ($this->raw['departure_day'] ?? []);
            $rows[$n] = ['day_no' => $n, 'id' => 0, 'row' => null, 'departure_row' => false, 'departure_day' => true, 'quote' => (string) ($rd['quote'] ?? ''), 'raw' => is_array($rd) ? $rd : []];
        }
        ksort($rows);
        return $rows;
    }

    private function nightName(int $n): string
    {
        $dep = $this->it['departure_day'] ?? null;
        $isDep = is_array($dep) && (int) ($dep['day_no'] ?? 0) === $n;
        $date = null;
        foreach (array_merge((array) ($this->it['days'] ?? []), (array) ($this->it['departure_rows'] ?? [])) as $d) {
            if ((int) $d['day_no'] === $n && !empty($d['day_date'])) {
                $date = (string) $d['day_date'];
            }
        }
        if ($isDep) {
            $date = $date ?? ($dep['date'] ?? null);
        }
        return ($isDep ? 'the departure day' : 'night ' . $n) . ($date ? ' (' . date('j M', strtotime($date)) . ')' : '');
    }

    private function fieldValue(string $key)
    {
        return $this->it['fields'][$key]['value'] ?? ($this->it[$key] ?? null);
    }

    private function emailValue(string $key)
    {
        $v = $this->raw[$key]['value'] ?? null;
        return is_array($v) ? null : $v;
    }

    private function setBy(string $key): ?string
    {
        $f = $this->it['fields'][$key] ?? [];
        return ($f['origin'] ?? '') === 'human' ? (trim((string) ($f['basis'] ?? '')) ?: 'set by hand') : null;
    }

    private function addIntent(array $i): void
    {
        $key = $i['key'];
        if (isset($this->intents[$key])) {
            // one working value, one new value: two readings of the same value must agree
            if (($this->intents[$key]['to_raw'] ?? null) != ($i['to_raw'] ?? null)) {
                unset($this->intents[$key]);
                $this->questions['clash:' . $key] = ['id' => 'clash:' . $key, 'kind' => 'info', 'text' => 'The change gives two different new values for ' . $i['label'] . ' (' . $i['to'] . '). Say it once, in one Edit Request.', 'options' => []];
            }
            return;
        }
        $i['same'] = self::sameValue($i['from_raw'] ?? null, $i['to_raw'] ?? null);
        $this->intents[$key] = $i;
    }

    private static function sameValue($a, $b): bool
    {
        if (is_array($a) || is_array($b)) {
            return json_encode($a) === json_encode($b);
        }
        return mb_strtolower(trim((string) $a)) === mb_strtolower(trim((string) $b));
    }

    // =====================================================================
    //  Dates
    // =====================================================================

    /**
     * Date expressions in a text, each with its byte span and its dates in order (a range gives two).
     * A year is taken only where the expression writes one; within one range the missing year of the
     * first date is the second's (the year before, when the months run over the new year).
     *
     * @return array<int, array{start:int, end:int, text:string, dates:array<int, array{day:int, month:int, year:?int, iso:?string}>}>
     */
    public static function dateExprs(string $text): array
    {
        $out = [];
        $taken = [];
        $M = self::MONTH_RX;
        $yr = static function ($y): ?int {
            $y = trim((string) $y, "' ");
            if ($y === '') {
                return null;
            }
            $n = (int) $y;
            if (strlen($y) === 2) {
                return ($n >= 20 && $n <= 39) ? 2000 + $n : null;
            }
            return ($n >= 2000 && $n <= 2099) ? $n : null;
        };
        $mon = static fn (string $m): int => self::MONTHS[substr(strtolower(rtrim($m, '.')), 0, 3)] ?? 0;
        $mk = static function (int $d, int $m, ?int $y): array {
            return ['day' => $d, 'month' => $m, 'year' => $y, 'iso' => ($y !== null && checkdate($m, $d, $y)) ? sprintf('%04d-%02d-%02d', $y, $m, $d) : null];
        };
        $add = static function (int $start, string $t, array $dates) use (&$out, &$taken, $text): void {
            $end = $start + strlen($t);
            if (count($dates) === 2) {
                // "Day 1 – 26 December" is a day's label and a date, not a range of dates
                if (preg_match('/\bday\s*$/iu', self::before($text, $start, 6))) {
                    return;
                }
                $a = sprintf('%02d%02d', $dates[0]['month'], $dates[0]['day']);
                $b = sprintf('%02d%02d', $dates[1]['month'], $dates[1]['day']);
                $ya = $dates[0]['year'] ?? 0;
                $yb = $dates[1]['year'] ?? 0;
                if ($ya . $a >= $yb . $b) {
                    return;   // a range runs forwards
                }
            }
            foreach ($taken as [$s, $e]) {
                if ($start < $e && $end > $s) {
                    return;
                }
            }
            foreach ($dates as $d) {
                if ($d['day'] < 1 || $d['day'] > 31 || $d['month'] < 1 || $d['month'] > 12) {
                    return;
                }
            }
            $taken[] = [$start, $end];
            $out[] = ['start' => $start, 'end' => $end, 'text' => $t, 'dates' => $dates];
        };
        $d = '(\d{1,2})(?:st|nd|rd|th)?';
        $sep = '\s*(?:-|–|—|~|to|till|until|through)\s*';
        // 26 December 2026 – 2 January 2027 / 26 Dec – 2 Jan 2027
        if (preg_match_all("/(?<![\\d:]){$d}\\s*{$M},?\\s*(\\d{4})?{$sep}{$d}\\s*{$M},?\\s*(\\d{4})(?!\\d)/iu", $text, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $m1 = $mon($m[2][0]);
                $m2 = $mon($m[5][0]);
                $y2 = $yr($m[6][0]);
                $y1 = $yr($m[3][0] ?? '') ?? ($y2 !== null ? ($m1 > $m2 ? $y2 - 1 : $y2) : null);
                $add($m[0][1], $m[0][0], [$mk((int) $m[1][0], $m1, $y1), $mk((int) $m[4][0], $m2, $y2)]);
            }
        }
        // 10–18 December 2026
        if (preg_match_all("/(?<![\\d:]){$d}{$sep}{$d}\\s*{$M},?\\s*(\\d{4})?(?!\\d)/iu", $text, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $mo = $mon($m[3][0]);
                $y = $yr($m[4][0] ?? '');
                $add($m[0][1], $m[0][0], [$mk((int) $m[1][0], $mo, $y), $mk((int) $m[2][0], $mo, $y)]);
            }
        }
        // December 10–18, 2026
        if (preg_match_all("/\\b{$M}\\s+{$d}{$sep}{$d},?\\s*(\\d{4})?(?!\\d)/iu", $text, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $mo = $mon($m[1][0]);
                $y = $yr($m[4][0] ?? '');
                $add($m[0][1], $m[0][0], [$mk((int) $m[2][0], $mo, $y), $mk((int) $m[3][0], $mo, $y)]);
            }
        }
        // 26 December 2026, 26 Dec, 26-Dec-2026, 26DEC26
        if (preg_match_all("/(?<![\\d:]){$d}\\s*[-\\/.]?\\s*{$M},?\\s*[-\\/.]?\\s*('?\\d{2}(?!\\d)|\\d{4}(?!\\d))?/iu", $text, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $add($m[0][1], rtrim($m[0][0]), [$mk((int) $m[1][0], $mon($m[2][0]), $yr($m[3][0] ?? ''))]);
            }
        }
        // December 26, 2026 / Dec 10
        if (preg_match_all("/\\b{$M}\\s+{$d}(?!\\d)(?:\\s*,?\\s*(\\d{4})(?!\\d))?/iu", $text, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $add($m[0][1], $m[0][0], [$mk((int) $m[2][0], $mon($m[1][0]), $yr($m[3][0] ?? ''))]);
            }
        }
        // 2026-12-26, 2026/12/26
        if (preg_match_all('/(?<!\d)(20[2-3]\d)[-\/.](\d{1,2})[-\/.](\d{1,2})(?!\d)/u', $text, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $add($m[0][1], $m[0][0], [$mk((int) $m[3][0], (int) $m[2][0], (int) $m[1][0])]);
            }
        }
        // 26/12/2026 — day first, as the trade writes it; a year is required
        if (preg_match_all('/(?<![\d\/.\-])(\d{1,2})[\/.](\d{1,2})[\/.](\d{4}|\d{2})(?![\d\/.])/u', $text, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $add($m[0][1], $m[0][0], [$mk((int) $m[1][0], (int) $m[2][0], $yr($m[3][0]))]);
            }
        }
        usort($out, static fn ($a, $b) => $a['start'] <=> $b['start']);
        return $out;
    }

    private static function iso($v): ?string
    {
        return TourDates::iso($v);
    }

    private static function pretty(?string $iso): string
    {
        return $iso ? date('D j M Y', strtotime($iso)) : '—';
    }

    private function readDates(): void
    {
        $exprs = self::dateExprs($this->A);
        $vals = [
            'arrival_date'   => [self::iso($this->fieldValue('arrival_date')), self::iso($this->emailValue('arrival_date')), self::iso($this->it['email_arrival_date'] ?? null)],
            'departure_date' => [self::iso($this->fieldValue('departure_date')), self::iso($this->emailValue('departure_date')), self::iso($this->it['email_departure_date'] ?? null)],
        ];
        foreach ($exprs as $x => $ex) {
            $span = $this->spanOf($ex['start'], $ex['end']);
            if ($span === null) {
                continue;
            }
            $hit = $this->changedIn($span[0], $span[1]);
            if (!$hit) {
                continue;
            }
            // a date inside a night's part of the email (not the tour's own dates) is that night's date:
            // the nights are dated from the arrival, never one by one
            $fields = $this->dateFields($ex, $x, $span, $vals);
            if ($fields === null) {
                continue;   // a question was asked, or it is a night's date (a refusal was written)
            }
            $this->claim($hit);
            $newText = $this->newText($span[0], $span[1]);
            $new = self::dateExprs($newText);
            $dates = [];
            foreach ($new as $ne) {
                foreach ($ne['dates'] as $dd) {
                    $dates[] = $dd;
                }
            }
            if ($newText === '' || $dates === []) {
                $this->questions['date:' . $x] = ['id' => 'date:' . $x, 'kind' => 'info', 'text' => 'The change takes out “' . $ex['text'] . '” and puts no date in its place. A tour needs its dates: type the new date in the Change to box.', 'options' => []];
                continue;
            }
            if (count($dates) !== count($fields)) {
                $this->questions['date:' . $x] = ['id' => 'date:' . $x, 'kind' => 'info', 'text' => '“' . $ex['text'] . '” has ' . count($fields) . ' date(s) and “' . $newText . '” has ' . count($dates) . '. Write the new ' . (count($fields) === 2 ? 'arrival and departure dates the same way' : 'date on its own') . '.', 'options' => []];
                continue;
            }
            foreach ($fields as $k => $field) {
                $nd = $dates[$k];
                $iso = $nd['iso'];
                if ($iso === null && $nd['year'] === null) {
                    $qid = 'year:' . $field;
                    $ans = $this->answers[$qid] ?? null;
                    $cur = $vals[$field][0] ?? $vals[$field][1];
                    if ($ans !== null && preg_match('/^20\d\d$/', (string) $ans) && checkdate($nd['month'], $nd['day'], (int) $ans)) {
                        $iso = sprintf('%04d-%02d-%02d', (int) $ans, $nd['month'], $nd['day']);
                    } else {
                        $y0 = $cur ? (int) substr($cur, 0, 4) : (int) date('Y');
                        $opts = [];
                        foreach ([$y0, $y0 + 1] as $y) {
                            if (checkdate($nd['month'], $nd['day'], $y)) {
                                $opts[] = ['value' => (string) $y, 'label' => date('D j M Y', mktime(0, 0, 0, $nd['month'], $nd['day'], $y))];
                            }
                        }
                        $this->questions[$qid] = ['id' => $qid, 'kind' => 'choice', 'text' => 'The new ' . strtolower(self::FIELD_LABELS[$field]) . ' “' . $newText . '” has no year. Which year is it? (A year is never filled in for you.)', 'options' => $opts];
                        continue;
                    }
                }
                if ($iso === null) {
                    $this->questions['date:' . $x . ':' . $k] = ['id' => 'date:' . $x . ':' . $k, 'kind' => 'info', 'text' => '“' . $newText . '” is not a real date.', 'options' => []];
                    continue;
                }
                $this->newDates[$field] = $iso;
                $cur = $vals[$field][0];
                $email = $vals[$field][2] ?? $vals[$field][1];
                $this->addIntent([
                    'key' => 'field:' . $field, 'kind' => 'field', 'field' => $field, 'label' => self::FIELD_LABELS[$field], 'scope' => 'Dates',
                    'from' => self::pretty($cur), 'to' => self::pretty($iso), 'from_raw' => $cur, 'to_raw' => $iso,
                    'note' => trim(($this->setBy($field) ? 'Now ' . self::pretty($cur) . ', ' . $this->setBy($field) . '.' : '') . ($email && $email !== $cur ? ' The email says ' . self::pretty($email) . '.' : '')),
                    'words' => [$ex['text'], $newText],
                ]);
            }
        }
    }

    /** Which of the tour's dates an expression is, or null (asked, or a night's own date). */
    private function dateFields(array $ex, int $x, array $span, array $vals): ?array
    {
        $n = count($ex['dates']);
        if ($n === 2) {
            return ['arrival_date', 'departure_date'];
        }
        $d = $ex['dates'][0];
        $matches = [];
        foreach ($vals as $field => $isos) {
            foreach ($isos as $iso) {
                if ($iso === null) {
                    continue;
                }
                if ($d['iso'] !== null ? $iso === $d['iso'] : ((int) substr($iso, 5, 2) === $d['month'] && (int) substr($iso, 8, 2) === $d['day'])) {
                    $matches[$field] = true;
                }
            }
        }
        // the words just before the date say which it is: "Arrival:", "departing", "check-out"
        $before = self::before($this->A, $ex['start'], 40);
        $nl = strrpos($before, "\n");
        $before = $nl === false ? $before : substr($before, $nl + 1);
        $isArr = (bool) preg_match(self::ARRIVE, $before);
        $isDep = (bool) preg_match(self::DEPART, $before);
        if (count($matches) === 1) {
            $f = array_key_first($matches);
            if (($f === 'arrival_date' && $isDep && !$isArr) || ($f === 'departure_date' && $isArr && !$isDep)) {
                // the value says one date and the label the other: not settled by either
            } else {
                return [$f];
            }
        }
        if ($isArr xor $isDep) {
            return [$isArr ? 'arrival_date' : 'departure_date'];
        }
        // a date in one night's part of the email is that night's date
        if ($this->inAnyNight($span[0]) !== null && !$matches) {
            $this->refusals[] = '“' . $ex['text'] . '” is the date of ' . $this->nightName((int) $this->inAnyNight($span[0])) . '. The nights are dated from the arrival date, one after another: change the arrival or departure date instead, and the nights follow.';
            $this->claim($this->changedIn($span[0], $span[1]));
            return null;
        }
        $qid = 'datefield:' . $x;
        $ans = $this->answers[$qid] ?? null;
        if (in_array($ans, ['arrival_date', 'departure_date'], true)) {
            return [$ans];
        }
        if ($ans === 'none') {
            $this->claim($this->changedIn($span[0], $span[1]));
            $this->notes[] = '“' . $ex['text'] . '” was left out: you said it is not one of the tour\'s dates.';
            return null;
        }
        $this->questions[$qid] = ['id' => $qid, 'kind' => 'choice', 'text' => 'Is “' . $ex['text'] . '” the arrival date or the departure date?', 'options' => [
            ['value' => 'arrival_date', 'label' => 'The arrival date (now ' . self::pretty($vals['arrival_date'][0]) . ')'],
            ['value' => 'departure_date', 'label' => 'The departure date (now ' . self::pretty($vals['departure_date'][0]) . ')'],
            ['value' => 'none', 'label' => 'Neither — leave it out'],
        ]];
        return null;
    }

    // =====================================================================
    //  Nights: they follow the dates
    // =====================================================================

    private function readNights(): void
    {
        if (!preg_match_all('/(?<![\d.])(\d{1,2})\s*(?:-\s*)?(nights?|nts?)\b/iu', $this->A, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return;
        }
        foreach ($mm as $m) {
            $span = $this->spanOf($m[0][1], $m[0][1] + strlen($m[0][0]));
            if ($span === null || !($hit = $this->changedIn($span[0], $span[1]))) {
                continue;
            }
            $this->claim($hit);
            // the days beside the nights ("8 Nights / 9 Days") move with them and say nothing of their own
            if (preg_match('/^\s*[\/,|-]?\s*\d{1,2}\s*days?\b/iu', substr($this->A, $m[0][1] + strlen($m[0][0]), 16), $dm)) {
                $ds = $this->spanOf($m[0][1] + strlen($m[0][0]), $m[0][1] + strlen($m[0][0]) + strlen($dm[0]));
                if ($ds) {
                    $this->claim($this->changedIn($ds[0], $ds[1]));
                }
            }
            $newText = $this->newText($span[0], $span[1]);
            if (!preg_match('/(\d{1,2})\s*(?:-\s*)?(?:nights?|nts?)\b/iu', $newText, $nm)) {
                continue;
            }
            $this->newNightsSaid = (int) $nm[1];
        }
        // "N days" on its own changes with the nights; claimed with them where they are
        if (preg_match_all('/(?<![\d.])(\d{1,2})\s*days?\b/iu', $this->A, $dd, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($dd as $m) {
                $span = $this->spanOf($m[0][1], $m[0][1] + strlen($m[0][0]));
                if ($span && $this->newNightsSaid !== null) {
                    $this->claim($this->changedIn($span[0], $span[1]));
                }
            }
        }
    }

    /** A new number of nights: fine when the new dates give it; otherwise one of the dates has to move, and a person says which. */
    private function checkNightsAgree(): void
    {
        if ($this->newNightsSaid === null) {
            return;
        }
        $arr = $this->newDates['arrival_date'] ?? self::iso($this->fieldValue('arrival_date'));
        $dep = $this->newDates['departure_date'] ?? self::iso($this->fieldValue('departure_date'));
        $n = TourDates::nights($arr, $dep);
        if ($this->newDates !== []) {
            if ($n !== null && $n !== $this->newNightsSaid) {
                $this->questions['nights'] = ['id' => 'nights', 'kind' => 'info', 'text' => 'The new dates give ' . $n . ' night(s), but the change says ' . $this->newNightsSaid . '. The nights always follow the dates: correct one of them in the Change to box.', 'options' => []];
            } else {
                $this->notes[] = 'The nights follow the dates: ' . $n . ' night(s).';
            }
            return;
        }
        if ($arr === null || $dep === null) {
            $this->questions['nights'] = ['id' => 'nights', 'kind' => 'info', 'text' => 'The nights follow the arrival and departure dates, and this inquiry does not have both yet. Put the dates in the change instead of the number of nights.', 'options' => []];
            return;
        }
        $cur = (int) TourDates::nights($arr, $dep);
        $diff = $this->newNightsSaid - $cur;
        if ($diff === 0) {
            $this->notes[] = 'The inquiry already has ' . $cur . ' night(s).';
            return;
        }
        $newDep = date('Y-m-d', strtotime($dep . ' ' . ($diff > 0 ? '+' : '') . $diff . ' day'));
        $newArr = date('Y-m-d', strtotime($arr . ' ' . ($diff > 0 ? '-' : '+') . abs($diff) . ' day'));
        $ans = $this->answers['nights'] ?? null;
        if ($ans === 'departure' || $ans === 'arrival') {
            $field = $ans . '_date';
            $iso = $ans === 'departure' ? $newDep : $newArr;
            $cur0 = $ans === 'departure' ? $dep : $arr;
            $this->addIntent([
                'key' => 'field:' . $field, 'kind' => 'field', 'field' => $field, 'label' => self::FIELD_LABELS[$field], 'scope' => 'Dates',
                'from' => self::pretty($cur0), 'to' => self::pretty($iso), 'from_raw' => $cur0, 'to_raw' => $iso,
                'note' => $cur . ' night(s) become ' . $this->newNightsSaid . ': the ' . ($ans === 'departure' ? 'departure moves' : 'arrival moves') . ', as you chose.',
                'words' => [$cur . ' nights', $this->newNightsSaid . ' nights'],
            ]);
            return;
        }
        $this->questions['nights'] = ['id' => 'nights', 'kind' => 'choice', 'text' => 'The nights follow the dates and are never typed. ' . $cur . ' night(s) becoming ' . $this->newNightsSaid . ' means one of the dates moves. Which one?', 'options' => [
            ['value' => 'departure', 'label' => 'The departure moves: ' . self::pretty($dep) . ' → ' . self::pretty($newDep)],
            ['value' => 'arrival', 'label' => 'The arrival moves: ' . self::pretty($arr) . ' → ' . self::pretty($newArr)],
        ]];
    }

    // =====================================================================
    //  The party
    // =====================================================================

    private const COUNT_RX = '/(?<![\d.])(\d{1,3}|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve)\s*(?:x\s*)?(adults?|adts?|pax|persons?|people|guests?|travell?ers?|passengers?|children|child|kids?|chd|infants?)\b/iu';

    private static function countOf(string $w): ?int
    {
        return ctype_digit($w) ? (int) $w : (self::NUM_WORDS[strtolower($w)] ?? null);
    }

    private static function countField(string $word): ?string
    {
        $w = strtolower($word);
        if (preg_match('/^(adults?|adts?)$/', $w)) {
            return 'pax_adults';
        }
        if (preg_match('/^(children|child|kids?|chd)$/', $w)) {
            return 'pax_children';
        }
        if (preg_match('/^infants?$/', $w)) {
            return null;
        }
        return 'pax_total';
    }

    private function readCounts(): void
    {
        if (!preg_match_all(self::COUNT_RX, $this->A, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return;
        }
        $said = [];
        foreach ($mm as $m) {
            $span = $this->spanOf($m[0][1], $m[0][1] + strlen($m[0][0]));
            if ($span === null || !($hit = $this->changedIn($span[0], $span[1]))) {
                continue;
            }
            $field = self::countField($m[2][0]);
            $this->claim($hit);
            if ($field === null) {
                $this->refusals[] = 'Infants are not counted separately on an inquiry. Say how many adults and children there are.';
                continue;
            }
            $newText = $this->newText($span[0], $span[1]);
            if ($newText === '') {
                $this->questions['count:' . $field] = ['id' => 'count:' . $field, 'kind' => 'info', 'text' => 'The change takes out “' . $m[0][0] . '” and says nothing in its place. Say the new number.', 'options' => []];
                continue;
            }
            if (!preg_match(self::COUNT_RX, $newText, $nm) || self::countField($nm[2]) !== $field) {
                $this->questions['count:' . $field] = ['id' => 'count:' . $field, 'kind' => 'info', 'text' => '“' . $newText . '” does not say how many ' . strtolower(self::FIELD_LABELS[$field]) . ' — write it as “3 adults”, “1 child” or “4 pax”.', 'options' => []];
                continue;
            }
            $said[$field] = self::countOf($nm[1]);
        }
        if (!$said) {
            return;
        }
        $cur = ['pax_total' => self::intOrNull($this->fieldValue('pax_total')), 'pax_adults' => self::intOrNull($this->fieldValue('pax_adults')), 'pax_children' => self::intOrNull($this->fieldValue('pax_children'))];
        $new = $cur;
        foreach ($said as $f => $v) {
            $new[$f] = $v;
        }
        // the party size is the adults and the children together — arithmetic, when both are known
        if (!isset($said['pax_total']) && (isset($said['pax_adults']) || isset($said['pax_children']))) {
            $wasSum = $cur['pax_total'] === null || $cur['pax_total'] === (int) ($cur['pax_adults'] ?? 0) + (int) ($cur['pax_children'] ?? 0);
            if ($wasSum && $new['pax_adults'] !== null) {
                $new['pax_total'] = (int) $new['pax_adults'] + (int) ($new['pax_children'] ?? 0);
            } else {
                $this->questions['count:pax_total'] = ['id' => 'count:pax_total', 'kind' => 'info', 'text' => 'The party size (' . $cur['pax_total'] . ') is not the adults and children added up on this inquiry, so the new size cannot be worked out. Say the whole party too, e.g. “3 adults, 4 pax”.', 'options' => []];
                return;
            }
        }
        foreach (['pax_adults', 'pax_children', 'pax_total'] as $f) {
            if ($new[$f] === $cur[$f] && !isset($said[$f])) {
                continue;
            }
            $this->addIntent([
                'key' => 'field:' . $f, 'kind' => 'field', 'field' => $f, 'label' => self::FIELD_LABELS[$f], 'scope' => 'The party',
                'from' => $cur[$f] === null ? '—' : (string) $cur[$f], 'to' => (string) $new[$f], 'from_raw' => $cur[$f], 'to_raw' => $new[$f],
                'note' => $f === 'pax_total' && !isset($said['pax_total']) ? 'The adults and children added up.' : ($this->setBy($f) ? 'Now ' . $cur[$f] . ', ' . $this->setBy($f) . '.' : ''),
            ]);
        }
    }

    private static function intOrNull($v): ?int
    {
        return ($v === null || $v === '' || !is_numeric($v)) ? null : (int) $v;
    }

    // =====================================================================
    //  Flights
    // =====================================================================

    private const FLIGHT_RX = '/\b([A-Z]{2}|[A-Z]\d|\d[A-Z])\s?(\d{2,4})\b/u';

    private static function flightKey(?string $v): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $v));
    }

    private function readFlights(): void
    {
        if (!preg_match_all(self::FLIGHT_RX, $this->A, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return;
        }
        foreach ($mm as $x => $m) {
            $span = $this->spanOf($m[0][1], $m[0][1] + strlen($m[0][0]));
            if ($span === null || !($hit = $this->changedIn($span[0], $span[1]))) {
                continue;
            }
            $code = self::flightKey($m[0][0]);
            $field = null;
            foreach (['arrival_flight', 'departure_flight'] as $f) {
                foreach ([$this->fieldValue($f), $this->emailValue($f)] as $v) {
                    if ($v !== null && str_contains(self::flightKey((string) $v), $code)) {
                        $field = $field === null || $field === $f ? $f : 'both';
                    }
                }
            }
            if ($field === null || $field === 'both') {
                $before = self::before($this->A, $m[0][1], 50);
                $isArr = (bool) preg_match('/\b(arriv\w*|arr\.?|inbound|in\s*bound|meet)\b/iu', $before);
                $isDep = (bool) preg_match('/\b(depart\w*|dep\.?|outbound|out\s*bound|leav\w*|return\w*)\b/iu', $before);
                $field = ($isArr xor $isDep) ? ($isArr ? 'arrival_flight' : 'departure_flight') : null;
            }
            if ($field === null) {
                $qid = 'flight:' . $x;
                $ans = $this->answers[$qid] ?? null;
                if (in_array($ans, ['arrival_flight', 'departure_flight'], true)) {
                    $field = $ans;
                } else {
                    $this->questions[$qid] = ['id' => $qid, 'kind' => 'choice', 'text' => 'Is “' . $m[0][0] . '” the arrival flight or the departure flight?', 'options' => [
                        ['value' => 'arrival_flight', 'label' => 'Arrival flight (now ' . ($this->fieldValue('arrival_flight') ?: '—') . ')'],
                        ['value' => 'departure_flight', 'label' => 'Departure flight (now ' . ($this->fieldValue('departure_flight') ?: '—') . ')'],
                    ]];
                    $this->claim($hit);
                    continue;
                }
            }
            $this->claim($hit);
            $newText = $this->newText($span[0], $span[1]);
            if ($newText === '' || !preg_match(self::FLIGHT_RX, $newText, $nm)) {
                $this->questions['flight:' . $field] = ['id' => 'flight:' . $field, 'kind' => 'info', 'text' => 'The new ' . strtolower(self::FIELD_LABELS[$field]) . ' should be a flight number, like UL 454 — “' . $newText . '” is not one.', 'options' => []];
                continue;
            }
            $cur = (string) ($this->fieldValue($field) ?? '');
            $newCode = trim($nm[0]);
            // the flight number inside what the inquiry holds is replaced; anything else written beside it stays
            $to = $cur !== '' && stripos(preg_replace('/\s+/', '', $cur), self::flightKey($m[0][0])) !== false
                ? preg_replace('/' . implode('\s?', array_map(static fn ($c) => preg_quote($c, '/'), str_split(self::flightKey($m[0][0])))) . '/i', $newCode, $cur, 1)
                : $newCode;
            $this->addIntent([
                'key' => 'field:' . $field, 'kind' => 'field', 'field' => $field, 'label' => self::FIELD_LABELS[$field], 'scope' => 'Flights',
                'from' => $cur !== '' ? $cur : '—', 'to' => $to, 'from_raw' => $cur, 'to_raw' => $to,
                'note' => $this->setBy($field) ? 'Now ' . $cur . ', ' . $this->setBy($field) . '.' : '',
            ]);
        }
    }

    // =====================================================================
    //  Rooms (the whole party's mix)
    // =====================================================================

    private const ROOM_RX = '/(?<![\d.])(\d{1,2}|one|two|three|four|five|six|seven|eight|nine|ten)\s*(?:x\s*)?(singles?|sgl|sngl|doubles?|dbl|twins?|twn|triples?|tpl|trpl)\b(?:\s*(?:bed\s*)?rooms?)?/iu';

    private static function roomType(string $w): string
    {
        $w = strtolower($w);
        return str_starts_with($w, 's') ? 'sgl' : (str_starts_with($w, 'd') ? 'dbl' : (str_starts_with($w, 'tw') ? 'twn' : 'tpl'));
    }

    private function readRooms(): void
    {
        // the party's rooms: every room count in A outside a night's own part, against every one in B
        $old = [];
        $spans = [];
        if (preg_match_all(self::ROOM_RX, $this->A, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $span = $this->spanOf($m[0][1], $m[0][1] + strlen($m[0][0]));
                if ($span === null || $this->inAnyNight($span[0]) !== null || preg_match('/(usd|lkr|rs\.?|\$|eur|€)\s*$/iu', self::before($this->A, $m[0][1], 6))) {
                    continue;   // a room count inside one night's part of the email is that night's rooms; after a currency, it is a price
                }
                $spans[] = $span;
                $t = self::roomType($m[2][0]);
                $old[$t] = ($old[$t] ?? 0) + (int) self::countOf($m[1][0]);
            }
        }
        if (!$spans) {
            return;
        }
        $new = [];
        $bSpans = [];
        if (preg_match_all(self::ROOM_RX, $this->B, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $t = self::roomType($m[2][0]);
                $new[$t] = ($new[$t] ?? 0) + (int) self::countOf($m[1][0]);
                $bSpans[] = [$m[0][1], $m[0][1] + strlen($m[0][0])];
            }
        }
        // the changes that are about the rooms: they touch a room count in A, or bring one in B
        $hit = [];
        foreach ($spans as $sp) {
            $hit = array_merge($hit, $this->changedIn($sp[0], $sp[1], true));
        }
        foreach ($this->segs as $k => [$type, $aS, $aE, $bS, $bE]) {
            if ($type !== 'ch' || $bE <= $bS) {
                continue;
            }
            foreach ($bSpans as [$x, $y]) {
                if ($this->tb[$bS][1] < $y && $this->tb[$bE - 1][2] > $x) {
                    $hit[] = $k;
                }
            }
        }
        ksort($old);
        ksort($new);
        if (!$hit || $old === $new) {
            return;
        }
        $this->claim(array_unique($hit));
        if (!empty($this->it['groups'])) {
            $this->refusals[] = 'This party is split into groups, and each group has its own rooms: change them under Groups on the inquiry.';
            return;
        }
        if (!$new) {
            $this->questions['rooms'] = ['id' => 'rooms', 'kind' => 'info', 'text' => 'The new rooms should say how many of each, like “1 double and 1 single room”.', 'options' => []];
            return;
        }
        $cur = ['sgl' => (int) ($this->it['rooms_sgl'] ?? 0), 'dbl' => (int) ($this->it['rooms_dbl'] ?? 0), 'twn' => (int) ($this->it['rooms_twn'] ?? 0), 'tpl' => (int) ($this->it['rooms_tpl'] ?? 0)];
        // the new words are the party's rooms, whole
        $to = ['sgl' => 0, 'dbl' => 0, 'twn' => 0, 'tpl' => 0];
        foreach ($new as $k => $v) {
            $to[$k] = $v;
        }
        $was = ['sgl' => 0, 'dbl' => 0, 'twn' => 0, 'tpl' => 0];
        foreach ($old as $k => $v) {
            $was[$k] = $v;
        }
        $note = '';
        if ($was !== $cur) {
            // the inquiry already says something else than the old words: a person changed it, or it was never set
            $ans = $this->answers['rooms'] ?? null;
            if ($ans === 'skip') {
                $this->notes[] = 'The rooms were left as they are (' . self::mixWords($cur) . '), as you chose.';
                return;
            }
            if ($ans !== 'new') {
                $this->questions['rooms'] = ['id' => 'rooms', 'kind' => 'choice', 'text' => 'These words say ' . self::mixWords($was) . ', but the inquiry has ' . self::mixWords($cur) . '. Should the rooms become ' . self::mixWords($to) . '?', 'options' => [
                    ['value' => 'new', 'label' => 'Yes — ' . self::mixWords($to)],
                    ['value' => 'skip', 'label' => 'No — leave the rooms as they are'],
                ]];
                return;
            }
            $note = 'The inquiry had ' . self::mixWords($cur) . ', not the ' . self::mixWords($was) . ' the old words say; you chose the new words.';
        }
        $this->addIntent([
            'key' => 'rooms', 'kind' => 'rooms', 'label' => 'Rooms', 'scope' => 'The party',
            'from' => self::mixWords($cur), 'to' => self::mixWords($to), 'from_raw' => $cur, 'to_raw' => $to, 'note' => $note,
        ]);
    }

    private static function mixWords(array $m): string
    {
        $w = [];
        foreach (['sgl' => 'SGL', 'dbl' => 'DBL', 'twn' => 'TWN', 'tpl' => 'TPL'] as $k => $l) {
            if ((int) ($m[$k] ?? 0) > 0) {
                $w[] = (int) $m[$k] . ' ' . $l;
            }
        }
        return $w ? implode(' · ', $w) : 'not set';
    }

    // =====================================================================
    //  Meal plans
    // =====================================================================

    private const MEAL_RX = '/\b(room\s*only|bed\s*(?:and|&)\s*breakfast|breakfast\s*only|half[\s-]*board|breakfast\s*(?:and|&)\s*dinner|full[\s-]*board|all[\s-]*inclusive|R\s*\/\s*O|B\s*\/\s*B|H\s*\/\s*B|F\s*\/\s*B|A\s*\/\s*I|RO|BB|HB|FB|AI)\b/u';

    private static function mealCode(string $w): ?string
    {
        $k = strtolower(preg_replace('/[\s\/&-]+/', ' ', trim($w)));
        $map = ['room only' => 'RO', 'bed and breakfast' => 'BB', 'bed breakfast' => 'BB', 'breakfast only' => 'BB', 'half board' => 'HB', 'breakfast and dinner' => 'HB', 'breakfast dinner' => 'HB', 'full board' => 'FB', 'all inclusive' => 'AI',
                'r o' => 'RO', 'b b' => 'BB', 'h b' => 'HB', 'f b' => 'FB', 'a i' => 'AI', 'ro' => 'RO', 'bb' => 'BB', 'hb' => 'HB', 'fb' => 'FB', 'ai' => 'AI'];
        return $map[$k] ?? null;
    }

    private function mealMatches(string $text): array
    {
        $out = [];
        // the words are matched case-blind except the bare two-letter codes, which must be capitals
        if (preg_match_all('/\b(room\s*only|bed\s*(?:and|&)\s*breakfast|breakfast\s*only|half[\s-]*board|breakfast\s*(?:and|&)\s*dinner|full[\s-]*board|all[\s-]*inclusive)\b/iu', $text, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $out[] = [$m[0][1], $m[0][0]];
            }
        }
        if (preg_match_all('/\b(R\s*\/\s*O|B\s*\/\s*B|H\s*\/\s*B|F\s*\/\s*B|A\s*\/\s*I|RO|BB|HB|FB|AI)\b/u', $text, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $out[] = [$m[0][1], $m[0][0]];
            }
        }
        usort($out, static fn ($a, $b) => $a[0] <=> $b[0]);
        return $out;
    }

    private function readMeals(): void
    {
        foreach ($this->mealMatches($this->A) as $x => [$off, $txt]) {
            $span = $this->spanOf($off, $off + strlen($txt));
            if ($span === null || !($hit = $this->changedIn($span[0], $span[1]))) {
                continue;
            }
            $this->claim($hit);
            $newText = $this->newText($span[0], $span[1]);
            $nm = $this->mealMatches($newText);
            $code = $nm ? self::mealCode($nm[0][1]) : null;
            if ($code === null) {
                $this->questions['meal:' . $x] = ['id' => 'meal:' . $x, 'kind' => 'info', 'text' => 'The new meal plan should be one of RO, BB, HB, FB or AI (or room only, bed and breakfast, half board, full board, all inclusive) — “' . $newText . '” is not one.', 'options' => []];
                continue;
            }
            $night = $this->inAnyNight($span[0]);
            if ($night !== null) {
                $this->nightMeal($night, $code, $txt, $newText);
                continue;
            }
            // the tour's own meal plan: the words the reader took it from, or said as a label ("Meals:", "Meal plan:")
            $quote = (string) ($this->raw['meal_basis']['quote'] ?? ($this->it['fields']['meal_basis']['quote'] ?? ''));
            $labelled = (bool) preg_match('/\b(meals?|meal\s*plan|board|basis)\b/iu', self::before($this->A, $off, 30));
            $inQuote = $quote !== '' && str_contains(self::normText($this->A), self::normText($quote)) || ($quote !== '' && str_contains(self::normText($quote), self::normText($txt)) && $labelled);
            $ans = $this->answers['meal:' . $x] ?? null;
            if ($inQuote || $labelled || $ans === 'tour') {
                $cur = $this->fieldValue('meal_basis');
                $this->addIntent([
                    'key' => 'field:meal_basis', 'kind' => 'field', 'field' => 'meal_basis', 'label' => self::FIELD_LABELS['meal_basis'], 'scope' => 'Meals',
                    'from' => $cur ?: '—', 'to' => $code, 'from_raw' => $cur, 'to_raw' => $code,
                    'note' => 'Every night that takes the tour\'s meal plan follows; a night whose meal plan was set on its own line or by a person keeps it.',
                ]);
                continue;
            }
            if ($ans !== null && ctype_digit((string) $ans)) {
                $this->nightMeal((int) $ans, $code, $txt, $newText);
                continue;
            }
            $opts = [['value' => 'tour', 'label' => 'The whole tour\'s meal plan (now ' . ($this->fieldValue('meal_basis') ?: 'not set') . ')']];
            foreach ((array) ($this->it['days'] ?? []) as $d) {
                $opts[] = ['value' => (string) $d['day_no'], 'label' => ucfirst($this->nightName((int) $d['day_no'])) . ' — ' . (($d['hotel_name_raw'] ?? '') ?: 'no hotel') . ' · ' . ($d['meal_plan'] ?: 'no meal plan')];
            }
            $this->questions['meal:' . $x] = ['id' => 'meal:' . $x, 'kind' => 'choice', 'text' => 'Which meal plan is “' . $txt . '” → “' . $newText . '”?', 'options' => $opts];
        }
    }

    private function nightMeal(int $n, string $code, string $old, string $new): void
    {
        $row = $this->nightRow($n);
        if ($row === null) {
            $this->refusals[] = ucfirst($this->nightName($n)) . ' is not a hotel night, so it has no meal plan.';
            return;
        }
        $this->addIntent([
            'key' => 'night:' . $n . ':meal', 'kind' => 'day', 'day_id' => (int) $row['id'], 'day_no' => $n, 'attr' => 'meal_plan',
            'label' => 'Meal plan', 'scope' => ucfirst($this->nightName($n)) . ($row['hotel_name_raw'] ? ' · ' . $row['hotel_name_raw'] : ''),
            'from' => $row['meal_plan'] ?: '—', 'to' => $code, 'from_raw' => $row['meal_plan'], 'to_raw' => $code,
            'note' => 'The night\'s published rate is read again for the new meal plan.',
        ]);
    }

    private function nightRow(int $n): ?array
    {
        foreach (array_merge((array) ($this->it['days'] ?? []), (array) ($this->it['departure_rows'] ?? [])) as $d) {
            if ((int) $d['day_no'] === $n) {
                return $d;
            }
        }
        return null;
    }

    // =====================================================================
    //  A night's hotel and area
    // =====================================================================

    /** The texts a night's hotel goes by: what the inquiry holds, the panel's name, the email's words. */
    private function hotelTexts(array $d, array $rd): array
    {
        $t = [];
        foreach ([(string) ($d['hotel_name_raw'] ?? ''), preg_replace('/^matched:\s*/i', '', (string) ($d['match_note'] ?? '')), (string) ($rd['hotel_name'] ?? '')] as $v) {
            $v = trim($v);
            if ($v !== '' && mb_strlen($v) >= 3 && stripos($v, 'no hotel') === false) {
                $t[mb_strtolower($v)] = $v;
            }
        }
        return array_values($t);
    }

    private function readNightValues(): void
    {
        // where a value of one night appears, per text, so a hotel on two nights is asked about once
        $found = [];
        foreach ($this->dayRows() as $n => $dr) {
            $d = $dr['row'];
            if ($d === null) {
                continue;   // the departure day has no hotel and no area of its own
            }
            foreach (['hotel' => $this->hotelTexts($d, $dr['raw']), 'area' => array_values(array_unique(array_filter([trim((string) ($d['area'] ?? '')), trim((string) ($dr['raw']['area'] ?? ''))], static fn ($v) => $v !== '' && mb_strlen($v) >= 3)))] as $attr => $texts) {
                // every place one of the night's names for it stands in A; names that overlap are one place
                $spans = [];
                foreach ($texts as $txt) {
                    $tt = self::tokens($txt);
                    foreach (self::find($this->ta, $tt) as $i) {
                        if ($this->inNight($i, $n)) {
                            $spans[] = [$i, $i + count($tt), $txt];
                        }
                    }
                }
                usort($spans, static fn ($a, $b) => [$a[0], -$a[1]] <=> [$b[0], -$b[1]]);
                $merged = [];
                foreach ($spans as $sp) {
                    $k = count($merged) - 1;
                    if ($k >= 0 && $sp[0] < $merged[$k][1]) {
                        if ($sp[1] > $merged[$k][1]) {
                            $merged[$k][1] = $sp[1];
                        }
                        continue;
                    }
                    $merged[] = $sp;
                }
                foreach ($merged as [$i, $e, $txt]) {
                    $hit = $this->changedIn($i, $e);
                    if (!$hit) {
                        continue;
                    }
                    // A town named in the day's words ("shopping in Colombo") is not the night's area
                    // statement while the area still stands, unchanged, elsewhere in that night's part —
                    // unless it sits on the hotel's own line ("Hotel: … – Colombo"), which is where the
                    // night is said to be. Those words change as the day's words only.
                    if ($attr === 'area' && !$this->onHotelLine($n, $d, $dr['raw'], $i) && $this->areaStandsElsewhere($n, $txt, $i, $e)) {
                        if ($this->newText($i, $e) !== '') {
                            $this->notes[] = '“' . ($this->textA($i, $e) ?: $txt) . '” in these words is where ' . $this->nightName($n) . ' is, and the night stays there: the words change as the day\'s words only. To move the night, change the town on the hotel\'s line (“Hotel: … – town”).';
                        }
                        continue;
                    }
                    $found[$attr . '|' . $i . '|' . $e][$n] = ['n' => $n, 'row' => $d, 'span' => [$i, $e], 'hit' => $hit, 'text' => $this->textA($i, $e) ?: $txt];
                }
            }
        }
        foreach ($found as $k => $byNight) {
            [$attr] = explode('|', $k);
            $first = reset($byNight);
            $this->claim($first['hit']);
            $nights = $byNight;
            if (count($byNight) > 1) {
                $qid = 'which:' . $attr . ':' . mb_strtolower($first['text']);
                $ans = ($this->answers['occ'] ?? null) === 'all' ? 'all' : ($this->answers[$qid] ?? null);
                if ($ans === 'all') {
                    // every night named
                } elseif ($ans !== null && ctype_digit((string) $ans) && isset($byNight[(int) $ans])) {
                    $nights = [(int) $ans => $byNight[(int) $ans]];
                } else {
                    $opts = [];
                    foreach ($byNight as $n => $f) {
                        $opts[] = ['value' => (string) $n, 'label' => ucfirst($this->nightName($n)) . ' only'];
                    }
                    $opts[] = ['value' => 'all', 'label' => 'All ' . count($byNight) . ' nights at “' . $first['text'] . '”'];
                    $this->questions[$qid] = ['id' => $qid, 'kind' => 'choice', 'text' => '“' . $first['text'] . '” is on ' . count($byNight) . ' nights. Which ' . ($attr === 'hotel' ? 'nights change hotel' : 'nights change') . '?', 'options' => $opts];
                    continue;
                }
            }
            $newText = $this->newText($first['span'][0], $first['span'][1]);
            foreach ($nights as $n => $f) {
                if ($attr === 'hotel') {
                    $this->nightHotel($n, $f['row'], $f['text'], $newText);
                } else {
                    $this->nightArea($n, $f['row'], $f['text'], $newText);
                }
            }
        }
    }

    /** Is A's word $i on a line of A that also names night $n's hotel? */
    private function onHotelLine(int $n, array $d, array $rd, int $i): bool
    {
        if (!isset($this->ta[$i])) {
            return false;
        }
        $at = $this->ta[$i][1];
        $ls = strrpos(substr($this->A, 0, $at), "\n");
        $ls = $ls === false ? 0 : $ls + 1;
        $le = strpos($this->A, "\n", $at);
        $le = $le === false ? strlen($this->A) : $le;
        foreach ($this->hotelTexts($d, $rd) as $h) {
            foreach (self::find($this->ta, self::tokens($h)) as $k) {
                if ($this->ta[$k][1] >= $ls && $this->ta[$k][1] < $le) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Does night $n's area ($txt) still stand, unchanged, somewhere else in that night's part — in the
     * words pasted, or in the email around them — besides A's words [$s, $e)?
     */
    private function areaStandsElsewhere(int $n, string $txt, int $s, int $e): bool
    {
        $tt = self::tokens($txt);
        $len = count($tt);
        if ($len === 0) {
            return false;
        }
        // in the words pasted
        foreach (self::find($this->ta, $tt) as $k) {
            if ($k < $e && $k + $len > $s) {
                continue;
            }
            if (!$this->inNight($k, $n) || $this->changedIn($k, $k + $len)) {
                continue;
            }
            return true;
        }
        // in the email, around the words pasted
        if ($this->occ === null || !isset($this->windows[$n])) {
            return false;
        }
        [$ws, $we] = $this->windows[$n];
        $aLen = count($this->ta);
        foreach (self::find($this->emailTok, $tt) as $p) {
            if ($p < $ws || $p >= $we) {
                continue;
            }
            $covered = false;
            foreach ($this->occList ?: [$this->occ] as $o) {
                if ($p + $len > $o && $p < $o + $aLen) {
                    $covered = true;   // inside the words pasted: looked at above
                }
            }
            if (!$covered) {
                return true;
            }
        }
        return false;
    }

    private function nightHotel(int $n, array $d, string $old, string $new): void
    {
        $scope = ucfirst($this->nightName($n));
        if ($new === '') {
            $this->refusals[] = 'The change takes the hotel off ' . $this->nightName($n) . '. A night keeps a hotel; to have one night fewer, change the dates, and the nights follow.';
            return;
        }
        if (!empty($d['day_no']) && $this->isDepartureRow($n)) {
            $this->refusals[] = ucfirst($this->nightName($n)) . ' falls on the departure day and takes no hotel. Settle it first with Make it the departure day, or It\'s an extra night.';
            return;
        }
        $qid = 'hotel:' . mb_strtolower(trim($new));
        $ans = $this->answers[$qid] ?? null;
        $hotel = null;
        if ($ans !== null && ctype_digit((string) $ans)) {
            $hotel = ($this->lk['hotelById'])((int) $ans);
        } elseif ($ans === 'none') {
            $this->refusals[] = 'Nothing changed on ' . $this->nightName($n) . ': the hotel “' . $new . '” was not found in Hotel Rates. It has to be in the panel before a night can be priced (Master Files → Supplier, then Hotel Rates).';
            return;
        }
        if ($hotel === null) {
            $look = ($this->lk['hotel'])($new);
            if (!empty($look['exact'])) {
                $hotel = $look['exact'];
            } else {
                $opts = [];
                foreach ((array) ($look['near'] ?? []) as $h) {
                    $opts[] = ['value' => (string) $h['id'], 'label' => $h['name'] . (!empty($h['location']) ? ' · ' . $h['location'] : '')];
                }
                $opts[] = ['value' => 'none', 'label' => 'None of these — the hotel is not in Hotel Rates yet'];
                $nights = isset($this->questions[$qid]) ? $this->questions[$qid]['nights'] : [];
                $nights[] = $this->nightName($n);
                $this->questions[$qid] = ['id' => $qid, 'kind' => 'choice', 'nights' => $nights, 'text' => (!empty($look['ambiguous']) ? 'More than one hotel in Hotel Rates goes by “' . $new . '”.' : 'No hotel in Hotel Rates is called exactly “' . $new . '”.') . ' Which hotel is meant for ' . implode(' and ', $nights) . '?', 'options' => $opts];
                return;
            }
        }
        $cur = (string) ($d['hotel_name_raw'] ?? '');
        $this->addIntent([
            'key' => 'night:' . $n . ':hotel', 'kind' => 'day', 'day_id' => (int) $d['id'], 'day_no' => $n, 'attr' => 'hotel_name_raw',
            'label' => 'Hotel', 'scope' => $scope, 'from' => $cur !== '' ? $cur : '—', 'to' => $hotel['name'], 'from_raw' => $cur, 'to_raw' => $hotel['name'], 'hotel_id' => (int) $hotel['id'],
            'note' => 'The room type is chosen again for the new hotel, and the night takes its published contract rate.' . (($d['origin'] ?? '') === 'human' ? ' The night was set by a person before.' : ''),
        ]);
    }

    private function isDepartureRow(int $n): bool
    {
        foreach ((array) ($this->it['departure_rows'] ?? []) as $r) {
            if ((int) $r['day_no'] === $n) {
                return true;
            }
        }
        return false;
    }

    private function nightArea(int $n, array $d, string $old, string $new): void
    {
        if ($new === '') {
            $this->refusals[] = 'The change takes the area off ' . $this->nightName($n) . '. Say where the night is instead.';
            return;
        }
        $cur = (string) ($d['area'] ?? '');
        $this->addIntent([
            'key' => 'night:' . $n . ':area', 'kind' => 'day', 'day_id' => (int) $d['id'], 'day_no' => $n, 'attr' => 'area',
            'label' => 'Area', 'scope' => ucfirst($this->nightName($n)), 'from' => $cur !== '' ? $cur : '—', 'to' => $new, 'from_raw' => $cur, 'to_raw' => $new,
            'note' => 'The legs for the tour are planned again for the new area.',
        ]);
    }

    // =====================================================================
    //  Visits and services already on the inquiry
    // =====================================================================

    private function readServices(): void
    {
        foreach ((array) ($this->it['services'] ?? []) as $s) {
            $n = (int) $s['day_no'];
            $texts = [];
            foreach ([(string) $s['title'], (string) ($s['source_title'] ?? '')] as $v) {
                $v = trim($v);
                if ($v !== '' && mb_strlen($v) >= 3) {
                    $texts[mb_strtolower($v)] = $v;
                }
            }
            foreach ($texts as $txt) {
                $tt = self::tokens($txt);
                foreach (self::find($this->ta, $tt) as $i) {
                    if (!$this->inNight($i, $n) && $this->occ !== null && isset($this->windows[$n])) {
                        continue;
                    }
                    $span = [$i, $i + count($tt)];
                    $hit = $this->changedIn($span[0], $span[1]);
                    if (!$hit) {
                        continue;
                    }
                    $this->claim($hit);
                    $newText = $this->newText($span[0], $span[1]);
                    $scope = ($n > 0 ? ucfirst($this->nightName($n)) : 'The tour');
                    if ($newText === '') {
                        $this->addIntent([
                            'key' => 'svc:' . $s['id'], 'kind' => 'service_remove', 'service_id' => (int) $s['id'], 'day_no' => $n,
                            'label' => 'Visit / service', 'scope' => $scope, 'from' => (string) $s['title'], 'to' => 'taken off', 'from_raw' => (string) $s['title'], 'to_raw' => null,
                            'note' => 'Taken off the programme and the costing. Reading the email again is switched off once this is applied, so it cannot come back that way.',
                        ]);
                    } else {
                        $this->serviceRename($s, $n, $scope, $newText);
                    }
                    continue 3;
                }
            }
            // Not named by its title in these words: the email's own words for it ("Final-day
            // shopping in Colombo" for "Colombo shopping"). Only their whole removal takes the line
            // off; words of it changed are the day's words, never a guess at a new visit.
            $q = trim((string) ($s['source_quote'] ?? ''));
            if (mb_strlen($q) < 6) {
                continue;
            }
            $qt = self::tokens($q);
            foreach (self::find($this->ta, $qt) as $i) {
                if (!$this->inNight($i, $n) && $this->occ !== null && isset($this->windows[$n])) {
                    continue;
                }
                $span = [$i, $i + count($qt)];
                $hit = $this->changedIn($span[0], $span[1]);
                if (!$hit || $this->newText($span[0], $span[1]) !== '') {
                    continue;
                }
                $this->claim($hit);
                $this->addIntent([
                    'key' => 'svc:' . $s['id'], 'kind' => 'service_remove', 'service_id' => (int) $s['id'], 'day_no' => $n,
                    'label' => 'Visit / service', 'scope' => $n > 0 ? ucfirst($this->nightName($n)) : 'The tour', 'from' => (string) $s['title'], 'to' => 'taken off', 'from_raw' => (string) $s['title'], 'to_raw' => null,
                    'note' => 'The email\'s words for it (“' . $q . '”) are taken out, so it is taken off the programme and the costing. Reading the email again is switched off once this is applied, so it cannot come back that way.',
                ]);
                continue 2;
            }
        }
    }

    private function serviceRename(array $s, int $n, string $scope, string $newText): void
    {
        $hit = ($this->lk['visit'])($newText);
        $this->addIntent([
            'key' => 'svc:' . $s['id'], 'kind' => 'service_rename', 'service_id' => (int) $s['id'], 'day_no' => $n,
            'label' => 'Visit / service', 'scope' => $scope, 'from' => (string) $s['title'], 'to' => $hit ? $hit['name'] : $newText, 'from_raw' => (string) $s['title'], 'to_raw' => $hit ? $hit['name'] : $newText,
            'master' => $hit,
            'note' => $hit ? ($hit['kind'] === 'free' ? 'A free visit from the master: on the programme, never costed.' : 'Priced from Paid Activities & Services.') : 'Not on either list (Free Visits, Paid Activities & Services) under this name, so it is shown as not on the list and not priced until it is matched.',
        ]);
    }

    // =====================================================================
    //  Words of a day, a request, the departure day
    // =====================================================================

    /**
     * Where a longer piece of the email — a day's programme, a request, the departure day — sits
     * inside A, in A's words. Found through the email when A was found there; otherwise by the
     * piece being inside A, or A inside the piece.
     */
    private function containerSpan(string $quote): ?array
    {
        $qt = self::tokens($quote);
        if (!$qt) {
            return null;
        }
        if ($this->occ !== null) {
            foreach ($this->occList ?: [$this->occ] as $o) {
                foreach (self::find($this->emailTok, $qt) as $h) {
                    $s = max($h, $o) - $o;
                    $e = min($h + count($qt), $o + count($this->ta)) - $o;
                    if ($s < $e) {
                        return [$s, $e];
                    }
                }
            }
            return null;
        }
        $in = self::find($this->ta, $qt);
        if ($in) {
            return [$in[0], $in[0] + count($qt)];
        }
        if (self::find($qt, $this->ta)) {
            return [0, count($this->ta)];
        }
        return null;
    }

    private function readContainers(): void
    {
        // a day's own words (the programme the email gives for it)
        $done = [];
        foreach ($this->dayRows() as $n => $dr) {
            $q = trim((string) $dr['quote']);
            if ($q === '') {
                continue;
            }
            $span = $this->containerSpan($q);
            if ($span === null || !($hit = $this->changedIn($span[0], $span[1], true))) {
                continue;
            }
            $this->claim($hit);
            $done[$n] = true;
            $old = $this->textA($span[0], $span[1]);
            $new = $this->newText($span[0], $span[1], true);
            if (!empty($dr['departure_day']) || $dr['row'] === null) {
                $this->departureWords($old, $new);
                continue;
            }
            $this->dayWords($n, $dr['row'], $old, $new);
        }
        // a day's words below its first line, when the reader quoted only that line: the change lies in
        // the night's part of the email and the night's words carry it — they follow, and nothing else
        if ($this->occ !== null) {
            foreach ($this->dayRows() as $n => $dr) {
                if (isset($done[$n]) || !isset($this->windows[$n])) {
                    continue;
                }
                $lo = null;
                $hi = null;
                foreach ($this->segs as $k => [$type, $aS, $aE, $bS, $bE]) {
                    if ($type !== 'ch' || !empty($this->refused[$k]) || !$this->inNight($aE > $aS ? $aS : max(0, $aS - 1), $n)) {
                        continue;
                    }
                    $lo = $lo === null ? $aS : min($lo, $aS);
                    $hi = $hi === null ? $aE : max($hi, $aE);
                }
                if ($lo === null) {
                    continue;
                }
                if ($hi <= $lo) {
                    // words put in, none taken out: the words either side say where
                    $lo = max(0, $lo - 1);
                    $hi = min(count($this->ta), $hi + 1);
                }
                $old = $this->textA($lo, $hi);
                $new = $this->newText($lo, $hi, true);
                if (!empty($dr['departure_day']) || $dr['row'] === null) {
                    if (self::replaceWords((string) ($this->it['departure_narrative'] ?? ''), $old, $new) !== null) {
                        $this->departureWords($old, $new);
                    }
                    continue;
                }
                if (self::replaceWords((string) ($dr['row']['narrative'] ?? ''), $old, $new) !== null) {
                    $this->dayWords($n, $dr['row'], $old, $new);
                }
            }
        }
        // a request or a question the email made
        foreach ((array) ($this->it['requests'] ?? []) as $r) {
            $q = trim((string) ($r['quote_text'] ?? ''));
            $span = $q !== '' ? $this->containerSpan($q) : null;
            if ($span === null) {
                $t = trim((string) $r['title']);
                $tt = self::tokens($t);
                $in = mb_strlen($t) >= 6 ? self::find($this->ta, $tt) : [];
                $span = $in ? [$in[0], $in[0] + count($tt)] : null;
            }
            if ($span === null || !($hit = $this->changedIn($span[0], $span[1], true))) {
                continue;
            }
            $this->claim($hit);
            $new = $this->newText($span[0], $span[1], true);
            $scope = ucfirst((string) ($r['kind'] ?? 'request'));
            if ($new === '') {
                $this->addIntent([
                    'key' => 'req:' . $r['id'], 'kind' => 'request_drop', 'request_id' => (int) $r['id'], 'label' => 'Customer request', 'scope' => $scope,
                    'from' => (string) $r['title'], 'to' => 'dropped', 'from_raw' => (string) $r['title'], 'to_raw' => null,
                    'note' => 'Marked as dropped, with this Edit Request as the reason; the line stays in the list.',
                ]);
                continue;
            }
            // the request's own short title: where its words are in A, they change as A's words do
            $title = trim((string) $r['title']);
            $tt = self::tokens($title);
            $at = $tt ? self::find($this->ta, $tt) : [];
            $newTitle = $at ? $this->newText($at[0], $at[0] + count($tt)) : '';
            $newTitle = $newTitle !== '' ? $newTitle : $new;
            $this->addIntent([
                'key' => 'req:' . $r['id'], 'kind' => 'request', 'request_id' => (int) $r['id'], 'label' => 'Customer request', 'scope' => $scope,
                'from' => $title, 'to' => mb_substr($newTitle, 0, 255), 'from_raw' => $title, 'to_raw' => mb_substr($newTitle, 0, 255), 'quote' => $new,
                'note' => 'The request now carries the new words: “' . mb_substr($new, 0, 160) . '”.',
            ]);
        }
    }

    private function dayWords(int $n, array $d, string $old, string $new): void
    {
        $narr = (string) ($d['narrative'] ?? '');
        $to = self::replaceWords($narr, $old, $new);
        if ($to === null) {
            $this->notes[] = ucfirst($this->nightName($n)) . '\'s wording does not carry these words, so it is left as it is.';
            return;
        }
        $this->addIntent([
            'key' => 'night:' . $n . ':words', 'kind' => 'day', 'day_id' => (int) $d['id'], 'day_no' => $n, 'attr' => 'narrative',
            'label' => 'The day\'s programme (the email\'s words)', 'scope' => ucfirst($this->nightName($n)), 'from' => $narr, 'to' => $to, 'from_raw' => $narr, 'to_raw' => $to,
            'note' => trim((string) ($d['client_text'] ?? '')) !== '' ? 'This night has its own wording for the client, written by a person: that is not changed — check it after.' : '',
        ]);
    }

    private function departureWords(string $old, string $new): void
    {
        $narr = (string) ($this->it['departure_narrative'] ?? '');
        $to = $narr !== '' ? self::replaceWords($narr, $old, $new) : null;
        if ($to === null) {
            $this->notes[] = 'The departure day\'s wording does not carry these words, so it is left as it is.';
            return;
        }
        $this->addIntent([
            'key' => 'dep:words', 'kind' => 'departure_words', 'label' => 'The departure day\'s programme (the email\'s words)', 'scope' => 'The departure day',
            'from' => $narr, 'to' => $to, 'from_raw' => $narr, 'to_raw' => $to,
            'note' => trim((string) ($this->it['departure_text'] ?? '')) !== '' ? 'The departure day has its own wording for the client, written by a person: that is not changed — check it after.' : '',
        ]);
    }

    /** $text with the words of $old replaced by $new (matched word by word, spacing and quote marks aside); null when $old is not in it. */
    public static function replaceWords(string $text, string $old, string $new): ?string
    {
        $tt = self::tokens($text);
        $ot = self::tokens($old);
        $hits = self::find($tt, $ot);
        if (!$hits || !$ot) {
            return null;
        }
        $s = $tt[$hits[0]][1];
        $e = $tt[$hits[0] + count($ot) - 1][2];
        $out = substr($text, 0, $s) . $new . substr($text, $e);
        return trim(preg_replace('/[ \t]{2,}/', ' ', $out));
    }

    // =====================================================================
    //  Passengers, crew, vehicle
    // =====================================================================

    private function readPassengers(): void
    {
        foreach ((array) ($this->it['passengers'] ?? []) as $p) {
            $name = trim((string) $p['full_name']);
            $tt = self::tokens($name);
            if (mb_strlen($name) < 3) {
                continue;
            }
            foreach (self::find($this->ta, $tt) as $i) {
                $span = [$i, $i + count($tt)];
                if (!($hit = $this->changedIn($span[0], $span[1]))) {
                    continue;
                }
                $this->claim($hit);
                $new = $this->newText($span[0], $span[1]);
                if ($new === '') {
                    $this->refusals[] = 'Taking a passenger off is done on the passenger list, where the room they are in is settled too.';
                    continue 2;
                }
                $this->addIntent([
                    'key' => 'pax:' . $p['id'], 'kind' => 'passenger', 'pax_id' => (int) $p['id'], 'label' => 'Passenger name', 'scope' => 'Passengers',
                    'from' => $name, 'to' => $new, 'from_raw' => $name, 'to_raw' => $new, 'note' => '',
                ]);
                continue 2;
            }
        }
    }

    private function readCrew(): void
    {
        $roles = (array) ($this->lk['crewRoles'] ?? []);
        $langs = [];
        foreach ($roles as $r) {
            if (!empty($r['language'])) {
                $langs[mb_strtolower($r['language'])] = $r['language'];
            }
        }
        foreach (['english', 'japanese', 'german', 'french', 'chinese', 'korean', 'italian', 'spanish', 'russian', 'hindi', 'arabic', 'dutch'] as $l) {
            $langs[$l] = $langs[$l] ?? ucfirst($l);
        }
        $rx = '/\b(' . implode('|', array_map(static fn ($l) => preg_quote($l, '/'), array_keys($langs))) . ')(?:\s*[-\s]\s*speaking)?\s+((?:driver|guide|chauffeur)(?:\s*[\/&-]\s*(?:driver|guide|chauffeur))*|driver[\s-]*guide|guide[\s-]*driver)\b/iu';
        if (!preg_match_all($rx, $this->A, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return;
        }
        foreach ($mm as $m) {
            $langSpan = $this->spanOf($m[1][1], $m[1][1] + strlen($m[1][0]));
            $all = $this->spanOf($m[0][1], $m[0][1] + strlen($m[0][0]));
            if ($langSpan === null || $all === null || !($hit = $this->changedIn($all[0], $all[1]))) {
                continue;
            }
            $this->claim($hit);
            $newText = $this->newText($all[0], $all[1]);
            if (!preg_match($rx, $newText, $nm)) {
                $this->refusals[] = 'Taking a driver or a guide off, or adding one, is done under Driver allocation or Guide allocation on the inquiry. An Edit Request can change the language.';
                continue;
            }
            $oldLang = mb_strtolower($m[1][0]);
            $newLang = mb_strtolower($nm[1]);
            if ($oldLang === $newLang) {
                continue;
            }
            $kinds = [];
            $w = mb_strtolower($m[2][0]);
            foreach (['driver' => 'driver', 'chauffeur' => 'driver', 'guide' => 'guide'] as $word => $kind) {
                if (str_contains($w, $word)) {
                    $kinds[$kind] = true;
                }
            }
            foreach ((array) ($this->it['crew'] ?? []) as $c) {
                $kind = (string) ($c['kind'] ?? '');
                if (!isset($kinds[$kind])) {
                    continue;
                }
                $cands = array_values(array_filter($roles, static fn ($r) => $r['kind'] === $kind && (int) ($r['active'] ?? 1) === 1 && mb_strtolower((string) ($r['language'] ?? '')) === $newLang));
                if (count($cands) !== 1) {
                    $this->refusals[] = 'The crew master has ' . (count($cands) ?: 'no') . ' ' . $kind . ' role' . (count($cands) === 1 ? '' : 's') . ' speaking ' . ucfirst($newLang) . ', so the ' . $kind . ' is not changed. Choose the role under ' . ucfirst($kind) . ' allocation on the inquiry.';
                    continue;
                }
                $to = $cands[0];
                if ($to['code'] === $c['role_code']) {
                    continue;
                }
                $this->addIntent([
                    'key' => 'crew:' . $c['role_code'], 'kind' => 'crew', 'from_code' => (string) $c['role_code'], 'to_code' => (string) $to['code'],
                    'label' => ucfirst($kind), 'scope' => 'Driver and guide', 'from' => (string) $c['role_name'], 'to' => (string) $to['name'], 'from_raw' => (string) $c['role_code'], 'to_raw' => (string) $to['code'],
                    'note' => 'The bata per day is the Crew Bata master\'s for the new role; nothing is typed.',
                ]);
            }
        }
    }

    private function readVehicle(): void
    {
        $classes = array_values(array_filter((array) ($this->lk['vehicleClasses'] ?? []), static fn ($c) => (int) ($c['active'] ?? 1) === 1));
        if (!$classes) {
            return;
        }
        $words = [];
        foreach ($classes as $c) {
            foreach (array_merge([(string) $c['name']], array_map('trim', explode(',', (string) ($c['match_words'] ?? '')))) as $w) {
                if ($w !== '' && !ctype_digit($w) && mb_strlen($w) >= 3) {
                    $words[mb_strtolower($w)][$c['code']] = true;
                }
            }
        }
        uksort($words, static fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $rx = '/\b(' . implode('|', array_map(static fn ($w) => preg_quote($w, '/'), array_keys($words))) . ')\b/iu';
        $mm = [];
        preg_match_all($rx, $this->A, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        // the old words say only "vehicle" or "transport" and the new ones name a size: that size is meant
        if (preg_match_all('/\b(vehicle|transport|car)\b/iu', $this->A, $gm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($gm as $g) {
                $known = false;
                foreach ($mm as $m) {
                    if ($m[0][1] <= $g[0][1] && $m[0][1] + strlen($m[0][0]) >= $g[0][1] + strlen($g[0][0])) {
                        $known = true;
                    }
                }
                if (!$known) {
                    $mm[] = $g;
                }
            }
        }
        if (!$mm) {
            return;
        }
        foreach ($mm as $m) {
            $span = $this->spanOf($m[0][1], $m[0][1] + strlen($m[0][0]));
            if ($span === null || !($hit = $this->changedIn($span[0], $span[1]))) {
                continue;
            }
            $this->claim($hit);
            $newText = $this->newText($span[0], $span[1]);
            $codes = [];
            if ($newText !== '' && preg_match_all($rx, $newText, $nm)) {
                foreach ($nm[1] as $w) {
                    $codes += $words[mb_strtolower($w)] ?? [];
                }
            }
            if (count($codes) !== 1) {
                $this->questions['vehicle'] = ['id' => 'vehicle', 'kind' => 'info', 'text' => '“' . $newText . '” is not exactly one of the vehicle sizes the company uses (' . implode(', ', array_column($classes, 'name')) . '). Name the size.', 'options' => []];
                return;
            }
            $vehicles = (array) ($this->it['vehicles'] ?? []);
            if (count($vehicles) !== 1) {
                $this->refusals[] = 'This inquiry has ' . count($vehicles) . ' vehicle lines; change the one meant under Vehicles on the inquiry.';
                return;
            }
            $v = $vehicles[0];
            $code = array_key_first($codes);
            $to = null;
            foreach ($classes as $c) {
                if ($c['code'] === $code) {
                    $to = $c;
                }
            }
            if ($to === null || $to['code'] === $v['class_code']) {
                return;
            }
            $this->addIntent([
                'key' => 'vehicle', 'kind' => 'vehicle', 'line' => $v, 'to_code' => (string) $to['code'], 'label' => 'Vehicle', 'scope' => 'Transport',
                'from' => (string) ($v['type'] ?: $v['class_code']), 'to' => (string) $to['name'], 'from_raw' => (string) $v['class_code'], 'to_raw' => (string) $to['code'],
                'note' => 'The rate per kilometre is the Transport Rates master\'s for the new size.',
            ]);
            return;
        }
    }

    // =====================================================================
    //  Plain text fields: the hotel category, the group name, the agent's reference, the rooms note
    // =====================================================================

    private function readTextFields(): void
    {
        foreach (['hotel_class', 'tour_name', 'agent_reference', 'rooms_note'] as $f) {
            $vals = array_values(array_unique(array_filter([trim((string) $this->fieldValue($f)), trim((string) $this->emailValue($f))], static fn ($v) => $v !== '' && mb_strlen($v) >= 2)));
            foreach ($vals as $v) {
                $tt = self::tokens($v);
                foreach (self::find($this->ta, $tt) as $i) {
                    $span = [$i, $i + count($tt)];
                    if (!($hit = $this->changedIn($span[0], $span[1]))) {
                        continue;
                    }
                    // a room count is the rooms' own; a word already explained by something else is not this field's
                    $fresh = array_values(array_filter($hit, fn ($k) => empty($this->claimed[$k])));
                    if (!$fresh) {
                        continue;
                    }
                    $this->claim($hit);
                    $new = $this->newText($span[0], $span[1]);
                    $cur = (string) ($this->fieldValue($f) ?? '');
                    $this->addIntent([
                        'key' => 'field:' . $f, 'kind' => 'field', 'field' => $f, 'label' => self::FIELD_LABELS[$f], 'scope' => 'The inquiry',
                        'from' => $cur !== '' ? $cur : '—', 'to' => $new !== '' ? $new : '(empty)', 'from_raw' => $cur, 'to_raw' => $new === '' ? null : $new,
                        'note' => $this->setBy($f) ? 'Now “' . $cur . '”, ' . $this->setBy($f) . '.' : '',
                    ]);
                    continue 3;
                }
            }
        }
    }

    // =====================================================================
    //  Visits the new words add
    // =====================================================================

    private function readAdditions(): void
    {
        $masters = (array) ($this->lk['masters'] ?? []);
        if (!$masters) {
            return;
        }
        foreach ($this->segs as $k => [$type, $aS, $aE, $bS, $bE]) {
            if ($type !== 'ch' || $bE <= $bS) {
                continue;
            }
            $bTok = array_slice($this->tb, $bS, $bE - $bS);
            $aTok = array_slice($this->ta, $aS, $aE - $aS);
            foreach ($masters as $mv) {
                $mt = self::tokens((string) $mv['name']);
                if (count($mt) < 2 && mb_strlen((string) $mv['name']) < 6) {
                    continue;   // a one-word name ("Kandy") is a place far more often than a visit
                }
                if (!self::find($bTok, $mt) || self::find($aTok, $mt)) {
                    continue;
                }
                // which day: the day whose words the change sits in
                $day = null;
                foreach ($this->dayRows() as $n => $dr) {
                    $q = trim((string) $dr['quote']);
                    $span = $q !== '' ? $this->containerSpan($q) : null;
                    if ($span !== null && $aS >= $span[0] && $aS <= $span[1]) {
                        $day = $n;
                        break;
                    }
                }
                if ($day === null) {
                    $day = $this->inAnyNight(max(0, min($aS, count($this->ta) - 1)));
                }
                $qid = 'visitday:' . mb_strtolower((string) $mv['name']);
                if ($day === null) {
                    $ans = $this->answers[$qid] ?? null;
                    if ($ans === 'skip') {
                        $this->claim([$k]);
                        continue;
                    }
                    if ($ans !== null && ctype_digit((string) $ans)) {
                        $day = (int) $ans;
                    } else {
                        $opts = [];
                        foreach ($this->dayRows() as $n => $dr) {
                            $opts[] = ['value' => (string) $n, 'label' => ucfirst($this->nightName($n))];
                        }
                        $opts[] = ['value' => 'skip', 'label' => 'It is not a visit to add — leave it out'];
                        $this->questions[$qid] = ['id' => $qid, 'kind' => 'choice', 'text' => '“' . $mv['name'] . '” is on the ' . ($mv['kind'] === 'free' ? 'Free Visits' : 'Paid Activities & Services') . ' list. Which day is it for?', 'options' => $opts];
                        $this->claim([$k]);
                        continue;
                    }
                }
                $already = false;
                foreach ((array) ($this->it['services'] ?? []) as $s) {
                    if ((int) $s['day_no'] === $day && self::normText((string) $s['title']) === self::normText((string) $mv['name'])) {
                        $already = true;
                    }
                }
                $this->claim([$k]);
                if ($already) {
                    continue;
                }
                $this->addIntent([
                    'key' => 'add:' . $day . ':' . $mv['kind'] . ':' . $mv['id'], 'kind' => 'service_add', 'day_no' => $day, 'master' => $mv,
                    'label' => 'Visit / service', 'scope' => ucfirst($this->nightName($day)), 'from' => 'not on the programme', 'to' => (string) $mv['name'], 'from_raw' => null, 'to_raw' => (string) $mv['name'],
                    'note' => $mv['kind'] === 'free' ? 'A free visit from the master: on the programme and the itinerary, never costed.' : 'From Paid Activities & Services, priced from the list.',
                ]);
            }
        }
    }

    // =====================================================================
    //  Figures that are never an Edit Request's to change
    // =====================================================================

    /** A price, a rate, bata, the markup or kilometres in a change: refused up front, so no other reading takes the number. */
    private function refuseFigures(): void
    {
        foreach ($this->segs as $k => [$type, $aS, $aE, $bS, $bE]) {
            if ($type !== 'ch') {
                continue;
            }
            // the words either side of the change count too: "USD 70" → "USD 60" changes only the number
            $ctxA = $this->textA(max(0, $aS - 3), min(count($this->ta), $aE + 3));
            $ctxB = $this->textB(max(0, $bS - 3), min(count($this->tb), $bE + 3));
            $changed = $this->textA($aS, $aE) . ' ' . $this->textB($bS, $bE);
            if (!preg_match('/\d/', $changed) && !preg_match(self::MONEY, $changed) && !preg_match(self::KM, $changed)) {
                continue;
            }
            if (preg_match(self::MONEY, $ctxA . ' ' . $ctxB) && (preg_match('/\d/', $changed) || preg_match(self::MONEY, $changed))) {
                $this->claimed[$k] = true;
                $this->refused[$k] = true;
                $this->refusals[] = 'Prices, rates, bata and the markup are never changed by an Edit Request: hotel rates come only from Hotel Rates (published contract rates), entrance fees and activities from their lists, bata from Crew Bata, and the markup is Management\'s under Pricing.';
            } elseif (preg_match(self::KM, $ctxA . ' ' . $ctxB)) {
                $this->claimed[$k] = true;
                $this->refused[$k] = true;
                $this->refusals[] = 'Kilometres come from the driving record or Google. Typing them over is done on the Mileage card and needs the Mileage override privilege (Management).';
            }
        }
    }

    // =====================================================================
    //  What nothing explains
    // =====================================================================

    private function unclaimed(): void
    {
        $left = [];
        foreach ($this->segs as $k => [$type, $aS, $aE, $bS, $bE]) {
            if ($type !== 'ch' || !empty($this->claimed[$k])) {
                continue;
            }
            $a = $this->textA($aS, $aE);
            $b = $this->textB($bS, $bE);
            // punctuation and spacing alone is not a change to anything
            if (preg_replace('/[^\p{L}\p{N}]+/u', '', $a . $b) === '') {
                continue;
            }
            $left[$k] = [$a, $b];
        }
        if (!$left) {
            return;
        }
        foreach ($left as $k => [$a, $b]) {
            $both = $a . ' ' . $b;
            if (preg_match(self::MONEY, $both)) {
                $this->refusals[] = 'Prices, rates, bata and the markup are never changed by an Edit Request: hotel rates come only from Hotel Rates (published contract rates), entrance fees and activities from their lists, bata from Crew Bata, and the markup is Management\'s under Pricing.';
                continue;
            }
            if (preg_match(self::KM, $both)) {
                $this->refusals[] = 'Kilometres come from the driving record or Google. Typing them over is done on the Mileage card and needs the Mileage override privilege (Management).';
                continue;
            }
            $label = ($a !== '' ? '“' . $a . '”' : 'nothing') . ' → ' . ($b !== '' ? '“' . $b . '”' : 'nothing');
            $qid = 'what:' . md5(mb_strtolower($a . '|' . $b));
            $ans = $this->answers[$qid] ?? null;
            if ($ans === 'skip') {
                $this->notes[] = $label . ' was left out: you said it changes nothing on the inquiry.';
                continue;
            }
            if (is_string($ans) && $ans !== '' && $this->applyTarget($ans, $b, $a)) {
                continue;
            }
            $this->askTarget($qid, 'The change ' . $label . ' is not any working value I can find. What does it change?', true);
        }
    }

    /** The question "what is this about", with every working value that could be. */
    private function askTarget(string $qid, string $text, bool $withSkip = false): void
    {
        $ans = $this->answers[$qid] ?? null;
        if (is_string($ans) && $ans !== '' && $ans !== 'skip' && $qid === 'target' && $this->applyTarget($ans, $this->B, $this->A)) {
            return;
        }
        $opts = $withSkip ? [['value' => 'skip', 'label' => 'Nothing — leave it out', 'group' => '']] : [];
        foreach (self::FIELD_LABELS as $f => $l) {
            $cur = $this->fieldValue($f);
            $opts[] = ['value' => 'field:' . $f, 'label' => $l . ' (now ' . (in_array($f, ['arrival_date', 'departure_date'], true) ? self::pretty(self::iso($cur)) : ($cur === null || $cur === '' ? '—' : (string) $cur)) . ')', 'group' => 'The inquiry'];
        }
        $opts[] = ['value' => 'rooms', 'label' => 'Rooms (now ' . self::mixWords(['sgl' => $this->it['rooms_sgl'] ?? 0, 'dbl' => $this->it['rooms_dbl'] ?? 0, 'twn' => $this->it['rooms_twn'] ?? 0, 'tpl' => $this->it['rooms_tpl'] ?? 0]) . ')', 'group' => 'The inquiry'];
        foreach ((array) ($this->it['days'] ?? []) as $d) {
            $n = (int) $d['day_no'];
            $g = ucfirst($this->nightName($n));
            $opts[] = ['value' => 'night:' . $n . ':hotel', 'label' => $g . ' — hotel (now ' . (($d['hotel_name_raw'] ?? '') ?: '—') . ')', 'group' => 'Nights'];
            $opts[] = ['value' => 'night:' . $n . ':meal', 'label' => $g . ' — meal plan (now ' . (($d['meal_plan'] ?? '') ?: '—') . ')', 'group' => 'Nights'];
            $opts[] = ['value' => 'night:' . $n . ':area', 'label' => $g . ' — area (now ' . (($d['area'] ?? '') ?: '—') . ')', 'group' => 'Nights'];
            $opts[] = ['value' => 'addvisit:' . $n, 'label' => $g . ' — add a visit from the lists', 'group' => 'Nights'];
        }
        if (is_array($this->it['departure_day'] ?? null)) {
            $opts[] = ['value' => 'addvisit:' . (int) $this->it['departure_day']['day_no'], 'label' => 'The departure day — add a visit from the lists', 'group' => 'Nights'];
        }
        foreach ((array) ($this->it['services'] ?? []) as $s) {
            $opts[] = ['value' => 'svc:' . $s['id'], 'label' => ((int) $s['day_no'] > 0 ? ucfirst($this->nightName((int) $s['day_no'])) : 'The tour') . ' — ' . $s['title'], 'group' => 'Visits and services'];
        }
        foreach ((array) ($this->it['requests'] ?? []) as $r) {
            $opts[] = ['value' => 'req:' . $r['id'], 'label' => 'Request — ' . $r['title'], 'group' => 'Customer requests'];
        }
        $opts[] = ['value' => 'req:new', 'label' => 'A new customer request', 'group' => 'Customer requests'];
        foreach ((array) ($this->it['passengers'] ?? []) as $p) {
            $opts[] = ['value' => 'pax:' . $p['id'], 'label' => 'Passenger — ' . $p['full_name'], 'group' => 'Passengers'];
        }
        if (count((array) ($this->it['vehicles'] ?? [])) === 1) {
            $v = $this->it['vehicles'][0];
            $opts[] = ['value' => 'vehicle', 'label' => 'Vehicle size (now ' . ($v['type'] ?: $v['class_code']) . ')', 'group' => 'Transport and crew'];
        }
        foreach ((array) ($this->it['crew'] ?? []) as $c) {
            $opts[] = ['value' => 'crew:' . $c['role_code'], 'label' => ucfirst((string) $c['kind']) . ' (now ' . $c['role_name'] . ')', 'group' => 'Transport and crew'];
        }
        $this->questions[$qid] = ['id' => $qid, 'kind' => 'target', 'text' => $text, 'options' => $opts];
    }

    /** A person said what the words are about: the new words are read for that value. */
    private function applyTarget(string $target, string $newText, string $oldText): bool
    {
        $newText = trim(self::stripLabel($newText, $oldText));
        if (str_starts_with($target, 'field:')) {
            $f = substr($target, 6);
            if (!isset(self::FIELD_LABELS[$f])) {
                return false;
            }
            $cur = $this->fieldValue($f);
            if (in_array($f, ['arrival_date', 'departure_date'], true)) {
                $d = self::dateExprs($newText);
                $iso = count($d) === 1 && count($d[0]['dates']) === 1 ? $d[0]['dates'][0]['iso'] : null;
                if ($iso === null) {
                    $this->questions['targetval:' . $f] = ['id' => 'targetval:' . $f, 'kind' => 'info', 'text' => 'For the ' . strtolower(self::FIELD_LABELS[$f]) . ', the Change to box should hold one date with its year — “' . $newText . '” does not.', 'options' => []];
                    return true;
                }
                $this->newDates[$f] = $iso;
                $this->addIntent(['key' => 'field:' . $f, 'kind' => 'field', 'field' => $f, 'label' => self::FIELD_LABELS[$f], 'scope' => 'Dates', 'from' => self::pretty(self::iso($cur)), 'to' => self::pretty($iso), 'from_raw' => self::iso($cur), 'to_raw' => $iso, 'note' => '']);
                return true;
            }
            if (in_array($f, ['pax_adults', 'pax_children', 'pax_total'], true)) {
                if (!preg_match('/^\D*(\d{1,3})\D*$/', $newText, $m)) {
                    $this->questions['targetval:' . $f] = ['id' => 'targetval:' . $f, 'kind' => 'info', 'text' => 'For ' . strtolower(self::FIELD_LABELS[$f]) . ', the Change to box should hold one number.', 'options' => []];
                    return true;
                }
                $this->addIntent(['key' => 'field:' . $f, 'kind' => 'field', 'field' => $f, 'label' => self::FIELD_LABELS[$f], 'scope' => 'The party', 'from' => $cur === null ? '—' : (string) $cur, 'to' => $m[1], 'from_raw' => self::intOrNull($cur), 'to_raw' => (int) $m[1], 'note' => '']);
                return true;
            }
            if ($f === 'meal_basis') {
                $mm = $this->mealMatches($newText);
                $code = $mm ? self::mealCode($mm[0][1]) : null;
                if ($code === null) {
                    $this->questions['targetval:' . $f] = ['id' => 'targetval:' . $f, 'kind' => 'info', 'text' => 'The meal plan should be RO, BB, HB, FB or AI.', 'options' => []];
                    return true;
                }
                $this->addIntent(['key' => 'field:meal_basis', 'kind' => 'field', 'field' => 'meal_basis', 'label' => self::FIELD_LABELS['meal_basis'], 'scope' => 'Meals', 'from' => $cur ?: '—', 'to' => $code, 'from_raw' => $cur, 'to_raw' => $code, 'note' => '']);
                return true;
            }
            $this->addIntent(['key' => 'field:' . $f, 'kind' => 'field', 'field' => $f, 'label' => self::FIELD_LABELS[$f], 'scope' => 'The inquiry', 'from' => $cur === null || $cur === '' ? '—' : (string) $cur, 'to' => $newText !== '' ? $newText : '(empty)', 'from_raw' => $cur, 'to_raw' => $newText === '' ? null : $newText, 'note' => '']);
            return true;
        }
        if ($target === 'rooms') {
            $new = [];
            if (preg_match_all(self::ROOM_RX, $newText, $nm, PREG_SET_ORDER)) {
                foreach ($nm as $m) {
                    $new[self::roomType($m[2])] = self::countOf($m[1]);
                }
            }
            if (!$new) {
                $this->questions['targetval:rooms'] = ['id' => 'targetval:rooms', 'kind' => 'info', 'text' => 'The rooms should say how many of each, like “1 double and 1 single room”.', 'options' => []];
                return true;
            }
            if (!empty($this->it['groups'])) {
                $this->refusals[] = 'This party is split into groups, and each group has its own rooms: change them under Groups on the inquiry.';
                return true;
            }
            $cur = ['sgl' => (int) ($this->it['rooms_sgl'] ?? 0), 'dbl' => (int) ($this->it['rooms_dbl'] ?? 0), 'twn' => (int) ($this->it['rooms_twn'] ?? 0), 'tpl' => (int) ($this->it['rooms_tpl'] ?? 0)];
            $to = ['sgl' => 0, 'dbl' => 0, 'twn' => 0, 'tpl' => 0];
            foreach ($new as $k => $v) {
                $to[$k] = $v;
            }
            $this->addIntent(['key' => 'rooms', 'kind' => 'rooms', 'label' => 'Rooms', 'scope' => 'The party', 'from' => self::mixWords($cur), 'to' => self::mixWords($to), 'from_raw' => $cur, 'to_raw' => $to, 'note' => '']);
            return true;
        }
        if (preg_match('/^night:(\d+):(hotel|meal|area)$/', $target, $m)) {
            $n = (int) $m[1];
            $row = $this->nightRow($n);
            if ($row === null) {
                return false;
            }
            if ($m[2] === 'hotel') {
                $this->nightHotel($n, $row, (string) $row['hotel_name_raw'], $newText);
            } elseif ($m[2] === 'area') {
                $this->nightArea($n, $row, (string) $row['area'], $newText);
            } else {
                $mm = $this->mealMatches($newText);
                $code = $mm ? self::mealCode($mm[0][1]) : null;
                if ($code === null) {
                    $this->questions['targetval:' . $target] = ['id' => 'targetval:' . $target, 'kind' => 'info', 'text' => 'The meal plan should be RO, BB, HB, FB or AI.', 'options' => []];
                    return true;
                }
                $this->nightMeal($n, $code, (string) $row['meal_plan'], $newText);
            }
            return true;
        }
        if (preg_match('/^addvisit:(\d+)$/', $target, $m)) {
            $hit = ($this->lk['visit'])($newText);
            if (!$hit) {
                $this->questions['targetval:' . $target] = ['id' => 'targetval:' . $target, 'kind' => 'info', 'text' => '“' . $newText . '” is not on the Free Visits or Paid Activities & Services list under that name. Type the name as the list has it, or add it to the list first.', 'options' => []];
                return true;
            }
            $day = (int) $m[1];
            $this->addIntent(['key' => 'add:' . $day . ':' . $hit['kind'] . ':' . $hit['id'], 'kind' => 'service_add', 'day_no' => $day, 'master' => $hit, 'label' => 'Visit / service', 'scope' => ucfirst($this->nightName($day)), 'from' => 'not on the programme', 'to' => (string) $hit['name'], 'from_raw' => null, 'to_raw' => (string) $hit['name'], 'note' => '']);
            return true;
        }
        if (preg_match('/^svc:(\d+)$/', $target, $m)) {
            foreach ((array) ($this->it['services'] ?? []) as $s) {
                if ((int) $s['id'] === (int) $m[1]) {
                    $n = (int) $s['day_no'];
                    $scope = $n > 0 ? ucfirst($this->nightName($n)) : 'The tour';
                    if ($newText === '') {
                        $this->addIntent(['key' => 'svc:' . $s['id'], 'kind' => 'service_remove', 'service_id' => (int) $s['id'], 'day_no' => $n, 'label' => 'Visit / service', 'scope' => $scope, 'from' => (string) $s['title'], 'to' => 'taken off', 'from_raw' => (string) $s['title'], 'to_raw' => null, 'note' => '']);
                    } else {
                        $this->serviceRename($s, $n, $scope, $newText);
                    }
                    return true;
                }
            }
            return false;
        }
        if (preg_match('/^req:(\d+|new)$/', $target, $m)) {
            if ($m[1] === 'new') {
                if ($newText === '') {
                    return false;
                }
                $this->addIntent(['key' => 'req:new:' . md5($newText), 'kind' => 'request_new', 'label' => 'Customer request', 'scope' => 'Request', 'from' => 'not on the inquiry', 'to' => mb_substr($newText, 0, 255), 'from_raw' => null, 'to_raw' => mb_substr($newText, 0, 255), 'quote' => $newText, 'note' => '']);
                return true;
            }
            foreach ((array) ($this->it['requests'] ?? []) as $r) {
                if ((int) $r['id'] === (int) $m[1]) {
                    if ($newText === '') {
                        $this->addIntent(['key' => 'req:' . $r['id'], 'kind' => 'request_drop', 'request_id' => (int) $r['id'], 'label' => 'Customer request', 'scope' => 'Request', 'from' => (string) $r['title'], 'to' => 'dropped', 'from_raw' => (string) $r['title'], 'to_raw' => null, 'note' => '']);
                    } else {
                        $this->addIntent(['key' => 'req:' . $r['id'], 'kind' => 'request', 'request_id' => (int) $r['id'], 'label' => 'Customer request', 'scope' => 'Request', 'from' => (string) $r['title'], 'to' => mb_substr($newText, 0, 255), 'from_raw' => (string) $r['title'], 'to_raw' => mb_substr($newText, 0, 255), 'quote' => $newText, 'note' => '']);
                    }
                    return true;
                }
            }
            return false;
        }
        if ($target === 'vehicle') {
            $classes = array_values(array_filter((array) ($this->lk['vehicleClasses'] ?? []), static fn ($c) => (int) ($c['active'] ?? 1) === 1));
            $hits = [];
            $k = self::normText($newText);
            foreach ($classes as $c) {
                foreach (array_merge([(string) $c['name']], array_map('trim', explode(',', (string) ($c['match_words'] ?? '')))) as $w) {
                    if ($w !== '' && !ctype_digit($w) && preg_match('/\b' . preg_quote(self::normText($w), '/') . '\b/u', $k)) {
                        $hits[$c['code']] = $c;
                    }
                }
            }
            $vehicles = (array) ($this->it['vehicles'] ?? []);
            if (count($hits) !== 1 || count($vehicles) !== 1) {
                $this->questions['targetval:vehicle'] = ['id' => 'targetval:vehicle', 'kind' => 'info', 'text' => 'Name exactly one of the vehicle sizes the company uses (' . implode(', ', array_column($classes, 'name')) . ').', 'options' => []];
                return true;
            }
            $to = reset($hits);
            $v = $vehicles[0];
            if ($to['code'] !== $v['class_code']) {
                $this->addIntent(['key' => 'vehicle', 'kind' => 'vehicle', 'line' => $v, 'to_code' => (string) $to['code'], 'label' => 'Vehicle', 'scope' => 'Transport', 'from' => (string) ($v['type'] ?: $v['class_code']), 'to' => (string) $to['name'], 'from_raw' => (string) $v['class_code'], 'to_raw' => (string) $to['code'], 'note' => 'The rate per kilometre is the Transport Rates master\'s for the new size.']);
            }
            return true;
        }
        if (preg_match('/^crew:(.+)$/', $target, $m)) {
            foreach ((array) ($this->it['crew'] ?? []) as $c) {
                if ((string) $c['role_code'] !== $m[1]) {
                    continue;
                }
                $kind = (string) $c['kind'];
                $k = self::normText($newText);
                $cands = array_values(array_filter((array) ($this->lk['crewRoles'] ?? []), static fn ($r) => $r['kind'] === $kind && (int) ($r['active'] ?? 1) === 1
                    && ((string) ($r['language'] ?? '') !== '' && preg_match('/\b' . preg_quote(mb_strtolower((string) $r['language']), '/') . '\b/u', $k) || self::normText((string) $r['name']) === $k)));
                if (count($cands) !== 1) {
                    $this->questions['targetval:' . $target] = ['id' => 'targetval:' . $target, 'kind' => 'info', 'text' => 'Say the language of the new ' . $kind . ' (for example “English speaking ' . $kind . '”); it has to be exactly one ' . $kind . ' role in the Crew Bata master.', 'options' => []];
                    return true;
                }
                if ($cands[0]['code'] !== $c['role_code']) {
                    $this->addIntent(['key' => 'crew:' . $c['role_code'], 'kind' => 'crew', 'from_code' => (string) $c['role_code'], 'to_code' => (string) $cands[0]['code'], 'label' => ucfirst($kind), 'scope' => 'Driver and guide', 'from' => (string) $c['role_name'], 'to' => (string) $cands[0]['name'], 'from_raw' => (string) $c['role_code'], 'to_raw' => (string) $cands[0]['code'], 'note' => 'The bata per day is the Crew Bata master\'s for the new role; nothing is typed.']);
                }
                return true;
            }
            return false;
        }
        if (preg_match('/^pax:(\d+)$/', $target, $m)) {
            foreach ((array) ($this->it['passengers'] ?? []) as $p) {
                if ((int) $p['id'] === (int) $m[1] && $newText !== '') {
                    $this->addIntent(['key' => 'pax:' . $p['id'], 'kind' => 'passenger', 'pax_id' => (int) $p['id'], 'label' => 'Passenger name', 'scope' => 'Passengers', 'from' => (string) $p['full_name'], 'to' => $newText, 'from_raw' => (string) $p['full_name'], 'to_raw' => $newText, 'note' => '']);
                    return true;
                }
            }
            return false;
        }
        return false;
    }

    /** "Departure: 2 Nov 2026" with the old words "Departure: 2 Jan 2027": the label both share is not part of the value. */
    private static function stripLabel(string $new, string $old): string
    {
        if (preg_match('/^\s*([^:\n]{1,40}):\s*/u', $new, $m) && preg_match('/^\s*' . preg_quote($m[1], '/') . ':/iu', $old)) {
            return substr($new, strlen($m[0]));
        }
        return $new;
    }
}
