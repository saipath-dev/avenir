<?php

namespace App\Libraries\TourFlow;

/**
 * tf-transfer-r1 (Nov 2026): the airport pick-up and drop done by the hotel itself.
 *
 * Some tours do not use our car to or from the airport: the first hotel collects the clients on arrival, or the last
 * hotel takes them to the airport on departure, and the hotel charges for it on its own bill.
 *
 *   - Each hotel's charge is kept once, in Hotel Rates → the hotel's record (tf_hotel_transfer_fee): an airport
 *     pick-up fee and an airport drop fee, the currency, and whether it is per vehicle (with how many one vehicle
 *     carries) or per person.
 *   - Each inquiry says how the clients arrive and leave (tf_intake.arrival_by / departure_by: car or hotel). Left
 *     unset, it is read from the email's own words ("by hotel transfer"), and shown as read so a person can change it.
 *   - By hotel: our car starts at the first hotel the next morning (pick-up) or ends at the last hotel on the day they
 *     arrive there (drop), with the garage run from that hotel; and the fee goes on that hotel's night in the costing,
 *     because it is paid with the hotel's bill. A fee typed on the inquiry (pickup_fee / drop_fee, the per-unit figure)
 *     is the person's and wins over the hotel's record. A hotel with no fee on file stops the quotation until one is given.
 */
final class HotelTransfers
{
    public const BASES = ['vehicle', 'person'];

    public static function installed($db): bool
    {
        try {
            return (bool) $db->query("SHOW TABLES LIKE 'tf_hotel_transfer_fee'")->getResultArray()
                && (bool) $db->query("SHOW COLUMNS FROM `tf_intake` LIKE 'departure_by'")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** The hotel's own charge, or null when nothing is on file. */
    public static function fee($db, int $hotelId): ?array
    {
        if ($hotelId <= 0) {
            return null;
        }
        try {
            $r = $db->query('SELECT * FROM tf_hotel_transfer_fee WHERE hotel_id = ?', [$hotelId])->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$r) {
            return null;
        }
        return [
            'hotel_id' => (int) $r['hotel_id'],
            'pickup_amount' => $r['pickup_amount'] !== null ? (float) $r['pickup_amount'] : null,
            'drop_amount' => $r['drop_amount'] !== null ? (float) $r['drop_amount'] : null,
            'currency' => strtoupper((string) ($r['currency'] ?: 'USD')),
            'basis' => in_array($r['basis'], self::BASES, true) ? $r['basis'] : 'vehicle',
            'pax_per_vehicle' => $r['pax_per_vehicle'] !== null ? (int) $r['pax_per_vehicle'] : null,
            'note' => $r['note'] ?: null,
            'updated_by' => $r['updated_by'] ?? null, 'updated_at' => $r['updated_at'] ?? null,
        ];
    }

    /** Save the hotel's charge from the hotel record's form. Empty amounts clear that side. */
    public static function save($db, int $hotelId, array $v, string $user): ?array
    {
        $num = static function ($x): ?float {
            $x = trim(str_replace(',', '', (string) $x));
            if ($x === '') {
                return null;
            }
            if (!is_numeric($x) || (float) $x < 0) {
                throw new \InvalidArgumentException('The airport pick-up and drop fees are amounts — 0 or more, or left empty.');
            }
            return round((float) $x, 2);
        };
        $pick = $num($v['pickup_amount'] ?? '');
        $drop = $num($v['drop_amount'] ?? '');
        $cur  = strtoupper(trim((string) ($v['currency'] ?? 'USD'))) ?: 'USD';
        if (!in_array($cur, ['USD', 'LKR'], true)) {
            throw new \InvalidArgumentException('The airport transfer fee is in USD or LKR.');
        }
        $basis = in_array((string) ($v['basis'] ?? ''), self::BASES, true) ? (string) $v['basis'] : 'vehicle';
        $ppv   = trim((string) ($v['pax_per_vehicle'] ?? ''));
        if ($ppv !== '' && (!ctype_digit($ppv) || (int) $ppv < 1 || (int) $ppv > 60)) {
            throw new \InvalidArgumentException('People per vehicle is a number from 1 to 60, or left empty (one vehicle for the party).');
        }
        $note = trim((string) ($v['note'] ?? ''));
        $was  = self::fee($db, $hotelId);
        if ($pick === null && $drop === null && $note === '') {
            if ($was) {
                $db->query('DELETE FROM tf_hotel_transfer_fee WHERE hotel_id = ?', [$hotelId]);
                Activity::master($db, $user, 'hotel_transfer_fee', $hotelId, 'Airport pick-up / drop fee cleared for hotel ' . $hotelId, [['what' => 'airport transfer fee', 'from' => 'set', 'to' => 'cleared']]);
            }
            return null;
        }
        $db->query(
            'INSERT INTO tf_hotel_transfer_fee (hotel_id, pickup_amount, drop_amount, currency, basis, pax_per_vehicle, note, updated_by, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE pickup_amount = VALUES(pickup_amount), drop_amount = VALUES(drop_amount), currency = VALUES(currency),
                basis = VALUES(basis), pax_per_vehicle = VALUES(pax_per_vehicle), note = VALUES(note), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
            [$hotelId, $pick, $drop, $cur, $basis, $ppv === '' ? null : (int) $ppv, $note === '' ? null : mb_substr($note, 0, 255), $user, date('Y-m-d H:i:s')]
        );
        $now = self::fee($db, $hotelId);
        $txt = static fn (?array $f, string $k) => $f && $f[$k] !== null ? $f['currency'] . ' ' . number_format($f[$k], 2) . ' per ' . $f['basis'] : '—';
        if ($txt($was, 'pickup_amount') !== $txt($now, 'pickup_amount') || $txt($was, 'drop_amount') !== $txt($now, 'drop_amount')) {
            Activity::master($db, $user, 'hotel_transfer_fee', $hotelId, 'Airport transfer fee for hotel ' . $hotelId . ': pick-up ' . $txt($now, 'pickup_amount') . ', drop ' . $txt($now, 'drop_amount'), [
                ['what' => 'airport pick-up fee', 'from' => $txt($was, 'pickup_amount'), 'to' => $txt($now, 'pickup_amount')],
                ['what' => 'airport drop fee', 'from' => $txt($was, 'drop_amount'), 'to' => $txt($now, 'drop_amount')],
            ]);
        }
        return $now;
    }

    /** "hotel" or "car" — the inquiry's own choice, else what the email says. */
    public static function mode(array $it, string $side): array
    {
        $col = $side === 'arrival' ? 'arrival_by' : 'departure_by';
        $set = (string) ($it[$col] ?? '');
        if (in_array($set, ['car', 'hotel'], true)) {
            return [$set, false];
        }
        if ($side === 'departure') {
            $txt = implode(' ', [(string) ($it['departure_note'] ?? ''), (string) ($it['departure_text'] ?? ''), (string) ($it['departure_narrative'] ?? '')]);
        } else {
            $first = ($it['days'] ?? [])[0] ?? [];
            $txt = implode(' ', [(string) ($first['narrative'] ?? ''), (string) ($first['special_note'] ?? ''), (string) ($first['source_quote'] ?? '')]);
        }
        $hotel = (bool) preg_match('/hotel(\'s)?\s+(own\s+)?(transfer|pick\s*-?\s*up|shuttle|car)|by\s+hotel\s+(transfer|car)|transfer\s+by\s+(the\s+)?hotel/i', $txt);
        return [$hotel ? 'hotel' : 'car', true];
    }

    /** The first and the last night with a hotel, from the inquiry's days. */
    public static function ends(array $days): array
    {
        $first = $last = null;
        foreach ($days as $d) {
            if (empty($d['hotel_id'])) {
                continue;
            }
            $first ??= $d;
            $last = $d;
        }
        return [$first, $last];
    }

    /** Hotels of the master whose name holds what was typed — for the transfer hotel search. */
    public static function search($db, string $q, int $limit = 20): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        try {
            return array_map(static fn ($r) => ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'area' => $r['area'] ?: null],
                $db->query("SELECT h.id, h.name, l.name AS area FROM hr2_hotel h LEFT JOIN hr2_location l ON l.id = h.location_id
                            WHERE h.status = 'active' AND h.name LIKE ? ORDER BY (h.name LIKE ?) DESC, h.name LIMIT " . max(1, min(50, $limit)),
                            ['%' . $q . '%', $q . '%'])->getResultArray());
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * tf-transfer-r2: both sides for an inquiry. The hotel that does the transfer is the one chosen on the inquiry
     * (arrival_hotel_id / departure_hotel_id) — never assumed to be the hotel they sleep at. The rate is the figure typed
     * on the inquiry, USD per car; a blank rate is missing information, never a free transfer. When the chosen hotel is
     * also the first (arrival) or last (departure) night's hotel, the charge goes on that night's bill; otherwise it is
     * its own line for that hotel. Either way it is counted once, under Hotels.
     * @return array{arrival: array, departure: array}
     */
    public static function forInquiry($db, array $it): array
    {
        $on   = self::installed($db);
        $sel  = $on ? (bool) $db->query("SHOW COLUMNS FROM `tf_intake` LIKE 'arrival_hotel_id'")->getResultArray() : false;
        [$first, $last] = self::ends($it['days'] ?? []);
        $cars = 0;
        foreach (($it['vehicles'] ?? []) as $v) {
            $cars += max(1, (int) ($v['qty'] ?? 1));
        }
        $cars = max(1, $cars);
        $out = [];
        foreach (['arrival' => [$first, 'arrival_hotel_id', 'pickup_fee', 'Airport pick-up by the hotel'], 'departure' => [$last, 'departure_hotel_id', 'drop_fee', 'Airport drop by the hotel']] as $side => [$stay, $hotelKey, $feeKey, $label]) {
            [$by, $auto] = $on ? self::mode($it, $side) : ['car', true];
            // tf-transfer-r3: the date of the transfer — typed, else the tour's arrival / departure date
            $dateKey = $side . '_transfer_date';
            $typedDate = !empty($it[$dateKey]) ? (string) $it[$dateKey] : null;
            $tourDate  = $side === 'arrival' ? ($it['arrival_date'] ?? null) : ($it['departure_date'] ?? null);
            $row = ['side' => $side, 'by' => $by, 'auto' => $auto, 'label' => $label, 'hotel_id' => null, 'hotel' => null, 'night' => null,
                    'unit' => null, 'currency' => 'USD', 'basis' => 'car', 'qty' => $cars, 'amount' => null, 'missing' => false, 'why' => null,
                    'date' => $typedDate ?? $tourDate, 'date_typed' => $typedDate !== null];
            if ($by === 'hotel') {
                $hid  = $sel ? (int) ($it[$hotelKey] ?? 0) : 0;
                $name = null;
                if ($hid > 0) {
                    $h = $db->query('SELECT name FROM hr2_hotel WHERE id = ?', [$hid])->getRowArray();
                    $name = $h ? (string) $h['name'] : null;
                    if (!$h) {
                        $hid = 0;
                    }
                }
                $unit = isset($it[$feeKey]) && $it[$feeKey] !== null && $it[$feeKey] !== '' ? (float) $it[$feeKey] : null;
                $night = ($hid > 0 && $stay && (int) $stay['hotel_id'] === $hid) ? (int) $stay['day_no'] : null;
                $row = array_merge($row, [
                    'hotel_id' => $hid ?: null, 'hotel' => $name, 'night' => $night, 'unit' => $unit,
                    'amount' => $unit !== null ? round($unit * $cars, 2) : null,
                    'missing' => $hid <= 0 || $unit === null,
                    'why' => $hid <= 0 ? 'Select the hotel that does the ' . ($side === 'arrival' ? 'pick-up' : 'drop') . '.' : ($unit === null ? 'Enter the rate (USD per car).' : null),
                ]);
            }
            $out[$side] = $row;
        }
        return $out;
    }
}
