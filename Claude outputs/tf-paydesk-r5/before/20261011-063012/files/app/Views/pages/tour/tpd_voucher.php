<?php
/**
 * tf-paydesk-r1 — the internal payment voucher / payment instruction (TourPaymentDeskController::voucher, ?pdf=1 → TCPDF).
 * Everything management approved, for the accountant: who is paid, how much, in what currency and at what rate, from
 * which Avenir account, with the invoice, booking and service details, the supporting documents, and the steps so far.
 *   $d           Documents::voucher()    $company    the company's lines    $for_pdf    tables and inline styles only
 *   $bank_visible whether this sign-in may see account numbers (accountant / approver / bank approver)
 */
$v = $d['voucher']; $t = $d['tour'] ?: []; $r = $d['request']; $x = $d['ext']; $sup = $d['supplier']; $acct = $d['bank_account']; $init = $d['initiation']; $app = $d['approval']; $rem = $d['remittance'];
$e = static fn ($s): string => esc((string) ($s ?? ''));
$m = static fn ($n): string => number_format((float) ($n ?? 0), 2);
$dt = static function ($s, $f = 'j M Y') { $s = trim((string) ($s ?? '')); if ($s === '' || str_starts_with($s, '0000')) return '—'; $ts = strtotime($s); return $ts ? date($f, $ts) : $s; };
$pdf = !empty($for_pdf);
$INK = '#2c3e50'; $MUTED = '#6c757d'; $P = '#842484'; $SOFT = '#f3e7f3'; $LINE = '#ced4da';
$mask = static fn ($no) => \App\Libraries\TourPayments\BankAccounts::mask($no, 4);
$usd = $d['usd'];
$amountLkr = (float) $v['Amount'];
$stageNo = ['voucher_issued' => 5, 'bank_initiated' => 6, 'paid' => 7, 'notify_pending' => 8, 'notified' => 9, 'reversed' => 0][$d['stage']] ?? 0;
$th = 'style="text-align:left; font-size:9px; letter-spacing:.04em; text-transform:uppercase; color:' . $MUTED . '; padding:4px 6px; border-bottom:1px solid ' . $LINE . ';"';
$td = 'style="font-size:10.5px; padding:4px 6px; border-bottom:1px solid #e9ecef; vertical-align:top; color:' . $INK . ';"';
$kv = static function ($k, $val) use ($MUTED, $INK) { return '<tr><td style="font-size:10px; color:' . $MUTED . '; padding:3px 6px; width:34%;">' . $k . '</td><td style="font-size:10.5px; color:' . $INK . '; padding:3px 6px;">' . ($val === '' || $val === null ? '—' : $val) . '</td></tr>'; };
$docsList = array_filter($d['documents'], static fn ($x) => $x['kind'] !== 'remittance');
?>
<?php if (!$pdf): ?>
<!doctype html><html><head><meta charset="utf-8"><title>Payment Voucher No. <?= (int) $v['Vnum'] ?> — <?= $e($t['manual_id'] ?? '') ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { font-family: Arial, Helvetica, sans-serif; color:<?= $INK ?>; margin:16px; max-width:900px; }
    .toolbar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:12px; }
    .toolbar a, .toolbar button { font:inherit; font-size:13px; padding:7px 12px; border:1px solid <?= $LINE ?>; border-radius:8px; background:#fff; color:<?= $INK ?>; text-decoration:none; cursor:pointer; }
    .toolbar a.main { background:<?= $P ?>; border-color:<?= $P ?>; color:#fff; font-weight:bold; }
    @media print { .toolbar { display:none; } body { margin:0; } }
</style></head><body>
<div class="toolbar">
    <a class="main" href="?pdf=1" target="_blank">⬇ PDF</a><button onclick="window.print()">Print</button><a href="javascript:history.back()">← Back</a>
    <span style="color:<?= $MUTED ?>; font-size:12px;">Printed <?= date('j M Y H:i') ?> by <?= $e($d['perms']['admin'] ?? false ? session()->get('username') : session()->get('username')) ?></span>
    <?php if (!empty($no_pdf_lib)): ?><span style="font-size:12px;color:#b7791f;">The PDF library is not installed on this server yet — use Print → Save as PDF.</span><?php endif; ?>
</div>
<?php endif; ?>
<table cellpadding="0" cellspacing="0" width="100%" style="font-family:<?= $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif' ?>; color:<?= $INK ?>; font-size:10.5px;">
<tr><td style="padding:10px 12px; background:<?= $P ?>; color:#fff;" width="62%">
    <div style="font-size:16px; font-weight:bold;">Payment Voucher No. <?= (int) $v['Vnum'] ?></div>
    <div style="font-size:11px;">Internal payment instruction · <?= $e($d['kind']) ?><?= $x && $x['parent_pr_id'] ? ' · balance payment' : '' ?></div>
    <div style="font-size:11px; margin-top:4px;">Status: <b><?= $e($d['stage_label']) ?></b></div>
</td><td style="padding:10px 12px; background:<?= $SOFT ?>; color:<?= $INK ?>; text-align:right;" width="38%">
    <div style="font-weight:bold;"><?= $e($company['name']) ?></div>
    <div style="font-size:9.5px; color:<?= $MUTED ?>;"><?= $e($company['address']) ?><br>Tel <?= $e($company['phone']) ?><?= $company['email'] ? ' · ' . $e($company['email']) : '' ?></div>
    <div style="font-size:9.5px; margin-top:4px;">Issued <?= $dt($v['C_Date'], 'j M Y H:i') ?> by <?= $e($v['login']) ?></div>
</td></tr>
</table>

<table cellpadding="0" cellspacing="0" width="100%" style="margin-top:10px; font-family:<?= $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif' ?>;">
<tr valign="top">
<td width="50%" style="padding-right:8px;">
    <div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin-bottom:3px;">Tour and service</div>
    <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>;">
        <?= $kv('Tour ID', '<b>' . $e($t['manual_id'] ?? $v['ref_po']) . '</b>') ?>
        <?= $kv('Passenger / group', $e($t['consignee'] ?? '')) ?>
        <?= $kv('Agent', $e(trim((string) ($t['agent_name'] ?? '')))) ?>
        <?= $kv('Tour dates', $dt($t['start_date'] ?? null) . ' – ' . $dt($t['end_date'] ?? null) . (isset($t['passenger_count']) ? ' · ' . (int) $t['passenger_count'] . ' pax' : '')) ?>
        <?= $kv('Service', $e($d['description'])) ?>
        <?= $kv('Service dates', $e($d['service_dates'] ? (new \App\Libraries\TourPayments\Documents(\Config\Database::connect()))->dateSpan($d['service_dates']) : '')) ?>
        <?= $kv('Supplier invoice no.', $e($x['invoice_no'] ?? ($v['inv'] !== '0' ? $v['inv'] : ''))) ?>
        <?= $kv('Hotel booking reference', $e($x['booking_ref'] ?? '')) ?>
        <?= $kv('Due date', $dt($x['due_date'] ?? ($r['start_date'] ?? null))) ?>
    </table>
</td>
<td width="50%" style="padding-left:8px;">
    <div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin-bottom:3px;">Amount</div>
    <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>;">
        <?php $fig = $d['figures'] ?? null; $b = $d['batch'] ?? null; ?>
        <?php if ($b && $fig): /* tf-paydesk-r2: the approval figures (provisional, never overwritten) and the payment-date figures (final at Paid) */ ?>
        <?= $kv('Batch', '<b>' . $e($b['batch_no']) . '</b> · ' . (int) $b['item_count'] . ' payment(s) · ' . (int) $b['tour_count'] . ' tour(s)') ?>
        <?= $kv('Locked USD amount', $usd ? '<b>USD ' . $m($fig['usd_total']) . '</b>' : '—') ?>
        <?php if ($usd): $apRates = array_values(array_unique(array_map(static fn ($i) => number_format((float) $i['approval_rate'], 2), array_filter($d['batch_items'], static fn ($i) => $i['currency'] === 'USD')))); ?>
        <?= $kv('Approval-date rate', '1 USD = LKR ' . $e(implode(' / ', $apRates)) . ' <span style="color:' . $MUTED . '; font-size:9px;">at approval — provisional</span>') ?>
        <?php endif; ?>
        <?= $kv('Provisional LKR (approval)', 'LKR ' . $m($fig['provisional_lkr_total'])) ?>
        <?php if ($usd && $fig['rate']): ?>
        <?= $kv($fig['final'] ? 'Final payment-date rate' : 'Payment-date rate (initiation)', '1 USD = LKR ' . number_format((float) $fig['rate'], 2) . ' <span style="color:' . $MUTED . '; font-size:9px;">' . ($fig['final'] ? 'locked at Paid · ' . $dt($app['paid_date']) : 'confirmed at bank initiation · ' . $dt($init['payment_date'] ?? null)) . '</span>') ?>
        <?php endif; ?>
        <?= $kv($fig['final'] ? 'Final LKR amount (locked)' : 'LKR amount to pay', '<b style="font-size:13px;">LKR ' . $m($fig['actual_lkr_total'] ?? $amountLkr) . '</b>' . ($fig['stage'] === 'provisional' ? ' <span style="color:' . $MUTED . '; font-size:9px;">provisional — final at the payment-date rate</span>' : '')) ?>
        <?php if ($usd && $fig['fx_diff'] !== null): ?>
        <?= $kv('Exchange-rate difference', '<b style="color:' . ($fig['fx_diff'] > 0 ? '#b23a32' : '#1f8a4c') . ';">' . ($fig['fx_diff'] > 0 ? '+ ' : '− ') . $m(abs((float) $fig['fx_diff'])) . ($fig['fx_diff'] > 0 ? ' loss' : ($fig['fx_diff'] < 0 ? ' gain' : '')) . '</b> <span style="color:' . $MUTED . '; font-size:9px;">final − provisional, recorded separately</span>') ?>
        <?php endif; ?>
        <?php if ($app && $app['charges'] !== null): ?><?= $kv('Bank charges · total debited', 'LKR ' . $m($app['charges']) . ' · <b>LKR ' . $m($app['total_debit']) . '</b>' . ((float) $app['charges'] > 0 ? ' <span style="color:' . $MUTED . '; font-size:9px;">' . ((int) ($app['charges_posted'] ?? 0) === 1 ? 'charges posted once to the Bank Charges account, per tour line' : 'charges NOT posted') . '</span>' : '')) ?><?php endif; ?>
        <?php if ($app && isset($app['reconciled'])): ?><?= $kv('Ledger reconciliation', (int) $app['reconciled'] === 1 ? '<b style="color:#1f8a4c;">reconciled</b> — credits to the paying account LKR ' . $m($app['ledger_bank_credit']) . ' = the bank debit' : '<b style="color:#b23a32;">not reconciled</b> — credits to the paying account LKR ' . $m($app['ledger_bank_credit']) . ' against a bank debit of ' . $m($app['total_debit'])) ?><?php endif; ?>
        <?php else: ?>
        <?= $kv('Original amount', $usd ? '<b>USD ' . $m($v['doller_amt']) . '</b>' : '<b>LKR ' . $m($amountLkr) . '</b>') ?>
        <?php if ($usd): ?>
        <?= $kv('Exchange rate', '1 USD = LKR ' . number_format((float) $d['usd_rate'], 2) . ' <span style="color:' . $MUTED . '; font-size:9px;">locked when requested' . (!empty($x['rate_date']) ? ' · ' . $dt($x['rate_date']) : '') . '</span>') ?>
        <?php endif; ?>
        <?= $kv('LKR amount to pay', '<b style="font-size:13px;">LKR ' . $m($amountLkr) . '</b>') ?>
        <?php endif; ?>
        <?= $kv('Requested', $r ? $dt($r['payment_request_date'], 'j M Y H:i') . ' by ' . $e($r['payment_request_user']) : '—') ?>
        <?= $kv('Approved by', $r && $r['accept_user'] ? $e($r['accept_user']) . ' · ' . $dt($r['accept_date'], 'j M Y H:i') : $e($v['login']) . ' · ' . $dt($v['C_Date'], 'j M Y H:i')) ?>
        <?= $kv('Avenir paying account', $acct ? $e($acct['label']) . ' · ' . $e($acct['bank_name']) . ($acct['branch'] ? ', ' . $e($acct['branch']) : '') . ($bank_visible ? ' · A/C ' . $e($acct['account_no']) : ' · A/C ' . $e($mask($acct['account_no']))) . ' · ' . $e($acct['currency']) : $e($v['Bank']) . ' <span style="color:' . $MUTED . ';">(' . $e($v['Bank_acc']) . ')</span>') ?>
        <?= $kv('Internal notes', nl2br($e($x['note'] ?? ''))) ?>
    </table>
</td></tr></table>

<div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin:10px 0 3px;">Beneficiary — supplier bank details</div>
<table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>; font-family:<?= $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif' ?>;">
    <tr valign="top"><td width="50%">
        <table cellpadding="0" cellspacing="0" width="100%">
            <?= $kv('Supplier', '<b>' . $e($sup['supplier_name']) . '</b> <span style="color:' . $MUTED . ';">' . $e($v['Acc_No']) . '</span>') ?>
            <?= $kv('Beneficiary name', $e($sup['beneficiary_name'])) ?>
            <?= $kv('Bank', $e(trim($sup['bank_name'] . ($sup['branch'] ? ', ' . $sup['branch'] : '')))) ?>
            <?= $kv('Account number', $bank_visible ? $e($sup['account_no']) : $e($mask($sup['account_no']))) ?>
        </table></td><td width="50%">
        <table cellpadding="0" cellspacing="0" width="100%">
            <?= $kv('Currency / SWIFT', $e(trim($sup['currency'] . ($sup['swift'] ? ' · ' . $sup['swift'] : '')))) ?>
            <?= $kv('Verification', $sup['status'] === 'verified' ? '<b style="color:#1f8a4c;">Verified</b> by ' . $e($sup['verified_by']) . ' on ' . $dt($sup['verified_at'], 'j M Y H:i') : ($sup['status'] === 'changed' ? '<b style="color:#b23a32;">CHANGED since verification — confirm before paying</b>' : '<b style="color:#b7791f;">Not verified — confirm before paying</b>')) ?>
            <?= $kv('Remittance e-mail', $e($sup['remittance_email'])) ?>
            <?= $kv('Invoice e-mail', $e($sup['invoice_email'])) ?>
        </table></td></tr>
</table>

<?php if (!empty($d['batch_items'])): $fig = $d['figures']; ?>
<div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin:10px 0 3px;">Payments in batch <?= $e($d['batch']['batch_no']) ?> — tours and invoices (approval figures · payment-date figures)</div>
<table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>;">
    <tr><th <?= $th ?>>Tour · guest</th><th <?= $th ?>>Service · dates</th><th <?= $th ?>>Invoice</th><th <?= $th ?> align="right">Locked amount</th><th <?= $th ?> align="right">Appr. rate</th><th <?= $th ?> align="right">Provisional LKR</th><th <?= $th ?> align="right"><?= $fig['final'] ? 'Final LKR' : 'LKR' ?></th><th <?= $th ?> align="right">Difference</th><th <?= $th ?> align="right">Charges share</th><th <?= $th ?>>Approved</th></tr>
    <?php foreach ($d['batch_items'] as $k => $it): $ln = $fig['lines'][$k]; ?><tr><td <?= $td ?>><b><?= $e($it['manual_id']) ?></b> <?= $e($it['guest']) ?></td><td <?= $td ?>><?= $e($it['kind']) ?><?= $it['service_dates'] ? ' · ' . $e((new \App\Libraries\TourPayments\Documents(\Config\Database::connect()))->dateSpan($it['service_dates'])) : '' ?></td><td <?= $td ?>><?= $e($it['invoice_no'] ?: '—') ?></td><td <?= $td ?> align="right"><?= $it['currency'] === 'USD' ? 'USD ' . $m($it['usd_amount']) : 'LKR ' . $m($it['lkr_amount']) ?></td><td <?= $td ?> align="right"><?= $it['currency'] === 'USD' ? number_format((float) $it['approval_rate'], 2) : '—' ?></td><td <?= $td ?> align="right"><?= $m($it['approval_lkr']) ?></td><td <?= $td ?> align="right"><?= $ln['actual_lkr'] !== null ? $m($ln['actual_lkr']) : '—' ?></td><td <?= $td ?> align="right"><?= $ln['fx_diff'] !== null ? ($ln['fx_diff'] > 0 ? '+' : '') . $m($ln['fx_diff']) : '—' ?></td><td <?= $td ?> align="right"><?= isset($it['charge_share']) && $it['charge_share'] !== null ? $m($it['charge_share']) : '—' ?></td><td <?= $td ?> style="color:<?= $MUTED ?>;"><?= $e($it['approved_by']) ?> · <?= $dt($it['approved_at'], 'j M Y H:i') ?></td></tr><?php endforeach; ?>
    <tr><td <?= $td ?> colspan="5" align="right"><b>Totals</b></td><td <?= $td ?> align="right"><b><?= $m($fig['provisional_lkr_total']) ?></b></td><td <?= $td ?> align="right"><b><?= $fig['actual_lkr_total'] !== null ? $m($fig['actual_lkr_total']) : '—' ?></b></td><td <?= $td ?> align="right"><b><?= $fig['fx_diff'] !== null ? ($fig['fx_diff'] > 0 ? '+' : '') . $m($fig['fx_diff']) : '—' ?></b></td><td <?= $td ?> align="right"><b><?= $app && $app['charges'] !== null ? $m($app['charges']) : '—' ?></b></td><td <?= $td ?>></td></tr>
</table>
<?php elseif (count($d['lines']) > 1): ?>
<div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin:10px 0 3px;">Lines on this voucher (bulk payment — one supplier, several charges)</div>
<table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>;">
    <tr><th <?= $th ?>>Tour</th><th <?= $th ?>>Description</th><th <?= $th ?> align="right">Rate</th><th <?= $th ?> align="right">LKR</th></tr>
    <?php foreach ($d['lines'] as $l): ?><tr><td <?= $td ?>><?= $e($l['manual_id'] ?: $l['Invoice']) ?> <?= $e($l['consignee']) ?></td><td <?= $td ?>><?= $e($l['Description']) ?></td><td <?= $td ?> align="right"><?= (float) $l['Rate'] > 0 ? $m($l['Rate']) : '—' ?></td><td <?= $td ?> align="right"><?= $m($l['Amount']) ?></td></tr><?php endforeach; ?>
</table>
<?php endif; ?>

<div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin:10px 0 3px;">The steps</div>
<table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>;">
    <tr><th <?= $th ?>>Step</th><th <?= $th ?>>When</th><th <?= $th ?>>Who</th><th <?= $th ?>>Details</th></tr>
    <tr><td <?= $td ?>>1–3 Requested · awaiting management approval</td><td <?= $td ?>><?= $r ? $dt($r['payment_request_date'], 'j M Y H:i') : '—' ?></td><td <?= $td ?>><?= $e($r['payment_request_user'] ?? '') ?></td><td <?= $td ?>><?= $r ? ($usd ? 'USD ' . $m($r['requestAmount']) : 'LKR ' . $m($r['requestAmount'])) : '' ?></td></tr>
    <tr><td <?= $td ?>>4 Approved for payment</td><td <?= $td ?>><?= $r && $r['accept_date'] ? $dt($r['accept_date'], 'j M Y H:i') : '—' ?></td><td <?= $td ?>><?= $e($r['accept_user'] ?? '') ?></td><td <?= $td ?>>Payment instruction created</td></tr>
    <tr><td <?= $td ?>>5 Payment voucher issued</td><td <?= $td ?>><?= $dt($v['C_Date'], 'j M Y H:i') ?></td><td <?= $td ?>><?= $e($v['login']) ?></td><td <?= $td ?>>Payment Voucher No. <?= (int) $v['Vnum'] ?><?= !empty($d['batch']) ? ' · supplier batch ' . $e($d['batch']['batch_no']) . ' created by ' . $e($d['batch']['created_by']) : '' ?> · in Bank Payment Check</td></tr>
    <tr><td <?= $td ?>>6 Bank payment initiated — awaiting bank approval</td><td <?= $td ?>><?= $init ? $dt($init['initiated_at'], 'j M Y H:i') : '—' ?></td><td <?= $td ?>><?= $e($init['initiated_by'] ?? '') ?></td><td <?= $td ?>><?= $init ? $e($init['currency']) . ' ' . $m($init['amount']) . ($init['batch_ref'] ? ' · batch ' . $e($init['batch_ref']) : '') . ($init['bank_ref'] ? ' · ref ' . $e($init['bank_ref']) : '') . ($init['charges'] !== null ? ' · charges ' . $m($init['charges']) : '') . (!empty($init['payment_date']) ? ' · payment date ' . $dt($init['payment_date']) : '') . (!empty($init['usd_rate']) ? ' · rate ' . number_format((float) $init['usd_rate'], 2) : '') . (!empty($init['total_debit']) ? ' · total debit ' . $m($init['total_debit']) : '') . ($init['note'] ? ' · ' . $e($init['note']) : '') : '' ?></td></tr>
    <tr><td <?= $td ?>>7 Paid — final bank approval</td><td <?= $td ?>><?= $app ? $dt($app['approved_at'], 'j M Y H:i') : ((int) $v['finalized'] === 1 ? $dt($v['finalized_date'], 'j M Y H:i') : '—') ?></td><td <?= $td ?>><?= $e($app['approved_by'] ?? ((int) $v['finalized'] === 1 ? $v['finalized_user'] : '')) ?></td><td <?= $td ?>><?= $app ? 'Paid ' . $dt($app['paid_date']) . ' · LKR ' . $m($app['lkr_amount']) . ($app['usd_rate'] ? ' (USD ' . $m($app['usd_amount']) . ' @ ' . number_format((float) $app['usd_rate'], 2) . ')' : '') . (isset($app['fx_diff']) && $app['fx_diff'] !== null ? ' · difference ' . ($app['fx_diff'] > 0 ? '+' : '') . $m($app['fx_diff']) : '') . (isset($app['charges']) && $app['charges'] !== null ? ' · charges ' . $m($app['charges']) . ' · total debit ' . $m($app['total_debit']) : '') . ' · ref ' . $e($app['bank_ref']) . ' · <b>locked</b>' : ((int) $v['finalized'] === 1 ? 'Bank date ' . $dt($v['bank_date']) : '') ?></td></tr>
    <tr><td <?= $td ?>>8–9 Supplier notification</td><td <?= $td ?>><?= $rem && $rem['sent_at'] ? $dt($rem['sent_at'], 'j M Y H:i') : '—' ?></td><td <?= $td ?>><?= $e($rem['sent_by'] ?? '') ?></td><td <?= $td ?>><?= $rem ? ($rem['status'] === 'sent' ? 'Remittance advice sent to ' . $e($rem['to_email']) : 'Pending (' . $e($rem['status']) . ')') : '' ?></td></tr>
    <?php if ((int) $v['Aflag'] === 2): ?><tr><td <?= $td ?> colspan="4" style="color:#b23a32;"><b>Reversed:</b> voucher cancelled<?= $v['remark'] ? ' — ' . $e($v['remark']) : '' ?><?= $v['Aut_user'] ? ' by ' . $e($v['Aut_user']) : '' ?></td></tr><?php endif; ?>
</table>

<div style="font-size:10px; letter-spacing:.05em; text-transform:uppercase; color:<?= $MUTED ?>; margin:10px 0 3px;">Supporting documents</div>
<table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid <?= $LINE ?>;">
    <?php if (!$docsList): ?><tr><td <?= $td ?> style="color:<?= $MUTED ?>;">None attached yet.</td></tr><?php endif; ?>
    <?php foreach ($docsList as $doc): ?><tr><td <?= $td ?>><?= $e(ucfirst(str_replace('_', ' ', $doc['kind']))) ?></td><td <?= $td ?>><?= $e($doc['title']) ?><?= $doc['number'] ? ' · ' . $e($doc['number']) : '' ?></td><td <?= $td ?>><?= $doc['file_path'] ? ($pdf ? 'file attached' : '<a href="' . base_url('tour/tour_payment_dashboard/file/' . (int) $doc['id']) . '" target="_blank">open</a>') : 'number only' ?></td><td <?= $td ?> style="color:<?= $MUTED ?>;"><?= $e($doc['uploaded_by']) ?> · <?= $dt($doc['uploaded_at'], 'j M Y H:i') ?></td></tr><?php endforeach; ?>
</table>

<div style="margin-top:12px; font-size:9px; color:<?= $MUTED ?>; border-top:1px solid <?= $LINE ?>; padding-top:5px;">
    Payment Voucher No. <?= (int) $v['Vnum'] ?> · <?= $e($t['manual_id'] ?? '') ?> · <?= $e($company['name']) ?> · printed <?= date('j M Y H:i') ?>. The management approval authorises the accountant to initiate the payment; only the bank approver's Paid marks it fully paid.
</div>
<?php if (!$pdf): ?></body></html><?php endif; ?>
