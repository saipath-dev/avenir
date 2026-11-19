<?php

namespace App\Libraries\TourFlow;

/**
 * tf-imaplite-r1 (19 Nov 2026) — reading the incoming mailbox WITHOUT the PHP imap extension.
 *
 * The UAT server has no imap extension, so CommsInbox could not open the mailbox at all. This class speaks the small part
 * of IMAP the inbox needs over an ordinary (SSL) socket, and parses a message's MIME itself:
 *
 *   open → EXAMINE (read-only: nothing is flagged, moved or deleted) → UID SEARCH → UID FETCH BODY.PEEK[] → LOGOUT
 *
 * It is used only when the extension is missing; where the extension exists CommsInbox works exactly as before.
 * Nothing here stores a password: it is handed in for the login and not kept.
 */
class ImapLite
{
    /** @var resource|null */
    private $fp = null;
    private int $tag = 0;
    public int $exists = 0;
    public string $uidvalidity = '';

    public static function available(): bool
    {
        return function_exists('stream_socket_client');
    }

    /** @throws \RuntimeException with a plain reason when the server cannot be reached or refuses the login */
    public static function open(string $host, int $port, string $secure, string $email, string $password, string $folder = 'INBOX', int $timeout = 20): self
    {
        $me = new self();
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'SNI_enabled' => true, 'peer_name' => $host]]);
        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new \RuntimeException('could not connect to ' . $host . ':' . $port . ($errstr !== '' ? ' (' . $errstr . ')' : ''));
        }
        stream_set_timeout($fp, 60);
        $me->fp = $fp;
        $hello = (string) fgets($fp, 8192);
        if (stripos($hello, '* OK') !== 0 && stripos($hello, '* PREAUTH') !== 0) {
            $me->close();
            throw new \RuntimeException('the server did not greet as an IMAP server');
        }
        if ($secure === 'tls') {
            $r = $me->cmd('STARTTLS');
            if (!$r['ok'] || !@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $me->close();
                throw new \RuntimeException('the server refused an encrypted connection (STARTTLS)');
            }
        }
        $r = $me->cmd('LOGIN ' . self::quote($email) . ' ' . self::quote($password));
        if (!$r['ok']) {
            $me->close();
            throw new \RuntimeException('the login was refused' . ($r['text'] !== '' ? ' (' . $r['text'] . ')' : ''));
        }
        $r = $me->cmd('EXAMINE ' . self::quote($folder));
        if (!$r['ok']) {
            $me->close();
            throw new \RuntimeException('the folder "' . $folder . '" could not be opened' . ($r['text'] !== '' ? ' (' . $r['text'] . ')' : ''));
        }
        foreach ($r['lines'] as $l) {
            if (preg_match('/^\* (\d+) EXISTS/i', $l, $m)) {
                $me->exists = (int) $m[1];
            }
            if (preg_match('/UIDVALIDITY (\d+)/i', $l, $m)) {
                $me->uidvalidity = $m[1];
            }
        }
        if ($me->uidvalidity === '' && preg_match('/UIDVALIDITY (\d+)/i', $r['text'], $m)) {
            $me->uidvalidity = $m[1];
        }
        return $me;
    }

    private static function quote(string $s): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $s) . '"';
    }

    /** One command. Returns ok, the tagged line's text, the untagged lines, and every literal the server sent. */
    private function cmd(string $c): array
    {
        $tag = 'A' . str_pad((string) (++$this->tag), 4, '0', STR_PAD_LEFT);
        if (@fwrite($this->fp, $tag . ' ' . $c . "\r\n") === false) {
            throw new \RuntimeException('the connection to the mail server was lost');
        }
        $lines = [];
        $lits = [];
        while (true) {
            $line = fgets($this->fp, 65536);
            if ($line === false) {
                $meta = stream_get_meta_data($this->fp);
                throw new \RuntimeException(!empty($meta['timed_out']) ? 'the mail server did not answer in time' : 'the mail server closed the connection');
            }
            // a literal: "{n}" at the end of the line, then exactly n bytes, then the rest of that line
            while (preg_match('/\{(\d+)\}\r?\n$/', $line, $m)) {
                $n = (int) $m[1];
                $buf = '';
                while (strlen($buf) < $n) {
                    $chunk = fread($this->fp, min(65536, $n - strlen($buf)));
                    if ($chunk === false || $chunk === '') {
                        throw new \RuntimeException('the mail server stopped in the middle of a message');
                    }
                    $buf .= $chunk;
                }
                $lits[] = $buf;
                $rest = fgets($this->fp, 65536);
                $line = substr($line, 0, -strlen($m[0])) . '{literal}' . ($rest === false ? '' : $rest);
            }
            $line = rtrim($line, "\r\n");
            if (strpos($line, $tag . ' ') === 0) {
                $st = substr($line, strlen($tag) + 1);
                return ['ok' => stripos($st, 'OK') === 0, 'text' => trim((string) preg_replace('/^(OK|NO|BAD)\s*/i', '', $st)), 'lines' => $lines, 'literals' => $lits];
            }
            $lines[] = $line;
        }
    }

    /** UIDs above $after, ascending. */
    public function uidsAfter(int $after): array
    {
        if ($this->exists < 1) {
            return [];
        }
        $r = $this->cmd('UID SEARCH UID ' . ($after + 1) . ':*');
        $out = [];
        foreach ($r['lines'] as $l) {
            if (stripos($l, '* SEARCH') === 0) {
                foreach (preg_split('/\s+/', trim(substr($l, 8))) ?: [] as $u) {
                    if (ctype_digit($u) && (int) $u > $after) {
                        $out[] = (int) $u;
                    }
                }
            }
        }
        sort($out);
        return $out;
    }

    public function size(int $uid): int
    {
        $r = $this->cmd('UID FETCH ' . $uid . ' (RFC822.SIZE)');
        foreach ($r['lines'] as $l) {
            if (preg_match('/RFC822\.SIZE (\d+)/i', $l, $m)) {
                return (int) $m[1];
            }
        }
        return 0;
    }

    /** The whole message, unread flags untouched (BODY.PEEK). Null when the server gave nothing. */
    public function raw(int $uid): ?string
    {
        $r = $this->cmd('UID FETCH ' . $uid . ' (BODY.PEEK[])');
        return $r['literals'][0] ?? null;
    }

    public function close(): void
    {
        if ($this->fp) {
            try {
                @fwrite($this->fp, 'Z999 LOGOUT' . "\r\n");
            } catch (\Throwable $e) {
            }
            @fclose($this->fp);
            $this->fp = null;
        }
    }

    // ------------------------------------------------------------------------------------------------ MIME

    /**
     * A raw message as the inbox needs it: subject, from, to, cc, reply_to, date, message_id, in_reply_to, references,
     * text, html, attachments [{name, mime, data}].
     */
    public static function parse(string $raw): array
    {
        [$h, $body] = self::split($raw);
        $out = ['subject' => self::word($h['subject'] ?? ''), 'from' => self::word($h['from'] ?? ''), 'to' => self::word($h['to'] ?? ''), 'cc' => self::word($h['cc'] ?? ''),
                'reply_to' => self::word($h['reply-to'] ?? ''), 'date' => trim((string) ($h['date'] ?? '')), 'message_id' => trim((string) ($h['message-id'] ?? '')),
                'in_reply_to' => trim((string) ($h['in-reply-to'] ?? '')), 'references' => trim((string) preg_replace('/\s+/', ' ', (string) ($h['references'] ?? ''))),
                'text' => '', 'html' => '', 'attachments' => []];
        self::part($h, $body, $out, 0);
        return $out;
    }

    /** headers (lower-case name → unfolded value, the first of each) and the body */
    private static function split(string $raw): array
    {
        $pos = strpos($raw, "\r\n\r\n");
        $gap = 4;
        if ($pos === false) {
            $pos = strpos($raw, "\n\n");
            $gap = 2;
        }
        $head = $pos === false ? $raw : substr($raw, 0, $pos);
        $body = $pos === false ? '' : substr($raw, $pos + $gap);
        $head = (string) preg_replace('/\r?\n[ \t]+/', ' ', $head);
        $h = [];
        foreach (preg_split('/\r?\n/', $head) ?: [] as $line) {
            $c = strpos($line, ':');
            if ($c === false) {
                continue;
            }
            $k = strtolower(trim(substr($line, 0, $c)));
            if (!isset($h[$k])) {
                $h[$k] = trim(substr($line, $c + 1));
            }
        }
        return [$h, $body];
    }

    private static function word(string $v): string
    {
        if ($v === '' || strpos($v, '=?') === false) {
            return self::utf8($v, '');
        }
        $d = function_exists('iconv_mime_decode') ? @iconv_mime_decode($v, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') : false;
        if ($d === false || $d === '') {
            $d = function_exists('mb_decode_mimeheader') ? @mb_decode_mimeheader($v) : $v;
        }
        return (string) $d;
    }

    private static function utf8(string $s, string $charset): string
    {
        $charset = strtoupper(trim($charset));
        if ($charset !== '' && $charset !== 'UTF-8' && $charset !== 'US-ASCII') {
            $c = @mb_convert_encoding($s, 'UTF-8', $charset);
            if ($c === false || $c === '') {
                $c = function_exists('iconv') ? @iconv($charset, 'UTF-8//IGNORE', $s) : false;
            }
            if ($c !== false && $c !== '') {
                return (string) $c;
            }
        }
        return mb_check_encoding($s, 'UTF-8') ? $s : (string) @mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
    }

    /** "type/sub; a=b; c="d"" → [type, [a=>b, c=>d]] (RFC 2231 name*=charset''value is decoded) */
    private static function params(string $v): array
    {
        $bits = explode(';', $v, 2);
        $p = [];
        if (isset($bits[1]) && preg_match_all('/([\w\-\*]+)\s*=\s*(?:"((?:[^"\\\\]|\\\\.)*)"|([^;]*))/', $bits[1], $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $k = strtolower($x[1]);
                $val = $x[2] !== '' ? stripcslashes($x[2]) : trim($x[3] ?? '');
                if (substr($k, -1) === '*') {
                    $k = rtrim(rtrim($k, '*'), '0123456789*');
                    $k = rtrim($k, '*');
                    if (preg_match("/^([^']*)'[^']*'(.*)$/", $val, $q)) {
                        $val = self::utf8(rawurldecode($q[2]), $q[1]);
                    } else {
                        $val = rawurldecode($val);
                    }
                    $p[$k] = ($p[$k] ?? '') . $val;
                } elseif (!isset($p[$k])) {
                    $p[$k] = $val;
                }
            }
        }
        return [strtolower(trim($bits[0])), $p];
    }

    private static function part(array $h, string $body, array &$out, int $depth): void
    {
        [$type, $tp] = self::params((string) ($h['content-type'] ?? 'text/plain'));
        if ($type === '') {
            $type = 'text/plain';
        }
        if (strpos($type, 'multipart/') === 0 && !empty($tp['boundary']) && $depth < 12) {
            $chunks = explode('--' . $tp['boundary'], $body);
            array_shift($chunks);   // the preamble
            foreach ($chunks as $c) {
                if (strpos($c, '--') === 0) {
                    break;          // the closing boundary
                }
                $c = (string) preg_replace('/^\r?\n/', '', $c);
                $c = (string) preg_replace('/\r?\n$/', '', $c);
                [$ph, $pb] = self::split($c);
                self::part($ph, $pb, $out, $depth + 1);
            }
            return;
        }
        [$disp, $dp] = self::params((string) ($h['content-disposition'] ?? ''));
        $enc = strtolower(trim((string) ($h['content-transfer-encoding'] ?? '')));
        $data = $enc === 'base64' ? (string) base64_decode((string) preg_replace('/\s+/', '', $body)) : ($enc === 'quoted-printable' ? quoted_printable_decode($body) : $body);
        $name = self::word((string) ($dp['filename'] ?? ($tp['name'] ?? '')));
        $isText = $type === 'text/plain' || $type === 'text/html';
        if ($disp === 'attachment' || $name !== '' || !$isText) {
            if ($type === 'message/rfc822' && $name === '') {
                $name = 'forwarded-message.eml';
            }
            if ($name === '' && $disp !== 'attachment' && strpos($type, 'image/') !== 0 && $type !== 'application/pdf') {
                return;   // a nameless non-text part that is not a file (a calendar stub, a signature block)
            }
            $out['attachments'][] = ['name' => $name !== '' ? $name : 'attachment.' . (explode('/', $type)[1] ?? 'bin'), 'mime' => $type, 'data' => $data];
            return;
        }
        $txt = self::utf8($data, (string) ($tp['charset'] ?? ''));
        if ($type === 'text/plain' && $out['text'] === '') {
            $out['text'] = $txt;
        } elseif ($type === 'text/html' && $out['html'] === '') {
            $out['html'] = $txt;
        }
    }
}
