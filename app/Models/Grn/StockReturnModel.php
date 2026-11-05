<?php

namespace App\Models\Grn;

use CodeIgniter\Model;

class StockReturnModel extends Model
{
    protected $table = 'stock_return';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'return_ref',
        'stock_card',
        'return_date',
        'quantity',
        'unit_cost',
        'total_value',
        'supplier_account',
        'reason',
        'notes',
        'stock_ref',
        'created_by',
        'created_at',
        'status'
    ];
    protected $returnType = 'array';
    protected $useTimestamps = false; // Handle created_at manually
}
