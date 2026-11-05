<?php

namespace App\Libraries\TourPayments;

/**
 * tf-bankreg-r1 (22 Oct 2026) — the Supplier Bank Account Registry and the account carried by each payment.
 *
 *   tpd_supplier_account   one row per bank account of a payee (hotel, driver, guide, restaurant, entrance / activity
 *                          supplier, any other payee), linked to the supplier master by Sup_Acc_No — never by name.
 *                          The supplier's display name (supplier.Name) and the exact account holder's name are separate.
 *                          Several accounts per supplier; one default; active / inactive; verified by a holder of the
 *                          verification privilege (the fingerprint detects a later change, as tpd_supplier_bank did).
 *   tpd_payment_account    the account SELECTED for a payment, copied in full at the moment of selection (request at
 *                          approval, then the batch and its voucher): a later registry edit never changes it. Where the
 *                          registry row now differs, is inactive or was deleted, the payment is flagged for renewed
 *                          review and is not initiated until a holder of the verification or batch privilege reviews it.
 *
 * The existing checks stay: SupplierBank::forPayment() answers from this registry when the supplier has a row here, so the
 * verification prompts at approval and batch creation, the masks on account numbers (Perms::bankDetails / Mask) and the
 * audit trail (tpd_audit) all work as before. Nothing posts to the ledger from here.
 */
final class SupplierAccounts
{
    public const MENU_URL = 'master_files/supplier_accounts';
    public const MENU_NAME = 'Supplier Bank Account Registry';
    public const REQUIRED = ['holder_name', 'bank_name', 'account_no'];   // a usable account: holder, bank and number (branch is kept but not compulsory)

    // ---------------------------------------------------------------------
    //  Tables and the menu entry (idempotent)
    // ---------------------------------------------------------------------

    public static function installed($db): bool
    {
        try {
            return (bool) $db->query("SHOW TABLES LIKE 'tpd_supplier_account'")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Create the two tables, seed one default account per supplier from what the system already holds, add the menu entry. */
    public static function ensure($db, string $by = 'setup'): array
    {
        $notes = [];
        $end = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $db->query("CREATE TABLE IF NOT EXISTS `tpd_supplier_account` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `sup_acc` VARCHAR(50) NOT NULL COMMENT 'supplier.Sup_Acc_No — the master id, the only link',
            `holder_name` VARCHAR(200) NULL COMMENT 'the exact name on the bank account',
            `bank_name` VARCHAR(160) NULL,
            `branch` VARCHAR(160) NULL,
            `account_no` VARCHAR(60) NULL,
            `currency` VARCHAR(3) NOT NULL DEFAULT 'LKR',
            `swift` VARCHAR(20) NULL,
            `label` VARCHAR(60) NULL COMMENT 'a short name staff know the account by (optional)',
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `status` VARCHAR(12) NOT NULL DEFAULT 'unverified' COMMENT 'unverified | verified',
            `verified_by` VARCHAR(100) NULL, `verified_at` DATETIME NULL,
            `verified_hash` CHAR(40) NULL,
            `source` VARCHAR(20) NULL COMMENT 'entered | supplier_master | supplier_bank (seeded from)',
            `note` VARCHAR(300) NULL,
            `created_by` VARCHAR(100) NULL, `created_at` DATETIME NULL,
            `updated_by` VARCHAR(100) NULL, `updated_at` DATETIME NULL,
            PRIMARY KEY (`id`), KEY `idx_tpd_supacc_sup` (`sup_acc`, `active`, `is_default`){$end}");
        $db->query("CREATE TABLE IF NOT EXISTS `tpd_payment_account` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ref_type` VARCHAR(10) NOT NULL COMMENT 'request | batch | voucher',
            `ref_id` VARCHAR(50) NOT NULL COMMENT 'payment_request.id | tpd_batch.id | voucher.Vnum',
            `sup_acc` VARCHAR(50) NOT NULL,
            `account_id` INT UNSIGNED NULL COMMENT 'tpd_supplier_account.id at selection (NULL: no registered account)',
            `supplier_name` VARCHAR(200) NULL,
            `holder_name` VARCHAR(200) NULL, `bank_name` VARCHAR(160) NULL, `branch` VARCHAR(160) NULL, `account_no` VARCHAR(60) NULL,
            `currency` VARCHAR(3) NULL, `swift` VARCHAR(20) NULL,
            `fingerprint` CHAR(40) NULL COMMENT 'of the registry row as selected',
            `complete` TINYINT(1) NOT NULL DEFAULT 0,
            `stage` VARCHAR(12) NOT NULL COMMENT 'approval | batch | reselected',
            `selected_by` VARCHAR(100) NULL, `selected_at` DATETIME NULL, `reason` VARCHAR(300) NULL,
            `reviewed_by` VARCHAR(100) NULL, `reviewed_at` DATETIME NULL, `review_note` VARCHAR(300) NULL,
            `reviewed_hash` CHAR(40) NULL COMMENT 'the registry fingerprint the reviewer accepted (keep) — a further change flags again',
            PRIMARY KEY (`id`), UNIQUE KEY `uq_tpd_payacc_ref` (`ref_type`, `ref_id`), KEY `idx_tpd_payacc_sup` (`sup_acc`){$end}");
        $notes[] = 'Tables tpd_supplier_account and tpd_payment_account are in place.';
        $notes = array_merge($notes, self::seed($db, $by));
        $notes[] = self::menu($db);
        return $notes;
    }

    /** One default account per supplier that has bank details anywhere already (the verified record first, else the master's columns). Runs once. */
    private static function seed($db, string $by): array
    {
        $n = (int) ($db->query("SELECT COUNT(*) AS n FROM tpd_supplier_account")->getRowArray()['n'] ?? 0);
        if ($n > 0) {
            return ['Registry already seeded (' . $n . ' accounts).'];
        }
        $hasRec = (bool) $db->query("SHOW TABLES LIKE 'tpd_supplier_bank'")->getResultArray();
        $rows = $db->query("SELECT s.Sup_Acc_No, s.Name, s.sup_bank_acc, s.bank_name, s.branch_name, s.acc_holder" . ($hasRec ? ", b.beneficiary_name, b.bank_name AS b_bank, b.branch AS b_branch, b.account_no AS b_account, b.currency AS b_currency, b.swift AS b_swift, b.status AS b_status, b.verified_by AS b_vby, b.verified_at AS b_vat" : '')
            . " FROM supplier s" . ($hasRec ? " LEFT JOIN tpd_supplier_bank b ON b.sup_acc = s.Sup_Acc_No" : '') . " ORDER BY s.Sup_Acc_No")->getResultArray();
        $now = date('Y-m-d H:i:s');
        $made = 0;
        $incomplete = 0;
        foreach ($rows as $r) {
            $fromRec = $hasRec && trim((string) ($r['b_account'] ?? '')) !== '';
            $acc = $fromRec ? $r['b_account'] : ($r['sup_bank_acc'] ?? '');
            $holder = $fromRec ? ($r['beneficiary_name'] ?: $r['acc_holder']) : ($r['acc_holder'] ?? '');
            $bank = $fromRec ? ($r['b_bank'] ?: $r['bank_name']) : ($r['bank_name'] ?? '');
            $branch = $fromRec ? ($r['b_branch'] ?: $r['branch_name']) : ($r['branch_name'] ?? '');
            if (trim((string) $acc) === '' && trim((string) $holder) === '' && trim((string) $bank) === '') {
                continue;   // nothing known: the supplier is listed without an account until one is entered
            }
            $row = ['sup_acc' => $r['Sup_Acc_No'], 'holder_name' => self::clean($holder, 200), 'bank_name' => self::clean($bank, 160), 'branch' => self::clean($branch, 160),
                'account_no' => self::cleanNo($acc), 'currency' => strtoupper(substr(trim((string) ($fromRec ? ($r['b_currency'] ?: 'LKR') : 'LKR')), 0, 3)) ?: 'LKR',
                'swift' => $fromRec ? (self::clean($r['b_swift'] ?? '', 20) ?: null) : null, 'is_default' => 1, 'active' => 1,
                'status' => ($fromRec && ($r['b_status'] ?? '') === 'verified') ? 'verified' : 'unverified',
                'verified_by' => ($fromRec && ($r['b_status'] ?? '') === 'verified') ? $r['b_vby'] : null, 'verified_at' => ($fromRec && ($r['b_status'] ?? '') === 'verified') ? $r['b_vat'] : null,
                'source' => $fromRec ? 'supplier_bank' : 'supplier_master', 'note' => 'Seeded from ' . ($fromRec ? 'Supplier Bank Details' : 'the supplier master') . ' on ' . substr($now, 0, 10),
                'created_by' => $by, 'created_at' => $now, 'updated_by' => $by, 'updated_at' => $now];
            if ($row['status'] === 'verified') {
                $row['verified_hash'] = self::fingerprint($row);
            }
            $db->table('tpd_supplier_account')->insert($row);
            $made++;
            if (!self::isComplete($row)) {
                $incomplete++;
            }
        }
        Audit::log($db, ['action' => 'supplier_registry_seeded', 'user' => $by, 'ref_type' => 'registry', 'ref_id' => 'seed', 'new' => ['accounts' => $made, 'incomplete' => $incomplete]]);
        return ['Registry seeded: ' . $made . ' default accounts copied from the existing records (' . $incomplete . ' incomplete — holder, bank or account number missing — to be completed in the registry).'];
    }

    private static function menu($db): string
    {
        try {
            if ($db->query("SELECT id FROM menu_list WHERE url = ? AND del = 0", [self::MENU_URL])->getResultArray()) {
                return 'Menu entry "' . self::MENU_NAME . '" already present.';
            }
            $c = $db->query("SELECT id FROM menu_category WHERE category = 'Master Files' AND del = 0 ORDER BY id LIMIT 1")->getRowArray();
            if (!$c) {
                return 'No Master Files menu category: add the entry under Settings → Menu List (URL ' . self::MENU_URL . ').';
            }
            $order = (int) ($db->query("SELECT COALESCE(MAX(order_id), 0) + 1 AS o FROM menu_list WHERE cat_id = ?", [(int) $c['id']])->getRowArray()['o'] ?? 1);
            $db->query("INSERT INTO menu_list (cat_id, name, user, del, url, order_id, show_in_menu_bar) VALUES (?, ?, 1, 0, ?, ?, 0)", [(int) $c['id'], self::MENU_NAME, self::MENU_URL, $order]);
            $id = (int) $db->insertID();
            if ($id > 0) {
                $db->query("INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES (1, 1, ?)", [$id]);
            }
            return 'Menu entry "' . self::MENU_NAME . '" added under Master Files and granted to the System Administrator; grant it to other groups under Settings → Group Privileges.';
        } catch (\Throwable $e) {
            return 'Menu entry not added: ' . $e->getMessage();
        }
    }

    // ---------------------------------------------------------------------
    //  Reading the registry
    // ---------------------------------------------------------------------

    public static function get($db, int $id): ?array
    {
        try {
            return $db->query("SELECT * FROM tpd_supplier_account WHERE id = ?", [$id])->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** All accounts of a supplier, default first. */
    public static function forSupplier($db, string $acc): array
    {
        try {
            return $db->query("SELECT * FROM tpd_supplier_account WHERE sup_acc = ? ORDER BY is_default DESC, active DESC, id", [$acc])->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** The account a payment takes when none is chosen: the active default, else the only active account, else none. */
    public static function defaultFor($db, string $acc): ?array
    {
        $rows = array_values(array_filter(self::forSupplier($db, $acc), static fn ($r) => (int) $r['active'] === 1));
        foreach ($rows as $r) {
            if ((int) $r['is_default'] === 1) {
                return $r;
            }
        }
        return count($rows) === 1 ? $rows[0] : null;
    }

    /** Default (or only) account of many suppliers at once, for a list page. */
    public static function defaultsFor($db, array $accs): array
    {
        $out = [];
        foreach (array_unique(array_filter(array_map('strval', $accs))) as $a) {
            $out[$a] = self::defaultFor($db, $a);
        }
        return $out;
    }

    public static function isComplete(?array $a): bool
    {
        if (!$a) {
            return false;
        }
        foreach (self::REQUIRED as $k) {
            if (trim((string) ($a[$k] ?? '')) === '') {
                return false;
            }
        }
        return true;
    }

    /** What is missing, in words. */
    public static function missing(?array $a): array
    {
        if (!$a) {
            return ['no registered account'];
        }
        $m = [];
        foreach (['holder_name' => 'account holder', 'bank_name' => 'bank', 'account_no' => 'account number'] as $k => $w) {
            if (trim((string) ($a[$k] ?? '')) === '') {
                $m[] = $w;
            }
        }
        return $m;
    }

    public static function fingerprint(array $a): string
    {
        $n = static fn ($v) => strtolower(preg_replace('/\s+/', '', (string) $v));
        return sha1(implode('|', [$n($a['holder_name'] ?? ''), $n($a['bank_name'] ?? ''), $n($a['branch'] ?? ''), $n($a['account_no'] ?? ''), $n($a['currency'] ?? ''), $n($a['swift'] ?? '')]));
    }

    /** verified / changed (verified, then edited) / unverified / inactive. */
    public static function state(?array $a): string
    {
        if (!$a) {
            return 'missing';
        }
        if ((int) $a['active'] !== 1) {
            return 'inactive';
        }
        if (($a['status'] ?? '') === 'verified') {
            return ($a['verified_hash'] && $a['verified_hash'] !== self::fingerprint($a)) ? 'changed' : 'verified';
        }
        return 'unverified';
    }

    /** The registry row in the shape the payment screens read (SupplierBank::forPayment), the number masked for a sign-in that may not see it. */
    public static function present($db, string $acc, ?array $a, ?array $master = null): array
    {
        $master = $master ?? SupplierBank::masterRow($db, $acc);
        $full = Perms::bankDetails();
        $show = static fn ($no) => $full ? (string) $no : BankAccounts::mask((string) $no, 4);
        $state = self::state($a);
        return [
            'sup_acc' => $acc, 'supplier_name' => $master['Name'] ?? $acc,
            'account_id' => $a ? (int) $a['id'] : null, 'registered' => (bool) $a, 'complete' => self::isComplete($a), 'missing' => self::missing($a),
            'beneficiary_name' => (string) ($a['holder_name'] ?? ''), 'holder_name' => (string) ($a['holder_name'] ?? ''),
            'bank_name' => (string) ($a['bank_name'] ?? ''), 'branch' => (string) ($a['branch'] ?? ''),
            'account_no' => $a ? $show($a['account_no'] ?? '') : '', 'account_no_masked' => !$full,
            'currency' => (string) ($a['currency'] ?? 'LKR'), 'swift' => (string) ($a['swift'] ?? ''), 'label' => (string) ($a['label'] ?? ''),
            'is_default' => $a ? (int) $a['is_default'] === 1 : false, 'active' => $a ? (int) $a['active'] === 1 : false,
            'status' => $state === 'missing' ? 'unverified' : $state, 'state' => $state,
            'verified_by' => $a['verified_by'] ?? null, 'verified_at' => $a['verified_at'] ?? null, 'note' => $a['note'] ?? null,
            'accounts' => count(self::forSupplier($db, $acc)),
            'registry' => true,
        ];
    }

    // ---------------------------------------------------------------------
    //  Maintaining the registry (the master page; authorised users only — the caller checks the privilege)
    // ---------------------------------------------------------------------

    public static function listing($db, string $q = '', string $filter = '', int $limit = 1500): array
    {
        $where = [];
        $bind = [];
        // the vehicle numbers a driver is known by: the vehicle master (vehicle.owner = supplier id) and the supplier master's own v_num
        $hasV = false;
        try {
            $hasV = (bool) $db->query("SHOW TABLES LIKE 'vehicle'")->getResultArray();
        } catch (\Throwable $e) {
        }
        $hasSV = false;
        try {
            $hasSV = (bool) $db->query("SHOW COLUMNS FROM supplier LIKE 'v_num'")->getResultArray();
        } catch (\Throwable $e) {
        }
        $vehCol = $hasV ? "(SELECT GROUP_CONCAT(DISTINCT v.v_num ORDER BY v.v_num SEPARATOR ', ') FROM vehicle v WHERE v.owner = s.Sup_Acc_No AND v.v_num <> '')" : 'NULL';
        $vehExpr = "CONCAT_WS(', ', NULLIF({$vehCol}, '')" . ($hasSV ? ", NULLIF(s.v_num, '')" : '') . ")";
        if (trim($q) !== '') {
            $qq = '%' . trim($q) . '%';
            $qv = '%' . preg_replace('/[\s\-]+/', '', trim($q)) . '%';   // a vehicle number typed with or without spaces / dashes
            $where[] = "(s.Name LIKE ? OR s.Sup_Acc_No LIKE ? OR a.holder_name LIKE ? OR a.account_no LIKE ? OR REPLACE(REPLACE(COALESCE({$vehExpr}, ''), ' ', ''), '-', '') LIKE ?)";
            $bind = [$qq, $qq, $qq, $qq, $qv];
        }
        $sql = "SELECT s.Sup_Acc_No, s.Name, s.sup_cat, {$vehExpr} AS vehicles, a.* , a.id AS account_id FROM supplier s LEFT JOIN tpd_supplier_account a ON a.sup_acc = s.Sup_Acc_No"
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY s.Name, a.is_default DESC, a.id LIMIT " . (int) $limit;
        try {
            $rows = $db->query($sql, $bind)->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $k = (string) $r['Sup_Acc_No'];
            if (!isset($out[$k])) {
                $out[$k] = ['sup_acc' => $k, 'name' => $r['Name'], 'category' => self::category((int) $r['sup_cat']), 'vehicles' => (string) ($r['vehicles'] ?? ''), 'accounts' => []];
            }
            if ($r['account_id']) {
                $r['state'] = self::state($r);
                $r['complete'] = self::isComplete($r);
                $r['missing'] = self::missing($r);
                $out[$k]['accounts'][] = $r;
            }
        }
        if ($filter === 'missing') {
            $out = array_filter($out, static fn ($s) => !$s['accounts']);
        } elseif ($filter === 'incomplete') {
            $out = array_filter($out, static fn ($s) => $s['accounts'] && count(array_filter($s['accounts'], static fn ($a) => $a['complete'] && (int) $a['active'] === 1)) === 0);
        } elseif ($filter === 'unverified') {
            $out = array_filter($out, static fn ($s) => count(array_filter($s['accounts'], static fn ($a) => in_array($a['state'], ['unverified', 'changed'], true))) > 0);
        } elseif ($filter === 'registered') {
            $out = array_filter($out, static fn ($s) => (bool) $s['accounts']);
        }
        return array_values($out);
    }

    public static function category(int $cat): string
    {
        return [1 => 'Hotel', 2 => 'Driver / transport', 3 => 'Guide', 4 => 'Restaurant / activity'][$cat] ?? 'Other payee';
    }

    /** Add or edit an account. A change to the holder, bank, branch, number, currency or SWIFT of a verified account drops its verification. */
    public static function save($db, array $in, string $by): array
    {
        $acc = trim((string) ($in['sup_acc'] ?? ''));
        $m = SupplierBank::masterRow($db, $acc);
        if (!$m) {
            return ['ok' => false, 'message' => 'No supplier with id ' . $acc . ' in the supplier master. Register the supplier there first; the registry links accounts by that id.'];
        }
        $id = (int) ($in['id'] ?? 0);
        $prev = $id ? self::get($db, $id) : null;
        if ($id && (!$prev || (string) $prev['sup_acc'] !== $acc)) {
            return ['ok' => false, 'message' => 'Account not found for this supplier.'];
        }
        $row = [
            'sup_acc' => $acc,
            'holder_name' => self::clean($in['holder_name'] ?? '', 200),
            'bank_name' => self::clean($in['bank_name'] ?? '', 160),
            'branch' => self::clean($in['branch'] ?? '', 160),
            'account_no' => self::cleanNo($in['account_no'] ?? ''),
            'currency' => strtoupper(substr(trim((string) ($in['currency'] ?? 'LKR')), 0, 3)) ?: 'LKR',
            'swift' => strtoupper(self::clean($in['swift'] ?? '', 20) ?? '') ?: null,
            'label' => self::clean($in['label'] ?? '', 60),
            'note' => self::clean($in['note'] ?? '', 300),
            'updated_by' => $by, 'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!preg_match('/^[A-Z]{3}$/', $row['currency'])) {
            return ['ok' => false, 'message' => 'Currency must be a three-letter code (LKR, USD, …).'];
        }
        if ($row['account_no'] !== null && strlen($row['account_no']) < 4) {
            return ['ok' => false, 'message' => 'The account number is too short.'];
        }
        // the same number at the same bank for another supplier is almost always a mistake — refused, named
        if ($row['account_no']) {
            $dup = $db->query("SELECT a.id, a.sup_acc, s.Name FROM tpd_supplier_account a LEFT JOIN supplier s ON s.Sup_Acc_No = a.sup_acc WHERE a.account_no = ? AND a.sup_acc <> ? AND a.active = 1 LIMIT 1", [$row['account_no'], $acc])->getRowArray();
            if ($dup) {
                return ['ok' => false, 'message' => 'Account number ' . BankAccounts::mask($row['account_no'], 4) . ' is already registered for ' . $dup['Name'] . ' (' . $dup['sup_acc'] . '). One account belongs to one supplier.'];
            }
        }
        $core = static fn ($a) => [$a['holder_name'] ?? null, $a['bank_name'] ?? null, $a['branch'] ?? null, $a['account_no'] ?? null, $a['currency'] ?? null, $a['swift'] ?? null];
        if ($prev) {
            if (($prev['status'] ?? '') === 'verified' && $core($prev) !== $core($row)) {
                $row['status'] = 'unverified';
                $row['verified_by'] = null;
                $row['verified_at'] = null;
                $row['verified_hash'] = null;
            }
            $db->table('tpd_supplier_account')->where('id', $id)->update($row);
        } else {
            $row['status'] = 'unverified';
            $row['active'] = 1;
            $row['source'] = 'entered';
            $row['created_by'] = $by;
            $row['created_at'] = $row['updated_at'];
            $row['is_default'] = count(array_filter(self::forSupplier($db, $acc), static fn ($r) => (int) $r['active'] === 1)) === 0 ? 1 : 0;   // the first active account is the default
            $db->table('tpd_supplier_account')->insert($row);
            $id = (int) $db->insertID();
        }
        if (!empty($in['make_default'])) {
            self::setDefault($db, $id, $by, true);
        }
        $after = self::get($db, $id);
        Audit::log($db, ['action' => $prev ? 'supplier_account_edited' : 'supplier_account_added', 'user' => $by, 'ref_type' => 'supplier_account', 'ref_id' => $id,
            'prev' => $prev ? self::strip($prev) : null, 'new' => self::strip($after), 'reason' => 'Supplier ' . $acc . ' (' . ($m['Name'] ?? '') . ')']);
        $flagged = self::flagPayments($db, $after, $by);
        return ['ok' => true, 'id' => $id, 'message' => ($prev ? 'Account updated' : 'Account added') . (self::isComplete($after) ? '' : ' — incomplete: ' . implode(', ', self::missing($after)) . ' missing') . (($after['status'] ?? '') === 'verified' ? '.' : ' (not yet verified).')
            . ($flagged ? ' ' . $flagged . ' payment(s) selected this account earlier and are now flagged for renewed review.' : ''), 'account' => $after];
    }

    public static function setDefault($db, int $id, string $by, bool $quiet = false): array
    {
        $a = self::get($db, $id);
        if (!$a) {
            return ['ok' => false, 'message' => 'Account not found.'];
        }
        if ((int) $a['active'] !== 1) {
            return ['ok' => false, 'message' => 'An inactive account cannot be the default.'];
        }
        $db->query("UPDATE tpd_supplier_account SET is_default = 0, updated_by = ?, updated_at = ? WHERE sup_acc = ? AND is_default = 1", [$by, date('Y-m-d H:i:s'), $a['sup_acc']]);
        $db->query("UPDATE tpd_supplier_account SET is_default = 1, updated_by = ?, updated_at = ? WHERE id = ?", [$by, date('Y-m-d H:i:s'), $id]);
        if (!$quiet) {
            Audit::log($db, ['action' => 'supplier_account_default', 'user' => $by, 'ref_type' => 'supplier_account', 'ref_id' => $id, 'new' => ['sup_acc' => $a['sup_acc'], 'default' => $id]]);
        }
        return ['ok' => true, 'message' => 'Default account set.'];
    }

    public static function setActive($db, int $id, bool $active, string $by, string $reason = ''): array
    {
        $a = self::get($db, $id);
        if (!$a) {
            return ['ok' => false, 'message' => 'Account not found.'];
        }
        $upd = ['active' => $active ? 1 : 0, 'updated_by' => $by, 'updated_at' => date('Y-m-d H:i:s')];
        if (!$active) {
            $upd['is_default'] = 0;
        }
        $db->table('tpd_supplier_account')->where('id', $id)->update($upd);
        if (!$active && (int) $a['is_default'] === 1) {
            // another active account becomes the default when there is exactly one
            $others = array_values(array_filter(self::forSupplier($db, $a['sup_acc']), static fn ($r) => (int) $r['active'] === 1));
            if (count($others) === 1) {
                self::setDefault($db, (int) $others[0]['id'], $by, true);
            }
        }
        Audit::log($db, ['action' => $active ? 'supplier_account_activated' : 'supplier_account_deactivated', 'user' => $by, 'ref_type' => 'supplier_account', 'ref_id' => $id, 'prev' => ['active' => (int) $a['active']], 'new' => ['active' => $active ? 1 : 0], 'reason' => $reason]);
        $flagged = $active ? 0 : self::flagPayments($db, self::get($db, $id), $by);
        return ['ok' => true, 'message' => ($active ? 'Account activated.' : 'Account deactivated.') . ($flagged ? ' ' . $flagged . ' payment(s) that selected it are flagged for renewed review.' : '')];
    }

    /** Verification by a holder of the privilege: the details as they stand are fingerprinted. */
    /** tf-stage5: who entered or last changed the account's details (the audit's last add/edit line; else the row's own columns). */
    public static function enteredBy($db, int $id): ?string
    {
        try {
            $r = $db->query("SELECT `user` FROM tpd_audit WHERE ref_type = 'supplier_account' AND ref_id = ? AND action IN ('supplier_account_added', 'supplier_account_edited') ORDER BY id DESC LIMIT 1", [(string) $id])->getRowArray();
            if ($r && trim((string) $r['user']) !== '') {
                return (string) $r['user'];
            }
        } catch (\Throwable $e) {
        }
        $a = self::get($db, $id);
        return $a ? (string) ($a['updated_by'] ?: $a['created_by'] ?: '') : null;
    }

    /** tf-stage5: the same person may not enter and verify. */
    public static function selfVerifyRefusal($db, int $id, string $by): ?string
    {
        $who = self::enteredBy($db, $id);
        if ($who !== null && $who !== '' && strcasecmp(trim($who), trim($by)) === 0) {
            return 'These details were entered or last changed by you (' . $by . '). Verification has to be done by another person — a Finance & Approvals Management Approver — against the bank\'s own document.';
        }
        return null;
    }

    /**
     * tf-stage5: the release gate. A bank transfer goes out only to an account that is verified as it stands now:
     * status verified and the details unchanged since (a change after verification = verify again). Returns the refusal, or null.
     */
    public static function releaseRefusal($db, ?array $a): ?string
    {
        $state = self::state($a);
        if ($state === 'verified') {
            return null;
        }
        $what = $a ? trim(($a['bank_name'] ?: 'bank not named') . ' · ' . ($a['holder_name'] ?: 'holder not named') . ' · ' . ($a['account_no'] ? '…' . substr(preg_replace('/\s+/', '', (string) $a['account_no']), -4) : 'no account number')) : 'no registered account';
        $why = ['missing' => 'no registered account is carried with this payment', 'inactive' => 'the account is deactivated', 'incomplete' => 'the account is incomplete',
                'changed' => 'the account\'s details were changed after it was verified', 'unverified' => 'the account has not been verified'][$state] ?? 'the account is not verified';
        return 'Not released: ' . $why . ' (' . $what . '). A Finance & Approvals Management Approver (not the person who entered the details) verifies this exact account in the Supplier Bank Account Registry; then the transfer can be initiated. Approval stands; nothing was written.';
    }

    public static function verify($db, int $id, string $by, string $note = ''): array
    {
        $a = self::get($db, $id);
        if (!$a) {
            return ['ok' => false, 'message' => 'Account not found.'];
        }
        if ($why = self::selfVerifyRefusal($db, $id, $by)) {   // tf-stage5
            return ['ok' => false, 'message' => $why];
        }
        if (!self::isComplete($a)) {
            return ['ok' => false, 'message' => 'The account is incomplete (' . implode(', ', self::missing($a)) . ' missing); complete it before verifying.'];
        }
        $db->table('tpd_supplier_account')->where('id', $id)->update(['status' => 'verified', 'verified_by' => $by, 'verified_at' => date('Y-m-d H:i:s'), 'verified_hash' => self::fingerprint($a), 'note' => trim($note) !== '' ? mb_substr(trim($note), 0, 300) : $a['note'], 'updated_by' => $by, 'updated_at' => date('Y-m-d H:i:s')]);
        Audit::log($db, ['action' => 'supplier_account_verified', 'user' => $by, 'ref_type' => 'supplier_account', 'ref_id' => $id, 'prev' => self::strip($a), 'new' => self::strip(self::get($db, $id)), 'reason' => $note]);
        return ['ok' => true, 'message' => 'Verified by ' . $by . '.'];
    }

    /** The registry's change history (tpd_audit), newest first. */
    public static function history($db, ?string $supAcc = null, int $limit = 200): array
    {
        try {
            if ($supAcc !== null && $supAcc !== '') {
                $ids = array_column(self::forSupplier($db, $supAcc), 'id');
                if (!$ids) {
                    return [];
                }
                $in = implode(',', array_map('intval', $ids));
                return $db->query("SELECT * FROM tpd_audit WHERE ref_type = 'supplier_account' AND ref_id IN ({$in}) ORDER BY at DESC, id DESC LIMIT " . (int) $limit)->getResultArray();
            }
            return $db->query("SELECT * FROM tpd_audit WHERE ref_type IN ('supplier_account', 'payment_account', 'registry') ORDER BY at DESC, id DESC LIMIT " . (int) $limit)->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ---------------------------------------------------------------------
    //  The account carried by a payment
    // ---------------------------------------------------------------------

    public static function snapshot($db, string $refType, $refId): ?array
    {
        try {
            return $db->query("SELECT * FROM tpd_payment_account WHERE ref_type = ? AND ref_id = ?", [$refType, (string) $refId])->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * The account a request pays to: the registry account given (an explicit selection) or the supplier's default, copied in
     * full. Called at approval (default) and on an explicit selection; an existing selection is kept unless $accountId is given.
     */
    public static function attachRequest($db, int $prId, string $by, ?int $accountId = null, string $reason = 'Selected at approval'): ?array
    {
        if (!self::installed($db)) {
            return null;
        }
        $r = $db->query("SELECT id, hotel_acc, hotel_name, tour_id, manual_id, payment_request FROM payment_request WHERE id = ?", [$prId])->getRowArray();
        if (!$r) {
            return null;
        }
        $acc = (string) $r['hotel_acc'];
        $have = self::snapshot($db, 'request', $prId);
        if ($have && $accountId === null) {
            return $have;
        }
        $a = $accountId !== null ? self::get($db, $accountId) : self::defaultFor($db, $acc);
        if ($accountId !== null && (!$a || (string) $a['sup_acc'] !== $acc)) {
            return null;   // an account of another supplier is never attached
        }
        $row = self::rowFrom($acc, $a, $accountId !== null ? 'reselected' : 'approval', $by, $reason);
        $row['ref_type'] = 'request';
        $row['ref_id'] = (string) $prId;
        $row['supplier_name'] = SupplierBank::masterRow($db, $acc)['Name'] ?? $r['hotel_name'];
        if ($have) {
            $db->table('tpd_payment_account')->where('id', $have['id'])->update($row);
        } else {
            $db->table('tpd_payment_account')->insert($row);
        }
        $new = self::snapshot($db, 'request', $prId);
        Audit::log($db, ['action' => $have ? 'payment_account_reselected' : 'payment_account_selected', 'user' => $by, 'tour_id' => $r['tour_id'], 'manual_id' => $r['manual_id'], 'ref_type' => 'request', 'ref_id' => $prId,
            'prev' => $have ? self::stripSnap($have) : null, 'new' => self::stripSnap($new), 'reason' => $reason]);
        return $new;
    }

    /** The batch and its voucher carry the requests' account (one account per batch — prepare() refuses a mix). */
    public static function attachBatch($db, int $batchId, ?int $vnum, string $supAcc, ?array $fromRequestSnap, string $by): void
    {
        if (!self::installed($db)) {
            return;
        }
        $a = $fromRequestSnap && $fromRequestSnap['account_id'] ? self::get($db, (int) $fromRequestSnap['account_id']) : self::defaultFor($db, $supAcc);
        $row = $fromRequestSnap ? self::copySnap($fromRequestSnap) : self::rowFrom($supAcc, $a, 'batch', $by, 'Carried into the batch');
        $row['stage'] = 'batch';
        $row['selected_by'] = $by;
        $row['selected_at'] = date('Y-m-d H:i:s');
        $row['reason'] = $fromRequestSnap ? 'Carried from the approved request' : 'Default account at batch creation';
        foreach ([['batch', (string) $batchId], ['voucher', $vnum ? (string) $vnum : null]] as [$t, $id]) {
            if ($id === null || self::snapshot($db, $t, $id)) {
                continue;
            }
            $db->table('tpd_payment_account')->insert($row + ['ref_type' => $t, 'ref_id' => $id]);
        }
        Audit::log($db, ['action' => 'payment_account_carried', 'user' => $by, 'ref_type' => 'batch', 'ref_id' => $batchId, 'vnum' => $vnum, 'new' => self::stripSnap($row)]);
    }

    /**
     * The state of a carried account against the registry now: ok · changed (the registry row was edited since) · inactive ·
     * incomplete · missing (no registered account when selected) · reviewed (changed, then accepted by a reviewer).
     */
    public static function review($db, ?array $snap): array
    {
        if (!$snap) {
            return ['state' => 'missing', 'label' => 'No registered account', 'blocking' => true, 'detail' => 'No account was carried with this payment. Register the supplier\'s account and select it.'];
        }
        if (!$snap['account_id'] || !(int) $snap['complete']) {
            $miss = self::missing(['holder_name' => $snap['holder_name'], 'bank_name' => $snap['bank_name'], 'account_no' => $snap['account_no']]);
            return ['state' => $snap['account_id'] ? 'incomplete' : 'missing', 'label' => $snap['account_id'] ? 'Incomplete account' : 'No registered account', 'blocking' => true,
                'detail' => ($snap['account_id'] ? 'The selected account is incomplete: ' : 'No registered account at selection: ') . implode(', ', $miss ?: ['details']) . ' missing. Complete it in the registry and select it again.'];
        }
        $cur = self::get($db, (int) $snap['account_id']);
        if (!$cur) {
            return ['state' => 'deleted', 'label' => 'Account removed from the registry', 'blocking' => true, 'detail' => 'The selected account no longer exists in the registry. Select another account.'];
        }
        $fp = self::fingerprint($cur);
        if ((int) $cur['active'] !== 1) {
            return ['state' => 'inactive', 'label' => 'Account deactivated since selection', 'blocking' => true, 'detail' => 'The selected account was deactivated after this payment was approved. Select another account or reactivate it; either way a reviewer must confirm.'];
        }
        if ($fp !== $snap['fingerprint']) {
            if ($snap['reviewed_hash'] && $snap['reviewed_hash'] === $fp) {
                return ['state' => 'reviewed', 'label' => 'Registry changed after approval — reviewed, paying the account as approved', 'blocking' => false, 'detail' => 'Reviewed by ' . $snap['reviewed_by'] . ' at ' . $snap['reviewed_at'] . ($snap['review_note'] ? ': ' . $snap['review_note'] : '')];
            }
            $diff = [];
            foreach (['holder_name' => 'holder', 'bank_name' => 'bank', 'branch' => 'branch', 'account_no' => 'account number', 'currency' => 'currency', 'swift' => 'SWIFT'] as $k => $w) {
                if (strtolower(preg_replace('/\s+/', '', (string) $cur[$k])) !== strtolower(preg_replace('/\s+/', '', (string) $snap[$k]))) {
                    $diff[] = $w;
                }
            }
            return ['state' => 'changed', 'label' => 'Registry changed after approval — renewed review required', 'blocking' => true, 'detail' => 'The registry\'s ' . implode(', ', $diff) . ' differ' . (count($diff) === 1 ? 's' : '') . ' from the account approved. A reviewer keeps the approved account or takes the new details, with a reason.'];
        }
        return ['state' => 'ok', 'label' => ($cur['status'] === 'verified' ? 'Verified' : 'Registered, not verified'), 'blocking' => false, 'detail' => ''];
    }

    /** Before a bank transfer: the voucher's carried account must be complete and not awaiting review. Returns the refusal, or null. */
    public static function initiationCheck($db, int $vnum): ?array
    {
        if (!self::installed($db)) {
            return null;
        }
        $snap = self::snapshot($db, 'voucher', $vnum);
        // tf-stage8: a block on the payee or on the exact bank account carried refuses the release, re-checked here every time
        try {
            $vrow = $db->query('SELECT Acc_No FROM voucher WHERE Vnum = ? LIMIT 1', [$vnum])->getRowArray();
            $why = \App\Libraries\TourFlow\Blocks::paymentRefusal($db, (string) ($vrow['Acc_No'] ?? ($snap['sup_acc'] ?? '')), !empty($snap['account_id']) ? (int) $snap['account_id'] : null, 'bank release (initiation)', Perms::user(), 'voucher ' . $vnum, $snap['account_no'] ?? null, $snap['bank_name'] ?? null);
            if ($why !== null) {
                return ['message' => $why, 'review' => ['state' => 'blocked', 'label' => 'Blocked', 'blocking' => true, 'detail' => $why], 'account' => $snap ? self::stripSnap($snap) : null];
            }
        } catch (\Throwable $e) {
        }
        if (!$snap) {
            // a voucher issued before the registry, or outside a batch: fall back to the voucher's supplier default so older payments are not stuck
            $v = $db->query("SELECT Acc_No FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
            $a = $v ? self::defaultFor($db, (string) $v['Acc_No']) : null;
            if ($a && self::isComplete($a)) {
                // tf-stage5: even an older voucher goes out only to a verified account
                if ($why = self::releaseRefusal($db, $a)) {
                    return ['message' => $why, 'review' => ['state' => self::state($a), 'label' => 'Not verified', 'blocking' => true, 'detail' => $why], 'account' => null];
                }
                return null;
            }
            return ['message' => 'No complete registered bank account for this payee (' . implode(', ', self::missing($a)) . '). Register it in the Supplier Bank Account Registry before the transfer is initiated.', 'review' => self::review($db, null)];
        }
        $rv = self::review($db, $snap);
        if ($rv['blocking']) {
            return ['message' => 'The bank transfer is not initiated: ' . $rv['label'] . '. ' . $rv['detail'], 'review' => $rv, 'account' => self::stripSnap($snap)];
        }
        // tf-stage5: approval may stand on an unverified account; the release may not — the exact account carried must be verified as it stands now
        $cur = !empty($snap['account_id']) ? self::get($db, (int) $snap['account_id']) : null;
        if ($why = self::releaseRefusal($db, $cur)) {
            return ['message' => $why, 'review' => ['state' => self::state($cur), 'label' => 'Account not verified', 'blocking' => true, 'detail' => $why], 'account' => self::stripSnap($snap)];
        }
        return null;
    }

    /** A reviewer's decision on a flagged payment: keep the approved account, or take the registry's current details (reason compulsory). */
    public static function reviewDecision($db, string $refType, $refId, string $decision, string $by, string $note): array
    {
        $snap = self::snapshot($db, $refType, $refId);
        if (!$snap) {
            return ['ok' => false, 'message' => 'No carried account for this payment.'];
        }
        if (trim($note) === '') {
            return ['ok' => false, 'message' => 'Give the reason for the decision.'];
        }
        $cur = $snap['account_id'] ? self::get($db, (int) $snap['account_id']) : null;
        $prev = self::stripSnap($snap);
        $now = date('Y-m-d H:i:s');
        if ($decision === 'keep') {
            if (!$cur) {
                return ['ok' => false, 'message' => 'The account no longer exists; select another account instead.'];
            }
            $db->table('tpd_payment_account')->where('id', $snap['id'])->update(['reviewed_by' => $by, 'reviewed_at' => $now, 'review_note' => mb_substr($note, 0, 300), 'reviewed_hash' => self::fingerprint($cur)]);
        } elseif ($decision === 'update') {
            if (!$cur || (int) $cur['active'] !== 1) {
                return ['ok' => false, 'message' => 'The registry account is inactive or gone; select another account.'];
            }
            if (!self::isComplete($cur)) {
                return ['ok' => false, 'message' => 'The registry account is incomplete (' . implode(', ', self::missing($cur)) . ').'];
            }
            // tf-stage10: replacement details are taken for a payment only by someone independent of them — not the person who
            // entered them and not the person who verified them; the release gate then still needs the account verified.
            foreach ([(string) self::enteredBy($db, (int) $cur['id']) => 'entered', (string) ($cur['verified_by'] ?? '') => 'verified'] as $who => $what) {
                if (trim($who) !== '' && strcasecmp(trim($who), trim($by)) === 0) {
                    return ['ok' => false, 'message' => 'These replacement details were ' . $what . ' by you (' . $by . '). Taking them for the payment has to be done by another person, so that entry, verification and the decision stay independent. Nothing was written.'];
                }
            }
            $row = self::rowFrom($snap['sup_acc'], $cur, 'reselected', $by, 'Review: ' . $note);
            unset($row['ref_type'], $row['ref_id']);
            $row['reviewed_by'] = $by;
            $row['reviewed_at'] = $now;
            $row['review_note'] = mb_substr($note, 0, 300);
            $row['reviewed_hash'] = $row['fingerprint'];
            $db->table('tpd_payment_account')->where('id', $snap['id'])->update($row);
        } else {
            return ['ok' => false, 'message' => 'Decision must be keep or update.'];
        }
        // the voucher and batch of a request (and the requests of a batch) take the same decision
        foreach (self::related($db, $refType, $refId) as [$t, $id]) {
            $s = self::snapshot($db, $t, $id);
            if (!$s) {
                continue;
            }
            if ($decision === 'keep') {
                $db->table('tpd_payment_account')->where('id', $s['id'])->update(['reviewed_by' => $by, 'reviewed_at' => $now, 'review_note' => mb_substr($note, 0, 300), 'reviewed_hash' => self::fingerprint($cur)]);
            } else {
                $row2 = $row;
                $db->table('tpd_payment_account')->where('id', $s['id'])->update($row2);
            }
        }
        Audit::log($db, ['action' => 'payment_account_reviewed', 'user' => $by, 'ref_type' => $refType, 'ref_id' => $refId, 'vnum' => $refType === 'voucher' ? (int) $refId : null, 'prev' => $prev, 'new' => self::stripSnap(self::snapshot($db, $refType, $refId)) + ['decision' => $decision], 'reason' => $note]);
        return ['ok' => true, 'message' => $decision === 'keep' ? 'Kept the account as approved; reviewed.' : 'Taken the registry\'s current details; reviewed.'];
    }

    /** The payments that selected a registry account and would now differ from it — their count (they show as flagged). */
    private static function flagPayments($db, ?array $a, string $by): int
    {
        if (!$a) {
            return 0;
        }
        try {
            $fp = self::fingerprint($a);
            $rows = $db->query("SELECT ref_type, ref_id, fingerprint, reviewed_hash FROM tpd_payment_account WHERE account_id = ?", [(int) $a['id']])->getResultArray();
            $n = 0;
            foreach ($rows as $r) {
                if (($r['fingerprint'] !== $fp || (int) $a['active'] !== 1) && $r['reviewed_hash'] !== $fp && $r['ref_type'] === 'voucher') {
                    $paid = $db->query("SELECT finalized FROM voucher WHERE Vnum = ? LIMIT 1", [(int) $r['ref_id']])->getRowArray();
                    if (!$paid || (int) $paid['finalized'] !== 1) {
                        $n++;
                    }
                }
            }
            if ($n) {
                Audit::log($db, ['action' => 'payment_account_flagged', 'user' => $by, 'ref_type' => 'supplier_account', 'ref_id' => $a['id'], 'new' => ['open_payments_flagged' => $n]]);
            }
            return $n;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function related($db, string $refType, $refId): array
    {
        $out = [];
        try {
            if ($refType === 'voucher') {
                $b = $db->query("SELECT id FROM tpd_batch WHERE vnum = ? LIMIT 1", [(int) $refId])->getRowArray();
                if ($b) {
                    $out[] = ['batch', (string) $b['id']];
                    foreach ($db->query("SELECT pr_id FROM tpd_request_ext WHERE batch_id = ?", [(int) $b['id']])->getResultArray() as $x) {
                        $out[] = ['request', (string) $x['pr_id']];
                    }
                }
            } elseif ($refType === 'batch') {
                $b = $db->query("SELECT vnum FROM tpd_batch WHERE id = ? LIMIT 1", [(int) $refId])->getRowArray();
                if ($b && $b['vnum']) {
                    $out[] = ['voucher', (string) $b['vnum']];
                }
                foreach ($db->query("SELECT pr_id FROM tpd_request_ext WHERE batch_id = ?", [(int) $refId])->getResultArray() as $x) {
                    $out[] = ['request', (string) $x['pr_id']];
                }
            } elseif ($refType === 'request') {
                $x = $db->query("SELECT batch_id, voucher_vnum FROM tpd_request_ext WHERE pr_id = ? LIMIT 1", [(int) $refId])->getRowArray();
                if ($x && $x['batch_id']) {
                    $out[] = ['batch', (string) $x['batch_id']];
                }
                if ($x && $x['voucher_vnum']) {
                    $out[] = ['voucher', (string) $x['voucher_vnum']];
                }
            }
        } catch (\Throwable $e) {
        }
        return $out;
    }

    /** The carried account for display: masked number unless the sign-in may see it, plus the review state. */
    public static function display($db, ?array $snap): ?array
    {
        if (!$snap) {
            return null;
        }
        $full = Perms::bankDetails();
        $rv = self::review($db, $snap);
        return ['account_id' => $snap['account_id'] ? (int) $snap['account_id'] : null, 'supplier_name' => $snap['supplier_name'], 'holder_name' => $snap['holder_name'], 'bank_name' => $snap['bank_name'], 'branch' => $snap['branch'],
            'account_no' => $full ? (string) $snap['account_no'] : BankAccounts::mask((string) $snap['account_no'], 4), 'account_no_masked' => !$full, 'currency' => $snap['currency'], 'swift' => $snap['swift'],
            'complete' => (int) $snap['complete'] === 1, 'stage' => $snap['stage'], 'selected_by' => $snap['selected_by'], 'selected_at' => $snap['selected_at'],
            'reviewed_by' => $snap['reviewed_by'], 'reviewed_at' => $snap['reviewed_at'], 'review' => $rv];
    }

    /** Snapshots of many vouchers at once (Bank Payment Check), for display. */
    public static function displayForVouchers($db, array $vnums): array
    {
        $out = [];
        if (!self::installed($db) || !$vnums) {
            return $out;
        }
        $in = implode(',', array_map(static fn ($v) => $db->escape((string) $v), array_unique($vnums)));
        try {
            foreach ($db->query("SELECT * FROM tpd_payment_account WHERE ref_type = 'voucher' AND ref_id IN ({$in})")->getResultArray() as $s) {
                $out[(string) $s['ref_id']] = self::display($db, $s);
            }
        } catch (\Throwable $e) {
        }
        return $out;
    }

    private static function rowFrom(string $acc, ?array $a, string $stage, string $by, string $reason): array
    {
        return ['sup_acc' => $acc, 'account_id' => $a ? (int) $a['id'] : null, 'supplier_name' => null,
            'holder_name' => $a['holder_name'] ?? null, 'bank_name' => $a['bank_name'] ?? null, 'branch' => $a['branch'] ?? null, 'account_no' => $a['account_no'] ?? null,
            'currency' => $a['currency'] ?? null, 'swift' => $a['swift'] ?? null, 'fingerprint' => $a ? self::fingerprint($a) : null, 'complete' => self::isComplete($a) ? 1 : 0,
            'stage' => $stage, 'selected_by' => $by, 'selected_at' => date('Y-m-d H:i:s'), 'reason' => mb_substr($reason, 0, 300),
            'reviewed_by' => null, 'reviewed_at' => null, 'review_note' => null, 'reviewed_hash' => null];
    }

    private static function copySnap(array $s): array
    {
        $keep = ['sup_acc', 'account_id', 'supplier_name', 'holder_name', 'bank_name', 'branch', 'account_no', 'currency', 'swift', 'fingerprint', 'complete', 'reviewed_by', 'reviewed_at', 'review_note', 'reviewed_hash'];
        return array_intersect_key($s, array_flip($keep));
    }

    private static function strip(?array $a): ?array
    {
        if (!$a) {
            return null;
        }
        $o = array_intersect_key($a, array_flip(['id', 'sup_acc', 'holder_name', 'bank_name', 'branch', 'account_no', 'currency', 'swift', 'label', 'is_default', 'active', 'status', 'note']));
        if (!empty($o['account_no'])) {
            $o['account_no'] = BankAccounts::mask($o['account_no'], 4);
        }
        return $o;
    }

    private static function stripSnap(?array $s): ?array
    {
        if (!$s) {
            return null;
        }
        $o = array_intersect_key($s, array_flip(['account_id', 'sup_acc', 'holder_name', 'bank_name', 'branch', 'account_no', 'currency', 'stage', 'complete', 'reviewed_by']));
        if (!empty($o['account_no'])) {
            $o['account_no'] = BankAccounts::mask($o['account_no'], 4);
        }
        return $o;
    }

    private static function clean($v, int $len): ?string
    {
        $v = trim(preg_replace('/\s+/', ' ', (string) $v));
        return $v === '' ? null : mb_substr($v, 0, $len);
    }

    private static function cleanNo($v): ?string
    {
        $v = preg_replace('/[^0-9A-Za-z\-]/', '', (string) $v);
        return $v === '' ? null : substr($v, 0, 60);
    }
}
