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
use App\Models\HotelRateModel;

class RoomTypeController extends BaseController
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

        // Load the Room Type data
        $roomTypeModel = new RoomTypeModel();
        $data['room_type'] = $roomTypeModel->findAll();

        // $countryModel = new CountryModel();
        // $data['countries'] = $countryModel->findAll();

        // $id = $this->request->getGet('id');
        // if (!empty($id)) {
        // 	$data['customer'] = $CustomerModel->where('ID', $id)->first();
        // }

        return view('pages/master_files/room_type', $data);
    }

    // route : base_url/master_files/room_type/create
    public function create()
    {
        try {
            $type = $this->request->getPost('rtype');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'rtype' => 'required'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Create a new RoomTypeModel instance
            $roomTypeModel = new RoomTypeModel();

            // Check if the Entrance fee already exists
            if ($roomTypeModel->where(['rtype' => $type])->first()) {
                // Return an error response
                return $this->response->setJSON(['status' => 400, 'message' => 'Room Type already exists']);
            }

            // Set the data for the new entrance fee
            $data = [
                'rtype' => $type
            ];

            // Insert
            if ($roomTypeModel->insert($data)) {
                // Return a success response
                return $this->response->setJSON(['status' => 201, 'message' => 'Room Type created successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/room_type/show/{id}
    public function show($id)
    {
        try {
            // Create a new EntranceFeeModel instance
            $roomTypeModel = new RoomTypeModel();

            // Get the entrance fee data
            $roomType = $roomTypeModel->find($id);

            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' => $roomType]);
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
            $rtype = $this->request->getPost('code');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'code' => 'required'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Set the data for the new Room Type
            $data = [
                'rtype' => $rtype
            ];

            // Create a new EntranceFeeModel instance
            $roomTypeModel = new RoomTypeModel();

            // Check if the Room Type already exists
            if ($roomTypeModel->where(['rtype' => $rtype])->where('id !=', $id)->first()) {
                // Return an error response
                return $this->response->setJSON(['status' => 409, 'message' => 'Room Type already exists']);
            }
                if($roomTypeModel->update($id, $data)) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Room Type updated successfully']);
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

            $hotelRateModel = new HotelRateModel();
            if ($hotelRateModel->where(['room_type' => $id])->first()) {
                // Return an error response
                return $this->response->setJSON(['status' => 400, 'message' => 'Room Type already exists within hotel rates']);
            }

            // Create a new EntranceFeeModel instance
            $roomTypeModel = new RoomTypeModel();

            // Delete
            if ($roomTypeModel->delete($id)) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Room Type deleted successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/room_type/all_room_types
    public function all_room_types()
    {
        $roomTypeModel = new RoomTypeModel();

        $data = $roomTypeModel->findAll();
        return json_encode($data);
    }
}
