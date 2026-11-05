<?php

namespace App\Controllers;

use App\Libraries\MenuLoader;
use App\Models\StaffMemberModel;
use App\Models\UserModel;
use Config\Services;

class ProfileController extends BaseController
{

    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $employeeModel = new StaffMemberModel();
        $data['employee'] = $employeeModel->where('empno', session()->get('emp_id'))->first();

        // load employee data
        $data['employee_name'] = $data['employee']['fullname'];
        $data['employee_phone'] = $data['employee']['Tp'];
        $data['emp_code'] = session()->get('emp_id');
        
        return view('pages/my_profile.php', $data);
    }

    public function change_password(){
        // get user_id from session
        $user_id = session()->get('user_id');

        // validate current_password, new_password and confirm_password
        $validation = Services::validation();
        $validation->setRules([
            'current_password' => 'required|strip_tags',
            'new_password' => 'required|strip_tags|min_length[8]|max_length[72]',
            'confirm_password' => 'required|strip_tags',
        ]);

        // validate request
        if ($validation->withRequest($this->request)->run()) {
            // get current_password, new_password and confirm_password
            $current_password = $this->request->getPost('current_password');
            $new_password = $this->request->getPost('new_password');
            $confirm_password = $this->request->getPost('confirm_password');

            // get user data from userModel
            $user_model = new UserModel();
            $user = $user_model->where('id', $user_id)->first();

            // check if current_password is correct
            if(password_verify($current_password, $user['pass'])){
                // check if new_password and confirm_password are same
                if($new_password == $confirm_password){
                    // update password
                    $user_model->update($user_id, ['pass' => password_hash($new_password, PASSWORD_DEFAULT)]);

                    // log the user out of all devices (including this one)
                    helper('usersession');
                    kill_user_sessions($user_id, false);
                    session()->destroy();

                    // return success message; frontend must honor redirect:true
                    return json_encode(array('status' => 201, 'message' => 'Password changed. Please login again.', 'redirect' => true));
                }else{
                    // return error message
                    return json_encode(array('status' => 400, 'message' => 'New password and confirm password are not same!'));
                }
            }else{
                // return error message
                return json_encode(array('status' => 400, 'message' => 'Current password is incorrect!'));
            }

        }else{
            // return error message
            return json_encode(array('status' => 400, 'message' => 'Please fill all the required fields!'));
        }

    }

    public function update_contact_info(){
        // get user_id from session
        $user_id = session()->get('user_id');

        // validate email, phone and address
        $validation = Services::validation();
        $validation->setRules([
            'employee_name' => 'required|strip_tags',
            'employee_phone' => 'required|strip_tags',
        ]);

        // validate request
        if ($validation->withRequest($this->request)->run()) {
            // get email, phone and address
            $name = $this->request->getVar('employee_name');
            $phone = $this->request->getVar('employee_phone');

            // update contact info
            $user_model = new StaffMemberModel();
            $user_model->update($user_id, ['fullname' => $name, 'Tp' => $phone]);

            // return success message
            return json_encode(array('status' => 201, 'message' => 'Contact info updated successfully!'));

        }else{
            // return error message
            return json_encode(array('status' => 400, 'message' => 'Please fill all the required fields!'));
        }
    }

    public function upload_profile_picture()
    {
        $acc_no = session()->get('acc_no');

        $validation = Services::validation();
        $validation->setRules([
            'profile_picture' => 'uploaded[profile_picture]|max_size[profile_picture,5120]|is_image[profile_picture]|mime_in[profile_picture,image/jpg,image/jpeg,image/png]',
        ]);

        if ($validation->withRequest($this->request)->run()) {
            try {
                $profile_picture = $this->request->getFile('profile_picture');
                $timestamp = date('YmdHis');
                $newFileName = $timestamp . '.' . $profile_picture->getExtension();
                $profile_picture->move('assets/images/users', $newFileName);

                $StaffMemberModel = new StaffMemberModel();
                $StaffMemberModel->where('Acc_No', $acc_no)->set(['prof_pic' => 'assets/images/users/' . $newFileName])->update();

                return json_encode(array('status' => 201, 'message' => 'Profile picture updated successfully!'));
            } catch (\Exception $e) {
                return json_encode(array('status' => 500, 'message' => 'An error occurred while uploading the profile picture: ' . $e->getMessage()));
            }
        } else {
            return json_encode(array('status' => 400, 'message' => 'Please upload a valid image!'));
        }
    }
}
