<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\HotelRates\QuoteReadiness;
use App\Libraries\TourFlow\IntakeService;
use NewGen\NewGenTestCase;

/**
 * "Incomplete — Rates Pending" (Shantha, 6 Oct 2026): a hotel on the master with no valid published contract rate is
 * marked so wherever a hotel is chosen, may be named for identification and preparation, and is never quotation-ready
 * and never priced until approved rates exist. A hotel that has an approved quotation plan is not marked.
 */
final class A14_RatesPendingTest extends NewGenTestCase
{
    private const ACC = '6-9-11-97901';

    private function svc(): HotelRateService
    {
        return new HotelRateService($this->db(), 'battery');
    }

    private function drop(int $id): void
    {
        $this->db()->query('DELETE FROM hr2_hotel_account WHERE hotel_id = ?', [$id]);
        $this->db()->query('DELETE FROM hr2_hotel_code WHERE hotel_id = ?', [$id]);
        $this->db()->query("DELETE FROM hr2_audit WHERE entity = 'hotel' AND entity_id = ?", [$id]);
        $this->db()->query('DELETE FROM hr2_hotel WHERE id = ?', [$id]);
    }

    protected function tearDown(): void
    {
        foreach ($this->rows('SELECT id FROM hr2_hotel WHERE primary_sup_acc_no = ?', [self::ACC]) as $r) {
            $this->drop((int) $r['id']);
        }
        parent::tearDown();
    }

    public function testANewHotelWithoutRatesIsMarkedPendingAndGivesNoQuotationPrice(): void
    {
        $loc = (int) $this->one("SELECT id FROM hr2_location WHERE name = 'Negombo'");
        $this->assertGreaterThan(0, $loc);
        $id = $this->svc()->createHotel('Battery pending hotel', self::ACC, 'Negombo');
        // the Hotel Rates list marks it
        $rows = array_values(array_filter($this->svc()->searchHotels('Battery pending hotel'), static fn ($h) => (int) $h['id'] === $id));
        $this->assertCount(1, $rows);
        $this->assertSame(1, (int) $rows[0]['rates_pending'], 'no approved quotation plan → Incomplete — Rates Pending');
        $this->assertSame(0, (int) $rows[0]['seasons_today']);
        // the quotation's hotel search names it, with the mark, so staff can identify and prepare it
        $hit = array_values(array_filter((new IntakeService($this->db(), 'battery'))->hotelsIn($loc), static fn ($h) => $h['id'] === $id));
        $this->assertCount(1, $hit, 'visible for identification');
        $this->assertTrue($hit[0]['rates_pending']);
        $this->assertMatchesRegularExpression('/^HTL-\d{3,}$/', (string) $hit[0]['code']);
        // the quotation engine itself: not ready on any date, no price, the reason named
        $q = new QuoteReadiness($this->db());
        $h = array_values(array_filter($q->hotels(), static fn ($x) => (int) $x['id'] === $id));
        $this->assertCount(1, $h);
        foreach ([date('Y-m-d'), date('Y-m-d', strtotime('+90 days'))] as $date) {
            $st = $q->hotelStatus($h[0], $date);
            $this->assertSame('none', $st['ready'], "not quotation-ready on {$date}");
            $this->assertNull($st['source'], 'no rate source, so no price');
            $this->assertSame('no_contract', $st['reason']['key']);
            $this->assertSame('Incomplete — Rates Pending', $st['reason']['text']);
        }
        $report = $q->report();
        $this->assertContains($id, array_map(static fn ($r) => (int) ($r['id'] ?? 0), $report['not_ready']), 'listed among the hotels the engine cannot price');
        $this->assertNotContains($id, array_map(static fn ($r) => (int) ($r['id'] ?? 0), $report['ready']));
        $this->drop($id);
    }

    public function testAHotelWithAnApprovedQuotationPlanIsNotMarked(): void
    {
        $hid = (int) $this->one("SELECT c.hotel_id FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.status = 'approved' AND c.applies_to <> 'payment' AND h.status = 'active' LIMIT 1");
        $this->assertGreaterThan(0, $hid);
        $name = (string) $this->one('SELECT name FROM hr2_hotel WHERE id = ?', [$hid]);
        $rows = array_values(array_filter($this->svc()->searchHotels($name), static fn ($h) => (int) $h['id'] === $hid));
        $this->assertCount(1, $rows);
        $this->assertSame(0, (int) $rows[0]['rates_pending']);
        $loc = (int) $this->one('SELECT location_id FROM hr2_hotel WHERE id = ?', [$hid]);
        if ($loc) {
            $hit = array_values(array_filter((new IntakeService($this->db(), 'battery'))->hotelsIn($loc), static fn ($h) => $h['id'] === $hid));
            $this->assertCount(1, $hit);
            $this->assertFalse($hit[0]['rates_pending']);
        }
    }

    public function testExpiredHotelsKeepTheirExpiredLabelAndAreNotMarked(): void
    {
        // a hotel whose approved quotation rates have all ended is "expired", never "Incomplete — Rates Pending"
        $today = date('Y-m-d');
        $expired = $this->rows("SELECT h.id FROM hr2_hotel h WHERE h.status = 'active'
                                AND EXISTS (SELECT 1 FROM hr2_contract c WHERE c.hotel_id = h.id AND c.status = 'approved' AND c.applies_to <> 'payment')
                                AND NOT EXISTS (SELECT 1 FROM hr2_contract c WHERE c.hotel_id = h.id AND c.status = 'approved' AND c.applies_to <> 'payment' AND c.valid_to >= ?)", [$today]);
        $this->assertNotEmpty($expired, 'the copy has hotels whose rates have expired');
        $byId = [];
        foreach ($this->svc()->searchHotels('', 2000) as $h) {
            $byId[(int) $h['id']] = $h;
        }
        $q = new QuoteReadiness($this->db());
        $hotels = [];
        foreach ($q->hotels() as $h) {
            $hotels[(int) $h['id']] = $h;
        }
        foreach ($expired as $e) {
            $id = (int) $e['id'];
            $this->assertSame(0, (int) $byId[$id]['rates_pending'], "hotel {$id}: expired, not marked");
            $this->assertNotNull($byId[$id]['latest_valid_to'], "hotel {$id}: the list shows it as expired on its last date");
            $st = $q->hotelStatus($hotels[$id], $today);
            $this->assertNotSame('Incomplete — Rates Pending', $st['reason']['text'] ?? '', "hotel {$id}: readiness keeps its own reason");
        }
    }

    public function testTheMarkCountsOnlyQuotationPlans(): void
    {
        // the rule as stored: approved AND not payment-only, nothing else
        $pending = (int) $this->one("SELECT COUNT(*) FROM hr2_hotel h WHERE h.status = 'active' AND NOT EXISTS (SELECT 1 FROM hr2_contract c WHERE c.hotel_id = h.id AND c.status = 'approved' AND c.applies_to <> 'payment')");
        $list = $this->svc()->searchHotels('', 2000);
        $marked = count(array_filter($list, static fn ($h) => $h['status'] === 'active' && (int) $h['rates_pending'] === 1));
        $this->assertSame($pending, $marked, 'the list marks exactly the active hotels without an approved quotation plan');
    }
}
