<?php

namespace App\Models;

use CodeIgniter\Model;

class PassengerDetailsModel extends Model
{
    protected $table = 'passenger_details';
    protected $primaryKey = 'id';
    protected $allowedFields = ['pass_name', 'tour_id', 'manual_tour_id'];
    protected $returnType = 'array';
}
