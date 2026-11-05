<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\CrewPeople;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\Staff;

/**
 * Master Files → Drivers & Guides: the Driver & Guide Master (schema 44, tf-crew-master-r1), grown from the
 * Drivers & Guides master of schema 36. One screen, four tabs — All, JSD, JSCG, JSNTG — each with its own Add;
 * the record with its personal details, garage, licences and copies, the vehicle normally supplied with its
 * documents and photographs, the LKR-per-km rate with its history, and the bank details for the holders of
 * "Driver & Guide bank details" (master_files/crew_people/bank — one menu URL, one privilege). Files are kept
 * outside the web root and served here to signed-in users only.
 *
 * Reached under master_files/crew_people (menu entry "Drivers & Guides"); the UserPermissionFilter guards it.
 */
class CrewPeopleController extends BaseController
{
    private const URL      = 'master_files/crew_people';
    private const BANK_URL = 'master_files/crew_people/bank';

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

    private function people(): CrewPeople
    {
        return new CrewPeople($this->db(), $this->user(), $this->userId(), $this->actorName());
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

    /** The bank privilege: the System Administrator (group 1) always; otherwise the menu URL granted to the group. */
    private function canBank(): bool
    {
        $m = $this->menuData();
        if ((int) ($m['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ($m['allowed_menu_list'] ?? [] as $row) {
            if (trim((string) ($row['url'] ?? ''), '/') === self::BANK_URL) {
                return true;
            }
        }
        return false;
    }

    /** The standard bata, guide fee and overnight allowance are set by the System Administrator (group 1) only. */
    private function canStandards(): bool
    {
        return (int) ($this->menuData()['user_role'] ?? 0) === 1;
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

    // GET master_files/crew_people
    public function index()
    {
        $data = $this->menuData();
        $data['user']       = $this->user();
        $data['page_url']   = self::URL;
        $data['intake_url'] = 'tour/intake';
        $data['installed']  = false;
        $data['master']     = false;
        $data['can_bank']   = $this->canBank();
        try {
            $v = Installer::installedVersion($this->db());
            $p = $this->people();
            $data['installed'] = $v !== null && $v >= 36 && $p->available();
            $data['master']    = $data['installed'] && $p->masterReady();
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/master_files/crew_people', $data);
    }

    // GET master_files/crew_people/data?archived=1 — every person (the archived too when asked), the categories, the ledger
    public function data()
    {
        try {
            $p = $this->people();
            $withArchived = (string) $this->request->getGet('archived') === '1';
            $bank = $this->canBank();
            // the archived only behind "show archived"; the inactive stay on the list, dimmed (they are never offered on a tour)
            $people = $p->all(null, false, null, null, $bank, false);
            if (!$withArchived) {
                $people = array_values(array_filter($people, static fn ($x) => empty($x['archived'])));
            }
            $counts = ['ALL' => 0, 'JSD' => 0, 'JSCG' => 0, 'JSNTG' => 0, 'NONE' => 0, 'to_confirm' => 0];
            foreach ($people as $x) {
                if ($x['archived'] || $x['status'] !== 'active') {
                    continue;
                }
                $counts['ALL']++;
                $counts[$x['category'] ?? 'NONE']++;
                if (!$x['category_confirmed']) {
                    $counts['to_confirm']++;
                }
            }
            return $this->json(['status' => 200, 'data' => [
                'people'     => $people,
                'counts'     => $counts,
                'roles'      => array_values($p->roles()),
                'categories' => CrewPeople::categories(),
                'vehicle_types' => CrewPeople::VEHICLE_TYPES, 'roof_types' => CrewPeople::ROOF_TYPES, 'ownership' => CrewPeople::OWNERSHIP,
                // the Transport Rates master's vehicle classes with their GENERAL rate per km today (a driver's special rate overrides it)
                'rate_classes' => array_values($p->rateClasses()),
                // the STANDARD bata (JSD / JSCG), guide fee (JSNTG) and overnight allowance (JSNTG), each with the date it took
                // effect and its history; a person's own agreed figure overrides the standard for them only
                'standards'  => $p->standardsSummary(),
                'standard_history' => $p->standardHistory(),
                'can_standards' => $this->canStandards(),
                'ledger'     => $p->ledger(),
                'can_bank'   => $bank,
                'master'     => $p->masterReady(),
                'upload_mb'  => CrewPeople::MAX_FILE_MB,
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET master_files/crew_people/person/(:num) — one record in full: vehicle, photos, rates, warnings, history
    public function person($id)
    {
        try {
            $p = $this->people();
            $row = $p->get((int) $id, $this->canBank());
            if (!$row) {
                return $this->json(['status' => 404, 'message' => 'No such person.']);
            }
            $row['history']  = $p->history((int) $id);
            $row['vehicles'] = $p->vehicles((int) $id);
            return $this->json(['status' => 200, 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_people/save[/(:num)] — the person (bank fields only from the privilege)
    public function save($id = null)
    {
        try {
            $row = $this->people()->save($this->body(), $id !== null ? (int) $id : null, $this->canBank());
            return $this->json(['status' => 200, 'message' => $row['name'] . ' saved.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_people/confirm/(:num) {category} — the proposed category confirmed or changed
    public function confirm($id)
    {
        try {
            $row = $this->people()->confirmCategory((int) $id, (string) ($this->body()['category'] ?? ''));
            return $this->json(['status' => 200, 'message' => $row['name'] . ' confirmed as ' . $row['category'] . '.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_people/archive/(:num) {note} — kept, never deleted; the tours they worked keep their name
    public function archive($id)
    {
        try {
            $row = $this->people()->archive((int) $id, $this->body()['note'] ?? null);
            return $this->json(['status' => 200, 'message' => $row['name'] . ' archived. Tick "show archived" to see them and restore.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_people/restore/(:num)
    public function restore($id)
    {
        try {
            $row = $this->people()->restore((int) $id);
            return $this->json(['status' => 200, 'message' => $row['name'] . ' is back on the master.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_people/vehicle/(:num)[/(:num)] — the vehicle normally supplied (new, or a change to one)
    public function vehicle($personId, $vehicleId = null)
    {
        try {
            $v = $this->people()->saveVehicle((int) $personId, $this->body(), $vehicleId !== null ? (int) $vehicleId : null);
            return $this->json(['status' => 200, 'message' => 'Vehicle ' . ($v['reg_no'] ?: '') . ' saved.', 'data' => $v]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_people/rate/(:num) {rate_km, effective_from, note} — a new LKR rate per km from a date
    public function rate($personId)
    {
        try {
            $b = $this->body();
            $rates = $this->people()->addRate((int) $personId, $b['rate_km'] ?? null, $b['effective_from'] ?? null, $b['note'] ?? null);
            return $this->json(['status' => 200, 'message' => 'Rate saved; the earlier rates stay on the record.', 'data' => $rates]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_people/bata/(:num) {bata, overnight, effective_from, note} — a new agreed bata (guide rate / allowance) from a date
    public function bata($personId)
    {
        try {
            $b = $this->body();
            $rows = $this->people()->addBata((int) $personId, $b['bata'] ?? null, $b['overnight'] ?? null, $b['effective_from'] ?? null, $b['note'] ?? null);
            return $this->json(['status' => 200, 'message' => 'Agreed figure saved; the earlier ones stay on the record.', 'data' => $rows]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_people/standard/(:segment) {amount, effective_from, note} — a new standard figure from a date
    // (bata | guide_fee | overnight), System Administrator only; the earlier figures stay, no tour moves
    public function standard($item)
    {
        try {
            if (!$this->canStandards()) {
                return $this->json(['status' => 403, 'message' => 'Only the System Administrator sets the standard figures.'], 403);
            }
            $b = $this->body();
            $p = $this->people();
            $item = strtolower(trim((string) $item));
            $rows = $p->addStandard($item, $b['amount'] ?? null, $b['effective_from'] ?? null, $b['note'] ?? null, !empty($b['withdraw']));
            $name = CrewPeople::STANDARD_ITEMS[$item]['name'] ?? 'Standard figure';
            return $this->json(['status' => 200, 'message' => $name . ' saved from ' . ($rows[0]['effective_from'] ?? '') . '; the earlier figures stay, and no tour already allocated moves.',
                'data' => ['rows' => $rows, 'standards' => $p->standardsSummary(), 'standard_history' => $p->standardHistory()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * POST master_files/crew_people/upload/(:num)/(:segment) — a multipart file: kind = photo | licence | guide_licence |
     * vehicle_insurance | vehicle_licence | vehicle_photo (with view = exterior | interior | other, vehicle_id optional).
     */
    public function upload($personId, $kind)
    {
        try {
            $f = $this->request->getFile('file');
            if (!$f || !$f->isValid()) {
                $why = $f && method_exists($f, 'getErrorString') ? $f->getErrorString() : 'no file arrived';
                return $this->json(['status' => 400, 'message' => 'The file did not arrive whole (' . $why . ').']);
            }
            $tmp  = method_exists($f, 'getTempName') ? (string) $f->getTempName() : (string) $f->getPathname();
            $name = method_exists($f, 'getClientName') ? (string) $f->getClientName() : (string) $f->getName();
            $opts = ['view' => $this->request->getPost('view'), 'caption' => $this->request->getPost('caption'), 'vehicle_id' => $this->request->getPost('vehicle_id')];
            $r = $this->people()->storeFile((int) $personId, (string) $kind, $tmp, $name, $opts);
            return $this->json(['status' => 200, 'message' => 'File stored.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/crew_people/photo/(:num)/(:num)/remove — a vehicle photograph taken off
    public function removePhoto($personId, $photoId)
    {
        try {
            $this->people()->removeVehiclePhoto((int) $personId, (int) $photoId);
            return $this->json(['status' => 200, 'message' => 'Photograph removed.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * GET master_files/crew_people/file/(:num)/(:segment)[/(:num)] — a stored file, to signed-in users who hold the
     * master. The files sit outside the web root; nothing is served by path.
     */
    public function file($personId, $kind, $id = null)
    {
        $f = $this->people()->fileFor((int) $personId, (string) $kind, $id !== null ? (int) $id : null);
        if (!$f || !$f['exists']) {
            return $this->response->setStatusCode(404)->setBody($f ? 'The file is not on the server (' . $f['name'] . ').' : 'No such file.');
        }
        return $this->response->setContentType($f['type'])->setHeader('Content-Disposition', 'inline; filename="' . $f['name'] . '"')->setHeader('Cache-Control', 'private, max-age=300')->setBody((string) file_get_contents($f['path']));
    }
}
