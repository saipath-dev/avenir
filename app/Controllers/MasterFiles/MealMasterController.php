<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\MealMaster;
use App\Libraries\TourFlow\ProgrammeMasters;
use App\Libraries\TourFlow\Staff;

/**
 * Master Files → Meal Master — master_files/meal_master (schema 40, tf-meals-r1)
 *
 * Lunches and dinners outside the hotel for passengers travelling on Half Board packages.
 * Each meal is one restaurant's lunch or dinner at one location for one period: the supplier
 * (picked from the Supplier master, never typed in), the restaurant's name, location, address,
 * contact, cuisine and what the meal is, the adult and child rate in USD or LKR, the taxes,
 * Valid From / Valid To, in use or not, remarks — and the legs of the route it serves
 * (Colombo → Kandy …), so a day of a booking that drives the leg is offered it. A meal is
 * switched off and kept, never deleted. The screen lists and searches the meals (by type,
 * location, supplier, validity), adds, edits and views them, and holds the meal settings.
 *
 * One menu URL = one privilege (UserPermissionFilter): granted to the System Administrator
 * by the installer and to nobody else; Admin grants it further under Group Privileges.
 */
class MealMasterController extends BaseController
{
    public const URL = MealMaster::URL;
    public const SUPPLIER_URL = 'master_files/supplier';

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

    private function master(): MealMaster
    {
        $db = $this->db();
        return new MealMaster($db, $this->user(), $this->userId(), Staff::displayName($db, $this->userId(), $this->user()));
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

    /** Whether the signed-in group may open a URL — read from the same privileges the sidebar reads. */
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

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : ($this->request->getPost() ?: []);
    }

    private function fail(\Throwable $e, int $status = 400)
    {
        return $this->json(['status' => $status, 'message' => $e->getMessage()]);
    }

    // GET master_files/meal_master
    public function index()
    {
        $data = $this->menuData();
        $data['user']         = $this->user();
        $data['page_url']     = self::URL;
        $data['intake_url']   = 'tour/intake';
        $data['supplier_url'] = self::SUPPLIER_URL;
        $data['sees_supplier'] = $this->holds(self::SUPPLIER_URL);
        $data['installed']    = false;
        try {
            $v = Installer::installedVersion($this->db());
            $data['installed'] = $v !== null && $v >= Installer::SCHEMA_VERSION && MealMaster::installed($this->db());
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/master_files/meal_master', $data);
    }

    // GET master_files/meal_master/data?on=YYYY-MM-DD — every meal (validity read on that date), the suppliers a meal may come
    // from (the Supplier master, read only), the destinations the masters know, the settings
    public function data()
    {
        try {
            $m  = $this->master();
            $on = trim((string) ($this->request->getGet('on') ?? ''));
            $on = preg_match('/^\d{4}-\d{2}-\d{2}$/', $on) ? $on : date('Y-m-d');
            $meals = $m->meals(['on' => $on]);
            $dests = [];
            try {
                $dests = (new ProgrammeMasters($this->db(), $this->user()))->destinations();
            } catch (\Throwable $e) {
                $dests = [];
            }
            $count = ['all' => count($meals), 'lunch' => 0, 'dinner' => 0, 'valid' => 0, 'off' => 0, 'ended' => 0, 'future' => 0, 'supplier' => 0];
            foreach ($meals as $x) {
                $count[$x['meal_type']]++;
                $count[$x['status']] = ($count[$x['status']] ?? 0) + 1;
            }
            return $this->json(['status' => 200, 'data' => [
                'on'           => $on,
                'meals'        => $meals,
                'count'        => $count,
                'suppliers'    => $m->suppliers(),
                'supplier_cats' => $m->supplierCats(),
                'all_cats'     => MealMaster::SUPPLIER_CATS,
                'types'        => MealMaster::TYPES,
                'currencies'   => MealMaster::CURRENCIES,
                'destinations' => $dests,
                'settings'     => $m->settings(),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET master_files/meal_master/meal/(:num) — one meal in full, with what has been done to it
    public function view($id)
    {
        try {
            $m = $this->master()->meal((int) $id);
            if (!$m) {
                return $this->json(['status' => 404, 'message' => 'That meal is not in the Meal Master.'], 404);
            }
            $m['history'] = [];
            try {
                $rows = $this->db()->query("SELECT a.id, a.at, a.by_name, a.by_user, a.summary FROM tf_activity a WHERE a.entity = 'master' AND a.entity_id = ? AND a.action = 'meal_master' ORDER BY a.id DESC LIMIT 20", [(int) $id])->getResultArray();
                $m['history'] = array_map(static fn ($r) => ['at' => $r['at'], 'by' => $r['by_name'] ?: $r['by_user'], 'summary' => $r['summary']], $rows);
            } catch (\Throwable $e) {
                $m['history'] = [];
            }
            return $this->json(['status' => 200, 'data' => $m]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/meal_master/save {id?, code, meal_type, supplier_id, restaurant, location, address, contact_name, contact_phone,
    // contact_email, cuisine, description, adult_rate, child_rate, currency, tax_pct, tax_included, tax_note, valid_from, valid_to, active,
    // remarks, routes: [{from, to}], confirm_high}
    public function save()
    {
        try {
            $b   = $this->body();
            $new = empty($b['id']);
            $m   = $this->master()->save($b);
            // tf-lunch-r1: dietary options, the child age band, the rate's approval, one default per route
            \App\Libraries\TourFlow\LunchArrangement::saveExtra(\Config\Database::connect(), (int) $m['id'], $b);
            $m   = $this->master()->meal((int) $m['id']) ?? $m;
            $msg = ($new ? 'Meal added' : 'Meal saved') . ': ' . $m['code'] . ' — ' . $m['title'] . '.';
            if (!$m['active']) {
                $msg .= ' Switched off: it is not offered for a booking.';
            } elseif ($m['status'] !== 'valid') {
                $msg .= ' ' . $m['status_text'] . '.';
            }
            if (!$new && ($m['used']['lines'] ?? 0) > 0) {
                $msg .= ' The ' . $m['used']['lines'] . ' line(s) linked to it on open inquiries show the change for review the next time each is opened — the saved figure moves only when Recalculate is pressed; a quotation version already issued keeps its figures.';
            }
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $m]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/meal_master/meal/(:num)/active {active: 0|1} — switched off and kept, or on again
    public function active($id)
    {
        try {
            $b  = $this->body();
            $on = !empty($b['active']);
            $m  = $this->master()->setActive((int) $id, $on);
            return $this->json(['status' => 200, 'message' => $on
                ? 'Switched on: ' . $m['code'] . ' is offered for a booking again' . ($m['status'] !== 'valid' ? ' (' . lcfirst($m['status_text']) . ')' : '') . '.'
                : 'Switched off and kept: ' . $m['code'] . ' is no longer offered for a booking' . (($m['used']['lines'] ?? 0) > 0 ? '; the ' . $m['used']['lines'] . ' line(s) linked to it are held on open inquiries until another meal is picked' : '') . '.', 'data' => $m]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/meal_master/settings {meal_hb_default, meal_lunch_time, meal_dinner_time, meal_supplier_cats, meal_auto_lunch}
    public function settings()
    {
        try {
            $s = $this->master()->saveSettings($this->body());
            return $this->json(['status' => 200, 'message' => 'Settings saved. Open inquiries read them the next time each is opened.', 'data' => $s]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
