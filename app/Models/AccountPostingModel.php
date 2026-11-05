<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountPostingModel extends Model
{
    protected $table = 'account_posting';
    protected $allowedFields = ['P_Code', 'PName', 'B_Code', 'T_Code', 'H_Code', 'L_Code', 'Link', 'Pay', 'acp', 'deact', 'grn', 'ID', 'type'];
    protected $returnType = 'array';
}
