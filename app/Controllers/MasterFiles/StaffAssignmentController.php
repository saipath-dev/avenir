<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Staff;
use App\Libraries\TourFlow\StaffAssignment;

/**
 * Master Files → Staff Assignment — master_files/staff (tf-stage10, 30 Oct 2026).
 * Super Admin only: every method checks the signed-in group itself, over and above the menu privilege (granted to the System
 * Administrator alone at install) — a direct URL or request from any other account answers 403 and is logged by the filter.
 * The panel lists every account for review, brings an account onto the panel (all Blocked), switches one row at a time with a
 * reason, offers group shortcuts that never touch the payment roles, changes status (sessions ended at once) and shows the
 * append-only history. Nothing here creates an account or a password.
 */
class StaffAssignmentController extends BaseController
{
    public const URL = StaffAssignment::URL;

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

    private function name(): string
    {
        return Staff::displayName($this->db(), $this->userId(), $this->user());
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

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    /** The one gate: only the System Administrator group (the Super Admin) — never by menu grant alone. */
    private function refusal()
    {
        if ((int) session()->get('group_id') === 1) {
            return null;
        }
        return $this->json(['status' => 403, 'message' => 'Staff Assignment is for the Super Admin only.'], 403);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : ($this->request->getPost() ?: []);
    }

    private function out(array $r)
    {
        $r['status'] = !empty($r['ok']) ? 200 : (!empty($r['refused']) ? 409 : 422);
        return $this->json($r, $r['status'] === 200 ? 200 : $r['status']);
    }

    public function index()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $data = $this->menuData();
        $data['page_url']     = self::URL;
        $data['installed']    = StaffAssignment::installed($this->db());
        $data['departments']  = StaffAssignment::DEPARTMENTS;
        $data['role_names']   = StaffAssignment::ROLE_NAMES;
        $data['reason_min']   = StaffAssignment::REASON_MIN;
        $data['open_staff']   = (int) ($this->request->getGet('staff') ?? 0);
        return view('pages/master_files/staff_assignment', $data);
    }

    /** Every account, for the review list. */
    public function accounts()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        return $this->json(['status' => 200, 'accounts' => StaffAssignment::accounts($this->db())]);
    }

    /** Every live menu entry (the rows of the permission panel). */
    public function menus()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        return $this->json(['status' => 200, 'menus' => StaffAssignment::menus($this->db())]);
    }

    /** One staff record with its permissions and history. */
    public function one()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $db = $this->db();
        $id = (int) $this->request->getGet('id');
        $s = StaffAssignment::staff($db, $id);
        if (!$s) {
            return $this->json(['status' => 404, 'message' => 'No such staff record.'], 404);
        }
        return $this->json(['status' => 200, 'staff' => $s, 'permissions' => StaffAssignment::permissions($db, $id), 'history' => StaffAssignment::history($db, $id, 200)]);
    }

    public function history()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $id = (int) ($this->request->getGet('staff') ?? 0);
        return $this->json(['status' => 200, 'history' => StaffAssignment::history($this->db(), $id ?: null, 500)]);
    }

    public function add()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        return $this->out(StaffAssignment::addToPanel($this->db(), (int) ($b['user_id'] ?? 0), (string) ($b['department'] ?? ''), (string) ($b['responsibilities'] ?? ''), (string) ($b['reason'] ?? ''), $this->user(), $this->name()));
    }

    public function remove()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        return $this->out(StaffAssignment::removeFromPanel($this->db(), (int) ($b['staff_id'] ?? 0), (string) ($b['reason'] ?? ''), $this->user(), $this->name()));
    }

    public function label()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        return $this->out(StaffAssignment::label($this->db(), (int) ($b['staff_id'] ?? 0), (string) ($b['department'] ?? ''), (string) ($b['responsibilities'] ?? ''), (string) ($b['reason'] ?? ''), $this->user(), $this->name()));
    }

    /** One row Open / Blocked, with a reason. */
    public function set()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        return $this->out(StaffAssignment::set($this->db(), (int) ($b['staff_id'] ?? 0), (int) ($b['menu_id'] ?? 0), (string) ($b['state'] ?? ''), (string) ($b['reason'] ?? ''), $this->user(), $this->name()));
    }

    /** A group shortcut: payment roles and protected rows are never opened by it. */
    public function bulk()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        $ids = is_array($b['menu_ids'] ?? null) ? $b['menu_ids'] : [];
        if (!$ids) {
            return $this->json(['status' => 422, 'message' => 'No rows given.'], 422);
        }
        return $this->out(StaffAssignment::bulk($this->db(), (int) ($b['staff_id'] ?? 0), $ids, (string) ($b['state'] ?? ''), (string) ($b['reason'] ?? ''), $this->user(), $this->name()));
    }

    public function status()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        return $this->out(StaffAssignment::status($this->db(), (int) ($b['staff_id'] ?? 0), (string) ($b['status'] ?? ''), (string) ($b['reason'] ?? ''), $this->user(), $this->name()));
    }

    /** The access list as CSV (read-only record for the Super Admin's files). */
    public function export()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $rows = StaffAssignment::exportRows($this->db());
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['kind', 'sign-in', 'name', 'group', 'enabled', 'on panel', 'status', 'department', 'page / action', 'url', 'state', 'reason', 'set by', 'set at']);
        foreach ($rows as $r) {
            fputcsv($fh, array_values($r));
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8')->setHeader('Content-Disposition', 'attachment; filename="staff-access-list-' . date('Ymd-Hi') . '.csv"')->setBody("\xEF\xBB\xBF" . $csv);
    }
}
