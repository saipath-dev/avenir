<?php

namespace App\Libraries\TourFlow;

/**
 * tf-resdesk-r2 (12 Nov 2026) — the local TEST MAILBOX.
 *
 * On a development copy every e-mail the system would send (hotels, restaurants, transport, agents) is caught here
 * instead of going to a mail server: it is saved, with its attachments, under writable/test_mailbox/ and can be read
 * on Hotel Bookings → Test mailbox. Nothing leaves the computer.
 *
 * It is ON only when BOTH hold:
 *   1. the .env line  tourflow.testMailbox = on
 *   2. the request is made on this computer (host localhost / 127.0.0.1 / ::1), or from the command line.
 * The live and UAT sites answer on system.avenirholidays.com, so a copied .env line cannot switch it on there.
 *
 * A failure can be rehearsed: an address listed in  tourflow.testMailboxFail = a@b.com, @hotel.lk  (an address, or a
 * domain starting with @) is refused as a mail server would refuse it; nothing is saved as sent.
 */
final class TestMailbox
{
    public const DIR = 'test_mailbox';

    private static function envv(string $k): string
    {
        $v = getenv($k);
        if ($v === false || $v === '') {
            $v = $_ENV[$k] ?? ($_SERVER[$k] ?? '');
        }
        return (string) $v;
    }

    public static function active(): bool
    {
        $flag = strtolower(trim(self::envv('tourflow.testMailbox')));
        if (!in_array($flag, ['on', '1', 'true', 'yes'], true)) {
            return false;
        }
        if (PHP_SAPI === 'cli') {
            return true;
        }
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '')));
        $host = preg_replace('/:\d+$/', '', $host);
        return in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true);
    }

    /** @return string[] the addresses / @domains to refuse */
    public static function failList(): array
    {
        return array_values(array_filter(array_map(static fn ($x) => strtolower(trim($x)), preg_split('/[;,\s]+/', self::envv('tourflow.testMailboxFail')) ?: [])));
    }

    private static function root(): string
    {
        return rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . self::DIR;
    }

    /**
     * Keep one message. Returns 200 as SendController does, or the refusal text.
     *
     * @param string[]|null $cc
     * @param array $attachments paths, or ['path' => …, 'name' => …]
     * @return int|string
     */
    public static function deliver($to, $subject, $body, $cc = null, $attachments = [], ?string $from = null)
    {
        $to = trim((string) $to);
        $cc = array_values(array_filter(array_map('trim', (array) ($cc ?? []))));
        try {
            foreach (MailServer::alwaysCc() as $always) {
                if (!in_array(strtolower($always), array_map('strtolower', array_merge([$to], $cc)), true)) {
                    $cc[] = $always;
                }
            }
        } catch (\Throwable $e) {
        }
        foreach (array_merge([$to], $cc) as $addr) {
            $a = strtolower($addr);
            foreach (self::failList() as $f) {
                if ($f !== '' && ($a === $f || ($f[0] === '@' && str_ends_with($a, $f)))) {
                    return 'Not sent: the mail server refused ' . $addr . ' (test mailbox — a rehearsed failure, tourflow.testMailboxFail).';
                }
            }
        }
        $id  = date('Ymd-His') . '-' . bin2hex(random_bytes(4));
        $dir = self::root() . DIRECTORY_SEPARATOR . $id;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return 'Not sent: the test mailbox folder could not be created (writable/' . self::DIR . ').';
        }
        $files = [];
        foreach ((array) $attachments as $att) {
            $path = is_array($att) ? (string) ($att['path'] ?? '') : (string) $att;
            $name = is_array($att) && !empty($att['name']) ? (string) $att['name'] : basename($path);
            if ($path === '' || !is_file($path)) {
                return 'Not sent: the attachment ' . $name . ' could not be found.';
            }
            $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name);
            copy($path, $dir . DIRECTORY_SEPARATOR . $safe);
            $files[] = ['name' => $name, 'file' => $safe, 'size' => filesize($path), 'sha256' => hash_file('sha256', $path)];
        }
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'body.html', (string) $body);
        $by = 'system';
        try {
            $by = (string) (session()->get('username') ?? 'system');
        } catch (\Throwable $e) {
        }
        $meta = ['id' => $id, 'at' => date('Y-m-d H:i:s'), 'from' => $from ?? $by, 'to' => $to, 'cc' => $cc, 'subject' => (string) $subject, 'attachments' => $files];
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'message.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return 200;
    }

    /** The newest messages first. */
    public static function messages(int $limit = 200): array
    {
        $out = [];
        $root = self::root();
        if (!is_dir($root)) {
            return [];
        }
        $dirs = array_values(array_filter(scandir($root) ?: [], static fn ($d) => (bool) preg_match('/^\d{8}-\d{6}-[0-9a-f]{8}$/', $d)));
        rsort($dirs);
        foreach (array_slice($dirs, 0, $limit) as $d) {
            $m = json_decode((string) @file_get_contents($root . DIRECTORY_SEPARATOR . $d . DIRECTORY_SEPARATOR . 'message.json'), true);
            if (is_array($m)) {
                $out[] = $m;
            }
        }
        return $out;
    }

    /** One message's file (body.html or an attachment), or null. */
    public static function file(string $id, string $name): ?string
    {
        if (!preg_match('/^\d{8}-\d{6}-[0-9a-f]{8}$/', $id) || !preg_match('/^[A-Za-z0-9._-]+$/', $name) || str_contains($name, '..')) {
            return null;
        }
        $p = self::root() . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . $name;
        return is_file($p) ? $p : null;
    }
}
