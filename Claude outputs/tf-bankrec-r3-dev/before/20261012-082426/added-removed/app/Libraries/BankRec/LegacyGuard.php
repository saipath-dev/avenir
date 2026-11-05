<?php

namespace App\Libraries\BankRec;

/**
 * tf-bankrec-r3 (D7) — keeps the old Bank Reconciliation screen (accounts/bank_reconcil) off the periods that the Daily Bank
 * Reconciliation covers. A bank ledger account is protected from the first day of its confirmed mapping: the old screen
 * may not set or clear its reconciliation flags (bank_rec, bankst_flag) or move its rows to or from the unrealised
 * account for any row dated on or after that day. Earlier dates stay available on the old screen as before.
 *
 * The guard only refuses; it never changes a flag. Flags that already exist in a protected period are listed by the new
 * workflow as a warning (flagsInPeriod) and left as they are.
 */
final class LegacyGuard
{
    /** The old screen moves rows of a bank account to this unrealised account (New_L_Code keeps the bank account). */
    public const UNREALISED = '1-6-112-10782';

    /** ledger code => ['start' => first day, 'account' => label, 'bank_account_id' => id] for every confirmed mapping */
    public static function protectedAccounts($db): array
    {
        try {
            if (!$db->query("SHOW TABLES LIKE 'brc_account_map'")->getResultArray()) {
                return [];
            }
            $rows = $db->query("SELECT m.ledger_code, m.rec_start, m.bank_account_id, a.label FROM brc_account_map m LEFT JOIN tpd_bank_account a ON a.id = m.bank_account_id
                WHERE m.status = 'confirmed' ORDER BY m.rec_start")->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $code = (string) $r['ledger_code'];
            if (!isset($out[$code]) || $r['rec_start'] < $out[$code]['start']) {
                $out[$code] = ['start' => (string) $r['rec_start'], 'account' => (string) ($r['label'] ?? $code), 'bank_account_id' => (int) $r['bank_account_id']];
            }
        }
        return $out;
    }

    /**
     * Refusal for a change to ledger rows (by ID; an ID can hold several rows) when any of those rows belongs to a protected
     * bank account (as L_Code, or as New_L_Code while moved to the unrealised account) and is dated on or after its first
     * day. Returns null when the change may go ahead.
     */
    public static function checkRows($db, array $ids, string $action): ?array
    {
        $prot = self::protectedAccounts($db);
        $ids = array_values(array_unique(array_filter(array_map(static fn ($i) => (int) $i, $ids), static fn ($i) => $i > 0)));
        if (!$prot || !$ids) {
            return null;
        }
        $hits = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $in = implode(',', $chunk);
            foreach ($db->query("SELECT ID, L_Code, New_L_Code, DATE(Date) AS d FROM account_runing WHERE ID IN ({$in})")->getResultArray() as $r) {
                foreach ([(string) $r['L_Code'], (string) ($r['New_L_Code'] ?? '')] as $code) {
                    if ($code !== '' && isset($prot[$code]) && $r['d'] >= $prot[$code]['start']) {
                        $hits[] = ['id' => (int) $r['ID'], 'ledger_code' => $code, 'date' => $r['d'], 'first_day' => $prot[$code]['start'], 'account' => $prot[$code]['account']];
                        break;
                    }
                }
            }
        }
        if (!$hits) {
            return null;
        }
        return self::refuse($db, $action, $hits, null);
    }

    /** Refusal for a change by account and date range (the old screen's "reconcile up to" and "repost" actions). */
    public static function checkRange($db, string $ledgerCode, ?string $from, string $to, string $action): ?array
    {
        $prot = self::protectedAccounts($db);
        $ledgerCode = trim($ledgerCode);
        if (!isset($prot[$ledgerCode])) {
            return null;
        }
        $start = $prot[$ledgerCode]['start'];
        $to = substr(trim($to), 0, 10);
        if ($to === '' || $to >= $start) {
            return self::refuse($db, $action, [], ['ledger_code' => $ledgerCode, 'from' => $from, 'to' => $to, 'first_day' => $start, 'account' => $prot[$ledgerCode]['account']]);
        }
        return null;
    }

    private static function refuse($db, string $action, array $rows, ?array $range): array
    {
        $first = $rows[0] ?? $range;
        $msg = 'The old Bank Reconciliation screen cannot change ' . ($range ? 'dates on or after ' . $first['first_day'] : 'ledger row' . (count($rows) > 1 ? 's' : '') . ' ' . implode(', ', array_slice(array_unique(array_column($rows, 'id')), 0, 8)))
            . ' of ' . $first['account'] . ' (ledger ' . $first['ledger_code'] . '): from ' . $first['first_day'] . ' this account is reconciled in Accounts → Daily Bank Reconciliation.'
            . ' Dates before ' . $first['first_day'] . ' can still be reconciled here. Nothing was changed.';
        try {
            if ($db->query("SHOW TABLES LIKE 'tpd_audit'")->getResultArray()) {
                Rec::audit($db, 'brc_legacy_refused', 'account_runing', $range ? $first['ledger_code'] : (string) $first['id'], null,
                    ['old_screen_action' => $action, 'rows' => array_slice($rows, 0, 50), 'range' => $range], 'old reconciliation screen: protected period (from ' . $first['first_day'] . ')', $first['account']);
            }
        } catch (\Throwable $e) {
            log_message('error', 'brc_legacy_refused not audited: ' . $e->getMessage());
        }
        return [409, $msg];
    }

    /**
     * Ledger rows of a protected account dated from its first day up to $date that carry flags of the old screen
     * (bank_rec or bankst_flag set), or that the old screen moved to the unrealised account. Read only.
     */
    public static function flagsInPeriod($db, string $ledgerCode, string $start, string $date): array
    {
        try {
            $rows = $db->query("SELECT ID, L_Code, New_L_Code, DATE(Date) AS d, Dr, Cr, Discription, bank_rec, bankst_flag FROM account_runing
                WHERE ((L_Code = ? AND (COALESCE(bank_rec, 0) <> 0 OR COALESCE(bankst_flag, 0) <> 0)) OR (L_Code = ? AND New_L_Code = ?))
                  AND DATE(Date) BETWEEN ? AND ? ORDER BY Date, ID LIMIT 200", [$ledgerCode, self::UNREALISED, $ledgerCode, $start, $date])->getResultArray();
        } catch (\Throwable $e) {
            return ['count' => 0, 'rows' => [], 'error' => $e->getMessage()];
        }
        $out = [];
        foreach ($rows as $r) {
            $what = [];
            if ((int) $r['bank_rec'] !== 0) {
                $what[] = 'bank_rec';
            }
            if ((int) $r['bankst_flag'] !== 0) {
                $what[] = 'bankst_flag';
            }
            if ($r['L_Code'] === self::UNREALISED) {
                $what[] = 'moved to the unrealised account ' . self::UNREALISED;
            }
            $out[] = ['id' => (int) $r['ID'], 'date' => $r['d'], 'amount' => Rec::dec(Rec::cents($r['Dr']) - Rec::cents($r['Cr'])), 'description' => (string) $r['Discription'], 'flags' => $what];
        }
        return ['count' => count($out), 'rows' => $out];
    }
}
