<?php

namespace App\Libraries\HotelRates;

/**
 * Creates / upgrades the hr2_* tables. Shared by the spark command, the
 * migration class and the test harness.
 */
final class Installer
{
    public const SCHEMA_VERSION = 9;   // 9 (27 Oct 2026, hotel-dup-r1): audit actions rename / override / archive / restore; 8 (6 Oct 2026): the hotel code HTL-001 …

    /** The collation written in hotel_rates_v2_schema.sql; replaced by the database's own at install time. */
    public const SQL_COLLATION = 'utf8mb4_unicode_ci';

    /** String foreign keys that must be dropped while tables are converted to another collation. */
    private const STRING_FKS = [
        ['hr2_market_country', 'fk_hr2_market_country_market', 'FOREIGN KEY (`market_code`) REFERENCES `hr2_market` (`code`)'],
    ];

    /** Layouts older than this are dropped and rebuilt; newer ones are upgraded in place by re-running the SQL file. */
    public const OLDEST_UPGRADABLE = 3;

    /** null = not installed, 1 = the first layout (hr2_rate_period), 3 = the previous layout, 4–7 = upgraded in place (7 = current). */
    public static function installedVersion($db): ?int
    {
        $tables = array_map(static fn ($r) => array_values($r)[0], $db->query("SHOW TABLES LIKE 'hr2_%'")->getResultArray());
        if (!$tables) {
            return null;
        }
        if (in_array('hr2_meta', $tables, true)) {
            $r = $db->query("SELECT v FROM hr2_meta WHERE k = 'schema_version'")->getRowArray();
            return (int) ($r['v'] ?? self::SCHEMA_VERSION);
        }
        return in_array('hr2_rate_period', $tables, true) ? 1 : 2;
    }

    /**
     * The utf8mb4 collation the rest of the database uses (tour_quotations, hotel_rate,
     * consignee …). The hr2_ tables are created in the same one, otherwise MySQL 8
     * refuses joins such as hr2_hotel_account.sup_acc_no = tour_quotations.hotel_acc
     * ("Illegal mix of collations").
     */
    public static function targetCollation($db): string
    {
        foreach ([
            "SELECT COLLATION_NAME AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_quotations' AND COLUMN_NAME = 'hotel_acc'",
            "SELECT COLLATION_NAME AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hotel_rate' AND COLUMN_NAME = 'hotel'",
            'SELECT @@collation_database AS c',
        ] as $sql) {
            try {
                $r = $db->query($sql);
                $row = ($r === false || $r === true) ? null : $r->getRowArray();
                $c = (string) ($row['c'] ?? '');
                if (str_starts_with($c, 'utf8mb4_')) {
                    return $c;
                }
            } catch (\Throwable $e) {
                // try the next one
            }
        }
        return self::SQL_COLLATION;
    }

    /** hr2_ tables whose collation differs from $collation → [name => current collation]. */
    public static function tablesWithOtherCollation($db, string $collation): array
    {
        $out = [];
        $r = $db->query("SELECT TABLE_NAME AS t, TABLE_COLLATION AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'hr2\\_%'");
        foreach (($r === false || $r === true) ? [] : $r->getResultArray() as $row) {
            if ((string) $row['c'] !== $collation) {
                $out[$row['t']] = (string) $row['c'];
            }
        }
        return $out;
    }

    /** Convert every hr2_ table to $collation (used when upgrading tables created in the SQL file's default). */
    public static function convertCollation($db, string $collation): array
    {
        $tables = self::tablesWithOtherCollation($db, $collation);
        if (!$tables) {
            return [];
        }
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach (self::STRING_FKS as [$table, $fk]) {
                try {
                    $db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk}`");
                } catch (\Throwable $e) {
                    // not there (yet)
                }
            }
            foreach (array_keys($tables) as $t) {
                $db->query("ALTER TABLE `{$t}` CONVERT TO CHARACTER SET utf8mb4 COLLATE {$collation}");
            }
            foreach (self::STRING_FKS as [$table, $fk, $def]) {
                try {
                    $db->query("ALTER TABLE `{$table}` ADD CONSTRAINT `{$fk}` {$def}");
                } catch (\Throwable $e) {
                    // already present
                }
            }
        } finally {
            $db->query('SET FOREIGN_KEY_CHECKS = 1');
        }
        return $tables;
    }

    /**
     * @param string $sqlDir  directory holding hotel_rates_v2_schema.sql
     * @param bool   $force   drop an old layout even if it holds hand-entered contracts
     * @return array{version_before:int|null, dropped:bool, statements:int, tables:int, notes:string[], countries:int}
     */
    public static function install($db, string $sqlDir, bool $force = false): array
    {
        $notes = [];
        $before = self::installedVersion($db);
        $dropped = false;
        if ($before !== null && $before < self::OLDEST_UPGRADABLE) {
            $manual = 0;
            try {
                $manual = (int) ($db->query("SELECT COUNT(*) AS n FROM hr2_contract WHERE source_type <> 'migration'")->getRowArray()['n'] ?? 0);
            } catch (\Throwable $e) {
                $manual = 0;
            }
            if ($manual > 0 && !$force) {
                throw new \RuntimeException("An older Hotel Rates layout (schema {$before}) holds {$manual} hand-entered contract(s). Export them from the old panel first, then run again with --force to rebuild the tables.");
            }
            $tables = array_map(static fn ($r) => array_values($r)[0], $db->query("SHOW TABLES LIKE 'hr2_%'")->getResultArray());
            $db->query('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tables as $t) {
                $db->query("DROP TABLE IF EXISTS `{$t}`");
            }
            $db->query('SET FOREIGN_KEY_CHECKS = 1');
            $dropped = true;
            $notes[] = "Dropped schema {$before} tables (" . count($tables) . '). Run hotelrates:migrate again to re-copy the legacy hotel_rate rows.';
        }
        $collation = self::targetCollation($db);
        $n = self::runSqlFile($db, rtrim($sqlDir, '/\\') . '/hotel_rates_v2_schema.sql', $collation === self::SQL_COLLATION ? [] : ['COLLATE=' . self::SQL_COLLATION => 'COLLATE=' . $collation]);
        $converted = self::convertCollation($db, $collation);
        if ($converted) {
            $notes[] = 'Converted ' . count($converted) . " table(s) to the database collation {$collation} so joins with the existing tables work.";
        }
        foreach (self::upgradeColumns($db) as $u) {
            $notes[] = $u;
        }
        $db->query("INSERT INTO hr2_meta (k, v) VALUES ('schema_version', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [(string) self::SCHEMA_VERSION]);
        if ($before !== null && $before >= self::OLDEST_UPGRADABLE && $before < self::SCHEMA_VERSION) {
            $notes[] = "Upgraded schema {$before} to " . self::SCHEMA_VERSION . ' in place (existing plans untouched).';
        }
        // the first schema-4 drop seeded legacy_screen = 'migrated'; the lock now defaults to 'all' unless someone set it from the panel
        try {
            $touched = (int) ($db->query("SELECT COUNT(*) AS n FROM hr2_audit WHERE entity = 'settings' AND new_json LIKE '%legacy_screen%'")->getRowArray()['n'] ?? 0);
            if ($touched === 0) {
                $db->query("UPDATE hr2_meta SET v = 'all' WHERE k = 'legacy_screen' AND v = 'migrated'");
            }
        } catch (\Throwable $e) {
            // nothing to fix
        }
        $countries = (new Settings($db))->seedMarketCountries();
        if ($countries) {
            $notes[] = "Country → market mapping: {$countries} countries seeded from the country table (edit them under Settings in the panel).";
        }
        $tables = $db->query("SHOW TABLES LIKE 'hr2_%'")->getResultArray();
        return ['version_before' => $before, 'dropped' => $dropped, 'statements' => $n, 'tables' => count($tables), 'notes' => $notes, 'countries' => $countries];
    }

    /**
     * Column-level upgrades CREATE TABLE IF NOT EXISTS cannot make on existing tables.
     * Each step checks first, so re-running is harmless.
     */
    public static function upgradeColumns($db): array
    {
        $notes = [];
        // schema 5: the audit log records "unmerge" (undo of a duplicate merge)
        $col = $db->query("SHOW COLUMNS FROM hr2_audit LIKE 'action'")->getRowArray();
        if ($col && stripos((string) $col['Type'], "'unmerge'") === false) {
            $db->query("ALTER TABLE hr2_audit MODIFY COLUMN `action` ENUM('insert','update','delete','merge','unmerge','approve','supersede','project','migrate') NOT NULL");
            $notes[] = "hr2_audit.action now accepts 'unmerge'.";
        }
        // schema 9 (hotel-dup-r1): the audit log names a hotel rename, a duplicate-warning override on registration,
        // and Management's archive / restore of a hotel — never a blank action
        $col = $db->query("SHOW COLUMNS FROM hr2_audit LIKE 'action'")->getRowArray();
        if ($col && stripos((string) $col['Type'], "'rename'") === false) {
            $db->query("ALTER TABLE hr2_audit MODIFY COLUMN `action` ENUM('insert','update','delete','merge','unmerge','approve','supersede','project','migrate','rename','override','archive','restore') NOT NULL");
            $notes[] = "hr2_audit.action now accepts 'rename', 'override', 'archive' and 'restore'.";
        }
        // schema 6: a plan can be for payments only (ad hoc rate) — the quotation never sees it
        $col = $db->query("SHOW COLUMNS FROM hr2_contract LIKE 'applies_to'")->getRowArray();
        if (!$col) {
            $db->query("ALTER TABLE hr2_contract ADD COLUMN `applies_to` ENUM('both','quotation','payment') NOT NULL DEFAULT 'both' AFTER `priority`");
            $notes[] = 'hr2_contract.applies_to added (both / quotation / payment).';
        }
        // schema 8: the hotel code HTL-001 … on the hotel itself (the register hr2_hotel_code is created by the schema file)
        $col = $db->query("SHOW COLUMNS FROM hr2_hotel LIKE 'hotel_code'")->getRowArray();
        if (!$col) {
            $db->query("ALTER TABLE hr2_hotel ADD COLUMN `hotel_code` VARCHAR(12) NULL AFTER `name`, ADD UNIQUE KEY `uq_hr2_hotel_code` (`hotel_code`)");
            $notes[] = 'hr2_hotel.hotel_code added (unique).';
        }
        $codes = new HotelCodes($db, 'installer');
        if ($codes->ready()) {
            $b = $codes->backfill();
            if ($b['given'] > 0) {
                $notes[] = "Hotel codes: {$b['given']} hotel(s) given their code — {$b['legacy']} keeping the number of their legacy H-code (HTL-001 is the hotel that was H001), {$b['fresh']} given the next free numbers; the sequence stands at " . HotelCodes::format($b['sequence']) . '. Legacy codes in supplier.hotel_code were not touched.';
            }
            foreach ($b['conflicts'] as $c) {
                $notes[] = 'Hotel code conflict: ' . $c;
            }
        }
        return $notes;
    }

    /** Execute every statement of a .sql file (statements end with ; at end of line). $replace = literal substitutions applied first. */
    public static function runSqlFile($db, string $path, array $replace = []): int
    {
        if (!is_file($path)) {
            throw new \RuntimeException("SQL file not found: {$path}");
        }
        $sql = file_get_contents($path);
        if ($replace) {
            $sql = strtr($sql, $replace);
        }
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
        $n = 0;
        foreach ($statements as $stmt) {
            $clean = trim(preg_replace('/^\s*--.*$/m', '', $stmt));
            if ($clean === '') {
                continue;
            }
            $db->query($clean);
            $n++;
        }
        return $n;
    }
}
