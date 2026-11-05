<?php

namespace App\Models;

use CodeIgniter\Model;

class TitleAccountsModel extends Model
{
    protected $table = 'account_titel';
    protected $allowedFields = ['T_Code', 'TName', 'B_Code', 'ID'];
    protected $returnType = 'array';
}
