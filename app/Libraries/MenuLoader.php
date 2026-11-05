<?php

namespace App\Libraries;

use App\Models\GroupProfileModel;
use App\Models\StaffMemberModel;
use App\Models\UserPrevQModel;

class MenuLoader
{
    public function loadMenuData()
    {
        $data = [];

        $emp_id = session()->get('emp_id');
        $user_id = session()->get('user_id');

        $userPrevQModel = new UserPrevQModel();

        $data['allowed_categories'] = $userPrevQModel
            ->select('cat_id, category, cat_oid')
            ->where('emp_id', $emp_id)
            ->where('user_id', $user_id)
            ->groupBy('category, cat_oid, cat_id')
            ->orderBy('cat_oid', 'ASC')
            ->findAll();

        $data['allowed_menu_list'] = $userPrevQModel
            ->select('cat_id, menu, menu_id, menu_oid, url, show_in_menu_bar')
            ->where('emp_id', $emp_id)
            ->where('user_id', $user_id)
            ->groupBy('menu_id, menu, menu_oid, cat_id, url, show_in_menu_bar')
            ->orderBy('menu_oid', 'ASC')
            ->orderBy('menu_id', 'ASC')   // tf-discount-r2: a stable order inside one order_id; nothing is moved
            ->findAll();

        $staffMemberModel = new StaffMemberModel();
        $employee = $staffMemberModel->select('Name, prof_pic')->where('emp_num', $emp_id)->get()->getRow();
        $data['sidebar_employee_name'] = $employee->Name;
        $data['profile_picture'] = $employee->prof_pic ? base_url($employee->prof_pic) : base_url('assets/images/users/no_profile.jpg');
        $data['sidebar_employee_code'] = $emp_id;

        $data['user_role'] = session()->get('group_id');

        $groupProfileModel = new GroupProfileModel();
        $data['sidebar_employee_name_profile_name'] = $groupProfileModel->select('type')->where('id', $data['user_role'])->get()->getRow()->type;

        return $data;
    }
}
