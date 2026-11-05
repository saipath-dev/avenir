<?php

namespace App\Models;

use CodeIgniter\Model;

class ChangesLogModel extends Model
{
    protected $table = 'changes_log';
    protected $primaryKey = 'r_id';
    protected $allowedFields = ['id', 'quotation_id', 'quotation_day','hotel_code', 'meal_code' , 'hotel_acc', 'hotel_name' , 'room_type_id', 'room_type', 'tour_date', 'start_date', 'end_date', 'sgl', 'dbl', 'tpl', 'new_hotel_acc', 'new_hotel_code','created_time', 'created_user'];
    protected $returnType = 'array';

    
}
