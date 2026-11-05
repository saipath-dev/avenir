<?php

namespace App\Libraries\BankRec;

/**
 * tf-bankrec-r1/r2 — proposed corrections and opening items. A correction is a proposed journal (ledger account, Dr, Cr)
 * for an authorised reviewer; it is NEVER posted by the system. After approval, Accounts posts it by hand in the
 * accounting module if they agree, then records the ledger entry ID here; r2 checks that entry against the approved
 * lines before it is shown as posted. Until then the reconciliation says plainly that the ledger is not corrected.
 *
 * Opening items explain the difference between bank and book on the first day; a reviewer accepts them. r2 adds opening
 * items taken from a ledger row booked before the first day (D10): proposed by a preparer, accepted by a reviewer, then
 * shown as outstanding until a match clears them on the statement.
 */
final class Corrections
{
    public const KINDS = [
        'bank_charges'      => 'Bank charges not in the ledger',
        'unbooked_receipt'  => 'Receipt on the statement, not in the ledger',
        'unbooked_payment'  => 'Payment on the statement, not in the ledger',
        'amount_difference' => 'Amount differs between statement and ledger',
        'duplicate_looking' => 'Duplicate-looking ledger entry',
        'fx_difference'     => 'Exchange-rate difference',
        'other'             => 'Other',
    ];
    public const STATE_LABELS = [
        'proposed'       => 'Proposed — awaiting approval (ledger not corrected)',
        'approved'       => 'Approved — not posted (ledger not corrected)',
        'noted'          => 'Posting noted without verification (r1) — verify it',
        'posted'         => 'Posted — linked to a verified ledger entry',
        'posted_changed' => 'Posted entry changed in the ledger — verify again',
        'rejected'       => 'Rejected',
        'withdrawn'      => 'Withdrawn',
    ];

    private static function ledgerName($db, string $code): ?string
    {
        $r = $db->query('SELECT PName FROM account_posting WHERE L_Code = ? LIMIT 1', [$code])->getRowArray();
        return $r['PName'] ?? null;
    }

    /** Where a correction stands: proposed, approved (not posted), posted and verified, posted but changed since, rejected, withdrawn. */
    public static function state($db, array $c): string
    {
        if ($c['status'] !== 'approved') {
            return $c['status'];
        }
        if (($c['posting_state'] ?? null) === 'verified' && $c['posted_ref']) {
            return self::postedFp($db, $c, (string) $c['posted_ref']) === $c['posted_fp'] ? 'posted' : 'posted_changed';
        }
        return $c['posted_ref'] ? 'noted' : 'approved';
    }

    /** Fingerprint of a ledger entry's rows (all of them), to notice a later change. */
    private static function postedFp($db, array $c, string $ledgerId): ?string
    {
        $rows = $db->query('SELECT L_Code, Date, Dr, Cr, Discription FROM account_runing WHERE ID = ? ORDER BY L_Code, Dr, Cr, Date, Discription', [(int) $ledgerId])->getResultArray();
        return $rows ? sha1(json_encode(array_map(static fn ($r) => [$r['L_Code'], $r['Date'], Rec::dec(Rec::cents($r['Dr'])), Rec::dec(Rec::cents($r['Cr'])), $r['Discription']], $rows))) : null;
    }

    /** Lines of this account already covered by a proposed or approved correction: line id / book key => correction id */
    private static function covered($db, int $accountId): array
    {
        $lines = [];
        $book = [];
        foreach ($db->query("SELECT id, related_json FROM brc_correction WHERE bank_account_id = ? AND status IN ('proposed', 'approved')", [$accountId])->getResultArray() as $c) {
            $rel = json_decode((string) $c['related_json'], true) ?: [];
            foreach ((array) ($rel['line_ids'] ?? []) as $l) {
                $lines[(int) $l] = (int) $c['id'];
            }
            foreach ((array) ($rel['book_keys'] ?? []) as $k) {
                $book[(string) $k] = (int) $c['id'];
            }
        }
        return [$lines, $book];
    }

    public static function propose($db, int $accountId, string $kind, string $description, string $entryDate, array $lines, array $related, string $user): array
    {
        $map = Rec::activeMap($db, $accountId);
        if (!$map) {
            return [409, 'The account has no confirmed ledger mapping.'];
        }
        if (!isset(self::KINDS[$kind])) {
            return [400, 'Unknown kind of correction.'];
        }
        if (mb_strlen(trim($description)) < 5) {
            return [400, 'Describe the correction (at least 5 characters).'];
        }
        if (!Rec::validDate($entryDate)) {
            return [400, 'Give the proposed entry date.'];
        }
        $clean = [];
        $dr = 0;
        $cr = 0;
        foreach ($lines as $l) {
            $l = (array) $l;
            $code = trim((string) ($l['ledger_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $name = self::ledgerName($db, $code);
            if (!$name) {
                return [400, "Ledger account {$code} does not exist in the chart of accounts."];
            }
            $d = Statements::parseAmount((string) ($l['dr'] ?? ''));
            $c = Statements::parseAmount((string) ($l['cr'] ?? ''));
            if ($d === false || $c === false) {
                return [400, 'An amount is not readable.'];
            }
            $dc = $d === null ? 0 : Rec::cents($d);
            $cc = $c === null ? 0 : Rec::cents($c);
            if ($dc < 0 || $cc < 0 || ($dc > 0 && $cc > 0) || ($dc === 0 && $cc === 0)) {
                return [400, 'Each line has either a debit or a credit (positive).'];
            }
            $dr += $dc;
            $cr += $cc;
            $clean[] = ['ledger_code' => $code, 'ledger_name' => $name, 'dr' => Rec::dec($dc), 'cr' => Rec::dec($cc), 'narration' => mb_substr(trim((string) ($l['narration'] ?? '')), 0, 200)];
        }
        if (count($clean) < 2) {
            return [400, 'A proposed entry needs at least two lines.'];
        }
        if ($dr !== $cr) {
            return [400, 'The proposed entry does not balance: debits ' . number_format($dr / 100, 2) . ', credits ' . number_format($cr / 100, 2) . '.'];
        }
        $lineIds = array_values(array_unique(array_map('intval', (array) ($related['line_ids'] ?? []))));
        foreach ($lineIds as $lid) {
            if (!$db->query('SELECT 1 FROM brc_line WHERE id = ? AND bank_account_id = ?', [$lid, $accountId])->getRowArray()) {
                return [400, "Statement line {$lid} is not a line of this account."];
            }
        }
        $rel = ['line_ids' => $lineIds, 'book_keys' => array_values(array_map('strval', (array) ($related['book_keys'] ?? [])))];
        return Rec::locked($db, $accountId, static function () use ($db, $accountId, $kind, $description, $entryDate, $clean, $dr, $rel, $user, $map) {
            [$cl, $cb] = self::covered($db, $accountId);
            foreach ($rel['line_ids'] as $lid) {
                if (isset($cl[$lid])) {
                    return [409, "Statement line {$lid} is already in correction #{$cl[$lid]} (proposed or approved)."];
                }
            }
            foreach ($rel['book_keys'] as $k) {
                if (isset($cb[$k])) {
                    return [409, "Ledger row {$k} is already in correction #{$cb[$k]} (proposed or approved)."];
                }
            }
            if (!Rec::ins($db, 'brc_correction', ['bank_account_id' => $accountId, 'kind' => $kind, 'description' => mb_substr(trim($description), 0, 400), 'entry_date' => $entryDate,
                'entry_json' => json_encode($clean, JSON_UNESCAPED_UNICODE), 'amount' => Rec::dec($dr), 'currency' => 'LKR', 'related_json' => json_encode($rel), 'status' => 'proposed',
                'created_by' => $user, 'created_at' => Rec::now()])) {
                return [409, 'Not saved.'];
            }
            $id = (int) $db->insertID();
            Rec::audit($db, 'brc_correction_proposed', 'brc_correction', $id, null, ['kind' => $kind, 'entry' => $clean, 'amount' => Rec::dec($dr), 'related' => $rel], $description, $map['account']['label']);
            return [200, 'Correction proposed for review. It will not be posted by the system; until Accounts posts it by hand and the entry is verified here, the reconciliation shows the ledger as not corrected.', $id];
        });
    }

    /** A proposal for the chosen bank-charge lines: Dr the bank-charges ledger account (payment workflow mapping), Cr the bank ledger. */
    public static function proposeCharges($db, int $accountId, array $lineIds, string $user, ?string $chargeLedger = null): array
    {
        $map = Rec::activeMap($db, $accountId);
        if (!$map) {
            return [409, 'The account has no confirmed ledger mapping.'];
        }
        $chargeLedger = $chargeLedger ?: (string) ($db->query("SELECT v FROM tpd_meta WHERE k = 'bank_charge_ledger_code'")->getRowArray()['v'] ?? '');
        if ($chargeLedger === '') {
            return [400, 'No bank-charges ledger account is mapped (Tour Payment Dashboard → ledger mapping); choose one.'];
        }
        $act = Rec::activeLinks($db, $accountId, $map['ledger_code']);
        $total = 0;
        $types = [];
        $ids = [];
        $dates = [];
        foreach (array_unique(array_map('intval', $lineIds)) as $lid) {
            $l = $db->query("SELECT l.* FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted' WHERE l.id = ? AND l.bank_account_id = ?", [$lid, $accountId])->getRowArray();
            if (!$l || $l['category'] !== 'charge') {
                return [400, "Line {$lid} is not an accepted bank-charge line of this account."];
            }
            if (isset($act['line_match'][$lid]) || isset($act['line_correction'][$lid])) {
                return [409, "Line {$lid} is already matched or already in a proposed correction."];
            }
            if ($l['lkr_amount'] === null) {
                return [409, "Line {$lid} has no LKR value yet (rate missing)."];
            }
            $total += -Rec::cents($l['lkr_amount']);
            $types[$l['charge_type'] ?: 'Bank charge'] = ($types[$l['charge_type'] ?: 'Bank charge'] ?? 0) + 1;
            $ids[] = $lid;
            $dates[] = $l['txn_date'];
        }
        if (!$ids) {
            return [400, 'Choose the bank-charge lines.'];
        }
        $desc = 'Bank charges on the statement not in the ledger: ' . implode(', ', array_map(static fn ($k, $v) => "{$v} × {$k}", array_keys($types), $types)) . ' (' . min($dates) . ' to ' . max($dates) . ').';
        return self::propose($db, $accountId, 'bank_charges', $desc, max($dates), [
            ['ledger_code' => $chargeLedger, 'dr' => Rec::dec($total), 'cr' => '', 'narration' => 'Bank charges ' . min($dates) . ' to ' . max($dates)],
            ['ledger_code' => $map['ledger_code'], 'dr' => '', 'cr' => Rec::dec($total), 'narration' => 'Bank charges ' . min($dates) . ' to ' . max($dates)],
        ], ['line_ids' => $ids], $user);
    }

    public static function decide($db, int $id, string $decision, string $note, string $user): array
    {
        $c = $db->query('SELECT * FROM brc_correction WHERE id = ?', [$id])->getRowArray();
        if (!$c || $c['status'] !== 'proposed') {
            return [409, 'That correction is not waiting for review.'];
        }
        if ($c['created_by'] === $user) {
            return [403, 'A correction must be reviewed by someone other than the person who proposed it.'];
        }
        if (!in_array($decision, ['approve', 'reject'], true)) {
            return [400, 'Decision must be approve or reject.'];
        }
        if ($decision === 'reject' && mb_strlen(trim($note)) < 5) {
            return [400, 'Give the reason for rejecting (at least 5 characters).'];
        }
        $new = $decision === 'approve' ? 'approved' : 'rejected';
        $db->query("UPDATE brc_correction SET status = ?, reviewed_by = ?, reviewed_at = ?, review_note = ? WHERE id = ? AND status = 'proposed'", [$new, $user, Rec::now(), mb_substr($note, 0, 300) ?: null, $id]);
        if ($db->affectedRows() !== 1) {
            return [409, 'Someone else decided on this correction a moment ago; reload.'];
        }
        $a = Rec::account($db, (int) $c['bank_account_id']);
        Rec::audit($db, 'brc_correction_' . $new, 'brc_correction', $id, ['status' => 'proposed', 'entry' => json_decode($c['entry_json'], true)], ['status' => $new], $note, $a['label'] ?? null);
        return [200, $decision === 'approve' ? 'Approved. The system does not post it: Accounts posts it by hand in the accounting module if they agree, then records the ledger entry here to have it verified. Until then the ledger is not corrected.' : 'Rejected.'];
    }

    public static function withdraw($db, int $id, string $reason, string $user, bool $isAdmin): array
    {
        $c = $db->query('SELECT * FROM brc_correction WHERE id = ?', [$id])->getRowArray();
        if (!$c || $c['status'] !== 'proposed') {
            return [409, 'Only a proposed correction can be withdrawn.'];
        }
        if ($c['created_by'] !== $user && !$isAdmin) {
            return [403, 'Only the person who proposed it can withdraw it.'];
        }
        if (mb_strlen(trim($reason)) < 5) {
            return [400, 'Give the reason (at least 5 characters).'];
        }
        $db->query("UPDATE brc_correction SET status = 'withdrawn', reviewed_by = ?, reviewed_at = ?, review_note = ? WHERE id = ? AND status = 'proposed'", [$user, Rec::now(), mb_substr($reason, 0, 300), $id]);
        if ($db->affectedRows() !== 1) {
            return [409, 'Someone else decided on this correction a moment ago; reload.'];
        }
        Rec::audit($db, 'brc_correction_withdrawn', 'brc_correction', $id, ['status' => 'proposed'], ['status' => 'withdrawn'], $reason);
        return [200, 'Withdrawn.'];
    }

    /**
     * After Accounts has posted an approved correction by hand, record which ledger entry it is. Nothing is posted here.
     * The entry is verified first: every row of that entry must be one of the approved lines (same ledger account, same
     * debit or credit), all approved lines must be there, and the entry may not already be linked to another correction.
     */
    public static function notePosted($db, int $id, string $ledgerId, string $user): array
    {
        $c = $db->query('SELECT * FROM brc_correction WHERE id = ?', [$id])->getRowArray();
        if (!$c || $c['status'] !== 'approved') {
            return [409, 'Only an approved correction can have its posting recorded.'];
        }
        if (($c['posting_state'] ?? null) === 'verified') {
            return [409, 'The posting is already recorded and verified (entry ' . $c['posted_ref'] . ').'];
        }
        $ledgerId = trim($ledgerId);
        if (!preg_match('/^\d{1,11}$/', $ledgerId)) {
            return [400, 'Give the ledger entry ID (a number).'];
        }
        $map = Rec::activeMap($db, (int) $c['bank_account_id']);
        $label = $map['account']['label'] ?? null;
        $rows = $db->query('SELECT ID, L_Code, Date, Dr, Cr, Discription FROM account_runing WHERE ID = ? ORDER BY L_Code, Dr, Cr', [(int) $ledgerId])->getResultArray();
        if (!$rows) {
            Rec::audit($db, 'brc_correction_posting_refused', 'brc_correction', $id, null, ['ledger_entry' => $ledgerId, 'why' => 'no such entry'], null, $label);
            return [404, 'No ledger entry with that ID.'];
        }
        $want = [];
        foreach (json_decode($c['entry_json'], true) ?: [] as $l) {
            $want[] = $l['ledger_code'] . '|' . Rec::dec(Rec::cents($l['dr'])) . '|' . Rec::dec(Rec::cents($l['cr']));
        }
        $have = array_map(static fn ($r) => $r['L_Code'] . '|' . Rec::dec(Rec::cents($r['Dr'])) . '|' . Rec::dec(Rec::cents($r['Cr'])), $rows);
        sort($want);
        sort($have);
        if ($want !== $have) {
            $missing = array_values(array_diff($want, $have));
            $extra = array_values(array_diff($have, $want));
            Rec::audit($db, 'brc_correction_posting_refused', 'brc_correction', $id, ['approved_lines' => $want], ['ledger_entry' => $ledgerId, 'entry_rows' => $have], 'the entry does not match the approved lines', $label);
            return [409, 'Ledger entry ' . $ledgerId . ' does not match the approved correction'
                . ($missing ? '; approved line(s) not found in it: ' . implode(', ', array_map([self::class, 'lineText'], $missing)) : '')
                . ($extra ? '; row(s) in it that are not in the approved correction: ' . implode(', ', array_map([self::class, 'lineText'], $extra)) : '')
                . '. Nothing was recorded.'];
        }
        $other = $db->query("SELECT id FROM brc_correction WHERE posted_ref = ? AND posting_state = 'verified' AND id <> ?", [$ledgerId, $id])->getRowArray();
        if ($other) {
            return [409, 'Ledger entry ' . $ledgerId . ' is already linked to correction #' . $other['id'] . '.'];
        }
        $fp = self::postedFp($db, $c, $ledgerId);
        $date = min(array_column($rows, 'Date'));
        $db->query("UPDATE brc_correction SET posted_ref = ?, posted_noted_by = ?, posted_noted_at = ?, posting_state = 'verified', posted_rows_json = ?, posted_fp = ?, posted_entry_date = ? WHERE id = ? AND status = 'approved' AND (posting_state IS NULL OR posting_state <> 'verified')",
            [$ledgerId, $user, Rec::now(), json_encode($rows, JSON_UNESCAPED_UNICODE), $fp, $date, $id]);
        if ($db->affectedRows() !== 1) {
            return [409, 'Someone else recorded this posting a moment ago; reload.'];
        }
        Rec::audit($db, 'brc_correction_posting_verified', 'brc_correction', $id, ['posted_ref' => $c['posted_ref']], ['ledger_entry' => $ledgerId, 'entry_date' => $date, 'rows' => $have], null, $label);
        return [200, 'Ledger entry ' . $ledgerId . ' (' . $date . ') matches the approved correction and is now linked to it. Match the statement line(s) to the posted ledger row(s) as usual.'];
    }

    /**
     * tf-bankrec-r3: verify again a posting whose ledger entry changed after it was verified ("Posted entry changed in the
     * ledger — verify again"). If the entry, as it is now, still matches the approved lines (for example only its wording
     * or date changed), its current state becomes the verified one; otherwise nothing is recorded and the correction keeps
     * the "verify again" state. The earlier verified state stays in the audit trail.
     */
    public static function reverify($db, int $id, string $user): array
    {
        $c = $db->query('SELECT * FROM brc_correction WHERE id = ?', [$id])->getRowArray();
        if (!$c || $c['status'] !== 'approved' || ($c['posting_state'] ?? null) !== 'verified' || !$c['posted_ref']) {
            return [409, 'Only a correction whose posting was verified can be verified again.'];
        }
        $ledgerId = (string) $c['posted_ref'];
        if (self::state($db, $c) !== 'posted_changed') {
            return [409, 'Ledger entry ' . $ledgerId . ' has not changed since it was verified; there is nothing to verify again.'];
        }
        $map = Rec::activeMap($db, (int) $c['bank_account_id']);
        $label = $map['account']['label'] ?? null;
        $rows = $db->query('SELECT ID, L_Code, Date, Dr, Cr, Discription FROM account_runing WHERE ID = ? ORDER BY L_Code, Dr, Cr', [(int) $ledgerId])->getResultArray();
        if (!$rows) {
            Rec::audit($db, 'brc_correction_reverify_refused', 'brc_correction', $id, null, ['ledger_entry' => $ledgerId, 'why' => 'the entry no longer exists'], null, $label);
            return [409, 'Ledger entry ' . $ledgerId . ' no longer exists in the ledger; the correction is not posted. Nothing was recorded.'];
        }
        $want = [];
        foreach (json_decode($c['entry_json'], true) ?: [] as $l) {
            $want[] = $l['ledger_code'] . '|' . Rec::dec(Rec::cents($l['dr'])) . '|' . Rec::dec(Rec::cents($l['cr']));
        }
        $have = array_map(static fn ($r) => $r['L_Code'] . '|' . Rec::dec(Rec::cents($r['Dr'])) . '|' . Rec::dec(Rec::cents($r['Cr'])), $rows);
        sort($want);
        sort($have);
        if ($want !== $have) {
            $missing = array_values(array_diff($want, $have));
            $extra = array_values(array_diff($have, $want));
            Rec::audit($db, 'brc_correction_reverify_refused', 'brc_correction', $id, ['approved_lines' => $want], ['ledger_entry' => $ledgerId, 'entry_rows' => $have], 'the changed entry no longer matches the approved lines', $label);
            return [409, 'Ledger entry ' . $ledgerId . ' as it is now does not match the approved correction'
                . ($missing ? '; approved line(s) not found in it: ' . implode(', ', array_map([self::class, 'lineText'], $missing)) : '')
                . ($extra ? '; row(s) in it that are not in the approved correction: ' . implode(', ', array_map([self::class, 'lineText'], $extra)) : '')
                . '. Nothing was recorded; it stays "verify again".'];
        }
        $fp = self::postedFp($db, $c, $ledgerId);
        $date = min(array_column($rows, 'Date'));
        $db->query('UPDATE brc_correction SET posted_rows_json = ?, posted_fp = ?, posted_entry_date = ?, posted_noted_by = ?, posted_noted_at = ? WHERE id = ? AND posted_fp <=> ?',
            [json_encode($rows, JSON_UNESCAPED_UNICODE), $fp, $date, $user, Rec::now(), $id, $c['posted_fp']]);
        if ($db->affectedRows() !== 1) {
            return [409, 'Someone else verified this posting again a moment ago; reload.'];
        }
        Rec::audit($db, 'brc_correction_posting_reverified', 'brc_correction', $id,
            ['ledger_entry' => $ledgerId, 'entry_date' => $c['posted_entry_date'], 'rows' => json_decode((string) $c['posted_rows_json'], true)],
            ['ledger_entry' => $ledgerId, 'entry_date' => $date, 'rows' => $rows], 'the entry changed after verification and still matches the approved lines', $label);
        return [200, 'Ledger entry ' . $ledgerId . ' (' . $date . ') was checked again: it still matches the approved correction, and its current state is now the verified one.'];
    }

    public static function lineText(string $x): string
    {
        [$code, $dr, $cr] = explode('|', $x) + ['', '0', '0'];
        return $code . ' ' . (Rec::cents($dr) ? 'Dr ' . number_format(Rec::cents($dr) / 100, 2) : 'Cr ' . number_format(Rec::cents($cr) / 100, 2));
    }

    public static function list($db, ?int $accountId = null, ?string $status = null): array
    {
        $w = [];
        $p = [];
        if ($accountId) {
            $w[] = 'c.bank_account_id = ?';
            $p[] = $accountId;
        }
        if ($status) {
            $w[] = 'c.status = ?';
            $p[] = $status;
        }
        $rows = $db->query('SELECT c.*, a.label AS account_label FROM brc_correction c JOIN tpd_bank_account a ON a.id = c.bank_account_id' . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY c.id DESC LIMIT 500', $p)->getResultArray();
        foreach ($rows as &$r) {
            $r['entry'] = json_decode((string) $r['entry_json'], true) ?: [];
            $r['related'] = json_decode((string) $r['related_json'], true) ?: [];
            $r['kind_label'] = self::KINDS[$r['kind']] ?? $r['kind'];
            $r['state'] = self::state($db, $r);
            $r['state_label'] = self::STATE_LABELS[$r['state']] ?? $r['state'];
            $r['ledger_corrected'] = $r['state'] === 'posted';
            unset($r['entry_json'], $r['related_json'], $r['posted_rows_json']);
        }
        return $rows;
    }

    // ---------------------------------------------------------------- opening items

    public static function proposeOpening($db, int $accountId, string $description, string $amount, string $evidence, string $user): array
    {
        $map = Rec::activeMap($db, $accountId);
        if (!$map) {
            return [409, 'The account has no confirmed ledger mapping.'];
        }
        if (mb_strlen(trim($description)) < 5) {
            return [400, 'Describe the item (at least 5 characters).'];
        }
        $a = Statements::parseAmount($amount);
        if ($a === false || $a === null || Rec::cents($a) === 0) {
            return [400, 'Give the amount (LKR; positive if it makes the bank higher than the book, negative otherwise).'];
        }
        if (!Rec::ins($db, 'brc_opening', ['bank_account_id' => $accountId, 'description' => mb_substr(trim($description), 0, 300), 'amount' => $a, 'evidence' => mb_substr(trim($evidence), 0, 300) ?: null,
            'status' => 'proposed', 'source' => 'manual', 'created_by' => $user, 'created_at' => Rec::now()])) {
            return [409, 'Not saved.'];
        }
        $id = (int) $db->insertID();
        Rec::audit($db, 'brc_opening_item_proposed', 'brc_opening', $id, null, ['amount' => $a, 'description' => $description, 'evidence' => $evidence, 'source' => 'manual'], null, $map['account']['label']);
        return [200, 'Opening item proposed; a reviewer accepts or rejects it.', $id];
    }

    /**
     * D10: a ledger row booked before the first day, outstanding at the start (e.g. a cheque issued but not yet presented),
     * proposed as an opening item. Its amount is its effect on bank − book at the start (the opposite of its ledger amount).
     */
    public static function proposeOpeningLedger($db, int $accountId, string $bookKey, string $evidence, string $user): array
    {
        $map = Rec::activeMap($db, $accountId);
        if (!$map) {
            return [409, 'The account has no confirmed ledger mapping.'];
        }
        if (!preg_match('/^(\d+)\.(\d+)$/', $bookKey, $m)) {
            return [400, 'Give the ledger row (e.g. 28741.1).'];
        }
        $rows = Rec::bookRowsForIds($db, $map['ledger_code'], [(int) $m[1]]);
        $row = $rows[$bookKey] ?? null;
        if (!$row) {
            return [404, "Ledger row {$bookKey} is not on the account's ledger {$map['ledger_code']}."];
        }
        if ($row['date'] >= $map['rec_start']) {
            return [400, "Ledger row {$bookKey} is dated {$row['date']}, on or after the first day {$map['rec_start']}; it is reconciled by matching, not as an opening item."];
        }
        $amt = -Rec::cents($row['amount']);
        if ($amt === 0) {
            return [400, 'That ledger row has no amount.'];
        }
        return Rec::locked($db, $accountId, static function () use ($db, $accountId, $bookKey, $row, $amt, $evidence, $user, $map) {
            $have = $db->query('SELECT id, status FROM brc_opening WHERE active_key = ?', ['O' . $accountId . ':' . $bookKey])->getRowArray();
            if ($have) {
                return [409, "Ledger row {$bookKey} is already opening item #{$have['id']} ({$have['status']})."];
            }
            $desc = 'Outstanding at the start: ' . $row['description'] . ' (' . $row['date'] . ', ledger ' . $bookKey . ($row['chk_no'] && $row['chk_no'] !== '0' ? ', cheque ' . $row['chk_no'] : '') . ($row['pv'] && $row['pv'] !== '0' ? ', PV ' . $row['pv'] : '') . ')';
            if (!Rec::ins($db, 'brc_opening', ['bank_account_id' => $accountId, 'description' => mb_substr($desc, 0, 300), 'amount' => Rec::dec($amt), 'evidence' => mb_substr(trim($evidence), 0, 300) ?: null,
                'status' => 'proposed', 'source' => 'ledger', 'book_key' => $bookKey, 'item_date' => $row['date'], 'book_fp' => $row['fp'], 'active_key' => 'O' . $accountId . ':' . $bookKey,
                'created_by' => $user, 'created_at' => Rec::now()])) {
                return [409, "Ledger row {$bookKey} was proposed as an opening item by someone else a moment ago; reload."];
            }
            $id = (int) $db->insertID();
            Rec::audit($db, 'brc_opening_item_proposed', 'brc_opening', $id, null, ['source' => 'ledger', 'book_key' => $bookKey, 'ledger_amount' => $row['amount'], 'amount' => Rec::dec($amt), 'date' => $row['date'], 'evidence' => $evidence], null, $map['account']['label']);
            return [200, 'Proposed as an opening outstanding item (' . Rec::fmt($amt) . ' LKR of bank − book at the start). A reviewer accepts or rejects it; until then it does not explain the opening difference.', $id];
        });
    }

    /** Ledger rows of the account booked in the days before the first day, with what is known about each (D10 candidates). */
    public static function openingCandidates($db, int $accountId, int $days = 60): array
    {
        $map = Rec::activeMap($db, $accountId);
        if (!$map) {
            return [];
        }
        $days = max(1, min(366, $days));
        $start = $map['rec_start'];
        $rows = Rec::bookRows($db, $map['ledger_code'], date('Y-m-d', strtotime($start . " -{$days} days")), date('Y-m-d', strtotime($start . ' -1 day')));
        $act = Rec::activeLinks($db, $accountId, $map['ledger_code']);
        $open = [];
        foreach ($db->query("SELECT id, book_key, status FROM brc_opening WHERE bank_account_id = ? AND source = 'ledger' AND status IN ('proposed', 'accepted')", [$accountId])->getResultArray() as $o) {
            $open[$o['book_key']] = $o;
        }
        $out = [];
        foreach (array_reverse($rows) as $r) {
            $mid = $act['book_match'][$r['key']] ?? null;
            $cleared = null;
            if ($mid) {
                foreach ($act['matches'] as $m) {
                    if ((int) $m['id'] === $mid) {
                        $cleared = max(array_map(static fn ($i) => $i['item_date'], array_filter($m['items'], static fn ($i) => $i['side'] === 'bank')) ?: ['']);
                    }
                }
            }
            $out[] = $r + ['effect_at_start' => Rec::dec(-Rec::cents($r['amount'])), 'opening_item' => $open[$r['key']] ?? null, 'match_id' => $mid, 'cleared_on' => $cleared];
        }
        return $out;
    }

    public static function decideOpening($db, int $id, string $decision, string $note, string $user): array
    {
        $o = $db->query('SELECT * FROM brc_opening WHERE id = ?', [$id])->getRowArray();
        if (!$o || $o['status'] !== 'proposed') {
            return [409, 'That opening item is not waiting for review.'];
        }
        if (!in_array($decision, ['accept', 'reject', 'withdraw'], true)) {
            return [400, 'Unknown decision.'];
        }
        if ($decision === 'withdraw' && $o['created_by'] !== $user) {
            return [403, 'Only the person who proposed it can withdraw it.'];
        }
        if ($decision !== 'withdraw' && $o['created_by'] === $user) {
            return [403, 'An opening item must be reviewed by someone other than the person who proposed it.'];
        }
        if ($decision !== 'accept' && mb_strlen(trim($note)) < 5) {
            return [400, 'Give the reason (at least 5 characters).'];
        }
        if ($decision === 'accept' && ($o['source'] ?? 'manual') === 'ledger') {
            $map = Rec::activeMap($db, (int) $o['bank_account_id']);
            $rows = $map ? Rec::bookRowsForIds($db, $map['ledger_code'], [(int) explode('.', (string) $o['book_key'])[0]]) : [];
            $row = $rows[$o['book_key']] ?? null;
            if (!$row || ($o['book_fp'] && $row['fp'] !== $o['book_fp'])) {
                return [409, 'Ledger row ' . $o['book_key'] . ' changed or disappeared since it was proposed; it must be proposed again.'];
            }
        }
        $new = ['accept' => 'accepted', 'reject' => 'rejected', 'withdraw' => 'withdrawn'][$decision];
        $db->query("UPDATE brc_opening SET status = ?, reviewed_by = ?, reviewed_at = ?, review_note = ?, active_key = IF(? = 'accepted', active_key, NULL) WHERE id = ? AND status = 'proposed'", [$new, $user, Rec::now(), mb_substr($note, 0, 300) ?: null, $new, $id]);
        if ($db->affectedRows() !== 1) {
            return [409, 'Someone else decided on this opening item a moment ago; reload.'];
        }
        $a = Rec::account($db, (int) $o['bank_account_id']);
        Rec::audit($db, 'brc_opening_item_' . $new, 'brc_opening', $id, ['status' => 'proposed', 'amount' => $o['amount'], 'source' => $o['source'] ?? 'manual', 'book_key' => $o['book_key'] ?? null], ['status' => $new], $note, $a['label'] ?? null);
        return [200, 'Opening item ' . $new . '.'];
    }
}
