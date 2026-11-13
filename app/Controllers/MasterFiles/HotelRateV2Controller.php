<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\AiSheetExtractor;
use App\Libraries\HotelRates\Dates;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\HotelRates\RateLookup;
use App\Libraries\HotelRates\RateImportService;
use App\Libraries\HotelRates\RateValidator;
use App\Libraries\HotelRates\Settings;
use App\Libraries\HotelRates\Sheet;
use App\Libraries\MenuLoader;

/**
 * Hotel Rates (new) panel.
 *
 * Two route groups (app/Config/Routes.hotel_rate_v2.php), two menu entries,
 * so the existing group-privilege screen decides who may do what:
 *
 *   master_files/hotel_rate_v2        the panel: view, enter drafts, import as
 *                                     drafts, coverage, quotes, health check
 *   master_files/hotel_rate_approval  approve / supersede / correct approved
 *                                     plans, import with approval, merge
 *                                     hotels, Settings (thresholds, markets,
 *                                     country → market, old-screen lock)
 *
 * UserPermissionFilter allows a request when its path is (a sub-path of) a
 * menu URL granted to the user's group, so granting "Hotel Rate Approval"
 * unlocks everything under the second group. canApprove() re-checks the same
 * grant inside the controller so an approval can never slip through the first
 * group (System Administrator, group 1, always passes).
 *
 * Every action except index() answers JSON: {status: 200|400|403|404|500, message?, data?}
 * (the convention the rest of the system uses).
 */
class HotelRateV2Controller extends BaseController
{
    public const PANEL_URL = 'master_files/hotel_rate_v2';
    public const APPROVAL_URL = 'master_files/hotel_rate_approval';

    private ?array $menuData = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function svc(): HotelRateService
    {
        return new HotelRateService(\Config\Database::connect(), (string) session()->get('username'));
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

    /** True when the user's group holds the "Hotel Rate Approval" menu entry (or is System Administrator). */
    private function canApprove(): bool
    {
        $data = $this->menuData();
        if ((int) ($data['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ((array) ($data['allowed_menu_list'] ?? []) as $m) {
            if (trim((string) ($m['url'] ?? ''), '/') === self::APPROVAL_URL) {
                return true;
            }
        }
        return false;
    }

    /** True when the user's group holds the Tour Desk Manage entry (tour/manage) or is System Administrator. */
    private function canManage(): bool
    {
        $data = $this->menuData();
        if ((int) ($data['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ((array) ($data['allowed_menu_list'] ?? []) as $m) {
            $u = is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '');
            if (trim((string) $u, '/') === \App\Libraries\TourFlow\Desks::MANAGE_URL) {
                return true;
            }
        }
        return false;
    }

    /** hotel-dup-r1: a hotel's name on the master is changed by the approval group or Management (or the administrator). */
    private function canRename(): bool
    {
        return $this->canApprove() || $this->canManage();
    }

    /** System Administrator (group 1) only — installing tables and copying the legacy rates is admin work. */
    private function isSysAdmin(): bool
    {
        return (int) ($this->menuData()['user_role'] ?? 0) === 1;
    }

    private function denied(string $what = 'approve rate plans')
    {
        return $this->json(['status' => 403, 'message' => "Your user group is not allowed to {$what}. Ask an administrator to grant the \"Hotel Rate Approval\" menu entry (" . self::APPROVAL_URL . ') under Settings › Group Privileges.']);
    }

    private function importer(): RateImportService
    {
        return new RateImportService(\Config\Database::connect(), (string) session()->get('username'));
    }

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }
        return $this->request->getPost() ?: [];
    }

    private function fail(\Throwable $e, int $status = 500)
    {
        return $this->json(['status' => $status, 'message' => $e->getMessage()]);
    }

    // ------------------------------------------------------------------
    //  Page
    // ------------------------------------------------------------------

    // GET master_files/hotel_rate_v2          the panel
    // GET master_files/hotel_rate_approval    the same panel opened on the approval queue
    public function index($mode = null)
    {
        $data = $this->menuData();
        $svc = $this->svc();
        $data['today'] = date('Y-m-d');
        $data['ai_enabled'] = class_exists('\Config\OpenAI') && !empty((new \Config\OpenAI())->apiKey);
        $data['user'] = (string) session()->get('username');
        $data['can_approve'] = $this->canApprove();
        // the ⋯ Manage on a hotel (archive / restore, with what depends on it) — the Tour Desk Manage privilege
        $data['can_manage'] = $this->canManage();
        $data['manage_url'] = \App\Libraries\TourFlow\Desks::MANAGE_URL;
        $data['approval_mode'] = $mode === 'approval' && $data['can_approve'];
        $data['panel_url'] = self::PANEL_URL;
        $data['approval_url'] = self::APPROVAL_URL;
        $data['work_url'] = \App\Controllers\MasterFiles\HotelWorkController::URL;
        $data['work_open'] = null;
        $data['preselect_acc'] = trim((string) $this->request->getGet('hotel'));
        // tf-hr-queue-r1: ?contract=ID opens that draft straight in the editor (the link on the approval queue)
        $data['preselect_contract'] = (int) $this->request->getGet('contract');
        $data['preselect_from'] = (string) $this->request->getGet('from') === 'queue' ? 'queue' : '';
        // hotel-dup-r1: renaming a hotel record (the master) is for approvers and Management; staff edit rates
        $data['can_rename'] = $this->canRename();
        $data['preselect_date'] = (string) (Dates::parse($this->request->getGet('date')) ?? '');
        $data['preselect_to'] = (string) (Dates::parse($this->request->getGet('to')) ?? '');
        $data['settings'] = Settings::defaults();
        $data['is_sysadmin'] = $this->isSysAdmin();
        $data['needs_migration'] = false;
        // tf-offers-r1: the Published / Tactical / Early Bird areas show once TourFlow schema 42 is installed
        try {
            $data['offers_ready'] = (new \App\Libraries\HotelRates\OfferRates(\Config\Database::connect(), $svc))->ready();
        } catch (\Throwable $e) {
            $data['offers_ready'] = false;
        }
        $data['offers_url'] = self::PANEL_URL . '/offers';
        $data['preselect_tab'] = in_array((string) $this->request->getGet('tab'), ['tactical', 'early_bird', 'plans'], true) ? (string) $this->request->getGet('tab') : '';
        try {
            $db = \Config\Database::connect();
            $version = \App\Libraries\HotelRates\Installer::installedVersion($db);
            $data['not_installed'] = $version === null || $version < \App\Libraries\HotelRates\Installer::SCHEMA_VERSION;
            $data['schema_version'] = $version;
            if (!$data['not_installed']) {
                $data['markets'] = $svc->markets();
                $data['settings'] = $svc->settings()->values();
                $svc->settings()->ensureMarketCountriesSeeded();
                $data['needs_migration'] = (int) ($db->query("SELECT COUNT(*) AS n FROM hr2_hotel")->getRowArray()['n'] ?? 0) === 0;
                try {
                    $wq = new \App\Libraries\HotelRates\WorkQueueService($db, $data['user'], $svc);
                    if (!$data['needs_migration']) {
                        $wq->scanIfDue((int) ($svc->settings()->get('work_scan_minutes') ?? 10));
                    }
                    $data['work_open'] = $wq->openCount();
                } catch (\Throwable $e) {
                    $data['work_open'] = null;
                }
            } else {
                $data['markets'] = [];
            }
        } catch (\Throwable $e) {
            $data['not_installed'] = true;
            $data['schema_version'] = null;
            $data['markets'] = [];
        }
        $data['default_market'] = $data['settings']['default_market'] ?? 'JP';
        return view('pages/master_files/hotel_rate_v2', $data);
    }

    public function approvalIndex()
    {
        if (!$this->canApprove()) {
            return redirect()->to(base_url(self::PANEL_URL));
        }
        return $this->index('approval');
    }

    // ------------------------------------------------------------------
    //  Hotels
    // ------------------------------------------------------------------

    // GET hotels?q=
    public function hotels()
    {
        try {
            $q = trim((string) $this->request->getGet('q'));
            $svc = $this->svc();
            // fav_ready: the Frequently Used mark exists (TourFlow schema 41) — the star and the tab show only then
            return $this->json(['status' => 200, 'data' => $svc->searchHotels($q, 800), 'fav_ready' => $svc->frequentReady()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET hotel/{id}?market=
    public function hotel($id)
    {
        try {
            $svc = $this->svc();
            $hotel = $svc->getHotel((int) $id);
            if (!$hotel) {
                return $this->json(['status' => 404, 'message' => 'Hotel not found.']);
            }
            $market = (string) ($this->request->getGet('market') ?: $svc->settings()->get('default_market'));
            $from = date('Y-m-d');
            $to = date('Y-m-d', strtotime('+' . (int) $svc->settings()->get('coverage_days') . ' days'));
            $contracts = $svc->contracts((int) $id);
            $docSvc = $this->docs();
            return $this->json(['status' => 200, 'data' => [
                'hotel' => $hotel,
                'room_types' => $svc->roomTypes((int) $id),
                'contracts' => $contracts,
                // tf-hr-docs-r1: paperclip counts — documents per plan, and the hotel-wide ones shown under every plan
                'doc_counts' => (object) $docSvc->countsForContracts(array_column($contracts, 'id')),
                'doc_hotel' => $docSvc->hotelWideCount((int) $id),
                'coverage' => $svc->coverage((int) $id, $from, $to, ['market' => $market]),
                'locations' => $svc->locations(),
                // tf-transfer-r1: the hotel's own airport pick-up / drop charge
                'transfer_fee' => \App\Libraries\TourFlow\HotelTransfers::installed(\Config\Database::connect()) ? \App\Libraries\TourFlow\HotelTransfers::fee(\Config\Database::connect(), (int) $id) : null,
                'transfer_fee_on' => \App\Libraries\TourFlow\HotelTransfers::installed(\Config\Database::connect()),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST hotel/{id}/update
    public function updateHotel($id)
    {
        try {
            $b = $this->body();
            // tf-transfer-r1: the airport pick-up / drop fee sits beside the hotel's record (TourFlow's own table)
            if (array_key_exists('tf_pickup_amount', $b) || array_key_exists('tf_drop_amount', $b)) {
                $tdb = \Config\Database::connect();
                if (\App\Libraries\TourFlow\HotelTransfers::installed($tdb)) {
                    \App\Libraries\TourFlow\HotelTransfers::save($tdb, (int) $id, [
                        'pickup_amount' => $b['tf_pickup_amount'] ?? '', 'drop_amount' => $b['tf_drop_amount'] ?? '', 'currency' => $b['tf_currency'] ?? 'USD',
                        'basis' => $b['tf_basis'] ?? 'vehicle', 'pax_per_vehicle' => $b['tf_pax_per_vehicle'] ?? '', 'note' => $b['tf_note'] ?? '',
                    ], (string) (session()->get('username') ?? session()->get('user_name') ?? 'user'));
                }
            }
            $fields = array_intersect_key($b, array_flip(HotelRateService::HOTEL_FIELDS));
            if (array_key_exists('location_name', $b)) {
                $fields['location_id'] = trim((string) $b['location_name']) === '' ? null : $this->svc()->ensureLocation((string) $b['location_name']);
            }
            if (isset($fields['name']) && trim((string) $fields['name']) === '') {
                return $this->json(['status' => 400, 'message' => 'Hotel name cannot be empty.']);
            }
            // hotel-dup-r1: the name on the master changes only by the approval group / Management, through
            // HotelRateService::renameHotel() (hotel + supplier + ledger name, audited; an exact duplicate is refused)
            $renamed = null;
            if (isset($fields['name'])) {
                $cur = $this->svc()->getHotel((int) $id);
                $fields['name'] = trim(preg_replace('/\s+/', ' ', (string) $fields['name']));
                if ($cur && $fields['name'] !== (string) $cur['name']) {
                    if (!$this->canRename()) {
                        return $this->json(['status' => 403, 'message' => 'Changing a hotel\'s name needs the Hotel Rate Approval or Tour Desk Manage privilege. Ask Management; the rates can still be corrected.']);
                    }
                    try {
                        $renamed = $this->svc()->renameHotel((int) $id, $fields['name']);
                    } catch (\RuntimeException $e) {
                        return $this->json(['status' => 409, 'message' => $e->getMessage()]);
                    }
                }
                unset($fields['name']);
            }
            // Taking a hotel out of use (or back) is Management's: the ⋯ Manage menu does it with
            // the dependency counts shown. The editor's status box is read-only for everyone else.
            if (array_key_exists('status', $fields) && !$this->canManage()) {
                $cur = $this->svc()->getHotel((int) $id);
                if ($cur && (string) $fields['status'] !== (string) $cur['status']) {
                    return $this->json(['status' => 403, 'message' => 'Changing a hotel\'s status (active / inactive) needs the Tour Desk Manage privilege. Ask Management to archive or restore it from the ⋯ Manage menu.']);
                }
                unset($fields['status']);
            }
            $this->svc()->updateHotel((int) $id, $fields);
            if ($renamed && $renamed['renamed']) {
                return $this->json(['status' => 200, 'message' => "Hotel renamed to \"{$renamed['name']}\" — on the Hotel Master, its supplier record and the ledger name" . ($renamed['accounts'] ? ' (' . implode(', ', $renamed['accounts']) . ')' : '') . '. The change is in the audit log.']);
            }
            return $this->json(['status' => 200, 'message' => 'Hotel updated.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST hotel/{id}/frequent {on: true|false} — the Hotel Master's "Frequently Used" mark. Only that flag on the
    // hotel record changes: the hotel stays in the full list, and no rate, plan or old Hotel Rate row is touched.
    public function frequent($id)
    {
        try {
            $b = $this->body();
            if (!array_key_exists('on', $b)) {
                return $this->json(['status' => 400, 'message' => 'Say whether to tick (on: true) or untick (on: false) Frequently Used.']);
            }
            $on = filter_var($b['on'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($on === null) {
                return $this->json(['status' => 400, 'message' => 'Say whether to tick (on: true) or untick (on: false) Frequently Used.']);
            }
            $res = $this->svc()->setFrequentlyUsed((int) $id, $on);
            $msg = $res['frequently_used'] ? "{$res['name']} is marked Frequently Used." : "{$res['name']} is no longer marked Frequently Used (it stays in the full hotel list).";
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // ------------------------------------------------------------------
    //  Published rates · Tactical rates · Early Bird offers (tf-offers-r1)
    // ------------------------------------------------------------------

    /**
     * tf-offers-r1: the panel's rate tools (Rate on a date, Coverage, Try a quote) answer as a quotation does —
     * published contract rates only — once the three areas are installed; all_plans=1 lets every approved plan compete.
     */
    private function publishedOnly(): array
    {
        if ($this->request->getGet('all_plans') || !$this->offers()->ready()) {
            return [];
        }
        return ['plan_types' => RateLookup::quotationPlanTypes(\Config\Database::connect())];
    }

    private function offers(): \App\Libraries\HotelRates\OfferRates
    {
        return new \App\Libraries\HotelRates\OfferRates(\Config\Database::connect(), $this->svc());
    }

    /** A sheet that is a tactical / early bird — and, when it already exists, was one before (a published plan never turns into one to skip approval). */
    private function isOfferSave(array $sheet): bool
    {
        $c = $sheet['contract'] ?? [];
        if (\App\Libraries\HotelRates\OfferRates::kindOf(['plan_type' => $c['plan_type'] ?? 'contract', 'applies_to' => $c['applies_to'] ?? 'both']) === null) {
            return false;
        }
        if (!empty($c['id'])) {
            $was = \Config\Database::connect()->query('SELECT plan_type, applies_to FROM hr2_contract WHERE id = ?', [(int) $c['id']])->getRowArray();
            if (!$was || \App\Libraries\HotelRates\OfferRates::kindOf($was) === null) {
                return false;
            }
        }
        return true;
    }

    // GET hotel/{id}/offers — the three areas of a hotel: published rates' state, its tactical rates and early bird offers
    public function hotelOffers($id)
    {
        try {
            $o = $this->offers();
            if (!$o->ready()) {
                return $this->json(['status' => 200, 'data' => null, 'message' => 'The Tactical and Early Bird areas appear after Install now (TourFlow schema 42).']);
            }
            return $this->json(['status' => 200, 'data' => $o->hotelAreas((int) $id)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST contract/{id}/withdraw_offer — a tactical / early bird stops counting (kept for history, as "withdrawn")
    public function withdrawOffer($id)
    {
        try {
            $db = \Config\Database::connect();
            $c = $db->query('SELECT id, plan_type, applies_to, status FROM hr2_contract WHERE id = ?', [(int) $id])->getRowArray();
            if (!$c || \App\Libraries\HotelRates\OfferRates::kindOf($c) === null) {
                return $this->json(['status' => 400, 'message' => 'Only a tactical rate or an early bird offer can be withdrawn here; a published plan is superseded through the approval queue.']);
            }
            if ($c['status'] !== 'approved') {
                return $this->json(['status' => 400, 'message' => $c['status'] === 'draft' ? 'This offer is a draft — delete it instead.' : 'This offer is no longer active.']);
            }
            $this->svc()->supersedeContract((int) $id);
            return $this->json(['status' => 200, 'message' => 'Withdrawn: it no longer counts for hotel payments. It stays in the list for history.']);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST hotel/{id}/room_type  {id?, name, ...ROOM_FIELDS, base_room_type?}
    public function roomType($id)
    {
        try {
            $b = $this->body();
            $svc = $this->svc();
            if (!empty($b['id'])) {
                $fields = array_intersect_key($b, array_flip(array_merge(HotelRateService::ROOM_FIELDS, ['base_room_type'])));
                $svc->updateRoomType((int) $b['id'], $fields);
                return $this->json(['status' => 200, 'message' => 'Room type updated.']);
            }
            $name = trim((string) ($b['name'] ?? ''));
            if ($name === '') {
                return $this->json(['status' => 400, 'message' => 'Room type name is required.']);
            }
            $extra = array_intersect_key($b, array_flip(array_merge(HotelRateService::ROOM_FIELDS, ['base_room_type'])));
            unset($extra['name']);
            $rt = $svc->ensureRoomType((int) $id, $name, null, array_filter($extra, static fn ($v) => $v !== '' && $v !== null));
            return $this->json(['status' => 200, 'message' => 'Room type added.', 'data' => $rt]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST hotel/{id}/project — rebuild the legacy hotel_rate rows of this hotel
    public function project($id)
    {
        try {
            $r = $this->svc()->projectHotel((int) $id);
            return $this->json(['status' => 200, 'message' => "{$r['rows_written']} legacy row(s) written for {$r['rooms']} room type(s).", 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET hotel/{id}/audit
    public function hotelAudit($id)
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->svc()->auditTrail('hotel', (int) $id)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // ------------------------------------------------------------------
    //  Rate plans
    // ------------------------------------------------------------------

    // GET contract/{id}
    public function contract($id)
    {
        try {
            $svc = $this->svc();
            $sheet = $svc->getContract((int) $id);
            if (!$sheet) {
                return $this->json(['status' => 404, 'message' => 'Plan not found.']);
            }
            return $this->json(['status' => 200, 'data' => $sheet, 'issues' => $svc->validateSheet($sheet)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST contract/validate {sheet}
    public function validateContract()
    {
        try {
            $b = $this->body();
            if (!is_array($b['sheet'] ?? null)) {
                return $this->json(['status' => 400, 'message' => 'No plan data received.']);
            }
            $issues = $this->svc()->validateSheet($b['sheet']);
            return $this->json(['status' => 200, 'data' => ['issues' => $issues, 'counts' => RateValidator::count($issues)]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST contract/save {sheet, approve, acknowledge_warnings, edit_approved}
    public function saveContract()
    {
        try {
            $b = $this->body();
            $sheet = $b['sheet'] ?? null;
            if (!is_array($sheet)) {
                return $this->json(['status' => 400, 'message' => 'No plan data received.']);
            }
            $approve = !empty($b['approve']);
            $editApproved = !empty($b['edit_approved']);
            $offerKind = \App\Libraries\HotelRates\OfferRates::kindOf(['plan_type' => $sheet['contract']['plan_type'] ?? 'contract', 'applies_to' => $sheet['contract']['applies_to'] ?? 'both']);
            // tf-offers-r1: a NEW tactical / early bird is entered as payment-only — it can never reach an inquiry, a
            // quotation, a confirmation or a booking (offers already on file keep their setting)
            if ($offerKind !== null && empty($sheet['contract']['id']) && in_array($sheet['contract']['plan_type'] ?? '', ['tactical', 'early_bird'], true) && $this->offers()->ready()) {
                $sheet['contract']['applies_to'] = 'payment';
            }
            // tf-offers-r1: a Tactical rate or an Early Bird offer counts as soon as it is saved (Shantha, 4 Oct 2026) —
            // "Save — active now" from its own tab, by anyone who holds the Hotel Rates (new) panel; the same for
            // correcting one already active. A published plan keeps the approval step exactly as before.
            if (!empty($b['activate_offer']) || ($editApproved && !$this->canApprove())) {
                if (!$this->isOfferSave($sheet)) {
                    return $this->denied($editApproved ? 'change approved rate plans' : 'approve rate plans');
                }
                if (!empty($b['activate_offer'])) {
                    $approve = true;
                }
            } elseif (($approve || $editApproved) && !$this->canApprove()) {
                return $this->denied($editApproved ? 'change approved rate plans' : 'approve rate plans');
            }
            $res = $this->svc()->saveContract($sheet, [
                'approve' => $approve,
                'acknowledge_warnings' => !empty($b['acknowledge_warnings']),
                'edit_approved' => $editApproved,
            ]);
            $c = RateValidator::count($res['issues']);
            $paymentOnly = (($sheet['contract']['applies_to'] ?? 'both') === 'payment');
            $offerReady = $offerKind !== null && $this->offers()->ready();
            $msg = $res['approved'] ? ($offerReady ? ($offerKind === 'early_bird' ? 'Early Bird offer' : 'Tactical rate') . ' saved — active now: at hotel payment it is offered to an authorized person to apply. Quotations keep the published rate.'
                : ($paymentOnly ? 'Ad hoc rate approved — used for hotel payments only; quotations keep the contract rate.' : 'Plan approved — rates are live for quotations and the old Hotel Rate screen.'))
                : ($res['saved'] ? 'Draft saved.' : ($c['errors'] ? "Not saved: {$c['errors']} error(s) to fix." : 'Please confirm the warnings.'));
            return $this->json(['status' => $res['saved'] ? 200 : 400, 'message' => $msg, 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST contract/{id}/approve {acknowledge_warnings}
    public function approve($id)
    {
        if (!$this->canApprove()) {
            return $this->denied();
        }
        try {
            $b = $this->body();
            $res = $this->svc()->approveContract((int) $id, null, !empty($b['acknowledge_warnings']));
            $paymentOnly = (($res['applies_to'] ?? null) === 'payment');
            $msg = $res['approved'] ? ($paymentOnly ? 'Ad hoc rate approved — used for hotel payments only; quotations keep the contract rate.' : 'Plan approved — rates are live.') : (!empty($res['needs_acknowledgement']) ? 'Please confirm the warnings.' : 'Fix the errors before approving.');
            return $this->json(['status' => $res['approved'] ? 200 : 400, 'message' => $msg, 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST contract/{id}/supersede
    public function supersede($id)
    {
        if (!$this->canApprove()) {
            return $this->denied('supersede approved rate plans');
        }
        try {
            $this->svc()->supersedeContract((int) $id);
            return $this->json(['status' => 200, 'message' => 'Plan marked as superseded; its rates are no longer used.']);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST contract/{id}/delete
    public function deleteContract($id)
    {
        try {
            $this->svc()->deleteContract((int) $id);
            return $this->json(['status' => 200, 'message' => 'Draft deleted.']);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST contract/{id}/copy {shift_years, name}
    public function copyContract($id)
    {
        try {
            $b = $this->body();
            $newId = $this->svc()->copyContract((int) $id, (int) ($b['shift_years'] ?? 0), isset($b['name']) && trim((string) $b['name']) !== '' ? trim((string) $b['name']) : null);
            return $this->json(['status' => 200, 'message' => 'Copied to a new draft.', 'data' => ['contract_id' => $newId]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET contract/{id}/audit
    public function contractAudit($id)
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->svc()->auditTrail('contract', (int) $id)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET coverage/{hotelId}?from=&to=&market=&booking_date=&rooms=
    public function coverage($hotelId)
    {
        try {
            $svc = $this->svc();
            $from = $this->request->getGet('from') ?: date('Y-m-d');
            $to = $this->request->getGet('to') ?: date('Y-m-d', strtotime('+' . (int) $svc->settings()->get('coverage_days') . ' days'));
            $opts = array_filter(['market' => $this->request->getGet('market'), 'country' => $this->request->getGet('country'), 'booking_date' => $this->request->getGet('booking_date'), 'rooms' => $this->request->getGet('rooms'), 'tier' => $this->request->getGet('tier')], static fn ($v) => $v !== null && $v !== '');
            $opts += $this->publishedOnly();
            return $this->json(['status' => 200, 'data' => $svc->coverage((int) $hotelId, $from, $to, $opts)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET rates_on?hotel_id=|hotel_acc=&date=&market=|country=&booking_date=&rooms=&pax=&tier=
    // The "rate on a date" card: every room type x meal plan the hotel has for that night,
    // the plan / season behind each cell, the night's supplements and restrictions, and —
    // when nothing is approved for the date — the nearest plans and any draft covering it.
    public function ratesOn()
    {
        try {
            $svc = $this->svc();
            $hotelId = (int) $this->request->getGet('hotel_id');
            if (!$hotelId && ($acc = (string) $this->request->getGet('hotel_acc')) !== '') {
                $h = $svc->findHotelByAcc($acc);
                $hotelId = $h ? (int) $h['id'] : 0;
            }
            $date = Dates::parse($this->request->getGet('date')) ?: date('Y-m-d');
            $to = Dates::parse($this->request->getGet('to'));
            if (!$hotelId) {
                return $this->json(['status' => 400, 'message' => 'hotel_id (or hotel_acc) is required.']);
            }
            $opts = array_filter(['market' => $this->request->getGet('market'), 'country' => $this->request->getGet('country'), 'booking_date' => Dates::parse($this->request->getGet('booking_date')), 'rooms' => $this->request->getGet('rooms'), 'pax' => $this->request->getGet('pax'), 'tier' => $this->request->getGet('tier')], static fn ($v) => $v !== null && $v !== '');
            $opts += $this->publishedOnly();
            if ($to && $to !== $date) {
                return $this->json(['status' => 200, 'data' => $svc->ratesForRange($hotelId, $date, $to, $opts)]);
            }
            return $this->json(['status' => 200, 'data' => ['mode' => 'night'] + $svc->ratesOn($hotelId, $date, $opts)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET lookup?hotel_acc=|hotel_id=&room=&meal=&date=&market=|country=&booking_date=&rooms=&pax=&tier=
    // Same answer the quotation timeline needs: SGL/DBL/TPL(/QUAD/UNIT) plus the night's supplements and rules.
    // Pass country= (the ISO code the quotation stores) and the market is taken from Settings › Country → market.
    public function lookup()
    {
        try {
            $acc = (string) $this->request->getGet('hotel_acc');
            $hotelId = (int) $this->request->getGet('hotel_id');
            $room = (string) $this->request->getGet('room');
            $meal = (string) $this->request->getGet('meal');
            $date = Dates::parse($this->request->getGet('date'));
            if ((!$acc && !$hotelId) || $room === '' || $meal === '' || !$date) {
                return $this->json(['status' => 400, 'message' => 'hotel_acc (or hotel_id), room, meal and date are required.']);
            }
            $opts = array_filter(['market' => $this->request->getGet('market'), 'country' => $this->request->getGet('country'), 'booking_date' => $this->request->getGet('booking_date'), 'rooms' => $this->request->getGet('rooms'), 'pax' => $this->request->getGet('pax'), 'tier' => $this->request->getGet('tier')], static fn ($v) => $v !== null && $v !== '');
            // a quotation's answer: published contract kinds only (all_plans=1 lets every approved plan compete, for the panel's own use)
            if (!$this->request->getGet('all_plans')) {
                $opts['plan_types'] = RateLookup::quotationPlanTypes(\Config\Database::connect());
            }
            $roomRef = ctype_digit($room) ? (int) $room : $room;
            $svc = $this->svc();
            $market = $svc->lookupEngine()->resolveMarket($opts);
            $r = $hotelId ? $svc->lookupById($hotelId, $roomRef, $meal, $date, $opts) : $svc->lookup($acc, $roomRef, $meal, $date, $opts);
            if (!$r) {
                return $this->json(['status' => 404, 'message' => "No approved contract rate for this hotel, room type, meal plan, date and market ({$market}).", 'market' => $market, 'flags' => $svc->lookupFlags(null, $date)]);
            }
            // flags: gala / peak / festival supplements, festive seasons, minimum stays, meal-plan rules — what a quotation must not miss
            return $this->json(['status' => 200, 'SGL' => $r['sgl'], 'DBL' => $r['dbl'], 'TPL' => $r['tpl'], 'QUAD' => $r['quad'], 'UNIT' => $r['unit'], 'market' => $market, 'flags' => $svc->lookupFlags($r, $date), 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET quote?hotel_id=&room=&meal=&check_in=&nights=&market=|country=&booking_date=&rooms=&pax=&adults=&children=
    // Prices a whole stay night by night (the panel's "Try a quote" tool and a starting point for the timeline).
    public function quote()
    {
        try {
            $hotelId = (int) $this->request->getGet('hotel_id');
            $room = (string) $this->request->getGet('room');
            $meal = (string) $this->request->getGet('meal');
            $checkIn = Dates::parse($this->request->getGet('check_in'));
            $nights = max(1, min(60, (int) ($this->request->getGet('nights') ?: 1)));
            $adults = max(1, (int) ($this->request->getGet('adults') ?: 2));
            if (!$hotelId || $room === '' || $meal === '' || !$checkIn) {
                return $this->json(['status' => 400, 'message' => 'hotel_id, room, meal and check_in are required.']);
            }
            $opts = array_filter(['market' => $this->request->getGet('market'), 'country' => $this->request->getGet('country'), 'booking_date' => $this->request->getGet('booking_date'), 'rooms' => $this->request->getGet('rooms'), 'pax' => $this->request->getGet('pax'), 'tier' => $this->request->getGet('tier')], static fn ($v) => $v !== null && $v !== '');
            $opts += $this->publishedOnly();
            $svc = $this->svc();
            $market = $svc->lookupEngine()->resolveMarket($opts);
            $occ = ['sgl' => 1, 'dbl' => 2, 'tpl' => 3, 'quad' => 4];
            $occKey = array_search(min($adults, 4), $occ, true) ?: 'dbl';
            $nightsOut = [];
            $total = 0.0; $totalSupp = 0.0; $missing = 0;
            $date = $checkIn;
            for ($i = 0; $i < $nights; $i++) {
                $r = $svc->lookupById($hotelId, ctype_digit($room) ? (int) $room : $room, $meal, $date, $opts);
                if (!$r) {
                    $nightsOut[] = ['date' => $date, 'missing' => true];
                    $missing++;
                } else {
                    $roomPrice = null;
                    if ($r['pricing_mode'] === 'per_unit') {
                        $roomPrice = $r['unit'];
                    } elseif ($r['pricing_mode'] === 'per_person') {
                        $roomPrice = $r[$occKey] !== null ? $r[$occKey] * $occ[$occKey] : null;
                    } else {
                        $roomPrice = $r[$occKey];
                    }
                    $supp = 0.0;
                    $suppList = [];
                    foreach ($r['supplements'] as $s) {
                        if (!(int) $s['compulsory']) {
                            continue;
                        }
                        $amt = $s['amount'] !== null ? (float) $s['amount'] : (float) ($s['amount_' . $occKey] ?? 0);
                        if ($s['pct_of_rate'] !== null && $roomPrice !== null) {
                            $amt = round($roomPrice * (float) $s['pct_of_rate'] / 100, 2);
                        }
                        $line = in_array($s['per'], ['person', 'adult'], true) ? $amt * $adults : $amt;
                        $supp += $line;
                        $suppList[] = ['name' => $s['name'], 'per' => $s['per'], 'amount' => $amt, 'line' => $line];
                    }
                    $total += (float) $roomPrice;
                    $totalSupp += $supp;
                    $nightsOut[] = ['date' => $date, 'room' => $roomPrice, 'supplements' => $suppList, 'supplement_total' => $supp, 'plan' => $r['plan']['name'], 'season' => $r['season']['name'], 'derived' => $r['derived'], 'restrictions' => array_values(array_filter($r['restrictions'], static fn ($x) => $x['kind'] !== 'other')), 'components' => $r['components']];
                }
                $date = Dates::next($date);
            }
            return $this->json(['status' => 200, 'data' => ['check_in' => $checkIn, 'nights' => $nights, 'occupancy' => $occKey, 'adults' => $adults, 'market' => $market, 'room_total' => round($total, 2), 'supplement_total' => round($totalSupp, 2), 'grand_total' => round($total + $totalSupp, 2), 'missing_nights' => $missing, 'nights_detail' => $nightsOut]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // ------------------------------------------------------------------
    //  Import
    // ------------------------------------------------------------------

    // POST import/preview {rows|workbook|sheets, defaults}
    public function importPreview()
    {
        try {
            $b = $this->body();
            $input = [];
            if (is_array($b['workbook'] ?? null)) {
                $input['workbook'] = $b['workbook'];
            } elseif (is_array($b['sheets'] ?? null)) {
                $input['sheets'] = $b['sheets'];
            } elseif (is_array($b['rows'] ?? null) && $b['rows']) {
                if (count($b['rows']) > 5000) {
                    return $this->json(['status' => 400, 'message' => 'Too many rows in one file (limit 5,000). Split the file.']);
                }
                $input['rows'] = $b['rows'];
            } else {
                return $this->json(['status' => 400, 'message' => 'The file has no data rows.']);
            }
            $defaults = is_array($b['defaults'] ?? null) ? $b['defaults'] : [];
            $res = $this->importer()->preview($input, $defaults);
            return $this->json(['status' => 200, 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST import/extract   multipart: contract_file, hotel_id?
    public function importExtract()
    {
        try {
            // tf-rate-reader-r1: the reader is Claude, with the Tour Inquiry email reader's key (Config\Anthropic)
            if (!AiSheetExtractor::configured()) {
                return $this->json(['status' => 400, 'message' => 'The AI reader is not set up on this server: ANTHROPIC_API_KEY (the key the Tour Inquiry email reader uses) is missing from .env. Enter the plan by hand meanwhile.']);
            }
            $hotelId = (int) $this->request->getPost('hotel_id');
            $file = $this->request->getFile('contract_file');
            if (!$file || !$file->isValid()) {
                return $this->json(['status' => 400, 'message' => 'No valid file uploaded.']);
            }
            $mime = $file->getMimeType();
            if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
                return $this->json(['status' => 400, 'message' => 'Only PDF, JPG and PNG files are accepted.']);
            }
            if ($file->getSizeByUnit('mb') > 20) {
                return $this->json(['status' => 400, 'message' => 'File is larger than 20 MB.']);
            }
            $originalName = $file->getName();
            $stored = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads', $stored);
            $path = WRITEPATH . 'uploads/' . $stored;
            $svc = $this->svc();
            $hints = ['markets' => $svc->markets()];
            if ($hotelId) {
                $h = $svc->getHotel($hotelId);
                $hints['hotel_name'] = $h['name'] ?? null;
            }
            try {
                $reader = new AiSheetExtractor();
                // a long rate sheet takes a minute or two to read: this request may run past PHP's usual limit
                if (function_exists('set_time_limit')) {
                    @set_time_limit(300);
                }
                $sheets = $reader->extract($path, $mime, $originalName, $hints);
            } catch (\Throwable $e) {
                // said plainly to the person, and written to the log (it used to reach the page only)
                if (function_exists('log_message')) {
                    log_message('error', 'Hotel Rates Read failed for "{file}": {msg}', ['file' => $originalName, 'msg' => $e->getMessage()]);
                }
                return $this->json(['status' => 502, 'message' => AiSheetExtractor::plainError($e->getMessage(), $originalName)]);
            } finally {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            if (!$sheets) {
                return $this->json(['status' => 404, 'message' => 'No rates were found in "' . $originalName . '", so there is nothing to import. It reads as a factsheet, brochure or letter rather than a rate sheet: enter the rates by hand, or ask the hotel for its rate sheet. Nothing was saved.']);
            }
            $defaults = ['source_type' => 'ai', 'source_file' => $originalName];
            if ($hotelId && count($sheets) === 1) {
                $defaults['hotel_id'] = $hotelId;
            }
            $res = $this->importer()->preview(['sheets' => $sheets], $defaults);
            return $this->json(['status' => 200, 'message' => 'Extracted ' . count($sheets) . ' plan(s). Check every number against the sheet before saving.', 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST import/{id}/commit {hotel_overrides, sheets, acknowledge_warnings, approve, supersede}
    public function importCommit($id)
    {
        try {
            $b = $this->body();
            $approve = !empty($b['approve']);
            $supersede = !empty($b['supersede']);
            // tf-offers-r1: a tactical / early bird uploaded from its own tab counts as soon as it is saved — allowed
            // when every plan sent is an offer (the tab sends them with their kind set); never with "supersede"
            if (!empty($b['activate_offer']) && !$supersede) {
                $sheets = is_array($b['sheets'] ?? null) ? $b['sheets'] : [];
                $allOffers = $sheets !== [] && !array_filter($sheets, static fn ($s) => !is_array($s) || \App\Libraries\HotelRates\OfferRates::kindOf(['plan_type' => $s['contract']['plan_type'] ?? 'contract', 'applies_to' => $s['contract']['applies_to'] ?? 'both']) === null);
                // every plan of the upload must be sent as an offer: none of it may slip through as a published plan
                $imp = $this->importer()->getImport((int) $id);
                $stored = count($imp['payload']['sheets'] ?? []);
                if (!$allOffers || $stored === 0 || count(array_intersect(array_keys($sheets), range(0, $stored - 1))) !== $stored) {
                    return $this->denied('approve imported rate plans');
                }
                $approve = true;
            } elseif (($approve || $supersede) && !$this->canApprove()) {
                return $this->denied('approve imported rate plans');
            }
            $sheetsIn = is_array($b['sheets'] ?? null) ? $b['sheets'] : [];
            // tf-offers-r1: tacticals / early birds uploaded from their own tab are new plans, entered payment-only
            if ($sheetsIn !== [] && $this->offers()->ready()) {
                foreach ($sheetsIn as $i => $s) {
                    if (is_array($s) && is_array($s['contract'] ?? null) && in_array($s['contract']['plan_type'] ?? '', ['tactical', 'early_bird'], true)) {
                        $sheetsIn[$i]['contract']['applies_to'] = 'payment';
                    }
                }
            }
            $res = $this->importer()->commit((int) $id, [
                'hotel_overrides' => is_array($b['hotel_overrides'] ?? null) ? $b['hotel_overrides'] : [],
                'sheets' => $sheetsIn,
                'acknowledge_warnings' => !empty($b['acknowledge_warnings']),
                'approve' => $approve,
                'supersede' => $supersede,
            ]);
            $saved = count(array_filter($res['results'], static fn ($r) => $r['saved']));
            return $this->json(['status' => $saved ? 200 : 400, 'message' => $saved ? "{$saved} plan(s) saved." : 'Nothing was saved; see the issues per plan.', 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET import/{id}
    public function importGet($id)
    {
        try {
            $imp = $this->importer()->getImport((int) $id);
            return $imp ? $this->json(['status' => 200, 'data' => $imp]) : $this->json(['status' => 404, 'message' => 'Import not found.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET imports
    public function imports()
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->importer()->recentImports(40)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // ------------------------------------------------------------------
    //  tf-hr-docs-r1: supporting documents (rate sheet PDF, contract, factsheet, e-mail) of a hotel / plan
    // ------------------------------------------------------------------

    private function docs(): \App\Libraries\HotelRates\RateDocuments
    {
        return new \App\Libraries\HotelRates\RateDocuments(\Config\Database::connect(), (string) session()->get('username'));
    }

    // GET hotel/{id}/documents — every active document of the hotel (hotel-wide ones and each plan's)
    public function hotelDocuments($id)
    {
        try {
            $d = $this->docs();
            return $this->json(['status' => 200, 'data' => ['documents' => $d->forHotel((int) $id), 'installed' => $d->installed(), 'storage_ok' => $d->storageRoot() !== null, 'max_mb' => \App\Libraries\HotelRates\RateDocuments::MAX_MB, 'kinds' => \App\Libraries\HotelRates\RateDocuments::KINDS, 'can_approve' => $this->canApprove(), 'user' => (string) session()->get('username')]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET contract/{id}/documents — the plan's own documents, then the hotel-wide ones
    public function contractDocuments($id)
    {
        try {
            $d = $this->docs();
            return $this->json(['status' => 200, 'data' => ['documents' => $d->forContract((int) $id), 'installed' => $d->installed(), 'storage_ok' => $d->storageRoot() !== null, 'max_mb' => \App\Libraries\HotelRates\RateDocuments::MAX_MB, 'kinds' => \App\Libraries\HotelRates\RateDocuments::KINDS, 'can_approve' => $this->canApprove(), 'user' => (string) session()->get('username')]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST documents/upload   multipart: document, hotel_id, contract_id?, kind?, note?
    public function uploadDocument()
    {
        try {
            $file = $this->request->getFile('document');
            if (!$file || !$file->isValid()) {
                $err = $file ? $file->getErrorString() : 'No file received.';
                return $this->json(['status' => 400, 'message' => 'The file was not received: ' . $err]);
            }
            $hotelId = (int) $this->request->getPost('hotel_id');
            $contractId = (int) $this->request->getPost('contract_id') ?: null;
            if (!$hotelId) {
                return $this->json(['status' => 400, 'message' => 'hotel_id is required.']);
            }
            $doc = $this->docs()->add($hotelId, $contractId, $file->getTempName(), $file->getClientName(), (string) ($this->request->getPost('kind') ?: 'rate_sheet'), $this->request->getPost('note'));
            return $this->json(['status' => 200, 'message' => '"' . $doc['original_name'] . '" attached' . ($doc['contract_id'] ? ' to the plan.' : ' to the hotel.'), 'data' => $doc]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET document/{id}[?download=1] — the file itself, through the panel only
    public function document($id)
    {
        try {
            $d = $this->docs();
            $doc = $d->get((int) $id);
            if (!$doc) {
                return $this->json(['status' => 404, 'message' => 'Document not found.'], 404);
            }
            $path = $d->path($doc);
            if (!$path) {
                return $this->json(['status' => 404, 'message' => 'The file is missing on the server.'], 404);
            }
            $inline = $doc['inline'] && !$this->request->getGet('download');
            $name = str_replace(['"', "\r", "\n"], '', (string) $doc['original_name']);
            $types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'txt' => 'text/plain; charset=UTF-8'];
            return $this->response
                ->setHeader('Content-Type', $types[$doc['ext']] ?? 'application/octet-stream')
                ->setHeader('Content-Disposition', ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"; filename*=UTF-8\'\'' . rawurlencode($name))
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setHeader('Cache-Control', 'private, max-age=0')
                ->setBody((string) file_get_contents($path));
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST document/{id}/remove {reason} — hidden from the lists; row, file and reason kept
    public function removeDocument($id)
    {
        try {
            $b = $this->body();
            $doc = $this->docs()->remove((int) $id, (string) ($b['reason'] ?? ''), $this->canApprove());
            return $this->json(['status' => 200, 'message' => '"' . $doc['original_name'] . '" removed (kept in the audit trail).', 'data' => $doc]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // ------------------------------------------------------------------
    //  Duplicates, locations, markets, system check
    // ------------------------------------------------------------------

    public function duplicates()
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->svc()->mergeSuggestions()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET similar?name=&exclude=   hotel-dup-r1: the records a new or renamed hotel may duplicate (read-only)
    public function similar()
    {
        try {
            $name = trim((string) $this->request->getGet('name'));
            if ($name === '') {
                return $this->json(['status' => 200, 'data' => []]);
            }
            return $this->json(['status' => 200, 'data' => $this->svc()->similarHotels($name, (int) $this->request->getGet('exclude'))]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET hotel/{id}/deps   hotel-dup-r1: everything that depends on the record, and whether it may be deleted (read-only)
    public function hotelDeps($id)
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->svc()->hotelDependencies((int) $id)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // hotel-dup-r1: deleting a hotel or a room type is done on the Hotel Master (master_files/hotel_master), not here.

    // POST merge {keep_id, merge_id}   hotel-dup-r1: Management only (was: the approval group)
    public function merge()
    {
        if (!$this->canManage()) {
            return $this->json(['status' => 403, 'message' => 'Removing a duplicate (merging two hotel records) needs the Tour Desk Manage privilege (Management).']);
        }
        try {
            $b = $this->body();
            $this->svc()->mergeHotels((int) ($b['keep_id'] ?? 0), (int) ($b['merge_id'] ?? 0));
            return $this->json(['status' => 200, 'message' => 'Hotels merged. The duplicate is hidden from quotation dropdowns; its ledger history is untouched.']);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET hotel/{id}/unmerge_preview — what "Undo merge" would put back (read-only)
    public function unmergePreview($id)
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->svc()->unmergePreview((int) $id)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST unmerge {hotel_id} — approval group: the merged duplicate becomes a hotel again
    public function unmerge()
    {
        if (!$this->canManage()) {
            return $this->denied('undo a merge');
        }
        try {
            $b = $this->body();
            $r = $this->svc()->unmergeHotel((int) ($b['hotel_id'] ?? 0));
            return $this->json(['status' => 200, 'message' => 'Merge undone: the hotel is back with ' . count($r['accounts']) . ' supplier code(s), ' . count($r['contracts']) . ' plan(s) and ' . $r['room_types'] . ' room type(s); the old screen rows of both hotels were rebuilt.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    public function locations()
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->svc()->locations()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function markets()
    {
        try {
            $svc = $this->svc();
            return $this->json(['status' => 200, 'data' => ['markets' => $svc->markets(), 'default' => $svc->settings()->get('default_market'), 'countries' => $svc->settings()->marketCountries()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET check?days=   (default: Settings check_days)
    public function check()
    {
        try {
            $db = \Config\Database::connect();
            $svc = $this->svc();
            $days = max(7, min(365, (int) ($this->request->getGet('days') ?: $svc->settings()->get('check_days'))));
            $expiryDays = (int) $svc->settings()->get('expiry_warning_days');
            $today = date('Y-m-d');
            $until = date('Y-m-d', strtotime("+{$days} days"));
            $nights = $db->query(
                "SELECT tq.quotation_id, c.manual_id, tq.tour_date, tq.hotel_acc, tq.hotel_name, tq.room_type, tq.meal_code, tq.dbl
                 FROM tour_quotations tq JOIN consignee c ON c.tour_id = tq.quotation_id
                 WHERE tq.hotel_acc <> '' AND tq.tour_date BETWEEN ? AND ? AND (c.tour_cancel IS NULL OR c.tour_cancel = 0)
                 ORDER BY tq.tour_date, c.manual_id",
                [$today, $until]
            )->getResultArray();
            $missing = [];
            $checked = 0;
            foreach ($nights as $n) {
                $checked++;
                $meal = strtolower((string) $n['meal_code']);
                if (!in_array($meal, ['ro', 'bb', 'hb', 'fb', 'ai'], true)) {
                    $meal = 'bb';
                }
                $r = $svc->lookup((string) $n['hotel_acc'], is_numeric($n['room_type']) ? (int) $n['room_type'] : (string) $n['room_type'], $meal, (string) $n['tour_date']);
                if (!$r) {
                    $key = $n['hotel_acc'] . '|' . $n['tour_date'];
                    $missing[$key] = ['manual_id' => $n['manual_id'], 'tour_date' => $n['tour_date'], 'hotel' => $n['hotel_name'], 'hotel_acc' => $n['hotel_acc'], 'room_type' => $n['room_type'], 'meal' => strtoupper($meal), 'quoted_dbl' => $n['dbl']];
                }
            }
            $expiring = $db->query(
                "SELECT c.id, c.name, c.valid_to, c.plan_type, h.id AS hotel_id, h.name AS hotel FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id
                 WHERE c.status = 'approved' AND c.plan_type IN ('contract','migrated') AND c.valid_to BETWEEN ? AND DATE_ADD(?, INTERVAL ? DAY)
                 AND NOT EXISTS (SELECT 1 FROM hr2_contract c2 WHERE c2.hotel_id = c.hotel_id AND c2.status = 'approved' AND c2.valid_from > c.valid_to)
                 ORDER BY c.valid_to",
                [$today, $today, $expiryDays]
            )->getResultArray();
            $closed = $db->query("SELECT c.id, c.name, c.book_to, h.id AS hotel_id, h.name AS hotel FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.status = 'approved' AND c.book_to IS NOT NULL AND c.book_to < ? AND c.valid_to >= ? ORDER BY c.book_to DESC", [$today, $today])->getResultArray();
            $flagged = $db->query(
                "SELECT h.id AS hotel_id, h.name AS hotel, c.id AS contract_id, c.name AS plan, s.name AS season, rt.name AS room_type, r.meal_plan, r.sgl, r.dbl, r.tpl, r.quality_flags
                 FROM hr2_rate r JOIN hr2_season s ON s.id = r.season_id JOIN hr2_contract c ON c.id = s.contract_id JOIN hr2_hotel h ON h.id = c.hotel_id JOIN hr2_room_type rt ON rt.id = r.room_type_id
                 WHERE r.quality_flags IS NOT NULL AND c.status = 'approved' AND c.valid_to >= ? ORDER BY h.name, s.sort_order LIMIT 500",
                [$today]
            )->getResultArray();
            $drafts = $db->query("SELECT c.id, c.name, c.updated_at, c.entered_by, h.id AS hotel_id, h.name AS hotel FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.status = 'draft' ORDER BY c.updated_at DESC LIMIT 100")->getResultArray();
            return $this->json(['status' => 200, 'data' => ['from' => $today, 'to' => $until, 'days' => $days, 'expiry_days' => $expiryDays, 'nights_checked' => $checked, 'missing' => array_values($missing), 'expiring' => $expiring, 'closed_windows' => $closed, 'flagged' => $flagged, 'drafts' => $drafts]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // ------------------------------------------------------------------
    //  Approval queue, dashboard summary, settings
    // ------------------------------------------------------------------

    // GET queue — drafts waiting for approval with their check counts, newest first
    public function queue()
    {
        try {
            $db = \Config\Database::connect();
            $svc = $this->svc();
            $drafts = $db->query(
                "SELECT c.id, c.name, c.plan_type, c.valid_from, c.valid_to, c.updated_at, c.entered_by, c.source_type, h.id AS hotel_id, h.name AS hotel,
                        (SELECT COUNT(*) FROM hr2_season s WHERE s.contract_id = c.id) AS seasons,
                        (SELECT COUNT(*) FROM hr2_rate r JOIN hr2_season s2 ON s2.id = r.season_id WHERE s2.contract_id = c.id) AS rate_rows
                 FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id
                 WHERE c.status = 'draft' ORDER BY c.updated_at DESC LIMIT 200"
            )->getResultArray();
            foreach ($drafts as &$d) {
                try {
                    $sheet = $svc->getContract((int) $d['id']);
                    $counts = RateValidator::count($sheet ? $svc->validateSheet($sheet) : []);
                } catch (\Throwable $e) {
                    $counts = ['errors' => 0, 'warnings' => 0];
                }
                $d['errors'] = $counts['errors'] ?? 0;
                $d['warnings'] = $counts['warnings'] ?? 0;
            }
            unset($d);
            // tf-hr-docs-r1: how many supporting documents each draft carries (its own + the hotel-wide ones)
            try {
                $docSvc = $this->docs();
                $own = $docSvc->countsForContracts(array_column($drafts, 'id'));
                $hw = [];
                foreach ($drafts as &$d) {
                    $hid = (int) $d['hotel_id'];
                    $hw[$hid] = $hw[$hid] ?? $docSvc->hotelWideCount($hid);
                    $d['docs'] = ($own[(int) $d['id']] ?? 0) + $hw[$hid];
                }
                unset($d);
            } catch (\Throwable $e) {
            }
            $recent = $db->query(
                "SELECT c.id, c.name, c.plan_type, c.approved_by, c.approved_at, h.id AS hotel_id, h.name AS hotel FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id
                 WHERE c.status = 'approved' AND c.approved_at IS NOT NULL ORDER BY c.approved_at DESC LIMIT 20"
            )->getResultArray();
            return $this->json(['status' => 200, 'data' => ['drafts' => $drafts, 'recently_approved' => $recent, 'can_approve' => $this->canApprove()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET summary — cheap counts for a dashboard tile / widget
    public function summary()
    {
        try {
            $db = \Config\Database::connect();
            $svc = $this->svc();
            $today = date('Y-m-d');
            $expiryDays = (int) $svc->settings()->get('expiry_warning_days');
            $one = static fn (string $sql, array $binds = []) => (int) ($db->query($sql, $binds)->getRowArray()['n'] ?? 0);
            $data = [
                'date' => $today,
                'hotels' => $one("SELECT COUNT(*) AS n FROM hr2_hotel WHERE status = 'active'"),
                'hotels_priced_today' => $one(
                    "SELECT COUNT(DISTINCT c.hotel_id) AS n FROM hr2_contract c JOIN hr2_season s ON s.contract_id = c.id JOIN hr2_season_date d ON d.season_id = s.id
                     WHERE c.status = 'approved' AND c.valid_from <= ? AND c.valid_to >= ? AND d.date_from <= ? AND d.date_to >= ?",
                    [$today, $today, $today, $today]
                ),
                'approved_plans' => $one("SELECT COUNT(*) AS n FROM hr2_contract WHERE status = 'approved' AND valid_to >= ?", [$today]),
                'drafts' => $one("SELECT COUNT(*) AS n FROM hr2_contract WHERE status = 'draft'"),
                'expiring' => $one(
                    "SELECT COUNT(*) AS n FROM hr2_contract c WHERE c.status = 'approved' AND c.plan_type IN ('contract','migrated') AND c.valid_to BETWEEN ? AND DATE_ADD(?, INTERVAL ? DAY)
                     AND NOT EXISTS (SELECT 1 FROM hr2_contract c2 WHERE c2.hotel_id = c.hotel_id AND c2.status = 'approved' AND c2.valid_from > c.valid_to)",
                    [$today, $today, $expiryDays]
                ),
                'expiry_days' => $expiryDays,
                'last_approval' => $db->query("SELECT MAX(approved_at) AS d FROM hr2_contract WHERE status = 'approved'")->getRowArray()['d'] ?? null,
                'can_approve' => $this->canApprove(),
                'panel_url' => base_url(self::PANEL_URL),
                'approval_url' => base_url(self::APPROVAL_URL),
            ];
            return $this->json(['status' => 200, 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /** Full Hotel Incomplete Work scan after a setup step; never throws. */
    private function workScan($db, string $user): ?array
    {
        try {
            return (new \App\Libraries\HotelRates\WorkQueueService($db, $user ?: 'panel'))->scan();
        } catch (\Throwable $e) {
            return null;
        }
    }

    // POST setup {step: install | dry_run | migrate | load_plans | cost_backfill | load_work_items | work_scan}   (approval group, System Administrator only)
    // The same work as `php spark hotelrates:install --menu` / `hotelrates:migrate`, for servers where spark is awkward.
    public function setup()
    {
        if (!$this->isSysAdmin()) {
            return $this->json(['status' => 403, 'message' => 'Only System Administrator can install or migrate the Hotel Rates tables.']);
        }
        try {
            $b = $this->body();
            $step = (string) ($b['step'] ?? '');
            $db = \Config\Database::connect();
            $user = (string) session()->get('username');
            if ($step === 'install') {
                $res = \App\Libraries\HotelRates\Installer::install($db, APPPATH . 'Database/Sql');
                $menu = \App\Libraries\HotelRates\Installer::runSqlFile($db, APPPATH . 'Database/Sql/hotel_rates_v2_menu.sql');
                $scan = $this->workScan($db, $user);
                $msg = "Tables ready (schema " . \App\Libraries\HotelRates\Installer::SCHEMA_VERSION . ", {$res['tables']} hr2_ tables). Menu entries 'Hotel Rates (new)', 'Hotel Rate Approval' and 'Hotel Incomplete Work' added under Master Files for System Administrator — log out and in to see them." . ($scan ? " Incomplete work: {$scan['open']} open item(s)." : '');
                return $this->json(['status' => 200, 'message' => $msg, 'data' => $res + ['menu_statements' => $menu, 'work' => $scan]]);
            }
            if ($step === 'dry_run' || $step === 'migrate') {
                if (\App\Libraries\HotelRates\Installer::installedVersion($db) !== \App\Libraries\HotelRates\Installer::SCHEMA_VERSION) {
                    return $this->json(['status' => 400, 'message' => 'Install the tables first.']);
                }
                $report = (new \App\Libraries\HotelRates\LegacyMigrator($db, $user ?: 'panel'))->run($step === 'dry_run');
                $skipped = $report['skipped'];
                $report['skipped'] = array_slice($skipped, 0, 50);
                $report['skipped_total'] = count($skipped);
                $msg = ($step === 'dry_run' ? 'Dry run — nothing written. ' : '') . "Hotels: {$report['hotels_created']} created, {$report['hotels_existing']} already there; {$report['plans_created']} migrated plan(s), {$report['seasons_created']} season(s), {$report['rate_rows_created']} rate row(s). Legacy rows: {$report['legacy_rows_total']} total, {$report['legacy_rows_mapped']} copied, {$report['legacy_rows_skipped']} skipped.";
                return $this->json(['status' => 200, 'message' => $msg, 'data' => $report]);
            }
            if ($step === 'retire_old_screen') {
                // hotel-dup-r1: the old Hotel Rate screen's menu entries are soft-deleted (del = 1, reversible); privileges untouched
                $n = 0;
                foreach (['master_files/hotel_rate', 'master_files/hotel_rate/all'] as $u) {
                    $db->query('UPDATE menu_list SET del = 1 WHERE url = ? AND del = 0', [$u]);
                    $n += (int) $db->affectedRows();
                }
                $db->query("UPDATE menu_list SET name = 'Hotel Rates' WHERE url = 'master_files/hotel_rate_v2' AND del = 0 AND name = 'Hotel Rates (new)'");   // no "(new)" once the old screen is gone
                $this->svc()->audit('settings', 0, 'update', null, ['old_hotel_rate_screen' => 'retired', 'menu_rows_hidden' => $n, 'by' => $user]);
                return $this->json(['status' => 200, 'message' => "Old Hotel Rate screen retired: {$n} menu entr" . ($n === 1 ? 'y' : 'ies') . ' hidden (reversible: set del = 0 on menu_list). Rates are entered on the Hotel Rates panel only.']);
            }
            if ($step === 'cost_backfill') {
                // price every confirmed tour that still has hotel nights from today on (or all confirmed tours with {all: true})
                $all = !empty($b['all']);
                $cost = new \App\Libraries\HotelRates\HotelCostService($db, $user ?: 'panel');
                $tours = $db->query("SELECT DISTINCT c.tour_id FROM consignee c JOIN tour_quotations tq ON tq.quotation_id = c.tour_id
                                      WHERE c.verify = 1 AND COALESCE(c.tour_cancel, 0) <> 1 AND tq.hotel_acc <> ''" . ($all ? '' : ' AND tq.tour_date >= CURDATE()'))->getResultArray();
                $done = 0; $failed = [];
                foreach ($tours as $t) {
                    try {
                        $cost->computeTour((string) $t['tour_id']);
                        $done++;
                    } catch (\Throwable $e) {
                        $failed[] = $t['tour_id'] . ': ' . $e->getMessage();
                    }
                }
                $scan = $this->workScan($db, $user);
                return $this->json(['status' => 200, 'message' => "Hotel costs computed for {$done} confirmed tour(s)" . ($failed ? ', ' . count($failed) . ' failed' : '') . '.' . ($scan ? " Incomplete work: {$scan['open']} open item(s)." : ''), 'data' => ['done' => $done, 'failed' => array_slice($failed, 0, 20), 'work' => $scan]]);
            }
            if ($step === 'load_plans') {
                // Transcribed rate sheets dropped into writable/hotel_rates_entry/*.json (the shape lib.plan() writes:
                // {key, hotel:{acc,name}, sheet:{...}}) are saved as DRAFTS for the approval queue. Existing plans with
                // the same hotel + name are left alone unless {replace_drafts: true} (then a draft of that name is updated).
                $dir = WRITEPATH . 'hotel_rates_entry';
                $files = is_dir($dir) ? glob($dir . '/*.json') : [];
                sort($files);
                $only = array_filter(array_map('trim', explode(',', (string) ($b['only'] ?? ''))));
                $replace = !empty($b['replace_drafts']);
                $svc = $this->svc();
                $out = ['saved' => [], 'updated' => [], 'exists' => [], 'no_supplier' => [], 'unknown_code' => [], 'failed' => []];
                foreach ($files as $f) {
                    $doc = json_decode((string) file_get_contents($f), true);
                    if (!is_array($doc) || empty($doc['sheet'])) {
                        $out['failed'][] = basename($f) . ': not a plan file';
                        continue;
                    }
                    $key = (string) ($doc['key'] ?? basename($f, '.json'));
                    if ($only && !in_array($key, $only, true)) {
                        continue;
                    }
                    $acc = (string) ($doc['hotel']['acc'] ?? '');
                    if ($acc === '') {
                        $out['no_supplier'][] = $key . ' — ' . ($doc['hotel']['name'] ?? '');
                        continue;
                    }
                    $h = $svc->findHotelByAcc($acc);
                    if (!$h) {
                        $out['unknown_code'][] = $key . ' — ' . $acc;
                        continue;
                    }
                    $sheet = $doc['sheet'];
                    $sheet['hotel'] = ['id' => (int) $h['id'], 'name' => $h['name'], 'sup_acc_no' => $acc];
                    $name = (string) ($sheet['contract']['name'] ?? '');
                    $existing = $db->query("SELECT id, status FROM hr2_contract WHERE hotel_id = ? AND name = ? AND status IN ('draft','approved') ORDER BY id DESC LIMIT 1", [(int) $h['id'], $name])->getRowArray();
                    if ($existing && !($replace && $existing['status'] === 'draft')) {
                        $out['exists'][] = $key . ' — #' . $existing['id'] . ' (' . $existing['status'] . ')';
                        continue;
                    }
                    if ($existing) {
                        $sheet['contract']['id'] = (int) $existing['id'];
                    }
                    try {
                        $res = $svc->saveContract($sheet, ['acknowledge_warnings' => true]);
                        $c = RateValidator::count($res['issues']);
                        $line = $key . ' — #' . $res['contract_id'] . ' ' . $h['name'] . " (E{$c['errors']} W{$c['warnings']})";
                        if ($res['saved']) {
                            $out[$existing ? 'updated' : 'saved'][] = $line;
                        } else {
                            $out['failed'][] = $line;
                        }
                    } catch (\Throwable $e) {
                        $out['failed'][] = $key . ' — ' . $e->getMessage();
                    }
                }
                $scan = $this->workScan($db, $user);
                $msg = count($out['saved']) . ' plan(s) saved as drafts, ' . count($out['updated']) . ' draft(s) updated, ' . count($out['exists']) . ' already present, ' . count($out['no_supplier']) . ' without a supplier record, ' . count($out['unknown_code']) . ' with an unknown code, ' . count($out['failed']) . ' failed.' . ($scan ? " Incomplete work: {$scan['open']} open item(s)." : '');
                return $this->json(['status' => 200, 'message' => $msg, 'data' => $out + ['work' => $scan]]);
            }
            if ($step === 'load_work_items') {
                // hand-registered gaps (rate-sheet queries, merges to confirm …) from writable/hotel_rates_entry/work_items.json
                $res = (new \App\Libraries\HotelRates\WorkQueueService($db, $user ?: 'panel'))->loadSeedFile(WRITEPATH . 'hotel_rates_entry/work_items.json');
                return $this->json(['status' => 200, 'message' => "{$res['loaded']} work item(s) registered" . ($res['failed'] ? ', ' . count($res['failed']) . ' failed' : '') . '.', 'data' => $res]);
            }
            if ($step === 'work_scan') {
                $scan = $this->workScan($db, $user);
                return $this->json(['status' => 200, 'message' => $scan ? "Scan finished: {$scan['new']} new, {$scan['closed']} closed, {$scan['open']} open." : 'Scan failed.', 'data' => $scan]);
            }
            return $this->json(['status' => 400, 'message' => 'Unknown step.']);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET settings — definitions + values + country → market list (read-only for everyone with the panel)
    public function settings()
    {
        try {
            $svc = $this->svc();
            $st = $svc->settings();
            return $this->json(['status' => 200, 'data' => [
                'settings' => $st->describe($svc->markets()),
                'values' => $st->values(),
                'markets' => $svc->markets(),
                'countries' => $st->marketCountries(),
                'can_edit' => $this->canApprove(),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST settings {values: {key: value}, countries: [{country_iso, market_code, note?}]}   (approval group)
    public function saveSettings()
    {
        if (!$this->canApprove()) {
            return $this->denied('change the Hotel Rates settings');
        }
        try {
            $b = $this->body();
            $svc = $this->svc();
            $st = $svc->settings();
            $out = ['saved' => [], 'errors' => [], 'countries_saved' => 0, 'country_errors' => []];
            if (is_array($b['values'] ?? null) && $b['values']) {
                $r = $st->save($b['values']);
                $out['saved'] = $r['saved'];
                $out['changed'] = array_keys($r['changed']);
                $out['errors'] = $r['errors'];
                if ($r['changed']) {
                    $svc->audit('settings', 0, 'update', array_map(static fn ($c) => $c[0], $r['changed']), array_map(static fn ($c) => $c[1], $r['changed']));
                }
            }
            if (is_array($b['countries'] ?? null) && $b['countries']) {
                $r = $st->saveMarketCountries($b['countries'], $svc->markets(), (string) session()->get('username'));
                $out['countries_saved'] = $r['saved'];
                $out['country_errors'] = $r['errors'];
                $svc->audit('market_country', 0, 'update', null, ['saved' => $r['saved'], 'rows' => array_map(static fn ($x) => ($x['country_iso'] ?? '') . '=' . ($x['market_code'] ?? ''), $b['countries'])]);
            }
            $ok = !$out['errors'] && !$out['country_errors'];
            $msg = $ok ? 'Settings saved.' : 'Some values were not saved — see the messages next to the fields.';
            if ($ok && in_array('default_market', $out['changed'] ?? [], true)) {
                $msg .= ' The default market changed: run "php spark hotelrates:project" (or Refresh old screen per hotel) so the old Hotel Rate rows follow it.';
            }
            return $this->json(['status' => $ok ? 200 : 400, 'message' => $msg, 'data' => $out + ['values' => $st->values(), 'countries' => $st->marketCountries()]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }
}
