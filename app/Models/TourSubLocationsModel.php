<?php

namespace App\Models;

use CodeIgniter\Model;

class TourSubLocationsModel extends Model
{
    protected $table = 'tour_sub_locations';
    protected $primaryKey = 'id';
    protected $allowedFields = ['quotation_id', 'date_id', 'from', 'to', 'km', 'tour_row', 'extra_km', 'estimate_km', 'ref_no'];
    protected $returnType = 'array';
}
