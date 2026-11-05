<?php /** tf-help-r1 — System Administrator: does every User Manual topic still match the system? @var array $r */ ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>User Manual check — Avenir Holidays</title>
<style>body{margin:0;background:#f6f3f8;font:14px/1.45 system-ui,"Segoe UI",Roboto,Arial,sans-serif;color:#241d2b}
main{max-width:980px;margin:0 auto;padding:18px}h1{font-size:20px;color:#4b2a5c}table{width:100%;border-collapse:collapse;background:#fff}
td,th{border:1px solid #e6dcef;padding:6px 8px;text-align:left;vertical-align:top}th{background:#f3edf7}
.ok{color:#0f7b55;font-weight:700}.bad{color:#b42318;font-weight:700}.p{color:#7a4b00;font-size:13px}a{color:#4b2a5c}</style></head>
<body><main>
<p><a href="<?= esc(base_url('help'), 'attr') ?>">← User Manual</a></p>
<h1>User Manual — checked against this system</h1>
<p><b class="<?= $r['ok'] === $r['total'] && !$r['guide_problems'] ? 'ok' : 'bad' ?>"><?= (int) $r['ok'] ?> of <?= (int) $r['total'] ?> topics match the system</b> · checked <?= esc($r['checked_at']) ?> · <?= esc($r['version']) ?></p>
<p>A topic marked <b class="bad">CHECK</b> quotes a button, message or screen that is no longer in the system: update that topic in <code>app/Help/sections/</code>; the help and the printable manual both follow.</p>
<?php if ($r['guide_problems']): ?><p class="bad"><?= esc(implode(' · ', $r['guide_problems'])) ?></p><?php endif; ?>
<table><tr><th>Section</th><th>Topic</th><th>Result</th></tr>
<?php foreach ($r['topics'] as $t): ?>
<tr><td><?= esc($t['section']) ?></td><td><?= esc($t['title']) ?></td><td><?php if ($t['ok']): ?><span class="ok">ok</span><?php else: ?><span class="bad">CHECK</span><?php foreach ($t['problems'] as $p): ?><div class="p"><?= esc($p) ?></div><?php endforeach; endif; ?></td></tr>
<?php endforeach; ?>
</table>
<p><a href="<?= esc(base_url('help/manual'), 'attr') ?>">View the printable manual (PDF)</a></p>
</main></body></html>
