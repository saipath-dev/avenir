<?php

namespace App\Models;

use CodeIgniter\Model;

class BankReconcilModel extends Model
{





  public function load_bank_details($date2, $bank, $flag)
  {
    $builder = $this->db->table('bank_statement');
    $builder->select('*');
    $builder->where('L_Code', $bank);
    $builder->where('bankst_flag', 0);
    

    $builder->where('Date <=', $date2); // Assuming $date1 is the end date

    $builder->orderBy('Date', 'ASC');
    
    

    $query = $builder->get();

    return $query->getResult();
  }

  public function load_opening_balance($date2, $bank) {
    $query = $this->db->table('bank_statement')
        ->select('SUM(Dr) - SUM(Cr) as bal') // Calculate the opening balance directly
        ->where('L_Code', $bank)
        ->where('Date <=', $date2)
        ->get();

        return $query->getRow();
      }

      public function load_opening_balance2($date2, $bank) {
        $query = $this->db->table('bank_statement')
        ->select('SUM(Dr) - SUM(Cr) as bal') // Calculate the opening balance directly
        ->where('L_Code', $bank)
        ->where('Date <', $date2)
        ->get();

        return $query->getRow();
      }




      public function get_realised_sum($date2,$bank,$flag)
      {
       $query = $this->db->table('bank_statement')
       ->select('sum(Cr) as cr,sum(Dr) as dr')
       ->where('L_Code', $bank)
      //  ->where('bankst_flag', $flag)
        ->where('bank_rec', $flag) // chethana
        ->where('Date <=', $date2)
        ->orderBy('Date', 'ASC')

        ->get();


        return $query->getResult();
      }

      public function load_post_data($date2, $bank)
      {
       $query = $this->db->table('bank_statement')
       ->select('*')
       ->where('L_Code', '1-6-112-10782')
       ->where('New_L_Code', $bank)
       ->where('bankst_flag', 0)
       ->where('Date <=', $date2)
       ->get();


       return $query->getResult();

     }

     public function load_bank_details_summary($date2, $bank, $flag)
     {
      $query = $this->db->table('bank_statement')
      ->select('SUM(Dr) as dr, SUM(Cr) as cr, 
        COUNT(ID) as total_count, 
        COUNT(CASE WHEN Dr > 0 THEN 1 END) as count_dr_positive, 
        COUNT(CASE WHEN Cr > 0 THEN 1 END) as count_cr_positive')
      ->where('L_Code', $bank)
      ->where('bankst_flag', 1)
      ->where('Date <=', $date2)
      ->get();

      return $query->getRowArray();

    }

    public function load_uncleared_summary($date2, $bank, $flag)
    {
      $query = $this->db->table('bank_statement')
      ->select('SUM(Dr) as dr, SUM(Cr) as cr, 
        COUNT(ID) as total_count, 
        COUNT(CASE WHEN Dr > 0 THEN 1 END) as count_dr_positive, 
        COUNT(CASE WHEN Cr > 0 THEN 1 END) as count_cr_positive')
      ->where('L_Code', $bank)
      ->where('bankst_flag', 0)
      ->where('Date <=', $date2)
      ->get();

      return $query->getRowArray();

    }



    public function load_cleared_entries( $date2, $bank, $flag)
    {
      $query = $this->db->table('bank_statement')
      ->select('*')
      ->where('L_Code', $bank)
      ->where('bankst_flag', $flag)
      ->where('Date <=', $date2)
      ->get();


      return $query->getResult();
    }




  }
