<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r7 (17 Oct 2026) — bringing an existing voucher onto the Payment Desk ("adoption").
 *
 * A voucher the desk has no record of (no desk request record, no supplier batch) — an older tour voucher raised before the
 * desk, or a non-tour payment such as a utility bill — is checked and recorded here before its bank payment is initiated.
 * Adoption writes ONE row in tpd_adoption and one audit line; it never changes the voucher, its lines or the ledger. The
 * checks refuse a voucher that is already paid, locked, initiated, cancelled, posted without its flag, half-authorised or
 * inconsistent, so that the desk's own steps (initiation by the accountant, Paid by a different bank approver, the chain's
 * authorisation through the guard, bank charges, the Paid lock, the ledger read-back) never produce a second voucher,
 * posting, remittance, initiation or payment.
 *
 * Posting modes (voucher.sup, as the chain posts it at authorisation, VoucherPaymentController::update):
 *   1  "Voucher Payment"   Dr the line account / Cr the voucher's bank account                       → the bank is credited
 *   2  "Supplier Account"  Dr Bank Control / Cr bank, then Dr the line account / Cr Bank Control     → the bank is credited
 *   0  (no type recorded)  Dr the line account / Cr Bank Control only                                → the bank is NOT credited
 * For mode 0 the accountant must confirm at adoption that the desk posts the bank leg at Paid (Dr Bank Control / Cr the
 * paying account, once, for exactly the Bank Control credit of that voucher). Without it the paying account would never
 * show the payment and the Daily Bank Reconciliation could not match the bank's debit.
 */
final class Adoption
{
    public const TABLE = 'tpd_adoption';
    public const BANK_CONTROL = '1-6-8-9750';
    public const LEG_TAG = 'VOUCHER PAYMENT - Bank leg';
    private const BANK_TYPES = ['bank transfer', 'bt'];

    public static function ddl($db): string
    {
        $coll = 'utf8mb4_unicode_ci';
        try {
            $c = (string) ($db->query("SELECT @@collation_database AS c")->getRowArray()['c'] ?? '');
            if (str_starts_with($c, 'utf8mb4_')) {
                $coll = $c;
            }
        } catch (\Throwable $e) {
        }
        return "CREATE TABLE IF NOT EXISTS `tpd_adoption` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `vnum` INT NOT NULL,
            `voucher_id` INT NOT NULL,
            `kind` VARCHAR(10) NOT NULL,
            `tour_id` VARCHAR(150) NULL,
            `manual_id` VARCHAR(50) NULL,
            `payee` VARCHAR(150) NULL,
            `sup_acc` VARCHAR(30) NULL,
            `amount` DECIMAL(14,2) NOT NULL,
            `aflag_at` INT NOT NULL,
            `posting_mode` VARCHAR(20) NOT NULL,
            `ledger_at` TEXT NULL,
            `bank_leg` TINYINT(1) NOT NULL DEFAULT 0,
            `bank_leg_entry` INT NULL,
            `bank_leg_at` DATETIME NULL,
            `checks` TEXT NULL,
            `note` VARCHAR(500) NULL,
            `adopted_by` VARCHAR(50) NOT NULL,
            `adopted_at` DATETIME(3) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_tpd_adoption_vnum` (`vnum`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={$coll}";
    }

    public static function ready($db): bool
    {
        try {
            return (int) ($db->query("SELECT COUNT(*) AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tpd_adoption'")->getRowArray()['n'] ?? 0) === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Creates tpd_adoption when it is missing (idempotent; nothing else is touched). */
    public static function ensure($db, string $by): array
    {
        $had = self::ready($db);
        $db->query(self::ddl($db));
        $now = self::ready($db);
        if (!$had && $now) {
            Audit::log($db, ['action' => 'setup', 'user' => $by, 'ref_type' => 'table', 'ref_id' => 'tpd_adoption', 'reason' => 'tf-paydesk-r7: table tpd_adoption created (voucher adoption onto the Payment Desk)']);
        }
        return ['created' => !$had && $now, 'ready' => $now, 'message' => $had ? 'tpd_adoption already in place; nothing to do.' : ($now ? 'tpd_adoption created.' : 'tpd_adoption could not be created.')];
    }

    public static function get($db, int $vnum): ?array
    {
        if (!self::ready($db)) {
            return null;
        }
        return $db->query("SELECT * FROM tpd_adoption WHERE vnum = ?", [$vnum])->getRowArray() ?: null;
    }

    /**
     * tf-paydesk-r7h build 5.2 (D-3): what the desk request record(s) naming a voucher mean —
     *   none      no desk request record names it;
     *   agrees    the record's request is this voucher's payment (RequestLink: its supplier and amount agree);
     *   held      a record names it but does not agree, and nobody has reviewed that yet (its request is held in Supplier Payables);
     *   reviewed  a record names it but did not agree, and a holder of the batch privilege has reviewed it (IS / is NOT the payment).
     * Only "agrees" puts the voucher on the desk; any other voucher is adopted first (every adoption check), and a "held" one is not
     * adopted before its review.
     */
    public static function deskRecordState($db, int $vnum, ?array $v = null): string
    {
        $xs = $db->query("SELECT pr_id FROM tpd_request_ext WHERE voucher_vnum = ?", [$vnum])->getResultArray();
        if (!$xs) {
            return 'none';
        }
        $v ??= $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        if (!$v) {
            return 'held';
        }
        $src = RequestLink::ofVoucher($db, $v, false)['source'];
        if ($src === 'desk' || $src === 'batch') {
            return 'agrees';
        }
        $rev = RequestLink::reviews($db, array_map(static fn ($x) => (int) $x['pr_id'], $xs));
        foreach ($xs as $x) {
            if (!in_array($rev[(int) $x['pr_id']][$vnum]['decision'] ?? '', ['is', 'not'], true)) {
                return 'held';
            }
        }
        return 'reviewed';
    }

    /** Is the voucher on the desk, and through what: a desk request record (that agrees with it), a supplier batch, or an adoption. */
    public static function onDesk($db, int $vnum): array
    {
        $st = self::deskRecordState($db, $vnum);   // tf-paydesk-r7h build 5.2 (D-3)
        if ($st === 'agrees') {
            return ['desk' => true, 'via' => 'request'];
        }
        if (Batches::byVnum($db, $vnum)) {
            return ['desk' => true, 'via' => 'batch'];
        }
        if (self::get($db, $vnum)) {
            return ['desk' => true, 'via' => 'adoption'];
        }
        return ['desk' => false, 'via' => null, 'adoption_ready' => self::ready($db), 'desk_record' => $st];
    }

    public static function mode(array $v): string
    {
        $s = (string) $v['sup'];
        return $s === '1' ? 'direct' : ($s === '2' ? 'bank_control_2step' : ($s === '0' || $s === '' ? 'bank_control_only' : 'unknown'));
    }

    /** Tour or non-tour: a line (or the voucher's reference) that is a tour number of the consignee table. */
    public static function kind($db, array $v): array
    {
        $tours = [];
        try {
            $rows = $db->query("SELECT DISTINCT c.tour_id, c.manual_id FROM v_inv vi JOIN consignee c ON c.tour_id = vi.Invoice WHERE vi.V_tkn = ?", [(int) $v['Vnum']])->getResultArray();
            foreach ($rows as $r) {
                $tours[(string) $r['tour_id']] = (string) $r['manual_id'];
            }
            if (!$tours && trim((string) $v['ref_po']) !== '') {
                $c = $db->query("SELECT tour_id, manual_id FROM consignee WHERE tour_id = ? LIMIT 1", [(string) $v['ref_po']])->getRowArray();
                if ($c) {
                    $tours[(string) $c['tour_id']] = (string) $c['manual_id'];
                }
            }
        } catch (\Throwable $e) {
        }
        $first = array_key_first($tours);
        return ['kind' => $tours ? 'tour' : 'non_tour', 'tour_id' => $first !== null ? (string) $first : null, 'manual_id' => $first !== null ? $tours[$first] : null, 'tours' => $tours];
    }

    /** kind() for many vouchers in two queries (the Payment Desk lists): vnum => kind info. Each voucher row needs Vnum and ref_po. */
    public static function kinds($db, array $vouchers): array
    {
        $out = [];
        $vnums = array_values(array_unique(array_map(static fn ($v) => (int) $v['Vnum'], $vouchers)));
        if (!$vnums) {
            return $out;
        }
        $tours = [];
        try {
            foreach (array_chunk($vnums, 500) as $chunk) {
                foreach ($db->query("SELECT DISTINCT vi.V_tkn, c.tour_id, c.manual_id FROM v_inv vi JOIN consignee c ON c.tour_id = vi.Invoice WHERE vi.V_tkn IN (" . implode(',', $chunk) . ")")->getResultArray() as $r) {
                    $tours[(int) $r['V_tkn']][(string) $r['tour_id']] = (string) $r['manual_id'];
                }
            }
            $refs = [];
            foreach ($vouchers as $v) {
                if (empty($tours[(int) $v['Vnum']]) && trim((string) $v['ref_po']) !== '') {
                    $refs[(string) $v['ref_po']] = true;
                }
            }
            $byRef = [];
            if ($refs) {
                foreach (array_chunk(array_keys($refs), 500) as $chunk) {
                    foreach ($db->query("SELECT tour_id, manual_id FROM consignee WHERE tour_id IN (" . implode(',', array_map(static fn ($x) => $db->escape($x), $chunk)) . ")")->getResultArray() as $c) {
                        $byRef[(string) $c['tour_id']] = (string) $c['manual_id'];
                    }
                }
            }
            foreach ($vouchers as $v) {
                $n = (int) $v['Vnum'];
                if (empty($tours[$n]) && isset($byRef[(string) $v['ref_po']])) {
                    $tours[$n] = [(string) $v['ref_po'] => $byRef[(string) $v['ref_po']]];
                }
            }
        } catch (\Throwable $e) {
        }
        foreach ($vnums as $n) {
            $t = $tours[$n] ?? [];
            $first = array_key_first($t);
            $out[$n] = ['kind' => $t ? 'tour' : 'non_tour', 'tour_id' => $first !== null ? (string) $first : null, 'manual_id' => $first !== null ? $t[$first] : null, 'tours' => $t];
        }
        return $out;
    }

    /** The chain's authorisation rows of the voucher (both ledger tables), by journal, with the credits to the bank and to Bank Control. */
    public static function ledger($db, array $v): array
    {
        $vnum = (int) $v['Vnum'];
        $rows = [];
        foreach (['account_runing', 'account_runing_2024'] as $t) {
            foreach ($db->query("SELECT ID, L_Code, Dr, Cr, Discription FROM `{$t}` WHERE Recpt_No = ? AND UPPER(Discription) LIKE 'VOUCHER PAYMENT%'", [$vnum])->getResultArray() as $r) {
                $r['tbl'] = $t;
                $rows[] = $r;
            }
        }
        $byJ = [];
        $bankCr = 0.0;
        $ctlCr = 0.0;
        $ctlDr = 0.0;
        $legRows = 0;
        foreach ($rows as $r) {
            $byJ[(int) $r['ID']] = ($byJ[(int) $r['ID']] ?? 0) + (float) $r['Dr'] - (float) $r['Cr'];
            if ((string) $r['L_Code'] === (string) $v['Bank_acc'] && (string) $v['Bank_acc'] !== '') {
                $bankCr += (float) $r['Cr'] - (float) $r['Dr'];
            }
            if ((string) $r['L_Code'] === self::BANK_CONTROL) {
                $ctlCr += (float) $r['Cr'];
                $ctlDr += (float) $r['Dr'];
            }
            if (stripos((string) $r['Discription'], self::LEG_TAG) === 0) {
                $legRows++;
            }
        }
        return ['rows' => count($rows), 'journals' => array_keys($byJ), 'unbalanced' => array_keys(array_filter($byJ, static fn ($d) => abs($d) > 0.005)),
            'bank_credit' => round($bankCr, 2), 'control_credit' => round($ctlCr, 2), 'control_debit' => round($ctlDr, 2), 'bank_leg_rows' => $legRows];
    }

    /**
     * Every check of an adoption, without writing anything. $opts['bank_leg'] = 1 is the accountant's confirmation for mode 0.
     * Returns ok (all passed), the checks (each: key, ok, what, detail), and what the desk will do with the voucher.
     */
    public static function check($db, int $vnum, array $opts = []): array
    {
        $checks = [];
        $add = static function (string $key, bool $ok, string $what, string $detail = '') use (&$checks) {
            $checks[] = ['key' => $key, 'ok' => $ok, 'what' => $what, 'detail' => $detail];
            return $ok;
        };
        $n = (int) ($db->query("SELECT COUNT(*) AS n FROM voucher WHERE Vnum = ?", [$vnum])->getRowArray()['n'] ?? 0);
        $v = $n === 1 ? $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray() : null;
        if (!$add('voucher', $n === 1, 'The voucher exists, once', $n === 1 ? 'Payment Voucher No. ' . $vnum : ($n === 0 ? 'no voucher with this number' : $n . ' rows carry this number'))) {
            return ['ok' => false, 'vnum' => $vnum, 'checks' => $checks, 'voucher' => null];
        }
        $type = strtolower(trim((string) $v['type']));
        $add('not_petty', strtolower((string) $v['v_typ']) !== 'petty cash' && $type !== 'petty cash', 'Not a petty-cash voucher', 'petty cash is confirmed on its own page');
        $add('bank_transfer', in_array($type, self::BANK_TYPES, true), 'A bank-transfer payment', 'payment type: ' . ($v['type'] ?: 'none') . ($type !== '' && !in_array($type, self::BANK_TYPES, true) ? ' — a cash or cheque voucher is settled by its own process, not through a bank transfer on the desk' : ''));
        $add('live', in_array((int) $v['Aflag'], [0, 1], true) && (int) ($v['rej'] ?? 0) !== 1, 'Not cancelled or rejected', 'status (Aflag) ' . (int) $v['Aflag']);
        $add('not_paid', (int) $v['finalized'] === 0, 'Not marked Paid already', (int) $v['finalized'] === 1 ? 'finalized by ' . ($v['finalized_user'] ?: '?') . ' on ' . ($v['finalized_date'] ?: '?') . ', bank date ' . ($v['bank_date'] ?: '?') : 'finalized = 0');
        $lock = $db->query("SELECT id, approved_by, approved_at FROM tpd_bank_approval WHERE vnum = ?", [$vnum])->getRowArray();
        $rem = $db->query("SELECT id, status FROM tpd_remittance WHERE vnum = ?", [$vnum])->getRowArray();
        $add('no_lock', !$lock && !$rem, 'No Paid lock and no remittance advice', $lock ? 'a Paid lock exists (' . $lock['approved_by'] . ', ' . $lock['approved_at'] . ')' : ($rem ? 'a remittance advice exists (' . $rem['status'] . ')' : 'none'));
        $init = $db->query("SELECT id, initiated_by, initiated_at FROM tpd_bank_initiation WHERE vnum = ?", [$vnum])->getRowArray();
        $add('no_initiation', !$init, 'No bank initiation recorded', $init ? 'initiated by ' . $init['initiated_by'] . ' at ' . $init['initiated_at'] : 'none');
        // tf-paydesk-r7h build 5.2 (D-3): only a desk record that agrees with the voucher puts it on the desk; one that does not agree is
        // reviewed first (its request is held), then the voucher is adopted with every check
        $deskRec = self::deskRecordState($db, $vnum, $v);
        $req = $deskRec === 'agrees';
        $bat = Batches::byVnum($db, $vnum);
        $add('not_on_desk', !$req && !$bat, 'Not on the desk already', $req ? 'it has a desk request record — it is completed on the Tour Payment Dashboard' : ($bat ? 'it belongs to batch ' . $bat['batch_no']
            : ($deskRec === 'none' ? 'no desk request record, no batch' : 'no batch; a desk request record names it but does not agree with it (' . ($deskRec === 'held' ? 'not reviewed yet' : 'reviewed') . ')')));
        $add('link_reviewed', $deskRec !== 'held', 'No unreviewed desk record names it', $deskRec === 'held'
            ? 'a desk request record names this voucher but does not agree with it; that request is held — a holder of the batch privilege reviews it first (Supplier Payables: IS / is NOT the payment)' : 'none');
        $prior = self::get($db, $vnum);
        $add('not_adopted', !$prior, 'Not adopted before', $prior ? 'adopted by ' . $prior['adopted_by'] . ' at ' . $prior['adopted_at'] : (self::ready($db) ? 'none' : 'the adoption table is not set up yet'));
        $lines = $db->query("SELECT COUNT(*) AS n, COALESCE(SUM(Amount), 0) AS s FROM v_inv WHERE V_tkn = ?", [$vnum])->getRowArray();
        $amt = round((float) $v['Amount'], 2);
        $add('lines', (int) $lines['n'] > 0 && $amt > 0 && abs(round((float) $lines['s'], 2) - $amt) < 0.01, 'Voucher lines add up to the voucher amount', (int) $lines['n'] . ' line(s), LKR ' . number_format((float) $lines['s'], 2) . ' against the voucher LKR ' . number_format($amt, 2));
        // tf-paydesk-r7g: the currency must be established from the voucher's source records (Currency), never from doller_amt alone
        $cur = Currency::ofVoucher($db, $v);
        $add('currency', $cur['established'], 'Currency established from its source records', $cur['established'] ? Currency::label($cur) . ' — ' . implode('; ', $cur['basis'])
            : $cur['reason'] . '. It cannot be adopted until its currency is established; nothing is assumed');
        $mode = self::mode($v);
        $led = self::ledger($db, $v);
        if ((int) $v['Aflag'] === 0) {
            $add('ledger', $led['rows'] === 0, 'Not posted yet (status unauthorised and no authorisation rows)', $led['rows'] ? $led['rows'] . ' authorisation row(s) already exist (journals ' . implode(',', $led['journals']) . ') although the voucher is not authorised — refer to Accounts' : 'no authorisation rows');
        } else {
            $expectedBank = $mode === 'bank_control_only' ? 0.0 : $amt;
            $ok = $led['rows'] > 0 && !$led['unbalanced'] && $led['bank_leg_rows'] === 0 && abs($led['bank_credit'] - $expectedBank) < 0.01 && ($mode !== 'bank_control_only' || abs($led['control_credit'] - $led['control_debit'] - $amt) < 0.01);
            $add('ledger', $ok, 'Authorised once, its posting complete and balanced', 'journals ' . (implode(',', $led['journals']) ?: 'none') . '; credited to the bank LKR ' . number_format($led['bank_credit'], 2) . ', to Bank Control LKR ' . number_format($led['control_credit'] - $led['control_debit'], 2) . ($led['unbalanced'] ? '; unbalanced journal(s) ' . implode(',', $led['unbalanced']) : ''));
        }
        $claim = null;
        try {
            $claim = $db->query("SELECT state, claimed_at FROM pg_auth_claim WHERE vnum = ?", [$vnum])->getRowArray();
        } catch (\Throwable $e) {
        }
        $add('no_claim', !$claim || $claim['state'] !== 'posting', 'No authorisation in progress or interrupted', $claim ? 'claim state ' . $claim['state'] . ' since ' . $claim['claimed_at'] : 'none');
        if ((int) $v['Aflag'] === 1) {
            $add('bank_account', (bool) Batches::ledgerAccount($db, (string) $v['Bank_acc']), 'Posted from an active bank ledger account', 'voucher bank account ' . ($v['Bank_acc'] ?: 'none') . ' — the payment must be initiated from this account');
        }
        $leg = (int) ($opts['bank_leg'] ?? 0) === 1;
        if ($mode === 'unknown') {
            $add('posting_mode', false, 'A known posting mode', 'voucher type code (sup) ' . $v['sup'] . ' is not one the chain knows');
        } elseif ($mode === 'bank_control_only') {
            $add('posting_mode', $leg, 'Bank leg confirmed for a Bank-Control voucher', 'the chain credits Bank Control (' . self::BANK_CONTROL . ') only; ' . ($leg ? 'the desk will post Dr Bank Control / Cr the paying account once at Paid' : 'confirm that the desk posts the bank leg at Paid (Dr Bank Control / Cr the paying account) — without it the bank account would never show this payment'));
        } else {
            $add('posting_mode', true, 'The chain credits the bank itself', $mode === 'direct' ? 'Voucher Payment: Dr line account / Cr bank' : 'Supplier Account: through Bank Control to the bank');
        }
        $kind = self::kind($db, $v);
        $ok = !in_array(false, array_column($checks, 'ok'), true);
        return ['ok' => $ok, 'vnum' => $vnum, 'checks' => $checks, 'kind' => $kind, 'mode' => $mode, 'currency' => $cur, 'needs_bank_leg' => $mode === 'bank_control_only', 'ledger' => $led,
            'voucher' => ['vnum' => $vnum, 'id' => (int) $v['ID'], 'payee' => $v['S_name'], 'sup_acc' => $v['Acc_No'], 'amount' => $amt, 'aflag' => (int) $v['Aflag'], 'type' => $v['type'], 'bank_acc' => $v['Bank_acc'], 'bank' => $v['Bank'], 'ref' => $v['ref_po'], 'created' => $v['C_Date'], 'created_by' => $v['login'], 'description' => $v['des']]];
    }

    /** Adopts the voucher: one lock per voucher, every check again under it, one row, one audit line. Nothing else is written. */
    public static function adopt($db, int $vnum, string $by, array $opts = []): array
    {
        if (!self::ready($db)) {
            return ['status' => 409, 'message' => 'The Payment Desk adoption table (tpd_adoption) is not set up yet. The administrator runs Payment Desk → Setup once.'];
        }
        $lock = 'tpd_adopt_' . $vnum;
        $got = $db->query("SELECT GET_LOCK(?, 10) AS g", [$lock])->getRowArray();
        if (!$got || (int) $got['g'] !== 1) {
            return ['status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' is being adopted by another request at this moment. Reload the page.'];
        }
        try {
            $c = self::check($db, $vnum, $opts);
            if (!$c['ok']) {
                $failed = array_values(array_filter($c['checks'], static fn ($x) => !$x['ok']));
                Audit::log($db, ['action' => 'adoption_refused', 'user' => $by, 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'new' => ['failed' => $failed],
                    'reason' => 'tf-paydesk-r7: ' . mb_substr(implode(' · ', array_map(static fn ($x) => $x['what'] . ': ' . $x['detail'], $failed)), 0, 480)]);
                return ['status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' cannot be adopted: ' . implode(' · ', array_map(static fn ($x) => $x['what'] . ' — ' . $x['detail'], $failed)) . '. Nothing was written.', 'checks' => $c['checks'], 'needs_bank_leg' => $c['needs_bank_leg'] ?? false];
            }
            $v = $c['voucher'];
            $row = ['vnum' => $vnum, 'voucher_id' => $v['id'], 'kind' => $c['kind']['kind'], 'tour_id' => $c['kind']['tour_id'], 'manual_id' => $c['kind']['manual_id'], 'payee' => mb_substr((string) $v['payee'], 0, 150),
                'sup_acc' => (string) $v['sup_acc'], 'amount' => $v['amount'], 'aflag_at' => $v['aflag'], 'posting_mode' => $c['mode'], 'ledger_at' => json_encode($c['ledger']),
                'bank_leg' => $c['needs_bank_leg'] ? 1 : 0, 'checks' => json_encode(array_map(static fn ($x) => ($x['ok'] ? 'ok' : 'FAIL') . ' · ' . $x['what'] . ' · ' . $x['detail'], $c['checks']), JSON_UNESCAPED_UNICODE),
                'note' => mb_substr(trim((string) ($opts['note'] ?? '')), 0, 500) ?: null, 'adopted_by' => $by, 'adopted_at' => date('Y-m-d H:i:s.v')];
            try {
                $db->table('tpd_adoption')->insert($row);
            } catch (\Throwable $e) {
                return ['status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' is already adopted (' . $e->getMessage() . '). Nothing else was written.'];
            }
            Audit::log($db, ['action' => 'voucher_adopted', 'user' => $by, 'tour_id' => $c['kind']['tour_id'], 'manual_id' => $c['kind']['manual_id'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum,
                'new' => ['kind' => $row['kind'], 'posting_mode' => $row['posting_mode'], 'bank_leg' => $row['bank_leg'], 'amount' => $row['amount'], 'aflag' => $row['aflag_at'], 'journals' => $c['ledger']['journals']],
                'reason' => 'tf-paydesk-r7: adopted onto the Payment Desk' . ($row['note'] ? ' — ' . $row['note'] : '')]);
            return ['status' => 200, 'message' => 'Payment Voucher No. ' . $vnum . ' is on the Payment Desk (' . ($row['kind'] === 'tour' ? 'tour ' . ($row['manual_id'] ?: $row['tour_id']) : 'non-tour payment') . '). Next: the accountant records the bank initiation; then a different person with the bank-approver privilege marks it Paid.' . ($row['bank_leg'] ? ' At Paid the desk also posts the bank leg (Dr Bank Control / Cr the paying account).' : ''),
                'adoption' => $row, 'checks' => $c['checks']];
        } finally {
            try {
                $db->query("SELECT RELEASE_LOCK(?)", [$lock]);
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * At Paid (inside the desk's per-voucher Paid lock, after the chain's authorisation): for an adopted Bank-Control voucher,
     * Dr Bank Control / Cr the paying account for exactly the Bank Control credit of the voucher — once. Anything else: nothing.
     */
    public static function postBankLeg($db, int $vnum, string $by, ?string $date = null): array
    {
        $a = self::get($db, $vnum);
        if (!$a || (int) $a['bank_leg'] !== 1) {
            return ['ok' => true, 'posted' => false, 'message' => 'not a bank-leg voucher'];
        }
        if (!empty($a['bank_leg_entry'])) {
            return ['ok' => true, 'posted' => false, 'entry' => (int) $a['bank_leg_entry'], 'message' => 'bank leg already posted (entry ' . (int) $a['bank_leg_entry'] . ')'];
        }
        $v = $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        if (!$v || (int) $v['Aflag'] !== 1) {
            return ['ok' => false, 'posted' => false, 'message' => 'the voucher is not authorised, so there is no Bank Control credit to clear'];
        }
        $led = self::ledger($db, $v);
        if ($led['bank_leg_rows'] > 0) {
            return ['ok' => false, 'posted' => false, 'message' => 'bank-leg rows already exist in the ledger for this voucher but are not recorded on its adoption — refer to Accounts; nothing more was posted'];
        }
        $amount = round($led['control_credit'] - $led['control_debit'], 2);
        if ($amount <= 0 || abs($amount - round((float) $v['Amount'], 2)) > 0.009 || $led['unbalanced']) {
            return ['ok' => false, 'posted' => false, 'message' => 'the Bank Control credit of this voucher (LKR ' . number_format($amount, 2) . ') does not equal its amount (LKR ' . number_format((float) $v['Amount'], 2) . ') — refer to Accounts; nothing was posted'];
        }
        $bank = Batches::ledgerAccount($db, (string) $v['Bank_acc']);
        if (!$bank) {
            return ['ok' => false, 'posted' => false, 'message' => 'the paying account (' . $v['Bank_acc'] . ') is not an active ledger account'];
        }
        $day = $date ?: date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $accId = (new \App\Models\InvoiceNumberModel())->incrementAccId();
        $desc = self::LEG_TAG . ': Bank Control to paying account, Payment Voucher No. ' . $vnum;
        $base = ['ID' => $accId, 'Invoice_Num' => (string) $v['ref_po'], 'Discription' => $desc, 'Date' => $day, 'status' => '0', 'Cashier' => $by, 'shift_id' => '', 'Chk_No' => (string) $v['C_Number'], 'DV' => 1,
            'Linkacc' => (string) $v['ref_acc_no'], 'aflag' => '3', 'job' => '0', 'index_no' => (string) $v['C_Number'], 'P_Cap' => '', 'P_Month' => $day, 'Tax' => '', 'srvc_no' => '', 'Dt' => $now, 'Fee' => '',
            'Effective_Date' => '', 'Card_No' => '0', 'Recpt_No' => $vnum, 'payee' => (string) $v['S_name'], 'advances_pay' => (int) ($v['advances_pay'] ?? 0)];
        $db->table('account_runing')->insertBatch([
            $base + ['L_Code' => self::BANK_CONTROL, 'Dr' => $amount, 'Cr' => 0],
            $base + ['L_Code' => $bank['code'], 'Dr' => 0, 'Cr' => $amount],
        ]);
        $db->query("UPDATE tpd_adoption SET bank_leg_entry = ?, bank_leg_at = ? WHERE vnum = ? AND bank_leg_entry IS NULL", [$accId, $now, $vnum]);
        Audit::log($db, ['action' => 'bank_leg_posted', 'user' => $by, 'tour_id' => (string) $v['ref_po'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'bank_account' => $bank['code'] . ' ' . $bank['name'],
            'new' => ['payment_voucher_no' => $vnum, 'entry' => $accId, 'amount' => $amount, 'posting' => 'Dr ' . self::BANK_CONTROL . ' Bank Control / Cr ' . $bank['code'] . ' ' . $bank['name'], 'date' => $day]]);
        return ['ok' => true, 'posted' => true, 'entry' => $accId, 'amount' => $amount, 'message' => 'bank leg posted: Dr Bank Control / Cr ' . $bank['code'] . ' LKR ' . number_format($amount, 2) . ' (entry ' . $accId . ')'];
    }
}
