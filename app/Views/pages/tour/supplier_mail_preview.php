<?php
/**
 * tf-supdesk-r1 — one supplier e-mail on a page: either as it WOULD go ($c, from TourSupplierMail::compose) or exactly as it
 * WENT ($sent, a tf_supplier_send_log row). The recipient, the subject, the attachments to open, and the body. Sends nothing.
 */
$e = static fn ($v) => esc((string) ($v ?? ''));
$isSent = !empty($sent);
$to   = $isSent ? $sent['to_addr'] : ($c['to'] ?? null);
$subj = $isSent ? $sent['subject'] : $c['subject'];
$body = $isSent ? $sent['body'] : $c['html'];
$atts = $isSent ? (array) json_decode((string) ($sent['attachments'] ?? '[]'), true) : $c['attachments'];
$keys = ['request', 'rooming'];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $isSent ? 'Sent e-mail' : 'E-mail preview' ?> — <?= $e($subj) ?></title>
<style>
    body { margin:0; background:#f3f1f2; font-family:'Segoe UI',Arial,Helvetica,sans-serif; color:#1a1a1a; }
    .bar { background:#8b1d40; color:#fff; padding:12px 20px; font-size:15px; font-weight:700; }
    .bar small { font-weight:500; opacity:.9; margin-left:10px; }
    .meta { max-width:860px; margin:16px auto 0; background:#fff; border:1px solid #e7dfe3; border-radius:10px; padding:14px 18px; font-size:14px; }
    .meta div { display:flex; gap:10px; padding:4px 0; } .meta b { width:110px; flex:none; color:#5f6470; font-weight:600; }
    .meta a { color:#8b1d40; font-weight:700; text-decoration:none; margin-right:14px; } .meta a:hover { text-decoration:underline; }
    .warn { color:#a3261d; font-weight:700; } .ok { color:#3f7a17; font-weight:700; }
    .mail { max-width:860px; margin:14px auto 30px; background:#fff; border:1px solid #e7dfe3; border-radius:10px; overflow:hidden; }
</style></head><body>
<div class="bar"><?= $isSent ? 'E-mail as it ' . ($sent['result'] === 'sent' ? 'was sent' : 'was attempted (NOT sent)') : 'E-mail preview — nothing has been sent' ?>
    <small><?= $e($isSent ? $sent['provider'] : $c['provider']) ?></small></div>
<div class="meta">
    <div><b>To</b><span><?= $to ? $e($to) : '<span class="warn">No e-mail address on the master — it cannot be sent</span>' ?></span></div>
    <div><b>Subject</b><span><?= $e($subj) ?></span></div>
    <div><b>Attachments</b><span><?php foreach ($atts as $i => $a): ?><a target="_blank" href="<?= $e($vbase . '/' . ($isSent ? $i : ($keys[$i] ?? 'x'))) ?>">&#128206; <?= $e($a['name'] ?? 'file') ?></a><?php endforeach; ?><?= $atts ? '' : '—' ?></span></div>
    <?php if ($isSent): ?>
    <div><b>Status</b><span class="<?= $sent['result'] === 'sent' ? 'ok' : 'warn' ?>"><?= $sent['result'] === 'sent' ? 'Sent' : 'Failed — ' . $e($sent['error']) ?></span></div>
    <div><b>By / when</b><span><?= $e($sent['by_name']) ?> · <?= $e(substr((string) $sent['at'], 0, 16)) ?><?= $sent['action'] === 'resend' ? ' · deliberate resend' : ($sent['action'] === 'retry' ? ' · retry after a failure' : '') ?></span></div>
    <?php if (!empty($sent['reason'])): ?><div><b>Reason</b><span><?= $e($sent['reason']) ?></span></div><?php endif; ?>
    <?php endif; ?>
</div>
<div class="mail"><?= $body ?></div>
</body></html>
