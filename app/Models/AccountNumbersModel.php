<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountNumbersModel extends Model
{
    protected $table = 'account_numbers';
    protected $allowedFields = ['TR_Code', 'HR_Code', 'Transaction_ID', 'PR_Code'];
    protected $returnType = 'array';

    public function incrementTRCode()
    {
        $builder = $this->db->table('account_numbers');
        $builder->select('TR_Code');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextTRCode = $result['TR_Code'] + 1;

        $data = [
            'TR_Code' => $nextTRCode
        ];

        $builder->update($data);

        return $nextTRCode;
    }

    public function incrementHRCode()
    {
        $builder = $this->db->table('account_numbers');
        $builder->select('HR_Code');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextHRCode = $result['HR_Code'] + 1;

        $data = [
            'HR_Code' => $nextHRCode
        ];

        $builder->update($data);

        return $nextHRCode;
    }

    public function incrementTransactionID()
    {
        $builder = $this->db->table('account_numbers');
        $builder->select('Transaction_ID');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextTransactionID = $result['Transaction_ID'] + 1;

        $data = [
            'Transaction_ID' => $nextTransactionID
        ];

        $builder->update($data);

        return $nextTransactionID;
    }

    public function incrementPRCode()
    {
        $builder = $this->db->table('account_numbers');
        $builder->select('PR_Code');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextPRCode = $result['PR_Code'] + 1;

        $data = [
            'PR_Code' => $nextPRCode
        ];

        $builder->update($data);

        return $nextPRCode;
    }
}
