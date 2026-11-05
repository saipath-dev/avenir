<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\IntakeService;
use App\Libraries\TourFlow\ProgrammeMasters;
use App\Libraries\TourFlow\Staff;

/**
 * Master Files → Paid Activities & Services — master_files/paid_services (schema 29)
 *
 * Everything that costs money on a day, by destination — Sigiriya: the rock entrance, the
 * village tour, the Minneriya jeep safari; Kandy: the Temple of the Tooth, the Kandyan dance;
 * Polonnaruwa and Anuradhapura: the ancient-city entrances — each with its rate structure:
 *
 *   Entrances, tickets and what is priced a head — the company's priced list (entrance_fee),
 *   USD a head and who settles it. Read as it is: its entries are added and changed under
 *   Master Files → Entrance Fee, which opens their accounts posting. This screen keeps only
 *   TourFlow's own word on an entry — the destination it sits under, and whether it is still
 *   offered on the day programme and matched (tf_fee_meta).
 *
 *   Activities and excursions — per person, per group or per vehicle, with or without
 *   transport, with a validity (tf_excursion_rate): added, changed and switched off here.
 *
 * Put on a day of an inquiry, a paid item pulls its rate and goes straight to the costing.
 * A destination is a person's word: an entry sits under one only once somebody puts it there,
 * its address shown beside it and never read for a town. Nothing is deleted from here.
 *
 * One menu URL = one privilege (UserPermissionFilter). Granted to the System Administrator by
 * the installer and to the quotation desk and Management by the desks' grant
 * (Desks::TOUR_DESK_V7); Admin grants it further under Group Privileges.
 */
class PaidServicesController extends BaseController
{
    public const URL          = 'master_files/paid_services';
    public const FEE_URL      = 'master_files/entrance_fee';
    public const FREE_URL     = 'master_files/free_visits';

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

    private function actorName(): string
    {
        return Staff::displayName($this->db(), $this->userId(), $this->user());
    }

    private function masters(): ProgrammeMasters
    {
        return new ProgrammeMasters($this->db(), $this->user(), $this->userId(), $this->actorName());
    }

    private function svc(): IntakeService
    {
        return (new IntakeService($this->db(), $this->user(), new HotelRateService($this->db(), $this->user())))->withActor($this->userId(), $this->actorName());
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

    // GET master_files/paid_services
    public function index()
    {
        $data = $this->menuData();
        $data['user']       = $this->user();
        $data['page_url']   = self::URL;
        $data['intake_url'] = 'tour/intake';
        $data['fee_url']    = self::FEE_URL;
        $data['sees_fee']   = $this->holds(self::FEE_URL);
        $data['free_url']   = self::FREE_URL;
        $data['sees_free']  = $this->holds(self::FREE_URL);
        // schema 39: the Place to Visit / Entrance Fee Master — where an entrance's rate is kept
        $data['master_url']  = \App\Libraries\TourFlow\EntranceMaster::URL;
        $data['sees_master'] = $this->holds(\App\Libraries\TourFlow\EntranceMaster::URL);
        $data['master_on']   = false;
        try {
            $data['master_on'] = \App\Libraries\TourFlow\EntranceMaster::installed($this->db());
        } catch (\Throwable $e) {
            $data['master_on'] = false;
        }
        $data['installed']  = false;
        try {
            $v = Installer::installedVersion($this->db());
            $data['installed'] = $v !== null && $v >= Installer::SCHEMA_VERSION;
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/master_files/paid_services', $data);
    }

    // GET master_files/paid_services/data — the entrances and the activities, each with its destination, every destination known, the units
    public function data()
    {
        try {
            $m = $this->masters();
            return $this->json(['status' => 200, 'data' => [
                'entrances'    => $m->entrances(null, true),
                'activities'   => $m->activities(null, true),
                'destinations' => $m->destinations(),
                'units'        => ['per_person' => 'per person', 'per_group' => 'per group', 'per_vehicle' => 'per vehicle'],
                'settled'      => ['in_tour' => 'in the tour', 'prepaid' => 'prepaid', 'direct' => 'paid direct by the clients'],
                'available'    => $m->available(),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/paid_services/entrance/(:num) {destination?, active?} — TourFlow's own word on an entry; the entry itself is Master Files → Entrance Fee
    public function entrance($id)
    {
        try {
            // schema 39: an entrance or a safari is kept in the Place to Visit / Entrance Fee Master alone — the library refuses it here
            $row = $this->masters()->placeEntrance((int) $id, $this->body());
            return $this->json(['status' => 200, 'message' => 'Saved.' . (!$row['active'] ? ' It is no longer offered on the day programme or matched; the lines already priced from it keep it.' : ''), 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/paid_services/activity {code, name, location, unit, rate, currency, includes_transport, valid_from, valid_to, note, active}
    public function activity()
    {
        try {
            $row = $this->masters()->saveActivity($this->body(), $this->svc());
            return $this->json(['status' => 200, 'message' => 'Saved. A quotation that already carries this activity keeps the rate it was given; new ones take this one.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/paid_services/activity/delete/(:segment) — switched off and kept, never deleted; switched on again by Edit → Active
    public function activityDelete($code)
    {
        try {
            $r = $this->masters()->deleteActivity((string) $code, $this->svc());
            return $this->json(['status' => 200, 'message' => 'Switched off and kept' . ($r['used'] ? ' — the ' . $r['used'] . ' quotation line(s) that carry it keep their copied figures' : '') . '. Tick "show switched off" to see it and switch it on again.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
