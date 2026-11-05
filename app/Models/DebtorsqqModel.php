<?php

namespace App\Models;

use CodeIgniter\Model;

class DebtorsqqModel extends Model
{
    protected $table = 'deptors_qq';
    // protected $primaryKey = 'id';
    protected $allowedFields = ['HName', 'SDr', 'SCr', 'Invoice_Num', 'status', 'Bal', 'L_Code', 'FirstOfDate', 'PName', 'index_no'];
    protected $returnType = 'array';
}
