<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\TourQuotationsModel;
use App\Models\ConsigneeModel;
use App\Models\DocumentUploadModel;

class TourDocumentationController extends BaseController
{
    public function index()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('t');

        $documentuploadmodel = new DocumentUploadModel();
        $tourConsigneeModel = new ConsigneeModel();

        $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();

        $log_sheet = $documentuploadmodel->where('tour_id', $tour_id)->where('type', 1)->where('delete', 0)->findAll();
        $tour_settlement = $documentuploadmodel->where('tour_id', $tour_id)->where('type', 2)->where('delete', 0)->findAll();
        $multiple_option = $documentuploadmodel->where('tour_id', $tour_id)->where('type', 3)->where('delete', 0)->findAll();

        $data['tour_id'] = $tour_id;
        $data['tour_main_details'] = $tour_main_details;
        $data['log_sheet'] = $log_sheet;
        $data['tour_settlement'] = $tour_settlement;
        $data['multiple_option'] = $multiple_option;

        return view('pages/tour/tour_documentation', $data);
    }

    // upload_log image function 
    public function upload_log()
    {
        try {

            $tour_id = $this->request->getPost('tour_id');
            $files = $this->request->getFiles();

            foreach ($files['images'] as $file) {
                if ($file->isValid() && !$file->hasMoved()) {


                    $uploadPath = WRITEPATH . 'uploads/tour_doc/' . $tour_id . '/log_sheet';
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }

                    $imageCount = count(glob($uploadPath . '/image*')) + 1;
                    $fileName = 'image' . $imageCount . '.' . $file->getClientExtension();
                    if (file_exists($uploadPath . '/' . $fileName)) {
                        $fileName = 'image' . $imageCount . '_' . time() . '.' . $file->getClientExtension();
                    }
                    $file->move($uploadPath, $fileName);

                    $DocumentUploadModel = new DocumentUploadModel();
                    $DocumentUploadModel->insert([
                        'tour_id' => $tour_id,
                        'type' => 1,
                        'image_path' => $fileName,
                        'user' => session()->get('username'),
                        'date' => date('Y-m-d')
                    ]);
                }
            }

            return $this->response->setJSON(["status" => 200, "message" => "Successfully Added!"]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function upload_tour_settlemet()
    {
        try {

            $tour_id = $this->request->getPost('tour_id');
            $files = $this->request->getFiles();

            foreach ($files['images'] as $file) {
                if ($file->isValid() && !$file->hasMoved()) {


                    $uploadPath = WRITEPATH . 'uploads/tour_doc/' . $tour_id . '/tour_settlement';
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }

                    $imageCount = count(glob($uploadPath . '/image*')) + 1;
                    $fileName = 'image' . $imageCount . '.' . $file->getClientExtension();
                    if (file_exists($uploadPath . '/' . $fileName)) {
                        $fileName = 'image' . $imageCount . '_' . time() . '.' . $file->getClientExtension();
                    }
                    $file->move($uploadPath, $fileName);

                    $DocumentUploadModel = new DocumentUploadModel();
                    $DocumentUploadModel->insert([
                        'tour_id' => $tour_id,
                        'type' => 2,
                        'image_path' => $fileName,
                        'user' => session()->get('username'),
                        'date' => date('Y-m-d')
                    ]);
                }
            }

            return $this->response->setJSON(["status" => 200, "message" => "Successfully Added!"]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function upload_multiple_option()
    {
        try {

            $tour_id = $this->request->getPost('tour_id');
            $files = $this->request->getFiles();

            foreach ($files['images'] as $file) {
                if ($file->isValid() && !$file->hasMoved()) {


                    $uploadPath = WRITEPATH . 'uploads/tour_doc/' . $tour_id . '/multiple_option';
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }

                    $imageCount = count(glob($uploadPath . '/image*')) + 1;
                    $fileName = 'image' . $imageCount . '.' . $file->getClientExtension();
                    if (file_exists($uploadPath . '/' . $fileName)) {
                        $fileName = 'image' . $imageCount . '_' . time() . '.' . $file->getClientExtension();
                    }
                    $file->move($uploadPath, $fileName);

                    $DocumentUploadModel = new DocumentUploadModel();
                    $DocumentUploadModel->insert([
                        'tour_id' => $tour_id,
                        'type' => 3,
                        'image_path' => $fileName,
                        'user' => session()->get('username'),
                        'date' => date('Y-m-d')
                    ]);
                }
            }

            return $this->response->setJSON(["status" => 200, "message" => "Successfully Added!"]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function get_image_base64($id, $type)
    {
        $documentuploadmodel = new DocumentUploadModel();
        $imageData = $documentuploadmodel->where('delete', 0)->find($id);

        if ($type == 1) {
            $filePath = WRITEPATH . '/uploads/tour_doc/' . $imageData['tour_id'] . '/log_sheet/' . $imageData['image_path'];
        } else if ($type == 2) {
            $filePath = WRITEPATH . '/uploads/tour_doc/' . $imageData['tour_id'] . '/tour_settlement/' . $imageData['image_path'];
        } else if ($type == 3) {
            $filePath = WRITEPATH . '/uploads/tour_doc/' . $imageData['tour_id'] . '/multiple_option/' . $imageData['image_path'];
        }

        if (!$imageData) {
            return $this->response->setJSON(['status' => 404, 'message' => 'Image not found.']);
        }

        // $filePath = WRITEPATH . '/uploads/tour_doc/' . $imageData['tour_id'] . '/log_sheet/' . $imageData['image_path'];

        if (!file_exists($filePath)) {
            return $this->response->setJSON(['status' => 404, 'message' => 'Image not found. Filepath Error.']);
        }

        $imageContent = file_get_contents($filePath);
        $base64 = base64_encode($imageContent);

        return $this->response->setJSON(['status' => 200, 'base64' => $base64]);
    }

    public function delete_image($id)
    {
        try {
            $documentuploadmodel = new DocumentUploadModel();
            // $id = $this->request->getGet('id');

            $delete  = $documentuploadmodel->update($id, ['delete' => 1, 'delete_user' => session()->get('username'), 'delete_date' => date('Y-m-d')]);

            if (!$delete) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Image not found.']);
            } else {
                return $this->response->setJSON(['status' => 200, 'message' => 'Image Deleted Successfully.']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function report()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $keyword = $this->request->getGet('keyword');

        $keyword = urldecode($keyword);

        $TourQuotationsModel = new TourQuotationsModel();

        $tourDetailsQuery = $TourQuotationsModel->select('tour_quotations.quotation_id AS tour_id, Max(consignee.end_date) AS end_date, Min(tour_quotations.tour_date) AS start_date, Max(tour_quotations.confirm) As Confirm, Max(tour_quotations.finalize) As Finalize ,Max(consignee.consignee) AS consignee, Max(consignee.agent_name) AS agent_name,Max(consignee.create_user) AS create_user,Max(consignee.create_time) AS create_time, consignee.mobile as mobile,consignee.email AS email, consignee.passenger_count AS passenger_count, Sum(tour_quotations.extra_act_fee) as activity_fee, Sum(tour_quotations.extra_kilo) as extra_km, Sum(tour_quotations.distance) As fullkm, Max(consignee.vehicle_rate) AS vehicle_rate, consignee.manual_id, consignee.create_user')->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')->groupBy('tour_quotations.quotation_id');

        $queries = [];

        if (!empty($keyword)) {
            $keywordQuery = clone $tourDetailsQuery;
            $keywordQuery = $keywordQuery
                ->orWhere('consignee.tour_id like', "%$keyword%")
                ->orWhere('consignee.consignee like', "%$keyword%")
                ->orWhere('consignee.manual_id like', "%$keyword%");
            $queries[] = $keywordQuery;
        }

        if (!empty($queries)) {
            $tourDetailsQuery = array_shift($queries);
            foreach ($queries as $query) {
                $tourDetailsQuery = $tourDetailsQuery->union($query, false);
            }
            $tourDetails = $tourDetailsQuery->get()->getResult();
        } else {
            $tourDetails = [];
        }

        $data['tour_details'] = $tourDetails;

        // $tour_id = $this->request->getGet('t');

        // $documentuploadmodel = new DocumentUploadModel();
        // $tourConsigneeModel = new ConsigneeModel();

        // $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();

        // $log_sheet = $documentuploadmodel->where('tour_id', $tour_id)->where('type', 1)->where('delete', 0)->findAll();
        // $tour_settlement = $documentuploadmodel->where('tour_id', $tour_id)->where('type', 2)->where('delete', 0)->findAll();
        // $multiple_option = $documentuploadmodel->where('tour_id', $tour_id)->where('type', 3)->where('delete', 0)->findAll();

        // $data['tour_id'] = $tour_id;
        // $data['tour_main_details'] = $tour_main_details;
        // $data['log_sheet'] = $log_sheet;
        // $data['tour_settlement'] = $tour_settlement;
        // $data['multiple_option'] = $multiple_option;

        return view('pages/tour/tour_documentation_report', $data);
    }

    public function get_tour_documentation()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('t');

        $documentuploadmodel = new DocumentUploadModel();
        $tourConsigneeModel = new ConsigneeModel();

        $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();

        $log_sheet = $documentuploadmodel->where('tour_id', $tour_id)->where('type', 1)->where('delete', 0)->findAll();
        $tour_settlement = $documentuploadmodel->where('tour_id', $tour_id)->where('type', 2)->where('delete', 0)->findAll();
        $multiple_option = $documentuploadmodel->where('tour_id', $tour_id)->where('type', 3)->where('delete', 0)->findAll();

        $data['tour_id'] = $tour_id;
        $data['tour_main_details'] = $tour_main_details;
        $data['log_sheet'] = $log_sheet;
        $data['tour_settlement'] = $tour_settlement;
        $data['multiple_option'] = $multiple_option;

        return view('pages/tour/tour_documentation_review', $data);

    }

    public function accept_image($id, $type)
    {
        try {
            $documentuploadmodel = new DocumentUploadModel();
            // $id = $this->request->getGet('id');

            $accept  = $documentuploadmodel->update($id, ['status' => 1, 'accept_user' => session()->get('username'), 'accept_date' => date('Y-m-d')]);

            if (!$accept) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Image not found.']);
            } else {
                return $this->response->setJSON(['status' => 200, 'message' => 'Image Accepted Successfully.']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function reject_image($id, $type)
    {
        try {
            $documentuploadmodel = new DocumentUploadModel();
            // $id = $this->request->getGet('id');

            $reject  = $documentuploadmodel->update($id, ['status' => 2, 'reject_user' => session()->get('username'), 'reject_date' => date('Y-m-d')]);

            if (!$reject) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Image not found.']);
            } else {
                return $this->response->setJSON(['status' => 200, 'message' => 'Image Rejected Successfully.']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
