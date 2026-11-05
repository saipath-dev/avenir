<?php

namespace App\Commands\HotelRates;

use App\Libraries\HotelRates\HotelRateService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark hotelrates:check [--days=N]   (default: Settings check_days)
 *
 * Hotel nights on upcoming tours with no approved rate, plans ending soon
 * without a successor, tactical offers whose booking window closed, and
 * approved rate rows carrying quality flags.
 */
class Check extends BaseCommand
{
    protected $group = 'HotelRates';
    protected $name = 'hotelrates:check';
    protected $description = 'Report upcoming tour nights without a rate, expiring plans and flagged rows.';
    protected $usage = 'hotelrates:check [--days=N]';
    protected $options = ['--days' => 'How far ahead to look (default: the check_days setting in the panel).'];

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $svc = new HotelRateService($db, 'spark');
        $days = max(7, min(365, (int) (CLI::getOption('days') ?: $svc->settings()->get('check_days'))));
        $expiryDays = (int) $svc->settings()->get('expiry_warning_days');
        $today = date('Y-m-d');
        $until = date('Y-m-d', strtotime("+{$days} days"));
        $nights = $db->query(
            "SELECT tq.quotation_id, c.manual_id, tq.tour_date, tq.hotel_acc, tq.hotel_name, tq.room_type, tq.meal_code
             FROM tour_quotations tq JOIN consignee c ON c.tour_id = tq.quotation_id
             WHERE tq.hotel_acc <> '' AND tq.tour_date BETWEEN ? AND ? AND (c.tour_cancel IS NULL OR c.tour_cancel = 0)",
            [$today, $until]
        )->getResultArray();
        $missing = [];
        foreach ($nights as $n) {
            $meal = strtolower((string) $n['meal_code']);
            if (!in_array($meal, ['ro', 'bb', 'hb', 'fb', 'ai'], true)) {
                $meal = 'bb';
            }
            if (!$svc->lookup((string) $n['hotel_acc'], is_numeric($n['room_type']) ? (int) $n['room_type'] : (string) $n['room_type'], $meal, (string) $n['tour_date'])) {
                $missing[$n['hotel_acc']] = ($missing[$n['hotel_acc']] ?? 0) + 1;
            }
        }
        CLI::write(count($nights) . " hotel nights checked {$today} → {$until}; " . array_sum($missing) . ' without an approved rate.', array_sum($missing) ? 'yellow' : 'green');
        arsort($missing);
        foreach (array_slice($missing, 0, 25, true) as $acc => $n) {
            $h = $svc->findHotelByAcc($acc);
            CLI::write('  ' . str_pad((string) $n, 4) . ($h['name'] ?? $acc) . " ({$acc})");
        }
        $expiring = $db->query("SELECT c.name, c.valid_to, h.name AS hotel FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.status = 'approved' AND c.plan_type IN ('contract','migrated') AND c.valid_to BETWEEN ? AND DATE_ADD(?, INTERVAL ? DAY) AND NOT EXISTS (SELECT 1 FROM hr2_contract c2 WHERE c2.hotel_id = c.hotel_id AND c2.status = 'approved' AND c2.valid_from > c.valid_to) ORDER BY c.valid_to", [$today, $today, $expiryDays])->getResultArray();
        CLI::write(count($expiring) . " plan(s) end within {$expiryDays} days with no successor:", count($expiring) ? 'yellow' : 'green');
        foreach ($expiring as $e) {
            CLI::write("  {$e['valid_to']}  {$e['hotel']} — {$e['name']}");
        }
        $closed = $db->query("SELECT c.name, c.book_to, h.name AS hotel FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.status = 'approved' AND c.book_to IS NOT NULL AND c.book_to < ? AND c.valid_to >= ? ORDER BY c.book_to", [$today, $today])->getResultArray();
        CLI::write(count($closed) . ' offer(s) whose booking window has closed (still valid for old bookings):');
        foreach ($closed as $e) {
            CLI::write("  closed {$e['book_to']}  {$e['hotel']} — {$e['name']}");
        }
        $flagged = $db->query("SELECT COUNT(*) AS n FROM hr2_rate r JOIN hr2_season s ON s.id = r.season_id JOIN hr2_contract c ON c.id = s.contract_id WHERE r.quality_flags IS NOT NULL AND c.status = 'approved' AND c.valid_to >= ?", [$today])->getRowArray();
        CLI::write(($flagged['n'] ?? 0) . ' approved rate row(s) still carry quality flags (open the panel → Check).');
    }
}
