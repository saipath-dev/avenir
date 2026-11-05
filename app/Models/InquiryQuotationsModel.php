<?php

namespace App\Models;

use CodeIgniter\Model;

class InquiryQuotationsModel extends Model
{
    protected $table = 'inquiry_quotations';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'inquiry_id', 'quotation_day', 'distance_type', 'transfer_code',
        'from_location', 'to_location', 'gdistance', 'distance', 'extra_kilo',
        'hotel_code', 'meal_code', 'hotel_acc', 'hotel_name', 'room_type',
        'room_type_id', 'tour_date', 'start_date', 'end_date',
        'created_time', 'created_user', 'added_by', 'added_time',
        'det_flag', 'saved', 'is_sub', 'trans_flag', 'ref_no', 'transfer'
    ];
    protected $returnType = 'array';
}