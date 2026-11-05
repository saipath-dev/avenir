<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\TourPayments\Adoption;
use App\Libraries\TourPayments\Audit;
use App\Libraries\TourPayments\Mask;
use App\Libraries\TourPayments\Overview;
use App\Libraries\TourPayments\Perms;
use App\Libraries\TourPayments\Schema;

/**
 * tf-paydesk-r7 (17 Oct 2026) — the unified Payment Desk (tour/payment_desk).
 *
 *   GET  tour/payment_desk              the page (any payment role)
 *   GET  tour/payment_desk/data         the whole chain, read-only (any payment role)
 *   GET  tour/payment_desk/adopt_check  the adoption checks of one voucher, nothing written (accountant)
 *   POST tour/payment_desk/adopt        adopts one voucher: one tpd_adoption row and one audit line (bank-initiation privilege)
 *   POST tour/payment_desk/setup        creates tpd_adoption when it is missing (System Administrator)
 *
 * tf-paydesk-r7d: every POST here (adopt, setup) must carry the page's CSRF token (_remap, App\Libraries\TourPayments\Csrf).
 *
 * Every other action on the page is the existing endpoint that already does it (initiate, Paid, batches, approval,
 * remittance); this controller adds the overview and the adoption only. The privilege is checked here even though the
 * permission filter has let the request through (it only knows that the user holds some payment role).
 */
class PaymentDeskController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    /** tf-paydesk-r7d: the CSRF token is checked for every POST (adopt, setup) before the action and its privilege check. */
    public function _remap(string $method, ...$params)
    {
        $ok = $method !== '' && $method[0] !== '_' && method_exists($this, $method);
        if ($ok) {
            $rm = new \ReflectionMethod($this, $method);
            $ok = $rm->isPublic() && !$rm->isStatic() && $rm->getDeclaringClass()->getName() === self::class;
        }
        if (!$ok) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forMethodNotFound($method);
        }
        if ($why = \App\Libraries\TourPayments\Csrf::problem($this->request)) {
            return \App\Libraries\TourPayments\Csrf::refuse($this->db(), $this->request, $this->response, $why, $method);
        }
        return $this->$method(...$params);
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function json(array $p, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($p);
    }

    private function deny(string $what)
    {
        try {
            Audit::log($this->db(), ['action' => 'access_denied', 'ref_type' => 'action', 'ref_id' => mb_substr((string) service('uri')->getRoutePath(), 0, 80), 'reason' => mb_substr('privilege: ' . $what, 0, 500),
                'new' => ['method' => strtoupper($this->request->getMethod()), 'vnum' => $this->request->getGetPost('vnum')]]);
        } catch (\Throwable $e) {
        }
        if ($this->request->isAJAX() || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json') || strtoupper($this->request->getMethod()) !== 'GET') {
            return $this->json(['status' => 403, 'message' => $what . ' The attempt was recorded.'], 403);
        }
        return redirect()->to(base_url('403'));
    }

    /** tf-paydesk-r7b: adoption is for whoever may record the bank initiation — the same privilege the initiation route needs. */
    private function canInitiate(): bool
    {
        return Perms::isAdmin() || Perms::holds('report/bank_payment_check/initiate');
    }

    /** tf-paydesk-r7c: may this user open the Daily Bank Reconciliation (read-only view)? The page hides its links otherwise. */
    private function canDbr(): bool
    {
        try {
            return class_exists(\App\Libraries\BankRec\Access::class) && \App\Libraries\BankRec\Access::can('view');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** The role flags the page and the overview use: the desk roles, the initiation privilege and the reconciliation view. */
    private function perms(): array
    {
        return Perms::all() + ['initiate' => $this->canInitiate(), 'dbr' => $this->canDbr()];
    }

    private function installed(): bool
    {
        try {
            return Schema::installed($this->db());
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function index()
    {
        if (!Perms::can('viewer')) {
            return $this->deny('The Payment Desk needs a payment role.');
        }
        $data = (new \App\Libraries\MenuLoader())->loadMenuData();
        $data['tpd_perms'] = $this->perms();
        $data['tpd_user'] = Perms::user();
        $data['tpd_ready'] = $this->installed();
        $data['adoption_ready'] = Adoption::ready($this->db());
        $data['focus_vnum'] = (int) ($this->request->getGet('vnum') ?? 0);
        return view('pages/tour/payment_desk', $data);
    }

    public function data()
    {
        if (!Perms::can('viewer')) {
            return $this->deny('The Payment Desk needs a payment role.');
        }
        if (!$this->installed()) {
            return $this->json(['status' => 409, 'message' => 'The Tour Payment Desk is not installed on this system.']);
        }
        try {
            $d = Overview::build($this->db(), $this->perms());
        } catch (\Throwable $e) {
            log_message('error', 'payment desk overview: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return $this->json(['status' => 500, 'message' => 'The Payment Desk could not be built: ' . $e->getMessage()]);
        }
        $d['user'] = Perms::user();
        return $this->json(['status' => 200, 'data' => Mask::bank($d)]);
    }

    public function adopt_check()
    {
        if (!Perms::can('accountant')) {
            return $this->deny('Checking a voucher for adoption needs the Bank Payment Check (accountant) privilege.');
        }
        $vnum = (int) ($this->request->getGet('vnum') ?? 0);
        if ($vnum <= 0) {
            return $this->json(['status' => 400, 'message' => 'Give the Payment Voucher No.']);
        }
        $c = Adoption::check($this->db(), $vnum, ['bank_leg' => (int) ($this->request->getGet('bank_leg') ?? 0)]);
        return $this->json(['status' => 200, 'data' => $c]);
    }

    public function adopt()
    {
        if (!$this->canInitiate()) {
            return $this->deny('Adopting a voucher onto the Payment Desk needs the bank-initiation privilege (report/bank_payment_check/initiate), the same as recording the bank initiation.');
        }
        if (!$this->installed()) {
            return $this->json(['status' => 409, 'message' => 'The Tour Payment Desk is not installed on this system.']);
        }
        $vnum = (int) ($this->request->getPost('vnum') ?? 0);
        if ($vnum <= 0) {
            return $this->json(['status' => 400, 'message' => 'Give the Payment Voucher No.']);
        }
        $r = Adoption::adopt($this->db(), $vnum, Perms::user(), ['bank_leg' => (int) ($this->request->getPost('bank_leg') ?? 0), 'note' => (string) ($this->request->getPost('note') ?? '')]);
        return $this->json($r);
    }

    public function setup()
    {
        if (!Perms::isAdmin()) {
            return $this->deny('Setting up the Payment Desk adoption table is the System Administrator\'s step.');
        }
        try {
            return $this->json(['status' => 200] + Adoption::ensure($this->db(), Perms::user()));
        } catch (\Throwable $e) {
            return $this->json(['status' => 500, 'message' => 'tpd_adoption could not be created: ' . $e->getMessage()]);
        }
    }
}
