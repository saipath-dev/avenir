<?php

namespace App\Models;

use CodeIgniter\Model;

class DebtorInvoiceLogModel extends Model
{
    protected $table = 'debtor_invoice_log';
    protected $primaryKey = 'id';
    protected $allowedFields = [ 'agent','tour_id', 'income_type', 'date', 'description', 'room_cost', 'transport_cost', 'doller_rate', 'dollar_amount', 'credit_balance', 'invoice_no','hotel_amount','activity_amount','activity_des','hotel_des','consignee','payment_lcode','cancel','cancel_user','cancel_date', 'requested_flag', 'requested_user', 'requested_date', 'inv_status', 'requested_reason', 'top_mng_approval_finalize'];
    protected $returnType = 'array';
}

// cancel Colume

// 0 -> Default
// 1 -> and Tour Amendment Request Accept
// 2 -> Tour Cancel Request
// 3 -> Tour Cancellation Request and Tour Amendment Request Reject
// 4 -> Tour Cancellation Request Accept 
// 5 -> Tour Amendment Request


// inv status Colume

// 0 -> Default
// 1 -> Approved
// 2 -> Sent
// 3 -> Rejected

// requested_flag Colume
// 2 -> Tour Cancel Request
// 5 -> Tour Amendment Request