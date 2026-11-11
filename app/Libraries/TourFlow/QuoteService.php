<?php

namespace App\Libraries\TourFlow;

/**
 * The quotation: what the tour costs, line by line, and what the client is asked for.
 *
 * Every figure here is read from somewhere else — the published rate copied beside the
 * night, the kilometres on the legs, the rate per kilometre copied onto the vehicle
 * line, the bata on the crew line, the amount on the service, the excursion rate copied
 * from its master — and multiplied. Nothing is typed into a quotation and nothing is
 * assumed: a figure that is not there is a blocker, said in words, and "Go for
 * Quotation" refuses until it is fixed. A night is never costed at nought because the
 * rate was missing; a leg is never costed at nought because nobody had driven it.
 *
 * The client's price is the cost plus a markup that only Management sets and only
 * Management sees. The costing is frozen into a numbered version the moment it passes,
 * so a quotation that went out keeps saying what it said, whatever moves afterwards.
 *
 * Beside it, the pax matrix (schema 31, PaxMatrix): every party size from one to ten costed
 * on its own from the masters, the inquiry's own size checked against the sections, and for
 * the agent only the price per person by size.
 */
final class QuoteService
{
    private $db;
    private string $user;
    private IntakeService $take;

    public function __construct($db, string $user, IntakeService $take)
    {
        $this->db   = $db;
        $this->user = $user;
        $this->take = $take;
    }

    // =====================================================================
    //  Pricing: the markup, management only
    // =====================================================================

    /** @return array{markup_default_pct:?float,quote_currency:string,quote_validity_days:int,quote_terms:string} */
    public function pricing(): array
    {
        $m = [];
        foreach ($this->db->query("SELECT k, v FROM tf_meta WHERE k IN ('markup_default_pct','quote_currency','quote_validity_days','quote_terms')")->getResultArray() as $r) {
            $m[$r['k']] = (string) $r['v'];
        }
        $pct = trim($m['markup_default_pct'] ?? '');
        return [
            'markup_default_pct'  => ($pct !== '' && is_numeric($pct)) ? (float) $pct : null,
            'quote_currency'      => strtoupper(trim($m['quote_currency'] ?? 'USD')) ?: 'USD',
            'quote_validity_days' => max(1, (int) ($m['quote_validity_days'] ?? 14)),
            'quote_terms'         => (string) ($m['quote_terms'] ?? ''),
        ];
    }

    /** The company-wide settings. The caller has already checked the person holds tour/pricing. */
    public function savePricing(array $s): array
    {
        if (array_key_exists('markup_default_pct', $s)) {
            $v = trim((string) $s['markup_default_pct']);
            if ($v !== '' && (!is_numeric($v) || (float) $v < 0 || (float) $v > 500)) {
                throw new \InvalidArgumentException('The default markup is a percentage between 0 and 500, or blank for none.');
            }
            $this->put('markup_default_pct', $v === '' ? '' : (string) round((float) $v, 2));
        }
        if (array_key_exists('quote_validity_days', $s)) {
            $d = (int) $s['quote_validity_days'];
            if ($d < 1 || $d > 365) {
                throw new \InvalidArgumentException('A quotation is valid for between 1 and 365 days.');
            }
            $this->put('quote_validity_days', (string) $d);
        }
        if (array_key_exists('quote_terms', $s)) {
            $this->put('quote_terms', mb_substr(trim((string) $s['quote_terms']), 0, 4000));
        }
        if (array_key_exists('quote_currency', $s)) {
            $c = strtoupper(trim((string) $s['quote_currency']));
            if ($c !== 'USD') {
                throw new \InvalidArgumentException('Quotations go out in US dollars; the currency is not changed here.');
            }
        }
        return $this->pricing();
    }

    /** The markup for one inquiry, or back to the default (null). Management only; the caller checks. */
    public function setMarkup(int $intakeId, $pct, ?string $note = null): array
    {
        $this->take->assertOpen($intakeId);
        if ($pct === null || $pct === '') {
            $this->db->query('UPDATE tf_intake SET markup_pct = NULL, markup_by = ?, markup_at = ? WHERE id = ?', [$this->user, date('Y-m-d H:i:s'), $intakeId]);
        } else {
            if (!is_numeric($pct) || (float) $pct < 0 || (float) $pct > 500) {
                throw new \InvalidArgumentException('The markup is a percentage between 0 and 500.');
            }
            $this->db->query('UPDATE tf_intake SET markup_pct = ?, markup_by = ?, markup_at = ? WHERE id = ?', [round((float) $pct, 2), $this->user, date('Y-m-d H:i:s'), $intakeId]);
        }
        // tf-walkfix-r1 (schema 35): the note stays with the markup, Management's own — never the inquiry's general
        // note, which the desks' cards show as "Notes"
        if ($note !== null && $this->hasMarkupNote()) {
            $this->db->query('UPDATE tf_intake SET markup_note = ? WHERE id = ?', [trim($note) === '' ? null : mb_substr(trim($note), 0, 500), $intakeId]);
        }
        return $this->costing($intakeId);
    }

    private function put(string $k, string $v): void
    {
        $this->db->query('INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
    }

    // =====================================================================
    //  The costing
    // =====================================================================

    /**
     * Everything the tour costs, from the inquiry as it stands now, with every figure's
     * origin beside it and every hole named. Read-only: nothing is written.
     *
     * @return array<string, mixed>
     */
    public function costing(int $intakeId): array
    {
        $it = $this->take->get($intakeId);
        if (!$it) {
            throw new \RuntimeException('No such inquiry.');
        }
        $blockers = [];
        $warnings = [];
        $pax      = (int) ($it['pax_total'] ?? 0);
        // schema 32: the dates decide the nights; the column is kept in step with them
        $tourDates = $it['tour_dates'] ?? TourDates::of($it);
        $nights    = (int) ($tourDates['nights'] ?? ($it['nights'] ?? 0));

        // the exchange rate: rupees per dollar on the arrival date (or the latest on file)
        $usd = $this->take->services()->usdRate($it['arrival_date'] ?? null);
        $rate = $usd['rate'] ?? null;
        // tf-stage4: the USD rate is kept with the inquiry from its first costing. A later daily rate is a change shown
        // beside it (old and new) and applied only by Recalculate — never by opening or refreshing.
        $usdNow = $usd;
        if (CostingGuard::installed($this->db)) {
            $kept = $it['usd_rate_kept'] ?? null;
            if (($kept === null || $kept === '') && $rate !== null && $rate > 0) {
                $this->db->query('UPDATE tf_intake SET usd_rate_kept = ?, usd_rate_kept_date = ?, usd_rate_kept_at = ? WHERE id = ? AND usd_rate_kept IS NULL', [$rate, $usd['rate_date'] ?? null, date('Y-m-d H:i:s'), $intakeId]);
                $kept = $rate;
                $it['usd_rate_kept'] = $rate;
                $it['usd_rate_kept_date'] = $usd['rate_date'] ?? null;
            }
            if ($kept !== null && $kept !== '' && (float) $kept > 0) {
                $rate = (float) $kept;
                $usd  = ['rate' => $rate, 'rate_date' => $it['usd_rate_kept_date'] ?? ($usd['rate_date'] ?? null)];
            }
        }
        if ($rate === null || $rate <= 0) {
            $blockers[] = ['code' => 'no_usd_rate', 'label' => 'No USD exchange rate is on file, so rupee costs (transport, crew) cannot be shown in dollars. Enter today\'s rate under Daily USD Rate.'];
        }
        $toUsd = static fn (?float $lkr) => ($lkr === null || $rate === null || $rate <= 0) ? null : round($lkr / $rate, 2);
        $toLkr = static fn (?float $u) => ($u === null || $rate === null || $rate <= 0) ? null : round($u * $rate, 2);

        if ($pax <= 0) {
            $blockers[] = ['code' => 'pax', 'label' => 'The number of passengers is not set.'];
        }
        // tf-stage8: a blocked record on the inquiry — "no new work" / "suspended" stop the quotation, "payments held" warns
        foreach (Blocks::forInquiry($this->db, $it) as $bi) {
            $line = $bi['where'] . ' — ' . Blocks::words($bi['block']);
            if ($bi['stops']) {
                $blockers[] = ['code' => 'blocked_' . $bi['block']['id'] . '_' . md5($bi['where']), 'label' => 'Blocked record on this inquiry: ' . $line . ' Change the record or wait until the Super Admin lifts the block.'];
            } else {
                $warnings[] = ['code' => 'blocked_pay_' . $bi['block']['id'] . '_' . md5($bi['where']), 'label' => 'Payments held: ' . $line . ' The quotation may go; no payment to this record is made while the hold stands.'];
            }
        }
        if (empty($it['arrival_date'])) {
            $blockers[] = ['code' => 'arrival', 'label' => 'The arrival date is not set.'];
        }
        if (($it['days'] ?? []) === []) {
            $blockers[] = ['code' => 'no_nights', 'label' => 'The itinerary has no nights on it.'];
        }
        // ---- the dates rule (schema 32): the dates decide the nights, and the departure day is no hotel night ----
        foreach (($it['departure_rows'] ?? []) as $r) {
            $n    = (int) $r['day_no'];
            $when = !empty($r['day_date']) ? ' (' . date('j M Y', strtotime((string) $r['day_date'])) . ')' : '';
            $what = trim((string) ($r['hotel_name_raw'] ?? '')) !== '' ? ', ' . trim((string) $r['hotel_name_raw']) : (trim((string) ($r['area'] ?? '')) !== '' ? ', ' . trim((string) $r['area']) : '');
            $blockers[] = ['code' => 'departure_row_' . $r['id'], 'label' => "Night {$n}{$when}{$what} falls on the departure day: the dates give {$nights} night(s), so it is not costed as a hotel night. Press Make it the departure day, or It's an extra night if the party really stays that night."];
        }
        if (($it['missing_nights'] ?? []) !== []) {
            $m = $it['missing_nights'];
            $blockers[] = ['code' => 'nights_missing', 'label' => 'The dates give ' . $nights . ' night(s), but night' . (count($m) === 1 ? ' ' : 's ') . TourDates::runs($m) . (count($m) === 1 ? ' has' : ' have') . ' no line, so nowhere to sleep is costed for ' . (count($m) === 1 ? 'it' : 'them') . '. Press Add the missing nights and name the hotels.'];
        }
        $dc = $it['dates_check'] ?? null;
        if ($dc && $dc['differs']) {
            if ($dc['confirmed']) {
                $warnings[] = ['code' => 'dates_kept', 'label' => 'The email says ' . $dc['email_line'] . '. These dates were confirmed by ' . ($dc['confirmed_by'] ?: 'a colleague') . ($dc['confirmed_at'] ? ' on ' . date('j M Y', strtotime((string) $dc['confirmed_at'])) : '') . '.'];
            } elseif (($dc['settled_line'] ?? '') !== '') {
                // schema 33: one date settled by an Edit Request, the other still to confirm
                $blockers[] = ['code' => 'dates_unconfirmed', 'label' => 'The email says ' . $dc['email_line'] . ', but this inquiry has ' . $dc['line'] . '. ' . $dc['settled_line'] . ' Still to confirm on the inquiry — ' . $dc['open_line'] . ': Keep these dates, or Use the email\'s ' . (count($dc['open']) === 1 ? $dc['open'][0] : 'dates') . '.'];
            } else {
                $blockers[] = ['code' => 'dates_unconfirmed', 'label' => 'The email says ' . $dc['email_line'] . ', but this inquiry has ' . $dc['line'] . ($dc['by_hand'] ? ' (changed by hand)' : '') . '. Confirm which is right on the inquiry: Keep these dates, or Use the email\'s dates.'];
            }
        }

        // ---- hotels: one line per night, room mix × published rate --------------------
        $hotels = [];
        $hotelUsd = 0.0;
        // tf-child-r1: the Child Fare Review — every per-person item confirmed before a quotation, its figures carried in
        $childReview = null;
        if ((int) ($it['pax_children'] ?? 0) > 0 && ChildFares::ready($this->db)) {
            $childReview = (new ChildFares($this->db, $this->user))->review($it);
            if ($childReview['open'] > 0) {
                $list = array_map(static fn ($i) => $i['title'] . ' (' . ($i['status'] === 'review_again' ? 'review again' : 'needs checking') . ')', array_values(array_filter($childReview['items'], static fn ($i) => $i['status'] !== 'confirmed')));
                $blockers[] = ['code' => 'child_review', 'label' => 'Child Fare Review: ' . $childReview['open'] . ' item(s) to confirm before quotation — ' . implode('; ', array_slice($list, 0, 6)) . (count($list) > 6 ? ' …' : '') . '. Mark each Applicable or Not applicable in the Child Fare Review.'];
            }
            if (count(ChildFares::children($it)) !== (int) $it['pax_children']) {
                $blockers[] = ['code' => 'child_count', 'label' => 'The party has ' . (int) $it['pax_children'] . ' child(ren); give each one\'s age and bedding in the Child Fare Review.'];
            }
        }
        $childByDay = [];
        foreach (($childReview['items'] ?? []) as $ci) {
            if ($ci['kind'] === 'hotel' && in_array($ci['status'], ['confirmed', 'review_again'], true) && $ci['decision'] !== '') {
                for ($dn = (int) $ci['day_from']; $dn <= (int) $ci['day_to']; $dn++) {
                    $childByDay[$dn] = $ci;
                }
            }
        }
        $mixAll = $it['rooms'] ?? IntakeService::roomMix($it, null);
        if (in_array($mixAll['source'], ['none', 'groups_empty', 'nobody'], true) && ($it['days'] ?? []) !== []) {
            $blockers[] = ['code' => 'rooms_mix', 'label' => 'The room mix (singles / doubles / twins / triples) is not set, so no night can be costed.'];
        }
        foreach (($it['groups'] ?? []) as $grp) {
            $one = IntakeService::roomMix(['groups' => [$grp]], null);
            if ((int) $grp['pax'] > 0 && $one['rooms'] === 0) {
                $blockers[] = ['code' => 'group_rooms_' . $grp['id'], 'label' => $grp['name'] . ' has no room mix yet.'];
            }
        }
        foreach (($it['days'] ?? []) as $d) {
            $n     = (int) $d['day_no'];
            $hotel = trim((string) ($d['hotel_name_raw'] ?? ''));
            $line  = [
                'day_no' => $n, 'date' => $d['day_date'], 'hotel' => $hotel !== '' ? $hotel : null, 'hotel_id' => $d['hotel_id'] ?? null, 'area' => $d['area'] ?? null,
                // the room type as this booking describes it (tf-walkfix-r1), else the master's name; the rate follows the type either way
                'room_type' => trim((string) ($d['room_type_text'] ?? '')) !== '' ? trim((string) $d['room_type_text']) : ($d['room_type_name'] ?? null), 'meal_plan' => $d['meal_plan'] ?? null, 'plan' => $d['rate_plan_name'] ?? null,
                'currency' => $d['rate_currency'] ?? null, 'mix' => $d['room_mix'] ?? IntakeService::roomMix($it, $n),
                'lines' => [], 'supplements' => [], 'amount' => null, 'amount_usd' => null, 'state' => 'ok', 'why' => null,
            ];
            $mix = $line['mix'];
            if ($hotel === '') {
                $line['state'] = 'missing';
                $line['why']   = 'No hotel named.';
                $blockers[]    = ['code' => 'hotel_none_' . $d['id'], 'label' => "Night {$n}: no hotel named."];
                $hotels[] = $line;
                continue;
            }
            if (empty($d['day_date'])) {
                $line['state'] = 'missing';
                $line['why']   = 'No date for the night.';
                $blockers[]    = ['code' => 'hotel_date_' . $d['id'], 'label' => "Night {$n} ({$hotel}): no date, so no rate can be read."];
                $hotels[] = $line;
                continue;
            }
            if (($d['meal_plan'] ?? null) === null) {
                $line['state'] = 'missing';
                $line['why']   = 'Meal plan not stated.';
                $blockers[]    = ['code' => 'hotel_meal_' . $d['id'], 'label' => "Night {$n} ({$hotel}): meal plan not stated."];
            }
            $state = (string) ($d['rate_state'] ?? '');
            if (in_array($state, ['not_in_panel', 'no_supplier'], true)) {
                $line['state'] = 'missing';
                $line['why']   = $state === 'no_supplier' ? 'No supplier record.' : 'Not in the Hotel Rates panel.';
                $blockers[]    = ['code' => 'hotel_' . $d['id'], 'label' => "Published rate missing: {$hotel}, night {$n} — " . ($state === 'no_supplier' ? 'no supplier record yet.' : 'the hotel is not in the Hotel Rates panel.')];
                $hotels[] = $line;
                continue;
            }
            if ($state !== 'rate') {
                $line['state'] = 'missing';
                $line['why']   = (string) ($d['rate_note'] ?? 'No published rate covers the night.');
                $blockers[]    = ['code' => 'hotel_' . $d['id'], 'label' => "Published rate missing: {$hotel}, night {$n} — " . ($d['rate_note'] ?? 'no published rate covers the night.')];
                $hotels[] = $line;
                continue;
            }
            if (($d['room_types_json'] ?? null) === null) {
                $line['state'] = 'missing';
                $line['why']   = 'Not read against the panel since the room types were added.';
                $blockers[]    = ['code' => 'reread_' . $d['id'], 'label' => "{$hotel}, night {$n}: the room types and published rate have not been read onto this night yet — press Check hotels again."];
                $hotels[] = $line;
                continue;
            }
            if (($d['room_type_id'] ?? null) === null) {
                $types  = is_array($d['room_types'] ?? null) ? $d['room_types'] : [];
                $priced = array_filter($types, static fn ($t) => !empty($t['priced']));
                $line['state'] = 'missing';
                $line['why']   = count($priced) > 1 ? 'Room type not chosen.' : 'No room type of this hotel carries a published rate for the date.';
                $blockers[]    = ['code' => 'roomtype_' . $d['id'], 'label' => count($priced) > 1
                    ? "{$hotel}, night {$n}: choose the room type."
                    : "Published rate missing: {$hotel}, night {$n} — no room type carries a published rate for the date."];
                $hotels[] = $line;
                continue;
            }
            if ($line['state'] === 'missing') {   // meal plan missing: nothing more can be read for the night
                $hotels[] = $line;
                continue;
            }
            $cur = strtoupper((string) ($d['rate_currency'] ?? 'USD'));
            if (!in_array($cur, ['USD', 'LKR'], true)) {
                $line['state'] = 'missing';
                $line['why']   = "Rate in {$cur}, which this costing cannot convert.";
                $blockers[]    = ['code' => 'hotel_cur_' . $d['id'], 'label' => "{$hotel}, night {$n}: the published rate is in {$cur}; only USD and LKR rates can be costed."];
                $hotels[] = $line;
                continue;
            }
            $amount = 0.0;
            $unit   = $d['rate_unit'] !== null && $d['rate_unit'] !== '' ? (float) $d['rate_unit'] : null;
            if ($unit !== null && $d['rate_dbl'] === null && $d['rate_sgl'] === null) {
                // a tent or a villa priced as one unit whatever the occupancy: one unit per room in the mix
                $units = max(1, (int) $mix['rooms']);
                $line['lines'][] = ['kind' => 'unit', 'label' => 'Per unit (up to ' . (int) ($d['unit_pax_max'] ?? 0) . ')', 'qty' => $units, 'rate' => $unit, 'amount' => round($units * $unit, 2)];
                $amount += $units * $unit;
                $warnings[] = ['code' => 'unit_' . $d['id'], 'label' => "{$hotel}, night {$n} is priced per unit (up to " . (int) ($d['unit_pax_max'] ?? 0) . " each); {$units} unit(s) are costed, one per room in the mix. Check the count."];
            } else {
                $need = [
                    ['sgl', 'Single', (int) $mix['sgl'], $d['rate_sgl']],
                    ['dbl', 'Double', (int) $mix['dbl'], $d['rate_dbl']],
                    ['twn', 'Twin', (int) $mix['twn'], $d['rate_dbl']],
                    ['tpl', 'Triple', (int) $mix['tpl'], $d['rate_tpl']],
                ];
                $short = [];
                foreach ($need as [$k, $label, $qty, $r]) {
                    if ($qty <= 0) {
                        continue;
                    }
                    if ($r === null || $r === '') {
                        $short[] = strtolower($label);
                        continue;
                    }
                    $line['lines'][] = ['kind' => $k, 'label' => $label, 'qty' => $qty, 'rate' => (float) $r, 'amount' => round($qty * (float) $r, 2)];
                    $amount += $qty * (float) $r;
                }
                if ($short !== []) {
                    $line['state'] = 'missing';
                    $line['why']   = 'No ' . implode(' / ', $short) . ' rate for ' . $line['room_type'] . ' on ' . $d['meal_plan'] . '.';
                    $blockers[]    = ['code' => 'rate_' . $d['id'], 'label' => "Published rate missing: {$hotel}, night {$n} — no " . implode(' / ', $short) . " {$d['meal_plan']} rate for {$line['room_type']} on " . date('j M Y', strtotime((string) $d['day_date'])) . '. Fix it in Hotel Rates.'];
                    $hotels[] = $line;
                    continue;
                }
                if ($line['lines'] === []) {
                    $line['state'] = 'missing';
                    $line['why']   = 'No rooms in the mix for this night.';
                    $blockers[]    = ['code' => 'mix_' . $d['id'], 'label' => "Night {$n} ({$hotel}): nobody is in a room that night — the room mix for the groups present is empty."];
                    $hotels[] = $line;
                    continue;
                }
            }
            // tf-child-r1: the children's charge for the night, as the Child Fare Review confirmed it (apart from the rooms)
            if (isset($childByDay[$n])) {
                $ci = $childByDay[$n];
                if ($ci['decision'] === 'applicable') {
                    $perN = $ci['manual_amount'] ?? $ci['child_rate'];
                    if ($perN !== null && $cur === 'USD') {
                        $line['lines'][] = ['kind' => 'child', 'label' => 'Child(ren) — ' . (int) $ci['children'] . ' (Child Fare Review)', 'qty' => 1, 'rate' => (float) $perN, 'amount' => round((float) $perN, 2)];
                        $amount += (float) $perN;
                    } elseif ($perN !== null) {
                        $warnings[] = ['code' => 'child_cur_' . $d['id'], 'label' => "{$hotel}, night {$n}: the child charge is in USD but the rate is in {$cur}; check it."];
                    }
                } else {
                    $line['lines'][] = ['kind' => 'child', 'label' => 'Child: no charge — ' . ($ci['reason'] ?: 'Child Fare Review'), 'qty' => (int) $ci['children'], 'rate' => 0.0, 'amount' => 0.0];
                }
            }
            // compulsory supplements: per person × the people there that night, per room × the rooms
            $paxNight = $mix['pax'] !== null && (int) $mix['pax'] > 0 ? (int) $mix['pax'] : $pax;
            foreach ((array) ($d['rate_supp'] ?? []) as $s) {
                $per = strtolower((string) ($s['per'] ?? 'person'));
                $qty = $per === 'room' ? max(1, (int) $mix['rooms']) : ($per === 'person' ? $paxNight : 1);
                if (!in_array($per, ['room', 'person'], true)) {
                    $warnings[] = ['code' => 'supp_' . $d['id'], 'label' => "{$hotel}, night {$n}: the supplement \"{$s['name']}\" is charged per {$per}; it is costed once. Check it."];
                }
                $tot = round($qty * (float) $s['amount'], 2);
                $line['supplements'][] = ['name' => (string) $s['name'], 'per' => $per, 'qty' => $qty, 'rate' => (float) $s['amount'], 'amount' => $tot];
                $amount += $tot;
            }
            $line['amount']     = round($amount, 2);
            $line['amount_usd'] = $cur === 'USD' ? $line['amount'] : $toUsd($line['amount']);
            if ($line['amount_usd'] !== null) {
                $hotelUsd += $line['amount_usd'];
            }
            $hotels[] = $line;
        }

        // ---- transport: each vehicle line, its charged kilometres × its rate ----------
        $mil = $it['mileage'] ?? ['by_vehicle' => [], 'legs' => 0];
        $transport = [];
        $transportLkr = 0.0;
        if (($it['vehicles'] ?? []) === []) {
            $blockers[] = ['code' => 'no_vehicle', 'label' => 'No vehicle has been chosen.'];
        }
        if ((int) ($mil['legs'] ?? 0) === 0 && ($it['days'] ?? []) !== []) {
            $blockers[] = ['code' => 'no_legs', 'label' => 'The itinerary has not been broken into legs, so there is no mileage to cost.'];
        }
        foreach (($mil['by_vehicle'] ?? []) as $bv) {
            $tl = [
                'vehicle_seq' => (int) $bv['vehicle_seq'], 'label' => $bv['label'] ?: ('Vehicle ' . ((int) $bv['vehicle_seq'] + 1)), 'type' => $bv['type'], 'qty' => (int) $bv['qty'],
                'group_id' => $bv['group_id'], 'legs' => (int) $bv['legs'], 'km' => (float) $bv['km'], 'extra_km' => (float) $bv['extra_km'],
                'waived_km' => (float) $bv['waived_km'], 'charged_km' => (float) $bv['charged_km'], 'km_rate' => $bv['km_rate'], 'currency' => $bv['currency'] ?: 'LKR',
                'amount' => $bv['amount'], 'amount_usd' => null, 'state' => 'ok', 'why' => null,
            ];
            if ((int) $bv['unpriced'] > 0) {
                $tl['state'] = 'missing';
                $tl['why']   = (int) $bv['unpriced'] . ' leg(s) have no distance.';
                $blockers[]  = ['code' => 'km_unpriced_' . $bv['vehicle_seq'], 'label' => $tl['label'] . ': ' . (int) $bv['unpriced'] . ' leg(s) have no distance — type the kilometres in.'];
            }
            if ($bv['km_rate'] === null && (int) $bv['legs'] > 0 && ($it['vehicles'] ?? []) !== []) {
                $tl['state'] = 'missing';
                $tl['why']   = 'No rate per kilometre.';
                $blockers[]  = ['code' => 'km_norate_' . $bv['vehicle_seq'], 'label' => $tl['label'] . ' (' . ($bv['type'] ?: 'size not set') . ') has no rate per kilometre. Set it under Master Files → Transport Rates.'];
            }
            if ($tl['state'] === 'ok' && $bv['amount'] !== null) {
                $transportLkr  += (float) $bv['amount'];
                $tl['amount_usd'] = $toUsd((float) $bv['amount']);
            }
            if ((int) $bv['thin'] > 0) {
                $warnings[] = ['code' => 'km_thin_' . $bv['vehicle_seq'], 'label' => $tl['label'] . ': ' . (int) $bv['thin'] . ' leg(s) rest on only one or two past journeys.'];
            }
            $transport[] = $tl;
        }
        foreach (($it['vehicles'] ?? []) as $v) {
            if (($v['rate_by'] ?? '') === 'manual') {
                $warnings[] = ['code' => 'rate_special_' . $v['id'], 'label' => 'A special rate per km is on the ' . $v['type'] . ' (' . number_format((float) $v['km_rate'], 2) . ' against the master\'s ' . ($v['master_rate'] !== null ? number_format((float) $v['master_rate'], 2) : '—') . ').', 'internal' => true];
            }
        }

        // ---- crew: bata × days; their rooms from the accommodation lines ------------
        $crew = [];
        $crewLkr = 0.0;
        if (($it['crew'] ?? []) === []) {
            $warnings[] = ['code' => 'no_crew', 'label' => 'No driver or guide is on this inquiry.'];
        }
        foreach (($it['crew'] ?? []) as $c) {
            // the master's bata for the category and language (schema 23); a freelancer's own
            // agreed figure is the payment side's and is not what the quotation is costed at
            $bata = ($c['bata'] === null || $c['bata'] === '') ? null : (float) $c['bata'];
            // schema 32: the days are the programme's (or a person's, when typed); before the rule, the nights
            $days = ($c['days_by'] ?? null) !== null ? (int) ($c['days'] ?? 0) : ((int) ($c['days'] ?? 0) ?: $nights);
            $cl   = ['role' => $c['role_name'], 'code' => $c['role_code'], 'person' => $c['person_name'] ?? null, 'days' => $days, 'bata' => $bata, 'currency' => $c['currency'] ?: 'LKR',
                     'amount' => null, 'amount_usd' => null, 'state' => 'ok', 'why' => null, 'days_by' => $c['days_by'] ?? null, 'days_basis' => $c['days_basis'] ?? null];
            if ($bata === null) {
                $cl['state'] = 'missing';
                $cl['why']   = 'No bata set.';
                $blockers[]  = ['code' => 'crew_rate_' . $c['role_code'], 'label' => $c['role_name'] . ' has no bata, so it cannot be costed. Admin sets it under Master Files → Crew Bata.'];
            } elseif ($days <= 0) {
                $cl['state'] = 'missing';
                $cl['why']   = 'No days.';
                $blockers[]  = ['code' => 'crew_days_' . $c['role_code'], 'label' => $c['role_name'] . (($c['days_by'] ?? null) === 'auto'
                    ? ': nothing on the programme yet to count the days from (no transfer, visit or activity on any day). Plan the transport, or type the days on the line.'
                    : ': the number of days is not set.')];
            } else {
                $cl['amount']     = round($days * $bata, 2);
                $cl['amount_usd'] = $toUsd($cl['amount']);
                $crewLkr         += $cl['amount'];
            }
            $crew[] = $cl;
        }
        $crewRooms = [];
        $crewRoomsUsd = 0.0;
        foreach (($it['accom'] ?? []) as $kind => $a) {
            if ((int) ($a['required'] ?? 0) !== 1) {
                continue;
            }
            $ar = ['kind' => $kind, 'nights' => (int) ($a['nights'] ?? 0), 'rate' => $a['rate'] !== null ? (float) $a['rate'] : null, 'currency' => $a['currency'] ?: 'USD',
                   'amount' => $a['amount'] !== null ? (float) $a['amount'] : null, 'amount_usd' => null, 'state' => 'ok', 'why' => null];
            if ((int) ($a['unknown_nights'] ?? 0) > 0) {
                $ar['state'] = 'missing';
                $ar['why']   = (int) $a['unknown_nights'] . ' night(s) with nothing on file.';
                $blockers[]  = ['code' => 'accom_' . $kind, 'label' => ucfirst($kind) . ' accommodation: ' . (int) $a['unknown_nights'] . ' night(s) have nothing on file about what a room costs. Price them or drop the requirement.'];
            } elseif ($ar['amount'] === null) {
                $ar['state'] = 'missing';
                $ar['why']   = 'No figure.';
                $blockers[]  = ['code' => 'accom_' . $kind, 'label' => ucfirst($kind) . ' accommodation is required but has no figure.'];
            } else {
                $cur = strtoupper($ar['currency']);
                $ar['amount_usd'] = $cur === 'USD' ? $ar['amount'] : ($cur === 'LKR' ? $toUsd($ar['amount']) : null);
                if ($ar['amount_usd'] === null && $cur !== 'LKR') {
                    $blockers[] = ['code' => 'accom_cur_' . $kind, 'label' => ucfirst($kind) . " accommodation is in {$cur}; only USD and LKR can be costed."];
                } elseif ($ar['amount_usd'] !== null) {
                    $crewRoomsUsd += $ar['amount_usd'];
                }
            }
            $crewRooms[] = $ar;
        }

        // ---- Entrance & Activities: the section's figures, taken as they are --------------
        // The section (IntakeService::entrances) is the one place these are worked out: each
        // line is priced there from the list and the party, and follows both on its own.
        // Nothing is priced or guessed here — a line is costed or it is not, by the same
        // reading the section uses, and what stops the quotation is said line by line.
        $services = [];
        $servicesUsd = 0.0;
        foreach (($it['services'] ?? []) as $s) {
            $mode = (string) ($s['pay_mode'] ?? 'unknown');
            $c    = ServiceLookup::classify($s);
            $sl = ['id' => (int) $s['id'], 'day_no' => (int) $s['day_no'], 'title' => $s['title'], 'kind' => $s['kind'], 'pay_mode' => $c['state'] === 'no_charge' ? 'no_charge' : $mode, 'qty' => $s['qty'], 'unit_usd' => $s['unit_usd'] !== null ? (float) $s['unit_usd'] : null,
                   'amount_usd' => $s['amount_usd'] !== null ? (float) $s['amount_usd'] : null, 'description' => $s['description'] ?? null, 'costed' => $c['costed'], 'state' => $c['blocks'] ? 'missing' : 'ok', 'why' => $c['why'],
                   'fee_code' => $s['fee_code'] ?? null, 'line_state' => $c['state'], 'free' => !empty($s['free_visit_id'])];
            if (!empty($s['meal_id'])) {
                // schema 40: a lunch or dinner from the Meal Master — kept with the version in the meal's own currency too
                $sl['meal'] = ['meal_id' => (int) $s['meal_id'], 'adults' => $s['qty_adult'] !== null ? (int) $s['qty_adult'] : null, 'children' => $s['qty_child'] !== null ? (int) $s['qty_child'] : null,
                               'time' => $s['meal_time'] ?? null, 'currency' => $s['rate_currency'] ?? null, 'rate_adult' => $s['rate_adult'] !== null ? (float) $s['rate_adult'] : null,
                               'rate_child' => $s['rate_child'] !== null ? (float) $s['rate_child'] : null, 'tax' => $s['tax_cur'] !== null ? (float) $s['tax_cur'] : null,
                               'total' => $s['amount_cur'] !== null ? (float) $s['amount_cur'] : null];
            }
            $day = 'Day ' . (int) $s['day_no'];
            switch ($c['state']) {
                case 'unsure':
                    // unsure, never forced: No charge, the closest entry, or the master — a person decides
                    $blockers[] = ['code' => 'svc_' . $s['id'], 'label' => $day . ': "' . $s['title'] . '" is not in the priced list — mark it No charge if nothing is paid, pick the priced entry' . (!empty($s['suggest_code']) ? ' (closest: ' . $s['suggest_code'] . ')' : '') . ', or add it under Master Files.'];
                    break;
                case 'no_amount':
                    $blockers[] = ['code' => 'svc_' . $s['id'], 'label' => !empty($s['place_id'])
                        ? $day . ': "' . $s['title'] . '" carries no amount — ' . rtrim((string) ($s['match_note'] ?: 'give the number on the line'), '.') . '.'
                        : $day . ': "' . $s['title'] . '" is in the list but carries no amount — mark it No charge if nothing is paid, or set the amount under Master Files → Entrance Fee.'];
                    break;
                case 'rate_missing':
                    // schema 39: a place of the Place to Visit / Entrance Fee Master with no rate for the day — the master is completed, nothing is guessed here
                    $blockers[] = ['code' => 'svc_rate_' . $s['id'], 'label' => $day . ': "' . $s['title'] . '" — ' . rtrim((string) ($s['match_note'] ?: 'in the Place to Visit / Entrance Fee Master with no rate for the day'), '.') . '.'];
                    break;
                case 'not_in_master':
                    // schema 39: an entrance or a safari that is not in the master — held, never taken as free
                    $blockers[] = ['code' => 'svc_master_' . $s['id'], 'label' => $day . ': "' . $s['title'] . '" is ' . ((string) ($s['kind'] ?? '') === 'safari' ? 'a safari' : 'an entrance') . ' not in the Place to Visit / Entrance Fee Master — pick the place, or add it there (with its rate, or as Free)'
                        . (!empty($s['suggest_code']) ? ' (closest: ' . $s['suggest_code'] . ')' : '') . '; if it is not ' . ((string) ($s['kind'] ?? '') === 'safari' ? 'a park safari' : 'a paid entrance') . ', change the line\'s kind.'];
                    break;
                case 'rupees':
                    // the priced list holds 2,000 for a temple: rupees in the USD column. Flagged by the list; a blocker here.
                    $blockers[] = ['code' => 'svc_rupees_' . $s['id'], 'label' => $day . ': "' . $s['title'] . '" is listed at ' . number_format((float) $s['unit_usd'], 2) . ' USD, which looks like rupees typed into the USD column. Correct it under Master Files → Entrance Fee.'];
                    break;
                case 'priced':
                    if ($mode === 'unknown') {
                        $warnings[] = ['code' => 'svc_mode_' . $s['id'], 'label' => $day . ': who pays for "' . $s['title'] . '" is not settled; it is costed in the tour so the quotation is not short.'];
                    }
                    $servicesUsd += $sl['amount_usd'];
                    break;
            }
            $services[] = $sl;
        }
        // the same name twice on one day is one thing costed twice: a stop, with the extra line to remove
        foreach (($it['entrances']['duplicates'] ?? []) as $dup) {
            $n = count($dup['ids']);
            $blockers[] = ['code' => 'svc_dup_' . (int) $dup['ids'][1], 'label' => 'Day ' . (int) $dup['day_no'] . ': "' . $dup['title'] . '" is on the day ' . ($n === 2 ? 'twice' : $n . ' times') . ' — remove the extra line under Entrance & Activities so it is not costed ' . ($n === 2 ? 'twice' : $n . ' times') . '.'];
        }
        // a place the day's narrative names that has no line: for a person to look at, so an entrance is not left out
        foreach (($it['entrances']['mentions'] ?? []) as $mn) {
            $warnings[] = ['code' => 'svc_mention_' . (int) $mn['day_no'] . '_' . preg_replace('/[^A-Za-z0-9]+/', '_', (string) $mn['code']), 'label' => 'Day ' . (int) $mn['day_no'] . ' mentions "' . $mn['phrase'] . '" — the priced list has ' . $mn['name'] . ' (' . $mn['code'] . ') — but there is no line for it under Entrance & Activities. Add it if it is a visit, or leave it if it is only a drive past.'];
        }
        // schema 40: the meals of a Half Board tour — the same people never given lunch (dinner) twice on a day, and a meal the
        // hotel's plan gives that night arranged outside only when a person said so, with the reason (the Meals card)
        foreach (($it['meals']['problems'] ?? []) as $mp) {
            $blockers[] = ['code' => (string) $mp['code'], 'label' => (string) $mp['label']];
        }
        // the section's own total, which is the figure below — one and the same
        $entrancesTotal = round((float) ($it['entrances']['total_usd'] ?? $servicesUsd), 2);
        if (abs($entrancesTotal - round($servicesUsd, 2)) >= 0.01) {
            // cannot happen while both read the same lines the same way; said loudly if it ever does
            $blockers[] = ['code' => 'svc_total', 'label' => 'The Entrance & Activities total (USD ' . number_format($entrancesTotal, 2) . ') and the costing\'s reading of its lines (USD ' . number_format($servicesUsd, 2) . ') disagree. Open the section and check it again.'];
        }

        // ---- excursions: the rate copied from the master × the quantity ----------------
        $excursions = [];
        $excUsd = 0.0;
        foreach (($it['excursions'] ?? []) as $x) {
            $cur = strtoupper((string) ($x['currency'] ?: 'USD'));
            // tf-child-r1: a per-person excursion the review marked "not applicable" is charged for the adults only
            foreach (($childReview['items'] ?? []) as $ci) {
                if ($ci['key'] === 'exc:' . (int) $x['id'] && $ci['decision'] === 'not_applicable' && (string) $x['unit'] === 'per_person') {
                    $x['qty'] = max(0, (float) $x['qty'] - (int) $ci['children']);
                    $x['amount'] = null;
                }
            }
            $xl  = ['day_no' => (int) $x['day_no'], 'group_id' => $x['group_id'], 'code' => $x['code'], 'name' => $x['name'], 'unit' => $x['unit'], 'qty' => (float) $x['qty'], 'rate' => (float) $x['rate'],
                    'currency' => $cur, 'amount' => $x['amount'] !== null ? (float) $x['amount'] : round((float) $x['qty'] * (float) $x['rate'], 2), 'amount_usd' => null,
                    'includes_transport' => (int) $x['includes_transport'], 'description' => $x['description'] ?? null, 'state' => 'ok', 'why' => null];
            if ($xl['qty'] <= 0) {
                $xl['state'] = 'missing';
                $xl['why']   = 'Quantity nought.';
                $blockers[]  = ['code' => 'exc_qty_' . $x['id'], 'label' => $x['name'] . ' on day ' . (int) $x['day_no'] . ' has a quantity of nought — say how many go.'];
            } elseif (!in_array($cur, ['USD', 'LKR'], true)) {
                $xl['state'] = 'missing';
                $xl['why']   = "In {$cur}.";
                $blockers[]  = ['code' => 'exc_cur_' . $x['id'], 'label' => $x['name'] . " is priced in {$cur}; only USD and LKR can be costed."];
            } else {
                $xl['amount_usd'] = $cur === 'USD' ? $xl['amount'] : $toUsd($xl['amount']);
                if ($xl['amount_usd'] !== null) {
                    $excUsd += $xl['amount_usd'];
                }
            }
            $excursions[] = $xl;
        }

        // ---- the groups shaped every line above; only their arithmetic is checked here --
        $sumGroups = array_sum(array_map(static fn ($g) => (int) $g['pax'], $it['groups'] ?? []));
        if (($it['groups'] ?? []) !== [] && $pax > 0 && $sumGroups !== $pax) {
            $blockers[] = ['code' => 'groups_pax', 'label' => "The groups add up to {$sumGroups} passenger(s), but the inquiry is for {$pax}."];
        }

        // ---- warnings carried over from the inquiry's own gap list ------------------
        foreach (($it['gaps'] ?? []) as $g) {
            if (in_array($g['code'], ['rooms_sleeps', 'nights_disagree'], true) || str_starts_with($g['code'], 'group_sleeps_') || str_starts_with($g['code'], 'group_names_') || str_starts_with($g['code'], 'q_') || str_starts_with($g['code'], 'rate_moved_')) {
                $warnings[] = ['code' => $g['code'], 'label' => $g['label']];
            }
        }

        // ---- totals, and the price ---------------------------------------------------
        $transportUsd = $toUsd($transportLkr) ?? 0.0;
        $crewUsd      = $toUsd($crewLkr) ?? 0.0;
        $sections = [
            'hotels'     => round($hotelUsd, 2),
            'transport'  => round($transportUsd, 2),
            'crew'       => round($crewUsd, 2),
            'crew_rooms' => round($crewRoomsUsd, 2),
            'services'   => $entrancesTotal,   // Entrance & Activities, the section's total as it is
            'excursions' => round($excUsd, 2),
        ];
        $costUsd = round(array_sum($sections), 2);
        $parts   = self::parts($sections);
        $pricing = $this->pricing();
        // tf-stage6: the markup is the inquiry's own, else the agent's default, else the company default
        $agentQ  = CustomersAgents::forInquiry($this->db, $it['agent_acc'] ?? null);
        $pct     = ($it['markup_pct'] !== null && $it['markup_pct'] !== '') ? (float) $it['markup_pct'] : ($agentQ['quote_markup_pct'] ?? $pricing['markup_default_pct']);
        $markup  = [
            'pct' => $pct, 'source' => ($it['markup_pct'] !== null && $it['markup_pct'] !== '') ? 'inquiry' : ($agentQ['quote_markup_pct'] !== null ? 'agent' : ($pct !== null ? 'default' : 'none')),
            'agent_currency' => $agentQ['quote_currency'], 'agent_is_agent' => $agentQ['is_agent'],
            'set_by' => $it['markup_by'] ?? null, 'set_at' => $it['markup_at'] ?? null, 'note' => $it['markup_note'] ?? null,
            'sell_usd' => null, 'profit_usd' => null, 'per_person_usd' => null,
        ];
        if ($pct === null) {
            $blockers[] = ['code' => 'pricing', 'label' => 'Pricing has not been set by Management: no markup on this inquiry and no default under Pricing. The client quotation cannot be produced until it is.'];
        } else {
            $markup['sell_usd']       = round($costUsd * (1 + $pct / 100), 2);
            $markup['profit_usd']     = round($markup['sell_usd'] - $costUsd, 2);
            $markup['per_person_usd'] = $pax > 0 ? round($markup['sell_usd'] / $pax, 2) : null;
        }

        // ---- the pax matrix (schema 31): every party size from one to ten costed on its own ----
        // from the masters, above the three sections. The inquiry's own size is worked out again
        // on its own rooms and vehicle and must come to the same figure as the sections above;
        // if the two ever disagree the quotation waits, because the agent's table would then
        // contradict the price. A size the masters cannot price is said, and "on request" for the agent.
        $matrix = null;
        try {
            $px = new PaxMatrix($this->db, $this->take->planner());
            if ($px->available()) {
                $matrix = $px->build($it, $rate, $pct);
                $matrix['check'] = PaxMatrix::check($matrix, $parts, $costUsd);
                $chk = $matrix['check'];
                if ($chk !== null && !$chk['equal']) {
                    $blockers[] = ['code' => 'pax_matrix_check', 'label' => 'The pax matrix gives USD ' . number_format($chk['matrix_usd'], 2) . ' for this inquiry\'s ' . $chk['pax'] . ' pax, the detailed costing USD ' . number_format($chk['costing_usd'], 2)
                        . ' (hotels ' . number_format((float) $chk['parts']['hotels']['matrix'], 2) . ' / ' . number_format($chk['parts']['hotels']['costing'], 2)
                        . ', paid ' . number_format((float) $chk['parts']['paid']['matrix'], 2) . ' / ' . number_format($chk['parts']['paid']['costing'], 2)
                        . ', transport ' . number_format((float) $chk['parts']['transport']['matrix'], 2) . ' / ' . number_format($chk['parts']['transport']['costing'], 2)
                        . '). They must agree before a quotation goes out.'];
                }
                $open = array_values(array_filter($matrix['levels'], static fn ($L) => !$L['complete'] && $L['gaps'] !== []));
                if ($open !== []) {
                    $why = [];
                    foreach ($open as $L) {
                        foreach ($L['gaps'] as $g) {
                            $why[$g] = true;
                        }
                    }
                    $warnings[] = ['code' => 'pax_matrix_open', 'label' => 'The pax matrix cannot price ' . self::sizes(array_column($open, 'pax')) . ' pax: ' . implode(' ', array_keys($why)) . ' The agent\'s table says "on request" for ' . (count($open) === 1 ? 'that size' : 'those sizes') . '.'];
                }
            }
        } catch (\Throwable $e) {
            $matrix = null;
            $warnings[] = ['code' => 'pax_matrix_error', 'label' => 'The pax matrix could not be worked out (' . $e->getMessage() . '), so the agent\'s quotation has no table by party size. The costing below is not affected.'];
        }

        // one entry per code: the same hole reported twice is noise
        // tf-stage4: the quotation gate — a rate that moved since the figures were saved has to be reviewed first
        $watch = $it['costing_watch'] ?? null;
        if (is_array($watch) && !empty($watch['blocked'])) {
            $what = array_slice(array_map(static fn ($i) => $i['what'] . ': ' . $i['old'] . ' → ' . $i['new'], $watch['items']), 0, 4);
            $blockers[] = ['code' => 'rates_unreviewed', 'label' => (int) $watch['count'] . ' rate change' . ((int) $watch['count'] === 1 ? '' : 's') . ' since this inquiry was costed ' . ((int) $watch['count'] === 1 ? 'is' : 'are') . ' not reviewed yet — ' . implode('; ', $what) . ((int) $watch['count'] > 4 ? ' …' : '') . '. Press Recalculate (take the new figures) or Keep saved figures: the Quotation Desk member handling it, Management or the System Administrator.'];
        } elseif (is_array($watch) && !empty($watch['reviewed'])) {
            $warnings[] = ['code' => 'rates_kept', 'label' => 'Saved figures kept after review by ' . ($watch['reviewed_by'] ?: 'a colleague') . ($watch['reviewed_at'] ? ' on ' . date('j M Y H:i', strtotime((string) $watch['reviewed_at'])) : '') . ': ' . (int) $watch['count'] . ' master rate change' . ((int) $watch['count'] === 1 ? '' : 's') . ' seen and not applied.'];
        }
        $dedupe = static function (array $rows): array {
            $seen = [];
            $out  = [];
            foreach ($rows as $r) {
                if (isset($seen[$r['code']])) {
                    continue;
                }
                $seen[$r['code']] = true;
                $out[] = $r;
            }
            return $out;
        };

        return [
            'intake_id'   => $intakeId,
            'inquiry_no'  => $it['inquiry_no'],
            'agent_name'  => $it['agent_name'],
            'agent_acc'   => $it['agent_acc'] ?? null,
            'tour_name'   => $it['tour_name'],
            'market_code' => $it['market_code'] ?? null,
            'arrival'     => $it['arrival_date'],
            'departure'   => $it['departure_date'],
            'nights'      => $nights,
            // schema 32: the one line every screen prints, and the departure day as the programme has it
            'tour_dates'  => $tourDates,
            'departure_day' => isset($it['departure_day']) && is_array($it['departure_day']) ? [
                'day_no' => $it['departure_day']['day_no'], 'date' => $it['departure_day']['date'], 'from' => $it['departure_day']['from'],
                'flight' => $it['departure_day']['flight'], 'note' => $it['departure_day']['note'],
                'services' => array_map(static fn ($s) => $s['title'], $it['departure_day']['services'] ?? []),
            ] : null,
            'pax'         => $pax,
            'groups'      => array_map(static fn ($g) => ['id' => (int) $g['id'], 'code' => $g['code'], 'name' => $g['name'], 'pax' => (int) $g['pax'], 'day_from' => $g['day_from'], 'day_to' => $g['day_to'],
                'sgl' => (int) $g['rooms_sgl'], 'dbl' => (int) $g['rooms_dbl'], 'twn' => (int) $g['rooms_twn'], 'tpl' => (int) $g['rooms_tpl']], $it['groups'] ?? []),
            'rooms'       => $mixAll,
            'currency'    => 'USD',
            'usd_rate'    => $rate,
            'rate_date'   => $usd['rate_date'] ?? null,
            'hotels'      => $hotels,
            'transport'   => $transport,
            'crew'        => $crew,
            'crew_rooms'  => $crewRooms,
            'services'    => $services,
            'entrances'   => ['total_usd' => $entrancesTotal, 'count' => count($services), 'stops' => (int) ($it['entrances']['stops'] ?? 0), 'by_state' => $it['entrances']['by_state'] ?? []],
            'excursions'  => $excursions,
            // the itinerary as written for the client (schema 28): frozen with the version, so what was sent stays as sent
            'itinerary'   => $it['itinerary'] ?? null,
            'sections'    => $sections,
            // the costing read in three (schema 29): hotels, paid activities and services, transport —
            // the same figures as the sections, grouped; the three add up to the tour cost
            'parts'       => $parts,
            'hotel_subtotals' => self::hotelSubtotals($hotels),
            // tf-child-r1: what the Child Fare Review decided, kept with the version
            'child_review' => $childReview === null ? null : ['children' => $childReview['children'], 'adults' => $childReview['adults'], 'open' => $childReview['open'],
                'items' => array_map(static fn ($i) => ['key' => $i['key'], 'kind' => $i['kind'], 'title' => $i['title'], 'days' => $i['day_from'] . ($i['day_to'] !== $i['day_from'] ? '–' . $i['day_to'] : ''),
                    'decision' => $i['decision'], 'status' => $i['status'], 'reason' => $i['reason'], 'adult_calc' => $i['adult_calc'], 'child_calc' => $i['decision'] === 'not_applicable' ? 'Child: no charge — ' . ($i['reason'] ?: '') : ($i['manual_amount'] !== null ? 'USD ' . number_format($i['manual_amount'], 2) . ' a night (worked out by staff)' : $i['child_calc']),
                    'child_total' => $i['effective_child_total'], 'decided_by' => $i['decided_by'], 'decided_at' => $i['decided_at']], $childReview['items'])],
            'free_visits' => count(array_filter($services, static fn ($sl) => $sl['line_state'] === 'no_charge')),
            // every party size from one to ten, each costed on its own (schema 31) — frozen with the version
            'pax_matrix'  => $matrix,
            'cost_usd'    => $costUsd,
            'cost_lkr'    => $toLkr($costUsd),
            'per_person_cost_usd' => $pax > 0 ? round($costUsd / $pax, 2) : null,
            'markup'      => $markup,
            // tf-stage4: the kept rate beside today's, and whether the saved figures have unreviewed changes
            'usd_now'     => $usdNow,
            'costing_watch' => $watch ?? null,
            'blockers'    => $dedupe($blockers),
            'warnings'    => $dedupe($warnings),
            'ready'       => $blockers === [],
            'costed_at'   => date('Y-m-d H:i:s'),
            'costed_by'   => $this->user,
        ];
    }

    /** Which sections make up each of the three parts of the costing, and what each part is called. */
    public const PARTS = [
        'hotels'    => ['label' => 'Hotel cost', 'sections' => ['hotels']],
        'paid'      => ['label' => 'Paid activities & services', 'sections' => ['services', 'excursions']],
        'transport' => ['label' => 'Transport cost', 'sections' => ['transport', 'crew', 'crew_rooms']],
    ];

    /**
     * The three parts of the costing from its sections: the hotels; the paid activities and
     * services (entrances and tickets, activities and excursions); the transport (the vehicle on
     * its mileage, the driver and guide, and their rooms). The three add up to the tour cost.
     * A costing frozen before the parts existed is read the same way.
     *
     * @param array<string, float|int|null> $sections
     * @return array<string, array{label:string, usd:float, sections:array<string, float>}>
     */
    public static function parts(array $sections): array
    {
        $out = [];
        foreach (self::PARTS as $k => $p) {
            $of = [];
            foreach ($p['sections'] as $sec) {
                $of[$sec] = round((float) ($sections[$sec] ?? 0), 2);
            }
            $out[$k] = ['label' => $p['label'], 'usd' => round(array_sum($of), 2), 'sections' => $of];
        }
        return $out;
    }

    /**
     * A subtotal for each hotel stay: the nights in a row at one hotel, in the order they come.
     * The same hotel again later in the tour is another stay with its own subtotal. A stay with a
     * night that could not be costed says so and its subtotal is what could be.
     *
     * @return array<int, array{hotel:?string, area:?string, nights:array<int,int>, amount_usd:float, complete:bool, last_day:int}>
     */
    public static function hotelSubtotals(array $hotels): array
    {
        $out = [];
        $cur = null;
        foreach ($hotels as $h) {
            $name = $h['hotel'] ?? null;
            $key  = mb_strtolower(trim((string) $name));
            if ($cur !== null && $cur['key'] === $key && $key !== '' && (int) $h['day_no'] === $cur['last_day'] + 1) {
                $cur['nights'][] = (int) $h['day_no'];
            } else {
                if ($cur !== null) {
                    $out[] = $cur;
                }
                $cur = ['key' => $key, 'hotel' => $name, 'area' => $h['area'] ?? null, 'nights' => [(int) $h['day_no']], 'amount_usd' => 0.0, 'complete' => true, 'last_day' => (int) $h['day_no']];
            }
            $cur['last_day'] = (int) $h['day_no'];
            if (($h['state'] ?? 'ok') !== 'ok' || $h['amount_usd'] === null) {
                $cur['complete'] = false;
            } else {
                $cur['amount_usd'] = round($cur['amount_usd'] + (float) $h['amount_usd'], 2);
            }
        }
        if ($cur !== null) {
            $out[] = $cur;
        }
        return array_map(static function ($c) {
            unset($c['key']);
            return $c;
        }, $out);
    }

    /**
     * What the client sees: the same costing with everything internal taken out —
     * no cost, no markup, no profit, no rate per kilometre, no bata, no supplier
     * figures, no special rates. What is left is the itinerary, what is included,
     * and the price — with the price per person by party size, and nothing else of the matrix.
     */
    public static function clientView(array $cost): array
    {
        $days = [];
        foreach ($cost['hotels'] as $h) {
            $days[$h['day_no']] = ['day_no' => $h['day_no'], 'date' => $h['date'], 'area' => $h['area'], 'hotel' => $h['hotel'], 'room_type' => $h['room_type'], 'meal_plan' => $h['meal_plan'], 'services' => [], 'excursions' => []];
        }
        // a day with something on it but no night (schema 32: the departure day — a late check-out, lunch
        // on the way) is a day of its own, dated from the arrival, with no hotel: it reads as the departure
        $dayOf = static function (int $n) use (&$days, $cost): void {
            if ($n > 0 && !isset($days[$n])) {
                $days[$n] = ['day_no' => $n, 'date' => TourDates::dateOf($cost['arrival'] ?? null, $n), 'area' => null, 'hotel' => null, 'room_type' => null, 'meal_plan' => null, 'services' => [], 'excursions' => []];
            }
        };
        foreach ($cost['services'] as $s) {
            if (in_array($s['pay_mode'], ['not_arranged'], true)) {
                continue;
            }
            $dayOf((int) $s['day_no']);
            // a no-charge stop is part of the programme and of what is included, at no cost
            $days[$s['day_no']]['services'][] = ['title' => $s['title'], 'kind' => $s['kind'], 'description' => $s['description'], 'included' => $s['costed'] || $s['pay_mode'] === 'no_charge' || $s['pay_mode'] === 'in_rate', 'note' => $s['pay_mode'] === 'direct' ? 'payable direct' : ($s['pay_mode'] === 'own_arrangement' ? 'own arrangement'
                : ($s['pay_mode'] === 'in_rate' ? 'included in the hotel rate'
                : ($s['pay_mode'] === 'optional' ? 'optional extra' . ($s['unit_usd'] !== null && $s['unit_usd'] > 0 ? ' — USD ' . number_format((float) $s['unit_usd'], 2) . ' per person' : '')
                // tf-safari-r1: a safari is one item to the client — the jeep and the park entrance in the one price, never broken down
                : ($s['costed'] && (string) $s['kind'] === 'safari' ? 'jeep and park entrance included' : null))))];
        }
        foreach ($cost['excursions'] as $x) {
            $dayOf((int) $x['day_no']);
            $days[$x['day_no']]['excursions'][] = ['name' => $x['name'], 'description' => $x['description'], 'includes_transport' => $x['includes_transport']];
        }
        ksort($days);
        $vehicles = [];
        foreach ($cost['transport'] as $t) {
            $vehicles[] = ['label' => $t['label'], 'type' => $t['type'], 'qty' => $t['qty']];
        }
        $crew = [];
        foreach ($cost['crew'] as $c) {
            $crew[] = $c['role'];
        }
        return [
            'inquiry_no' => $cost['inquiry_no'], 'agent_name' => $cost['agent_name'], 'tour_name' => $cost['tour_name'],
            'arrival' => $cost['arrival'], 'departure' => $cost['departure'], 'nights' => $cost['nights'], 'pax' => $cost['pax'],
            // schema 32: the one dates line; a version frozen before it has none and prints as it did
            'tour_dates' => $cost['tour_dates'] ?? null,
            'groups' => $cost['groups'], 'rooms' => $cost['rooms'], 'days' => array_values($days), 'vehicles' => $vehicles, 'crew' => $crew,
            'itinerary' => $cost['itinerary'] ?? null,
            'currency' => $cost['currency'],
            'total_usd' => $cost['markup']['sell_usd'], 'per_person_usd' => $cost['markup']['per_person_usd'],
            // the price per person by party size (schema 31): the size, the rooms, the price — and
            // nothing else; a version frozen before the matrix existed has no table
            'pax_prices' => PaxMatrix::forAgent($cost['pax_matrix'] ?? null),
        ];
    }

    /** "3", "3 and 5", "3, 5, 7 and 9". */
    private static function sizes(array $n): string
    {
        $n = array_map('intval', $n);
        if (count($n) <= 1) {
            return (string) ($n[0] ?? '');
        }
        $last = array_pop($n);
        return implode(', ', $n) . ' and ' . $last;
    }

    // =====================================================================
    //  Go for Quotation: validate, then freeze a version
    // =====================================================================

    /**
     * Validate, and on a pass write a draft version with the costing frozen inside it.
     * On a fail nothing is written and the blockers come back in words.
     *
     * @return array{ok:bool,blockers:array,warnings:array,quote:?array,cost:array}
     */
    public function goForQuotation(int $intakeId): array
    {
        $this->take->assertOpen($intakeId);
        $cost = $this->costing($intakeId);
        if ($cost['blockers'] !== []) {
            return ['ok' => false, 'blockers' => $cost['blockers'], 'warnings' => $cost['warnings'], 'quote' => null, 'cost' => $cost];
        }
        $pricing = $this->pricing();
        // an earlier draft that was never issued is replaced, not kept: only issued versions are history
        $this->db->query("DELETE FROM tf_quote WHERE intake_id = ? AND status = 'draft'", [$intakeId]);
        $ver  = (int) ($this->db->query('SELECT COALESCE(MAX(version), 0) + 1 AS v FROM tf_quote WHERE intake_id = ?', [$intakeId])->getRowArray()['v'] ?? 1);
        $now  = date('Y-m-d H:i:s');
        $this->db->query(
            "INSERT INTO tf_quote (intake_id, version, status, currency, usd_rate, rate_date, pax, cost_lkr, cost_usd, markup_pct, sell_usd, per_person_usd, cost_json, valid_until, created_at, created_by)
             VALUES (?,?,'draft','USD',?,?,?,?,?,?,?,?,?,?,?,?)",
            [$intakeId, $ver, $cost['usd_rate'], $cost['rate_date'], $cost['pax'], $cost['cost_lkr'], $cost['cost_usd'], $cost['markup']['pct'], $cost['markup']['sell_usd'], $cost['markup']['per_person_usd'],
             json_encode($cost, JSON_UNESCAPED_UNICODE), date('Y-m-d', strtotime('+' . $pricing['quote_validity_days'] . ' days')), $now, $this->user]
        );
        $qid = (int) ($this->db->query('SELECT LAST_INSERT_ID() AS id')->getRowArray()['id'] ?? 0);
        // schema 34: the version starts quoted for the party size (never above the matrix's top row)
        $this->initMaxPax($qid, $cost);
        return ['ok' => true, 'blockers' => [], 'warnings' => $cost['warnings'], 'quote' => $this->quote($qid), 'cost' => $cost];
    }

    /** Mark a draft as sent to the agent. Earlier issued versions become history; the inquiry is quoted. */
    public function issue(int $quoteId, ?string $note = null): array
    {
        $q = $this->db->query('SELECT * FROM tf_quote WHERE id = ?', [$quoteId])->getRowArray();
        if (!$q) {
            throw new \RuntimeException('No such quotation.');
        }
        if ($q['status'] !== 'draft') {
            throw new \RuntimeException('Only a draft can be issued; this version is ' . $q['status'] . '.');
        }
        $this->take->assertOpen((int) $q['intake_id']);
        $now = date('Y-m-d H:i:s');
        $this->db->query("UPDATE tf_quote SET status = 'superseded' WHERE intake_id = ? AND status = 'issued'", [(int) $q['intake_id']]);
        // tf-comms-r3: a version the system drafted from a request e-mail is reviewed and approved by the person who issues it
        $this->db->query("UPDATE tf_quote SET status = 'issued', issued_at = ?, issued_by = ?, note = COALESCE(?, IF(review_required = 1, CONCAT('Reviewed and issued by ', ?), note)), review_required = 0 WHERE id = ?", [$now, $this->user, $note !== null ? mb_substr(trim($note), 0, 255) : null, $this->user, $quoteId]);
        $this->take->setStatus((int) $q['intake_id'], 'quoted');
        return $this->quote($quoteId);
    }

    /** Throw a draft away. An issued version is history and stays. */
    public function discard(int $quoteId): void
    {
        $q = $this->db->query('SELECT intake_id, status FROM tf_quote WHERE id = ?', [$quoteId])->getRowArray();
        if (!$q) {
            return;
        }
        if ($q['status'] !== 'draft') {
            throw new \RuntimeException('An issued quotation is not deleted; a newer version supersedes it.');
        }
        $this->db->query('DELETE FROM tf_quote WHERE id = ?', [$quoteId]);
    }

    public function quote(int $quoteId): ?array
    {
        $q = $this->db->query('SELECT * FROM tf_quote WHERE id = ?', [$quoteId])->getRowArray();
        if (!$q) {
            return null;
        }
        $q['cost'] = json_decode((string) $q['cost_json'], true) ?: [];
        unset($q['cost_json']);
        return $q;
    }

    /** @return array<int, array<string, mixed>> the versions, newest first, without the frozen costing */
    public function quotes(int $intakeId): array
    {
        $rows = $this->db->query('SELECT id, intake_id, version, status, currency, usd_rate, rate_date, pax, cost_usd, cost_lkr, markup_pct, sell_usd, per_person_usd, valid_until, note, issued_at, issued_by, created_at, created_by' . ($this->hasMaxPax() ? ', max_pax' : '') . '
                                 FROM tf_quote WHERE intake_id = ? ORDER BY version DESC', [$intakeId])->getResultArray();
        if ($this->hasEmails()) {
            foreach ($rows as &$r) {
                $r['emailed'] = $this->lastEmail((int) $r['id']);
            }
            unset($r);
        }
        return $rows;
    }

    /** The current version to show: the draft if there is one, else the issued one. */
    public function current(int $intakeId): ?array
    {
        $q = $this->db->query("SELECT id FROM tf_quote WHERE intake_id = ? AND status IN ('draft','issued') ORDER BY FIELD(status, 'draft', 'issued'), version DESC LIMIT 1", [$intakeId])->getRowArray();
        return $q ? $this->quote((int) $q['id']) : null;
    }

    // =====================================================================
    //  The maximum number of travellers a version is quoted for (schema 34)
    // =====================================================================

    private ?bool $maxPaxColumn = null;
    private ?bool $emailTable   = null;

    private ?bool $markupNoteColumn = null;

    /** schema 35: the pricing note's own column (an install not yet upgraded has none; the note is then not kept) */
    private function hasMarkupNote(): bool
    {
        if ($this->markupNoteColumn === null) {
            try {
                $this->markupNoteColumn = (bool) $this->db->query("SHOW COLUMNS FROM tf_intake LIKE 'markup_note'")->getResultArray();
            } catch (\Throwable $e) {
                $this->markupNoteColumn = false;
            }
        }
        return $this->markupNoteColumn;
    }

    private function hasMaxPax(): bool
    {
        if ($this->maxPaxColumn === null) {
            try {
                $this->maxPaxColumn = (bool) $this->db->query("SHOW COLUMNS FROM tf_quote LIKE 'max_pax'")->getResultArray();
            } catch (\Throwable $e) {
                $this->maxPaxColumn = false;
            }
        }
        return $this->maxPaxColumn;
    }

    private function hasEmails(): bool
    {
        if ($this->emailTable === null) {
            try {
                $this->emailTable = (bool) $this->db->query("SHOW TABLES LIKE 'tf_quote_email'")->getResultArray();
            } catch (\Throwable $e) {
                $this->emailTable = false;
            }
        }
        return $this->emailTable;
    }

    /** The party sizes the version's price table carries, smallest first ([] when it has no table). */
    public static function paxLevels(array $cost): array
    {
        $out = [];
        foreach (PaxMatrix::forAgent($cost['pax_matrix'] ?? null) as $r) {
            $out[] = (int) $r['pax'];
        }
        sort($out);
        return $out;
    }

    private function initMaxPax(int $quoteId, array $cost): void
    {
        if (!$this->hasMaxPax()) {
            return;
        }
        $levels = self::paxLevels($cost);
        if ($levels === []) {
            return;   // no price table on this version: nothing to limit
        }
        $pax = (int) ($cost['pax'] ?? 0);
        $max = $pax > 0 ? min($pax, max($levels)) : max($levels);
        if (!in_array($max, $levels, true)) {
            // the party size is not a row of its own (a size the matrix skips): the nearest row above it, else the top
            $above = array_values(array_filter($levels, static fn ($l) => $l >= $max));
            $max   = $above ? $above[0] : max($levels);
        }
        $this->db->query('UPDATE tf_quote SET max_pax = ? WHERE id = ?', [$max, $quoteId]);
    }

    /**
     * Set the maximum number of travellers a version is quoted for: one of the rows of its price table.
     * Allowed on a draft or an issued version until the version has been emailed — the email carries the
     * range that went, so after that a new version is the way to quote another range.
     */
    public function setMaxPax(int $quoteId, $max): array
    {
        if (!$this->hasMaxPax()) {
            throw new \RuntimeException('This install has no maximum-travellers column yet: press Install now on Tour Inquiry first.');
        }
        $q = $this->quote($quoteId);
        if (!$q) {
            throw new \RuntimeException('No such quotation.');
        }
        $this->take->assertOpen((int) $q['intake_id']);
        if ($q['status'] === 'superseded') {
            throw new \RuntimeException('This version is history; the maximum is set on the current version.');
        }
        $levels = self::paxLevels($q['cost']);
        if ($levels === []) {
            throw new \RuntimeException('This version has no price-per-person table, so there is no range to limit.');
        }
        if (!is_numeric($max) || (float) $max !== (float) (int) $max || !in_array((int) $max, $levels, true)) {
            throw new \InvalidArgumentException('The maximum number of travellers is one of the sizes on the price table: ' . self::sizes($levels) . '.');
        }
        $sent = $this->lastEmail($quoteId, 'sent');
        if ($sent !== null && (int) ($q['max_pax'] ?? null) !== (int) $max) {
            throw new \RuntimeException('Version ' . $q['version'] . ' was emailed on ' . substr((string) $sent['sent_at'], 0, 16) . ' for up to ' . (int) ($q['max_pax'] ?? null) . ' travellers; that range is what the agent has. Make a new version to quote another range.');
        }
        $this->db->query('UPDATE tf_quote SET max_pax = ? WHERE id = ?', [(int) $max, $quoteId]);
        return $this->quote($quoteId);
    }

    /** The client view limited to the version's maximum: the price rows up to it, no further. */
    public static function clientViewFor(array $cost, ?int $maxPax): array
    {
        $view = self::clientView($cost);
        $view['pax_prices_all'] = $view['pax_prices'];
        if ($maxPax !== null && $maxPax > 0 && !empty($view['pax_prices'])) {
            $view['pax_prices'] = array_values(array_filter($view['pax_prices'], static fn ($r) => (int) $r['pax'] <= $maxPax));
        }
        $view['max_pax'] = $maxPax;
        return $view;
    }

    // =====================================================================
    //  The quotation emailed to the agent (schema 34)
    // =====================================================================

    /**
     * The email as it starts for one version: To (the agent's address on the inquiry), CC (empty), the subject
     * and the message from the templates with the inquiry's own words filled in. Staff edit it before sending.
     *
     * @param array{name:string, email:?string, can_send:bool} $sender  Staff::contact
     */
    public function emailDraft(int $quoteId, array $sender, array $company, ?int $templateId = null): array
    {
        $q = $this->quote($quoteId);
        if (!$q) {
            throw new \RuntimeException('No such quotation.');
        }
        $it   = $this->db->query('SELECT agent_email, agent_name, tour_name, inquiry_no, arrival_date, departure_date, nights, pax_total FROM tf_intake WHERE id = ?', [(int) $q['intake_id']])->getRowArray() ?: [];
        $cost = $q['cost'];
        $fmtD = static fn ($d) => $d ? date('j M Y', strtotime((string) $d)) : '';
        $vars = [
            'agent'       => trim((string) ($cost['agent_name'] ?? $it['agent_name'] ?? '')) ?: 'Sir or Madam',
            'agent_name'  => trim((string) ($cost['agent_name'] ?? $it['agent_name'] ?? '')),
            'ref'         => ($cost['inquiry_no'] ?? $it['inquiry_no'] ?? '') . ' / v' . (int) $q['version'],
            'inquiry_no'  => (string) ($cost['inquiry_no'] ?? $it['inquiry_no'] ?? ''),
            'version'     => (string) (int) $q['version'],
            'tour_name'   => trim((string) ($cost['tour_name'] ?? $it['tour_name'] ?? '')) ?: 'your tour',
            'dates'       => ($cost['arrival'] ?? null) && ($cost['departure'] ?? null) ? $fmtD($cost['arrival']) . ' to ' . $fmtD($cost['departure']) : (string) ($cost['tour_dates'] ?? ''),
            'arrival'     => $fmtD($cost['arrival'] ?? $it['arrival_date'] ?? null),
            'departure'   => $fmtD($cost['departure'] ?? $it['departure_date'] ?? null),
            'nights'      => (string) (int) ($cost['nights'] ?? $it['nights'] ?? 0),
            'pax'         => (string) (int) ($cost['pax'] ?? $it['pax_total'] ?? 0),
            'max_pax'     => ($q['max_pax'] ?? null) !== null ? (string) (int) ($q['max_pax'] ?? null) : (string) (int) ($cost['pax'] ?? 0),
            'price'       => $q['sell_usd'] !== null ? 'USD ' . number_format((float) $q['sell_usd'], 2) : '',
            'per_person'  => $q['per_person_usd'] !== null ? 'USD ' . number_format((float) $q['per_person_usd'], 2) : '',
            'valid_until' => $fmtD($q['valid_until'] ?? null),
            'me'          => (string) ($sender['name'] ?? ''),
            'company'     => (string) ($company['name'] ?? 'Avenir Holidays'),
        ];
        $vars['today'] = date('j M Y');
        // the ready-made emails this window offers (schema 34): the quotation ones and the general ones, filled in
        $tpls  = new EmailTemplates($this->db);
        $list  = EmailTemplates::filled($tpls->forWindow('quotation'), $vars);
        $start = null;
        foreach ($list as $x) {
            if ($templateId !== null && $x['id'] === $templateId) {
                $start = $x;
            }
        }
        if ($start === null) {
            $start = EmailTemplates::starting($list, 'quotation');
        }
        if ($start === null) {
            // no template on this install yet: the standard quotation text
            [$n0, $c0, $d0, $s0, $m0] = EmailTemplates::SEED[0];
            $start = ['id' => 0, 'name' => $n0, 'category' => $c0, 'subject' => trim(EmailTemplates::fill($s0, $vars)), 'message' => EmailTemplates::fill($m0, $vars)];
        }
        $to   = trim((string) ($it['agent_email'] ?? ''));
        return [
            'to'       => filter_var($to, FILTER_VALIDATE_EMAIL) ? $to : '',
            'to_note'  => $to === '' ? 'The inquiry has no agent email address; type one, or add it on the inquiry.' : (filter_var($to, FILTER_VALIDATE_EMAIL) ? '' : 'The address on the inquiry (' . $to . ') does not look like an email address.'),
            'cc'       => '',
            'template_id' => (int) $start['id'],
            'templates'   => $list,
            'subject'  => (string) $start['subject'],
            'message'  => (string) $start['message'],
            'from'     => $sender['email'] ?? null,
            'can_send' => (bool) ($sender['can_send'] ?? false),
            'max_pax'  => ($q['max_pax'] ?? null) !== null ? (int) ($q['max_pax'] ?? null) : null,
            'levels'   => self::paxLevels($cost),
            'pdf_name' => self::pdfName($cost['inquiry_no'] ?? null, (int) $q['version']),
            'history'  => $this->emails($quoteId),
            'locked'   => $this->lastEmail($quoteId, 'sent') !== null,
        ];
    }

    public static function pdfName($inquiryNo, int $version): string
    {
        return 'Quotation-' . preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($inquiryNo ?: 'draft')) . '-v' . $version . '.pdf';
    }

    /** @return array<int, array<string, mixed>> every send and failure of a version, newest first */
    public function emails(int $quoteId): array
    {
        if (!$this->hasEmails()) {
            return [];
        }
        return $this->db->query('SELECT id, to_email, cc_email, subject, pdf_name, max_pax, status, error, sent_by, sent_name, sent_from, sent_at FROM tf_quote_email WHERE quote_id = ? ORDER BY id DESC', [$quoteId])->getResultArray();
    }

    public function lastEmail(int $quoteId, ?string $status = null): ?array
    {
        if (!$this->hasEmails()) {
            return null;
        }
        return $this->db->query('SELECT id, to_email, cc_email, subject, pdf_name, max_pax, status, error, sent_by, sent_name, sent_from, sent_at FROM tf_quote_email WHERE quote_id = ?' . ($status ? ' AND status = ?' : '') . ' ORDER BY id DESC LIMIT 1', $status ? [$quoteId, $status] : [$quoteId])->getRowArray() ?: null;
    }

    /**
     * One send, or one failure, kept whole. On a send, a draft version is issued at the same time ("Emailed to …"),
     * so Confirm Tour can follow; a version already issued is left as it is.
     */
    public function recordEmail(int $quoteId, array $mail, string $status, ?string $error, string $byUser, ?int $byId, string $byName, ?string $from): array
    {
        $q = $this->quote($quoteId);
        if (!$q) {
            throw new \RuntimeException('No such quotation.');
        }
        $now = date('Y-m-d H:i:s');
        $this->db->query('INSERT INTO tf_quote_email (intake_id, quote_id, version, to_email, cc_email, subject, message, pdf_name, max_pax, status, error, sent_by, sent_by_id, sent_name, sent_from, sent_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            (int) $q['intake_id'], $quoteId, (int) $q['version'], mb_substr((string) $mail['to'], 0, 190), mb_substr((string) ($mail['cc'] ?? ''), 0, 500) ?: null, mb_substr((string) $mail['subject'], 0, 255), (string) ($mail['message'] ?? ''),
            mb_substr((string) ($mail['pdf_name'] ?? ''), 0, 120) ?: null, ($q['max_pax'] ?? null) !== null ? (int) ($q['max_pax'] ?? null) : null, $status === 'sent' ? 'sent' : 'failed', $error !== null ? mb_substr($error, 0, 500) : null,
            mb_substr($byUser, 0, 100), $byId, mb_substr($byName, 0, 190), $from !== null ? mb_substr($from, 0, 190) : null, $now,
        ]);
        $id = (int) $this->db->insertID();
        if ($status === 'sent' && $q['status'] === 'draft') {
            $this->issue($quoteId, 'Emailed to ' . $mail['to'] . ' on ' . substr($now, 0, 16) . ' by ' . $byName);
        }
        return ['id' => $id, 'quote' => $this->quote($quoteId)];
    }
}
