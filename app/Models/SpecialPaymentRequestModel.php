<?php

namespace App\Models;

use CodeIgniter\Model;

class SpecialPaymentRequestModel extends Model
{
    protected $table = 'special_payment_requests';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'tour_id',
        'manual_id',
        'agent_name',
        'consignee',
        'start_date',
        'end_date',
        'pax_count',
        'requested_by',
        'requested_date',
        'status',
        'admin_user',
        'admin_action_date',
        'notes'
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
    protected $returnType = 'array';
}
