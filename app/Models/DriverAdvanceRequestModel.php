<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverAdvanceRequestModel extends Model
{
    protected $table = 'driver_advance_request';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'tour_id',
        'manual_id',
        'driver',
        'driver_name',
        'amount',
        'username',
        'date',
        'flag',
        'timezone'
    ];

}
