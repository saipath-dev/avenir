<?php

namespace App\Libraries\PayGuard;

use App\Libraries\TourPayments\Audit;

/**
 * tf-payguard-r1 — one authorisation posting per voucher, across every route.
 *
 * The payment tables (voucher, v_inv, account_runing, invoice_no) are MyISAM: there is no transaction and no rollback.
 * This guard therefore works with four things that do not need a transaction:
 *   1. a MySQL named lock per voucher number (GET_LOCK), so two requests for the same voucher run one after the other,
 *      whatever route they came through (Voucher Print, Bank Payment Check, the Tour Payment Desk's Paid, any other caller);
 *   2. a re-read of the voucher and of the ledger UNDER that lock: a voucher already authorised, rejected, cancelled, or
 *      already carrying voucher-payment rows in either ledger table is refused with a message, and nothing is posted;
 *   3. a claim row in an InnoDB table (pg_auth_claim, primary key = voucher number): a second claim is refused by the
 *      storage engine itself even if a lock were ever lost;
 *   4. compensation on failure: every ledger row written in the attempt is known by its journal id and is deleted again,
 *      the voucher, its lines and the tour markers are put back to their snapshot, and the claim is removed, so a failed
 *      attempt leaves nothing behind and can be retried once the cause is fixed.
 * Every outcome (claimed, posted, refused, failed-and-undone) is written to tpd_audit.
 */
final class AuthGuard
{
    public const LOCK_PREFIX = 'avr_auth_';
    public const LOCK_WAIT   = 10;   // seconds a second request waits for the first to finish
    public const STALE_MIN   = 10;   // r1.4: minutes a claim may stand at 'posting' before the sweep treats it as abandoned (r1.2's per-voucher recovery needs no age: holding the lock is the proof)
    public const SWEEP_MAX   = 3;    // r1.4: abandoned claims recovered per request, so no single request carries the cost of many
    public const VERSION     = 'tf-payguard-r1.4';   // r1.4 (16 Oct 2026): sweep of abandoned attempts on every claim (test I-5: a voucher nobody retries is no longer left half-posted);   // r1.3 (16 Oct 2026): one-statement counter takes in InvoiceNumberModel (test N-1 showed the read-then-write take handing one number to two concurrent requests) and the last two unchecked writes of V1 checked; r1.1: every write checked — a failed statement returns false and throws nothing when DBDebug is off (the production setting), which let a repeated operation key post twice (test G-11); r1.2: recovery of an interrupted attempt under the lock (recover), the stale-claim wait removed

    private static bool $tablesChecked = false;

    /** Claim the right to post voucher record $voucherId. Returns ['ok'=>bool,'message'=>..., 'claim'=>[...]]. */
    public static function claim(int $voucherId, string $route): array
    {
        $db = \Config\Database::connect();
        self::ensureTables($db);
        $user = (string) (session()->get('username') ?? '');
        self::sweep($db, $route, $user);   // r1.4: abandoned attempts of OTHER vouchers are recovered here, before this voucher is dealt with

        $v = $db->query('SELECT ID, Vnum, Aflag, rej, sup, Amount, ref_po, V_num, advances_pay, C_Number, S_name, Aut_user, aut_date FROM voucher WHERE ID = ?', [$voucherId])->getRowArray();
        if (!$v) {
            self::audit($db, 'auth_refused', null, $voucherId, $route, $user, 'voucher record not found');
            return ['ok' => false, 'message' => 'Voucher record ' . $voucherId . ' was not found. Nothing was posted.'];
        }
        $vnum = (int) $v['Vnum'];
        $lock = self::LOCK_PREFIX . $vnum;

        $got = $db->query('SELECT GET_LOCK(?, ?) AS g', [$lock, self::LOCK_WAIT])->getRowArray();
        if (!$got || (int) $got['g'] !== 1) {
            self::audit($db, 'auth_refused', $vnum, $voucherId, $route, $user, 'another authorisation of this voucher is in progress (lock busy)');
            return ['ok' => false, 'message' => 'Voucher ' . $vnum . ' is being authorised by another request at this moment. Wait for it to finish, then reload the page. Nothing was posted by this request.'];
        }

        // Everything below runs under the lock. Re-read, then check the ledger, then claim.
        try {
            $v = $db->query('SELECT ID, Vnum, Aflag, rej, sup, Amount, ref_po, V_num, advances_pay, C_Number, S_name, Aut_user, aut_date FROM voucher WHERE ID = ?', [$voucherId])->getRowArray();
            if (!$v) {
                throw new GuardRefusal('Voucher record ' . $voucherId . ' was not found. Nothing was posted.', 'voucher record not found');
            }
            // r1.2: an earlier attempt that never finished (process ended, connection lost) left its claim at 'posting'.
            // Holding the voucher's named lock proves that attempt's process is gone (MySQL releases the lock with the
            // connection), so it is recovered now, before anything else is decided: the rows decide what happened.
            $existing = $db->query('SELECT * FROM pg_auth_claim WHERE vnum = ?', [$vnum])->getRowArray();
            if ($existing && $existing['state'] === 'posting') {
                $v = self::recover($db, $existing, $v, $route, $user);
            }
            if ((int) $v['rej'] === 1) {
                throw new GuardRefusal('Voucher ' . $vnum . ' was rejected; it cannot be authorised. Nothing was posted.', 'voucher is rejected (rej=1)');
            }
            if ((int) $v['Aflag'] !== 0) {
                $word = [1 => 'already authorised', 2 => 'rejected', 3 => 'cancelled'][(int) $v['Aflag']] ?? ('at status ' . (int) $v['Aflag']);
                throw new GuardRefusal('Voucher ' . $vnum . ' is ' . $word . '; it cannot be authorised again. No second posting was made.', 'Aflag=' . (int) $v['Aflag']);
            }
            foreach (['account_runing', 'account_runing_2024'] as $t) {
                $n = (int) ($db->query("SELECT COUNT(*) AS n FROM `{$t}` WHERE Recpt_No = ? AND UPPER(Discription) LIKE 'VOUCHER PAYMENT%'", [$vnum])->getRowArray()['n'] ?? 0);
                if ($n > 0) {
                    throw new GuardRefusal('The ledger (' . $t . ') already holds ' . $n . ' voucher-payment row' . ($n === 1 ? '' : 's') . ' for voucher ' . $vnum . '; it cannot be posted again. Refer this voucher to Accounts.', 'ledger already has ' . $n . ' rows in ' . $t);
                }
            }
            // The claim row (re-read: recovery may have changed or removed it). A duplicate key means another request
            // already holds or completed a claim.
            $existing = $db->query('SELECT * FROM pg_auth_claim WHERE vnum = ?', [$vnum])->getRowArray();
            if ($existing) {
                if ($existing['state'] === 'posted') {
                    throw new GuardRefusal('Voucher ' . $vnum . ' was already posted (claim record ' . $existing['claimed_at'] . ' by ' . $existing['user'] . '). No second posting was made.', 'claim already posted');
                }
                throw new GuardRefusal('Voucher ' . $vnum . ' still carries a claim of ' . $existing['claimed_at'] . ' (state ' . $existing['state'] . ') after recovery. Refer this voucher to Accounts. Nothing was posted.', 'claim remains after recovery: ' . $existing['state']);
            }
            $snapshot = self::snapshot($db, $v);
            try {
                $ins = $db->query('INSERT INTO pg_auth_claim (vnum, voucher_id, route, user, claimed_at, state, snapshot) VALUES (?, ?, ?, ?, NOW(3), ?, ?)',
                    [$vnum, $voucherId, mb_substr($route, 0, 60), mb_substr($user, 0, 100), 'posting', json_encode($snapshot)]);
                self::must($db, $ins, 'claim insert');   // r1.1: a duplicate claim is refused even when the driver does not throw
            } catch (\Throwable $e) {
                throw new GuardRefusal('Voucher ' . $vnum . ' could not be claimed for posting (' . self::short($e->getMessage()) . '). Nothing was posted.', 'claim insert failed: ' . self::short($e->getMessage()));
            }
            self::audit($db, 'auth_claimed', $vnum, $voucherId, $route, $user, 'claim taken under lock ' . $lock);
            return ['ok' => true, 'vnum' => $vnum, 'voucher_id' => $voucherId, 'lock' => $lock, 'route' => $route, 'user' => $user, 'snapshot' => $snapshot];
        } catch (GuardRefusal $r) {
            self::audit($db, 'auth_refused', $vnum, $voucherId, $route, $user, $r->reason);
            self::releaseLock($db, $lock);
            return ['ok' => false, 'message' => $r->getMessage()];
        } catch (\Throwable $e) {
            self::audit($db, 'auth_refused', $vnum, $voucherId, $route, $user, 'guard error: ' . self::short($e->getMessage()));
            self::releaseLock($db, $lock);
            return ['ok' => false, 'message' => 'Voucher ' . $vnum . ' could not be checked (' . self::short($e->getMessage()) . '). Nothing was posted.'];
        }
    }

    /** The posting succeeded: record the journal ids, mark the claim posted, release the lock. */
    public static function complete(array $claim, array $journalIds): void
    {
        $db = \Config\Database::connect();
        $ids = implode(',', array_map('intval', $journalIds));
        $note = '';
        try {
            $upd = $db->query('UPDATE pg_auth_claim SET state = ?, journal_ids = ?, finished_at = NOW(3) WHERE vnum = ? AND state = ?', ['posted', $ids, $claim['vnum'], 'posting']);
            self::must($db, $upd, 'claim update');
            if ((int) $db->affectedRows() !== 1) {
                throw new \RuntimeException('claim update changed ' . (int) $db->affectedRows() . ' rows');
            }
        } catch (\Throwable $e) {
            log_message('error', 'payguard complete: ' . $e->getMessage());
            $note = ' | WARNING: claim not marked posted (' . self::short($e->getMessage()) . ')';   // r1.1: visible in the audit row
        }
        self::audit($db, 'auth_posted', $claim['vnum'], $claim['voucher_id'], $claim['route'], $claim['user'], 'journal ids ' . $ids . $note, $ids);
        self::releaseLock($db, $claim['lock']);
    }

    /** The posting failed part-way: undo what this attempt wrote, remove the claim, release the lock. */
    public static function fail(array $claim, array $journalIds, string $reason): array
    {
        $db = \Config\Database::connect();
        $vnum = (int) $claim['vnum'];
        $summary = self::undo($db, $claim, $journalIds);
        self::audit($db, 'auth_failed_undone', $vnum, $claim['voucher_id'], $claim['route'], $claim['user'], 'failure: ' . self::short($reason) . ' | undo: ' . $summary);
        self::releaseLock($db, $claim['lock']);
        $incomplete = strpos($summary, 'UNDO INCOMPLETE') !== false;
        return [
            'message' => 'Posting of voucher ' . $vnum . ' failed (' . self::short($reason) . ') and was ' . ($incomplete ? 'only partly undone: ' . $summary . '. Refer to Accounts before any retry.' : 'undone: ' . $summary . '. Nothing was posted. You may try again.'),
            'incomplete' => $incomplete,
        ];
    }

    /**
     * r1.2: recovery of an attempt whose claim is still 'posting' while this request holds the voucher's lock (so that
     * attempt's process is gone). The rows decide: ledger rows for the voucher (none can exist before a claim — the claim
     * refuses a voucher that already has any), the voucher status and the line statuses.
     *   complete  (ledger rows present, Aflag 1, every line RCType 1): the attempt had finished its posting and only the
     *             completion mark is missing — the claim is marked posted (recovered), the night flags of the snapshot are
     *             completed, and the voucher is then refused as already authorised by the normal checks;
     *   incomplete (anything else): everything the attempt wrote is undone from its snapshot and its claim removed, so the
     *             voucher is free to be posted by this request.
     * Returns the voucher row re-read after recovery. Throws GuardRefusal if the undo could not be completed.
     */
    private static function recover($db, array $existing, array $v, string $route, string $user): array
    {
        $vnum = (int) $v['Vnum'];
        $voucherId = (int) $v['ID'];
        $snapshot = json_decode((string) ($existing['snapshot'] ?? ''), true);
        $snapshot = is_array($snapshot) ? $snapshot : [];
        $ids = array_map(static fn ($r) => (int) $r['ID'], $db->query("SELECT DISTINCT ID FROM account_runing WHERE Recpt_No = ? AND UPPER(Discription) LIKE 'VOUCHER PAYMENT%' ORDER BY ID", [$vnum])->getResultArray());
        $rows = (int) ($db->query("SELECT COUNT(*) AS n FROM account_runing WHERE Recpt_No = ? AND UPPER(Discription) LIKE 'VOUCHER PAYMENT%'", [$vnum])->getRowArray()['n'] ?? 0);
        $lines = $db->query('SELECT ID, RCType FROM v_inv WHERE V_tkn = ?', [$vnum])->getResultArray();
        $linesDone = $lines !== [] && array_filter($lines, static fn ($l) => (int) $l['RCType'] !== 1) === [];
        $found = count($ids) . ' journal(s), ' . $rows . ' ledger rows, Aflag ' . (int) $v['Aflag'] . ', ' . count($lines) . ' line(s)' . ($linesDone ? ' all RCType 1' : ' not all RCType 1');
        $claim = ['vnum' => $vnum, 'voucher_id' => $voucherId, 'route' => (string) $existing['route'], 'user' => (string) $existing['user'], 'snapshot' => $snapshot];
        $who = 'the attempt of ' . $existing['claimed_at'] . ' by ' . $existing['user'] . ' (' . $existing['route'] . ')';
        if ($ids !== [] && (int) $v['Aflag'] === 1 && $linesDone) {
            foreach (($snapshot['nights'] ?? []) as $night) {
                self::must($db, $db->query('UPDATE tour_quotations SET hotel_payment_flag = 1 WHERE id = ?', [(int) $night['id']]), 'night flag completion');
            }
            $list = implode(',', $ids);
            self::must($db, $db->query('UPDATE pg_auth_claim SET state = ?, journal_ids = ?, finished_at = NOW(3) WHERE vnum = ? AND state = ?', ['posted', $list, $vnum, 'posting']), 'claim recovery update');
            self::audit($db, 'auth_posted', $vnum, $voucherId, $route, $user, 'recovered: ' . $who . ' had completed its posting (' . $found . ') and only the completion mark was missing; the claim is now marked posted', $list);
        } else {
            $summary = self::undo($db, $claim, $ids);
            self::audit($db, 'auth_recovered_undone', $vnum, $voucherId, $route, $user, 'recovered: ' . $who . ' was incomplete (' . $found . ') | undo: ' . $summary);
            if (strpos($summary, 'UNDO INCOMPLETE') !== false) {
                throw new GuardRefusal('Voucher ' . $vnum . ': an interrupted earlier attempt could only partly be undone (' . $summary . '). Refer this voucher to Accounts. Nothing was posted.', 'recovery undo incomplete');
            }
        }
        $again = $db->query('SELECT ID, Vnum, Aflag, rej, sup, Amount, ref_po, V_num, advances_pay, C_Number, S_name, Aut_user, aut_date FROM voucher WHERE ID = ?', [$voucherId])->getRowArray();
        return $again ?: $v;
    }

    /**
     * r1.4 (16 Oct 2026): abandoned attempts. An attempt whose process died leaves its claim at 'posting'; r1.2 recovers it on
     * the next request for THAT voucher. This sweep, run at the start of every claim, recovers every other claim that has stood
     * at 'posting' for longer than STALE_MIN minutes and whose named lock is free (a live process still holds its lock and is
     * left alone), so a voucher nobody retries is not left half-posted. The same recover() decides from the rows: complete →
     * marked posted, incomplete → undone from the snapshot. Bounded to SWEEP_MAX per request; a failure is logged and audited
     * by recover() itself, never raised into the caller's request.
     */
    private static function sweep($db, string $route, string $user): void
    {
        try {
            $stale = $db->query('SELECT * FROM pg_auth_claim WHERE state = ? AND claimed_at < NOW(3) - INTERVAL ? MINUTE ORDER BY claimed_at LIMIT ' . self::SWEEP_MAX, ['posting', self::STALE_MIN])->getResultArray();
        } catch (\Throwable $e) {
            return;
        }
        foreach ($stale as $c) {
            $lock = self::LOCK_PREFIX . (int) $c['vnum'];
            try {
                $got = $db->query('SELECT GET_LOCK(?, 0) AS g', [$lock])->getRowArray();
                if (!$got || (int) $got['g'] !== 1) {
                    continue;   // the lock is held: that attempt's process is alive, nothing is touched
                }
                try {
                    $again = $db->query('SELECT * FROM pg_auth_claim WHERE vnum = ? AND state = ?', [(int) $c['vnum'], 'posting'])->getRowArray();
                    $v = $db->query('SELECT ID, Vnum, Aflag, rej, sup, Amount, ref_po, V_num, advances_pay, C_Number, S_name, Aut_user, aut_date FROM voucher WHERE ID = ?', [(int) $c['voucher_id']])->getRowArray();
                    if ($again && $v) {
                        self::audit($db, 'auth_sweep', (int) $v['Vnum'], (int) $v['ID'], $route, $user, 'abandoned attempt found by the sweep: claimed ' . $again['claimed_at'] . ' by ' . $again['user'] . ' (' . $again['route'] . '), more than ' . self::STALE_MIN . ' minutes at posting, lock free');
                        self::recover($db, $again, $v, $route . ' (sweep)', $user);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'payguard sweep of voucher ' . $c['vnum'] . ': ' . $e->getMessage());   // an UNDO INCOMPLETE is already audited by recover(); the claim stays for a person to look at
                } finally {
                    self::releaseLock($db, $lock);
                }
            } catch (\Throwable $e) {
                log_message('error', 'payguard sweep lock of voucher ' . $c['vnum'] . ': ' . $e->getMessage());
            }
        }
    }

    /** Undo of one attempt from its snapshot and journal ids; returns the summary. Does not audit or release the lock. */
    private static function undo($db, array $claim, array $journalIds): string
    {
        $vnum = (int) $claim['vnum'];
        $done = [];
        try {
            $ids = array_values(array_unique(array_map('intval', $journalIds)));
            // r1.1: every statement of the undo is checked (must); a failure stops here and is reported as UNDO INCOMPLETE
            if ($ids) {
                $in = implode(',', $ids);
                self::must($db, $db->query("DELETE FROM account_runing WHERE ID IN ({$in}) AND Recpt_No = ? AND UPPER(Discription) LIKE 'VOUCHER PAYMENT%'", [$vnum]), 'ledger delete');
                $done[] = $db->affectedRows() . ' ledger rows removed (journal ids ' . $in . ')';
            } else {
                $done[] = 'no ledger rows had been written';
            }
            $s = $claim['snapshot'] ?? [];
            self::must($db, $db->query('UPDATE voucher SET Aflag = ?, Aut_user = ?, aut_date = ? WHERE ID = ?', [(int) ($s['voucher']['Aflag'] ?? 0), $s['voucher']['Aut_user'] ?? null, $s['voucher']['aut_date'] ?? null, (int) $claim['voucher_id']]), 'voucher status restore');
            $done[] = 'voucher status restored to Aflag ' . (int) ($s['voucher']['Aflag'] ?? 0);
            foreach (($s['lines'] ?? []) as $ln) {
                self::must($db, $db->query('UPDATE v_inv SET RCType = ? WHERE ID = ?', [$ln['RCType'], (int) $ln['ID']]), 'line status restore');
            }
            $done[] = count($s['lines'] ?? []) . ' voucher lines restored';
            foreach (($s['nights'] ?? []) as $n) {
                self::must($db, $db->query('UPDATE tour_quotations SET hotel_payment_flag = ? WHERE id = ?', [$n['hotel_payment_flag'], (int) $n['id']]), 'night flag restore');
            }
            if (!empty($s['consignee'])) {
                self::must($db, $db->query('UPDATE consignee SET transport_advance = ?, transport_settled = ? WHERE tour_id = ?', [$s['consignee']['transport_advance'], $s['consignee']['transport_settled'], $s['consignee']['tour_id']]), 'tour marker restore');
            }
            foreach (($s['allocations'] ?? []) as $a) {
                self::must($db, $db->query('UPDATE driver_allocation SET payment_settlement = ? WHERE id = ?', [$a['payment_settlement'], (int) $a['id']]), 'allocation restore');
            }
            self::must($db, $db->query('DELETE FROM pg_auth_claim WHERE vnum = ? AND state = ?', [$vnum, 'posting']), 'claim delete');
            $done[] = 'claim removed';
        } catch (\Throwable $e) {
            $done[] = 'UNDO INCOMPLETE: ' . self::short($e->getMessage());
            log_message('error', 'payguard fail/undo: ' . $e->getMessage());
        }
        return implode('; ', $done);
    }

    // Release build (tf-payguard-r1.3, 16 Oct 2026): the test instrumentation of the isolated test copy (forced exits and
    // throws, forced waits) is not in this file; nothing here reads a test field from the request.

    /** Snapshot of everything the authorisation path may change, for compensation. */
    private static function snapshot($db, array $v): array
    {
        $vnum = (int) $v['Vnum'];
        $s = ['voucher' => ['Aflag' => (int) $v['Aflag'], 'Aut_user' => $v['Aut_user'], 'aut_date' => $v['aut_date']]];
        $s['lines'] = $db->query('SELECT ID, RCType FROM v_inv WHERE V_tkn = ?', [$vnum])->getResultArray();
        $s['nights'] = [];
        if (!empty($v['ref_po']) && !empty($v['V_num'])) {
            $dates = [];
            foreach (explode(',', (string) $v['V_num']) as $d) {
                $d = trim($d);
                if ($d !== '' && strtotime($d) !== false) {
                    $dates[] = date('Y-m-d', strtotime($d));
                }
            }
            if ($dates) {
                $ph = implode(',', array_fill(0, count($dates), '?'));
                $s['nights'] = $db->query("SELECT id, hotel_payment_flag FROM tour_quotations WHERE quotation_id = ? AND tour_date IN ({$ph})", array_merge([$v['ref_po']], $dates))->getResultArray();
            }
        }
        $s['consignee'] = null;
        $s['allocations'] = [];
        if (in_array((int) $v['advances_pay'], [1, 11], true) && !empty($v['C_Number'])) {
            $c = $db->query('SELECT tour_id, transport_advance, transport_settled FROM consignee WHERE tour_id = ?', [$v['C_Number']])->getRowArray();
            $s['consignee'] = $c ?: null;
            $s['allocations'] = $db->query('SELECT id, payment_settlement FROM driver_allocation WHERE tour_id = ? AND driver_name = ?', [$v['C_Number'], (string) $v['S_name']])->getResultArray();
        }
        return $s;
    }

    private static function ensureTables($db): void
    {
        if (self::$tablesChecked) {
            return;
        }
        $db->query("CREATE TABLE IF NOT EXISTS pg_auth_claim (
            vnum INT NOT NULL,
            voucher_id INT NOT NULL,
            route VARCHAR(60) NOT NULL,
            user VARCHAR(100) NOT NULL,
            claimed_at DATETIME(3) NOT NULL,
            finished_at DATETIME(3) NULL,
            state VARCHAR(20) NOT NULL,
            journal_ids VARCHAR(500) NULL,
            snapshot TEXT NULL,
            PRIMARY KEY (vnum)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        self::$tablesChecked = true;
    }

    /**
     * r1.1: with DBDebug off (CI_ENVIRONMENT = production, as on the live system), a failed statement returns false and
     * throws nothing; every write of the guard and of the guarded routes goes through here so a failure is never silent.
     */
    public static function must($db, $result, string $what): void
    {
        if ($result === false) {
            $e = $db->error();
            throw new \RuntimeException($what . ' failed [' . ($e['code'] ?? '?') . '] ' . ($e['message'] ?? 'no message'));
        }
    }

    private static function releaseLock($db, string $lock): void
    {
        try {
            $db->query('SELECT RELEASE_LOCK(?) AS r', [$lock]);
        } catch (\Throwable $e) {
        }
    }

    private static function audit($db, string $action, ?int $vnum, int $voucherId, string $route, string $user, string $reason, ?string $new = null): void
    {
        Audit::log($db, [
            'action'   => $action,
            'user'     => $user,
            'ref_type' => 'voucher',
            'ref_id'   => (string) $voucherId,
            'vnum'     => $vnum,
            'prev'     => $route,
            'new'      => $new,
            'reason'   => self::VERSION . ': ' . $reason,
        ]);
    }

    private static function short(string $m): string
    {
        return mb_substr(preg_replace('/\s+/', ' ', $m), 0, 160);
    }
}

/** A refusal decided under the lock; the reason goes to the audit row, the message to the screen. */
final class GuardRefusal extends \RuntimeException
{
    public string $reason;

    public function __construct(string $message, string $reason)
    {
        parent::__construct($message);
        $this->reason = $reason;
    }
}
