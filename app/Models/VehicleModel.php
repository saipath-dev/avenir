<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleModel extends Model
{
    protected $table = 'vehicle';
    protected $primaryKey = 'id';
    protected $allowedFields = ['type', 'passenger_count', 'km_rate', 'v_num', 'owner', 'ocontact', 'bata', 'action', 'vimg', 'vlimg', 'vehi_license_expire_date', 'viimg', 'vehi_insurance_expire_date'];
    protected $returnType = 'array';
}