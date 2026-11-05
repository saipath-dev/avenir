<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\VehicleModel;
use App\Models\ConsigneeModel;
use App\Models\SupplierModel;

class VehicleController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/master_files/vehicle
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Load the Room Type data
        $VehicleModel = new VehicleModel();
        $data['vehicles'] = $VehicleModel->select('vehicle.*, supplier.Name')
            ->join('supplier', 'supplier.Sup_Acc_No = vehicle.owner', 'left')
            ->findAll();

        $supplierModel = new SupplierModel();
        $data['suppliers'] = $supplierModel->where('sup_cat', 2)->findAll();

        return view('pages/master_files/vehicle', $data);
    }

    // route : base_url/master_files/vehicle/create
    public function create()
    {
        try {
            $type = $this->request->getPost('type');
            $passenger_count = $this->request->getPost('passenger_count');
            $km_rate = $this->request->getPost('km_rate');
            $v_number = $this->request->getPost('vnu');
            $v_owner = $this->request->getPost('vowner');
            $owner_contact = $this->request->getPost('vownercontact');
            $bata_per_day = $this->request->getPost('bata_per_day');
            $action = $this->request->getPost('action');
            $v_license_expire_date = $this->request->getPost('vled');
            $v_insurance_expire_date = $this->request->getPost('vied');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'type' => 'required|strip_tags',
                'passenger_count' => 'required|strip_tags',
                'km_rate' => 'required|strip_tags',
                'vnu' => 'required|strip_tags',
                'vowner' => 'required|strip_tags',
                'vownercontact' => 'required|strip_tags',
                'action' => 'required|strip_tags',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Create a new RoomTypeModel instance
            $VehicleModel = new VehicleModel();

            if ($action == 1) {
                // Check if the Entrance fee already exists
                if ($VehicleModel->where(['type' => $type, 'v_num' => $v_number, 'action' => 1])->first()) {

                    // Return an error response
                    return $this->response->setJSON(['status' => 400, 'message' => 'Vehicle already exists']);
                }
            } else {
                // Check if the Entrance fee already exists
                if ($VehicleModel->where(['v_num' => $v_number])->first()) {
                    // Return an error response
                    return $this->response->setJSON(['status' => 400, 'message' => 'Vehicle already exists']);
                }
            }
            // log_message('critical', $VehicleModel->getLastQuery());
            // Handle image upload
            $vimg = $this->request->getFile('vimg');
            $vimgName = '';
            if (!empty($vimg) && $vimg->isValid() && !$vimg->hasMoved()) {
                $vimgName = $vimg->getRandomName();
                $uploadPath = FCPATH . 'uploads/vehicle/';
                // create directory if not exists
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                $vimg->move($uploadPath, $vimgName);
            }

            $vlimg = $this->request->getFile('vlimg');
            $vlimgName = '';
            if (!empty($vlimg) && $vlimg->isValid() && !$vlimg->hasMoved()) {
                $vlimgName = $vlimg->getRandomName();
                $uploadPath = FCPATH . 'uploads/vehicle/license';
                // create directory if not exists
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                $vlimg->move($uploadPath, $vlimgName);
            }

            $viimg = $this->request->getFile('viimg');
            $viimgName = '';
            if (!empty($viimg) && $viimg->isValid() && !$viimg->hasMoved()) {
                $viimgName = $viimg->getRandomName();
                $uploadPath = FCPATH . 'uploads/vehicle/insurance';
                // create directory if not exists
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                $viimg->move($uploadPath, $viimgName);
            }

            // Set the data for the new entrance fee
            $data = [
                'type' => $type,
                'passenger_count' => $passenger_count,
                'km_rate' => $km_rate,
                'v_num' => $v_number,
                'owner' => $v_owner,
                'ocontact' => $owner_contact,
                'bata' => $bata_per_day,
                'action' => $action,
                'vimg' => $vimgName,
                'vlimg' => $vlimgName,
                'vehi_license_expire_date' => $v_license_expire_date,
                'viimg' => $viimgName,
                'vehi_insurance_expire_date' => $v_insurance_expire_date
            ];

            // Insert
            if ($VehicleModel->insert($data)) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Vehicle created successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // // route : base_url/master_files/room_type/show/{id}
    public function show($id)
    {
        try {
            // Create a new EntranceFeeModel instance
            $VehicleModel = new VehicleModel();

            // Get the entrance fee data
            $vehicleDetails = $VehicleModel->find($id);

            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' => $vehicleDetails]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // // route : base_url/master_files/entrance_fee/update
    public function update()
    {
        try {
            $id = $this->request->getPost('row_id');
            $type = $this->request->getPost('type_up');
            $passenger_count = $this->request->getPost('passenger_count_up');
            $km_rate = $this->request->getPost('km_rate_up');
            $v_number = $this->request->getPost('vnu_up');
            $v_owner = $this->request->getPost('vowner_up');
            $owner_contact = $this->request->getPost('vownercontact_up');
            $bata_per_day = $this->request->getPost('bata_per_day_up');
            $action = $this->request->getPost('action_up');
            $v_license_expire_date = $this->request->getPost('vled_up');
            $v_insurance_expire_date = $this->request->getPost('vied_up');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'row_id' => 'required|strip_tags',
                'type_up' => 'required|strip_tags',
                'passenger_count_up' => 'required|strip_tags',
                'km_rate_up' => 'required|strip_tags',
                'vnu_up' => 'required|strip_tags',
                'vowner_up' => 'required|strip_tags',
                'vownercontact_up' => 'required|strip_tags',
                'action_up' => 'required|strip_tags',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Create a new EntranceFeeModel instance
            $VehicleModel = new VehicleModel();

            if ($action == 1) {
                // Check if the Room Type already exists
                if ($VehicleModel->where(['type' => $type, 'v_num' => $v_number, 'action' => 1])->where('id !=', $id)->first()) {
                    // Return an error response
                    return $this->response->setJSON(['status' => 400, 'message' => 'Vehicle already exists']);
                }
            } else {
                // Check if the Room Type already exists
                if ($VehicleModel->where(['v_num' => $v_number])->where('id !=', $id)->first()) {
                    // Return an error response
                    return $this->response->setJSON(['status' => 400, 'message' => 'Vehicle already exists']);
                }
            }

            // Handle image upload
            $vimg = $this->request->getFile('vimg');
            $vimgName = '';
            if (!empty($vimg) && $vimg->isValid() && !$vimg->hasMoved()) {
                $vimgName = $vimg->getRandomName();
                $uploadPath = FCPATH . 'uploads/vehicle/';
                // create directory if not exists
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                $vimg->move($uploadPath, $vimgName);
            }

            $vlimg = $this->request->getFile('vlimg');
            $vlimgName = '';
            if (!empty($vlimg) && $vlimg->isValid() && !$vlimg->hasMoved()) {
                $vlimgName = $vlimg->getRandomName();
                $uploadPath = FCPATH . 'uploads/vehicle/license';
                // create directory if not exists
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                $vlimg->move($uploadPath, $vlimgName);
            }

            $viimg = $this->request->getFile('viimg');
            $viimgName = '';
            if (!empty($viimg) && $viimg->isValid() && !$viimg->hasMoved()) {
                $viimgName = $viimg->getRandomName();
                $uploadPath = FCPATH . 'uploads/vehicle/insurance';
                // create directory if not exists
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                $viimg->move($uploadPath, $viimgName);
            }


            // Set the data for the new Room Type
            $data = [
                'type' => $type,
                'passenger_count' => $passenger_count,
                'km_rate' => $km_rate,
                'v_num' => $v_number,
                'owner' => $v_owner,
                'ocontact' => $owner_contact,
                'bata' => $bata_per_day,
                'action' => $action,
                'vehi_license_expire_date' => $v_license_expire_date,
                'vehi_insurance_expire_date' => $v_insurance_expire_date
            ];

            // Append img key if an image was uploaded
            if ($vimgName !== '') {
                $data['vimg'] = $vimgName;
            }

            if ($vlimgName !== '') {
                $data['vlimg'] = $vlimgName;
            }

            if ($viimgName !== '') {
                $data['viimg'] = $viimgName;
            }

            // Update
            if ($VehicleModel->update($id, $data)) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Vehicle updated successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // // route : base_url/master_files/room_type/delete
    public function deleteVehicle()
    {
        try {
            $row_id = $this->request->getGet('row_id');

            $ConsigneeModel = new ConsigneeModel();
            if ($ConsigneeModel->where('vehicle', $row_id)->findAll()) {
                // Return an error response
                return $this->response->setJSON(['status' => 400, 'message' => 'Vehicle already exists within quotations']);
            }

            // Create a new EntranceFeeModel instance
            $VehicleModel = new VehicleModel();

            // Delete
            if ($VehicleModel->delete($row_id)) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Vehicle deleted successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
