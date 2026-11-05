<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\TourFlow\Discounts;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Staff;

/**
 * tf-discount-r1 — Tour Discounts (Pilot), tour/pricing/discount.
 *
 * One menu URL = one privilege. Every method checks it; the menu is not the gate, this is.
 * Ledger posting is switched off in Discounts::POSTING_ENABLED and cannot be turned on from here.
 */
class DiscountController extends BaseController
{
    public const URL = 'tour/pricing/discount';

    private function db()
    {
        return \Config\Database::connect();
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function userId(): ?int
    {
        $i = session()->get('user_id') ?? session()->get('id');
        return $i === null ? null : (int) $i;
    }

    private function name(): string
    {
        try {
            return (string) Staff::displayName($this->db(), $this->userId(), $this->user());
        } catch (\Throwable $e) {
            return $this->user();
        }
    }

    private function svc(): Discounts
    {
        return new Discounts($this->db(), $this->user(), $this->userId(), $this->name());
    }

    /** The privilege behind this URL. Checked on every call, not only when the menu is drawn. */
    private function holds(): bool
    {
        try {
            $r = $this->db()->query('SELECT 1 FROM user_priv_q WHERE user_id = ? AND url = ? LIMIT 1',
                [$this->userId(), self::URL])->getRowArray();
            return (bool) $r;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function refusal(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if ($this->holds()) {
            return null;
        }
        return $this->response->setStatusCode(403)->setJSON([
            'status' => 403,
            'message' => 'Tour Discounts needs its own privilege, which Management grants under Settings → Group Privileges. The attempt was recorded.',
        ]);
    }

    private function body(): array
    {
        $j = json_decode((string) $this->request->getBody(), true);
        return is_array($j) ? $j : (array) $this->request->getPost();
    }

    private function ok($data, string $message = ''): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response->setJSON(['status' => 200, 'message' => $message, 'data' => $data]);
    }

    private function fail(\Throwable $e, int $code = 400): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON(['status' => $code, 'message' => $e->getMessage()]);
    }

    // ---------------------------------------------------------------------------------------------

    /** The panel itself. */
    public function index()
    {
        if ($r = $this->refusal()) { return $r; }
        try {
            $data = (new MenuLoader())->loadMenuData();
        } catch (\Throwable $e) {
            $data = ['user_role' => session()->get('group_id'), 'allowed_menu_list' => []];
        }
        $data['user']            = $this->user();
        $data['page_url']        = self::URL;
        $data['posting_enabled'] = Discounts::POSTING_ENABLED;

        return view('pages/tour/pricing_discount', $data);
    }

    /** The same answer as before, for anything that wants it as data. */
    public function status()
    {
        if ($r = $this->refusal()) { return $r; }
        return $this->ok(['posting_enabled' => Discounts::POSTING_ENABLED],
            'Tour Discounts. Ledger posting is switched off.');
    }

    /** Find an issued invoice by its number or the customer's name. Read-only. */
    public function invoices()
    {
        if ($r = $this->refusal()) { return $r; }
        $q = trim((string) $this->request->getGet('q'));
        try {
            $sql  = 'SELECT id, invoice_no, date, consignee, credit_balance, dollar_amount, cancel
                     FROM debtor_invoice_log';
            $bind = [];
            if ($q !== '') {
                $sql .= ' WHERE invoice_no LIKE ? OR consignee LIKE ?';
                $bind = ['%' . $q . '%', '%' . $q . '%'];
            }
            $sql .= ' ORDER BY id DESC LIMIT 25';
            return $this->ok($this->db()->query($sql, $bind)->getResultArray());
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /** One invoice: its figures, which methods it allows, and why one is refused. */
    public function invoice($logId)
    {
        if ($r = $this->refusal()) { return $r; }
        try {
            $svc = $this->svc();
            $inv = $svc->invoice((int) $logId);
            return $this->ok([
                'invoice'     => $inv,
                'eligibility' => $svc->eligibility($inv),
                'discounts'   => $svc->listFor((int) $logId),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /** The private calculation, before anything is recorded. */
    public function preview($logId)
    {
        if ($r = $this->refusal()) { return $r; }
        try {
            $b = $this->body();
            return $this->ok($this->svc()->preview((int) $logId, (string) ($b['method'] ?? ''), (float) ($b['input'] ?? 0)));
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function request($logId)
    {
        if ($r = $this->refusal()) { return $r; }
        try {
            $b = $this->body();
            $r2 = $this->svc()->request((int) $logId, (string) ($b['method'] ?? ''), (float) ($b['input'] ?? 0),
                (string) ($b['reason'] ?? ''), (string) ($b['idem_key'] ?? ''));
            return $this->ok($r2, $r2['message']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function approve($id)
    {
        if ($r = $this->refusal()) { return $r; }
        try {
            $b = $this->body();
            $r2 = $this->svc()->approve((int) $id, (string) ($b['note'] ?? ''));
            return $this->ok($r2, $r2['message']);
        } catch (\Throwable $e) {
            return $this->fail($e, 409);
        }
    }

    public function cancel($id)
    {
        if ($r = $this->refusal()) { return $r; }
        try {
            $r2 = $this->svc()->cancel((int) $id, (string) ($this->body()['note'] ?? ''));
            return $this->ok($r2, $r2['message']);
        } catch (\Throwable $e) {
            return $this->fail($e, 409);
        }
    }

    public function reverse($id)
    {
        if ($r = $this->refusal()) { return $r; }
        try {
            $r2 = $this->svc()->reverse((int) $id, (string) ($this->body()['note'] ?? ''));
            return $this->ok($r2, $r2['message']);
        } catch (\Throwable $e) {
            return $this->fail($e, 409);
        }
    }

    /** The log of one discount. */
    public function history($id)
    {
        if ($r = $this->refusal()) { return $r; }
        return $this->ok($this->svc()->logFor((int) $id));
    }
}
