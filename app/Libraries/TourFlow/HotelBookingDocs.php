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
        $row = static fn ($k, $v, $last = false) => '<tr><td width="34%" style="background-color:#f7eef1;color:#32141e;font-weight:bold;font-size:11pt;border-bottom:0.4px solid #c08f9f;border-right:0.4px solid #c08f9f">' . $k . '</td>'
            . '<td width="66%" style="font-size:11pt;color:#1B1620;border-bottom:0.4px solid #c08f9f">' . $v . '</td></tr>';
        $html = '<p style="font-size:12pt;font-weight:bold;color:#1B1620">Dear Hotel Reservations Team,</p>'
            . '<p style="font-size:11pt;color:#2A2430">Greetings from <span style="color:#8b1d40;font-weight:bold">Avenir Holidays</span>.<br>We would be grateful if you could kindly book the following accommodation and confirm the reservation at your earliest convenience.</p>'
            . '<table cellpadding="0" cellspacing="0" width="100%"><tr><td width="22%"></td><td width="56%" style="background-color:#8b1d40;color:#FFFFFF;text-align:center;font-size:12pt;font-weight:bold;line-height:2">RESERVATION CONTROL NO.</td><td width="22%"></td></tr></table>'
            . '<table cellpadding="7" cellspacing="0" width="100%" style="border:1.4px solid #8b1d40"><tr><td style="text-align:center;font-size:24pt;font-weight:bold;color:#1B1620"><span style="color:#78b848">—— </span>' . $e($no) . '<span style="color:#78b848"> ——</span></td></tr></table>'
            . '<p style="font-size:9pt;color:#55505A;text-align:center">Tour ' . $e($doc['tour']['tour_no']) . (!empty($doc['tour']['inquiry_no']) ? ' · Inquiry ' . $e($doc['tour']['inquiry_no']) : '') . '</p>'
            . '<table cellpadding="0" cellspacing="0" width="100%"><tr><td width="50%" style="font-size:13pt;font-weight:bold;color:#1B1620"><span style="color:#8b1d40">&#9632;</span> RESERVATION DETAILS</td><td width="50%" style="border-bottom:1px solid #78b848"></td></tr></table><br>'
            . '<table cellpadding="6" cellspacing="0" width="100%" style="border:0.6px solid #8b1d40">'
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

    /**
     * tf-hotelreq-r5 (19 Nov 2026, Shantha's second mock-up): the Hotel Booking Request — the Voucher / Reservation Control No.,
     * the booking name and PAX directly under it, the hotel, the stay, a reservation SUMMARY (no guest list: the full names and
     * the room allocations are only in the Rooming List PDF), and the hotel's confirmation. Fixed palette; everything from the booking.
     */
    private static function drawRequest(HotelDocPdf $pdf, array $doc, array $rl): void
    {
        $T = [0, 77, 80]; $BAR = [0, 102, 102]; $G = [120, 184, 72]; $ink = [32, 43, 48]; $line = [184, 200, 200];
        $h = $doc['hotel']; $s = $doc['stay']; $t = $doc['tour']; $no = self::voucherNo($doc);
        // tf-hotelreq-r9: the Hotel Voucher (after the hotel has confirmed) is the same page with its own number, words and the hotel's confirmation filled in
        $isV = ($doc['_mode'] ?? 'request') === 'voucher';
        if ($isV && trim((string) ($s['voucher_no'] ?? '')) !== '') { $no = trim((string) $s['voucher_no']); }
        $L = 9; $W = $pdf->getPageWidth() - 18; $f = 'dejavusans'; $bottom = $pdf->getPageHeight() - 12;
        $pdf->SetAutoPageBreak(false);
        $dm = static fn ($v) => $v ? date('j M Y', strtotime((string) $v)) : '—';
        $need = static function (float $hh) use ($pdf, &$y, $bottom) { if ($y + $hh > $bottom) { $pdf->AddPage(); $y = 22; } };
        $bed = static function (float $x, float $yy, float $k = 1) use ($pdf) {   // a small bed, in the current fill colour
            $pdf->Rect($x, $yy, 1.1 * $k, 6.4 * $k, 'F'); $pdf->Rect($x, $yy + 3.8 * $k, 8.6 * $k, 1.3 * $k, 'F'); $pdf->Rect($x + 7.5 * $k, $yy + 2.6 * $k, 1.1 * $k, 3.8 * $k, 'F');
            $pdf->Rect($x + 1.7 * $k, $yy + 1.9 * $k, 2.1 * $k, 1.5 * $k, 'F'); $pdf->Rect($x + 4.3 * $k, $yy + 2.3 * $k, 3.2 * $k, 1.2 * $k, 'F');
        };
        $lead = '';
        foreach ((array) ($doc['passengers'] ?? []) as $p) {
            if (trim((string) ($p['full_name'] ?? '')) !== '') { $lead = trim((string) $p['full_name']); break; }
        }
        $ad = (int) ($t['pax_adults'] ?? 0); $ch = (int) ($t['pax_children'] ?? 0);
        $pax = ($ad + $ch) ?: (int) ($s['pax'] ?? $t['pax_total'] ?? $rl['total_guests']);
        $name = $lead !== '' ? $lead : (trim((string) ($t['tour_name'] ?? '')) ?: 'Name to follow');
        // the number: a teal tab, then the number in its own box
        $y = 42;
        $pdf->SetFillColor(...$BAR); $pdf->Rect($L + 19, $y, $W - 38, 9.2, 'F');
        $pdf->SetTextColor(255, 255, 255); $pdf->SetFont($f, 'B', 12); $pdf->SetFontSpacing(0.4); $pdf->SetXY($L + 19, $y); $pdf->Cell($W - 38, 9.2, $isV ? 'HOTEL VOUCHER NO.' : 'VOUCHER / RESERVATION CONTROL NO.', 0, 0, 'C'); $pdf->SetFontSpacing(0);
        $y += 9.2;
        $pdf->SetDrawColor(...$BAR); $pdf->SetLineWidth(0.6); $pdf->Rect($L, $y, $W, 16.6, 'D');
        $pdf->SetTextColor(...$T); $pdf->SetFont($f, 'B', 27); $nw = $pdf->GetStringWidth($no); $pdf->SetXY($L, $y + 1.8); $pdf->Cell($W, 13, $no, 0, 0, 'C');
        $pdf->SetDrawColor(...$G); $pdf->SetLineWidth(1); $mid = $L + $W / 2;
        $pdf->Line($mid - $nw / 2 - 23, $y + 8.4, $mid - $nw / 2 - 7, $y + 8.4); $pdf->Line($mid + $nw / 2 + 7, $y + 8.4, $mid + $nw / 2 + 23, $y + 8.4);
        // the booking name and PAX, directly under the number
        $y += 21;
        $pdf->SetFillColor(232, 243, 222); $pdf->RoundedRect($L - 0.5, $y, $W + 1, 20.4, 2.5, '1111', 'F');
        $big = mb_strtoupper($name) . ' — ' . $pax . ' PAX'; $fs = 27;
        $pdf->SetFont($f, 'B', $fs);
        while ($fs > 13 && $pdf->GetStringWidth($big) > $W - 10) { $fs -= 1; $pdf->SetFont($f, 'B', $fs); }
        $pdf->SetTextColor(...$T); $pdf->SetXY($L, $y + 1.2); $pdf->Cell($W, 12, $big, 0, 0, 'C');
        $pdf->SetTextColor(...$ink); $pdf->SetFont($f, '', 12.5); $pdf->SetXY($L, $y + 12.8);
        $pdf->Cell($W, 6, ($ad + $ch) ? $ad . ' adult' . ($ad === 1 ? '' : 's') . ' • ' . $ch . ' child' . ($ch === 1 ? '' : 'ren') : $pax . ' guest' . ($pax === 1 ? '' : 's'), 0, 0, 'C');
        $y += 23.2;
        $ref = 'Tour ' . $t['tour_no'] . (!empty($t['inquiry_no']) ? '  |  Inquiry ' . $t['inquiry_no'] : '');
        $pdf->SetFont($f, '', 10); $rw = $pdf->GetStringWidth($ref) + 16; $pdf->SetXY($L, $y); $pdf->Cell($W, 5.4, $ref, 0, 0, 'C');
        $pdf->SetDrawColor(...$line); $pdf->SetLineWidth(0.3); $pdf->Line($L, $y + 2.9, $mid - $rw / 2, $y + 2.9); $pdf->Line($mid + $rw / 2, $y + 2.9, $L + $W, $y + 2.9);
        // the letter
        $y += 8.5; $pdf->SetFont($f, 'B', 11.5); $pdf->SetXY($L + 0.5, $y); $pdf->Cell($W, 6, 'Dear Reservations Manager,', 0, 0, 'L');
        $pdf->SetFont($f, '', 9.6);
        $pdf->writeHTMLCell($W, 0, $L + 0.5, $y + 6.8, 'Greetings from <b style="color:#004D50">Avenir Holidays.</b> ' . ($isV
            ? 'Please accept this voucher for the accommodation detailed below, confirmed by your reservations department under the reference shown. Kindly settle the account with our office as agreed; the guests are not to be charged for the services listed.'
            : 'Kindly reserve the accommodation detailed below. Please reply with your hotel confirmation reference, quoting our voucher number.'), 0, 1);
        // the hotel
        $y = $pdf->GetY() + 3.5; $hh = 13.2;
        $pdf->SetDrawColor(...$BAR); $pdf->SetLineWidth(0.35); $pdf->Rect($L, $y, $W, $hh, 'D');
        $pdf->SetFillColor(...$BAR); $pdf->Polygon([$L, $y, $L + 28, $y, $L + 21, $y + $hh, $L, $y + $hh], 'F');
        $pdf->SetFillColor(255, 255, 255); $pdf->Rect($L + 7.2, $y + 3.4, 4.4, 7, 'F'); $pdf->Rect($L + 11.6, $y + 5.6, 3.6, 4.8, 'F'); $pdf->Rect($L + 6.2, $y + 10.2, 10, 0.7, 'F');
        $pdf->SetFillColor(...$BAR);
        foreach ([[8, 4.4], [9.9, 4.4], [8, 6.2], [9.9, 6.2], [8, 8], [9.9, 8], [12.4, 6.6], [13.7, 6.6], [12.4, 8.3], [13.7, 8.3]] as [$dx, $dy]) { $pdf->Rect($L + $dx, $y + $dy, 0.9, 0.9, 'F'); }
        $where = trim((string) ($s['area'] ?? '')) ?: trim((string) ($h['address'] ?? ''));
        $pdf->SetTextColor(...$ink); $fs = 15.5; $pdf->SetFont($f, 'B', $fs); $nm = (string) $h['name']; $rest = $where !== '' ? ' / ' . $where : '';
        while ($fs > 9 && $pdf->GetStringWidth($nm . $rest) > $W - 36) { $fs -= 0.5; $pdf->SetFont($f, 'B', $fs); }
        $pdf->SetXY($L + 32, $y); $pdf->Cell($pdf->GetStringWidth($nm) + 0.5, $hh, $nm, 0, 0, 'L'); $pdf->SetFont($f, '', $fs); $pdf->Cell(60, $hh, $rest, 0, 0, 'L');
        // the stay in three boxes
        $y += $hh + 4.4; $bw = ($W - 9) / 3; $n = (int) $s['nights'];
        foreach ([['Check-in', $dm($s['check_in'])], ['Check-out', $dm($s['check_out'])], ['Duration', $n . ' night' . ($n === 1 ? '' : 's')]] as $i => [$k, $v]) {
            $x = $L - 0.5 + $i * ($bw + 5);
            $pdf->SetDrawColor(...$line); $pdf->SetLineWidth(0.4); $pdf->RoundedRect($x, $y, $bw, 16.2, 2, '1111', 'D');
            $pdf->SetFillColor(...$G);
            if ($i < 2) {
                $pdf->RoundedRect($x + 6.5, $y + 4.6, 8, 7.6, 1, '1111', 'F'); $pdf->Rect($x + 8.2, $y + 3.6, 1, 2, 'F'); $pdf->Rect($x + 11.8, $y + 3.6, 1, 2, 'F');
                $pdf->SetFillColor(255, 255, 255); $pdf->Rect($x + 7.4, $y + 7, 6.2, 4.3, 'F');
                $pdf->SetFillColor(...$G);
                foreach ([[8.2, 7.8], [10, 7.8], [11.8, 7.8], [8.2, 9.5], [10, 9.5], [11.8, 9.5]] as [$dx, $dy]) { $pdf->Rect($x + $dx, $y + $dy, 1, 1, 'F'); }
            } else {
                $bed($x + 6.2, $y + 4.6, 1.05);
            }
            $pdf->SetDrawColor(...$G); $pdf->SetLineWidth(0.4); $pdf->Line($x + 18.6, $y + 3.2, $x + 18.6, $y + 13);
            $pdf->SetTextColor(...$ink); $pdf->SetFont($f, 'B', 9.8); $pdf->SetXY($x + 21.5, $y + 1.9); $pdf->Cell($bw - 22, 5, $k, 0, 0, 'L');
            $pdf->SetFont($f, 'B', 14.5); $pdf->SetXY($x + 21.5, $y + 7); $pdf->Cell($bw - 22, 7.4, $v, 0, 0, 'L');
        }
        // reservation summary — numbers and kinds only; the names are in the Rooming List
        $y += 20;
        $cats = []; $beds = [];
        foreach ((array) $rl['rooms'] as $r) {
            $c = trim((string) (array_key_exists('category', $r) ? $r['category'] : ($rl['room_type_text'] ?? '')));
            if ($c !== '') { $cats[$c] = ($cats[$c] ?? 0) + 1; }
            $w = RoomingList::WORD[$r['code']] ?? (string) $r['code']; $beds[$w] = ($beds[$w] ?? 0) + 1;
        }
        $mix = static fn (array $m) => !$m ? '—' : (count($m) === 1 ? (string) array_key_first($m) . (reset($m) > 1 ? ' (' . reset($m) . ' rooms)' : '')
            : implode(' • ', array_map(static fn ($k, $v) => $v . ' ' . $k, array_keys($m), $m)));
        $nr = (int) $rl['total_rooms'];
        $rows = [['Rooms', $nr > 0 ? $nr . ' room' . ($nr === 1 ? '' : 's') : '—'], ['Room category', $mix($cats)], ['Bed arrangement', $mix($beds)],
                 ['Meal plan', count($rl['meals']) > 1 ? 'Differs by room — see the Rooming List' : self::mealWord($s['meal_plan'])],
                 ['Special requests', trim((string) ($s['special_note'] ?? '')) !== '' ? (string) $s['special_note'] : 'None specified']];
        if (!empty($s['crew_rooms'])) {
            $rows[] = ['Crew rooms', (string) $s['crew_rooms']];
        }
        if (trim((string) ($doc['rate_basis'] ?? '')) !== '') {   // the basis only, never a figure
            $rows[] = ['Rate', (string) $doc['rate_basis']];
        }
        $pdf->SetFillColor(...$BAR); $pdf->Rect($L - 0.5, $y, $W + 1, 7.4, 'F');
        $pdf->SetTextColor(255, 255, 255); $pdf->SetFont($f, 'B', 11.5); $pdf->SetFontSpacing(0.25); $pdf->SetXY($L + 3.5, $y); $pdf->Cell($W - 4, 7.4, 'RESERVATION SUMMARY', 0, 0, 'L'); $pdf->SetFontSpacing(0);
        $y += 7.4; $kw = 62; $pdf->SetLineWidth(0.25);
        foreach ($rows as [$k, $v]) {
            $pdf->SetFont($f, '', 10.5);
            $rh = max(7.1, $pdf->getStringHeight($W - $kw - 10, $v) + 2);
            $need($rh);
            $pdf->SetDrawColor(...$line); $pdf->SetFillColor(237, 245, 244); $pdf->Rect($L - 0.5, $y, $kw, $rh, 'DF'); $pdf->Rect($L - 0.5 + $kw, $y, $W + 1 - $kw, $rh, 'D');
            $pdf->SetTextColor(...$ink); $pdf->SetFont($f, 'B', 10.5); $pdf->SetXY($L + 3.8, $y + 1); $pdf->Cell($kw - 5, 5.1, $k, 0, 0, 'L');
            $pdf->SetFont($f, '', 10.5); $pdf->SetXY($L + $kw + 5, $y + 1); $pdf->MultiCell($W - $kw - 10, 5.1, $v, 0, 'L');
            $y += $rh;
        }
        // the rooming list — named only when it is attached
        if (!$isV) {   // tf-hotelreq-r10: the Rooming List always goes with the Booking Request; the later Hotel Voucher goes alone
            $y += 3.2; $need(14.2);
            $pdf->SetFillColor(217, 238, 238); $pdf->RoundedRect($L - 0.5, $y, $W + 1, 14.2, 2, '1111', 'F');
            $pdf->SetDrawColor(...$G); $pdf->SetLineWidth(0.7); $pdf->RoundedRect($L + 5.2, $y + 3.2, 3.6, 8, 1.8, '1111', 'D'); $pdf->RoundedRect($L + 6.4, $y + 5, 1.3, 4.6, 0.65, '1111', 'D');
            $pdf->SetLineWidth(0.4); $pdf->Line($L + 14, $y + 2.4, $L + 14, $y + 11.8);
            $pdf->SetTextColor(...$T); $pdf->SetFont($f, 'B', 12.5); $pdf->SetXY($L + 18.5, $y + 1.6); $a = 'Separate attachment: '; $pdf->Cell($pdf->GetStringWidth($a), 6, $a, 0, 0, 'L');
            $pdf->SetTextColor(...$ink); $pdf->Cell(60, 6, 'Rooming List PDF', 0, 0, 'L');
            $pdf->SetFont($f, '', 9.6); $pdf->SetXY($L + 18.5, $y + 7.6); $pdf->Cell($W - 20, 5, 'Full guest names and room allocations are provided in the rooming list.', 0, 0, 'L');
            $y += 14.2;
        }
        // the hotel's confirmation — its own block
        $y += 3.5; $need(33 + 20.5);
        $pdf->SetFillColor(232, 243, 222); $pdf->RoundedRect($L - 0.5, $y, $W + 1, 33, 2, '1111', 'F');
        $pdf->SetTextColor(...$T); $pdf->SetFont($f, 'B', 14); $ttl = 'HOTEL CONFIRMATION'; $tw = $pdf->GetStringWidth($ttl); $pdf->SetXY($L + 3, $y + 1.3); $pdf->Cell($tw + 2, 7, $ttl, 0, 0, 'L');
        $pdf->SetDrawColor(...$G); $pdf->SetLineWidth(0.45); $pdf->Line($L + $tw + 9, $y + 5, $L + $W - 3, $y + 5);
        $pdf->SetDrawColor(...$line); $pdf->SetLineWidth(0.25); $pdf->SetFillColor(255, 255, 255); $pdf->SetTextColor(...$ink); $pdf->SetFont($f, 'B', 10.5);
        $cf = [trim((string) ($s['confirmation_ref'] ?? '')), trim((string) ($s['confirmed_with'] ?? '')), !empty($s['confirmed_at']) ? date('j M Y', strtotime((string) $s['confirmed_at'])) : ''];
        foreach (['Hotel confirmation reference', 'Confirmed by', 'Date'] as $i => $k) {
            $ly = $y + 9.6 + $i * 6.7; $pdf->Rect($L + 3, $ly, 68, 6.7, 'DF'); $pdf->Rect($L + 71, $ly, $W - 74, 6.7, 'DF');
            $pdf->SetFont($f, 'B', 10.5); $pdf->SetXY($L + 6, $ly); $pdf->Cell(64, 6.7, $k, 0, 0, 'L');
            if ($isV && $cf[$i] !== '') {   // on the voucher the hotel's own confirmation is printed, not left to fill
                $pdf->SetFont($f, '', 10.5); $pdf->SetXY($L + 77, $ly); $pdf->Cell($W - 86, 6.7, $cf[$i], 0, 0, 'L');
            } else {
                $pdf->SetDrawColor(...$ink); $pdf->Line($L + 77, $ly + 5.2, $L + $W - 8, $ly + 5.2); $pdf->SetDrawColor(...$line);
            }
        }
        $y += 37.5;
        $pdf->SetFont($f, '', 10.5); $pdf->SetXY($L + 1, $y); $pdf->Cell($W, 5.2, 'Kind regards,', 0, 0, 'L');
        $pdf->SetFont($f, 'B', 10.5); $pdf->SetXY($L + 1, $y + 5.3); $pdf->Cell($W, 5.2, 'Reservations Team,', 0, 0, 'L');
        $pdf->SetFont($f, '', 10.5); $pdf->SetXY($L + 1, $y + 10.6); $pdf->Cell($W, 5.2, 'Avenir Holidays (Pvt) Ltd.', 0, 0, 'L');
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
        $info = static fn ($k, $v) => '<tr><td width="34%" style="font-weight:bold;font-size:11.5pt;color:#202B30">' . $k . '</td><td width="66%" style="font-size:11.5pt;color:#202B30;border-left:1px solid #B8C8C8">' . $v . '</td></tr>';
        $html = '<table cellpadding="0" cellspacing="0" width="100%"><tr><td width="18%"></td><td width="64%" style="border:1.4px solid #6B7278;text-align:center;font-size:15pt;font-weight:bold;line-height:2;color:#202B30">Reservation Control No:  <span style="color:#6B7278">|</span>  ' . $e($no) . '</td><td width="18%"></td></tr></table><br><br>'
            . '<table cellpadding="0" cellspacing="0" width="100%"><tr><td width="55%"><table cellpadding="3">'
            . $info('Hotel:', $e($hotel))
            . $info('Stay:', $e(date('j M Y', strtotime((string) $s['check_in'])) . ' – ' . date('j M Y', strtotime((string) $s['check_out'])) . ' (' . (int) $s['nights'] . ' night' . ((int) $s['nights'] === 1 ? '' : 's') . ')'))
            . $info('Total guests:', $e(self::guestWords($doc, $rl)))
            . '</table></td><td width="45%" style="border-left:1px solid #B8C8C8"><table cellpadding="3">'
            . $info('Total rooms:', $e($rl['summary'] . ' (' . $rl['total_rooms'] . ')'))
            . $info('Meal plan:', $e($perMeal ? 'Per room — see the list' : self::mealWord($s['meal_plan'])))
            . $info('Tour:', $e($doc['tour']['tour_no']))
            . '</table></td></tr></table><br><br>';
        $gw = $perMeal ? 50 : 60;
        $gcol = max(1, $maxG);
        $wEach = round($gw / $gcol, 2);
        $th = static fn ($t, $w, $sub = '') => '<th width="' . $w . '%" style="background-color:#6B7278;color:#FFFFFF;font-weight:bold;font-size:11.5pt;text-align:center;border:0.5px solid #6B7278">' . $t . ($sub !== '' ? '<br><span style="font-size:8.5pt;font-weight:normal">' . $sub . '</span>' : '') . '</th>';
        $html .= '<table cellpadding="6" cellspacing="0" width="100%" style="border:0.5px solid #B8C8C8"><thead><tr>'
            . $th('Allocation', 11) . $th('Hotel room no.', 15, '(Hotel to fill)') . $th('Room type', 14);
        for ($g = 1; $g <= $gcol; $g++) {
            $html .= $th('Guest ' . $g, $wEach);
        }
        if ($perMeal) {
            $html .= $th('Meal plan', 10);
        }
        $html .= '</tr></thead><tbody>';
        $td = static fn ($v, $w, $st = '') => '<td width="' . $w . '%" style="font-size:12pt;color:#202B30;border:0.5px solid #B8C8C8;' . $st . '">' . $v . '</td>';
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
        $html .= '<br><br><table cellpadding="8" cellspacing="0" width="100%"><tr><td style="text-align:center;font-size:12pt;font-weight:bold;font-style:italic;color:#202B30">Hotel room numbers to be completed by the hotel.</td></tr></table>';
        return $html;
    }

    /** One of the two documents as a PDF (bytes). */
    public static function documentPdf(string $kind, array $doc, array $rl): string
    {
        $pdf = new HotelDocPdf($kind === 'rooming' ? 'L' : 'P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->kind = $kind;
        $pdf->control = self::voucherNo($doc);
        if ($kind === 'voucher') {
            $doc['_mode'] = 'voucher';
        }
        $pdf->docTitle = $kind === 'rooming' ? 'HOTEL ROOMING LIST' : ($kind === 'voucher' ? "HOTEL\nVOUCHER" : 'HOTEL BOOKING REQUEST');
        $pdf->chip = $kind === 'rooming' ? '' : ($kind === 'voucher' ? 'Confirmed by the hotel' : 'Awaiting hotel confirmation');
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
        if ($kind !== 'rooming') {
            self::drawRequest($pdf, $doc, $rl);   // tf-hotelreq-r3: white, teal and green — the approved mock-up
            return $pdf->Output('doc.pdf', 'S');
        }
        $pdf->SetY(51);
        $pdf->writeHTML(self::roomingHtml($doc, $rl), true, false, true, false, '');
        return $pdf->Output('doc.pdf', 'S');
    }

    /** The rooming list of the stay, from its record (an override for a preview only). */
    public static function rooming(array $doc): array
    {
        // tf-paxrooms-r1: the rooms and sharing staff entered on the Passengers tab for this stay, where there are any
        if (!empty($doc['tour']['intake_id']) && !empty($doc['stay']['id'])) {
            $mine = PaxRooms::roomingForJob(\Config\Database::connect(), (int) $doc['tour']['intake_id'], (int) $doc['stay']['id'], (string) ($doc['stay']['meal_plan'] ?? ''));
            if ($mine !== null) {
                return $mine;
            }
        }
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
            $sha = hash('sha256', ($k === 'request' ? 'r8|' : 'r6g|') . $html);   // r4: the teal request; the rooming list is unchanged
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

    /**
     * tf-hotelreq-r9: one of the hotel documents as a PDF file to open or attach, made now from the record and not kept as a version:
     * 'request' | 'voucher' | 'rooming'. Returns ['path','name'] under writable/tf_mail (the temporary letters).
     */
    public static function letterFile(string $kind, array $doc): array
    {
        $rl  = self::rooming($doc);
        $no  = $kind === 'voucher' && trim((string) ($doc['stay']['voucher_no'] ?? '')) !== '' ? trim((string) $doc['stay']['voucher_no']) : self::voucherNo($doc);
        $nm  = ['request' => 'Hotel-Booking-Request-', 'voucher' => 'Hotel-Voucher-', 'rooming' => 'Hotel-Rooming-List-'][$kind] ?? 'Hotel-Document-';
        $name = $nm . preg_replace('/[^A-Za-z0-9\-]+/', '-', $no . '-' . ($doc['hotel']['name'] ?? 'hotel')) . '.pdf';
        $dir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'tf_mail';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $path = $dir . DIRECTORY_SEPARATOR . 'h' . bin2hex(random_bytes(5)) . '-' . $name;
        file_put_contents($path, self::documentPdf($kind, $doc, $rl));
        return ['path' => $path, 'name' => $name, 'rooms' => (int) $rl['total_rooms']];
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
