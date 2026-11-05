<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsPrepaidToTourActivity extends Migration
{
    public function up()
    {
        $fields = [
            'is_prepaid' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
                'comment' => 'Set to 1 when the activity fee was prepaid to the supplier',
            ],
        ];

        $this->forge->addColumn('tour_activity', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('tour_activity', ['is_prepaid']);
    }
}
