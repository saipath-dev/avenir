<?php

namespace App\Models;

use CodeIgniter\Model;

class UserPrevQModel extends Model
{
    protected $table = 'user_priv_q';
    protected $allowedFields = ['user_id', 'emp_id', 'user', 'group_id', 'menu_id', 'type', 'name', 'cat_id', 'url', 'menu_oid', 'category', 'cat_oid', 'menu'];
    protected $returnType = 'array';
}
