<?php

namespace App\Models;

use CodeIgniter\Model;

class TourActivityModel extends Model
{
	protected $table = 'tour_activity';
	protected $primaryKey = 'id';
	protected $allowedFields = ['tour_id', 'tour_row', 'tour_date', 'name', 'code', 'fee', 'lkr_fee', 'ref_no', 'supplier_id', 'supplier_name', 'payment_request', 'payment_request_user', 'payment_request_date', 'is_prepaid'];
	protected $returnType = 'array';



	public function getActivityDetails($tour_id) {
		$query = $this->db->table('tour_activity')
		->select('tour_activity.tour_id,entrance_fee.Name')
		->join('entrance_fee', 'tour_activity.name = entrance_fee.id')
		->where('tour_activity.tour_id', $tour_id)
		->get();

    return $query->getResultArray(); // Ensure you're returning it as an associative array
}



// public function gethoteldetails($tour_id) {
// 	$query = $this->db->table('tour_quotations')
// 	->select('MAX(meal_code) as meal_code,MAX(tour_date) as first_date, MIN(tour_date) AS last_date')
// 	->join('entrance_fee', 'tour_activity.name = entrance_fee.id')
// 	->where('tour_activity.tour_id', $tour_id)
// 	->get();

//     return $query->getResultArray(); // Ensure you're returning it as an associative array
// }

}
