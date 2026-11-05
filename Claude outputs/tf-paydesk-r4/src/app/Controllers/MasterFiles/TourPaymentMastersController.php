<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourPayments\BankAccounts;
use App\Libraries\TourPayments\Perms;
use App\Libraries\TourPayments\Schema;
use App\Libraries\TourPayments\SupplierBank;

/**
 * tf-paydesk-r1 — two masters behind the payment workflow:
 *   master_files/bank_accounts   the Avenir Bank Account Master (the company's own accounts, tied to their ledger codes)
 *   master_files/supplier_bank   suppliers' bank-payment details and their verification
 */
class TourPaymentMastersController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function json(array $p, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($p);
    }

    private function page(string $view, array $extra = [])
    {
        $data = (new MenuLoader())->loadMenuData();
        $data['perms'] = Perms::all();
        $data['tpd_ready'] = Schema::installed($this->db());
        return view($view, $data + $extra);
    }

    // ---- Avenir Bank Account Master ----

    public function bank_accounts()
    {
        if (!Perms::can('bank_master') && !Perms::can('accountant')) {
            return redirect()->to(base_url('403'));
        }
        return $this->page('pages/master_files/tpd_bank_accounts', ['accounts' => BankAccounts::all($this->db()), 'ledgers' => BankAccounts::ledgerAccounts($this->db()), 'can_edit' => Perms::can('bank_master')]);
    }

    public function bank_accounts_save()
    {
        if (!Perms::can('bank_master')) {
            return $this->json(['status' => 403, 'message' => 'Managing the Avenir Bank Account Master needs its privilege.'], 403);
        }
        $r = BankAccounts::save($this->db(), $this->request->getPost() ?: [], Perms::user());
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message'], 'id' => $r['id'] ?? null]);
    }

    public function bank_accounts_list()
    {
        if (!Perms::can('bank_master') && !Perms::can('accountant') && !Perms::can('approver')) {
            return $this->json(['status' => 403, 'message' => 'Forbidden'], 403);
        }
        return $this->json(['status' => 200, 'data' => BankAccounts::forSelection($this->db())]);
    }

    // ---- Supplier bank details ----

    public function supplier_bank()
    {
        if (!Perms::can('supplier_bank') && !Perms::can('verifier') && !Perms::can('accountant')) {
            return redirect()->to(base_url('403'));
        }
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        return $this->page('pages/master_files/tpd_supplier_bank', ['rows' => SupplierBank::listing($this->db(), $q), 'q' => $q, 'can_edit' => Perms::can('supplier_bank'), 'can_verify' => Perms::can('verifier')]);
    }

    public function supplier_bank_get()
    {
        if (!Perms::can('supplier_bank') && !Perms::can('verifier') && !Perms::can('accountant') && !Perms::can('requester')) {
            return $this->json(['status' => 403, 'message' => 'Forbidden'], 403);
        }
        $acc = trim((string) ($this->request->getGet('acc') ?? ''));
        return $this->json(['status' => 200, 'data' => SupplierBank::forPayment($this->db(), $acc)]);
    }

    public function supplier_bank_save()
    {
        if (!Perms::can('supplier_bank')) {
            return $this->json(['status' => 403, 'message' => 'Saving supplier bank details needs the Supplier Bank Details privilege.'], 403);
        }
        $acc = trim((string) $this->request->getPost('sup_acc'));
        $r = SupplierBank::save($this->db(), $acc, $this->request->getPost() ?: [], Perms::user());
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message'], 'state' => $r['status'] ?? null]);
    }

    public function supplier_bank_verify()
    {
        if (!Perms::can('verifier')) {
            return $this->json(['status' => 403, 'message' => 'Verifying needs the Supplier bank verification privilege.'], 403);
        }
        $acc = trim((string) $this->request->getPost('sup_acc'));
        $r = SupplierBank::verify($this->db(), $acc, Perms::user(), (string) $this->request->getPost('note'));
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message']]);
    }
}
