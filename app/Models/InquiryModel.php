<?php

namespace App\Models;

use CodeIgniter\Model;

class InquiryModel extends Model
{
    protected $table = 'inquiry';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'inquiry_code', 'name', 'email', 'phone', 'date', 'message', 'record_time',
        'reply_flag', 'agent_name', 'start_date', 'end_date', 'roomsgl', 'roomdbl',
        'roomtpl', 'is_accepted', 'is_created',
        // New fields from Avenir.txt
        'vehicle', 'vehicle_name', 'passenger_count', 'transport_only', 'hotel_only',
        'manual_id', 'arrival_flight', 'departure_flight', 'arrival_flight_time',
        'departure_flight_time'
    ];
    protected $returnType = 'array';
    protected $useTimestamps = false;
}
