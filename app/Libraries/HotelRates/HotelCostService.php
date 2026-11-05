<?php

namespace App\Libraries\HotelRates;

/**
 * What a tour PAYS each hotel — the handoff from the Hotel Rates panel to the
 * Tour Payment Dashboard.
 *
 * For every hotel night of a tour (one tour_quotations row) the payable rate is
 * the rule from before tf-offers-r1 with the tacticals and early birds taken out of
 * it: an ad hoc payment-only rate when one covers the night, otherwise the published
 * / contract rate — exactly what a quotation is built on, which is always stored
 * beside it. Once Install now has reached schema 42, OfferRates lists the tactical
 * rates and early bird offers that fit the stay: the stay is flagged for a person
 * holding "Tactical / Early Bird rate selection", and the payment changes only when
 * that person applies one (or keeps the published rate) — nothing is applied by
 * itself. Before schema 42 the earlier rule stands unchanged.
 * When the panel has no rate at all for the night, the quoted rate is used and the
 * row is flagged ("check rate before payment"). The customer's quotation
 * (tour_quotations.sgl/dbl/tpl) is never touched: quoted − payable is the margin.
 *
 * Rows live in hr2_tour_hotel_cost (one per tour_quotations row). They are
 * written when the tour is confirmed (invoice generated), rebuilt when a plan
 * of one of its hotels is approved or superseded, on demand from the dashboard,
 * and lazily when the dashboard finds none for a confirmed tour.
 */
final class HotelCostService
{
    private $db;
    private string $user;
    private HotelRateService $svc;

    public function __construct($db, string $user = 'system', ?HotelRateService $svc = null)
    {
        $this->db = $db;
        $this->user = $user ?: 'system';
        $this->svc = $svc ?? new HotelRateService($db, $this->user);
    }

    public const NOTE_NO_RATE = 'No ad hoc or special rate found and no contract rate for this night — the quoted rate is used. Check the rate before payment.';
    public const NOTE_NO_HOTEL = 'Hotel is not in the Hotel Rates panel — the quoted rate is used. Check the rate before payment.';
    public const NOTE_NO_QUOTE = 'No rate at all: neither the panel nor the quotation prices this night.';

    /** Confirmed = the customer invoice was generated (consignee.verify / tour_quotations.confirm). */
    public function isConfirmed(string $tourId): bool
    {
        $c = $this->row('SELECT verify, tour_cancel FROM consignee WHERE tour_id = ?', [$tourId]);
        if ($c && (int) ($c['tour_cancel'] ?? 0) === 1) {
            return false;
        }
        if ($c && (int) ($c['verify'] ?? 0) === 1) {
            return true;
        }
        $q = $this->row('SELECT MAX(confirm) AS c FROM tour_quotations WHERE quotation_id = ?', [$tourId]);
        return (int) ($q['c'] ?? 0) === 1;
    }

    /**
     * Price every hotel night of the tour and store the rows. Returns the per-hotel summary.
     * $opts: booking_date (defaults to the tour's creation date), force (ignore confirmation)
     */
    public function computeTour(string $tourId, array $opts = []): array
    {
        $tour = $this->row('SELECT tour_id, manual_id, country, sgl, dbl, tpl, create_time, verify, tour_cancel FROM consignee WHERE tour_id = ?', [$tourId]);
        $rows = $this->rows("SELECT id, tour_date, hotel_acc, hotel_name, room_type, meal_code, sgl, dbl, tpl, sglcount, dblcount, tplcount, confirm FROM tour_quotations WHERE quotation_id = ? AND hotel_acc <> '' AND confirm <> 3 ORDER BY tour_date, id", [$tourId]);
        $now = date('Y-m-d H:i:s');
        $bookingDate = $opts['booking_date'] ?? ($tour && !empty($tour['create_time']) ? substr((string) $tour['create_time'], 0, 10) : date('Y-m-d'));
        $country = $tour ? strtoupper(trim((string) $tour['country'])) : '';
        $engine = $this->svc->lookupEngine();
        $market = $engine->resolveMarket($country !== '' ? ['country' => $country] : []);
        $out = [];
        // tf-offers-r1: published beside the rate used, tactical / early bird offers, choices and their log
        $offers = new OfferRates($this->db, $this->svc);
        $useOffers = $offers->ready();
        $stayLen = $useOffers ? $this->stayLengths($rows) : [];
        $choices = $useOffers ? $offers->choicesForTour($tourId) : [];
        $before = $useOffers ? $this->usedByHotel($tourId) : [];
        $this->db->transStart();
        $this->db->query('DELETE FROM hr2_tour_hotel_cost WHERE tour_id = ?', [$tourId]);
        foreach ($rows as $q) {
            $acc = trim((string) $q['hotel_acc']);
            $sglC = (int) $q['sglcount']; $dblC = (int) $q['dblcount']; $tplC = (int) $q['tplcount'];
            if ($sglC + $dblC + $tplC === 0 && $tour) {
                $sglC = (int) $tour['sgl']; $dblC = (int) $tour['dbl']; $tplC = (int) $tour['tpl'];
            }
            $roomsBooked = $sglC + $dblC + $tplC;
            $adults = $sglC + 2 * $dblC + 3 * $tplC;
            $qs = $this->num($q['sgl']); $qd = $this->num($q['dbl']); $qt = $this->num($q['tpl']);
            $quoteTotal = round(($qs ?? 0) * $sglC + ($qd ?? 0) * $dblC + ($qt ?? 0) * $tplC, 2);
            $meal = strtoupper(trim((string) $q['meal_code']));
            $hotel = $acc !== '' ? $this->svc->findHotelByAcc($acc) : null;
            $rec = ['tour_id' => $tourId, 'manual_id' => $tour['manual_id'] ?? null, 'quotation_row_id' => (int) $q['id'], 'hotel_acc' => $acc, 'hotel_id' => $hotel ? (int) $hotel['id'] : null,
                'tour_date' => $q['tour_date'], 'room_type_legacy' => ctype_digit((string) $q['room_type']) ? (int) $q['room_type'] : null, 'room_type_name' => null, 'meal_plan' => in_array($meal, Sheet::MEAL_PLANS, true) ? $meal : null, 'market' => $market,
                'sgl_count' => $sglC, 'dbl_count' => $dblC, 'tpl_count' => $tplC, 'quote_sgl' => $qs, 'quote_dbl' => $qd, 'quote_tpl' => $qt,
                'pay_sgl' => null, 'pay_dbl' => null, 'pay_tpl' => null, 'pay_supplements' => 0.0, 'quote_total' => $quoteTotal, 'pay_total' => 0.0,
                'source' => 'none', 'plan_id' => null, 'plan_name' => null, 'season_name' => null, 'derived' => null, 'flags_json' => null, 'check_note' => null];
            if ($useOffers) {
                $rec += ['pub_sgl' => null, 'pub_dbl' => null, 'pub_tpl' => null, 'pub_supplements' => null, 'pub_total' => null, 'pub_plan_id' => null, 'pub_plan_name' => null,
                    'basis' => null, 'offer_plan_id' => null, 'offer_kind' => null, 'offer_name' => null, 'options_json' => null, 'choice_needed' => 0];
            }
            $r = null; $rq = null; $night = null; $roomRef = null; $lookupOpts = [];
            $priceable = $hotel && $rec['meal_plan'] && $q['room_type'] !== '' && $q['room_type'] !== '0';
            if ($priceable) {
                $roomRef = ctype_digit((string) $q['room_type']) ? (int) $q['room_type'] : (string) $q['room_type'];
                $lookupOpts = ['market' => $market, 'booking_date' => $bookingDate, 'rooms' => max(1, $roomsBooked), 'pax' => max(1, $adults)];
            }
            if ($useOffers && $priceable) {
                $night = $offers->night((int) $hotel['id'], $q['tour_date'], $roomRef, $rec['meal_plan'], $lookupOpts, $stayLen[(int) $q['id']] ?? 1, $choices[$acc] ?? null);
                $pub = $night['published'];
                $r = $night['res'];
                $rq = $pub;
                if ($r && $pub && ($night['used'] || ($r['plan']['applies_to'] ?? 'both') === 'payment')) {
                    // an offer or an ad hoc rate replaces the room rate: the night's compulsory supplements (gala
                    // dinners, peak …) and rules still come from the published plan unless it lists its own
                    if (!$r['supplements']) {
                        $r['supplements'] = $pub['supplements'];
                    }
                    if (!$r['restrictions']) {
                        $r['restrictions'] = $pub['restrictions'];
                    }
                }
                $counts = [$sglC, $dblC, $tplC];
                if ($pub) {
                    [$ps, $pd, $pt] = $this->roomRates($pub);
                    $rec['pub_sgl'] = $ps; $rec['pub_dbl'] = $pd; $rec['pub_tpl'] = $pt;
                    $rec['pub_supplements'] = $this->compulsorySupplements($pub, $adults, max(1, $roomsBooked), (float) ($pd ?? $ps ?? 0));
                    $rec['pub_total'] = $this->nightTotal([$ps, $pd, $pt], $counts, [$qs, $qd, $qt], (float) $rec['pub_supplements']);
                    $rec['pub_plan_id'] = (int) $pub['plan']['id']; $rec['pub_plan_name'] = $pub['plan']['name'];
                }
                $optList = [];
                foreach ($night['options'] as $o) {
                    $or = $o['res'];
                    if ($pub && !$or['supplements']) {
                        $or['supplements'] = $pub['supplements'];
                    }
                    [$os, $od, $ot] = $this->roomRates($or);
                    $sup = $this->compulsorySupplements($or, $adults, max(1, $roomsBooked), (float) ($od ?? $os ?? 0));
                    $optList[] = ['plan_id' => $o['plan_id'], 'kind' => $o['kind'], 'name' => $o['name'], 'rate_code' => $o['rate_code'], 'ok' => $o['ok'], 'why' => $o['why'],
                        'proposed' => $night['proposed'] === $o['plan_id'], 'book_to' => $o['book_to'], 'rates' => [$os, $od, $ot], 'supplements' => $sup, 'total' => $this->nightTotal([$os, $od, $ot], $counts, [$qs, $qd, $qt], $sup)];
                }
                $rec['options_json'] = $optList ? json_encode($optList, JSON_UNESCAPED_UNICODE) : null;
                $rec['basis'] = $night['basis'];
                // choice_needed: special rates fit and no authorized person has decided yet (the published rate is paid)
                $rec['choice_needed'] = $night['basis'] === 'pending' ? 1 : 0;
                if ($night['used']) {
                    $rec['offer_plan_id'] = $night['used']['plan_id']; $rec['offer_kind'] = $night['used']['kind']; $rec['offer_name'] = $night['used']['name'];
                }
            } elseif ($priceable) {
                $r = $engine->price((int) $hotel['id'], $q['tour_date'], $roomRef, $rec['meal_plan'], $lookupOpts + ['purpose' => 'payment']);
                if ($r && ($r['plan']['applies_to'] ?? 'both') === 'payment') {
                    // an ad hoc rate replaces the room rate only: the night's compulsory supplements
                    // (gala dinners, peak …) and rules still come from the contract unless the ad hoc plan lists its own
                    $rq = $engine->price((int) $hotel['id'], $q['tour_date'], $roomRef, $rec['meal_plan'], $lookupOpts);
                    if (!$r['supplements'] && $rq) {
                        $r['supplements'] = $rq['supplements'];
                    }
                    if (!$r['restrictions'] && $rq) {
                        $r['restrictions'] = $rq['restrictions'];
                    }
                }
            }
            if ($r) {
                $mult = $r['pricing_mode'] === 'per_person';
                if ($r['pricing_mode'] === 'per_unit') {
                    $rec['pay_sgl'] = $r['unit']; $rec['pay_dbl'] = $r['unit']; $rec['pay_tpl'] = $r['unit'];
                } else {
                    $rec['pay_sgl'] = $r['sgl'] !== null ? round($r['sgl'], 2) : null;
                    $rec['pay_dbl'] = $r['dbl'] !== null ? round($r['dbl'] * ($mult ? 2 : 1), 2) : null;
                    $rec['pay_tpl'] = $r['tpl'] !== null ? round($r['tpl'] * ($mult ? 3 : 1), 2) : null;
                }
                // a booked occupancy the plan does not price falls back to the quoted figure for that occupancy
                foreach ([['sgl', $sglC, $qs], ['dbl', $dblC, $qd], ['tpl', $tplC, $qt]] as [$occ, $count, $quoted]) {
                    if ($count > 0 && $rec['pay_' . $occ] === null) {
                        $rec['pay_' . $occ] = $quoted;
                        $rec['check_note'] = 'The plan prices no ' . strtoupper($occ) . ' for this room type — quoted figure used for that occupancy. Check before payment.';
                    }
                }
                $rec['pay_supplements'] = $this->compulsorySupplements($r, $adults, max(1, $roomsBooked), (float) ($rec['pay_dbl'] ?? $rec['pay_sgl'] ?? 0));
                $rec['pay_total'] = round(($rec['pay_sgl'] ?? 0) * $sglC + ($rec['pay_dbl'] ?? 0) * $dblC + ($rec['pay_tpl'] ?? 0) * $tplC + $rec['pay_supplements'], 2);
                $rec['source'] = ($r['plan']['applies_to'] ?? 'both') === 'payment' && !($useOffers && $night && $night['used']) ? 'adhoc' : 'contracted';
                $rec['plan_id'] = (int) $r['plan']['id']; $rec['plan_name'] = $r['plan']['name']; $rec['season_name'] = $r['season']['name']; $rec['derived'] = $r['derived'];
                $rec['room_type_name'] = $r['room_type']['name'];
                $flags = $rq ? $this->svc->lookupFlags($rq, $q['tour_date']) : $this->svc->lookupFlags($r, $q['tour_date']);
                if ($useOffers && $night && $night['used']) {
                    $kindName = OfferRates::KINDS[$night['used']['kind']] ?? 'Offer';
                    $flags[] = ['kind' => 'offer', 'level' => 'green', 'name' => $kindName . ' used for the payment', 'detail' => 'hotel is paid ' . number_format((float) ($rec['pay_dbl'] ?? $rec['pay_sgl'] ?? 0), 2) . ($rec['pub_dbl'] !== null ? ' instead of the published ' . number_format((float) $rec['pub_dbl'], 2) : '') . ' · ' . $night['used']['name'], 'amount' => $rec['pay_dbl'], 'per' => null, 'meal_plans' => $rec['meal_plan'], 'compulsory' => null, 'key' => 'offer'];
                } elseif ($rec['source'] === 'adhoc') {
                    $quoteDbl = $rq['dbl'] ?? null;
                    $flags[] = ['kind' => 'adhoc', 'level' => 'green', 'name' => 'Ad hoc payment rate', 'detail' => 'hotel is paid ' . number_format((float) ($rec['pay_dbl'] ?? $rec['pay_sgl'] ?? 0), 2) . ($quoteDbl !== null ? ' instead of the contract ' . number_format((float) $quoteDbl, 2) : '') . ' · ' . $r['plan']['name'], 'amount' => $rec['pay_dbl'], 'per' => null, 'meal_plans' => $rec['meal_plan'], 'compulsory' => null, 'key' => 'adhoc'];
                }
                $rec['flags_json'] = json_encode($flags, JSON_UNESCAPED_UNICODE);
            } else {
                $rec['pay_sgl'] = $qs; $rec['pay_dbl'] = $qd; $rec['pay_tpl'] = $qt;
                $rec['pay_total'] = $quoteTotal;
                $rec['source'] = $quoteTotal > 0 ? 'quoted' : 'none';
                $rec['check_note'] = !$hotel ? self::NOTE_NO_HOTEL : ($quoteTotal > 0 ? self::NOTE_NO_RATE : self::NOTE_NO_QUOTE);
                if ($hotel && $q['room_type'] !== '' && ctype_digit((string) $q['room_type'])) {
                    $lr = $this->row('SELECT rtype FROM room_type WHERE id = ?', [(int) $q['room_type']]);
                    $rec['room_type_name'] = $lr['rtype'] ?? null;
                }
                $rec['flags_json'] = json_encode($this->svc->lookupFlags(null, $q['tour_date']), JSON_UNESCAPED_UNICODE);
            }
            if ($useOffers && $rec['basis'] === null) {
                $rec['basis'] = $rec['source'] === 'quoted' ? 'quoted' : ($rec['source'] === 'none' ? 'none' : 'published');
            }
            $rec['computed_at'] = $now; $rec['computed_by'] = $this->user;
            if ($useOffers) {
                // tf-offers-r1: an offer's name or note never stops the costing for being long (the columns' own sizes)
                foreach (['check_note' => 300, 'plan_name' => 160, 'pub_plan_name' => 160, 'offer_name' => 255, 'season_name' => 120] as $c => $len) {
                    if (isset($rec[$c]) && is_string($rec[$c]) && mb_strlen($rec[$c]) > $len) {
                        $rec[$c] = mb_substr($rec[$c], 0, $len - 1) . '…';
                    }
                }
            }
            $cols = array_keys($rec);
            $this->db->query('INSERT INTO hr2_tour_hotel_cost (`' . implode('`, `', $cols) . '`) VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($rec));
            $out[] = $rec;
        }
        if ($useOffers) {
            $this->logOfferChanges($offers, $tourId, $tour['manual_id'] ?? null, $before, $out, $opts, $now);
        }
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('Storing the hotel costs failed; nothing was written.');
        }
        // what this tour is still missing (no contract rate, unusual margin) goes to Hotel Incomplete Work
        if ($opts['scan'] ?? true) {
            try {
                (new WorkQueueService($this->db, $this->user, $this->svc))->scan(['tour_id' => $tourId]);
            } catch (\Throwable $e) {
                // the work queue never blocks costing
            }
        }
        return $this->summarise($out, $tourId);
    }

    /** Compulsory supplements of one night for the booked pax / rooms (gala dinners, peak, festival …). */
    public function compulsorySupplements(array $r, int $adults, int $rooms, float $roomRate): float
    {
        $total = 0.0;
        foreach ($r['supplements'] as $s) {
            if (!(int) $s['compulsory']) {
                continue;
            }
            $amt = $s['amount'] !== null ? (float) $s['amount'] : (float) ($s['amount_dbl'] ?? $s['amount_sgl'] ?? 0);
            if ($s['pct_of_rate'] !== null && $roomRate > 0) {
                $amt = round($roomRate * (float) $s['pct_of_rate'] / 100, 2);
            }
            $total += in_array($s['per'], ['person', 'adult'], true) ? $amt * $adults : (in_array($s['per'], ['room', 'night'], true) ? $amt * $rooms : $amt);
        }
        return round($total, 2);
    }

    /** Stored rows of a tour, grouped per hotel with totals, sources and check notes. */
    public function summary(string $tourId): array
    {
        $rows = $this->rows('SELECT * FROM hr2_tour_hotel_cost WHERE tour_id = ? ORDER BY tour_date, id', [$tourId]);
        return $this->summarise($rows, $tourId);
    }

    /** Compute when the tour is confirmed and nothing is stored yet (or a plan changed since); returns the summary. */
    public function ensureTour(string $tourId, bool $force = false): array
    {
        $have = (int) ($this->row('SELECT COUNT(*) AS n, MIN(computed_at) AS at FROM hr2_tour_hotel_cost WHERE tour_id = ?', [$tourId])['n'] ?? 0);
        if ($force || (!$have && $this->isConfirmed($tourId)) || ($have && $this->isStale($tourId))) {
            return $this->computeTour($tourId);
        }
        return $this->summary($tourId);
    }

    /** True when a plan of one of the tour's hotels was approved / superseded after the rows were computed. */
    public function isStale(string $tourId): bool
    {
        $r = $this->row('SELECT MIN(c.computed_at) AS at FROM hr2_tour_hotel_cost c WHERE c.tour_id = ?', [$tourId]);
        if (!$r || !$r['at']) {
            return false;
        }
        $changed = $this->row("SELECT COUNT(*) AS n FROM hr2_contract p JOIN hr2_tour_hotel_cost c ON c.hotel_id = p.hotel_id AND c.tour_id = ?
                                WHERE p.status IN ('approved', 'superseded', 'cancelled') AND GREATEST(COALESCE(p.approved_at, '2000-01-01'), p.updated_at) > ?", [$tourId, $r['at']]);
        return (int) ($changed['n'] ?? 0) > 0;
    }

    /** Recompute the confirmed tours with nights at this hotel from today on (called when a plan is approved / superseded). */
    public function refreshHotel(int $hotelId): int
    {
        $accs = array_column($this->rows('SELECT sup_acc_no FROM hr2_hotel_account WHERE hotel_id = ?', [$hotelId]), 'sup_acc_no');
        if (!$accs) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($accs), '?'));
        $tours = $this->rows("SELECT DISTINCT tq.quotation_id FROM tour_quotations tq JOIN consignee c ON c.tour_id = tq.quotation_id
                              WHERE tq.hotel_acc IN ({$in}) AND tq.tour_date >= CURDATE() AND c.verify = 1 AND COALESCE(c.tour_cancel, 0) <> 1", $accs);
        $n = 0;
        foreach ($tours as $t) {
            try {
                $this->computeTour((string) $t['quotation_id']);
                $n++;
            } catch (\Throwable $e) {
                // one broken tour must not stop the others
            }
        }
        return $n;
    }

    private function summarise(array $rows, string $tourId): array
    {
        $hotels = [];
        foreach ($rows as $r) {
            $acc = $r['hotel_acc'];
            $h = &$hotels[$acc];
            if (!$h) {
                $h = ['hotel_acc' => $acc, 'hotel_id' => $r['hotel_id'], 'nights' => 0, 'quote_total' => 0.0, 'pay_total' => 0.0, 'supplements_total' => 0.0, 'margin' => 0.0, 'sources' => [], 'plans' => [], 'check_notes' => [], 'computed_at' => $r['computed_at'], 'rows' => []];
            }
            $h['nights']++;
            $h['quote_total'] = round($h['quote_total'] + (float) $r['quote_total'], 2);
            $h['pay_total'] = round($h['pay_total'] + (float) $r['pay_total'], 2);
            $h['supplements_total'] = round($h['supplements_total'] + (float) $r['pay_supplements'], 2);
            $h['sources'][$r['source']] = ($h['sources'][$r['source']] ?? 0) + 1;
            if ($r['plan_name'] && !in_array($r['plan_name'], $h['plans'], true)) {
                $h['plans'][] = $r['plan_name'];
            }
            if ($r['check_note'] && !in_array($r['check_note'], $h['check_notes'], true)) {
                $h['check_notes'][] = $r['check_note'];
            }
            $h['rows'][] = ['date' => $r['tour_date'], 'room_type' => $r['room_type_name'], 'meal_plan' => $r['meal_plan'], 'counts' => [(int) $r['sgl_count'], (int) $r['dbl_count'], (int) $r['tpl_count']],
                'quote' => [$this->num($r['quote_sgl']), $this->num($r['quote_dbl']), $this->num($r['quote_tpl'])], 'pay' => [$this->num($r['pay_sgl']), $this->num($r['pay_dbl']), $this->num($r['pay_tpl'])],
                'supplements' => (float) $r['pay_supplements'], 'quote_total' => (float) $r['quote_total'], 'pay_total' => (float) $r['pay_total'], 'source' => $r['source'], 'plan' => $r['plan_name'], 'season' => $r['season_name'], 'check_note' => $r['check_note'],
                'flags' => $r['flags_json'] ? (json_decode((string) $r['flags_json'], true) ?: []) : [],
                // tf-offers-r1: the published rate beside the rate used, the offer used and the offers that fit
                'basis' => $r['basis'] ?? null, 'pub' => [$this->num($r['pub_sgl'] ?? null), $this->num($r['pub_dbl'] ?? null), $this->num($r['pub_tpl'] ?? null)],
                'pub_total' => isset($r['pub_total']) && $r['pub_total'] !== null ? (float) $r['pub_total'] : null, 'pub_plan' => $r['pub_plan_name'] ?? null,
                'offer' => !empty($r['offer_plan_id']) ? ['plan_id' => (int) $r['offer_plan_id'], 'kind' => $r['offer_kind'], 'name' => $r['offer_name']] : null,
                'options' => !empty($r['options_json']) ? (json_decode((string) $r['options_json'], true) ?: []) : [], 'choice_needed' => (int) ($r['choice_needed'] ?? 0)];
            unset($h);
        }
        foreach ($hotels as &$h) {
            $h['margin'] = round($h['quote_total'] - $h['pay_total'], 2);
            $src = array_keys($h['sources']);
            $h['source'] = count($src) === 1 ? $src[0] : (isset($h['sources']['quoted']) || isset($h['sources']['none']) ? 'mixed_check' : (isset($h['sources']['adhoc']) ? 'mixed_adhoc' : 'mixed'));
            $h['source_label'] = self::sourceLabel($h['source']);
            $h['needs_check'] = isset($h['sources']['quoted']) || isset($h['sources']['none']);
            $this->summariseOffers($h);
        }
        unset($h);
        // the active choice and the history of each stay (tf-offers-r1)
        $offers = new OfferRates($this->db, $this->svc);
        if ($rows && $offers->ready()) {
            $choices = $offers->choicesForTour($tourId);
            foreach ($hotels as $acc => &$h) {
                $c = $choices[$acc] ?? null;
                $h['choice'] = $c ? ['choice' => $c['choice'], 'contract_id' => $c['contract_id'] !== null ? (int) $c['contract_id'] : null, 'offer_name' => $c['offer_name'], 'offer_kind' => $c['offer_kind'], 'note' => $c['note'], 'chosen_by' => $c['chosen_by'], 'chosen_at' => $c['chosen_at']] : null;
                $h['history'] = array_map(static fn ($e) => $e + ['label' => OfferRates::eventLabel($e['event'])], $offers->history($tourId, (string) $acc, 15));
            }
            unset($h);
        }
        return ['tour_id' => $tourId, 'nights' => count($rows), 'hotels' => $hotels, 'computed' => (bool) $rows, 'offers_ready' => $offers->ready()];
    }

    /**
     * Per stay: the published total beside the total used, the saving, the offers used, every offer that fits with
     * what the stay would cost with it, and whether a person still has to choose (tf-offers-r1).
     */
    private function summariseOffers(array &$h): void
    {
        $pubTotal = 0.0; $pubMissing = false; $used = []; $options = []; $choiceNeeded = 0; $bases = [];
        foreach ($h['rows'] as $n) {
            if ($n['basis'] === null) {
                $h['offers_ready'] = false;
                return;
            }
            $bases[$n['basis']] = ($bases[$n['basis']] ?? 0) + 1;
            if ($n['pub_total'] === null) {
                $pubMissing = true;
            } else {
                $pubTotal += $n['pub_total'];
            }
            if ($n['offer']) {
                $k = $n['offer']['plan_id'];
                $used[$k] = $used[$k] ?? $n['offer'] + ['nights' => 0, 'total' => 0.0];
                $used[$k]['nights']++;
                $used[$k]['total'] = round($used[$k]['total'] + $n['pay_total'], 2);
            }
            $choiceNeeded += $n['choice_needed'];
        }
        foreach ($h['rows'] as $n) {
            foreach ($n['options'] as $o) {
                $k = (int) $o['plan_id'];
                $options[$k] = $options[$k] ?? ['plan_id' => $k, 'kind' => $o['kind'], 'name' => $o['name'], 'rate_code' => $o['rate_code'] ?? null, 'book_to' => $o['book_to'] ?? null, 'nights_fit' => 0, 'nights_listed' => 0, 'why' => null, 'total_if_used' => 0.0];
            }
        }
        foreach ($options as $k => &$opt) {
            foreach ($h['rows'] as $n) {
                $mine = null;
                foreach ($n['options'] as $o) {
                    if ((int) $o['plan_id'] === $k) {
                        $mine = $o;
                    }
                }
                if ($mine) {
                    $opt['nights_listed']++;
                }
                if ($mine && $mine['ok']) {
                    $opt['nights_fit']++;
                    $opt['total_if_used'] += (float) $mine['total'];
                } else {
                    if ($mine && !$opt['why']) {
                        $opt['why'] = $mine['why'];
                    }
                    $opt['total_if_used'] += $n['pub_total'] ?? $n['pay_total'];
                }
            }
            $opt['total_if_used'] = round($opt['total_if_used'], 2);
            $opt['saving'] = $pubMissing ? null : round($pubTotal - $opt['total_if_used'], 2);
            $opt['used'] = isset($used[$k]);
        }
        unset($opt);
        $h['offers_ready'] = true;
        $h['pub_total'] = $pubMissing ? null : round($pubTotal, 2);
        $h['saving'] = $pubMissing ? null : round($pubTotal - $h['pay_total'], 2);
        $h['offers_used'] = array_values($used);
        $h['offer_options'] = array_values($options);
        // choice_needed: nights with special rates fitting and nobody's decision yet (the published rate is paid meanwhile)
        $h['choice_needed'] = $choiceNeeded;
        $h['bases'] = $bases;
        // proposed: none (tf-offers-r3, Shantha 4 Oct 2026). The published rate is always the default at the hotel payment —
        // also when a special rate costs less; a special rate is used only when an authorized person selects it. The key
        // stays (always null) for the screens that read it.
        $h['proposed'] = null;
        if ($used) {
            $kinds = array_values(array_unique(array_column($used, 'kind')));
            $label = implode(' + ', array_map(static fn ($k) => $k === 'early_bird' ? 'Early Bird rate' : 'Tactical rate', $kinds));
            $h['source_label'] = $label . (count($used) && array_sum(array_column($used, 'nights')) < $h['nights'] ? ' + published' : '');
        } elseif ($choiceNeeded > 0) {
            $h['source_label'] = ($h['needs_check'] ? 'No published rate' : $h['source_label']) . ' — special rate available';
        }
    }

    /**
     * The hotel cost of a stay as a sign-in WITHOUT "Tactical / Early Bird rate selection" may see it: the amount the
     * hotel is paid and where it comes from, but no tactical or early bird — not whether one is available, not its
     * name, rate or saving (Shantha, 4 Oct 2026: "only visible to authorized users"). A special rate an authorized
     * person applied shows as "Special rate — set by an authorized user".
     */
    public static function forViewer(?array $h, bool $canDecide): ?array
    {
        if ($h === null || $canDecide || empty($h['offers_ready'])) {
            return $h;
        }
        $offerNames = array_column($h['offers_used'] ?? [], 'name');
        $anyUsed = $offerNames !== [];
        $partly = $anyUsed && array_sum(array_column($h['offers_used'], 'nights')) < (int) $h['nights'];
        foreach (['pub_total', 'saving', 'offers_used', 'offer_options', 'choice_needed', 'choice', 'history', 'bases', 'proposed'] as $k) {
            unset($h[$k]);
        }
        $h['offers_ready'] = false;
        $h['source_label'] = $anyUsed ? 'Special rate — set by an authorized user' . ($partly ? ' + published' : '') : self::sourceLabel((string) $h['source']);
        $h['plans'] = array_values(array_unique(array_map(static fn ($p) => in_array($p, $offerNames, true) ? 'Special rate' : $p, $h['plans'] ?? [])));
        foreach ($h['rows'] as &$r) {
            if (!empty($r['offer'])) {
                $r['plan'] = 'Special rate';
                $r['season'] = null;
            }
            $r['flags'] = array_values(array_filter($r['flags'] ?? [], static fn ($f) => ($f['kind'] ?? '') !== 'offer'));
            foreach (['basis', 'pub', 'pub_total', 'pub_plan', 'offer', 'options', 'choice_needed'] as $k) {
                unset($r[$k]);
            }
        }
        unset($r);
        return $h;
    }

    /** Nights of each tour_quotations row's stay: consecutive dates at the same hotel. */
    private function stayLengths(array $rows): array
    {
        $byHotel = [];
        foreach ($rows as $q) {
            $byHotel[trim((string) $q['hotel_acc'])][$q['tour_date']][] = (int) $q['id'];
        }
        $out = [];
        foreach ($byHotel as $dates) {
            ksort($dates);
            $run = []; $prev = null;
            $flush = static function (array $run) use (&$out, $dates) {
                foreach ($run as $d) {
                    foreach ($dates[$d] as $id) {
                        $out[$id] = count($run);
                    }
                }
            };
            foreach (array_keys($dates) as $d) {
                if ($prev !== null && (strtotime($d) - strtotime($prev)) / 86400 > 1.5) {
                    $flush($run);
                    $run = [];
                }
                $run[] = $d; $prev = $d;
            }
            $flush($run);
        }
        return $out;
    }

    /** Room rates of a priced night as the payment counts them (per person × occupancy, a whole unit as it is). */
    private function roomRates(array $r): array
    {
        if ($r['pricing_mode'] === 'per_unit') {
            return [$r['unit'], $r['unit'], $r['unit']];
        }
        $m = $r['pricing_mode'] === 'per_person';
        return [$r['sgl'] !== null ? round($r['sgl'], 2) : null, $r['dbl'] !== null ? round($r['dbl'] * ($m ? 2 : 1), 2) : null, $r['tpl'] !== null ? round($r['tpl'] * ($m ? 3 : 1), 2) : null];
    }

    /** One night's total for the booked rooms; an occupancy the rate does not price counts at the quoted figure. */
    private function nightTotal(array $rates, array $counts, array $quoted, float $supplements): float
    {
        $t = 0.0;
        foreach ([0, 1, 2] as $i) {
            if ($counts[$i] > 0) {
                $t += (float) ($rates[$i] ?? $quoted[$i] ?? 0) * $counts[$i];
            }
        }
        return round($t + $supplements, 2);
    }

    /** What each stay of the tour used before this computation: hotel => signature + totals. */
    private function usedByHotel(string $tourId): array
    {
        $out = [];
        foreach ($this->rows('SELECT hotel_acc, hotel_id, basis, offer_plan_id, offer_name, offer_kind, pub_total, pay_total, choice_needed FROM hr2_tour_hotel_cost WHERE tour_id = ? ORDER BY tour_date, id', [$tourId]) as $r) {
            $out[$r['hotel_acc']][] = $r;
        }
        return array_map([$this, 'staySignature'], $out);
    }

    private function staySignature(array $rows): array
    {
        $sig = []; $pub = 0.0; $pubMissing = false; $pay = 0.0; $need = 0; $offer = null;
        foreach ($rows as $r) {
            $sig[] = ($r['basis'] ?? '') . ':' . ($r['offer_plan_id'] ?? '');
            if (($r['pub_total'] ?? null) === null) { $pubMissing = true; } else { $pub += (float) $r['pub_total']; }
            $pay += (float) $r['pay_total'];
            $need += (int) ($r['choice_needed'] ?? 0);
            if (!empty($r['offer_plan_id']) && !$offer) {
                $offer = ['contract_id' => (int) $r['offer_plan_id'], 'offer_name' => $r['offer_name'], 'offer_kind' => $r['offer_kind']];
            }
        }
        $u = array_values(array_unique($sig));
        sort($u);
        return ['sig' => implode('|', $u), 'nights' => count($rows), 'published_total' => $pubMissing ? null : round($pub, 2), 'used_total' => round($pay, 2), 'choice_needed' => $need, 'offer' => $offer, 'hotel_id' => $rows[0]['hotel_id'] ?? null, 'has_offer' => (bool) $offer];
    }

    /** One log line per stay whose payment rate changed, that got a special rate to decide, or that a person decided (tf-offers-r1). */
    private function logOfferChanges(OfferRates $offers, string $tourId, ?string $manualId, array $before, array $out, array $opts, string $now): void
    {
        $after = [];
        foreach ($out as $r) {
            $after[$r['hotel_acc']][] = $r;
        }
        $after = array_map([$this, 'staySignature'], $after);
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $acc) {
            $b = $before[$acc] ?? null; $a = $after[$acc] ?? null;
            if (!$a) {
                continue;
            }
            $event = null; $note = null;
            if (!empty($opts['offer_event']) && ($opts['offer_hotel'] ?? null) === $acc) {
                $event = $opts['offer_event'];
                $note = $opts['offer_note'] ?? null;
            } elseif (!$b || $b['sig'] !== $a['sig']) {
                if ($a['choice_needed'] > 0) {
                    $event = 'available';
                } elseif ($a['has_offer']) {
                    $event = 'selected';
                } elseif ($b && ($b['has_offer'] || $b['choice_needed'] > 0)) {
                    $event = 'published';
                }
            }
            if ($event === null) {
                continue;
            }
            $offers->log(['tour_id' => $tourId, 'manual_id' => $manualId, 'hotel_acc' => $acc, 'hotel_id' => $a['hotel_id'], 'event' => $event,
                'contract_id' => $a['offer']['contract_id'] ?? null, 'offer_name' => $a['offer']['offer_name'] ?? null, 'offer_kind' => $a['offer']['offer_kind'] ?? null,
                'nights' => $a['nights'], 'published_total' => $a['published_total'], 'used_total' => $a['used_total'], 'note' => $note, 'by_user' => $this->user, 'created_at' => $now]);
        }
    }

    /**
     * The payment-rate block of one stay for the payment screens (Tour Payment Dashboard, Payment Approval): the
     * published total beside the total used, the saving, the offer(s) used, every offer that fits with what the stay
     * would cost with it, whether a person still has to choose, the choice made and the history. Null before
     * schema 42 (the screens then show what they showed before).
     */
    public static function offerFields(?array $h): ?array
    {
        if (!$h || empty($h['offers_ready'])) {
            return null;
        }
        return [
            'hotel_acc' => $h['hotel_acc'], 'nights' => $h['nights'], 'pay_total' => $h['pay_total'], 'pub_total' => $h['pub_total'] ?? null, 'saving' => $h['saving'] ?? null,
            'offers_used' => $h['offers_used'] ?? [], 'offer_options' => $h['offer_options'] ?? [], 'choice_needed' => (int) ($h['choice_needed'] ?? 0), 'proposed' => $h['proposed'] ?? null,
            'choice' => $h['choice'] ?? null, 'history' => $h['history'] ?? [], 'source_label' => $h['source_label'] ?? null,
            'nights_detail' => array_map(static fn ($r) => ['date' => $r['date'], 'room_type' => $r['room_type'], 'meal_plan' => $r['meal_plan'], 'basis' => $r['basis'], 'pub_total' => $r['pub_total'], 'pay_total' => $r['pay_total'], 'offer' => $r['offer']], $h['rows'] ?? []),
        ];
    }

    public static function sourceLabel(string $source): string
    {
        return [
            'adhoc' => 'Ad hoc rate', 'contracted' => 'Published / contract rate', 'quoted' => 'Quoted rate — no contract rate found', 'none' => 'No rate found',
            'mixed_adhoc' => 'Ad hoc + contracted', 'mixed_check' => 'Partly without a contract rate — check', 'mixed' => 'Mixed',
        ][$source] ?? $source;
    }

    private function num($v): ?float
    {
        return ($v === null || $v === '' || (float) $v <= 0) ? null : (float) $v;
    }

    private function row(string $sql, array $binds = []): ?array
    {
        $r = $this->db->query($sql, $binds)->getRowArray();
        return $r ?: null;
    }

    private function rows(string $sql, array $binds = []): array
    {
        return $this->db->query($sql, $binds)->getResultArray();
    }
}
