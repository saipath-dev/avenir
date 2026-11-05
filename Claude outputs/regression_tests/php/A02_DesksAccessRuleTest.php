<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\TourFlow\Desks;
use NewGen\NewGenTestCase;

/**
 * The access rule of the new generation (Shantha, 6 Oct 2026): nothing is carried forward from the old system, a desk
 * holds exactly what he granted, and no release seeds a desk's starting set again. The copy's desk groups hold what live's
 * held when the copy was made; these checks fail the moment a seed or a release grants anything by itself.
 */
final class A02_DesksAccessRuleTest extends NewGenTestCase
{
    public function testTheSeedIsFrozenAtVersionNine(): void
    {
        $this->assertSame(9, Desks::VERSION, 'Desks::VERSION — a bump would re-seed the starting sets, which the access rule forbids');
        $this->assertSame('9', (string) $this->one("SELECT v FROM tf_meta WHERE k = 'desks_version'"));
    }

    public function testEnsureChangesNothingWhenTheSeedIsAlreadyDone(): void
    {
        $before = (int) $this->one('SELECT COUNT(*) FROM user_privileges');
        $groups = (int) $this->one('SELECT COUNT(*) FROM group_profile');
        $sig    = (string) $this->one("SELECT MD5(GROUP_CONCAT(CONCAT(group_id, ':', menu_id) ORDER BY group_id, menu_id)) FROM user_privileges");
        Desks::ensure($this->db());
        $this->assertSame($before, (int) $this->one('SELECT COUNT(*) FROM user_privileges'), 'no privilege row added or removed by ensure()');
        $this->assertSame($groups, (int) $this->one('SELECT COUNT(*) FROM group_profile'), 'no group added by ensure()');
        $this->assertSame($sig, (string) $this->one("SELECT MD5(GROUP_CONCAT(CONCAT(group_id, ':', menu_id) ORDER BY group_id, menu_id)) FROM user_privileges"), 'the grants are byte for byte the same');
    }

    public function testTheThreeDeskGroupsExistOnce(): void
    {
        $ids = Desks::ids($this->db());
        foreach (['Quotation Desk' => 26, 'Hotel Desk' => 27, 'Transport & Crew Guide Allocation Desk' => 28] as $name => $id) {
            $this->assertSame($id, $ids[$name] ?? null, $name);
            $this->assertSame(1, (int) $this->one('SELECT COUNT(*) FROM group_profile WHERE LOWER(type) = LOWER(?) AND del = 0', [$name]), "$name exists exactly once");
        }
        $this->assertSame(0, (int) $this->one("SELECT COUNT(*) FROM group_profile WHERE type = 'Transport Desk' AND del = 0"), 'the old seed name is not re-created beside the renamed group');
    }

    public function testTheDesksHoldExactlyWhatWasGranted(): void
    {
        $held = function (int $g): array {
            $u = array_values(array_unique(array_map(static fn ($r) => $r['url'], $this->rows('SELECT ml.url FROM user_privileges up JOIN menu_list ml ON ml.id = up.menu_id WHERE up.group_id = ? AND ml.del = 0', [$g]))));
            sort($u);
            return $u;
        };
        $quotation = $held(26);
        $transport = $held(28);
        $hotel     = $held(27);
        $this->assertSame(['tour/intake'], $quotation, 'Quotation Desk: Tour Inquiry only');
        $this->assertSame(['tour/intake', 'tour/ticket_stock', 'tour/transport_ops'], $transport, 'Transport & Crew Guide Allocation Desk: Tour Inquiry, Transport Operations, Ticket Stock');
        foreach (['tour/hotel_bookings', 'master_files/hotel_rate_v2', 'master_files/hotel_work'] as $u) {
            $this->assertContains($u, $hotel, "Hotel Desk holds $u");
        }
        // what must stay ungranted until Shantha says (6 Oct 2026)
        $never = ['tour/board', 'tour/pricing', 'tour/intake/mileage', 'tour/transport_ops/crew_change', 'tour/transport_ops/driver_advance', 'tour/manage', 'master_files/crew_people/bank', 'master_files/crew_people', 'master_files/transport_rates', 'master_files/mileage_allowances', 'master_files/crew_bata', 'master_files/entrance_master', 'master_files/meal_master', 'master_files/free_visits', 'master_files/paid_services', 'master_files/excursion_rates', 'master_files/tour_photos', 'master_files/offer_selection'];
        foreach ([26 => $quotation, 27 => $hotel, 28 => $transport] as $g => $urls) {
            foreach ($never as $u) {
                $this->assertNotContains($u, $urls, "group $g must not hold $u");
            }
        }
    }

    public function testEveryDeskScreenIsAMenuEntryAndEveryGrantPointsAtOne(): void
    {
        $screens = array_merge(Desks::TOUR_DESK, Desks::TOUR_DESK_V2, Desks::TOUR_DESK_V3, Desks::TOUR_DESK_V4, Desks::TOUR_DESK_V5);
        $this->assertGreaterThanOrEqual(12, count($screens));
        foreach ($screens as $url => $groups) {
            $n = (int) $this->one('SELECT COUNT(*) FROM menu_list WHERE url = ? AND del = 0', [$url]);
            $this->assertSame(1, $n, "$url has exactly one live menu entry (one menu URL = one privilege)");
        }
        // the groups the new generation manages (the three desks and Management) hold no dangling grant; the legacy groups are not judged here
        $this->assertSame(0, (int) $this->one('SELECT COUNT(*) FROM user_privileges up LEFT JOIN menu_list ml ON ml.id = up.menu_id WHERE up.group_id IN (21, 26, 27, 28) AND ml.id IS NULL'), 'no desk grant points at a missing menu entry');
        $this->assertSame(0, (int) $this->one('SELECT COUNT(*) FROM user_privileges up LEFT JOIN group_profile g ON g.id = up.group_id WHERE up.group_id IN (21, 26, 27, 28) AND g.id IS NULL'), 'no desk grant points at a missing group');
    }
}
