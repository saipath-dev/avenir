<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffDivisionModel extends Model
{
    protected $table = 'staff_division';
    protected $primaryKey = 'id';
    protected $allowedFields = ['division', 'user', 'del'];
    protected $returnType = 'array';
}
