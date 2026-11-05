<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourPayments\Perms;
use App\Libraries\TourPayments\SupplierAccounts;

/**
 * tf-bankreg-r1 (22 Oct 2026) — master_files/supplier_accounts: the Supplier Bank Account Registry.
 * Viewing: a holder of the page (Settings → Group Privileges). Adding, editing, default and active: the Supplier Bank Details
 * privilege (master_files/supplier_bank) or the administrator. Verifying: the Supplier bank verification privilege.
 * Every change is written to the audit trail (who, what, when) by SupplierAccounts.
 */
class SupplierAccountsController extends BaseController
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

    private function canEdit(): bool
    {
        return Perms::isAdmin() || Perms::can('supplier_bank');
    }

    public function index()
    {
        $db = $this->db();
        $data = (new MenuLoader())->loadMenuData();
        $notes = [];
        if (!SupplierAccounts::installed($db)) {
            if (Perms::isAdmin()) {
                $notes = SupplierAccounts::ensure($db, Perms::user());   // first opening by the administrator sets the registry up
            }
        }
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $f = trim((string) ($this->request->getGet('f') ?? ''));
        $data += [
            'rows' => SupplierAccounts::installed($db) ? SupplierAccounts::listing($db, $q, $f) : [],
            'q' => $q, 'f' => $f, 'notes' => $notes,
            'installed' => SupplierAccounts::installed($db),
            'can_edit' => $this->canEdit(), 'can_verify' => Perms::can('verifier'), 'full_numbers' => Perms::bankDetails(),
            'history' => SupplierAccounts::installed($db) ? SupplierAccounts::history($db, $q !== '' && preg_match('/^[0-9\-]+$/', $q) ? $q : null, 60) : [],
        ];
        return view('pages/master_files/tpd_supplier_accounts', $data);
    }

    // GET list?sup_acc= — one supplier's accounts (for a payment screen's selection)
    public function list()
    {
        $db = $this->db();
        $acc = trim((string) ($this->request->getGet('sup_acc') ?? ''));
        $out = [];
        foreach (SupplierAccounts::forSupplier($db, $acc) as $a) {
            $out[] = SupplierAccounts::present($db, $acc, $a);
        }
        return $this->json(['status' => 200, 'data' => $out]);
    }

    public function save()
    {
        if (!$this->canEdit()) {
            return $this->json(['status' => 403, 'message' => 'Changing the registry needs the Supplier Bank Details privilege.'], 403);
        }
        $r = SupplierAccounts::save($this->db(), $this->request->getPost() ?: [], Perms::user());
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message'], 'id' => $r['id'] ?? null]);
    }

    public function set_default()
    {
        if (!$this->canEdit()) {
            return $this->json(['status' => 403, 'message' => 'Changing the registry needs the Supplier Bank Details privilege.'], 403);
        }
        $r = SupplierAccounts::setDefault($this->db(), (int) $this->request->getPost('id'), Perms::user());
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message']]);
    }

    public function set_active()
    {
        if (!$this->canEdit()) {
            return $this->json(['status' => 403, 'message' => 'Changing the registry needs the Supplier Bank Details privilege.'], 403);
        }
        $r = SupplierAccounts::setActive($this->db(), (int) $this->request->getPost('id'), (int) $this->request->getPost('active') === 1, Perms::user(), (string) $this->request->getPost('reason'));
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message']]);
    }

    public function verify()
    {
        if (!Perms::can('verifier')) {
            return $this->json(['status' => 403, 'message' => 'Verifying an account needs the Supplier bank verification privilege.'], 403);
        }
        $r = SupplierAccounts::verify($this->db(), (int) $this->request->getPost('id'), Perms::user(), (string) $this->request->getPost('note'));
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message']]);
    }

    // GET history?sup_acc= — the change log of one supplier's accounts (or the whole registry)
    public function history()
    {
        $rows = SupplierAccounts::history($this->db(), trim((string) ($this->request->getGet('sup_acc') ?? '')) ?: null, 200);
        return $this->json(['status' => 200, 'data' => $rows]);
    }
}
