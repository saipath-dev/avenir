<?php

namespace App\Libraries\TourFlow;

/**
 * The staff, as the Tour Desk names them.
 *
 * A handling agent is a user account (user_acc_tbl) shown by the employee's full name
 * where the employee record has one, and by the sign-in name otherwise. Nothing is
 * invented: a user with no employee record is listed under the name they sign in with.
 *
 * Every read here is best-effort. A join that fails on some install (a column named
 * differently, say) falls back to the sign-in name, never to a broken screen.
 */
final class Staff
{
    /** The screens whose holders count as Tour Desk staff for the handling-agent list. */
    public const DESK_URLS = ['tour/intake', 'tour/hotel_bookings', 'tour/transport_ops', 'tour/board', 'tour/manage'];

    private const NAME_SQL = "COALESCE(NULLIF(TRIM(e.fullname), ''), NULLIF(TRIM(e.Name), ''), u.user)";

    /** One account by id: ['id' => int, 'username' => string, 'name' => string] or null. */
    public static function byId($db, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $r = $db->query('SELECT u.id, u.user AS username, ' . self::NAME_SQL . ' AS name FROM user_acc_tbl u LEFT JOIN employee e ON e.emp_num = u.emp_id WHERE u.id = ? LIMIT 1', [$id])->getRowArray();
        } catch (\Throwable $e) {
            try {
                $r = $db->query('SELECT u.id, u.user AS username, u.user AS name FROM user_acc_tbl u WHERE u.id = ? LIMIT 1', [$id])->getRowArray();
            } catch (\Throwable $e2) {
                return null;
            }
        }
        return $r ? ['id' => (int) $r['id'], 'username' => (string) $r['username'], 'name' => (string) ($r['name'] ?: $r['username'])] : null;
    }

    /** One account by the name it signs in with (what the session and every *_by column carry). */
    public static function byUsername($db, string $username): ?array
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }
        try {
            $r = $db->query('SELECT u.id, u.user AS username, ' . self::NAME_SQL . ' AS name FROM user_acc_tbl u LEFT JOIN employee e ON e.emp_num = u.emp_id WHERE u.user = ? ORDER BY u.id LIMIT 1', [$username])->getRowArray();
        } catch (\Throwable $e) {
            try {
                $r = $db->query('SELECT u.id, u.user AS username, u.user AS name FROM user_acc_tbl u WHERE u.user = ? ORDER BY u.id LIMIT 1', [$username])->getRowArray();
            } catch (\Throwable $e2) {
                return null;
            }
        }
        return $r ? ['id' => (int) $r['id'], 'username' => (string) $r['username'], 'name' => (string) ($r['name'] ?: $r['username'])] : null;
    }

    /** The name to show for the signed-in person: the employee's full name, else the sign-in name. */
    public static function displayName($db, ?int $userId, string $username): string
    {
        $who = $userId ? self::byId($db, $userId) : null;
        if (!$who && $username !== '') {
            $who = self::byUsername($db, $username);
        }
        return $who['name'] ?? $username;
    }

    /**
     * The signed-in person as a hotel would reach them: name, telephone (the employee
     * record's) and the mailbox their account sends from (user_acc_tbl.email — the one
     * the Hotel Reservation screen has always sent through). Whether that mailbox is set
     * up to send is `can_send`; the password itself is never read here. Any column an
     * install lacks simply comes back empty.
     *
     * @return array{name:string, phone:?string, email:?string, can_send:bool}
     */
    public static function contact($db, ?int $userId, string $username): array
    {
        $out = ['name' => self::displayName($db, $userId, $username), 'phone' => null, 'email' => null, 'can_send' => false];
        try {
            $sql = 'SELECT u.email, u.host, (u.email_password IS NOT NULL AND u.email_password <> \'\') AS has_pass, e.Tp AS phone FROM user_acc_tbl u LEFT JOIN employee e ON e.emp_num = u.emp_id WHERE ' . ($userId ? 'u.id = ?' : 'u.user = ?') . ' LIMIT 1';
            $r = $db->query($sql, [$userId ?: $username])->getRowArray();
            if ($r) {
                $out['phone']    = trim((string) ($r['phone'] ?? '')) !== '' ? trim((string) $r['phone']) : null;
                $out['email']    = trim((string) ($r['email'] ?? '')) !== '' ? trim((string) $r['email']) : null;
                $out['can_send'] = $out['email'] !== null && trim((string) ($r['host'] ?? '')) !== '' && (int) ($r['has_pass'] ?? 0) === 1;
            }
        } catch (\Throwable $e) {
            // a column this install lacks: the document goes without that line
        }
        // tf-resdesk-r2: with the local test mailbox on, every message is caught on this computer — a mailbox is not needed
        if (!$out['can_send'] && TestMailbox::active()) {
            $out['can_send'] = true;
            $out['email'] = $out['email'] ?? 'test-mailbox@localhost';
        }
        return $out;
    }

    /**
     * Everyone who can be a handling agent: the accounts whose group holds a Tour Desk
     * screen (or is the System Administrator), plus any name already on an inquiry that
     * matches no account — so an old record still filters by the name it carries.
     *
     * @return array<int, array{id:?int, name:string, username:?string, group:?string}>
     */
    public static function handlers($db): array
    {
        $out = [];
        $seen = [];
        try {
            $rows = $db->query(
                'SELECT DISTINCT u.id, u.user AS username, g.type AS group_name, ' . self::NAME_SQL . ' AS name
                 FROM user_acc_tbl u
                 LEFT JOIN group_profile g ON g.id = u.group_id
                 LEFT JOIN employee e ON e.emp_num = u.emp_id
                 WHERE u.group_id = 1 OR u.group_id IN (
                    SELECT up.group_id FROM user_privileges up JOIN menu_list ml ON ml.id = up.menu_id
                    WHERE ml.del = 0 AND TRIM(BOTH \'/\' FROM ml.url) IN (' . implode(',', array_fill(0, count(self::DESK_URLS), '?')) . '))
                 OR u.id IN (
                    SELECT s.user_id FROM tf_staff s JOIN tf_staff_permission p ON p.staff_id = s.id AND p.state = \'open\'
                    JOIN menu_list ml2 ON ml2.id = p.menu_id
                    WHERE s.on_panel = 1 AND ml2.del = 0 AND TRIM(BOTH \'/\' FROM ml2.url) IN (' . implode(',', array_fill(0, count(self::DESK_URLS), '?')) . '))
                 ORDER BY name, u.id',
                array_merge(self::DESK_URLS, self::DESK_URLS)   // tf-zeroperm-r1: a staff account is a handler by its panel rows, not its group
            )->getResultArray();
        } catch (\Throwable $e) {
            try {
                $rows = $db->query('SELECT u.id, u.user AS username, NULL AS group_name, u.user AS name FROM user_acc_tbl u ORDER BY u.user, u.id')->getResultArray();
            } catch (\Throwable $e2) {
                $rows = [];
            }
        }
        foreach ($rows as $r) {
            $id = (int) $r['id'];
            if (isset($seen['id:' . $id])) {
                continue;
            }
            $seen['id:' . $id] = true;
            $name = trim((string) ($r['name'] ?? '')) ?: (string) $r['username'];
            $seen['name:' . mb_strtolower($name)] = true;
            $out[] = ['id' => $id, 'name' => $name, 'username' => (string) $r['username'], 'group' => $r['group_name'] ?? null];
        }
        // names on file that match no account (a user since removed, or a record written before accounts were matched)
        try {
            foreach ($db->query('SELECT handler_name AS name, MIN(handler_id) AS id FROM tf_intake WHERE handler_name IS NOT NULL AND handler_name <> \'\' GROUP BY handler_name')->getResultArray() as $r) {
                $name = trim((string) $r['name']);
                $id = $r['id'] !== null ? (int) $r['id'] : null;
                if ($name === '' || ($id && isset($seen['id:' . $id])) || isset($seen['name:' . mb_strtolower($name)])) {
                    continue;
                }
                $seen['name:' . mb_strtolower($name)] = true;
                $out[] = ['id' => $id, 'name' => $name, 'username' => null, 'group' => null];
            }
        } catch (\Throwable $e) {
            // no tf_intake yet: the account list is the whole answer
        }
        usort($out, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        return $out;
    }
}
