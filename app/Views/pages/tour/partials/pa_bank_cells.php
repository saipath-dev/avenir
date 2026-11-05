<?php
/**
 * tf-bankreg-r1 (22 Oct 2026) — Payment Approval: the payee's registered bank account beside the name (the four cells of the
 * group row): account holder · bank · branch · account number. $pa is SupplierAccounts::present() — the number already masked
 * for a sign-in that may not see it. Nothing is editable here; the registry page is linked for those who may edit it.
 */
$pa = $pa ?? null;
$e = static fn ($v) => esc((string) ($v ?? ''));
if (!$pa || empty($pa['registered'])): ?>
    <td colspan="4" class="small"><span class="pau-st" style="margin-left:0;background:#fde8e8;color:#9b1c1c;">No registered bank account</span>
        <?php if (!empty($can_edit)): ?> <a class="small" href="<?= $e($registry_url) ?>?q=<?= urlencode((string) ($pa['sup_acc'] ?? '')) ?>" target="_blank">Register</a><?php endif; ?></td>
<?php else:
    $st = $pa['state'] ?? 'unverified';
    $pill = ['verified' => ['#e4f4ea', '#1f8a4c', 'Verified'], 'unverified' => ['#fdf3e1', '#b7791f', 'Not verified'], 'changed' => ['#fbe9e7', '#b23a32', 'Changed since verified'], 'inactive' => ['#eef0f3', '#6c757d', 'Inactive'], 'no_default' => ['#fbe9e7', '#b23a32', 'No default account']][$st] ?? ['#eef0f3', '#6c757d', ucfirst($st)];
    $incomplete = empty($pa['complete']); ?>
    <td class="small"><strong><?= $e($pa['holder_name'] ?: '—') ?></strong>
        <br><span class="pau-st" style="margin-left:0;background:<?= $pill[0] ?>;color:<?= $pill[1] ?>;"><?= $e($pill[2]) ?></span>
        <?php if ($incomplete): ?><span class="pau-st" style="margin-left:4px;background:#fde8e8;color:#9b1c1c;" title="<?= $e(implode(', ', $pa['missing'] ?? [])) ?> missing">Incomplete</span><?php endif; ?>
        <?php if (($pa['accounts'] ?? 1) > 1): ?><span class="text-muted" style="font-size:.72rem;"> · <?= (int) $pa['accounts'] ?> accounts, default shown</span><?php endif; ?></td>
    <td class="small"><?= $e($pa['bank_name'] ?: '—') ?><?= $pa['swift'] ? '<br><span class="text-muted">' . $e($pa['swift']) . '</span>' : '' ?></td>
    <td class="small"><?= $e($pa['branch'] ?: '—') ?></td>
    <td class="small" style="font-family:ui-monospace,Menlo,Consolas,monospace;"><?= $e($pa['account_no'] ?: '—') ?><br><span class="text-muted" style="font-family:inherit;"><?= $e($pa['currency'] ?: 'LKR') ?> account</span></td>
<?php endif; ?>
