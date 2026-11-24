<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\TourFlow\IntakeService;
use App\Libraries\TourFlow\QuoteService;
use App\Libraries\TourFlow\Staff;
use App\Libraries\TourFlow\TravelPlans;

/**
 * tf-travel-r1 (24 Nov 2026) — the Travel Plans tab of a Tour Inquiry: passengers who leave before the rest. Routes sit in the
 * tour/intake group, so the same sign-in and permission filters apply as to the rest of the inquiry. Library: TourFlow\TravelPlans.
 */
class TravelPlansController extends BaseController
{
    private function db()
    {
        return \Config\Database::connect();
    }

    private function user(): string
    {
        $uid = (int) session()->get('user_id');
        return Staff::displayName($this->db(), $uid > 0 ? $uid : null, (string) session()->get('username'));
    }

    private function take(): IntakeService
    {
        $u = (string) session()->get('username');
        $uid = (int) session()->get('user_id');
        $svc = new IntakeService($this->db(), $u, new HotelRateService($this->db(), $u));
        return method_exists($svc, 'withActor') ? $svc->withActor($uid > 0 ? $uid : null, $this->user()) : $svc;
    }

    private function lib(?IntakeService $take = null): TravelPlans
    {
        return new TravelPlans($this->db(), $take ?? $this->take(), $this->user());
    }

    private function body(): array
    {
        if (stripos((string) $this->request->getHeaderLine('Content-Type'), 'json') === false) {
            return $this->request->getPost() ?: [];
        }
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : [];
    }

    private function out(array $p)
    {
        return $this->response->setJSON($p);
    }

    // GET intake/(:num)/travel
    public function data($id)
    {
        try {
            return $this->out(['status' => 200, 'data' => $this->lib()->data((int) $id)]);
        } catch (\Throwable $e) {
            return $this->out(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // POST intake/(:num)/travel/(save|preview|rooms_ok|delete)
    public function act($id, $what)
    {
        $id = (int) $id;
        try {
            $take = $this->take();
            $lib = $this->lib($take);
            $b = $this->body();
            $planId = (int) ($b['plan_id'] ?? 0) ?: null;
            switch ($what) {
                case 'preview':
                    $sees = \App\Libraries\TourPayments\Perms::holds('tour/pricing');   // cost totals: the Pricing privilege, as on the costing
                    return $this->out(['status' => 200, 'data' => $lib->preview($id, $planId, $b, new QuoteService($this->db(), (string) session()->get('username'), $take), $sees)]);
                case 'save':
                    $r = $lib->save($id, $planId, $b);
                    return $this->out(['status' => 200, 'message' => 'Travel plan saved. The groups, the airport transfer and the service quantities follow it.', 'data' => $r['data'], 'plan_id' => $r['plan_id']]);
                case 'rooms_ok':
                    return $this->out(['status' => 200, 'message' => 'Room allocation confirmed.', 'data' => $lib->reviewRooms($id)]);
                case 'delete':
                    if (!$planId) {
                        return $this->out(['status' => 400, 'message' => 'Which travel plan?']);
                    }
                    return $this->out(['status' => 200, 'message' => 'Travel plan removed. The passengers stay with the tour again.', 'data' => $lib->delete($id, $planId)]);
            }
            return $this->out(['status' => 404, 'message' => 'Unknown action.']);
        } catch (\Throwable $e) {
            return $this->out(['status' => 400, 'message' => $e->getMessage()]);
        }
    }
}
