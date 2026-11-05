<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\HotelRates\Installer as HrInstaller;
use App\Libraries\HotelRates\QuoteReadiness;
use App\Libraries\HotelRates\WorkQueueService;
use NewGen\NewGenTestCase;

/**
 * The Hotel Rates module and the Hotel Incomplete Work queue: the installed version, hotels and contracts on the copy,
 * a rate looked up by date (never guessed), the work queue's scan (idempotent), its counts, Ignore and Done rules.
 */
final class A09_HotelRatesAndWorkQueueTest extends NewGenTestCase
{
    private function rates(): HotelRateService
    {
        return new HotelRateService($this->db(), 'tcadmin');
    }

    public function testTheModuleIsAtItsCurrentVersion(): void
    {
        $this->assertSame(HrInstaller::SCHEMA_VERSION, HrInstaller::installedVersion($this->db()));
        $n = (int) $this->one("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'hr2\\_%'");
        $this->assertGreaterThanOrEqual(30, $n);
    }

    public function testHotelsAndContractsRead(): void
    {
        $svc = $this->rates();
        $hotels = $svc->searchHotels('', 1000);
        $this->assertSame((int) $this->one("SELECT COUNT(*) FROM hr2_hotel WHERE status = 'active'"), count($hotels), 'every active hotel listed');
        $this->assertGreaterThan(100, count($hotels));
        $withPlans = (int) $this->one("SELECT hotel_id FROM hr2_contract WHERE status = 'approved' GROUP BY hotel_id ORDER BY COUNT(*) DESC LIMIT 1");
        $this->assertGreaterThan(0, $withPlans);
        $h = $svc->getHotel($withPlans);
        $this->assertNotNull($h);
        $contracts = $svc->contracts($withPlans);
        $this->assertNotSame([], $contracts);
        foreach ($contracts as $c) {
            $this->assertContains($c['status'], ['draft', 'approved', 'superseded', 'rejected', 'archived'], 'a known contract status');
        }
        $found = $svc->searchHotels(substr((string) $h['name'], 0, 6), 50);
        $this->assertNotSame([], $found, 'search by a part of the name');
    }

    public function testARateIsLookedUpByDateOrNotAtAll(): void
    {
        $svc = $this->rates();
        $row = $this->rows("SELECT c.hotel_id, c.valid_from, c.valid_to, h.name FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.status = 'approved' AND c.valid_from IS NOT NULL ORDER BY c.id LIMIT 1");
        if ($row === []) {
            $this->markTestSkipped('no approved contract on this copy');
        }
        $r = $row[0];
        $on = $svc->ratesOn((int) $r['hotel_id'], (string) $r['valid_from']);
        $this->assertIsArray($on);
        $cov = $svc->coverage((int) $r['hotel_id'], (string) $r['valid_from'], (string) ($r['valid_to'] ?: $r['valid_from']));
        $this->assertIsArray($cov);
        $none = $svc->ratesOn((int) $r['hotel_id'], '1999-01-01');
        $this->assertIsArray($none);
        $cells = array_filter($none['cells'] ?? $none['rates'] ?? $none, static fn ($c) => is_array($c) && !empty($c['rate']));
        $this->assertSame([], array_values($cells), 'a date no plan covers prices nothing');
    }

    public function testTheWorkQueueScanIsIdempotentAndItsCountsAgree(): void
    {
        $wq = new WorkQueueService($this->db(), 'tcadmin');
        $first = $wq->scan();
        $second = $wq->scan();
        $this->assertSame(0, (int) $second['new'], 'a second scan finds nothing new');
        $this->assertSame(0, (int) $second['reopened']);
        $open = (int) $this->one("SELECT COUNT(*) FROM hr2_work_item WHERE status = 'open'");
        $this->assertSame($open, (int) $second['open']);
        $this->assertSame($open, $wq->openCount());
        $counts = $wq->counts();
        $this->assertSame($open, array_sum(array_map(static fn ($k) => (int) $k['n'], $counts)), 'the kinds add up to the open total');
        $list = $wq->list(['status' => 'open']);
        $this->assertSame($open, count($list['items'] ?? $list), 'the Open list is the open items');
        $this->assertStringContainsString('Hotel Incomplete Work', $wq->exportText(['status' => 'open']));
    }

    public function testIgnoreIsPerItemAndSurvivesTheNextScan(): void
    {
        $wq = new WorkQueueService($this->db(), 'tcadmin');
        $id = (int) $this->one("SELECT id FROM hr2_work_item WHERE status = 'open' AND source = 'auto' ORDER BY id LIMIT 1");
        $this->assertGreaterThan(0, $id);
        $openBefore = $wq->openCount();
        $it = $wq->resolve($id, 'ignored', 'battery: not used');
        $this->assertSame('ignored', $it['status']);
        $this->assertStringContainsString('Ignored by tcadmin', (string) $it['note']);
        $this->assertSame($openBefore - 1, $wq->openCount(), 'an ignored item leaves the open count');
        $wq->scan();
        $this->assertSame('ignored', (string) $this->one('SELECT status FROM hr2_work_item WHERE id = ?', [$id]), 'the next scan does not reopen an ignored item');
        $this->assertSame($openBefore - 1, $wq->openCount());
        // Done is different: found again, it reopens
        $wq->resolve($id, 'done', 'battery: pretend done');
        $wq->scan();
        $st = (string) $this->one('SELECT status FROM hr2_work_item WHERE id = ?', [$id]);
        $this->assertContains($st, ['open', 'done'], 'a done item found again reopens (unless its cause is gone)');
        $wq->resolve($id, 'open', 'battery: back as it was');
        $this->assertSame($openBefore, $wq->openCount(), 'back as it was');
    }

    public function testReadinessReportsWhatCannotBePriced(): void
    {
        $qr = new QuoteReadiness($this->db());
        $rep = $qr->report();
        $this->assertIsArray($rep);
        $this->assertNotSame([], $qr->quotationTypes());
        $this->assertNotSame('', $qr->defaultMarket());
    }
}
