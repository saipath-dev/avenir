<?php

namespace App\Models;

use CodeIgniter\Model;

class JobRoleModel extends Model
{
    protected $table = 'job_roles';
    protected $primaryKey = 'id';
    protected $allowedFields = ['job_role', 'created_at', 'created_by', 'updated_at', 'updated_by', 'del'];
    protected $returnType = 'array';

    // Note: Manual soft delete - NOT using $useSoftDeletes
    // Note: Manual timestamps - NOT using $useTimestamps
}
