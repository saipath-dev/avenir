<?php

namespace App\Libraries\TourFlow;

/**
 * tf-comms-r3 (27 Oct 2026, Shantha) — secrets at rest.
 *
 * Mailbox passwords (the company system mailbox, the incoming mailbox, and every staff member's own mailbox in
 * user_acc_tbl.email_password) are kept encrypted: AES-256-CTR with an HMAC, through CodeIgniter's Encrypter
 * (OpenSSL driver), under a key that lives ONLY in this install's .env file (encryption.key). The key is generated
 * inside each environment — development, UAT and production each make their own with installKey() — and is never
 * copied between them, printed, logged or stored in the database; after a deployment the passwords are simply
 * entered again. A stored secret reads "enc1:…" and is useless without the key. Values written before this
 * (plain text) are still accepted by reveal() so nothing stops working; migrateUserMailboxes() seals them.
 *
 * Nothing in this class, or in anything that calls it, writes a password to a log, a screen or a JSON answer.
 */
final class Secrets
{
    public const PREFIX = 'enc1:';

    /** tf_meta keys that hold a secret: never written to a backup copy, never returned to a screen. */
    public const META_SECRET_KEYS = ['company_mailbox_pass', 'inbox_pass', 'inbox_poll_token'];

    private static ?string $key = null;
    private static bool $keyLoaded = false;

    private static function envFile(): string
    {
        return rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . '.env';
    }

    /** The key as bytes, from .env (encryption.key = hex2bin:… or base64:…), or null when none is set. */
    private static function key(): ?string
    {
        if (self::$keyLoaded) {
            return self::$key;
        }
        self::$keyLoaded = true;
        $raw = '';
        $file = self::envFile();
        if (is_file($file)) {
            foreach (file($file) as $line) {
                if (preg_match('/^\s*encryption\.key\s*=\s*(.+?)\s*$/', $line, $m)) {
                    $raw = trim($m[1], " \t\"'");
                }
            }
        }
        if ($raw === '') {
            $raw = (string) (getenv('encryption.key') ?: '');
        }
        if ($raw === '') {
            return self::$key = null;
        }
        if (str_starts_with($raw, 'hex2bin:')) {
            $bin = @hex2bin(substr($raw, 8));
        } elseif (str_starts_with($raw, 'base64:')) {
            $bin = base64_decode(substr($raw, 7), true);
        } else {
            $bin = $raw;
        }
        return self::$key = (is_string($bin) && strlen($bin) >= 32) ? $bin : null;
    }

    public static function ready(): bool
    {
        return self::key() !== null;
    }

    private static function encrypter(): \CodeIgniter\Encryption\EncrypterInterface
    {
        $cfg = new \Config\Encryption();
        $cfg->key = (string) self::key();
        $cfg->driver = 'OpenSSL';
        return \Config\Services::encrypter($cfg, false);
    }

    public static function isSealed(?string $v): bool
    {
        return $v !== null && str_starts_with($v, self::PREFIX);
    }

    /** Plain text → "enc1:<base64 ciphertext>". Throws when no key is set (the caller says what to do). */
    public static function seal(string $plain): string
    {
        if ($plain === '') {
            return '';
        }
        if (!self::ready()) {
            throw new \RuntimeException('No encryption key is set for this install yet (Tour Desk Admin → Settings → Generate this environment\'s encryption key). The password was not saved.');
        }
        return self::PREFIX . base64_encode(self::encrypter()->encrypt($plain));
    }

    /** As seal(), but an install that has no key yet keeps the value as before (plain) so staff accounts still work; the Admin card shows the count to encrypt. */
    public static function sealIfReady(string $plain): string
    {
        if ($plain === '' || !self::ready()) {
            if ($plain !== '' && !self::ready()) {
                log_message('warning', 'A staff mailbox password was saved without encryption: no encryption key is set for this install yet (Tour Desk Admin → Settings).');
            }
            return $plain;
        }
        return self::seal($plain);
    }

    /**
     * Stored value → plain text, for use in the same request only. A sealed value needs the key; without it (or
     * with another environment's key) '' comes back and the caller reports "password must be entered again".
     * A value written before encryption (plain text) is returned as it is.
     */
    public static function reveal(?string $stored): string
    {
        $stored = (string) $stored;
        if ($stored === '') {
            return '';
        }
        if (!self::isSealed($stored)) {
            return $stored;
        }
        if (!self::ready()) {
            return '';
        }
        try {
            $bin = base64_decode(substr($stored, strlen(self::PREFIX)), true);
            return is_string($bin) ? (string) self::encrypter()->decrypt($bin) : '';
        } catch (\Throwable $e) {
            return '';   // another environment's key, or a damaged value: treated as "not saved"
        }
    }

    /** Can this stored value be read with this install's key? ('plain' | 'sealed' | 'unreadable' | 'none') */
    public static function state(?string $stored): string
    {
        $stored = (string) $stored;
        if ($stored === '') {
            return 'none';
        }
        if (!self::isSealed($stored)) {
            return 'plain';
        }
        return self::reveal($stored) !== '' ? 'sealed' : 'unreadable';
    }

    /**
     * Generate this environment's key and write it to .env (only when none is set). The key is never returned.
     * @return array{ok:bool, message:string}
     */
    public static function installKey(): array
    {
        if (self::ready()) {
            return ['ok' => true, 'message' => 'An encryption key is already set for this install; it was left as it is.'];
        }
        $file = self::envFile();
        if (!is_file($file)) {
            return ['ok' => false, 'message' => 'The .env file was not found, so no key could be written.'];
        }
        if (!is_writable($file)) {
            return ['ok' => false, 'message' => 'The .env file is not writable by the web server; add the line encryption.key = hex2bin:<64 hex characters> by hand (a value made on this server only).'];
        }
        $hex = bin2hex(random_bytes(32));
        $content = (string) file_get_contents($file);
        $line = 'encryption.key = hex2bin:' . $hex;
        if (preg_match('/^\s*#\s*encryption\.key\s*=.*$/m', $content)) {
            $content = (string) preg_replace('/^\s*#\s*encryption\.key\s*=.*$/m', $line, $content, 1);
        } else {
            $content = rtrim($content, "\r\n") . "\n\n# tf-comms-r3: this install's own key for mailbox passwords at rest. Never copy it to another server.\n" . $line . "\n";
        }
        if (file_put_contents($file, $content) === false) {
            return ['ok' => false, 'message' => 'The .env file could not be written.'];
        }
        self::$keyLoaded = false;
        self::$key = null;
        return ['ok' => self::ready(), 'message' => self::ready() ? 'A new encryption key was generated for this install and written to .env. It is not shown anywhere. Mailbox passwords saved from now on are encrypted with it.' : 'The key was written but could not be read back; check the .env file.'];
    }

    /**
     * Seal every staff mailbox password still held as plain text in user_acc_tbl.email_password. Each row is
     * read, sealed and written back one at a time; a row that cannot be sealed is left as it was. Nothing is
     * logged but counts. @return array{sealed:int, already:int, empty:int, failed:int}
     */
    public static function migrateUserMailboxes($db): array
    {
        if (!self::ready()) {
            throw new \RuntimeException('No encryption key is set for this install yet; generate it first.');
        }
        $n = ['sealed' => 0, 'already' => 0, 'empty' => 0, 'failed' => 0];
        foreach ($db->query('SELECT id, email_password FROM user_acc_tbl')->getResultArray() as $r) {
            $v = (string) ($r['email_password'] ?? '');
            if ($v === '') {
                $n['empty']++;
                continue;
            }
            if (self::isSealed($v)) {
                $n['already']++;
                continue;
            }
            try {
                $sealed = self::seal($v);
                if (self::reveal($sealed) !== $v) {
                    throw new \RuntimeException('round trip failed');
                }
                $db->query('UPDATE user_acc_tbl SET email_password = ? WHERE id = ? AND email_password = ?', [$sealed, (int) $r['id'], $v]);
                $n['sealed']++;
            } catch (\Throwable $e) {
                $n['failed']++;
            }
        }
        return $n;
    }

    /** How the staff mailbox passwords stand (counts only). */
    public static function userMailboxState($db): array
    {
        $n = ['plain' => 0, 'sealed' => 0, 'unreadable' => 0, 'none' => 0];
        try {
            foreach ($db->query('SELECT email_password FROM user_acc_tbl')->getResultArray() as $r) {
                $n[self::state($r['email_password'] ?? null)]++;
            }
        } catch (\Throwable $e) {
        }
        return $n;
    }
}
