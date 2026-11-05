<?php
declare(strict_types=1);

namespace NewGen\Php;

use NewGen\NewGenTestCase;

/**
 * Every release kit under writable/tf_releases can be rolled back and re-applied on a scratch copy of the tree, file by
 * file by checksum, and never touches the database for a kit that declares no menu entries. (The crew release's kit
 * declares one menu entry; its database side is not exercised here — the kit refuses without a reachable database and
 * says so.)
 */
final class A10_KitsAndRollbackTest extends NewGenTestCase
{
    /** @return string[] kit folders */
    private function kits(): array
    {
        return array_map('dirname', (array) glob(WRITEPATH . 'tf_releases/tf-*/MANIFEST.json'));
    }

    public function testEveryKitHasItsPartsAndItsChecksumsAreTrue(): void
    {
        $kits = $this->kits();
        if ($kits === []) {
            $this->markTestSkipped('no kit on this copy');
        }
        foreach ($kits as $k) {
            foreach (['MANIFEST.json', 'kit_lib.php', 'reapply.php', 'rollback.php', 'REAPPLY.bat', 'ROLLBACK.bat', 'README.txt'] as $f) {
                $this->assertFileExists("$k/$f", basename($k) . "/$f");
            }
            $m = json_decode((string) file_get_contents("$k/MANIFEST.json"), true);
            $this->assertNotEmpty($m['files']);
            foreach ($m['files'] as $f) {
                $this->assertFileExists("$k/release/" . $f['path']);
                $this->assertSame($f['release_md5'], md5_file("$k/release/" . $f['path']), basename($k) . ': release/' . $f['path']);
                if ($f['before_md5'] !== null) {
                    $this->assertFileExists("$k/before/" . $f['path']);
                    $this->assertSame($f['before_md5'], md5_file("$k/before/" . $f['path']), basename($k) . ': before/' . $f['path']);
                }
            }
            $rc = 0; $out = [];
            exec('php -l ' . escapeshellarg("$k/reapply.php") . ' && php -l ' . escapeshellarg("$k/rollback.php") . ' && php -l ' . escapeshellarg("$k/kit_lib.php"), $out, $rc);
            $this->assertSame(0, $rc, basename($k) . ': the kit scripts parse');
        }
    }

    public function testApplyRollBackApplyOnAScratchTree(): void
    {
        $kits = array_filter($this->kits(), static function ($k) {
            $m = json_decode((string) file_get_contents("$k/MANIFEST.json"), true);
            return empty($m['menus']) && empty($m['menu_category']);      // kits with no database side
        });
        if ($kits === []) {
            $this->markTestSkipped('no file-only kit on this copy');
        }
        foreach ($kits as $k) {
            $m = json_decode((string) file_get_contents("$k/MANIFEST.json"), true);
            $tmp = sys_get_temp_dir() . '/kitrb_' . bin2hex(random_bytes(4));
            mkdir("$tmp/avenir/writable/tf_releases", 0775, true);
            mkdir("$tmp/avenir/app/Libraries/TourFlow", 0775, true);
            copy(APPPATH . 'Libraries/TourFlow/Installer.php', "$tmp/avenir/app/Libraries/TourFlow/Installer.php");   // kit_root() looks for it
            exec('cp -r ' . escapeshellarg($k) . ' ' . escapeshellarg("$tmp/avenir/writable/tf_releases/"));
            $kk = "$tmp/avenir/writable/tf_releases/" . basename($k);
            // start from the "before" state
            foreach ($m['files'] as $f) {
                $dst = "$tmp/avenir/" . $f['path'];
                @mkdir(dirname($dst), 0775, true);
                if ($f['before_md5'] !== null) {
                    copy("$k/before/" . $f['path'], $dst);
                } elseif (is_file($dst)) {
                    unlink($dst);
                }
            }
            exec('php ' . escapeshellarg("$kk/reapply.php") . ' --yes 2>&1', $o1, $r1);
            $this->assertSame(0, $r1, basename($k) . ' reapply: ' . implode(' | ', $o1));
            foreach ($m['files'] as $f) {
                $this->assertSame($f['release_md5'], md5_file("$tmp/avenir/" . $f['path']), basename($k) . ' after apply: ' . $f['path']);
            }
            exec('php ' . escapeshellarg("$kk/rollback.php") . ' --yes 2>&1', $o2, $r2);
            $this->assertSame(0, $r2, basename($k) . ' rollback: ' . implode(' | ', $o2));
            foreach ($m['files'] as $f) {
                if ($f['before_md5'] !== null) {
                    $this->assertSame($f['before_md5'], md5_file("$tmp/avenir/" . $f['path']), basename($k) . ' after rollback: ' . $f['path']);
                } else {
                    $this->assertFileDoesNotExist("$tmp/avenir/" . $f['path'], basename($k) . ' after rollback: the added file is moved aside');
                    $this->assertNotSame([], (array) glob("$kk/rolled_back/*/" . $f['path']), 'and kept');
                }
            }
            exec('php ' . escapeshellarg("$kk/reapply.php") . ' --yes 2>&1', $o3, $r3);
            $this->assertSame(0, $r3);
            foreach ($m['files'] as $f) {
                $this->assertSame($f['release_md5'], md5_file("$tmp/avenir/" . $f['path']), basename($k) . ' after the second apply: ' . $f['path']);
            }
            exec('rm -rf ' . escapeshellarg($tmp));
        }
    }

    public function testAKitRefusesATreeThatIsNeitherBeforeNorRelease(): void
    {
        $kits = array_filter($this->kits(), static function ($k) {
            $m = json_decode((string) file_get_contents("$k/MANIFEST.json"), true);
            return empty($m['menus']);
        });
        if ($kits === []) {
            $this->markTestSkipped('no file-only kit on this copy');
        }
        $k = array_values($kits)[0];
        $m = json_decode((string) file_get_contents("$k/MANIFEST.json"), true);
        $tmp = sys_get_temp_dir() . '/kitref_' . bin2hex(random_bytes(4));
        mkdir("$tmp/avenir/writable/tf_releases", 0775, true);
        mkdir("$tmp/avenir/app/Libraries/TourFlow", 0775, true);
        copy(APPPATH . 'Libraries/TourFlow/Installer.php', "$tmp/avenir/app/Libraries/TourFlow/Installer.php");
        exec('cp -r ' . escapeshellarg($k) . ' ' . escapeshellarg("$tmp/avenir/writable/tf_releases/"));
        $kk = "$tmp/avenir/writable/tf_releases/" . basename($k);
        $f = $m['files'][0];
        @mkdir(dirname("$tmp/avenir/" . $f['path']), 0775, true);
        file_put_contents("$tmp/avenir/" . $f['path'], "<?php // somebody's own edit\n");
        exec('php ' . escapeshellarg("$kk/reapply.php") . ' --yes 2>&1', $o, $rc);
        $this->assertSame(2, $rc, 'refused without --force: ' . implode(' | ', $o));
        $this->assertSame("<?php // somebody's own edit\n", file_get_contents("$tmp/avenir/" . $f['path']), 'the stranger\'s file is untouched');
        exec('rm -rf ' . escapeshellarg($tmp));
    }
}
