<?php
// stand-ins for what CodeIgniter gives the library
namespace { function config($n) { $c = new \stdClass(); $c->domain = 'avenirholidays.com'; $c->smtpHost = 'smtp.lolipop.jp'; $c->smtpPort = 465; $c->smtpCrypto = 'ssl'; return $c; } }
namespace {
require __DIR__ . '/src/app/Libraries/TourFlow/MailServer.php';
use App\Libraries\TourFlow\MailServer as M;
$ok = 0; $bad = 0;
function t($name, $cond, $detail = '') { global $ok, $bad; if ($cond) { $ok++; echo "  ok   $name\n"; } else { $bad++; echo "  FAIL $name  $detail\n"; } }
$jp = mb_convert_encoding('このようなホストは不明です。', 'SJIS-win', 'UTF-8');
$err = 'Message could not be sent. Mailer Error: SMTP Error: Could not connect to SMTP host. Failed to connect to serverSMTP server error: Failed to connect to server Additional SMTP info: php_network_getaddresses: getaddrinfo for mail.avenirholidays.com failed: ' . $jp . ' ';
t('the original error is not UTF-8 (what broke the screen)', !mb_check_encoding($err, 'UTF-8') && json_encode(['m' => $err]) === false);
$r = M::readable($err);
t('readable(): valid UTF-8, JSON works', mb_check_encoding($r, 'UTF-8') && json_encode(['m' => $r]) !== false, $r);
t('readable(): Japanese text recovered', str_contains($r, 'このようなホストは不明です。'), $r);
t('readable(): plain reason first, names the server', str_starts_with($r, 'Not sent: the mail server name "mail.avenirholidays.com" could not be found'), $r);
t('readable() twice = once', M::readable($r) === $r);
$auth = M::readable('Message could not be sent. Mailer Error: SMTP Error: Could not authenticate.', 'smtp.lolipop.jp', 'traveljp@avenirholidays.com');
t('wrong password → plain reason', str_starts_with($auth, 'Not sent: the mail server refused the login for traveljp@avenirholidays.com'), $auth);
$to = M::readable('Message could not be sent. Mailer Error: SMTP Error: Could not connect to SMTP host. Failed to connect to server Connection timed out', 'smtp.lolipop.jp');
t('timeout → plain reason', str_starts_with($to, 'Not sent: the mail server "smtp.lolipop.jp" could not be reached'), $to);
t('ordinary UTF-8 text unchanged', M::readable('Some other reply ’ é') === 'Some other reply ’ é');
t('Western Windows text converted', mb_check_encoding(M::utf8("caf\xe9"), 'UTF-8'));
$s = M::forAddress('traveljp@avenirholidays.com', 'mail.avenirholidays.com');
t('company mailbox → smtp.lolipop.jp:465 ssl', $s['host'] === 'smtp.lolipop.jp' && $s['port'] === 465 && $s['secure'] === 'ssl' && $s['company'], json_encode($s));
$s = M::forAddress('Land@AvenirHolidays.com', '');
t('company mailbox, any case, empty host → company server', $s['host'] === 'smtp.lolipop.jp', json_encode($s));
$s = M::forAddress('memene@mailinator.co', 'smtp.gmail.com');
t('other domain keeps its own host', $s['host'] === 'smtp.gmail.com' && $s['port'] === 465 && !$s['company'], json_encode($s));
$s = M::forAddress('x@notavenirholidays.com', 'h.example');
t('look-alike domain is not the company', $s['host'] === 'h.example', json_encode($s));
echo "$ok ok, $bad failed\n";
}
