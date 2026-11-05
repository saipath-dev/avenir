<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTemplateFieldsToConsignee extends Migration
{
    public function up()
    {
        $fields = [
            'template_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'tour_id of the template this tour was generated from',
            ],
            'template_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Denormalized name of the template at time of tour creation',
            ],
        ];

        $this->forge->addColumn('consignee', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('consignee', ['template_id', 'template_name']);
    }
}
