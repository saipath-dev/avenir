<?php

namespace App\Models;

use CodeIgniter\Model;

class HeadingAccountsModel extends Model
{
    protected $table = 'account_heading';
    protected $allowedFields = ['H_Code', 'T_Code', 'HName', 'B_Code', 'ID'];
    protected $returnType = 'array';
}
