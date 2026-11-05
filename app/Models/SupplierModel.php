<?php

namespace App\Models;

use CodeIgniter\Model;

class SupplierModel extends Model
{
    protected $table = 'supplier';
    protected $primaryKey = 'ID';
    protected $allowedFields = ['Sup_Acc_No', 'Name', 'Address', 'NIC', 'TP', 'Mobile', 'flag', 'cr_lmt', 'cr_dyz', 'csh', 'chq', 'chq_fvr', 'bulk_payment', 'rep_offce', 'rep_mobile', 'ac', 'sup_cat', 'ref_no', 'email', 'sup_bank_acc', 'bank_id', 'bank_name', 'branch_id', 'branch_name', 'remark', 'create_user', 'dled', 'gled', 'dlimg', 'glimg', 'language', 'acc_holder', 'is_login_enabled', 'username', 'password', 'type', 'passenger_count', 'km_rate', 'v_num', 'bata', 'action', 'vimg', 'vlimg', 'vehi_license_expire_date', 'viimg', 'vehi_insurance_expire_date', 'hotel_code', 'is_avalible', 'unavailable_since'];
    protected $returnType = 'array';
}
