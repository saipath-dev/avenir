<?php

namespace App\Libraries\TourFlow;

/**
 * tf-stage10 (30 Oct 2026) — Staff Assignment: individual Open / Blocked permissions for the accounts the Super Admin
 * brings onto the panel. Schema 57: tf_staff, tf_staff_permission, tf_staff_log.
 *
 * Rules, all enforced here (the server), never only on the screen:
 *   - an account on the panel has exactly its own Open list: the view user_priv_q answers from tf_staff_permission for it, so
 *     the sidebar, UserPermissionFilter, Perms and every direct request see the same list; its group grants nothing;
 *   - every change needs a reason (REASON_MIN characters) and writes one line to tf_staff_log (append-only);
 *   - the three payment roles — A management approval, B bank payment initiation, C final authorisation (Paid) — at most ONE per
 *     person, refused in every order; bulk changes never open any of them;
 *   - protected rows (Settings, Staff Assignment, Blocks & Investigations, the System-Administrator-only action rights) are never
 *     opened for anybody; the System Administrator account is never put on the panel; Driver-group accounts wait for decision 9;
 *   - suspension / deactivation ends every session of the account at once (kill_user_sessions) and the session filter refuses
 *     its next request (sessionRefusal);
 *   - a department is a label: it grants nothing.
 * Installing creates no account and alters no existing row.
 */
final class StaffAssignment
{
    public const URL = 'master_files/staff';
    public const REASON_MIN = 5;

    /** The three separated payment roles, by menu URL. 96/97 are the legacy voucher-authentication (release) page. */
    public const ROLES = [
        'A' => ['tour/tour_payment_dashboard/approve', 'tour/tour_accounts/payment_approval_v2', 'tour/tour_accounts/payment_requested_dashboard_new'],
        'B' => ['report/bank_payment_check/initiate'],
        'C' => ['report/bank_payment_check/approve', 'accounts/creditor_invoice/voucher_authentication'],
    ];
    public const ROLE_NAMES = ['A' => 'Management Approval', 'B' => 'Bank Payment Initiation', 'C' => 'Final Authorisation (Paid)'];

    /** Never opened for a staff account, whatever is asked: a prefix match on the URL. */
    public const PROTECTED_PREFIXES = ['settings/', 'staff/', 'master_files/blocks', 'master_files/staff', 'accounts/bank_unrealised_value'];
    public const PROTECTED_URLS = ['act:chart.maintain', 'act:vouchers.finalise', 'act:vouchers.audit_mark'];

    public const STATUSES = ['active' => 'Active', 'suspended' => 'Suspended', 'deactivated' => 'Deactivated'];
    public const DEPARTMENTS = ['Quotation Desk', 'Tour Reservations', 'Hotel Reservations', 'Ticketing', 'Transport & Guide Handling', 'Accounts', 'Finance & Approvals', 'Management', 'Operations', 'Audit'];

    private static ?bool $installed = null;
    private static array $byUser = [];

    // ------------------------------------------------------------------ installation

    public static function installed($db): bool
    {
        if (self::$installed === null) {
            try {
                self::$installed = (bool) $db->query("SHOW TABLES LIKE 'tf_staff_permission'")->getResultArray();
            } catch (\Throwable $e) {
                self::$installed = false;
            }
        }
        return self::$installed;
    }

    // ------------------------------------------------------------------ who is on the panel

    /** The tf_staff row of an account (null when it was never put on the panel). */
    public static function byUser($db, ?int $userId): ?array
    {
        if (!$userId || !self::installed($db)) {
            return null;
        }
        if (!array_key_exists($userId, self::$byUser)) {
            self::$byUser[$userId] = $db->query('SELECT * FROM tf_staff WHERE user_id = ? LIMIT 1', [$userId])->getRowArray() ?: null;
        }
        return self::$byUser[$userId];
    }

    /** True when the account's access is decided by the panel (on it, whatever its status). */
    public static function onPanel($db, ?int $userId): bool
    {
        $s = self::byUser($db, $userId);
        return $s !== null && (int) $s['on_panel'] === 1;
    }

    /** The Open URLs of a panel account → menu name (for AccessPolicyFilter); null when the account is not on the panel. */
    public static function heldOverride($db, ?int $userId): ?array
    {
        $s = self::byUser($db, $userId);
        if (!$s || (int) $s['on_panel'] !== 1) {
            return null;
        }
        if ($s['status'] !== 'active') {
            return [];
        }
        $out = [];
        foreach ($db->query("SELECT ml.url, ml.name FROM tf_staff_permission p JOIN menu_list ml ON ml.id = p.menu_id AND ml.del = 0 WHERE p.staff_id = ? AND p.state = 'open'", [(int) $s['id']])->getResultArray() as $r) {
            $out[(string) $r['url']] = (string) $r['name'];
        }
        return $out;
    }

    /**
     * For UserSessionFilter, on every request: the reason this signed-in account may not continue — disabled on the account
     * table, or suspended / deactivated on the panel. Null = carry on. Never applies to the System Administrator group.
     */
    public static function sessionRefusal($db, ?int $userId, int $groupId): ?string
    {
        if (!$userId || $groupId === 1) {
            return null;
        }
        try {
            $u = $db->query('SELECT access FROM user_acc_tbl WHERE id = ? LIMIT 1', [$userId])->getRowArray();
            if ($u && (int) $u['access'] !== 1) {
                return 'account disabled';
            }
            $s = self::byUser($db, $userId);
            if ($s && (int) $s['on_panel'] === 1 && $s['status'] !== 'active') {
                return 'account ' . $s['status'];
            }
        } catch (\Throwable $e) {
        }
        return null;
    }

    // ------------------------------------------------------------------ roles

    public static function roleOf(string $url): ?string
    {
        $url = trim($url, '/');
        foreach (self::ROLES as $k => $urls) {
            if (in_array($url, $urls, true)) {
                return $k;
            }
        }
        return null;
    }

    public static function isProtected(string $url): bool
    {
        $url = trim($url, '/');
        if (in_array($url, self::PROTECTED_URLS, true)) {
            return true;
        }
        foreach (self::PROTECTED_PREFIXES as $p) {
            if ($url === rtrim($p, '/') || str_starts_with($url, $p)) {
                return true;
            }
        }
        return false;
    }

    /** The payment roles a panel member holds through Open rows, e.g. ['B']. */
    public static function rolesHeld($db, int $staffId): array
    {
        $held = [];
        foreach ($db->query("SELECT ml.url FROM tf_staff_permission p JOIN menu_list ml ON ml.id = p.menu_id WHERE p.staff_id = ? AND p.state = 'open'", [$staffId])->getResultArray() as $r) {
            if ($k = self::roleOf((string) $r['url'])) {
                $held[$k] = true;
            }
        }
        return array_keys($held);
    }

    /** For Perms: a panel account that somehow holds more than one payment role answers "no" to all of them. */
    public static function roleConflict($db, ?int $userId): bool
    {
        $s = self::byUser($db, $userId);
        if (!$s || (int) $s['on_panel'] !== 1) {
            return false;
        }
        return count(self::rolesHeld($db, (int) $s['id'])) > 1;
    }

    // ------------------------------------------------------------------ reading

    /** Every account the system has, for the review list: name, sign-in, group, status, last recorded activity, panel row. */
    public static function accounts($db): array
    {
        $rows = $db->query("SELECT u.id, u.user, u.group_id, u.access, u.emp_id, g.type AS group_name, e.Name AS emp_name
                            FROM user_acc_tbl u LEFT JOIN group_profile g ON g.id = u.group_id LEFT JOIN employee e ON e.emp_num = u.emp_id
                            ORDER BY (u.group_id = 24), g.type, u.user")->getResultArray();
        $last = [];
        try {
            foreach ($db->query('SELECT Usr, MAX(Date) d FROM a_log GROUP BY Usr')->getResultArray() as $r) {
                $last[strtolower((string) $r['Usr'])] = (string) $r['d'];
            }
        } catch (\Throwable $e) {
        }
        $staff = [];
        if (self::installed($db)) {
            foreach ($db->query('SELECT * FROM tf_staff')->getResultArray() as $s) {
                $staff[(int) $s['user_id']] = $s;
            }
        }
        $open = [];
        if ($staff) {
            foreach ($db->query("SELECT staff_id, COUNT(*) n FROM tf_staff_permission WHERE state = 'open' GROUP BY staff_id")->getResultArray() as $r) {
                $open[(int) $r['staff_id']] = (int) $r['n'];
            }
        }
        $out = [];
        foreach ($rows as $r) {
            $s = $staff[(int) $r['id']] ?? null;
            $out[] = [
                'user_id' => (int) $r['id'], 'username' => (string) $r['user'], 'name' => trim((string) ($r['emp_name'] ?? '')) ?: null,
                'group_id' => (int) $r['group_id'], 'group' => (string) ($r['group_name'] ?? ('group ' . $r['group_id'])),
                'enabled' => (int) $r['access'] === 1, 'is_admin' => (int) $r['group_id'] === 1, 'is_driver' => (int) $r['group_id'] === 24,
                'last_activity' => $last[strtolower((string) $r['user'])] ?? null,
                'staff_id' => $s ? (int) $s['id'] : null, 'on_panel' => $s ? (int) $s['on_panel'] === 1 : false,
                'status' => $s ? (string) $s['status'] : null, 'department' => $s ? (string) ($s['department'] ?? '') : null,
                'responsibilities' => $s ? (string) ($s['responsibilities'] ?? '') : null,
                'open_count' => $s ? ($open[(int) $s['id']] ?? 0) : null,
                'roles' => $s && (int) $s['on_panel'] === 1 ? self::rolesHeld($db, (int) $s['id']) : [],
            ];
        }
        return $out;
    }

    /** Every live menu entry, grouped by category, with its role / protected flags. One row per entry (duplicate URLs are marked). */
    public static function menus($db): array
    {
        $rows = $db->query('SELECT ml.id, ml.cat_id, mc.category, mc.order_id cat_oid, ml.name, ml.url, ml.show_in_menu_bar, ml.order_id
                            FROM menu_list ml JOIN menu_category mc ON mc.id = ml.cat_id WHERE ml.del = 0 ORDER BY mc.order_id, ml.order_id, ml.id')->getResultArray();
        $byUrl = [];
        foreach ($rows as $r) {
            $byUrl[trim((string) $r['url'], '/')][] = (int) $r['id'];
        }
        $out = [];
        foreach ($rows as $r) {
            $url = trim((string) $r['url'], '/');
            $out[] = [
                'id' => (int) $r['id'], 'category' => trim((string) $r['category']), 'cat_id' => (int) $r['cat_id'], 'name' => trim((string) $r['name']), 'url' => $url,
                'right' => (int) $r['show_in_menu_bar'] === 1, 'role' => self::roleOf($url), 'protected' => self::isProtected($url),
                'same_url' => array_values(array_diff($byUrl[$url], [(int) $r['id']])),
            ];
        }
        return $out;
    }

    /** menu_id → permission row of one staff member (absent = Blocked). */
    public static function permissions($db, int $staffId): array
    {
        $out = [];
        foreach ($db->query('SELECT * FROM tf_staff_permission WHERE staff_id = ?', [$staffId])->getResultArray() as $r) {
            $out[(int) $r['menu_id']] = $r;
        }
        return $out;
    }

    public static function staff($db, int $staffId): ?array
    {
        $s = $db->query('SELECT s.*, u.user username, u.group_id, u.access, g.type group_name, e.Name emp_name FROM tf_staff s JOIN user_acc_tbl u ON u.id = s.user_id LEFT JOIN group_profile g ON g.id = u.group_id LEFT JOIN employee e ON e.emp_num = u.emp_id WHERE s.id = ? LIMIT 1', [$staffId])->getRowArray();
        if ($s) {
            $s['roles'] = self::rolesHeld($db, $staffId);
        }
        return $s ?: null;
    }

    public static function history($db, ?int $staffId, int $limit = 300): array
    {
        $sql = 'SELECT l.*, ml.name menu_name, ml.url menu_url FROM tf_staff_log l LEFT JOIN menu_list ml ON ml.id = l.menu_id ';
        $args = [];
        if ($staffId) {
            $sql .= 'WHERE l.staff_id = ? ';
            $args[] = $staffId;
        }
        $sql .= 'ORDER BY l.id DESC LIMIT ' . max(1, min(2000, $limit));
        return $db->query($sql, $args)->getResultArray();
    }

    // ------------------------------------------------------------------ writing (Super Admin only — the controller checks the group)

    private static function reasonRefusal(string $reason): ?string
    {
        if (mb_strlen(trim($reason)) < self::REASON_MIN) {
            return 'Give a reason of at least ' . self::REASON_MIN . ' characters. Nothing was changed.';
        }
        return null;
    }

    public static function log($db, ?int $staffId, ?int $userId, string $action, ?int $menuId, ?string $from, ?string $to, string $reason, string $detail, string $by, string $byName): void
    {
        $db->table('tf_staff_log')->insert([
            'staff_id' => $staffId, 'user_id' => $userId, 'action' => $action, 'menu_id' => $menuId, 'from_state' => $from, 'to_state' => $to,
            'reason' => mb_substr(trim($reason), 0, 500), 'detail' => mb_substr($detail, 0, 1000), 'by_user' => mb_substr($by, 0, 100), 'by_name' => mb_substr($byName, 0, 190),
            'at' => date('Y-m-d H:i:s'), 'ip' => mb_substr((string) (service('request')->getIPAddress() ?? ''), 0, 45),
        ]);
    }

    /** Put an existing account on the panel: all Blocked to start. Never the System Administrator; drivers wait for decision 9. */
    public static function addToPanel($db, int $userId, string $department, string $responsibilities, string $reason, string $by, string $byName): array
    {
        if ($why = self::reasonRefusal($reason)) {
            return ['ok' => false, 'message' => $why];
        }
        $u = $db->query('SELECT id, user, group_id, access FROM user_acc_tbl WHERE id = ? LIMIT 1', [$userId])->getRowArray();
        if (!$u) {
            return ['ok' => false, 'message' => 'No such account.'];
        }
        if ((int) $u['group_id'] === 1) {
            return ['ok' => false, 'message' => 'The System Administrator account is never put on the panel. Its access does not come from here.'];
        }
        if ((int) $u['group_id'] === 24) {
            return ['ok' => false, 'message' => 'Driver portal sign-ins are not staff accounts; whether they come onto the panel is decision 9, not yet taken.'];
        }
        $department = trim($department);
        if ($department !== '' && !in_array($department, self::DEPARTMENTS, true)) {
            return ['ok' => false, 'message' => 'Unknown department label.'];
        }
        $existing = self::byUser($db, $userId);
        $now = date('Y-m-d H:i:s');
        if ($existing) {
            if ((int) $existing['on_panel'] === 1) {
                return ['ok' => false, 'message' => 'This account is already on the panel.'];
            }
            $db->table('tf_staff')->where('id', (int) $existing['id'])->update(['on_panel' => 1, 'department' => $department ?: null, 'responsibilities' => mb_substr(trim($responsibilities), 0, 500) ?: null, 'status' => 'active', 'status_reason' => null, 'updated_by' => $by, 'updated_at' => $now]);
            $id = (int) $existing['id'];
            // back onto the panel = a fresh start: every row Blocked again (the earlier Open rows stay in the history, not in force)
            $reset = (int) ($db->query("SELECT COUNT(*) n FROM tf_staff_permission WHERE staff_id = ? AND state = 'open'", [$id])->getRowArray()['n'] ?? 0);
            if ($reset > 0) {
                $db->query("UPDATE tf_staff_permission SET state = 'blocked', reason = ?, set_by = ?, set_at = ? WHERE staff_id = ? AND state = 'open'", ['Re-added to the panel: ' . mb_substr(trim($reason), 0, 400), $by, $now, $id]);
                self::log($db, $id, $userId, 'blocked', null, 'open', 'blocked', $reason, $reset . ' row(s) that were Open before the account left the panel are Blocked again on re-adding.', $by, $byName);
            }
        } else {
            $db->table('tf_staff')->insert(['user_id' => $userId, 'on_panel' => 1, 'department' => $department ?: null, 'responsibilities' => mb_substr(trim($responsibilities), 0, 500) ?: null, 'status' => 'active', 'created_by' => $by, 'created_at' => $now, 'updated_by' => $by, 'updated_at' => $now]);
            $id = (int) $db->insertID();
        }
        unset(self::$byUser[$userId]);
        self::log($db, $id, $userId, 'added_to_panel', null, null, 'active', $reason, 'Account "' . $u['user'] . '" brought onto the panel: every page and action Blocked; its group list no longer counts. Department label: ' . ($department ?: '—') . '.', $by, $byName);
        // the person's open sessions re-read their access on the next request (the view answers from the panel now)
        return ['ok' => true, 'id' => $id, 'message' => 'Account "' . $u['user'] . '" is on the panel with every page and action Blocked. Open what is needed, one row at a time, with a reason.'];
    }

    /** Take an account off the panel: it goes back to its group's list. Logged; only the Super Admin reaches this. */
    public static function removeFromPanel($db, int $staffId, string $reason, string $by, string $byName): array
    {
        if ($why = self::reasonRefusal($reason)) {
            return ['ok' => false, 'message' => $why];
        }
        $s = self::staff($db, $staffId);
        if (!$s || (int) $s['on_panel'] !== 1) {
            return ['ok' => false, 'message' => 'This account is not on the panel.'];
        }
        $db->table('tf_staff')->where('id', $staffId)->update(['on_panel' => 0, 'updated_by' => $by, 'updated_at' => date('Y-m-d H:i:s')]);
        unset(self::$byUser[(int) $s['user_id']]);
        self::log($db, $staffId, (int) $s['user_id'], 'removed_from_panel', null, 'on panel', 'group list', $reason, 'Account "' . $s['username'] . '" taken off the panel: its group "' . $s['group_name'] . '" decides its access again. The Open / Blocked rows are kept, not applied.', $by, $byName);
        return ['ok' => true, 'message' => 'Account "' . $s['username'] . '" is back on its group list.'];
    }

    /** The department label and responsibilities: labels only, they grant nothing. */
    public static function label($db, int $staffId, string $department, string $responsibilities, string $reason, string $by, string $byName): array
    {
        if ($why = self::reasonRefusal($reason)) {
            return ['ok' => false, 'message' => $why];
        }
        $s = self::staff($db, $staffId);
        if (!$s) {
            return ['ok' => false, 'message' => 'No such staff record.'];
        }
        $department = trim($department);
        if ($department !== '' && !in_array($department, self::DEPARTMENTS, true)) {
            return ['ok' => false, 'message' => 'Unknown department label.'];
        }
        $db->table('tf_staff')->where('id', $staffId)->update(['department' => $department ?: null, 'responsibilities' => mb_substr(trim($responsibilities), 0, 500) ?: null, 'updated_by' => $by, 'updated_at' => date('Y-m-d H:i:s')]);
        self::log($db, $staffId, (int) $s['user_id'], 'label', null, (string) ($s['department'] ?? ''), $department, $reason, 'Department label / responsibilities changed (labels grant nothing).', $by, $byName);
        return ['ok' => true, 'message' => 'Label saved. It grants nothing; access is the Open list only.'];
    }

    /**
     * One row: Open or Blocked, with a reason. Refuses protected rows, a second payment role (any order), and a missing reason.
     * Every refusal is logged too. Rows sharing the same URL are switched together.
     */
    public static function set($db, int $staffId, int $menuId, string $state, string $reason, string $by, string $byName, bool $bulk = false): array
    {
        $state = $state === 'open' ? 'open' : 'blocked';
        if ($why = self::reasonRefusal($reason)) {
            return ['ok' => false, 'message' => $why];
        }
        $s = self::staff($db, $staffId);
        if (!$s) {
            return ['ok' => false, 'message' => 'No such staff record.'];
        }
        $m = $db->query('SELECT id, name, url FROM menu_list WHERE id = ? AND del = 0 LIMIT 1', [$menuId])->getRowArray();
        if (!$m) {
            return ['ok' => false, 'message' => 'No such page or action in the menu table.'];
        }
        $url = trim((string) $m['url'], '/');
        $cur = self::permissions($db, $staffId)[$menuId]['state'] ?? 'blocked';
        $label = $m['name'] . ' (' . $url . ')';
        if ($state === 'open' && self::isProtected($url)) {
            self::log($db, $staffId, (int) $s['user_id'], 'refused', $menuId, $cur, 'open', $reason, 'Protected: ' . $label . ' is Super Admin only and is never opened for a staff account.', $by, $byName);
            return ['ok' => false, 'refused' => 'protected', 'message' => '"' . $m['name'] . '" is Super Admin only. It has no Open control for staff; the refusal was logged.'];
        }
        $role = self::roleOf($url);
        if ($state === 'open' && $role) {
            if ($bulk) {
                self::log($db, $staffId, (int) $s['user_id'], 'refused', $menuId, $cur, 'open', $reason, 'Bulk change may not open a payment role: ' . $label . ' (' . self::ROLE_NAMES[$role] . ').', $by, $byName);
                return ['ok' => false, 'refused' => 'bulk_role', 'message' => '"' . $m['name'] . '" is payment role ' . $role . ' (' . self::ROLE_NAMES[$role] . '): never opened in bulk — open it on its own, with its own reason.'];
            }
            $held = array_values(array_diff(self::rolesHeld($db, $staffId), [$role]));
            if ($held) {
                $h = $held[0];
                self::log($db, $staffId, (int) $s['user_id'], 'refused', $menuId, $cur, 'open', $reason, 'Payment-role separation: already holds ' . $h . ' (' . self::ROLE_NAMES[$h] . '); ' . $label . ' is role ' . $role . '.', $by, $byName);
                return ['ok' => false, 'refused' => 'role', 'message' => 'Not opened. ' . $s['username'] . ' already holds ' . self::ROLE_NAMES[$h] . '. Opening ' . self::ROLE_NAMES[$role] . ' as well would give one person two of the three payment roles. Keep them with different people. The refusal was logged.'];
            }
        }
        if ($cur === $state) {
            return ['ok' => true, 'unchanged' => true, 'message' => '"' . $m['name'] . '" was already ' . ucfirst($state) . '.'];
        }
        $now = date('Y-m-d H:i:s');
        // rows that share this URL switch together (one URL = one permission on the server)
        $ids = array_map(static fn ($r) => (int) $r['id'], $db->query('SELECT id FROM menu_list WHERE del = 0 AND TRIM(BOTH "/" FROM url) = ?', [$url])->getResultArray()) ?: [$menuId];
        foreach ($ids as $mid) {
            $db->query('INSERT INTO tf_staff_permission (staff_id, menu_id, state, reason, set_by, set_at) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE state = VALUES(state), reason = VALUES(reason), set_by = VALUES(set_by), set_at = VALUES(set_at)',
                [$staffId, $mid, $state, mb_substr(trim($reason), 0, 500), $by, $now]);
        }
        self::log($db, $staffId, (int) $s['user_id'], $state === 'open' ? 'opened' : 'blocked', $menuId, $cur, $state, $reason, $label . ($role ? ' — payment role ' . $role : '') . (count($ids) > 1 ? ' (' . count($ids) . ' menu rows share this URL)' : '') . ($bulk ? ' [bulk]' : ''), $by, $byName);
        return ['ok' => true, 'message' => '"' . $m['name'] . '" is now ' . ucfirst($state) . ' for ' . $s['username'] . '. Applies on their next request.'];
    }

    /** A group shortcut: each row on its own, same reason; payment roles and protected rows are refused, never granted. */
    public static function bulk($db, int $staffId, array $menuIds, string $state, string $reason, string $by, string $byName): array
    {
        $done = $refused = $unchanged = [];
        foreach (array_unique(array_map('intval', $menuIds)) as $mid) {
            $r = self::set($db, $staffId, $mid, $state, $reason, $by, $byName, true);
            if (!empty($r['unchanged'])) {
                $unchanged[] = $mid;
            } elseif ($r['ok']) {
                $done[] = $mid;
            } else {
                $refused[] = ['menu_id' => $mid, 'message' => $r['message']];
            }
        }
        return ['ok' => true, 'done' => $done, 'refused' => $refused, 'unchanged' => $unchanged,
            'message' => count($done) . ' switched to ' . ucfirst($state === 'open' ? 'open' : 'blocked') . (count($refused) ? ', ' . count($refused) . ' refused' . (self::reasonRefusal($reason) ? ' (no reason given)' : ' (payment roles / protected rows are never opened in bulk)') : '') . (count($unchanged) ? ', ' . count($unchanged) . ' already so' : '') . '.'];
    }

    /** Active / Suspended / Deactivated — ends every session of the account at once; the session filter refuses the next request. */
    public static function status($db, int $staffId, string $status, string $reason, string $by, string $byName): array
    {
        if (!isset(self::STATUSES[$status])) {
            return ['ok' => false, 'message' => 'Status must be active, suspended or deactivated.'];
        }
        if ($why = self::reasonRefusal($reason)) {
            return ['ok' => false, 'message' => $why];
        }
        $s = self::staff($db, $staffId);
        if (!$s) {
            return ['ok' => false, 'message' => 'No such staff record.'];
        }
        if ($s['status'] === $status) {
            return ['ok' => true, 'unchanged' => true, 'message' => 'Already ' . self::STATUSES[$status] . '.'];
        }
        $db->table('tf_staff')->where('id', $staffId)->update(['status' => $status, 'status_reason' => mb_substr(trim($reason), 0, 500), 'status_by' => $by, 'status_at' => date('Y-m-d H:i:s'), 'updated_by' => $by, 'updated_at' => date('Y-m-d H:i:s')]);
        unset(self::$byUser[(int) $s['user_id']]);
        $killed = 0;
        if ($status !== 'active') {
            try {
                helper('usersession');
                $killed = (int) kill_user_sessions((int) $s['user_id'], false, $status === 'suspended' ? 'suspension' : 'deactivation');   // the revocation is labelled for what it is
            } catch (\Throwable $e) {
            }
        }
        self::log($db, $staffId, (int) $s['user_id'], 'status', null, (string) $s['status'], $status, $reason, 'Account "' . $s['username'] . '" ' . self::STATUSES[$status] . ($status !== 'active' ? '; ' . $killed . ' open session(s) ended at once' : ''), $by, $byName);
        return ['ok' => true, 'message' => 'Account "' . $s['username'] . '" is ' . self::STATUSES[$status] . '.' . ($status !== 'active' ? ' ' . $killed . ' open session(s) ended; the next request is refused.' : '')];
    }

    /** Separation (tf-stage10, decision 18:09): the creator of a supplier batch may not management-approve or finally authorise that batch. */
    public static function batchCreatorRefusal($db, string $step, array $prIds, ?int $vnum, string $me): ?string
    {
        if (!in_array($step, ['approve', 'paid'], true) || $me === '') {
            return null;
        }
        try {
            $batches = [];
            if ($vnum) {
                foreach ($db->query('SELECT id, batch_no, created_by FROM tpd_batch WHERE vnum = ?', [$vnum])->getResultArray() as $b) {
                    $batches[(int) $b['id']] = $b;
                }
            }
            $prIds = array_values(array_filter(array_map('intval', $prIds)));
            if ($prIds) {
                foreach ($db->query('SELECT DISTINCT b.id, b.batch_no, b.created_by FROM tpd_request_ext x JOIN tpd_batch b ON b.id = x.batch_id WHERE x.pr_id IN (' . implode(',', $prIds) . ')')->getResultArray() as $b) {
                    $batches[(int) $b['id']] = $b;
                }
            }
            foreach ($batches as $b) {
                if (strcasecmp(trim((string) $b['created_by']), trim($me)) === 0) {
                    return 'Separation of duties: you created supplier payment batch ' . ($b['batch_no'] ?: ('#' . $b['id'])) . ' yourself (batch creation is preparation only), so you cannot ' . ($step === 'paid' ? 'finally authorise (Paid)' : 'management-approve') . ' it. Another authorised user must do this step.';
                }
            }
        } catch (\Throwable $e) {
        }
        return null;
    }

    /** The access list for export: one line per panel member and Open row, and one per account with its status. */
    public static function exportRows($db): array
    {
        $out = [];
        foreach (self::accounts($db) as $a) {
            $out[] = ['kind' => 'account', 'username' => $a['username'], 'name' => $a['name'], 'group' => $a['group'], 'enabled' => $a['enabled'] ? 'yes' : 'no', 'on_panel' => $a['on_panel'] ? 'yes' : 'no', 'status' => $a['status'] ?? '', 'department' => $a['department'] ?? '', 'page' => '', 'url' => '', 'state' => '', 'reason' => '', 'by' => '', 'at' => ''];
            if ($a['on_panel']) {
                foreach ($db->query('SELECT p.*, ml.name, ml.url FROM tf_staff_permission p JOIN menu_list ml ON ml.id = p.menu_id WHERE p.staff_id = ? ORDER BY ml.cat_id, ml.order_id', [(int) $a['staff_id']])->getResultArray() as $p) {
                    $out[] = ['kind' => 'permission', 'username' => $a['username'], 'name' => $a['name'], 'group' => $a['group'], 'enabled' => '', 'on_panel' => '', 'status' => '', 'department' => '', 'page' => $p['name'], 'url' => $p['url'], 'state' => $p['state'], 'reason' => $p['reason'], 'by' => $p['set_by'], 'at' => $p['set_at']];
                }
            }
        }
        return $out;
    }
}
