<?php

namespace App\Models;

use CodeIgniter\Model;

class InquiryActivityModel extends Model
{
    protected $table = 'inquiry_activities';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'inquiry_id', 'inquiry_row', 'inquiry_date', 'name', 'code', 'ref_no', 'place_name'
    ];
    protected $returnType = 'array';
    protected $useTimestamps = false;

    public function getActivityDetails($inquiry_id)
    {
        $query = $this->db->table('inquiry_activities')
            ->select('inquiry_activities.inquiry_id, entrance_fee.name')
            ->join('entrance_fee', 'inquiry_activities.name = entrance_fee.id')
            ->where('inquiry_activities.inquiry_id', $inquiry_id)
            ->get();

        return $query->getResultArray();
    }
}