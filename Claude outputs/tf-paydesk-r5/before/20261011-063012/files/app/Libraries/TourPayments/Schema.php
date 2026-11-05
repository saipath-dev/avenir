<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r1 (10 Oct 2026) — the tables and privileges behind the Tour Payment & Accounting Workflow.
 *
 * Everything new lives in tables prefixed tpd_ (tour payment desk). No existing table is altered and no existing
 * row is rewritten: the payment chain stays in payment_request → voucher / v_inv → Bank Payment Check as it is,
 * and the tpd_ tables add what the chain never recorded — the exchange rate locked to each request, the
 * accountant's bank initiation, the bank approver's final lock, the remittance advice sent to the supplier, the
 * company's own bank accounts, the suppliers' verified bank details, manual tour expenses, and the audit trail.
 *
 * ensure() is idempotent: CREATE TABLE IF NOT EXISTS for each table, the privilege rows only when missing, and a
 * copy of the touched menu rows kept in writable/tpd_backups before anything is added. Running it twice does nothing
 * the second time. The release kit's rollback removes nothing from the database: the tables stay (empty or not) so
 * no history is ever lost by a rollback; they are simply unused by the restored files.
 */
final class Schema
{
    public const VERSION = 6;   // bump when a table or privilege is added (4 = tf-paydesk-r2: supplier payables, batches, payment-date rate; 5 = r3: paying-account confirmation, Pay Individually Now, exchange gain/loss mapping, the voucher-number guard; 6 = r4: bank-charge ledger mapping, charge allocation and reconciliation)

    /** url => [name, hidden-privilege?, category key] — category key: 'payments' (where the dashboard sits), 'masters' */
    public const MENUS = [
        'tour/tour_payment_dashboard/approve'  => ['Tour payment approval (management)', 1, 'payments'],
        'tour/tour_payment_dashboard/payables' => ['Supplier Payables', 0, 'payments'],
        'tour/tour_payment_dashboard/batch'    => ['Supplier payment batch — create (management)', 1, 'payments'],
        'report/bank_payment_check/initiate'   => ['Bank payment initiation (accountant)', 1, 'payments'],
        'report/bank_payment_check/approve'    => ['Bank payment approver (Paid)', 1, 'payments'],
        'master_files/bank_accounts'           => ['Avenir Bank Accounts', 0, 'masters'],
        'master_files/supplier_bank'           => ['Supplier Bank Details', 0, 'masters'],
        'master_files/supplier_bank/verify'    => ['Supplier bank verification', 1, 'masters'],
    ];

    public static function version($db): int
    {
        try {
            $r = $db->query("SELECT v FROM tpd_meta WHERE k = 'schema_version'")->getRowArray();
            return (int) ($r['v'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function installed($db): bool
    {
        return self::version($db) >= self::VERSION;
    }

    /** Creates what is missing. Returns the notes of what it did. */
    public static function ensure($db, string $by = 'setup'): array
    {
        $notes = [];
        $coll = self::collation($db);
        foreach (self::tables($coll) as $name => $sql) {
            $existed = (bool) $db->query("SHOW TABLES LIKE " . $db->escape($name))->getResultArray();
            $db->query($sql);
            if (!$existed) {
                $notes[] = "Table {$name} created.";
            }
        }
        $notes = array_merge($notes, self::upgradeColumns($db));
        $notes = array_merge($notes, self::menus($db));
        $notes = array_merge($notes, self::voucherNumberGuard($db));
        $db->query("INSERT INTO tpd_meta (k, v) VALUES ('schema_version', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [(string) self::VERSION]);
        $db->query("INSERT INTO tpd_meta (k, v) VALUES ('installed_at', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [date('Y-m-d H:i:s') . ' by ' . $by]);
        if (!$notes) {
            $notes[] = 'Already in place; nothing to do.';
        }
        return $notes;
    }

    /** What is there and what is missing, without changing anything. */
    public static function status($db): array
    {
        $coll = self::collation($db);
        $tables = [];
        foreach (array_keys(self::tables($coll)) as $name) {
            $tables[$name] = (bool) $db->query("SHOW TABLES LIKE " . $db->escape($name))->getResultArray();
        }
        $menus = [];
        foreach (self::MENUS as $url => [$name]) {
            $menus[$url] = (bool) $db->query("SELECT id FROM menu_list WHERE url = ? AND del = 0", [$url])->getResultArray();
        }
        return ['version' => self::version($db), 'target' => self::VERSION, 'tables' => $tables, 'menus' => $menus,
            'voucher_number' => self::voucherNumberState($db),
            'ready' => self::version($db) >= self::VERSION && !in_array(false, $tables, true) && !in_array(false, $menus, true)];
    }

    /**
     * tf-paydesk-r3 — the official Payment Voucher No. is voucher.Vnum (the chain's own number, one per voucher, never
     * rewritten). Setup adds what the table never had: a UNIQUE index on Vnum (a duplicate number is refused by the
     * database) and a BEFORE UPDATE trigger (a change to an issued number is refused). Both are additive and reversible
     * (DROP INDEX / DROP TRIGGER); no row is touched. The trigger needs the TRIGGER privilege — if the database user
     * lacks it, Setup says so and the index alone stands.
     */
    public static function voucherNumberState($db): array
    {
        $out = ['unique_index' => false, 'guard_trigger' => false, 'duplicates' => null];
        try {
            $out['unique_index'] = (bool) $db->query("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'voucher' AND INDEX_NAME = 'uq_tpd_voucher_vnum' LIMIT 1")->getResultArray();
            $out['guard_trigger'] = (bool) $db->query("SELECT 1 FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = 'tpd_voucher_vnum_guard' LIMIT 1")->getResultArray();
            $out['duplicates'] = (int) ($db->query("SELECT COUNT(*) - COUNT(DISTINCT Vnum) AS d FROM voucher")->getRowArray()['d'] ?? 0);
        } catch (\Throwable $e) {
        }
        return $out;
    }

    private static function voucherNumberGuard($db): array
    {
        $notes = [];
        $st = self::voucherNumberState($db);
        if (!$st['unique_index']) {
            if ((int) $st['duplicates'] > 0) {
                $notes[] = 'Payment Voucher No.: voucher.Vnum has ' . (int) $st['duplicates'] . ' duplicated number(s) already — the unique index was NOT added. Ask Accounts to look at those vouchers first (SELECT Vnum, COUNT(*) FROM voucher GROUP BY Vnum HAVING COUNT(*) > 1).';
            } else {
                try {
                    $db->query("ALTER TABLE `voucher` ADD UNIQUE INDEX `uq_tpd_voucher_vnum` (`Vnum`)");
                    $notes[] = 'Payment Voucher No.: unique index added on voucher.Vnum — a duplicated voucher number is now refused by the database.';
                } catch (\Throwable $e) {
                    $notes[] = 'Payment Voucher No.: the unique index on voucher.Vnum could not be added (' . $e->getMessage() . ').';
                }
            }
        }
        if (!$st['guard_trigger']) {
            try {
                $db->query("CREATE TRIGGER `tpd_voucher_vnum_guard` BEFORE UPDATE ON `voucher` FOR EACH ROW BEGIN IF NEW.Vnum <> OLD.Vnum THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Payment Voucher No. (Vnum) cannot be changed once issued'; END IF; END");
                $notes[] = 'Payment Voucher No.: update guard added on voucher.Vnum — an issued voucher number cannot be changed.';
            } catch (\Throwable $e) {
                $notes[] = 'Payment Voucher No.: the update guard (trigger) could not be added — ' . $e->getMessage() . '. The application never rewrites a voucher number; the trigger would only add a database-level refusal.';
            }
        }
        return $notes;
    }

    private static function collation($db): string
    {
        try {
            $r = $db->query("SELECT @@collation_database AS c")->getRowArray();
            $c = (string) ($r['c'] ?? '');
            return str_starts_with($c, 'utf8mb4_') ? $c : 'utf8mb4_unicode_ci';
        } catch (\Throwable $e) {
            return 'utf8mb4_unicode_ci';
        }
    }

    /** @return array<string,string> table name => CREATE TABLE IF NOT EXISTS */
    private static function tables(string $coll): array
    {
        $end = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={$coll}";
        return [
            'tpd_meta' => "CREATE TABLE IF NOT EXISTS `tpd_meta` (
                `k` VARCHAR(60) NOT NULL, `v` TEXT NULL, PRIMARY KEY (`k`){$end}",

            // the company's own bank accounts — the account the accountant pays from, mapped to its ledger code
            'tpd_bank_account' => "CREATE TABLE IF NOT EXISTS `tpd_bank_account` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `entity` VARCHAR(160) NOT NULL DEFAULT 'Avenir Holidays (Pvt) Ltd',
                `bank_name` VARCHAR(160) NOT NULL,
                `branch` VARCHAR(160) NULL,
                `account_name` VARCHAR(160) NOT NULL,
                `account_no` VARCHAR(60) NOT NULL,
                `currency` VARCHAR(3) NOT NULL DEFAULT 'LKR',
                `swift` VARCHAR(20) NULL,
                `account_type` VARCHAR(40) NULL,
                `label` VARCHAR(120) NOT NULL,
                `ledger_code` VARCHAR(50) NULL COMMENT 'account_posting.L_Code the ledger posts to (the bank ledger account)',
                `is_default` TINYINT(1) NOT NULL DEFAULT 0,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `external_display` VARCHAR(10) NOT NULL DEFAULT 'masked' COMMENT 'full | masked | hidden on supplier documents',
                `display_digits` TINYINT UNSIGNED NOT NULL DEFAULT 4,
                `note` VARCHAR(300) NULL,
                `created_by` VARCHAR(100) NULL, `created_at` DATETIME NULL,
                `updated_by` VARCHAR(100) NULL, `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`), KEY `idx_tpd_bank_active` (`active`, `is_default`){$end}",

            // a supplier's verified bank-payment information, beside the supplier master (which keeps its own columns)
            'tpd_supplier_bank' => "CREATE TABLE IF NOT EXISTS `tpd_supplier_bank` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `sup_acc` VARCHAR(50) NOT NULL,
                `beneficiary_name` VARCHAR(200) NULL,
                `bank_name` VARCHAR(160) NULL,
                `branch` VARCHAR(160) NULL,
                `account_no` VARCHAR(60) NULL,
                `currency` VARCHAR(3) NOT NULL DEFAULT 'LKR',
                `swift` VARCHAR(20) NULL,
                `invoice_email` VARCHAR(200) NULL,
                `remittance_email` VARCHAR(200) NULL,
                `status` VARCHAR(12) NOT NULL DEFAULT 'unverified' COMMENT 'unverified | verified | changed | inactive',
                `verified_by` VARCHAR(100) NULL, `verified_at` DATETIME NULL,
                `verified_hash` CHAR(40) NULL COMMENT 'sha1 of the details as verified — a later change is detected against it',
                `note` VARCHAR(300) NULL,
                `updated_by` VARCHAR(100) NULL, `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`), UNIQUE KEY `uq_tpd_supplier_bank` (`sup_acc`){$end}",

            // what the payment chain never recorded per request: the rate locked to it, the invoice, the due date
            'tpd_request_ext' => "CREATE TABLE IF NOT EXISTS `tpd_request_ext` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `pr_id` INT UNSIGNED NOT NULL COMMENT 'payment_request.id',
                `tour_id` VARCHAR(50) NOT NULL,
                `hotel_acc` VARCHAR(50) NOT NULL,
                `type` VARCHAR(40) NULL,
                `source_type` VARCHAR(20) NULL COMMENT 'hotel | activity | driver_advance | expense',
                `source_id` VARCHAR(60) NULL,
                `currency` VARCHAR(3) NOT NULL DEFAULT 'LKR',
                `amount` DECIMAL(14,2) NULL COMMENT 'the request in its own currency',
                `usd_rate` DECIMAL(12,4) NULL COMMENT 'USD → LKR locked when the request was made',
                `lkr_amount` DECIMAL(14,2) NULL,
                `rate_date` DATE NULL,
                `rate_user` VARCHAR(100) NULL,
                `invoice_no` VARCHAR(100) NULL,
                `booking_ref` VARCHAR(100) NULL,
                `due_date` DATE NULL,
                `note` VARCHAR(500) NULL,
                `approved_by` VARCHAR(100) NULL, `approved_at` DATETIME NULL,
                `voucher_vnum` INT NULL,
                `created_at` DATETIME NULL,
                PRIMARY KEY (`id`), UNIQUE KEY `uq_tpd_request_ext` (`pr_id`), KEY `idx_tpd_request_ext_tour` (`tour_id`, `hotel_acc`){$end}",

            // the accountant's step: the bank transfer initiated, awaiting the bank approver
            'tpd_bank_initiation' => "CREATE TABLE IF NOT EXISTS `tpd_bank_initiation` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `vnum` INT NOT NULL COMMENT 'voucher.Vnum',
                `voucher_id` INT NULL,
                `tour_id` VARCHAR(50) NULL,
                `sup_acc` VARCHAR(50) NULL,
                `bank_account_id` INT UNSIGNED NULL,
                `ledger_code` VARCHAR(50) NULL,
                `initiated_at` DATETIME NOT NULL,
                `batch_ref` VARCHAR(100) NULL,
                `bank_ref` VARCHAR(100) NULL,
                `amount` DECIMAL(14,2) NOT NULL,
                `currency` VARCHAR(3) NOT NULL DEFAULT 'LKR',
                `charges` DECIMAL(12,2) NULL,
                `file_path` VARCHAR(255) NULL,
                `note` VARCHAR(500) NULL,
                `initiated_by` VARCHAR(100) NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`), UNIQUE KEY `uq_tpd_bank_initiation` (`vnum`), KEY `idx_tpd_bank_initiation_batch` (`batch_ref`){$end}",

            // the bank approver's step: Paid — everything locked as it was at that moment
            'tpd_bank_approval' => "CREATE TABLE IF NOT EXISTS `tpd_bank_approval` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `vnum` INT NOT NULL,
                `voucher_id` INT NULL,
                `tour_id` VARCHAR(50) NULL,
                `sup_acc` VARCHAR(50) NULL,
                `paid_date` DATE NOT NULL,
                `amount` DECIMAL(14,2) NOT NULL,
                `currency` VARCHAR(3) NOT NULL DEFAULT 'LKR',
                `usd_amount` DECIMAL(14,2) NULL,
                `usd_rate` DECIMAL(12,4) NULL,
                `lkr_amount` DECIMAL(14,2) NULL,
                `bank_account_id` INT UNSIGNED NULL,
                `ledger_code` VARCHAR(50) NULL,
                `bank_ref` VARCHAR(100) NULL,
                `initiated_by` VARCHAR(100) NULL,
                `approved_by` VARCHAR(100) NOT NULL,
                `approved_at` DATETIME(3) NOT NULL,
                `note` VARCHAR(500) NULL,
                PRIMARY KEY (`id`), UNIQUE KEY `uq_tpd_bank_approval` (`vnum`){$end}",

            // one remittance advice per voucher, per supplier — sent once
            'tpd_remittance' => "CREATE TABLE IF NOT EXISTS `tpd_remittance` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `vnum` INT NOT NULL,
                `tour_id` VARCHAR(50) NULL,
                `sup_acc` VARCHAR(50) NOT NULL,
                `supplier_name` VARCHAR(200) NULL,
                `status` VARCHAR(10) NOT NULL DEFAULT 'pending' COMMENT 'pending | prepared | sent | failed',
                `to_email` VARCHAR(200) NULL,
                `cc` VARCHAR(400) NULL,
                `subject` VARCHAR(255) NULL,
                `body` MEDIUMTEXT NULL,
                `pdf_path` VARCHAR(255) NULL,
                `prepared_by` VARCHAR(100) NULL, `prepared_at` DATETIME NULL,
                `sent_by` VARCHAR(100) NULL, `sent_at` DATETIME NULL,
                `error` VARCHAR(500) NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`), UNIQUE KEY `uq_tpd_remittance` (`vnum`), KEY `idx_tpd_remittance_sup` (`sup_acc`), KEY `idx_tpd_remittance_tour` (`tour_id`){$end}",

            // supporting documents and numbers: supplier invoices, booking confirmations, bank proofs, the advices sent
            'tpd_document' => "CREATE TABLE IF NOT EXISTS `tpd_document` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `tour_id` VARCHAR(50) NOT NULL,
                `kind` VARCHAR(15) NOT NULL COMMENT 'invoice | booking | bank_proof | remittance | other',
                `ref_type` VARCHAR(20) NULL COMMENT 'line | request | voucher',
                `ref_id` VARCHAR(80) NULL,
                `vnum` INT NULL,
                `number` VARCHAR(100) NULL,
                `title` VARCHAR(200) NULL,
                `file_path` VARCHAR(255) NULL,
                `uploaded_by` VARCHAR(100) NULL, `uploaded_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`), KEY `idx_tpd_document_tour` (`tour_id`), KEY `idx_tpd_document_ref` (`ref_type`, `ref_id`), KEY `idx_tpd_document_vnum` (`vnum`){$end}",

            // other supplier expenses added by hand (restaurants not in the itinerary, tickets bought on the day, ...)
            'tpd_expense' => "CREATE TABLE IF NOT EXISTS `tpd_expense` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `tour_id` VARCHAR(50) NOT NULL,
                `manual_id` VARCHAR(50) NULL,
                `service_date` DATE NULL,
                `category` VARCHAR(30) NOT NULL DEFAULT 'Other',
                `supplier_acc` VARCHAR(50) NULL,
                `supplier_name` VARCHAR(200) NOT NULL,
                `description` VARCHAR(255) NOT NULL,
                `currency` VARCHAR(3) NOT NULL DEFAULT 'LKR',
                `amount` DECIMAL(14,2) NOT NULL,
                `invoice_no` VARCHAR(100) NULL,
                `due_date` DATE NULL,
                `note` VARCHAR(500) NULL,
                `status` VARCHAR(10) NOT NULL DEFAULT 'open' COMMENT 'open | cancelled',
                `created_by` VARCHAR(100) NOT NULL, `created_at` DATETIME NOT NULL,
                `cancelled_by` VARCHAR(100) NULL, `cancelled_at` DATETIME NULL, `cancel_reason` VARCHAR(300) NULL,
                PRIMARY KEY (`id`), KEY `idx_tpd_expense_tour` (`tour_id`, `status`){$end}",

            // tf-paydesk-r2: a supplier payment batch — approved requests of ONE supplier (any tours) taken out of Supplier Payables
            // together; one batch = one voucher in the accountant's queue (Bank Payment Check)
            'tpd_batch' => "CREATE TABLE IF NOT EXISTS `tpd_batch` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `batch_no` VARCHAR(20) NULL COMMENT 'SB-00001 …',
                `sup_acc` VARCHAR(50) NOT NULL,
                `supplier_name` VARCHAR(200) NULL,
                `currency` VARCHAR(5) NOT NULL DEFAULT 'LKR' COMMENT 'USD | LKR | MIXED',
                `usd_total` DECIMAL(14,2) NULL COMMENT 'the locked USD amounts, summed',
                `approval_lkr_total` DECIMAL(14,2) NOT NULL COMMENT 'provisional LKR at the approval-date rates (+ the LKR items)',
                `item_count` INT NOT NULL DEFAULT 0,
                `tour_count` INT NOT NULL DEFAULT 0,
                `bank_account_id` INT UNSIGNED NULL,
                `ledger_code` VARCHAR(50) NULL,
                `vnum` INT NULL COMMENT 'the voucher issued for the batch',
                `status` VARCHAR(12) NOT NULL DEFAULT 'issued' COMMENT 'issued | initiated | paid | notified | cancelled',
                `note` VARCHAR(500) NULL,
                `created_by` VARCHAR(100) NOT NULL, `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`), UNIQUE KEY `uq_tpd_batch_no` (`batch_no`), KEY `idx_tpd_batch_sup` (`sup_acc`, `status`), KEY `idx_tpd_batch_vnum` (`vnum`){$end}",

            // append-only: who did what, when, from what to what
            'tpd_audit' => "CREATE TABLE IF NOT EXISTS `tpd_audit` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `at` DATETIME(3) NOT NULL,
                `user` VARCHAR(100) NOT NULL,
                `user_id` INT NULL,
                `tour_id` VARCHAR(50) NULL,
                `manual_id` VARCHAR(50) NULL,
                `action` VARCHAR(60) NOT NULL,
                `ref_type` VARCHAR(20) NULL,
                `ref_id` VARCHAR(80) NULL,
                `vnum` INT NULL,
                `invoice_no` VARCHAR(100) NULL,
                `prev_value` TEXT NULL,
                `new_value` TEXT NULL,
                `reason` VARCHAR(500) NULL,
                `usd_rate` DECIMAL(12,4) NULL,
                `bank_account` VARCHAR(160) NULL,
                `bank_ref` VARCHAR(100) NULL,
                `ip` VARCHAR(45) NULL,
                PRIMARY KEY (`id`), KEY `idx_tpd_audit_tour` (`tour_id`, `at`), KEY `idx_tpd_audit_vnum` (`vnum`), KEY `idx_tpd_audit_user` (`user`, `at`){$end}",
        ];
    }

    /** Columns added after a release: CREATE TABLE IF NOT EXISTS cannot reach a table that already exists. */
    private static function upgradeColumns($db): array
    {
        $notes = [];
        $want = [
            // table => [column => definition]
            'tpd_request_ext' => [
                'parent_pr_id' => "INT UNSIGNED NULL COMMENT 'the earlier request this one pays the balance of'",
                // tf-paydesk-r2 — the approval figures (never overwritten) and the final payment figures (locked at Paid)
                'batch_id' => "INT UNSIGNED NULL COMMENT 'tpd_batch.id once taken into a supplier payment batch'",
                'vinv_id' => "INT NULL COMMENT 'the v_inv line of this request on the batch voucher'",
                'approval_rate' => "DECIMAL(12,4) NULL COMMENT 'USD rate shown at approval (provisional)'",
                'approval_lkr' => "DECIMAL(14,2) NULL COMMENT 'provisional LKR = USD × approval rate'",
                'approval_rate_date' => 'DATE NULL',
                'paid_rate' => "DECIMAL(12,4) NULL COMMENT 'USD rate of the actual bank-payment date (final, locked at Paid)'",
                'paid_lkr' => "DECIMAL(14,2) NULL COMMENT 'final LKR = USD × payment-date rate'",
                'paid_date' => 'DATE NULL',
                'fx_diff' => "DECIMAL(14,2) NULL COMMENT 'final LKR − provisional LKR (+ loss / − gain), this request\\'s share'",
                'fx_entry_id' => "INT NULL COMMENT 'tf-paydesk-r3: the account_runing entry (pair) that posted this line\\'s exchange-rate difference'",
                // tf-paydesk-r4 — this request's share of the bank charges of its voucher (allocated pro rata to the final LKR), posted once
                'charge_share' => "DECIMAL(12,2) NULL COMMENT 'tf-paydesk-r4: this request\\'s share of the bank charges of the payment (pro rata to its final LKR)'",
                'charge_entry_id' => "INT NULL COMMENT 'tf-paydesk-r4: the account_runing entry (pair) that posted this line\\'s share of the bank charges'",
            ],
            'tpd_remittance'  => ['message_id' => 'VARCHAR(200) NULL'],
            'tpd_bank_initiation' => [
                'batch_id' => 'INT UNSIGNED NULL',
                'payment_date' => "DATE NULL COMMENT 'the actual bank-payment date the accountant entered'",
                'usd_amount' => 'DECIMAL(14,2) NULL',
                'usd_rate' => "DECIMAL(12,4) NULL COMMENT 'the rate of the payment date, confirmed by the accountant'",
                'rate_source' => "VARCHAR(12) NULL COMMENT 'daily (Today''s rate) | entered'",
                'approval_lkr' => "DECIMAL(14,2) NULL COMMENT 'the provisional LKR at approval, for comparison'",
                'fx_diff' => 'DECIMAL(14,2) NULL',
                'total_debit' => "DECIMAL(14,2) NULL COMMENT 'LKR amount + bank charges'",
            ],
            'tpd_bank_approval' => [
                'batch_id' => 'INT UNSIGNED NULL',
                'approval_lkr' => "DECIMAL(14,2) NULL COMMENT 'the provisional LKR at approval — kept beside the final figures'",
                'fx_diff' => "DECIMAL(14,2) NULL COMMENT 'final LKR − provisional LKR: + exchange loss, − exchange gain'",
                'charges' => 'DECIMAL(12,2) NULL',
                'total_debit' => 'DECIMAL(14,2) NULL',
                'rate_source' => 'VARCHAR(12) NULL',
                'fx_posted' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 when the difference was posted to the exchange gain/loss ledger account'",
                // tf-paydesk-r3
                'fx_entries' => "VARCHAR(255) NULL COMMENT 'the account_runing entry ids of the exchange-difference postings (one pair per tour line)'",
                'account_confirmed_by' => "VARCHAR(100) NULL COMMENT 'the bank approver who confirmed the paying account at Paid'",
                // tf-paydesk-r4 — the bank charges posted once (Dr Bank Charges / Cr paying account, per tour line) and the ledger reconciled to the bank debit
                'charges_posted' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'tf-paydesk-r4: 1 when the bank charges were posted to the mapped Bank Charges account (once)'",
                'charge_entries' => "VARCHAR(255) NULL COMMENT 'the account_runing entry ids of the bank-charge postings (one pair per tour line)'",
                'ledger_bank_credit' => "DECIMAL(14,2) NULL COMMENT 'sum of the credits to the paying account\\'s ledger for this voucher at Paid (payment + charges)'",
                'reconciled' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 when ledger_bank_credit = total_debit and every entry balances'",
            ],
            'tpd_batch' => [
                // tf-paydesk-r3 — Pay Individually Now, and management's suggested paying account (the accountant confirms the actual one)
                'individual' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = paid individually (the accumulation stage bypassed)'",
                'bypass_reason' => "VARCHAR(40) NULL COMMENT 'driver_advance | driver_settlement | urgent_cash | pay_at_gate | emergency | other'",
                'bypass_note' => 'VARCHAR(500) NULL',
                'suggested_bank_account_id' => "INT UNSIGNED NULL COMMENT 'the Avenir account management suggested when creating the batch'",
                'suggested_ledger_code' => 'VARCHAR(50) NULL',
            ],
        ];
        foreach ($want as $table => $cols) {
            try {
                $have = array_map(static fn ($r) => $r['Field'], $db->query("SHOW COLUMNS FROM `{$table}`")->getResultArray());
            } catch (\Throwable $e) {
                continue;
            }
            foreach ($cols as $col => $def) {
                if (!in_array($col, $have, true)) {
                    $db->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
                    $notes[] = "Column {$table}.{$col} added.";
                }
            }
        }
        return $notes;
    }

    /** The privilege entries (menu_list rows) and their grant to the System Administrator group. */
    private static function menus($db): array
    {
        $notes = [];
        $cats = self::categories($db);
        foreach (self::MENUS as $url => [$name, $hidden, $catKey]) {
            if ($db->query("SELECT id FROM menu_list WHERE url = ? AND del = 0", [$url])->getResultArray()) {
                continue;
            }
            $cat = $cats[$catKey] ?? $cats['masters'] ?? null;
            if ($cat === null) {
                $notes[] = "The \"{$name}\" entry could not be added (no menu category found). Add it under Settings → Menu List: URL {$url}.";
                continue;
            }
            $order = (int) ($db->query("SELECT COALESCE(MAX(order_id), 0) + 1 AS o FROM menu_list WHERE cat_id = ?", [$cat])->getRowArray()['o'] ?? 1);
            $db->query("INSERT INTO menu_list (cat_id, name, user, del, url, order_id, show_in_menu_bar) VALUES (?, ?, 1, 0, ?, ?, ?)", [$cat, $name, $url, $order, $hidden]);
            $id = (int) $db->insertID();
            if ($id > 0) {
                // the System Administrator group holds every new privilege; others are granted under Settings → Group Privileges
                $db->query("INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES (1, 1, ?)", [$id]);
            }
            $notes[] = "Privilege \"{$name}\" ({$url}) added and granted to the System Administrator. Grant it to the right groups under Settings → Group Privileges; they log out and in once.";
        }
        return $notes;
    }

    /** The category the dashboard sits in on this install (Payments / Tour), and Master Files. */
    private static function categories($db): array
    {
        $out = [];
        $r = $db->query("SELECT cat_id FROM menu_list WHERE url = 'tour/tour_payment_dashboard' AND del = 0 LIMIT 1")->getRowArray();
        if ($r) {
            $out['payments'] = (int) $r['cat_id'];
        }
        foreach (['Payments', 'Tour', 'Master Files'] as $cat) {
            $c = $db->query("SELECT id FROM menu_category WHERE category = ? AND del = 0 ORDER BY id LIMIT 1", [$cat])->getRowArray();
            if ($c) {
                if ($cat === 'Master Files') {
                    $out['masters'] = (int) $c['id'];
                } elseif (!isset($out['payments'])) {
                    $out['payments'] = (int) $c['id'];
                }
            }
        }
        if (!isset($out['payments']) && isset($out['masters'])) {
            $out['payments'] = $out['masters'];
        }
        return $out;
    }
}
