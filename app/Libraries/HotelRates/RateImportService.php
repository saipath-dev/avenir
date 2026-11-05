<?php

namespace App\Libraries\HotelRates;

/**
 * Two-step import: preview (parse + validate, nothing written except the
 * preview record) then commit (save each sheet as a plan, draft or approved).
 *
 * Accepted inputs
 *  1. The legacy wide layout the old Hotel Rate upload used: one row per room
 *     type × season with SRO SBB SHB SFB DRO … TFB (+ optional SAI/DAI/TAI).
 *  2. A long layout: one row per room type × season × meal plan with
 *     SGL / DBL / TPL / QUAD / UNIT / Child with bed / Child no bed.
 *  3. The multi-sheet workbook produced by the panel's "Download template"
 *     (Plan, Rates, Room supplements, Meal deltas, Meal prices, Supplements,
 *     Children, Guide, FOC, Offers, Cancellation, Payment, Restrictions, Extras).
 *  4. Sheets extracted by AiSheetExtractor (already in Sheet shape).
 *
 * Hotels are never created by an import. A sheet whose hotel cannot be matched
 * is reported with suggestions; the person picks the hotel in the preview.
 */
class RateImportService
{
    /** @var object */
    private $db;
    private HotelRateService $svc;
    private string $user;

    /** Column aliases (lower-case, punctuation stripped, spaces removed) => canonical key, for the row layouts. */
    private const ALIASES = [
        'hotel' => 'hotel', 'hotelname' => 'hotel', 'property' => 'hotel', 'supacc' => 'hotel_acc', 'supaccno' => 'hotel_acc', 'hotelcode' => 'hotel_acc', 'code' => 'hotel_acc', 'suppliercode' => 'hotel_acc',
        'location' => 'location', 'city' => 'location', 'area' => 'location',
        'roomtype' => 'room_type', 'room' => 'room_type', 'category' => 'room_type', 'roomcategory' => 'room_type',
        'startdate' => 'sdate', 'sdate' => 'sdate', 'from' => 'sdate', 'validfrom' => 'sdate', 'datefrom' => 'sdate', 'start' => 'sdate',
        'enddate' => 'edate', 'edate' => 'edate', 'to' => 'edate', 'validto' => 'edate', 'dateto' => 'edate', 'end' => 'edate',
        'season' => 'season', 'seasonname' => 'season', 'period' => 'season', 'kind' => 'kind', 'seasonkind' => 'kind',
        'market' => 'market', 'markets' => 'market', 'currency' => 'currency', 'contract' => 'contract', 'contractname' => 'contract', 'plan' => 'contract', 'planname' => 'contract',
        'plantype' => 'plan_type', 'type' => 'plan_type', 'ratecode' => 'rate_code', 'tier' => 'tier', 'bookfrom' => 'book_from', 'bookto' => 'book_to', 'bookbefore' => 'book_to',
        'minstay' => 'min_stay', 'minimumstay' => 'min_stay', 'maxrooms' => 'max_rooms', 'maximumrooms' => 'max_rooms',
        'mealplan' => 'meal_plan', 'meal' => 'meal_plan', 'basis' => 'meal_plan', 'board' => 'meal_plan',
        'sgl' => 'sgl', 'single' => 'sgl', 'dbl' => 'dbl', 'double' => 'dbl', 'tpl' => 'tpl', 'triple' => 'tpl', 'tripple' => 'tpl', 'quad' => 'quad', 'quadruple' => 'quad', 'family' => 'quad', 'unit' => 'unit', 'unitrate' => 'unit', 'villa' => 'unit',
        'child' => 'child_no_bed', 'childrate' => 'child_no_bed', 'childnobed' => 'child_no_bed', 'childwithoutbed' => 'child_no_bed', 'childsharing' => 'child_no_bed', 'childwithbed' => 'child_with_bed', 'childextrabed' => 'child_with_bed',
        'extrabed' => 'extra_bed', 'note' => 'note', 'notes' => 'note', 'remarks' => 'note',
        'sro' => 'sro', 'sbb' => 'sbb', 'shb' => 'shb', 'sfb' => 'sfb', 'dro' => 'dro', 'dbb' => 'dbb', 'dhb' => 'dhb', 'dfb' => 'dfb', 'tro' => 'tro', 'tbb' => 'tbb', 'thb' => 'thb', 'tfb' => 'tfb', 'sai' => 'sai', 'dai' => 'dai', 'tai' => 'tai',
    ];

    /** Sheet-name aliases of the workbook layout => sheet key. */
    private const WORKBOOK_SHEETS = [
        'plan' => 'plan', 'contract' => 'plan', 'header' => 'plan',
        'rates' => 'rates', 'rate' => 'rates', 'seasons' => 'rates',
        'roomtypes' => 'room_types', 'rooms' => 'room_types', 'inventory' => 'room_types',
        'roomsupplements' => 'room_supplements', 'roomsupplement' => 'room_supplements',
        'mealdeltas' => 'meal_deltas', 'mealdelta' => 'meal_deltas', 'mealsupplements' => 'meal_deltas',
        'mealprices' => 'meal_prices', 'meals' => 'meal_prices', 'extrameals' => 'meal_prices',
        'supplements' => 'supplements', 'supplement' => 'supplements', 'gala' => 'supplements',
        'children' => 'child_rates', 'child' => 'child_rates', 'childrates' => 'child_rates', 'childpolicy' => 'child_rates',
        'guide' => 'guide_policies', 'guides' => 'guide_policies', 'guidepolicy' => 'guide_policies',
        'foc' => 'foc_rules', 'focrules' => 'foc_rules',
        'offers' => 'offers', 'offer' => 'offers',
        'cancellation' => 'cancellation_rules', 'cancellations' => 'cancellation_rules', 'cancellationrules' => 'cancellation_rules',
        'payment' => 'payment_rules', 'payments' => 'payment_rules', 'paymentrules' => 'payment_rules',
        'restrictions' => 'restrictions', 'restriction' => 'restrictions',
        'extras' => 'extras', 'excursions' => 'extras',
    ];

    public function __construct($db, ?string $user = null)
    {
        $this->db = $db;
        $this->user = $user ?: 'system';
        $this->svc = new HotelRateService($db, $this->user);
    }

    // ------------------------------------------------------------------
    //  Normalisation
    // ------------------------------------------------------------------

    /**
     * @param array $input  'rows' => flat rows (wide or long layout) OR 'workbook' => [sheetName => rows] OR 'sheets' => Sheet-shaped arrays
     * @param array $defaults  hotel_id, market, currency, contract_name, plan_type, source_type, source_file, parent_contract_id
     * @return array<int, array> sheets with extra keys 'suggestions', 'source_rows', 'unreadable_rows'
     */
    public function normalise(array $input, array $defaults = []): array
    {
        if (isset($input['sheets']) && is_array($input['sheets'])) {
            $sheets = [];
            foreach ($input['sheets'] as $s) {
                if (is_array($s)) {
                    $sheets[] = $this->finishSheet(Sheet::normalise($s), $defaults, 0, []);
                }
            }
            return $sheets;
        }
        if (isset($input['workbook']) && is_array($input['workbook'])) {
            return [$this->fromWorkbook($input['workbook'], $defaults)];
        }
        return $this->fromRows($input['rows'] ?? (array_is_list($input) ? $input : []), $defaults);
    }

    /** Wide or long rows → one sheet per hotel. */
    private function fromRows(array $rows, array $defaults): array
    {
        $groups = [];
        foreach ($rows as $i => $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $r = self::canonicalRow($raw);
            $isEmpty = true;
            foreach ($r as $v) {
                if (trim((string) $v) !== '') { $isEmpty = false; break; }
            }
            if ($isEmpty) {
                continue;
            }
            $hotelKey = !empty($defaults['hotel_id']) ? 'id:' . (int) $defaults['hotel_id']
                : (trim((string) ($r['hotel_acc'] ?? '')) !== '' ? 'acc:' . trim((string) $r['hotel_acc'])
                : 'name:' . mb_strtolower(trim((string) ($r['hotel'] ?? ''))));
            $r['_row'] = $i + 2;
            $groups[$hotelKey][] = $r;
        }
        $sheets = [];
        foreach ($groups as $key => $list) {
            $first = $list[0];
            $seasons = []; // name|from|to => season
            $unreadable = [];
            foreach ($list as $r) {
                $from = Dates::parse($r['sdate'] ?? null);
                $to = Dates::parse($r['edate'] ?? null);
                if (!$from || !$to) {
                    $unreadable[] = $r['_row'];
                }
                $seasonName = trim((string) ($r['season'] ?? ''));
                $sk = $seasonName !== '' ? 'n:' . mb_strtolower($seasonName) : 'd:' . ($from ?: 'bad' . $r['_row']) . '|' . ($to ?: 'bad');
                if (!isset($seasons[$sk])) {
                    $seasons[$sk] = ['name' => $seasonName !== '' ? $seasonName : ($from && $to ? LegacyMigrator::seasonName($from, $to) . " ({$from})" : null), 'kind' => $r['kind'] ?? null, 'min_stay' => $r['min_stay'] ?? null, 'max_rooms' => $r['max_rooms'] ?? null, 'dates' => [], 'rates' => [], 'source_rows' => []];
                }
                $seasons[$sk]['source_rows'][] = $r['_row'];
                $range = ['from' => $from, 'to' => $to, 'raw_from' => $r['sdate'] ?? null, 'raw_to' => $r['edate'] ?? null];
                $have = false;
                foreach ($seasons[$sk]['dates'] as $d) {
                    if ($d['from'] === $from && $d['to'] === $to) { $have = true; break; }
                }
                if (!$have && ($from || $to)) {
                    $seasons[$sk]['dates'][] = $range;
                }
                $room = trim((string) ($r['room_type'] ?? ''));
                $pushRate = static function (array $rate) use (&$seasons, $sk): void {
                    // the same room + meal repeated for a second date range of the same season is one row, not a duplicate
                    foreach ($seasons[$sk]['rates'] as $ex) {
                        if (mb_strtolower($ex['room_type']) === mb_strtolower($rate['room_type']) && $ex['meal_plan'] === $rate['meal_plan']) {
                            $same = true;
                            foreach (['sgl', 'dbl', 'tpl', 'quad', 'unit', 'child_with_bed', 'child_no_bed'] as $k) {
                                if (Sheet::num($ex[$k] ?? null) != Sheet::num($rate[$k] ?? null)) { $same = false; break; }
                            }
                            if ($same) {
                                return;
                            }
                        }
                    }
                    $seasons[$sk]['rates'][] = $rate;
                };
                if (isset($r['meal_plan']) && trim((string) $r['meal_plan']) !== '') {
                    $pushRate(['room_type' => $room, 'meal_plan' => Sheet::meal($r['meal_plan']) ?? strtoupper(trim((string) $r['meal_plan'])), 'sgl' => $r['sgl'] ?? null, 'dbl' => $r['dbl'] ?? null, 'tpl' => $r['tpl'] ?? null, 'quad' => $r['quad'] ?? null, 'unit' => $r['unit'] ?? null, 'child_with_bed' => $r['child_with_bed'] ?? null, 'child_no_bed' => $r['child_no_bed'] ?? null, 'note' => $r['note'] ?? null, 'source_row' => $r['_row']]);
                } else {
                    foreach (['ro' => 'RO', 'bb' => 'BB', 'hb' => 'HB', 'fb' => 'FB', 'ai' => 'AI'] as $suffix => $meal) {
                        $s = Sheet::num($r['s' . $suffix] ?? null); $d = Sheet::num($r['d' . $suffix] ?? null); $t = Sheet::num($r['t' . $suffix] ?? null);
                        if (!$s && !$d && !$t) {
                            continue;
                        }
                        $pushRate(['room_type' => $room, 'meal_plan' => $meal, 'sgl' => $s, 'dbl' => $d, 'tpl' => $t, 'child_no_bed' => $r['child_no_bed'] ?? null, 'child_with_bed' => $r['child_with_bed'] ?? null, 'source_row' => $r['_row']]);
                    }
                }
            }
            $contract = [
                'name' => trim((string) ($first['contract'] ?? ($defaults['contract_name'] ?? ''))),
                'plan_type' => $first['plan_type'] ?? ($defaults['plan_type'] ?? 'contract'),
                'rate_code' => $first['rate_code'] ?? null,
                'tier' => $first['tier'] ?? 'all',
                'markets' => $first['market'] ?? ($defaults['market'] ?? null),
                'currency' => $first['currency'] ?? ($defaults['currency'] ?? $this->svc->settings()->get('default_currency')),
                'book_from' => $first['book_from'] ?? null, 'book_to' => $first['book_to'] ?? null,
                'pricing_mode' => $defaults['pricing_mode'] ?? 'per_room', 'taxes_included' => $defaults['taxes_included'] ?? 1,
                'source_type' => $defaults['source_type'] ?? 'excel', 'source_file' => $defaults['source_file'] ?? null,
                'parent_contract_id' => $defaults['parent_contract_id'] ?? null,
                'extra_bed_adult' => $first['extra_bed'] ?? null,
            ];
            $sheet = Sheet::normalise(['hotel' => ['name' => trim((string) ($first['hotel'] ?? '')), 'sup_acc_no' => trim((string) ($first['hotel_acc'] ?? '')) ?: null], 'contract' => $contract, 'seasons' => array_values($seasons)]);
            $sheet['location'] = trim((string) ($first['location'] ?? ''));
            $sheets[] = $this->finishSheet($sheet, $defaults, count($list), $unreadable, $key);
        }
        return $sheets;
    }

    /** Workbook (map of sheet name => rows) → one sheet. */
    private function fromWorkbook(array $workbook, array $defaults): array
    {
        $parts = [];
        foreach ($workbook as $name => $rows) {
            $k = self::WORKBOOK_SHEETS[preg_replace('/[^a-z]/', '', strtolower((string) $name))] ?? null;
            if ($k && is_array($rows)) {
                $parts[$k] = $rows;
            }
        }
        $sheet = ['hotel' => [], 'contract' => []];
        // Plan sheet: key/value pairs or a one-row table
        foreach ($parts['plan'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $vals = array_values($row);
            $keys = array_keys($row);
            if (count($row) >= 2 && preg_match('/^(field|key|setting|name)$/i', (string) $keys[0])) {
                $field = preg_replace('/[^a-z]/', '', strtolower((string) $vals[0]));
                $value = $vals[1];
                $this->applyPlanField($sheet, $field, $value);
            } else {
                foreach ($row as $key => $value) {
                    $this->applyPlanField($sheet, preg_replace('/[^a-z]/', '', strtolower((string) $key)), $value);
                }
            }
        }
        $sheet['contract'] += ['source_type' => $defaults['source_type'] ?? 'excel', 'source_file' => $defaults['source_file'] ?? null, 'parent_contract_id' => $defaults['parent_contract_id'] ?? null];
        if (empty($sheet['contract']['markets']) && !empty($defaults['market'])) {
            $sheet['contract']['markets'] = $defaults['market'];
        }
        // Rates: long layout grouped into seasons
        $ratesSheet = $this->fromRows($parts['rates'] ?? [], ['hotel_id' => 1]);
        $sheet['seasons'] = $ratesSheet[0]['seasons'] ?? [];
        $unreadable = $ratesSheet[0]['unreadable_rows'] ?? [];
        $sourceRows = $ratesSheet[0]['source_rows'] ?? 0;
        // room types
        foreach ($parts['room_types'] ?? [] as $row) {
            $r = self::canonicalGeneric($row, ['roomtype' => 'name', 'room' => 'name', 'name' => 'name', 'code' => 'code', 'pricedas' => 'priced_as', 'pricing' => 'priced_as', 'baseroom' => 'base_room_type', 'baseroomtype' => 'base_room_type', 'rooms' => 'room_count', 'roomcount' => 'room_count', 'noofrooms' => 'room_count', 'size' => 'size_sqm', 'sizesqm' => 'size_sqm', 'sqm' => 'size_sqm', 'beds' => 'bed_config', 'bedconfig' => 'bed_config', 'view' => 'view', 'maxadults' => 'max_adults', 'maxchildren' => 'max_children', 'maxoccupancy' => 'max_occupancy', 'extrabed' => 'extra_bed_allowed', 'extrabedallowed' => 'extra_bed_allowed', 'unitpaxmin' => 'unit_pax_min', 'unitpaxmax' => 'unit_pax_max', 'description' => 'description']);
            if (!empty($r['name'])) {
                if (!empty($r['base_room_type']) && empty($r['priced_as'])) {
                    $r['priced_as'] = 'supplement';
                }
                $sheet['room_types'][] = $r;
            }
        }
        $lists = [
            'room_supplements' => ['roomtype' => 'room_type', 'room' => 'room_type', 'season' => 'season', 'amount' => 'amount', 'supplement' => 'amount', 'per' => 'per', 'note' => 'note'],
            'meal_deltas' => ['from' => 'from', 'frommealplan' => 'from', 'fromplan' => 'from', 'to' => 'to', 'tomealplan' => 'to', 'toplan' => 'to', 'adult' => 'adult', 'amount' => 'adult', 'child' => 'child', 'season' => 'season', 'note' => 'note'],
            'meal_prices' => ['meal' => 'meal', 'name' => 'name', 'adult' => 'adult', 'amount' => 'adult', 'child' => 'child', 'note' => 'note'],
            'supplements' => ['type' => 'type', 'name' => 'name', 'from' => 'date_from', 'datefrom' => 'date_from', 'to' => 'date_to', 'dateto' => 'date_to', 'season' => 'season', 'mealplans' => 'meal_plans', 'mealplan' => 'meal_plans', 'basis' => 'meal_plans', 'roomtype' => 'room_type', 'room' => 'room_type', 'per' => 'per', 'amount' => 'amount', 'sgl' => 'amount_sgl', 'dbl' => 'amount_dbl', 'tpl' => 'amount_tpl', 'childamount' => 'child_amount', 'child' => 'child_amount', 'childpct' => 'child_pct', 'child%' => 'child_pct', 'pctofrate' => 'pct_of_rate', '%ofrate' => 'pct_of_rate', 'compulsory' => 'compulsory', 'note' => 'note'],
            'child_rates' => ['agefrom' => 'age_from', 'from' => 'age_from', 'ageto' => 'age_to', 'to' => 'age_to', 'bed' => 'bed', 'mealplan' => 'meal_plan', 'basis' => 'meal_plan', 'amount' => 'amount', 'pct' => 'pct', '%' => 'pct', 'pctbasis' => 'pct_basis', 'of' => 'pct_basis', 'maxchildren' => 'max_children', 'roomtype' => 'room_type', 'note' => 'note'],
            'guide_policies' => ['role' => 'role', 'mealplan' => 'meal_plan', 'basis' => 'meal_plan', 'amount' => 'amount', 'currency' => 'currency', 'pctof' => 'pct_of', 'pct' => 'pct', '%' => 'pct', 'per' => 'per', 'freefrompax' => 'free_from_pax', 'focfrompax' => 'free_from_pax', 'minrooms' => 'min_rooms', 'accommodation' => 'accommodation', 'note' => 'note'],
            'foc_rules' => ['basis' => 'basis', 'every' => 'every_n', 'everyn' => 'every_n', 'free' => 'free_n', 'freen' => 'free_n', 'discount' => 'discount_pct', 'discountpct' => 'discount_pct', 'as' => 'as_what', 'aswhat' => 'as_what', 'note' => 'note'],
            'offers' => ['kind' => 'kind', 'type' => 'kind', 'name' => 'name', 'bookfrom' => 'book_from', 'bookto' => 'book_to', 'stayfrom' => 'stay_from', 'stayto' => 'stay_to', 'minnights' => 'min_nights', 'minpax' => 'min_pax', 'minrooms' => 'min_rooms', 'valuepct' => 'value_pct', 'pct' => 'value_pct', '%' => 'value_pct', 'valueamount' => 'value_amount', 'amount' => 'value_amount', 'freenights' => 'free_nights', 'per' => 'per', 'combinable' => 'combinable', 'excludespeak' => 'excludes_peak', 'conditions' => 'conditions', 'note' => 'conditions'],
            'cancellation_rules' => ['from' => 'date_from', 'stayfrom' => 'date_from', 'datefrom' => 'date_from', 'to' => 'date_to', 'stayto' => 'date_to', 'dateto' => 'date_to', 'tier' => 'tier', 'minrooms' => 'min_rooms', 'maxrooms' => 'max_rooms', 'minpax' => 'min_pax', 'maxpax' => 'max_pax', 'daysfrom' => 'days_from', 'daysbeforefrom' => 'days_from', 'daysto' => 'days_to', 'daysbeforeto' => 'days_to', 'chargepct' => 'charge_pct', 'charge' => 'charge_pct', '%' => 'charge_pct', 'chargenights' => 'charge_nights', 'nights' => 'charge_nights', 'note' => 'note'],
            'payment_rules' => ['applies' => 'applies', 'appliesto' => 'applies', 'kind' => 'kind', 'type' => 'kind', 'daysbefore' => 'days_before', 'days' => 'days_before', 'pct' => 'pct', '%' => 'pct', 'from' => 'date_from', 'to' => 'date_to', 'note' => 'note'],
            'restrictions' => ['kind' => 'kind', 'type' => 'kind', 'from' => 'date_from', 'datefrom' => 'date_from', 'to' => 'date_to', 'dateto' => 'date_to', 'value' => 'value', 'note' => 'note'],
            'extras' => ['category' => 'category', 'name' => 'name', 'per' => 'per', 'adult' => 'adult', 'amount' => 'adult', 'child' => 'child', 'currency' => 'currency', 'note' => 'note'],
        ];
        foreach ($lists as $k => $aliases) {
            foreach ($parts[$k] ?? [] as $row) {
                $r = self::canonicalGeneric($row, $aliases);
                $empty = true;
                foreach ($r as $v) {
                    if (trim((string) $v) !== '') { $empty = false; break; }
                }
                if (!$empty) {
                    $sheet[$k][] = $r;
                }
            }
        }
        $sheet = Sheet::normalise($sheet);
        if (!empty($defaults['hotel_id'])) {
            $sheet['hotel']['id'] = (int) $defaults['hotel_id'];
        }
        return $this->finishSheet($sheet, $defaults, $sourceRows, $unreadable);
    }

    private function applyPlanField(array &$sheet, string $field, $value): void
    {
        $map = [
            'hotel' => ['hotel', 'name'], 'hotelname' => ['hotel', 'name'], 'hotelcode' => ['hotel', 'sup_acc_no'], 'suppliercode' => ['hotel', 'sup_acc_no'], 'supaccno' => ['hotel', 'sup_acc_no'],
            'plan' => ['contract', 'name'], 'planname' => ['contract', 'name'], 'name' => ['contract', 'name'], 'contract' => ['contract', 'name'], 'contractname' => ['contract', 'name'],
            'plantype' => ['contract', 'plan_type'], 'type' => ['contract', 'plan_type'], 'ratecode' => ['contract', 'rate_code'], 'tier' => ['contract', 'tier'], 'tierminrooms' => ['contract', 'tier_min_rooms'], 'tiermaxrooms' => ['contract', 'tier_max_rooms'], 'tierminpax' => ['contract', 'tier_min_pax'], 'tiermaxpax' => ['contract', 'tier_max_pax'],
            'priority' => ['contract', 'priority'], 'markets' => ['contract', 'markets'], 'market' => ['contract', 'markets'], 'validfrom' => ['contract', 'valid_from'], 'validto' => ['contract', 'valid_to'], 'from' => ['contract', 'valid_from'], 'to' => ['contract', 'valid_to'],
            'bookfrom' => ['contract', 'book_from'], 'bookto' => ['contract', 'book_to'], 'bookbefore' => ['contract', 'book_to'], 'currency' => ['contract', 'currency'], 'billingcurrency' => ['contract', 'billing_currency'],
            'pricingmode' => ['contract', 'pricing_mode'], 'pricing' => ['contract', 'pricing_mode'], 'ratesare' => ['contract', 'pricing_mode'], 'defaultmealplan' => ['contract', 'default_meal_plan'], 'taxesincluded' => ['contract', 'taxes_included'], 'taxpct' => ['contract', 'tax_pct'], 'tax' => ['contract', 'tax_pct'], 'taxnote' => ['contract', 'tax_note'],
            'receivedat' => ['contract', 'received_at'], 'received' => ['contract', 'received_at'], 'contractdate' => ['contract', 'contract_date'], 'contracttype' => ['contract', 'contract_type'], 'hotelreference' => ['contract', 'hotel_reference'], 'reference' => ['contract', 'hotel_reference'], 'creditfacility' => ['contract', 'credit_facility'],
            'minstay' => ['contract', 'min_stay'], 'maxroomsperbooking' => ['contract', 'max_rooms_per_booking'], 'releasedays' => ['contract', 'release_days'], 'tentativeholdhours' => ['contract', 'tentative_hold_hours'], 'extrabedadult' => ['contract', 'extra_bed_adult'], 'extrabed' => ['contract', 'extra_bed_adult'], 'maxchildrenfree' => ['contract', 'max_children_free'],
            'childpolicy' => ['contract', 'child_policy_text'], 'guidepolicy' => ['contract', 'guide_policy_text'], 'focpolicy' => ['contract', 'foc_policy_text'], 'cancellationpolicy' => ['contract', 'cancellation_text'], 'cancellation' => ['contract', 'cancellation_text'], 'paymentterms' => ['contract', 'payment_text'], 'payment' => ['contract', 'payment_text'], 'terms' => ['contract', 'terms_text'], 'termsandconditions' => ['contract', 'terms_text'], 'vouchernote' => ['contract', 'voucher_note'], 'notes' => ['contract', 'notes'], 'note' => ['contract', 'notes'],
        ];
        if (isset($map[$field]) && $value !== null && trim((string) $value) !== '') {
            [$section, $key] = $map[$field];
            $sheet[$section][$key] = is_string($value) ? trim($value) : $value;
        }
    }

    /** Resolve the hotel, attach suggestions and counts. */
    private function finishSheet(array $sheet, array $defaults, int $sourceRows, array $unreadable, ?string $key = null): array
    {
        $hotel = $sheet['hotel'];
        $resolved = null;
        if (!empty($defaults['hotel_id'])) {
            $resolved = $this->svc->getHotel((int) $defaults['hotel_id']);
        } elseif (!empty($hotel['id'])) {
            $resolved = $this->svc->getHotel((int) $hotel['id']);
        } elseif (!empty($hotel['sup_acc_no'])) {
            $resolved = $this->svc->findHotelByAcc($hotel['sup_acc_no']);
        }
        $suggestions = [];
        if (!$resolved && $hotel['name'] !== '') {
            $name = $hotel['name'];
            $resolved = $this->row("SELECT * FROM hr2_hotel WHERE status <> 'merged' AND LOWER(name) = ? ORDER BY id LIMIT 1", [mb_strtolower($name)]);
            if (!$resolved) {
                $acct = $this->row('SELECT hotel_id FROM hr2_hotel_account WHERE LOWER(legacy_name) = ?', [mb_strtolower($name)]);
                $resolved = $acct ? $this->svc->getHotel((int) $acct['hotel_id']) : null;
            }
            if (!$resolved) {
                $k = HotelRateService::nameKey($name);
                if ($k !== '') {
                    foreach ($this->rows("SELECT id, name, primary_sup_acc_no FROM hr2_hotel WHERE status <> 'merged'") as $cand) {
                        if (HotelRateService::nameKey($cand['name']) === $k) {
                            $resolved = $cand;
                            break;
                        }
                    }
                }
            }
            if (!$resolved) {
                $suggestions = $this->suggestHotels($name);
            }
        }
        if ($resolved) {
            $sheet['hotel'] = ['id' => (int) $resolved['id'], 'name' => $resolved['name'], 'sup_acc_no' => $resolved['primary_sup_acc_no']];
        }
        if ($sheet['contract']['name'] === '') {
            $ranges = Sheet::seasonRanges($sheet);
            $froms = array_column($ranges, 0); $tos = array_column($ranges, 1);
            $sheet['contract']['name'] = ($sheet['contract']['valid_from'] ?: ($froms ? min($froms) : '?')) . ' – ' . ($sheet['contract']['valid_to'] ?: ($tos ? max($tos) : '?')) . ' ' . ($sheet['contract']['plan_type'] === 'contract' ? 'rates' : str_replace('_', ' ', $sheet['contract']['plan_type']));
        }
        $sheet['suggestions'] = $suggestions;
        $sheet['source_rows'] = $sourceRows;
        $sheet['unreadable_rows'] = $unreadable;
        $sheet['hotel_key'] = $key;
        return $sheet;
    }

    // ------------------------------------------------------------------
    //  Preview / commit
    // ------------------------------------------------------------------

    /** @return array{import_id:int, sheets:array, totals:array} */
    public function preview(array $input, array $defaults = []): array
    {
        $sheets = $this->normalise($input, $defaults);
        $totals = ['ok' => 0, 'warning' => 0, 'error' => 0];
        foreach ($sheets as &$sheet) {
            $sheet['issues'] = $this->validateSheet($sheet);
            $c = RateValidator::count($sheet['issues']);
            $sheet['errors'] = $c['errors'];
            $sheet['warnings'] = $c['warnings'];
            if ($c['errors'] > 0) { $totals['error']++; } elseif ($c['warnings'] > 0) { $totals['warning']++; } else { $totals['ok']++; }
        }
        unset($sheet);
        $rowsTotal = isset($input['rows']) ? count($input['rows']) : (isset($input['workbook']) ? array_sum(array_map('count', array_filter($input['workbook'], 'is_array'))) : count($sheets));
        $this->db->query(
            'INSERT INTO hr2_import (hotel_id, source, file_name, status, rows_total, rows_ok, rows_warning, rows_error, payload, created_by, created_at) VALUES (?, ?, ?, \'preview\', ?, ?, ?, ?, ?, ?, ?)',
            [!empty($defaults['hotel_id']) ? (int) $defaults['hotel_id'] : null, in_array($defaults['source_type'] ?? '', ['excel', 'ai', 'manual', 'migration', 'email'], true) ? $defaults['source_type'] : 'excel', $defaults['source_file'] ?? null, $rowsTotal, $totals['ok'], $totals['warning'], $totals['error'], json_encode(['defaults' => $defaults, 'sheets' => $sheets], JSON_UNESCAPED_UNICODE), $this->user, date('Y-m-d H:i:s')]
        );
        $importId = (int) $this->db->insertID();
        return ['import_id' => $importId, 'sheets' => $sheets, 'totals' => $totals];
    }

    /**
     * @param array $opts  'hotel_overrides' => [i => hotel_id], 'sheets' => [i => edited sheet] (from the preview editor),
     *                     'acknowledge_warnings' => bool, 'approve' => bool (default false: save as drafts), 'supersede' => bool
     */
    public function commit(int $importId, array $opts = []): array
    {
        $imp = $this->row('SELECT * FROM hr2_import WHERE id = ?', [$importId]);
        if (!$imp) {
            throw new \RuntimeException("Import {$importId} not found.");
        }
        if ($imp['status'] !== 'preview') {
            throw new \RuntimeException("Import {$importId} was already {$imp['status']}.");
        }
        $payload = json_decode((string) $imp['payload'], true) ?: [];
        $sheets = $payload['sheets'] ?? [];
        $overrides = $opts['hotel_overrides'] ?? [];
        $edited = $opts['sheets'] ?? [];
        $approve = !empty($opts['approve']);
        $results = [];
        $committed = 0;
        $contractIds = [];
        foreach ($sheets as $i => $sheet) {
            if (isset($edited[$i]) && is_array($edited[$i])) {
                $keep = ['suggestions' => $sheet['suggestions'] ?? [], 'source_rows' => $sheet['source_rows'] ?? 0];
                $sheet = Sheet::normalise($edited[$i]) + $keep;
            }
            if (isset($overrides[$i]) && (int) $overrides[$i] > 0) {
                $h = $this->svc->getHotel((int) $overrides[$i]);
                if ($h) {
                    $sheet['hotel'] = ['id' => (int) $h['id'], 'name' => $h['name'], 'sup_acc_no' => $h['primary_sup_acc_no']];
                }
            }
            if (!empty($opts['supersede']) && !empty($sheet['hotel']['id'])) {
                foreach ($this->svc->existingPlans((int) $sheet['hotel']['id']) as $ex) {
                    if (($ex['plan_type'] ?? '') !== ($sheet['contract']['plan_type'] ?? 'contract')) {
                        continue;
                    }
                    foreach (Sheet::seasonRanges($sheet) as [$rf, $rt]) {
                        foreach ($ex['ranges'] as [$ef, $et]) {
                            if (Dates::overlaps($rf, $rt, $ef, $et)) {
                                $this->svc->supersedeContract((int) $ex['contract_id']);
                                continue 3;
                            }
                        }
                    }
                }
            }
            try {
                $res = $this->svc->saveContract($sheet, ['approve' => $approve, 'acknowledge_warnings' => !empty($opts['acknowledge_warnings']), 'import_id' => $importId]);
            } catch (\Throwable $e) {
                $res = ['saved' => false, 'approved' => false, 'contract_id' => null, 'issues' => [['level' => 'error', 'code' => 'E99', 'message' => $e->getMessage(), 'section' => 'header', 'season' => null, 'row' => null]]];
            }
            if ($res['saved']) {
                $committed++;
                if ($res['contract_id']) {
                    $contractIds[] = (int) $res['contract_id'];
                    $this->db->query('UPDATE hr2_contract SET source_type = ?, source_file = ?, import_id = ? WHERE id = ?', [$imp['source'], $imp['file_name'], $importId, $res['contract_id']]);
                }
            }
            $results[] = ['sheet' => $i, 'hotel' => $sheet['hotel']['name'] ?? '', 'hotel_id' => $sheet['hotel']['id'] ?? null, 'plan' => $sheet['contract']['name'] ?? '', 'saved' => $res['saved'], 'approved' => $res['approved'], 'contract_id' => $res['contract_id'], 'issues' => $res['issues'], 'needs_acknowledgement' => $res['needs_acknowledgement'] ?? false];
        }
        $status = $committed === 0 ? 'failed' : 'committed';
        $this->db->query('UPDATE hr2_import SET status = ?, committed_at = ?, contract_id = ?, contract_ids = ?, payload = ? WHERE id = ?', [$status, date('Y-m-d H:i:s'), $contractIds[0] ?? null, implode(',', $contractIds) ?: null, json_encode(['defaults' => $payload['defaults'] ?? [], 'sheets' => $sheets, 'results' => $results], JSON_UNESCAPED_UNICODE), $importId]);
        return ['import_id' => $importId, 'status' => $status, 'results' => $results];
    }

    public function getImport(int $importId): ?array
    {
        $imp = $this->row('SELECT * FROM hr2_import WHERE id = ?', [$importId]);
        if ($imp) {
            $imp['payload'] = json_decode((string) $imp['payload'], true);
        }
        return $imp ?: null;
    }

    public function recentImports(int $limit = 30): array
    {
        return $this->rows('SELECT i.id, i.hotel_id, i.source, i.file_name, i.status, i.rows_total, i.rows_ok, i.rows_warning, i.rows_error, i.contract_ids, i.created_by, i.created_at, i.committed_at, h.name AS hotel_name FROM hr2_import i LEFT JOIN hr2_hotel h ON h.id = i.hotel_id ORDER BY i.id DESC LIMIT ' . (int) $limit);
    }

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    public function validateSheet(array $sheet): array
    {
        $issues = $this->svc->validateSheet($sheet);
        foreach ($sheet['unreadable_rows'] ?? [] as $rowNo) {
            $issues[] = ['level' => 'error', 'code' => 'E03', 'message' => "Spreadsheet row {$rowNo}: start or end date could not be read (use 01.05.2026 or 2026-05-01).", 'section' => 'seasons', 'season' => null, 'row' => $rowNo];
        }
        return $issues;
    }

    /** Closest hotel names for an unmatched spelling, best first. */
    public function suggestHotels(string $name, int $limit = 8): array
    {
        $key = HotelRateService::nameKey($name);
        if ($key === '') {
            return [];
        }
        $scored = [];
        foreach ($this->rows("SELECT h.id, h.name, h.primary_sup_acc_no, l.name AS location_name FROM hr2_hotel h LEFT JOIN hr2_location l ON l.id = h.location_id WHERE h.status <> 'merged'") as $cand) {
            $ck = HotelRateService::nameKey($cand['name']);
            if ($ck === '') {
                continue;
            }
            similar_text($key, $ck, $pct);
            $lev = levenshtein(substr($key, 0, 60), substr($ck, 0, 60));
            $score = max($pct, 100 - ($lev * 100 / max(strlen($key), strlen($ck))));
            if (str_contains($ck, $key) || str_contains($key, $ck)) {
                $score = max($score, 85);
            }
            if ($score >= 60) {
                $cand['score'] = round($score);
                $scored[] = $cand;
            }
        }
        usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $limit);
    }

    /** Map any header spelling of the row layouts to canonical keys. */
    public static function canonicalRow(array $raw): array
    {
        $out = [];
        foreach ($raw as $k => $v) {
            $key = preg_replace('/[^a-z0-9]+/', '', mb_strtolower(trim((string) $k)));
            $canon = self::ALIASES[$key] ?? null;
            if ($canon === null) {
                continue;
            }
            $out[$canon] = is_string($v) ? trim($v) : $v;
        }
        return $out;
    }

    private static function canonicalGeneric(array $raw, array $aliases): array
    {
        $out = [];
        foreach ($raw as $k => $v) {
            $key = preg_replace('/[^a-z0-9%]+/', '', mb_strtolower(trim((string) $k)));
            $canon = $aliases[$key] ?? null;
            if ($canon === null) {
                continue;
            }
            $out[$canon] = is_string($v) ? trim($v) : $v;
        }
        return $out;
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
