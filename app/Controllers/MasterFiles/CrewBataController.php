<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\IntakeService;

/**
 * Master Files → Crew Bata — master_files/crew_bata (schema 23)
 *
 * The driver and guide roles a quotation can carry: a category (Driver, Chauffeur guide,
 * National guide …) in a language, with the bata per day the business pays for it. The
 * quotation screen picks the type and the language from this list and shows the bata;
 * it is maintained here and only here. A changed bata reaches every inquiry still being
 * worked on straight away (the existing rule), never one already quoted.
 *
 * One menu URL = one privilege (UserPermissionFilter). Granted to the System
 * Administrator by the installer; Admin grants it further under Group Privileges.
 */
class CrewBataController extends BaseController
{
    public const URL = 'master_files/crew_bata';

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

    // GET master_files/crew_bata
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
        return view('pages/master_files/crew_bata', $data);
    }

    // GET master_files/crew_bata/data — every role, active or not, with how many quotation lines carry it
    public function data()
    {
        try {
            $rows = $this->svc()->crewRoles(true);
            $used = [];
            $open = [];
            foreach ($this->db()->query("SELECT c.role_code, COUNT(*) n, SUM(i.status IN ('new','working','quoted')) o FROM tf_intake_crew c JOIN tf_intake i ON i.id = c.intake_id GROUP BY c.role_code")->getResultArray() as $r) {
                $used[(string) $r['role_code']] = (int) $r['n'];
                $open[(string) $r['role_code']] = (int) $r['o'];
            }
            foreach ($rows as &$r) {
                $r['used'] = $used[$r['code']] ?? 0;
                $r['open'] = $open[$r['code']] ?? 0;
            }
            unset($r);
            return $this->json(['status' => 200, 'data' => $rows]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_bata/save — add a role, or change its category, language, name, note, bata, active
    public function save()
    {
        try {
            $row = $this->svc()->saveCrewRole($this->body());
            return $this->json(['status' => 200, 'message' => 'Saved. Every inquiry still being worked on shows it as it is now; a quotation already issued keeps its figures.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_bata/bata — the bata per day for one role, on its own
    public function bata()
    {
        try {
            $b = $this->body();
            $this->svc()->saveCrewBata((string) ($b['code'] ?? ''), $b['bata'] ?? null, $b['currency'] ?? null);
            return $this->json(['status' => 200, 'message' => 'Bata saved. Every inquiry still being worked on picks it up; a quotation already issued keeps its figures.', 'data' => $this->svc()->crewRoles(true)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_bata/delete/(:segment) — off the list; switched off instead when any quotation line carries it
    public function delete($code)
    {
        try {
            $r = $this->svc()->deleteCrewRole((string) $code);
            return $this->json(['status' => 200, 'message' => $r['deleted'] ? 'Removed.' : 'It is on ' . $r['used'] . ' quotation line(s), so it was switched off rather than removed; those lines keep what they carry.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
