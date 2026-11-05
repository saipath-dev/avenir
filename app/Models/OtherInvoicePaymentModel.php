<?php

namespace App\Models;

use CodeIgniter\Model;

class OtherInvoicePaymentModel extends Model
{
    protected $table         = 'other_invoice_payment';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'invoice_no',
        'payment_date',
        'payment_method',
        'pay_jpy_amount',
        'pay_rate',
        'pay_lkr_amount',
        'ref',
        'created_by',
        'created_at',
    ];
    protected $returnType = 'array';
}
