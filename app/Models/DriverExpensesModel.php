<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverExpensesModel extends Model
{
    protected $table = 'driver_expenses';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'tour_id',
        'agent',
        'agent_name',
        'category',
        'supplier',
        'supplier_name',
        'invoice_no',
        'expense_date',
        'dollar_rate',
        'dollar_amount',
        'lkr_amount',
        'description',
        'status',           // pending, approved, rejected
        'created_by',
        'created_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'acc_run_id',        // Reference to account_runing ID after approval
        'posted_by',         // User who posted to accounts
        'posted_at',         // When posted to accounts
        'request_send',      // 0=not sent, 1=sent
        'request_send_by',
        'request_send_at',
        'finalized_by',
        'finalized_at'
    ];
    protected $returnType = 'array';
    protected $useTimestamps = false;

    /**
     * Get pending expenses for approval
     */
    public function getPendingExpenses()
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT driver_expenses.*, consignee.consignee, consignee.manual_id
            FROM driver_expenses
            LEFT JOIN consignee ON consignee.tour_id COLLATE utf8mb4_general_ci = driver_expenses.tour_id COLLATE utf8mb4_general_ci
            WHERE driver_expenses.status = 'pending'
            ORDER BY driver_expenses.created_at DESC
        ")->getResultArray();
    }

    /**
     * Get expenses by tour ID
     */
    public function getExpensesByTour($tour_id)
    {
        return $this->where('tour_id', $tour_id)
            ->orderBy('expense_date', 'ASC')
            ->findAll();
    }

    /**
     * Get expenses by status
     */
    public function getExpensesByStatus($status)
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT driver_expenses.*, consignee.consignee, consignee.manual_id
            FROM driver_expenses
            LEFT JOIN consignee ON consignee.tour_id COLLATE utf8mb4_general_ci = driver_expenses.tour_id COLLATE utf8mb4_general_ci
            WHERE driver_expenses.status = ?
            ORDER BY driver_expenses.created_at DESC
        ", [$status])->getResultArray();
    }
}
