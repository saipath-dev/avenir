<?php

namespace App\Commands\HotelRates;

use App\Libraries\HotelRates\LegacyMigrator;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark hotelrates:migrate [--dry-run]
 *
 * Copies supplier (hotels), hotel_rate (rates) and the room types used on tours
 * into the hr2_* tables. Legacy tables are never modified. Re-runnable.
 */
class Migrate extends BaseCommand
{
    protected $group = 'HotelRates';
    protected $name = 'hotelrates:migrate';
    protected $description = 'Copy the legacy hotel_rate rows into Hotel Rates plans (one "migrated" plan per hotel).';
    protected $usage = 'hotelrates:migrate [--dry-run]';
    protected $options = ['--dry-run' => 'Report only; write nothing.'];

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $dry = (bool) CLI::getOption('dry-run');
        $report = (new LegacyMigrator($db, 'spark'))->run($dry);
        $skipped = $report['skipped'];
        unset($report['skipped']);
        CLI::write(($dry ? 'DRY RUN — ' : '') . 'Hotels created ' . $report['hotels_created'] . ', existing ' . $report['hotels_existing'] . '; plans ' . $report['plans_created'] . ', seasons ' . $report['seasons_created'] . ', rate rows ' . $report['rate_rows_created'], 'green');
        CLI::write('Legacy rows: ' . $report['legacy_rows_total'] . ' total, ' . $report['legacy_rows_mapped'] . ' mapped, ' . $report['legacy_rows_skipped'] . ' skipped');
        CLI::write('Flags: ' . json_encode($report['flags']));
        foreach (array_slice($skipped, 0, 30) as $s) {
            CLI::write("  legacy #{$s[0]}: {$s[1]}", 'yellow');
        }
        if (count($skipped) > 30) {
            CLI::write('  … ' . (count($skipped) - 30) . ' more', 'yellow');
        }
    }
}
