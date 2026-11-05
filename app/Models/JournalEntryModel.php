<?php

namespace App\Models;

use CodeIgniter\Model;

class JournalEntryModel extends Model
{
	

	public function fetch_data($query)
	{
		$builder = $this->db->table('acc_selct');


			$builder->groupStart()
			// ->like('L_Code', $query)
			->Like('PName', $query)
			// ->orLike('TName', $query)
			// ->orLike('HName', $query)
			// ->orLike('BName', $query)
			->groupEnd();
		// }

    $query = $builder->get();  // Execute the query
    // log_message('critical', $this->db->getLastQuery());

    if ($query->getResult()) { // Check if there are results
        return $query->getResultArray(); // Return as array of results
    } else {
        return false; // Return false if no results found
    }
}




}
