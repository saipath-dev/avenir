<?php

namespace App\Controllers\API\V1\Inquiry;

use App\Models\ApiInquiryModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

class ApiInquiryController extends ResourceController
{
    use ResponseTrait;
    
    private function generateWebInquiryCode()
    {
        $today = date('dmY'); // Format: DDMMYYYY
        $prefix = 'WEB';
        
        $model = new ApiInquiryModel();
        
        // Find latest code for today with WEB prefix
        $latestInquiry = $model->like('inquiry_code', $prefix . $today)
                              ->orderBy('inquiry_code', 'DESC')
                              ->first();
        
        // Extract and increment sequence
        if ($latestInquiry && !empty($latestInquiry['inquiry_code'])) {
            $sequence = (int)substr($latestInquiry['inquiry_code'], -3) + 1;
        } else {
            $sequence = 1;
        }
        
        // Format as 3-digit sequence
        $paddedSequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        
        return $prefix . $today . $paddedSequence;
    }

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // create new group profile - route : base_url/settings/groupProfile/create
    public function createInquiry()
    {
        try {

            $validation = \Config\Services::validation();
            $validation->setRules([
                'name'      => 'required|max_length[255]',
                'email'     => 'required|valid_email|max_length[255]',
                'mobilenum' => 'required|numeric|max_length[20]',
                'inquiry'   => 'required',
                'address'   => 'required|max_length[255]',
                'place'     => 'required|max_length[100]',
                'date'      => 'required|valid_date',
                'rooms'     => 'required|integer',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->respond([
                    "message" => current($validation->getErrors()),
                    "errors" => $validation->getErrors(),
                ], 400);
            }

            // Generate inquiry code
            $inquiry_code = $this->generateWebInquiryCode();

            $data = [
                'inquiry_code' => $inquiry_code,
                'Name'      => strip_tags(htmlspecialchars($this->request->getVar('name'), ENT_QUOTES, 'UTF-8')),
                'Email'     => strip_tags(htmlspecialchars($this->request->getVar('email'), ENT_QUOTES, 'UTF-8')),
                'Mobilenum' => strip_tags(htmlspecialchars($this->request->getVar('mobilenum'), ENT_QUOTES, 'UTF-8')),
                'Inquiry'   => strip_tags(htmlspecialchars($this->request->getVar('inquiry'), ENT_QUOTES, 'UTF-8')),
                'Address'   => strip_tags(htmlspecialchars($this->request->getVar('address'), ENT_QUOTES, 'UTF-8')),
                'Place'     => strip_tags(htmlspecialchars($this->request->getVar('place'), ENT_QUOTES, 'UTF-8')),
                'Date'      => strip_tags(htmlspecialchars($this->request->getVar('date'), ENT_QUOTES, 'UTF-8')),
                'Rooms'     => strip_tags(htmlspecialchars($this->request->getVar('rooms'), ENT_QUOTES, 'UTF-8')),
                'responded' => 0
            ];

            $model = new ApiInquiryModel();
            $model->insert($data);

            return $this->respond(['status' => 201, 'message' => 'Inquiry created successfully'], 201);
        } catch (\Exception $e) {
            log_message('critical', $e);
            return $this->failServerError($e->getMessage());
        }
    }
}
