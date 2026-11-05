<?php

namespace App\Libraries\HotelRates;

/**
 * One-time copy of the legacy tables into the hr2_* tables (schema 3).
 *
 *  supplier (sup_cat = 1)       -> hr2_hotel + hr2_hotel_account + hr2_location
 *  hotel_rate x room_type       -> hr2_room_type (per hotel), one plan of type "migrated" per hotel,
 *                                  hr2_season (+ hr2_season_date) per distinct date pair, hr2_rate per meal plan
 *  tour_quotations (hotel,room) -> hr2_room_type for room types used on tours but never priced
 *
 * Nothing in the legacy tables is modified. Every legacy row is accounted for:
 * it becomes rates (and is registered in hr2_legacy_map so the projector
 * replaces that exact row later) or it is listed in the report with a reason.
 *
 * Re-runnable: hotels / room types already present are reused; legacy rows
 * already mapped are skipped.
 */
class LegacyMigrator
{
    /** @var object */
    private $db;
    private HotelRateService $svc;
    private string $user;
    private bool $dryRun = false;

    public const LOCATION_ALIASES = [
        'negambo' => 'Negombo', 'bentota' => 'Bentota', 'pasikuda beach' => 'Pasikuda', 'passikudah' => 'Pasikuda', 'pasikudah' => 'Pasikuda',
        'nuwara eliya 22200' => 'Nuwara Eliya', 'nuwaraeliya' => 'Nuwara Eliya', 'dick oya' => 'Dickoya', 'sigiriya ' => 'Sigiriya',
        'katunayaka' => 'Katunayake', 'kaluthara' => 'Kalutara', 'thissamaharama' => 'Tissamaharama', 'tissa' => 'Tissamaharama', 'colombo 03' => 'Colombo',
    ];

    public function __construct($db, ?string $user = null)
    {
        $this->db = $db;
        $this->user = $user ?: 'migration';
        $this->svc = new HotelRateService($db, $this->user);
    }

    public function run(bool $dryRun = false): array
    {
        $this->dryRun = $dryRun;
        $report = [
            'dry_run' => $dryRun,
            'hotels_created' => 0, 'hotels_existing' => 0, 'locations_created' => 0,
            'room_types_total' => 0, 'plans_created' => 0, 'seasons_created' => 0, 'rate_rows_created' => 0,
            'legacy_rows_total' => 0, 'legacy_rows_mapped' => 0, 'legacy_rows_skipped' => 0,
            'flags' => ['all_zero' => 0, 'reversed_dates' => 0, 'overlap' => 0, 'duplicate' => 0, 'unreadable_dates' => 0, 'unknown_hotel' => 0, 'unknown_room_type' => 0, 'tiny' => 0, 'huge' => 0, 'occupancy_order' => 0],
            'skipped' => [],
        ];

        $tiny = (float) $this->svc->settings()->get('tiny_rate');
        $huge = (float) $this->svc->settings()->get('huge_rate');
        $currency = (string) $this->svc->settings()->get('default_currency');

        if (!$dryRun) {
            $this->db->transStart();
        }

        // ---- 1. hotels -------------------------------------------------------
        $suppliers = $this->rows("SELECT ID, Sup_Acc_No, Name, Address, email FROM supplier WHERE sup_cat = 1 AND Sup_Acc_No IS NOT NULL AND Sup_Acc_No <> '' ORDER BY ID");
        $hotelByAcc = [];
        foreach ($suppliers as $s) {
            $acc = trim($s['Sup_Acc_No']);
            $existing = $this->row('SELECT hotel_id FROM hr2_hotel_account WHERE sup_acc_no = ?', [$acc]);
            if ($existing) {
                $hotelByAcc[$acc] = (int) $existing['hotel_id'];
                $report['hotels_existing']++;
                continue;
            }
            $name = trim(preg_replace('/\s+/', ' ', (string) $s['Name'])) ?: ('Hotel ' . $acc);
            $locationId = $this->locationFor($s['Address'], $report);
            if ($dryRun) {
                $hotelByAcc[$acc] = -((int) $s['ID']);
            } else {
                $this->db->query(
                    "INSERT INTO hr2_hotel (name, primary_sup_acc_no, location_id, reservations_email, currency, status, created_at, created_by) VALUES (?, ?, ?, ?, ?, 'active', ?, ?)",
                    [$name, $acc, $locationId, $s['email'] ?: null, $currency, date('Y-m-d H:i:s'), $this->user]
                );
                $hid = (int) $this->db->insertID();
                $this->db->query('INSERT INTO hr2_hotel_account (sup_acc_no, hotel_id, legacy_name, is_primary) VALUES (?, ?, ?, 1)', [$acc, $hid, $s['Name']]);
                $hotelByAcc[$acc] = $hid;
                $codes = new HotelCodes($this->db, $this->user ?? 'migrator');
                if ($codes->ready()) {
                    $codes->assign($hid, 'migrate');            // HTL-### = its legacy H-number when it has one
                }
            }
            $report['hotels_created']++;
        }

        // ---- 2. legacy rate rows per hotel ------------------------------------
        $legacy = $this->rows('SELECT hr.*, rt.rtype FROM hotel_rate hr LEFT JOIN room_type rt ON rt.id = hr.room_type ORDER BY hr.hotel, hr.room_type, hr.sdate, hr.id');
        $report['legacy_rows_total'] = count($legacy);
        $mapped = [];
        foreach ($this->rows('SELECT hotel_rate_id FROM hr2_legacy_map') as $m) {
            $mapped[(int) $m['hotel_rate_id']] = true;
        }
        $byHotel = [];
        foreach ($legacy as $r) {
            if (isset($mapped[(int) $r['id']])) {
                $report['legacy_rows_mapped']++;
                continue;
            }
            $acc = trim((string) $r['hotel']);
            if ($acc === '' || !isset($hotelByAcc[$acc])) {
                $report['flags']['unknown_hotel']++;
                $report['legacy_rows_skipped']++;
                $report['skipped'][] = [$r['id'], "hotel code '{$acc}' is not a hotel supplier"];
                continue;
            }
            if (empty($r['room_type']) || $r['rtype'] === null) {
                $report['flags']['unknown_room_type']++;
                $report['legacy_rows_skipped']++;
                $report['skipped'][] = [$r['id'], 'room type id ' . $r['room_type'] . ' does not exist'];
                continue;
            }
            $byHotel[$acc][] = $r;
        }
        $rateCols = ['sro', 'sbb', 'shb', 'sfb', 'dro', 'dbb', 'dhb', 'dfb', 'tro', 'tbb', 'thb', 'tfb'];

        foreach ($byHotel as $acc => $rows) {
            $hotelId = $hotelByAcc[$acc];
            $seasons = []; // "from|to" => [from, to, rooms => [legacyRoomId => [...]]]
            $minFrom = null; $maxTo = null;
            foreach ($rows as $r) {
                $from = Dates::parse($r['sdate']); $to = Dates::parse($r['edate']);
                $flags = [];
                if (!$from || !$to) {
                    $report['flags']['unreadable_dates']++;
                    $report['legacy_rows_skipped']++;
                    $report['skipped'][] = [$r['id'], 'start or end date unreadable'];
                    continue;
                }
                if ($from > $to) {
                    [$from, $to] = [$to, $from];
                    $flags[] = 'reversed_dates';
                    $report['flags']['reversed_dates']++;
                }
                $allZero = true;
                foreach ($rateCols as $col) {
                    if ((float) $r[$col] > 0) { $allZero = false; break; }
                }
                if ($allZero) {
                    $flags[] = 'all_zero';
                    $report['flags']['all_zero']++;
                }
                $key = "{$from}|{$to}";
                $roomId = (int) $r['room_type'];
                if (isset($seasons[$key]['rooms'][$roomId])) {
                    $report['flags']['duplicate']++;
                    $report['legacy_rows_skipped']++;
                    $report['skipped'][] = [$r['id'], "duplicate of legacy row {$seasons[$key]['rooms'][$roomId]['legacy_id']} (same hotel, room type and dates)"];
                    continue;
                }
                $vals = [];
                foreach (['ro', 'bb', 'hb', 'fb'] as $meal) {
                    $s = (float) $r['s' . $meal]; $d = (float) $r['d' . $meal]; $t = (float) $r['t' . $meal];
                    if ($s > 0 || $d > 0 || $t > 0) {
                        $vals[strtoupper($meal)] = ['sgl' => $s > 0 ? $s : null, 'dbl' => $d > 0 ? $d : null, 'tpl' => $t > 0 ? $t : null];
                    }
                }
                $seasons[$key]['from'] = $from;
                $seasons[$key]['to'] = $to;
                $seasons[$key]['rooms'][$roomId] = ['name' => trim($r['rtype']), 'vals' => $vals, 'legacy_id' => (int) $r['id'], 'flags' => $flags];
                $minFrom = $minFrom === null ? $from : min($minFrom, $from);
                $maxTo = $maxTo === null ? $to : max($maxTo, $to);
            }
            if (!$seasons) {
                continue;
            }
            // overlap flags per room type across seasons
            $ranges = [];
            foreach ($seasons as $key => $p) {
                foreach ($p['rooms'] as $roomId => $info) {
                    $ranges[$roomId][] = [$p['from'], $p['to'], $key];
                }
            }
            foreach ($ranges as $roomId => $list) {
                for ($i = 0; $i < count($list); $i++) {
                    for ($j = $i + 1; $j < count($list); $j++) {
                        if (Dates::overlaps($list[$i][0], $list[$i][1], $list[$j][0], $list[$j][1])) {
                            foreach ([$list[$i][2], $list[$j][2]] as $k) {
                                if (!in_array('overlap', $seasons[$k]['rooms'][$roomId]['flags'], true)) {
                                    $seasons[$k]['rooms'][$roomId]['flags'][] = 'overlap';
                                    $report['flags']['overlap']++;
                                }
                            }
                        }
                    }
                }
            }

            // ---- write ---------------------------------------------------
            $contractId = null;
            if (!$dryRun) {
                $existingContract = $this->row("SELECT id FROM hr2_contract WHERE hotel_id = ? AND plan_type = 'migrated' ORDER BY id LIMIT 1", [$hotelId]);
                if ($existingContract) {
                    $contractId = (int) $existingContract['id'];
                    $this->db->query('UPDATE hr2_contract SET valid_from = LEAST(valid_from, ?), valid_to = GREATEST(valid_to, ?) WHERE id = ?', [$minFrom, $maxTo, $contractId]);
                } else {
                    $this->db->query(
                        "INSERT INTO hr2_contract (hotel_id, name, plan_type, tier, priority, valid_from, valid_to, currency, pricing_mode, taxes_included, status, source_type, notes, entered_by, approved_by, approved_at, created_at, updated_at)
                         VALUES (?, 'Migrated from Hotel Rate screen', 'migrated', 'all', -10, ?, ?, ?, 'per_room', 1, 'approved', 'migration', ?, ?, ?, ?, ?, ?)",
                        [$hotelId, $minFrom, $maxTo, $currency, 'Rows copied from the legacy hotel_rate table on ' . date('Y-m-d') . '. Rows flagged all_zero, overlap or reversed_dates need a person to check them against the contract. Replace this plan with the real contract when the sheet is entered.', $this->user, $this->user, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
                    );
                    $contractId = (int) $this->db->insertID();
                    $report['plans_created']++;
                }
            } else {
                $report['plans_created']++;
            }

            $sort = 0;
            foreach ($seasons as $key => $p) {
                $seasonId = null;
                if (!$dryRun) {
                    $existingSeason = $this->row('SELECT s.id FROM hr2_season s JOIN hr2_season_date d ON d.season_id = s.id WHERE s.contract_id = ? AND d.date_from = ? AND d.date_to = ? LIMIT 1', [$contractId, $p['from'], $p['to']]);
                    if ($existingSeason) {
                        $seasonId = (int) $existingSeason['id'];
                    } else {
                        $this->db->query('INSERT INTO hr2_season (contract_id, name, kind, sort_order) VALUES (?, ?, \'normal\', ?)', [$contractId, self::seasonName($p['from'], $p['to']) . ' (' . $p['from'] . ')', $sort++]);
                        $seasonId = (int) $this->db->insertID();
                        $this->db->query('INSERT INTO hr2_season_date (season_id, date_from, date_to) VALUES (?, ?, ?)', [$seasonId, $p['from'], $p['to']]);
                        $report['seasons_created']++;
                    }
                } else {
                    $report['seasons_created']++;
                }
                foreach ($p['rooms'] as $legacyRoomId => $info) {
                    if ($dryRun) {
                        $report['rate_rows_created'] += max(1, count($info['vals']));
                        $report['legacy_rows_mapped']++;
                        continue;
                    }
                    $rt = $this->svc->ensureRoomType($hotelId, $info['name'], $legacyRoomId);
                    $baseFlags = $info['flags'];
                    if (!$info['vals']) {
                        $this->db->query(
                            'INSERT IGNORE INTO hr2_rate (season_id, room_type_id, meal_plan, sgl, dbl, tpl, quality_flags) VALUES (?, ?, \'BB\', NULL, NULL, NULL, ?)',
                            [$seasonId, $rt['id'], implode(',', array_unique(array_merge($baseFlags, ['all_zero'])))]
                        );
                        $report['rate_rows_created']++;
                    } else {
                        foreach ($info['vals'] as $meal => $v) {
                            $flags = array_unique(array_merge($baseFlags, array_filter(explode(',', HotelRateService::rowFlags($v, 'per_room', $tiny, $huge)))));
                            foreach (['tiny', 'huge', 'occupancy_order'] as $f) {
                                if (in_array($f, $flags, true)) { $report['flags'][$f]++; }
                            }
                            $this->db->query(
                                'INSERT IGNORE INTO hr2_rate (season_id, room_type_id, meal_plan, sgl, dbl, tpl, quality_flags) VALUES (?, ?, ?, ?, ?, ?, ?)',
                                [$seasonId, $rt['id'], $meal, $v['sgl'], $v['dbl'], $v['tpl'], $flags ? implode(',', $flags) : null]
                            );
                            $report['rate_rows_created']++;
                        }
                    }
                    $this->db->query('INSERT IGNORE INTO hr2_legacy_map (hotel_rate_id, hotel_id, room_type_id, date_from, date_to, contract_ids) VALUES (?, ?, ?, ?, ?, ?)', [$info['legacy_id'], $hotelId, $rt['id'], $p['from'], $p['to'], (string) $contractId]);
                    $report['legacy_rows_mapped']++;
                }
            }
        }

        // ---- 3. room types used on tours but never priced ---------------------
        $used = $this->rows("SELECT DISTINCT tq.hotel_acc, tq.room_type FROM tour_quotations tq WHERE tq.hotel_acc <> '' AND tq.room_type <> '' AND tq.room_type <> '0'");
        foreach ($used as $u) {
            $acc = trim($u['hotel_acc']);
            if (!isset($hotelByAcc[$acc]) || !is_numeric($u['room_type']) || $hotelByAcc[$acc] < 0) {
                continue;
            }
            $lg = $this->row('SELECT id, rtype FROM room_type WHERE id = ?', [(int) $u['room_type']]);
            if (!$lg || trim($lg['rtype']) === '') {
                continue;
            }
            if (!$dryRun) {
                $this->svc->ensureRoomType($hotelByAcc[$acc], $lg['rtype'], (int) $lg['id']);
            }
        }

        if (!$dryRun) {
            $report['room_types_total'] = (int) ($this->row('SELECT COUNT(*) AS n FROM hr2_room_type')['n'] ?? 0);
            $this->db->query(
                'INSERT INTO hr2_import (source, file_name, status, rows_total, rows_ok, rows_warning, rows_error, payload, created_by, created_at, committed_at) VALUES (\'migration\', \'hotel_rate table\', \'committed\', ?, ?, ?, ?, ?, ?, ?, ?)',
                [$report['legacy_rows_total'], $report['legacy_rows_mapped'], array_sum($report['flags']), $report['legacy_rows_skipped'], json_encode($report, JSON_UNESCAPED_UNICODE), $this->user, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
            );
            $this->db->transComplete();
            if (!$this->db->transStatus()) {
                throw new \RuntimeException('Migration failed and was rolled back.');
            }
        }
        return $report;
    }

    private function locationFor(?string $address, array &$report): ?int
    {
        $name = trim((string) $address);
        if ($name === '') {
            return null;
        }
        $canon = self::LOCATION_ALIASES[mb_strtolower($name)] ?? $name;
        if ($this->dryRun) {
            return null;
        }
        $before = $this->row('SELECT id FROM hr2_location WHERE LOWER(name) = ?', [mb_strtolower($canon)]);
        $id = $this->svc->ensureLocation($canon);
        if (!$before) {
            $report['locations_created']++;
        }
        if (mb_strtolower($canon) !== mb_strtolower($name)) {
            $this->db->query('INSERT IGNORE INTO hr2_location_alias (alias, location_id) VALUES (?, ?)', [mb_strtolower($name), $id]);
        }
        return $id;
    }

    public static function seasonName(string $from, string $to): string
    {
        $m = (int) substr($from, 5, 2);
        $season = ($m >= 5 && $m <= 10) ? 'Summer' : 'Winter';
        $y1 = substr($from, 0, 4); $y2 = substr($to, 0, 4);
        return $season . ' ' . ($y1 === $y2 ? $y1 : "{$y1}/" . substr($y2, 2));
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
