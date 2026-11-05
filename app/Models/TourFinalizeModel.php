<?php

namespace App\Models;

use CodeIgniter\Model;

class TourFinalizeModel extends Model
{
    protected $table = 'tour_finalize';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'tour_id',
        'manual_id',
        'consignee_name',
        'driver_acc',
        'driver_name',
        'tour_date_ids',
        'expense_ids',
        'expense_count',
        'distance_total',
        'total_dollar_amount',
        'total_lkr_amount',
        'status',
        'finalized_by',
        'finalized_at',
        'created_at',
        'updated_at',
    ];
    protected $returnType = 'array';
    protected $useTimestamps = false;
}
