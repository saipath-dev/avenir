<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverPortalExpensesModel extends Model
{
    protected $table = 'driver_portal_expenses';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'tour_id',
        'tour_date_id',
        'agent',
        'agent_name',
        'category',
        'supplier',
        'supplier_name',
        'invoice_no',
        'expense_date',
        'dollar_rate',
        'dollar_amount',
        'lkr_amount',
        'description',
        'acc_no',
        'status',
        'request_send',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at'       
    ];
    protected $returnType = 'array';
    protected $useTimestamps = false;
}
