<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversationModel extends Model
{
    protected $table = 'conversations';
    protected $primaryKey = 'id';
    protected $allowedFields = ['agent', 'title', 'tagged_tour', 'tagged_inquiry', 'status', 'time', 'last_seen', 'active_quotation_id', 'quotation_status'];
    protected $returnType = 'array';
}
