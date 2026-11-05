<?php
/**
 * tf-paydesk-r5 — the itemised remittance advice of a BULK supplier payment: one bank transfer, every voucher and item listed.
 *   $d  Bulk::detail()   $company   $for_pdf
 */
$b = $d['batch']; $sup = $d['supplier']; $acct = $d['bank_account']; $ch = $d['children']; $tot = $d['totals'];
$e = static fn ($s): string => esc((string) ($s ?? ''));
$m = static fn ($n): string => number_format((float) ($n ?? 0), 2);
$dt = static function ($s, $f = 'j M Y') { $s = trim((string) ($s ?? '')); if ($s === '' || str_starts_with($s, '0000')) return '—'; $ts = strtotime($s); return $ts ? date($f, $ts) : $s; };
$pdf = !empty($for_pdf);
$INK = '#2c3e50'; $MUTED = '#6c757d'; $P = '#842484'; $SOFT = '#f3e7f3'; $LINE = '#ced4da';
$font = $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif';
$ext = $acct ? \App\Libraries\TourPayments\BankAccounts::external($acct) : null;
$docs = new \App\Libraries\TourPayments\Documents(\Config\Database::connect());
$kv = static function ($k, $val) use ($MUTED, $INK) { return '<tr><td style="font-size:10.5px; color:' . $MUTED . '; padding:4px 6px; width:40%;">' . $k . '</td><td style="font-size:11px; color:' . $INK . '; padding:4px 6px;">' . ($val === '' || $val === null ? '—' : $val) . '</td></tr>'; };
$first = $ch[0]['vnum'] ?? null; $last = $ch ? end($ch)['vnum'] : null;
$paidLkr = $tot['paid_lkr'] > 0 ? $tot['paid_lkr'] : ($tot['transfer_lkr'] ?? $tot['voucher_total']);
?>
<?php if (!$pdf): ?>
<!doctype html><html><head><meta charset="utf-8"><title>Remittance advice — bulk batch <?= $e($b['batch_no']) ?> — <?= $e($sup['supplier_name'] ?? $b['supplier_name']) ?></title><meta name="viewport" content="width=device-width, initial-scale=1">
<style> body { font-family: Arial, Helvetica, sans-serif; color:<?= $INK ?>; margin:16px; max-width:900px; } .toolbar { display:flex; gap:8px; margin-bottom:12px; } .toolbar a, .toolbar button { font:inherit; font-size:13px; padding:7px 12px; border:1px solid <?= $LINE ?>; border-radius:8px; background:#fff; color:<?= $INK ?>; text-decoration:none; cursor:pointer; } .toolbar a.main { background:<?= $P ?>; border-color:<?= $P ?>; color:#fff; font-weight:bold; } @media print { .toolbar { display:none; } }</style></head><body>
<div class="toolbar"><a class="main" href="<?= base_url('tour/tour_payment_dashboard/bulk_remittance/' . (int) $b['id']) ?>?pdf=1" target="_blank">⬇ PDF</a><button onclick="window.print()">Print</button><a href="<?= base_url('tour/tour_payment_dashboard/bulk/' . (int) $b['id']) ?>">← Batch</a>
<?php if ($d['remittance']): ?><span style="font-size:12px; color:<?= $MUTED ?>; align-self:center;">Status: <b><?= $e($d['remittance']['status']) ?></b><?= $d['remittance']['sent_at'] ? ' · sent ' . $dt($d['remittance']['sent_at'], 'j M Y H:i') . ' to ' . $e($d['remittance']['to_email']) : '' ?></span><?php endif; ?></div>
<?php endif; ?>
<table cellpadding="0" cellspacing="0" width="100%" style="font-family:<?= $font ?>; color:<?= $INK ?>; font-size:11px;">
<tr><td width="58%" style="padding:12px; background:<?= $P ?>; color:#fff;">
    <div style="font-size:17px; font-weight:bold;">Remittance advice — consolidated payment</div>
    <div style="font-size:11px;">Bulk batch <?= $e($b['batch_no']) ?> · <?= count($ch) ?> payment vouchers (No. <?= (int) $first ?>–<?= (int) $last ?>) · one bank transfer<?= $b['payment_date'] ? ' · ' . $dt($b['payment_date']) : '' ?></div>
</td><td width="42%" style="padding:12px; background:<?= $SOFT ?>; text-align:right;">
    <div style="font-weight:bold; font-size:12px;"><?= $e($company['name']) ?></div>
    <div style="font-size:9.5px; color:<?= $MUTED ?>;"><?= $e($company['address']) ?><br>Tel <?= $e($company['phone']) ?><?= $company['email'] ? '<br>' . $e($company['email']) : '' ?></div>
</td></tr></table>

<table cellpadding="0" cellspacing="0" width="100%" style="margin-top:12px; font-family:<?= $font ?>;">
<tr valign="top"><td width="50%" style="padding-right:8px;">
    <div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin-bottom:3px;">To</div>
    <div style="font-size:13px; font-weight:bold;"><?= $e($sup['supplier_name'] ?? $b['supplier_name']) ?></div>
    <div style="font-size:10.5px; color:<?= $MUTED ?>;"><?= $e($sup['remittance_email'] ?? '') ?></div>
    <p style="font-size:11px; margin:10px 0 0;">Dear <?= $e($sup['supplier_name'] ?? $b['supplier_name']) ?>,<br><br>We confirm that the payments listed below have been settled by <b>one bank transfer</b>. Each item carries its own Payment Voucher number for your reconciliation. Please check your bank account.</p>
</td><td width="50%" style="padding-left:8px;">
    <div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin-bottom:3px;">The transfer</div>
    <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>;">
        <?= $kv('Amount transferred', '<b style="font-size:14px;">LKR ' . $m($paidLkr) . '</b>') ?>
        <?php if ($d['is_usd']): ?><?= $kv('Equivalent', 'USD ' . $m($tot['transfer_total']) . ($b['usd_rate'] ? ' at 1 USD = LKR ' . number_format((float) $b['usd_rate'], 2) . ' <span style="color:' . $MUTED . '; font-size:9px;">rate of the payment date</span>' : '')) ?><?php endif; ?>
        <?= $kv('Payment date', $dt($b['payment_date'])) ?>
        <?= $kv('Bank transaction reference', $e($b['bank_ref'])) ?>
        <?= $kv('Our reference', '<b>Bulk batch ' . $e($b['batch_no']) . '</b> · vouchers ' . (int) $first . '–' . (int) $last) ?>
        <?= $kv('From', $acct ? $e($ext['bank'] ?? $acct['bank_name']) . (!empty($ext['shown']) && ($ext['account'] ?? '') !== '' ? ' · ' . $e($ext['account']) : '') : '—') ?>
        <?= $kv('To your account', ($sup['bank_name'] ?? '') !== '' ? $e($sup['bank_name']) . (($sup['account_no'] ?? '') !== '' ? ' · ending ' . $e(substr((string) $sup['account_no'], -4)) : '') : '—') ?>
    </table>
</td></tr></table>

<div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin:12px 0 3px;">Items settled by this transfer (<?= count($ch) ?>)</div>
<table cellpadding="4" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>; font-family:<?= $font ?>; font-size:10px;">
<tr style="background:<?= $SOFT ?>;"><th align="left">#</th><th align="left">Voucher No.</th><th align="left">Tour</th><th align="left">Guest</th><th align="left">Service · dates</th><th align="left">Invoice / ref</th><th align="right">Amount</th><?php if ($d['is_usd']): ?><th align="right">LKR paid</th><?php endif; ?></tr>
<?php foreach ($ch as $i => $c): ?>
<tr style="border-top:1px solid <?= $LINE ?>;"><td><?= $i + 1 ?></td><td><b><?= (int) $c['vnum'] ?></b></td><td><?= $e($c['manual_id']) ?></td><td><?= $e($c['guest']) ?></td><td><?= $e($c['kind']) ?><?= $c['service_dates'] ? ' · ' . $e($docs->dateSpan($c['service_dates'])) : '' ?></td><td><?= $e($c['invoice_no'] ?: '—') ?></td>
<td align="right"><?= $c['currency'] === 'USD' ? 'USD ' . $m($c['usd_amount']) : 'LKR ' . $m($c['lkr_amount']) ?></td><?php if ($d['is_usd']): ?><td align="right"><?= $m($c['paid_lkr'] ?? $c['approval_lkr']) ?></td><?php endif; ?></tr>
<?php endforeach; ?>
<tr style="background:<?= $SOFT ?>; font-weight:bold;"><td colspan="6" align="right">Total</td><td align="right"><?= $d['is_usd'] ? 'USD ' . $m($tot['transfer_total']) : 'LKR ' . $m($tot['voucher_total']) ?></td><?php if ($d['is_usd']): ?><td align="right"><?= $m($paidLkr) ?></td><?php endif; ?></tr>
</table>
<p style="font-size:9.5px; color:<?= $MUTED ?>; margin-top:10px;">Bank charges, if any, were borne by <?= $e($company['name']) ?> and are not deducted from the amounts above. Please quote the voucher numbers in any query.</p>
<p style="font-size:11px; margin-top:12px;">Kind regards,<br><?= $e($company['name']) ?></p>
<?php if (!$pdf): ?></body></html><?php endif; ?>
