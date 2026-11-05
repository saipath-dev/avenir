<?php

namespace App\Models;

use CodeIgniter\Model;

class BankBranchesModel extends Model
{
    protected $table = 'branches';
    protected $primaryKey = 'id';
    protected $allowedFields = ['bankID', 'name'];
    protected $returnType = 'array';
}
