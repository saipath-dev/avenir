<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverAllocationAuditLogModel extends Model
{
    protected $table = 'driver_allocation_audit';
    protected $primaryKey = 'audit_id';

    protected $allowedFields = [
        'operation',
        'table_name',
        'record_id',
        'tour_id',
        'manual_id',
        'driver',
        'driver_name',
        'changed_at',
        'changed_by',
    ];

}
