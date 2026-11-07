<?php

namespace App\Libraries\TourFlow;

use App\Libraries\HotelRates\Installer as RatesInstaller;

/**
 * Creates / upgrades the tf_* tables.
 *
 * Deliberately small. The Hotel Rates installer already works out the database's
 * collation and runs a .sql file statement by statement, so this reuses both
 * rather than keeping a second copy of that logic. Re-running is harmless.
 */
final class Installer
{
    public const SCHEMA_VERSION = 57;   // 57 (30 Oct 2026, tf-stage10): Staff Assignment — tf_staff, tf_staff_permission, tf_staff_log; user_priv_q answers from the panel for panel accounts;   // was 56:   // 56 (30 Oct 2026, tf-stage8): Blocks & Investigations — tf_block, tf_block_log, the Super-Admin-only page;   // was 55:   // 55 (29 Oct 2026, tf-stage6): Customers & Agents — quotation currency and default markup on an agent, the agent flag's who/when;   // was 54:   // 54 (29 Oct 2026, tf-stage5): bank-account safeguards — verification by Finance & Approvals — Management Approver;   // was 53:   // 53 (29 Oct 2026, tf-stage4): saved costings protected — kept USD rate, service-line keys and drift, the recalculation log;   // was 52:   // 52 (29 Oct 2026, tf-stage2): the daily USD rate from the Home page, with its history;   // was 51:   // 51 (28 Oct 2026, tf-safari-r2): jeep and entrance paid together; the safari payment slip;   // was 50:   // 50 (28 Oct 2026, tf-safari-r1): the safari / Place to Visit master — jeep capacity, child age rules, archive, jeep operator and ticket supplier details;   // was 49:   // 49 (28 Oct 2026, tf-child-r1): the Child Fare Review;   // 48 (28 Oct 2026, tf-attend-r1): per-person lines follow the party; attendance override with reason
    public const SCHEMA_VERSION_47 = 47;   // 47 (27 Oct 2026, tf-comms-r3): incoming e-mail, encrypted mailbox passwords, quotation drafts for review; 46 (tf-comms-r2): e-mails sent from Communications & History; 45 (tf-comms-r1): Communications & History

    /** The collation written in tour_flow_schema.sql; swapped for the database's own at install time. */
    public const SQL_COLLATION = 'utf8mb4_unicode_ci';

    /** null = not installed. */
    public static function installedVersion($db): ?int
    {
        $tables = array_map(static fn ($r) => array_values($r)[0], $db->query("SHOW TABLES LIKE 'tf_%'")->getResultArray());
        if (!$tables || !in_array('tf_meta', $tables, true)) {
            return null;
        }
        $r = $db->query("SELECT v FROM tf_meta WHERE k = 'schema_version'")->getRowArray();
        return (int) ($r['v'] ?? self::SCHEMA_VERSION);
    }

    // =====================================================================
    //  Rollback protection: a copy of the TourFlow tables before any change
    // =====================================================================

    /** Where the copies are kept: writable/tf_backups, a folder closed to the web. */
    public static function backupDir(): string
    {
        $base = defined('WRITEPATH') ? (string) WRITEPATH : sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'tf_backups';
    }

    /** One value as SQL: NULL, or a quoted string escaped the way MySQL reads it back. */
    private static function sqlValue($v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        return "'" . strtr((string) $v, ["\\" => "\\\\", "\0" => '\\0', "\n" => '\\n', "\r" => '\\r', "'" => "\\'", '"' => '\\"', "\x1a" => '\\Z']) . "'";
    }

    /**
     * A copy of every tf_ table (structure and rows) and of the TourFlow menu entries with
     * their privileges, as one SQL file under writable/tf_backups, written whole or not at all.
     * Run in phpMyAdmin it puts the TourFlow tables back exactly as they were at that moment —
     * everything done in TourFlow after it would be lost, so it is the last resort, not the
     * rollback: a release is rolled back by its kit, which changes no data.
     *
     * @return array{file:string, path:string, bytes:int, md5:string, rows:int, tables:array<string,int>}
     */
    public static function snapshot($db, string $label, string $by = 'installer'): array
    {
        $dir = self::backupDir();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('the folder ' . $dir . ' could not be created');
        }
        self::assertWritableDir($dir);
        // writable/ is closed to the web by its own .htaccess; these keep this folder closed even if that ever changes
        if (!is_file($dir . DIRECTORY_SEPARATOR . '.htaccess')) {
            @file_put_contents($dir . DIRECTORY_SEPARATOR . '.htaccess', "<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n<IfModule !authz_core_module>\n    Deny from all\n</IfModule>\n");
        }
        if (!is_file($dir . DIRECTORY_SEPARATOR . 'index.html')) {
            @file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.html', '');
        }
        $file = 'tf-' . date('Ymd-His') . '-' . trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $label), '-') . '.sql';
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        $part = $path . '.part';
        $fh = @fopen($part, 'wb');
        if (!$fh) {
            throw new \RuntimeException('the file ' . $part . ' could not be opened for writing');
        }
        $w = static function (string $s) use ($fh, $part): void {
            if (@fwrite($fh, $s) === false) {
                throw new \RuntimeException('the file ' . $part . ' could not be written');
            }
        };
        $tables = [];
        $rows   = 0;
        try {
            $ver = null;
            try {
                $ver = $db->query("SELECT v FROM tf_meta WHERE k = 'schema_version'")->getRowArray()['v'] ?? null;
            } catch (\Throwable $e) {
                $ver = null;
            }
            $w("-- TourFlow tables — a copy taken " . date('Y-m-d H:i:s') . " by {$by} ({$label}), schema " . ($ver ?? 'unknown') . ".\n"
             . "-- To put the TourFlow tables back exactly as they were at that moment, run this file in phpMyAdmin on\n"
             . "-- the Avenir database. Everything done in TourFlow after the copy would be lost: a last resort only.\n"
             . "-- The menu entries and privileges at the end are put back row by row (REPLACE), nothing else in those tables is touched.\n"
             . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");
            $names = [];
            foreach ($db->query('SHOW TABLES')->getResultArray() as $r) {
                $t = (string) array_values($r)[0];
                if (str_starts_with($t, 'tf_')) {
                    $names[] = $t;
                }
            }
            sort($names);
            foreach ($names as $t) {
                $create = $db->query('SHOW CREATE TABLE `' . $t . '`')->getRowArray();
                $w("DROP TABLE IF EXISTS `{$t}`;\n" . ($create['Create Table'] ?? array_values($create)[1]) . ";\n");
                $pk = [];
                foreach ($db->query("SHOW KEYS FROM `{$t}` WHERE Key_name = 'PRIMARY'")->getResultArray() as $k) {
                    $pk[(int) $k['Seq_in_index']] = '`' . $k['Column_name'] . '`';
                }
                ksort($pk);
                $order = $pk ? ' ORDER BY ' . implode(', ', $pk) : '';
                $n = 0;
                for ($off = 0; ; $off += 500) {
                    $chunk = $db->query('SELECT * FROM `' . $t . '`' . $order . ' LIMIT 500 OFFSET ' . $off)->getResultArray();
                    if (!$chunk) {
                        break;
                    }
                    $cols = '(`' . implode('`, `', array_keys($chunk[0])) . '`)';
                    $vals = [];
                    foreach ($chunk as $row) {
                        // tf-comms-r3: a secret (an encrypted mailbox password, the scheduled-task token) is never written to a copy;
                        // after a restore the System Administrator enters it again
                        if ($t === 'tf_meta' && in_array((string) ($row['k'] ?? ''), Secrets::META_SECRET_KEYS, true)) {
                            $w("-- tf_meta row '" . $row['k'] . "' left out of this copy on purpose (a secret; enter it again after a restore).\n");
                            continue;
                        }
                        $vals[] = '(' . implode(', ', array_map([self::class, 'sqlValue'], array_values($row))) . ')';
                    }
                    if (!$vals) {
                        continue;
                    }
                    $w("INSERT INTO `{$t}` {$cols} VALUES\n" . implode(",\n", $vals) . ";\n");
                    $n += count($chunk);
                    if (count($chunk) < 500) {
                        break;
                    }
                }
                $tables[$t] = $n;
                $rows += $n;
                $w("\n");
            }
            // the TourFlow menu entries and who holds them: put back row by row, never dropped
            $urls = array_map(static fn ($m) => $m[0], self::MENUS);
            try {
                $in = implode(',', array_fill(0, count($urls), '?'));
                $menus = $db->query('SELECT * FROM menu_list WHERE url IN (' . $in . ') ORDER BY id', $urls)->getResultArray();
                if ($menus) {
                    $w("REPLACE INTO `menu_list` (`" . implode('`, `', array_keys($menus[0])) . "`) VALUES\n"
                        . implode(",\n", array_map(static fn ($r) => '(' . implode(', ', array_map([self::class, 'sqlValue'], array_values($r))) . ')', $menus)) . ";\n");
                    $ids = array_map(static fn ($r) => (int) $r['id'], $menus);
                    $privs = $db->query('SELECT * FROM user_privileges WHERE menu_id IN (' . implode(',', $ids) . ') ORDER BY id')->getResultArray();
                    if ($privs) {
                        $w("REPLACE INTO `user_privileges` (`" . implode('`, `', array_keys($privs[0])) . "`) VALUES\n"
                            . implode(",\n", array_map(static fn ($r) => '(' . implode(', ', array_map([self::class, 'sqlValue'], array_values($r))) . ')', $privs)) . ";\n");
                    }
                    $tables['menu_list (TourFlow entries)'] = count($menus);
                    $tables['user_privileges (for them)']   = count($privs);
                }
            } catch (\Throwable $e) {
                $w('-- the menu entries could not be copied: ' . str_replace(["\r", "\n"], ' ', $e->getMessage()) . "\n");
            }
            $w("\nSET FOREIGN_KEY_CHECKS = 1;\n-- end of copy: {$rows} rows in " . count($names) . " tf_ tables\n");
            fclose($fh);
            $fh = null;
        } catch (\Throwable $e) {
            if ($fh) {
                fclose($fh);
            }
            @unlink($part);
            throw $e;
        }
        if (!@rename($part, $path)) {
            @unlink($part);
            throw new \RuntimeException('the finished copy could not be named ' . $file);
        }
        $out = ['file' => $file, 'path' => $path, 'bytes' => (int) filesize($path), 'md5' => (string) md5_file($path), 'rows' => $rows, 'tables' => $tables];
        // tf-comms-r1: the Communications & History attachments travel with the tables — copied beside the .sql
        try {
            $out['attachments'] = Comms::copyAttachmentsTo($dir . DIRECTORY_SEPARATOR . substr($file, 0, -4) . '-files');
        } catch (\Throwable $e) {
            $out['attachments'] = ['error' => $e->getMessage()];
        }
        @file_put_contents($path . '.json', json_encode(['taken_at' => date('Y-m-d H:i:s'), 'by' => $by, 'label' => $label] + $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $out;
    }

    /**
     * The folder must take a file, really: a probe file is opened, written and removed. PHP's is_writable() is not
     * asked any more — on Windows it answers from the folder's read-only attribute and an access-rights check of its
     * own, and either can say "no" for a folder Apache writes to perfectly well (a folder touched by Explorer, a copy
     * tool or a synced drive, say). It reported writable/tf_backups "not writable" on 5 and 6 October 2026 three times
     * while every actual write succeeded. A folder that truly refuses a file still fails here, with the reason the
     * system gives.
     */
    public static function assertWritableDir(string $dir): void
    {
        $probe = $dir . DIRECTORY_SEPARATOR . '.write-probe-' . bin2hex(random_bytes(4)) . '.tmp';
        $fh    = @fopen($probe, 'xb');
        if (!$fh) {
            $err = error_get_last();
            throw new \RuntimeException('the folder ' . $dir . ' is not writable' . (isset($err['message']) ? ' (' . trim((string) preg_replace('/^fopen\([^)]*\):\s*/', '', (string) $err['message'])) . ')' : ''));
        }
        $ok = @fwrite($fh, 'ok') !== false;
        @fclose($fh);
        @unlink($probe);
        if (!$ok) {
            throw new \RuntimeException('the folder ' . $dir . ' is not writable (a file was created but could not be written)');
        }
    }

    /** The copies kept, newest first: file, when, size, rows. */
    public static function snapshots(): array
    {
        $dir = self::backupDir();
        $out = [];
        foreach ((array) glob($dir . DIRECTORY_SEPARATOR . 'tf-*.sql') as $f) {
            $meta = is_file($f . '.json') ? (json_decode((string) file_get_contents($f . '.json'), true) ?: []) : [];
            $out[] = ['file' => basename($f), 'bytes' => (int) filesize($f), 'at' => $meta['taken_at'] ?? date('Y-m-d H:i:s', (int) filemtime($f)), 'by' => $meta['by'] ?? null, 'label' => $meta['label'] ?? null, 'rows' => $meta['rows'] ?? null, 'md5' => $meta['md5'] ?? null];
        }
        usort($out, static fn ($a, $b) => strcmp($b['file'], $a['file']));
        return $out;
    }

    /**
     * The no-charge sightseeing stops typed on inquiries, by name, each with where it came
     * from ("INQ 1019 night 4 (Sigiriya / Dambulla / Kandalama)", the first three places named).
     * Only sightseeing: a meal, a guide or a vehicle marked no charge is not a visit.
     *
     * @return array<string, string>
     */
    private static function noChargeStops($db): array
    {
        $out = [];
        $rows = $db->query("SELECT s.title, s.day_no, i.inquiry_no, d.area FROM tf_intake_service s
                            JOIN tf_intake i ON i.id = s.intake_id
                            LEFT JOIN tf_intake_day d ON d.intake_id = s.intake_id AND d.day_no = s.day_no
                            WHERE (s.match_state = 'no_charge' OR s.pay_mode = 'no_charge') AND s.kind IN ('visit', 'entrance', 'other') AND s.day_no > 0
                            ORDER BY s.id")->getResultArray();
        $where = [];
        foreach ($rows as $r) {
            $name = mb_substr(trim((string) preg_replace('/\s+/', ' ', (string) $r['title'])), 0, 190);
            if ($name === '') {
                continue;
            }
            $w = trim((string) $r['inquiry_no']) . ' night ' . (int) $r['day_no'] . (trim((string) $r['area']) !== '' ? ' (' . trim((string) $r['area']) . ')' : '');
            if (!in_array($w, $where[$name] ?? [], true) && count($where[$name] ?? []) < 3) {
                $where[$name][] = $w;
            }
        }
        foreach ($where as $name => $ws) {
            $out[$name] = mb_substr('Copied from ' . implode('; ', $ws), 0, 200);
        }
        return $out;
    }

    /**
     * @return array{version_before:int|null, statements:int, tables:int, collation:string, notes:string[]}
     */
    public static function install($db, string $sqlDir): array
    {
        $notes  = [];
        $before = self::installedVersion($db);

        // Rollback protection (schema 30): before an install that is already there is upgraded,
        // every tf_ table and the TourFlow menu entries with their privileges are copied to
        // writable/tf_backups. No copy, no upgrade: nothing is changed without one.
        if ($before !== null && $before < self::SCHEMA_VERSION) {
            try {
                $snap = self::snapshot($db, 'schema-' . $before . '-to-' . self::SCHEMA_VERSION);
                $notes[] = 'A copy of the TourFlow tables as they were at schema ' . $before . ' was written to writable/tf_backups/' . $snap['file'] . ' (' . $snap['rows'] . ' rows, ' . count($snap['tables']) . ' tables) before the upgrade.';
            } catch (\Throwable $e) {
                throw new \RuntimeException('The upgrade to schema ' . self::SCHEMA_VERSION . ' was not run: a copy of the TourFlow tables could not be written first (' . $e->getMessage() . '). Nothing was changed.');
            }
        }

        // The tf_ tables join tour_quotations.hotel_acc and hr2_hotel_account.sup_acc_no,
        // so they have to be created in the same collation or MySQL 8 refuses the join.
        $collation = RatesInstaller::targetCollation($db);
        $replace   = $collation === self::SQL_COLLATION ? [] : ['COLLATE=' . self::SQL_COLLATION => 'COLLATE=' . $collation];

        // Renames run BEFORE the schema file: it seeds rows by the new column names, and
        // a table that already exists is not recreated, so the rename has to happen first.
        foreach (self::renameColumns($db) as $u) {
            $notes[] = $u;
        }

        // schema 28: a setting may be a list (the airlines, the aliases), longer than the 400
        // characters the settings column began with — widened before the file seeds them
        foreach (self::widenSettingsColumn($db) as $u) {
            $notes[] = $u;
        }

        $statements = RatesInstaller::runSqlFile($db, rtrim($sqlDir, '/\\') . '/tour_flow_schema.sql', $replace);

        foreach (self::upgradeColumns($db) as $u) {
            $notes[] = $u;
        }
        foreach (self::widenEnums($db) as $u) {
            $notes[] = $u;
        }

        $converted = self::convertCollation($db, $collation);
        if ($converted) {
            $notes[] = 'Converted ' . count($converted) . " table(s) to the database collation {$collation}.";
        }

        $db->query("INSERT INTO tf_meta (k, v) VALUES ('schema_version', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [(string) self::SCHEMA_VERSION]);

        if ($before === null) {
            $notes[] = 'Tour Flow installed. Nothing on any existing screen changes until an inquiry is converted to a tour.';
        } elseif ($before < self::SCHEMA_VERSION) {
            $notes[] = "Upgraded Tour Flow schema {$before} to " . self::SCHEMA_VERSION . ' in place (existing inquiries untouched).';
        } else {
            $notes[] = 'Already at schema ' . self::SCHEMA_VERSION . '; the tables were re-checked and left as they are.';
        }

        foreach (self::upgradeSettings($db) as $u) {
            $notes[] = $u;
        }

        $menu = self::ensureMenu($db, $sqlDir);
        if ($menu !== null) {
            $notes[] = $menu;
        }
        foreach (self::staffView($db) as $u) {   // tf-stage10
            $notes[] = $u;
        }
        try {
            foreach (StaffPositions::ensure($db) as $u) {   // tf-positions-r1
                $notes[] = $u;
            }
        } catch (\Throwable $e) {
            $notes[] = 'Positions: ' . $e->getMessage();
        }
        try {
            foreach (StaffProfiles::ensure($db) as $u) {   // tf-staffno-r1
                $notes[] = $u;
            }
        } catch (\Throwable $e) {
            $notes[] = 'Staff profiles: ' . $e->getMessage();
        }
        // Transport & Guide Services (Shantha, 5 Oct 2026): the four connected masters under one sidebar heading
        foreach (self::transportGuideMenu($db) as $u) {
            $notes[] = $u;
        }
        foreach (Desks::ensure($db) as $u) {
            $notes[] = $u;
        }

        // Schema 11 copies the room type and the published rate beside every night. The
        // nights already on file were checked before that existed, so they are read again
        // once here — best effort, nothing typed, and an inquiry that will not read is left
        // for its own "Check hotels again" button.
        if ($before !== null && $before < 11) {
            $notes[] = self::rereadOpenInquiries($db);
        }

        // Schema 12 gives every inquiry a handling agent. The ones already on file were
        // created before that existed, so their creator becomes their handler — the same
        // rule a new inquiry follows — and the tours copy it. Nothing already set is touched.
        // Schema 53 (tf-stage4): the open inquiries keep the figures they hold today as the baseline — nothing recalculated.
        if ($before !== null && $before < 53) {
            try {
                $notes[] = CostingGuard::baseline($db);
            } catch (\Throwable $e) {
                $notes[] = 'Saved-costing baseline: ' . $e->getMessage();
            }
        }
        // Schema 54 (tf-stage5): the verification right and the registry page go to Finance & Approvals — Management Approver (additive).
        foreach (self::stage5Grants($db) as $u) {
            $notes[] = $u;
        }
        // Schema 55 (tf-stage6): the reviewed agent list is flagged once; nothing else on the ledger moves.
        if ($before !== null && $before < 55) {
            try {
                $notes[] = CustomersAgents::baseline($db);
            } catch (\Throwable $e) {
                $notes[] = 'Customers & Agents baseline: ' . $e->getMessage();
            }
        }
        foreach (self::backfillHandlers($db) as $u) {
            $notes[] = $u;
        }

        // Schema 13: the customer ledger's agent flag reads 0 for everyone nobody has marked.
        foreach (self::agentFlag($db) as $u) {
            $notes[] = $u;
        }

        // Schema 41: the Hotel Master's Frequently Used mark (a flag on the hotel record, nothing else).
        foreach (self::frequentHotelFlag($db) as $u) {
            $notes[] = $u;
        }

        // Schema 42: Published rates, Tactical rates and Early Bird offers kept apart — the payment side's tables.
        foreach (self::offerTables($db, $collation, $before !== null && $before < 42) as $u) {
            $notes[] = $u;
        }

        // Schema 43: hotel payments waiting for the System Administrator's rate review (tf-offers-r3).
        foreach (self::paymentHoldTable($db, $collation) as $u) {
            $notes[] = $u;
        }

        // Schema 48 (tf-attend-r1): a number left on a per-person line without an attendance override no longer counts —
        // on an open inquiry it is cleared (each one said on the inquiry's history); nothing on a confirmed or closed one is touched
        foreach (self::clearUnconfirmedCounts($db) as $u) {
            $notes[] = $u;
        }

        // Schema 45 (tf-comms-r1): the inquiry e-mails, quotation mails and hotel letters already on file become the
        // first lines of each Communications & History record (once; a line already captured is left alone)
        foreach (Comms::backfill($db, 'installer') as $u) {
            $notes[] = $u;
        }

        // Schema 44 (tf-crew-master-r1): the Driver & Guide Master — the driver registration (Supplier → New Driver,
        // category 2) brought onto the master once, each record linked to its ledger account; run once, never again.
        foreach (CrewPeople::migrateRegistration($db, 'installer') as $u) {
            $notes[] = $u;
        }

        // Schema 37: the activity lines already on file learn which Entrance Fee row they stand for,
        // from the quotation line each came from, so the ticket stock can serve them too.
        foreach (self::backfillServiceFees($db) as $u) {
            $notes[] = $u;
        }

        // Schema 39: the Place to Visit / Entrance Fee Master (entrances and safaris only). Ticket Stock holds its
        // attractions by the master's place; then the one-time import of the approved list of old Place to Visit
        // records (Database/Sql/tour_flow_place_import.json) — run once, never again; the old list is not changed.
        foreach (self::ticketsByPlace($db) as $u) {
            $notes[] = $u;
        }
        foreach (EntranceMaster::import($db, null, 'installer', $sqlDir) as $u) {
            $notes[] = $u;
        }

        // Schema 17: a leg planned before the two figures existed carried the driven record
        // alone, in `km`. That figure is the driven record still — copied beside itself so the
        // pane shows it in the right column; Google is asked when the inquiry is next planned
        // or when somebody presses Ask Google. Nothing already quoted moves here.
        if ($before !== null && $before < 17) {
            try {
                $db->query("UPDATE tf_intake_sector SET actual_km = km, actual_by = km_by, actual_why = why WHERE actual_why IS NULL AND km_by IN ('history','town_history')");
                $n = $db->affectedRows();
                $db->query("UPDATE tf_intake_sector SET actual_why = why WHERE actual_why IS NULL AND km_by = 'none' AND kind IN ('transfer','arrival','departure','positioning','local')");
                $notes[] = 'Kept the driven record beside ' . (int) $n . ' leg(s) already planned; Google is asked about them when each inquiry is next planned, or from its Ask Google button.';
            } catch (\Throwable $e) {
                $notes[] = 'The legs already planned could not be carried across (' . $e->getMessage() . '); each inquiry fills them in when next planned.';
            }
        }

        // Schema 19: until now, a leg somebody edited made the plan keep off that whole day,
        // so the day's other journeys quietly stopped being planned. Each leg now says which
        // leg of the plan it is. For legs already edited we take them at their ends as they
        // stand, which is what the plan makes of them — so nothing is laid down twice.
        if ($before !== null && $before < 19) {
            try {
                $db->query("UPDATE tf_intake_sector SET plan_key = CONCAT(vehicle_seq, ':', day_no, ':', LOWER(TRIM(COALESCE(from_place, ''))), '→', LOWER(TRIM(COALESCE(to_place, ''))), ':', kind)
                            WHERE plan_key IS NULL AND origin = 'human'");
                $n = $db->affectedRows();
                if ($n > 0) {
                    $notes[] = 'Tied ' . (int) $n . ' leg(s) already edited back to the plan, so the rest of those days are planned again instead of being left out.';
                }
            } catch (\Throwable $e) {
                $notes[] = 'The legs already edited could not be tied back to the plan (' . $e->getMessage() . '); check the mileage on any inquiry that is re-planned.';
            }
        }

        // Schema 14: every email already on file was stored exactly as pasted (nothing could
        // change it before), so that text is its own untouched original.
        try {
            if ($db->query("SHOW COLUMNS FROM `tf_intake` LIKE 'source_text_original'")->getResultArray()) {
                $db->query('UPDATE tf_intake SET source_text_original = source_text WHERE source_text_original IS NULL AND source_text IS NOT NULL');
                $n = $db->affectedRows();
                if ($n > 0) {
                    $notes[] = "Kept the original email text on {$n} inquiry(ies) that had one.";
                }
            }
        } catch (\Throwable $e) {
            $notes[] = 'The original email copies could not be filled in (' . $e->getMessage() . ').';
        }

        $tables = $db->query("SHOW TABLES LIKE 'tf_%'")->getResultArray();
        return ['version_before' => $before, 'statements' => $statements, 'tables' => count($tables), 'collation' => $collation, 'notes' => $notes];
    }

    /**
     * Columns that changed name after a release, renamed in place so the figures already
     * entered are kept. Each one checks first, so re-running does nothing.
     *
     * schema 5: what this called a crew "day rate" is called bata in the trade, and the
     * `vehicle` table already uses that word for the same kind of figure. Using two names
     * for one thing is how a quotation ends up counting it twice.
     */
    public static function renameColumns($db): array
    {
        $notes = [];
        foreach ([['tf_crew_role', 'day_rate', 'bata'], ['tf_intake_crew', 'day_rate', 'bata']] as [$table, $from, $to]) {
            if (!$db->query("SHOW TABLES LIKE '{$table}'")->getResultArray()) {
                continue;
            }
            $hasOld = $db->query("SHOW COLUMNS FROM `{$table}` LIKE ?", [$from])->getResultArray();
            $hasNew = $db->query("SHOW COLUMNS FROM `{$table}` LIKE ?", [$to])->getResultArray();
            if (!$hasOld || $hasNew) {
                continue;
            }
            try {
                $db->query("ALTER TABLE `{$table}` CHANGE COLUMN `{$from}` `{$to}` DECIMAL(10,2) NULL");
                $notes[] = "{$table}.{$from} renamed to {$to}; the figures already entered are kept.";
            } catch (\Throwable $e) {
                $notes[] = "Could not rename {$table}.{$from} to {$to} ({$e->getMessage()}).";
            }
        }
        return $notes;
    }

    /**
     * Columns added after the first release. CREATE TABLE IF NOT EXISTS cannot reach a
     * table that already exists, so each one is added here instead — checked first, so
     * re-running does nothing and no existing data is rewritten.
     */
    /** tf-attend-r1: clears numbers on per-person lines that were never confirmed as an attendance override (open inquiries only). */
    public static function clearUnconfirmedCounts($db): array
    {
        try {
            if (!$db->query("SHOW COLUMNS FROM `tf_intake_service` LIKE 'qty_override'")->getResultArray()) {
                return [];
            }
            $kinds = "'" . implode("','", ServiceLookup::PER_PERSON) . "'";
            $rows = $db->query("SELECT s.id, s.intake_id, s.day_no, s.title, s.qty, i.inquiry_no, i.pax_total FROM tf_intake_service s JOIN tf_intake i ON i.id = s.intake_id
                WHERE s.qty IS NOT NULL AND s.qty_override = 0 AND s.meal_id IS NULL AND s.kind IN ({$kinds})
                AND i.archived_at IS NULL AND i.status NOT IN ('confirmed', 'cancelled', 'declined')")->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $db->query('UPDATE tf_intake_service SET qty = NULL WHERE id = ? AND qty_override = 0', [(int) $r['id']]);
            $same = (int) $r['pax_total'] > 0 && (float) $r['qty'] === (float) $r['pax_total'];
            try {
                (new Activity($db, 'installer'))->record('inquiry', (int) $r['intake_id'], (int) $r['intake_id'], null, 'services',
                    'Day ' . (int) $r['day_no'] . ' "' . $r['title'] . '": the number ' . (float) $r['qty'] . ' on this per-person line was never confirmed as an attendance override, so the line now follows the party (' . (int) $r['pax_total'] . ')' . ($same ? ' — the same count, nothing changes in the price.' : '. If fewer of the party attend, set an attendance override with the reason.'),
                    [['what' => 'Service ' . $r['title'] . ' (day ' . (int) $r['day_no'] . ') · quantity', 'from' => (float) $r['qty'], 'to' => 'the party']]);
            } catch (\Throwable $e) {
            }
            $out[] = $r['inquiry_no'] . ' day ' . (int) $r['day_no'] . ' "' . $r['title'] . '": unconfirmed number ' . (float) $r['qty'] . ' cleared — follows the party of ' . (int) $r['pax_total'] . '.';
        }
        return $out;
    }

    public static function upgradeColumns($db): array
    {
        $notes = [];
        $add   = [
            // schema 46 (tf-comms-r2): an e-mail composed and sent from Communications & History keeps its bcc, the
            // mailbox it went from, how the send ended, and the thread (reply chain) it belongs to
            // schema 47 (tf-comms-r3): the Message-ID an e-mail went out with (a reply names it in In-Reply-To), and a
            // quotation version drafted by the system from a request e-mail, waiting for a person's review
            'tf_comm' => [
                'message_id'  => "ALTER TABLE `tf_comm` ADD COLUMN `message_id` VARCHAR(255) NULL AFTER `thread_key`, ADD KEY `idx_tf_comm_msgid` (`message_id`)",
                'bcc_addr'    => "ALTER TABLE `tf_comm` ADD COLUMN `bcc_addr` VARCHAR(500) NULL AFTER `cc_addr`",
                'send_state'  => "ALTER TABLE `tf_comm` ADD COLUMN `send_state` ENUM('','sent','failed','not_sent') NOT NULL DEFAULT '' AFTER `links_json`",
                'send_error'  => "ALTER TABLE `tf_comm` ADD COLUMN `send_error` VARCHAR(500) NULL AFTER `send_state`",
                'sent_from'   => "ALTER TABLE `tf_comm` ADD COLUMN `sent_from` VARCHAR(255) NULL AFTER `send_error`",
                'in_reply_to' => "ALTER TABLE `tf_comm` ADD COLUMN `in_reply_to` INT UNSIGNED NULL AFTER `sent_from`",
                'thread_key'  => "ALTER TABLE `tf_comm` ADD COLUMN `thread_key` VARCHAR(120) NULL AFTER `in_reply_to`",
            ],
            // schema 34: the maximum number of travellers a quotation version is quoted for (the client page and
            // its PDF show the price-per-person rows up to it); versions made before this stay NULL and show every row
            'tf_quote' => [
                'max_pax' => "ALTER TABLE `tf_quote` ADD COLUMN `max_pax` SMALLINT UNSIGNED NULL AFTER `issued_by`",
                // schema 47 (tf-comms-r3): a version the system drafted from a request e-mail, waiting for a person's review
                'review_required' => "ALTER TABLE `tf_quote` ADD COLUMN `review_required` TINYINT(1) NOT NULL DEFAULT 0 AFTER `note`",
                'auto_note'       => "ALTER TABLE `tf_quote` ADD COLUMN `auto_note` TEXT NULL AFTER `review_required`",
            ],
            // schema 2: a place for the one thing about a day that does not fit a field
            'tf_intake_day' => [
                'special_note' => "ALTER TABLE `tf_intake_day` ADD COLUMN `special_note` VARCHAR(500) NULL AFTER `narrative`",
                'origin'       => "ALTER TABLE `tf_intake_day` ADD COLUMN `origin` ENUM('ai','human') NOT NULL DEFAULT 'ai' AFTER `special_note`",
                // schema 11 (below) adds the room type and the rate copied beside it
            ],
            'tf_intake_service' => [
                // schema 48 (tf-attend-r1): a number on a per-person line counts only as a staff-confirmed attendance override
                'qty_override' => "ALTER TABLE `tf_intake_service` ADD COLUMN `qty_override` TINYINT(1) NOT NULL DEFAULT 0 AFTER `qty`",
                'qty_reason'   => "ALTER TABLE `tf_intake_service` ADD COLUMN `qty_reason` VARCHAR(255) NULL AFTER `qty_override`",
                'qty_set_by'   => "ALTER TABLE `tf_intake_service` ADD COLUMN `qty_set_by` VARCHAR(100) NULL AFTER `qty_reason`",
                'qty_set_at'   => "ALTER TABLE `tf_intake_service` ADD COLUMN `qty_set_at` DATETIME NULL AFTER `qty_set_by`",
                'source_title' => "ALTER TABLE `tf_intake_service` ADD COLUMN `source_title` VARCHAR(255) NULL AFTER `title`",
                'description'  => "ALTER TABLE `tf_intake_service` ADD COLUMN `description` TEXT NULL AFTER `special_note`",
            ],
            // schema 3: a row typed by a person is theirs, and a later read of the email
            // must leave it alone. Without this, pasting an email onto an inquiry somebody
            // had already filled in by hand would delete their work and rebuild it.
            'tf_intake_pax' => [
                'origin'   => "ALTER TABLE `tf_intake_pax` ADD COLUMN `origin` ENUM('ai','human') NOT NULL DEFAULT 'ai' AFTER `note`",
                'group_id' => "ALTER TABLE `tf_intake_pax` ADD COLUMN `group_id` INT UNSIGNED NULL AFTER `origin`",
            ],
            'tf_intake_request' => [
                'origin' => "ALTER TABLE `tf_intake_request` ADD COLUMN `origin` ENUM('ai','human') NOT NULL DEFAULT 'ai' AFTER `answered_at`",
            ],
            // schema 6: crew are freelancers, so a line can name the person engaged and
            // carry their own agreed bata for the payment side after confirmation
            'tf_intake_crew' => [
                'supplier_acc' => "ALTER TABLE `tf_intake_crew` ADD COLUMN `supplier_acc` VARCHAR(50) NULL AFTER `chosen`",
                'person_name'  => "ALTER TABLE `tf_intake_crew` ADD COLUMN `person_name` VARCHAR(190) NULL AFTER `supplier_acc`",
                'person_bata'  => "ALTER TABLE `tf_intake_crew` ADD COLUMN `person_bata` DECIMAL(10,2) NULL AFTER `person_name`",
            ],
            // schema 9: the inquiry copies the rate per kilometre onto itself the moment the
            // vehicle is settled, and remembers which master rate it came from. A change to
            // the master afterwards never moves a figure already quoted; a special rate typed
            // for one job keeps the master figure printed beside it.
            // schema 11: the quotation engine — groups, vehicles by day, room type and rate
            // per night, visit descriptions, the room mix and the management markup
            'tf_intake_sector' => [
                'vehicle_seq' => "ALTER TABLE `tf_intake_sector` ADD COLUMN `vehicle_seq` SMALLINT NOT NULL DEFAULT 0 AFTER `seq`",
                'waived_by'   => "ALTER TABLE `tf_intake_sector` ADD COLUMN `waived_by` VARCHAR(190) NULL AFTER `note`",
            ],
            'tf_intake_day_11' => [
                'room_type_id'   => "ALTER TABLE `tf_intake_day` ADD COLUMN `room_type_id` INT UNSIGNED NULL AFTER `rooms`",
                'room_type_name' => "ALTER TABLE `tf_intake_day` ADD COLUMN `room_type_name` VARCHAR(120) NULL AFTER `room_type_id`",
                'rate_plan_id'   => "ALTER TABLE `tf_intake_day` ADD COLUMN `rate_plan_id` INT UNSIGNED NULL AFTER `rate_note`",
                'rate_plan_name' => "ALTER TABLE `tf_intake_day` ADD COLUMN `rate_plan_name` VARCHAR(160) NULL AFTER `rate_plan_id`",
                'rate_sgl'       => "ALTER TABLE `tf_intake_day` ADD COLUMN `rate_sgl` DECIMAL(10,2) NULL AFTER `rate_plan_name`",
                'rate_dbl'       => "ALTER TABLE `tf_intake_day` ADD COLUMN `rate_dbl` DECIMAL(10,2) NULL AFTER `rate_sgl`",
                'rate_tpl'       => "ALTER TABLE `tf_intake_day` ADD COLUMN `rate_tpl` DECIMAL(10,2) NULL AFTER `rate_dbl`",
                'rate_unit'      => "ALTER TABLE `tf_intake_day` ADD COLUMN `rate_unit` DECIMAL(10,2) NULL AFTER `rate_tpl`",
                'unit_pax_max'   => "ALTER TABLE `tf_intake_day` ADD COLUMN `unit_pax_max` SMALLINT UNSIGNED NULL AFTER `rate_unit`",
                'rate_currency'  => "ALTER TABLE `tf_intake_day` ADD COLUMN `rate_currency` CHAR(3) NULL AFTER `unit_pax_max`",
                'rate_supp_json' => "ALTER TABLE `tf_intake_day` ADD COLUMN `rate_supp_json` TEXT NULL AFTER `rate_currency`",
                'room_types_json'=> "ALTER TABLE `tf_intake_day` ADD COLUMN `room_types_json` TEXT NULL AFTER `rate_supp_json`",
                // schema 35: the room type filled in from the master (the hotel's normal one — the first with a
                // published rate in the panel's own order) is marked so, until a person chooses; and the wording
                // this booking uses for it, when it differs from the master's name (the rate still follows the type)
                'room_type_auto' => "ALTER TABLE `tf_intake_day` ADD COLUMN `room_type_auto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `room_type_name`",
                'room_type_text' => "ALTER TABLE `tf_intake_day` ADD COLUMN `room_type_text` VARCHAR(120) NULL AFTER `room_type_auto`",
            ],
            'tf_place' => [
                // schema 50 (tf-safari-r1): how many people a jeep carries (the jeeps a party needs), the children's age rules for the
                // entrance (free under, child rate up to, adult rate above), and the archive (a place used in bookings is archived, never deleted)
                'jeep_seats'     => "ALTER TABLE `tf_place` ADD COLUMN `jeep_seats` SMALLINT UNSIGNED NULL AFTER `jeep`",
                'child_age_from' => "ALTER TABLE `tf_place` ADD COLUMN `child_age_from` DECIMAL(4,2) NULL AFTER `jeep_seats`",
                'child_age_to'   => "ALTER TABLE `tf_place` ADD COLUMN `child_age_to` DECIMAL(4,2) NULL AFTER `child_age_from`",
                'age_note'       => "ALTER TABLE `tf_place` ADD COLUMN `age_note` VARCHAR(255) NULL AFTER `child_age_to`",
                'archived_at'    => "ALTER TABLE `tf_place` ADD COLUMN `archived_at` DATETIME NULL AFTER `review_note`",
                'archived_by'    => "ALTER TABLE `tf_place` ADD COLUMN `archived_by` VARCHAR(100) NULL AFTER `archived_at`",
                'archive_note'   => "ALTER TABLE `tf_place` ADD COLUMN `archive_note` VARCHAR(255) NULL AFTER `archived_by`",
                // schema 51 (tf-safari-r2): the jeep operator is paid the jeep and the park entrance together, in one payment
                'pay_together'   => "ALTER TABLE `tf_place` ADD COLUMN `pay_together` TINYINT(1) NOT NULL DEFAULT 0 AFTER `age_note`",
            ],
            // schema 53 (tf-stage4): the USD rate kept with the inquiry from its first costing, and the review of rate changes
            'tf_intake_53' => [
                'usd_rate_kept'        => "ALTER TABLE `tf_intake` ADD COLUMN `usd_rate_kept` DECIMAL(12,4) NULL",
                'usd_rate_kept_date'   => "ALTER TABLE `tf_intake` ADD COLUMN `usd_rate_kept_date` DATE NULL AFTER `usd_rate_kept`",
                'usd_rate_kept_at'     => "ALTER TABLE `tf_intake` ADD COLUMN `usd_rate_kept_at` DATETIME NULL AFTER `usd_rate_kept_date`",
                'costing_reviewed_sig' => "ALTER TABLE `tf_intake` ADD COLUMN `costing_reviewed_sig` VARCHAR(64) NULL AFTER `usd_rate_kept_at`",
                'costing_reviewed_at'  => "ALTER TABLE `tf_intake` ADD COLUMN `costing_reviewed_at` DATETIME NULL AFTER `costing_reviewed_sig`",
                'costing_reviewed_by'  => "ALTER TABLE `tf_intake` ADD COLUMN `costing_reviewed_by` VARCHAR(190) NULL AFTER `costing_reviewed_at`",
            ],
            // schema 53 (tf-stage4): what a service line was priced for, when its figure was saved, and the master's later answer
            'tf_intake_service_53' => [
                'rate_key'        => "ALTER TABLE `tf_intake_service` ADD COLUMN `rate_key` VARCHAR(64) NULL",
                'rate_frozen_at'  => "ALTER TABLE `tf_intake_service` ADD COLUMN `rate_frozen_at` DATETIME NULL AFTER `rate_key`",
                'rate_drift_json' => "ALTER TABLE `tf_intake_service` ADD COLUMN `rate_drift_json` TEXT NULL AFTER `rate_frozen_at`",
            ],
            // schema 55 (tf-stage6): what an agent carries for quotations, and who set the flag when
            'customers_55' => [
                'quote_currency'   => "ALTER TABLE `customers` ADD COLUMN `quote_currency` CHAR(3) NULL",
                'quote_markup_pct' => "ALTER TABLE `customers` ADD COLUMN `quote_markup_pct` DECIMAL(6,2) NULL AFTER `quote_currency`",
                'agent_set_by'     => "ALTER TABLE `customers` ADD COLUMN `agent_set_by` VARCHAR(100) NULL AFTER `quote_markup_pct`",
                'agent_set_at'     => "ALTER TABLE `customers` ADD COLUMN `agent_set_at` DATETIME NULL AFTER `agent_set_by`",
                'agent_note'       => "ALTER TABLE `customers` ADD COLUMN `agent_note` VARCHAR(255) NULL AFTER `agent_set_at`",
            ],
            'tf_intake' => [
                // schema 49 (tf-child-r1): each child's age and bedding, for the Child Fare Review
                'child_json' => "ALTER TABLE `tf_intake` ADD COLUMN `child_json` TEXT NULL AFTER `pax_children`",
                'confirmed_at' => "ALTER TABLE `tf_intake` ADD COLUMN `confirmed_at` DATETIME NULL AFTER `converted_by`",
                'confirmed_by' => "ALTER TABLE `tf_intake` ADD COLUMN `confirmed_by` VARCHAR(100) NULL AFTER `confirmed_at`",
                'rooms_sgl'    => "ALTER TABLE `tf_intake` ADD COLUMN `rooms_sgl` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `rooms_note`",
                'rooms_dbl'    => "ALTER TABLE `tf_intake` ADD COLUMN `rooms_dbl` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `rooms_sgl`",
                'rooms_twn'    => "ALTER TABLE `tf_intake` ADD COLUMN `rooms_twn` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `rooms_dbl`",
                'rooms_tpl'    => "ALTER TABLE `tf_intake` ADD COLUMN `rooms_tpl` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `rooms_twn`",
                'markup_pct'   => "ALTER TABLE `tf_intake` ADD COLUMN `markup_pct` DECIMAL(6,2) NULL AFTER `note`",
                'markup_by'    => "ALTER TABLE `tf_intake` ADD COLUMN `markup_by` VARCHAR(100) NULL AFTER `markup_pct`",
                'markup_at'    => "ALTER TABLE `tf_intake` ADD COLUMN `markup_at` DATETIME NULL AFTER `markup_by`",
                // schema 35: Management's note beside the markup, kept with the markup and shown only under Pricing —
                // it used to go into the inquiry's general note, which the desks' cards show as "Notes"
                'markup_note'  => "ALTER TABLE `tf_intake` ADD COLUMN `markup_note` VARCHAR(500) NULL AFTER `markup_at`",
            ],
            // schema 12: whose tour it is (ownership, kept apart from who last edited it),
            // and archiving in place of deleting
            'tf_intake_12' => [
                'handler_id'     => "ALTER TABLE `tf_intake` ADD COLUMN `handler_id` INT UNSIGNED NULL AFTER `status`",
                'handler_name'   => "ALTER TABLE `tf_intake` ADD COLUMN `handler_name` VARCHAR(190) NULL AFTER `handler_id`",
                'handler_set_by' => "ALTER TABLE `tf_intake` ADD COLUMN `handler_set_by` VARCHAR(100) NULL AFTER `handler_name`",
                'handler_set_at' => "ALTER TABLE `tf_intake` ADD COLUMN `handler_set_at` DATETIME NULL AFTER `handler_set_by`",
                'archived_at'    => "ALTER TABLE `tf_intake` ADD COLUMN `archived_at` DATETIME NULL AFTER `updated_by`",
                'archived_by'    => "ALTER TABLE `tf_intake` ADD COLUMN `archived_by` VARCHAR(100) NULL AFTER `archived_at`",
                'archive_note'   => "ALTER TABLE `tf_intake` ADD COLUMN `archive_note` VARCHAR(400) NULL AFTER `archived_by`",
            ],
            // schema 14: the email as pasted is kept untouched beside the copy the reader
            // works from, which staff may correct (a missing year, say) before it is read
            'tf_intake_14' => [
                'source_text_original' => "ALTER TABLE `tf_intake` ADD COLUMN `source_text_original` MEDIUMTEXT NULL AFTER `source_text`",
                'source_edited_at'     => "ALTER TABLE `tf_intake` ADD COLUMN `source_edited_at` DATETIME NULL AFTER `source_text_original`",
                'source_edited_by'     => "ALTER TABLE `tf_intake` ADD COLUMN `source_edited_by` VARCHAR(100) NULL AFTER `source_edited_at`",
            ],
            // schema 15: where a night's meal plan came from — the night's own line ('day'),
            // the meal basis the agent wrote once for the whole tour ('tour', with the words),
            // or a person ('human') — so a figure taken from "accommodation H/B" is never
            // passed off as written on the night
            'tf_intake_day_15' => [
                'meal_source' => "ALTER TABLE `tf_intake_day` ADD COLUMN `meal_source` VARCHAR(12) NULL AFTER `meal_plan`",
                'meal_quote'  => "ALTER TABLE `tf_intake_day` ADD COLUMN `meal_quote` VARCHAR(190) NULL AFTER `meal_source`",
            ],
            // schema 16: the figure copied onto a night is what the agent is quoted on, and it
            // stays until somebody replaces it. `rate_key` is what it was priced for (hotel,
            // date, room type, meal plan, market); `rate_drift_json` is the panel's answer when
            // that has since changed, shown beside the night rather than written over it
            'tf_intake_day_16' => [
                'rate_frozen_at'  => "ALTER TABLE `tf_intake_day` ADD COLUMN `rate_frozen_at` DATETIME NULL AFTER `room_types_json`",
                'rate_key'        => "ALTER TABLE `tf_intake_day` ADD COLUMN `rate_key` VARCHAR(120) NULL AFTER `rate_frozen_at`",
                'rate_drift_json' => "ALTER TABLE `tf_intake_day` ADD COLUMN `rate_drift_json` TEXT NULL AFTER `rate_key`",
            ],
            // schema 17: two figures on every driven leg — Google's road distance and the
            // company's own driven record — with `km` the one the quotation is costed on
            'tf_intake_sector_17' => [
                'google_km'  => "ALTER TABLE `tf_intake_sector` ADD COLUMN `google_km` DECIMAL(10,2) NULL AFTER `km_high`",
                'google_by'  => "ALTER TABLE `tf_intake_sector` ADD COLUMN `google_by` ENUM('google','not_found','no_key','error','none') NOT NULL DEFAULT 'none' AFTER `google_km`",
                'google_why' => "ALTER TABLE `tf_intake_sector` ADD COLUMN `google_why` VARCHAR(255) NULL AFTER `google_by`",
                'actual_km'  => "ALTER TABLE `tf_intake_sector` ADD COLUMN `actual_km` DECIMAL(10,2) NULL AFTER `google_why`",
                'actual_by'  => "ALTER TABLE `tf_intake_sector` ADD COLUMN `actual_by` ENUM('history','town_history','none') NOT NULL DEFAULT 'none' AFTER `actual_km`",
                'actual_why' => "ALTER TABLE `tf_intake_sector` ADD COLUMN `actual_why` VARCHAR(400) NULL AFTER `actual_by`",
            ],
            // schema 18: how many of the journeys behind the driven figure were entered after
            // a tour ran (the record learns from those first)
            'tf_intake_sector_18' => [
                'samples_post' => "ALTER TABLE `tf_intake_sector` ADD COLUMN `samples_post` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `samples_dropped`",
                // a leg somebody took off stays off: the row is kept as the marker
                'removed_at'   => "ALTER TABLE `tf_intake_sector` ADD COLUMN `removed_at` DATETIME NULL AFTER `waived_by`",
                'removed_by'   => "ALTER TABLE `tf_intake_sector` ADD COLUMN `removed_by` VARCHAR(100) NULL AFTER `removed_at`",
            ],
            // schema 19: which leg of the plan this row is. A leg somebody edits stays the
            // plan's leg — the kilometres changed, not the journey — so the plan knows not to
            // lay it down a second time, and goes on planning the rest of that day around it.
            // Empty on a leg a person added themselves: the plan never made that one.
            'tf_intake_sector_19' => [
                'plan_key' => "ALTER TABLE `tf_intake_sector` ADD COLUMN `plan_key` VARCHAR(400) NULL AFTER `removed_by`",
            ],
            // schema 20: the quotation version the agent accepted travels with the tour, and
            // the Hotel Reservations & Activities desk's "all confirmed" mark sits on it
            'tf_tour_20' => [
                'quote_id'                  => "ALTER TABLE `tf_tour` ADD COLUMN `quote_id` INT UNSIGNED NULL AFTER `note`",
                'quote_version'             => "ALTER TABLE `tf_tour` ADD COLUMN `quote_version` SMALLINT UNSIGNED NULL AFTER `quote_id`",
                'reservations_confirmed_at' => "ALTER TABLE `tf_tour` ADD COLUMN `reservations_confirmed_at` DATETIME NULL AFTER `quote_version`",
                'reservations_confirmed_by' => "ALTER TABLE `tf_tour` ADD COLUMN `reservations_confirmed_by` VARCHAR(100) NULL AFTER `reservations_confirmed_at`",
            ],
            // schema 21: the Transport Desk's "work complete" mark — the tour leaves the red
            // Transport count when it is set, as it leaves Hotel Bookings on the hotel mark
            'tf_tour_21' => [
                'transport_complete_at' => "ALTER TABLE `tf_tour` ADD COLUMN `transport_complete_at` DATETIME NULL AFTER `reservations_confirmed_by`",
                'transport_complete_by' => "ALTER TABLE `tf_tour` ADD COLUMN `transport_complete_by` VARCHAR(100) NULL AFTER `transport_complete_at`",
            ],
            // schema 22: a tour deleted by Admin — archived, out of every list and count, restorable
            'tf_tour_22' => [
                'archived_at'  => "ALTER TABLE `tf_tour` ADD COLUMN `archived_at` DATETIME NULL AFTER `transport_complete_by`",
                'archived_by'  => "ALTER TABLE `tf_tour` ADD COLUMN `archived_by` VARCHAR(100) NULL AFTER `archived_at`",
                'archive_note' => "ALTER TABLE `tf_tour` ADD COLUMN `archive_note` VARCHAR(400) NULL AFTER `archived_by`",
            ],
            // schema 23: a crew role is a category (Driver, Chauffeur guide, National guide …)
            // in a language; the quotation screen picks the two from the master, and the bata
            // belongs to the pair. Filled from the kind for the rows already there (below).
            'tf_crew_role' => [
                'category' => "ALTER TABLE `tf_crew_role` ADD COLUMN `category` VARCHAR(60) NULL AFTER `kind`",
            ],
            // schema 24: one crew row per person, with the days they work; a person replaced
            // mid-tour keeps their row, ended, and points at the replacement
            // schema 25: the closest priced-list entry, offered for a person to accept
            'tf_intake_service_25' => [
                'suggest_code' => "ALTER TABLE `tf_intake_service` ADD COLUMN `suggest_code` VARCHAR(100) NULL AFTER `match_note`",
            ],
            // schema 28: a person's own wording for a day on the client quotation
            'tf_intake_day_28' => [
                'client_text' => "ALTER TABLE `tf_intake_day` ADD COLUMN `client_text` VARCHAR(2000) NULL AFTER `special_note`",
            ],
            // schema 29: a line put on a day from the Free Visits master says so; the activities
            // master has a destination (the priced list itself is never altered: see tf_fee_meta)
            'tf_intake_service_29' => [
                'free_visit_id' => "ALTER TABLE `tf_intake_service` ADD COLUMN `free_visit_id` INT UNSIGNED NULL AFTER `fee_code`",
            ],
            'tf_excursion_rate' => [
                'location' => "ALTER TABLE `tf_excursion_rate` ADD COLUMN `location` VARCHAR(120) NULL AFTER `name`",
            ],
            // schema 32: the dates rule. The departure day is the day after the last night, never a
            // hotel night: its own wording and note live on the inquiry. The email's own dates are
            // kept beside the inquiry's, and a person's confirmation when the two differ is kept
            // with the pair it was given for, so it lapses the moment either changes.
            'tf_intake_32' => [
                'departure_text'       => "ALTER TABLE `tf_intake` ADD COLUMN `departure_text` VARCHAR(2000) NULL AFTER `departure_flight`",
                'departure_narrative'  => "ALTER TABLE `tf_intake` ADD COLUMN `departure_narrative` TEXT NULL AFTER `departure_text`",
                'departure_note'       => "ALTER TABLE `tf_intake` ADD COLUMN `departure_note` VARCHAR(500) NULL AFTER `departure_narrative`",
                'email_arrival_date'   => "ALTER TABLE `tf_intake` ADD COLUMN `email_arrival_date` DATE NULL AFTER `departure_note`",
                'email_departure_date' => "ALTER TABLE `tf_intake` ADD COLUMN `email_departure_date` DATE NULL AFTER `email_arrival_date`",
                'dates_confirmed_key'  => "ALTER TABLE `tf_intake` ADD COLUMN `dates_confirmed_key` VARCHAR(40) NULL AFTER `email_departure_date`",
                'dates_confirmed_by'   => "ALTER TABLE `tf_intake` ADD COLUMN `dates_confirmed_by` VARCHAR(100) NULL AFTER `dates_confirmed_key`",
                'dates_confirmed_at'   => "ALTER TABLE `tf_intake` ADD COLUMN `dates_confirmed_at` DATETIME NULL AFTER `dates_confirmed_by`",
            ],
            // schema 32: a crew line's days are worked out from the programme unless a person typed them
            'tf_intake_crew_32' => [
                'days_by' => "ALTER TABLE `tf_intake_crew` ADD COLUMN `days_by` ENUM('auto','human') NULL AFTER `days`",
            ],
            // schema 30: where a free visit copied from past inquiries came from (it has no destination until a person gives it one)
            'tf_free_visit' => [
                'origin' => "ALTER TABLE `tf_free_visit` ADD COLUMN `origin` VARCHAR(255) NULL AFTER `source`",
            ],
            // schema 44 (tf-crew-master-r1): the Driver & Guide Master — category, the personal details, the garage,
            // the licence copies, the guide's rates, the bank details; the registration a record was migrated from
            'tf_crew_person_44' => [
                'category'           => "ALTER TABLE `tf_crew_person` ADD COLUMN `category` VARCHAR(10) NULL AFTER `kind`",
                'category_confirmed' => "ALTER TABLE `tf_crew_person` ADD COLUMN `category_confirmed` TINYINT(1) NOT NULL DEFAULT 1 AFTER `category`",
                'nickname'           => "ALTER TABLE `tf_crew_person` ADD COLUMN `nickname` VARCHAR(120) NULL AFTER `name`",
                'id_type'            => "ALTER TABLE `tf_crew_person` ADD COLUMN `id_type` ENUM('nic','passport') NOT NULL DEFAULT 'nic' AFTER `nic`",
                'whatsapp'           => "ALTER TABLE `tf_crew_person` ADD COLUMN `whatsapp` VARCHAR(60) NULL AFTER `mobile`",
                'home_address'       => "ALTER TABLE `tf_crew_person` ADD COLUMN `home_address` VARCHAR(400) NULL AFTER `email`",
                'garage_address'     => "ALTER TABLE `tf_crew_person` ADD COLUMN `garage_address` VARCHAR(400) NULL AFTER `home_address`",
                'garage_same'        => "ALTER TABLE `tf_crew_person` ADD COLUMN `garage_same` TINYINT(1) NOT NULL DEFAULT 0 AFTER `garage_address`",
                'photo_file'         => "ALTER TABLE `tf_crew_person` ADD COLUMN `photo_file` VARCHAR(255) NULL AFTER `garage_same`",
                'licence_file'       => "ALTER TABLE `tf_crew_person` ADD COLUMN `licence_file` VARCHAR(255) NULL AFTER `licence_expiry`",
                'guide_licence_file' => "ALTER TABLE `tf_crew_person` ADD COLUMN `guide_licence_file` VARCHAR(255) NULL AFTER `guide_licence_expiry`",
                'japanese_note'      => "ALTER TABLE `tf_crew_person` ADD COLUMN `japanese_note` VARCHAR(400) NULL AFTER `languages`",
                'overnight_bata'     => "ALTER TABLE `tf_crew_person` ADD COLUMN `overnight_bata` DECIMAL(10,2) NULL AFTER `bata_currency`",
                'bank_name'          => "ALTER TABLE `tf_crew_person` ADD COLUMN `bank_name` VARCHAR(120) NULL AFTER `overnight_bata`",
                'bank_branch'        => "ALTER TABLE `tf_crew_person` ADD COLUMN `bank_branch` VARCHAR(120) NULL AFTER `bank_name`",
                'bank_account_no'    => "ALTER TABLE `tf_crew_person` ADD COLUMN `bank_account_no` VARCHAR(60) NULL AFTER `bank_branch`",
                'bank_account_name'  => "ALTER TABLE `tf_crew_person` ADD COLUMN `bank_account_name` VARCHAR(190) NULL AFTER `bank_account_no`",
                'bank_verified'      => "ALTER TABLE `tf_crew_person` ADD COLUMN `bank_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `bank_account_name`",
                'bank_verified_at'   => "ALTER TABLE `tf_crew_person` ADD COLUMN `bank_verified_at` DATE NULL AFTER `bank_verified`",
                'bank_verified_by'   => "ALTER TABLE `tf_crew_person` ADD COLUMN `bank_verified_by` VARCHAR(100) NULL AFTER `bank_verified_at`",
                'payment_remarks'    => "ALTER TABLE `tf_crew_person` ADD COLUMN `payment_remarks` VARCHAR(400) NULL AFTER `bank_verified_by`",
                'migrated_from'      => "ALTER TABLE `tf_crew_person` ADD COLUMN `migrated_from` VARCHAR(50) NULL AFTER `payment_remarks`",
                'migrated_at'        => "ALTER TABLE `tf_crew_person` ADD COLUMN `migrated_at` DATETIME NULL AFTER `migrated_from`",
            ],
            // schema 44: the crew line copies the person's category and their rate per kilometre the day they are
            // engaged, so a rate changed on the master afterwards never moves a tour already settled
            'tf_transport_crew_44' => [
                'person_category'    => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_category` VARCHAR(10) NULL AFTER `person_bata`",
                'person_km_rate'     => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_km_rate` DECIMAL(10,2) NULL AFTER `person_category`",
                'person_rate_from'   => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_rate_from` DATE NULL AFTER `person_km_rate`",
                // which rate was copied: the driver's special rate, or the general rate of their vehicle's class
                'person_rate_source' => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_rate_source` VARCHAR(10) NULL AFTER `person_rate_from`",
                'person_rate_class'  => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_rate_class` VARCHAR(20) NULL AFTER `person_rate_source`",
                // the agreed bata's date and, for a guide, the bata / overnight allowance per night — copied the same day
                'person_bata_from'   => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_bata_from` DATE NULL AFTER `person_rate_class`",
                'person_overnight'   => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_overnight` DECIMAL(10,2) NULL AFTER `person_bata_from`",
                // where the bata (guide fee) copied onto the line came from: the person's own agreed figure, the standard
                // in force that day, or a figure typed at allocation; the same for the overnight allowance
                'person_bata_source'      => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_bata_source` VARCHAR(10) NULL AFTER `person_overnight`",
                'person_overnight_from'   => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_overnight_from` DATE NULL AFTER `person_bata_source`",
                'person_overnight_source' => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_overnight_source` VARCHAR(10) NULL AFTER `person_overnight_from`",
            ],
            // schema 44: the vehicle's class on the Transport Rates master, which gives its general rate per kilometre
            'tf_crew_vehicle' => [
                'rate_class' => "ALTER TABLE `tf_crew_vehicle` ADD COLUMN `rate_class` VARCHAR(20) NULL AFTER `roof_type`",
            ],
            'tf_transport_crew' => [
                'date_from'     => "ALTER TABLE `tf_transport_crew` ADD COLUMN `date_from` DATE NULL AFTER `note`",
                'date_to'       => "ALTER TABLE `tf_transport_crew` ADD COLUMN `date_to` DATE NULL AFTER `date_from`",
                'replaces'      => "ALTER TABLE `tf_transport_crew` ADD COLUMN `replaces` INT UNSIGNED NULL AFTER `date_to`",
                'replaced_by'   => "ALTER TABLE `tf_transport_crew` ADD COLUMN `replaced_by` INT UNSIGNED NULL AFTER `replaces`",
                'replaced_at'   => "ALTER TABLE `tf_transport_crew` ADD COLUMN `replaced_at` DATETIME NULL AFTER `replaced_by`",
                'replaced_note' => "ALTER TABLE `tf_transport_crew` ADD COLUMN `replaced_note` VARCHAR(400) NULL AFTER `replaced_at`",
            ],
            // schema 20: when to chase the agent about a quotation
            'tf_intake_20' => [
                'follow_up_on'   => "ALTER TABLE `tf_intake` ADD COLUMN `follow_up_on` DATE NULL AFTER `confirmed_by`",
                'follow_up_note' => "ALTER TABLE `tf_intake` ADD COLUMN `follow_up_note` VARCHAR(255) NULL AFTER `follow_up_on`",
                // once a person has touched the crew it is theirs: a guide taken off stays off
                'crew_by'        => "ALTER TABLE `tf_intake` ADD COLUMN `crew_by` ENUM('auto','human') NOT NULL DEFAULT 'auto' AFTER `follow_up_note`",
            ],
            // schema 18: the transport card carries Google's figure beside the costed one, and
            // takes the actual kilometres driven after the tour — the record every later
            // quotation learns from
            'tf_transport_leg' => [
                'google_km'   => "ALTER TABLE `tf_transport_leg` ADD COLUMN `google_km` DECIMAL(10,2) NULL AFTER `extra_km`",
                'quoted_by'   => "ALTER TABLE `tf_transport_leg` ADD COLUMN `quoted_by` VARCHAR(16) NULL AFTER `google_km`",
                'actual_km'   => "ALTER TABLE `tf_transport_leg` ADD COLUMN `actual_km` DECIMAL(10,2) NULL AFTER `quoted_by`",
                'actual_by'   => "ALTER TABLE `tf_transport_leg` ADD COLUMN `actual_by` VARCHAR(100) NULL AFTER `actual_km`",
                'actual_at'   => "ALTER TABLE `tf_transport_leg` ADD COLUMN `actual_at` DATETIME NULL AFTER `actual_by`",
                'actual_note' => "ALTER TABLE `tf_transport_leg` ADD COLUMN `actual_note` VARCHAR(255) NULL AFTER `actual_at`",
            ],
            'tf_transport_job_18' => [
                'actual_km_total' => "ALTER TABLE `tf_transport_job` ADD COLUMN `actual_km_total` DECIMAL(10,2) NULL AFTER `extra_km_total`",
            ],
            'tf_tour' => [
                'handler_id'   => "ALTER TABLE `tf_tour` ADD COLUMN `handler_id` INT UNSIGNED NULL AFTER `amending`",
                'handler_name' => "ALTER TABLE `tf_tour` ADD COLUMN `handler_name` VARCHAR(190) NULL AFTER `handler_id`",
            ],
            'tf_hotel_job' => [
                // schema 35: the room type as this booking describes it (the master's name unless the desk changes it here)
                'room_type_text' => "ALTER TABLE `tf_hotel_job` ADD COLUMN `room_type_text` VARCHAR(120) NULL AFTER `rooms_note`",
                'archived_at'  => "ALTER TABLE `tf_hotel_job` ADD COLUMN `archived_at` DATETIME NULL AFTER `updated_by`",
                'archived_by'  => "ALTER TABLE `tf_hotel_job` ADD COLUMN `archived_by` VARCHAR(100) NULL AFTER `archived_at`",
                'archive_note' => "ALTER TABLE `tf_hotel_job` ADD COLUMN `archive_note` VARCHAR(400) NULL AFTER `archived_by`",
            ],
            'tf_intake_service_39' => [
                // schema 39 (tf-entrance-r1): the place of the Place to Visit / Entrance Fee Master a line is, and the rates it
                // was priced from — the entrance (its child figure, and the figure in the master's own currency: an LKR rate is
                // converted at the day's USD rate) and, at a safari park, the jeep per vehicle
                'place_id'      => "ALTER TABLE `tf_intake_service` ADD COLUMN `place_id` INT UNSIGNED NULL AFTER `fee_code`",
                'rate_id'       => "ALTER TABLE `tf_intake_service` ADD COLUMN `rate_id` INT UNSIGNED NULL AFTER `place_id`",
                'child_usd'     => "ALTER TABLE `tf_intake_service` ADD COLUMN `child_usd` DECIMAL(10,2) NULL AFTER `unit_usd`",
                'rate_currency' => "ALTER TABLE `tf_intake_service` ADD COLUMN `rate_currency` CHAR(3) NULL AFTER `child_usd`",
                'rate_adult'    => "ALTER TABLE `tf_intake_service` ADD COLUMN `rate_adult` DECIMAL(12,2) NULL AFTER `rate_currency`",
                'rate_child'    => "ALTER TABLE `tf_intake_service` ADD COLUMN `rate_child` DECIMAL(12,2) NULL AFTER `rate_adult`",
                'jeep_rate_id'  => "ALTER TABLE `tf_intake_service` ADD COLUMN `jeep_rate_id` INT UNSIGNED NULL AFTER `rate_child`",
                'jeep_usd'      => "ALTER TABLE `tf_intake_service` ADD COLUMN `jeep_usd` DECIMAL(10,2) NULL AFTER `jeep_rate_id`",
            ],
            'tf_intake_service_40' => [
                // schema 40 (tf-meals-r1): a lunch or dinner line linked to the Meal Master, with the adults and children it is for,
                // the time, and the figures it was priced from in the meal's own currency (the tax and the total kept as they were)
                'meal_id'      => "ALTER TABLE `tf_intake_service` ADD COLUMN `meal_id` INT UNSIGNED NULL AFTER `jeep_usd`",
                'qty_adult'    => "ALTER TABLE `tf_intake_service` ADD COLUMN `qty_adult` SMALLINT NULL AFTER `meal_id`",
                'qty_child'    => "ALTER TABLE `tf_intake_service` ADD COLUMN `qty_child` SMALLINT NULL AFTER `qty_adult`",
                'meal_time'    => "ALTER TABLE `tf_intake_service` ADD COLUMN `meal_time` VARCHAR(5) NULL AFTER `qty_child`",
                'tax_pct'      => "ALTER TABLE `tf_intake_service` ADD COLUMN `tax_pct` DECIMAL(6,2) NULL AFTER `meal_time`",
                'tax_cur'      => "ALTER TABLE `tf_intake_service` ADD COLUMN `tax_cur` DECIMAL(12,2) NULL AFTER `tax_pct`",
                'amount_cur'   => "ALTER TABLE `tf_intake_service` ADD COLUMN `amount_cur` DECIMAL(12,2) NULL AFTER `tax_cur`",
                // a meal arranged outside although the hotel's plan gives it that night: the reason a person gave, and who
                'meal_ok_note' => "ALTER TABLE `tf_intake_service` ADD COLUMN `meal_ok_note` VARCHAR(255) NULL AFTER `amount_cur`",
                // a lunch the system picked from the route (1) rather than a person (0): only those are re-picked when the route changes
                'meal_auto'    => "ALTER TABLE `tf_intake_service` ADD COLUMN `meal_auto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `meal_id`",
                // the restaurant's booking email and contact for this booking (from the Meal Master when chosen, changeable there):
                // where the booking voucher goes at Confirm Tour
                'meal_email'   => "ALTER TABLE `tf_intake_service` ADD COLUMN `meal_email` VARCHAR(190) NULL AFTER `meal_ok_note`",
                'meal_contact' => "ALTER TABLE `tf_intake_service` ADD COLUMN `meal_contact` VARCHAR(120) NULL AFTER `meal_email`",
                'meal_phone'   => "ALTER TABLE `tf_intake_service` ADD COLUMN `meal_phone` VARCHAR(60) NULL AFTER `meal_contact`",
            ],
            'tf_meal_40' => [
                // the actual amount the restaurant charges, in LKR (the booking voucher's figures; the quotation rate is USD)
                'adult_lkr' => "ALTER TABLE `tf_meal` ADD COLUMN `adult_lkr` DECIMAL(12,2) NULL AFTER `currency`",
                'child_lkr' => "ALTER TABLE `tf_meal` ADD COLUMN `child_lkr` DECIMAL(12,2) NULL AFTER `adult_lkr`",
            ],
            'tf_intake_40' => [
                // schema 40: the days a person took the automatic lunch (dinner) away ("lunch:3,lunch:5"), so the system does not put it back
                'meal_skip' => "ALTER TABLE `tf_intake` ADD COLUMN `meal_skip` VARCHAR(400) NULL AFTER `markup_note`",
            ],
            'tf_intake_day_40' => [
                // schema 40: on a Half Board night, the meal the hotel gives besides breakfast (blank: the Meal Master's setting, dinner)
                'hb_meal' => "ALTER TABLE `tf_intake_day` ADD COLUMN `hb_meal` ENUM('lunch','dinner') NULL AFTER `meal_plan`",
            ],
            'tf_service_job_39' => [
                // schema 39: the place of the master an activity line stands for — Ticket Stock serves it by its place
                'place_id' => "ALTER TABLE `tf_service_job` ADD COLUMN `place_id` INT UNSIGNED NULL AFTER `fee_code`",
            ],
            'tf_ticket_attraction_39' => [
                // schema 39: an attraction under ticket control is a place of the master
                'place_id' => "ALTER TABLE `tf_ticket_attraction` ADD COLUMN `place_id` INT UNSIGNED NULL AFTER `fee_id`",
            ],
            'tf_ticket_batch_38' => [
                // schema 38 (tf-tickets-r2): the purchase of each batch — when it was bought / paid for, and what was paid for it
                'paid_on'     => "ALTER TABLE `tf_ticket_batch` ADD COLUMN `paid_on` DATE NULL AFTER `received_on`",
                'amount_paid' => "ALTER TABLE `tf_ticket_batch` ADD COLUMN `amount_paid` DECIMAL(12,2) NULL AFTER `count`",
            ],
            'tf_service_job_37' => [
                // schema 37: the Entrance Fee master's row the line stands for, so the ticket stock knows whose tickets it takes
                'fee_id'   => "ALTER TABLE `tf_service_job` ADD COLUMN `fee_id` INT NULL AFTER `pay_mode`",
                'fee_code' => "ALTER TABLE `tf_service_job` ADD COLUMN `fee_code` VARCHAR(100) NULL AFTER `fee_id`",
            ],
            'tf_transport_crew_36' => [
                // schema 36: the person engaged, as the Drivers & Guides master knows them, and their phone for the pack
                'person_id'    => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_id` INT UNSIGNED NULL AFTER `supplier_acc`",
                'person_phone' => "ALTER TABLE `tf_transport_crew` ADD COLUMN `person_phone` VARCHAR(60) NULL AFTER `person_name`",
            ],
            'tf_transport_job' => [
                'archived_at'  => "ALTER TABLE `tf_transport_job` ADD COLUMN `archived_at` DATETIME NULL AFTER `updated_by`",
                'archived_by'  => "ALTER TABLE `tf_transport_job` ADD COLUMN `archived_by` VARCHAR(100) NULL AFTER `archived_at`",
                'archive_note' => "ALTER TABLE `tf_transport_job` ADD COLUMN `archive_note` VARCHAR(400) NULL AFTER `archived_by`",
            ],
            'tf_intake_vehicle' => [
                'group_id'    => "ALTER TABLE `tf_intake_vehicle` ADD COLUMN `group_id` INT UNSIGNED NULL AFTER `intake_id`",
                'day_from'    => "ALTER TABLE `tf_intake_vehicle` ADD COLUMN `day_from` SMALLINT UNSIGNED NULL AFTER `group_id`",
                'day_to'      => "ALTER TABLE `tf_intake_vehicle` ADD COLUMN `day_to` SMALLINT UNSIGNED NULL AFTER `day_from`",
                'label'       => "ALTER TABLE `tf_intake_vehicle` ADD COLUMN `label` VARCHAR(40) NULL AFTER `day_to`",
                'rate_id'     => "ALTER TABLE `tf_intake_vehicle` ADD COLUMN `rate_id` INT UNSIGNED NULL AFTER `km_rate`",
                'master_rate' => "ALTER TABLE `tf_intake_vehicle` ADD COLUMN `master_rate` DECIMAL(10,2) NULL AFTER `rate_id`",
                'rate_by'     => "ALTER TABLE `tf_intake_vehicle` ADD COLUMN `rate_by` ENUM('master','manual','none') NOT NULL DEFAULT 'none' AFTER `master_rate`",
                'rate_note'   => "ALTER TABLE `tf_intake_vehicle` ADD COLUMN `rate_note` VARCHAR(200) NULL AFTER `rate_by`",
                'rate_set_by' => "ALTER TABLE `tf_intake_vehicle` ADD COLUMN `rate_set_by` VARCHAR(100) NULL AFTER `rate_note`",
                'rate_set_at' => "ALTER TABLE `tf_intake_vehicle` ADD COLUMN `rate_set_at` DATETIME NULL AFTER `rate_set_by`",
            ],
        ];

        foreach ($add as $table => $columns) {
            $table = preg_replace('/_\d+$/', '', $table);   // one table may have more than one block (schema 2 and schema 11, say)
            if (!$db->query("SHOW TABLES LIKE '{$table}'")->getResultArray()) {
                continue;
            }
            foreach ($columns as $column => $sql) {
                $has = $db->query("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column])->getResultArray();
                if ($has) {
                    continue;
                }
                try {
                    $db->query($sql);
                    $notes[] = "Added {$table}.{$column}.";
                } catch (\Throwable $e) {
                    $notes[] = "Could not add {$table}.{$column} ({$e->getMessage()}). The panel still works; that field will be unavailable.";
                }
            }
        }
        return $notes;
    }

    /**
     * Settings changes an INSERT IGNORE cannot make on an install that already has
     * the row. Each step checks first, so re-running is harmless, and anything a
     * person has deliberately chosen is left alone.
     */
    public static function upgradeSettings($db): array
    {
        // schema 34: the ready-made emails, the standard set written once when the table is empty
        $notesSeed = [];
        try {
            $n = (new EmailTemplates($db))->seed();
            if ($n > 0) {
                $notesSeed = ['Seeded ' . $n . ' ready-made email templates (Tour Desk Admin → Settings → Email templates).'];
            }
            // schema 40: the restaurant booking voucher's template, once, on a table seeded before it existed
            $m = (new EmailTemplates($db))->seedMissing();
            if ($m > 0) {
                $notesSeed[] = 'Added the ready-made email "Restaurant Booking Voucher" (Tour Desk Admin → Settings → Email templates).';
            }
        } catch (\Throwable $e) {
            // without the table there is nothing to seed; the health check reports a missing table
        }
        $notes = $notesSeed;
        $get = static function (string $k) use ($db): ?string {
            $r = $db->query('SELECT v FROM tf_meta WHERE k = ?', [$k])->getRowArray();
            return $r['v'] ?? null;
        };
        // schema 32, once: the email's own dates beside each inquiry's, read from the reading already
        // saved with it (no email is read again), and only where the email itself states a year; and
        // each open inquiry's crew days marked as the programme's or as typed by a person. A line whose
        // days are not the nights — the old default — was changed by somebody and stays theirs. Nothing
        // else is touched: no night, no date, no service, no figure.
        if ($get('dates_rule_backfilled') === null && self::hasColumns($db, 'tf_intake', ['email_arrival_date', 'email_departure_date'])) {
            $n = 0;
            $crewAuto = $crewHuman = 0;
            try {
                foreach ($db->query("SELECT id, extract_json, source_text, email_arrival_date, email_departure_date FROM tf_intake WHERE extract_json IS NOT NULL AND extract_json <> ''")->getResultArray() as $r) {
                    if ($r['email_arrival_date'] !== null || $r['email_departure_date'] !== null) {
                        continue;
                    }
                    $raw = json_decode((string) $r['extract_json'], true);
                    if (!is_array($raw) || !TextChecks::yearStated((string) ($r['source_text'] ?? ''))) {
                        continue;
                    }
                    $f   = is_array($raw['fields'] ?? null) ? $raw['fields'] : $raw;
                    $pick = static function ($node): ?string {
                        $v = is_array($node) ? ($node['value'] ?? null) : $node;
                        return TourDates::iso($v);
                    };
                    $ea = $pick($f['arrival_date'] ?? null);
                    $ed = $pick($f['departure_date'] ?? null);
                    if ($ea === null && $ed === null) {
                        continue;
                    }
                    $db->query('UPDATE tf_intake SET email_arrival_date = ?, email_departure_date = ? WHERE id = ?', [$ea, $ed, (int) $r['id']]);
                    $n++;
                }
                if (self::hasColumns($db, 'tf_intake_crew', ['days_by'])) {
                    $open = "SELECT i.id FROM tf_intake i WHERE i.status NOT IN ('confirmed','cancelled','declined','converted')" . (self::hasColumns($db, 'tf_intake', ['archived_at']) ? ' AND i.archived_at IS NULL' : '');
                    $db->query("UPDATE tf_intake_crew c JOIN tf_intake i ON i.id = c.intake_id SET c.days_by = 'auto' WHERE c.days_by IS NULL AND i.id IN ({$open}) AND (c.days IS NULL OR c.days = i.nights)");
                    $crewAuto = $db->affectedRows();
                    $db->query("UPDATE tf_intake_crew c JOIN tf_intake i ON i.id = c.intake_id SET c.days_by = 'human' WHERE c.days_by IS NULL AND i.id IN ({$open})");
                    $crewHuman = $db->affectedRows();
                }
                $db->query("INSERT INTO tf_meta (k, v) VALUES ('dates_rule_backfilled', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [(string) $n]);
                $notes[] = "The email's own dates were kept beside {$n} inquiry(ies), from the reading already saved with each. On open inquiries, {$crewAuto} crew line(s) now take their days from the programme and {$crewHuman} keep the days a person typed.";
            } catch (\Throwable $e) {
                $notes[] = 'The dates rule could not be set up on the inquiries already on file (' . $e->getMessage() . '). Nothing was changed on them.';
            }
        }
        // schema 29/30: the no-charge sightseeing stops already typed on inquiries are copied into
        // the Free Visits master once, so nothing typed so far is lost — each with no destination
        // (a destination is a person's word, never worked out) and a note of the inquiry and night
        // it came from. The lines themselves are left exactly as they are, not joined to the master.
        $hasOrigin = $db->query("SHOW TABLES LIKE 'tf_free_visit'")->getResultArray() && $db->query("SHOW COLUMNS FROM tf_free_visit LIKE 'origin'")->getResultArray();
        if ($get('free_visits_backfilled') === null && $hasOrigin) {
            $n = 0;
            try {
                $seen = [];
                foreach ($db->query('SELECT name FROM tf_free_visit')->getResultArray() as $r) {
                    $seen[mb_strtolower(trim((string) $r['name']))] = true;
                }
                foreach (self::noChargeStops($db) as $name => $from) {
                    if (isset($seen[mb_strtolower($name)])) {
                        continue;
                    }
                    $seen[mb_strtolower($name)] = true;
                    $db->query("INSERT INTO tf_free_visit (destination, name, kind, active, sort_order, source, origin, created_at, created_by) VALUES ('', ?, 'sightseeing', 1, 0, 'backfill', ?, ?, 'installer')", [$name, $from, date('Y-m-d H:i:s')]);
                    $n++;
                }
                $db->query("INSERT INTO tf_meta (k, v) VALUES ('free_visits_backfilled', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [(string) $n]);
                $db->query("INSERT INTO tf_meta (k, v) VALUES ('free_visits_unplaced', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", ['0']);
                if ($n > 0) {
                    $notes[] = "{$n} free visit(s) already on inquiries were copied into the Free Visits master, not placed under any destination until a person places them.";
                }
            } catch (\Throwable $e) {
                $notes[] = 'The free visits on inquiries could not be copied into the master: ' . $e->getMessage();
            }
        }
        // schema 30: the copies made by schema 29 were filed under the town of the night they were
        // on — a guess. Each one nobody has changed since is taken back to "not placed yet", the
        // town it was filed under kept in its note; one a person has edited is left as they left it.
        if ($get('free_visits_unplaced') === null && $hasOrigin) {
            try {
                $from = self::noChargeStops($db);
                $n = 0;
                foreach ($db->query("SELECT id, destination, name FROM tf_free_visit WHERE source = 'backfill' AND updated_at IS NULL AND destination <> ''")->getResultArray() as $r) {
                    $origin = mb_substr(($from[trim((string) $r['name'])] ?? 'Copied from a past inquiry') . ' — had been filed under ' . $r['destination'] . ' by the installer', 0, 255);
                    $db->query("UPDATE tf_free_visit SET destination = '', origin = ? WHERE id = ?", [$origin, (int) $r['id']]);
                    Activity::master($db, 'installer', 'free_visit', (int) $r['id'], 'Free Visits: ' . $r['name'] . ' taken back to "not placed yet" — its destination had been worked out by the installer, not given by a person',
                        [['what' => 'destination', 'from' => $r['destination'], 'to' => null]]);
                    $n++;
                }
                $db->query("INSERT INTO tf_meta (k, v) VALUES ('free_visits_unplaced', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [(string) $n]);
                if ($n > 0) {
                    $notes[] = "{$n} free visit(s) copied from past inquiries were taken back to \"not placed yet\": their destination had been worked out from the night, not given by a person.";
                }
            } catch (\Throwable $e) {
                $notes[] = 'The copied free visits could not be taken back to "not placed yet": ' . $e->getMessage();
            }
        }

        // schema 5: a bata is a local allowance, so it is held in rupees like vehicle.bata.
        // Only roles nobody has priced yet are moved; a currency somebody chose is theirs.
        try {
            if ($db->query("SHOW TABLES LIKE 'tf_crew_role'")->getResultArray()) {
                $db->query("UPDATE tf_crew_role SET currency = 'LKR' WHERE bata IS NULL AND currency = 'USD'");
            }
        } catch (\Throwable $e) {
            // a currency left at the old default costs nothing; the figure is what matters
        }

        if ($get('quote_plan_types') === null) {
            $db->query("INSERT INTO tf_meta (k, v) VALUES ('quote_plan_types', 'contract,migrated,addendum,series') ON DUPLICATE KEY UPDATE v = v");
            $notes[] = 'Quotations will use published contract rates only. Tactical and special-offer plans stay on file for the payment side and are never quoted; change the tf_meta setting quote_plan_types if that list should differ.';
        }

        if ($get('extract_provider') === null) {
            $db->query("INSERT INTO tf_meta (k, v) VALUES ('extract_provider', 'anthropic') ON DUPLICATE KEY UPDATE v = v");
            $notes[] = 'Emails are now read by Claude (Anthropic). Set ANTHROPIC_API_KEY in .env; switch services later by changing the tf_meta setting extract_provider.';
        }
        // the first release defaulted to an OpenAI model; move it in step with the provider
        if ($get('extract_provider') === 'anthropic' && in_array((string) $get('extract_model'), ['gpt-4o', 'gpt-4o-mini', ''], true)) {
            $db->query("UPDATE tf_meta SET v = 'claude-sonnet-5' WHERE k = 'extract_model'");
            $notes[] = 'Reading model set to claude-sonnet-5.';
        }

        // schema 17: the quotation is costed on Google's road distance by preference, the
        // driven record kept beside it. Changed on Master Files → Mileage Allowances.
        // schema 20: tour numbers of their own, from settings; the reasons an activity or a
        // ticket can be a problem (seeded here, after the enum that holds them is widened)
        if ($get('tour_prefix') === null) {
            $db->query("INSERT IGNORE INTO tf_meta (k, v) VALUES ('tour_prefix', 'AVH'), ('tour_year', ''), ('tour_next', '1'), ('tour_pad', '5')");
            $notes[] = 'Tours confirmed from now on take a number of their own, AVH-<year>-<count>; the prefix and the count are settings on the Manage screen. Tours already confirmed keep their number.';
        }
        try {
            $db->query("INSERT IGNORE INTO tf_reason (code, kind, name, sort_order) VALUES
                ('S_FULL', 'service_problem', 'No availability on that date', 10),
                ('S_CLOSED', 'service_problem', 'Closed or not running that day', 20),
                ('S_PRICE', 'service_problem', 'Price differs from what was quoted', 30),
                ('S_NOREPLY', 'service_problem', 'No reply from the supplier', 40),
                ('S_OTHER', 'service_problem', 'Other — see the note', 90)");
        } catch (\Throwable $e) {
            $notes[] = 'The activity problem reasons could not be seeded (' . $e->getMessage() . ').';
        }

        // schema 21: what a hotel can answer, in the desk's words; the company's own lines on
        // the booking request and the voucher (edited on Manage → Settings, never in code)
        try {
            $db->query("INSERT IGNORE INTO tf_reason (code, kind, name, sort_order) VALUES
                ('REJECTED', 'hotel_problem', 'Rejected / unavailable — the hotel cannot take the booking', 5),
                ('ALTERNATIVE', 'hotel_problem', 'Alternative required — another hotel has to be found', 6)");
        } catch (\Throwable $e) {
            $notes[] = 'The hotel reply reasons could not be seeded (' . $e->getMessage() . ').';
        }
        if ($get('company_name') === null) {
            $db->query("INSERT IGNORE INTO tf_meta (k, v) VALUES ('company_name', 'Avenir Holidays (Pvt) Ltd'), ('company_address', 'No. 126/1, Park Road, Colombo 05, Sri Lanka'), ('company_phone', '(+94) 114 215 977'), ('company_email', 'traveljp@avenirholidays.com, land@avenirholidays.com')");
            $notes[] = 'The company lines on hotel booking requests and vouchers are settings on the Manage screen; check them there.';
        }

        // schema 22: the privilege behind the Tour Desk's Admin tab reads as such under Group
        // Privileges; only the installer's own name is touched, never one a person typed
        try {
            $db->query("UPDATE menu_list SET name = 'Tour Desk Admin' WHERE url = 'tour/manage' AND name = 'Tour Desk Manage' AND del = 0");
        } catch (\Throwable $e) {
            // the label is a convenience; the privilege works under either name
        }

        // schema 23: every crew role gets a category so the quotation screen can offer type
        // and language separately. A role nobody has categorised is its kind, plainly —
        // "Driver" or "Guide" — and Admin renames it under Master Files → Crew Bata.
        try {
            if ($db->query("SHOW COLUMNS FROM `tf_crew_role` LIKE 'category'")->getResultArray()) {
                $n = $db->query("SELECT COUNT(*) n FROM tf_crew_role WHERE category IS NULL OR category = ''")->getRowArray()['n'] ?? 0;
                if ((int) $n > 0) {
                    $db->query("UPDATE tf_crew_role SET category = CASE kind WHEN 'driver' THEN 'Driver' ELSE 'Guide' END WHERE category IS NULL OR category = ''");
                    $notes[] = 'Driver and guide bata is now maintained under Master Files → Crew Bata (Admin), not on the quotation screen. Each role was given its kind as its category ("Driver" / "Guide"); rename or add categories there.';
                }
            }
        } catch (\Throwable $e) {
            $notes[] = 'The crew role categories could not be filled in (' . $e->getMessage() . '); set them under Master Files → Crew Bata.';
        }

        // schema 25: the no-charge words and the priced list's other names are settings; an
        // install that predates them gets the starting lists (Admin edits them under Settings)
        if ($get('service_no_charge_words') === null) {
            $db->query("INSERT IGNORE INTO tf_meta (k, v) VALUES ('service_no_charge_words', 's/s, ss, sightseeing, sight seeing, city tour, market, shopping, photo stop, drive through, free time, leisure, at leisure')");
            $db->query("INSERT IGNORE INTO tf_meta (k, v) VALUES ('service_aliases', 'temple of the tooth => KDY ENT\ntemple of tooth => KDY ENT\ntooth relic => KDY ENT\nkandy temple => KDY ENT')");
            $notes[] = 'Sightseeing with nothing to pay (S/S, market, shopping …) is now recognised as no charge instead of flagged as unpriced; the words, and the priced list\'s other names, are under Admin → Settings.';
        }

        if ($get('mileage_quote_source') === null) {
            $db->query("INSERT INTO tf_meta (k, v) VALUES ('mileage_quote_source', 'google') ON DUPLICATE KEY UPDATE v = v");
            $notes[] = 'Mileage is quoted at Google\'s road distance from now on, with the driven record shown beside it. Set GOOGLE_MAPS_API_KEY in .env; the choice is on Master Files → Mileage Allowances.';
        }
        return $notes;
    }

    /**
     * Add the "Tour Inquiry" menu entry and grant it to the System Administrator,
     * the same way the Hotel Rates install does. Additive: it inserts one row if it
     * is not already there and changes nothing else. A failure here is reported but
     * never stops the install — the tables are what matter, and the entry can always
     * be added by hand under Settings → Menu List.
     */
    private static function rereadOpenInquiries($db): string
    {
        $done = 0;
        $failed = 0;
        try {
            $take = new IntakeService($db, 'installer', new \App\Libraries\HotelRates\HotelRateService($db, 'installer'));
            foreach ($db->query("SELECT id FROM tf_intake WHERE status IN ('new','working','quoted')")->getResultArray() as $r) {
                try {
                    $take->checkHotels((int) $r['id']);
                    $done++;
                } catch (\Throwable $e) {
                    $failed++;
                }
            }
        } catch (\Throwable $e) {
            return 'The open inquiries could not be re-read against the Hotel Rates panel (' . $e->getMessage() . '); press "Check hotels again" on each.';
        }
        return "Room types and published rates read onto {$done} open inquiry(ies)." . ($failed ? " {$failed} could not be read; press \"Check hotels again\" on those." : '');
    }

    /**
     * schema 12: an inquiry with no handling agent gets its creator, matched to a user
     * account by the name the session writes into created_by; a tour copies its inquiry's.
     * Only empty columns are written, so a handler somebody has since set is never moved.
     */
    /**
     * Schema 39: an attraction under ticket control is a place of the Place to Visit / Entrance Fee
     * Master — its old record (fee_id) may be empty from now on, and one place is under control once.
     */
    public static function ticketsByPlace($db): array
    {
        $notes = [];
        try {
            if (!$db->query("SHOW TABLES LIKE 'tf_ticket_attraction'")->getResultArray()) {
                return $notes;
            }
            $col = $db->query("SHOW COLUMNS FROM `tf_ticket_attraction` LIKE 'fee_id'")->getRowArray();
            if ($col && strtoupper((string) ($col['Null'] ?? '')) === 'NO') {
                $db->query('ALTER TABLE `tf_ticket_attraction` MODIFY COLUMN `fee_id` INT NULL');
                $notes[] = 'tf_ticket_attraction.fee_id may now be empty (an attraction is a place of the master).';
            }
            if ($db->query("SHOW COLUMNS FROM `tf_ticket_attraction` LIKE 'place_id'")->getResultArray()
                && !$db->query("SHOW INDEX FROM `tf_ticket_attraction` WHERE Key_name = 'ux_tf_tatt_place'")->getResultArray()) {
                $db->query('ALTER TABLE `tf_ticket_attraction` ADD UNIQUE KEY `ux_tf_tatt_place` (`place_id`)');
                $notes[] = 'Added the one-place-once key to tf_ticket_attraction.';
            }
        } catch (\Throwable $e) {
            $notes[] = 'Ticket Stock could not be keyed by place (' . $e->getMessage() . ').';
        }
        return $notes;
    }

    /** Schema 37: tf_service_job.fee_id / fee_code from the quotation's service line (source 'service'); only empty ones are written. */
    public static function backfillServiceFees($db): array
    {
        try {
            if (!$db->query("SHOW TABLES LIKE 'tf_service_job'")->getResultArray() || !$db->query("SHOW COLUMNS FROM `tf_service_job` LIKE 'fee_id'")->getResultArray()
                || !$db->query("SHOW TABLES LIKE 'tf_intake_service'")->getResultArray()) {
                return [];
            }
            $db->query("UPDATE tf_service_job j JOIN tf_intake_service s ON s.id = j.source_id SET j.fee_id = s.fee_id, j.fee_code = s.fee_code WHERE j.source = 'service' AND j.fee_id IS NULL AND s.fee_id IS NOT NULL");
            $n = (int) $db->affectedRows();
            return $n ? ["Entrance attraction noted on {$n} activity line(s) from their quotation lines (ticket stock)."] : [];
        } catch (\Throwable $e) {
            return ['Activity lines could not be matched to the Entrance Fee master: ' . $e->getMessage()];
        }
    }

    public static function backfillHandlers($db): array
    {
        $notes = [];
        try {
            $col = $db->query("SHOW COLUMNS FROM `tf_intake` LIKE 'handler_name'")->getResultArray();
            if (!$col) {
                return [];
            }
            $rows = $db->query("SELECT id, created_by, created_at FROM tf_intake WHERE handler_name IS NULL AND created_by IS NOT NULL AND created_by <> ''")->getResultArray();
            $done = 0;
            foreach ($rows as $r) {
                $who = Staff::byUsername($db, (string) $r['created_by']);
                $db->query(
                    "UPDATE tf_intake SET handler_id = ?, handler_name = ?, handler_set_by = 'installer', handler_set_at = ? WHERE id = ? AND handler_name IS NULL",
                    [$who['id'] ?? null, $who['name'] ?? (string) $r['created_by'], $r['created_at'], (int) $r['id']]
                );
                $done++;
            }
            if ($db->query("SHOW TABLES LIKE 'tf_tour'")->getResultArray() && $db->query("SHOW COLUMNS FROM `tf_tour` LIKE 'handler_name'")->getResultArray()) {
                $db->query('UPDATE tf_tour t JOIN tf_intake i ON i.id = t.intake_id SET t.handler_id = i.handler_id, t.handler_name = i.handler_name WHERE t.handler_name IS NULL AND i.handler_name IS NOT NULL');
            }
            if ($done) {
                $notes[] = "Handling agent set on {$done} inquiry(ies) from whoever created each; Management reassigns from the ⋯ Manage menu.";
            }
        } catch (\Throwable $e) {
            $notes[] = 'The handling agents could not be filled in from the creators (' . $e->getMessage() . '); set them from the ⋯ Manage menu.';
        }
        return $notes;
    }

    /**
     * schema 13: customers.agent is the one flag that says which customer on the ledger is
     * a booking agent, as opposed to a ticketing customer. The column was already there
     * (int, empty for everyone); it becomes NOT NULL DEFAULT 0 and every empty value is
     * written as 0, so a record nobody has marked reads as "not an agent" and a customer
     * added later starts that way too. A 1 already on file is kept. Marking is done by
     * hand under Master Files → Consignees; nothing here decides who is an agent.
     */
    public static function agentFlag($db): array
    {
        $notes = [];
        try {
            if (!$db->query("SHOW TABLES LIKE 'customers'")->getResultArray()) {
                return [];
            }
            $col = $db->query("SHOW COLUMNS FROM `customers` LIKE 'agent'")->getRowArray();
            if (!$col) {
                $db->query('ALTER TABLE `customers` ADD COLUMN `agent` INT NOT NULL DEFAULT 0');
                $notes[] = 'Added customers.agent (0 for everyone): mark the booking agents under Master Files → Consignees.';
                return $notes;
            }
            $empty = (int) ($db->query('SELECT COUNT(*) AS n FROM `customers` WHERE `agent` IS NULL')->getRowArray()['n'] ?? 0);
            if ($empty > 0) {
                $db->query('UPDATE `customers` SET `agent` = 0 WHERE `agent` IS NULL');
                $notes[] = "customers.agent: {$empty} customer(s) with no value now read 0 (not an agent).";
            }
            if (strtoupper((string) ($col['Null'] ?? '')) === 'YES' || (string) ($col['Default'] ?? '') !== '0') {
                $db->query('ALTER TABLE `customers` MODIFY `agent` INT NOT NULL DEFAULT 0');
                $notes[] = 'customers.agent now defaults to 0 for every new customer.';
            }
        } catch (\Throwable $e) {
            $notes[] = 'customers.agent could not be checked (' . $e->getMessage() . '); the agent marks still work, an unmarked customer reads as not an agent.';
        }
        return $notes;
    }

    /**
     * schema 41 (tf-frequent-hotels-r1): the Hotel Master's "Frequently Used" mark. One flag on
     * the existing hotel record (hr2_hotel.frequently_used), 0 for every hotel until somebody ticks
     * it in Hotel Rates (new). No hotel is copied, no other column and no rate, plan or old Hotel
     * Rate row is touched. A mark already on file is kept; re-running changes nothing.
     */
    public static function frequentHotelFlag($db): array
    {
        $notes = [];
        try {
            if (!$db->query("SHOW TABLES LIKE 'hr2_hotel'")->getResultArray()) {
                return [];
            }
            if (!$db->query("SHOW COLUMNS FROM `hr2_hotel` LIKE 'frequently_used'")->getRowArray()) {
                $db->query('ALTER TABLE `hr2_hotel` ADD COLUMN `frequently_used` TINYINT(1) NOT NULL DEFAULT 0');
                $notes[] = 'Added the Frequently Used mark to the Hotel Master (none marked yet): tick the hotels you use regularly in Hotel Rates (new); the "Frequently Used Hotels" tab shows only those.';
            }
        } catch (\Throwable $e) {
            $notes[] = 'The Frequently Used mark could not be added (' . $e->getMessage() . '); the Hotel Master works as before without it.';
        }
        return $notes;
    }

    /**
     * schema 42 (tf-offers-r1): Published rates, Tactical rates and Early Bird offers kept apart. Inquiries,
     * quotations, confirmations and bookings use the published rates only; tacticals and early birds count only at the
     * hotel PAYMENT, when a person holding "Tactical / Early Bird rate selection" applies one:
     *   hr2_offer_choice   the decision for a tour's stay at a hotel (a special rate applied, or the published rate
     *                      kept), by whom and when (kept for good: a new decision marks the old one replaced)
     *   hr2_offer_log      every change of the rate a hotel payment uses, special rates becoming available, and who
     *   hr2_tour_hotel_cost  gains the published rate beside the rate used, the offer used, the offers that fit
     * The offers already approved move into the new areas as they are (no plan or rate changes). On the upgrade, the
     * old Hotel Rate rows of the hotels with an offer are rebuilt from their published plans only, and the hotel costs
     * of their confirmed tours are recomputed (published rate by default, special rates flagged).
     */
    public static function offerTables($db, string $collation, bool $upgrading): array
    {
        $notes = [];
        try {
            if (!$db->query("SHOW TABLES LIKE 'hr2_contract'")->getResultArray() || !$db->query("SHOW TABLES LIKE 'hr2_tour_hotel_cost'")->getResultArray()) {
                return [];
            }
            $cs = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={$collation}";
            $made = [];
            if (!$db->query("SHOW TABLES LIKE 'hr2_offer_choice'")->getResultArray()) {
                $db->query("CREATE TABLE `hr2_offer_choice` (
                    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `tour_id`     VARCHAR(50)  NOT NULL,
                    `hotel_acc`   VARCHAR(50)  NOT NULL,
                    `hotel_id`    INT UNSIGNED NULL,
                    `choice`      ENUM('published','offer') NOT NULL,
                    `contract_id` INT UNSIGNED NULL,
                    `offer_name`  VARCHAR(255) NULL,
                    `offer_kind`  VARCHAR(20)  NULL,
                    `note`        VARCHAR(300) NULL,
                    `chosen_by`   VARCHAR(100) NOT NULL,
                    `chosen_at`   DATETIME     NOT NULL,
                    `active`      TINYINT(1)   NOT NULL DEFAULT 1,
                    `ended_at`    DATETIME     NULL,
                    `ended_by`    VARCHAR(100) NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr2_offer_choice` (`tour_id`, `hotel_acc`, `active`)
                ) {$cs}");
                $made[] = 'hr2_offer_choice';
            }
            if (!$db->query("SHOW TABLES LIKE 'hr2_offer_log'")->getResultArray()) {
                $db->query("CREATE TABLE `hr2_offer_log` (
                    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `tour_id`         VARCHAR(50)  NOT NULL,
                    `manual_id`       VARCHAR(50)  NULL,
                    `hotel_acc`       VARCHAR(50)  NOT NULL,
                    `hotel_id`        INT UNSIGNED NULL,
                    `event`           VARCHAR(30)  NOT NULL,
                    `contract_id`     INT UNSIGNED NULL,
                    `offer_name`      VARCHAR(255) NULL,
                    `offer_kind`      VARCHAR(20)  NULL,
                    `nights`          SMALLINT     NOT NULL DEFAULT 0,
                    `published_total` DECIMAL(12,2) NULL,
                    `used_total`      DECIMAL(12,2) NULL,
                    `note`            VARCHAR(300) NULL,
                    `by_user`         VARCHAR(100) NOT NULL,
                    `created_at`      DATETIME     NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr2_offer_log` (`tour_id`, `hotel_acc`)
                ) {$cs}");
                $made[] = 'hr2_offer_log';
            }
            $cols = [
                'pub_sgl' => 'DECIMAL(10,2) NULL', 'pub_dbl' => 'DECIMAL(10,2) NULL', 'pub_tpl' => 'DECIMAL(10,2) NULL', 'pub_supplements' => 'DECIMAL(10,2) NULL', 'pub_total' => 'DECIMAL(12,2) NULL',
                'pub_plan_id' => 'INT UNSIGNED NULL', 'pub_plan_name' => 'VARCHAR(160) NULL', 'basis' => 'VARCHAR(20) NULL', 'offer_plan_id' => 'INT UNSIGNED NULL', 'offer_kind' => 'VARCHAR(20) NULL',
                'offer_name' => 'VARCHAR(255) NULL', 'options_json' => 'TEXT NULL', 'choice_needed' => 'TINYINT(1) NOT NULL DEFAULT 0',
            ];
            $added = 0;
            foreach ($cols as $c => $def) {
                if (!$db->query("SHOW COLUMNS FROM `hr2_tour_hotel_cost` LIKE '{$c}'")->getRowArray()) {
                    $db->query("ALTER TABLE `hr2_tour_hotel_cost` ADD COLUMN `{$c}` {$def}");
                    $added++;
                }
            }
            if ($made || $added) {
                $notes[] = 'Tactical rates and Early Bird offers: ' . ($made ? implode(', ', $made) . ' created' : '') . ($made && $added ? '; ' : '') . ($added ? "{$added} column(s) added to hr2_tour_hotel_cost (the published rate beside the rate a payment uses)" : '') . '. Quotations keep using the published rates only.';
            }
            if ($upgrading) {
                // the hotels with an approved tactical / special offer / early bird: (1) the old Hotel Rate rows are built
                // again from the published plans only (inquiry, confirmation and booking use published rates only — until
                // now an offer could price those rows); (2) the payable costs of their confirmed tours follow the new rule:
                // the published rate, the special rates flagged for an authorized person to decide
                $hotels = $db->query('SELECT DISTINCT c.hotel_id FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.status = \'approved\' AND h.status <> \'merged\' AND ' . \App\Libraries\HotelRates\OfferRates::offerSql('c'))->getResultArray();
                $tours = 0; $rows = 0; $projected = 0;
                foreach ($hotels as $h) {
                    try {
                        $svcH = new \App\Libraries\HotelRates\HotelRateService($db, 'installer');
                        $res = (new \App\Libraries\HotelRates\LegacyProjector($db, $svcH->lookupEngine(), 'installer'))->projectHotel((int) $h['hotel_id']);
                        $rows += (int) $res['rows_written'];
                        $projected++;
                    } catch (\Throwable $e) {
                        $notes[] = 'The old Hotel Rate rows of hotel #' . (int) $h['hotel_id'] . ' could not be rebuilt (' . $e->getMessage() . '); press "Refresh old screen" on its Hotel Rates page.';
                    }
                    try {
                        $tours += (new \App\Libraries\HotelRates\HotelCostService($db, 'installer'))->refreshHotel((int) $h['hotel_id']);
                    } catch (\Throwable $e) {
                        $notes[] = 'The hotel costs of hotel #' . (int) $h['hotel_id'] . ' could not be recomputed (' . $e->getMessage() . '); press Recalculate on the Tour Payment Dashboard.';
                    }
                }
                if ($hotels) {
                    $notes[] = 'Old Hotel Rate rows rebuilt from the published rates only for ' . $projected . ' hotel(s) with a tactical, special offer or early bird (' . $rows . ' row(s)).';
                    $notes[] = 'Hotel payments recomputed: published rate by default, special rates flagged for an authorized person to decide — ' . count($hotels) . ' hotel(s), ' . $tours . ' confirmed tour(s) from today on.';
                }
            }
        } catch (\Throwable $e) {
            $notes[] = 'The Tactical / Early Bird tables could not be set up (' . $e->getMessage() . '); hotel payments keep working as before.';
        }
        return $notes;
    }

    /** TourFlow's menu entries: URL, name, and the SQL file that adds one (granted to the administrator). */
    public const MENUS = [
            ['tour/intake',                     'Tour Inquiry',       'tour_flow_menu.sql'],
            ['master_files/transport_rates',    'Transport Rates',    'tour_flow_menu_transport.sql'],
            ['master_files/mileage_allowances', 'Mileage Allowances', 'tour_flow_menu_allowance.sql'],
            ['tour/hotel_bookings',              'Hotel Bookings',      'tour_flow_menu_hotel_bookings.sql'],
            ['tour/transport_ops',               'Transport Operations','tour_flow_menu_transport_ops.sql'],
            ['tour/board',                       'Tour Board',          'tour_flow_menu_board.sql'],
            ['master_files/excursion_rates',     'Excursion Rates',     'tour_flow_menu_excursions.sql'],
            ['tour/pricing',                     'Pricing',             'tour_flow_menu_pricing.sql'],
            ['tour/intake/mileage',              'Mileage override',    'tour_flow_menu_mileage.sql'],
            ['tour/manage',                      'Tour Desk Manage',    'tour_flow_menu_manage.sql'],
            ['master_files/crew_bata',           'Crew Bata',           'tour_flow_menu_crew_bata.sql'],
            ['tour/transport_ops/crew_change',   'Crew replacement',    'tour_flow_menu_crew_change.sql'],
            ['tour/transport_ops/driver_advance', 'Driver advance',     'tour_flow_menu_driver_advance.sql'],
            ['master_files/tour_photos',         'Tour Photos',         'tour_flow_menu_tour_photos.sql'],
            ['master_files/free_visits',         'Free Visits',         'tour_flow_menu_free_visits.sql'],
            ['master_files/paid_services',       'Paid Activities & Services', 'tour_flow_menu_paid_services.sql'],
            ['master_files/crew_people',         'Drivers & Guides',    'tour_flow_menu_crew_people.sql'],
            ['tour/ticket_stock',                'Ticket Stock',        'tour_flow_menu_ticket_stock.sql'],
            ['master_files/entrance_master',     'Place to Visit / Entrance Fee Master', 'tour_flow_menu_entrance_master.sql'],
            ['master_files/meal_master',         'Meal Master',         'tour_flow_menu_meal_master.sql'],
            ['master_files/offer_selection',     'Tactical / Early Bird rate selection', 'tour_flow_menu_offer_selection.sql'],
            ['master_files/crew_people/bank',    'Driver & Guide bank details', 'tour_flow_menu_crew_bank.sql'],
            ['home/usd_rate',                    'Enter the daily USD rate', 'tour_flow_menu_usd_rate_entry.sql'],   // tf-stage2
            ['tour/intake/recalculate',          'Recalculate saved costings', 'tour_flow_menu_recalculate.sql'],   // tf-stage4
            ['master_files/blocks',              'Blocks & Investigations', 'tour_flow_menu_blocks.sql'],   // tf-stage8: System Administrator only
            ['master_files/staff',               'Staff Assignment', 'tour_flow_menu_staff.sql'],   // tf-stage10: System Administrator only
            ['master_files/email_templates',     'Email Templates', 'tour_flow_menu_email_templates.sql'],   // tf-mailtpl-r1: the saved wording of every email
    ];

    /**
     * schema 43 (tf-offers-r3): hr2_payment_hold — a hotel payment prepared by payment staff for a stay with a tactical /
     * early bird that fits and no decision yet waits here for the System Administrator's rate review, before any
     * payment request or accounting entry is made: who prepared it, when, the request as prepared, then who released it
     * (with the rate chosen and the final amount, and the payment request it became) or returned it. Nothing else
     * changes; an empty table changes nothing anywhere.
     */
    public static function paymentHoldTable($db, string $collation): array
    {
        try {
            if ($db->query("SHOW TABLES LIKE 'hr2_payment_hold'")->getResultArray()) {
                return [];
            }
            if (!$db->query("SHOW TABLES LIKE 'hr2_offer_log'")->getResultArray()) {
                return [];   // the offer tables come first (schema 42)
            }
            $db->query("CREATE TABLE `hr2_payment_hold` (
                `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `tour_id`            VARCHAR(50)   NOT NULL,
                `manual_id`          VARCHAR(50)   NULL,
                `hotel_acc`          VARCHAR(50)   NOT NULL,
                `hotel_id`           INT UNSIGNED  NULL,
                `hotel_name`         VARCHAR(255)  NULL,
                `type`               VARCHAR(30)   NOT NULL DEFAULT 'Hotel',
                `status`             VARCHAR(20)   NOT NULL DEFAULT 'awaiting',
                `request_amount`     DECIMAL(12,2) NULL,
                `payload`            TEXT          NOT NULL,
                `prepared_by`        VARCHAR(100)  NOT NULL,
                `prepared_at`        DATETIME      NOT NULL,
                `decided_by`         VARCHAR(100)  NULL,
                `decided_at`         DATETIME      NULL,
                `choice`             VARCHAR(20)   NULL,
                `contract_id`        INT UNSIGNED  NULL,
                `final_amount`       DECIMAL(12,2) NULL,
                `note`               VARCHAR(300)  NULL,
                `payment_request_id` INT UNSIGNED  NULL,
                PRIMARY KEY (`id`),
                KEY `idx_hr2_payment_hold_stay` (`tour_id`, `hotel_acc`),
                KEY `idx_hr2_payment_hold_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={$collation}");
            return ['Hotel payments with an undecided tactical / early bird now wait for the System Administrator\'s rate review before they are requested (table hr2_payment_hold).'];
        } catch (\Throwable $e) {
            return ['The rate-review hold could not be added (' . $e->getMessage() . '); hotel payments are requested as before.'];
        }
    }

    /** The sidebar heading the four transport masters sit under (Shantha, 5 Oct 2026), and what each entry becomes. */
    public const TGS_CATEGORY = 'Transport & Guide Services';
    public const TGS_MENU = [
        // url => [the order within the heading, the menu name it takes when it still carries the old one (null: kept)]
        'master_files/transport_rates'    => [1, null],
        'master_files/mileage_allowances' => [2, ['Mileage Allowances' => 'Mileage & Allowances']],
        'master_files/crew_people'        => [3, null],
        'master_files/crew_bata'          => [4, ['Crew Bata' => 'Crew Roles & Quotation Costing']],
        'master_files/crew_people/bank'   => [5, null],   // the hidden privilege travels with the master it belongs to
    ];

    /**
     * Transport & Guide Services: one heading in the sidebar (a menu category beside Master Files) for Transport Rates,
     * Mileage & Allowances, Drivers & Guides and Crew Roles & Quotation Costing — the four moved under it, in that
     * order, two of them renamed on the menu. Nothing else changes: every entry keeps its id, its URL and its
     * privileges (the sidebar and the privilege screens read the same rows). Transport Operations stays where it is.
     * What each entry was before (category, order, name) is kept in tf_meta so the release kit's ROLLBACK can put it
     * back. Runs on every Install now; nothing to do the second time.
     */
    public static function transportGuideMenu($db): array
    {
        $notes = [];
        try {
            foreach (['menu_category', 'menu_list', 'tf_meta'] as $t) {
                if (!$db->query("SHOW TABLES LIKE '{$t}'")->getResultArray()) {
                    return $notes;
                }
            }
            $cat = $db->query('SELECT id FROM menu_category WHERE category = ? AND del = 0 ORDER BY id LIMIT 1', [self::TGS_CATEGORY])->getRowArray();
            if (!$cat) {
                // a heading the kit's ROLLBACK switched off comes back as it was; otherwise a new one beside Master Files
                $off = $db->query('SELECT id FROM menu_category WHERE category = ? AND del = 1 ORDER BY id DESC LIMIT 1', [self::TGS_CATEGORY])->getRowArray();
                if ($off) {
                    $db->query('UPDATE menu_category SET del = 0 WHERE id = ?', [(int) $off['id']]);
                } else {
                    $mf  = $db->query("SELECT id, order_id FROM menu_category WHERE category = 'Master Files' AND del = 0 ORDER BY id LIMIT 1")->getRowArray();
                    $uid = $db->query('SELECT MIN(id) id FROM user_acc_tbl WHERE group_id = 1')->getRowArray()['id'] ?? 0;
                    $db->query('INSERT INTO menu_category (category, user, del, order_id) VALUES (?, ?, 0, ?)', [self::TGS_CATEGORY, (int) ($uid ?? 0), (int) ($mf['order_id'] ?? 4)]);
                }
                $cat = $db->query('SELECT id FROM menu_category WHERE category = ? AND del = 0 ORDER BY id DESC LIMIT 1', [self::TGS_CATEGORY])->getRowArray();
                $notes[] = 'Menu heading "' . self::TGS_CATEGORY . '" ' . ($off ? 'switched on again' : 'added to the sidebar, beside Master Files') . ' (its place among the headings can be changed under Settings → Menu Category).';
            }
            $catId  = (int) $cat['id'];
            $meta   = $db->query("SELECT v FROM tf_meta WHERE k = 'tgs_menu_before'")->getRowArray();
            $before = $meta ? (json_decode((string) $meta['v'], true) ?: []) : [];
            $moved  = [];
            foreach (self::TGS_MENU as $url => [$order, $rename]) {
                $row = $db->query('SELECT id, cat_id, order_id, name FROM menu_list WHERE url = ? AND del = 0 ORDER BY id LIMIT 1', [$url])->getRowArray();
                if (!$row) {
                    continue;
                }
                $name = (string) $row['name'];
                if ($rename !== null && $name === array_key_first($rename)) {
                    $name = (string) $rename[$name];
                }
                if ((int) $row['cat_id'] === $catId && (int) $row['order_id'] === $order && $name === (string) $row['name']) {
                    continue;
                }
                if (!isset($before[$url])) {
                    $before[$url] = ['id' => (int) $row['id'], 'cat_id' => (int) $row['cat_id'], 'order_id' => (int) $row['order_id'], 'name' => (string) $row['name']];
                }
                $db->query('UPDATE menu_list SET cat_id = ?, order_id = ?, name = ? WHERE id = ?', [$catId, $order, $name, (int) $row['id']]);
                $moved[] = $name;
            }
            if ($moved) {
                $db->query("INSERT INTO tf_meta (k, v) VALUES ('tgs_menu_before', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [json_encode($before, JSON_UNESCAPED_SLASHES)]);
                $notes[] = 'Under "' . self::TGS_CATEGORY . '" now: ' . implode(', ', array_filter($moved, static fn ($n) => $n !== 'Driver & Guide bank details')) . ' (the bank-details privilege travels with Drivers & Guides). Their URLs, ids and privileges are unchanged; everybody logs out and in once to see the heading.';
            }
        } catch (\Throwable $e) {
            $notes[] = 'The "' . self::TGS_CATEGORY . '" heading could not be set up automatically (' . $e->getMessage() . '). Under Settings → Menu Category add the heading, and under Menu List move Transport Rates, Mileage Allowances, Drivers & Guides and Crew Bata to it.';
        }
        return $notes;
    }

    /** tf-stage5: grant master_files/supplier_bank/verify and master_files/supplier_accounts to the Management Approver group. Idempotent. */
    public static function stage5Grants($db): array
    {
        $notes = [];
        try {
            $g = $db->query("SELECT id FROM group_profile WHERE del = 0 AND type = 'Finance & Approvals — Management Approver' LIMIT 1")->getRowArray();
            if (!$g) {
                return ['Group "Finance & Approvals — Management Approver" not found; the verification right was not granted. Grant master_files/supplier_bank/verify under Settings → Group Privileges.'];
            }
            $admin = (int) ($db->query('SELECT MIN(id) id FROM user_acc_tbl WHERE group_id = 1')->getRowArray()['id'] ?? 0);
            foreach (['master_files/supplier_bank/verify' => 'Supplier bank verification', 'master_files/supplier_accounts' => 'Supplier Bank Account Registry'] as $url => $name) {
                $m = $db->query('SELECT id FROM menu_list WHERE url = ? AND del = 0 ORDER BY id LIMIT 1', [$url])->getRowArray();
                if (!$m) {
                    $notes[] = 'Menu entry ' . $url . ' not found; grant it by hand.';
                    continue;
                }
                $has = $db->query('SELECT 1 FROM user_privileges WHERE group_id = ? AND menu_id = ? LIMIT 1', [(int) $g['id'], (int) $m['id']])->getRowArray();
                if (!$has) {
                    $db->query('INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES (?,?,?)', [(int) $g['id'], $admin, (int) $m['id']]);
                    $notes[] = '"' . $name . '" granted to Finance & Approvals — Management Approver (they log out and in once).';
                }
            }
        } catch (\Throwable $e) {
            $notes[] = 'Stage 5 grants: ' . $e->getMessage();
        }
        return $notes;
    }

    /**
     * tf-stage10 (schema 57): the privilege view user_priv_q answers from the Staff Assignment panel for an account that is on it
     * (its Open rows, while Active) and exactly as before for every other account. The view's previous definition is kept in
     * tf_meta (user_priv_q_before_57) for rollback. Nothing in user_privileges, menu_list or user_acc_tbl is touched.
     */
    public static function staffView($db): array
    {
        $notes = [];
        try {
            $cur = $db->query('SHOW CREATE VIEW user_priv_q')->getRowArray();
            $ddl = (string) ($cur['Create View'] ?? '');
            // tf-zeroperm-r1 (7 Nov 2026, Shantha): a group grants menu privileges to the System Administrator group only; every
            // other account holds exactly its Open rows on the Staff Access Control Panel, and nothing when it is not on it.
            $zeroGroup = str_contains($ddl, '`user_acc_tbl`.`group_id` = 1');
            if (str_contains($ddl, 'tf_staff_permission') && $zeroGroup) {
                return [];
            }
            if ($ddl !== '') {
                $key = str_contains($ddl, 'tf_staff_permission') ? 'user_priv_q_before_zeroperm' : 'user_priv_q_before_57';
                $db->query("INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = v", [$key, $ddl]);
            }
            $cols = '`user_acc_tbl`.`emp_id` AS `emp_id`, `employee`.`Name` AS `name`, `user_acc_tbl`.`id` AS `user_id`, `user_acc_tbl`.`user` AS `user`, `user_acc_tbl`.`group_id` AS `group_id`, `group_profile`.`type` AS `type`, `menu_list`.`cat_id` AS `cat_id`, `menu_category`.`category` AS `category`, `menu_category`.`order_id` AS `cat_oid`, `menu_list`.`id` AS `menu_id`, `menu_list`.`name` AS `menu`, `menu_list`.`url` AS `url`, `menu_list`.`order_id` AS `menu_oid`, `employee`.`Acc_No` AS `account_no`, `menu_list`.`show_in_menu_bar` AS `show_in_menu_bar`';
            $db->query('CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `user_priv_q` AS '
                . 'SELECT ' . $cols . ' FROM `user_acc_tbl` JOIN `user_privileges` ON `user_acc_tbl`.`group_id` = `user_privileges`.`group_id` JOIN `employee` ON `employee`.`emp_num` = `user_acc_tbl`.`emp_id` JOIN `menu_list` ON `user_privileges`.`menu_id` = `menu_list`.`id` JOIN `menu_category` ON `menu_category`.`id` = `menu_list`.`cat_id` JOIN `group_profile` ON `group_profile`.`id` = `user_acc_tbl`.`group_id` '
                . "WHERE `user_acc_tbl`.`access` = '1' AND `menu_list`.`del` = '0' AND `user_acc_tbl`.`group_id` = 1 AND NOT EXISTS (SELECT 1 FROM `tf_staff` s WHERE s.`user_id` = `user_acc_tbl`.`id` AND s.`on_panel` = 1) "
                . 'UNION ALL SELECT ' . $cols . ' FROM `user_acc_tbl` JOIN `tf_staff` s ON s.`user_id` = `user_acc_tbl`.`id` AND s.`on_panel` = 1 AND s.`status` = \'active\' JOIN `tf_staff_permission` p ON p.`staff_id` = s.`id` AND p.`state` = \'open\' JOIN `employee` ON `employee`.`emp_num` = `user_acc_tbl`.`emp_id` JOIN `menu_list` ON `menu_list`.`id` = p.`menu_id` JOIN `menu_category` ON `menu_category`.`id` = `menu_list`.`cat_id` JOIN `group_profile` ON `group_profile`.`id` = `user_acc_tbl`.`group_id` '
                . "WHERE `user_acc_tbl`.`access` = '1' AND `menu_list`.`del` = '0'");
            $notes[] = 'The privilege view user_priv_q now gives group privileges to the System Administrator group only; every other account holds exactly its Open rows on the Staff Access Control Panel (tf-zeroperm-r1). The previous definition is kept in tf_meta.';
        } catch (\Throwable $e) {
            $notes[] = 'Staff Assignment view: ' . $e->getMessage();
        }
        return $notes;
    }

    public static function ensureMenu($db, string $sqlDir): ?string
    {
        $notes = [];
        foreach (self::MENUS as [$url, $name, $file]) {
            if ($db->query("SELECT id FROM menu_list WHERE url = ? AND del = 0", [$url])->getResultArray()) {
                continue;
            }
            try {
                RatesInstaller::runSqlFile($db, rtrim($sqlDir, '/\\') . '/' . $file);
            } catch (\Throwable $e) {
                $notes[] = "The \"{$name}\" menu entry could not be added automatically (" . $e->getMessage() . "). Add it under Settings → Menu List: name \"{$name}\", URL {$url}.";
                continue;
            }
            $notes[] = $db->query("SELECT id FROM menu_list WHERE url = ? AND del = 0", [$url])->getResultArray()
                ? "Menu entry \"{$name}\" added and granted to the System Administrator. Grant it to the other groups under Settings → Group Privileges; they need to log out and in once."
                : "The \"{$name}\" menu entry was not added — add it under Settings → Menu List: name \"{$name}\", URL {$url}.";
        }
        return $notes === [] ? null : implode(' ', $notes);
    }

    /**
     * An ENUM that gained a value after a release. MySQL cannot add one in place, so the
     * column is redefined with the fuller list; the rows already in it are unaffected.
     *
     * schema 9: a day the party spends at the hotel with no sightseeing, and the driver's
     * garage run at each end of the tour, are their own kinds of leg — charged at the
     * Mileage Allowances master's figures, not looked up in the driving history.
     */
    public static function widenEnums($db): array
    {
        $notes = [];
        $want  = [
            ['tf_intake_sector', 'kind', 'garage',
             "ALTER TABLE `tf_intake_sector` MODIFY COLUMN `kind` ENUM('transfer','local','arrival','departure','positioning','idle','garage') NOT NULL DEFAULT 'transfer'"],
            // schema 10: an inquiry is quoted, then confirmed by the agent — the confirmation is
            // what pushes the tour to the Hotel and Transport desks. 'converted' is kept in the
            // list so no existing row is refused, but nothing sets it any more.
            ['tf_intake', 'status', 'confirmed',
             "ALTER TABLE `tf_intake` MODIFY COLUMN `status` ENUM('new','working','quoted','confirmed','declined','cancelled','converted') NOT NULL DEFAULT 'new'"],
            // schema 17: a leg quoted at Google's road distance says so
            ['tf_intake_sector', 'km_by', 'google',
             "ALTER TABLE `tf_intake_sector` MODIFY COLUMN `km_by` ENUM('history','town_history','city_allowance','manual','google','none') NOT NULL DEFAULT 'none'"],
            // schema 18: a leg quoted at an actual mileage somebody typed says so, and the
            // actual column can hold a typed figure
            ['tf_intake_sector', 'km_by', 'actual',
             "ALTER TABLE `tf_intake_sector` MODIFY COLUMN `km_by` ENUM('history','town_history','city_allowance','manual','google','actual','none') NOT NULL DEFAULT 'none'"],
            ['tf_intake_sector', 'actual_by', 'manual',
             "ALTER TABLE `tf_intake_sector` MODIFY COLUMN `actual_by` ENUM('history','town_history','manual','none') NOT NULL DEFAULT 'none'"],
            // schema 20: activities and tickets have their own lane on the trail and their own
            // problem reasons
            ['tf_tour_event', 'lane', 'service',
             "ALTER TABLE `tf_tour_event` MODIFY COLUMN `lane` ENUM('tour','quotation','hotel','transport','service') NOT NULL DEFAULT 'tour'"],
            ['tf_reason', 'kind', 'service_problem',
             "ALTER TABLE `tf_reason` MODIFY COLUMN `kind` ENUM('hotel_problem','service_problem') NOT NULL DEFAULT 'hotel_problem'"],
            // schema 24: a driver or guide replaced mid-tour keeps their row, marked so
            ['tf_transport_crew', 'status', 'replaced',
             "ALTER TABLE `tf_transport_crew` MODIFY COLUMN `status` ENUM('to_engage','engaged','declined','replaced') NOT NULL DEFAULT 'to_engage'"],
            // schema 25: a sightseeing stop with nothing to pay is its own state and its own pay mode
            ['tf_intake_service', 'match_state', 'no_charge',
             "ALTER TABLE `tf_intake_service` MODIFY COLUMN `match_state` ENUM('matched','not_in_list','nothing_named','not_checked','no_charge') NOT NULL DEFAULT 'not_checked'"],
            ['tf_intake_service', 'pay_mode', 'no_charge',
             "ALTER TABLE `tf_intake_service` MODIFY COLUMN `pay_mode` ENUM('in_tour','prepaid','direct','own_arrangement','not_arranged','unknown','no_charge') NOT NULL DEFAULT 'unknown'"],
            // schema 35: a leg that starts and ends in the airport area (Katunayake, the planner's
            // gateway town — the airport hotel on the first or last night) with no figure from past
            // tours or Google is quoted at 0 km and says so: the airport and garage running already
            // covers it, so it does not stop the quotation. Katunayake only; every other town as before.
            ['tf_intake_sector', 'km_by', 'same_place',
             "ALTER TABLE `tf_intake_sector` MODIFY COLUMN `km_by` ENUM('history','town_history','city_allowance','manual','google','actual','none','same_place') NOT NULL DEFAULT 'none'"],
            // schema 39: an attraction not in the Entrance Fee Master (no entrance charge), and an item in the master
            // with no rate for the day (held until the master has one) are their own states
            ['tf_intake_service', 'match_state', 'not_in_master',
             "ALTER TABLE `tf_intake_service` MODIFY COLUMN `match_state` ENUM('matched','not_in_list','nothing_named','not_checked','no_charge','not_in_master','rate_missing') NOT NULL DEFAULT 'not_checked'"],
            ['tf_intake_service', 'match_state', 'rate_missing',
             "ALTER TABLE `tf_intake_service` MODIFY COLUMN `match_state` ENUM('matched','not_in_list','nothing_named','not_checked','no_charge','not_in_master','rate_missing') NOT NULL DEFAULT 'not_checked'"],
            // schema 37: an entrance paid at the counter on the day is its own status on the Activities desk
            ['tf_service_job', 'status', 'pay_at_gate',
             "ALTER TABLE `tf_service_job` MODIFY COLUMN `status` ENUM('to_book','requested','confirmed','not_needed','problem','cancelled','pay_at_gate') NOT NULL DEFAULT 'to_book'"],
        ];
        foreach ($want as [$table, $column, $value, $sql]) {
            if (!$db->query("SHOW TABLES LIKE '{$table}'")->getResultArray()) {
                continue;
            }
            $col = $db->query("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column])->getRowArray();
            if (!$col || str_contains((string) ($col['Type'] ?? ''), "'{$value}'")) {
                continue;
            }
            try {
                $db->query($sql);
                $notes[] = "Added '{$value}' to {$table}.{$column}.";
            } catch (\Throwable $e) {
                $notes[] = "Could not widen {$table}.{$column}: " . $e->getMessage();
            }
        }
        return $notes;
    }

    /** The settings column made TEXT, so a list setting is never cut short; rows already there are unaffected. */
    public static function widenSettingsColumn($db): array
    {
        try {
            if (!$db->query("SHOW TABLES LIKE 'tf_meta'")->getResultArray()) {
                return [];
            }
            $col = $db->query("SHOW COLUMNS FROM `tf_meta` LIKE 'v'")->getRowArray();
            if (!$col || stripos((string) ($col['Type'] ?? ''), 'text') !== false) {
                return [];
            }
            $db->query('ALTER TABLE `tf_meta` MODIFY COLUMN `v` TEXT NULL');
            return ['Settings column widened (tf_meta.v is now TEXT).'];
        } catch (\Throwable $e) {
            return ['Could not widen tf_meta.v: ' . $e->getMessage()];
        }
    }

    /** Bring any tf_ table created under a different collation into line. */
    public static function convertCollation($db, string $collation): array
    {
        $charset = explode('_', $collation)[0];
        $rows = $db->query(
            "SELECT TABLE_NAME AS t FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'tf\\_%' AND TABLE_COLLATION <> ?",
            [$collation]
        )->getResultArray();
        $done = [];
        foreach ($rows as $r) {
            try {
                $db->query("ALTER TABLE `{$r['t']}` CONVERT TO CHARACTER SET {$charset} COLLATE {$collation}");
                $done[] = $r['t'];
            } catch (\Throwable $e) {
                // a table that will not convert is reported by the health check rather than blocking the install
            }
        }
        return $done;
    }

    /** Quick health report for the panel banner. */
    public static function health($db): array
    {
        $version = self::installedVersion($db);
        $out = ['installed' => $version !== null && $version >= self::SCHEMA_VERSION, 'version' => $version, 'current' => self::SCHEMA_VERSION, 'problems' => []];
        if (!$out['installed']) {
            return $out;
        }
        foreach (['tf_intake', 'tf_intake_day', 'tf_intake_pax', 'tf_intake_request', 'tf_intake_service', 'tf_crew_role', 'tf_intake_vehicle', 'tf_intake_crew', 'tf_intake_accom', 'tf_intake_sector', 'tf_transport_rate', 'tf_mileage_allowance',
                 'tf_tour', 'tf_hotel_job', 'tf_transport_job', 'tf_transport_leg', 'tf_transport_crew', 'tf_transport_doc', 'tf_doc_type', 'tf_reason', 'tf_tour_event',
                 'tf_intake_group', 'tf_intake_vehicle_day', 'tf_excursion_rate', 'tf_intake_excursion', 'tf_quote', 'tf_activity', 'tf_google_distance', 'tf_crew_voucher', 'tf_driver_advance', 'tf_driver_adjustment', 'tf_driver_settlement', 'tf_photo_tag', 'tf_photo', 'tf_photo_tag_map', 'tf_intake_day_photo', 'tf_free_visit', 'tf_fee_meta', 'tf_pax_rooms', 'tf_edit_request', 'tf_user_authority', 'tf_quote_email', 'tf_email_template', 'tf_crew_person', 'tf_ticket_attraction', 'tf_ticket_batch', 'tf_ticket', 'tf_meal', 'tf_meal_route', 'tf_meal_voucher', 'tf_comm', 'tf_comm_attachment', 'tf_comm_followup', 'tf_mail_in', 'tf_mail_in_attachment', 'tf_child_review', 'tf_place_party', 'tf_safari_slip', 'tf_usd_rate_log', 'tf_recalc_log', 'tf_block', 'tf_block_log', 'tf_staff', 'tf_staff_permission', 'tf_staff_log'] as $t) {
            $n = $db->query("SHOW TABLES LIKE '{$t}'")->getResultArray();
            if (!$n) {
                $out['problems'][] = "Table {$t} is missing — press Re-run install.";
            }
        }
        return $out;
    }

    /** Whether a table has every one of these columns (a step that needs them waits until it does). */
    private static function hasColumns($db, string $table, array $columns): bool
    {
        try {
            if (!$db->query("SHOW TABLES LIKE '{$table}'")->getResultArray()) {
                return false;
            }
            foreach ($columns as $c) {
                if (!$db->query("SHOW COLUMNS FROM `{$table}` LIKE ?", [$c])->getResultArray()) {
                    return false;
                }
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
