<?php

namespace App\Libraries\TourFlow;

/**
 * tf-supmail-r1 (4 Nov 2026, Shantha) — the reservation requests to ACTIVITY suppliers and to RESTAURANTS.
 *
 * The two groups that had wording but no sender. Every activity line and every Meal Master line on a confirmed
 * tour gets its request PREPARED automatically from the record, in the saved wording from Master Files →
 * Email Templates ("Activity & other supplier request", "Restaurant reservation request"), with the tour, the
 * day, the time, the place and the party size filled in. Where the supplier has an e-mail address on a master,
 * it is prepared to go by e-mail through the company's own mailbox; where there is none, the same words are
 * prepared to send by hand and to print. Nothing is invented: a missing address is named.
 *
 * Preparing is not sending, and sending is NOT confirming. A request leaves the line exactly as it was, and
 * only the desk recording the supplier's own answer makes it confirmed. So an activity, a restaurant, a hotel,
 * transport, the driver and the guide all stay pending until their own team confirms them, and the tour cannot
 * reach its final confirmation until every required section is green.
 *
 * One request per line per party, guarded at the database itself (see service_messages.sql), so a double click,
 * a retry or two desks pressing together cannot raise the same request twice. Everything is kept here with its
 * channel, its state and its reason, and mirrored into Communications & History against the tour.
 */
final class SupplierMailer
{
    private $db;
    private string $user;
    private ?string $actor;
    private ?int $userId = null;

    /** the values a master uses for "nothing here" */
    private const EMPTY_VALUES = ['', '0', '-', 'N/A', 'NA', 'NIL', 'NONE', 'NULL'];

    /** which saved wording each party is written from */
    private const GROUP = ['activity' => 'activity_request', 'restaurant' => 'restaurant_request'];

    public function __construct($db, string $user = '', ?string $actor = null, ?int $userId = null)
    {
        $this->db     = $db;
        $this->user   = $user;
        $this->actor  = $actor ?: ($user ?: null);
        $this->userId = $userId;
    }

    public static function ready($db): bool
    {
        try {
            return $db->query("SHOW TABLES LIKE 'tf_service_message'")->getNumRows() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function clean($v): ?string
    {
        $v = trim((string) ($v ?? ''));
        return $v !== '' && !in_array(strtoupper($v), self::EMPTY_VALUES, true) ? $v : null;
    }

    private static function email($v): ?string
    {
        $v = self::clean($v);
        return $v !== null && filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
    }

    private static function phone($v): ?string
    {
        $v = self::clean($v);
        return $v !== null && preg_match('/[0-9]{6,}/', preg_replace('/\D+/', '', $v)) ? $v : null;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /** a stored time as people read it, or '' — never a guess */
    private static function hhmm($v): string
    {
        $v = trim((string) ($v ?? ''));
        return $v !== '' && $v !== '00:00:00' ? substr($v, 0, 5) : '';
    }

    private function userId(): ?int
    {
        if ($this->userId !== null) {
            return $this->userId;
        }
        try {
            $id = (int) session()->get('user_id');
            return $id > 0 ? $id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Who has to be told about this tour's activities and meals, with the address the masters hold.
     * One row per line that still needs arranging. Nothing is written.
     */
    public function parties(int $tourId): array
    {
        $out = [];
        $rows = $this->db->query("SELECT j.*, t.intake_id FROM tf_service_job j JOIN tf_tour t ON t.id = j.tour_id
                                   WHERE j.tour_id = ? AND j.archived_at IS NULL
                                     AND j.status NOT IN ('cancelled', 'not_needed', 'pay_at_gate')
                                   ORDER BY j.on_date, j.seq, j.id", [$tourId])->getResultArray();
        foreach ($rows as $j) {
            $out[] = $this->party($tourId, $j);
        }
        return $out;
    }

    /** one row of parties(): which party it is, where the letter would go, and what is missing said plainly */
    private function party(int $tourId, array $j): array
    {
        $jobId = (int) $j['id'];
        $meal  = $this->mealOf($j);
        if ($meal !== null) {
            $name   = self::clean($meal['restaurant']) ?: 'the restaurant';
            $email  = $meal['email'];
            $phone  = $meal['phone'];
            $source = $meal['source'];
            $party  = 'restaurant';
        } else {
            [$name, $email, $phone, $source] = $this->activityContact($j);
            $party = 'activity';
        }
        $channel = $email !== null ? 'email' : ($phone !== null ? 'whatsapp' : 'print');
        $missing = null;
        if ($email === null) {
            $missing = $phone !== null
                ? 'No e-mail address for ' . $name . ' — the request is prepared as a message to send by hand to ' . $phone . ', and as a sheet to print.'
                : 'No e-mail address and no telephone number for ' . $name . ' (' . $source . ') — the request can only be printed. Put the contact on the master.';
        }
        return ['tour_id' => $tourId, 'job_id' => $jobId, 'party' => $party, 'name' => $name, 'title' => (string) $j['title'],
                'email' => $email, 'phone' => $phone, 'channel' => $channel, 'source' => $source, 'missing' => $missing,
                'on_date' => $j['on_date'], 'pax' => $j['pax'], 'status' => (string) $j['status'],
                // the line itself, so the letter is written from the row already read rather than reading it again
                'line' => $j, 'meal' => $meal];
    }

    /** the Meal Master meal behind a line, with the address the master holds — null when the line is not a meal */
    private function mealOf(array $j): ?array
    {
        if ((string) ($j['source'] ?? '') !== 'service' || empty($j['source_id'])) {
            return null;
        }
        try {
            $s = $this->db->query('SELECT * FROM tf_intake_service WHERE id = ?', [(int) $j['source_id']])->getRowArray();
            if (!$s || empty($s['meal_id'])) {
                return null;
            }
            $m = $this->db->query('SELECT * FROM tf_meal WHERE id = ?', [(int) $s['meal_id']])->getRowArray() ?: [];
            // the address the booking line carries, then the master's own contact, then the supplier record
            $email  = self::email($s['meal_email'] ?? null);
            $source = $email !== null ? 'the address on the booking line' : '';
            if ($email === null) {
                $email  = self::email($m['contact_email'] ?? null);
                $source = $email !== null ? 'the Meal Master' : $source;
            }
            $phone = self::phone($s['meal_phone'] ?? ($m['contact_phone'] ?? null));
            $acc   = self::clean($m['supplier_acc'] ?? null);
            if ($email === null && $acc !== null) {
                $sup = $this->db->query('SELECT email, Mobile, TP FROM supplier WHERE Sup_Acc_No = ? LIMIT 1', [$acc])->getRowArray();
                if ($sup) {
                    $email = self::email($sup['email']);
                    $phone = $phone ?: self::phone($sup['Mobile'] ?: $sup['TP']);
                    if ($email !== null) {
                        $source = 'the supplier record ' . $acc;
                    }
                }
            }
            return [
                'restaurant' => $m['restaurant'] ?? null, 'email' => $email, 'phone' => $phone,
                'source' => $source !== '' ? $source : 'the Meal Master',
                'meal_type' => $m['meal_type'] ?? null, 'time' => $s['meal_time'] ?? null,
                'location' => $m['location'] ?? null,
                'adults' => (int) ($s['qty_adult'] ?? 0), 'children' => (int) ($s['qty_child'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** where an activity line's place is, for {location} — empty when the line names no place */
    private function placeLocation(array $j): string
    {
        if (empty($j['place_id'])) {
            return '';
        }
        try {
            $p = $this->db->query('SELECT location, destination FROM tf_place WHERE id = ?', [(int) $j['place_id']])->getRowArray();
            return (string) (self::clean($p['location'] ?? null) ?? self::clean($p['destination'] ?? null) ?? '');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** the supplier behind an activity line: the place's own party, else the supplier record, else nothing */
    private function activityContact(array $j): array
    {
        $name = self::clean($j['title']) ?: 'the supplier';
        if (!empty($j['place_id'])) {
            try {
                // the ticket supplier first, then the jeep operator: whoever actually takes the booking
                $p = $this->db->query("SELECT * FROM tf_place_party WHERE place_id = ? ORDER BY FIELD(role, 'ticket_supplier', 'jeep_operator'), id LIMIT 1", [(int) $j['place_id']])->getRowArray();
                if ($p) {
                    $email = self::email($p['email']);
                    $phone = self::phone($p['whatsapp'] ?: $p['phone']);
                    $who   = self::clean($p['business_name']) ?: $name;
                    if ($email === null && !empty($p['supplier_id'])) {
                        $sup = $this->db->query('SELECT Name, email, Mobile, TP FROM supplier WHERE id = ? LIMIT 1', [(int) $p['supplier_id']])->getRowArray();
                        if ($sup) {
                            $email = self::email($sup['email']);
                            $phone = $phone ?: self::phone($sup['Mobile'] ?: $sup['TP']);
                            $who   = self::clean($sup['Name']) ?: $who;
                            if ($email !== null) {
                                return [$who, $email, $phone, 'the supplier record behind the place'];
                            }
                        }
                    }
                    return [$who, $email, $phone, 'the place on the Places & Entrance Fees master'];
                }
            } catch (\Throwable $e) {
            }
            return [$name, null, null, 'the place is on the master but has no booking contact on it'];
        }
        return [$name, null, null, 'this line is on no master, so it has no contact'];
    }

    /**
     * The words of the request, from the SAVED wording, filled from this tour and this line. A word the record
     * cannot fill is left empty and named, never invented.
     */
    public function letter(int $tourId, array $p): array
    {
        // the line as parties() already read it; only read it again when the caller did not bring it
        $j = is_array($p['line'] ?? null) && $p['line'] !== []
            ? $p['line']
            : ($this->db->query('SELECT * FROM tf_service_job WHERE id = ?', [(int) $p['job_id']])->getRowArray() ?: []);
        // tf-supmail-r2 (4 Nov 2026, Shantha): the tour is taken from the LINE, never from the caller's word for
        // it. A letter that named one tour's number over another tour's meal would be wrong in the worst way —
        // the supplier would hold the wrong day for the wrong party — so a line and a tour that disagree are
        // refused outright rather than written up.
        $ownTourId = (int) ($j['tour_id'] ?? 0);
        if ($ownTourId > 0 && $tourId > 0 && $ownTourId !== $tourId) {
            throw new \RuntimeException('That line belongs to another tour (' . $ownTourId . ', not ' . $tourId . '). Nothing was written.');
        }
        $tourId = $ownTourId > 0 ? $ownTourId : $tourId;
        $t = $this->db->query('SELECT t.*, i.inquiry_no FROM tf_tour t LEFT JOIN tf_intake i ON i.id = t.intake_id WHERE t.id = ?', [$tourId])->getRowArray() ?: [];
        $d = static fn ($x) => $x ? date('j M Y', strtotime((string) $x)) : '';
        $meal = array_key_exists('meal', $p) ? $p['meal'] : $this->mealOf($j);
        $mw   = new MailWords($this->db);
        $vars = [
            'supplier'      => (string) $p['name'],
            'activity'      => (string) ($j['title'] ?? ''),
            'activity_date' => $d($j['on_date'] ?? null),
            // the time the line is booked for; a meal carries its own, an activity carries the line's. Never a guess.
            'activity_time' => (string) ($meal['time'] ?? self::hhmm($j['on_time'] ?? null)),
            'location'      => (string) ($meal['location'] ?? $this->placeLocation($j)),
            'restaurant'    => (string) ($meal['restaurant'] ?? ''),
            'meal'          => $meal !== null ? ucfirst((string) ($meal['meal_type'] ?? 'meal')) : '',
            'meal_date'     => $d($j['on_date'] ?? null),
            'meal_time'     => (string) ($meal['time'] ?? self::hhmm($j['on_time'] ?? null)),
            'adults'        => $meal !== null ? (string) $meal['adults'] : '',
            'children'      => $meal !== null ? (string) $meal['children'] : '',
        ] + $mw->forIntake((int) ($t['intake_id'] ?? 0), ['name' => $this->actor ?: $this->user]);
        // the line's own party size beats the tour's, when the line carries one
        if (($j['pax'] ?? null) !== null) {
            $vars['pax'] = (string) (int) $j['pax'];
        }
        $group   = self::GROUP[$p['party']] ?? 'activity_request';
        $subject = 'Booking request — ' . ($t['tour_no'] ?? '') . ' — ' . ($j['title'] ?? '') . (!empty($j['on_date']) ? ' on ' . $d($j['on_date']) : '');
        $text     = null;
        $missing  = [];
        $required = [];
        try {
            $et    = new EmailTemplates($this->db);
            $list  = EmailTemplates::filled($et->forWindow($group), $vars);
            $start = EmailTemplates::starting($list, $group);
            if ($start && trim((string) $start['message']) !== '') {
                $subject = trim((string) $start['subject']) !== '' ? (string) $start['subject'] : $subject;
                $text    = rtrim((string) $start['message']);
                $missing = (array) ($start['missing'] ?? []);
                // of what could not be filled, the part a supplier genuinely cannot act without
                $raw = EmailTemplates::starting($et->forWindow($group), $group);
                if ($raw) {
                    $required = EmailTemplates::missingRequired((string) $raw['subject'] . ' ' . (string) $raw['message'], $vars);
                }
            }
        } catch (\Throwable $e) {
            $text = null;
        }
        if ($text === null) {
            // no saved wording on this install: a plain request, so a booking is never held up by a template
            $text = implode("\n", [
                'Dear ' . $p['name'] . ',',
                '',
                'Kindly reserve ' . ($j['title'] ?? 'the service below') . (!empty($j['on_date']) ? ' on ' . $d($j['on_date']) : '')
                    . ' for ' . (($vars['pax'] ?? '') !== '' ? $vars['pax'] : 'our') . ' passenger(s) travelling with us under tour ' . ($t['tour_no'] ?? '') . '.',
                '',
                'Please confirm the reservation by return email, with your reference.',
                '',
                'Avenir Holidays',
            ]);
        }
        return ['subject' => mb_substr($subject, 0, 250), 'text' => $text, 'missing' => $missing, 'required' => $required,
                'ref' => ($t['tour_no'] ?? '') . ' / ' . ($t['inquiry_no'] ?? '')];
    }

    /**
     * Prepare a request for every line that has none yet. Writes 'prepared' rows only — nothing is sent by
     * preparing, and no line's status is touched. Safe to call again: a line that already has a request is
     * left alone, and the database itself refuses a second one.
     */
    public function prepare(int $tourId, bool $send = true): array
    {
        if (!self::ready($this->db)) {
            return ['prepared' => 0, 'rows' => []];
        }
        $made = [];
        foreach ($this->parties($tourId) as $p) {
            $l   = $this->letter($tourId, $p);
            $has = $this->db->query("SELECT id, state, edited_at FROM tf_service_message WHERE job_id = ? AND party = ? AND kind = 'request' ORDER BY id DESC LIMIT 1",
                [(int) $p['job_id'], $p['party']])->getRowArray();
            if ($has) {
                // a request still only PREPARED follows the record: the day, the people or the place may have
                // changed since. One already sent, failed or handled by hand is never rewritten.
                if ((string) $has['state'] === 'prepared' && empty($has['edited_at'])) {
                    // a letter somebody has written themselves is theirs: only the untouched ones follow the record
                    $this->db->query('UPDATE tf_service_message SET party_name = ?, channel = ?, to_addr = ?, to_phone = ?, subject = ?, body = ?, by_name = ?, `at` = ? WHERE id = ?',
                        [$p['name'], $p['channel'], $p['email'], $p['phone'], $l['subject'], $l['text'], $this->actor, $this->now(), (int) $has['id']]);
                } elseif ((string) $has['state'] === 'prepared') {
                    // the address still follows the master; the words stay as the person wrote them
                    $this->db->query('UPDATE tf_service_message SET party_name = ?, channel = ?, to_addr = ?, to_phone = ? WHERE id = ?',
                        [$p['name'], $p['channel'], $p['email'], $p['phone'], (int) $has['id']]);
                }
                continue;
            }
            try {
                $this->db->query("INSERT INTO tf_service_message (tour_id, job_id, party, party_name, kind, req_key, channel, state, to_addr, to_phone, subject, body, by_name, `at`)
                                  VALUES (?,?,?,?,'request','request',?,'prepared',?,?,?,?,?,?)",
                    [$tourId, $p['job_id'], $p['party'], $p['name'], $p['channel'], $p['email'], $p['phone'], $l['subject'], $l['text'], $this->actor, $this->now()]);
            } catch (\Throwable $e) {
                continue;   // the unique key refused a second request for this line: somebody got there first
            }
            $made[] = ['id' => (int) $this->db->insertID()] + $p;
        }
        return ['prepared' => count($made), 'rows' => $made] + ($send ? $this->autoSend($made) : []);
    }

    /**
     * The requests that CAN go, go — as the hotels' letters go at Confirm. One with no address, or with no
     * mailbox to send from, or on an installation where automatic sending is switched off, stays 'prepared'
     * with its reason and shows on the desk; it is never called 'sent'. No line's status is touched.
     */
    private function autoSend(array $rows): array
    {
        $out = ['sent' => 0, 'failed' => 0, 'held' => 0, 'why' => null];
        $withAddress = array_values(array_filter($rows, static fn ($r) => !empty($r['email'])));
        if (!$withAddress) {
            $out['held'] = count($rows);
            return $out;
        }
        try {
            $sender = CommsMail::senderFor($this->db, $this->userId(), $this->user);
        } catch (\Throwable $e) {
            $sender = ['kind' => 'none'];
        }
        if (!AutoSend::on($this->db)) {
            $out['held'] = count($withAddress);
            $out['why']  = AutoSend::HELD;
            return $out;
        }
        if (!self::canSend($sender)) {
            $out['held'] = count($withAddress);
            $out['why']  = $sender['note'] ?? 'No mailbox is set up to send from yet.';
            return $out;
        }
        $sender['user_id'] = $this->userId();
        foreach ($withAddress as $r) {
            // a letter still missing something waits on the desk; automatic sending never papers over a hole
            if ($this->holes((int) $r['id']) !== null) {
                $out['held']++;
                $out['why'] = $out['why'] ?? 'One or more letters are waiting for something the record has not got.';
                continue;
            }
            try {
                $res = $this->send((int) $r['id'], $sender);
                $out[!empty($res['ok']) ? 'sent' : 'failed']++;
            } catch (\Throwable $e) {
                $out['failed']++;
            }
        }
        return $out;
    }

    /**
     * tf-supmail-r2 (Shantha, 4 Nov 2026): change the subject and the message of ONE prepared request.
     *
     * This is the desk's own copy of that letter and nothing else: the saved template on Master Files is not
     * touched, and no other request is. A request already sent, failed or handled by hand is not rewritten —
     * what went out stays as it went. The edit is kept with who made it, so the trail shows the words that
     * were actually sent rather than the words the template would have produced.
     */
    public function edit(int $messageId, ?string $subject, ?string $body): array
    {
        $m = $this->db->query('SELECT * FROM tf_service_message WHERE id = ?', [$messageId])->getRowArray();
        if (!$m) {
            throw new \RuntimeException('That request is not on the record.');
        }
        if ((string) $m['state'] !== 'prepared') {
            throw new \RuntimeException('This request was already ' . ((string) $m['state'] === 'recorded' ? 'handled by hand' : $m['state'])
                . ' on ' . substr((string) $m['at'], 0, 16) . '. What went out is kept as it went and is not rewritten.');
        }
        $subject = mb_substr(trim(str_replace(["\r", "\n"], ' ', (string) ($subject ?? ''))), 0, 250);
        $body    = rtrim(str_replace("\r", '', (string) ($body ?? '')));
        if ($subject === '') {
            throw new \InvalidArgumentException('The request needs a subject.');
        }
        if (trim($body) === '') {
            throw new \InvalidArgumentException('Type the message that goes to ' . $m['party_name'] . '.');
        }
        if (mb_strlen($body) > 8000) {
            throw new \InvalidArgumentException('The message is too long (8,000 characters at most).');
        }
        $this->db->query('UPDATE tf_service_message SET subject = ?, body = ?, edited_at = ?, edited_by = ?, `at` = ? WHERE id = ? AND state = \'prepared\'',
            [$subject, $body, $this->now(), $this->actor, $this->now(), $messageId]);
        if ((int) $this->db->affectedRows() === 0) {
            throw new \RuntimeException('Somebody sent or handled this request a moment ago. Nothing was changed.');
        }
        return $this->db->query('SELECT * FROM tf_service_message WHERE id = ?', [$messageId])->getRowArray();
    }

    /** Whether there is a mailbox to send from at all: the company's, or the signed-in person's own. */
    public static function canSend(array $sender): bool
    {
        return (string) ($sender['kind'] ?? 'none') !== 'none' && trim((string) ($sender['email'] ?? '')) !== '';
    }

    /** every communication on this tour's activity and meal lines, newest first */
    public function messages(int $tourId): array
    {
        if (!self::ready($this->db)) {
            return [];
        }
        return $this->db->query('SELECT * FROM tf_service_message WHERE tour_id = ? ORDER BY id DESC', [$tourId])->getResultArray();
    }

    /**
     * Send a prepared request by e-mail. The result — sent, or the reason it was not — is kept on the row.
     * The line is NOT touched: a request is not a confirmation, and the desk records the answer itself.
     */
    public function send(int $messageId, array $sender, ?string $to = null, ?string $cc = null): array
    {
        $m = $this->db->query('SELECT * FROM tf_service_message WHERE id = ?', [$messageId])->getRowArray();
        if (!$m) {
            throw new \RuntimeException('That request is not on the record.');
        }
        if ((string) $m['state'] === 'sent') {
            return ['ok' => true, 'already' => true,
                    'message' => 'It was already sent on ' . substr((string) $m['at'], 0, 16) . ' to ' . $m['to_addr'] . '. It is not sent twice.'];
        }
        $addr = self::email($to ?: $m['to_addr']);
        if ($addr === null) {
            throw new \RuntimeException('There is no e-mail address for ' . $m['party_name'] . '. Send it by hand or print it, and record that here.');
        }
        // tf-supmail-r2: a letter still carrying an unfilled word is never sent. The supplier would read a
        // sentence with a hole in it, and the worst of those is a missing time: they would hold nothing.
        $hole = $this->holes((int) $messageId, $m);
        if ($hole !== null) {
            throw new \RuntimeException($hole);
        }
        if (!self::canSend($sender)) {
            throw new \RuntimeException(($sender['note'] ?? 'No mailbox is set up to send from.') . ' Nothing was sent. Print the request, or send it by hand and record it here.');
        }
        // tf-supmail-r6 (Shantha, 4 Nov 2026): the saved wording carries its own "Kind regards, …" so that a
        // printed letter, or one sent by hand, is complete on its own. The e-mail then adds the company
        // signature, which would sign the letter twice. The sign-off is dropped HERE, where the outgoing HTML
        // is built, and only here: tf_service_message.body is left exactly as it was written, so printing and
        // send-by-hand are unchanged, and so is what the communication record keeps.
        $html = CommsMail::html(nl2br(esc(EmailTemplates::withoutSignOff((string) $m['body']))), CommsMail::signature($this->db, $sender['user_id'] ?? null, $this->user), (string) $m['subject']);
        // tf-supmail-r2: the mailbox the letter goes FROM has to reach the mailer. Passing the sender under its
        // own key left CommsMail to fall back on the signed-in person's mailbox every time, so a company system
        // mailbox was never used and a letter sent by somebody without one failed. The same shape as Comms.
        $r = CommsMail::send($this->db, [
            'to' => [$addr], 'cc' => CommsMail::addresses($cc ?? $m['cc_addr']), 'subject' => (string) $m['subject'],
            'html' => $html,
            'reply_to'   => $sender['reply_to'] ?? null,
            'from_email' => (string) ($sender['kind'] ?? '') === 'system' ? (string) ($sender['email'] ?? '') : '',
            'from_name'  => (string) ($sender['name'] ?? 'Avenir Holidays'),
            'password'   => (string) ($sender['kind'] ?? '') === 'system' ? CommsMail::systemPassword($this->db) : '',
            'user_id'    => $sender['user_id'] ?? $this->userId(),
        ]);
        $ok = !empty($r['ok']);
        // tf-supmail-r2: the copy the company setting adds by itself goes on the record too, so the history
        // shows every address the letter actually reached — not only the ones the desk typed.
        $ccAll = trim(implode(', ', array_filter(array_merge(
            CommsMail::addresses($cc ?? $m['cc_addr']), (array) ($r['cc_added'] ?? [])))));
        $this->db->query("UPDATE tf_service_message SET state = ?, channel = 'email', to_addr = ?, cc_addr = ?, error = ?, by_name = ?, `at` = ? WHERE id = ?",
            [$ok ? 'sent' : 'failed', $addr, $ccAll !== '' ? $ccAll : null, $ok ? null : mb_substr((string) ($r['error'] ?? 'not sent'), 0, 500), $this->actor, $this->now(), $messageId]);
        $this->toComms($messageId);
        return ['ok' => $ok, 'message' => $ok
            ? 'Sent to ' . $addr . '. The mail server accepted it — that is not proof it was read; the booking stays pending until the desk records the answer.'
            : 'NOT sent — ' . ($r['error'] ?? 'the mail server refused it') . ' The attempt is kept on the record.'];
    }

    /**
     * tf-supmail-r2: what this prepared request still needs before anybody may send it. Null when it is ready.
     *
     * It reads the line the letter was written from, not the letter's text, so a message a person has edited by
     * hand is judged on the record behind it. A wording that asks for a time with no time on the line is the
     * case this was built for: the request is held, and the desk is told exactly what to enter.
     */
    public function holes(int $messageId, ?array $m = null): ?string
    {
        $m = $m ?: ($this->db->query('SELECT * FROM tf_service_message WHERE id = ?', [$messageId])->getRowArray() ?: []);
        if (!$m || empty($m['job_id'])) {
            return null;
        }
        $j = $this->db->query('SELECT * FROM tf_service_job WHERE id = ?', [(int) $m['job_id']])->getRowArray();
        if (!$j) {
            return 'The line this request was written for is no longer on the tour.';
        }
        $p = $this->party((int) $j['tour_id'], $j);
        try {
            $l = $this->letter((int) $j['tour_id'], $p);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
        // only what a supplier cannot act without holds the letter; the rest is flagged on the screen
        $stop = (array) ($l['required'] ?? []);
        // an edited copy may still carry a brace nobody filled in — that always holds it
        if (preg_match_all('/\{([a-z_]+)\}/', (string) ($m['subject'] ?? '') . ' ' . (string) ($m['body'] ?? ''), $mm)) {
            foreach (array_unique($mm[1]) as $w) {
                $stop[] = '{' . $w . '}, which was never filled in';
            }
        }
        if (!$stop) {
            return null;
        }
        $needsTime = (bool) array_filter($stop, static fn ($w) => str_contains(strtolower((string) $w), 'time'));
        return 'This request is not ready to send: it asks for ' . implode('; ', array_unique($stop)) . '.'
            . ($needsTime ? ' Enter the time on the activity line above, and the letter is written again with it.' : ' Put it on the record first.')
            . ' Nothing was sent.';
    }

    /** the request was handled by hand — a message, a phone call, a printed sheet — or an answer came back */
    public function record(int $messageId, string $channel, string $note, string $kind = 'request'): array
    {
        $m = $this->db->query('SELECT * FROM tf_service_message WHERE id = ?', [$messageId])->getRowArray();
        if (!$m) {
            throw new \RuntimeException('That request is not on the record.');
        }
        $channel = in_array($channel, ['whatsapp', 'print', 'other', 'email'], true) ? $channel : 'other';
        if ($kind === 'reply') {
            $this->db->query("INSERT INTO tf_service_message (tour_id, job_id, party, party_name, kind, req_key, channel, state, to_addr, to_phone, subject, body, by_name, `at`)
                              VALUES (?,?,?,?,'reply',NULL,?,'recorded',?,?,?,?,?,?)",
                [$m['tour_id'], $m['job_id'], $m['party'], $m['party_name'], $channel, $m['to_addr'], $m['to_phone'],
                 'Reply — ' . $m['party_name'], mb_substr($note, 0, 4000), $this->actor, $this->now()]);
            $this->toComms((int) $this->db->insertID());
            return ['ok' => true, 'message' => 'The answer is on the record. It does not confirm the booking — record the confirmation on the line.'];
        }
        $this->db->query("UPDATE tf_service_message SET state = 'recorded', channel = ?, error = NULL, body = CONCAT(COALESCE(body, ''), ?), by_name = ?, `at` = ? WHERE id = ?",
            [$channel, "\n\n--- sent by hand (" . $channel . ") on " . $this->now() . ' by ' . $this->actor . ($note !== '' ? ': ' . mb_substr($note, 0, 500) : '') . ' ---', $this->actor, $this->now(), $messageId]);
        $this->toComms($messageId);
        return ['ok' => true, 'message' => 'Recorded as sent by ' . $channel . '. The booking stays pending until the answer is recorded on the line.'];
    }

    /**
     * The communication as Communications & History keeps it: the same thread as the hotels' letters, the
     * transport requests and the agent's mail, with a guard so one message is never written twice.
     */
    private function toComms(int $messageId): void
    {
        try {
            $m = $this->db->query('SELECT m.*, t.intake_id FROM tf_service_message m JOIN tf_tour t ON t.id = m.tour_id WHERE m.id = ?', [$messageId])->getRowArray();
            if (!$m || empty($m['intake_id'])) {
                return;
            }
            $state = (string) $m['state'];
            // tf-supmail-r2: one history entry per ATTEMPT, not one per request. A letter that failed and was
            // then sent must show both, and the entry that says "sent" must carry the wording that actually
            // went. The guard is on this exact attempt, so calling this twice for one attempt still writes once.
            if ($this->db->query("SELECT id FROM tf_comm WHERE ref_table = 'tf_service_message' AND ref_id = ? AND send_state = ? AND occurred_at = ?",
                    [$messageId, $state === 'sent' ? 'sent' : ($state === 'failed' ? 'failed' : 'not_sent'), $m['at']])->getRowArray()) {
                return;
            }
            $mark = $state === 'failed' ? '[NOT SENT — ' . ($m['error'] ?: 'failed') . '] '
                : ($state === 'recorded' ? '[' . strtoupper((string) $m['channel']) . '] ' : ($state === 'prepared' ? '[PREPARED, NOT SENT] ' : ''));
            (new Comms($this->db, $this->user, null, $this->actor))->add((int) $m['intake_id'], [
                'direction' => (string) $m['kind'] === 'reply' ? 'in' : 'out',
                'channel' => in_array((string) $m['channel'], ['email', 'whatsapp'], true) ? (string) $m['channel'] : 'note',
                'source' => 'system', 'ref_table' => 'tf_service_message', 'ref_id' => $messageId, 'tag' => '',
                'occurred_at' => $m['at'], 'to_addr' => $m['to_addr'], 'cc_addr' => $m['cc_addr'],
                'subject' => $mark . (string) $m['subject'], 'body' => (string) $m['body'],
                'send_state' => $state === 'sent' ? 'sent' : ($state === 'failed' ? 'failed' : 'not_sent'),
                'send_error' => $m['error'],
            ]);
        } catch (\Throwable $e) {
            // the request stands on its own record; the thread copy is best effort
        }
    }

    /** what has not gone and still has to — for the desk's own list and count */
    public function unsent(int $tourId = 0): array
    {
        if (!self::ready($this->db)) {
            return [];
        }
        $sql = "SELECT m.*, t.tour_no FROM tf_service_message m JOIN tf_tour t ON t.id = m.tour_id
                 WHERE m.kind = 'request' AND m.state IN ('prepared', 'failed') AND t.stage <> 'cancelled' AND t.archived_at IS NULL";
        $bind = [];
        if ($tourId > 0) {
            $sql .= ' AND m.tour_id = ?';
            $bind[] = $tourId;
        }
        return $this->db->query($sql . ' ORDER BY m.id DESC LIMIT 200', $bind)->getResultArray();
    }
}
