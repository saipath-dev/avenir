<?php

namespace App\Libraries\BankRec;

/**
 * tf-bankrec-r1 (Step 2, 11 Oct 2026): the tables and privileges of the Daily Bank Reconciliation workflow.
 *
 * Everything lives in new tables prefixed brc_. No existing table is altered and no existing row is rewritten: the
 * ledger (account_runing), its reconciliation flags (bank_rec, bankst_flag, BankFlg), vouchers and the payment chain are
 * only READ. Statement lines, matches, marks, transfers, rates, proposed corrections and daily records are kept here,
 * and every change is written to the existing audit trail (tpd_audit).
 *
 * ensure() is idempotent: CREATE TABLE IF NOT EXISTS, privilege rows only when missing (granted to the System
 * Administrator group only; departments are granted under Settings → Group Privileges). A rollback of the release
 * removes nothing from the database, so no history is lost.
 */
final class Schema
{
    public const VERSION = 1;
    public const META_KEY = 'brc_schema_version';

    /** url => [name, shown in the menu bar (1) or a hidden privilege (0)] — category: Accounts */
    public const MENUS = [
        'accounts/daily_bank_rec'         => ['Daily Bank Reconciliation', 1],
        'accounts/daily_bank_rec/prepare' => ['Daily Bank Reconciliation — prepare (import, match, propose)', 0],
        'accounts/daily_bank_rec/review'  => ['Daily Bank Reconciliation — review (confirm mappings, approve, sign off)', 0],
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

    /** Creates what is missing; returns notes. Needs the payment workflow's tpd_meta / tpd_audit (R5). */
    public static function ensure($db): array
    {
        $notes = [];
        if (!$db->query("SHOW TABLES LIKE 'tpd_meta'")->getResultArray() || !$db->query("SHOW TABLES LIKE 'tpd_audit'")->getResultArray()) {
            return ['The payment workflow (tpd_meta, tpd_audit) is not set up on this database; set it up first.'];
        }
        $coll = self::collation($db);
        foreach (self::tables($coll) as $name => $sql) {
            $existed = (bool) $db->query('SHOW TABLES LIKE ' . $db->escape($name))->getResultArray();
            $db->query($sql);
            if (!$existed) {
                $notes[] = "Table {$name} created.";
            }
        }
        $notes = array_merge($notes, self::menus($db));
        $db->query('INSERT INTO tpd_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [self::META_KEY, (string) self::VERSION]);
        return $notes ?: ['Already in place; nothing to do.'];
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

    /** @return array<string,string> */
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

            // a match: statement line(s) ⇄ ledger row(s); undone matches are kept
            'brc_match' => "CREATE TABLE IF NOT EXISTS `brc_match` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `bank_account_id` INT UNSIGNED NOT NULL,
                `method` VARCHAR(12) NOT NULL COMMENT 'suggested | manual',
                `bank_total` DECIMAL(19,2) NOT NULL COMMENT 'account currency',
                `bank_total_lkr` DECIMAL(19,2) NULL,
                `book_total` DECIMAL(19,2) NOT NULL COMMENT 'LKR (the ledger)',
                `difference_lkr` DECIMAL(19,2) NOT NULL DEFAULT 0,
                `diff_reason` VARCHAR(40) NULL COMMENT 'exchange rate | rounding | other',
                `status` VARCHAR(10) NOT NULL DEFAULT 'active' COMMENT 'active | undone',
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
                `status` VARCHAR(10) NOT NULL DEFAULT 'active' COMMENT 'active | cleared',
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
