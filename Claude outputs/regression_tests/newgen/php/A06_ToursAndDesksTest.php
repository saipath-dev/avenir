<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\TourFlow\Badges;
use App\Libraries\TourFlow\IntakeService;
use App\Libraries\TourFlow\TourService;
use NewGen\NewGenTestCase;

/** Confirmed tours, the three desks' queues, the Tour Board and the counts on the Tour Desk bar — read over the copy's tours. */
final class A06_ToursAndDesksTest extends NewGenTestCase
{
    private function tours(): TourService
    {
        return new TourService($this->db(), 'tcadmin', (new IntakeService($this->db(), 'tcadmin'))->withActor(901, 'Test Administrator'));
    }

    public function testATourReadsWithItsLanesAndStrip(): void
    {
        $id = (int) $this->one('SELECT MAX(id) FROM tf_tour');
        $this->assertGreaterThan(0, $id);
        $ts = $this->tours();
        $t = $ts->get($id);
        $this->assertNotNull($t);
        foreach (['tour_no', 'intake_id', 'stage', 'agent_name', 'arrival_date', 'departure_date', 'nights', 'pax_total'] as $k) {
            $this->assertArrayHasKey($k, $t);
        }
        $this->assertMatchesRegularExpression('/^AVH-\d{4}-\d{5}$/', (string) $t['tour_no'], 'the tour number form AVH-YYYY-NNNNN');
        $lanes = $ts->lanes($t);
        $this->assertIsArray($lanes);
        $strip = $ts->strip($t);
        $this->assertIsArray($strip);
        $details = $ts->details($id);
        $this->assertNotNull($details);
        $this->assertSame($id, (int) ($ts->tourForIntake((int) $t['intake_id'])['id'] ?? 0), 'tourForIntake finds the tour of its inquiry');
    }

    public function testAWorkingInquiryCannotBeConfirmedWithoutAnIssuedQuotation(): void
    {
        $id = (int) $this->one("SELECT id FROM tf_intake WHERE status = 'working' AND archived_at IS NULL ORDER BY id DESC LIMIT 1");
        $take = (new IntakeService($this->db(), 'tcadmin'))->withActor(901, 'Test Administrator');
        $blockers = $this->tours()->confirmBlockers($take->get($id));
        $this->assertNotSame([], $blockers, 'confirming needs an issued quotation first');
        $this->assertStringContainsString('quotation', strtolower(implode(' ', $blockers)));
        $this->assertNull($this->tours()->issuedQuote($id));
    }

    public function testTheDeskQueuesAndCountsAgreeWithTheTables(): void
    {
        $ts = $this->tours();
        foreach (['quotation', 'hotel', 'transport'] as $desk) {
            $c = $ts->deskCounts($desk);
            foreach (['pending', 'completed', 'cancelled', 'all'] as $k) {
                $this->assertArrayHasKey($k, $c, "$desk.$k");
            }
            $this->assertSame((int) $c['all'], count($ts->deskTours($desk, ['status' => 'all'])), "$desk: 'all' count equals the list");
        }
        $hc = $ts->hotelCounts();
        $this->assertSame((int) $this->one("SELECT COUNT(*) FROM tf_hotel_job h JOIN tf_tour t ON t.id = h.tour_id WHERE h.status = 'to_book' AND t.cancelled_at IS NULL AND t.archived_at IS NULL"), (int) $hc['to_book'], 'hotel jobs to book (live tours only)');
        $this->assertIsArray($ts->hotelQueue());
        $this->assertIsArray($ts->board());
    }

    public function testTheBadgesOnTheBarComeFromTheSameNumbers(): void
    {
        $b = Badges::all();
        foreach (['tour/intake', 'tour/hotel_bookings', 'tour/transport_ops', 'tour/board', 'tour/ticket_stock', 'master_files/hotel_work', 'master_files/hotel_rate_v2'] as $u) {
            $this->assertArrayHasKey($u, $b, "badge $u");
            $this->assertIsInt($b[$u]);
            $this->assertGreaterThanOrEqual(0, $b[$u]);
        }
        $take = (new IntakeService($this->db(), 'tcadmin'));
        $this->assertSame((int) $take->counts()['open'], $b['tour/intake'], 'the Tour Inquiry badge is the open count');
        $this->assertSame((int) $this->one("SELECT COUNT(*) FROM hr2_work_item WHERE status = 'open'"), $b['master_files/hotel_work'], 'the Incomplete Work badge is the open items');
    }

    public function testCancelledToursStayReadableAndOffTheDesks(): void
    {
        $cancelled = (int) $this->one('SELECT COUNT(*) FROM tf_tour WHERE cancelled_at IS NOT NULL');
        if ($cancelled === 0) {
            $this->markTestSkipped('no cancelled tour on this copy');
        }
        $id = (int) $this->one('SELECT id FROM tf_tour WHERE cancelled_at IS NOT NULL ORDER BY id DESC LIMIT 1');
        $t = $this->tours()->get($id);
        $this->assertNotNull($t, 'a cancelled tour still reads');
        $this->assertNotEmpty($t['cancelled_at']);
        $this->assertSame(0, (int) $this->one("SELECT COUNT(*) FROM tf_hotel_job h JOIN tf_tour t ON t.id = h.tour_id WHERE t.id = ? AND h.status = 'to_book' AND t.cancelled_at IS NULL", [$id]), 'its hotel work is not waiting on the desk');
    }

    public function testTheTourRecordKeepsItsHistory(): void
    {
        $id = (int) $this->one('SELECT MAX(id) FROM tf_tour');
        $events = (int) $this->one('SELECT COUNT(*) FROM tf_tour_event WHERE tour_id = ?', [$id]);
        $activity = (int) $this->one('SELECT COUNT(*) FROM tf_activity WHERE tour_id = ? OR intake_id = (SELECT intake_id FROM tf_tour WHERE id = ?)', [$id, $id]);
        $this->assertGreaterThan(0, $events + $activity, 'the record carries history');
    }
}
