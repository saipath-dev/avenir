<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'user_acc_tbl';
    protected $primaryKey = 'id';
    protected $allowedFields = ['emp_id', 'user', 'pass', 'acc_no', 'group_id', 'access', 'login_type', 'last_logged', 'host', 'email', 'email_password', 'agent', 'user_role'];
    protected $returnType = 'array';
}
