<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuCategoryModel extends Model
{
    protected $table = 'menu_category';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id', 'category', 'user', 'del', 'order_id'];
    protected $returnType = 'array';
}
