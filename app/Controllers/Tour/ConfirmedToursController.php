<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\EmailTemplates;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\MailWords;
use App\Libraries\TourFlow\Staff;
use App\Libraries\TourFlow\TourService;

/**
 * Tour Desk → Confirmed Tours — tour/intake/confirmed (tf-confirmed-r1)
 *
 * One line per confirmed tour, straight from the Tour Desk bar, so a confirmed inquiry is found as a tour instead of
 * through the inquiry list: the tour and inquiry numbers, the agent and group, the passengers, the dates, where the
 * tour stands, and how far Hotel Bookings & Activities and Transport have got with it. A view, nothing more: it reads
 * the tours the desks already work on (TourService::deskTours, the same read the two desks' lists make — tf_tour and its
 * cards, no copy), changes nothing, and has no buttons that act on a tour. Confirm Tour, the desks' flows and the
 * Board are untouched.
 *
 * Privileges: no menu entry and no grant of its own. The page sits under tour/intake, so the existing route filter lets
 * in exactly the sign-ins that hold Inquiries — who already see every confirmed inquiry and its progress strip there.
 * Each line opens the confirmed inquiry; the Complete record, the programme and the desks are linked only for a sign-in
 * that holds the screen they live on (the Board, Hotel Bookings, Transport), so nobody reaches anything new. No money.
 */
class ConfirmedToursController extends BaseController
{
    public const URL  = 'tour/intake/confirmed';
    public const GATE = 'tour/intake';   // the privilege this page rides on (the route filter's prefix match)

    private ?array $menuData = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function menuData(): array
    {
        if ($this->menuData === null) {
            try {
                $this->menuData = (new MenuLoader())->loadMenuData();
            } catch (\Throwable $e) {
                $this->menuData = ['user_role' => session()->get('group_id'), 'allowed_menu_list' => []];
            }
        }
        return $this->menuData;
    }

    private function userId(): ?int
    {
        $id = (int) session()->get('user_id');
        return $id > 0 ? $id : null;
    }

    /** Whether the signed-in group holds a menu URL (the System Administrator holds every one). */
    private function holds(string $url): bool
    {
        $md = $this->menuData();
        if ((int) ($md['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ((array) ($md['allowed_menu_list'] ?? []) as $m) {
            $u = is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '');
            if (trim((string) $u, '/') === $url) {
                return true;
            }
        }
        return false;
    }

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    // GET tour/intake/confirmed
    public function index()
    {
        $data = $this->menuData();
        $data['user']          = $this->user();
        $data['page_url']      = self::URL;
        $data['intake_url']    = self::GATE;
        $data['installed']     = false;
        $data['handlers']      = [];
        // the other screens a line may link to — only those this sign-in already holds
        $data['can_hotel']     = $this->holds(TourDeskController::HOTEL);
        $data['can_transport'] = $this->holds(TourDeskController::TRANSPORT);
        $data['can_board']     = $this->holds(TourDeskController::BOARD);
        try {
            $db = $this->db();
            $v = Installer::installedVersion($db);
            $data['installed'] = $v !== null && $v >= Installer::SCHEMA_VERSION;
            if ($data['installed']) {
                $data['handlers'] = Staff::handlers($db);
            }
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/tour/confirmed_tours', $data);
    }

    // GET tour/intake/confirmed/data?q=&handler=&from=&to= — every confirmed tour (Admin-deleted ones excepted), nearest arrival first
    public function data()
    {
        try {
            $g = fn (string $k) => trim((string) ($this->request->getGet($k) ?? ''));
            $f = ['which' => 'all', 'q' => $g('q'), 'handler' => $g('handler'), 'from' => $g('from'), 'to' => $g('to')];
            foreach (['from', 'to'] as $k) {
                if ($f[$k] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f[$k])) {
                    $f[$k] = '';
                }
            }
            $db   = $this->db();
            $rows = (new TourService($db, $this->user()))->deskTours('hotel', $f);
            $out = [];
            $counts = ['live' => 0, 'completed' => 0, 'cancelled' => 0, 'all' => 0, 'changed' => 0];
            foreach ($rows as $t) {
                $stage = (string) $t['stage'];
                $group = $stage === 'cancelled' ? 'cancelled' : (in_array($stage, ['completed', 'closed'], true) ? 'completed' : 'live');
                $counts[$group]++;
                $counts['all']++;
                $s = (array) $t['strip'];
                $reopened = $this->reopenedSince($db, (int) $t['id'], $t['final_confirmed_at'] ?? null);
                if ($reopened !== null) {
                    $counts['changed'] = ($counts['changed'] ?? 0) + 1;
                }
                // the fields a line shows, and nothing else: no money, nothing a desk keeps to itself
                $out[] = [
                    'id' => $t['id'], 'tour_no' => $t['tour_no'], 'inquiry_no' => $t['inquiry_no'], 'intake_id' => $t['intake_id'],
                    'stage' => $stage, 'group' => $group, 'amending' => $t['amending'],
                    'agent_name' => $t['agent_name'], 'tour_name' => $t['tour_name'], 'pax_total' => $t['pax_total'],
                    'arrival_date' => $t['arrival_date'], 'departure_date' => $t['departure_date'], 'nights' => $t['nights'], 'dates_line' => $t['dates_line'],
                    'handler_name' => $t['handler_name'], 'confirmed_at' => $t['confirmed_at'], 'quote_version' => $t['quote_version'],
                    'cancelled_at' => $t['cancelled_at'], 'cancel_why' => $t['cancel_why'], 'problems' => $t['problems'],
                    'progress' => [
                        'hotels' => $s['hotels'] ?? null, 'activities' => $s['activities'] ?? null,
                        'hotel_work' => $s['hotel_work'] ?? null, 'reservations_confirmed_at' => $s['reservations_confirmed_at'] ?? null, 'reservations_confirmed_by' => $s['reservations_confirmed_by'] ?? null,
                        'vehicle' => $s['vehicle'] ?? null, 'driver' => $s['driver'] ?? null, 'guide' => $s['guide'] ?? null,
                        'transport_work' => $s['transport_work'] ?? null, 'transport_complete_at' => $s['transport_complete_at'] ?? null, 'transport_complete_by' => $s['transport_complete_by'] ?? null,
                    ],
                    // tf-workflow-r1: client acceptance and the final confirmation are two different things
                    'final_confirmed_at' => $t['final_confirmed_at'] ?? null,
                    'final_confirmed_by' => $t['final_confirmed_by'] ?? null,
                    // tf-confirmed-r2: a desk that reopened its work after the agent was told, and what the
                    // confirmation letter would carry
                    'reopened' => $reopened,
                    'ready' => self::readiness($s, $stage, $t['final_confirmed_at'] ?? null, $reopened),
                    'mail' => $this->mailPlan($db, (int) $t['intake_id'], (int) $t['id'],
                        self::readiness($s, $stage, $t['final_confirmed_at'] ?? null, $reopened), $t['final_confirmed_at'] ?? null, $reopened),
                ];
            }
            return $this->json(['status' => 200, 'data' => ['tours' => $out, 'counts' => $counts]]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    /**
     * tf-workflow-r1 — is this tour ready for its FINAL confirmation?
     *
     * Every required section must be green. A desk with nothing to do for this tour ('none') is not
     * a section that is waiting, so it does not hold the tour back; a desk still working ('pending')
     * does. A cancelled tour is never ready, and a tour already finally confirmed is not ready again.
     *
     * @param array<string, mixed> $strip the tour's own progress, as the desks have left it
     * @return array{ok:bool, waiting:list<string>, why:?string}
     */
    /**
     * tf-confirmed-r2: what stands between this tour and its confirmation to the agent. Three states:
     * nothing sent yet, sent and nothing has moved since, or sent and a desk has since reopened its work —
     * which has to be finished again before an updated confirmation goes out.
     */

    /**
     * tf-confirmed-r2: what the confirmed-itinerary letter would carry — the agent's address from the inquiry,
     * the issued quotation version (the itinerary the agent accepted) as the attachment, and the hotel vouchers
     * issued for the tour. Read only: it prepares, it never sends. Anything missing is named, so the screen can
     * say what has to be put right instead of refusing silently.
     */

    /**
     * GET notification/reopened_tours — the top bar's alert: tours the agent has already confirmed to the client
     * where a desk has since reopened its work. Reads only, answers only a sign-in holding Inquiries, and names
     * the tour, the department, who reopened it, when, why, and where the work now is.
     */
    public function reopenedAlert()
    {
        try {
            if (!$this->holds(self::GATE)) {
                return $this->json(['status' => 200, 'data' => ['allowed' => false, 'count' => 0, 'items' => []]]);
            }
            $db = $this->db();
            $rows = $db->query("SELECT t.id, t.tour_no, t.intake_id, t.handler_id, t.handler_name, t.final_confirmed_at,
                                       t.reservations_confirmed_at, t.transport_complete_at, i.inquiry_no, i.agent_name
                                  FROM tf_tour t LEFT JOIN tf_intake i ON i.id = t.intake_id
                                 WHERE t.final_confirmed_at IS NOT NULL AND t.stage <> 'cancelled' AND t.archived_at IS NULL
                                 ORDER BY t.arrival_date IS NULL, t.arrival_date LIMIT 100")->getResultArray();
            $me = $this->userId();
            $items = [];
            foreach ($rows as $t) {
                $r = $this->reopenedSince($db, (int) $t['id'], $t['final_confirmed_at']);
                if ($r === null) {
                    continue;
                }
                $where = in_array('Transport, driver and guide', $r['what'], true) && !in_array('Hotels and activities', $r['what'], true)
                    ? 'tour/transport_ops' : 'tour/hotel_bookings';
                $items[] = [
                    'tour_id' => (int) $t['id'], 'tour_no' => $t['tour_no'], 'inquiry_no' => $t['inquiry_no'],
                    'agent_name' => $t['agent_name'], 'handler_name' => $t['handler_name'],
                    'mine' => $me !== null && (int) $t['handler_id'] === (int) $me,
                    'what' => implode(' and ', $r['what']), 'by' => $r['by'], 'at' => $r['at'], 'why' => $r['why'],
                    'told_at' => $t['final_confirmed_at'], 'work_url' => base_url($where . '?tour=' . (int) $t['id']),
                ];
            }
            usort($items, static fn ($a, $b) => ($b['mine'] <=> $a['mine']) ?: strcmp((string) $b['at'], (string) $a['at']));
            return $this->json(['status' => 200, 'data' => [
                'allowed' => true, 'count' => count($items),
                'mine' => count(array_filter($items, static fn ($i) => $i['mine'])),
                'items' => array_slice($items, 0, 20), 'url' => base_url(self::URL),
            ]]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 200, 'data' => ['allowed' => false, 'count' => 0, 'items' => []]]);
        }
    }

    /**
     * tf-mailtpl-r3 (4 Nov 2026, Shantha): the letter this line would send, drawn from the SAVED wording.
     *
     * Which wording depends on where the tour actually is, never on who is looking:
     *   - every required section green AND the handling agent has pressed Final confirm, nothing reopened
     *       → "Final confirmation — all services secured": the letter may say the services are secured.
     *   - anything else — a section still pending, or a section reopened since the agent was told —
     *       → "Tour acceptance — reservations starting": the letter says the services are being requested
     *         and are NOT yet secured, and the screen says why the final confirmation is not available.
     * Nothing here sends; it only prepares what the Send box would carry.
     */
    private function mailPlan($db, int $intakeId, int $tourId, ?array $ready = null, $finalAt = null, ?array $reopened = null): array
    {
        $out = ['to' => null, 'agent' => null, 'subject' => null, 'docs' => [], 'missing' => []];
        try {
            $it = $db->query('SELECT inquiry_no, agent_name, agent_email, tour_name, arrival_date, departure_date FROM tf_intake WHERE id = ?', [$intakeId])->getRowArray();
            $tr = $db->query('SELECT tour_no FROM tf_tour WHERE id = ?', [$tourId])->getRowArray();
            if (!$it) {
                return $out;
            }
            $out['agent'] = $it['agent_name'];
            $out['to']    = trim((string) ($it['agent_email'] ?? '')) ?: null;
            if ($out['to'] === null) {
                $out['missing'][] = 'The agent has no e-mail address on the inquiry. Put it on the inquiry first.';
            }
            $dates = $it['arrival_date'] ? date('j M Y', strtotime((string) $it['arrival_date']))
                . ($it['departure_date'] ? ' – ' . date('j M Y', strtotime((string) $it['departure_date'])) : '') : '';
            $ref = ($tr['tour_no'] ?? '') . ($it['inquiry_no'] ? ' / ' . $it['inquiry_no'] : '');
            // the stage decides the wording, and the wording comes from Master Files, not from here
            $secured = $finalAt !== null && $finalAt !== '' && $reopened === null && ($ready === null || empty($ready['waiting']));
            $group   = $secured ? 'final_confirmation' : 'confirmation';
            $out['stage'] = $secured ? 'final' : 'accepted';
            $out['template_group'] = EmailTemplates::CATEGORIES[$group] ?? $group;
            $out['subject'] = trim('Confirmed: ' . ($it['tour_name'] ? $it['tour_name'] . ' — ' : '') . $dates . ' (' . trim($ref, ' /') . ')');
            $out['greeting'] = 'Dear ' . ($it['agent_name'] ?: 'Sir / Madam') . ",\n\n";
            $out['body'] = 'We are pleased to confirm ' . ($it['tour_name'] ?: 'the tour') . ($dates ? ', ' . $dates : '')
                . ', under our reference ' . trim($ref, ' /') . ".\n\n"
                . "The confirmed itinerary is attached.\n\n"
                . "Please let us know if anything needs to be adjusted.";
            try {
                $vars  = (new MailWords($db))->forIntake($intakeId, ['name' => $this->user()]);
                $list  = EmailTemplates::filled((new EmailTemplates($db))->forWindow($group), $vars);
                $start = EmailTemplates::starting($list, $group);
                if ($start && trim((string) $start['message']) !== '') {
                    $out['subject']  = trim((string) $start['subject']) !== '' ? (string) $start['subject'] : $out['subject'];
                    $out['body']     = EmailTemplates::withoutSignOff((string) $start['message']);
                    $out['greeting'] = '';
                    $out['template'] = (string) $start['name'];
                    foreach ((array) ($start['missing'] ?? []) as $w) {
                        $out['missing'][] = 'The wording asks for ' . $w . ', and the record has none.';
                    }
                }
            } catch (\Throwable $e) {
                // no saved wording on this install: the plain letter above, which claims nothing
            }
            if (!$secured) {
                $out['missing'][] = $reopened !== null
                    ? 'This letter cannot say the services are secured: ' . $reopened['line'] . '. Finish that work and press Final confirm again first.'
                    : (($finalAt === null || $finalAt === '')
                        ? 'This letter cannot say the services are secured until every required section is confirmed by its team and Final confirm is pressed.'
                        : 'This letter cannot say the services are secured while a required section is still pending.');
            }
            $q = $db->query("SELECT id, version FROM tf_quote WHERE intake_id = ? AND status = 'issued' ORDER BY version DESC LIMIT 1", [$intakeId])->getRowArray();
            if ($q) {
                $out['docs'][] = ['key' => 'quote:' . (int) $q['id'], 'label' => 'Confirmed itinerary — version ' . $q['version'], 'on' => true];
            } else {
                $out['missing'][] = 'No issued quotation version to send as the confirmed itinerary.';
            }
            // tf-confirmed-r3: every stay whose voucher has been ISSUED can be attached, whether or not the
            // voucher was ever e-mailed to the hotel — the agent should not have to mail the hotel first to be
            // able to send the client their voucher. A stay still waiting for the hotel is named, not offered.
            foreach ($db->query("SELECT id, hotel_name, status, voucher_no, check_in FROM tf_hotel_job
                                  WHERE tour_id = ? AND archived_at IS NULL ORDER BY seq, check_in", [$tourId])->getResultArray() as $j) {
                if ((string) $j['status'] === 'voucher_issued') {
                    $out['docs'][] = ['key' => 'hvoucher:' . (int) $j['id'],
                        'label' => 'Hotel voucher ' . ($j['voucher_no'] ?: '') . ' — ' . $j['hotel_name'], 'on' => false];
                } elseif (!in_array((string) $j['status'], ['cancelled'], true)) {
                    $out['missing'][] = $j['hotel_name'] . ': no voucher issued yet (' . str_replace('_', ' ', (string) $j['status']) . ') — it cannot be attached.';
                }
            }
        } catch (\Throwable $e) {
            // a line that cannot be prepared still shows; the screen says what is missing
        }
        return $out;
    }

    private static function readiness(array $strip, string $stage, $finalAt, ?array $reopened = null): array
    {
        if ($stage === 'cancelled') {
            return ['ok' => false, 'waiting' => [], 'why' => 'This tour is cancelled.', 'again' => false];
        }
        $waiting = [];
        if (($strip['hotel_work'] ?? 'none') === 'pending') {
            $waiting[] = 'Hotels and activities';
        }
        if (($strip['transport_work'] ?? 'none') === 'pending') {
            $waiting[] = 'Transport, driver and guide';
        }
        $sent = $finalAt !== null && $finalAt !== '';
        if ($sent && $reopened === null) {
            return ['ok' => false, 'waiting' => [], 'why' => 'The final confirmation has already been sent.', 'again' => false];
        }
        if ($waiting !== []) {
            return ['ok' => false, 'waiting' => $waiting,
                'why' => implode(' and ', $waiting) . ' still to be confirmed by the team.', 'again' => $sent];
        }
        return ['ok' => true, 'waiting' => [], 'why' => null, 'again' => $sent];
    }

    /**
     * tf-confirmed-r2: a desk that took its completion mark off AFTER the agent was told the tour was confirmed.
     * Read from the tour's own trail, so nothing is stored twice: which desk, who, when and the reason they gave.
     * Null while nothing has moved since the confirmation went out.
     */
    private function reopenedSince($db, int $tourId, $finalAt): ?array
    {
        if ($finalAt === null || $finalAt === '') {
            return null;
        }
        try {
            $rows = $db->query("SELECT event, note, `by`, `at` FROM tf_tour_event
                                 WHERE tour_id = ? AND event IN ('reservations_unconfirmed', 'transport_incomplete') AND `at` > ?
                                 ORDER BY id DESC LIMIT 10", [$tourId, $finalAt])->getResultArray();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$rows) {
            return null;
        }
        $name = ['reservations_unconfirmed' => 'Hotels and activities', 'transport_incomplete' => 'Transport, driver and guide'];
        $what = [];
        foreach ($rows as $r) {
            $w = $name[$r['event']] ?? $r['event'];
            if (!in_array($w, $what, true)) {
                $what[] = $w;
            }
        }
        $last = $rows[0];
        return ['what' => $what, 'by' => $last['by'], 'at' => $last['at'], 'why' => $last['note'],
                'line' => implode(' and ', $what) . ' was reopened by ' . ($last['by'] ?: 'a desk') . ' on '
                        . substr((string) $last['at'], 0, 16) . ($last['note'] ? ' — ' . $last['note'] : '')];
    }


    /**
     * POST tour/intake/confirmed/(:num)/final — the Confirmed Tour Handler's final confirmation.
     *
     * It is refused unless every required section is green, so it can never go out ahead of the
     * teams. It records who pressed it and when; it sends nothing by itself — the handler sends the
     * confirmed itinerary from the same panel, and that e-mail is kept on Communications & History
     * with its attachments, like every other e-mail on the record.
     */
    public function finalConfirm($tourId)
    {
        try {
            $db  = $this->db();
            $svc = new TourService($db, $this->user());
            $t   = $db->query('SELECT id, tour_no, intake_id, stage, final_confirmed_at FROM tf_tour WHERE id = ?', [(int) $tourId])->getRowArray();
            if (!$t) {
                return $this->json(['status' => 404, 'message' => 'That tour was not found.']);
            }
            $strip    = $svc->stripFor((int) $tourId) ?: [];
            $reopened = $this->reopenedSince($db, (int) $tourId, $t['final_confirmed_at']);
            $r        = self::readiness($strip, (string) $t['stage'], $t['final_confirmed_at'], $reopened);
            if (!$r['ok']) {
                return $this->json(['status' => 409, 'message' => $r['why'] ?: 'This tour is not ready for its final confirmation yet.']);
            }
            $again = !empty($r['again']);
            $now   = date('Y-m-d H:i:s');
            // tf-confirmed-r2: the guard is the value this read saw, so two people cannot both write it
            $prev = $t['final_confirmed_at'];
            if ($prev === null) {
                $db->query('UPDATE tf_tour SET final_confirmed_at = ?, final_confirmed_by = ? WHERE id = ? AND final_confirmed_at IS NULL',
                    [$now, mb_substr($this->user(), 0, 100), (int) $tourId]);
            } else {
                $db->query('UPDATE tf_tour SET final_confirmed_at = ?, final_confirmed_by = ? WHERE id = ? AND final_confirmed_at = ?',
                    [$now, mb_substr($this->user(), 0, 100), (int) $tourId, $prev]);
            }
            if ((int) $db->affectedRows() === 0) {
                return $this->json(['status' => 409, 'message' => 'Someone confirmed this tour a moment ago. Nothing was changed.']);
            }
            try {
                $svc->event((int) $t['intake_id'], (int) $tourId, 'tour', (int) $tourId, $again ? 'final_reconfirmed' : 'final_confirmed', null, null,
                    $again
                        ? 'Updated confirmation after ' . ($reopened['line'] ?? 'a desk reopened its work') . '. Every required section is confirmed again, and ' . $this->user() . ' confirmed the tour to the agent.'
                        : 'Final confirmation: every required section was confirmed by its team, and ' . $this->user() . ' confirmed the tour to the agent.');
            } catch (\Throwable $e) {
                // the confirmation stands; its own trail line is best effort
            }
            return $this->json(['status' => 200,
                'message' => 'Tour ' . $t['tour_no'] . ($again ? ' is confirmed again.' : ' is confirmed.') . ' Send the confirmed itinerary to the agent from this line; it is kept on the record.',
                'data' => ['tour_id' => (int) $tourId, 'intake_id' => (int) $t['intake_id'], 'final_confirmed_at' => $now, 'final_confirmed_by' => $this->user(), 'again' => $again]]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

}
