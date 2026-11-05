<?php

namespace App\Models;

use CodeIgniter\Model;

class UtilityBillModel extends Model
{
    protected $table      = 'utility_bill';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'id',
        'payment_type',
        'acc_liability',
        'acc_name_liability',
        'acc_expenses',
        'acc_name_expenses',
        'date',
        'inv_no',
        'description',
        'units',
        'rate',
        'amount',
        'cashier',
        'created_at',
        'status',
        'accepted_by',
        'accepted_at',
        'rejected_by',
        'rejected_at',
        'pay_status',
        'pay_status_by',
        'pay_status_at',
        'voucher_token',
    ];
    protected $returnType = 'array';
}
