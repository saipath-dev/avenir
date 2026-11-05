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
 *   $rate     the published contract rate to quote, or null
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
$rateRows = [];
if (is_array($rate) && !empty($rate['totals'])) {
    foreach ($rate['totals'] as $r) {
        $rateRows[] = [$r['room_type'] ?? '', $r['meal_plan'] ?? '', $r['sgl'] ?? null, $r['dbl'] ?? null, $r['tpl'] ?? null];
    }
}
$contactBits = array_values(array_filter([$co['address'] ?? null, $co['phone'] ? 'Tel ' . $co['phone'] : null, $co['email'] ? 'Email ' . $co['email'] : null]));
$senderBits  = array_values(array_filter([$who['name'] ?? null, $who['phone'] ? 'Mobile ' . $who['phone'] : null, $who['email'] ?? null]));
$title = $isV ? 'HOTEL VOUCHER' : 'HOTEL BOOKING REQUEST';
$intro = $isV
    ? 'Please accept this voucher for the accommodation below, confirmed by your reservations department under the reference shown. Kindly settle the account with our office as agreed; the guests are not to be charged for the services listed.'
    : 'Greetings from ' . ($co['name'] ?: 'Avenir Holidays') . '. Kindly reserve the accommodation below and send us your confirmation number at your earliest convenience.';
$hotelContact = array_values(array_filter([$h['address'] ?? null, $h['phone'] ? 'Tel ' . $h['phone'] : null, $h['mobile'] ? 'Mobile ' . $h['mobile'] : null, $h['email'] ? 'Email ' . $h['email'] : null, $h['contact'] ? 'Attn ' . $h['contact'] : null]));

if (!empty($email)):
    // ---- the email body: plain tables, inline styles, nothing a mail client will strip ----
    $td = 'border:1px solid #444;padding:7px 10px;font-family:Arial,Helvetica,sans-serif;font-size:13px;vertical-align:top';
?>
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#222;max-width:720px">
<?php if (!empty($logo)): ?><p style="margin:0 0 10px"><img src="<?= $e($logo) ?>" alt="<?= $e($co['name']) ?>" style="max-height:60px"></p><?php endif; ?>
<p style="font-size:15px;font-weight:bold;margin:0 0 4px"><?= $title ?></p>
<p style="margin:0 0 12px;color:#555"><?= $e($co['name']) ?><?= $contactBits ? ' · ' . $e(implode(' · ', $contactBits)) : '' ?></p>
<?php if (!empty($message)): foreach (preg_split('/\n{2,}/', str_replace("\r", '', trim((string) $message))) ?: [] as $para): ?><p><?= nl2br($e($para)) ?></p><?php endforeach; else: ?>
<p>Dear Reservations Team<?= $h['name'] ? ', ' . $e($h['name']) : '' ?>,</p>
<p><?= $e($intro) ?></p>
<?php endif; ?>
<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;margin:8px 0 14px">
<?php foreach ($rows as [$k, $v]): ?>
  <tr><td style="<?= $td ?>;width:34%;font-weight:bold;background:#f3f3f3"><?= $e($k) ?></td><td style="<?= $td ?>"><?= $e($v) ?></td></tr>
<?php endforeach; ?>
<?php if ($paxNames): ?>
  <tr><td style="<?= $td ?>;font-weight:bold;background:#f3f3f3">Passenger names</td><td style="<?= $td ?>"><?= implode('<br>', array_map($e, $paxNames)) ?></td></tr>
<?php endif; ?>
  <tr><td style="<?= $td ?>;font-weight:bold;background:#f3f3f3">Special requests / remarks</td><td style="<?= $td ?>"><?= $remarkLines ? nl2br($e(implode("\n", $remarkLines))) : '—' ?></td></tr>
<?php if ($rateRows): ?>
  <tr><td style="<?= $td ?>;font-weight:bold;background:#f3f3f3">Rate (as per contract)</td><td style="<?= $td ?>"><?= implode('<br>', array_map(static fn ($r) => $e(trim($r[0] . ' · ' . $r[1])) . ': ' . implode(', ', array_filter([$r[2] !== null ? 'single ' . $e($rate['currency'] ?? '') . ' ' . $e($r[2]) : null, $r[3] !== null ? 'double ' . $e($rate['currency'] ?? '') . ' ' . $e($r[3]) : null, $r[4] !== null ? 'triple ' . $e($rate['currency'] ?? '') . ' ' . $e($r[4]) : null])) . ' per room per night', $rateRows)) ?></td></tr>
<?php endif; ?>
  <tr><td style="<?= $td ?>;font-weight:bold;background:#f3f3f3">Avenir contact</td><td style="<?= $td ?>"><?= $e(implode(' · ', $senderBits ?: [$co['name']])) ?><?= $t['handler_name'] && $t['handler_name'] !== ($who['name'] ?? '') ? '<br>Tour handled by ' . $e($t['handler_name']) : '' ?></td></tr>
</table>
<?php if (!$isV): ?><p><b>Kindly reply with your confirmation number.</b> Please quote the Avenir reference <b><?= $e($d['reference']) ?></b> on every correspondence and invoice.</p><?php else: ?><p>Please quote the voucher number <b><?= $e($s['voucher_no']) ?></b> and the Avenir reference <b><?= $e($d['reference']) ?></b> on your invoice.</p><?php endif; ?>
<p>Best regards,</p>
<p><b><?= $e($who['name'] ?: $co['name']) ?></b><br><?= $e($co['name']) ?><?php foreach ($contactBits as $b): ?><br><?= $e($b) ?><?php endforeach; ?><?php if (!empty($who['phone'])): ?><br>Mobile <?= $e($who['phone']) ?><?php endif; ?><?php if (!empty($who['email'])): ?><br><?= $e($who['email']) ?><?php endif; ?></p>
</div>
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
        <tr><td class="k">Avenir contact</td><td><?= $e(implode(' · ', $senderBits ?: [$co['name']])) ?><?= $t['handler_name'] && $t['handler_name'] !== ($who['name'] ?? '') ? '<br>Tour handled by ' . $e($t['handler_name']) : '' ?></td></tr>
    </table>
    <?php if ($rateRows): ?>
    <p><b>Rate, as per contract</b> (<?= $e($rate['currency'] ?? '') ?> per room per night)</p>
    <table class="rate"><thead><tr><th>Room type</th><th>Meal plan</th><th class="num">Single</th><th class="num">Double</th><th class="num">Triple</th></tr></thead><tbody>
        <?php foreach ($rateRows as $r): ?><tr><td><?= $e($r[0]) ?></td><td><?= $e($r[1]) ?></td><td class="num"><?= $r[2] !== null ? $e($r[2]) : '—' ?></td><td class="num"><?= $r[3] !== null ? $e($r[3]) : '—' ?></td><td class="num"><?= $r[4] !== null ? $e($r[4]) : '—' ?></td></tr><?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>
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
