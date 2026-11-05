<?php
/**
 * The restaurant booking voucher for one lunch or dinner of the Meal Master on a confirmed tour (schema 40).
 *
 * It goes to the restaurant when the tour is confirmed (and from the Activities desk) to book the meal; the restaurant
 * confirms it by return email and bills the office against its number. One design, three uses ($mode):
 *   page   the page to print from the browser (the logo embedded, the CANCELLED stamp on a cancelled one)
 *   email  the email body — plain tables and inline styles every mail client shows the same way, kept on the voucher
 *   pdf    the same tables for the PDF that goes with the email (TCPDF), the logo from its file
 * Everything is read from the voucher row as it was issued (MealMaster::voucher): the restaurant and its contact, the
 * client, the tour, the guide when one is allocated, the day and the time, the adults and children, the Meal Master's
 * actual rates in LKR and the amount — never converted from USD — so a later change never moves a voucher already sent.
 *
 *   $voucher    the voucher row joined to the tour, with supplier, address, description, client, guide, company
 *   $mode       page | email | pdf  (older callers: $email true = email)
 *   $message    the opening as the email has it (from the ready-made email, edited), or null for the standard one
 *   $sender     the signed-in person's name, phone and email (Staff::contact) — the contact person
 *   $logo       the logo's URL for the email, or null
 *   $logo_file  the logo's file (page: embedded; pdf: drawn from it), or null
 *   $user       who is printing
 */
$v    = $voucher;
$co   = $v['company'] ?? ($company ?? []);
$who  = $sender ?? [];
$mode = $mode ?? (!empty($email) ? 'email' : 'page');
$pdf  = $mode === 'pdf';
$e    = static fn ($x): string => esc((string) ($x ?? ''));
$fmt  = static function (?string $ymd): string {
    if (!$ymd) {
        return '—';
    }
    $ts = strtotime($ymd);
    return $ts ? date('l, d F Y', $ts) : $ymd;
};
$money = static fn ($n): string => number_format((float) $n, 2);
$fs    = static fn (float $px): string => $pdf ? round($px * 0.75, 1) . 'pt' : $px . 'px';   // sizes: px on screen and in mail, pt in the PDF
$P = '#842484';   // Avenir purple
$M = '#e90c8b';   // Avenir magenta
$INK = '#1f2430';
$MUTED = '#6b6475';
$LINE = '#e7dfe9';
$SOFT = '#f8f1f7';
$cancelled = (string) ($v['state'] ?? '') === 'cancelled';
$rest  = trim((string) $v['restaurant']);
$a     = (int) ($v['adults'] ?? 0);
$c     = (int) ($v['children'] ?? 0);
$type  = (string) ($v['type_name'] ?? ucfirst((string) $v['meal_type']));
$childRate = $v['child_lkr'] === null ? $v['adult_lkr'] : $v['child_lkr'];
$pct   = rtrim(rtrim(number_format((float) ($v['tax_pct'] ?? 0), 2), '0'), '.');
$sum   = $a . ' adult' . ($a === 1 ? '' : 's') . ' × LKR ' . $money($v['adult_lkr']) . ($c ? '<br>' . $c . ' child' . ($c === 1 ? '' : 'ren') . ((float) $childRate == 0.0 ? ' — free' : ' × LKR ' . $money($childRate)) : '')
       . ((float) ($v['tax_lkr'] ?? 0) > 0 ? '<br>Taxes ' . $pct . '% LKR ' . $money($v['tax_lkr']) : (!empty($v['tax_included']) && (float) ($v['tax_pct'] ?? 0) > 0 ? '<br>Taxes (' . $pct . '%) included' : ''));
$place = $rest . ($v['location'] && stripos($rest, (string) $v['location']) === false ? ', ' . $v['location'] : '');
$guests = $a . ' adult' . ($a === 1 ? '' : 's') . ($c ? ' and ' . $c . ' child' . ($c === 1 ? '' : 'ren') : '') . '  (' . ($a + $c) . ' pax)';
$rows = [
    ['Restaurant', $place],
    ['Date', $fmt($v['on_date'])],
    ['Time', $v['meal_time'] ?: 'as arranged'],
    ['Meal', $type . (!empty($v['description']) ? ' — ' . $v['description'] : '')],
    ['Guests', $guests],
    ['Client name', $v['client'] ?? ($v['tour_name'] ?: '—')],
    ['Tour reference', trim(($v['tour_no'] ?? '') . (!empty($v['agent_name']) ? '  ·  ' . $v['agent_name'] : ''))],
    ['Guide', $v['guide'] ?? 'To be advised before the tour'],
];
if (!empty($v['special_note'])) {
    $rows[] = ['Special requests', $v['special_note']];
}
$toLines = array_values(array_filter([
    $v['address'] ?? null,
    !empty($v['contact']) ? 'Contact: ' . $v['contact'] : null,
    !empty($v['to_email']) ? $v['to_email'] : null,
]));
$coLines = array_values(array_filter([$co['address'] ?? null, trim(implode('  ·  ', array_filter([!empty($co['phone']) ? 'Tel ' . $co['phone'] : null, !empty($co['email']) ? $co['email'] : null])))]));
$contact = trim(implode('  ·  ', array_filter([$who['name'] ?? null, !empty($who['phone']) ? 'Mobile ' . $who['phone'] : null, $who['email'] ?? null])));
$intro = 'Greetings from ' . (($co['name'] ?? '') ?: 'Avenir Holidays') . '. Kindly reserve the ' . strtolower($type) . ' below for our guests and confirm this voucher by return email, quoting the voucher number.';
// the logo: the URL in the email, the file in the PDF, embedded on the page
$logoTag = '';
if ($pdf && !empty($logo_file) && is_file($logo_file)) {
    $logoTag = '<img src="' . $logo_file . '" height="44">';
} elseif ($mode === 'page' && !empty($logo_file) && is_file($logo_file)) {
    $logoTag = '<img src="data:image/png;base64,' . base64_encode((string) file_get_contents($logo_file)) . '" style="height:58px;width:auto" alt="' . $e($co['name'] ?? '') . '">';
} elseif ($mode === 'email' && !empty($logo)) {
    $logoTag = '<img src="' . $e($logo) . '" style="height:52px;width:auto" alt="' . $e($co['name'] ?? '') . '">';
}
$font = $pdf ? 'helvetica' : "'Segoe UI',Helvetica,Arial,sans-serif";
$opening = !empty($message) ? (preg_split('/\n{2,}/', str_replace("\r", '', trim((string) $message))) ?: []) : ['Dear ' . $rest . ',', $intro];

// ---- the voucher itself: tables and inline styles, the same in the mail, the PDF and on the page ----
ob_start();
?>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family:<?= $font ?>;color:<?= $INK ?>">
  <tr>
    <td width="50%" valign="middle"><?= $logoTag !== '' ? $logoTag : '<span style="font-size:' . $fs(20) . ';font-weight:bold;color:' . $P . '">' . $e(($co['name'] ?? '') ?: 'Avenir Holidays') . '</span>' ?></td>
    <td width="50%" align="right" valign="middle" style="text-align:right">
      <span style="font-size:<?= $fs(10.5) ?>;font-weight:bold;color:<?= $M ?>;letter-spacing:1px">RESTAURANT BOOKING VOUCHER</span><br>
      <span style="font-size:<?= $fs(24) ?>;font-weight:bold;color:<?= $P ?>"><?= $e($v['voucher_no']) ?></span><br>
      <span style="font-size:<?= $fs(11) ?>;color:<?= $MUTED ?>">Issued <?= $e(date('d M Y', strtotime((string) $v['issued_at']))) ?></span>
    </td>
  </tr>
  <tr><td colspan="2" style="font-size:<?= $fs(4) ?>;line-height:<?= $fs(4) ?>">&nbsp;</td></tr>
  <tr><td colspan="2" bgcolor="<?= $P ?>" style="background:<?= $P ?>;font-size:<?= $fs(3) ?>;line-height:<?= $fs(3) ?>;height:3px">&nbsp;</td></tr>
  <tr><td colspan="2" style="font-size:<?= $fs(10) ?>;line-height:<?= $fs(10) ?>">&nbsp;</td></tr>
  <tr>
    <td width="58%" valign="top" style="font-size:<?= $fs(12.5) ?>;line-height:1.5">
      <span style="font-size:<?= $fs(10) ?>;font-weight:bold;color:<?= $MUTED ?>;letter-spacing:1px">TO</span><br>
      <span style="font-size:<?= $fs(15) ?>;font-weight:bold"><?= $e($rest) ?></span><br>
      <span style="color:<?= $MUTED ?>"><?= implode('<br>', array_map($e, $toLines)) ?></span>
    </td>
    <td width="42%" valign="top" align="right" style="text-align:right;font-size:<?= $fs(12.5) ?>;line-height:1.5">
      <span style="font-size:<?= $fs(10) ?>;font-weight:bold;color:<?= $MUTED ?>;letter-spacing:1px">TOUR</span><br>
      <span style="font-size:<?= $fs(15) ?>;font-weight:bold"><?= $e($v['tour_no']) ?></span><br>
      <span style="color:<?= $MUTED ?>"><?= $e($v['tour_name'] ?: '') ?></span>
    </td>
  </tr>
  <tr><td colspan="2" style="font-size:<?= $fs(10) ?>;line-height:<?= $fs(10) ?>">&nbsp;</td></tr>
  <tr><td colspan="2" style="font-size:<?= $fs(13) ?>;line-height:1.55">
    <?php if ($pdf): ?><?= implode('<br><br>', array_map(static fn ($p) => nl2br($e($p)), $opening)) ?><br><?php else: ?><?php foreach ($opening as $para): ?><p style="margin:0 0 10px 0"><?= nl2br($e($para)) ?></p><?php endforeach; ?><?php endif; ?>
  </td></tr>
  <tr><td colspan="2">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td width="62%" valign="top" style="border:1px solid <?= $LINE ?>">
          <table width="100%" cellpadding="<?= $pdf ? '5' : '0' ?>" cellspacing="0" border="0">
            <tr><td colspan="2" bgcolor="<?= $SOFT ?>" style="background:<?= $SOFT ?>;padding:9px 14px;font-size:<?= $fs(10.5) ?>;font-weight:bold;color:<?= $P ?>;letter-spacing:1px;border-bottom:1px solid <?= $LINE ?>">RESERVATION</td></tr>
            <?php foreach ($rows as $i => [$k, $val]): ?>
            <tr>
              <td width="34%" valign="top" style="padding:8px 14px;font-size:<?= $fs(11.5) ?>;color:<?= $MUTED ?>;<?= $i ? 'border-top:1px solid ' . $LINE : '' ?>"><?= $e($k) ?></td>
              <td width="66%" valign="top" style="padding:8px 14px 8px 0;font-size:<?= $fs(13) ?>;font-weight:bold;<?= $i ? 'border-top:1px solid ' . $LINE : '' ?>"><?= nl2br($e($val)) ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </td>
        <td width="3%">&nbsp;</td>
        <td width="35%" valign="top" bgcolor="<?= $P ?>" style="background:<?= $P ?>;color:#ffffff;padding:16px 16px">
          <table width="100%" cellpadding="<?= $pdf ? '6' : '0' ?>" cellspacing="0" border="0">
            <tr><td style="color:#f3d7ee;font-size:<?= $fs(10.5) ?>;font-weight:bold;letter-spacing:1px">AMOUNT</td></tr>
            <tr><td style="color:#ffffff;font-size:<?= $fs(24) ?>;font-weight:bold;padding:4px 0 2px 0">LKR <?= $e($money($v['amount_lkr'])) ?></td></tr>
            <tr><td style="color:#f3d7ee;font-size:<?= $fs(11.5) ?>;line-height:1.6;padding-bottom:10px"><?= $sum ?></td></tr>
            <tr><td style="color:#ffffff;font-size:<?= $fs(12) ?>;font-weight:bold;border-top:1px solid #a54fa5;padding-top:10px">Payment by <?= $e(($co['name'] ?? '') ?: 'Avenir Holidays') ?></td></tr>
            <tr><td style="color:#f3d7ee;font-size:<?= $fs(11) ?>;line-height:1.5;padding-top:4px">Please bill our office quoting the voucher number. The guests are not to be charged for the meal.</td></tr>
          </table>
        </td>
      </tr>
    </table>
  </td></tr>
  <tr><td colspan="2" style="font-size:<?= $fs(12) ?>;line-height:<?= $fs(12) ?>">&nbsp;</td></tr>
  <?php $ask = '<b>Kindly confirm this reservation</b> by return email, quoting voucher <b>' . $e($v['voucher_no']) . '</b>.' . ($contact !== '' ? '<br>Contact person: <b>' . $e($contact) . '</b>' : ''); ?>
  <?php if ($pdf): /* TCPDF keeps no cell padding beside a border: the magenta edge is a narrow cell of its own */ ?>
  <tr><td colspan="2"><table width="100%" cellpadding="7" cellspacing="0" border="0"><tr><td width="1%" bgcolor="<?= $M ?>"></td><td width="99%" bgcolor="<?= $SOFT ?>" style="font-size:<?= $fs(12.5) ?>;line-height:1.5"><?= $ask ?></td></tr></table></td></tr>
  <?php else: ?>
  <tr><td colspan="2" bgcolor="<?= $SOFT ?>" style="background:<?= $SOFT ?>;border-left:4px solid <?= $M ?>;padding:11px 14px;font-size:<?= $fs(12.5) ?>;line-height:1.5">
    <?= $ask ?>
  </td></tr>
  <?php endif; ?>
  <tr><td colspan="2" style="font-size:<?= $fs(10) ?>;line-height:<?= $fs(10) ?>">&nbsp;</td></tr>
  <tr><td colspan="2" style="font-size:<?= $fs(12.5) ?>">Thank you for your kind cooperation.</td></tr>
  <tr><td colspan="2" style="font-size:<?= $fs(18) ?>;line-height:<?= $fs(18) ?>">&nbsp;</td></tr>
  <tr><td colspan="2" align="center" style="text-align:center;border-top:1px solid <?= $LINE ?>;padding-top:10px;font-size:<?= $fs(11) ?>;line-height:1.55;color:<?= $MUTED ?>">
    <span style="font-weight:bold;color:<?= $P ?>"><?= $e(($co['name'] ?? '') ?: 'Avenir Holidays') ?></span><br><?= implode('<br>', array_map($e, $coLines)) ?>
  </td></tr>
</table>
<?php
$sheet = (string) ob_get_clean();

if ($mode === 'email'):
?>
<div style="max-width:700px;margin:0 auto;padding:6px 4px"><?= $sheet ?></div>
<?php
    return;
endif;
if ($pdf):
    // TCPDF prints the line break between two tags as a space at the start of the next line: none between tags
    echo preg_replace(['/>\s+</', '/(<td[^>]*>)\s+/', '/\s+(<\/td>)/'], ['><', '$1', '$1'], $sheet);
    return;
endif;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($v['voucher_no']) ?> — restaurant booking voucher<?= $cancelled ? ' (cancelled)' : '' ?></title>
<style>
    body { margin:0; background:#efeaf1; color:<?= $INK ?>; font:13px/1.45 "Segoe UI", Helvetica, Arial, sans-serif; }
    .bar { display:flex; justify-content:space-between; gap:12px; align-items:center; margin:16px auto 8px; max-width:820px; padding:0 4px; }
    .bar .help { color:<?= $MUTED ?>; font-size:12px; }
    .bar button { font:inherit; padding:7px 16px; border-radius:999px; border:1px solid <?= $P ?>; color:<?= $P ?>; background:#fff; cursor:pointer; font-weight:600; }
    .sheet { position:relative; background:#fff; max-width:820px; margin:0 auto 24px; padding:36px 42px 30px; border-radius:14px; box-shadow:0 2px 6px rgba(60,20,70,.06), 0 12px 32px rgba(60,20,70,.08); overflow:hidden; }
    .sheet:before { content:""; position:absolute; left:0; top:0; right:0; height:6px; background:linear-gradient(90deg, <?= $P ?>, <?= $M ?>); }
    .stamp { position:absolute; top:42%; left:50%; transform:translate(-50%,-50%) rotate(-20deg); font-size:68px; font-weight:800; letter-spacing:.18em; color:#b23a32; opacity:.16; border:6px solid #b23a32; padding:6px 24px; border-radius:14px; pointer-events:none; }
    .meta { margin-top:14px; color:<?= $MUTED ?>; font-size:11px; text-align:right; }
    @media print { body { background:#fff; } .bar { display:none; } .sheet { box-shadow:none; border-radius:0; margin:0; max-width:none; padding:10mm 12mm; } }
    @media (max-width:640px) { .sheet { padding:24px 16px; border-radius:0; } }
</style>
</head>
<body>
<div class="bar">
    <span class="help">The booking voucher for <?= $e($rest) ?>, as it goes to the restaurant.<?= $cancelled ? ' <b style="color:#b23a32">This voucher is cancelled' . (!empty($v['cancel_note']) ? ' — ' . $e($v['cancel_note']) : '') . '.</b>' : '' ?></span>
    <button type="button" onclick="window.print()">Print</button>
</div>
<div class="sheet">
    <?php if ($cancelled): ?><div class="stamp">CANCELLED</div><?php endif; ?>
    <?= $sheet ?>
    <div class="meta">Issued <?= $e(date('d M Y H:i', strtotime((string) $v['issued_at']))) ?><?= !empty($v['issued_by']) ? ' by ' . $e($v['issued_by']) : '' ?><?= !empty($v['sent_at']) ? ' · sent ' . $e(date('d M Y H:i', strtotime((string) $v['sent_at']))) . (!empty($v['to_email']) && (string) $v['mail_status'] === 'sent' ? ' to ' . $e($v['to_email']) : '') : '' ?> · printed <?= $e(date('d M Y H:i')) ?><?= !empty($user) ? ' by ' . $e($user) : '' ?></div>
</div>
</body>
</html>
