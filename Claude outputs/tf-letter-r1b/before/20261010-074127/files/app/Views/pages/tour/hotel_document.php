<?php
/**
 * The Hotel Desk's letter to a hotel: the booking request, and later the voucher.
 *
 * One template, two uses. As a page ($email false) it prints from the browser — the
 * Avenir header, the reference block, the stay, the party, the remarks, the company's
 * lines and a signature. As an email body ($email true) the same content is drawn in
 * the plain table style every mail client shows the same way, ready to send through the
 * signed-in user's own mailbox and to be kept, untouched, on the stay.
 *
 * Everything on it is read from the one record (TourService::hotelDocument): the stay as
 * the desk holds it, the hotel's supplier record, the company's lines from settings, the
 * sender's from their account. Nothing typed here, and no price unless a group holding
 * Hotel Rates asked for the published contract rate to go on the request ($rate).
 *
 *   $doc      the document data (kind, reference, company, sender, hotel, tour, stay, passengers, subject)
 *   $email    true for the email body only
 *   $rate     not used since tf-letter-r1: a letter to a hotel never carries a rate figure (confidential)
 *   $rate_basis  what the letter says about the rate instead: "As per agreed contract rates", or the tactical /
 *             early bird offer applied to the stay (HotelLetter::rateBasis)
 *   $remarks  what the desk added for this letter, or null
 *   $message  the opening as the desk wrote it (from a ready-made email, edited), in place of the fixed greeting and intro; or null
 *   $logo     the URL of the company logo, or null
 */
$d       = $doc;
$kind    = $d['kind'] ?? 'request';
$isV     = $kind === 'voucher';
$s       = $d['stay'];
$t       = $d['tour'];
$h       = $d['hotel'];
$co      = $d['company'];
$who     = $d['sender'];
$e       = static fn ($v): string => esc((string) ($v ?? ''));
$fmt     = static function (?string $ymd, bool $long = true): string {
    if (!$ymd) {
        return '—';
    }
    $ts = strtotime($ymd);
    return $ts ? date($long ? 'D, d M Y' : 'd M Y', $ts) : $ymd;
};
$meals   = ['RO' => 'Room only', 'BB' => 'Bed & breakfast', 'HB' => 'Half board', 'FB' => 'Full board', 'AI' => 'All inclusive'];
$meal    = $s['meal_plan'] ? ($s['meal_plan'] . ' — ' . ($meals[$s['meal_plan']] ?? $s['meal_plan'])) : 'not stated';
$nights  = (int) $s['nights'];
$party   = $s['pax'] !== null ? (int) $s['pax'] : ($t['pax_total'] !== null ? (int) $t['pax_total'] : null);
$partyTx = $party === null ? '—' : $party . ' pax' . ($t['pax_adults'] !== null || $t['pax_children'] !== null ? ' (' . (int) $t['pax_adults'] . ' adult' . ((int) $t['pax_adults'] === 1 ? '' : 's') . ((int) $t['pax_children'] > 0 ? ', ' . (int) $t['pax_children'] . ' child' . ((int) $t['pax_children'] === 1 ? '' : 'ren') : '') . ')' : '');
$rooms   = trim(($s['rooms'] !== null ? (int) $s['rooms'] . ' room' . ((int) $s['rooms'] === 1 ? '' : 's') : '') . ($s['rooms_note'] ? ' — ' . $s['rooms_note'] : ''));
$remarkLines = array_values(array_filter([
    $s['special_note'] ? trim((string) $s['special_note']) : null,
    $s['crew_rooms'] ? 'Crew: ' . trim((string) $s['crew_rooms']) : null,
    !empty($remarks) ? trim((string) $remarks) : null,
]));
$rows = [
    [$isV ? 'Voucher number' : 'Avenir reference', $isV ? ($s['voucher_no'] ?: '—') : $d['reference']],
];
if ($isV) {
    $rows[] = ['Avenir reference', $d['reference']];
    $rows[] = ['Hotel confirmation', trim(($s['confirmation_ref'] ?: '—') . ($s['confirmed_with'] ? ' (' . $s['confirmed_with'] . ')' : ''))];
}
$rows = array_merge($rows, [
    ['Hotel', trim(($h['name'] ?: 'not named') . ($h['acc'] ? '  ·  ' . $h['acc'] : ''))],
    ['Guest / group', trim(($t['tour_name'] ?: '—') . ($t['agent_name'] ? '  ·  via ' . $t['agent_name'] : '') . ($t['market_code'] ? ' (' . $t['market_code'] . ')' : ''))],
    ['Check-in', $fmt($s['check_in'])],
    ['Check-out', $fmt($s['check_out'])],
    ['Nights', $nights . ' night' . ($nights === 1 ? '' : 's')],
    ['Pax', $partyTx],
    ['Rooms / room types', $rooms !== '' ? $rooms : '—'],
    ['Meal plan', $meal],
]);
$paxNames = array_values(array_filter(array_map(static fn ($p) => trim((string) ($p['full_name'] ?? '')) . (!empty($p['room_type']) ? ' (' . $p['room_type'] . ')' : ''), $d['passengers'] ?? []), static fn ($x) => trim($x, ' ()') !== ''));
// tf-letter-r1: never a figure — the basis of the rate, in words
$basis = trim((string) ($rate_basis ?? '')) !== '' ? (string) $rate_basis : 'As per agreed contract rates';
$contactBits = array_values(array_filter([$co['address'] ?? null, $co['phone'] ? 'Tel ' . $co['phone'] : null, $co['email'] ? 'Email ' . $co['email'] : null]));
$senderBits  = array_values(array_filter([$who['name'] ?? null, $who['phone'] ? 'Mobile ' . $who['phone'] : null, $who['email'] ?? null]));
$title = $isV ? 'HOTEL VOUCHER' : 'HOTEL BOOKING REQUEST';
$intro = $isV
    ? 'Please accept this voucher for the accommodation below, confirmed by your reservations department under the reference shown. Kindly settle the account with our office as agreed; the guests are not to be charged for the services listed.'
    : 'Greetings from ' . ($co['name'] ?: 'Avenir Holidays') . '. Kindly reserve the accommodation below and send us your confirmation number at your earliest convenience.';
$hotelContact = array_values(array_filter([$h['address'] ?? null, $h['phone'] ? 'Tel ' . $h['phone'] : null, $h['mobile'] ? 'Mobile ' . $h['mobile'] : null, $h['email'] ? 'Email ' . $h['email'] : null, $h['contact'] ? 'Attn ' . $h['contact'] : null]));

if (!empty($email)):
    // ---- the email body (tf-letter-r1): a letterhead and tables with inline styles only — what every mail client
    // (Gmail, Outlook, phones) shows the same way. The logo is the site's own file: the sender embeds it in the email.
    $font = 'font-family:Arial,Helvetica,sans-serif;';
    $lab  = $font . 'width:34%;padding:8px 12px;border-bottom:1px solid #e3e6eb;background:#f7f5f9;font-size:13px;font-weight:bold;color:#3d2a4a;vertical-align:top';
    $val  = $font . 'padding:8px 12px;border-bottom:1px solid #e3e6eb;font-size:13px;color:#1d2733;vertical-align:top';
    $sec  = $font . 'padding:16px 0 6px;font-size:11px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#842484';
    $row  = static fn (string $k, string $vHtml): string => '<tr><td style="' . $lab . '">' . esc($k) . '</td><td style="' . $val . '">' . $vHtml . '</td></tr>';
    $detail = [];
    foreach ($rows as [$k, $v]) {
        if ($k === 'Avenir reference' || $k === 'Voucher number') {
            continue;   // in the title block
        }
        $detail[] = $row($k, $e($v));
    }
    $ref = $isV ? 'Voucher ' . ($s['voucher_no'] ?: '—') : $d['reference'];
?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f2f3f6;margin:0;padding:0">
<tr><td align="center" style="padding:18px 8px">
<table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" style="width:680px;max-width:680px;background:#ffffff;border:1px solid #e1e4ea;<?= $font ?>color:#1d2733">
  <tr><td style="padding:22px 28px 16px;border-bottom:3px solid #842484">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>
      <td valign="middle" style="width:46%"><?php if (!empty($logo)): ?><img src="<?= $e($logo) ?>" width="220" alt="<?= $e($co['name'] ?: 'Avenir Holidays') ?>" style="display:block;width:220px;max-width:220px;height:auto;border:0"><?php else: ?><span style="<?= $font ?>font-size:20px;font-weight:bold;color:#842484"><?= $e($co['name'] ?: 'Avenir Holidays') ?></span><?php endif; ?></td>
      <td valign="middle" align="right" style="<?= $font ?>font-size:11.5px;line-height:1.55;color:#5f6f80"><b style="font-size:13px;color:#1d2733"><?= $e($co['name'] ?: 'Avenir Holidays') ?></b><?php foreach ($contactBits as $b): ?><br><?= $e($b) ?><?php endforeach; ?></td>
    </tr></table>
  </td></tr>
  <tr><td style="padding:18px 28px 4px">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>
      <td valign="bottom" style="<?= $font ?>font-size:19px;font-weight:bold;letter-spacing:1.5px;color:#842484"><?= $title ?></td>
      <td valign="bottom" align="right" style="<?= $font ?>font-size:12px;line-height:1.5;color:#5f6f80">Our reference <b style="color:#1d2733"><?= $e($ref) ?></b><?php if ($isV): ?><br>Avenir reference <b style="color:#1d2733"><?= $e($d['reference']) ?></b><?php endif; ?><br><?= $e(date('d F Y')) ?></td>
    </tr></table>
  </td></tr>
  <tr><td style="padding:10px 28px 0;<?= $font ?>font-size:13px;color:#1d2733">To: <b>Reservations Department<?= $h['name'] ? ', ' . $e($h['name']) : '' ?></b></td></tr>
  <tr><td style="padding:12px 28px 2px;<?= $font ?>font-size:13.5px;line-height:1.6;color:#1d2733">
<?php if (!empty($message)): foreach (preg_split('/\n{2,}/', str_replace("\r", '', trim((string) $message))) ?: [] as $para): ?>    <p style="margin:0 0 10px"><?= nl2br($e($para)) ?></p>
<?php endforeach; else: ?>    <p style="margin:0 0 10px">Dear Reservations Team,</p>
    <p style="margin:0 0 10px"><?= $e($intro) ?></p>
<?php endif; ?>
  </td></tr>
  <tr><td style="padding:0 28px">
    <div style="<?= $sec ?>"><?= $isV ? 'Voucher details' : 'Reservation details' ?></div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;border-top:1px solid #e3e6eb">
<?= implode("\n", $detail) . "\n" ?>
<?php if ($paxNames): ?><?= $row('Passenger names', implode('<br>', array_map($e, $paxNames))) . "\n" ?><?php endif; ?>
<?= $row('Special requests / remarks', $remarkLines ? nl2br($e(implode("\n", $remarkLines))) : '—') . "\n" ?>
<?= $row('Rate', '<b>' . $e($basis) . '</b>') . "\n" ?>
    </table>
  </td></tr>
  <tr><td style="padding:18px 28px 6px">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>
      <td style="background:#f8f1f8;border-left:4px solid #842484;padding:12px 16px;<?= $font ?>font-size:13px;line-height:1.55;color:#1d2733">
<?php if (!$isV): ?>        <b>Kindly confirm this reservation by return e-mail</b> with your confirmation number. Please quote our reference <b><?= $e($d['reference']) ?></b> on all correspondence and on your invoice.
<?php else: ?>        Please quote the voucher number <b><?= $e($s['voucher_no']) ?></b> and our reference <b><?= $e($d['reference']) ?></b> on your invoice. The guests are not to be charged for the services listed above.
<?php endif; ?>
      </td>
    </tr></table>
  </td></tr>
  <tr><td style="padding:14px 28px 22px;<?= $font ?>font-size:13px;line-height:1.6;color:#1d2733">
    Best regards,<br><br>
    <b><?= $e($who['name'] ?: $co['name']) ?></b><br>
    <?= $e($co['name']) ?><?php if (!empty($who['phone'])): ?><br>Mobile <?= $e($who['phone']) ?><?php endif; ?><?php if (!empty($who['email'])): ?><br><a href="mailto:<?= $e($who['email']) ?>" style="color:#842484;text-decoration:none"><?= $e($who['email']) ?></a><?php endif; ?><?php if ($t['handler_name'] && $t['handler_name'] !== ($who['name'] ?? '')): ?><br><span style="color:#5f6f80">Tour handled by <?= $e($t['handler_name']) ?></span><?php endif; ?>
  </td></tr>
  <tr><td style="background:#2b1d33;padding:12px 28px;<?= $font ?>font-size:11px;line-height:1.55;color:#d9cfe0">
    <?= $e($co['name']) ?><?= $contactBits ? ' · ' . $e(implode(' · ', $contactBits)) : '' ?><br>
    <span style="color:#b9a9c6">This <?= $isV ? 'voucher' : 'booking request' ?> and the rates it refers to are confidential between the hotel and <?= $e($co['name'] ?: 'Avenir Holidays') ?>. Please do not disclose rates to the guests.</span>
  </td></tr>
</table>
</td></tr>
</table>
<?php
    return;
endif;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($d['reference']) ?> — <?= $isV ? 'hotel voucher' : 'booking request' ?></title>
<style>
    :root { --ink:#1d2733; --muted:#5f6f80; --line:#cfd6de; --purple:#842484; }
    * { box-sizing:border-box; }
    body { margin:0; background:#eef0f3; color:var(--ink); font:13px/1.45 "Segoe UI", Arial, Helvetica, sans-serif; }
    .sheet { background:#fff; max-width:820px; margin:18px auto; padding:34px 40px 30px; box-shadow:0 2px 12px rgba(0,0,0,.08); }
    .bar { display:flex; justify-content:space-between; gap:12px; align-items:center; margin:0 auto 6px; max-width:820px; padding:0 4px; }
    .bar button { font:inherit; padding:6px 14px; border-radius:8px; border:1px solid var(--line); background:#fff; cursor:pointer; }
    .bar .help { color:var(--muted); font-size:12px; }
    header { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; border-bottom:2px solid var(--purple); padding-bottom:12px; margin-bottom:16px; }
    header .co b { display:block; font-size:18px; color:var(--purple); letter-spacing:.01em; }
    header .co span { display:block; color:var(--muted); font-size:12px; }
    header img { max-height:64px; }
    h1 { font-size:17px; letter-spacing:.08em; text-transform:uppercase; margin:0 0 2px; }
    .ref { color:var(--muted); font-size:12px; margin-bottom:14px; }
    .to { margin:0 0 14px; }
    .to b { display:block; }
    .to span { display:block; color:var(--muted); font-size:12px; }
    table.kv { width:100%; border-collapse:collapse; margin:10px 0 14px; }
    table.kv td { border:1px solid var(--line); padding:7px 10px; vertical-align:top; }
    table.kv td.k { width:32%; font-weight:600; background:#f6f7fa; }
    table.rate { width:100%; border-collapse:collapse; margin:4px 0 14px; font-size:12.5px; }
    table.rate th, table.rate td { border:1px solid var(--line); padding:5px 8px; text-align:left; }
    table.rate th { background:#f6f7fa; font-weight:600; }
    .num { font-variant-numeric:tabular-nums; text-align:right; }
    p { margin:0 0 10px; }
    .sig { margin-top:26px; }
    .sig b { display:block; }
    .sig span { display:block; color:var(--muted); font-size:12px; }
    .foot { margin-top:22px; padding-top:8px; border-top:1px solid var(--line); color:var(--muted); font-size:11px; display:flex; justify-content:space-between; gap:12px; }
    @media print { body { background:#fff; } .sheet { box-shadow:none; margin:0; max-width:none; padding:10mm 12mm; } .bar { display:none; } }
</style>
</head>
<body>
<div class="bar">
    <span class="help"><?= $isV ? 'The voucher as it goes to the hotel.' : 'The booking request as it goes to the hotel.' ?> Print, or save as PDF from the print dialog.</span>
    <button type="button" onclick="window.print()">Print</button>
</div>
<div class="sheet">
    <header>
        <div class="co"><b><?= $e($co['name'] ?: 'Avenir Holidays') ?></b><?php foreach ($contactBits as $b): ?><span><?= $e($b) ?></span><?php endforeach; ?></div>
        <?php if (!empty($logo)): ?><img src="<?= $e($logo) ?>" alt=""><?php endif; ?>
    </header>
    <h1><?= $title ?></h1>
    <div class="ref"><?= $isV ? 'Voucher ' . $e($s['voucher_no'] ?: '—') . ' · ' : '' ?>Avenir reference <b><?= $e($d['reference']) ?></b> · <?= $e(date('d M Y')) ?></div>
    <div class="to"><b>To: <?= $e($h['name'] ?: 'Hotel not named') ?></b><?php foreach ($hotelContact as $b): ?><span><?= $e($b) ?></span><?php endforeach; ?></div>
    <?php if (!empty($message)): foreach (preg_split('/\n{2,}/', str_replace("\r", '', trim((string) $message))) ?: [] as $para): ?><p><?= nl2br($e($para)) ?></p><?php endforeach; else: ?>
    <p>Dear Reservations Team,</p>
    <p><?= $e($intro) ?></p>
    <?php endif; ?>
    <table class="kv">
        <?php foreach ($rows as [$k, $v]): ?><tr><td class="k"><?= $e($k) ?></td><td><?= $e($v) ?></td></tr><?php endforeach; ?>
        <?php if ($paxNames): ?><tr><td class="k">Passenger names</td><td><?= implode('<br>', array_map($e, $paxNames)) ?></td></tr><?php endif; ?>
        <tr><td class="k">Special requests / remarks</td><td><?= $remarkLines ? nl2br($e(implode("\n", $remarkLines))) : '—' ?></td></tr>
        <tr><td class="k">Rate</td><td><b><?= $e($basis) ?></b></td></tr>
        <tr><td class="k">Avenir contact</td><td><?= $e(implode(' · ', $senderBits ?: [$co['name']])) ?><?= $t['handler_name'] && $t['handler_name'] !== ($who['name'] ?? '') ? '<br>Tour handled by ' . $e($t['handler_name']) : '' ?></td></tr>
    </table>
    <?php if (!$isV): ?>
    <p><b>Kindly reply with your confirmation number.</b> Please quote the Avenir reference <b><?= $e($d['reference']) ?></b> on every correspondence and invoice.</p>
    <?php else: ?>
    <p>Please quote the voucher number <b><?= $e($s['voucher_no']) ?></b> and the Avenir reference <b><?= $e($d['reference']) ?></b> on your invoice. The guests are not to be charged for the services listed above.</p>
    <?php endif; ?>
    <div class="sig"><p>Best regards,</p><b><?= $e($who['name'] ?: $co['name']) ?></b><span><?= $e($co['name']) ?></span><?php if (!empty($who['phone'])): ?><span>Mobile <?= $e($who['phone']) ?></span><?php endif; ?><?php if (!empty($who['email'])): ?><span><?= $e($who['email']) ?></span><?php endif; ?></div>
    <div class="foot"><span><?= $e($co['name']) ?><?= $contactBits ? ' · ' . $e(implode(' · ', $contactBits)) : '' ?></span><span>Prepared <?= $e(date('d M Y H:i')) ?><?= !empty($who['name']) ? ' by ' . $e($who['name']) : '' ?></span></div>
</div>
</body>
</html>
