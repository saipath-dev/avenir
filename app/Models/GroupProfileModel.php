<?php

namespace App\Models;

use CodeIgniter\Model;

class GroupProfileModel extends Model
{
    protected $table = 'group_profile';
    protected $primaryKey = 'id';
    protected $allowedFields = ['type', 'user', 'del'];
    protected $returnType = 'array';
}
