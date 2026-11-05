<?php
/**
 * Global daily USD -> LKR rate lookup.
 * Requires an EXACT daily_usd_rate row for the given date — no fallback to a
 * prior day's rate. A day nobody entered a rate for returns null on purpose,
 * so hotel payment requests are genuinely blocked until today's rate is set.
 */
if (!function_exists('get_usd_rate')) {
    function get_usd_rate($date = null)
    {
        $date = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');

        $db = \Config\Database::connect();
        $row = $db->table('daily_usd_rate')
            ->select('rate')
            ->where('rate_date', $date)
            ->get()->getRowArray();

        return $row['rate'] ?? null;
    }
}
