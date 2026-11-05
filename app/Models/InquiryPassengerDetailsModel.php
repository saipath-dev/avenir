<?php

namespace App\Models;

use CodeIgniter\Model;

class InquiryPassengerDetailsModel extends Model
{
    protected $table = 'inquiry_passenger_details';
    protected $primaryKey = 'id';
    protected $allowedFields = ['pass_name', 'inquiry_id'];
    protected $returnType = 'array';
}