<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\OfferRates;

/**
 * Tactical / Early Bird rate selection — master_files/offer_selection (tf-offers-r1)
 *
 * The privilege behind "Decide the payment rate" on the Tour Payment Dashboard: for one tour's stay at one hotel,
 * apply a tactical rate or early bird offer that fits it, or keep the published rate. Only holders see special rates
 * at payment at all (OfferRates::canDecide). One menu URL = one privilege — the route filter admits only the groups that hold master_files/offer_selection
 * (granted to the System Administrator when installed; others under Settings → Group Privileges). Every choice is
 * kept with who and when; the stay's payable rows are recomputed at once and the change is logged.
 * Quotations are never touched.
 *
 * tf-offers-r3: the review before a hotel payment is logged with who and when (POST reviewed), and the terms of a
 * tactical / early bird can be read from the review and the payment step (GET offer/<id>) — for holders only. Hotel
 * payments prepared by payment staff for a stay with an undecided special rate wait for the rate review (PaymentHold):
 * GET holds lists them, POST return sends one back; the release itself is TourAccountsController::release_rate_review.
 *
 * tf-paydesk-r7e: the three POST actions here (and the release) check the page's security token first (Csrf::guard).
 */
class OfferSelectionController extends BaseController
{
    public const URL = 'master_files/offer_selection';

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }
        return $this->request->getPost() ?: [];
    }

    /** Belt and braces beside the route filter: the special rates are for holders of the privilege only. */
    private function denied()
    {
        return OfferRates::currentUserCanDecide() ? null : $this->json(['status' => 403, 'message' => 'Special rates are shown only to holders of "Tactical / Early Bird rate selection".'], 403);
    }

    // POST master_files/offer_selection/reviewed {tour_id, hotel_acc, viewed: [offer names whose terms were opened]}
    public function reviewed()
    {
        // tf-paydesk-r7e: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'reviewed')) {
            return $tfCsrf;
        }
        try {
            if ($r = $this->denied()) {
                return $r;
            }
            $b = $this->body();
            $tourId = trim((string) ($b['tour_id'] ?? ''));
            $acc = trim((string) ($b['hotel_acc'] ?? ''));
            if ($tourId === '' || $acc === '') {
                return $this->json(['status' => 400, 'message' => 'Tour and hotel are both needed.']);
            }
            $o = new OfferRates(\Config\Database::connect());
            if (!$o->ready()) {
                return $this->json(['status' => 400, 'message' => 'Tactical and Early Bird selection appears after Install now (TourFlow schema 42).']);
            }
            $e = $o->recordReview($tourId, $acc, (string) session()->get('username'), array_slice((array) ($b['viewed'] ?? []), 0, 10));
            return $this->json(['status' => 200, 'message' => 'Review recorded.', 'data' => $e]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // GET master_files/offer_selection/offer/<plan id> — the terms of one tactical / early bird
    public function offer($id = 0)
    {
        try {
            if ($r = $this->denied()) {
                return $r;
            }
            $o = new OfferRates(\Config\Database::connect());
            if (!$o->ready()) {
                return $this->json(['status' => 400, 'message' => 'Tactical and Early Bird selection appears after Install now (TourFlow schema 42).']);
            }
            $t = $o->terms((int) $id);
            if ($t === null) {
                return $this->json(['status' => 404, 'message' => 'No tactical or early bird with that number.'], 404);
            }
            return $this->json(['status' => 200, 'data' => $t]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // GET master_files/offer_selection/holds — the hotel payments waiting for the rate review (tf-offers-r3)
    public function holds()
    {
        try {
            if ($r = $this->denied()) {
                return $r;
            }
            $db = \Config\Database::connect();
            return $this->json(['status' => 200, 'data' => \App\Libraries\HotelRates\PaymentHold::awaiting($db)]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // POST master_files/offer_selection/return {hold_id, note} — back to the staff who prepared it (tf-offers-r3)
    public function returnHold()
    {
        // tf-paydesk-r7e: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'returnHold')) {
            return $tfCsrf;
        }
        try {
            if ($r = $this->denied()) {
                return $r;
            }
            $b = $this->body();
            $h = \App\Libraries\HotelRates\PaymentHold::returnToStaff(\Config\Database::connect(), (int) ($b['hold_id'] ?? 0), (string) session()->get('username'), isset($b['note']) ? (string) $b['note'] : null);
            return $this->json(['status' => 200, 'message' => 'Returned to ' . $h['prepared_by'] . '. Nothing was requested.', 'data' => ['id' => (int) $h['id'], 'status' => $h['status']]]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // POST master_files/offer_selection/choose {tour_id, hotel_acc, choice: published|<plan id>, note}
    public function choose()
    {
        // tf-paydesk-r7e: the page's security token is checked before this action's own checks and before anything is read
        // or written (Csrf::guard). The global CSRF filter stays off; the permission filter still runs first.
        if ($tfCsrf = \App\Libraries\TourPayments\Csrf::guard($this->request, $this->response, 'choose')) {
            return $tfCsrf;
        }
        try {
            $b = $this->body();
            $tourId = trim((string) ($b['tour_id'] ?? ''));
            $acc = trim((string) ($b['hotel_acc'] ?? ''));
            $choice = trim((string) ($b['choice'] ?? ''));
            if ($tourId === '' || $acc === '' || $choice === '') {
                return $this->json(['status' => 400, 'message' => 'Tour, hotel and the rate chosen are all needed.']);
            }
            $o = new OfferRates(\Config\Database::connect());
            if (!$o->ready()) {
                return $this->json(['status' => 400, 'message' => 'Tactical and Early Bird selection appears after Install now (TourFlow schema 42).']);
            }
            $res = $o->choose($tourId, $acc, $choice, isset($b['note']) ? (string) $b['note'] : null, (string) session()->get('username'));
            $h = $res['hotel'];
            $msg = $res['choice'] === 'published' ? 'The published rate is kept for this hotel payment.' : '"' . $res['offer_name'] . '" is applied to this hotel payment.';
            if ($h) {
                $msg .= ' Payable now $ ' . number_format((float) $h['pay_total'], 2) . (isset($h['pub_total']) && $h['pub_total'] !== null ? ' (published $ ' . number_format((float) $h['pub_total'], 2) . ')' : '') . '.';
            }
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }
}
