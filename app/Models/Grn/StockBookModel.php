<?php

namespace App\Models\Grn;

use CodeIgniter\Model;

class StockBookModel extends Model
{
    protected $table = 'stock_book';
    protected $primaryKey = 'id';
    protected $allowedFields = ['Name'];
    protected $returnType = 'array';
}
