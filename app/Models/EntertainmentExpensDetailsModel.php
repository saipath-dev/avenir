<?php

namespace App\Models;

use CodeIgniter\Model;

class EntertainmentExpensDetailsModel extends Model
{
    protected $table = 'entertainment_expenses_details';
    protected $primaryKey = 'ID';
    protected $allowedFields = [
        'Date',
        'EntryDate',
        'Description',
        'Amount',
        'currency',
        'invoice_img_path',
        'ref_no',
        'flag',
        'user',
        'username',
        'payment_method'
    ];
    protected $returnType = 'array';
}
