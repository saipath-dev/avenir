<?php

namespace App\Libraries\BankRec;

/**
 * tf-bankrec-r1/r2 (Step 2): the tables and privileges of the Daily Bank Reconciliation workflow.
 *
 * Everything lives in new tables prefixed brc_. No existing Avenir table is altered and no existing row is rewritten:
 * the ledger (account_runing), its reconciliation flags (bank_rec, bankst_flag, BankFlg), vouchers and the payment chain
 * are only READ. Statement lines, matches, marks, transfers, rates, proposed corrections and daily records are kept
 * here, and every change is written to the existing audit trail (tpd_audit).
 *
 * Version 2 (tf-bankrec-r2, 12 Oct 2026) adds columns and two tables to the brc_ tables only:
 *   brc_claim       one row per statement line / ledger row that is matched or classified (primary key = the item), so
 *                   two users can never match or classify the same item at the same moment (the database refuses it)
 *   brc_mark_event  the history of a timing classification cleared by a match and restored by an undo (D9)
 *   + brc_line.version, brc_fx_rate.version (conflicting edits are refused), brc_match.kind / remainder_lkr /
 *     expected_date / supersedes_id / superseded_by / diff_note (partial clearing, explained differences),
 *     brc_mark.cleared_by_match, brc_opening.source / book_key / item_date / active_key (opening outstanding items, D10),
 *     brc_correction posting fields (posted entry verified against the ledger), brc_transfer.active_from / active_to,
 *     brc_day.inputs_json / inputs_hash / fx_note / pending_json (the reviewed snapshot and change detection).
 *
 * ensure() is idempotent: CREATE TABLE IF NOT EXISTS, columns and indexes added only when missing, privilege rows only
 * when missing. A rollback of the release removes nothing from the database, so no history is lost.
 */
final class Schema
{
    public const VERSION = 2;
    public const META_KEY = 'brc_schema_version';

    /** url => [name, shown in the menu bar (1) or a hidden privilege (0)] — category: Accounts */
    public const MENUS = [
        'accounts/daily_bank_rec'         => ['Daily Bank Reconciliation', 1],
        'accounts/daily_bank_rec/prepare' => ['Daily Bank Reconciliation — prepare (import, match, propose)', 0],
        'accounts/daily_bank_rec/review'  => ['Daily Bank Reconciliation — review (confirm mappings, approve, sign off)', 0],
    ];

    /** Columns added in version 2: table => [column => definition]. */
    public const V2_COLUMNS = [
        'brc_line' => ['version' => "INT NOT NULL DEFAULT 0 COMMENT 'raised by every change; a change based on an older version is refused'"],
        'brc_fx_rate' => ['version' => "INT NOT NULL DEFAULT 0"],
        'brc_match' => [
            'kind' => "VARCHAR(10) NOT NULL DEFAULT 'full' COMMENT 'full | partial (the rest clears later)'",
            'remainder_lkr' => "DECIMAL(19,2) NULL COMMENT 'partial: the part still outstanding (bank − book, LKR)'",
            'expected_date' => "DATE NULL COMMENT 'partial: when the rest is expected'",
            'supersedes_id' => "INT UNSIGNED NULL COMMENT 'the partial match this one completes or extends'",
            'superseded_by' => "INT UNSIGNED NULL",
            'diff_note' => "VARCHAR(300) NULL COMMENT 'the explanation of a difference (required for exchange-rate and other differences)'",
            'diff_noted_by' => "VARCHAR(100) NULL",
            'diff_noted_at' => "DATETIME NULL",
        ],
        'brc_mark' => ['cleared_by_match' => "INT UNSIGNED NULL COMMENT 'a timing item cleared by this match (status matched); an undo restores it'"],
        'brc_opening' => [
            'source' => "VARCHAR(10) NOT NULL DEFAULT 'manual' COMMENT 'manual | ledger (a ledger row booked before the first day)'",
            'book_key' => "VARCHAR(40) NULL",
            'item_date' => "DATE NULL",
            'book_fp' => "CHAR(16) NULL COMMENT 'fingerprint of the ledger row when it was proposed'",
            'active_key' => "VARCHAR(80) NULL COMMENT 'set while proposed or accepted: one opening item per ledger row'",
        ],
        'brc_correction' => [
            'posting_state' => "VARCHAR(12) NULL COMMENT 'verified: the posted ledger entry was checked against the approved lines'",
            'posted_rows_json' => "TEXT NULL",
            'posted_fp' => "CHAR(40) NULL",
            'posted_entry_date' => "DATE NULL",
        ],
        'brc_transfer' => [
            'active_from' => "INT UNSIGNED NULL COMMENT 'from_line_id while active (unique)'",
            'active_to' => "INT UNSIGNED NULL COMMENT 'to_line_id while active (unique)'",
        ],
        'brc_day' => [
            'inputs_json' => "TEXT NULL COMMENT 'fingerprints of everything the record was computed from'",
            'inputs_hash' => "CHAR(40) NULL",
            'fx_note' => "VARCHAR(300) NULL COMMENT 'the preparer''s explanation of the exchange-rate revaluation'",
            'pending_json' => "TEXT NULL COMMENT 'corrections not yet posted when the record was prepared'",
        ],
    ];

    /** Indexes added in version 2: table => [index name => definition]. */
    public const V2_INDEXES = [
        'brc_opening' => ['uq_brc_open_active' => 'UNIQUE KEY `uq_brc_open_active` (`active_key`)'],
        'brc_transfer' => ['uq_brc_tr_from' => 'UNIQUE KEY `uq_brc_tr_from` (`active_from`)', 'uq_brc_tr_to' => 'UNIQUE KEY `uq_brc_tr_to` (`active_to`)'],
        'brc_mark' => ['idx_brc_mark_match' => 'KEY `idx_brc_mark_match` (`cleared_by_match`)'],
        'brc_correction' => ['idx_brc_corr_posted' => 'KEY `idx_brc_corr_posted` (`posted_ref`)'],
    ];

    public static function version($db): int
    {
        try {
            $r = $db->query("SELECT v FROM tpd_meta WHERE k = ?", [self::META_KEY])->getRowArray();
            return (int) ($r['v'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function installed($db): bool
    {
        return self::version($db) >= self::VERSION;
    }

    public static function status($db): array
    {
        $tables = [];
        foreach (array_keys(self::tables('utf8mb4_unicode_ci')) as $t) {
            $tables[$t] = (bool) $db->query('SHOW TABLES LIKE ' . $db->escape($t))->getResultArray();
        }
        $menus = [];
        foreach (self::MENUS as $url => $m) {
            $menus[$url] = (bool) $db->query('SELECT id FROM menu_list WHERE url = ? AND del = 0', [$url])->getResultArray();
        }
        $tpd = (bool) $db->query("SHOW TABLES LIKE 'tpd_meta'")->getResultArray() && (bool) $db->query("SHOW TABLES LIKE 'tpd_audit'")->getResultArray();
        return ['version' => self::version($db), 'target' => self::VERSION, 'tables' => $tables, 'menus' => $menus, 'tpd_ready' => $tpd,
            'ready' => $tpd && self::installed($db) && !in_array(false, $tables, true) && !in_array(false, $menus, true)];
    }

    /** Creates what is missing and upgrades version 1 to 2; returns notes. Needs the payment workflow's tpd_meta / tpd_audit (R5). */
    public static function ensure($db): array
    {
        $notes = [];
        if (!$db->query("SHOW TABLES LIKE 'tpd_meta'")->getResultArray() || !$db->query("SHOW TABLES LIKE 'tpd_audit'")->getResultArray()) {
            return ['The payment workflow (tpd_meta, tpd_audit) is not set up on this database; set it up first.'];
        }
        $from = self::version($db);
        $coll = self::collation($db);
        foreach (self::tables($coll) as $name => $sql) {
            $existed = (bool) $db->query('SHOW TABLES LIKE ' . $db->escape($name))->getResultArray();
            $db->query($sql);
            if (!$existed) {
                $notes[] = "Table {$name} created.";
            }
        }
        // version 2: columns and indexes on tables that existed in version 1
        foreach (self::V2_COLUMNS as $t => $cols) {
            $have = array_column($db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$t])->getResultArray(), 'COLUMN_NAME');
            foreach ($cols as $c => $def) {
                if (!in_array($c, $have, true)) {
                    $db->query("ALTER TABLE `{$t}` ADD COLUMN `{$c}` {$def}");
                    $notes[] = "Column {$t}.{$c} added.";
                }
            }
        }
        foreach (self::V2_INDEXES as $t => $idx) {
            $have = array_column($db->query("SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$t])->getResultArray(), 'INDEX_NAME');
            foreach ($idx as $n => $def) {
                if (!in_array($n, $have, true)) {
                    if ($t === 'brc_transfer') {
                        $db->query("UPDATE brc_transfer SET active_from = from_line_id, active_to = to_line_id WHERE status = 'active' AND active_from IS NULL");
                    }
                    $db->query("ALTER TABLE `{$t}` ADD {$def}");
                    $notes[] = "Index {$t}.{$n} added.";
                }
            }
        }
        if ($from < 2) {
            $notes = array_merge($notes, self::backfillV2($db));
        }
        $notes = array_merge($notes, self::menus($db));
        $db->query('INSERT INTO tpd_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [self::META_KEY, (string) self::VERSION]);
        return $notes ?: ['Already in place; nothing to do.'];
    }

    /**
     * Version 1 → 2 data: a claim for every item that is matched or classified now, the active transfer keys, and the
     * explanation of existing differences taken from the match note where one was given. Nothing else changes; an
     * exchange-rate difference that was recorded without a note stays visible as "to be explained".
     */
    public static function backfillV2($db): array
    {
        $notes = [];
        $db->query("UPDATE brc_transfer SET active_from = from_line_id, active_to = to_line_id WHERE status = 'active' AND active_from IS NULL");
        $db->query("UPDATE brc_transfer SET active_from = NULL, active_to = NULL WHERE status <> 'active'");
        $db->query("INSERT IGNORE INTO brc_claim (item_key, bank_account_id, kind, ref_id, claimed_by, claimed_at)
            SELECT CONCAT('L', i.line_id), m.bank_account_id, 'match', m.id, m.created_by, m.created_at FROM brc_match_item i JOIN brc_match m ON m.id = i.match_id AND m.status = 'active' WHERE i.side = 'bank'");
        $c1 = $db->affectedRows();
        $db->query("INSERT IGNORE INTO brc_claim (item_key, bank_account_id, kind, ref_id, claimed_by, claimed_at)
            SELECT CONCAT('B', m.bank_account_id, ':', i.book_key), m.bank_account_id, 'match', m.id, m.created_by, m.created_at FROM brc_match_item i JOIN brc_match m ON m.id = i.match_id AND m.status = 'active' WHERE i.side = 'book'");
        $c2 = $db->affectedRows();
        $db->query("INSERT IGNORE INTO brc_claim (item_key, bank_account_id, kind, ref_id, claimed_by, claimed_at)
            SELECT IF(k.side = 'bank', CONCAT('L', k.line_id), CONCAT('B', k.bank_account_id, ':', k.book_key)), k.bank_account_id, 'mark', k.id, k.created_by, k.created_at FROM brc_mark k WHERE k.status = 'active'");
        $c3 = $db->affectedRows();
        $want = (int) ($db->query("SELECT (SELECT COUNT(*) FROM brc_match_item i JOIN brc_match m ON m.id = i.match_id AND m.status = 'active') + (SELECT COUNT(*) FROM brc_mark WHERE status = 'active') AS n")->getRowArray()['n'] ?? 0);
        $notes[] = 'Claims recorded for ' . ($c1 + $c2 + $c3) . ' matched or classified item(s)' . (($c1 + $c2 + $c3) !== $want ? ' — ' . ($want - $c1 - $c2 - $c3) . ' item(s) were matched or classified twice before this version: see brc_claim against brc_match_item / brc_mark.' : '.');
        $db->query("UPDATE brc_match SET diff_note = note, diff_noted_by = created_by, diff_noted_at = created_at WHERE diff_note IS NULL AND difference_lkr <> 0 AND diff_reason IN ('exchange rate', 'other') AND note IS NOT NULL AND CHAR_LENGTH(TRIM(note)) >= 5");
        $open = (int) ($db->query("SELECT COUNT(*) AS n FROM brc_match WHERE status = 'active' AND difference_lkr <> 0 AND diff_reason = 'exchange rate' AND diff_note IS NULL")->getRowArray()['n'] ?? 0);
        if ($open) {
            $notes[] = "{$open} exchange-rate difference(s) recorded without an explanation are now shown as \"to be explained\".";
        }
        return $notes;
    }

    private static function collation($db): string
    {
        try {
            $c = (string) ($db->query('SELECT @@collation_database AS c')->getRowArray()['c'] ?? '');
            return str_starts_with($c, 'utf8mb4_') ? $c : 'utf8mb4_unicode_ci';
        } catch (\Throwable $e) {
            return 'utf8mb4_unicode_ci';
        }
    }

    /** @return array<string,string> the version-1 tables (as created by r1) and the version-2 tables */
    private static function tables(string $coll): array
    {
        $end = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={$coll}";
        $who = "`created_by` VARCHAR(100) NOT NULL, `created_at` DATETIME NOT NULL";
        return [
            // the ledger mapping of each Avenir bank account (tpd_bank_account), proposed by one user and confirmed by another
            'brc_account_map' => "CREATE TABLE IF NOT EXISTS `brc_account_map` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `bank_account_id` INT UNSIGNED NOT NULL,
                `ledger_code` VARCHAR(50) NOT NULL,
                `currency` VARCHAR(3) NOT NULL,
                `rec_start` DATE NOT NULL COMMENT 'first day this account is reconciled',
                `status` VARCHAR(12) NOT NULL DEFAULT 'proposed' COMMENT 'proposed | confirmed | rejected | superseded',
                `note` VARCHAR(300) NULL,
                {$who},
                `decided_by` VARCHAR(100) NULL, `decided_at` DATETIME NULL, `decision_note` VARCHAR(300) NULL,
                PRIMARY KEY (`id`), KEY `idx_brc_map_acct` (`bank_account_id`, `status`){$end}",

            // a bank statement (upload or manual entry): draft → accepted (locked) or void; never posted to the ledger
            'brc_statement' => "CREATE TABLE IF NOT EXISTS `brc_statement` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `bank_account_id` INT UNSIGNED NOT NULL,
                `currency` VARCHAR(3) NOT NULL,
                `period_from` DATE NOT NULL, `period_to` DATE NOT NULL,
                `opening_balance` DECIMAL(19,2) NOT NULL COMMENT 'funds: + in credit, − overdrawn',
                `closing_balance` DECIMAL(19,2) NOT NULL,
                `total_in` DECIMAL(19,2) NOT NULL DEFAULT 0, `total_out` DECIMAL(19,2) NOT NULL DEFAULT 0,
                `line_count` INT NOT NULL DEFAULT 0,
                `source` VARCHAR(10) NOT NULL COMMENT 'upload | manual',
                `file_name` VARCHAR(200) NULL, `file_sha256` CHAR(64) NULL,
                `checks_json` MEDIUMTEXT NULL,
                `status` VARCHAR(10) NOT NULL DEFAULT 'draft' COMMENT 'draft | accepted | void',
                `note` VARCHAR(300) NULL,
                {$who},
                `accepted_by` VARCHAR(100) NULL, `accepted_at` DATETIME NULL,
                `void_by` VARCHAR(100) NULL, `void_at` DATETIME NULL, `void_reason` VARCHAR(300) NULL,
                PRIMARY KEY (`id`), KEY `idx_brc_stmt_acct` (`bank_account_id`, `status`, `period_from`){$end}",

            // one statement line, in the account's own currency, with its LKR equivalent; orig_json keeps the line as imported
            'brc_line' => "CREATE TABLE IF NOT EXISTS `brc_line` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `statement_id` INT UNSIGNED NOT NULL,
                `bank_account_id` INT UNSIGNED NOT NULL,
                `line_no` INT NOT NULL,
                `txn_date` DATE NOT NULL, `value_date` DATE NULL,
                `description` VARCHAR(400) NOT NULL DEFAULT '',
                `reference` VARCHAR(120) NULL,
                `money_in` DECIMAL(19,2) NOT NULL DEFAULT 0, `money_out` DECIMAL(19,2) NOT NULL DEFAULT 0,
                `balance` DECIMAL(19,2) NULL,
                `currency` VARCHAR(3) NOT NULL,
                `fx_rate` DECIMAL(18,6) NULL COMMENT 'LKR per unit on txn_date (1 for LKR)',
                `fx_source` VARCHAR(40) NULL,
                `lkr_amount` DECIMAL(19,2) NULL COMMENT '(money_in − money_out) × fx_rate',
                `category` VARCHAR(20) NOT NULL DEFAULT 'other' COMMENT 'receipt | payment | charge | transfer | interest | other',
                `charge_type` VARCHAR(40) NULL,
                `state` VARCHAR(12) NOT NULL DEFAULT 'open' COMMENT 'open | matched | timing | duplicate | excluded',
                `orig_json` TEXT NOT NULL,
                `edited` TINYINT(1) NOT NULL DEFAULT 0,
                `fingerprint` CHAR(40) NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`), KEY `idx_brc_line_acct` (`bank_account_id`, `txn_date`), KEY `idx_brc_line_stmt` (`statement_id`), KEY `idx_brc_line_fp` (`fingerprint`){$end}",

            // daily exchange rates used by the reconciliation (LKR per unit); one rate per currency and day
            'brc_fx_rate' => "CREATE TABLE IF NOT EXISTS `brc_fx_rate` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `currency` VARCHAR(3) NOT NULL,
                `rate_date` DATE NOT NULL,
                `rate` DECIMAL(18,6) NOT NULL,
                `source` VARCHAR(40) NOT NULL,
                `note` VARCHAR(200) NULL,
                {$who},
                `updated_by` VARCHAR(100) NULL, `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`), UNIQUE KEY `uq_brc_rate` (`currency`, `rate_date`){$end}",

            // a match: statement line(s) ⇄ ledger row(s); undone and superseded matches are kept
            'brc_match' => "CREATE TABLE IF NOT EXISTS `brc_match` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `bank_account_id` INT UNSIGNED NOT NULL,
                `method` VARCHAR(12) NOT NULL COMMENT 'suggested | manual',
                `bank_total` DECIMAL(19,2) NOT NULL COMMENT 'account currency',
                `bank_total_lkr` DECIMAL(19,2) NULL,
                `book_total` DECIMAL(19,2) NOT NULL COMMENT 'LKR (the ledger)',
                `difference_lkr` DECIMAL(19,2) NOT NULL DEFAULT 0,
                `diff_reason` VARCHAR(40) NULL COMMENT 'exchange rate | rounding | other | partial',
                `status` VARCHAR(10) NOT NULL DEFAULT 'active' COMMENT 'active | undone | superseded',
                `note` VARCHAR(300) NULL,
                {$who},
                `undone_by` VARCHAR(100) NULL, `undone_at` DATETIME NULL, `undo_reason` VARCHAR(300) NULL,
                PRIMARY KEY (`id`), KEY `idx_brc_match_acct` (`bank_account_id`, `status`){$end}",

            'brc_match_item' => "CREATE TABLE IF NOT EXISTS `brc_match_item` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `match_id` INT UNSIGNED NOT NULL,
                `side` VARCHAR(4) NOT NULL COMMENT 'bank | book',
                `line_id` INT UNSIGNED NULL,
                `book_key` VARCHAR(40) NULL COMMENT 'ledger row: ID.n',
                `amount` DECIMAL(19,2) NOT NULL COMMENT 'signed: + adds to funds',
                `item_date` DATE NOT NULL,
                `snapshot` TEXT NULL,
                PRIMARY KEY (`id`), KEY `idx_brc_mi_match` (`match_id`), KEY `idx_brc_mi_line` (`line_id`), KEY `idx_brc_mi_book` (`book_key`){$end}",

            // a classification of an unmatched item: timing (expected to clear), duplicate-looking, excluded (with reason)
            'brc_mark' => "CREATE TABLE IF NOT EXISTS `brc_mark` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `bank_account_id` INT UNSIGNED NOT NULL,
                `side` VARCHAR(4) NOT NULL,
                `line_id` INT UNSIGNED NULL,
                `book_key` VARCHAR(40) NULL,
                `mark` VARCHAR(12) NOT NULL COMMENT 'timing | duplicate | excluded',
                `related` VARCHAR(60) NULL COMMENT 'duplicate-looking: the item it resembles',
                `expected_date` DATE NULL,
                `amount` DECIMAL(19,2) NOT NULL,
                `item_date` DATE NOT NULL,
                `note` VARCHAR(300) NULL,
                `status` VARCHAR(10) NOT NULL DEFAULT 'active' COMMENT 'active | cleared | matched (timing item cleared by a match)',
                {$who},
                `cleared_by` VARCHAR(100) NULL, `cleared_at` DATETIME NULL, `clear_reason` VARCHAR(300) NULL,
                PRIMARY KEY (`id`), KEY `idx_brc_mark_acct` (`bank_account_id`, `status`), KEY `idx_brc_mark_line` (`line_id`), KEY `idx_brc_mark_book` (`book_key`){$end}",

            // a transfer between two Avenir accounts (e.g. USD → LKR), both statement lines, with the day's rate
            'brc_transfer' => "CREATE TABLE IF NOT EXISTS `brc_transfer` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `from_account_id` INT UNSIGNED NOT NULL, `to_account_id` INT UNSIGNED NOT NULL,
                `from_line_id` INT UNSIGNED NOT NULL, `to_line_id` INT UNSIGNED NOT NULL,
                `from_currency` VARCHAR(3) NOT NULL, `to_currency` VARCHAR(3) NOT NULL,
                `from_amount` DECIMAL(19,2) NOT NULL, `to_amount` DECIMAL(19,2) NOT NULL,
                `transfer_date` DATE NOT NULL,
                `implied_rate` DECIMAL(18,6) NULL,
                `daily_rate` DECIMAL(18,6) NULL, `daily_rate_source` VARCHAR(40) NULL,
                `rate_difference_lkr` DECIMAL(19,2) NULL COMMENT 'LKR received − amount × daily rate',
                `status` VARCHAR(10) NOT NULL DEFAULT 'active',
                `note` VARCHAR(300) NULL,
                {$who},
                `undone_by` VARCHAR(100) NULL, `undone_at` DATETIME NULL, `undo_reason` VARCHAR(300) NULL,
                PRIMARY KEY (`id`), KEY `idx_brc_tr_from` (`from_line_id`), KEY `idx_brc_tr_to` (`to_line_id`){$end}",

            // items that explain the difference between bank and book on the first day (opening reconciliation)
            'brc_opening' => "CREATE TABLE IF NOT EXISTS `brc_opening` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `bank_account_id` INT UNSIGNED NOT NULL,
                `description` VARCHAR(300) NOT NULL,
                `amount` DECIMAL(19,2) NOT NULL COMMENT 'part of (bank − book) at the start it explains, LKR',
                `evidence` VARCHAR(300) NULL,
                `status` VARCHAR(10) NOT NULL DEFAULT 'proposed' COMMENT 'proposed | accepted | rejected | withdrawn',
                {$who},
                `reviewed_by` VARCHAR(100) NULL, `reviewed_at` DATETIME NULL, `review_note` VARCHAR(300) NULL,
                PRIMARY KEY (`id`), KEY `idx_brc_open_acct` (`bank_account_id`, `status`){$end}",

            // a proposed correction for authorised review; never posted by the system
            'brc_correction' => "CREATE TABLE IF NOT EXISTS `brc_correction` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `bank_account_id` INT UNSIGNED NOT NULL,
                `kind` VARCHAR(30) NOT NULL,
                `description` VARCHAR(400) NOT NULL,
                `entry_date` DATE NOT NULL,
                `entry_json` TEXT NOT NULL COMMENT 'proposed journal lines: ledger_code, dr, cr, narration',
                `amount` DECIMAL(19,2) NOT NULL,
                `currency` VARCHAR(3) NOT NULL DEFAULT 'LKR',
                `related_json` TEXT NULL,
                `status` VARCHAR(10) NOT NULL DEFAULT 'proposed' COMMENT 'proposed | approved | rejected | withdrawn',
                {$who},
                `reviewed_by` VARCHAR(100) NULL, `reviewed_at` DATETIME NULL, `review_note` VARCHAR(300) NULL,
                `posted_ref` VARCHAR(60) NULL COMMENT 'ledger entry posted by hand in the accounting module, recorded afterwards',
                `posted_noted_by` VARCHAR(100) NULL, `posted_noted_at` DATETIME NULL,
                PRIMARY KEY (`id`), KEY `idx_brc_corr_acct` (`bank_account_id`, `status`){$end}",

            // the daily reconciliation record: prepared by one user, reviewed by another
            'brc_day' => "CREATE TABLE IF NOT EXISTS `brc_day` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `bank_account_id` INT UNSIGNED NOT NULL,
                `rec_date` DATE NOT NULL,
                `currency` VARCHAR(3) NOT NULL,
                `bank_closing` DECIMAL(19,2) NULL, `bank_closing_lkr` DECIMAL(19,2) NULL, `closing_rate` DECIMAL(18,6) NULL,
                `book_closing` DECIMAL(19,2) NULL,
                `difference_lkr` DECIMAL(19,2) NULL,
                `unexplained_lkr` DECIMAL(19,2) NULL,
                `status_code` VARCHAR(30) NOT NULL,
                `status_label` VARCHAR(120) NOT NULL,
                `snapshot` MEDIUMTEXT NOT NULL,
                `prepared_by` VARCHAR(100) NOT NULL, `prepared_at` DATETIME NOT NULL, `prepare_note` VARCHAR(300) NULL,
                `review_status` VARCHAR(10) NOT NULL DEFAULT 'prepared' COMMENT 'prepared | reviewed | returned',
                `reviewed_by` VARCHAR(100) NULL, `reviewed_at` DATETIME NULL, `review_note` VARCHAR(300) NULL,
                PRIMARY KEY (`id`), KEY `idx_brc_day_acct` (`bank_account_id`, `rec_date`){$end}",

            // version 2: one row per item that is matched or classified; the primary key makes a second claim impossible
            'brc_claim' => "CREATE TABLE IF NOT EXISTS `brc_claim` (
                `item_key` VARCHAR(80) NOT NULL COMMENT 'L<statement line id> | B<bank account id>:<ledger row ID.n>',
                `bank_account_id` INT UNSIGNED NOT NULL,
                `kind` VARCHAR(10) NOT NULL COMMENT 'match | mark',
                `ref_id` INT UNSIGNED NOT NULL,
                `claimed_by` VARCHAR(100) NOT NULL, `claimed_at` DATETIME NOT NULL,
                PRIMARY KEY (`item_key`), KEY `idx_brc_claim_ref` (`kind`, `ref_id`){$end}",

            // version 2: what happened to a timing classification (cleared by a match, restored by an undo)
            'brc_mark_event' => "CREATE TABLE IF NOT EXISTS `brc_mark_event` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `mark_id` INT UNSIGNED NOT NULL,
                `event` VARCHAR(20) NOT NULL COMMENT 'cleared_by_match | restored_by_undo',
                `match_id` INT UNSIGNED NULL,
                `by_user` VARCHAR(100) NOT NULL, `at` DATETIME NOT NULL,
                `note` VARCHAR(300) NULL,
                PRIMARY KEY (`id`), KEY `idx_brc_me_mark` (`mark_id`){$end}",
        ];
    }

    private static function menus($db): array
    {
        $notes = [];
        $cat = $db->query("SELECT id FROM menu_category WHERE category = 'Accounts' AND del = 0 ORDER BY id LIMIT 1")->getRowArray();
        if (!$cat) {
            $cat = $db->query("SELECT cat_id AS id FROM menu_list WHERE url = 'tour/tour_payment_dashboard' AND del = 0 LIMIT 1")->getRowArray();
        }
        if (!$cat) {
            return ['No menu category "Accounts" was found; add the Daily Bank Reconciliation entries under Settings → Menu List.'];
        }
        foreach (self::MENUS as $url => [$name, $shown]) {
            if ($db->query('SELECT id FROM menu_list WHERE url = ? AND del = 0', [$url])->getResultArray()) {
                continue;
            }
            $order = (int) ($db->query('SELECT COALESCE(MAX(order_id), 0) + 1 AS o FROM menu_list WHERE cat_id = ?', [(int) $cat['id']])->getRowArray()['o'] ?? 1);
            $db->query('INSERT INTO menu_list (cat_id, name, user, del, url, order_id, show_in_menu_bar) VALUES (?, ?, 1, 0, ?, ?, ?)', [(int) $cat['id'], $name, $url, $order, $shown]);
            $id = (int) $db->insertID();
            if ($id > 0) {
                $db->query('INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES (1, 1, ?)', [$id]);
            }
            $notes[] = "Privilege \"{$name}\" ({$url}) added and granted to the System Administrator only.";
        }
        return $notes;
    }
}
