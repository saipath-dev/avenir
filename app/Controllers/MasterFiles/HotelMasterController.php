<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\MenuLoader;

/**
 * Hotel Master — add, edit or remove hotels   (master_files/hotel_master)
 * hotel-dup-r1, 27 Oct 2026 (Shantha)
 *
 * One list of every hotel by code and name. Everyone with the menu entry may look;
 * the changes are Management's (Tour Desk Manage privilege, or System Administrator):
 *   add      — opens Master Files → New Supplier with the Hotel category (the supplier
 *              account and the HTL code are born together; a similar name is warned there)
 *   edit     — name (also on the supplier record and the ledger name), location, chain,
 *              contacts, notes
 *   merge    — a genuine duplicate folded into the correct hotel (undoable)
 *   archive  — a hotel with history leaves the pickers; everything on file stays
 *   delete   — only a record nothing refers to
 * Every merge, archive and delete shows the dependencies first and needs the typed word
 * (MERGE / ARCHIVE / DELETE); every action is written to hr2_audit. Deletion controls live
 * here and nowhere else.
 */
class HotelMasterController extends BaseController
{
    public const URL = 'master_files/hotel_master';

    private ?array $menuData = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function svc(): HotelRateService
    {
        return new HotelRateService(\Config\Database::connect(), $this->user());
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

    /** Management: the Tour Desk Manage entry (tour/manage) on the user's group, or System Administrator. */
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

    /** Adding and deleting a hotel are reserved to the named account(s) (Settings on this page, System Administrator sets them). */
    private function isOwner(): bool
    {
        return $this->svc()->isHotelMasterOwner($this->user());
    }

    private function isSysAdmin(): bool
    {
        return (int) ($this->menuData()['user_role'] ?? 0) === 1;
    }

    /** The signed-in user's own password, checked against the login hash; the value is never stored or logged. */
    private function passwordOk(string $password): bool
    {
        if ($password === '') {
            return false;
        }
        $u = (new \App\Models\UserModel())->where('id', (int) session()->get('user_id'))->first();
        return $u && !empty($u['pass']) && password_verify($password, (string) $u['pass']);
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

    private function fail(\Throwable $e, int $status = 400)
    {
        return $this->json(['status' => $status, 'message' => $e->getMessage()]);
    }

    private function denied(string $what)
    {
        return $this->json(['status' => 403, 'message' => "{$what} needs the Tour Desk Manage privilege (Management). Ask Management."]);
    }

    private function confirmWord(array $b, string $word)
    {
        if (strtoupper(trim((string) ($b['confirm'] ?? ''))) !== $word) {
            return $this->json(['status' => 400, 'message' => "Type {$word} to confirm."]);
        }
        return null;
    }

    // GET master_files/hotel_master
    public function index()
    {
        $data = $this->menuData();
        $data['user'] = $this->user();
        $data['can_manage'] = $this->canManage();
        $data['can_add_delete'] = $this->canManage() && $this->isOwner();
        $data['is_sysadmin'] = $this->isSysAdmin();
        $data['owners'] = $this->svc()->hotelMasterOwners();
        $data['master_url'] = self::URL;
        $data['panel_url'] = HotelRateV2Controller::PANEL_URL;
        $data['new_supplier_url'] = 'master_files/supplier?cat=1';
        $data['installed'] = false;
        try {
            $db = \Config\Database::connect();
            $version = \App\Libraries\HotelRates\Installer::installedVersion($db);
            $data['installed'] = $version !== null && $version >= \App\Libraries\HotelRates\Installer::SCHEMA_VERSION;
        } catch (\Throwable $e) {
            $data['installed'] = false;
        }
        $data['preselect'] = (int) $this->request->getGet('hotel');
        return view('pages/master_files/hotel_master', $data);
    }

    // GET list — every hotel (active, inactive and merged), with what hangs on it
    public function list()
    {
        try {
            $db = \Config\Database::connect();
            $rows = $db->query("SELECT h.id, h.hotel_code, h.name, h.status, h.merged_into_id, h.primary_sup_acc_no, h.chain, h.updated_at, h.created_at,
                    l.name AS location_name, m.name AS merged_into_name, m.hotel_code AS merged_into_code,
                    COALESCE(ca.n, 0) AS plans_approved, COALESCE(cd.n, 0) AS plans_draft, COALESCE(tq.n, 0) AS nights_last_year, COALESCE(tqa.n, 0) AS nights_all
                FROM hr2_hotel h
                LEFT JOIN hr2_location l ON l.id = h.location_id
                LEFT JOIN hr2_hotel m ON m.id = h.merged_into_id
                LEFT JOIN (SELECT hotel_id, COUNT(*) AS n FROM hr2_contract WHERE status = 'approved' GROUP BY hotel_id) ca ON ca.hotel_id = h.id
                LEFT JOIN (SELECT hotel_id, COUNT(*) AS n FROM hr2_contract WHERE status = 'draft' GROUP BY hotel_id) cd ON cd.hotel_id = h.id
                LEFT JOIN (SELECT a.hotel_id, COUNT(*) AS n FROM tour_quotations q JOIN hr2_hotel_account a ON a.sup_acc_no = q.hotel_acc WHERE q.tour_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY) GROUP BY a.hotel_id) tq ON tq.hotel_id = h.id
                LEFT JOIN (SELECT a.hotel_id, COUNT(*) AS n FROM tour_quotations q JOIN hr2_hotel_account a ON a.sup_acc_no = q.hotel_acc GROUP BY a.hotel_id) tqa ON tqa.hotel_id = h.id
                ORDER BY h.name")->getResultArray();
            return $this->json(['status' => 200, 'data' => $rows, 'can_manage' => $this->canManage()]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // GET hotel/{id} — the record, its dependencies, room types, duplicates suggestion and history
    public function hotel($id)
    {
        try {
            $svc = $this->svc();
            $h = $svc->getHotel((int) $id);
            if (!$h) {
                return $this->json(['status' => 404, 'message' => 'Hotel not found.']);
            }
            return $this->json(['status' => 200, 'data' => [
                'hotel' => $h,
                'dependencies' => $svc->hotelDependencies((int) $id),
                'room_types' => $svc->roomTypes((int) $id),
                'similar' => $svc->similarHotels($h['name'], (int) $id),
                'audit' => $svc->auditTrail('hotel', (int) $id, 40),
                'locations' => $svc->locations(),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET similar?name=&exclude=
    public function similar()
    {
        try {
            $name = trim((string) $this->request->getGet('name'));
            return $this->json(['status' => 200, 'data' => $name === '' ? [] : $this->svc()->similarHotels($name, (int) $this->request->getGet('exclude'))]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST hotel/{id}/update {name?, location_name?, chain?, star_category?, grade?, address?, website?, reservations_email?, reservations_phone?, sales_contact?, accounts_email?, phone?, notes?}
    public function update($id)
    {
        if (!$this->canManage()) {
            return $this->denied('Editing a hotel');
        }
        try {
            $b = $this->body();
            $svc = $this->svc();
            $allowed = ['chain', 'star_category', 'grade', 'address', 'website', 'reservations_email', 'reservations_phone', 'sales_contact', 'accounts_email', 'phone', 'check_in_time', 'check_out_time', 'currency', 'tax_note', 'notes'];
            $fields = array_intersect_key($b, array_flip($allowed));
            if (array_key_exists('location_name', $b)) {
                $fields['location_id'] = trim((string) $b['location_name']) === '' ? null : $svc->ensureLocation((string) $b['location_name']);
            }
            $msg = [];
            if (array_key_exists('name', $b)) {
                $r = $svc->renameHotel((int) $id, (string) $b['name']);
                if ($r['renamed']) {
                    $msg[] = "renamed \"{$r['old_name']}\" → \"{$r['name']}\" (Hotel Master, supplier record" . ($r['accounts'] ? ' ' . implode(', ', $r['accounts']) : '') . ', ledger name)';
                }
            }
            if ($fields) {
                $svc->updateHotel((int) $id, $fields);
                $msg[] = 'details saved';
            }
            return $this->json(['status' => 200, 'message' => $msg ? ucfirst(implode('; ', $msg)) . '. Written to the audit log.' : 'Nothing changed.']);
        } catch (\RuntimeException $e) {
            return $this->fail($e, 409);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST hotel/{id}/merge {keep_id, confirm:'MERGE', note}
    public function merge($id)
    {
        if (!$this->canManage()) {
            return $this->denied('Removing a duplicate (merging hotels)');
        }
        try {
            $b = $this->body();
            if ($r = $this->confirmWord($b, 'MERGE')) {
                return $r;
            }
            $svc = $this->svc();
            $keep = $svc->getHotel((int) ($b['keep_id'] ?? 0));
            $dup = $svc->getHotel((int) $id);
            if (!$keep || !$dup) {
                return $this->json(['status' => 404, 'message' => 'Hotel not found.']);
            }
            $svc->mergeHotels((int) $keep['id'], (int) $id);
            if (!empty($b['note'])) {
                $svc->audit('hotel', (int) $id, 'update', null, ['merge_note' => trim((string) $b['note']), 'merged_into' => (int) $keep['id']]);
            }
            return $this->json(['status' => 200, 'message' => "\"{$dup['name']}\" ({$dup['hotel_code']}) was folded into \"{$keep['name']}\" ({$keep['hotel_code']}): its plans, room types and supplier code moved across; it is hidden from the pickers. Logged; can be undone.", 'data' => ['keep_id' => (int) $keep['id']]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST hotel/{id}/unmerge {confirm:'UNMERGE'}
    public function unmerge($id)
    {
        if (!$this->canManage()) {
            return $this->denied('Undoing a merge');
        }
        try {
            $b = $this->body();
            if ($r = $this->confirmWord($b, 'UNMERGE')) {
                return $r;
            }
            $r = $this->svc()->unmergeHotel((int) $id);
            return $this->json(['status' => 200, 'message' => 'Merge undone: the hotel is back with ' . count($r['accounts']) . ' supplier code(s), ' . count($r['contracts']) . ' plan(s) and ' . $r['room_types'] . ' room type(s).', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST hotel/{id}/archive {confirm:'ARCHIVE', note}  ·  POST hotel/{id}/restore {}
    public function archive($id)
    {
        if (!$this->canManage()) {
            return $this->denied('Archiving a hotel');
        }
        try {
            $b = $this->body();
            if ($r = $this->confirmWord($b, 'ARCHIVE')) {
                return $r;
            }
            $svc = $this->svc();
            $d = $svc->hotelDependencies((int) $id);
            $r = $svc->setHotelArchived((int) $id, true, trim((string) ($b['note'] ?? '')));
            return $this->json(['status' => 200, 'message' => $r['changed'] ? "\"{$d['name']}\" ({$d['hotel_code']}) is archived: it leaves the hotel pickers; its {$d['plans_approved']} approved plan(s), {$d['quotation_nights']} night(s) on file, payments and vouchers are untouched. Logged; restorable." : 'Already archived.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function restore($id)
    {
        if (!$this->canManage()) {
            return $this->denied('Restoring a hotel');
        }
        try {
            $r = $this->svc()->setHotelArchived((int) $id, false);
            return $this->json(['status' => 200, 'message' => $r['changed'] ? "\"{$r['name']}\" is active again. Logged." : 'Already active.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET hotel/{id}/removal — exactly what a deletion would remove and what stays (read-only)
    public function removal($id)
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->svc()->removalPreview((int) $id), 'can_add_delete' => $this->canManage() && $this->isOwner()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST owners {owners:'user1, user2'} — System Administrator: the accounts allowed to add or delete a hotel
    public function owners()
    {
        if (!$this->isSysAdmin()) {
            return $this->json(['status' => 403, 'message' => 'Only System Administrator sets who may add or delete hotels.']);
        }
        try {
            $b = $this->body();
            $svc = $this->svc();
            $old = $svc->hotelMasterOwners();
            $new = array_values(array_filter(array_map('trim', explode(',', (string) ($b['owners'] ?? '')))));
            $svc->setMeta(HotelRateService::OWNERS_KEY, implode(', ', $new));
            $svc->audit('settings', 0, 'update', ['hotel_master_owners' => $old], ['hotel_master_owners' => $new]);
            return $this->json(['status' => 200, 'message' => 'Add / delete hotel is now reserved to: ' . (implode(', ', $new) ?: 'nobody') . '.', 'data' => $new]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST hotel/{id}/delete {confirm:'DELETE', reason, password, include_rates}
    //   reserved to the owner account(s); the reason is required; the signed-in user's own password is checked;
    //   refused unless nothing depends on the record (approved rates, bookings, quotations, vouchers, payments, inquiries)
    public function delete($id)
    {
        if (!$this->canManage() || !$this->isOwner()) {
            return $this->json(['status' => 403, 'message' => 'Deleting a hotel is reserved to ' . (implode(', ', $this->svc()->hotelMasterOwners()) ?: 'no account yet') . '.']);
        }
        try {
            $b = $this->body();
            if ($r = $this->confirmWord($b, 'DELETE')) {
                return $r;
            }
            $reason = trim((string) ($b['reason'] ?? $b['note'] ?? ''));
            if (mb_strlen($reason) < 5) {
                return $this->json(['status' => 400, 'message' => 'A reason is required (it goes to the audit log).']);
            }
            $prev = $this->svc()->removalPreview((int) $id);
            if (!$prev['can_delete']) {
                return $this->json(['status' => 400, 'message' => 'This hotel cannot be deleted: ' . implode('; ', $prev['blocks']) . '. Archive it instead.']);
            }
            if ($prev['has_rates'] && empty($b['include_rates'])) {
                return $this->json(['status' => 400, 'message' => 'This hotel has ' . implode(', ', $prev['removable']) . '. Tick "Include its rate plans and old-screen rows" to delete them with it, or archive the hotel instead.']);
            }
            if (!$this->passwordOk((string) ($b['password'] ?? ''))) {
                $this->svc()->audit('hotel', (int) $id, 'update', null, ['delete_refused' => 'password not recognised', 'reason' => $reason]);
                return $this->json(['status' => 403, 'message' => 'Password not recognised. Nothing was deleted.']);
            }
            $r = $this->svc()->deleteHotel((int) $id, $reason, !empty($b['include_rates']));
            return $this->json(['status' => 200, 'message' => "{$r['name']} ({$r['hotel_code']}) was deleted. Nothing referred to it; the whole record is kept in the audit log and its code number is never reused. Its supplier account " . implode(', ', $r['accounts']) . ' is marked not available in Master Files.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST hotel/{id}/room_type/{rtId}/delete {confirm:'DELETE', note}
    public function deleteRoomType($id, $rtId)
    {
        if (!$this->canManage()) {
            return $this->denied('Deleting a room type');
        }
        try {
            $b = $this->body();
            if ($r = $this->confirmWord($b, 'DELETE')) {
                return $r;
            }
            $svc = $this->svc();
            if (!array_filter($svc->roomTypes((int) $id), static fn ($r) => (int) $r['id'] === (int) $rtId)) {
                return $this->json(['status' => 404, 'message' => 'That room type is not on this hotel.']);
            }
            $r = $svc->deleteRoomType((int) $rtId, trim((string) ($b['note'] ?? '')));
            return $this->json(['status' => 200, 'message' => "Room type \"{$r['name']}\" deleted; nothing referred to it. Logged."]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
