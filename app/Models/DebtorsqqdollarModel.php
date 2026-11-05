<?php

namespace App\Models;

use CodeIgniter\Model;

class DebtorsqqdollarModel extends Model
{
    protected $table = 'deptors_qq_dollar';
    // protected $primaryKey = 'id';
    protected $allowedFields = ['HName', 'SDr', 'SCr', 'Invoice_Num', 'status', 'Bal', 'DDr', 'DCr', 'DBal', 'L_Code', 'FirstOfDate', 'PName', 'index_no', 'dollarrate', 'dollar', 'Discription', 'dollarrate_cr', 'dollar_cr'];
    protected $returnType = 'array';
}
