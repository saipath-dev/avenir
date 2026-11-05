<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r1 — the Avenir Bank Account Master: the company's own accounts, each tied to the ledger account the
 * voucher posts to (account_posting L_Code, the list Payment approval and Bank Payment Check already offer), so an
 * account chosen here is the same account the accounting record shows.
 *
 * While the master is empty the old list of bank ledger accounts is offered, so nothing stops; once an active
 * account exists in the master, only the master's accounts can be chosen (no free typing).
 */
final class BankAccounts
{
    public static function all($db, bool $activeOnly = false): array
    {
        try {
            return $db->query("SELECT * FROM tpd_bank_account" . ($activeOnly ? ' WHERE active = 1' : '') . " ORDER BY is_default DESC, active DESC, label ASC")->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function get($db, int $id): ?array
    {
        try {
            return $db->query("SELECT * FROM tpd_bank_account WHERE id = ?", [$id])->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The bank ledger accounts the chain already uses (Payment approval's list). */
    public static function ledgerAccounts($db): array
    {
        try {
            return $db->query("SELECT L_Code, PName FROM account_posting WHERE B_Code = 1 AND T_Code = 6 AND H_Code = 8 AND PName NOT LIKE '%Control%' ORDER BY PName")->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * What the accountant may choose from: the master's active accounts; with none, the ledger accounts (marked
     * so the screen can say the master is empty).
     */
    public static function forSelection($db): array
    {
        $m = self::all($db, true);
        if ($m) {
            return ['source' => 'master', 'accounts' => array_map(static fn ($a) => [
                'id' => (int) $a['id'], 'label' => $a['label'], 'bank_name' => $a['bank_name'], 'branch' => $a['branch'], 'account_name' => $a['account_name'],
                'account_no_masked' => self::mask($a['account_no'], 4), 'currency' => $a['currency'], 'ledger_code' => $a['ledger_code'], 'is_default' => (int) $a['is_default'] === 1,
                'entity' => $a['entity'], 'account_type' => $a['account_type'], 'swift' => $a['swift'],
            ], $m)];
        }
        return ['source' => 'ledger', 'accounts' => array_map(static fn ($l) => ['id' => null, 'label' => $l['PName'], 'bank_name' => $l['PName'], 'branch' => '', 'account_name' => '', 'account_no_masked' => '', 'currency' => 'LKR', 'ledger_code' => $l['L_Code'], 'is_default' => false, 'entity' => '', 'account_type' => '', 'swift' => ''], self::ledgerAccounts($db))];
    }

    /** The master account behind a ledger code (a voucher's Bank_acc), if one is mastered. */
    public static function byLedger($db, ?string $ledger): ?array
    {
        if (!$ledger) {
            return null;
        }
        try {
            return $db->query("SELECT * FROM tpd_bank_account WHERE ledger_code = ? ORDER BY active DESC, is_default DESC LIMIT 1", [$ledger])->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function default($db): ?array
    {
        try {
            return $db->query("SELECT * FROM tpd_bank_account WHERE active = 1 ORDER BY is_default DESC, id ASC LIMIT 1")->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function mask(?string $no, int $digits = 4): string
    {
        $no = trim((string) $no);
        if ($no === '') {
            return '';
        }
        $clean = preg_replace('/\s+/', '', $no);
        if (strlen($clean) <= $digits) {
            return str_repeat('•', max(0, strlen($clean) - 1)) . substr($clean, -1);
        }
        return str_repeat('•', max(4, strlen($clean) - $digits)) . substr($clean, -$digits);
    }

    /** The account as it may appear on a supplier's document, by its external-display setting. */
    public static function external(array $a): array
    {
        $mode = (string) ($a['external_display'] ?? 'masked');
        $no = (string) $a['account_no'];
        return [
            'bank' => $a['bank_name'] . ($a['branch'] ? ', ' . $a['branch'] : ''),
            'account_name' => $a['account_name'],
            'account' => $mode === 'full' ? $no : ($mode === 'hidden' ? '' : self::mask($no, (int) ($a['display_digits'] ?: 4))),
            'shown' => $mode !== 'hidden',
            'currency' => $a['currency'], 'swift' => $a['swift'] ?? '',
        ];
    }

    public static function save($db, array $in, string $by): array
    {
        $id = (int) ($in['id'] ?? 0);
        $row = [
            'entity' => trim((string) ($in['entity'] ?? '')) ?: 'Avenir Holidays (Pvt) Ltd',
            'bank_name' => trim((string) ($in['bank_name'] ?? '')),
            'branch' => trim((string) ($in['branch'] ?? '')) ?: null,
            'account_name' => trim((string) ($in['account_name'] ?? '')),
            'account_no' => preg_replace('/[^0-9A-Za-z\-]/', '', (string) ($in['account_no'] ?? '')),
            'currency' => strtoupper(substr(trim((string) ($in['currency'] ?? 'LKR')), 0, 3)) ?: 'LKR',
            'swift' => strtoupper(trim((string) ($in['swift'] ?? ''))) ?: null,
            'account_type' => trim((string) ($in['account_type'] ?? '')) ?: null,
            'label' => trim((string) ($in['label'] ?? '')),
            'ledger_code' => trim((string) ($in['ledger_code'] ?? '')) ?: null,
            'is_default' => !empty($in['is_default']) ? 1 : 0,
            'active' => isset($in['active']) ? (!empty($in['active']) ? 1 : 0) : 1,
            'external_display' => in_array($in['external_display'] ?? 'masked', ['full', 'masked', 'hidden'], true) ? $in['external_display'] : 'masked',
            'display_digits' => max(2, min(8, (int) ($in['display_digits'] ?? 4))),
            'note' => trim((string) ($in['note'] ?? '')) ?: null,
        ];
        foreach (['bank_name', 'account_name', 'account_no', 'label'] as $k) {
            if ($row[$k] === '') {
                return ['ok' => false, 'message' => 'Bank name, account name, account number and the internal label are required.'];
            }
        }
        $prev = $id ? self::get($db, $id) : null;
        if ($id && !$prev) {
            return ['ok' => false, 'message' => 'That bank account no longer exists.'];
        }
        if ($row['is_default']) {
            $db->query("UPDATE tpd_bank_account SET is_default = 0 WHERE id <> ?", [$id]);
        }
        if ($prev) {
            $row['updated_by'] = $by;
            $row['updated_at'] = date('Y-m-d H:i:s');
            $db->table('tpd_bank_account')->where('id', $id)->update($row);
        } else {
            $row['created_by'] = $by;
            $row['created_at'] = date('Y-m-d H:i:s');
            $db->table('tpd_bank_account')->insert($row);
            $id = (int) $db->insertID();
        }
        $after = self::get($db, $id);
        $strip = static fn ($a) => $a ? array_merge(array_diff_key($a, array_flip(['created_by', 'created_at', 'updated_by', 'updated_at'])), ['account_no' => self::mask($a['account_no'], 4)]) : null;
        Audit::log($db, ['action' => 'bank_account_saved', 'ref_type' => 'bank_account', 'ref_id' => $id, 'prev' => $strip($prev), 'new' => $strip($after), 'bank_account' => $after['label'] ?? null]);
        return ['ok' => true, 'id' => $id, 'message' => $prev ? 'Bank account updated.' : 'Bank account added.'];
    }
}
