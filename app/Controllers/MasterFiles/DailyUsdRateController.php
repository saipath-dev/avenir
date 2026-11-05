<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\DailyUsdRateModel;

class DailyUsdRateController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/master_files/daily_usd_rate
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $dailyUsdRateModel = new DailyUsdRateModel();
        $data['rates'] = $dailyUsdRateModel->orderBy('rate_date', 'DESC')->findAll();

        return view('pages/master_files/daily_usd_rate', $data);
    }

    // route : base_url/master_files/daily_usd_rate/create
    public function create()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'rate_date' => 'required|valid_date',
                'rate' => 'required|numeric',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
            }

            $this->upsertRate($this->request->getVar('rate_date'), $this->request->getVar('rate'));

            return $this->response->setJSON(["status" => 200, "message" => "Daily USD Rate saved successfully!"]);
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/daily_usd_rate/quick_save
    public function quick_save()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'rate_date' => 'required|valid_date',
                'rate' => 'required|numeric',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
            }

            $rateDate = $this->request->getVar('rate_date');
            $rate = $this->request->getVar('rate');

            $this->upsertRate($rateDate, $rate);

            return $this->response->setJSON(["status" => 200, "message" => "Rate saved successfully!", "rate" => $rate, "rate_date" => $rateDate]);
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/daily_usd_rate/today
    public function today()
    {
        helper('daily_usd_rate');

        $rate = get_usd_rate();

        return $this->response->setJSON(["status" => 200, "data" => ["rate" => $rate, "rate_date" => date('Y-m-d')]]);
    }

    // route : base_url/master_files/daily_usd_rate/show/{id}
    public function show($id)
    {
        try {
            $dailyUsdRateModel = new DailyUsdRateModel();
            $rate = $dailyUsdRateModel->find($id);

            if (!$rate) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Rate not found!']);
            }

            return $this->response->setJSON(['status' => 200, 'data' => $rate]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/daily_usd_rate/update/{id}
    public function update($id = null)
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'rate' => 'required|numeric',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
            }

            $dailyUsdRateModel = new DailyUsdRateModel();
            // tf-stage2: an edit of a row is a correction — logged with its reason
            if (\App\Libraries\TourFlow\UsdRate::installed(\Config\Database::connect())) {
                $row = $dailyUsdRateModel->find($id);
                if (!$row) {
                    return $this->response->setJSON(['status' => 404, 'message' => 'Rate not found!']);
                }
                $db = \Config\Database::connect();
                $name = \App\Libraries\TourFlow\Staff::displayName($db, (int) session()->get('user_id') ?: null, (string) session()->get('username'));
                $r = \App\Libraries\TourFlow\UsdRate::save($db, $row['rate_date'], $this->request->getVar('rate'), (string) $this->request->getVar('reason'), (string) session()->get('username'), $name, 'daily_usd_rate_page');
                return $this->response->setJSON(["status" => 200, "message" => $r['message']]);
            }

            if ($dailyUsdRateModel->update($id, ['rate' => $this->request->getVar('rate')])) {
                return $this->response->setJSON(["status" => 200, "message" => "Daily USD Rate updated successfully!"]);
            }
            throw new \Exception("Daily USD Rate update failed!");
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/daily_usd_rate/delete
    public function delete()
    {
        try {
            $id = $this->request->getGet('id');

            $dailyUsdRateModel = new DailyUsdRateModel();
            if ($dailyUsdRateModel->delete($id)) {
                return $this->response->setJSON(["status" => 200, "message" => "Daily USD Rate deleted successfully!"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    // Upsert by rate_date (unique) — overwrite the rate if the date already has one.
    private function upsertRate($rateDate, $rate)
    {
        $rateDate = date('Y-m-d', strtotime($rateDate));

        // tf-stage2: the same writer as the Home box — a second figure for a date is a correction with a reason, and every
        // entry and correction is logged (old and new figure, who, when, where entered)
        if (\App\Libraries\TourFlow\UsdRate::installed(\Config\Database::connect())) {
            $db = \Config\Database::connect();
            $name = \App\Libraries\TourFlow\Staff::displayName($db, (int) session()->get('user_id') ?: null, (string) session()->get('username'));
            \App\Libraries\TourFlow\UsdRate::save($db, $rateDate, $rate, (string) $this->request->getVar('reason'), (string) session()->get('username'), $name, 'daily_usd_rate_page');
            return;
        }

        $dailyUsdRateModel = new DailyUsdRateModel();
        $existing = $dailyUsdRateModel->where('rate_date', $rateDate)->first();

        $data = [
            'rate_date' => $rateDate,
            'rate' => $rate,
            'created_user' => session()->get('username'),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $dailyUsdRateModel->update($existing['id'], $data);
        } else {
            $dailyUsdRateModel->insert($data);
        }
    }
}
