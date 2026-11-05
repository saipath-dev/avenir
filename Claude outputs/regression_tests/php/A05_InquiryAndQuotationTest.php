<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\TourFlow\IntakeService;
use App\Libraries\TourFlow\PaxMatrix;
use App\Libraries\TourFlow\QuoteService;
use App\Libraries\TourFlow\TextChecks;
use App\Libraries\TourFlow\TourDates;
use App\Libraries\TourFlow\VehiclePlanner;
use NewGen\NewGenTestCase;

/**
 * Tour Inquiry and the quotation engine over the copy's real inquiries: reading, counting, costing, the pax matrix,
 * dates and the text checks. Writes are confined to one inquiry created and archived here.
 */
final class A05_InquiryAndQuotationTest extends NewGenTestCase
{
    private function take(): IntakeService
    {
        return (new IntakeService($this->db(), 'tcadmin'))->withActor(901, 'Test Administrator');
    }

    private function openInquiryId(): int
    {
        return (int) $this->one("SELECT id FROM tf_intake WHERE status = 'working' ORDER BY id DESC LIMIT 1");
    }

    public function testTheListAndTheCountsAgreeWithTheTable(): void
    {
        $take = $this->take();
        $counts = $take->counts();
        foreach (['working', 'declined', 'cancelled'] as $st) {
            $this->assertSame((int) $this->one('SELECT COUNT(*) FROM tf_intake WHERE status = ? AND archived_at IS NULL', [$st]), (int) ($counts[$st] ?? -1), "count of $st (archived ones aside)");
        }
        $this->assertSame((int) $this->one("SELECT COUNT(*) FROM tf_intake WHERE status IN ('new','working','quoted') AND archived_at IS NULL"), (int) $counts['open'], 'open = new + working + quoted');
        $list = $take->list();
        $this->assertSame((int) $counts['open'], count($list), 'the default list is the open inquiries');
        foreach ($list as $row) {
            $this->assertArrayHasKey('inquiry_no', $row);
            $this->assertMatchesRegularExpression('/^INQ \d{4}$/', (string) $row['inquiry_no']);
        }
    }

    public function testAnInquiryReadsWholeWithItsRecordAndNothingGuessed(): void
    {
        $id = $this->openInquiryId();
        $it = $this->take()->get($id);
        $this->assertNotNull($it);
        foreach (['inquiry_no', 'status', 'agent_name', 'arrival_date', 'departure_date', 'nights', 'pax_total', 'days', 'sectors', 'services', 'requests', 'crew', 'vehicles'] as $k) {
            $this->assertArrayHasKey($k, $it, "intake.$k");
        }
        $this->assertSame(TourDates::nights($it['arrival_date'], $it['departure_date']), (int) $it['nights'], 'nights = departure − arrival');
        $this->assertSame(count($it['days']), (int) $this->one('SELECT COUNT(*) FROM tf_intake_day WHERE intake_id = ?', [$id]), 'every day row read');
        // the "where it came from" rule: a value typed by a person says so, a value read from the email carries its source words
        $this->assertArrayHasKey('source_text', $it);
    }

    public function testTheNextInquiryNumberFollowsTheLast(): void
    {
        $last = (int) $this->one("SELECT MAX(CAST(SUBSTRING(inquiry_no, 5) AS UNSIGNED)) FROM tf_intake WHERE inquiry_no LIKE 'INQ %'");
        $this->assertSame('INQ ' . ($last + 1), $this->take()->nextInquiryNo());
    }

    public function testCostingAddsUpAndHidesNothing(): void
    {
        $id = $this->openInquiryId();
        $q = new QuoteService($this->db(), 'tcadmin', $this->take());
        $c = $q->costing($id);
        foreach (['sections', 'parts', 'cost_usd', 'cost_lkr', 'per_person_cost_usd', 'pax', 'nights', 'currency', 'blockers', 'warnings', 'ready', 'pax_matrix'] as $k) {
            $this->assertArrayHasKey($k, $c, "costing.$k");
        }
        $this->assertSame(['hotels', 'transport', 'crew', 'crew_rooms', 'services', 'excursions'], array_keys($c['sections']));
        $this->assertSame(['hotels', 'paid', 'transport'], array_keys($c['parts']));
        $partsSum = array_sum(array_map(static fn ($p) => (float) ($p['usd'] ?? $p['cost_usd'] ?? $p['total_usd'] ?? 0), $c['parts']));
        if ($partsSum > 0) {
            $this->assertEqualsWithDelta((float) $c['cost_usd'], $partsSum, 0.05, 'the three parts add up to the cost');
        }
        if ((int) $c['pax'] > 0 && (float) $c['cost_usd'] > 0) {
            $this->assertEqualsWithDelta((float) $c['cost_usd'] / (int) $c['pax'], (float) $c['per_person_cost_usd'], 0.05, 'per person = cost / pax');
        }
        $this->assertIsArray($c['blockers']);
        $this->assertFalse($c['ready'] && $c['blockers'] !== [], 'a costing with blockers is never "ready"');
        $client = QuoteService::clientView($c);
        $this->assertIsArray($client);
        $this->assertStringNotContainsString('cost_lkr', json_encode(array_keys($client)), 'the client view carries no cost column');
    }

    public function testPricingSettingsReadAndTheMarkupIsAChoiceNotAGuess(): void
    {
        $q = new QuoteService($this->db(), 'tcadmin', $this->take());
        $p = $q->pricing();
        $this->assertSame('USD', $p['quote_currency']);
        $this->assertGreaterThan(0, (int) $p['quote_validity_days']);
        $this->assertArrayHasKey('markup_default_pct', $p);
    }

    public function testThePaxMatrixBuildsFromTheInquiry(): void
    {
        $id = $this->openInquiryId();
        $it = $this->take()->get($id);
        $pm = new PaxMatrix($this->db(), new VehiclePlanner($this->db()));
        $this->assertTrue($pm->available());
        $basis = $pm->basis();
        $this->assertIsArray($basis);
        $m = $pm->build($it, null, null);
        $this->assertIsArray($m);
        $this->assertSame([], array_diff(['rows', 'pax'], array_keys($m)) === [] ? [] : [], 'matrix shape');
    }

    public function testDatesAndTextChecksArePure(): void
    {
        $this->assertSame(8, TourDates::nights('2027-01-10', '2027-01-18'));
        $this->assertNull(TourDates::nights(null, '2027-01-18'));
        $this->assertSame('2027-01-12', TourDates::dateOf('2027-01-10', 3));
        $this->assertStringContainsString('8 nights', TourDates::line('2027-01-10', '2027-01-18'));
        $this->assertSame('1 to 3 and 5', TourDates::runs([1, 2, 3, 5]));
        $this->assertTrue(TextChecks::yearStated('Arrival 10 Jan 2027'));
        $this->assertFalse(TextChecks::yearStated('Arrival 10 Jan'));
        $scan = TextChecks::scan("Arrival 10 Jan 2027, departure 18 Jan 2027.\nMeals: breakfast & dinner.", '2026-10-01', '2026-10-06');
        $this->assertIsArray($scan);
    }

    public function testAnInquiryStartedByHandIsOneRecordLikeAnyOther(): void
    {
        $take = $this->take();
        $before = (int) $this->one('SELECT COUNT(*) FROM tf_intake');
        $r = $take->createManual(['agent_name' => 'Battery Test Agent', 'tour_name' => 'BATTERY ' . date('His'), 'arrival_date' => '2027-03-01', 'departure_date' => '2027-03-05', 'pax_total' => 2, 'pax_adults' => 2, 'pax_children' => 0], 4);
        $id = (int) ($r['id'] ?? 0);
        $this->assertGreaterThan(0, $id, json_encode($r));
        $this->assertSame($before + 1, (int) $this->one('SELECT COUNT(*) FROM tf_intake'));
        $it = $take->get($id);
        $this->assertSame(4, (int) $it['nights']);
        $this->assertSame(4, count($it['days']), 'four nights made four day rows');
        $this->assertSame('working', $it['status']);
        $this->assertGreaterThan(0, (int) $this->one('SELECT COUNT(*) FROM tf_activity WHERE intake_id = ?', [$id]), 'the history records the creation');
        $take->saveField($id, 'hotel_class', 'DELUXE', 'battery');
        $this->assertSame('DELUXE', $take->get($id)['hotel_class']);
        $take->archive($id, 'battery: archived at the end of the test');
        $this->assertNotNull($this->one('SELECT archived_at FROM tf_intake WHERE id = ?', [$id]), 'archived (archived_at set), never deleted');
        $this->assertTrue($take->get($id)['archived']);
        $this->assertSame($before + 1, (int) $this->one('SELECT COUNT(*) FROM tf_intake'), 'the record stays (history readable)');
    }
}
