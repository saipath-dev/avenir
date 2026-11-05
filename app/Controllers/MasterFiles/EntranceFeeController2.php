<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;

use App\Models\EntranceFeeModel;
use Config\Services;

class EntranceFeeController2 extends BaseController
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
		$data['entrance_fee'] = $EntranceFeeModel->findAll();

		return view('pages/master_files/entrance_fee', $data);
	}

	// route : base_url/master_files/entrance_fee/create
	public function create()
	{
		try {
			$code = $this->request->getPost('code');
			$location = $this->request->getPost('location');
			$entranceFee = $this->request->getPost('entrance_fee');

			// Validate the input data
			$validation = service('validation');
			$validation->setRules([
				'code' => 'required',
				'location' => 'required',
				'entrance_fee' => 'required|numeric',
			]);

			if (!$validation->withRequest($this->request)->run()) {
				// Validation failed, return the validation errors
				return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
			}

			// Create a new EntranceFeeModel instance
			$entranceFeeModel = new EntranceFeeModel();

			// Check if the Entrance fee already exists
			if ($entranceFeeModel->where(['code' => $code, 'location' => $location])->first()) {
				// Return an error response
				return $this->response->setJSON(['status' => 400, 'message' => 'Entrance fee already exists']);
			}

			// Set the data for the new entrance fee
			$data = [
				'code' => $code,
				'location' => $location,
				'entranceFee' => $entranceFee,
			];

			// Insert
			if ($entranceFeeModel->insert($data)) {
				// Return a success response
				return $this->response->setJSON(['status' => 201, 'message' => 'Entrance fee created successfully']);
			}
		} catch (\Exception $e) {
			// Return an error response
			return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
		}
	}

    public function bulk_add()
    {
        try {
            $data = $this->request->getJSON(true); // Get the JSON payload as an associative array

            if (!is_array($data)) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'message' => 'Invalid input data format']);
            }

            $errors = [];
            $successCount = 0;

            // Iterate over each entry in the JSON array
            foreach ($data as $entry) {
                // Convert 'entranceFee' to 'entrance_fee'
                $entry['entrance_fee'] = $entry['entranceFee'];
                unset($entry['entranceFee']);

                // Log the entry data for debugging
                log_message('debug', 'Processing entry: ' . json_encode($entry));

                $validation = \Config\Services::validation();
                $validation->setRules([
                    'code' => 'required',
                    'location' => 'required',
                    'entrance_fee' => 'required|numeric',
                ]);

                if (!$validation->run($entry)) {
                    // Log the validation errors
                    log_message('error', 'Validation errors: ' . json_encode($validation->getErrors()));

                    // Validation failed for this entry
                    $errors[] = [
                        'entry' => $entry,
                        'errors' => $validation->getErrors(),
                    ];
                    continue;
                }

                // Check if the entrance fee already exists
                $entranceFeeModel = new EntranceFeeModel();
                if ($entranceFeeModel->where(['code' => $entry['code'], 'location' => $entry['location']])->first()) {
                    // Log the duplicate entry
                    log_message('error', 'Duplicate entry found: ' . json_encode($entry));

                    // Entry already exists, add to errors
                    $errors[] = [
                        'entry' => $entry,
                        'errors' => ['message' => 'Entrance fee already exists'],
                    ];
                    continue;
                }

                // Set the data for the new entrance fee
                $entryData = [
                    'code' => $entry['code'],
                    'location' => $entry['location'],
                    'entranceFee' => $entry['entrance_fee'],
                ];

                // Insert the entry
                if ($entranceFeeModel->insert($entryData)) {
                    $successCount++;
                } else {
                    // Log the insertion failure
                    log_message('error', 'Failed to insert entry: ' . json_encode($entry));

                    $errors[] = [
                        'entry' => $entry,
                        'errors' => ['message' => 'Failed to insert entrance fee'],
                    ];
                }
            }

            // Prepare the response
            $response = [
                'status' => 207, // Multi-Status
                'message' => "$successCount entries successfully added",
                'errors' => $errors,
            ];

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }



    // route : base_url/master_files/entrance_fee/show/{id}
	public function show($id)
	{
		try {
			// Create a new EntranceFeeModel instance
			$entranceFeeModel = new EntranceFeeModel();

			// Get the entrance fee data
			$entranceFee = $entranceFeeModel->find($id);

			// Return the entrance fee data
			return $this->response->setJSON(['status' => 200, 'data' => $entranceFee]);
		} catch (\Exception $e) {
			// Return an error response
			return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
		}
	}

	// route : base_url/master_files/entrance_fee/update
	public function update()
	{
		try {
			$id = $this->request->getPost('id');
			$code = $this->request->getPost('code');
			$location = $this->request->getPost('location');
			$entranceFee = $this->request->getPost('entrance_fee');

			// Validate the input data
			$validation = service('validation');
			$validation->setRules([
				'code' => 'required',
				'location' => 'required',
				'entrance_fee' => 'required|numeric',
			]);

			if (!$validation->withRequest($this->request)->run()) {
				// Validation failed, return the validation errors
				return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
			}

			// Create a new EntranceFeeModel instance
			$entranceFeeModel = new EntranceFeeModel();

			// Check if the Entrance fee already exists
			if ($entranceFeeModel->where(['code' => $code, 'location' => $location])->where('id !=', $id)->first()) {
				// Return an error response
				return $this->response->setJSON(['status' => 400, 'message' => 'Entrance fee already exists']);
			}

			// Set the data for the new entrance fee
			$data = [
				'code' => $code,
				'location' => $location,
				'entranceFee' => $entranceFee,
			];

			// Update
			if ($entranceFeeModel->update($id, $data)) {
				// Return a success response
				return $this->response->setJSON(['status' => 200, 'message' => 'Entrance fee updated successfully']);
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

			// Create a new EntranceFeeModel instance
			$entranceFeeModel = new EntranceFeeModel();

			// Delete
			if ($entranceFeeModel->delete($id)) {
				// Return a success response
				return $this->response->setJSON(['status' => 200, 'message' => 'Entrance fee deleted successfully']);
			}
		} catch (\Exception $e) {
			// Return an error response
			return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
		}
	}
}
