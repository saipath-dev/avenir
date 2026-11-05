<?php

namespace App\Models;

use CodeIgniter\Model;

class EntranceFeeModel extends Model
{
    protected $table = 'entrance_fee';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'code', 'l_code', 'location', 'entranceFee', 'entranceFeeLkr', 'img', 'description', 'direct_payment', 'is_prepaid'];
    protected $returnType = 'array';
}
