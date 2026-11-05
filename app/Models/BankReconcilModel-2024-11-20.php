<?php

namespace App\Models;

use CodeIgniter\Model;

class BankReconcilModel extends Model
{





	public function load_bank_details($date1, $date2, $bank, $flag)
	{
		$query = $this->db->table('bank_statement')
		->select('*')
		->where('L_Code', $bank)
		// ->where('bankst_flag', $flag)
		->where("date(Date) BETWEEN '$date1' AND '$date2'")
		->get();


		return $query->getResult();
	}
	public function load_opening_balance($date1, $date2, $bank) {
		$query = $this->db->table('bank_statement')
        ->select('SUM(Dr) - SUM(Cr) as bal') // Calculate the opening balance directly
        ->where('L_Code', $bank)
        ->where('date(Date) <', $date1)
        ->get();

        return $query->getRow();
    }



    public function get_realised_sum($date1, $date2,$bank,$flag)
    {
    	$query = $this->db->table('bank_statement')
    	->select('sum(Cr) as cr,sum(Dr) as dr')
    	->where('L_Code', $bank)
    	->where('bankst_flag', $flag)
    	->where("date(Date) BETWEEN '$date1' AND '$date2'")

    	->get();


    	return $query->getResult();
    }

    public function load_post_data($date1, $date2, $bank)
    {
    	$query = $this->db->table('bank_statement')
    	->select('*')
    	->where('L_Code', '1-6-112-10782')
    	->where('New_L_Code', $bank)
    	->where('bankst_flag', 0)
    	->where("date(Date) BETWEEN '$date1' AND '$date2'")
    	->get();


    	return $query->getResult();
    }

    public function load_bank_details_summary($date1, $date2, $bank, $flag)
    {
      $query = $this->db->table('bank_statement')
      ->select('SUM(Dr) as dr, SUM(Cr) as cr, 
          COUNT(ID) as total_count, 
          COUNT(CASE WHEN Dr > 0 THEN 1 END) as count_dr_positive, 
          COUNT(CASE WHEN Cr > 0 THEN 1 END) as count_cr_positive')
      ->where('L_Code', $bank)
      ->where('bankst_flag', 1)
      ->where("date(Date) BETWEEN '$date1' AND '$date2'")
      ->get();

      return $query->getRowArray();

  }

  public function load_uncleared_summary($date1, $date2, $bank, $flag)
  {
      $query = $this->db->table('bank_statement')
      ->select('SUM(Dr) as dr, SUM(Cr) as cr, 
          COUNT(ID) as total_count, 
          COUNT(CASE WHEN Dr > 0 THEN 1 END) as count_dr_positive, 
          COUNT(CASE WHEN Cr > 0 THEN 1 END) as count_cr_positive')
      ->where('L_Code', $bank)
      ->where('bankst_flag', 0)
      ->where("date(Date) BETWEEN '$date1' AND '$date2'")
      ->get();

      return $query->getRowArray();

  }



  public function load_cleared_entries($date1, $date2, $bank, $flag)
  {
    $query = $this->db->table('bank_statement')
    ->select('*')
    ->where('L_Code', $bank)
    ->where('bankst_flag', $flag)
    ->where("date(Date) BETWEEN '$date1' AND '$date2'")
    ->get();


    return $query->getResult();
}




}
