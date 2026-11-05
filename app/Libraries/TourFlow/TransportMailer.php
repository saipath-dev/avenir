<?php

namespace App\Libraries\TourFlow;

/**
 * tf-transmail-r1 (3 Nov 2026) — the transport booking requests: to the vehicle's supplier, to the driver and to
 * the guide engaged on a confirmed tour.
 *
 * Every assignment gets a request PREPARED automatically from the record — the tour, the dates, the party, the
 * vehicle and the days of service. Where the person or the supplier has an e-mail address on the master, it is
 * prepared to go by e-mail through the company's own mailbox; where there is none, the same words are prepared as
 * a WhatsApp message to send by hand and as a sheet to print. Nothing is invented: a missing address is named.
 *
 * Preparing is not sending, and sending is not confirming. A request leaves the vehicle, the driver and the guide
 * exactly as they were — the Transport team records the supplier's or the driver's own answer on the card, and only
 * that makes a booking confirmed. Every communication is kept here with its channel, its state and its reason, and
 * is mirrored into Communications & History against the tour.
 */
final class TransportMailer
{
    private $db;
    private string $user;
    private ?string $actor;
    private ?int $userId = null;

    /** the values a master uses for "nothing here" */
    private const EMPTY_VALUES = ['', '0', '-', 'N/A', 'NA', 'NIL', 'NONE', 'NULL'];

    public function __construct($db, string $user = '', ?string $actor = null, ?int $userId = null)
    {
        $this->db = $db;
        $this->user = $user;
        $this->actor = $actor ?: ($user ?: null);
        $this->userId = $userId;
    }

    public static function ready($db): bool
    {
        try {
            return $db->query("SHOW TABLES LIKE 'tf_transport_message'")->getNumRows() > 0;
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

    /**
     * Who has to be told about this tour's transport, with the address or phone the masters hold.
     * One row per party: the vehicle's supplier, the driver, the guide. Nothing is written.
     */
    public function parties(int $tourId): array
    {
        $out = [];
        $jobs = $this->db->query('SELECT * FROM tf_transport_job WHERE tour_id = ? AND archived_at IS NULL ORDER BY seq, id', [$tourId])->getResultArray();
        foreach ($jobs as $j) {
            $jobId = (int) $j['id'];
            // the supplier behind the vehicle: the vehicle's own owner, or the supplier account on the crew line
            $supAcc = null;
            $supName = null;
            if (!empty($j['vehicle_id'])) {
                $v = $this->db->query('SELECT v.id, v.reg_no, v.person_id, p.name AS owner_name, p.supplier_acc, p.email AS owner_email, p.mobile AS owner_mobile, p.whatsapp AS owner_whatsapp
                                         FROM tf_crew_vehicle v LEFT JOIN tf_crew_person p ON p.id = v.person_id WHERE v.id = ?', [(int) $j['vehicle_id']])->getRowArray();
                if ($v) {
                    $supAcc  = self::clean($v['supplier_acc']);
                    $supName = self::clean($v['owner_name']);
                    $out[] = $this->party($tourId, $jobId, null, 'supplier',
                        $supName ?: ('Vehicle owner — ' . ($v['reg_no'] ?? '')),
                        self::email($v['owner_email']), self::phone($v['owner_whatsapp'] ?: $v['owner_mobile']),
                        $supAcc, 'the vehicle owner on the Crew & Vehicle master');
                    continue;
                }
            }
            if (self::clean($j['vehicle_reg']) !== null) {
                $out[] = $this->party($tourId, $jobId, null, 'supplier', 'Vehicle ' . $j['vehicle_reg'], null, null, null,
                    'the vehicle was typed in by hand, so it is on no master and has no contact');
            }
        }
        $crew = $this->db->query('SELECT c.*, j.id AS job_id, p.email AS person_email, p.mobile AS person_mobile, p.whatsapp AS person_whatsapp, p.name AS master_name
                                    FROM tf_transport_crew c JOIN tf_transport_job j ON j.id = c.job_id
                                    LEFT JOIN tf_crew_person p ON p.id = c.person_id
                                   WHERE j.tour_id = ? AND j.archived_at IS NULL AND c.status = \'engaged\' ORDER BY c.id', [$tourId])->getResultArray();
        foreach ($crew as $c) {
            $role = (string) $c['kind'] === 'guide' ? 'guide' : 'driver';
            $out[] = $this->party($tourId, (int) $c['job_id'], (int) $c['id'], $role,
                self::clean($c['person_name']) ?: (self::clean($c['master_name']) ?: ucfirst($role)),
                self::email($c['person_email']), self::phone($c['person_whatsapp'] ?: $c['person_mobile']),
                self::clean($c['supplier_acc']),
                $c['person_id'] ? 'the Crew master' : 'the name was typed in by hand, so it is on no master');
        }
        return $out;
    }

    /** one row of parties(), with the address worked out and what is missing said plainly */
    private function party(int $tourId, ?int $jobId, ?int $crewId, string $role, string $name, ?string $email, ?string $phone, ?string $supAcc, string $source): array
    {
        if ($email === null && $supAcc !== null) {
            try {
                $s = $this->db->query('SELECT email, Mobile, TP FROM supplier WHERE Sup_Acc_No = ? LIMIT 1', [$supAcc])->getRowArray();
                if ($s) {
                    $email = self::email($s['email']);
                    $phone = $phone ?: self::phone($s['Mobile'] ?: $s['TP']);
                    if ($email !== null) {
                        $source = 'the supplier record ' . $supAcc;
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        $channel = $email !== null ? 'email' : ($phone !== null ? 'whatsapp' : 'print');
        $missing = null;
        if ($email === null) {
            $missing = $phone !== null
                ? 'No e-mail address for ' . $name . ' — the request is prepared as a WhatsApp message to ' . $phone . ' and as a sheet to print.'
                : 'No e-mail address and no telephone number for ' . $name . ' (' . $source . ') — the request can only be printed. Put the contact on the master.';
        }
        return ['tour_id' => $tourId, 'job_id' => $jobId, 'crew_id' => $crewId, 'party' => $role, 'name' => $name,
                'email' => $email, 'phone' => $phone, 'channel' => $channel, 'source' => $source, 'missing' => $missing];
    }

    /** the words of the request, from the record — the same text whichever way it goes */
    public function letter(int $tourId, array $p): array
    {
        $t = $this->db->query('SELECT t.*, i.inquiry_no FROM tf_tour t LEFT JOIN tf_intake i ON i.id = t.intake_id WHERE t.id = ?', [$tourId])->getRowArray() ?: [];
        $j = $p['job_id'] ? $this->db->query('SELECT * FROM tf_transport_job WHERE id = ?', [(int) $p['job_id']])->getRowArray() : null;
        $c = $p['crew_id'] ? $this->db->query('SELECT * FROM tf_transport_crew WHERE id = ?', [(int) $p['crew_id']])->getRowArray() : null;
        $d = static fn ($x) => $x ? date('j M Y', strtotime((string) $x)) : '—';
        $dates = $d($t['arrival_date'] ?? null) . ' – ' . $d($t['departure_date'] ?? null);
        $what = $p['party'] === 'supplier'
            ? 'vehicle ' . trim((string) (($j['vehicle_type'] ?? '') . ' ' . ($j['vehicle_reg'] ?? '')))
            : ($p['party'] === 'guide' ? 'guide services' : 'driver services');
        $days = $c && !empty($c['date_from'])
            ? $d($c['date_from']) . ' – ' . $d($c['date_to'] ?: ($t['departure_date'] ?? null))
            : $dates;
        $subject = 'Booking request — ' . ($t['tour_no'] ?? '') . ' · ' . $dates . ' · ' . ucfirst($p['party']);
        // tf-mailtpl-r1 (4 Nov 2026): the saved "Driver, guide & transport request" wording, filled from this
        // tour and this party. The letter below is the fallback, used only when no template is saved for the
        // group — so a request is never held up by a missing template, and never goes out empty.
        try {
            $mw   = new MailWords($this->db);
            // the party's own words first, then the tour's — the party decides {supplier}, {role} and {vehicle}
            $vars = $mw->transportWords($tourId, $p) + $mw->forIntake((int) ($t['intake_id'] ?? 0), ['name' => $this->actor ?: $this->user]);
            $et    = new EmailTemplates($this->db);
            $start = EmailTemplates::starting(EmailTemplates::filled($et->forWindow('transport_request'), $vars), 'transport_request');
            if ($start && trim((string) $start['message']) !== '') {
                return [
                    'subject' => trim((string) $start['subject']) !== '' ? (string) $start['subject'] : $subject,
                    'text'    => rtrim((string) $start['message']),
                    'ref'     => ($t['tour_no'] ?? '') . ' / ' . ($t['inquiry_no'] ?? ''),
                ];
            }
        } catch (\Throwable $e) {
            // no templates on this install: the letter below, exactly as before
        }
        // the fallback letter, used only when this install has no saved transport wording
        $lines = [
            'Dear ' . $p['name'] . ',',
            '',
            'We request your ' . $what . ' for the following confirmed tour.',
            '',
            'Tour: ' . ($t['tour_no'] ?? '') . ($t['tour_name'] ? ' — ' . $t['tour_name'] : ''),
            'Agent: ' . ($t['agent_name'] ?: '—'),
            'Dates: ' . $dates,
            'Passengers: ' . (($t['pax_total'] ?? null) !== null ? $t['pax_total'] : '—'),
        ];
        if ($j) {
            $lines[] = 'Vehicle: ' . trim((string) (($j['vehicle_type'] ?? '') . ' ' . ($j['vehicle_reg'] ? '(' . $j['vehicle_reg'] . ')' : '')));
        }
        if ($c) {
            $lines[] = 'Service days: ' . $days;
            if (self::clean($c['role_name']) !== null) {
                $lines[] = 'Role: ' . $c['role_name'] . (self::clean($c['language']) ? ' · ' . $c['language'] : '');
            }
        }
        $lines[] = '';
        $lines[] = 'Please confirm your availability by reply. This is a request, not a confirmation: the booking is';
        $lines[] = 'held as pending on our side until your confirmation is recorded by our Transport desk.';
        $lines[] = '';
        $lines[] = 'Avenir Holidays — Transport';
        return ['subject' => $subject, 'text' => implode("\n", $lines), 'ref' => ($t['tour_no'] ?? '') . ' / ' . ($t['inquiry_no'] ?? '')];
    }

    /**
     * Prepare a request for every party that has none yet. Writes 'prepared' rows only — nothing is sent, nothing
     * on a card is touched. Safe to call again: a party that already has a request on this tour is left alone.
     */
    public function prepare(int $tourId, bool $send = true): array
    {
        if (!self::ready($this->db)) {
            return ['prepared' => 0, 'rows' => []];
        }
        $made = [];
        foreach ($this->parties($tourId) as $p) {
            $has = $this->db->query('SELECT id, state FROM tf_transport_message WHERE tour_id = ? AND party = ? AND COALESCE(crew_id, 0) = ? AND COALESCE(job_id, 0) = ? AND kind = \'request\' ORDER BY id DESC LIMIT 1',
                [$tourId, $p['party'], (int) ($p['crew_id'] ?? 0), (int) ($p['job_id'] ?? 0)])->getRowArray();
            $l = $this->letter($tourId, $p);
            if ($has) {
                // a request still only prepared follows the record: the desk may have changed the driver, the
                // vehicle or the dates since. One already sent, failed or handled by hand is never rewritten.
                if ((string) $has['state'] === 'prepared') {
                    $this->db->query('UPDATE tf_transport_message SET party_name = ?, channel = ?, to_addr = ?, to_phone = ?, subject = ?, body = ?, by_name = ?, `at` = ? WHERE id = ?',
                        [$p['name'], $p['channel'], $p['email'], $p['phone'], $l['subject'], $l['text'], $this->actor, $this->now(), (int) $has['id']]);
                }
                continue;
            }
            $this->db->query('INSERT INTO tf_transport_message (tour_id, job_id, crew_id, party, party_name, kind, channel, state, to_addr, to_phone, subject, body, by_name, `at`)
                              VALUES (?,?,?,?,?,\'request\',?,\'prepared\',?,?,?,?,?,?)',
                [$tourId, $p['job_id'], $p['crew_id'], $p['party'], $p['name'], $p['channel'], $p['email'], $p['phone'], $l['subject'], $l['text'], $this->actor, $this->now()]);
            $made[] = ['id' => (int) $this->db->insertID()] + $p;
        }
        return ['prepared' => count($made), 'rows' => $made] + ($send ? $this->autoSend($made) : []);
    }

    /**
     * tf-transmail-r2: the requests that CAN go, go — the moment the party is assigned, as the hotels' letters go
     * at Confirm. One with no address, or with no mailbox to send from, stays 'prepared' with the reason; it is
     * never called 'sent'. Nothing here touches the vehicle, the driver or the guide: a request is not a booking.
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
        if (!self::autoSendOn($this->db)) {
            // tf-transmail-r2: automatic sending is OFF until it is switched on for this installation. The letters
            // are written and wait; nothing goes out by itself, and nothing is ever called 'sent' that did not go.
            $out['held'] = count($withAddress);
            $out['why'] = AutoSend::HELD;
            return $out;
        }
        if (!self::canSend($sender)) {
            // no mailbox yet: the letters wait, and the desk is told why. Nothing is marked sent.
            $out['held'] = count($withAddress);
            $out['why'] = $sender['note'] ?? 'No mailbox is set up to send from yet.';
            return $out;
        }
        $sender['user_id'] = $this->userId();
        foreach ($withAddress as $r) {
            try {
                $res = $this->send((int) $r['id'], $sender);
                $out[!empty($res['ok']) ? 'sent' : 'failed']++;
            } catch (\Throwable $e) {
                $out['failed']++;
            }
        }
        return $out;
    }

    /** the signed-in person, when there is one: the mailbox a request goes from is theirs or the company's */
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

    /** every communication on this tour, newest first */
    public function messages(int $tourId): array
    {
        if (!self::ready($this->db)) {
            return [];
        }
        return $this->db->query('SELECT * FROM tf_transport_message WHERE tour_id = ? ORDER BY id DESC', [$tourId])->getResultArray();
    }



    /**
     * Whether a prepared request may go out by itself. OFF unless tf_meta.transport_autosend is '1' — so a
     * development or a freshly installed system never mails a supplier on its own, and switching it on is a
     * deliberate act recorded in the settings.
     */
    public static function autoSendOn($db): bool
    {
        return AutoSend::on($db);
    }

    /** Whether there is a mailbox to send from at all: the company's, or the signed-in person's own. */
    public static function canSend(array $sender): bool
    {
        return (string) ($sender['kind'] ?? 'none') !== 'none' && trim((string) ($sender['email'] ?? '')) !== '';
    }

    /**
     * Send a prepared request by e-mail, through the company's own mailbox. The result — sent or the reason it
     * was not — is kept on the row. The vehicle, the driver and the guide are not touched: a request is not a
     * confirmation.
     */
    public function send(int $messageId, array $sender, ?string $to = null, ?string $cc = null): array
    {
        $m = $this->db->query('SELECT * FROM tf_transport_message WHERE id = ?', [$messageId])->getRowArray();
        if (!$m) {
            throw new \RuntimeException('That request is not on the record.');
        }
        if ((string) $m['state'] === 'sent') {
            return ['ok' => true, 'already' => true, 'message' => 'It was already sent on ' . substr((string) $m['at'], 0, 16) . ' to ' . $m['to_addr'] . '. It is not sent twice.'];
        }
        $addr = self::email($to ?: $m['to_addr']);
        if ($addr === null) {
            throw new \RuntimeException('There is no e-mail address for ' . $m['party_name'] . '. Send it as a WhatsApp message or print it, and record that here.');
        }
        if (!self::canSend($sender)) {
            throw new \RuntimeException(($sender['note'] ?? 'No mailbox is set up to send from.') . ' Nothing was sent. Print the request, or send it on WhatsApp and record it here.');
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
        $this->db->query('UPDATE tf_transport_message SET state = ?, channel = \'email\', to_addr = ?, cc_addr = ?, error = ?, by_name = ?, `at` = ? WHERE id = ?',
            [$ok ? 'sent' : 'failed', $addr, $ccAll !== '' ? $ccAll : null, $ok ? null : mb_substr((string) ($r['error'] ?? 'not sent'), 0, 500), $this->actor, $this->now(), $messageId]);
        $this->toComms($messageId);
        return ['ok' => $ok, 'message' => $ok
            ? 'Sent to ' . $addr . '. The mail server accepted it — that is not proof it was read; the booking stays pending until the Transport desk records the answer.'
            : 'NOT sent — ' . ($r['error'] ?? 'the mail server refused it') . ' The attempt is kept on the record.'];
    }

    /** the request was handled by hand — WhatsApp, a phone call, a printed sheet — or an answer came back */
    public function record(int $messageId, string $channel, string $note, string $kind = 'request'): array
    {
        $m = $this->db->query('SELECT * FROM tf_transport_message WHERE id = ?', [$messageId])->getRowArray();
        if (!$m) {
            throw new \RuntimeException('That request is not on the record.');
        }
        $channel = in_array($channel, ['whatsapp', 'print', 'other', 'email'], true) ? $channel : 'other';
        if ($kind === 'reply') {
            $this->db->query('INSERT INTO tf_transport_message (tour_id, job_id, crew_id, party, party_name, kind, channel, state, to_addr, to_phone, subject, body, by_name, `at`)
                              VALUES (?,?,?,?,?,\'reply\',?,\'recorded\',?,?,?,?,?,?)',
                [$m['tour_id'], $m['job_id'], $m['crew_id'], $m['party'], $m['party_name'], $channel, $m['to_addr'], $m['to_phone'],
                 'Reply — ' . $m['party_name'], mb_substr($note, 0, 4000), $this->actor, $this->now()]);
            $this->toComms((int) $this->db->insertID());
            return ['ok' => true, 'message' => 'The answer is on the record. It does not confirm the booking — record the confirmation on the card.'];
        }
        $this->db->query('UPDATE tf_transport_message SET state = \'recorded\', channel = ?, error = NULL, body = CONCAT(COALESCE(body, \'\'), ?), by_name = ?, `at` = ? WHERE id = ?',
            [$channel, "\n\n--- sent by hand (" . $channel . ") on " . $this->now() . " by " . $this->actor . ($note !== '' ? ': ' . mb_substr($note, 0, 500) : '') . ' ---', $this->actor, $this->now(), $messageId]);
        $this->toComms($messageId);
        return ['ok' => true, 'message' => 'Recorded as sent by ' . $channel . '. The booking stays pending until the answer is recorded.'];
    }


    /**
     * The communication as Communications & History keeps it: the same thread as the hotels' letters and the
     * agent's mail, against the tour's inquiry, with a guard so one message is never written twice.
     */
    private function toComms(int $messageId): void
    {
        try {
            $m = $this->db->query('SELECT m.*, t.intake_id FROM tf_transport_message m JOIN tf_tour t ON t.id = m.tour_id WHERE m.id = ?', [$messageId])->getRowArray();
            if (!$m || empty($m['intake_id'])) {
                return;
            }
            $state = (string) $m['state'];
            // tf-supmail-r2: one history entry per ATTEMPT, not one per request. A letter that failed and was
            // then sent must show both, and the entry that says "sent" must carry the wording that actually
            // went. The guard is on this exact attempt, so calling this twice for one attempt still writes once.
            if ($this->db->query("SELECT id FROM tf_comm WHERE ref_table = 'tf_transport_message' AND ref_id = ? AND send_state = ? AND occurred_at = ?",
                    [$messageId, $state === 'sent' ? 'sent' : ($state === 'failed' ? 'failed' : 'not_sent'), $m['at']])->getRowArray()) {
                return;
            }
            $mark = $state === 'failed' ? '[NOT SENT — ' . ($m['error'] ?: 'failed') . '] '
                : ($state === 'recorded' ? '[' . strtoupper((string) $m['channel']) . '] ' : ($state === 'prepared' ? '[PREPARED, NOT SENT] ' : ''));
            (new Comms($this->db, $this->user, null, $this->actor))->add((int) $m['intake_id'], [
                'direction' => (string) $m['kind'] === 'reply' ? 'in' : 'out',
                // Communications keeps its own list of channels; a printed sheet is recorded there as a note
                'channel' => in_array((string) $m['channel'], ['email', 'whatsapp'], true) ? (string) $m['channel'] : 'note',
                'source' => 'system', 'ref_table' => 'tf_transport_message', 'ref_id' => $messageId, 'tag' => '',
                'occurred_at' => $m['at'], 'to_addr' => $m['to_addr'], 'cc_addr' => $m['cc_addr'],
                'subject' => $mark . (string) $m['subject'], 'body' => (string) $m['body'],
                'send_state' => $state === 'sent' ? 'sent' : ($state === 'failed' ? 'failed' : 'not_sent'),
                'send_error' => $m['error'],
            ]);
        } catch (\Throwable $e) {
            // the request stands on its own record; the thread copy is best effort
        }
    }

    /** what has not gone and still has to — for the desk's own count */
    public function unsent(int $tourId = 0): array
    {
        if (!self::ready($this->db)) {
            return [];
        }
        $sql = "SELECT m.*, t.tour_no FROM tf_transport_message m JOIN tf_tour t ON t.id = m.tour_id
                 WHERE m.kind = 'request' AND m.state IN ('prepared', 'failed') AND t.stage <> 'cancelled' AND t.archived_at IS NULL";
        $bind = [];
        if ($tourId > 0) {
            $sql .= ' AND m.tour_id = ?';
            $bind[] = $tourId;
        }
        return $this->db->query($sql . ' ORDER BY m.id DESC LIMIT 200', $bind)->getResultArray();
    }
}
