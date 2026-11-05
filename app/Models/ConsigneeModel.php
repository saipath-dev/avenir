<?php

namespace App\Models;

use CodeIgniter\Model;

class ConsigneeModel extends Model
{
    protected $table = 'consignee';
    protected $primaryKey = 'ID';
    protected $allowedFields = ['tour_id', 'consignee', 'country', 'k_code', 'mobile', 'email', 'passenger_count', 'start_date', 'end_date', 'create_user', 'create_time', 'vehicle', 'vehicle_name', 'finalize', 'vehicle_rate', 'transport_agent', 'reference_no', 'transport_advance', 'manual_id', 'tour_code', 'agent', 'agent_name', 'arrival_flight', 'departure_flight', 'bank', 'bank_name', 'arrival_flight_time', 'departure_flight_time', 'sgl', 'dbl', 'tpl', 'dbl_twin', 'hotel_only', 'ref_no', 'total_room_cost', 'total_transport_cost', 'total_mileage', 'tour_bata', 'bata_day_rate', 'bata_days', 'activity_fee_total', 'transport_agent_name', 'transport_only', 'is_template', 'transport_settled', 'identical', 'verify', 'log_sheet_mileage', 'guide', 'guide_name', 'finalize_txt', 'agent_ref', 'amend_inv','payment_complete_user','payment_complete','payment_complete_date','payment_request','payment_request_user','payment_request_date','tour_cancel','marked_complete','marked_complete_user','marked_complete_date','per_person_cost', 'chat_conversation_id', 'created_by_agent', 'agent_phase_completion', 'agent_itinerary', 'inv_alert_status', 'accepted_user', 'accepted_date', 'tour_code', 'template_id', 'template_name'];

    protected $returnType = 'array';

    public function incrementRefno($tour_id)
    {
        // log_message('critical', 'ConsigneeModel::incrementRefno - Called with tour_id: ' . $tour_id);

        $builder = $this->db->table('consignee');
        $builder->select('ref_no');
        $builder->where('tour_id', $tour_id);
        $query = $builder->get();
        $result = $query->getRowArray();

        // log_message('critical', 'ConsigneeModel::incrementRefno - Query result: ' . json_encode($result));

        // Check if consignee record exists
        if (empty($result)) {
            // log_message('critical', 'ConsigneeModel::incrementRefno - ERROR: No consignee record found for tour_id: ' . $tour_id);
            throw new \RuntimeException('No consignee record found for tour_id: ' . $tour_id . '. Please create a consignee record first.');
        }

        $nextRef = $result['ref_no'] + 1;
        // log_message('critical', 'ConsigneeModel::incrementRefno - Incrementing ref_no from ' . $result['ref_no'] . ' to ' . $nextRef);

        $data = [
            'ref_no' => $nextRef
        ];

        $builder->where('tour_id',$tour_id)->update($data);
        // log_message('critical', 'ConsigneeModel::incrementRefno - Updated ref_no to ' . $nextRef);

        return $nextRef;
    }

    public function getAvailableTours()
    {
        $db = \Config\Database::connect();
        $builder = $this->db->table($this->table);

        try {
            // Construct the subquery for the exclusion
            $subQuery = $this->db->table('account_runing')
            ->select('index_no')
            ->getCompiledSelect();

            // Use the subquery in a WHERE NOT IN clause
            $builder->select('tour_id, manual_id')
            ->whereNotIn('tour_id', function($subBuilder) use ($subQuery) {
                $subBuilder->select('index_no')
                ->from('account_runing');
            });

            // Execute the query and fetch results
            $query = $builder->get();
            if ($query === false) {
                throw new \RuntimeException('Error executing the query');
            }
            return $query->getResultArray();
        } catch (\RuntimeException $e) {
            // Log the error message
            log_message('error', $e->getMessage());
            return [];
        }
    }

    public function getConsignee($tour_id) {
        $query = $this->db->table('consignee')
        ->select('customers.Name, customers.Sup_Acc_No')
        ->join('customers', 'customers.Sup_Acc_No = consignee.agent')
        ->where('consignee.tour_id', $tour_id)
        ->get();

        return $query->getRowArray(); // Ensure you're returning it as an associative array
    }

    public function getDatesByTourId($tour_id)
    {
        return $this->select('start_date, end_date')
            ->where('tour_id', $tour_id)
            ->first();
    }

}
