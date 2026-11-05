<?php
/**
 * One document of the driver's pack, generated from the record (TourService::packDocument)
 * so nothing is typed twice: the hotel list, the allowance sheet, the log sheet. The
 * itinerary copy is the programme sheet (pages/tour/programme). As a page to print, or the
 * same HTML handed to the PDF library — so the markup is tables and inline styles only.
 *
 *   $kind       hotels | allowance | log | settlement
 *   $title      the document's name on the pack list
 *   $job        the transport card (TourService::transportJob)
 *   $details    the tour as the desks read it (TourService::details)
 *   $company    the company's lines from settings
 *   $driver     the driver on the card now, or null;  $guide  the guide, or null
 *   $stays      (hotels) every stay with the hotel's contact lines
 *   $allowance  (allowance) people, rooms, kilometres, allowance legs
 *   $for_pdf    true when the HTML goes to the PDF library (no toolbar)
 *   $user       who is printing
 *
 * Nothing on any of these is a price the desk does not already see: bata on the allowance
 * sheet is the desk's own; there is no rate per kilometre, no hotel rate, no markup.
 */
$j     = $job;
$d     = $details;
$co    = $company;
$pdf   = !empty($for_pdf);
$e     = static fn ($x): string => esc((string) ($x ?? ''));
$fmt   = static function (?string $ymd): string {
    if (!$ymd) {
        return '—';
    }
    $ts = strtotime($ymd);
    return $ts ? date('D j M Y', $ts) : $ymd;
};
$num   = static fn ($n): string => $n === null || $n === '' ? '—' : rtrim(rtrim(number_format((float) $n, 1, '.', ','), '0'), '.');
$money = static fn ($n): string => $n === null || $n === '' ? '—' : number_format((float) $n, 2);
$meals = ['RO' => 'room only', 'BB' => 'bed & breakfast', 'HB' => 'half board', 'FB' => 'full board', 'AI' => 'all inclusive'];
$INK = '#1d2733'; $MUTED = '#5f6f80'; $LINE = '#b9c2cc'; $SOFT = '#e3e8ee'; $NAVY = '#1e3553'; $GRN = '#1f8a4c'; $AMB = '#b7791f'; $RED = '#b23a32';
$th  = 'style="text-align:left; font-size:9.5px; letter-spacing:0.04em; text-transform:uppercase; color:' . $MUTED . '; padding:5px 6px; border-bottom:1px solid ' . $LINE . ';"';
$td  = 'style="font-size:11px; padding:6px 6px; border-bottom:1px solid ' . $SOFT . '; vertical-align:top; color:' . $INK . ';"';
$tdr = 'style="font-size:11px; padding:6px 6px; border-bottom:1px solid ' . $SOFT . '; vertical-align:top; color:' . $INK . '; text-align:right; font-family:Courier, monospace;"';
$small = 'style="font-size:10px; color:' . $MUTED . ';"';
$h2  = 'style="font-size:12px; letter-spacing:0.06em; text-transform:uppercase; color:' . $MUTED . '; margin:18px 0 6px;"';
$tag = static fn (string $text, string $color) => '<span style="font-size:9.5px; color:' . $color . '; font-weight:bold;">' . esc($text) . '</span>';
$stayState = static function (array $s) use ($tag, $GRN, $AMB, $RED): string {
    return match ((string) $s['status']) {
        'confirmed'      => $tag('CONFIRMED' . ($s['confirmation_ref'] ? ' · ref ' . $s['confirmation_ref'] : ''), $GRN),
        'voucher_issued' => $tag('VOUCHER ' . ($s['voucher_no'] ?: 'issued') . ($s['confirmation_ref'] ? ' · ref ' . $s['confirmation_ref'] : ''), $GRN),
        'requested'      => $tag('REQUESTED — not yet confirmed', $AMB),
        'problem'        => $tag('PROBLEM' . ($s['problem_note'] ? ' — ' . $s['problem_note'] : ''), $RED),
        'cancelled'      => $tag('CANCELLED', $RED),
        default          => $tag('TO BOOK — not yet requested', $AMB),
    };
};
$vehicle = trim(($j['vehicle_type'] ?? '') . ((int) ($j['qty'] ?? 1) > 1 ? ' × ' . (int) $j['qty'] : '') . ($j['vehicle_reg'] ? ' · ' . $j['vehicle_reg'] : ''));
$pax = (int) ($d['pax_total'] ?? $j['tour_pax'] ?? 0);
$who = static fn (?array $p): string => $p ? $p['person_name'] . ($p['language'] ? ' · ' . $p['language'] : '') . (!empty($p['person_phone']) ? ' · ' . $p['person_phone'] : '') : 'not yet engaged';
$title = $title ?? ['hotels' => 'Hotel list', 'allowance' => 'Allowance sheet', 'log' => 'Log sheet', 'settlement' => 'Settlement'][$kind];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= $e($j['tour_no']) ?> — <?= $e($title) ?></title>
<?php if (!$pdf): ?>
<style>
    body { font-family: Arial, Helvetica, sans-serif; color: #1d2733; margin: 24px; max-width: 960px; }
    .toolbar { margin-bottom: 14px; } .toolbar button { font: inherit; padding: 6px 12px; }
    @media print { .toolbar { display: none; } body { margin: 10mm; } }
</style>
<?php endif; ?>
</head>
<body style="font-family:Arial, Helvetica, sans-serif; color:<?= $INK ?>; font-size:11px;">
<?php if (!$pdf): ?>
<div class="toolbar"><button onclick="window.print()">Print / Save as PDF</button> <button onclick="history.back()" style="margin-left:6px">&larr; Back</button> <span style="color:<?= $MUTED ?>; font-size:11px;">Printed <?= date('j M Y H:i') ?> by <?= $e($user ?? '') ?></span></div>
<?php endif; ?>
<?php if (!empty($no_pdf_lib)): ?><div class="toolbar" style="font-size:12px;color:#b7791f;background:#fdf3e1;border:1px solid #f0d9a8;border-radius:8px;padding:8px 12px;">The PDF library is not installed on this server yet, so this is the print view: choose <b>Save as PDF</b> in the print dialog. (Ask the administrator to run <span style="font-family:monospace">composer install</span> — tecnickcom/tcpdf is already in composer.json.)</div><?php endif; ?>

<table cellpadding="0" cellspacing="0" width="100%" style="border-bottom:2px solid <?= $NAVY ?>;">
    <tr>
        <td width="62%" style="padding:0 0 8px 0; vertical-align:top;">
            <div style="font-size:18px; font-weight:bold; color:<?= $NAVY ?>;"><?= $e($title) ?></div>
            <div style="font-size:12px; margin-top:2px;">Tour <b><?= $e($j['tour_no']) ?></b><?= $j['tour_name'] ? ' · ' . $e($j['tour_name']) : '' ?></div>
            <div <?= $small ?>><?= $e($j['agent_name'] ?: '—') ?> · <?= $e($fmt($j['arrival_date'])) ?> – <?= $e($fmt($j['departure_date'])) ?> · <?= $pax ?> pax<?= !empty($d['arrival_flight']) ? ' · arrives ' . $e($d['arrival_flight']) : '' ?><?= !empty($d['departure_flight']) ? ' · departs ' . $e($d['departure_flight']) : '' ?></div>
        </td>
        <td width="38%" style="padding:0 0 8px 0; vertical-align:top; text-align:right;">
            <div style="font-size:12px; font-weight:bold;"><?= $e($co['name'] ?: 'Avenir Holidays') ?></div>
            <?php foreach (array_filter([$co['address'] ?? null, !empty($co['phone']) ? 'Tel ' . $co['phone'] : null, !empty($co['email']) ? $co['email'] : null]) as $line): ?><div <?= $small ?>><?= $e($line) ?></div><?php endforeach; ?>
            <div <?= $small ?> style="margin-top:4px;">Handling agent: <?= $e($j['handler_name'] ?: '—') ?></div>
        </td>
    </tr>
</table>
<table cellpadding="0" cellspacing="0" width="100%" style="margin-top:8px;">
    <tr>
        <td width="34%" <?= $td ?>><span <?= $small ?>>Vehicle</span><br><b><?= $e($vehicle ?: '—') ?></b></td>
        <td width="33%" <?= $td ?>><span <?= $small ?>>Driver</span><br><b><?= $e($who($driver)) ?></b></td>
        <td width="33%" <?= $td ?>><span <?= $small ?>>Guide</span><br><b><?= $e($guide ? $who($guide) : 'none on this card') ?></b></td>
    </tr>
</table>

<?php if ($kind === 'hotels'): ?>
<div <?= $h2 ?>>Hotels, night by night — as the Hotel Desk holds them today</div>
<table cellpadding="0" cellspacing="0" width="100%">
    <tr><th width="17%" <?= $th ?>>Nights</th><th width="30%" <?= $th ?>>Hotel and contact</th><th width="15%" <?= $th ?>>Board · rooms</th><th width="20%" <?= $th ?>>Booking</th><th width="18%" <?= $th ?>>Notes for the driver</th></tr>
    <?php foreach ($stays as $s): ?>
    <tr>
        <td <?= $td ?>><b>Night<?= $s['day_to'] > $s['day_from'] ? 's ' . $s['day_from'] . '–' . $s['day_to'] : ' ' . $s['day_from'] ?></b> <span <?= $small ?>>· <?= (int) $s['nights'] ?> night<?= (int) $s['nights'] === 1 ? '' : 's' ?></span><br><span <?= $small ?>>in <?= $e($s['check_in'] ? date('D j M', strtotime((string) $s['check_in'])) : '—') ?><br>out <?= $e($s['check_out'] ? date('D j M', strtotime((string) $s['check_out'])) : '—') ?></span></td>
        <td <?= $td ?>><b><?= $e($s['hotel'] ?: 'hotel not yet named') ?></b><?= $s['area'] ? ' <span ' . $small . '>· ' . $e($s['area']) . '</span>' : '' ?>
            <?php foreach (array_filter([$s['address'], $s['phone'] ? 'Tel ' . $s['phone'] : null, $s['mobile'] ? 'Mobile ' . $s['mobile'] : null, $s['contact'] ? 'Contact ' . $s['contact'] : null]) as $line): ?><br><span <?= $small ?>><?= $e($line) ?></span><?php endforeach; ?>
            <?php if (!$s['address'] && !$s['phone']): ?><br><span <?= $small ?>>no address or telephone on the supplier record</span><?php endif; ?></td>
        <td <?= $td ?>><?= $s['meal_plan'] ? $e($meals[strtoupper((string) $s['meal_plan'])] ?? $s['meal_plan']) : $tag('meal plan not stated', $AMB) ?><?= $s['rooms'] ? '<br>' . (int) $s['rooms'] . ' room' . ((int) $s['rooms'] === 1 ? '' : 's') : '' ?><?= $s['rooms_note'] ? '<br><span ' . $small . '>' . $e($s['rooms_note']) . '</span>' : '' ?></td>
        <td <?= $td ?>><?= $stayState($s) ?><?= $s['confirmed_with'] ? '<br><span ' . $small . '>with ' . $e($s['confirmed_with']) . '</span>' : '' ?></td>
        <td <?= $td ?>><?= $s['crew_rooms'] ? '<b>Crew rooms:</b> ' . $e($s['crew_rooms']) : '<span ' . $small . '>crew rooms: not stated</span>' ?><?= $s['special_note'] ? '<br><span ' . $small . '>' . $e($s['special_note']) . '</span>' : '' ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$stays): ?><tr><td colspan="5" <?= $td ?>><span <?= $small ?>>No stays on the Hotel Desk for this tour yet.</span></td></tr><?php endif; ?>
</table>
<div <?= $small ?> style="margin-top:8px;">A stay marked TO BOOK or REQUESTED is not confirmed: check with the office before the drive. Telephone numbers are the hotel's own from the supplier record.</div>

<?php elseif ($kind === 'allowance'): ?>
<?php $a = $allowance; ?>
<div <?= $h2 ?>>Bata — the days on the card × the bata a day</div>
<table cellpadding="0" cellspacing="0" width="100%">
    <tr><th width="18%" <?= $th ?>>Engaged as</th><th width="18%" <?= $th ?>>Name</th><th width="24%" <?= $th ?>>Days</th><th width="14%" <?= $th ?>>Bata a day</th><th width="14%" <?= $th ?>>Total</th><th width="12%" <?= $th ?>>Voucher</th></tr>
    <?php foreach ($a['people'] as $p): ?>
    <tr>
        <td <?= $td ?>><?= $e($p['role']) ?><br><span <?= $small ?>><?= $e($p['kind']) ?><?= $p['language'] ? ' · ' . $e($p['language']) : '' ?><?= $p['status'] === 'replaced' ? ' · replaced' : '' ?></span></td>
        <td <?= $td ?>><b><?= $e($p['name']) ?></b><?= $p['acc'] ? '<br><span ' . $small . '>ledger ' . $e($p['acc']) . '</span>' : '' ?></td>
        <td <?= $td ?>><?= $p['date_from'] && $p['date_to'] ? $e($fmt($p['date_from'])) . ' – ' . $e($fmt($p['date_to'])) . '<br>' : '' ?><?= $p['days'] !== null ? $p['days'] . ' day' . ($p['days'] === 1 ? '' : 's') . ' <span ' . $small . '>(' . ($p['days_source'] === 'voucher' ? 'as on the voucher' : ($p['days_source'] === 'worked' ? 'as set on the card' : 'as quoted')) . ')</span>' : $tag('days not set', $AMB) ?></td>
        <td <?= $td ?>><?= $p['rate'] !== null ? $e($p['currency']) . ' ' . $money($p['rate']) : '—' ?><?= $p['rate_source'] ? '<br><span ' . $small . '>' . ($p['rate_source'] === 'own' ? 'own agreed' : "the master's") . '</span>' : '<br>' . $tag('no bata', $AMB) ?></td>
        <td <?= $td ?>><b><?= $p['total'] !== null ? $e($p['currency']) . ' ' . $money($p['total']) : '—' ?></b></td>
        <td <?= $td ?>><?= $p['voucher'] ? $e($p['voucher']['voucher_no']) . '<br><span ' . $small . '>' . $e($p['voucher']['currency']) . ' ' . $money($p['voucher']['amount']) . '</span>' : '<span ' . $small . '>not issued</span>' ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$a['people']): ?><tr><td colspan="6" <?= $td ?>><span <?= $small ?>>Nobody engaged on this card yet.</span></td></tr><?php endif; ?>
</table>
<div <?= $h2 ?>>Rooms for the crew — as the quotation allowed</div>
<table cellpadding="0" cellspacing="0" width="100%">
    <tr><th width="20%" <?= $th ?>>For</th><th width="20%" <?= $th ?>>Room</th><th width="60%" <?= $th ?>>Note</th></tr>
    <?php foreach ($a['rooms'] as $r): ?>
    <tr><td <?= $td ?>><?= $e(ucfirst($r['kind'])) ?></td>
        <td <?= $td ?>><?= $r['required'] ? '<b>required</b>' . ($r['nights'] !== null ? ' · ' . $r['nights'] . ' night' . ($r['nights'] === 1 ? '' : 's') : '') . ($r['complimentary'] ? ' <span ' . $small . '>(' . $r['complimentary'] . ' complimentary)</span>' : '') : '<span ' . $small . '>not required</span>' ?></td>
        <td <?= $td ?>><?= $r['note'] ? $e($r['note']) : '<span ' . $small . '>where the hotel gives a crew room it is on the hotel list; otherwise the room is the crew\'s own arrangement unless the office says</span>' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$a['rooms']): ?><tr><td colspan="3" <?= $td ?>><span <?= $small ?>>No crew room line on the quotation.</span></td></tr><?php endif; ?>
</table>
<div <?= $h2 ?>>Kilometres on the schedule</div>
<table cellpadding="0" cellspacing="0" width="100%">
    <tr><td width="34%" <?= $td ?>><span <?= $small ?>>Schedule</span><br><b><?= $num($a['km']['total']) ?> km</b><?= $a['km']['extra'] !== null ? ' + ' . $num($a['km']['extra']) . ' km local running' : '' ?></td>
        <td width="22%" <?= $td ?>><span <?= $small ?>>Days on the card</span><br><b><?= $a['km']['days'] ?? '—' ?></b></td>
        <td width="44%" <?= $td ?>><span <?= $small ?>>Allowance legs (garage, idle, local)</span><br><?= $a['allowance_legs'] ? implode('<br>', array_map(static fn ($l) => 'Day ' . $l['day_no'] . ' · ' . esc(ucfirst($l['kind'])) . ': ' . $num($l['km']) . ' km', $a['allowance_legs'])) : '<span ' . $small . '>none</span>' ?></td></tr>
</table>
<div <?= $small ?> style="margin-top:8px;">The bata is what the card carries today; the payment voucher issued per person for the days actually worked is what the payment side pays against. Fuel, tolls and parking are settled on the log sheet after the tour.</div>
<table cellpadding="0" cellspacing="0" width="100%" style="margin-top:26px;">
    <tr>
        <td width="33%" style="padding:0 12px 0 0;"><div style="border-top:1px solid <?= $INK ?>; padding-top:4px; font-size:10px; color:<?= $MUTED ?>;">Prepared by (office) · date</div></td>
        <td width="33%" style="padding:0 12px;"><div style="border-top:1px solid <?= $INK ?>; padding-top:4px; font-size:10px; color:<?= $MUTED ?>;">Received by driver · date</div></td>
        <td width="34%" style="padding:0 0 0 12px;"><div style="border-top:1px solid <?= $INK ?>; padding-top:4px; font-size:10px; color:<?= $MUTED ?>;">Received by guide · date</div></td>
    </tr>
</table>

<?php elseif ($kind === 'log'): ?>
<?php
$KIND = ['transfer' => 'Transfer', 'local' => 'Local', 'arrival' => 'Arrival', 'departure' => 'Departure', 'positioning' => 'Positioning', 'idle' => 'Idle', 'garage' => 'Garage'];
$legs = array_values(array_filter($j['legs'] ?? [], static fn ($l) => !in_array((string) $l['kind'], ['garage', 'idle'], true)));
$blank = 'style="font-size:11px; padding:9px 6px; border-bottom:1px solid ' . $SOFT . '; vertical-align:top; background:#fbfbfc;"';
?>
<div <?= $h2 ?>>Log sheet — filled in by the driver, day by day, and handed back after the tour</div>
<table cellpadding="0" cellspacing="0" width="100%">
    <tr><th width="7%" <?= $th ?>>Day</th><th width="11%" <?= $th ?>>Date</th><th width="30%" <?= $th ?>>From → to (as scheduled)</th><th width="12%" <?= $th ?>>Meter start</th><th width="12%" <?= $th ?>>Meter end</th><th width="8%" <?= $th ?>>Km</th><th width="20%" <?= $th ?>>Fuel · tolls · remarks</th></tr>
    <?php foreach ($legs as $l): ?>
    <tr><td <?= $td ?>><?= (int) $l['day_no'] ?></td><td <?= $td ?>><?= $l['day_date'] ? $e(date('j M', strtotime((string) $l['day_date']))) : '' ?></td>
        <td <?= $td ?>><?= $e($l['from_place'] ?: '—') ?> → <b><?= $e($l['to_place'] ?: '—') ?></b><br><span <?= $small ?>><?= $e($KIND[$l['kind']] ?? $l['kind']) ?><?= $l['night_hotel'] ? ' · night at ' . $e($l['night_hotel']) : '' ?><?= $l['km'] !== null ? ' · scheduled ' . $num($l['km']) . ' km' : '' ?></span></td>
        <td <?= $blank ?>>&nbsp;</td><td <?= $blank ?>>&nbsp;</td><td <?= $blank ?>>&nbsp;</td><td <?= $blank ?>>&nbsp;</td></tr>
    <?php endforeach; ?>
    <?php for ($i = 0; $i < 4; $i++): ?>
    <tr><td <?= $blank ?>>&nbsp;</td><td <?= $blank ?>>&nbsp;</td><td <?= $blank ?>>&nbsp;</td><td <?= $blank ?>>&nbsp;</td><td <?= $blank ?>>&nbsp;</td><td <?= $blank ?>>&nbsp;</td><td <?= $blank ?>>&nbsp;</td></tr>
    <?php endfor; ?>
    <tr><td colspan="5" <?= $tdr ?>><b>Total kilometres driven</b></td><td <?= $blank ?>>&nbsp;</td><td <?= $blank ?>>&nbsp;</td></tr>
</table>
<div <?= $small ?> style="margin-top:8px;">Scheduled: <?= $num($j['km_total']) ?> km<?= $j['extra_km_total'] !== null ? ' + ' . $num($j['extra_km_total']) . ' km local running' : '' ?>. The office enters the kilometres from this sheet on the card after the tour; every later quotation on the same sectors learns from them.</div>
<table cellpadding="0" cellspacing="0" width="100%" style="margin-top:26px;">
    <tr>
        <td width="50%" style="padding:0 12px 0 0;"><div style="border-top:1px solid <?= $INK ?>; padding-top:4px; font-size:10px; color:<?= $MUTED ?>;">Driver's signature · date</div></td>
        <td width="50%" style="padding:0 0 0 12px;"><div style="border-top:1px solid <?= $INK ?>; padding-top:4px; font-size:10px; color:<?= $MUTED ?>;">Checked by (office) · date</div></td>
    </tr>
</table>
<?php elseif ($kind === 'settlement'): ?>
<?php
$st  = $j['settlement'] ?? null;
$cur = $st['currency'] ?? 'LKR';
$signed = static fn ($n): string => ($n === null ? '—' : (((float) $n < 0 ? '- ' : '') . number_format(abs((float) $n), 2)));
$basisWord = ['actual' => 'on the kilometres actually driven', 'scheduled' => 'on the schedule (actual kilometres not all entered)', 'costed' => 'as costed on the quotation'];
?>
<div <?= $h2 ?>>Driver's settlement<?= $st && $st['frozen'] ? ' — ' . $e($st['settlement_no']) . ', settled ' . $e($fmt(substr((string) $st['settled_at'], 0, 10))) . ' by ' . $e($st['settled_by']) : ' — as the card stands now, not yet settled' ?></div>
<?php if (!$st): ?>
<div <?= $small ?>>The settlement tables are not on this install yet.</div>
<?php else: ?>
<table cellpadding="0" cellspacing="0" width="100%">
    <tr><th width="70%" <?= $th ?>>Payable</th><th width="30%" <?= $th ?> style="text-align:right;"><?= $e($cur) ?></th></tr>
    <tr><td <?= $td ?>>Vehicle hire<?= $st['hire'] !== null ? ' <span ' . $small . '>' . $e($basisWord[$st['hire_basis']] ?? $st['hire_basis']) . ($st['hire_km'] !== null ? ' · ' . $num($st['hire_km']) . ' km' : '') . '</span>' : ' <span ' . $small . '>no rate per kilometre on this card</span>' ?></td><td <?= $tdr ?>><?= $st['hire'] !== null ? $money($st['hire']) : '—' ?></td></tr>
    <?php foreach ($st['bata_lines'] ?? [] as $b): ?>
    <tr><td <?= $td ?>>Bata — <?= $e($b['name']) ?> <span <?= $small ?>><?= $b['days'] !== null ? $b['days'] . ' day' . ((int) $b['days'] === 1 ? '' : 's') : 'days not set' ?><?= $b['rate'] !== null ? ' × ' . $money($b['rate']) : '' ?> · <?= $e($b['source']) ?></span></td><td <?= $tdr ?>><?= $b['amount'] !== null ? $money($b['amount']) : '—' ?></td></tr>
    <?php endforeach; ?>
    <tr><td <?= $td ?>><b>Total payable</b></td><td <?= $tdr ?>><b><?= $money($st['payable']) ?></b></td></tr>
    <?php foreach ($st['advance_lines'] ?? [] as $a): ?>
    <tr><td <?= $td ?>>Less advance <?= $e($a['advance_no']) ?> <span <?= $small ?>>paid <?= $e($fmt($a['paid_on'] ?? null)) ?><?= !empty($a['person_name']) ? ' to ' . $e($a['person_name']) : '' ?></span></td><td <?= $tdr ?>>- <?= $money($a['amount']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!($st['advance_lines'] ?? [])): ?><tr><td <?= $td ?>><span <?= $small ?>>No advance paid on this card.</span></td><td <?= $tdr ?>>—</td></tr><?php endif; ?>
    <?php foreach ($st['adjustment_lines'] ?? [] as $x): ?>
    <tr><td <?= $td ?>><?= (float) $x['amount'] >= 0 ? 'Plus' : 'Less' ?> <?= $e($x['label']) ?><?= !empty($x['note']) ? ' <span ' . $small . '>' . $e($x['note']) . '</span>' : '' ?></td><td <?= $tdr ?>><?= (float) $x['amount'] >= 0 ? '+ ' : '- ' ?><?= $money(abs((float) $x['amount'])) ?></td></tr>
    <?php endforeach; ?>
    <tr><td <?= $td ?> style="font-size:12.5px; padding:9px 6px; border-top:2px solid <?= $NAVY ?>; border-bottom:2px solid <?= $NAVY ?>;"><b>Balance <?= (float) $st['balance'] >= 0 ? 'due to the driver' : 'due from the driver' ?></b> <span <?= $small ?>>= payable - advances ± adjustments</span></td><td <?= $tdr ?> style="font-size:13px; padding:9px 6px; border-top:2px solid <?= $NAVY ?>; border-bottom:2px solid <?= $NAVY ?>;"><b><?= $e($cur) ?> <?= $money(abs((float) $st['balance'])) ?></b></td></tr>
</table>
<?php if (!empty($st['note'])): ?><div <?= $small ?> style="margin-top:6px;"><?= $e($st['note']) ?></div><?php endif; ?>
<?php if (!$st['frozen']): ?><div <?= $small ?> style="margin-top:6px;">A statement of where the card stands now. The figures are frozen, numbered and kept when the card is settled on the Transport Desk.</div><?php endif; ?>
<table cellpadding="0" cellspacing="0" width="100%" style="margin-top:26px;">
    <tr>
        <td width="33%" style="padding:0 12px 0 0;"><div style="border-top:1px solid <?= $INK ?>; padding-top:4px; font-size:10px; color:<?= $MUTED ?>;">Settled by (office) · date</div></td>
        <td width="33%" style="padding:0 12px;"><div style="border-top:1px solid <?= $INK ?>; padding-top:4px; font-size:10px; color:<?= $MUTED ?>;">Received by driver · date</div></td>
        <td width="34%" style="padding:0 0 0 12px;"><div style="border-top:1px solid <?= $INK ?>; padding-top:4px; font-size:10px; color:<?= $MUTED ?>;">Approved · date</div></td>
    </tr>
</table>
<?php endif; ?>
<?php endif; ?>

<div <?= $small ?> style="margin-top:18px;">One tour, one record: this sheet is read from the card and the desks as they stand on <?= date('j M Y H:i') ?>. <?= $kind === 'allowance' ? "The bata is the desk's own figure; there is no rate per kilometre, no hotel rate and no price on this sheet." : ($kind === 'settlement' ? 'The figures are the vehicle hire on the kilometres and the bata for the days as the card holds them; no rate per kilometre is printed.' : 'Nothing on it is a rate or a price.') ?></div>
<?php if (!empty($auto_print)): ?><script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script><?php endif; ?>
</body>
</html>
