<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountRunning2024Model extends Model
{
    protected $table = 'account_runing_2024';
    protected $primaryKey = 'ID';
    protected $allowedFields = ['ID', 'L_Code', 'Dr', 'Cr', 'Discription', 'Date', 'status', 'Cashier', 'Chk_No', 'Invoice_Num', 'DV', 'Linkacc', 'aflag', 'job', 'index_no', 'P_Cap', 'P_Month', 'Tax', 'srvc_no', 'Dt', 'fine_wave', 'fine_tax', 'fine_amount', 'fine_date', 'Recpt_No', 'Fee', 'Effective_Date', 'Slip_No', 'BankFlg', 'Fee_Dis', 'DiscountFlg', 'fine_remove', 'Bank_Name', 'Chk_Date', 'Bank_Inv', 'Bank_Date', 'Card_No', 'shift_id', 'InvCancel', 'P_Cat', 'InvCancelNote', 'Payment_Grade', 'JE_INV', 'voucher_no', 'br_code', 'repostlogin', 'bankst_flag', 'New_L_Code', 'repostdate', 'inv_total', 'dollarrate', 'dollar', 'advances_pay','payee','hotel_ref','date_changed','date_changed_user','bank_rec', 'hotel_ref'];
    protected $returnType = 'array';
}

// Special Note
// advances_pay = 1 transport advance (expenses)
// advances_pay = 11 transport settlement 
// advances_pay = 111 transport expenses 
// advances_pay = 2 hotel advance
// advances_pay = 22 hotel Confirmation
