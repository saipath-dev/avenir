<?php

namespace App\Libraries\HotelRates;

/**
 * The rate review before a hotel payment request (tf-offers-r3, schema 43; Shantha, 5 Oct 2026):
 *
 *   payment staff prepare the hotel payment → if a tactical or early bird fits the stay and nobody has decided yet,
 *   it is HELD here ("Awaiting rate review") — no payment request, no accounting entry → the System Administrator
 *   (a holder of "Tactical / Early Bird rate selection") reviews it, selects the published rate or the special rate and
 *   the final amount → RELEASE creates the payment request exactly as the prepared one would have been (same handler,
 *   the preparer as the requester), at the final amount → Payment approval → voucher → Bank Payment Check, as before.
 *   He may also RETURN it to the staff with a note.
 *
 * Payment staff see only that it waits for the rate review — never the special rate, its name or the saving. A stay with
 * no special rate, or with a decision already on file, is requested at once, as before. Every step is logged in
 * hr2_offer_log (held, reviewed, the decision, released / returned) with who and when.
 */
final class PaymentHold
{
    /** the fields of a payment request as the Tour Payment Dashboard and Tour Accounts post them */
    public const FIELDS = ['tourId', 'hotelAcc', 'manualId', 'consignee', 'createUser', 'startDate', 'endDate', 'hotelName', 'bankName', 'branchName', 'supBankAcc',
        'totalHotelPay', 'totalHotelAdvance', 'dueAmount', 'requestAmount', 'tot_amt_doller', 'type', 'activityId'];

    public static function ready($db): bool
    {
        try {
            return (bool) $db->query("SHOW TABLES LIKE 'hr2_payment_hold'")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function get($db, int $id): ?array
    {
        $r = $db->query('SELECT * FROM hr2_payment_hold WHERE id = ?', [$id])->getRowArray();
        return $r ?: null;
    }

    public static function awaitingFor($db, string $tourId, string $hotelAcc): ?array
    {
        $r = $db->query("SELECT * FROM hr2_payment_hold WHERE tour_id = ? AND hotel_acc = ? AND status = 'awaiting' ORDER BY id DESC LIMIT 1", [$tourId, $hotelAcc])->getRowArray();
        return $r ?: null;
    }

    /**
     * Called by the payment request handler (TourAccountsController::payment_request) for a hotel, before anything is
     * written: null = request it as before; otherwise the answer to give (held, or already waiting).
     *
     * @param callable $in the request's fields by name
     */
    public static function intercept($db, string $tourId, string $hotelAcc, callable $in, string $user): ?array
    {
        if (!self::ready($db)) {
            return null;
        }
        $tourId = trim($tourId); $hotelAcc = trim($hotelAcc);
        $aw = self::awaitingFor($db, $tourId, $hotelAcc);
        if ($aw) {
            return ['status' => 409, 'message' => 'This hotel payment is already waiting for the System Administrator\'s rate review (sent by ' . $aw['prepared_by'] . ' on ' . substr((string) $aw['prepared_at'], 0, 16) . ').'];
        }
        $offers = new OfferRates($db);
        if (!$offers->ready()) {
            return null;
        }
        $sum = (new HotelCostService($db, $user))->ensureTour($tourId);
        $h = $sum['hotels'][$hotelAcc] ?? null;
        if (!$h || empty($h['offers_ready']) || (int) ($h['choice_needed'] ?? 0) <= 0) {
            return null;   // no special rate fits, or an authorized person has decided already: requested as before
        }
        $payload = [];
        foreach (self::FIELDS as $k) {
            $v = $in($k);
            if ($v !== null) {
                $payload[$k] = is_scalar($v) ? (string) $v : $v;
            }
        }
        $amount = isset($payload['requestAmount']) && is_numeric($payload['requestAmount']) ? round((float) $payload['requestAmount'], 2) : null;
        $now = date('Y-m-d H:i:s');
        $db->query('INSERT INTO hr2_payment_hold (tour_id, manual_id, hotel_acc, hotel_id, hotel_name, type, status, request_amount, payload, prepared_by, prepared_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $tourId, $payload['manualId'] ?? null, $hotelAcc, isset($h['hotel_id']) && $h['hotel_id'] !== null ? (int) $h['hotel_id'] : null, $payload['hotelName'] ?? null, 'Hotel', 'awaiting', $amount,
            json_encode($payload, JSON_UNESCAPED_UNICODE), $user, $now,
        ]);
        $id = (int) $db->insertID();
        $offers->log(['tour_id' => $tourId, 'manual_id' => $payload['manualId'] ?? null, 'hotel_acc' => $hotelAcc, 'hotel_id' => $h['hotel_id'] ?? null, 'event' => 'held',
            'nights' => (int) ($h['nights'] ?? 0), 'published_total' => $h['pub_total'] ?? null, 'used_total' => $amount,
            'note' => 'Payment prepared' . ($amount !== null ? ' for $ ' . number_format($amount, 2) : '') . ' — waiting for the rate review', 'by_user' => $user, 'created_at' => $now]);
        return ['status' => 200, 'held' => true, 'hold_id' => $id,
            'message' => 'Sent to the System Administrator for rate review. It goes on to Payment approval once the rate is confirmed.'];
    }

    /**
     * The amount a release may not exceed — the same ceiling as any payment request on the Tour Payment Dashboard: the
     * quoted figure as prepared, or the hotel cost payable now (with no decision yet: the published rate, or the quoted
     * figure when none is on file), whichever is higher. A special rate dearer than that cannot be requested (Shantha,
     * 4 Oct 2026: the ceiling stays). Null when the rate chosen does not fit the stay.
     */
    public static function ceiling($db, array $hold, string $choice): ?float
    {
        $payload = json_decode((string) $hold['payload'], true) ?: [];
        $quoted = (float) ($payload['tot_amt_doller'] ?? $payload['totalHotelPay'] ?? 0);
        $h = (new HotelCostService($db, 'system'))->summary((string) $hold['tour_id'])['hotels'][$hold['hotel_acc']] ?? null;
        if (!$h) {
            return null;
        }
        if ($choice !== 'published') {
            $fits = false;
            foreach ($h['offer_options'] ?? [] as $o) {
                if ((string) $o['plan_id'] === $choice && $o['nights_fit'] > 0) {
                    $fits = true;
                }
            }
            if (!$fits) {
                return null;
            }
        }
        return round(max($quoted, (float) $h['pay_total']), 2);
    }

    public static function markReleased($db, int $id, string $user, float $amount, string $choice, ?int $contractId, ?int $paymentRequestId, ?string $note): void
    {
        $db->query("UPDATE hr2_payment_hold SET status = 'released', decided_by = ?, decided_at = ?, choice = ?, contract_id = ?, final_amount = ?, note = ?, payment_request_id = ? WHERE id = ? AND status = 'awaiting'",
            [$user, date('Y-m-d H:i:s'), $choice === 'published' ? 'published' : 'offer', $contractId, round($amount, 2), $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 300) : null, $paymentRequestId, $id]);
    }

    /** Back to the staff who prepared it, with a note; they may prepare it again. */
    public static function returnToStaff($db, int $id, string $user, ?string $note): array
    {
        $hold = self::get($db, $id);
        if (!$hold || $hold['status'] !== 'awaiting') {
            throw new \RuntimeException('That hotel payment is not waiting for a rate review any more.');
        }
        $note = $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 300) : null;
        $now = date('Y-m-d H:i:s');
        $db->query("UPDATE hr2_payment_hold SET status = 'returned', decided_by = ?, decided_at = ?, note = ? WHERE id = ? AND status = 'awaiting'", [$user, $now, $note, $id]);
        (new OfferRates($db))->log(['tour_id' => $hold['tour_id'], 'manual_id' => $hold['manual_id'], 'hotel_acc' => $hold['hotel_acc'], 'hotel_id' => $hold['hotel_id'], 'event' => 'returned',
            'used_total' => $hold['request_amount'], 'note' => 'Returned to ' . $hold['prepared_by'] . ($note ? ': ' . $note : ''), 'by_user' => $user, 'created_at' => $now]);
        return self::get($db, $id);
    }

    /** Every hotel payment waiting for the rate review, oldest first (the System Administrator's list). */
    public static function awaiting($db): array
    {
        if (!self::ready($db)) {
            return [];
        }
        return $db->query("SELECT id, tour_id, manual_id, hotel_acc, hotel_name, request_amount, prepared_by, prepared_at FROM hr2_payment_hold WHERE status = 'awaiting' ORDER BY prepared_at, id")->getResultArray();
    }

    /**
     * What the payment screens show for each hotel of a tour: the hold waiting (or the last one returned and not
     * prepared again). Holders get the hold's number to act on it; everyone else only that it waits.
     */
    public static function forTour($db, string $tourId, bool $canDecide): array
    {
        if (!self::ready($db)) {
            return [];
        }
        $latest = [];
        foreach ($db->query('SELECT * FROM hr2_payment_hold WHERE tour_id = ? ORDER BY id', [$tourId])->getResultArray() as $r) {
            $latest[$r['hotel_acc']] = $r;   // the latest hold of each hotel decides
        }
        $out = [];
        foreach ($latest as $r) {
            if (!in_array($r['status'], ['awaiting', 'returned'], true)) {
                continue;
            }
            $row = ['status' => $r['status'], 'prepared_by' => $r['prepared_by'], 'prepared_at' => $r['prepared_at'], 'amount' => $r['request_amount'] !== null ? (float) $r['request_amount'] : null];
            if ($r['status'] === 'returned') {
                $row += ['returned_by' => $r['decided_by'], 'returned_at' => $r['decided_at'], 'note' => $r['note']];
            }
            if ($canDecide) {
                $payload = json_decode((string) $r['payload'], true) ?: [];
                $row += ['hold_id' => (int) $r['id'], 'bank_name' => $payload['bankName'] ?? null, 'sup_bank_acc' => $payload['supBankAcc'] ?? null];
            }
            $out[$r['hotel_acc']] = $row;
        }
        return $out;
    }
}
