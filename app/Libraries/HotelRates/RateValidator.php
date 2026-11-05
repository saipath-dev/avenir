<?php

namespace App\Libraries\HotelRates;

/**
 * Validates a normalised sheet (see Sheet) before anything is written.
 *
 * Issues: ['level' => 'error'|'warning', 'code' => 'E05', 'message' => '...', 'section' => 'seasons', 'season' => int|null, 'row' => int|null]
 *
 * Errors block approval (drafts may still be saved). Warnings must be
 * acknowledged by the person approving. The codes are stable so staff learn
 * them; the messages say what to do, not just what is wrong.
 *
 *  E01 hotel unmatched              E02 validity / currency            E03 season dates
 *  E04 season outside validity      E05 duplicate / overlapping rows   E07 room type / meal plan
 *  E08 empty rate rows              E09 zero or negative rate          E10 supplement
 *  E11 booking window               E12 markets                        E13 meal delta
 *  E14 child rule                   E15 cancellation rule              E16 room supplement
 *  E17 parent plan                  E18 unit pricing
 *  W01 gaps  W02 expiry  W03 meal order  W04 occupancy order  W05 tiny/huge  W06 no gala
 *  W07 child bands  W08 new room type  W09 change vs previous  W10 overlaps other plan
 *  W11 cancellation bands  W12 restriction dates  W13 booking window closed
 *  W14 supplement room without base rate  W15 taxes  W16 seasons of a tactical plan without max rooms
 *  W17 min stay  W18 guide policy  W19 per-person sanity
 */
class RateValidator
{
    /**
     * Thresholds come from Settings (hr2_meta) through $options['settings'];
     * these are only the fallbacks when the validator runs without a database.
     */
    private const FALLBACK = ['tiny_rate' => 10.0, 'huge_rate' => 3000.0, 'expiry_warning_days' => 90, 'change_warning_pct' => 40.0, 'gala_dates' => ['12-24', '12-31'], 'gala_child_pct' => 50, 'child_max_age' => 18];

    private array $issues = [];
    private array $cfg = [];

    /**
     * @param array $sheet     normalised sheet
     * @param array $existing  approved plans of the same hotel (other than this one):
     *                         [['contract_id','contract_name','plan_type','tier','markets'=>[codes],'ranges'=>[[from,to]],'room_types'=>[names]]]
     * @param array $options   'today', 'known_room_types' => [lower name => row], 'previous_rates' => ["room|meal" => [sgl,dbl,tpl]],
     *                         'markets' => [code => name], 'parent' => contract row|null,
     *                         'settings' => [key => typed value] (see Settings::DEFINITIONS)
     */
    public function validate(array $sheet, array $existing = [], array $options = []): array
    {
        $this->issues = [];
        $this->cfg = self::FALLBACK;
        foreach ((array) ($options['settings'] ?? []) as $k => $v) {
            if (array_key_exists($k, self::FALLBACK) && $v !== null && $v !== '' && $v !== []) {
                $this->cfg[$k] = $k === 'gala_dates' ? (is_array($v) ? $v : Settings::parseDates((string) $v)) : $v;
            }
        }
        $sheet = Sheet::normalise($sheet);
        $today = $options['today'] ?? date('Y-m-d');
        $known = $options['known_room_types'] ?? null;
        $previous = $options['previous_rates'] ?? [];
        $marketCodes = $options['markets'] ?? null;
        $tiny = (float) $this->cfg['tiny_rate'];
        $huge = (float) $this->cfg['huge_rate'];
        $changePct = (float) $this->cfg['change_warning_pct'];
        $expiryDays = (int) $this->cfg['expiry_warning_days'];
        $galaDates = $this->cfg['gala_dates'];
        $galaChildPct = (float) $this->cfg['gala_child_pct'];
        $childMaxAge = (float) $this->cfg['child_max_age'];

        $hotel = $sheet['hotel'];
        $c = $sheet['contract'];
        $mode = $c['pricing_mode'];

        // ---- header --------------------------------------------------------
        if (empty($hotel['id'])) {
            $this->error('E01', 'Hotel could not be matched to a hotel record' . ($hotel['name'] !== '' ? " ('{$hotel['name']}')" : '') . '. Pick the hotel from the list — imports never create hotels.', 'header');
        }
        if ($c['name'] === '') {
            $this->error('E02', 'Give the rate plan a name (e.g. "Winter 2026/27 travel agent rates" or the hotel\'s rate code).', 'header');
        }
        $vFrom = $c['valid_from'];
        $vTo = $c['valid_to'];
        if (!$vFrom || !$vTo) {
            $this->error('E02', 'Validity (stay dates from / to) is missing or unreadable.', 'header');
        } elseif ($vFrom > $vTo) {
            $this->error('E02', "Validity is reversed ({$vFrom} to {$vTo}).", 'header');
        }
        if (!preg_match('/^[A-Z]{3}$/', $c['currency'])) {
            $this->error('E02', "Currency '{$c['currency']}' is not a 3-letter code (USD, LKR, EUR).", 'header');
        }
        if ($c['book_from'] && $c['book_to'] && $c['book_from'] > $c['book_to']) {
            $this->error('E11', "Booking window is reversed ({$c['book_from']} to {$c['book_to']}).", 'header');
        }
        if ($c['book_to'] && $c['book_to'] < $today) {
            $this->warning('W13', "The booking window closed on {$c['book_to']}: this plan will never be picked for new quotations. Keep it only for bookings made before that date.", 'header');
        }
        if (in_array($c['plan_type'], ['tactical', 'early_bird', 'special_offer'], true) && !$c['book_to'] && !$c['rate_code'] && ($c['applies_to'] ?? 'both') !== 'payment') {
            $this->warning('W13', 'Tactical / early-bird / special offers usually have a "book before" date or a rate code that must appear on the voucher. Check the sheet and fill in Booking window or Rate code.', 'header');
        }
        if ($c['plan_type'] === 'addendum' && !$c['parent_contract_id']) {
            $this->error('E17', 'An addendum must point to the base plan it amends (Parent plan). Everything the addendum does not define is taken from that plan.', 'header');
        }
        if ($c['parent_contract_id'] && $c['id'] && (int) $c['parent_contract_id'] === (int) $c['id']) {
            $this->error('E17', 'A plan cannot be its own parent.', 'header');
        }
        if (!empty($options['parent']) && ($options['parent']['hotel_id'] ?? null) && $hotel['id'] && (int) $options['parent']['hotel_id'] !== (int) $hotel['id']) {
            $this->error('E17', 'The parent plan belongs to a different hotel.', 'header');
        }
        if (in_array($c['tier'], ['fit', 'group'], true) && $c['tier_min_rooms'] === null && $c['tier_max_rooms'] === null && $c['tier_min_pax'] === null && $c['tier_max_pax'] === null) {
            $this->warning('W10', "Tier is '{$c['tier']}' but no room / pax limits are given (e.g. FIT = up to 4 rooms, Group = 5 rooms or more). Without limits the tier is only a label.", 'header');
        }
        foreach ([['tier_min_rooms', 'tier_max_rooms'], ['tier_min_pax', 'tier_max_pax']] as [$lo, $hi]) {
            if ($c[$lo] !== null && $c[$hi] !== null && $c[$lo] > $c[$hi]) {
                $this->error('E02', "Tier limits are reversed ({$lo} {$c[$lo]} > {$hi} {$c[$hi]}).", 'header');
            }
        }
        if (!$c['taxes_included'] && $c['tax_pct'] === null) {
            $this->warning('W15', 'Rates are marked as excluding taxes but no tax percentage is given; quotations cannot add the tax. Enter the % (e.g. 18 for VAT, 34.34 for Hilton-style SC+VAT).', 'header');
        }
        // markets
        $seenMarkets = [];
        foreach ($c['markets'] as $m) {
            if ($marketCodes !== null && !isset($marketCodes[$m['code']])) {
                $this->error('E12', "Market code '{$m['code']}' is not in the market list. Use one of: " . implode(', ', array_keys($marketCodes)) . '.', 'header');
            }
            if (isset($seenMarkets[$m['code']]) && $seenMarkets[$m['code']] !== $m['mode']) {
                $this->error('E12', "Market '{$m['code']}' is both included and excluded.", 'header');
            }
            $seenMarkets[$m['code']] = $m['mode'];
        }
        $hasInclude = in_array('include', array_column($c['markets'], 'mode'), true);
        if (!$hasInclude && in_array('exclude', array_column($c['markets'], 'mode'), true)) {
            // fine: "all markets except X"
        }

        // ---- room types declared on the sheet ---------------------------------
        $sheetRooms = [];
        foreach ($sheet['room_types'] as $ri => $rt) {
            $sheetRooms[mb_strtolower($rt['name'])] = $rt;
            if ($rt['priced_as'] === 'supplement' && empty($rt['base_room_type'])) {
                $this->error('E16', "Room type '{$rt['name']}' is priced as base + supplement but no base room is named.", 'room_types', null, $ri);
            }
            if ($mode === 'per_unit' && ($rt['unit_pax_max'] ?? null) === null) {
                $this->warning('W19', "Unit-priced room '{$rt['name']}': enter the maximum pax the unit sleeps so quotations can check the group fits.", 'room_types', null, $ri);
            }
        }
        $roomInfo = static function (string $lowerName) use ($sheetRooms, $known): ?array {
            if (isset($sheetRooms[$lowerName])) {
                return $sheetRooms[$lowerName];
            }
            if (is_array($known) && isset($known[$lowerName])) {
                return $known[$lowerName];
            }
            return null;
        };

        // ---- seasons & rates -----------------------------------------------
        $seasons = $sheet['seasons'];
        if (empty($seasons)) {
            $this->error('E03', 'The plan has no seasons. Add at least one season with its dates and rates.', 'seasons');
        }
        $coverByRoom = [];      // lower room => [[from, to, seasonIndex]]
        $rangesAll = [];        // all season ranges
        $seasonNames = [];
        $baseRoomsWithRates = [];
        $supplementRoomsUsed = [];
        foreach ($seasons as $si => $s) {
            $label = 'Season ' . ($si + 1) . ($s['name'] ? " ({$s['name']})" : '');
            if ($s['name'] !== null) {
                $k = mb_strtolower($s['name']);
                if (isset($seasonNames[$k])) {
                    $this->error('E03', "{$label}: two seasons have the same name. Names must be unique inside a plan (they are used to link supplements and meal deltas).", 'seasons', $si);
                }
                $seasonNames[$k] = $si;
            }
            if (empty($s['dates'])) {
                $this->error('E03', "{$label}: has no date range. Add the dates (a season may have several ranges, e.g. 01 Nov–20 Dec and 01–30 Apr).", 'seasons', $si);
            }
            $validRanges = [];
            foreach ($s['dates'] as $di => $d) {
                if (!$d['from'] || !$d['to']) {
                    $raw = trim((string) ($d['raw_from'] ?? '')) . ' → ' . trim((string) ($d['raw_to'] ?? ''));
                    $this->error('E03', "{$label}, date range " . ($di + 1) . ": start or end date is missing or unreadable ({$raw}). Use 01.11.2026 or 2026-11-01.", 'seasons', $si);
                    continue;
                }
                if ($d['from'] > $d['to']) {
                    $this->error('E03', "{$label}: {$d['from']} is after {$d['to']}.", 'seasons', $si);
                    continue;
                }
                if ($vFrom && $vTo && $vFrom <= $vTo && ($d['from'] < $vFrom || $d['to'] > $vTo)) {
                    $this->error('E04', "{$label}: {$d['from']} to {$d['to']} falls outside the plan validity {$vFrom} to {$vTo}. Fix the season dates or widen the validity. (Sheets often carry last year's dates by mistake.)", 'seasons', $si);
                }
                $validRanges[] = [$d['from'], $d['to']];
                $rangesAll[] = [$d['from'], $d['to'], $si];
            }
            // ranges inside one season must not overlap each other
            for ($i = 0; $i < count($validRanges); $i++) {
                for ($j = $i + 1; $j < count($validRanges); $j++) {
                    if (Dates::overlaps($validRanges[$i][0], $validRanges[$i][1], $validRanges[$j][0], $validRanges[$j][1])) {
                        $this->error('E05', "{$label}: its date ranges overlap each other ({$validRanges[$i][0]}–{$validRanges[$i][1]} and {$validRanges[$j][0]}–{$validRanges[$j][1]}).", 'seasons', $si);
                    }
                }
            }
            if ($s['max_rooms'] !== null && !in_array($c['plan_type'], ['tactical', 'special_offer', 'early_bird'], true)) {
                $this->warning('W16', "{$label}: a maximum number of rooms is set ({$s['max_rooms']}) although the plan is a normal contract. Usually only tactical offers cap rooms; check the sheet.", 'seasons', $si);
            }

            $rates = $s['rates'];
            if (empty($rates)) {
                $this->error('E08', "{$label}: has no rate rows. Every season needs at least one room type with a rate.", 'seasons', $si);
                continue;
            }
            $anyPrimary = false;
            $seenRows = [];
            $roomsInSeason = [];
            foreach ($rates as $ri => $row) {
                $room = $row['room_type'];
                $meal = $row['meal_plan'];
                if ($room === '') {
                    $this->error('E07', "{$label}, row " . ($ri + 1) . ': room type is missing.', 'rates', $si, $ri);
                    continue;
                }
                if (!in_array($meal, Sheet::MEAL_PLANS, true)) {
                    $this->error('E07', "{$label}, {$room}: meal plan '{$meal}' is not one of RO, BB, HB, FB, AI." . ($c['default_meal_plan'] ? '' : ' If the sheet gives no meal plan, set the plan\'s default meal plan.'), 'rates', $si, $ri);
                    continue;
                }
                $lower = mb_strtolower($room);
                $info = $roomInfo($lower);
                if ($known !== null && $info === null && empty($row['room_type_id'])) {
                    $this->warning('W08', "{$label}: room type '{$room}' does not exist for this hotel yet and will be created. Check the spelling against the hotel's room list.", 'rates', $si, $ri);
                }
                if ($info && ($info['priced_as'] ?? 'base') === 'supplement') {
                    $this->warning('W14', "{$label}: '{$room}' is set up as base + supplement but has its own rate row here. The row wins for this season; if that is not intended, remove it and keep the room supplement.", 'rates', $si, $ri);
                }
                $key = "{$lower}|{$meal}";
                if (isset($seenRows[$key])) {
                    $this->error('E05', "{$label}: {$room} {$meal} appears twice.", 'rates', $si, $ri);
                }
                $seenRows[$key] = true;

                $vals = ['sgl' => $row['sgl'], 'dbl' => $row['dbl'], 'tpl' => $row['tpl'], 'quad' => $row['quad'], 'unit' => $row['unit']];
                $anyValue = false;
                foreach ($vals as $occ => $v) {
                    if ($v === null) {
                        continue;
                    }
                    if ($v < 0) {
                        $this->error('E09', "{$label}, {$room} {$meal}: {$occ} is negative.", 'rates', $si, $ri);
                    } elseif ($v == 0.0) {
                        $this->error('E09', "{$label}, {$room} {$meal}: {$occ} is 0. Leave the cell blank when the hotel does not offer it.", 'rates', $si, $ri);
                        $vals[$occ] = null;
                        continue;
                    }
                    $anyValue = true;
                    if ($v < $tiny) {
                        $this->warning('W05', "{$label}, {$room} {$meal}: {$occ} = {$v} looks too small (below {$tiny}).", 'rates', $si, $ri);
                    } elseif ($v > $huge) {
                        $this->warning('W05', "{$label}, {$room} {$meal}: {$occ} = {$v} looks too large (above {$huge}).", 'rates', $si, $ri);
                    }
                }
                foreach (['child_with_bed', 'child_no_bed'] as $ck) {
                    if ($row[$ck] !== null && $row[$ck] < 0) {
                        $this->error('E09', "{$label}, {$room} {$meal}: {$ck} is negative.", 'rates', $si, $ri);
                    }
                }
                if (!$anyValue) {
                    $this->error('E08', "{$label}, {$room} {$meal}: no rate in any column. Remove the row or fill it in.", 'rates', $si, $ri);
                    continue;
                }
                if ($mode === 'per_unit') {
                    if ($vals['unit'] === null) {
                        $this->error('E18', "{$label}, {$room} {$meal}: the plan is priced per unit but the UNIT column is empty.", 'rates', $si, $ri);
                    } else {
                        $anyPrimary = true;
                    }
                } else {
                    if ($vals['dbl'] !== null) {
                        $anyPrimary = true;
                    }
                    if ($vals['unit'] !== null) {
                        $this->warning('W19', "{$label}, {$room} {$meal}: a UNIT price is given on a per-room / per-person plan; it will be ignored unless the plan is set to per unit.", 'rates', $si, $ri);
                    }
                }
                // occupancy order
                $s1 = $vals['sgl']; $d2 = $vals['dbl']; $t3 = $vals['tpl']; $q4 = $vals['quad'];
                if ($mode === 'per_room') {
                    if ($s1 !== null && $d2 !== null && $s1 > $d2) {
                        $this->warning('W04', "{$label}, {$room} {$meal}: single ({$s1}) is higher than double ({$d2}) on a per-room plan.", 'rates', $si, $ri);
                    }
                    if ($d2 !== null && $t3 !== null && $d2 > $t3) {
                        $this->warning('W04', "{$label}, {$room} {$meal}: double ({$d2}) is higher than triple ({$t3}). Typo? (Blue Water once printed 35 for 135.)", 'rates', $si, $ri);
                    }
                    if ($t3 !== null && $q4 !== null && $t3 > $q4) {
                        $this->warning('W04', "{$label}, {$room} {$meal}: triple ({$t3}) is higher than quad ({$q4}).", 'rates', $si, $ri);
                    }
                } elseif ($mode === 'per_person') {
                    if ($s1 !== null && $d2 !== null && $s1 < $d2) {
                        $this->warning('W04', "{$label}, {$room} {$meal}: single ({$s1}) is lower than per-person sharing ({$d2}) on a per-person plan — check the pricing mode.", 'rates', $si, $ri);
                    }
                }
                // change versus previous approved plan
                $prevKey = $lower . '|' . $meal;
                if (isset($previous[$prevKey]) && ($c['applies_to'] ?? 'both') !== 'payment') { // an ad hoc payment rate is meant to differ from the contract
                    foreach (['sgl', 'dbl', 'tpl'] as $occ) {
                        $old = Sheet::num($previous[$prevKey][$occ] ?? null);
                        $new = $vals[$occ];
                        if ($old && $new) {
                            $pct = abs($new - $old) / $old * 100.0;
                            if ($pct > $changePct) {
                                $this->warning('W09', sprintf('%s, %s %s: %s changed from %.2f to %.2f (%.0f%%) against the latest approved plan.', $label, $room, $meal, $occ, $old, $new, $pct), 'rates', $si, $ri);
                            }
                        }
                    }
                }
                $roomsInSeason[$lower][$meal] = $vals;
                $baseRoomsWithRates[$lower] = true;
            }
            if (!$anyPrimary) {
                $this->error('E08', "{$label}: no double-occupancy rate on any room type" . ($mode === 'per_unit' ? ' (unit price)' : '') . '. Every season needs at least one.', 'seasons', $si);
            }
            foreach ($roomsInSeason as $roomKey => $byMeal) {
                foreach (['sgl', 'dbl', 'tpl'] as $occ) {
                    $prevMeal = null; $prevVal = null;
                    foreach (Sheet::MEAL_ORDER as $meal => $order) {
                        if ($meal === 'AI' || !isset($byMeal[$meal]) || $byMeal[$meal][$occ] === null) {
                            continue;
                        }
                        $v = $byMeal[$meal][$occ];
                        if ($prevVal !== null && $v < $prevVal) {
                            $this->warning('W03', "{$label}, {$roomKey}: {$occ} {$meal} ({$v}) is lower than {$prevMeal} ({$prevVal}). Meal plans normally go RO ≤ BB ≤ HB ≤ FB.", 'rates', $si);
                        }
                        $prevMeal = $meal; $prevVal = $v;
                    }
                }
                foreach ($validRanges as [$rf, $rt]) {
                    $coverByRoom[$roomKey][] = [$rf, $rt, $si];
                }
            }
        }

        // ---- overlaps and gaps per room type across seasons --------------------
        foreach ($coverByRoom as $roomKey => $ranges) {
            usort($ranges, static fn ($a, $b) => strcmp($a[0], $b[0]));
            for ($i = 0; $i < count($ranges); $i++) {
                for ($j = $i + 1; $j < count($ranges); $j++) {
                    if ($ranges[$i][2] !== $ranges[$j][2] && Dates::overlaps($ranges[$i][0], $ranges[$i][1], $ranges[$j][0], $ranges[$j][1])) {
                        $this->error('E05', sprintf('%s: season %d (%s to %s) and season %d (%s to %s) overlap. Each night can carry only one rate per room type inside a plan.', $roomKey, $ranges[$i][2] + 1, $ranges[$i][0], $ranges[$i][1], $ranges[$j][2] + 1, $ranges[$j][0], $ranges[$j][1]), 'seasons', $ranges[$j][2]);
                    }
                }
            }
            if ($vFrom && $vTo && $vFrom <= $vTo) {
                foreach (Dates::gaps(array_map(static fn ($r) => [$r[0], $r[1]], $ranges), $vFrom, $vTo) as [$gf, $gt]) {
                    $this->warning('W01', "{$roomKey}: no rate from {$gf} to {$gt} although the plan is valid then." . ($c['plan_type'] === 'contract' ? ' Ask the hotel, or shorten the validity.' : ' For an offer that is normal — the base contract fills the gap.'), 'seasons');
                }
            }
        }
        // supplement-priced rooms need a base with rates somewhere in the plan
        foreach ($sheet['room_supplements'] as $ri => $rs) {
            if ($rs['room_type'] === '') {
                $this->error('E16', 'Room supplement ' . ($ri + 1) . ': room type is missing.', 'room_supplements', null, $ri);
                continue;
            }
            if ($rs['amount'] === null) {
                $this->error('E16', "Room supplement for '{$rs['room_type']}': amount is missing (negative amounts are allowed for cheaper categories).", 'room_supplements', null, $ri);
            }
            $info = $roomInfo(mb_strtolower($rs['room_type']));
            $base = $info['base_room_type'] ?? ($info['base_room_type_name'] ?? null);
            if ($base && !isset($baseRoomsWithRates[mb_strtolower((string) $base)])) {
                $this->warning('W14', "Room supplement for '{$rs['room_type']}': its base room '{$base}' has no rate row in this plan, so the supplement cannot be applied here (fine for an addendum whose parent prices the base).", 'room_supplements', null, $ri);
            }
            if ($rs['season'] !== null && !isset($seasonNames[mb_strtolower($rs['season'])]) && !is_numeric($rs['season'])) {
                $this->error('E16', "Room supplement for '{$rs['room_type']}': season '{$rs['season']}' does not exist in this plan.", 'room_supplements', null, $ri);
            }
            $supplementRoomsUsed[mb_strtolower($rs['room_type'])] = true;
        }

        // ---- meal deltas -----------------------------------------------------
        $deltaSeen = [];
        foreach ($sheet['meal_deltas'] as $di => $d) {
            if (!$d['from'] || !$d['to']) {
                $this->error('E13', 'Meal delta ' . ($di + 1) . ': "from" and "to" meal plans are required (e.g. from HB to BB = −5).', 'meal_deltas', null, $di);
                continue;
            }
            if ($d['from'] === $d['to']) {
                $this->error('E13', 'Meal delta ' . ($di + 1) . ": from and to are both {$d['from']}.", 'meal_deltas', null, $di);
            }
            if ($d['adult'] === null) {
                $this->error('E13', 'Meal delta ' . ($di + 1) . " ({$d['from']} → {$d['to']}): adult amount is missing. Reductions are negative (BB reduction on HB = −5), supplements positive.", 'meal_deltas', null, $di);
            } else {
                $expectSign = Sheet::MEAL_ORDER[$d['to']] <=> Sheet::MEAL_ORDER[$d['from']];
                if ($expectSign > 0 && $d['adult'] < 0) {
                    $this->warning('W03', 'Meal delta ' . ($di + 1) . ": going from {$d['from']} up to {$d['to']} normally adds money, but the amount is negative ({$d['adult']}).", 'meal_deltas', null, $di);
                } elseif ($expectSign < 0 && $d['adult'] > 0) {
                    $this->warning('W03', 'Meal delta ' . ($di + 1) . ": going from {$d['from']} down to {$d['to']} normally reduces the rate, but the amount is positive ({$d['adult']}). Enter reductions as negative numbers.", 'meal_deltas', null, $di);
                }
            }
            $k = $d['from'] . '>' . $d['to'] . '|' . mb_strtolower((string) $d['season']);
            if (isset($deltaSeen[$k])) {
                $this->error('E13', 'Meal delta ' . ($di + 1) . ": {$d['from']} → {$d['to']} is defined twice for the same season.", 'meal_deltas', null, $di);
            }
            $deltaSeen[$k] = true;
            if ($d['season'] !== null && !isset($seasonNames[mb_strtolower($d['season'])]) && !is_numeric($d['season'])) {
                $this->error('E13', 'Meal delta ' . ($di + 1) . ": season '{$d['season']}' does not exist in this plan.", 'meal_deltas', null, $di);
            }
        }
        foreach ($sheet['meal_prices'] as $mi => $m) {
            if ($m['adult'] === null || $m['adult'] < 0) {
                $this->error('E13', 'Extra meal ' . ($mi + 1) . " ({$m['meal']}): adult price is missing or negative.", 'meal_prices', null, $mi);
            }
        }

        // ---- expiry --------------------------------------------------------
        if ($vTo && $vTo >= $today && Dates::diffDays($today, $vTo) <= $expiryDays) {
            $this->warning('W02', "Validity ends on {$vTo}, within {$expiryDays} days. Ask the hotel for the next rate sheet.", 'header');
        }
        if ($vTo && $vTo < $today) {
            $this->warning('W02', "This plan expired on {$vTo}; it will only serve historical quotations.", 'header');
        }

        // ---- supplements -----------------------------------------------------
        $galaCovered = [];
        foreach ($sheet['supplements'] as $si => $sup) {
            $label = 'Supplement ' . ($si + 1) . ($sup['name'] !== '' ? " ({$sup['name']})" : '');
            $hasAmount = $sup['amount'] !== null || $sup['amount_sgl'] !== null || $sup['amount_dbl'] !== null || $sup['amount_tpl'] !== null || $sup['pct_of_rate'] !== null;
            if (!$hasAmount) {
                $this->error('E10', "{$label}: give an amount, occupancy amounts (SGL/DBL/TPL) or a % of the room rate.", 'supplements', null, $si);
            }
            foreach (['amount', 'amount_sgl', 'amount_dbl', 'amount_tpl', 'child_amount'] as $k) {
                if ($sup[$k] !== null && $sup[$k] < 0) {
                    $this->error('E10', "{$label}: {$k} is negative.", 'supplements', null, $si);
                }
            }
            if ($sup['name'] === '') {
                $this->error('E10', "{$label}: name is missing (e.g. Christmas Eve Gala Dinner, Perahera supplement).", 'supplements', null, $si);
            }
            $sf = $sup['date_from']; $st = $sup['date_to'];
            if (($sf && !$st) || (!$sf && $st)) {
                $this->error('E10', "{$label}: give both dates or neither.", 'supplements', null, $si);
            }
            if ($sf && $st && $sf > $st) {
                $this->error('E10', "{$label}: dates are reversed ({$sf} to {$st}).", 'supplements', null, $si);
            }
            if ($sf && $st && $vFrom && $vTo && ($sf < $vFrom || $st > $vTo)) {
                $this->error('E10', "{$label}: {$sf} to {$st} is outside the plan validity {$vFrom} to {$vTo}. Sheets often keep last year's dates — check the year.", 'supplements', null, $si);
            }
            if ($sup['season'] !== null && !isset($seasonNames[mb_strtolower($sup['season'])]) && !is_numeric($sup['season'])) {
                $this->error('E10', "{$label}: season '{$sup['season']}' does not exist in this plan.", 'supplements', null, $si);
            }
            if (in_array($sup['type'], ['gala', 'peak', 'festival'], true) && !$sf && !$st && $sup['season'] === null) {
                $this->warning('W06', "{$label}: a {$sup['type']} supplement without dates or a season applies to every night of the plan. Add the dates (24 Dec, 31 Dec, Perahera week…).", 'supplements', null, $si);
            }
            if ($sup['type'] === 'gala' && $sf && $st) {
                foreach (Dates::galaNights($sf, $st, $galaDates) as $g) {
                    $galaCovered[$g] = true;
                }
                if ($sup['child_amount'] === null && $sup['child_pct'] === null) {
                    $this->warning('W07', "{$label}: no child amount or child %. Most hotels charge children " . rtrim(rtrim(number_format($galaChildPct, 2, '.', ''), '0'), '.') . "% for gala dinners — check the sheet.", 'supplements', null, $si);
                }
            }
        }
        // (a payment-only ad hoc rate without galas takes them from the contract plan when the hotel is paid)
        if ($vFrom && $vTo && $vFrom <= $vTo && $c['plan_type'] !== 'addendum' && ($c['applies_to'] ?? 'both') !== 'payment' && $galaDates) {
            foreach (Dates::galaNights($vFrom, $vTo, $galaDates) as $g) {
                if (!isset($galaCovered[$g])) {
                    $this->warning('W06', "No gala supplement covers {$g}. Almost every Sri Lankan contract charges one on these nights (" . implode(', ', $galaDates) . " — see Settings); check the sheet." . (in_array($c['plan_type'], ['tactical', 'special_offer', 'early_bird'], true) ? ' (Offers usually say "all other supplements as per contract" — then attach the base contract as parent instead.)' : ''), 'supplements');
                }
            }
        }

        // ---- children ----------------------------------------------------------
        foreach ($sheet['child_rates'] as $ci => $ch) {
            $label = 'Child rule ' . ($ci + 1);
            if ($ch['age_to'] === null) {
                $this->error('E14', "{$label}: the upper age is missing (e.g. 5.99 or 11.99).", 'child_rates', null, $ci);
                continue;
            }
            if ($ch['age_from'] > $ch['age_to']) {
                $this->error('E14', "{$label}: age from ({$ch['age_from']}) is above age to ({$ch['age_to']}).", 'child_rates', null, $ci);
            }
            if ($ch['age_to'] > $childMaxAge) {
                $this->warning('W07', "{$label}: age to {$ch['age_to']} is above {$childMaxAge} — children are normally up to 11.99 or 12 (limit set under Settings).", 'child_rates', null, $ci);
            }
            if ($ch['amount'] === null && $ch['pct'] === null) {
                $this->error('E14', "{$label}: give an amount (0 = free of charge) or a % with its basis (50% of the meal rate, of the adult rate…).", 'child_rates', null, $ci);
            }
            if ($ch['pct'] !== null && $ch['pct_basis'] === null) {
                $this->error('E14', "{$label}: a % needs a basis (adult rate / meal rate / meal supplement / room rate).", 'child_rates', null, $ci);
            }
            if ($ch['amount'] !== null && $ch['amount'] < 0) {
                $this->error('E14', "{$label}: amount is negative.", 'child_rates', null, $ci);
            }
        }
        if ($sheet['child_rates']) {
            // band coverage 0..12
            $bands = array_map(static fn ($ch) => [(float) $ch['age_from'], (float) ($ch['age_to'] ?? 0)], array_filter($sheet['child_rates'], static fn ($ch) => $ch['age_to'] !== null));
            usort($bands, static fn ($a, $b) => $a[0] <=> $b[0]);
            $cursor = 0.0;
            foreach ($bands as [$from, $to]) {
                if ($from > $cursor + 1.0) {
                    $this->warning('W07', sprintf('Child rules leave ages %.2f to %.2f undefined. Add a rule (often "free of charge") for that band.', $cursor, $from), 'child_rates');
                }
                $cursor = max($cursor, $to);
            }
        } elseif ($c['child_policy_text'] === null && $c['plan_type'] === 'contract') {
            $this->warning('W07', 'No child rules and no child policy text. Japanese agents ask for child-with-bed and child-without-bed prices — enter the hotel\'s child policy.', 'child_rates');
        }

        // ---- guide / FOC / offers -----------------------------------------------
        foreach ($sheet['guide_policies'] as $gi => $g) {
            if ($g['amount'] === null && $g['pct'] === null && $g['free_from_pax'] === null) {
                $this->warning('W18', 'Guide policy ' . ($gi + 1) . ': no amount, % or "free from N pax" — it carries no information.', 'guide_policies', null, $gi);
            }
            if ($g['pct'] !== null && $g['pct_of'] === null) {
                $this->error('E10', 'Guide policy ' . ($gi + 1) . ': a % needs a basis (single rate / double rate / room rate).', 'guide_policies', null, $gi);
            }
        }
        foreach ($sheet['foc_rules'] as $fi => $f) {
            if (!$f['every_n'] || $f['every_n'] < 2) {
                $this->error('E10', 'FOC rule ' . ($fi + 1) . ': "every N paying" must be 2 or more (e.g. every 15 paying pax, the 16th is free).', 'foc_rules', null, $fi);
            }
        }
        foreach ($sheet['offers'] as $oi => $o) {
            if ($o['name'] === '') {
                $this->error('E10', 'Offer ' . ($oi + 1) . ': name is missing.', 'offers', null, $oi);
            }
            if ($o['book_from'] && $o['book_to'] && $o['book_from'] > $o['book_to']) {
                $this->error('E11', 'Offer ' . ($oi + 1) . ': booking window is reversed.', 'offers', null, $oi);
            }
            if ($o['stay_from'] && $o['stay_to'] && $o['stay_from'] > $o['stay_to']) {
                $this->error('E11', 'Offer ' . ($oi + 1) . ': stay dates are reversed.', 'offers', null, $oi);
            }
        }

        // ---- cancellation & payment --------------------------------------------
        $groups = [];
        foreach ($sheet['cancellation_rules'] as $ci => $r) {
            $label = 'Cancellation rule ' . ($ci + 1);
            if ($r['charge_pct'] === null && $r['charge_nights'] === null) {
                $this->error('E15', "{$label}: give the charge as a % of the stay or a number of nights (0% = free cancellation).", 'cancellation_rules', null, $ci);
            }
            if ($r['charge_pct'] !== null && ($r['charge_pct'] < 0 || $r['charge_pct'] > 100)) {
                $this->error('E15', "{$label}: charge % must be between 0 and 100.", 'cancellation_rules', null, $ci);
            }
            if ($r['days_from'] !== null && $r['days_from'] < $r['days_to']) {
                $this->error('E15', "{$label}: the band is reversed — 'days from' ({$r['days_from']}) must be the larger number (e.g. from 30 to 15 days before arrival).", 'cancellation_rules', null, $ci);
            }
            if ($r['date_from'] && $r['date_to'] && $r['date_from'] > $r['date_to']) {
                $this->error('E15', "{$label}: stay dates are reversed.", 'cancellation_rules', null, $ci);
            }
            $gk = ($r['date_from'] ?? '') . '|' . ($r['date_to'] ?? '') . '|' . $r['tier'] . '|' . ($r['min_rooms'] ?? '') . '-' . ($r['max_rooms'] ?? '') . '|' . ($r['min_pax'] ?? '') . '-' . ($r['max_pax'] ?? '');
            $groups[$gk][] = $r;
        }
        foreach ($groups as $gk => $list) {
            usort($list, static fn ($a, $b) => ($b['days_from'] ?? 9999) <=> ($a['days_from'] ?? 9999));
            $prevPct = null; $prevTo = null;
            foreach ($list as $r) {
                if ($prevTo !== null && $r['days_from'] !== null && $r['days_from'] >= $prevTo && $prevTo > 0) {
                    // touching bands are fine (30-15, 14-0); real overlap when from >= previous to and previous to > from
                }
                if ($prevPct !== null && $r['charge_pct'] !== null && $r['charge_pct'] < $prevPct) {
                    $this->warning('W11', sprintf('Cancellation: the band %s–%s days charges %s%% but an earlier band charges %s%% — charges normally rise as arrival gets closer. This is what the hotel wrote (Tamarind Tree does this); confirm it.', $r['days_from'] ?? '∞', $r['days_to'], $r['charge_pct'], $prevPct), 'cancellation_rules');
                }
                if ($r['charge_pct'] !== null) {
                    $prevPct = $r['charge_pct'];
                }
                $prevTo = $r['days_to'];
            }
        }
        foreach ($sheet['payment_rules'] as $pi => $p) {
            if ($p['pct'] !== null && ($p['pct'] < 0 || $p['pct'] > 100)) {
                $this->error('E15', 'Payment rule ' . ($pi + 1) . ': % must be between 0 and 100.', 'payment_rules', null, $pi);
            }
        }

        // ---- restrictions ----------------------------------------------------
        foreach ($sheet['restrictions'] as $ri => $r) {
            if (!$r['date_from'] || !$r['date_to']) {
                $this->error('E10', 'Restriction ' . ($ri + 1) . ': dates are required.', 'restrictions', null, $ri);
                continue;
            }
            if ($r['date_from'] > $r['date_to']) {
                $this->error('E10', 'Restriction ' . ($ri + 1) . ': dates are reversed.', 'restrictions', null, $ri);
            }
            if ($vFrom && $vTo && ($r['date_from'] < $vFrom || $r['date_to'] > $vTo)) {
                $this->warning('W12', 'Restriction ' . ($ri + 1) . " ({$r['kind']}): {$r['date_from']} to {$r['date_to']} is outside the plan validity.", 'restrictions', null, $ri);
            }
            if ($r['kind'] === 'min_stay' && (!$r['value'] || !is_numeric($r['value']))) {
                $this->error('E10', 'Restriction ' . ($ri + 1) . ': minimum stay needs the number of nights in "value".', 'restrictions', null, $ri);
            }
            if ($r['kind'] === 'meal_plan_required' && Sheet::mealList($r['value']) === null) {
                $this->error('E10', 'Restriction ' . ($ri + 1) . ': "meal plan required" needs the allowed plans in "value" (e.g. HB,FB,AI).', 'restrictions', null, $ri);
            }
        }
        if ($c['min_stay'] !== null && $c['min_stay'] > 7) {
            $this->warning('W17', "Minimum stay of {$c['min_stay']} nights on the whole plan — correct for Ayurveda packages, unusual otherwise.", 'header');
        }

        // ---- overlap with other approved plans of the same hotel ----------------
        foreach ($existing as $ex) {
            $sameKind = ($ex['plan_type'] ?? 'contract') === $c['plan_type'] && ($ex['tier'] ?? 'all') === $c['tier'] && ($ex['applies_to'] ?? 'both') === ($c['applies_to'] ?? 'both');
            if (!$sameKind) {
                continue;
            }
            $exMarkets = $ex['markets'] ?? [];
            $myMarkets = array_column(array_filter($c['markets'], static fn ($m) => $m['mode'] === 'include'), 'code');
            $marketsTouch = !$exMarkets || !$myMarkets || array_intersect($exMarkets, $myMarkets);
            if (!$marketsTouch) {
                continue;
            }
            $roomsTouch = empty($ex['room_types']) || empty($coverByRoom) || array_intersect(array_map('mb_strtolower', $ex['room_types']), array_keys($coverByRoom));
            if (!$roomsTouch) {
                continue;
            }
            foreach ($ex['ranges'] ?? [] as [$ef, $et]) {
                foreach ($rangesAll as [$rf, $rt, $si]) {
                    if (Dates::overlaps($rf, $rt, $ef, $et)) {
                        $this->warning('W10', sprintf("%s to %s overlaps the approved %s '%s' (%s to %s) for the same tier and markets. The newer / higher-priority plan wins; if this sheet replaces it, mark the old one as superseded.", $rf, $rt, $ex['plan_type'] ?? 'plan', $ex['contract_name'] ?? ('#' . $ex['contract_id']), $ef, $et), 'seasons', $si);
                        continue 3;
                    }
                }
            }
        }

        return $this->issues;
    }

    /** @return array{errors:int, warnings:int} */
    public static function count(array $issues): array
    {
        $e = 0; $w = 0;
        foreach ($issues as $i) {
            if ($i['level'] === 'error') { $e++; } else { $w++; }
        }
        return ['errors' => $e, 'warnings' => $w];
    }

    public static function hasErrors(array $issues): bool
    {
        foreach ($issues as $i) {
            if ($i['level'] === 'error') {
                return true;
            }
        }
        return false;
    }

    /** Kept for callers of the old API. */
    public static function num($v): ?float
    {
        return Sheet::num($v);
    }

    private function error(string $code, string $message, string $section = 'header', ?int $season = null, ?int $row = null): void
    {
        $this->issues[] = ['level' => 'error', 'code' => $code, 'message' => $message, 'section' => $section, 'season' => $season, 'row' => $row];
    }

    private function warning(string $code, string $message, string $section = 'header', ?int $season = null, ?int $row = null): void
    {
        $this->issues[] = ['level' => 'warning', 'code' => $code, 'message' => $message, 'section' => $section, 'season' => $season, 'row' => $row];
    }
}
