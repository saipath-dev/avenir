<?php
/**
 * tf-paydesk-r1 — the Export Payment Sheet: the tour's payments day by day as the dashboard shows them, to print or
 * send (TourPaymentDeskController::sheet, ?pdf=1 → TCPDF). Read only. Account numbers only for the sign-ins that may
 * see them ($bank_visible); otherwise masked.
 */
$D = $desk; $T = $D['tour']; $tot = $D['totals'];
$e = static fn ($s): string => esc((string) ($s ?? ''));
$m = static fn ($n, $d = 2): string => number_format((float) ($n ?? 0), $d);
$dt = static function ($s, $f = 'j M Y') { $s = trim((string) ($s ?? '')); if ($s === '' || str_starts_with($s, '0000')) return '—'; $ts = strtotime($s); return $ts ? date($f, $ts) : $s; };
$pdf = !empty($for_pdf);
$INK = '#2c3e50'; $MUTED = '#6c757d'; $P = '#842484'; $SOFT = '#f3e7f3'; $LINE = '#ced4da';
$lines = []; foreach ($D['lines'] as $l) { $lines[$l['key']] = $l; }
$colors = ['grey' => '#6c757d', 'blue' => '#2563eb', 'indigo' => '#4f46e5', 'violet' => '#7c3aed', 'teal' => '#0f766e', 'green' => '#1f8a4c', 'olive' => '#4d7c0f', 'purple' => '#6d28d9', 'yellow' => '#1a1a1a', 'red' => '#b23a32', 'amber' => '#1a1a1a'];
$th = 'style="text-align:left; font-size:8.5px; letter-spacing:.04em; text-transform:uppercase; color:' . $MUTED . '; padding:4px 5px; border-bottom:1px solid ' . $LINE . ';"';
$td = 'style="font-size:9.5px; padding:4px 5px; border-bottom:1px solid #e9ecef; vertical-align:top; color:' . $INK . ';"';
$tdr = $td . ' align="right"';
$font = $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif';
?>
<?php if (!$pdf): ?>
<!doctype html><html><head><meta charset="utf-8"><title>Payment sheet — <?= $e($T['manual_id']) ?></title><meta name="viewport" content="width=device-width, initial-scale=1">
<style> body { font-family: Arial, Helvetica, sans-serif; color:<?= $INK ?>; margin:16px; max-width:1100px; } .toolbar { display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; align-items:center; } .toolbar a, .toolbar button { font:inherit; font-size:13px; padding:7px 12px; border:1px solid <?= $LINE ?>; border-radius:8px; background:#fff; color:<?= $INK ?>; text-decoration:none; cursor:pointer; } .toolbar a.main { background:<?= $P ?>; border-color:<?= $P ?>; color:#fff; font-weight:bold; } @media print { .toolbar { display:none; } body { margin:0; } } table { border-collapse:collapse; }</style></head><body>
<div class="toolbar"><a class="main" href="?manual_id=<?= rawurlencode($T['manual_id']) ?>&pdf=1" target="_blank">⬇ PDF</a><button onclick="window.print()">Print</button><a href="<?= base_url('tour/tour_payment_dashboard?manual_id=' . rawurlencode($T['manual_id'])) ?>">← Dashboard</a>
<span style="font-size:12px; color:<?= $MUTED ?>;">Printed <?= date('j M Y H:i') ?> by <?= $e($user) ?></span><?php if (!empty($no_pdf_lib)): ?><span style="font-size:12px;color:#1a1a1a;">The PDF library is not installed on this server yet — use Print → Save as PDF.</span><?php endif; ?></div>
<?php endif; ?>
<table cellpadding="0" cellspacing="0" width="100%" style="font-family:<?= $font ?>; color:<?= $INK ?>;">
<tr><td width="60%" style="padding:10px 12px; background:<?= $P ?>; color:#fff;">
    <div style="font-size:15px; font-weight:bold;">Payment sheet · <?= $e($T['name']) ?></div>
    <div style="font-size:10.5px;">Tour <?= $e($T['manual_id']) ?> · <?= $dt($T['start_date']) ?> – <?= $dt($T['end_date']) ?> · <?= (int) $T['pax'] ?> pax · <?= $e($T['agency']) ?><?= $T['drivers'] ? ' · driver ' . $e(implode(', ', $T['drivers'])) : '' ?><?= $T['guide'] ? ' · guide ' . $e($T['guide']) : '' ?></div>
</td><td width="40%" style="padding:10px 12px; background:<?= $SOFT ?>; text-align:right;">
    <div style="font-weight:bold;"><?= $e($company['name']) ?></div>
    <div style="font-size:9px; color:<?= $MUTED ?>;">Prepared <?= date('j M Y H:i') ?> by <?= $e($user) ?> · USD rate today: <?= $D['rate']['rate'] ? 'LKR ' . $m($D['rate']['rate']) . ' (' . $e($D['rate']['entered_by']) . ')' : 'not entered' ?></div>
</td></tr></table>

<table cellpadding="0" cellspacing="0" width="100%" style="margin-top:8px; font-family:<?= $font ?>;">
<tr>
<?php foreach ([['Estimated tour cost', 'LKR ' . $m($tot['estimated'], 0), count($D['lines']) . ' items'], ['Total paid', 'LKR ' . $m($tot['paid'], 0), $tot['pct'] . '% of estimated cost'], ['Outstanding', 'LKR ' . $m($tot['outstanding'], 0), (100 - $tot['pct']) . '% remaining'], ['Due this week / overdue', 'LKR ' . $m($tot['due_week'] + $tot['overdue'], 0), 'overdue LKR ' . $m($tot['overdue'], 0)]] as $k): ?>
<td width="25%" style="padding:6px 8px; border:1px solid <?= $LINE ?>;"><div style="font-size:9px; color:<?= $MUTED ?>;"><?= $k[0] ?></div><div style="font-size:12.5px; font-weight:bold;"><?= $k[1] ?></div><div style="font-size:8.5px; color:<?= $MUTED ?>;"><?= $k[2] ?></div></td>
<?php endforeach; ?>
</tr></table>

<?php foreach ($D['days'] as $day): if (!$day['lines']) continue; ?>
<div style="margin-top:10px; padding:5px 8px; background:<?= $SOFT ?>; font-family:<?= $font ?>; font-size:11px; font-weight:bold; color:<?= $P ?>;">
    <?= $day['day_no'] ? 'Day ' . (int) $day['day_no'] . ' · ' . $dt($day['date'], 'D j M Y') : $e($day['title']) ?><?= $day['title'] && $day['day_no'] ? ' · ' . $e($day['title']) : '' ?><?= $day['hotel'] ? ' · Night: ' . $e($day['hotel']) : '' ?>
    <span style="float:right; font-weight:normal; color:<?= $INK ?>;">LKR <?= $m($day['total'], 0) ?> · <?= (int) $day['paid_count'] ?> paid · <?= (int) $day['pending'] ?> pending</span>
</div>
<table cellpadding="0" cellspacing="0" width="100%" style="font-family:<?= $font ?>; border:1px solid <?= $LINE ?>;">
    <tr><th <?= $th ?> width="22%">Supplier · service</th><th <?= $th ?> width="10%">Invoice</th><th <?= $th ?> width="10%" align="right">Original</th><th <?= $th ?> width="7%" align="right">Rate</th><th <?= $th ?> width="9%" align="right">LKR</th><th <?= $th ?> width="9%" align="right">Paid</th><th <?= $th ?> width="9%" align="right">Balance</th><th <?= $th ?> width="9%">Requested / due</th><th <?= $th ?> width="15%">Status</th></tr>
    <?php foreach ($day['lines'] as $key): $l = $lines[$key] ?? null; if (!$l) continue; $usd = $l['currency'] === 'USD'; ?>
    <tr>
        <td <?= $td ?>><b><?= $e($l['supplier']) ?></b><br><span style="color:<?= $MUTED ?>;"><?= $e($l['category']) ?><?= $l['nights'] ? ' · ' . (int) $l['nights'] . ' night(s)' : '' ?><?= $l['description'] && $l['description'] !== $l['supplier'] && !ctype_digit(trim((string) $l['description'])) ? ' · ' . $e($l['description']) : '' ?></span>
            <?php foreach ($l['warnings'] as $w): ?><br><span style="color:<?= $w['level'] === 'danger' ? '#b23a32' : ($w['level'] === 'warning' ? '#1a1a1a' : '#2563eb') ?>; font-size:8.5px;">▲ <?= $e($w['text']) ?></span><?php endforeach; ?>
            <?php if ($usd && $l['rate_label']): ?><br><span style="color:<?= $MUTED ?>; font-size:8.5px;"><?= $e($l['rate_label']) ?></span><?php endif; ?></td>
        <td <?= $td ?>><?= $e($l['invoice_no'] ?: '—') ?></td>
        <td <?= $tdr ?>><?= $usd ? 'USD ' . $m($l['usd_amount']) : 'LKR ' . $m($l['amount']) ?></td>
        <td <?= $tdr ?>><?= $usd ? ($l['usd_rate'] ? $m($l['usd_rate']) . ($l['rate_locked'] ? ' 🔒' : '*') : '—') : '—' ?></td>
        <td <?= $tdr ?>><?= $l['lkr_amount'] > 0 ? $m($l['lkr_amount']) . ($l['lkr_is_indicative'] ? '*' : '') : '—' ?></td>
        <td <?= $tdr ?>><?= $m($l['paid_lkr']) ?></td>
        <td <?= $tdr ?>><?= $m($l['balance_lkr']) ?></td>
        <td <?= $td ?>><?= $dt($l['request_date']) ?><br><?= $dt($l['due_date']) ?><?= $l['urgency'] ? '<br><b style="color:' . ($l['urgency'] === 'overdue' ? '#b23a32' : '#1a1a1a') . ';">' . $e($l['urgency_label']) . '</b>' : '' ?></td>
        <td <?= $td ?>><b style="color:<?= $colors[$l['color']] ?? $INK ?>;"><?= $e($l['status_label']) ?></b><?= $l['notify_label'] ? '<br><span style="font-size:8.5px;">' . $e($l['notify_label']) . '</span>' : '' ?><?= $l['partly'] ? '<br><span style="font-size:8.5px; color:#6d28d9;">Partly paid</span>' : '' ?>
            <?php foreach ($l['transactions'] as $tx): if (!$tx['vnum']) continue; ?><br><span style="font-size:8.5px; color:<?= $MUTED ?>;">Payment Voucher No. <?= (int) $tx['vnum'] ?><?= !empty($tx['batch']) ? ' · batch ' . esc($tx['batch']['no']) : '' ?> · LKR <?= $m($tx['voucher_amount']) ?><?= $tx['usd_rate'] ? ' @ ' . $m($tx['usd_rate']) : '' ?><?= !empty($tx['approval_rate']) && !empty($tx['paid_rate']) && abs($tx['paid_rate'] - $tx['approval_rate']) > 0.00001 ? ' (approval ' . $m($tx['approval_rate']) . ' → paid ' . $m($tx['paid_rate']) . ', diff ' . ($tx['fx_diff'] > 0 ? '+' : '') . $m($tx['fx_diff']) . ')' : '' ?><?= $tx['finalized'] ? ' · paid ' . $dt($tx['paid_date']) : '' ?></span><?php endforeach; ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endforeach; ?>

<table cellpadding="0" cellspacing="0" width="100%" style="margin-top:10px; font-family:<?= $font ?>; background:<?= $SOFT ?>; border:1px solid #e2c6e2;">
<tr><td style="padding:8px 10px;"><div style="font-size:12px; font-weight:bold; color:<?= $P ?>;">Total estimated cost</div><div style="font-size:9px; color:<?= $MUTED ?>;">* indicative at today's rate — the part not yet requested. 🔒 locked when the payment was requested. Paid figures are the finalised vouchers.</div></td>
<td style="padding:8px 10px; text-align:right;"><div style="font-size:16px; font-weight:bold; color:<?= $P ?>;">LKR <?= $m($tot['estimated'], 0) ?></div><div style="font-size:10px;">paid LKR <?= $m($tot['paid'], 0) ?> · outstanding LKR <?= $m($tot['outstanding'], 0) ?></div></td></tr>
</table>
<div style="margin-top:8px; font-size:9px; color:<?= $MUTED ?>; font-family:<?= $font ?>;">Tour <?= $e($T['manual_id']) ?> · payment sheet prepared <?= date('j M Y H:i') ?> · <?= $e($company['name']) ?> · Tel <?= $e($company['phone']) ?>. Internal document — supplier bank details are on the vouchers, not on this sheet.</div>
<?php if (!$pdf): ?></body></html><?php endif; ?>
