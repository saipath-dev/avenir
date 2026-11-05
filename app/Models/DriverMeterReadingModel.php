<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverMeterReadingModel extends Model
{
    protected $table      = 'driver_meter_reading';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'tour_id', 'driver_acc', 'reading_date', 'type',
        'km', 'previous_km', 'photo', 'notes',
        'is_override', 'overridden_by', 'overridden_at', 'created_by'
    ];
}
