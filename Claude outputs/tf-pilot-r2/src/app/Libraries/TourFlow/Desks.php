<?php

namespace App\Libraries\TourFlow;

/**
 * The three desks as groups, with their starting privileges.
 *
 * Access in this system is one method and one method only: a group in group_profile,
 * a menu entry in menu_list, a grant in user_privileges, and the route filter that
 * checks them on every request. This class adds nothing to that. It creates the three
 * desk groups the first time the installer runs at schema 10, gives each its starting
 * set of screens, and grants the Tour Desk screens to the existing Management group —
 * then never touches any of it again. From that moment Settings → Group Privileges is
 * the only place those grants live, so anything a person removes or adds there stays
 * removed or added; a re-run of the installer does not put the starting set back.
 *
 * Every grant is matched by URL against the live menu list. A screen that has no menu
 * entry is reported by name, not invented: nothing is created for it.
 *
 * THE ACCESS RULE OF THE NEW GENERATION (Shantha, 6 Oct 2026). Nobody keeps a privilege
 * because the old system gave it: the legacy grants are a reference, never an authority.
 * Access is given only on his instruction, by name, desk by desk; what he has not decided
 * stays ungranted and is asked about, never guessed. VERSION therefore stays at 9 — no
 * release seeds a desk's starting set again; new screens get a menu entry only, and the
 * grant is his to make in Settings → Group Privileges.
 */
final class Desks
{
    /** Bumped only when a later release should seed something new; the seed then runs once more, still additively. */
    public const VERSION = 9;

    public const QUOTATION  = 'Quotation Desk';
    public const HOTEL      = 'Hotel Desk';
    public const TRANSPORT  = 'Transport Desk';   // the seed name only: on live the group is 'Transport & Crew Guide Allocation Desk' (renamed 6 Oct 2026).
                                                    // The seed looks a group up by this name, so a re-seed would NOT find the renamed group — one more reason VERSION stays at 9.
    public const MANAGEMENT = 'Management';   // the group that already exists; nothing is created under this name

    public const GROUPS = [self::QUOTATION, self::HOTEL, self::TRANSPORT];

    /** The Tour Desk's own screens: menu URL => the groups that start with it. */
    public const TOUR_DESK = [
        'tour/intake'                     => [self::QUOTATION, self::MANAGEMENT],
        'tour/hotel_bookings'             => [self::HOTEL, self::MANAGEMENT],
        'tour/transport_ops'              => [self::TRANSPORT, self::MANAGEMENT],
        'tour/board'                      => [self::MANAGEMENT],
        'master_files/hotel_rate_v2'      => [self::HOTEL, self::MANAGEMENT],
        'master_files/hotel_work'         => [self::HOTEL, self::MANAGEMENT],
        'master_files/transport_rates'    => [self::MANAGEMENT],
        'master_files/mileage_allowances' => [self::MANAGEMENT],
    ];

    /**
     * Added at version 2 — the quotation engine's own screens. Applied once, like the rest:
     * the Excursion Rates master to the desk that quotes and to Management; Pricing (the
     * markup, which staff never see) and the mileage override to Management only.
     */
    public const TOUR_DESK_V2 = [
        'master_files/excursion_rates' => [self::QUOTATION, self::MANAGEMENT],
        'tour/pricing'                 => [self::MANAGEMENT],
        'tour/intake/mileage'          => [self::MANAGEMENT],
    ];

    /**
     * Added at version 3 — the Manage privilege: reassigning a handling agent, archiving
     * and restoring, removing an archived inquiry, archiving a hotel in the master. Management
     * only; the System Administrator holds everything regardless. Every desk sees the handling
     * agent and filters by it without this.
     */
    public const TOUR_DESK_V3 = [
        'tour/manage' => [self::MANAGEMENT],
    ];

    /**
     * Added at version 4 — replacing a driver or guide mid-tour on the Transport Desk (schema
     * 24). Management by default, and the System Administrator; give it to the Transport
     * Desk group under Settings → Group Privileges if the desk should do it on its own.
     */
    public const TOUR_DESK_V4 = [
        'tour/transport_ops/crew_change' => [self::MANAGEMENT],
    ];

    /**
     * Added at version 5 — the driver's advance and settlement on the Transport Desk (schema
     * 26): approving, adjusting and paying an advance, listing settlement adjustments and
     * settling the card. Management by default, and the System Administrator; give it to
     * the group that pays drivers under Settings → Group Privileges. Everybody on the desk
     * may request an advance and sees the figures.
     */
    public const TOUR_DESK_V5 = [
        'tour/transport_ops/driver_advance' => [self::MANAGEMENT],
    ];

    /**
     * Added at version 6 — the tour photo library (schema 27), Master Files → Tour Photos:
     * the pictures the client quotation shows. Admin keeps it; Management holds it too.
     */
    /** Schema 29: the two programme masters — free visits, and paid activities and services — for the quotation desk and Management. */
    public const TOUR_DESK_V7 = [
        'master_files/free_visits'   => [self::QUOTATION, self::MANAGEMENT],
        'master_files/paid_services' => [self::QUOTATION, self::MANAGEMENT],
    ];

    public const TOUR_DESK_V6 = [
        'master_files/tour_photos' => [self::MANAGEMENT],
    ];

    /** Schema 36: the Drivers & Guides master — Management keeps it; the Transport Desk reads it through its own screen. */
    public const TOUR_DESK_V8 = [
        'master_files/crew_people' => [self::MANAGEMENT],
    ];

    /** Schema 37: Ticket Stock — Management receives stock and reads the counts; the Activities desk allocates under its own privilege. */
    public const TOUR_DESK_V9 = [
        'tour/ticket_stock' => [self::MANAGEMENT],
    ];

    /** The privilege the ⋯ Manage menus hang on, so a screen asks by one name. */
    public const MANAGE_URL = 'tour/manage';

    /** The privilege the driver's advance and settlement hang on. */
    public const DRIVER_ADVANCE_URL = 'tour/transport_ops/driver_advance';

    /** The privilege "Replace mid-tour" on the Transport Desk hangs on. */
    public const CREW_CHANGE_URL = 'tour/transport_ops/crew_change';

    /**
     * tf-pilot-r2 — the ALLOCATION privilege: engaging a driver or guide on a card, setting its vehicle, the days a person
     * works. It is the existing "Driver Allocation" entry (the legacy per-tour allocation page; Operations holds it), so one
     * grant covers both the legacy page and the Transport Desk's allocation actions. Groups with Transport Operations alone
     * (arrangements, documents, driver advances) see the cards but cannot allocate.
     */
    public const ALLOCATION_URL = 'dashboard2/transport/driver_allow/tour_transport';

    /**
     * tf-pilot-r2 — the existing groups that allocate on the Transport Desk today through Transport Operations alone (before the
     * department profiles): Management (21) and the Transport & Crew Guide Allocation Desk (28). They keep exactly what they have
     * — existing access is not changed without approval — until their people move to the department profiles; then remove them
     * from this list. The department profiles (Transport & Guide Handling and the rest) allocate only with the privilege above.
     */
    public const LEGACY_ALLOCATION_GROUPS = [21, 28];

    /**
     * The screens the same work uses today, granted alongside so nobody loses a screen
     * by moving into a desk group. Kept until they are retired after UAT. Management is
     * not listed: managers hold their screens through the group they are in already.
     */
    public const LEGACY = [
        'dashboard2/tiles'                                  => [self::QUOTATION, self::HOTEL, self::TRANSPORT],
        // quotation
        'tour/quotation'                                    => [self::QUOTATION],
        'tour/tour_details'                                 => [self::QUOTATION],
        'tour/calendar'                                     => [self::QUOTATION],
        'dashboard2/tour_search/tour_quotation'             => [self::QUOTATION],
        'dashboard2/tour_search/itinerary_print'            => [self::QUOTATION],
        'dashboard2/tour_search/reservation_card'           => [self::QUOTATION],
        'dashboard2/tour_search/timeline'                   => [self::QUOTATION],
        'accounts/debtor_invoice'                           => [self::QUOTATION],
        // hotel
        'dashboard2/hotel_management/hotel_reservation'     => [self::HOTEL],
        'dashboard2/hotel_management/hotel_booking'         => [self::HOTEL],
        'dashboard2/hotel_management/hotel_payment'         => [self::HOTEL],
        'tour/tour_accounts/hotel_rate_confirm'             => [self::HOTEL],
        'master_files/room_type'                            => [self::HOTEL],
        // transport
        'dashboard2/transport/driver_allow/tour_transport'  => [self::TRANSPORT],
        'dashboard2/transport/driver_allow/pending_tours'   => [self::TRANSPORT],
        'dashboard2/transport/driver_allow/allocated_tours' => [self::TRANSPORT],
        'dashboard2/transport/driver_allow/advance_request' => [self::TRANSPORT],
        'dashboard2/transport/transport_portal'             => [self::TRANSPORT],
        'dashboard2/transport/transport_ongoing'            => [self::TRANSPORT],
        'dashboard2/transport/driver_logsheet'              => [self::TRANSPORT],
        'dashboard2/transport/driver_expense'               => [self::TRANSPORT],
        'dashboard2/transport/driver_settlement'            => [self::TRANSPORT],
        'dashboard2/transport/fund_return'                  => [self::TRANSPORT],
        'dashboard2/transport/driver_report'                => [self::TRANSPORT],
        'dashboard2/transport/shop_allow'                   => [self::TRANSPORT],
        'report/driver_ledger'                              => [self::TRANSPORT],
        'master_files/vehicle'                              => [self::TRANSPORT],
    ];

    /** @return string[] notes for the install report */
    public static function ensure($db): array
    {
        $notes = [];
        try {
            $done = (int) (($db->query("SELECT v FROM tf_meta WHERE k = 'desks_version'")->getRowArray()['v'] ?? 0));
            if ($done >= self::VERSION) {
                return [];
            }
            if ($done >= 1) {
                // only what the later versions add; nothing an admin removed since comes back
                $sets = [];
                if ($done < 2) {
                    $sets[] = self::TOUR_DESK_V2;
                }
                if ($done < 3) {
                    $sets[] = self::TOUR_DESK_V3;
                }
                if ($done < 4) {
                    $sets[] = self::TOUR_DESK_V4;
                }
                if ($done < 5) {
                    $sets[] = self::TOUR_DESK_V5;
                }
                if ($done < 6) {
                    $sets[] = self::TOUR_DESK_V6;
                }
                if ($done < 7) {
                    $sets[] = self::TOUR_DESK_V7;
                }
                if ($done < 8) {
                    $sets[] = self::TOUR_DESK_V8;
                }
                $sets[] = self::TOUR_DESK_V9;
                return self::grantOnce($db, $sets, self::VERSION);
            }
            foreach (['group_profile', 'menu_list', 'user_privileges', 'user_acc_tbl'] as $t) {
                if (!$db->query("SHOW TABLES LIKE '{$t}'")->getResultArray()) {
                    return ["The desk groups were not set up: table {$t} is missing on this database."];
                }
            }
            $admin = (int) ($db->query('SELECT MIN(id) AS id FROM user_acc_tbl WHERE group_id = 1')->getRowArray()['id'] ?? 0);

            // 1) the groups — created only if no live group carries the name already
            $groups = [];
            foreach (self::GROUPS as $name) {
                $row = $db->query('SELECT id FROM group_profile WHERE LOWER(type) = LOWER(?) AND del = 0 ORDER BY id LIMIT 1', [$name])->getRowArray();
                if ($row) {
                    $groups[$name] = (int) $row['id'];
                    $notes[] = "Group \"{$name}\" already exists (id {$row['id']}); it was left as it is.";
                    continue;
                }
                $db->query('INSERT INTO group_profile (type, user, del) VALUES (?, ?, 0)', [$name, $admin]);
                $groups[$name] = (int) $db->insertID();
                $notes[] = "Group \"{$name}\" created (id {$groups[$name]}).";
            }
            $mgmt = $db->query('SELECT id FROM group_profile WHERE LOWER(type) = LOWER(?) AND del = 0 ORDER BY id LIMIT 1', [self::MANAGEMENT])->getRowArray();
            if ($mgmt) {
                $groups[self::MANAGEMENT] = (int) $mgmt['id'];
            } else {
                $notes[] = 'No group named "Management" was found, so the Tour Desk screens were not granted to management. Grant them under Settings → Group Privileges to whichever group your managers are in.';
            }

            // 2) the grants, matched by URL; a URL with no menu entry is reported, never invented
            $granted = 0;
            $missing = [];
            foreach ([self::TOUR_DESK, self::TOUR_DESK_V2, self::TOUR_DESK_V3, self::TOUR_DESK_V4, self::TOUR_DESK_V5, self::TOUR_DESK_V6, self::TOUR_DESK_V7, self::TOUR_DESK_V8, self::TOUR_DESK_V9, self::LEGACY] as $set) {
                foreach ($set as $url => $names) {
                    $menu = $db->query("SELECT id FROM menu_list WHERE TRIM(BOTH '/' FROM url) = ? AND del = 0 ORDER BY id LIMIT 1", [trim($url, '/')])->getRowArray();
                    if (!$menu) {
                        $missing[] = $url;
                        continue;
                    }
                    foreach ($names as $name) {
                        if (!isset($groups[$name])) {
                            continue;
                        }
                        $has = $db->query('SELECT id FROM user_privileges WHERE group_id = ? AND menu_id = ? LIMIT 1', [$groups[$name], (int) $menu['id']])->getRowArray();
                        if ($has) {
                            continue;
                        }
                        $db->query('INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES (?, ?, ?)', [$groups[$name], $admin, (int) $menu['id']]);
                        $granted++;
                    }
                }
            }
            $notes[] = "{$granted} starting privilege(s) granted. From here on Settings → Group Privileges is the only place they are kept; re-running the install never puts them back.";
            if ($missing) {
                $notes[] = 'No menu entry exists for: ' . implode(', ', $missing) . ' — nothing was granted for those. Add an entry under Settings → Menu List if the screen should be reachable.';
            }

            $db->query("INSERT INTO tf_meta (k, v) VALUES ('desks_version', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [(string) self::VERSION]);
        } catch (\Throwable $e) {
            $notes[] = 'The desk groups could not be set up automatically (' . $e->getMessage() . '). They can be created by hand under Settings → Group Profiles.';
        }
        return $notes;
    }

    /** Grant one or more sets by URL to the groups named, additively, and record the version. */
    private static function grantOnce($db, array $sets, int $version): array
    {
        $notes = [];
        $admin = (int) ($db->query('SELECT MIN(id) AS id FROM user_acc_tbl WHERE group_id = 1')->getRowArray()['id'] ?? 0);
        $groups = [];
        foreach ($db->query('SELECT id, type FROM group_profile WHERE del = 0')->getResultArray() as $r) {
            $groups[trim((string) $r['type'])] = (int) $r['id'];
        }
        $granted = 0;
        $missing = [];
        foreach ($sets as $set) {
            foreach ($set as $url => $names) {
                $menu = $db->query("SELECT id FROM menu_list WHERE TRIM(BOTH '/' FROM url) = ? AND del = 0 ORDER BY id LIMIT 1", [trim($url, '/')])->getRowArray();
                if (!$menu) {
                    $missing[] = $url;
                    continue;
                }
                foreach ($names as $name) {
                    if (!isset($groups[$name])) {
                        continue;
                    }
                    if ($db->query('SELECT id FROM user_privileges WHERE group_id = ? AND menu_id = ? LIMIT 1', [$groups[$name], (int) $menu['id']])->getRowArray()) {
                        continue;
                    }
                    $db->query('INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES (?, ?, ?)', [$groups[$name], $admin, (int) $menu['id']]);
                    $granted++;
                }
            }
        }
        $names = [];
        foreach ($sets as $set) {
            foreach (array_keys($set) as $url) {
                $names[] = $url;
            }
        }
        $notes[] = "{$granted} privilege(s) granted for the new screen(s) (" . implode(', ', $names) . '). Adjust them under Settings → Group Privileges; a group needs to log out and in once.';
        if ($missing) {
            $notes[] = 'No menu entry exists for: ' . implode(', ', $missing) . ' — nothing was granted for those.';
        }
        $db->query("INSERT INTO tf_meta (k, v) VALUES ('desks_version', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [(string) $version]);
        return $notes;
    }

    /** The desk group ids by name, for screens that need to know which desk somebody sits at. */
    public static function ids($db): array
    {
        $out = [];
        try {
            foreach ($db->query('SELECT id, type FROM group_profile WHERE del = 0')->getResultArray() as $r) {
                $out[trim((string) $r['type'])] = (int) $r['id'];
            }
        } catch (\Throwable $e) {
            // no groups to hand: the route filter still decides who opens what
        }
        return $out;
    }
}
