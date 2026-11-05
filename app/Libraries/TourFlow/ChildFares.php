<?php

namespace App\Libraries\TourFlow;

/**
 * tf-child-r1 (28 Oct 2026, Shantha) — the Child Fare Review of an inquiry.
 *
 * When the party has children, every thing on the itinerary that is charged per person — each hotel stay, each entrance
 * or visit, a safari park's entrance, a lunch or dinner, a per-person excursion — is listed with the adults and the
 * children, each child's age and bedding, the master's policy and the child rate it holds. The system SUGGESTS
 * "Applicable" or "Not applicable" from the master data; nothing counts until a person presses one of the two:
 *
 *   Applicable      the recorded child rate is charged, shown apart from the adults' figure
 *   Not applicable  "Child: no charge", with the reason (free admission, included in the room / sharing the parents'
 *                   bed, charged per vehicle, or the reason typed)
 *
 * Unknown cases stay unselected and read "Needs checking"; an unchecked item is never taken as free, and never at the
 * adult rate — the quotation cannot be produced until every item is confirmed. A decision keeps a fingerprint of what
 * it was made on (the children's ages and bedding, the party, the item and its rates); when any of that changes the
 * item reads "Review again" and stops the quotation once more. A child with no charge for an item is still a passenger:
 * the party, the vehicle and the room occupancy never change here.
 *
 * Vehicle-based charges (a jeep at a safari park) are listed for completeness and can only be confirmed as
 * "Not applicable — charged per vehicle": the jeep charge stays as it is; the park's entrance tickets are a separate item.
 */
final class ChildFares
{
    public const TABLE = 'tf_child_review';
    public const BEDS = ['no_bed' => 'shares a bed (no extra bed)', 'extra_bed' => 'extra bed', 'own_bed' => 'own bed / own room'];
    public const REASONS = [
        'free'        => 'Free admission for children',
        'in_room'     => 'Included in the room rate / sharing the parents\' bed',
        'per_vehicle' => 'Charged per vehicle, not per person',
        'other'       => 'Other (say why)',
    ];

    private $db;
    private string $user;
    private ?int $userId;
    private ?string $name;

    public function __construct($db, string $user = '', ?int $userId = null, ?string $name = null)
    {
        $this->db = $db;
        $this->user = $user;
        $this->userId = $userId;
        $this->name = $name;
    }

    public static function ready($db): bool
    {
        try {
            return (bool) $db->query("SHOW TABLES LIKE 'tf_child_review'")->getRowArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ------------------------------------------------------------------ the children

    /** The children as recorded on the inquiry: [{age:?float, bed:string}], always as many as the party has children. */
    /** tf-safari-r1: each child's age (null where not given), in order — what the pricing reads the place's age rules against. */
    public static function ages(array $it): array
    {
        return array_map(static fn ($c) => $c['age'], self::children($it));
    }

    public static function children(array $it): array
    {
        $n = max(0, (int) ($it['pax_children'] ?? 0));
        $list = json_decode((string) ($it['child_json'] ?? ''), true);
        $list = is_array($list) ? array_values($list) : [];
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $c = is_array($list[$i] ?? null) ? $list[$i] : [];
            $age = isset($c['age']) && $c['age'] !== '' && $c['age'] !== null && is_numeric($c['age']) ? round((float) $c['age'], 2) : null;
            $bed = isset($c['bed']) && array_key_exists((string) $c['bed'], self::BEDS) ? (string) $c['bed'] : '';
            $out[] = ['n' => $i + 1, 'age' => $age, 'bed' => $bed];
        }
        return $out;
    }

    /** Save the children's ages and bedding (as many as the party has children). */
    public function saveChildren(int $intakeId, array $rows): array
    {
        $it = $this->db->query('SELECT id, pax_children, child_json FROM tf_intake WHERE id = ?', [$intakeId])->getRowArray();
        if (!$it) {
            throw new \RuntimeException('Inquiry not found.');
        }
        $n = max(0, (int) $it['pax_children']);
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $r = is_array($rows[$i] ?? null) ? $rows[$i] : [];
            $age = isset($r['age']) && $r['age'] !== '' && $r['age'] !== null ? $r['age'] : null;
            if ($age !== null && (!is_numeric($age) || (float) $age < 0 || (float) $age > 17.99)) {
                throw new \InvalidArgumentException('Child ' . ($i + 1) . ': the age must be a number from 0 to 17.99 (12 and over is usually an adult — then count them as an adult).');
            }
            $bed = isset($r['bed']) && array_key_exists((string) $r['bed'], self::BEDS) ? (string) $r['bed'] : '';
            $out[] = ['age' => $age === null ? null : round((float) $age, 2), 'bed' => $bed];
        }
        $was = (string) ($it['child_json'] ?? '');
        $now = json_encode($out, JSON_UNESCAPED_UNICODE);
        $this->db->query('UPDATE tf_intake SET child_json = ? WHERE id = ?', [$now, $intakeId]);
        if ($was !== $now) {
            (new Activity($this->db, $this->user, $this->userId, $this->name))->record('inquiry', $intakeId, $intakeId, null, 'child_fares',
                'Children for the Child Fare Review: ' . implode('; ', array_map(static fn ($c, $i) => 'child ' . ($i + 1) . ' age ' . ($c['age'] ?? '?') . ($c['bed'] ? ', ' . self::BEDS[$c['bed']] : ''), $out, array_keys($out))),
                [['what' => 'children (ages, bedding)', 'from' => $was !== '' ? $was : null, 'to' => $now]]);
        }
        return $out;
    }

    // ------------------------------------------------------------------ the items

    private static function money($v): ?float
    {
        return $v === null || $v === '' ? null : round((float) $v, 2);
    }

    private static function ageText(?float $a): string
    {
        return $a === null ? 'age not given' : (floor($a) == $a ? (int) $a : $a) . ' yrs';
    }

    /**
     * Everything per person on the itinerary, with the system's suggestion and the figures. Each item:
     * key, kind, title, days, basis, policy, adults, children, suggestion (applicable|not_applicable|unknown), suggest_why,
     * suggest_reason (for not applicable), adult_calc, child_calc (as the suggestion would charge), child_total (USD, or
     * null when it cannot be worked out), can_apply (bool), apply_block (why not), vehicle (bool), fingerprint.
     */
    public function items(array $it): array
    {
        $adults = (int) ($it['pax_adults'] ?? 0);
        $kids = self::children($it);
        $nKids = count($kids);
        if ($nKids === 0) {
            return [];
        }
        $common = ['adults' => $adults, 'children' => $nKids, 'kids' => array_map(static fn ($c) => [$c['age'], $c['bed']], $kids), 'pax' => (int) ($it['pax_total'] ?? 0)];
        $out = [];

        // ---- hotel stays: nights in a row at one hotel on one contract, room type and meal plan
        $stays = [];
        foreach (($it['days'] ?? []) as $d) {
            if (trim((string) ($d['hotel_name_raw'] ?? '')) === '') {
                continue;
            }
            $k = mb_strtolower(trim((string) $d['hotel_name_raw'])) . '|' . (int) ($d['rate_plan_id'] ?? 0) . '|' . (int) ($d['room_type_id'] ?? 0) . '|' . strtoupper((string) ($d['meal_plan'] ?? ''));
            $last = end($stays);
            if ($last && $last['k'] === $k && (int) $d['day_no'] === $last['to'] + 1) {
                $stays[key($stays)]['to'] = (int) $d['day_no'];
                $stays[key($stays)]['nights'][] = $d;
            } else {
                $stays[] = ['k' => $k, 'from' => (int) $d['day_no'], 'to' => (int) $d['day_no'], 'nights' => [$d]];
            }
        }
        foreach ($stays as $st) {
            $out[] = $this->hotelItem($st, $kids, $adults, $common);
        }

        // ---- the Entrance & Activities lines, the meals and a safari park's two charges
        foreach (($it['services'] ?? []) as $s) {
            if (!empty($s['free_visit_id']) || in_array((string) ($s['pay_mode'] ?? ''), ServiceLookup::FREE_MODES, true) || (string) ($s['match_state'] ?? '') === 'no_charge') {
                continue;   // nothing is paid for it by anybody
            }
            if (!empty($s['meal_id'])) {
                $out[] = $this->mealItem($s, $kids, $common);
                continue;
            }
            $park = !empty($s['place_id']) && ((isset($s['jeep_usd']) && $s['jeep_usd'] !== null && $s['jeep_usd'] !== '') || (string) ($s['kind'] ?? '') === 'safari');
            if (!$park && !ServiceLookup::followsParty($s) && empty($s['place_id'])) {
                continue;   // not charged per person (tuk tuks, transport, other): nothing for a child to differ on
            }
            $out[] = $this->entranceItem($s, $kids, $adults, $common, $park);
            if ($park) {
                $out[] = $this->jeepItem($s, $common);
            }
        }

        // ---- per-person excursions
        foreach (($it['excursions'] ?? []) as $x) {
            if ((string) ($x['unit'] ?? '') !== 'per_person') {
                continue;
            }
            $out[] = $this->excursionItem($x, $kids, $adults, $common);
        }
        return $out;
    }

    private function finish(array $item, array $common, array $figures): array
    {
        $item['adults'] = $common['adults'];
        $item['children'] = $common['children'];
        $item['fingerprint'] = sha1(json_encode([$item['key'], $common, $figures], JSON_UNESCAPED_UNICODE));
        return $item;
    }

    private function entranceItem(array $s, array $kids, int $adults, array $common, bool $park): array
    {
        $unit = self::money($s['unit_usd'] ?? null);
        // the master's own child figure: a place of the Entrance Fee Master (rate_child / child_usd); the old list has none
        $child = !empty($s['place_id']) ? self::money($s['child_usd'] ?? null) : null;
        $n = count($kids);
        $title = (string) $s['title'];
        $where = !empty($s['place_id']) ? 'Place to Visit / Entrance Fee Master' . (!empty($s['fee_code']) ? ' — ' . $s['fee_code'] : '') : (!empty($s['fee_code']) || !empty($s['fee_id']) ? 'priced list (old Entrance Fee list) — ' . ($s['fee_code'] ?: '#' . $s['fee_id']) : 'not priced');
        $item = ['key' => 'svc:' . (int) $s['id'], 'kind' => $park ? 'park' : 'entrance', 'title' => $park ? $title . ' — park entrance' : $title, 'day_from' => (int) $s['day_no'], 'day_to' => (int) $s['day_no'],
            'basis' => 'per person' . ($park ? ' (the jeep is a separate, per-vehicle item)' : ''), 'source' => $where, 'vehicle' => false,
            'adult_calc' => $unit !== null ? $adults . ' adult' . ($adults === 1 ? '' : 's') . ' × USD ' . number_format($unit, 2) . ' = USD ' . number_format($adults * $unit, 2) : 'adult rate not known yet',
            'adult_total' => $unit !== null ? round($adults * $unit, 2) : null];
        if ($unit === null) {
            $msg = !empty($s['place_id']) ? 'The master has no rate for this day — complete it there first.' : 'Not priced yet (see Entrance & Activities): price it first, then decide the child fare.';
            $item += ['policy' => !empty($s['place_id']) ? 'no rate for the day in the master' : 'not priced yet', 'suggestion' => 'unknown', 'suggest_why' => $msg, 'child_rate' => null,
                'child_calc' => null, 'child_total' => null, 'can_apply' => false, 'apply_block' => $msg];
        } elseif ($child === null) {
            $item += ['policy' => 'no child rate recorded', 'suggestion' => 'unknown', 'suggest_why' => 'Needs checking: the master records no child rate for this place. If children pay, enter the child rate in the Entrance Fee Master (the adult rate for children too, if that is the rule); if they enter free, confirm "Not applicable".',
                'child_rate' => null, 'child_calc' => null, 'child_total' => null, 'can_apply' => false, 'apply_block' => 'The master holds no child rate for it — enter one in the Entrance Fee Master first.'];
        } elseif (!empty($s['place_id']) && (($ru = EntranceMaster::rules($this->db, (int) $s['place_id']))['age_from'] !== null || $ru['age_to'] !== null)) {
            // tf-safari-r1: the place's age rules decide each child — free under the age, the child rate within it, the adult rate above
            $sp = EntranceMaster::childSplit($unit, $child, array_map(static fn ($k) => $k['age'], $kids), $n, $ru['age_from'], $ru['age_to']);
            $policy = ($child !== null ? 'child rate USD ' . number_format($child, 2) . ' a child. ' : '') . EntranceMaster::ageRuleText($ru['age_from'], $ru['age_to']);
            if ($sp['total'] === null) {
                $item += ['policy' => $policy, 'suggestion' => 'unknown', 'suggest_why' => 'A child within the child ages, and the master records no child rate.', 'child_rate' => null,
                    'child_calc' => null, 'child_total' => null, 'can_apply' => false, 'apply_block' => 'The master holds no child rate for it — enter one in the Place to Visit master first.'];
            } elseif ($sp['free'] === $n) {
                $item += ['policy' => $policy, 'suggestion' => 'not_applicable', 'suggest_reason' => 'free', 'suggest_why' => 'Every child is under the age the park charges from.', 'child_rate' => $child,
                    'child_calc' => implode('; ', $sp['parts']) . ' = USD 0.00', 'child_total' => 0.0, 'can_apply' => false, 'apply_block' => 'Every child is under the charged age: confirm "Not applicable — free admission".'];
            } else {
                $item += ['policy' => $policy, 'suggestion' => 'applicable', 'suggest_why' => 'The master\'s child rate and age rules give the figure.' . ($sp['no_age'] ? ' A child\'s age is not given — the child rate is used; give the age to be sure.' : ''),
                    'child_rate' => $child, 'child_calc' => implode('; ', $sp['parts']) . ' = USD ' . number_format($sp['total'], 2), 'child_total' => $sp['total'], 'can_apply' => true, 'apply_block' => null];
            }
            return $this->finish($item, $common, [$s['day_no'], $s['title'], $s['place_id'] ?? null, $s['fee_id'] ?? null, $unit, $child, ServiceLookup::effectiveQty($s), $s['kind'] ?? null, $ru['age_from'], $ru['age_to']]);
        } elseif ($child == 0.0) {
            $item += ['policy' => 'child rate USD 0.00 (free for children)', 'suggestion' => 'not_applicable', 'suggest_reason' => 'free', 'suggest_why' => 'The master records children as free.', 'child_rate' => 0.0,
                'child_calc' => $n . ' child' . ($n === 1 ? '' : 'ren') . ' × USD 0.00 = USD 0.00', 'child_total' => 0.0, 'can_apply' => false, 'apply_block' => 'The child rate is nil: confirm "Not applicable — free admission".'];
        } else {
            $item += ['policy' => 'child rate USD ' . number_format($child, 2) . ' a child (the master records no age limit; children are the party\'s children)', 'suggestion' => 'applicable',
                'suggest_why' => 'The master records a child rate.', 'child_rate' => $child,
                'child_calc' => $n . ' child' . ($n === 1 ? '' : 'ren') . ' (' . implode(', ', array_map(static fn ($k) => self::ageText($k['age']), $kids)) . ') × USD ' . number_format($child, 2) . ' = USD ' . number_format($n * $child, 2),
                'child_total' => round($n * $child, 2), 'can_apply' => true, 'apply_block' => null];
        }
        return $this->finish($item, $common, [$s['day_no'], $s['title'], $s['place_id'] ?? null, $s['fee_id'] ?? null, $unit, $child, ServiceLookup::effectiveQty($s), $s['kind'] ?? null]);
    }

    private function jeepItem(array $s, array $common): array
    {
        $jeep = self::money($s['jeep_usd'] ?? null);
        $q = ServiceLookup::effectiveQty($s) ?? (isset($s['qty']) && $s['qty'] !== null && $s['qty'] !== '' ? (float) $s['qty'] : null);
        if ($q === null && !empty($s['place_id'])) {
            // tf-safari-r1: the jeeps the party needs, from the jeep capacity
            $need = EntranceMaster::jeepsNeeded((int) ($common['pax'] ?? 0) ?: null, EntranceMaster::rules($this->db, (int) $s['place_id'])['seats']);
            $q = $need !== null ? (float) $need : null;
        }
        $item = ['key' => 'svc:' . (int) $s['id'] . ':jeep', 'kind' => 'vehicle', 'title' => $s['title'] . ' — jeep hire', 'day_from' => (int) $s['day_no'], 'day_to' => (int) $s['day_no'],
            'basis' => 'per vehicle (jeep)', 'source' => 'Place to Visit / Entrance Fee Master' . (!empty($s['fee_code']) ? ' — ' . $s['fee_code'] : ''), 'vehicle' => true,
            'policy' => $jeep !== null ? 'USD ' . number_format($jeep, 2) . ' a jeep' : 'no jeep rate for the day',
            'adult_calc' => $jeep !== null && $q !== null ? (int) $q . ' jeep' . ((int) $q === 1 ? '' : 's') . ' × USD ' . number_format($jeep, 2) . ' = USD ' . number_format($q * $jeep, 2) . ' (for the whole party, children included)' : 'jeep hire: ' . ($jeep === null ? 'no rate yet' : 'number of jeeps not given'),
            'adult_total' => $jeep !== null && $q !== null ? round($q * $jeep, 2) : null,
            'suggestion' => 'not_applicable', 'suggest_reason' => 'per_vehicle', 'suggest_why' => 'A jeep is hired per vehicle: a child adds no separate jeep charge. The child still counts for the jeep\'s seats.',
            'child_rate' => null, 'child_calc' => 'Child: no charge (per vehicle)', 'child_total' => 0.0, 'can_apply' => false, 'apply_block' => 'Charged per vehicle: there is no child fare to apply.'];
        return $this->finish($item, $common, [$s['day_no'], $s['title'], $jeep, $q]);
    }

    private function mealItem(array $s, array $kids, array $common): array
    {
        $meal = null;
        try {
            $meal = $this->db->query('SELECT * FROM tf_meal WHERE id = ?', [(int) $s['meal_id']])->getRowArray();
        } catch (\Throwable $e) {
        }
        $child = $meal && isset($meal['child_rate']) && $meal['child_rate'] !== null && $meal['child_rate'] !== '' ? round((float) $meal['child_rate'], 2) : null;
        $adult = $meal ? self::money($meal['adult_rate'] ?? null) : null;
        $cur = $meal['currency'] ?? ($s['rate_currency'] ?? 'USD');
        $ca = (int) ($s['qty_adult'] ?? 0);
        $cc = (int) ($s['qty_child'] ?? count($kids));
        $item = ['key' => 'svc:' . (int) $s['id'], 'kind' => 'meal', 'title' => (string) $s['title'], 'day_from' => (int) $s['day_no'], 'day_to' => (int) $s['day_no'],
            'basis' => 'per person (Meal Master ' . ($meal['code'] ?? '') . ')', 'source' => 'Meal Master', 'vehicle' => false,
            'adult_calc' => $adult !== null ? $ca . ' adult' . ($ca === 1 ? '' : 's') . ' × ' . $cur . ' ' . number_format($adult, 2) : 'adult price not known', 'adult_total' => null];
        if ($child === null) {
            $item += ['policy' => 'no child price in the Meal Master', 'suggestion' => 'unknown', 'suggest_why' => 'Needs checking: the Meal Master has no child price for this meal.', 'child_rate' => null, 'child_calc' => null, 'child_total' => null,
                'can_apply' => false, 'apply_block' => 'Enter the child price in the Meal Master first.'];
        } elseif ($child == 0.0) {
            $item += ['policy' => 'child price ' . $cur . ' 0.00', 'suggestion' => 'not_applicable', 'suggest_reason' => 'free', 'suggest_why' => 'The Meal Master records children as free.', 'child_rate' => 0.0,
                'child_calc' => $cc . ' child' . ($cc === 1 ? '' : 'ren') . ' × ' . $cur . ' 0.00', 'child_total' => 0.0, 'can_apply' => false, 'apply_block' => 'The child price is nil.'];
        } else {
            $item += ['policy' => 'child price ' . $cur . ' ' . number_format($child, 2), 'suggestion' => 'applicable', 'suggest_why' => 'The Meal Master records a child price.', 'child_rate' => $child,
                'child_calc' => $cc . ' child' . ($cc === 1 ? '' : 'ren') . ' × ' . $cur . ' ' . number_format($child, 2) . ' = ' . $cur . ' ' . number_format($cc * $child, 2), 'child_total' => $cur === 'USD' ? round($cc * $child, 2) : null,
                'can_apply' => true, 'apply_block' => null];
        }
        return $this->finish($item, $common, [$s['day_no'], $s['meal_id'], $adult, $child, $ca, $cc]);
    }

    private function excursionItem(array $x, array $kids, int $adults, array $common): array
    {
        $rate = self::money($x['rate'] ?? null);
        $n = count($kids);
        $item = ['key' => 'exc:' . (int) $x['id'], 'kind' => 'excursion', 'title' => (string) $x['name'], 'day_from' => (int) $x['day_no'], 'day_to' => (int) $x['day_no'],
            'basis' => 'per person', 'source' => 'Excursion rates' . (!empty($x['code']) ? ' — ' . $x['code'] : ''), 'vehicle' => false,
            'adult_calc' => $rate !== null ? $adults . ' adult' . ($adults === 1 ? '' : 's') . ' × ' . ($x['currency'] ?: 'USD') . ' ' . number_format($rate, 2) : 'rate not known', 'adult_total' => null,
            'policy' => 'the excursion master records no child rate', 'suggestion' => 'unknown', 'suggest_why' => 'Needs checking: no child rate is recorded for this excursion. If children pay the same, keep them in the quantity and confirm "Applicable"; if they go free, confirm "Not applicable".',
            'child_rate' => $rate, 'child_calc' => $rate !== null ? $n . ' child' . ($n === 1 ? '' : 'ren') . ' × ' . ($x['currency'] ?: 'USD') . ' ' . number_format($rate, 2) . ' (the rate on the line)' : null,
            'child_total' => $rate !== null && strtoupper((string) ($x['currency'] ?: 'USD')) === 'USD' ? round($n * $rate, 2) : null, 'can_apply' => $rate !== null, 'apply_block' => $rate === null ? 'No rate on the line.' : null];
        return $this->finish($item, $common, [$x['day_no'], $x['code'] ?? null, $x['name'], $rate, $x['qty'] ?? null, $x['unit']]);
    }

    /** A hotel stay: the contract's child bands, by age, bedding and meal plan; the amount a child a night where it can be worked out. */
    private function hotelItem(array $st, array $kids, int $adults, array $common): array
    {
        $d0 = $st['nights'][0];
        $nights = count($st['nights']);
        $contractId = (int) ($d0['rate_plan_id'] ?? 0);
        $meal = strtoupper((string) ($d0['meal_plan'] ?? ''));
        $roomType = (int) ($d0['room_type_id'] ?? 0);
        $contract = null;
        $bands = [];
        if ($contractId > 0) {
            try {
                $contract = $this->db->query('SELECT id, name, child_policy_text FROM hr2_contract WHERE id = ?', [$contractId])->getRowArray();
                $bands = $this->db->query('SELECT * FROM hr2_child_rate WHERE contract_id = ? ORDER BY age_from, id', [$contractId])->getResultArray();
            } catch (\Throwable $e) {
                $bands = [];
            }
        }
        $title = trim((string) $d0['hotel_name_raw']) . ' — ' . $nights . ' night' . ($nights === 1 ? '' : 's') . ($meal !== '' ? ' ' . $meal : '') . (!empty($d0['room_type_name']) ? ', ' . $d0['room_type_name'] : '');
        $item = ['key' => 'hotel:' . (int) $st['from'] . ':' . substr(sha1($st['k']), 0, 10), 'kind' => 'hotel', 'title' => $title, 'day_from' => (int) $st['from'], 'day_to' => (int) $st['to'],
            'basis' => 'per child per night, by age, bedding and meal plan', 'source' => $contract ? 'Hotel contract — ' . $contract['name'] : 'no approved contract on these nights', 'vehicle' => false,
            'policy' => $contract ? trim((string) ($contract['child_policy_text'] ?? '')) ?: 'no child policy text on the contract' : 'no contract',
            'adult_calc' => 'rooms as the room mix gives them (unchanged; the child stays in the room count)', 'adult_total' => null, 'nights' => $nights, 'per_child' => []];
        $reasons = [];
        $perNight = 0.0;
        $allFree = true;
        $ok = $contract !== null && $bands !== [];
        foreach ($kids as $k) {
            $line = ['child' => $k['n'], 'age' => $k['age'], 'bed' => $k['bed'], 'band' => null, 'amount' => null, 'note' => null];
            if (!$contract) {
                $line['note'] = 'no contract';
                $ok = false;
            } elseif ($k['age'] === null) {
                $line['note'] = 'age not given';
                $ok = false;
            } else {
                $cands = array_values(array_filter($bands, static fn ($b) => $k['age'] >= (float) $b['age_from'] - 0.001 && $k['age'] <= (float) $b['age_to'] + 0.001
                    && (empty($b['room_type_id']) || (int) $b['room_type_id'] === $roomType)));
                if (!$cands) {
                    $line['note'] = 'the contract has no band for ' . self::ageText($k['age']) . ($bands ? ' (bands: ' . implode(', ', array_map(static fn ($b) => rtrim(rtrim($b['age_from'], '0'), '.') . '–' . rtrim(rtrim($b['age_to'], '0'), '.'), $bands)) . ')' : '');
                    $ok = false;
                } else {
                    $free = array_values(array_filter($cands, static fn ($b) => $b['amount'] !== null && (float) $b['amount'] == 0.0 && $b['bed'] === 'any'));
                    $bedWant = $k['bed'] === 'extra_bed' ? 'with_bed' : ($k['bed'] === 'no_bed' ? 'no_bed' : '');
                    $byBed = array_values(array_filter($cands, static fn ($b) => $b['bed'] === 'any' || ($bedWant !== '' && $b['bed'] === $bedWant)));
                    $byMeal = array_values(array_filter($byBed, static fn ($b) => empty($b['meal_plan']) || strtoupper((string) $b['meal_plan']) === $meal));
                    if ($free) {
                        $line['band'] = $free[0];
                        $line['amount'] = 0.0;
                        $line['note'] = 'free: ' . ($free[0]['note'] ?: 'age band ' . $free[0]['age_from'] . '–' . $free[0]['age_to']);
                    } elseif ($k['bed'] === '' || $k['bed'] === 'own_bed') {
                        $line['note'] = $k['bed'] === '' ? 'bedding not given (shares a bed or extra bed?)' : 'own bed / own room: the room mix charges it; check the contract';
                        $ok = false;
                    } elseif (!$byBed) {
                        $line['note'] = 'no band for a child with this bedding';
                        $ok = false;
                    } elseif (!$byMeal) {
                        $line['note'] = 'the contract states this child charge for ' . implode('/', array_unique(array_map(static fn ($b) => (string) $b['meal_plan'], $byBed))) . ' only; this stay is ' . ($meal ?: 'without a meal plan') . ' — work out the ' . ($meal ?: '') . ' figure from the contract';
                        $line['band'] = $byBed[0];
                        $ok = false;
                    } else {
                        $b = $byMeal[0];
                        $line['band'] = $b;
                        if ($b['amount'] !== null) {
                            $line['amount'] = round((float) $b['amount'], 2);
                            $line['note'] = 'USD ' . number_format((float) $b['amount'], 2) . ' a night — ' . ($b['note'] ?: '');
                        } elseif ($b['pct'] !== null && $b['pct_basis'] === 'room_rate' && $d0['rate_dbl'] !== null) {
                            $line['amount'] = round((float) $b['pct'] / 100 * (float) $d0['rate_dbl'], 2);
                            $line['note'] = rtrim(rtrim($b['pct'], '0'), '.') . '% of the double rate USD ' . number_format((float) $d0['rate_dbl'], 2) . ' = USD ' . number_format($line['amount'], 2) . ' a night';
                        } else {
                            $line['note'] = ($b['pct'] !== null ? rtrim(rtrim($b['pct'], '0'), '.') . '% of the ' . str_replace('_', ' ', (string) $b['pct_basis']) : 'no amount') . ' — the base figure is not on this night; work it out from the contract';
                            $ok = false;
                        }
                        if (!empty($b['max_children'])) {
                            $line['note'] .= ' (at most ' . (int) $b['max_children'] . ' child' . ((int) $b['max_children'] === 1 ? '' : 'ren') . ' on these terms)';
                        }
                    }
                }
            }
            if ($line['amount'] !== null) {
                $perNight += $line['amount'];
                if ($line['amount'] > 0) {
                    $allFree = false;
                }
            } else {
                $allFree = false;
            }
            $item['per_child'][] = $line;
            $reasons[] = 'child ' . $k['n'] . ' (' . self::ageText($k['age']) . ($k['bed'] ? ', ' . self::BEDS[$k['bed']] : '') . '): ' . ($line['note'] ?? '—');
        }
        $item['child_rate'] = $ok ? round($perNight, 2) : null;
        if ($ok && $allFree) {
            $item += ['suggestion' => 'not_applicable', 'suggest_reason' => 'in_room', 'suggest_why' => 'The contract makes the child(ren) free: ' . implode('; ', $reasons) . '.',
                'child_calc' => 'Child: no charge — ' . implode('; ', $reasons), 'child_total' => 0.0, 'can_apply' => false, 'apply_block' => 'The contract charges nothing for the child(ren) here.'];
        } elseif ($ok) {
            $item += ['suggestion' => 'applicable', 'suggest_why' => 'The contract charges for the child(ren): ' . implode('; ', $reasons) . '.',
                'child_calc' => 'USD ' . number_format($perNight, 2) . ' a night × ' . $nights . ' night' . ($nights === 1 ? '' : 's') . ' = USD ' . number_format($perNight * $nights, 2) . ' (' . implode('; ', $reasons) . ')',
                'child_total' => round($perNight * $nights, 2), 'can_apply' => true, 'apply_block' => null];
        } else {
            $item += ['suggestion' => 'unknown', 'suggest_why' => 'Needs checking — ' . implode('; ', $reasons) . '.', 'child_calc' => null, 'child_total' => null,
                'can_apply' => $contract !== null, 'manual' => $contract !== null,
                'apply_block' => $contract !== null ? null : 'No approved contract on these nights.'];
        }
        return $this->finish($item, $common, [$st['k'], $st['from'], $st['to'], $contractId, $meal, $roomType, $d0['rate_dbl'] ?? null, array_map(static fn ($b) => [$b['id'], $b['age_from'], $b['age_to'], $b['bed'], $b['meal_plan'], $b['amount'], $b['pct'], $b['pct_basis']], $bands)]);
    }

    // ------------------------------------------------------------------ the review

    /** The items with what staff decided: status confirmed | needs_checking | review_again. */
    public function review(array $it): array
    {
        $items = $this->items($it);
        $saved = [];
        if (self::ready($this->db)) {
            foreach ($this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE intake_id = ?', [(int) $it['id']])->getResultArray() as $r) {
                $saved[(string) $r['item_key']] = $r;
            }
        }
        $open = 0;
        foreach ($items as &$i) {
            $r = $saved[$i['key']] ?? null;
            $i['decision'] = $r ? (string) $r['decision'] : '';
            $i['reason'] = $r['reason'] ?? null;
            $i['reason_code'] = $r['reason_code'] ?? null;
            $i['decided_by'] = $r['decided_name'] ?? ($r['decided_by'] ?? null);
            $i['decided_at'] = $r['decided_at'] ?? null;
            $i['saved_child_total'] = $r && $r['child_total'] !== null ? (float) $r['child_total'] : null;
            $i['manual_amount'] = $r && (int) ($r['manual'] ?? 0) === 1 ? (float) $r['per_night'] : null;
            if ($i['decision'] === '') {
                $i['status'] = 'needs_checking';
            } elseif (($r['fingerprint'] ?? '') !== $i['fingerprint']) {
                $i['status'] = 'review_again';
            } else {
                $i['status'] = 'confirmed';
            }
            // what the quotation takes: the decision as made; nothing for an item still open
            if ($i['status'] === 'confirmed') {
                $i['effective_child_total'] = $i['decision'] === 'applicable' ? ($i['manual_amount'] !== null ? round($i['manual_amount'] * (int) ($i['nights'] ?? 1), 2) : $i['child_total']) : 0.0;
            } else {
                $i['effective_child_total'] = null;
                $open++;
            }
        }
        unset($i);
        // what the agent's email says about the children (ages, bedding) — shown beside the ages, never filled in by itself
        $hints = [];
        foreach (preg_split('/\r?\n/', (string) ($it['source_text'] ?? '')) ?: [] as $line) {
            $l = trim($line);
            if ($l !== '' && mb_strlen($l) < 200 && preg_match('/\b(child|children|kid|kids|infant|baby|years?[- ]old|yrs?\b|bed)\b|才|歳|子供|幼児/iu', $l) && count($hints) < 6 && !in_array($l, $hints, true)) {
                $hints[] = $l;
            }
        }
        return ['children' => self::children($it), 'adults' => (int) ($it['pax_adults'] ?? 0), 'pax' => (int) ($it['pax_total'] ?? 0), 'items' => $items, 'open' => $open, 'email_hints' => $hints,
            'reasons' => self::REASONS, 'beds' => self::BEDS, 'complete' => $open === 0];
    }

    /** Decide one item. $decision: applicable | not_applicable | '' (clear). For a hotel that needs a hand-worked figure: per_night (USD a night for all the children) with a reason. */
    public function decide(array $it, string $key, string $decision, ?string $reasonCode, ?string $reason, $perNight = null): array
    {
        $items = array_column($this->items($it), null, 'key');
        if (!isset($items[$key])) {
            throw new \InvalidArgumentException('That item is no longer on the itinerary.');
        }
        $i = $items[$key];
        $intakeId = (int) $it['id'];
        $reason = trim((string) $reason);
        $manual = false;
        if ($decision === '') {
            $this->db->query('DELETE FROM ' . self::TABLE . ' WHERE intake_id = ? AND item_key = ?', [$intakeId, $key]);
            $this->log($intakeId, $i, 'cleared — needs checking again');
            return ['item' => $i, 'decision' => ''];
        }
        if (!in_array($decision, ['applicable', 'not_applicable'], true)) {
            throw new \InvalidArgumentException('Choose Applicable or Not applicable.');
        }
        $childTotal = null;
        if ($decision === 'applicable') {
            if (!empty($i['vehicle'])) {
                throw new \InvalidArgumentException('A per-vehicle charge has no child fare: confirm "Not applicable — charged per vehicle".');
            }
            if ($i['child_total'] !== null && $i['can_apply']) {
                $childTotal = (float) $i['child_total'];
            } elseif (!empty($i['manual']) && $perNight !== null && $perNight !== '') {
                if (!is_numeric($perNight) || (float) $perNight < 0) {
                    throw new \InvalidArgumentException('Type the child charge a night as a number (USD).');
                }
                if (mb_strlen($reason) < 5) {
                    throw new \InvalidArgumentException('Say how the figure was worked out from the contract (at least five characters).');
                }
                $manual = true;
                $childTotal = round((float) $perNight * (int) ($i['nights'] ?? 1), 2);
            } else {
                throw new \InvalidArgumentException($i['apply_block'] ?: 'There is no recorded child rate to apply here.' . (!empty($i['manual']) ? ' Type the figure a night worked out from the contract, with how it was worked out.' : ''));
            }
        } else {
            if (!$reasonCode || !array_key_exists($reasonCode, self::REASONS)) {
                throw new \InvalidArgumentException('Say why no child fare applies.');
            }
            if ($reasonCode === 'other' && mb_strlen($reason) < 5) {
                throw new \InvalidArgumentException('Say why no child fare applies (at least five characters).');
            }
            if (!empty($i['vehicle']) && $reasonCode !== 'per_vehicle') {
                $reasonCode = 'per_vehicle';
            }
            $childTotal = 0.0;
            if ($reason === '') {
                $reason = self::REASONS[$reasonCode];
            }
        }
        $now = date('Y-m-d H:i:s');
        // MANUAL is a reserved word in this MySQL: every column is quoted; a failed write is an error, never a silent success
        $ok = $this->db->query('INSERT INTO ' . self::TABLE . ' (`intake_id`, `item_key`, `kind`, `title`, `day_from`, `day_to`, `decision`, `reason_code`, `reason`, `child_total`, `per_night`, `manual`, `suggestion`, `fingerprint`, `calc_json`, `decided_by`, `decided_by_id`, `decided_name`, `decided_at`)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE kind = VALUES(kind), title = VALUES(title), day_from = VALUES(day_from), day_to = VALUES(day_to), decision = VALUES(decision), reason_code = VALUES(reason_code), reason = VALUES(reason),
              child_total = VALUES(child_total), per_night = VALUES(per_night), `manual` = VALUES(`manual`), suggestion = VALUES(suggestion), fingerprint = VALUES(fingerprint), calc_json = VALUES(calc_json),
              decided_by = VALUES(decided_by), decided_by_id = VALUES(decided_by_id), decided_name = VALUES(decided_name), decided_at = VALUES(decided_at)',
            [$intakeId, $key, $i['kind'], mb_substr($i['title'], 0, 255), $i['day_from'], $i['day_to'], $decision, $decision === 'not_applicable' ? $reasonCode : null, $reason !== '' ? mb_substr($reason, 0, 255) : null,
             $childTotal, $manual ? round((float) $perNight, 2) : null, $manual ? 1 : 0, $i['suggestion'], $i['fingerprint'],
             json_encode(['adult_calc' => $i['adult_calc'], 'child_calc' => $manual ? 'USD ' . number_format((float) $perNight, 2) . ' a night × ' . (int) ($i['nights'] ?? 1) . ' = USD ' . number_format($childTotal, 2) . ' (worked out by staff: ' . $reason . ')' : ($decision === 'applicable' ? $i['child_calc'] : 'Child: no charge — ' . $reason), 'policy' => $i['policy'], 'source' => $i['source']], JSON_UNESCAPED_UNICODE),
             $this->user, $this->userId, $this->name, $now]);
        if ($ok === false) {
            throw new \RuntimeException('The decision could not be saved. Nothing was changed.');
        }
        $this->log($intakeId, $i, $decision === 'applicable' ? 'Applicable — child USD ' . number_format((float) $childTotal, 2) . ($manual ? ' (figure worked out by staff: ' . $reason . ')' : '') : 'Not applicable — Child: no charge (' . $reason . ')');
        return ['item' => $i, 'decision' => $decision, 'child_total' => $childTotal];
    }

    private function log(int $intakeId, array $i, string $what): void
    {
        try {
            (new Activity($this->db, $this->user, $this->userId, $this->name))->record('inquiry', $intakeId, $intakeId, null, 'child_fares',
                'Child Fare Review — ' . $i['title'] . ' (day ' . $i['day_from'] . ($i['day_to'] !== $i['day_from'] ? '–' . $i['day_to'] : '') . '): ' . $what, []);
        } catch (\Throwable $e) {
        }
    }

    /** The decisions as the pricing reads them: [item_key => decision], for confirmed items whose fingerprint still holds. */
    public static function decisions($db, int $intakeId): array
    {
        if (!self::ready($db)) {
            return [];
        }
        $out = [];
        foreach ($db->query('SELECT item_key, decision FROM ' . self::TABLE . ' WHERE intake_id = ?', [$intakeId])->getResultArray() as $r) {
            $out[(string) $r['item_key']] = (string) $r['decision'];
        }
        return $out;
    }
}
