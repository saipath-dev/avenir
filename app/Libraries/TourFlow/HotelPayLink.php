<?php

namespace App\Libraries\TourFlow;

/**
 * tf-resdesk-r2 (12 Nov 2026) — a confirmed hotel booking of a new-desk tour enters the EXISTING payment chain.
 *
 * Shantha, 12 Nov 2026: "hotel confirmation → Payment Approval → payment voucher created after approval → Bank Payment →
 * finalisation", with the chain's roles, separation of duties, ledger postings and audit unchanged.
 *
 * The chain (payment_request → approval → Supplier Payables / voucher → Bank Payment Check → Paid) reads a tour from
 * `consignee` and its hotel nights from `tour_quotations`. A tour confirmed at the new Tour Desk has neither, so when the
 * Reservation Desk records a hotel's confirmation (Confirm Booking) this class:
 *   1. finds or makes the tour's payment record in `consignee` (tour_id = manual_id = the tour number, e.g. AVH-2026-00001;
 *      tour_code 'TF', the new desk's own tour id in ref_no) — once per tour;
 *   2. writes the stay's nights to `tour_quotations` (hotel, dates, rooms, meal plan, the quotation's USD rates) — once per
 *      night, never twice;
 *   3. raises the hotel payment request through the chain's own handler (TourAccountsController::paymentRequestFrom, via
 *      raiseForResDesk), so the duplicate check, the rate-review hold, the USD rate of the day, the request's journal and
 *      its desk record are exactly the chain's.
 * Nothing is raised before the hotel confirms; sending the booking voucher never raises anything.
 *
 * Read back (forJobs): the request's state, who approved it and when (approval date), its payment voucher, and the paid date
 * (the voucher's finalised bank date) — the chain's own records, never copied.
 */
final class HotelPayLink
{
    private $db;
    private string $user;

    public function __construct($db, string $user)
    {
        $this->db = $db;
        $this->user = $user;
    }

    public static function ensure($db): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $db->query("CREATE TABLE IF NOT EXISTS `tf_hotel_pay` (
            `job_id` INT NOT NULL,
            `tour_id` INT NOT NULL,
            `legacy_tour_id` VARCHAR(50) NOT NULL,
            `hotel_acc` VARCHAR(50) NOT NULL,
            `payment_request_id` INT NULL,
            `amount_usd` DECIMAL(14,2) NULL,
            `state` VARCHAR(12) NOT NULL COMMENT 'raised | held | failed',
            `message` VARCHAR(500) NULL,
            `by` VARCHAR(50) NULL,
            `at` DATETIME NOT NULL,
            PRIMARY KEY (`job_id`),
            KEY `legacy` (`legacy_tour_id`, `hotel_acc`)
        )");
        $done = true;
    }

    /** The supplier code the stay pays: its own, else the panel hotel's primary supplier. */
    public function hotelAcc(array $j): ?string
    {
        $acc = trim((string) ($j['hotel_acc'] ?? ''));
        if ($acc === '' && !empty($j['hotel_id'])) {
            try {
                $acc = trim((string) ($this->db->query('SELECT primary_sup_acc_no FROM hr2_hotel WHERE id = ?', [(int) $j['hotel_id']])->getRowArray()['primary_sup_acc_no'] ?? ''));
            } catch (\Throwable $e) {
                $acc = '';
            }
        }
        return $acc !== '' ? $acc : null;
    }

    /**
     * What the stay costs, night by night, from the quotation (the inquiry's nights: the published rate copied beside each night,
     * times the room mix). Returns ['nights' => [date => [...]], 'total' => USD, 'problems' => [..]].
     */
    public function costFromQuotation(array $j, array $it): array
    {
        $nights = [];
        $problems = [];
        $total = 0.0;
        foreach ((array) ($it['days'] ?? []) as $d) {
            $n = (int) ($d['day_no'] ?? 0);
            if ($n < (int) $j['day_from'] || $n > (int) $j['day_to']) {
                continue;
            }
            $date = $d['day_date'] ?? (!empty($it['arrival_date']) ? date('Y-m-d', strtotime($it['arrival_date'] . ' +' . ($n - 1) . ' days')) : null);
            $mix = is_array($d['room_mix'] ?? null) ? $d['room_mix'] : [];
            $sg = (int) ($mix['sgl'] ?? 0); $db = (int) ($mix['dbl'] ?? 0) + (int) ($mix['twn'] ?? 0); $tp = (int) ($mix['tpl'] ?? 0);
            $rs = $d['rate_sgl'] !== null ? (float) $d['rate_sgl'] : null;
            $rd = $d['rate_dbl'] !== null ? (float) $d['rate_dbl'] : null;
            $rt = $d['rate_tpl'] !== null ? (float) $d['rate_tpl'] : null;
            $cur = strtoupper(trim((string) ($d['rate_currency'] ?? 'USD'))) ?: 'USD';
            if ($cur !== 'USD') {
                $problems[] = 'Night ' . $n . ' is priced in ' . $cur . ', not USD.';
            }
            if ($sg + $db + $tp === 0) {
                $problems[] = 'Night ' . $n . ' has no room mix.';
            }
            if (($sg && $rs === null) || ($db && $rd === null) || ($tp && $rt === null)) {
                $problems[] = 'Night ' . $n . ' has no published rate for every room on it.';
            }
            $amt = $sg * (float) $rs + $db * (float) $rd + $tp * (float) $rt;
            $total += $amt;
            $nights[$date] = ['day_no' => $n, 'sgl' => $sg, 'dbl' => $db, 'tpl' => $tp, 'rate_sgl' => $rs, 'rate_dbl' => $rd, 'rate_tpl' => $rt, 'amount' => round($amt, 2),
                'meal' => (string) ($d['meal_plan'] ?? $j['meal_plan'] ?? ''), 'room_type' => (string) ($d['room_type_name'] ?? $j['room_type_text'] ?? '')];
        }
        if (!$nights) {
            $problems[] = 'The quotation has no nights for this stay.';
        }
        return ['nights' => $nights, 'total' => round($total, 2), 'problems' => $problems];
    }

    /** The tour's record in `consignee` (made once). Returns its tour_id. */
    private function consigneeFor(array $tour): string
    {
        $no = (string) $tour['tour_no'];
        $row = $this->db->query('SELECT ID, tour_id FROM consignee WHERE tour_id = ? LIMIT 1', [$no])->getRowArray();
        if ($row) {
            return (string) $row['tour_id'];
        }
        $this->db->query('INSERT INTO consignee (tour_id, manual_id, consignee, agent, agent_name, passenger_count, start_date, end_date, arrival_flight, departure_flight,
                create_user, create_time, verify, is_template, tour_cancel, payment_complete, tour_code, ref_no, agent_ref)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1,0,0,0,?,?,?)', [
            $no, $no, mb_substr((string) ($tour['tour_name'] ?: $tour['agent_name'] ?: $no), 0, 255), $tour['agent_acc'] ?? null, $tour['agent_name'] ?? null,
            $tour['pax_total'] !== null ? (int) $tour['pax_total'] : null, $tour['arrival_date'], $tour['departure_date'], $tour['arrival_flight'] ?? null, $tour['departure_flight'] ?? null,
            $this->user, date('Y-m-d H:i:s'), 'TF', (int) $tour['id'], $tour['inquiry_no'] ?? null,
        ]);
        return $no;
    }

    /** The stay's nights in `tour_quotations` (a night already there is left as it is). */
    private function nightsFor(string $legacyId, string $acc, array $j, array $cost, string $ref): int
    {
        $n = 0;
        foreach ($cost['nights'] as $date => $c) {
            $has = $this->db->query('SELECT id FROM tour_quotations WHERE quotation_id = ? AND hotel_acc = ? AND tour_date = ? LIMIT 1', [$legacyId, $acc, $date])->getRowArray();
            if ($has) {
                continue;
            }
            $this->db->query('INSERT INTO tour_quotations (quotation_id, quotation_day, hotel_acc, hotel_name, meal_code, room_type, tour_date, start_date, end_date, sgl, dbl, tpl,
                    sglcount, dblcount, tplcount, created_time, created_user, confirm, finalize, hotel_booking_flag, booking_confirmed_flag, hotel_payment_flag, confirmation_reference,
                    saved, verify, payment_complete)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,0,1,1,0,?,1,1,0)', [
                $legacyId, (float) $c['day_no'], $acc, mb_substr((string) $j['hotel_name'], 0, 255), $c['meal'] !== '' ? $c['meal'] : null, $c['room_type'] !== '' ? mb_substr($c['room_type'], 0, 255) : null,
                $date, $j['check_in'], $j['check_out'], (float) $c['rate_sgl'], (float) $c['rate_dbl'], (float) $c['rate_tpl'],
                $c['sgl'], $c['dbl'], $c['tpl'], date('Y-m-d H:i:s'), $this->user, mb_substr($ref, 0, 255),
            ]);
            $n++;
        }
        return $n;
    }

    /**
     * Raise the stay's hotel payment request. $raise is the chain's handler: fn(array $payload, string $user): string (JSON).
     * Returns ['ok' => bool, 'state' => raised|held|failed, 'message' => ..., 'payment_request_id' => ?int].
     */
    public function raise(array $j, array $tour, array $it, callable $raise): array
    {
        self::ensure($this->db);
        $jobId = (int) $j['id'];
        $done = $this->db->query("SELECT * FROM tf_hotel_pay WHERE job_id = ? AND state IN ('raised','held')", [$jobId])->getRowArray();
        if ($done) {
            return ['ok' => true, 'state' => $done['state'], 'message' => 'Already raised.', 'payment_request_id' => $done['payment_request_id'] !== null ? (int) $done['payment_request_id'] : null];
        }
        $acc = $this->hotelAcc($j);
        $record = function (string $state, string $msg, ?int $prId = null, ?float $amt = null, string $legacy = '') use ($jobId, $j, $acc) {
            $this->db->query('REPLACE INTO tf_hotel_pay (job_id, tour_id, legacy_tour_id, hotel_acc, payment_request_id, amount_usd, state, message, `by`, `at`) VALUES (?,?,?,?,?,?,?,?,?,?)',
                [$jobId, (int) $j['tour_id'], $legacy, (string) $acc, $prId, $amt, $state, mb_substr($msg, 0, 500), $this->user, date('Y-m-d H:i:s')]);
            return ['ok' => $state !== 'failed', 'state' => $state, 'message' => $msg, 'payment_request_id' => $prId];
        };
        if ($acc === null) {
            return $record('failed', 'The hotel has no supplier account (Hotel Master → supplier), so no payment request can be raised.');
        }
        $cost = $this->costFromQuotation($j, $it);
        if ($cost['problems'] || $cost['total'] <= 0) {
            return $record('failed', 'The payment request was not raised: ' . implode(' ', $cost['problems'] ?: ['the stay costs nothing on the quotation.']));
        }
        $ref = trim((string) ($j['confirmation_ref'] ?? ''));
        $legacy = '';
        $this->db->transStart();
        $legacy = $this->consigneeFor($tour);
        $this->nightsFor($legacy, $acc, $j, $cost, $ref);
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            return $record('failed', 'The tour\'s payment record could not be written; nothing was raised.', null, null, $legacy);
        }
        $sup = $this->db->query('SELECT Name FROM supplier WHERE Sup_Acc_No = ? LIMIT 1', [$acc])->getRowArray();
        $payload = [
            'tourId' => $legacy, 'hotelAcc' => $acc, 'manualId' => (string) $tour['tour_no'], 'consignee' => (string) ($tour['tour_name'] ?: $tour['agent_name'] ?: $tour['tour_no']),
            'createUser' => $this->user, 'startDate' => $j['check_in'], 'endDate' => $j['check_out'], 'hotelName' => (string) (($sup['Name'] ?? '') ?: $j['hotel_name']),
            // the bank details always from the supplier master on the server (the chain reads them when it sees a masked number)
            'bankName' => '', 'branchName' => '', 'supBankAcc' => '••••',
            'totalHotelPay' => (string) $cost['total'], 'totalHotelAdvance' => '0', 'dueAmount' => (string) $cost['total'],
            'requestAmount' => (string) $cost['total'], 'tot_amt_doller' => (string) $cost['total'], 'type' => 'Hotel',
            'bookingRef' => $ref !== '' ? $ref : null, 'note' => 'Reservation Desk: hotel confirmed (tf-resdesk-r2)',
        ];
        $out = json_decode((string) $raise($payload, $this->user), true) ?: [];
        $code = (int) ($out['status'] ?? 0);
        $msg = (string) ($out['message'] ?? 'No answer from the payment request.');
        $pr = $this->db->query("SELECT id FROM payment_request WHERE tour_id = ? AND hotel_acc = ? AND type = 'Hotel' AND payment_request NOT IN (3,5) ORDER BY id DESC LIMIT 1", [$legacy, $acc])->getRowArray();
        $prId = $pr ? (int) $pr['id'] : null;
        if (!empty($out['held'])) {
            return $record('held', 'Held for the tactical / early-bird rate review before it goes to Payment Approval: ' . $msg, null, $cost['total'], $legacy);
        }
        if ($code === 200 && $prId) {
            // the hotel's confirmation reference on the nights (the chain's handler clears it when it writes the request)
            $this->db->query('UPDATE tour_quotations SET confirmation_reference = ? WHERE quotation_id = ? AND hotel_acc = ? AND tour_date BETWEEN ? AND ?',
                [mb_substr($ref, 0, 255), $legacy, $acc, $j['check_in'], date('Y-m-d', strtotime((string) $j['check_out'] . ' -1 day'))]);
            return $record('raised', 'Hotel payment request #' . $prId . ' raised for USD ' . number_format($cost['total'], 2) . ' — waiting in Payment Approval.', $prId, $cost['total'], $legacy);
        }
        if ($code === 409 && $prId) {
            return $record('raised', 'A payment request already exists for this stay (#' . $prId . ').', $prId, $cost['total'], $legacy);
        }
        return $record('failed', 'The payment request was not raised: ' . $msg, null, $cost['total'], $legacy);
    }

    /**
     * The payment side of each stay, from the chain's records: [job_id => [stage, payment_request_id, amount_usd, requested_at,
     * approved_at, approved_by, voucher_no, voucher_aflag, paid, paid_date, link_state, link_message]].
     */
    public function forJobs(array $jobIds): array
    {
        $out = [];
        if (!$jobIds) {
            return $out;
        }
        try {
            self::ensure($this->db);
            $in = implode(',', array_map('intval', $jobIds));
            $links = $this->db->query("SELECT * FROM tf_hotel_pay WHERE job_id IN ({$in})")->getResultArray();
        } catch (\Throwable $e) {
            return $out;
        }
        foreach ($links as $l) {
            $jid = (int) $l['job_id'];
            $o = ['stage' => null, 'link_state' => $l['state'], 'link_message' => $l['message'], 'payment_request_id' => null, 'amount_usd' => $l['amount_usd'],
                'requested_at' => null, 'requested_by' => null, 'approved_at' => null, 'approved_by' => null, 'voucher_no' => null, 'voucher_aflag' => null, 'paid' => false, 'paid_date' => null];
            if ($l['state'] === 'held') {
                $o['stage'] = 'held';
            }
            if (!empty($l['payment_request_id'])) {
                $pr = $this->db->query('SELECT * FROM payment_request WHERE id = ?', [(int) $l['payment_request_id']])->getRowArray();
                if ($pr) {
                    $o['payment_request_id'] = (int) $pr['id'];
                    $o['requested_at'] = $pr['payment_request_date'];
                    $o['requested_by'] = $pr['payment_request_user'];
                    $st = (int) $pr['payment_request'];
                    $x = null;
                    try {
                        $x = $this->db->query('SELECT approved_by, approved_at, voucher_vnum FROM tpd_request_ext WHERE pr_id = ?', [(int) $pr['id']])->getRowArray() ?: null;
                    } catch (\Throwable $e) {
                    }
                    if (in_array($st, [4, 6, 2], true)) {
                        $o['stage'] = 'approved';
                        $o['approved_at'] = $x['approved_at'] ?? $pr['accept_date'];
                        $o['approved_by'] = $x['approved_by'] ?? $pr['accept_user'];
                    } elseif ($st === 5) {
                        $o['stage'] = 'rejected';
                    } elseif ($st === 3) {
                        $o['stage'] = 'cancelled';
                    } else {
                        $o['stage'] = 'requested';
                    }
                    try {
                        $v = \App\Libraries\TourPayments\PayDesk::voucherOfRequest($this->db, $pr);
                    } catch (\Throwable $e) {
                        $v = null;
                    }
                    if ($v) {
                        $o['voucher_no'] = isset($v['Vnum']) ? (int) $v['Vnum'] : null;
                        $o['voucher_aflag'] = isset($v['Aflag']) ? (int) $v['Aflag'] : null;
                        if ((int) ($v['finalized'] ?? 0) === 1) {
                            $o['paid'] = true;
                            $o['paid_date'] = $v['bank_date'] ?? null;
                            $o['stage'] = 'paid';
                        }
                    }
                }
            }
            $out[$jid] = $o;
        }
        return $out;
    }
}
