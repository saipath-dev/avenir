<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ConsigneeModel;
use App\Models\TourQuotationsModel;

class NotificationController extends BaseController
{
    /**
     * Get tours where driver is not allocated
     * Conditions:
     * - ConsigneeModel: transport_agent is null, verify = 1
     * - TourQuotationsModel: confirm = 1 or 4
     */
    public function getUnallocatedDriverTours()
    {
        try {
            $db = \Config\Database::connect();

            // Query to get tours that don't have driver allocation
            $builder = $db->table('consignee as c');
            $builder->select('c.tour_id, c.manual_id, c.consignee, c.start_date, c.end_date, c.passenger_count');
            $builder->join('tour_quotations as tq', 'c.tour_id = tq.quotation_id', 'inner');
            $builder->join('driver_allocation as da', 'c.tour_id = da.tour_id', 'left');
            $builder->where('da.tour_id IS NULL');
            $builder->where('c.start_date >=', date('Y-m-d'));
            $builder->where('c.start_date <=', date('Y-m-d', strtotime('+2 months')));
            $builder->where('c.verify', 1);
            $builder->whereIn('tq.confirm', [1, 4]);
            $builder->groupBy('c.tour_id');
            $builder->orderBy('c.start_date', 'ASC');

            $query = $builder->get();
            $results = $query->getResultArray();

            return $this->response->setJSON([
                'status' => 200,
                'count' => count($results),
                'tours' => $results
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Error fetching notifications: ' . $e->getMessage(),
                'count' => 0,
                'tours' => []
            ]);
        }
    }
}
