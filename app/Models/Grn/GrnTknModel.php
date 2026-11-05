<?php

namespace App\Models\Grn;

use CodeIgniter\Model;

class GrnTknModel extends Model
{
    protected $table = 'grn_tkn';
    protected $allowedFields = ['sup', 'inv', 'stock_card', 'itm', 'rack', 'qty', 'ucost', 'ws_min', 'ws_max', 'rtl_min', 'rtl_max', 'modl', 'avg', 'reordr', 'maxordr', 'date', 'tkn', 'sub', 'foc', 'ID', 'expire_date', 'List_price', 'Dis_price', 'vat', 'item_discount', 'sales_margin', 'Vat_p', 'nbt', 'dis', 'vat_dis', 'tn', 'job', 'LocalP', 'SN', 'po_no', 'total_price'];
    protected $returnType = 'array';
}
