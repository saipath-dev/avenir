<?php

namespace App\Libraries\TourFlow;

/**
 * tf-staffno-r1 (7 Nov 2026, Shantha) — staff numbers, job titles and work descriptions, and the restricted
 * Pilot report "Staff Work Descriptions" (report/staff_work_descriptions).
 *
 * A profile is information only. A job title, group label, work description or computer-account link grants NOTHING:
 * access comes only from rows opened on the Staff Access Control Panel. The report itself is for the System Administrator,
 * or for a person the Super Admin explicitly opens it for on the panel (its exact menu address) — checked on the page and
 * on the export.
 */
final class StaffProfiles
{
    public const REPORT_URL = 'report/staff_work_descriptions';
    public const REPORT_NAME = 'Staff Work Descriptions';
    public const PILOT_CATEGORY = 'Pilot Report';

    public static function ensure($db): array
    {
        $notes = [];
        $db->query("CREATE TABLE IF NOT EXISTS `tf_staff_profile` (
            `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `employee_id` INT NOT NULL COMMENT 'employee.ID — the internal id, never changed',
            `job_title`   VARCHAR(120) NULL,
            `group_label` VARCHAR(120) NULL COMMENT 'the intended group; a label, it grants nothing',
            `duties`      VARCHAR(1000) NULL,
            `updated_by`  VARCHAR(100) NOT NULL,
            `updated_at`  DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ux_tf_staff_profile_emp` (`employee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        // the menu entry under Pilot Report, granted to the System Administrator group only
        $menu = $db->query('SELECT id FROM menu_list WHERE url = ? AND del = 0 LIMIT 1', [self::REPORT_URL])->getRowArray();
        if (!$menu) {
            $cat = $db->query('SELECT id FROM menu_category WHERE category = ? LIMIT 1', [self::PILOT_CATEGORY])->getRowArray();
            if ($cat) {
                $order = (int) ($db->query('SELECT COALESCE(MAX(order_id), 0) + 1 AS n FROM menu_list WHERE cat_id = ?', [(int) $cat['id']])->getRowArray()['n'] ?? 1);
                $db->query('INSERT INTO menu_list (cat_id, name, user, del, url, order_id, show_in_menu_bar) VALUES (?, ?, 1, 0, ?, ?, 0)', [(int) $cat['id'], self::REPORT_NAME, self::REPORT_URL, $order]);
                $menu = ['id' => (int) $db->insertID()];
                $notes[] = 'Menu entry "' . self::REPORT_NAME . '" added under ' . self::PILOT_CATEGORY . '.';
            } else {
                $notes[] = 'The ' . self::PILOT_CATEGORY . ' menu category was not found; add "' . self::REPORT_NAME . '" (' . self::REPORT_URL . ') under Settings → Menu Lists.';
            }
        }
        if ($menu && !$db->query('SELECT id FROM user_privileges WHERE group_id = 1 AND menu_id = ? LIMIT 1', [(int) $menu['id']])->getRowArray()) {
            $db->query('INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES (1, 1, ?)', [(int) $menu['id']]);
            $notes[] = '"' . self::REPORT_NAME . '" granted to the System Administrator group only.';
        }
        // tf-move283-r1: Staff Assignment is listed under Pilot Report (same menu entry, same address, same privileges), shown in the menu
        $pilot = $db->query('SELECT id FROM menu_category WHERE category = ? LIMIT 1', [self::PILOT_CATEGORY])->getRowArray();
        $sa = $db->query("SELECT id, cat_id, show_in_menu_bar FROM menu_list WHERE TRIM(BOTH '/' FROM url) = 'master_files/staff' AND del = 0 LIMIT 1")->getRowArray();
        if ($pilot && $sa && ((int) $sa['cat_id'] !== (int) $pilot['id'] || (int) $sa['show_in_menu_bar'] !== 0)) {
            $order = (int) ($db->query('SELECT COALESCE(MAX(order_id), 0) + 1 AS n FROM menu_list WHERE cat_id = ?', [(int) $pilot['id']])->getRowArray()['n'] ?? 1);
            if ((int) $sa['cat_id'] === (int) $pilot['id']) {
                $db->query('UPDATE menu_list SET show_in_menu_bar = 0 WHERE id = ?', [(int) $sa['id']]);
            } else {
                $db->query('UPDATE menu_list SET cat_id = ?, order_id = ?, show_in_menu_bar = 0 WHERE id = ?', [(int) $pilot['id'], $order, (int) $sa['id']]);
            }
            $notes[] = 'Staff Assignment is now listed under ' . self::PILOT_CATEGORY . ' (same page, same privileges).';
        }
        // the two group labels used for the fresh staff set-up; a group grants nothing, so they are created with no privileges
        foreach (['Quotation Team', 'Operations and Administration'] as $label) {
            if (!$db->query('SELECT id FROM group_profile WHERE type = ? AND del = 0 LIMIT 1', [$label])->getRowArray()) {
                $db->query('INSERT INTO group_profile (type, user, del) VALUES (?, 1, 0)', [$label]);
                $notes[] = 'Group "' . $label . '" added (no privileges).';
            }
        }
        return $notes ?: ['Staff profiles and the Staff Work Descriptions report are present.'];
    }

    /** May the signed-in user open the report (page and export)? System Administrator, or the exact menu address held. */
    public static function mayView($db, ?int $userId, int $groupId): bool
    {
        if ($groupId === 1) {
            return true;
        }
        if (!$userId) {
            return false;
        }
        return (bool) $db->query('SELECT 1 FROM user_priv_q WHERE user_id = ? AND TRIM(BOTH \'/\' FROM url) = ? LIMIT 1', [$userId, self::REPORT_URL])->getRowArray();
    }

    /** The active staff, by staff number, with title, group, duties and any linked computer account. */
    public static function rows($db): array
    {
        $rows = $db->query(
            "SELECT e.ID AS employee_id, e.empno AS staff_no, COALESCE(NULLIF(TRIM(e.fullname), ''), e.Name) AS name,
                    p.job_title, p.group_label, p.duties,
                    u.user AS account, u.access AS account_enabled, g.type AS account_group,
                    pos.title AS position
             FROM employee e
             LEFT JOIN tf_staff_profile p ON p.employee_id = e.ID
             LEFT JOIN user_acc_tbl u ON u.emp_id = e.emp_num AND u.group_id <> 1
             LEFT JOIN group_profile g ON g.id = u.group_id
             LEFT JOIN tf_position_holder h ON h.user_id = u.id AND h.to_at IS NULL
             LEFT JOIN tf_position pos ON pos.id = h.position_id
             WHERE e.is_active = 1 AND e.emp_num NOT IN (SELECT emp_id FROM user_acc_tbl WHERE group_id = 1)
             ORDER BY (e.empno REGEXP '^[0-9]+$') DESC, CAST(e.empno AS UNSIGNED), e.ID"
        )->getResultArray();
        foreach ($rows as &$r) {
            $r['title'] = $r['position'] ?: $r['job_title'];
            $r['group'] = $r['account_group'] ?: $r['group_label'];
        }
        return $rows;
    }

    public static function save($db, int $employeeId, ?string $title, ?string $group, ?string $duties, string $by): void
    {
        $db->query(
            'INSERT INTO tf_staff_profile (employee_id, job_title, group_label, duties, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE job_title = VALUES(job_title), group_label = VALUES(group_label), duties = VALUES(duties), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
            [$employeeId, $title, $group, $duties, $by, date('Y-m-d H:i:s')]
        );
    }

    /**
     * tf-retire-r1 (8 Nov 2026, Shantha): retire old employee records. A record is retired, not deleted: it is made inactive
     * and its staff number moves to OLD-<number>, so the number is free and the person leaves every list, picker and report,
     * while tours, payments, accounts and history stay linked by the internal number (emp_num), which never changes.
     * Kept: every record a login points at, and the IDs passed in $keepIds. $apply = false only lists what would change.
     */
    public static function retire($db, array $keepIds, string $reason, string $by, bool $apply): array
    {
        $reason = trim($reason);
        if ($apply && mb_strlen($reason) < 5) {
            return ['ok' => false, 'message' => 'Give a reason (at least 5 characters).'];
        }
        $keepIds = array_values(array_filter(array_map('intval', $keepIds)));
        $rows = $db->query(
            "SELECT e.ID, e.empno, e.emp_num, e.fullname FROM employee e
             WHERE e.is_active = 1
               AND e.emp_num NOT IN (SELECT emp_id FROM user_acc_tbl WHERE emp_id IS NOT NULL)
               " . ($keepIds ? 'AND e.ID NOT IN (' . implode(',', $keepIds) . ')' : '') . "
             ORDER BY e.ID"
        )->getResultArray();
        if (!$apply) {
            return ['ok' => true, 'count' => count($rows), 'rows' => $rows];
        }
        $now = date('Y-m-d H:i:s');
        $db->transBegin();
        try {
            $db->query("INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", ['retire_before_' . date('Ymd_His'), json_encode($rows)]);
            $done = 0;
            foreach ($rows as $r) {
                $no = (string) $r['empno'];
                $newNo = ($no !== '' && stripos($no, 'OLD-') !== 0) ? 'OLD-' . $no : $no;
                $db->query('UPDATE employee SET is_active = 0, empno = ? WHERE ID = ? AND is_active = 1', [$newNo, (int) $r['ID']]);
                $done += (int) $db->affectedRows();
            }
            if ($done !== count($rows)) {
                throw new \RuntimeException('only ' . $done . ' of ' . count($rows) . ' records could be retired');
            }
            if (StaffAssignment::installed($db)) {
                StaffAssignment::log($db, null, null, 'employees_retired', null, 'active', 'retired', $reason,
                    $done . ' employee record(s) retired (inactive, staff number moved to OLD-<n>); kept linked by internal number. IDs: ' . implode(',', array_column($rows, 'ID')), $by, $by);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Nothing was retired: ' . $e->getMessage() . '.'];
        }
        return ['ok' => true, 'count' => $done, 'message' => $done . ' old employee record(s) retired. Their staff numbers are free; their history stays linked.'];
    }

    /** tf-retire-r1: give an active employee record a staff number. The internal number (emp_num) is not touched. */
    public static function setNumber($db, int $employeeId, string $number, string $reason, string $by): array
    {
        $number = trim($number);
        if (!preg_match('/^[0-9]{1,6}$/', $number)) {
            return ['ok' => false, 'message' => 'A staff number is 1 to 6 digits.'];
        }
        if (mb_strlen(trim($reason)) < 5) {
            return ['ok' => false, 'message' => 'Give a reason (at least 5 characters).'];
        }
        $e = $db->query('SELECT ID, empno, fullname FROM employee WHERE ID = ? AND is_active = 1 LIMIT 1', [$employeeId])->getRowArray();
        if (!$e) {
            return ['ok' => false, 'message' => 'No such active employee record.'];
        }
        $taken = $db->query('SELECT ID, fullname, is_active FROM employee WHERE empno = ? AND ID <> ? LIMIT 1', [$number, $employeeId])->getRowArray();
        if ($taken) {
            return ['ok' => false, 'message' => 'Staff number ' . $number . ' is already used by ' . $taken['fullname'] . ((int) $taken['is_active'] ? '' : ' (inactive)') . '.'];
        }
        $db->query('UPDATE employee SET empno = ? WHERE ID = ?', [$number, $employeeId]);
        if (StaffAssignment::installed($db)) {
            StaffAssignment::log($db, null, null, 'staff_number', null, (string) $e['empno'], $number, $reason, 'Employee "' . $e['fullname'] . '" (record ' . $employeeId . ') staff number ' . $e['empno'] . ' -> ' . $number . '; internal number unchanged.', $by, $by);
        }
        return ['ok' => true, 'message' => $e['fullname'] . ' now has staff number ' . $number . '.'];
    }
}
