<?php

namespace App\Models;

use CodeIgniter\Model;

class AccSelectModel extends Model
{
    protected $table = 'acc_selct';
    protected $allowedFields = ['B_Code', 'BName', 'T_Code', 'TName', 'HName', 'H_Code', 'P_Code', 'PName', 'L_Code'];
    protected $returnType = 'array';
}
