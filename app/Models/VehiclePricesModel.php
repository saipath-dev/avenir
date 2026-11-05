<?php

namespace App\Models;

use CodeIgniter\Model;

class VehiclePricesModel extends Model
{
    protected $table = 'vehicle_prices';
    protected $primaryKey = 'id';
    protected $allowedFields = ['Report_id', 'Stock_no', 'Chassis_no', 'Auction', 'Buying_price', 'Buying_price_tax', 'Recycle', 'Road_tax', 'Transport', 'Transport_tax', 'Auction_fee', 'Auction_fee_tax', 'Other', 'Inspection_fee', 'LR_cost', 'Loading_fee', 'Repairs', 'Repairs_tax', 'Doc', 'Total', 'Remark', 'reg_by', 'invoice_no', 'warehouse_id', 'transport_flag', 'vehi_reg_flag', 'price_flag', 'shipping_flag', 'document_flag', 'update_flag', 'add_date', 'added_time', 'added_by', 'location', 'proforma'];
    protected $returnType = 'array';
}
