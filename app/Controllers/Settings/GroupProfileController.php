<?php

namespace App\Controllers\Settings;

use App\Controllers\BaseController;
use App\Models\GroupProfileModel;
use App\Models\UserModel;

use App\Libraries\MenuLoader;

class GroupProfileController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/settings/group_profiles
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$model = new GroupProfileModel();
		$data['profiles'] = $model->select('*')->where('del', 0)->orderBy('id', 'ASC')->findAll();

		return view('pages/settings/group_profiles', $data);
	}

	// create new profile - route : base_url/settings/group_profiles/create
	public function create()
	{

		$validation = \Config\Services::validation();
		$validation->setRules([
			'profile_name' => 'required|strip_tags',

		]);

		if ($validation->withRequest($this->request)->run()) {

			$profile_name = $this->request->getVar('profile_name');

			$model = new GroupProfileModel();
			$rowCount = $model->select('type')->where('type', $profile_name)->where('del', 0)->countAllResults();

			if ($rowCount > 0) {
				// Conflict
				return $this->response->setJSON(["status" => 409, "message" => "Group Profile already exists"]);
			} else {
				$data = [
					'type' => $profile_name,
					'del' => 0,
					'user' => session()->get('emp_id'),
				];

				if ($model->insert($data)) {
					// Created
					return $this->response->setJSON(["status" => 201, "message" => "Group Profile Created Successfully"]);
				} else {
					// Internal Server Error
					return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
				}
			}
		} else {
			// Bad Request
			return $this->response->setJSON(["status" => 400, "message" => "Please provide new profile name or the provided name is not valid."]);
		}
	}

	// get single profile data - route : base_url/settings/group_profiles/show/$1
	public function show($id = 0)
	{
		$model = new GroupProfileModel();

		$data['profile'] = $model->select('type,user,del,id')->where('del', 0)->find($id);

		// if data is not empty
		if (!empty($data)) {
			$responseData = [
				'status' => 200, // Success
				'error' => null,
				'message' => 'Data retrieved successfully.',
				'data' => $data
			];


			return $this->response->setJSON($responseData);
			// return $this->respond($response);
		} else {
			$responseData = [
				'status' => 404, // Not found
				'error' => null,
				'message' => 'Profile not found.'
			];
			return $this->response->setJSON($responseData);
		}
	}

	// update profile data - route : base_url/settings/group_profiles/update/$1
	public function update($id = 0)
	{
		$validation = \Config\Services::validation();
		$validation->setRules([
			'profile_name_edit' => 'required|strip_tags',
			'profile_name_old_edit' => 'required|strip_tags',
			'profile_id_edit' => 'required|strip_tags',
		]);

		$data = $this->request->getRawInput();

		if ($validation->run($data)) {
			$model = new GroupProfileModel();
			$rowCount = $model->select('type')->where('type', $data['profile_name_edit'])->where('del', 0)->countAllResults();

			if ($rowCount > 0) {
				// Conflict
				return $this->response->setJSON(["status" => 409, "message" => "Group Profile already exists or the provided name is same as the current name. Please choose a different name."]);
			} else {
				$updateData = [
					'type' => $data['profile_name_edit'],
				];

				if ($model->update($id, $updateData)) {
					// Updated
					return $this->response->setJSON(["status" => 200, "message" => "Group Profile Updated Successfully"]);
				} else {
					// Internal Server Error
					return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
				}
			}
		} else {
			// Bad Request
			return $this->response->setJSON(["status" => 400, "message" => "Please provide a profile name or the provided name is not valid."]);
		}
	}

	// delete profile - route : base_url/settings/group_profiles/delete/$1
	public function delete($id)
	{
		$model = new GroupProfileModel();
		$data = [
			'del' => 1,
			'user' => session()->get('emp_id'),
		];

		if ($model->update($id, $data)) {
			// Delete user accounts from UserModel where group_id = $id
			$userModel = new UserModel();
			$userModel->where('group_id', $id)->delete();
			// Deleted
			return $this->response->setJSON(["status" => 200, "message" => "Group Profile Deleted Successfully"]);
		} else {
			// Internal Server Error
			return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
		}
	}
}
