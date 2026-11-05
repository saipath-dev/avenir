<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailModel extends Model
{
    protected $table = 'emails';
    protected $primaryKey = 'id';
    protected $allowedFields = ['mail_id', 'tour_quotation_id', 'tour_id', 'is_read' ];
    protected $returnType = 'array';
}
