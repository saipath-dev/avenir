<?php

namespace App\Models;

use CodeIgniter\Model;

class ExtraKilometersModels extends Model
{
    protected $table = 'extra_kilometers';
    protected $primaryKey = 'id';
    protected $allowedFields = ['city', 'kilometers'];
    protected $returnType = 'array';
}
