<?php
/**
 * The complete record of a confirmed tour (tf-record-r1): one page, printable, under the Tour Board's privilege.
 * From the inquiry as it arrived to where the tour stands now — the email, the request, the itinerary, every
 * quotation version with what went to the agent and the version accepted, the confirmation and every
 * re-confirmation, the hotel stays and activity lines with their letters, the vehicles with the driver and the
 * guide, the amendments, and the whole history oldest first. Read from the one record (TourRecord); nothing here
 * writes. A frozen version is shown as it was frozen.
 *
 *   $r            TourRecord::assemble()
 *   $urls         board, intake, hotels, transport, quote (the base of the version links)
 *   $sees_pricing whether the person may see money (the Pricing privilege): costs are shown only then
 */
$t   = $r['tour'];
$it  = $r['inquiry'];
$rc  = $r['received'];
$d   = $r['details'] ?? [];
$e   = static fn ($v): string => esc((string) ($v ?? ''));
$fmt = static function ($x, bool $long = false): string {
    if (!$x) {
        return '—';
    }
    $ts = strtotime((string) $x);
    return $ts ? date($long ? 'D j M Y' : 'j M Y', $ts) : (string) $x;
};
$when = static fn ($x): string => $x ? substr((string) $x, 0, 16) : '—';
$usd  = static fn ($v): string => $v === null || $v === '' ? '—' : 'USD ' . number_format((float) $v, 2);
$P = '#842484'; $INK = '#2c3e50'; $MUTED = '#6c757d'; $LINE = '#e6dbe6';
$HL = ['to_book' => 'To book', 'requested' => 'Requested', 'confirmed' => 'Confirmed', 'voucher_issued' => 'Voucher issued', 'problem' => 'Problem', 'cancelled' => 'Cancelled'];
$AL = ['to_book' => 'To book', 'requested' => 'Requested', 'confirmed' => 'Confirmed', 'not_needed' => 'Nothing to arrange', 'problem' => 'Problem', 'cancelled' => 'Cancelled', 'pay_at_gate' => 'Pay at gate'];
$TL = ['to_allocate' => 'To allocate', 'allocated' => 'Allocated', 'scheduled' => 'Scheduled', 'pack_issued' => 'Pack issued', 'running' => 'Running', 'returned' => 'Returned', 'settled' => 'Settled', 'cancelled' => 'Cancelled'];
$pill = static function (string $text, string $kind = 'grey') use ($e): string {
    $c = ['green' => '#e4f4ea;color:#1f8a4c', 'red' => '#fbe9e7;color:#b23a32', 'amber' => '#fdf3e1;color:#b7791f', 'blue' => '#e8f1fb;color:#1a5fa8', 'grey' => '#eef1f5;color:#5f6f80', 'purple' => '#f3e6f3;color:#842484'][$kind] ?? '#eef1f5;color:#5f6f80';
    return '<span style="display:inline-block;padding:1px 9px;border-radius:12px;font-size:11.5px;font-weight:600;background:' . $c . '">' . $e($text) . '</span>';
};
$stageKind = ['confirmed' => 'green', 'operating' => 'blue', 'completed' => 'grey', 'closed' => 'grey', 'cancelled' => 'red'];
$counts = $r['counts'];
$amendedStay = [];
foreach ($r['hotels'] as $hj) {
    if ((int) ($hj['amended'] ?? 0)) {
        $amendedStay[(int) $hj['id']] = true;
    }
}
$first  = null;
foreach ($r['confirmations'] as $c) {
    if ($c['event'] === 'confirmed') {
        $first = $c;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Complete record — <?= $e($t['tour_no']) ?> — <?= $e($t['tour_name'] ?: $t['agent_name']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { margin:0; background:#eceff3; font-family:"Segoe UI", Helvetica, Arial, sans-serif; color:<?= $INK ?>; font-size:13px; line-height:1.5; }
    .bar { position:sticky; top:0; z-index:5; display:flex; gap:8px; align-items:center; flex-wrap:wrap; padding:10px 18px; background:#fff; border-bottom:1px solid #dfe5ec; box-shadow:0 2px 8px rgba(0,0,0,.05); }
    .bar a, .bar button { font:inherit; font-size:12.5px; font-weight:600; padding:7px 12px; border-radius:8px; border:1px solid #dfe5ec; background:#fff; color:<?= $INK ?>; text-decoration:none; cursor:pointer; }
    .bar a:hover, .bar button:hover { background:#f4f6f9; }
    .bar .toc { margin-left:auto; display:flex; gap:4px; flex-wrap:wrap; }
    .bar .toc a { font-weight:500; padding:5px 9px; font-size:12px; color:<?= $MUTED ?>; border-color:transparent; }
    .sheet { max-width:980px; margin:22px auto; background:#fff; padding:28px 34px 40px; box-shadow:0 8px 30px rgba(0,0,0,.08); box-sizing:border-box; border-radius:4px; }
    h1 { font-size:24px; color:<?= $P ?>; margin:0 0 4px; }
    h2 { font-size:15px; color:<?= $P ?>; margin:28px 0 8px; border-bottom:1px solid <?= $LINE ?>; padding-bottom:4px; scroll-margin-top:70px; }
    h3 { font-size:13px; color:<?= $INK ?>; margin:14px 0 6px; }
    .sub { color:<?= $MUTED ?>; font-size:12.5px; }
    table { border-collapse:collapse; width:100%; font-size:12.5px; }
    th, td { text-align:left; vertical-align:top; padding:6px 8px; border-bottom:1px solid #f0ebf0; }
    th { font-size:11px; letter-spacing:.04em; text-transform:uppercase; color:<?= $MUTED ?>; background:#faf5fa; }
    .kv { display:grid; grid-template-columns:170px 1fr; gap:4px 12px; font-size:12.5px; }
    .kv b { color:<?= $MUTED ?>; font-weight:600; }
    .mono { font-family:ui-monospace, Menlo, Consolas, monospace; font-size:12px; }
    .email { white-space:pre-wrap; background:#faf9fb; border:1px solid <?= $LINE ?>; border-radius:8px; padding:12px 14px; font-size:12.5px; max-height:420px; overflow:auto; }
    .vbox { border:1px solid <?= $LINE ?>; border-radius:10px; padding:12px 14px; margin:10px 0; }
    .vbox.sold { border-color:#1f8a4c; box-shadow:0 0 0 2px #e4f4ea; }
    .hist div.l { padding:6px 0; border-bottom:1px solid #f0ebf0; font-size:12.5px; }
    .hist .w { color:<?= $MUTED ?>; font-size:11.5px; }
    .hist table.ch { margin:4px 0 0 0; font-size:11.5px; }
    .lane { font-size:10px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; margin-right:6px; }
    .lane.hotel { color:#1878c0; } .lane.transport { color:#b8640f; } .lane.quotation { color:#842484; } .lane.tour { color:<?= $INK ?>; } .lane.service { color:#0f8b7e; }
    .counts { display:flex; gap:10px; flex-wrap:wrap; margin:10px 0 4px; }
    .counts span { background:#faf5fa; border:1px solid <?= $LINE ?>; border-radius:8px; padding:5px 10px; font-size:12px; }
    .counts b { font-size:14px; }
    .note { font-size:12px; color:<?= $MUTED ?>; }
    a.open { color:#1a5fa8; text-decoration:none; } a.open:hover { text-decoration:underline; }
    @media print { body { background:#fff; } .bar { display:none; } .sheet { margin:0; box-shadow:none; max-width:none; padding:0; } .email { max-height:none; } h2 { page-break-after:avoid; } }
</style></head><body>
<div class="bar">
    <a href="<?= $e($urls['board']) ?>">&larr; Tour Board</a>
    <?php if (!empty($urls['intake'])): ?><a href="<?= $e($urls['intake']) ?>?id=<?= (int) $t['intake_id'] ?>">The inquiry</a><?php endif; ?>
    <?php if (!empty($urls['intake'])): ?><a href="<?= $e($urls['intake']) ?>/comms/<?= (int) $t['intake_id'] ?>">Communications &amp; History</a><?php endif; ?>
    <?php if (!empty($urls['hotels'])): ?><a href="<?= $e($urls['hotels']) ?>?tour=<?= (int) $t['id'] ?>">Hotel Bookings</a><?php endif; ?>
    <?php if (!empty($urls['transport'])): ?><a href="<?= $e($urls['transport']) ?>?tour=<?= (int) $t['id'] ?>">Transport</a><?php endif; ?>
    <button onclick="window.print()">Print</button>
    <nav class="toc"><a href="#s1">1 Inquiry</a><a href="#s2">2 Itinerary</a><a href="#s3">3 Quotations</a><a href="#s4">4 Confirmation</a><a href="#s5">5 Hotels &amp; activities</a><a href="#s6">6 Transport</a><a href="#s7">7 Payments</a><a href="#s8">8 Amendments</a><a href="#s9">9 History</a></nav>
</div>
<div class="sheet">
<h1>Complete record — <?= $e($t['tour_no']) ?></h1>
<div class="sub"><b><?= $e($t['tour_name'] ?: '—') ?></b> · <?= $e($t['agent_name'] ?: '—') ?> · <?= $e($t['dates_line'] ?? ($fmt($t['arrival_date']) . ' – ' . $fmt($t['departure_date']))) ?> · <?= (int) ($t['pax_total'] ?? 0) ?> pax
    · <?= $pill(ucfirst((string) $t['stage']), $stageKind[(string) $t['stage']] ?? 'grey') ?><?= (int) ($t['amending'] ?? 0) ? ' ' . $pill('quotation open for amendment', 'amber') : '' ?>
    · inquiry <?= $e($rc['inquiry_no'] ?? '') ?> · handling agent <?= $e($t['handler_name'] ?: 'nobody') ?></div>
<div class="counts">
    <span><b><?= $counts['versions'] ?></b> quotation version<?= $counts['versions'] === 1 ? '' : 's' ?></span>
    <span><b><?= $counts['emails'] ?></b> email<?= $counts['emails'] === 1 ? '' : 's' ?> to the agent</span>
    <span><b><?= $counts['hotels'] ?></b> hotel stay<?= $counts['hotels'] === 1 ? '' : 's' ?></span>
    <span><b><?= $counts['activities'] ?></b> activity line<?= $counts['activities'] === 1 ? '' : 's' ?></span>
    <span><b><?= $counts['vehicles'] ?></b> vehicle<?= $counts['vehicles'] === 1 ? '' : 's' ?></span>
    <span><b><?= $counts['edits'] ?></b> edit request<?= $counts['edits'] === 1 ? '' : 's' ?></span>
    <span><b><?= $counts['history'] ?></b> history line<?= $counts['history'] === 1 ? '' : 's' ?></span>
</div>
<p class="note">Confirmed <?= $when($t['confirmed_at']) ?> by <?= $e($t['confirmed_by']) ?><?= $r['sold_first'] ? ' · the agent accepted quotation version ' . (int) $r['sold_first'] : '' ?><?= $r['sold_now'] !== null && $r['sold_first'] !== null && (int) $r['sold_now'] !== (int) $r['sold_first'] ? ' · after amendment, now sold as version ' . (int) $r['sold_now'] : '' ?><?= !empty($t['cancelled_at']) ? ' · <b style="color:#b23a32">cancelled ' . $when($t['cancelled_at']) . ' by ' . $e($t['cancelled_by']) . ' — ' . $e($t['cancel_why']) . '</b>' : '' ?>. Printed <?= date('j M Y H:i') ?>.</p>

<h2 id="s1">1 · The inquiry as it arrived</h2>
<div class="kv">
    <b>Inquiry</b><span><?= $e($rc['inquiry_no'] ?? '') ?> · opened <?= $when($rc['created_at'] ?? null) ?> by <?= $e($rc['created_by'] ?? '') ?></span>
    <b>From</b><span><?= $e($rc['source_from'] ?? '') ?: '—' ?><?= !empty($rc['source_received_at']) ? ' · received ' . $when($rc['source_received_at']) : '' ?></span>
    <b>Subject</b><span><?= $e($rc['source_subject'] ?? '') ?: '—' ?></span>
    <b>Agent</b><span><?= $e($it['agent_name'] ?? $t['agent_name']) ?><?= !empty($it['agent_acc']) ? ' · ' . $e($it['agent_acc']) : '' ?><?= !empty($rc['agent_email']) ? ' · ' . $e($rc['agent_email']) : '' ?><?= !empty($it['country_iso']) ? ' · ' . $e($it['country_iso']) : '' ?><?= !empty($it['market_code']) ? ' (' . $e($it['market_code']) . ')' : '' ?></span>
    <b>Request, as held now</b><span><?= $e($it['tour_name'] ?? '') ?: '—' ?> · <?= $fmt($it['arrival_date'] ?? null) ?> to <?= $fmt($it['departure_date'] ?? null) ?> · <?= (int) ($it['nights'] ?? 0) ?> nights · <?= (int) ($it['pax_total'] ?? 0) ?> pax<?= isset($it['pax_adults']) && $it['pax_adults'] !== null ? ' (' . (int) $it['pax_adults'] . ' adults' . ((int) ($it['pax_children'] ?? 0) ? ', ' . (int) $it['pax_children'] . ' children' : '') . ')' : '' ?><?= !empty($it['rooms_note']) ? ' · rooms: ' . $e($it['rooms_note']) : '' ?><?= !empty($it['hotel_class']) ? ' · ' . $e($it['hotel_class']) : '' ?></span>
    <b>Flights</b><span><?= $e($it['arrival_flight'] ?? '') ?: '—' ?> / <?= $e($it['departure_flight'] ?? '') ?: '—' ?></span>
</div>
<p class="note">The email below is kept as it arrived. The request lines above are the working copy, which staff correct as the agent writes back; every change to them since the inquiry was opened is in section 9, with who made it and when.</p>
<?php $text = trim((string) ($rc['source_text_original'] ?? '')) !== '' ? $rc['source_text_original'] : ($rc['source_text'] ?? ''); ?>
<?php if (trim((string) $text) !== ''): ?>
<h3>The email as received<?= !empty($rc['source_text_original']) && trim((string) $rc['source_text']) !== trim((string) $rc['source_text_original']) ? ' (the original; the working copy was corrected ' . $when($rc['source_edited_at'] ?? null) . ' by ' . $e($rc['source_edited_by'] ?? '') . ')' : '' ?></h3>
<div class="email"><?= $e($text) ?></div>
<?php else: ?>
<p class="note">No email text on this inquiry (it was typed in by hand).</p>
<?php endif; ?>
<?php if (!empty($it['requests'])): ?>
<h3>The agent's requests</h3>
<table><thead><tr><th>Request</th><th>Status</th><th>Answer</th></tr></thead><tbody>
<?php foreach ($it['requests'] as $q): if (($q['status'] ?? '') === 'dropped') { continue; } ?>
<tr><td><?= $e($q['title'] ?? $q['quote_text'] ?? '') ?><?= !empty($q['quote_text']) && ($q['quote_text'] !== ($q['title'] ?? '')) ? '<div class="note">“' . $e($q['quote_text']) . '”</div>' : '' ?></td><td><?= $pill(ucfirst((string) ($q['status'] ?? '')), ($q['status'] ?? '') === 'done' ? 'green' : (($q['status'] ?? '') === 'answered' ? 'blue' : 'amber')) ?></td><td><?= $e($q['answer'] ?? '') ?><?= !empty($q['answered_by']) ? '<div class="note">' . $e($q['answered_by']) . ' · ' . $when($q['answered_at'] ?? null) . '</div>' : '' ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
<?php if (!empty($d['passengers'])): ?>
<h3>Passengers</h3>
<p><?= implode(' · ', array_map(static fn ($p) => $e($p['full_name']) . (!empty($p['room_type']) ? ' (' . $e($p['room_type']) . ')' : ''), $d['passengers'])) ?></p>
<?php endif; ?>

<h2 id="s2">2 · The itinerary as it stands</h2>
<p class="note">Day by day as the desks hold it now, with where each stay and line stands. The itinerary the agent accepted is in the version frozen below (section 3), unchanged since it went out.</p>
<table><thead><tr><th style="width:70px">Day</th><th>Area · hotel</th><th>Rooms · meal</th><th>Programme</th><th>On the desks</th></tr></thead><tbody>
<?php foreach ($d['days'] ?? [] as $day): ?>
<tr><td class="mono">Day <?= (int) $day['day_no'] ?><div class="note"><?= $fmt($day['date']) ?></div></td>
    <td><?= $e($day['area'] ?? '') ?><?= !empty($day['hotel']) ? '<br><b>' . $e($day['hotel']) . '</b>' : '<br><span style="color:#b23a32">no hotel named</span>' ?></td>
    <td><?= $day['rooms'] !== null && $day['rooms'] !== '' ? (int) $day['rooms'] . ' room' . ((int) $day['rooms'] === 1 ? '' : 's') : '—' ?><?= !empty($day['rooms_note']) ? ' · ' . $e($day['rooms_note']) : '' ?><br><?= $e($day['meal_plan'] ?? '') ?: '<span style="color:#b7791f">meal plan not stated</span>' ?></td>
    <td><?= !empty($day['wording']) ? $e($day['wording']) : $e($day['narrative'] ?? '') ?>
        <?php foreach (array_merge($day['services'] ?? [], $day['excursions'] ?? []) as $x): ?><div class="note">• <?= $e($x['title'] ?? $x['name'] ?? '') ?><?= !empty($x['pay_mode']) && $x['pay_mode'] !== 'in_tour' ? ' (' . $e(str_replace('_', ' ', (string) $x['pay_mode'])) . ')' : '' ?></div><?php endforeach; ?>
        <?= !empty($day['special_note']) ? '<div class="note">Special: ' . $e($day['special_note']) . '</div>' : '' ?></td>
    <td><?php if (!empty($day['stay'])): $st = $day['stay']; ?><?= $pill($HL[$st['status']] ?? $st['status'], $st['status'] === 'voucher_issued' || $st['status'] === 'confirmed' ? 'green' : ($st['status'] === 'problem' ? 'red' : ($st['status'] === 'cancelled' ? 'red' : 'grey'))) ?><?= !empty($st['confirmation_ref']) ? '<div class="note">ref ' . $e($st['confirmation_ref']) . '</div>' : '' ?><?= !empty($st['voucher_no']) ? '<div class="note">' . $e($st['voucher_no']) . '</div>' : '' ?><?= isset($amendedStay[(int) $st['id']]) ? '<div>' . $pill('amended — desk to check', 'amber') . '</div>' : '' ?><?php endif; ?>
        <?php foreach (array_merge($day['services'] ?? [], $day['excursions'] ?? []) as $x): if (!empty($x['desk'])): ?><div class="note"><?= $e($x['title'] ?? $x['name'] ?? '') ?>: <?= $e($AL[$x['desk']['status']] ?? $x['desk']['status']) ?><?= !empty($x['desk']['ref']) ? ' · ' . $e($x['desk']['ref']) : '' ?></div><?php endif; endforeach; ?></td></tr>
<?php endforeach; ?>
<?php if (!empty($d['departure_wording']) || !empty($d['departure_services']) || !empty($d['departure_excursions'])): ?>
<tr><td class="mono">Day <?= (int) ($d['departure_day_no'] ?? 0) ?><div class="note">departure</div></td><td colspan="2"><?= $e($d['departure_note'] ?? '') ?></td><td><?= $e($d['departure_wording'] ?? '') ?><?php foreach (array_merge($d['departure_services'] ?? [], $d['departure_excursions'] ?? []) as $x): ?><div class="note">• <?= $e($x['title'] ?? $x['name'] ?? '') ?></div><?php endforeach; ?></td><td></td></tr>
<?php endif; ?>
<?php if (empty($d['days'])): ?><tr><td colspan="5" class="note">No day rows.</td></tr><?php endif; ?>
</tbody></table>

<h2 id="s3">3 · Quotation versions and what went to the agent</h2>
<p class="note">Every version in order. A version's costing is frozen at Go for Quotation and never changed afterwards, so what was sent is what is shown; <b>Open</b> shows it exactly as frozen, <b>PDF</b> makes the document again from the frozen version. Emails to the agent are listed under the version they carried.</p>
<?php if (!$r['versions']): ?><p class="note">No quotation version on this inquiry (the tour was confirmed on the inquiry number alone).</p><?php endif; ?>
<?php foreach ($r['versions'] as $v): $isFirst = $r['sold_first'] !== null && (int) $v['version'] === (int) $r['sold_first']; ?>
<div class="vbox<?= $v['sold_now'] || $isFirst ? ' sold' : '' ?>">
    <div><b>Version <?= (int) $v['version'] ?></b> · <?= $pill(ucfirst((string) $v['status']), $v['status'] === 'issued' ? 'green' : ($v['status'] === 'draft' ? 'amber' : 'grey')) ?>
        <?= $isFirst ? ' ' . $pill('accepted by the agent — confirmed as the sale', 'green') : '' ?><?= $v['sold_now'] && !$isFirst ? ' ' . $pill('sold as, after amendment', 'blue') : '' ?>
        <span class="note" style="margin-left:8px">made <?= $when($v['created_at']) ?> by <?= $e($v['created_by']) ?><?= !empty($v['issued_at']) ? ' · issued ' . $when($v['issued_at']) . ' by ' . $e($v['issued_by']) : '' ?><?= !empty($v['note']) ? ' · ' . $e($v['note']) : '' ?></span>
        <?php if (!empty($urls['quote'])): ?><span style="float:right"><a class="open" href="<?= $e($urls['quote']) ?>/<?= (int) $v['id'] ?>?view=client">Open</a> · <a class="open" href="<?= $e($urls['quote']) ?>/<?= (int) $v['id'] ?>?view=client&amp;pdf=1">PDF</a><?= $sees_pricing ? ' · <a class="open" href="' . $e($urls['quote']) . '/' . (int) $v['id'] . '?view=internal">Costing</a>' : '' ?></span><?php endif; ?></div>
    <div class="kv" style="margin-top:6px">
        <b>Price</b><span><?= $usd($v['frozen']['sell_usd']) ?><?= $v['frozen']['per_person_usd'] !== null ? ' · ' . $usd($v['frozen']['per_person_usd']) . ' per person' : '' ?> · <?= (int) ($v['frozen']['pax'] ?? $v['pax']) ?> pax · valid until <?= $fmt($v['valid_until']) ?><?= $v['max_pax'] !== null ? ' · quoted for up to ' . (int) $v['max_pax'] . ' travellers' : '' ?></span>
        <b>Hotels as frozen</b><span><?= $v['frozen']['hotels'] ? implode(' · ', array_map(static fn ($h) => 'D' . (int) $h['day_no'] . ' ' . $e($h['hotel'] ?: ($h['area'] ?: '?')) . (!empty($h['meal_plan']) ? ' (' . $e($h['meal_plan']) . ')' : ''), $v['frozen']['hotels'])) : '—' ?></span>
        <?php if ($v['frozen']['pax_prices']): ?><b>Per person by size</b><span><?= implode(' · ', array_map(static fn ($p) => (int) $p['pax'] . ': ' . ($p['per_person_usd'] !== null ? number_format((float) $p['per_person_usd'], 2) : 'on request'), array_filter($v['frozen']['pax_prices'], static fn ($p) => $v['max_pax'] === null || (int) $p['pax'] <= (int) $v['max_pax']))) ?> (USD)</span><?php endif; ?>
        <b>Emailed to the agent</b><span><?php if ($v['emails']): ?><?php foreach (array_reverse($v['emails']) as $m): ?><div><?= $m['status'] === 'sent' ? '✔' : '✖' ?> <?= $when($m['sent_at']) ?> · to <?= $e($m['to_email']) ?><?= !empty($m['cc_email']) ? ' (cc ' . $e($m['cc_email']) . ')' : '' ?> · by <?= $e($m['sent_name'] ?: $m['sent_by']) ?> · <?= $e($m['pdf_name']) ?><?= $m['max_pax'] ? ' · rows up to ' . (int) $m['max_pax'] : '' ?><?= $m['status'] !== 'sent' ? ' · <span style="color:#b23a32">failed: ' . $e($m['error']) . '</span>' : '' ?><div class="note">“<?= $e($m['subject']) ?>”</div></div><?php endforeach; ?><?php else: ?>not emailed from the system<?= $v['status'] === 'issued' ? ' (issued by hand' . (!empty($v['note']) ? ': ' . $e($v['note']) : '') . ')' : '' ?><?php endif; ?></span>
    </div>
</div>
<?php endforeach; ?>

<h2 id="s4">4 · The confirmation</h2>
<div class="kv">
    <b>Confirmed</b><span><?= $when($t['confirmed_at']) ?> by <b><?= $e($t['confirmed_by']) ?></b><?= $first ? ' — ' . $e($first['note']) : '' ?></span>
    <b>Version accepted</b><span><?= $r['sold_first'] !== null ? 'quotation version ' . (int) $r['sold_first'] . ' (frozen; shown above exactly as it went out)' : '— (no version recorded)' ?></span>
    <?php if ($r['sold_now'] !== null && $r['sold_first'] !== null && (int) $r['sold_now'] !== (int) $r['sold_first']): ?><b>Sold as, now</b><span>version <?= (int) $r['sold_now'] ?> — re-confirmed after an amendment; version <?= (int) $r['sold_first'] ?> stays as the record of the original acceptance</span><?php endif; ?>
    <b>Tour number</b><span class="mono"><?= $e($t['tour_no']) ?></span>
    <b>Hotel work</b><span><?= !empty($t['reservations_confirmed_at']) ? 'complete — marked ' . $when($t['reservations_confirmed_at']) . ' by ' . $e($t['reservations_confirmed_by']) : 'pending' ?></span>
    <b>Transport work</b><span><?= !empty($t['transport_complete_at']) ? 'complete — marked ' . $when($t['transport_complete_at']) . ' by ' . $e($t['transport_complete_by']) : 'pending' ?></span>
</div>
<?php if (count($r['confirmations']) > 1 || ($r['confirmations'] && $r['confirmations'][0]['event'] !== 'confirmed')): ?>
<h3>Every change of stage</h3>
<table><thead><tr><th style="width:140px">When</th><th style="width:120px">What</th><th>Note</th><th style="width:140px">By</th></tr></thead><tbody>
<?php foreach ($r['confirmations'] as $c): ?><tr><td class="mono"><?= $when($c['at']) ?></td><td><?= $pill(str_replace('_', ' ', (string) $c['event']), in_array($c['event'], ['confirmed', 'reconfirmed', 'reopened', 'restored'], true) ? 'green' : ($c['event'] === 'cancelled' || $c['event'] === 'deleted' ? 'red' : 'amber')) ?></td><td><?= $e($c['note']) ?></td><td><?= $e($c['by']) ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php endif; ?>

<h2 id="s5">5 · Hotels and activities after confirmation</h2>
<?php if (!$r['hotels']): ?><p class="note">No hotel stays on this tour.</p><?php endif; ?>
<?php foreach ($r['hotels'] as $h): ?>
<div class="vbox">
    <div><b><?= $e($h['hotel_name'] ?: 'Hotel not named') ?></b><?= !empty($h['area']) ? ' · ' . $e($h['area']) : '' ?> · <?= $fmt($h['check_in']) ?> → <?= $fmt($h['check_out']) ?> · <?= (int) $h['nights'] ?> night<?= (int) $h['nights'] === 1 ? '' : 's' ?> · <?= $h['rooms'] !== null ? (int) $h['rooms'] . ' room' . ((int) $h['rooms'] === 1 ? '' : 's') : '—' ?><?= !empty($h['rooms_note']) ? ' (' . $e($h['rooms_note']) . ')' : '' ?> · <?= $e($h['meal_plan'] ?: 'meal plan not stated') ?>
        <span style="float:right"><?= $pill($HL[$h['status']] ?? $h['status'], in_array($h['status'], ['confirmed', 'voucher_issued'], true) ? 'green' : (in_array($h['status'], ['problem', 'cancelled'], true) ? 'red' : 'grey')) ?><?= (int) ($h['amended'] ?? 0) ? ' ' . $pill('amended', 'amber') : '' ?></span></div>
    <div class="kv" style="margin-top:6px">
        <?php if (!empty($h['requested_at'])): ?><b>Requested</b><span><?= $when($h['requested_at']) ?> by <?= $e($h['requested_by']) ?><?= !empty($h['request_note']) ? ' — ' . $e($h['request_note']) : '' ?></span><?php endif; ?>
        <?php if (!empty($h['confirmed_at'])): ?><b>Confirmed by the hotel</b><span><?= $when($h['confirmed_at']) ?> by <?= $e($h['confirmed_by']) ?> · ref <?= $e($h['confirmation_ref'] ?: '—') ?><?= !empty($h['confirmed_with']) ? ' (' . $e($h['confirmed_with']) . ')' : '' ?></span><?php endif; ?>
        <?php if (!empty($h['voucher_no'])): ?><b>Voucher</b><span><?= $e($h['voucher_no']) ?> · <?= $when($h['voucher_at']) ?> by <?= $e($h['voucher_by']) ?></span><?php endif; ?>
        <?php if (!empty($h['problem_note'])): ?><b style="color:#b23a32">Problem</b><span><?= $e($h['problem_note']) ?> · <?= $when($h['problem_at']) ?> by <?= $e($h['problem_by']) ?></span><?php endif; ?>
        <?php if (!empty($h['special_note'])): ?><b>Special requests</b><span><?= $e($h['special_note']) ?></span><?php endif; ?>
        <?php if (!empty($h['messages'])): ?><b>Letters and replies</b><span><?php foreach (array_reverse($h['messages']) as $m): ?><div><?= $e(ucfirst((string) $m['kind'])) ?> · <?= $e($m['channel']) ?><?= !empty($m['to_addr']) ? ' to ' . $e($m['to_addr']) : '' ?> · <?= $when($m['at']) ?> · <?= $e($m['by_name'] ?: $m['by']) ?> · <?= $pill((string) $m['state'], $m['state'] === 'sent' || $m['state'] === 'received' ? 'green' : ($m['state'] === 'failed' ? 'red' : 'grey')) ?><?= !empty($m['subject']) ? '<div class="note">“' . $e($m['subject']) . '”</div>' : '' ?></div><?php endforeach; ?></span><?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php if ($r['activities']): ?>
<h3>Activities and tickets</h3>
<table><thead><tr><th style="width:60px">Day</th><th>What</th><th>Paid</th><th>Status</th><th>Reference · note</th></tr></thead><tbody>
<?php foreach ($r['activities'] as $a): ?><tr><td class="mono"><?= $a['day_no'] ? 'D' . (int) $a['day_no'] : '—' ?></td><td><?= $e($a['title']) ?> <span class="note"><?= $e($a['kind']) ?></span></td><td><?= $e(str_replace('_', ' ', (string) $a['pay_mode'])) ?></td><td><?= $pill($AL[$a['status']] ?? $a['status'], in_array($a['status'], ['confirmed', 'not_needed', 'pay_at_gate'], true) ? 'green' : (in_array($a['status'], ['problem', 'cancelled'], true) ? 'red' : 'grey')) ?></td><td><?= !empty($a['ticket_text']) ? 'Tickets <b>' . $e($a['ticket_text']) . '</b> from stock' . (!empty($a['tickets_used']) ? ' · ' . (int) $a['tickets_used'] . ' used' : '') : $e($a['confirmation_ref'] ?? '') ?><?= !empty($a['confirmed_at']) ? '<div class="note">' . $when($a['confirmed_at']) . ' by ' . $e($a['confirmed_by']) . '</div>' : '' ?><?= $a['status'] === 'pay_at_gate' && !empty($a['request_note']) ? '<div class="note">' . $e($a['request_note']) . '</div>' : '' ?><?= !empty($a['problem_note']) ? '<div class="note" style="color:#b23a32">' . $e($a['problem_note']) . '</div>' : '' ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php endif; ?>

<h2 id="s6">6 · Transport — vehicle, driver and guide</h2>
<?php if (!$r['transport']): ?><p class="note">No vehicle on this tour.</p><?php endif; ?>
<?php foreach ($r['transport'] as $j): ?>
<div class="vbox">
    <div><b><?= $e($j['vehicle_type']) ?><?= (int) ($j['qty'] ?? 1) > 1 ? ' × ' . (int) $j['qty'] : '' ?></b> · <?= $j['km_total'] !== null ? number_format((float) $j['km_total']) . ' km' : '—' ?> · <?= (int) ($j['days'] ?? 0) ?> day<?= (int) ($j['days'] ?? 0) === 1 ? '' : 's' ?>
        <span style="float:right"><?= $pill($TL[$j['status']] ?? $j['status'], in_array($j['status'], ['allocated', 'scheduled', 'pack_issued', 'running', 'returned', 'settled'], true) ? 'green' : ($j['status'] === 'cancelled' ? 'red' : 'grey')) ?><?= (int) ($j['amended'] ?? 0) ? ' ' . $pill('amended', 'amber') : '' ?></span></div>
    <div class="kv" style="margin-top:6px">
        <b>Vehicle</b><span><?= trim((string) ($j['vehicle_reg'] ?? '')) !== '' ? '<b>' . $e($j['vehicle_reg']) . '</b>' . (!empty($j['reg_is_type']) ? ' <span style="color:#b7791f">(no registration on the vehicle master)</span>' : '') . (!empty($j['vehicle_note']) && !preg_match('/^Vehicle \d+$/', trim((string) $j['vehicle_note'])) ? ' · ' . $e($j['vehicle_note']) : '') . (!empty($j['allocated_at']) ? ' · allocated ' . $when($j['allocated_at']) . ' by ' . $e($j['allocated_by']) : '') : '<span style="color:#b7791f">to allocate</span>' ?></span>
        <b>Crew</b><span><?php $crew = $j['crew'] ?? []; if (!$crew): ?>—<?php endif; ?><?php foreach ($crew as $c): ?><div><?= $e($c['role_name'] ?? ucfirst((string) $c['kind'])) ?>: <?= trim((string) ($c['person_name'] ?? '')) !== '' ? '<b>' . $e($c['person_name']) . '</b>' . (!empty($c['person_phone']) ? ' · ' . $e($c['person_phone']) : '') : '<span style="color:#b7791f">nobody yet</span>' ?> · <?= $e(str_replace('_', ' ', (string) $c['status'])) ?><?= !empty($c['date_from']) ? ' · ' . $fmt($c['date_from']) . ' – ' . $fmt($c['date_to']) : '' ?><?= !empty($c['replaced_note']) ? ' · ' . $e($c['replaced_note']) : '' ?></div><?php endforeach; ?></span>
        <?php if (!empty($j['docs'])): ?><b>Driver's pack</b><span><?= implode(' · ', array_map(static fn ($x) => $e($x['name'] ?? $x['code'] ?? '') . ' ' . $e($x['state'] ?? ''), $j['docs'])) ?></span><?php endif; ?>
        <?php if (!empty($j['scheduled_at'])): ?><b>Scheduled</b><span><?= $when($j['scheduled_at']) ?> by <?= $e($j['scheduled_by']) ?></span><?php endif; ?>
        <?php if (!empty($j['returned_at'])): ?><b>Returned</b><span><?= $when($j['returned_at']) ?> by <?= $e($j['returned_by']) ?></span><?php endif; ?>
        <?php if (!empty($j['note'])): ?><b>Note</b><span><?= $e($j['note']) ?></span><?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<h2 id="s7">7 · Supplier payments</h2>
<?php /* tf-paydesk-r3: the payment desk's records of this tour — the official Payment Voucher No. of every payment */ $pays = $r['payments'] ?? []; $mp = static fn ($n) => number_format((float) $n, 2); ?>
<?php if (!$pays): ?><p class="note"><?= !empty($r['legacy_tour_id']) ? 'No supplier payment recorded on the payment desk for this tour yet.' : 'This record is not linked to a tour on the payment desk (no legacy tour id / matching Tour ID).' ?></p><?php else: ?>
<table><thead><tr><th>Payment Voucher No.</th><th>Supplier · service</th><th>Invoice</th><th>Approved</th><th>Rate (approval → paid)</th><th>LKR (provisional → final)</th><th>Difference</th><th>Bank charges (this tour's share)</th><th>Status</th><th>Paid · reference</th></tr></thead><tbody>
<?php foreach ($pays as $p): ?><tr>
    <td class="mono"><?= $p['voucher_vnum'] ? '<b>' . (int) $p['voucher_vnum'] . '</b>' . ($p['batch_no'] ? '<div class="note">batch ' . $e($p['batch_no']) . ((int) ($p['individual'] ?? 0) === 1 ? ' · paid individually (' . $e($p['bypass_label']) . ')' : ((int) ($p['item_count'] ?? 1) > 1 ? ' · ' . (int) $p['item_count'] . ' payments / ' . (int) $p['tour_count'] . ' tours' : '')) . '</div>' : '') : '<span class="note">not yet issued</span>' ?></td>
    <td><?= $e($p['hotel_name']) ?> <span class="note"><?= $e($p['type'] === 'Hotel' ? 'Hotel' : ($p['type'] === 'Transport Advance' ? 'Driver advance' : 'Service')) ?></span></td>
    <td><?= $e($p['invoice_no'] ?: '—') ?></td>
    <td><?= $e($p['approved_by'] ?: '') ?><?= !empty($p['approved_at']) ? '<div class="note">' . $when($p['approved_at']) . '</div>' : '' ?></td>
    <td class="mono"><?= $p['currency'] === 'USD' ? 'USD ' . $mp($p['amount']) . ' @ ' . ($p['approval_rate'] !== null ? number_format((float) $p['approval_rate'], 2) : '—') . ($p['paid_rate'] !== null ? ' → ' . number_format((float) $p['paid_rate'], 2) : '') : 'LKR — no conversion' ?></td>
    <td class="mono"><?= $p['approval_lkr'] !== null ? $mp($p['approval_lkr']) : '—' ?><?= $p['paid_lkr'] !== null ? ' → <b>' . $mp($p['paid_lkr']) . '</b>' : '' ?></td>
    <td class="mono"><?= $p['fx_diff'] !== null && abs((float) $p['fx_diff']) > 0.004 ? '<span style="color:' . ((float) $p['fx_diff'] > 0 ? '#b23a32' : '#1f8a4c') . '">' . ((float) $p['fx_diff'] > 0 ? '+' : '') . $mp($p['fx_diff']) . ((float) $p['fx_diff'] > 0 ? ' loss' : ' gain') . ((int) ($p['fx_posted'] ?? 0) === 1 ? ' · posted' : '') . '</span>' : ($p['paid_lkr'] !== null && $p['currency'] === 'USD' ? '0.00' : '—') ?></td>
    <td class="mono"><?= isset($p['charge_share']) && $p['charge_share'] !== null ? $mp($p['charge_share']) . ((int) ($p['charges_posted'] ?? 0) === 1 ? ' · posted' : '') . ((int) ($p['item_count'] ?? 1) > 1 ? '<div class="note">of ' . $mp($p['charges']) . ' on the batch</div>' : '') . ($p['tour_total_debit'] !== null ? '<div class="note">debited for this tour ' . $mp($p['tour_total_debit']) . '</div>' : '') : ($p['paid_lkr'] !== null ? '0.00' : '—') ?></td>
    <td><?= $pill($p['stage_label'], in_array($p['stage'], ['paid', 'notified'], true) ? 'green' : ($p['stage'] === 'reversed' ? 'red' : 'grey')) ?></td>
    <td><?= !empty($p['paid_date']) ? $fmt($p['paid_date']) . ' · ' . $e($p['paid_by'] ?: '') . ($p['bank_ref'] ? '<div class="note">ref ' . $e($p['bank_ref']) . ($p['Bank'] ? ' · ' . $e($p['Bank']) : '') . '</div>' : '') . ($p['remit_status'] === 'sent' ? '<div class="note">advice sent ' . $when($p['sent_at']) . '</div>' : '') : '—' ?></td>
</tr><?php endforeach; ?>
</tbody></table>
<p class="note">The Payment Voucher No. is the voucher's own number (one per voucher, never reused or changed); the same number is on the dashboard, Supplier Payables, the batch, the voucher PDF, the bank pages, the remittance advice and the audit history. A consolidated batch has one number; each supplier invoice is listed on it separately.</p>
<?php endif; ?>

<h2 id="s8">8 · Amendments and later changes</h2>
<?php if (!$r['edits'] && count($r['confirmations']) <= 1): ?><p class="note">No Edit Request and no change of stage since the confirmation.</p><?php endif; ?>
<?php if ($r['edits']): ?>
<table><thead><tr><th style="width:40px">#</th><th>What the agent wrote</th><th>Changed to</th><th>Why</th><th>Status</th><th style="width:200px">Who · when</th></tr></thead><tbody>
<?php foreach (array_reverse($r['edits']) as $x): ?><tr><td class="mono"><?= (int) $x['seq'] ?></td><td>“<?= $e(mb_substr((string) ($x['pasted_text'] ?? ''), 0, 240)) ?>”</td><td><?= $e(mb_substr((string) ($x['change_to'] ?? ''), 0, 240)) ?></td><td><?= $e($x['reason'] ?? '') ?></td><td><?= $pill(str_replace('_', ' ', (string) ($x['status'] ?? '')), ($x['status'] ?? '') === 'applied' ? 'green' : (($x['status'] ?? '') === 'cancelled' ? 'red' : 'amber')) ?><?= (int) ($x['was_locked'] ?? 0) ? '<div class="note">asked while confirmed</div>' : '' ?></td><td class="note">asked <?= $e($x['requested_by'] ?? '') ?> <?= $when($x['requested_at'] ?? null) ?><?= !empty($x['confirmed_at']) ? '<br>applied ' . $e($x['confirmed_by'] ?? '') . ' ' . $when($x['confirmed_at']) : '' ?><?= !empty($x['cancelled_at']) ? '<br>cancelled ' . $e($x['cancelled_by'] ?? '') . ' ' . $when($x['cancelled_at']) . (!empty($x['cancel_note']) ? ' — ' . $e($x['cancel_note']) : '') : '' ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php endif; ?>
<?php $later = array_values(array_filter($r['confirmations'], static fn ($c) => $c['event'] !== 'confirmed')); if ($later): ?>
<h3>Changes of stage after the confirmation</h3>
<table><thead><tr><th style="width:140px">When</th><th style="width:140px">What</th><th>Note</th><th style="width:140px">By</th></tr></thead><tbody>
<?php foreach ($later as $c): ?><tr><td class="mono"><?= $when($c['at']) ?></td><td><?= $pill(str_replace('_', ' ', (string) $c['event']), in_array($c['event'], ['reconfirmed', 'reopened', 'restored'], true) ? 'green' : (in_array($c['event'], ['cancelled', 'deleted'], true) ? 'red' : 'amber')) ?></td><td><?= $e($c['note']) ?></td><td><?= $e($c['by']) ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php endif; ?>

<h2 id="s9">9 · The whole history, oldest first</h2>
<p class="note"><?= $counts['history'] ?> lines: every edit to the inquiry, every move on the desks, every letter, every change of stage — who, when, what changed. The same lines as the Activity log and the cards' own trails; nothing is left out<?= $sees_pricing ? '' : ' except the pricing lines, which Management sees' ?>.</p>
<div class="hist">
<?php foreach ($r['history'] as $l): ?>
<div class="l"><span class="lane <?= $e($l['lane'] ?? 'tour') ?>"><?= $e($l['lane'] ?? 'tour') ?></span><b><?= $e(str_replace('_', ' ', (string) ($l['action'] ?? ''))) ?></b> <?= $e($l['summary'] ?? '') ?><div class="w"><?= $when($l['at'] ?? null) ?> · <?= $e($l['by'] ?? '') ?></div>
<?php if (!empty($l['changes'])): ?><table class="ch"><?php foreach ($l['changes'] as $c): ?><tr><td style="width:30%"><?= $e($c['what'] ?? '') ?></td><td><?= $e(is_scalar($c['from'] ?? null) ? (string) $c['from'] : json_encode($c['from'] ?? null)) ?> → <b><?= $e(is_scalar($c['to'] ?? null) ? (string) $c['to'] : json_encode($c['to'] ?? null)) ?></b></td></tr><?php endforeach; ?><?= ($l['changes_total'] ?? 0) > count($l['changes']) ? '<tr><td colspan="2" class="note">+' . ((int) $l['changes_total'] - count($l['changes'])) . ' more</td></tr>' : '' ?></table><?php endif; ?></div>
<?php endforeach; ?>
<?php if (!$r['history']): ?><div class="note">Nothing yet.</div><?php endif; ?>
</div>
<p class="note" style="margin-top:26px;border-top:1px solid <?= $LINE ?>;padding-top:6px"><?= $e($t['tour_no']) ?> · complete record · printed <?= date('j M Y H:i') ?></p>
</div>
</body></html>
