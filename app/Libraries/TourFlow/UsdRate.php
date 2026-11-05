<?php

namespace App\Libraries\TourFlow;

/**
 * tf-stage2 (29 Oct 2026) — the daily USD rate: today's entry from the Home page, and the history of every entry and
 * correction (tf_usd_rate_log). The rate itself stays in daily_usd_rate, read as before by the Payment Desk (an exact row
 * for the day, helper get_usd_rate) and by the TourFlow costing (the latest row on or before the day, ServiceLookup::usdRate).
 *
 * Nothing here reprices anything: a saved inquiry keeps the figures it holds until it is read again (until Stage 4, an open
 * inquiry takes the latest rate on its next opening, as it does today); an issued quotation version and a voucher carry
 * their own figures and never move.
 */
final class UsdRate
{
    public const URL = 'home/usd_rate';
    public const STALE_DAYS = 1;   // a rate older than today is "not entered today"

    public static function installed($db): bool
    {
        try {
            return (bool) $db->query("SHOW TABLES LIKE 'tf_usd_rate_log'")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Today's rate (an exact row for today), the latest rate (any day), and the words for the box. */
    public static function status($db): array
    {
        $today = date('Y-m-d');
        $t = $db->query('SELECT * FROM daily_usd_rate WHERE rate_date = ?', [$today])->getRowArray();
        $last = $db->query('SELECT * FROM daily_usd_rate ORDER BY rate_date DESC LIMIT 1')->getRowArray();
        $age = $last ? (int) floor((strtotime($today) - strtotime($last['rate_date'])) / 86400) : null;
        $corr = null;
        if ($t && self::installed($db)) {
            $corr = (int) ($db->query("SELECT COUNT(*) n FROM tf_usd_rate_log WHERE rate_date = ? AND kind = 'correction'", [$today])->getRowArray()['n'] ?? 0);
        }
        // tf-stage4d: a figure whose latest entry or correction is marked TEST is shown as a TEST figure until a real one replaces it
        $test = false;
        if ($t && self::installed($db)) {
            $lastLog = $db->query('SELECT reason FROM tf_usd_rate_log WHERE rate_date = ? ORDER BY id DESC LIMIT 1', [$today])->getRowArray();
            $test = $lastLog !== null && stripos((string) ($lastLog['reason'] ?? ''), 'TEST') !== false;
        }
        return [
            'today'      => $today,
            'entered'    => $t !== null,
            'rate'       => $t ? (float) $t['rate'] : null,
            'by'         => $t['created_user'] ?? null,
            'at'         => $t['created_at'] ?? null,
            'corrections'=> $corr,
            'test'       => $test,
            'last_rate'  => $last ? (float) $last['rate'] : null,
            'last_date'  => $last['rate_date'] ?? null,
            'last_by'    => $last['created_user'] ?? null,
            'last_age'   => $age,
            'warning'    => $t ? null : ($last ? 'Today\'s rate has not been entered. The last rate is ' . number_format((float) $last['rate'], 2) . ' from ' . date('j M Y', strtotime($last['rate_date'])) . ' (' . $age . ' day' . ($age === 1 ? '' : 's') . ' ago).' : 'No USD rate has ever been entered.'),
        ];
    }

    /**
     * Save a rate for a date (today from the Home box; any date from the Daily USD Rate page). A second save for the same
     * date is a correction: the reason is required, and the old and new figures are logged with who and when.
     */
    public static function save($db, string $rateDate, $rate, ?string $reason, string $user, ?string $userName, string $source): array
    {
        $rateDate = date('Y-m-d', strtotime($rateDate));
        if (!is_numeric($rate) || (float) $rate <= 0) {
            throw new \InvalidArgumentException('Type the rate as a number of rupees for one US dollar (for example 325.50).');
        }
        $rate = round((float) $rate, 2);
        if ($rate < 100 || $rate > 1000) {
            throw new \InvalidArgumentException('That does not look like a rupees-per-dollar figure (expected between 100 and 1,000). Check it and try again.');
        }
        if ($rateDate > date('Y-m-d')) {
            throw new \InvalidArgumentException('A rate cannot be entered for a future date.');
        }
        $reason = trim((string) $reason);
        $existing = $db->query('SELECT * FROM daily_usd_rate WHERE rate_date = ?', [$rateDate])->getRowArray();
        $now = date('Y-m-d H:i:s');
        if ($existing) {
            if ((float) $existing['rate'] === $rate) {
                return ['ok' => true, 'kind' => 'unchanged', 'message' => 'The rate for ' . date('j M Y', strtotime($rateDate)) . ' is already ' . number_format($rate, 2) . '.'];
            }
            if (mb_strlen($reason) < 5) {
                throw new \InvalidArgumentException('This corrects the rate already entered for ' . date('j M Y', strtotime($rateDate)) . ' (' . number_format((float) $existing['rate'], 2) . '). Give the reason for the correction (at least five characters).');
            }
            $db->query('UPDATE daily_usd_rate SET rate = ?, created_user = ?, created_at = ? WHERE id = ?', [$rate, $user, $now, (int) $existing['id']]);
            self::log($db, $rateDate, (float) $existing['rate'], $rate, 'correction', $reason, $user, $userName, $source, (string) $existing['created_user'], (string) $existing['created_at']);
            return ['ok' => true, 'kind' => 'correction', 'message' => 'Corrected: 1 USD = ' . number_format($rate, 2) . ' LKR for ' . date('j M Y', strtotime($rateDate)) . ' (was ' . number_format((float) $existing['rate'], 2) . ').'];
        }
        $db->query('INSERT INTO daily_usd_rate (rate_date, rate, created_user, created_at) VALUES (?,?,?,?)', [$rateDate, $rate, $user, $now]);
        self::log($db, $rateDate, null, $rate, 'entry', $reason, $user, $userName, $source, null, null);
        return ['ok' => true, 'kind' => 'entry', 'message' => 'Saved: 1 USD = ' . number_format($rate, 2) . ' LKR for ' . date('j M Y', strtotime($rateDate)) . '.'];
    }

    private static function log($db, string $rateDate, ?float $old, float $new, string $kind, string $reason, string $user, ?string $userName, string $source, ?string $prevUser, ?string $prevAt): void
    {
        if (!self::installed($db)) {
            return;
        }
        $db->query('INSERT INTO tf_usd_rate_log (rate_date, old_rate, new_rate, kind, reason, user, user_name, source, prev_user, prev_at, at) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$rateDate, $old, $new, $kind, $reason !== '' ? mb_substr($reason, 0, 255) : null, $user, $userName, $source, $prevUser, $prevAt, date('Y-m-d H:i:s')]);
    }

    /** The history, newest first. */
    public static function history($db, int $limit = 100): array
    {
        if (!self::installed($db)) {
            return [];
        }
        return $db->query('SELECT * FROM tf_usd_rate_log ORDER BY at DESC, id DESC LIMIT ' . (int) $limit)->getResultArray();
    }
}
