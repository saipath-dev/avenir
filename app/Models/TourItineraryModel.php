<?php

namespace App\Models;

use CodeIgniter\Model;

class TourItineraryModel extends Model
{
    protected $table = 'tour_itinerary';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tour_id', 'tour_row', 'tour_date', 'order_id', 'description', 'area', 'bf', 'lunch', 'dinner', 'insert_date', 'ref_no', 'loc_ref'];
    protected $returnType = 'array';
}
