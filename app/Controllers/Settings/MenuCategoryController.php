<?php

namespace App\Controllers\Settings;

use App\Controllers\BaseController;
use App\Models\MenuCategoryModel;
use App\Models\MenuListModel;

use App\Libraries\MenuLoader;

class MenuCategoryController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/settings/menu_category
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$model = new MenuCategoryModel();

		// get all menu categories
		$data['categories'] = $model->select('*')->where('del', 0)->orderBy('order_id', 'ASC')->findAll();

		return view('pages/settings/menu_category', $data);
	}

	// create new category - route : base_url/settings/menu_category/create
	public function create()
	{
		$validation = \Config\Services::validation();
		$validation->setRules([
			'category_name' => 'required|strip_tags',
		]);

		if ($validation->withRequest($this->request)->run()) {

			$category_name = $this->request->getVar('category_name');

			$model = new MenuCategoryModel();
			// check if category name already exists
			$rowCount = $model->select('category')->where('category', $category_name)->where('del', 0)->countAllResults();
			// if rowCount < 1, then category name is not exists
			if ($rowCount < 1) {
				$data = [
					'category' => $category_name,
					'del' => 0,
					'user' => session()->get('emp_id'),
				];

				if ($model->insert($data)) {
					// Created
					return $this->response->setJSON(["status" => 201, "message" => "Menu Category Created Successfully"]);
				} else {
					// Internal Server Error
					return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
				}
			} else {
				// Conflict
				return $this->response->setJSON(["status" => 409, "message" => "Menu Category already exists"]);
			}
		} else {
			// Bad Request
			return $this->response->setJSON(["status" => 400, "message" => "Please provide new profile name or the provided name is not valid."]);
		}
	}

	// get single category data - route : base_url/settings/menu_category/show/$1
	public function show($id = 0)
	{
		$model = new MenuCategoryModel();

		// get category data by id
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
				'message' => 'Category not found.'
			];
			return $this->response->setJSON($responseData);
		}
	}

	// update category data - route : base_url/settings/menu_category/update/$1
	public function update($id = 0)
	{
		$validation = \Config\Services::validation();
		$validation->setRules([
			'category_name_edit' => 'required|strip_tags',
			'order' => 'required|strip_tags|numeric'
		]);

		$data = $this->request->getRawInput();

		if ($validation->run($data)) {

			$model = new MenuCategoryModel();

			$data2 = [
				'order_id' => $data['order'],
				'del' => 0,
				'user' => session()->get('emp_id'),
			];

			// check if category name already exists
			$rowCount = $model->select('category')->where('category', $data['category_name_edit'])->countAllResults();
			// if rowCount < 1, then category name is not exists
			if ($rowCount < 1) {
				$data2['category'] = $data['category_name_edit'];
			}

			if ($model->update($id, $data2)) {
				// Created
				return $this->response->setJSON(["status" => 200, "message" => "Menu Category Updated Successfully"]);
			} else {
				// Internal Server Error
				return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
			}

		} else {
			// Bad Request
			return $this->response->setJSON(["status" => 400, "message" => "Please provide a category name or the provided name is not valid."]);
		}
	}

	// delete category - route : base_url/settings/menu_category/delete/$1
	public function delete($id)
	{
		$model = new MenuCategoryModel();
		$data = [
			'del' => 1,
			'user' => session()->get('emp_id'),
		];

		if ($model->update($id, $data)) {
			// Delete all menu list items under this category (where cat_id = $id)
			$menuListModel = new MenuListModel();
			$menuListModel->where('cat_id', $id)->delete();
			// Deleted
			return $this->response->setJSON(["status" => 200, "message" => "Menu Category Deleted Successfully"]);
		} else {
			// Internal Server Error
			return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
		}
	}
}
