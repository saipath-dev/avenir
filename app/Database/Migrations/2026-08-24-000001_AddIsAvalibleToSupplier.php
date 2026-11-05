<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsAvalibleToSupplier extends Migration
{
    public function up()
    {
        $fields = [
            'is_avalible' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
                'comment' => 'Set to 0 to hide supplier from tour quotation add-pickers (hotel/driver/restaurant)',
            ],
        ];

        $this->forge->addColumn('supplier', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('supplier', ['is_avalible']);
    }
}
