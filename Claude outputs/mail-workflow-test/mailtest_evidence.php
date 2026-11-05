<?php
/**
 * TEST COPY ONLY (avenir_mailtest) — the test record, read-only.
 *
 *   http://localhost/avenir_mailtest/mailtest_evidence.php      (opens only on the server itself)
 *
 * Reads the dummy booking, the letters sent, the e-mails received, how each was matched, the staff notices and the
 * mailbox's state (never its password) from the test database, shows them, and saves the same as
 * writable/mailtest/evidence-<date-time>.json so Claude can check them. It changes nothing in any database.
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
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        exit('This page opens only on the server itself.');
    }
    $root = dirname(__DIR__);
    defined('ENVIRONMENT') || define('ENVIRONMENT', 'production');
    defined('APPPATH') || define('APPPATH', $root . '/app/');
    require_once $root . '/app/Config/Database.php';
    $cfg = new \Config\Database();
    $d = $cfg->default;
    $e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    if ((string) ($d['database'] ?? '') !== MT_DB) {
        exit('Refused: this copy is not pointed at ' . MT_DB . '.');
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $m = new mysqli((string) $d['hostname'], (string) $d['username'], (string) $d['password'], MT_DB, (int) ($d['port'] ?? 3306));
    $m->set_charset('utf8mb4');
    $m->query('SET SESSION TRANSACTION READ ONLY');
    $rows = static function (string $sql) use ($m): array {
        try {
            return $m->query($sql)->fetch_all(MYSQLI_ASSOC);
        } catch (\Throwable $ex) {
            return [['error' => $ex->getMessage()]];
        }
    };
    $ev = [
        'taken_at' => date('Y-m-d H:i:s'),
        'database' => (string) $m->query('SELECT DATABASE()')->fetch_row()[0],
        'stays' => $rows("SELECT j.id, t.tour_no, j.hotel_name, j.check_in, j.check_out, j.nights, j.rooms, j.meal_plan, j.status, j.confirmation_ref, j.confirmed_with, j.confirmed_at, j.confirmed_by, j.requested_at, j.request_ref, j.rcpt_to, j.rcpt_cc FROM tf_hotel_job j JOIN tf_tour t ON t.id = j.tour_id ORDER BY j.id"),
        'letters_sent' => $rows("SELECT id, job_id, kind, trigger_by, status, via, from_addr, to_addr, cc_addr, subject, request_ref, rfc_message_id, attempts, last_error, created_by, sent_at FROM tf_hotel_send ORDER BY id"),
        'emails_received' => $rows("SELECT id, account_role, direction, from_addr, to_addr, subject, message_id, in_reply_to, sent_date, received_at, kind, state, tour_id, job_id, send_id, match_method, match_confidence, extracted_json, LEFT(body_text, 1500) AS body_text FROM tf_mail_message ORDER BY id"),
        'review_queue' => $rows("SELECT * FROM tf_mail_review ORDER BY id"),
        'notices' => $rows("SELECT id, kind, tour_id, job_id, mail_id, how, title, detail, created_at, created_by FROM tf_mail_notice ORDER BY id"),
        'mailbox' => $rows("SELECT role, address, provider, status, last_error, last_sync_at, (secret_enc IS NOT NULL) AS password_kept_sealed, cursor_state, server_json, updated_at, updated_by FROM tf_mail_account ORDER BY id"),
        'settings' => $rows("SELECT k, v FROM tf_meta WHERE k IN ('hotel_auto_send', 'mail_send_via', 'schema_version')"),
        'activity' => $rows("SELECT id, entity, entity_id, tour_id, action, summary, by_user, at FROM tf_activity ORDER BY id DESC LIMIT 60"),
    ];
    $dir = $root . '/writable/mailtest';
    $file = $dir . '/evidence-' . date('Ymd-His') . '.json';
    @file_put_contents($file, json_encode($ev, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>TEST COPY record — avenir_mailtest</title>
<style>body{font:14px/1.45 system-ui,Segoe UI,sans-serif;margin:0;background:#faf7fb;color:#241d2b}.bar{background:#b42318;color:#fff;font-weight:600;padding:8px 16px}
main{max-width:1100px;margin:0 auto;padding:16px}h2{font-size:16px;margin:18px 0 6px}table{border-collapse:collapse;width:100%;background:#fff;font-size:12.5px}
td,th{border:1px solid #e6dcef;padding:4px 6px;vertical-align:top;text-align:left}th{background:#f3edf7}.ok{color:#0f7b55;font-weight:700}</style></head><body>
<div class="bar">TEST COPY (avenir_mailtest) — dummy data only — not the live Avenir system</div><main>
<p class="ok">Saved for Claude as <?= $e(basename($file)) ?> (read-only: nothing was changed).</p>
<?php foreach (['stays', 'letters_sent', 'emails_received', 'review_queue', 'notices', 'mailbox', 'settings'] as $k): $t = $ev[$k]; ?>
<h2><?= $e(str_replace('_', ' ', ucfirst($k))) ?> (<?= count($t) ?>)</h2>
<?php if ($t): ?><table><tr><?php foreach (array_keys($t[0]) as $c): if (in_array($c, ['body_text', 'extracted_json', 'cursor_state', 'server_json'], true)) continue; ?><th><?= $e($c) ?></th><?php endforeach; ?></tr>
<?php foreach ($t as $r): ?><tr><?php foreach ($r as $c => $v): if (in_array($c, ['body_text', 'extracted_json', 'cursor_state', 'server_json'], true)) continue; ?><td><?= $e($v) ?></td><?php endforeach; ?></tr><?php endforeach; ?></table>
<?php else: ?><p>none</p><?php endif; endforeach; ?>
</main></body></html>
<?php
}
