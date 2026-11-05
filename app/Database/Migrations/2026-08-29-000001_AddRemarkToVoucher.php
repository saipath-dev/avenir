<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRemarkToVoucher extends Migration
{
    public function up()
    {
        $fields = [
            'remark' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'default' => null,
                'comment' => 'Remark entered on approve/finalize (optional) or cancel/reject (required)',
            ],
        ];

        $this->forge->addColumn('voucher', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('voucher', ['remark']);
    }
}
