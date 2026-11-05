<?php

namespace App\Models;

use CodeIgniter\Model;

class InquirySubLocationsModel extends Model
{
    protected $table = 'inquiry_sub_locations';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'inquiry_id', 'date_id', 'inquiry_row', 'from', 'to', 'km',
        'extra_km', 'estimate_km', 'ref_no'
    ];
    protected $returnType = 'array';
    protected $useTimestamps = false;
}