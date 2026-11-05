<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\HotelRates\HotelCodes;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\HotelRates\Installer;
use App\Libraries\HotelRates\WorkQueueService;
use NewGen\NewGenTestCase;

/**
 * The hotel code HTL-001 … (Shantha, 6 Oct 2026): every hotel on the master has one, given by the system, unique, kept
 * through renames and archiving, never reused; HTL-### is the hotel that was H### on the old Supplier master; the number
 * comes from one atomic sequence, so two records created at the same instant never share a code.
 *
 * Every row this suite creates it removes again (the sequence moves on — that is the point: numbers are never reused).
 */
final class A13_HotelCodesTest extends NewGenTestCase
{
    private const PROBE_ACC_PREFIX = '6-9-11-97';     // accounts that exist nowhere else: probe rows only

    private function svc(string $user = 'battery'): HotelRateService
    {
        return new HotelRateService($this->db(), $user);
    }

    private function codes(): HotelCodes
    {
        return new HotelCodes($this->db(), 'battery');
    }

    /** Remove a probe hotel and everything that hangs off it (the sequence is left where it is). */
    private function dropHotel(int $id): void
    {
        $this->db()->query('DELETE FROM hr2_hotel_account WHERE hotel_id = ?', [$id]);
        $this->db()->query('DELETE FROM hr2_hotel_code WHERE hotel_id = ?', [$id]);
        $this->db()->query("DELETE FROM hr2_audit WHERE entity = 'hotel' AND entity_id = ?", [$id]);
        $this->db()->query('DELETE FROM hr2_hotel WHERE id = ?', [$id]);
    }

    protected function tearDown(): void
    {
        // whatever a failed test left behind
        foreach ($this->rows("SELECT id FROM hr2_hotel WHERE name LIKE 'Battery probe hotel%' OR primary_sup_acc_no LIKE ?", [self::PROBE_ACC_PREFIX . '%']) as $r) {
            $this->dropHotel((int) $r['id']);
        }
        $this->db()->query('DELETE FROM hr2_hotel_code WHERE sup_acc_no LIKE ? AND hotel_id IS NULL', [self::PROBE_ACC_PREFIX . '%']);
        $this->db()->query('DELETE FROM supplier WHERE Sup_Acc_No LIKE ?', [self::PROBE_ACC_PREFIX . '%']);
        parent::tearDown();
    }

    // ---- the schema and the backfill --------------------------------------------------------------------------------

    public function testSchemaEightIsInstalled(): void
    {
        $this->assertSame(8, Installer::SCHEMA_VERSION);
        $this->assertSame('8', (string) $this->one("SELECT v FROM hr2_meta WHERE k = 'schema_version'"));
        foreach (['hr2_sequence', 'hr2_hotel_code'] as $t) {
            $this->assertSame(1, (int) $this->one('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$t]), $t);
        }
        $col = $this->rows("SHOW COLUMNS FROM hr2_hotel LIKE 'hotel_code'");
        $this->assertCount(1, $col, 'hr2_hotel.hotel_code');
        $this->assertSame(1, (int) $this->one("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'hr2_hotel' AND index_name = 'uq_hr2_hotel_code' AND non_unique = 0"), 'the code is UNIQUE on the hotel');
        foreach (['uq_hr2_hotel_code_no', 'uq_hr2_hotel_code_hotel'] as $ix) {
            $this->assertSame(1, (int) $this->one("SELECT COUNT(DISTINCT index_name) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'hr2_hotel_code' AND index_name = ? AND non_unique = 0", [$ix]), $ix);
        }
        $this->assertTrue($this->codes()->ready());
    }

    public function testEveryHotelHasOneDistinctWellFormedCode(): void
    {
        $n = (int) $this->one('SELECT COUNT(*) FROM hr2_hotel');
        $this->assertGreaterThan(300, $n);
        $this->assertSame($n, (int) $this->one('SELECT COUNT(hotel_code) FROM hr2_hotel'), 'no hotel without a code — active, inactive or merged');
        $this->assertSame($n, (int) $this->one('SELECT COUNT(DISTINCT hotel_code) FROM hr2_hotel'), 'no two hotels share a code');
        $this->assertSame(0, (int) $this->one("SELECT COUNT(*) FROM hr2_hotel WHERE hotel_code NOT REGEXP '^HTL-[0-9]{3,}$'"), 'HTL-### everywhere');
        $this->assertSame($n, (int) $this->one('SELECT COUNT(*) FROM hr2_hotel h JOIN hr2_hotel_code c ON c.hotel_id = h.id AND c.code = h.hotel_code'), 'the register agrees with the hotel on every code');
        $this->assertSame('HTL-001', (string) $this->one('SELECT MIN(hotel_code) FROM hr2_hotel'), 'numbering begins at HTL-001');
    }

    public function testTheLegacyNumberingIsKept(): void
    {
        // HTL-### is the hotel whose supplier account carried H### on the old Supplier master
        $mismatch = (int) $this->one("SELECT COUNT(*) FROM hr2_hotel h JOIN supplier s ON s.Sup_Acc_No = h.primary_sup_acc_no AND s.sup_cat = 1 AND s.hotel_code REGEXP '^H[0-9]+$'
                                      WHERE CAST(SUBSTRING(s.hotel_code, 2) AS UNSIGNED) <> CAST(SUBSTRING(h.hotel_code, 5) AS UNSIGNED)");
        $this->assertSame(0, $mismatch, 'every legacy H-number equals the HTL number of its hotel');
        $this->assertSame('H001', (string) $this->one("SELECT s.hotel_code FROM hr2_hotel h JOIN supplier s ON s.Sup_Acc_No = h.primary_sup_acc_no WHERE h.hotel_code = 'HTL-001' LIMIT 1"));
        $legacyMax = (int) $this->one("SELECT MAX(CAST(SUBSTRING(hotel_code, 2) AS UNSIGNED)) FROM supplier WHERE sup_cat = 1 AND hotel_code REGEXP '^H[0-9]+$'");
        $this->assertGreaterThanOrEqual($legacyMax, $this->codes()->lastNumber(), 'the sequence stands at or beyond the highest legacy number');
        $this->assertGreaterThanOrEqual($legacyMax, (int) $this->one('SELECT hotel_code FROM invoice_no'), 'the old counter was brought up to the numbers in use');
        // the legacy column itself was not rewritten
        $this->assertSame(0, (int) $this->one("SELECT COUNT(*) FROM supplier WHERE sup_cat = 1 AND hotel_code LIKE 'HTL-%'"), 'supplier.hotel_code keeps its H### spelling');
    }

    public function testBackfillIsIdempotent(): void
    {
        $before = $this->codes()->lastNumber();
        $r = $this->codes()->backfill();
        $this->assertSame(0, $r['given'], 'a second backfill gives nothing');
        $this->assertSame((int) $this->one('SELECT COUNT(*) FROM hr2_hotel'), $r['already']);
        $this->assertSame($before, $this->codes()->lastNumber(), 'and moves the sequence nowhere');
        $this->assertSame([], $r['conflicts']);
    }

    // ---- the format -----------------------------------------------------------------------------------------------------

    public function testFormatAndParse(): void
    {
        $this->assertSame('HTL-007', HotelCodes::format(7));
        $this->assertSame('HTL-335', HotelCodes::format(335));
        $this->assertSame('HTL-1000', HotelCodes::format(1000), 'the width is a minimum, never a ceiling');
        foreach (['HTL-007' => 7, 'htl 7' => 7, 'HTL007' => 7, 'H007' => 7, 'h7' => 7, '7' => 7, '0012' => 12] as $in => $out) {
            $this->assertSame($out, HotelCodes::parse((string) $in), (string) $in);
        }
        foreach (['', 'Cinnamon', 'HTL-', 'H', '6-9-11-10549', 'TRN-001'] as $in) {
            $this->assertNull(HotelCodes::parse($in), $in);
        }
    }

    // ---- new hotels -----------------------------------------------------------------------------------------------------

    public function testANewHotelIsBornWithTheNextCodeAndNobodyTypesIt(): void
    {
        $before = $this->codes()->lastNumber();
        $id = $this->svc()->createHotel('Battery probe hotel A', self::PROBE_ACC_PREFIX . '001', null);
        $h = $this->svc()->getHotel($id);
        $this->assertSame(HotelCodes::format($before + 1), $h['hotel_code']);
        $this->assertSame($h['hotel_code'], $this->codes()->codeOf($id));
        $reg = $this->rows('SELECT * FROM hr2_hotel_code WHERE hotel_id = ?', [$id]);
        $this->assertCount(1, $reg);
        $this->assertSame('create', $reg[0]['how']);
        $this->assertSame('battery', $reg[0]['assigned_by']);
        $this->assertSame(self::PROBE_ACC_PREFIX . '001', $reg[0]['sup_acc_no']);
        $this->assertSame('Battery probe hotel A', $reg[0]['hotel_name_at']);
        $this->assertSame($id, (int) $this->codes()->hotelByCode($h['hotel_code'])['id']);
        $this->dropHotel($id);
    }

    public function testRenamingArchivingAndMergingKeepTheCode(): void
    {
        $svc = $this->svc();
        $a = $svc->createHotel('Battery probe hotel B', self::PROBE_ACC_PREFIX . '002', null);
        $b = $svc->createHotel('Battery probe hotel C', self::PROBE_ACC_PREFIX . '003', null);
        $codeA = $this->codes()->codeOf($a);
        $codeB = $this->codes()->codeOf($b);
        $this->assertNotSame($codeA, $codeB);

        $svc->updateHotel($a, ['name' => 'Battery probe hotel B (renamed)', 'address' => 'Somewhere 1']);
        $this->assertSame($codeA, $this->codes()->codeOf($a), 'a rename changes nothing');
        $this->assertSame('Battery probe hotel B', (string) $this->one('SELECT hotel_name_at FROM hr2_hotel_code WHERE hotel_id = ?', [$a]), 'the register remembers the name at the time the code was given');

        $svc->updateHotel($a, ['status' => 'inactive']);
        $this->assertSame($codeA, $this->codes()->codeOf($a), 'an archived hotel keeps its code');
        $this->assertSame('inactive', (string) $this->one('SELECT status FROM hr2_hotel WHERE id = ?', [$a]));

        $svc->mergeHotels($a, $b);
        $this->assertSame('merged', (string) $this->one('SELECT status FROM hr2_hotel WHERE id = ?', [$b]));
        $this->assertSame($codeB, $this->codes()->codeOf($b), 'a merged hotel keeps its code');
        $this->assertSame($codeA, $this->codes()->codeOf($a), 'and so does the one that absorbed it');
        $this->assertSame($b, (int) $this->codes()->hotelByCode($codeB)['id'], 'the merged hotel is still found by its code');
        $this->assertSame(1, (int) $this->one('SELECT COUNT(*) FROM hr2_hotel_code WHERE code IN (?, ?) AND hotel_id = ?', [$codeA, $codeB, $a]), 'the kept hotel took no second code');

        $svc->unmergeHotel($b);
        $this->assertSame($codeB, $this->codes()->codeOf($b));
        $this->dropHotel($b);
        $this->dropHotel($a);
    }

    public function testAHotelThatLostItsCodeGetsTheSameOneBackWhenFixed(): void
    {
        $svc = $this->svc();
        $id = $svc->createHotel('Battery probe hotel D', self::PROBE_ACC_PREFIX . '004', null);
        $code = $this->codes()->codeOf($id);
        $this->db()->query('UPDATE hr2_hotel SET hotel_code = NULL WHERE id = ?', [$id]);      // a record with the code missing
        $this->assertNull($this->codes()->codeOf($id));
        $svc->updateHotel($id, ['address' => 'completed by a person']);                        // completed or fixed → the code comes back
        $this->assertSame($code, $this->codes()->codeOf($id), 'the register gives the same code back, never a new one');
        $this->dropHotel($id);
    }

    public function testAHotelInsertedWithoutACodeGetsOneTheFirstTimeItIsTouched(): void
    {
        $svc = $this->svc();
        $this->db()->query("INSERT INTO hr2_hotel (name, primary_sup_acc_no, currency, status, created_at, created_by) VALUES ('Battery probe hotel E', ?, 'USD', 'active', NOW(), 'battery')", [self::PROBE_ACC_PREFIX . '005']);
        $id = (int) $this->db()->insertID();
        $this->assertNull($this->codes()->codeOf($id));
        $before = $this->codes()->lastNumber();
        $svc->updateHotel($id, ['notes' => 'touched']);
        $this->assertSame(HotelCodes::format($before + 1), $this->codes()->codeOf($id));
        $this->assertSame('fix', (string) $this->one('SELECT how FROM hr2_hotel_code WHERE hotel_id = ?', [$id]));
        $this->dropHotel($id);
    }

    // ---- the old Supplier master and the attach flow ---------------------------------------------------------------------

    public function testTheOldScreenAndTheMasterShareOneNumbering(): void
    {
        // the Supplier master reserves the number for the account first (H###) …
        $acc = self::PROBE_ACC_PREFIX . '006';
        $no = $this->codes()->reserveForAccount($acc, 'Battery probe hotel F', 'supplier-master');
        $this->assertSame($this->codes()->lastNumber(), $no);
        $this->assertSame($no, $this->codes()->reserveForAccount($acc, 'Battery probe hotel F', 'supplier-master'), 'asking twice for the same account gives the same number');
        // … and the hotel born for that account takes the very same number as HTL-###
        $id = $this->svc()->createHotel('Battery probe hotel F', $acc, null);
        $this->assertSame(HotelCodes::format($no), $this->codes()->codeOf($id));
        $this->assertSame(1, (int) $this->one('SELECT COUNT(*) FROM hr2_hotel_code WHERE sup_acc_no = ?', [$acc]), 'one register row, not two');
        // an account that already belongs to a hotel is refused a second number
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already belongs to the hotel');
        try {
            $this->codes()->reserveForAccount($acc, 'Battery probe hotel F again', 'supplier-master');
        } finally {
            $this->dropHotel($id);
        }
    }

    public function testASupplierCreatedBeforeThisReleaseKeepsItsLegacyNumberWhenItsHotelIsBorn(): void
    {
        $acc = self::PROBE_ACC_PREFIX . '007';
        $legacyNo = $this->codes()->lastNumber() + 1;                                          // what the old counter would have printed
        $this->db()->query("INSERT INTO supplier (Sup_Acc_No, Name, Address, sup_cat, hotel_code, create_user) VALUES (?, 'Battery probe hotel G', 'Talpe', 1, ?, 'battery')", [$acc, 'H' . str_pad((string) $legacyNo, 3, '0', STR_PAD_LEFT)]);
        $this->assertSame($legacyNo, $this->codes()->legacyNumberFor($acc));
        $id = $this->svc()->createHotel('Battery probe hotel G', $acc, null);
        $this->assertSame(HotelCodes::format($legacyNo), $this->codes()->codeOf($id), 'HTL-### = H###');
        $this->assertSame('create-legacy', (string) $this->one('SELECT how FROM hr2_hotel_code WHERE hotel_id = ?', [$id]));
        $this->assertSame($legacyNo, $this->codes()->lastNumber(), 'the sequence was raised past the legacy number');
        $this->dropHotel($id);
        $this->db()->query('DELETE FROM supplier WHERE Sup_Acc_No = ?', [$acc]);
    }

    public function testTheAttachDialogFindsSuppliersByNameCodeOrAccount(): void
    {
        $q = new WorkQueueService($this->db(), 'battery', $this->svc());
        $byCode = $q->searchSuppliers('HTL-002');
        $this->assertCount(1, $byCode);
        $this->assertSame('HTL-002', $byCode[0]['hotel_code']);
        $this->assertSame('H002', $byCode[0]['legacy_code']);
        $this->assertSame($byCode, $q->searchSuppliers('H002'), 'the old spelling finds the same supplier');
        $this->assertSame($byCode[0]['acc'], $q->searchSuppliers($byCode[0]['acc'])[0]['acc'], 'the account number finds it');
        $this->assertNotEmpty($q->searchSuppliers(mb_substr($byCode[0]['name'], 0, 8)), 'part of the name finds it');
        $this->assertSame([], $q->searchSuppliers(''), 'nothing is listed for nothing');
        foreach ($q->searchSuppliers('Cinnamon') as $r) {
            $this->assertMatchesRegularExpression('/^6-9-11-/', $r['acc']);
        }
    }

    public function testWorkItemsCarryTheHotelCode(): void
    {
        $q = new WorkQueueService($this->db(), 'battery', $this->svc());
        $items = $q->list(['status' => 'open']);
        $withHotel = array_filter($items, static fn ($i) => !empty($i['hotel_id']));
        $this->assertNotEmpty($withHotel);
        foreach ($withHotel as $i) {
            $this->assertMatchesRegularExpression('/^HTL-\d{3,}$/', (string) $i['hotel_code'], $i['title']);
            $this->assertSame((string) $this->one('SELECT hotel_code FROM hr2_hotel WHERE id = ?', [(int) $i['hotel_id']]), (string) $i['hotel_code']);
        }
        $one = array_values($withHotel)[0];
        $found = $q->list(['status' => 'open', 'q' => $one['hotel_code']]);
        $this->assertNotEmpty($found, 'searching the list by a hotel code finds that hotel\'s items');
        foreach ($found as $f) {
            $this->assertSame($one['hotel_code'], $f['hotel_code']);
        }
        $this->assertSame('No supplier account', WorkQueueService::KINDS['missing_supplier']['label']);
    }

    // ---- never twice ------------------------------------------------------------------------------------------------------

    public function testTwentyHotelsCreatedAtTheSameInstantGetTwentyDifferentCodes(): void
    {
        $before = $this->codes()->lastNumber();
        $php = PHP_BINARY;
        $probe = __DIR__ . '/_bin/hotel_code_probe.php';
        $this->assertFileExists($probe);
        $procs = [];
        $pipes = [];
        for ($i = 1; $i <= 20; $i++) {
            $p = proc_open([$php, $probe, (string) $i], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp);
            $this->assertIsResource($p);
            $procs[$i] = $p;
            $pipes[$i] = $pp;
        }
        $out = [];
        foreach ($procs as $i => $p) {
            $out[$i] = trim(trim((string) stream_get_contents($pipes[$i][1])) . ' ' . trim((string) stream_get_contents($pipes[$i][2])));
            fclose($pipes[$i][1]);
            fclose($pipes[$i][2]);
            $this->assertSame(0, proc_close($p), "probe {$i}: {$out[$i]}");
        }
        $codes = [];
        foreach ($out as $i => $line) {
            $this->assertMatchesRegularExpression('/^\d+ HTL-\d{3,}$/', $line, "probe {$i}");
            $codes[] = explode(' ', $line)[1];
        }
        $this->assertCount(20, array_unique($codes), 'twenty different codes');
        $this->assertSame($before + 20, $this->codes()->lastNumber(), 'the sequence moved by exactly twenty');
        $nums = array_map(static fn ($c) => (int) substr($c, 4), $codes);
        sort($nums);
        $this->assertSame(range($before + 1, $before + 20), $nums, 'no gap, no repeat');
        foreach ($this->rows("SELECT id FROM hr2_hotel WHERE name LIKE 'Battery probe hotel P%'") as $r) {
            $this->dropHotel((int) $r['id']);
        }
    }

    public function testACodeIsNeverGivenTwiceEvenAfterItsHotelIsGone(): void
    {
        $id = $this->svc()->createHotel('Battery probe hotel H', self::PROBE_ACC_PREFIX . '008', null);
        $code = $this->codes()->codeOf($id);
        $this->dropHotel($id);                                                                  // the record is gone (a thing live never does; the copy's clean-up)
        $id2 = $this->svc()->createHotel('Battery probe hotel I', self::PROBE_ACC_PREFIX . '009', null);
        $this->assertGreaterThan((int) substr($code, 4), (int) substr((string) $this->codes()->codeOf($id2), 4), 'the next hotel takes a higher number, never the freed one');
        $this->dropHotel($id2);
    }

    public function testTheQuotationHotelSearchCarriesTheCode(): void
    {
        $svc = new \App\Libraries\TourFlow\IntakeService($this->db(), 'battery');
        $loc = (int) $this->one("SELECT location_id FROM hr2_hotel WHERE status = 'active' AND location_id IS NOT NULL LIMIT 1");
        $hotels = $svc->hotelsIn($loc);
        $this->assertNotEmpty($hotels);
        foreach ($hotels as $h) {
            $this->assertArrayHasKey('code', $h);
            $this->assertMatchesRegularExpression('/^HTL-\d{3,}$/', (string) $h['code'], $h['name']);
        }
    }
}
