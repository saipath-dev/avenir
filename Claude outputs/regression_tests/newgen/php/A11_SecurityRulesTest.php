<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\TourFlow\EditRequests;
use App\Libraries\TourFlow\Reauth;
use NewGen\NewGenTestCase;

/**
 * The rules that protect money and authority: a password is asked again before a sensitive action and a wrong one is
 * refused; Edit Request Authority is held only when granted; test accounts exist only on the copy; no secret is in the
 * battery; the sessions table and the login guard are in place.
 */
final class A11_SecurityRulesTest extends NewGenTestCase
{
    public function testReauthRefusesAWrongOrEmptyPassword(): void
    {
        $r = Reauth::verify($this->db(), 901, '', 'battery');
        $this->assertFalse($r['ok']);
        $r = Reauth::verify($this->db(), 901, 'definitely-not-the-password', 'battery');
        $this->assertFalse($r['ok']);
        $this->assertNotSame('', (string) $r['message']);
        $r = Reauth::verify($this->db(), null, 'x', 'battery');
        $this->assertFalse($r['ok'], 'no signed-in user, no verification');
    }

    public function testReauthAcceptsTheRightPasswordOfTheTestAccount(): void
    {
        $r = Reauth::verify($this->db(), 901, 'tc-admin-2026', 'battery');
        $this->assertTrue($r['ok'], json_encode($r));
    }

    public function testEditRequestAuthorityIsNeverImplied(): void
    {
        $this->assertTrue(EditRequests::installed($this->db()));
        foreach ([902, 903, 904] as $uid) {
            $this->assertFalse(EditRequests::holds($this->db(), $uid), "desk tester $uid holds no Edit Request Authority");
        }
        $this->assertFalse(EditRequests::holds($this->db(), null));
    }

    public function testTheTestAccountsLiveOnlyOnTheCopy(): void
    {
        $this->assertSame(5, (int) $this->one("SELECT COUNT(*) FROM user_acc_tbl WHERE user IN ('tcadmin','tcquote','tctransport','tchotel','tcmgmt')"));
        $this->assertSame(1, (int) $this->one("SELECT user_role FROM user_acc_tbl WHERE user = 'tcadmin'"), 'the test administrator is user_role 1 (installer rights)');
        $this->assertSame(0, (int) $this->one("SELECT COUNT(*) FROM user_acc_tbl WHERE user IN ('tcquote','tctransport','tchotel','tcmgmt') AND user_role = 1"), 'no desk tester has installer rights');
        foreach ($this->rows("SELECT user, pass FROM user_acc_tbl WHERE user LIKE 'tc%'") as $u) {
            $this->assertStringStartsWith('$2y$', (string) $u['pass'], $u['user'] . ': a bcrypt hash, never a plain password');
        }
    }

    public function testTheBatteryCarriesNoSecret(): void
    {
        $dir = dirname(__DIR__);
        $bad = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            /** @var \SplFileInfo $f */
            if (!in_array($f->getExtension(), ['php', 'py', 'sh', 'xml', 'md', 'txt', 'json'], true) || $f->getPathname() === __FILE__) {
                continue;
            }
            $src = (string) file_get_contents($f->getPathname());
            if (preg_match('/(sk-ant-|AIza[0-9A-Za-z_-]{20,}|SMTPPass\s*=\s*[\'"][^\'"]+|password\s*=\s*[\'"](?!tc-)[^\'"]{8,}[\'"])/i', $src)) {
                $bad[] = str_replace($dir, 'tests/newgen', $f->getPathname());
            }
        }
        $this->assertSame([], $bad, 'files that look like they carry a key or a real password');
    }

    public function testSessionsAreKeptInTheDatabaseAndLoginsAreThrottled(): void
    {
        $this->assertSame(1, (int) $this->one("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'ci_sessions'"));
        $this->assertSame(1, (int) $this->one("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'user_sessions'"));
        $src = (string) file_get_contents(APPPATH . 'Controllers/LoginController.php');
        $this->assertStringContainsString('password_verify', $src);
        $this->assertStringContainsString('guardLoginAttempt', $src, 'the login throttle');
        $this->assertStringContainsString('regenerate(true)', $src, 'the session id is regenerated at sign-in');
    }
}
