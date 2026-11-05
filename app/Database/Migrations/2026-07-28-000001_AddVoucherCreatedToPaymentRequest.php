<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVoucherCreatedToPaymentRequest extends Migration
{
    public function up()
    {
        $fields = [
            'voucher_created' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
                'comment' => 'Set to 1 once a voucher row has been created for this payment request',
            ],
        ];

        $this->forge->addColumn('payment_request', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('payment_request', ['voucher_created']);
    }
}
