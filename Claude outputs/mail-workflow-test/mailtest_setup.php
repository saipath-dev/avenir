<?php
/**
 * TEST COPY ONLY (avenir_mailtest) — the one-time setup of the test database, and its removal after the test.
 *
 *   http://localhost/avenir_mailtest/mailtest_setup.php     (opens only on the server itself)
 *
 * Create: makes the new database avenir_mailtest on this computer's MySQL, loads the dummy test data
 * (writable/mailtest/mailtest.sql: the empty tables, the menu definitions, ONE dummy tour with ONE dummy hotel stay)
 * and sets the password YOU choose for the test sign-in "mailtestadmin". It refuses to run twice.
 * Remove: deletes the database avenir_mailtest only (you type REMOVE avenir_mailtest to confirm).
 *
 * The connection settings are this test copy's own (app/Config/Database.php with its TEST COPY guard). The page refuses
 * unless every connection of the copy points at avenir_mailtest. The live databases (avenir, inquiries, avenir_2024)
 * are never named, opened or changed here. No e-mail, no internet.
 */

namespace CodeIgniter\Database {
    if (!class_exists(Config::class, false)) {
        class Config
        {
            public function __construct()
            {
            }
        }
    }
}

namespace {
    const MT_DB = 'avenir_mailtest';
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        exit('This page opens only on the server itself.');
    }
    $root = dirname(__DIR__);
    $dir = $root . '/writable/mailtest';
    $lock = $dir . '/SETUP-DONE.txt';
    $sqlFile = $dir . '/mailtest.sql';
    $nonceFile = $dir . '/nonce.txt';
    $e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

    defined('ENVIRONMENT') || define('ENVIRONMENT', 'production');
    defined('APPPATH') || define('APPPATH', $root . '/app/');
    require_once $root . '/app/Config/Database.php';
    $cfg = new \Config\Database();
    $checks = [];
    $names = [];
    foreach (['default', 'secondary', 'secondDatabase'] as $g) {
        if (isset($cfg->{$g}) && is_array($cfg->{$g})) {
            $names[$g] = (string) ($cfg->{$g}['database'] ?? '');
        }
    }
    $guardOk = $names && count(array_unique($names)) === 1 && reset($names) === MT_DB;
    $checks[] = ['Every database connection of this copy points at ' . MT_DB, $guardOk, implode(', ', array_map(static fn ($k, $v) => "$k → $v", array_keys($names), $names))];
    foreach (['mysqli', 'openssl', 'sodium', 'mbstring', 'intl'] as $x) {
        $checks[] = ['PHP extension ' . $x, extension_loaded($x), ''];
    }
    $checks[] = ['Dummy data file present', is_file($sqlFile), is_file($sqlFile) ? (round(filesize($sqlFile) / 1024) . ' KB, md5 ' . md5_file($sqlFile)) : ''];
    $d = $cfg->default;
    $m = null;
    $exists = false;
    $tables = 0;
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $m = new mysqli((string) $d['hostname'], (string) $d['username'], (string) $d['password'], '', (int) ($d['port'] ?? 3306));
        $m->set_charset('utf8mb4');
        $exists = (bool) $m->query("SHOW DATABASES LIKE '" . MT_DB . "'")->fetch_row();
        if ($exists) {
            $tables = (int) $m->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . MT_DB . "'")->fetch_row()[0];
        }
        $checks[] = ['MySQL on this computer answers', true, 'server ' . $m->server_info];
    } catch (\Throwable $ex) {
        $checks[] = ['MySQL on this computer answers', false, $ex->getMessage()];
    }
    $allOk = !in_array(false, array_column($checks, 1), true);

    $msg = '';
    $done = null;
    $nonceOk = static function () use ($nonceFile): bool {
        $n = is_file($nonceFile) ? trim((string) file_get_contents($nonceFile)) : '';
        return $n !== '' && hash_equals($n, (string) ($_POST['nonce'] ?? ''));
    };
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = (string) ($_POST['act'] ?? '');
        if (!$nonceOk()) {
            $msg = 'The page was out of date. Nothing was done. Reload it and try again.';
        } elseif ($act === 'create') {
            $p1 = (string) ($_POST['p1'] ?? '');
            $p2 = (string) ($_POST['p2'] ?? '');
            if (!$allOk) {
                $msg = 'Not every check is green. Nothing was done.';
            } elseif (is_file($lock) || ($exists && $tables > 0)) {
                $msg = 'The test database is already set up. Nothing was done.';
            } elseif (empty($_POST['ack'])) {
                $msg = 'Tick the box to confirm. Nothing was done.';
            } elseif (strlen($p1) < 10 || $p1 !== $p2) {
                $msg = 'The two passwords must match and be at least 10 characters. Nothing was done.';
            } else {
                try {
                    $m->query('CREATE DATABASE IF NOT EXISTS `' . MT_DB . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                    $m->select_db(MT_DB);
                    if ((string) $m->query('SELECT DATABASE()')->fetch_row()[0] !== MT_DB) {
                        throw new \RuntimeException('not connected to the test database');
                    }
                    $sql = (string) file_get_contents($sqlFile);
                    if (preg_match('/^\s*USE\s|`(avenir|inquiries|avenir_2024)`\./mi', $sql)) {
                        throw new \RuntimeException('the data file names another database; refused');
                    }
                    $m->multi_query($sql);
                    do {
                        if ($r = $m->store_result()) {
                            $r->free();
                        }
                    } while ($m->more_results() && $m->next_result());
                    $st = $m->prepare("UPDATE user_acc_tbl SET pass = ?, access = 1 WHERE user = 'mailtestadmin'");
                    $h = password_hash($p1, PASSWORD_DEFAULT);
                    $st->bind_param('s', $h);
                    $st->execute();
                    $tables = (int) $m->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . MT_DB . "'")->fetch_row()[0];
                    $tour = $m->query("SELECT t.tour_no, t.tour_name, j.hotel_name, j.check_in, j.check_out FROM tf_tour t JOIN tf_hotel_job j ON j.tour_id = t.id WHERE t.tour_no LIKE 'TEST-%' LIMIT 1")->fetch_assoc();
                    $done = ['tables' => $tables, 'tour' => $tour];
                    file_put_contents($lock, 'Set up ' . date('Y-m-d H:i:s') . ' — ' . $tables . ' tables in ' . MT_DB . "\n");
                    $msg = 'Done. The test database ' . MT_DB . ' is ready.';
                    $exists = true;
                } catch (\Throwable $ex) {
                    $msg = 'Stopped: ' . $ex->getMessage() . '. The live databases were not touched. Tell Claude what this says.';
                }
            }
        } elseif ($act === 'remove') {
            if (trim((string) ($_POST['confirm'] ?? '')) !== 'REMOVE ' . MT_DB) {
                $msg = 'Type REMOVE ' . MT_DB . ' exactly to confirm. Nothing was done.';
            } elseif (!$guardOk || !$m) {
                $msg = 'Not every check is green. Nothing was done.';
            } else {
                $m->query('DROP DATABASE IF EXISTS `' . MT_DB . '`');
                @unlink($lock);
                $exists = false;
                $tables = 0;
                $msg = 'The test database ' . MT_DB . ' was removed. Nothing else was changed.';
            }
        }
    }
    $nonce = bin2hex(random_bytes(16));
    @file_put_contents($nonceFile, $nonce);
    $setUp = is_file($lock);
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>TEST COPY setup — avenir_mailtest</title>
<style>
 body{font:15px/1.45 system-ui,Segoe UI,sans-serif;margin:0;background:#faf7fb;color:#241d2b}
 .bar{background:#b42318;color:#fff;font-weight:600;padding:8px 16px}
 main{max-width:760px;margin:0 auto;padding:16px}
 h1{font-size:20px;margin:12px 0}
 table{border-collapse:collapse;width:100%;background:#fff}
 td{border:1px solid #e6dcef;padding:6px 8px;vertical-align:top}
 .ok{color:#0f7b55;font-weight:700}.no{color:#b42318;font-weight:700}
 .box{background:#fff;border:1px solid #e6dcef;border-radius:10px;padding:12px 14px;margin:14px 0}
 .msg{background:#fff4d6;border:1px solid #e9c46a;border-radius:10px;padding:10px 12px;margin:12px 0;font-weight:600}
 input[type=password],input[type=text]{font:inherit;padding:6px 8px;width:100%;max-width:340px;box-sizing:border-box}
 button{font:inherit;padding:7px 14px;border-radius:8px;border:1px solid #842484;background:#842484;color:#fff;cursor:pointer}
 button.rm{background:#fff;color:#b42318;border-color:#b42318}
 small{color:#6b6375}
</style></head><body>
<div class="bar">TEST COPY (avenir_mailtest) — dummy data only — not the live Avenir system</div>
<main>
<h1>Test copy: database setup</h1>
<?php if ($msg !== ''): ?><div class="msg"><?= $e($msg) ?></div><?php endif; ?>
<table>
<?php foreach ($checks as [$label, $ok, $detail]): ?>
 <tr><td><?= $e($label) ?></td><td class="<?= $ok ? 'ok' : 'no' ?>"><?= $ok ? 'OK' : 'NO' ?></td><td><small><?= $e($detail) ?></small></td></tr>
<?php endforeach; ?>
 <tr><td>Test database <?= $e(MT_DB) ?></td><td><?= $exists ? 'exists' : 'not yet' ?></td><td><small><?= $exists ? $e($tables . ' tables') : '' ?></small></td></tr>
</table>
<?php if ($done): ?>
<div class="box"><b>Ready.</b> <?= $e($done['tables']) ?> tables. Dummy booking: <?= $e(($done['tour']['tour_no'] ?? '?') . ' — ' . ($done['tour']['hotel_name'] ?? '?') . ', ' . ($done['tour']['check_in'] ?? '') . ' to ' . ($done['tour']['check_out'] ?? '')) ?>.<br>
Next: <a href="./login">sign in to the test copy</a> as <b>mailtestadmin</b> with the password you just chose.</div>
<?php endif; ?>
<?php if (!$setUp && !($exists && $tables > 0)): ?>
<form method="post" class="box">
 <b>Create the test database</b><br>
 <small>Makes the new database <?= $e(MT_DB) ?> with the dummy test data, and sets the password for the test sign-in <b>mailtestadmin</b>.
 Choose a new password used nowhere else (at least 10 characters). Not the mailbox password.</small>
 <p><label>Password for mailtestadmin<br><input type="password" name="p1" autocomplete="new-password"></label></p>
 <p><label>The same again<br><input type="password" name="p2" autocomplete="new-password"></label></p>
 <p><label><input type="checkbox" name="ack" value="1"> I understand this creates a new, separate database <?= $e(MT_DB) ?> on this computer. The live databases are not touched.</label></p>
 <input type="hidden" name="act" value="create"><input type="hidden" name="nonce" value="<?= $e($nonce) ?>">
 <button type="submit" <?= $allOk ? '' : 'disabled' ?>>Create the test database</button>
</form>
<?php else: ?>
<p>The test database is set up. <a href="./login">Sign in to the test copy</a>.</p>
<?php endif; ?>
<form method="post" class="box">
 <b>After the test: remove the test database</b><br>
 <small>Deletes the database <?= $e(MT_DB) ?> only (the dummy booking, the test e-mails it read, the sealed test mailbox password). Type <b>REMOVE <?= $e(MT_DB) ?></b> to confirm.</small>
 <p><input type="text" name="confirm" autocomplete="off"></p>
 <input type="hidden" name="act" value="remove"><input type="hidden" name="nonce" value="<?= $e($nonce) ?>">
 <button type="submit" class="rm">Remove the test database</button>
</form>
</main></body></html>
<?php
}
