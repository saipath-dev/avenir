<?php

namespace App\Models;

use CodeIgniter\Model;

class TourQuotationsModel extends Model
{
    protected $table = 'tour_quotations';
    protected $primaryKey = 'id';
    protected $allowedFields = ['quotation_id', 'quotation_day', 'hotel_code', 'meal_code', 'hotel_acc', 'hotel_name', 'room_type_id', 'room_type', 'tour_date', 'start_date', 'end_date', 'sgl', 'dbl', 'tpl', 'created_time', 'created_user', 'det_flag', 'distance_type', 'transfer_code', 'transfer', 'distance', 'from_location', 'to_location', 'gdistance', 'added_time', 'added_by', 'trans_flag', 'confirm', 'finalize', 'hotel_advance', 'advance_ref', 'extra_kilo', 'extra_act_fee', 'sglcount', 'sglfoc', 'dblcount', 'dblfoc', 'tplcount', 'tplfoc', 'extra_hotel_fee', 'q_extra_hotel_fee', 'hotel_dollar_rate', 'hotel_booking_flag', 'booking_confirmed_flag', 'hotel_payment_flag', 'confirmation_email', 'confirmation_reference', 'is_sub', 'saved', 'verify', 'total_hotel_doller', 'pay_doller_count','payment_complete_user','payment_complete','payment_complete_date','payment_request','payment_request_user','payment_request_date'];
    protected $returnType = 'array';
}



// 0 -> Default (not book)
// 1 -> book
// 3 -> Cancel Booking
// 4 -> Amend inv