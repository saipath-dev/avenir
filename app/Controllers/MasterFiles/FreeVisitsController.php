<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\ProgrammeMasters;
use App\Libraries\TourFlow\Staff;

/**
 * Master Files → Free Visits — master_files/free_visits (schema 29)
 *
 * Each destination's normal sightseeing and en-route stops: a walk round the Galle Fort
 * ramparts, a photo stop at the Ella Gap, a spice garden on the way to Kandy. Nothing on
 * this screen asks for or carries a cost — there is no field for one. A free visit put on a
 * day of an inquiry is on the programme and in the itinerary the client reads, and never in
 * the costing. Anything with a rate belongs under Paid Activities & Services. The destination
 * is a person's word (the ones copied from past inquiries have none until given one), and
 * nothing is deleted: taking a visit off switches it off and keeps it.
 *
 * One menu URL = one privilege (UserPermissionFilter). Granted to the System Administrator by
 * the installer and to the quotation desk and Management by the desks' grant
 * (Desks::TOUR_DESK_V7); Admin grants it further under Group Privileges.
 */
class FreeVisitsController extends BaseController
{
    public const URL      = 'master_files/free_visits';
    public const PAID_URL = 'master_files/paid_services';

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

    private function masters(): ProgrammeMasters
    {
        return new ProgrammeMasters($this->db(), $this->user(), $this->userId(), Staff::displayName($this->db(), $this->userId(), $this->user()));
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

    // GET master_files/free_visits
    public function index()
    {
        $data = $this->menuData();
        $data['user']       = $this->user();
        $data['page_url']   = self::URL;
        $data['intake_url'] = 'tour/intake';
        $data['paid_url']   = self::PAID_URL;
        $data['sees_paid']  = $this->holds(self::PAID_URL);
        $data['installed']  = false;
        try {
            $v = Installer::installedVersion($this->db());
            $data['installed'] = $v !== null && $v >= Installer::SCHEMA_VERSION;
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/master_files/free_visits', $data);
    }

    // GET master_files/free_visits/data[?all=1] — every free visit by destination (the switched-off ones too with all=1), and every destination the masters know
    public function data()
    {
        try {
            $m = $this->masters();
            return $this->json(['status' => 200, 'data' => [
                'visits'       => $m->freeVisits(null, (string) $this->request->getGet('all') === '1'),
                'destinations' => $m->destinations(),
                'kinds'        => ProgrammeMasters::FREE_KIND_WORDS,
                'available'    => $m->available(),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/free_visits/save {id?, destination, name, kind, description, active} — no cost can be given
    public function save()
    {
        try {
            $row = $this->masters()->saveFreeVisit($this->body());
            return $this->json(['status' => 200, 'message' => 'Saved. It can be put on any day from the inquiry\'s day programme; it is never costed.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/free_visits/delete/(:num) — switched off and kept, never deleted; switched on again by Edit → Active
    public function delete($id)
    {
        try {
            $r = $this->masters()->deleteFreeVisit((int) $id);
            return $this->json(['status' => 200, 'message' => 'Switched off and kept' . ($r['used'] ? ' — the ' . $r['used'] . ' day line(s) that carry it keep it as written' : '') . '. Tick "show switched off" to see it and switch it on again.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
