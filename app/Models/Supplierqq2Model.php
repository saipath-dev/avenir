<?php

namespace App\Models;

use CodeIgniter\Model;

class Supplierqq2Model extends Model
{
    protected $table = 'supplier_qq2';
    // protected $primaryKey = 'id';
    protected $allowedFields = ['HName', 'SDr', 'SCr', 'Invoice_Num', 'status', 'Bal', 'L_Code', 'FirstOfDate', 'PName', 'index_no', 'Balance_30_Days', 'Balance_60_Days', 'Balance_90_Days', 'Balance_Over_90_Days'];
    protected $returnType = 'array';
}
