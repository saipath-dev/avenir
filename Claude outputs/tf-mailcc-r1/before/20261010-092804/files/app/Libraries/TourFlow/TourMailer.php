<?php

namespace App\Libraries\TourFlow;

/**
 * tf-tourmail-r1 (10 Oct 2026) — the hotels' booking requests go by e-mail when a tour is confirmed, as the
 * restaurants' booking vouchers already do (Shantha: "send the reservation mails automatically to the hotels and
 * restaurants at the time we press the confirm button … we need to see the sent mails on the system, and if any mail
 * is not sent the system has to say so").
 *
 *   plan        before Confirm Tour (from the inquiry) or on the Hotel Desk (from the tour's cards): every stay, the
 *               address its request goes to, what the letter will say about rooms and crew rooms, and why one cannot go
 *   sendHotels  after OK: each stay's request e-mailed from the signed-in person's mailbox; a stay that cannot go
 *               (no address, no hotel named, no mailbox, the server refused, left out) is kept as "not sent" with the
 *               reason — it stays on the "Mails not sent" alert until it is sent or recorded as sent another way
 *   log         every hotel and restaurant mail, per tour or for all tours, with the copy as it went
 *   unsent      what has not gone, for the alert in the top bar
 *
 * Nothing here touches a rate, the Hotel Master or the restaurants' master. A stay already requested, confirmed or
 * vouchered is never sent again from here.
 */
final class TourMailer
{
    public const VERSION = 'tf-tourmail-r1';

    private $db;
    private string $user;
    private ?int $userId;
    private string $actor;

    public function __construct($db, string $user, ?int $userId, string $actor)
    {
        $this->db = $db;
        $this->user = $user;
        $this->userId = $userId;
        $this->actor = $actor;
    }

    public function sender(): array
    {
        return Staff::contact($this->db, $this->userId, $this->user);
    }

    // =====================================================================
    //  The plan: what each hotel gets and where it goes
    // =====================================================================

    /** Before Confirm Tour: the stays as Confirm makes them (TourService::stays), each with its address. */
    public function previewForIntake(array $it): array
    {
        $stays = TourService::stays($it);
        // an amendment confirmed again: the stays already requested keep their request — nothing goes twice
        $old = [];
        try {
            foreach ($this->db->query("SELECT j.id, j.day_from, j.hotel_acc, j.hotel_id, j.status,
                        (SELECT COUNT(*) FROM tf_hotel_message m WHERE m.job_id = j.id AND m.kind = 'request' AND m.state IN ('sent','recorded')) AS went
                     FROM tf_hotel_job j JOIN tf_tour t ON t.id = j.tour_id WHERE t.intake_id = ? AND j.archived_at IS NULL", [(int) $it['id']])->getResultArray() as $r) {
                $old[(int) $r['day_from']] = $r;
            }
        } catch (\Throwable $e) {
            $old = [];
        }
        $crew = self::crewPlan($it);
        $out = [];
        foreach ($stays as $s) {
            $addr = $this->hotelAddress($s['hotel_id'] ?? null, $s['hotel_acc'] ?? null);
            $prev = $old[(int) $s['day_from']] ?? null;
            $same = $prev && ((string) $prev['hotel_acc'] === (string) ($s['hotel_acc'] ?? '') || ((int) $prev['hotel_id'] > 0 && (int) $prev['hotel_id'] === (int) ($s['hotel_id'] ?? 0)));
            $already = $same && (!in_array((string) $prev['status'], ['to_book', 'problem'], true) || (int) $prev['went'] > 0);
            $out[] = $this->row([
                'job_id' => $same ? (int) $prev['id'] : null, 'day_from' => (int) $s['day_from'], 'day_to' => (int) $s['day_to'],
                'hotel_name' => $s['hotel_name'], 'area' => $s['area'], 'check_in' => $s['check_in'], 'check_out' => $s['check_out'], 'nights' => (int) $s['nights'],
                'rooms' => $s['rooms'], 'rooms_note' => $s['rooms_note'] ?? ($it['rooms_note'] ?? null), 'meal_plan' => $s['meal'],
                'needs_hotel' => !empty($s['needs_hotel']), 'needs_meal' => !empty($s['needs_meal']),
                'email' => $addr['email'], 'email_source' => $addr['source'], 'status' => $already ? (string) $prev['status'] : 'to_book', 'already' => $already,
                'driver' => self::crewDefault($crew['driver'], (int) $s['day_from'], (int) $s['day_to']),
                'guide' => self::crewDefault($crew['guide'], (int) $s['day_from'], (int) $s['day_to']),
            ]);
        }
        return $this->finish($it, $out, $crew);
    }

    /** On the Hotel Desk, for a tour already confirmed: its cards, each with the address its next letter goes to. */
    public function planForTour(int $tourId, array $it): array
    {
        $crew = self::crewPlan($it);
        $legacy = self::legacyCrewNote($it);
        $out = [];
        foreach ($this->cards($tourId) as $j) {
            $addr = $this->cardAddress($j);
            $already = !in_array((string) $j['status'], ['to_book', 'problem'], true) || (int) $j['went'] > 0;
            $text = trim((string) ($j['crew_rooms'] ?? ''));
            // the crew line as Confirm wrote it before r1 (the whole tour's nights on every stay) is replaced by the
            // stay's own nights; a line a person wrote on the card is read as it stands
            $driver = ($text === '' || $text === $legacy) ? self::crewDefault($crew['driver'], (int) $j['day_from'], (int) $j['day_to']) : self::crewFromText($text, 'Driver', (int) $j['nights']);
            $guide  = ($text === '' || $text === $legacy) ? self::crewDefault($crew['guide'], (int) $j['day_from'], (int) $j['day_to']) : self::crewFromText($text, 'Guide', (int) $j['nights']);
            $out[] = $this->row([
                'job_id' => (int) $j['id'], 'day_from' => (int) $j['day_from'], 'day_to' => (int) $j['day_to'],
                'hotel_name' => $j['hotel_name'], 'area' => $j['area'], 'check_in' => $j['check_in'], 'check_out' => $j['check_out'], 'nights' => (int) $j['nights'],
                'rooms' => $j['rooms'] !== null ? (int) $j['rooms'] : null, 'rooms_note' => $j['rooms_note'], 'meal_plan' => $j['meal_plan'],
                'needs_hotel' => (int) $j['needs_hotel'] === 1, 'needs_meal' => (int) $j['needs_meal'] === 1,
                'email' => $addr['email'], 'email_source' => $addr['source'], 'status' => (string) $j['status'], 'already' => $already,
                'last' => $j['last_state'] ? ['state' => $j['last_state'], 'at' => $j['last_at'], 'to' => $j['last_to'], 'error' => $j['last_error']] : null,
                'driver' => $driver, 'guide' => $guide,
            ]);
        }
        return $this->finish($it, $out, $crew);
    }

    /** The plan as the screen shows it: the room type put right where the agent asked for another (and said so). */
    private function finish(array $it, array $out, array $crew): array
    {
        $rooms = self::roomCheck($it, $out);
        if ($rooms['suggest'] !== null) {
            foreach ($out as &$r) {
                if ($r['already'] && $r['rooms_note'] !== null && self::swapBeds($r['rooms_note'], $rooms['suggest']) !== $r['rooms_note']) {
                    // requested already with the other type: not changed or sent again from here — said, for the desk
                    $r['rooms_stale'] = true;
                }
                if (!$r['already'] && $r['rooms_note'] !== null) {
                    $fixed = self::swapBeds($r['rooms_note'], $rooms['suggest']);
                    if ($fixed !== $r['rooms_note']) {
                        $r['rooms_was'] = $r['rooms_note'];
                        $r['rooms_note'] = $fixed;
                        $r = $this->row($r);
                    }
                }
            }
            unset($r);
        }
        return ['sender' => $this->sender(), 'stays' => $out, 'rooms' => $rooms, 'crew' => self::crewSummary($crew), 'cc' => ''];
    }

    private function row(array $r): array
    {
        $why = null;
        if ($r['already']) {
            $why = null;
        } elseif ($r['needs_hotel']) {
            $why = 'No hotel is named for these nights.';
        } elseif ($r['needs_meal']) {
            $why = 'The meal plan is not stated for these nights.';
        } elseif (!$r['email']) {
            $why = 'No e-mail address for ' . ($r['hotel_name'] ?: 'this hotel') . ' (Hotel Master → Reservations e-mail, or type one here).';
        }
        $r['rooms_line'] = trim(($r['rooms'] !== null ? (int) $r['rooms'] . ' room' . ((int) $r['rooms'] === 1 ? '' : 's') : '') . ($r['rooms_note'] ? ' — ' . $r['rooms_note'] : ''));
        $r['blocked'] = $why;
        return $r;
    }

    /** The tour's cards with the last request letter on each. */
    private function cards(int $tourId): array
    {
        return $this->db->query("SELECT j.*,
                (SELECT COUNT(*) FROM tf_hotel_message m WHERE m.job_id = j.id AND m.kind = 'request' AND m.state IN ('sent','recorded')) AS went,
                (SELECT m.state FROM tf_hotel_message m WHERE m.job_id = j.id AND m.kind = 'request' ORDER BY m.id DESC LIMIT 1) AS last_state,
                (SELECT m.`at` FROM tf_hotel_message m WHERE m.job_id = j.id AND m.kind = 'request' ORDER BY m.id DESC LIMIT 1) AS last_at,
                (SELECT m.to_addr FROM tf_hotel_message m WHERE m.job_id = j.id AND m.kind = 'request' ORDER BY m.id DESC LIMIT 1) AS last_to,
                (SELECT m.error FROM tf_hotel_message m WHERE m.job_id = j.id AND m.kind = 'request' ORDER BY m.id DESC LIMIT 1) AS last_error
             FROM tf_hotel_job j WHERE j.tour_id = ? AND j.archived_at IS NULL ORDER BY j.seq, j.id", [$tourId])->getResultArray();
    }

    /** The address a stay's letter goes to — as the Hotel Desk's letter box reads it: the address the last letter on the stay went to, else the Hotel Master's, else the supplier record's. */
    private function cardAddress(array $j): array
    {
        try {
            $r = $this->db->query("SELECT to_addr FROM tf_hotel_message WHERE job_id = ? AND channel = 'email' AND to_addr IS NOT NULL AND to_addr <> '' ORDER BY id DESC LIMIT 1", [(int) $j['id']])->getRowArray();
            if ($r && self::email($r['to_addr'])) {
                return ['email' => self::email($r['to_addr']), 'source' => 'stay'];
            }
        } catch (\Throwable $e) {
        }
        return $this->hotelAddress($j['hotel_id'] ?? null, $j['hotel_acc'] ?? null);
    }

    /** The Hotel Master's Reservations e-mail, else the supplier record's (by the stay's code, or the master's primary supplier). Read only. */
    public function hotelAddress($hotelId, $acc): array
    {
        $clean = static fn ($v) => trim((string) ($v ?? '')) !== '' && !in_array(strtoupper(trim((string) $v)), ['0', '-', 'N/A', 'NA', 'NIL', 'NONE', 'NULL'], true) ? trim((string) $v) : null;
        $acc = trim((string) ($acc ?? ''));
        if ((int) $hotelId > 0) {
            try {
                $hm = $this->db->query('SELECT reservations_email, primary_sup_acc_no FROM hr2_hotel WHERE id = ?', [(int) $hotelId])->getRowArray();
                if ($hm && ($m = self::email($clean($hm['reservations_email'] ?? null)))) {
                    return ['email' => $m, 'source' => 'master'];
                }
                if ($acc === '' && $hm) {
                    $acc = trim((string) ($hm['primary_sup_acc_no'] ?? ''));
                }
            } catch (\Throwable $e) {
            }
        }
        if ($acc !== '') {
            try {
                $sup = $this->db->query('SELECT email FROM supplier WHERE Sup_Acc_No = ? LIMIT 1', [$acc])->getRowArray();
                if ($sup && ($m = self::email($clean($sup['email'] ?? null)))) {
                    return ['email' => $m, 'source' => 'supplier'];
                }
            } catch (\Throwable $e) {
            }
        }
        return ['email' => null, 'source' => null];
    }

    // ---- crew rooms: each stay's own nights (not the whole tour's on every letter) ----

    /** The driver's and the guide's room requirement, night by night, from the inquiry's accommodation lines. */
    public static function crewPlan(array $it): array
    {
        $out = [];
        foreach (['driver', 'guide'] as $kind) {
            $a = $it['accom'][$kind] ?? null;
            $req = $a && (int) ($a['required'] ?? 0) === 1;
            $days = [];
            foreach ((array) ($a['lines'] ?? []) as $l) {
                if (isset($l['day_no'])) {
                    $days[] = (int) $l['day_no'];
                }
            }
            $days = array_values(array_unique($days));
            $set = $req && $a['nights'] !== null ? (int) $a['nights'] : null;
            // "set by hand" to fewer nights than the tour has: the inquiry does not say which nights — the person says
            $choose = $req && (string) ($a['nights_by'] ?? '') === 'override' && $set !== null && $days && $set < count($days);
            $out[$kind] = ['required' => $req, 'nights' => $set, 'days' => $days, 'choose' => $choose];
        }
        return $out;
    }

    private static function crewDefault(array $c, int $from, int $to): int
    {
        if (!$c['required'] || $c['choose'] || $c['nights'] === 0) {
            return 0;
        }
        if (!$c['days']) {
            return max(0, $to - $from + 1);
        }
        return count(array_filter($c['days'], static fn ($d) => $d >= $from && $d <= $to));
    }

    private static function crewSummary(array $crew): array
    {
        $out = [];
        foreach ($crew as $kind => $c) {
            $out[$kind] = ['required' => $c['required'], 'nights' => $c['nights'], 'choose' => $c['choose'], 'tour_nights' => count($c['days'])];
        }
        return $out;
    }

    /** The line Confirm Tour wrote on every card before r1 (TourService::crewRoomsNote), to recognise it. */
    private static function legacyCrewNote(array $it): ?string
    {
        $bits = [];
        foreach (['driver' => 'Driver', 'guide' => 'Guide'] as $kind => $label) {
            $a = $it['accom'][$kind] ?? null;
            if ($a && (int) ($a['required'] ?? 0) === 1) {
                $bits[] = $label . ' room required' . (!empty($a['nights']) ? " ({$a['nights']} night(s))" : '');
            }
        }
        return $bits ? mb_substr(implode('; ', $bits), 0, 255) : null;
    }

    private static function crewFromText(string $text, string $label, int $stayNights): int
    {
        if (!preg_match('/' . $label . ' room required(?:\s*\((\d+)\s*night)?/i', $text, $m)) {
            return 0;
        }
        return isset($m[1]) && $m[1] !== '' ? (int) $m[1] : $stayNights;
    }

    /** "Driver room required (2 nights); Guide room required (1 night)" — the stay's own nights. */
    public static function crewText(int $driver, int $guide): ?string
    {
        $bits = [];
        foreach (['Driver' => $driver, 'Guide' => $guide] as $label => $n) {
            if ($n > 0) {
                $bits[] = $label . ' room required (' . $n . ' night' . ($n === 1 ? '' : 's') . ')';
            }
        }
        return $bits ? implode('; ', $bits) : null;
    }

    // ---- rooms: the agent's words beside the room mix the letters carry ----

    /**
     * The agent asked for twin beds and the rooms are entered as DBL (or the other way round): offered as a change to the
     * letters only — the quotation and its costing are not touched (a double and a twin are costed alike).
     */
    public static function roomCheck(array $it, array $stays): array
    {
        $note = trim((string) ($it['rooms_note'] ?? ''));
        $all = implode(' ', array_map(static fn ($s) => (string) ($s['rooms_note'] ?? ''), $stays));
        $dbl = (bool) preg_match('/\bDBL\b/', $all);
        $twn = (bool) preg_match('/\bTWN\b/', $all);
        $askTwin = (bool) preg_match('/\btwin|\btwn\b/i', $note);
        $askDbl = (bool) preg_match('/\bdouble|\bdbl\b/i', $note);
        $suggest = null;
        if ($askTwin && !$askDbl && $dbl && !$twn) {
            $suggest = 'twin';
        } elseif ($askDbl && !$askTwin && $twn && !$dbl) {
            $suggest = 'double';
        }
        return ['agent_note' => $note !== '' ? $note : null, 'has_dbl' => $dbl, 'has_twn' => $twn, 'suggest' => $suggest];
    }

    private static function swapBeds(?string $note, string $to): ?string
    {
        if ($note === null) {
            return null;
        }
        return $to === 'twin' ? preg_replace('/\bDBL\b/', 'TWN', $note) : preg_replace('/\bTWN\b/', 'DBL', $note);
    }

    // =====================================================================
    //  Sending
    // =====================================================================

    /**
     * The tour's requests, as the person saw them and pressed OK.
     *
     * @param array $choice ['beds' => 'twin'|'double'|null, 'cc' => string,
     *                       'stays' => [['day_from' => n, 'send' => bool, 'email' => string, 'driver' => n, 'guide' => n], …]]
     * @param callable $render fn(int $jobId, array $sender): array{subject: string, body: string}
     * @return array<int, array{job_id:int, hotel:string, status:string, to:?string, why:?string}>
     *         status: sent | kept (already requested — not sent again) | not_sent
     */
    public function sendHotels(TourService $svc, int $tourId, array $it, array $choice, array $sender, callable $render): array
    {
        @set_time_limit(600);
        $serverDown = null;   // the mail server could not be reached or refused the login: the rest are not tried
        $byDay = [];
        foreach ((array) ($choice['stays'] ?? []) as $c) {
            if (isset($c['day_from'])) {
                $byDay[(int) $c['day_from']] = $c;
            }
        }
        // Shantha, 10 Oct: where the rooms were entered as another type than the agent asked for, the letters carry the
        // type the agent asked for, and the change is noted on the stay — the quotation is not touched
        $roomCheck = self::roomCheck($it, array_map(static fn ($j) => ['rooms_note' => $j['rooms_note']], $this->cards($tourId)));
        $beds = $roomCheck['suggest'];
        $bedsWhy = $beds ? ' — the agent asked for "' . $roomCheck['agent_note'] . '"' : '';
        $cc = array_values(array_filter(array_map(static fn ($x) => self::email($x), preg_split('/[;,\s]+/', (string) ($choice['cc'] ?? '')) ?: [])));
        $out = [];
        $intakeId = $this->intakeOf($tourId);
        foreach ($this->cards($tourId) as $j) {
            $id = (int) $j['id'];
            $hotel = (string) ($j['hotel_name'] ?: 'a hotel');
            $c = $byDay[(int) $j['day_from']] ?? null;
            if ($c === null) {
                continue;   // not on the list the person saw: left as it is
            }
            $already = !in_array((string) $j['status'], ['to_book', 'problem'], true) || (int) $j['went'] > 0;
            if ($already) {
                $out[] = ['job_id' => $id, 'hotel' => $hotel, 'status' => 'kept', 'to' => $j['last_to'], 'why' => 'Requested already — not sent again.'];
                continue;
            }
            // the letter's crew and room lines as the person set them (this stay only; the quotation is not touched)
            try {
                $this->applyLines($svc, $j, (int) ($c['driver'] ?? 0), (int) ($c['guide'] ?? 0), $beds, $bedsWhy);
            } catch (\Throwable $e) {
                // the lines stay as they were; the letter still goes
            }
            $to = self::email($c['email'] ?? null);
            $why = null;
            if (empty($c['send'])) {
                $why = 'Left out by ' . $this->actor . ' when the requests were sent.';
            } elseif ((int) $j['needs_hotel'] === 1) {
                $why = 'No hotel is named for these nights.';
            } elseif ((int) $j['needs_meal'] === 1) {
                $why = 'The meal plan is not stated for these nights.';
            } elseif ($to === null) {
                $why = trim((string) ($c['email'] ?? '')) !== '' ? '"' . trim((string) $c['email']) . '" is not an e-mail address.' : 'No e-mail address for ' . $hotel . '.';
            } elseif (empty($sender['can_send'])) {
                $why = 'Your mailbox is not set up to send from (My Profile → Mailbox).';
            } elseif ($serverDown !== null) {
                $why = 'Not tried — ' . $serverDown;
            }
            $subject = null;
            $body = null;
            if ($why === null) {
                try {
                    $doc = $render($id, $sender);
                    $subject = $doc['subject'];
                    $body = $doc['body'];
                    $r = HotelMail::send($to, $subject, $body, $cc);
                    if (!empty($r['ok'])) {
                        $svc->hotelRequestSent($id, 'email', $to, implode(', ', $cc), $subject, $body, $this->actor);
                        $out[] = ['job_id' => $id, 'hotel' => $hotel, 'status' => 'sent', 'to' => $to, 'why' => null];
                        continue;
                    }
                    $why = (string) ($r['error'] ?? 'The mail server did not take it.');
                } catch (\Throwable $e) {
                    $why = MailServer::readable($e->getMessage());
                }
                if (preg_match('/refused the login|could not be reached|could not be found|timed out|could not connect|Your account has no mailbox/i', (string) $why)) {
                    $serverDown = 'the mail server failed on the request before: ' . preg_replace('/^Not sent:\s*/', '', (string) $why);
                }
            }
            // not sent: kept on the stay with the reason — it is on the "Mails not sent" alert until it goes
            try {
                $svc->recordHotelMessage($id, 'request', 'email', $to, $cc ? implode(', ', $cc) : null, $subject, $body, 'failed', mb_substr((string) $why, 0, 400), $this->actor);
                $svc->event($intakeId, $tourId, 'hotel', $id, 'request_not_sent', null, null, 'Booking request not sent: ' . $why);
            } catch (\Throwable $e) {
            }
            $out[] = ['job_id' => $id, 'hotel' => $hotel, 'status' => 'not_sent', 'to' => $to, 'why' => $why];
        }
        return $out;
    }

    private function intakeOf(int $tourId): int
    {
        return (int) ($this->db->query('SELECT intake_id FROM tf_tour WHERE id = ?', [$tourId])->getRowArray()['intake_id'] ?? 0);
    }

    /** The stay's crew line and bed type as the person set them; written on the card's trail when they change. */
    private function applyLines(TourService $svc, array $j, int $driver, int $guide, ?string $beds, string $bedsWhy = ''): void
    {
        $crew = self::crewText(max(0, min($driver, 99)), max(0, min($guide, 99)));
        $note = $beds ? self::swapBeds($j['rooms_note'], $beds) : $j['rooms_note'];
        $set = [];
        $bind = [];
        $said = [];
        if ((string) ($crew ?? '') !== (string) ($j['crew_rooms'] ?? '')) {
            $set[] = 'crew_rooms = ?';
            $bind[] = $crew;
            $said[] = 'crew rooms: ' . ($crew ?? 'none');
        }
        if ((string) ($note ?? '') !== (string) ($j['rooms_note'] ?? '')) {
            $set[] = 'rooms_note = ?';
            $bind[] = mb_substr((string) $note, 0, 255);
            $said[] = 'room type corrected from "' . $j['rooms_note'] . '" to "' . $note . '"' . $bedsWhy;
        }
        if (!$set) {
            return;
        }
        $bind[] = date('Y-m-d H:i:s');
        $bind[] = $this->user;
        $bind[] = (int) $j['id'];
        $this->db->query('UPDATE tf_hotel_job SET ' . implode(', ', $set) . ', updated_at = ?, updated_by = ? WHERE id = ?', $bind);
        $svc->event($this->intakeOf((int) $j['tour_id']), (int) $j['tour_id'], 'hotel', (int) $j['id'], 'letter_lines', null, null,
            'Before the booking request: ' . implode('; ', $said) . ' (set by ' . $this->actor . '; the quotation is unchanged)');
    }

    // =====================================================================
    //  The mails: per tour, all tours, and what has not gone
    // =====================================================================

    /**
     * Every hotel request / voucher letter and every restaurant booking voucher, newest first.
     * @param array $f ['tour_id' => ?int, 'state' => 'all'|'sent'|'not_sent', 'q' => string, 'limit' => int]
     */
    public function log(array $f): array
    {
        $tourId = (int) ($f['tour_id'] ?? 0);
        $state = (string) ($f['state'] ?? 'all');
        $q = trim((string) ($f['q'] ?? ''));
        $limit = max(1, min(500, (int) ($f['limit'] ?? 200)));
        $rows = [];
        $w = ['1=1'];
        $b = [];
        if ($tourId > 0) {
            $w[] = 'm.tour_id = ?';
            $b[] = $tourId;
        }
        if ($q !== '') {
            $w[] = '(t.tour_no LIKE ? OR i.inquiry_no LIKE ? OR j.hotel_name LIKE ? OR m.to_addr LIKE ? OR t.tour_name LIKE ?)';
            array_push($b, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%");
        }
        $w[] = "m.kind IN ('request','voucher')";
        foreach ($this->db->query('SELECT m.id, m.job_id, m.tour_id, m.kind, m.channel, m.to_addr, m.cc_addr, m.subject, m.state, m.error, m.by_name, m.`at`, (m.body IS NOT NULL AND m.body <> \'\') AS has_copy,
                    j.hotel_name, j.status AS card_status, j.check_in, t.tour_no, t.tour_name, i.inquiry_no,
                    (SELECT MAX(m2.id) FROM tf_hotel_message m2 WHERE m2.job_id = m.job_id AND m2.kind = m.kind) AS newest
                 FROM tf_hotel_message m JOIN tf_hotel_job j ON j.id = m.job_id JOIN tf_tour t ON t.id = m.tour_id LEFT JOIN tf_intake i ON i.id = t.intake_id
                 WHERE ' . implode(' AND ', $w) . ' ORDER BY m.id DESC LIMIT ' . $limit, $b)->getResultArray() as $r) {
            $st = $r['state'] === 'failed' ? 'not_sent' : ($r['state'] === 'recorded' ? 'other' : 'sent');
            $rows[] = [
                'type' => 'hotel', 'id' => (int) $r['id'], 'job_id' => (int) $r['job_id'], 'tour_id' => (int) $r['tour_id'], 'tour_no' => $r['tour_no'], 'tour_name' => $r['tour_name'], 'inquiry_no' => $r['inquiry_no'],
                'name' => $r['hotel_name'], 'what' => $r['kind'] === 'voucher' ? 'Hotel voucher' : 'Hotel booking request', 'on' => $r['check_in'],
                'to' => $r['to_addr'], 'cc' => $r['cc_addr'], 'subject' => $r['subject'], 'state' => $st, 'channel' => $r['channel'], 'error' => $r['error'],
                'by' => $r['by_name'], 'at' => $r['at'], 'has_copy' => (int) $r['has_copy'] === 1,
                // a failure later put right (sent again, or recorded as sent another way) is history, not an alert
                'open' => $st === 'not_sent' && (int) $r['newest'] === (int) $r['id'] && in_array((string) $r['card_status'], ['to_book', 'problem'], true),
            ];
        }
        if ($this->vouchersOn()) {
            $w = ['1=1'];
            $b = [];
            if ($tourId > 0) {
                $w[] = 'v.tour_id = ?';
                $b[] = $tourId;
            }
            if ($q !== '') {
                $w[] = '(t.tour_no LIKE ? OR i.inquiry_no LIKE ? OR v.restaurant LIKE ? OR v.to_email LIKE ? OR v.voucher_no LIKE ? OR t.tour_name LIKE ?)';
                array_push($b, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%");
            }
            foreach ($this->db->query('SELECT v.id, v.job_id, v.tour_id, v.voucher_no, v.restaurant, v.meal_type, v.on_date, v.to_email, v.mail_cc, v.mail_subject, v.mail_status, v.mail_error, v.sent_at, v.sent_by, v.issued_at, v.state, v.note,
                        (v.mail_body IS NOT NULL AND v.mail_body <> \'\') AS has_copy, sj.status AS line_status, t.tour_no, t.tour_name, i.inquiry_no
                     FROM tf_meal_voucher v JOIN tf_tour t ON t.id = v.tour_id LEFT JOIN tf_intake i ON i.id = t.intake_id LEFT JOIN tf_service_job sj ON sj.id = v.job_id
                     WHERE ' . implode(' AND ', $w) . ' ORDER BY v.id DESC LIMIT ' . $limit, $b)->getResultArray() as $r) {
                $st = $r['mail_status'] === 'sent' ? 'sent' : ($r['mail_status'] === 'other' ? 'other' : 'not_sent');
                $rows[] = [
                    'type' => 'restaurant', 'id' => (int) $r['id'], 'job_id' => (int) $r['job_id'], 'tour_id' => (int) $r['tour_id'], 'tour_no' => $r['tour_no'], 'tour_name' => $r['tour_name'], 'inquiry_no' => $r['inquiry_no'],
                    'name' => $r['restaurant'], 'what' => 'Restaurant booking voucher ' . $r['voucher_no'] . ' (' . $r['meal_type'] . ')' . ($r['state'] === 'cancelled' ? ' — cancelled' : ''), 'on' => $r['on_date'],
                    'to' => $r['to_email'], 'cc' => $r['mail_cc'], 'subject' => $r['mail_subject'], 'state' => $st, 'channel' => $r['mail_status'] === 'other' ? 'other' : 'email',
                    'error' => $st === 'not_sent' ? ($r['mail_error'] ?: ($r['to_email'] ? 'Not sent yet.' : 'No e-mail address for ' . $r['restaurant'] . '.')) : ($r['mail_status'] === 'other' ? $r['note'] : null),
                    'by' => $r['sent_by'], 'at' => $r['sent_at'] ?: $r['issued_at'], 'has_copy' => (int) $r['has_copy'] === 1,
                    'open' => $st === 'not_sent' && $r['state'] === 'issued' && !in_array((string) $r['line_status'], ['cancelled', 'not_needed', 'confirmed'], true),
                ];
            }
        }
        if ($state === 'not_sent') {
            $rows = array_values(array_filter($rows, static fn ($r) => $r['open']));
        } elseif ($state === 'sent') {
            $rows = array_values(array_filter($rows, static fn ($r) => $r['state'] !== 'not_sent'));
        }
        usort($rows, static fn ($a, $b) => strcmp((string) $b['at'], (string) $a['at']));
        return array_slice($rows, 0, $limit);
    }

    /**
     * What has not gone and still has to: the last request letter of a stay still to book that failed, and a restaurant
     * voucher issued but not sent (no address, refused). Gone or recorded as sent another way: off the list.
     */
    public function unsent(int $limit = 50): array
    {
        $out = [];
        try {
            foreach ($this->db->query("SELECT m.id, m.job_id, m.tour_id, m.error, m.`at`, m.to_addr, j.hotel_name, t.tour_no, t.tour_name
                    FROM tf_hotel_message m
                    JOIN (SELECT job_id, MAX(id) AS last_id FROM tf_hotel_message WHERE kind = 'request' GROUP BY job_id) x ON x.last_id = m.id
                    JOIN tf_hotel_job j ON j.id = m.job_id JOIN tf_tour t ON t.id = j.tour_id
                    WHERE m.state = 'failed' AND j.status IN ('to_book','problem') AND j.archived_at IS NULL AND t.archived_at IS NULL AND t.stage <> 'cancelled'
                    ORDER BY m.id DESC LIMIT " . max(1, min(200, $limit)))->getResultArray() as $r) {
                $out[] = ['type' => 'hotel', 'id' => (int) $r['id'], 'job_id' => (int) $r['job_id'], 'tour_id' => (int) $r['tour_id'], 'tour_no' => $r['tour_no'], 'tour_name' => $r['tour_name'],
                    'name' => $r['hotel_name'], 'what' => 'Hotel booking request', 'to' => $r['to_addr'], 'why' => $r['error'], 'at' => $r['at']];
            }
        } catch (\Throwable $e) {
        }
        if ($this->vouchersOn()) {
            try {
                foreach ($this->db->query("SELECT v.id, v.job_id, v.tour_id, v.voucher_no, v.restaurant, v.to_email, v.mail_error, v.issued_at, t.tour_no, t.tour_name
                        FROM tf_meal_voucher v JOIN tf_tour t ON t.id = v.tour_id LEFT JOIN tf_service_job sj ON sj.id = v.job_id
                        WHERE v.state = 'issued' AND v.mail_status IN ('not_sent','failed') AND t.archived_at IS NULL AND t.stage <> 'cancelled'
                          AND (sj.id IS NULL OR (sj.status NOT IN ('cancelled','not_needed','confirmed') AND sj.archived_at IS NULL))
                        ORDER BY v.id DESC LIMIT " . max(1, min(200, $limit)))->getResultArray() as $r) {
                    $out[] = ['type' => 'restaurant', 'id' => (int) $r['id'], 'job_id' => (int) $r['job_id'], 'tour_id' => (int) $r['tour_id'], 'tour_no' => $r['tour_no'], 'tour_name' => $r['tour_name'],
                        'name' => $r['restaurant'], 'what' => 'Restaurant voucher ' . $r['voucher_no'], 'to' => $r['to_email'],
                        'why' => $r['mail_error'] ?: ($r['to_email'] ? 'Not sent yet.' : 'No e-mail address for ' . $r['restaurant'] . '.'), 'at' => $r['issued_at']];
                }
            } catch (\Throwable $e) {
            }
        }
        usort($out, static fn ($a, $b) => strcmp((string) $b['at'], (string) $a['at']));
        return array_slice($out, 0, $limit);
    }

    /** The copy of a mail as it went (or as it was prepared), for the screen. */
    public function copy(string $type, int $id): ?array
    {
        if ($type === 'hotel') {
            $r = $this->db->query('SELECT m.subject, m.body, m.to_addr, m.cc_addr, m.state, m.error, m.`at`, m.by_name, t.tour_no FROM tf_hotel_message m JOIN tf_tour t ON t.id = m.tour_id WHERE m.id = ?', [$id])->getRowArray();
            return $r ? ['subject' => $r['subject'], 'body' => $r['body'], 'to' => $r['to_addr'], 'cc' => $r['cc_addr'], 'state' => $r['state'], 'error' => $r['error'], 'at' => $r['at'], 'by' => $r['by_name'], 'tour_no' => $r['tour_no']] : null;
        }
        if ($type === 'restaurant' && $this->vouchersOn()) {
            $r = $this->db->query('SELECT v.mail_subject, v.mail_body, v.to_email, v.mail_cc, v.mail_status, v.mail_error, v.sent_at, v.sent_by, t.tour_no FROM tf_meal_voucher v JOIN tf_tour t ON t.id = v.tour_id WHERE v.id = ?', [$id])->getRowArray();
            return $r ? ['subject' => $r['mail_subject'], 'body' => $r['mail_body'], 'to' => $r['to_email'], 'cc' => $r['mail_cc'], 'state' => $r['mail_status'], 'error' => $r['mail_error'], 'at' => $r['sent_at'], 'by' => $r['sent_by'], 'tour_no' => $r['tour_no']] : null;
        }
        return null;
    }

    private function vouchersOn(): bool
    {
        try {
            return (bool) $this->db->query("SHOW TABLES LIKE 'tf_meal_voucher'")->getResultArray()
                && (bool) $this->db->query("SHOW COLUMNS FROM `tf_meal_voucher` LIKE 'mail_status'")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function email($v): ?string
    {
        $v = trim((string) ($v ?? ''));
        return $v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
    }
}
