<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\TourFlow\EntranceMaster;
use App\Libraries\TourFlow\MealMaster;
use App\Libraries\TourFlow\ProgrammeMasters;
use App\Libraries\TourFlow\TicketStock;
use NewGen\NewGenTestCase;

/** The programme masters: places & entrance fees, meals, free visits, paid activities, ticket stock — read over the copy. */
final class A08_MastersTest extends NewGenTestCase
{
    public function testThePlaceMasterIsInstalledAndPricesByDate(): void
    {
        $this->assertTrue(EntranceMaster::installed($this->db()));
        $em = new EntranceMaster($this->db(), 'tcadmin', 901, 'Test Administrator');
        $places = $em->places();
        $this->assertSame((int) $this->one("SELECT COUNT(*) FROM tf_place WHERE active = 1 AND merged_into IS NULL"), count($places), 'every live place listed');
        $this->assertGreaterThan(10, count($places));
        $withRate = array_values(array_filter($places, static fn ($p) => !empty($p['rates']) || !empty($p['entrance']) || !empty($p['charges'])));
        $p = $places[0];
        $one = $em->place((int) $p['id']);
        $this->assertNotNull($one);
        $this->assertSame($p['id'], $one['id']);
        $this->assertSame('', EntranceMaster::fmtDate(null));
        $this->assertNotSame('', EntranceMaster::fmtDate('2026-10-06'));
    }

    public function testTheMealMasterSettingsAndSuppliers(): void
    {
        $this->assertTrue(MealMaster::installed($this->db()));
        $mm = new MealMaster($this->db(), 'tcadmin', 901, 'Test Administrator');
        $s = $mm->settings();
        $this->assertSame('dinner', $s['meal_hb_default'], 'Half Board: the hotel gives breakfast and dinner by default');
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $s['meal_lunch_time']);
        $this->assertSame('dinner', $mm->hbDefault());
        $this->assertGreaterThan(0, count($mm->supplierCats()));
        $this->assertGreaterThan(0, count($mm->suppliers()));
        $this->assertTrue(MealMaster::placeMatch('Kandy', 'Kandy'));
        $this->assertSame('1,250.00', MealMaster::money(1250.0));
    }

    public function testFreeVisitsPaidActivitiesAndDestinations(): void
    {
        $pm = new ProgrammeMasters($this->db(), 'tcadmin', 901, 'Test Administrator');
        $this->assertTrue($pm->available());
        $this->assertSame((int) $this->one('SELECT COUNT(*) FROM tf_free_visit WHERE active = 1'), count($pm->freeVisits()), 'free visits listed');
        $this->assertGreaterThan(0, count($pm->destinations()));
        $this->assertIsArray($pm->activities());
        $this->assertSame('Kandy', ProgrammeMasters::destination('kandy'));
        $this->assertIsArray($pm->entrances());
    }

    public function testTheTicketStockReadsItsAttractionsAndFees(): void
    {
        $tk = new TicketStock($this->db(), 'tcadmin');
        $this->assertTrue($tk->available());
        $fees = $tk->feeMaster();
        $this->assertGreaterThan(10, count($fees));
        $atts = $tk->attractions(false);
        $this->assertSame((int) $this->one('SELECT COUNT(*) FROM tf_ticket_attraction'), count($atts));
        if ($atts !== []) {
            $a = $tk->attraction((int) $atts[0]['id']);
            $this->assertArrayHasKey('control', $a);
            $this->assertContains($a['control'], ['stock', 'gate', 'none', 'online', 'counted', 'uncontrolled'], 'a known control kind');
        }
        $this->assertSame([], $tk->ticketsForTour(0));
    }

    public function testTheOfferSelectionPrivilegeIsItsOwnMenuEntry(): void
    {
        $this->assertSame(1, (int) $this->one("SELECT COUNT(*) FROM menu_list WHERE url = 'master_files/offer_selection' AND del = 0"));
        $this->assertSame(0, (int) $this->one("SELECT COUNT(*) FROM user_privileges up JOIN menu_list ml ON ml.id = up.menu_id WHERE ml.url = 'master_files/offer_selection' AND up.group_id IN (26, 27, 28)"), 'no desk holds the Tactical / Early Bird selection (special rates are the System Administrator\'s)');
    }
}
