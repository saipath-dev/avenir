<?php

namespace App\Models;

use CodeIgniter\Model;

class VoucherPaymentModel extends Model
{
	protected $table = 'voucher';
	protected $allowedFields = ['Acc_No', 'Pay', 'C_Date', 'W_Date', 'C_Number', 'Amount', 'print', 'des', 'Address', 'Bank', 'Vnum', 'S_name', 'inv', 'flag', 'Bank_acc', 'dv', 'vcat', 'login', 'bookt', 'Aflag', 'app_user', 'Aut_user', 'type', 'rej_user', 'printflg', 'sup', 'VAT', 'VAT_Amt', 'vat_m', 'nbt', 'nbt_amt', 'nbt_m', 'g_ttl', 'v_typ', 'ref_po', 'stmp_d', 'V_num', 'ref_account', 'ref_acc_no', 'wht_active', 'wht_p', 'wht_amount', 'aut_date', 'Recommendation', 'emp_code', 'rej', 'rejlogin', 'rejdate', 'aut_login', 'print_login', 'vnumber', 'insflag', 'vcher', 'unrlz_acc', 'is_expenses', 'acc_run_id', 'advances_pay', 'is_confirm', 'confirm_user', 'confirm_date', 'doller_amt', 'is_special', 'finalized', 'finalized_user', 'finalized_date', 'bank_date', 'remark'];

	protected $returnType = 'array';

	// Special Note
	// advances_pay = 1 transport advance (expenses)
	// advances_pay = 11 transport settlement 
	// advances_pay = 2 hotel advance

	public function fetch_data($type, $query)
	{
		$query = trim($query); // Trim any whitespace around the query

		$builder = $this->db->table('acc_selct');

		if ($type == 2) {
			$builder->groupStart()
				->like('L_Code', '6-9-11-', 'after')
				->orLike('L_Code', '6-9-23-', 'after')
				->groupEnd()
				->groupStart()
				->like('PName', $query)
				->orLike('TName', $query)
				->orLike('HName', $query)
				->orLike('BName', $query)
				->groupEnd();
		} else {
			$builder->groupStart()
				->like('L_Code', $query)
				->orLike('PName', $query)
				->orLike('TName', $query)
				->orLike('HName', $query)
				->orLike('BName', $query)
				->groupEnd();
		}

		// get last query
		// log_message('critical', 'fetch_data: ' . $query);
		// Log the SQL query before execution
		// log_message('critical', 'fetch_data SQL: ' . $builder->getCompiledSelect(false));

		$query = $builder->get();  // Execute the query

		if ($query->getResult()) { // Check if there are results
			return $query->getResultArray(); // Return as array of results
		} else {
			return false; // Return false if no results found
		}
	}



	public function load_vouchers($tour_id, $vouchernumber = null)
	{
		// Get user ID from session and sanitize it
		$user = strip_tags(session()->get('emp_id'));

		// Start the query builder
		$query = $this->db->table('voucher')
			->select('voucher.id, Acc_No, Pay,voucher.sup, C_Date, W_Date, C_Number AS Chq_No, voucher.Amount, voucher.des AS Discription, Address, Bank, Vnum As Voucher, S_name As Account_Name, inv AS Inv_no, flag, Bank_acc, dv, vcat, voucher.login, AFlag, v_typ AS Type, app_user AS Requested_By, Type AS Pay_Type')
			// ->join('v_inv', 'voucher.Vnum = v_inv.V_tkn')
			->where('Aflag', 0)
			// 		->groupStart() // Start grouping conditions
			// 		->where('voucher.rej !=', 1)
			// 		->orWhere('voucher.rej IS NULL')
			// ->groupEnd() // End grouping conditions
			// ->Where('voucher.rej IS NULL')

			// ->groupBy('Vnum, v_inv.Invoice')
			->orderBy('Vnum', 'asc');


		if ($tour_id !== 'other') {
			if ($tour_id == 'petty') {
				$query->where('type', 'Petty Cash');
				// $query->where('is_special', 0);
			} else if ($tour_id == 'special') {
				$query->where('is_special', 1);
			} else {
				$query->where('C_Number', $tour_id);
				$query->where('type <>', 'Petty Cash');
				// $query->where('is_special', 0);
			}
		} else if ($tour_id == 'other') {
			$query->where('voucher.is_expenses !=', 1);
			$query->where('type <>', 'Petty Cash');
			$query->where('is_special', 0);
		}

		// if voucher number is provided, add it to the query
		if (!empty($vouchernumber)) {
			$query->where('Vnum', $vouchernumber);
		}


		return $query->get()->getResult();
	}

	// All pending vouchers (excluding Petty Cash), flat list ordered by voucher number ascending
	public function load_all_vouchers($vouchernumber = null)
	{
		$query = $this->db->table('voucher')
			->select('voucher.id, voucher.Acc_No, voucher.Pay, voucher.sup, voucher.C_Date, voucher.W_Date, voucher.C_Number AS Chq_No, voucher.Amount, voucher.des AS Discription, voucher.Address, voucher.Bank, voucher.Vnum As Voucher, voucher.S_name As Account_Name, voucher.inv AS Inv_no, voucher.flag, voucher.Bank_acc, voucher.dv, voucher.vcat, voucher.login, voucher.AFlag, voucher.v_typ AS Type, voucher.app_user AS Requested_By, voucher.v_typ AS Pay_Type, consignee.manual_id')
			->join('consignee', 'consignee.tour_id = voucher.C_Number', 'left')
			->where('voucher.Aflag', 0)
			->where('voucher.type <>', 'Petty Cash')
			->orderBy('voucher.Vnum', 'asc');

		if (!empty($vouchernumber)) {
			$query->where('voucher.Vnum', $vouchernumber);
		}

		return $query->get()->getResult();
	}


	public function load_vouchers_pending($tour_id, $vouchernumber = null)
	{
		// Get user ID from session and sanitize it
		$user = strip_tags(session()->get('emp_id'));

		// Start the query builder
		$query = $this->db->table('voucher')
			->select('voucher.id, Acc_No, Pay,voucher.sup, C_Date, W_Date, C_Number AS Chq_No, voucher.Amount, voucher.des AS Discription, Address, Bank, Vnum As Voucher, S_name As Account_Name, inv AS Inv_no, flag, Bank_acc, dv, vcat, voucher.login, AFlag, v_typ AS Type, app_user AS Requested_By, Type AS Pay_Type')
			// ->join('v_inv', 'voucher.Vnum = v_inv.V_tkn')
			->where('Aflag', 0)
			// 		->groupStart() // Start grouping conditions
			// 		->where('voucher.rej !=', 1)
			// 		->orWhere('voucher.rej IS NULL')
			// ->groupEnd() // End grouping conditions
			// ->Where('voucher.rej IS NULL')

			// ->groupBy('Vnum, v_inv.Invoice')
			->orderBy('Vnum', 'asc');


		if ($tour_id !== 'other') {
			// if ($tour_id == 'petty') {
			// 	$query->where('type', 'Petty Cash');
			// 	// $query->where('is_special', 0);
			// } else {
				$query->where('C_Number', $tour_id);
				// $query->where('type <>', 'Petty Cash');
				// $query->where('is_special', 0);
			// }
		} else if ($tour_id == 'other') {
			$query->where('voucher.is_expenses !=', 1);
			// $query->where('type <>', 'Petty Cash');
			// $query->where('is_special', 0);
		}

		// if voucher number is provided, add it to the query
		if (!empty($vouchernumber)) {
			$query->where('Vnum', $vouchernumber);
		}


		return $query->get()->getResult();
	}

	public function confirmed_vouchers($tour_id)
	{
		// Get user ID from session and sanitize it
		$user = strip_tags(session()->get('emp_id'));

		// Start the query builder
		$query = $this->db->table('voucher')
			->select('voucher.id, Acc_No, Pay,voucher.sup, C_Date, W_Date, C_Number AS Chq_No, voucher.Amount, voucher.des AS Discription, Address, Bank, Vnum As Voucher, S_name As Account_Name, inv AS Inv_no, flag, Bank_acc, dv, vcat, voucher.login, AFlag, v_typ AS Type, app_user AS Requested_By, Type AS Pay_Type')
			// ->join('v_inv', 'voucher.Vnum = v_inv.V_tkn')
			->where('Aflag', 1)
			->where('is_confirm', 0)
			// 		->groupStart() // Start grouping conditions
			// 		->where('voucher.rej !=', 1)
			// 		->orWhere('voucher.rej IS NULL')
			// ->groupEnd() // End grouping conditions
			// ->Where('voucher.rej IS NULL')

			// ->groupBy('Vnum, v_inv.Invoice')
			->orderBy('Vnum', 'asc');


		if ($tour_id !== 'other') {
			$query->where('C_Number', $tour_id);
		} else if ($tour_id == 'other') {
			$query->where('voucher.is_expenses !=', 1);
			// $query->where('is_special', 0);
		}


		return $query->get()->getResult();
	}


	public function load_vouchers_new()
	{
		// Get user ID from session and sanitize it
		$user = strip_tags(session()->get('username'));

		// Start the query builder
		$query = $this->db->table('voucher')
			->select('voucher.id, Acc_No, Pay,voucher.sup, C_Date, W_Date, C_Number AS Chq_No, voucher.Amount, voucher.des AS Discription, Address, Bank, Vnum As Voucher, S_name As Account_Name, inv AS Inv_no, flag, Bank_acc, dv, vcat, voucher.login, AFlag, v_typ AS Type, app_user AS Requested_By, Type AS Pay_Type, v_inv.Description, v_inv.Invoice,v_inv.V_tkn')
			->join('v_inv', 'voucher.Vnum = v_inv.V_tkn')
			->where('Aflag', 0)
			// ->where('voucher.rej !=', 1)
			// ->where('voucher.login', $user)

			->groupBy('Vnum, v_inv.Invoice')
			->orderBy('Vnum', 'asc');





		return $query->get()->getResult();
	}

	public function load_voucher_details($id)
	{
		// $user = strip_tags(session()->get('emp_id'));

		$query = $this->db->table('voucher')
			->select('voucher.C_Number,voucher.Aflag,voucher.Amount as v_amount,voucher.Vnum,voucher.login as login_name,voucher.Aut_user as Aut_user,voucher.des,voucher.S_name,voucher.Bank,voucher.ref_po,voucher.C_Date')
			// ->join('v_inv', 'voucher.Vnum = v_inv.V_tkn')
			->where('voucher.Vnum', $id)
			// ->where('voucher.login', $user)
			// ->groupBy('Vnum')
			// ->orderBy('Vnum', 'asc')
			->get();

		return $query->getRow();
	}

	// public function load_voucher_details_manual()
	// {
	// // $user = strip_tags(session()->get('emp_id'));

	// $query = $this->db->table('voucher')
	// ->select('voucher.C_Number,voucher.Aflag,voucher.Amount as v_amount,voucher.Vnum,voucher.des,voucher.S_name,voucher.Bank,voucher.ref_po,voucher.Bank_acc,v_inv.*,voucher.C_Date')
	// ->join('v_inv', 'voucher.Vnum = v_inv.V_tkn')
	// // ->where('v_inv.V_tkn', $id)
	// ->where('Description LIKE', 'Hotel Reservation%')
	// 	// ->where('voucher.login', $user)
	// 	// ->groupBy('Vnum')
	// 	// ->orderBy('Vnum', 'asc')
	// ->get();

	// return $query->getResultArray();

	public function load_voucher_details_manual()
	{
		// Get user ID from session and sanitize it
		$user = strip_tags(session()->get('emp_id'));

		// Start the query builder
		$query = $this->db->table('voucher')
			->select('voucher.id, Acc_No, Pay,voucher.sup, C_Date, W_Date, C_Number AS Chq_No, voucher.Amount, voucher.des AS Discription, Address, Bank, Vnum As Voucher, S_name As Account_Name, inv AS Inv_no, flag, Bank_acc, dv, vcat, voucher.login, AFlag, v_typ AS Type, app_user AS Requested_By, Type AS Pay_Type, v_inv.Description, v_inv.Invoice')
			->join('v_inv', 'voucher.Vnum = v_inv.V_tkn')
			->where('Aflag', 0)
			->where('Description LIKE', 'Hotel Reservation%')

			->groupBy('Vnum, v_inv.Invoice')
			->orderBy('Vnum', 'asc');

		// Apply additional filter if $tour_id is not null
		// if ($tour_id !== 'other') {
		// 	$query->where('C_Number', $tour_id);
		// }else if($tour_id=='other'){
		// 	$query->where('voucher.is_expenses !=', 1);

		// }

		// Execute the query and return the result
		return $query->get()->getResultArray();
	}
	// }

	public function voucher($id)
	{
		$query = $this->db->table('voucher')
			->select('voucher.*,v_inv.Description,v_inv.V_tkn,v_inv.account,v_inv.Payee,v_inv.Amount as sup_amount,v_inv.Invoice')
			->join('v_inv', 'voucher.Vnum = v_inv.V_tkn')
			->where('voucher.id', $id)
			->get();

		// $voucher = $this->db->get();

		return $query->getResult();
	}

	public function getSupplierDetails($id)
	{
		// Fetch the initial results from account_runing and supplier
		$query = $this->db->table('account_runing')
			->select('
			SUM(Dr) as dr, 
			SUM(Cr) as cr, 
			MAX(Discription) as des, 
			SUM(Cr) - SUM(Dr) as bal, 
			MAX(Recpt_No) as voucher_no, 
			account_runing.L_Code as lcode, 
			MAX(Date) as date, 
			COALESCE(supplier.Name, acc_selct.PName) as supplier_name, 
			Invoice_Num
			')
			->join('supplier', 'supplier.Sup_Acc_No = account_runing.L_Code')
			->join('acc_selct', 'acc_selct.L_Code = account_runing.L_Code', 'left')
			->where('account_runing.L_Code', $id)
			->groupBy('account_runing.Invoice_Num, account_runing.L_Code')
			->get()
			->getResultArray();

		// log_message('critical', 'getSupplierDetails1 Query: ' . $this->db->getLastQuery());
		// log_message('critical', 'getSupplierDetails1 Results: ' . json_encode($query));

		// Initialize an array to hold the final results with total amounts
		$results = [];

		// Loop through the results and fetch the total amount from the voucher table
		foreach ($query as $row) {
			// Fetch the total amount from the voucher table for each row
			$voucherQuery = $this->db->table('v_inv')
				->select('SUM(Amount) as total_amount')
				->where('account', $row['lcode'])
				->where('Invoice', $row['Invoice_Num'])
				->where('Invoice', $row['Invoice_Num'])
				// ->where('V_tkn', $row['voucher_no'])
				->where('Date', $row['date'])
				->where('RCType', 0)
				->get()
				->getRowArray();

			// Add the total amount to the current row
			$row['total_amount'] = $voucherQuery['total_amount'] ?? 0; // Default to 0 if no result

			// Append the row to the final results
			$results[] = $row;
		}


		return $results; // Return the final results
	}




	public function fetch_filter_data($query)
	{
		$query = trim($query); // Ensure query is trimmed

		// Ensure query is not empty after trimming
		if (empty($query)) {
			log_message('critical', 'fetch_filter_data: Empty query' . $query);
			return false; // Return false if query is empty
		}

		// Build the query
		$builder = $this->db->table('acc_selct');
		$builder->groupStart()
			->like('L_Code', $query)
			->orLike('PName', $query)
			->orLike('TName', $query)
			->orLike('HName', $query)
			->orLike('BName', $query)
			->groupEnd();

		$result = $builder->get();  // Execute the query

		// Log the actual query
		// log_message('critical', 'fetch_filter_data Query: ' . $this->db->getLastQuery());

		// Check for results and return them
		if ($result->getNumRows() > 0) { // If results exist
			return $result->getResultArray(); // Return result as an array
		} else {
			return false; // Return false if no results found
		}
	}
}
