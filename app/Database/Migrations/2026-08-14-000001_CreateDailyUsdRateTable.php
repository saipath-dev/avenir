<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDailyUsdRateTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'rate_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            'created_user' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('rate_date');
        $this->forge->createTable('daily_usd_rate');
    }

    public function down()
    {
        $this->forge->dropTable('daily_usd_rate');
    }
}
