<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountNumbersModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\CountryModel;
use App\Models\CustomerModel;
use App\Models\RoomTypeModel;
use App\Models\TransferMilageModel;
use App\Models\TourQuotationsModel;
use Exception;
use PhpParser\JsonDecoder;

class TransferMilageController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/master_files/customer
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Load the Transfer Milage data
        $transferMilageModel = new TransferMilageModel();
        $data['transfer_milage'] = $transferMilageModel->findAll();

        // $countryModel = new CountryModel();
        // $data['countries'] = $countryModel->findAll();

        // $id = $this->request->getGet('id');
        // if (!empty($id)) {
        // 	$data['customer'] = $CustomerModel->where('ID', $id)->first();
        // }

        return view('pages/master_files/transfer_milage', $data);
    }

    // route : base_url/master_files/room_type/create
    public function create()
    {
        try {
            $transfer_code = $this->request->getPost('transfer_code');
            $from = $this->request->getPost('from');
            $to = $this->request->getPost('to');
            $estimated = $this->request->getPost('estimated');
            $actual = $this->request->getPost('actual');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'transfer_code' => 'required',
                'from' => 'required',
                'to' => 'required',
                'estimated' => 'required',
                'actual' => 'required'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Create a new RoomTypeModel instance
            $transferMilageModel = new TransferMilageModel();

            // if from has ', Sri Lanka' remove it
            if (strpos(strval($from), ', Sri Lanka') !== false) {
                $from = str_replace(', Sri Lanka', '', $from);
            }

            // if to has ', Sri Lanka' remove it
            if (strpos(strval($to), ', Sri Lanka') !== false) {
                $to = str_replace(', Sri Lanka', '', $to);
            }

            // Check if the Entrance fee already exists
            if ($transferMilageModel->where(['transfer_code' => $transfer_code, 'from' => $from, 'to' => $to])->first()) {
                // Return an error response
                return $this->response->setJSON(['status' => 400, 'message' => 'Tranfer already exists']);
            }

            try {
                // if estimated has 'km' remove it and convert to float
                if (strpos(strval($estimated), 'km') !== false) {
                    $estimated = str_replace('km', '', $estimated);
                }
                $estimated = floatval($estimated);

                // if actual has 'km' remove it and convert to float
                if (strpos(strval($actual), 'km') !== false) {
                    $actual = str_replace('km', '', $actual);
                }
                $actual = floatval($actual);
            } catch (Exception $e) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid estimated or actual distance. It should be a number followed by "km"']);
            }

            // Set the data for the new entrance fee
            $data = [
                'transfer_code' => $transfer_code,
                'from' => $from,
                'to' => $to,
                'estimated_mileage' => $estimated,
                'actual_mileage' => $actual
            ];

            // Insert
            if ($transferMilageModel->insert($data)) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Transfer created successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function new_create($from_location, $to_location, $gdistance, $distance)
    {
        $transferMilageModel = new TransferMilageModel();
        // Check if the Entrance fee already exists
        if ($transferMilageModel->where(['from' => $from_location, 'to' => $to_location])->first()) {
            // Return an error response
            // return json_encode(['status' => 400, 'message' => 'Tranfer already exists']);
            $transferMilageModel->where(['from' => $from_location, 'to' => $to_location])->set(['estimated_mileage' => $gdistance, 'actual_mileage' => $distance])->update();
            // log_message('critical', $transferMilageModel->getLastQuery());
        } else {

            // Set the data for the new entrance fee
            $data = [
                'from' => $from_location,
                'to' => $to_location,
                'estimated_mileage' => $gdistance,
                'actual_mileage' => $distance
            ];

            // Insert
            if ($transferMilageModel->insert($data)) {
                // Return a success response
                // log_message('critical', $transferMilageModel->getLastQuery());
                return json_encode(['status' => 200, 'message' => 'Transfer created successfully']);
            }
        }
        // return json_encode(['status' => 500, 'message' => 'Transfer created successfully']);
    }

    // route : base_url/master_files/room_type/bulk_add

    //post [
    //  {
    //    "transfer_code": "AHU/BER",
    //    "transfer": "AHUNGALLA  TO BERUWELLA",
    //    "milage": 18,
    //    "milage2": 28.98
    //  },
    //  {
    //    "transfer_code": "AMP/YAL",
    //    "transfer": "AMPARA TO YALA",
    //    "milage": 138,
    //    "milage2": 222.18
    //  }
    //]

    public function bulk_add()
    {
        try {
            $data = $this->request->getJSON(true); // Get the JSON payload as an associative array

            if (!is_array($data)) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'message' => 'Invalid input data format']);
            }

            log_message('critical', 'Processing entry: ' . json_encode($data));

            $errors = [];
            $successCount = 0;

            // Iterate over each entry in the JSON array
            foreach ($data as $entry) {

                // Log the entry data for debugging
                //log_message('critical', 'Processing entry: ' . json_encode($entry));

                $validation = \Config\Services::validation();
                $validation->setRules([
                    'transfer_code' => 'required',
                    'milage' => 'required|numeric',
                    'milage2' => 'required|numeric'
                ]);


                if (empty($entry['transfer_code']) || empty($entry['milage']) || empty($entry['milage2']) || !is_numeric($entry['milage']) || !is_numeric($entry['milage2'])) {
                    // Log the validation errors
                    log_message('error', 'Validation errors: ' . json_encode($validation->getErrors()));

                    // Validation failed for this entry
                    $errors[] = [
                        'entry' => $entry,
                        'errors' => "Validation failed",
                    ];
                    continue;
                }

                // Check if the entrance fee already exists
                $transferMilageModel = new TransferMilageModel();
                if ($transferMilageModel->where(['transfer_code' => $entry['transfer_code'], 'milage' => $entry['milage']])->first()) {
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
                    'transfer_code' => $entry['transfer_code'],
                    'milage' => $entry['milage'],
                    'milage2' => $entry['milage2']
                ];

                // Insert the entry
                if ($transferMilageModel->insert($entryData)) {
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
            // Log the exception
            log_message('error', 'Exception: ' . $e->getMessage());

            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/room_type/show/{id}
    public function show($id)
    {
        try {
            // Create a new EntranceFeeModel instance
            $transferMilageModel = new TransferMilageModel();

            // Get the entrance fee data
            $transferMilag = $transferMilageModel->find($id);

            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' => $transferMilag]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/entrance_fee/update/{id}
    public function update($id)
    {
        try {
            $transfer_code = $this->request->getPost('transfer_code');
            // $transfer = $this->request->getPost('tranfer');
            $from = $this->request->getPost('from');
            $to = $this->request->getPost('to');
            $estimated = $this->request->getPost('estimated');
            $actual = $this->request->getPost('actual');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'transfer_code' => 'required',
                // 'tranfer' => 'required',
                'from' => 'required',
                'to' => 'required',
                'estimated' => 'required',
                'actual' => 'required'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Create a new TransferMilageModel instance
            $transferMilageModel = new TransferMilageModel();

            // if from has ', Sri Lanka' remove it
            if (strpos(strval($from), ', Sri Lanka') !== false) {
                $from = str_replace(', Sri Lanka', '', $from);
            }

            // if to has ', Sri Lanka' remove it
            if (strpos(strval($to), ', Sri Lanka') !== false) {
                $to = str_replace(', Sri Lanka', '', $to);
            }

            // Check if the transfer already exists
            if ($transferMilageModel->where(['transfer_code' => $transfer_code, 'from' => $from, 'to' => $to])->where('id !=', $id)->first()) {
                // Return an error response
                return $this->response->setJSON(['status' => 400, 'message' => 'Transfer already exists']);
            }

            try {
                // if estimated has 'km' remove it and convert to float
                if (strpos(strval($estimated), 'km') !== false) {
                    $estimated = str_replace('km', '', $estimated);
                }
                $estimated = floatval($estimated);

                // if actual has 'km' remove it and convert to float
                if (strpos(strval($actual), 'km') !== false) {
                    $actual = str_replace('km', '', $actual);
                }
                $actual = floatval($actual);
            } catch (\Exception $e) {
                // Exception occurred while converting the distance to float
                return $this->response->setJSON(['status' => 500, 'message' => 'Invalid estimated or actual distance. It should be a number followed by "km"']);
            }

            // Set the data for the updated transfer
            $data = [
                'transfer_code' => $transfer_code,
                'from' => $from,
                'to' => $to,
                'estimated_mileage' => $estimated,
                'actual_mileage' => $actual
            ];

            // Update the transfer
            if ($transferMilageModel->update($id, $data)) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Transfer updated successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/room_type/delete
    public function delete()
    {
        try {
            $id = $this->request->getGet('id');

            $TourQuotationsModel = new TourQuotationsModel();
            if ($TourQuotationsModel->where('transfer_code', $id)->findAll()) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Transfer Code  already exists within quotations']);
            }

            // Create a new EntranceFeeModel instance
            $transferMilageModel = new TransferMilageModel();

            // Delete
            if ($transferMilageModel->delete($id)) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Transfer deleted successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
