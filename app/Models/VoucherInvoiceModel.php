<?php

namespace App\Models;

use CodeIgniter\Model;

class VoucherInvoiceModel extends Model
{
	protected $table = 'v_inv';
	protected $allowedFields = ['Date', 'Invoice', 'Description', 'Units', 'Rate', 'Amount', 'ID', 'V_tkn', 'H_ID', 'acc', 'acc_amnt', 'account', 'ityp', 'ref', 'GC_Acc', 'InvAmount', 'Due', 'employee', 'link_code', 'emp_V_Acc', 'emp_V_Acc_no', 'with_out_vat', 'VAT', 'login', 'RCType', 'INV_Date', 'Payee', 'is_expenses', 'acc_run_id'];
	protected $returnType = 'array';




}
