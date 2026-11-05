<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\Dates;
use App\Libraries\HotelRates\QuoteReadiness;
use App\Libraries\MenuLoader;

/**
 * Hotel Rates → Quotation readiness — master_files/hotel_rate_v2/readiness (tf-hr-ready-r1)
 *
 * Two lists — the hotels the quotation engine can price by itself on a date, and the hotels it cannot, each with the
 * reason — and the rate-expiry warnings (14 days before an approved contract rate runs out, until an approved rate
 * covers the night after). Everything comes from QuoteReadiness, which asks the quotation engine itself and writes
 * nothing: opening this page runs no scan, saves no setting and changes no rate, quotation or work item.
 *
 * Privileges: no menu entry and no grant of its own. The page sits under master_files/hotel_rate_v2 (Hotel Rates
 * (new)), so the existing route filter lets in exactly the sign-ins that hold the Hotel Rates panel. Links to Hotel
 * Incomplete Work and the approval queue show only to a sign-in that holds them.
 */
class HotelReadinessController extends BaseController
{
    public const URL = 'master_files/hotel_rate_v2/readiness';
    public const GATE = 'master_files/hotel_rate_v2';

    private ?array $menuData = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
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

    /** Whether the signed-in group holds a menu URL (the System Administrator holds every one). */
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

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function installed($db): bool
    {
        $v = \App\Libraries\HotelRates\Installer::installedVersion($db);
        return $v !== null && $v >= \App\Libraries\HotelRates\Installer::SCHEMA_VERSION;
    }

    // GET master_files/hotel_rate_v2/readiness
    public function index()
    {
        $data = $this->menuData();
        $data['page_url'] = self::URL;
        $data['panel_url'] = self::GATE;
        $data['work_url'] = HotelWorkController::URL;
        $data['approval_url'] = HotelRateV2Controller::APPROVAL_URL;
        $data['can_work'] = $this->holds(HotelWorkController::URL);
        $data['can_approve'] = $this->holds(HotelRateV2Controller::APPROVAL_URL);
        $data['today'] = date('Y-m-d');
        $data['alert_days'] = QuoteReadiness::ALERT_DAYS;
        $data['installed'] = false;
        $data['default_market'] = 'JP';
        $data['quotation_types'] = [];
        try {
            $db = \Config\Database::connect();
            $data['installed'] = $this->installed($db);
            if ($data['installed']) {
                $q = new QuoteReadiness($db);
                $data['default_market'] = $q->defaultMarket();
                $data['quotation_types'] = $q->quotationTypes();
            }
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/master_files/hotel_rate_readiness', $data);
    }

    // GET master_files/hotel_rate_v2/readiness/data?date=YYYY-MM-DD — both lists and the warnings for that night
    public function data()
    {
        try {
            $db = \Config\Database::connect();
            if (!$this->installed($db)) {
                return $this->json(['status' => 400, 'message' => 'The Hotel Rates tables are not installed.']);
            }
            $date = Dates::parse((string) ($this->request->getGet('date') ?? '')) ?: date('Y-m-d');
            $t0 = microtime(true);
            $report = (new QuoteReadiness($db))->report($date);
            $report['took_ms'] = (int) round((microtime(true) - $t0) * 1000);
            return $this->json(['status' => 200, 'data' => $report]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // GET master_files/hotel_rate_v2/readiness/alerts — the rate-expiry warnings alone (the panel's banner)
    public function alerts()
    {
        try {
            $db = \Config\Database::connect();
            if (!$this->installed($db)) {
                return $this->json(['status' => 200, 'data' => ['alerts' => [], 'alert_days' => QuoteReadiness::ALERT_DAYS]]);
            }
            $q = new QuoteReadiness($db);
            return $this->json(['status' => 200, 'data' => ['alerts' => $q->alerts(), 'alert_days' => QuoteReadiness::ALERT_DAYS, 'today' => $q->today()]]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
