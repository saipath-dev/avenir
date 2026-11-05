<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Activity;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\IntakeService;
use App\Libraries\TourFlow\PaxMatrix;
use App\Libraries\TourFlow\QuoteService;

/**
 * Pricing — tour/pricing (Management)
 *
 * The one place the markup lives: the company default, and an override per inquiry.
 * Staff on the Quotation Desk never see this screen, nor a markup or a profit figure
 * anywhere else — they see cost and the client's price. One menu URL = one privilege.
 * Also Management's room basis for the pax matrix (schema 31): the rooms each party size
 * from 1 to 10 is costed on.
 */
class PricingController extends BaseController
{
    public const URL = 'tour/pricing';

    private ?array $menuData = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function take(): IntakeService
    {
        return new IntakeService($this->db(), $this->user(), new HotelRateService($this->db(), $this->user()));
    }

    private function quotes(): QuoteService
    {
        return new QuoteService($this->db(), $this->user(), $this->take());
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

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : ($this->request->getPost() ?: []);
    }

    private function fail(\Throwable $e, int $status = 400)
    {
        return $this->json(['status' => $status, 'message' => $e->getMessage()]);
    }

    // GET tour/pricing
    public function index()
    {
        $data = $this->menuData();
        $data['user']       = $this->user();
        $data['page_url']   = self::URL;
        $data['intake_url'] = TourIntakeController::URL;
        $data['installed']  = false;
        try {
            $v = Installer::installedVersion($this->db());
            $data['installed'] = $v !== null && $v >= Installer::SCHEMA_VERSION;
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/tour/pricing', $data);
    }

    // GET tour/pricing/data — the settings and every open or quoted inquiry, costed
    public function data()
    {
        try {
            $take = $this->take();
            $q    = $this->quotes();
            $rows = [];
            foreach ($take->list(['status' => 'open', 'limit' => 60]) as $r) {
                try {
                    $c = $q->costing((int) $r['id']);
                    $rows[] = [
                        'id' => (int) $r['id'], 'inquiry_no' => $r['inquiry_no'], 'status' => $r['status'], 'agent_name' => $r['agent_name'], 'tour_name' => $r['tour_name'],
                        'arrival_date' => $r['arrival_date'], 'nights' => $r['nights'], 'pax' => $c['pax'],
                        'cost_usd' => $c['cost_usd'], 'markup' => $c['markup'], 'ready' => $c['ready'], 'blockers' => count($c['blockers']),
                    ];
                } catch (\Throwable $e) {
                    $rows[] = ['id' => (int) $r['id'], 'inquiry_no' => $r['inquiry_no'], 'status' => $r['status'], 'agent_name' => $r['agent_name'], 'tour_name' => $r['tour_name'], 'error' => $e->getMessage()];
                }
            }
            return $this->json(['status' => 200, 'data' => ['settings' => $q->pricing(), 'rows' => $rows, 'pax_rooms' => array_values($this->matrix($take)->basis())]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    private function matrix(?IntakeService $take = null): PaxMatrix
    {
        return new PaxMatrix($this->db(), ($take ?? $this->take())->planner());
    }

    // POST tour/pricing/pax_rooms/(:num) {rooms_sgl, rooms_dbl, rooms_twn, rooms_tpl} — the rooms one party
    // size is costed on in the pax matrix (schema 31); they must sleep exactly that many. Logged.
    public function paxRooms($pax)
    {
        try {
            $basis = $this->matrix()->saveBasis((int) $pax, $this->body(), $this->user());
            return $this->json(['status' => 200, 'message' => (int) $pax . ' pax: ' . ($basis[(int) $pax]['text'] ?? 'saved') . '. The next costing uses it; quotations already frozen keep theirs.', 'data' => ['pax_rooms' => array_values($basis)]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/pricing/settings {markup_default_pct, quote_validity_days, quote_terms}
    public function settings()
    {
        try {
            $was = $this->quotes()->pricing();
            $now = $this->quotes()->savePricing($this->body());
            // Management's defaults, on the log with what they were — read only by a group holding Pricing
            $changes = [];
            foreach ($now as $k => $v) {
                if (!is_scalar($v) && $v !== null) {
                    continue;
                }
                $changes[] = ['what' => str_replace('_', ' ', (string) $k), 'from' => $was[$k] ?? null, 'to' => $v];
            }
            Activity::master($this->db(), $this->user(), 'pricing_settings', 0, 'Pricing settings changed', $changes);
            return $this->json(['status' => 200, 'message' => 'Pricing settings saved.', 'data' => $now]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/pricing/markup/(:num) {pct, note} — the markup for one inquiry, or back to the default (blank)
    public function markup($id)
    {
        try {
            $b = $this->body();
            $was = $this->db()->query('SELECT markup_pct FROM tf_intake WHERE id = ?', [(int) $id])->getRowArray();
            $c = $this->quotes()->setMarkup((int) $id, $b['pct'] ?? null, $b['note'] ?? null);
            // the markup's history is Management's, like the markup: entity 'pricing' is shown only to Pricing holders
            try {
                $now = $this->db()->query('SELECT markup_pct FROM tf_intake WHERE id = ?', [(int) $id])->getRowArray();
                (new Activity($this->db(), $this->user()))->logDiff('pricing', (int) $id, (int) $id, null, 'markup',
                    ['fields' => ['markup %' => $was['markup_pct'] ?? null]], ['fields' => ['markup %' => $now['markup_pct'] ?? null]], $b['note'] ?? null);
            } catch (\Throwable $e) {
            }
            return $this->json(['status' => 200, 'message' => ($b['pct'] ?? '') === '' ? 'Back on the default markup.' : 'Markup set.', 'data' => ['cost_usd' => $c['cost_usd'], 'markup' => $c['markup']]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
