<?php
declare(strict_types=1);

namespace NewGen\Php;

use NewGen\NewGenTestCase;

/**
 * The program files themselves: every PHP file the application can load parses; the new-generation views and
 * libraries carry no debugging leftovers; the kits on record match what is installed.
 */
final class A04_SourceIntegrityTest extends NewGenTestCase
{
    /** @return string[] */
    private function phpFiles(): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(APPPATH, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            /** @var \SplFileInfo $f */
            if ($f->getExtension() !== 'php') {
                continue;
            }
            $p = $f->getPathname();
            // the tree carries old copies the program never loads ("… copy.php", "…_backup.php", dated copies); they are not judged here
            if (preg_match('#(\scopy|copy\s?\d*|_backup|backup_|\s-\s|\(\d\)|\d{4}-\d{2}-\d{2}|shakya|hasintha|old)\.php$#i', basename($p)) || str_contains($p, '/Dump/') || str_contains($p, '/dump/')) {
                continue;
            }
            $out[] = $p;
        }
        sort($out);
        return $out;
    }

    public function testEveryLoadablePhpFileParses(): void
    {
        $bad = [];
        $files = $this->phpFiles();
        $this->assertGreaterThan(600, count($files));
        foreach (array_chunk($files, 40) as $chunk) {
            foreach ($chunk as $p) {
                $out = [];
                $rc = 0;
                exec('php -l ' . escapeshellarg($p) . ' 2>&1', $out, $rc);
                if ($rc !== 0) {
                    $bad[] = str_replace(APPPATH, 'app/', $p) . ': ' . trim(implode(' ', $out));
                }
            }
        }
        $this->assertSame([], $bad, 'files with a syntax error');
    }

    public function testTheNewGenerationFilesCarryNoDebugLeftovers(): void
    {
        // the new-generation files: the TourFlow and Hotel Rates libraries, the Tour Desk controllers, and every view that carries the Tour Desk bar
        $files = array_merge((array) glob(APPPATH . 'Libraries/TourFlow/*.php'), (array) glob(APPPATH . 'Libraries/HotelRates/*.php'), (array) glob(APPPATH . 'Controllers/Tour/TourDesk*.php'), (array) glob(APPPATH . 'Controllers/Tour/TourManage*.php'), (array) glob(APPPATH . 'Controllers/Tour/TourIntake*.php'), (array) glob(APPPATH . 'Controllers/MasterFiles/Crew*.php'), (array) glob(APPPATH . 'Controllers/MasterFiles/Hotel*V2*.php'));
        foreach (array_merge((array) glob(APPPATH . 'Views/pages/tour/*.php'), (array) glob(APPPATH . 'Views/pages/master_files/*.php'), (array) glob(APPPATH . 'Views/layouts/t*.php')) as $v) {
            $src = (string) file_get_contents($v);
            if (str_contains($src, "layouts/tourdesk_bar") || str_contains($src, "layouts/tgs_bar") || str_contains($src, 'tf-fold-toggle')) {
                $files[] = $v;
            }
        }
        $hits = [];
        foreach ($files as $p) {
            $src = (string) file_get_contents($p);
            if (preg_match('/^\s*(var_dump|print_r|dd|dump)\(/m', $src) || preg_match('/\bconsole\.log\(/', $src)) {
                $hits[] = str_replace(APPPATH, 'app/', $p);
            }
        }
        $this->assertSame([], $hits, 'debugging calls left in the new-generation files');
    }

    public function testTheReleaseKitsOnRecordMatchWhatIsInstalled(): void
    {
        $kits = (array) glob(WRITEPATH . 'tf_releases/tf-*/MANIFEST.json');
        if ($kits === []) {
            $this->markTestSkipped('no release kit under writable/tf_releases on this copy');
        }
        $checked = 0;
        foreach ($kits as $mf) {
            $m = json_decode((string) file_get_contents($mf), true);
            if (!is_array($m) || empty($m['files'])) {
                continue;
            }
            foreach ($m['files'] as $f) {
                $live = ROOTPATH . $f['path'];
                if (!is_file($live)) {
                    continue;
                }
                $md5 = md5_file($live);
                $this->assertContains($md5, array_filter([$f['release_md5'], $f['before_md5']]), basename(dirname($mf)) . ': ' . $f['path'] . ' is neither the kit\'s release nor its before version (someone changed it since)');
                $checked++;
            }
        }
        $this->assertGreaterThan(0, $checked);
    }

    public function testTheViewsOfTheReleaseCompile(): void
    {
        foreach (['Views/layouts/tf_ux.php', 'Views/layouts/tourdesk_bar.php', 'Views/layouts/tgs_bar.php', 'Views/pages/tour/intake.php', 'Views/pages/tour/transport_ops.php', 'Views/pages/master_files/crew_people.php', 'Views/pages/master_files/hotel_work.php'] as $v) {
            $rc = 0; $out = [];
            exec('php -l ' . escapeshellarg(APPPATH . $v) . ' 2>&1', $out, $rc);
            $this->assertSame(0, $rc, $v);
        }
    }
}
