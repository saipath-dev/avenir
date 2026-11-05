<?php

namespace App\Controllers\Reports;

use App\Controllers\BaseController;
use App\Models\QuotationAuditLogModel;
use App\Models\ConsigneeModel;
use App\Models\UserModel;
use App\Libraries\MenuLoader;

class ChangeLogReportController extends BaseController
{
    protected $auditLogModel;
    protected $consigneeModel;
    protected $userModel;

    public function __construct()
    {
        $this->auditLogModel = new QuotationAuditLogModel();
        $this->consigneeModel = new ConsigneeModel();
        $this->userModel = new UserModel();
    }

    /**
     * Main report page
     */
    public function index()
    {
        // Get all tours for filter dropdown
        $data['tours'] = $this->consigneeModel
            ->select('tour_id, manual_id, consignee')
            ->orderBy('ID', 'DESC')
            ->findAll();

        // Get all users for filter dropdown
        $data['users'] = $this->userModel
            ->select('id, user')
            ->orderBy('user', 'ASC')
            ->findAll();

        $data['change_types'] = [
            'tour_creation' => 'Tour Creation',
            'hotel_date' => 'Hotel/Date Changes',
            'sub_location' => 'Sub-Location Changes',
            'activity' => 'Activity Changes',
            'itinerary' => 'Itinerary Changes',
            'general' => 'General Updates',
            'room_count' => 'Room Count Changes',
            'vehicle_rate' => 'Vehicle Rate Changes',
            'passenger' => 'Passenger Changes'
        ];

        $data['action_types'] = ['INSERT', 'UPDATE', 'DELETE'];

        // Load menu data (required for header/sidebar)
        $menuLoader = new MenuLoader();
        $menuData = $menuLoader->loadMenuData();
        $data = array_merge($data, $menuData);

        return view('pages/reports/change_log_report', $data);
    }

    /**
     * Get filtered change logs (AJAX)
     */
    public function getChanges()
    {
        try {
            $request = $this->request->getPost();

            $filters = [
                'tour_id' => $request['tour_id'] ?? null,
                'tour_search' => $request['tour_search'] ?? null,
                'change_type' => $request['change_type'] ?? null,
                'action_type' => $request['action_type'] ?? null,
                'user_id' => $request['user_id'] ?? null,
                'date_from' => $request['date_from'] ?? null,
                'date_to' => $request['date_to'] ?? null
            ];

            // Remove empty filters
            $filters = array_filter($filters, function ($value) {
                return !empty($value);
            });

            $logs = $this->auditLogModel->getFilteredLogs($filters);

            return $this->response->setJSON([
                'status' => 200,
                'data' => $logs,
                'count' => count($logs)
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Export to Excel
     */
    public function exportExcel()
    {
        try {
            $request = $this->request->getGet();

            $filters = [
                'tour_id' => $request['tour_id'] ?? null,
                'tour_search' => $request['tour_search'] ?? null,
                'change_type' => $request['change_type'] ?? null,
                'action_type' => $request['action_type'] ?? null,
                'user_id' => $request['user_id'] ?? null,
                'date_from' => $request['date_from'] ?? null,
                'date_to' => $request['date_to'] ?? null
            ];

            $filters = array_filter($filters);
            $logs = $this->auditLogModel->getFilteredLogs($filters);

            // Prepare CSV data
            $filename = 'change_log_report_' . date('Y-m-d_His') . '.csv';

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($output, [
                'Date/Time',
                'Tour ID',
                'Manual ID',
                'Consignee',
                'Change Type',
                'Action',
                'Description',
                'User',
                'IP Address'
            ]);

            // CSV Data
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['created_at'],
                    $log['tour_id'],
                    $log['tour_manual_id'],
                    $log['consignee_name'],
                    $log['change_type'],
                    $log['action_type'],
                    $log['change_description'],
                    $log['user_name'],
                    $log['ip_address']
                ]);
            }

            fclose($output);
            exit;
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            exit;
        }
    }

    /**
     * Get change statistics
     */
    public function getStatistics()
    {
        try {
            $tourId = $this->request->getGet('tour_id') ?? null;
            $stats = $this->auditLogModel->getStatistics($tourId);

            return $this->response->setJSON([
                'status' => 200,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get tour timeline
     */
    public function getTourTimeline($tourId)
    {
        try {
            $timeline = $this->auditLogModel->getTourTimeline($tourId);

            return $this->response->setJSON([
                'status' => 200,
                'data' => $timeline
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
}
