<?php
/**
 * tf-paydesk-r1 — the supplier's Paid Voucher / Remittance Advice: one voucher, one supplier, nothing of anybody else.
 * Rendered as a page (preview) and as the PDF attached to the e-mail. Avenir's paying account appears as the Bank
 * Account Master's external-display setting allows (full, masked to the approved digits, or hidden).
 *   $d  Documents::voucher()    $company    $for_pdf
 */
$v = $d['voucher']; $t = $d['tour'] ?: []; $x = $d['ext']; $sup = $d['supplier']; $acct = $d['bank_account']; $init = $d['initiation']; $app = $d['approval']; $rem = $d['remittance'];
$e = static fn ($s): string => esc((string) ($s ?? ''));
$m = static fn ($n): string => number_format((float) ($n ?? 0), 2);
$dt = static function ($s, $f = 'j M Y') { $s = trim((string) ($s ?? '')); if ($s === '' || str_starts_with($s, '0000')) return '—'; $ts = strtotime($s); return $ts ? date($f, $ts) : $s; };
$pdf = !empty($for_pdf);
$INK = '#2c3e50'; $MUTED = '#6c757d'; $P = '#842484'; $SOFT = '#f3e3f3'; $LINE = '#ced4da';
$usd = $d['usd'];
$paidDate = $app['paid_date'] ?? ($v['bank_date'] ?: substr((string) $v['finalized_date'], 0, 10));
$bankRef = $app['bank_ref'] ?? ($init['bank_ref'] ?? ($init['batch_ref'] ?? ''));
$ext = $acct ? \App\Libraries\TourPayments\BankAccounts::external($acct) : null;
$kv = static function ($k, $val) use ($MUTED, $INK) { return '<tr><td style="font-size:10.5px; color:' . $MUTED . '; padding:4px 6px; width:36%;">' . $k . '</td><td style="font-size:11px; color:' . $INK . '; padding:4px 6px;">' . ($val === '' || $val === null ? '—' : $val) . '</td></tr>'; };
$tfDocs = new \App\Libraries\TourPayments\Documents(\Config\Database::connect());
$span = $tfDocs->dateSpan($d['service_dates']);
// tf-docs-r1 (24 Nov 2026): the stay in full; the Avenir logo and purple; the hotel's payment kept apart from Avenir's own bank charges
$tfStay = ($d['kind'] ?? '') === 'Hotel' ? $tfDocs->stay((array) $d['service_dates'], $d['request']['start_date'] ?? null, $d['request']['end_date'] ?? null) : null;
$tfLogoFile = (defined('FCPATH') ? FCPATH : '') . 'assets/images/Logo-new.png';
$tfCharges = $app && $app['charges'] !== null ? (float) $app['charges'] : ($init && ($init['charges'] ?? null) !== null ? (float) $init['charges'] : 0.0);
$invoices = array_values(array_unique(array_filter(array_merge([$x['invoice_no'] ?? null, $v['inv'] !== '0' ? $v['inv'] : null], array_map(static fn ($doc) => $doc['kind'] === 'invoice' ? $doc['number'] : null, $d['documents']), array_map(static fn ($it) => $it['invoice_no'], $d['batch_items'] ?? [])))));
?>
<?php if (!$pdf): ?>
<!doctype html><html><head><meta charset="utf-8"><title>Remittance advice <?= (int) $v['Vnum'] ?> — <?= $e($sup['supplier_name']) ?></title><meta name="viewport" content="width=device-width, initial-scale=1">
<style> body { font-family: Arial, Helvetica, sans-serif; color:<?= $INK ?>; margin:16px; max-width:800px; } .toolbar { display:flex; gap:8px; margin-bottom:12px; } .toolbar a, .toolbar button { font:inherit; font-size:13px; padding:7px 12px; border:1px solid <?= $LINE ?>; border-radius:8px; background:#fff; color:<?= $INK ?>; text-decoration:none; cursor:pointer; } .toolbar a.main { background:<?= $P ?>; border-color:<?= $P ?>; color:#fff; font-weight:bold; } @media print { .toolbar { display:none; } }</style></head><body>
<div class="toolbar"><a class="main" href="<?= base_url('tour/tour_payment_dashboard/remittance_pdf/' . (int) $v['Vnum']) ?>" target="_blank">⬇ PDF</a><button onclick="window.print()">Print</button><a href="javascript:history.back()">← Back</a>
<?php if ($rem): ?><span style="font-size:12px; color:<?= $MUTED ?>; align-self:center;">Status: <b><?= $e(['pending' => 'Not prepared', 'prepared' => 'Prepared — not sent', 'sent' => 'Sent', 'failed' => 'Send failed'][$rem['status']] ?? 'Not prepared') ?></b><?= $rem['sent_at'] ? ' · sent ' . $dt($rem['sent_at'], 'j M Y H:i') . ' to ' . $e($rem['to_email']) : '' ?></span><?php endif; ?>
<?php if (!empty($no_pdf_lib)): ?><span style="font-size:12px;color:#1a1a1a;">The PDF library is not installed on this server yet — use Print → Save as PDF.</span><?php endif; ?></div>
<?php endif; ?>
<table cellpadding="0" cellspacing="0" width="100%" style="font-family:<?= $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif' ?>; color:<?= $INK ?>; font-size:11px;">
<tr><td width="50%" style="padding:0 0 6px; border-bottom:2px solid <?= $P ?>;"><?php if ($pdf): ?><?= is_file($tfLogoFile) ? '<img src="' . $e($tfLogoFile) . '" height="42" alt="Avenir Holidays">' : '<span style="font-size:14pt; color:' . $P . ';">AVENIR HOLIDAYS</span>' ?><?php else: ?><img src="<?= base_url('assets/images/Logo-new.png') ?>" alt="Avenir Holidays — Connecting Cultures" style="height:58px; width:auto; display:block;"><?php endif; ?></td>
<td width="50%" align="right" style="padding:0 0 6px; border-bottom:2px solid <?= $P ?>; text-align:right;">
    <div style="font-size:17px; font-weight:bold; color:<?= $P ?>;">REMITTANCE ADVICE</div>
    <div style="font-size:11px;">Payment Voucher No. <?= (int) $v['Vnum'] ?> · paid <?= $dt($paidDate) ?></div>
    <div style="font-size:9.5px; color:<?= $MUTED ?>;"><?= $e($company['name']) ?> · Tel <?= $e($company['phone']) ?></div>
</td></tr></table>

<table cellpadding="0" cellspacing="0" width="100%" style="margin-top:12px; font-family:<?= $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif' ?>;">
<tr valign="top"><td width="50%" style="padding-right:8px;">
    <div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin-bottom:3px;">To</div>
    <div style="font-size:13px; font-weight:bold;"><?= $e($sup['supplier_name']) ?></div>
    <div style="font-size:10.5px; color:<?= $MUTED ?>;"><?= $e($sup['remittance_email'] ?: '') ?></div>
    <p style="font-size:11px; margin:10px 0 0;">Dear <?= $e($sup['supplier_name']) ?>,<br><br>We confirm that the following payment has been completed. Please check your bank account.</p>
</td><td width="50%" style="padding-left:8px;">
    <div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin-bottom:3px;">Payment</div>
    <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>;">
        <?php $app = $d['approval'] ?? null; $paidLkr = $app && $app['lkr_amount'] !== null ? (float) $app['lkr_amount'] : (float) $v['Amount']; ?>
        <?= $kv('Paid to your account', '<b style="font-size:14px; color:' . $P . ';">LKR ' . $m($paidLkr) . '</b>') ?>
        <?php if ($usd): ?><?= $kv('Equivalent', 'USD ' . $m($v['doller_amt']) . ' at 1 USD = LKR ' . number_format((float) $d['usd_rate'], 2) . ' <span style="color:' . $MUTED . '; font-size:9px;">rate of the payment date</span>') ?><?php endif; ?>
        <?= $kv('Payment date', $dt($paidDate)) ?>
        <?= $kv('Bank transaction reference', $e($bankRef)) ?>
        <?= $kv('Payment Voucher No.', '<b>' . (int) $v['Vnum'] . '</b>' . (!empty($d['batch']) ? ' · batch ' . $e($d['batch']['batch_no']) : '')) ?>
    </table>
</td></tr></table>

<div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin:12px 0 3px;">What this payment is for</div>
<table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>; font-family:<?= $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif' ?>;">
    <?= $kv('Tour ID', '<b>' . $e($t['manual_id'] ?? $v['ref_po']) . '</b>') ?>
    <?= $kv('Passenger / group', $e($t['consignee'] ?? '')) ?>
    <?= $kv('Service', $e($d['description'])) ?>
    <?php if ($tfStay): ?><?= $kv('Check-in', $e($tfStay['in_text'])) ?><?= $kv('Check-out', $e($tfStay['out_text'])) ?><?= $kv('Nights', $e((string) $tfStay['nights'])) ?><?php else: ?><?= $kv('Service dates', $e($span)) ?><?php endif; ?>
    <?= $kv('Hotel booking reference', $e($x['booking_ref'] ?? '')) ?>
    <?= $kv('Supplier invoice number(s)', $e(implode(', ', $invoices))) ?>
    <?php if (!empty($d['batch_items']) && count($d['batch_items']) > 1): $fig = $d['figures']; ?>
    <?= $kv('Payments covered (batch ' . $e($d['batch']['batch_no']) . ')', implode('<br>', array_map(static function ($it, $k) use ($e, $fig, $m) { $lkr = $fig['lines'][$k]['actual_lkr'] ?? $it['approval_lkr']; return $e($it['manual_id'] . ' · ' . $it['guest'] . ' · ' . $it['kind'] . ($it['invoice_no'] ? ' · inv. ' . $it['invoice_no'] : '') . ' · ' . ($it['currency'] === 'USD' ? 'USD ' . number_format((float) $it['usd_amount'], 2) . ' → ' : '') . 'LKR ' . number_format((float) $lkr, 2)); }, $d['batch_items'], array_keys($d['batch_items'])))) ?>
    <?php elseif (count($d['lines']) > 1): ?><?= $kv('Charges covered', implode('<br>', array_map(static fn ($l) => $e(($l['manual_id'] ?: $l['Invoice']) . ' · ' . $l['Description'] . ' · LKR ' . number_format((float) $l['Amount'], 2)), $d['lines']))) ?><?php endif; ?>
</table>

<div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin:12px 0 3px;">Payment summary</div>
<table cellpadding="<?= $pdf ? 4 : 0 ?>" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>; font-family:<?= $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif' ?>;">
    <tr><td width="64%" bgcolor="<?= $SOFT ?>" style="font-size:11px; padding:6px; background-color:<?= $SOFT ?>;"><b>Hotel payment — credited to your account</b></td><td width="36%" align="right" bgcolor="<?= $SOFT ?>" style="font-size:12px; padding:6px; text-align:right; background-color:<?= $SOFT ?>;"><b>LKR <?= $m($paidLkr) ?></b></td></tr>
    <tr><td width="64%" style="font-size:10.5px; padding:6px; color:<?= $MUTED ?>;">Bank charges — paid by Avenir Holidays to its own bank; not part of your payment</td><td width="36%" align="right" style="font-size:10.5px; padding:6px; text-align:right; color:<?= $MUTED ?>;">LKR <?= $m($tfCharges) ?></td></tr>
    <tr><td width="64%" style="font-size:10.5px; padding:6px; color:<?= $MUTED ?>; border-top:1px solid <?= $LINE ?>;">Total debited from Avenir's account (for Avenir's records)</td><td width="36%" align="right" style="font-size:10.5px; padding:6px; text-align:right; color:<?= $MUTED ?>; border-top:1px solid <?= $LINE ?>;">LKR <?= $m($paidLkr + $tfCharges) ?></td></tr>
</table>

<div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin:12px 0 3px;">Paid from</div>
<table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>; font-family:<?= $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif' ?>;">
    <?= $kv('Paying bank', $ext ? $e($ext['bank']) : $e($v['Bank'])) ?>
    <?php if ($ext && $ext['shown']): ?><?= $kv('Account', $e($ext['account_name']) . ($ext['account'] ? ' · ' . $e($ext['account']) : '') . ($ext['currency'] ? ' · ' . $e($ext['currency']) : '')) ?><?php endif; ?>
    <?= $kv('Beneficiary account (yours)', $e($sup['bank_name']) . ($sup['branch'] ? ', ' . $e($sup['branch']) : '') . ($sup['account_no'] ? ' · A/C ' . $e(\App\Libraries\TourPayments\BankAccounts::mask($sup['account_no'], 4)) : '')) ?>
</table>

<p style="font-size:11px; margin-top:14px;">Kind regards,<br><b>Avenir Holidays</b></p>
<div style="margin-top:10px; font-size:9px; color:<?= $MUTED ?>; border-top:1px solid <?= $LINE ?>; padding-top:5px;">Remittance advice for Payment Voucher No. <?= (int) $v['Vnum'] ?> · <?= $e($t['manual_id'] ?? '') ?> · issued <?= date('j M Y H:i') ?> · <?= $e($company['name']) ?>. This advice concerns only the supplier named above.</div>
<?php if (!$pdf): ?></body></html><?php endif; ?>
