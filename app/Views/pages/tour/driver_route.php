<?php
/**
 * tf-route-map-r1 (10 Oct 2026) — the driver's route map sheet (TourService::packDocument, kind "route"):
 * the tour's journey on a map of Sri Lanka and the planned mileage day by day, from the transport card's own saved
 * legs. A page to print, with the downloads (one picture for WhatsApp, a PDF), or the same HTML handed to the PDF
 * library — tables and inline styles only. Read only, and nothing a driver should not see: no rate, no price, no
 * margin, no control.
 *
 *   $route     RouteMap::build(): legs, days, stops, totals, revision
 *   $job       the transport card;  $details  the tour;  $company  the company's lines
 *   $map_url   the map picture (GET …?map=1);  $img_url  the whole card as one PNG;  $pdf_url  the PDF
 *   $for_pdf   true when the HTML goes to the PDF library;  $map_file  then the map picture's file
 *   $user      who is printing
 */
$r   = $route;
$j   = $job;
$co  = $company;
$pdf = !empty($for_pdf);
$e   = static fn ($x): string => esc((string) ($x ?? ''));
$km  = static fn ($v): string => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1, '.', ','), '0'), '.') . ' km';
$dt  = static function (?string $ymd, string $f = 'D j M Y'): string { $t = $ymd ? strtotime($ymd) : false; return $t ? date($f, $t) : '—'; };
$town = static fn (string $p): string => \App\Libraries\TourFlow\RouteMap::locate($p)['town'] ?? (($parts = array_values(array_filter(array_map('trim', explode(',', $p))))) ? end($parts) : $p);
$FONT = $pdf ? 'dejavusans' : 'Arial, Helvetica, sans-serif';   // the PDF library's core fonts have no arrows or bullets; DejaVu has
$MONO = $pdf ? 'dejavusansmono' : 'Courier, monospace';
// tf-route-map-r1c: the system's own colours, as the map picture (brand purple #842484 and its tint #f3e7f3, ink #2c3e50, muted #6c757d)
$INK = '#2c3e50'; $MUTED = '#6c757d'; $LINE = '#ced4da'; $SOFT = '#e9ecef'; $NAVY = '#842484'; $GRN = '#1f8a4c'; $AMB = '#1a1a1a'; $RED = '#b23a32'; $BAND = '#f3e7f3';
$th  = 'style="text-align:left; font-size:9.5px; letter-spacing:0.04em; text-transform:uppercase; color:' . $MUTED . '; padding:5px 6px; border-bottom:1px solid ' . $LINE . ';"';
$thr = 'style="text-align:right; font-size:9.5px; letter-spacing:0.04em; text-transform:uppercase; color:' . $MUTED . '; padding:5px 6px; border-bottom:1px solid ' . $LINE . ';"';
$td  = 'style="font-size:11px; padding:5px 6px; border-bottom:1px solid ' . $SOFT . '; vertical-align:top; color:' . $INK . ';"';
$tdr = 'style="font-size:11px; padding:5px 6px; border-bottom:1px solid ' . $SOFT . '; vertical-align:top; color:' . $INK . '; text-align:right; font-family:' . $MONO . ';"';
$tdb = 'style="font-size:12px; padding:7px 6px; border-top:1px solid ' . $LINE . '; vertical-align:top; color:' . $NAVY . '; background:' . $BAND . '; font-weight:bold;"';
$tdbr = 'style="font-size:12px; padding:7px 6px; border-top:1px solid ' . $LINE . '; vertical-align:top; color:' . $NAVY . '; background:' . $BAND . '; font-weight:bold; text-align:right; font-family:' . $MONO . ';"';
$small = 'style="font-size:10px; color:' . $MUTED . ';"';
$vehicle = $r['vehicle'] !== '' ? $r['vehicle'] : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($r['tour_no']) ?> — Route map</title>
<?php if (!$pdf): ?>
<style>
    body { font-family: Arial, Helvetica, sans-serif; color: #2c3e50; margin: 16px; max-width: 960px; }
    .toolbar { margin-bottom: 12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .toolbar a, .toolbar button { font: inherit; font-size:13px; padding: 7px 12px; border:1px solid #ced4da; border-radius:8px; background:#fff; color:#2c3e50; text-decoration:none; cursor:pointer; }
    .toolbar a.main { background:#842484; border-color:#842484; color:#fff; font-weight:bold; }
    .map img { width:100%; height:auto; display:block; border:1px solid #ced4da; border-radius:8px; }
    table { border-collapse:collapse; }
    @media print { .toolbar { display: none; } body { margin: 8mm; } }
</style>
<?php endif; ?>
</head>
<body style="font-family:<?= $FONT ?>; color:<?= $INK ?>; font-size:11px;">
<?php if (!$pdf): ?>
<div class="toolbar">
    <a class="main" href="<?= $e($img_url) ?>" title="One picture, 1080 px wide, with the map and the day-by-day table — send it on WhatsApp">&#8681; Download picture (WhatsApp)</a>
    <a href="<?= $e($pdf_url) ?>" target="_blank">PDF</a>
    <button onclick="window.print()">Print</button>
    <button onclick="history.back()">&larr; Back</button>
    <span style="color:<?= $MUTED ?>; font-size:11px;">Printed <?= date('j M Y H:i') ?> by <?= $e($user ?? '') ?> · planned mileage, actual driving may vary</span>
</div>
<?php endif; ?>
<?php if (!empty($no_pdf_lib)): ?><div class="toolbar" style="font-size:12px;color:#1a1a1a;background:#ffffff;border:1px solid #d9d3dd;border-radius:8px;padding:8px 12px;">The PDF library is not installed on this server yet, so this is the print view: choose <b>Save as PDF</b> in the print dialog, or send the picture.</div><?php endif; ?>

<table cellpadding="0" cellspacing="0" width="100%" style="border-bottom:2px solid <?= $NAVY ?>;">
    <tr>
        <td width="64%" style="padding:0 0 8px 0; vertical-align:top;">
            <div style="font-size:18px; font-weight:bold; color:<?= $NAVY ?>;">Driver's route map</div>
            <div style="font-size:12px; margin-top:2px;">Tour <b><?= $e($r['tour_no']) ?></b><?= $r['tour_name'] !== '' ? ' · ' . $e($r['tour_name']) : '' ?></div>
            <div <?= $small ?>><?= $e($dt($r['arrival_date'], 'j M Y')) ?> – <?= $e($dt($r['departure_date'], 'j M Y')) ?> · <?= (int) $r['pax'] ?> pax · <?= $e($vehicle) ?><?= $r['arrival_flight'] !== '' ? ' · arrives ' . $e($r['arrival_flight']) : '' ?><?= $r['departure_flight'] !== '' ? ' · departs ' . $e($r['departure_flight']) : '' ?></div>
            <div <?= $small ?>>Driver: <b><?= $e($r['driver'] !== '' ? $r['driver'] : 'not yet engaged') ?></b></div>
        </td>
        <td width="36%" style="padding:0 0 8px 0; vertical-align:top; text-align:right;">
            <div style="font-size:12px; font-weight:bold;"><?= $e($co['name'] ?: 'Avenir Holidays') ?></div>
            <?php foreach (array_filter([$co['address'] ?? null, !empty($co['phone']) ? 'Tel ' . $co['phone'] : null]) as $line): ?><div <?= $small ?>><?= $e($line) ?></div><?php endforeach; ?>
            <div <?= $small ?> style="margin-top:4px;"><b>Revision <?= $e($r['revision'] ? $dt($r['revision'], 'j M Y H:i') : '—') ?></b></div>
            <div <?= $small ?>>A newer revision replaces this sheet.</div>
        </td>
    </tr>
</table>

<div class="map" style="margin-top:10px;">
<?php if ($pdf): ?>
    <img src="<?= $e($map_file) ?>" width="560" alt="Route map">
<?php else: ?>
    <img src="<?= $e($map_url) ?>" alt="Route map">
<?php endif; ?>
</div>

<div style="font-size:12px; letter-spacing:0.06em; text-transform:uppercase; color:<?= $MUTED ?>; margin:14px 0 6px;">Day by day — planned mileage</div>
<table cellpadding="0" cellspacing="0" width="100%">
    <tr><th width="15%" <?= $th ?>>Day / date</th><th width="44%" <?= $th ?>>From → to</th><th width="15%" <?= $thr ?>>Planned km</th><th width="12%" <?= $thr ?>>Extra</th><th width="14%" <?= $th ?>>&nbsp;&nbsp;Night</th></tr>
    <?php foreach ($r['days'] as $day): ?>
    <?php $route = implode(' → ', array_map($town, $day['route'])); ?>
    <tr>
        <td <?= $tdb ?>>Day <?= (int) $day['day'] ?><br><span style="font-weight:normal; font-size:10px; color:<?= $MUTED ?>;"><?= $e($dt($day['date'], 'D j M Y')) ?></span></td>
        <td <?= $tdb ?>><?= $e($route) ?><br><span style="font-weight:normal; font-size:10px; color:<?= $day['night_hotel'] !== '' ? $GRN : $MUTED ?>;"><?= $day['night_hotel'] !== '' ? 'Night: ' . $e($day['night_hotel']) . ($day['night_area'] !== '' ? ', ' . $e($day['night_area']) : '') : ($day['night_area'] !== '' ? 'Night: ' . $e($day['night_area']) . ' — hotel not named yet' : 'No hotel night') ?></span></td>
        <td <?= $tdbr ?>><?= $e($km($day['km'])) ?><?= $day['unpriced'] ? ' +?' : '' ?><br><span style="font-weight:normal; font-size:9.5px; color:<?= $MUTED ?>;">day total</span></td>
        <td <?= $tdbr ?>><span style="color:<?= $AMB ?>"><?= $day['extra'] > 0 ? $e($km($day['extra'])) : '—' ?></span></td>
        <td <?= $tdb ?>>&nbsp;&nbsp;<?= $day['night_hotel'] !== '' ? '<span style="color:' . $GRN . '">● hotel</span>' : '—' ?></td>
    </tr>
    <?php foreach ($day['legs'] as $li): $l = $r['legs'][$li]; ?>
    <?php
        $what = $l['same'] ? $town($l['from']) : $town($l['from']) . ' → ' . $town($l['to']);
        if ($l['same'] && $l['kind'] === 'local') { $what .= ' (city tour)'; } elseif ($l['kind'] === 'idle') { $what .= ' (stay day)'; } elseif ($l['same'] && $l['kind'] === 'arrival') { $what .= ' (airport hotel — no drive)'; }
        $tag = $l['tags'] ? strtoupper($l['tags'][0]) : '';
        $tagColor = $l['garage'] ? $MUTED : (str_starts_with($tag, 'AIRPORT') ? '#2563eb' : $MUTED);
    ?>
    <tr>
        <td <?= $td ?>></td>
        <td <?= $td ?>><span style="color:<?= $l['garage'] ? $MUTED : $INK ?>"><?= $e($what) ?></span><?= $tag !== '' ? ' <span style="font-size:9px; font-weight:bold; color:' . $tagColor . ';">' . $e($tag) . '</span>' : '' ?><?= $l['from'] !== $town($l['from']) || $l['to'] !== $town($l['to']) ? '<br><span ' . $small . '>' . $e($l['same'] ? $l['from'] : $l['from'] . ' → ' . $l['to']) . '</span>' : '' ?></td>
        <td <?= $tdr ?>><?= $l['km'] === null ? '<span style="color:' . $RED . '">not set</span>' : $e($km($l['km'])) ?></td>
        <td <?= $tdr ?>><span style="color:<?= $AMB ?>"><?= $l['extra'] ? $e($km($l['extra'])) : '—' ?></span></td>
        <td <?= $td ?>></td>
    </tr>
    <?php endforeach; ?>
    <?php endforeach; ?>
    <?php if (!$r['days']): ?><tr><td colspan="5" <?= $td ?>><span <?= $small ?>>No legs on this card yet.</span></td></tr><?php endif; ?>
</table>

<table cellpadding="0" cellspacing="0" width="100%" style="margin-top:10px; background:#f3e7f3; border:1px solid #e2c6e2;">
    <tr>
        <td width="60%" style="padding:8px 10px; vertical-align:top;">
            <div style="font-size:13px; font-weight:bold; color:<?= $NAVY ?>;">Total planned mileage</div>
            <div <?= $small ?>><?= count($r['legs']) ?> legs over <?= count($r['days']) ?> days<?= $r['garage_km'] > 0 ? ' · garage runs ' . $e($km($r['garage_km'])) . ' included' : '' ?><?= $r['unpriced'] ? ' · ' . (int) $r['unpriced'] . ' leg(s) without a figure' : '' ?></div>
            <div <?= $small ?>>Planned mileage — actual driving may vary. The log sheet records the kilometres driven.</div>
        </td>
        <td width="40%" style="padding:8px 10px; vertical-align:top; text-align:right;">
            <div style="font-size:20px; font-weight:bold; color:<?= $NAVY ?>; font-family:<?= $MONO ?>;"><?= $e($km($r['total_km'])) ?></div>
            <div style="font-size:10.5px; color:<?= $AMB ?>; font-weight:bold;">Extra sightseeing allowance (separate): <?= $e($km($r['total_extra'])) ?></div>
        </td>
    </tr>
</table>
<?php if ($r['unknown']): ?><div <?= $small ?> style="margin-top:6px;">Not on the map (place not in the gazetteer): <?= $e(implode(', ', array_map(static fn ($s) => $s['name'], $r['unknown']))) ?>.</div><?php endif; ?>
<div <?= $small ?> style="margin-top:8px; border-top:1px solid <?= $LINE ?>; padding-top:6px;">Tour <?= $e($r['tour_no']) ?> · revision <?= $e($r['revision'] ? $dt($r['revision'], 'j M Y H:i') : '—') ?> · printed <?= $e($dt($r['generated'], 'j M Y H:i')) ?> · <?= $e($co['name'] ?: 'Avenir Holidays') ?><?= !empty($co['phone']) ? ' · Tel ' . $e($co['phone']) : '' ?></div>
<?php if (!empty($auto_print)): ?><script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script><?php endif; ?>
</body>
</html>
