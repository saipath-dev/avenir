<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r5 — the atomic voucher writer (Decision B), used ONLY by the Tour Payment Desk's bulk batch creation;
 * every legacy screen keeps calling the chain's own create_bulk_voucher.
 *
 * It writes exactly the rows the chain's create_bulk_voucher writes in batch mode (one `voucher` row, one `v_inv` line per
 * request, payment_request.voucher_created = 1, invoice_no.V_Num advanced) — same columns, same values, same conventions —
 * but inside ONE database transaction, under the voucher-issue lock, after checking every number it is about to use.
 *
 *   mode 'single'   : one voucher for all the items (the r4 batch behaviour) — used to compare with the legacy writer
 *   mode 'per_item' : one voucher per item, consecutive numbers, all-or-nothing (the r5 bulk behaviour)
 *
 * On any failure nothing remains (ROLLBACK) when the four tables are InnoDB; the caller is told why.
 */
final class Vouchers
{
    public const LOCK = 'tpd_voucher_issue';

    /**
     * @param array  $requests  payment_request rows (as the chain reads them), in id order
     * @param array  $opts      bank_acc, bank_name, rates[pr_id], amounts[pr_id] (LKR of the line), usd[pr_id], user_acc_no, username,
     *                          mode ('single'|'per_item'), now (datetime)
     * @return array ok, vnums[], message, counter_before, counter_after
     */
    public static function issue($db, array $requests, array $opts): array
    {
        $mode = $opts['mode'] ?? 'single';
        if (!$requests) {
            return ['ok' => false, 'message' => 'No requests.'];
        }
        foreach ($requests as $r) {
            if (!in_array((string) $r['payment_request'], ['4', '6'], true) || (int) $r['voucher_created'] === 1) {
                return ['ok' => false, 'message' => 'Not an approved request without a voucher: ' . $r['manual_id']];
            }
        }
        $accs = array_unique(array_column($requests, 'hotel_acc'));
        if (count($accs) !== 1) {
            return ['ok' => false, 'message' => 'One voucher run is one supplier.'];
        }
        $hotelAcc  = $requests[0]['hotel_acc'];
        $hotelName = $requests[0]['hotel_name'] ?: $requests[0]['consignee'];
        $rates = (array) ($opts['rates'] ?? []);
        $lkr   = (array) ($opts['amounts'] ?? []);
        $usd   = (array) ($opts['usd'] ?? []);
        $now   = $opts['now'] ?? date('Y-m-d H:i:s');
        $today = substr($now, 0, 10);

        // the lines exactly as the chain values them (batch mode)
        $lines = [];
        foreach ($requests as $req) {
            if ($req['type'] === 'Hotel') {
                $rate = (float) ($rates[$req['id']] ?? 0);
                if ($rate <= 0) {
                    return ['ok' => false, 'message' => 'No dollar rate for ' . $req['manual_id'] . '.'];
                }
                $amount = isset($lkr[$req['id']]) ? (float) $lkr[$req['id']] : (float) $req['requestAmount'] * $rate;
                $lines[] = ['req' => $req, 'amount' => round($amount, 2), 'rate' => $rate, 'usd' => (float) $req['requestAmount'], 'is_usd' => true, 'desc' => 'Hotel Reservation ' . $req['manual_id'] . ' / ' . $req['hotel_name']];
            } elseif ($req['type'] === 'Transport Advance') {
                $lines[] = ['req' => $req, 'amount' => round((float) $req['requestAmount'], 2), 'rate' => 0, 'usd' => 0.0, 'is_usd' => false, 'desc' => 'Transport Advance ' . $req['manual_id']];
            } else {
                $isUsd = !empty($usd[$req['id']]);
                $amount = isset($lkr[$req['id']]) ? (float) $lkr[$req['id']] : (float) $req['requestAmount'];
                $lines[] = ['req' => $req, 'amount' => round($amount, 2), 'rate' => $isUsd ? (float) ($rates[$req['id']] ?? 0) : 0, 'usd' => $isUsd ? (float) $usd[$req['id']] : 0.0, 'is_usd' => $isUsd, 'desc' => 'Lunch or Other Fee ' . $req['manual_id'] . ' / ' . $req['hotel_name']];
            }
        }
        $groups = $mode === 'per_item' ? array_map(static fn ($l) => [$l], $lines) : [$lines];
        $n = count($groups);

        $got = $db->query("SELECT GET_LOCK(?, 15) AS g", [self::LOCK])->getRowArray();
        if (!$got || (int) $got['g'] !== 1) {
            return ['ok' => false, 'message' => 'Another voucher is being issued at this moment. Try again in a few seconds.'];
        }
        $vnums = [];
        try {
            $counter = (int) ($db->query("SELECT V_Num FROM invoice_no LIMIT 1")->getRowArray()['V_Num'] ?? 0);
            $maxV = (int) ($db->query("SELECT MAX(Vnum) AS m FROM voucher")->getRowArray()['m'] ?? 0);
            // every number this run will use must be free — checked BEFORE anything is written
            $clash = $db->query("SELECT Vnum FROM voucher WHERE Vnum BETWEEN ? AND ? ORDER BY Vnum LIMIT 1", [$counter + 1, $counter + $n])->getRowArray();
            if ($clash) {
                return ['ok' => false, 'message' => 'Refused before writing: the voucher-number counter (invoice_no.V_Num = ' . $counter . ') is behind the voucher table (last Payment Voucher No. ' . $maxV . '); number ' . $clash['Vnum'] . ' is already issued. Accounts must bring the counter back in step (set invoice_no.V_Num to ' . $maxV . ').', 'counter_before' => $counter, 'max_vnum' => $maxV];
            }
            $db->transBegin();
            $k = 0;
            foreach ($groups as $g) {
                $k++;
                $vnum = $counter + $k;
                $total = round(array_sum(array_column($g, 'amount')), 2);
                $dollar = round(array_sum(array_column($g, 'usd')), 2);
                $isUsd = (bool) array_filter($g, static fn ($l) => $l['is_usd']);
                $first = $g[0]['req'];
                $db->table('voucher')->insert([
                    'Pay' => $hotelName, 'emp_code' => $hotelAcc, 'S_name' => $hotelName, 'Acc_No' => $hotelAcc, 'sup' => '1', 'type' => 'Bank Transfer',
                    'Amount' => $total, 'VAT' => '0', 'VAT_Amt' => '0', 'vat_m' => '0', 'nbt' => '0', 'nbt_amt' => '0', 'nbt_m' => '0', 'g_ttl' => $total,
                    'Address' => '0', 'print' => '0', 'des' => '0', 'dv' => '0', 'app_user' => $opts['user_acc_no'] ?? null, 'login' => $opts['username'] ?? null,
                    'Aflag' => '0', 'v_typ' => 'Bank Transfer', 'ref_po' => $first['tour_id'], 'insflag' => 1, 'inv' => '0', 'Vnum' => $vnum,
                    'Bank' => $opts['bank_name'] ?? '', 'Bank_acc' => $opts['bank_acc'] ?? '', 'C_Date' => $now, 'C_Number' => $first['tour_id'], 'W_Date' => $now,
                    'is_expenses' => 1, 'advances_pay' => $first['type'] === 'Transport Advance' ? 1 : 2, 'doller_amt' => $isUsd ? $dollar : $total,
                ]);
                if ($db->affectedRows() !== 1) {
                    throw new \RuntimeException('voucher row ' . $vnum . ' not written');
                }
                foreach ($g as $l) {
                    $req = $l['req'];
                    $db->table('v_inv')->insert([
                        'Date' => $now, 'Invoice' => $req['tour_id'], 'Description' => $l['desc'], 'Units' => 0, 'Rate' => $l['rate'], 'Amount' => $l['amount'], 'InvAmount' => $l['amount'],
                        'V_tkn' => $vnum, 'ityp' => '1', 'link_code' => $req['hotel_acc'], 'employee' => $hotelName, 'login' => $opts['username'] ?? null, 'INV_Date' => $today,
                        'Payee' => $hotelName, 'account' => $req['hotel_acc'],
                    ]);
                    if ($db->affectedRows() !== 1) {
                        throw new \RuntimeException('v_inv line of voucher ' . $vnum . ' not written');
                    }
                    $db->query("UPDATE payment_request SET voucher_created = 1 WHERE id = ? AND voucher_created = 0", [(int) $req['id']]);
                    if ($db->affectedRows() !== 1) {
                        throw new \RuntimeException('request #' . $req['id'] . ' was vouchered by someone else meanwhile');
                    }
                }
                $db->query("UPDATE invoice_no SET V_Num = ?", [$vnum]);
                $vnums[] = $vnum;
            }
            // re-read before commit: every number issued exactly once
            $cnt = (int) ($db->query("SELECT COUNT(*) AS c FROM voucher WHERE Vnum BETWEEN ? AND ?", [$counter + 1, $counter + $n])->getRowArray()['c'] ?? 0);
            if ($cnt !== $n) {
                throw new \RuntimeException('voucher count mismatch before commit: ' . $cnt . ' of ' . $n);
            }
            // the caller's own rows (batch records) are written inside the same transaction: with the vouchers or not at all
            if (!empty($opts['within']) && is_callable($opts['within'])) {
                ($opts['within'])($db, $vnums);
            }
            $db->transCommit();
            return ['ok' => true, 'vnums' => $vnums, 'counter_before' => $counter, 'counter_after' => $counter + $n, 'message' => $n . ' voucher(s) issued: ' . ($counter + 1) . ($n > 1 ? '–' . ($counter + $n) : '')];
        } catch (\Throwable $e) {
            try {
                $db->transRollback();
            } catch (\Throwable $x) {
            }
            return ['ok' => false, 'message' => 'Nothing issued: ' . $e->getMessage(), 'vnums_attempted' => $vnums, 'counter_before' => $counter ?? null];
        } finally {
            try {
                $db->query("SELECT RELEASE_LOCK(?)", [self::LOCK]);
            } catch (\Throwable $e) {
            }
        }
    }
}
