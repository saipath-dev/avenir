<?php

namespace App\Controllers\NewDashboard;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\ConsigneeModel;
use App\Models\SupplierModel;
use App\Models\DriverAllocationModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\PaymentRequestModel;
use App\Models\VoucherPaymentModel;
use App\Models\LogSheetMileModel;
use App\Models\DriverAllocationAuditLogModel;
use App\Models\VoucherInvoiceModel;
use App\Models\InvoiceNumberModel;



class DriverLogsheetController extends BaseController
{

    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = $this->request->getGet('tour_id');

        $data['tile_dashboard'] = true;

        $SupplierModel = new SupplierModel();
        $data['transport_agent'] = $SupplierModel->where('sup_cat', 2)->findAll();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $data['agents'] = [];

        $tourConsigneeModel = new ConsigneeModel();

        $tour_condition = $tourConsigneeModel->select('hotel_only')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $driverallocationModel = new DriverAllocationModel();
        $data['drivers'] = $driverallocationModel->where('tour_id', $tour_id)->findAll();

        $PaymentRequestModel = new PaymentRequestModel();
        $data['advance_payment_requests'] = $PaymentRequestModel->where('tour_id', $tour_id)->where('hotel_acc', 'TRANSPORT')->findAll();

        $tour_main_details = $tourConsigneeModel->where('tour_id', $tour_id)->findAll();
        $data['tour_id'] = $tour_id;
        // $data['type'] = $type;
        $data['tour_main_details'] = $tour_main_details;

        return view('pages/dashboard2/transport/log_sheet', $data);
    }

    public function get_totday_log_sheet()
    {
        $tour = $this->request->getPost('tour_id');
        $agent = $this->request->getPost('agent');

        $logsheetmileModel = new LogSheetMileModel();
        $result = $logsheetmileModel->selectSum('no_of_dates')
            ->where('tour_id', $tour)
            ->where('transport_agent', $agent)
            ->first();

        return $this->response->setJSON([
            'status' => 200,
            'total_days' => $result['no_of_dates'] ?? 0
        ]);
    }

    public function get_logsheet_mileage($tour_id, $agent)
    {
        $logsheetmileModel = new LogSheetMileModel();
        $logsheetmile = $logsheetmileModel->where('tour_id', $tour_id)->where('transport_agent', $agent)->orderBy('id', 'desc')->first();
        return json_encode($logsheetmile);
    }

    public function get_t_agent_absolute_balance()
    {
        $tagent = $this->request->getGet('tagent');
        $accountRunningModel = new AccountRunningModel();
        $balance = $accountRunningModel->select('SUM(Dr) - SUM(Cr) AS balance')
            ->where('L_Code', $tagent)
            ->first()['balance'];

        return $this->response->setJSON(['status' => 200, 'data' => $balance]);
    }

    // Add Logsheet Mileage
    public function add_logsheet_mileage()
    {
        try {
            $tour = $this->request->getPost('tour_id');
            $agent = $this->request->getPost('agent');
            $estimatedmileage = $this->request->getPost('estimatedmileage');
            $v_rate = $this->request->getPost('v_rate');
            $mileage = $this->request->getPost('totalactualmileage');
            $dara_rate = $this->request->getPost('dara_rate');
            $noofdate = $this->request->getPost('noofdate');
            $new_vehi_rate = $this->request->getPost('v_rate_new');


            if ($tour == null || $mileage == null) {
                return json_encode(['status' => 400, 'message' => 'Please fill all the fields']);
            }

            if (!is_numeric($mileage)) {
                return json_encode(['status' => 400, 'message' => 'Mileage must be a number']);
            }

            $ConsigneeModel = new ConsigneeModel();
            $logsheetmileModel = new LogSheetMileModel();

            if ($new_vehi_rate != null && $new_vehi_rate != '') {
                $v_rate = $new_vehi_rate;
            }

            // This endpoint is the only insert path into log_sheet_mile for a given tour+agent, so
            // a prior row already existing means this save is overriding a previously recorded
            // mileage (whether that came from the driver's own TourFinalize or an earlier override
            // here) — track the value it replaces and who made the change.
            $previousRow = $logsheetmileModel
                ->where('tour_id', $tour)
                ->where('transport_agent', $agent)
                ->orderBy('id', 'DESC')
                ->first();
            $isOverride = $previousRow !== null;

            $data_logsheet = [
                'tour_id' => $tour,
                'transport_agent' => $agent,
                'log_sheet_mileage' => $mileage,
                'previous_mileage' => $isOverride ? $previousRow['log_sheet_mileage'] : null,
                'estimate_mileage' => $estimatedmileage,
                'vehicle_rate' => $v_rate,
                'per_day_rate' => $dara_rate,
                'no_of_dates' => $noofdate,
                'is_override' => $isOverride ? 1 : 0,
                'overridden_by' => $isOverride ? session()->get('username') : null,
                'login' => session()->get('username'),
                'date' => date('Y-m-d H:i:s')
            ];

            $insertId = $logsheetmileModel->insert($data_logsheet, true);
            if (!$insertId) {
                return json_encode(['status' => 400, 'message' => 'Addition failed']);
            }

            // A tour can have logsheet mileage entered for more than one driver (e.g. one driver
            // covers days 1-3, another covers day 4). consignee holds one total_mileage/
            // total_transport_cost per tour, so this must sum every driver's latest logsheet row
            // instead of overwriting with just this agent's figures — same fix as
            // TransportPortalController::getTourTransportTotals() / DriverPortalController::getTourTransportTotals().
            $tourTotals = $this->getTourTransportTotals($tour, $logsheetmileModel);

            $data = [
                'log_sheet_mileage' => $tourTotals['mileage'],
                'total_mileage' => $tourTotals['mileage'],
                'total_transport_cost' => $tourTotals['cost'],
                'bata_day_rate' => $dara_rate,
                'vehicle_rate' => $v_rate,
            ];

            $updateResult = $ConsigneeModel->where('tour_id', $tour)->set($data)->update();

            if ($updateResult) {
                return json_encode(['status' => 200, 'message' => 'Successfully added']);
            } else {
                return json_encode(['status' => 400, 'message' => 'Addition failed']);
            }
        } catch (\Exception $e) {
            return json_encode(['status' => 400, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    private function getTourTransportTotals(string $tour_id, LogSheetMileModel $logsheetmileModel): array
    {
        $rows = $logsheetmileModel
            ->select('id, transport_agent, log_sheet_mileage, vehicle_rate, per_day_rate, no_of_dates')
            ->where('tour_id', $tour_id)
            ->orderBy('id', 'DESC')
            ->findAll();

        $latestByAgent = [];
        foreach ($rows as $row) {
            $agent = (string) ($row['transport_agent'] ?? '');
            if ($agent === '' || isset($latestByAgent[$agent])) {
                continue;
            }
            $latestByAgent[$agent] = $row;
        }

        $mileageTotal = 0.0;
        $costTotal = 0.0;
        foreach ($latestByAgent as $row) {
            $mileage = (float) ($row['log_sheet_mileage'] ?? 0);
            $rate = (float) ($row['vehicle_rate'] ?? 0);
            $bata = (float) ($row['per_day_rate'] ?? 0);
            $days = (int) ($row['no_of_dates'] ?? 0);

            $mileageTotal += $mileage;
            $costTotal += ($mileage * $rate) + ($bata * $days);
        }

        return [
            'mileage' => round($mileageTotal, 2),
            'cost' => round($costTotal, 2),
        ];
    }
}
