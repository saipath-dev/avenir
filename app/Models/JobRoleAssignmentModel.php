<?php

namespace App\Models;

use CodeIgniter\Model;

class JobRoleAssignmentModel extends Model
{
    protected $table = 'job_role_assignments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'job_role_id', 'start_date', 'end_date', 'assigned_at', 'assigned_by', 'updated_at', 'updated_by', 'del'];
    protected $returnType = 'array';

    // Note: Manual soft delete - NOT using $useSoftDeletes
    // Note: Manual timestamps - NOT using $useTimestamps
}
