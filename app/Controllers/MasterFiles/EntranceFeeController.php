<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountNumbersModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\EntranceFeeModel;
use App\Models\TourActivityModel;
use Config\Services;

class EntranceFeeController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	/**
	 * Entrance Fee Modal
	 * Columns - ['id', 'code', 'location', 'entranceFee'];
	 */

	// route : base_url/master_files/entrance_fee
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		// Load the Entrance Fee data
		$EntranceFeeModel = new EntranceFeeModel();
		$data['entrance_fee'] = $EntranceFeeModel->select('entrance_fee.*, account_posting.type')->join('account_posting','account_posting.L_Code = entrance_fee.l_code')->where('account_posting.type <>', 0)->findAll();

		return view('pages/master_files/entrance_fee', $data);
	}

	// route : base_url/master_files/entrance_fee/create
	public function create()
	{
		try {
			$code = $this->request->getPost('code');
			$location = $this->request->getPost('location');
			$entranceFee = $this->request->getPost('entrance_fee');
			$entranceFeeLkr = $this->request->getPost('entrance_fee_lkr');
			$name = $this->request->getPost('name');
			$des = $this->request->getPost('descript');
			$type = $this->request->getPost('type');
			$directPayment = $this->request->getPost('direct_payment') ? 1 : 0;
			$isPrepaid = $this->request->getPost('is_prepaid') ? 1 : 0;

			// Validate the input data
			$validation = service('validation');
			$validation->setRules([
				'code' => 'strip_tags',
				'location' => 'required|strip_tags',
				'entrance_fee' => 'strip_tags|numeric',
				'entrance_fee_lkr' => 'strip_tags|numeric',
				'name' => 'strip_tags|required',
			]);

			if (!$validation->withRequest($this->request)->run()) {
				// Validation failed, return the validation errors
				return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
			}

			// Handle image upload
			$img = $this->request->getFile('img');
			$imgName = '';
			if (!empty($img) && $img->isValid() && !$img->hasMoved()) {
				$imgName = $img->getRandomName();
				$uploadPath = FCPATH . 'uploads/places/';
				// create directory if not exists
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$img->move($uploadPath, $imgName);
			}

			// Create a new EntranceFeeModel instance
			$entranceFeeModel = new EntranceFeeModel();

			// Check if the Entrance fee already exists
			if ($entranceFeeModel->where(['code' => $code, 'location' => $location])->first()) {
				// Return an error response
				return $this->response->setJSON(['status' => 400, 'message' => 'Place Record already exists']);
			}

			// if location has ', Sri Lanka' remove it
			if (strpos(strval($location), ', Sri Lanka') !== false) {
				$location = str_replace(', Sri Lanka', '', $location);
			}

			// Set the data for the new entrance fee
			$data = [
				'name' => $name,
				'code' => $code,
				'location' => $location,
				'description' => $des,
				'entranceFee' => $entranceFee,
				'entranceFeeLkr' => $entranceFeeLkr,
				'img' => $imgName,
				'direct_payment' => $directPayment,
				'is_prepaid' => $isPrepaid,
			];

			$AccountNumbersModel = new AccountNumbersModel();
			$AccountPostingModel = new AccountPostingModel();
			$PCode = $AccountNumbersModel->incrementPRCode();
			$BCode = 4;
			$TCode = 8;
			$HCode = 109;
			$L_code = $BCode . '-' . $TCode . '-' . $HCode . '-' . $PCode;
			$data_x = array(
				'B_Code' => $BCode,
				'T_Code' => $TCode,
				'H_Code' => $HCode,
				'PName' => $data['name'],
				'P_Code' => $PCode,
				'L_Code' => $L_code,
				'type' => $type
			);
			$is_exist = $AccountPostingModel->where('B_Code', $BCode)->where('T_Code', $TCode)->where('H_Code', $HCode)->where('PName', $data['name'])->countAllResults();
			if ($is_exist == 0) {
				if ($AccountPostingModel->insert($data_x)) {
					$data['l_code'] = $L_code;
					$entranceFeeModel->insert($data);
					return json_encode(array('status' => 201, 'message' => 'Place Record created successfully'));
				}
			} else {
				return json_encode(array('status' => 400, 'message' => 'Posting Account already exists'));
			}
		} catch (\Exception $e) {
			// Return an error response
			return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
		}
	}

	// route : base_url/master_files/entrance_fee/bulk_add
	// public function bulk_add()
	// {
	// 	try {
	// 		$data = $this->request->getJSON(true); // Get the JSON payload as an associative array

	// 		if (!is_array($data)) {
	// 			return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'message' => 'Invalid input data format']);
	// 		}

	// 		log_message('critical', 'Processing entry: ' . json_encode($data));

	// 		$errors = [];
	// 		$successCount = 0;

	// 		// Iterate over each entry in the JSON array
	// 		foreach ($data as $entry) {

	// 			// Log the entry data for debugging
	// 			//log_message('critical', 'Processing entry: ' . json_encode($entry));

	// 			$validation = \Config\Services::validation();
	// 			$validation->setRules([
	// 				'code' => 'required',
	// 				'location' => 'required',
	// 				'entranceFee' => 'required|numeric',
	// 			]);

	// 			// !empty($entry['code']) && !empty($entry['location']) && !empty($entry['entranceFee']) && is_numeric($entry['entranceFee'])

	// 			if (empty($entry['code']) || empty($entry['location']) || empty($entry['entranceFee']) || !is_numeric($entry['entranceFee'])) {
	// 				// Log the validation errors
	// 				log_message('error', 'Validation errors: ' . json_encode($validation->getErrors()));

	// 				// Validation failed for this entry
	// 				$errors[] = [
	// 					'entry' => $entry,
	// 					'errors' => "Validation failed",
	// 				];
	// 				continue;
	// 			}

	// 			// Check if the entrance fee already exists
	// 			$entranceFeeModel = new EntranceFeeModel();
	// 			if ($entranceFeeModel->where(['code' => $entry['code'], 'location' => $entry['location']])->first()) {
	// 				// Log the duplicate entry
	// 				log_message('error', 'Duplicate entry found: ' . json_encode($entry));

	// 				// Entry already exists, add to errors
	// 				$errors[] = [
	// 					'entry' => $entry,
	// 					'errors' => ['message' => 'Place Record already exists'],
	// 				];
	// 				continue;
	// 			}

	// 			// Set the data for the new entrance fee
	// 			$entryData = [
	// 				'code' => $entry['code'],
	// 				'location' => $entry['location'],
	// 				'entranceFee' => $entry['entranceFee'],
	// 			];

	// 			// Insert the entry
	// 			if ($entranceFeeModel->insert($entryData)) {
	// 				$successCount++;
	// 			} else {
	// 				// Log the insertion failure
	// 				log_message('error', 'Failed to insert entry: ' . json_encode($entry));

	// 				$errors[] = [
	// 					'entry' => $entry,
	// 					'errors' => ['message' => 'Failed to insert Place'],
	// 				];
	// 			}
	// 		}

	// 		// Prepare the response
	// 		$response = [
	// 			'status' => 207, // Multi-Status
	// 			'message' => "$successCount entries successfully added",
	// 			'errors' => $errors,
	// 		];

	// 		return $this->response->setJSON($response);
	// 	} catch (\Exception $e) {
	// 		// Log the exception
	// 		log_message('error', 'Exception: ' . $e->getMessage());

	// 		// Return an error response
	// 		return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
	// 	}
	// }

	// route : base_url/master_files/entrance_fee/show/{id}
	public function show($id)
	{
		try {
			// Create a new EntranceFeeModel instance
			$entranceFeeModel = new EntranceFeeModel();

			// Get the entrance fee data
			$entranceFee = $entranceFeeModel->select('entrance_fee.*, account_posting.type')->join('account_posting','account_posting.L_Code = entrance_fee.l_code')->where('account_posting.type <>', 0)->find($id);

			// Return the entrance fee data
			return $this->response->setJSON(['status' => 200, 'data' => $entranceFee]);
		} catch (\Exception $e) {
			// Return an error response
			return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
		}
	}

	// route : base_url/master_files/entrance_fee/update
	public function update($id)
	{
		try {
			// $id = $this->request->getPost('id');
			$name = $this->request->getPost('name');
			$code = $this->request->getPost('code');
			$location = $this->request->getPost('location');
			$entranceFee = $this->request->getPost('entrance_fee');
			$entranceFeeLkr = $this->request->getPost('entrance_fee_lkr');
			$des = $this->request->getPost('descript');
			$type = $this->request->getPost('type');
			$directPayment = $this->request->getPost('direct_payment') ? 1 : 0;
			$isPrepaid = $this->request->getPost('is_prepaid') ? 1 : 0;

			// Validate the input data
			$validation = service('validation');
			$validation->setRules([
				'name' => 'required|strip_tags',
				'code' => 'required|strip_tags',
				'location' => 'required|strip_tags',
				'entrance_fee' => 'required|numeric',
				'entrance_fee_lkr' => 'strip_tags|numeric',
			]);

			if (!$validation->withRequest($this->request)->run()) {
				// Validation failed, return the validation errors
				return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
			}

			// Create a new EntranceFeeModel instance
			$entranceFeeModel = new EntranceFeeModel();

			// Check if the Entrance fee already exists
			if ($entranceFeeModel->where(['code' => $code, 'location' => $location])->where('id !=', $id)->countAllResults() > 0) {
				// Return an error response
				return $this->response->setJSON(['status' => 400, 'message' => 'Place Record already exists']);
			}

			$imgName = '';
			$img = $this->request->getFile('img');
			if ($img->isValid() && !$img->hasMoved()) {
				$imgName = $img->getRandomName();
				$uploadPath = FCPATH . 'uploads/places/';
				// create directory if not exists
				if (!is_dir($uploadPath)) {
					mkdir($uploadPath, 0777, true);
				}
				$img->move($uploadPath, $imgName);
			}

			// if location has ', Sri Lanka' remove it
			if (strpos(strval($location), ', Sri Lanka') !== false) {
				$location = str_replace(', Sri Lanka', '', $location);
			}

			// get l_code
			$l_code = $entranceFeeModel->select('l_code')->where('id', $id)->first();

			// Set the data for the new entrance fee
			$data = [
				'name' => $name,
				'code' => $code,
				'location' => $location,
				'description' => $des,
				'entranceFee' => $entranceFee,
				'entranceFeeLkr' => $entranceFeeLkr,
				'direct_payment' => $directPayment,
				'is_prepaid' => $isPrepaid,
			];

			// Append img key if an image was uploaded
			if ($imgName !== '') {
				$data['img'] = $imgName;
			}

			// Update
			if ($entranceFeeModel->update($id, $data)) {
				// Update posting account
				$AccountPostingModel = new AccountPostingModel();
				$AccountPostingModel->where('L_Code', $l_code['l_code'])->set(['PName' => $name,'type' => $type])->update();
				// Return a success response
				return $this->response->setJSON(['status' => 201, 'message' => 'Place Recored updated successfully']);
			}
		} catch (\Exception $e) {
			// Return an error response
			return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
		}
	}

	// route : base_url/master_files/entrance_fee/delete
	public function delete()
	{
		try {
			$id = $this->request->getGet('id');
			$activity = $this->request->getGet('activity');
			// Create a new EntranceFeeModel instance
			$entranceFeeModel = new EntranceFeeModel();

			// get l_code
			$l_code = $entranceFeeModel->select('l_code')->where('id', $id)->first();

			// check if entries exist in account running
			$accountRunningModel = new AccountRunningModel();
			$accountRunning = $accountRunningModel->where('L_Code', $l_code['l_code'])->countAllResults();

			$touractivityModel = new TourActivityModel();
			$activityrecord = $touractivityModel->where('name', $activity)->countAllResults();

			if ($accountRunning > 0 || $activityrecord > 0) {
				// Return an error response
				return $this->response->setJSON(['status' => 400, 'message' => 'Can not delete, Place Record has transactions']);
			}

			$AccountPostingModel = new AccountPostingModel();
			$AccountPostingModel->where('L_Code', $l_code['l_code'])->delete();

			// Delete
			if ($entranceFeeModel->delete($id)) {
				// Return a success response
				return $this->response->setJSON(['status' => 200, 'message' => 'Place Record deleted successfully']);
			}
		} catch (\Exception $e) {
			// Return an error response
			return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
		}
	}
}
