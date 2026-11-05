<?php

namespace App\Models;

use CodeIgniter\Model;

class CountryCodeModel extends Model
{
    protected $table = 'country';
    protected $primaryKey = 'id';
    protected $allowedFields = ['iso', 'name', 'nicename', 'iso3', 'numcode', 'phonecode'];
    protected $returnType = 'array';
}
