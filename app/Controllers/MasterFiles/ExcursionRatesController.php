<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\IntakeService;

/**
 * Master Files → Excursion Rates — master_files/excursion_rates
 *
 * The excursions a quotation can carry — a jeep safari, a boat, a train, a village
 * tour — with their own rates, kept apart from entrance fees. Typed by a person and
 * changed by a person. A quotation copies the rate onto its own line the moment the
 * excursion is added, so a change here never moves a figure already quoted.
 *
 * One menu URL = one privilege (UserPermissionFilter).
 */
class ExcursionRatesController extends BaseController
{
    public const URL = 'master_files/excursion_rates';

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

    private function svc(): IntakeService
    {
        return new IntakeService($this->db(), $this->user(), new HotelRateService($this->db(), $this->user()));
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

    // GET master_files/excursion_rates
    public function index()
    {
        $data = $this->menuData();
        $data['user']       = $this->user();
        $data['page_url']   = self::URL;
        $data['intake_url'] = 'tour/intake';
        $data['installed']  = false;
        try {
            $v = Installer::installedVersion($this->db());
            $data['installed'] = $v !== null && $v >= Installer::SCHEMA_VERSION;
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/master_files/excursion_rates', $data);
    }

    // GET master_files/excursion_rates/data
    public function data()
    {
        try {
            $rows = $this->svc()->excursionRates(false);
            // how many quotation lines each one is on: a figure worth seeing before switching one off
            $used = [];
            foreach ($this->db()->query('SELECT code, COUNT(*) n FROM tf_intake_excursion GROUP BY code')->getResultArray() as $r) {
                $used[(string) $r['code']] = (int) $r['n'];
            }
            foreach ($rows as &$r) {
                $r['used'] = $used[(string) $r['code']] ?? 0;
            }
            unset($r);
            return $this->json(['status' => 200, 'data' => $rows]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/excursion_rates/save
    public function save()
    {
        try {
            $row = $this->svc()->saveExcursionRate($this->body());
            return $this->json(['status' => 200, 'message' => 'Saved. A quotation that already carries this excursion keeps the rate it was given; new ones take this one.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/excursion_rates/delete/(:segment)
    public function delete($code)
    {
        try {
            $r = $this->svc()->deleteExcursionRate((string) $code);
            return $this->json(['status' => 200, 'message' => $r['deleted'] ? 'Removed.' : 'It is on ' . $r['used'] . ' quotation line(s), so it was switched off rather than removed; those lines keep their copied figures.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
