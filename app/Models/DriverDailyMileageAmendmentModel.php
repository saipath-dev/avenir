<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverDailyMileageAmendmentModel extends Model
{
    protected $table = 'driver_daily_mileage_amendment';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'tour_id',
        'driver_acc',
        'reading_date',
        'amended_km',
        'note',
        'amended_by',
        'amended_at',
        'updated_at',
    ];
    protected $returnType = 'array';
}
