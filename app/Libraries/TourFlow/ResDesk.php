<?php

namespace App\Libraries\TourFlow;

/**
 * tf-resdesk-r2 (12 Nov 2026) — what the Hotel Reservation Desk shows on top of the Hotel Bookings records.
 *
 * Read-only views over the existing tables (tf_tour, tf_hotel_job, tf_hotel_message, tf_hotel_doc) and, for the payment
 * columns, over the payment chain's own records (HotelPayLink). Nothing here changes a booking: opening a tour only records
 * that the desk has seen it (markSeen), which takes the "New booking" label off — it never confirms a hotel.
 *
 * Stage words on a stay (one, the furthest reached):
 *   not_sent      — the booking voucher has not gone (never tried, or the last attempt failed: failed=true)
 *   awaiting      — "Voucher emailed — awaiting hotel confirmation"
 *   confirmed     — the hotel confirmed; the hotel payment request waits in Payment Approval
 *   approved      — payment approved; the payment voucher is with Bank Payment
 *   paid          — the payment is finalised (Paid, with the bank's paid date)
 *   problem / cancelled — as the desk recorded
 */
final class ResDesk
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function markSeen(int $tourId, string $user): bool
    {
        HotelBookingDocs::ensure($this->db);
        $this->db->query('UPDATE tf_tour SET hotel_seen_at = ?, hotel_seen_by = ? WHERE id = ? AND hotel_seen_at IS NULL', [date('Y-m-d H:i:s'), $user, $tourId]);
        return $this->db->affectedRows() === 1;
    }

    /** The newest booking-request message of each stay (the booking voucher e-mail), keyed by job id. */
    private function lastMails(array $jobIds): array
    {
        if (!$jobIds) {
            return [];
        }
        $in = implode(',', array_map('intval', $jobIds));
        $rows = $this->db->query("SELECT m.id, m.job_id, m.channel, m.to_addr, m.cc_addr, m.subject, m.state, m.error, m.by_name, m.`at`, m.doc_id
            FROM tf_hotel_message m JOIN (SELECT job_id, MAX(id) mid FROM tf_hotel_message WHERE kind = 'request' AND job_id IN ({$in}) GROUP BY job_id) x ON x.mid = m.id")->getResultArray();
        $ok = $this->db->query("SELECT job_id, MAX(id) mid FROM tf_hotel_message WHERE kind = 'request' AND state IN ('sent','recorded') AND job_id IN ({$in}) GROUP BY job_id")->getResultArray();
        $okBy = [];
        foreach ($ok as $o) {
            $okBy[(int) $o['job_id']] = (int) $o['mid'];
        }
        $out = [];
        foreach ($rows as $r) {
            $r['last_ok_id'] = $okBy[(int) $r['job_id']] ?? null;
            $out[(int) $r['job_id']] = $r;
        }
        return $out;
    }

    private function docs(array $jobIds): array
    {
        if (!$jobIds) {
            return [];
        }
        $in = implode(',', array_map('intval', $jobIds));
        $out = [];
        foreach ($this->db->query("SELECT id, job_id, kind, version, message_id, original_name, mime, size_bytes, `by`, `at` FROM tf_hotel_doc WHERE job_id IN ({$in}) ORDER BY id DESC")->getResultArray() as $d) {
            $out[(int) $d['job_id']][] = $d;
        }
        return $out;
    }

    /** One stay's stage, from its record, its last mail and its payment. */
    public static function stage(array $j, ?array $mail, array $pay): array
    {
        $st = (string) $j['status'];
        if (!empty($j['archived_at'])) {
            return ['key' => 'archived', 'label' => 'Archived', 'tone' => 'grey'];
        }
        if ($st === 'cancelled') {
            return ['key' => 'cancelled', 'label' => 'Cancelled', 'tone' => 'grey'];
        }
        if ($st === 'problem') {
            return ['key' => 'problem', 'label' => 'Problem — see the stay', 'tone' => 'red'];
        }
        if (in_array($st, ['confirmed', 'voucher_issued'], true)) {
            if (($pay['stage'] ?? '') === 'paid') {
                return ['key' => 'paid', 'label' => 'Paid', 'tone' => 'green'];
            }
            if (($pay['stage'] ?? '') === 'approved') {
                return ['key' => 'approved', 'label' => 'Payment approved — with Bank Payment', 'tone' => 'teal'];
            }
            if (($pay['stage'] ?? '') === 'rejected') {
                return ['key' => 'pay_rejected', 'label' => 'Payment request rejected — see Payment Approval', 'tone' => 'red'];
            }
            if (($pay['stage'] ?? '') === 'requested') {
                return ['key' => 'confirmed', 'label' => 'Hotel confirmed — awaiting payment approval', 'tone' => 'purple'];
            }
            return ['key' => 'confirmed', 'label' => 'Hotel confirmed — payment request not raised', 'tone' => 'amber'];
        }
        if ($mail && in_array((string) $mail['state'], ['sent', 'recorded'], true)) {
            return ['key' => 'awaiting', 'label' => 'Voucher emailed — awaiting hotel confirmation', 'tone' => 'blue'];
        }
        if ($mail && $mail['last_ok_id']) {
            return ['key' => 'awaiting', 'label' => 'Voucher emailed — awaiting hotel confirmation (a later re-send failed)', 'tone' => 'blue'];
        }
        if ($st === 'requested') {
            return ['key' => 'awaiting', 'label' => 'Requested — awaiting hotel confirmation', 'tone' => 'blue'];
        }
        if ($mail && (string) $mail['state'] === 'failed') {
            return ['key' => 'failed', 'label' => 'Email FAILED — voucher not sent', 'tone' => 'red', 'failed' => true];
        }
        return ['key' => 'not_sent', 'label' => 'Not sent yet', 'tone' => 'red', 'failed' => false];
    }

    /** The stays of an opened tour, each with its mail, documents, stage and payment dates. */
    public function enrichStays(array $stays): array
    {
        HotelBookingDocs::ensure($this->db);
        $ids = array_map(static fn ($j) => (int) $j['id'], $stays);
        $mails = $this->lastMails($ids);
        $docs = $this->docs($ids);
        $pays = (new HotelPayLink($this->db, ''))->forJobs($ids);
        // every letter and reply of each stay, newest first, with the exact files that went with each e-mail
        $msgs = [];
        if ($ids) {
            $in = implode(',', array_map('intval', $ids));
            foreach ($this->db->query("SELECT id, job_id, kind, channel, to_addr, cc_addr, subject, state, error, by_name, `by`, `at`, doc_id, doc2_id, CASE WHEN kind = 'reply' THEN LEFT(body, 600) ELSE NULL END AS reply_text FROM tf_hotel_message WHERE job_id IN ({$in}) ORDER BY id DESC")->getResultArray() as $m) {
                $msgs[(int) $m['job_id']][] = $m;
            }
        }
        foreach ($stays as &$j) {
            $id = (int) $j['id'];
            $j['rd_msgs'] = $msgs[$id] ?? [];
            $m = $mails[$id] ?? null;
            $pay = $pays[$id] ?? ['stage' => null];
            $j['rd_mail'] = $m;
            $j['rd_docs'] = $docs[$id] ?? [];
            $j['rd_pay'] = $pay;
            $j['rd_stage'] = self::stage($j, $m, $pay);
        }
        unset($j);
        return $stays;
    }

    /** The list's rows with the "New booking" flag and the hotel confirmation progress. */
    public function enrichTours(array $tours): array
    {
        if (!$tours) {
            return $tours;
        }
        HotelBookingDocs::ensure($this->db);
        $in = implode(',', array_map(static fn ($t) => (int) $t['id'], $tours));
        $seen = [];
        foreach ($this->db->query("SELECT id, hotel_seen_at, hotel_seen_by, pax_adults, pax_children FROM tf_tour WHERE id IN ({$in})")->getResultArray() as $r) {
            $seen[(int) $r['id']] = $r;
        }
        $jobs = $this->db->query("SELECT id, tour_id, status, archived_at FROM tf_hotel_job WHERE tour_id IN ({$in}) AND archived_at IS NULL")->getResultArray();
        $mails = $this->lastMails(array_map(static fn ($j) => (int) $j['id'], $jobs));
        $pays = (new HotelPayLink($this->db, ''))->forJobs(array_map(static fn ($j) => (int) $j['id'], $jobs));
        $agg = [];
        foreach ($jobs as $j) {
            $t = (int) $j['tour_id'];
            $agg[$t] = $agg[$t] ?? ['total' => 0, 'confirmed' => 0, 'awaiting' => 0, 'failed' => 0, 'not_sent' => 0, 'paid' => 0, 'approved' => 0, 'requested' => 0, 'problem' => 0];
            if ((string) $j['status'] === 'cancelled') {
                continue;
            }
            $agg[$t]['total']++;
            $s = self::stage($j, $mails[(int) $j['id']] ?? null, $pays[(int) $j['id']] ?? []);
            switch ($s['key']) {
                case 'paid': $agg[$t]['confirmed']++; $agg[$t]['paid']++; break;
                case 'approved': $agg[$t]['confirmed']++; $agg[$t]['approved']++; break;
                case 'confirmed': case 'pay_rejected':
                    $agg[$t]['confirmed']++;
                    if ((($pays[(int) $j['id']] ?? [])['stage'] ?? '') === 'requested') {
                        $agg[$t]['requested']++;
                    } else {
                        $agg[$t]['unraised'] = ($agg[$t]['unraised'] ?? 0) + 1;
                    }
                    break;
                case 'awaiting': $agg[$t]['awaiting']++; break;
                case 'failed': $agg[$t]['failed']++; break;
                case 'not_sent': $agg[$t]['not_sent']++; break;
                case 'problem': $agg[$t]['problem']++; break;
            }
        }
        foreach ($tours as &$t) {
            $id = (int) $t['id'];
            $t['is_new'] = empty($seen[$id]['hotel_seen_at']);
            $t['seen_at'] = $seen[$id]['hotel_seen_at'] ?? null;
            $t['seen_by'] = $seen[$id]['hotel_seen_by'] ?? null;
            $t['pax_adults'] = $seen[$id]['pax_adults'] ?? null;
            $t['pax_children'] = $seen[$id]['pax_children'] ?? null;
            $t['rd'] = $agg[$id] ?? ['total' => 0, 'confirmed' => 0, 'awaiting' => 0, 'failed' => 0, 'not_sent' => 0, 'paid' => 0, 'approved' => 0, 'requested' => 0, 'problem' => 0];
        }
        unset($t);
        return $tours;
    }
    // =====================================================================
    //  tf-resdesk-r2 (13 Nov 2026, Shantha): Pending Reservations / Confirmed Hotel Reservations / Other Reservations
    // =====================================================================

    /** "NITOBE/NAOKO MS" → "Naoko Nitobe"; any other written form is kept, only its capitals softened. */
    public static function clientName(?string $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }
        $raw = trim(preg_replace('/\s*\([^)]*\)/', '', $raw));
        if (str_contains($raw, '/')) {
            [$sur, $given] = array_map('trim', explode('/', $raw, 2));
            $given = trim(preg_replace('/\b(MR|MRS|MS|MISS|MSTR|DR|PROF)\b\.?/i', '', $given));
            $raw = trim(preg_replace('/\s+/', ' ', $given . ' ' . $sur));
        }
        return mb_convert_case(mb_strtolower($raw), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * One row per confirmed tour with its traffic light, for the three lists. Every saved (live) tour is read — no limit —
     * and the search runs over all of them:
     *   hotel light   red    — a hotel whose booking request has not gone successfully (never sent, or the last try failed)
     *                 yellow — every request sent; a hotel confirmation still outstanding
     *                 green  — every hotel confirmed with its confirmation reference recorded
     *   other light   (activities, tickets, restaurants — the desk's other work, tracked apart)
     *                 red    — a line not yet requested · yellow — requested, awaiting · green — every line done
     * Opening a tour never changes a light: it is worked out from the bookings each time.
     */
    public function lists(array $f): array
    {
        HotelBookingDocs::ensure($this->db);
        $list = in_array($f['list'] ?? '', ['pending', 'confirmed', 'other'], true) ? $f['list'] : 'pending';
        $q = trim((string) ($f['q'] ?? ''));
        $arr = trim((string) ($f['arrival'] ?? ''));
        $from = trim((string) ($f['from'] ?? '')) ?: $arr;
        $to = trim((string) ($f['to'] ?? '')) ?: $arr;
        $per = in_array((int) ($f['per'] ?? 10), [10, 25, 50], true) ? (int) $f['per'] : 10;
        $page = max(1, (int) ($f['page'] ?? 1));
        $arch = $this->db->query("SHOW COLUMNS FROM tf_tour LIKE 'archived_at'")->getResultArray() ? ' AND t.archived_at IS NULL' : '';
        $tours = $this->db->query("SELECT t.id, t.tour_no, t.intake_id, t.agent_name, t.tour_name, t.arrival_date, t.departure_date, t.pax_total, t.stage, t.hotel_seen_at, i.inquiry_no
            FROM tf_tour t LEFT JOIN tf_intake i ON i.id = t.intake_id WHERE t.stage <> 'cancelled'{$arch} ORDER BY t.arrival_date IS NULL, t.arrival_date, t.id")->getResultArray();
        if (!$tours) {
            return ['rows' => [], 'total' => 0, 'page' => 1, 'per' => $per, 'pages' => 1, 'counts' => ['pending' => 0, 'confirmed' => 0, 'other' => 0], 'list' => $list];
        }
        $ids = implode(',', array_map(static fn ($t) => (int) $t['id'], $tours));
        $intakes = implode(',', array_map(static fn ($t) => (int) $t['intake_id'], $tours)) ?: '0';
        $jobs = [];
        foreach ($this->db->query("SELECT id, tour_id, status, confirmation_ref FROM tf_hotel_job WHERE tour_id IN ({$ids}) AND archived_at IS NULL AND status <> 'cancelled'")->getResultArray() as $j) {
            $jobs[(int) $j['tour_id']][] = $j;
        }
        $okMail = [];
        foreach ($this->db->query("SELECT DISTINCT job_id FROM tf_hotel_message WHERE tour_id IN ({$ids}) AND kind = 'request' AND state IN ('sent','recorded')")->getResultArray() as $m) {
            $okMail[(int) $m['job_id']] = true;
        }
        $svc = [];
        try {
            foreach ($this->db->query("SELECT tour_id, status FROM tf_service_job WHERE tour_id IN ({$ids}) AND archived_at IS NULL AND status <> 'cancelled'")->getResultArray() as $x) {
                $svc[(int) $x['tour_id']][] = (string) $x['status'];
            }
        } catch (\Throwable $e) {
        }
        $pax = [];
        try {
            foreach ($this->db->query("SELECT intake_id, full_name FROM tf_intake_pax WHERE intake_id IN ({$intakes}) ORDER BY intake_id, sort_order, id")->getResultArray() as $p) {
                $pax[(int) $p['intake_id']][] = (string) $p['full_name'];
            }
        } catch (\Throwable $e) {
        }
        $rows = [];
        $counts = ['pending' => 0, 'confirmed' => 0, 'other' => 0];
        foreach ($tours as $t) {
            $tid = (int) $t['id'];
            $names = $pax[(int) $t['intake_id']] ?? [];
            $client = $names ? self::clientName($names[0]) : '';
            $hay = mb_strtolower(implode(' | ', array_merge([(string) $t['inquiry_no'], (string) $t['tour_no'], (string) $t['agent_name'], (string) $t['tour_name'], $client], $names, $t['arrival_date'] ? [(string) $t['arrival_date'], date('j M Y', strtotime((string) $t['arrival_date'])), date('d M Y', strtotime((string) $t['arrival_date'])), date('d/m/Y', strtotime((string) $t['arrival_date']))] : [])));
            if ($q !== '' && !str_contains($hay, mb_strtolower($q))) {
                continue;
            }
            if (($from !== '' && (string) $t['arrival_date'] < $from) || ($to !== '' && (string) $t['arrival_date'] > $to)) {
                continue;   // an arrival date, or a range: from and/or to
            }
            // hotels
            $hl = null;
            $hj = $jobs[$tid] ?? [];
            $conf = 0; $unsent = 0;
            foreach ($hj as $j) {
                $isConf = in_array((string) $j['status'], ['confirmed', 'voucher_issued'], true) && trim((string) $j['confirmation_ref']) !== '';
                if ($isConf) {
                    $conf++;
                } elseif (empty($okMail[(int) $j['id']]) && (string) $j['status'] !== 'requested') {
                    $unsent++;
                }
            }
            if ($hj) {
                $hl = $conf === count($hj) ? 'green' : ($unsent > 0 ? 'red' : 'yellow');
            }
            // other reservations
            $ol = null;
            $sj = $svc[$tid] ?? [];
            if ($sj) {
                $done = count(array_filter($sj, static fn ($s) => in_array($s, TourService::SERVICE_DONE, true)));
                $notReq = count(array_filter($sj, static fn ($s) => in_array($s, ['to_book', 'problem'], true)));
                $ol = $done === count($sj) ? 'green' : ($notReq > 0 ? 'red' : 'yellow');
            }
            $row = ['id' => $tid, 'tour_no' => $t['tour_no'], 'inquiry_no' => $t['inquiry_no'], 'agent_name' => $t['agent_name'], 'client_name' => $client !== '' ? $client : (string) $t['tour_name'],
                'group' => $t['tour_name'], 'arrival_date' => $t['arrival_date'], 'departure_date' => $t['departure_date'], 'pax_total' => $t['pax_total'],
                'is_new' => empty($t['hotel_seen_at']), 'hotels' => count($hj), 'hotels_confirmed' => $conf, 'hotels_unsent' => $unsent, 'hotel_light' => $hl,
                'other_light' => $ol, 'other_total' => count($sj), 'other_done' => $sj ? count(array_filter($sj, static fn ($s) => in_array($s, TourService::SERVICE_DONE, true))) : 0];
            if ($hl === 'red' || $hl === 'yellow') {
                $counts['pending']++;
                if ($list === 'pending') { $rows[] = $row; }
            } elseif ($hl === 'green') {
                $counts['confirmed']++;
                if ($list === 'confirmed') { $rows[] = $row; }
            }
            if ($ol !== null) {
                $counts['other']++;
                if ($list === 'other') { $rows[] = $row; }
            }
        }
        $total = count($rows);
        $pages = max(1, (int) ceil($total / $per));
        $page = min($page, $pages);
        return ['rows' => array_slice($rows, ($page - 1) * $per, $per), 'total' => $total, 'page' => $page, 'per' => $per, 'pages' => $pages, 'counts' => $counts, 'list' => $list];
    }
}
