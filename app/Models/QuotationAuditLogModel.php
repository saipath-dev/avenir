<?php

namespace App\Models;

use CodeIgniter\Model;

class QuotationAuditLogModel extends Model
{
    protected $table = 'quotation_changes_audit_log';
    protected $primaryKey = 'log_id';
    protected $allowedFields = [
        'tour_id',
        'tour_manual_id',
        'consignee_name',
        'change_type',
        'action_type',
        'table_affected',
        'record_id',
        'quotation_date',
        'quotation_row_id',
        'field_changed',
        'old_value',
        'new_value',
        'full_data_snapshot',
        'change_description',
        'ip_address',
        'user_agent',
        'user_id',
        'user_name',
        'created_at'
    ];
    protected $returnType = 'array';
    protected $useTimestamps = false;

    /**
     * Get filtered change logs for reporting
     */
    public function getFilteredLogs($filters = [])
    {
        $builder = $this->builder();

        if (!empty($filters['tour_id'])) {
            $builder->where('tour_id', $filters['tour_id']);
        }

        if (!empty($filters['tour_search'])) {
            $builder->groupStart()
                ->like('tour_manual_id', $filters['tour_search'])
                ->orLike('consignee_name', $filters['tour_search'])
                ->orLike('tour_id', $filters['tour_search'])
                ->groupEnd();
        }

        if (!empty($filters['change_type'])) {
            $builder->where('change_type', $filters['change_type']);
        }

        if (!empty($filters['action_type'])) {
            $builder->where('action_type', $filters['action_type']);
        }

        if (!empty($filters['user_id'])) {
            $builder->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('DATE(created_at) >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('DATE(created_at) <=', $filters['date_to']);
        }

        $builder->orderBy('created_at', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get change log statistics
     */
    public function getStatistics($tourId = null)
    {
        $builder = $this->builder();

        if ($tourId) {
            $builder->where('tour_id', $tourId);
        }

        $stats = [
            'total_changes' => $builder->countAllResults(false),
            'by_type' => [],
            'by_action' => [],
            'by_user' => []
        ];

        // Count by change type
        $typeBuilder = clone $builder;
        $stats['by_type'] = $typeBuilder->select('change_type, COUNT(*) as count')
            ->groupBy('change_type')
            ->get()
            ->getResultArray();

        // Count by action type
        $actionBuilder = clone $builder;
        $stats['by_action'] = $actionBuilder->select('action_type, COUNT(*) as count')
            ->groupBy('action_type')
            ->get()
            ->getResultArray();

        // Count by user
        $userBuilder = clone $builder;
        $stats['by_user'] = $userBuilder->select('user_name, user_id, COUNT(*) as count')
            ->groupBy('user_id, user_name')
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return $stats;
    }

    /**
     * Get change timeline for a specific tour
     */
    public function getTourTimeline($tourId)
    {
        return $this->where('tour_id', $tourId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get changes by date range
     */
    public function getChangesByDateRange($startDate, $endDate, $limit = 100)
    {
        return $this->where('DATE(created_at) >=', $startDate)
            ->where('DATE(created_at) <=', $endDate)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get recent changes
     */
    public function getRecentChanges($limit = 50)
    {
        return $this->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get changes by user
     */
    public function getUserChanges($userId, $limit = 100)
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
