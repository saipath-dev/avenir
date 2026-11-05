<?php

namespace App\Models;

use CodeIgniter\Model;

class LogSheetMileModel extends Model
{
    protected $table = 'log_sheet_mile';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tour_id', 'transport_agent', 'log_sheet_mileage', 'previous_mileage', 'estimate_mileage', 'vehicle_rate', 'per_day_rate', 'no_of_dates', 'is_override', 'overridden_by', 'login', 'date'];
    protected $returnType = 'array';
}
