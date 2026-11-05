<?php

namespace App\Models\Grn;

use CodeIgniter\Model;

class StockCardModel extends Model
{
    protected $table = 'stock_card';
    protected $primaryKey = 'ID';
    protected $allowedFields = ['Stock_Book', 'Stock_Cat', 'Sales_P', 'Date', 'wPrice', 'Sales_P_Max', 'wPrice_Max', 'Rack_Location', 'Stock_Card', 'reorder', 'pp', 'vat', 'dis', 'tp', 'iorder', 'Max_ordr', 'itm', 'Bar_code', 'flag', 'units', 'foc', 'Descrip', 'remarks', 'entrance_fee_id'];
    protected $returnType = 'array';
}
