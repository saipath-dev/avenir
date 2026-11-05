<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverAllocationModel extends Model
{
    protected $table = 'driver_allocation';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'tour_id',
        'manual_id',
        'driver',
        'driver_name',
        'allow_date',
        'allow_user',
        'payment_settlement',
        'request_allocation',
        'requested_user',
        'requested_date',
        'approved_user',
        'approved_date'
    ];

}
