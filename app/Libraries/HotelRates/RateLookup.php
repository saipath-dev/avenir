<?php

namespace App\Libraries\HotelRates;

/**
 * The pricing engine: "what does one night cost?"
 *
 * Loads every approved rate plan of a hotel once (a "bundle"), then answers
 * price() questions in memory. Used by the panel (lookup, coverage), by the
 * legacy projector (which fills the old hotel_rate table) and — later — by the
 * quotation timeline instead of NewTourController::get_meal_prices().
 *
 * Selection of the plan for a night
 *   1. status approved, validity covers the date, a season date range covers the date
 *   2. booking window (book_from/book_to) contains the booking date
 *   3. markets: plan includes the quotation's market (or has no market list) and does not exclude it;
 *      the market comes from opts['market'], else opts['country'] via hr2_market_country, else Settings default_market
 *   4. tier: 'all' always; 'fit' / 'group' by rooms / pax limits (defaults from Settings: fit_max_rooms / group_min_rooms) or by the tier hint
 *   5. order: priority DESC, plan type (tactical / offer / early bird > addendum > series > contract > migrated),
 *      explicit market match, explicit tier, newest approval
 *   6. opts['plan_types'] (a list of plan types) keeps the search to those kinds. A customer
 *      quotation passes the published kinds (contract, migrated, addendum, series — see
 *      quotationPlanTypes()), so a tactical or special-offer plan that would otherwise win the
 *      night is walked past and the contract underneath prices it. Without the option every
 *      approved plan competes, which is what the panel's own screens and the payment side want.
 *
 * Rate derivation inside the chosen plan (and its parent chain for addenda)
 *   a. the season × room type × meal plan row itself
 *   b. base room row + room supplement when the room is priced as base + supplement
 *   c. another meal plan's row + meal delta(s) ("BB reduction on HB", "AI on BB")
 *   d. otherwise the next candidate plan
 */
class RateLookup
{
    /** @var object */
    private $db;
    private array $bundles = [];
    private ?Settings $settings = null;
    private array $countryMarkets = [];

    public function __construct($db, ?Settings $settings = null)
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    public function settings(): Settings
    {
        return $this->settings ??= new Settings($this->db);
    }

    /** hr2_meta.default_market — the market assumed when a quotation gives none. */
    public function defaultMarket(): string
    {
        return $this->settings()->get('default_market') ?: 'ALL';
    }

    /**
     * Market for a quotation: explicit market code, else the country the
     * quotation stores (consignee.country, ISO-2) through hr2_market_country,
     * else the default market.
     */
    public function resolveMarket(array $opts): string
    {
        $m = strtoupper(trim((string) ($opts['market'] ?? '')));
        if ($m !== '') {
            return $m;
        }
        $iso = strtoupper(trim((string) ($opts['country'] ?? '')));
        if ($iso !== '') {
            if (!array_key_exists($iso, $this->countryMarkets)) {
                $this->countryMarkets[$iso] = $this->settings()->marketForCountry($iso);
            }
            return $this->countryMarkets[$iso];
        }
        return $this->defaultMarket();
    }

    /**
     * The plan types a customer quotation may be built on — the company's rule that only a
     * published contract rate is quoted. One setting, tf_meta.quote_plan_types, read by the
     * quotation engine and by every screen that prices a night for a quotation; the default
     * stands when the Tour Desk tables are not installed or the setting is blank.
     *
     * @return string[] lower-case plan types
     */
    public static function quotationPlanTypes($db): array
    {
        $default = ['contract', 'migrated', 'addendum', 'series'];
        try {
            if (!$db->query("SHOW TABLES LIKE 'tf_meta'")->getResultArray()) {
                return $default;
            }
            $r = $db->query("SELECT v FROM tf_meta WHERE k = 'quote_plan_types'")->getRowArray();
            $set = array_values(array_filter(array_map('trim', explode(',', strtolower((string) ($r['v'] ?? ''))))));
            return $set ?: $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function forget(?int $hotelId = null): void
    {
        if ($hotelId === null) {
            $this->bundles = [];
        } else {
            unset($this->bundles[$hotelId], $this->bundles[$hotelId . ':d']);
        }
    }

    // =====================================================================
    //  Bundle
    // =====================================================================

    /** Everything approved for one hotel, in memory. */
    public function bundle(int $hotelId, bool $includeDrafts = false): array
    {
        $key = $hotelId . ($includeDrafts ? ':d' : '');
        if (isset($this->bundles[$key])) {
            return $this->bundles[$key];
        }
        $hotel = $this->row('SELECT * FROM hr2_hotel WHERE id = ?', [$hotelId]);
        $b = ['hotel' => $hotel, 'room_types' => [], 'room_by_name' => [], 'room_by_legacy' => [], 'plans' => []];
        if (!$hotel) {
            return $this->bundles[$key] = $b;
        }
        foreach ($this->rows('SELECT * FROM hr2_room_type WHERE hotel_id = ? ORDER BY sort_order, name', [$hotelId]) as $rt) {
            $b['room_types'][(int) $rt['id']] = $rt;
            $b['room_by_name'][mb_strtolower(trim($rt['name']))] = (int) $rt['id'];
            if (!empty($rt['legacy_room_type_id']) && !isset($b['room_by_legacy'][(int) $rt['legacy_room_type_id']])) {
                $b['room_by_legacy'][(int) $rt['legacy_room_type_id']] = (int) $rt['id'];
            }
        }
        $statuses = $includeDrafts ? "('approved','draft')" : "('approved')";
        $plans = $this->rows("SELECT * FROM hr2_contract WHERE hotel_id = ? AND status IN {$statuses} ORDER BY id", [$hotelId]);
        if (!$plans) {
            return $this->bundles[$key] = $b;
        }
        $ids = array_map(static fn ($p) => (int) $p['id'], $plans);
        $in = implode(',', $ids);
        foreach ($plans as $p) {
            $p['markets'] = ['include' => [], 'exclude' => []];
            $p['seasons'] = [];
            $p['rates'] = [];
            foreach (['room_supplements', 'meal_deltas', 'meal_prices', 'supplements', 'child_rates', 'guide_policies', 'foc_rules', 'offers', 'cancellation_rules', 'payment_rules', 'restrictions', 'extras'] as $k) {
                $p[$k] = [];
            }
            $b['plans'][(int) $p['id']] = $p;
        }
        foreach ($this->rows("SELECT * FROM hr2_contract_market WHERE contract_id IN ({$in})") as $m) {
            $b['plans'][(int) $m['contract_id']]['markets'][$m['mode']][] = $m['market_code'];
        }
        $seasonPlan = [];
        foreach ($this->rows("SELECT * FROM hr2_season WHERE contract_id IN ({$in}) ORDER BY sort_order, id") as $s) {
            $s['dates'] = [];
            $b['plans'][(int) $s['contract_id']]['seasons'][(int) $s['id']] = $s;
            $seasonPlan[(int) $s['id']] = (int) $s['contract_id'];
        }
        if ($seasonPlan) {
            $sin = implode(',', array_keys($seasonPlan));
            foreach ($this->rows("SELECT * FROM hr2_season_date WHERE season_id IN ({$sin}) ORDER BY date_from") as $d) {
                $b['plans'][$seasonPlan[(int) $d['season_id']]]['seasons'][(int) $d['season_id']]['dates'][] = [$d['date_from'], $d['date_to']];
            }
            foreach ($this->rows("SELECT * FROM hr2_rate WHERE season_id IN ({$sin})") as $r) {
                $any = false;
                foreach (['sgl', 'dbl', 'tpl', 'quad', 'unit', 'child_with_bed', 'child_no_bed'] as $k) {
                    if ($r[$k] !== null && (float) $r[$k] <= 0) {
                        $r[$k] = null; // 0 is never a price
                    }
                    if ($r[$k] !== null && in_array($k, ['sgl', 'dbl', 'tpl', 'quad', 'unit'], true)) {
                        $any = true;
                    }
                }
                if (!$any) {
                    continue; // a row without a single price (the migration's "all_zero" placeholders) is "not offered": it never prices a night
                }
                $b['plans'][$seasonPlan[(int) $r['season_id']]]['rates'][(int) $r['season_id']][(int) $r['room_type_id']][$r['meal_plan']] = $r;
            }
        }
        $tables = ['room_supplements' => 'hr2_room_supplement', 'meal_deltas' => 'hr2_meal_delta', 'meal_prices' => 'hr2_meal_price', 'supplements' => 'hr2_supplement', 'child_rates' => 'hr2_child_rate', 'guide_policies' => 'hr2_guide_policy', 'foc_rules' => 'hr2_foc_rule', 'offers' => 'hr2_offer', 'cancellation_rules' => 'hr2_cancellation_rule', 'payment_rules' => 'hr2_payment_rule', 'restrictions' => 'hr2_restriction', 'extras' => 'hr2_extra'];
        foreach ($tables as $k => $t) {
            foreach ($this->rows("SELECT * FROM {$t} WHERE contract_id IN ({$in}) ORDER BY id") as $r) {
                $b['plans'][(int) $r['contract_id']][$k][] = $r;
            }
        }
        return $this->bundles[$key] = $b;
    }

    /** Room type id from a legacy room_type.id, an hr2 id ('hr2:12'), or a name. */
    public function resolveRoomTypeId(array $bundle, $ref): ?int
    {
        if (is_string($ref) && str_starts_with($ref, 'hr2:')) {
            $id = (int) substr($ref, 4);
            return isset($bundle['room_types'][$id]) ? $id : null;
        }
        if (is_int($ref) || (is_string($ref) && ctype_digit($ref))) {
            $id = (int) $ref;
            if (isset($bundle['room_by_legacy'][$id])) {
                return $bundle['room_by_legacy'][$id];
            }
            $legacy = $this->row('SELECT rtype FROM room_type WHERE id = ?', [$id]);
            $ref = $legacy ? $legacy['rtype'] : null;
        }
        if (is_string($ref) && trim($ref) !== '') {
            return $bundle['room_by_name'][mb_strtolower(trim($ref))] ?? null;
        }
        return null;
    }

    // =====================================================================
    //  price()
    // =====================================================================

    /**
     * @param int|string $roomRef  legacy room_type.id, 'hr2:<id>' or the room name
     * @param array $opts  market, booking_date, rooms, pax, tier, include_drafts, plan_id (force one plan)
     */
    public function price(int $hotelId, string $date, $roomRef, string $meal, array $opts = []): ?array
    {
        $meal = Sheet::meal($meal) ?? strtoupper($meal);
        if (!in_array($meal, Sheet::MEAL_PLANS, true)) {
            return null;
        }
        $b = $this->bundle($hotelId, !empty($opts['include_drafts']));
        if (!$b['hotel']) {
            return null;
        }
        $roomId = $this->resolveRoomTypeId($b, $roomRef);
        if (!$roomId) {
            return null;
        }
        $candidates = $this->candidates($b, $date, $opts);
        $tried = [];
        foreach ($candidates as $cand) {
            $plan = $b['plans'][$cand['plan_id']];
            $chain = $this->chain($b, $plan);
            $res = $this->derive($b, $chain, $cand['season_id'], $roomId, $meal);
            if ($res === null) {
                $tried[] = $plan['name'];
                continue;
            }
            $season = $plan['seasons'][$cand['season_id']];
            $res['plan'] = ['id' => (int) $plan['id'], 'name' => $plan['name'], 'plan_type' => $plan['plan_type'], 'rate_code' => $plan['rate_code'], 'tier' => $plan['tier'], 'priority' => (int) $plan['priority'], 'applies_to' => $plan['applies_to'] ?? 'both', 'voucher_note' => $plan['voucher_note'], 'status' => $plan['status'], 'parent_contract_id' => $plan['parent_contract_id']];
            $res['season'] = ['id' => (int) $season['id'], 'name' => $season['name'], 'kind' => $season['kind'], 'min_stay' => $season['min_stay'] !== null ? (int) $season['min_stay'] : ($plan['min_stay'] !== null ? (int) $plan['min_stay'] : null), 'max_rooms' => $season['max_rooms'], 'date_from' => $cand['range'][0], 'date_to' => $cand['range'][1]];
            $res['room_type'] = ['id' => $roomId, 'name' => $b['room_types'][$roomId]['name'], 'legacy_room_type_id' => $b['room_types'][$roomId]['legacy_room_type_id'], 'priced_as' => $b['room_types'][$roomId]['priced_as']];
            $res['meal_plan'] = $meal;
            $res['pricing_mode'] = $plan['pricing_mode'];
            $res['currency'] = $plan['currency'];
            $res['billing_currency'] = $plan['billing_currency'];
            $res['taxes_included'] = (int) $plan['taxes_included'];
            $res['tax_pct'] = $plan['tax_pct'];
            $res['date'] = $date;
            $res['hotel_id'] = $hotelId;
            $res['hotel_name'] = $b['hotel']['name'];
            $res['supplements'] = $this->supplementsForNight($b, $chain, $date, $meal, $roomId);
            $res['restrictions'] = $this->restrictionsForNight($chain, $date, $meal);
            $res['child_rates'] = $this->firstDefined($chain, 'child_rates', static fn ($r) => ($r['meal_plan'] === null || $r['meal_plan'] === $meal) && ($r['room_type_id'] === null || (int) $r['room_type_id'] === $roomId));
            $res['guide_policies'] = $this->firstDefined($chain, 'guide_policies');
            $res['foc_rules'] = $this->firstDefined($chain, 'foc_rules');
            $res['meal_prices'] = $this->firstDefined($chain, 'meal_prices');
            $res['offers'] = $this->firstDefined($chain, 'offers', static fn ($o) => (!$o['stay_from'] || $o['stay_from'] <= $date) && (!$o['stay_to'] || $o['stay_to'] >= $date));
            $res['other_plans'] = array_values(array_unique(array_merge($tried, array_map(static fn ($c) => $b['plans'][$c['plan_id']]['name'], array_slice($candidates, array_search($cand, $candidates, true) + 1)))));
            return $res;
        }
        return null;
    }

    /** Candidate (plan, season, range) triples for a night, best first. */
    public function candidates(array $b, string $date, array $opts = []): array
    {
        $market = $this->resolveMarket($opts);
        $bookingDate = $opts['booking_date'] ?? date('Y-m-d');
        $rooms = (int) ($opts['rooms'] ?? 1);
        $pax = (int) ($opts['pax'] ?? 2);
        $tierHint = $opts['tier'] ?? null;
        $forcePlan = !empty($opts['plan_id']) ? (int) $opts['plan_id'] : null;
        // purpose: 'quotation' (default — what the customer is quoted; ad hoc payment-only plans are invisible)
        //          'payment'   (what the hotel is paid; ad hoc payment-only plans win over everything else)
        $purpose = ($opts['purpose'] ?? 'quotation') === 'payment' ? 'payment' : 'quotation';
        // only these kinds of plan, when the caller says so (a quotation: the published kinds)
        $types = isset($opts['plan_types']) && is_array($opts['plan_types']) && $opts['plan_types'] !== [] ? array_map('strtolower', $opts['plan_types']) : null;
        $out = [];
        foreach ($b['plans'] as $pid => $p) {
            if ($forcePlan && $pid !== $forcePlan) {
                continue;
            }
            if ($p['status'] !== 'approved' && empty($opts['include_drafts'])) {
                continue;
            }
            $applies = $p['applies_to'] ?? 'both';
            if (!$forcePlan && $applies !== 'both' && $applies !== $purpose) {
                continue;
            }
            if (!$forcePlan && $types !== null && !in_array(strtolower((string) $p['plan_type']), $types, true)) {
                continue;
            }
            $adhocFirst = ($purpose === 'payment' && $applies === 'payment') ? 1 : 0;
            if ($p['valid_from'] > $date || $p['valid_to'] < $date) {
                continue;
            }
            if (!$forcePlan) {
                if (($p['book_from'] && $bookingDate < $p['book_from']) || ($p['book_to'] && $bookingDate > $p['book_to'])) {
                    continue;
                }
                $marketScore = $this->marketMatch($p, $market);
                if ($marketScore < 0) {
                    continue;
                }
                $tierScore = $this->tierMatch($p, $rooms, $pax, $tierHint);
                if ($tierScore < 0) {
                    continue;
                }
            } else {
                $marketScore = 0; $tierScore = 0;
            }
            // every season of the plan that covers the night is a candidate; when seasons
            // overlap (legacy data copied across as it was) the narrowest range is tried first
            // and price() moves on to the next when the room / meal plan is not in it
            foreach ($p['seasons'] as $sid => $s) {
                foreach ($s['dates'] as [$from, $to]) {
                    if ($from <= $date && $to >= $date) {
                        $out[] = ['plan_id' => $pid, 'season_id' => $sid, 'range' => [$from, $to], 'sort' => [$adhocFirst, (int) $p['priority'], Sheet::PLAN_RANK[$p['plan_type']] ?? 0, $marketScore, $tierScore, (string) $p['approved_at'], $pid, -Dates::diffDays($from, $to)]];
                        continue 2;
                    }
                }
            }
        }
        usort($out, static function ($x, $y) {
            for ($i = 0; $i < 8; $i++) {
                $c = $y['sort'][$i] <=> $x['sort'][$i];
                if ($c !== 0) {
                    return $c;
                }
            }
            return 0;
        });
        return $out;
    }

    /** -1 = no, 0 = all-market plan, 1 = explicit market match. */
    private function marketMatch(array $p, string $market): int
    {
        $inc = $p['markets']['include'] ?? [];
        $exc = $p['markets']['exclude'] ?? [];
        if ($market !== 'ALL' && in_array($market, $exc, true)) {
            return -1;
        }
        if (!$inc) {
            return 0;
        }
        if ($market !== 'ALL' && in_array($market, $inc, true)) {
            return 1;
        }
        return -1;
    }

    /** -1 = no, 0 = tier 'all', 1 = explicit tier match. */
    private function tierMatch(array $p, int $rooms, int $pax, ?string $hint): int
    {
        $tier = $p['tier'] ?: 'all';
        if ($tier === 'all') {
            return 0;
        }
        if ($hint !== null && $hint !== '') {
            return $hint === $tier ? 1 : -1;
        }
        if (in_array($tier, ['wedding', 'long_stay'], true)) {
            return -1; // only with an explicit hint
        }
        $minR = $p['tier_min_rooms'] !== null ? (int) $p['tier_min_rooms'] : null;
        $maxR = $p['tier_max_rooms'] !== null ? (int) $p['tier_max_rooms'] : null;
        $minP = $p['tier_min_pax'] !== null ? (int) $p['tier_min_pax'] : null;
        $maxP = $p['tier_max_pax'] !== null ? (int) $p['tier_max_pax'] : null;
        if ($minR === null && $maxR === null && $minP === null && $maxP === null) {
            // the sheet gives no numbers: use the limits from Settings
            if ($tier === 'fit') { $maxR = (int) $this->settings()->get('fit_max_rooms'); }
            if ($tier === 'group') { $minR = (int) $this->settings()->get('group_min_rooms'); }
        }
        if (($minR !== null && $rooms < $minR) || ($maxR !== null && $rooms > $maxR)) {
            return -1;
        }
        if (($minP !== null && $pax < $minP) || ($maxP !== null && $pax > $maxP)) {
            return -1;
        }
        return 1;
    }

    /** [plan, parent, grandparent …] — parents are used for anything the plan does not define. */
    public function chain(array $b, array $plan): array
    {
        $chain = [$plan];
        $seen = [(int) $plan['id'] => true];
        $cur = $plan;
        while (!empty($cur['parent_contract_id'])) {
            $pid = (int) $cur['parent_contract_id'];
            if (isset($seen[$pid])) {
                break;
            }
            $seen[$pid] = true;
            $parent = $b['plans'][$pid] ?? $this->loadPlanIntoBundle($b, $pid);
            if (!$parent) {
                break;
            }
            $chain[] = $parent;
            $cur = $parent;
        }
        return $chain;
    }

    /** A superseded parent is still consulted for an addendum's inherited terms. */
    private function loadPlanIntoBundle(array &$b, int $planId): ?array
    {
        $p = $this->row('SELECT * FROM hr2_contract WHERE id = ? AND hotel_id = ?', [$planId, (int) $b['hotel']['id']]);
        if (!$p) {
            return null;
        }
        $tmp = $this->bundleForPlans([$p]);
        $plan = $tmp[$planId];
        $hid = (int) $b['hotel']['id'];
        foreach ([$hid, $hid . ':d'] as $key) {
            if (isset($this->bundles[$key])) {
                $this->bundles[$key]['plans'][$planId] = $plan;
            }
        }
        $b['plans'][$planId] = $plan;
        return $plan;
    }

    private function bundleForPlans(array $plans): array
    {
        $out = [];
        foreach ($plans as $p) {
            $pid = (int) $p['id'];
            $p['markets'] = ['include' => [], 'exclude' => []];
            foreach ($this->rows('SELECT * FROM hr2_contract_market WHERE contract_id = ?', [$pid]) as $m) {
                $p['markets'][$m['mode']][] = $m['market_code'];
            }
            $p['seasons'] = []; $p['rates'] = [];
            foreach ($this->rows('SELECT * FROM hr2_season WHERE contract_id = ? ORDER BY sort_order, id', [$pid]) as $s) {
                $s['dates'] = [];
                foreach ($this->rows('SELECT date_from, date_to FROM hr2_season_date WHERE season_id = ? ORDER BY date_from', [(int) $s['id']]) as $d) {
                    $s['dates'][] = [$d['date_from'], $d['date_to']];
                }
                $p['seasons'][(int) $s['id']] = $s;
                foreach ($this->rows('SELECT * FROM hr2_rate WHERE season_id = ?', [(int) $s['id']]) as $r) {
                    $p['rates'][(int) $s['id']][(int) $r['room_type_id']][$r['meal_plan']] = $r;
                }
            }
            $tables = ['room_supplements' => 'hr2_room_supplement', 'meal_deltas' => 'hr2_meal_delta', 'meal_prices' => 'hr2_meal_price', 'supplements' => 'hr2_supplement', 'child_rates' => 'hr2_child_rate', 'guide_policies' => 'hr2_guide_policy', 'foc_rules' => 'hr2_foc_rule', 'offers' => 'hr2_offer', 'cancellation_rules' => 'hr2_cancellation_rule', 'payment_rules' => 'hr2_payment_rule', 'restrictions' => 'hr2_restriction', 'extras' => 'hr2_extra'];
            foreach ($tables as $k => $t) {
                $p[$k] = $this->rows("SELECT * FROM {$t} WHERE contract_id = ? ORDER BY id", [$pid]);
            }
            $out[$pid] = $p;
        }
        return $out;
    }

    // =====================================================================
    //  Derivation
    // =====================================================================

    /**
     * Price for (season of the first plan in $chain) × room × meal, using the
     * chain for room supplements and meal deltas. null = not priced here.
     */
    public function derive(array $b, array $chain, int $seasonId, int $roomId, string $meal): ?array
    {
        $plan = $chain[0];
        $mode = $plan['pricing_mode'];
        $room = $b['room_types'][$roomId] ?? null;
        if (!$room) {
            return null;
        }
        $components = [];

        // a. direct row
        $direct = $plan['rates'][$seasonId][$roomId][$meal] ?? null;
        if ($direct) {
            return $this->pack($direct, $mode, 'direct', [['kind' => 'rate', 'room' => $room['name'], 'meal' => $meal, 'sgl' => $direct['sgl'], 'dbl' => $direct['dbl'], 'tpl' => $direct['tpl'], 'quad' => $direct['quad'], 'unit' => $direct['unit']]]);
        }

        // b. base room + room supplement (any room with a base room and a supplement row in the chain)
        $baseId = !empty($room['base_room_type_id']) ? (int) $room['base_room_type_id'] : null;
        if ($baseId !== null && $baseId !== $roomId) {
            $rs = $this->findRoomSupplement($chain, $seasonId, $roomId);
            if ($rs) {
                $baseRes = $this->deriveMeal($b, $chain, $seasonId, $baseId, $meal, $mode);
                if ($baseRes) {
                    $res = $baseRes;
                    $amount = (float) $rs['amount'];
                    foreach (['sgl' => 1, 'dbl' => 2, 'tpl' => 3, 'quad' => 4] as $occ => $n) {
                        if ($res[$occ] !== null) {
                            $res[$occ] = round($res[$occ] + ($rs['per'] === 'person' && $mode === 'per_room' ? $amount * $n : $amount), 2);
                        }
                    }
                    if ($res['unit'] !== null) {
                        $res['unit'] = round($res['unit'] + $amount, 2);
                    }
                    $res['derived'] = ($res['derived'] === 'direct' ? 'room_supplement' : $res['derived'] . '+room_supplement');
                    $res['components'][] = ['kind' => 'room_supplement', 'room' => $room['name'], 'base' => $b['room_types'][$baseId]['name'] ?? $baseId, 'amount' => $amount, 'per' => $rs['per'], 'plan' => $rs['_plan_name'] ?? null];
                    return $res;
                }
            }
        }

        // c. meal delta from another meal plan of the same room
        return $this->deriveMeal($b, $chain, $seasonId, $roomId, $meal, $mode);
    }

    /** Direct row for (room, meal) or another meal plan's row + delta chain. */
    private function deriveMeal(array $b, array $chain, int $seasonId, int $roomId, string $meal, string $mode): ?array
    {
        $plan = $chain[0];
        $rows = $plan['rates'][$seasonId][$roomId] ?? [];
        if (isset($rows[$meal])) {
            $r = $rows[$meal];
            return $this->pack($r, $mode, 'direct', [['kind' => 'rate', 'room' => $b['room_types'][$roomId]['name'], 'meal' => $meal, 'sgl' => $r['sgl'], 'dbl' => $r['dbl'], 'tpl' => $r['tpl'], 'quad' => $r['quad'], 'unit' => $r['unit']]]);
        }
        if (!$rows || $mode === 'per_unit') {
            return null;
        }
        // breadth-first over meal plans reachable through deltas (max 3 steps)
        $deltas = $this->deltasFor($chain, $seasonId);
        if (!$deltas) {
            return null;
        }
        $available = array_keys($rows);
        usort($available, static fn ($x, $y) => abs(Sheet::MEAL_ORDER[$x] - Sheet::MEAL_ORDER[$meal]) <=> abs(Sheet::MEAL_ORDER[$y] - Sheet::MEAL_ORDER[$meal]));
        foreach ($available as $start) {
            $path = $this->deltaPath($deltas, $start, $meal, 3);
            if ($path === null) {
                continue;
            }
            $r = $rows[$start];
            $res = $this->pack($r, $mode, 'meal_delta', [['kind' => 'rate', 'room' => $b['room_types'][$roomId]['name'], 'meal' => $start, 'sgl' => $r['sgl'], 'dbl' => $r['dbl'], 'tpl' => $r['tpl'], 'quad' => $r['quad'], 'unit' => $r['unit']]]);
            foreach ($path as $d) {
                $adult = (float) $d['adult'];
                foreach (['sgl' => 1, 'dbl' => 2, 'tpl' => 3, 'quad' => 4] as $occ => $n) {
                    if ($res[$occ] !== null) {
                        $res[$occ] = round($res[$occ] + ($mode === 'per_room' ? $adult * $n : $adult), 2);
                    }
                }
                if ($d['child'] !== null) {
                    foreach (['child_with_bed', 'child_no_bed'] as $ck) {
                        if ($res[$ck] !== null) {
                            $res[$ck] = round($res[$ck] + (float) $d['child'], 2);
                        }
                    }
                }
                $res['components'][] = ['kind' => 'meal_delta', 'from' => $d['from_plan'], 'to' => $d['to_plan'], 'adult' => $adult, 'child' => $d['child'], 'plan' => $d['_plan_name'] ?? null];
            }
            return $res;
        }
        return null;
    }

    /** Meal deltas visible from a plan chain for a season: [from][to] => row. Plan-own, season-specific first. */
    private function deltasFor(array $chain, int $seasonId): array
    {
        $out = [];
        foreach ($chain as $depth => $p) {
            $rows = $p['meal_deltas'] ?? [];
            // season-specific rows of the plan itself win; general rows next; parents last
            usort($rows, static fn ($x, $y) => (($y['season_id'] !== null) <=> ($x['season_id'] !== null)));
            foreach ($rows as $d) {
                if ($d['season_id'] !== null && $depth === 0 && (int) $d['season_id'] !== $seasonId) {
                    continue;
                }
                if ($d['season_id'] !== null && $depth > 0) {
                    continue; // a parent's season-bound delta cannot be matched to this plan's season
                }
                if (!isset($out[$d['from_plan']][$d['to_plan']])) {
                    $d['_plan_name'] = $p['name'];
                    $out[$d['from_plan']][$d['to_plan']] = $d;
                }
            }
            if ($out && $depth === 0) {
                // the plan defines its own deltas — do not mix in the parent's
                break;
            }
        }
        return $out;
    }

    private function deltaPath(array $deltas, string $from, string $to, int $maxDepth): ?array
    {
        $queue = [[$from, []]];
        $seen = [$from => true];
        while ($queue) {
            [$cur, $path] = array_shift($queue);
            if (count($path) >= $maxDepth) {
                continue;
            }
            foreach ($deltas[$cur] ?? [] as $next => $d) {
                if (isset($seen[$next])) {
                    continue;
                }
                $np = array_merge($path, [$d]);
                if ($next === $to) {
                    return $np;
                }
                $seen[$next] = true;
                $queue[] = [$next, $np];
            }
        }
        return null;
    }

    private function findRoomSupplement(array $chain, int $seasonId, int $roomId): ?array
    {
        foreach ($chain as $depth => $p) {
            $best = null;
            foreach ($p['room_supplements'] ?? [] as $rs) {
                if ((int) $rs['room_type_id'] !== $roomId) {
                    continue;
                }
                if ($rs['season_id'] !== null) {
                    if ($depth === 0 && (int) $rs['season_id'] === $seasonId) {
                        $rs['_plan_name'] = $p['name'];
                        return $rs;
                    }
                    continue;
                }
                $best = $best ?? $rs;
            }
            if ($best) {
                $best['_plan_name'] = $p['name'];
                return $best;
            }
        }
        return null;
    }

    private function pack(array $r, string $mode, string $derived, array $components): array
    {
        $n = static fn ($v) => $v === null ? null : (float) $v;
        return [
            'sgl' => $n($r['sgl']), 'dbl' => $n($r['dbl']), 'tpl' => $n($r['tpl']), 'quad' => $n($r['quad']), 'unit' => $n($r['unit']),
            'child_with_bed' => $n($r['child_with_bed'] ?? null), 'child_no_bed' => $n($r['child_no_bed'] ?? null),
            'quality_flags' => $r['quality_flags'] ?? null, 'derived' => $derived, 'components' => $components,
        ];
    }

    // =====================================================================
    //  Night-level extras
    // =====================================================================

    /** Supplements that apply to the night; each type is taken from the first plan in the chain that defines it. */
    public function supplementsForNight(array $b, array $chain, string $date, string $meal, ?int $roomId): array
    {
        $out = [];
        $typesDone = [];
        foreach ($chain as $depth => $p) {
            $typesHere = [];
            foreach ($p['supplements'] ?? [] as $s) {
                $typesHere[$s['type']] = true;
            }
            foreach ($p['supplements'] ?? [] as $s) {
                if (isset($typesDone[$s['type']])) {
                    continue;
                }
                if (!$this->supplementApplies($p, $s, $date, $meal, $roomId)) {
                    continue;
                }
                $s['plan_name'] = $p['name'];
                $s['inherited'] = $depth > 0;
                $out[] = $s;
            }
            foreach (array_keys($typesHere) as $t) {
                $typesDone[$t] = true;
            }
        }
        usort($out, static fn ($x, $y) => [(int) $y['compulsory'], $x['id']] <=> [(int) $x['compulsory'], $y['id']]);
        return $out;
    }

    private function supplementApplies(array $plan, array $s, string $date, string $meal, ?int $roomId): bool
    {
        if ($s['date_from'] && $s['date_to']) {
            if ($date < $s['date_from'] || $date > $s['date_to']) {
                return false;
            }
        } elseif ($s['season_id'] !== null) {
            $season = $plan['seasons'][(int) $s['season_id']] ?? null;
            if (!$season || !$this->inSeason($season, $date)) {
                return false;
            }
        }
        if ($s['applies_meal_plans'] && !in_array($meal, explode(',', $s['applies_meal_plans']), true)) {
            return false;
        }
        if ($s['room_type_id'] !== null && $roomId !== null && (int) $s['room_type_id'] !== $roomId) {
            return false;
        }
        return true;
    }

    public function restrictionsForNight(array $chain, string $date, string $meal): array
    {
        $out = [];
        foreach ($chain as $depth => $p) {
            foreach ($p['restrictions'] ?? [] as $r) {
                if ($r['date_from'] <= $date && $r['date_to'] >= $date) {
                    $r['plan_name'] = $p['name'];
                    $r['inherited'] = $depth > 0;
                    $r['violated'] = $r['kind'] === 'meal_plan_required' && $r['value'] && !in_array($meal, explode(',', Sheet::mealList($r['value']) ?? ''), true);
                    $out[] = $r;
                }
            }
            if ($out) {
                break;
            }
        }
        return $out;
    }

    private function firstDefined(array $chain, string $key, ?callable $filter = null): array
    {
        foreach ($chain as $depth => $p) {
            $rows = $p[$key] ?? [];
            if (!$rows) {
                continue;
            }
            if ($filter) {
                $rows = array_values(array_filter($rows, $filter));
            }
            foreach ($rows as &$r) {
                $r['plan_name'] = $p['name'];
                $r['inherited'] = $depth > 0;
            }
            unset($r);
            return $rows;
        }
        return [];
    }

    private function inSeason(array $season, string $date): bool
    {
        foreach ($season['dates'] as [$f, $t]) {
            if ($f <= $date && $t >= $date) {
                return true;
            }
        }
        return false;
    }

    // =====================================================================
    //  Coverage helpers
    // =====================================================================

    /**
     * Every boundary date across the approved plans of a hotel between $from and $to,
     * as elementary [from, to] intervals in which the winning rate cannot change.
     */
    public function intervals(array $b, string $from, string $to): array
    {
        $points = [$from];
        foreach ($b['plans'] as $p) {
            if ($p['status'] !== 'approved') {
                continue;
            }
            foreach ([$p['valid_from'], Dates::next($p['valid_to'])] as $d) {
                if ($d > $from && $d <= $to) {
                    $points[] = $d;
                }
            }
            foreach ($p['seasons'] as $s) {
                foreach ($s['dates'] as [$f, $t]) {
                    foreach ([$f, Dates::next($t)] as $d) {
                        if ($d > $from && $d <= $to) {
                            $points[] = $d;
                        }
                    }
                }
            }
            foreach ($p['supplements'] as $s) {
                if ($s['date_from'] && $s['date_to']) {
                    foreach ([$s['date_from'], Dates::next($s['date_to'])] as $d) {
                        if ($d > $from && $d <= $to) {
                            $points[] = $d;
                        }
                    }
                }
            }
            foreach ($p['room_supplements'] as $rs) {
                // season-bound room supplements change at season boundaries already listed
            }
        }
        $points = array_values(array_unique($points));
        sort($points);
        $out = [];
        for ($i = 0; $i < count($points); $i++) {
            $end = isset($points[$i + 1]) ? Dates::prev($points[$i + 1]) : $to;
            if ($end >= $points[$i]) {
                $out[] = [$points[$i], $end];
            }
        }
        return $out;
    }

    protected function row(string $sql, array $binds = []): ?array
    {
        $res = $this->db->query($sql, $binds);
        if ($res === false || $res === true) {
            return null;
        }
        $r = $res->getRowArray();
        return $r ?: null;
    }

    protected function rows(string $sql, array $binds = []): array
    {
        $res = $this->db->query($sql, $binds);
        if ($res === false || $res === true) {
            return [];
        }
        return $res->getResultArray();
    }
}
