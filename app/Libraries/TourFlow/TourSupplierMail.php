<?php

namespace App\Libraries\TourFlow;

/**
 * tf-supdesk-r1 (19 Nov 2026, Shantha) — Supplier Emails & Vouchers of ONE confirmed tour, for Operations Control.
 *
 * One entry for every hotel reservation and every booked service provider (restaurants, safari operators, spice
 * gardens and other activity suppliers, the vehicle supplier, the driver, the guide). Each entry carries the provider,
 * the booked service, the recipient address from the hotel / supplier master, what is missing, and where its e-mail
 * stands. Nothing here invents anything and nothing here sends by itself: confirming a tour PREPARES the messages;
 * a member of staff reads the address, the e-mail and the voucher, and presses Send Email for that one reservation.
 *
 * It rests on what the desks already use and changes none of it: the hotel letters (TourService::hotelDocument, the
 * Hotel Booking Request and Rooming List PDFs of HotelBookingDocs, HotelMail), the activity / restaurant requests
 * (SupplierMailer) and the transport requests (TransportMailer). What it adds:
 *
 *   tf_supplier_send_log  one line per ATTEMPT, kept for good — the tour, the entry, the exact subject and body that
 *                         went, the recipient, the attachments (with the stored copy), who, when, sent or failed, and
 *                         whether it was a first send, a retry after a failure, or a deliberate resend with its reason.
 *   tf_send_guard         a short lock per entry, so a second click or a refresh while a message is going sends nothing.
 *
 * "E-mail sent" is never "booking confirmed": sending leaves the supplier's booking state exactly where the desk's own
 * rules put it, and the entry shows the two side by side.
 */
class TourSupplierMail
{
    private $db;
    private string $user;
    private string $actor;
    private ?int $userId;
    private static bool $ensured = false;

    public function __construct($db, string $user, string $actor, ?int $userId)
    {
        $this->db     = $db;
        $this->user   = $user;
        $this->actor  = $actor !== '' ? $actor : $user;
        $this->userId = $userId;
        self::ensure($db);
    }

    public static function ensure($db): void
    {
        if (self::$ensured) {
            return;
        }
        self::$ensured = true;
        $db->query("CREATE TABLE IF NOT EXISTS `tf_supplier_send_log` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `tour_id` INT UNSIGNED NOT NULL,
            `type` VARCHAR(12) NOT NULL, `ref_id` INT UNSIGNED NOT NULL, `message_id` INT UNSIGNED NULL, `action` VARCHAR(10) NOT NULL, `reason` VARCHAR(400) NULL,
            `provider` VARCHAR(200) NULL, `to_addr` VARCHAR(400) NULL, `cc_addr` VARCHAR(400) NULL, `subject` VARCHAR(255) NULL, `body` MEDIUMTEXT NULL,
            `attachments` TEXT NULL, `result` VARCHAR(10) NOT NULL, `error` VARCHAR(500) NULL, `by_name` VARCHAR(100) NULL, `at` DATETIME NOT NULL,
            PRIMARY KEY (`id`), KEY `k_entry` (`tour_id`, `type`, `ref_id`, `id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS `tf_send_guard` (`k` VARCHAR(60) NOT NULL, `at` DATETIME NOT NULL, `by_name` VARCHAR(100) NULL, PRIMARY KEY (`k`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private static function d($v): string
    {
        return $v ? date('D j M Y', strtotime((string) $v)) : '—';
    }

    private function tour(int $tourId): array
    {
        $t = $this->db->query('SELECT * FROM tf_tour WHERE id = ?', [$tourId])->getRowArray();
        if (!$t) {
            throw new \RuntimeException('That tour was not found.');
        }
        return $t;
    }

    /** every attempt of this tour, newest first, grouped per entry ("hotel:12") */
    private function logs(int $tourId): array
    {
        $out = [];
        foreach ($this->db->query('SELECT id, type, ref_id, action, reason, to_addr, subject, attachments, result, error, by_name, at FROM tf_supplier_send_log WHERE tour_id = ? ORDER BY id DESC', [$tourId])->getResultArray() as $r) {
            $r['attachments'] = array_map(static fn ($a) => ['name' => $a['name'] ?? 'file'], (array) json_decode((string) ($r['attachments'] ?? '[]'), true));
            $out[$r['type'] . ':' . $r['ref_id']][] = $r;
        }
        return $out;
    }

    private static function status(array $logs): array
    {
        $last = $logs[0] ?? null;
        $sent = null;
        foreach ($logs as $l) {
            if ($l['result'] === 'sent') {
                $sent = $l;
                break;
            }
        }
        $n = count(array_filter($logs, static fn ($l) => $l['result'] === 'sent'));
        return ['state' => !$last ? 'not_sent' : ($last['result'] === 'sent' ? 'sent' : ($sent ? 'sent' : 'failed')),
                'last_failed' => $last && $last['result'] !== 'sent' ? $last : null, 'sent' => $sent, 'times_sent' => $n];
    }

    /** The entries of a tour: hotels, then activity suppliers and restaurants, then transport. Reads; prepares; never sends. */
    public function entries(TourService $svc, int $tourId, array $sender, bool $canHotel, bool $canTransport): array
    {
        $t    = $this->tour($tourId);
        $logs = $this->logs($tourId);
        $out  = [];
        $mailbox = (string) ($sender['kind'] ?? 'none') !== 'none' && trim((string) ($sender['email'] ?? '')) !== '';
        $tm = new TourMailer($this->db, $this->user, $this->userId, $this->actor);

        // ---- hotels: one entry per stay ----
        $jobs = $this->db->query("SELECT * FROM tf_hotel_job WHERE tour_id = ? AND archived_at IS NULL AND status <> 'cancelled' ORDER BY check_in, seq, id", [$tourId])->getResultArray();
        foreach ($jobs as $j) {
            $addr  = $tm->hotelAddress($j['hotel_id'], $j['hotel_acc']);
            $flags = [];
            if ((int) $j['needs_hotel']) {
                $flags[] = ['stop' => true, 'text' => 'No hotel is named for these nights. The Quotation Desk has to name one.'];
            }
            if ((int) $j['needs_meal']) {
                $flags[] = ['stop' => true, 'text' => 'The meal plan is not stated on this stay.'];
            }
            if (empty($addr['email'])) {
                $flags[] = ['stop' => true, 'text' => 'No reservations e-mail address on the Hotel Master or the supplier record for this hotel.'];
            }
            if (trim((string) ($j['rooms_note'] ?? '')) === '' && (int) ($j['rooms'] ?? 0) === 0) {
                $flags[] = ['stop' => false, 'text' => 'No rooms are stated on this stay.'];
            }
            $lm = $this->db->query("SELECT state, at, by_name, to_addr, channel FROM tf_hotel_message WHERE job_id = ? AND kind = 'request' ORDER BY id DESC LIMIT 1", [(int) $j['id']])->getRowArray();
            $key = 'hotel:' . $j['id'];
            $st  = self::status($logs[$key] ?? []);
            $out[] = [
                'type' => 'hotel', 'id' => (int) $j['id'], 'group' => 'Hotel', 'provider' => (string) ($j['hotel_name'] ?: 'Hotel not named'),
                'service' => trim(self::d($j['check_in']) . ' – ' . self::d($j['check_out']) . ' · ' . (int) $j['nights'] . ' night' . ((int) $j['nights'] === 1 ? '' : 's')
                    . ($j['rooms_note'] ? ' · ' . $j['rooms_note'] : '') . ($j['meal_plan'] ? ' · ' . $j['meal_plan'] : '')),
                'date' => $j['check_in'], 'recipient' => $addr['email'] ?? null,
                'recipient_source' => ($addr['source'] ?? null) === 'master' ? 'Hotel Master' : (($addr['source'] ?? null) === 'supplier' ? 'supplier record' : null),
                'flags' => $flags, 'booking' => self::bookingWord((string) $j['status']), 'booking_state' => (string) $j['status'],
                'earlier' => $lm && empty($logs[$key]) ? ['state' => $lm['state'], 'at' => $lm['at'], 'by' => $lm['by_name'], 'to' => $lm['to_addr'], 'channel' => $lm['channel']] : null,
                'vouchers' => [['key' => 'request', 'label' => 'Hotel Booking Request'], ['key' => 'rooming', 'label' => 'Rooming List']],
                'can' => $canHotel, 'mailbox' => $mailbox, 'history' => $logs[$key] ?? [],
            ] + $st;
        }

        // ---- activity suppliers and restaurants ----
        if (SupplierMailer::ready($this->db)) {
            $sm = new SupplierMailer($this->db, $this->user, $this->actor, $this->userId);
            try {
                $sm->prepare($tourId, false);
            } catch (\Throwable $e) {
                log_message('error', 'Supplier requests could not be prepared: ' . $e->getMessage());
            }
            foreach ($sm->parties($tourId) as $p) {
                $m = $this->db->query("SELECT * FROM tf_service_message WHERE job_id = ? AND party = ? AND kind = 'request' ORDER BY id DESC LIMIT 1", [(int) $p['job_id'], $p['party']])->getRowArray();
                $flags = [];
                if (empty($p['email'])) {
                    $flags[] = ['stop' => true, 'text' => (string) ($p['missing'] ?: 'No e-mail address for ' . $p['name'] . '.')];
                }
                if ($m) {
                    $hole = $sm->holes((int) $m['id'], $m);
                    if ($hole !== null) {
                        $flags[] = ['stop' => true, 'text' => $hole];
                    }
                } else {
                    $flags[] = ['stop' => true, 'text' => 'The request could not be prepared from the record.'];
                }
                $line = (array) ($p['line'] ?? []);
                $key  = 'service:' . $p['job_id'];
                $st   = self::status($logs[$key] ?? []);
                $out[] = [
                    'type' => 'service', 'id' => (int) $p['job_id'], 'group' => $p['party'] === 'restaurant' ? 'Restaurant' : 'Activity / supplier', 'provider' => (string) $p['name'],
                    'service' => trim((string) $p['title'] . ' · ' . self::d($p['on_date']) . (!empty($line['on_time']) ? ' · ' . substr((string) $line['on_time'], 0, 5) : '') . ($p['pax'] ? ' · ' . (int) $p['pax'] . ' pax' : '')),
                    'date' => $p['on_date'], 'recipient' => $p['email'] ?: null, 'recipient_source' => $p['email'] ? (string) $p['source'] : null,
                    'flags' => $flags, 'booking' => self::bookingWord((string) $p['status']), 'booking_state' => (string) $p['status'],
                    'earlier' => $m && in_array((string) $m['state'], ['sent', 'failed', 'recorded'], true) && empty($logs[$key]) ? ['state' => $m['state'], 'at' => $m['at'], 'by' => $m['by_name'], 'to' => $m['to_addr'], 'channel' => $m['channel']] : null,
                    'vouchers' => [['key' => 'service', 'label' => $p['party'] === 'restaurant' ? 'Meal Voucher' : 'Service Voucher']],
                    'can' => $canHotel, 'mailbox' => $mailbox, 'history' => $logs[$key] ?? [],
                ] + $st;
            }
        }

        // ---- transport: the vehicle supplier, the driver, the guide ----
        if (TransportMailer::ready($this->db)) {
            $tr = new TransportMailer($this->db, $this->user, $this->actor);
            try {
                $tr->prepare($tourId, false);
            } catch (\Throwable $e) {
                log_message('error', 'Transport requests could not be prepared: ' . $e->getMessage());
            }
            $names = ['supplier' => 'Vehicle supplier', 'driver' => 'Driver', 'guide' => 'Guide'];
            foreach ($tr->messages($tourId) as $m) {
                if ((string) $m['kind'] !== 'request') {
                    continue;
                }
                $flags = [];
                if (trim((string) ($m['to_addr'] ?? '')) === '') {
                    $flags[] = ['stop' => true, 'text' => 'No e-mail address for ' . $m['party_name'] . ' on the master. It can be sent by hand or printed from the Transport desk.'];
                }
                $key = 'transport:' . $m['id'];
                $st  = self::status($logs[$key] ?? []);
                $out[] = [
                    'type' => 'transport', 'id' => (int) $m['id'], 'group' => $names[(string) $m['party']] ?? 'Transport', 'provider' => (string) $m['party_name'],
                    'service' => trim((string) preg_replace('/^Booking request\s*[—-]\s*' . preg_quote((string) $t['tour_no'], '/') . '\s*[—-]\s*/u', '', (string) $m['subject'])),
                    'date' => $t['arrival_date'], 'recipient' => trim((string) ($m['to_addr'] ?? '')) ?: null, 'recipient_source' => trim((string) ($m['to_addr'] ?? '')) !== '' ? 'master' : null,
                    'flags' => $flags, 'booking' => null, 'booking_state' => null,
                    'earlier' => in_array((string) $m['state'], ['sent', 'failed', 'recorded'], true) && empty($logs[$key]) ? ['state' => $m['state'], 'at' => $m['at'], 'by' => $m['by_name'], 'to' => $m['to_addr'], 'channel' => $m['channel']] : null,
                    'vouchers' => [['key' => 'transport', 'label' => 'Transport Booking Voucher']],
                    'can' => $canTransport, 'mailbox' => $mailbox, 'history' => $logs[$key] ?? [],
                ] + $st;
            }
        }
        return $out;
    }

    private static function bookingWord(string $s): string
    {
        $w = ['to_book' => 'To book', 'requested' => 'Requested — awaiting the supplier', 'confirmed' => 'Confirmed by the supplier', 'voucher_issued' => 'Confirmed — voucher issued',
              'problem' => 'Problem', 'done' => 'Done', 'booked' => 'Booked', 'pending' => 'Pending'];
        return $w[$s] ?? ucfirst(str_replace('_', ' ', $s));
    }

    // ------------------------------------------------------------------------------------------------ the letter

    /** What would go for one entry: to, subject, html body, attachments [{path,name}], provider, message_id. Builds the PDFs. */
    public function compose(TourService $svc, int $tourId, string $type, int $id, array $who): array
    {
        $t = $this->tour($tourId);
        if ($type === 'hotel') {
            $j = $this->db->query('SELECT * FROM tf_hotel_job WHERE id = ? AND tour_id = ?', [$id, $tourId])->getRowArray();
            if (!$j) {
                throw new \RuntimeException('That stay is not on this tour.');
            }
            $doc  = $svc->hotelDocument($id, 'request', $who);
            $both = (new HotelBookingDocs($this->db, $this->actor))->bookingDocs($svc, $id, $who);
            $addr = (new TourMailer($this->db, $this->user, $this->userId, $this->actor))->hotelAddress($j['hotel_id'], $j['hotel_acc']);
            return ['to' => $addr['email'] ?? null, 'subject' => (string) $doc['subject'], 'provider' => (string) $j['hotel_name'], 'message_id' => null,
                    'html' => view('pages/tour/hotel_booking_email', ['doc' => $doc, 'control' => HotelBookingDocs::voucherNo($doc), 'message' => null]),
                    // the Rooming List goes only when the stay has rooms on it — the request then says so, and not otherwise
                    'attachments' => array_merge([['path' => $both['request']['path'], 'name' => $both['request']['original_name'], 'doc_id' => (int) $both['request']['id']]],
                        (int) ($both['rooming_list']['total_rooms'] ?? 0) > 0 ? [['path' => $both['rooming']['path'], 'name' => $both['rooming']['original_name'], 'doc_id' => (int) $both['rooming']['id']]] : [])];
        }
        $sender = CommsMail::senderFor($this->db, $this->userId, $this->user);
        if ($type === 'service') {
            $m = $this->db->query("SELECT m.* FROM tf_service_message m JOIN tf_service_job j ON j.id = m.job_id WHERE m.job_id = ? AND m.tour_id = ? AND m.kind = 'request' ORDER BY m.id DESC LIMIT 1", [$id, $tourId])->getRowArray();
            $j = $this->db->query('SELECT * FROM tf_service_job WHERE id = ? AND tour_id = ?', [$id, $tourId])->getRowArray();
            if (!$m || !$j) {
                throw new \RuntimeException('That request is not on this tour.');
            }
            $meal = (string) $m['party'] === 'restaurant';
            $no   = $t['tour_no'] . '-S' . str_pad((string) ((int) $j['seq'] ?: (int) $j['id']), 2, '0', STR_PAD_LEFT);
            $rows = [['Supplier', (string) $m['party_name']], ['Service', (string) $j['title']], ['Date', self::d($j['on_date'])]];
            if (!empty($j['on_time'])) {
                $rows[] = ['Time', substr((string) $j['on_time'], 0, 5)];
            }
            $rows[] = ['Guests', $j['pax'] ? (int) $j['pax'] . ' pax' : '—'];
            $rows[] = ['Group / tour', trim((string) $t['tour_name']) ?: (string) $t['tour_no']];
            if (trim((string) ($j['special_note'] ?? '')) !== '') {
                $rows[] = ['Special requests', (string) $j['special_note']];
            }
            $pdf = self::voucherPdf($meal ? 'MEAL VOUCHER' : 'SERVICE VOUCHER', $no, $rows, $who,
                'Please provide the service above against this voucher and quote the voucher number on your invoice to Avenir Holidays. The guests are not to be charged for the service listed.');
            $name = ($meal ? 'Meal-Voucher-' : 'Service-Voucher-') . preg_replace('/[^A-Za-z0-9\-]+/', '-', $no . '-' . $m['party_name']) . '.pdf';
            return ['to' => trim((string) ($m['to_addr'] ?? '')) ?: null, 'subject' => (string) $m['subject'], 'provider' => (string) $m['party_name'], 'message_id' => (int) $m['id'],
                    'html' => CommsMail::html(nl2br(esc(EmailTemplates::withoutSignOff((string) $m['body']))), CommsMail::signature($this->db, $this->userId, $this->user), (string) $m['subject']),
                    'attachments' => [['bytes' => $pdf, 'name' => $name]], 'sender' => $sender];
        }
        if ($type === 'transport') {
            $m = $this->db->query('SELECT * FROM tf_transport_message WHERE id = ? AND tour_id = ?', [$id, $tourId])->getRowArray();
            if (!$m) {
                throw new \RuntimeException('That request is not on this tour.');
            }
            $no   = $t['tour_no'] . '-T' . str_pad((string) (int) $m['id'], 2, '0', STR_PAD_LEFT);
            $rows = [[ucfirst((string) $m['party']), (string) $m['party_name']], ['Service', trim((string) preg_replace('/^Booking request\s*[—-]\s*/u', '', (string) $m['subject']))],
                     ['Tour dates', self::d($t['arrival_date']) . ' – ' . self::d($t['departure_date'])], ['Guests', $t['pax_total'] ? (int) $t['pax_total'] . ' pax' : '—'],
                     ['Group / tour', trim((string) $t['tour_name']) ?: (string) $t['tour_no']]];
            $pdf  = self::voucherPdf('TRANSPORT BOOKING VOUCHER', $no, $rows, $who, 'Please confirm this booking to Avenir Holidays and quote the voucher number on your invoice or settlement.');
            return ['to' => trim((string) ($m['to_addr'] ?? '')) ?: null, 'subject' => (string) $m['subject'], 'provider' => (string) $m['party_name'], 'message_id' => (int) $m['id'],
                    'html' => CommsMail::html(nl2br(esc(EmailTemplates::withoutSignOff((string) $m['body']))), CommsMail::signature($this->db, $this->userId, $this->user), (string) $m['subject']),
                    'attachments' => [['bytes' => $pdf, 'name' => 'Transport-Booking-Voucher-' . preg_replace('/[^A-Za-z0-9\-]+/', '-', $no . '-' . $m['party_name']) . '.pdf']], 'sender' => $sender];
        }
        throw new \RuntimeException('Unknown entry.');
    }

    /** A plain voucher on the company's existing document frame (the Hotel Booking Request frame). No rate, no price. */
    private static function voucherPdf(string $title, string $no, array $rows, array $who, string $foot): string
    {
        if (!class_exists('\TCPDF')) {
            require_once ROOTPATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';
        }
        $pdf = new HotelDocPdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->kind = 'request';
        $pdf->control = $no;
        $pdf->docTitle = $title;
        $pdf->logo = HotelBookingDocs::logoPath();
        $pdf->footerLine = trim('Avenir Holidays · ' . ($who['name'] ?? '') . (!empty($who['email']) ? ' · ' . $who['email'] : ''));
        $pdf->SetCreator('Avenir Holidays');
        $pdf->SetAuthor('Avenir Holidays');
        $pdf->SetTitle($title . ' ' . $no);
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(14, 22, 14);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(14);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->AddPage();
        $pdf->SetY(50);
        $e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $h = '<p style="font-size:11pt"><b>Voucher No.</b> &nbsp; <span style="font-size:15pt"><b>' . $e($no) . '</b></span></p>'
           . '<table cellpadding="6" border="1" style="border-color:#bbbbbb;font-size:10.5pt">';
        foreach ($rows as [$k, $v]) {
            $h .= '<tr><td width="32%" style="background-color:#f4eef1"><b>' . $e($k) . '</b></td><td width="68%">' . nl2br($e($v)) . '</td></tr>';
        }
        $h .= '</table><p style="font-size:10pt"><br>' . $e($foot) . '</p><p style="font-size:9pt;color:#666666">Issued ' . date('j M Y') . ' by ' . $e($who['name'] ?? 'Avenir Holidays') . '</p>';
        $pdf->writeHTML($h, true, false, true, false, '');
        return $pdf->Output('voucher.pdf', 'S');
    }

    /** One voucher of an entry as a PDF: [bytes, name]. */
    public function voucher(TourService $svc, int $tourId, string $type, int $id, string $which, array $who): array
    {
        $c = $this->compose($svc, $tourId, $type, $id, $who);
        $a = $c['attachments'][$type === 'hotel' && $which === 'rooming' ? 1 : 0] ?? null;
        if (!$a) {
            throw new \RuntimeException('There is no voucher for this entry.');
        }
        return [isset($a['bytes']) ? $a['bytes'] : (string) file_get_contents($a['path']), $a['name']];
    }

    /** The exact e-mail of one past attempt (for "view what was sent"). */
    public function logRow(int $tourId, int $logId): ?array
    {
        return $this->db->query('SELECT * FROM tf_supplier_send_log WHERE id = ? AND tour_id = ?', [$logId, $tourId])->getRowArray() ?: null;
    }

    public function logFile(array $row, int $i): ?array
    {
        $a = ((array) json_decode((string) ($row['attachments'] ?? '[]'), true))[$i] ?? null;
        $p = $a && !empty($a['file']) ? WRITEPATH . 'tf_supplier_docs' . DIRECTORY_SEPARATOR . (int) $row['tour_id'] . DIRECTORY_SEPARATOR . basename((string) $a['file']) : null;
        return $p && is_file($p) ? ['path' => $p, 'name' => (string) $a['name']] : null;
    }

    // -------------------------------------------------------------------------------------------------- sending

    /**
     * Send ONE entry's e-mail, now, because a person pressed Send Email on it.
     *   first send   — nothing sent yet
     *   retry        — the last attempt failed
     *   resend       — it was already sent: only with $resend and a reason, and it is recorded as a deliberate resend
     * A second press while one is going, or a refresh that posts again, sends nothing.
     */
    public function send(TourService $svc, int $tourId, string $type, int $id, array $who, bool $resend, string $reason): array
    {
        $key = substr($type, 0, 1) . ':' . $tourId . ':' . $id;
        $this->db->query('DELETE FROM tf_send_guard WHERE k = ? AND at < ?', [$key, date('Y-m-d H:i:s', time() - 180)]);
        try {
            $this->db->query('INSERT INTO tf_send_guard (k, at, by_name) VALUES (?, ?, ?)', [$key, $this->now(), mb_substr($this->actor, 0, 100)]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'busy' => true, 'message' => 'This e-mail is already being sent. Nothing was sent twice.'];
        }
        if ((int) $this->db->affectedRows() < 1) {
            return ['ok' => false, 'busy' => true, 'message' => 'This e-mail is already being sent. Nothing was sent twice.'];
        }
        try {
            $st = self::status($this->logs($tourId)[$type . ':' . $id] ?? []);
            $action = 'send';
            if ($st['state'] === 'sent' && !$st['last_failed']) {
                if (!$resend) {
                    return ['ok' => false, 'already' => true, 'message' => 'Already sent on ' . substr((string) $st['sent']['at'], 0, 16) . ' to ' . $st['sent']['to_addr'] . ' by ' . $st['sent']['by_name'] . '. It was not sent again.'];
                }
                if (trim($reason) === '') {
                    return ['ok' => false, 'message' => 'Say why it is sent again; the reason is kept with the resend. Nothing was sent.'];
                }
                $action = 'resend';
            } elseif ($st['last_failed']) {
                $action = $st['times_sent'] > 0 ? 'resend' : 'retry';
            }
            $c  = $this->compose($svc, $tourId, $type, $id, $who);
            $to = TourMailer::email($c['to']);
            if ($to === null) {
                return ['ok' => false, 'message' => 'There is no e-mail address for ' . $c['provider'] . ' on the master. Nothing was sent.'];
            }
            // the copies of the attachments that go, kept with the attempt
            $dir = WRITEPATH . 'tf_supplier_docs' . DIRECTORY_SEPARATOR . $tourId;
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $att = [];
            $kept = [];
            foreach ($c['attachments'] as $a) {
                $bytes = isset($a['bytes']) ? $a['bytes'] : (string) file_get_contents($a['path']);
                $file  = substr(hash('sha256', $bytes), 0, 16) . '-' . preg_replace('/[^A-Za-z0-9\.\-]+/', '-', $a['name']);
                if (!is_file($dir . DIRECTORY_SEPARATOR . $file)) {
                    file_put_contents($dir . DIRECTORY_SEPARATOR . $file, $bytes);
                }
                $att[]  = ['path' => $dir . DIRECTORY_SEPARATOR . $file, 'name' => $a['name']];
                $kept[] = ['name' => $a['name'], 'file' => $file];
            }
            $ok = false;
            $err = null;
            $messageId = $c['message_id'];
            if ($type === 'hotel') {
                $r   = HotelMail::send($to, $c['subject'], $c['html'], [], $att);
                $ok  = !empty($r['ok']);
                $err = $ok ? null : (string) ($r['error'] ?? 'The mail server did not accept the message.');
                try {
                    if ($ok) {
                        $svc->hotelRequestSent($id, 'email', $to, '', $c['subject'], $c['html'], $this->actor);
                    } else {
                        $svc->recordHotelMessage($id, 'request', 'email', $to, '', $c['subject'], $c['html'], 'failed', $err, $this->actor);
                    }
                    $mm = $this->db->query('SELECT MAX(id) AS id FROM tf_hotel_message WHERE job_id = ?', [$id])->getRowArray();
                    $messageId = !empty($mm['id']) ? (int) $mm['id'] : null;
                    if ($messageId) {
                        $hd = new HotelBookingDocs($this->db, $this->actor);
                        foreach ($c['attachments'] as $a) {
                            if (!empty($a['doc_id'])) {
                                $hd->linkMessage((int) $a['doc_id'], $messageId);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    if ($ok) {
                        log_message('error', 'Hotel request sent but its desk record failed: ' . $e->getMessage());
                    } else {
                        $err = $err ?: $e->getMessage();
                    }
                }
            } else {
                $sender = $c['sender'];
                if ((string) ($sender['kind'] ?? 'none') === 'none' || trim((string) ($sender['email'] ?? '')) === '') {
                    return ['ok' => false, 'message' => ($sender['note'] ?? 'No mailbox is set up to send from.') . ' Nothing was sent.'];
                }
                $r = CommsMail::send($this->db, [
                    'to' => [$to], 'cc' => [], 'subject' => $c['subject'], 'html' => $c['html'], 'attachments' => $att,
                    'reply_to'   => $sender['reply_to'] ?? null,
                    'from_email' => (string) ($sender['kind'] ?? '') === 'system' ? (string) ($sender['email'] ?? '') : '',
                    'from_name'  => (string) ($sender['name'] ?? 'Avenir Holidays'),
                    'password'   => (string) ($sender['kind'] ?? '') === 'system' ? CommsMail::systemPassword($this->db) : '',
                    'user_id'    => $this->userId,
                ]);
                $ok  = !empty($r['ok']);
                $err = $ok ? null : (string) ($r['error'] ?? 'The mail server refused it.');
                // the desk's own row says where the request stands; the booking itself is not touched
                $table = $type === 'service' ? 'tf_service_message' : 'tf_transport_message';
                $this->db->query("UPDATE {$table} SET state = ?, channel = 'email', to_addr = ?, error = ?, by_name = ?, `at` = ? WHERE id = ?",
                    [$ok ? 'sent' : 'failed', $to, $ok ? null : mb_substr((string) $err, 0, 500), mb_substr($this->actor, 0, 100), $this->now(), (int) $messageId]);
            }
            $this->db->query('INSERT INTO tf_supplier_send_log (tour_id, type, ref_id, message_id, action, reason, provider, to_addr, subject, body, attachments, result, error, by_name, at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$tourId, $type, $id, $messageId, $action, trim($reason) !== '' ? mb_substr(trim($reason), 0, 400) : null, mb_substr((string) $c['provider'], 0, 200), $to,
                 mb_substr((string) $c['subject'], 0, 255), $c['html'], json_encode($kept), $ok ? 'sent' : 'failed', $ok ? null : mb_substr((string) $err, 0, 500), mb_substr($this->actor, 0, 100), $this->now()]);
            try {
                $t = $this->tour($tourId);
                $svc->event((int) $t['intake_id'], $tourId, 'tour', $tourId, $ok ? 'supplier_mail_sent' : 'supplier_mail_failed', null, null,
                    ($action === 'resend' ? 'Deliberate resend' : ($action === 'retry' ? 'Retry after a failed send' : 'E-mail')) . ' to ' . $c['provider'] . ' (' . $to . ') by ' . $this->actor
                    . ($ok ? ' — sent.' : ' — NOT sent: ' . $err) . (trim($reason) !== '' ? ' Reason: ' . trim($reason) : ''));
            } catch (\Throwable $e) {
                // the attempt is on its own log either way
            }
            return ['ok' => $ok, 'action' => $action, 'message' => $ok
                ? ($action === 'resend' ? 'Sent again to ' : 'Sent to ') . $to . '. The copy is kept on this tour. The booking stays as it is until the supplier confirms.'
                : 'NOT sent — ' . $err . ' The attempt is kept on this tour; it can be tried again.'];
        } finally {
            $this->db->query('DELETE FROM tf_send_guard WHERE k = ?', [$key]);
        }
    }
}
