<?php

namespace App\Models;

use CodeIgniter\Model;

class UsageNoteModel extends Model
{
    protected $table = 'usage_note';
    protected $primaryKey = 'id';
    protected $allowedFields = ['usage_note_no', 'date', 'tour_id', 'activity_id', 'activity_ref_no', 'item_code', 'quantity', 'created_by', 'status', 'approved_by', 'approved_at', 'rejection_reason', 'remarks', 'created_at', 'updated_at'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get usage note report data with all related information
     *
     * @param array $filters ['startdate', 'enddate', 'tour_keyword', 'item_keyword']
     * @return array
     */
    public function getReportData($filters = [])
    {
        $builder = $this->db->table($this->table);

        $builder->select('
            usage_note.id as usage_note_id,
            usage_note.usage_note_no,
            usage_note.date,
            usage_note.tour_id,
            usage_note.item_code,
            usage_note.quantity,
            usage_note.status,
            consignee.manual_id,
            consignee.consignee,
            consignee.passenger_count,
            stock_card.Descrip as item_description,
            tour_activity.tour_date as activity_date,
            tour_activity.code as activity_code,
            entrance_fee.name as activity_name,
            user_acc_tbl.user as created_by_name,
            COALESCE(stock.Unit_Cost, stock.pPrice, 0) as unit_cost,
            (usage_note.quantity * COALESCE(stock.Unit_Cost, stock.pPrice, 0)) as total_value,
            COALESCE(SUM(unr.quantity), 0) as returned_quantity,
            (usage_note.quantity - COALESCE(SUM(unr.quantity), 0)) as returnable_quantity
        ');

        // Join tables
        $builder->join('consignee', 'consignee.tour_id = usage_note.tour_id', 'LEFT');
        $builder->join('stock_card', 'stock_card.Stock_Card = usage_note.item_code', 'LEFT');
        $builder->join('tour_activity', 'tour_activity.id = usage_note.activity_id', 'LEFT');
        $builder->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'LEFT');
        $builder->join('user_acc_tbl', 'user_acc_tbl.acc_no = usage_note.created_by', 'LEFT');

        // Subquery for stock cost - get latest cost with proper grouping
        $builder->join('(
            SELECT s1.Stock_Card,
                   s1.Unit_Cost,
                   s1.pPrice
            FROM stock s1
            INNER JOIN (
                SELECT Stock_Card, MAX(ID) as max_id
                FROM stock
                WHERE (Unit_Cost > 0 OR pPrice > 0)
                GROUP BY Stock_Card
            ) s2 ON s1.Stock_Card = s2.Stock_Card AND s1.ID = s2.max_id
        ) as stock', 'stock.Stock_Card = usage_note.item_code', 'LEFT');

        // Join stock_return to calculate returned quantities
        $builder->join('stock_return unr',
            'unr.usage_note_id = usage_note.id AND unr.status = "processed"',
            'LEFT');

        // Apply filters
        if (!empty($filters['startdate'])) {
            $builder->where('usage_note.date >=', $filters['startdate']);
        }
        if (!empty($filters['enddate'])) {
            $builder->where('usage_note.date <=', $filters['enddate']);
        }
        if (!empty($filters['tour_keyword'])) {
            $builder->groupStart()
                ->like('consignee.manual_id', $filters['tour_keyword'])
                ->orLike('consignee.consignee', $filters['tour_keyword'])
                ->groupEnd();
        }
        if (!empty($filters['item_keyword'])) {
            $builder->groupStart()
                ->like('stock_card.Stock_Card', $filters['item_keyword'])
                ->orLike('stock_card.Descrip', $filters['item_keyword'])
                ->groupEnd();
        }

        $builder->groupBy('usage_note.id');
        $builder->orderBy('usage_note.date', 'DESC');
        $builder->orderBy('usage_note.usage_note_no', 'DESC');

        return $builder->get()->getResultArray();
    }
}
