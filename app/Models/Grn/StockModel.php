<?php

namespace App\Models\Grn;

use CodeIgniter\Model;

class StockModel extends Model
{
    protected $table = 'stock';
    protected $allowedFields = ['Unit_Cost', 'Dr_Qty', 'Cr_Qty', 'Ref', 'Status', 'S_Price', 'Stock_Card', 'Stores', 'Wrt', 'SN', 'pPrice', 'unit_vat', 'L_dis', 'job_flag', 'Descrip', 'line_dis', 'unit_Price', 'tym', 'hr', 'gatepass', 'V_Num', 'dmg', 'brnch', 'one', 'foc', 'Act_prze', 'new_cost', 'value', 'Dr_ml', 'Cr_ml', 'ava_qty', 'flg', 'cus_acc', 'Nx', 'Vx', 'R_price', 'W_price', 'expire_date', 'List_price', 'Dis_price', 'vat', 'nbt', 'txt', 'tn', 'index_no', 'issued_date', 'returned_date', 'principal_req', 'book_type', 'price', 'req_by', 'app_by', 'app_date', 'B_Code'];
    protected $returnType = 'array';
}
