<?php

namespace App\Models;

use CodeIgniter\Model;

class InvDescriptionModel extends Model
{
    protected $table = 'inv_description';
    protected $primaryKey = 'id';
    protected $allowedFields = ['description', 'user', 'del'];
    protected $returnType = 'array';
}
