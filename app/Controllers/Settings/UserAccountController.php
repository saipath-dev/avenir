<?php

namespace App\Controllers\Settings;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\GroupProfileModel;

use App\Libraries\MenuLoader;
use App\Models\StaffMemberModel;

class UserAccountController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/settings/user_account
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		// get logged in user user_id
		$user_id = session()->get('user_id');

		$data['user_id'] = $user_id;

		$userModel = new UserModel();

		// get all user accounts
		$user_accounts = $userModel
			->select('user_acc_tbl.*, employee.fullname AS employee_name, group_profile.type AS profile_type, customers.Name as agent_name')
			->join('group_profile', 'group_profile.id = user_acc_tbl.group_id')
			->join('employee', 'employee.emp_num = user_acc_tbl.emp_id')
			->join('customers', 'customers.Sup_Acc_No = user_acc_tbl.agent', 'left')
			->orderBy('user_acc_tbl.group_id', 'asc')
			->findAll();

		// strip credential fields before passing to view
		$user_accounts = array_map(function ($row) {
			unset($row['pass'], $row['email_password']);
			return $row;
		}, $user_accounts);
		$data['user_accounts'] = $user_accounts;

		// get all group profiles
		$groupProfileModel = new GroupProfileModel();
		$data['group_profiles'] = $groupProfileModel->select('*')->where('id !=', 1)->where('del', 0)->findAll();

		// if $user_id != 1, remove id=1 & id=2 from group_profiles
		if ($user_id != 1) {
			$data['group_profiles'] = array_filter($data['group_profiles'], function ($var) {
				return ($var['id'] != 1 && $var['id'] != 2);
			});
		}

		$staffMemberModel = new StaffMemberModel();
		// get all staff members
		$members = $staffMemberModel->select('emp_num, fullname AS Name')->findAll();
		$data['members'] = [];
		// get members who is not in user_accounts
		foreach ($members as $member) {
			$found = false;
			foreach ($user_accounts as $user) {
				if ($member['emp_num'] == $user['emp_id']) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$data['members'][] = $member;
			}
		}

		$customerModal = new \App\Models\CustomerModel();
		$data['agents'] = $customerModal->findAll();

		// var_dump($data);
		// die();

		return view('pages/settings/user_account', $data);
	}

	// route : base_url/settings/user_account/show/$1
	public function show($id = 0)
	{

		$userModel = new UserModel();
		// get user account by id
		$data = $userModel->find($id);

		// if data is not empty
		if (!empty($data)) {
			// whitelist: only expose non-sensitive fields (never pass/email_password)
			$allowed = ['id', 'emp_id', 'user', 'acc_no', 'group_id', 'access', 'host', 'email', 'agent', 'user_role', 'login_type', 'last_logged'];
			$safeData = [];
			foreach ($allowed as $field) {
				if (array_key_exists($field, $data)) {
					$safeData[$field] = $data[$field];
				}
			}
			// get employee name from staff_members table
			$staffMemberModel = new StaffMemberModel();
			$employee = $staffMemberModel->select('fullname as name')->where('emp_num', $data['emp_id'])->findAll(1);
			$responseData = [
				'status' => 200, // Success
				'error' => null,
				'message' => 'Data retrieved successfully.',
				'data' => $safeData,
				'employee_name' => $employee[0]['name']
			];


			return $this->response->setJSON($responseData);
			// return $this->respond($response);
		} else {
			$responseData = [
				'status' => 404, // Not found
				'error' => null,
				'message' => 'Account not found.'
			];
			return $this->response->setJSON($responseData);
		}
	}

	// route : base_url/settings/user_account/create
	public function create()
	{
		try {
			$validationData = [
				'group_profile' => 'required|strip_tags|numeric|not_in_list[0]',
				'employee_id' => 'required|strip_tags|numeric|not_in_list[0]',
				'username' => 'required|strip_tags|alpha_numeric_space',
				'password' => 'required|strip_tags|min_length[8]|max_length[72]',
				'host' => 'permit_empty|strip_tags',
				'email' => 'permit_empty|strip_tags|valid_email',
				'email_password' => 'permit_empty|strip_tags',
			];

			$validation = \Config\Services::validation();
			$validation->setRules($validationData);

			if ($validation->withRequest($this->request)->run()) {

				$group_profile = $this->request->getVar('group_profile');
				$employee_id = $this->request->getVar('employee_id');
				$username = $this->request->getVar('username');
				$password = $this->request->getVar('password');
				$host = $this->request->getVar('host');
				$email = $this->request->getVar('email');
				$email_password = $this->request->getVar('email_password');
				$agent = $this->request->getVar('agent');

				if ($group_profile == 14 && $agent == null) {
					return $this->response->setJSON(["status" => 400, "message" => "Agent is required."]);
				}

				$model = new UserModel();
				$rowCount = $model->where('user', $username)->countAllResults();

				if ($rowCount > 0) {
					// Conflict
					return $this->response->setJSON(["status" => 409, "message" => "Username already exists. Please choose a different one."]);
				} else {
					// get emp_num, location_id, cost_center_id, designation_id from staff_members table
					$staffMemberModel = new StaffMemberModel();
					$staffMember = $staffMemberModel->select('emp_num, Acc_No')->where('emp_num', $employee_id)->findAll(1);
					// hash password
					$password = password_hash($password, PASSWORD_DEFAULT);
					$data = [
						'group_id' => $group_profile,
						'user' => $username,
						'pass' => $password,
						'emp_id' => $staffMember[0]['emp_num'],
						'acc_no' => $staffMember[0]['Acc_No'],
						'access' => 1,
						'host' => $host,
						'email' => $email,
						'email_password' => !empty($email_password) ? \App\Libraries\TourFlow\Secrets::sealIfReady((string) $email_password) : $email_password,   // tf-comms-r3: encrypted at rest
						'agent' => $agent
					];

					$model->insert($data); // insert data to user_acc_tbl
					return $this->response->setJSON(["status" => 201, "message" => "User Account Created Successfully."]);
				}
			} else {
				// Bad Request
				return $this->response->setJSON(["status" => 400, "message" => "All fields are required.", "errors" => $validation->getErrors()]);
			}
		} catch (\Exception $e) {
			log_message('error', 'UserAccountController::create exception: ' . $e->getMessage());
			return $this->response->setJSON(["status" => 500, "message" => "An internal error occurred. Please try again."]);
		}
	}

	// route : base_url/settings/user_account/update/$1
	public function update($id = 0)
	{

		if ($id != 1) {
			$validation = \Config\Services::validation();
			$validation->setRules([
				'group_profile_edit' => 'required|strip_tags|numeric',
				'employee_id_edit' => 'required|strip_tags|numeric',
				'password' => 'permit_empty|strip_tags|min_length[8]|max_length[72]',
				'host' => 'permit_empty|strip_tags',
				'email' => 'permit_empty|strip_tags|valid_email',
				'email_password' => 'permit_empty|strip_tags',
			]);

			$data = $this->request->getRawInput();

			if ($validation->run($data)) {

				$group_profile = $this->request->getRawInputVar('group_profile_edit');
				$employee_id = $this->request->getRawInputVar('employee_id_edit');
				$password = $this->request->getRawInputVar('password');
				$host = $this->request->getRawInputVar('host');
				$email = $this->request->getRawInputVar('email');
				$email_password = $this->request->getRawInputVar('email_password');

				$model = new UserModel();
				$data = [
					'group_id' => $group_profile,
					'host' => $host,
					'email' => $email
				];

				// if password is not empty, hash it and add to data array
				if (!empty($password)) {
					$password = password_hash($password, PASSWORD_DEFAULT);
					$data['pass'] = $password;
				}

				if (!empty($password)) {
						helper('usersession');
						kill_user_sessions($id, false);
					}

				if (!empty($email_password)) {
					$data['email_password'] = \App\Libraries\TourFlow\Secrets::sealIfReady((string) $email_password);   // tf-comms-r3: encrypted at rest
				}

				if ($model->update($id, $data)) {
					// if password was changed, revoke ALL of that user's sessions
					if (!empty($data['pass'])) {
						helper('usersession');
						kill_user_sessions($id, false);
					}
					// get emp_id from user_acc_tbl
					$user = $model->select('emp_id')->where('id', $id)->findAll(1);
					// if logged in user is updating his own account
					if ($user[0]['emp_id'] == session()->get('emp_id')) {
						// logout
						session()->destroy();
						// redirect to /;
						redirect('/');
					}
					// Created
					return $this->response->setJSON(["status" => 201, "message" => "User Account Updated Successfully."]);
				} else {
					// Internal Server Error
					return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
				}
			} else {
				// Bad Request
				$firstError = current($validation->getErrors());
				return $this->response->setJSON(["status" => 400, "message" => $firstError]);
			}
		} else {
			// Forbidden
			return $this->response->setJSON(["status" => 500, "message" => "You can't update System Administrator Account."]);
		}
	}

	// route : base_url/settings/user_account/delete/$1 
	public function delete($id)
	{
		if ($id != 1) {
			$model = new UserModel();
			// get emp_id from user_acc_tbl
			$user = $model->select('emp_id')->where('id', $id)->findAll(1);
			// delete user from staff_members table
			$model->delete($id);
			// if logged in user is deleting his own account
			if ($user[0]['emp_id'] == session()->get('emp_id')) {
				// logout
				session()->destroy();
				// redirect to /;
				redirect('/');
			}
			return $this->response->setJSON(["status" => 200, "message" => "User Account Deleted Successfully"]);
		} else {
			// Forbidden
			return $this->response->setJSON(["status" => 500, "message" => "You can't delete System Administrator Account."]);
		}
	}

	public function update_access($user_id)
	{
		$model = new UserModel();
		$user = $model->find($user_id);
		$access = $user['access'] == 1 ? 0 : 1;
		$data = [
			'access' => $access
		];
		$model->update($user_id, $data);
		return $this->response->setJSON(["status" => 200, "message" => "Account Access Updated Successfully."]);
	}
}
