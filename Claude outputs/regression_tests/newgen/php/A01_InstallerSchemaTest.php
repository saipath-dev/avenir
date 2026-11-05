<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\TourFlow\Installer;
use NewGen\NewGenTestCase;

/** The installed state: schema, desks version, every TourFlow table, the copies folder and its write check. */
final class A01_InstallerSchemaTest extends NewGenTestCase
{
    public function testSchemaIsAtTheLiveVersion(): void
    {
        $this->assertSame('44', (string) $this->one("SELECT v FROM tf_meta WHERE k = 'schema_version'"), 'TourFlow schema version');
        $this->assertSame('9', (string) $this->one("SELECT v FROM tf_meta WHERE k = 'desks_version'"), 'desks seed version (never re-seeded: the access rule)');
    }

    public function testEveryTourFlowTableIsThere(): void
    {
        $names = array_map(static fn ($r) => array_values($r)[0], $this->rows('SHOW TABLES'));
        $tf = array_values(array_filter($names, static fn ($t) => str_starts_with((string) $t, 'tf_')));
        $this->assertGreaterThanOrEqual(63, count($tf), 'tf_ tables (63 at schema 44)');
        foreach (['tf_intake', 'tf_tour', 'tf_quote', 'tf_hotel_job', 'tf_transport_job', 'tf_transport_crew', 'tf_crew_person', 'tf_crew_vehicle', 'tf_crew_rate', 'tf_crew_bata', 'tf_crew_standard', 'tf_ticket', 'tf_place', 'tf_meal', 'tf_photo', 'tf_meta', 'tf_activity'] as $t) {
            $this->assertContains($t, $tf, $t);
        }
    }

    public function testTheCrewMasterCarriesTheSchema44Columns(): void
    {
        $cols = array_column($this->rows('SHOW COLUMNS FROM tf_crew_person'), 'Field');
        foreach (['category', 'category_confirmed', 'nickname', 'bank_account_no', 'bank_verified', 'overnight_bata', 'migrated_from'] as $c) {
            $this->assertContains($c, $cols, "tf_crew_person.$c");
        }
        $cols = array_column($this->rows('SHOW COLUMNS FROM tf_transport_crew'), 'Field');
        foreach (['person_category', 'person_km_rate', 'person_bata_from', 'person_overnight'] as $c) {
            $this->assertContains($c, $cols, "tf_transport_crew.$c");
        }
    }

    public function testTheCopiesFolderIsWhereTheKitsExpectIt(): void
    {
        $this->assertSame(rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'tf_backups', Installer::backupDir());
    }

    public function testTheWriteCheckProbesARealWriteAndCleansUp(): void
    {
        $dir = sys_get_temp_dir() . '/tf_probe_' . bin2hex(random_bytes(3));
        mkdir($dir, 0775, true);
        Installer::assertWritableDir($dir);                       // no exception
        $this->assertSame([], array_values(array_diff(scandir($dir), ['.', '..'])), 'the probe file is removed');
        rmdir($dir);
    }

    public function testTheWriteCheckStillRefusesAFolderThatCannotTakeAFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not writable');
        Installer::assertWritableDir(sys_get_temp_dir() . '/does-not-exist-' . bin2hex(random_bytes(3)));
    }

    public function testASnapshotIsWrittenWholeWithItsChecksum(): void
    {
        $snap = Installer::snapshot($this->db(), 'battery', 'tests');
        $this->assertFileExists($snap['path']);
        $this->assertSame(md5_file($snap['path']), $snap['md5']);
        $this->assertGreaterThan(50, $snap['rows']);
        $this->assertGreaterThanOrEqual(60, count($snap['tables']));
        $this->assertFileExists($snap['path'] . '.json');
        $this->assertStringContainsString('schema 44', (string) file_get_contents($snap['path']));
        $this->assertStringNotContainsString('.part', implode(',', (array) glob(dirname($snap['path']) . '/*.part')), 'no half-written file left behind');
        $listed = array_column(Installer::snapshots(), 'file');
        $this->assertContains($snap['file'], $listed, 'the Admin list shows it');
        @unlink($snap['path']); @unlink($snap['path'] . '.json');
    }
}
