<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUnavailableSinceToSupplier extends Migration
{
    public function up()
    {
        $fields = [
            'unavailable_since' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null,
                'comment' => 'Date the supplier was marked unavailable; cleared when re-enabled',
            ],
        ];

        $this->forge->addColumn('supplier', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('supplier', ['unavailable_since']);
    }
}
