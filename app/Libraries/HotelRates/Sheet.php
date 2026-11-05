<?php

namespace App\Libraries\HotelRates;

/**
 * The "sheet": one rate plan as a plain array, the single shape that the panel
 * editor, the validator, the importer, the AI extractor, the migrator and the
 * database layer all speak. normalise() turns anything close to it (form
 * input, JSON from the browser, extractor output) into the full shape with
 * every key present and every value typed, so the other classes never guess.
 *
 *  [
 *    'hotel'    => ['id' => int|null, 'name' => string, 'sup_acc_no' => string|null],
 *    'contract' => [ ...every hr2_contract column..., 'markets' => [['code' => 'JP', 'mode' => 'include'], ...] ],
 *    'room_types' => [ ['name', 'code', 'priced_as', 'base_room_type', 'room_count', 'size_sqm', 'bed_config', 'view',
 *                       'max_adults', 'max_children', 'max_occupancy', 'extra_bed_allowed', 'unit_pax_min', 'unit_pax_max', 'description'] ],
 *    'seasons'  => [ ['id', 'name', 'kind', 'min_stay', 'max_rooms', 'notes',
 *                     'dates' => [['from' => 'Y-m-d', 'to' => 'Y-m-d'], ...],
 *                     'rates' => [['room_type', 'room_type_id', 'meal_plan', 'sgl', 'dbl', 'tpl', 'quad', 'unit', 'child_with_bed', 'child_no_bed', 'note'], ...]] ],
 *    'room_supplements'   => [['room_type', 'season', 'amount', 'per', 'note']],
 *    'meal_deltas'        => [['from', 'to', 'adult', 'child', 'season', 'note']],
 *    'meal_prices'        => [['meal', 'name', 'adult', 'child', 'note']],
 *    'supplements'        => [['type', 'name', 'date_from', 'date_to', 'season', 'meal_plans', 'room_type', 'per', 'amount', 'amount_sgl', 'amount_dbl', 'amount_tpl', 'child_amount', 'child_pct', 'pct_of_rate', 'compulsory', 'note']],
 *    'child_rates'        => [['age_from', 'age_to', 'bed', 'meal_plan', 'amount', 'pct', 'pct_basis', 'max_children', 'room_type', 'note']],
 *    'guide_policies'     => [['role', 'meal_plan', 'amount', 'currency', 'pct_of', 'pct', 'per', 'free_from_pax', 'min_rooms', 'accommodation', 'note']],
 *    'foc_rules'          => [['basis', 'every_n', 'free_n', 'discount_pct', 'as_what', 'note']],
 *    'offers'             => [['kind', 'name', 'book_from', 'book_to', 'stay_from', 'stay_to', 'min_nights', 'min_pax', 'min_rooms', 'value_pct', 'value_amount', 'free_nights', 'per', 'combinable', 'excludes_peak', 'conditions']],
 *    'cancellation_rules' => [['date_from', 'date_to', 'tier', 'min_rooms', 'max_rooms', 'min_pax', 'max_pax', 'days_from', 'days_to', 'charge_pct', 'charge_nights', 'note']],
 *    'payment_rules'      => [['applies', 'kind', 'days_before', 'pct', 'date_from', 'date_to', 'note']],
 *    'restrictions'       => [['kind', 'date_from', 'date_to', 'value', 'note']],
 *    'extras'             => [['category', 'name', 'per', 'adult', 'child', 'currency', 'note']],
 *  ]
 *
 * Season references inside child lists ('season') are season names (or the
 * 0-based index as a string) — the database layer resolves them to ids.
 */
final class Sheet
{
    public const MEAL_PLANS = ['RO', 'BB', 'HB', 'FB', 'AI'];
    public const MEAL_ORDER = ['RO' => 0, 'BB' => 1, 'HB' => 2, 'FB' => 3, 'AI' => 4];
    public const MEAL_LABELS = ['RO' => 'Room only', 'BB' => 'Bed & breakfast', 'HB' => 'Half board', 'FB' => 'Full board', 'AI' => 'All inclusive'];
    public const OCCUPANCIES = ['sgl' => 1, 'dbl' => 2, 'tpl' => 3, 'quad' => 4];

    public const PLAN_TYPES = ['contract', 'special_offer', 'tactical', 'early_bird', 'addendum', 'series', 'migrated'];
    public const TIERS = ['all', 'fit', 'group', 'wedding', 'long_stay'];
    /** both = prices quotations and hotel payments; quotation = selling side only; payment = ad hoc rate, hotel cost only. */
    public const APPLIES_TO = ['both', 'quotation', 'payment'];
    public const PRICING_MODES = ['per_room', 'per_person', 'per_unit'];
    public const SEASON_KINDS = ['normal', 'peak', 'festive', 'low', 'other'];
    public const SUPPLEMENT_TYPES = ['gala', 'peak', 'festival', 'extra_adult', 'early_checkin', 'late_checkout', 'other'];
    public const SUPPLEMENT_PER = ['person', 'adult', 'child', 'room', 'night', 'booking'];
    public const CHILD_BED = ['any', 'with_bed', 'no_bed'];
    public const PCT_BASIS = ['adult_rate', 'meal_rate', 'meal_supplement', 'room_rate'];
    public const GUIDE_ROLES = ['guide', 'driver', 'tour_leader', 'any'];
    public const GUIDE_ACCOMMODATION = ['hotel_room', 'guide_quarters', 'nearby', 'unknown'];
    public const FOC_BASIS = ['pax', 'rooms'];
    public const FOC_AS = ['any', 'half_double', 'single', 'guide_room'];
    public const OFFER_KINDS = ['early_booking', 'long_stay', 'honeymoon', 'group_reduction', 'free_night', 'other'];
    public const OFFER_PER = ['person', 'room', 'booking', 'night'];
    public const CANCEL_TIERS = ['all', 'fit', 'group'];
    public const PAY_APPLIES = ['all', 'credit', 'non_credit', 'fit', 'group', 'peak'];
    public const PAY_KINDS = ['prepay', 'advance', 'interest', 'surcharge', 'credit_period', 'other'];
    public const RESTRICTION_KINDS = ['min_stay', 'closed_to_arrival', 'closed_to_departure', 'meal_plan_required', 'stop_sale', 'other'];
    public const EXTRA_CATEGORIES = ['excursion', 'transfer', 'mice', 'wellness', 'other'];
    public const EXTRA_PER = ['person', 'room', 'booking', 'group', 'hour'];
    public const MEALS = ['breakfast', 'lunch', 'dinner', 'other'];
    public const SOURCE_TYPES = ['manual', 'excel', 'ai', 'migration', 'email'];
    public const CREDIT = ['unknown', 'yes', 'no'];

    /** Plan type specificity used when several approved plans match the same night. */
    public const PLAN_RANK = ['tactical' => 5, 'special_offer' => 5, 'early_bird' => 5, 'addendum' => 4, 'series' => 3, 'contract' => 2, 'migrated' => 1];

    /** Contract header keys accepted from the editor / importer (everything else is dropped). */
    public const CONTRACT_KEYS = [
        'id', 'name', 'plan_type', 'rate_code', 'tier', 'tier_min_rooms', 'tier_max_rooms', 'tier_min_pax', 'tier_max_pax', 'priority', 'applies_to', 'parent_contract_id',
        'valid_from', 'valid_to', 'book_from', 'book_to', 'currency', 'billing_currency', 'pricing_mode', 'default_meal_plan', 'taxes_included', 'tax_pct', 'tax_note',
        'status', 'source_type', 'source_file', 'received_at', 'contract_date', 'contract_type', 'hotel_reference', 'credit_facility',
        'min_stay', 'max_rooms_per_booking', 'release_days', 'tentative_hold_hours', 'extra_bed_adult', 'max_children_free',
        'child_policy_text', 'guide_policy_text', 'foc_policy_text', 'cancellation_text', 'payment_text', 'terms_text', 'voucher_note', 'notes',
    ];

    /** Fill defaults and coerce types. Idempotent. */
    public static function normalise(array $in): array
    {
        $hotel = $in['hotel'] ?? [];
        $c = $in['contract'] ?? [];
        $out = [
            'hotel' => [
                'id' => self::intOrNull($hotel['id'] ?? null),
                'name' => trim((string) ($hotel['name'] ?? '')),
                'sup_acc_no' => self::strOrNull($hotel['sup_acc_no'] ?? null),
            ],
            'contract' => [
                'id' => self::intOrNull($c['id'] ?? null),
                'name' => trim((string) ($c['name'] ?? '')),
                'plan_type' => self::enum($c['plan_type'] ?? null, self::PLAN_TYPES, 'contract'),
                'rate_code' => self::strOrNull($c['rate_code'] ?? null, 60),
                'tier' => self::enum($c['tier'] ?? null, self::TIERS, 'all'),
                'tier_min_rooms' => self::intOrNull($c['tier_min_rooms'] ?? null),
                'tier_max_rooms' => self::intOrNull($c['tier_max_rooms'] ?? null),
                'tier_min_pax' => self::intOrNull($c['tier_min_pax'] ?? null),
                'tier_max_pax' => self::intOrNull($c['tier_max_pax'] ?? null),
                'priority' => (int) (self::intOrNull($c['priority'] ?? null) ?? 0),
                'applies_to' => self::enum($c['applies_to'] ?? null, self::APPLIES_TO, 'both'),
                'parent_contract_id' => self::intOrNull($c['parent_contract_id'] ?? null),
                'markets' => self::markets($c['markets'] ?? ($c['market'] ?? null)),
                'valid_from' => Dates::parse($c['valid_from'] ?? null),
                'valid_to' => Dates::parse($c['valid_to'] ?? null),
                'book_from' => Dates::parse($c['book_from'] ?? null),
                'book_to' => Dates::parse($c['book_to'] ?? null),
                'currency' => self::currency($c['currency'] ?? null),
                'billing_currency' => self::enum($c['billing_currency'] ?? null, ['same', 'LKR'], 'same'),
                'pricing_mode' => self::enum($c['pricing_mode'] ?? ($c['rate_basis'] ?? null), self::PRICING_MODES, 'per_room'),
                'default_meal_plan' => self::meal($c['default_meal_plan'] ?? null),
                'taxes_included' => isset($c['taxes_included']) && $c['taxes_included'] !== '' ? (int) (bool) $c['taxes_included'] : 1,
                'tax_pct' => self::num($c['tax_pct'] ?? null),
                'tax_note' => self::strOrNull($c['tax_note'] ?? null, 200),
                'status' => self::enum($c['status'] ?? null, ['draft', 'approved', 'superseded', 'cancelled'], 'draft'),
                'source_type' => self::enum($c['source_type'] ?? null, self::SOURCE_TYPES, 'manual'),
                'source_file' => self::strOrNull($c['source_file'] ?? null, 255),
                'received_at' => Dates::parse($c['received_at'] ?? null),
                'contract_date' => Dates::parse($c['contract_date'] ?? null),
                'contract_type' => self::strOrNull($c['contract_type'] ?? null, 40),
                'hotel_reference' => self::strOrNull($c['hotel_reference'] ?? null, 80),
                'credit_facility' => self::enum($c['credit_facility'] ?? null, self::CREDIT, 'unknown'),
                'min_stay' => self::intOrNull($c['min_stay'] ?? null),
                'max_rooms_per_booking' => self::intOrNull($c['max_rooms_per_booking'] ?? null),
                'release_days' => self::intOrNull($c['release_days'] ?? null),
                'tentative_hold_hours' => self::intOrNull($c['tentative_hold_hours'] ?? null),
                'extra_bed_adult' => self::num($c['extra_bed_adult'] ?? null),
                'max_children_free' => self::intOrNull($c['max_children_free'] ?? null),
                'child_policy_text' => self::strOrNull($c['child_policy_text'] ?? null, 800),
                'guide_policy_text' => self::strOrNull($c['guide_policy_text'] ?? null, 600),
                'foc_policy_text' => self::strOrNull($c['foc_policy_text'] ?? null, 400),
                'cancellation_text' => self::strOrNull($c['cancellation_text'] ?? null, 1200),
                'payment_text' => self::strOrNull($c['payment_text'] ?? null, 800),
                'terms_text' => self::strOrNull($c['terms_text'] ?? null, 20000),
                'voucher_note' => self::strOrNull($c['voucher_note'] ?? null, 300),
                'notes' => self::strOrNull($c['notes'] ?? null, 20000),
            ],
            'room_types' => [],
            'seasons' => [],
            'room_supplements' => [],
            'meal_deltas' => [],
            'meal_prices' => [],
            'supplements' => [],
            'child_rates' => [],
            'guide_policies' => [],
            'foc_rules' => [],
            'offers' => [],
            'cancellation_rules' => [],
            'payment_rules' => [],
            'restrictions' => [],
            'extras' => [],
        ];

        foreach (self::list($in['room_types'] ?? []) as $r) {
            $name = trim((string) ($r['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out['room_types'][] = [
                'id' => self::intOrNull($r['id'] ?? null),
                'name' => $name,
                'code' => self::strOrNull($r['code'] ?? null, 20),
                'priced_as' => self::enum($r['priced_as'] ?? null, ['base', 'supplement'], 'base'),
                'base_room_type' => self::strOrNull($r['base_room_type'] ?? null, 160),
                'room_count' => self::intOrNull($r['room_count'] ?? null),
                'size_sqm' => self::num($r['size_sqm'] ?? null),
                'bed_config' => self::strOrNull($r['bed_config'] ?? null, 120),
                'view' => self::strOrNull($r['view'] ?? null, 60),
                'max_adults' => self::intOrNull($r['max_adults'] ?? null),
                'max_children' => self::intOrNull($r['max_children'] ?? null),
                'max_occupancy' => self::intOrNull($r['max_occupancy'] ?? null),
                'extra_bed_allowed' => isset($r['extra_bed_allowed']) && $r['extra_bed_allowed'] !== '' ? (int) (bool) $r['extra_bed_allowed'] : null,
                'unit_pax_min' => self::intOrNull($r['unit_pax_min'] ?? null),
                'unit_pax_max' => self::intOrNull($r['unit_pax_max'] ?? null),
                'description' => self::strOrNull($r['description'] ?? null, 400),
                'status' => self::enum($r['status'] ?? null, ['active', 'inactive'], 'active'),
            ];
        }

        // seasons: accept the old 'periods' key (date_from/date_to) too
        $seasonsIn = self::list($in['seasons'] ?? ($in['periods'] ?? []));
        foreach ($seasonsIn as $si => $s) {
            $dates = [];
            foreach (self::list($s['dates'] ?? []) as $d) {
                $dates[] = ['from' => Dates::parse($d['from'] ?? ($d['date_from'] ?? null)), 'to' => Dates::parse($d['to'] ?? ($d['date_to'] ?? null)), 'raw_from' => $d['from'] ?? ($d['date_from'] ?? null), 'raw_to' => $d['to'] ?? ($d['date_to'] ?? null)];
            }
            if (!$dates && (isset($s['date_from']) || isset($s['date_to']))) {
                $dates[] = ['from' => Dates::parse($s['date_from'] ?? null), 'to' => Dates::parse($s['date_to'] ?? null), 'raw_from' => $s['date_from'] ?? null, 'raw_to' => $s['date_to'] ?? null];
            }
            $rates = [];
            foreach (self::list($s['rates'] ?? []) as $r) {
                $rates[] = [
                    'room_type' => trim((string) ($r['room_type'] ?? '')),
                    'room_type_id' => self::intOrNull($r['room_type_id'] ?? null),
                    'meal_plan' => self::meal($r['meal_plan'] ?? null) ?? strtoupper(trim((string) ($r['meal_plan'] ?? ''))),
                    'sgl' => self::num($r['sgl'] ?? null),
                    'dbl' => self::num($r['dbl'] ?? null),
                    'tpl' => self::num($r['tpl'] ?? null),
                    'quad' => self::num($r['quad'] ?? null),
                    'unit' => self::num($r['unit'] ?? null),
                    'child_with_bed' => self::num($r['child_with_bed'] ?? null),
                    'child_no_bed' => self::num($r['child_no_bed'] ?? ($r['child_rate'] ?? null)),
                    'note' => self::strOrNull($r['note'] ?? null, 200),
                    'source_row' => $r['source_row'] ?? null,
                ];
            }
            $out['seasons'][] = [
                'id' => self::intOrNull($s['id'] ?? null),
                'name' => self::strOrNull($s['name'] ?? null, 120),
                'kind' => self::enum($s['kind'] ?? null, self::SEASON_KINDS, 'normal'),
                'min_stay' => self::intOrNull($s['min_stay'] ?? null),
                'max_rooms' => self::intOrNull($s['max_rooms'] ?? null),
                'notes' => self::strOrNull($s['notes'] ?? null, 300),
                'dates' => $dates,
                'rates' => $rates,
                'source_rows' => $s['source_rows'] ?? null,
            ];
        }

        foreach (self::list($in['room_supplements'] ?? []) as $r) {
            $out['room_supplements'][] = ['room_type' => trim((string) ($r['room_type'] ?? '')), 'room_type_id' => self::intOrNull($r['room_type_id'] ?? null), 'season' => self::strOrNull($r['season'] ?? null), 'amount' => self::num($r['amount'] ?? null), 'per' => self::enum($r['per'] ?? null, ['room', 'person'], 'room'), 'note' => self::strOrNull($r['note'] ?? null, 200)];
        }
        foreach (self::list($in['meal_deltas'] ?? []) as $r) {
            $out['meal_deltas'][] = ['from' => self::meal($r['from'] ?? ($r['from_plan'] ?? null)), 'to' => self::meal($r['to'] ?? ($r['to_plan'] ?? null)), 'adult' => self::num($r['adult'] ?? null), 'child' => self::num($r['child'] ?? null), 'season' => self::strOrNull($r['season'] ?? null), 'note' => self::strOrNull($r['note'] ?? null, 200)];
        }
        foreach (self::list($in['meal_prices'] ?? []) as $r) {
            $out['meal_prices'][] = ['meal' => self::enum($r['meal'] ?? null, self::MEALS, 'other'), 'name' => self::strOrNull($r['name'] ?? null, 80), 'adult' => self::num($r['adult'] ?? null), 'child' => self::num($r['child'] ?? null), 'note' => self::strOrNull($r['note'] ?? null, 200)];
        }
        foreach (self::list($in['supplements'] ?? []) as $r) {
            $type = self::enum($r['type'] ?? null, self::SUPPLEMENT_TYPES, null);
            if ($type === null) {
                $type = match ($r['type'] ?? '') { 'gala_dinner' => 'gala', 'peak_surcharge' => 'peak', default => 'other' };
            }
            $out['supplements'][] = [
                'type' => $type,
                'name' => trim((string) ($r['name'] ?? '')),
                'date_from' => Dates::parse($r['date_from'] ?? null),
                'date_to' => Dates::parse($r['date_to'] ?? null),
                'season' => self::strOrNull($r['season'] ?? null),
                'meal_plans' => self::mealList($r['meal_plans'] ?? ($r['applies_meal_plans'] ?? null)),
                'room_type' => self::strOrNull($r['room_type'] ?? null, 160),
                'room_type_id' => self::intOrNull($r['room_type_id'] ?? null),
                'per' => self::enum($r['per'] ?? null, self::SUPPLEMENT_PER, 'person'),
                'amount' => self::num($r['amount'] ?? null),
                'amount_sgl' => self::num($r['amount_sgl'] ?? null),
                'amount_dbl' => self::num($r['amount_dbl'] ?? null),
                'amount_tpl' => self::num($r['amount_tpl'] ?? null),
                'child_amount' => self::num($r['child_amount'] ?? null),
                'child_pct' => self::num($r['child_pct'] ?? ($r['child_reduction_pct'] ?? null)),
                'pct_of_rate' => self::num($r['pct_of_rate'] ?? null),
                'compulsory' => isset($r['compulsory']) && $r['compulsory'] !== '' ? (int) (bool) $r['compulsory'] : 1,
                'note' => self::strOrNull($r['note'] ?? ($r['notes'] ?? null), 400),
            ];
        }
        foreach (self::list($in['child_rates'] ?? []) as $r) {
            $out['child_rates'][] = [
                'age_from' => self::num($r['age_from'] ?? null) ?? 0.0,
                'age_to' => self::num($r['age_to'] ?? null),
                'bed' => self::enum($r['bed'] ?? null, self::CHILD_BED, 'any'),
                'meal_plan' => self::meal($r['meal_plan'] ?? null),
                'amount' => self::num($r['amount'] ?? null),
                'pct' => self::num($r['pct'] ?? null),
                'pct_basis' => self::enum($r['pct_basis'] ?? null, self::PCT_BASIS, null),
                'max_children' => self::intOrNull($r['max_children'] ?? null),
                'room_type' => self::strOrNull($r['room_type'] ?? null, 160),
                'note' => self::strOrNull($r['note'] ?? null, 300),
            ];
        }
        foreach (self::list($in['guide_policies'] ?? []) as $r) {
            $out['guide_policies'][] = [
                'role' => self::enum($r['role'] ?? null, self::GUIDE_ROLES, 'guide'),
                'meal_plan' => self::meal($r['meal_plan'] ?? null),
                'amount' => self::num($r['amount'] ?? null),
                'currency' => self::strOrNull($r['currency'] ?? null, 3) ? strtoupper((string) $r['currency']) : null,
                'pct_of' => self::enum($r['pct_of'] ?? null, ['sgl', 'dbl', 'room'], null),
                'pct' => self::num($r['pct'] ?? null),
                'per' => self::enum($r['per'] ?? null, ['person', 'room'], 'room'),
                'free_from_pax' => self::intOrNull($r['free_from_pax'] ?? null),
                'min_rooms' => self::intOrNull($r['min_rooms'] ?? null),
                'accommodation' => self::enum($r['accommodation'] ?? null, self::GUIDE_ACCOMMODATION, 'unknown'),
                'note' => self::strOrNull($r['note'] ?? null, 300),
            ];
        }
        foreach (self::list($in['foc_rules'] ?? []) as $r) {
            $out['foc_rules'][] = ['basis' => self::enum($r['basis'] ?? null, self::FOC_BASIS, 'pax'), 'every_n' => self::intOrNull($r['every_n'] ?? null), 'free_n' => self::intOrNull($r['free_n'] ?? null) ?? 1, 'discount_pct' => self::num($r['discount_pct'] ?? null) ?? 100.0, 'as_what' => self::enum($r['as_what'] ?? null, self::FOC_AS, 'any'), 'note' => self::strOrNull($r['note'] ?? null, 300)];
        }
        foreach (self::list($in['offers'] ?? []) as $r) {
            $out['offers'][] = [
                'kind' => self::enum($r['kind'] ?? null, self::OFFER_KINDS, 'other'), 'name' => trim((string) ($r['name'] ?? '')),
                'book_from' => Dates::parse($r['book_from'] ?? null), 'book_to' => Dates::parse($r['book_to'] ?? null), 'stay_from' => Dates::parse($r['stay_from'] ?? null), 'stay_to' => Dates::parse($r['stay_to'] ?? null),
                'min_nights' => self::intOrNull($r['min_nights'] ?? null), 'min_pax' => self::intOrNull($r['min_pax'] ?? null), 'min_rooms' => self::intOrNull($r['min_rooms'] ?? null),
                'value_pct' => self::num($r['value_pct'] ?? null), 'value_amount' => self::num($r['value_amount'] ?? null), 'free_nights' => self::intOrNull($r['free_nights'] ?? null),
                'per' => self::enum($r['per'] ?? null, self::OFFER_PER, 'room'), 'combinable' => isset($r['combinable']) ? (int) (bool) $r['combinable'] : 0, 'excludes_peak' => isset($r['excludes_peak']) ? (int) (bool) $r['excludes_peak'] : 0,
                'conditions' => self::strOrNull($r['conditions'] ?? null, 600),
            ];
        }
        foreach (self::list($in['cancellation_rules'] ?? []) as $r) {
            $out['cancellation_rules'][] = [
                'date_from' => Dates::parse($r['date_from'] ?? null), 'date_to' => Dates::parse($r['date_to'] ?? null), 'tier' => self::enum($r['tier'] ?? null, self::CANCEL_TIERS, 'all'),
                'min_rooms' => self::intOrNull($r['min_rooms'] ?? null), 'max_rooms' => self::intOrNull($r['max_rooms'] ?? null), 'min_pax' => self::intOrNull($r['min_pax'] ?? null), 'max_pax' => self::intOrNull($r['max_pax'] ?? null),
                'days_from' => self::intOrNull($r['days_from'] ?? null), 'days_to' => self::intOrNull($r['days_to'] ?? null) ?? 0,
                'charge_pct' => self::num($r['charge_pct'] ?? null), 'charge_nights' => self::intOrNull($r['charge_nights'] ?? null), 'note' => self::strOrNull($r['note'] ?? null, 300),
            ];
        }
        foreach (self::list($in['payment_rules'] ?? []) as $r) {
            $out['payment_rules'][] = ['applies' => self::enum($r['applies'] ?? null, self::PAY_APPLIES, 'all'), 'kind' => self::enum($r['kind'] ?? null, self::PAY_KINDS, 'prepay'), 'days_before' => self::intOrNull($r['days_before'] ?? null), 'pct' => self::num($r['pct'] ?? null), 'date_from' => Dates::parse($r['date_from'] ?? null), 'date_to' => Dates::parse($r['date_to'] ?? null), 'note' => self::strOrNull($r['note'] ?? null, 300)];
        }
        foreach (self::list($in['restrictions'] ?? []) as $r) {
            $out['restrictions'][] = ['kind' => self::enum($r['kind'] ?? null, self::RESTRICTION_KINDS, 'other'), 'date_from' => Dates::parse($r['date_from'] ?? null), 'date_to' => Dates::parse($r['date_to'] ?? null), 'value' => self::strOrNull($r['value'] ?? null, 60), 'note' => self::strOrNull($r['note'] ?? null, 300)];
        }
        foreach (self::list($in['extras'] ?? []) as $r) {
            $name = trim((string) ($r['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out['extras'][] = ['category' => self::enum($r['category'] ?? null, self::EXTRA_CATEGORIES, 'other'), 'name' => $name, 'per' => self::enum($r['per'] ?? null, self::EXTRA_PER, 'person'), 'adult' => self::num($r['adult'] ?? null), 'child' => self::num($r['child'] ?? null), 'currency' => self::strOrNull($r['currency'] ?? null, 3), 'note' => self::strOrNull($r['note'] ?? null, 300)];
        }

        // pass-through keys the importer attaches
        foreach (['suggestions', 'source_rows', 'unreadable_rows', 'location', 'issues', 'errors', 'warnings', 'hotel_key'] as $k) {
            if (array_key_exists($k, $in)) {
                $out[$k] = $in[$k];
            }
        }
        return $out;
    }

    /** All date ranges of a sheet's seasons: [[from, to, seasonIndex], ...] */
    public static function seasonRanges(array $sheet): array
    {
        $out = [];
        foreach ($sheet['seasons'] as $si => $s) {
            foreach ($s['dates'] as $d) {
                if ($d['from'] && $d['to']) {
                    $out[] = [$d['from'], $d['to'], $si];
                }
            }
        }
        return $out;
    }

    /** Human label of a season: name or its date ranges. */
    public static function seasonLabel(array $season, int $index): string
    {
        if (!empty($season['name'])) {
            return $season['name'];
        }
        $parts = [];
        foreach ($season['dates'] as $d) {
            $parts[] = ($d['from'] ?? '?') . ' → ' . ($d['to'] ?? '?');
        }
        return $parts ? implode(' & ', $parts) : 'Season ' . ($index + 1);
    }

    // ------------------------------------------------------------------ helpers

    /** Numeric cell -> float|null. Blank, '-', 'n/a', 'x' mean "not offered". */
    public static function num($v): ?float
    {
        if ($v === null) {
            return null;
        }
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }
        if (is_bool($v)) {
            return $v ? 1.0 : 0.0;
        }
        $s = trim((string) $v);
        if ($s === '' || in_array(strtolower($s), ['-', '–', '—', 'n/a', 'na', 'x', 'nil', 'null', 'none', 'nr', 'closed'], true)) {
            return null;
        }
        $neg = str_starts_with($s, '(') && str_ends_with($s, ')') ? -1 : 1; // (25) accounting style
        $s = preg_replace('/[^0-9.\-]/', '', str_replace(['USD', 'usd', 'US$', 'Rs.', 'LKR'], '', $s));
        if ($s === '' || $s === '-' || $s === '.') {
            return null;
        }
        return is_numeric($s) ? $neg * (float) $s : null;
    }

    public static function intOrNull($v): ?int
    {
        if ($v === null || $v === '' || $v === false) {
            return null;
        }
        if (is_numeric($v)) {
            return (int) $v;
        }
        $n = self::num($v);
        return $n === null ? null : (int) $n;
    }

    public static function strOrNull($v, int $max = 255): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }
        return mb_substr($s, 0, $max);
    }

    public static function enum($v, array $allowed, ?string $default): ?string
    {
        $s = strtolower(trim((string) $v));
        if ($s === '') {
            return $default;
        }
        foreach ($allowed as $a) {
            if (strtolower($a) === $s) {
                return $a;
            }
        }
        return $default;
    }

    public static function currency($v): string
    {
        $s = strtoupper(trim((string) $v));
        if ($s === '' || $s === 'US$' || $s === '$') {
            return 'USD';
        }
        return preg_match('/^[A-Z]{3}$/', $s) ? $s : substr(preg_replace('/[^A-Z]/', '', $s) . 'USD', 0, 3);
    }

    /** Any spelling of a meal plan -> RO/BB/HB/FB/AI or null. */
    public static function meal($v): ?string
    {
        if ($v === null) {
            return null;
        }
        $k = strtoupper(preg_replace('/[^a-z]/i', '', (string) $v));
        if ($k === '') {
            return null;
        }
        return match (true) {
            in_array($k, ['RO', 'ROOMONLY', 'EP', 'EUROPEANPLAN', 'ROOM'], true) => 'RO',
            in_array($k, ['BB', 'BEDANDBREAKFAST', 'BEDBREAKFAST', 'CP', 'BREAKFAST', 'BFAST', 'BEDBFAST', 'BNB'], true) => 'BB',
            in_array($k, ['HB', 'HALFBOARD', 'MAP', 'DBB', 'DINNERBEDBREAKFAST'], true) => 'HB',
            in_array($k, ['FB', 'FULLBOARD', 'AP', 'AMERICANPLAN'], true) => 'FB',
            in_array($k, ['AI', 'ALLINCLUSIVE', 'ALLINCL', 'ALLIN', 'AIP'], true) => 'AI',
            default => null,
        };
    }

    /** "HB, FB / AI" -> "HB,FB,AI"; null/'' -> null (= all). */
    public static function mealList($v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_array($v)) {
            $v = implode(',', $v);
        }
        $s = trim((string) $v);
        if ($s === '' || strtolower($s) === 'all') {
            return null;
        }
        $out = [];
        foreach (preg_split('/[\s,\/|&+]+/', $s) as $p) {
            $m = self::meal($p);
            if ($m && !in_array($m, $out, true)) {
                $out[] = $m;
            }
        }
        return $out ? implode(',', $out) : null;
    }

    /** Markets: list of {code, mode} or a "JP, CN" string or "ALL". */
    public static function markets($v): array
    {
        if ($v === null) {
            return [];
        }
        $out = [];
        if (is_string($v)) {
            $s = strtoupper(trim($v));
            if ($s === '' || $s === 'ALL' || $s === 'ALL MARKETS') {
                return [];
            }
            foreach (preg_split('/[\s,\/|]+/', $s) as $p) {
                if ($p !== '') {
                    $mode = 'include';
                    if (str_starts_with($p, '-') || str_starts_with($p, '!')) {
                        $mode = 'exclude';
                        $p = substr($p, 1);
                    }
                    $out[] = ['code' => $p, 'mode' => $mode];
                }
            }
            return $out;
        }
        foreach (self::list($v) as $m) {
            if (is_string($m)) {
                $m = ['code' => $m];
            }
            $code = strtoupper(trim((string) ($m['code'] ?? ($m['market_code'] ?? ''))));
            if ($code === '' || $code === 'ALL') {
                continue;
            }
            $out[] = ['code' => $code, 'mode' => ($m['mode'] ?? 'include') === 'exclude' ? 'exclude' : 'include'];
        }
        return $out;
    }

    private static function list($v): array
    {
        if (!is_array($v)) {
            return [];
        }
        // JSON objects with numeric keys arrive as assoc arrays; keep order
        return array_values(array_filter($v, 'is_array'));
    }
}
