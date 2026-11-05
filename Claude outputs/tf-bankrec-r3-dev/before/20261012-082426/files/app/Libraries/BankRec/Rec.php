<?php

namespace App\Libraries\BankRec;

use App\Libraries\TourPayments\Audit;
use App\Libraries\TourPayments\BankAccounts;

/**
 * tf-bankrec-r1 — the core of the Daily Bank Reconciliation: accounts and their confirmed ledger mapping, daily rates,
 * the bank side (accepted statements) and the book side (the ledger, read only), and the daily reconciliation.
 *
 * Sign convention everywhere: an amount is "funds" — positive adds to the money in the account, negative takes it out.
 * Bank: money_in − money_out (account currency). Book: Dr − Cr of the bank ledger account (LKR).
 * Money is computed in integer cents; rates are LKR per one unit of the account currency.
 */
final class Rec
{
    public const CURRENCIES = ['LKR', 'USD', 'JPY'];
    public const CURRENCY_NAMES = ['LKR' => 'Sri Lankan rupee', 'USD' => 'US dollar', 'JPY' => 'Japanese yen'];
    public const STATUS_LABELS = [
        'not_set_up'   => 'Not set up — ledger mapping not confirmed',
        'insufficient' => 'Unreconciled — insufficient data',
        'open_items'   => 'Unreconciled — items to explain',
        'explained'    => 'Reconciled — all items explained (awaiting review)',
        'reviewed'     => 'Reconciled — reviewed',
    ];

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
        $clash = $db->query("SELECT m.bank_account_id FROM brc_account_map m WHERE m.status = 'confirmed' AND m.ledger_code = ? AND m.bank_account_id <> ?", [$a['ledger_code'], $accountId])->getRowArray();
        if ($clash) {
            return [409, 'That ledger account is already confirmed for another bank account; one ledger account maps to one bank account.'];
        }
        $prev = $db->query("SELECT * FROM brc_account_map WHERE bank_account_id = ? AND status = 'proposed'", [$accountId])->getResultArray();
        $db->transStart();
        $db->query("UPDATE brc_account_map SET status = 'superseded', decided_by = ?, decided_at = ?, decision_note = 'replaced by a newer proposal' WHERE bank_account_id = ? AND status = 'proposed'", [$user, self::now(), $accountId]);
        $db->table('brc_account_map')->insert(['bank_account_id' => $accountId, 'ledger_code' => $a['ledger_code'], 'currency' => $cur, 'rec_start' => $recStart,
            'status' => 'proposed', 'note' => mb_substr($note, 0, 300) ?: null, 'created_by' => $user, 'created_at' => self::now()]);
        $id = (int) $db->insertID();
        $db->transComplete();
        self::audit($db, 'brc_mapping_proposed', 'brc_account', $accountId, $prev ?: null, ['map_id' => $id, 'ledger_code' => $a['ledger_code'], 'currency' => $cur, 'rec_start' => $recStart], $note, $a['label']);
        return [200, 'Mapping proposed. Another user with the review privilege must confirm it.', $id];
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
        $db->transStart();
        if ($decision === 'confirm') {
            $db->query("UPDATE brc_account_map SET status = 'superseded', decided_by = ?, decided_at = ?, decision_note = 'replaced by a newer confirmed mapping' WHERE bank_account_id = ? AND status = 'confirmed'", [$user, self::now(), (int) $m['bank_account_id']]);
        }
        $db->query('UPDATE brc_account_map SET status = ?, decided_by = ?, decided_at = ?, decision_note = ? WHERE id = ?', [$decision === 'confirm' ? 'confirmed' : 'rejected', $user, self::now(), mb_substr($note, 0, 300) ?: null, $mapId]);
        $db->transComplete();
        self::audit($db, $decision === 'confirm' ? 'brc_mapping_confirmed' : 'brc_mapping_rejected', 'brc_account', $m['bank_account_id'], $m, ['status' => $decision === 'confirm' ? 'confirmed' : 'rejected'], $note, $a['label'] ?? null);
        return [200, $decision === 'confirm' ? 'Mapping confirmed.' : 'Mapping rejected.'];
    }

    // ---------------------------------------------------------------- rates

    /** The applicable rate (LKR per unit) for a currency on a day: the reconciliation's own rate, else the system daily USD rate. */
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

    public static function saveRate($db, string $currency, string $date, string $rate, string $source, string $note, string $user): array
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
        $db->transStart();
        if ($prev) {
            $db->query('UPDATE brc_fx_rate SET rate = ?, source = ?, note = ?, updated_by = ?, updated_at = ? WHERE id = ?', [trim($rate), mb_substr($source, 0, 40), mb_substr($note, 0, 200) ?: null, $user, self::now(), (int) $prev['id']]);
        } else {
            $db->table('brc_fx_rate')->insert(['currency' => $currency, 'rate_date' => $date, 'rate' => trim($rate), 'source' => mb_substr($source, 0, 40), 'note' => mb_substr($note, 0, 200) ?: null, 'created_by' => $user, 'created_at' => self::now()]);
        }
        $n = self::revalueLines($db, $currency, $date);
        $db->transComplete();
        self::audit($db, $prev ? 'brc_rate_changed' : 'brc_rate_entered', 'brc_fx', $currency . ' ' . $date, $prev, ['rate' => trim($rate), 'source' => $source, 'lines_revalued' => $n], $note);
        return [200, ($prev ? 'Rate changed' : 'Rate saved') . ($n ? "; LKR equivalents of {$n} statement line(s) recalculated." : '.')];
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

    private static function fp(array $r): string
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
     * reconciling item by kind, the explanation, the unexplained remainder and the status. Read only.
     */
    public static function day($db, int $accountId, string $date): array
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
            'opening_rate' => $startRate['rate'] ?? null, 'closing_rate' => $endRate['rate'] ?? null, 'closing_rate_source' => $endRate['source'] ?? null,
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
        $act = self::activeLinks($db, $accountId);
        $lineById = [];
        foreach ($lines as $l) {
            $lineById[(int) $l['id']] = $l;
        }
        $bookByKey = [];
        foreach ($book as $b) {
            $bookByKey[$b['key']] = $b;
        }
        $items = ['matched' => [], 'bank_unmatched' => [], 'book_unmatched' => [], 'timing' => [], 'duplicate' => [], 'excluded' => [], 'charges' => [], 'matched_later' => []];
        $sum = ['bank_unmatched' => 0, 'book_unmatched' => 0, 'bank_lkr_window' => 0];
        $rateMissing = 0;
        foreach ($lines as $l) {
            if ($l['lkr_amount'] === null) {
                $rateMissing++;
            } else {
                $sum['bank_lkr_window'] += self::cents($l['lkr_amount']);
            }
        }
        // matches: "closed" when every item is dated on or before the day; a match that reaches past the day leaves its
        // earlier side as a timing item for this day
        $closedLines = [];
        $closedBook = [];
        $openMatchLine = [];
        $openMatchBook = [];
        $matchDiff = 0;
        $preStart = 0;
        $changed = [];
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
            foreach ($m['items'] as $it) {
                if ($it['side'] === 'bank') {
                    $closedLines[(int) $it['line_id']] = true;
                    $ln = $lineById[(int) $it['line_id']] ?? null;
                    $bankLkr += ($ln && $ln['lkr_amount'] !== null) ? self::cents($ln['lkr_amount']) : 0;
                } else {
                    if ($it['item_date'] < $start) {
                        $preStart += self::cents($it['amount']);
                        $bookSum += self::cents($it['amount']);
                        continue;
                    }
                    $closedBook[$it['book_key']] = true;
                    $cur_ = $bookByKey[$it['book_key']] ?? null;
                    $snap = json_decode((string) $it['snapshot'], true) ?: [];
                    if (!$cur_ || (isset($snap['fp']) && $snap['fp'] !== $cur_['fp'])) {
                        $changed[] = $it['book_key'];
                    }
                    $bookSum += $cur_ ? self::cents($cur_['amount']) : self::cents($it['amount']);
                }
            }
            $m['difference_now_lkr'] = self::dec($bankLkr - $bookSum);
            $matchDiff += $bankLkr - $bookSum;
            $items['matched'][] = $m;
        }
        foreach ($lines as $l) {
            if (isset($closedLines[(int) $l['id']])) {
                continue;
            }
            $amt = self::cents($l['money_in']) - self::cents($l['money_out']);
            $lkr = $l['lkr_amount'] === null ? null : self::cents($l['lkr_amount']);
            $view = ['line_id' => (int) $l['id'], 'date' => $l['txn_date'], 'description' => $l['description'], 'reference' => $l['reference'], 'amount' => self::dec($amt), 'amount_lkr' => $lkr === null ? null : self::dec($lkr), 'category' => $l['category'], 'charge_type' => $l['charge_type'], 'state' => $l['state'], 'side' => 'bank'];
            $explained = false;
            if (isset($openMatchLine[(int) $l['id']])) {
                $view['matched_later'] = $openMatchLine[(int) $l['id']];
                $items['matched_later'][] = $view;
                $explained = true;
            } elseif ($mark = $act['line_mark'][(int) $l['id']] ?? null) {
                $view['mark'] = $mark;
                $items[$mark['mark']][] = $view;
                $explained = true;
            } else {
                // a line covered by a proposed or approved correction is explained (the correction itself is reviewed)
                $corr = $act['line_correction'][(int) $l['id']] ?? null;
                $view['correction'] = $corr;
                $explained = (bool) $corr;
                if ($l['category'] === 'charge') {
                    $items['charges'][] = $view;
                }
            }
            $view['explained'] = $explained;
            $items['bank_unmatched'][] = $view;
            $sum['bank_unmatched'] += $lkr ?? 0;
        }
        foreach ($book as $b) {
            if (isset($closedBook[$b['key']])) {
                continue;
            }
            $view = $b + ['side' => 'book'];
            $explained = false;
            if (isset($openMatchBook[$b['key']])) {
                $view['matched_later'] = $openMatchBook[$b['key']];
                $items['matched_later'][] = $view;
                $explained = true;
            } elseif ($mark = $act['book_mark'][$b['key']] ?? null) {
                $view['mark'] = $mark;
                $items[$mark['mark']][] = $view;
                $explained = true;
            }
            if ($corr = $act['book_correction'][$b['key']] ?? null) {
                $view['correction'] = $corr;
                $explained = true;
            }
            $view['explained'] = $explained;
            $items['book_unmatched'][] = $view;
            $sum['book_unmatched'] += self::cents($b['amount']);
        }
        if ($changed) {
            $insufficient[] = 'Ledger row(s) ' . implode(', ', array_slice(array_unique($changed), 0, 5)) . ' changed or disappeared in the ledger after they were matched; undo those matches and match again.';
        }
        if ($rateMissing) {
            $insufficient[] = "{$rateMissing} statement line(s) have no {$cur} rate for their day, so their LKR value is unknown.";
        }
        // opening difference and its explanation
        $opening = $db->query("SELECT * FROM brc_opening WHERE bank_account_id = ? AND status IN ('proposed', 'accepted') ORDER BY id", [$accountId])->getResultArray();
        $openAccepted = 0;
        $openProposed = 0;
        foreach ($opening as $o) {
            if ($o['status'] === 'accepted') {
                $openAccepted += self::cents($o['amount']);
            } else {
                $openProposed += self::cents($o['amount']);
            }
        }
        $openDiff = ($out['bank']['opening_lkr'] !== null) ? self::cents($out['bank']['opening_lkr']) - $bookOpen : null;
        $openRemaining = $openDiff === null ? null : $openDiff + $preStart - $openAccepted;
        // revaluation of a foreign-currency balance: closing at the day's rate − (opening at the first day's rate + lines at their own rates)
        $reval = 0;
        if ($cur !== 'LKR' && $bankOpen !== null && $bankClose !== null && $startRate && $endRate && !$rateMissing) {
            $reval = self::toLkr($bankClose, $endRate['rate']) - (self::toLkr($bankOpen, $startRate['rate']) + $sum['bank_lkr_window']);
        }
        $diff = ($out['bank']['closing_lkr'] !== null) ? self::cents($out['bank']['closing_lkr']) - $bookClose : null;
        $explainedTotal = ($openRemaining === null) ? null : $openRemaining + $openAccepted + $matchDiff + $sum['bank_unmatched'] - $sum['book_unmatched'] + $reval;
        $unexplained = ($diff === null || $explainedTotal === null) ? null : $diff - $explainedTotal;
        $out['reconciliation'] = [
            'difference_lkr' => $diff === null ? null : self::dec($diff),
            'opening_difference_lkr' => $openDiff === null ? null : self::dec($openDiff),
            'opening_explained_by_earlier_book_items' => self::dec(-$preStart),
            'opening_items_accepted' => self::dec($openAccepted), 'opening_items_proposed' => self::dec($openProposed),
            'opening_remaining' => $openRemaining === null ? null : self::dec($openRemaining),
            'bank_items_not_in_book' => self::dec($sum['bank_unmatched']),
            'book_items_not_on_statement' => self::dec(-$sum['book_unmatched']),
            'match_differences' => self::dec($matchDiff),
            'revaluation' => self::dec($reval),
            'unexplained_lkr' => $unexplained === null ? null : self::dec($unexplained),
        ];
        $out['opening_items'] = $opening;
        if ($openRemaining !== null && $openRemaining !== 0) {
            $insufficient[] = 'The opening difference of ' . number_format($openRemaining / 100, 2) . ' LKR on ' . $start . ' is not yet explained (opening items to be entered and accepted).';
        }
        if ($unexplained !== null && $unexplained !== 0) {
            $insufficient[] = 'An unexplained remainder of ' . number_format($unexplained / 100, 2) . ' LKR: a statement or the ledger is incomplete or changed.';
        }
        $out['items'] = $items;
        $open = 0;
        foreach (['bank_unmatched', 'book_unmatched'] as $k) {
            foreach ($items[$k] as $it) {
                $open += $it['explained'] ? 0 : 1;
            }
        }
        $out['counts'] = ['bank_lines' => count($lines), 'book_rows' => count($book), 'matched' => count($items['matched']), 'bank_unmatched' => count($items['bank_unmatched']),
            'book_unmatched' => count($items['book_unmatched']), 'timing' => count($items['timing']), 'duplicate' => count($items['duplicate']),
            'excluded' => count($items['excluded']), 'charges' => count($items['charges']), 'matched_later' => count($items['matched_later']), 'unexplained_items' => $open];
        $last = $db->query('SELECT * FROM brc_day WHERE bank_account_id = ? AND rec_date = ? ORDER BY id DESC LIMIT 1', [$accountId, $date])->getRowArray();
        if ($insufficient) {
            $code = 'insufficient';
        } elseif ($open > 0) {
            $code = 'open_items';
            $out['reasons'][] = "{$open} item(s) still to match or classify.";
        } else {
            $same = $last && $last['difference_lkr'] !== null && $diff !== null && self::cents($last['difference_lkr']) === $diff;
            $code = ($last && $same && $last['review_status'] === 'reviewed' && $last['status_code'] === 'explained') ? 'reviewed' : 'explained';
        }
        $out['reasons'] = array_merge($insufficient, $out['reasons']);
        $out['status_code'] = $code;
        $out['status_label'] = self::STATUS_LABELS[$code];
        $out['last_record'] = $last ? array_diff_key($last, ['snapshot' => 1]) : null;
        return $out;
    }

    /** Active matches, marks and correction links of an account, indexed for the day computation. */
    public static function activeLinks($db, int $accountId): array
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
        foreach ($db->query("SELECT id, status, related_json FROM brc_correction WHERE bank_account_id = ? AND status IN ('proposed', 'approved')", [$accountId])->getResultArray() as $c) {
            $rel = json_decode((string) $c['related_json'], true) ?: [];
            foreach ((array) ($rel['line_ids'] ?? []) as $lid) {
                $lineCorr[(int) $lid] = ['id' => (int) $c['id'], 'status' => $c['status']];
            }
            foreach ((array) ($rel['book_keys'] ?? []) as $bk) {
                $bookCorr[(string) $bk] = ['id' => (int) $c['id'], 'status' => $c['status']];
            }
        }
        return ['matches' => array_values($byId), 'line_match' => $lineMatch, 'book_match' => $bookMatch, 'line_mark' => $lineMark, 'book_mark' => $bookMark, 'line_correction' => $lineCorr, 'book_correction' => $bookCorr];
    }

    /** Prepare (record) the day: stores the computed reconciliation as it stands, with who and when. */
    public static function prepareDay($db, int $accountId, string $date, string $note, string $user): array
    {
        $d = self::day($db, $accountId, $date);
        if (!$d['ok']) {
            return [404, $d['message']];
        }
        if ($d['status_code'] === 'not_set_up') {
            return [409, 'The account is not set up (mapping not confirmed).'];
        }
        $db->table('brc_day')->insert([
            'bank_account_id' => $accountId, 'rec_date' => $date, 'currency' => $d['currency'],
            'bank_closing' => $d['bank']['closing'] ?? null, 'bank_closing_lkr' => $d['bank']['closing_lkr'] ?? null, 'closing_rate' => $d['bank']['closing_rate'] ?? null,
            'book_closing' => $d['book']['closing'] ?? null, 'difference_lkr' => $d['reconciliation']['difference_lkr'] ?? null, 'unexplained_lkr' => $d['reconciliation']['unexplained_lkr'] ?? null,
            'status_code' => $d['status_code'] === 'reviewed' ? 'explained' : $d['status_code'], 'status_label' => in_array($d['status_code'], ['explained', 'reviewed'], true) ? 'Reconciled — all items explained' : self::STATUS_LABELS[$d['status_code']],
            'snapshot' => json_encode(['bank' => $d['bank'], 'book' => $d['book'], 'reconciliation' => $d['reconciliation'], 'counts' => $d['counts'], 'reasons' => $d['reasons'], 'coverage' => $d['coverage'] ?? null], JSON_UNESCAPED_UNICODE),
            'prepared_by' => $user, 'prepared_at' => self::now(), 'prepare_note' => mb_substr($note, 0, 300) ?: null, 'review_status' => 'prepared',
        ]);
        $id = (int) $db->insertID();
        self::audit($db, 'brc_day_prepared', 'brc_day', $id, null, ['date' => $date, 'status' => $d['status_label'], 'difference_lkr' => $d['reconciliation']['difference_lkr'] ?? null, 'unexplained_lkr' => $d['reconciliation']['unexplained_lkr'] ?? null], $note, $d['account']['label']);
        return [200, 'Daily record prepared: ' . $d['status_label'] . '.', $id];
    }

    public static function reviewDay($db, int $dayId, string $decision, string $note, string $user): array
    {
        $r = $db->query('SELECT * FROM brc_day WHERE id = ?', [$dayId])->getRowArray();
        if (!$r || $r['review_status'] !== 'prepared') {
            return [409, 'That daily record is not waiting for review.'];
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
        }
        $db->query('UPDATE brc_day SET review_status = ?, reviewed_by = ?, reviewed_at = ?, review_note = ? WHERE id = ?', [$decision, $user, self::now(), mb_substr($note, 0, 300) ?: null, $dayId]);
        $a = self::account($db, (int) $r['bank_account_id']);
        self::audit($db, $decision === 'reviewed' ? 'brc_day_reviewed' : 'brc_day_returned', 'brc_day', $dayId, ['review_status' => 'prepared', 'status' => $r['status_label']], ['review_status' => $decision], $note, $a['label'] ?? null);
        return [200, $decision === 'reviewed' ? 'Daily record reviewed.' : 'Daily record returned to the preparer.'];
    }
}
