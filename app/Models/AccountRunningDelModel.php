<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountRunningDelModel extends Model
{
    protected $table = 'account_runing_del';
    protected $primaryKey = 'ID';
    protected $allowedFields = ['ID', 'L_Code', 'Dr', 'Cr', 'Discription', 'Date', 'status', 'Cashier', 'Chk_No', 'Invoice_Num', 'DV', 'Linkacc', 'aflag', 'job', 'index_no', 'P_Cap', 'P_Month', 'Tax', 'srvc_no', 'Dt', 'fine_wave', 'fine_tax', 'fine_amount', 'fine_date', 'Recpt_No', 'Fee', 'Effective_Date', 'Slip_No', 'BankFlg', 'Fee_Dis', 'DiscountFlg', 'fine_remove', 'Bank_Name', 'Chk_Date', 'Bank_Inv', 'Bank_Date', 'Card_No', 'shift_id', 'InvCancel', 'P_Cat', 'InvCancelNote', 'Payment_Grade', 'JE_INV', 'Sup_Invoice', 'br_code', 'repostlogin', 'bankst_flag', 'New_L_Code', 'repostdate', 'inv_total', 'exchange_rate', 'dollar_amount', 'delete_ok', 'direct_customer'];
    protected $returnType = 'array';
}
