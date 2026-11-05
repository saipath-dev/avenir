<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverSettlementRequestModel extends Model
{
    protected $table = 'driver_settlement_request';
    protected $primaryKey = 'id';
    protected $allowedFields = ['Dr_L_Code', 'Cr_L_Code', 'tour_id', 'manual_id', 'pay_date', 'Dt', 'remaining_balance', 'transagent_name', 'transagent', 'amount', 'status', 'request_user', 'accept_user', 'accept_date', 'bank', 'bank_name'];
    protected $returnType = 'array';
}