<?php
/**
 * The client quotation — what the agent receives. Avenir Holidays letterhead, the
 * itinerary day by day, what is included, the price. No cost, no markup, no profit,
 * no rate per kilometre, no bata, no special rate: the data handed to this page has
 * none of those (QuoteService::clientView), so nothing here could print them.
 *
 * One template for the screen and for the PDF ($for_pdf): on paper it is plain tables
 * with inline styles, which is what TCPDF draws faithfully.
 *
 * Below the price, the price per person by number of travellers (schema 31): the size, the
 * rooms it is based on, the price — from QuoteService::clientView, which hands this page
 * nothing else of the pax matrix.
 *
 * The pictures ($photos, schema 27) come from Master Files → Tour Photos: one for each day
 * whose programme the library has a picture for, and up to three across the top. Without
 * the library, or with nothing chosen, the page is exactly as before.
 */
$pdf  = !empty($for_pdf);
$c    = $cost;
$q    = $quote;
$co   = $company ?? ['name' => 'Avenir Holidays'];
$ref  = ($c['inquiry_no'] ?? '') . ($q ? ' / v' . (int) $q['version'] : '');
$date = $q ? substr((string) ($q['issued_at'] ?: $q['created_at']), 0, 10) : date('Y-m-d');
$fmtD = static fn ($d) => $d ? date('j M Y', strtotime((string) $d)) : '—';
$usd  = static fn ($v) => $v === null ? '—' : 'USD ' . number_format((float) $v, 2);
$mix  = $c['rooms'] ?? [];
$mixWords = [];
foreach ([['sgl', 'single'], ['dbl', 'double'], ['twn', 'twin'], ['tpl', 'triple']] as [$k, $w]) {
    if ((int) ($mix[$k] ?? 0) > 0) {
        $mixWords[] = (int) $mix[$k] . ' ' . $w . ((int) $mix[$k] > 1 ? ' rooms' : ' room');
    }
}
$meals = ['RO' => 'room only', 'BB' => 'bed & breakfast', 'HB' => 'half board', 'FB' => 'full board', 'AI' => 'all inclusive'];
$included = [];
$direct   = [];
foreach ($c['days'] as $d) {
    foreach ($d['services'] as $s) {
        if ($s['included']) {
            $included[] = $s['title'];
        } elseif ($s['note'] === 'payable direct') {
            $direct[] = $s['title'];
        }
    }
    foreach ($d['excursions'] as $x) {
        $included[] = $x['name'];
    }
}
$vehicles = array_map(static fn ($v) => ($v['qty'] > 1 ? $v['qty'] . ' × ' : '') . $v['type'], $c['vehicles']);
// the itinerary as written (schema 28): a paragraph a day; a version frozen before it carries none and reads as before
$itin     = is_array($c['itinerary'] ?? null) ? $c['itinerary'] : ['days' => [], 'departure' => null];
$dayText  = static fn (int $n): string => trim((string) ($itin['days'][$n]['text'] ?? $itin['days'][(string) $n]['text'] ?? ''));
$depText  = trim((string) ($itin['departure']['text'] ?? ''));
$P = '#842484'; $M = '#e90c8b'; $INK = '#2c3e50'; $MUTED = '#6c757d'; $LINE = '#e6dbe6';
// the day pictures from the tour photo library (schema 27): a presentation layer — absent, the page is as it always was
$ph        = $photos ?? [];
$dayPhotos = is_array($ph['days'] ?? null) ? $ph['days'] : [];
$coverPics = is_array($ph['cover'] ?? null) ? $ph['cover'] : [];
$photoDir  = (string) ($ph['dir'] ?? '');
$hasPhotos = false;
foreach ($dayPhotos as $dp) {
    if (!empty($dp['photo_id'])) {
        $hasPhotos = true;
        break;
    }
}
$onPaper = static fn (array $p): bool => $photoDir !== '' && !empty($p['file']) && is_file($photoDir . $p['file']);
// one picture, on screen by its URL and on paper by its file; $w is the width in pixels on paper
$picTag = static function (array $p, int $w, string $style) use ($pdf, $photoDir, $onPaper): string {
    if (empty($p['photo_id'])) {
        return '';
    }
    $alt = esc($p['caption'] ?: ($p['tag_name'] ?? ''));
    if ($pdf) {
        if (!$onPaper($p)) {
            return '';
        }
        $h = !empty($p['width']) && !empty($p['height']) ? (int) round($w * (int) $p['height'] / max(1, (int) $p['width'])) : (int) round($w * 2 / 3);
        return '<img src="' . $photoDir . $p['file'] . '" width="' . $w . '" height="' . $h . '" alt="' . $alt . '">';
    }
    return '<img src="' . esc((string) ($p['url'] ?? '')) . '" style="' . $style . '" alt="' . $alt . '">';
};
$logo = $logo ?? '';
$logoTag = '';
if ($logo && is_file($logo)) {
    $logoTag = $pdf
        ? '<img src="' . $logo . '" width="120">'
        : '<img src="data:image/png;base64,' . base64_encode((string) file_get_contents($logo)) . '" style="width:150px;height:auto" alt="' . esc($co['name']) . '">';
}
?>
<?php if (!$pdf): ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Quotation <?= esc($ref) ?> — <?= esc($co['name']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { margin:0; background:#eceff3; font-family:"Segoe UI", Helvetica, Arial, sans-serif; color:<?= $INK ?>; font-size:13px; }
    .bar { position:sticky; top:0; z-index:5; display:flex; gap:8px; align-items:center; padding:10px 18px; background:#fff; border-bottom:1px solid #dfe5ec; box-shadow:0 2px 8px rgba(0,0,0,.05); }
    .bar a, .bar button { font:inherit; font-size:12.5px; font-weight:600; padding:7px 12px; border-radius:8px; border:1px solid #dfe5ec; background:#fff; color:<?= $INK ?>; text-decoration:none; cursor:pointer; }
    .bar a:hover, .bar button:hover { background:#f4f6f9; }
    .bar .primary { background:linear-gradient(135deg, <?= $M ?> 0%, #8f2a8d 100%); color:#fff; border:0; }
    .bar .note { margin-left:auto; font-size:12px; color:<?= $MUTED ?>; }
    .bar .preview { background:#fdf3e1; color:#b7791f; border-color:#f0d9a8; }
    .sheet { width:210mm; min-height:297mm; margin:22px auto; background:#fff; padding:18mm 16mm; box-shadow:0 8px 30px rgba(0,0,0,.08); box-sizing:border-box; }
    table { border-collapse:collapse; }
    .staff, .mailbox { font-family:"Segoe UI", Helvetica, Arial, sans-serif; }
    .mailbox { width:210mm; margin:0 auto 40px; background:#fff; border:1px solid #dfe5ec; border-radius:10px; padding:16px 18px; box-sizing:border-box; box-shadow:0 8px 30px rgba(0,0,0,.05); }
    .mailbox h2 { font-size:15px; margin:0 0 4px; color:<?= $P ?>; }
    .mailbox .sub { font-size:12px; color:<?= $MUTED ?>; margin:0 0 12px; }
    .mailbox label { display:block; font-size:11.5px; font-weight:600; color:<?= $MUTED ?>; margin:8px 0 3px; }
    .mailbox input, .mailbox textarea { width:100%; box-sizing:border-box; font:inherit; font-size:13px; padding:7px 9px; border:1px solid #cfd6de; border-radius:7px; background:#fff; color:<?= $INK ?>; }
    .mailbox textarea { min-height:190px; resize:vertical; line-height:1.45; }
    .mailbox .row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .mailbox .acts { display:flex; gap:8px; align-items:center; margin-top:12px; flex-wrap:wrap; }
    .mailbox .acts button { font:inherit; font-size:12.5px; font-weight:600; padding:8px 14px; border-radius:8px; border:1px solid #dfe5ec; background:#fff; color:<?= $INK ?>; cursor:pointer; }
    .mailbox .acts button.send { background:linear-gradient(135deg, <?= $M ?> 0%, #8f2a8d 100%); color:#fff; border:0; }
    .mailbox .acts button[disabled] { opacity:.55; cursor:not-allowed; }
    .mailbox .warn { background:#fdf3e1; color:#8a5a00; border:1px solid #f0d9a8; border-radius:8px; padding:8px 12px; font-size:12.5px; margin-top:10px; }
    .mailbox .ok { background:#e6f4ea; color:#1e6b34; border:1px solid #bfe3c9; border-radius:8px; padding:8px 12px; font-size:12.5px; margin-top:10px; }
    .mailbox .err { background:#fbe9e6; color:#8a2a22; border:1px solid #f0b8b0; border-radius:8px; padding:8px 12px; font-size:12.5px; margin-top:10px; }
    .mailbox .hist { font-size:12px; color:<?= $MUTED ?>; margin-top:12px; border-top:1px solid #eef2f6; padding-top:8px; }
    .mailbox .hist div { padding:3px 0; }
    .overlay { position:fixed; inset:0; background:rgba(20,30,40,.45); display:none; align-items:flex-start; justify-content:center; padding:40px 16px; z-index:50; overflow:auto; }
    .overlay.on { display:flex; }
    .modal { background:#fff; border-radius:12px; width:min(760px, 100%); box-shadow:0 20px 60px rgba(0,0,0,.25); }
    .modal .hd { padding:14px 18px; border-bottom:1px solid #eef2f6; display:flex; align-items:center; gap:10px; }
    .modal .hd h3 { margin:0; font-size:15px; color:<?= $P ?>; flex:1; }
    .modal .hd button { font:inherit; border:0; background:none; font-size:20px; cursor:pointer; color:<?= $MUTED ?>; }
    .modal .env { padding:12px 18px; font-size:12.5px; border-bottom:1px solid #eef2f6; display:grid; grid-template-columns:90px 1fr; gap:4px 10px; }
    .modal .env b { color:<?= $MUTED ?>; font-weight:600; }
    .modal .body { padding:16px 18px; }
    .modal .att { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border:1px solid #dfe5ec; border-radius:8px; background:#f4f6f9; font-size:12.5px; text-decoration:none; color:<?= $INK ?>; }
    .modal .ft { padding:12px 18px; border-top:1px solid #eef2f6; display:flex; gap:8px; justify-content:flex-end; }
    .modal .ft button { font:inherit; font-size:12.5px; font-weight:600; padding:8px 14px; border-radius:8px; border:1px solid #dfe5ec; background:#fff; cursor:pointer; }
    .modal .ft button.send { background:linear-gradient(135deg, <?= $M ?> 0%, #8f2a8d 100%); color:#fff; border:0; }
    @media print { body { background:#fff; } .bar, .staff, .mailbox, .overlay { display:none !important; } .sheet { margin:0; box-shadow:none; width:auto; min-height:0; padding:0; } }
</style></head><body>
<div class="bar">
    <a href="<?= base_url($intake_url ?? 'tour/intake') ?>?id=<?= (int) $intake_id ?>">&larr; Back to the inquiry</a>
    <a href="?view=internal">Internal costing</a>
    <button onclick="window.print()">Print</button>
    <a class="primary" href="?view=client&pdf=1" target="_blank">Download PDF</a>
    <?php if (!empty($preview)): ?><span class="preview" style="padding:7px 12px;border-radius:8px;font-size:12px;font-weight:600">Preview — follows the inquiry as it stands; nothing is issued</span>
    <?php elseif ($q): ?><span class="note">Version <?= (int) $q['version'] ?> · <?= esc($q['status']) ?><?= $q['issued_at'] ? ' · issued ' . esc(substr((string) $q['issued_at'], 0, 16)) . ' by ' . esc((string) $q['issued_by']) : '' ?></span><?php endif; ?>
</div>
<?php if (!empty($no_pdf_lib)): ?><div style="width:210mm;margin:14px auto -8px;font-size:12px;color:#b7791f;background:#fdf3e1;border:1px solid #f0d9a8;border-radius:8px;padding:8px 12px;">The PDF library is not installed on this server yet, so this is the print view: choose <b>Save as PDF</b> in the print dialog. (Ask the administrator to run <span style="font-family:monospace">composer install</span> — tecnickcom/tcpdf is already in composer.json.)</div><?php endif; ?>
<div class="sheet">
<?php endif; ?>

<table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:2px solid <?= $P ?>;">
    <tr>
        <td width="35%" style="padding:0 0 10px 0; vertical-align:bottom;"><?= $logoTag ?: '<span style="font-size:22px;font-weight:bold;color:' . $P . '">' . esc($co['name']) . '</span>' ?></td>
        <td width="65%" align="right" style="padding:0 0 10px 0; vertical-align:bottom; font-size:11px; color:<?= $MUTED ?>; line-height:1.5;">
            <b style="font-size:13px;color:<?= $INK ?>"><?= esc($co['name']) ?></b><br>
            <?= !empty($co['address']) ? esc($co['address']) . '<br>' : '' ?>
            <?= implode(' &nbsp;·&nbsp; ', array_filter([!empty($co['phone']) ? esc($co['phone']) : '', !empty($co['email']) ? esc($co['email']) : '', !empty($co['web']) ? esc($co['web']) : ''])) ?>
        </td>
    </tr>
</table>

<table width="100%" cellpadding="4" cellspacing="0" style="margin-top:14px;">
    <tr>
        <td width="60%" style="vertical-align:top;">
            <span style="font-size:24px; font-weight:bold; color:<?= $P ?>;">Quotation</span><br>
            <span style="font-size:12px; color:<?= $MUTED ?>;">Reference <b style="color:<?= $INK ?>"><?= esc($ref) ?></b> &nbsp;·&nbsp; <?= $fmtD($date) ?></span>
            <?php if ($q && !empty($q['valid_until'])): ?><br><span style="font-size:12px; color:<?= $MUTED ?>;">Valid until <b style="color:<?= $INK ?>"><?= $fmtD($q['valid_until']) ?></b></span><?php endif; ?>
        </td>
        <td width="40%" style="vertical-align:top; font-size:12px; line-height:1.6;">
            <b>To:</b> <?= esc($c['agent_name'] ?: '—') ?><br>
            <b>Tour:</b> <?= esc($c['tour_name'] ?: '—') ?><br>
            <b>Party:</b> <?= (int) $c['pax'] ?> passenger<?= (int) $c['pax'] === 1 ? '' : 's' ?><?= $mixWords ? ' — ' . esc(implode(', ', $mixWords)) : '' ?><br>
            <?php if (!empty($c['tour_dates']['line'])): ?><b>Dates:</b> <?= esc($c['tour_dates']['line']) ?>
            <?php else: ?><b>Dates:</b> <?= $fmtD($c['arrival']) ?> to <?= $fmtD($c['departure']) ?> &nbsp;(<?= (int) $c['nights'] ?> night<?= (int) $c['nights'] === 1 ? '' : 's' ?>)<?php endif; ?>
        </td>
    </tr>
</table>

<?php if ($coverPics): ?>
<table width="100%" cellpadding="3" cellspacing="0" style="margin-top:12px;" class="cover">
    <tr>
        <?php $cw = (int) floor(100 / count($coverPics)); foreach ($coverPics as $cp): ?>
        <?php // on paper every cover picture is 140px (about 40 mm) tall, its width from its own shape, three across at most
        $pw = !empty($cp['width']) && !empty($cp['height']) ? (int) round(140 * (int) $cp['width'] / max(1, (int) $cp['height'])) : 187; ?>
        <td width="<?= $cw ?>%" style="vertical-align:top; text-align:center;"><?= $picTag($cp, min($pw, 210), 'width:100%;height:118px;object-fit:cover;border-radius:6px;display:block') ?><?= $cp['caption'] ? '<div style="font-size:9.5px;color:' . $MUTED . ';margin-top:2px;">' . esc($cp['caption']) . '</div>' : '' ?></td>
        <?php endforeach; ?>
    </tr>
</table>
<?php endif; ?>

<?php if (count($c['groups']) > 1): ?>
<table width="100%" cellpadding="5" cellspacing="0" style="margin-top:10px; font-size:11.5px; border:1px solid <?= $LINE ?>;">
    <tr style="background:#faf5fa;"><td colspan="3" style="font-weight:bold; color:<?= $P ?>;">The party travels in <?= count($c['groups']) ?> groups</td></tr>
    <?php foreach ($c['groups'] as $g): ?>
    <tr><td width="40%"><b><?= esc($g['name']) ?></b></td><td width="20%"><?= (int) $g['pax'] ?> passenger<?= (int) $g['pax'] === 1 ? '' : 's' ?></td>
        <td width="40%"><?= $g['day_from'] || $g['day_to'] ? 'nights ' . (int) ($g['day_from'] ?: 1) . ' to ' . (int) ($g['day_to'] ?: $c['nights']) : 'the whole tour' ?></td></tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h3 style="font-size:14px; color:<?= $P ?>; margin:20px 0 6px 0; border-bottom:1px solid <?= $LINE ?>; padding-bottom:4px;">Itinerary</h3>
<table width="100%" cellpadding="6" cellspacing="0" style="font-size:11.5px;">
    <?php $depRow = false; foreach ($c['days'] as $d): $dp = $dayPhotos[(int) $d['day_no']] ?? null;
        // a row past the nights the dates give is the departure day written as a night: it reads as the departure
        $isDep = (int) $c['nights'] > 0 && (int) $d['day_no'] > (int) $c['nights'] && !$d['hotel'];
        $depRow = $depRow || $isDep; ?>
    <tr style="border-bottom:1px solid <?= $LINE ?>;">
        <td width="<?= $hasPhotos ? '15%' : '17%' ?>" style="vertical-align:top; border-bottom:1px solid <?= $LINE ?>;"><b style="color:<?= $P ?>;">Day <?= (int) $d['day_no'] ?></b><br><span style="color:<?= $MUTED ?>;"><?= $fmtD($d['date']) ?></span></td>
        <?php if ($hasPhotos): ?><td width="24%" style="vertical-align:top; border-bottom:1px solid <?= $LINE ?>; text-align:center;" class="daypic"><?= $dp ? $picTag($dp, 150, 'width:100%;max-width:170px;height:auto;border-radius:4px;display:inline-block') : '' ?><?= $dp && !empty($dp['photo_id']) && $dp['caption'] ? '<div style="font-size:9.5px;color:' . $MUTED . ';margin-top:2px;">' . esc($dp['caption']) . '</div>' : '' ?></td><?php endif; ?>
        <td width="<?= $hasPhotos ? '61%' : '83%' ?>" style="vertical-align:top; border-bottom:1px solid <?= $LINE ?>;">
            <?php if ($isDep): ?>
            <b><?= esc($d['area'] ?: '') ?></b>
            <div class="daytext" style="margin-top:4px; line-height:1.5;"><?= $depText !== '' ? nl2br(esc($depText)) : 'Transfer to the airport for departure.' ?></div>
            <?php else: ?>
            <b><?= esc($d['area'] ?: '') ?></b><?= $d['hotel'] ? ($d['area'] ? ' — ' : '') . esc($d['hotel']) : '' ?>
            <?php if ($d['room_type'] || $d['meal_plan']): ?><br><span style="color:<?= $MUTED ?>;"><?= esc($d['room_type'] ?: '') ?><?= $d['room_type'] && $d['meal_plan'] ? ' · ' : '' ?><?= esc($meals[$d['meal_plan']] ?? ($d['meal_plan'] ?: '')) ?></span><?php endif; ?>
            <?php if ($dayText((int) $d['day_no']) !== ''): ?>
                <div class="daytext" style="margin-top:4px; line-height:1.5;"><?= nl2br(esc($dayText((int) $d['day_no']))) ?></div>
                <?php // what the priced list and the excursion master say about a stop, and anything payable direct or the party's own
                foreach (array_merge($d['services'], $d['excursions']) as $s): $name = $s['title'] ?? $s['name']; if (!empty($s['description']) || !empty($s['note'])): ?>
                    <div style="color:<?= $MUTED ?>; font-size:10.5px; margin-top:2px;"><i><?= esc($name) ?></i><?= !empty($s['note']) ? ' (' . esc($s['note']) . ')' : '' ?><?= !empty($s['description']) ? ' — ' . nl2br(esc($s['description'])) : '' ?></div>
                <?php endif; endforeach; ?>
            <?php else: ?>
            <?php foreach ($d['services'] as $s): ?>
                <br><span style="color:<?= $INK ?>;">&#8226; <?= esc($s['title']) ?><?= $s['note'] ? ' <i style="color:' . $MUTED . '">(' . esc($s['note']) . ')</i>' : '' ?></span>
                <?php if (!empty($s['description'])): ?><br><span style="color:<?= $MUTED ?>; font-size:11px;"><?= nl2br(esc($s['description'])) ?></span><?php endif; ?>
            <?php endforeach; ?>
            <?php foreach ($d['excursions'] as $x): ?>
                <br><span style="color:<?= $INK ?>;">&#8226; <?= esc($x['name']) ?><?= !empty($x['includes_transport']) ? ' <i style="color:' . $MUTED . '">(transport included)</i>' : '' ?></span>
                <?php if (!empty($x['description'])): ?><br><span style="color:<?= $MUTED ?>; font-size:11px;"><?= nl2br(esc($x['description'])) ?></span><?php endif; ?>
            <?php endforeach; ?>
            <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$depRow): ?>
    <tr><td width="<?= $hasPhotos ? '15%' : '17%' ?>" style="vertical-align:top;"><b style="color:<?= $P ?>;">Day <?= (int) $c['nights'] + 1 ?></b><br><span style="color:<?= $MUTED ?>;"><?= $fmtD($c['departure']) ?></span></td>
        <?php if ($hasPhotos): ?><td width="24%"></td><?php endif; ?>
        <td width="<?= $hasPhotos ? '61%' : '83%' ?>" style="vertical-align:top;" class="daytext"><?= $depText !== '' ? nl2br(esc($depText)) : 'Transfer to the airport for departure.' ?></td></tr>
    <?php endif; ?>
</table>

<h3 style="font-size:14px; color:<?= $P ?>; margin:20px 0 6px 0; border-bottom:1px solid <?= $LINE ?>; padding-bottom:4px;">The price includes</h3>
<table width="100%" cellpadding="3" cellspacing="0" style="font-size:11.5px;">
    <tr><td width="4%" style="vertical-align:top;">&#8226;</td><td width="96%">Accommodation for <?= (int) $c['nights'] ?> night<?= (int) $c['nights'] === 1 ? '' : 's' ?> in the hotels and room types listed, on the meal basis shown<?= $mixWords ? ', in ' . esc(implode(', ', $mixWords)) : '' ?>.</td></tr>
    <?php if ($vehicles): ?><tr><td style="vertical-align:top;">&#8226;</td><td>Transport throughout by <?= esc(implode(' and ', $vehicles)) ?><?= $c['crew'] ? ' with ' . esc(implode(' and ', $c['crew'])) : '' ?>, including airport transfers.</td></tr><?php endif; ?>
    <?php if ($included): ?><tr><td style="vertical-align:top;">&#8226;</td><td><?= esc(implode(', ', array_unique($included))) ?>.</td></tr><?php endif; ?>
</table>
<?php if ($direct): ?>
<h3 style="font-size:14px; color:<?= $P ?>; margin:16px 0 6px 0; border-bottom:1px solid <?= $LINE ?>; padding-bottom:4px;">Payable direct on the day</h3>
<table width="100%" cellpadding="3" cellspacing="0" style="font-size:11.5px;"><tr><td width="4%" style="vertical-align:top;">&#8226;</td><td width="96%"><?= esc(implode(', ', array_unique($direct))) ?>.</td></tr></table>
<?php endif; ?>

<table width="100%" cellpadding="8" cellspacing="0" style="margin-top:22px; border:1px solid <?= $LINE ?>; background:#faf5fa;">
    <tr>
        <td width="55%" style="font-size:12px; color:<?= $MUTED ?>; vertical-align:middle;">Total for <?= (int) $c['pax'] ?> passenger<?= (int) $c['pax'] === 1 ? '' : 's' ?>, in US dollars <span style="display:inline-block; margin-left:6px; padding:1px 7px; border:1px solid <?= $P ?>; border-radius:10px; color:<?= $P ?>; font-size:10px; font-weight:bold; letter-spacing:.04em;">AS REQUESTED</span></td>
        <td width="45%" align="right" style="font-size:20px; font-weight:bold; color:<?= $P ?>; vertical-align:middle;"><?= $c['total_usd'] !== null ? $usd($c['total_usd']) : '<span style="font-size:12px;color:#b7791f">price to follow</span>' ?></td>
    </tr>
    <?php if ($c['per_person_usd'] !== null): ?>
    <tr><td style="font-size:12px; color:<?= $MUTED ?>; border-top:1px solid <?= $LINE ?>;">Per person, for the party of <?= (int) $c['pax'] ?> requested</td>
        <td align="right" style="font-size:14px; font-weight:bold; color:<?= $INK ?>; border-top:1px solid <?= $LINE ?>;"><?= $usd($c['per_person_usd']) ?></td></tr>
    <?php endif; ?>
</table>

<?php
// the price per person by the size of the party (schema 31): the size, the rooms it is based on,
// the price — nothing else reaches this page. A version frozen before the table existed has none.
// Schema 34: a version is quoted for up to a maximum number of travellers — on paper (and in print) the rows
// stop there; on screen every row is drawn and the ones above the maximum are hidden, so the staff control
// below can raise or lower it without a reload. A preview, or a version made before the maximum existed,
// shows every row as before.
$pp      = $c['pax_prices'] ?? [];
$ppAll   = $c['pax_prices_all'] ?? $pp;
$maxPax  = isset($max_pax) && $max_pax !== null ? (int) $max_pax : null;
$levels  = is_array($levels ?? null) ? $levels : [];
$staffUi = !$pdf && $q && $levels !== [];
$emailOn = !$pdf && $q && is_array($email ?? null);
$rows    = $staffUi ? $ppAll : $pp;
?>
<?php if ($staffUi): ?>
<div class="staff" data-maxpax-box style="margin:22px 0 -8px 0; padding:10px 12px; background:#f4f6f9; border:1px solid #dfe5ec; border-radius:8px; font-size:12.5px;">
    <label for="max_pax" style="font-weight:600; color:<?= $INK ?>;">Maximum Number of Travellers <span style="color:#b23a32">*</span></label>
    <?php if (!empty($email['locked'])): ?>
        <span id="max_pax_text" style="font-weight:600; margin-left:8px;"><?= $maxPax !== null ? $maxPax : '—' ?></span>
        <span style="color:<?= $MUTED ?>; margin-left:8px;">Set when version <?= (int) $q['version'] ?> was emailed; the agent has this range. To quote another range, make a new version.</span>
    <?php else: ?>
        <select id="max_pax" required style="font:inherit; margin-left:8px; padding:4px 8px; border:1px solid #cfd6de; border-radius:6px; background:#fff;">
            <?php if ($maxPax === null): ?><option value="" selected>choose…</option><?php endif; ?>
            <?php foreach ($levels as $L): ?><option value="<?= (int) $L ?>"<?= $maxPax === (int) $L ? ' selected' : '' ?>><?= (int) $L ?></option><?php endforeach; ?>
        </select>
        <span id="max_pax_note" style="color:<?= $MUTED ?>; margin-left:8px;">Required. The quotation, the PDF and the email show the price-per-person rows up to this size and hide the rest; the party enquired for is <?= (int) $c['pax'] ?>. Saved on version <?= (int) $q['version'] ?> as soon as it is chosen.</span>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php if ($rows): ?>
<h3 style="font-size:14px; color:<?= $P ?>; margin:20px 0 6px 0; border-bottom:1px solid <?= $LINE ?>; padding-bottom:4px;">Price per person by number of travellers</h3>
<p style="font-size:10.5px; color:<?= $MUTED ?>; margin:0 0 6px 0;">The party of <b style="color:<?= $INK ?>"><?= (int) $c['pax'] ?></b> you asked for is marked. The other sizes are ready-calculated options, so a different party size can be confirmed on this quotation without a fresh one.</p>
<table width="100%" cellpadding="5" cellspacing="0" style="font-size:11.5px; border:1px solid <?= $LINE ?>;" data-pax-prices>
    <tr style="background:#faf5fa;"><td width="16%" style="font-weight:bold; color:<?= $MUTED ?>; border-bottom:1px solid <?= $LINE ?>;">Pax</td><td width="54%" style="font-weight:bold; color:<?= $MUTED ?>; border-bottom:1px solid <?= $LINE ?>;">Rooms</td><td width="30%" align="right" style="font-weight:bold; color:<?= $MUTED ?>; border-bottom:1px solid <?= $LINE ?>;">Per person</td></tr>
    <?php foreach ($rows as $r): $mine = (int) $r['pax'] === (int) $c['pax']; $hidden = $staffUi && $maxPax !== null && (int) $r['pax'] > $maxPax; ?>
    <tr data-pax="<?= (int) $r['pax'] ?>"<?= $mine ? ' data-requested="1"' : '' ?><?= $hidden ? ' hidden' : '' ?> style="<?= $mine ? 'font-weight:bold; background:#f3e6f3;' : '' ?><?= $hidden ? ' display:none;' : '' ?>"><td style="border-bottom:1px solid #f3eef3;<?= $mine ? ' border-left:3px solid ' . $P . ';' : '' ?>"><?= (int) $r['pax'] ?></td><td style="border-bottom:1px solid #f3eef3;"><?= esc($r['rooms']) ?><?= $mine ? ' <span style="display:inline-block; margin-left:6px; padding:0 6px; border:1px solid ' . $P . '; border-radius:10px; color:' . $P . '; font-size:9px; letter-spacing:.04em;">AS REQUESTED</span>' : '' ?></td>
        <td align="right" style="border-bottom:1px solid #f3eef3;<?= $mine ? ' color:' . $P . '; font-size:12.5px;' : '' ?>"><?= $r['per_person_usd'] !== null ? $usd($r['per_person_usd']) : '<span style="color:' . $MUTED . '; font-weight:normal;">on request</span>' ?></td></tr>
    <?php endforeach; ?>
</table>
<p style="font-size:10px; color:<?= $MUTED ?>; margin:4px 0 0 0;">Each price is for that number of travellers in the rooms shown, with the same hotels, programme and services and a vehicle to suit the party. Per person, in US dollars.<span id="max_pax_line"<?= $maxPax === null ? ' style="display:none"' : '' ?>> Quoted for parties of up to <b id="max_pax_n"><?= $maxPax !== null ? $maxPax : '' ?></b> travellers.</span></p>
<?php endif; ?>

<?php if (!empty($pricing['quote_terms'])): ?>
<h3 style="font-size:12px; color:<?= $P ?>; margin:20px 0 4px 0;">Terms</h3>
<p style="font-size:10.5px; color:<?= $MUTED ?>; line-height:1.55; margin:0;"><?= nl2br(esc($pricing['quote_terms'])) ?></p>
<?php endif; ?>

<p style="font-size:10px; color:<?= $MUTED ?>; margin-top:26px; border-top:1px solid <?= $LINE ?>; padding-top:6px;"><?= esc($co['name']) ?> &nbsp;·&nbsp; Quotation <?= esc($ref) ?> &nbsp;·&nbsp; <?= $fmtD($date) ?></p>

<?php if (!$pdf): ?>
</div>
<?php if ($emailOn): $em = $email; ?>
<div class="mailbox" id="mailbox">
    <h2>Email this quotation to the agent</h2>
    <p class="sub">The PDF of version <?= (int) $q['version'] ?> goes attached (<span class="mono" id="em_pdf"><?= esc($em['pdf_name']) ?></span>), with the price rows up to the maximum chosen above. It is sent from your own mailbox<?= !empty($em['from']) ? ' (<b>' . esc($em['from']) . '</b>)' : '' ?>; nothing is sent until you press Send and confirm. Every send is kept on the version and on the Activity log<?= $q['status'] === 'draft' ? '; sending issues this draft as the version the agent has' : '' ?>.</p>
    <?php if (empty($em['can_send'])): ?><div class="warn">Your account has no mailbox set up to send from (email, password and host on your user profile), so Send is off. Preview still works; download the PDF and send it from your mail program, or ask Management to set the mailbox up.</div><?php endif; ?>
    <?php if (!empty($em['to_note'])): ?><div class="warn" id="em_to_note"><?= esc($em['to_note']) ?></div><?php endif; ?>
    <?php if (!empty($em['templates'])): ?>
    <label for="em_template">Template <span style="font-weight:normal">(fills the subject and the message; edit them as you like)</span></label>
    <select id="em_template" style="width:100%; font:inherit; font-size:13px; padding:7px 9px; border:1px solid #cfd6de; border-radius:7px; background:#fff;">
        <?php $grp = null; foreach ($em['templates'] as $t): if ($t['category_label'] !== $grp): if ($grp !== null): ?></optgroup><?php endif; $grp = $t['category_label']; ?><optgroup label="<?= esc($grp) ?>"><?php endif; ?>
            <option value="<?= (int) $t['id'] ?>"<?= (int) $t['id'] === (int) $em['template_id'] ? ' selected' : '' ?>><?= esc($t['name']) ?><?= $t['is_default'] ? ' (default)' : '' ?></option>
        <?php endforeach; if ($grp !== null): ?></optgroup><?php endif; ?>
    </select>
    <script>window.__emTemplates = <?= json_encode($em['templates'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <?php endif; ?>
    <div class="row2">
        <div><label for="em_to">To</label><input id="em_to" type="email" value="<?= esc($em['to']) ?>" placeholder="agent@example.com" autocomplete="off"></div>
        <div><label for="em_cc">CC <span style="font-weight:normal">(optional, comma-separated)</span></label><input id="em_cc" value="<?= esc($em['cc']) ?>" placeholder="" autocomplete="off"></div>
    </div>
    <label for="em_subject">Subject</label><input id="em_subject" value="<?= esc($em['subject']) ?>" maxlength="255">
    <label for="em_message">Message</label><textarea id="em_message" maxlength="8000"><?= esc($em['message']) ?></textarea>
    <div class="acts">
        <button type="button" id="em_preview">Preview email</button>
        <button type="button" class="send" id="em_send"<?= empty($em['can_send']) ? ' disabled' : '' ?>>Send</button>
        <span style="font-size:12px;color:<?= $MUTED ?>">Both open the preview first — the email exactly as it will go, with the attachment to open; nothing leaves until Send now is pressed there.</span>
    </div>
    <div id="em_result"></div>
    <div class="hist" id="em_hist"><?php if (!empty($em['history'])): ?><b>Sent so far</b><?php foreach ($em['history'] as $h): ?><div><?= $h['status'] === 'sent' ? '✔' : '✖' ?> <?= esc(substr((string) $h['sent_at'], 0, 16)) ?> · to <?= esc($h['to_email']) ?><?= $h['cc_email'] ? ' (cc ' . esc($h['cc_email']) . ')' : '' ?> · by <?= esc($h['sent_name'] ?: $h['sent_by']) ?> · <?= esc($h['pdf_name'] ?? '') ?><?= $h['max_pax'] ? ' · rows up to ' . (int) $h['max_pax'] : '' ?><?= $h['status'] !== 'sent' ? ' · <span style="color:#8a2a22">failed: ' . esc((string) $h['error']) . '</span>' : '' ?></div><?php endforeach; ?><?php endif; ?></div>
</div>
<div class="overlay" id="em_overlay" role="dialog" aria-modal="true" aria-labelledby="em_mtitle">
    <div class="modal">
        <div class="hd"><h3 id="em_mtitle">Preview — this is how it will go</h3><button type="button" id="em_close" aria-label="Close">×</button></div>
        <div class="env" id="em_env"></div>
        <div id="em_warn" style="display:none; margin:0 18px; padding:8px 12px; background:#fdf3e1; border:1px solid #f0d9a8; border-radius:8px; color:#7a4b00; font-size:12.5px;"></div>
        <div class="body"><div id="em_html"></div><p style="margin:14px 0 0"><a class="att" id="em_att" href="#" target="_blank" rel="noopener">📎 <span id="em_attname"></span> · open</a></p></div>
        <div class="ft"><button type="button" id="em_mclose">Close</button><button type="button" class="send" id="em_msend">Send now</button></div>
    </div>
</div>
<?php endif; ?>
<?php if (!empty($auto_print)): ?><script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script><?php endif; ?>
<script>
// Schema 34: the maximum number of travellers, saved on the version as soon as it is chosen, and the rows shown up to it
(function () {
    var sel = document.getElementById('max_pax');
    var base = location.pathname;
    function post(path, body) {
        return fetch(base + path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) }).then(function (r) { return r.json(); }).catch(function () { return { status: 0, message: 'The server did not answer.' }; });
    }
    window.__quotePost = post;
    function showRows(max) {
        document.querySelectorAll('[data-pax-prices] tr[data-pax]').forEach(function (tr) {
            var hide = max !== null && Number(tr.dataset.pax) > max;
            tr.hidden = hide; tr.style.display = hide ? 'none' : '';
        });
        var line = document.getElementById('max_pax_line'), n = document.getElementById('max_pax_n');
        if (line) { line.style.display = max === null ? 'none' : ''; }
        if (n) { n.textContent = max === null ? '' : String(max); }
    }
    if (!sel) return;
    var last = sel.value;
    sel.addEventListener('change', function () {
        var v = sel.value;
        if (!v) return;
        var note = document.getElementById('max_pax_note');
        post('/max_pax', { max_pax: Number(v) }).then(function (j) {
            if (j.status !== 200) { sel.value = last; if (note) { note.textContent = j.message || 'Not saved.'; note.style.color = '#8a2a22'; } return; }
            last = v; showRows(Number(v));
            var o = sel.querySelector('option[value=""]'); if (o) o.remove();
            if (note) { note.textContent = j.message; note.style.color = '#1e6b34'; }
            // tf-walkfix-r1: the email follows the maximum — the ready-made emails are filled again for the new
            // range, and a subject or message still as the template filled it is refilled; words the person has
            // edited are kept, and the panel says so
            if (typeof window.__emRefill === 'function') { window.__emRefill(Number(v)); }
        });
    });
})();
// the email panel: preview, then send — the server checks everything again and sends from the signed-in mailbox
(function () {
    var box = document.getElementById('mailbox');
    if (!box) return;
    var post = window.__quotePost;
    var $ = function (id) { return document.getElementById(id); };
    function fields() { return { to: $('em_to').value.trim(), cc: $('em_cc').value.trim(), subject: $('em_subject').value.trim(), message: $('em_message').value }; }
    function say(kind, text) { $('em_result').innerHTML = '<div class="' + kind + '"></div>'; $('em_result').firstChild.textContent = text; }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function openPreview() {
        return post('/email/preview', fields()).then(function (j) {
            if (j.status !== 200) { say('err', j.message || 'The preview could not be made.'); return false; }
            var d = j.data;
            $('em_env').innerHTML = '<b>From</b><span>' + esc(d.from_name || '') + (d.from ? ' &lt;' + esc(d.from) + '&gt;' : ' <span style="color:#8a5a00">(no mailbox set up on your account)</span>') + '</span>'
                + '<b>To</b><span>' + esc(d.to) + '</span>' + (d.cc ? '<b>CC</b><span>' + esc(d.cc) + '</span>' : '') + '<b>Subject</b><span>' + esc(d.subject) + '</span>'
                + '<b>Attachment</b><span>' + esc(d.attachment) + (d.max_pax ? ' — price rows up to ' + esc(d.max_pax) + ' travellers' : '') + '</span>';
            $('em_html').innerHTML = d.html;
            var w = $('em_warn'); if (w) { w.style.display = d.warning ? '' : 'none'; w.textContent = d.warning || ''; }
            $('em_attname').textContent = d.attachment; $('em_att').href = d.pdf_url;
            $('em_msend').disabled = !d.can_send; $('em_msend').title = d.can_send ? '' : 'Your account has no mailbox set up to send from';
            $('em_overlay').classList.add('on');
            return true;
        });
    }
    function closePreview() { $('em_overlay').classList.remove('on'); }
    function send() {
        // Send always goes through the preview: the person reads exactly what will go, then presses Send now
        var f = fields();
        $('em_send').disabled = true; $('em_msend').disabled = true;
        post('/email/send', f).then(function (j) {
            $('em_send').disabled = false; $('em_msend').disabled = false;
            closePreview();
            if (j.status !== 200) { say('err', j.message || 'Not sent.'); return; }
            say('ok', j.message);
            var h = (j.data && j.data.email && j.data.email.history) || [];
            $('em_hist').innerHTML = h.length ? '<b>Sent so far</b>' + h.map(function (x) { return '<div>' + (x.status === 'sent' ? '✔' : '✖') + ' ' + esc(String(x.sent_at).slice(0, 16)) + ' · to ' + esc(x.to_email) + (x.cc_email ? ' (cc ' + esc(x.cc_email) + ')' : '') + ' · by ' + esc(x.sent_name || x.sent_by) + ' · ' + esc(x.pdf_name || '') + (x.max_pax ? ' · rows up to ' + esc(x.max_pax) : '') + (x.status !== 'sent' ? ' · <span style="color:#8a2a22">failed: ' + esc(x.error || '') + '</span>' : '') + '</div>'; }).join('') : '';
            // the range is now the agent's: the selector becomes a plain figure
            var sel = $('max_pax');
            if (sel && j.data && j.data.email && j.data.email.locked) { var t = document.createElement('span'); t.id = 'max_pax_text'; t.style.cssText = 'font-weight:600;margin-left:8px'; t.textContent = sel.value; sel.replaceWith(t); var n = $('max_pax_note'); if (n) n.textContent = 'Set when this version was emailed; the agent has this range. To quote another range, make a new version.'; }
            var bar = document.querySelector('.bar .note'); if (bar && j.data && j.data.quote) { bar.textContent = 'Version ' + j.data.quote.version + ' · ' + j.data.quote.status; }
        });
    }
    var tsel = $('em_template');
    // what the template last filled in: a subject or message still equal to it has not been edited by hand
    var filled = { subject: $('em_subject').value, message: $('em_message').value };
    function fillFrom(t) { $('em_subject').value = t.subject; $('em_message').value = t.message; filled = { subject: t.subject, message: t.message }; }
    if (tsel) {
        tsel.addEventListener('change', function () {
            var t = (window.__emTemplates || []).filter(function (x) { return String(x.id) === tsel.value; })[0];
            if (!t) return;
            fillFrom(t);
            say('ok', 'Template "' + t.name + '" filled in the subject and the message; edit them as you like before sending.');
        });
    }
    window.__emRefill = function (max) {
        var q = tsel && tsel.value ? '?template=' + encodeURIComponent(tsel.value) : '';
        fetch(base + '/email' + q, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); }).then(function (j) {
            if (j.status !== 200 || !j.data) return;
            var d = j.data;
            if (d.templates) { window.__emTemplates = d.templates; }
            var untouched = $('em_subject').value === filled.subject && $('em_message').value === filled.message;
            if (untouched) {
                fillFrom({ subject: d.subject, message: d.message });
                say('ok', 'The maximum is now ' + max + ' travellers: the subject and the message were filled in again to match the attachment. Edit them as you like before sending.');
            } else {
                say('err', 'The maximum is now ' + max + ' travellers. The subject or message was edited by hand, so it was left as it is — check that its wording matches, or pick the template again to fill it in afresh.');
            }
        }).catch(function () {});
    };
    var base = location.pathname;
    $('em_preview').addEventListener('click', openPreview);
    $('em_close').addEventListener('click', closePreview); $('em_mclose').addEventListener('click', closePreview);
    $('em_overlay').addEventListener('click', function (e) { if (e.target === $('em_overlay')) closePreview(); });
    $('em_send').addEventListener('click', function () { openPreview().then(function (ok) { if (ok) { /* the person reads the preview, then presses Send now */ } }); });
    $('em_msend').addEventListener('click', send);
})();
// Download PDF opens in a new tab; a browser that refuses new tabs (the Claude desktop app's browser pane) gets it in this tab instead
document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a[target="_blank"]') : null;
    if (!a || !a.href || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    var w = null;
    try { w = window.open(a.href, '_blank'); } catch (err) { w = null; }
    if (!w) location.href = a.href;
});
</script>
</body></html>
<?php endif; ?>
