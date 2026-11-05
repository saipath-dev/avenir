<?php

namespace App\Libraries\TourFlow;

/**
 * tf-discount-r1 (1 Nov 2026) — a one-off discount against an invoice that has already been issued.
 *
 * Shantha's rules, and where each is enforced:
 *   - the invoice is identified by debtor_invoice_log.id and nothing else. Invoice_Num repeats (1,293
 *     distinct numbers across 2,039 invoices) and account_runing has no primary key, so neither can key it.
 *   - one live adjustment per invoice: the database holds it, through a generated live_flag column under
 *     UNIQUE (invoice_log_id, live_flag). The screen checks too, but the database is what guarantees it.
 *   - Method B (a percentage off the issued total) works on any invoice.
 *   - Method A (points off the markup) needs the cost and markup frozen on the issued TourFlow quotation.
 *     Legacy invoices have no path to one — debtor_invoice_log.tour_id is a consignee tour, and a join to
 *     tf_tour returns nothing — so Method A is refused for them on the server, with the reason said plainly.
 *     Nothing is inferred, assigned or backfilled.
 *   - every figure shown is stored on the adjustment. Nothing here reads a balance view, so the feature is
 *     unaffected by the separate USD debtor-balance defect in the historical rows.
 *   - The ledger (account_runing) is MyISAM: it ignores transactions, has no row locking and no unique
 *     key, and a multi-row INSERT there is not atomic. Posting is therefore gated by a unique key in an
 *     InnoDB claim table and verified against the ledger afterwards. See postPair() and verifyPair().
 *   - POSTING IS BLOCKED. Approval records the decision and the entries that WOULD be written; it writes
 *     nothing to account_runing. Enabling that needs Shantha's separate authorisation.
 */
final class Discounts
{
    /** Ledger posting is switched off until Shantha authorises it. Approval still records and audits. */
    public const POSTING_ENABLED = false;

    public const TABLE = 'tf_invoice_discount';
    public const LOG   = 'tf_invoice_discount_log';

    /** The income account used only when the invoice does not name one. */
    public const INCOME_FALLBACK = '5-7-7-10388';

    private $db;
    private string $user;
    private ?int $userId;
    private ?string $name;

    /** Set only by the disposable-database harness. The application never passes it. */
    private bool $sandbox;

    public function __construct($db, string $user = '', ?int $userId = null, ?string $name = null, bool $sandbox = false)
    {
        $this->db      = $db;
        $this->user    = $user;
        $this->userId  = $userId;
        $this->name    = $name;
        $this->sandbox = $sandbox;
    }

    /**
     * Two independent gates. In the application, posting happens only if POSTING_ENABLED is true, and it is
     * false. In the harness, posting happens only if the connected database is a sandbox — so even a harness
     * pointed at the wrong database writes nothing.
     */
    private function postable(): bool
    {
        if ($this->sandbox) {
            $d = (string) ($this->db->query('SELECT DATABASE() d')->getRowArray()['d'] ?? '');
            return str_contains($d, '_sandbox_');
        }
        return self::POSTING_ENABLED;
    }

    /**
     * The posting gate.
     *
     * account_runing is MyISAM. It takes no part in a transaction, it has no row locking, and it has no
     * unique key — so NOTHING in that table can stop a second posting or undo a partial one. A multi-row
     * INSERT is not atomic there either: if the second row is refused, the first stays. The narration
     * marker is a label, not a constraint, and is never relied on alone.
     *
     * The gate is therefore held in InnoDB, in tf_ledger_post_claim, by a unique key on
     * (discount_id, op). Whoever inserts that row owns the posting. A simultaneous second attempt gets a
     * duplicate-key error from the database and writes nothing. The claim is committed BEFORE the ledger
     * is touched, and carries the ledger ID it allocated and the two legs as they must appear, so an
     * interrupted posting leaves a record naming exactly which rows to check — see verifyPair() and
     * repairPosting().
     */
    private const OPS = ['discount' => '', 'reversal' => ' REV'];

    /** A seam the harness uses to interrupt after the ledger write, before the claim is settled. */
    public static $afterLedgerWrite = null;

    /** A seam the harness uses to make one leg fail. Never set by the application. */
    public static $mutateRows = null;

    /** A seam the harness uses to interleave a second attempt before this one claims. */
    public static $afterLedgerId = null;

    private function tagFor(int $discountId, string $op): string
    {
        return 'DISC #' . $discountId . self::OPS[$op];
    }

    private function claim(int $discountId, string $op): ?array
    {
        return $this->db->query(
            'SELECT * FROM tf_ledger_post_claim WHERE discount_id = ? AND op = ?',
            [$discountId, $op]
        )->getRowArray() ?: null;
    }

    /**
     * The expected pair, reduced to the fields that must match exactly. Verification compares these,
     * never the narration alone.
     */
    private function expectedLegs(array $rows, string $tag): array
    {
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'L_Code'      => (string) $r['L_Code'],
                'job'         => (string) ($r['job'] ?? ''),
                'Dr'          => self::money($r['Dr'] ?? 0),
                'Cr'          => self::money($r['Cr'] ?? 0),
                'dollar'      => round((float) ($r['dollar'] ?? 0), 3),
                'dollar_cr'   => round((float) ($r['dollar_cr'] ?? 0), 3),
                'Invoice_Num' => (string) $r['Invoice_Num'],
                'status'      => (string) $r['status'],
                'tag'         => $tag,
            ];
        }
        return $out;
    }

    /**
     * Does the ledger hold exactly this pair? Checks the count, the two accounts, the LKR amounts, the
     * USD amounts, the invoice, the status, the operation type and the ledger ID — and that no row
     * carrying this operation's marker sits under any other ledger ID.
     *
     * Returns ['ok', 'found', 'matched', 'missing', 'problems']. It never writes.
     */
    public function verifyPair(int $ledgerId, array $expected, string $tag): array
    {
        $problems = [];
        $actual   = $this->db->query(
            'SELECT L_Code, job, Dr, Cr, dollar, dollar_cr, Invoice_Num, status, Discription FROM account_runing WHERE ID = ?',
            [$ledgerId]
        )->getResultArray();

        $elsewhere = $this->db->query(
            'SELECT DISTINCT ID FROM account_runing WHERE Discription LIKE ? AND ID <> ?',
            ['%[' . $tag . ']%', $ledgerId]
        )->getResultArray();
        if ($elsewhere) {
            $problems[] = 'this operation also appears under ledger ID ' . implode(', ', array_column($elsewhere, 'ID'));
        }

        $unmatched = $actual;
        $missing   = [];
        $matched   = 0;
        foreach ($expected as $i => $e) {
            // expected legs may have come back through JSON, where 51700.00 decodes as an int
            $e = [
                'L_Code'      => (string) $e['L_Code'],
                'job'         => (string) ($e['job'] ?? ''),
                'Dr'          => self::money($e['Dr'] ?? 0),
                'Cr'          => self::money($e['Cr'] ?? 0),
                'dollar'      => round((float) ($e['dollar'] ?? 0), 3),
                'dollar_cr'   => round((float) ($e['dollar_cr'] ?? 0), 3),
                'Invoice_Num' => (string) $e['Invoice_Num'],
                'status'      => (string) $e['status'],
                'tag'         => (string) $e['tag'],
            ];
            $hit = null;
            foreach ($unmatched as $k => $a) {
                if ((string) $a['L_Code'] === $e['L_Code']
                    && (string) ($a['job'] ?? '') === $e['job']
                    && self::money($a['Dr']) === $e['Dr']
                    && self::money($a['Cr']) === $e['Cr']
                    && round((float) $a['dollar'], 3) === $e['dollar']
                    && round((float) $a['dollar_cr'], 3) === $e['dollar_cr']
                    && (string) $a['Invoice_Num'] === $e['Invoice_Num']
                    && (string) $a['status'] === $e['status']
                    && str_contains((string) $a['Discription'], '[' . $e['tag'] . ']')) {
                    $hit = $k;
                    break;
                }
            }
            if ($hit === null) {
                $missing[] = $i;
            } else {
                unset($unmatched[$hit]);
                $matched++;
            }
        }
        if ($unmatched) {
            $problems[] = count($unmatched) . ' row(s) under ledger ID ' . $ledgerId . ' do not match this operation';
        }
        if ($missing) {
            $problems[] = count($missing) . ' of the ' . count($expected) . ' legs are not in the ledger';
        }
        if (count($actual) !== count($expected) && !$missing && !$unmatched) {
            $problems[] = 'the ledger holds ' . count($actual) . ' rows for this posting, not ' . count($expected);
        }

        return [
            'ok'       => $problems === [] && $matched === count($expected) && count($actual) === count($expected),
            'found'    => count($actual),
            'matched'  => $matched,
            'missing'  => $missing,
            'problems' => $problems,
        ];
    }

    /** The ledger's own ID allocator — the same one every existing posting writer uses. */
    private function takeLedgerId(): int
    {
        $res = $this->db->query('UPDATE invoice_no SET `acc_id` = LAST_INSERT_ID(`acc_id` + 1)');
        if ($res === false || (int) $this->db->affectedRows() !== 1) {
            $e = $this->db->error();
            throw new \RuntimeException('ledger ID counter take failed: [' . ($e['code'] ?? '?') . '] ' . ($e['message'] ?? 'no message'));
        }
        $id = (int) ($this->db->query('SELECT LAST_INSERT_ID() AS n')->getRowArray()['n'] ?? 0);
        if ($id <= 0) {
            throw new \RuntimeException('ledger ID counter returned no number.');
        }
        return $id;
    }

    /** Write the given legs into the ledger under one ID. Nothing here assumes the write is atomic. */
    private function writeLegs(int $ledgerId, array $rows, string $tag): void
    {
        $prepared = [];
        foreach ($rows as $r) {
            $r = array_diff_key($r, ['why_usd' => 1, 'tf_expect_index' => 1]);
            $r['Discription'] = trim((string) ($r['Discription'] ?? '')) . ' [' . $tag . ']';
            $prepared[] = array_merge(
                ['ID' => $ledgerId, 'Date' => date('Y-m-d'), 'Dt' => $this->now(), 'Cashier' => $this->user, 'advances_pay' => 0],
                $r
            );
        }
        if (is_callable(self::$mutateRows)) {
            $prepared = (self::$mutateRows)($prepared);
        }
        $this->db->table('account_runing')->insertBatch($prepared);
    }

    /**
     * Claim the posting, write the pair, verify it, settle the claim. A second simultaneous attempt is
     * stopped by the unique key before it writes anything.
     */
    private function postPair(array $rows, int $discountId, string $op): int
    {
        $tag      = $this->tagFor($discountId, $op);
        $expected = $this->expectedLegs($rows, $tag);

        $ledgerId = $this->takeLedgerId();
        if (is_callable(self::$afterLedgerId)) { (self::$afterLedgerId)(); }
        // The claim is the gate. The unique key on (discount_id, op) is what stops a second posting —
        // whether the driver reports the duplicate by returning false or by throwing.
        try {
            $res = $this->db->query(
                'INSERT INTO tf_ledger_post_claim (discount_id, op, ledger_id, expected, state, claimed_by, claimed_at) VALUES (?, ?, ?, ?, \'claimed\', ?, ?)',
                [$discountId, $op, $ledgerId, json_encode($expected), $this->user, $this->now()]
            );
            $err = $res === false ? $this->db->error() : ['code' => 0, 'message' => ''];
        } catch (\Throwable $e) {
            $res = false;
            $err = ['code' => (int) $e->getCode(), 'message' => $e->getMessage()];
        }
        if ($res === false) {
            if ((int) $err['code'] === 1062 || str_contains((string) $err['message'], 'Duplicate entry')) {
                throw new \RuntimeException('Another posting of this adjustment is already under way, or an earlier one was left unfinished. Nothing was written. It must be checked before this adjustment is posted again.');
            }
            throw new \RuntimeException('could not claim the posting: [' . $err['code'] . '] ' . $err['message']);
        }

        $failure = null;
        try {
            $this->writeLegs($ledgerId, $rows, $tag);
        } catch (\Throwable $e) {
            $failure = $e->getMessage();
        }

        if (is_callable(self::$afterLedgerWrite)) { (self::$afterLedgerWrite)(); }

        return $this->settle($discountId, $op, $ledgerId, $expected, $tag, $failure);
    }

    /** Verify what is actually in the ledger and record the verdict on the claim. */
    private function settle(int $discountId, string $op, int $ledgerId, array $expected, string $tag, ?string $failure): int
    {
        $v = $this->verifyPair($ledgerId, $expected, $tag);
        if ($v['ok']) {
            $this->db->query(
                'UPDATE tf_ledger_post_claim SET state = \'complete\', legs_found = ?, problem = NULL, settled_at = ? WHERE discount_id = ? AND op = ?',
                [$v['found'], $this->now(), $discountId, $op]
            );
            return $ledgerId;
        }

        $why = trim(($failure !== null ? 'the ledger write failed: ' . $failure . '. ' : '') . implode('; ', $v['problems']));
        $this->db->query(
            'UPDATE tf_ledger_post_claim SET state = \'incomplete\', legs_found = ?, problem = ?, settled_at = ? WHERE discount_id = ? AND op = ?',
            [$v['found'], mb_substr($why, 0, 1000), $this->now(), $discountId, $op]
        );
        $this->audit($discountId, 'posting incomplete',
            'Ledger ID ' . $ledgerId . ' holds ' . $v['found'] . ' of ' . count($expected) . ' legs. ' . $why);
        throw new \RuntimeException('The accounting entry for this adjustment is incomplete: ledger ID ' . $ledgerId .
            ' holds ' . $v['found'] . ' of ' . count($expected) . ' legs. ' . $why .
            ' Nothing further was posted; the adjustment is marked for repair.');
    }

    /**
     * Finish an interrupted posting. It verifies first, writes only the legs that are genuinely missing,
     * and verifies again. Anything it cannot account for — an extra row, a wrong amount, the same
     * operation under another ledger ID — stops it with a visible exception and no write.
     */
    public function repairPosting(int $discountId, string $op): array
    {
        if (!isset(self::OPS[$op])) {
            throw new \InvalidArgumentException('The operation is either discount or reversal.');
        }
        if (!$this->postable()) {
            throw new \RuntimeException('Ledger posting is switched off, so there is nothing to repair.');
        }

        $this->db->transBegin();
        try {
            $c = $this->db->query(
                'SELECT * FROM tf_ledger_post_claim WHERE discount_id = ? AND op = ? FOR UPDATE',
                [$discountId, $op]
            )->getRowArray();
            if (!$c) {
                throw new \RuntimeException('There is no posting of that operation to repair.');
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        $ledgerId = (int) $c['ledger_id'];
        $expected = json_decode((string) $c['expected'], true) ?: [];
        $tag      = $this->tagFor($discountId, $op);
        $v        = $this->verifyPair($ledgerId, $expected, $tag);

        if ($v['ok']) {
            $this->db->query(
                'UPDATE tf_ledger_post_claim SET state = \'complete\', legs_found = ?, problem = NULL, settled_at = ? WHERE id = ?',
                [$v['found'], $this->now(), $c['id']]
            );
            return ['ledger_id' => $ledgerId, 'written' => 0, 'message' => 'The pair was already complete. Nothing was written.'];
        }

        // only a genuinely missing leg may be written; everything else stops here
        $blocking = array_values(array_filter($v['problems'], static fn ($p) => !str_contains($p, 'legs are not in the ledger')));
        if ($blocking !== [] || $v['missing'] === []) {
            throw new \RuntimeException('This posting cannot be completed automatically: ' .
                implode('; ', $v['problems'] ?: ['nothing is missing and nothing matches']) .
                '. Nothing was written. Accounts must look at ledger ID ' . $ledgerId . '.');
        }

        $full = $op === 'reversal'
            ? $this->proposedReversalEntries(...$this->figuresFor($discountId))
            : $this->proposedEntries(...$this->figuresFor($discountId));
        $write = [];
        foreach ($v['missing'] as $i) {
            if (!isset($full[$i])) {
                throw new \RuntimeException('The missing leg cannot be rebuilt from the adjustment. Nothing was written.');
            }
            $write[] = $full[$i];
        }
        $this->writeLegs($ledgerId, $write, $tag);

        $after = $this->verifyPair($ledgerId, $expected, $tag);
        if (!$after['ok']) {
            $this->audit($discountId, 'repair failed',
                'Ledger ID ' . $ledgerId . ' still holds ' . $after['found'] . ' of ' . count($expected) . ' legs. ' . implode('; ', $after['problems']));
            throw new \RuntimeException('The repair did not complete the pair: ' . implode('; ', $after['problems']) .
                '. Accounts must look at ledger ID ' . $ledgerId . '.');
        }
        $this->db->query(
            'UPDATE tf_ledger_post_claim SET state = \'complete\', legs_found = ?, problem = NULL, settled_at = ? WHERE id = ?',
            [$after['found'], $this->now(), $c['id']]
        );
        $col = $op === 'reversal' ? 'reverse_post_id' : 'post_id';
        $this->db->query('UPDATE ' . self::TABLE . ' SET ' . $col . ' = ? WHERE id = ?', [$ledgerId, $discountId]);
        $this->audit($discountId, 'posting repaired',
            count($write) . ' missing leg(s) written under ledger ID ' . $ledgerId . '; the pair now verifies.');

        return ['ledger_id' => $ledgerId, 'written' => count($write),
                'message' => 'Ledger ID ' . $ledgerId . ' completed: ' . count($write) . ' missing leg(s) written. The pair verifies.'];
    }

    /** The invoice and the two figures a posting is built from. */
    private function figuresFor(int $discountId): array
    {
        $d = $this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE id = ?', [$discountId])->getRowArray();
        if (!$d) {
            throw new \InvalidArgumentException('No such discount.');
        }
        return [
            $this->invoice((int) $d['invoice_log_id']),
            (float) $d['discount_local'],
            $d['discount_usd'] === null ? null : (float) $d['discount_usd'],
        ];
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private static function money($v): float
    {
        return round((float) $v, 2);
    }

    // =================================================================================================
    //  The invoice
    // =================================================================================================

    /** The invoice as this panel sees it, with its ledger status and what may be done to it. */
    public function invoice(int $logId): array
    {
        $inv = $this->db->query('SELECT * FROM debtor_invoice_log WHERE id = ?', [$logId])->getRowArray();
        if (!$inv) {
            throw new \InvalidArgumentException('No invoice with that reference.');
        }

        $customer = trim((string) ($inv['agent'] ?? ''));
        $income   = trim((string) ($inv['payment_lcode'] ?? ''));
        $fallback = $income === '';
        if ($fallback) {
            $income = self::INCOME_FALLBACK;
        }

        return [
            'id'              => (int) $inv['id'],
            'invoice_no'      => (string) $inv['invoice_no'],
            'legacy_tour_id'  => $inv['tour_id'] !== null ? (int) $inv['tour_id'] : null,
            'consignee'       => (string) ($inv['consignee'] ?? ''),
            'date'            => (string) ($inv['date'] ?? ''),
            'customer_lcode'  => $customer,
            'income_lcode'    => $income,
            'income_fallback' => $fallback,
            'issued_local'    => self::money($inv['credit_balance'] ?? 0),
            'issued_usd'      => $inv['dollar_amount'] !== null ? self::money($inv['dollar_amount']) : null,
            'usd_rate'        => $inv['doller_rate'] !== null ? round((float) $inv['doller_rate'], 4) : null,
            'cancelled'       => (int) ($inv['cancel'] ?? 0) === 1,
            'ledger_status'   => $this->ledgerStatus((string) $inv['invoice_no'], $customer),
            'quote'           => $this->quoteFor($inv),
            'live'            => $this->live($logId),
        ];
    }

    /**
     * The status carried by this invoice's own ledger rows. The debtor views group by it, so an adjustment
     * posted under a different status would split the invoice into two balance lines instead of netting.
     * Returns null when the invoice's rows disagree, which the caller must refuse rather than guess.
     */
    public function ledgerStatus(string $invoiceNo, string $customerLcode): ?string
    {
        if ($invoiceNo === '' || $customerLcode === '') {
            return null;
        }
        $rows = $this->db->query(
            'SELECT DISTINCT COALESCE(status, \'\') s FROM account_runing WHERE Invoice_Num = ? AND L_Code = ?',
            [$invoiceNo, $customerLcode]
        )->getResultArray();
        if (count($rows) !== 1) {
            return null;            // none, or more than one — the caller refuses
        }
        return (string) $rows[0]['s'];
    }

    /**
     * The issued TourFlow quotation behind this invoice, or null. Nothing is inferred: the only link that
     * counts is debtor_invoice_log.tour_id matching tf_tour.id exactly. For legacy invoices it never does.
     */
    private function quoteFor(array $inv): ?array
    {
        $tourId = $inv['tour_id'] ?? null;
        if ($tourId === null) {
            return null;
        }
        try {
            $t = $this->db->query('SELECT id FROM tf_tour WHERE id = ?', [(int) $tourId])->getRowArray();
            if (!$t) {
                return null;
            }
            $q = $this->db->query(
                "SELECT id, version, cost_usd, markup_pct FROM tf_quote WHERE tour_id = ? AND status = 'issued' ORDER BY id DESC LIMIT 1",
                [(int) $t['id']]
            )->getRowArray();
            return $q ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The live adjustment on an invoice, if any. */
    public function live(int $logId): ?array
    {
        return $this->db->query(
            'SELECT * FROM ' . self::TABLE . " WHERE invoice_log_id = ? AND state IN ('requested','approved') LIMIT 1",
            [$logId]
        )->getRowArray() ?: null;
    }

    // =================================================================================================
    //  What may be done
    // =================================================================================================

    /** Which methods this invoice allows, and the reason where one is refused. Enforced on the server. */
    public function eligibility(array $inv): array
    {
        $out = [
            'percent' => ['allowed' => true, 'why' => ''],
            'points'  => ['allowed' => false, 'why' => ''],
        ];

        if ($inv['cancelled']) {
            $out['percent'] = ['allowed' => false, 'why' => 'This invoice is cancelled.'];
        }
        if ($inv['issued_local'] <= 0) {
            $out['percent'] = ['allowed' => false, 'why' => 'The invoice has no amount to discount.'];
        }
        if ($inv['ledger_status'] === null) {
            $out['percent'] = ['allowed' => false, 'why' => 'This invoice\'s ledger rows do not agree on one status, so an adjustment cannot be posted to match them. Accounts must look at the invoice first.'];
        }
        if ($inv['customer_lcode'] === '') {
            $out['percent'] = ['allowed' => false, 'why' => 'The invoice does not name a customer account.'];
        }
        if ($inv['live']) {
            $s = $inv['live']['state'];
            $out['percent'] = ['allowed' => false, 'why' => 'This invoice already has a discount ' . ($s === 'approved' ? 'approved' : 'waiting for approval') . '. Cancel or reverse it before recording another.'];
        }

        if ($inv['quote'] === null) {
            $out['points'] = [
                'allowed' => false,
                'why'     => 'Reducing the markup needs the cost and the markup frozen on the issued quotation. This invoice was raised in Accounts against a legacy tour and has no link to a TourFlow quotation, so those figures do not exist for it. Use a percentage off the invoice total instead.',
            ];
        } elseif ($out['percent']['allowed']) {
            $out['points'] = ['allowed' => true, 'why' => ''];
        } else {
            $out['points'] = ['allowed' => false, 'why' => $out['percent']['why']];
        }

        return $out;
    }

    // =================================================================================================
    //  The calculation
    // =================================================================================================

    /**
     * What the discount would be. Both figures are derived by subtraction from the ISSUED amounts, so the
     * three numbers on the customer document always reconcile exactly.
     */
    public function preview(int $logId, string $method, float $input): array
    {
        $inv = $this->invoice($logId);
        $el  = $this->eligibility($inv);
        if (!isset($el[$method])) {
            throw new \InvalidArgumentException('Choose one method: a percentage off the invoice total, or points off the markup.');
        }
        if (!$el[$method]['allowed']) {
            throw new \RuntimeException($el[$method]['why']);
        }

        $issued    = $inv['issued_local'];
        $issuedUsd = $inv['issued_usd'];
        $cost      = null;
        $markup    = null;

        if ($method === 'percent') {
            if ($input <= 0 || $input > 100) {
                throw new \InvalidArgumentException('A discount off the total is a percentage between 0 and 100.');
            }
            $discount = self::money($issued * $input / 100);
            $revised  = self::money($issued - $discount);
        } else {
            $q      = $inv['quote'];
            $cost   = self::money($q['cost_usd'] ?? 0);
            $markup = round((float) ($q['markup_pct'] ?? 0), 2);
            if ($input <= 0 || $input > $markup + 100) {
                throw new \InvalidArgumentException('Points off the markup must be a positive number.');
            }
            $revised  = self::money($cost * (1 + ($markup - $input) / 100));
            $discount = self::money($issued - $revised);
        }

        if ($discount <= 0) {
            throw new \InvalidArgumentException('That leaves the invoice unchanged.');
        }
        if ($discount > $issued) {
            throw new \InvalidArgumentException('A discount cannot be larger than the invoice.');
        }

        // the USD leg comes from the invoice's own USD figure, never from a rate conversion of the local one
        $discountUsd = null;
        $revisedUsd  = null;
        if ($issuedUsd !== null && $issued > 0) {
            $discountUsd = self::money($issuedUsd * ($discount / $issued));
            $revisedUsd  = self::money($issuedUsd - $discountUsd);
        }

        // an external percentage is shown only when it reproduces the amount exactly at two decimals
        $pct      = $issued > 0 ? round($discount / $issued * 100, 2) : null;
        $external = ($pct !== null && self::money($issued * $pct / 100) === $discount) ? $pct : null;

        $profit = $cost !== null ? self::money($revised - $cost) : null;

        return [
            'invoice'            => $inv,
            'method'             => $method,
            'input'              => $input,
            'cost_amount'        => $cost,
            'original_markup'    => $markup,
            'issued_local'       => $issued,
            'issued_usd'         => $issuedUsd,
            'usd_rate'           => $inv['usd_rate'],
            'discount_local'     => $discount,
            'discount_usd'       => $discountUsd,
            'revised_local'      => $revised,
            'revised_usd'        => $revisedUsd,
            'remaining_profit'   => $profit,
            'below_cost'         => $cost !== null && $revised < $cost,
            'external_pct'       => $external,
            'reconciles'         => self::money($revised + $discount) === $issued,
            'usd_reconciles'     => $issuedUsd === null || self::money(($revisedUsd ?? 0) + ($discountUsd ?? 0)) === $issuedUsd,
            'proposed_entries'   => $this->proposedEntries($inv, $discount, $discountUsd),
            'proposed_reversal'  => $this->proposedReversalEntries($inv, $discount, $discountUsd),
            'posting_enabled'    => self::POSTING_ENABLED,
        ];
    }

    /**
     * The two ledger rows a posting WOULD write. Returned for review; nothing is written while posting is off.
     * Dr the invoice's own income account, Cr the customer — the mirror of how the invoice was raised — with
     * the USD figure on dollar_cr, which is the field the debtor view subtracts.
     */
    public function proposedEntries(array $inv, float $discountLocal, ?float $discountUsd): array
    {
        // The USD figure goes on the DEBTOR leg only, and on dollar_cr, because that is the single field any
        // balance actually reads: deptors_qq_dollar computes DBal = SUM(dollar) - SUM(dollar_cr) and filters
        // HAVING HName = 'Trade Debtors'. The income account (Sales Income) is excluded from that view, and
        // account_runing_q2, which the Balance Sheet reads, carries no USD column at all. In the whole ledger
        // only 18 rows have dollar_cr > 0 and every one is a debtor account: no income row has ever carried a
        // credit-side USD figure. Writing one here would be the first of its kind, read by nothing, and could
        // skew a future USD report in a direction nobody has chosen. So the income leg carries no USD, and the
        // USD amount is held on the adjustment record, which is what every document and preview reads.
        $common = [
            'Invoice_Num' => $inv['invoice_no'],
            'index_no'    => $inv['legacy_tour_id'],
            'status'      => $inv['ledger_status'],
            'Discription' => 'Invoice discount ' . $inv['invoice_no'],
        ];
        return [
            array_merge($common, [
                'L_Code' => $inv['income_lcode'], 'Dr' => $discountLocal, 'Cr' => 0.00,
                'job' => $inv['customer_lcode'],
                'dollar' => 0, 'dollar_cr' => 0, 'dollarrate_cr' => null,
                'why_usd' => 'none — income USD is read by no balance, and no income row has ever carried dollar_cr',
            ]),
            array_merge($common, [
                'L_Code' => $inv['customer_lcode'], 'Dr' => 0.00, 'Cr' => $discountLocal,
                'job' => $inv['income_lcode'],
                'dollar' => 0, 'dollar_cr' => $discountUsd, 'dollarrate_cr' => $inv['usd_rate'],
                'why_usd' => 'dollar_cr — the field deptors_qq_dollar subtracts to form DBal for this debtor',
            ]),
        ];
    }

    /**
     * The pair a REVERSAL would write. It is the mirror of the discount, and the USD leg uses `dollar`
     * rather than `dollar_cr` for one reason only: DBal = SUM(dollar) - SUM(dollar_cr), and rows are only
     * ever inserted, never edited. The discount lowered DBal by putting the amount in dollar_cr; the only
     * way to raise it back by inserting a row is to put the same amount in dollar. The two rows together
     * leave DBal exactly where it started.
     */
    public function proposedReversalEntries(array $inv, float $discountLocal, ?float $discountUsd): array
    {
        $common = [
            'Invoice_Num' => $inv['invoice_no'],
            'index_no'    => $inv['legacy_tour_id'],
            'status'      => $inv['ledger_status'],
            'Discription' => 'Invoice discount reversed ' . $inv['invoice_no'],
        ];
        return [
            array_merge($common, [
                'L_Code' => $inv['customer_lcode'], 'Dr' => $discountLocal, 'Cr' => 0.00,
                'job' => $inv['income_lcode'],
                'dollar' => $discountUsd, 'dollar_cr' => 0, 'dollarrate' => $inv['usd_rate'],
                'why_usd' => 'dollar — adds back what the discount took out of DBal, by insert only',
            ]),
            array_merge($common, [
                'L_Code' => $inv['income_lcode'], 'Dr' => 0.00, 'Cr' => $discountLocal,
                'job' => $inv['customer_lcode'],
                'dollar' => 0, 'dollar_cr' => 0, 'dollarrate' => null,
                'why_usd' => 'none — income USD is read by no balance',
            ]),
        ];
    }

    // =================================================================================================
    //  The workflow. Every state change writes an Activity line and a row in the discount's own log.
    // =================================================================================================

    private function audit(int $id, string $action, string $note): void
    {
        $this->db->table(self::LOG)->insert([
            'discount_id' => $id, 'action' => $action, 'note' => mb_substr($note, 0, 1000),
            'by_user' => $this->user, 'by_user_id' => $this->userId, 'by_name' => $this->name ?: $this->user,
            'at' => $this->now(),
        ]);
        try {
            (new Activity($this->db, $this->user, $this->userId, $this->name))
                ->record('invoice_discount', $id, null, null, $action, $note);
        } catch (\Throwable $e) {
            // the discount's own log is the record of last resort; a missing Activity signature never
            // silently loses the trail, and never stops the state change
        }
    }

    /** Record a discount for approval. Nothing is posted. */
    public function request(int $logId, string $method, float $input, string $reason, string $idemKey): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('Say why this discount is being given.');
        }
        if (trim($idemKey) === '') {
            throw new \InvalidArgumentException('Missing submission key.');
        }

        $existing = $this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE idem_key = ?', [$idemKey])->getRowArray();
        if ($existing) {
            return ['duplicate' => true, 'id' => (int) $existing['id'],
                    'message' => 'This was already recorded as discount #' . $existing['id'] . '; it has not been recorded twice.'];
        }

        $p   = $this->preview($logId, $method, $input);
        $inv = $p['invoice'];
        $now = $this->now();

        try {
            $this->db->table(self::TABLE)->insert([
                'invoice_log_id' => $logId,
                'invoice_no' => $inv['invoice_no'], 'tour_id' => null,
                'quote_id' => $inv['quote']['id'] ?? null,
                'customer_lcode' => $inv['customer_lcode'], 'income_lcode' => $inv['income_lcode'],
                'income_fallback' => $inv['income_fallback'] ? 1 : 0,
                'ledger_status' => (string) $inv['ledger_status'],
                'method' => $method, 'input_value' => $input,
                'cost_amount' => $p['cost_amount'], 'original_markup_pct' => $p['original_markup'],
                'issued_local' => $p['issued_local'], 'issued_usd' => $p['issued_usd'], 'usd_rate' => $p['usd_rate'],
                'discount_local' => $p['discount_local'], 'discount_usd' => $p['discount_usd'],
                'revised_local' => $p['revised_local'], 'revised_usd' => $p['revised_usd'],
                'remaining_profit' => $p['remaining_profit'], 'below_cost' => $p['below_cost'] ? 1 : 0,
                'external_pct' => $p['external_pct'],
                'reason' => $reason, 'state' => 'requested', 'idem_key' => $idemKey,
                'requested_by' => $this->user, 'requested_by_id' => $this->userId, 'requested_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'uq_live_per_invoice') !== false || stripos($e->getMessage(), 'Duplicate') !== false) {
                throw new \RuntimeException('This invoice already has a discount waiting or approved. Cancel or reverse it before recording another.');
            }
            throw $e;
        }

        $id = (int) $this->db->insertID();
        $this->audit($id, 'requested', 'Discount recorded on ' . $inv['invoice_no'] . ': ' .
            ($method === 'percent' ? $input . '% off the invoice total' : $input . ' points off the markup') .
            ' — ' . number_format($p['discount_local'], 2) . ' off ' . number_format($p['issued_local'], 2) .
            ', revised ' . number_format($p['revised_local'], 2) . '. ' . $reason);

        return ['duplicate' => false, 'id' => $id, 'message' => 'Discount #' . $id . ' recorded. It changes nothing until it is approved by someone else.'];
    }

    /**
     * Approve. A second person only, and never the requester — the System Administrator included.
     * While POSTING_ENABLED is false this records the decision and writes nothing to the ledger.
     * The decision is committed before any posting; the posting is gated and verified — see postPair().
     */
    public function approve(int $id, string $note = ''): array
    {
        $d = $this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE id = ?', [$id])->getRowArray();
        if (!$d) {
            throw new \InvalidArgumentException('No such discount.');
        }
        if ((int) $d['requested_by_id'] === (int) $this->userId) {
            throw new \RuntimeException('A discount is approved by someone other than the person who recorded it. This one is yours.');
        }
        if ((int) $d['below_cost'] === 1 && trim($note) === '') {
            throw new \InvalidArgumentException('This leaves the invoice below cost. Say why before approving.');
        }

        // The decision is committed first, on its own, because it lives in an InnoDB table and can be
        // made atomic. The ledger is MyISAM and cannot, so posting follows the decision and is idempotent.
        // If posting then fails, the adjustment is left approved with no posting recorded — visible, wrong
        // in no account, and completed by calling completePosting() again.
        $this->db->transBegin();
        try {
            // the loser of two simultaneous approvals changes nothing: the WHERE clause decides, not the read above
            $this->db->query(
                'UPDATE ' . self::TABLE . " SET state = 'approved', approved_by = ?, approved_by_id = ?, approved_at = ?, approve_reason = ?, updated_at = ? WHERE id = ? AND state = 'requested'",
                [$this->user, $this->userId, $this->now(), mb_substr(trim($note), 0, 500) ?: null, $this->now(), $id]
            );
            if ((int) $this->db->affectedRows() !== 1) {
                $now = $this->db->query('SELECT state FROM ' . self::TABLE . ' WHERE id = ?', [$id])->getRowArray();
                throw new \RuntimeException('This discount is no longer waiting for approval (it is ' . ($now['state'] ?? 'gone') . '), possibly decided at this same moment. Nothing was changed.');
            }
            $this->audit($id, 'approved', 'Approved by ' . ($this->name ?: $this->user) . ($note !== '' ? ' — ' . trim($note) : ''));
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        $postId = $this->completePosting($id);

        return [
            'id' => $id, 'posted' => $postId !== null, 'post_id' => $postId, 'posting_enabled' => self::POSTING_ENABLED,
            'message' => self::POSTING_ENABLED
                ? 'Discount #' . $id . ' approved and posted.'
                : 'Discount #' . $id . ' approved and recorded. Ledger posting is switched off, so no accounting entry was made.',
        ];
    }

    /**
     * Post an approved adjustment that has no posting recorded yet.
     *
     * A claim already on file means a posting was attempted. If it verifies, it is adopted. If it does
     * not, nothing is written and the caller is told to repair it — the figures are never posted twice
     * on the strength of a narration marker.
     */
    public function completePosting(int $id): ?int
    {
        return $this->completeOne($id, 'discount');
    }

    /** The same, for a reversal whose mirror pair has not been recorded yet. */
    public function completeReversalPosting(int $id): ?int
    {
        return $this->completeOne($id, 'reversal');
    }

    private function completeOne(int $id, string $op): ?int
    {
        $d = $this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE id = ?', [$id])->getRowArray();
        if (!$d) {
            return null;
        }
        $col = $op === 'reversal' ? 'reverse_post_id' : 'post_id';
        if ($op === 'reversal') {
            if ($d['state'] !== 'reversed' || $d['post_id'] === null) { return null; }
        } elseif ($d['state'] !== 'approved') {
            return null;
        }
        if ($d[$col] !== null) {
            return (int) $d[$col];
        }
        if (!$this->postable()) {
            return null;
        }

        $tag      = $this->tagFor($id, $op);
        [$inv, $local, $usd] = $this->figuresFor($id);
        $rows     = $op === 'reversal'
            ? $this->proposedReversalEntries($inv, $local, $usd)
            : $this->proposedEntries($inv, $local, $usd);

        $c = $this->claim($id, $op);
        if ($c !== null) {
            $expected = json_decode((string) $c['expected'], true) ?: $this->expectedLegs($rows, $tag);
            $v        = $this->verifyPair((int) $c['ledger_id'], $expected, $tag);
            if ($v['ok']) {
                $this->db->query('UPDATE ' . self::TABLE . ' SET ' . $col . ' = ? WHERE id = ?', [(int) $c['ledger_id'], $id]);
                return (int) $c['ledger_id'];
            }
            throw new \RuntimeException('An earlier posting of this adjustment did not complete (ledger ID ' .
                (int) $c['ledger_id'] . ' holds ' . $v['found'] . ' of ' . count($expected) . ' legs: ' .
                implode('; ', $v['problems']) . '). Nothing was written. It must be repaired before it is posted.');
        }

        $postId = $this->postPair($rows, $id, $op);
        $this->db->query('UPDATE ' . self::TABLE . ' SET ' . $col . ' = ? WHERE id = ?', [$postId, $id]);
        $this->audit($id, $op === 'reversal' ? 'reversal posted' : 'posted', 'Posted as ledger ID ' . $postId . '.');
        return $postId;
    }

    /** Cancel, only while still waiting — it has posted nothing, so nothing is unwound. */
    public function cancel(int $id, string $note = ''): array
    {
        $this->db->query(
            'UPDATE ' . self::TABLE . " SET state = 'cancelled', cancelled_by = ?, cancelled_at = ?, cancel_note = ?, updated_at = ? WHERE id = ? AND state = 'requested'",
            [$this->user, $this->now(), mb_substr(trim($note), 0, 500) ?: null, $this->now(), $id]
        );
        if ((int) $this->db->affectedRows() !== 1) {
            throw new \RuntimeException('Only a discount still waiting for approval can be cancelled.');
        }
        $this->audit($id, 'cancelled', 'Cancelled before approval' . ($note !== '' ? ' — ' . trim($note) : ''));
        return ['id' => $id, 'message' => 'Discount #' . $id . ' cancelled. Nothing had been posted.'];
    }

    /** Reverse an approved discount. The original row stays; a reversal is recorded beside it. */
    public function reverse(int $id, string $note = ''): array
    {
        if (trim($note) === '') {
            throw new \InvalidArgumentException('Say why this discount is being reversed.');
        }
        $d = $this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE id = ?', [$id])->getRowArray();
        if (!$d) { throw new \InvalidArgumentException('No such discount.'); }
        $this->db->transBegin();
        try {
            $this->db->query(
                'UPDATE ' . self::TABLE . " SET state = 'reversed', reversed_by = ?, reversed_at = ?, reverse_note = ?, updated_at = ? WHERE id = ? AND state = 'approved'",
                [$this->user, $this->now(), mb_substr(trim($note), 0, 500), $this->now(), $id]
            );
            if ((int) $this->db->affectedRows() !== 1) {
                throw new \RuntimeException('Only an approved discount can be reversed, and only once. Nothing was changed.');
            }
            $this->audit($id, 'reversed', 'Reversed — ' . trim($note));
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        $postId = $this->completeReversalPosting($id);

        return ['id' => $id, 'reverse_post_id' => $postId, 'message' => 'Discount #' . $id . ' reversed.'];
    }

    /** Every discount on an invoice, newest first. */
    public function listFor(int $logId): array
    {
        return $this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE invoice_log_id = ? ORDER BY id DESC', [$logId])->getResultArray();
    }

    /** The log of one discount. */
    public function logFor(int $id): array
    {
        return $this->db->query('SELECT * FROM ' . self::LOG . ' WHERE discount_id = ? ORDER BY id', [$id])->getResultArray();
    }
}
