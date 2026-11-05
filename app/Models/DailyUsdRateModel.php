<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyUsdRateModel extends Model
{
    protected $table = 'daily_usd_rate';
    protected $primaryKey = 'id';
    protected $allowedFields = ['rate_date', 'rate', 'created_user', 'created_at'];
    protected $returnType = 'array';
}
