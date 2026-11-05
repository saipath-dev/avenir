<?php

namespace App\Libraries\TourFlow;

/**
 * tf-mailfix-r1 (10 Oct 2026) — which server a mailbox sends through, and mail errors people can read.
 *
 * forAddress(): a company mailbox (…@avenirholidays.com, see Config\CompanyMail) sends through the company
 * server; any other mailbox through the host on its user account (port 465, SSL, as before).
 *
 * readable(): what the mail library reports can carry text from Windows in its own language and code page
 * (on this laptop Japanese, Shift-JIS: "このようなホストは不明です。" for "No such host is known"). Such text
 * is not UTF-8 and made the screen's answer fail with "Malformed UTF-8 characters". It is turned into
 * UTF-8 here, and the usual failures get a plain sentence in front, so the person sees what to do.
 */
final class MailServer
{
    /** @return array{host:string, port:int, secure:string, company:bool} */
    public static function forAddress(string $email, ?string $userHost): array
    {
        $cfg = null;
        try {
            $cfg = config('CompanyMail');
        } catch (\Throwable $e) {
            $cfg = null;
        }
        $domain = $cfg ? strtolower(trim((string) $cfg->domain)) : '';
        $at = strtolower(trim((string) substr((string) strrchr($email, '@'), 1)));
        if ($cfg && $domain !== '' && trim((string) $cfg->smtpHost) !== '' && ($at === $domain || str_ends_with($at, '.' . $domain))) {
            $secure = strtolower(trim((string) $cfg->smtpCrypto)) === 'tls' ? 'tls' : 'ssl';
            return ['host' => trim((string) $cfg->smtpHost), 'port' => (int) $cfg->smtpPort > 0 ? (int) $cfg->smtpPort : ($secure === 'tls' ? 587 : 465), 'secure' => $secure, 'company' => true];
        }
        return ['host' => trim((string) $userHost), 'port' => 465, 'secure' => 'ssl', 'company' => false];
    }

    /** Any text as valid UTF-8: Japanese Windows (CP932) first, then Western Windows (CP1252); never invalid bytes. */
    public static function utf8(string $s): string
    {
        if ($s === '' || mb_check_encoding($s, 'UTF-8')) {
            return $s;
        }
        foreach (['SJIS-win', 'Windows-1252'] as $enc) {
            $t = @mb_convert_encoding($s, 'UTF-8', $enc);
            if (is_string($t) && $t !== '' && mb_check_encoding($t, 'UTF-8') && !str_contains($t, '?' . '?' . '?')) {
                return $t;
            }
        }
        return mb_scrub($s, 'UTF-8');
    }

    /** A mail failure as the person should read it: the reason in plain words, then the technical text (UTF-8). */
    public static function readable(string $error, string $host = '', string $email = ''): string
    {
        $tech = trim(self::utf8($error));
        if (str_starts_with($tech, 'Not sent: ')) {
            return $tech;   // already put into plain words (by the sender) — not twice
        }
        $low = strtolower($tech);
        $h = $host !== '' ? $host : (preg_match('/getaddrinfo for ([^\s:]+) failed/i', $tech, $m) ? $m[1] : '');
        $why = null;
        if (str_contains($low, 'getaddrinfo') || str_contains($low, 'php_network_getaddresses') || str_contains($low, 'name or service not known') || str_contains($low, 'no such host')) {
            $why = 'Not sent: the mail server name ' . ($h !== '' ? '"' . $h . '" ' : '') . 'could not be found. Check the internet connection; if it works, the mail server name set for this mailbox is wrong.';
        } elseif (str_contains($low, 'could not authenticate') || str_contains($low, 'authentication failed') || str_contains($low, '535')) {
            $why = 'Not sent: the mail server refused the login' . ($email !== '' ? ' for ' . $email : '') . '. The mailbox password on the user account is wrong, or the mailbox does not exist on the mail server.';
        } elseif (str_contains($low, 'timed out') || str_contains($low, 'connection refused') || str_contains($low, 'could not connect to smtp host') || str_contains($low, 'failed to connect')) {
            $why = 'Not sent: the mail server ' . ($h !== '' ? '"' . $h . '" ' : '') . 'could not be reached. Check the internet connection; a firewall or antivirus may be blocking outgoing mail.';
        }
        if ($tech === '') {
            return $why ?? 'The mail server did not accept the message.';
        }
        return $why !== null ? $why . ' (Details: ' . $tech . ')' : $tech;
    }
}
