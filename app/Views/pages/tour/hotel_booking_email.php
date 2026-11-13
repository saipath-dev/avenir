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
$row = static function (string $k, string $v) use ($font) {
    return '<tr><td style="' . $font . 'padding:11px 14px;width:34%;background:#F6EEF7;border-bottom:1px solid #D9C3DD;border-right:1px solid #D9C3DD;font-size:14px;font-weight:700;color:#2A1630;vertical-align:top">' . $k . '</td>'
        . '<td style="' . $font . 'padding:11px 14px;border-bottom:1px solid #D9C3DD;font-size:14px;color:#1B1620;vertical-align:top">' . $v . '</td></tr>';
};
?>
<div style="background:#F3EFF4;padding:24px 0;margin:0">
<table role="presentation" width="680" align="center" cellpadding="0" cellspacing="0" style="max-width:680px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #E2D8E4">
  <tr><td style="background:#842484;padding:18px 22px;border-bottom:5px solid #78b848">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
      <td style="width:230px"><?php if ($logo): ?><div style="background:#ffffff;border-radius:10px;padding:8px 10px;display:inline-block"><img src="<?= $e($logo) ?>" alt="Avenir Holidays" width="200" style="display:block;width:200px;height:auto"></div><?php else: ?><span style="<?= $font ?>font-size:20px;font-weight:800;color:#fff">Avenir Holidays</span><?php endif; ?></td>
      <td style="<?= $font ?>border-left:2px solid rgba(255,255,255,.75);padding-left:18px;font-size:26px;font-weight:800;line-height:1.15;color:#ffffff">HOTEL BOOKING<br>REQUEST</td>
    </tr></table>
  </td></tr>
  <tr><td style="<?= $font ?>padding:24px 28px 6px;color:#1B1620">
    <div style="font-size:17px;font-weight:700">Dear Hotel Reservations Team,</div>
    <div style="font-size:15px;margin-top:12px">Greetings from <b style="color:#842484">Avenir Holidays</b>.</div>
    <div style="font-size:15px;margin-top:6px;line-height:1.55">We would be grateful if you could kindly book the following accommodation and confirm the reservation at your earliest convenience.</div>
  </td></tr>
  <tr><td style="padding:18px 28px 4px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
      <div style="<?= $font ?>display:inline-block;background:#842484;color:#ffffff;font-size:15px;font-weight:700;letter-spacing:.5px;padding:7px 28px;border-radius:8px 8px 0 0">RESERVATION CONTROL NO.</div>
    </td></tr><tr><td style="border:2px solid #842484;border-radius:10px;padding:14px 10px;text-align:center">
      <span style="color:#78b848;font-size:22px">&mdash;&mdash;&nbsp;</span><span style="<?= $font ?>font-size:32px;font-weight:800;color:#1B1620;letter-spacing:.5px"><?= $e($control) ?></span><span style="color:#78b848;font-size:22px">&nbsp;&mdash;&mdash;</span>
      <div style="<?= $font ?>font-size:12.5px;color:#55505A;margin-top:4px">Tour <?= $e($doc['tour']['tour_no']) ?><?= !empty($doc['tour']['inquiry_no']) ? ' · Inquiry ' . $e($doc['tour']['inquiry_no']) : '' ?></div>
    </td></tr></table>
  </td></tr>
  <tr><td style="padding:20px 28px 8px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
      <td style="<?= $font ?>font-size:17px;font-weight:800;color:#1B1620;white-space:nowrap;padding-right:12px"><span style="display:inline-block;width:26px;height:26px;border-radius:13px;background:#842484;color:#fff;text-align:center;line-height:26px;font-size:14px;margin-right:8px">&#9632;</span>RESERVATION DETAILS</td>
      <td style="width:100%;border-bottom:2px solid #78b848"></td>
    </tr></table>
  </td></tr>
  <tr><td style="padding:6px 28px 6px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #842484">
      <?= $row('Hotel', $e($hotel)) ?>
      <?= $row('Check-in', $e($d($s['check_in']))) ?>
      <?= $row('Check-out', $e($d($s['check_out']))) ?>
      <?= $row('Nights', $e((int) $s['nights'])) ?>
      <?= $row('Guests', $e(HotelBookingDocs::guestWords($doc, $rl))) ?>
      <?= $row('Lead guest', $lead !== '' ? $e($lead) : 'To follow') ?>
      <?= $row('Rooms', $e($rl['summary'] . ' (' . $rl['total_rooms'] . ' room' . ($rl['total_rooms'] === 1 ? '' : 's') . ')')) ?>
      <?= $row('Meal plan', $e(count($rl['meals']) > 1 ? 'Differs by room — see the Rooming List' : HotelBookingDocs::mealWord($s['meal_plan']))) ?>
      <?= $row('Special requests', !empty($s['special_note']) ? nl2br($e($s['special_note'])) : '—') ?>
      <?php if (!empty($s['crew_rooms'])): ?><?= $row('Crew rooms', $e($s['crew_rooms'])) ?><?php endif; ?>
      <?= $row('Rooming list', '<b>Rooming List attached</b> — ' . (int) $rl['total_guests'] . ' guests in ' . (int) $rl['total_rooms'] . ' rooms') ?>
    </table>
  </td></tr>
  <tr><td style="padding:18px 28px 10px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#E7F3DA;border-radius:12px"><tr>
      <td style="width:64px;text-align:center;padding:14px 0"><span style="display:inline-block;width:40px;height:40px;border-radius:20px;background:#4E9A2A;color:#fff;font-size:22px;line-height:40px;font-weight:700">&#10003;</span></td>
      <td style="<?= $font ?>padding:14px 16px;border-left:3px solid #78b848;font-size:16px;font-weight:700;color:#10300A;line-height:1.45">To confirm this booking, please quote the Reservation Control No. (<?= $e($control) ?>) in your reply.<div style="font-size:13.5px;font-weight:400;margin-top:6px">Your confirmation number: ______________________</div></td>
    </tr></table>
  </td></tr>
  <tr><td style="<?= $font ?>padding:10px 28px 22px;font-size:14px;color:#1B1620;line-height:1.6">
    Attached: Hotel Booking Request and Hotel Rooming List (PDF) — <?= $e($control) ?>.<br><br>
    Thank you,<br><b><?= $e($sd['name'] ?? '') ?></b><br><?= $e($c['name'] ?? 'Avenir Holidays') ?><?= !empty($sd['phone']) ? ' · ' . $e($sd['phone']) : '' ?><?= !empty($sd['email']) ? ' · ' . $e($sd['email']) : '' ?>
  </td></tr>
  <tr><td style="<?= $font ?>background:#F8F4F9;border-top:1px solid #EAE0EC;padding:10px 28px;font-size:11.5px;color:#55505A"><?= $e($c['address'] ?? '') ?><?= !empty($c['phone']) ? ' · ' . $e($c['phone']) : '' ?></td></tr>
</table>
</div>
