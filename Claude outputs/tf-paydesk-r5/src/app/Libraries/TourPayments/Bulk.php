<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r5 — bulk supplier payment: ONE bank transfer, ONE voucher PER ITEM, all under one bulk batch.
 *
 * Model: a parent tpd_batch row (mode bulk_parent, vnum NULL, batch number SB-xxxxx) owns one single-voucher child batch
 * per item (mode bulk_child, parent_id = the parent). Every child is an ordinary r4 batch with its own voucher, so the
 * per-voucher pages, documents, initiation, Paid, FX and charge postings and reconciliation work unchanged; the bulk
 * endpoints run them for every child under the parent's lock, resumably, and roll the results up to the parent.
 *
 * Issue is all-or-nothing: the vouchers (Vouchers::issue, one transaction) and the parent + child rows (written inside
 * that same transaction) exist together or not at all when the voucher tables are InnoDB; on MyISAM the run is
 * verified and undone by hand and the batch records that it was not transactional (engine_atomic = 0).
 */
final class Bulk
{
    public const REASONS = [
        'not_approved' => 'not approved', 'vouchered' => 'already vouchered', 'batched' => 'already in a batch', 'paid' => 'already paid',
        'cancelled_tour' => 'tour cancelled', 'on_hold' => 'on hold', 'disputed' => 'disputed', 'bank_unverified' => 'supplier bank not verified',
        'bank_changed' => 'supplier bank changed since verification', 'rate_missing' => 'USD item without an approval rate', 'other_currency' => 'other currency',
    ];

    /** Are the four voucher tables transactional here? */
    public static function atomic($db): bool
    {
        try {
            $rows = $db->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('voucher','v_inv','invoice_no','payment_request')")->getResultArray();
            return count($rows) === 4 && !array_filter($rows, static fn ($r) => strtoupper((string) $r['ENGINE']) !== 'INNODB');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * The supplier's approved items with an eligibility verdict each, grouped by currency.
     * ['supplier', 'sup_acc', 'bank', 'atomic', 'currencies' => [cur => ['eligible' => [...], 'excluded' => [...], 'total' => x, 'count' => n]]]
     */
    public static function eligibility($db, string $supAcc): array
    {
        $g = null;
        foreach (Batches::awaiting($db, $supAcc) as $grp) {   // awaiting() returns a list
            if ((string) $grp['sup_acc'] === $supAcc) {
                $g = $grp;
            }
        }
        $bank = SupplierBank::forPayment($db, $supAcc);
        $bankStatus = $bank['status'] ?? 'missing';
        $out = ['supplier' => $g['supplier'] ?? $supAcc, 'sup_acc' => $supAcc, 'bank' => $bank, 'atomic' => self::atomic($db), 'currencies' => new \stdClass()];
        if (!$g) {
            return $out;
        }
        $holds = [];
        try {
            foreach ($db->query("SELECT tour_id FROM hr2_payment_hold WHERE hotel_acc = ? AND status = 'awaiting'", [$supAcc])->getResultArray() as $h) {
                $holds[(string) $h['tour_id']] = true;
            }
        } catch (\Throwable $e) {
        }
        foreach ($g['items'] as $it) {
            $x = $db->query("SELECT disputed, dispute_reason, bulk_id FROM tpd_request_ext WHERE pr_id = ?", [(int) $it['pr_id']])->getRowArray() ?: [];
            $why = null;
            if (!in_array((int) $it['pr_status'], [4, 6], true)) {
                $why = 'not_approved';
            } elseif ($it['voucher_vnum']) {
                $why = 'vouchered';
            } elseif ($it['batch_id'] || !empty($x['bulk_id'])) {
                $why = 'batched';
            } elseif ($it['tour_cancelled']) {
                $why = 'cancelled_tour';
            } elseif ($it['type'] === 'Hotel' && isset($holds[$it['tour_id']])) {
                $why = 'on_hold';
            } elseif ((int) ($x['disputed'] ?? 0) === 1) {
                $why = 'disputed';
            } elseif ($it['currency'] === 'USD' && $it['rate_missing']) {
                $why = 'rate_missing';
            } elseif ($it['type'] !== 'Transport Advance' && $bankStatus === 'changed') {
                $why = 'bank_changed';
            } elseif ($it['type'] !== 'Transport Advance' && $bankStatus !== 'verified') {
                $why = 'bank_unverified';
            }
            $cur = $it['currency'];
            if ($out['currencies'] instanceof \stdClass) {
                $out['currencies'] = [];
            }
            $out['currencies'][$cur] = $out['currencies'][$cur] ?? ['eligible' => [], 'excluded' => [], 'total' => 0.0, 'provisional_lkr' => 0.0, 'count' => 0];
            $row = $it + ['eligible' => $why === null, 'reason' => $why, 'reason_label' => $why ? self::REASONS[$why] : null, 'dispute_reason' => $x['dispute_reason'] ?? null];
            if ($why === null) {
                $out['currencies'][$cur]['eligible'][] = $row;
                $out['currencies'][$cur]['total'] = round($out['currencies'][$cur]['total'] + (float) ($cur === 'USD' ? $it['usd_amount'] : $it['lkr_amount']), 2);
                $out['currencies'][$cur]['provisional_lkr'] = round($out['currencies'][$cur]['provisional_lkr'] + (float) ($it['approval_lkr'] ?? 0), 2);
                $out['currencies'][$cur]['count']++;
            } else {
                $out['currencies'][$cur]['excluded'][] = $row;
            }
        }
        return $out;
    }

    /** Mark / clear an item as disputed (excluded from bulk payment until cleared). */
    public static function dispute($db, int $prId, bool $disputed, string $reason, string $by): array
    {
        $x = $db->query("SELECT pr_id, batch_id, voucher_vnum FROM tpd_request_ext WHERE pr_id = ?", [$prId])->getRowArray();
        if (!$x) {
            PayDesk::recordRequest($db, $prId, []);
        } elseif ($x['batch_id'] || $x['voucher_vnum']) {
            return ['ok' => false, 'message' => 'This payment already has a voucher; dispute it through the voucher (cancellation), not here.'];
        }
        if ($disputed && trim($reason) === '') {
            return ['ok' => false, 'message' => 'Say why the item is disputed (kept in the audit record).'];
        }
        $db->query("UPDATE tpd_request_ext SET disputed = ?, dispute_reason = ?, disputed_by = ?, disputed_at = ? WHERE pr_id = ?", [$disputed ? 1 : 0, $disputed ? trim($reason) : null, $disputed ? $by : null, $disputed ? date('Y-m-d H:i:s') : null, $prId]);
        $pr = $db->query("SELECT tour_id, manual_id FROM payment_request WHERE id = ?", [$prId])->getRowArray() ?: [];
        Audit::log($db, ['action' => $disputed ? 'item_disputed' : 'dispute_cleared', 'user' => $by, 'tour_id' => $pr['tour_id'] ?? null, 'manual_id' => $pr['manual_id'] ?? null, 'ref_type' => 'request', 'ref_id' => $prId, 'reason' => trim($reason) ?: null]);
        return ['ok' => true];
    }

    /**
     * Create the bulk batch: N vouchers (one per item, consecutive numbers) + the parent and child batch rows, all in one
     * transaction. $prIds are re-validated against eligibility here (the client's selection is never trusted).
     */
    public static function create($db, string $supAcc, array $prIds, string $currency, array $acct, string $token, string $by, ?string $note, array $session): array
    {
        $prIds = array_values(array_unique(array_map('intval', $prIds)));
        if (!$prIds) {
            return ['ok' => false, 'status' => 400, 'message' => 'Select at least one eligible payment.'];
        }
        if (!preg_match('/^[A-Za-z0-9_-]{16,64}$/', $token)) {
            return ['ok' => false, 'status' => 400, 'message' => 'The confirmation token is missing. Reopen the confirmation and confirm again.'];
        }
        $dup = $db->query("SELECT id, batch_no FROM tpd_batch WHERE submit_token = ?", [$token])->getRowArray();
        if ($dup) {
            return ['ok' => true, 'status' => 200, 'duplicate' => true, 'batch_id' => (int) $dup['id'], 'batch_no' => $dup['batch_no'], 'message' => 'This confirmation was already processed as batch ' . $dup['batch_no'] . ' — nothing was created twice.'];
        }
        $el = self::eligibility($db, $supAcc);
        $cur = $el['currencies'][$currency] ?? null;
        if (!$cur) {
            return ['ok' => false, 'status' => 400, 'message' => 'No eligible ' . $currency . ' payments for this supplier.'];
        }
        $eligible = array_column($cur['eligible'], null, 'pr_id');
        $bad = [];
        foreach ($prIds as $id) {
            if (!isset($eligible[$id])) {
                $ex = null;
                foreach ($el['currencies'] as $c => $grp) {
                    foreach ($grp['excluded'] as $e) {
                        if ((int) $e['pr_id'] === $id) {
                            $ex = $e['reason_label'];
                        }
                    }
                    foreach ($grp['eligible'] as $e) {
                        if ((int) $e['pr_id'] === $id && $c !== $currency) {
                            $ex = self::REASONS['other_currency'];
                        }
                    }
                }
                $bad[] = '#' . $id . ' (' . ($ex ?: 'not in this supplier\'s queue') . ')';
            }
        }
        if ($bad) {
            return ['ok' => false, 'status' => 409, 'message' => 'Not eligible for this bulk payment: ' . implode(', ', $bad) . '. Nothing was created.'];
        }
        $prep = Batches::prepare($db, $supAcc, $prIds, false);
        if (!$prep['ok']) {
            return ['ok' => false, 'status' => 400, 'message' => $prep['message']];
        }
        if ($prep['currency'] !== $currency) {
            return ['ok' => false, 'status' => 409, 'message' => 'A bulk batch is one currency; the selection mixes currencies. Nothing was created.'];
        }
        if ($why = Batches::validateAccount($db, $acct)) {
            return ['ok' => false, 'status' => 400, 'message' => $why];
        }
        $rates = [];
        $amounts = [];
        $usd = [];
        $byId = [];
        foreach ($prep['items'] as $it) {
            $byId[$it['pr_id']] = $it;
            if ($it['currency'] === 'USD') {
                $rates[$it['pr_id']] = (string) $it['approval_rate'];
                $amounts[$it['pr_id']] = (string) $it['approval_lkr'];
                if ($it['type'] !== 'Hotel') {
                    $usd[$it['pr_id']] = (string) $it['usd_amount'];
                }
            } else {
                $amounts[$it['pr_id']] = (string) $it['lkr_amount'];
            }
        }
        $reqRows = $db->query("SELECT * FROM payment_request WHERE id IN (" . implode(',', $prep['pr_ids']) . ") ORDER BY id ASC")->getResultArray();
        $atomic = self::atomic($db);
        $now = date('Y-m-d H:i:s');
        $parent = null;
        $within = function ($db, array $vnums) use (&$parent, $prep, $acct, $note, $by, $token, $now, $currency, $reqRows, $byId, $atomic) {
            // the parent row
            $db->table('tpd_batch')->insert(['sup_acc' => $prep['sup_acc'], 'supplier_name' => $prep['supplier'], 'currency' => $currency, 'usd_total' => $prep['usd_total'] > 0 ? $prep['usd_total'] : null,
                'approval_lkr_total' => $prep['approval_lkr_total'], 'item_count' => $prep['item_count'], 'tour_count' => $prep['tour_count'],
                'bank_account_id' => $acct['id'], 'ledger_code' => $acct['ledger_code'], 'suggested_bank_account_id' => $acct['id'], 'suggested_ledger_code' => $acct['ledger_code'],
                'vnum' => null, 'status' => 'issued', 'note' => $note ?: null, 'created_by' => $by, 'created_at' => $now,
                'mode' => 'bulk_parent', 'voucher_count' => count($vnums), 'transfer_total' => $currency === 'USD' ? $prep['usd_total'] : $prep['lkr_total'],
                'submit_token' => $token, 'writer' => 'atomic', 'engine_atomic' => $atomic ? 1 : 0, 'selected_by' => $by, 'selected_at' => $now, 'confirmed_by' => $by, 'confirmed_at' => $now]);
            $pid = (int) $db->insertID();
            $no = 'SB-' . str_pad((string) $pid, 5, '0', STR_PAD_LEFT);
            $db->query("UPDATE tpd_batch SET batch_no = ? WHERE id = ?", [$no, $pid]);
            // one child batch per voucher, in the order the vouchers were issued (request id order)
            $children = [];
            foreach ($reqRows as $k => $req) {
                $it = $byId[(int) $req['id']];
                $childPrep = ['ok' => true, 'items' => [$it], 'pr_ids' => [(int) $req['id']], 'sup_acc' => $prep['sup_acc'], 'supplier' => $prep['supplier'], 'bank' => $prep['bank'],
                    'usd_total' => $it['currency'] === 'USD' ? round((float) $it['usd_amount'], 2) : 0.0, 'lkr_total' => $it['currency'] === 'LKR' ? round((float) $it['lkr_amount'], 2) : 0.0,
                    'approval_lkr_total' => round((float) ($it['approval_lkr'] ?? 0), 2), 'currency' => $it['currency'], 'tour_count' => 1, 'item_count' => 1];
                $child = Batches::finish($db, $childPrep, (int) $vnums[$k], $acct, null, $by, ['parent_id' => $pid, 'mode' => 'bulk_child', 'quiet' => true]);
                $db->query("UPDATE tpd_request_ext SET bulk_id = ? WHERE pr_id = ?", [$pid, (int) $req['id']]);
                $children[] = ['batch_id' => (int) $child['id'], 'batch_no' => $child['batch_no'], 'vnum' => (int) $vnums[$k], 'pr_id' => (int) $req['id'], 'tour' => $it['manual_id'], 'amount' => $it['currency'] === 'USD' ? $it['usd_amount'] : $it['lkr_amount']];
            }
            Audit::log($db, ['action' => 'bulk_issued', 'user' => $by, 'ref_type' => 'batch', 'ref_id' => $no, 'bank_account' => $acct['label'] ?? null, 'reason' => $note ?: null,
                'new' => ['batch' => $no, 'supplier' => $prep['supplier'], 'currency' => $currency, 'vouchers' => $vnums, 'first' => $vnums[0], 'last' => end($vnums), 'count' => count($vnums), 'requests' => $prep['pr_ids'],
                    'tours' => array_values(array_unique(array_column($prep['items'], 'manual_id'))), 'transfer_total' => $currency === 'USD' ? $prep['usd_total'] : $prep['lkr_total'], 'provisional_lkr' => $prep['approval_lkr_total'], 'atomic' => $atomic, 'token' => $token]]);
            $parent = ['id' => $pid, 'batch_no' => $no, 'children' => $children];
        };
        $res = Vouchers::issue($db, $reqRows, ['bank_acc' => $acct['ledger_code'], 'bank_name' => $acct['label'], 'rates' => $rates, 'amounts' => $amounts, 'usd' => $usd,
            'user_acc_no' => $session['acc_no'] ?? null, 'username' => $session['username'] ?? $by, 'mode' => 'per_item', 'within' => $within]);
        if (empty($res['ok'])) {
            if (!$atomic) {
                self::undo($db, $prep['pr_ids'], (array) ($res['vnums_attempted'] ?? []), (int) ($res['counter_before'] ?? 0));
            }
            Audit::log($db, ['action' => 'voucher_issue_failed', 'user' => $by, 'ref_type' => 'supplier', 'ref_id' => $supAcc, 'new' => ['pr_ids' => $prep['pr_ids'], 'writer' => 'atomic', 'atomic' => $atomic, 'token' => $token], 'reason' => $res['message']]);
            return ['ok' => false, 'status' => 409, 'message' => $res['message'] . ($atomic ? '' : ' (The voucher tables are not transactional on this install; the rows this run wrote were removed by hand — check the audit record.)')];
        }
        return ['ok' => true, 'status' => 200, 'batch_id' => $parent['id'], 'batch_no' => $parent['batch_no'], 'vnums' => $res['vnums'], 'children' => $parent['children'], 'atomic' => $atomic,
            'message' => count($res['vnums']) . ' vouchers issued (Payment Voucher No. ' . $res['vnums'][0] . '–' . end($res['vnums']) . ') under bulk batch ' . $parent['batch_no'] . ' for ' . $prep['supplier'] . ' — one bank transfer of ' . $currency . ' ' . number_format((float) ($currency === 'USD' ? $prep['usd_total'] : $prep['lkr_total']), 2) . '. Suggested account: ' . $acct['label'] . ' — the accountant confirms the actual paying account at initiation.'];
    }

    /** Non-transactional tables: take back what a failed run wrote (its own vouchers, lines, flags, counter) — verified afterwards. */
    private static function undo($db, array $prIds, array $vnums, int $counterBefore): void
    {
        try {
            if ($vnums) {
                $in = implode(',', array_map('intval', $vnums));
                $db->query("DELETE FROM v_inv WHERE V_tkn IN ({$in})");
                $db->query("DELETE FROM voucher WHERE Vnum IN ({$in})");
                $db->query("DELETE FROM tpd_batch WHERE vnum IN ({$in}) AND mode = 'bulk_child'");
            }
            if ($prIds) {
                $ids = implode(',', array_map('intval', $prIds));
                $db->query("UPDATE payment_request SET voucher_created = 0 WHERE id IN ({$ids})");
                $db->query("UPDATE tpd_request_ext SET batch_id = NULL, vinv_id = NULL, voucher_vnum = NULL, bulk_id = NULL WHERE pr_id IN ({$ids})");
            }
            if ($counterBefore > 0) {
                $db->query("UPDATE invoice_no SET V_Num = ?", [$counterBefore]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'tf-paydesk-r5 bulk undo: ' . $e->getMessage());
        }
    }

    /** The parent with its children and their state, totals and reconciliation. */
    public static function detail($db, int $id): ?array
    {
        $p = Batches::get($db, $id);
        if (!$p || ($p['mode'] ?? 'single') !== 'bulk_parent') {
            return null;
        }
        $kids = $db->query("SELECT b.*, v.Aflag, v.finalized, v.bank_date, v.Amount AS voucher_amount, v.ref_po, i.payment_date, i.usd_rate AS init_rate, i.charges AS init_charges, i.bank_ref AS init_ref, i.initiated_by,
                                   a.paid_date, a.usd_rate AS paid_rate, a.lkr_amount AS paid_lkr, a.fx_diff, a.charges, a.total_debit, a.approved_by AS paid_by, a.reconciled AS v_reconciled, a.ledger_bank_credit, a.fx_posted, a.charges_posted
                            FROM tpd_batch b LEFT JOIN voucher v ON v.Vnum = b.vnum LEFT JOIN tpd_bank_initiation i ON i.vnum = b.vnum LEFT JOIN tpd_bank_approval a ON a.vnum = b.vnum
                            WHERE b.parent_id = ? ORDER BY b.vnum", [$id])->getResultArray();
        $children = [];
        $n = ['issued' => 0, 'initiated' => 0, 'paid' => 0, 'cancelled' => 0, 'reconciled' => 0];
        $sum = ['voucher' => 0.0, 'paid_lkr' => 0.0, 'charges' => 0.0, 'total_debit' => 0.0, 'bank_credit' => 0.0, 'fx' => 0.0];
        foreach ($kids as $k) {
            $items = Batches::items($db, (int) $k['id']);
            $it = $items[0] ?? [];
            $stage = PayDesk::isCancelled($k) ? 'cancelled' : ((int) ($k['finalized'] ?? 0) === 1 ? 'paid' : ($k['payment_date'] || $k['initiated_by'] ? 'initiated' : 'issued'));
            $n[$stage]++;
            if ($stage === 'paid' && (int) ($k['v_reconciled'] ?? 0) === 1) {
                $n['reconciled']++;
            }
            $sum['voucher'] += (float) ($k['voucher_amount'] ?? 0);
            $sum['paid_lkr'] += (float) ($k['paid_lkr'] ?? 0);
            $sum['charges'] += (float) ($k['charges'] ?? $k['init_charges'] ?? 0);
            $sum['total_debit'] += (float) ($k['total_debit'] ?? 0);
            $sum['bank_credit'] += (float) ($k['ledger_bank_credit'] ?? 0);
            $sum['fx'] += (float) ($k['fx_diff'] ?? 0);
            $children[] = ['batch_id' => (int) $k['id'], 'batch_no' => $k['batch_no'], 'vnum' => (int) $k['vnum'], 'stage' => $stage, 'stage_label' => Batches::STAGES[$stage] ?? $stage,
                'pr_id' => $it['pr_id'] ?? null, 'tour_id' => $it['tour_id'] ?? $k['ref_po'], 'manual_id' => $it['manual_id'] ?? null, 'guest' => $it['guest'] ?? null, 'kind' => $it['kind'] ?? null, 'service_dates' => $it['service_dates'] ?? [], 'invoice_no' => $it['invoice_no'] ?? null,
                'currency' => $it['currency'] ?? $k['currency'], 'usd_amount' => $it['usd_amount'] ?? null, 'lkr_amount' => $it['lkr_amount'] ?? null, 'approval_rate' => $it['approval_rate'] ?? null, 'approval_lkr' => $it['approval_lkr'] ?? null,
                'voucher_amount' => $k['voucher_amount'] !== null ? (float) $k['voucher_amount'] : null, 'payment_date' => $k['payment_date'], 'init_rate' => $k['init_rate'], 'bank_ref' => $k['init_ref'],
                'paid_date' => $k['paid_date'], 'paid_rate' => $k['paid_rate'], 'paid_lkr' => $k['paid_lkr'] !== null ? (float) $k['paid_lkr'] : null, 'fx_diff' => $k['fx_diff'] !== null ? (float) $k['fx_diff'] : null,
                'charge_share' => $k['charges'] !== null ? (float) $k['charges'] : ($k['init_charges'] !== null ? (float) $k['init_charges'] : null), 'total_debit' => $k['total_debit'] !== null ? (float) $k['total_debit'] : null,
                'fx_posted' => (int) ($k['fx_posted'] ?? 0) === 1, 'charges_posted' => (int) ($k['charges_posted'] ?? 0) === 1, 'reconciled' => (int) ($k['v_reconciled'] ?? 0) === 1, 'ledger_bank_credit' => $k['ledger_bank_credit'] !== null ? (float) $k['ledger_bank_credit'] : null];
        }
        $count = count($children);
        // the stage over the LIVE vouchers (a voucher cancelled on the legacy screen leaves the batch incomplete for good:
        // the transfer recorded for the batch no longer matches the vouchers, so it is never shown as complete or balanced)
        $live = $count - $n['cancelled'];
        if ($count === 0) {
            $stage = 'empty';
        } elseif ($live === 0) {
            $stage = 'cancelled';
        } elseif ($n['paid'] === $live) {
            $stage = $n['cancelled'] > 0 ? 'paid_with_cancelled' : ((int) $p['reconciled'] === 1 ? 'paid' : 'paid_unreconciled');
        } elseif ($n['paid'] > 0 || $n['initiated'] > 0) {
            $stage = $n['initiated'] + $n['paid'] === $live ? ($n['paid'] > 0 ? 'paid_partial' : 'initiated') : 'initiated_partial';
        } else {
            $stage = 'issued';
        }
        $labels = ['issued' => 'Vouchers issued — in the accountant\'s queue', 'initiated' => 'Bank transfer initiated — awaiting bank approval', 'initiated_partial' => 'Bank initiation incomplete — re-run the initiation', 'paid_partial' => 'Paid incomplete — re-run Paid', 'paid_unreconciled' => 'Paid — ledger not yet balanced (re-run Paid)', 'paid' => 'Paid — ledger balanced over the batch', 'paid_with_cancelled' => 'Paid, but ' . $n['cancelled'] . ' voucher(s) cancelled — the batch does not balance; check the ledger and the bank', 'cancelled' => 'Cancelled', 'empty' => 'No vouchers'];
        $rem = $db->query("SELECT * FROM tpd_remittance WHERE batch_id = ? AND vnum IS NULL ORDER BY id DESC LIMIT 1", [$id])->getRowArray() ?: null;
        $audit = $db->query("SELECT * FROM tpd_audit WHERE (ref_type = 'batch' AND ref_id = ?) OR (vnum IN (SELECT vnum FROM tpd_batch WHERE parent_id = ?) AND action IN ('bank_initiated','paid','charges_posted','ledger_reconciled','fx_posted','voucher_cancelled')) ORDER BY id", [(string) $p['batch_no'], $id])->getResultArray();
        $acct = !empty($p['bank_account_id']) ? BankAccounts::get($db, (int) $p['bank_account_id']) : null;
        return ['batch' => $p, 'children' => $children, 'counts' => $n, 'count' => $count, 'stage' => $stage, 'stage_label' => $labels[$stage] ?? $stage,
            'totals' => ['voucher_total' => round($sum['voucher'], 2), 'transfer_total' => $p['transfer_total'] !== null ? (float) $p['transfer_total'] : null, 'transfer_lkr' => $p['transfer_lkr'] !== null ? (float) $p['transfer_lkr'] : null,
                'paid_lkr' => round($sum['paid_lkr'], 2), 'charges_total' => $p['charges_total'] !== null ? (float) $p['charges_total'] : null, 'charges_allocated' => round($sum['charges'], 2), 'total_debit' => round($sum['total_debit'], 2), 'bank_credit' => round($sum['bank_credit'], 2), 'fx_diff' => round($sum['fx'], 2),
                'vouchers_equal_transfer' => $p['transfer_total'] !== null && abs(round($sum['voucher'], 2) - (float) $p['transfer_total']) < 0.005 || $p['currency'] === 'USD'],
            'supplier' => SupplierBank::forPayment($db, (string) $p['sup_acc']), 'bank_account' => $acct, 'tours' => array_values(array_unique(array_filter(array_column($children, 'manual_id')))),
            'remittance' => $rem, 'audit' => $audit, 'atomic' => (int) ($p['engine_atomic'] ?? 0) === 1, 'is_usd' => $p['currency'] === 'USD', 'reconciled' => (int) $p['reconciled'] === 1];
    }

    /** The parent of a child voucher, if any. */
    public static function parentOfVnum($db, int $vnum): ?array
    {
        try {
            $c = $db->query("SELECT parent_id FROM tpd_batch WHERE vnum = ? AND parent_id IS NOT NULL LIMIT 1", [$vnum])->getRowArray();
            return $c ? Batches::get($db, (int) $c['parent_id']) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Charges of the one bank transfer allocated across the children pro rata to their LKR at the given rate (largest remainder, to the cent). */
    public static function allocateCharges($db, array $children, float $charges, ?float $rate): array
    {
        $lines = [];
        foreach ($children as $c) {
            $basis = $c['currency'] === 'USD' ? ($rate ? PayDesk::lkr((float) $c['usd_amount'], $rate) : (float) ($c['approval_lkr'] ?? 0)) : (float) $c['lkr_amount'];
            $lines[] = ['key' => (string) $c['vnum'], 'basis' => round((float) $basis, 2)];
        }
        return Batches::allocate($lines, $charges);
    }

    /** Σ of the children's reconciliations → the parent's verdict (ledger balanced over the batch); recorded on the parent. */
    public static function reconcile($db, int $id, string $by): array
    {
        $d = self::detail($db, $id);
        if (!$d) {
            return ['ok' => false, 'message' => 'Not a bulk batch.'];
        }
        $allPaid = $d['counts']['paid'] === $d['count'] && $d['count'] > 0;
        $allRec = $allPaid && $d['counts']['reconciled'] === $d['count'];
        $p = $d['batch'];
        $expected = round((float) ($p['transfer_lkr'] ?? $d['totals']['paid_lkr']) + (float) ($p['charges_total'] ?? 0), 2);
        $credit = $d['totals']['bank_credit'];
        $chargesOk = $p['charges_total'] === null || abs(round($d['totals']['charges_allocated'], 2) - (float) $p['charges_total']) < 0.005;
        $ok = $allRec && $chargesOk && abs($credit - $expected) < 0.005;
        $checks = [
            ['what' => 'Every voucher of the batch is Paid', 'ok' => $allPaid, 'detail' => $d['counts']['paid'] . ' of ' . $d['count']],
            ['what' => 'Every voucher\'s own ledger check passed (payment + FX + charges, Dr = Cr)', 'ok' => $allRec, 'detail' => $d['counts']['reconciled'] . ' of ' . $d['count']],
            ['what' => 'Bank-charge shares add back to the charges entered', 'ok' => $chargesOk, 'detail' => number_format($d['totals']['charges_allocated'], 2) . ' of ' . number_format((float) ($p['charges_total'] ?? 0), 2)],
            ['what' => 'Σ credits to the paying account over the batch = the one bank debit (transfer + charges)', 'ok' => abs($credit - $expected) < 0.005, 'detail' => number_format($credit, 2) . ' vs ' . number_format($expected, 2)],
        ];
        $db->query("UPDATE tpd_batch SET reconciled = ?, reconciled_at = ?, ledger_bank_credit = ?, total_debit = ?, charges_allocated = ?, updated_at = ? WHERE id = ?",
            [$ok ? 1 : 0, $ok ? date('Y-m-d H:i:s') : null, round($credit, 2), $expected, round($d['totals']['charges_allocated'], 2), date('Y-m-d H:i:s'), $id]);
        Audit::log($db, ['action' => $ok ? 'bulk_reconciled' : 'bulk_reconcile_failed', 'user' => $by, 'ref_type' => 'batch', 'ref_id' => $p['batch_no'], 'new' => ['checks' => $checks, 'bank_credit' => round($credit, 2), 'expected' => $expected]]);
        return ['ok' => $ok, 'checks' => $checks, 'bank_credit' => round($credit, 2), 'expected' => $expected, 'note' => 'Ledger balanced over the batch is NOT a bank-statement match; that is Bank Reconciliation Step 2.'];
    }
}
