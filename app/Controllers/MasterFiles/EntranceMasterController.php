<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\EntranceMaster;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\Staff;

/**
 * Master Files → Place to Visit / Entrance Fee Master — master_files/entrance_master (schema 39, tf-entrance-r1)
 *
 * Entrances and safaris only: the paid entrance places and the safari parks, and the one
 * authority on their charges. Each place has its entrance rate for a period (adult and child,
 * USD or LKR, or Free) and, at a safari park, its jeep rate per vehicle; its destination,
 * whether it is in use, and its other names. Duplicates are merged once a person confirms
 * them. The one-time import from the old Place to Visit list is shown record by record, read
 * only; the old list itself is left as it is and is not followed.
 *
 * One menu URL = one privilege (UserPermissionFilter): granted to the System Administrator
 * by the installer and to nobody else; Admin grants it further under Group Privileges.
 */
class EntranceMasterController extends BaseController
{
    public const URL       = 'master_files/entrance_master';
    public const FEE_URL   = 'master_files/entrance_fee';
    public const PAID_URL  = 'master_files/paid_services';
    public const FREE_URL  = 'master_files/free_visits';
    public const EXC_URL   = 'master_files/excursion_rates';

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

    private function master(): EntranceMaster
    {
        $db = $this->db();
        return new EntranceMaster($db, $this->user(), $this->userId(), Staff::displayName($db, $this->userId(), $this->user()));
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

    // GET master_files/entrance_master
    public function index()
    {
        $data = $this->menuData();
        $data['user']       = $this->user();
        $data['page_url']   = self::URL;
        $data['intake_url'] = 'tour/intake';
        $data['fee_url']    = self::FEE_URL;
        $data['sees_fee']   = $this->holds(self::FEE_URL);
        $data['paid_url']   = self::PAID_URL;
        $data['sees_paid']  = $this->holds(self::PAID_URL);
        $data['free_url']   = self::FREE_URL;
        $data['sees_free']  = $this->holds(self::FREE_URL);
        $data['exc_url']    = self::EXC_URL;
        $data['sees_exc']   = $this->holds(self::EXC_URL);
        $data['installed']  = false;
        try {
            $v = Installer::installedVersion($this->db());
            $data['installed'] = $v !== null && $v >= Installer::SCHEMA_VERSION && EntranceMaster::installed($this->db());
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/master_files/entrance_master', $data);
    }

    // GET master_files/entrance_master/data?on=YYYY-MM-DD — every place with its rates and other names, the counts, the
    // one-time import from the old Place to Visit list (read only), the free visits the master also holds
    public function data()
    {
        try {
            $m  = $this->master();
            $on = trim((string) ($this->request->getGet('on') ?? ''));
            $on = preg_match('/^\d{4}-\d{2}-\d{2}$/', $on) ? $on : date('Y-m-d');
            $places = $m->places($on);
            $dests = [];
            try {
                $dests = (new \App\Libraries\TourFlow\ProgrammeMasters($this->db(), $this->user()))->destinations();
            } catch (\Throwable $e) {
                $dests = [];
            }
            return $this->json(['status' => 200, 'data' => [
                'on'           => $on,
                'places'       => $places,
                'summary'      => EntranceMaster::summary($places),
                'import'       => $m->importReport(),
                'conflicts'    => $m->conflicts(),
                'currencies'   => EntranceMaster::CURRENCIES,
                'charges'      => EntranceMaster::CHARGES,
                'pay_modes'    => EntranceMaster::PAY_MODES,
                'categories'   => EntranceMaster::CATEGORIES,
                'destinations' => $dests,
                'roles'        => EntranceMaster::ROLES,   // tf-safari-r1
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET master_files/entrance_master/check?title=&date=&kind=&adults=&children=&qty= — what the master makes of a line, read as a quotation line is (qty: the jeeps at a safari park)
    public function check()
    {
        try {
            $g = fn (string $k) => $this->request->getGet($k);
            $title = trim((string) ($g('title') ?? ''));
            if ($title === '') {
                throw new \InvalidArgumentException('Type a name to try.');
            }
            $date = trim((string) ($g('date') ?? ''));
            $kind = trim((string) ($g('kind') ?? ''));
            $int  = static fn ($v) => $v === null || $v === '' ? null : max(0, (int) $v);
            return $this->json(['status' => 200, 'data' => $this->master()->check($title, preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null, $kind !== '' ? $kind : null, $int($g('adults')), $int($g('children')), $g('qty'))]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/entrance_master/place {id?, name, code, destination, location, map_link, description, category, pay_mode, jeep, active}
    public function place()
    {
        try {
            $p = $this->master()->savePlace($this->body());
            return $this->json(['status' => 200, 'message' => 'Saved.' . ((int) ($p['active'] ?? 1) !== 1 ? ' Not in use: an entrance or safari line naming it is held until someone puts it right.' : (empty($p['rates']) ? ' It has no rate yet: a quotation using it waits until a rate is entered or it is marked Free.' : '')), 'data' => $p]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/entrance_master/places/merge {from, into} — two places that are one
    public function mergePlaces()
    {
        try {
            $b = $this->body();
            $r = $this->master()->mergePlaces((int) ($b['from'] ?? 0), (int) ($b['into'] ?? 0));
            return $this->json(['status' => 200, 'message' => 'Merged. The place merged is kept, out of use, and read as the one kept; its names point there.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/entrance_master/rate {id?, place_id, charge (entrance | jeep), free_entry, valid_from, valid_to, adult_rate, child_rate, currency, note, close_previous, confirm_high}
    public function rate()
    {
        try {
            $r = $this->master()->saveRate($this->body());
            return $this->json(['status' => 200, 'message' => 'Saved' . ($r['closed'] ? ' — the rate before it now ends the day before' : '') . '. An open inquiry with a saved figure shows the change for review the next time it is opened — the figure moves only when Recalculate is pressed; a quotation version already issued keeps its figures.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/entrance_master/rate/(:num)/off — switched off and kept
    public function rateOff($id)
    {
        try {
            $r = $this->master()->switchOffRate((int) $id);
            return $this->json(['status' => 200, 'message' => 'Rate switched off and kept. Lines that used it are worked out again on their next read.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // tf-safari-r1 ---------------------------------------------------------------------------------------------
    // GET  master_files/entrance_master/suppliers?q=          suppliers to link (name, account code — nothing else)
    // POST master_files/entrance_master/party {place_id, role, supplier_id, business_name, …, account_number, clear_account}
    // GET  master_files/entrance_master/place/(:num)/delete   what Delete would do (removed, or archived because it was used)
    // POST master_files/entrance_master/place/(:num)/delete {note}
    // POST master_files/entrance_master/place/(:num)/restore
    public function suppliers()
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->master()->suppliers((string) ($this->request->getGet('q') ?? ''))]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function party()
    {
        try {
            $r = $this->master()->saveParty($this->body());
            return $this->json(['status' => 200, 'message' => 'Saved.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function deletePlace($id)
    {
        try {
            $m = $this->master();
            if (strtolower($this->request->getMethod()) !== 'post') {
                return $this->json(['status' => 200, 'data' => $m->deleteCheck((int) $id)]);
            }
            $b = $this->body();
            $r = $m->deletePlace((int) $id, isset($b['note']) ? (string) $b['note'] : null);
            return $this->json(['status' => 200, 'message' => $r['done'] === 'archived'
                ? $r['place'] . ' archived: it was used (' . implode(', ', $r['why']) . '), so it is kept for that history and no longer offered. Restore it from the Archived filter.'
                : $r['place'] . ' deleted. It had never been used.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function restorePlace($id)
    {
        try {
            $p = $this->master()->restorePlace((int) $id);
            return $this->json(['status' => 200, 'message' => ($p['name'] ?? 'The place') . ' restored and in use again.', 'data' => $p]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/entrance_master/alias {place_id, alias}
    public function alias()
    {
        try {
            $b = $this->body();
            $a = $this->master()->addAlias((int) ($b['place_id'] ?? 0), (string) ($b['alias'] ?? ''));
            return $this->json(['status' => 200, 'message' => '"' . $a['alias'] . '" now points at this place.', 'data' => $a]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/entrance_master/alias/(:num)/off — taken off, kept
    public function aliasOff($id)
    {
        try {
            $a = $this->master()->removeAlias((int) $id);
            return $this->json(['status' => 200, 'message' => '"' . $a['alias'] . '" taken off.', 'data' => $a]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
