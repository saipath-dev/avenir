<?php

namespace App\Models;

use CodeIgniter\Model;

class BasementAccountsModel extends Model
{
    protected $table = 'account_basement';
    protected $allowedFields = ['B_Code', 'BName', 'balflag', 'ID'];
    protected $returnType = 'array';
}
