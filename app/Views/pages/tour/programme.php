<?php
/**
 * The tour programme — what a desk prints once the inquiry is confirmed: the driver's
 * sheet, the hotel desk's rooming list, the file copy. The tour day by day with the
 * hotel, meal plan and rooms each night and what happens that day; the passengers; the
 * flights; the vehicle and the crew engaged; the agent's requests and their answers.
 * The data handed to this page (TourService::details) carries no cost, no rate, no
 * markup and no price, so nothing here could print them.
 */
$d     = $details;
$fmtD  = static fn ($x) => $x ? date('D j M Y', strtotime((string) $x)) : '—';
$meals = ['RO' => 'room only', 'BB' => 'bed & breakfast', 'HB' => 'half board', 'FB' => 'full board', 'AI' => 'all inclusive'];
$paid  = ['in_tour' => 'in the tour', 'prepaid' => 'prepaid', 'direct' => 'paid direct by the party', 'own_arrangement' => 'the party\'s own arrangement', 'not_arranged' => 'not arranged', 'no_charge' => 'no charge', 'unknown' => ''];
$st    = static fn (?array $x) => $x === null ? '' : (match ((string) $x['status']) {
    'confirmed'      => 'confirmed' . (!empty($x['ref']) || !empty($x['confirmation_ref']) ? ' · ref ' . ($x['ref'] ?? $x['confirmation_ref']) : ''),
    'voucher_issued' => 'voucher ' . ($x['voucher_no'] ?? 'issued'),
    'requested'      => 'requested',
    'not_needed'     => 'nothing to arrange',
    'pay_at_gate'    => 'PAY AT GATE' . (!empty($x['note']) || !empty($x['request_note']) ? ' — ' . ($x['note'] ?? $x['request_note']) : ''),
    'problem'        => 'PROBLEM' . (!empty($x['note']) || !empty($x['problem_note']) ? ' — ' . ($x['note'] ?? $x['problem_note']) : ''),
    'cancelled'      => 'cancelled',
    default          => 'to book',
});
$pax = trim(((int) ($d['pax_total'] ?? 0)) . ' pax' . ($d['pax_adults'] !== null || $d['pax_children'] !== null ? ' (' . (int) $d['pax_adults'] . ' adults' . ((int) $d['pax_children'] > 0 ? ', ' . (int) $d['pax_children'] . ' children' : '') . ')' : ''));
// as the itinerary copy in a driver's pack: headed so, for the one vehicle card it is handed over with,
// and as the HTML the PDF library takes (tables and inline styles; no toolbar)
$pdf   = !empty($for_pdf);
$pack  = $pack_title ?? null;
$card  = $vehicle_card ?? null;
$MUTED = '#5f6f80';
$sm    = 'style="font-size:11px; color:' . $MUTED . ';"';
$tdTop = 'style="vertical-align:top; padding:0;"';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= esc($d['tour_no']) ?> — <?= $pack ? esc($pack) : 'programme' ?></title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 12.5px; color: #1d2733; margin: 24px; }
    h1 { font-size: 20px; margin: 0 0 2px; } h2 { font-size: 13px; letter-spacing: .06em; text-transform: uppercase; color: #5f6f80; margin: 22px 0 6px; }
    .sub { color: #5f6f80; font-size: 12px; }
    .head { border-bottom: 2px solid #1e3553; margin-bottom: 4px; }
    .head td { padding: 0 0 10px; border: 0; }
    .cols > tbody > tr > td { padding: 0; border: 0; vertical-align: top; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: 11px; letter-spacing: .04em; text-transform: uppercase; color: #5f6f80; padding: 6px 8px; border-bottom: 1px solid #b9c2cc; }
    td { padding: 7px 8px; border-bottom: 1px solid #e3e8ee; vertical-align: top; }
    .kv { display: grid; grid-template-columns: 150px 1fr; gap: 3px 12px; }
    .kv b { color: #5f6f80; font-weight: 600; }
    .tag { display: inline-block; font-size: 10.5px; padding: 1px 7px; border-radius: 10px; background: #eef1f5; color: #5f6f80; white-space: nowrap; }
    .tag.ok { background: #e4f4ea; color: #1f8a4c; } .tag.bad { background: #fbe9e7; color: #b23a32; }
    .muted { color: #5f6f80; } .small { font-size: 11.5px; }
    .mark { display: inline-block; background: #1f8a4c; color: #fff; padding: 2px 9px; border-radius: 10px; font-weight: 700; font-size: 11px; }
    .toolbar { margin-bottom: 14px; } .toolbar button { font: inherit; padding: 6px 12px; }
    @media print { .toolbar { display: none; } body { margin: 10mm; } }
</style>
</head>
<body<?= $pdf ? ' style="font-family:Arial, Helvetica, sans-serif; font-size:11px; color:#1d2733;"' : '' ?>>
<?php if (!$pdf): ?><div class="toolbar"><button onclick="window.print()">Print / Save as PDF</button> <button onclick="history.back()" style="margin-left:6px">&larr; Back</button> <span class="muted small">Printed <?= date('j M Y H:i') ?> by <?= esc($user ?? '') ?></span></div><?php endif; ?>
<?php if (!empty($no_pdf_lib)): ?><div class="toolbar" style="font-size:12px;color:#b7791f;background:#fdf3e1;border:1px solid #f0d9a8;border-radius:8px;padding:8px 12px;">The PDF library is not installed on this server yet, so this is the print view: choose <b>Save as PDF</b> in the print dialog. (Ask the administrator to run <span style="font-family:monospace">composer install</span> — tecnickcom/tcpdf is already in composer.json.)</div><?php endif; ?>
<table cellpadding="<?= $pdf ? '3' : '0' ?>" cellspacing="0" width="100%"<?= $pdf ? '' : ' class="head" style="border-bottom:2px solid #1e3553;"' ?>>
    <tr>
        <td width="62%" <?= $tdTop ?>>
            <?php if ($pack): ?><div style="font-size:12px; <?= $pdf ? '' : 'letter-spacing:.06em; ' ?>text-transform:uppercase; color:#1e3553; font-weight:bold;"><?= esc($pack) ?> — driver's pack</div><?php endif; ?>
            <h1 style="font-size:20px; margin:0 0 2px;">Tour <?= esc($d['tour_no']) ?><?= $d['tour_name'] ? ' · ' . esc($d['tour_name']) : '' ?></h1>
            <div class="sub" <?= $sm ?>><?= esc($d['agent_name'] ?: '—') ?><?= $d['agent_acc'] ? ' · ' . esc($d['agent_acc']) : '' ?><?= $d['market_code'] ? ' · ' . esc($d['market_code']) . ' market' : '' ?> · inquiry <?= esc($d['inquiry_no'] ?? '') ?><?= $d['quote_version'] ? ' · quotation v' . (int) $d['quote_version'] : '' ?></div>
            <div class="sub" <?= $sm ?>><?= !empty($d['dates_line']) ? esc($d['dates_line']) : esc($fmtD($d['arrival_date'])) . ' – ' . esc($fmtD($d['departure_date'])) . ' · ' . (int) $d['nights'] . ' night' . ((int) $d['nights'] === 1 ? '' : 's') ?> · <?= esc($pax) ?><?= $d['rooms_note'] ? ' · ' . esc($d['rooms_note']) : '' ?><?= $d['hotel_class'] ? ' · ' . esc($d['hotel_class']) : '' ?></div>
            <?php if ($card): ?><div class="sub" <?= $sm ?>>This copy: <b><?= esc(trim($card['vehicle_type'] . ((int) ($card['qty'] ?? 1) > 1 ? ' × ' . (int) $card['qty'] : '') . ($card['vehicle_reg'] ? ' · ' . $card['vehicle_reg'] : ''))) ?></b><?php foreach ($card['crew'] ?? [] as $c): ?><?php if ((string) $c['status'] === 'engaged' && trim((string) ($c['person_name'] ?? '')) !== ''): ?> · <?= esc($c['role_name']) ?> <b><?= esc($c['person_name']) ?></b><?php endif; ?><?php endforeach; ?></div><?php endif; ?>
        </td>
        <td width="38%" <?= $tdTop ?> style="text-align:right; font-size:12px;">
            <div>Arrival: <b><?= esc($d['arrival_flight'] ?: 'flight not stated') ?></b></div>
            <div>Departure: <b><?= esc($d['departure_flight'] ?: 'flight not stated') ?></b></div>
            <div class="muted" <?= $sm ?>>Handling agent: <?= esc($d['handler_name'] ?: '—') ?> · confirmed <?= esc(substr((string) $d['confirmed_at'], 0, 10)) ?></div>
            <?php if (!empty($d['reservations_confirmed_at'])): ?><div style="margin-top:4px"><span class="mark" style="background:#1f8a4c; color:#fff; padding:2px 9px; font-weight:bold; font-size:11px;">Hotels <?= $pdf ? 'and' : '&amp;' ?> activities confirmed</span></div><?php endif; ?>
        </td>
    </tr>
</table>
<?php if ($pdf): ?><hr style="height:2px; color:#1e3553; background-color:#1e3553; border:0; margin:2px 0 6px 0;"><?php endif; ?>

<h2>Programme</h2>
<table>
    <thead><tr><th style="width:110px">Day</th><th>Programme</th><th style="width:34%">Night</th></tr></thead>
    <tbody>
    <?php foreach ($d['days'] as $day): ?>
        <tr>
            <td><b>Day <?= (int) $day['day_no'] ?></b><div class="small muted"><?= esc($fmtD($day['date'])) ?></div><?= $day['area'] ? '<div>' . esc($day['area']) . '</div>' : '' ?></td>
            <td>
                <?php if (!empty($day['wording'])): ?><div<?= $pack ? '' : ' class="muted"' ?> style="margin-bottom:3px;"><?= esc($day['wording']) ?></div><?php endif; ?>
                <?= $day['narrative'] && !$pack ? '<div class="small muted">Agent: ' . nl2br(esc($day['narrative'])) . '</div>' : '' ?>
                <?php foreach ($day['services'] as $sv): ?>
                    <div>• <?= esc($sv['title']) ?><span class="muted small"> — <?= esc($sv['kind']) ?><?= !empty($paid[$sv['pay_mode'] ?? '']) ? ', ' . esc($paid[$sv['pay_mode']]) : '' ?></span><?= $sv['desk'] ? ' <span class="tag ' . ($sv['desk']['status'] === 'problem' ? 'bad' : (in_array($sv['desk']['status'], ['confirmed', 'not_needed', 'pay_at_gate'], true) ? 'ok' : '')) . '">' . esc($st($sv['desk'])) . '</span>' : '' ?><?= $sv['note'] ? '<div class="small muted">' . esc($sv['note']) . '</div>' : '' ?></div>
                <?php endforeach; ?>
                <?php foreach ($day['excursions'] as $ex): ?>
                    <div>• <?= esc($ex['name']) ?><span class="muted small"> — excursion<?= (int) $ex['includes_transport'] ? ', transport included' : '' ?></span><?= $ex['desk'] ? ' <span class="tag ' . ($ex['desk']['status'] === 'problem' ? 'bad' : (in_array($ex['desk']['status'], ['confirmed', 'not_needed', 'pay_at_gate'], true) ? 'ok' : '')) . '">' . esc($st($ex['desk'])) . '</span>' : '' ?><?= $ex['note'] ? '<div class="small muted">' . esc($ex['note']) . '</div>' : '' ?></div>
                <?php endforeach; ?>
                <?php foreach ($day['desk_lines'] as $l): ?>
                    <div>• <?= esc($l['title']) ?><span class="muted small"> — added by the desk</span> <span class="tag <?= $l['status'] === 'problem' ? 'bad' : (in_array($l['status'], ['confirmed', 'not_needed', 'pay_at_gate'], true) ? 'ok' : '') ?>"><?= esc($st($l)) ?></span></div>
                <?php endforeach; ?>
                <?= $day['special_note'] ? '<div class="small"><b>Note:</b> ' . esc($day['special_note']) . '</div>' : '' ?>
            </td>
            <td>
                <?php if ($day['hotel']): ?>
                    <b><?= esc($day['hotel']) ?></b>
                    <div class="small"><?= $day['meal_plan'] ? esc($meals[strtoupper((string) $day['meal_plan'])] ?? $day['meal_plan']) : '<span class="tag bad">meal plan not stated</span>' ?><?= $day['rooms'] ? ' · ' . (int) $day['rooms'] . ' room' . ((int) $day['rooms'] === 1 ? '' : 's') : '' ?><?= $day['rooms_note'] ? ' · ' . esc($day['rooms_note']) : '' ?></div>
                    <?php if ($day['stay']): ?><div class="small"><span class="tag <?= $day['stay']['status'] === 'problem' ? 'bad' : (in_array($day['stay']['status'], ['confirmed', 'voucher_issued'], true) ? 'ok' : '') ?>"><?= esc($st($day['stay'])) ?></span></div><?php endif; ?>
                <?php else: ?>
                    <span class="tag bad">no hotel named</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if ($d['days'] && (!empty($d['departure_wording']) || !empty($d['departure_services']) || !empty($d['departure_excursions']) || !empty($d['departure_desk_lines']))): ?>
        <tr>
            <td><b>Day <?= (int) ($d['departure_day_no'] ?? ((int) end($d['days'])['day_no'] + 1)) ?></b><div class="small muted"><?= esc($fmtD($d['departure_date'] ?? null)) ?></div><div>Departure</div></td>
            <td colspan="2">
                <?php if (!empty($d['departure_wording'])): ?><div<?= $pack ? '' : ' class="muted"' ?> style="margin-bottom:3px;"><?= esc($d['departure_wording']) ?></div><?php endif; ?>
                <?php foreach ($d['departure_services'] ?? [] as $sv): ?>
                    <div>• <?= esc($sv['title']) ?><span class="muted small"> — <?= esc($sv['kind']) ?><?= !empty($paid[$sv['pay_mode'] ?? '']) ? ', ' . esc($paid[$sv['pay_mode']]) : '' ?></span><?= $sv['desk'] ? ' <span class="tag ' . ($sv['desk']['status'] === 'problem' ? 'bad' : (in_array($sv['desk']['status'], ['confirmed', 'not_needed', 'pay_at_gate'], true) ? 'ok' : '')) . '">' . esc($st($sv['desk'])) . '</span>' : '' ?><?= $sv['note'] ? '<div class="small muted">' . esc($sv['note']) . '</div>' : '' ?></div>
                <?php endforeach; ?>
                <?php foreach ($d['departure_excursions'] ?? [] as $ex): ?>
                    <div>• <?= esc($ex['name']) ?><span class="muted small"> — excursion<?= (int) $ex['includes_transport'] ? ', transport included' : '' ?></span><?= $ex['desk'] ? ' <span class="tag ' . ($ex['desk']['status'] === 'problem' ? 'bad' : (in_array($ex['desk']['status'], ['confirmed', 'not_needed', 'pay_at_gate'], true) ? 'ok' : '')) . '">' . esc($st($ex['desk'])) . '</span>' : '' ?><?= $ex['note'] ? '<div class="small muted">' . esc($ex['note']) . '</div>' : '' ?></div>
                <?php endforeach; ?>
                <?php foreach ($d['departure_desk_lines'] ?? [] as $l): ?>
                    <div>• <?= esc($l['title']) ?><span class="muted small"> — added by the desk</span> <span class="tag <?= $l['status'] === 'problem' ? 'bad' : (in_array($l['status'], ['confirmed', 'not_needed', 'pay_at_gate'], true) ? 'ok' : '') ?>"><?= esc($st($l)) ?></span></div>
                <?php endforeach; ?>
                <?= !empty($d['departure_note']) ? '<div class="small"><b>Note:</b> ' . esc($d['departure_note']) . '</div>' : '' ?>
                <div class="small muted">No hotel: the transfer to the airport for the flight.</div>
            </td>
        </tr>
    <?php endif; ?>
    <?php if (!$d['days']): ?><tr><td colspan="3" class="muted">No nights on the quotation.</td></tr><?php endif; ?>
    </tbody>
</table>
<?php if ($d['tour_services'] || $d['tour_desk_lines']): ?>
    <div class="small" style="margin-top:6px"><b>On the tour as a whole:</b>
    <?php foreach ($d['tour_services'] as $sv): ?><span>• <?= esc($sv['title']) ?><?= $sv['desk'] ? ' (' . esc($st($sv['desk'])) . ')' : '' ?></span> <?php endforeach; ?>
    <?php foreach ($d['tour_desk_lines'] as $l): ?><span>• <?= esc($l['title']) ?> (<?= esc($st($l)) ?>)</span> <?php endforeach; ?>
    </div>
<?php endif; ?>

<table cellpadding="<?= $pdf ? '3' : '0' ?>" cellspacing="0" width="100%"<?= $pdf ? '' : ' class="cols"' ?>><tr>
    <td width="50%" <?= $tdTop ?> style="padding-right:14px;">
        <h2>Passengers</h2>
        <?php if ($d['passengers']): ?>
            <table><thead><tr><th>#</th><th>Name</th><th>Room</th><th>Note</th></tr></thead><tbody>
            <?php foreach ($d['passengers'] as $i => $p): ?><tr><td><?= $i + 1 ?></td><td><?= esc($p['full_name']) ?></td><td><?= esc($p['room_type'] ?: '') ?></td><td class="small muted"><?= esc($p['note'] ?: '') ?></td></tr><?php endforeach; ?>
            </tbody></table>
        <?php else: ?><div class="muted">No names on the quotation yet.</div><?php endif; ?>
    </td>
    <td width="50%" <?= $tdTop ?> style="padding-left:14px;">
        <h2>Vehicle <?= $pdf ? 'and' : '&amp;' ?> crew</h2>
        <?php if ($d['vehicles']): ?>
            <?php foreach ($d['vehicles'] as $v): ?>
                <div><b><?= esc($v['vehicle_type']) ?></b><?= (int) $v['qty'] > 1 ? ' × ' . (int) $v['qty'] : '' ?> · <?= $v['vehicle_reg'] ? esc($v['vehicle_reg']) : '<span class="tag">no vehicle yet</span>' ?> <span class="tag"><?= esc(str_replace('_', ' ', (string) $v['status'])) ?></span><?= $v['vehicle_note'] ? '<div class="small muted">' . esc($v['vehicle_note']) . '</div>' : '' ?></div>
                <?php foreach ($v['crew'] as $c): ?>
                    <div class="small" style="margin-left:12px"><?= esc($c['role_name']) ?>: <?= $c['person_name'] ? '<b>' . esc($c['person_name']) . '</b>' : '<span class="tag">nobody yet</span>' ?><?= $c['status'] === 'engaged' ? ' <span class="tag ok">engaged</span>' : ($c['status'] === 'declined' ? ' <span class="tag bad">declined</span>' : ($c['status'] === 'replaced' ? ' <span class="tag">replaced</span>' : '')) ?><?= !empty($c['date_from']) && !empty($c['date_to']) ? ' · ' . esc($c['date_from']) . ' to ' . esc($c['date_to']) : ($c['days'] ? ' · ' . (int) $c['days'] . ' day(s)' : '') ?></div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php else: ?><div class="muted">No vehicle line on the quotation.</div><?php endif; ?>
    </td>
</tr></table>

<?php if ($d['requests']): ?>
    <h2>The agent's requests</h2>
    <table><thead><tr><th style="width:90px">Kind</th><th>Request</th><th style="width:40%">Answer</th></tr></thead><tbody>
    <?php foreach ($d['requests'] as $r): ?><tr><td><?= esc($r['kind']) ?><div class="small muted"><?= esc($r['status']) ?></div></td><td><?= esc($r['title']) ?></td><td class="small"><?= $r['answer'] ? esc($r['answer']) . '<div class="muted">' . esc($r['answered_by'] ?: '') . ' ' . esc(substr((string) $r['answered_at'], 0, 10)) . '</div>' : '<span class="muted">—</span>' ?></td></tr><?php endforeach; ?>
    </tbody></table>
<?php endif; ?>
<?php if ($d['note']): ?><h2>Notes</h2><div><?= nl2br(esc($d['note'])) ?></div><?php endif; ?>
<div class="small muted" style="margin-top:22px">One tour, one record: this sheet is read from the confirmed inquiry and the desks' cards as they stand now. Nothing on it is a price.</div>
<?php if (!empty($auto_print)): ?><script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script><?php endif; ?>
</body>
</html>
