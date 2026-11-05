<?php

namespace App\Models;

use CodeIgniter\Model;

class InvalertlogModel extends Model
{
    protected $table = 'inv_alert_log';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tour_id', 'manual_id', 'inv_no', 'action', 'action_user', 'action_date'];

    protected $returnType = 'array';


}
