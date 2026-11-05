<?php

namespace App\Models;

use CodeIgniter\Model;

class DebitNoteModel extends Model
{
    protected $table = 'debit_notes';
    protected $primaryKey = 'id';
    protected $allowedFields = ['invoice_no', 'dr', 'cr', 'balance', 'cancelled_date', 'cancelled_by', 'invoice_log_id'];
    protected $returnType = 'array';
}
