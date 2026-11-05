<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountNumbersModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\CountryModel;
use App\Models\CustomerModel;
use App\Models\TourQuotationsModel;

class ShopRegistrationController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/master_files/shop
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		// Load the supplier data
		$CustomerModel = new CustomerModel();

		$countryModel = new CountryModel();
		$data['countries'] = $countryModel->findAll();

		$id = $this->request->getGet('id');
		if (!empty($id)) {
			$data['customer'] = $CustomerModel->where('ID', $id)->first();
		}

		return view('pages/master_files/new_shop', $data);
	}

	// create new customer - route : base_url/master_files/shop/create
	public function create()
		{
			try {
			// validation
			$validation =  \Config\Services::validation();
			$validation->setRules([
				'supplier_name' => 'required|strip_tags',
				'address' => 'required|strip_tags'
			]);

			if (!$validation->withRequest($this->request)->run()) {
				return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
			}

			$accountNumbersModel = new AccountNumbersModel();
			$nextPRCode = $accountNumbersModel->incrementPRCode();

			$data = array(
				
				'Name' => $this->request->getVar('supplier_name'),
				'Address' => $this->request->getVar('address'),
				'flag' => '1',				
				'TP' => $this->request->getVar('tel_home'),
				'Mobile' => $this->request->getVar('tel_mobile'),
				'Sup_Acc_No' => '1-2-5-' . $nextPRCode,
				'email' =>  $this->request->getVar('email'),
				't_code_1' =>  $this->request->getVar('t_code_1'),
				't_code_2' =>  $this->request->getVar('t_code_2'),
				'contact_person' =>  $this->request->getVar('person_name'),
				'per_mobile' =>  $this->request->getVar('per_tel_mobile'),
				'per_tp' =>  $this->request->getVar('per_tel_home'),
				'per_t_code_1' =>  $this->request->getVar('per_t_code_1'),
				'per_t_code_2' =>  $this->request->getVar('per_t_code_2')
			);

			$customerModel = new CustomerModel();
			$customer_id = $customerModel->insert($data);
			if ($customer_id) {
				$accountPostingMode = new AccountPostingModel();
				$accountPostingMode->insert([
					'P_Code' => $nextPRCode,
					'PName' => $this->request->getVar('supplier_name'),
					'B_Code' => 1,
					'T_Code' => 2,
					'H_Code' => 5,
					'L_Code' => '1-2-5-' . $nextPRCode
				]);
				return $this->response->setJSON(["status" => 201, "message" => "Shop Added successfully!", "customer_id" => $customer_id]);
			}
			throw new \Exception("Shop creation failed!");
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	// all customers - route : base_url/master_files/customer/all
	public function all()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$customerModel = new CustomerModel();
		$data['shops'] = $customerModel->select('customers.*, country.nicename')->join('country', 'customers.country = country.iso', 'left')->where('customers.flag', '1')->findAll();

		return view('pages/master_files/all_shops', $data);
	}

	// update - route : base_url/master_files/customer/update/{id}
	public function update($id = null, $acc_no = null)
	{
		try {
			// validation
			$validation =  \Config\Services::validation();
			$validation->setRules([
				'supplier_name' => 'required|strip_tags',
				'address' => 'required|strip_tags'
			]);

			if (!$validation->withRequest($this->request)->run()) {
				return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
			}

			$data = array(

				'Name' => $this->request->getVar('supplier_name'),
				'Address' => $this->request->getVar('address'),
				'flag' => '1',
				'TP' => $this->request->getVar('tel_home'),
				'Mobile' => $this->request->getVar('tel_mobile'),

				'email' =>  $this->request->getVar('email'),
				'country' =>  $this->request->getVar('country'),
				't_code_1' =>  $this->request->getVar('t_code_1'),
				't_code_2' =>  $this->request->getVar('t_code_2'),
				'contact_person' =>  $this->request->getVar('person_name'),
				'per_mobile' =>  $this->request->getVar('per_tel_mobile'),
				'per_tp' =>  $this->request->getVar('per_tel_home'),
				'per_t_code_1' =>  $this->request->getVar('per_t_code_1'),
				'per_t_code_2' =>  $this->request->getVar('per_t_code_2')
			);

			$customerModel = new CustomerModel();
			$accountPostingMode = new AccountPostingModel();
			if ($customerModel->update($id, $data)) {
				$accountPostingMode->where('L_Code', $acc_no)->set('PName', $this->request->getVar('supplier_name'))->update();
				// log_message('critical', $accountPostingMode->getLastQuery());
				return $this->response->setJSON(["status" => 200, "message" => "Shop updated successfully!", "customer_id" => $id]);
			}
			throw new \Exception("Shop update failed!");
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	// delete - route : base_url/master_files/customer/delete
	public function delete()
	{
		try {
			$accountRunningModel = new AccountRunningModel();
			$accHasRecords = $accountRunningModel->where('L_Code', $this->request->getVar('Acc_No'))->countAllResults();

			$tourquotationModel = new TourQuotationsModel();
			$tourquotatiom = $tourquotationModel->where('L_Code', $this->request->getVar('Acc_No'))->countAllResults();

			if ($accHasRecords > 0 || $tourquotatiom > 0) {
				return $this->response->setJSON(["status" => 400, "message" => "Cannot delete this shop. This shop has transactions!"]);
			}
			$customerModel = new CustomerModel();
			if ($customerModel->where('Sup_Acc_No', $this->request->getVar('Acc_No'))->delete()) {
				$accountPostingModel = new AccountPostingModel();
				$accountPostingModel->where('L_Code', $this->request->getVar('Acc_No'))->delete();
				// log_message('critical', $accountPostingModel->getLastQuery());
				return $this->response->setJSON(["status" => 200, "message" => "Shop deleted successfully!"]);
			}
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	// get customer details by id - route : base_url/master_files/customer/get
	public function getCustomerDetailsById()
	{
		$customerModel = new CustomerModel();
		$customer = $customerModel->where('ID', $this->request->getGet('id'))->first();
		if (!$customer) {
			return $this->response->setJSON(["status" => 404, "message" => "Customer not found!"]);
		}
		return $this->response->setJSON(["status" => 200, "customer" => $customer]);
	}
}
