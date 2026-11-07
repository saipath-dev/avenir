<?php

namespace App\Controllers;

use App\Libraries\HomeDesks;
use App\Libraries\MenuLoader;

/**
 * tf-home-r1 (22 Oct 2026) — Home: the Avenir Holidays Handling System dashboard with the five desks.
 * Open to every signed-in user (like the Pilot page); what each desk lists is decided by the user's own privileges
 * (HomeDesks::forUser). Read-only: it changes nothing. Drivers keep their own portal.
 * tf-home-r4: the tall panel uses the painting on a taller canvas (the painting whole, the night sky extended above and
 * below it); the original file stays and is used if the tall one is missing.
 */
class HomeDeskController extends BaseController
{
    public function index()
    {
        if ((int) session()->get('group_id') === 24) {
            return redirect()->to(base_url('driver_portal'));
        }
        $data = (new MenuLoader())->loadMenuData();
        $data['home'] = HomeDesks::forUser($data);
        // tf-stage2: today's USD rate for the Home box, and whether this person holds the right to enter it
        try {
            $db = \Config\Database::connect();
            $data['usd'] = \App\Libraries\TourFlow\UsdRate::status($db);
            $data['usd_may'] = (int) session()->get('group_id') === 1;
            foreach ((array) ($data['allowed_menu_list'] ?? []) as $m) {
                if (trim((string) (is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '')), '/ ') === \App\Libraries\TourFlow\UsdRate::URL) {
                    $data['usd_may'] = true;
                }
            }
        } catch (\Throwable $e) {
            $data['usd'] = null;
            $data['usd_may'] = false;
        }
        $tall = 'assets/images/home/office-painting-sun-moon-tall.jpg';
        $art = 'assets/images/home/office-painting-sun-moon.jpg';
        $data['home_painting_tall'] = is_file(FCPATH . $tall);
        $use = $data['home_painting_tall'] ? $tall : $art;
        $data['home_painting'] = is_file(FCPATH . $use) ? base_url($use) . '?v=' . filemtime(FCPATH . $use) : null;
        return view('pages/home_desks', $data);
    }

    // =====================================================================
    //  tf-tourdesk-r1 (25 Oct 2026) — the Tour Desk page: five illustrated tiles that open the existing TourFlow pages, with
    //  the next month's tours and the next seven days' arrivals read from the records as they stand. Demonstration build:
    //  read-only, nothing written, the tiles and the calendars shown only to a sign-in that may open the pages behind them.
    // =====================================================================

    /** The Tour Desk page: home/tour_desk */
    // tf-stage2: POST home/usd_rate/save {rate, reason} — today's rate from the Home box (the "Enter the daily USD rate" right);
    // GET home/usd_rate/status — the box's figures (anyone signed in)
    public function usdRateSave()
    {
        try {
            $menu = (new MenuLoader())->loadMenuData();
            $may = (int) session()->get('group_id') === 1;
            foreach ((array) ($menu['allowed_menu_list'] ?? []) as $m) {
                if (trim((string) (is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '')), '/ ') === \App\Libraries\TourFlow\UsdRate::URL) {
                    $may = true;
                }
            }
            if (!$may) {
                return $this->response->setStatusCode(403)->setJSON(['status' => 403, 'message' => 'Entering the daily USD rate needs the "Enter the daily USD rate" right (Settings → Group Privileges).']);
            }
            $b = $this->request->getJSON(true) ?: $this->request->getPost();
            $db = \Config\Database::connect();
            $name = \App\Libraries\TourFlow\Staff::displayName($db, (int) session()->get('user_id') ?: null, (string) session()->get('username'));
            $r = \App\Libraries\TourFlow\UsdRate::save($db, date('Y-m-d'), $b['rate'] ?? null, $b['reason'] ?? null, (string) session()->get('username'), $name, 'home');
            return $this->response->setJSON(['status' => 200, 'message' => $r['message'], 'kind' => $r['kind'], 'data' => \App\Libraries\TourFlow\UsdRate::status($db)]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 500, 'message' => 'The rate could not be saved: ' . $e->getMessage()]);
        }
    }

    public function usdRateStatus()
    {
        try {
            return $this->response->setJSON(['status' => 200, 'data' => \App\Libraries\TourFlow\UsdRate::status(\Config\Database::connect())]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function tourDesk()
    {
        if ((int) session()->get('group_id') === 24) {
            return redirect()->to(base_url('driver_portal'));
        }
        $data = (new MenuLoader())->loadMenuData();
        $home = HomeDesks::forUser($data);
        $data['td'] = self::tourDeskTiles($home);
        $data['td_can_calendar'] = $data['td']['can_calendar'];
        return view('pages/tour_desk', $data);
    }

    /** tf-tourdesk-r2: Upcoming Tours — the next one month as a calendar and day by day (home/tour_desk/upcoming) */
    public function tourDeskUpcoming()
    {
        return $this->tourDeskDetail('upcoming');
    }

    /** tf-tourdesk-r2: Next Arrivals — the next seven days with flight, time, tour number, pax, driver and vehicle (home/tour_desk/arrivals) */
    public function tourDeskArrivals()
    {
        return $this->tourDeskDetail('arrivals');
    }

    private function tourDeskDetail(string $mode)
    {
        if ((int) session()->get('group_id') === 24) {
            return redirect()->to(base_url('driver_portal'));
        }
        $data = (new MenuLoader())->loadMenuData();
        $home = HomeDesks::forUser($data);
        $td = self::tourDeskTiles($home);
        $rd = self::reservationDeskTiles($home);   // tf-resdesk-r1: the Reservation Desk opens the same two views
        $xd = self::transportDeskTiles($home);     // tf-transdesk-r1: and the Transport Operations Desk
        $tile = $td['tiles'][$mode === 'arrivals' ? 4 : 3];
        $data['mode'] = $mode;
        $data['td_can_calendar'] = $tile['ok'] || $rd['can_calendar'] || $xd['can_calendar'];
        $data['td_open'] = $tile['ok'] ? ($tile['behind'] ?? null) : null;
        $desk = (string) $this->request->getGet('desk');
        $data['td_home'] = ['url' => 'home/tour_desk', 'label' => 'Tour Desk'];
        if ($desk === 'reservation' && $rd['can_calendar']) {
            $data['td_home'] = ['url' => 'home/reservation_desk', 'label' => 'Reservation Desk'];
        } elseif ($desk === 'transport' && $xd['can_calendar']) {
            $data['td_home'] = ['url' => 'home/transport_desk', 'label' => 'Transport Desk'];
        }
        return view('pages/tour_desk_detail', $data);
    }

    /** JSON: the next month's tours by day and the next seven days' arrivals (home/tour_desk/data) */
    public function tourDeskData()
    {
        $menu = (new MenuLoader())->loadMenuData();
        $home = HomeDesks::forUser($menu);
        $td = self::tourDeskTiles($home);
        // tf-resdesk-r1: the Reservation Desk reads the same calendars, so a Reservation sign-in is answered too
        if (!$td['can_calendar'] && !self::reservationDeskTiles($home)['can_calendar'] && !self::transportDeskTiles($home)['can_calendar']) {   // tf-transdesk-r1: and the Transport Desk
            return $this->response->setJSON(['status' => 403, 'message' => 'The calendars show for a sign-in that may open the Tour Desk pages.']);
        }
        date_default_timezone_set('Asia/Colombo');
        $db = \Config\Database::connect();
        $today = date('Y-m-d');
        $toMonth = date('Y-m-d', strtotime($today . ' +30 days'));
        $toWeek = date('Y-m-d', strtotime($today . ' +6 days'));
        $rows = $db->query("SELECT c.tour_id, MAX(c.manual_id) AS manual_id, MAX(c.consignee) AS consignee, MIN(c.start_date) AS start_date, MAX(c.end_date) AS end_date,
                                   MAX(c.passenger_count) AS pax, MAX(c.arrival_flight) AS arrival_flight, MAX(c.arrival_flight_time) AS arrival_flight_time, MAX(c.agent_name) AS agent_name, MAX(c.country) AS country
                            FROM consignee c
                            WHERE c.start_date >= ? AND c.start_date <= ? AND c.verify = 1 AND c.is_template = 0 AND COALESCE(c.tour_cancel, 0) <> 1
                            GROUP BY c.tour_id ORDER BY MIN(c.start_date), MAX(c.manual_id)", [$today, $toMonth])->getResultArray();
        $ids = array_column($rows, 'tour_id');
        $drivers = [];
        if ($ids) {
            $in = implode(',', array_map(static fn ($v) => $db->escape((string) $v), $ids));
            // the latest allocation per tour; the driver's registered vehicle from the vehicle master (owner) or the driver's own record
            foreach ($db->query("SELECT da.tour_id, da.driver, da.driver_name, s.Name AS sup_name,
                                        COALESCE(NULLIF((SELECT GROUP_CONCAT(DISTINCT v.v_num ORDER BY v.v_num SEPARATOR ', ') FROM vehicle v WHERE v.owner = da.driver AND v.v_num <> ''), ''), NULLIF(s.v_num, '')) AS vehicle
                                 FROM driver_allocation da
                                 JOIN (SELECT tour_id, MAX(id) AS id FROM driver_allocation WHERE tour_id IN ({$in}) GROUP BY tour_id) l ON l.id = da.id
                                 LEFT JOIN supplier s ON s.Sup_Acc_No = da.driver")->getResultArray() as $d) {
                $drivers[(string) $d['tour_id']] = ['driver' => trim((string) ($d['sup_name'] ?: $d['driver_name'])), 'vehicle' => trim((string) ($d['vehicle'] ?? ''))];
            }
        }
        $byDay = [];
        $arrivals = [];
        foreach ($rows as $r) {
            $d = substr((string) $r['start_date'], 0, 10);
            $drv = $drivers[(string) $r['tour_id']] ?? ['driver' => '', 'vehicle' => ''];
            $item = ['manual_id' => trim((string) $r['manual_id']), 'client' => trim((string) $r['consignee']), 'start' => $d, 'end' => substr((string) $r['end_date'], 0, 10), 'pax' => (int) $r['pax'],
                'flight' => trim((string) $r['arrival_flight']), 'time' => trim((string) $r['arrival_flight_time']), 'agent' => trim((string) $r['agent_name']), 'country' => trim((string) $r['country']),
                'driver' => $drv['driver'], 'vehicle' => $drv['vehicle']];
            $byDay[$d][] = $item;
            if ($d <= $toWeek) {
                $arrivals[] = $item;
            }
        }
        return $this->response->setJSON(['status' => 200, 'today' => $today, 'month_to' => $toMonth, 'week_to' => $toWeek, 'by_day' => $byDay, 'arrivals' => $arrivals, 'tours' => count($rows)]);
    }

    // =====================================================================
    //  tf-resdesk-r1 (25 Oct 2026) — the Reservation Desk page (home/reservation_desk), to the approved Kandy artwork: six
    //  sections — Hotel Reservations & Bookings, Ticket Stock, Confirmed Tours, Upcoming Tours, Next Arrivals, Today at a
    //  Glance — each opening an EXISTING page or view. Read-only; the tiles follow the sign-in's own privileges.
    // =====================================================================

    public function reservationDesk()
    {
        if ((int) session()->get('group_id') === 24) {
            return redirect()->to(base_url('driver_portal'));
        }
        $data = (new MenuLoader())->loadMenuData();
        $home = HomeDesks::forUser($data);
        $data['rd'] = self::reservationDeskTiles($home);
        $data['td_can_calendar'] = $data['rd']['can_calendar'];
        // the other Reservation Desk pages the sign-in holds (hotel rates, incomplete work, room types, the earlier screens) — kept reachable below the tiles
        $data['rd_more'] = [];
        $tileUrls = array_column($data['rd']['tiles'], 'url');
        foreach ($home['desks'] as $d) {
            if (($d['key'] ?? '') !== 'reservation') {
                continue;
            }
            $walk = static function (array $items) use (&$walk, &$data, $tileUrls): void {
                foreach ($items as $it) {
                    if (isset($it['items'])) {
                        $walk($it['items']);
                    } elseif (!empty($it['url']) && !in_array(strtok((string) $it['url'], '?'), $tileUrls, true)
                        && !in_array($it['url'], ['master_files/hotel_rate_v2', 'master_files/room_type'], true)) {   // these two sit under Masters → Hotel Master
                        $data['rd_more'][] = ['url' => $it['url'], 'label' => $it['label'], 'icon' => $it['icon'] ?? 'bi-link-45deg'];
                    }
                }
            };
            $walk($d['items']);
        }
        return view('pages/reservation_desk', $data);
    }

    // =====================================================================
    //  tf-transdesk-r1 (25 Oct 2026) — the Transport Operations Desk page (home/transport_desk), to the approved hill-country
    //  artwork. Design stage: Transport Operations, Pending Allocations, Driver & Guide Handling, Railway Bookings (visual only:
    //  no booking page exists yet), Upcoming Tours, Route Maps & Mileage, Today at a Glance. Read-only; tiles follow the sign-in.
    // =====================================================================

    public function transportDesk()
    {
        if ((int) session()->get('group_id') === 24) {
            return redirect()->to(base_url('driver_portal'));
        }
        $data = (new MenuLoader())->loadMenuData();
        $home = HomeDesks::forUser($data);
        $data['xd'] = self::transportDeskTiles($home);
        $data['td_can_calendar'] = $data['xd']['can_calendar'];
        // the other Transport Desk pages the sign-in holds (ongoing tours, transport rates, crew roles) — kept reachable below the tiles
        $data['xd_more'] = [];
        $tileUrls = array_map(static fn ($t) => strtok((string) $t['url'], '?'), $data['xd']['tiles']);
        foreach ($home['desks'] as $d) {
            if (($d['key'] ?? '') !== 'transport') {
                continue;
            }
            $walk = static function (array $items) use (&$walk, &$data, $tileUrls): void {
                foreach ($items as $it) {
                    if (isset($it['items'])) {
                        $walk($it['items']);
                    } elseif (!empty($it['url']) && !in_array(strtok((string) $it['url'], '?'), $tileUrls, true)) {
                        $data['xd_more'][] = ['url' => $it['url'], 'label' => $it['label'], 'icon' => $it['icon'] ?? 'bi-link-45deg'];
                    }
                }
            };
            $walk($d['items']);
        }
        return view('pages/transport_desk', $data);
    }

    // =====================================================================
    //  tf-masters-r2 (25 Oct 2026) — Master Files in two levels, to the approved designs: five category cards (home/master_files),
    //  each opening its compact three-column page of buttons (home/master_files/{category}); every button an EXISTING master page
    //  the sign-in holds (the same rows the Master Files desk lists). Navigation only: no route, page, record or privilege changes.
    // =====================================================================

    /** Level one: the five category cards (home/master_files) */
    public function masterFiles()
    {
        if ((int) session()->get('group_id') === 24) {
            return redirect()->to(base_url('driver_portal'));
        }
        $data = (new MenuLoader())->loadMenuData();
        [$sections, $total] = self::masterFileSections(HomeDesks::forUser($data));
        $data['mf_sections'] = $sections;
        $data['mf_total'] = $total;
        // tf-stage3c: the record count each door tile shows (the approved mockup), read-only, each one best effort
        $db = \Config\Database::connect();
        $cnt = static function (string $sql) use ($db): ?int {
            try {
                return (int) ($db->query($sql)->getRowArray()['n'] ?? 0);
            } catch (\Throwable $e) {
                return null;
            }
        };
        $counts = [
            'customers'  => ['n' => $cnt('SELECT COUNT(*) n FROM customers'), 'label' => 'customers'],
            'hotels'     => ['n' => $cnt("SELECT COUNT(*) n FROM hr2_hotel WHERE status = 'active'"), 'label' => 'hotels'],
            'transport'  => ['n' => $cnt('SELECT COUNT(*) n FROM tf_crew_person'), 'label' => 'drivers & guides'],
            'activities' => ['n' => $cnt('SELECT COUNT(*) n FROM tf_place WHERE archived_at IS NULL'), 'label' => 'places'],
            'company'    => ['n' => count($sections['company']['items'] ?? []), 'label' => 'pages'],
        ];
        $data['mf_counts'] = array_filter($counts, static fn ($c) => $c['n'] !== null);
        // the Add record list: the existing "new record" pages this sign-in holds — nothing new behind the button
        $heldUrls = [];
        foreach ($sections as $sec) {
            foreach ($sec['items'] as $it) {
                $heldUrls[trim(strtok((string) $it['url'], '?'), '/')] = true;
            }
        }
        $data['mf_add'] = array_values(array_filter([
            ['url' => 'master_files/customer',        'label' => 'New customer or agent'],
            ['url' => 'master_files/hotel_master',    'label' => 'New hotel'],
            ['url' => 'master_files/supplier',        'label' => 'New supplier'],
            ['url' => 'master_files/crew_people',     'label' => 'New driver or guide'],
            ['url' => 'master_files/entrance_master', 'label' => 'New place or safari park'],
            ['url' => 'master_files/paid_services',   'label' => 'New paid activity'],
            ['url' => 'master_files/meal_master',     'label' => 'New meal'],
        ], static fn ($a) => isset($heldUrls[$a['url']])));
        return view('pages/master_files_landing', $data);
    }

    /** tf-stage3: the held page URLs, for the search and the attention list (only pages the sign-in holds are offered) */
    private static function heldUrls(array $home): array
    {
        $held = [];
        foreach ($home['desks'] as $d) {
            $walk = static function (array $items) use (&$walk, &$held): void {
                foreach ($items as $it) {
                    if (isset($it['items'])) {
                        $walk($it['items']);
                    } elseif (!empty($it['url'])) {
                        $held[trim(strtok((string) $it['url'], '?'), '/')] = true;
                    }
                }
            };
            $walk($d['items'] ?? []);
        }
        if ((int) session()->get('group_id') === 1) {   // tf-stage8: the Super Admin's own page is held whether or not the session's menu list has caught up
            $held['master_files/blocks'] = true;
            $held['master_files/staff'] = true;   // tf-stage10
        }
        if ((int) session()->get('group_id') === 1) {
            foreach (\App\Libraries\TourFlow\MasterSearch::pages() as $u) {
                $held[$u] = true;
            }
        }
        return $held;
    }

    // GET home/master_files/search?q= — one search across every master the sign-in holds (tf-stage3)
    public function masterFilesSearch()
    {
        try {
            $data = (new MenuLoader())->loadMenuData();
            $held = self::heldUrls(HomeDesks::forUser($data));
            $q = trim((string) ($this->request->getGet('q') ?? ''));
            return $this->response->setJSON(['status' => 200, 'data' => \App\Libraries\TourFlow\MasterSearch::search(\Config\Database::connect(), $q, $held, 8, trim((string) ($this->request->getGet('door') ?? '')) ?: null)]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // GET home/master_files/attention — the Needs attention list (tf-stage3)
    public function masterFilesAttention()
    {
        try {
            $data = (new MenuLoader())->loadMenuData();
            $held = self::heldUrls(HomeDesks::forUser($data));
            return $this->response->setJSON(['status' => 200, 'data' => \App\Libraries\TourFlow\MasterSearch::attention(\Config\Database::connect(), $held, 'home', trim((string) ($this->request->getGet('door') ?? '')) ?: null)]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    /** Level two: one category's buttons in three columns (home/master_files/{category}) */
    public function masterFilesCategory(string $cat = '')
    {
        if ((int) session()->get('group_id') === 24) {
            return redirect()->to(base_url('driver_portal'));
        }
        $data = (new MenuLoader())->loadMenuData();
        [$sections, $total] = self::masterFileSections(HomeDesks::forUser($data));
        if (!isset($sections[$cat])) {
            return redirect()->to(base_url('home/master_files'));
        }
        $data['mf_sections'] = [$cat => $sections[$cat]];
        $data['mf_total'] = $total;
        $data['mf_category'] = $sections[$cat];
        $data['mf_door_key'] = $cat;   // tf-stage7: the door's own search and attention
        return view('pages/master_files_desk', $data);
    }

    /**
     * The five categories — Hotel & Accommodation, Transport & Operations, Tour Services & Attractions, Suppliers & Finance,
     * System & General — each holding the existing master pages the sign-in holds (the Master Files desk's own rows). A held
     * page named in no category lands in System & General, so nothing is ever lost. Returns [$sections, $heldCount].
     */
    private static function masterFileSections(array $home): array
    {
        $held = [];      // the Master Files desk's own pages: these fill the doors and the System & General catch-all
        $other = [];     // pages held under another desk (Pricing, the Tour Desk admin): offered only where a door names them
        foreach ($home['desks'] as $d) {
            $isMaster = ($d['key'] ?? '') === 'masters';
            $walk = static function (array $items) use (&$walk, &$held, &$other, $isMaster): void {
                foreach ($items as $it) {
                    if (isset($it['items'])) {
                        $walk($it['items']);
                    } elseif (!empty($it['url'])) {
                        $k = trim(strtok((string) $it['url'], '?'), '/');
                        if ($isMaster) {
                            $held[$k] = ['url' => $it['url'], 'label' => (string) $it['label']];
                        } elseif (!isset($held[$k])) {
                            $other[$k] = ['url' => $it['url'], 'label' => (string) $it['label']];   // tf-stage3
                        }
                    }
                }
            };
            $walk($d['items']);
        }
        // tf-mfdoor-r1 (31 Oct 2026): the Super Admin's own two pages are granted to the System Administrator group alone and are
        // not part of any desk list, so the sixth door never had anything to show. They are added to this sign-in's own held list
        // here, for group 1 only; every other account is unchanged, and the controllers of both pages still check the group on
        // every request. No privilege, route or record is touched.
        if ((int) session()->get('group_id') === 1) {
            foreach ([['master_files/blocks', 'Blocks & Investigations']] as [$u, $l]) {   // tf-move283-r1 (7 Nov 2026): Staff Assignment now lives under Pilot Report only
                $held[$u] = $held[$u] ?? ['url' => $u, 'label' => $l];
            }
        }
        // tf-stage3 (29 Oct 2026): the six doors of the Master panel — the same existing pages, regrouped; a held page named in no
        // door lands in System & General, as before, so nothing is ever lost
        $plan = [
            'customers' => ['title' => 'Customers & Agents', 'short' => 'customer and agent', 'icon' => 'bi-people', 'tone' => 'purple', 'tint' => 'rose', 'items' => [
                ['url' => 'master_files/customer/agents',           'via' => 'master_files/customer', 'label' => 'Customers & Agents', 'icon' => 'bi-people-fill'],   // tf-stage6
                ['url' => 'master_files/customer',                  'label' => 'All customers (Consignee)', 'icon' => 'bi-people'],   // the menu row reads "Conginee"; shown correctly spelt, the row untouched
            ]],
            'hotels' => ['title' => 'Hotels', 'short' => 'hotel', 'icon' => 'bi-building', 'tone' => 'purple', 'tint' => 'rose', 'items' => [
                ['url' => 'master_files/hotel_master',        'label' => 'Hotel Master',          'icon' => 'bi-building-gear'],
                ['url' => 'master_files/hotel_rate_v2',       'label' => 'Hotel Rates',           'icon' => 'bi-building-fill'],
                ['url' => 'master_files/room_type',           'label' => 'Room Types',            'icon' => 'bi-door-open-fill'],
                ['url' => 'master_files/hotel_rate_approval', 'label' => 'Hotel Rate Approval',   'icon' => 'bi-patch-check-fill'],
                ['url' => 'master_files/hotel_work',          'label' => 'Hotel Incomplete Work', 'icon' => 'bi-list-check'],
                ['url' => 'master_files/offer_selection',     'label' => 'Tactical / Early Bird rate selection', 'icon' => 'bi-tags-fill'],
            ]],
            'transport' => ['title' => 'Transport & Crew', 'short' => 'transport and crew', 'icon' => 'bi-bus-front', 'tone' => 'green', 'tint' => 'mint', 'items' => [
                ['url' => 'master_files/crew_people',               'label' => 'Drivers & Guides',  'icon' => 'bi-person-badge-fill'],
                ['url' => 'master_files/supplier/new_driver_index', 'label' => 'Driver registration', 'icon' => 'bi-person-plus-fill'],
                ['url' => 'master_files/supplier/all_drivers',      'label' => 'Driver Report',    'icon' => 'bi-file-earmark-text-fill'],
                ['url' => 'master_files/vehicle',                   'label' => 'Vehicle',          'icon' => 'bi-truck-front-fill'],
                ['url' => 'master_files/transport_rates',           'label' => 'Transport Rates',  'icon' => 'bi-cash-coin'],
                ['url' => 'master_files/mileage_allowances',        'label' => 'Mileage Allowances', 'icon' => 'bi-signpost-2'],
                ['url' => 'master_files/transfer_milages',          'label' => 'Routing Registry', 'icon' => 'bi-signpost-split-fill'],
                ['url' => 'master_files/extra_kilometers',          'label' => 'Extra Kilometers', 'icon' => 'bi-speedometer2'],
                ['url' => 'master_files/crew_bata',                 'label' => 'Crew Bata',        'icon' => 'bi-wallet2'],
                ['url' => 'master_files/crew_people/bank',          'label' => 'Driver & Guide bank details', 'icon' => 'bi-credit-card-2-front-fill'],
            ]],
            'activities' => ['title' => 'Activities & Suppliers', 'short' => 'activity and supplier', 'icon' => 'bi-camera', 'tone' => 'purple', 'tint' => 'rose', 'items' => [
                // tf-stage1 (29 Oct 2026): the old Place to Visit list and Excursion Rates are off the menu — their pages still open by address
                ['url' => 'master_files/entrance_master',   'label' => 'Place to Visit / Entrance Fee Master', 'icon' => 'bi-ticket-detailed-fill'],
                ['url' => 'master_files/paid_services',     'label' => 'Paid Activities & Services',           'icon' => 'bi-person-walking'],
                ['url' => 'master_files/meal_master',       'label' => 'Meal Master',                          'icon' => 'bi-cup-hot-fill'],
                ['url' => 'master_files/free_visits',       'label' => 'Free Visits',                          'icon' => 'bi-star-fill'],
                ['url' => 'master_files/tour_photos',       'label' => 'Tour Photos',                          'icon' => 'bi-images'],
                ['url' => 'master_files/supplier',          'label' => 'Supplier',                             'icon' => 'bi-people-fill'],
                ['url' => 'master_files/supplier/all',      'label' => 'Supplier Report',                      'icon' => 'bi-file-earmark-text-fill'],
                ['url' => 'master_files/shop_registration', 'label' => 'Shop Registration',                    'icon' => 'bi-bag-fill'],
            ]],
            'company' => ['title' => 'Company Settings', 'short' => 'company setting', 'icon' => 'bi-gear', 'tone' => 'green', 'tint' => 'mint', 'items' => [
                ['url' => 'master_files/daily_usd_rate',    'label' => 'Daily USD Rate',                 'icon' => 'bi-bar-chart-fill'],
                ['url' => 'master_files/bank_accounts',     'label' => 'Avenir Bank Accounts',           'icon' => 'bi-bank2'],
                ['url' => 'master_files/supplier_accounts', 'label' => 'Supplier Bank Account Registry', 'icon' => 'bi-safe2-fill'],
                ['url' => 'master_files/supplier_bank',     'label' => 'Supplier Bank Details',          'icon' => 'bi-credit-card-2-front-fill'],
                ['url' => 'tour/pricing',                   'label' => 'Pricing & quotation defaults',   'icon' => 'bi-percent'],
                ['url' => 'tour/manage',                    'label' => 'Tour Desk admin & mailboxes',    'icon' => 'bi-shield-lock'],
            ]],
            // the sixth door: built in Stage 8; until then the System Administrator sees it as coming, nobody else sees it
            'blocks' => ['title' => 'Blocks & Investigations', 'short' => 'block and investigation', 'icon' => 'bi-shield-exclamation', 'tone' => 'purple', 'tint' => 'rose', 'items' => [
                ['url' => 'master_files/blocks', 'label' => 'Blocks & Investigations', 'icon' => 'bi-shield-exclamation'],   // tf-stage8: Super Admin only
            ]],
            'system' => ['title' => 'System & General', 'short' => 'system and general', 'icon' => 'bi-grid-3x3-gap', 'tone' => 'green', 'tint' => 'mint', 'items' => []],
        ];
        $used = [];
        $sections = [];
        foreach ($plan as $key => $sec) {
            $out = [];
            foreach ($sec['items'] as $it) {
                if (isset($it['via']) && isset($held[$it['via']])) {   // tf-stage6: a page reached through another page's privilege
                    $out[] = $it;
                } elseif (isset($held[$it['url']])) {
                    $used[$it['url']] = true;
                    $it['url'] = $held[$it['url']]['url'];
                    $out[] = $it;
                } elseif (isset($other[$it['url']])) {   // tf-stage3: a page held under another desk that a door names
                    $it['url'] = $other[$it['url']]['url'];
                    $out[] = $it;
                }
            }
            $sec['items'] = $out;
            $sections[$key] = $sec;
        }
        foreach ($held as $u => $h) {   // any other master page the sign-in holds
            if (!isset($used[$u])) {
                $sections['system']['items'][] = ['url' => $h['url'], 'label' => $h['label'], 'icon' => 'bi-folder2-open'];
            }
        }
        foreach ($sections as $k => $sec) {
            $sections[$k]['count'] = count($sec['items']);
        }
        // tf-stage3: an empty door that is not "coming" is dropped for this sign-in; the System & General door shows only when something landed in it
        foreach (['blocks', 'system'] as $k) {
            if ($sections[$k]['count'] === 0 && empty($sections[$k]['coming'])) {
                unset($sections[$k]);
            }
        }
        return [$sections, count($held)];
    }

    // =====================================================================
    //  tf-findesk-r1 (25 Oct 2026) — the Finance Desk page (home/finance_desk), to the approved mockup: four sections of buttons
    //  — Payments & Payables, Tour Payment Monitoring, Banking & Reconciliation, Accounting & Finance — every button an EXISTING
    //  page the sign-in holds (the Finance Desk's own rows). Payment Approval stays in the Approver Desk and is not offered here.
    //  Navigation only: no route, page, record, rule or privilege changes.
    // =====================================================================

    public function financeDesk()
    {
        if ((int) session()->get('group_id') === 24) {
            return redirect()->to(base_url('driver_portal'));
        }
        $data = (new MenuLoader())->loadMenuData();
        $home = HomeDesks::forUser($data);
        $held = [];
        foreach ($home['desks'] as $d) {
            if (($d['key'] ?? '') !== 'finance') {
                continue;
            }
            $walk = static function (array $items) use (&$walk, &$held): void {
                foreach ($items as $it) {
                    if (isset($it['items'])) {
                        $walk($it['items']);
                    } elseif (!empty($it['url'])) {
                        $held[trim(strtok((string) $it['url'], '?'), '/')] = ['url' => $it['url'], 'label' => (string) $it['label'], 'icon' => $it['icon'] ?? 'bi-link-45deg'];
                    }
                }
            };
            $walk($d['items']);
        }
        $plan = [
            'payments' => ['title' => 'Payments & Payables', 'icon' => 'bi-wallet2', 'tone' => 'purple', 'items' => [
                ['url' => 'tour/payment_desk',                               'label' => 'Payment Desk',       'icon' => 'bi-wallet2'],
                ['url' => 'tour/tour_payment_dashboard/payables',            'label' => 'Supplier Payables',  'icon' => 'bi-receipt-cutoff'],
                ['url' => 'accounts/voucher_payments/0',                     'label' => 'Voucher Payments',   'icon' => 'bi-receipt'],
                ['url' => 'report/bank_payment_check/finalized_records',     'label' => 'Finalized Payments', 'icon' => 'bi-check2-circle'],
            ]],
            'monitor' => ['title' => 'Tour Payment Monitoring', 'icon' => 'bi-bar-chart-fill', 'tone' => 'gold', 'note' => 'Payment Approval is handled in the Approver Desk.', 'items' => [
                ['url' => 'tour/tour_payment_dashboard',                     'label' => 'Tour Payment Dashboard', 'icon' => 'bi-bar-chart-fill'],
            ]],
            'banking' => ['title' => 'Banking & Reconciliation', 'icon' => 'bi-bank', 'tone' => 'green', 'items' => [
                ['url' => 'report/bank_payment_check',                       'label' => 'Bank Payment Check',                  'icon' => 'bi-bank'],
                ['url' => 'accounts/daily_bank_rec',                         'label' => 'Daily Bank Reconciliation',           'icon' => 'bi-journal-check'],
                ['url' => 'accounts/bank_reconcil',                          'label' => 'Bank Reconciliation (Earlier Screen)', 'icon' => 'bi-clock-history'],
            ]],
            'accounting' => ['title' => 'Accounting & Finance', 'icon' => 'bi-calculator-fill', 'tone' => 'slate', 'items' => [
                ['url' => 'accounts/journal_entry',                          'label' => 'Journal Entry',      'icon' => 'bi-journal-text'],
                ['group' => 'finance_masters', 'label' => 'Finance Masters', 'icon' => 'bi-gear-fill', 'members' => [
                    'master_files/supplier_bank' => 'Supplier Bank Details', 'master_files/bank_accounts' => 'Avenir Bank Accounts', 'master_files/daily_usd_rate' => "Today's USD Rate"]],
            ]],
        ];
        $used = [];
        $sections = [];
        foreach ($plan as $key => $sec) {
            $out = [];
            foreach ($sec['items'] as $it) {
                if (isset($it['group'])) {
                    $members = [];
                    foreach ($it['members'] as $u => $label) {
                        if (isset($held[$u])) {
                            $members[] = ['url' => $held[$u]['url'], 'label' => $label, 'icon' => $held[$u]['icon']];
                            $used[$u] = true;
                        }
                    }
                    if ($members) {
                        $it['members'] = $members;
                        $out[] = $it;
                    }
                } elseif (isset($held[$it['url']])) {
                    $used[$it['url']] = true;
                    $it['url'] = $held[$it['url']]['url'];
                    $out[] = $it;
                }
            }
            if ($out) {
                $sec['items'] = $out;
                $sections[$key] = $sec;
            }
        }
        $other = [];
        foreach ($held as $u => $h) {   // any other Finance Desk page the sign-in holds — never dropped
            if (!isset($used[$u])) {
                $other[] = ['url' => $h['url'], 'label' => $h['label'], 'icon' => $h['icon']];
            }
        }
        if ($other) {
            $sections['other'] = ['title' => 'Other finance pages', 'icon' => 'bi-folder2-open', 'tone' => 'slate', 'items' => $other];
        }
        $data['fd_sections'] = $sections;
        $data['fd_total'] = count($held);
        return view('pages/finance_desk', $data);
    }

    // =====================================================================
    //  tf-appdesk-r1 (25 Oct 2026) — the Approver Desk page (home/approver_desk), to the approved mockup: two large cards, Payment
    //  Approval and Hotel Rate Approval, each opening its EXISTING page with the same URL and privilege. Navigation only.
    // =====================================================================

    public function approverDesk()
    {
        if ((int) session()->get('group_id') === 24) {
            return redirect()->to(base_url('driver_portal'));
        }
        $data = (new MenuLoader())->loadMenuData();
        $home = HomeDesks::forUser($data);
        $held = [];
        foreach ($home['desks'] as $d) {
            if (($d['key'] ?? '') !== 'approver') {
                continue;
            }
            foreach ($d['items'] as $it) {
                if (!empty($it['url'])) {
                    $held[trim(strtok((string) $it['url'], '?'), '/')] = $it;
                }
            }
        }
        $plan = [
            ['url' => 'tour/tour_accounts/payment_approval_v2', 'title' => 'Payment Approval',    'sub' => 'Review, approve or reject payment requests',  'btn' => 'Open Payment Approval',    'icon' => 'bi-file-earmark-check-fill', 'tone' => 'purple'],
            ['url' => 'master_files/hotel_rate_approval',       'title' => 'Hotel Rate Approval', 'sub' => 'Review and approve submitted hotel rates',  'btn' => 'Open Hotel Rate Approval', 'icon' => 'bi-building-fill-check',   'tone' => 'green'],
        ];
        $cards = [];
        $used = [];
        foreach ($plan as $c) {
            if (isset($held[$c['url']])) {
                $used[$c['url']] = true;
                $cards[] = $c;
            }
        }
        foreach ($held as $u => $it) {   // any other approval page the sign-in holds — never dropped
            if (!isset($used[$u])) {
                $cards[] = ['url' => $it['url'], 'title' => (string) $it['label'], 'sub' => (string) ($it['sub'] ?? ''), 'btn' => 'Open ' . $it['label'], 'icon' => $it['icon'] ?? 'bi-shield-check', 'tone' => 'purple'];
            }
        }
        $data['ad_cards'] = $cards;
        return view('pages/approver_desk', $data);
    }

    /** The Transport Desk's tiles, each pointing at an existing page the sign-in may open; a tile without access is shown but not linked. */
    private static function transportDeskTiles(array $home): array
    {
        $may = self::heldPages($home);
        $pending = $may('dashboard2/transport/driver_allow/pending_tours') || $may('dashboard2/transport/driver_allow/tour_transport');
        $crewUrl = $may('master_files/crew_people') ? 'master_files/crew_people' : 'master_files/supplier/new_driver_index';
        $routeUrl = $may('master_files/mileage_allowances') ? 'master_files/mileage_allowances' : 'master_files/transfer_milages';
        $calendar = $may('tour/transport_ops') || $pending || $may('tour/board') || $may('tour/intake/confirmed') || $may('tour/intake');
        $tiles = [
            ['key' => 'ops',      'title' => 'Transport Operations',    'url' => 'tour/transport_ops', 'ok' => $may('tour/transport_ops')],
            // tf-rotation-r1: Pending Allocations is the rotation's page; the earlier Pending Driver Allocation page stays under "Also on this desk"
            ['key' => 'pending',  'title' => 'Pending Allocations',     'url' => 'tour/transport_ops/allocations', 'ok' => $pending || $may('tour/transport_ops')],
            ['key' => 'crew',     'title' => 'Driver & Guide Handling', 'url' => $crewUrl . '?desk=transport', 'ok' => $may($crewUrl)],
            // no railway booking page exists in the system yet — the section is shown, not linked (design stage; nothing invented)
            ['key' => 'rail',     'title' => 'Railway Bookings',        'url' => '', 'ok' => false, 'note' => 'Booking page not built yet — visual section for review.'],
            ['key' => 'upcoming', 'title' => 'Upcoming Tours',          'url' => 'home/tour_desk/upcoming?desk=transport', 'ok' => $calendar],
            ['key' => 'routes',   'title' => 'Route Maps & Mileage',    'url' => $routeUrl . '?desk=transport', 'ok' => $may($routeUrl)],
        ];
        return ['tiles' => $tiles, 'can_calendar' => $calendar];
    }

    /** The Reservation Desk's tiles, each pointing at an existing page the sign-in may open; a tile without access is shown but not linked. */
    private static function reservationDeskTiles(array $home): array
    {
        $may = self::heldPages($home);
        $anyTour = $may('tour/board') || $may('tour/intake/confirmed') || $may('tour/intake');
        $calendar = $may('tour/hotel_bookings') || $may('tour/ticket_stock') || $anyTour;
        $tiles = [
            ['key' => 'hotel',     'title' => 'Hotel Reservations & Bookings', 'url' => 'tour/hotel_bookings', 'ok' => $may('tour/hotel_bookings')],
            ['key' => 'tickets',   'title' => 'Ticket Stock',    'url' => 'tour/ticket_stock', 'ok' => $may('tour/ticket_stock')],
            ['key' => 'confirmed', 'title' => 'Confirmed Tours', 'url' => 'tour/intake/confirmed', 'ok' => $may('tour/intake/confirmed') || $may('tour/intake')],
            ['key' => 'upcoming',  'title' => 'Upcoming Tours',  'url' => 'home/tour_desk/upcoming?desk=reservation', 'ok' => $calendar],
            ['key' => 'arrivals',  'title' => 'Next Arrivals',   'url' => 'home/tour_desk/arrivals?desk=reservation', 'ok' => $calendar],
        ];
        return ['tiles' => $tiles, 'can_calendar' => $calendar];
    }

    /** The pages a sign-in holds, as HomeDesks::forUser lists them: a closure answering may('tour/board') */
    private static function heldPages(array $home): \Closure
    {
        $held = [];
        foreach ($home['desks'] as $d) {
            $walk = static function (array $items) use (&$walk, &$held): void {
                foreach ($items as $it) {
                    if (isset($it['items'])) {
                        $walk($it['items']);
                    } elseif (!empty($it['url'])) {
                        $held[strtok((string) $it['url'], '?')] = true;
                    }
                }
            };
            $walk($d['items']);
        }
        return static fn (string $u) => isset($held[$u]);
    }

    /** The five tiles, each pointing at an existing page the user may open (HomeDesks::forUser decides); a tile without access is shown but not linked. */
    private static function tourDeskTiles(array $home): array
    {
        $held = [];
        foreach ($home['desks'] as $d) {
            $walk = static function (array $items) use (&$walk, &$held): void {
                foreach ($items as $it) {
                    if (isset($it['items'])) {
                        $walk($it['items']);
                    } elseif (!empty($it['url'])) {
                        $held[strtok((string) $it['url'], '?')] = true;
                    }
                }
            };
            $walk($d['items']);
        }
        $may = static fn (string $u) => isset($held[$u]);
        $tiles = [
            ['key' => 'inquiries', 'title' => 'Tour Inquiries', 'sub' => 'every inquiry — new ones and e-mailed ones are started inside', 'url' => 'tour/intake', 'ok' => $may('tour/intake')],
            ['key' => 'confirmed', 'title' => 'Confirmed Tours', 'sub' => 'tours agreed with the agent', 'url' => 'tour/intake/confirmed', 'ok' => $may('tour/intake/confirmed') || $may('tour/intake')],
            ['key' => 'board', 'title' => 'Tour Board', 'sub' => 'every tour at a glance', 'url' => 'tour/board', 'ok' => $may('tour/board')],
            // tf-tourdesk-r2: Upcoming Tours and Next Arrivals open their own detail views (home/tour_desk/upcoming, home/tour_desk/arrivals);
            // 'behind' is the existing page each view offers to open on, when the sign-in holds it
            ['key' => 'upcoming', 'title' => 'Upcoming Tours', 'sub' => 'the next one month', 'url' => 'home/tour_desk/upcoming', 'ok' => $may('tour/board') || $may('tour/intake/confirmed') || $may('tour/intake'),
                'behind' => $may('tour/board') ? ['url' => 'tour/board', 'label' => 'Open Tour Board'] : ['url' => 'tour/intake/confirmed', 'label' => 'Open Confirmed Tours']],
            ['key' => 'arrivals', 'title' => 'Next Arrivals', 'sub' => 'the next seven days', 'url' => 'home/tour_desk/arrivals', 'ok' => $may('tour/transport_ops') || $may('tour/board') || $may('tour/intake/confirmed') || $may('tour/intake'),
                'behind' => $may('tour/transport_ops') ? ['url' => 'tour/transport_ops', 'label' => 'Open Transport Ops'] : ($may('tour/board') ? ['url' => 'tour/board', 'label' => 'Open Tour Board'] : ['url' => 'tour/intake/confirmed', 'label' => 'Open Confirmed Tours'])],
        ];
        return ['tiles' => $tiles, 'can_calendar' => $tiles[3]['ok']];
    }
}
