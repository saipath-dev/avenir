<?php

namespace App\Models;

use CodeIgniter\Model;

class UserPrivilegeModel extends Model
{
    protected $table = 'user_privileges';
    protected $primaryKey = 'id';
    protected $allowedFields = ['group_id', 'menu_id', 'user_id'];
    protected $returnType = 'array';
}
