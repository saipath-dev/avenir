<?php

namespace App\Models;

use CodeIgniter\Model;

class OtherInvoiceLogModel extends Model
{
    protected $table = 'other_invoice_log';
    protected $primaryKey = 'id';
    protected $allowedFields = [ 'agent', 'income_type', 'date', 'description', 'invoice_no','cancel','cancel_user','cancel_date', 'amount', 'amount_jpy', 'conversion_rate', 'amount_lkr', 'is_staff_invoice', 'created_at', 'created_by', 'status', 'pay_status', 'payment_by', 'payment_datetime', 'pay_jpy_amount', 'pay_rate' ];
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