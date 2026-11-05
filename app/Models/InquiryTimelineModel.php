<?php

namespace App\Models;

use CodeIgniter\Model;

class InquiryTimelineModel extends Model
{
    protected $table = 'inquiry_timeline';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'inquiry_id', 'inquiry_day', 'inquiry_date', 'from_location', 'to_location',
        'hotel_acc', 'hotel_name', 'room_type', 'meal_code', 'gdistance',
        'distance', 'extra_kilo', 'transfer_code', 'created_time', 'created_user',
        'saved', 'is_sub', 'confirm'
    ];
    protected $returnType = 'array';
    protected $useTimestamps = false;
}
