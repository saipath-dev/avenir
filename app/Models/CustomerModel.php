<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'ID';
    protected $allowedFields = ['Sup_Acc_No', 'Name', 'Address', 'NIC', 'TP', 'Mobile', 'flag', 'cr_lmt', 'cr_dyz', 'csh', 'chq', 'chq_fvr', 'rep_offce', 'rep_mobile', 'ac', 'sup_cat', 'ref_no', 'dealer_acc', 'email', 'country', 't_code_1', 't_code_2', 'zipcode', 'margin', 'long_name', 'prefix', 'title', 'contact_person', 'per_mobile', 'per_tp', 'per_t_code_1', 'per_t_code_2'];
    protected $returnType = 'array';

    /**
     * The customers a quotation offers as the agent: the ones marked as agents under
     * Master Files → Consignees (agent = 1), by name. Until the first customer has been
     * marked the whole ledger is offered, so no quotation screen ever turns up empty.
     * The mark itself is set only from the Consignees list (CustomerController::agent).
     */
    public function agentsForQuotation(): array
    {
        try {
            $marked = $this->where('agent', 1)->orderBy('Name', 'ASC')->findAll();
            if ($marked !== []) {
                return $marked;
            }
        } catch (\Throwable $e) {
            // a ledger without the agent column: everyone, as before
        }
        return $this->orderBy('Name', 'ASC')->findAll();
    }

    /** How many customers are marked as agents — 0 until the marking has begun. */
    public function agentsMarked(): int
    {
        try {
            return (int) $this->where('agent', 1)->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
