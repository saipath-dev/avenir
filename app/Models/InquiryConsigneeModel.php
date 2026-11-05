<?php

namespace App\Models;

use CodeIgniter\Model;

class InquiryConsigneeModel extends Model
{
    protected $table = 'inquiry_consignee';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'inquiry_id', 'consignee', 'country', 'k_code', 'mobile', 'email',
        'agent_ref', 'transport_agent', 'transport_agent_name', 'guide',
        'guide_name', 'vehicle', 'vehicle_name', 'vehicle_rate',
        'passenger_count', 'start_date', 'end_date', 'create_user',
        'create_time', 'manual_id', 'agent', 'agent_name', 'arrival_flight',
        'departure_flight', 'arrival_flight_time', 'departure_flight_time',
        'hotel_only', 'transport_only', 'finalize', 'finalize_txt',
        'is_template', 'ref_no'
    ];
    protected $returnType = 'array';

    public function incrementRefno($inquiryId)
    {
        $query = $this->db->query("SELECT ref_no FROM inquiry_consignee WHERE inquiry_id = ?", [$inquiryId]);
        $result = $query->getRow();

        if ($result) {
            $newRefNo = $result->ref_no + 1;
            $this->db->query("UPDATE inquiry_consignee SET ref_no = ? WHERE inquiry_id = ?", [$newRefNo, $inquiryId]);
            return $newRefNo;
        }

        return 1;
    }
}