<?php

namespace App\Commands\HotelRates;

use App\Libraries\HotelRates\LegacyProjector;
use App\Libraries\HotelRates\RateLookup;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark hotelrates:project [--hotel=ID]
 *
 * Rebuilds the legacy hotel_rate rows from the approved plans. Run nightly
 * (Windows Task Scheduler / cron) because tactical offers with booking windows
 * start and stop applying as the calendar moves.
 */
class Project extends BaseCommand
{
    protected $group = 'HotelRates';
    protected $name = 'hotelrates:project';
    protected $description = 'Rebuild the legacy hotel_rate table from the approved Hotel Rates plans.';
    protected $usage = 'hotelrates:project [--hotel=ID]';
    protected $options = ['--hotel' => 'Only this hr2_hotel id.'];

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $projector = new LegacyProjector($db, new RateLookup($db), 'spark');
        $hotel = CLI::getOption('hotel');
        $t0 = microtime(true);
        if ($hotel) {
            $r = $projector->projectHotel((int) $hotel);
            CLI::write(json_encode($r), 'green');
        } else {
            $r = $projector->projectAll();
            CLI::write("{$r['hotels']} hotel(s): {$r['rows_written']} legacy rows written, {$r['rows_deleted']} replaced, {$r['skipped']} unit-priced rooms skipped", 'green');
        }
        // the nightly run also refreshes Hotel Incomplete Work (nights without a rate, drafts, margins …)
        if (!$hotel) {
            try {
                $w = (new \App\Libraries\HotelRates\WorkQueueService($db, 'spark'))->scan();
                CLI::write("Incomplete work: {$w['new']} new, {$w['closed']} closed, {$w['open']} open", 'yellow');
            } catch (\Throwable $e) {
                CLI::error('Incomplete work scan failed: ' . $e->getMessage());
            }
        }
        CLI::write('took ' . round(microtime(true) - $t0, 1) . 's');
    }
}
