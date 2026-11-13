<?php /** tf-resdesk-r2: the local test mailbox — every e-mail the system would have sent, caught on this computer. */ $e = static fn ($v) => esc((string) ($v ?? '')); ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Test mailbox</title>
<style>
body{margin:0;font-family:Inter,system-ui,Segoe UI,sans-serif;background:#F6F3F7;color:#1F1A24}
.top{background:#7B2382;color:#fff;padding:14px 24px;font-weight:700}.top small{font-weight:400;opacity:.9;margin-left:10px}
.wrap{max-width:1100px;margin:18px auto;padding:0 16px}
.note{background:#EAF6E2;border:1px solid #A7D08E;color:#1E4A0E;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:14px}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;font-size:13.5px}
th{background:#F1E9F3;color:#4A1450;text-align:left;padding:9px 10px;font-size:12px;letter-spacing:.03em}
td{padding:9px 10px;border-top:1px solid #EEE7F1;vertical-align:top}
a{color:#6E1F70;font-weight:600}
</style></head><body>
<div class="top">Avenir Holidays · Test mailbox<small>localhost only — nothing here was sent to anyone</small></div>
<div class="wrap">
<div class="note">Every e-mail the system tried to send on this computer is kept here instead of going to a mail server. Rehearsed failures (refused addresses): <b><?= $fail ? $e(implode(', ', $fail)) : 'none set' ?></b> — set in .env <code>tourflow.testMailboxFail</code>.</div>
<table><thead><tr><th>When</th><th>To (would have gone to)</th><th>CC</th><th>Subject</th><th>Attachments</th><th>By</th></tr></thead><tbody>
<?php if (!$messages): ?><tr><td colspan="6">No message yet.</td></tr><?php endif; ?>
<?php foreach ($messages as $m): ?>
<tr><td style="white-space:nowrap"><?= $e($m['at']) ?></td><td><?= $e($m['to']) ?></td><td><?= $e(implode(', ', (array) $m['cc'])) ?></td>
<td><a href="<?= base_url('tour/hotel_bookings/test_mailbox/' . $m['id'] . '/body.html') ?>" target="_blank"><?= $e($m['subject']) ?></a></td>
<td><?php foreach ((array) $m['attachments'] as $a): ?><a href="<?= base_url('tour/hotel_bookings/test_mailbox/' . $m['id'] . '/' . $a['file']) ?>" target="_blank"><?= $e($a['name']) ?></a> (<?= number_format(((int) $a['size']) / 1024, 1) ?> KB)<br><?php endforeach; ?><?= empty($m['attachments']) ? '—' : '' ?></td>
<td><?= $e($m['from']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></body></html>
