<?php

namespace App\Models;

use CodeIgniter\Model;

class EntertainmentExpensModel extends Model
{
    protected $table = 'entertainment_expenses';
    protected $primaryKey = 'ID';
    protected $allowedFields = [
        'grand_total',
        'card_acc',
        'card_name',
        'ref_no',
        'flag',
        'user',
        'username',
        'payment_method',
        'rec_user',
        'rec_username'
    ];
    protected $returnType = 'array';
}
