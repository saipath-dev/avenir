<?php

namespace App\Libraries\TourFlow;

/**
 * The complete record of a confirmed tour, from the inquiry as it arrived to where the tour stands
 * now (tf-record-r1): the email and the request as received; the itinerary; every quotation version
 * with what was emailed to the agent and the version the agent accepted; the confirmation — when,
 * by whom, and every re-confirmation after an amendment; the hotel stays and activity lines with
 * the letters that went and the replies; the vehicles with the driver and the guide; the
 * amendments (Edit Requests, Amend, Un-confirm, Cancel, Reopen); and the whole history, oldest
 * first. Everything is read from the one record — nothing is copied, nothing is written — and a
 * frozen version is shown as it was frozen: the costing a version carries is never changed after
 * Go for Quotation, so the quotation the agent confirmed stays exactly as it went out, whatever
 * is amended later.
 */
final class TourRecord
{
    private $db;
    private TourService $tours;
    private IntakeService $take;
    private QuoteService $quotes;
    private Activity $activity;

    public function __construct($db, string $user, TourService $tours, IntakeService $take, QuoteService $quotes, Activity $activity)
    {
        $this->db       = $db;
        $this->tours    = $tours;
        $this->take     = $take;
        $this->quotes   = $quotes;
        $this->activity = $activity;
    }

    /** @return array<string, mixed>|null everything the record page shows; null when there is no such tour */
    public function assemble(int $tourId, bool $seesPricing = false): ?array
    {
        $tour = $this->tours->get($tourId);
        if (!$tour) {
            return null;
        }
        $iid     = (int) $tour['intake_id'];
        $it      = $this->take->get($iid) ?: [];
        $details = null;
        try {
            $details = $this->tours->details($tourId);
        } catch (\Throwable $e) {
            $details = null;
        }
        $raw = $this->db->query('SELECT inquiry_no, status, source_subject, source_from, source_received_at, source_text, source_text_original, source_edited_at, source_edited_by, created_at, created_by, handler_name, agent_email, confirmed_at, confirmed_by FROM tf_intake WHERE id = ?', [$iid])->getRowArray() ?: [];

        // ---- the quotation versions, with what was emailed and which the agent accepted ----
        $versions = [];
        foreach (array_reverse($this->quotes->quotes($iid)) as $v) {   // oldest first
            $full = $this->quotes->quote((int) $v['id']);
            $cost = $full['cost'] ?? [];
            $v['emails']     = $this->quotes->emails((int) $v['id']);
            $v['max_pax']    = $v['max_pax'] ?? null;
            $v['sold_now']   = isset($tour['quote_id']) && (int) $tour['quote_id'] === (int) $v['id'];
            $v['frozen']     = [
                'nights' => $cost['nights'] ?? null, 'pax' => $cost['pax'] ?? null, 'arrival' => $cost['arrival'] ?? null, 'departure' => $cost['departure'] ?? null,
                'sell_usd' => $cost['markup']['sell_usd'] ?? $v['sell_usd'], 'per_person_usd' => $cost['markup']['per_person_usd'] ?? $v['per_person_usd'],
                'hotels' => array_map(static fn ($h) => ['day_no' => $h['day_no'] ?? null, 'area' => $h['area'] ?? null, 'hotel' => $h['hotel'] ?? null, 'room_type' => $h['room_type'] ?? null, 'meal_plan' => $h['meal_plan'] ?? null], $cost['hotels'] ?? []),
                'pax_prices' => PaxMatrix::forAgent($cost['pax_matrix'] ?? null),
            ];
            $versions[] = $v;
        }

        // ---- the confirmation and every re-confirmation, from the tour's own trail (never from a later edit) ----
        $confirmations = [];
        foreach (array_reverse($tour['events'] ?? []) as $ev) {   // oldest first
            $lane = (string) ($ev['lane'] ?? '');
            $isTour = ($lane === 'quotation' && in_array((string) $ev['event'], ['confirmed', 'reconfirmed', 'unconfirmed', 'unlocked', 'amend'], true))
                || ($lane === 'tour' && in_array((string) $ev['event'], ['cancelled', 'reopened', 'deleted', 'restored'], true));
            if ($isTour) {
                $note = (string) ($ev['note'] ?? '');
                $sold = preg_match('/Sold as version (\d+)/', $note, $m) ? (int) $m[1] : null;
                $confirmations[] = ['event' => $ev['event'], 'at' => $ev['at'], 'by' => $ev['by'], 'note' => $note, 'version' => $sold];
            }
        }
        $first = null;
        foreach ($confirmations as $c) {
            if ($c['event'] === 'confirmed') {
                $first = $c;
                break;
            }
        }
        $soldFirst = $first['version'] ?? (isset($tour['quote_version']) ? (int) $tour['quote_version'] : null);

        // ---- hotels, activities, transport as the desks hold them, with the letters and the crew ----
        $hotels = [];
        foreach ($tour['hotel_jobs'] as $h) {
            if (!empty($h['archived_at'])) {
                continue;
            }
            $h['messages'] = $this->tours->hotelMessages((int) $h['id'], 50);
            $hotels[] = $h;
        }
        $activities = array_values(array_filter($tour['service_jobs'] ?? [], static fn ($j) => empty($j['archived_at'])));
        $transport  = array_values(array_filter($tour['transport_jobs'] ?? [], static fn ($j) => empty($j['archived_at'])));
        foreach ($transport as &$tj) {
            // tf-masters-r3: a type standing in for a registration is said so by the master's own types
            $tj['reg_is_type'] = $this->tours->regIsType($tj);
        }
        unset($tj);

        // ---- the amendments: Edit Requests with their outcome, and the Board's own actions ----
        $edits = [];
        try {
            $edits = (new EditRequests($this->db, $this->take))->listFor($iid);
        } catch (\Throwable $e) {
            $edits = [];
        }

        // ---- the whole history, oldest first ----
        $history = $this->activity->timeline($iid, $tourId, 1000);
        // oldest first; within one second the edit lines come before the trail's own events, so "created" reads
        // before "confirmed" (the two trails number their lines separately, so their ids cannot be compared)
        usort($history, static fn ($a, $b) => [$a['at'], $a['kind'] === 'activity' ? 0 : 1, $a['id']] <=> [$b['at'], $b['kind'] === 'activity' ? 0 : 1, $b['id']]);
        if (!$seesPricing) {
            $history = array_values(array_filter($history, static fn ($r) => ($r['entity'] ?? '') !== 'pricing'));
        }

        return [
            'tour'          => $tour,
            'inquiry'       => $it,
            'received'      => $raw,
            'details'       => $details,
            'versions'      => $versions,
            'confirmations' => $confirmations,
            'sold_first'    => $soldFirst,
            'sold_now'      => isset($tour['quote_version']) ? (int) $tour['quote_version'] : null,
            'hotels'        => $hotels,
            'activities'    => $activities,
            'transport'     => $transport,
            'edits'         => $edits,
            'history'       => $history,
            'counts'        => ['versions' => count($versions), 'emails' => array_sum(array_map(static fn ($v) => count($v['emails']), $versions)), 'hotels' => count($hotels), 'activities' => count($activities), 'vehicles' => count($transport), 'edits' => count($edits), 'history' => count($history)],
        ];
    }
}
