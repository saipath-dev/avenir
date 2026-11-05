<?php

namespace App\Models;

use CodeIgniter\Model;

class SupplierqqModel extends Model
{
    protected $table = 'supplier_qq';
    // protected $primaryKey = 'id';
    protected $allowedFields = ['HName', 'SDr', 'SCr', 'Invoice_Num', 'status', 'Bal', 'L_Code', 'FirstOfDate', 'PName', 'index_no'];
    protected $returnType = 'array';
}
