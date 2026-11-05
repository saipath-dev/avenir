<?php

namespace App\Libraries\HotelRates;

/**
 * Keeps the legacy `hotel_rate` table in step with the approved rate plans so
 * every old screen (quotation timeline get_meal_prices(), Hotel Rate report,
 * old Hotel Rate page) keeps working without a code change.
 *
 * For each hotel: every elementary date interval × every room type the plans
 * price → the 12 legacy numbers (SGL/DBL/TPL × RO/BB/HB/FB) as the pricing
 * engine would answer for the DEFAULT MARKET (hr2_meta.default_market, JP) and
 * a booking made today. Per-person plans are multiplied by occupancy; unit
 * priced rooms (whole villas) cannot be expressed and are listed as skipped.
 *
 * Rows written by the projector are remembered in hr2_legacy_map and replaced
 * on the next run; legacy rows the migrator could not read stay untouched.
 *
 * Only the published plan kinds are projected (RateLookup::quotationPlanTypes):
 * a tactical, special offer or early bird never prices these rows.
 */
class LegacyProjector
{
    /** @var object */
    private $db;
    private RateLookup $lookup;
    private string $user;

    public function __construct($db, RateLookup $lookup, ?string $user = null)
    {
        $this->db = $db;
        $this->lookup = $lookup;
        $this->user = $user ?: 'system';
    }

    public function projectHotel(int $hotelId, ?string $bookingDate = null): array
    {
        $b = $this->lookup->bundle($hotelId);
        $res = ['hotel_id' => $hotelId, 'rows_written' => 0, 'rows_deleted' => 0, 'rooms' => 0, 'skipped' => []];
        if (!$b['hotel']) {
            return $res;
        }
        // one set of rows per supplier code of the hotel: a booking made on a duplicate code that was
        // merged in still finds its rates on the old screens and in the quotation timeline
        $accounts = array_column($this->rows('SELECT sup_acc_no FROM hr2_hotel_account WHERE hotel_id = ? ORDER BY is_primary DESC, sup_acc_no', [$hotelId]), 'sup_acc_no');
        if (!$accounts) {
            $accounts = [$b['hotel']['primary_sup_acc_no']];
        }
        $location = $this->row('SELECT name FROM hr2_location WHERE id = ?', [$b['hotel']['location_id']])['name'] ?? '';
        $approved = array_filter($b['plans'], static fn ($p) => $p['status'] === 'approved');

        // old projected rows go
        $old = $this->rows('SELECT hotel_rate_id FROM hr2_legacy_map WHERE hotel_id = ?', [$hotelId]);
        foreach ($old as $o) {
            $this->db->query('DELETE FROM hotel_rate WHERE id = ?', [$o['hotel_rate_id']]);
            $res['rows_deleted']++;
        }
        $this->db->query('DELETE FROM hr2_legacy_map WHERE hotel_id = ?', [$hotelId]);
        if (!$approved) {
            return $res;
        }

        $from = min(array_column($approved, 'valid_from'));
        $to = max(array_column($approved, 'valid_to'));
        $intervals = $this->lookup->intervals($b, $from, $to);
        // published contract rates only (tf-offers-r1): these rows feed the old screens — the inquiry timeline, the
        // conversion of an inquiry into a tour, the Hotel Rate report — where a tactical or early bird must never price
        $opts = ['market' => $this->lookup->defaultMarket(), 'booking_date' => $bookingDate ?: date('Y-m-d'), 'rooms' => 1, 'pax' => 2];
        if ((new OfferRates($this->db))->ready()) {   // from Install now (schema 42) on; before it, every approved plan as until now
            $opts['plan_types'] = RateLookup::quotationPlanTypes($this->db);
        }

        // room types priced anywhere
        $roomIds = [];
        foreach ($approved as $p) {
            foreach ($p['rates'] as $bySeason) {
                foreach (array_keys($bySeason) as $rid) {
                    $roomIds[$rid] = true;
                }
            }
            foreach ($p['room_supplements'] as $rs) {
                $roomIds[(int) $rs['room_type_id']] = true;
            }
        }
        foreach (array_keys($roomIds) as $rid) {
            $room = $b['room_types'][$rid] ?? null;
            if (!$room) {
                continue;
            }
            $legacyRoomId = (int) $room['legacy_room_type_id'];
            if (!$legacyRoomId) {
                $legacyRoomId = $this->ensureLegacyRoomTypeId($room['name']);
                $this->db->query('UPDATE hr2_room_type SET legacy_room_type_id = ? WHERE id = ?', [$legacyRoomId, $rid]);
            }
            $segments = [];
            foreach ($intervals as [$f, $t]) {
                $v = array_fill_keys(['sro', 'sbb', 'shb', 'sfb', 'dro', 'dbb', 'dhb', 'dfb', 'tro', 'tbb', 'thb', 'tfb'], 0.0);
                $plans = [];
                $any = false;
                foreach (['RO' => 'ro', 'BB' => 'bb', 'HB' => 'hb', 'FB' => 'fb'] as $meal => $suffix) {
                    $r = $this->lookup->price($hotelId, $f, 'hr2:' . $rid, $meal, $opts);
                    if (!$r) {
                        continue;
                    }
                    if ($r['pricing_mode'] === 'per_unit') {
                        $res['skipped'][] = "{$room['name']} {$f}–{$t}: unit-priced (whole villa) — the old screens cannot show it";
                        continue 2;
                    }
                    $mult = $r['pricing_mode'] === 'per_person';
                    $v['s' . $suffix] = $r['sgl'] !== null ? round($r['sgl'], 2) : 0.0;
                    $v['d' . $suffix] = $r['dbl'] !== null ? round($r['dbl'] * ($mult ? 2 : 1), 2) : 0.0;
                    $v['t' . $suffix] = $r['tpl'] !== null ? round($r['tpl'] * ($mult ? 3 : 1), 2) : 0.0;
                    $plans[$r['plan']['id']] = true;
                    $any = true;
                }
                if (!$any) {
                    continue;
                }
                $last = $segments ? $segments[count($segments) - 1] : null;
                if ($last && $last['v'] == $v && Dates::next($last['to']) === $f) {
                    $segments[count($segments) - 1]['to'] = $t;
                    $segments[count($segments) - 1]['plans'] += $plans;
                } else {
                    $segments[] = ['from' => $f, 'to' => $t, 'v' => $v, 'plans' => $plans];
                }
            }
            foreach ($segments as $seg) {
                $v = $seg['v'];
                foreach ($accounts as $acc) {
                    $this->db->query(
                        'INSERT INTO hotel_rate (hotel, location, hotel_code, room_type, sro, sbb, shb, sfb, sdate, edate, dro, dbb, dhb, dfb, dsdate, dedate, tro, tbb, thb, tfb, tsdate, tedate) VALUES (?, ?, \'\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [$acc, $location, $legacyRoomId, $v['sro'], $v['sbb'], $v['shb'], $v['sfb'], $seg['from'], $seg['to'], $v['dro'], $v['dbb'], $v['dhb'], $v['dfb'], $seg['from'], $seg['to'], $v['tro'], $v['tbb'], $v['thb'], $v['tfb'], $seg['from'], $seg['to']]
                    );
                    $newId = (int) $this->db->insertID();
                    $this->db->query('INSERT INTO hr2_legacy_map (hotel_rate_id, hotel_id, room_type_id, date_from, date_to, contract_ids) VALUES (?, ?, ?, ?, ?, ?)', [$newId, $hotelId, $rid, $seg['from'], $seg['to'], implode(',', array_keys($seg['plans']))]);
                    $res['rows_written']++;
                }
            }
            if ($segments) {
                $res['rooms']++;
            }
        }
        $res['skipped'] = array_values(array_unique($res['skipped']));
        return $res;
    }

    /** Every hotel with an approved plan (nightly job). */
    public function projectAll(?string $bookingDate = null): array
    {
        $out = ['hotels' => 0, 'rows_written' => 0, 'rows_deleted' => 0, 'skipped' => 0];
        $ids = array_column($this->rows("SELECT DISTINCT hotel_id FROM hr2_contract WHERE status = 'approved' UNION SELECT DISTINCT hotel_id FROM hr2_legacy_map"), 'hotel_id');
        foreach ($ids as $hid) {
            $this->lookup->forget((int) $hid);
            $r = $this->projectHotel((int) $hid, $bookingDate);
            $out['hotels']++;
            $out['rows_written'] += $r['rows_written'];
            $out['rows_deleted'] += $r['rows_deleted'];
            $out['skipped'] += count($r['skipped']);
        }
        return $out;
    }

    private function ensureLegacyRoomTypeId(string $name): int
    {
        $row = $this->row('SELECT id FROM room_type WHERE LOWER(TRIM(rtype)) = ? ORDER BY id LIMIT 1', [mb_strtolower(trim($name))]);
        if ($row) {
            return (int) $row['id'];
        }
        $this->db->query('INSERT INTO room_type (rtype) VALUES (?)', [trim($name)]);
        return (int) $this->db->insertID();
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
