<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountRunningQ2Model extends Model
{
    protected $table = 'account_runing_q2';
    protected $allowedFields = ['TourId', 'BName', 'TName', 'HName', 'PName', 'Date', 'Dr', 'Cr', 'bal', 'Recpt_No'];
    protected $returnType = 'array';
}
