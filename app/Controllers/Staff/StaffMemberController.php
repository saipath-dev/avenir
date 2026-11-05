<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountNumbersModel;
use App\Models\AccountPostingModel;
use App\Models\StaffMemberModel;

class StaffMemberController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/staff/registration
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$data['type'] = "insert";

		$id = $this->request->getGet('id');
		if ($id != null) {
			$StaffMemberModel = new StaffMemberModel();
			$data['member'] = $StaffMemberModel->find($id);

			$data['type'] = "update";
		} else {
			$StaffMemberModel = new StaffMemberModel();
			$maxNo = $StaffMemberModel
				->select('MAX(CAST(empno AS UNSIGNED)) AS max_no', false)
				->where("empno REGEXP '^[0-9]+$'", null, false)
				->where('LENGTH(empno) <=', 6)
				->get()
				->getRow('max_no');

			$data['next_emp_no'] = $maxNo ? ((int) $maxNo + 1) : 1;
		}

		return view('pages/staff/staff_registration', $data);
	}

	// create new customer - route : base_url/staff/create
	public function create()
	{
		try {
			// validation
			$validation =  \Config\Services::validation();
			$validation->setRules([
				'name' => 'required|strip_tags',
				'surname' => 'required|strip_tags',
				'fullname' => 'required|strip_tags',
				'nic' => 'strip_tags',
				'other_name' => 'strip_tags',
				'emp_no' => 'required|strip_tags',
				'tp' => 'strip_tags',
				'email' => 'strip_tags',
			]);

			if (!$validation->withRequest($this->request)->run()) {
				return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
			}

			$accountNumbersModel = new AccountNumbersModel();
			$nextPRCode = $accountNumbersModel->incrementPRCode();

			$data = array(
				'Name' => $this->request->getVar('name'),
				'surname' => $this->request->getVar('surname'),
				'fullname' => $this->request->getVar('fullname'),
				'othr_nm' => $this->request->getVar('other_name'),
				'empno' => $this->request->getVar('emp_no'),
				'emp_num' => $this->request->getVar('emp_no'),
				'Tp' => $this->request->getVar('tp'),
				'email' => $this->request->getVar('email'),
				'Nic' => $this->request->getVar('nic'),
				'Acc_No' => '4-8-9-' . $nextPRCode
			);

			$emp_no = $this->request->getVar('emp_no');

			$StaffMemberModel = new StaffMemberModel();
			// check if employee already exists by emp_no
			$employee = $StaffMemberModel->where('empno', $emp_no)->countAllResults();
			if ($employee > 0) {
				return $this->response->setJSON(["status" => 400, "message" => "Employee already exists with the same Employee No!"]);
			}
			if ($StaffMemberModel->insert($data)) {
				$accountPostingMode = new AccountPostingModel();
				$accountPostingMode->insert([
					'P_Code' => $nextPRCode,
					'PName' => $this->request->getVar('fullname'),
					'B_Code' => 4,
					'T_Code' => 8,
					'H_Code' => 9,
					'L_Code' => '4-8-9-' . $nextPRCode
				]);
				return $this->response->setJSON(["status" => 201, "message" => "Employee Added successfully!"]);
			}
			throw new \Exception("Employee creation failed!");
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	// all customers - route : base_url/staff/all
	public function all()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$StaffMemberModel = new StaffMemberModel();
		$data['members'] = $StaffMemberModel->findAll();

		return view('pages/staff/staff_directory', $data);
	}

	// delete customer - route : base_url/staff/update
	public function update()
	{
		try {
			// validation
			$validation =  \Config\Services::validation();
			$validation->setRules([
				'name' => 'required|strip_tags',
				'surname' => 'required|strip_tags',
				'fullname' => 'required|strip_tags',
				'nic' => 'strip_tags',
				'other_name' => 'strip_tags',
				'emp_no' => 'required|strip_tags',
				'tp' => 'strip_tags',
				'email' => 'strip_tags',
			]);

			if (!$validation->withRequest($this->request)->run()) {
				return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
			}

			$emp_num = $this->request->getVar('emp_no');
			$StaffMemberModel = new StaffMemberModel();
			$member = $StaffMemberModel->where('empno', $emp_num)->findAll();

			if (empty($member)) {
				return $this->response->setJSON(["status" => 404, "message" => "Employee not found!"]);
			}

			$data = array(
				'Name' => $this->request->getVar('name'),
				'surname' => $this->request->getVar('surname'),
				'fullname' => $this->request->getVar('fullname'),
				'othr_nm' => $this->request->getVar('other_name'),
				'Tp' => $this->request->getVar('tp'),
				'email' => $this->request->getVar('email'),
				'Nic' => $this->request->getVar('nic'),
			);

			if ($StaffMemberModel->update($member[0]['ID'], $data)) {
				return $this->response->setJSON(["status" => 200, "message" => "Employee Updated successfully!"]);
			}
			throw new \Exception("Employee update failed!");
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}
}
