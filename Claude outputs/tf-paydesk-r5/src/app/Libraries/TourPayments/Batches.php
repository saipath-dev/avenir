<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r2 (10 Oct 2026) — Supplier Payables and supplier payment batches.
 *
 *   Payment Request → Payment Approval → SUPPLIER PAYABLES (approved payments wait under their supplier)
 *     → management selects a supplier's items and creates a BATCH → one voucher, in the accountant's queue
 *       → the accountant initiates the bank payment at the PAYMENT-DATE rate → the bank approver confirms Paid
 *         (the rate of the actual payment date, locked) → remittance advice.
 *
 * The approval figures of every request — the locked USD amount, the rate shown at approval, the provisional LKR, the
 * approval date and user — are written once (tpd_request_ext.approval_*) and never overwritten. The final figures —
 * the payment-date rate, the final LKR and the exchange-rate difference — are written at Paid (tpd_request_ext.paid_*,
 * fx_diff) and never changed afterwards. Between the two, the batch voucher's amount follows the rate the accountant
 * confirmed at initiation, so the ledger (posted by the existing authorisation step at Paid) carries the final LKR.
 *
 * The voucher itself is still raised by the chain's own create_bulk_voucher (batch mode): nothing here writes voucher or
 * v_inv rows of its own, except the re-rating of an unposted (Aflag 0) batch voucher to the payment-date rate.
 */
final class Batches
{
    /** The new flow is on once the schema reached version 4 (an older install keeps the voucher-at-approval behaviour). */
    public static function accumulates($db = null): bool
    {
        static $memo = null;
        if ($memo !== null) {
            return $memo;
        }
        try {
            $db = $db ?: \Config\Database::connect();
            $memo = Schema::version($db) >= 4;
        } catch (\Throwable $e) {
            $memo = false;
        }
        return $memo;
    }

    /** Today's (or a given day's) confirmed USD rate: the Daily USD Rate entry of that date, exact day only. */
    public static function rateFor($db, ?string $date = null): ?array
    {
        $date = $date ?: date('Y-m-d');
        try {
            $r = $db->query("SELECT rate, rate_date, created_user, created_at FROM daily_usd_rate WHERE rate_date = ? LIMIT 1", [$date])->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$r || (float) $r['rate'] <= 0) {
            return null;
        }
        return ['rate' => (float) $r['rate'], 'date' => $r['rate_date'], 'by' => $r['created_user'], 'at' => $r['created_at']];
    }

    // =====================================================================
    //  Supplier Payables: approved requests waiting for a batch
    // =====================================================================

    /** One approved request as a payable item, with its approval figures. */
    private static function item($db, array $r, ?array $x, ?array $c): array
    {
        $usd = (string) $r['type'] === 'Hotel' || (($x['currency'] ?? '') === 'USD');
        $usdAmount = $usd ? (float) ($x['amount'] ?? $r['requestAmount']) : null;
        if ($usd && (string) $r['type'] === 'Hotel') {
            $usdAmount = (float) $r['requestAmount'];
        }
        $rate = null;
        if ($usd) {
            $rate = $x && $x['approval_rate'] !== null ? (float) $x['approval_rate'] : ($x && $x['usd_rate'] !== null ? (float) $x['usd_rate'] : null);
            if ($rate === null) {
                $q = $db->query("SELECT MAX(hotel_dollar_rate) AS r FROM tour_quotations WHERE quotation_id = ? AND hotel_acc = ?", [(string) $r['tour_id'], (string) $r['hotel_acc']])->getRowArray();
                $rate = (float) ($q['r'] ?? 0) > 0 ? (float) $q['r'] : null;
            }
        }
        $lkr = $x && $x['approval_lkr'] !== null ? (float) $x['approval_lkr'] : ($usd ? ($rate ? PayDesk::lkr($usdAmount, $rate) : null) : (float) $r['requestAmount']);
        $kind = (string) $r['type'] === 'Hotel' ? 'Hotel' : ((string) $r['type'] === 'Transport Advance' ? 'Driver advance' : (($x['source_type'] ?? '') === 'expense' ? 'Expense' : 'Service'));
        $dates = [];
        if ((string) $r['type'] === 'Hotel') {
            $dates = array_column($db->query("SELECT tour_date FROM tour_quotations WHERE quotation_id = ? AND hotel_acc = ? AND confirm <> 3 ORDER BY tour_date", [(string) $r['tour_id'], (string) $r['hotel_acc']])->getResultArray(), 'tour_date');
        } elseif ((string) $r['type'] === 'lunch') {
            if (($x['source_type'] ?? '') === 'expense') {
                $e = $db->query("SELECT service_date, description FROM tpd_expense WHERE id = ?", [(int) ($x['source_id'] ?? 0)])->getRowArray();
                $dates = $e && $e['service_date'] ? [$e['service_date']] : [];
                $kind = $e ? 'Expense · ' . $e['description'] : $kind;
            } else {
                $dates = array_column($db->query("SELECT tour_date FROM tour_activity WHERE tour_id = ? AND supplier_id = ? ORDER BY tour_date", [(string) $r['tour_id'], (string) $r['hotel_acc']])->getResultArray(), 'tour_date');
            }
        }
        return [
            'pr_id' => (int) $r['id'], 'tour_id' => (string) $r['tour_id'], 'manual_id' => (string) $r['manual_id'], 'guest' => (string) ($c['consignee'] ?? $r['consignee']),
            'start_date' => $c['start_date'] ?? $r['start_date'], 'end_date' => $c['end_date'] ?? $r['end_date'], 'pax' => (int) ($c['passenger_count'] ?? 0), 'agent' => (string) ($c['agent_name'] ?? ''),
            'tour_cancelled' => (int) ($c['tour_cancel'] ?? 0) === 1,
            'sup_acc' => (string) $r['hotel_acc'], 'supplier' => (string) ($r['hotel_name'] ?: $r['consignee']), 'type' => (string) $r['type'], 'kind' => $kind,
            'service_dates' => $dates, 'currency' => $usd ? 'USD' : 'LKR', 'usd_amount' => $usd ? round($usdAmount, 2) : null,
            'request_rate' => $x && $x['usd_rate'] !== null ? (float) $x['usd_rate'] : null, 'request_rate_date' => $x['rate_date'] ?? null,
            'approval_rate' => $rate, 'approval_rate_date' => $x['approval_rate_date'] ?? null, 'approval_lkr' => $lkr !== null ? round($lkr, 2) : null,
            'lkr_amount' => !$usd ? round((float) $r['requestAmount'], 2) : null,
            'approved_by' => $x['approved_by'] ?? $r['accept_user'], 'approved_at' => $x['approved_at'] ?? $r['accept_date'],
            'requested_by' => $r['payment_request_user'], 'requested_at' => $r['payment_request_date'],
            'invoice_no' => $x['invoice_no'] ?? null, 'booking_ref' => $x['booking_ref'] ?? null, 'due_date' => $x['due_date'] ?? null, 'note' => $x['note'] ?? null,
            'pr_status' => (int) $r['payment_request'], 'is_bulk' => (int) $r['is_bulk'] === 1,
            'batch_id' => $x['batch_id'] ?? null, 'voucher_vnum' => $x['voucher_vnum'] ?? null,
            'paid_rate' => $x['paid_rate'] ?? null, 'paid_lkr' => $x['paid_lkr'] ?? null, 'paid_date' => $x['paid_date'] ?? null, 'fx_diff' => $x['fx_diff'] ?? null,
            // tf-paydesk-r4: this request's share of the bank charges (allocated at Paid), and the ledger entry that posted it
            'charge_share' => isset($x['charge_share']) && $x['charge_share'] !== null ? (float) $x['charge_share'] : null, 'charge_entry_id' => $x['charge_entry_id'] ?? null,
            'rate_missing' => $usd && $rate === null,
        ];
    }

    /** The approved requests not yet in a batch: [sup_acc => [supplier, bank, items, totals]] (one supplier when $supAcc is given). */
    public static function awaiting($db, ?string $supAcc = null): array
    {
        $rows = $db->query("SELECT pr.*, c.consignee AS c_consignee, c.start_date AS c_start, c.end_date AS c_end, c.passenger_count, c.agent_name, c.tour_cancel
                            FROM payment_request pr LEFT JOIN consignee c ON c.tour_id = pr.tour_id
                            LEFT JOIN tpd_request_ext x ON x.pr_id = pr.id
                            WHERE pr.payment_request IN (4,6) AND pr.voucher_created = 0 AND (x.batch_id IS NULL)" . ($supAcc !== null ? ' AND pr.hotel_acc = ' . $db->escape($supAcc) : '') . "
                            ORDER BY pr.hotel_name, pr.accept_date, pr.id")->getResultArray();
        $out = [];
        // tf-paydesk-r5: the request records and the voucher check for ALL rows in a few queries (was three queries per row)
        $exts = [];
        try {
            if ($rows) {
                foreach ($db->query("SELECT * FROM tpd_request_ext WHERE pr_id IN (" . implode(',', array_map(static fn ($r) => (int) $r['id'], $rows)) . ")")->getResultArray() as $x) {
                    $exts[(int) $x['pr_id']] = $x;
                }
            }
        } catch (\Throwable $e) {
        }
        $vouchers = PayDesk::vouchersOfRequests($db, $rows);
        foreach ($rows as $r) {
            // a request answered by a voucher the desk does not know about (older rows) is not a payable
            $x = $exts[(int) $r['id']] ?? null;
            if (!$x || empty($x['voucher_vnum'])) {
                if (!empty($vouchers[(int) $r['id']])) {
                    continue;
                }
            } elseif (!empty($x['voucher_vnum'])) {
                continue;
            }
            $c = ['consignee' => $r['c_consignee'], 'start_date' => $r['c_start'], 'end_date' => $r['c_end'], 'passenger_count' => $r['passenger_count'], 'agent_name' => $r['agent_name'], 'tour_cancel' => $r['tour_cancel']];
            $it = self::item($db, $r, $x, $c);
            $it['disputed'] = (int) ($x['disputed'] ?? 0) === 1;   // tf-paydesk-r5: a disputed item waits here but is kept out of every bulk payment
            $it['dispute_reason'] = $it['disputed'] ? ($x['dispute_reason'] ?? null) : null;
            $acc = $it['sup_acc'];
            if (!isset($out[$acc])) {
                $out[$acc] = ['sup_acc' => $acc, 'supplier' => $it['supplier'], 'bank' => SupplierBank::forPayment($db, $acc), 'items' => [], 'usd_total' => 0.0, 'lkr_total' => 0.0, 'approval_lkr_total' => 0.0, 'tours' => [], 'rate_missing' => false, 'is_bulk' => $it['is_bulk']];
            }
            $out[$acc]['items'][] = $it;
            $out[$acc]['usd_total'] += (float) ($it['usd_amount'] ?? 0);
            $out[$acc]['lkr_total'] += (float) ($it['lkr_amount'] ?? 0);
            $out[$acc]['approval_lkr_total'] += (float) ($it['approval_lkr'] ?? 0);
            $out[$acc]['tours'][$it['tour_id']] = $it['manual_id'];
            $out[$acc]['rate_missing'] = $out[$acc]['rate_missing'] || $it['rate_missing'];
        }
        foreach ($out as &$s) {
            $s['usd_total'] = round($s['usd_total'], 2);
            $s['lkr_total'] = round($s['lkr_total'], 2);
            $s['approval_lkr_total'] = round($s['approval_lkr_total'], 2);
            $s['item_count'] = count($s['items']);
            $s['tour_count'] = count($s['tours']);
            $s['tours'] = array_values($s['tours']);
            $s['currency'] = $s['usd_total'] > 0 ? ($s['lkr_total'] > 0 ? 'MIXED' : 'USD') : 'LKR';
            $s['supplier_name'] = $s['bank']['supplier_name'] ?: $s['supplier'];
        }
        unset($s);
        uasort($out, static fn ($a, $b) => [$b['approval_lkr_total']] <=> [$a['approval_lkr_total']]);
        return array_values($out);
    }

    /** tf-paydesk-r3 — the reasons a payment may skip the accumulation stage (Pay Individually Now). */
    public const BYPASS = [
        'driver_advance'    => 'Driver advance',
        'driver_settlement' => 'Driver settlement',
        'urgent_cash'       => 'Urgent cash payment',
        'pay_at_gate'       => 'Pay-at-gate item',
        'emergency'         => 'Emergency supplier payment',
        'other'             => 'Other authorised payment (must not wait for a bulk batch)',
    ];

    /** Checks a selection for a batch: one supplier, approved, no voucher, no batch yet, rates known. */
    public static function prepare($db, string $supAcc, array $prIds, bool $individual = false): array
    {
        $prIds = array_values(array_unique(array_filter(array_map('intval', $prIds))));
        sort($prIds);
        if (!$prIds) {
            return ['ok' => false, 'message' => 'Choose at least one approved payment.'];
        }
        if ($individual && count($prIds) !== 1) {
            return ['ok' => false, 'message' => 'Pay Individually Now takes exactly one approved payment (it creates a single-item batch).'];
        }
        $sup = self::awaiting($db, $supAcc);
        $have = [];
        foreach ($sup[0]['items'] ?? [] as $it) {
            $have[$it['pr_id']] = $it;
        }
        $items = [];
        foreach ($prIds as $id) {
            if (!isset($have[$id])) {
                $r = $db->query("SELECT id, hotel_acc, hotel_name, payment_request, voucher_created, manual_id FROM payment_request WHERE id = ?", [$id])->getRowArray();
                if (!$r) {
                    return ['ok' => false, 'message' => 'Request #' . $id . ' does not exist.'];
                }
                if ((string) $r['hotel_acc'] !== $supAcc) {
                    return ['ok' => false, 'message' => 'One batch is one supplier: request #' . $id . ' (' . $r['manual_id'] . ') belongs to ' . $r['hotel_name'] . '.'];
                }
                if (!in_array((int) $r['payment_request'], [4, 6], true)) {
                    return ['ok' => false, 'message' => 'Request #' . $id . ' (' . $r['manual_id'] . ') is not approved (status ' . $r['payment_request'] . '). Only approved payments go into a batch.'];
                }
                return ['ok' => false, 'message' => 'Request #' . $id . ' (' . $r['manual_id'] . ') already has a voucher or is in another batch.'];
            }
            if ($have[$id]['rate_missing']) {
                return ['ok' => false, 'message' => 'Request #' . $id . ' (' . $have[$id]['manual_id'] . ') is a USD payment with no approval rate on record. Confirm Today\'s USD rate and approve it again, or ask the administrator.'];
            }
            if ($have[$id]['tour_cancelled']) {
                return ['ok' => false, 'message' => 'Tour ' . $have[$id]['manual_id'] . ' is cancelled; check request #' . $id . ' before paying it.'];
            }
            $items[] = $have[$id];
        }
        $usd = round(array_sum(array_map(static fn ($i) => (float) ($i['usd_amount'] ?? 0), $items)), 2);
        $lkr = round(array_sum(array_map(static fn ($i) => (float) ($i['lkr_amount'] ?? 0), $items)), 2);
        $prov = round(array_sum(array_map(static fn ($i) => (float) ($i['approval_lkr'] ?? 0), $items)), 2);
        return ['ok' => true, 'items' => $items, 'pr_ids' => $prIds, 'sup_acc' => $supAcc, 'supplier' => $sup[0]['supplier_name'] ?? $items[0]['supplier'], 'bank' => $sup[0]['bank'] ?? null,
            'usd_total' => $usd, 'lkr_total' => $lkr, 'approval_lkr_total' => $prov, 'currency' => $usd > 0 ? ($lkr > 0 ? 'MIXED' : 'USD') : 'LKR',
            'tour_count' => count(array_unique(array_column($items, 'tour_id'))), 'item_count' => count($items)];
    }

    /** After the chain raised the voucher: the batch row, the requests tied to it, the v_inv line of each. */
    public static function finish($db, array $prep, int $vnum, array $acct, ?string $note, string $by, array $opts = []): array
    {
        $now = date('Y-m-d H:i:s');
        $individual = !empty($opts['individual']);
        $db->table('tpd_batch')->insert(['sup_acc' => $prep['sup_acc'], 'supplier_name' => $prep['supplier'], 'currency' => $prep['currency'], 'usd_total' => $prep['usd_total'] > 0 ? $prep['usd_total'] : null,
            'approval_lkr_total' => $prep['approval_lkr_total'], 'item_count' => $prep['item_count'], 'tour_count' => $prep['tour_count'],
            // management's SUGGESTED paying account — the accountant confirms the actual one at initiation, the bank approver at Paid
            'bank_account_id' => $acct['id'] ?? null, 'ledger_code' => $acct['ledger_code'] ?? null, 'suggested_bank_account_id' => $acct['id'] ?? null, 'suggested_ledger_code' => $acct['ledger_code'] ?? null,
            'vnum' => $vnum, 'status' => 'issued', 'note' => $note ?: null, 'created_by' => $by, 'created_at' => $now,
            'individual' => $individual ? 1 : 0, 'bypass_reason' => $individual ? ($opts['bypass_reason'] ?? 'other') : null, 'bypass_note' => $individual ? (($opts['bypass_note'] ?? '') ?: null) : null,
            // tf-paydesk-r5: a child of a bulk batch
            'parent_id' => $opts['parent_id'] ?? null, 'mode' => $opts['mode'] ?? 'single', 'writer' => !empty($opts['parent_id']) ? 'atomic' : 'legacy']);
        $id = (int) $db->insertID();
        $no = 'SB-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
        $db->query("UPDATE tpd_batch SET batch_no = ? WHERE id = ?", [$no, $id]);
        // the v_inv lines were written in request order (ids ascending) by create_bulk_voucher
        $lines = $db->query("SELECT ID, Invoice, Amount, Rate FROM v_inv WHERE V_tkn = ? ORDER BY ID", [$vnum])->getResultArray();
        foreach ($prep['items'] as $k => $it) {
            $vi = null;
            if (isset($lines[$k]) && (string) $lines[$k]['Invoice'] === (string) $it['tour_id'] && abs((float) $lines[$k]['Amount'] - (float) ($it['approval_lkr'] ?? $it['lkr_amount'])) < 0.02) {
                $vi = (int) $lines[$k]['ID'];
            } else {
                foreach ($lines as $l) {
                    if ((string) $l['Invoice'] === (string) $it['tour_id'] && abs((float) $l['Amount'] - (float) ($it['approval_lkr'] ?? $it['lkr_amount'])) < 0.02) {
                        $vi = (int) $l['ID'];
                        break;
                    }
                }
            }
            if (!$db->query("SELECT id FROM tpd_request_ext WHERE pr_id = ?", [$it['pr_id']])->getRowArray()) {
                PayDesk::recordRequest($db, $it['pr_id'], ['usd_rate' => $it['approval_rate']]);
            }
            $db->query("UPDATE tpd_request_ext SET batch_id = ?, vinv_id = ?, voucher_vnum = ?, approved_by = COALESCE(approved_by, ?), approved_at = COALESCE(approved_at, ?),
                        approval_rate = COALESCE(approval_rate, ?), approval_lkr = COALESCE(approval_lkr, ?), approval_rate_date = COALESCE(approval_rate_date, ?) WHERE pr_id = ?",
                [$id, $vi, $vnum, $it['approved_by'], $it['approved_at'] ?: $now, $it['approval_rate'], $it['approval_lkr'], $it['approval_rate_date'] ?: substr((string) ($it['approved_at'] ?: $now), 0, 10), $it['pr_id']]);
        }
        if (!empty($opts['quiet'])) {   // tf-paydesk-r5 bulk child: one voucher_issued entry on its tour; the parent carries the batch entry
            Audit::log($db, ['action' => 'voucher_issued', 'user' => $by, 'tour_id' => $prep['items'][0]['tour_id'], 'manual_id' => $prep['items'][0]['manual_id'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'bank_account' => $acct['label'] ?? null, 'new' => ['batch' => $no, 'bulk_batch_id' => $opts['parent_id'], 'voucher' => $vnum]]);
            return self::get($db, $id);
        }
        Audit::log($db, ['action' => 'batch_created', 'user' => $by, 'tour_id' => $prep['tour_count'] === 1 ? $prep['items'][0]['tour_id'] : null, 'manual_id' => implode(', ', array_unique(array_column($prep['items'], 'manual_id'))),
            'ref_type' => 'batch', 'ref_id' => $no, 'vnum' => $vnum, 'bank_account' => $acct['label'] ?? null, 'reason' => $note ?: null,
            'new' => ['batch' => $no, 'supplier' => $prep['supplier'], 'requests' => $prep['pr_ids'], 'tours' => array_values(array_unique(array_column($prep['items'], 'manual_id'))), 'usd' => $prep['usd_total'], 'provisional_lkr' => $prep['approval_lkr_total'], 'payment_voucher_no' => $vnum,
                'suggested_account' => $acct['label'] ?? null, 'individual' => $individual, 'bypass_reason' => $individual ? (self::BYPASS[$opts['bypass_reason'] ?? 'other'] ?? $opts['bypass_reason']) : null]]);
        if ($individual) {
            // the accumulation stage was bypassed: why, by whom, for which payment — its own audit entry
            Audit::log($db, ['action' => 'accumulation_bypassed', 'user' => $by, 'tour_id' => $prep['items'][0]['tour_id'], 'manual_id' => $prep['items'][0]['manual_id'], 'ref_type' => 'batch', 'ref_id' => $no, 'vnum' => $vnum,
                'reason' => (self::BYPASS[$opts['bypass_reason'] ?? 'other'] ?? $opts['bypass_reason']) . (($opts['bypass_note'] ?? '') !== '' ? ': ' . $opts['bypass_note'] : ''),
                'new' => ['batch' => $no, 'supplier' => $prep['supplier'], 'request' => $prep['pr_ids'][0], 'payment_voucher_no' => $vnum, 'bypass_reason' => $opts['bypass_reason'] ?? 'other']]);
        }
        foreach (array_unique(array_column($prep['items'], 'tour_id')) as $tid) {
            if ($prep['tour_count'] > 1) {
                Audit::log($db, ['action' => 'voucher_issued', 'user' => $by, 'tour_id' => (string) $tid, 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'bank_account' => $acct['label'] ?? null, 'new' => ['batch' => $no, 'voucher' => $vnum]]);
            }
        }
        if ($prep['tour_count'] === 1) {
            Audit::log($db, ['action' => 'voucher_issued', 'user' => $by, 'tour_id' => $prep['items'][0]['tour_id'], 'manual_id' => $prep['items'][0]['manual_id'], 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'bank_account' => $acct['label'] ?? null, 'new' => ['batch' => $no, 'voucher' => $vnum]]);
        }
        return self::get($db, $id);
    }

    // =====================================================================
    //  A batch
    // =====================================================================

    public static function get($db, int $id): ?array
    {
        try {
            return $db->query("SELECT * FROM tpd_batch WHERE id = ?", [$id])->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function byVnum($db, int $vnum): ?array
    {
        try {
            return $db->query("SELECT * FROM tpd_batch WHERE vnum = ? ORDER BY id DESC LIMIT 1", [$vnum])->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The batch's items in request order, with their approval and (when there) final figures. */
    public static function items($db, int $batchId): array
    {
        $rows = $db->query("SELECT pr.*, c.consignee AS c_consignee, c.start_date AS c_start, c.end_date AS c_end, c.passenger_count, c.agent_name, c.tour_cancel
                            FROM tpd_request_ext x JOIN payment_request pr ON pr.id = x.pr_id LEFT JOIN consignee c ON c.tour_id = pr.tour_id
                            WHERE x.batch_id = ? ORDER BY pr.id", [$batchId])->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $x = $db->query("SELECT * FROM tpd_request_ext WHERE pr_id = ?", [(int) $r['id']])->getRowArray();
            $c = ['consignee' => $r['c_consignee'], 'start_date' => $r['c_start'], 'end_date' => $r['c_end'], 'passenger_count' => $r['passenger_count'], 'agent_name' => $r['agent_name'], 'tour_cancel' => $r['tour_cancel']];
            $out[] = self::item($db, $r, $x, $c);
        }
        return $out;
    }

    /**
     * The figures of a batch at a given rate: each USD item's LKR at that rate (rounded per item, so the parts add up
     * exactly), the LKR items as they are, the totals, and the difference against the provisional (approval) LKR.
     */
    public static function figures(array $items, ?float $rate): array
    {
        $lines = [];
        $actual = 0.0;
        $prov = 0.0;
        $usd = 0.0;
        $missing = false;
        foreach ($items as $it) {
            $p = (float) ($it['approval_lkr'] ?? 0);
            if ($it['currency'] === 'USD') {
                $a = $rate ? PayDesk::lkr((float) $it['usd_amount'], $rate) : null;
                $usd += (float) $it['usd_amount'];
                $missing = $missing || $a === null;
            } else {
                $a = (float) $it['lkr_amount'];
            }
            $lines[] = ['pr_id' => $it['pr_id'], 'provisional_lkr' => round($p, 2), 'actual_lkr' => $a !== null ? round($a, 2) : null, 'fx_diff' => $a !== null ? round($a - $p, 2) : null];
            $actual += (float) ($a ?? 0);
            $prov += $p;
        }
        return ['rate' => $rate, 'usd_total' => round($usd, 2), 'provisional_lkr_total' => round($prov, 2), 'actual_lkr_total' => $missing ? null : round($actual, 2),
            'fx_diff' => $missing ? null : round($actual - $prov, 2), 'rate_missing' => $missing, 'lines' => $lines];
    }

    /** Everything about a batch for its page: the batch, items, voucher, supplier bank, initiation, Paid lock, remittance, audit. */
    public static function detail($db, int $id): ?array
    {
        $b = self::get($db, $id);
        if (!$b) {
            return null;
        }
        $items = self::items($db, $id);
        $v = $b['vnum'] ? $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [(int) $b['vnum']])->getRowArray() : null;
        $init = $b['vnum'] ? ($db->query("SELECT * FROM tpd_bank_initiation WHERE vnum = ?", [(int) $b['vnum']])->getRowArray() ?: null) : null;
        $app = $b['vnum'] ? ($db->query("SELECT * FROM tpd_bank_approval WHERE vnum = ?", [(int) $b['vnum']])->getRowArray() ?: null) : null;
        $rem = $b['vnum'] ? ($db->query("SELECT * FROM tpd_remittance WHERE vnum = ?", [(int) $b['vnum']])->getRowArray() ?: null) : null;
        $docs = $b['vnum'] ? $db->query("SELECT * FROM tpd_document WHERE vnum = ? ORDER BY id", [(int) $b['vnum']])->getResultArray() : [];
        $acct = null;
        if (!empty($app['bank_account_id'])) {
            $acct = BankAccounts::get($db, (int) $app['bank_account_id']);
        } elseif (!empty($init['bank_account_id'])) {
            $acct = BankAccounts::get($db, (int) $init['bank_account_id']);
        } elseif (!empty($b['bank_account_id'])) {
            $acct = BankAccounts::get($db, (int) $b['bank_account_id']);
        } elseif ($v) {
            $acct = BankAccounts::byLedger($db, $v['Bank_acc']);
        }
        $stage = $b['status'];
        if ($v) {
            $stage = PayDesk::isCancelled($v) ? 'cancelled' : ((int) $v['finalized'] === 1 ? ($rem && $rem['status'] === 'sent' ? 'notified' : 'paid') : ($init ? 'initiated' : 'issued'));
        }
        $isUsd = $b['currency'] !== 'LKR';
        $today = date('Y-m-d');
        $todayRate = $isUsd ? self::rateFor($db, $today) : null;
        // the figures: provisional (approval), at initiation, final (Paid) — and today's, for the step about to be taken
        $provisional = self::figures($items, null);
        $atInit = $init ? self::figures($items, $init['usd_rate'] !== null ? (float) $init['usd_rate'] : null) : null;
        $final = $app ? self::figures($items, $app['usd_rate'] !== null ? (float) $app['usd_rate'] : null) : null;
        $atToday = !$app ? self::figures($items, $todayRate['rate'] ?? ($isUsd ? null : null)) : null;
        $audit = $b['vnum'] ? Audit::forVoucher($db, (int) $b['vnum']) : [];
        try {
            $more = $db->query("SELECT * FROM tpd_audit WHERE ref_type = 'batch' AND ref_id = ? ORDER BY id", [(string) $b['batch_no']])->getResultArray();
            $seen = array_column($audit, 'id');
            foreach ($more as $m) {
                if (!in_array($m['id'], $seen, false)) {
                    $audit[] = $m;
                }
            }
            usort($audit, static fn ($a, $c) => strcmp((string) $a['at'], (string) $c['at']));
        } catch (\Throwable $e) {
        }
        // tf-paydesk-r3: the suggested account (management), the account as it stands (the voucher's), the ledger mapping and the Paid block
        $suggested = !empty($b['suggested_bank_account_id']) ? BankAccounts::get($db, (int) $b['suggested_bank_account_id']) : null;
        $current = $v ? self::currentAccount($db, $v, $b, $init) : null;
        $map = self::fxMapping($db);
        $paidBlock = $isUsd && !$map['complete'] && !$app ? $map['warning'] : null;
        // tf-paydesk-r4: the bank-charge mapping, the charges as they stand (the lock's, else the initiation's), the per-tour allocation
        // preview and, once Paid, the ledger read back and reconciled to the bank debit
        $chMap = self::chargeMapping($db);
        $chargesNow = $app ? (float) ($app['charges'] ?? 0) : ($init ? (float) ($init['charges'] ?? 0) : 0.0);
        $chargeBlock = !$app && $chargesNow > 0 && !$chMap['complete'] ? $chMap['warning'] : null;
        $figForCharges = $app ? $final : ($atInit ?: $atToday);
        $chargeLinesPreview = $v ? self::chargeLines($db, $v, $b, $items, $figForCharges) : [];
        $chargeShares = $chargesNow > 0 ? self::allocate($chargeLinesPreview, $chargesNow) : [];
        $reconciliation = $app ? self::reconcile($db, (int) $b['vnum']) : null;
        return [
            'batch' => $b, 'items' => $items, 'voucher' => $v, 'supplier' => SupplierBank::forPayment($db, (string) $b['sup_acc']), 'bank_account' => $acct,
            'initiation' => $init, 'approval' => $app, 'remittance' => $rem, 'documents' => $docs, 'stage' => $stage, 'stage_label' => self::STAGES[$stage] ?? ucfirst($stage),
            'is_usd' => $isUsd, 'today' => $today, 'today_rate' => $todayRate, 'provisional' => $provisional, 'at_initiation' => $atInit, 'final' => $final, 'at_today' => $atToday,
            'tours' => array_values(array_unique(array_column($items, 'manual_id'))), 'audit' => $audit,
            'fx_ledger' => $map['loss'], 'fx_mapping' => $map, 'paid_block' => $paidBlock,
            'payment_voucher_no' => $b['vnum'] ? (int) $b['vnum'] : null,
            'suggested_account' => $suggested ? ['id' => (int) $suggested['id'], 'label' => $suggested['label'], 'ledger_code' => $suggested['ledger_code'], 'currency' => $suggested['currency']] : null,
            'current_account' => $current ? ['id' => $current['id'], 'label' => $current['label'], 'ledger_code' => $current['ledger_code'], 'currency' => $current['account']['currency'] ?? null] : null,
            'individual' => (int) ($b['individual'] ?? 0) === 1, 'bypass_label' => !empty($b['bypass_reason']) ? (self::BYPASS[$b['bypass_reason']] ?? $b['bypass_reason']) : null,
            'account_changes' => array_values(array_filter($audit, static fn ($a) => ($a['action'] ?? '') === 'bank_account_changed')),
            'charge_mapping' => $chMap, 'charge_block' => $chargeBlock, 'charges' => $chargesNow,
            'charge_shares' => array_map(static fn ($l) => ['pr_id' => $l['pr_id'], 'tour_id' => $l['tour_id'], 'manual_id' => $l['manual_id'], 'basis' => round((float) $l['basis'], 2), 'share' => $app && $l['pr_id'] ? (self::chargeShareOf($items, (int) $l['pr_id'])) : ($chargeShares[$l['key']] ?? 0.0)], $chargeLinesPreview),
            'reconciliation' => $reconciliation,
        ];
    }

    private static function chargeShareOf(array $items, int $prId): ?float
    {
        foreach ($items as $it) {
            if ((int) $it['pr_id'] === $prId) {
                return $it['charge_share'];
            }
        }
        return null;
    }

    public const STAGES = ['issued' => 'Payment Voucher Issued — in the accountant\'s queue', 'initiated' => 'Bank Payment Initiated — Awaiting Bank Approval', 'paid' => 'Fully Paid — Supplier Notification Pending', 'notified' => 'Fully Paid — Supplier Notified', 'cancelled' => 'Cancelled / Reversed'];

    /** All batches, newest first, with their stage. */
    public static function listing($db, string $status = '', string $q = '', int $limit = 300): array
    {
        try {
            $rows = $db->query("SELECT b.*, v.Aflag, v.finalized, v.bank_date, v.Amount AS voucher_amount, i.payment_date, i.usd_rate AS init_rate, i.initiated_by, a.paid_date, a.usd_rate AS paid_rate, a.lkr_amount AS paid_lkr, a.fx_diff, a.charges, a.total_debit, a.approved_by AS paid_by, r.status AS remit_status, r.sent_at
                                FROM tpd_batch b LEFT JOIN voucher v ON v.Vnum = b.vnum LEFT JOIN tpd_bank_initiation i ON i.vnum = b.vnum LEFT JOIN tpd_bank_approval a ON a.vnum = b.vnum LEFT JOIN tpd_remittance r ON r.vnum = b.vnum
                                " . (trim($q) !== '' ? ' WHERE (b.supplier_name LIKE ' . $db->escape('%' . trim($q) . '%') . ' OR b.batch_no LIKE ' . $db->escape('%' . trim($q) . '%') . ' OR b.vnum = ' . $db->escape(trim($q)) . ')' : '') . "
                                ORDER BY b.id DESC LIMIT " . (int) $limit)->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            // tf-paydesk-r5: a bulk parent is listed with its roll-up; its children are listed under it (parent_no), not as top-level batches
            if (($r['mode'] ?? 'single') === 'bulk_parent') {
                $d = Bulk::detail($db, (int) $r['id']);
                $r['stage'] = $d['stage'] ?? 'issued';
                $r['stage_label'] = $d['stage_label'] ?? 'Bulk batch';
                $r['tours'] = $d['tours'] ?? [];
                $r['bulk'] = ['count' => $d['count'] ?? 0, 'counts' => $d['counts'] ?? [], 'first' => $d['children'][0]['vnum'] ?? null, 'last' => $d['children'] ? end($d['children'])['vnum'] : null, 'totals' => $d['totals'] ?? []];
                $r['bypass_label'] = null;
                if ($status === '' || $status === 'all' || str_starts_with((string) $r['stage'], $status)) {
                    $out[] = $r;
                }
                continue;
            }
            $stage = $r['status'];
            if ($r['Aflag'] !== null) {
                $stage = PayDesk::isCancelled($r) ? 'cancelled' : ((int) $r['finalized'] === 1 ? ($r['remit_status'] === 'sent' ? 'notified' : 'paid') : ($r['payment_date'] || $r['initiated_by'] ? 'initiated' : 'issued'));
            }
            if ($status !== '' && $status !== 'all' && $stage !== $status) {
                continue;
            }
            $r['stage'] = $stage;
            $r['stage_label'] = self::STAGES[$stage] ?? ucfirst($stage);
            $r['tours'] = array_values(array_unique(array_column(self::items($db, (int) $r['id']), 'manual_id')));
            $r['bypass_label'] = !empty($r['bypass_reason']) ? (self::BYPASS[$r['bypass_reason']] ?? $r['bypass_reason']) : null;
            if (!empty($r['parent_id'])) {
                $r['parent_no'] = 'SB-' . str_pad((string) $r['parent_id'], 5, '0', STR_PAD_LEFT);
            }
            $out[] = $r;
        }
        return $out;
    }

    // =====================================================================
    //  The payment-date rate: re-rating the batch voucher, the Paid allocation, the exchange-rate difference
    // =====================================================================

    /**
     * Brings an unposted batch voucher (Aflag 0) to the given rate: voucher.Amount / g_ttl and each USD v_inv line's
     * Amount / InvAmount / Rate. Nothing is touched once the ledger is posted (Aflag 1) — the difference is then
     * recorded on the Paid lock and, when an exchange gain/loss account is configured, posted there.
     */
    public static function applyRate($db, array $batch, array $items, ?float $rate): array
    {
        $fig = self::figures($items, $rate);
        if ($fig['rate_missing'] || !$batch['vnum']) {
            return $fig;
        }
        $v = $db->query("SELECT ID, Aflag, Amount FROM voucher WHERE Vnum = ? LIMIT 1", [(int) $batch['vnum']])->getRowArray();
        if (!$v || (int) $v['Aflag'] !== 0) {
            $fig['voucher_updated'] = false;
            return $fig;
        }
        foreach ($items as $k => $it) {
            $line = $fig['lines'][$k];
            if ($it['currency'] !== 'USD' || $line['actual_lkr'] === null) {
                continue;
            }
            $x = $db->query("SELECT vinv_id FROM tpd_request_ext WHERE pr_id = ?", [$it['pr_id']])->getRowArray();
            if (!empty($x['vinv_id'])) {
                $db->query("UPDATE v_inv SET Amount = ?, InvAmount = ?, Rate = ? WHERE ID = ? AND V_tkn = ?", [$line['actual_lkr'], $line['actual_lkr'], $rate, (int) $x['vinv_id'], (int) $batch['vnum']]);
            }
        }
        $db->query("UPDATE voucher SET Amount = ?, g_ttl = ? WHERE Vnum = ? AND Aflag = 0", [$fig['actual_lkr_total'], $fig['actual_lkr_total'], (int) $batch['vnum']]);
        $fig['voucher_updated'] = true;
        return $fig;
    }

    /** At Paid: the final figures of every request in the batch, written once. */
    public static function allocatePaid($db, array $batch, array $items, ?float $rate, string $paidDate): array
    {
        $fig = self::figures($items, $rate);
        foreach ($items as $k => $it) {
            $line = $fig['lines'][$k];
            $db->query("UPDATE tpd_request_ext SET paid_rate = COALESCE(paid_rate, ?), paid_lkr = COALESCE(paid_lkr, ?), paid_date = COALESCE(paid_date, ?), fx_diff = COALESCE(fx_diff, ?) WHERE pr_id = ?",
                [$it['currency'] === 'USD' ? $rate : null, $line['actual_lkr'], $paidDate, $line['fx_diff'], $it['pr_id']]);
        }
        return $fig;
    }

    // =====================================================================
    //  tf-paydesk-r3 — the exchange gain/loss ledger mapping and the per-tour postings
    // =====================================================================

    /** A ledger account of the chart of accounts, by code — null when it does not exist or is deactivated. */
    public static function ledgerAccount($db, ?string $code): ?array
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }
        try {
            $a = $db->query("SELECT L_Code, PName, COALESCE(deact, 0) AS deact FROM account_posting WHERE L_Code = ? LIMIT 1", [$code])->getRowArray();
            return $a && (int) $a['deact'] === 0 ? ['code' => $a['L_Code'], 'name' => $a['PName']] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * The Realised Exchange Gain and Realised Exchange Loss accounts the administrator / Accounts manager mapped in
     * Setup (tpd_meta fx_gain_ledger_code / fx_loss_ledger_code). Nothing is invented: the codes must name existing,
     * active accounts of the chart of accounts, chosen by Accounts. complete = both mapped and valid.
     */
    public static function fxMapping($db): array
    {
        $get = static function ($k) use ($db) {
            try {
                $r = $db->query("SELECT v FROM tpd_meta WHERE k = ?", [$k])->getRowArray();
                return trim((string) ($r['v'] ?? ''));
            } catch (\Throwable $e) {
                return '';
            }
        };
        $gainCode = $get('fx_gain_ledger_code');
        $lossCode = $get('fx_loss_ledger_code');
        $gain = self::ledgerAccount($db, $gainCode);
        $loss = self::ledgerAccount($db, $lossCode);
        $problems = [];
        if ($gainCode === '') {
            $problems[] = 'no Realised Exchange Gain account mapped';
        } elseif (!$gain) {
            $problems[] = 'the mapped Realised Exchange Gain account ' . $gainCode . ' is not an active ledger account';
        }
        if ($lossCode === '') {
            $problems[] = 'no Realised Exchange Loss account mapped';
        } elseif (!$loss) {
            $problems[] = 'the mapped Realised Exchange Loss account ' . $lossCode . ' is not an active ledger account';
        }
        if ($gain && $loss && $gain['code'] === $loss['code']) {
            $problems[] = 'the gain and loss accounts must be different';
        }
        return ['gain' => $gain, 'loss' => $loss, 'gain_code' => $gainCode, 'loss_code' => $lossCode, 'complete' => !$problems, 'problems' => $problems,
            'warning' => $problems ? 'Exchange gain/loss ledger mapping incomplete: ' . implode('; ', $problems) . '. Final Paid of a foreign-currency payment is blocked until the System Administrator or the Accounts manager maps the accounts (Tour Payment Dashboard → Settings → Ledger mapping).' : null];
    }

    /** Kept for older callers: the loss account, or null. */
    public static function fxLedger($db): ?array
    {
        return self::fxMapping($db)['loss'];
    }

    // =====================================================================
    //  tf-paydesk-r4 — the bank-charge ledger mapping, the allocation of charges across tour lines, the postings, the reconciliation
    // =====================================================================

    /** The ledger entries the desk posts itself, by their account_runing.advances_pay tag (the chain's own values are 0/1/2/11/22/111…). */
    public const AP_FX = 23;
    public const AP_CHARGES = 24;

    /**
     * The Bank Charges account the administrator / Accounts manager mapped in Settings (tpd_meta bank_charge_ledger_code) — an
     * existing, active account of the chart of accounts, chosen by Accounts. Needed only when a payment carries bank charges.
     */
    public static function chargeMapping($db): array
    {
        $code = '';
        try {
            $r = $db->query("SELECT v FROM tpd_meta WHERE k = 'bank_charge_ledger_code'")->getRowArray();
            $code = trim((string) ($r['v'] ?? ''));
        } catch (\Throwable $e) {
        }
        $acct = self::ledgerAccount($db, $code);
        $problems = [];
        if ($code === '') {
            $problems[] = 'no Bank Charges account mapped';
        } elseif (!$acct) {
            $problems[] = 'the mapped Bank Charges account ' . $code . ' is not an active ledger account';
        }
        return ['account' => $acct, 'code' => $code, 'complete' => !$problems, 'problems' => $problems,
            'warning' => $problems ? 'Bank-charge ledger mapping incomplete: ' . implode('; ', $problems) . '. Final Paid of a payment with bank charges is blocked until the System Administrator or the Accounts manager maps the account (Tour Payment Dashboard → Settings → Ledger mapping). A payment the bank charged nothing for (charges 0.00) does not need it.' : null];
    }

    /** All three mappings and whether they are usable. */
    public static function ledgerMappings($db): array
    {
        $fx = self::fxMapping($db);
        $ch = self::chargeMapping($db);
        return ['fx' => $fx, 'charges' => $ch, 'complete' => $fx['complete'] && $ch['complete']];
    }

    /**
     * Allocates an amount across lines pro rata to their basis, to the cent, so that the shares add up EXACTLY to the amount
     * (largest-remainder rounding). A line without a positive basis gets an equal share when no line has one. One line → all.
     * $lines: [['key' => any, 'basis' => float], …]  → [key => share]
     */
    public static function allocate(array $lines, float $amount): array
    {
        $amount = round($amount, 2);
        $out = [];
        if (!$lines) {
            return $out;
        }
        $n = count($lines);
        $total = 0.0;
        foreach ($lines as $l) {
            $total += max(0.0, (float) ($l['basis'] ?? 0));
        }
        $cents = (int) round($amount * 100);
        $raw = [];
        $floor = [];
        $given = 0;
        foreach ($lines as $i => $l) {
            $w = $total > 0 ? max(0.0, (float) ($l['basis'] ?? 0)) / $total : 1 / $n;
            $raw[$i] = $cents * $w;
            $floor[$i] = (int) floor($raw[$i] + 1e-9);
            $given += $floor[$i];
        }
        $left = $cents - $given;
        // the remaining cents go to the lines with the largest fractional parts (ties: the earlier line)
        $order = array_keys($raw);
        usort($order, static fn ($a, $b) => ($raw[$b] - $floor[$b]) <=> ($raw[$a] - $floor[$a]) ?: $a <=> $b);
        foreach ($order as $i) {
            if ($left <= 0) {
                break;
            }
            $floor[$i]++;
            $left--;
        }
        foreach ($lines as $i => $l) {
            $out[$l['key']] = $floor[$i] / 100;
        }
        return $out;
    }

    /**
     * The tour lines a voucher's bank charges are allocated to: the batch's items with their final LKR as the basis, or, for a
     * voucher outside the batch flow, one line for the voucher's own tour.
     */
    public static function chargeLines($db, array $v, ?array $batch, array $items, ?array $fig): array
    {
        $lines = [];
        if ($batch && $items) {
            foreach ($items as $k => $it) {
                $line = $fig['lines'][$k] ?? null;
                $basis = $line && $line['actual_lkr'] !== null ? (float) $line['actual_lkr'] : (float) ($it['approval_lkr'] ?? 0);
                $lines[] = ['key' => (int) $it['pr_id'], 'pr_id' => (int) $it['pr_id'], 'tour_id' => (string) $it['tour_id'], 'manual_id' => (string) $it['manual_id'], 'basis' => $basis, 'usd_amount' => $it['usd_amount'] ?? null];
            }
        } else {
            $c = $db->query("SELECT manual_id FROM consignee WHERE tour_id = ? LIMIT 1", [(string) $v['ref_po']])->getRowArray();
            $lines[] = ['key' => 0, 'pr_id' => null, 'tour_id' => (string) $v['ref_po'], 'manual_id' => (string) ($c['manual_id'] ?? $v['ref_po']), 'basis' => (float) $v['Amount'], 'usd_amount' => $v['doller_amt']];
        }
        return $lines;
    }

    /**
     * Posts the bank charges of a paid voucher, ONE BALANCED PAIR PER TOUR LINE: Dr Bank Charges (the line's share) / Cr the
     * Avenir paying account (the same share), each pair carrying its tour (Invoice_Num / index_no), so that the paying
     * account's ledger shows the payment credits (posted by the chain's authorisation step) plus the charge credits = the
     * amount the bank actually debited. Done ONCE per voucher (tpd_bank_approval.charges_posted); refused when the mapping
     * is incomplete; nothing at all is written for charges of 0.00.
     */
    public static function postCharges($db, int $vnum, array $lines, float $charges, string $by): array
    {
        $charges = round($charges, 2);
        if ($charges <= 0) {
            return ['ok' => true, 'message' => 'no bank charges to post', 'entries' => [], 'lines' => [], 'posted' => false];
        }
        $map = self::chargeMapping($db);
        if (!$map['complete']) {
            return ['ok' => false, 'message' => $map['warning'], 'entries' => [], 'lines' => [], 'posted' => false];
        }
        $v = $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        if (!$v) {
            return ['ok' => false, 'message' => 'Voucher not found.', 'entries' => [], 'lines' => [], 'posted' => false];
        }
        $app = $db->query("SELECT charges_posted, charge_entries FROM tpd_bank_approval WHERE vnum = ?", [$vnum])->getRowArray();
        if ($app && (int) $app['charges_posted'] === 1) {
            return ['ok' => true, 'message' => 'already posted', 'entries' => array_filter(explode(',', (string) $app['charge_entries'])), 'lines' => [], 'posted' => false];
        }
        $bank = self::ledgerAccount($db, (string) $v['Bank_acc']);
        if (!$bank) {
            return ['ok' => false, 'message' => 'The paying account\'s ledger account (' . $v['Bank_acc'] . ') is not an active ledger account.', 'entries' => [], 'lines' => [], 'posted' => false];
        }
        $shares = self::allocate($lines, $charges);
        $inv = new \App\Models\InvoiceNumberModel();
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $entries = [];
        $detail = [];
        $total = 0.0;
        foreach ($lines as $l) {
            $share = round((float) ($shares[$l['key']] ?? 0), 2);
            if ($share <= 0) {
                continue;
            }
            $accId = $inv->incrementAccId();
            $desc = 'Bank charges Payment Voucher No. ' . $vnum . ' / ' . $l['manual_id'] . (count($lines) > 1 ? ' (share ' . number_format($share, 2) . ' of ' . number_format($charges, 2) . ')' : '');
            $rows = [['L_Code' => $map['account']['code'], 'Dr' => $share, 'Cr' => 0.00], ['L_Code' => $bank['code'], 'Dr' => 0.00, 'Cr' => $share]];   // Dr Bank Charges / Cr the paying account
            $batchRows = [];
            foreach ($rows as $r) {
                $batchRows[] = ['ID' => $accId, 'L_Code' => $r['L_Code'], 'Date' => $today, 'Discription' => $desc, 'Dr' => $r['Dr'], 'Cr' => $r['Cr'], 'Invoice_Num' => (string) $l['tour_id'], 'Linkacc' => $bank['code'],
                    'index_no' => (string) $l['tour_id'], 'job' => $map['account']['code'], 'advances_pay' => self::AP_CHARGES, 'fine_date' => $today, 'Dt' => $now, 'Cashier' => $by, 'payee' => (string) $v['S_name']];
            }
            $db->table('account_runing')->insertBatch($batchRows);
            if (!empty($l['pr_id'])) {
                $db->query("UPDATE tpd_request_ext SET charge_share = ?, charge_entry_id = ? WHERE pr_id = ?", [$share, $accId, (int) $l['pr_id']]);
            }
            $entries[] = $accId;
            $total += $share;
            $detail[] = ['tour' => $l['manual_id'], 'share' => $share, 'account' => $map['account']['code'] . ' ' . $map['account']['name'], 'bank' => $bank['code'] . ' ' . $bank['name'], 'entry' => $accId, 'posting' => 'Dr Bank Charges / Cr paying account'];
        }
        $db->query("UPDATE tpd_bank_approval SET charges_posted = 1, charge_entries = ? WHERE vnum = ?", [implode(',', $entries), $vnum]);
        Audit::log($db, ['action' => 'charges_posted', 'user' => $by, 'tour_id' => (string) $v['ref_po'], 'manual_id' => implode(', ', array_unique(array_column($lines, 'manual_id'))), 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum,
            'bank_account' => (string) $v['Bank'], 'new' => ['payment_voucher_no' => $vnum, 'charges' => round($total, 2), 'charges_account' => $map['account']['code'] . ' ' . $map['account']['name'], 'paying_account_ledger' => $bank['code'] . ' ' . $bank['name'], 'lines' => $detail]]);
        return ['ok' => true, 'message' => count($entries) . ' balanced posting(s), LKR ' . number_format($total, 2), 'entries' => $entries, 'lines' => $detail, 'posted' => true];
    }

    /**
     * The ledger of a paid voucher, read back from account_runing and checked against the Paid lock: the payment credits to
     * the paying account (the chain's authorisation rows, Recpt_No = the voucher number), the bank-charge pairs and the
     * exchange-difference pairs (by the entry ids kept on the lock). Reconciled = every entry balances (Dr = Cr), the credits
     * to the paying account equal the total the bank debited (final LKR + charges), the charges were posted exactly once.
     */
    public static function reconcile($db, int $vnum): ?array
    {
        $v = $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        $app = $db->query("SELECT * FROM tpd_bank_approval WHERE vnum = ?", [$vnum])->getRowArray();
        if (!$v || !$app) {
            return null;
        }
        $bank = (string) $v['Bank_acc'];
        $names = [];
        $name = static function ($code) use ($db, &$names) {
            if (!isset($names[$code])) {
                $a = $db->query("SELECT PName FROM account_posting WHERE L_Code = ? LIMIT 1", [$code])->getRowArray();
                $names[$code] = $a ? (string) $a['PName'] : '';
            }
            return $names[$code];
        };
        $rows = [];
        $pay = $db->query("SELECT ID, L_Code, Dr, Cr, Discription, Date, Invoice_Num FROM account_runing WHERE Recpt_No = ? AND Discription LIKE 'VOUCHER PAYMENT%' ORDER BY ID, Dr DESC", [$vnum])->getResultArray();
        foreach ($pay as $r) {
            $rows[] = ['group' => 'payment', 'entry' => (int) $r['ID'], 'account' => $r['L_Code'], 'name' => $name($r['L_Code']), 'dr' => (float) $r['Dr'], 'cr' => (float) $r['Cr'], 'tour' => $r['Invoice_Num'], 'description' => $r['Discription'], 'date' => $r['Date']];
        }
        foreach (['charges' => $app['charge_entries'] ?? '', 'fx' => $app['fx_entries'] ?? ''] as $grp => $ids) {
            $ids = array_values(array_filter(array_map('intval', explode(',', (string) $ids))));
            if (!$ids) {
                continue;
            }
            $q = $db->query("SELECT ID, L_Code, Dr, Cr, Discription, Date, Invoice_Num FROM account_runing WHERE ID IN (" . implode(',', $ids) . ") ORDER BY ID, Dr DESC")->getResultArray();
            foreach ($q as $r) {
                $rows[] = ['group' => $grp, 'entry' => (int) $r['ID'], 'account' => $r['L_Code'], 'name' => $name($r['L_Code']), 'dr' => (float) $r['Dr'], 'cr' => (float) $r['Cr'], 'tour' => $r['Invoice_Num'], 'description' => $r['Discription'], 'date' => $r['Date']];
            }
        }
        $sum = static fn (string $grp, string $side, ?string $acct = null) => round(array_sum(array_map(static fn ($r) => $r[$side], array_filter($rows, static fn ($r) => $r['group'] === $grp && ($acct === null || $r['account'] === $acct)))), 2);
        $byEntry = [];
        foreach ($rows as $r) {
            $byEntry[$r['entry']] = ($byEntry[$r['entry']] ?? 0) + $r['dr'] - $r['cr'];
        }
        $unbalanced = array_keys(array_filter($byEntry, static fn ($d) => abs($d) > 0.005));
        $charges = (float) ($app['charges'] ?? 0);
        $lkr = (float) $app['lkr_amount'];
        $totalDebit = round($lkr + $charges, 2);
        $payCr = $sum('payment', 'cr', $bank);
        $chCr = $sum('charges', 'cr', $bank);
        $chDr = $sum('charges', 'dr');
        $bankCr = round($payCr + $chCr, 2);
        $checks = [
            ['what' => 'Payment credited to the paying account (' . $bank . ')', 'expected' => $lkr, 'actual' => $payCr, 'ok' => abs($payCr - $lkr) < 0.005],
            ['what' => 'Bank charges credited to the paying account', 'expected' => $charges, 'actual' => $chCr, 'ok' => abs($chCr - $charges) < 0.005],
            ['what' => 'Bank charges debited to the Bank Charges account (recorded once)', 'expected' => $charges, 'actual' => $chDr, 'ok' => abs($chDr - $charges) < 0.005 && ($charges <= 0 ? (int) $app['charges_posted'] === 0 : (int) $app['charges_posted'] === 1)],
            ['what' => 'Total credited to the paying account = total the bank debited', 'expected' => $totalDebit, 'actual' => $bankCr, 'ok' => abs($bankCr - $totalDebit) < 0.005],
            ['what' => 'Every ledger entry balances (Dr = Cr)', 'expected' => count($byEntry) . ' entries', 'actual' => count($unbalanced) ? 'unbalanced: ' . implode(',', $unbalanced) : 'all balanced', 'ok' => !$unbalanced && $byEntry],
        ];
        if ($app['fx_diff'] !== null && abs((float) $app['fx_diff']) >= 0.005) {
            $fxNet = round($sum('fx', 'dr') - $sum('fx', 'cr'), 2);
            $fxDr = $sum('fx', 'dr');
            $checks[] = ['what' => 'Exchange-rate difference posted (balanced pairs, ' . ((float) $app['fx_diff'] > 0 ? 'loss' : 'gain') . ')', 'expected' => abs((float) $app['fx_diff']), 'actual' => $fxDr, 'ok' => abs($fxDr - abs((float) $app['fx_diff'])) < 0.005 && abs($fxNet) < 0.005 && (int) $app['fx_posted'] === 1];
        }
        $ok = !in_array(false, array_column($checks, 'ok'), true);
        return ['vnum' => $vnum, 'bank_ledger' => $bank, 'bank_name' => $name($bank), 'rows' => $rows, 'checks' => $checks, 'reconciled' => $ok,
            'totals' => ['final_lkr' => $lkr, 'charges' => $charges, 'total_debit' => $totalDebit, 'bank_credit' => $bankCr, 'payment_credit' => $payCr, 'charge_credit' => $chCr, 'charge_debit' => $chDr,
                'dr' => round(array_sum(array_column($rows, 'dr')), 2), 'cr' => round(array_sum(array_column($rows, 'cr')), 2)]];
    }

    /** Writes the reconciliation result on the Paid lock (the bank credit found in the ledger, and whether it reconciles). */
    public static function recordReconciliation($db, int $vnum, string $by): ?array
    {
        $rec = self::reconcile($db, $vnum);
        if ($rec) {
            try {
                $db->query("UPDATE tpd_bank_approval SET ledger_bank_credit = ?, reconciled = ? WHERE vnum = ?", [$rec['totals']['bank_credit'], $rec['reconciled'] ? 1 : 0, $vnum]);
                Audit::log($db, ['action' => 'ledger_reconciled', 'user' => $by, 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'bank_account' => $rec['bank_ledger'] . ' ' . $rec['bank_name'],
                    'new' => ['payment_voucher_no' => $vnum, 'reconciled' => $rec['reconciled'], 'totals' => $rec['totals'], 'checks' => array_map(static fn ($c) => ($c['ok'] ? 'ok' : 'FAIL') . ' · ' . $c['what'] . ' · expected ' . (is_numeric($c['expected']) ? number_format((float) $c['expected'], 2) : $c['expected']) . ' · actual ' . (is_numeric($c['actual']) ? number_format((float) $c['actual'], 2) : $c['actual']), $rec['checks'])]]);
            } catch (\Throwable $e) {
            }
        }
        return $rec;
    }

    /**
     * Posts the exchange-rate difference of a paid batch to the ledger, ONE BALANCED PAIR PER TOUR LINE: a line whose
     * final LKR exceeds its provisional LKR (the rate rose) is Dr Realised Exchange Loss / Cr supplier; a line whose
     * final LKR is lower (the rate fell) is Dr supplier / Cr Realised Exchange Gain — each pair carrying its own tour
     * (Invoice_Num / index_no), so the supplier's ledger, credited at the approval figures and debited at the final
     * figures, nets to zero tour by tour. Done once per voucher; refused when the mapping is incomplete.
     */
    public static function postFx($db, int $vnum, array $items, array $fig, string $by): array
    {
        $map = self::fxMapping($db);
        if (!$map['complete']) {
            return ['ok' => false, 'message' => $map['warning'], 'entries' => []];
        }
        $v = $db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        if (!$v) {
            return ['ok' => false, 'message' => 'Voucher not found.', 'entries' => []];
        }
        $app = $db->query("SELECT fx_posted, fx_entries FROM tpd_bank_approval WHERE vnum = ?", [$vnum])->getRowArray();
        if ($app && (int) $app['fx_posted'] === 1) {
            return ['ok' => true, 'message' => 'already posted', 'entries' => array_filter(explode(',', (string) $app['fx_entries']))];
        }
        $inv = new \App\Models\InvoiceNumberModel();
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $entries = [];
        $detail = [];
        foreach ($items as $k => $it) {
            $line = $fig['lines'][$k] ?? null;
            $diff = $line && $line['fx_diff'] !== null ? round((float) $line['fx_diff'], 2) : 0.0;
            if ($it['currency'] !== 'USD' || abs($diff) < 0.005) {
                continue;
            }
            $acct = $diff > 0 ? $map['loss'] : $map['gain'];
            $accId = $inv->incrementAccId();
            $abs = round(abs($diff), 2);
            $desc = ($diff > 0 ? 'Exchange loss' : 'Exchange gain') . ' Payment Voucher No. ' . $vnum . ' / ' . $it['manual_id'] . ' (USD ' . number_format((float) $it['usd_amount'], 2) . ' @ ' . number_format((float) $it['approval_rate'], 2) . ' → ' . number_format((float) $fig['rate'], 2) . ')';
            $rows = $diff > 0
                ? [['L_Code' => $acct['code'], 'Dr' => $abs, 'Cr' => 0.00], ['L_Code' => (string) $v['Acc_No'], 'Dr' => 0.00, 'Cr' => $abs]]     // loss: Dr loss account / Cr supplier
                : [['L_Code' => (string) $v['Acc_No'], 'Dr' => $abs, 'Cr' => 0.00], ['L_Code' => $acct['code'], 'Dr' => 0.00, 'Cr' => $abs]];   // gain: Dr supplier / Cr gain account
            $batch = [];
            foreach ($rows as $r) {
                $batch[] = ['ID' => $accId, 'L_Code' => $r['L_Code'], 'Date' => $today, 'Discription' => $desc, 'Dr' => $r['Dr'], 'Cr' => $r['Cr'], 'Invoice_Num' => (string) $it['tour_id'], 'Linkacc' => (string) $v['Acc_No'],
                    'index_no' => (string) $it['tour_id'], 'job' => $acct['code'], 'advances_pay' => 23, 'fine_date' => $today, 'Dt' => $now, 'Cashier' => $by, 'dollarrate' => (float) $fig['rate'], 'dollar' => (float) $it['usd_amount']];
            }
            $db->table('account_runing')->insertBatch($batch);
            $db->query("UPDATE tpd_request_ext SET fx_entry_id = ? WHERE pr_id = ?", [$accId, $it['pr_id']]);
            $entries[] = $accId;
            $detail[] = ['tour' => $it['manual_id'], 'difference' => $diff, 'account' => $acct['code'] . ' ' . $acct['name'], 'entry' => $accId, 'posting' => $diff > 0 ? 'Dr loss / Cr supplier' : 'Dr supplier / Cr gain'];
        }
        $db->query("UPDATE tpd_bank_approval SET fx_posted = 1, fx_entries = ? WHERE vnum = ?", [implode(',', $entries), $vnum]);
        Audit::log($db, ['action' => 'fx_posted', 'user' => $by, 'tour_id' => (string) $v['ref_po'], 'manual_id' => implode(', ', array_unique(array_column($items, 'manual_id'))), 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum,
            'usd_rate' => $fig['rate'], 'new' => ['payment_voucher_no' => $vnum, 'total_difference' => $fig['fx_diff'], 'gain_account' => $map['gain']['code'] . ' ' . $map['gain']['name'], 'loss_account' => $map['loss']['code'] . ' ' . $map['loss']['name'], 'lines' => $detail]]);
        return ['ok' => true, 'message' => $entries ? count($entries) . ' balanced posting(s)' : 'no difference to post', 'entries' => $entries, 'lines' => $detail];
    }

    // =====================================================================
    //  tf-paydesk-r3 — the paying account: validated, and every change recorded
    // =====================================================================

    /** The Avenir account is fit to pay an LKR voucher: active, tied to an active ledger account, in LKR. */
    public static function validateAccount($db, ?array $acct): ?string
    {
        if (!$acct) {
            return 'Select the Avenir bank account.';
        }
        $a = $acct['account'] ?? null;
        if ($a && (int) $a['active'] !== 1) {
            return 'The account "' . $acct['label'] . '" is inactive on the Avenir Bank Account Master.';
        }
        if (empty($acct['ledger_code']) || !self::ledgerAccount($db, $acct['ledger_code'])) {
            return 'The account "' . $acct['label'] . '" is not tied to an active ledger account (' . ($acct['ledger_code'] ?: 'none') . '). Fix it on the Avenir Bank Account Master first.';
        }
        $cur = strtoupper((string) ($a['currency'] ?? 'LKR'));
        if ($cur !== 'LKR') {
            return 'The account "' . $acct['label'] . '" is a ' . $cur . ' account; this payment is made in LKR. Choose an LKR account.';
        }
        return null;
    }

    /** A change of the paying account between the steps: the original and the new account, who, when, why — in the audit record. */
    public static function recordAccountChange($db, int $vnum, ?array $batch, string $stage, ?array $prev, array $new, string $by, string $reason): void
    {
        $v = $db->query("SELECT ref_po FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        Audit::log($db, ['action' => 'bank_account_changed', 'user' => $by, 'tour_id' => (string) ($v['ref_po'] ?? ''), 'ref_type' => $batch ? 'batch' : 'voucher', 'ref_id' => $batch['batch_no'] ?? $vnum, 'vnum' => $vnum,
            'prev' => ['account' => $prev['label'] ?? null, 'ledger' => $prev['ledger_code'] ?? null, 'currency' => $prev['account']['currency'] ?? null],
            'new' => ['account' => $new['label'], 'ledger' => $new['ledger_code'], 'currency' => $new['account']['currency'] ?? 'LKR', 'stage' => $stage, 'revalidated' => 'active account · active ledger account · LKR'],
            'bank_account' => $new['label'], 'reason' => $reason]);
    }

    /** The account the voucher is paying from at this moment (the master account by ledger code, else the voucher's own words). */
    public static function currentAccount($db, array $v, ?array $batch = null, ?array $init = null): ?array
    {
        $id = $init['bank_account_id'] ?? ($batch['bank_account_id'] ?? null);
        $a = $id ? BankAccounts::get($db, (int) $id) : null;
        if (!$a || (string) $a['ledger_code'] !== (string) $v['Bank_acc']) {
            $a = BankAccounts::byLedger($db, $v['Bank_acc'] ?? null);
        }
        if ($a) {
            return ['id' => (int) $a['id'], 'ledger_code' => $a['ledger_code'], 'label' => $a['label'], 'bank_name' => $a['bank_name'], 'account' => $a];
        }
        return $v['Bank_acc'] ? ['id' => null, 'ledger_code' => (string) $v['Bank_acc'], 'label' => (string) $v['Bank'], 'bank_name' => (string) $v['Bank'], 'account' => null] : null;
    }

    // =====================================================================
    //  tf-paydesk-r3 — the supplier payments of one tour, for the Complete Tour Record
    // =====================================================================

    /** Every payment voucher of a tour with its official Payment Voucher No., batch, figures and state. */
    public static function tourPayments($db, string $tourId): array
    {
        try {
            $rows = $db->query("SELECT x.pr_id, x.batch_id, x.voucher_vnum, x.currency, x.amount, x.approval_rate, x.approval_lkr, x.paid_rate, x.paid_lkr, x.paid_date, x.fx_diff, x.invoice_no, x.approved_by, x.approved_at, x.type,
                                       x.charge_share, x.charge_entry_id, x.fx_entry_id,
                                       pr.hotel_name, pr.requestAmount, pr.payment_request, pr.payment_request_user, pr.payment_request_date,
                                       b.batch_no, b.individual, b.bypass_reason, b.item_count, b.tour_count,
                                       v.Amount AS voucher_amount, v.Aflag, v.finalized, v.bank_date, v.Bank,
                                       a.bank_ref, a.approved_by AS paid_by, a.charges, a.total_debit, a.fx_posted, a.charges_posted, a.reconciled, r.status AS remit_status, r.sent_at, r.to_email
                                FROM tpd_request_ext x JOIN payment_request pr ON pr.id = x.pr_id
                                LEFT JOIN tpd_batch b ON b.id = x.batch_id LEFT JOIN voucher v ON v.Vnum = x.voucher_vnum
                                LEFT JOIN tpd_bank_approval a ON a.vnum = x.voucher_vnum LEFT JOIN tpd_remittance r ON r.vnum = x.voucher_vnum
                                WHERE x.tour_id = ? AND pr.payment_request NOT IN (3,5) ORDER BY x.pr_id", [$tourId])->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($rows as &$r) {
            $r['stage'] = $r['voucher_vnum'] ? (PayDesk::isCancelled($r) ? 'reversed' : ((int) $r['finalized'] === 1 ? ($r['remit_status'] === 'sent' ? 'notified' : 'paid') : 'voucher_issued')) : (in_array((int) $r['payment_request'], [4, 6], true) ? 'approved' : 'awaiting');
            $r['stage_label'] = PayDesk::STATUS[$r['stage']][1] ?? ucfirst($r['stage']);
            $r['bypass_label'] = $r['bypass_reason'] ? (self::BYPASS[$r['bypass_reason']] ?? $r['bypass_reason']) : null;
            // tf-paydesk-r4: this tour's share of the bank charges and the total the bank debited for this tour's line
            $r['charge_share'] = $r['charge_share'] !== null ? (float) $r['charge_share'] : null;
            $r['tour_total_debit'] = $r['paid_lkr'] !== null ? round((float) $r['paid_lkr'] + (float) ($r['charge_share'] ?? 0), 2) : null;
        }
        unset($r);
        return $rows;
    }

    public static function setStatus($db, int $batchId, string $status): void
    {
        try {
            $db->query("UPDATE tpd_batch SET status = ?, updated_at = ? WHERE id = ?", [$status, date('Y-m-d H:i:s'), $batchId]);
        } catch (\Throwable $e) {
        }
    }
}
