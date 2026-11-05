<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountNumbersModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\EntranceFeeModel;
use App\Models\InvoiceNumberModel;
use App\Models\SupplierModel;
use App\Models\TourQuotationsModel;
use App\Models\HotelRateModel;
use App\Models\BankModel;
use App\Models\BankBranchesModel;
use App\Models\UserModel;
use App\Models\StaffMemberModel;

class SupplierController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/master_files/supplier
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$id = $this->request->getGet('id');
		if (!empty($id)) {
			$supplierModel = new SupplierModel();
			$data['supplier'] = $supplierModel->where('ID', $id)->first();
		}

		$bankModel = new BankModel();
		$data['banks'] = $bankModel->findAll();

		return view('pages/master_files/new_supplier', $data);
	}

	public function new_driver_index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$id = $this->request->getGet('id');
		if (!empty($id)) {
			$supplierModel = new SupplierModel();
			$data['supplier'] = $supplierModel->where('ID', $id)->first();
		}

		$bankModel = new BankModel();
		$data['banks'] = $bankModel->findAll();

		return view('pages/master_files/new_driver', $data);
	}

	// get branch data - route : base_url/master_files/supplier/get_branches
	public function get_branches()
	{
		$bank = $this->request->getVar('bank_id');
		$bankBranchesModel = new BankBranchesModel();
		$data = $bankBranchesModel->where('bankID', $bank)->findAll();
		// log_message('critical',$bankBranchesModel->getLastQuery());
		return $this->response->setJSON(["status" => 200, "data" => $data]);
	}

	// get supplier data - route : base_url/master_files/supplier/get_supplier_data
	public function get_supplier_data($acc)
	{
		$supplierModel = new SupplierModel();
		$data = $supplierModel->where('Sup_Acc_No', $acc)->first();
		return $this->response->setJSON($data);
	}

	// preview next hotel code (does not increment) - route : base_url/master_files/supplier/next_hotel_code
	public function next_hotel_code()
	{
		$codes = $this->hotelCodes();
		if ($codes !== null) {
			// since 6 Oct 2026 the number comes from the sequence shared with the Hotel Master: H336 is HTL-336
			$nextNo = $codes->lastNumber() + 1;
			return $this->response->setJSON(["status" => 200, "hotel_code" => 'H' . str_pad((string) $nextNo, 3, '0', STR_PAD_LEFT), "code" => \App\Libraries\HotelRates\HotelCodes::format($nextNo)]);
		}
		$invoiceNumberModel = new InvoiceNumberModel();
		$nextNo = $invoiceNumberModel->peekNextHotelCode();
		$hotelCode = 'H' . str_pad($nextNo, 3, '0', STR_PAD_LEFT);
		return $this->response->setJSON(["status" => 200, "hotel_code" => $hotelCode]);
	}

	/** The hotel-code register of the Hotel Master, when it is installed (schema 8); null on an older installation. */
	private function hotelCodes(): ?\App\Libraries\HotelRates\HotelCodes
	{
		try {
			$codes = new \App\Libraries\HotelRates\HotelCodes(\Config\Database::connect(), (string) session()->get('username'));
			return $codes->ready() ? $codes : null;
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * The account number for a new supplier, guaranteed unused: when the counter's next number is already a supplier or a
	 * ledger account, the counter is set past the highest 6-9-… account in use and that next number is used instead.
	 *
	 * @return array{0:int, 1:string} [P_Code, L_Code]
	 */
	private function freeAccountNumber(int $pCode, string $accNo, string $prefix, AccountNumbersModel $counter): array
	{
		$db = \Config\Database::connect();
		$inUse = static function (string $acc) use ($db): bool {
			return (bool) $db->query('SELECT 1 FROM supplier WHERE Sup_Acc_No = ? LIMIT 1', [$acc])->getRowArray()
				|| (bool) $db->query('SELECT 1 FROM account_posting WHERE L_Code = ? LIMIT 1', [$acc])->getRowArray();
		};
		if (!$inUse($accNo)) {
			return [$pCode, $accNo];
		}
		$max = (int) ($db->query("SELECT GREATEST(COALESCE(MAX(P_Code), 0), (SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(Sup_Acc_No, '-', -1) AS UNSIGNED)), 0) FROM supplier WHERE Sup_Acc_No LIKE '6-9-%')) AS m FROM account_posting WHERE B_Code = 6 AND T_Code = 9")->getRowArray()['m'] ?? 0);
		$next = max($max, $pCode) + 1;
		while ($inUse($prefix . $next)) {
			$next++;
		}
		$db->query('UPDATE account_numbers SET PR_Code = ?', [$next]);
		log_message('warning', "Supplier account counter was behind: {$accNo} is already in use; moved to {$next}.");
		return [$next, $prefix . $next];
	}

	/**
	 * A new hotel supplier goes onto the Hotel Master at once (6 Oct 2026): the hotel record with its code HTL-### (the same
	 * number as its H-code), its supplier account attached, the address kept and the location matched when the address
	 * names a known one. Before this, a hotel waited for a rate sheet or an administrator's migration step to appear in the
	 * Hotel Rates panel. Any trouble here is logged and never stops the supplier from being created.
	 *
	 * @return array{id:int, code:?string}|null
	 */
	private function putHotelOnMaster(string $accNo, string $name, string $address, string $email, string $phone): ?array
	{
		try {
			$db = \Config\Database::connect();
			if (!$db->query("SHOW TABLES LIKE 'hr2_hotel'")->getRowArray()) {
				return null;                                    // the Hotel Rates module is not installed here
			}
			$svc = new \App\Libraries\HotelRates\HotelRateService($db, (string) session()->get('username'));
			$name = trim(preg_replace('/\s+/', ' ', $name));
			$hid = $svc->createHotel($name, $accNo, $svc->matchLocation($address), [
				'address'            => trim($address),
				'reservations_email' => trim($email),
				'reservations_phone' => trim($phone),
			]);
			return ['id' => $hid, 'code' => $svc->codes()->ready() ? $svc->codes()->codeOf($hid) : null];
		} catch (\Throwable $e) {
			log_message('error', 'Supplier ' . $accNo . ' created but not put on the Hotel Master: ' . $e->getMessage());
			return null;
		}
	}

	/**
	 * The H-number of a new hotel supplier. Since 6 Oct 2026 it is drawn atomically from the hotel-code sequence shared
	 * with the Hotel Master (the hotel this account becomes is HTL-### with the same number), and the old counter
	 * invoice_no.hotel_code is kept in step; on an installation without the register the old counter is used as before.
	 */
	private function nextHotelNumber(string $accNo, string $name): int
	{
		$codes = $this->hotelCodes();
		if ($codes !== null) {
			$no = $codes->reserveForAccount($accNo, $name, 'supplier-master');
			try {
				\Config\Database::connect()->query('UPDATE invoice_no SET hotel_code = GREATEST(COALESCE(hotel_code, 0), ?)', [$no]);
			} catch (\Throwable $e) {
				// the old counter is optional
			}
			return $no;
		}
		return (int) (new InvoiceNumberModel())->incrementHotelCode();
	}

	// create new supplier - route : base_url/master_files/supplier/create
	public function create()
	{
		try {
			// validation
			$validation =  \Config\Services::validation();
			$validation->setRules([
				'supplier_name' => 'required|strip_tags',
				'address' => 'required|strip_tags',
				'sup_cat' => 'required|strip_tags'

			]);

			if (!$validation->withRequest($this->request)->run()) {
				return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
			}

			$cash = 0;
			if (!empty($this->request->getVar('cash'))) {
				$cash = htmlspecialchars(strip_tags($this->request->getVar('cash')), ENT_QUOTES, 'UTF-8');
			}

			$cheque = 0;
			if (!empty($this->request->getVar('cheque'))) {
				$cheque = htmlspecialchars(strip_tags($this->request->getVar('cheque')), ENT_QUOTES, 'UTF-8');
			}

			$bulk_payment = 0;
			if (!empty($this->request->getVar('bulk_payment'))) {
				$bulk_payment = 1;
			}

			// check if there is a supplier with the same name and sup_cat
			$supplierModel = new SupplierModel();
			$supplierExists = $supplierModel->where('Name', $this->request->getVar('supplier_name'))->where('sup_cat', $this->request->getVar('sup_cat'))->countAllResults();
			if ($supplierExists > 0) {
				return $this->response->setJSON(["status" => 400, "message" => "Please choose a different supplier name."]);
			}

			// sup_bank_acc already exists (only validate if bank account is provided)
			$supBankAcc = $this->request->getVar('sup_bank_acc');
			if (!empty($supBankAcc)) {
				$supplierExists = $supplierModel->where('sup_bank_acc', $supBankAcc)->where('bank_id', $this->request->getVar('bank_id'))->countAllResults();
				if ($supplierExists > 0) {
					return $this->response->setJSON(["status" => 400, "message" => "Supplier with the same bank account already exists."]);
				}
			}

			$accountNumbersModel = new AccountNumbersModel();
			$nextPRCode = $accountNumbersModel->incrementPRCode();

			$address = $this->request->getVar('address');
			// if address has ', Sri Lanka' remove it
			if (strpos($address, ', Sri Lanka') !== false) {
				$address = str_replace(', Sri Lanka', '', $address);
			}

			$sup_cat = $this->request->getVar('sup_cat');

			if ($sup_cat == 3) {
				$acc_no = '6-9-23-' . $nextPRCode;
			} else {
				$acc_no = '6-9-11-' . $nextPRCode;
			}
			// 6 Oct 2026: the counter can fall behind the ledger (a restored copy, a hand edit) — then the "next" number is one
			// that already exists, and the old code would have put a second supplier on another supplier's account.
			// Now the counter is moved past the highest account in use and the next free number is taken, once, and logged.
			[$nextPRCode, $acc_no] = $this->freeAccountNumber($nextPRCode, $acc_no, $sup_cat == 3 ? '6-9-23-' : '6-9-11-', $accountNumbersModel);

			$hotel_code = null;
			if ($sup_cat == 1) {
				$nextHotelNo = $this->nextHotelNumber($acc_no, (string) $this->request->getVar('supplier_name'));
				$hotel_code = 'H' . str_pad((string) $nextHotelNo, 3, '0', STR_PAD_LEFT);
			}

			$dlimg = $this->request->getFile('dlimg');
			$dlimgName = '';
			if (!empty($dlimg) && $dlimg->isValid() && !$dlimg->hasMoved()) {
				$dlimgName = $dlimg->getRandomName();
				$uploadPath = FCPATH . 'uploads/driver/license';
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$dlimg->move($uploadPath, $dlimgName);
			}

			$glimg = $this->request->getFile('glimg');
			$glimgName = '';
			if (!empty($glimg) && $glimg->isValid() && !$glimg->hasMoved()) {
				$glimgName = $glimg->getRandomName();
				$uploadPath = FCPATH . 'uploads/driver/license/guide';
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$glimg->move($uploadPath, $glimgName);
			}

			$vimg = $this->request->getFile('vimg');
			$vimgName = '';
			if (!empty($vimg) && $vimg->isValid() && !$vimg->hasMoved()) {
				$vimgName = $vimg->getRandomName();
				$uploadPath = FCPATH . 'uploads/vehicle/';
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$vimg->move($uploadPath, $vimgName);
			}

			$vlimg = $this->request->getFile('vlimg');
			$vlimgName = '';
			if (!empty($vlimg) && $vlimg->isValid() && !$vlimg->hasMoved()) {
				$vlimgName = $vlimg->getRandomName();
				$uploadPath = FCPATH . 'uploads/vehicle/license';
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$vlimg->move($uploadPath, $vlimgName);
			}

			$viimg = $this->request->getFile('viimg');
			$viimgName = '';
			if (!empty($viimg) && $viimg->isValid() && !$viimg->hasMoved()) {
				$viimgName = $viimg->getRandomName();
				$uploadPath = FCPATH . 'uploads/vehicle/insurance';
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$viimg->move($uploadPath, $viimgName);
			}

			$language = $this->request->getVar('language');
			$languageStr = is_array($language) ? implode(',', $language) : $language;

			$data = array(

				'Name' => $this->request->getVar('supplier_name'),
				'Address' => $address,
				'NIC' => $this->request->getVar('nic'),
				'TP' => $this->request->getVar('tel_home'),
				'Mobile' => $this->request->getVar('tel_mobile'),
				'cr_lmt' => $this->request->getVar('credit_limit'),
				'cr_dyz' => $this->request->getVar('credit_days'),
				'rep_offce' =>  $this->request->getVar('rep_office'),
				'rep_mobile' =>  $this->request->getVar('rep_mobile'),
				'csh' =>  $cash,
				'chq' =>  $cheque,
				'bulk_payment' => $bulk_payment,
				'sup_cat' =>  $this->request->getVar('sup_cat'),
				'Sup_Acc_No' => $acc_no,
				'hotel_code' => $hotel_code,
				'email' =>  $this->request->getVar('email'),
				'sup_bank_acc' =>  $this->request->getVar('sup_bank_acc'),
				'bank_id' =>  $this->request->getVar('bank_id'),
				'bank_name' =>  $this->request->getVar('bank_name'),
				'branch_id' =>  $this->request->getVar('branch_id'),
				'branch_name' =>  $this->request->getVar('branch_name'),
				'remark' =>  $this->request->getVar('remark'),
				'create_user' => session()->get('username'),
				'dlimg' => $dlimgName,
				'glimg' => $glimgName,
				'dled' => $this->request->getVar('dled'),
				'gled' => $this->request->getVar('gled'),
				'language' => $languageStr,
				'acc_holder' => $this->request->getVar('bank_acc_holder'),
				'type' => $this->request->getVar('type'),
				'passenger_count' => $this->request->getVar('passenger_count'),
				'km_rate' => $this->request->getVar('km_rate'),
				'v_num' => $this->request->getVar('vnu'),
				'bata' => $this->request->getVar('bata_per_day'),
				'action' => !empty($this->request->getVar('rate_vehicle')) ? 1 : 0,
				'vimg' => $vimgName,
				'vlimg' => $vlimgName,
				'vehi_license_expire_date' => $this->request->getVar('vled') ?: null,
				'viimg' => $viimgName,
				'vehi_insurance_expire_date' => $this->request->getVar('vied') ?: null
			);

			if ($supplierModel->insert($data)) {
				// log_message('critical', 'New supplier created with ID: ' . $supplierModel->getLastQuery());
				$accountPostingMode = new AccountPostingModel();

				if ($sup_cat == 3) {
					$accountPostingMode->insert([
						'P_Code' => $nextPRCode,
						'PName' => $this->request->getVar('supplier_name'),
						'B_Code' => 6,
						'T_Code' => 9,
						'H_Code' => 23,
						'L_Code' => '6-9-23-' . $nextPRCode
					]);
				} else {
					$accountPostingMode->insert([
						'P_Code' => $nextPRCode,
						'PName' => $this->request->getVar('supplier_name'),
						'B_Code' => 6,
						'T_Code' => 9,
						'H_Code' => 11,
						'L_Code' => '6-9-11-' . $nextPRCode
					]);
				}



				$onMaster = $sup_cat == 1 ? $this->putHotelOnMaster($acc_no, (string) $this->request->getVar('supplier_name'), (string) $address, (string) $this->request->getVar('email'), (string) $this->request->getVar('tel_home')) : null;
				return $this->response->setJSON(["status" => 201, "message" => "Supplier Added successfully!" . ($onMaster ? " Hotel code {$onMaster['code']} — the hotel is on the Hotel Master." : ''), "hotel" => $onMaster]);
			}
			throw new \Exception("Supplier creation failed!");
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	// all suppliers - route : base_url/master_files/supplier/all
	public function all()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$cat = $this->request->getGet('cat');
		$data['cat'] = $cat;

		$supplierModel = new SupplierModel();

		if (!empty($cat)) {
			if ($cat != 99) {
				$data['suppliers'] = $supplierModel->where('sup_cat', $cat)->where('sup_cat !=', 2)->findAll();
			} else {
				$data['suppliers'] = $supplierModel->where('sup_cat !=', 2)->findAll();
			}
		} else {
			$data['suppliers'] = $supplierModel->where('sup_cat !=', 2)->findAll();
		}
		return view('pages/master_files/all_suppliers', $data);
	}

	public function all_drivers()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$supplierModel = new SupplierModel();
		$data['suppliers'] = $supplierModel->where('sup_cat =', 2)->findAll();

		return view('pages/master_files/all_drivers', $data);
	}

	// update - route : base_url/master_files/supplier/update/{id}
	public function update($id = null)
	{
		try {
			// validation
			$validation =  \Config\Services::validation();
			$validation->setRules([
				'supplier_name' => 'required|strip_tags',
				'address' => 'required|strip_tags',
				'sup_cat' => 'required|strip_tags',
				'Acc_No' => 'required|strip_tags'
			]);

			if (!$validation->withRequest($this->request)->run()) {
				return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
			}

			$cash = 0;
			if (!empty($this->request->getVar('cash'))) {
				$cash = htmlspecialchars(strip_tags($this->request->getVar('cash')), ENT_QUOTES, 'UTF-8');
			}

			$cheque = 0;
			if (!empty($this->request->getVar('cheque'))) {
				$cheque = htmlspecialchars(strip_tags($this->request->getVar('cheque')), ENT_QUOTES, 'UTF-8');
			}

			$bulk_payment = 0;
			if (!empty($this->request->getVar('bulk_payment'))) {
				$bulk_payment = 1;
			}

			$address = $this->request->getVar('address');
			// if address has ', Sri Lanka' remove it
			if (strpos($address, ', Sri Lanka') !== false) {
				$address = str_replace(', Sri Lanka', '', $address);
			}

			$supplierModel = new SupplierModel();
			$supplierExists = $supplierModel->where('Name', $this->request->getVar('supplier_name'))->where('sup_cat', $this->request->getVar('sup_cat'))->where('ID !=', $id)->countAllResults();
			if ($supplierExists > 0) {
				return $this->response->setJSON(["status" => 400, "message" => "Please choose a different supplier name."]);
			}

			// The edit form posts the bank as `bank` and the branch as `branch`; an empty choice
			// arrives as '' and the branch placeholder as its text ("Select Branch"), neither of
			// which is an id. Read them once here so the duplicate check and the saved row agree.
			$bankId   = $this->request->getVar('bank') ?? $this->request->getVar('bank_id');
			$branchId = $this->request->getVar('branch') ?? $this->request->getVar('branch_id');
			$bankId   = is_numeric($bankId) && (int) $bankId > 0 ? (int) $bankId : null;
			$branchId = is_numeric($branchId) && (int) $branchId > 0 ? (int) $branchId : null;

			// sup_bank_acc already exists (only when a bank account is given, as create() does:
			// with no account the check used to match every other supplier without one and
			// refused the edit)
			$supBankAcc = $this->request->getVar('sup_bank_acc');
			if (!empty($supBankAcc)) {
				$supplierExists = $supplierModel->where('sup_bank_acc', $supBankAcc)->where('bank_id', $bankId)->where('ID !=', $id)->countAllResults();
				if ($supplierExists > 0) {
					return $this->response->setJSON(["status" => 400, "message" => "Supplier with the same bank account already exists."]);
				}
			}

			$dlimg = $this->request->getFile('dlimg');
			$dlimgName = '';
			if (
				!empty($dlimg) && $dlimg->isValid() && !$dlimg->hasMoved()
			) {
				$dlimgName = $dlimg->getRandomName();
				$uploadPath = FCPATH . 'uploads/driver/license';
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$dlimg->move($uploadPath, $dlimgName);
			}

			$glimg = $this->request->getFile('glimg');
			$glimgName = '';
			if (
				!empty($glimg) && $glimg->isValid() && !$glimg->hasMoved()
			) {
				$glimgName = $glimg->getRandomName();
				$uploadPath = FCPATH . 'uploads/driver/license/guide';
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$glimg->move($uploadPath, $glimgName);
			}

			$vimg = $this->request->getFile('vimg');
			$vimgName = '';
			if (!empty($vimg) && $vimg->isValid() && !$vimg->hasMoved()) {
				$vimgName = $vimg->getRandomName();
				$uploadPath = FCPATH . 'uploads/vehicle/';
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$vimg->move($uploadPath, $vimgName);
			}

			$vlimg = $this->request->getFile('vlimg');
			$vlimgName = '';
			if (!empty($vlimg) && $vlimg->isValid() && !$vlimg->hasMoved()) {
				$vlimgName = $vlimg->getRandomName();
				$uploadPath = FCPATH . 'uploads/vehicle/license';
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$vlimg->move($uploadPath, $vlimgName);
			}

			$viimg = $this->request->getFile('viimg');
			$viimgName = '';
			if (!empty($viimg) && $viimg->isValid() && !$viimg->hasMoved()) {
				$viimgName = $viimg->getRandomName();
				$uploadPath = FCPATH . 'uploads/vehicle/insurance';
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$viimg->move($uploadPath, $viimgName);
			}


			$data = array(
				'Name' => $this->request->getVar('supplier_name'),
				'Address' => $this->request->getVar('address'),
				'NIC' => $this->request->getVar('nic'),
				'TP' => $this->request->getVar('tel_home'),
				'Mobile' => $this->request->getVar('tel_mobile'),
				'cr_lmt' => $this->request->getVar('credit_limit'),
				'cr_dyz' => $this->request->getVar('credit_days'),
				'rep_offce' =>  $this->request->getVar('rep_office'),
				'rep_mobile' =>  $this->request->getVar('rep_mobile'),
				'csh' =>  $cash,
				'chq' =>  $cheque,
				'bulk_payment' => $bulk_payment,
				'sup_cat' =>  $this->request->getVar('sup_cat'),
				'email' =>  $this->request->getVar('email'),
				'sup_bank_acc' =>  $this->request->getVar('sup_bank_acc'),
				'bank_id' =>  $bankId,
				'bank_name' =>  $bankId === null ? null : $this->request->getVar('bank_name'),
				'branch_id' =>  $branchId,
				'branch_name' =>  $branchId === null ? null : $this->request->getVar('branch_name'),
				'remark' =>  $this->request->getVar('remark'),
				'create_user' => session()->get('username'),
				'dled' => $this->request->getVar('dled'),
				'gled' => $this->request->getVar('gled'),
				'language' => $this->request->getVar('language'),
				'acc_holder' => $this->request->getVar('bank_acc_holder'),
				'type' => $this->request->getVar('type'),
				'passenger_count' => $this->request->getVar('passenger_count'),
				'km_rate' => $this->request->getVar('km_rate'),
				'v_num' => $this->request->getVar('vnu'),
				'bata' => $this->request->getVar('bata_per_day'),
				'action' => !empty($this->request->getVar('rate_vehicle')) ? 1 : 0,
				'vehi_license_expire_date' => $this->request->getVar('vled') ?: null,
				'vehi_insurance_expire_date' => $this->request->getVar('vied') ?: null
			);
			if (!empty($dlimgName)) {
				$data['dlimg'] = $dlimgName;
			}
			if (!empty($glimgName)) {
				$data['glimg'] = $glimgName;
			}
			if (!empty($vimgName)) {
				$data['vimg'] = $vimgName;
			}
			if (!empty($vlimgName)) {
				$data['vlimg'] = $vlimgName;
			}
			if (!empty($viimgName)) {
				$data['viimg'] = $viimgName;
			}

			$supplierModel = new SupplierModel();
			if ($supplierModel->update($id, $data)) {

				$accountPostingModel = new AccountPostingModel();
				$accountPostingModel->set(['PName' => $this->request->getVar('supplier_name')])->where('L_Code', $this->request->getVar('Acc_No'))->update();

				return $this->response->setJSON(["status" => 200, "message" => "Supplier updated successfully!"]);
			}
			throw new \Exception("Supplier update failed!");
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	// delete - route : base_url/master_files/supplier/delete
	public function delete()
	{
		try {
			$accountRunningModel = new AccountRunningModel();
			$accHasRecords = $accountRunningModel->where('L_Code', $this->request->getVar('Acc_No'))->countAllResults();

			$tourQuatationModel = new TourQuotationsModel();
			$tourQuatationHasRecords = $tourQuatationModel->where('hotel_acc', $this->request->getVar('Acc_No'))->countAllResults();

			if ($accHasRecords > 0 || $tourQuatationHasRecords > 0) {
				return $this->response->setJSON(["status" => 400, "message" => "Cannot delete this supplier. This supplier has transactions!"]);
			}

			$supplierModel = new SupplierModel();

			if ($supplierModel->where('Sup_Acc_No', $this->request->getVar('Acc_No'))->delete()) {

				$accountPostingModel = new AccountPostingModel();
				$accountPostingModel->where('L_Code', $this->request->getVar('Acc_No'))->delete();

				$hotelRatesModel = new HotelRateModel();
				$hotelRatesModel->where('hotel', $this->request->getVar('Acc_No'))->delete();

				return $this->response->setJSON(["status" => 200, "message" => "Supplier deleted successfully!"]);
			}
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	// toggle login status - route : base_url/master_files/supplier/toggle_login
	public function toggle_login()
	{
		try {
			$supplierId = $this->request->getVar('supplier_id');
			$status = $this->request->getVar('status');

			if (empty($supplierId)) {
				return $this->response->setJSON(["status" => 400, "message" => "Supplier ID is required!"]);
			}

			$supplierModel = new SupplierModel();
			$supplier = $supplierModel->find($supplierId);

			if (!$supplier) {
				return $this->response->setJSON(["status" => 404, "message" => "Supplier not found!"]);
			}

			$data = ['is_login_enabled' => $status];

			// If enabling login, get username and password
			if ($status == 1) {
				$username = $this->request->getVar('username');
				$password = $this->request->getVar('password');

				if (empty($username) || empty($password)) {
					return $this->response->setJSON(["status" => 400, "message" => "Username and password are required to enable login!"]);
				}

				$model = new UserModel();
				$rowCount = $model->where('user', $username)->countAllResults();
				if ($rowCount > 0) {
					// Conflict
					return $this->response->setJSON(["status" => 409, "message" => "Username already exists. Please choose a different one."]);
				}
				//i need to generate $employee_id randam 8 digit 
				$employee_id = rand(10000000, 99999999); 

				$staffMemberModel = new StaffMemberModel();

				// check if emp_num exists, if exists generate new one
				while ($staffMemberModel->where('emp_num', $employee_id)->countAllResults() > 0) {
					$employee_id = rand(10000000, 99999999);
				}

				$data_emp = [
					'emp_num' => $employee_id,
					'empno' => $employee_id,
					'Name' => $supplier['Name'],
					'Acc_No' => $supplier['Sup_Acc_No'],
					'Tp' => $supplier['Mobile'],
					'Nic' => $supplier['NIC']
				];

				$staffMemberModel->insert($data_emp);

				$staffMember = $staffMemberModel->select('emp_num, Acc_No')->where('emp_num', $employee_id)->findAll(1);

				// Set username as NIC number
				$data['username'] = $supplier['NIC'];
				// Hash the password for security
				$data['password'] = password_hash($password, PASSWORD_DEFAULT);

				// $password = password_hash($password, PASSWORD_DEFAULT);

				$data_user = [
					'group_id' => 24,
					'user' => $data['username'],
					'pass' => $data['password'],
					'emp_id' => $staffMember[0]['emp_num'],
					'acc_no' => $staffMember[0]['Acc_No'],
					'access' => 1,
					'user_role' => 1
				];

				$model->insert($data_user);
			} else {
				// If disabling login, clear username and password
				$data['username'] = null;
				$data['password'] = null;
			}

			if ($supplierModel->update($supplierId, $data)) {
				$message = $status == 1 ? "Login enabled successfully! Username set to NIC number." : "Login disabled successfully!";
				return $this->response->setJSON(["status" => 200, "message" => $message]);
			}

			throw new \Exception("Failed to update login status!");
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	// toggle availability status - route : base_url/master_files/supplier/toggle_availability
	public function toggle_availability()
	{
		try {
			$supplierId = $this->request->getVar('supplier_id');
			$status = $this->request->getVar('status');

			if (empty($supplierId)) {
				return $this->response->setJSON(["status" => 400, "message" => "Supplier ID is required!"]);
			}

			$supplierModel = new SupplierModel();
			$supplier = $supplierModel->find($supplierId);

			if (!$supplier) {
				return $this->response->setJSON(["status" => 404, "message" => "Supplier not found!"]);
			}

			$data = ['is_avalible' => $status];
			$data['unavailable_since'] = ($status == 0) ? date('Y-m-d') : null;

			if ($supplierModel->update($supplierId, $data)) {
				$message = $status == 1 ? "Supplier marked as available!" : "Supplier marked as unavailable!";
				return $this->response->setJSON(["status" => 200, "message" => $message]);
			}

			throw new \Exception("Failed to update availability status!");
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}
}
