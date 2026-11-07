<?php

namespace App\Libraries\TourFlow;

/**
 * tf-positions-r1 (7 Nov 2026, Shantha) — position first, then the person.
 *
 * A position (e.g. "Director of Operations") is its own record: a title, a department label and the responsibilities. The person
 * who holds it is a separate record with dates: who, from when, until when, who assigned them and why. The panel reads
 * "Director of Operations → Iqbal".
 *
 * A position grants NOTHING. Permissions stay where they always were — on the person's own panel record (tf_staff_permission),
 * opened one row at a time by the Super Admin with a reason. Assigning or ending a holder changes no permission and no status.
 * Holder rows are never deleted: ending a holding writes its end date, so who held a position, and when, stays on record.
 *
 * One current holder per position, and one current position per person (both decisions open for review; the tables allow either).
 */
final class StaffPositions
{
    public const TITLE_MIN = 3;
    public const TITLE_MAX = 120;

    /** Create the two tables if they are missing. Creates nothing else, alters nothing that exists. */
    public static function ensure($db): array
    {
        $db->query("CREATE TABLE IF NOT EXISTS `tf_position` (
            `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `title`            VARCHAR(120) NOT NULL,
            `department`       VARCHAR(80)  NULL,
            `responsibilities` VARCHAR(500) NULL,
            `status`           ENUM('open','closed') NOT NULL DEFAULT 'open',
            `created_by`       VARCHAR(100) NOT NULL,
            `created_at`       DATETIME NOT NULL,
            `updated_by`       VARCHAR(100) NULL,
            `updated_at`       DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ux_tf_position_title` (`title`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->query("CREATE TABLE IF NOT EXISTS `tf_position_holder` (
            `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `position_id`   INT UNSIGNED NOT NULL,
            `staff_id`      INT UNSIGNED NOT NULL,
            `user_id`       INT NOT NULL,
            `from_at`       DATETIME NOT NULL,
            `to_at`         DATETIME NULL,
            `assigned_by`   VARCHAR(100) NOT NULL,
            `assign_reason` VARCHAR(500) NOT NULL,
            `ended_by`      VARCHAR(100) NULL,
            `end_reason`    VARCHAR(500) NULL,
            PRIMARY KEY (`id`),
            KEY `ix_tf_pos_holder_pos` (`position_id`, `to_at`),
            KEY `ix_tf_pos_holder_staff` (`staff_id`, `to_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        return ['Positions: tf_position and tf_position_holder are present (a position grants nothing; the person holding it is recorded separately, with dates).'];
    }

    public static function installed($db): bool
    {
        try {
            return (bool) $db->query("SHOW TABLES LIKE 'tf_position_holder'")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function reasonRefusal(string $reason): ?string
    {
        return mb_strlen(trim($reason)) < StaffAssignment::REASON_MIN
            ? 'Give a reason of at least ' . StaffAssignment::REASON_MIN . ' characters. Nothing was changed.' : null;
    }

    /** Every position with its current holder (if any). */
    public static function positions($db): array
    {
        $rows = $db->query(
            "SELECT p.*, h.id AS holder_id, h.staff_id, h.user_id, h.from_at, h.assigned_by,
                    u.user AS username, u.access, s.status AS staff_status, s.on_panel,
                    COALESCE(NULLIF(TRIM(e.fullname), ''), e.Name) AS person_name,
                    (SELECT COUNT(*) FROM tf_staff_permission sp WHERE sp.staff_id = h.staff_id AND sp.state = 'open') AS open_count
             FROM tf_position p
             LEFT JOIN tf_position_holder h ON h.position_id = p.id AND h.to_at IS NULL
             LEFT JOIN user_acc_tbl u ON u.id = h.user_id
             LEFT JOIN tf_staff s ON s.id = h.staff_id
             LEFT JOIN employee e ON e.emp_num = u.emp_id
             ORDER BY p.status = 'closed', p.title"
        )->getResultArray();
        foreach ($rows as &$r) {
            $r['holder'] = $r['holder_id'] ? [
                'staff_id' => (int) $r['staff_id'], 'user_id' => (int) $r['user_id'], 'username' => (string) $r['username'],
                'name' => trim((string) ($r['person_name'] ?? '')) ?: (string) $r['username'], 'since' => (string) $r['from_at'],
                'enabled' => (int) $r['access'] === 1, 'status' => (string) ($r['staff_status'] ?? ''), 'open_count' => (int) $r['open_count'],
            ] : null;
            foreach (['holder_id', 'staff_id', 'user_id', 'from_at', 'assigned_by', 'username', 'access', 'staff_status', 'on_panel', 'person_name', 'open_count'] as $k) {
                unset($r[$k]);
            }
        }
        return $rows;
    }

    /** Everyone who has held one position, oldest first. */
    public static function holders($db, int $positionId): array
    {
        return $db->query(
            "SELECT h.*, u.user AS username FROM tf_position_holder h LEFT JOIN user_acc_tbl u ON u.id = h.user_id WHERE h.position_id = ? ORDER BY h.id",
            [$positionId]
        )->getResultArray();
    }

    public static function create($db, string $title, string $department, string $responsibilities, string $reason, string $by, string $byName): array
    {
        if ($why = self::reasonRefusal($reason)) {
            return ['ok' => false, 'message' => $why];
        }
        $title = trim(preg_replace('/\s+/', ' ', $title));
        if (mb_strlen($title) < self::TITLE_MIN || mb_strlen($title) > self::TITLE_MAX) {
            return ['ok' => false, 'message' => 'The position title is ' . self::TITLE_MIN . ' to ' . self::TITLE_MAX . ' characters. Nothing was changed.'];
        }
        $department = trim($department);
        if ($department !== '' && !in_array($department, StaffAssignment::DEPARTMENTS, true)) {
            return ['ok' => false, 'message' => 'Unknown department label.'];
        }
        if ($db->query('SELECT id FROM tf_position WHERE title = ? LIMIT 1', [$title])->getRowArray()) {
            return ['ok' => false, 'message' => 'A position called "' . $title . '" already exists.'];
        }
        $db->table('tf_position')->insert([
            'title' => $title, 'department' => $department ?: null, 'responsibilities' => mb_substr(trim($responsibilities), 0, 500) ?: null,
            'status' => 'open', 'created_by' => $by, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        StaffAssignment::log($db, null, null, 'position_created', null, null, $title, $reason,
            'Position "' . $title . '" created' . ($department ? ' (' . $department . ')' : '') . '. A position grants nothing; permissions are opened on the person who holds it.', $by, $byName);
        return ['ok' => true, 'id' => $id, 'message' => 'Position "' . $title . '" created. It grants nothing. Assign the person who holds it next.'];
    }

    /** Assign a person (an account already on the panel) to a position. Changes no permission and no status. */
    public static function assign($db, int $positionId, int $staffId, string $reason, string $by, string $byName): array
    {
        if ($why = self::reasonRefusal($reason)) {
            return ['ok' => false, 'message' => $why];
        }
        $p = $db->query('SELECT * FROM tf_position WHERE id = ? LIMIT 1', [$positionId])->getRowArray();
        if (!$p) {
            return ['ok' => false, 'message' => 'No such position.'];
        }
        if ($p['status'] !== 'open') {
            return ['ok' => false, 'message' => 'Position "' . $p['title'] . '" is closed.'];
        }
        $s = StaffAssignment::staff($db, $staffId);
        if (!$s || (int) $s['on_panel'] !== 1) {
            return ['ok' => false, 'message' => 'Only a person on the Staff Access Control Panel can hold a position.'];
        }
        $cur = $db->query('SELECT h.id, u.user FROM tf_position_holder h LEFT JOIN user_acc_tbl u ON u.id = h.user_id WHERE h.position_id = ? AND h.to_at IS NULL LIMIT 1', [$positionId])->getRowArray();
        if ($cur) {
            return ['ok' => false, 'refused' => 'held', 'message' => '"' . $p['title'] . '" is held by ' . $cur['user'] . '. End that holding first; the record of it is kept.'];
        }
        $other = $db->query('SELECT p.title FROM tf_position_holder h JOIN tf_position p ON p.id = h.position_id WHERE h.staff_id = ? AND h.to_at IS NULL LIMIT 1', [$staffId])->getRowArray();
        if ($other) {
            return ['ok' => false, 'refused' => 'other', 'message' => $s['username'] . ' already holds "' . $other['title'] . '".'];
        }
        $db->table('tf_position_holder')->insert([
            'position_id' => $positionId, 'staff_id' => $staffId, 'user_id' => (int) $s['user_id'], 'from_at' => date('Y-m-d H:i:s'),
            'assigned_by' => $by, 'assign_reason' => mb_substr(trim($reason), 0, 500),
        ]);
        StaffAssignment::log($db, $staffId, (int) $s['user_id'], 'position_assigned', null, null, mb_substr($p['title'], 0, 40), $reason,
            $p['title'] . ' → ' . $s['username'] . '. No permission or status changed by this; permissions are opened on the person, one row at a time.', $by, $byName);
        return ['ok' => true, 'message' => $p['title'] . ' → ' . $s['username'] . '. No permission was granted by this.'];
    }

    /** End the current holding of a position. The holder row is kept with its end date; permissions and status are not touched. */
    public static function end($db, int $positionId, string $reason, string $by, string $byName): array
    {
        if ($why = self::reasonRefusal($reason)) {
            return ['ok' => false, 'message' => $why];
        }
        $h = $db->query('SELECT h.*, p.title, u.user FROM tf_position_holder h JOIN tf_position p ON p.id = h.position_id LEFT JOIN user_acc_tbl u ON u.id = h.user_id WHERE h.position_id = ? AND h.to_at IS NULL LIMIT 1', [$positionId])->getRowArray();
        if (!$h) {
            return ['ok' => false, 'message' => 'Nobody holds this position.'];
        }
        $db->table('tf_position_holder')->where('id', (int) $h['id'])->update(['to_at' => date('Y-m-d H:i:s'), 'ended_by' => $by, 'end_reason' => mb_substr(trim($reason), 0, 500)]);
        StaffAssignment::log($db, (int) $h['staff_id'], (int) $h['user_id'], 'position_ended', null, mb_substr($h['title'], 0, 40), null, $reason,
            $h['title'] . ' no longer held by ' . $h['user'] . '. Their permissions and status are unchanged by this: suspend or take them off the panel separately if their access should end.', $by, $byName);
        return ['ok' => true, 'message' => $h['user'] . ' no longer holds "' . $h['title'] . '". Their access is unchanged — suspend them on the panel if it should end.'];
    }
}
