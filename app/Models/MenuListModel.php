<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuListModel extends Model
{
    protected $table = 'menu_list';
    protected $primaryKey = 'id';
    protected $allowedFields = ['cat_id', 'name', 'user', 'del', 'url', 'order_id'];
    protected $returnType = 'array';
}
