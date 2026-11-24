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
// tf-voucher-r4: the LAYOUT reads the voucher's own state ($tfIsFinal). The preview / print lock is a separate control: it is applied only
// when the controller passes $final (tf-finance-r1); where it does not, Print and PDF stay as they were before the lock existed.
$tfIsFinal = (int) ($d['voucher']['finalized'] ?? 0) === 1;
$tfLockOn = isset($final);
$final = $tfLockOn ? !empty($final) : true;
$INK = '#2c3e50'; $MUTED = '#6c757d'; $P = '#842484'; $SOFT = '#f3e3f3'; $LINE = '#ced4da';
$mask = static fn ($no) => \App\Libraries\TourPayments\BankAccounts::mask($no, 4);
$usd = $d['usd'];
$amountLkr = (float) $v['Amount'];
$stageNo = ['voucher_issued' => 5, 'bank_initiated' => 6, 'paid' => 7, 'notify_pending' => 8, 'notified' => 9, 'reversed' => 0][$d['stage']] ?? 0;
$th = 'style="text-align:left; font-size:9px; letter-spacing:.04em; text-transform:uppercase; color:' . $MUTED . '; padding:4px 6px; border-bottom:1px solid ' . $LINE . ';"';
$td = 'style="font-size:10.5px; padding:4px 6px; border-bottom:1px solid #e9ecef; vertical-align:top; color:' . $INK . ';"';
$kv = static function ($k, $val) use ($MUTED, $INK) { return '<tr><td width="34%" style="font-size:10px; color:' . $MUTED . '; padding:3px 6px; width:34%;">' . $k . '</td><td width="66%" style="font-size:10.5px; color:' . $INK . '; padding:3px 6px;">' . ($val === '' || $val === null ? '—' : $val) . '</td></tr>'; };
$docsList = array_filter($d['documents'], static fn ($x) => $x['kind'] !== 'remittance');
// tf-paydesk-r7h: only the request(s) STORED as this voucher's (Documents::voucher → RequestLink::ofVoucher), in their own currency
$link = $d['request_link'] ?? null; $ra = $d['request_amount'] ?? null;
$linkBy = ['batch' => 'supplier batch ' . ($d['batch']['batch_no'] ?? ''), 'desk' => 'the desk record of the request', 'voucher_line' => 'the voucher line\'s reference',
    'review' => 'a review (' . ($link['review']['by'] ?? '') . ', ' . ($link['review']['at'] ?? '') . ')'];   // tf-paydesk-r7h build 5
$linkHtml = '—';
if ($link) {
    if ($r && isset($linkBy[$link['source']])) {
        $linkHtml = '<b>Request #' . (int) $r['id'] . '</b> · ' . $e($r['type']) . ' · ' . $e($ra['label'] ?? '') . ' <span style="color:' . $MUTED . '; font-size:9px;">linked by ' . $e($linkBy[$link['source']]) . '</span>'
            . ($link['source'] === 'review' ? '<br><span style="color:' . $MUTED . '; font-size:9px;">' . $e($link['note']) . '</span>' : '');
    } elseif (count($link['requests']) > 1) {
        $linkHtml = $e($link['note']) . '<br>' . implode('<br>', array_map(static fn ($q) => '#' . (int) $q['id'] . ' · ' . $e($q['manual_id']) . ' · ' . $e($q['type']) . ' · ' . $e($link['amounts'][(int) $q['id']]['label'] ?? ''), $link['requests']));
    } else {
        $linkHtml = '<span style="color:#9a6400;">' . $e($link['note'] ?: 'No payment request is linked to this voucher.') . '</span>';
    }
}
$manyReq = $link && !$r && count($link['requests']) > 1;
?>
<?php if (!$pdf): ?>
<!doctype html><html><head><meta charset="utf-8"><title>Payment Voucher No. <?= (int) $v['Vnum'] ?> — <?= $e($t['manual_id'] ?? '') ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { font-family: Arial, Helvetica, sans-serif; color:<?= $INK ?>; margin:16px; max-width:900px; }
    .toolbar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:12px; }
    .toolbar a, .toolbar button { font:inherit; font-size:13px; padding:7px 12px; border:1px solid <?= $LINE ?>; border-radius:8px; background:#fff; color:<?= $INK ?>; text-decoration:none; cursor:pointer; }
    .toolbar a.main { background:<?= $P ?>; border-color:<?= $P ?>; color:#fff; font-weight:bold; }
    @page { size:A4 portrait; margin:11mm 12mm; }
    @media print { .toolbar { display:none; } body { margin:0; max-width:none; } table { page-break-inside:avoid; } }
    <?php if (empty($final)): /* tf-finance-r1: a preview cannot be printed */ ?>
    .pv-preview { background:#842484; color:#fff; font-weight:bold; font-size:13px; padding:8px 12px; border-radius:8px; margin-bottom:12px; }
    body:after { content:'PREVIEW — NOT FINALIZED'; position:fixed; top:42%; left:0; right:0; text-align:center; font-size:54px; font-weight:bold; color:rgba(132,36,132,.13); transform:rotate(-24deg); pointer-events:none; z-index:5; }
    @media print { body > * { display:none !important; } body:before { content:'This voucher is a preview. It can be printed only after the payment is finalized.'; display:block; font-size:16px; padding:40px; } body:after { display:none; } }
    <?php endif; ?>
</style></head><body>
<div class="toolbar">
    <?php if (!empty($final)): ?><a class="main" href="?pdf=1" target="_blank">⬇ PDF</a><button onclick="window.print()">Print</button><?php else: ?><span style="font-size:12.5px; padding:7px 12px; border:1px dashed #b9a9c2; border-radius:8px; color:#5a5160;">Print and PDF are unlocked after finalization</span><?php endif; ?><a href="javascript:history.back()">← Back</a>
    <span style="color:<?= $MUTED ?>; font-size:12px;">Printed <?= date('j M Y H:i') ?> by <?= $e($d['perms']['admin'] ?? false ? session()->get('username') : session()->get('username')) ?></span>
    <?php if (!empty($no_pdf_lib)): ?><span style="font-size:12px;color:#1a1a1a;">The PDF library is not installed on this server yet — use Print → Save as PDF.</span><?php endif; ?>
</div>
<?php if (empty($final)): ?><div class="pv-preview">PREVIEW — this voucher is not finalized yet. Printing and the PDF are unlocked once the bank payment is finalized.</div><?php endif; ?>
<?php if (!empty($final) && !empty($_GET['print'])): ?><script>window.addEventListener('load', function () { window.print(); });</script><?php endif; ?>
<?php endif; ?>
<?php
/* tf-voucher-r2 (23 Nov 2026, Shantha): the payment voucher in the approved Avenir design — page 1 the voucher, page 2 the detailed
   audit history. Every figure is the one the old layout showed (Documents::voucher); nothing is recalculated here. Account numbers
   show their last four digits only. Tables and inline styles only, so the same markup makes the PDF. */
$PUR = '#842484'; $PSOFT = '#f3e3f3'; $PLINE = '#d9c3dc'; $FONT = $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif';
$fig = $d['figures'] ?? null; $b = $d['batch'] ?? null; $items = $d['batch_items'] ?? [];
$tfDb = \Config\Database::connect(); $tfDocs = new \App\Libraries\TourPayments\Documents($tfDb);
$isFinal = $tfIsFinal;
$inqOf = static function ($manual) use ($tfDb) {
    static $memo = [];
    $manual = trim((string) $manual);
    if ($manual === '') { return ''; }
    if (!array_key_exists($manual, $memo)) {
        try {
            $q = $tfDb->query('SELECT i.inquiry_no FROM tf_tour t JOIN tf_intake i ON i.id = t.intake_id WHERE t.tour_no = ? LIMIT 1', [$manual])->getRowArray();
            $memo[$manual] = trim((string) preg_replace('/^INQ[\s\-:]*/i', '', (string) ($q['inquiry_no'] ?? '')));
        } catch (\Throwable $e) { $memo[$manual] = ''; }
    }
    return $memo[$manual];
};
$tours = $items ? array_values(array_unique(array_filter(array_map(static fn ($i) => (string) ($i['manual_id'] ?? ''), $items)))) : array_filter([(string) ($t['manual_id'] ?? $v['ref_po'] ?? '')]);
$inqs = array_values(array_unique(array_filter(array_map($inqOf, $tours))));
$ending = static function ($no) { $s = preg_replace('/\s+/', '', (string) ($no ?? '')); return $s === '' ? '—' : 'Account ending ' . substr($s, -4); };
$paidLkr = $app ? (float) $app['lkr_amount'] : (float) ($fig['actual_lkr_total'] ?? $fig['provisional_lkr_total'] ?? $amountLkr);
$charges = $app && $app['charges'] !== null ? (float) $app['charges'] : ($init && $init['charges'] !== null ? (float) $init['charges'] : null);
$totalDebit = $app && $app['total_debit'] !== null ? (float) $app['total_debit'] : ($init && !empty($init['total_debit']) ? (float) $init['total_debit'] : null);
$payDate = $app['paid_date'] ?? ($init['payment_date'] ?? null);
$apRates = $usd && $items ? array_values(array_unique(array_map(static fn ($i) => number_format((float) $i['approval_rate'], 2), array_filter($items, static fn ($i) => $i['currency'] === 'USD')))) : [];
$approvedAmt = $usd ? 'USD ' . $m($fig['usd_total'] ?? $v['doller_amt']) : 'LKR ' . $m($fig['provisional_lkr_total'] ?? $amountLkr);
$approvalRate = $usd ? ($apRates ? '1 USD = LKR ' . $e(implode(' / ', $apRates)) : ((float) ($d['usd_rate'] ?? 0) > 0 ? '1 USD = LKR ' . number_format((float) $d['usd_rate'], 2) : '—')) : '—';
$payRate = $usd && $fig && !empty($fig['rate']) ? '1 USD = LKR ' . number_format((float) $fig['rate'], 2) . ($fig['final'] ? '' : ' <span style="color:' . $MUTED . '; font-size:9px;">at bank payment — not final</span>') : '—';
$fx = $usd && $fig && $fig['fx_diff'] !== null ? 'LKR ' . $m(abs((float) $fig['fx_diff'])) . ((float) $fig['fx_diff'] > 0 ? ' loss' : ((float) $fig['fx_diff'] < 0 ? ' gain' : '')) : '—';
$bankRef = $app['bank_ref'] ?? ($init['bank_ref'] ?? '');
$cancelled = in_array((int) $v['Aflag'], [2, 3], true);
$tfRemLabel = ['pending' => 'Not prepared', 'prepared' => 'Prepared — not sent', 'sent' => 'Sent', 'failed' => 'Send failed'];   // tf-docs-r2: the notification status in plain words
$remTxt = $rem ? ($tfRemLabel[$rem['status']] ?? 'Not prepared') : ($isFinal ? 'Not prepared' : 'Not due yet');
$cp = $pdf ? 2.4 : 0;   // TCPDF reads cellpadding, widths and bgcolor on cells — not CSS padding, margins or div backgrounds
$sec = static fn ($title, $cols = 2) => '<tr><td colspan="' . (int) $cols . '" bgcolor="' . $PUR . '" style="background-color:' . $PUR . '; color:#ffffff; font-size:' . ($pdf ? '8.5pt' : '11px') . '; font-weight:bold; letter-spacing:.04em; padding:5px 10px;">' . mb_strtoupper($title) . '</td></tr>';
$gap = $pdf ? '<table cellpadding="0" cellspacing="0" width="100%"><tr><td style="font-size:4pt;">&nbsp;</td></tr></table>' : '';
$big = $pdf ? '13pt' : '17px'; $lh = $pdf ? '1.25' : '1.5';
$row = static function ($k, $val) use ($INK) { return '<tr><td width="38%" style="font-size:10.5px; color:#3d3340; padding:4px 10px; width:38%; border-bottom:1px solid #ece4ee;">' . $k . '</td><td width="62%" style="font-size:10.5px; font-weight:bold; color:' . $INK . '; padding:4px 10px; border-bottom:1px solid #ece4ee;">' . ($val === '' || $val === null ? '—' : $val) . '</td></tr>'; };
$sig = static function ($who, $when) use ($e, $dt, $MUTED) { $who = trim((string) $who); return $who === '' ? '<span style="color:' . $MUTED . ';">—</span>' : '<b>' . $e($who) . '</b>' . ($when ? '<br><span style="font-size:9.5px; color:' . $MUTED . ';">' . $dt($when, 'j M Y H:i') . '</span>' : ''); };
$box = 'border:1px solid ' . $PLINE . ';';
?>
<?php /* tf-voucher-r3 (Shantha, 23 Nov 2026): one official Avenir Holidays logo in the header — no separate company-name heading or address block */
$tfLogoFile = (defined('FCPATH') ? FCPATH : '') . 'assets/images/Logo-new.png'; ?>
<table cellpadding="<?= $pdf ? 1 : 0 ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; color:<?= $INK ?>;">
<tr><td width="50%" style="padding:0 0 4px; border-bottom:2px solid <?= $PUR ?>;"><?php if ($pdf): ?><?= is_file($tfLogoFile) ? '<img src="' . $e($tfLogoFile) . '" height="42" alt="Avenir Holidays">' : '<span style="font-size:14pt; color:#5a1a5a;">AVENIR HOLIDAYS</span>' ?><?php else: ?><img src="<?= base_url('assets/images/Logo-new.png') ?>" alt="Avenir Holidays — Connecting Cultures" style="height:58px; width:auto; display:block;"><?php endif; ?></td>
<td width="50%" align="right" valign="bottom" style="padding:0 0 4px; border-bottom:2px solid <?= $PUR ?>; vertical-align:bottom;">
    <span style="font-size:<?= $pdf ? '16pt' : '24px' ?>; font-weight:bold; color:#3d0f3d; letter-spacing:.01em;">PAYMENT VOUCHER</span><br>
    <span style="font-size:<?= $pdf ? '9.5pt' : '12.5px' ?>;">Voucher No. <b><?= (int) $v['Vnum'] ?></b> &nbsp;|&nbsp; Payment date <b><?= $payDate ? $dt($payDate) : '—' ?></b><?= $x && $x['parent_pr_id'] ? ' &nbsp;|&nbsp; Balance payment' : '' ?></span>
</td></tr>
</table>

<table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; color:<?= $INK ?>; margin-top:8px;">

<tr><td style="padding-top:7px;">
    <table cellpadding="<?= $cp ?>" cellspacing="0"<?= $pdf ? ' width="100%"' : '' ?>><tr>
        <td<?= $pdf ? ' width="56%"' : '' ?> style="background-color:<?= $cancelled ? '#fde8e8' : ($isFinal ? '#e3f1d6' : '#fff3d6') ?>; color:<?= $cancelled ? '#b3261e' : ($isFinal ? '#3f7a1c' : '#8a6100') ?>; font-size:11.5px; padding:6px 12px;">Payment: <b><?= $cancelled ? ((int) $v['Aflag'] === 3 ? 'Cancelled' : 'Reversed') : ($isFinal ? 'Finalised' : 'Not finalised — ' . $e($d['stage_label'])) ?></b></td>
        <td width="<?= $pdf ? '2%' : '8' ?>"></td>
        <td<?= $pdf ? ' width="42%"' : '' ?> style="background-color:<?= $PSOFT ?>; color:#5a1a5a; font-size:11.5px; padding:6px 12px;">Supplier notification: <b><?= $e($remTxt) ?></b></td>
    </tr></table>
</td></tr>
</table>

<?= $gap ?><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; margin-top:9px;">
<?php if ($pdf): ?><tr><td width="100%" bgcolor="<?= $PUR ?>" style="background-color:<?= $PUR ?>; color:#ffffff; font-size:13pt; font-weight:bold;"><span style="font-size:8pt;">SUPPLIER &nbsp;&nbsp;|&nbsp;&nbsp; </span><?= $e($sup['supplier_name'] ?: $v['S_name']) ?></td></tr><?php else: ?><tr><td width="20%" style="background-color:<?= $PUR ?>; color:#ffffff; font-size:11px; font-weight:bold; letter-spacing:.05em; padding:10px 12px; border-right:1px solid #b77ab7;">SUPPLIER</td>
<td width="80%" style="background-color:<?= $PUR ?>; color:#ffffff; font-size:<?= $pdf ? '13pt' : '18px' ?>; font-weight:bold; padding:8px 14px;"><?= $e($sup['supplier_name'] ?: $v['S_name']) ?></td></tr><?php endif; ?>
</table>

<?= $gap ?><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; margin-top:9px; <?= $box ?>">
<tr>
<td width="36%" style="padding:8px 12px; border-right:1px solid <?= $PLINE ?>;"><div style="font-size:11px; color:#3d3340;"><?= $isFinal ? 'Paid to supplier' : 'Amount to pay' . ($fig && ($fig['stage'] ?? '') === 'provisional' ? ' (provisional)' : '') ?></div><div style="font-size:<?= $big ?>; font-weight:bold;">LKR <?= $m($paidLkr) ?></div></td>
<td width="27%" style="padding:8px 12px; border-right:1px solid <?= $PLINE ?>;"><div style="font-size:11px; color:#3d3340;">Bank charges</div><div style="font-size:<?= $big ?>; font-weight:bold;"><?= $charges !== null ? 'LKR ' . $m($charges) : '—' ?></div></td>
<td width="37%" style="padding:8px 12px; background-color:<?= $PSOFT ?>;"><div style="font-size:11px; font-weight:bold; color:#3d0f3d;">Total bank debit</div><div style="font-size:<?= $big ?>; font-weight:bold;"><?= $totalDebit !== null ? 'LKR ' . $m($totalDebit) : '—' ?></div></td>
</tr></table>

<?= $gap ?><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; margin-top:9px;">
<tr valign="top">
<td width="49%"><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="<?= $box ?>"><?= $sec('Booking details', 2) ?>
    <?= $row('Tour ID', $e(implode(', ', $tours) ?: ($v['ref_po'] ?? ''))) ?>
    <?= $row('Inquiry No.', $inqs ? $e(implode(', ', array_map(static fn ($q) => 'INQ ' . $q, $inqs))) : '') ?>
    <?= $row('Guest / group', $e($t['consignee'] ?? '')) ?>
    <?= $row('Guests', isset($t['passenger_count']) && $t['passenger_count'] !== null && $t['passenger_count'] !== '' ? (string) (int) $t['passenger_count'] : '') ?>
    <?= $row('Tour dates', ($t['start_date'] ?? null) ? $dt($t['start_date']) . ' – ' . $dt($t['end_date'] ?? null) : '') ?>
    <?php $tfStay = ($d['kind'] === 'Hotel' || stripos((string) $d['kind'], 'hotel') !== false) ? $tfDocs->stay((array) $d['service_dates'], $r['start_date'] ?? null, $r['end_date'] ?? null) : null;   // tf-docs-r1: the stay in full
    if ($tfStay): ?><?= $row('Check-in', $e($tfStay['in_text'])) ?><?= $row('Check-out', $e($tfStay['out_text'])) ?><?= $row('Nights', $e((string) $tfStay['nights'])) ?>
    <?php else: ?><?= $row('Service dates', $e($d['service_dates'] ? $tfDocs->dateSpan($d['service_dates']) : '')) ?><?php endif; ?>
    <?= $row('Invoice', $e($x['invoice_no'] ?? ($v['inv'] !== '0' ? $v['inv'] : ''))) ?>
    <?= $row('Booking reference', $e($x['booking_ref'] ?? '')) ?>
</table></td>
<td width="2%"></td>
<td width="49%"><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="<?= $box ?>"><?= $sec('Payment details', 2) ?>
    <?= $row('Approved amount', $approvedAmt) ?>
    <?= $row('Approval rate', $approvalRate) ?>
    <?= $row('Payment rate', $payRate) ?>
    <?= $row('Exchange difference', $fx) ?>
    <?= $row('Batch', $b ? $e($b['batch_no']) . ((int) $b['item_count'] > 1 ? ' <span style="font-weight:normal; color:' . $MUTED . ';">· ' . (int) $b['item_count'] . ' payments · ' . (int) $b['tour_count'] . ' tour(s)</span>' : '') : '') ?>
    <?= $row('Bank reference', $e($bankRef)) ?>
    <?= $row('Service', '<span style="font-weight:normal;">' . $e($d['description']) . '</span>') ?>
</table></td>
</tr></table>

<?= $gap ?><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; margin-top:9px; <?= $box ?>">
<?= $sec('Bank details', 2) ?>
<tr valign="top">
<td width="50%" style="padding:7px 12px; border-right:1px solid <?= $PLINE ?>; font-size:10.5px; line-height:<?= $lh ?>;">
    <div style="font-size:10px; font-weight:bold; color:<?= $PUR ?>; letter-spacing:.04em;">BENEFICIARY</div>
    <b><?= $e($sup['beneficiary_name'] ?: '—') ?></b><br><?= $e(trim($sup['bank_name'] . ($sup['branch'] ? ', ' . $sup['branch'] : '')) ?: '—') ?><br><?= $e($ending($sup['account_no'])) ?><?= trim($sup['currency'] . ($sup['swift'] ? ' · ' . $sup['swift'] : '')) !== '' ? ' · ' . $e(trim($sup['currency'] . ($sup['swift'] ? ' · ' . $sup['swift'] : ''))) : '' ?><br>
    <?= $sup['status'] === 'verified' ? '<span style="color:#3f7a1c;"><b>Verified</b> by ' . $e($sup['verified_by']) . ' on ' . $dt($sup['verified_at'], 'j M Y H:i') . '</span>' : ($sup['status'] === 'changed' ? '<b style="color:#b3261e;">CHANGED since verification — confirm before paying</b>' : '<b style="color:#b3261e;">Not verified — confirm before paying</b>') ?>
</td>
<td width="50%" style="padding:7px 12px; font-size:10.5px; line-height:<?= $lh ?>;">
    <div style="font-size:10px; font-weight:bold; color:<?= $PUR ?>; letter-spacing:.04em;">PAID FROM</div>
    <?php if ($acct): ?><b><?= $e($acct['label']) ?></b><br><?= $e($acct['bank_name']) ?><?= $acct['branch'] ? ', ' . $e($acct['branch']) : '' ?><br><?= $e($ending($acct['account_no'])) ?> · <?= $e($acct['currency']) ?>
    <?php else: ?><b><?= $e($v['Bank'] ?: '—') ?></b><br><span style="color:<?= $MUTED ?>;">ledger <?= $e($v['Bank_acc']) ?></span><?php endif; ?>
</td></tr></table>

<?= $gap ?><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; margin-top:9px; <?= $box ?>">
<?= $sec('Invoice allocation', 4) ?>
<?php $ah = 'style="background-color:' . $PSOFT . '; font-size:9.5px; font-weight:bold; letter-spacing:.04em; text-transform:uppercase; color:#3d0f3d; padding:5px 10px; border-bottom:1px solid ' . $PLINE . ';"'; $ad = 'style="font-size:11px; font-weight:bold; padding:6px 10px; border-bottom:1px solid #ece4ee;"'; ?>
<tr><th <?= $ah ?> align="left" width="22%">Invoice</th><th <?= $ah ?> align="left" width="34%">Tour · Inquiry</th><th <?= $ah ?> align="right" width="22%">Approved amount</th><th <?= $ah ?> align="right" width="22%"><?= $isFinal ? 'Paid amount (LKR)' : 'Amount (LKR)' ?></th></tr>
<?php if ($items): foreach ($items as $k => $it): $ln = $fig['lines'][$k] ?? []; $iq = $inqOf($it['manual_id'] ?? ''); ?>
<tr><td <?= $ad ?>><?= $e($it['invoice_no'] ?: '—') ?></td><td <?= $ad ?>><?= $e($it['manual_id']) ?><?= $iq !== '' ? ' <span style="font-weight:normal; color:' . $MUTED . ';">· INQ ' . $e($iq) . '</span>' : '' ?></td><td <?= $ad ?> align="right"><?= $it['currency'] === 'USD' ? 'USD ' . $m($it['usd_amount']) : 'LKR ' . $m($it['lkr_amount']) ?></td><td <?= $ad ?> align="right">LKR <?= $m(($ln['actual_lkr'] ?? null) !== null ? $ln['actual_lkr'] : $it['approval_lkr']) ?></td></tr>
<?php endforeach; if (count($items) > 1): ?>
<tr><td <?= $ad ?> colspan="2" align="right">Total</td><td <?= $ad ?> align="right"><?= $usd ? 'USD ' . $m($fig['usd_total']) : '' ?></td><td <?= $ad ?> align="right">LKR <?= $m($fig['actual_lkr_total'] ?? $fig['provisional_lkr_total']) ?></td></tr>
<?php endif; elseif (count($d['lines']) > 1): foreach ($d['lines'] as $l): ?>
<tr><td <?= $ad ?>><?= $e($l['Description']) ?></td><td <?= $ad ?>><?= $e($l['manual_id'] ?: $l['Invoice']) ?> <span style="font-weight:normal;"><?= $e($l['consignee']) ?></span></td><td <?= $ad ?> align="right"><?= (float) $l['Rate'] > 0 ? $m($l['Rate']) : '—' ?></td><td <?= $ad ?> align="right">LKR <?= $m($l['Amount']) ?></td></tr>
<?php endforeach; else: ?>
<tr><td <?= $ad ?>><?= $e(($x['invoice_no'] ?? '') ?: ($v['inv'] !== '0' ? $v['inv'] : '—')) ?></td><td <?= $ad ?>><?= $e(implode(', ', $tours)) ?><?= $inqs ? ' <span style="font-weight:normal; color:' . $MUTED . ';">· INQ ' . $e(implode(', ', $inqs)) . '</span>' : '' ?></td><td <?= $ad ?> align="right"><?= $approvedAmt ?></td><td <?= $ad ?> align="right">LKR <?= $m($paidLkr) ?></td></tr>
<?php endif; ?>
</table>

<?= $gap ?><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; margin-top:9px; <?= $box ?>">
<?= $sec('Approval summary', 4) ?>
<tr><th <?= $ah ?> align="center" width="25%">Requested by</th><th <?= $ah ?> align="center" width="25%">Approved by</th><th <?= $ah ?> align="center" width="25%">Bank details recorded by</th><th <?= $ah ?> align="center" width="25%">Finalised by</th></tr>
<?php $sd = 'style="font-size:10.5px; padding:7px 8px; text-align:center; border-right:1px solid #ece4ee;"'; ?>
<tr valign="top"><td <?= $sd ?>><?= $r ? $sig($r['payment_request_user'], $r['payment_request_date']) : ($manyReq ? '<span style="color:' . $MUTED . ';">' . count($link['requests']) . ' requests — see page 2</span>' : $sig('', null)) ?></td>
<td <?= $sd ?>><?= $r && $r['accept_user'] ? $sig($r['accept_user'], $r['accept_date']) : ($manyReq ? '<span style="color:' . $MUTED . ';">see page 2</span>' : $sig('', null)) ?></td>
<td <?= $sd ?>><?= $init ? $sig($init['initiated_by'], $init['initiated_at']) : $sig('', null) ?></td>
<td <?= $sd ?>><?= $app ? $sig($app['approved_by'], $app['approved_at']) : ((int) $v['finalized'] === 1 ? $sig($v['finalized_user'], $v['finalized_date']) : $sig('', null)) ?></td></tr>
</table>

<div style="font-family:<?= $FONT ?>; margin-top:10px; border-top:2px solid <?= $PUR ?>; padding-top:5px; text-align:center; font-size:9.5px; font-style:italic; color:#5a1a5a;">
    Internal payment voucher • Detailed audit history on separate page<?= $isFinal ? '' : '<br><b>Preview — not finalised. This voucher does not confirm a payment.</b>' ?>
</div>

<?php if ($pdf): ?><br pagebreak="true" /><?php else: ?><div style="page-break-before:always; height:18px;"></div><?php endif; ?>

<table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; color:<?= $INK ?>;">
<tr><td style="font-size:15px; font-weight:bold; color:#3d0f3d; padding-bottom:4px; border-bottom:2px solid <?= $PUR ?>;">Payment Voucher No. <?= (int) $v['Vnum'] ?> — detailed audit history</td>
<td align="right" style="font-size:9.5px; color:<?= $MUTED ?>; padding-bottom:4px; border-bottom:2px solid <?= $PUR ?>;"><?= $e($sup['supplier_name'] ?: $v['S_name']) ?> · <?= $e(implode(', ', $tours)) ?><br>Issued <?= $dt($v['C_Date'], 'j M Y H:i') ?> by <?= $e($v['login']) ?></td></tr>
</table>

<?= $gap ?><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; margin-top:9px; <?= $box ?>">
<?= $sec('The steps', 4) ?>
    <tr><th <?= $th ?> width="25%">Step</th><th <?= $th ?> width="15%">When</th><th <?= $th ?> width="18%">Who</th><th <?= $th ?> width="42%">Details</th></tr>
    <tr><td <?= $td ?>>1–3 Requested · awaiting management approval</td><td <?= $td ?>><?= $r ? $dt($r['payment_request_date'], 'j M Y H:i') : '—' ?></td><td <?= $td ?>><?= $e($r['payment_request_user'] ?? '') ?></td><td <?= $td ?>><?= $ra ? $e($ra['label']) . ' · request #' . (int) $r['id'] : ($manyReq ? count($link['requests']) . ' requests (below)' : '<span style="color:' . $MUTED . ';">no request linked</span>') ?></td></tr>
    <tr><td <?= $td ?>>4 Approved for payment</td><td <?= $td ?>><?= $r && $r['accept_date'] ? $dt($r['accept_date'], 'j M Y H:i') : '—' ?></td><td <?= $td ?>><?= $e($r['accept_user'] ?? '') ?></td><td <?= $td ?>>Payment instruction created</td></tr>
    <tr><td <?= $td ?>>5 Payment voucher issued</td><td <?= $td ?>><?= $dt($v['C_Date'], 'j M Y H:i') ?></td><td <?= $td ?>><?= $e($v['login']) ?></td><td <?= $td ?>>Payment Voucher No. <?= (int) $v['Vnum'] ?><?= !empty($d['batch']) ? ' · supplier batch ' . $e($d['batch']['batch_no']) . ' created by ' . $e($d['batch']['created_by']) : '' ?></td></tr>
    <tr><td <?= $td ?>>6 Bank payment recorded — awaiting finalisation</td><td <?= $td ?>><?= $init ? $dt($init['initiated_at'], 'j M Y H:i') : '—' ?></td><td <?= $td ?>><?= $e($init['initiated_by'] ?? '') ?></td><td <?= $td ?>><?= $init ? $e($init['currency']) . ' ' . $m($init['amount']) . ($init['batch_ref'] ? ' · batch ' . $e($init['batch_ref']) : '') . ($init['bank_ref'] ? ' · ref ' . $e($init['bank_ref']) : '') . ($init['charges'] !== null ? ' · charges ' . $m($init['charges']) : '') . (!empty($init['payment_date']) ? ' · payment date ' . $dt($init['payment_date']) : '') . (!empty($init['usd_rate']) ? ' · rate ' . number_format((float) $init['usd_rate'], 2) : '') . (!empty($init['total_debit']) ? ' · total debit ' . $m($init['total_debit']) : '') . ($init['note'] ? ' · ' . $e($init['note']) : '') : '' ?></td></tr>
    <tr><td <?= $td ?>>7 Paid — finalised</td><td <?= $td ?>><?= $app ? $dt($app['approved_at'], 'j M Y H:i') : ((int) $v['finalized'] === 1 ? $dt($v['finalized_date'], 'j M Y H:i') : '—') ?></td><td <?= $td ?>><?= $e($app['approved_by'] ?? ((int) $v['finalized'] === 1 ? $v['finalized_user'] : '')) ?></td><td <?= $td ?>><?= $app ? 'Paid ' . $dt($app['paid_date']) . ' · LKR ' . $m($app['lkr_amount']) . ($app['usd_rate'] ? ' (USD ' . $m($app['usd_amount']) . ' @ ' . number_format((float) $app['usd_rate'], 2) . ')' : '') . (isset($app['fx_diff']) && $app['fx_diff'] !== null ? ' · difference ' . ($app['fx_diff'] > 0 ? '+' : '') . $m($app['fx_diff']) : '') . (isset($app['charges']) && $app['charges'] !== null ? ' · charges ' . $m($app['charges']) . ' · total debit ' . $m($app['total_debit']) : '') . ' · ref ' . $e($app['bank_ref']) . ' · <b>locked</b>' : ((int) $v['finalized'] === 1 ? 'Bank date ' . $dt($v['bank_date']) : '') ?></td></tr>
    <tr><td <?= $td ?>>8–9 Supplier notification</td><td <?= $td ?>><?= $rem && $rem['sent_at'] ? $dt($rem['sent_at'], 'j M Y H:i') : '—' ?></td><td <?= $td ?>><?= $e($rem['sent_by'] ?? '') ?></td><td <?= $td ?>><?= $rem ? ($rem['status'] === 'sent' ? 'Remittance advice sent to ' . $e($rem['to_email']) : $e($tfRemLabel[$rem['status']] ?? 'Not prepared') . ($rem['status'] === 'prepared' && !empty($rem['prepared_by']) ? ' · prepared by ' . $e($rem['prepared_by']) . ' ' . $dt($rem['prepared_at'] ?? null, 'j M Y H:i') : '') . ($rem['status'] === 'failed' && !empty($rem['error']) ? ' · ' . $e($rem['error']) : '')) : '' ?></td></tr>
    <?php if ($cancelled): ?><tr><td <?= $td ?> colspan="4" style="color:#b23a32;"><b><?= (int) $v['Aflag'] === 3 ? 'Cancelled' : 'Reversed' ?>:</b> voucher <?= (int) $v['Aflag'] === 3 ? 'cancelled on the Voucher Cancellation screen' : 'rejected' ?><?= $v['remark'] ? ' — ' . $e($v['remark']) : '' ?><?= $v['Aut_user'] ? ' by ' . $e($v['Aut_user']) : '' ?></td></tr><?php endif; ?>
</table>

<?= $gap ?><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; margin-top:9px; <?= $box ?>">
<?= $sec('Figures and links on record', 2) ?>
    <?= $kv('Payment request', $linkHtml) ?>
    <?php if ($b && $fig): ?>
    <?= $kv('Provisional LKR (approval)', 'LKR ' . $m($fig['provisional_lkr_total'])) ?>
    <?= $kv($fig['final'] ? 'Final LKR amount (locked)' : 'LKR amount to pay', 'LKR ' . $m($fig['actual_lkr_total'] ?? $amountLkr) . ($fig['stage'] === 'provisional' ? ' <span style="color:' . $MUTED . '; font-size:9px;">provisional — final at the payment-date rate</span>' : '')) ?>
    <?php if ($usd && $fig['fx_diff'] !== null): ?><?= $kv('Exchange-rate difference', ($fig['fx_diff'] > 0 ? '+ ' : '− ') . $m(abs((float) $fig['fx_diff'])) . ($fig['fx_diff'] > 0 ? ' loss' : ($fig['fx_diff'] < 0 ? ' gain' : '')) . ' <span style="color:' . $MUTED . '; font-size:9px;">final − provisional, recorded separately</span>') ?><?php endif; ?>
    <?php elseif ($usd): ?>
    <?= $kv('Exchange rate', '1 USD = LKR ' . number_format((float) $d['usd_rate'], 2) . ' <span style="color:' . $MUTED . '; font-size:9px;">locked when requested' . (!empty($x['rate_date']) ? ' · ' . $dt($x['rate_date']) : '') . '</span>') ?>
    <?php endif; ?>
    <?php if ($app && $app['charges'] !== null): ?><?= $kv('Bank charges · total debited', 'LKR ' . $m($app['charges']) . ' · <b>LKR ' . $m($app['total_debit']) . '</b>' . ((float) $app['charges'] > 0 ? ' <span style="color:' . $MUTED . '; font-size:9px;">' . ((int) ($app['charges_posted'] ?? 0) === 1 ? 'charges posted once to the Bank Charges account, per tour line' : 'charges NOT posted') . '</span>' : '')) ?><?php endif; ?>
    <?php if ($app && isset($app['reconciled'])): ?><?= $kv('Ledger reconciliation', (int) $app['reconciled'] === 1 ? '<b style="color:#1f8a4c;">reconciled</b> — credits to the paying account LKR ' . $m($app['ledger_bank_credit']) . ' = the bank debit' : '<b style="color:#b23a32;">not reconciled</b> — credits to the paying account LKR ' . $m($app['ledger_bank_credit']) . ' against a bank debit of ' . $m($app['total_debit'])) ?><?php endif; ?>
    <?php $tfV = $e(trim((string) ($t['agent_name'] ?? ''))); if (!$pdf || !in_array(trim((string) $tfV), ['', '—'], true)): ?><?= $kv('Agent', $tfV) ?><?php endif; ?>
    <?php $tfV = $dt($x['due_date'] ?? ($r['start_date'] ?? null)); if (!$pdf || !in_array(trim((string) $tfV), ['', '—'], true)): ?><?= $kv('Check-in date', $tfV) ?><?php endif; ?>
    <?php $tfV = $e($sup['remittance_email']); if (!$pdf || !in_array(trim((string) $tfV), ['', '—'], true)): ?><?= $kv('Remittance e-mail', $tfV) ?><?php endif; ?>
    <?php $tfV = $e($sup['invoice_email']); if (!$pdf || !in_array(trim((string) $tfV), ['', '—'], true)): ?><?= $kv('Invoice e-mail', $tfV) ?><?php endif; ?>
    <?php $tfV = nl2br($e($x['note'] ?? '')); if (!$pdf || !in_array(trim((string) $tfV), ['', '—'], true)): ?><?= $kv('Internal notes', $tfV) ?><?php endif; ?>
</table>

<?php if ($items): if ($pdf) { $td = str_replace('font-size:10.5px', 'font-size:7.5pt', $td); } ?>
<?= $gap ?><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; margin-top:9px; <?= $box ?>">
<?= $sec('Payments in batch ' . $e($b['batch_no']) . ' — approval figures and payment-date figures', 9) ?>
    <tr><th <?= $th ?> width="15%">Tour · guest</th><th <?= $th ?> width="12%">Service · dates</th><th <?= $th ?> width="9%">Invoice</th><th <?= $th ?> align="right" width="11%">Locked amount</th><th <?= $th ?> align="right" width="7%">Appr. rate</th><th <?= $th ?> align="right" width="12%">Provisional LKR</th><th <?= $th ?> align="right" width="11%"><?= $fig['final'] ? 'Final LKR' : 'LKR' ?></th><th <?= $th ?> align="right" width="10%">Difference</th><th <?= $th ?> width="13%">Approved</th></tr>
    <?php foreach ($items as $k => $it): $ln = $fig['lines'][$k]; ?><tr><td <?= $td ?>><b><?= $e($it['manual_id']) ?></b> <?= $e($it['guest']) ?></td><td <?= $td ?>><?= $e($it['kind']) ?><?= $it['service_dates'] ? ' · ' . $e($tfDocs->dateSpan($it['service_dates'])) : '' ?></td><td <?= $td ?>><?= $e($it['invoice_no'] ?: '—') ?></td><td <?= $td ?> align="right"><?= $it['currency'] === 'USD' ? 'USD ' . $m($it['usd_amount']) : 'LKR ' . $m($it['lkr_amount']) ?></td><td <?= $td ?> align="right"><?= $it['currency'] === 'USD' ? number_format((float) $it['approval_rate'], 2) : '—' ?></td><td <?= $td ?> align="right"><?= $m($it['approval_lkr']) ?></td><td <?= $td ?> align="right"><?= $ln['actual_lkr'] !== null ? $m($ln['actual_lkr']) : '—' ?></td><td <?= $td ?> align="right"><?= $ln['fx_diff'] !== null ? ($ln['fx_diff'] > 0 ? '+' : '') . $m($ln['fx_diff']) : '—' ?><?= isset($it['charge_share']) && $it['charge_share'] !== null ? '<br><span style="font-size:9px; color:' . $MUTED . ';">charges ' . $m($it['charge_share']) . '</span>' : '' ?></td><td <?= str_replace('color:' . $INK, 'color:' . $MUTED, $td) ?>><?= $e($it['approved_by']) ?> · <?= $dt($it['approved_at'], 'j M Y H:i') ?></td></tr><?php endforeach; ?>
</table>
<?php endif; ?>

<?= $gap ?><table cellpadding="<?= $cp ?>" cellspacing="0" width="100%" style="font-family:<?= $FONT ?>; margin-top:9px; <?= $box ?>">
<?= $sec('Supporting documents', 4) ?>
    <?php if (!$docsList): ?><tr><td <?= $td ?> style="color:<?= $MUTED ?>;">None attached yet.</td></tr><?php endif; ?>
    <?php foreach ($docsList as $doc): ?><tr><td <?= $td ?>><?= $e(ucfirst(str_replace('_', ' ', $doc['kind']))) ?></td><td <?= $td ?>><?= $e($doc['title']) ?><?= $doc['number'] ? ' · ' . $e($doc['number']) : '' ?></td><td <?= $td ?>><?= $doc['file_path'] ? ($pdf ? 'file attached' : '<a href="' . base_url('tour/tour_payment_dashboard/file/' . (int) $doc['id']) . '" target="_blank">open</a>') : 'number only' ?></td><td <?= $td ?> style="color:<?= $MUTED ?>;"><?= $e($doc['uploaded_by']) ?> · <?= $dt($doc['uploaded_at'], 'j M Y H:i') ?></td></tr><?php endforeach; ?>
</table>

<div style="font-family:<?= $FONT ?>; margin-top:12px; font-size:9px; color:<?= $MUTED ?>; border-top:1px solid <?= $PLINE ?>; padding-top:5px;">
    Payment Voucher No. <?= (int) $v['Vnum'] ?> · <?= $e(implode(', ', $tours)) ?> · <?= $e($company['name']) ?> · printed <?= date('j M Y H:i') ?>. The management approval authorises the accountant to record the bank payment; only the bank approver's finalisation marks it paid.
</div>
<?php if (!$pdf): ?></body></html><?php endif; ?>
