<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\OfferRates;
use App\Libraries\MenuLoader;

/**
 * Hotel Rates → Tactical rates & Early Bird offers — master_files/hotel_rate_v2/offers (tf-offers-r1)
 *
 * The monitor across hotels: every tactical rate and early bird offer with its status (active, booking deadline
 * close, booking closed, expired, withdrawn, draft), and the hotels that have none — so the team can approach those
 * hotels and ask for special rates. Read-only: opening it writes nothing. Adding, correcting or withdrawing an offer
 * happens on the hotel's own Tactical / Early Bird tab in Hotel Rates (new).
 *
 * Privileges: no menu entry of its own — it sits under master_files/hotel_rate_v2, so the route filter lets in
 * exactly the sign-ins that hold the Hotel Rates (new) panel.
 */
class HotelOffersController extends BaseController
{
    public const URL = 'master_files/hotel_rate_v2/offers';
    public const GATE = 'master_files/hotel_rate_v2';

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    // GET master_files/hotel_rate_v2/offers
    public function index()
    {
        try {
            $data = (new MenuLoader())->loadMenuData();
        } catch (\Throwable $e) {
            $data = ['user_role' => session()->get('group_id'), 'allowed_menu_list' => []];
        }
        $data['page_url'] = self::URL;
        $data['panel_url'] = self::GATE;
        $data['today'] = date('Y-m-d');
        $data['tab'] = (string) $this->request->getGet('tab') === 'early_bird' ? 'early_bird' : 'tactical';
        try {
            $data['ready'] = (new OfferRates(\Config\Database::connect()))->ready();
        } catch (\Throwable $e) {
            $data['ready'] = false;
        }
        $data['deadline_soon_days'] = OfferRates::DEADLINE_SOON_DAYS;
        return view('pages/master_files/hotel_offers', $data);
    }

    // GET master_files/hotel_rate_v2/offers/data
    public function data()
    {
        try {
            $o = new OfferRates(\Config\Database::connect());
            if (!$o->ready()) {
                return $this->json(['status' => 400, 'message' => 'Tactical rates and Early Bird offers appear after Install now (TourFlow schema 42).']);
            }
            return $this->json(['status' => 200, 'data' => $o->monitor()]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
