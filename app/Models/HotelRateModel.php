<?php

namespace App\Models;

use CodeIgniter\Model;

class HotelRateModel extends Model
{
    protected $table = 'hotel_rate';
    protected $primaryKey = 'id';
    protected $allowedFields = ['hotel', 'location', 'hotel_code', 'room_type', 'sro', 'sbb', 'shb', 'sfb', 'sdate', 'edate', 'dro', 'dbb', 'dhb', 'dfb', 'dsdate', 'dedate', 'tro', 'tbb', 'thb', 'tfb', 'tsdate', 'tedate'];
    protected $returnType = 'array';
}
