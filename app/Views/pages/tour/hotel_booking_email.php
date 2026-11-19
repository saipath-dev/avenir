<?php
/**
 * tf-resdesk-r2 (13 Nov 2026) — the Hotel Booking Request e-mail, to Shantha's mock-up. The Hotel Booking Request PDF and the
 * Hotel Rooming List PDF go with it, all three with the same Reservation Control No. Table layout, inline styles (mail programs).
 * No price. $doc = TourService::hotelDocument(); $control = the reservation control number.
 */
use App\Libraries\TourFlow\HotelBookingDocs;

$e = static fn ($v) => esc((string) ($v ?? ''));
$h = $doc['hotel']; $s = $doc['stay']; $c = $doc['company']; $sd = $doc['sender'] ?? [];
$rl = HotelBookingDocs::rooming($doc);
$d = static fn ($v) => $v ? date('D, j M Y', strtotime((string) $v)) : '—';
$lead = '';
foreach ((array) ($doc['passengers'] ?? []) as $p) {
    if (trim((string) ($p['full_name'] ?? '')) !== '') { $lead = trim((string) $p['full_name']); break; }
}
$logo = \App\Libraries\TourFlow\HotelLetter::logoUrl();
$hotel = $h['name'] . (!empty($s['area']) ? ', ' . $s['area'] : (!empty($h['address']) ? ', ' . $h['address'] : ''));
$font = "font-family:'Segoe UI',Arial,Helvetica,sans-serif;";
// tf-hotelreq-r5 (19 Nov 2026, Shantha's second mock-up and fixed palette): the same look as the Hotel Booking Request PDF — the number,
// the booking name and PAX under it, the hotel, the stay, a reservation SUMMARY. Full names and room allocations are only in the Rooming List.
$T = '#006666'; $D = '#004D50'; $G = '#78B848'; $ink = '#202B30'; $bd = '#B8C8C8';
$dm = static fn ($v) => $v ? date('j M Y', strtotime((string) $v)) : '—';
$n = (int) $s['nights']; $t = $doc['tour'];
$where = trim((string) ($s['area'] ?? '')) ?: trim((string) ($h['address'] ?? ''));
$ad = (int) ($t['pax_adults'] ?? 0); $ch = (int) ($t['pax_children'] ?? 0);
$pax = ($ad + $ch) ?: (int) ($s['pax'] ?? $t['pax_total'] ?? $rl['total_guests']);
$bname = $lead !== '' ? $lead : (trim((string) ($t['tour_name'] ?? '')) ?: 'Name to follow');
$cats = []; $beds = [];
foreach ((array) $rl['rooms'] as $r) {
    $cc = trim((string) (array_key_exists('category', $r) ? $r['category'] : ($rl['room_type_text'] ?? '')));
    if ($cc !== '') { $cats[$cc] = ($cats[$cc] ?? 0) + 1; }
    $w = \App\Libraries\TourFlow\RoomingList::WORD[$r['code']] ?? (string) $r['code']; $beds[$w] = ($beds[$w] ?? 0) + 1;
}
$mix = static fn (array $m) => !$m ? '—' : (count($m) === 1 ? (string) array_key_first($m) . (reset($m) > 1 ? ' (' . reset($m) . ' rooms)' : '')
    : implode(' • ', array_map(static fn ($k, $v) => $v . ' ' . $k, array_keys($m), $m)));
$nr = (int) $rl['total_rooms'];
// tf-hotelreq-r9: the same e-mail carries the Hotel Voucher once the hotel has confirmed ($mode = 'voucher')
$isV = ($mode ?? 'request') === 'voucher';
if ($isV && trim((string) ($s['voucher_no'] ?? '')) !== '') { $control = trim((string) $s['voucher_no']); }
$note = trim((string) ($message ?? ''));
$row = static function (string $k, string $v) use ($font, $ink, $bd) {
    return '<tr><td style="' . $font . 'padding:8px 14px;width:32%;background:#EDF5F4;border:1px solid ' . $bd . ';font-size:14px;font-weight:700;color:' . $ink . ';vertical-align:top">' . $k . '</td>'
        . '<td style="' . $font . 'padding:8px 14px;border:1px solid ' . $bd . ';font-size:14px;color:' . $ink . ';vertical-align:top">' . $v . '</td></tr>';
};
$box = static function (string $k, string $v) use ($font, $G, $ink, $bd) {
    return '<td width="33%" style="padding:0 4px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1.5px solid ' . $bd . ';border-radius:8px"><tr><td style="' . $font . 'padding:9px 6px 10px 14px;border-left:4px solid ' . $G . ';border-radius:8px">'
        . '<div style="font-size:13px;font-weight:700;color:' . $ink . '">' . $k . '</div><div style="font-size:19px;font-weight:800;color:' . $ink . ';margin-top:2px">' . $v . '</div></td></tr></table></td>';
};
?>
<div style="background:#FFFFFF;padding:24px 0;margin:0">
<table role="presentation" width="680" align="center" cellpadding="0" cellspacing="0" style="max-width:680px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid <?= $bd ?>">
  <tr><td style="padding:18px 26px 12px;border-bottom:4px solid <?= $G ?>">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
      <td style="width:270px;vertical-align:middle"><?php if ($logo): ?><img src="<?= $e($logo) ?>" alt="Avenir Holidays" width="250" style="display:block;width:250px;height:auto"><?php else: ?><span style="<?= $font ?>font-size:24px;font-weight:800;color:<?= $D ?>">Avenir Holidays</span><?php endif; ?></td>
      <td style="<?= $font ?>border-left:2px solid <?= $T ?>;padding-left:20px;vertical-align:middle"><div style="font-size:25px;font-weight:800;line-height:1.1;color:<?= $D ?>"><?= $isV ? 'HOTEL<br>VOUCHER' : 'HOTEL BOOKING<br>REQUEST' ?></div>
        <div style="display:inline-block;background:#E8F3DE;color:<?= $D ?>;font-size:13.5px;font-weight:700;padding:6px 14px;border-radius:6px;margin-top:8px"><?= $isV ? 'Confirmed by the hotel' : 'Awaiting hotel confirmation' ?></div></td>
    </tr></table>
  </td></tr>
  <tr><td style="padding:18px 26px 0">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center"><div style="<?= $font ?>display:inline-block;background:<?= $T ?>;color:#ffffff;font-size:15px;font-weight:700;letter-spacing:1px;padding:8px 40px"><?= $isV ? 'HOTEL VOUCHER NO.' : 'VOUCHER / RESERVATION CONTROL NO.' ?></div></td></tr>
      <tr><td style="<?= $font ?>border:2px solid <?= $T ?>;padding:8px 10px 10px;text-align:center"><span style="color:<?= $G ?>;font-size:24px;font-weight:700">&mdash;&mdash;&nbsp;&nbsp;</span><span style="font-size:32px;font-weight:800;color:<?= $D ?>;letter-spacing:.5px"><?= $e($control) ?></span><span style="color:<?= $G ?>;font-size:24px;font-weight:700">&nbsp;&nbsp;&mdash;&mdash;</span></td></tr></table>
  </td></tr>
  <tr><td style="padding:12px 26px 0">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#E8F3DE;border-radius:8px"><tr><td style="<?= $font ?>padding:10px 10px 12px;text-align:center">
      <div style="font-size:30px;font-weight:800;color:<?= $D ?>;line-height:1.15"><?= $e(mb_strtoupper($bname)) ?> &mdash; <?= (int) $pax ?> PAX</div>
      <div style="font-size:16px;color:<?= $ink ?>;margin-top:2px"><?= ($ad + $ch) ? $ad . ' adult' . ($ad === 1 ? '' : 's') . ' &bull; ' . $ch . ' child' . ($ch === 1 ? '' : 'ren') : (int) $pax . ' guest' . ($pax === 1 ? '' : 's') ?></div>
    </td></tr></table>
    <div style="<?= $font ?>font-size:13px;color:<?= $ink ?>;margin-top:9px;text-align:center">Tour <?= $e($t['tour_no']) ?><?= !empty($t['inquiry_no']) ? ' &nbsp;|&nbsp; Inquiry ' . $e($t['inquiry_no']) : '' ?></div>
  </td></tr>
  <tr><td style="<?= $font ?>padding:14px 26px 4px;color:<?= $ink ?>">
    <div style="font-size:16px;font-weight:700">Dear Reservations Manager,</div>
    <div style="font-size:14.5px;margin-top:8px;line-height:1.55">Greetings from <b style="color:<?= $D ?>">Avenir Holidays.</b> <?= $isV
        ? 'Please accept this voucher for the accommodation detailed below, confirmed by your reservations department under the reference shown. Kindly settle the account with our office as agreed; the guests are not to be charged for the services listed.'
        : 'Kindly reserve the accommodation detailed below. Please reply with your hotel confirmation reference, quoting our voucher number.' ?></div>
    <?php if ($note !== ''): ?><div style="font-size:14.5px;margin-top:8px;line-height:1.55"><?= nl2br($e($note)) ?></div><?php endif; ?>
  </td></tr>
  <tr><td style="padding:12px 26px 0">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1.5px solid <?= $T ?>"><tr>
      <td style="width:14px;background:<?= $T ?>">&nbsp;</td>
      <td style="<?= $font ?>padding:11px 16px;font-size:20px;color:<?= $ink ?>"><b><?= $e($h['name']) ?></b><?= $where !== '' ? ' / ' . $e($where) : '' ?></td>
    </tr></table>
  </td></tr>
  <tr><td style="padding:12px 22px 0">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
      <?= $box('Check-in', $e($dm($s['check_in']))) ?><?= $box('Check-out', $e($dm($s['check_out']))) ?><?= $box('Duration', $n . ' night' . ($n === 1 ? '' : 's')) ?>
    </tr></table>
  </td></tr>
  <tr><td style="padding:14px 26px 0">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
      <tr><td colspan="2" style="<?= $font ?>background:<?= $T ?>;color:#ffffff;font-size:15px;font-weight:700;letter-spacing:.8px;padding:8px 14px">RESERVATION SUMMARY</td></tr>
      <?= $row('Rooms', $nr > 0 ? $nr . ' room' . ($nr === 1 ? '' : 's') : '—') ?>
      <?= $row('Room category', $e($mix($cats))) ?>
      <?= $row('Bed arrangement', $e($mix($beds))) ?>
      <?= $row('Meal plan', $e(count($rl['meals']) > 1 ? 'Differs by room — see the Rooming List' : HotelBookingDocs::mealWord($s['meal_plan']))) ?>
      <?= $row('Special requests', !empty($s['special_note']) ? nl2br($e($s['special_note'])) : 'None specified') ?>
      <?php if (!empty($s['crew_rooms'])): ?><?= $row('Crew rooms', $e($s['crew_rooms'])) ?><?php endif; ?>
      <?php if (trim((string) ($doc['rate_basis'] ?? '')) !== ''): ?><?= $row('Rate', $e($doc['rate_basis'])) ?><?php endif; ?>
    </table>
  </td></tr>
  <?php if ($nr > 0 && !$isV): ?>
  <tr><td style="padding:12px 26px 0">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#D9EEEE;border-radius:8px"><tr><td style="<?= $font ?>padding:10px 16px;border-left:4px solid <?= $G ?>;border-radius:8px;color:<?= $ink ?>">
      <div style="font-size:17px;font-weight:800"><span style="color:<?= $D ?>">Separate attachment:</span> Rooming List PDF</div>
      <div style="font-size:13.5px;margin-top:2px">Full guest names and room allocations are provided in the rooming list.</div>
    </td></tr></table>
  </td></tr>
  <?php endif; ?>
  <tr><td style="padding:12px 26px 0">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#E8F3DE;border-radius:8px"><tr><td style="<?= $font ?>padding:12px 16px;color:<?= $ink ?>">
      <div style="font-size:18px;font-weight:800;color:<?= $D ?>;border-bottom:1.5px solid <?= $G ?>;padding-bottom:6px">HOTEL CONFIRMATION</div>
      <?php if ($isV): ?>
      <div style="font-size:14.5px;margin-top:8px">Hotel confirmation reference: <b><?= $e(trim((string) ($s['confirmation_ref'] ?? '')) ?: '—') ?></b><?= trim((string) ($s['confirmed_with'] ?? '')) !== '' ? ' &nbsp;&bull;&nbsp; Confirmed by: <b>' . $e($s['confirmed_with']) . '</b>' : '' ?><?= !empty($s['confirmed_at']) ? ' &nbsp;&bull;&nbsp; ' . $e(date('j M Y', strtotime((string) $s['confirmed_at']))) : '' ?></div>
      <?php else: ?>
      <div style="font-size:14.5px;margin-top:8px">Please reply with your <b>hotel confirmation reference</b>, the name of the person confirming and the date, quoting <b><?= $e($control) ?></b>.</div>
      <?php endif; ?>
    </td></tr></table>
  </td></tr>
  <tr><td style="<?= $font ?>padding:16px 26px 18px;font-size:15px;color:<?= $ink ?>;line-height:1.55">
    Kind regards,<br><b>Reservations Team,</b><br>Avenir Holidays (Pvt) Ltd.
    <div style="font-size:12.5px;margin-top:10px">Attached: <?= $isV ? 'Hotel Voucher' : 'Hotel Booking Request' . ($nr > 0 ? ' and Hotel Rooming List' : '') ?> (PDF) &mdash; <?= $e($control) ?>.</div>
  </td></tr>
  <tr><td style="<?= $font ?>border-top:2px solid <?= $G ?>;padding:10px 26px;font-size:12px;color:<?= $ink ?>"><?= $e($c['address'] ?? '') ?><?= !empty($c['phone']) ? ' &nbsp;&middot;&nbsp; ' . $e($c['phone']) : '' ?></td></tr>
</table>
</div>
