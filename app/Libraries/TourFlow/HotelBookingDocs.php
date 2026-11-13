<?php

namespace App\Libraries\TourFlow;

/**
 * tf-resdesk-r2 (12 Nov 2026) — the documents of a hotel booking, kept for good.
 *
 *   booking_voucher  — the Hotel Booking Voucher (PDF) attached to the e-mail that asks the hotel to reserve the stay.
 *                      A new version is kept only when its content changes (an amended stay); every version stays.
 *   confirmation     — the hotel's confirmation evidence uploaded with Confirm Booking (its e-mail, a PDF, a picture).
 *
 * Files live under writable/tf_hotel_docs/<tour id>/ with their SHA-256; a row is never deleted (tf_hotel_doc). The e-mail
 * itself (subject, body, recipient, time, result) is the existing copy in tf_hotel_message; each message names the voucher
 * version attached to it (tf_hotel_message.doc_id), so "View Sent Email" and "View Booking Voucher" open exactly what went.
 *
 * Also adds, once, the columns this change needs:
 *   tf_tour.hotel_seen_at / hotel_seen_by    — the first time the Reservation Desk opened the tour (until then: New booking)
 *   tf_hotel_job.hotel_confirmed_on          — the date the hotel confirmed (as the hotel wrote it)
 */
final class HotelBookingDocs
{
    public const MAX_BYTES = 20 * 1024 * 1024;
    public const EXT = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'eml', 'msg', 'txt', 'doc', 'docx'];

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
        $db->query("CREATE TABLE IF NOT EXISTS `tf_hotel_doc` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `job_id` INT NOT NULL,
            `tour_id` INT NOT NULL,
            `kind` VARCHAR(20) NOT NULL,
            `version` INT NOT NULL DEFAULT 1,
            `message_id` INT NULL,
            `original_name` VARCHAR(255) NOT NULL,
            `stored_name` VARCHAR(255) NOT NULL,
            `mime` VARCHAR(100) NULL,
            `size_bytes` INT NOT NULL DEFAULT 0,
            `sha256` CHAR(64) NOT NULL,
            `note` VARCHAR(500) NULL,
            `by` VARCHAR(50) NULL,
            `at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `job_kind` (`job_id`, `kind`),
            KEY `tour` (`tour_id`)
        )");
        $add = static function ($db, string $t, string $c, string $ddl): void {
            if (!$db->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'")->getResultArray()) {
                $db->query("ALTER TABLE `{$t}` ADD COLUMN {$ddl}");
            }
        };
        $add($db, 'tf_tour', 'hotel_seen_at', '`hotel_seen_at` DATETIME NULL');
        $add($db, 'tf_tour', 'hotel_seen_by', '`hotel_seen_by` VARCHAR(50) NULL');
        $add($db, 'tf_hotel_job', 'hotel_confirmed_on', '`hotel_confirmed_on` DATE NULL');
        $add($db, 'tf_hotel_message', 'doc_id', '`doc_id` INT NULL');
        $add($db, 'tf_hotel_message', 'doc2_id', '`doc2_id` INT NULL');
        $done = true;
    }

    private function dir(int $tourId): string
    {
        $d = rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'tf_hotel_docs' . DIRECTORY_SEPARATOR . $tourId;
        if (!is_dir($d)) {
            @mkdir($d, 0775, true);
        }
        return $d;
    }

    public function get(int $id): ?array
    {
        return $this->db->query('SELECT * FROM tf_hotel_doc WHERE id = ?', [$id])->getRowArray() ?: null;
    }

    public function path(array $doc): ?string
    {
        $p = $this->dir((int) $doc['tour_id']) . DIRECTORY_SEPARATOR . basename((string) $doc['stored_name']);
        return is_file($p) ? $p : null;
    }

    /** @return array<int, array> every document of the stay, newest first */
    public function forJob(int $jobId): array
    {
        return $this->db->query('SELECT id, job_id, tour_id, kind, version, message_id, original_name, mime, size_bytes, sha256, note, `by`, `at` FROM tf_hotel_doc WHERE job_id = ? ORDER BY id DESC', [$jobId])->getResultArray();
    }

    private function store(int $jobId, int $tourId, string $kind, string $bytesOrPath, bool $isPath, string $name, ?string $mime, ?string $note): array
    {
        $sha = $isPath ? hash_file('sha256', $bytesOrPath) : hash('sha256', $bytesOrPath);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $v = (int) ($this->db->query('SELECT COALESCE(MAX(version),0) AS v FROM tf_hotel_doc WHERE job_id = ? AND kind = ?', [$jobId, $kind])->getRowArray()['v'] ?? 0) + 1;
        $stored = $kind . '-' . $jobId . '-v' . $v . '-' . substr($sha, 0, 12) . ($ext !== '' ? '.' . $ext : '');
        $path = $this->dir($tourId) . DIRECTORY_SEPARATOR . $stored;
        $ok = $isPath ? copy($bytesOrPath, $path) : (file_put_contents($path, $bytesOrPath) !== false);
        if (!$ok) {
            throw new \RuntimeException('The document could not be saved (writable/tf_hotel_docs).');
        }
        $this->db->query('INSERT INTO tf_hotel_doc (job_id, tour_id, kind, version, original_name, stored_name, mime, size_bytes, sha256, note, `by`, `at`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$jobId, $tourId, $kind, $v, mb_substr($name, 0, 255), $stored, $mime, (int) filesize($path), $sha, $note !== null ? mb_substr($note, 0, 500) : null, $this->user, date('Y-m-d H:i:s')]);
        return $this->get((int) $this->db->insertID());
    }

    public function linkMessage(int $docId, int $messageId): void
    {
        // each message names the Booking Request version attached to it; every version keeps the first message it went with
        $d = $this->get($docId);
        if ($d && (string) $d['kind'] === 'booking_voucher') {
            $this->db->query('UPDATE tf_hotel_message SET doc_id = ? WHERE id = ?', [$docId, $messageId]);
        } elseif ($d && (string) $d['kind'] === 'rooming_list') {
            $this->db->query('UPDATE tf_hotel_message SET doc2_id = ? WHERE id = ?', [$docId, $messageId]);
        }
        $this->db->query('UPDATE tf_hotel_doc SET message_id = ? WHERE id = ? AND message_id IS NULL', [$messageId, $docId]);
    }

    /** The hotel's confirmation evidence, uploaded with Confirm Booking. */
    public function addEvidence(int $jobId, int $tourId, string $tmpPath, string $originalName, ?string $mime, ?string $note): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::EXT, true)) {
            throw new \RuntimeException('That file type is not accepted. Use PDF, a picture (JPG, PNG, WEBP), an e-mail file (EML, MSG), TXT or Word.');
        }
        if (!is_file($tmpPath) || filesize($tmpPath) <= 0) {
            throw new \RuntimeException('The file is empty.');
        }
        if (filesize($tmpPath) > self::MAX_BYTES) {
            throw new \RuntimeException('The file is larger than 20 MB.');
        }
        return $this->store($jobId, $tourId, 'confirmation', $tmpPath, true, $originalName, $mime, $note);
    }

    public static function voucherNo(array $doc): string
    {
        return trim((string) ($doc['tour']['tour_no'] ?? 'TOUR')) . '-H' . str_pad((string) ((int) ($doc['stay']['day_from'] ?? 0)), 2, '0', STR_PAD_LEFT);
    }

    private static function d($v): string
    {
        return $v ? date('D j M Y', strtotime((string) $v)) : '—';
    }

    public const MEAL = ['RO' => 'Room only', 'BB' => 'Bed & Breakfast', 'HB' => 'Half Board', 'FB' => 'Full Board', 'AI' => 'All Inclusive'];

    public static function logoPath(): ?string
    {
        foreach (['assets/images/email/avenir-logo-email.png', 'assets/images/email/logo.png'] as $rel) {
            if (defined('FCPATH') && is_file(FCPATH . $rel)) {
                return FCPATH . $rel;
            }
        }
        return null;
    }

    public static function mealWord(?string $m): string
    {
        $m = strtoupper(trim((string) $m));
        return $m === '' ? '—' : (self::MEAL[$m] ?? $m) . (isset(self::MEAL[$m]) ? ' (' . $m . ')' : '');
    }

    /** The guest line of a stay: "10 adults", "3 adults + 2 children". */
    public static function guestWords(array $doc, array $rl): string
    {
        $t = $doc['tour']; $s = $doc['stay'];
        $a = (int) ($t['pax_adults'] ?? 0); $c = (int) ($t['pax_children'] ?? 0);
        if ($a || $c) {
            return trim(($a ? $a . ' adult' . ($a === 1 ? '' : 's') : '') . ($c ? ($a ? ' + ' : '') . $c . ' child' . ($c === 1 ? '' : 'ren') : ''));
        }
        $n = (int) ($s['pax'] ?? $t['pax_total'] ?? $rl['total_guests']);
        return $n . ' guest' . ($n === 1 ? '' : 's');
    }

    /** The reservation as the Hotel Booking Request PDF shows it (TCPDF HTML). No price. */
    public static function requestHtml(array $doc, array $rl): string
    {
        $e = static fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $h = $doc['hotel']; $s = $doc['stay']; $no = self::voucherNo($doc);
        $lead = '';
        foreach ((array) ($doc['passengers'] ?? []) as $p) {
            if (trim((string) ($p['full_name'] ?? '')) !== '') { $lead = trim((string) $p['full_name']); break; }
        }
        $hotel = $h['name'] . (!empty($s['area']) ? ', ' . $s['area'] : (!empty($h['address']) ? ', ' . $h['address'] : ''));
        $row = static fn ($k, $v, $last = false) => '<tr><td width="34%" style="background-color:#F6EEF7;color:#2A1630;font-weight:bold;font-size:11pt;border-bottom:0.4px solid #B98FC0;border-right:0.4px solid #B98FC0">' . $k . '</td>'
            . '<td width="66%" style="font-size:11pt;color:#1B1620;border-bottom:0.4px solid #B98FC0">' . $v . '</td></tr>';
        $html = '<p style="font-size:12pt;font-weight:bold;color:#1B1620">Dear Hotel Reservations Team,</p>'
            . '<p style="font-size:11pt;color:#2A2430">Greetings from <span style="color:#842484;font-weight:bold">Avenir Holidays</span>.<br>We would be grateful if you could kindly book the following accommodation and confirm the reservation at your earliest convenience.</p>'
            . '<table cellpadding="0" cellspacing="0" width="100%"><tr><td width="22%"></td><td width="56%" style="background-color:#842484;color:#FFFFFF;text-align:center;font-size:12pt;font-weight:bold;line-height:2">RESERVATION CONTROL NO.</td><td width="22%"></td></tr></table>'
            . '<table cellpadding="7" cellspacing="0" width="100%" style="border:1.4px solid #842484"><tr><td style="text-align:center;font-size:24pt;font-weight:bold;color:#1B1620"><span style="color:#78b848">—— </span>' . $e($no) . '<span style="color:#78b848"> ——</span></td></tr></table>'
            . '<p style="font-size:9pt;color:#55505A;text-align:center">Tour ' . $e($doc['tour']['tour_no']) . (!empty($doc['tour']['inquiry_no']) ? ' · Inquiry ' . $e($doc['tour']['inquiry_no']) : '') . '</p>'
            . '<table cellpadding="0" cellspacing="0" width="100%"><tr><td width="50%" style="font-size:13pt;font-weight:bold;color:#1B1620"><span style="color:#842484">&#9632;</span> RESERVATION DETAILS</td><td width="50%" style="border-bottom:1px solid #78b848"></td></tr></table><br>'
            . '<table cellpadding="6" cellspacing="0" width="100%" style="border:0.6px solid #842484">'
            . $row('Hotel', $e($hotel))
            . $row('Check-in', $e(self::d($s['check_in'])))
            . $row('Check-out', $e(self::d($s['check_out'])))
            . $row('Nights', $e((int) $s['nights']))
            . $row('Guests', $e(self::guestWords($doc, $rl)))
            . $row('Lead guest', $lead !== '' ? $e($lead) : 'To follow')
            . $row('Rooms', $e($rl['summary'] !== '' ? $rl['summary'] . ' (' . $rl['total_rooms'] . ' room' . ($rl['total_rooms'] === 1 ? '' : 's') . ')' : '—'))
            . $row('Meal plan', $e(count($rl['meals']) > 1 ? 'Differs by room — see the Rooming List' : self::mealWord($s['meal_plan'])))
            . $row('Special requests', !empty($s['special_note']) ? nl2br($e($s['special_note'])) : '—')
            . (!empty($s['crew_rooms']) ? $row('Crew rooms', $e($s['crew_rooms'])) : '')
            . $row('Rooming list', '<b>Rooming List attached</b> — ' . (int) $rl['total_guests'] . ' guests in ' . (int) $rl['total_rooms'] . ' rooms')
            . '</table><br><br>'
            . '<table cellpadding="9" cellspacing="0" width="100%" style="background-color:#E7F3DA"><tr><td width="10%" style="text-align:center;font-size:20pt;color:#4E8F2A">&#10004;</td>'
            . '<td width="90%" style="font-size:12pt;font-weight:bold;color:#10300A;border-left:1.2px solid #78b848">To confirm this booking, please quote the Reservation Control No. (' . $e($no) . ') in your reply.'
            . '<br><span style="font-size:10pt;font-weight:normal">Your confirmation number: ______________________________</span></td></tr></table>';
        return $html;
    }

    /** The guests room by room (TCPDF HTML; the heading row repeats on every page). */
    public static function roomingHtml(array $doc, array $rl): string
    {
        $e = static fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $h = $doc['hotel']; $s = $doc['stay']; $no = self::voucherNo($doc);
        $maxG = 2;
        foreach ($rl['rooms'] as $r) {
            $maxG = max($maxG, count($r['guests']));
        }
        $perMeal = count($rl['meals']) > 1;
        $hotel = $h['name'] . (!empty($s['area']) ? ', ' . $s['area'] : '');
        $info = static fn ($k, $v) => '<tr><td width="34%" style="font-weight:bold;font-size:11.5pt;color:#1B1620">' . $k . '</td><td width="66%" style="font-size:11.5pt;color:#1B1620;border-left:1px solid #78b848">' . $v . '</td></tr>';
        $html = '<table cellpadding="0" cellspacing="0" width="100%"><tr><td width="18%"></td><td width="64%" style="border:1.4px solid #842484;text-align:center;font-size:15pt;font-weight:bold;line-height:2;color:#1B1620">Reservation Control No:  <span style="color:#842484">|</span>  ' . $e($no) . '</td><td width="18%"></td></tr></table><br><br>'
            . '<table cellpadding="0" cellspacing="0" width="100%"><tr><td width="55%"><table cellpadding="3">'
            . $info('Hotel:', $e($hotel))
            . $info('Stay:', $e(date('j M Y', strtotime((string) $s['check_in'])) . ' – ' . date('j M Y', strtotime((string) $s['check_out'])) . ' (' . (int) $s['nights'] . ' night' . ((int) $s['nights'] === 1 ? '' : 's') . ')'))
            . $info('Total guests:', $e(self::guestWords($doc, $rl)))
            . '</table></td><td width="45%" style="border-left:1px solid #78b848"><table cellpadding="3">'
            . $info('Total rooms:', $e($rl['summary'] . ' (' . $rl['total_rooms'] . ')'))
            . $info('Meal plan:', $e($perMeal ? 'Per room — see the list' : self::mealWord($s['meal_plan'])))
            . $info('Tour:', $e($doc['tour']['tour_no']))
            . '</table></td></tr></table><br><br>';
        $gw = $perMeal ? 50 : 60;
        $gcol = max(1, $maxG);
        $wEach = round($gw / $gcol, 2);
        $th = static fn ($t, $w, $sub = '') => '<th width="' . $w . '%" style="background-color:#842484;color:#FFFFFF;font-weight:bold;font-size:11.5pt;text-align:center;border:0.5px solid #842484">' . $t . ($sub !== '' ? '<br><span style="font-size:8.5pt;font-weight:normal">' . $sub . '</span>' : '') . '</th>';
        $html .= '<table cellpadding="6" cellspacing="0" width="100%" style="border:0.6px solid #842484"><thead><tr>'
            . $th('Allocation', 11) . $th('Hotel room no.', 15, '(Hotel to fill)') . $th('Room type', 14);
        for ($g = 1; $g <= $gcol; $g++) {
            $html .= $th('Guest ' . $g, $wEach);
        }
        if ($perMeal) {
            $html .= $th('Meal plan', 10);
        }
        $html .= '</tr></thead><tbody>';
        $td = static fn ($v, $w, $st = '') => '<td width="' . $w . '%" style="font-size:12pt;color:#1B1620;border:0.5px solid #B98FC0;' . $st . '">' . $v . '</td>';
        foreach ($rl['rooms'] as $k => $r) {
            $html .= '<tr nobr="true">' . $td('<b>' . str_pad((string) ($k + 1), 2, '0', STR_PAD_LEFT) . '</b>', 11, 'text-align:center;font-size:13pt') . $td('&nbsp;', 15) . $td($e(RoomingList::roomLabel($r, $rl['room_type_text'])), 14, 'font-size:10.5pt');
            for ($g = 0; $g < $gcol; $g++) {
                $gg = $r['guests'][$g] ?? null;
                $html .= $td($gg ? '<b>' . $e($gg['name']) . '</b>' . ($gg['no_bed'] ? '<br><span style="font-size:8.5pt;font-weight:normal">(sharing, no extra bed)</span>' : '') : '', $wEach);
            }
            if ($perMeal) {
                $html .= $td($e($r['meal']), 10, 'text-align:center');
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        if ($rl['unallocated']) {
            $html .= '<p style="color:#A3261E;font-size:11pt;font-weight:bold">Still to allocate: ' . $e(implode(', ', $rl['unallocated'])) . '</p>';
        }
        $html .= '<br><br><table cellpadding="8" cellspacing="0" width="100%" style="background-color:#E7F3DA"><tr><td style="text-align:center;font-size:13pt;font-weight:bold;font-style:italic;color:#10300A">Hotel room numbers to be completed by the hotel.</td></tr></table>';
        return $html;
    }

    /** One of the two documents as a PDF (bytes). */
    public static function documentPdf(string $kind, array $doc, array $rl): string
    {
        $pdf = new HotelDocPdf($kind === 'rooming' ? 'L' : 'P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->kind = $kind;
        $pdf->control = self::voucherNo($doc);
        $pdf->docTitle = $kind === 'rooming' ? 'HOTEL ROOMING LIST' : 'HOTEL BOOKING REQUEST';
        $pdf->logo = self::logoPath();
        $c = $doc['company'];
        $sd = $doc['sender'] ?? [];
        $pdf->footerLine = trim(($c['name'] ?? 'Avenir Holidays') . ' · ' . ($sd['name'] ?? '') . (!empty($sd['email']) ? ' · ' . $sd['email'] : '') . (!empty($c['phone']) ? ' · ' . $c['phone'] : ''));
        $pdf->SetCreator('Avenir Holidays');
        $pdf->SetAuthor('Avenir Holidays');
        $pdf->SetTitle($pdf->docTitle . ' ' . $pdf->control);
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(14, 22, 14);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(14);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->AddPage();
        $pdf->SetY($kind === 'rooming' ? 50 : 50);
        $pdf->writeHTML($kind === 'rooming' ? self::roomingHtml($doc, $rl) : self::requestHtml($doc, $rl), true, false, true, false, '');
        return $pdf->Output('doc.pdf', 'S');
    }

    /** The rooming list of the stay, from its record (an override for a preview only). */
    public static function rooming(array $doc): array
    {
        return RoomingList::build($doc['stay'], (array) ($doc['passengers'] ?? []), $doc['stay']['room_type_text'] ?? null);
    }

    /**
     * Both documents of a stay, kept: the Hotel Booking Request (kind booking_voucher) and the Hotel Rooming List (kind
     * rooming_list). A version is stored only when its content changed. Returns ['request' => row+path, 'rooming' => row+path].
     */
    public function bookingDocs(TourService $svc, int $jobId, array $sender): array
    {
        $doc = $svc->hotelDocument($jobId, 'request', $sender);
        $rl = self::rooming($doc);
        $no = self::voucherNo($doc);
        $slug = preg_replace('/[^A-Za-z0-9\-]+/', '-', $no . '-' . ($doc['hotel']['name'] ?? 'hotel'));
        $out = [];
        foreach (['request' => ['booking_voucher', 'Hotel-Booking-Request-' . $slug . '.pdf', self::requestHtml($doc, $rl)], 'rooming' => ['rooming_list', 'Hotel-Rooming-List-' . $slug . '.pdf', self::roomingHtml($doc, $rl)]] as $k => [$kind, $name, $html]) {
            $sha = hash('sha256', 'r2b|' . $html);
            $last = $this->db->query('SELECT * FROM tf_hotel_doc WHERE job_id = ? AND kind = ? ORDER BY version DESC LIMIT 1', [$jobId, $kind])->getRowArray();
            if ($last && (string) ($last['note'] ?? '') === 'content ' . $sha && ($p = $this->path($last))) {
                $out[$k] = $last + ['path' => $p];
                continue;
            }
            $row = $this->store($jobId, (int) $doc['tour']['id'], $kind, self::documentPdf($k, $doc, $rl), false, $name, 'application/pdf', 'content ' . $sha);
            $out[$k] = $row + ['path' => $this->path($row)];
        }
        $out['rooming_list'] = $rl;
        return $out;
    }

    /** Kept for older callers: the Hotel Booking Request alone. */
    public function bookingVoucher(TourService $svc, int $jobId, array $sender): array
    {
        return $this->bookingDocs($svc, $jobId, $sender)['request'];
    }

    public static function pdf(string $html, string $title): string
    {
        if (!class_exists('\TCPDF')) {
            $lib = ROOTPATH . 'vendor' . DIRECTORY_SEPARATOR . 'tecnickcom' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'tcpdf.php';
            if (is_file($lib)) {
                require_once $lib;
            }
        }
        if (!class_exists('\TCPDF')) {
            throw new \RuntimeException('The PDF library (TCPDF) is not installed.');
        }
        $p = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $p->SetCreator('Avenir Holidays');
        $p->SetAuthor('Avenir Holidays');
        $p->SetTitle($title);
        $p->setPrintHeader(false);
        $p->setPrintFooter(false);
        $p->SetMargins(14, 14, 14);
        $p->SetAutoPageBreak(true, 14);
        $p->SetFont('dejavusans', '', 10);
        $p->AddPage();
        $p->writeHTML($html, true, false, true, false, '');
        return $p->Output('voucher.pdf', 'S');
    }
}
