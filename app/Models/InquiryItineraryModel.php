<?php

namespace App\Models;

use CodeIgniter\Model;

class InquiryItineraryModel extends Model
{
    protected $table = 'inquiry_itinerary';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'inquiry_id', 'inquiry_row', 'inquiry_date', 'order_id', 'description',
        'area', 'bf', 'lunch', 'dinner', 'insert_date', 'ref_no', 'loc_ref'
    ];
    protected $returnType = 'array';
    protected $useTimestamps = false;
}