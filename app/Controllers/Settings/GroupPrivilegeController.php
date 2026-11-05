<?php

namespace App\Controllers\Settings;

use App\Controllers\BaseController;
use App\Models\GroupProfileModel;
use App\Models\MenuCategoryModel;
use App\Models\MenuListModel;
use App\Models\UserPrivilegeModel;

use App\Libraries\MenuLoader;

class GroupPrivilegeController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/settings/group_privilege/$1
	public function index($group_id = 0)
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		// get group_id
		$data['group_id'] = $group_id;

		// get all group profiles
		$groupProileModel = new GroupProfileModel();
		$data['group_profiles'] = $groupProileModel->select('*')->where('del', 0)->orderBy('id', 'ASC')->findAll();

		if ($group_id > 0) {

			//get all menu categories
			$menuCategoryModel = new MenuCategoryModel();
			$data['categories'] = $menuCategoryModel->select('*')->where('del', 0)->orderBy('order_id', 'ASC')->findAll();

			// get all menu items
			$menuListModel = new MenuListModel();
			$data['menu_list'] = $menuListModel->select('menu_list.*, menu_category.category')->where('menu_list.del', 0)->join('menu_category', 'menu_category.id = menu_list.cat_id')->orderBy('cat_id', 'ASC')->findAll();

			// get user privileges by group id
			$userPrivilegeModel = new UserPrivilegeModel();
			$data['privileges'] = $userPrivilegeModel->where('group_id', $group_id)->findAll();
		}

		return view('pages/settings/group_privileges', $data);
	}

	// add privilege - route : base_url/settings/group_privilege/add/$group_id/$menu_id
	public function add($group_id = 0, $menu_id = 0)
	{
		if ($group_id > 0 && $menu_id > 0) {

			$userPrivilegeModel = new UserPrivilegeModel();

			// create an data array
			$data = array(
				'group_id' => $group_id,
				'menu_id' => $menu_id,
				'user_id' => session()->get('emp_id')
			);

			// add privilege
			if ($userPrivilegeModel->insert($data)) {
				return $this->response->setJSON(["status" => 201, "message" => "Privilege Added Successfully."]);
			} else {
				return $this->response->setJSON(["status" => 500, "message" => "Something went wrong."]);
			}
		} else {
			return $this->response->setJSON(["status" => 400, "message" => "Bad Request"]);
		}
	}

	// remove privilege - route : base_url/settings/group_privilege/remove/$id
	public function remove($id = 0)
	{
		if ($id > 0) {

			$userPrivilegeModel = new UserPrivilegeModel();

			// remove privilege
			if ($userPrivilegeModel->delete($id)) {
				return $this->response->setJSON(["status" => 200, "message" => "Privilege Added Successfully."]);
			} else {
				return $this->response->setJSON(["status" => 500, "message" => "Something went wrong."]);
			}
		} else {
			return $this->response->setJSON(["status" => 400, "message" => "Bad Request"]);
		}
	}
}
