<?php

namespace App\Controllers\Settings;

use App\Controllers\BaseController;
use App\Models\MenuCategoryModel;
use App\Models\MenuListModel;

use App\Libraries\MenuLoader;

class MenuListController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/settings/menu_list
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

		// get all menu categories
		$menuCategoryModel = new MenuCategoryModel();
		$data['categories'] = $menuCategoryModel->select('*')->where('del', 0)->orderBy('order_id', 'ASC')->findAll();

		// load all menu list
		$menuListModel = new MenuListModel();
		$data['menu_list'] = $menuListModel->select('menu_list.*, menu_category.category')->where('menu_list.del', 0)->join('menu_category', 'menu_category.id = menu_list.cat_id')->orderBy('cat_id', 'ASC')->findAll();

		return view('pages/settings/menu_list', $data);
	}

	// create new menu item - route : base_url/settings/menu_list/create
	public function create()
	{
		$validation = \Config\Services::validation();
		$validation->setRules([
			'menu_item_category' => 'required|strip_tags|not_in_list[0]',
			'menu_item_name' => 'required|strip_tags',
			'menu_item_url' => 'required|strip_tags',
		]);

		if ($validation->withRequest($this->request)->run()) {

			$menu_item_category = $this->request->getVar('menu_item_category');
			$menu_item_name = $this->request->getVar('menu_item_name');
			$menu_item_url = $this->request->getVar('menu_item_url');

			$model = new MenuListModel();
			// check if menu name already exists under the category
			$rowCount = $model->select('name')->where('name', $menu_item_name)->where('cat_id', $menu_item_category)->where('del', 0)->countAllResults();
			// if rowCount < 1, then menu_list name is not exists
			if ($rowCount == 0) {
				$data = [
					'cat_id' => $menu_item_category,
					'name' => $menu_item_name,
					'url' => $menu_item_url,
					'user' => session()->get('emp_id'),
					'del' => 0,
				];

				if ($model->insert($data)) {
					// Created
					return $this->response->setJSON(["status" => 201, "message" => "Menu Item Added successfully!"]);
				} else {
					// Internal Server Error
					return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
				}
			} else {
				// Conflict
				return $this->response->setJSON(["status" => 409, "message" => "Menu Item name already exists under selected Menu Category!"]);
			}
		} else {
			// Bad Request
			$errors = $validation->getErrors();
			return $this->response->setJSON(["status" => 400, "message" => 'Required Fields are missing.', "errors" => $errors]);
		}
	}

	// get single menu_list data - route : base_url/settings/menu_list/show/$1
	public function show($id = 0)
	{
		$model = new MenuListModel();

		// get menu_list data by id
		$data = $model->select('*')->where('del', 0)->find($id);

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
				'message' => 'Menu Item not found.'
			];
			return $this->response->setJSON($responseData);
		}
	}

	// update menu_list data - route : base_url/settings/menu_list/update/$1
	public function update($id = 0)
	{
		$validation = \Config\Services::validation();
		$validation->setRules([
			'menu_item_name_edit' => 'required|strip_tags',
			'menu_item_name_old_edit' => 'required|strip_tags',
			'menu_item_order' => 'required|strip_tags|numeric',
			'menu_item_id_edit' => 'required|strip_tags|numeric',
			'menu_item_category_edit' => 'required|strip_tags|numeric|not_in_list[0]',
			'menu_item_url_edit' => 'required|strip_tags',
		]);

		$data = $this->request->getRawInput();

		if ($validation->run($data)) {

			$model = new MenuListModel();

			$name_changed  = false;

			if($data['menu_item_name_edit'] != $data['menu_item_name_old_edit']) {
				// check if menu name already exists under the category
				$rowCount = $model->select('name')->where('name', $data['menu_item_name_edit'])->where('cat_id', $data['menu_item_category_edit'])->where('del', 0)->countAllResults();
				// if rowCount < 1, then menu_list name is not exists
				if ($rowCount > 0) {
					// Conflict
					return $this->response->setJSON(["status" => 409, "message" => "Menu Item name already exists under selected Menu Category!"]);
				}

				$name_changed = true;
			}

			$data2 = [
				'cat_id' => $data['menu_item_category_edit'],
				'url' => $data['menu_item_url_edit'],
				'order_id' => $data['menu_item_order'],
				'user' => session()->get('emp_id'),
			];

			if($name_changed) {
				$data2['name'] = $data['menu_item_name_edit'];
			}

			if ($model->update($id, $data2)) {
				// Created
				return $this->response->setJSON(["status" => 200, "message" => "Menu Item Updated Successfully"]);
			} else {
				// Internal Server Error
				return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
			}

		} else {
			// Bad Request
			return $this->response->setJSON(["status" => 400, "message" => "Required Fields are missing.", "errors" => $validation->getErrors()]);
		}
	}

	// delete menu_list - route : base_url/settings/menu_list/delete/$1
	public function delete($id)
	{
		$model = new MenuListModel();
		$data = [
			'del' => 1,
			'user' => session()->get('emp_id'),
		];

		if ($model->update($id, $data)) {

			// Deleted
			return $this->response->setJSON(["status" => 200, "message" => "Menu Item Deleted Successfully"]);
		} else {
			// Internal Server Error
			return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
		}
	}
}
