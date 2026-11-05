<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r7g (17 Oct 2026) — the currency of a payment voucher, established from its source records.
 *
 * voucher.doller_amt is NOT a currency marker. Several writers copy the LKR amount into it (Lunch Pay, the driver
 * settlement of accepted expenses, driver advances, the approval of a lunch / other / transport-advance request), while the
 * approval of a hotel request writes the USD amount there and the USD rate on the voucher line. Treating any doller_amt > 0
 * as USD made an LKR voucher read "USD 5,275.00 × 306.00 = LKR 5,275.00" (r7f F-2). This class decides the currency only
 * from records that say what the payment is:
 *
 *   1. a desk supplier batch (tpd_batch)                → the batch's currency
 *   2. the desk's request record(s) of the voucher       → their currency (tpd_request_ext.voucher_vnum), only when they
 *                                                          account for the voucher: the same supplier account, and amounts
 *                                                          that add up to its LKR amount (LKR) or its USD figure (USD)
 *   3. no foreign-currency amount on the voucher         → LKR (the voucher's amount is LKR and nothing else is recorded)
 *   4. the voucher's lines matched one by one to their source record, by tour, supplier and amount:
 *        "Hotel Reservation …"      a Hotel request, voucher_created, USD amount = line LKR ÷ the line's own rate (> 1),
 *                                   and the voucher's USD figure = the sum                                      → USD
 *        "Lunch or Other Fee …"     a non-hotel, non-advance request, voucher_created, LKR amount = line, rate 0 → LKR
 *        "Transport Advance <tour>" a Transport Advance request, voucher_created, or a driver-advance request /
 *                                   the tour's recorded advance for that driver, LKR amount = line, rate 0       → LKR
 *        "Lunch Payment …"          a lunch request completed by Lunch Pay, same supplier, LKR amount = line, rate 0 → LKR
 *        "Transport Payment Settled" the driver settlement (advances_pay 11) of a tour with that driver's log sheet → LKR
 *      and, for LKR, the voucher's doller_amt equal to its LKR amount (the copy those writers make).
 * Anything else — no source record, more than one currency, a supplier that differs from the source, a rate of 1, amounts that
 * do not agree — leaves the currency NOT established, with the reason; the desk then refuses to adopt or initiate the voucher.
 * Read-only: it never writes.
 */
final class Currency
{
    private const TOL = 0.01;

    /**
     * @return array{currency: ?string, established: bool, usd: ?float, rate: ?float, lkr: float, source: string, basis: array, reason: ?string}
     */
    public static function ofVoucher($db, array $v): array
    {
        $vnum = (int) ($v['Vnum'] ?? 0);
        $lkr = round((float) ($v['Amount'] ?? 0), 2);
        $usdRec = isset($v['doller_amt']) && $v['doller_amt'] !== null && (float) $v['doller_amt'] > 0 ? round((float) $v['doller_amt'], 2) : null;
        $out = static function (?string $cur, ?float $usd, ?float $rate, string $source, array $basis, ?string $why = null) use ($lkr): array {
            return ['currency' => $cur, 'established' => $cur !== null, 'usd' => $cur === 'USD' && $usd !== null ? round($usd, 2) : null,
                'rate' => $cur === 'USD' && $rate !== null ? round($rate, 4) : null, 'lkr' => $lkr, 'source' => $source, 'basis' => $basis, 'reason' => $cur === null ? $why : null];
        };
        if ($vnum <= 0) {
            return $out(null, null, null, 'none', [], 'no voucher number');
        }
        // 1. a desk supplier batch
        $b = null;
        try {
            $b = Batches::byVnum($db, $vnum);
        } catch (\Throwable $e) {
        }
        if ($b) {
            $cur = strtoupper((string) $b['currency']) === 'USD' ? 'USD' : 'LKR';
            $usd = null;
            if ($cur === 'USD') {
                $s = $db->query("SELECT COALESCE(SUM(amount), 0) AS s FROM tpd_request_ext WHERE batch_id = ? AND currency = 'USD'", [(int) $b['id']])->getRowArray();
                $usd = (float) ($s['s'] ?? 0) > 0 ? (float) $s['s'] : $usdRec;
            }
            return $out($cur, $usd, null, 'batch', ['supplier batch ' . $b['batch_no'] . ' is in ' . $cur]);
        }
        // 2. the desk's request record(s) of the voucher. A link is evidence only when it accounts for the voucher — the same supplier
        //    account, and amounts that add up to the voucher's LKR amount (LKR) or to its USD figure (USD). A link that does not
        //    agree leaves the currency NOT established (r7g build 2: an approval could link an unrelated voucher of the same supplier
        //    and tour — PayDesk::recordApproval — and its request's currency was then taken for the voucher's).
        $ext = [];
        try {
            $ext = $db->query("SELECT x.pr_id, x.currency, x.amount, x.usd_rate, x.approval_rate, COALESCE(NULLIF(x.hotel_acc, ''), pr.hotel_acc) AS sup_acc, pr.requestAmount
                               FROM tpd_request_ext x LEFT JOIN payment_request pr ON pr.id = x.pr_id WHERE x.voucher_vnum = ?", [$vnum])->getResultArray();
        } catch (\Throwable $e) {
        }
        // tf-paydesk-r7h build 5.2 (D-3): a desk record whose disagreement with this voucher was reviewed (IS / is NOT the payment) is not
        // used as evidence of the voucher's currency; the voucher's own records decide. An unreviewed one still leaves it NOT established.
        if ($ext) {
            try {
                $rev = RequestLink::reviews($db, array_map(static fn ($x) => (int) $x['pr_id'], $ext));
                $ext = array_values(array_filter($ext, static fn ($x) => !in_array($rev[(int) $x['pr_id']][$vnum]['decision'] ?? '', ['is', 'not'], true)));
            } catch (\Throwable $e) {
            }
        }
        if ($ext) {
            $curs = array_values(array_unique(array_map(static fn ($x) => strtoupper((string) $x['currency']) === 'USD' ? 'USD' : 'LKR', $ext)));
            if (count($curs) !== 1) {
                return $out(null, null, null, 'request_record', [], 'its desk request records are in more than one currency (' . implode(', ', $curs) . ')');
            }
            $ids = implode(', ', array_map(static fn ($x) => '#' . $x['pr_id'], $ext));
            $vAcc = trim((string) ($v['Acc_No'] ?? ''));
            $others = array_values(array_unique(array_filter(array_map(static fn ($x) => trim((string) $x['sup_acc']), $ext), static fn ($a) => $a !== $vAcc)));
            if ($others) {
                return $out(null, null, null, 'request_record', [], 'its desk request record(s) ' . $ids . ' are for ' . implode(', ', $others) . ', but the voucher pays ' . $vAcc . ' — the link is not reliable');
            }
            $sum = round(array_sum(array_map(static fn ($x) => $x['amount'] !== null ? (float) $x['amount'] : (float) $x['requestAmount'], $ext)), 2);
            if ($curs[0] === 'LKR') {
                if (abs($sum - $lkr) > self::TOL) {
                    return $out(null, null, null, 'request_record', [], 'its desk request record(s) ' . $ids . ' (LKR ' . number_format($sum, 2) . ') do not account for the voucher\'s LKR ' . number_format($lkr, 2) . ' — the link is not reliable');
                }
                return $out('LKR', null, null, 'request_record', ['desk request record(s) ' . $ids . ' in LKR ' . number_format($sum, 2)]);
            }
            $usd = $sum;
            $rates = array_values(array_unique(array_filter(array_map(static fn ($x) => $x['usd_rate'] !== null ? round((float) $x['usd_rate'], 4) : null, $ext))));
            $rate = count($rates) === 1 && $rates[0] > 1 ? $rates[0] : ($usd > 0 ? round($lkr / $usd, 4) : null);
            if ($usd <= 0 || $rate === null || $rate <= 1) {
                return $out(null, null, null, 'request_record', [], 'its desk request record(s) ' . $ids . ' are in USD but give no USD amount and rate above 1 for the voucher\'s LKR ' . number_format($lkr, 2));
            }
            if ($usdRec !== null && abs($usd - $usdRec) > self::TOL) {
                return $out(null, null, null, 'request_record', [], 'its desk request record(s) ' . $ids . ' (USD ' . number_format($usd, 2) . ') do not equal the voucher\'s USD figure (' . number_format($usdRec, 2) . ') — the link is not reliable');
            }
            return $out('USD', $usd, $rate, 'request_record', ['desk request record(s) ' . $ids . ' in USD ' . number_format($usd, 2)]);
        }
        // 3. no foreign-currency amount recorded on the voucher
        if ($usdRec === null) {
            return $out('LKR', null, null, 'voucher', ['the voucher records no foreign-currency amount; its amount is LKR ' . number_format($lkr, 2)]);
        }
        // 4. the voucher's lines, each matched to its source record
        $lines = $db->query("SELECT ID, Description, Rate, Amount, Invoice, account, link_code, ref FROM v_inv WHERE V_tkn = ? ORDER BY ID", [$vnum])->getResultArray();
        if (!$lines) {
            return $out(null, null, null, 'lines', [], 'the voucher has a foreign-currency figure (' . number_format($usdRec, 2) . ') but no lines to trace it to a source record');
        }
        $acc = trim((string) ($v['Acc_No'] ?? ''));
        $tour = trim((string) ($v['ref_po'] ?? ''));
        $basis = [];
        $currencies = [];
        $usdSum = 0.0;
        $rates = [];
        foreach ($lines as $l) {
            $r = self::line($db, $v, $l, $acc, $tour);
            if ($r['currency'] === null) {
                return $out(null, null, null, 'lines', $basis, 'line ' . (int) $l['ID'] . ' ("' . mb_substr(trim((string) $l['Description']), 0, 60) . '"): ' . $r['reason']);
            }
            $currencies[$r['currency']] = true;
            $basis[] = $r['basis'];
            if ($r['currency'] === 'USD') {
                $usdSum += (float) $r['usd'];
                $rates[(string) $r['rate']] = (float) $r['rate'];
            }
        }
        if (count($currencies) !== 1) {
            return $out(null, null, null, 'lines', $basis, 'its lines are in more than one currency');
        }
        $cur = array_key_first($currencies);
        if ($cur === 'LKR') {
            if (abs($usdRec - $lkr) > self::TOL) {
                return $out(null, null, null, 'lines', $basis, 'the lines are LKR but the voucher\'s foreign-currency figure (' . number_format($usdRec, 2) . ') is not the copy of its LKR amount (' . number_format($lkr, 2) . ') those writers make');
            }
            return $out('LKR', null, null, 'lines', $basis);
        }
        if (abs(round($usdSum, 2) - $usdRec) > self::TOL) {
            return $out(null, null, null, 'lines', $basis, 'the USD of its lines (' . number_format($usdSum, 2) . ') does not equal the voucher\'s USD figure (' . number_format($usdRec, 2) . ')');
        }
        $rate = count($rates) === 1 ? (float) array_values($rates)[0] : round($lkr / $usdSum, 4);
        return $out('USD', $usdRec, $rate, 'lines', $basis);
    }

    /** One voucher line against its source record. */
    private static function line($db, array $v, array $l, string $acc, string $tour): array
    {
        $no = static fn (string $why): array => ['currency' => null, 'reason' => $why];
        $desc = trim((string) $l['Description']);
        $rate = round((float) $l['Rate'], 4);
        $amt = round((float) $l['Amount'], 2);
        $lineTour = trim((string) ($l['Invoice'] ?? '')) ?: $tour;
        $lineAcc = trim((string) ($l['account'] ?? '')) ?: $acc;
        if ($lineAcc !== $acc) {
            return $no('the line pays ' . $lineAcc . ' but the voucher pays ' . $acc);
        }
        $vn = (int) ($v['Vnum'] ?? 0);
        $req = static function (string $typeSql, array $args) use ($db, $lineTour, $acc, $vn): array {
            // tf-paydesk-r7h: a request already linked to ANOTHER voucher (its desk record, or a voucher line naming it) is that voucher's
            // source, never this one's — it is not taken as evidence here (r7h test: a batched request was cited for an unrelated voucher)
            // tf-paydesk-r7h build 5 (verification V-7): a request a review confirmed as paid by THIS voucher counts like voucher_created = 1
            $typeSql = str_replace('voucher_created = 1', "(voucher_created = 1 OR EXISTS (SELECT 1 FROM tpd_meta lc WHERE lc.k = CONCAT('payconf:', payment_request.id, ':', " . (int) $vn . ")))", $typeSql);
            return $db->query("SELECT id, type, hotel_acc, requestAmount, payment_request, voucher_created FROM payment_request WHERE tour_id = ? AND hotel_acc = ? AND " . $typeSql
                . " AND NOT EXISTS (SELECT 1 FROM tpd_request_ext lx WHERE lx.pr_id = payment_request.id AND lx.voucher_vnum IS NOT NULL AND lx.voucher_vnum <> ?)"
                . " AND NOT EXISTS (SELECT 1 FROM v_inv lr WHERE lr.ref = CONCAT('tpd:pr:', payment_request.id) AND lr.V_tkn <> ?)"
                // tf-paydesk-r7h build 5: nor a request a review confirmed as paid by another voucher
                . " AND NOT EXISTS (SELECT 1 FROM tpd_meta lm WHERE lm.k LIKE CONCAT('payconf:', payment_request.id, ':%') AND lm.k <> CONCAT('payconf:', payment_request.id, ':', ?)) ORDER BY id",
                array_merge([$lineTour, $acc], $args, [$vn, $vn, $vn]))->getResultArray();
        };
        if (str_starts_with($desc, 'Hotel Reservation')) {
            if ($rate <= 1) {
                return $no('a hotel line without a recorded exchange rate (rate ' . number_format($rate, 2) . '); a rate of 1 or none is not evidence of USD');
            }
            $usd = round($amt / $rate, 2);
            if (abs(round($usd * $rate, 2) - $amt) > 1.00) {
                return $no('its LKR ' . number_format($amt, 2) . ' is not a USD amount at its rate ' . number_format($rate, 2));
            }
            $m = $req("type = 'Hotel' AND voucher_created = 1 AND ABS(requestAmount - ?) < 0.01", [$usd]);
            if (!$m) {
                return $no('no approved hotel request of tour ' . $lineTour . ' for ' . $acc . ' for USD ' . number_format($usd, 2));
            }
            return ['currency' => 'USD', 'usd' => $usd, 'rate' => $rate, 'basis' => 'hotel request #' . $m[0]['id'] . ': USD ' . number_format($usd, 2) . ' at ' . number_format($rate, 2) . ' (rate recorded on the voucher line)'];
        }
        if ($rate > 0) {
            return $no('a line with an exchange rate (' . number_format($rate, 2) . ') that is not a hotel reservation');
        }
        if (str_starts_with($desc, 'Lunch or Other Fee')) {
            $m = $req("type NOT IN ('Hotel', 'Transport Advance') AND voucher_created = 1 AND ABS(requestAmount - ?) < 0.01", [$amt]);
            return $m ? ['currency' => 'LKR', 'basis' => $m[0]['type'] . ' request #' . $m[0]['id'] . ': LKR ' . number_format($amt, 2)]
                      : $no('no approved lunch / other request of tour ' . $lineTour . ' for ' . $acc . ' for LKR ' . number_format($amt, 2));
        }
        if (str_starts_with($desc, 'Transport Advance Transfer')) {
            return $no('a transfer of an advance between drivers — no payment request or advance record stands behind it');
        }
        if (str_starts_with($desc, 'Transport Advance')) {
            $m = $req("type = 'Transport Advance' AND voucher_created = 1 AND ABS(requestAmount - ?) < 0.01", [$amt]);
            if ($m) {
                return ['currency' => 'LKR', 'basis' => 'transport advance request #' . $m[0]['id'] . ': LKR ' . number_format($amt, 2)];
            }
            $d = $db->query("SELECT id FROM driver_advance_request WHERE tour_id = ? AND driver = ? AND ABS(amount - ?) < 0.01 ORDER BY id LIMIT 1", [$lineTour, $acc, $amt])->getRowArray();
            if ($d) {
                return ['currency' => 'LKR', 'basis' => 'driver advance request #' . $d['id'] . ': LKR ' . number_format($amt, 2)];
            }
            $c = $db->query("SELECT tour_id FROM consignee WHERE tour_id = ? AND transport_agent = ? AND ABS(transport_advance - ?) < 0.01 LIMIT 1", [$lineTour, $acc, $amt])->getRowArray();
            return $c ? ['currency' => 'LKR', 'basis' => 'the tour\'s recorded transport advance for ' . $acc . ': LKR ' . number_format($amt, 2)]
                      : $no('no transport-advance request, driver advance request or recorded advance of tour ' . $lineTour . ' for ' . $acc . ' for LKR ' . number_format($amt, 2));
        }
        if (str_starts_with($desc, 'Lunch Payment')) {
            // tf-paydesk-r7h: a line that names its request (Lunch Pay from r7h on) is traced to that request only
            if (($pid = RequestLink::refOf($l['ref'] ?? null)) !== null) {
                $p = $db->query("SELECT id, tour_id, type, hotel_acc, requestAmount, payment_request FROM payment_request WHERE id = ?", [$pid])->getRowArray();
                if (!$p || (string) $p['type'] !== 'lunch' || (int) $p['payment_request'] !== 2 || trim((string) $p['hotel_acc']) !== $acc || (string) $p['tour_id'] !== $lineTour || abs(round((float) $p['requestAmount'], 2) - $amt) > self::TOL) {
                    return $no('the line names request #' . $pid . ', which is not a lunch request of tour ' . $lineTour . ' for ' . $acc . ' completed by Lunch Pay for LKR ' . number_format($amt, 2));
                }
                return ['currency' => 'LKR', 'basis' => 'lunch request #' . $pid . ' (named by the voucher line) paid by Lunch Pay: LKR ' . number_format($amt, 2)];
            }
            $m = $req("type = 'lunch' AND payment_request = 2 AND ABS(requestAmount - ?) < 0.01", [$amt]);
            if ($m) {
                return ['currency' => 'LKR', 'basis' => 'lunch request #' . $m[0]['id'] . ' paid by Lunch Pay: LKR ' . number_format($amt, 2)];
            }
            $o = $db->query("SELECT id, hotel_acc FROM payment_request WHERE tour_id = ? AND type = 'lunch' AND payment_request = 2 AND ABS(requestAmount - ?) < 0.01 ORDER BY id", [$lineTour, $amt])->getResultArray();
            return $no($o ? 'the lunch request' . (count($o) > 1 ? 's' : '') . ' of tour ' . $lineTour . ' for this amount (#' . implode(', #', array_column($o, 'id')) . ') ' . (count($o) > 1 ? 'are' : 'is') . ' for ' . implode(', ', array_unique(array_column($o, 'hotel_acc'))) . ', but the voucher pays ' . $acc . ' — correct the voucher before paying it'
                          : 'no lunch request of tour ' . $lineTour . ' completed by Lunch Pay for ' . $acc . ' for LKR ' . number_format($amt, 2));
        }
        if (str_starts_with($desc, 'Transport Payment Settled')) {
            if ((int) ($v['advances_pay'] ?? 0) !== 11) {
                return $no('a settlement line on a voucher that is not a driver settlement (advances_pay ' . (int) ($v['advances_pay'] ?? 0) . ')');
            }
            $s = $db->query("SELECT id FROM log_sheet_mile WHERE tour_id = ? AND transport_agent = ? ORDER BY id DESC LIMIT 1", [$lineTour, $acc])->getRowArray();
            return $s ? ['currency' => 'LKR', 'basis' => 'driver settlement of tour ' . $lineTour . ' computed in LKR from log sheet #' . $s['id'] . ', LKR advances and LKR expenses']
                      : $no('a driver settlement without the driver\'s log sheet for tour ' . $lineTour);
        }
        return $no('no known source for a line of this kind');
    }

    /** A short label for messages and pages: "LKR", "USD 430.00 at 325.00", or "not established — reason". */
    public static function label(array $c): string
    {
        if (!$c['established']) {
            return 'currency not established — ' . $c['reason'];
        }
        return $c['currency'] === 'USD' ? 'USD ' . number_format((float) $c['usd'], 2) . ($c['rate'] ? ' at ' . number_format((float) $c['rate'], 2) : '') : 'LKR';
    }
}
