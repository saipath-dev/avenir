<?php

namespace App\Libraries\TourFlow\Mail;

/**
 * A small IMAP4rev1 client over TLS (tf-mail-r1) — just what reading a hotel mailbox needs: log in, open the inbox
 * read-only, list the messages newer than the last one read (by UID), and fetch each one whole WITHOUT marking it read
 * (BODY.PEEK), so staff reading the same mailbox in their own mail program see nothing change. The server's certificate
 * is always checked. Plain PHP streams: it does not need the imap extension (gone from PHP 8.4).
 *
 * The password is only ever sent inside the TLS connection, never written to a log or an error message.
 */
final class ImapClient
{
    /** @var resource|null */
    private $s = null;
    private int $n = 0;
    private string $host;
    private int $port;
    private ?string $cafile;
    private int $timeout;

    public function __construct(string $host, int $port = 993, ?string $cafile = null, int $timeout = 25)
    {
        $this->host = $host;
        $this->port = $port;
        $this->cafile = $cafile !== null && is_file($cafile) ? $cafile : null;
        $this->timeout = $timeout;
    }

    public function connect(): string
    {
        $ssl = ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $this->host, 'SNI_enabled' => true];
        if ($this->cafile) {
            $ssl['cafile'] = $this->cafile;
        }
        $ctx = stream_context_create(['ssl' => $ssl]);
        $s = @stream_socket_client('ssl://' . $this->host . ':' . $this->port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $ctx);
        if (!$s) {
            throw new \RuntimeException('Could not open a secure connection to ' . $this->host . ':' . $this->port . ' (' . ($errstr ?: 'error ' . $errno) . ').');
        }
        stream_set_timeout($s, $this->timeout);
        $this->s = $s;
        $greeting = $this->line();
        if (!preg_match('/^\* (OK|PREAUTH)/i', $greeting)) {
            $this->close();
            throw new \RuntimeException('The mail server did not greet as an IMAP server: ' . mb_substr($greeting, 0, 120));
        }
        return $greeting;
    }

    public function login(string $user, string $password): void
    {
        $tag = $this->tag();
        $this->write($tag . ' LOGIN ' . $this->astring($user) . ' ');
        $this->writeArg($password, $tag);
        $res = $this->until($tag);
        if (!preg_match('/^' . $tag . ' OK/i', $res['tagged'])) {
            throw new \RuntimeException('The mail server refused the login for ' . $user . ': ' . mb_substr(preg_replace('/^' . $tag . ' (NO|BAD)\s*/i', '', $res['tagged']), 0, 160));
        }
    }

    /** Open a mailbox read-only. @return array{exists:int, uidvalidity:?int, uidnext:?int} */
    public function examine(string $mailbox = 'INBOX'): array
    {
        $res = $this->cmd('EXAMINE ' . $this->astring($mailbox));
        $out = ['exists' => 0, 'uidvalidity' => null, 'uidnext' => null];
        foreach ($res['untagged'] as $l) {
            if (preg_match('/^\* (\d+) EXISTS/i', $l, $m)) {
                $out['exists'] = (int) $m[1];
            } elseif (preg_match('/\[UIDVALIDITY (\d+)\]/i', $l, $m)) {
                $out['uidvalidity'] = (int) $m[1];
            } elseif (preg_match('/\[UIDNEXT (\d+)\]/i', $l, $m)) {
                $out['uidnext'] = (int) $m[1];
            }
        }
        return $out;
    }

    /** The UIDs from $from upwards, oldest first. @return int[] */
    public function uidsFrom(int $from): array
    {
        $res = $this->cmd('UID SEARCH UID ' . max(1, $from) . ':*');
        $out = [];
        foreach ($res['untagged'] as $l) {
            if (preg_match('/^\* SEARCH\b(.*)$/i', $l, $m)) {
                foreach (preg_split('/\s+/', trim($m[1])) ?: [] as $u) {
                    if (ctype_digit($u) && (int) $u >= $from) {
                        $out[] = (int) $u;           // "n:*" also answers the highest UID when none is newer
                    }
                }
            }
        }
        sort($out);
        return array_values(array_unique($out));
    }

    /** One message as the server holds it (RFC 5322), left unread. */
    public function fetchRaw(int $uid): ?string
    {
        $res = $this->cmd('UID FETCH ' . $uid . ' BODY.PEEK[]');
        return $res['literals'][0] ?? null;
    }

    public function logout(): void
    {
        if ($this->s) {
            try {
                $this->cmd('LOGOUT');
            } catch (\Throwable $e) {
                // closing anyway
            }
        }
        $this->close();
    }

    public function close(): void
    {
        if ($this->s) {
            @fclose($this->s);
        }
        $this->s = null;
    }

    // ---- protocol --------------------------------------------------------------------------------------------------

    private function tag(): string
    {
        return 'A' . (++$this->n);
    }

    /** @return array{tagged:string, untagged:string[], literals:string[]} */
    private function cmd(string $command): array
    {
        $tag = $this->tag();
        $this->write($tag . ' ' . $command . "\r\n");
        $res = $this->until($tag);
        if (!preg_match('/^' . $tag . ' OK/i', $res['tagged'])) {
            throw new \RuntimeException('The mail server answered "' . mb_substr($res['tagged'], 0, 160) . '" to ' . strtok($command, ' ') . '.');
        }
        return $res;
    }

    private function astring(string $v): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $v) . '"';
    }

    /** The last argument of a command: quoted when plain, else as a literal (after the server's go-ahead). */
    private function writeArg(string $v, string $tag): void
    {
        if (preg_match('/^[\x20-\x7e]*$/', $v)) {
            $this->write($this->astring($v) . "\r\n");
            return;
        }
        $this->write('{' . strlen($v) . "}\r\n");
        $go = $this->line();
        if (!str_starts_with($go, '+')) {
            throw new \RuntimeException('The mail server did not accept the login (' . mb_substr($go, 0, 80) . ').');
        }
        $this->write($v . "\r\n");
    }

    private function write(string $data): void
    {
        if (!$this->s || @fwrite($this->s, $data) === false) {
            throw new \RuntimeException('The connection to the mail server was lost.');
        }
    }

    private function line(): string
    {
        $l = $this->s ? fgets($this->s) : false;
        if ($l === false) {
            $meta = $this->s ? stream_get_meta_data($this->s) : [];
            throw new \RuntimeException(!empty($meta['timed_out']) ? 'The mail server did not answer in time.' : 'The connection to the mail server was closed.');
        }
        return rtrim($l, "\r\n");
    }

    private function read(int $bytes): string
    {
        $out = '';
        while (strlen($out) < $bytes) {
            $chunk = fread($this->s, $bytes - strlen($out));
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->s);
                if (!empty($meta['timed_out']) || feof($this->s)) {
                    throw new \RuntimeException('The mail server stopped in the middle of a message.');
                }
                continue;
            }
            $out .= $chunk;
        }
        return $out;
    }

    /** @return array{tagged:string, untagged:string[], literals:string[]} */
    private function until(string $tag): array
    {
        $untagged = [];
        $literals = [];
        while (true) {
            $l = $this->line();
            // a line may end in a literal: {n} then n bytes, then the rest of the line
            while (preg_match('/\{(\d+)\}$/', $l, $m)) {
                $literals[] = $this->read((int) $m[1]);
                $l = substr($l, 0, -strlen($m[0])) . '{…}' . $this->line();
            }
            if (str_starts_with($l, $tag . ' ')) {
                return ['tagged' => $l, 'untagged' => $untagged, 'literals' => $literals];
            }
            $untagged[] = $l;
        }
    }
}
