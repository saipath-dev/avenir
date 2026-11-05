<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\JobRoleModel;
use App\Models\JobRoleAssignmentModel;
use App\Models\UserModel;

class JobRoleController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // ========================================
    // JOB ROLES CRUD METHODS
    // ========================================

    // route : base_url/master_files/job_role
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Load the Job Role data (only non-deleted)
        $jobRoleModel = new JobRoleModel();
        $data['job_roles'] = $jobRoleModel->where('del', 0)->findAll();

        return view('pages/master_files/job_role', $data);
    }

    // route : base_url/master_files/job_role/create
    public function create()
    {
        try {
            $job_role = $this->request->getPost('job_role');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'job_role' => 'required|strip_tags|min_length[2]|max_length[255]'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Create a new JobRoleModel instance
            $jobRoleModel = new JobRoleModel();

            // Check if the Job Role already exists (only active ones)
            if ($jobRoleModel->where(['job_role' => $job_role, 'del' => 0])->first()) {
                // Return an error response
                return $this->response->setJSON(['status' => 400, 'message' => 'Job Role already exists']);
            }

            // Set the data for the new job role
            $data = [
                'job_role' => $job_role,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => session()->get('username')
            ];

            // Insert
            if ($jobRoleModel->insert($data)) {
                // Return a success response
                return $this->response->setJSON(['status' => 201, 'message' => 'Job Role created successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/job_role/show/{id}
    public function show($id)
    {
        try {
            // Create a new JobRoleModel instance
            $jobRoleModel = new JobRoleModel();

            // Get the job role data
            $jobRole = $jobRoleModel->find($id);

            // Return the job role data
            return $this->response->setJSON(['status' => 200, 'data' => $jobRole]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/job_role/update
    public function update($id)
    {
        try {
            $job_role = $this->request->getPost('job_role');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'job_role' => 'required|strip_tags|min_length[2]|max_length[255]'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Set the data for the job role
            $data = [
                'job_role' => $job_role,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => session()->get('username')
            ];

            // Create a new JobRoleModel instance
            $jobRoleModel = new JobRoleModel();

            // Check if the Job Role already exists (excluding current record)
            if ($jobRoleModel->where(['job_role' => $job_role, 'del' => 0])->where('id !=', $id)->first()) {
                // Return an error response
                return $this->response->setJSON(['status' => 409, 'message' => 'Job Role already exists']);
            }

            if ($jobRoleModel->update($id, $data)) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Job Role updated successfully']);
            }

        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/job_role/delete
    public function delete()
    {
        try {
            $id = $this->request->getGet('id');

            // Check if job role is used in assignments
            $assignmentModel = new JobRoleAssignmentModel();
            if ($assignmentModel->where('job_role_id', $id)->where('del', 0)->countAllResults() > 0) {
                // Return an error response
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot delete Job Role with active assignments']);
            }

            // Create a new JobRoleModel instance
            $jobRoleModel = new JobRoleModel();

            // Soft delete
            if ($jobRoleModel->update($id, ['del' => 1])) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Job Role deleted successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ========================================
    // JOB ROLE ASSIGNMENTS METHODS
    // ========================================

    // route : base_url/master_files/job_role/assignments
    public function assignments()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Load assignments
        $assignmentModel = new JobRoleAssignmentModel();
        // Removed JOIN on job_roles because job_role_id is now JSON
        $assignments = $assignmentModel
            ->select('job_role_assignments.*, user_acc_tbl.user, employee.fullname AS user_name')
            ->join('user_acc_tbl', 'user_acc_tbl.id = job_role_assignments.user_id')
            ->join('employee', 'employee.emp_num = user_acc_tbl.emp_id')
            ->where('job_role_assignments.del', 0)
            ->orderBy('job_role_assignments.id', 'DESC')
            ->findAll();

        // Pre-fetch all job roles to avoid N+1 query
        $jobRoleModel = new JobRoleModel();
        $allRoles = $jobRoleModel->findAll();
        $roleMap = [];
        foreach ($allRoles as $r) {
            $roleMap[$r['id']] = $r['job_role'];
        }

        // Process assignments to decode JSON and get names, and set status
        foreach ($assignments as &$assignment) {
            // Decode job_role_id (JSON string) -> array
            // Handle legacy data where it might be a simple int
            $roleIds = json_decode($assignment['job_role_id'], true);
            if (!is_array($roleIds)) {
                // Fallback for legacy int data or if json_decode fails
                $roleIds = [$assignment['job_role_id']];
            }

            // Map IDs to Names
            $roleNames = [];
            foreach ($roleIds as $rId) {
                if (isset($roleMap[$rId])) {
                    $roleNames[] = $roleMap[$rId];
                } else {
                    $roleNames[] = "Unknown ($rId)";
                }
            }
            $assignment['job_role_name'] = implode(', ', $roleNames);


            // Status Logic
            $today = date('Y-m-d');
            if ($today < $assignment['start_date']) {
                $assignment['status'] = 'Upcoming';
                $assignment['status_class'] = 'info';
            } elseif ($today > $assignment['end_date']) {
                $assignment['status'] = 'Expired';
                $assignment['status_class'] = 'secondary';
            } else {
                $assignment['status'] = 'Active';
                $assignment['status_class'] = 'success';
            }
        }

        $data['assignments'] = $assignments;

        return view('pages/master_files/job_role_assignment', $data);
    }

    // route : base_url/master_files/job_role/get_user_employees
    public function getUserEmployees()
    {
        try {
            $userModel = new UserModel();
            $users = $userModel
                ->select('user_acc_tbl.id, user_acc_tbl.emp_id, employee.fullname AS employee_name')
                ->join('employee', 'employee.emp_num = user_acc_tbl.emp_id')
                ->findAll();

            return $this->response->setJSON($users);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/job_role/assignments/create
    public function createAssignment()
    {
        try {
            $user_id = $this->request->getPost('user_id');
            $job_role_ids = $this->request->getPost('job_role_id'); // Array
            $start_date = $this->request->getPost('start_date');
            $end_date = $this->request->getPost('end_date');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'user_id' => 'required|numeric',
                'job_role_id' => 'required', // Will be array
                'start_date' => 'required|valid_date',
                'end_date' => 'required|valid_date',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Ensure job_role_id is an array
            if (!is_array($job_role_ids)) {
                $job_role_ids = [$job_role_ids];
            }

            // Check if at least one job role is selected
            if (empty($job_role_ids)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Please select at least one job role'
                ]);
            }

            // Custom date validation
            if (strtotime($end_date) < strtotime($start_date)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'End date must be after start date'
                ]);
            }

            // Initialize models
            $assignmentModel = new JobRoleAssignmentModel();
            $jobRoleModel = new JobRoleModel();

            // Filter out empty/invalid job role IDs
            $valid_job_role_ids = array_filter($job_role_ids, function($id) {
                return is_numeric($id) && !empty($id);
            });

            if (empty($valid_job_role_ids)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'No valid job roles selected'
                ]);
            }

            // Validate all job role IDs exist
            foreach ($valid_job_role_ids as $job_role_id) {
                $jobRole = $jobRoleModel->find($job_role_id);
                if (!$jobRole) {
                    return $this->response->setJSON([
                        'status' => 400,
                        'message' => "Invalid job role ID: $job_role_id"
                    ]);
                }
            }

            // Check for overlapping assignments
            // Since we're storing all roles in one record, check if user has ANY assignment overlapping with these dates
            $overlap = $assignmentModel->where('user_id', $user_id)
                                       ->where('del', 0)
                                       ->groupStart()
                                           ->where("'$start_date' <= end_date", null, false)
                                           ->where("'$end_date' >= start_date", null, false)
                                       ->groupEnd()
                                       ->first();

            if ($overlap) {
                $existing_start = date('d M Y', strtotime($overlap['start_date']));
                $existing_end = date('d M Y', strtotime($overlap['end_date']));
                $new_start = date('d M Y', strtotime($start_date));
                $new_end = date('d M Y', strtotime($end_date));

                return $this->response->setJSON([
                    'status' => 409,
                    'message' => "Date range conflict. User already has job role(s) assigned from " .
                                "$existing_start to $existing_end. " .
                                "Your requested dates ($new_start to $new_end) overlap with this assignment."
                ]);
            }

            // Prepare data for insertion - store all roles as JSON in single record
            $data = [
                'user_id' => $user_id,
                'job_role_id' => json_encode(array_values($valid_job_role_ids)), // Store as JSON array
                'start_date' => $start_date,
                'end_date' => $end_date,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assigned_by' => session()->get('username')
            ];

            // Insert single assignment record
            if ($assignmentModel->insert($data)) {
                $count = count($valid_job_role_ids);
                $message = $count === 1
                    ? "Job role assigned successfully"
                    : "$count job roles assigned successfully";

                return $this->response->setJSON([
                    'status' => 201,
                    'message' => $message
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Failed to save assignment'
                ]);
            }

        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/job_role/assignments/show/{id}
    public function showAssignment($id)
    {
        try {
            // Create a new JobRoleAssignmentModel instance
            $assignmentModel = new JobRoleAssignmentModel();

            // Get the assignment data
            $assignment = $assignmentModel->find($id);

            if ($assignment) {
                // Decode JSON job_role_id back to array for frontend
                $roleIds = json_decode($assignment['job_role_id'], true);
                if (!is_array($roleIds)) {
                    // Fallback if it's a single ID (old data)
                    $roleIds = [$assignment['job_role_id']];
                }
                $assignment['job_role_id'] = $roleIds;
            }

            // Return the assignment data
            return $this->response->setJSON(['status' => 200, 'data' => $assignment]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/job_role/assignments/update/{id}
    public function updateAssignment($id)
    {
        try {
            $user_id = $this->request->getPost('user_id');
            $job_role_ids = $this->request->getPost('job_role_id'); // Now accepts array
            $start_date = $this->request->getPost('start_date');
            $end_date = $this->request->getPost('end_date');

            // Validate the input data
            $validation = service('validation');
            $validation->setRules([
                'user_id' => 'required|numeric',
                'job_role_id' => 'required',
                'start_date' => 'required|valid_date',
                'end_date' => 'required|valid_date',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                // Validation failed, return the validation errors
                return $this->response->setStatusCode(400)->setJSON($validation->getErrors());
            }

            // Ensure job_role_id is an array
            if (!is_array($job_role_ids)) {
                $job_role_ids = [$job_role_ids];
            }

            // Custom date validation
            if (strtotime($end_date) < strtotime($start_date)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'End date must be after start date'
                ]);
            }

            // Initialize models
            $assignmentModel = new JobRoleAssignmentModel();
            $jobRoleModel = new JobRoleModel();

            // Filter out empty/invalid job role IDs
            $valid_job_role_ids = array_filter($job_role_ids, function($id) {
                return is_numeric($id) && !empty($id);
            });

            if (empty($valid_job_role_ids)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'No valid job roles selected'
                ]);
            }

            // Validate all job role IDs exist
            foreach ($valid_job_role_ids as $job_role_id) {
                $jobRole = $jobRoleModel->find($job_role_id);
                if (!$jobRole) {
                    return $this->response->setJSON([
                        'status' => 400,
                        'message' => "Invalid job role ID: $job_role_id"
                    ]);
                }
            }

            // Check for overlapping assignments (excluding current record)
            $overlap = $assignmentModel->where('user_id', $user_id)
                                       ->where('id !=', $id)
                                       ->where('del', 0)
                                       ->groupStart()
                                           ->where("'$start_date' <= end_date", null, false)
                                           ->where("'$end_date' >= start_date", null, false)
                                       ->groupEnd()
                                       ->first();

            if ($overlap) {
                $existing_start = date('d M Y', strtotime($overlap['start_date']));
                $existing_end = date('d M Y', strtotime($overlap['end_date']));
                $new_start = date('d M Y', strtotime($start_date));
                $new_end = date('d M Y', strtotime($end_date));

                $message = "Date range conflict. User already has job role(s) assigned from " .
                          "$existing_start to $existing_end. " .
                          "Your requested dates ($new_start to $new_end) overlap with this assignment.";

                return $this->response->setJSON([
                    'status' => 409,
                    'message' => $message
                ]);
            }

            // Set the data for the assignment update - store as JSON
            $data = [
                'user_id' => $user_id,
                'job_role_id' => json_encode(array_values($valid_job_role_ids)),
                'start_date' => $start_date,
                'end_date' => $end_date,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => session()->get('username')
            ];

            if ($assignmentModel->update($id, $data)) {
                return $this->response->setJSON(['status' => 200, 'message' => 'Job Role assignment updated successfully']);
            } else {
                return $this->response->setJSON(['status' => 500, 'message' => 'Failed to update assignment']);
            }

        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/job_role/assignments/delete
    public function deleteAssignment()
    {
        try {
            $id = $this->request->getGet('id');

            // Create a new JobRoleAssignmentModel instance
            $assignmentModel = new JobRoleAssignmentModel();

            // Soft delete
            if ($assignmentModel->update($id, ['del' => 1])) {
                // Return a success response
                return $this->response->setJSON(['status' => 200, 'message' => 'Job Role assignment deleted successfully']);
            }
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ========================================
    // HELPER METHODS FOR MULTI-ASSIGNMENT
    // ========================================

    /**
     * Check if ANY of the proposed job roles overlap with ANY existing assignment roles 
     * for this user in the given date range.
     *
     * @param int $user_id
     * @param array $new_role_ids (Array of ints)
     * @param string $start_date
     * @param string $end_date
     * @param int|null $exclude_id Assignment ID to exclude (for updates)
     * @return string|null Returns error message if overlap found, null otherwise
     */
    private function checkOverlappingAssignment($user_id, $new_role_ids, $start_date, $end_date, $exclude_id = null)
    {
        $assignmentModel = new JobRoleAssignmentModel();
        $jobRoleModel = new JobRoleModel();

        // 1. Find all "Temporal" Overlaps first (Database Query)
        //    (Just finding records that overlap in Time)
        $builder = $assignmentModel->where('user_id', $user_id)
                                   ->where('del', 0);

        if ($exclude_id !== null) {
            $builder->where('id !=', $exclude_id);
        }

        // Overlap condition: (new_start <= existing_end) AND (new_end >= existing_start)
        $builder->groupStart()
                    ->where("'$start_date' <= end_date", null, false)
                    ->where("'$end_date' >= start_date", null, false)
                ->groupEnd();

        $temporalOverlaps = $builder->findAll();

        if (empty($temporalOverlaps)) {
            return null; // As no time overlaps, definitely no conflict
        }

        // 2. Check "Role" Overlaps in PHP (Logic)
        //    Iterate through the time-overlapping records and see if they contain any of the same roles
        foreach ($temporalOverlaps as $record) {
            // Parse existing roles
            $existing_role_ids = json_decode($record['job_role_id'], true);
            if (!is_array($existing_role_ids)) {
                $existing_role_ids = [$record['job_role_id']];
            }

            // Intersect: Do any $new_role_ids exist in $existing_role_ids?
            $commonRoles = array_intersect($new_role_ids, $existing_role_ids);

            if (!empty($commonRoles)) {
                // CONFLICT FOUND!
                
                // Get one conflicting role name for the message
                $conflictId = reset($commonRoles);
                $roleData = $jobRoleModel->find($conflictId);
                $roleName = $roleData ? $roleData['job_role'] : "Role #$conflictId";

                $existing_start = date('d M Y', strtotime($record['start_date']));
                $existing_end = date('d M Y', strtotime($record['end_date']));

                return "<strong>Conflict Detected:</strong><br>" .
                       "Role '<strong>$roleName</strong>' is already assigned to this user from $existing_start to $existing_end.<br>" .
                       "You cannot assign it again in an overlapping period.";
            }
        }

        return null; // Overlapping time, but different roles -> Safe
    }
}
