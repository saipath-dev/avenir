<?php
/**
 * tf-pilot-r1 — the department panel at the top of the common starting page (Pilot): the department, the tasks waiting,
 * the shortcuts and the reports the signed-in user may open. Rendered from App\Libraries\Pilot::forUser(); every item
 * here is one the user's privileges already allow — the panel grants nothing. $pilot = that array.
 */
$p = $pilot ?? null;
if (!$p) {
    return;
}
$e = static fn ($s): string => esc((string) ($s ?? ''));
$tone = ['action' => ['#842484', '#f3e7f3'], 'pending' => ['#b25e00', '#fff3d6'], 'blocked' => ['#c5281c', '#fde8e8'], 'info' => ['#172443', '#e9ecf5'], 'done' => ['#0d8a6e', '#e4f4ea']];
$open = array_filter($p['tasks'], static fn ($t) => $t['count'] > 0);
?>
<style>
    .pilot { border: 1px solid #e4e8ee; border-radius: 16px; background: #fff; padding: 16px 18px; margin-top: 12px; box-shadow: 0 2px 10px rgba(23,36,67,.06); }
    .pilot h5 { margin: 0; font-weight: 700; color: #172443; font-size: 1.05rem; }
    .pilot .dept { display: inline-block; border-radius: 999px; padding: 3px 11px; font-size: .78rem; font-weight: 700; background: #172443; color: #fff; margin-left: 8px; vertical-align: middle; }
    .pilot .muted { color: #6c757d; font-size: .82rem; }
    .pilot .pl-tasks { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 10px; margin-top: 12px; align-items: start; }
    .pilot .pl-task { display: flex !important; align-items: center; gap: 12px; border: 1px solid #e4e8ee; border-radius: 12px; padding: 10px 12px !important; height: auto !important; min-height: 0 !important; text-decoration: none; color: #172443; background: #fff; transition: box-shadow .15s; }
    .pilot .pl-task:hover { box-shadow: 0 4px 14px rgba(23,36,67,.12); color: #172443; }
    .pilot .pl-task .n { min-width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.15rem; font-variant-numeric: tabular-nums; }
    .pilot .pl-task .l { font-size: .84rem; line-height: 1.25; }
    .pilot .sc { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
    .pilot .sc a { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #d9d2e3; border-radius: 999px; padding: 6px 12px; font-size: .82rem; font-weight: 600; color: #842484; text-decoration: none; background: #fff; }
    .pilot .sc a:hover { background: #f3e7f3; }
    .pilot .rep { margin-top: 12px; font-size: .82rem; }
    .pilot .rep a { color: #172443; text-decoration: none; border-bottom: 1px dotted #9aa3b5; margin-right: 12px; white-space: nowrap; line-height: 1.9; }
    .pilot .rep a:hover { color: #842484; border-bottom-color: #842484; }
    .pilot .hdr { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; }
    @media (max-width: 640px) { .pilot { padding: 12px; } .pilot .pl-tasks { grid-template-columns: 1fr; } }
</style>
<div class="pilot animate-fade-in" id="pilot_panel">
    <div class="hdr">
        <div><h5><i class="bi bi-compass me-1" style="color:#842484;"></i>Pilot <span class="dept"><?= $e($p['department']) ?></span></h5>
            <div class="muted mt-1">Signed in as <b><?= $e($p['user']) ?></b> · <?= (int) $p['menus'] ?> permitted pages in <?= (int) $p['categories'] ?> areas (the tiles below). Only what your profile allows is shown here; every action is recorded under your name.</div></div>
        <div class="muted"><?= date('l, j F Y') ?></div>
    </div>
    <?php if ($p['tasks']): ?>
    <div class="pl-tasks">
        <?php foreach ($p['tasks'] as $t): [$fg, $bg] = $tone[$t['tone']] ?? $tone['info']; ?>
        <a class="pl-task" href="<?= base_url($t['url']) ?>"><div class="n" style="color:<?= $fg ?>; background:<?= $bg ?>;"><?= (int) $t['count'] ?></div><div class="l"><?= $e($t['label']) ?></div></a>
        <?php endforeach; ?>
    </div>
    <?php if (!$open): ?><div class="muted mt-2"><i class="bi bi-check2-circle me-1" style="color:#0d8a6e;"></i>Nothing is waiting for you right now.</div><?php endif; ?>
    <?php endif; ?>
    <?php if ($p['shortcuts']): ?>
    <div class="sc">
        <?php foreach ($p['shortcuts'] as $s): ?><a href="<?= base_url($s['url']) ?>"><i class="bi <?= $e($s['icon']) ?>"></i><?= $e($s['label']) ?></a><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($p['reports']): ?>
    <div class="rep"><span class="muted"><i class="bi bi-file-earmark-bar-graph me-1"></i>Your reports (<?= count($p['reports']) ?>):</span>
        <?php foreach ($p['reports'] as $r): ?><a href="<?= base_url($r['url']) ?>"><?= $e($r['label']) ?></a><?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
