<?php

namespace App\Libraries\TourFlow;

/**
 * A second look at the signed-in person's password before an action that cannot be taken back:
 * Confirm Tour, and cancelling (or deleting) a confirmed tour. The password is checked the way
 * the sign-in checks it (password_verify against user_acc_tbl.pass, the account enabled), never
 * stored, never logged. Five wrong passwords within fifteen minutes lock the check for fifteen
 * minutes; every wrong password is a line on the Activity log, so the trail shows who tried.
 * The counters live in tf_meta (reauth_fail:<user id>); no table changes.
 */
final class Reauth
{
    public const MAX_FAILS = 5;
    public const WINDOW    = 900;   // seconds: both the counting window and the lock

    /**
     * @return array{ok: bool, message: string, wait?: int}
     */
    public static function verify($db, ?int $userId, ?string $password, string $purpose, ?Activity $activity = null): array
    {
        $userId = $userId ?: null;
        if ($userId === null) {
            return ['ok' => false, 'message' => 'Your sign-in could not be read. Sign in again and try once more.'];
        }
        $password = (string) $password;
        if ($password === '') {
            return ['ok' => false, 'message' => 'Enter your password.'];
        }
        $fails = self::fails($db, $userId);
        if (count($fails) >= self::MAX_FAILS) {
            $wait = (int) ceil((max($fails) + self::WINDOW - time()) / 60);
            return ['ok' => false, 'wait' => max(1, $wait), 'message' => 'Too many wrong passwords. Try again in ' . max(1, $wait) . ' minute' . ($wait === 1 ? '' : 's') . '. Nothing was changed.'];
        }
        $row = null;
        try {
            $row = $db->query('SELECT pass, access, user FROM user_acc_tbl WHERE id = ? LIMIT 1', [$userId])->getRowArray();
        } catch (\Throwable $e) {
            $row = null;
        }
        $good = $row && (int) $row['access'] === 1 && is_string($row['pass']) && $row['pass'] !== '' && password_verify($password, $row['pass']);
        if (!$good) {
            $fails[] = time();
            self::save($db, $userId, $fails);
            if ($activity) {
                try {
                    $activity->record('user', $userId, null, null, 'reauth_failed', 'Password not recognised — ' . $purpose . ' (' . count($fails) . ' of ' . self::MAX_FAILS . ' before a 15-minute wait)');
                } catch (\Throwable $e) {
                    // the log never stops the refusal
                }
            }
            $left = self::MAX_FAILS - count($fails);
            return ['ok' => false, 'message' => 'Password not recognised. Nothing was changed.' . ($left > 0 && $left <= 2 ? ' ' . $left . ' more ' . ($left === 1 ? 'try' : 'tries') . ' before a 15-minute wait.' : '')];
        }
        self::save($db, $userId, []);   // a right password clears the counter, stale stamps included
        return ['ok' => true, 'message' => 'Password confirmed.'];
    }

    /** @return int[] the wrong passwords within the window, oldest first */
    private static function fails($db, int $userId): array
    {
        try {
            $r = $db->query('SELECT v FROM tf_meta WHERE k = ?', ['reauth_fail:' . $userId])->getRowArray();
            $list = $r ? json_decode((string) $r['v'], true) : null;
        } catch (\Throwable $e) {
            $list = null;
        }
        $since = time() - self::WINDOW;
        $out = [];
        foreach (is_array($list) ? $list : [] as $t) {
            if ((int) $t >= $since) {
                $out[] = (int) $t;
            }
        }
        sort($out);
        return $out;
    }

    private static function save($db, int $userId, array $fails): void
    {
        try {
            $db->query('INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', ['reauth_fail:' . $userId, json_encode(array_values($fails))]);
        } catch (\Throwable $e) {
            // without the counter the check still refuses a wrong password; it only cannot lock
        }
    }
}
