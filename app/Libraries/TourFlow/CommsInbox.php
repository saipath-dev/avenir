<?php

namespace App\Libraries\TourFlow;

/**
 * tf-comms-r3 (27 Oct 2026, Shantha) — incoming e-mail, collected from the TourFlow mailbox and filed on the right
 * inquiry / tour's Communications & History record.
 *
 * The mailbox (Tour Desk Admin → Settings → Incoming mailbox: IMAP host, port, SSL, address, encrypted password)
 * is opened READ-ONLY. Messages are taken by UID, every UID above the last one recorded — seen or unseen, because
 * another mail program may have read them first — and a message whose Message-ID is already on file is never
 * imported twice. Each message is written to tf_mail_in (with its attachments under writable/tf_mail_in/) and
 * only then is the last UID moved on; nothing in the mailbox is ever flagged, moved or deleted. If the folder's
 * UIDVALIDITY changes (the server renumbered it) the UID counter starts again and the Message-ID check alone
 * keeps duplicates out.
 *
 * Filing is strict, in this order, and nothing else files a message automatically:
 *   1. In-Reply-To / References names a Message-ID of an e-mail this system sent  → that conversation (certain)
 *   2. the subject or body carries the system's reference tag  [AVH:INQ 1038]  or  [AVH:AVH-2026-00012]  → that record
 *   3. the body quotes the conversation key an e-mail from here carries  (AVH-REF:…)  → that conversation
 * A similar subject, the same sender or a loosely typed number never files anything: the message waits in the
 * "Unmatched incoming e-mails" queue for a person to attach (with the reason kept) or discard (with a reason).
 *
 * Runs from: the queue page's "Check mailbox now"; the Communications pages' quiet check (at most every five
 * minutes, when a page loads); `php spark tourflow:inbox`; or GET api/tourflow/mail_poll/<token> for a scheduled
 * task (Windows Task Scheduler / cron) so mail is collected when nobody is signed in.
 */
final class CommsInbox
{
    public const META = ['inbox_host', 'inbox_port', 'inbox_secure', 'inbox_email', 'inbox_pass', 'inbox_folder', 'inbox_last_uid', 'inbox_uidvalidity', 'inbox_last_check', 'inbox_last_result', 'inbox_poll_token', 'inbox_web_poll', 'inbox_auto_quote', 'inbox_enabled'];
    public const MAX_PER_RUN = 50;
    public const MAX_RAW = 26214400;   // 25 MB: a larger message is noted and skipped
    public const AUTO_INTERVAL = 300;  // seconds between the quiet checks a page load triggers
    public const TAG_RE = '/\[AVH:\s*((?:INQ)\s*\d{3,6}|[A-Z]{2,5}-\d{4}-\d{3,6})\s*\]/i';
    public const REF_RE = '/AVH-REF:\s*([a-z0-9]{10,40})/i';

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

    public static function ready($db): bool
    {
        try {
            return (bool) $db->query("SHOW TABLES LIKE 'tf_mail_in'")->getRowArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function dir(int $mailId = 0): string
    {
        $base = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'tf_mail_in';
        return $mailId ? $base . DIRECTORY_SEPARATOR . $mailId : $base;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    // ------------------------------------------------------------------ settings

    /** The incoming mailbox settings, without the password (has_password instead). */
    public static function settings($db): array
    {
        $m = ['host' => '', 'port' => 993, 'secure' => 'ssl', 'email' => '', 'folder' => 'INBOX', 'has_password' => false, 'password_state' => 'none', 'last_uid' => 0, 'uidvalidity' => '', 'last_check' => null, 'last_result' => null, 'auto_quote' => false, 'enabled' => true, 'has_token' => false, 'web_poll' => false, 'imap_ext' => extension_loaded('imap')];
        try {
            foreach ($db->query('SELECT k, v FROM tf_meta WHERE k LIKE ?', ['inbox\_%'])->getResultArray() as $r) {
                $k = substr((string) $r['k'], 6);
                $v = (string) $r['v'];
                switch ($k) {
                    case 'pass':
                        $m['has_password'] = $v !== '';
                        $m['password_state'] = Secrets::state($v);
                        break;
                    case 'poll_token':
                        $m['has_token'] = $v !== '';
                        break;
                    case 'port':
                        $m['port'] = (int) $v ?: 993;
                        break;
                    case 'last_uid':
                        $m['last_uid'] = (int) $v;
                        break;
                    case 'auto_quote':
                        $m['auto_quote'] = $v === '1';
                        break;
                    case 'enabled':
                        $m['enabled'] = $v !== '0';
                        break;
                    case 'web_poll':
                        $m['web_poll'] = $v === '1';
                        break;
                    default:
                        $m[$k] = $v;
                }
            }
        } catch (\Throwable $e) {
        }
        $m['configured'] = $m['host'] !== '' && $m['email'] !== '' && $m['password_state'] === 'sealed';
        return $m;
    }

    private static function meta($db, string $k, $v): void
    {
        $db->query('INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', ['inbox_' . $k, (string) $v]);
    }

    private static function metaGet($db, string $k): string
    {
        $r = $db->query('SELECT v FROM tf_meta WHERE k = ?', ['inbox_' . $k])->getRowArray();
        return (string) ($r['v'] ?? '');
    }

    /** Save the settings (System Administrator). The password is checked with the server before it is kept. */
    public static function saveSettings($db, array $b): array
    {
        $was = self::settings($db);
        $host = trim((string) ($b['host'] ?? $was['host']));
        $port = (int) ($b['port'] ?? $was['port']) ?: 993;
        $secure = in_array($b['secure'] ?? $was['secure'], ['ssl', 'tls', 'none'], true) ? $b['secure'] : 'ssl';
        $email = trim((string) ($b['email'] ?? $was['email']));
        $folder = trim((string) ($b['folder'] ?? $was['folder'])) ?: 'INBOX';
        $pw = (string) ($b['password'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('"' . $email . '" is not an e-mail address.');
        }
        if ($host !== '' && !preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
            throw new \InvalidArgumentException('"' . $host . '" is not a server name.');
        }
        $changes = [];
        if ($pw !== '') {
            if ($host === '' || $email === '') {
                throw new \InvalidArgumentException('Enter the server and the mailbox address first.');
            }
            $r = self::checkLogin($host, $port, $secure, $folder, $email, $pw);
            if (!$r['ok']) {
                throw new \RuntimeException($r['message'] . ' Nothing was saved.');
            }
            self::meta($db, 'pass', Secrets::seal($pw));
            $changes[] = ['what' => 'incoming mailbox password', 'from' => $was['has_password'] ? '(set)' : '(none)', 'to' => '(set — accepted by ' . $host . ', encrypted at rest)'];
        } elseif ($was['has_password'] && (strcasecmp($email, $was['email']) !== 0 || strcasecmp($host, $was['host']) !== 0)) {
            self::meta($db, 'pass', '');
            $changes[] = ['what' => 'incoming mailbox password', 'from' => '(set)', 'to' => '(cleared — the address or server changed; enter the password again)'];
        }
        foreach (['host' => $host, 'port' => $port, 'secure' => $secure, 'email' => $email, 'folder' => $folder] as $k => $v) {
            if ((string) $was[$k] !== (string) $v) {
                $changes[] = ['what' => 'incoming mailbox ' . $k, 'from' => $was[$k], 'to' => $v];
                if ($k === 'email' || $k === 'host' || $k === 'folder') {
                    self::meta($db, 'last_uid', 0);   // a different mailbox: start its UIDs from the beginning (Message-ID keeps duplicates out)
                    self::meta($db, 'uidvalidity', '');
                }
            }
            self::meta($db, $k, $v);
        }
        if (array_key_exists('auto_quote', $b)) {
            self::meta($db, 'auto_quote', !empty($b['auto_quote']) ? '1' : '0');
            if ($was['auto_quote'] !== !empty($b['auto_quote'])) {
                $changes[] = ['what' => 'draft a quotation from a request e-mail automatically', 'from' => $was['auto_quote'] ? 'on' : 'off', 'to' => !empty($b['auto_quote']) ? 'on' : 'off'];
            }
        }
        if (array_key_exists('enabled', $b)) {
            self::meta($db, 'enabled', !empty($b['enabled']) ? '1' : '0');
        }
        if (array_key_exists('web_poll', $b)) {
            self::meta($db, 'web_poll', !empty($b['web_poll']) ? '1' : '0');
            if ($was['web_poll'] !== !empty($b['web_poll'])) {
                $changes[] = ['what' => 'web entry for the scheduled task', 'from' => $was['web_poll'] ? 'on' : 'off', 'to' => !empty($b['web_poll']) ? 'on' : 'off'];
            }
        }
        $newToken = null;
        if (!empty($b['new_token'])) {
            // the token is shown once and kept only as a hash: it cannot be read back from the database or a backup
            $newToken = bin2hex(random_bytes(24));
            self::meta($db, 'poll_token', 'sha256:' . hash('sha256', $newToken));
            $changes[] = ['what' => 'scheduled-task token', 'from' => $was['has_token'] ? '(set)' : '(none)', 'to' => '(new one made; shown once)'];
        }
        return ['settings' => self::settings($db), 'changes' => $changes, 'new_token' => $newToken];
    }

    /** Is this the scheduled task's token? Only its hash is kept; the web entry must also be switched on. */
    public static function pollTokenOk($db, string $token): bool
    {
        $stored = self::metaGet($db, 'poll_token');
        if ($stored === '' || strlen($token) < 32 || self::metaGet($db, 'web_poll') !== '1') {
            return false;
        }
        $hash = str_starts_with($stored, 'sha256:') ? substr($stored, 7) : hash('sha256', $stored);
        return hash_equals($hash, hash('sha256', $token));
    }

    private static function mailboxSpec(string $host, int $port, string $secure, string $folder): string
    {
        $flags = '/imap' . ($secure === 'ssl' ? '/ssl' : ($secure === 'tls' ? '/tls' : '/notls')) . ($secure !== 'none' ? '/novalidate-cert' : '') . '/readonly';
        return '{' . $host . ':' . $port . $flags . '}' . $folder;
    }

    /** Opens the mailbox read-only and says what it found; nothing is read or changed. */
    public static function checkLogin(string $host, int $port, string $secure, string $folder, string $email, string $password): array
    {
        if (!extension_loaded('imap')) {
            return ['ok' => false, 'message' => 'The PHP imap extension is not loaded on this server, so the mailbox cannot be read.'];
        }
        $spec = self::mailboxSpec($host, $port, $secure, $folder);
        $old = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', '20');
        imap_timeout(IMAP_OPENTIMEOUT, 20);
        imap_timeout(IMAP_READTIMEOUT, 30);
        $m = @imap_open($spec, $email, $password, OP_READONLY, 1);
        ini_set('default_socket_timeout', (string) $old);
        if ($m === false) {
            $err = (string) (imap_last_error() ?: 'the server did not answer');
            imap_errors();
            return ['ok' => false, 'message' => MailServer::readable('IMAP: ' . $err, $host, $email)];
        }
        $st = @imap_status($m, $spec, SA_MESSAGES | SA_UIDVALIDITY | SA_UIDNEXT);
        @imap_close($m);
        imap_errors();
        $n = $st ? (int) $st->messages : 0;
        return ['ok' => true, 'message' => 'The mail server ' . $host . ' accepted the login for ' . $email . ' (' . $folder . ': ' . $n . ' message' . ($n === 1 ? '' : 's') . '). Nothing was read.', 'messages' => $n, 'uidvalidity' => $st ? (string) $st->uidvalidity : '', 'uidnext' => $st ? (int) $st->uidnext : 0];
    }

    // ------------------------------------------------------------------ collecting

    /** The quiet check a page load triggers: only when the last check is older than AUTO_INTERVAL. */
    public function autoCheck(): ?array
    {
        $s = self::settings($this->db);
        if (!$s['configured'] || !$s['enabled']) {
            return null;
        }
        if ($s['last_check'] && strtotime((string) $s['last_check']) > time() - self::AUTO_INTERVAL) {
            return null;
        }
        return $this->check('page');
    }

    /**
     * Collect new messages. Returns counts and notes; every message is on tf_mail_in before the UID counter moves.
     * @return array{ok:bool, message:string, new:int, filed:int, unmatched:int, skipped:int, duplicates:int, drafts:int}
     */
    public function check(string $trigger = 'manual'): array
    {
        $out = ['ok' => false, 'message' => '', 'new' => 0, 'filed' => 0, 'unmatched' => 0, 'skipped' => 0, 'duplicates' => 0, 'drafts' => 0, 'trigger' => $trigger];
        $s = self::settings($this->db);
        if (!$s['configured']) {
            $out['message'] = $s['imap_ext'] ? 'The incoming mailbox is not set up (Tour Desk Admin → Settings → Incoming mailbox).' : 'The PHP imap extension is not loaded on this server.';
            return $out;
        }
        if (!$s['enabled']) {
            $out['message'] = 'Collecting is switched off (Tour Desk Admin → Settings → Incoming mailbox).';
            return $out;
        }
        // one run at a time
        $lock = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'tf_mail_in' . DIRECTORY_SEPARATOR . '.lock';
        if (!is_dir(dirname($lock))) {
            @mkdir(dirname($lock), 0775, true);
        }
        $fh = @fopen($lock, 'c');
        if ($fh && !flock($fh, LOCK_EX | LOCK_NB)) {
            fclose($fh);
            $out['message'] = 'Another check is running; try again in a moment.';
            return $out;
        }
        $password = Secrets::reveal(self::metaGet($this->db, 'pass'));
        $spec = self::mailboxSpec($s['host'], (int) $s['port'], $s['secure'], $s['folder']);
        imap_timeout(IMAP_OPENTIMEOUT, 20);
        imap_timeout(IMAP_READTIMEOUT, 60);
        $m = @imap_open($spec, $s['email'], $password, OP_READONLY, 1);
        $password = '';
        if ($m === false) {
            $err = (string) (imap_last_error() ?: 'the server did not answer');
            imap_errors();
            $out['message'] = MailServer::readable('IMAP: ' . $err, $s['host'], $s['email']);
            $this->finish($out, $fh, $lock);
            return $out;
        }
        $this->conn = $m;
        try {
            $st = @imap_status($m, $spec, SA_MESSAGES | SA_UIDVALIDITY | SA_UIDNEXT);
            $validity = $st ? (string) $st->uidvalidity : '';
            $lastUid = (int) $s['last_uid'];
            if ($validity !== '' && $s['uidvalidity'] !== '' && $validity !== (string) $s['uidvalidity']) {
                $lastUid = 0;   // the server renumbered the folder: read everything again; Message-ID keeps duplicates out
                $out['notes'][] = 'The mailbox was renumbered by the server (UIDVALIDITY changed); every message was checked again by Message-ID.';
            }
            $total = $st ? (int) $st->messages : 0;
            $overview = $total > 0 ? (@imap_fetch_overview($m, ($lastUid + 1) . ':*', FT_UID) ?: []) : [];
            $uids = [];
            foreach ($overview as $o) {
                if ((int) $o->uid > $lastUid) {
                    $uids[(int) $o->uid] = $o;
                }
            }
            ksort($uids);
            $taken = 0;
            $highest = $lastUid;
            foreach ($uids as $uid => $o) {
                if ($taken >= self::MAX_PER_RUN) {
                    $out['notes'][] = 'More than ' . self::MAX_PER_RUN . ' new messages: the rest are collected on the next check.';
                    break;
                }
                $taken++;
                $r = $this->import($m, $uid, $o, $s);
                $out[$r['outcome']]++;
                if (!empty($r['draft'])) {
                    $out['drafts']++;
                }
                if ($r['outcome'] === 'filed' || $r['outcome'] === 'unmatched') {
                    $out['new']++;
                }
                if (!empty($r['note'])) {
                    $out['notes'][] = $r['note'];
                }
                if ($r['outcome'] !== 'error') {
                    $highest = max($highest, $uid);
                    self::meta($this->db, 'last_uid', $highest);   // only once the row is safely on file
                } else {
                    break;   // a message that could not be stored is tried again next time; nothing after it is skipped
                }
            }
            if ($validity !== '') {
                self::meta($this->db, 'uidvalidity', $validity);
            }
            $out['ok'] = true;
            $out['message'] = $out['new'] ? $out['new'] . ' new message' . ($out['new'] === 1 ? '' : 's') . ': ' . $out['filed'] . ' filed on a record, ' . $out['unmatched'] . ' waiting in the queue' . ($out['duplicates'] ? ', ' . $out['duplicates'] . ' already on file' : '') . ($out['skipped'] ? ', ' . $out['skipped'] . ' skipped' : '') . ($out['drafts'] ? ', ' . $out['drafts'] . ' quotation draft(s) prepared for review' : '') . '.' : 'Nothing new in the mailbox' . ($out['duplicates'] ? ' (' . $out['duplicates'] . ' already on file)' : '') . '.';
        } catch (\Throwable $e) {
            $out['message'] = 'The check stopped: ' . $e->getMessage();
        } finally {
            $this->conn = null;
            @imap_close($m);
            imap_errors();
        }
        $this->finish($out, $fh, $lock);
        return $out;
    }

    private function finish(array $out, $fh, string $lock): void
    {
        self::meta($this->db, 'last_check', $this->now());
        self::meta($this->db, 'last_result', mb_substr(($out['ok'] ? 'OK — ' : 'FAILED — ') . $out['message'], 0, 500));
        if ($fh) {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
        try {
            if ($out['new'] > 0 || !$out['ok']) {
                Activity::master($this->db, $this->user, 'inbox', 0, 'Incoming mailbox check (' . $out['trigger'] . '): ' . $out['message'], [], $this->userId, $this->name);
            }
        } catch (\Throwable $e) {
        }
    }

    /** One message → tf_mail_in (+ attachments) → filed or queued. outcome: filed | unmatched | duplicates | skipped | error */
    private function import($m, int $uid, object $o, array $s): array
    {
        $messageId = trim((string) ($o->message_id ?? ''));
        $size = (int) ($o->size ?? 0);
        if ($messageId !== '' && $this->db->query('SELECT id FROM tf_mail_in WHERE message_id = ?', [$messageId])->getRowArray()) {
            return ['outcome' => 'duplicates'];
        }
        if ($this->db->query('SELECT id FROM tf_mail_in WHERE mailbox = ? AND uid = ? AND uidvalidity = ?', [$s['email'], $uid, (string) ($s['uidvalidity'] ?: '')])->getRowArray()) {
            return ['outcome' => 'duplicates'];
        }
        if ($size > self::MAX_RAW) {
            $this->db->query('INSERT INTO tf_mail_in (mailbox, uid, uidvalidity, message_id, subject, from_addr, received_at, status, note, imported_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
                [$s['email'], $uid, (string) $s['uidvalidity'], $messageId ?: null, mb_substr(self::decodeHeader((string) ($o->subject ?? '')), 0, 400), mb_substr(self::decodeHeader((string) ($o->from ?? '')), 0, 400), $this->when($o->date ?? null), 'skipped', 'Message of ' . Comms::human($size) . ' not imported (over 25 MB).', $this->now()]);
            return ['outcome' => 'skipped', 'note' => 'One message of ' . Comms::human($size) . ' was skipped (over 25 MB): ' . self::decodeHeader((string) ($o->subject ?? ''))];
        }
        try {
            $hdrRaw = (string) @imap_fetchheader($m, $uid, FT_UID | FT_PREFETCHTEXT);
            $h = @imap_rfc822_parse_headers($hdrRaw);
            $structure = @imap_fetchstructure($m, $uid, FT_UID);
            $parts = ['text' => '', 'html' => '', 'attachments' => []];
            if ($structure) {
                $this->walk($m, $uid, $structure, '', $parts);
            }
            $text = trim($parts['text']) !== '' ? $parts['text'] : self::htmlToText($parts['html']);
            $from = $h && !empty($h->from) ? self::addrList($h->from) : self::decodeHeader((string) ($o->from ?? ''));
            $to = $h && !empty($h->to) ? self::addrList($h->to) : '';
            $cc = $h && !empty($h->cc) ? self::addrList($h->cc) : '';
            $replyTo = $h && !empty($h->reply_to) ? self::addrList($h->reply_to) : '';
            $subject = $h && isset($h->subject) ? self::decodeHeader((string) $h->subject) : self::decodeHeader((string) ($o->subject ?? ''));
            $inReplyTo = $h && isset($h->in_reply_to) ? trim((string) $h->in_reply_to) : '';
            $references = $h && isset($h->references) ? trim((string) $h->references) : '';
            if ($messageId === '' && $h && isset($h->message_id)) {
                $messageId = trim((string) $h->message_id);
            }
            if ($messageId !== '' && $this->db->query('SELECT id FROM tf_mail_in WHERE message_id = ?', [$messageId])->getRowArray()) {
                return ['outcome' => 'duplicates'];
            }
            $this->db->query(
                'INSERT INTO tf_mail_in (mailbox, uid, uidvalidity, message_id, in_reply_to, references_hdr, from_addr, to_addr, cc_addr, reply_to, subject, body_text, body_html, received_at, size_bytes, status, imported_at, imported_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$s['email'], $uid, (string) $s['uidvalidity'], $messageId ?: null, mb_substr($inReplyTo, 0, 998) ?: null, mb_substr($references, 0, 4000) ?: null, mb_substr($from, 0, 400), mb_substr($to, 0, 400) ?: null, mb_substr($cc, 0, 500) ?: null, mb_substr($replyTo, 0, 400) ?: null,
                 mb_substr($subject, 0, 400), $text !== '' ? $text : null, $parts['html'] !== '' ? mb_substr($parts['html'], 0, 1000000) : null, $this->when($h->date ?? ($o->date ?? null)), $size, 'new', $this->now(), $this->user]
            );
            $id = (int) $this->db->insertID();
            foreach ($parts['attachments'] as $a) {
                $this->storeAttachment($id, $uid, $a);
            }
            $r = $this->file($id);
            $draft = false;
            if ($r['status'] === 'unmatched' && $s['auto_quote']) {
                try {
                    $d = (new CommsQuoteDraft($this->db, $this->user, $this->userId, $this->name))->fromMail($id, true);
                    $draft = !empty($d['intake_id']);
                } catch (\Throwable $e) {
                    log_message('error', 'Quotation draft from mail ' . $id . ': ' . $e->getMessage());
                }
            }
            return ['outcome' => $r['status'] === 'matched' ? 'filed' : 'unmatched', 'draft' => $draft];
        } catch (\Throwable $e) {
            log_message('error', 'Incoming mail UID ' . $uid . ' not stored: ' . $e->getMessage());
            return ['outcome' => 'error', 'note' => 'A message could not be stored (' . $e->getMessage() . '); it is tried again on the next check.'];
        }
    }

    private function when($v): string
    {
        $t = $v ? strtotime((string) $v) : false;
        return $t ? date('Y-m-d H:i:s', $t) : $this->now();
    }

    private static function decodeHeader(string $v): string
    {
        $out = '';
        foreach (@imap_mime_header_decode($v) ?: [] as $p) {
            $cs = strtoupper((string) $p->charset);
            $out .= ($cs === 'DEFAULT' || $cs === 'US-ASCII' || $cs === 'UTF-8') ? $p->text : (@mb_convert_encoding($p->text, 'UTF-8', $cs) ?: $p->text);
        }
        return trim(MailServer::utf8($out !== '' ? $out : $v));
    }

    private static function addrList(array $list): string
    {
        $out = [];
        foreach ($list as $a) {
            $mail = isset($a->mailbox, $a->host) ? $a->mailbox . '@' . $a->host : '';
            $name = isset($a->personal) ? self::decodeHeader((string) $a->personal) : '';
            $out[] = $name !== '' && $mail !== '' ? $name . ' <' . $mail . '>' : ($mail ?: $name);
        }
        return implode(', ', array_filter($out));
    }

    /** Walks the MIME structure: the text and html bodies are read now; attachments are only described (fetched later by section). */
    private function walk($m, int $uid, object $part, string $prefix, array &$parts, int $depth = 0): void
    {
        if ($depth > 12) {
            return;
        }
        $type = (int) $part->type;   // 0 text, 1 multipart, 2 message, 3 application, 4 audio, 5 image, 6 video
        if ($type === 1 && !empty($part->parts)) {
            foreach ($part->parts as $i => $sub) {
                $this->walk($m, $uid, $sub, $prefix === '' ? (string) ($i + 1) : $prefix . '.' . ($i + 1), $parts, $depth + 1);
            }
            return;
        }
        $section = $prefix === '' ? '1' : $prefix;
        $params = [];
        foreach (array_merge((array) ($part->parameters ?? []), (array) ($part->dparameters ?? [])) as $p) {
            $params[strtolower((string) $p->attribute)] = self::decodeHeader((string) $p->value);
        }
        $filename = $params['filename'] ?? ($params['name'] ?? '');
        $disposition = strtolower((string) ($part->disposition ?? ''));
        $subtype = strtolower((string) ($part->subtype ?? ''));
        $inlineText = $type === 0 && ($subtype === 'plain' || $subtype === 'html') && $disposition !== 'attachment';
        if (!$inlineText) {
            $name = $filename !== '' ? $filename : ($type === 2 ? 'forwarded-message-' . $section . '.eml' : 'part-' . $section . '.' . ($subtype ?: 'bin'));
            $parts['attachments'][] = ['section' => $section, 'name' => $name, 'encoding' => (int) $part->encoding, 'mime' => strtolower(self::typeName($type) . '/' . $subtype), 'bytes' => (int) ($part->bytes ?? 0)];
            return;
        }
        $raw = (string) @imap_fetchbody($m, $uid, $section, FT_UID | FT_PEEK);
        $body = self::decodeBody($raw, (int) $part->encoding, $params['charset'] ?? 'UTF-8');
        if ($subtype === 'plain' && $parts['text'] === '') {
            $parts['text'] = $body;
        } elseif ($subtype === 'html' && $parts['html'] === '') {
            $parts['html'] = $body;
        }
    }

    private static function typeName(int $t): string
    {
        return ['text', 'multipart', 'message', 'application', 'audio', 'image', 'video', 'model', 'other'][$t] ?? 'application';
    }

    private static function decodeBody(string $raw, int $encoding, string $charset): string
    {
        if ($encoding === 3) {
            $raw = (string) base64_decode($raw, false);
        } elseif ($encoding === 4) {
            $raw = quoted_printable_decode($raw);
        }
        $cs = strtoupper(trim($charset));
        if ($cs !== '' && $cs !== 'UTF-8' && $cs !== 'US-ASCII' && $cs !== 'DEFAULT') {
            $conv = @mb_convert_encoding($raw, 'UTF-8', $cs === 'ISO-2022-JP' ? 'ISO-2022-JP-MS' : $cs);
            if (is_string($conv) && $conv !== '') {
                $raw = $conv;
            }
        }
        return str_replace("\r\n", "\n", MailServer::utf8($raw));
    }

    public static function htmlToText(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        $h = preg_replace('/<(script|style)\b.*?<\/\1>/is', '', $html);
        $h = preg_replace('/<br\s*\/?>|<\/p>|<\/div>|<\/tr>|<\/li>|<\/h[1-6]>/i', "\n", (string) $h);
        $t = html_entity_decode(strip_tags((string) $h), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace("/[ \t]*\n[ \t]*(\n[ \t]*)+/", "\n\n", (string) preg_replace('/[ \t]+/', ' ', $t)));
    }

    private function storeAttachment(int $mailId, int $uid, array $a): void
    {
        // fetched only now, by section, with PEEK so the message keeps its flags
        $data = $this->fetchSection($uid, $a['section'], (int) $a['encoding']);
        if ($data === null || $data === '') {
            return;
        }
        $ext = strtolower((string) pathinfo($a['name'], PATHINFO_EXTENSION));
        $dir = self::dir($mailId);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }
        $base = self::dir();
        if (!is_file($base . DIRECTORY_SEPARATOR . '.htaccess')) {
            @file_put_contents($base . DIRECTORY_SEPARATOR . '.htaccess', "<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n<IfModule !authz_core_module>\n    Deny from all\n</IfModule>\n");
        }
        $stored = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . ($ext !== '' ? '.' . $ext : '.bin');
        if (@file_put_contents($dir . DIRECTORY_SEPARATOR . $stored, $data) === false) {
            return;
        }
        $this->db->query('INSERT INTO tf_mail_in_attachment (mail_id, file, original_name, mime, bytes) VALUES (?,?,?,?,?)', [$mailId, $stored, mb_substr($a['name'], 0, 255), mb_substr($a['mime'], 0, 120), strlen($data)]);
    }

    /** @var resource|\IMAP\Connection|null the connection import() is working on */
    private $conn = null;

    private function fetchSection(int $uid, string $section, int $encoding): ?string
    {
        if ($this->conn === null) {
            return null;
        }
        $raw = (string) @imap_fetchbody($this->conn, $uid, $section, FT_UID | FT_PEEK);
        if ($raw === '') {
            return null;
        }
        if ($encoding === 3) {
            return (string) base64_decode($raw, false);
        }
        if ($encoding === 4) {
            return quoted_printable_decode($raw);
        }
        return $raw;
    }

    // ------------------------------------------------------------------ filing (the strict order)

    /** Try to file one stored message. Returns ['status' => matched|unmatched, 'reason' => …]. */
    public function file(int $mailId): array
    {
        $r = $this->db->query('SELECT * FROM tf_mail_in WHERE id = ?', [$mailId])->getRowArray();
        if (!$r || $r['status'] === 'matched' || $r['status'] === 'discarded') {
            return ['status' => $r['status'] ?? 'unmatched', 'reason' => 'already handled'];
        }
        $hit = $this->match($r);
        if ($hit === null) {
            $this->db->query("UPDATE tf_mail_in SET status = 'unmatched' WHERE id = ?", [$mailId]);
            return ['status' => 'unmatched', 'reason' => 'no certain reference'];
        }
        $this->attachTo($mailId, (int) $hit['intake_id'], $hit['comm_id'], $hit['reason'], 'auto');
        return ['status' => 'matched', 'reason' => $hit['reason']];
    }

    /** The strict order. Returns ['intake_id','comm_id'|null,'reason'] or null. Nothing here looks at the sender or a similar subject. */
    public function match(array $r): ?array
    {
        // 1. In-Reply-To / References → a Message-ID this system sent
        $ids = [];
        foreach ([(string) ($r['in_reply_to'] ?? ''), (string) ($r['references_hdr'] ?? '')] as $hdr) {
            if (preg_match_all('/<[^<>\s]+>/', $hdr, $mm)) {
                foreach ($mm[0] as $id) {
                    $ids[$id] = true;
                }
            }
        }
        if ($ids) {
            $place = implode(',', array_fill(0, count($ids), '?'));
            $c = $this->db->query('SELECT id, intake_id FROM tf_comm WHERE message_id IN (' . $place . ') AND removed_at IS NULL ORDER BY id DESC LIMIT 1', array_keys($ids))->getRowArray();
            if ($c) {
                return ['intake_id' => (int) $c['intake_id'], 'comm_id' => (int) $c['id'], 'reason' => 'In-Reply-To / References names Message-ID of e-mail #' . $c['id'] . ' sent from the system'];
            }
        }
        // 2. the system's reference tag in the subject or the body
        $hay = (string) ($r['subject'] ?? '') . "\n" . (string) ($r['body_text'] ?? '');
        if (preg_match_all(self::TAG_RE, $hay, $mm)) {
            $found = [];
            foreach ($mm[1] as $tag) {
                $tag = strtoupper(preg_replace('/\s+/', ' ', trim($tag)));
                if (preg_match('/^INQ\s*(\d+)$/', $tag, $n)) {
                    $it = $this->db->query('SELECT id FROM tf_intake WHERE inquiry_no = ? AND archived_at IS NULL', ['INQ ' . (int) $n[1]])->getRowArray();
                    if ($it) {
                        $found[(int) $it['id']] = 'reference tag [AVH:INQ ' . (int) $n[1] . ']';
                    }
                } else {
                    $t = $this->db->query('SELECT intake_id FROM tf_tour WHERE tour_no = ?', [$tag])->getRowArray();
                    if ($t) {
                        $found[(int) $t['intake_id']] = 'reference tag [AVH:' . $tag . ']';
                    }
                }
            }
            if (count($found) === 1) {
                $iid = (int) array_key_first($found);
                return ['intake_id' => $iid, 'comm_id' => null, 'reason' => $found[$iid]];
            }
            if (count($found) > 1) {
                return null;   // two different records named: a person decides
            }
        }
        // 3. the conversation key quoted from an e-mail sent from here
        if (preg_match_all(self::REF_RE, $hay, $mm)) {
            $keys = array_values(array_unique(array_map('strtolower', $mm[1])));
            $place = implode(',', array_fill(0, count($keys), '?'));
            $rows = $this->db->query("SELECT id, intake_id, thread_key FROM tf_comm WHERE thread_key IN ({$place}) AND direction = 'out' AND removed_at IS NULL ORDER BY id DESC", $keys)->getResultArray();
            $byIntake = [];
            foreach ($rows as $c) {
                $byIntake[(int) $c['intake_id']] = $byIntake[(int) $c['intake_id']] ?? (int) $c['id'];
            }
            if (count($byIntake) === 1) {
                $iid = (int) array_key_first($byIntake);
                return ['intake_id' => $iid, 'comm_id' => $byIntake[$iid], 'reason' => 'conversation key AVH-REF quoted from e-mail #' . $byIntake[$iid]];
            }
        }
        return null;
    }

    /** File a stored message on a record: the Communications line, its attachments, the thread. $how: auto | staff */
    public function attachTo(int $mailId, int $intakeId, ?int $replyToComm, string $reason, string $how): array
    {
        $r = $this->db->query('SELECT * FROM tf_mail_in WHERE id = ?', [$mailId])->getRowArray();
        if (!$r) {
            throw new \RuntimeException('Message not found.');
        }
        if ($r['status'] === 'matched' && !empty($r['comm_id'])) {
            throw new \RuntimeException('This message is already filed on a record.');
        }
        if (!$this->db->query('SELECT id FROM tf_intake WHERE id = ?', [$intakeId])->getRowArray()) {
            throw new \RuntimeException('Inquiry not found.');
        }
        $comms = new Comms($this->db, $this->user, $this->userId, $this->name);
        $tag = '';
        $body = (string) ($r['body_text'] ?? '');
        $c = $comms->add($intakeId, ['direction' => 'in', 'channel' => 'email', 'source' => 'system', 'ref_table' => 'tf_mail_in', 'ref_id' => $mailId, 'tag' => $tag,
            'occurred_at' => $r['received_at'], 'from_addr' => $r['from_addr'], 'to_addr' => $r['to_addr'], 'cc_addr' => $r['cc_addr'], 'subject' => $r['subject'] ?: '(no subject)', 'body' => $body !== '' ? $body : '(no text in the message)',
            'allow_empty' => true, 'message_id' => $r['message_id'], 'in_reply_to' => $replyToComm,
            'links' => [['kind' => 'mail_in', 'id' => $mailId, 'label' => ($how === 'auto' ? 'filed automatically — ' : 'filed by ' . ($this->name ?: $this->user) . ' — ') . $reason]]]);
        foreach ($this->db->query('SELECT * FROM tf_mail_in_attachment WHERE mail_id = ?', [$mailId])->getResultArray() as $a) {
            $path = self::dir($mailId) . DIRECTORY_SEPARATOR . $a['file'];
            if (is_file($path)) {
                try {
                    $comms->attach((int) $c['id'], ['tmp' => $path, 'name' => $a['original_name'], 'size' => (int) $a['bytes'], 'mime' => $a['mime']]);
                } catch (\Throwable $e) {
                    log_message('error', 'Incoming attachment not copied to the record: ' . $e->getMessage());
                }
            }
        }
        $this->db->query("UPDATE tf_mail_in SET status = 'matched', intake_id = ?, comm_id = ?, match_reason = ?, matched_by = ?, matched_at = ? WHERE id = ?", [$intakeId, (int) $c['id'], mb_substr(($how === 'auto' ? 'auto: ' : 'staff: ') . $reason, 0, 255), $this->user, $this->now(), $mailId]);
        (new Activity($this->db, $this->user, $this->userId, $this->name))->record('comm', (int) $c['id'], $intakeId, $c['tour_id'] !== null ? (int) $c['tour_id'] : null, $how === 'auto' ? 'mail_in_filed' : 'mail_in_attached',
            'Incoming e-mail "' . mb_substr((string) $r['subject'], 0, 100) . '" from ' . mb_substr((string) $r['from_addr'], 0, 80) . ($how === 'auto' ? ' filed automatically — ' : ' attached by ' . ($this->name ?: $this->user) . ' — ') . $reason);
        return $c;
    }

    public function discard(int $mailId, string $why): void
    {
        if (mb_strlen(trim($why)) < 5) {
            throw new \InvalidArgumentException('Give the reason (at least five characters).');
        }
        $r = $this->db->query('SELECT id, status, subject, from_addr FROM tf_mail_in WHERE id = ?', [$mailId])->getRowArray();
        if (!$r || $r['status'] === 'matched') {
            throw new \RuntimeException('That message cannot be discarded' . ($r ? ' (it is filed on a record)' : '') . '.');
        }
        $this->db->query("UPDATE tf_mail_in SET status = 'discarded', note = ?, matched_by = ?, matched_at = ? WHERE id = ?", [mb_substr(trim($why), 0, 500), $this->user, $this->now(), $mailId]);
        Activity::master($this->db, $this->user, 'inbox', $mailId, 'Incoming e-mail "' . mb_substr((string) $r['subject'], 0, 100) . '" from ' . mb_substr((string) $r['from_addr'], 0, 80) . ' discarded from the queue — ' . mb_substr(trim($why), 0, 200) . ' (the message stays on file and in the mailbox)', [], $this->userId, $this->name);
    }

    /** The queue and the recent history. */
    public function queue(string $status = 'unmatched', int $limit = 100): array
    {
        $w = $status === 'all' ? '1=1' : 'm.status = ?';
        $rows = $this->db->query("SELECT m.id, m.uid, m.message_id, m.in_reply_to IS NOT NULL AS has_reply_hdr, m.from_addr, m.to_addr, m.subject, LEFT(IFNULL(m.body_text, ''), 400) AS preview, m.received_at, m.size_bytes, m.status, m.note, m.intake_id, m.comm_id, m.match_reason, m.matched_by, m.matched_at, m.imported_at, m.draft_intake_id,
                i.inquiry_no, t.tour_no, (SELECT COUNT(*) FROM tf_mail_in_attachment a WHERE a.mail_id = m.id) AS attachments
             FROM tf_mail_in m LEFT JOIN tf_intake i ON i.id = m.intake_id LEFT JOIN tf_tour t ON t.intake_id = m.intake_id
             WHERE {$w} ORDER BY m.received_at DESC, m.id DESC LIMIT " . (int) $limit, $status === 'all' ? [] : [$status])->getResultArray();
        foreach ($rows as &$r) {
            $r['tags'] = preg_match_all(self::TAG_RE, (string) $r['subject'] . ' ' . (string) $r['preview'], $mm) ? array_values(array_unique($mm[1])) : [];
            $r['looks_like_request'] = CommsQuoteDraft::looksLikeRequest((string) $r['subject'], (string) $r['preview'])['score'] >= 2;
        }
        return $rows;
    }

    public function message(int $mailId): ?array
    {
        $r = $this->db->query('SELECT * FROM tf_mail_in WHERE id = ?', [$mailId])->getRowArray();
        if (!$r) {
            return null;
        }
        $r['attachments'] = $this->db->query('SELECT id, original_name, mime, bytes FROM tf_mail_in_attachment WHERE mail_id = ? ORDER BY id', [$mailId])->getResultArray();
        unset($r['body_html']);
        return $r;
    }

    public function attachmentPath(int $attId): ?array
    {
        $a = $this->db->query('SELECT * FROM tf_mail_in_attachment WHERE id = ?', [$attId])->getRowArray();
        if (!$a) {
            return null;
        }
        $a['path'] = self::dir((int) $a['mail_id']) . DIRECTORY_SEPARATOR . $a['file'];
        return $a;
    }

    public function counts(): array
    {
        $out = ['unmatched' => 0, 'new' => 0, 'matched' => 0, 'discarded' => 0, 'skipped' => 0];
        try {
            foreach ($this->db->query('SELECT status, COUNT(*) n FROM tf_mail_in GROUP BY status')->getResultArray() as $r) {
                $out[$r['status']] = (int) $r['n'];
            }
        } catch (\Throwable $e) {
        }
        return $out;
    }

}
