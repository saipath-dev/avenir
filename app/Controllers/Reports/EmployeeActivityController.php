<?php

namespace App\Controllers\Reports;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Libraries\MenuLoader;

class EmployeeActivityController extends BaseController
{
    public function index()
    {
        $userModel = new UserModel();
        $data['employees'] = $userModel->findAll();

        // Load menu data for layout
        $menuLoader = new MenuLoader();
        $menuData = $menuLoader->loadMenuData();
        $data = array_merge($data, $menuData);

        return view('pages/reports/employee_activity_report', $data);
    }

    public function getActivityReport()
    {
        try {
            $date = $this->request->getVar('date');
            $employeeId = $this->request->getVar('employee_id');

            if (empty($date)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'msg' => 'Please select a date'
                ]);
            }

            $db = \Config\Database::connect();
            $builder = $db->table('a_log');

            // Get employee username if employee_id is provided
            $employeeFilter = null;
            if (!empty($employeeId) && $employeeId != 'all') {
                $userModel = new UserModel();
                $user = $userModel->find($employeeId);
                if ($user) {
                    $employeeFilter = $user['user'];
                }
            }

            // Build query to get all activities for the selected date
            $builder->select('Usr, Panel, Url, Date, Tym, ip');
            $builder->where('DATE(Date)', $date);

            if ($employeeFilter) {
                $builder->where('Usr', $employeeFilter);
            }

            $builder->orderBy('Usr, Date', 'ASC');
            $activities = $builder->get()->getResultArray();

            if (empty($activities)) {
                return $this->response->setJSON([
                    'status' => 404,
                    'msg' => 'No activity found for the selected date'
                ]);
            }

            // Group activities by employee
            $employeeActivities = [];
            foreach ($activities as $activity) {
                $usr = $activity['Usr'];
                if (!isset($employeeActivities[$usr])) {
                    $employeeActivities[$usr] = [
                        'employee' => $usr,
                        'login_time' => null,
                        'last_activity' => null,
                        'total_hours' => 0,
                        'total_minutes' => 0,
                        'activities' => []
                    ];
                }

                // Check if this is a login
                if (strtolower($activity['Panel']) == 'login') {
                    $employeeActivities[$usr]['login_time'] = $activity['Date'];
                }

                // Add to activities list
                $employeeActivities[$usr]['activities'][] = $activity;

                // Update last activity
                $employeeActivities[$usr]['last_activity'] = $activity['Date'];
            }

            // Calculate work hours for each employee
            foreach ($employeeActivities as $usr => &$empData) {
                if ($empData['login_time'] && $empData['last_activity']) {
                    $loginTime = new \DateTime($empData['login_time']);
                    $lastActivity = new \DateTime($empData['last_activity']);

                    $diff = $loginTime->diff($lastActivity);
                    $empData['total_hours'] = $diff->h + ($diff->days * 24);
                    $empData['total_minutes'] = $diff->i;
                    $empData['total_seconds'] = $diff->s;
                    $empData['formatted_duration'] = sprintf('%02d:%02d:%02d',
                        $empData['total_hours'],
                        $empData['total_minutes'],
                        $empData['total_seconds']
                    );
                    $empData['decimal_hours'] = round($empData['total_hours'] + ($empData['total_minutes'] / 60), 2);
                }
            }

            return $this->response->setJSON([
                'status' => 200,
                'data' => array_values($employeeActivities)
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    public function getActivityDetails()
    {
        try {
            $date = $this->request->getVar('date');
            $employee = $this->request->getVar('employee');

            if (empty($date) || empty($employee)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'msg' => 'Missing parameters'
                ]);
            }

            $db = \Config\Database::connect();
            $builder = $db->table('a_log');

            $builder->select('Usr, Panel, Url, Date, Tym, ip');
            $builder->where('DATE(Date)', $date);
            $builder->where('Usr', $employee);
            $builder->orderBy('Date', 'ASC');

            $activities = $builder->get()->getResultArray();

            return $this->response->setJSON([
                'status' => 200,
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }
}
