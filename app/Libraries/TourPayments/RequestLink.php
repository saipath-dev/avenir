<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r7h (18 Oct 2026) — which payment request a voucher pays, and which voucher may already pay an approved request.
 *
 * Before r7h the voucher page took "the latest request of the same supplier and tour" (an adopted voucher could show another
 * request's amount, currency, requester, approver and notes — r7g D-1), and Supplier Payables hid an approved request whenever
 * the supplier had any other voucher on the same tour issued after its approval (r7g D-2). Both now go through this class.
 *
 * A voucher's request comes only from a STORED link:
 *   1. the desk supplier batch of the voucher (tpd_batch, tpd_request_ext.batch_id);
 *   2. the desk request record(s) naming the voucher (tpd_request_ext.voucher_vnum) — used only when their supplier and
 *      amount agree with the voucher; a record that does not agree is reported, not shown;
 *   3. the voucher line's own reference to its request (v_inv.ref = "tpd:pr:<id>", written by Lunch Pay from r7h on).
 * Without a stored link no request is shown. Requests of the same supplier, tour, kind AND amount are listed separately as
 * "possible source — not linked", never as the voucher's request.
 *
 * Supplier Payables (earlierPayments): an approved request leaves the list only when a voucher is linked to it (1–3), or —
 * for a request approved before the desk kept records (no desk approval record) — when a voucher of the same supplier and
 * tour, issued on or after its approval, carries the same amount (the former evidence of payment, now with the amount).
 * Any other voucher of the same supplier and tour never hides a request. When such a voucher could be its payment (same
 * amount for a desk-approved request; any amount for an older request) the request is shown HELD: it is kept out of every
 * batch (server side) until a holder of the batch privilege checks it and releases it with a reason (tpd_meta + audit).
 * Build 4: the Tour Payment Dashboard (PayDesk::storedLinks) pairs requests and vouchers through ofVoucher() as well.
 *
 * Build 5 (19 Oct 2026, r7h build-4 report N-2 and N-5):
 *   - a desk record that names a voucher which does not agree with it (supplier, amount) is not a link: the request stays visible in
 *     Supplier Payables, HELD with the reason, and every payment step refuses it until it is reviewed. The record is never cleared.
 *   - an older request (no desk approval record) matched to a voucher only by supplier, tour and date — even with the same amount —
 *     is an UNVERIFIED association: not taken as paid, held, and no further payment of it is allowed until it is reviewed.
 *   - a review is a holder of the batch privilege recording, once and with a reason, that the voucher IS the payment (it then counts
 *     as a stored link) or is NOT (it is no longer associated). Nothing else is written: no voucher, no ledger entry, no legacy table.
 * Read-only except the reviews (release(), confirm(), reviewLine()).
 */
final class RequestLink
{
    private const TOL = 0.01;
    public const REF_PREFIX = 'tpd:pr:';

    /** The request id a voucher line names (v_inv.ref = "tpd:pr:<id>"), or null. */
    public static function refOf($ref): ?int
    {
        $ref = trim((string) ($ref ?? ''));
        return preg_match('/^tpd:pr:(\d+)$/', $ref, $m) ? (int) $m[1] : null;
    }

    // =====================================================================
    //  The voucher page: the request(s) a voucher pays (stored links only)
    // =====================================================================

    /**
     * @return array{source: string, requests: array, request: ?array, ext: ?array, amounts: array, note: ?string, candidates: array}
     *   source: batch | desk | voucher_line | desk_rejected | line_rejected | none;  amounts: [pr_id => requestAmount()] of the linked requests
     * @param bool $withCandidates  false: the stored link only, no "possible source" search (the Tour Payment Dashboard, build 4)
     */
    public static function ofVoucher($db, array $v, bool $withCandidates = true): array
    {
        $vnum = (int) ($v['Vnum'] ?? 0);
        $out = ['source' => 'none', 'requests' => [], 'request' => null, 'ext' => null, 'amounts' => [], 'note' => null, 'candidates' => []];
        $amounts = static function (array $reqs, array $ext): array {
            $byId = [];
            foreach ($ext as $x) {
                $byId[(int) $x['pr_id']] = $x;
            }
            $a = [];
            foreach ($reqs as $r) {
                $a[(int) $r['id']] = self::requestAmount($r, $byId[(int) $r['id']] ?? null);
            }
            return $a;
        };
        if ($vnum <= 0) {
            return $out;
        }
        // 1. the desk supplier batch
        $b = null;
        try {
            $b = Batches::byVnum($db, $vnum);
        } catch (\Throwable $e) {
        }
        if ($b) {
            $ext = $db->query("SELECT * FROM tpd_request_ext WHERE batch_id = ? ORDER BY id", [(int) $b['id']])->getResultArray();
            $reqs = self::requests($db, array_map(static fn ($x) => (int) $x['pr_id'], $ext));
            $out['source'] = 'batch';
            $out['requests'] = $reqs;
            $out['amounts'] = $amounts($reqs, $ext);
            if (count($reqs) === 1) {
                $out['request'] = $reqs[0];
                $out['ext'] = $ext[0];
            } else {
                $out['note'] = 'This voucher pays ' . count($reqs) . ' requests of supplier batch ' . $b['batch_no'] . ' (listed below).';
            }
            return $out;
        }
        // 2. the desk request record(s) naming the voucher
        $ext = [];
        try {
            $ext = $db->query("SELECT * FROM tpd_request_ext WHERE voucher_vnum = ? ORDER BY id", [$vnum])->getResultArray();
        } catch (\Throwable $e) {
        }
        if ($ext) {
            $reqs = self::requests($db, array_map(static fn ($x) => (int) $x['pr_id'], $ext));
            $ids = implode(', ', array_map(static fn ($x) => '#' . (int) $x['pr_id'], $ext));
            $why = self::disagreement($v, $reqs, $ext);
            if ($why !== null) {
                // tf-paydesk-r7h build 5 (N-2): not this voucher's link; the request is held until a review confirms or rejects the voucher
                $rev = self::reviews($db, array_map(static fn ($x) => (int) $x['pr_id'], $ext));
                $conf = array_values(array_filter($reqs, static fn ($q) => ($rev[(int) $q['id']][$vnum]['decision'] ?? '') === 'is'));
                if ($conf) {
                    $c = $rev[(int) $conf[0]['id']][$vnum];
                    $out['source'] = 'review';
                    $out['requests'] = $conf;
                    $out['amounts'] = $amounts($conf, $ext);
                    $out['review'] = $c;
                    if (count($conf) === 1) {
                        $out['request'] = $conf[0];
                        foreach ($ext as $xx) {
                            if ((int) $xx['pr_id'] === (int) $conf[0]['id']) {
                                $out['ext'] = $xx;
                            }
                        }
                    }
                    $out['note'] = 'Linked by review: ' . $c['by'] . ' confirmed on ' . $c['at'] . ' that this voucher is the payment of request ' . implode(', ', array_map(static fn ($q) => '#' . (int) $q['id'], $conf))
                        . ' — ' . $c['reason'] . ' (its desk record did not agree: ' . $why . ').';
                    return $out;
                }
                $rel = array_values(array_filter($ext, static fn ($xx) => ($rev[(int) $xx['pr_id']][$vnum]['decision'] ?? '') === 'not'));
                $out['source'] = 'desk_rejected';
                $out['note'] = 'A desk record names request ' . $ids . ' for this voucher, but ' . $why . ' — it is not shown as this voucher\'s request.'
                    . ($rel ? ' Reviewed: ' . implode('; ', array_map(static function ($xx) use ($rev, $vnum) {
                        $d = $rev[(int) $xx['pr_id']][$vnum];
                        return 'not the payment of request #' . (int) $xx['pr_id'] . ' (' . $d['by'] . ', ' . $d['at'] . ' — ' . $d['reason'] . ')';
                    }, $rel)) . '.' : ' The request is held in Supplier Payables until a holder of the batch privilege reviews this voucher.');
                return $out;
            }
            $out['source'] = 'desk';
            $out['requests'] = $reqs;
            $out['amounts'] = $amounts($reqs, $ext);
            if (count($reqs) === 1) {
                $out['request'] = $reqs[0];
                $out['ext'] = $ext[0];
            } else {
                $out['note'] = 'This voucher pays ' . count($reqs) . ' requests: ' . $ids . '.';
            }
            return $out;
        }
        // 3. the voucher line's own reference
        $refIds = [];
        foreach ($db->query("SELECT ref FROM v_inv WHERE V_tkn = ?", [$vnum])->getResultArray() as $l) {
            if (($id = self::refOf($l['ref'])) !== null) {
                $refIds[$id] = true;
            }
        }
        if ($refIds) {
            $reqs = self::requests($db, array_keys($refIds));
            $ids = implode(', ', array_map(static fn ($id) => '#' . $id, array_keys($refIds)));
            $lx = [];
            try {
                if ($reqs) {
                    $lx = $db->query("SELECT * FROM tpd_request_ext WHERE pr_id IN (" . implode(',', array_map(static fn ($r) => (int) $r['id'], $reqs)) . ") ORDER BY pr_id")->getResultArray();
                }
            } catch (\Throwable $e) {
            }
            $why = count($reqs) !== count($refIds) ? 'that request no longer exists' : self::disagreement($v, $reqs, $lx);
            if ($why !== null) {
                $out['source'] = 'line_rejected';
                $out['note'] = 'The voucher line names request ' . $ids . ', but ' . $why . ' — it is not shown as this voucher\'s request.';
                return $out;
            }
            $out['source'] = 'voucher_line';
            $out['requests'] = $reqs;
            $out['amounts'] = $amounts($reqs, $lx);
            if (count($reqs) === 1) {
                $out['request'] = $reqs[0];
                $out['ext'] = $lx[0] ?? null;
            } else {
                $out['note'] = 'The voucher lines name ' . count($reqs) . ' requests: ' . $ids . '.';
            }
            return $out;
        }
        // 4. tf-paydesk-r7h build 5: a review that confirmed this voucher as a request's payment (no stored link existed)
        $confIds = [];
        try {
            foreach ($db->query("SELECT k, v FROM tpd_meta WHERE k LIKE ?", ['payconf:%:' . $vnum])->getResultArray() as $m) {
                if (preg_match('/^payconf:(\d+):(\d+)$/', (string) $m['k'], $mm) && (int) $mm[2] === $vnum
                    && !$db->query("SELECT k FROM tpd_meta WHERE k = ?", ['payrel:' . $mm[1] . ':' . $mm[2]])->getRowArray()) {   // V-5: not when conflicting
                    $confIds[(int) $mm[1]] = self::decoded($m['v']);
                }
            }
        } catch (\Throwable $e) {
        }
        if ($confIds) {
            $reqs = array_values(array_filter(self::requests($db, array_keys($confIds)), static fn ($q) => trim((string) $q['hotel_acc']) === trim((string) ($v['Acc_No'] ?? ''))));
            if ($reqs) {
                $lx = [];
                try {
                    $lx = $db->query("SELECT * FROM tpd_request_ext WHERE pr_id IN (" . implode(',', array_map(static fn ($q) => (int) $q['id'], $reqs)) . ") ORDER BY pr_id")->getResultArray();
                } catch (\Throwable $e) {
                }
                $c = $confIds[(int) $reqs[0]['id']];
                $out['source'] = 'review';
                $out['requests'] = $reqs;
                $out['amounts'] = $amounts($reqs, $lx);
                $out['review'] = $c;
                if (count($reqs) === 1) {
                    $out['request'] = $reqs[0];
                    $out['ext'] = $lx[0] ?? null;
                }
                $out['note'] = 'Linked by review: ' . $c['by'] . ' confirmed on ' . $c['at'] . ' that this voucher is the payment of request ' . implode(', ', array_map(static fn ($q) => '#' . (int) $q['id'], $reqs)) . ' — ' . $c['reason'] . '.';
                return $out;
            }
        }
        // none — possible payments are only listed (unverified)
        if (!$withCandidates) {
            $out['note'] = 'No payment request is linked to this voucher.';
            return $out;
        }
        $out['candidates'] = self::candidates($db, $v);
        $n = count($out['candidates']);
        $out['note'] = 'No payment request is linked to this voucher.' . ($n === 1
            ? ' Unverified possible payment of request #' . $out['candidates'][0]['id'] . ' (' . $out['candidates'][0]['label'] . '; ' . $out['candidates'][0]['basis'] . ') — not linked; it counts as paid only after a review.'
            : ($n > 1 ? ' It may be the payment of ' . $n . ' requests (' . implode(', ', array_map(static fn ($c) => '#' . $c['id'], $out['candidates'])) . ') — none is linked; each needs a review.' : ''))
            . self::reviewNote($db, $v);
        return $out;
    }

    /** The reviews of a voucher no request is linked to — "not the payment of request #N", and the Tour Payment Dashboard's line decision — as sentences, or ''. */
    private static function reviewNote($db, array $v): string
    {
        $vnum = (int) ($v['Vnum'] ?? 0);
        $tours = [];
        try {
            foreach ($db->query("SELECT DISTINCT Invoice FROM v_inv WHERE V_tkn = ?", [$vnum])->getResultArray() as $l) {
                if (trim((string) $l['Invoice']) !== '') {
                    $tours[trim((string) $l['Invoice'])] = true;
                }
            }
        } catch (\Throwable $e) {
        }
        if (trim((string) ($v['ref_po'] ?? '')) !== '') {
            $tours[trim((string) $v['ref_po'])] = true;
        }
        $names = ['driver_advance' => 'driver advance', 'driver_settlement' => 'driver settlement', 'hotel' => 'hotel stay', 'activity' => 'activity', 'expense' => 'expense', 'orphan' => 'request'];
        $out = [];
        // the requests a review recorded this voucher as NOT the payment of (it had been an unverified possible payment of them)
        try {
            foreach ($db->query("SELECT k, v FROM tpd_meta WHERE k LIKE ?", ['payrel:%:' . $vnum])->getResultArray() as $m) {
                if (preg_match('/^payrel:(\d+):(\d+)$/', (string) $m['k'], $mm) && (int) $mm[2] === $vnum) {
                    $d = self::decoded($m['v']);
                    $out[] = 'Reviewed: not the payment of request #' . (int) $mm[1] . ' (' . $d['by'] . ', ' . $d['at'] . ' — ' . $d['reason'] . ').';
                }
            }
        } catch (\Throwable $e) {
        }
        foreach (array_keys($tours) as $t) {
            $d = self::lineReviews($db, (string) $t)[$vnum] ?? null;
            if ($d) {
                $kind = explode(':', $d['line'])[0];
                $out[] = 'Reviewed on the Tour Payment Dashboard: ' . ($d['decision'] === 'is' ? 'IS' : 'is NOT') . ' a payment of the ' . ($names[$kind] ?? str_replace('_', ' ', $kind)) . ' line of tour ' . $t
                    . ' (' . $d['by'] . ', ' . $d['at'] . ' — ' . $d['reason'] . ').';
            }
        }
        return $out ? ' ' . implode(' ', $out) : '';
    }

    /** The currency and amount of a request as it was requested (a hotel request is USD, others LKR, unless its desk record says otherwise). */
    public static function requestAmount(?array $r, ?array $x): ?array
    {
        if (!$r) {
            return null;
        }
        $cur = $x && !empty($x['currency']) ? (strtoupper((string) $x['currency']) === 'USD' ? 'USD' : 'LKR') : ((string) $r['type'] === 'Hotel' ? 'USD' : 'LKR');
        $amt = $x && $x['amount'] !== null && $x['amount'] !== '' ? (float) $x['amount'] : (float) $r['requestAmount'];
        return ['currency' => $cur, 'amount' => round($amt, 2), 'label' => $cur . ' ' . number_format($amt, 2)];
    }

    // =====================================================================
    //  Supplier Payables: what may already pay an approved request
    // =====================================================================

    /**
     * What links, or may already pay, each request ($rows = payment_request rows of any status; $exts = [pr_id => tpd_request_ext row]).
     * Cancelled and rejected requests are left out. [pr_id => ['state' => linked|invalid|possible|released|other, 'kind' => string,
     *   'vnums' => [...], 'basis' => string, 'released' => [...], 'vouchers' => [...], 'others' => [...], 'review' => ?array]]
     * (no entry: no voucher is linked to it and none could be its payment).
     *   linked   — a STORED link: its supplier batch, a review that confirmed a voucher as its payment (build 5), its desk record when
     *              the voucher's supplier and amount agree, or a voucher line naming it. It is paid through that voucher.
     *   invalid  — build 5 (N-2): its desk record names a voucher that does not agree with it and nobody has reviewed that yet. The
     *              request is unpaid; it is shown HELD with the reason, and every payment step refuses it until the review.
     *   possible — a voucher that may be its payment and is linked to no other request, issued on or after its approval: for a
     *              desk-approved request one of the same supplier, tour and amount; for an older request (no desk approval record) any
     *              amount — build 5 (N-5): the same amount included, no longer taken as paid. An UNVERIFIED association: HELD until a
     *              holder of the batch privilege reviews it (IS / is NOT the payment). A request not yet approved (requested, on hold)
     *              is matched to no voucher.
     *   released — every such voucher was reviewed as not its payment (or its wrong desk record was): payable.
     *   other    — desk-approved request: only vouchers of another amount (shown for information; payable).
     */
    public static function earlierPayments($db, array $rows, array $exts): array
    {
        $out = [];
        $rows = array_values(array_filter($rows, static fn ($r) => !in_array((int) $r['payment_request'], [3, 5], true)));
        if (!$rows) {
            return $out;
        }
        $claimed = self::claims($db);   // vnum => [pr_id => source] — every voucher a request is linked to or recorded against
        $reviews = self::reviews($db, array_map(static fn ($r) => (int) $r['id'], $rows));
        $accs = array_values(array_unique(array_map(static fn ($r) => (string) $r['hotel_acc'], $rows)));
        $tours = array_values(array_unique(array_map(static fn ($r) => (string) $r['tour_id'], $rows)));
        $tourIn = implode(',', array_map([$db, 'escape'], $tours));
        $batchV = [];
        $bids = array_values(array_unique(array_filter(array_map(static fn ($x) => (int) ($x['batch_id'] ?? 0), $exts))));
        if ($bids) {
            try {
                foreach ($db->query("SELECT id, vnum FROM tpd_batch WHERE id IN (" . implode(',', $bids) . ") AND vnum IS NOT NULL")->getResultArray() as $b) {
                    $batchV[(int) $b['id']] = (int) $b['vnum'];
                }
            } catch (\Throwable $e) {
            }
        }
        $deskV = [];
        $dv = array_values(array_unique(array_filter(array_map(static fn ($x) => (int) ($x['voucher_vnum'] ?? 0), $exts))));
        if ($dv) {
            foreach ($db->query("SELECT Vnum, Acc_No, Amount, doller_amt, Aflag, C_Date, finalized FROM voucher WHERE Vnum IN (" . implode(',', $dv) . ")")->getResultArray() as $v) {
                $deskV[(int) $v['Vnum']] = $v;
            }
        }
        // the vouchers of these suppliers on these tours (not cancelled), oldest first
        $cands = $db->query("SELECT Vnum, Acc_No, advances_pay, Aflag, C_Date, ref_po, Amount, doller_amt, finalized FROM voucher WHERE Acc_No IN ("
            . implode(',', array_map([$db, 'escape'], $accs)) . ") AND advances_pay IN (1,2) AND Aflag NOT IN (2,3)
              AND (ref_po IN ({$tourIn}) OR Vnum IN (SELECT V_tkn FROM v_inv WHERE Invoice IN ({$tourIn}))) ORDER BY Vnum ASC")->getResultArray();
        $lines = [];   // vnum => [ [Invoice, Amount, Rate] ... ]  (lines of these tours)
        $refTo = [];   // vnum => [pr_id => true]                 (lines naming a request)
        if ($cands) {
            $vn = array_map(static fn ($v) => (int) $v['Vnum'], $cands);
            foreach (array_chunk($vn, 500) as $chunk) {
                foreach ($db->query("SELECT V_tkn, Invoice, Amount, Rate, ref FROM v_inv WHERE V_tkn IN (" . implode(',', $chunk) . ") AND (Invoice IN ({$tourIn}) OR ref LIKE 'tpd:pr:%')")->getResultArray() as $l) {
                    $k = (int) $l['V_tkn'];
                    if (($id = self::refOf($l['ref'])) !== null) {
                        $refTo[$k][$id] = true;
                    }
                    if (in_array((string) $l['Invoice'], $tours, true)) {
                        $lines[$k][] = $l;
                    }
                }
            }
        }
        $vinfo = [];   // vnum => what the pages show of it
        $label = static fn (array $v) => ['vnum' => (int) $v['Vnum'], 'date' => substr((string) $v['C_Date'], 0, 10), 'lkr' => round((float) $v['Amount'], 2), 'paid' => (int) $v['finalized'] === 1,
            'label' => 'Payment Voucher No. ' . (int) $v['Vnum'] . ' · ' . substr((string) $v['C_Date'], 0, 10) . ' · LKR ' . number_format((float) $v['Amount'], 2) . ((int) $v['finalized'] === 1 ? ' · Paid' : '')];
        foreach ($cands as $v) {
            $vinfo[(int) $v['Vnum']] = $label($v);
        }
        foreach ($deskV as $vn => $v) {
            $vinfo[$vn] = $vinfo[$vn] ?? $label($v);
        }
        foreach ($rows as $r) {
            $id = (int) $r['id'];
            $x = $exts[$id] ?? null;
            $rev = $reviews[$id] ?? [];
            $desk = $x && $x['approved_by'] !== null && $x['approved_by'] !== '';
            $info = static fn (array $vns): array => array_values(array_filter(array_map(static fn ($vn) => $vinfo[$vn] ?? null, $vns)));
            $relList = static function (array $vns) use ($rev): array {
                return array_values(array_map(static fn ($vn) => ['vnum' => $vn, 'by' => $rev[$vn]['by'], 'at' => $rev[$vn]['at'], 'reason' => $rev[$vn]['reason']], $vns));
            };
            // 1. its supplier batch
            if ($x && !empty($x['batch_id']) && isset($batchV[(int) $x['batch_id']])) {
                $bv = $batchV[(int) $x['batch_id']];
                $out[$id] = ['state' => 'linked', 'kind' => 'batch', 'vnums' => [$bv], 'basis' => 'its supplier batch', 'released' => [], 'vouchers' => $info([$bv]), 'others' => [], 'review' => null];
                continue;
            }
            // 2. a review that confirmed a voucher as its payment (tf-paydesk-r7h build 5)
            $conf = array_keys(array_filter($rev, static fn ($d) => $d['decision'] === 'is'));
            if ($conf) {
                $c = $rev[$conf[0]];
                $out[$id] = ['state' => 'linked', 'kind' => 'review', 'vnums' => $conf, 'basis' => 'confirmed by review (' . $c['by'] . ', ' . $c['at'] . ')', 'released' => [], 'vouchers' => $info($conf), 'others' => [], 'review' => $c];
                continue;
            }
            // 3. its desk record — when the voucher it names agrees (supplier, amount); otherwise held until reviewed (build 5, N-2)
            $invalid = null;
            $own = $x && !empty($x['voucher_vnum']) ? (int) $x['voucher_vnum'] : 0;
            if ($own) {
                $vx = $deskV[$own] ?? null;
                $why = $vx ? self::disagreement($vx, [$r], [$x]) : 'that voucher does not exist';
                if ($why === null) {
                    $out[$id] = ['state' => 'linked', 'kind' => 'desk', 'vnums' => [$own], 'basis' => 'the desk record of the request', 'released' => [], 'vouchers' => $info([$own]), 'others' => [], 'review' => null];
                    continue;
                }
                if (($rev[$own]['decision'] ?? '') !== 'not') {
                    $invalid = ['vnum' => $own, 'why' => $why];
                }
            }
            // 4. a voucher line naming it; otherwise the vouchers that may be its payment
            $adv = (string) $r['type'] === 'Transport Advance' ? 1 : 2;
            $since = substr((string) ($r['accept_date'] ?: $r['payment_request_date']), 0, 10);
            $usd = (string) $r['type'] === 'Hotel' || strtoupper((string) ($x['currency'] ?? '')) === 'USD';
            $amt = round($x && $x['amount'] !== null && $x['amount'] !== '' ? (float) $x['amount'] : (float) $r['requestAmount'], 2);
            $tour = (string) $r['tour_id'];
            $named = [];
            $same = [];
            $other = [];
            foreach ($cands as $v) {
                if ((string) $v['Acc_No'] !== (string) $r['hotel_acc'] || (int) $v['advances_pay'] !== $adv) {
                    continue;
                }
                $vn = (int) $v['Vnum'];
                if (isset($refTo[$vn][$id])) {
                    $named[] = $vn;   // the voucher line names this request
                    continue;
                }
                if ($vn === $own || ($claimed[$vn] ?? []) && array_diff(array_keys($claimed[$vn]), [$id])) {
                    continue;         // its own (refused) desk record's voucher, or a voucher another request is linked to or recorded against
                }
                if (substr((string) $v['C_Date'], 0, 10) < $since) {
                    continue;         // issued before the request was approved
                }
                if ((string) $v['ref_po'] !== $tour && empty(array_filter($lines[$vn] ?? [], static fn ($l) => (string) $l['Invoice'] === $tour))) {
                    continue;         // not on this tour
                }
                if (self::sameAmount($v, $lines[$vn] ?? [], $tour, $amt, $usd)) {
                    $same[] = $vn;
                } else {
                    $other[] = $vn;
                }
            }
            if ($named) {
                $out[$id] = ['state' => 'linked', 'kind' => 'voucher_line', 'vnums' => $named, 'basis' => 'the voucher line names this request', 'released' => [], 'vouchers' => $info($named), 'others' => [], 'review' => null];
                continue;
            }
            if ($invalid) {
                $out[$id] = ['state' => 'invalid', 'kind' => 'invalid_link', 'vnums' => [$invalid['vnum']], 'basis' => 'its desk record names Payment Voucher No. ' . $invalid['vnum'] . ', but ' . $invalid['why'] . (($rev[$invalid['vnum']]['decision'] ?? '') === 'conflict' ? ' — CONFLICTING REVIEWS on record (' . $rev[$invalid['vnum']]['reason'] . '): held until the System Administrator removes the wrong one' : ''),
                    'released' => [], 'vouchers' => $info([$invalid['vnum']]), 'others' => $info(array_merge($same, $other)), 'review' => null];
                continue;
            }
            if (in_array((int) $r['payment_request'], [0, 1], true)) {
                continue;   // not approved yet (requested / on hold): only a stored link can pay it — no voucher is matched to it
            }
            $held = $desk ? $same : array_merge($same, $other);   // vouchers that may be its payment
            $rest = $desk ? $other : [];                           // vouchers that cannot be (shown for information)
            $relAll = array_keys(array_filter($rev, static fn ($d) => $d['decision'] === 'not'));
            if ($held) {
                $rel = array_values(array_filter($held, static fn ($vn) => isset($rev[$vn]) && $rev[$vn]['decision'] === 'not'));
                $open = array_values(array_diff($held, $rel));
                $basis = $desk ? 'same supplier, tour and amount, issued on or after the approval'
                    : ($open && in_array($open[0], $same, true) ? 'approved before the desk kept records — same supplier, tour and amount, issued on or after the approval, not linked (unverified)'
                        : 'approved before the desk kept records — same supplier and tour, issued on or after the approval, not linked (unverified)');
                foreach ($open as $cvn) {
                    if (($rev[$cvn]['decision'] ?? '') === 'conflict') {   // tf-paydesk-r7h build 5 (V-5)
                        $basis .= ' — CONFLICTING REVIEWS on record for Payment Voucher No. ' . $cvn . ' (' . $rev[$cvn]['reason'] . '): held until the System Administrator removes the wrong one';
                    }
                }
                $out[$id] = $open
                    ? ['state' => 'possible', 'kind' => $desk ? 'desk_possible' : 'unverified', 'vnums' => $open, 'basis' => $basis, 'released' => $relList($relAll), 'vouchers' => $info($open), 'others' => $info($rest), 'review' => null]
                    : ['state' => 'released', 'kind' => 'released', 'vnums' => $held, 'basis' => $basis, 'released' => $relList($relAll), 'vouchers' => $info($held), 'others' => $info($rest), 'review' => null];
                continue;
            }
            if ($other) {
                $out[$id] = ['state' => 'other', 'kind' => 'other', 'vnums' => $other, 'basis' => 'other vouchers of the same supplier and tour (another amount) — not linked to this request', 'released' => $relList($relAll), 'vouchers' => $info($other), 'others' => [], 'review' => null];
                continue;
            }
            if ($relAll) {
                $out[$id] = ['state' => 'released', 'kind' => 'released', 'vnums' => [], 'basis' => 'reviewed', 'released' => $relList($relAll), 'vouchers' => [], 'others' => [], 'review' => null];
            }
        }
        return $out;
    }

    /** Does the voucher carry this amount for this tour (a line of the tour, or the voucher itself)? */
    private static function sameAmount(array $v, array $vlines, string $tour, float $amt, bool $usd): bool
    {
        foreach ($vlines as $l) {
            if ((string) $l['Invoice'] !== $tour) {
                continue;
            }
            if ($usd) {
                $rate = (float) $l['Rate'];
                if ($rate > 1 && abs(round((float) $l['Amount'] / $rate, 2) - $amt) < self::TOL) {
                    return true;
                }
            } elseif (abs(round((float) $l['Amount'], 2) - $amt) < self::TOL) {
                return true;
            }
        }
        if ($usd) {
            return $v['doller_amt'] !== null && (float) $v['doller_amt'] > 0 && abs(round((float) $v['doller_amt'], 2) - $amt) < self::TOL;
        }
        return abs(round((float) $v['Amount'], 2) - $amt) < self::TOL;
    }

    // =====================================================================
    //  Reviews (tf-paydesk-r7h build 5): a holder of the batch privilege records, with a reason, that a voucher IS or is NOT the payment
    //  of a request (payconf:<pr>:<vnum> / payrel:<pr>:<vnum>), or of a dashboard line that has no request (vline:<tour>:<vnum>).
    //  Each decision is written once (tpd_meta, primary key) and audited. Nothing else is written: no voucher is created, no link
    //  is cleared, no legacy table or ledger entry is touched.
    // =====================================================================

    /** [pr_id => [vnum => ['decision' => 'is'|'not', 'by', 'at', 'reason']]] for the given requests. */
    public static function reviews($db, array $prIds): array
    {
        $out = [];
        $prIds = array_values(array_unique(array_filter(array_map('intval', $prIds))));
        if (!$prIds) {
            return $out;
        }
        try {
            $both = [];
            foreach ($db->query("SELECT k, v FROM tpd_meta WHERE k LIKE 'payrel:%' OR k LIKE 'payconf:%'")->getResultArray() as $m) {
                if (!preg_match('/^(payrel|payconf):(\d+):(\d+)$/', (string) $m['k'], $mm) || !in_array((int) $mm[2], $prIds, true)) {
                    continue;
                }
                $both[(int) $mm[2]][(int) $mm[3]][$mm[1]] = self::decoded($m['v']);
            }
            foreach ($both as $pr => $vs) {
                foreach ($vs as $vn => $k) {
                    if (isset($k['payconf'], $k['payrel'])) {
                        // tf-paydesk-r7h build 5 (verification V-5): IS and NOT both on record (two decisions at the same moment, before the
                        // review lock) — neither counts; the payment stays held until the System Administrator removes the wrong one
                        $out[$pr][$vn] = ['decision' => 'conflict', 'by' => $k['payconf']['by'] . ' / ' . $k['payrel']['by'], 'at' => max($k['payconf']['at'], $k['payrel']['at']),
                            'reason' => 'conflicting reviews: IS (' . $k['payconf']['by'] . ', ' . $k['payconf']['at'] . ') and NOT (' . $k['payrel']['by'] . ', ' . $k['payrel']['at'] . ')'];
                    } else {
                        $out[$pr][$vn] = ['decision' => isset($k['payconf']) ? 'is' : 'not'] + ($k['payconf'] ?? $k['payrel']);
                    }
                }
            }
        } catch (\Throwable $e) {
        }
        return $out;
    }

    /** [pr_id => [vnum => ['by','at','reason']]] — the "not this payment" decisions (r7h build 3 name, kept for its callers). */
    public static function releases($db, array $prIds): array
    {
        $out = [];
        foreach (self::reviews($db, $prIds) as $pr => $vs) {
            foreach ($vs as $vn => $d) {
                if ($d['decision'] === 'not') {
                    $out[$pr][$vn] = ['by' => $d['by'], 'at' => $d['at'], 'reason' => $d['reason']];
                }
            }
        }
        return $out;
    }

    /** Record that voucher $vnum is NOT the payment of request $prId. Returns ['ok', 'status', 'message']. */
    public static function release($db, int $prId, int $vnum, string $by, string $reason): array
    {
        return self::decide($db, $prId, $vnum, 'not', $by, $reason);
    }

    /** Record that voucher $vnum IS the payment of request $prId (build 5). Returns ['ok', 'status', 'message']. */
    public static function confirm($db, int $prId, int $vnum, string $by, string $reason): array
    {
        return self::decide($db, $prId, $vnum, 'is', $by, $reason);
    }

    private static function decide($db, int $prId, int $vnum, string $decision, string $by, string $reason): array
    {
        // tf-paydesk-r7h build 5 (verification V-5): one decision at a time per request and per voucher — an IS and a NOT sent at the same
        // moment from two sessions had both been recorded. The second now waits, then meets the first one's record and is refused.
        return PayDesk::withItemLock($db, 'review-pr|' . $prId, fn () => PayDesk::withItemLock($db, 'review-v|' . $vnum, fn () => self::decideOnce($db, $prId, $vnum, $decision, $by, $reason)))
            ?? ['ok' => false, 'status' => 409, 'message' => 'Another review of this payment is being recorded at this moment. Reload the page. Nothing was written.'];
    }

    private static function decideOnce($db, int $prId, int $vnum, string $decision, string $by, string $reason): array
    {
        $is = $decision === 'is';
        $reason = trim($reason);
        if ($reason === '') {
            return ['ok' => false, 'status' => 400, 'message' => 'A reason is required: say how you checked that Payment Voucher No. ' . $vnum . ($is ? ' IS' : ' is not') . ' this payment.'];
        }
        $r = $db->query("SELECT * FROM payment_request WHERE id = ?", [$prId])->getRowArray();
        if (!$r) {
            return ['ok' => false, 'status' => 404, 'message' => 'Request not found.'];
        }
        $x = null;
        try {
            $x = $db->query("SELECT * FROM tpd_request_ext WHERE pr_id = ?", [$prId])->getRowArray() ?: null;
        } catch (\Throwable $e) {
        }
        $kMine = ($is ? 'payconf:' : 'payrel:') . $prId . ':' . $vnum;
        $kOther = ($is ? 'payrel:' : 'payconf:') . $prId . ':' . $vnum;
        $done = $db->query("SELECT v FROM tpd_meta WHERE k = ?", [$kMine])->getRowArray();
        if ($done) {   // a repeat click: the first one recorded it
            $j = self::decoded($done['v']);
            return ['ok' => true, 'status' => 200, 'duplicate' => true, 'message' => 'Already recorded by ' . $j['by'] . ' on ' . $j['at'] . ' — nothing was written twice.'];
        }
        $prior = $db->query("SELECT v FROM tpd_meta WHERE k = ?", [$kOther])->getRowArray();
        if ($prior) {
            $j = self::decoded($prior['v']);
            return ['ok' => false, 'status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' was already reviewed for request #' . $prId . ' as ' . ($is ? 'NOT its payment' : 'its payment') . ' by ' . $j['by'] . ' on ' . $j['at']
                . '. A decision is recorded once; if it was wrong, the System Administrator must correct it. Nothing was written.'];
        }
        if ($is) {
            $have = $db->query("SELECT k FROM tpd_meta WHERE k LIKE ?", ['payconf:' . $prId . ':%'])->getRowArray();
            if ($have && preg_match('/^payconf:\d+:(\d+)$/', (string) $have['k'], $mm)) {
                return ['ok' => false, 'status' => 409, 'message' => 'Request #' . $prId . ' is already confirmed as paid by Payment Voucher No. ' . $mm[1] . '. Nothing was written.'];
            }
        }
        $st = self::earlierPayments($db, [$r], $x ? [$prId => $x] : [])[$prId] ?? null;
        if (!$st || !in_array($st['state'], ['possible', 'invalid'], true) || !in_array($vnum, array_map('intval', $st['vnums']), true)) {
            return ['ok' => false, 'status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' is not an unverified or held payment of request #' . $prId . ' (nothing to review). Reload the page. Nothing was written.'];
        }
        if ($is) {
            // tf-paydesk-r7h build 5 (V-5): a voucher already confirmed as the payment of a dashboard line is not given to a request as well
            foreach ($db->query("SELECT k, v FROM tpd_meta WHERE k LIKE ?", ['vline:%:' . $vnum])->getResultArray() as $lm) {
                $lj = json_decode((string) $lm['v'], true) ?: [];
                if (preg_match('/^vline:.+:(\d+)$/', (string) $lm['k'], $mm) && (int) $mm[1] === $vnum && ($lj['decision'] ?? '') === 'is') {
                    return ['ok' => false, 'status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' was already confirmed as a payment of a dashboard line (' . ($lj['line'] ?? '') . ', ' . ($lj['by'] ?? '') . '). Nothing was written.'];
                }
            }
            $vr = $db->query("SELECT Aflag FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
            if (!$vr || in_array((int) $vr['Aflag'], PayDesk::AFLAG_DEAD, true)) {
                return ['ok' => false, 'status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' is cancelled or rejected; it cannot be this payment. Nothing was written.'];
            }
        }
        $val = json_encode(['by' => $by, 'at' => date('Y-m-d H:i:s'), 'reason' => mb_substr($reason, 0, 300)], JSON_UNESCAPED_UNICODE);
        $db->query("INSERT IGNORE INTO tpd_meta (k, v) VALUES (?, ?)", [$kMine, $val]);   // k is the primary key: a concurrent second click writes nothing
        if ($db->affectedRows() < 1) {
            return ['ok' => true, 'status' => 200, 'duplicate' => true, 'message' => 'Already recorded — nothing was written twice.'];
        }
        Audit::log($db, ['action' => $is ? 'payment_link_confirmed' : 'possible_payment_released', 'user' => $by, 'tour_id' => $r['tour_id'], 'manual_id' => $r['manual_id'], 'ref_type' => 'request', 'ref_id' => $prId, 'vnum' => $vnum,
            'reason' => $reason, 'new' => ['decision' => $is ? 'IS the payment' : 'NOT the payment', 'state' => $st['state'], 'kind' => $st['kind'] ?? null, 'held_by' => $st['vnums'], 'basis' => $st['basis']]]);
        $payable = in_array((int) $r['payment_request'], [4, 6], true) && (int) $r['voucher_created'] === 0 && empty($x['batch_id']);
        if ($is) {
            return ['ok' => true, 'status' => 200, 'message' => 'Confirmed: Payment Voucher No. ' . $vnum . ' is recorded as the payment of request #' . $prId . ' (' . $by . '). The request is now linked to it — paid when that voucher is Paid — and no other payment of it is allowed. Nothing else was changed.'];
        }
        $left = array_values(array_diff(array_map('intval', $st['vnums']), [$vnum]));
        if ($st['state'] === 'invalid') {
            return ['ok' => true, 'status' => 200, 'message' => 'Reviewed: Payment Voucher No. ' . $vnum . ' is recorded as not the payment of request #' . $prId . ' (' . $by . '). Its desk record stays as it was.'
                . ($payable ? ' Request #' . $prId . ' can now go into a batch.' : ' It is no longer shown as its payment.')];
        }
        return ['ok' => true, 'status' => 200, 'message' => 'Released: Payment Voucher No. ' . $vnum . ' is recorded as not this payment (' . $by . ').'
            . ($left ? ' Still held by Payment Voucher No. ' . implode(', ', $left) . '.' : ($payable ? ' Request #' . $prId . ' can now go into a batch.' : ' It is no longer shown as its payment.'))];
    }

    /** [vnum => ['decision' => 'is'|'not', 'line', 'by', 'at', 'reason']] — the line reviews of a tour's vouchers that no request explains. */
    public static function lineReviews($db, string $tourId): array
    {
        $out = [];
        if ($tourId === '') {
            return $out;
        }
        try {
            foreach ($db->query("SELECT k, v FROM tpd_meta WHERE k LIKE ?", ['vline:' . $tourId . ':%'])->getResultArray() as $m) {
                if (preg_match('/^vline:(.+):(\d+)$/', (string) $m['k'], $mm) && $mm[1] === $tourId) {
                    $j = json_decode((string) $m['v'], true) ?: [];
                    $out[(int) $mm[2]] = ['decision' => ($j['decision'] ?? '') === 'is' ? 'is' : 'not', 'line' => (string) ($j['line'] ?? '')] + self::decoded($m['v']);
                }
            }
        } catch (\Throwable $e) {
        }
        return $out;
    }

    /**
     * Record that a voucher no request explains IS / is NOT a payment of a dashboard line (tour + line key).
     * tf-paydesk-r7h build 5.2 (L-3): $target answers null only when the Tour Payment Dashboard of this tour shows this line with this
     * voucher as its unverified association (built from the same data the dashboard shows), or the reason it does not. A fabricated or
     * unrelated line reference is refused before anything is written — it never releases a hold.
     */
    public static function reviewLine($db, string $tourId, string $lineKey, int $vnum, string $decision, string $by, string $reason, callable $target): array
    {
        // tf-paydesk-r7h build 5 (V-5): one decision at a time per voucher (also against a request review of the same voucher)
        return PayDesk::withItemLock($db, 'review-v|' . $vnum, fn () => self::reviewLineOnce($db, $tourId, $lineKey, $vnum, $decision, $by, $reason, $target))
            ?? ['ok' => false, 'status' => 409, 'message' => 'Another review of this voucher is being recorded at this moment. Reload the page. Nothing was written.'];
    }

    private static function reviewLineOnce($db, string $tourId, string $lineKey, int $vnum, string $decision, string $by, string $reason, callable $target): array
    {
        $is = $decision === 'is';
        $reason = trim($reason);
        if (!in_array($decision, ['is', 'not'], true)) {
            return ['ok' => false, 'status' => 400, 'message' => 'Say whether the voucher IS or is NOT a payment of this line.'];
        }
        if ($reason === '') {
            return ['ok' => false, 'status' => 400, 'message' => 'A reason is required: say how you checked Payment Voucher No. ' . $vnum . '.'];
        }
        if ($tourId === '' || strlen($lineKey) > 60 || !preg_match('/^[a-z_]+:[A-Za-z0-9\-]+(:\d+)?$/', $lineKey)) {
            return ['ok' => false, 'status' => 400, 'message' => 'Unknown line. Reload the page. Nothing was written.'];
        }
        $acc = explode(':', $lineKey)[1];
        $v = $db->query("SELECT Vnum, Acc_No, Aflag, ref_po FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        if (!$v) {
            return ['ok' => false, 'status' => 404, 'message' => 'Payment Voucher No. ' . $vnum . ' does not exist.'];
        }
        if (trim((string) $v['Acc_No']) !== $acc) {
            return ['ok' => false, 'status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' pays another supplier than this line. Nothing was written.'];
        }
        $onTour = (string) $v['ref_po'] === $tourId || $db->query("SELECT ID FROM v_inv WHERE V_tkn = ? AND Invoice = ? LIMIT 1", [$vnum, $tourId])->getRowArray();
        if (!$onTour) {
            return ['ok' => false, 'status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' is not a voucher of this tour. Nothing was written.'];
        }
        if (in_array((int) $v['Aflag'], PayDesk::AFLAG_DEAD, true)) {
            return ['ok' => false, 'status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' is cancelled or rejected; there is nothing to review. Nothing was written.'];
        }
        $cl = self::claims($db)[$vnum] ?? [];
        if ($cl) {
            return ['ok' => false, 'status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' is linked to (or recorded against) request #' . implode(', #', array_keys($cl)) . ' — review it with that request. Nothing was written.'];
        }
        $k = 'vline:' . $tourId . ':' . $vnum;
        $done = $db->query("SELECT v FROM tpd_meta WHERE k = ?", [$k])->getRowArray();
        if ($done) {
            $j = json_decode((string) $done['v'], true) ?: [];
            $d = self::decoded($done['v']);
            if (($j['decision'] ?? '') === $decision && ($j['line'] ?? '') === $lineKey) {
                return ['ok' => true, 'status' => 200, 'duplicate' => true, 'message' => 'Already recorded by ' . $d['by'] . ' on ' . $d['at'] . ' — nothing was written twice.'];
            }
            return ['ok' => false, 'status' => 409, 'message' => 'Payment Voucher No. ' . $vnum . ' was already reviewed on this tour by ' . $d['by'] . ' on ' . $d['at'] . '. A decision is recorded once; if it was wrong, the System Administrator must correct it. Nothing was written.'];
        }
        // tf-paydesk-r7h build 5.2 (L-3): the reference must identify the actual dashboard line and its unverified voucher
        $why = $target();
        if ($why !== null) {
            Audit::log($db, ['action' => 'payment_review_refused', 'user' => $by, 'tour_id' => $tourId, 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'reason' => 'line review refused — ' . $why . ' (line reference ' . $lineKey . ')',
                'new' => ['line' => $lineKey, 'decision' => $decision]]);
            return ['ok' => false, 'status' => 409, 'message' => $why . ' Reload the dashboard. Nothing was written.'];
        }
        $val = json_encode(['decision' => $decision, 'line' => $lineKey, 'by' => $by, 'at' => date('Y-m-d H:i:s'), 'reason' => mb_substr($reason, 0, 300)], JSON_UNESCAPED_UNICODE);
        $db->query("INSERT IGNORE INTO tpd_meta (k, v) VALUES (?, ?)", [$k, $val]);
        if ($db->affectedRows() < 1) {
            return ['ok' => true, 'status' => 200, 'duplicate' => true, 'message' => 'Already recorded — nothing was written twice.'];
        }
        Audit::log($db, ['action' => 'voucher_line_reviewed', 'user' => $by, 'tour_id' => $tourId, 'ref_type' => 'voucher', 'ref_id' => $vnum, 'vnum' => $vnum, 'reason' => $reason,
            'new' => ['line' => $lineKey, 'decision' => $is ? 'IS a payment of this line' : 'NOT a payment of this line']]);
        return ['ok' => true, 'status' => 200, 'message' => $is
            ? 'Confirmed: Payment Voucher No. ' . $vnum . ' is recorded as a payment of this line (' . $by . '). It now counts for the line. Nothing else was changed.'
            : 'Reviewed: Payment Voucher No. ' . $vnum . ' is recorded as not a payment of this line (' . $by . '). It is shown on its own line ("recorded outside the request chain"). Nothing else was changed.'];
    }

    /**
     * The vouchers of a supplier on a tour (of the kind of a request type) that no request explains and nobody has reviewed: a further
     * payment of that line waits for their review (PayDesk::openRequest, build 5 N-5).
     */
    public static function unsettledFor($db, string $tourId, string $acc, string $type): array
    {
        $kind = $type === 'Transport Advance' ? ' AND v.advances_pay = 1' : ' AND v.advances_pay <> 1';
        $vs = $db->query("SELECT DISTINCT v.Vnum FROM voucher v LEFT JOIN v_inv vi ON vi.V_tkn = v.Vnum AND vi.Invoice = ?
                          WHERE v.Acc_No = ? AND v.Aflag NOT IN (2,3){$kind} AND (vi.ID IS NOT NULL OR v.ref_po = ?) ORDER BY v.Vnum", [$tourId, $acc, $tourId])->getResultArray();
        if (!$vs) {
            return [];
        }
        $cl = self::claims($db);
        $lr = self::lineReviews($db, $tourId);
        return array_values(array_filter(array_map(static fn ($v) => (int) $v['Vnum'], $vs), static fn ($vn) => empty($cl[$vn]) && !isset($lr[$vn])));
    }

    /** vnum => [pr_id => source] for every voucher a request is linked to or recorded against (desk record, batch, voucher line, review). */
    private static function claims($db): array
    {
        $c = [];
        try {
            $bids = [];
            foreach ($db->query("SELECT pr_id, voucher_vnum, batch_id FROM tpd_request_ext WHERE voucher_vnum IS NOT NULL OR batch_id IS NOT NULL")->getResultArray() as $x) {
                if (!empty($x['voucher_vnum'])) {
                    $c[(int) $x['voucher_vnum']][(int) $x['pr_id']] = 'desk';
                }
                if (!empty($x['batch_id'])) {
                    $bids[(int) $x['batch_id']][] = (int) $x['pr_id'];
                }
            }
            if ($bids) {
                foreach ($db->query("SELECT id, vnum FROM tpd_batch WHERE id IN (" . implode(',', array_keys($bids)) . ") AND vnum IS NOT NULL")->getResultArray() as $b) {
                    foreach ($bids[(int) $b['id']] as $pr) {
                        $c[(int) $b['vnum']][$pr] = 'batch';
                    }
                }
            }
        } catch (\Throwable $e) {
        }
        foreach ($db->query("SELECT V_tkn, ref FROM v_inv WHERE ref LIKE 'tpd:pr:%'")->getResultArray() as $l) {
            if (($id = self::refOf($l['ref'])) !== null) {
                $c[(int) $l['V_tkn']][$id] = 'voucher_line';
            }
        }
        try {
            $rels = [];
            foreach ($db->query("SELECT k FROM tpd_meta WHERE k LIKE 'payrel:%'")->getResultArray() as $m) {
                $rels[substr((string) $m['k'], 7)] = true;
            }
            foreach ($db->query("SELECT k FROM tpd_meta WHERE k LIKE 'payconf:%'")->getResultArray() as $m) {
                if (preg_match('/^payconf:(\d+):(\d+)$/', (string) $m['k'], $mm) && !isset($rels[$mm[1] . ':' . $mm[2]])) {   // V-5: a conflicting pair is no link
                    $c[(int) $mm[2]][(int) $mm[1]] = 'review';
                }
            }
        } catch (\Throwable $e) {
        }
        return $c;
    }

    /** ['by', 'at', 'reason'] of a stored decision. */
    private static function decoded($json): array
    {
        $j = json_decode((string) $json, true) ?: [];
        return ['by' => (string) ($j['by'] ?? ''), 'at' => (string) ($j['at'] ?? ''), 'reason' => (string) ($j['reason'] ?? '')];
    }

    // =====================================================================
    //  helpers
    // =====================================================================

    private static function requests($db, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        return $db->query("SELECT * FROM payment_request WHERE id IN (" . implode(',', $ids) . ") ORDER BY id")->getResultArray();
    }

    /** Why the requests do not agree with the voucher (supplier, amount), or null when they do. */
    private static function disagreement(array $v, array $reqs, array $ext): ?string
    {
        $acc = trim((string) ($v['Acc_No'] ?? ''));
        $others = array_values(array_unique(array_filter(array_map(static fn ($r) => trim((string) $r['hotel_acc']), $reqs), static fn ($a) => $a !== $acc)));
        if ($others) {
            return 'the request is for ' . implode(', ', $others) . ' while the voucher pays ' . $acc;
        }
        $byId = [];
        foreach ($ext as $x) {
            $byId[(int) $x['pr_id']] = $x;
        }
        $lkr = 0.0;
        $usd = 0.0;
        $hasUsd = false;
        foreach ($reqs as $r) {
            $a = self::requestAmount($r, $byId[(int) $r['id']] ?? null);
            if ($a['currency'] === 'USD') {
                $hasUsd = true;
                $usd += $a['amount'];
            } else {
                $lkr += $a['amount'];
            }
        }
        if ($hasUsd && $lkr > 0) {
            return 'the requests mix USD and LKR';
        }
        if ($hasUsd) {
            $rec = isset($v['doller_amt']) && $v['doller_amt'] !== null ? round((float) $v['doller_amt'], 2) : 0.0;
            return $rec > 0 && abs(round($usd, 2) - $rec) > self::TOL ? 'the request amount (USD ' . number_format($usd, 2) . ') is not the voucher\'s USD figure (' . number_format($rec, 2) . ')' : null;
        }
        $vl = round((float) ($v['Amount'] ?? 0), 2);
        return abs(round($lkr, 2) - $vl) > self::TOL ? 'the request amount (LKR ' . number_format($lkr, 2) . ') is not the voucher\'s amount (LKR ' . number_format($vl, 2) . ')' : null;
    }

    /**
     * The requests this voucher may be the payment of — the same rule as Supplier Payables and the dashboard (earlierPayments): an
     * UNVERIFIED association, never shown as the voucher's request. tf-paydesk-r7h build 5 (was: same supplier, tour, kind and amount).
     */
    private static function candidates($db, array $v): array
    {
        $acc = trim((string) ($v['Acc_No'] ?? ''));
        $vnum = (int) ($v['Vnum'] ?? 0);
        if ($acc === '' || $vnum <= 0 || in_array((int) ($v['Aflag'] ?? 0), PayDesk::AFLAG_DEAD, true)) {
            return [];
        }
        $tours = [];
        foreach ($db->query("SELECT DISTINCT Invoice FROM v_inv WHERE V_tkn = ?", [$vnum])->getResultArray() as $l) {
            if (trim((string) $l['Invoice']) !== '') {
                $tours[trim((string) $l['Invoice'])] = true;
            }
        }
        if (trim((string) ($v['ref_po'] ?? '')) !== '') {
            $tours[trim((string) $v['ref_po'])] = true;
        }
        if (!$tours) {
            return [];
        }
        $rows = $db->query("SELECT * FROM payment_request WHERE hotel_acc = ? AND tour_id IN (" . implode(',', array_map([$db, 'escape'], array_keys($tours))) . ")
                            AND (payment_request IN (2,4,6) OR voucher_created = 1) ORDER BY id", [$acc])->getResultArray();
        if (!$rows) {
            return [];
        }
        $exts = [];
        try {
            foreach ($db->query("SELECT * FROM tpd_request_ext WHERE pr_id IN (" . implode(',', array_map(static fn ($r) => (int) $r['id'], $rows)) . ")")->getResultArray() as $x) {
                $exts[(int) $x['pr_id']] = $x;
            }
        } catch (\Throwable $e) {
        }
        $byId = [];
        foreach ($rows as $r) {
            $byId[(int) $r['id']] = $r;
        }
        $out = [];
        foreach (self::earlierPayments($db, $rows, $exts) as $id => $st) {
            if ($st['state'] === 'possible' && in_array($vnum, array_map('intval', $st['vnums']), true)) {
                $r = $byId[$id];
                $a = self::requestAmount($r, $exts[$id] ?? null);
                $out[] = ['id' => (int) $id, 'type' => (string) $r['type'], 'status' => (int) $r['payment_request'], 'label' => $r['type'] . ', ' . $a['label'], 'basis' => $st['basis']];
            }
        }
        return $out;
    }
}
