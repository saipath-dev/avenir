<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\GoogleDistance;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\MileageAllowances;
use App\Libraries\TourFlow\TransportRates;

/**
 * Two Master Files screens, kept apart because they answer different questions:
 *
 *   master_files/transport_rates      what a kilometre costs, by vehicle size, with history
 *   master_files/mileage_allowances   how many kilometres a stay day, the garage run and
 *                                     a city tour are charged at
 *
 * One menu URL = one privilege (UserPermissionFilter), the rule the rest of Master Files
 * uses. Everything here is typed by a person; nothing updates a figure on its own.
 */
class TransportController extends BaseController
{
    public const RATES_URL      = 'master_files/transport_rates';
    public const ALLOWANCES_URL = 'master_files/mileage_allowances';

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

    private function ratesSvc(): TransportRates
    {
        return new TransportRates($this->db(), $this->user());
    }

    private function tours(): \App\Libraries\TourFlow\TourService
    {
        $db = $this->db();
        return new \App\Libraries\TourFlow\TourService($db, $this->user(), new \App\Libraries\TourFlow\IntakeService($db, $this->user(), new \App\Libraries\HotelRates\HotelRateService($db, $this->user())));
    }

    private function allowancesSvc(): MileageAllowances
    {
        return new MileageAllowances($this->db(), $this->user());
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

    /** Whether the signed-in group holds a menu URL — the same privileges the sidebar reads; the administrator holds all. */
    private function holdsMenu(string $url): bool
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

    private function installed(): bool
    {
        try {
            $v = Installer::installedVersion($this->db());
            return $v !== null && $v >= Installer::SCHEMA_VERSION;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // =====================================================================
    //  Transport Rates
    // =====================================================================

    // GET master_files/transport_rates
    public function rates()
    {
        $data = $this->menuData();
        $data['user']           = $this->user();
        $data['rates_url']      = self::RATES_URL;
        $data['allowances_url'] = self::ALLOWANCES_URL;
        $data['intake_url']     = 'tour/intake';
        $data['installed']      = $this->installed();
        // tf-masters-r3: the fleet list links to the Vehicle master only for a sign-in that may open it
        $data['can_vehicle_master'] = $this->holdsMenu('master_files/vehicle');
        $data['vehicle_master_url'] = 'master_files/vehicle';
        return view('pages/master_files/transport_rates', $data);
    }

    // GET master_files/transport_rates/fleet — the vehicle master as the Transport desk sees it, read-only (tf-masters-r3)
    public function fleet()
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->tours()->vehicles()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET master_files/transport_rates/board
    public function board()
    {
        try {
            $r = $this->ratesSvc();
            $board = $r->board();
            // Transport & Guide Services (Shantha, 5 Oct 2026): each class says how many people on the Driver & Guide Master
            // supply a vehicle in it — their general rate is this class's — and links to them
            $counts = $this->crewByClass();
            foreach ($board as &$b) {
                $b['crew'] = $counts[(string) $b['code']] ?? 0;
            }
            unset($b);
            return $this->json(['status' => 200, 'data' => ['board' => $board, 'stale' => $r->staleOpen(), 'crew_url' => 'master_files/crew_people', 'crew_master' => $this->holdsMenu('master_files/crew_people')]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /** Active people on the Driver & Guide Master by the rate class of the vehicle they normally supply (none before schema 44). */
    private function crewByClass(): array
    {
        $out = [];
        try {
            if (!$this->db()->query("SHOW TABLES LIKE 'tf_crew_vehicle'")->getResultArray()) {
                return $out;
            }
            foreach ($this->db()->query("SELECT v.rate_class, COUNT(DISTINCT v.person_id) n FROM tf_crew_vehicle v JOIN tf_crew_person p ON p.id = v.person_id
                                         WHERE v.is_current = 1 AND v.rate_class IS NOT NULL AND v.rate_class <> '' AND p.status = 'active' AND p.archived_at IS NULL GROUP BY v.rate_class")->getResultArray() as $row) {
                $out[(string) $row['rate_class']] = (int) $row['n'];
            }
        } catch (\Throwable $e) {
            // the master not there yet
        }
        return $out;
    }

    // GET master_files/transport_rates/history/(:segment)
    public function history($code)
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->ratesSvc()->history((string) $code)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/transport_rates/rate {class_code, rate_km, currency, valid_from, reason}
    public function setRate()
    {
        try {
            $b   = $this->body();
            $row = $this->ratesSvc()->set(
                (string) ($b['class_code'] ?? ''), $b['rate_km'] ?? null, (string) ($b['currency'] ?? 'LKR'),
                $b['valid_from'] ?? null, $b['reason'] ?? null
            );
            $lane = ($row['currency'] ?? '') === 'LKR' ? 'LKR driver payment rate' : 'USD quotation rate';   // tf-kmrate-r1
            return $this->json(['status' => 200, 'message' => $lane . ' saved. It applies from ' . $row['valid_from'] . '; the one before it is kept in the history.' . (!empty($row['reinstated']) ? ' The size was retired — it is back in use.' : ''), 'data' => $this->ratesSvc()->board()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/transport_rates/rate/(:num)/correct {rate_km, reason}
    public function correct($id)
    {
        try {
            $b   = $this->body();
            $row = $this->ratesSvc()->correct((int) $id, $b['rate_km'] ?? null, $b['reason'] ?? null);
            $msg = 'Corrected and logged as a correction.';
            if ((int) ($row['used_on'] ?? 0) > 0) {
                $msg .= ' ' . (int) $row['used_on'] . ' inquiry line(s) had already copied the old figure and keep it; re-price them from the inquiry if that is wanted.';
            }
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $this->ratesSvc()->board()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/transport_rates/class/(:segment)/active {active}
    public function active($code)
    {
        try {
            $b = $this->body();
            $this->ratesSvc()->setActive((string) $code, !empty($b['active']));
            return $this->json(['status' => 200, 'message' => !empty($b['active']) ? 'Size offered again.' : 'Size retired. Past quotations are untouched.', 'data' => $this->ratesSvc()->board()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/transport_rates/class/(:segment)/max_pax {max_pax} — how many a size carries on the ladder (schema 31)
    public function maxPax($code)
    {
        try {
            $b = $this->body();
            $board = $this->ratesSvc()->setMaxPax((string) $code, $b['max_pax'] ?? null);
            return $this->json(['status' => 200, 'message' => 'Saved. New vehicle choices and the pax matrix follow the new band; vehicles already on inquiries keep what they have.', 'data' => $board]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // =====================================================================
    //  Mileage Allowances
    // =====================================================================

    // GET master_files/mileage_allowances
    public function allowances()
    {
        $data = $this->menuData();
        $data['user']           = $this->user();
        $data['rates_url']      = self::RATES_URL;
        $data['allowances_url'] = self::ALLOWANCES_URL;
        $data['intake_url']     = 'tour/intake';
        $data['installed']      = $this->installed();
        return view('pages/master_files/mileage_allowances', $data);
    }

    // GET master_files/mileage_allowances/data
    public function allowanceData()
    {
        try {
            $a = $this->allowancesSvc();
            return $this->json(['status' => 200, 'data' => ['fixed' => $a->all(), 'cities' => $a->cities(), 'has_city_master' => $a->hasCityMaster(), 'quote' => $this->quoteData(), 'route' => $this->routeData()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /** Which figure the quotation is costed on, and whether Google can be asked at all. */
    private function quoteData(): array
    {
        $db  = $this->db();
        $src = 'google';
        try {
            $r = $db->query("SELECT v FROM tf_meta WHERE k = 'mileage_quote_source'")->getRowArray();
            $src = strtolower(trim((string) ($r['v'] ?? ''))) === 'actual' ? 'actual' : 'google';
        } catch (\Throwable $e) {
            // no setting yet: Google, as the installer seeds it
        }
        $g = new GoogleDistance($db);
        return ['source' => $src, 'google_key' => $g->hasKey(), 'key_problem' => $g->keyProblem(), 'cached' => $g->cachedCount(), 'table' => $g->available()];
    }

    // POST master_files/mileage_allowances/source {source: google|actual}
    public function quoteSource()
    {
        try {
            $b   = $this->body();
            $src = strtolower(trim((string) ($b['source'] ?? '')));
            if (!in_array($src, ['google', 'actual'], true)) {
                throw new \InvalidArgumentException('Choose Google\'s road distance or the driven record.');
            }
            $this->db()->query("INSERT INTO tf_meta (k, v) VALUES ('mileage_quote_source', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [$src]);
            return $this->json(['status' => 200,
                'message' => ($src === 'google' ? 'Quotations are costed on Google\'s road distance from now on.' : 'Quotations are costed on the driven record from now on.')
                    . ' An inquiry already planned keeps its figures until it is planned again or Ask Google is pressed on it.',
                'data' => $this->quoteData()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/mileage_allowances/google_test {from, to} — one live lookup, to see the key works
    public function googleTest()
    {
        try {
            $b    = $this->body();
            $from = trim((string) ($b['from'] ?? ''));
            $to   = trim((string) ($b['to'] ?? ''));
            if ($from === '' || $to === '') {
                throw new \InvalidArgumentException('Name both places to look up.');
            }
            $g = new GoogleDistance($this->db());
            if (!$g->hasKey()) {
                throw new \RuntimeException((string) $g->keyProblem());
            }
            $r = $g->distance($from, $to, true);
            return $this->json(['status' => $r['by'] === 'google' ? 200 : 400, 'message' => $r['why'], 'data' => $r + ['cached' => $g->cachedCount()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/mileage_allowances/allowance/(:segment) {km, note, active}
    public function saveAllowance($code)
    {
        try {
            $b = $this->body();
            $this->allowancesSvc()->save((string) $code, $b['km'] ?? null, $b['note'] ?? null, array_key_exists('active', $b) ? !empty($b['active']) : null);
            return $this->json(['status' => 200, 'message' => 'Allowance saved. New inquiries use it from now; nothing already priced changes.', 'data' => $this->allowancesSvc()->all()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * tf-garage-r1: where a tour starts and ends. At the airport (the company's way since Nov 2026): the vehicle's day begins
     * at the airport, the garage run is the only running added (Garage → airport at the start, airport → Garage at the end,
     * the garage figure above each), and there is no positioning leg from Colombo. Otherwise the older plan: Garage → Colombo,
     * then Colombo → airport to collect the party.
     */
    private function routeData(): array
    {
        $get = function (string $k, string $d): string {
            try {
                $r = $this->db()->query('SELECT v FROM tf_meta WHERE k = ?', [$k])->getRowArray();
                return $r ? (string) $r['v'] : $d;
            } catch (\Throwable $e) {
                return $d;
            }
        };
        $base = trim($get('vehicle_base', 'Colombo'));
        $pos  = trim($get('position_on_arrival', '1')) === '1';
        return ['start_at_airport' => $base === '' && !$pos, 'vehicle_base' => $base, 'position_on_arrival' => $pos, 'gateway' => trim($get('gateway', 'Katunayake')) ?: 'Katunayake'];
    }

    // POST master_files/mileage_allowances/route {start_at_airport: 1 | 0}
    public function saveRoute()
    {
        try {
            $b   = $this->body();
            $on  = !empty($b['start_at_airport']) && (string) $b['start_at_airport'] !== '0';
            $was = $this->routeData();
            $db  = $this->db();
            foreach (['vehicle_base' => $on ? '' : 'Colombo', 'position_on_arrival' => $on ? '0' : '1'] as $k => $v) {
                $db->query('INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
            }
            if ($was['start_at_airport'] !== $on) {
                \App\Libraries\TourFlow\Activity::master($db, $this->user(), 'mileage_allowance', 0, 'Mileage Allowances: tours ' . ($on ? 'start and end at the airport (garage run only)' : 'start from Colombo (garage → Colombo → airport)'), [
                    ['what' => 'tour start', 'from' => $was['start_at_airport'] ? 'airport' : 'Colombo', 'to' => $on ? 'airport' : 'Colombo'],
                ]);
            }
            return $this->json(['status' => 200, 'message' => ($on ? 'Tours now start and end at the airport: Garage → airport and airport → Garage, the garage figure each way.' : 'Tours start from Colombo again: Garage → Colombo, then Colombo → airport.') . ' New inquiries and re-read mileage use it; a leg typed by hand is never touched.', 'data' => $this->routeData()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/mileage_allowances/city          {city, km}
    // POST master_files/mileage_allowances/city/(:num)   {city, km} or {delete: 1}
    public function city($id = null)
    {
        try {
            $b = $this->body();
            $a = $this->allowancesSvc();
            if ($id !== null && !empty($b['delete'])) {
                return $this->json(['status' => 200, 'message' => 'City removed from the list.', 'data' => $a->deleteCity((int) $id)]);
            }
            $cities = $a->saveCity($id === null ? null : (int) $id, (string) ($b['city'] ?? ''), $b['km'] ?? null);
            return $this->json(['status' => 200, 'message' => $id === null ? 'City added.' : 'City allowance saved.', 'data' => $cities]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
