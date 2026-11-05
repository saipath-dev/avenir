<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCurrencyFieldsToOtherInvoiceLog extends Migration
{
    public function up()
    {
        $fields = [
            'amount_jpy' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'comment' => 'Amount in Japanese Yen'
            ],
            'conversion_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
                'comment' => 'JPY to LKR conversion rate'
            ],
            'amount_lkr' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'comment' => 'Calculated amount in LKR'
            ],
            'is_staff_invoice' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
                'comment' => '0=Regular Invoice, 1=Staff Invoice'
            ]
        ];

        $this->forge->addColumn('other_invoice_log', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('other_invoice_log', ['amount_jpy', 'conversion_rate', 'amount_lkr', 'is_staff_invoice']);
    }
}
