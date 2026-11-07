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
		$members = $staffMemberModel->select('emp_num, fullname AS Name')->where('is_active', 1)->findAll();   // tf-empactive-r1: only active employees can be given a login
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

				// tf-zeroperm-r1 (7 Nov 2026, Shantha): a new staff account comes onto the Staff Access Control Panel, so it may
				// not be created in a group that never comes onto it (System Administrator, Driver, Admin)
				if (isset(\App\Libraries\TourFlow\StaffAssignment::NEVER_ON_PANEL[(int) $group_profile])) {
					return $this->response->setJSON(["status" => 400, "message" => \App\Libraries\TourFlow\StaffAssignment::NEVER_ON_PANEL[(int) $group_profile] . ' A staff account cannot be created in this group.']);
				}

				$model = new UserModel();
				$rowCount = $model->where('user', $username)->countAllResults();

				if ($rowCount > 0) {
					// Conflict
					return $this->response->setJSON(["status" => 409, "message" => "Username already exists. Please choose a different one."]);
				} else {
					// get emp_num, location_id, cost_center_id, designation_id from staff_members table
					$staffMemberModel = new StaffMemberModel();
					$staffMember = $staffMemberModel->select('emp_num, Acc_No, is_active')->where('emp_num', $employee_id)->findAll(1);
					// tf-empactive-r1 (7 Nov 2026, Shantha): a deactivated employee record cannot be given a new login
					if (empty($staffMember) || (int) ($staffMember[0]['is_active'] ?? 0) !== 1) {
						return $this->response->setJSON(["status" => 400, "message" => "This employee record is not active. Only an active employee can be given a login."]);
					}
					// hash password
					$password = password_hash($password, PASSWORD_DEFAULT);
					// tf-staffno-r1: the account links to the employee by its internal number (emp_num), which must name exactly one record
					if ((int) $staffMemberModel->where('emp_num', $staffMember[0]['emp_num'])->countAllResults() !== 1) {
						return $this->response->setJSON(["status" => 400, "message" => "This employee's internal number is shared with another record, so a login could not be linked to it safely. Nothing was created."]);
					}
					$data = [
						'group_id' => $group_profile,
						'user' => $username,
						'pass' => $password,
						'emp_id' => $staffMember[0]['emp_num'],
						'acc_no' => $staffMember[0]['Acc_No'],
						'access' => 0,   // tf-zeroperm-r1: created disabled; enabled only once it is on the Staff Access Control Panel
						'host' => $host,
						'email' => $email,
						'email_password' => !empty($email_password) ? \App\Libraries\TourFlow\Secrets::sealIfReady((string) $email_password) : $email_password,   // tf-comms-r3: encrypted at rest
						'agent' => $agent
					];

					$model->insert($data); // insert data to user_acc_tbl
					$newId = (int) $model->getInsertID();

					// tf-zeroperm-r1: register it on the Staff Access Control Panel with zero permissions BEFORE it can ever be
					// enabled. If that fails the account simply stays disabled, which is safe.
					$by = (string) session()->get('username');
					$reg = \App\Libraries\TourFlow\StaffAssignment::addToPanel(\Config\Database::connect(), $newId, '', '', 'New staff account registered on the Staff Access Control Panel with zero permissions at creation (tf-zeroperm-r1).', $by, $by);
					if (empty($reg['ok'])) {
						log_message('error', 'tf-zeroperm-r1: account ' . $newId . ' created disabled but not registered on the panel: ' . ($reg['message'] ?? ''));
						return $this->response->setJSON(["status" => 500, "message" => "The account was created but is disabled: it could not be registered on the Staff Access Control Panel (" . ($reg['message'] ?? 'unknown reason') . "). It cannot sign in."]);
					}
					return $this->response->setJSON(["status" => 201, "message" => "User Account Created. It is disabled and on the Staff Access Control Panel with no permissions. Open what you approve under Master Files -> Staff Assignment, then enable it here."]);
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

				// tf-zeroperm-r1 (7 Nov 2026, Shantha): a staff account is never moved into a group that sits outside the Staff
				// Access Control Panel (System Administrator, Driver, Admin) — that would hand it access the panel does not decide
				$currentGroup = (int) ((new UserModel())->select('group_id')->find($id)['group_id'] ?? 0);
				if ((int) $group_profile !== $currentGroup && isset(\App\Libraries\TourFlow\StaffAssignment::NEVER_ON_PANEL[(int) $group_profile])) {
					return $this->response->setJSON(["status" => 400, "message" => \App\Libraries\TourFlow\StaffAssignment::NEVER_ON_PANEL[(int) $group_profile] . ' A staff account cannot be moved into this group.']);
				}
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
		// tf-userdel-r1 (7 Nov 2026, Shantha): the login goes, its history stays. Job role assignments that point at the login
		// (the database refuses to delete a login they reference) are copied to job_role_assignments_archive first; its panel
		// access is closed, any position it holds is ended, its sessions end, and the deletion is logged. The answer says what
		// actually happened: before this, a refused delete still said "Deleted Successfully".
		$id = (int) $id;
		$db = \Config\Database::connect();
		$user = $db->query('SELECT id, user, emp_id, group_id FROM user_acc_tbl WHERE id = ? LIMIT 1', [$id])->getRowArray();
		if (!$user) {
			return $this->response->setJSON(["status" => 404, "message" => "No such account."]);
		}
		if ($id === 1 || (int) $user['group_id'] === 1) {
			return $this->response->setJSON(["status" => 500, "message" => "You can't delete System Administrator Account."]);
		}
		$by = (string) (session()->get('username') ?? session()->get('user') ?? ('user ' . session()->get('user_id')));
		$now = date('Y-m-d H:i:s');
		$killed = 0;
		try {
			helper('usersession');
			$killed = (int) kill_user_sessions($id, false, 'account_deleted');
		} catch (\Throwable $e) {
		}
		$archived = 0;
		// the archive table is created before the transaction: MySQL commits implicitly on CREATE TABLE
		if ($db->query("SHOW TABLES LIKE 'job_role_assignments'")->getRowArray() && (int) ($db->query('SELECT COUNT(*) n FROM job_role_assignments WHERE user_id = ?', [$id])->getRowArray()['n'] ?? 0) > 0) {
			$db->query("CREATE TABLE IF NOT EXISTS job_role_assignments_archive (
						archive_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
						original_id INT UNSIGNED NOT NULL, user_id INT NOT NULL, deleted_username VARCHAR(190) NULL,
						job_role_id VARCHAR(255) NULL, start_date DATE NULL, end_date DATE NULL,
						assigned_at DATETIME NULL, assigned_by VARCHAR(100) NULL, updated_at DATETIME NULL, updated_by VARCHAR(100) NULL, del TINYINT(1) NULL,
						archived_at DATETIME NOT NULL, archived_by VARCHAR(100) NOT NULL, archive_reason VARCHAR(255) NOT NULL,
						KEY ix_jra_archive_user (user_id)
					)");
		}
		$db->transBegin();
		try {
			$hasJra = (bool) $db->query("SHOW TABLES LIKE 'job_role_assignments'")->getRowArray();
			if ($hasJra) {
				$n = (int) ($db->query('SELECT COUNT(*) n FROM job_role_assignments WHERE user_id = ?', [$id])->getRowArray()['n'] ?? 0);
				if ($n > 0) {
					$db->query("INSERT INTO job_role_assignments_archive (original_id, user_id, deleted_username, job_role_id, start_date, end_date, assigned_at, assigned_by, updated_at, updated_by, del, archived_at, archived_by, archive_reason)
						SELECT id, user_id, ?, job_role_id, start_date, end_date, assigned_at, assigned_by, updated_at, updated_by, del, ?, ?, 'login deleted under Settings -> User Accounts'
						FROM job_role_assignments WHERE user_id = ?", [$user['user'], $now, $by, $id]);
					$archived = (int) $db->affectedRows();
					if ($archived !== $n) {
						throw new \RuntimeException('the job role assignments could not all be archived');
					}
					$db->query('DELETE FROM job_role_assignments WHERE user_id = ?', [$id]);
				}
			}
			$staff = null;
			if (\App\Libraries\TourFlow\StaffAssignment::installed($db)) {
				$staff = $db->query('SELECT id FROM tf_staff WHERE user_id = ? LIMIT 1', [$id])->getRowArray();
				if ($staff) {
					$db->query("UPDATE tf_staff_permission SET state = 'blocked', reason = 'Login deleted', set_by = ?, set_at = ? WHERE staff_id = ? AND state = 'open'", [$by, $now, (int) $staff['id']]);
					$db->query("UPDATE tf_staff SET on_panel = 0, status = 'deactivated', status_reason = 'Login deleted', updated_by = ?, updated_at = ? WHERE id = ?", [$by, $now, (int) $staff['id']]);
				}
				if ($db->query("SHOW TABLES LIKE 'tf_position_holder'")->getRowArray()) {
					$db->query("UPDATE tf_position_holder SET to_at = ?, ended_by = ?, end_reason = 'Login deleted' WHERE user_id = ? AND to_at IS NULL", [$now, $by, $id]);
				}
			}
			$db->query('DELETE FROM user_acc_tbl WHERE id = ? AND id <> 1', [$id]);
			if ((int) $db->affectedRows() !== 1) {
				throw new \RuntimeException('the database did not remove the login');
			}
			if (\App\Libraries\TourFlow\StaffAssignment::installed($db)) {
				\App\Libraries\TourFlow\StaffAssignment::log($db, $staff ? (int) $staff['id'] : null, $id, 'login_deleted', null, 'login', 'deleted', 'Deleted under Settings -> User Accounts', 'Login "' . $user['user'] . '" deleted; ' . $archived . ' job role assignment(s) archived; ' . $killed . ' open session(s) ended. The employee record and its history are kept.', $by, $by);
			}
			$db->transCommit();
		} catch (\Throwable $e) {
			$db->transRollback();
			log_message('error', 'user account delete ' . $id . ': ' . $e->getMessage());
			return $this->response->setJSON(["status" => 500, "message" => 'The account "' . $user['user'] . '" was not deleted: ' . $e->getMessage() . '. Nothing was changed.']);
		}
		// if logged in user is deleting his own account
		if ((string) $user['emp_id'] === (string) session()->get('emp_id')) {
			session()->destroy();
		}
		return $this->response->setJSON(["status" => 200, "message" => 'User account "' . $user['user'] . '" deleted.' . ($archived ? ' ' . $archived . ' job role assignment(s) were archived first.' : '')]);
	}

	public function update_access($user_id)
	{
		$model = new UserModel();
		$user = $model->find($user_id);
		if (!$user) {
			return $this->response->setJSON(["status" => 500, "message" => "No such account."]);
		}
		// tf-zeroperm-r1 (7 Nov 2026, Shantha): the System Administrator's access is never switched from here (it would lock the system)
		if ((int) $user['id'] === 1 || (int) $user['group_id'] === 1) {
			return $this->response->setJSON(["status" => 500, "message" => "The System Administrator account's access is not switched from here."]);
		}
		$db = \Config\Database::connect();
		$access = $user['access'] == 1 ? 0 : 1;
		if ($access === 1) {
			// enabling: only an account that is on the Staff Access Control Panel and Active there; its group grants nothing
			if (isset(\App\Libraries\TourFlow\StaffAssignment::NEVER_ON_PANEL[(int) $user['group_id']])) {
				return $this->response->setJSON(["status" => 500, "message" => \App\Libraries\TourFlow\StaffAssignment::NEVER_ON_PANEL[(int) $user['group_id']] . ' It cannot be enabled.']);
			}
			$stop = \App\Libraries\TourFlow\StaffAssignment::panelRefusal($db, (int) $user['id'], (int) $user['group_id']);
			if ($stop !== null) {
				return $this->response->setJSON(["status" => 500, "message" => "This account cannot be enabled: it is " . $stop . ". Bring it onto Master Files -> Staff Assignment and make it Active first."]);
			}
		}
		$model->update($user_id, ['access' => $access]);
		$killed = 0;
		if ($access === 0) {
			try {
				helper('usersession');
				$killed = (int) kill_user_sessions((int) $user['id'], false, 'disabled');
			} catch (\Throwable $e) {
			}
		}
		// the switch is recorded in the panel history beside the permission changes
		$s = \App\Libraries\TourFlow\StaffAssignment::byUser($db, (int) $user['id']);
		if ($s) {
			$by = (string) session()->get('username');
			\App\Libraries\TourFlow\StaffAssignment::log($db, (int) $s['id'], (int) $user['id'], 'access', null, $access ? 'disabled' : 'enabled', $access ? 'enabled' : 'disabled', 'Access switched under Settings -> User Accounts', 'Account "' . $user['user'] . '" ' . ($access ? 'enabled' : 'disabled') . ($access ? '' : '; ' . $killed . ' open session(s) ended') . '.', $by, $by);
		}
		return $this->response->setJSON(["status" => 200, "message" => "Account " . ($access ? "enabled" : "disabled") . "." . ($access ? "" : " " . $killed . " open session(s) ended.")]);
	}
}
