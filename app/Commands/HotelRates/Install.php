<?php

namespace App\Commands\HotelRates;

use App\Libraries\HotelRates\Installer;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark hotelrates:install [--menu] [--force]
 *
 * Creates the hr2_* tables (schema 5) from app/Database/Sql/hotel_rates_v2_schema.sql.
 * Safe to re-run: schema 3 is upgraded in place, the first layout (schema 1) is
 * dropped and rebuilt; --force is needed if that old layout holds hand-entered
 * contracts. --menu adds the "Hotel Rates (new)" and "Hotel Rate Approval"
 * entries under Master Files for the System Administrator group.
 */
class Install extends BaseCommand
{
    protected $group = 'HotelRates';
    protected $name = 'hotelrates:install';
    protected $description = 'Create or upgrade the Hotel Rates tables (and optionally the menu entry).';
    protected $usage = 'hotelrates:install [--menu] [--force]';
    protected $options = ['--menu' => 'Also insert the menu entry and the System Administrator grant.', '--force' => 'Rebuild an older layout even if it holds hand-entered contracts.'];

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        try {
            $res = Installer::install($db, APPPATH . 'Database/Sql', (bool) CLI::getOption('force'));
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
            return;
        }
        foreach ($res['notes'] as $n) {
            CLI::write($n, 'yellow');
        }
        CLI::write("Schema {$res['statements']} statement(s) executed; {$res['tables']} hr2_ tables present (schema version " . Installer::SCHEMA_VERSION . ').', 'green');
        if (CLI::getOption('menu')) {
            $count = Installer::runSqlFile($db, APPPATH . 'Database/Sql/hotel_rates_v2_menu.sql');
            CLI::write("Menu: {$count} statement(s) executed — entries 'Hotel Rates (new)' (master_files/hotel_rate_v2) and 'Hotel Rate Approval' (master_files/hotel_rate_approval) under Master Files, granted to System Administrator.", 'green');
        }
        CLI::write('Settings (markets, tiers, gala nights, check thresholds, country → market, old-screen lock) are edited from the panel: Master Files → Hotel Rates (new) → Settings.', 'yellow');
        if ($res['dropped']) {
            CLI::write('Now run: php spark hotelrates:migrate', 'yellow');
        }
    }
}
