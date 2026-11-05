<?php

namespace App\Libraries\HotelRates;

/**
 * Hotel quotation readiness and the rate-expiry warning (tf-hr-ready-r1).
 *
 * Answers two questions for Management and the Hotel Reservations desk, without changing anything:
 *
 *   1. Can the quotation engine price this hotel by itself on a given date?
 *      "Yes" only when the engine, asked the way a customer quotation asks it, returns a price:
 *      RateLookup::price() with the quotation's plan kinds (RateLookup::quotationPlanTypes() — contract,
 *      migrated, addendum, series), an approved plan, today's booking date, one room for two people. A
 *      tactical, special-offer or early-bird plan is never asked, so an offer that needs a person to choose
 *      it can never make a hotel look quote-ready, and a draft is never asked either. Nothing here decides a
 *      price: every "yes" is the engine's own answer, so this list cannot disagree with a quotation.
 *      Markets: when a hotel's quotation plans name markets, each named market is asked separately (plus one
 *      market no plan names, standing for all the others); a hotel priced for some markets only says which.
 *
 *   2. Is an approved contract rate about to run out, or has it run out, with nothing approved after it?
 *      The coverage the engine gives is followed day by day (through RateLookup::intervals(), the boundaries
 *      where the answer can change); a warning starts ALERT_DAYS (14) days before the last covered night and
 *      stays — "Expired" after that night — until an approved quotation rate covers the night after. A draft
 *      or a transcribed sheet waiting to be loaded does not clear it: it changes the warning to
 *      "Replacement rates received …". Expiries before WATCH_FROM are not raised as warnings (the old Hotel
 *      Rate screen's long-past seasons); they still show in the not-quote-ready list as "expired".
 *
 * Read-only by construction: SELECTs, the engine's own reads and a read of writable/hotel_rates_entry/*.json.
 * No scan, no cache, no settings write, no work item — opening the screens built on it changes nothing.
 */
final class QuoteReadiness
{
    /** The warning starts this many days before the last covered night. */
    public const ALERT_DAYS = 14;
    /** Expiries from this date on are raised as warnings; older ones are listed only. */
    public const WATCH_FROM = '2026-10-01';
    /** How far either side of a date the coverage is followed (expired-on / next-starts / covered-until). */
    public const HORIZON_DAYS = 400;
    /** "Upcoming nights": hotel nights on quotations and tours from today for this many days. */
    public const UPCOMING_DAYS = 180;

    public const TYPE_LABEL = ['contract' => 'Contract', 'migrated' => 'Legacy rate (old Hotel Rate screen)', 'addendum' => 'Addendum', 'series' => 'Series',
        'tactical' => 'Tactical offer', 'special_offer' => 'Special offer', 'early_bird' => 'Early-bird offer'];

    private $db;
    private RateLookup $engine;
    private ?string $entryDir;
    private string $today;
    /** @var string[] the plan kinds a quotation asks for */
    private array $types;
    private string $defaultMarket;
    /** @var string[] market codes (without ALL) */
    private array $marketCodes = [];
    private ?array $sheetsByHotel = null;
    private ?array $draftsByHotel = null;
    private ?array $supplierOk = null;
    private ?array $upcoming = null;
    private ?array $accounts = null;

    public function __construct($db, ?RateLookup $engine = null, ?string $entryDir = null, ?string $today = null)
    {
        $this->db = $db;
        // a private engine: its bundle cache is this report's alone
        $this->engine = $engine ?? new RateLookup($db);
        $this->entryDir = $entryDir ?? (defined('WRITEPATH') ? WRITEPATH . 'hotel_rates_entry' : null);
        $this->today = $today ?: date('Y-m-d');
        $this->types = array_values(array_map('strtolower', RateLookup::quotationPlanTypes($db)));
        $this->defaultMarket = $this->engine->defaultMarket();
        foreach ($this->rows('SELECT code FROM hr2_market ORDER BY sort_order, code') as $m) {
            if ($m['code'] !== 'ALL') {
                $this->marketCodes[] = (string) $m['code'];
            }
        }
        if (!$this->marketCodes) {
            $this->marketCodes = [$this->defaultMarket];
        }
    }

    public function today(): string
    {
        return $this->today;
    }

    public function quotationTypes(): array
    {
        return $this->types;
    }

    public function defaultMarket(): string
    {
        return $this->defaultMarket;
    }

    // =====================================================================
    //  The report: both lists and the warnings
    // =====================================================================

    /**
     * @param string|null $date  the night to check (default today)
     * @return array{date:string,today:string,ready:array,not_ready:array,alerts:array,totals:array,...}
     */
    public function report(?string $date = null): array
    {
        $date = Dates::parse($date) ?: $this->today;
        $ready = [];
        $notReady = [];
        $alerts = [];
        $inactive = 0;
        foreach ($this->hotels() as $h) {
            if ($h['status'] !== 'active') {
                $inactive++;
                continue;
            }
            $r = $this->hotelStatus($h, $date);
            if ($r['alert']) {
                $alerts[] = $r['alert'];
            }
            unset($r['_runs']);
            if ($r['ready'] === 'none') {
                $notReady[] = $r;
            } else {
                $ready[] = $r;
            }
        }
        // rate sheets transcribed for a hotel that has no supplier record yet: not a hotel of the panel, but not quotable either
        foreach ($this->sheetsWithoutSupplier() as $s) {
            $notReady[] = $s;
        }
        // the same hotel on file twice (the panel's Duplicates): say so where the other record is quote-ready
        $readyByName = [];
        foreach ($ready as $r) {
            $readyByName[self::nameKey($r['name'])][(int) $r['id']] = $r['name'] . ' (' . $r['acc'] . ')';
        }
        foreach ($notReady as &$n) {
            if (!empty($n['id']) && isset($readyByName[self::nameKey($n['name'])])) {
                $n['notes'][] = ['key' => 'duplicate', 'level' => 'grey', 'text' => 'Possible duplicate: ' . implode(', ', $readyByName[self::nameKey($n['name'])]) . ' is quote-ready (Hotel Rates › Duplicates)'];
            }
        }
        unset($n);
        foreach ($alerts as &$a) {
            $others = $readyByName[self::nameKey($a['hotel'])] ?? [];
            unset($others[(int) $a['hotel_id']]);   // the hotel itself is not its own duplicate
            if ($others) {
                $a['possible_duplicate'] = implode(', ', $others);
            }
        }
        unset($a);
        usort($alerts, static fn ($a, $b) => [$a['state'] === 'expired' ? 0 : 1, $a['ends']] <=> [$b['state'] === 'expired' ? 0 : 1, $b['ends']]);
        $reasons = [];
        foreach ($notReady as $n) {
            $reasons[$n['reason']['key']] = ($reasons[$n['reason']['key']] ?? 0) + 1;
        }
        return [
            'date' => $date,
            'today' => $this->today,
            'default_market' => $this->defaultMarket,
            'quotation_types' => $this->types,
            'alert_days' => self::ALERT_DAYS,
            'watch_from' => self::WATCH_FROM,
            'ready' => $ready,
            'not_ready' => $notReady,
            'alerts' => $alerts,
            'totals' => [
                'ready' => count($ready),
                'ready_all' => count(array_filter($ready, static fn ($r) => $r['ready'] === 'all')),
                'ready_some' => count(array_filter($ready, static fn ($r) => $r['ready'] === 'some')),
                'not_ready' => count($notReady),
                'not_ready_hotels' => count(array_filter($notReady, static fn ($r) => !empty($r['id']))),
                'sheets_without_supplier' => count(array_filter($notReady, static fn ($r) => empty($r['id']))),
                'alerts' => count($alerts),
                'expiring' => count(array_filter($alerts, static fn ($a) => $a['state'] === 'expiring')),
                'expired' => count(array_filter($alerts, static fn ($a) => $a['state'] === 'expired')),
                'inactive_not_listed' => $inactive,
                'reasons' => $reasons,
            ],
        ];
    }

    /**
     * The warnings alone — cheap enough for the Tour Desk bar: only hotels with a quotation plan or season ending
     * between WATCH_FROM and today + ALERT_DAYS can have one, so only those are followed through the engine.
     */
    public function alerts(): array
    {
        $until = self::addDays($this->today, self::ALERT_DAYS);
        $in = $this->typesIn();
        $ids = array_map(static fn ($r) => (int) $r['hotel_id'], $this->rows(
            "SELECT DISTINCT c.hotel_id FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id AND h.status = 'active'
             LEFT JOIN hr2_season s ON s.contract_id = c.id LEFT JOIN hr2_season_date d ON d.season_id = s.id
             WHERE c.status = 'approved' AND c.plan_type IN ({$in}) AND COALESCE(c.applies_to, 'both') <> 'payment'
               AND ((c.valid_to BETWEEN ? AND ?) OR (d.date_to BETWEEN ? AND ?))",
            [self::WATCH_FROM, $until, self::WATCH_FROM, $until]
        ));
        if (!$ids) {
            return [];
        }
        $byId = [];
        foreach ($this->hotels() as $h) {
            $byId[(int) $h['id']] = $h;
        }
        $out = [];
        foreach ($ids as $id) {
            if (!isset($byId[$id])) {
                continue;
            }
            $a = $this->hotelStatus($byId[$id], $this->today, true)['alert'];
            if ($a) {
                $out[] = $a;
            }
        }
        usort($out, static fn ($a, $b) => [$a['state'] === 'expired' ? 0 : 1, $a['ends']] <=> [$b['state'] === 'expired' ? 0 : 1, $b['ends']]);
        return $out;
    }

    // =====================================================================
    //  One hotel
    // =====================================================================

    /**
     * @param array $h  a row of hotels()
     * @param bool $alertOnly  skip what only the lists need
     */
    public function hotelStatus(array $h, string $date, bool $alertOnly = false): array
    {
        $hid = (int) $h['id'];
        $b = $this->engine->bundle($hid);
        $plans = $this->quotePlans($b);
        $pairs = $this->pairs($b, $plans);
        $mset = $this->marketSet($plans);
        $other = $this->otherMarket;
        $from = self::addDays(min($date, $this->today), -self::HORIZON_DAYS);
        $to = self::addDays(max($date, $this->today), self::HORIZON_DAYS);
        $runs = $plans && $pairs ? $this->runs($hid, $b, $pairs, $mset, $from, $to) : [];

        $out = [
            'id' => $hid, 'name' => $h['name'], 'acc' => $h['primary_sup_acc_no'], 'accounts' => $h['accounts'] ?? [], 'location' => $h['location_name'] ?? null, 'chain' => $h['chain'] ?? null,
            'ready' => 'none', 'markets' => null, 'source' => null, 'covered_until' => null, 'reason' => null, 'notes' => [],
            'prev_end' => null, 'next_start' => null, 'upcoming_nights' => 0, 'alert' => null, '_runs' => $runs,
        ];

        // ---- the warning (always about today) ----
        $out['alert'] = $this->alertFor($h, $runs, $to);
        if ($alertOnly) {
            return $out;
        }

        // ---- the check date ----
        $onDate = $this->runAt($runs, $date);
        $out['upcoming_nights'] = $this->upcomingNights($hid);
        $drafts = $this->drafts($hid);
        $sheets = $this->sheetsFor($hid);
        $offers = $this->offersOn($b, $date);
        if (!$this->supplierOk($hid)) {
            $out['notes'][] = ['key' => 'no_supplier', 'level' => 'red', 'text' => 'No supplier record (Master Files → Supplier)'];
        }
        if ($onDate) {
            // the engine prices the night: which markets, which plan
            $priced = [];
            $unpriced = [];
            $first = null;
            foreach ($mset as $m) {
                $r = $this->priced($hid, $b, $pairs, $date, $this->opts($m));
                if ($r) {
                    $priced[] = $m;
                    $first = $first ?? $r;
                } else {
                    $unpriced[] = $m;
                }
            }
            $out['ready'] = $unpriced ? 'some' : 'all';
            $out['markets'] = $this->marketsText($other, $priced, $unpriced);
            if ($first) {
                $out['source'] = ['plan_id' => (int) $first['plan']['id'], 'plan' => $first['plan']['name'], 'plan_type' => $first['plan']['plan_type'], 'type_label' => self::TYPE_LABEL[$first['plan']['plan_type']] ?? $first['plan']['plan_type'], 'season' => $first['season']['name'] ?? null];
            }
            $out['covered_until'] = $onDate[1] >= $to ? null : $onDate[1];
            $out['reason'] = ['key' => 'ready', 'level' => 'green', 'text' => 'Quotation rate on file', 'detail' => $out['source'] ? ($out['source']['type_label'] . ': ' . $out['source']['plan']) : ''];
            if ($out['ready'] === 'some') {
                $out['notes'][] = ['key' => 'markets', 'level' => 'amber', 'text' => 'Contract rate for some markets only — ' . $out['markets']['text']];
            }
            foreach ($offers as $o) {
                $out['notes'][] = ['key' => 'offer', 'level' => 'blue', 'text' => self::TYPE_LABEL[$o['plan_type']] . ' also covers this date (manual selection only): ' . $o['name']];
            }
            return $out;
        }

        // ---- not quotable on the date: why ----
        foreach ($runs as [$f, $t]) {
            if ($t < $date) {
                $out['prev_end'] = $t;
            } elseif ($f > $date && $out['next_start'] === null) {
                $out['next_start'] = $f;
            }
        }
        if (!$runs) {
            $last = $this->lastPricedEnd($hid, $date);
            $out['prev_end'] = $last;
        }
        $draftOn = array_values(array_filter($drafts, static fn ($d) => $d['valid_from'] <= $date && $d['valid_to'] >= $date));
        $sheetOn = array_values(array_filter($sheets, static fn ($s) => (!$s['valid_from'] || $s['valid_from'] <= $date) && (!$s['valid_to'] || $s['valid_to'] >= $date)));
        $groupOnly = $plans && $pairs && $this->priced($hid, $b, $pairs, $date, $this->opts($mset[0], 'group')) !== null;

        $underlying = null;
        if ($out['prev_end'] && !$out['next_start']) {
            $underlying = ['key' => 'expired', 'level' => 'red', 'text' => 'Contract / rate expired', 'detail' => 'The last approved quotation rate ended ' . $this->fmt($out['prev_end']) . '.'];
        } elseif ($out['prev_end'] && $out['next_start']) {
            $underlying = ['key' => 'gap', 'level' => 'amber', 'text' => 'No valid rate for the required dates', 'detail' => 'Approved rates end ' . $this->fmt($out['prev_end']) . ' and start again ' . $this->fmt($out['next_start']) . '.'];
        } elseif ($out['next_start']) {
            $underlying = ['key' => 'not_started', 'level' => 'amber', 'text' => 'No valid rate for the required dates', 'detail' => 'The next approved contract rate starts ' . $this->fmt($out['next_start']) . '.'];
        } elseif ($groupOnly) {
            $underlying = ['key' => 'group_only', 'level' => 'amber', 'text' => 'Contract rate for groups only', 'detail' => 'An approved group contract covers this date; a one-room quotation is not priced from it.'];
        } elseif ($plans) {
            $underlying = ['key' => 'no_prices', 'level' => 'red', 'text' => 'No usable approved contract rate', 'detail' => 'An approved plan is on file but it prices no room on this date (empty or zero rows, or a market it is not for).'];
        } else {
            $underlying = ['key' => 'no_contract', 'level' => 'red', 'text' => 'Incomplete — Rates Pending', 'detail' => 'No approved contract, legacy, addendum or series rate is on file for this hotel; it is on the master for identification and preparation and gives no quotation price.'];
        }
        $replacing = in_array($underlying['key'], ['expired', 'gap', 'not_started'], true);
        if ($draftOn) {
            $d = $draftOn[0];
            $out['reason'] = ['key' => $replacing ? 'replacement_draft' : 'draft', 'level' => 'blue',
                'text' => $replacing ? 'Replacement rate received — still awaiting approval' : 'Draft awaiting approval',
                'detail' => 'Draft #' . $d['id'] . ' "' . $d['name'] . '" (' . $this->fmt($d['valid_from']) . ' – ' . $this->fmt($d['valid_to']) . ') covers this date; it is used once approved.'];
            $out['notes'][] = $underlying + ['under' => true];
        } elseif ($sheetOn) {
            $s = $sheetOn[0];
            $out['reason'] = ['key' => 'sheet_waiting', 'level' => 'blue', 'text' => 'Rate sheet waiting in Hotel Incomplete Work',
                'detail' => 'The sheet "' . $s['plan'] . '" is transcribed (file ' . $s['file'] . ') but not loaded as a plan yet.'];
            $out['notes'][] = $underlying + ['under' => true];
        } elseif ($offers && in_array($underlying['key'], ['no_contract', 'no_prices', 'expired', 'gap', 'not_started'], true)) {
            $o = $offers[0];
            $out['reason'] = ['key' => 'offer_only', 'level' => 'amber', 'text' => 'Only a tactical / special offer covers this date',
                'detail' => (self::TYPE_LABEL[$o['plan_type']] ?? $o['plan_type']) . ' "' . $o['name'] . '" — offers are chosen by a person, never used automatically.'];
            $out['notes'][] = $underlying + ['under' => true];
        } else {
            $out['reason'] = $underlying;
            foreach ($offers as $o) {
                $out['notes'][] = ['key' => 'offer', 'level' => 'blue', 'text' => (self::TYPE_LABEL[$o['plan_type']] ?? $o['plan_type']) . ' covers this date (manual selection only): ' . $o['name']];
            }
        }
        // drafts / sheets that do not cover the date but are on file
        foreach ($drafts as $d) {
            if (!in_array($d, $draftOn, true)) {
                $out['notes'][] = ['key' => 'draft_other', 'level' => 'blue', 'text' => 'Draft #' . $d['id'] . ' awaiting approval: ' . $d['name'] . ' (' . $this->fmt($d['valid_from']) . ' – ' . $this->fmt($d['valid_to']) . ')'];
            }
        }
        $otherSheets = array_values(array_filter($sheets, static fn ($s) => !in_array($s, $sheetOn, true)));
        if (count($otherSheets) > 2) {
            $out['notes'][] = ['key' => 'sheet_other', 'level' => 'blue', 'text' => count($otherSheets) . ' more rate sheets waiting to be loaded (' . implode('; ', array_map(static fn ($s) => $s['plan'], array_slice($otherSheets, 0, 3))) . (count($otherSheets) > 3 ? '; …' : '') . ')'];
        } else {
            foreach ($otherSheets as $s) {
                $out['notes'][] = ['key' => 'sheet_other', 'level' => 'blue', 'text' => 'Rate sheet waiting to be loaded: ' . $s['plan']];
            }
        }
        return $out;
    }

    /** The warning for a hotel, relative to today; null when none. */
    private function alertFor(array $h, array $runs, string $horizonEnd): ?array
    {
        $hid = (int) $h['id'];
        $today = $this->today;
        $now = $this->runAt($runs, $today);
        $state = null;
        $ends = null;
        if ($now) {
            if ($now[1] < $horizonEnd && $now[1] <= self::addDays($today, self::ALERT_DAYS)) {
                $state = 'expiring';
                $ends = $now[1];
            }
        } else {
            $prev = null;
            foreach ($runs as [$f, $t]) {
                if ($t < $today) {
                    $prev = $t;
                }
            }
            if ($prev !== null && $prev >= self::WATCH_FROM) {
                $state = 'expired';
                $ends = $prev;
            }
        }
        if ($state === null) {
            return null;
        }
        $after = Dates::next($ends);
        $nextStart = null;
        foreach ($runs as [$f, $t]) {
            if ($f > $ends) {
                $nextStart = $f;
                break;
            }
        }
        $draft = null;
        foreach ($this->drafts($hid) as $d) {
            if ($d['valid_to'] >= $after) {
                $draft = $d;
                break;
            }
        }
        $sheet = null;
        foreach ($this->sheetsFor($hid) as $s) {
            if (!$s['valid_to'] || $s['valid_to'] >= $after) {
                $sheet = $s;
                break;
            }
        }
        if ($draft) {
            $rep = ['key' => 'draft', 'level' => 'blue', 'text' => 'Replacement rates received — draft awaiting approval', 'detail' => 'Draft #' . $draft['id'] . ' "' . $draft['name'] . '" (' . $this->fmt($draft['valid_from']) . ' – ' . $this->fmt($draft['valid_to']) . ')', 'plan_id' => (int) $draft['id']];
        } elseif ($sheet) {
            $rep = ['key' => 'sheet', 'level' => 'blue', 'text' => 'Replacement rate sheet received — waiting to be loaded', 'detail' => $sheet['plan'] . ' (file ' . $sheet['file'] . ')'];
        } elseif ($nextStart) {
            $rep = ['key' => 'gap', 'level' => 'amber', 'text' => 'Next approved rate starts ' . $this->fmt($nextStart) . ' — no rate in between', 'detail' => 'Rates needed for ' . $this->fmt($after) . ' – ' . $this->fmt(Dates::prev($nextStart)) . '.'];
        } else {
            $rep = ['key' => 'required', 'level' => 'red', 'text' => 'Replacement rates required', 'detail' => 'New rates required from ' . $this->fmt($after) . '.'];
        }
        $days = Dates::diffDays($today, $ends);
        $headline = 'RATE EXPIRY WARNING — ' . $h['name'] . ' — ' . ($state === 'expiring'
            ? 'Current contract expires ' . $this->fmt($ends, true) . ($days === 0 ? ' (today)' : ' (in ' . $days . ' day' . ($days === 1 ? '' : 's') . ')')
            : 'Contract expired ' . $this->fmt($ends, true))
            . ' — ' . ($rep['key'] === 'draft' ? 'replacement draft awaiting approval' : ($rep['key'] === 'sheet' ? 'replacement sheet waiting to be loaded' : 'New rates required'));
        return [
            'hotel_id' => $hid, 'hotel' => $h['name'], 'acc' => $h['primary_sup_acc_no'], 'location' => $h['location_name'] ?? null,
            'state' => $state, 'state_text' => $state === 'expiring' ? 'Expiring within ' . self::ALERT_DAYS . ' days' : 'Expired',
            'ends' => $ends, 'ends_text' => $this->fmt($ends, true), 'days' => $days, 'next_start' => $nextStart, 'replacement' => $rep, 'headline' => $headline,
            'upcoming_nights' => $this->upcomingNights($hid),
        ];
    }

    // =====================================================================
    //  The engine, asked the way a quotation asks it
    // =====================================================================

    /** Options of a customer quotation: published plan kinds only, approved only, booked today, one room for two. */
    public function opts(string $market, ?string $tier = null): array
    {
        $o = ['market' => $market, 'booking_date' => $this->today, 'rooms' => 1, 'pax' => 2, 'plan_types' => $this->types];
        if ($tier === 'group') {
            $o['tier'] = 'group';
            $o['rooms'] = 20;
            $o['pax'] = 40;
        }
        return $o;
    }

    /** Approved plans of the quotation kinds (payment-only ad hoc rates are never a quotation's). */
    private function quotePlans(array $b): array
    {
        $out = [];
        foreach ($b['plans'] as $pid => $p) {
            if ($p['status'] === 'approved' && in_array(strtolower((string) $p['plan_type']), $this->types, true) && ($p['applies_to'] ?? 'both') !== 'payment') {
                $out[$pid] = $p;
            }
        }
        return $out;
    }

    /** Every room type × meal plan those plans carry a row for (and the supplement rooms over them). */
    private function pairs(array $b, array $plans): array
    {
        $pairs = [];
        foreach ($plans as $p) {
            $meals = [];
            foreach ($p['rates'] as $bySeason) {
                foreach ($bySeason as $rid => $byMeal) {
                    foreach ($byMeal as $meal => $r) {
                        $pairs[$rid . '|' . $meal] = [(int) $rid, (string) $meal];
                        $meals[$meal] = true;
                    }
                }
            }
            foreach ($p['room_supplements'] as $rs) {
                foreach (array_keys($meals) as $meal) {
                    $pairs[(int) $rs['room_type_id'] . '|' . $meal] = [(int) $rs['room_type_id'], (string) $meal];
                }
            }
        }
        return array_values($pairs);
    }

    /** The market that stood for "every market no plan names" in the last marketSet() (null when every market is named). */
    private ?string $otherMarket = null;

    /** The markets worth asking: those the plans name, plus one they do not name (it stands for all the others). */
    private function marketSet(array $plans): array
    {
        $this->otherMarket = null;
        $named = [];
        foreach ($plans as $p) {
            foreach (['include', 'exclude'] as $mode) {
                foreach ($p['markets'][$mode] ?? [] as $c) {
                    $named[$c] = true;
                }
            }
        }
        if (!$named) {
            return [$this->defaultMarket];
        }
        $set = array_keys($named);
        $other = !isset($named[$this->defaultMarket]) ? $this->defaultMarket : null;
        foreach ($this->marketCodes as $c) {
            if ($other === null && !isset($named[$c])) {
                $other = $c;
            }
        }
        if ($other !== null) {
            $set[] = $other;
            $this->otherMarket = $other;
        }
        return $set;
    }

    private function marketsText(?string $other, array $priced, array $unpriced): array
    {
        if (!$unpriced) {
            return ['all' => true, 'text' => 'All markets', 'priced' => $priced, 'unpriced' => []];
        }
        if ($other !== null && in_array($other, $priced, true)) {
            $text = 'All markets except ' . implode(', ', $unpriced);
        } else {
            $text = 'Only ' . implode(', ', array_values(array_filter($priced, static fn ($m) => $m !== $other)));
        }
        return ['all' => false, 'text' => $text, 'priced' => array_values(array_filter($priced, static fn ($m) => $m !== $other)), 'unpriced' => array_values(array_filter($unpriced, static fn ($m) => $m !== $other)), 'others_priced' => $other !== null ? in_array($other, $priced, true) : null];
    }

    /** The engine's first price for the night, or null. */
    private function priced(int $hid, array $b, array $pairs, string $date, array $opts): ?array
    {
        if (!$pairs || !$this->engine->candidates($b, $date, $opts)) {
            return null;
        }
        foreach ($pairs as [$rid, $meal]) {
            $r = $this->engine->price($hid, $date, 'hr2:' . $rid, $meal, $opts);
            if ($r) {
                return $r;
            }
        }
        return null;
    }

    /** Covered runs [from, to] in the window: nights some market's quotation is priced on, followed interval by interval. */
    private function runs(int $hid, array $b, array $pairs, array $mset, string $from, string $to): array
    {
        $runs = [];
        foreach ($this->engine->intervals($b, $from, $to) as [$f, $t]) {
            $ok = false;
            foreach ($mset as $m) {
                if ($this->priced($hid, $b, $pairs, $f, $this->opts($m))) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                continue;
            }
            $n = count($runs);
            if ($n && Dates::next($runs[$n - 1][1]) === $f) {
                $runs[$n - 1][1] = $t;
            } else {
                $runs[] = [$f, $t];
            }
        }
        return $runs;
    }

    private function runAt(array $runs, string $date): ?array
    {
        foreach ($runs as $r) {
            if ($r[0] <= $date && $r[1] >= $date) {
                return $r;
            }
        }
        return null;
    }

    /** Approved offers (not a quotation kind) with a priced row in a season covering the date. */
    private function offersOn(array $b, string $date): array
    {
        $out = [];
        foreach ($b['plans'] as $pid => $p) {
            if ($p['status'] !== 'approved' || in_array(strtolower((string) $p['plan_type']), $this->types, true) || ($p['applies_to'] ?? 'both') === 'payment') {
                continue;
            }
            if ($p['valid_from'] > $date || $p['valid_to'] < $date) {
                continue;
            }
            foreach ($p['seasons'] as $sid => $s) {
                foreach ($s['dates'] as [$f, $t]) {
                    if ($f <= $date && $t >= $date && !empty($p['rates'][$sid])) {
                        $out[] = ['id' => (int) $pid, 'name' => $p['name'], 'plan_type' => $p['plan_type'], 'book_to' => $p['book_to'], 'min_stay' => $p['min_stay'] ?? null];
                        continue 3;
                    }
                }
            }
        }
        return $out;
    }

    /** The last night any approved quotation plan with a priced row covered, before the date (for hotels whose rates ran out long ago). */
    private function lastPricedEnd(int $hid, string $date): ?string
    {
        $in = $this->typesIn();
        $r = $this->row(
            "SELECT MAX(LEAST(d.date_to, c.valid_to)) AS e FROM hr2_contract c JOIN hr2_season s ON s.contract_id = c.id JOIN hr2_season_date d ON d.season_id = s.id
             WHERE c.hotel_id = ? AND c.status = 'approved' AND c.plan_type IN ({$in}) AND COALESCE(c.applies_to, 'both') <> 'payment' AND d.date_from <= c.valid_to AND LEAST(d.date_to, c.valid_to) < ?
               AND EXISTS (SELECT 1 FROM hr2_rate r WHERE r.season_id = s.id AND (r.sgl > 0 OR r.dbl > 0 OR r.tpl > 0 OR r.quad > 0 OR r.unit > 0))",
            [$hid, $date]
        );
        return $r['e'] ?? null;
    }

    // =====================================================================
    //  What is on file around the rates: drafts, sheets, suppliers, use
    // =====================================================================

    /** Active (and archived) hotels with their location and accounts. */
    public function hotels(): array
    {
        $rows = $this->rows("SELECT h.id, h.name, h.primary_sup_acc_no, h.status, h.chain, l.name AS location_name FROM hr2_hotel h LEFT JOIN hr2_location l ON l.id = h.location_id WHERE h.status <> 'merged' ORDER BY h.name, h.id");
        $acc = $this->accounts();
        foreach ($rows as &$r) {
            $r['accounts'] = $acc[(int) $r['id']] ?? [];
        }
        unset($r);
        return $rows;
    }

    private function accounts(): array
    {
        if ($this->accounts === null) {
            $this->accounts = [];
            foreach ($this->rows('SELECT hotel_id, sup_acc_no FROM hr2_hotel_account ORDER BY is_primary DESC, sup_acc_no') as $a) {
                $this->accounts[(int) $a['hotel_id']][] = trim((string) $a['sup_acc_no']);
            }
        }
        return $this->accounts;
    }

    /** Drafts of the quotation kinds, per hotel, earliest first. */
    private function drafts(int $hid): array
    {
        if ($this->draftsByHotel === null) {
            $this->draftsByHotel = [];
            $in = $this->typesIn();
            foreach ($this->rows("SELECT id, hotel_id, name, plan_type, valid_from, valid_to FROM hr2_contract WHERE status = 'draft' AND plan_type IN ({$in}) AND COALESCE(applies_to, 'both') <> 'payment' ORDER BY valid_from, id") as $d) {
                $this->draftsByHotel[(int) $d['hotel_id']][] = $d;
            }
        }
        return $this->draftsByHotel[$hid] ?? [];
    }

    /**
     * Transcribed sheets (writable/hotel_rates_entry/*.json) for a hotel of the panel that are not loaded as a plan yet
     * — no plan of the same name for that hotel in any status. Read only.
     */
    private function sheetsFor(int $hid): array
    {
        if ($this->sheetsByHotel === null) {
            $this->sheetsByHotel = [];
            $accHotel = [];
            foreach ($this->accounts() as $h => $accs) {
                foreach ($accs as $a) {
                    $accHotel[$a] = $h;
                }
            }
            foreach ($this->sheetFiles() as $doc) {
                $acc = trim((string) ($doc['hotel']['acc'] ?? ''));
                if ($acc === '' || !isset($accHotel[$acc])) {
                    continue;
                }
                $h = $accHotel[$acc];
                $c = $doc['sheet']['contract'] ?? [];
                $name = (string) ($c['name'] ?? '');
                if ($this->row('SELECT id FROM hr2_contract WHERE hotel_id = ? AND name = ? LIMIT 1', [$h, $name])) {
                    continue;
                }
                $this->sheetsByHotel[$h][] = ['file' => $doc['_file'], 'plan' => $name, 'valid_from' => $c['valid_from'] ?? null, 'valid_to' => $c['valid_to'] ?? null, 'plan_type' => $c['plan_type'] ?? 'contract'];
            }
        }
        return $this->sheetsByHotel[$hid] ?? [];
    }

    /** Sheets whose hotel has no supplier record (the "Rate sheet without a supplier" items of Hotel Incomplete Work). */
    public function sheetsWithoutSupplier(): array
    {
        $out = [];
        $accHotel = [];
        foreach ($this->accounts() as $h => $accs) {
            foreach ($accs as $a) {
                $accHotel[$a] = $h;
            }
        }
        foreach ($this->sheetFiles() as $doc) {
            $acc = trim((string) ($doc['hotel']['acc'] ?? ''));
            if ($acc !== '' && isset($accHotel[$acc])) {
                continue;
            }
            $known = $acc !== '' ? $this->row('SELECT Name FROM supplier WHERE Sup_Acc_No = ?', [$acc]) : null;
            $c = $doc['sheet']['contract'] ?? [];
            $out[] = [
                'id' => null, 'name' => (string) ($doc['hotel']['name'] ?? $doc['key'] ?? ''), 'acc' => $acc !== '' ? $acc : null, 'accounts' => [], 'location' => $doc['hotel']['location'] ?? null, 'chain' => $doc['hotel']['chain'] ?? null,
                'ready' => 'none', 'markets' => null, 'source' => null, 'covered_until' => null,
                'reason' => ['key' => 'no_supplier', 'level' => 'red', 'text' => $acc === '' ? 'No supplier record' : ($known ? 'Supplier not in the Hotel Rates panel yet' : 'Unknown supplier code ' . $acc),
                    'detail' => 'Rate sheet waiting in Hotel Incomplete Work: "' . ($c['name'] ?? '') . '" (' . $this->fmt($c['valid_from'] ?? null) . ' – ' . $this->fmt($c['valid_to'] ?? null) . '). Create the supplier under Master Files → Supplier, then press "Attach supplier code" on the item; the plan loads as a draft for approval.'],
                'notes' => [['key' => 'sheet', 'level' => 'blue', 'text' => 'Rate sheet waiting in Hotel Incomplete Work (file ' . $doc['_file'] . ')']],
                'prev_end' => null, 'next_start' => null, 'upcoming_nights' => 0, 'alert' => null, 'sheet_file' => $doc['_file'],
            ];
        }
        return $out;
    }

    private ?array $sheetDocs = null;

    private function sheetFiles(): array
    {
        if ($this->sheetDocs === null) {
            $this->sheetDocs = [];
            if ($this->entryDir && is_dir($this->entryDir)) {
                $files = glob(rtrim($this->entryDir, '/\\') . '/*.json') ?: [];
                sort($files);
                foreach ($files as $f) {
                    if (basename($f) === 'work_items.json') {
                        continue;
                    }
                    $doc = json_decode((string) @file_get_contents($f), true);
                    if (!is_array($doc) || empty($doc['sheet'])) {
                        continue;
                    }
                    $doc['_file'] = basename($f);
                    $this->sheetDocs[] = $doc;
                }
            }
        }
        return $this->sheetDocs;
    }

    /** True when at least one of the hotel's supplier codes is a record in Master Files → Supplier. */
    private function supplierOk(int $hid): bool
    {
        if ($this->supplierOk === null) {
            $this->supplierOk = [];
            foreach ($this->rows('SELECT a.hotel_id, MAX(s.Sup_Acc_No IS NOT NULL) AS ok FROM hr2_hotel_account a LEFT JOIN supplier s ON s.Sup_Acc_No = a.sup_acc_no GROUP BY a.hotel_id') as $r) {
                $this->supplierOk[(int) $r['hotel_id']] = (int) $r['ok'] === 1;
            }
        }
        return $this->supplierOk[$hid] ?? false;
    }

    /** Hotel nights on quotations and tours from today for UPCOMING_DAYS days (how much a gap matters). */
    private function upcomingNights(int $hid): int
    {
        if ($this->upcoming === null) {
            $this->upcoming = [];
            $accHotel = [];
            foreach ($this->accounts() as $h => $accs) {
                foreach ($accs as $a) {
                    $accHotel[$a] = $h;
                }
            }
            try {
                foreach ($this->rows("SELECT hotel_acc, COUNT(*) AS n FROM tour_quotations WHERE hotel_acc <> '' AND tour_date BETWEEN ? AND ? GROUP BY hotel_acc", [$this->today, self::addDays($this->today, self::UPCOMING_DAYS)]) as $r) {
                    $h = $accHotel[trim((string) $r['hotel_acc'])] ?? null;
                    if ($h !== null) {
                        $this->upcoming[$h] = ($this->upcoming[$h] ?? 0) + (int) $r['n'];
                    }
                }
            } catch (\Throwable $e) {
                // the legacy quotation table is optional here
            }
        }
        return $this->upcoming[$hid] ?? 0;
    }

    // =====================================================================
    //  Helpers
    // =====================================================================

    /** A hotel name reduced for spotting the same hotel on file twice ("Aliya Resort & Spa" = "Aliya Resort and Spa"). */
    private static function nameKey(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', str_replace('&', 'and', mb_strtolower($name)));
    }

    private static function addDays(string $date, int $days): string
    {
        return (new \DateTime($date))->modify(($days >= 0 ? '+' : '') . $days . ' days')->format('Y-m-d');
    }

    private function typesIn(): string
    {
        return implode(',', array_map(static fn ($t) => "'" . preg_replace('/[^a-z_]/', '', $t) . "'", $this->types ?: ['contract']));
    }

    private function fmt(?string $d, bool $long = false): string
    {
        if (!$d) {
            return '—';
        }
        $t = strtotime($d);
        return $t ? date($long ? 'j F Y' : 'j M Y', $t) : $d;
    }

    private function row(string $sql, array $binds = []): ?array
    {
        $res = $this->db->query($sql, $binds);
        if ($res === false || $res === true) {
            return null;
        }
        $r = $res->getRowArray();
        return $r ?: null;
    }

    private function rows(string $sql, array $binds = []): array
    {
        $res = $this->db->query($sql, $binds);
        if ($res === false || $res === true) {
            return [];
        }
        return $res->getResultArray();
    }
}
