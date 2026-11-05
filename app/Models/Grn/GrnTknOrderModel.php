<?php

namespace App\Models\Grn;

use CodeIgniter\Model;

class GrnTknOrderModel extends Model
{
    protected $table = 'grn_tkn_order';
    protected $primaryKey = 'id';
    protected $allowedFields = ['inv_no', 'sup_id', 'order_no', 'date', 'flag', 'po_no'];
    protected $returnType = 'array';
}
