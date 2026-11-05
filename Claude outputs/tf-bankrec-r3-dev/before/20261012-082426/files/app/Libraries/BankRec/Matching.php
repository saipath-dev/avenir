<?php

namespace App\Libraries\BankRec;

/**
 * tf-bankrec-r1 — matching statement lines with ledger rows, classifying what does not match (timing, duplicate-looking,
 * excluded), and transfers between Avenir's own accounts with the day's exchange rate. Suggestions are computed only;
 * nothing is matched until a preparer accepts it. Undoing keeps the record.
 */
final class Matching
{
    public const FX_TOLERANCE = 0.01;   // a foreign-currency line may differ from its ledger row by up to 1 % (exchange rate)

    private static function tokens(string $text): array
    {
        $t = strtoupper($text);
        $out = ['tours' => [], 'numbers' => []];
        if (preg_match_all('/\bAH\s*-?\s*(\d{3,5})\b/', $t, $m)) {
            $out['tours'] = array_values(array_unique($m[1]));
        }
        if (preg_match_all('/\b(\d{3,6})\b/', $t, $m)) {
            $out['numbers'] = array_values(array_unique($m[1]));
        }
        return $out;
    }

    /** The open (unmatched, unclassified) lines and ledger rows of an account in a date range. */
    public static function openItems($db, int $accountId, string $from, string $to, int $bookDaysBefore = 0): array
    {
        $map = Rec::activeMap($db, $accountId);
        if (!$map) {
            return ['bank' => [], 'book' => []];
        }
        $start = max($from, $map['rec_start']);
        $act = Rec::activeLinks($db, $accountId);
        $bank = [];
        foreach (Rec::bankLines($db, $accountId, $start, $to) as $l) {
            if (isset($act['line_match'][(int) $l['id']]) || isset($act['line_mark'][(int) $l['id']])) {
                continue;
            }
            $bank[] = $l + ['amount' => Rec::dec(Rec::cents($l['money_in']) - Rec::cents($l['money_out']))];
        }
        $bookFrom = $bookDaysBefore > 0 ? date('Y-m-d', strtotime($start . " -{$bookDaysBefore} days")) : $start;
        $book = [];
        foreach (Rec::bookRows($db, $map['ledger_code'], $bookFrom, date('Y-m-d', strtotime($to . ' +10 days'))) as $b) {
            if (isset($act['book_match'][$b['key']]) || isset($act['book_mark'][$b['key']])) {
                continue;
            }
            $book[] = $b;
        }
        return ['bank' => $bank, 'book' => $book, 'map' => $map];
    }

    /**
     * Suggested matches (computed, not saved): one line to one ledger row of the same amount (LKR, or within 1 % of the LKR
     * value for USD / JPY) dated up to 10 days before or 3 after; one line to several rows that share a payment voucher (PV)
     * number or date and add up exactly. Scored by PV / tour numbers in the wording and date distance.
     */
    public static function suggest($db, int $accountId, string $from, string $to): array
    {
        $o = self::openItems($db, $accountId, $from, $to, 30);
        if (!$o['bank']) {
            return [];
        }
        $cur = $o['map']['account']['currency'];
        $book = $o['book'];
        $usedBook = [];
        $out = [];
        foreach ($o['bank'] as $l) {
            if ($l['lkr_amount'] === null || in_array($l['category'], ['charge'], true)) {
                continue;
            }
            $target = Rec::cents($l['lkr_amount']);
            $tok = self::tokens($l['description'] . ' ' . $l['reference']);
            $cands = [];
            foreach ($book as $b) {
                if (isset($usedBook[$b['key']])) {
                    continue;
                }
                $amt = Rec::cents($b['amount']);
                if (($amt > 0) !== ($target > 0)) {
                    continue;
                }
                $ok = $cur === 'LKR' ? $amt === $target : abs($amt - $target) <= abs($target) * self::FX_TOLERANCE;
                if (!$ok) {
                    continue;
                }
                $days = (int) round((strtotime($b['date']) - strtotime($l['txn_date'])) / 86400);
                if ($days < -10 || $days > 3) {
                    continue;
                }
                $score = 0;
                $why = [];
                $btxt = strtoupper($b['description'] . ' ' . $b['chk_no'] . ' ' . $b['invoice'] . ' ' . $b['payee'] . ' ' . $b['tour']);
                if ($b['pv'] && $b['pv'] !== '0' && (in_array($b['pv'], $tok['numbers'], true) || self::prefixHit($b['pv'], $l['description']))) {
                    $score += 3;
                    $why[] = 'PV ' . $b['pv'] . ' in the wording';
                }
                $chk = trim((string) $b['chk_no']);
                if ($chk !== '' && $chk !== '0' && strlen(ltrim($chk, '0')) >= 3 && (in_array($chk, $tok['numbers'], true) || in_array(ltrim($chk, '0'), array_map(static fn ($n) => ltrim($n, '0'), $tok['numbers']), true))) {
                    $score += 3;
                    $why[] = 'cheque / reference ' . $chk;
                }
                foreach ($tok['tours'] as $t) {
                    if (str_contains($btxt, $t)) {
                        $score += 2;
                        $why[] = 'tour AH ' . $t;
                        break;
                    }
                }
                $score += $days === 0 ? 1 : 0;
                $why[] = $days === 0 ? 'same day' : abs($days) . ' day(s) apart';
                $cands[] = ['book' => [$b], 'score' => $score, 'why' => $why, 'days' => abs($days)];
            }
            // several ledger rows sharing a PV number (one transfer paying a voucher with several lines)
            $byPv = [];
            foreach ($book as $b) {
                if (!isset($usedBook[$b['key']]) && $b['pv'] && $b['pv'] !== '0' && (($l['money_out'] > 0) === (Rec::cents($b['amount']) < 0))) {
                    $byPv[$b['pv']][] = $b;
                }
            }
            foreach ($byPv as $pv => $rows) {
                if (count($rows) < 2) {
                    continue;
                }
                $s = array_sum(array_map(static fn ($r) => Rec::cents($r['amount']), $rows));
                $ok = $cur === 'LKR' ? $s === $target : abs($s - $target) <= abs($target) * self::FX_TOLERANCE;
                if ($ok) {
                    $hit = in_array((string) $pv, $tok['numbers'], true) || self::prefixHit((string) $pv, $l['description']);
                    // several rows adding up is only a hint by itself; the PV number in the wording makes it strong
                    $cands[] = ['book' => $rows, 'score' => $hit ? 4 : 0, 'why' => array_merge(['PV ' . $pv . ': ' . count($rows) . ' rows add up'], $hit ? ['PV in the wording'] : []), 'days' => 0];
                }
            }
            if (!$cands) {
                continue;
            }
            usort($cands, static fn ($a, $b) => [$b['score'], $a['days']] <=> [$a['score'], $b['days']]);
            $best = $cands[0];
            $unique = count($cands) === 1 || $cands[1]['score'] < $best['score'];
            $conf = ($unique && $best['score'] >= 3) ? 'high' : ($unique ? 'medium' : 'low');
            if ($conf !== 'low') {
                foreach ($best['book'] as $b) {
                    $usedBook[$b['key']] = true;
                }
            }
            $out[] = ['line' => ['id' => (int) $l['id'], 'date' => $l['txn_date'], 'description' => $l['description'], 'amount' => $l['amount'], 'amount_lkr' => $l['lkr_amount']],
                'book' => array_map(static fn ($b) => ['key' => $b['key'], 'date' => $b['date'], 'amount' => $b['amount'], 'description' => $b['description'], 'pv' => $b['pv'], 'payee' => $b['payee']], $best['book']),
                'confidence' => $conf, 'why' => $best['why'], 'alternatives' => count($cands) - 1];
        }
        return $out;
    }

    /** Bank wording often cuts a PV number short ("- 79", "- 791" for PV 7919): a match on 2+ leading digits after a dash. */
    private static function prefixHit(string $pv, string $desc): bool
    {
        if (strlen($pv) < 3) {
            return false;
        }
        return (bool) preg_match('/-\s*(\d{2,})\s*$/', trim($desc), $m) && str_starts_with($pv, $m[1]) && strlen($m[1]) >= 3;
    }

    /** Ledger rows and statement lines that look duplicated (same amount and reference close together). Flags only. */
    public static function duplicateCandidates($db, int $accountId, string $from, string $to): array
    {
        $o = self::openItems($db, $accountId, $from, $to);
        $out = [];
        $groups = [];
        foreach ($o['book'] as $b) {
            if ($b['date'] > $to) {
                continue;
            }
            $ref = ($b['pv'] && $b['pv'] !== '0') ? 'PV ' . $b['pv'] : trim($b['chk_no'] . '/' . $b['invoice'], '/');
            $groups['book|' . $b['amount'] . '|' . $ref][] = $b;
        }
        foreach ($o['bank'] as $l) {
            $groups['bank|' . $l['fingerprint']][] = $l;
        }
        foreach ($groups as $k => $rows) {
            if (count($rows) < 2) {
                continue;
            }
            $side = str_starts_with($k, 'book') ? 'book' : 'bank';
            $dates = array_map(static fn ($r) => $side === 'book' ? $r['date'] : $r['txn_date'], $rows);
            if ((strtotime(max($dates)) - strtotime(min($dates))) / 86400 > 7) {
                continue;
            }
            $out[] = ['side' => $side, 'items' => array_map(static fn ($r) => $side === 'book' ? ['key' => $r['key'], 'date' => $r['date'], 'amount' => $r['amount'], 'description' => $r['description'], 'pv' => $r['pv']] : ['line_id' => (int) $r['id'], 'date' => $r['txn_date'], 'amount' => $r['amount'], 'description' => $r['description']], $rows)];
        }
        return $out;
    }

    /** Match statement line(s) with ledger row(s). */
    public static function create($db, int $accountId, array $lineIds, array $bookKeys, string $method, ?string $diffReason, string $note, string $user): array
    {
        $map = Rec::activeMap($db, $accountId);
        if (!$map) {
            return [409, 'The account has no confirmed ledger mapping.'];
        }
        $lineIds = array_values(array_unique(array_map('intval', $lineIds)));
        $bookKeys = array_values(array_unique(array_map('strval', $bookKeys)));
        if (!$lineIds || !$bookKeys) {
            return [400, 'Choose at least one statement line and one ledger row.'];
        }
        if (count($lineIds) > 50 || count($bookKeys) > 50) {
            return [400, 'Too many items in one match.'];
        }
        $act = Rec::activeLinks($db, $accountId);
        $lines = [];
        foreach ($lineIds as $id) {
            $l = $db->query("SELECT l.* FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted' WHERE l.id = ? AND l.bank_account_id = ?", [$id, $accountId])->getRowArray();
            if (!$l) {
                return [404, "Statement line {$id} is not an accepted line of this account."];
            }
            if ($l['txn_date'] < $map['rec_start']) {
                return [409, "Statement line {$id} is dated before the first day of the reconciliation."];
            }
            if (isset($act['line_match'][$id]) || isset($act['line_mark'][$id])) {
                return [409, "Statement line {$id} is already matched or classified."];
            }
            if ($l['lkr_amount'] === null) {
                return [409, "Statement line {$id} has no LKR value yet (enter the {$l['currency']} rate for {$l['txn_date']})."];
            }
            $lines[] = $l;
        }
        $book = [];
        $allBook = [];
        foreach (Rec::bookRows($db, $map['ledger_code'], '1900-01-01', '2999-12-31') as $r) {
            $allBook[$r['key']] = $r;
        }
        foreach ($bookKeys as $k) {
            if (!preg_match('/^(\d+)\.(\d+)$/', $k, $m)) {
                return [400, "Ledger row {$k} is not valid."];
            }
            $rows = isset($allBook[$k]) ? [$allBook[$k]] : [];
            if (!$rows) {
                return [404, "Ledger row {$k} is not on the account's ledger {$map['ledger_code']}."];
            }
            if (isset($act['book_match'][$k]) || isset($act['book_mark'][$k])) {
                return [409, "Ledger row {$k} is already matched or classified."];
            }
            $book[] = $rows[0];
        }
        $bankTotal = array_sum(array_map(static fn ($l) => Rec::cents($l['money_in']) - Rec::cents($l['money_out']), $lines));
        $bankLkr = array_sum(array_map(static fn ($l) => Rec::cents($l['lkr_amount']), $lines));
        $bookTotal = array_sum(array_map(static fn ($b) => Rec::cents($b['amount']), $book));
        $diff = $bankLkr - $bookTotal;
        $cur = $map['account']['currency'];
        if ($diff !== 0) {
            if (($bankLkr > 0) !== ($bookTotal > 0)) {
                return [400, 'The statement side and the ledger side go in opposite directions.'];
            }
            if (!in_array($diffReason, ['exchange rate', 'rounding', 'other'], true)) {
                return [400, 'The two sides differ by ' . number_format($diff / 100, 2) . ' LKR. Say why: exchange rate, rounding or other.'];
            }
            if ($diffReason === 'rounding' && abs($diff) > 100) {
                return [400, 'Rounding covers up to 1.00 LKR.'];
            }
            if ($diffReason === 'exchange rate' && ($cur === 'LKR' || abs($diff) > abs($bookTotal) * 0.05)) {
                return [400, $cur === 'LKR' ? 'An LKR account has no exchange-rate difference.' : 'An exchange-rate difference above 5 % needs the reason "other".'];
            }
            if ($diffReason === 'other' && mb_strlen(trim($note)) < 5) {
                return [400, 'Explain the difference in the note (at least 5 characters).'];
            }
        }
        $db->transStart();
        $db->table('brc_match')->insert(['bank_account_id' => $accountId, 'method' => $method === 'suggested' ? 'suggested' : 'manual', 'bank_total' => Rec::dec($bankTotal), 'bank_total_lkr' => Rec::dec($bankLkr),
            'book_total' => Rec::dec($bookTotal), 'difference_lkr' => Rec::dec($diff), 'diff_reason' => $diff !== 0 ? $diffReason : null, 'status' => 'active', 'note' => mb_substr($note, 0, 300) ?: null,
            'created_by' => $user, 'created_at' => Rec::now()]);
        $mid = (int) $db->insertID();
        foreach ($lines as $l) {
            $db->table('brc_match_item')->insert(['match_id' => $mid, 'side' => 'bank', 'line_id' => (int) $l['id'], 'amount' => Rec::dec(Rec::cents($l['money_in']) - Rec::cents($l['money_out'])), 'item_date' => $l['txn_date'],
                'snapshot' => json_encode(['description' => $l['description'], 'lkr' => $l['lkr_amount'], 'rate' => $l['fx_rate']], JSON_UNESCAPED_UNICODE)]);
            $db->query("UPDATE brc_line SET state = 'matched' WHERE id = ?", [(int) $l['id']]);
        }
        foreach ($book as $b) {
            $db->table('brc_match_item')->insert(['match_id' => $mid, 'side' => 'book', 'book_key' => $b['key'], 'amount' => $b['amount'], 'item_date' => $b['date'],
                'snapshot' => json_encode(['id' => $b['id'], 'description' => $b['description'], 'pv' => $b['pv'], 'dr' => $b['dr'], 'cr' => $b['cr'], 'fp' => $b['fp']], JSON_UNESCAPED_UNICODE)]);
        }
        $db->transComplete();
        Rec::audit($db, 'brc_matched', 'brc_match', $mid, null, ['lines' => $lineIds, 'ledger_rows' => $bookKeys, 'bank' => Rec::dec($bankTotal), 'bank_lkr' => Rec::dec($bankLkr), 'book' => Rec::dec($bookTotal), 'difference_lkr' => Rec::dec($diff), 'reason' => $diffReason, 'method' => $method], $note, $map['account']['label']);
        return [200, 'Matched' . ($diff !== 0 ? ' (difference ' . number_format($diff / 100, 2) . ' LKR: ' . $diffReason . ')' : '') . '.', $mid];
    }

    public static function undo($db, int $matchId, string $reason, string $user): array
    {
        $m = $db->query("SELECT * FROM brc_match WHERE id = ? AND status = 'active'", [$matchId])->getRowArray();
        if (!$m) {
            return [409, 'That match is not active.'];
        }
        if (mb_strlen(trim($reason)) < 5) {
            return [400, 'Give the reason (at least 5 characters).'];
        }
        $items = $db->query('SELECT * FROM brc_match_item WHERE match_id = ?', [$matchId])->getResultArray();
        $db->transStart();
        $db->query("UPDATE brc_match SET status = 'undone', undone_by = ?, undone_at = ?, undo_reason = ? WHERE id = ?", [$user, Rec::now(), mb_substr($reason, 0, 300), $matchId]);
        foreach ($items as $it) {
            if ($it['side'] === 'bank') {
                $db->query("UPDATE brc_line SET state = 'open' WHERE id = ? AND state = 'matched'", [(int) $it['line_id']]);
            }
        }
        $db->transComplete();
        $a = Rec::account($db, (int) $m['bank_account_id']);
        Rec::audit($db, 'brc_match_undone', 'brc_match', $matchId, ['status' => 'active', 'items' => $items], ['status' => 'undone'], $reason, $a['label'] ?? null);
        return [200, 'Match undone (kept in the record).'];
    }

    /** Classify an unmatched line or ledger row: timing (expected to clear), duplicate-looking, excluded. */
    public static function mark($db, int $accountId, string $side, $ref, string $mark, string $note, ?string $expected, ?string $related, string $user): array
    {
        $map = Rec::activeMap($db, $accountId);
        if (!$map) {
            return [409, 'The account has no confirmed ledger mapping.'];
        }
        if (!in_array($mark, ['timing', 'duplicate', 'excluded'], true)) {
            return [400, 'Unknown classification.'];
        }
        if (mb_strlen(trim($note)) < 5) {
            return [400, 'Give a note (at least 5 characters).'];
        }
        if ($mark === 'timing' && $expected !== null && $expected !== '' && !Rec::validDate($expected)) {
            return [400, 'The expected date is not valid.'];
        }
        $act = Rec::activeLinks($db, $accountId);
        if ($side === 'bank') {
            $id = (int) $ref;
            $l = $db->query("SELECT l.* FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted' WHERE l.id = ? AND l.bank_account_id = ?", [$id, $accountId])->getRowArray();
            if (!$l) {
                return [404, 'Not an accepted line of this account.'];
            }
            if (isset($act['line_match'][$id]) || isset($act['line_mark'][$id])) {
                return [409, 'That line is already matched or classified.'];
            }
            $row = ['line_id' => $id, 'book_key' => null, 'amount' => Rec::dec(Rec::cents($l['money_in']) - Rec::cents($l['money_out'])), 'item_date' => $l['txn_date']];
        } elseif ($side === 'book') {
            $k = (string) $ref;
            $rows = array_values(array_filter(Rec::bookRows($db, $map['ledger_code'], '1900-01-01', '2999-12-31'), static fn ($r) => $r['key'] === $k));
            if (!$rows) {
                return [404, 'Not a ledger row of this account.'];
            }
            if (isset($act['book_match'][$k]) || isset($act['book_mark'][$k])) {
                return [409, 'That ledger row is already matched or classified.'];
            }
            $row = ['line_id' => null, 'book_key' => $k, 'amount' => $rows[0]['amount'], 'item_date' => $rows[0]['date']];
        } else {
            return [400, 'Side must be bank or book.'];
        }
        $db->transStart();
        $db->table('brc_mark')->insert($row + ['bank_account_id' => $accountId, 'side' => $side, 'mark' => $mark, 'related' => $related ? mb_substr($related, 0, 60) : null,
            'expected_date' => ($mark === 'timing' && $expected) ? $expected : null, 'note' => mb_substr($note, 0, 300), 'status' => 'active', 'created_by' => $user, 'created_at' => Rec::now()]);
        $mid = (int) $db->insertID();
        if ($side === 'bank') {
            $db->query('UPDATE brc_line SET state = ? WHERE id = ?', [$mark, $row['line_id']]);
        }
        $db->transComplete();
        Rec::audit($db, 'brc_classified', 'brc_mark', $mid, null, $row + ['side' => $side, 'mark' => $mark, 'expected' => $expected, 'related' => $related], $note, $map['account']['label']);
        return [200, 'Classified as ' . ($mark === 'duplicate' ? 'duplicate-looking' : $mark) . '.', $mid];
    }

    public static function unmark($db, int $markId, string $reason, string $user): array
    {
        $k = $db->query("SELECT * FROM brc_mark WHERE id = ? AND status = 'active'", [$markId])->getRowArray();
        if (!$k) {
            return [409, 'That classification is not active.'];
        }
        if (mb_strlen(trim($reason)) < 5) {
            return [400, 'Give the reason (at least 5 characters).'];
        }
        $db->query("UPDATE brc_mark SET status = 'cleared', cleared_by = ?, cleared_at = ?, clear_reason = ? WHERE id = ?", [$user, Rec::now(), mb_substr($reason, 0, 300), $markId]);
        if ($k['side'] === 'bank') {
            $db->query("UPDATE brc_line SET state = 'open' WHERE id = ? AND state = ?", [(int) $k['line_id'], $k['mark']]);
        }
        $a = Rec::account($db, (int) $k['bank_account_id']);
        Rec::audit($db, 'brc_classification_cleared', 'brc_mark', $markId, $k, ['status' => 'cleared'], $reason, $a['label'] ?? null);
        return [200, 'Classification cleared.'];
    }

    // ---------------------------------------------------------------- transfers between Avenir accounts

    /** Pairs: money out of one Avenir account and money in to another within 2 days, at a plausible rate. */
    public static function suggestTransfers($db, string $from, string $to): array
    {
        $outs = $db->query("SELECT l.*, a.label FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted' JOIN tpd_bank_account a ON a.id = l.bank_account_id
            WHERE l.txn_date BETWEEN ? AND ? AND l.money_out > 0 AND l.category IN ('transfer', 'payment', 'other') AND l.id NOT IN (SELECT from_line_id FROM brc_transfer WHERE status = 'active')", [$from, $to])->getResultArray();
        $ins = $db->query("SELECT l.*, a.label FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted' JOIN tpd_bank_account a ON a.id = l.bank_account_id
            WHERE l.txn_date BETWEEN ? AND ? AND l.money_in > 0 AND l.category IN ('transfer', 'receipt', 'other') AND l.id NOT IN (SELECT to_line_id FROM brc_transfer WHERE status = 'active')", [date('Y-m-d', strtotime($from . ' -2 days')), date('Y-m-d', strtotime($to . ' +2 days'))])->getResultArray();
        $out = [];
        $used = [];
        foreach ($outs as $o) {
            foreach ($ins as $i) {
                if ($o['bank_account_id'] === $i['bank_account_id'] || isset($used[$i['id']])) {
                    continue;
                }
                if (abs(strtotime($i['txn_date']) - strtotime($o['txn_date'])) > 2 * 86400) {
                    continue;
                }
                $fa = Rec::cents($o['money_out']);
                $ta = Rec::cents($i['money_in']);
                if ($o['currency'] === $i['currency']) {
                    if ($fa !== $ta) {
                        continue;
                    }
                    $implied = '1';
                    $ok = true;
                } else {
                    $implied = (string) round($ta / $fa, 6);
                    $r = $i['currency'] === 'LKR' ? Rec::rate($db, $o['currency'], $o['txn_date']) : null;
                    $ok = $r ? abs((float) $implied - (float) $r['rate']) <= (float) $r['rate'] * 0.05 : false;
                }
                if (!$ok) {
                    continue;
                }
                $used[$i['id']] = true;
                $out[] = ['from' => ['line_id' => (int) $o['id'], 'account' => $o['label'], 'date' => $o['txn_date'], 'amount' => $o['money_out'], 'currency' => $o['currency'], 'description' => $o['description']],
                    'to' => ['line_id' => (int) $i['id'], 'account' => $i['label'], 'date' => $i['txn_date'], 'amount' => $i['money_in'], 'currency' => $i['currency'], 'description' => $i['description']], 'implied_rate' => $implied];
                break;
            }
        }
        return $out;
    }

    public static function createTransfer($db, int $fromLine, int $toLine, string $note, string $user): array
    {
        $f = $db->query("SELECT l.* FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted' WHERE l.id = ?", [$fromLine])->getRowArray();
        $t = $db->query("SELECT l.* FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted' WHERE l.id = ?", [$toLine])->getRowArray();
        if (!$f || !$t) {
            return [404, 'Both lines must be accepted statement lines.'];
        }
        if ($f['bank_account_id'] === $t['bank_account_id']) {
            return [400, 'A transfer is between two different Avenir accounts.'];
        }
        if (Rec::cents($f['money_out']) <= 0 || Rec::cents($t['money_in']) <= 0) {
            return [400, 'The first line must be money out and the second money in.'];
        }
        if ($db->query("SELECT id FROM brc_transfer WHERE status = 'active' AND (from_line_id IN (?, ?) OR to_line_id IN (?, ?))", [$fromLine, $toLine, $fromLine, $toLine])->getRowArray()) {
            return [409, 'One of these lines is already part of a transfer.'];
        }
        $fa = Rec::cents($f['money_out']);
        $ta = Rec::cents($t['money_in']);
        $implied = round($ta / $fa, 6);
        $rate = null;
        $diff = null;
        if ($f['currency'] !== $t['currency']) {
            $foreign = $t['currency'] === 'LKR' ? $f['currency'] : $t['currency'];
            $r = Rec::rate($db, $foreign, $f['txn_date']);
            if ($r) {
                $rate = $r;
                if ($t['currency'] === 'LKR') {
                    $diff = $ta - Rec::toLkr($fa, $r['rate']);
                } else {
                    $diff = Rec::toLkr($ta, $r['rate']) - $fa;
                }
            }
        }
        $db->table('brc_transfer')->insert(['from_account_id' => (int) $f['bank_account_id'], 'to_account_id' => (int) $t['bank_account_id'], 'from_line_id' => $fromLine, 'to_line_id' => $toLine,
            'from_currency' => $f['currency'], 'to_currency' => $t['currency'], 'from_amount' => $f['money_out'], 'to_amount' => $t['money_in'], 'transfer_date' => $f['txn_date'],
            'implied_rate' => (string) $implied, 'daily_rate' => $rate['rate'] ?? null, 'daily_rate_source' => $rate['source'] ?? null, 'rate_difference_lkr' => $diff === null ? null : Rec::dec($diff),
            'status' => 'active', 'note' => mb_substr($note, 0, 300) ?: null, 'created_by' => $user, 'created_at' => Rec::now()]);
        $id = (int) $db->insertID();
        Rec::audit($db, 'brc_transfer_recorded', 'brc_transfer', $id, null, ['from_line' => $fromLine, 'to_line' => $toLine, 'from' => $f['money_out'] . ' ' . $f['currency'], 'to' => $t['money_in'] . ' ' . $t['currency'],
            'implied_rate' => $implied, 'daily_rate' => $rate['rate'] ?? null, 'rate_source' => $rate['source'] ?? null, 'rate_difference_lkr' => $diff === null ? null : Rec::dec($diff)], $note);
        $msg = 'Transfer recorded';
        if ($f['currency'] !== $t['currency']) {
            $msg .= $rate ? ' at ' . $implied . ' (day rate ' . rtrim(rtrim((string) $rate['rate'], '0'), '.') . ', difference ' . number_format($diff / 100, 2) . ' LKR)' : ' (no daily rate yet for that day)';
        }
        return [200, $msg . '. Each side is still matched to the ledger in its own account.', $id];
    }

    public static function undoTransfer($db, int $id, string $reason, string $user): array
    {
        $t = $db->query("SELECT * FROM brc_transfer WHERE id = ? AND status = 'active'", [$id])->getRowArray();
        if (!$t) {
            return [409, 'That transfer is not active.'];
        }
        if (mb_strlen(trim($reason)) < 5) {
            return [400, 'Give the reason (at least 5 characters).'];
        }
        $db->query("UPDATE brc_transfer SET status = 'undone', undone_by = ?, undone_at = ?, undo_reason = ? WHERE id = ?", [$user, Rec::now(), mb_substr($reason, 0, 300), $id]);
        Rec::audit($db, 'brc_transfer_undone', 'brc_transfer', $id, $t, ['status' => 'undone'], $reason);
        return [200, 'Transfer undone (kept in the record).'];
    }

    /** The transfer register with each side's ledger state. */
    public static function transfers($db, string $from, string $to): array
    {
        $rows = $db->query("SELECT t.*, fa.label AS from_label, ta.label AS to_label, fl.description AS from_desc, tl.description AS to_desc, fl.state AS from_state, tl.state AS to_state
            FROM brc_transfer t JOIN tpd_bank_account fa ON fa.id = t.from_account_id JOIN tpd_bank_account ta ON ta.id = t.to_account_id
            JOIN brc_line fl ON fl.id = t.from_line_id JOIN brc_line tl ON tl.id = t.to_line_id
            WHERE t.transfer_date BETWEEN ? AND ? ORDER BY t.transfer_date, t.id", [$from, $to])->getResultArray();
        return $rows;
    }
}
