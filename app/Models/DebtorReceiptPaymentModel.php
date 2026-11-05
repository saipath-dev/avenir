<?php

namespace App\Models;

use CodeIgniter\Model;

class DebtorReceiptPaymentModel extends Model
{
    protected $table = 'debtor_receipts';
    protected $primaryKey = 'id';
    protected $allowedFields = ['receipt_no', 'customer_id', 'payment_date', 'cash_amount', 'bank_amount', 'card_amount', 'slip_no', 'booking_no', 'customer_name', 'payment_method', 'created_by', 'created_at', 'updated_at', 'card_details', 'Date', 'tour_id', 'dollar', 'dollarrate'];
    protected $returnType = 'array';
}
