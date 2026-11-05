<?php

namespace App\Libraries\BankRec;

/**
 * tf-bankrec-r1/r2 — matching statement lines with ledger rows, classifying what does not match (timing, duplicate-looking,
 * excluded), and transfers between Avenir's own accounts with the day's exchange rate. Suggestions are computed only;
 * nothing is matched until a preparer accepts it. Undoing keeps the record.
 *
 * r2: a timing item is offered in suggestions and can be matched; accepting the match clears its timing classification
 * (kept, with who classified it and when) and an undo restores it (D9). A match may be partial — the rest clears later —
 * and be completed by a later match that supersedes it. Every matched or classified item has one row in brc_claim, so
 * two users can never match or classify the same item at the same moment.
 */
final class Matching
{
    public const FX_TOLERANCE = 0.01;   // a foreign-currency line may be SUGGESTED for a ledger row within 1 %; the difference must still be explained

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

    private static function markView(?array $mk): ?array
    {
        return $mk ? ['id' => (int) $mk['id'], 'mark' => $mk['mark'], 'expected_date' => $mk['expected_date'], 'note' => $mk['note'], 'created_by' => $mk['created_by'], 'created_at' => $mk['created_at']] : null;
    }

    /**
     * The open lines and ledger rows of an account in a date range: not matched, and not classified as duplicate-looking or
     * excluded. Timing items are included (flagged "timing") so they can be matched when they clear (D9).
     */
    public static function openItems($db, int $accountId, string $from, string $to, int $bookDaysBefore = 0): array
    {
        $map = Rec::activeMap($db, $accountId);
        if (!$map) {
            return ['bank' => [], 'book' => []];
        }
        $start = max($from, $map['rec_start']);
        $act = Rec::activeLinks($db, $accountId, $map['ledger_code']);
        $bank = [];
        foreach (Rec::bankLines($db, $accountId, $start, $to) as $l) {
            $mk = $act['line_mark'][(int) $l['id']] ?? null;
            if (isset($act['line_match'][(int) $l['id']]) || ($mk && $mk['mark'] !== 'timing')) {
                continue;
            }
            $bank[] = $l + ['amount' => Rec::dec(Rec::cents($l['money_in']) - Rec::cents($l['money_out'])), 'timing' => self::markView($mk), 'correction' => $act['line_correction'][(int) $l['id']] ?? null];
        }
        $bookFrom = $bookDaysBefore > 0 ? date('Y-m-d', strtotime($map['rec_start'] . " -{$bookDaysBefore} days")) : $start;
        $opening = [];
        foreach ($db->query("SELECT id, book_key, status FROM brc_opening WHERE bank_account_id = ? AND source = 'ledger' AND status IN ('proposed', 'accepted')", [$accountId])->getResultArray() as $o) {
            $opening[$o['book_key']] = ['id' => (int) $o['id'], 'status' => $o['status']];
        }
        $book = [];
        foreach (Rec::bookRows($db, $map['ledger_code'], $bookFrom, date('Y-m-d', strtotime($to . ' +10 days'))) as $b) {
            $mk = $act['book_mark'][$b['key']] ?? null;
            if (isset($act['book_match'][$b['key']]) || ($mk && $mk['mark'] !== 'timing')) {
                continue;
            }
            $book[] = $b + ['timing' => self::markView($mk), 'before_start' => $b['date'] < $map['rec_start'], 'opening_item' => $opening[$b['key']] ?? null];
        }
        return ['bank' => $bank, 'book' => $book, 'map' => $map, 'act' => $act];
    }

    /**
     * Suggested matches (computed, not saved): one line to one ledger row of the same amount (LKR, or within 1 % of the LKR
     * value for USD / JPY) dated up to 10 days before or 3 after; one line to several rows that share a payment voucher (PV)
     * number and add up; a line that completes a partial match. Scored by PV / tour numbers in the wording and date
     * distance. Timing items are included. Any difference is shown and must be explained when accepting.
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
        $bv = static fn ($b) => ['key' => $b['key'], 'date' => $b['date'], 'amount' => $b['amount'], 'description' => $b['description'], 'pv' => $b['pv'], 'payee' => $b['payee'], 'timing' => $b['timing'] ?? null, 'before_start' => $b['before_start'] ?? false, 'opening_item' => $b['opening_item'] ?? null];
        // a line that completes a partial match
        $partials = array_values(array_filter($o['act']['matches'], static fn ($m) => ($m['kind'] ?? 'full') === 'partial'));
        $usedLine = [];
        foreach ($partials as $pm) {
            $rem = Rec::cents($pm['difference_lkr']);
            $bankSide = 0;
            $bookSide = 0;
            foreach ($pm['items'] as $it) {
                $it['side'] === 'bank' ? $bankSide += Rec::cents($it['amount']) : $bookSide += Rec::cents($it['amount']);
            }
            if (abs($bookSide) <= abs($bankSide)) {
                continue;   // the rest is a ledger row; offered in the ledger list
            }
            $maxDate = max(array_map(static fn ($i) => $i['item_date'], $pm['items']));
            foreach ($o['bank'] as $l) {
                if (isset($usedLine[$l['id']]) || $l['lkr_amount'] === null || $l['txn_date'] < $maxDate) {
                    continue;
                }
                $v = Rec::cents($l['lkr_amount']);
                $ok = $cur === 'LKR' ? $v === -$rem : (($v > 0) === (-$rem > 0) && abs($v + $rem) <= abs($rem) * self::FX_TOLERANCE);
                if ($ok) {
                    $usedLine[$l['id']] = true;
                    $out[] = ['type' => 'complete', 'match_id' => (int) $pm['id'], 'line' => ['id' => (int) $l['id'], 'date' => $l['txn_date'], 'description' => $l['description'], 'amount' => $l['amount'], 'amount_lkr' => $l['lkr_amount'], 'timing' => $l['timing']],
                        'book' => [], 'confidence' => 'high', 'why' => ['completes partial match #' . $pm['id'] . ' (' . Rec::fmt(-$rem) . ' still to clear)'], 'alternatives' => 0,
                        'difference_lkr' => Rec::dec($v + $rem), 'needs_explanation' => $v + $rem !== 0, 'clears_timing' => $l['timing'] ? 1 : 0, 'timing_ids' => $l['timing'] ? [$l['timing']['id']] : []];
                    break;
                }
            }
        }
        foreach ($o['bank'] as $l) {
            if (isset($usedLine[$l['id']]) || $l['lkr_amount'] === null || in_array($l['category'], ['charge'], true)) {
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
                if ($b['timing']) {
                    $why[] = 'timing item (classified by ' . $b['timing']['created_by'] . ($b['timing']['expected_date'] ? ', expected ' . $b['timing']['expected_date'] : '') . ') — accepting clears the classification';
                }
                if (!empty($b['before_start'])) {
                    $why[] = 'booked before the first day' . (!empty($b['opening_item']) ? ' — opening item #' . $b['opening_item']['id'] . ' (' . $b['opening_item']['status'] . ')' : ' — not an opening item yet');
                }
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
            $d = $target - array_sum(array_map(static fn ($b) => Rec::cents($b['amount']), $best['book']));
            if ($l['timing']) {
                $best['why'][] = 'the statement line is a timing item — accepting clears the classification';
            }
            $out[] = ['type' => 'match', 'line' => ['id' => (int) $l['id'], 'date' => $l['txn_date'], 'description' => $l['description'], 'amount' => $l['amount'], 'amount_lkr' => $l['lkr_amount'], 'timing' => $l['timing']],
                'book' => array_map($bv, $best['book']), 'confidence' => $conf, 'why' => $best['why'], 'alternatives' => count($cands) - 1,
                'difference_lkr' => Rec::dec($d), 'needs_explanation' => $d !== 0,
                'clears_timing' => ($l['timing'] ? 1 : 0) + count(array_filter($best['book'], static fn ($b) => !empty($b['timing']))),
                'timing_ids' => array_values(array_merge($l['timing'] ? [$l['timing']['id']] : [], array_map(static fn ($b) => $b['timing']['id'], array_filter($best['book'], static fn ($b) => !empty($b['timing'])))))];
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

    /**
     * Match statement line(s) with ledger row(s).
     *   $opt['partial']       true: one side is only part of the other; the rest is outstanding until a later match completes it
     *   $opt['expected_date'] when the rest of a partial match is expected
     *   $opt['supersedes']    the id of an active partial match this one completes or extends (its items are included)
     *   $opt['expect_timing'] the ids of the timing classifications the user saw on these items and agrees to clear; a
     *                         timing classification not in this list (e.g. made by another user a moment ago) is refused
     * A difference needs a reason; an exchange-rate or "other" difference also needs an explanation in the note — the
     * 1 % suggestion tolerance never explains anything by itself.
     */
    public static function create($db, int $accountId, array $lineIds, array $bookKeys, string $method, ?string $diffReason, string $note, string $user, array $opt = []): array
    {
        $map = Rec::activeMap($db, $accountId);
        if (!$map) {
            return [409, 'The account has no confirmed ledger mapping.'];
        }
        $lineIds = array_values(array_unique(array_map('intval', $lineIds)));
        $bookKeys = array_values(array_unique(array_map('strval', $bookKeys)));
        $supersedes = (int) ($opt['supersedes'] ?? 0);
        $partial = !empty($opt['partial']);
        $expected = $opt['expected_date'] ?? null;
        $expectTiming = array_map('intval', (array) ($opt['expect_timing'] ?? []));
        if ($expected !== null && $expected !== '' && !Rec::validDate($expected)) {
            return [400, 'The expected date is not valid.'];
        }
        if (!$supersedes && (!$lineIds || !$bookKeys)) {
            return [400, 'Choose at least one statement line and one ledger row.'];
        }
        if ($supersedes && !$lineIds && !$bookKeys) {
            return [400, 'Choose the statement line(s) or ledger row(s) to add to the partial match.'];
        }
        if (count($lineIds) > 50 || count($bookKeys) > 50) {
            return [400, 'Too many items in one match.'];
        }
        return Rec::locked($db, $accountId, static function () use ($db, $accountId, $map, $lineIds, $bookKeys, $method, $diffReason, $note, $user, $supersedes, $partial, $expected, $expectTiming) {
            $old = null;
            if ($supersedes) {
                $old = $db->query("SELECT * FROM brc_match WHERE id = ? AND bank_account_id = ? AND status = 'active'", [$supersedes, $accountId])->getRowArray();
                if (!$old || ($old['kind'] ?? 'full') !== 'partial') {
                    return [409, 'That match is not an active partial match of this account.'];
                }
                foreach ($db->query('SELECT * FROM brc_match_item WHERE match_id = ?', [$supersedes])->getResultArray() as $it) {
                    $it['side'] === 'bank' ? $lineIds[] = (int) $it['line_id'] : $bookKeys[] = (string) $it['book_key'];
                }
                $lineIds = array_values(array_unique($lineIds));
                $bookKeys = array_values(array_unique($bookKeys));
                if ($expected === null || $expected === '') {
                    $expected = $old['expected_date'];
                }
            }
            $claims = [];   // item key => existing claim to take over: ['kind' => match|mark, 'ref' => id, 'mark' => row]
            $check = static function (string $key, string $what) use ($db, $supersedes, $expectTiming, &$claims) {
                $c = $db->query('SELECT * FROM brc_claim WHERE item_key = ?', [$key])->getRowArray();
                if (!$c) {
                    return null;
                }
                if ($c['kind'] === 'match' && $supersedes && (int) $c['ref_id'] === $supersedes) {
                    $claims[$key] = ['kind' => 'match', 'ref' => $supersedes];
                    return null;
                }
                if ($c['kind'] === 'mark') {
                    $mk = $db->query('SELECT * FROM brc_mark WHERE id = ?', [(int) $c['ref_id']])->getRowArray();
                    if ($mk && $mk['mark'] === 'timing' && $mk['status'] === 'active') {
                        if (!in_array((int) $mk['id'], $expectTiming, true)) {
                            return [409, "{$what} is classified as timing (by {$mk['created_by']} at {$mk['created_at']}" . ($mk['expected_date'] ? ", expected {$mk['expected_date']}" : '') . '). Reload and confirm that the match clears this timing classification; nothing was saved.'];
                        }
                        $claims[$key] = ['kind' => 'mark', 'ref' => (int) $mk['id'], 'mark' => $mk];
                        return null;
                    }
                    $kindName = ($mk['mark'] ?? '') === 'duplicate' ? 'duplicate-looking' : ($mk['mark'] ?? 'classified');
                    return [409, "{$what} is classified as {$kindName}; clear that classification before matching it."];
                }
                return [409, "{$what} is already matched (match #{$c['ref_id']})."];
            };
            $lines = [];
            foreach ($lineIds as $id) {
                $l = $db->query("SELECT l.* FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted' WHERE l.id = ? AND l.bank_account_id = ?", [$id, $accountId])->getRowArray();
                if (!$l) {
                    return [404, "Statement line {$id} is not an accepted line of this account."];
                }
                if ($l['txn_date'] < $map['rec_start']) {
                    return [409, "Statement line {$id} is dated before the first day of the reconciliation."];
                }
                if ($e = $check(Rec::lineKey($id), "Statement line {$id}")) {
                    return $e;
                }
                if ($l['lkr_amount'] === null) {
                    return [409, "Statement line {$id} has no LKR value yet (enter the {$l['currency']} rate for {$l['txn_date']})."];
                }
                $lines[] = $l;
            }
            $ids = array_map(static fn ($k) => (int) explode('.', $k)[0], $bookKeys);
            $rows = Rec::bookRowsForIds($db, $map['ledger_code'], $ids);
            $book = [];
            $preStart = [];
            foreach ($bookKeys as $k) {
                if (!preg_match('/^(\d+)\.(\d+)$/', $k)) {
                    return [400, "Ledger row {$k} is not valid."];
                }
                if (!isset($rows[$k])) {
                    return [404, "Ledger row {$k} is not on the account's ledger {$map['ledger_code']}."];
                }
                if ($e = $check(Rec::bookKey($accountId, $k), "Ledger row {$k}")) {
                    return $e;
                }
                if ($rows[$k]['date'] < $map['rec_start']) {
                    $preStart[] = $k;
                }
                $book[] = $rows[$k];
            }
            if (!$lines || !$book) {
                return [400, 'A match needs at least one statement line and one ledger row.'];
            }
            $bankTotal = array_sum(array_map(static fn ($l) => Rec::cents($l['money_in']) - Rec::cents($l['money_out']), $lines));
            $bankLkr = array_sum(array_map(static fn ($l) => Rec::cents($l['lkr_amount']), $lines));
            $bookTotal = array_sum(array_map(static fn ($b) => Rec::cents($b['amount']), $book));
            $diff = $bankLkr - $bookTotal;
            $cur = $map['account']['currency'];
            $kind = 'full';
            if ($partial) {
                if ($diff === 0) {
                    return [400, 'The two sides are equal; this is a full match, not a partial one.'];
                }
                if ($bankLkr === 0 || $bookTotal === 0 || ($bankLkr > 0) !== ($bookTotal > 0)) {
                    return [400, 'A partial match needs both sides in the same direction.'];
                }
                if (mb_strlen(trim($note)) < 5) {
                    return [400, 'Say what the rest is and when it is expected to clear (note, at least 5 characters).'];
                }
                $kind = 'partial';
                $diffReason = 'partial';
            } elseif ($diff !== 0) {
                if (($bankLkr > 0) !== ($bookTotal > 0)) {
                    return [400, 'The statement side and the ledger side go in opposite directions.'];
                }
                if (!in_array($diffReason, ['exchange rate', 'rounding', 'other'], true)) {
                    return [400, 'The two sides differ by ' . Rec::fmt($diff) . ' LKR. Say why: exchange rate, rounding or other — or record a partial match if the rest clears later.'];
                }
                if ($diffReason === 'rounding' && abs($diff) > 100) {
                    return [400, 'Rounding covers up to 1.00 LKR.'];
                }
                if ($diffReason === 'exchange rate' && ($cur === 'LKR' || abs($diff) > abs($bookTotal) * 0.05)) {
                    return [400, $cur === 'LKR' ? 'An LKR account has no exchange-rate difference.' : 'An exchange-rate difference above 5 % needs the reason "other".'];
                }
                if (in_array($diffReason, ['exchange rate', 'other'], true) && mb_strlen(trim($note)) < 5) {
                    return [400, 'Explain the ' . ($diffReason === 'exchange rate' ? 'exchange-rate ' : '') . 'difference of ' . Rec::fmt($diff) . ' LKR in the note (e.g. the rate the bank applied against the day\'s rate). A tolerance never explains it by itself.'];
                }
            }
            $mid = 0;
            $cleared = [];
            $err = Rec::tx($db, static function () use ($db, $accountId, $map, $method, $bankTotal, $bankLkr, $bookTotal, $diff, $diffReason, $note, $user, $kind, $expected, $supersedes, $lines, $book, $claims, &$mid, &$cleared) {
                $now = Rec::now();
                if (!Rec::ins($db, 'brc_match', ['bank_account_id' => $accountId, 'method' => in_array($method, ['suggested', 'complete'], true) ? 'suggested' : 'manual', 'bank_total' => Rec::dec($bankTotal), 'bank_total_lkr' => Rec::dec($bankLkr),
                    'book_total' => Rec::dec($bookTotal), 'difference_lkr' => Rec::dec($diff), 'diff_reason' => $diff !== 0 ? $diffReason : null, 'status' => 'active', 'note' => mb_substr($note, 0, 300) ?: null,
                    'kind' => $kind, 'remainder_lkr' => $kind === 'partial' ? Rec::dec($diff) : null, 'expected_date' => $kind === 'partial' ? ($expected ?: null) : null, 'supersedes_id' => $supersedes ?: null,
                    'diff_note' => ($diff !== 0 && trim($note) !== '') ? mb_substr(trim($note), 0, 300) : null, 'diff_noted_by' => ($diff !== 0 && trim($note) !== '') ? $user : null, 'diff_noted_at' => ($diff !== 0 && trim($note) !== '') ? $now : null,
                    'created_by' => $user, 'created_at' => $now])) {
                    return [409, 'Not saved.'];
                }
                $mid = (int) $db->insertID();
                if ($supersedes) {
                    $db->query("UPDATE brc_match SET status = 'superseded', superseded_by = ? WHERE id = ? AND status = 'active'", [$mid, $supersedes]);
                    if ($db->affectedRows() !== 1) {
                        return [409, 'The partial match was changed by someone else a moment ago; reload.'];
                    }
                }
                $take = static function (string $key) use ($db, $accountId, $mid, $user, $now, $claims, &$cleared) {
                    $c = $claims[$key] ?? null;
                    if (!$c) {
                        return Rec::ins($db, 'brc_claim', ['item_key' => $key, 'bank_account_id' => $accountId, 'kind' => 'match', 'ref_id' => $mid, 'claimed_by' => $user, 'claimed_at' => $now]);
                    }
                    if ($c['kind'] === 'mark') {
                        $db->query("UPDATE brc_mark SET status = 'matched', cleared_by = ?, cleared_at = ?, clear_reason = ?, cleared_by_match = ? WHERE id = ? AND status = 'active'", [$user, $now, 'cleared by match #' . $mid, $mid, $c['ref']]);
                        if ($db->affectedRows() !== 1) {
                            return false;
                        }
                        if (!Rec::ins($db, 'brc_mark_event', ['mark_id' => $c['ref'], 'event' => 'cleared_by_match', 'match_id' => $mid, 'by_user' => $user, 'at' => $now, 'note' => null])) {
                            return false;
                        }
                        $cleared[] = $c['mark'];
                    }
                    $db->query('UPDATE brc_claim SET kind = ?, ref_id = ?, claimed_by = ?, claimed_at = ? WHERE item_key = ? AND kind = ? AND ref_id = ?', ['match', $mid, $user, $now, $key, $c['kind'], $c['ref']]);
                    return $db->affectedRows() === 1;
                };
                foreach ($lines as $l) {
                    if (!Rec::ins($db, 'brc_match_item', ['match_id' => $mid, 'side' => 'bank', 'line_id' => (int) $l['id'], 'amount' => Rec::dec(Rec::cents($l['money_in']) - Rec::cents($l['money_out'])), 'item_date' => $l['txn_date'],
                        'snapshot' => json_encode(['description' => $l['description'], 'lkr' => $l['lkr_amount'], 'rate' => $l['fx_rate']], JSON_UNESCAPED_UNICODE)])) {
                        return [409, 'Not saved.'];
                    }
                    if (!$take(Rec::lineKey((int) $l['id']))) {
                        return [409, 'Statement line ' . $l['id'] . ' was matched or classified by someone else a moment ago; nothing was saved. Reload.'];
                    }
                    $db->query("UPDATE brc_line SET state = 'matched' WHERE id = ?", [(int) $l['id']]);
                }
                foreach ($book as $b) {
                    if (!Rec::ins($db, 'brc_match_item', ['match_id' => $mid, 'side' => 'book', 'book_key' => $b['key'], 'amount' => $b['amount'], 'item_date' => $b['date'],
                        'snapshot' => json_encode(['id' => $b['id'], 'description' => $b['description'], 'pv' => $b['pv'], 'dr' => $b['dr'], 'cr' => $b['cr'], 'fp' => $b['fp']], JSON_UNESCAPED_UNICODE)])) {
                        return [409, 'Not saved.'];
                    }
                    if (!$take(Rec::bookKey($accountId, $b['key']))) {
                        return [409, 'Ledger row ' . $b['key'] . ' was matched or classified by someone else a moment ago; nothing was saved. Reload.'];
                    }
                }
                return null;
            });
            if ($err) {
                return $err;
            }
            $label = $map['account']['label'];
            Rec::audit($db, 'brc_matched', 'brc_match', $mid, $supersedes ? ['partial_match' => $supersedes] : null, ['kind' => $kind, 'lines' => array_map(static fn ($l) => (int) $l['id'], $lines), 'ledger_rows' => array_map(static fn ($b) => $b['key'], $book),
                'bank' => Rec::dec($bankTotal), 'bank_lkr' => Rec::dec($bankLkr), 'book' => Rec::dec($bookTotal), 'difference_lkr' => Rec::dec($diff), 'reason' => $diff !== 0 ? $diffReason : null, 'explanation' => $diff !== 0 ? (trim($note) ?: null) : null,
                'expected_date' => $kind === 'partial' ? $expected : null, 'supersedes' => $supersedes ?: null, 'method' => $method, 'timing_cleared' => array_map(static fn ($m) => (int) $m['id'], $cleared)], $note, $label);
            foreach ($cleared as $mk) {
                Rec::audit($db, 'brc_timing_cleared_by_match', 'brc_mark', $mk['id'], ['status' => 'active', 'mark' => 'timing', 'classified_by' => $mk['created_by'], 'classified_at' => $mk['created_at'], 'expected_date' => $mk['expected_date'], 'note' => $mk['note']],
                    ['status' => 'matched', 'match' => $mid], 'cleared by match #' . $mid, $label);
            }
            $msg = $kind === 'partial' ? 'Partly matched: ' . Rec::fmt(abs($diff)) . ' LKR still to clear' . ($expected ? ' (expected ' . $expected . ')' : '') . '; it stays outstanding until a later match completes it.'
                : ($supersedes ? 'Partial match #' . $supersedes . ' completed (kept in the record).' : 'Matched' . ($diff !== 0 ? ' (difference ' . Rec::fmt($diff) . ' LKR: ' . $diffReason . ', explained)' : '') . '.');
            if ($cleared) {
                $msg .= ' The timing classification of ' . count($cleared) . ' item(s) is cleared by this match (kept in the record; an undo restores it).';
            }
            if ($preStart) {
                $unacc = [];
                foreach ($preStart as $k) {
                    $o = $db->query("SELECT status FROM brc_opening WHERE active_key = ?", ['O' . $accountId . ':' . $k])->getRowArray();
                    if (!$o || $o['status'] !== 'accepted') {
                        $unacc[] = $k . ($o ? ' (opening item ' . $o['status'] . ')' : '');
                    }
                }
                if ($unacc) {
                    $msg .= ' Note: ledger row(s) ' . implode(', ', $unacc) . ' were booked before the first day and are not accepted opening items; clearing them does not explain the opening difference until they are.';
                }
            }
            return [200, $msg, $mid];
        });
    }

    /** Undo a match: kept as "undone"; a timing classification it cleared is restored; a partial match it completed is active again. */
    public static function undo($db, int $matchId, string $reason, string $user): array
    {
        $m = $db->query("SELECT * FROM brc_match WHERE id = ? AND status = 'active'", [$matchId])->getRowArray();
        if (!$m) {
            return [409, 'That match is not active.'];
        }
        if (mb_strlen(trim($reason)) < 5) {
            return [400, 'Give the reason (at least 5 characters).'];
        }
        $acct = (int) $m['bank_account_id'];
        return Rec::locked($db, $acct, static function () use ($db, $matchId, $reason, $user, $acct) {
            $m = $db->query("SELECT * FROM brc_match WHERE id = ? AND status = 'active'", [$matchId])->getRowArray();
            if (!$m) {
                return [409, 'That match was undone by someone else a moment ago.'];
            }
            $items = $db->query('SELECT * FROM brc_match_item WHERE match_id = ?', [$matchId])->getResultArray();
            $oldId = (int) ($m['supersedes_id'] ?? 0);
            $oldKeys = [];
            if ($oldId) {
                foreach ($db->query('SELECT * FROM brc_match_item WHERE match_id = ?', [$oldId])->getResultArray() as $it) {
                    $oldKeys[$it['side'] === 'bank' ? Rec::lineKey((int) $it['line_id']) : Rec::bookKey($acct, $it['book_key'])] = true;
                }
            }
            $marks = [];
            foreach ($db->query("SELECT * FROM brc_mark WHERE cleared_by_match = ? AND status = 'matched'", [$matchId])->getResultArray() as $mk) {
                $marks[$mk['side'] === 'bank' ? Rec::lineKey((int) $mk['line_id']) : Rec::bookKey($acct, $mk['book_key'])] = $mk;
            }
            $restored = [];
            $err = Rec::tx($db, static function () use ($db, $matchId, $reason, $user, $acct, $items, $oldId, $oldKeys, $marks, &$restored) {
                $now = Rec::now();
                $db->query("UPDATE brc_match SET status = 'undone', undone_by = ?, undone_at = ?, undo_reason = ? WHERE id = ? AND status = 'active'", [$user, $now, mb_substr($reason, 0, 300), $matchId]);
                if ($db->affectedRows() !== 1) {
                    return [409, 'That match was undone by someone else a moment ago.'];
                }
                if ($oldId) {
                    $db->query("UPDATE brc_match SET status = 'active', superseded_by = NULL WHERE id = ? AND status = 'superseded' AND superseded_by = ?", [$oldId, $matchId]);
                    if ($db->affectedRows() !== 1) {
                        return [409, 'The partial match this one completed could not be restored; nothing was undone.'];
                    }
                }
                foreach ($items as $it) {
                    $key = $it['side'] === 'bank' ? Rec::lineKey((int) $it['line_id']) : Rec::bookKey($acct, $it['book_key']);
                    $state = 'open';
                    if (isset($oldKeys[$key])) {
                        $db->query("UPDATE brc_claim SET ref_id = ? WHERE item_key = ? AND kind = 'match' AND ref_id = ?", [$oldId, $key, $matchId]);
                        $ok = $db->affectedRows() === 1;
                        $state = 'matched';
                    } elseif ($mk = $marks[$key] ?? null) {
                        $db->query("UPDATE brc_mark SET status = 'active', cleared_by = NULL, cleared_at = NULL, clear_reason = NULL, cleared_by_match = NULL WHERE id = ? AND status = 'matched'", [(int) $mk['id']]);
                        $ok = $db->affectedRows() === 1;
                        $db->query("UPDATE brc_claim SET kind = 'mark', ref_id = ? WHERE item_key = ? AND kind = 'match' AND ref_id = ?", [(int) $mk['id'], $key, $matchId]);
                        $ok = $ok && $db->affectedRows() === 1 && Rec::ins($db, 'brc_mark_event', ['mark_id' => (int) $mk['id'], 'event' => 'restored_by_undo', 'match_id' => $matchId, 'by_user' => $user, 'at' => $now, 'note' => mb_substr($reason, 0, 300)]);
                        $restored[] = $mk;
                        $state = $mk['mark'];
                    } else {
                        $db->query("DELETE FROM brc_claim WHERE item_key = ? AND kind = 'match' AND ref_id = ?", [$key, $matchId]);
                        $ok = $db->affectedRows() === 1;
                    }
                    if (!$ok) {
                        return [409, 'The match\'s items changed while undoing; nothing was undone. Reload.'];
                    }
                    if ($it['side'] === 'bank') {
                        $db->query('UPDATE brc_line SET state = ? WHERE id = ?', [$state, (int) $it['line_id']]);
                    }
                }
                return null;
            });
            if ($err) {
                return $err;
            }
            $a = Rec::account($db, $acct);
            Rec::audit($db, 'brc_match_undone', 'brc_match', $matchId, ['status' => 'active', 'kind' => $m['kind'] ?? 'full', 'items' => $items], ['status' => 'undone', 'partial_match_restored' => $oldId ?: null, 'timing_restored' => array_map(static fn ($k) => (int) $k['id'], $restored)], $reason, $a['label'] ?? null);
            foreach ($restored as $mk) {
                Rec::audit($db, 'brc_timing_restored_by_undo', 'brc_mark', $mk['id'], ['status' => 'matched', 'match' => $matchId], ['status' => 'active', 'mark' => 'timing', 'classified_by' => $mk['created_by'], 'classified_at' => $mk['created_at'], 'expected_date' => $mk['expected_date']], $reason, $a['label'] ?? null);
            }
            return [200, 'Match undone (kept in the record).' . ($restored ? ' The timing classification of ' . count($restored) . ' item(s) is restored, as first recorded.' : '') . ($oldId ? ' Partial match #' . $oldId . ' is active again.' : '')];
        });
    }

    /** Explain a difference inside an active match (an exchange-rate or other difference recorded without one, or one that appeared after a rate change). */
    public static function explain($db, int $matchId, ?string $reason, string $note, string $user): array
    {
        $m = $db->query("SELECT * FROM brc_match WHERE id = ? AND status = 'active'", [$matchId])->getRowArray();
        if (!$m) {
            return [409, 'That match is not active.'];
        }
        if (($m['kind'] ?? 'full') === 'partial') {
            return [400, 'A partial match is completed by matching the rest, not explained.'];
        }
        if (mb_strlen(trim($note)) < 5) {
            return [400, 'Write the explanation (at least 5 characters).'];
        }
        $map = Rec::activeMap($db, (int) $m['bank_account_id']);
        $reason = $reason ?: $m['diff_reason'];
        if (!in_array($reason, ['exchange rate', 'rounding', 'other'], true)) {
            return [400, 'Say why: exchange rate, rounding or other.'];
        }
        if ($reason === 'exchange rate' && ($map['account']['currency'] ?? 'LKR') === 'LKR') {
            return [400, 'An LKR account has no exchange-rate difference.'];
        }
        $db->query("UPDATE brc_match SET diff_reason = ?, diff_note = ?, diff_noted_by = ?, diff_noted_at = ? WHERE id = ? AND status = 'active'", [$reason, mb_substr(trim($note), 0, 300), $user, Rec::now(), $matchId]);
        if ($db->affectedRows() !== 1) {
            return [409, 'That match changed a moment ago; reload.'];
        }
        Rec::audit($db, 'brc_difference_explained', 'brc_match', $matchId, ['reason' => $m['diff_reason'], 'note' => $m['diff_note'] ?? null, 'difference_lkr' => $m['difference_lkr']], ['reason' => $reason, 'note' => trim($note)], $note, $map['account']['label'] ?? null);
        return [200, 'Explanation recorded.'];
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
        if ($side === 'bank') {
            $id = (int) $ref;
            $l = $db->query("SELECT l.* FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted' WHERE l.id = ? AND l.bank_account_id = ?", [$id, $accountId])->getRowArray();
            if (!$l) {
                return [404, 'Not an accepted line of this account.'];
            }
            $row = ['line_id' => $id, 'book_key' => null, 'amount' => Rec::dec(Rec::cents($l['money_in']) - Rec::cents($l['money_out'])), 'item_date' => $l['txn_date']];
            $key = Rec::lineKey($id);
        } elseif ($side === 'book') {
            $k = (string) $ref;
            if (!preg_match('/^(\d+)\.(\d+)$/', $k, $mm)) {
                return [400, 'Not a valid ledger row.'];
            }
            $rows = Rec::bookRowsForIds($db, $map['ledger_code'], [(int) $mm[1]]);
            if (!isset($rows[$k])) {
                return [404, 'Not a ledger row of this account.'];
            }
            $row = ['line_id' => null, 'book_key' => $k, 'amount' => $rows[$k]['amount'], 'item_date' => $rows[$k]['date']];
            $key = Rec::bookKey($accountId, $k);
        } else {
            return [400, 'Side must be bank or book.'];
        }
        return Rec::locked($db, $accountId, static function () use ($db, $accountId, $side, $row, $key, $mark, $note, $expected, $related, $user, $map) {
            $c = $db->query('SELECT * FROM brc_claim WHERE item_key = ?', [$key])->getRowArray();
            if ($c) {
                return [409, ($side === 'bank' ? 'That line' : 'That ledger row') . ' is already ' . ($c['kind'] === 'match' ? 'matched (match #' . $c['ref_id'] . ')' : 'classified') . '.'];
            }
            $mid = 0;
            $err = Rec::tx($db, static function () use ($db, $accountId, $side, $row, $key, $mark, $note, $expected, $related, $user, &$mid) {
                if (!Rec::ins($db, 'brc_mark', $row + ['bank_account_id' => $accountId, 'side' => $side, 'mark' => $mark, 'related' => $related ? mb_substr($related, 0, 60) : null,
                    'expected_date' => ($mark === 'timing' && $expected) ? $expected : null, 'note' => mb_substr($note, 0, 300), 'status' => 'active', 'created_by' => $user, 'created_at' => Rec::now()])) {
                    return [409, 'Not saved.'];
                }
                $mid = (int) $db->insertID();
                if (!Rec::ins($db, 'brc_claim', ['item_key' => $key, 'bank_account_id' => $accountId, 'kind' => 'mark', 'ref_id' => $mid, 'claimed_by' => $user, 'claimed_at' => Rec::now()])) {
                    return [409, 'That item was matched or classified by someone else a moment ago; nothing was saved.'];
                }
                if ($side === 'bank') {
                    $db->query('UPDATE brc_line SET state = ? WHERE id = ?', [$mark, $row['line_id']]);
                }
                return null;
            });
            if ($err) {
                return $err;
            }
            Rec::audit($db, 'brc_classified', 'brc_mark', $mid, null, $row + ['side' => $side, 'mark' => $mark, 'expected' => $expected, 'related' => $related], $note, $map['account']['label']);
            return [200, 'Classified as ' . ($mark === 'duplicate' ? 'duplicate-looking' : $mark) . '.', $mid];
        });
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
        return Rec::locked($db, (int) $k['bank_account_id'], static function () use ($db, $k, $markId, $reason, $user) {
            $err = Rec::tx($db, static function () use ($db, $k, $markId, $reason, $user) {
                $db->query("UPDATE brc_mark SET status = 'cleared', cleared_by = ?, cleared_at = ?, clear_reason = ? WHERE id = ? AND status = 'active'", [$user, Rec::now(), mb_substr($reason, 0, 300), $markId]);
                if ($db->affectedRows() !== 1) {
                    return [409, 'That classification was changed by someone else a moment ago.'];
                }
                $db->query("DELETE FROM brc_claim WHERE kind = 'mark' AND ref_id = ?", [$markId]);
                if ($k['side'] === 'bank') {
                    $db->query("UPDATE brc_line SET state = 'open' WHERE id = ? AND state = ?", [(int) $k['line_id'], $k['mark']]);
                }
                return null;
            });
            if ($err) {
                return $err;
            }
            $a = Rec::account($db, (int) $k['bank_account_id']);
            Rec::audit($db, 'brc_classification_cleared', 'brc_mark', $markId, $k, ['status' => 'cleared'], $reason, $a['label'] ?? null);
            return [200, 'Classification cleared.'];
        });
    }

    /** The history of the timing classifications of an account (cleared by a match, restored by an undo). */
    public static function markHistory($db, int $accountId): array
    {
        return $db->query("SELECT e.*, k.side, k.line_id, k.book_key, k.amount, k.item_date, k.expected_date, k.created_by AS classified_by, k.created_at AS classified_at, k.status AS mark_status
            FROM brc_mark_event e JOIN brc_mark k ON k.id = e.mark_id WHERE k.bank_account_id = ? ORDER BY e.id DESC LIMIT 200", [$accountId])->getResultArray();
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
        if (!Rec::ins($db, 'brc_transfer', ['from_account_id' => (int) $f['bank_account_id'], 'to_account_id' => (int) $t['bank_account_id'], 'from_line_id' => $fromLine, 'to_line_id' => $toLine,
            'from_currency' => $f['currency'], 'to_currency' => $t['currency'], 'from_amount' => $f['money_out'], 'to_amount' => $t['money_in'], 'transfer_date' => $f['txn_date'],
            'implied_rate' => (string) $implied, 'daily_rate' => $rate['rate'] ?? null, 'daily_rate_source' => $rate['source'] ?? null, 'rate_difference_lkr' => $diff === null ? null : Rec::dec($diff),
            'status' => 'active', 'active_from' => $fromLine, 'active_to' => $toLine, 'note' => mb_substr($note, 0, 300) ?: null, 'created_by' => $user, 'created_at' => Rec::now()])) {
            return [409, 'One of these lines was recorded in a transfer by someone else a moment ago; reload.'];
        }
        $id = (int) $db->insertID();
        Rec::audit($db, 'brc_transfer_recorded', 'brc_transfer', $id, null, ['from_line' => $fromLine, 'to_line' => $toLine, 'from' => $f['money_out'] . ' ' . $f['currency'], 'to' => $t['money_in'] . ' ' . $t['currency'],
            'implied_rate' => $implied, 'daily_rate' => $rate['rate'] ?? null, 'rate_source' => $rate['source'] ?? null, 'rate_difference_lkr' => $diff === null ? null : Rec::dec($diff)], $note);
        $msg = 'Transfer recorded';
        if ($f['currency'] !== $t['currency']) {
            $msg .= $rate ? ' at ' . $implied . ' (day rate ' . rtrim(rtrim((string) $rate['rate'], '0'), '.') . ', difference ' . number_format($diff / 100, 2) . ' LKR)' : ' (no daily rate yet for that day)';
        }
        return [200, $msg . '. Each side is still matched to the ledger in its own account, where an exchange-rate difference must be explained.', $id];
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
        $db->query("UPDATE brc_transfer SET status = 'undone', active_from = NULL, active_to = NULL, undone_by = ?, undone_at = ?, undo_reason = ? WHERE id = ? AND status = 'active'", [$user, Rec::now(), mb_substr($reason, 0, 300), $id]);
        if ($db->affectedRows() !== 1) {
            return [409, 'That transfer was undone by someone else a moment ago.'];
        }
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
