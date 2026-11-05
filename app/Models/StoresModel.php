<?php

namespace App\Models;

use CodeIgniter\Model;

class StoresModel extends Model
{
    protected $table = 'stores';
    protected $primaryKey = 'Id';
    protected $allowedFields = ['Stores'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
