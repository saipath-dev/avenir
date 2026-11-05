<?php

namespace App\Models\Grn;

use CodeIgniter\Model;

class StockCostModel extends Model
{
    protected $table = 'stock_cost';
    protected $allowedFields = ['Stock_Card', 'Avg_Cost', 'Last_Cost', 'usern', 'date'];
    protected $returnType = 'array';
}
