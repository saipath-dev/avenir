<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiInquiryModel extends Model
{
    protected $table = 'inquiry';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'inquiry_code',
        'Name',
        'Email',
        'Mobilenum',
        'Inquiry',
        'Address',
        'Place',
        'Date',
        'Rooms',
        'responded'
    ];

    protected $DBGroup = 'default';
}
