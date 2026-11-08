<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Staff;
use App\Libraries\TourFlow\StaffAssignment;
use App\Libraries\TourFlow\StaffPositions;

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
        $data['positions_installed'] = StaffPositions::installed($this->db());   // tf-positions-r1
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

    // ---------------------------------------------------------------- tf-positions-r1: position first, then the person

    /** Every position with its current holder. */
    public function positions()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $db = $this->db();
        if (!StaffPositions::installed($db)) {
            return $this->json(['status' => 200, 'installed' => false, 'positions' => []]);
        }
        return $this->json(['status' => 200, 'installed' => true, 'positions' => StaffPositions::positions($db)]);
    }

    public function positionCreate()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        return $this->out(StaffPositions::create($this->db(), (string) ($b['title'] ?? ''), (string) ($b['department'] ?? ''), (string) ($b['responsibilities'] ?? ''), (string) ($b['reason'] ?? ''), $this->user(), $this->name()));
    }

    public function positionAssign()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        return $this->out(StaffPositions::assign($this->db(), (int) ($b['position_id'] ?? 0), (int) ($b['staff_id'] ?? 0), (string) ($b['reason'] ?? ''), $this->user(), $this->name()));
    }

    public function positionEnd()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        return $this->out(StaffPositions::end($this->db(), (int) ($b['position_id'] ?? 0), (string) ($b['reason'] ?? ''), $this->user(), $this->name()));
    }

    // ---------------------------------------------------------------- tf-panel2-r1: the redesigned Staff Access Control Panel

    /** GET map — every page and action, arranged in Travel Sales / Ground Handling / Accounts / Shared & Administration. */
    public function map()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        return $this->json(['status' => 200, 'categories' => \App\Libraries\TourFlow\StaffTaskMap::build($this->db()), 'role_names' => StaffAssignment::ROLE_NAMES]);
    }

    /** GET person?id=<staff id> — the person, their saved settings, and the last logged change of every row. */
    public function person()
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
        $last = [];
        foreach ($db->query('SELECT l.menu_id, l.action, l.to_state, l.reason, l.by_name, l.by_user, l.at FROM tf_staff_log l
                             JOIN (SELECT menu_id, MAX(id) mid FROM tf_staff_log WHERE staff_id = ? AND menu_id IS NOT NULL GROUP BY menu_id) x ON x.mid = l.id', [$id])->getResultArray() as $r) {
            $last[(int) $r['menu_id']] = $r;
        }
        return $this->json(['status' => 200, 'staff' => $s, 'permissions' => StaffAssignment::permissions($db, $id), 'last' => $last]);
    }

    /** GET category?cat=sales|ground|acc|shared — who holds access in a work category (people with permission, not who is signed in). */
    public function category()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $db = $this->db();
        $key = (string) $this->request->getGet('cat');
        $cat = null;
        foreach (\App\Libraries\TourFlow\StaffTaskMap::build($db) as $c) {
            if ($c['key'] === $key) {
                $cat = $c;
            }
        }
        if (!$cat) {
            return $this->json(['status' => 404, 'message' => 'No such category.'], 404);
        }
        $tasks = array_values(array_filter($cat['tasks'], static fn ($t) => empty($t['locked'])));
        $people = ['enabled' => [], 'inactive' => [], 'admins' => []];
        foreach (StaffAssignment::accounts($db) as $a) {
            if ($a['is_admin']) {
                $people['admins'][] = ['name' => $a['name'], 'username' => $a['username'], 'enabled' => $a['enabled']];
                continue;
            }
            if (!$a['on_panel'] || !$a['staff_id']) {
                continue;
            }
            $open = [];
            foreach (StaffAssignment::permissions($db, (int) $a['staff_id']) as $mid => $p) {
                if ($p['state'] === 'open') {
                    $open[(int) $mid] = true;
                }
            }
            $held = [];
            foreach ($tasks as $t) {
                $n = 0;
                $tot = 0;
                foreach ($t['screens'] as $sc) {
                    $tot++;
                    $ids = array_merge([$sc['id']], $sc['same_ids']);
                    foreach ($ids as $i) {
                        if (isset($open[$i])) {
                            $n++;
                            break;
                        }
                    }
                }
                if ($n > 0) {
                    $held[] = ['task' => $t['title'], 'open' => $n, 'of' => $tot];
                }
            }
            if (!$held) {
                continue;
            }
            $row = ['name' => $a['name'], 'username' => $a['username'], 'staff_id' => $a['staff_id'], 'group' => $a['group'], 'tasks' => $held,
                'coverage' => count($held) === count($tasks) ? 'every' : 'some', 'task_count' => count($tasks),
                'state' => !$a['enabled'] ? 'Sign-in disabled' : ($a['status'] !== 'active' ? ucfirst((string) $a['status']) : 'Enabled')];
            if ($a['enabled'] && $a['status'] === 'active') {
                $people['enabled'][] = $row;
            } else {
                $people['inactive'][] = $row;
            }
        }
        return $this->json(['status' => 200, 'category' => ['key' => $cat['key'], 'title' => $cat['title'], 'desc' => $cat['desc'], 'task_count' => count($tasks)], 'people' => $people]);
    }

    /**
     * POST apply {staff_id, reason, changes: [{menu_id, to: open|blocked|unset, group: bool}]} — the reviewed batch.
     * Blocks and removals run first, then opens, so a person can move from one payment role to another in one review.
     * A change made from a category or task heading (group: true) never opens a payment role; a row's own change may, and the
     * three-role separation still refuses a second role. Every row is logged on its own with the one reason.
     */
    public function apply()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        $db = $this->db();
        $staff = (int) ($b['staff_id'] ?? 0);
        $reason = (string) ($b['reason'] ?? '');
        $changes = is_array($b['changes'] ?? null) ? $b['changes'] : [];
        if (!$changes) {
            return $this->json(['status' => 422, 'message' => 'Nothing to change.'], 422);
        }
        if (mb_strlen(trim($reason)) < StaffAssignment::REASON_MIN) {
            return $this->json(['status' => 422, 'message' => 'Give a reason of at least ' . StaffAssignment::REASON_MIN . ' characters. Nothing was changed.'], 422);
        }
        usort($changes, static fn ($x, $y) => (($x['to'] ?? '') === 'open') <=> (($y['to'] ?? '') === 'open'));
        $done = $refused = $unchanged = [];
        foreach ($changes as $c) {
            $mid = (int) ($c['menu_id'] ?? 0);
            $to = (string) ($c['to'] ?? '');
            if ($to === 'unset') {
                $res = StaffAssignment::unset($db, $staff, $mid, $reason, $this->user(), $this->name());
            } elseif ($to === 'open' || $to === 'blocked') {
                $res = StaffAssignment::set($db, $staff, $mid, $to, $reason, $this->user(), $this->name(), !empty($c['group']));
            } else {
                $res = ['ok' => false, 'message' => 'Unknown setting "' . $to . '".'];
            }
            if (!empty($res['unchanged'])) {
                $unchanged[] = $mid;
            } elseif (!empty($res['ok'])) {
                $done[] = ['menu_id' => $mid, 'to' => $to];
            } else {
                $refused[] = ['menu_id' => $mid, 'to' => $to, 'message' => $res['message'] ?? 'Refused.'];
            }
        }
        return $this->json(['status' => 200, 'done' => $done, 'refused' => $refused, 'unchanged' => $unchanged,
            'message' => count($done) . ' change(s) saved' . ($refused ? ', ' . count($refused) . ' refused' : '') . ($unchanged ? ', ' . count($unchanged) . ' already so' : '') . '.']);
    }

    // tf-retire-r1: retire old employee records (preview, then apply) and set a staff number — Super Admin only
    public function retireOld()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        return $this->out(\App\Libraries\TourFlow\StaffProfiles::retire($this->db(), (array) ($b['keep_ids'] ?? []), (string) ($b['reason'] ?? ''), $this->user(), !empty($b['apply'])));
    }

    public function staffNumber()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        return $this->out(\App\Libraries\TourFlow\StaffProfiles::setNumber($this->db(), (int) ($b['employee_id'] ?? 0), (string) ($b['number'] ?? ''), (string) ($b['reason'] ?? ''), $this->user()));
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
