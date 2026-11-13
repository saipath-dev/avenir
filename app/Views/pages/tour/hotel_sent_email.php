<?php
/** tf-resdesk-r2: one e-mail to a hotel exactly as kept — recipient, time, result, subject, attachment and the body as it went. */
$e = static fn ($v) => esc((string) ($v ?? ''));
$ok = in_array((string) $m['state'], ['sent', 'recorded'], true);
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Sent email — <?= $e($m['hotel_name']) ?></title>
<style>
body{margin:0;font-family:Inter,system-ui,Segoe UI,sans-serif;background:#F6F3F7;color:#1F1A24}
.top{background:#7B2382;color:#fff;padding:14px 24px;font-weight:700}
.wrap{max-width:900px;margin:18px auto;padding:0 16px}
.card{background:#fff;border:1px solid #E2D6E6;border-radius:12px;padding:16px 18px;margin-bottom:14px}
.kv{display:grid;grid-template-columns:140px 1fr;gap:6px 14px;font-size:14px}.kv dt{color:#4A1450;font-weight:600}.kv dd{margin:0}
.st{display:inline-block;border-radius:14px;padding:3px 10px;font-weight:700;font-size:13px}
.ok{background:#E4F4EA;color:#1C6B3B}.bad{background:#FBE9E7;color:#8C1D18}
a{color:#6E1F70;font-weight:600}
iframe{width:100%;height:900px;border:1px solid #E2D6E6;border-radius:8px;background:#fff}
</style></head><body>
<div class="top">Avenir Holidays · Sent email · <?= $e($m['tour_no']) ?> · <?= $e($m['hotel_name']) ?></div>
<div class="wrap">
<div class="card"><dl class="kv">
<dt>Result</dt><dd><span class="st <?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? ($m['channel'] === 'email' ? 'Sent' : 'Recorded as sent by other means') : 'FAILED — not sent' ?></span><?= !$ok && $m['error'] ? ' ' . $e($m['error']) : '' ?></dd>
<dt>To</dt><dd><?= $e($m['to_addr'] ?: '—') ?></dd>
<?php if (!empty($m['cc_addr'])): ?><dt>CC</dt><dd><?= $e($m['cc_addr']) ?></dd><?php endif; ?>
<dt><?= $ok ? 'Sent' : 'Tried' ?></dt><dd><?= $e(date('j M Y, H:i', strtotime((string) $m['at']))) ?> by <?= $e($m['by_name'] ?: $m['by']) ?></dd>
<dt>Subject</dt><dd><?= $e($m['subject'] ?: '—') ?></dd>
<dt>Attachments</dt><dd><?php foreach ((array) ($more ?? []) as $x): ?><a href="<?= base_url('tour/hotel_bookings/doc/' . (int) $x['id']) ?>" target="_blank"><?= $e($x['original_name']) ?></a> (version <?= (int) $x['version'] ?>)<br><?php endforeach; ?><?php if ($doc): ?><a href="<?= base_url('tour/hotel_bookings/doc/' . (int) $doc['id']) ?>" target="_blank"><?= $e($doc['original_name']) ?></a> (version <?= (int) $doc['version'] ?>, SHA-256 <?= $e(substr((string) $doc['sha256'], 0, 16)) ?>…)<?php else: ?>—<?php endif; ?></dd>
</dl></div>
<?php if ((string) $m['body'] !== ''): ?>
<div class="card"><iframe sandbox="" srcdoc="<?= esc((string) $m['body'], 'attr') ?>" title="The email body as it went"></iframe></div>
<?php endif; ?>
</div></body></html>
