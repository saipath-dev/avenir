<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\EntranceMaster;
use App\Libraries\TourFlow\SafariRates;
use App\Libraries\TourFlow\Staff;

/**
 * Tour Desk → Masters → Safari Rate Master — master_files/entrance_master/safari (tf-safarirate-r1, 27 Nov 2026, Shantha)
 *
 * The rate entry of each safari park (Minneriya, Yala): seasonal entrance per visitor, the gate charges of a foreign group,
 * the jeep supplier with its source and evidence, the quotation settings (USD 75 an adult, child price open, USD 1 = LKR 320),
 * an internal cost preview and the cost checks by party size. Opened under the Places & Entrance Fees privilege: whoever
 * holds that master holds this one.
 */
class SafariRateController extends BaseController
{
    public const URL = SafariRates::URL;

    private ?array $menuData = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function actor(): string
    {
        $id = (int) session()->get('user_id');
        try {
            return Staff::displayName($this->db(), $id > 0 ? $id : null, $this->user()) ?: $this->user();
        } catch (\Throwable $e) {
            return $this->user();
        }
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

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    // GET master_files/entrance_master/safari
    public function index()
    {
        if (!$this->holds(EntranceMaster::URL)) {
            return redirect()->to(base_url('dashboard2/tiles'));
        }
        $data = $this->menuData();
        $data['page_url']  = self::URL;
        $data['place_url'] = EntranceMaster::URL;
        $data['ready']     = SafariRates::ensure($this->db());
        return view('pages/master_files/safari_rate_master', $data);
    }

    // GET master_files/entrance_master/safari/data — the parks, their unit figures, cost checks and history
    public function data()
    {
        if (!$this->holds(EntranceMaster::URL)) {
            return $this->json(['status' => 403, 'message' => 'You do not hold the Places & Entrance Fees master.'], 403);
        }
        try {
            $db = $this->db();
            $parks = [];
            foreach (SafariRates::all($db) as $r) {
                $place = null;
                if ($r['place_id']) {
                    $place = $db->query('SELECT id, name, code FROM tf_place WHERE id = ?', [$r['place_id']])->getRowArray();
                }
                $parks[] = $r + [
                    'place' => $place,
                    'units' => SafariRates::units($r),
                    'gaps' => SafariRates::gaps($r),
                    'checks' => SafariRates::checks($r, [1, 2, 6, 7], 'tourist'),
                    'checks_regular' => SafariRates::checks($r, [1, 2, 6, 7], 'regular'),
                    'preview' => SafariRates::cost($r, 2) + ['sell' => SafariRates::sell($r, 2)],
                    'history' => SafariRates::history($db, (int) $r['id']),
                ];
            }
            $usd = null;
            try {
                $usd = $db->query('SELECT rate, rate_date FROM daily_usd_rate ORDER BY rate_date DESC LIMIT 1')->getRowArray();
            } catch (\Throwable $e) {
            }
            return $this->json(['status' => 200, 'data' => ['parks' => $parks, 'cats' => SafariRates::CATS, 'seasons' => SafariRates::SEASONS, 'evidence' => SafariRates::EVIDENCE, 'daily_usd' => $usd]]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    // POST master_files/entrance_master/safari/save — one park's figures
    public function save()
    {
        if (!$this->holds(EntranceMaster::URL)) {
            return $this->json(['status' => 403, 'message' => 'You do not hold the Places & Entrance Fees master.'], 403);
        }
        $v = $this->request->getJSON(true);
        $v = is_array($v) ? $v : ($this->request->getPost() ?: []);
        try {
            $out = SafariRates::save($this->db(), $v, $this->actor());
            return $this->json(['status' => 200, 'message' => $out['changed'] ? 'Saved. Inquiries pick the new figures up when they are next checked or recalculated.' : 'Nothing changed.', 'data' => $out]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['status' => 400, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return $this->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}
