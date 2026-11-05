<?php

namespace App\Libraries\TourFlow;

use App\Libraries\HotelRates\HotelRateService;

/**
 * tf-comms-r3 (27 Oct 2026, Shantha) — a quotation request that arrives by e-mail becomes an inquiry and, where
 * the system has everything it needs, a quotation DRAFT for staff review. Nothing here issues or sends anything.
 *
 * Steps, each written to Communications & History:
 *   1. the e-mail is read by the same reader the inquiry paste uses (EmailExtractor): dates, passengers, rooms,
 *      meal basis, itinerary, transport, guide / language, special requests — nothing invented, and whatever the
 *      reader could not settle is a flag, never a guess;
 *   2. the inquiry is created (the e-mail itself is its first line) and the message is filed on it;
 *   3. the usual costing runs with the published, approved Hotel Rates and the masters only (Go for Quotation);
 *      every missing rate or detail is a blocker exactly as on the screen;
 *   4. when a version can be produced it is saved as a DRAFT marked "Draft – Staff Review Required" with the
 *      flags beside it; it cannot be e-mailed until a person has reviewed it and pressed Issue.
 */
final class CommsQuoteDraft
{
    public const REVIEW_LABEL = 'Draft – Staff Review Required';

    private $db;
    private string $user;
    private ?int $userId;
    private ?string $name;

    public function __construct($db, string $user = 'inbox', ?int $userId = null, ?string $name = null)
    {
        $this->db = $db;
        $this->user = $user;
        $this->userId = $userId;
        $this->name = $name;
    }

    /** Does this e-mail read like a request for a quotation? ['score' => n, 'words' => [...]] (2 or more = likely). */
    public static function looksLikeRequest(string $subject, string $body): array
    {
        $hay = mb_strtolower($subject . "\n" . $body);
        $score = 0;
        $words = [];
        foreach (['quotation', 'quote', 'rates', 'price', 'pricing', 'itinerary', 'tour request', 'package', 'proposal', 'cost', 'inquiry', 'enquiry', '見積', 'ツアー'] as $w) {
            if (str_contains($hay, $w)) {
                $score++;
                $words[] = $w;
            }
        }
        if (preg_match('/\b(\d{1,2}\s*(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\s*(20\d{2})?|20\d{2}[\/\-.]\d{1,2}[\/\-.]\d{1,2}|\d{1,2}[\/\-.]\d{1,2}[\/\-.]20\d{2})\b/i', $hay)) {
            $score++;
            $words[] = 'a date';
        }
        if (preg_match('/\b(\d+)\s*(pax|adults?|persons?|people|guests?|travellers?|travelers?|名)/i', $hay)) {
            $score++;
            $words[] = 'a party size';
        }
        if (preg_match('/\b(nights?|泊)\b/i', $hay)) {
            $score++;
            $words[] = 'nights';
        }
        if (preg_match('/^\s*(re|fw|fwd)\s*:/i', $subject) && $score > 0) {
            $score--;   // a reply or a forward is less likely to be a fresh request
        }
        return ['score' => $score, 'words' => $words];
    }

    /** "1 DBL", "2 TWN + 1 SGL", "1 double room": the same mix on every line that states one, or null when unclear. */
    public static function roomMixFromText(string $text): ?array
    {
        $map = ['sgl' => 'sgl', 'single' => 'sgl', 'dbl' => 'dbl', 'double' => 'dbl', 'twn' => 'twn', 'twin' => 'twn', 'tpl' => 'tpl', 'triple' => 'tpl'];
        $lines = [];
        foreach (preg_split('/\n/', $text) ?: [] as $line) {
            if (!preg_match_all('/\b(\d{1,2})\s*(?:x\s*)?(sgl|single|dbl|double|twn|twin|tpl|triple)\b/i', $line, $mm, PREG_SET_ORDER)) {
                continue;
            }
            $mix = ['sgl' => 0, 'dbl' => 0, 'twn' => 0, 'tpl' => 0];
            foreach ($mm as $m) {
                $mix[$map[strtolower($m[2])]] += (int) $m[1];
            }
            $lines[] = $mix;
        }
        if (!$lines) {
            return null;
        }
        foreach ($lines as $l) {
            if ($l !== $lines[0]) {
                return null;   // the mix changes from night to night: a person settles it
            }
        }
        return array_sum($lines[0]) > 0 && array_sum($lines[0]) <= 50 ? $lines[0] : null;
    }

    private function take(): IntakeService
    {
        return (new IntakeService($this->db, $this->user, new HotelRateService($this->db, $this->user)))->withActor($this->userId, $this->name);
    }

    /**
     * From a stored incoming message (tf_mail_in, unmatched) to an inquiry and, where possible, a draft version.
     * $auto says whether the system did this by itself (the mailbox setting) or a person pressed the button.
     * @return array{intake_id:int, inquiry_no:string, quote:?array, flags:array, blockers:array, message:string}
     */
    public function fromMail(int $mailId, bool $auto): array
    {
        $m = $this->db->query('SELECT * FROM tf_mail_in WHERE id = ?', [$mailId])->getRowArray();
        if (!$m) {
            throw new \RuntimeException('Message not found.');
        }
        if (!empty($m['draft_intake_id'])) {
            throw new \RuntimeException('An inquiry was already made from this e-mail (' . ($this->db->query('SELECT inquiry_no FROM tf_intake WHERE id = ?', [(int) $m['draft_intake_id']])->getRowArray()['inquiry_no'] ?? '#' . $m['draft_intake_id']) . ').');
        }
        if ($m['status'] === 'matched') {
            throw new \RuntimeException('This message is filed on an existing record; make the quotation there.');
        }
        $text = trim((string) ($m['body_text'] ?? ''));
        if ($text === '') {
            throw new \RuntimeException('The message has no text to read.');
        }
        $like = self::looksLikeRequest((string) $m['subject'], $text);
        if ($auto && $like['score'] < 2) {
            throw new \RuntimeException('The message does not read like a quotation request (' . ($like['words'] ? 'only: ' . implode(', ', $like['words']) : 'no request words, dates or party size') . '); left in the queue.');
        }
        $take = $this->take();
        $who = $auto ? 'the system (automatic reading of a request e-mail)' : ($this->name ?: $this->user);
        $source = ['source_text' => $text, 'source_subject' => (string) $m['subject'], 'source_from' => (string) $m['from_addr'], 'source_received_at' => (string) $m['received_at']];
        $id = $take->create($source);
        $flags = [];
        $days = 0;
        $read = false;
        try {
            $ex = EmailExtractor::make($this->db)->extract($text, (string) $m['received_at']);
            $item = $take->applyExtraction($id, $ex);
            $days = count($item['days'] ?? []);
            $read = true;
            foreach ((array) ($ex['notes'] ?? []) as $n) {
                $flags[] = 'Reader: ' . $n;
            }
        } catch (\Throwable $e) {
            $plain = (string) $e->getMessage();
            try {
                $take->recordExtractionFailure($id, $plain);
            } catch (\Throwable $e2) {
            }
            $flags[] = 'The e-mail could not be read automatically: ' . $plain . ' Fill the inquiry in by hand.';
            $item = $take->get($id);
        }
        $inquiryNo = (string) ($item['inquiry_no'] ?? '');
        // the message is filed on the new inquiry: the inquiry's own first line (captured by create()) is the e-mail,
        // so the stored message points at that line rather than making a second one; attachments go onto it
        $comms = new Comms($this->db, $this->user, $this->userId, $this->name);
        $line = $this->db->query("SELECT id FROM tf_comm WHERE intake_id = ? AND ref_table = 'tf_intake' ORDER BY id LIMIT 1", [$id])->getRowArray();
        $commId = $line ? (int) $line['id'] : null;
        if ($commId === null) {
            $c = $comms->add($id, ['direction' => 'in', 'channel' => 'email', 'source' => 'system', 'ref_table' => 'tf_mail_in', 'ref_id' => $mailId, 'occurred_at' => $m['received_at'], 'from_addr' => $m['from_addr'], 'to_addr' => $m['to_addr'], 'cc_addr' => $m['cc_addr'], 'subject' => $m['subject'], 'body' => $text, 'message_id' => $m['message_id']]);
            $commId = (int) $c['id'];
        } else {
            $this->db->query('UPDATE tf_comm SET message_id = ?, from_addr = COALESCE(from_addr, ?), to_addr = ?, cc_addr = ? WHERE id = ?', [$m['message_id'], $m['from_addr'], $m['to_addr'], $m['cc_addr'], $commId]);
        }
        foreach ($this->db->query('SELECT * FROM tf_mail_in_attachment WHERE mail_id = ?', [$mailId])->getResultArray() as $a) {
            $path = CommsInbox::dir($mailId) . DIRECTORY_SEPARATOR . $a['file'];
            if (is_file($path)) {
                try {
                    $comms->attach($commId, ['tmp' => $path, 'name' => $a['original_name'], 'size' => (int) $a['bytes'], 'mime' => $a['mime']]);
                } catch (\Throwable $e) {
                }
            }
        }
        $this->db->query("UPDATE tf_mail_in SET status = 'matched', intake_id = ?, comm_id = ?, draft_intake_id = ?, match_reason = ?, matched_by = ?, matched_at = ? WHERE id = ?",
            [$id, $commId, $id, mb_substr(($auto ? 'auto: ' : 'staff: ') . 'new inquiry ' . $inquiryNo . ' made from this request e-mail', 0, 255), $this->user, date('Y-m-d H:i:s'), $mailId]);
        $act = new Activity($this->db, $this->user, $this->userId, $this->name);
        $act->record('inquiry', $id, $id, null, 'auto_inquiry', $inquiryNo . ' created by ' . $who . ' from the e-mail "' . mb_substr((string) $m['subject'], 0, 100) . '" (' . mb_substr((string) $m['from_addr'], 0, 80) . ')' . ($read ? ': ' . $days . ' night(s) read' : ': the e-mail could not be read; fields left blank'));

        // the room mix: taken from the e-mail only when it states it plainly and the same way throughout ("1 DBL", "2 TWN + 1 SGL");
        // anything less certain is left for a person, with a flag
        $it = $take->get($id);
        if ($read && in_array((string) (($it['rooms'] ?? [])['source'] ?? 'none'), ['none', 'nobody', 'groups_empty'], true)) {
            $mix = self::roomMixFromText($text);
            if ($mix !== null) {
                try {
                    $take->saveRooms($id, $mix);
                    $flags[] = 'Room mix taken from the e-mail as written (' . implode(', ', array_filter(array_map(static fn ($k, $v) => $v ? $v . ' ' . strtoupper($k) : '', array_keys($mix), $mix))) . ') — check it.';
                    $it = $take->get($id);
                } catch (\Throwable $e) {
                    $flags[] = 'Room mix in the e-mail could not be applied: ' . $e->getMessage();
                }
            }
        }
        // what the reader left open — the gaps a person must settle
        if (empty($it['arrival_date'])) {
            $flags[] = 'Arrival date not found.';
        }
        if (empty($it['departure_date'])) {
            $flags[] = 'Departure date not found.';
        }
        if ((int) ($it['pax_total'] ?? 0) <= 0) {
            $flags[] = 'Number of passengers not found.';
        }
        if (empty($it['days'])) {
            $flags[] = 'No itinerary nights could be read.';
        }
        foreach ((array) ($it['days'] ?? []) as $d) {
            $n = (int) ($d['day_no'] ?? 0);
            if (trim((string) ($d['hotel_name_raw'] ?? '')) === '') {
                $flags[] = 'Night ' . $n . ' (' . ($d['area'] ?: 'area unknown') . '): no hotel named.';
            }
            if (trim((string) ($d['meal_plan'] ?? '')) === '') {
                $flags[] = 'Night ' . $n . ': meal basis not stated.';
            }
        }
        if (in_array((string) (($it['rooms'] ?? [])['source'] ?? 'none'), ['none', 'nobody', 'groups_empty'], true)) {
            $flags[] = 'Room mix (singles / doubles / twins / triples) not stated.';
        }
        if (empty($it['vehicles'])) {
            $flags[] = 'Vehicle / transport not stated (the usual vehicle for the party size is used when costed).';
        }
        if (!preg_match('/\b(guide|chauffeur|driver)\b/i', $text)) {
            $flags[] = 'Guide / language need not mentioned in the e-mail.';
        }
        $flags = array_values(array_unique($flags));

        // the costing, from published and approved rates only; blockers are the screen's own
        $quote = null;
        $blockers = [];
        $warnings = [];
        try {
            $q = new QuoteService($this->db, $this->user, $take);
            $r = $q->goForQuotation($id);
            $blockers = array_map(static fn ($b) => is_array($b) ? (string) ($b['label'] ?? '') : (string) $b, (array) ($r['blockers'] ?? []));
            $warnings = array_map(static fn ($b) => is_array($b) ? (string) ($b['label'] ?? '') : (string) $b, (array) ($r['warnings'] ?? []));
            if (!empty($r['ok']) && !empty($r['quote'])) {
                $quote = $r['quote'];
                $note = mb_substr(implode("\n", array_merge($flags, array_map(static fn ($w) => 'Check: ' . $w, $warnings))), 0, 4000);
                $this->db->query("UPDATE tf_quote SET review_required = 1, auto_note = ?, note = ? WHERE id = ?", [$note !== '' ? $note : null, self::REVIEW_LABEL, (int) $quote['id']]);
                $quote['review_required'] = 1;
            }
        } catch (\Throwable $e) {
            $blockers[] = 'Costing stopped: ' . $e->getMessage();
        }

        // the result on the record, for whoever opens it
        $subject = $quote ? 'AUTO: Quotation draft v' . $quote['version'] . ' prepared — ' . self::REVIEW_LABEL : 'AUTO: Quotation NOT prepared — ' . count($blockers) . ' thing(s) to settle first';
        $bodyLines = [];
        $bodyLines[] = ($auto ? 'The system read the request e-mail' : ($this->name ?: $this->user) . ' asked the system to read the request e-mail') . ' and created ' . $inquiryNo . ($read ? ' with ' . $days . ' night(s).' : ' but could not read the itinerary.');
        if ($quote) {
            $bodyLines[] = 'A draft quotation (version ' . $quote['version'] . ', USD ' . $quote['sell_usd'] . ', ' . $quote['per_person_usd'] . ' per person) was costed from the published, approved rates only. It is a DRAFT: nothing has been issued or sent. A staff member must open the inquiry, check every line against the e-mail, correct what is wrong, and press Issue before it can be e-mailed.';
        } else {
            $bodyLines[] = 'No quotation could be costed yet. To settle first:';
            foreach ($blockers as $b) {
                $bodyLines[] = ' • ' . $b;
            }
        }
        if ($flags) {
            $bodyLines[] = 'Flags — missing or uncertain in the e-mail:';
            foreach ($flags as $f) {
                $bodyLines[] = ' • ' . $f;
            }
        }
        if ($warnings) {
            $bodyLines[] = 'Warnings from the costing:';
            foreach ($warnings as $w) {
                $bodyLines[] = ' • ' . $w;
            }
        }
        $comms->add($id, ['direction' => 'internal', 'channel' => 'note', 'source' => 'system', 'tag' => '', 'occurred_at' => date('Y-m-d H:i:s'), 'subject' => $subject, 'body' => implode("\n", $bodyLines),
            'links' => $quote ? [['kind' => 'quote', 'id' => (int) $quote['id'], 'label' => 'quotation version ' . $quote['version'] . ' (draft — review required)']] : [['kind' => 'inquiry', 'id' => $id, 'label' => 'the inquiry']]]);
        $act->record('inquiry', $id, $id, null, $quote ? 'auto_quote_draft' : 'auto_quote_blocked', $quote ? 'Quotation draft v' . $quote['version'] . ' prepared automatically — ' . self::REVIEW_LABEL . ' (' . count($flags) . ' flag(s))' : 'Quotation not prepared automatically — ' . count($blockers) . ' blocker(s), ' . count($flags) . ' flag(s)');
        return ['intake_id' => $id, 'inquiry_no' => $inquiryNo, 'quote' => $quote, 'flags' => $flags, 'blockers' => $blockers, 'warnings' => $warnings,
            'message' => $inquiryNo . ' created from the e-mail' . ($quote ? '; draft quotation v' . $quote['version'] . ' prepared for staff review (' . count($flags) . ' flag(s)).' : '; no quotation yet — ' . count($blockers) . ' blocker(s) and ' . count($flags) . ' flag(s) to settle on the inquiry.')];
    }
}
