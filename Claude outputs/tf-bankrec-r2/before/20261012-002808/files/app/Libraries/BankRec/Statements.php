<?php

namespace App\Libraries\BankRec;

use App\Libraries\TourPayments\BankAccounts;

/**
 * tf-bankrec-r1 — bank statements: read a CSV export (or lines typed in), check it, keep it as a draft, accept it.
 * Nothing here ever writes to the ledger. A statement's lines keep the values as imported (orig_json); a draft line
 * changed later keeps both, and every change is audited.
 */
final class Statements
{
    public const CATEGORIES = ['receipt', 'payment', 'charge', 'transfer', 'interest', 'other'];
    private const DATE_FORMATS = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y', 'd-M-Y', 'd M Y', 'd-M-y', 'j/n/Y', 'j-M-Y', 'Y/m/d', 'd M y'];

    // ---------------------------------------------------------------- reading

    /** CSV text → rows (arrays of cells). Detects the separator; accepts a UTF-8 BOM; converts Windows-1252 text. */
    public static function csvRows(string $text): array
    {
        if (str_starts_with($text, "\xEF\xBB\xBF")) {
            $text = substr($text, 3);
        }
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }
        $sample = implode("\n", array_slice(preg_split('/\r\n|\r|\n/', $text), 0, 20));
        $sep = ',';
        $best = -1;
        foreach ([',', ';', "\t", '|'] as $c) {
            $n = substr_count($sample, $c);
            if ($n > $best) {
                $best = $n;
                $sep = $c;
            }
        }
        $fh = fopen('php://memory', 'r+');
        fwrite($fh, $text);
        rewind($fh);
        $rows = [];
        while (($r = fgetcsv($fh, 0, $sep, '"', '\\')) !== false) {
            if ($r === [null] || (count($r) === 1 && trim((string) $r[0]) === '')) {
                continue;
            }
            $rows[] = array_map(static fn ($c) => trim((string) $c), $r);
        }
        fclose($fh);
        return $rows;
    }

    /** Finds the header row and guesses which column is which. */
    public static function guessMap(array $rows): array
    {
        $want = [
            'value_date' => '/value\s*date/i',
            'date' => '/^(txn|tran|transaction|posting|post|book(ing)?)?\s*\.?\s*date$|^date$|tran.*date|posting\s*date/i',
            'description' => '/desc|particular|narration|details|remark|transaction$/i',
            'reference' => '/ref|cheque|chq|instrument/i',
            'money_out' => '/debit|withdraw|payment|^dr$|paid\s*out|money\s*out/i',
            'money_in' => '/credit|deposit|receipt|^cr$|paid\s*in|money\s*in/i',
            'amount' => '/^amount|^amt/i',
            'balance' => '/balance/i',
        ];
        $headerAt = null;
        $map = [];
        foreach (array_slice($rows, 0, 25, true) as $i => $r) {
            $hits = [];
            foreach ($r as $ci => $cell) {
                foreach ($want as $k => $re) {
                    if (!isset($hits[$k]) && $cell !== '' && preg_match($re, $cell)) {
                        if ($k === 'date' && preg_match('/value/i', $cell)) {
                            continue;
                        }
                        $hits[$k] = $ci;
                        break;
                    }
                }
            }
            if (isset($hits['date']) && (isset($hits['money_in']) || isset($hits['money_out']) || isset($hits['amount']))) {
                $headerAt = $i;
                $map = $hits;
                break;
            }
        }
        return ['header_row' => $headerAt, 'map' => $map, 'headers' => $headerAt === null ? [] : $rows[$headerAt]];
    }

    public static function parseDate(string $v, ?string $fmt = null): ?string
    {
        $v = trim(preg_replace('/\s+/', ' ', $v));
        if ($v === '') {
            return null;
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T]\d{2}:\d{2}/', $v, $m)) {
            $v = $m[1];
        }
        foreach ($fmt ? [$fmt] : self::DATE_FORMATS as $f) {
            $d = \DateTime::createFromFormat('!' . $f, $v);
            $e = \DateTime::getLastErrors();
            if ($d && (!$e || ($e['warning_count'] === 0 && $e['error_count'] === 0))) {
                $out = $d->format('Y-m-d');
                return Rec::validDate($out) ? $out : null;
            }
        }
        return null;
    }

    /** "1,234.50", "(1,234.50)", "1234.5 CR", "-12" → signed decimal string; '' → null; unreadable → false. */
    public static function parseAmount(string $v)
    {
        $v = strtoupper(trim(str_replace(["\xC2\xA0", ' '], '', $v)));
        if ($v === '' || $v === '-') {
            return null;
        }
        $sign = 1;
        if (preg_match('/^\((.*)\)$/', $v, $m)) {
            $v = $m[1];
            $sign = -1;
        }
        if (preg_match('/^(.*?)(CR|DR)$/', $v, $m)) {
            $v = $m[1];
            if ($m[2] === 'DR') {
                $sign = -$sign;
            }
        }
        $v = preg_replace('/^(LKR|USD|JPY|RS\.?)/', '', $v);
        $v = str_replace(',', '', $v);
        if (str_starts_with($v, '-')) {
            $sign = -$sign;
            $v = substr($v, 1);
        }
        if (!preg_match('/^\d+(\.\d+)?$/', $v)) {
            return false;
        }
        return Rec::dec($sign * Rec::cents($v));
    }

    /** Category and charge type from the wording (the preparer can change it). */
    public static function categorise(string $desc, int $in, int $out, array $otherAccountNos = []): array
    {
        $d = strtoupper($desc);
        foreach ($otherAccountNos as $no) {
            if (strlen($no) >= 6 && str_contains(preg_replace('/[^0-9A-Z]/', '', $d), $no)) {
                return ['transfer', null];
            }
        }
        if ($out > 0 && preg_match('/\b(CHGS?|CHARGES?|FEES?|COMMISSION|COMM|GPRS|SMS\s*ALERT|STAMP\s*DUTY|SERVICE\s*CHARGE|DEBIT\s*TAX|LEVY)\b/', $d)) {
            $t = str_contains($d, 'CEFT') ? 'CEFT transfer fee' : (preg_match('/FUND|TRF|TRANSFER/', $d) ? 'Fund transfer fee' : (str_contains($d, 'GPRS') ? 'GPRS rental' : (str_contains($d, 'SMS') ? 'SMS alert fee' : 'Bank charge')));
            return ['charge', $t];
        }
        if (preg_match('/\bINTEREST\b/', $d)) {
            return ['interest', null];
        }
        if ($in > 0 && preg_match('/\b(TRF|TRANSFER)\s+FROM\b|\bOWN\s+A\/?C/', $d)) {
            return ['transfer', null];
        }
        return [$in > 0 ? 'receipt' : 'payment', null];
    }

    /**
     * Normalises CSV rows (with a column map) or typed lines into statement lines. Returns lines with their errors.
     * $opt: map (column indexes), header_row, date_format, amount_sign ('in_positive' | 'out_positive').
     */
    public static function normalise(array $rows, array $opt, array $otherAccountNos = []): array
    {
        $map = $opt['map'] ?? [];
        $hdr = $opt['header_row'] ?? null;
        $fmt = $opt['date_format'] ?? null;
        $lines = [];
        $n = 0;
        foreach ($rows as $i => $r) {
            if ($hdr !== null && $i <= $hdr) {
                continue;
            }
            $cell = static fn ($k) => isset($map[$k]) && $map[$k] !== '' && $map[$k] !== null ? (string) ($r[(int) $map[$k]] ?? '') : '';
            $rawDate = $cell('date');
            $desc = $cell('description');
            if ($rawDate === '' && $desc === '' && $cell('money_in') === '' && $cell('money_out') === '' && $cell('amount') === '') {
                continue;
            }
            if (preg_match('/opening|closing|brought\s*forward|carried\s*forward|total/i', $desc . ' ' . $rawDate) && $cell('money_in') === '' && $cell('money_out') === '' && $cell('amount') === '') {
                continue;
            }
            $n++;
            $errs = [];
            $date = self::parseDate($rawDate, $fmt);
            if (!$date) {
                $errs[] = 'date not readable: "' . mb_substr($rawDate, 0, 20) . '"';
            }
            $vd = $cell('value_date') !== '' ? self::parseDate($cell('value_date'), $fmt) : null;
            $in = 0;
            $out = 0;
            if (isset($map['amount']) && $map['amount'] !== '' && (!isset($map['money_in']) || $map['money_in'] === '') && (!isset($map['money_out']) || $map['money_out'] === '')) {
                $a = self::parseAmount($cell('amount'));
                if ($a === false || $a === null) {
                    $errs[] = 'amount not readable';
                } else {
                    $c = Rec::cents($a) * (($opt['amount_sign'] ?? 'in_positive') === 'out_positive' ? -1 : 1);
                    $c >= 0 ? $in = $c : $out = -$c;
                }
            } else {
                $ai = self::parseAmount($cell('money_in'));
                $ao = self::parseAmount($cell('money_out'));
                if ($ai === false || $ao === false) {
                    $errs[] = 'amount not readable';
                } else {
                    $in = $ai === null ? 0 : abs(Rec::cents($ai));
                    $out = $ao === null ? 0 : abs(Rec::cents($ao));
                }
                if ($in > 0 && $out > 0) {
                    $errs[] = 'both money in and money out on one line';
                }
            }
            if (!$errs && $in === 0 && $out === 0) {
                $errs[] = 'no amount';
            }
            $bal = null;
            if ($cell('balance') !== '') {
                $b = self::parseAmount($cell('balance'));
                if ($b === false) {
                    $errs[] = 'balance not readable';
                } else {
                    $bal = $b;
                }
            }
            [$cat, $ctype] = self::categorise($desc, $in, $out, $otherAccountNos);
            $lines[] = ['line_no' => $n, 'txn_date' => $date, 'value_date' => $vd, 'description' => mb_substr($desc, 0, 400), 'reference' => mb_substr($cell('reference'), 0, 120) ?: null,
                'money_in' => Rec::dec($in), 'money_out' => Rec::dec($out), 'balance' => $bal, 'category' => $cat, 'charge_type' => $ctype, 'errors' => $errs,
                'raw' => array_slice($r, 0, 20)];
        }
        return $lines;
    }

    /** Typed lines (manual entry) in the same shape as normalise(). */
    public static function manualLines(array $in, array $otherAccountNos = []): array
    {
        $lines = [];
        $n = 0;
        foreach ($in as $r) {
            $r = (array) $r;
            if (trim(implode('', array_map('strval', $r))) === '') {
                continue;
            }
            $n++;
            $errs = [];
            $date = self::parseDate((string) ($r['date'] ?? ''));
            if (!$date) {
                $errs[] = 'date not readable';
            }
            $ai = self::parseAmount((string) ($r['money_in'] ?? ''));
            $ao = self::parseAmount((string) ($r['money_out'] ?? ''));
            $inC = ($ai === false || $ai === null) ? 0 : abs(Rec::cents($ai));
            $outC = ($ao === false || $ao === null) ? 0 : abs(Rec::cents($ao));
            if ($ai === false || $ao === false) {
                $errs[] = 'amount not readable';
            }
            if ($inC > 0 && $outC > 0) {
                $errs[] = 'both money in and money out on one line';
            }
            if (!$errs && $inC === 0 && $outC === 0) {
                $errs[] = 'no amount';
            }
            $bal = null;
            if (trim((string) ($r['balance'] ?? '')) !== '') {
                $b = self::parseAmount((string) $r['balance']);
                $b === false ? $errs[] = 'balance not readable' : $bal = $b;
            }
            $desc = mb_substr(trim((string) ($r['description'] ?? '')), 0, 400);
            [$cat, $ctype] = self::categorise($desc, $inC, $outC, $otherAccountNos);
            $lines[] = ['line_no' => $n, 'txn_date' => $date, 'value_date' => null, 'description' => $desc, 'reference' => mb_substr(trim((string) ($r['reference'] ?? '')), 0, 120) ?: null,
                'money_in' => Rec::dec($inC), 'money_out' => Rec::dec($outC), 'balance' => $bal, 'category' => $cat, 'charge_type' => $ctype, 'errors' => $errs, 'raw' => null];
        }
        return $lines;
    }

    public static function fingerprint(int $accountId, array $l): string
    {
        return sha1(implode('|', [$accountId, $l['txn_date'], $l['money_in'], $l['money_out'], strtolower(preg_replace('/\s+/', ' ', trim((string) $l['description'])))]));
    }

    /** Account numbers of the OTHER Avenir accounts (digits/letters only), to recognise transfers between them. */
    public static function otherAccountNos($db, int $accountId): array
    {
        $out = [];
        foreach (BankAccounts::all($db) as $a) {
            if ((int) $a['id'] !== $accountId) {
                $out[] = preg_replace('/[^0-9A-Z]/', '', strtoupper((string) $a['account_no']));
            }
        }
        return array_values(array_filter($out));
    }

    // ---------------------------------------------------------------- checking

    /**
     * Checks a statement before it is kept or accepted. Errors block acceptance; warnings are shown and kept.
     * $h: opening_balance, closing_balance (optional), period_from, period_to (optional), currency; $statementId when re-checking a kept one.
     */
    public static function check($db, int $accountId, array $h, array $lines, ?int $statementId = null): array
    {
        $errors = [];
        $warnings = [];
        $acct = Rec::account($db, $accountId);
        if (!$acct) {
            return ['errors' => ['Unknown bank account.'], 'warnings' => [], 'summary' => []];
        }
        $cur = $acct['currency'];
        if (!in_array($cur, Rec::CURRENCIES, true)) {
            $errors[] = "The account's currency {$cur} is not supported.";
        }
        if (!empty($h['currency']) && strtoupper($h['currency']) !== $cur) {
            $errors[] = "The statement is in {$h['currency']} but the account is a {$cur} account.";
        }
        if (!$lines) {
            $errors[] = 'The statement has no lines.';
        }
        foreach ($lines as $l) {
            foreach ($l['errors'] ?? [] as $e) {
                $errors[] = "Line {$l['line_no']}: {$e}.";
            }
        }
        $dates = array_values(array_filter(array_column($lines, 'txn_date')));
        $from = ($h['period_from'] ?? '') ?: ($dates ? min($dates) : null);
        $to = ($h['period_to'] ?? '') ?: ($dates ? max($dates) : null);
        if (!$from || !$to || !Rec::validDate($from) || !Rec::validDate($to) || $from > $to) {
            $errors[] = 'The statement period (from, to) is missing or not valid.';
        }
        foreach ($lines as $l) {
            if ($l['txn_date'] && $from && $to && ($l['txn_date'] < $from || $l['txn_date'] > $to)) {
                $errors[] = "Line {$l['line_no']}: dated {$l['txn_date']}, outside the period {$from} to {$to}.";
            }
        }
        // opening balance: given, or derived from the first printed balance
        $opening = ($h['opening_balance'] ?? '') === '' || ($h['opening_balance'] ?? null) === null ? null : self::parseAmount((string) $h['opening_balance']);
        $derivedOpening = null;
        if ($lines && $lines[0]['balance'] !== null) {
            $derivedOpening = Rec::dec(Rec::cents($lines[0]['balance']) - (Rec::cents($lines[0]['money_in']) - Rec::cents($lines[0]['money_out'])));
        }
        if ($opening === false) {
            $errors[] = 'The opening balance is not readable.';
            $opening = null;
        }
        if ($opening === null) {
            if ($derivedOpening !== null) {
                $warnings[] = "Opening balance not given; taken from the first line's printed balance: {$derivedOpening}.";
                $opening = $derivedOpening;
            } else {
                $errors[] = 'Give the opening balance shown on the statement.';
            }
        } elseif ($derivedOpening !== null && Rec::cents($derivedOpening) !== Rec::cents($opening)) {
            $errors[] = "The opening balance {$opening} does not agree with the first line (its balance implies {$derivedOpening}).";
        }
        $in = 0;
        $out = 0;
        $breaks = [];
        $run = $opening === null ? null : Rec::cents($opening);
        foreach ($lines as $l) {
            $in += Rec::cents($l['money_in']);
            $out += Rec::cents($l['money_out']);
            if ($run !== null) {
                $run += Rec::cents($l['money_in']) - Rec::cents($l['money_out']);
                // every printed balance must equal the opening balance plus all lines so far (no reset after a break,
                // so a mistyped balance shows once and a missing line shows from that point on)
                if ($l['balance'] !== null && Rec::cents($l['balance']) !== $run) {
                    $breaks[] = ['line_no' => $l['line_no'], 'expected' => Rec::dec($run), 'printed' => $l['balance']];
                }
            }
        }
        foreach (array_slice($breaks, 0, 10) as $b) {
            $errors[] = "Line {$b['line_no']}: printed balance {$b['printed']} but opening + lines give {$b['expected']}.";
        }
        if (count($breaks) > 10) {
            $errors[] = (count($breaks) - 10) . ' more balance break(s).';
        }
        $derivedClosing = $opening === null ? null : Rec::dec(Rec::cents($opening) + $in - $out);
        $closing = ($h['closing_balance'] ?? '') === '' || ($h['closing_balance'] ?? null) === null ? null : self::parseAmount((string) $h['closing_balance']);
        if ($closing === false) {
            $errors[] = 'The closing balance is not readable.';
            $closing = null;
        }
        if ($closing !== null && $derivedClosing !== null && Rec::cents($closing) !== Rec::cents($derivedClosing)) {
            $errors[] = "The closing balance {$closing} does not agree with opening + lines ({$derivedClosing}).";
        }
        // continuity with the account's other statements
        if ($from && $to) {
            $params = [$accountId, $to, $from];
            $sqlX = $statementId ? ' AND id <> ' . (int) $statementId : '';
            $ov = $db->query("SELECT id, period_from, period_to, status FROM brc_statement WHERE bank_account_id = ? AND status = 'accepted' AND period_from <= ? AND period_to >= ?{$sqlX}", $params)->getResultArray();
            foreach ($ov as $o) {
                $errors[] = "The period overlaps accepted statement {$o['id']} ({$o['period_from']} to {$o['period_to']}).";
            }
            $prev = $db->query("SELECT * FROM brc_statement WHERE bank_account_id = ? AND status = 'accepted' AND period_to < ?{$sqlX} ORDER BY period_to DESC LIMIT 1", [$accountId, $from])->getRowArray();
            if ($prev) {
                $gapStart = date('Y-m-d', strtotime($prev['period_to'] . ' +1 day'));
                if ($gapStart < $from) {
                    $warnings[] = "No statement for {$gapStart} to " . date('Y-m-d', strtotime($from . ' -1 day')) . ' (a gap after statement ' . $prev['id'] . ').';
                } elseif ($opening !== null && Rec::cents($opening) !== Rec::cents($prev['closing_balance'])) {
                    $errors[] = "The opening balance {$opening} does not equal the closing balance {$prev['closing_balance']} of the previous statement {$prev['id']}.";
                }
            }
        }
        // duplicate-looking lines (kept, only flagged)
        $seen = [];
        $dups = [];
        foreach ($lines as $l) {
            if (!$l['txn_date']) {
                continue;
            }
            $fp = self::fingerprint($accountId, $l);
            if (isset($seen[$fp])) {
                $dups[] = "lines {$seen[$fp]} and {$l['line_no']}";
            }
            $seen[$fp] = $seen[$fp] ?? $l['line_no'];
        }
        if ($dups) {
            $warnings[] = 'Duplicate-looking lines in this statement (same date, amount and wording; kept, check them): ' . implode('; ', array_slice($dups, 0, 8)) . (count($dups) > 8 ? '…' : '') . '.';
        }
        if ($seen) {
            $fps = array_keys($seen);
            $sqlX = $statementId ? ' AND l.statement_id <> ' . (int) $statementId : '';
            $hit = 0;
            foreach (array_chunk($fps, 300) as $ch) {
                $in_ = implode(',', array_map(static fn ($f) => "'" . $f . "'", $ch));
                $hit += (int) ($db->query("SELECT COUNT(*) AS n FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status <> 'void' WHERE l.fingerprint IN ({$in_}){$sqlX}")->getRowArray()['n'] ?? 0);
            }
            if ($hit) {
                $warnings[] = "{$hit} line(s) look the same as lines already kept in another statement of this account (possible double import).";
            }
        }
        if ($cur !== 'LKR') {
            $missing = [];
            foreach (array_unique($dates) as $d) {
                if (!Rec::rate($db, $cur, $d)) {
                    $missing[] = $d;
                }
            }
            if ($missing) {
                sort($missing);
                $warnings[] = "No {$cur} rate yet for " . count($missing) . ' day(s) (' . implode(', ', array_slice($missing, 0, 6)) . (count($missing) > 6 ? '…' : '') . '); the LKR equivalents of those lines stay blank until the rates are entered.';
            }
        }
        $cats = [];
        foreach ($lines as $l) {
            $cats[$l['category']] = ($cats[$l['category']] ?? 0) + 1;
        }
        return ['errors' => array_values(array_unique($errors)), 'warnings' => $warnings, 'summary' => [
            'currency' => $cur, 'period_from' => $from, 'period_to' => $to, 'opening_balance' => $opening, 'closing_balance' => $closing ?? $derivedClosing,
            'derived_closing' => $derivedClosing, 'total_in' => Rec::dec($in), 'total_out' => Rec::dec($out), 'line_count' => count($lines), 'categories' => $cats,
            'balance_breaks' => count($breaks)]];
    }

    // ---------------------------------------------------------------- keeping

    public static function saveDraft($db, int $accountId, array $h, array $lines, string $source, ?string $fileName, ?string $fileSha, string $user): array
    {
        $chk = self::check($db, $accountId, $h, $lines);
        $blocking = array_values(array_filter($chk['errors'], static fn ($e) => preg_match('/^Line \d+: (date|amount|both|no amount|balance not)|period .*missing|no lines|opening balance (is not readable|shown)|Give the opening|not supported|is in [A-Z]{3} but/i', $e)));
        if ($blocking) {
            return [400, 'The statement cannot be kept yet: ' . implode(' ', array_slice($blocking, 0, 5)), null, $chk];
        }
        $s = $chk['summary'];
        $acct = Rec::account($db, $accountId);
        $db->transStart();
        $db->table('brc_statement')->insert(['bank_account_id' => $accountId, 'currency' => $s['currency'], 'period_from' => $s['period_from'], 'period_to' => $s['period_to'],
            'opening_balance' => $s['opening_balance'], 'closing_balance' => $s['closing_balance'], 'total_in' => $s['total_in'], 'total_out' => $s['total_out'], 'line_count' => $s['line_count'],
            'source' => $source, 'file_name' => $fileName ? mb_substr($fileName, 0, 200) : null, 'file_sha256' => $fileSha, 'checks_json' => json_encode($chk, JSON_UNESCAPED_UNICODE),
            'status' => 'draft', 'note' => mb_substr((string) ($h['note'] ?? ''), 0, 300) ?: null, 'created_by' => $user, 'created_at' => Rec::now()]);
        $sid = (int) $db->insertID();
        foreach ($lines as $l) {
            $amt = Rec::cents($l['money_in']) - Rec::cents($l['money_out']);
            $r = Rec::rate($db, $s['currency'], $l['txn_date']);
            $lkr = $r ? Rec::toLkr($amt, $r['rate']) : null;
            $orig = $l;
            unset($orig['errors']);
            $db->table('brc_line')->insert(['statement_id' => $sid, 'bank_account_id' => $accountId, 'line_no' => $l['line_no'], 'txn_date' => $l['txn_date'], 'value_date' => $l['value_date'],
                'description' => $l['description'], 'reference' => $l['reference'], 'money_in' => $l['money_in'], 'money_out' => $l['money_out'], 'balance' => $l['balance'], 'currency' => $s['currency'],
                'fx_rate' => $r['rate'] ?? null, 'fx_source' => $r['source'] ?? null, 'lkr_amount' => $lkr === null ? null : Rec::dec($lkr), 'category' => $l['category'], 'charge_type' => $l['charge_type'],
                'state' => 'open', 'orig_json' => json_encode($orig, JSON_UNESCAPED_UNICODE), 'edited' => 0, 'fingerprint' => self::fingerprint($accountId, $l), 'created_at' => Rec::now()]);
        }
        $db->transComplete();
        Rec::audit($db, 'brc_statement_kept', 'brc_statement', $sid, null, ['source' => $source, 'file' => $fileName, 'sha256' => $fileSha, 'period' => $s['period_from'] . ' to ' . $s['period_to'],
            'opening' => $s['opening_balance'], 'closing' => $s['closing_balance'], 'lines' => $s['line_count'], 'errors' => count($chk['errors']), 'warnings' => count($chk['warnings'])], null, $acct['label'] ?? null);
        return [200, 'Statement kept as a draft (' . $s['line_count'] . ' lines). Nothing is posted to the ledger. ' . ($chk['errors'] ? 'It has ' . count($chk['errors']) . ' check error(s) to resolve before it can be accepted.' : 'It can now be accepted.'), $sid, $chk];
    }

    public static function linesOf($db, int $statementId): array
    {
        return $db->query('SELECT * FROM brc_line WHERE statement_id = ? ORDER BY line_no', [$statementId])->getResultArray();
    }

    /** Re-check a kept statement from its stored lines. */
    public static function recheck($db, int $statementId): ?array
    {
        $s = $db->query('SELECT * FROM brc_statement WHERE id = ?', [$statementId])->getRowArray();
        if (!$s) {
            return null;
        }
        $lines = array_map(static fn ($l) => ['line_no' => (int) $l['line_no'], 'txn_date' => $l['txn_date'], 'value_date' => $l['value_date'], 'description' => $l['description'], 'reference' => $l['reference'],
            'money_in' => $l['money_in'], 'money_out' => $l['money_out'], 'balance' => $l['balance'], 'category' => $l['category'], 'charge_type' => $l['charge_type'], 'errors' => []], self::linesOf($db, $statementId));
        return self::check($db, (int) $s['bank_account_id'], ['opening_balance' => $s['opening_balance'], 'closing_balance' => $s['closing_balance'], 'period_from' => $s['period_from'], 'period_to' => $s['period_to'], 'currency' => $s['currency']], $lines, $statementId);
    }

    public static function accept($db, int $statementId, string $user): array
    {
        $s = $db->query('SELECT * FROM brc_statement WHERE id = ?', [$statementId])->getRowArray();
        if (!$s || $s['status'] !== 'draft') {
            return [409, 'Only a draft statement can be accepted.'];
        }
        $chk = self::recheck($db, $statementId);
        if ($chk['errors']) {
            $db->query('UPDATE brc_statement SET checks_json = ? WHERE id = ?', [json_encode($chk, JSON_UNESCAPED_UNICODE), $statementId]);
            return [409, 'The statement does not pass its checks: ' . implode(' ', array_slice($chk['errors'], 0, 4)), $chk];
        }
        $db->query("UPDATE brc_statement SET status = 'accepted', accepted_by = ?, accepted_at = ?, checks_json = ?, closing_balance = ? WHERE id = ? AND status = 'draft'", [$user, Rec::now(), json_encode($chk, JSON_UNESCAPED_UNICODE), $chk['summary']['closing_balance'], $statementId]);
        $a = Rec::account($db, (int) $s['bank_account_id']);
        Rec::audit($db, 'brc_statement_accepted', 'brc_statement', $statementId, ['status' => 'draft'], ['status' => 'accepted', 'period' => $s['period_from'] . ' to ' . $s['period_to'], 'closing' => $chk['summary']['closing_balance'], 'warnings' => $chk['warnings']], null, $a['label'] ?? null);
        return [200, 'Statement accepted. Its lines can now be matched. (Nothing was posted to the ledger.)', $chk];
    }

    public static function void($db, int $statementId, string $reason, string $user, bool $isReviewer): array
    {
        $s = $db->query('SELECT * FROM brc_statement WHERE id = ?', [$statementId])->getRowArray();
        if (!$s || $s['status'] === 'void') {
            return [409, 'That statement cannot be voided.'];
        }
        if (mb_strlen(trim($reason)) < 5) {
            return [400, 'Give the reason (at least 5 characters).'];
        }
        if ($s['status'] === 'accepted') {
            if (!$isReviewer) {
                return [403, 'An accepted statement can be voided only by a reviewer.'];
            }
            $in = $db->query("SELECT
                (SELECT COUNT(*) FROM brc_match_item i JOIN brc_match m ON m.id = i.match_id AND m.status = 'active' JOIN brc_line l ON l.id = i.line_id WHERE l.statement_id = ?) AS m,
                (SELECT COUNT(*) FROM brc_mark k JOIN brc_line l ON l.id = k.line_id WHERE k.status = 'active' AND l.statement_id = ?) AS k,
                (SELECT COUNT(*) FROM brc_transfer t JOIN brc_line l ON (l.id = t.from_line_id OR l.id = t.to_line_id) WHERE t.status = 'active' AND l.statement_id = ?) AS t", [$statementId, $statementId, $statementId])->getRowArray();
            if ((int) $in['m'] + (int) $in['k'] + (int) $in['t'] > 0) {
                return [409, 'Undo the matches, classifications and transfers on its lines first (' . (int) $in['m'] . ' matched, ' . (int) $in['k'] . ' classified, ' . (int) $in['t'] . ' transfers).'];
            }
        }
        $db->query("UPDATE brc_statement SET status = 'void', void_by = ?, void_at = ?, void_reason = ? WHERE id = ?", [$user, Rec::now(), mb_substr($reason, 0, 300), $statementId]);
        $a = Rec::account($db, (int) $s['bank_account_id']);
        Rec::audit($db, 'brc_statement_voided', 'brc_statement', $statementId, ['status' => $s['status']], ['status' => 'void'], $reason, $a['label'] ?? null);
        return [200, 'Statement voided (kept for the record).'];
    }

    /** Change a line of a DRAFT statement; the original stays in orig_json and in the audit. */
    public static function editLine($db, int $lineId, array $in, string $reason, string $user): array
    {
        $l = $db->query('SELECT l.*, s.status AS st FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id WHERE l.id = ?', [$lineId])->getRowArray();
        if (!$l) {
            return [404, 'Unknown line.'];
        }
        if ($l['st'] !== 'draft') {
            return [409, 'Lines of an accepted statement cannot be changed; void it (reviewer) and import it again.'];
        }
        if (mb_strlen(trim($reason)) < 5) {
            return [400, 'Give the reason for the change (at least 5 characters).'];
        }
        $set = [];
        if (isset($in['txn_date'])) {
            $d = self::parseDate((string) $in['txn_date']);
            if (!$d) {
                return [400, 'The date is not readable.'];
            }
            $set['txn_date'] = $d;
        }
        foreach (['money_in', 'money_out', 'balance'] as $k) {
            if (array_key_exists($k, $in)) {
                $v = self::parseAmount((string) $in[$k]);
                if ($v === false) {
                    return [400, "The {$k} amount is not readable."];
                }
                $set[$k] = $k === 'balance' ? $v : Rec::dec(abs(Rec::cents($v ?? '0')));
            }
        }
        foreach (['description' => 400, 'reference' => 120] as $k => $len) {
            if (isset($in[$k])) {
                $set[$k] = mb_substr(trim((string) $in[$k]), 0, $len);
            }
        }
        if (!$set) {
            return [400, 'Nothing to change.'];
        }
        $new = array_merge($l, $set);
        if (Rec::cents($new['money_in']) > 0 && Rec::cents($new['money_out']) > 0) {
            return [400, 'A line has money in or money out, not both.'];
        }
        $amt = Rec::cents($new['money_in']) - Rec::cents($new['money_out']);
        $r = Rec::rate($db, $l['currency'], $new['txn_date']);
        $set['fx_rate'] = $r['rate'] ?? null;
        $set['fx_source'] = $r['source'] ?? null;
        $set['lkr_amount'] = $r ? Rec::dec(Rec::toLkr($amt, $r['rate'])) : null;
        $set['edited'] = 1;
        $set['fingerprint'] = self::fingerprint((int) $l['bank_account_id'], $new);
        $db->table('brc_line')->where('id', $lineId)->update($set);
        $before = array_intersect_key($l, $set + ['txn_date' => 1]);
        $db->query('UPDATE brc_statement SET total_in = (SELECT COALESCE(SUM(money_in),0) FROM brc_line WHERE statement_id = ?), total_out = (SELECT COALESCE(SUM(money_out),0) FROM brc_line WHERE statement_id = ?) WHERE id = ?', [(int) $l['statement_id'], (int) $l['statement_id'], (int) $l['statement_id']]);
        Rec::audit($db, 'brc_line_changed', 'brc_line', $lineId, $before + ['as_imported' => json_decode((string) $l['orig_json'], true)], $set, $reason);
        return [200, 'Line changed; the original is kept.'];
    }

    /** Change a line's category (any statement status; amounts never change). */
    public static function recategorise($db, int $lineId, string $category, ?string $chargeType, string $reason, string $user): array
    {
        $l = $db->query('SELECT * FROM brc_line WHERE id = ?', [$lineId])->getRowArray();
        if (!$l) {
            return [404, 'Unknown line.'];
        }
        if (!in_array($category, self::CATEGORIES, true)) {
            return [400, 'Unknown category.'];
        }
        if (mb_strlen(trim($reason)) < 5) {
            return [400, 'Give the reason (at least 5 characters).'];
        }
        if ($category === 'charge' && Rec::cents($l['money_out']) <= 0) {
            return [400, 'A bank charge is money out.'];
        }
        $db->query('UPDATE brc_line SET category = ?, charge_type = ? WHERE id = ?', [$category, $category === 'charge' ? (mb_substr((string) $chargeType, 0, 40) ?: 'Bank charge') : null, $lineId]);
        Rec::audit($db, 'brc_line_recategorised', 'brc_line', $lineId, ['category' => $l['category'], 'charge_type' => $l['charge_type']], ['category' => $category, 'charge_type' => $chargeType], $reason);
        return [200, 'Category changed.'];
    }
}
