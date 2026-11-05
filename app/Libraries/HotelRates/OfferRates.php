<?php

namespace App\Libraries\HotelRates;

/**
 * Published rates, Tactical rates and Early Bird offers — three separate things (tf-offers-r1, schema 42).
 *
 *   Published / contract rate  the hotel's seasonal rates to every agent: the ONLY rate of an inquiry, a quotation,
 *                              a confirmation and a booking (plan types of RateLookup::quotationPlanTypes).
 *   Tactical rate              a special rate the hotel gives us selectively (production, flash sale, special offer,
 *                              package): plan_type tactical, or special_offer.
 *   Early Bird offer           a rate for booking ahead: plan_type early_bird (its booking deadline is book_to).
 *   (An "ad hoc" payment-only rate — a contract, addendum or special-offer plan with applies_to = payment — is neither:
 *   it pays the hotel as it did before this release.)
 *
 * Tacticals and early birds count only at the hotel PAYMENT of a confirmed tour, and only when a person holding
 * "Tactical / Early Bird rate selection" decides so (Shantha, 4 Oct 2026: "flag if there's a tactical or early bird
 * available, only visible to authorized users, and let them decide whether to apply that special rate for payment or
 * just use the published rate"). For every hotel night this class lists the published rate, the rate the payment uses
 * without any special rate (the published rate, or an ad hoc payment rate), and every approved tactical / early bird
 * that fits the night (stay dates, booking window against the tour's booking date, market, tier, room type and meal
 * plan, minimum stay against the length of the stay, no stop sale):
 *
 *   - no decision for the stay yet, one or more offers fit: the payment uses the published rate and the stay is
 *     flagged "special rate available" — nothing is applied by itself, and the published rate is the default on the
 *     payment step even when a special rate costs less (tf-offers-r3);
 *   - a decision for the stay: the offer chosen (on the nights it fits) or the published rate, kept with who and when.
 *
 * The review an authorized person makes before such a payment (the notice, the special rates, their terms) is logged
 * too, with who and when, whatever they decide afterwards (tf-offers-r3).
 *
 * Every decision is kept (who, when, what replaced it) and every change of the rate a payment uses is logged
 * (hr2_offer_log). Nothing here writes a plan, a rate or a quotation.
 */
final class OfferRates
{
    public const KINDS = ['tactical' => 'Tactical rate', 'early_bird' => 'Early Bird offer'];
    public const TACTICAL_TYPES = ['tactical', 'special_offer'];
    /** Early birds whose booking deadline is this close are flagged. */
    public const DEADLINE_SOON_DAYS = 14;

    private $db;
    private HotelRateService $svc;
    private ?bool $ready = null;
    private ?array $quoteTypes = null;

    public function __construct($db, ?HotelRateService $svc = null)
    {
        $this->db = $db;
        $this->svc = $svc ?? new HotelRateService($db, 'system');
    }

    /** True once Install now (TourFlow schema 42) has created the tables and columns this needs. */
    public function ready(): bool
    {
        if ($this->ready === null) {
            try {
                $this->ready = (bool) $this->db->query("SHOW TABLES LIKE 'hr2_offer_choice'")->getResultArray()
                    && (bool) $this->db->query("SHOW COLUMNS FROM hr2_tour_hotel_cost LIKE 'basis'")->getRowArray();
            } catch (\Throwable $e) {
                $this->ready = false;
            }
        }
        return $this->ready;
    }

    /** 'tactical' | 'early_bird' | null (a published plan, or an ad hoc payment-only rate). */
    public static function kindOf(array $plan): ?string
    {
        $type = (string) ($plan['plan_type'] ?? 'contract');
        if ($type === 'early_bird') {
            return 'early_bird';
        }
        if ($type === 'tactical') {
            return 'tactical';
        }
        // a special offer is a tactical; a payment-only special offer is the older "ad hoc payment rate"
        if ($type === 'special_offer' && ($plan['applies_to'] ?? 'both') !== 'payment') {
            return 'tactical';
        }
        return null;
    }

    /** SQL condition (alias c) for the plans that are tacticals or early birds. */
    public static function offerSql(string $alias = 'c'): string
    {
        return "({$alias}.plan_type IN ('tactical','early_bird') OR ({$alias}.plan_type = 'special_offer' AND {$alias}.applies_to <> 'payment'))";
    }

    /** The signed-in user's answer to canDecide() (false when the menu cannot be read). */
    public static function currentUserCanDecide(): bool
    {
        static $answer = null;
        if ($answer === null) {
            try {
                $answer = self::canDecide((new \App\Libraries\MenuLoader())->loadMenuData());
            } catch (\Throwable $e) {
                $answer = false;
            }
        }
        return $answer;
    }

    /** Whether a sign-in may see and decide special rates at payment: the System Administrator, or the privilege. */
    public static function canDecide(array $menuData): bool
    {
        if ((int) ($menuData['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ((array) ($menuData['allowed_menu_list'] ?? []) as $m) {
            $u = is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '');
            if (trim((string) $u, '/') === 'master_files/offer_selection') {
                return true;
            }
        }
        return false;
    }

    private function quoteTypes(): array
    {
        return $this->quoteTypes ??= RateLookup::quotationPlanTypes($this->db);
    }

    // =====================================================================
    //  One night: the published rate, the offers that fit, the rate used
    // =====================================================================

    /**
     * @param array      $opts        the payment lookup options (market, booking_date, rooms, pax)
     * @param int        $stayNights  nights of this stay at the hotel (consecutive), for minimum-stay conditions
     * @param array|null $choice      the active decision for this tour's stay at the hotel (hr2_offer_choice row)
     * @return array{published: ?array, base: ?array, options: array, used: ?array, res: ?array, basis: string, proposed: ?int}
     *   basis: published (no special rate fits) | pending (special rates fit, nobody has decided: published used) |
     *          selected (an authorized person applied an offer) | published_selected (they kept the published rate)
     */
    public function night(int $hotelId, string $date, $roomRef, string $meal, array $opts, int $stayNights, ?array $choice = null): array
    {
        $engine = $this->svc->lookupEngine();
        $base = array_intersect_key($opts, array_flip(['market', 'country', 'booking_date', 'rooms', 'pax', 'tier']));
        // the published rate: exactly what a quotation asks for
        $pub = $engine->price($hotelId, $date, $roomRef, $meal, $base + ['plan_types' => $this->quoteTypes()]);
        // what the payment uses without any special rate: an ad hoc payment-only rate first, else the published rate
        // (the rule before this release, with the tacticals and early birds taken out of it)
        $pay = null;
        $options = [];
        $b = $engine->bundle($hotelId);
        if ($b['hotel']) {
            $seen = [];
            foreach ($engine->candidates($b, $date, $base + ['purpose' => 'payment']) as $cand) {
                $pid = (int) $cand['plan_id'];
                if (isset($seen[$pid])) {
                    continue;
                }
                $seen[$pid] = true;
                $plan = $b['plans'][$pid];
                $kind = self::kindOf($plan);
                if ($kind === null) {
                    if ($pay === null) {
                        $pay = $engine->price($hotelId, $date, $roomRef, $meal, $base + ['purpose' => 'payment', 'plan_id' => $pid]);
                    }
                    continue;
                }
                $res = $engine->price($hotelId, $date, $roomRef, $meal, $base + ['purpose' => 'payment', 'plan_id' => $pid]);
                if (!$res) {
                    continue; // the offer does not price this room type and meal plan
                }
                [$ok, $why] = $this->conditions($res, $stayNights);
                $options[] = ['plan_id' => $pid, 'kind' => $kind, 'name' => (string) $plan['name'], 'rate_code' => $plan['rate_code'] ?? null, 'plan_type' => $plan['plan_type'],
                    'book_to' => $plan['book_to'] ?? null, 'ok' => $ok, 'why' => $why, 'res' => $res];
            }
        }
        $pay = $pay ?? $pub;
        $usable = array_values(array_filter($options, static fn ($o) => $o['ok']));
        $used = null; $basis = 'published'; $proposed = null;
        if ($choice) {
            if (($choice['choice'] ?? '') === 'published') {
                $basis = 'published_selected';
            } else {
                foreach ($usable as $o) {
                    if ($o['plan_id'] === (int) $choice['contract_id']) {
                        $used = $o;
                        break;
                    }
                }
                // the offer applied to the stay does not fit this night: the published rate pays it
                $basis = $used ? 'selected' : 'published';
            }
        } elseif ($usable) {
            $basis = 'pending';
            $proposed = count($usable) === 1 ? $usable[0]['plan_id'] : null;
        }
        return ['published' => $pub, 'base' => $pay, 'options' => $options, 'used' => $used, 'res' => $used ? $used['res'] : $pay, 'basis' => $basis, 'proposed' => $proposed];
    }

    /** Conditions the system can check: minimum stay (season, plan, restriction rows) and a stop sale. */
    private function conditions(array $res, int $stayNights): array
    {
        $min = (int) ($res['season']['min_stay'] ?? 0);
        foreach ($res['restrictions'] ?? [] as $r) {
            if (($r['kind'] ?? '') === 'min_stay' && is_numeric($r['value'] ?? null)) {
                $min = max($min, (int) $r['value']);
            }
            if (($r['kind'] ?? '') === 'stop_sale') {
                return [false, 'stop sale on this night'];
            }
        }
        if ($min > 1 && $stayNights < $min) {
            return [false, "needs at least {$min} nights; this stay is {$stayNights}"];
        }
        return [true, null];
    }

    // =====================================================================
    //  Choices and their history
    // =====================================================================

    /** Active choices of a tour, keyed by hotel code. */
    public function choicesForTour(string $tourId): array
    {
        $out = [];
        foreach ($this->db->query('SELECT * FROM hr2_offer_choice WHERE tour_id = ? AND active = 1 ORDER BY id', [$tourId])->getResultArray() as $r) {
            $out[$r['hotel_acc']] = $r;
        }
        return $out;
    }

    /**
     * Decide the rate a tour's stay at a hotel is paid at: 'published' (keep the published rate) or the id of an
     * approved tactical / early bird of that hotel (apply it). The previous decision is kept, marked as replaced.
     */
    public function choose(string $tourId, string $hotelAcc, string $choice, ?string $note, string $user): array
    {
        $tourId = trim($tourId); $hotelAcc = trim($hotelAcc);
        $stay = $this->db->query('SELECT hotel_id, manual_id, COUNT(*) AS n FROM hr2_tour_hotel_cost WHERE tour_id = ? AND hotel_acc = ? GROUP BY hotel_id, manual_id', [$tourId, $hotelAcc])->getRowArray();
        if (!$stay) {
            throw new \RuntimeException('This tour has no costed nights at that hotel — press Recalculate on the Tour Payment Dashboard first.');
        }
        $hotelId = $stay['hotel_id'] !== null ? (int) $stay['hotel_id'] : null;
        $row = ['choice' => null, 'contract_id' => null, 'offer_name' => null, 'offer_kind' => null];
        if ($choice === 'published') {
            $row['choice'] = 'published';
        } else {
            if (!ctype_digit($choice)) {
                throw new \RuntimeException('Choose the published rate or one of the special rates listed.');
            }
            $plan = $this->db->query('SELECT c.id, c.hotel_id, c.name, c.plan_type, c.applies_to, c.status FROM hr2_contract c WHERE c.id = ?', [(int) $choice])->getRowArray();
            if (!$plan || $plan['status'] !== 'approved' || self::kindOf($plan) === null || $hotelId === null || (int) $plan['hotel_id'] !== $hotelId) {
                throw new \RuntimeException('That offer is not an active tactical or early bird of this hotel.');
            }
            $row = ['choice' => 'offer', 'contract_id' => (int) $plan['id'], 'offer_name' => $plan['name'], 'offer_kind' => self::kindOf($plan)];
        }
        $now = date('Y-m-d H:i:s');
        $note = $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 300) : null;
        $this->db->query('UPDATE hr2_offer_choice SET active = 0, ended_at = ?, ended_by = ? WHERE tour_id = ? AND hotel_acc = ? AND active = 1', [$now, $user, $tourId, $hotelAcc]);
        $this->db->query('INSERT INTO hr2_offer_choice (tour_id, hotel_acc, hotel_id, choice, contract_id, offer_name, offer_kind, note, chosen_by, chosen_at, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)',
            [$tourId, $hotelAcc, $hotelId, $row['choice'], $row['contract_id'], $row['offer_name'], $row['offer_kind'], $note, $user, $now]);
        // the payable rows follow at once
        $cost = new HotelCostService($this->db, $user, $this->svc);
        $sum = $cost->computeTour($tourId, ['offer_event' => $choice === 'published' ? 'published_selected' : 'selected', 'offer_note' => $note, 'offer_hotel' => $hotelAcc]);
        return ['choice' => $row['choice'], 'contract_id' => $row['contract_id'], 'offer_name' => $row['offer_name'], 'hotel' => $sum['hotels'][$hotelAcc] ?? null];
    }

    public function log(array $e): void
    {
        $this->db->query('INSERT INTO hr2_offer_log (tour_id, manual_id, hotel_acc, hotel_id, event, contract_id, offer_name, offer_kind, nights, published_total, used_total, note, by_user, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $e['tour_id'], $e['manual_id'] ?? null, $e['hotel_acc'], $e['hotel_id'] ?? null, $e['event'], $e['contract_id'] ?? null, isset($e['offer_name']) ? mb_substr((string) $e['offer_name'], 0, 255) : null, $e['offer_kind'] ?? null,
            (int) ($e['nights'] ?? 0), $e['published_total'] ?? null, $e['used_total'] ?? null, isset($e['note']) ? mb_substr((string) $e['note'], 0, 300) : null, $e['by_user'] ?? 'system', $e['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    /** The history of one tour's stay at a hotel, newest first. */
    public function history(string $tourId, string $hotelAcc, int $limit = 30): array
    {
        return $this->db->query('SELECT event, contract_id, offer_name, offer_kind, nights, published_total, used_total, note, by_user, created_at FROM hr2_offer_log WHERE tour_id = ? AND hotel_acc = ? ORDER BY id DESC LIMIT ' . (int) $limit, [$tourId, $hotelAcc])->getResultArray();
    }

    /**
     * The review of the special rates of one tour's stay at a hotel, by an authorized person, before its hotel payment
     * (tf-offers-r3): logged as "reviewed" with who and when, the special rates that fit and the amounts — whatever is
     * decided afterwards. Nothing else changes: no decision, no payable row, no plan.
     *
     * @param string[] $viewed the names of the offers whose terms were opened during the review
     */
    public function recordReview(string $tourId, string $hotelAcc, string $user, array $viewed = []): array
    {
        $tourId = trim($tourId); $hotelAcc = trim($hotelAcc);
        $rows = $this->db->query('SELECT hotel_id, manual_id, pub_total, pay_total, options_json FROM hr2_tour_hotel_cost WHERE tour_id = ? AND hotel_acc = ? ORDER BY tour_date', [$tourId, $hotelAcc])->getResultArray();
        if (!$rows) {
            throw new \RuntimeException('This tour has no costed nights at that hotel — press Recalculate on the Tour Payment Dashboard first.');
        }
        $fit = []; $pub = 0.0; $pubMissing = false; $pay = 0.0;
        foreach ($rows as $r) {
            if ($r['pub_total'] === null) { $pubMissing = true; } else { $pub += (float) $r['pub_total']; }
            $pay += (float) $r['pay_total'];
            foreach (($r['options_json'] ? (json_decode((string) $r['options_json'], true) ?: []) : []) as $o) {
                if (!empty($o['ok'])) {
                    $fit[(int) $o['plan_id']] = ['plan_id' => (int) $o['plan_id'], 'name' => (string) $o['name'], 'kind' => $o['kind'] ?? null];
                }
            }
        }
        if (!$fit) {
            throw new \RuntimeException('No tactical or early bird fits this stay — there is nothing to review.');
        }
        $fit = array_values($fit);
        $kinds = array_values(array_unique(array_column($fit, 'kind')));
        $viewed = array_values(array_unique(array_filter(array_map(static fn ($v) => trim((string) $v), $viewed))));
        $entry = ['tour_id' => $tourId, 'manual_id' => $rows[0]['manual_id'], 'hotel_acc' => $hotelAcc, 'hotel_id' => $rows[0]['hotel_id'] !== null ? (int) $rows[0]['hotel_id'] : null,
            'event' => 'reviewed', 'contract_id' => count($fit) === 1 ? $fit[0]['plan_id'] : null, 'offer_name' => implode(' + ', array_column($fit, 'name')),
            'offer_kind' => count($kinds) === 1 ? $kinds[0] : 'mixed', 'nights' => count($rows), 'published_total' => $pubMissing ? null : round($pub, 2), 'used_total' => round($pay, 2),
            'note' => $viewed ? 'Terms opened: ' . implode('; ', $viewed) : null, 'by_user' => $user, 'created_at' => date('Y-m-d H:i:s')];
        $this->log($entry);
        return $entry + ['label' => self::eventLabel('reviewed')];
    }

    /**
     * The terms of one tactical / early bird, as the review and the payment step show them to an authorized person
     * (tf-offers-r3): stay and booking windows, markets, minimum stay, every season with its rates, the compulsory
     * supplements and the written conditions. Null for a plan that is not a tactical or early bird.
     */
    public function terms(int $planId, ?string $today = null): ?array
    {
        $plan = $this->db->query('SELECT id, hotel_id, name, plan_type, applies_to, status, valid_from, valid_to, book_from, book_to FROM hr2_contract WHERE id = ?', [$planId])->getRowArray();
        if (!$plan || self::kindOf($plan) === null) {
            return null;
        }
        $sheet = $this->svc->getContract($planId);
        if (!$sheet) {
            return null;
        }
        $c = $sheet['contract'];
        $meta = $sheet['meta'] ?? [];
        $minStay = (int) ($c['min_stay'] ?? 0);
        $seasons = [];
        foreach ($sheet['seasons'] ?? [] as $se) {
            $minStay = max($minStay, (int) ($se['min_stay'] ?? 0));
            $seasons[] = ['name' => $se['name'], 'kind' => $se['kind'] ?? 'normal', 'min_stay' => $se['min_stay'] ?? null, 'notes' => $se['notes'] ?? null,
                'dates' => array_map(static fn ($d) => ['from' => $d['from'], 'to' => $d['to']], $se['dates'] ?? []),
                'rates' => array_map(static fn ($r) => ['room_type' => $r['room_type'], 'meal_plan' => $r['meal_plan'], 'sgl' => $r['sgl'], 'dbl' => $r['dbl'], 'tpl' => $r['tpl'], 'unit' => $r['unit'] ?? null, 'note' => $r['note'] ?? null], $se['rates'] ?? [])];
        }
        foreach ($sheet['restrictions'] ?? [] as $r) {
            if (($r['kind'] ?? '') === 'min_stay') {
                $minStay = max($minStay, (int) ($r['value'] ?? 0));
            }
        }
        $markets = [];
        foreach ((array) ($c['markets'] ?? []) as $m) {
            $markets[] = is_array($m) ? ((($m['mode'] ?? '') === 'exclude' ? 'not ' : '') . ($m['code'] ?? $m['market_code'] ?? '')) : (string) $m;
        }
        $text = [];
        foreach (['terms_text' => 'Terms', 'cancellation_text' => 'Cancellation', 'payment_text' => 'Payment', 'child_policy_text' => 'Children', 'notes' => 'Notes'] as $k => $label) {
            if (trim((string) ($c[$k] ?? '')) !== '') {
                $text[] = ['label' => $label, 'text' => (string) $c[$k]];
            }
        }
        $kind = self::kindOf($plan);
        return [
            'id' => (int) $plan['id'], 'kind' => $kind, 'kind_label' => $kind === 'early_bird' ? 'Early Bird offer' : 'Tactical rate', 'name' => $c['name'], 'rate_code' => $c['rate_code'] ?? null,
            'hotel' => ['id' => (int) $plan['hotel_id'], 'name' => $sheet['hotel']['name'] ?? null], 'offer_status' => self::status($plan, $today ?: date('Y-m-d')),
            'valid_from' => $c['valid_from'], 'valid_to' => $c['valid_to'], 'book_from' => $c['book_from'] ?? null, 'book_to' => $c['book_to'] ?? null,
            'markets' => $markets, 'min_stay' => $minStay ?: null, 'currency' => $c['currency'] ?? null, 'pricing_mode' => $c['pricing_mode'] ?? null, 'taxes_included' => isset($c['taxes_included']) ? (bool) $c['taxes_included'] : null,
            'seasons' => $seasons,
            // rooms priced as a supplement over the base room, and meal plans as a supplement over the base plan
            'room_supplements' => array_values(array_map(static fn ($x) => ['room_type' => $x['room_type'] ?? null, 'amount' => $x['amount'] ?? null, 'per' => $x['per'] ?? null, 'season' => $x['season'] ?? null, 'note' => $x['note'] ?? null], $sheet['room_supplements'] ?? [])),
            'meal_deltas' => array_values(array_map(static fn ($x) => ['from' => $x['from'] ?? null, 'to' => $x['to'] ?? null, 'adult' => $x['adult'] ?? null, 'child' => $x['child'] ?? null, 'season' => $x['season'] ?? null, 'note' => $x['note'] ?? null], $sheet['meal_deltas'] ?? [])),
            'supplements' => array_values(array_map(static fn ($x) => ['name' => $x['name'] ?? 'Supplement', 'type' => $x['type'] ?? null, 'compulsory' => !empty($x['compulsory']),
                'date_from' => $x['date_from'] ?? null, 'date_to' => $x['date_to'] ?? null, 'meal_plans' => $x['meal_plans'] ?? null, 'room_type' => $x['room_type'] ?? null, 'per' => $x['per'] ?? null,
                'amount' => $x['amount'] ?? null, 'amount_sgl' => $x['amount_sgl'] ?? null, 'amount_dbl' => $x['amount_dbl'] ?? null, 'amount_tpl' => $x['amount_tpl'] ?? null, 'child_amount' => $x['child_amount'] ?? null, 'note' => $x['note'] ?? null], $sheet['supplements'] ?? [])),
            'restrictions' => array_values(array_map(static fn ($x) => ['kind' => $x['kind'] ?? null, 'date_from' => $x['date_from'] ?? null, 'date_to' => $x['date_to'] ?? null, 'value' => $x['value'] ?? null, 'note' => $x['note'] ?? null], $sheet['restrictions'] ?? [])),
            'conditions' => $text, 'source_file' => $c['source_file'] ?? null,
            'approved_by' => $meta['approved_by'] ?? null, 'approved_at' => $meta['approved_at'] ?? null,
        ];
    }

    public static function eventLabel(string $event): string
    {
        return [
            'available' => 'Special rate available — the published rate is used until an authorized person decides',
            'selected' => 'Special rate applied',
            'published_selected' => 'Published rate kept',
            'published' => 'No special rate applies any more — published rate',
            'reviewed' => 'Special rate reviewed',
            // tf-offers-r3: the hotel payment held for the rate review (PaymentHold)
            'held' => 'Sent for rate review', 'released' => 'Released to Payment approval', 'returned' => 'Returned to staff',
            // before the decision rule of 4 Oct 2026 (test copy only)
            'proposed' => 'Offer proposed', 'choice_needed' => 'Several offers fit', 'cleared' => 'Decision removed',
        ][$event] ?? $event;
    }

    // =====================================================================
    //  The three areas of a hotel, and the monitor across hotels
    // =====================================================================

    /** Status of an offer on a day: draft | upcoming (booking opens later) | active (bookable now) | deadline_soon | booking_closed | expired | withdrawn. */
    public static function status(array $c, string $today): string
    {
        if ($c['status'] === 'draft') {
            return 'draft';
        }
        if (in_array($c['status'], ['superseded', 'cancelled'], true)) {
            return 'withdrawn';
        }
        if ($c['valid_to'] < $today) {
            return 'expired';
        }
        if (!empty($c['book_to']) && $c['book_to'] < $today) {
            return 'booking_closed';
        }
        if (!empty($c['book_from']) && $c['book_from'] > $today) {
            return 'upcoming';
        }
        if (!empty($c['book_to']) && (strtotime($c['book_to']) - strtotime($today)) / 86400 <= self::DEADLINE_SOON_DAYS) {
            return 'deadline_soon';
        }
        // bookable now (its stay period may begin later — the stay dates say when)
        return 'active';
    }

    /** Active = approved, still bookable, stay period not over (an upcoming stay period counts: it can be booked now). */
    public static function isActive(string $status): bool
    {
        return in_array($status, ['active', 'deadline_soon', 'upcoming'], true);
    }

    /**
     * Everything the hotel screen shows for the three areas: the published rates' state, and every tactical and
     * early bird of the hotel with its status, rooms, meal plans, markets, conditions and lowest payable double.
     */
    public function hotelAreas(int $hotelId, ?string $today = null): array
    {
        $today = $today ?: date('Y-m-d');
        $types = $this->quoteTypes();
        $in = "'" . implode("','", array_map('addslashes', $types)) . "'";
        $pub = $this->db->query("SELECT COUNT(*) AS n, MAX(valid_to) AS until, SUM(valid_from <= ? AND valid_to >= ?) AS today_n FROM hr2_contract
                                 WHERE hotel_id = ? AND status = 'approved' AND plan_type IN ({$in}) AND applies_to <> 'payment' AND valid_to >= ?", [$today, $today, $hotelId, $today])->getRowArray();
        $pubDrafts = (int) ($this->db->query("SELECT COUNT(*) AS n FROM hr2_contract WHERE hotel_id = ? AND status = 'draft' AND plan_type IN ({$in}) AND applies_to <> 'payment'", [$hotelId])->getRowArray()['n'] ?? 0);
        $offers = $this->offerRows('c.hotel_id = ?', [$hotelId], $today);
        $out = ['published' => ['current' => (int) ($pub['n'] ?? 0), 'until' => $pub['until'] ?? null, 'covers_today' => (int) ($pub['today_n'] ?? 0) > 0, 'drafts' => $pubDrafts],
            'tactical' => [], 'early_bird' => []];
        foreach ($offers as $o) {
            $out[$o['kind']][] = $o;
        }
        foreach (['tactical', 'early_bird'] as $k) {
            $out[$k . '_active'] = count(array_filter($out[$k], static fn ($o) => self::isActive($o['offer_status'])));
        }
        return $out;
    }

    /** Offer plans matching a condition, with their summaries. */
    private function offerRows(string $where, array $binds, string $today): array
    {
        $rows = $this->db->query("SELECT c.id, c.hotel_id, h.name AS hotel_name, h.primary_sup_acc_no, l.name AS location_name, c.name, c.plan_type, c.applies_to, c.rate_code, c.status, c.valid_from, c.valid_to, c.book_from, c.book_to,
                    c.min_stay, c.currency, c.pricing_mode, c.source_type, c.source_file, c.notes, c.entered_by, c.approved_by, c.approved_at, c.created_at, c.updated_at
                FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id LEFT JOIN hr2_location l ON l.id = h.location_id
                WHERE " . self::offerSql('c') . " AND c.status IN ('draft','approved','superseded') AND h.status <> 'merged' AND {$where}
                ORDER BY c.valid_to DESC, c.id DESC", $binds)->getResultArray();
        if (!$rows) {
            return [];
        }
        $ids = implode(',', array_map(static fn ($r) => (int) $r['id'], $rows));
        $rates = []; $markets = []; $minStay = [];
        foreach ($this->db->query("SELECT s.contract_id, rt.name AS room, r.meal_plan, MIN(COALESCE(r.dbl, r.unit, r.sgl)) AS lowest
                                   FROM hr2_rate r JOIN hr2_season s ON s.id = r.season_id JOIN hr2_room_type rt ON rt.id = r.room_type_id
                                   WHERE s.contract_id IN ({$ids}) GROUP BY s.contract_id, rt.name, r.meal_plan")->getResultArray() as $r) {
            $rates[(int) $r['contract_id']][] = $r;
        }
        foreach ($this->db->query("SELECT contract_id, market_code, mode FROM hr2_contract_market WHERE contract_id IN ({$ids})")->getResultArray() as $m) {
            $markets[(int) $m['contract_id']][] = ($m['mode'] === 'exclude' ? '−' : '') . $m['market_code'];
        }
        foreach ($this->db->query("SELECT contract_id, MAX(min_stay) AS m FROM hr2_season WHERE contract_id IN ({$ids}) GROUP BY contract_id")->getResultArray() as $m) {
            $minStay[(int) $m['contract_id']] = (int) $m['m'];
        }
        foreach ($this->db->query("SELECT contract_id, MAX(CAST(value AS UNSIGNED)) AS m FROM hr2_restriction WHERE kind = 'min_stay' AND contract_id IN ({$ids}) GROUP BY contract_id")->getResultArray() as $m) {
            $minStay[(int) $m['contract_id']] = max($minStay[(int) $m['contract_id']] ?? 0, (int) $m['m']);
        }
        $out = [];
        foreach ($rows as $c) {
            $id = (int) $c['id'];
            $rr = $rates[$id] ?? [];
            usort($rr, static fn ($a, $b) => (float) $a['lowest'] <=> (float) $b['lowest']);
            $st = self::status($c, $today);
            $out[] = [
                'id' => $id, 'hotel_id' => (int) $c['hotel_id'], 'hotel_name' => $c['hotel_name'], 'hotel_acc' => $c['primary_sup_acc_no'], 'location' => $c['location_name'],
                'kind' => self::kindOf($c), 'plan_type' => $c['plan_type'], 'payment_only' => ($c['applies_to'] ?? 'both') === 'payment', 'name' => $c['name'], 'rate_code' => $c['rate_code'],
                'status' => $c['status'], 'offer_status' => $st, 'valid_from' => $c['valid_from'], 'valid_to' => $c['valid_to'], 'book_from' => $c['book_from'], 'book_to' => $c['book_to'],
                'days_to_deadline' => $c['book_to'] ? (int) round((strtotime($c['book_to']) - strtotime($today)) / 86400) : null,
                'markets' => $markets[$id] ?? [], 'min_stay' => max((int) ($c['min_stay'] ?? 0), $minStay[$id] ?? 0) ?: null,
                'rooms' => array_values(array_unique(array_column($rr, 'room'))), 'meal_plans' => array_values(array_unique(array_column($rr, 'meal_plan'))),
                'lowest' => $rr ? ['amount' => (float) $rr[0]['lowest'], 'room' => $rr[0]['room'], 'meal_plan' => $rr[0]['meal_plan']] : null,
                'currency' => $c['currency'], 'pricing_mode' => $c['pricing_mode'], 'source_type' => $c['source_type'], 'source_file' => $c['source_file'],
                'entered_by' => $c['entered_by'], 'approved_by' => $c['approved_by'], 'approved_at' => $c['approved_at'],
            ];
        }
        return $out;
    }

    /** The monitor: every hotel with its counts, every offer, and the hotels without any. */
    public function monitor(?string $today = null): array
    {
        $today = $today ?: date('Y-m-d');
        $offers = $this->offerRows('1 = 1', [], $today);
        $hotels = $this->svc->searchHotels('', 1000, $today);
        $types = $this->quoteTypes();
        $in = "'" . implode("','", array_map('addslashes', $types)) . "'";
        $pub = [];
        foreach ($this->db->query("SELECT hotel_id, MAX(valid_to) AS until FROM hr2_contract WHERE status = 'approved' AND plan_type IN ({$in}) AND applies_to <> 'payment' AND valid_to >= ? GROUP BY hotel_id", [$today])->getResultArray() as $r) {
            $pub[(int) $r['hotel_id']] = $r['until'];
        }
        $count = [];
        foreach ($offers as $o) {
            if (self::isActive($o['offer_status'])) {
                $count[$o['hotel_id']][$o['kind']] = ($count[$o['hotel_id']][$o['kind']] ?? 0) + 1;
            }
        }
        $list = [];
        foreach ($hotels as $h) {
            if ($h['status'] !== 'active') {
                continue;
            }
            $id = (int) $h['id'];
            $list[] = ['id' => $id, 'name' => $h['name'], 'acc' => $h['primary_sup_acc_no'], 'location' => $h['location_name'], 'chain' => $h['chain'],
                'nights_last_year' => (int) $h['nights_last_year'], 'frequently_used' => (int) ($h['frequently_used'] ?? 0),
                'published_until' => $pub[$id] ?? null, 'tactical_active' => $count[$id]['tactical'] ?? 0, 'early_bird_active' => $count[$id]['early_bird'] ?? 0];
        }
        return ['today' => $today, 'deadline_soon_days' => self::DEADLINE_SOON_DAYS, 'hotels' => $list, 'offers' => $offers];
    }
}
