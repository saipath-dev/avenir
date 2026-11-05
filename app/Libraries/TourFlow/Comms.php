<?php

namespace App\Libraries\TourFlow;

/**
 * Communications & History — tf-comms-r1 (27 Oct 2026, Shantha)
 *
 * One continuous record per inquiry (and so per tour: tf_tour.intake_id is unique): every e-mail in and out,
 * WhatsApp / SMS / other messages, telephone notes, meeting notes and internal handover notes, with attachments,
 * who recorded them and what they refer to; plus the follow-ups they leave open. The timeline() merges this with
 * what the system already keeps — the activity log, desk events, edit requests, quotation versions, quotation
 * e-mails and hotel letters — so nothing is written twice: those stay in their own tables and are only captured
 * here as lines that point back to them.
 *
 * Captured automatically (source = 'system'): the inquiry's own e-mail as pasted, quotation e-mails sent from the
 * system, hotel letters sent from the system and hotel replies typed on the desk, restaurant vouchers e-mailed
 * from the Meal Master, and (tf-comms-r2) every e-mail composed and sent from this page itself, with its
 * attachments and whether the mail server took it. Everything else — incoming
 * e-mails that arrive in a person's mailbox, WhatsApp, calls, meetings — is recorded by hand (source = 'manual').
 * Nothing recorded here changes the itinerary or approves a request: changes go through the Edit Request.
 */
final class Comms
{
    public const CHANNELS = ['email' => 'E-mail', 'whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'phone' => 'Telephone call', 'meeting' => 'Meeting', 'note' => 'Internal note', 'handover' => 'Handover note', 'system' => 'System'];
    public const TAGS = ['' => '—', 'agreement' => 'Agreed arrangement', 'approval' => 'Approval received', 'change' => 'Change requested', 'question' => 'Question / pending', 'confirmation' => 'Confirmation'];
    public const MAX_BYTES = 15728640;   // 15 MB per attachment
    public const ALLOWED_EXT = ['pdf', 'eml', 'msg', 'txt', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'html', 'htm', 'zip'];

    private $db;
    private string $user;
    private ?int $userId;
    private ?string $name;

    public function __construct($db, string $user = '', ?int $userId = null, ?string $name = null)
    {
        $this->db = $db;
        $this->user = $user;
        $this->userId = $userId;
        $this->name = $name;
    }

    public static function ready($db): bool
    {
        try {
            return (bool) $db->query("SHOW TABLES LIKE 'tf_comm'")->getRowArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Attachments live outside the web root: writable/tf_comm/<intake id>/<file>. */
    public static function dir(int $intakeId = 0): string
    {
        $base = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'tf_comm';
        return $intakeId ? $base . DIRECTORY_SEPARATOR . $intakeId : $base;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function tourIdOf(int $intakeId): ?int
    {
        try {
            $r = $this->db->query('SELECT id FROM tf_tour WHERE intake_id = ?', [$intakeId])->getRowArray();
            return $r ? (int) $r['id'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function activity(): Activity
    {
        return new Activity($this->db, $this->user, $this->userId, $this->name);
    }

    // ------------------------------------------------------------------ recording

    /**
     * Record one communication. $m: direction in|out|internal, channel, tag, occurred_at, from_addr, to_addr, cc_addr,
     * subject, body, links (array of {kind,id,label}), source system|manual|import, ref_table, ref_id.
     */
    public function add(int $intakeId, array $m): array
    {
        $direction = in_array($m['direction'] ?? '', ['in', 'out', 'internal'], true) ? $m['direction'] : 'in';
        $channel = array_key_exists($m['channel'] ?? '', self::CHANNELS) ? ($m['channel'] ?? 'email') : 'email';
        $tag = array_key_exists($m['tag'] ?? '', self::TAGS) ? ($m['tag'] ?? '') : '';
        $source = in_array($m['source'] ?? '', ['system', 'manual', 'import'], true) ? $m['source'] : 'manual';
        $body = trim((string) ($m['body'] ?? ''));
        $subject = trim((string) ($m['subject'] ?? ''));
        if ($body === '' && $subject === '' && empty($m['allow_empty'])) {
            throw new \RuntimeException('Type the message, or at least its subject.');
        }
        $when = $this->parseDateTime($m['occurred_at'] ?? null) ?? $this->now();
        $links = is_array($m['links'] ?? null) ? array_values(array_filter($m['links'], static fn ($l) => is_array($l) && !empty($l['kind']))) : [];
        // tf-comms-r2: an e-mail sent from here also keeps bcc, the mailbox it went from, how the send ended and the thread it belongs to;
        // a reply (sent or received) joins the thread of the message it answers
        $sendState = in_array($m['send_state'] ?? '', ['sent', 'failed', 'not_sent'], true) ? $m['send_state'] : '';
        if (!empty($m['in_reply_to']) && empty($m['thread_key'])) {
            $orig = $this->db->query('SELECT id, intake_id, subject, thread_key FROM tf_comm WHERE id = ?', [(int) $m['in_reply_to']])->getRowArray();
            if ($orig && (int) $orig['intake_id'] === $intakeId) {
                $m['thread_key'] = self::threadKey($orig['thread_key'] ?? null, (string) $orig['subject']);
                if (empty($orig['thread_key'])) {
                    $this->db->query('UPDATE tf_comm SET thread_key = ? WHERE id = ?', [$m['thread_key'], (int) $orig['id']]);
                }
            } else {
                $m['in_reply_to'] = null;
            }
        }
        $this->db->query(
            'INSERT INTO tf_comm (intake_id, tour_id, direction, channel, tag, occurred_at, from_addr, to_addr, cc_addr, bcc_addr, subject, body, source, ref_table, ref_id, links_json, version, send_state, send_error, sent_from, in_reply_to, thread_key, message_id, recorded_by, recorded_by_id, recorded_name, recorded_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,?,?,?,?,?,?,?,?,?)',
            [$intakeId, $this->tourIdOf($intakeId), $direction, $channel, $tag, $when, self::cut($m['from_addr'] ?? null, 400), self::cut($m['to_addr'] ?? null, 400), self::cut($m['cc_addr'] ?? null, 500), self::cut($m['bcc_addr'] ?? null, 500),
             self::cut($subject, 400), $body !== '' ? $body : null, $source, self::cut($m['ref_table'] ?? null, 40), isset($m['ref_id']) ? (int) $m['ref_id'] : null,
             $links ? json_encode($links, JSON_UNESCAPED_UNICODE) : null, $sendState, self::cut($m['send_error'] ?? null, 500), self::cut($m['sent_from'] ?? null, 255),
             !empty($m['in_reply_to']) ? (int) $m['in_reply_to'] : null, self::cut($m['thread_key'] ?? null, 120), self::cut($m['message_id'] ?? null, 255), $this->user, $this->userId, $this->name, $this->now()]
        );
        $id = (int) $this->db->insertID();
        if ($source !== 'system') {
            $this->activity()->record('comm', $id, $intakeId, $this->tourIdOf($intakeId), 'comm_recorded', self::CHANNELS[$channel] . ' ' . ($direction === 'in' ? 'received' : ($direction === 'out' ? 'sent' : 'note')) . ($subject !== '' ? ' — ' . mb_substr($subject, 0, 120) : ''));
        }
        return $this->get($id);
    }

    /** Correct a recorded line: the previous version is kept inside the row (prev_json) and the audit log says who. */
    public function edit(int $id, array $m, string $why = ''): array
    {
        $cur = $this->get($id, true);
        if (!$cur) {
            throw new \RuntimeException('Not found.');
        }
        if ($cur['source'] === 'system') {
            throw new \RuntimeException('A line the system captured is not edited; add a note beside it instead.');
        }
        if ($cur['removed_at']) {
            throw new \RuntimeException('This line was removed.');
        }
        $prev = json_decode((string) ($cur['prev_json'] ?? ''), true) ?: [];
        $snapshot = array_intersect_key($cur, array_flip(['version', 'direction', 'channel', 'tag', 'occurred_at', 'from_addr', 'to_addr', 'cc_addr', 'subject', 'body', 'links_json', 'edited_at', 'edited_by']));
        $snapshot['saved_at'] = $this->now();
        $snapshot['saved_by'] = $this->user;
        $snapshot['why'] = $why;
        $prev[] = $snapshot;
        $direction = in_array($m['direction'] ?? $cur['direction'], ['in', 'out', 'internal'], true) ? ($m['direction'] ?? $cur['direction']) : $cur['direction'];
        $channel = array_key_exists($m['channel'] ?? $cur['channel'], self::CHANNELS) ? ($m['channel'] ?? $cur['channel']) : $cur['channel'];
        $tag = array_key_exists($m['tag'] ?? $cur['tag'], self::TAGS) ? ($m['tag'] ?? $cur['tag']) : $cur['tag'];
        $when = $this->parseDateTime($m['occurred_at'] ?? null) ?? $cur['occurred_at'];
        $links = array_key_exists('links', $m) ? (is_array($m['links']) ? json_encode(array_values($m['links']), JSON_UNESCAPED_UNICODE) : null) : $cur['links_json'];
        $this->db->query(
            'UPDATE tf_comm SET direction = ?, channel = ?, tag = ?, occurred_at = ?, from_addr = ?, to_addr = ?, cc_addr = ?, subject = ?, body = ?, links_json = ?, version = version + 1, prev_json = ?, edited_at = ?, edited_by = ? WHERE id = ?',
            [$direction, $channel, $tag, $when, self::cut($m['from_addr'] ?? $cur['from_addr'], 400), self::cut($m['to_addr'] ?? $cur['to_addr'], 400), self::cut($m['cc_addr'] ?? $cur['cc_addr'], 500),
             self::cut($m['subject'] ?? $cur['subject'], 400), array_key_exists('body', $m) ? (trim((string) $m['body']) ?: null) : $cur['body'], $links, json_encode($prev, JSON_UNESCAPED_UNICODE), $this->now(), $this->user, $id]
        );
        $this->activity()->record('comm', $id, (int) $cur['intake_id'], $cur['tour_id'] !== null ? (int) $cur['tour_id'] : null, 'comm_corrected', 'Communication #' . $id . ' corrected' . ($why !== '' ? ' — ' . mb_substr($why, 0, 200) : '') . ' (previous version kept)');
        return $this->get($id);
    }

    /** Remove a line from view — never deleted: it stays in the table with who and why, and the audit log says so. */
    public function remove(int $id, string $why): array
    {
        $cur = $this->get($id, true);
        if (!$cur) {
            throw new \RuntimeException('Not found.');
        }
        if ($cur['source'] === 'system') {
            throw new \RuntimeException('A line the system captured cannot be removed.');
        }
        if (mb_strlen(trim($why)) < 5) {
            throw new \RuntimeException('Give the reason (at least five characters) — it goes to the audit log.');
        }
        $this->db->query('UPDATE tf_comm SET removed_at = ?, removed_by = ?, remove_note = ? WHERE id = ?', [$this->now(), $this->user, self::cut($why, 500), $id]);
        $this->activity()->record('comm', $id, (int) $cur['intake_id'], $cur['tour_id'] !== null ? (int) $cur['tour_id'] : null, 'comm_removed', 'Communication #' . $id . ' removed from view — ' . mb_substr($why, 0, 200) . ' (kept in the table)');
        return $this->get($id, true);
    }

    public function get(int $id, bool $includeRemoved = false): ?array
    {
        $r = $this->db->query('SELECT * FROM tf_comm WHERE id = ?', [$id])->getRowArray();
        if (!$r || (!$includeRemoved && $r['removed_at'])) {
            return null;
        }
        $r['links'] = json_decode((string) ($r['links_json'] ?? ''), true) ?: [];
        $r['previous'] = json_decode((string) ($r['prev_json'] ?? ''), true) ?: [];
        $r['attachments'] = $this->attachments($id);
        return $r;
    }

    public function listFor(int $intakeId, bool $includeRemoved = false): array
    {
        $rows = $this->db->query('SELECT * FROM tf_comm WHERE intake_id = ?' . ($includeRemoved ? '' : ' AND removed_at IS NULL') . ' ORDER BY occurred_at DESC, id DESC', [$intakeId])->getResultArray();
        $att = [];
        foreach ($this->db->query('SELECT * FROM tf_comm_attachment WHERE intake_id = ? AND removed_at IS NULL ORDER BY id', [$intakeId])->getResultArray() as $a) {
            $att[(int) $a['comm_id']][] = $a;
        }
        foreach ($rows as &$r) {
            $r['links'] = json_decode((string) ($r['links_json'] ?? ''), true) ?: [];
            $r['attachments'] = $att[(int) $r['id']] ?? [];
            $r['versions'] = (int) $r['version'];
            unset($r['prev_json']);
        }
        return $rows;
    }

    // ------------------------------------------------------------------ attachments

    public function attachments(int $commId): array
    {
        return $this->db->query('SELECT * FROM tf_comm_attachment WHERE comm_id = ? AND removed_at IS NULL ORDER BY id', [$commId])->getResultArray();
    }

    /** $file = ['tmp' => path, 'name' => original, 'size' => bytes, 'mime' => type]. */
    public function attach(int $commId, array $file): array
    {
        $c = $this->get($commId, true);
        if (!$c) {
            throw new \RuntimeException('Not found.');
        }
        $name = trim((string) ($file['name'] ?? 'file'));
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            throw new \RuntimeException("\"{$name}\": this file type is not accepted (allowed: " . implode(', ', self::ALLOWED_EXT) . ').');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new \RuntimeException("\"{$name}\" is " . ($size <= 0 ? 'empty' : 'over 15 MB') . '.');
        }
        $dir = self::dir((int) $c['intake_id']);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('The attachments folder could not be created.');
        }
        foreach ([self::dir() => "<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n<IfModule !authz_core_module>\n    Deny from all\n</IfModule>\n"] as $d => $ht) {
            if (!is_file($d . DIRECTORY_SEPARATOR . '.htaccess')) {
                @file_put_contents($d . DIRECTORY_SEPARATOR . '.htaccess', $ht);
            }
        }
        $stored = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $stored;
        $tmp = (string) ($file['tmp'] ?? '');
        $ok = $tmp !== '' && (is_uploaded_file($tmp) ? @move_uploaded_file($tmp, $dest) : @copy($tmp, $dest));
        if (!$ok) {
            throw new \RuntimeException("\"{$name}\" could not be stored.");
        }
        $this->db->query('INSERT INTO tf_comm_attachment (comm_id, intake_id, file, original_name, mime, bytes, uploaded_at, uploaded_by) VALUES (?,?,?,?,?,?,?,?)',
            [$commId, (int) $c['intake_id'], $stored, self::cut($name, 255), self::cut($file['mime'] ?? null, 120), $size, $this->now(), $this->user]);
        $id = (int) $this->db->insertID();
        $this->activity()->record('comm', $commId, (int) $c['intake_id'], $c['tour_id'] !== null ? (int) $c['tour_id'] : null, 'comm_attached', 'Attachment "' . mb_substr($name, 0, 120) . '" (' . self::human($size) . ') added to communication #' . $commId);
        return $this->db->query('SELECT * FROM tf_comm_attachment WHERE id = ?', [$id])->getRowArray();
    }

    public function attachment(int $attId): ?array
    {
        $a = $this->db->query('SELECT * FROM tf_comm_attachment WHERE id = ? AND removed_at IS NULL', [$attId])->getRowArray();
        if (!$a) {
            return null;
        }
        $a['path'] = self::dir((int) $a['intake_id']) . DIRECTORY_SEPARATOR . $a['file'];
        return $a;
    }

    public function removeAttachment(int $attId, string $why): void
    {
        $a = $this->attachment($attId);
        if (!$a) {
            throw new \RuntimeException('Not found.');
        }
        if (mb_strlen(trim($why)) < 5) {
            throw new \RuntimeException('Give the reason (at least five characters).');
        }
        $this->db->query('UPDATE tf_comm_attachment SET removed_at = ?, removed_by = ? WHERE id = ?', [$this->now(), $this->user, $attId]);   // the file stays on disk
        $c = $this->get((int) $a['comm_id'], true);
        $this->activity()->record('comm', (int) $a['comm_id'], (int) $a['intake_id'], $c && $c['tour_id'] !== null ? (int) $c['tour_id'] : null, 'comm_attachment_removed', 'Attachment "' . $a['original_name'] . '" removed from view — ' . mb_substr($why, 0, 200) . ' (file kept)');
    }

    /** Copies every attachment folder into $target (for the TourFlow backup). */
    public static function copyAttachmentsTo(string $target): array
    {
        $src = self::dir();
        if (!is_dir($src)) {
            return ['files' => 0, 'bytes' => 0, 'target' => null];
        }
        $files = 0; $bytes = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $f) {
            $rel = substr((string) $f, strlen($src) + 1);
            $to = $target . DIRECTORY_SEPARATOR . $rel;
            if ($f->isDir()) {
                if (!is_dir($to)) {
                    @mkdir($to, 0775, true);
                }
            } else {
                if (!is_dir(dirname($to))) {
                    @mkdir(dirname($to), 0775, true);
                }
                if (@copy((string) $f, $to)) {
                    $files++;
                    $bytes += (int) $f->getSize();
                }
            }
        }
        return ['files' => $files, 'bytes' => $bytes, 'target' => $target];
    }

    // ------------------------------------------------------------------ follow-ups

    public function followups(int $intakeId, bool $all = false): array
    {
        return $this->db->query('SELECT * FROM tf_comm_followup WHERE intake_id = ?' . ($all ? '' : " AND status = 'open'") . ' ORDER BY (status = \'open\') DESC, due_on IS NULL, due_on, id', [$intakeId])->getResultArray();
    }

    public function addFollowup(int $intakeId, array $f): array
    {
        $title = trim((string) ($f['title'] ?? ''));
        if ($title === '') {
            throw new \RuntimeException('Say what has to be done.');
        }
        $ownerId = isset($f['owner_id']) && $f['owner_id'] !== '' ? (int) $f['owner_id'] : null;
        $ownerName = trim((string) ($f['owner_name'] ?? ''));
        if ($ownerId && $ownerName === '') {
            $ownerName = (string) Staff::displayName($this->db, $ownerId, '');
        }
        if ($ownerName === '') {
            throw new \RuntimeException('Every follow-up needs a responsible staff member.');
        }
        $due = $this->parseDate($f['due_on'] ?? null);
        if ($due === null) {
            throw new \RuntimeException('Every follow-up needs a due date.');
        }
        $this->db->query('INSERT INTO tf_comm_followup (intake_id, tour_id, comm_id, title, detail, owner_id, owner_name, due_on, status, created_at, created_by) VALUES (?,?,?,?,?,?,?,?,\'open\',?,?)',
            [$intakeId, $this->tourIdOf($intakeId), !empty($f['comm_id']) ? (int) $f['comm_id'] : null, self::cut($title, 255), self::cut($f['detail'] ?? null, 1000), $ownerId, self::cut($ownerName, 190), $due, $this->now(), $this->user]);
        $id = (int) $this->db->insertID();
        $this->activity()->record('followup', $id, $intakeId, $this->tourIdOf($intakeId), 'followup_added', 'Follow-up: ' . mb_substr($title, 0, 150) . ' — ' . $ownerName . ', due ' . $due);
        return $this->db->query('SELECT * FROM tf_comm_followup WHERE id = ?', [$id])->getRowArray();
    }

    public function setFollowup(int $id, array $f): array
    {
        $cur = $this->db->query('SELECT * FROM tf_comm_followup WHERE id = ?', [$id])->getRowArray();
        if (!$cur) {
            throw new \RuntimeException('Not found.');
        }
        $status = in_array($f['status'] ?? $cur['status'], ['open', 'done', 'dropped'], true) ? ($f['status'] ?? $cur['status']) : $cur['status'];
        $ownerId = array_key_exists('owner_id', $f) ? ($f['owner_id'] !== '' && $f['owner_id'] !== null ? (int) $f['owner_id'] : null) : $cur['owner_id'];
        $ownerName = array_key_exists('owner_name', $f) ? trim((string) $f['owner_name']) : (string) $cur['owner_name'];
        if ($ownerId && $ownerName === '') {
            $ownerName = (string) (Staff::displayName($this->db, $ownerId, '') ?: $cur['owner_name']);
        }
        $due = array_key_exists('due_on', $f) ? $this->parseDate($f['due_on']) : $cur['due_on'];
        $done = $status === 'open' ? null : ($cur['done_at'] ?: $this->now());
        $this->db->query('UPDATE tf_comm_followup SET title = ?, detail = ?, owner_id = ?, owner_name = ?, due_on = ?, status = ?, done_at = ?, done_by = ?, done_note = ?, updated_at = ? WHERE id = ?',
            [self::cut($f['title'] ?? $cur['title'], 255), self::cut(array_key_exists('detail', $f) ? $f['detail'] : $cur['detail'], 1000), $ownerId, self::cut($ownerName, 190), $due, $status, $done, $status === 'open' ? null : ($cur['done_by'] ?: $this->user), self::cut($f['done_note'] ?? $cur['done_note'], 500), $this->now(), $id]);
        $changes = [];
        foreach (['status' => $status, 'owner_name' => $ownerName, 'due_on' => $due] as $k => $v) {
            if ((string) $cur[$k] !== (string) $v) {
                $changes[] = ['what' => $k, 'from' => $cur[$k], 'to' => $v];
            }
        }
        $this->activity()->record('followup', $id, (int) $cur['intake_id'], $cur['tour_id'] !== null ? (int) $cur['tour_id'] : null, 'followup_' . $status, 'Follow-up "' . mb_substr((string) $cur['title'], 0, 120) . '": ' . ($changes ? implode('; ', array_map(static fn ($c) => "{$c['what']} {$c['from']} → {$c['to']}", $changes)) : 'updated'), $changes ?: null);
        return $this->db->query('SELECT * FROM tf_comm_followup WHERE id = ?', [$id])->getRowArray();
    }

    // ------------------------------------------------------------------ the continuous record

    /** Everything on one inquiry / tour, newest first, as one list of {at, kind, …}. */
    public function timeline(int $intakeId, int $limit = 400): array
    {
        $tourId = $this->tourIdOf($intakeId);
        $rows = [];
        foreach ($this->listFor($intakeId) as $c) {
            $rows[] = ['at' => $c['occurred_at'], 'kind' => 'comm', 'id' => (int) $c['id'], 'direction' => $c['direction'], 'channel' => $c['channel'], 'tag' => $c['tag'], 'source' => $c['source'],
                'subject' => $c['subject'], 'body' => $c['body'], 'from' => $c['from_addr'], 'to' => $c['to_addr'], 'cc' => $c['cc_addr'], 'by' => $c['recorded_name'] ?: $c['recorded_by'], 'recorded_at' => $c['recorded_at'],
                'links' => $c['links'], 'attachments' => $c['attachments'], 'versions' => (int) $c['version'], 'ref' => $c['ref_table'] ? $c['ref_table'] . '#' . $c['ref_id'] : null,
                'bcc' => $c['bcc_addr'] ?? null, 'send_state' => $c['send_state'] ?? '', 'send_error' => $c['send_error'] ?? null, 'sent_from' => $c['sent_from'] ?? null, 'in_reply_to' => isset($c['in_reply_to']) && $c['in_reply_to'] !== null ? (int) $c['in_reply_to'] : null, 'thread_key' => $c['thread_key'] ?? null, 'message_id' => $c['message_id'] ?? null];
        }
        foreach ((new Activity($this->db, $this->user, $this->userId, $this->name))->timeline($intakeId, $tourId, $limit) as $a) {
            if (($a['entity'] ?? '') === 'comm' || ($a['entity'] ?? '') === 'followup') {
                continue;   // the communication itself is the line; its bookkeeping lines would double it
            }
            $rows[] = ['at' => $a['at'], 'kind' => $a['kind'] === 'event' ? 'event' : 'activity', 'id' => (int) $a['id'], 'entity' => $a['entity'] ?? null, 'lane' => $a['lane'] ?? null, 'action' => $a['action'] ?? null,
                'summary' => $a['summary'] ?? null, 'changes' => $a['changes'] ?? [], 'by' => $a['by'] ?? null];
        }
        try {
            foreach ($this->db->query('SELECT * FROM tf_edit_request WHERE intake_id = ? ORDER BY id', [$intakeId])->getResultArray() as $e) {
                $rows[] = ['at' => $e['requested_at'], 'kind' => 'edit_request', 'id' => (int) $e['id'], 'seq' => (int) $e['seq'], 'status' => $e['status'], 'pasted' => $e['pasted_text'], 'change_to' => $e['change_to'], 'reason' => $e['reason'],
                    'by' => $e['requested_by'], 'checked_at' => $e['checked_at'], 'confirmed_by' => $e['confirmed_by'], 'confirmed_at' => $e['confirmed_at'], 'cancelled_at' => $e['cancelled_at'], 'cancel_note' => $e['cancel_note']];
            }
        } catch (\Throwable $e) {
        }
        try {
            foreach ($this->db->query('SELECT id, version, status, sell_usd, per_person_usd, pax, valid_until, issued_at, issued_by, created_at, created_by, note FROM tf_quote WHERE intake_id = ? ORDER BY version', [$intakeId])->getResultArray() as $q) {
                $rows[] = ['at' => $q['issued_at'] ?: $q['created_at'], 'kind' => 'quote', 'id' => (int) $q['id'], 'version' => (int) $q['version'], 'status' => $q['status'], 'sell_usd' => $q['sell_usd'], 'per_person_usd' => $q['per_person_usd'], 'pax' => $q['pax'], 'valid_until' => $q['valid_until'], 'by' => $q['issued_by'] ?: $q['created_by'], 'note' => $q['note']];
            }
        } catch (\Throwable $e) {
        }
        usort($rows, static fn ($a, $b) => strcmp((string) $b['at'], (string) $a['at']) ?: (($b['id'] ?? 0) <=> ($a['id'] ?? 0)));
        return array_slice($rows, 0, $limit);
    }

    /** The latest agreed arrangements, the open questions and the pending confirmations — the handover page. */
    public function summary(int $intakeId): array
    {
        $it = $this->db->query('SELECT * FROM tf_intake WHERE id = ?', [$intakeId])->getRowArray();
        if (!$it) {
            throw new \RuntimeException('Inquiry not found.');
        }
        $tour = $this->db->query('SELECT * FROM tf_tour WHERE intake_id = ?', [$intakeId])->getRowArray();
        $issued = null;
        try {
            $issued = $this->db->query("SELECT id, version, status, sell_usd, per_person_usd, pax, valid_until, issued_at, issued_by FROM tf_quote WHERE intake_id = ? AND status IN ('issued') ORDER BY version DESC LIMIT 1", [$intakeId])->getRowArray();
        } catch (\Throwable $e) {
        }
        $sold = null;
        if ($tour && !empty($tour['quote_id'])) {
            try {
                $sold = $this->db->query('SELECT id, version, status, sell_usd, per_person_usd, pax, issued_at FROM tf_quote WHERE id = ?', [(int) $tour['quote_id']])->getRowArray();
            } catch (\Throwable $e) {
            }
        }
        $applied = [];
        $waiting = [];
        try {
            foreach ($this->db->query('SELECT id, seq, status, change_to, reason, requested_by, requested_at, confirmed_by, confirmed_at FROM tf_edit_request WHERE intake_id = ? ORDER BY id', [$intakeId])->getResultArray() as $e) {
                if ($e['status'] === 'applied') {
                    $applied[] = $e;
                } elseif (in_array($e['status'], ['open', 'waiting'], true)) {
                    $waiting[] = $e;
                }
            }
        } catch (\Throwable $e) {
        }
        $agreed = $this->db->query("SELECT id, occurred_at, channel, direction, subject, body, from_addr, recorded_name FROM tf_comm WHERE intake_id = ? AND removed_at IS NULL AND tag IN ('agreement', 'approval', 'confirmation') ORDER BY occurred_at DESC LIMIT 20", [$intakeId])->getResultArray();
        $questions = $this->db->query("SELECT id, occurred_at, channel, direction, subject, body, from_addr, recorded_name FROM tf_comm WHERE intake_id = ? AND removed_at IS NULL AND tag = 'question' ORDER BY occurred_at DESC LIMIT 20", [$intakeId])->getResultArray();
        $requests = [];
        try {
            $requests = $this->db->query("SELECT id, kind, title, status, answer FROM tf_intake_request WHERE intake_id = ? AND status = 'open' ORDER BY sort_order, id", [$intakeId])->getResultArray();
        } catch (\Throwable $e) {
        }
        $hotels = [];
        if ($tour) {
            try {
                $hotels = $this->db->query("SELECT id, hotel_name, check_in, nights, status, confirmation_ref, confirmed_at FROM tf_hotel_job WHERE tour_id = ? AND archived_at IS NULL ORDER BY check_in", [(int) $tour['id']])->getResultArray();
            } catch (\Throwable $e) {
                $hotels = [];
            }
        }
        $lastIn = $this->db->query("SELECT occurred_at, channel, subject, from_addr FROM tf_comm WHERE intake_id = ? AND removed_at IS NULL AND direction = 'in' ORDER BY occurred_at DESC LIMIT 1", [$intakeId])->getRowArray();
        $lastOut = $this->db->query("SELECT occurred_at, channel, subject, to_addr FROM tf_comm WHERE intake_id = ? AND removed_at IS NULL AND direction = 'out' AND send_state <> 'failed' ORDER BY occurred_at DESC LIMIT 1", [$intakeId])->getRowArray();   // tf-comms-r2: a mail the server refused is no contact
        return [
            'inquiry' => ['id' => (int) $it['id'], 'inquiry_no' => $it['inquiry_no'], 'status' => $it['status'], 'tour_name' => $it['tour_name'], 'agent_name' => $it['agent_name'], 'agent_email' => $it['agent_email'],
                'arrival_date' => $it['arrival_date'], 'departure_date' => $it['departure_date'], 'nights' => $it['nights'], 'pax_total' => $it['pax_total'], 'handler_id' => $it['handler_id'], 'handler_name' => $it['handler_name'], 'archived_at' => $it['archived_at']],
            'tour' => $tour ? ['id' => (int) $tour['id'], 'tour_no' => $tour['tour_no'], 'stage' => $tour['stage'], 'confirmed_at' => $tour['confirmed_at'], 'confirmed_by' => $tour['confirmed_by'], 'handler_name' => $tour['handler_name'], 'amending' => $tour['amending']] : null,
            'quotation' => ['issued' => $issued, 'sold' => $sold],
            'changes_applied' => $applied,
            'changes_waiting' => $waiting,
            'agreed' => $agreed,
            'questions' => $questions,
            'open_requests' => $requests,
            'hotels' => $hotels,
            'followups' => $this->followups($intakeId),
            'last_in' => $lastIn,
            'last_out' => $lastOut,
            'counts' => ['comms' => (int) ($this->db->query('SELECT COUNT(*) n FROM tf_comm WHERE intake_id = ? AND removed_at IS NULL', [$intakeId])->getRowArray()['n'] ?? 0),
                'open_followups' => (int) ($this->db->query("SELECT COUNT(*) n FROM tf_comm_followup WHERE intake_id = ? AND status = 'open'", [$intakeId])->getRowArray()['n'] ?? 0)],
        ];
    }

    /** Search across every inquiry / tour: number, client / tour name, agent, travel dates, message text. */
    public function search(array $f, int $limit = 200): array
    {
        $q = trim((string) ($f['q'] ?? ''));
        $where = ['1=1']; $b = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(i.inquiry_no LIKE ? OR t.tour_no LIKE ? OR i.tour_name LIKE ? OR i.agent_name LIKE ? OR i.agent_email LIKE ? OR i.source_subject LIKE ? OR EXISTS (SELECT 1 FROM tf_comm c WHERE c.intake_id = i.id AND c.removed_at IS NULL AND (c.subject LIKE ? OR c.body LIKE ? OR c.from_addr LIKE ? OR c.to_addr LIKE ?)) OR EXISTS (SELECT 1 FROM tf_intake_pax p WHERE p.intake_id = i.id AND p.full_name LIKE ?))';
            array_push($b, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like);
        }
        if (!empty($f['from'])) { $where[] = 'i.departure_date >= ?'; $b[] = $f['from']; }
        if (!empty($f['to'])) { $where[] = 'i.arrival_date <= ?'; $b[] = $f['to']; }
        if (!empty($f['handler'])) { $where[] = '(i.handler_name LIKE ? OR t.handler_name LIKE ?)'; $b[] = '%' . $f['handler'] . '%'; $b[] = '%' . $f['handler'] . '%'; }
        if (!empty($f['open_only'])) { $where[] = "EXISTS (SELECT 1 FROM tf_comm_followup u WHERE u.intake_id = i.id AND u.status = 'open')"; }
        $sql = "SELECT i.id, i.inquiry_no, i.status, i.tour_name, i.agent_name, i.arrival_date, i.departure_date, i.pax_total, i.handler_name, i.archived_at, t.id AS tour_id, t.tour_no, t.stage,
                (SELECT COUNT(*) FROM tf_comm c WHERE c.intake_id = i.id AND c.removed_at IS NULL) AS comms,
                (SELECT MAX(c.occurred_at) FROM tf_comm c WHERE c.intake_id = i.id AND c.removed_at IS NULL) AS last_comm,
                (SELECT COUNT(*) FROM tf_comm_followup u WHERE u.intake_id = i.id AND u.status = 'open') AS open_followups,
                (SELECT MIN(u.due_on) FROM tf_comm_followup u WHERE u.intake_id = i.id AND u.status = 'open') AS next_due
            FROM tf_intake i LEFT JOIN tf_tour t ON t.intake_id = i.id WHERE " . implode(' AND ', $where) . ' ORDER BY i.arrival_date DESC, i.id DESC LIMIT ' . (int) $limit;
        return $this->db->query($sql, $b)->getResultArray();
    }

    /** Follow-ups due or overdue across every tour, for a person or everyone. */
    public function dueFollowups(?int $ownerId = null, int $limit = 200): array
    {
        $sql = "SELECT u.*, i.inquiry_no, i.tour_name, i.agent_name, i.arrival_date, t.tour_no FROM tf_comm_followup u JOIN tf_intake i ON i.id = u.intake_id LEFT JOIN tf_tour t ON t.intake_id = i.id WHERE u.status = 'open'" . ($ownerId ? ' AND u.owner_id = ?' : '') . ' ORDER BY u.due_on IS NULL, u.due_on, u.id LIMIT ' . (int) $limit;
        return $this->db->query($sql, $ownerId ? [$ownerId] : [])->getResultArray();
    }

    // ------------------------------------------------------------------ automatic capture

    /** The inquiry's own e-mail, as pasted (once per inquiry; a re-attached e-mail is a new line). */
    public function captureSourceEmail(int $intakeId): ?array
    {
        if (!self::ready($this->db)) {
            return null;
        }
        $it = $this->db->query('SELECT id, source_subject, source_from, source_received_at, source_text, created_at, created_by, agent_email FROM tf_intake WHERE id = ?', [$intakeId])->getRowArray();
        if (!$it || trim((string) $it['source_text']) === '') {
            return null;
        }
        $hash = sha1((string) $it['source_text']);
        $dup = $this->db->query("SELECT id FROM tf_comm WHERE intake_id = ? AND ref_table = 'tf_intake' AND source = 'system' AND SHA1(IFNULL(body, '')) = ?", [$intakeId, $hash])->getRowArray();
        if ($dup) {
            return null;
        }
        return $this->add($intakeId, ['direction' => 'in', 'channel' => 'email', 'source' => 'system', 'ref_table' => 'tf_intake', 'ref_id' => $intakeId,
            'occurred_at' => $it['source_received_at'] && substr((string) $it['source_received_at'], 0, 4) !== '0000' ? $it['source_received_at'] : $it['created_at'],
            'from_addr' => $it['source_from'] ?: $it['agent_email'], 'to_addr' => null, 'subject' => $it['source_subject'] ?: '(inquiry e-mail)', 'body' => $it['source_text'],
            'links' => [['kind' => 'inquiry', 'id' => $intakeId, 'label' => 'the inquiry as it arrived']]]);
    }

    /** A quotation e-mail the system sent (tf_quote_email row), with the PDF kept as the attachment when given. */
    public function captureQuoteEmail(int $quoteEmailId, ?string $pdfPath = null): ?array
    {
        if (!self::ready($this->db)) {
            return null;
        }
        $m = $this->db->query('SELECT * FROM tf_quote_email WHERE id = ?', [$quoteEmailId])->getRowArray();
        if (!$m) {
            return null;
        }
        if ($this->db->query("SELECT id FROM tf_comm WHERE ref_table = 'tf_quote_email' AND ref_id = ?", [$quoteEmailId])->getRowArray()) {
            return null;
        }
        $c = $this->add((int) $m['intake_id'], ['direction' => 'out', 'channel' => 'email', 'source' => 'system', 'ref_table' => 'tf_quote_email', 'ref_id' => $quoteEmailId, 'tag' => '',
            'occurred_at' => $m['sent_at'], 'from_addr' => $m['sent_from'], 'to_addr' => $m['to_email'], 'cc_addr' => $m['cc_email'],
            'subject' => ($m['status'] === 'failed' ? '[NOT SENT — ' . ($m['error'] ?: 'failed') . '] ' : '') . (string) $m['subject'], 'body' => $m['message'],
            'links' => [['kind' => 'quote', 'id' => (int) $m['quote_id'], 'label' => 'quotation version ' . $m['version']]]]);
        if ($pdfPath && is_file($pdfPath)) {
            try {
                $this->attach((int) $c['id'], ['tmp' => $pdfPath, 'name' => $m['pdf_name'] ?: ('quotation-v' . $m['version'] . '.pdf'), 'size' => (int) filesize($pdfPath), 'mime' => 'application/pdf']);
            } catch (\Throwable $e) {
                log_message('error', 'Quotation PDF not kept with the communication: ' . $e->getMessage());
            }
        }
        return $c;
    }

    /** A hotel letter sent from the system, or a hotel reply typed on the desk (tf_hotel_message row). */
    public function captureHotelMessage(int $messageId): ?array
    {
        if (!self::ready($this->db)) {
            return null;
        }
        $m = $this->db->query('SELECT m.*, j.hotel_name, t.intake_id FROM tf_hotel_message m JOIN tf_hotel_job j ON j.id = m.job_id JOIN tf_tour t ON t.id = m.tour_id WHERE m.id = ?', [$messageId])->getRowArray();
        if (!$m || empty($m['intake_id'])) {
            return null;
        }
        if ($this->db->query("SELECT id FROM tf_comm WHERE ref_table = 'tf_hotel_message' AND ref_id = ?", [$messageId])->getRowArray()) {
            return null;
        }
        $kind = ['request' => 'Booking request to hotel', 'voucher' => 'Voucher to hotel', 'reply' => 'Reply from hotel'][$m['kind']] ?? $m['kind'];
        return $this->add((int) $m['intake_id'], ['direction' => $m['kind'] === 'reply' ? 'in' : 'out', 'channel' => $m['channel'] === 'email' ? 'email' : 'note', 'source' => 'system', 'ref_table' => 'tf_hotel_message', 'ref_id' => $messageId,
            'tag' => $m['kind'] === 'reply' ? 'confirmation' : '', 'occurred_at' => $m['at'], 'from_addr' => $m['kind'] === 'reply' ? $m['hotel_name'] : null, 'to_addr' => $m['to_addr'], 'cc_addr' => $m['cc_addr'],
            'subject' => ($m['state'] === 'failed' ? '[NOT SENT] ' : '') . $kind . ' — ' . $m['hotel_name'] . ($m['subject'] ? ': ' . $m['subject'] : ''), 'body' => $m['body'],
            'links' => [['kind' => 'hotel_job', 'id' => (int) $m['job_id'], 'label' => 'hotel booking ' . $m['hotel_name']]]]);
    }

    /** Installer: capture what was already on file, once. */
    public static function backfill($db, string $by = 'installer'): array
    {
        if (!self::ready($db)) {
            return [];
        }
        $svc = new self($db, $by);
        $n = ['emails' => 0, 'quotes' => 0, 'hotels' => 0];
        foreach ($db->query("SELECT id FROM tf_intake WHERE source_text IS NOT NULL AND source_text <> ''")->getResultArray() as $r) {
            try {
                if ($svc->captureSourceEmail((int) $r['id'])) {
                    $n['emails']++;
                }
            } catch (\Throwable $e) {
                log_message('error', 'Communications backfill (inquiry ' . $r['id'] . '): ' . $e->getMessage());
            }
        }
        try {
            foreach ($db->query('SELECT id FROM tf_quote_email ORDER BY id')->getResultArray() as $r) {
                if ($svc->captureQuoteEmail((int) $r['id'])) {
                    $n['quotes']++;
                }
            }
        } catch (\Throwable $e) {
        }
        try {
            foreach ($db->query('SELECT id FROM tf_hotel_message ORDER BY id')->getResultArray() as $r) {
                if ($svc->captureHotelMessage((int) $r['id'])) {
                    $n['hotels']++;
                }
            }
        } catch (\Throwable $e) {
        }
        // tf-comms-r2: restaurant booking vouchers e-mailed from the Meal Master (schema 40)
        $n['vouchers'] = 0;
        try {
            foreach ($db->query("SELECT id FROM tf_meal_voucher WHERE mail_status IN ('sent', 'failed') ORDER BY id")->getResultArray() as $r) {
                if ($svc->captureMealVoucher((int) $r['id'])) {
                    $n['vouchers']++;
                }
            }
        } catch (\Throwable $e) {
        }
        $total = array_sum($n);
        return $total ? ["Communications & History: captured {$n['emails']} inquiry e-mail(s), {$n['quotes']} quotation e-mail(s), {$n['hotels']} hotel letter(s) / repl(ies), {$n['vouchers']} restaurant voucher e-mail(s) already on file."] : [];
    }

    // ------------------------------------------------------------------ tf-comms-r2: e-mails sent from here

    /** A restaurant booking voucher e-mailed from the Meal Master (tf_meal_voucher row with mail_status sent / failed). */
    public function captureMealVoucher(int $voucherId): ?array
    {
        if (!self::ready($this->db)) {
            return null;
        }
        try {
            $v = $this->db->query('SELECT * FROM tf_meal_voucher WHERE id = ?', [$voucherId])->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$v || empty($v['intake_id']) || !in_array((string) ($v['mail_status'] ?? ''), ['sent', 'failed'], true)) {
            return null;
        }
        if ($this->db->query("SELECT id FROM tf_comm WHERE ref_table = 'tf_meal_voucher' AND ref_id = ?", [$voucherId])->getRowArray()) {
            return null;
        }
        $failed = (string) $v['mail_status'] === 'failed';
        return $this->add((int) $v['intake_id'], ['direction' => 'out', 'channel' => 'email', 'source' => 'system', 'ref_table' => 'tf_meal_voucher', 'ref_id' => $voucherId, 'tag' => '',
            'occurred_at' => $v['sent_at'] ?: $this->now(), 'from_addr' => null, 'to_addr' => $v['to_email'], 'cc_addr' => $v['mail_cc'],
            'subject' => ($failed ? '[NOT SENT] ' : '') . 'Restaurant voucher ' . $v['voucher_no'] . ' — ' . $v['restaurant'] . ($v['mail_subject'] ? ': ' . $v['mail_subject'] : ''),
            'body' => $failed ? (string) ($v['mail_error'] ?: 'The e-mail could not be sent.') : trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>|<\/p>|<\/tr>/i', "\n", (string) $v['mail_body'])), ENT_QUOTES, 'UTF-8')),
            'send_state' => $failed ? 'failed' : 'sent', 'send_error' => $failed ? $v['mail_error'] : null,
            'links' => [['kind' => 'meal_voucher', 'id' => $voucherId, 'label' => 'restaurant voucher ' . $v['voucher_no']]]]);
    }

    /** The reference lines of a thread: the first message's key, or a fresh one from the subject. */
    public static function threadKey(?string $existing, string $subject): string
    {
        if ($existing !== null && trim($existing) !== '') {
            return trim($existing);
        }
        $core = strtolower(trim((string) preg_replace('/^\s*((re|fw|fwd|aw|wg)\s*:\s*)+/i', '', $subject)));
        return 't' . substr(sha1($core . '|' . microtime(true) . '|' . random_int(1, PHP_INT_MAX)), 0, 20);
    }

    /** What a reply to this message starts from: the addresses turned round, Re: subject, the original quoted. */
    public function replyDefaults(int $commId): ?array
    {
        $c = $this->get($commId);
        if (!$c) {
            return null;
        }
        $from = CommsMail::addresses($c['from_addr']);
        $to = CommsMail::addresses($c['to_addr']);
        $me = $this->db->query('SELECT email FROM user_acc_tbl WHERE id = ?', [(int) $this->userId])->getRowArray();
        $mine = strtolower(trim((string) ($me['email'] ?? '')));
        $sys = CommsMail::systemMailbox($this->db);
        $ours = array_filter([$mine, $sys ? strtolower($sys['email']) : '']);
        $toList = $c['direction'] === 'in' ? ($from ?: []) : ($to ?: []);
        $ccList = array_values(array_filter(array_merge($c['direction'] === 'in' ? $to : [], CommsMail::addresses($c['cc_addr'])), static fn ($a) => !in_array(strtolower($a), $ours, true) && !in_array(strtolower($a), array_map('strtolower', $toList), true)));
        $subject = (string) $c['subject'];
        if (!preg_match('/^\s*re\s*:/i', $subject)) {
            $subject = 'Re: ' . $subject;
        }
        $quoted = "\n\n\n---- On " . substr((string) $c['occurred_at'], 0, 16) . ($c['from_addr'] ? ' ' . $c['from_addr'] : '') . " wrote ----\n" . preg_replace('/^/m', '> ', (string) ($c['body'] ?? ''));
        return ['in_reply_to' => (int) $c['id'], 'thread_key' => self::threadKey($c['thread_key'] ?? null, (string) $c['subject']), 'to' => implode(', ', $toList), 'cc' => implode(', ', $ccList), 'subject' => $subject, 'quoted' => $quoted,
            'original' => ['id' => (int) $c['id'], 'subject' => $c['subject'], 'from' => $c['from_addr'], 'at' => $c['occurred_at']]];
    }

    /**
     * The documents of this inquiry / tour that can go with an e-mail: quotation versions (the client's quotation
     * and itinerary as a PDF), hotel letters and restaurant vouchers already sent (as PDF copies), and every file
     * already on this record. Each has a key the send endpoint understands.
     */
    public function documents(int $intakeId): array
    {
        $out = [];
        try {
            foreach ($this->db->query("SELECT id, version, status, issued_at, created_at FROM tf_quote WHERE intake_id = ? ORDER BY version DESC", [$intakeId])->getResultArray() as $q) {
                $out[] = ['key' => 'quote:' . (int) $q['id'], 'group' => 'Quotation & itinerary (PDF)', 'label' => 'Quotation / itinerary version ' . $q['version'] . ' (' . $q['status'] . ')', 'at' => $q['issued_at'] ?: $q['created_at']];
            }
        } catch (\Throwable $e) {
        }
        try {
            foreach ($this->db->query("SELECT m.id, m.kind, m.subject, m.`at`, j.hotel_name FROM tf_hotel_message m JOIN tf_hotel_job j ON j.id = m.job_id JOIN tf_tour t ON t.id = m.tour_id WHERE t.intake_id = ? AND m.kind IN ('request', 'voucher') AND m.body IS NOT NULL AND m.body <> '' ORDER BY m.id DESC", [$intakeId])->getResultArray() as $m) {
                $out[] = ['key' => 'hotel:' . (int) $m['id'], 'group' => 'Hotel letters (PDF copy)', 'label' => ($m['kind'] === 'voucher' ? 'Voucher — ' : 'Booking request — ') . $m['hotel_name'] . ' · ' . substr((string) $m['at'], 0, 10), 'at' => $m['at']];
            }
        } catch (\Throwable $e) {
        }
        try {
            foreach ($this->db->query("SELECT id, voucher_no, restaurant, sent_at FROM tf_meal_voucher WHERE intake_id = ? AND mail_body IS NOT NULL AND mail_body <> '' ORDER BY id DESC", [$intakeId])->getResultArray() as $v) {
                $out[] = ['key' => 'voucher:' . (int) $v['id'], 'group' => 'Restaurant vouchers (PDF copy)', 'label' => 'Voucher ' . $v['voucher_no'] . ' — ' . $v['restaurant'], 'at' => $v['sent_at']];
            }
        } catch (\Throwable $e) {
        }
        // tf-guided-r3 (2 Nov 2026): the customer invoices of THIS inquiry's own tour, so a quotation,
        // an itinerary and an invoice can go out together from the record. The invoice is reached only
        // through the tour this inquiry became (tf_tour.legacy_tour_id); nothing else is listed, and no
        // invoice search of any kind is opened here.
        try {
            foreach ($this->db->query("SELECT l.id, l.invoice_no, l.date, l.dollar_amount, l.cancel
                                         FROM debtor_invoice_log l
                                        WHERE l.invoice_no IS NOT NULL AND l.invoice_no <> ''
                                          AND (l.tour_id = (SELECT i.tour_id FROM tf_intake i WHERE i.id = ?)
                                               OR l.tour_id = (SELECT t.legacy_tour_id FROM tf_tour t WHERE t.intake_id = ? LIMIT 1))
                                        ORDER BY l.id DESC", [$intakeId, $intakeId])->getResultArray() as $inv) {
                $out[] = ['key' => 'invoice:' . (int) $inv['id'], 'group' => 'Invoice (PDF copy)',
                    'label' => 'Invoice ' . $inv['invoice_no'] . ' — USD ' . number_format((float) $inv['dollar_amount'], 2)
                        . ((int) $inv['cancel'] === 1 ? ' (cancelled)' : ''), 'at' => $inv['date']];
            }
        } catch (\Throwable $e) {
        }
        foreach ($this->db->query('SELECT a.id, a.original_name, a.bytes, a.uploaded_at, c.subject FROM tf_comm_attachment a JOIN tf_comm c ON c.id = a.comm_id WHERE a.intake_id = ? AND a.removed_at IS NULL AND c.removed_at IS NULL ORDER BY a.id DESC', [$intakeId])->getResultArray() as $a) {
            $out[] = ['key' => 'att:' . (int) $a['id'], 'group' => 'Files already on this record', 'label' => $a['original_name'] . ' (' . self::human((int) $a['bytes']) . ') — ' . mb_substr((string) $a['subject'], 0, 60), 'at' => $a['uploaded_at']];
        }
        return $out;
    }

    /**
     * Send an e-mail from this record and keep it here whatever happens. $in: to, cc, bcc (strings), subject, body,
     * tag, in_reply_to, links. $files: [{path,name}] prepared documents, [{tmp,name,size,mime}] uploads (tmp).
     * $sender: CommsMail::senderFor(). Returns the recorded line (send_state sent / failed) and ok.
     */
    public function sendEmail(int $intakeId, array $in, array $files, array $sender): array
    {
        $to = CommsMail::addresses($in['to'] ?? '');
        $cc = CommsMail::addresses($in['cc'] ?? '');
        $bcc = CommsMail::addresses($in['bcc'] ?? '');
        $subject = trim(str_replace(["\r", "\n"], ' ', (string) ($in['subject'] ?? '')));
        $body = trim(str_replace("\r", '', (string) ($in['body'] ?? '')));
        if (!$to) {
            throw new \InvalidArgumentException('Enter at least one e-mail address in To.');
        }
        if ($subject === '') {
            throw new \InvalidArgumentException('The e-mail needs a subject.');
        }
        if ($body === '') {
            throw new \InvalidArgumentException('Type the message.');
        }
        if (mb_strlen($body) > 20000) {
            throw new \InvalidArgumentException('The message is too long (20,000 characters at most).');
        }
        if ($sender['kind'] === 'none') {
            throw new \RuntimeException($sender['note']);
        }
        $signature = CommsMail::signature($this->db, $this->userId, $this->user);
        $it = $this->db->query('SELECT inquiry_no FROM tf_intake WHERE id = ?', [$intakeId])->getRowArray();
        $inquiryNo = (string) ($it['inquiry_no'] ?? '');
        $attachments = [];
        foreach ($files as $f) {
            $path = (string) ($f['path'] ?? $f['tmp'] ?? '');
            if ($path !== '' && is_file($path)) {
                $attachments[] = ['path' => $path, 'name' => (string) ($f['name'] ?? basename($path))];
            }
        }
        $replyTo = null;
        $threadKey = null;
        if (!empty($in['in_reply_to'])) {
            $orig = $this->get((int) $in['in_reply_to']);
            if ($orig && (int) $orig['intake_id'] === $intakeId) {
                $replyTo = (int) $orig['id'];
                $threadKey = self::threadKey($orig['thread_key'] ?? null, (string) $orig['subject']);
                if (empty($orig['thread_key'])) {
                    $this->db->query('UPDATE tf_comm SET thread_key = ? WHERE id = ? AND thread_key IS NULL', [$threadKey, (int) $orig['id']]);
                }
            }
        }
        $threadKey = $threadKey ?? self::threadKey(null, $subject);
        // tf-comms-r3: the reference tag in the subject and the conversation key under the signature — a reply that keeps either is filed with certainty
        if ($inquiryNo !== '' && !preg_match(CommsInbox::TAG_RE, $subject)) {
            $subject = mb_substr($subject, 0, 330) . ' [AVH:' . $inquiryNo . ']';
        }
        $msg = ['to' => $to, 'cc' => $cc, 'bcc' => $bcc, 'subject' => $subject, 'html' => CommsMail::html($body, $signature, 'AVH-REF:' . $threadKey), 'attachments' => $attachments, 'reply_to' => $sender['reply_to'] ?? null,
            'from_email' => $sender['kind'] === 'system' ? $sender['email'] : '', 'from_name' => $sender['name'] ?? 'Avenir Holidays', 'password' => $sender['kind'] === 'system' ? CommsMail::systemPassword($this->db) : '', 'user_id' => $this->userId, 'intake_id' => $intakeId];
        $r = CommsMail::send($this->db, $msg);
        $msg['password'] = '';
        $ccAll = array_merge($cc, $r['cc_added'] ?? []);
        $tag = array_key_exists($in['tag'] ?? '', self::TAGS) ? ($in['tag'] ?? '') : '';
        $links = is_array($in['links'] ?? null) ? $in['links'] : [];
        $c = $this->add($intakeId, ['direction' => 'out', 'channel' => 'email', 'source' => 'system', 'tag' => $tag, 'occurred_at' => $this->now(),
            'from_addr' => ($sender['name'] ?? 'Avenir Holidays') . ' <' . ($r['from'] ?: ($sender['email'] ?? '')) . '>', 'to_addr' => implode(', ', $to), 'cc_addr' => $ccAll ? implode(', ', $ccAll) : null, 'bcc_addr' => $bcc ? implode(', ', $bcc) : null,
            'subject' => $subject, 'body' => $body . "\n\n" . $signature . "\n\nRef: AVH-REF:" . $threadKey, 'send_state' => $r['ok'] ? 'sent' : 'failed', 'send_error' => $r['ok'] ? null : $r['error'], 'sent_from' => $r['from'] ?: ($sender['email'] ?? null),
            'in_reply_to' => $replyTo, 'thread_key' => $threadKey, 'message_id' => $r['message_id'] ?? null, 'links' => $links]);
        foreach ($files as $f) {
            try {
                if (!empty($f['tmp'])) {
                    $this->attach((int) $c['id'], $f);
                } elseif (!empty($f['path']) && is_file($f['path'])) {
                    $this->attach((int) $c['id'], ['tmp' => $f['path'], 'name' => $f['name'] ?? basename($f['path']), 'size' => (int) filesize($f['path']), 'mime' => $f['mime'] ?? (str_ends_with(strtolower((string) ($f['name'] ?? '')), '.pdf') ? 'application/pdf' : null)]);
                }
            } catch (\Throwable $e) {
                log_message('error', 'Comms e-mail attachment not kept: ' . $e->getMessage());
            }
        }
        $tourId = $this->tourIdOf($intakeId);
        $this->activity()->record('comm', (int) $c['id'], $intakeId, $tourId, $r['ok'] ? 'comm_email_sent' : 'comm_email_failed',
            ($r['ok'] ? 'E-mail sent to ' : 'E-mail NOT sent to ') . implode(', ', $to) . ($ccAll ? ' (cc ' . implode(', ', $ccAll) . ')' : '') . ' — ' . mb_substr($subject, 0, 120) . ' — by ' . ($this->name ?: $this->user) . ' from ' . ($r['from'] ?: 'no mailbox') . (count($attachments) ? ', ' . count($attachments) . ' attachment(s)' : '') . ($r['ok'] ? '' : ': ' . $r['error']));
        return ['ok' => $r['ok'], 'error' => $r['error'], 'comm' => $this->get((int) $c['id']), 'cc_added' => $r['cc_added'] ?? []];
    }

    // ------------------------------------------------------------------ helpers

    private function parseDateTime($v): ?string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        $t = strtotime(str_replace('T', ' ', $v));
        return $t ? date('Y-m-d H:i:s', $t) : null;
    }

    private function parseDate($v): ?string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        $t = strtotime($v);
        return $t ? date('Y-m-d', $t) : null;
    }

    private static function cut($v, int $len): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : mb_substr($v, 0, $len);
    }

    public static function human(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : ($bytes >= 1024 ? round($bytes / 1024) . ' KB' : $bytes . ' B');
    }
}
