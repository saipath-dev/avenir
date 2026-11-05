<?php

namespace App\Controllers;

use App\Controllers\Chat\ChatController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\ConsigneeModel;
use App\Models\GroupProfileModel;
use App\Models\TourQuotationsModel;
use App\Models\InquiryModel;
use App\Models\StaffMemberModel;
use App\Models\SupplierModel;

class DashboardController extends BaseController
{

    // public function index()
    // {
    //     $data = array();

    //     $group_id = session()->get('group_id');
    //     if ($group_id == 14) {
    //         return redirect()->to(base_url('chat'));
    //     }

    //     $menuLoader = new MenuLoader();
    //     $data = $menuLoader->loadMenuData();

    //     if ($group_id == 1) {
    //         $TourQuotationsModel = new TourQuotationsModel();
    //         $data['quotation_count'] = $TourQuotationsModel->distinct()->select('quotation_id')->where('confirm', 1)->where('finalize', 1)->countAllResults();
    //         $data['quotation_pending_count'] = $TourQuotationsModel->distinct()->select('quotation_id')->where('confirm', 0)->where('finalize',
    //             1
    //         )->countAllResults();

    //         $InquiryModel = new InquiryModel();
    //         $data['inq_count'] = $InquiryModel->select('id')->where('reply_flag', 1)->countAllResults();
    //         $data['inq_pending_count'] = $InquiryModel->select('id')->where('reply_flag', 0)->countAllResults();

    //         // Get today's tours
    //         $data['today_tours'] = $TourQuotationsModel->where('tour_date', date('Y-m-d'))->where('confirm', 1)->where('finalize', 1)->findAll();

    //         // Get the selected year or default to the current year
    //         $selected_year = $this->request->getVar('year') ?? date('Y');

    //         $currentMonth = date('m');
    //         $ConsigneeModel = new ConsigneeModel();

    //         // Fetch total tours for each month
    //         $monthly_tours = $ConsigneeModel
    //             ->select('MONTH(start_date) as month, COUNT(*) as total_tours')
    //             ->where('YEAR(start_date)', $selected_year)
    //             ->groupBy('MONTH(start_date)')
    //             ->findAll();

    //         // Prepare monthly data
    //         $monthlyData = [];
    //         for ($month = 1; $month <= 12; $month++) {
    //             $monthlyData[$month] = [
    //                 'total_tours' => 0,
    //                 'started_tours' => 0, // Initialize started_tours to 0
    //             ];
    //         }

    //         // Populate total tours for each month
    //         foreach ($monthly_tours as $tours) {
    //             $monthlyData[$tours['month']]['total_tours'] = $tours['total_tours'];
    //         }

    //         // Calculate started tours only for months up to the current month
    //         for ($month = 1; $month <= $currentMonth; $month++) {
    //             $monthlyData[$month]['started_tours'] = $ConsigneeModel
    //             ->where('MONTH(start_date)', $month)
    //             ->where('YEAR(start_date)', $selected_year)
    //                 ->where('start_date <=', date('Y-m-d')) // Count only until today
    //                 ->countAllResults();
    //         }

    //         // Assign data to the view
    //         $data['monthly_data'] = $monthlyData;
    //         $data['selected_year'] = $selected_year;

    //         // Log query (for debugging purposes)
    //         // log_message('critical', $ConsigneeModel->getLastQuery());
    //     }

    //     $emp_id = session()->get('emp_id');
    //     $staffMemberModel = new StaffMemberModel();
    //     $data['staff'] = $staffMemberModel->where('emp_num', $emp_id)->first();

    //     return view('pages/index', $data);
    // }

    public function index()
    {
        $data = [];

        $group_id = session()->get('group_id');
        if ($group_id == 14) {
            return redirect()->to(base_url('chat'));
        }

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        if ($group_id == 1) {
            $TourQuotationsModel = new TourQuotationsModel();
            $data['quotation_count'] = $TourQuotationsModel->distinct()->select('quotation_id')->where('confirm', 1)->where('finalize', 1)->countAllResults();
            $data['quotation_pending_count'] = $TourQuotationsModel->distinct()->select('quotation_id')->where('confirm', 0)->where('finalize', 1)->countAllResults();
            // log_message('critical', $TourQuotationsModel->getLastQuery());

            $InquiryModel = new InquiryModel();
            $data['inq_count'] = $InquiryModel->select('id')->where('reply_flag', 1)->countAllResults();
            $data['inq_pending_count'] = $InquiryModel->select('id')->where('reply_flag', 0)->countAllResults();
            // log_message('critical', $InquiryModel->getLastQuery());

            // Get today's tours
            $data['today_tours'] = $TourQuotationsModel->where('tour_date', date('Y-m-d'))->where('confirm', 1)->where('finalize', 1)->findAll();
            // log_message('critical', $TourQuotationsModel->getLastQuery());

            // Get the selected year or default to the current year
            $selected_year = $this->request->getVar('year') ?? date('Y');

            // log_message('critical', 'Selected year: ' . $selected_year);

            $currentMonth = date('m');
            $ConsigneeModel = new ConsigneeModel();

            // Fetch total tours for each month
            $monthly_tours = $ConsigneeModel
                ->select('MONTH(start_date) as month, COUNT(*) as total_tours')
                ->where('YEAR(start_date)', $selected_year)
                ->groupBy('MONTH(start_date)')
                ->orderBy('MONTH(start_date)', 'ASC')
                ->findAll();

                // log_message('critical', $ConsigneeModel->getLastQuery());
            // Prepare monthly data
            $monthlyData = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthlyData[$month] = [
                    'total_tours' => 0,
                    'started_tours' => 0, // Initialize started_tours to 0
                ];
            }

            // Populate total tours for each month
            foreach ($monthly_tours as $tours) {
                $monthlyData[$tours['month']]['total_tours'] = $tours['total_tours'];
            }

            // Calculate started tours only for months up to the current month
            for ($month = 1; $month <= 12; $month++) {
                // Select tours where ALL related tour_quotations have confirm = 1
                $monthlyData[$month]['started_tours'] = $ConsigneeModel
                ->select('COUNT(*)')
                ->where('MONTH(start_date)', $month)
                ->where('YEAR(start_date)', $selected_year)
                ->where('EXISTS (SELECT 1 FROM tour_quotations tq WHERE tq.quotation_id = consignee.tour_id AND tq.confirm = 1)', null, false)  // Using raw SQL for the EXISTS clause
                ->countAllResults();

                // log_message('critical', $ConsigneeModel->getLastQuery());
            }

            // Log query for debugging (to check the executed SQL)
            // log_message('debug', $ConsigneeModel->getLastQuery());

            // Assign data to the view
            $data['monthly_data'] = $monthlyData;
            $data['selected_year'] = $selected_year;

            // Log query (for debugging purposes)
            // log_message('critical', $data['selected_year']);
        }

        $emp_id = session()->get('emp_id');
        $staffMemberModel = new StaffMemberModel();
        $data['staff'] = $staffMemberModel->where('emp_num', $emp_id)->first();

        // get group nanme
        $groupProfileModel = new GroupProfileModel();
        $data['user_group_name'] = $groupProfileModel->select('type')->where('id', $group_id)->get()->getRow()->type;

        // Get today's employee activity
        $data['employee_activity'] = $this->getTodayEmployeeActivity();

        return view('pages/index', $data);
    }

    public function get_monthly_data()
    {
        $selected_year = $this->request->getVar('year') ?? date('Y');
        $currentMonth = date('m');
        $ConsigneeModel = new ConsigneeModel();

        // Fetch total tours for each month
        $monthly_tours = $ConsigneeModel
        ->select('MONTH(start_date) as month, COUNT(*) as total_tours')
        ->where('YEAR(start_date)', $selected_year)
        ->groupBy('MONTH(start_date)')
        ->findAll();

        // Prepare monthly data
        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[$month] = [
                'total_tours' => 0,
                'started_tours' => 0, // Initialize started_tours to 0
            ];
        }

        // Populate total tours for each month
        foreach ($monthly_tours as $tours) {
            $monthlyData[$tours['month']]['total_tours'] = $tours['total_tours'];
        }

        // Calculate started tours only for months up to the current month
        for ($month = 1; $month <= 12; $month++) {
            // Select tours where ALL related tour_quotations have confirm = 1
            $monthlyData[$month]['started_tours'] = $ConsigneeModel
                ->select('COUNT(*)')
                ->where('MONTH(start_date)', $month)
                ->where('YEAR(start_date)', $selected_year)
                ->where('EXISTS (SELECT 1 FROM tour_quotations tq WHERE tq.quotation_id = consignee.tour_id AND tq.confirm = 1)', null, false)  // Using raw SQL for the EXISTS clause
                ->countAllResults();
        }

        // Month names array
        $monthNames = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
        ];

        $output = '';

        // Generate table rows for the first quarter (Jan to Mar)
        $output .= '<tr style="background: linear-gradient(90deg, #b9f3fa, #88d4f0);">';
        for ($month = 1; $month <= 3; $month++) {
            $output .= '<td class="border" style="padding: 10px; background-color: #f0f8ff; transition: background-color 0.3s ease;" 
                onmouseover="this.style.backgroundColor=\'#e0f7fa\';" 
                onmouseout="this.style.backgroundColor=\'#f0f8ff\';">
                <strong style="color: #08808e;">' . $monthNames[$month] . '</strong> <br>' . $monthlyData[$month]['started_tours'] . '/' . $monthlyData[$month]['total_tours'] . '</td>';
        }
        $output .= '</tr>';

        // Generate table rows for the second quarter (Apr to Jun)
        $output .= '<tr style="background: linear-gradient(90deg, #88d4f0, #5ebae6);">';
        for ($month = 4; $month <= 6; $month++) {
            $output .= '<td class="border" style="padding: 10px; background-color: #f0f8ff; transition: background-color 0.3s ease;" 
                onmouseover="this.style.backgroundColor=\'#e0f7fa\';" 
                onmouseout="this.style.backgroundColor=\'#f0f8ff\';">
                <strong style="color: #08808e;">' . $monthNames[$month] . '</strong> <br>' . $monthlyData[$month]['started_tours'] . '/' . $monthlyData[$month]['total_tours'] . '</td>';
        }
        $output .= '</tr>';

        // Generate table rows for the third quarter (Jul to Sep)
        $output .= '<tr style="background: linear-gradient(90deg, #5ebae6, #349fc2);">';
        for ($month = 7; $month <= 9; $month++) {
            $output .= '<td class="border" style="padding: 10px; background-color: #f0f8ff; transition: background-color 0.3s ease;" 
                onmouseover="this.style.backgroundColor=\'#e0f7fa\';" 
                onmouseout="this.style.backgroundColor=\'#f0f8ff\';">
                <strong style="color: #08808e;">' . $monthNames[$month] . '</strong> <br>' . $monthlyData[$month]['started_tours'] . '/' . $monthlyData[$month]['total_tours'] . '</td>';
        }
        $output .= '</tr>';

        // Generate table rows for the fourth quarter (Oct to Dec)
        $output .= '<tr style="background: linear-gradient(90deg, #349fc2, #08808e);">';
        for ($month = 10; $month <= 12; $month++) {
            $output .= '<td class="border" style="padding: 10px; background-color: #f0f8ff; transition: background-color 0.3s ease;" 
                onmouseover="this.style.backgroundColor=\'#e0f7fa\';" 
                onmouseout="this.style.backgroundColor=\'#f0f8ff\';">
                <strong style="color: #08808e;">' . $monthNames[$month] . '</strong> <br>' . $monthlyData[$month]['started_tours'] . '/' . $monthlyData[$month]['total_tours'] . '</td>';
        }
        $output .= '</tr>';

        return $this->response->setJSON($output);

    }

    public function get_bank_balances()
    {
        $accountPostingModel = new AccountPostingModel();
        $accountRunningModel = new AccountRunningModel();
        $data['banks'] = $accountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        // foreach bank, get his total dr and cr and then balance
        foreach ($data['banks'] as $key => $bank) {
            $records = $accountRunningModel->where('L_Code', $bank['L_Code'])->orderBy('Date', 'ASC')->findAll();
            $total_dr = 0;
            $total_cr = 0;
            foreach ($records as $record) {
                $total_dr += $record['Dr'];
                $total_cr += $record['Cr'];
            }
            $balance = $total_dr - $total_cr;
            $data['banks'][$key]['total_dr'] = $total_dr;
            $data['banks'][$key]['total_cr'] = $total_cr;
            $data['banks'][$key]['balance'] = $balance;
        }

        return json_encode($data['banks']);
    }

    //class ConsigneeModel extends Model
    //{
    //    protected $table = 'consignee';
    //    protected $primaryKey = 'ID';
    //    protected $allowedFields = ['tour_id', 'consignee', 'country', 'k_code', 'mobile', 'email', 'passenger_count', 'start_date', 'end_date', 'create_user', 'create_time', 'vehicle', 'vehicle_name', 'finalize', 'vehicle_rate', 'transport_agent', 'reference_no', 'transport_advance', 'manual_id', 'agent', 'agent_name', 'arrival_flight', 'departure_flight', 'bank', 'bank_name', 'arrival_flight_time', 'departure_flight_time', 'sgl', 'dbl', 'tpl', 'dbl_twin', 'hotel_only', 'ref_no', 'total_room_cost', 'total_transport_cost', 'total_mileage', 'tour_bata', 'bata_day_rate', 'bata_days', 'activity_fee_total', 'transport_agent_name', 'transport_only', 'is_template', 'transport_settled', 'identical', 'verify'];
    //    protected $returnType = 'array';

    public function get_agent_list()
    {
        $ConsigneeModel = new ConsigneeModel();
        $data['agents'] = $ConsigneeModel->select('agent, agent_name, COUNT(*) as agent_count')->groupBy('agent')->findAll();

        return json_encode($data['agents']);
    }

    public function get_transport_agent_list()
    {
        $ConsigneeModel = new ConsigneeModel();
        $supplierModel = new SupplierModel();
        $data['transport_agents'] = $supplierModel->select('supplier.Sup_Acc_No, supplier.Name, COUNT(consignee.transport_agent) as transport_count')
            ->join('consignee', 'supplier.Sup_Acc_No = consignee.transport_agent', 'left')
            ->where('supplier.sup_cat', 2)
            ->groupBy('supplier.ID')
            ->orderBy('transport_count', 'DESC')
            ->limit(7)
            ->findAll();
        // log_message('critical', $ConsigneeModel->getLastQuery());

        return json_encode($data['transport_agents']);
    }

    public function get_all_transport_agent_list()
    {
        $supplierModel = new SupplierModel(); // No need for ConsigneeModel here
        $data['all_transport_agents'] = $supplierModel->select('supplier.Sup_Acc_No, supplier.Name, COUNT(consignee.transport_agent) as transport_count')
            ->join('consignee', 'supplier.Sup_Acc_No = consignee.transport_agent', 'left')
            ->where('supplier.sup_cat', 2)
            ->groupBy('supplier.ID')
            ->orderBy('transport_count', 'DESC')
            ->findAll();

        // Return JSON response
        return $this->response->setJSON($data['all_transport_agents']);
    }

    public function get_all_NA_tours()
    {
        // $supplierModel = new SupplierModel(); // No need for ConsigneeModel here
        $ConsigneeModel = new ConsigneeModel();
        $data['all_NA_tours'] = $ConsigneeModel
            ->where('transport_agent', '6-9-11-10691')
            ->findAll();

        // Return JSON response
        return $this->response->setJSON($data['all_NA_tours']);
    }

    public function get_unconfirmed_driver_tours()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('consignee');

        // Get tours where driver is not allocated (transport_agent is NULL)
        // and tour is verified (verify = 1) and confirmed (exists in tour_quotations with confirm = 1)
        $query = $builder
            ->select('consignee.tour_id, consignee.consignee, consignee.start_date, consignee.end_date, consignee.passenger_count, consignee.agent_name')
            ->join('tour_quotations', 'tour_quotations.quotation_id = consignee.tour_id', 'inner')
            ->where('consignee.transport_agent IS NULL', null, false)
            ->where('consignee.verify', 1)
            ->where('tour_quotations.confirm', 1)
            ->groupBy('consignee.tour_id')
            ->orderBy('consignee.start_date', 'ASC')
            ->get();

        $tours = $query->getResultArray();

        // Count unique tours
        $count = count($tours);

        return $this->response->setJSON([
            'status' => 200,
            'count' => $count,
            'tours' => $tours
        ]);
    }


    public function get_liabilities($date1, $date2)
    {
        $date1 = date('Y-m-d', strtotime($date1));
        $date2 = date('Y-m-d', strtotime($date2));

        $SupplierModel = new SupplierModel();
        $data['suppliers']  = $SupplierModel->orderBy('Name', 'ASC')->findAll();

        $from_date = strtotime($date1);
        $to_date = strtotime($date2);
        $months = array();
        $records = array();

        while ($from_date <= $to_date) {
            $month = date('Y-m', $from_date);
            $from_date = strtotime('+1 month', $from_date);
            // get last day of the month
            $last_day = date('Y-m-t', strtotime($month));
            $months[] = [
                'first_day' => $month . '-01',
                'last_day' => $last_day,
                'name' => date('F', strtotime($month)),
                'month' => date('Y-m', strtotime($month)),
            ];
        }

        $accountRunningModel = new AccountRunningModel();
        // foreach supplier, get his total dr and cr and then balance
        $total_payable = 0;
        foreach ($data['suppliers'] as $key => $supplier) {

            $records = $accountRunningModel->where('L_Code', $supplier['Sup_Acc_No'])->where('Date <=', $date2)->where('Date >=', $date1)->orderBy('Date', 'ASC')->findAll();

            $total_dr = 0;
            $total_cr = 0;

            foreach ($records as $record) {
                $total_dr += $record['Dr'];
                $total_cr += $record['Cr'];
            }

            $balance = $total_cr - $total_dr;
            $data['suppliers'][$key]['total_dr'] = $total_dr;
            $data['suppliers'][$key]['total_cr'] = $total_cr;
            $data['suppliers'][$key]['balance'] = $balance;
            $total_payable += $balance;
        }

        // foreach month, get total dr
        foreach ($months as $keyx => $month) {

            $months[$keyx]['total_dr'] = 0;
            $months[$keyx]['total_cr'] = 0;

            $month['first_day'] = date('Y-m-d', strtotime($month['first_day']));
            $month['last_day'] = date('Y-m-d', strtotime($month['last_day']));

            foreach ($data['suppliers'] as $key => $supplier) {
                $records = $accountRunningModel->where('L_Code', $supplier['Sup_Acc_No'])->where('Dr >', 0)->where('Date <=', $month['last_day'])->where('Date >=', $month['first_day'])->orderBy('Date', 'ASC')->findAll();

                foreach ($records as $record) {
                    $months[$keyx]['total_dr'] += $record['Dr'];
                }
            }

            $months[$keyx]['total_dr'] = round($months[$keyx]['total_dr']);
        }

        // sort suppliers by balance DESC
        usort($data['suppliers'], function ($a, $b) {
            return $b['balance'] <=> $a['balance'];
        });

        $data['total_payable'] = round($total_payable);
        $data['months'] = $months;

        return json_encode($data);
    }

    private function getTodayEmployeeActivity()
    {
        $db = \Config\Database::connect();
        $employeeActivity = [];
        $today = date('Y-m-d');

        // Get all distinct users who logged in today, excluding super admin and drivers (group_id = 14)
        $loggedUsers = $db->table('a_log')
            ->distinct()
            ->select('a_log.Usr')
            ->join('user_acc_tbl', 'user_acc_tbl.user = a_log.Usr', 'left')
            ->where('DATE(a_log.Date)', $today)
            ->where('a_log.Usr !=', 'super admin')
            ->where('user_acc_tbl.group_id !=', 24)
            ->get()
            ->getResultArray();

        foreach ($loggedUsers as $userRow) {
            $username = $userRow['Usr'];

            // Get all activity for this user today
            $activities = $db->table('a_log')
                ->select('Usr, Panel, Url, Date, Tym, ip')
                ->where('DATE(Date)', $today)
                ->where('Usr', $username)
                ->orderBy('Date', 'ASC')
                ->get()
                ->getResultArray();

            if (!empty($activities)) {
                $loginTime = null;
                $lastActivity = null;

                foreach ($activities as $activity) {
                    if (strtolower($activity['Panel']) == 'login') {
                        $loginTime = $activity['Date'];
                    }
                    $lastActivity = $activity['Date'];
                }

                // Get user info from user_acc_tbl to determine gender
                $userInfo = $db->table('user_acc_tbl')
                    ->select('user_acc_tbl.emp_id, employee.gender, employee.fullname')
                    ->join('employee', 'employee.emp_num = user_acc_tbl.emp_id', 'left')
                    ->where('user_acc_tbl.user', $username)
                    ->get()
                    ->getRowArray();

                // Determine gender (default to male if not found)
                $gender = 'male';
                if ($userInfo && !empty($userInfo['gender'])) {
                    $gender = strtolower($userInfo['gender']);
                }

                // Calculate duration
                $duration = 'N/A';
                $durationMinutes = 0;
                if ($loginTime && $lastActivity) {
                    $start = new \DateTime($loginTime);
                    $end = new \DateTime($lastActivity);
                    $diff = $start->diff($end);

                    $hours = $diff->h + ($diff->days * 24);
                    $minutes = $diff->i;
                    $durationMinutes = ($hours * 60) + $minutes;

                    $duration = sprintf('%02d:%02d:%02d', $hours, $minutes, $diff->s);
                }

                $employeeActivity[] = [
                    'name' => $username,
                    'gender' => $gender,
                    'login_time' => $loginTime ? date('h:i A', strtotime($loginTime)) : 'N/A',
                    'last_activity' => $lastActivity ? date('h:i A', strtotime($lastActivity)) : 'N/A',
                    'duration' => $duration,
                    'duration_minutes' => $durationMinutes,
                    'activity_count' => count($activities)
                ];
            }
        }

        // do NOT close the shared DB connection - the DB session handler needs it at shutdown
        return $employeeActivity;
    }
}
