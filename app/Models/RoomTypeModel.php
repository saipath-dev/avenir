<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomTypeModel extends Model
{
    protected $table = 'room_type';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id', 'rtype'];
    protected $returnType = 'array';
}
