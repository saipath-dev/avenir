<?php

namespace App\Models\Grn;

use CodeIgniter\Model;

class StockCatModel extends Model
{
    protected $table = 'stock_cat';
    protected $primaryKey = 'id';
    protected $allowedFields = ['Name'];
    protected $returnType = 'array';
}
