<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentRequestModel extends Model
{
	protected $table = 'payment_request';
	protected $primaryKey = 'id';
	protected $allowedFields = [ 'tour_id',
	'hotel_acc',
	'manual_id',
	'consignee',
	'create_user',
	'start_date',
	'end_date',
	'hotel_name',
	'bank_name',
	'branch_name',
	'sup_bank_acc',
	'total_hotel_pay',
	'total_hotel_advance',
	'due_amount',
	'payment_request',
	'payment_request_user',
	'requestAmount',
	'payment_request_date',
	'payment_cancel_date',
	'payment_cancel_user',
	'accept_user',
	'accept_date',
	'complete_user',
	'complete_date',
	'type',
	'is_bulk',
	'voucher_created'
	];
	protected $returnType = 'array';
}
