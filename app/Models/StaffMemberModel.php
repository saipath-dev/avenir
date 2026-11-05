<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffMemberModel extends Model
{
    protected $table = 'employee';
    protected $primaryKey = 'ID';
    protected $allowedFields = ['empno', 'emp_num', 'Acc_No', 'Name', 'Tp', 'Division', 'payout_date', 'installment', 'epf_no', 'bank_acc', 'extra', 'Salary', 'Link_Code', 'in_t', 'Out_t', 'meal', 'transport', 'desig', 'appoinment', 'cntrct', 'Living', 'Spec', 'Allow', 'Family', 'Incentive', 'Cloth', 'Com', 'BRA', 'OT', 'rate', 'epf_basic', 'Bbranch', 'Nic', 'epf_trans', 'othr_nm', 'bank', 'Sft_installment', 'annual', 'sick', 'casual', 'Tot_allowance', 'att_bonus', 'soft_install', 'address', 'prof_pic', 'Start_time', 'Service_ID', 'email', 'surname', 'fullname', 'tpOffice', 'tpHome', 'birthday', 'gender', 'emp_status', 'sync_status', 'updated_time', 'sync_time', 'location', 'is_active', 'lib_status'];
    protected $returnType = 'array';
}
