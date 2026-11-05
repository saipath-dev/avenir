<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\TourFlow\IntakeService;
use App\Libraries\TourFlow\QuoteService;
use App\Libraries\TourFlow\TourService;
use NewGen\NewGenTestCase;

/**
 * The quotation engine never guesses: an inquiry with no room mix, no hotel named, a leg without kilometres and no
 * pricing set is costed to nothing, its blockers named one by one, and Go for Quotation and Confirm refuse. The
 * inquiry made here is archived at the end (never deleted).
 */
final class A12_EngineGuardRailsTest extends NewGenTestCase
{
    public function testAnEmptyInquiryIsRefusedWithEveryReasonNamed(): void
    {
        $take = (new IntakeService($this->db(), 'tcadmin'))->withActor(901, 'Test Administrator');
        $r = $take->createManual(['agent_name' => 'LION TOURS', 'agent_acc' => '1-2-5-10635', 'tour_name' => 'GUARD ' . date('His'), 'arrival_date' => '2027-02-01', 'departure_date' => '2027-02-04', 'pax_total' => 2, 'pax_adults' => 2, 'pax_children' => 0, 'hotel_class' => 'DELUXE'], 3, '1-2-5-10635');
        $id = (int) $r['id'];
        $this->assertGreaterThan(0, $id);
        try {
            $q = new QuoteService($this->db(), 'tcadmin', $take);
            $c = $q->costing($id);
            $codes = array_map(static fn ($b) => preg_replace('/_\d+$/', '', (string) $b['code']), $c['blockers']);
            $this->assertContains('rooms_mix', $codes, 'the room mix is not set');
            $this->assertContains('hotel_none', $codes, 'no hotel named on a night');
            $this->assertContains('km_unpriced', $codes, 'a leg without a distance');
            $this->assertContains('pricing', $codes, 'no markup and no default: Management decides pricing, never the engine');
            $this->assertSame(3, count(array_filter($codes, static fn ($c) => $c === 'hotel_none')), 'one blocker per night');
            $this->assertFalse($c['ready']);
            $this->assertSame(0.0, (float) $c['cost_usd'], 'nothing is costed from nothing');
            $this->assertNull($c['markup']['pct']);
            $this->assertSame('none', $c['markup']['source']);
            $this->assertSame(325.0, (float) $c['usd_rate'], 'the USD rate comes from the daily rate table, not a constant');
            $this->assertTrue($c['tour_dates']['complete']);
            $this->assertSame(3, (int) $c['tour_dates']['nights']);
            $this->assertSame('standard', $c['itinerary']['days']['1']['source'], 'the standard itinerary wording is offered, marked as standard');
            $g = $q->goForQuotation($id);
            $this->assertFalse($g['ok'], 'Go for Quotation refuses');
            $this->assertNotSame([], $g['blockers']);
            $this->assertNull($g['quote'], 'no quotation version is created');
            $this->assertSame(0, (int) $this->one('SELECT COUNT(*) FROM tf_quote WHERE intake_id = ?', [$id]));
            $ts = new TourService($this->db(), 'tcadmin', $take);
            $this->assertNotSame([], $ts->confirmBlockers($take->get($id)), 'Confirm refuses without an issued quotation');
            $this->assertSame(0, (int) $this->one('SELECT COUNT(*) FROM tf_tour WHERE intake_id = ?', [$id]), 'no tour was made');
            // a vehicle was suggested for 2 pax from the Transport Rates ladder, at the master's rate — not invented
            $this->assertSame('SEDAN CAR', (string) ($c['transport'][0]['type'] ?? ''), 'a sedan for two');
            $this->assertSame(100.0, (float) ($c['transport'][0]['km_rate'] ?? 0), 'at the general rate of the class');
            $this->assertSame('missing', (string) ($c['transport'][0]['state'] ?? ''), 'but not costed while a leg has no distance');
        } finally {
            $take->archive($id, 'battery: guard rails, archived at the end');
        }
    }

    public function testTheRoomMixIsTypedNeverWorkedOutFromThePassengerCount(): void
    {
        $take = (new IntakeService($this->db(), 'tcadmin'))->withActor(901, 'Test Administrator');
        $id = (int) $this->one("SELECT id FROM tf_intake WHERE status = 'working' AND archived_at IS NULL AND tour_name NOT LIKE 'GUARD%' AND tour_name NOT LIKE 'BATTERY%' ORDER BY id DESC LIMIT 1");
        $it = $take->get($id);
        $rooms = $it['rooms'] ?? [];
        $this->assertIsArray($rooms);
        if (($rooms['source'] ?? '') === 'none') {
            $this->assertSame(0, (int) ($rooms['rooms'] ?? 0), 'with no mix typed there are no rooms — the count is never guessed from pax');
        } else {
            $this->assertContains($rooms['source'], ['intake', 'groups', 'groups_empty', 'nobody'], 'a mix names where it came from: the inquiry\'s typed fields or its groups');
            $this->assertSame((int) $rooms['sgl'] + (int) $rooms['dbl'] + (int) $rooms['twn'] + (int) $rooms['tpl'], (int) $rooms['rooms'], 'rooms = the sum of the typed kinds');
            $this->assertSame((int) $rooms['sgl'] + 2 * (int) $rooms['dbl'] + 2 * (int) $rooms['twn'] + 3 * (int) $rooms['tpl'], (int) $rooms['sleeps'], 'sleeps = 1 per single, 2 per double or twin, 3 per triple');
        }
    }
}
