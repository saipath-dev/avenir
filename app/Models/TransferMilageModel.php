<?php

namespace App\Models;

use CodeIgniter\Model;

class TransferMilageModel extends Model
{
    protected $table = 'transfer_milage';
    protected $primaryKey = 'id';
    protected $allowedFields = ['transfer_code', 'transfer', 'from', 'to', 'estimated_mileage', 'actual_mileage'];
    protected $returnType = 'array';
}
