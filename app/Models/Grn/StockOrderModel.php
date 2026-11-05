<?php

namespace App\Models\Grn;

use CodeIgniter\Model;

class StockOrderModel extends Model
{
    protected $table = 'stock_orders';
    protected $primaryKey = 'id';
    protected $allowedFields = ['Date', 'Sup_Acc', 'Discount', 'Type', 'Invoice', 'Login', 'Ref', 'P_Order', 'Job', 'cashier', 'Sales_return', 'Gatepass', 'salesmen', 'reson', 'Stock_date', 'Emp', 'divition', 'V_Number', 'Pending_job', 'pflag', 'emp_number', 'emp_name', 'loading', 'unloading', 'csh', 'crdt', 'sum_id', 'v_no', 'PO_GRN', 'usr', 'trns', 'vat', 'po', 'res_date', 'inv_date', 'Dis_Amt', 'L_Amt', 'Suplier_name', 'item_discount', 'sales_margin', 'Vat_p', 'vat_dis', 'VAT_tot', 'NBT_tot', 'nbt_p', 'GRN_no', 'Account', 'div', 'GRN_Type', 'GRN_Total', 'PO_NO', 'Dis_tot', 'indx_no', 'Note', 'B_Code', 'customer_name'];
    protected $returnType = 'array';
}
