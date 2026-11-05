<?php

namespace App\Models;

use CodeIgniter\Model;

class BankModel extends Model
{
    protected $table = 'banks';
    protected $primaryKey = 'id';
    protected $allowedFields = ['bank_id', 'name'];
    protected $returnType = 'array';
}
