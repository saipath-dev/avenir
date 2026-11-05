<?php

namespace App\Models;

use CodeIgniter\Model;

class TourShopAllocationModel extends Model
{
    protected $table = 'tour_shop_allocation';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tour_id', 'manual_id', 'tour_date', 'shop_id', 'shop_name', 'allocated_by', 'created_at'];
    protected $returnType = 'array';
}
