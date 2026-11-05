<?php

namespace App\Libraries\TourFlow;

/**
 * tf-stage8 (30 Oct 2026) — Blocks & Investigations (schema 56).
 *
 * A block is put on one exact record — a supplier (a hotel's or a restaurant's ledger account, a driver or guide), a hotel,
 * a customer or agent, or one registered bank account — by the Super Admin alone, with the reason, a reference and the
 * date, and is lifted only by the Super Admin with a reason. Three kinds: "work" (no new work: the record cannot be
 * put on an inquiry, a quotation cannot be produced while it is on one), "payments" (no payment to it is approved,
 * batched, bulked, initiated or marked paid; balances stay as they are), or "both" (suspended pending investigation).
 * Every route checks the same table; the release re-checks; staff cannot override. Notes are restricted to holders of
 * the right. Nothing spreads through loosely connected records: a hotel block holds that hotel and its own ledger
 * account; an account block holds that one account (the supplier's other accounts are untouched).
 */
final class Blocks
{
    public const URL = 'master_files/blocks';
    public const KINDS = ['work' => 'No new work', 'payments' => 'Payments held', 'both' => 'Suspended pending investigation'];
    public const TYPES = ['supplier' => 'Supplier / driver / guide', 'hotel' => 'Hotel', 'customer' => 'Customer / agent', 'account' => 'Bank account'];

    private static ?bool $installed = null;

    /** A bank account number as compared: digits and letters only, upper case. */
    public static function normNo(?string $no): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $no));
    }

    /** A bank name as compared: letters and digits only, lower case, common words dropped. */
    public static function normBank(?string $bank): string
    {
        $b = strtolower(preg_replace('/[^A-Za-z0-9 ]/', ' ', (string) $bank));
        $b = preg_replace('/\b(bank|plc|ltd|limited|the|of|ceylon|sri|lanka)\b/', ' ', $b);
        return preg_replace('/\s+/', '', $b);
    }

    public static function installed($db): bool
    {
        if (self::$installed === null) {
            try {
                self::$installed = (bool) $db->query("SHOW TABLES LIKE 'tf_block'")->getResultArray();
            } catch (\Throwable $e) {
                self::$installed = false;
            }
        }
        return self::$installed;
    }

    // ------------------------------------------------------------------ reading

    public static function active($db): array
    {
        if (!self::installed($db)) {
            return [];
        }
        return $db->query("SELECT * FROM tf_block WHERE status = 'active' ORDER BY set_at DESC")->getResultArray();
    }

    public static function all($db, int $limit = 200): array
    {
        if (!self::installed($db)) {
            return [];
        }
        return $db->query("SELECT * FROM tf_block ORDER BY (status = 'active') DESC, set_at DESC LIMIT " . (int) $limit)->getResultArray();
    }

    public static function get($db, int $id): ?array
    {
        $b = $db->query('SELECT * FROM tf_block WHERE id = ?', [$id])->getRowArray();
        if ($b) {
            $b['log'] = $db->query('SELECT * FROM tf_block_log WHERE block_id = ? ORDER BY id', [$id])->getResultArray();
        }
        return $b ?: null;
    }

    /** The one line a refusal shows for a block. */
    public static function words(array $b): string
    {
        return self::KINDS[$b['kind']] . ' — ' . self::TYPES[$b['target_type']] . ' ' . $b['target_label'] . ' (blocked by ' . ($b['set_by_name'] ?: $b['set_by']) . ' on ' . date('j M Y', strtotime($b['set_at'])) . ($b['reference'] ? ', ref ' . $b['reference'] : '') . '). Reason: ' . $b['reason'];
    }

    /**
     * The active blocks that hold a payment to a supplier account and/or a registered bank account:
     * the supplier itself, a hotel whose ledger account it is, or that one bank account.
     */
    public static function paymentBlocks($db, ?string $supAcc, ?int $accountId, ?string $accountNo = null, ?string $bankName = null): array
    {
        if (!self::installed($db)) {
            return [];
        }
        $out = [];
        $supAcc = trim((string) $supAcc);
        if ($supAcc !== '') {
            $out = $db->query("SELECT * FROM tf_block WHERE status = 'active' AND kind IN ('payments','both') AND ((target_type IN ('supplier','hotel') AND sup_acc = ?) OR (target_type = 'supplier' AND target_key = ?))", [$supAcc, $supAcc])->getResultArray();
        }
        // the bank account itself: by its number (and bank, when the block names one), wherever it is registered
        if ($accountId && ($accountNo === null || $accountNo === '')) {
            $a = $db->query('SELECT account_no, bank_name FROM tpd_supplier_account WHERE id = ?', [(int) $accountId])->getRowArray();
            if ($a) {
                $accountNo = $a['account_no'];
                $bankName = $bankName ?? $a['bank_name'];
            }
        }
        $no = self::normNo($accountNo);
        if ($no !== '') {
            foreach ($db->query("SELECT * FROM tf_block WHERE status = 'active' AND kind IN ('payments','both') AND target_type = 'account' AND acct_no_norm = ?", [$no])->getResultArray() as $b) {
                // a block that names the bank holds only the same bank's account of that number; one without a bank holds the number anywhere
                if ((string) $b['bank_norm'] === '' || $bankName === null || $bankName === '' || $b['bank_norm'] === self::normBank($bankName)) {
                    $out[] = $b;
                }
            }
        }
        return $out;
    }

    /** The refusal text for a payment step, or null. $step is written to the block's log. */
    public static function paymentRefusal($db, ?string $supAcc, ?int $accountId, string $step, string $user, $ref = null, ?string $accountNo = null, ?string $bankName = null): ?string
    {
        $bs = self::paymentBlocks($db, $supAcc, $accountId, $accountNo, $bankName);
        if ($bs === []) {
            return null;
        }
        foreach ($bs as $b) {
            self::log($db, (int) $b['id'], 'refused', $user, null, mb_substr($step . ($ref !== null ? ' ' . $ref : '') . ' — refused for ' . ($supAcc ?: '') . ($accountId ? ' account #' . $accountId : '') . ($accountNo ? ' …' . substr(self::normNo($accountNo), -4) : '') . ' · reason on the block: ' . $b['reason'], 0, 255));
        }
        return 'Blocked: ' . implode(' | ', array_map([self::class, 'words'], $bs)) . ' No payment to this payee is approved, batched or released until the block is lifted by the Super Admin. Balances are unchanged; nothing was written.';
    }

    /** The active "no new work" blocks on any of the targets given: ['supplier' => acc, 'hotel' => id, 'customer' => acc]. */
    public static function workBlocks($db, array $targets): array
    {
        if (!self::installed($db)) {
            return [];
        }
        $out = [];
        foreach ($targets as $type => $key) {
            $key = trim((string) $key);
            if ($key === '' || !isset(self::TYPES[$type])) {
                continue;
            }
            foreach ($db->query("SELECT * FROM tf_block WHERE status = 'active' AND kind IN ('work','both') AND target_type = ? AND target_key = ?", [$type, $key])->getResultArray() as $b) {
                $out[(int) $b['id']] = $b;
            }
        }
        return array_values($out);
    }

    /** "No new work" blocks on a ledger account: the supplier itself, or a hotel whose ledger account it is. */
    public static function workBlocksForSupplierAcc($db, ?string $acc): array
    {
        $acc = trim((string) $acc);
        if ($acc === '' || !self::installed($db)) {
            return [];
        }
        return $db->query("SELECT * FROM tf_block WHERE status = 'active' AND kind IN ('work','both') AND ((target_type = 'supplier' AND target_key = ?) OR (target_type = 'hotel' AND sup_acc = ?))", [$acc, $acc])->getResultArray();
    }

    public static function workRefusalForSupplierAcc($db, ?string $acc, string $step, string $user): ?string
    {
        $bs = self::workBlocksForSupplierAcc($db, $acc);
        if ($bs === []) {
            return null;
        }
        foreach ($bs as $b) {
            self::log($db, (int) $b['id'], 'refused', $user, null, mb_substr($step, 0, 255));
        }
        return 'Blocked: ' . implode(' | ', array_map([self::class, 'words'], $bs)) . ' No new work with this record until the block is lifted.';
    }

    public static function workRefusal($db, array $targets, string $step, string $user): ?string
    {
        $bs = self::workBlocks($db, $targets);
        if ($bs === []) {
            return null;
        }
        foreach ($bs as $b) {
            self::log($db, (int) $b['id'], 'refused', $user, null, mb_substr($step, 0, 255));
        }
        return 'Blocked: ' . implode(' | ', array_map([self::class, 'words'], $bs)) . ' No new work with this record until the block is lifted by the Super Admin.';
    }

    /** The blocks touching an inquiry as get() read it: its hotels (by night), its agent, its crew (by supplier account). */
    public static function forInquiry($db, array $it): array
    {
        if (!self::installed($db)) {
            return [];
        }
        $targets = [];
        foreach (($it['days'] ?? []) as $d) {
            if (!empty($d['hotel_id'])) {
                $targets[] = ['hotel', (string) (int) $d['hotel_id'], 'Night ' . (int) $d['day_no'] . ' · ' . ($d['hotel_name_raw'] ?? '')];
            }
        }
        if (!empty($it['agent_acc'])) {
            $targets[] = ['customer', (string) $it['agent_acc'], 'Agent ' . ($it['agent_name'] ?? '')];
        }
        foreach (($it['crew'] ?? []) as $c) {
            if (!empty($c['supplier_acc'])) {
                $targets[] = ['supplier', (string) $c['supplier_acc'], ($c['role_name'] ?? 'Crew') . ' ' . ($c['person_name'] ?? '')];
            }
        }
        $out = [];
        foreach ($targets as [$type, $key, $where]) {
            $rows = $db->query("SELECT * FROM tf_block WHERE status = 'active' AND target_type = ? AND target_key = ?", [$type, $key])->getResultArray();
            foreach ($rows as $b) {
                // "no new work" and "suspended" stop the quotation; "payments held" lets it through with a warning
                $out[] = ['where' => $where, 'block' => $b, 'stops' => in_array($b['kind'], ['work', 'both'], true)];
            }
        }
        return $out;
    }

    /** The ledger accounts of a place's operators (jeep operator, ticket supplier): tf_place_party → supplier. */
    public static function placeSupplierAccs($db, int $placeId): array
    {
        try {
            $rows = $db->query('SELECT DISTINCT s.Sup_Acc_No acc FROM tf_place_party p JOIN supplier s ON s.ID = p.supplier_id WHERE p.place_id = ? AND s.Sup_Acc_No IS NOT NULL', [$placeId])->getResultArray();
            return array_values(array_filter(array_map(static fn ($r) => trim((string) $r['acc']), $rows)));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** The ledger accounts behind a service job on a tour: the place's operators, the meal's restaurant. */
    public static function serviceJobSupplierAccs($db, array $job): array
    {
        $out = [];
        if (!empty($job['place_id'])) {
            $out = self::placeSupplierAccs($db, (int) $job['place_id']);
        }
        try {
            if (!empty($job['source_id'])) {
                $m = $db->query('SELECT m.supplier_acc FROM tf_intake_service s JOIN tf_meal m ON m.id = s.meal_id WHERE s.id = ? LIMIT 1', [(int) $job['source_id']])->getRowArray();
                if ($m && trim((string) $m['supplier_acc']) !== '') {
                    $out[] = trim((string) $m['supplier_acc']);
                }
            }
        } catch (\Throwable $e) {
        }
        return array_values(array_unique($out));
    }

    // ------------------------------------------------------------------ what a block would touch (shown before applying)

    public static function preview($db, string $type, string $key): array
    {
        $key = trim($key);
        $p = ['type' => $type, 'key' => $key, 'label' => null, 'sup_acc' => null, 'linked' => [], 'warning' => null];
        try {
            if ($type === 'supplier') {
                $s = $db->query('SELECT Sup_Acc_No, Name, sup_cat FROM supplier WHERE Sup_Acc_No = ? LIMIT 1', [$key])->getRowArray();
                if (!$s) {
                    return $p + ['error' => 'No supplier with account ' . $key . '.'];
                }
                $p['label'] = $s['Name'] . ' (' . $key . ')';
                $p['sup_acc'] = $key;
                $crew = $db->query('SELECT name, kind FROM tf_crew_person WHERE supplier_acc = ? AND archived_at IS NULL', [$key])->getResultArray();
                if ($crew) {
                    $p['linked'][] = ['what' => 'Driver / guide record', 'n' => count($crew), 'detail' => implode(', ', array_map(static fn ($c) => $c['name'] . ' (' . $c['kind'] . ')', $crew))];
                }
                $p['linked'][] = ['what' => 'Payment requests awaiting approval or payment', 'n' => (int) $db->query("SELECT COUNT(*) n FROM payment_request WHERE hotel_acc = ? AND payment_request IN (2,3,4,5,6)", [$key])->getRowArray()['n'], 'detail' => 'held while the block stands; balances unchanged'];
                $p['linked'][] = ['what' => 'Registered bank accounts', 'n' => (int) $db->query('SELECT COUNT(*) n FROM tpd_supplier_account WHERE sup_acc = ? AND active = 1', [$key])->getRowArray()['n'], 'detail' => 'all of them held by a supplier block'];
                $p['linked'][] = ['what' => 'Open inquiries with this person as crew', 'n' => (int) $db->query("SELECT COUNT(DISTINCT c.intake_id) n FROM tf_intake_crew c JOIN tf_intake i ON i.id = c.intake_id WHERE c.supplier_acc = ? AND i.archived_at IS NULL AND i.status IN ('new','working','quoted')", [$key])->getRowArray()['n'], 'detail' => 'flagged for review; no new assignment under "no new work" or "suspended"'];
                $p['linked'][] = ['what' => 'Upcoming confirmed tours as transport agent / guide', 'n' => (int) $db->query("SELECT COUNT(*) n FROM consignee WHERE (transport_agent = ? OR guide = ?) AND start_date >= CURDATE() AND tour_cancel = 0", [$key, $key])->getRowArray()['n'], 'detail' => 'flagged for review; not changed by the block'];
            } elseif ($type === 'hotel') {
                $h = $db->query('SELECT id, name, hotel_code, primary_sup_acc_no FROM hr2_hotel WHERE id = ? LIMIT 1', [(int) $key])->getRowArray();
                if (!$h) {
                    return $p + ['error' => 'No hotel with id ' . $key . '.'];
                }
                $p['label'] = $h['name'] . ' (' . $h['hotel_code'] . ')';
                $p['sup_acc'] = $h['primary_sup_acc_no'] ?: null;
                $p['linked'][] = ['what' => 'Its own ledger account', 'n' => $p['sup_acc'] ? 1 : 0, 'detail' => $p['sup_acc'] ? $p['sup_acc'] . ' — payments to it held by a payments block' : 'none linked'];
                $p['linked'][] = ['what' => 'Open inquiries with a night here', 'n' => (int) $db->query("SELECT COUNT(DISTINCT d.intake_id) n FROM tf_intake_day d JOIN tf_intake i ON i.id = d.intake_id WHERE d.hotel_id = ? AND i.archived_at IS NULL AND i.status IN ('new','working','quoted')", [(int) $h['id']])->getRowArray()['n'], 'detail' => 'flagged for review; "no new work" or "suspended" stops the quotation, "payments held" only warns'];
                $p['linked'][] = ['what' => 'Approved rate contracts', 'n' => (int) $db->query("SELECT COUNT(*) n FROM hr2_contract WHERE hotel_id = ? AND status = 'approved'", [(int) $h['id']])->getRowArray()['n'], 'detail' => 'untouched'];
                if ($p['sup_acc']) {
                    $p['linked'][] = ['what' => 'Payment requests awaiting approval or payment', 'n' => (int) $db->query("SELECT COUNT(*) n FROM payment_request WHERE hotel_acc = ? AND payment_request IN (2,3,4,5,6)", [$p['sup_acc']])->getRowArray()['n'], 'detail' => 'held while the block stands'];
                }
                $p['warning'] = 'Other hotels of the same owner or group are NOT blocked by this; block each one on purpose if that is intended.';
            } elseif ($type === 'customer') {
                $c = $db->query('SELECT Sup_Acc_No, Name, long_name, agent FROM customers WHERE Sup_Acc_No = ? LIMIT 1', [$key])->getRowArray();
                if (!$c) {
                    return $p + ['error' => 'No customer with code ' . $key . '.'];
                }
                $p['label'] = ($c['long_name'] ?: $c['Name']) . ' (' . $key . ')' . ((int) $c['agent'] === 1 ? ' — agent' : '');
                $p['linked'][] = ['what' => 'Open inquiries under this agent', 'n' => (int) $db->query("SELECT COUNT(*) n FROM tf_intake WHERE agent_acc = ? AND archived_at IS NULL AND status IN ('new','working','quoted')", [$key])->getRowArray()['n'], 'detail' => 'flagged for review; "no new work" or "suspended" stops the quotation, "payments held" only warns'];
                $p['linked'][] = ['what' => 'Upcoming confirmed tours', 'n' => (int) $db->query("SELECT COUNT(*) n FROM consignee WHERE agent = ? AND start_date >= CURDATE() AND tour_cancel = 0", [$key])->getRowArray()['n'], 'detail' => 'not changed by the block; flagged for review'];
            } elseif ($type === 'account') {
                $a = $db->query('SELECT a.*, s.Name FROM tpd_supplier_account a LEFT JOIN supplier s ON s.Sup_Acc_No = a.sup_acc WHERE a.id = ? LIMIT 1', [(int) $key])->getRowArray();
                if (!$a) {
                    return $p + ['error' => 'No registered account #' . $key . '.'];
                }
                $no = self::normNo($a['account_no']);
                $p['label'] = ($a['bank_name'] ?: 'bank?') . ' · ' . ($a['holder_name'] ?: 'holder?') . ' · …' . substr($no, -4) . ' (first seen under ' . ($a['Name'] ?: $a['sup_acc']) . ')';
                $p['sup_acc'] = null;   // an account block holds the account, wherever it is registered — not the supplier
                $p['acct_no_norm'] = $no;
                $p['bank_norm'] = self::normBank($a['bank_name']);
                // every registry record with the same number (any supplier, any bank spelling)
                $same = [];
                foreach ($db->query("SELECT a2.id, a2.sup_acc, a2.bank_name, a2.holder_name, a2.account_no, a2.status, a2.active, s2.Name FROM tpd_supplier_account a2 LEFT JOIN supplier s2 ON s2.Sup_Acc_No = a2.sup_acc")->getResultArray() as $r) {
                    if (self::normNo($r['account_no']) === $no) {
                        $same[] = $r;
                    }
                }
                $p['matches'] = array_map(static fn ($r) => ['where' => 'Registry #' . $r['id'] . ' under ' . ($r['Name'] ?: $r['sup_acc']) . ' (' . $r['sup_acc'] . ')', 'bank' => $r['bank_name'], 'holder' => $r['holder_name'], 'last4' => substr(self::normNo($r['account_no']), -4), 'state' => (int) $r['active'] === 1 ? $r['status'] : 'inactive', 'same_bank' => self::normBank($r['bank_name']) === $p['bank_norm']], $same);
                // the legacy supplier-bank table and the supplier master, for the same number
                foreach ($db->query('SELECT b.sup_acc, b.bank_name, b.beneficiary_name, b.account_no, s.Name FROM tpd_supplier_bank b LEFT JOIN supplier s ON s.Sup_Acc_No = b.sup_acc')->getResultArray() as $r) {
                    if (self::normNo($r['account_no']) === $no) {
                        $p['matches'][] = ['where' => 'Supplier bank details (legacy) under ' . ($r['Name'] ?: $r['sup_acc']) . ' (' . $r['sup_acc'] . ')', 'bank' => $r['bank_name'], 'holder' => $r['beneficiary_name'], 'last4' => substr($no, -4), 'state' => 'legacy', 'same_bank' => self::normBank($r['bank_name']) === $p['bank_norm']];
                    }
                }
                foreach ($db->query("SELECT Sup_Acc_No, Name, bank_name, acc_holder, sup_bank_acc FROM supplier WHERE sup_bank_acc IS NOT NULL AND sup_bank_acc <> ''")->getResultArray() as $r) {
                    if (self::normNo($r['sup_bank_acc']) === $no) {
                        $p['matches'][] = ['where' => 'Supplier master record ' . $r['Name'] . ' (' . $r['Sup_Acc_No'] . ')', 'bank' => $r['bank_name'], 'holder' => $r['acc_holder'], 'last4' => substr($no, -4), 'state' => 'master', 'same_bank' => self::normBank($r['bank_name']) === $p['bank_norm']];
                    }
                }
                $n = count($p['matches']);
                $p['linked'][] = ['what' => 'Records carrying this account number (all held by the block)', 'n' => $n, 'detail' => implode('; ', array_map(static fn ($m) => $m['where'] . ($m['same_bank'] ? '' : ' — different bank spelling'), $p['matches']))];
                $p['linked'][] = ['what' => 'Payments carrying this account (not yet paid)', 'n' => (int) $db->query("SELECT COUNT(*) n FROM tpd_payment_account pa WHERE pa.ref_type = 'voucher' AND pa.account_id IN (" . (implode(',', array_map(static fn ($r) => (int) $r['id'], $same)) ?: '0') . ") AND NOT EXISTS (SELECT 1 FROM voucher v WHERE v.Vnum = pa.ref_id AND v.flag = 1)")->getRowArray()['n'], 'detail' => 'release refused while the block stands'];
                $p['linked'][] = ['what' => 'The supplier\'s other accounts (different numbers)', 'n' => (int) $db->query('SELECT COUNT(*) n FROM tpd_supplier_account WHERE sup_acc = ? AND id <> ? AND active = 1', [$a['sup_acc'], (int) $a['id']])->getRowArray()['n'], 'detail' => 'NOT held by this block'];
                $p['warning'] = 'The block follows the account number' . ($p['bank_norm'] !== '' ? ' at ' . $a['bank_name'] : '') . ', not the registry row: any registry record, legacy detail or supplier master entry with this number is held, under whichever supplier it sits.';
            } else {
                return $p + ['error' => 'Unknown target type.'];
            }
        } catch (\Throwable $e) {
            $p['error'] = $e->getMessage();
        }
        return $p;
    }

    // ------------------------------------------------------------------ apply / lift

    public static function apply($db, array $in, string $user, ?string $userName): array
    {
        $type = (string) ($in['target_type'] ?? '');
        $key  = trim((string) ($in['target_key'] ?? ''));
        $kind = (string) ($in['kind'] ?? '');
        $reason = trim((string) ($in['reason'] ?? ''));
        if (!isset(self::TYPES[$type]) || $key === '') {
            return ['ok' => false, 'message' => 'Choose the exact record to block.'];
        }
        if (!isset(self::KINDS[$kind])) {
            return ['ok' => false, 'message' => 'Choose what the block holds: new work, payments, or both.'];
        }
        if (mb_strlen($reason) < 10) {
            return ['ok' => false, 'message' => 'Give the reason for the block (at least ten characters); it is shown on every refusal.'];
        }
        $p = self::preview($db, $type, $key);
        if (!empty($p['error'])) {
            return ['ok' => false, 'message' => $p['error']];
        }
        $dup = $type === 'account'
            ? $db->query("SELECT id FROM tf_block WHERE status = 'active' AND target_type = 'account' AND acct_no_norm = ?", [$p['acct_no_norm'] ?? ''])->getRowArray()
            : $db->query("SELECT id FROM tf_block WHERE status = 'active' AND target_type = ? AND target_key = ?", [$type, $key])->getRowArray();
        if ($dup) {
            return ['ok' => false, 'message' => 'This record already carries an active block (#' . $dup['id'] . '). Lift it first if the kind should change.'];
        }
        $now = date('Y-m-d H:i:s');
        $db->query('INSERT INTO tf_block (target_type, target_key, target_label, sup_acc, acct_no_norm, bank_norm, kind, reason, reference, notes, status, set_by, set_by_name, set_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            $type, $key, mb_substr((string) $p['label'], 0, 190), $p['sup_acc'], $p['acct_no_norm'] ?? null, $p['bank_norm'] ?? null, $kind, mb_substr($reason, 0, 500), mb_substr(trim((string) ($in['reference'] ?? '')), 0, 120) ?: null,
            trim((string) ($in['notes'] ?? '')) !== '' ? trim((string) $in['notes']) : null, 'active', $user, $userName, $now,
        ]);
        $id = (int) ($db->query('SELECT LAST_INSERT_ID() AS id')->getRowArray()['id'] ?? 0);
        self::log($db, $id, 'applied', $user, $userName, self::KINDS[$kind] . ' — ' . $reason . ' · linked: ' . implode('; ', array_map(static fn ($l) => $l['what'] . ' ' . $l['n'], $p['linked'])));
        return ['ok' => true, 'id' => $id, 'message' => self::KINDS[$kind] . ' applied to ' . $p['label'] . '. Every route now refuses; the block is lifted only by the Super Admin, with a reason.'];
    }

    public static function lift($db, int $id, string $reason, string $user, ?string $userName): array
    {
        $b = $db->query('SELECT * FROM tf_block WHERE id = ?', [$id])->getRowArray();
        if (!$b) {
            return ['ok' => false, 'message' => 'No such block.'];
        }
        if ($b['status'] !== 'active') {
            return ['ok' => false, 'message' => 'This block was already lifted on ' . $b['lifted_at'] . ' by ' . ($b['lifted_by_name'] ?: $b['lifted_by']) . '.'];
        }
        $reason = trim($reason);
        if (mb_strlen($reason) < 10) {
            return ['ok' => false, 'message' => 'Give the reason the block is lifted (at least ten characters).'];
        }
        $db->query("UPDATE tf_block SET status = 'lifted', lifted_by = ?, lifted_by_name = ?, lifted_at = ?, lift_reason = ? WHERE id = ?", [$user, $userName, date('Y-m-d H:i:s'), mb_substr($reason, 0, 500), $id]);
        self::log($db, $id, 'lifted', $user, $userName, $reason);
        return ['ok' => true, 'message' => 'Block lifted on ' . $b['target_label'] . '. Work and payments resume under the usual checks.'];
    }

    public static function note($db, int $id, string $text, string $user, ?string $userName): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['ok' => false, 'message' => 'Nothing to add.'];
        }
        self::log($db, $id, 'note', $user, $userName, $text);
        return ['ok' => true, 'message' => 'Note added (restricted to holders of the right).'];
    }

    private static function log($db, int $blockId, string $action, string $user, ?string $userName, ?string $detail): void
    {
        try {
            $db->query('INSERT INTO tf_block_log (block_id, action, `user`, user_name, `at`, detail) VALUES (?,?,?,?,?,?)', [$blockId, $action, $user, $userName, date('Y-m-d H:i:s'), $detail !== null ? mb_substr($detail, 0, 1000) : null]);
        } catch (\Throwable $e) {
        }
    }

    /** The one-line counts for the Needs attention list. */
    public static function attention($db): array
    {
        if (!self::installed($db)) {
            return [];
        }
        $out = [];
        $n = (int) ($db->query("SELECT COUNT(*) n FROM tf_block WHERE status = 'active'")->getRowArray()['n'] ?? 0);
        if ($n) {
            $out[] = ['level' => 'red', 'what' => $n . ' active block' . ($n === 1 ? '' : 's'), 'why' => 'Work or payments held on these records until the Super Admin lifts the block'];
            // existing work that involves a blocked record: never changed by the block, listed for a person to review
            $accs = array_values(array_filter(array_map(static fn ($b) => $b['target_type'] === 'account' ? null : ($b['sup_acc'] ?: ($b['target_type'] === 'supplier' ? $b['target_key'] : null)), self::active($db))));
            $cust = array_values(array_filter(array_map(static fn ($b) => $b['target_type'] === 'customer' ? $b['target_key'] : null, self::active($db))));
            $in = static fn (array $v) => implode(',', array_map(static fn ($x) => "'" . str_replace("'", "''", (string) $x) . "'", $v)) ?: "''";
            $t = (int) ($db->query("SELECT COUNT(*) n FROM consignee WHERE start_date >= CURDATE() AND tour_cancel = 0 AND (transport_agent IN (" . $in($accs) . ") OR guide IN (" . $in($accs) . ") OR agent IN (" . $in($cust) . ") OR EXISTS (SELECT 1 FROM tour_quotations q WHERE q.quotation_id = consignee.tour_id AND q.hotel_acc IN (" . $in($accs) . ")))")->getRowArray()['n'] ?? 0);
            if ($t) {
                $out[] = ['level' => 'amber', 'what' => $t . ' upcoming confirmed tour' . ($t === 1 ? '' : 's') . ' involve' . ($t === 1 ? 's' : '') . ' a blocked record', 'why' => 'Not changed by the block; review each tour and decide'];
            }
        }
        return $out;
    }
}
