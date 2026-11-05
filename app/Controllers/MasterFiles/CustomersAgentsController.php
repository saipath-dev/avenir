<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\CustomersAgents;
use App\Libraries\TourFlow\Staff;

/**
 * Master Files → Customers & Agents — master_files/customer/agents (tf-stage6, 29 Oct 2026).
 *
 * The door's own page: agents first, then direct customers; one search; a record view with the contact details, the agent
 * settings (flag, quotation currency, default markup) and the recent tours and inquiries under it. The records and codes are
 * the existing customer ledger; adding and editing a customer stay on the existing pages. The page is reached under the
 * existing Customers (Consignee) privilege (a sub-path of master_files/customer); changing the flag or the quotation
 * settings needs Pricing (Management) or the System Administrator and is written to the history.
 */
class CustomersAgentsController extends BaseController
{
    public const URL = CustomersAgents::URL;
    public const PRICING_URL = 'tour/pricing';

    private ?array $menuData = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function userId(): ?int
    {
        $id = (int) session()->get('user_id');
        return $id > 0 ? $id : null;
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function menuData(): array
    {
        if ($this->menuData === null) {
            try {
                $this->menuData = (new MenuLoader())->loadMenuData();
            } catch (\Throwable $e) {
                $this->menuData = ['user_role' => session()->get('group_id'), 'allowed_menu_list' => []];
            }
        }
        return $this->menuData;
    }

    private function holds(string $url): bool
    {
        $md = $this->menuData();
        if ((int) ($md['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ((array) ($md['allowed_menu_list'] ?? []) as $m) {
            $u = is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '');
            if (trim((string) $u, '/') === $url) {
                return true;
            }
        }
        return false;
    }

    private function canSet(): bool
    {
        return $this->holds(self::PRICING_URL);
    }

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : ($this->request->getPost() ?: []);
    }

    // GET master_files/customer/agents
    public function index()
    {
        $data = $this->menuData();
        $data['page_url']   = self::URL;
        $data['installed']  = CustomersAgents::installed($this->db());
        $data['can_set']    = $this->canSet();
        $data['open_acc']   = trim((string) ($this->request->getGet('acc') ?? ''));
        $data['initial_q']  = trim((string) ($this->request->getGet('q') ?? ''));
        $data['initial_f']  = trim((string) ($this->request->getGet('f') ?? ''));
        $data['edit_url']   = 'master_files/customer/all';
        $data['new_url']    = 'master_files/customer';
        $data['intake_url'] = 'tour/intake';
        return view('pages/master_files/customers_agents', $data);
    }

    // GET master_files/customer/agents/data?q=&f=
    public function data()
    {
        try {
            $db = $this->db();
            return $this->json(['status' => 200, 'data' => CustomersAgents::list($db, trim((string) ($this->request->getGet('q') ?? '')), trim((string) ($this->request->getGet('f') ?? ''))), 'counts' => CustomersAgents::counts($db)]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // GET master_files/customer/agents/record?acc=
    public function record()
    {
        $r = CustomersAgents::one($this->db(), trim((string) ($this->request->getGet('acc') ?? '')));
        return $r ? $this->json(['status' => 200, 'data' => $r]) : $this->json(['status' => 404, 'message' => 'No such customer.'], 404);
    }

    // POST master_files/customer/agents/set_agent {acc, agent: 0|1, note}
    public function setAgent()
    {
        if (!$this->canSet()) {
            return $this->json(['status' => 403, 'message' => 'Marking a customer as an agent is for Management (the Pricing privilege) or the System Administrator.'], 403);
        }
        $b = $this->body();
        $r = CustomersAgents::setAgent($this->db(), trim((string) ($b['acc'] ?? '')), (int) ($b['agent'] ?? 0) === 1, $this->user(), $this->userId(), Staff::displayName($this->db(), $this->userId(), $this->user()), trim((string) ($b['note'] ?? '')));
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message']]);
    }

    // POST master_files/customer/agents/set_quote {acc, quote_currency, quote_markup_pct}
    public function setQuote()
    {
        if (!$this->canSet()) {
            return $this->json(['status' => 403, 'message' => 'The quotation currency and default markup are for Management (the Pricing privilege) or the System Administrator.'], 403);
        }
        $b = $this->body();
        $r = CustomersAgents::setQuote($this->db(), trim((string) ($b['acc'] ?? '')), $b['quote_currency'] ?? null, $b['quote_markup_pct'] ?? '', $this->user(), $this->userId(), Staff::displayName($this->db(), $this->userId(), $this->user()));
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message']]);
    }
}
