<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceNumberModel extends Model
{
    protected $table = 'invoice_no';
    protected $allowedFields = ['Stock', 'No', 'Recipt_No', 'Gatepass', 'Purchase', 'Invoice_2', 'Invoice_3', 'start_t', 'end_t', 'DealerC', 'Sales_return', 'V_Num', 'Pay_slip', 'Aut', 'Accs', 'dv_id', 'w_id', 'Grns', 'slock', 'G_id', 'sr', 'day', 'job_card', 'ins_ref', 'Job_invoice', 'Job_reg', 'Gatepass_s', 'repair_inv', 'Month', 'yy', 'srvc_no', 'pos_receipt_no', 'shift_id', 'external_cus', 'check_out_id', 'LS', 'STC', 'TMP', 'STS', 'INCT', 'Item_Token', 'Item_Req', 'Purchase_Token', 'Purchases', 'app_no', 'sms_bulk_id', 'notice_bulk_id', 'Dispose_Token', 'Disposes', 'Dispose', 'promoted_year', 'fx_Dispose_Token', 'fx_Disposes', 'inventory_receipt', 'Pro_purchase_no', 'supplier_return', 'sup_return_token', 'voucher', 'Tour_No', 'inquiry_no', 'doller_amt', 'debtor_receipt_no', 'entertainment_expenses_inv'];
    protected $returnType = 'array';

    public function GetNextPurchaseNumber()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('Purchase');
        $query = $builder->get();
        $result = $query->getRowArray();

        $data = [
            'Purchase' => $result['Purchase'] + 1
        ];
        $builder->update($data);

        return $data['Purchase'];
    }

    public function incrementNo()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('No');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextNo = $result['No'] + 1;

        $data = [
            'No' => $nextNo
        ];

        $builder->update($data);

        return $nextNo;
    }

       public function incrementEntertainmentExpensesInv()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('entertainment_expenses_inv');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextNo = $result['entertainment_expenses_inv'] + 1;

        $data = [
            'entertainment_expenses_inv' => $nextNo
        ];

        $builder->update($data);

        return $nextNo;
    }

    public function incremenDebtorInvocieNo()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('Invoice_2');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextNo = $result['Invoice_2'] + 1;

        $data = [
            'Invoice_2' => $nextNo
        ];

        $builder->update($data);

        return $nextNo;
    }

    public function incrementProforma()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('profotma_no');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextNo = $result['profotma_no'] + 1;

        $data = [
            'profotma_no' => $nextNo
        ];

        $builder->update($data);

        return $nextNo;
    }

    //Tour_No Increment
    public function incrementTourNo()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('Tour_No');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextTourNo = $result['Tour_No'] + 1;

        $data = [
            'Tour_No' => $nextTourNo
        ];

        $builder->update($data);

        return $nextTourNo;
    }

    //Inquiry_No Increment
    public function incrementInquiryNo()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('inquiry_no');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextInquiryNo = $result['inquiry_no'] + 1;

        $data = [
            'inquiry_no' => $nextInquiryNo
        ];

        $builder->update($data);

        return $nextInquiryNo;
    }

    //Manual_Tour_No Increment
    public function incrementManualTourId()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('tour_manual_id');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextmanuTourNo = $result['tour_manual_id'] + 1;

        $data = [
            'tour_manual_id' => $nextmanuTourNo
        ];

        $builder->update($data);

        return $nextmanuTourNo;
    }

    public function incrementAccId()
    {
        // tf-payguard-r1.3 (16 Oct 2026): the number is taken in ONE statement (UPDATE … SET acc_id = LAST_INSERT_ID(acc_id + 1)),
        // so two requests can never read the same value and both write value + 1 (test N-1 showed the former
        // read-then-write take handing one number to two concurrent requests). See takeAtomic().
        return $this->takeAtomic('acc_id');
    }

    public function incrementJInv()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('JE_inv');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextNo = $result['JE_inv'] + 1;

        $data = [
            'JE_inv' => $nextNo
        ];

        $builder->update($data);

        return $nextNo;
    }

    public function incrementVoucherToken()
    {
        // tf-payguard-r1.3 (16 Oct 2026): the number is taken in ONE statement (UPDATE … SET V_Num = LAST_INSERT_ID(V_Num + 1)),
        // so two requests can never read the same value and both write value + 1 (test N-1 showed the former
        // read-then-write take handing one number to two concurrent requests). See takeAtomic().
        return $this->takeAtomic('V_Num');
    }

    public function incrementVoucher()
    {
        // tf-payguard-r1.3 (16 Oct 2026): the number is taken in ONE statement (UPDATE … SET voucher = LAST_INSERT_ID(voucher + 1)),
        // so two requests can never read the same value and both write value + 1 (test N-1 showed the former
        // read-then-write take handing one number to two concurrent requests). See takeAtomic().
        return $this->takeAtomic('voucher');
    }

    public function incrementDebtorReceiptNo()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('debtor_receipt_no');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextNo = $result['debtor_receipt_no'] + 1;

        $data = [
            'debtor_receipt_no' => $nextNo
        ];

        $builder->update($data);

        return $nextNo;
    }

    public function incrementStockNo()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('Stock');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextNo = $result['Stock'] + 1;

        $data = [
            'Stock' => $nextNo
        ];

        $builder->update($data);

        return $nextNo;
    }

    public function incrementHotelCode()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('hotel_code');
        $query = $builder->get();
        $result = $query->getRowArray();

        $nextNo = $result['hotel_code'] + 1;

        $data = [
            'hotel_code' => $nextNo
        ];

        $builder->update($data);

        return $nextNo;
    }

    public function peekNextHotelCode()
    {
        $builder = $this->db->table('invoice_no');
        $builder->select('hotel_code');
        $query = $builder->get();
        $result = $query->getRowArray();

        return $result['hotel_code'] + 1;
    }

    /**
     * tf-payguard-r1.3: one-statement counter take. LAST_INSERT_ID(expr) is per connection and the UPDATE is serialised by
     * the storage engine (table lock on MyISAM, row lock on InnoDB), so concurrent takes always receive different numbers.
     * The result is checked (DBDebug off returns false on failure instead of throwing, D-09). Release build: the forced
     * wait of the isolated test copy is not in this file.
     */
    private function takeAtomic(string $column): int
    {
        $res = $this->db->query("UPDATE invoice_no SET `{$column}` = LAST_INSERT_ID(`{$column}` + 1)");
        $affected = (int) $this->db->affectedRows();
        if ($res === false || $affected !== 1) {
            $e = $this->db->error();
            throw new \RuntimeException('counter take failed for ' . $column . ': [' . ($e['code'] ?? '?') . '] ' . ($e['message'] ?? 'no message') . ' (affected ' . $affected . ')');
        }
        $row = $this->db->query('SELECT LAST_INSERT_ID() AS n')->getRowArray();
        $n = (int) ($row['n'] ?? 0);
        if ($n <= 0) {
            throw new \RuntimeException('counter take returned no number for ' . $column);
        }
        return $n;
    }
}
