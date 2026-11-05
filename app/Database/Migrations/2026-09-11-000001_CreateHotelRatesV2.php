<?php

namespace App\Database\Migrations;

use App\Libraries\HotelRates\Installer;
use CodeIgniter\Database\Migration;

/**
 * Hotel Rates (new) — installs / upgrades to Installer::SCHEMA_VERSION (currently 6). Runs the same SQL file and column upgrades as `php spark hotelrates:install`.
 */
class CreateHotelRatesV2 extends Migration
{
    public function up()
    {
        Installer::install($this->db, APPPATH . 'Database/Sql');
    }

    public function down()
    {
        $tables = $this->db->query("SHOW TABLES LIKE 'hr2_%'")->getResultArray();
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $t) {
            $this->db->query('DROP TABLE IF EXISTS `' . array_values($t)[0] . '`');
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
