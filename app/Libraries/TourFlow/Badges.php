<?php

namespace App\Libraries\TourFlow;

/**
 * The counts on the Tour Desk bar, from one place.
 *
 * Every panel that carries the bar gets the same figures: inquiries still with the
 * Quotation Desk, hotel stays to book or in trouble, vehicles to allocate or running
 * late, tours the Board should look at, and the Hotel Rates queue's open items. They
 * come from the same queries the screens themselves use, so a badge never says one
 * thing and the screen another; nothing is stored twice.
 *
 * Read once per request. Any table that is not installed yet simply contributes no
 * badge — the bar draws without it, and the route filter still decides who opens what.
 */
final class Badges
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        self::$cache = [];
        try {
            $db = \Config\Database::connect();
            $user = function_exists('session') ? (string) session()->get('username') : '';
            if ($db->query("SHOW TABLES LIKE 'tf_tour'")->getResultArray()) {
                self::$cache = (new TourService($db, $user))->counts();
            }
            if ($db->query("SHOW TABLES LIKE 'hr2_work_item'")->getResultArray()) {
                self::$cache['master_files/hotel_work'] = (int) ($db->query("SELECT COUNT(*) AS n FROM hr2_work_item WHERE status = 'open'")->getRowArray()['n'] ?? 0);
                // tf-hr-ready-r1: hotels with a rate-expiry warning, on the Hotel Rates tab
                self::$cache['master_files/hotel_rate_v2'] = self::rateAlerts($db);
            }
        } catch (\Throwable $e) {
            // a badge is a convenience; a screen must never fail for want of one
        }
        return self::$cache;
    }

    /**
     * tf-hr-ready-r1: the number of hotels with a rate-expiry warning (QuoteReadiness::alerts — read-only). Kept ten
     * minutes in the signed-in session so the bar stays light on every Tour Desk screen; the Hotel Rates panel and the
     * Quotation readiness page always show the warnings fresh.
     */
    private static function rateAlerts($db): int
    {
        try {
            $s = function_exists('session') ? session() : null;
            $c = $s ? $s->get('tf_hr_rate_alerts') : null;
            if (is_array($c) && ($c['day'] ?? '') === date('Y-m-d') && (time() - (int) ($c['at'] ?? 0)) < 600) {
                return (int) ($c['n'] ?? 0);
            }
            $n = count((new \App\Libraries\HotelRates\QuoteReadiness($db))->alerts());
            if ($s && method_exists($s, 'set')) {
                $s->set('tf_hr_rate_alerts', ['n' => $n, 'at' => time(), 'day' => date('Y-m-d')]);
            }
            return $n;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
