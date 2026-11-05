<?php

namespace App\Libraries\BankRec;

use App\Libraries\TourPayments\Audit;
use App\Libraries\TourPayments\BankAccounts;

/**
 * tf-bankrec-r1/r2 — the core of the Daily Bank Reconciliation: accounts and their confirmed ledger mapping, daily
 * rates, the bank side (accepted statements) and the book side (the ledger, read only), and the daily reconciliation.
 *
 * Sign convention everywhere: an amount is "funds" — positive adds to the money in the account, negative takes it out.
 * Bank: money_in − money_out (account currency). Book: Dr − Cr of the bank ledger account (LKR).
 * Money is computed in integer cents; rates are LKR per one unit of the account currency.
 *
 * r2 (12 Oct 2026): timing items can be matched (D9); ledger rows booked before the first day can be opening
 * outstanding items, accepted by a reviewer, and clearing later never explains the opening difference by itself (D10);
 * partial clearing; exchange-rate differences need an explanation; corrections are shown as proposed / approved but
 * not posted / posted and verified; the daily record keeps its snapshot and the fingerprints of what it was computed
 * from, so a later change is flagged for re-review; changes are serialised per account and conflicting edits refused.
 */
final class Rec
{
    public const CURRENCIES = ['LKR', 'USD', 'JPY'];
    public const CURRENCY_NAMES = ['LKR' => 'Sri Lankan rupee', 'USD' => 'US dollar', 'JPY' => 'Japanese yen'];
    public const STATUS_LABELS = [
        'not_set_up'        => 'Not set up — ledger mapping not confirmed',
        'insufficient'      => 'Unreconciled — insufficient data',
        'open_items'        => 'Unreconciled — items to explain',
        'explained'         => 'Reconciled — all items explained (awaiting review)',
        'explained_pending' => 'All items explained — ledger not yet corrected (awaiting review)',
        'reviewed'          => 'Reconciled — reviewed',
        'reviewed_pending'  => 'Reviewed — ledger not yet corrected',
    ];
    /** the parts of a day's fingerprint whose change makes a reviewed record out of date */
    public const MATERIAL = ['mapping', 'statements', 'rates', 'ledger', 'matches', 'items', 'opening'];
    public const COMPONENT_LABELS = ['mapping' => 'the ledger mapping', 'statements' => 'statement lines or their LKR values', 'rates' => 'exchange rates', 'ledger' => 'ledger rows (book side)',
        'matches' => 'matches or their explanations', 'items' => 'how unmatched items are explained', 'opening' => 'opening items', 'postings' => 'correction approvals or postings'];

    // ---------------------------------------------------------------- money helpers

    public static function cents($v): int
    {
        if ($v === null || $v === '') {
            return 0;
        }
        if (is_int($v)) {
            return $v * 100;
        }
        $s = trim((string) $v);
        $neg = str_starts_with($s, '-');
        $s = ltrim($s, '+-');
        [$i, $f] = array_pad(explode('.', $s, 2), 2, '');
        $f = substr(str_pad($f, 3, '0'), 0, 3);
        $c = (int) $i * 100 + intdiv((int) $f, 10) + ((int) substr($f, 2, 1) >= 5 ? 1 : 0);
        return $neg ? -$c : $c;
    }

    public static function dec(int $cents): string
    {
        $neg = $cents < 0;
        $a = abs($cents);
        return ($neg ? '-' : '') . intdiv($a, 100) . '.' . str_pad((string) ($a % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function fmt(int $cents): string
    {
        return ($cents < 0 ? '−' : '') . number_format(abs($cents) / 100, 2);
    }

    /** cents of the account currency × rate → LKR cents (half away from zero) */
    public static function toLkr(int $cents, ?string $rate): ?int
    {
        if ($rate === null || $rate === '') {
            return null;
        }
        return (int) round($cents * (float) $rate);
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public static function validDate(?string $d): bool
    {
        if (!$d || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            return false;
        }
        [$y, $m, $dd] = array_map('intval', explode('-', $d));
        return checkdate($m, $dd, $y) && $y >= 2000 && $y <= 2100;
    }

    public static function audit($db, string $action, string $refType, $refId, $prev, $new, ?string $reason = null, ?string $account = null): void
    {
        Audit::log($db, ['action' => $action, 'ref_type' => $refType, 'ref_id' => $refId, 'prev' => $prev, 'new' => $new, 'reason' => $reason, 'bank_account' => $account]);
    }

    // ---------------------------------------------------------------- concurrency: one change at a time per account

    /** A named database lock per account (and database): changes to one account are made one after the other. */
    public static function lockName($db, int $accountId): string
    {
        $d = (string) ($db->query('SELECT DATABASE() AS d')->getRowArray()['d'] ?? 'db');
        return substr('brc:' . $d, 0, 50) . ':' . $accountId;
    }

    /** Runs $fn while holding the account's lock (waits up to 15 s). $fn returns the usual [code, message, ...] array. */
    public static function locked($db, int $accountId, callable $fn): array
    {
        $name = self::lockName($db, $accountId);
        $got = (int) ($db->query('SELECT GET_LOCK(?, 15) AS l', [$name])->getRowArray()['l'] ?? 0);
        if ($got !== 1) {
            return [409, 'Another user is saving a change to this account right now; try again in a moment.'];
        }
        try {
            return $fn();
        } finally {
            $db->query('SELECT RELEASE_LOCK(?) AS r', [$name]);
        }
    }

    /**
     * Runs $fn in a database transaction. $fn returns null to commit, or an error [code, message] to roll back. A query
     * the database refuses (e.g. a second claim on the same item) rolls everything back.
     */
    public static function tx($db, callable $fn): ?array
    {
        $db->transStrict(false);
        $db->transBegin();
        try {
            $r = $fn();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'brc tx: ' . $e->getMessage());
            return [409, 'Not saved: the database refused the change (another user may have changed the same item at the same moment). Reload and try again.'];
        }
        if ($r !== null) {
            $db->transRollback();
            return $r;
        }
        if ($db->transStatus() === false) {
            $db->transComplete();
            return [409, 'Not saved: the database refused the change (another user may have changed the same item at the same moment). Reload and try again.'];
        }
        $db->transCommit();
        return null;
    }

    /** Insert that reports failure instead of throwing (e.g. a duplicate claim). */
    public static function ins($db, string $table, array $row): bool
    {
        try {
            return (bool) $db->table($table)->insert($row);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function lineKey(int $lineId): string
    {
        return 'L' . $lineId;
    }

    public static function bookKey(int $accountId, string $key): string
    {
        return 'B' . $accountId . ':' . $key;
    }

    // ---------------------------------------------------------------- accounts and the ledger mapping

    /** Avenir bank accounts with their reconciliation mapping state; account numbers masked. */
    public static function accounts($db): array
    {
        $out = [];
        foreach (BankAccounts::all($db) as $a) {
            $out[] = self::accountView($db, $a);
        }
        return $out;
    }

    public static function account($db, int $id): ?array
    {
        $a = BankAccounts::get($db, $id);
        return $a ? self::accountView($db, $a) : null;
    }

    private static function accountView($db, array $a): array
    {
        $maps = $db->query('SELECT * FROM brc_account_map WHERE bank_account_id = ? ORDER BY id DESC', [(int) $a['id']])->getResultArray();
        $confirmed = null;
        $pending = null;
        foreach ($maps as $m) {
            if ($m['status'] === 'confirmed' && !$confirmed) {
                $confirmed = $m;
            }
            if ($m['status'] === 'proposed' && !$pending) {
                $pending = $m;
            }
        }
        $state = 'none';
        if ($confirmed) {
            $state = ($confirmed['ledger_code'] === (string) $a['ledger_code'] && $confirmed['currency'] === strtoupper((string) $a['currency'])) ? 'confirmed' : 'changed';
        } elseif ($pending) {
            $state = 'proposed';
        }
        $ledgerName = null;
        if ($a['ledger_code']) {
            $r = $db->query('SELECT PName FROM account_posting WHERE L_Code = ? LIMIT 1', [$a['ledger_code']])->getRowArray();
            $ledgerName = $r['PName'] ?? null;
        }
        return [
            'id' => (int) $a['id'], 'label' => $a['label'], 'bank_name' => $a['bank_name'], 'branch' => $a['branch'],
            'account_no_masked' => BankAccounts::mask($a['account_no'], 4), 'currency' => strtoupper((string) $a['currency']),
            'ledger_code' => $a['ledger_code'], 'ledger_name' => $ledgerName, 'active' => (int) $a['active'] === 1,
            'map_state' => $state, 'map' => $state === 'confirmed' ? $confirmed : null, 'map_confirmed' => $confirmed, 'map_pending' => $pending,
            'supported_currency' => in_array(strtoupper((string) $a['currency']), self::CURRENCIES, true),
        ];
    }

    /** The confirmed mapping in force (null if none, or if the master changed since it was confirmed). */
    public static function activeMap($db, int $accountId): ?array
    {
        $a = self::account($db, $accountId);
        return $a && $a['map_state'] === 'confirmed' ? $a['map'] + ['account' => $a] : null;
    }

    public static function proposeMap($db, int $accountId, string $recStart, string $note, string $user): array
    {
        $a = BankAccounts::get($db, $accountId);
        if (!$a) {
            return [404, 'That bank account does not exist in the Avenir Bank Accounts master.'];
        }
        $cur = strtoupper((string) $a['currency']);
        if (!in_array($cur, self::CURRENCIES, true)) {
            return [400, "The account's currency ({$cur}) is not one the reconciliation supports (LKR, USD, JPY)."];
        }
        if (!$a['ledger_code']) {
            return [400, 'Choose this account\'s ledger account in the Avenir Bank Accounts master first; the mapping is proposed from there.'];
        }
        if (!$db->query('SELECT 1 FROM account_posting WHERE L_Code = ? LIMIT 1', [$a['ledger_code']])->getRowArray()) {
            return [400, 'The ledger account in the master does not exist in the chart of accounts.'];
        }
        if (!self::validDate($recStart)) {
            return [400, 'Give the first day to reconcile (a valid date).'];
        }
        return self::locked($db, $accountId, static function () use ($db, $accountId, $a, $cur, $recStart, $note, $user) {
            $clash = $db->query("SELECT m.bank_account_id FROM brc_account_map m WHERE m.status = 'confirmed' AND m.ledger_code = ? AND m.bank_account_id <> ?", [$a['ledger_code'], $accountId])->getRowArray();
            if ($clash) {
                return [409, 'That ledger account is already confirmed for another bank account; one ledger account maps to one bank account.'];
            }
            $prev = $db->query("SELECT * FROM brc_account_map WHERE bank_account_id = ? AND status = 'proposed'", [$accountId])->getResultArray();
            $id = 0;
            $err = self::tx($db, static function () use ($db, $accountId, $a, $cur, $recStart, $note, $user, &$id) {
                $db->query("UPDATE brc_account_map SET status = 'superseded', decided_by = ?, decided_at = ?, decision_note = 'replaced by a newer proposal' WHERE bank_account_id = ? AND status = 'proposed'", [$user, self::now(), $accountId]);
                if (!self::ins($db, 'brc_account_map', ['bank_account_id' => $accountId, 'ledger_code' => $a['ledger_code'], 'currency' => $cur, 'rec_start' => $recStart,
                    'status' => 'proposed', 'note' => mb_substr($note, 0, 300) ?: null, 'created_by' => $user, 'created_at' => self::now()])) {
                    return [409, 'Not saved.'];
                }
                $id = (int) $db->insertID();
                return null;
            });
            if ($err) {
                return $err;
            }
            self::audit($db, 'brc_mapping_proposed', 'brc_account', $accountId, $prev ?: null, ['map_id' => $id, 'ledger_code' => $a['ledger_code'], 'currency' => $cur, 'rec_start' => $recStart], $note, $a['label']);
            return [200, 'Mapping proposed. Another user with the review privilege must confirm it.', $id];
        });
    }

    public static function decideMap($db, int $mapId, string $decision, string $note, string $user): array
    {
        $m = $db->query('SELECT * FROM brc_account_map WHERE id = ?', [$mapId])->getRowArray();
        if (!$m || $m['status'] !== 'proposed') {
            return [409, 'That proposal is not waiting for a decision.'];
        }
        if ($m['created_by'] === $user) {
            return [403, 'A mapping must be confirmed by someone other than the person who proposed it.'];
        }
        if (!in_array($decision, ['confirm', 'reject'], true)) {
            return [400, 'Decision must be confirm or reject.'];
        }
        if ($decision === 'reject' && mb_strlen(trim($note)) < 5) {
            return [400, 'Give the reason for rejecting (at least 5 characters).'];
        }
        $a = BankAccounts::get($db, (int) $m['bank_account_id']);
        if ($decision === 'confirm' && (!$a || (string) $a['ledger_code'] !== $m['ledger_code'] || strtoupper((string) $a['currency']) !== $m['currency'])) {
            return [409, 'The account in the master has changed since this was proposed; propose the mapping again.'];
        }
        return self::locked($db, (int) $m['bank_account_id'], static function () use ($db, $m, $mapId, $decision, $note, $user, $a) {
            $err = self::tx($db, static function () use ($db, $m, $mapId, $decision, $note, $user) {
                if ($decision === 'confirm') {
                    $db->query("UPDATE brc_account_map SET status = 'superseded', decided_by = ?, decided_at = ?, decision_note = 'replaced by a newer confirmed mapping' WHERE bank_account_id = ? AND status = 'confirmed'", [$user, self::now(), (int) $m['bank_account_id']]);
                }
                $db->query("UPDATE brc_account_map SET status = ?, decided_by = ?, decided_at = ?, decision_note = ? WHERE id = ? AND status = 'proposed'", [$decision === 'confirm' ? 'confirmed' : 'rejected', $user, self::now(), mb_substr($note, 0, 300) ?: null, $mapId]);
                return $db->affectedRows() === 1 ? null : [409, 'Someone else decided on this proposal a moment ago; reload.'];
            });
            if ($err) {
                return $err;
            }
            self::audit($db, $decision === 'confirm' ? 'brc_mapping_confirmed' : 'brc_mapping_rejected', 'brc_account', $m['bank_account_id'], $m, ['status' => $decision === 'confirm' ? 'confirmed' : 'rejected'], $note, $a['label'] ?? null);
            return [200, $decision === 'confirm' ? 'Mapping confirmed.' : 'Mapping rejected.'];
        });
    }

    // ---------------------------------------------------------------- rates

    /**
     * The applicable rate (LKR per unit) for a currency on a day: the reconciliation's own rate entered for that day,
     * else (USD only) the system's daily USD rate for that day. Never a rate of another day.
     */
    public static function rate($db, string $currency, string $date): ?array
    {
        if ($currency === 'LKR') {
            return ['rate' => '1', 'source' => 'LKR'];
        }
        $r = $db->query('SELECT rate, source FROM brc_fx_rate WHERE currency = ? AND rate_date = ?', [$currency, $date])->getRowArray();
        if ($r) {
            return ['rate' => (string) $r['rate'], 'source' => $r['source']];
        }
        if ($currency === 'USD') {
            try {
                $s = $db->query('SELECT rate FROM daily_usd_rate WHERE rate_date = ?', [$date])->getRowArray();
                if ($s) {
                    return ['rate' => (string) $s['rate'], 'source' => 'system daily USD rate'];
                }
            } catch (\Throwable $e) {
            }
        }
        return null;
    }

    /** Enter or change a day's rate. A change must say which version it changes ($version, from the list) and why. */
    public static function saveRate($db, string $currency, string $date, string $rate, string $source, string $note, string $user, ?int $version = null): array
    {
        $currency = strtoupper($currency);
        if (!in_array($currency, ['USD', 'JPY'], true)) {
            return [400, 'Rates are kept for USD and JPY (LKR is always 1).'];
        }
        if (!self::validDate($date)) {
            return [400, 'Give a valid date.'];
        }
        if (!preg_match('/^\d{1,6}(\.\d{1,6})?$/', trim($rate)) || (float) $rate <= 0) {
            return [400, 'Give the rate as LKR per one ' . $currency . ' (a positive number, up to 6 decimals).'];
        }
        $source = trim($source);
        if ($source === '') {
            return [400, 'Say where the rate comes from (e.g. bank buying rate on the statement, Central Bank).'];
        }
        $prev = $db->query('SELECT * FROM brc_fx_rate WHERE currency = ? AND rate_date = ?', [$currency, $date])->getRowArray();
        if ($prev && mb_strlen(trim($note)) < 5) {
            return [400, 'A rate already exists for that day; give the reason for changing it (at least 5 characters).'];
        }
        if ($prev && $version !== null && (int) $prev['version'] !== $version) {
            return [409, 'This rate was changed by ' . ($prev['updated_by'] ?: $prev['created_by']) . ' at ' . ($prev['updated_at'] ?: $prev['created_at']) . ' after you opened it (now ' . rtrim(rtrim((string) $prev['rate'], '0'), '.') . '). Reload and check before changing it.'];
        }
        if ($prev && $version === null) {
            return [409, 'A rate already exists for that day; open it from the list (Change) so the change is made on the version you saw.'];
        }
        $n = 0;
        $err = self::tx($db, static function () use ($db, $prev, $currency, $date, $rate, $source, $note, $user, &$n) {
            if ($prev) {
                $db->query('UPDATE brc_fx_rate SET rate = ?, source = ?, note = ?, updated_by = ?, updated_at = ?, version = version + 1 WHERE id = ? AND version = ?', [trim($rate), mb_substr($source, 0, 40), mb_substr($note, 0, 200) ?: null, $user, self::now(), (int) $prev['id'], (int) $prev['version']]);
                if ($db->affectedRows() !== 1) {
                    return [409, 'Someone else changed this rate a moment ago; reload.'];
                }
            } elseif (!self::ins($db, 'brc_fx_rate', ['currency' => $currency, 'rate_date' => $date, 'rate' => trim($rate), 'source' => mb_substr($source, 0, 40), 'note' => mb_substr($note, 0, 200) ?: null, 'created_by' => $user, 'created_at' => self::now()])) {
                return [409, 'Someone else entered a rate for that day a moment ago; reload and check it.'];
            }
            $n = self::revalueLines($db, $currency, $date);
            return null;
        });
        if ($err) {
            return $err;
        }
        self::audit($db, $prev ? 'brc_rate_changed' : 'brc_rate_entered', 'brc_fx', $currency . ' ' . $date, $prev, ['rate' => trim($rate), 'source' => $source, 'lines_revalued' => $n], $note);
        return [200, ($prev ? 'Rate changed' : 'Rate saved') . ($n ? "; LKR equivalents of {$n} statement line(s) recalculated." : '.') . ($prev ? ' Daily records already reviewed for the days it affects are flagged for re-review.' : '')];
    }

    /** Recompute the LKR equivalent of the statement lines of one currency and day (the lines' own amounts never change). */
    public static function revalueLines($db, string $currency, string $date): int
    {
        $r = self::rate($db, $currency, $date);
        $lines = $db->query('SELECT id, money_in, money_out FROM brc_line WHERE currency = ? AND txn_date = ?', [$currency, $date])->getResultArray();
        foreach ($lines as $l) {
            $amt = self::cents($l['money_in']) - self::cents($l['money_out']);
            $lkr = $r ? self::toLkr($amt, $r['rate']) : null;
            $db->query('UPDATE brc_line SET fx_rate = ?, fx_source = ?, lkr_amount = ? WHERE id = ?', [$r['rate'] ?? null, $r['source'] ?? null, $lkr === null ? null : self::dec($lkr), (int) $l['id']]);
        }
        return count($lines);
    }

    // ---------------------------------------------------------------- the book side (ledger, read only)

    /**
     * Ledger rows of the bank ledger account between two dates (inclusive). Each row gets a stable key "ID.n" (n = 1 unless
     * the same entry ID has several rows on this account) and a fingerprint of its contents, to notice later changes.
     */
    public static function bookRows($db, string $ledger, string $from, string $to): array
    {
        $rows = $db->query('SELECT ID, L_Code, Date, Dr, Cr, Discription, Chk_No, Invoice_Num, Recpt_No, payee, index_no, Dt FROM account_runing WHERE L_Code = ? AND Date BETWEEN ? AND ? ORDER BY Date, ID', [$ledger, $from, $to])->getResultArray();
        return self::keyRows($db, $ledger, $rows);
    }

    /** The rows of the given entry IDs on the ledger account, keyed "ID.n" the same way as bookRows(). */
    public static function bookRowsForIds($db, string $ledger, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $rows = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $in = implode(',', $chunk);
            $rows = array_merge($rows, $db->query("SELECT ID, L_Code, Date, Dr, Cr, Discription, Chk_No, Invoice_Num, Recpt_No, payee, index_no, Dt FROM account_runing WHERE L_Code = ? AND ID IN ({$in}) ORDER BY Date, ID", [$ledger])->getResultArray());
        }
        $out = [];
        foreach (self::keyRows($db, $ledger, $rows) as $r) {
            $out[$r['key']] = $r;
        }
        return $out;
    }

    private static function keyRows($db, string $ledger, array $rows): array
    {
        if (!$rows) {
            return [];
        }
        $ids = array_values(array_unique(array_map(static fn ($r) => (int) $r['ID'], $rows)));
        $multi = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $in = implode(',', $chunk);
            foreach ($db->query("SELECT ID FROM account_runing WHERE L_Code = ? AND ID IN ({$in}) GROUP BY ID HAVING COUNT(*) > 1", [$ledger])->getResultArray() as $m) {
                $multi[(int) $m['ID']] = true;
            }
        }
        $ord = [];
        if ($multi) {
            $in = implode(',', array_keys($multi));
            $all = $db->query("SELECT ID, Date, Dr, Cr, Discription, Dt FROM account_runing WHERE L_Code = ? AND ID IN ({$in}) ORDER BY ID, Date, Dr, Cr, Discription, Dt", [$ledger])->getResultArray();
            $n = [];
            foreach ($all as $r) {
                $n[$r['ID']] = ($n[$r['ID']] ?? 0) + 1;
                $ord[$r['ID'] . '|' . self::fp($r)][] = $n[$r['ID']];
            }
        }
        $used = [];
        $out = [];
        foreach ($rows as $r) {
            $fp = self::fp($r);
            $k = $r['ID'] . '|' . $fp;
            if (isset($multi[(int) $r['ID']])) {
                $i = $used[$k] ?? 0;
                $used[$k] = $i + 1;
                $nth = $ord[$k][$i] ?? 1;
            } else {
                $nth = 1;
            }
            $out[] = ['key' => $r['ID'] . '.' . $nth, 'id' => (int) $r['ID'], 'date' => $r['Date'], 'dr' => $r['Dr'], 'cr' => $r['Cr'],
                'amount' => self::dec(self::cents($r['Dr']) - self::cents($r['Cr'])), 'description' => $r['Discription'], 'pv' => (string) $r['Recpt_No'],
                'chk_no' => $r['Chk_No'], 'invoice' => $r['Invoice_Num'], 'payee' => $r['payee'], 'tour' => $r['index_no'], 'entered' => $r['Dt'], 'fp' => $fp];
        }
        return $out;
    }

    public static function fp(array $r): string
    {
        return substr(sha1(implode('|', [$r['Date'], $r['Dr'], $r['Cr'], $r['Discription'], $r['Dt'] ?? ''])), 0, 16);
    }

    /** Book balance of the ledger account at the end of a day (rows dated up to and including it). */
    public static function bookBalance($db, string $ledger, string $date): int
    {
        $r = $db->query('SELECT COALESCE(SUM(Dr), 0) AS dr, COALESCE(SUM(Cr), 0) AS cr FROM account_runing WHERE L_Code = ? AND Date <= ?', [$ledger, $date])->getRowArray();
        return self::cents($r['dr']) - self::cents($r['cr']);
    }

    /** The latest ledger posting date and entry time of the account (shows how current the book is). */
    public static function bookFreshness($db, string $ledger): array
    {
        $r = $db->query('SELECT MAX(Dt) AS last_entered FROM account_runing WHERE L_Code = ?', [$ledger])->getRowArray();
        $g = $db->query('SELECT MAX(Dt) AS last_entered FROM account_runing')->getRowArray();
        return ['account_last_entered' => $r['last_entered'] ?? null, 'ledger_last_entered' => $g['last_entered'] ?? null];
    }

    /** One fingerprint of every ledger row of the account dated up to the day (count, totals and a hash of each row). */
    public static function ledgerFingerprint($db, string $ledger, string $date): string
    {
        $r = $db->query("SELECT COUNT(*) AS n, COALESCE(SUM(Dr), 0) AS dr, COALESCE(SUM(Cr), 0) AS cr,
            COALESCE(SUM(CAST(CONV(SUBSTRING(MD5(CONCAT_WS('|', ID, Date, Dr, Cr, Discription, IFNULL(Dt, ''), IFNULL(Recpt_No, ''), IFNULL(Chk_No, ''))), 1, 15), 16, 10) AS DECIMAL(30,0))), 0) AS h
            FROM account_runing WHERE L_Code = ? AND Date <= ?", [$ledger, $date])->getRowArray();
        return sha1(implode('|', [$r['n'], $r['dr'], $r['cr'], $r['h']]));
    }

    // ---------------------------------------------------------------- the bank side (accepted statements)

    public static function statements($db, int $accountId, ?string $status = null): array
    {
        $sql = 'SELECT * FROM brc_statement WHERE bank_account_id = ?' . ($status ? ' AND status = ?' : '') . ' ORDER BY period_from, id';
        return $db->query($sql, $status ? [$accountId, $status] : [$accountId])->getResultArray();
    }

    /** Coverage of [from, to] by accepted statements: gaps, overlaps and opening/closing breaks between consecutive statements. */
    public static function coverage($db, int $accountId, string $from, string $to): array
    {
        $st = array_values(array_filter(self::statements($db, $accountId, 'accepted'), static fn ($s) => $s['period_to'] >= $from && $s['period_from'] <= $to));
        $issues = [];
        $cursor = $from;
        $prev = null;
        foreach ($st as $s) {
            if ($s['period_from'] > $cursor) {
                $issues[] = "No accepted statement for {$cursor} to " . date('Y-m-d', strtotime($s['period_from'] . ' -1 day')) . '.';
            }
            if ($prev && $s['period_from'] <= $prev['period_to']) {
                $issues[] = "Statements {$prev['id']} and {$s['id']} overlap.";
            }
            if ($prev && self::cents($s['opening_balance']) !== self::cents($prev['closing_balance'])) {
                $issues[] = "Statement {$s['id']} opens at {$s['opening_balance']} but statement {$prev['id']} closed at {$prev['closing_balance']}.";
            }
            $next = date('Y-m-d', strtotime($s['period_to'] . ' +1 day'));
            $cursor = max($cursor, $next);
            $prev = $s;
        }
        if ($cursor <= $to) {
            $issues[] = $st ? "No accepted statement after {$prev['period_to']} (needed up to {$to})." : "No accepted statement for {$from} to {$to}.";
        }
        return ['statements' => $st, 'issues' => $issues, 'complete' => !$issues];
    }

    /** Accepted statement lines of the account between two dates, with their current reconciliation state. */
    public static function bankLines($db, int $accountId, string $from, string $to): array
    {
        return $db->query("SELECT l.* FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted'
            WHERE l.bank_account_id = ? AND l.txn_date BETWEEN ? AND ? ORDER BY l.txn_date, l.statement_id, l.line_no", [$accountId, $from, $to])->getResultArray();
    }

    /** Bank balance (account currency, cents) at the end of a day from the accepted statement covering it; null if not covered. */
    public static function bankBalance($db, int $accountId, string $date): ?array
    {
        $s = $db->query("SELECT * FROM brc_statement WHERE bank_account_id = ? AND status = 'accepted' AND period_from <= ? AND period_to >= ? ORDER BY id DESC LIMIT 1", [$accountId, $date, $date])->getRowArray();
        if (!$s) {
            return null;
        }
        $sum = $db->query('SELECT COALESCE(SUM(money_in), 0) AS i, COALESCE(SUM(money_out), 0) AS o FROM brc_line WHERE statement_id = ? AND txn_date <= ?', [(int) $s['id'], $date])->getRowArray();
        $derived = self::cents($s['opening_balance']) + self::cents($sum['i']) - self::cents($sum['o']);
        $last = $db->query('SELECT balance FROM brc_line WHERE statement_id = ? AND txn_date <= ? AND balance IS NOT NULL ORDER BY txn_date DESC, line_no DESC LIMIT 1', [(int) $s['id'], $date])->getRowArray();
        $printed = $last ? self::cents($last['balance']) : null;
        return ['statement_id' => (int) $s['id'], 'derived' => $derived, 'printed' => $printed, 'balance' => $printed ?? $derived, 'agrees' => $printed === null || $printed === $derived];
    }

    // ---------------------------------------------------------------- the daily reconciliation

    /**
     * Everything for one account at the end of one day: bank and book balances kept separate, the opening difference, every
     * reconciling item by kind, the explanation, the unexplained remainder, the corrections not yet posted, the status,
     * the fingerprints of what it was computed from and (unless $withReview is false) the state of the day's record.
     * Read only.
     */
    public static function day($db, int $accountId, string $date, bool $withReview = true): array
    {
        $acct = self::account($db, $accountId);
        if (!$acct) {
            return ['ok' => false, 'message' => 'Unknown bank account.'];
        }
        $out = ['ok' => true, 'account' => $acct, 'date' => $date, 'currency' => $acct['currency'], 'reasons' => []];
        $map = $acct['map_state'] === 'confirmed' ? $acct['map'] : null;
        if (!$map) {
            $out['status_code'] = 'not_set_up';
            $out['status_label'] = self::STATUS_LABELS['not_set_up'];
            $out['reasons'][] = $acct['map_state'] === 'changed' ? 'The account changed in the master after its mapping was confirmed; the mapping must be confirmed again.' : 'The ledger mapping has not been confirmed.';
            return $out;
        }
        $start = $map['rec_start'];
        $ledger = $map['ledger_code'];
        $cur = $acct['currency'];
        $out['rec_start'] = $start;
        $out['ledger_code'] = $ledger;
        if ($date < $start) {
            $out['status_code'] = 'insufficient';
            $out['status_label'] = self::STATUS_LABELS['insufficient'];
            $out['reasons'][] = "Reconciliation of this account starts on {$start}.";
            return $out;
        }
        $insufficient = [];
        $cov = self::coverage($db, $accountId, $start, $date);
        $out['coverage'] = ['statements' => array_map(static fn ($s) => ['id' => (int) $s['id'], 'from' => $s['period_from'], 'to' => $s['period_to'], 'opening' => $s['opening_balance'], 'closing' => $s['closing_balance']], $cov['statements']), 'issues' => $cov['issues']];
        foreach ($cov['issues'] as $i) {
            $insufficient[] = $i;
        }
        $first = $cov['statements'][0] ?? null;
        $startRate = self::rate($db, $cur, $start);
        $endRate = self::rate($db, $cur, $date);
        if (!$startRate) {
            $insufficient[] = "No {$cur} rate for {$start} (the first day).";
        }
        if (!$endRate) {
            $insufficient[] = "No {$cur} rate for {$date}.";
        }
        // bank side (account currency) and its LKR value
        $bankOpen = ($first && $first['period_from'] === $start) ? self::cents($first['opening_balance']) : null;
        if ($first && $first['period_from'] !== $start) {
            $insufficient[] = "The first accepted statement starts on {$first['period_from']}, not on the first day {$start}.";
        }
        $bal = self::bankBalance($db, $accountId, $date);
        if ($bal && !$bal['agrees']) {
            $insufficient[] = "Statement {$bal['statement_id']}: the printed balance at {$date} does not agree with opening + lines.";
        }
        $bankClose = $bal['balance'] ?? null;
        $out['bank'] = [
            'opening' => $bankOpen === null ? null : self::dec($bankOpen), 'closing' => $bankClose === null ? null : self::dec($bankClose),
            'opening_rate' => $startRate['rate'] ?? null, 'opening_rate_source' => $startRate['source'] ?? null, 'closing_rate' => $endRate['rate'] ?? null, 'closing_rate_source' => $endRate['source'] ?? null,
            'opening_lkr' => ($bankOpen !== null && $startRate) ? self::dec(self::toLkr($bankOpen, $startRate['rate'])) : null,
            'closing_lkr' => ($bankClose !== null && $endRate) ? self::dec(self::toLkr($bankClose, $endRate['rate'])) : null,
        ];
        // book side (LKR)
        $bookOpen = self::bookBalance($db, $ledger, date('Y-m-d', strtotime($start . ' -1 day')));
        $bookClose = self::bookBalance($db, $ledger, $date);
        $out['book'] = ['opening' => self::dec($bookOpen), 'closing' => self::dec($bookClose), 'currency' => 'LKR'] + self::bookFreshness($db, $ledger);

        // items in the window
        $lines = self::bankLines($db, $accountId, $start, $date);
        $book = self::bookRows($db, $ledger, $start, $date);
        $act = self::activeLinks($db, $accountId, $ledger);
        $lineById = [];
        foreach ($lines as $l) {
            $lineById[(int) $l['id']] = $l;
        }
        $bookByKey = [];
        foreach ($book as $b) {
            $bookByKey[$b['key']] = $b;
        }
        $items = ['matched' => [], 'bank_unmatched' => [], 'book_unmatched' => [], 'timing' => [], 'duplicate' => [], 'excluded' => [], 'charges' => [], 'matched_later' => [],
            'partial' => [], 'differences' => [], 'opening' => []];
        $sum = ['bank_unmatched' => 0, 'book_unmatched' => 0, 'bank_lkr_window' => 0];
        $tok = ['matches' => [], 'items' => []];   // what the day's explanation rests on (for the record's fingerprint)
        $rateMissing = 0;
        foreach ($lines as $l) {
            if ($l['lkr_amount'] === null) {
                $rateMissing++;
            } else {
                $sum['bank_lkr_window'] += self::cents($l['lkr_amount']);
            }
        }
        // matches: "closed" when every item is dated on or before the day; a match that reaches past the day leaves its
        // earlier side outstanding (timing) for this day
        $closedLines = [];
        $closedBook = [];
        $openMatchLine = [];
        $openMatchBook = [];
        $matchDiff = 0;
        $partialSum = 0;
        $preStart = [];     // ledger rows dated before the first day, cleared by a closed match: key => [amount, match, cleared_on, snapshot fp]
        $changed = [];
        $diffOpen = 0;
        foreach ($act['matches'] as $m) {
            $inWindow = false;
            $maxDate = '0000-00-00';
            foreach ($m['items'] as $it) {
                $maxDate = max($maxDate, $it['item_date']);
                if ($it['item_date'] >= $start && $it['item_date'] <= $date) {
                    $inWindow = true;
                }
            }
            if (!$inWindow) {
                continue;
            }
            if ($maxDate > $date) {
                foreach ($m['items'] as $it) {
                    if ($it['side'] === 'bank') {
                        $openMatchLine[(int) $it['line_id']] = (int) $m['id'];
                    } else {
                        $openMatchBook[$it['book_key']] = (int) $m['id'];
                    }
                }
                continue;
            }
            $bankLkr = 0;
            $bookSum = 0;
            $bankDates = [];
            $pre = [];
            foreach ($m['items'] as $it) {
                if ($it['side'] === 'bank') {
                    $closedLines[(int) $it['line_id']] = true;
                    $ln = $lineById[(int) $it['line_id']] ?? null;
                    $bankLkr += ($ln && $ln['lkr_amount'] !== null) ? self::cents($ln['lkr_amount']) : 0;
                    $bankDates[] = $it['item_date'];
                } else {
                    $snap = json_decode((string) $it['snapshot'], true) ?: [];
                    if ($it['item_date'] < $start) {
                        $pre[] = ['key' => $it['book_key'], 'amount' => self::cents($it['amount']), 'fp' => $snap['fp'] ?? null];
                        $bookSum += self::cents($it['amount']);
                        continue;
                    }
                    $closedBook[$it['book_key']] = true;
                    $cur_ = $bookByKey[$it['book_key']] ?? null;
                    if (!$cur_ || (isset($snap['fp']) && $snap['fp'] !== $cur_['fp'])) {
                        $changed[] = $it['book_key'];
                    }
                    $bookSum += $cur_ ? self::cents($cur_['amount']) : self::cents($it['amount']);
                }
            }
            foreach ($pre as $p) {
                $preStart[$p['key']] = ['amount' => $p['amount'], 'match' => (int) $m['id'], 'cleared_on' => $bankDates ? max($bankDates) : $maxDate, 'fp' => $p['fp']];
            }
            $d = $bankLkr - $bookSum;
            $m['difference_now_lkr'] = self::dec($d);
            if (($m['kind'] ?? 'full') === 'partial') {
                $partialSum += $d;
                $items['partial'][] = ['match_id' => (int) $m['id'], 'remainder_lkr' => self::dec($d), 'expected_date' => $m['expected_date'], 'note' => $m['diff_note'] ?: $m['note'],
                    'waiting_for' => $d < 0 ? 'the rest on the statement' : 'the rest in the ledger'];
            } else {
                $matchDiff += $d;
                if ($d !== 0) {
                    $reason = $m['diff_reason'];
                    $note = trim((string) ($m['diff_note'] ?? ''));
                    $explained = ($reason === 'rounding' && abs($d) <= 100) || ($reason !== null && $reason !== 'rounding' && mb_strlen($note) >= 5);
                    $items['differences'][] = ['match_id' => (int) $m['id'], 'amount_lkr' => self::dec($d), 'reason' => $reason, 'note' => $note ?: null, 'explained' => $explained,
                        'noted_by' => $m['diff_noted_by'] ?? null, 'recorded_lkr' => $m['difference_lkr'], 'changed_since_match' => self::cents($m['difference_lkr']) !== $d];
                    if (!$explained) {
                        $diffOpen++;
                    }
                }
            }
            // the fingerprint describes the explanation as at this day, not the match record: a partly cleared group is
            // "outstanding" like an item matched after the day, so completing it later does not change this day
            $keys = array_map(static fn ($i) => $i['side'] === 'bank' ? 'L' . $i['line_id'] : 'B' . $i['book_key'], $m['items']);
            sort($keys);
            if (($m['kind'] ?? 'full') === 'partial') {
                foreach ($keys as $k) {
                    $tok['items'][] = $k . '=O';
                }
            } else {
                $tok['matches'][] = [$keys, self::dec($d), $d !== 0 ? $m['diff_reason'] : null, $d !== 0 ? ($m['diff_note'] ?? null) : null];
            }
            $items['matched'][] = $m;
        }
        $corrTok = static fn ($c) => $c ? 'C' . $c['id'] . (in_array($c['state'], ['rejected', 'withdrawn'], true) ? 'x' : '') : '';
        // a correction posted with an entry dated after this day had not corrected the ledger yet at this day
        $asAt = static function (?array $c) use ($date) {
            if ($c && $c['state'] === 'posted' && ($c['posted_entry_date'] ?? '') > $date) {
                $c['state'] = 'approved';
                $c['label'] = 'Approved — posted after this date (entry ' . $c['posted_ref'] . ' dated ' . $c['posted_entry_date'] . '); not corrected at this date';
            }
            return $c;
        };
        foreach ($lines as $l) {
            if (isset($closedLines[(int) $l['id']])) {
                continue;
            }
            $amt = self::cents($l['money_in']) - self::cents($l['money_out']);
            $lkr = $l['lkr_amount'] === null ? null : self::cents($l['lkr_amount']);
            $view = ['line_id' => (int) $l['id'], 'date' => $l['txn_date'], 'description' => $l['description'], 'reference' => $l['reference'], 'amount' => self::dec($amt), 'amount_lkr' => $lkr === null ? null : self::dec($lkr), 'category' => $l['category'], 'charge_type' => $l['charge_type'], 'state' => $l['state'], 'side' => 'bank'];
            $explained = false;
            $t = 'U';
            if (isset($openMatchLine[(int) $l['id']])) {
                $view['matched_later'] = $openMatchLine[(int) $l['id']];
                $items['matched_later'][] = $view;
                $explained = true;
                $t = 'O';
            } elseif ($mark = $act['line_mark'][(int) $l['id']] ?? null) {
                $view['mark'] = $mark;
                $items[$mark['mark']][] = $view;
                $explained = true;
                $t = $mark['mark'] === 'timing' ? 'O' : 'K' . $mark['mark'];
            } else {
                // a line covered by a correction is explained, and the correction's state (proposed, approved but not
                // posted, posted and verified) is shown with it; a posted entry that changed in the ledger is not
                $corr = $asAt($act['line_correction'][(int) $l['id']] ?? null);
                $view['correction'] = $corr;
                $explained = $corr && $corr['state'] !== 'posted_changed';
                $t = $corr ? $corrTok($corr) : 'U';
                if ($l['category'] === 'charge') {
                    $items['charges'][] = $view;
                }
            }
            $view['explained'] = $explained;
            $items['bank_unmatched'][] = $view;
            $sum['bank_unmatched'] += $lkr ?? 0;
            $tok['items'][] = 'L' . $l['id'] . '=' . $t;
        }
        foreach ($book as $b) {
            if (isset($closedBook[$b['key']])) {
                continue;
            }
            $view = $b + ['side' => 'book'];
            $explained = false;
            $t = 'U';
            if (isset($openMatchBook[$b['key']])) {
                $view['matched_later'] = $openMatchBook[$b['key']];
                $items['matched_later'][] = $view;
                $explained = true;
                $t = 'O';
            } elseif ($mark = $act['book_mark'][$b['key']] ?? null) {
                $view['mark'] = $mark;
                $items[$mark['mark']][] = $view;
                $explained = true;
                $t = $mark['mark'] === 'timing' ? 'O' : 'K' . $mark['mark'];
            }
            if ($corr = $asAt($act['book_correction'][$b['key']] ?? null)) {
                $view['correction'] = $corr;
                $explained = $explained || $corr['state'] !== 'posted_changed';
                $t .= $corrTok($corr);
            }
            if ($pc = $act['book_posted'][$b['key']] ?? null) {
                // this ledger row IS the entry Accounts posted for an approved correction (verified)
                $view['posted_correction'] = $pc;
                $explained = true;
                $t .= 'P' . $pc['id'];
            }
            $view['explained'] = $explained;
            $items['book_unmatched'][] = $view;
            $sum['book_unmatched'] += self::cents($b['amount']);
            $tok['items'][] = 'B' . $b['key'] . '=' . $t;
        }
        // ledger rows before the first day that a match cleared: still the same in the ledger?
        if ($preStart) {
            $ids = array_map(static fn ($k) => (int) explode('.', $k)[0], array_keys($preStart));
            $now = self::bookRowsForIds($db, $ledger, $ids);
            foreach ($preStart as $k => $p) {
                if (!isset($now[$k]) || ($p['fp'] && $now[$k]['fp'] !== $p['fp'])) {
                    $changed[] = $k;
                }
            }
        }
        if ($changed) {
            $insufficient[] = 'Ledger row(s) ' . implode(', ', array_slice(array_unique($changed), 0, 5)) . ' changed or disappeared in the ledger after they were matched; undo those matches and match again.';
        }
        if ($rateMissing) {
            $insufficient[] = "{$rateMissing} statement line(s) have no {$cur} rate for their day, so their LKR value is unknown.";
        }

        // ---- opening difference and its explanation (D10): only accepted opening items explain it; a ledger row from
        // before the first day that clears later is an opening item only once it is proposed and accepted
        $opening = $db->query("SELECT * FROM brc_opening WHERE bank_account_id = ? AND status IN ('proposed', 'accepted') ORDER BY id", [$accountId])->getResultArray();
        $openAccepted = 0;
        $openProposed = 0;
        $acceptedLedger = [];
        $proposedLedger = [];
        $openingChanged = [];
        $ledgerOpenRows = [];
        $ledIds = [];
        foreach ($opening as $o) {
            if (($o['source'] ?? 'manual') === 'ledger' && $o['book_key']) {
                $ledIds[] = (int) explode('.', $o['book_key'])[0];
            }
        }
        if ($ledIds) {
            $ledgerOpenRows = self::bookRowsForIds($db, $ledger, $ledIds);
        }
        foreach ($opening as $o) {
            $isLedger = ($o['source'] ?? 'manual') === 'ledger' && $o['book_key'];
            $view = ['id' => (int) $o['id'], 'source' => $o['source'] ?? 'manual', 'book_key' => $o['book_key'] ?? null, 'item_date' => $o['item_date'] ?? null, 'description' => $o['description'],
                'amount' => $o['amount'], 'status' => $o['status'], 'created_by' => $o['created_by'], 'reviewed_by' => $o['reviewed_by']];
            if ($o['status'] === 'accepted') {
                $openAccepted += self::cents($o['amount']);
                if ($isLedger) {
                    $acceptedLedger[$o['book_key']] = (int) $o['id'];
                    $row = $ledgerOpenRows[$o['book_key']] ?? null;
                    if (!$row || ($o['book_fp'] && $row['fp'] !== $o['book_fp'])) {
                        $openingChanged[] = $o['book_key'];
                    }
                    if (isset($preStart[$o['book_key']])) {
                        $view['state'] = 'cleared';
                        $view['cleared_on'] = $preStart[$o['book_key']]['cleared_on'];
                        $view['match_id'] = $preStart[$o['book_key']]['match'];
                    } else {
                        $view['state'] = 'outstanding';
                    }
                } else {
                    $view['state'] = 'accepted';
                }
            } else {
                $openProposed += self::cents($o['amount']);
                $view['state'] = 'proposed';
                if ($isLedger) {
                    $proposedLedger[$o['book_key']] = (int) $o['id'];
                }
            }
            $items['opening'][] = $view;
        }
        $preAcc = 0;
        $preUnacc = 0;
        foreach ($preStart as $k => $p) {
            if (isset($acceptedLedger[$k])) {
                $preAcc += $p['amount'];
            } else {
                $preUnacc += $p['amount'];
                $insufficient[] = "Ledger row {$k} (booked before the first day {$start}) cleared on the statement on {$p['cleared_on']}, but it is not an accepted opening item"
                    . (isset($proposedLedger[$k]) ? ' (proposed as opening item #' . $proposedLedger[$k] . ', awaiting acceptance)' : '') . '. Clearing later does not by itself explain the opening difference.';
            }
        }
        if ($openingChanged) {
            $insufficient[] = 'Opening item ledger row(s) ' . implode(', ', array_slice($openingChanged, 0, 5)) . ' changed or disappeared in the ledger after they were proposed; the opening item must be reviewed again.';
        }
        $openDiff = ($out['bank']['opening_lkr'] !== null) ? self::cents($out['bank']['opening_lkr']) - $bookOpen : null;
        $openRemaining = $openDiff === null ? null : $openDiff - $openAccepted;
        // revaluation of a foreign-currency balance: closing at the day's rate − (opening at the first day's rate + lines at their own rates)
        $reval = 0;
        if ($cur !== 'LKR' && $bankOpen !== null && $bankClose !== null && $startRate && $endRate && !$rateMissing) {
            $reval = self::toLkr($bankClose, $endRate['rate']) - (self::toLkr($bankOpen, $startRate['rate']) + $sum['bank_lkr_window']);
        }
        $diff = ($out['bank']['closing_lkr'] !== null) ? self::cents($out['bank']['closing_lkr']) - $bookClose : null;
        $explainedTotal = ($openRemaining === null) ? null : $openRemaining + $openAccepted + $preAcc + $preUnacc + $matchDiff + $partialSum + $sum['bank_unmatched'] - $sum['book_unmatched'] + $reval;
        $unexplained = ($diff === null || $explainedTotal === null) ? null : $diff - $explainedTotal;
        $out['reconciliation'] = [
            'difference_lkr' => $diff === null ? null : self::dec($diff),
            'opening_difference_lkr' => $openDiff === null ? null : self::dec($openDiff),
            'opening_items_accepted' => self::dec($openAccepted), 'opening_items_proposed' => self::dec($openProposed),
            'opening_items_cleared' => self::dec($preAcc),
            'opening_items_outstanding' => self::dec($openAccepted + $preAcc),
            'pre_start_cleared_not_accepted' => self::dec($preUnacc),
            'opening_remaining' => $openRemaining === null ? null : self::dec($openRemaining),
            'bank_items_not_in_book' => self::dec($sum['bank_unmatched']),
            'book_items_not_on_statement' => self::dec(-$sum['book_unmatched']),
            'match_differences' => self::dec($matchDiff),
            'partial_remainders' => self::dec($partialSum),
            'revaluation' => self::dec($reval),
            'revaluation_rate' => $endRate['rate'] ?? null, 'revaluation_rate_source' => $endRate['source'] ?? null,
            'unexplained_lkr' => $unexplained === null ? null : self::dec($unexplained),
        ];
        $out['opening_items'] = $opening;
        if ($openRemaining !== null && $openRemaining !== 0) {
            $insufficient[] = 'The opening difference of ' . self::fmt($openRemaining) . ' LKR on ' . $start . ' is not yet explained by accepted opening items'
                . ($openProposed ? ' (' . self::fmt($openProposed) . ' proposed, awaiting acceptance)' : '') . '.';
        }
        if ($unexplained !== null && $unexplained !== 0) {
            $insufficient[] = 'An unexplained remainder of ' . self::fmt($unexplained) . ' LKR: a statement or the ledger is incomplete or changed.';
        }
        $out['items'] = $items;
        $open = $diffOpen;
        foreach (['bank_unmatched', 'book_unmatched'] as $k) {
            foreach ($items[$k] as $it) {
                $open += $it['explained'] ? 0 : 1;
            }
        }
        // corrections behind this day's explanations, by state: the ledger is corrected only when an entry is posted and verified
        $pending = self::pendingFrom($items);
        $out['pending_postings'] = $pending;
        $out['counts'] = ['bank_lines' => count($lines), 'book_rows' => count($book), 'matched' => count($items['matched']), 'bank_unmatched' => count($items['bank_unmatched']),
            'book_unmatched' => count($items['book_unmatched']), 'timing' => count($items['timing']), 'duplicate' => count($items['duplicate']),
            'excluded' => count($items['excluded']), 'charges' => count($items['charges']), 'matched_later' => count($items['matched_later']), 'partial' => count($items['partial']),
            'differences' => count($items['differences']), 'differences_unexplained' => $diffOpen, 'opening_outstanding' => count(array_filter($items['opening'], static fn ($o) => $o['state'] === 'outstanding')),
            'unexplained_items' => $open];
        if ($insufficient) {
            $code = 'insufficient';
        } elseif ($open > 0) {
            $code = 'open_items';
            $out['reasons'][] = "{$open} item(s) still to match, classify or explain" . ($diffOpen ? " (of which {$diffOpen} difference(s) inside matches without an explanation)" : '') . '.';
        } else {
            $code = $pending['not_corrected'] > 0 ? 'explained_pending' : 'explained';
        }
        if ($cur !== 'LKR' && $reval !== 0) {
            $out['reasons'][] = 'Revaluation of the ' . $cur . ' balance at the day\'s rate ' . (isset($endRate['rate']) ? rtrim(rtrim((string) $endRate['rate'], '0'), '.') : '—') . ' (' . ($endRate['source'] ?? '—') . '): ' . self::fmt($reval) . ' LKR — an exchange-rate difference that the preparer explains in the daily record; not posted.';
        }
        $out['reasons'] = array_merge($insufficient, $out['reasons']);
        // fingerprints of what this result rests on
        $out['inputs'] = self::inputsOf($db, $accountId, $map, $date, $start, $tok, $opening, $pending);
        $out['inputs_hash'] = self::materialHash($out['inputs']);
        $out['status_code'] = $code;
        if ($withReview) {
            $last = $db->query('SELECT * FROM brc_day WHERE bank_account_id = ? AND rec_date = ? ORDER BY id DESC LIMIT 1', [$accountId, $date])->getRowArray();
            $out['review'] = self::reviewState($last, $out);
            if ($out['review']['state'] === 'reviewed' && in_array($code, ['explained', 'explained_pending'], true)) {
                $code = $code === 'explained' ? 'reviewed' : 'reviewed_pending';
            }
            $out['last_record'] = $last ? array_diff_key($last, ['snapshot' => 1, 'inputs_json' => 1, 'pending_json' => 1]) : null;
        }
        $out['status_code'] = $code;
        $out['status_label'] = self::STATUS_LABELS[$code] . (in_array($code, ['explained_pending', 'reviewed_pending'], true) ? ' — ' . $pending['summary'] : '');
        return $out;
    }

    /** The corrections behind a day's explained items, grouped by state, with totals and a one-line summary. */
    private static function pendingFrom(array $items): array
    {
        $seen = [];
        foreach (['bank_unmatched', 'book_unmatched'] as $k) {
            foreach ($items[$k] as $it) {
                foreach (['correction', 'posted_correction'] as $f) {
                    if (!empty($it[$f])) {
                        $seen[(int) $it[$f]['id']] = $it[$f];
                    }
                }
            }
        }
        $g = ['proposed' => [], 'approved' => [], 'noted' => [], 'posted' => [], 'posted_changed' => [], 'rejected' => [], 'withdrawn' => []];
        foreach ($seen as $c) {
            $g[$c['state']][] = $c;
        }
        $tot = static fn ($list) => self::dec(array_sum(array_map(static fn ($c) => self::cents($c['amount']), $list)));
        $parts = [];
        if ($g['proposed']) {
            $parts[] = count($g['proposed']) . ' correction(s) awaiting approval (' . self::fmt(self::cents($tot($g['proposed']))) . ')';
        }
        if ($g['approved']) {
            $later = count(array_filter($g['approved'], static fn ($c) => !empty($c['posted_ref']) && str_contains((string) ($c['label'] ?? ''), 'posted after this date')));
            $parts[] = count($g['approved']) . ' approved but not posted (' . self::fmt(self::cents($tot($g['approved']))) . ')' . ($later ? ' — ' . $later . ' of them posted after this date' : '');
        }
        if ($g['noted']) {
            $parts[] = count($g['noted']) . ' posting noted but not verified';
        }
        if ($g['posted_changed']) {
            $parts[] = count($g['posted_changed']) . ' posted entry changed in the ledger';
        }
        return ['proposed' => ['count' => count($g['proposed']), 'total' => $tot($g['proposed']), 'ids' => array_column($g['proposed'], 'id')],
            'approved' => ['count' => count($g['approved']), 'total' => $tot($g['approved']), 'ids' => array_column($g['approved'], 'id')],
            'posted' => ['count' => count($g['posted']), 'total' => $tot($g['posted']), 'ids' => array_column($g['posted'], 'id')],
            'posted_changed' => ['count' => count($g['posted_changed']), 'ids' => array_column($g['posted_changed'], 'id')],
            'noted' => ['count' => count($g['noted']), 'ids' => array_column($g['noted'], 'id')],
            'not_corrected' => count($g['proposed']) + count($g['approved']) + count($g['noted']) + count($g['posted_changed']),
            'summary' => $parts ? implode('; ', $parts) : 'none', 'list' => array_values($seen)];
    }

    /**
     * Fingerprints of what a day's result rests on: the mapping, the accepted statement lines up to the day (and their LKR
     * values), the rates, every ledger row of the account up to the day, the closed matches and their explanations, how
     * each unmatched item is explained, the opening items — and, separately, the progress of the corrections.
     */
    private static function inputsOf($db, int $accountId, array $map, string $date, string $start, array $tok, array $opening, array $pending): array
    {
        $cur = strtoupper((string) $map['currency']);
        $st = $db->query("SELECT id, period_from, period_to, opening_balance, closing_balance FROM brc_statement WHERE bank_account_id = ? AND status = 'accepted' AND period_from <= ? AND period_to >= ? ORDER BY id", [$accountId, $date, $start])->getResultArray();
        $ln = $db->query("SELECT l.id, l.txn_date, l.money_in, l.money_out, l.balance, l.category, l.fx_rate, l.lkr_amount FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted'
            WHERE l.bank_account_id = ? AND l.txn_date BETWEEN ? AND ? ORDER BY l.id", [$accountId, $start, $date])->getResultArray();
        $rates = [self::rate($db, $cur, $start), self::rate($db, $cur, $date)];
        if ($cur !== 'LKR') {
            $rates[] = $db->query('SELECT rate_date, rate, source FROM brc_fx_rate WHERE currency = ? AND rate_date BETWEEN ? AND ? ORDER BY rate_date', [$cur, $start, $date])->getResultArray();
        }
        sort($tok['items']);
        usort($tok['matches'], static fn ($a, $b) => strcmp(json_encode($a), json_encode($b)));
        $op = array_map(static fn ($o) => [(int) $o['id'], $o['status'], $o['amount'], $o['book_key'] ?? null], $opening);
        $posting = array_map(static fn ($c) => [(int) $c['id'], $c['state']], $pending['list']);
        usort($posting, static fn ($a, $b) => $a[0] <=> $b[0]);
        return [
            'mapping' => sha1(json_encode([(int) $map['id'], $map['ledger_code'], $map['currency'], $map['rec_start']])),
            'statements' => sha1(json_encode([$st, $ln])),
            'rates' => sha1(json_encode($rates)),
            'ledger' => self::ledgerFingerprint($db, (string) $map['ledger_code'], $date),
            'matches' => sha1(json_encode($tok['matches'])),
            'items' => sha1(json_encode($tok['items'])),
            'opening' => sha1(json_encode($op)),
            'postings' => sha1(json_encode($posting)),
        ];
    }

    public static function materialHash(array $inputs): string
    {
        return sha1(json_encode(array_map(static fn ($k) => $inputs[$k] ?? null, self::MATERIAL)));
    }

    /**
     * The state of a day's latest record against the live result: prepared (and still current?), reviewed and current,
     * reviewed but out of date (what changed), returned. The reviewed snapshot itself is never changed.
     */
    public static function reviewState(?array $rec, array $live): array
    {
        if (!$rec) {
            return ['state' => 'none', 'label' => 'No record prepared for this date yet.'];
        }
        $old = json_decode((string) ($rec['inputs_json'] ?? ''), true) ?: null;
        $changed = [];
        $tracked = $old !== null;
        if ($tracked) {
            foreach (array_merge(self::MATERIAL, ['postings']) as $k) {
                if (($old[$k] ?? null) !== ($live['inputs'][$k] ?? null)) {
                    $changed[] = $k;
                }
            }
        }
        $material = array_values(array_intersect($changed, self::MATERIAL));
        $liveCode = $live['status_code'];
        $recCode = $rec['status_code'];
        $resultChanged = ($rec['difference_lkr'] ?? null) !== ($live['reconciliation']['difference_lkr'] ?? null)
            || ($rec['unexplained_lkr'] ?? null) !== ($live['reconciliation']['unexplained_lkr'] ?? null)
            || $recCode !== $liveCode;
        $outdated = $tracked ? (bool) $material : $resultChanged;
        $why = $tracked ? array_map(static fn ($k) => self::COMPONENT_LABELS[$k], $material) : ($resultChanged ? ['the result (recorded before change tracking): status or figures differ'] : []);
        $id = (int) $rec['id'];
        $st = ['record_id' => $id, 'review_status' => $rec['review_status'], 'prepared_by' => $rec['prepared_by'], 'prepared_at' => $rec['prepared_at'], 'reviewed_by' => $rec['reviewed_by'], 'reviewed_at' => $rec['reviewed_at'],
            'tracked' => $tracked, 'changed' => $material, 'changed_labels' => $why, 'postings_progressed' => in_array('postings', $changed, true), 'result_changed' => $resultChanged,
            'recorded_status' => $rec['status_label']];
        if ($rec['review_status'] === 'returned') {
            return $st + ['state' => 'returned', 'label' => "Record #{$id} was returned by {$rec['reviewed_by']}; prepare it again."];
        }
        if ($rec['review_status'] === 'prepared') {
            return $st + ($outdated ? ['state' => 'prepared_outdated', 'label' => "Record #{$id} — changed after it was prepared (" . implode(', ', $why) . '); prepare it again before review.']
                : ['state' => 'prepared', 'label' => "Record #{$id} prepared by {$rec['prepared_by']} — waiting for review."]);
        }
        if ($outdated) {
            return $st + ['state' => 'reviewed_outdated', 'label' => "Changed since review #{$id} (reviewed by {$rec['reviewed_by']}): " . implode(', ', $why) . ' — re-review needed. The reviewed snapshot is kept.'];
        }
        return $st + ['state' => 'reviewed', 'label' => "Reviewed #{$id} by {$rec['reviewed_by']} — current." . (in_array('postings', $changed, true) ? ' Corrections have progressed since (approved or posted).' : '')];
    }

    /** Active matches, marks and correction links of an account, indexed for the day computation. */
    public static function activeLinks($db, int $accountId, ?string $ledger = null): array
    {
        $matches = $db->query("SELECT * FROM brc_match WHERE bank_account_id = ? AND status = 'active' ORDER BY id", [$accountId])->getResultArray();
        $byId = [];
        foreach ($matches as $m) {
            $m['items'] = [];
            $byId[(int) $m['id']] = $m;
        }
        $lineMatch = [];
        $bookMatch = [];
        if ($byId) {
            $in = implode(',', array_keys($byId));
            foreach ($db->query("SELECT * FROM brc_match_item WHERE match_id IN ({$in}) ORDER BY id")->getResultArray() as $it) {
                $byId[(int) $it['match_id']]['items'][] = $it;
                if ($it['side'] === 'bank') {
                    $lineMatch[(int) $it['line_id']] = (int) $it['match_id'];
                } else {
                    $bookMatch[$it['book_key']] = (int) $it['match_id'];
                }
            }
        }
        $lineMark = [];
        $bookMark = [];
        foreach ($db->query("SELECT * FROM brc_mark WHERE bank_account_id = ? AND status = 'active'", [$accountId])->getResultArray() as $mk) {
            if ($mk['side'] === 'bank') {
                $lineMark[(int) $mk['line_id']] = $mk;
            } else {
                $bookMark[$mk['book_key']] = $mk;
            }
        }
        $lineCorr = [];
        $bookCorr = [];
        $bookPosted = [];
        if ($ledger === null) {
            $m = self::activeMap($db, $accountId);
            $ledger = $m['ledger_code'] ?? '';
        }
        foreach ($db->query("SELECT * FROM brc_correction WHERE bank_account_id = ? AND status IN ('proposed', 'approved')", [$accountId])->getResultArray() as $c) {
            $state = Corrections::state($db, $c);
            $info = ['id' => (int) $c['id'], 'status' => $c['status'], 'state' => $state, 'label' => Corrections::STATE_LABELS[$state] ?? $state, 'amount' => $c['amount'], 'posted_ref' => $c['posted_ref'], 'posted_entry_date' => $c['posted_entry_date'] ?? null];
            $rel = json_decode((string) $c['related_json'], true) ?: [];
            foreach ((array) ($rel['line_ids'] ?? []) as $lid) {
                $lineCorr[(int) $lid] = $info;
            }
            foreach ((array) ($rel['book_keys'] ?? []) as $bk) {
                $bookCorr[(string) $bk] = $info;
            }
            if ($state === 'posted' && $c['posted_ref'] && $ledger !== '') {
                foreach (array_keys(self::bookRowsForIds($db, $ledger, [(int) $c['posted_ref']])) as $k) {
                    $bookPosted[$k] = $info;
                }
            }
        }
        return ['matches' => array_values($byId), 'line_match' => $lineMatch, 'book_match' => $bookMatch, 'line_mark' => $lineMark, 'book_mark' => $bookMark, 'line_correction' => $lineCorr, 'book_correction' => $bookCorr, 'book_posted' => $bookPosted];
    }

    /** A compact copy of the day for the record: figures, reasons, every reconciling item with how it is explained. */
    private static function snapshotOf(array $d): array
    {
        $item = static function ($it) {
            $how = 'not explained';
            if (!empty($it['matched_later'])) {
                $how = 'outstanding: matched to an item after this day (match #' . $it['matched_later'] . ')';
            } elseif (!empty($it['mark'])) {
                $how = $it['mark']['mark'] . ($it['mark']['mark'] === 'timing' && $it['mark']['expected_date'] ? ' (expected ' . $it['mark']['expected_date'] . ')' : '') . ': ' . $it['mark']['note'];
            }
            if (!empty($it['correction'])) {
                $how = ($how === 'not explained' ? '' : $how . '; ') . 'correction #' . $it['correction']['id'] . ': ' . $it['correction']['label'];
            }
            if (!empty($it['posted_correction'])) {
                $how = 'the posted entry of correction #' . $it['posted_correction']['id'];
            }
            return ['side' => $it['side'], 'ref' => $it['side'] === 'bank' ? 'line ' . $it['line_id'] : 'ledger ' . $it['key'], 'date' => $it['date'], 'description' => $it['description'],
                'amount_lkr' => $it['side'] === 'bank' ? $it['amount_lkr'] : $it['amount'], 'explained' => $it['explained'], 'how' => $how];
        };
        return ['bank' => $d['bank'], 'book' => $d['book'], 'reconciliation' => $d['reconciliation'], 'counts' => $d['counts'], 'reasons' => $d['reasons'], 'coverage' => $d['coverage'] ?? null,
            'unmatched' => array_map($item, array_merge($d['items']['bank_unmatched'], $d['items']['book_unmatched'])),
            'matches' => array_map(static fn ($m) => ['id' => (int) $m['id'], 'kind' => $m['kind'] ?? 'full', 'difference_lkr' => $m['difference_now_lkr'] ?? $m['difference_lkr'], 'reason' => $m['diff_reason'], 'note' => $m['diff_note'] ?? null,
                'items' => array_map(static fn ($i) => ($i['side'] === 'bank' ? 'line ' . $i['line_id'] : 'ledger ' . $i['book_key']) . ' ' . $i['item_date'] . ' ' . $i['amount'], $m['items'])], $d['items']['matched']),
            'partial' => $d['items']['partial'], 'differences' => $d['items']['differences'], 'opening' => $d['items']['opening'], 'pending_postings' => $d['pending_postings']];
    }

    /** Prepare (record) the day: stores the computed reconciliation as it stands and what it rests on, with who and when. */
    public static function prepareDay($db, int $accountId, string $date, string $note, string $user, string $fxNote = ''): array
    {
        return self::locked($db, $accountId, static function () use ($db, $accountId, $date, $note, $user, $fxNote) {
            $d = self::day($db, $accountId, $date, false);
            if (!$d['ok']) {
                return [404, $d['message']];
            }
            if ($d['status_code'] === 'not_set_up') {
                return [409, 'The account is not set up (mapping not confirmed).'];
            }
            $reval = self::cents($d['reconciliation']['revaluation'] ?? '0');
            if ($d['currency'] !== 'LKR' && $reval !== 0 && mb_strlen(trim($fxNote)) < 5) {
                return [400, 'The ' . $d['currency'] . ' balance has a revaluation of ' . self::fmt($reval) . ' LKR at the day\'s rate ' . ($d['reconciliation']['revaluation_rate'] ?? '—')
                    . '. Explain it in the record (e.g. unrealised exchange difference; month-end entry to be posted by Accounts).'];
            }
            $label = self::STATUS_LABELS[$d['status_code']] . ($d['status_code'] === 'explained_pending' ? ' — ' . $d['pending_postings']['summary'] : '');
            $snap = self::snapshotOf($d);
            if (!self::ins($db, 'brc_day', [
                'bank_account_id' => $accountId, 'rec_date' => $date, 'currency' => $d['currency'],
                'bank_closing' => $d['bank']['closing'] ?? null, 'bank_closing_lkr' => $d['bank']['closing_lkr'] ?? null, 'closing_rate' => $d['bank']['closing_rate'] ?? null,
                'book_closing' => $d['book']['closing'] ?? null, 'difference_lkr' => $d['reconciliation']['difference_lkr'] ?? null, 'unexplained_lkr' => $d['reconciliation']['unexplained_lkr'] ?? null,
                'status_code' => $d['status_code'], 'status_label' => mb_substr($label, 0, 120),
                'snapshot' => json_encode($snap, JSON_UNESCAPED_UNICODE),
                'inputs_json' => json_encode($d['inputs']), 'inputs_hash' => $d['inputs_hash'], 'fx_note' => mb_substr(trim($fxNote), 0, 300) ?: null,
                'pending_json' => json_encode($d['pending_postings'], JSON_UNESCAPED_UNICODE),
                'prepared_by' => $user, 'prepared_at' => self::now(), 'prepare_note' => mb_substr($note, 0, 300) ?: null, 'review_status' => 'prepared',
            ])) {
                return [409, 'The record could not be saved.'];
            }
            $id = (int) $db->insertID();
            self::audit($db, 'brc_day_prepared', 'brc_day', $id, null, ['date' => $date, 'status' => $label, 'difference_lkr' => $d['reconciliation']['difference_lkr'] ?? null, 'unexplained_lkr' => $d['reconciliation']['unexplained_lkr'] ?? null,
                'pending_postings' => $d['pending_postings']['summary'], 'fx_note' => $fxNote ?: null, 'inputs_hash' => $d['inputs_hash']], $note, $d['account']['label']);
            return [200, 'Daily record prepared: ' . $label . '.', $id];
        });
    }

    public static function reviewDay($db, int $dayId, string $decision, string $note, string $user): array
    {
        $r = $db->query('SELECT * FROM brc_day WHERE id = ?', [$dayId])->getRowArray();
        if (!$r) {
            return [404, 'Unknown daily record.'];
        }
        return self::locked($db, (int) $r['bank_account_id'], static function () use ($db, $dayId, $decision, $note, $user) {
            $r = $db->query('SELECT * FROM brc_day WHERE id = ?', [$dayId])->getRowArray();
            $a = self::account($db, (int) $r['bank_account_id']);
            if ($r['review_status'] !== 'prepared') {
                return [409, 'That daily record is not waiting for review (' . $r['review_status'] . ($r['reviewed_by'] ? ' by ' . $r['reviewed_by'] : '') . ').'];
            }
            if ($r['prepared_by'] === $user) {
                return [403, 'The daily record must be reviewed by someone other than the person who prepared it.'];
            }
            if (!in_array($decision, ['reviewed', 'returned'], true)) {
                return [400, 'Decision must be reviewed or returned.'];
            }
            if ($decision === 'returned' && mb_strlen(trim($note)) < 5) {
                return [400, 'Say why it is returned (at least 5 characters).'];
            }
            if ($decision === 'reviewed') {
                $latest = $db->query('SELECT id FROM brc_day WHERE bank_account_id = ? AND rec_date = ? ORDER BY id DESC LIMIT 1', [(int) $r['bank_account_id'], $r['rec_date']])->getRowArray();
                if ((int) $latest['id'] !== $dayId) {
                    return [409, 'A newer record exists for that day; review the newest one.'];
                }
                if (!$r['inputs_hash']) {
                    return [409, 'This record was prepared before changes were tracked; the preparer must prepare it again.'];
                }
                $live = self::day($db, (int) $r['bank_account_id'], $r['rec_date'], false);
                if (($live['inputs_hash'] ?? null) !== $r['inputs_hash']) {
                    $old = json_decode((string) $r['inputs_json'], true) ?: [];
                    $what = [];
                    foreach (self::MATERIAL as $k) {
                        if (($old[$k] ?? null) !== ($live['inputs'][$k] ?? null)) {
                            $what[] = self::COMPONENT_LABELS[$k];
                        }
                    }
                    self::audit($db, 'brc_day_review_refused', 'brc_day', $dayId, null, ['changed' => $what], 'data changed after the record was prepared', $a['label'] ?? null);
                    return [409, 'The data changed after this record was prepared (' . implode(', ', $what) . '). The preparer must prepare it again; nothing was reviewed.'];
                }
            }
            $db->query("UPDATE brc_day SET review_status = ?, reviewed_by = ?, reviewed_at = ?, review_note = ? WHERE id = ? AND review_status = 'prepared'", [$decision, $user, self::now(), mb_substr($note, 0, 300) ?: null, $dayId]);
            if ($db->affectedRows() !== 1) {
                return [409, 'Someone else decided on this record a moment ago; reload.'];
            }
            self::audit($db, $decision === 'reviewed' ? 'brc_day_reviewed' : 'brc_day_returned', 'brc_day', $dayId, ['review_status' => 'prepared', 'status' => $r['status_label'], 'pending_postings' => json_decode((string) $r['pending_json'], true)['summary'] ?? null], ['review_status' => $decision], $note, $a['label'] ?? null);
            return [200, $decision === 'reviewed' ? 'Daily record reviewed.' . (str_contains((string) $r['status_code'], 'pending') ? ' Note: the ledger is not yet corrected for the corrections listed on it.' : '') : 'Daily record returned to the preparer.'];
        });
    }

    /** The daily records of an account with each one's state against the live data (latest per date). */
    public static function records($db, int $accountId, int $limit = 200): array
    {
        $rows = $db->query('SELECT * FROM brc_day WHERE bank_account_id = ? ORDER BY rec_date DESC, id DESC LIMIT ' . (int) $limit, [$accountId])->getResultArray();
        $latest = [];
        $live = [];
        foreach ($rows as &$r) {
            $r['pending'] = json_decode((string) ($r['pending_json'] ?? ''), true) ?: null;
            $isLatest = !isset($latest[$r['rec_date']]);
            if ($isLatest) {
                $latest[$r['rec_date']] = (int) $r['id'];
                $live[$r['rec_date']] = $live[$r['rec_date']] ?? self::day($db, $accountId, $r['rec_date'], false);
                $st = self::reviewState($r, $live[$r['rec_date']]);
                $r['live'] = ['state' => $st['state'], 'label' => $st['label'], 'changed' => $st['changed_labels'], 'postings_progressed' => $st['postings_progressed'],
                    'status_now' => self::STATUS_LABELS[$live[$r['rec_date']]['status_code']] ?? $live[$r['rec_date']]['status_code'], 'pending_now' => $live[$r['rec_date']]['pending_postings']['summary'] ?? null];
            } else {
                $r['live'] = ['state' => 'superseded', 'label' => 'Superseded by record #' . $latest[$r['rec_date']] . ' for the same date.'];
            }
            unset($r['snapshot'], $r['inputs_json'], $r['pending_json']);
        }
        return $rows;
    }
}
