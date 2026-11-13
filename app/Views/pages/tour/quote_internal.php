<?php
/**
 * The internal costing — every line, where its figure came from, and the totals. For
 * the desk it shows cost and the client's price; the markup and the profit are drawn
 * only when the person holds Pricing ($sees_pricing) and the costing was not stripped
 * for them ($cost['markup']['hidden']).
 *
 * Above the three sections, the pax matrix (schema 31): every party size from 1 to 10
 * costed on its own from the masters — its rooms, its vehicle, its paid lines — with the
 * cost and the price per person, and the profit for Management only. The inquiry's own
 * size is marked, and checked against the sections below.
 *
 * One template for the screen and for the PDF ($for_pdf): plain tables, inline styles.
 */
$pdf  = !empty($for_pdf);
$c    = $cost;
$q    = $quote;
$mgmt = !empty($sees_pricing) && empty($c['markup']['hidden']);
// tf-margin-r1: the tour's COST TOTALS are Management's, like the markup and the profit. Without
// Pricing the roll-ups and the totals are not printed at all — not as a figure, not as a zero.
// The supplier figure on each line stays: the desks need it to do the work.
$costOn = !empty($sees_pricing) && empty($c['cost_hidden']);
$ref  = ($c['inquiry_no'] ?? '') . ($q ? ' / v' . (int) $q['version'] : ' / preview');
$fmtD = static fn ($d) => $d ? date('j M Y', strtotime((string) $d)) : '—';
$n2   = static fn ($v) => $v === null ? '—' : number_format((float) $v, 2);
$n1   = static fn ($v) => $v === null ? '—' : number_format((float) $v, 1);
$P = '#842484'; $INK = '#2c3e50'; $MUTED = '#6c757d'; $LINE = '#e6dbe6'; $RED = '#b23a32'; $AMB = '#1a1a1a'; $GRN = '#1f8a4c';
$th = 'style="font-size:10px; text-transform:uppercase; letter-spacing:.04em; color:' . $MUTED . '; border-bottom:1px solid ' . $LINE . '; text-align:left; padding:5px 6px;"';
$thr = 'style="font-size:10px; text-transform:uppercase; letter-spacing:.04em; color:' . $MUTED . '; border-bottom:1px solid ' . $LINE . '; text-align:right; padding:5px 6px;"';
$td = 'style="font-size:11px; border-bottom:1px solid #f0e9f0; padding:5px 6px; vertical-align:top;"';
$tdr = 'style="font-size:11px; border-bottom:1px solid #f0e9f0; padding:5px 6px; vertical-align:top; text-align:right; font-family:Consolas,Menlo,monospace; white-space:nowrap;"';
$w   = static fn (int $pct) => ' width="' . $pct . '%"';
$h3 = 'style="font-size:13px; color:' . $P . '; margin:18px 0 6px 0; border-bottom:1px solid ' . $LINE . '; padding-bottom:4px;"';
$missing = static fn (array $row) => ($row['state'] ?? 'ok') === 'missing' ? '<span style="color:' . $RED . '; font-weight:bold;">' . esc($row['why'] ?? 'missing') . '</span>' : '';
$mixShort = static function (array $m): string {
    $p = [];
    foreach ([['sgl', 'S'], ['dbl', 'D'], ['twn', 'Tw'], ['tpl', 'T']] as [$k, $l]) {
        if ((int) ($m[$k] ?? 0) > 0) {
            $p[] = (int) $m[$k] . $l;
        }
    }
    return $p ? implode(' ', $p) : '—';
};
?>
<?php if (!$pdf): ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Costing <?= esc($ref) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { margin:0; background:#eceff3; font-family:"Segoe UI", Helvetica, Arial, sans-serif; color:<?= $INK ?>; font-size:13px; }
    .bar { position:sticky; top:0; z-index:5; display:flex; gap:8px; align-items:center; padding:10px 18px; background:#fff; border-bottom:1px solid #dfe5ec; box-shadow:0 2px 8px rgba(0,0,0,.05); }
    .bar a, .bar button { font:inherit; font-size:12.5px; font-weight:600; padding:7px 12px; border-radius:8px; border:1px solid #dfe5ec; background:#fff; color:<?= $INK ?>; text-decoration:none; cursor:pointer; }
    .bar a:hover, .bar button:hover { background:#f4f6f9; }
    .bar .primary { background:linear-gradient(135deg, #e90c8b 0%, #8f2a8d 100%); color:#fff; border:0; }
    .bar .note { margin-left:auto; font-size:12px; color:<?= $MUTED ?>; }
    .bar .preview { background:#ffffff; color:#1a1a1a; border-color:#d9d3dd; }
    .sheet { width:210mm; margin:22px auto; background:#fff; padding:14mm 14mm; box-shadow:0 8px 30px rgba(0,0,0,.08); box-sizing:border-box; }
    table { border-collapse:collapse; width:100%; }
    @media print { body { background:#fff; } .bar { display:none; } .sheet { margin:0; box-shadow:none; width:auto; padding:0; } }
</style></head><body>
<div class="bar">
    <a href="<?= base_url($intake_url ?? 'tour/intake') ?>?id=<?= (int) $intake_id ?>">&larr; Back to the inquiry</a>
    <a href="?view=client">Client quotation</a>
    <button onclick="window.print()">Print</button>
    <a class="primary" href="?view=internal&pdf=1" target="_blank">Download PDF</a>
    <?php if (!empty($preview)): ?><span class="preview" style="padding:7px 12px;border-radius:8px;font-size:12px;font-weight:600">Preview — follows the inquiry as it stands</span>
    <?php elseif ($q): ?><span class="note">Version <?= (int) $q['version'] ?> · <?= esc($q['status']) ?> · frozen <?= esc(substr((string) $q['created_at'], 0, 16)) ?> by <?= esc((string) $q['created_by']) ?></span><?php endif; ?>
</div>
<?php if (!empty($no_pdf_lib)): ?><div style="width:210mm;margin:14px auto -8px;font-size:12px;color:#1a1a1a;background:#ffffff;border:1px solid #d9d3dd;border-radius:8px;padding:8px 12px;">The PDF library is not installed on this server yet, so this is the print view: choose <b>Save as PDF</b> in the print dialog. (Ask the administrator to run <span style="font-family:monospace">composer install</span> — tecnickcom/tcpdf is already in composer.json.)</div><?php endif; ?>
<div class="sheet">
<?php endif; ?>

<table cellpadding="0" cellspacing="0" style="border-bottom:2px solid <?= $P ?>;">
    <tr><td style="padding:0 0 8px 0;"><span style="font-size:18px; font-weight:bold; color:<?= $P ?>;">Internal costing</span> &nbsp;<span style="font-size:12px; color:<?= $MUTED ?>;">INTERNAL — not for the agent</span></td>
        <td align="right" style="padding:0 0 8px 0; font-size:11px; color:<?= $MUTED ?>;">Ref <b style="color:<?= $INK ?>"><?= esc($ref) ?></b><br>costed <?= esc(substr((string) ($c['costed_at'] ?? ''), 0, 16)) ?> by <?= esc((string) ($c['costed_by'] ?? '')) ?></td></tr>
</table>
<table cellpadding="4" cellspacing="0" style="margin-top:8px; font-size:11.5px;">
    <tr><td width="50%"><b>Agent:</b> <?= esc($c['agent_name'] ?: '—') ?> &nbsp; <b>Tour:</b> <?= esc($c['tour_name'] ?: '—') ?><?= !empty($c['market_code']) ? ' &nbsp; <b>Market:</b> ' . esc($c['market_code']) : '' ?></td>
        <td width="50%"><b>Dates:</b> <?= !empty($c['tour_dates']['line']) ? esc($c['tour_dates']['line']) : $fmtD($c['arrival']) . ' – ' . $fmtD($c['departure']) . ' (' . (int) $c['nights'] . ' n)' ?> &nbsp; <b>Pax:</b> <?= (int) $c['pax'] ?> &nbsp; <b>Rooms:</b> <?= esc($mixShort($c['rooms'] ?? [])) ?> &nbsp; <b>USD rate:</b> <?= $c['usd_rate'] !== null ? $n2($c['usd_rate']) . ' (' . esc((string) $c['rate_date']) . ')' : '<span style="color:' . $RED . '">none</span>' ?></td></tr>
</table>

<?php if (!empty($c['blockers'])): ?>
<table cellpadding="5" cellspacing="0" style="margin-top:10px; border:1px solid #f0c7c3; background:#fbe9e7;">
    <tr><td style="font-size:11.5px; color:<?= $RED ?>;"><b>Cannot be quoted yet — <?= count($c['blockers']) ?> thing(s) to fix:</b><br>
    <?php foreach ($c['blockers'] as $b): ?>&#8226; <?= esc($b['label']) ?><br><?php endforeach; ?></td></tr>
</table>
<?php endif; ?>
<?php if (!empty($c['warnings'])): ?>
<table cellpadding="5" cellspacing="0" style="margin-top:8px; border:1px solid #d9d3dd; background:#ffffff;">
    <tr><td style="font-size:11px; color:<?= $AMB ?>;"><b>Worth an eye:</b><br>
    <?php foreach ($c['warnings'] as $wn): /* not $w: that is the column-width helper the tables below use */ ?>&#8226; <?= esc($wn['label']) ?><br><?php endforeach; ?></td></tr>
</table>
<?php endif; ?>

<?php
// the costing in three parts (schema 29): hotels, paid activities and services, transport — the same
// figures as the sections, grouped; a version frozen before the parts existed is read the same way
$QS    = \App\Libraries\TourFlow\QuoteService::class;
$parts = $c['parts'] ?? $QS::parts($c['sections'] ?? []);
$costOnParts = $costOn;   // tf-margin-r1: the roll-ups print only for Pricing
$stays = $c['hotel_subtotals'] ?? $QS::hotelSubtotals($c['hotels'] ?? []);
$stayEnd = [];
foreach ($stays as $st) {
    if (!empty($st['transfer'])) {
        continue;   // tf-transfer-r2: shown on its own row below the nights
    }
    $stayEnd[(int) $st['last_day']] = $st;
}
$nightsWord = static function (array $ns): string {
    $a = (int) reset($ns); $b = (int) end($ns);
    return (count($ns) === 1 ? 'night ' . $a : 'nights ' . $a . '–' . $b) . ' (' . count($ns) . ' night' . (count($ns) === 1 ? '' : 's') . ')';
};
$paidLines = array_values(array_filter($c['services'] ?? [], static fn ($s) => ($s['line_state'] ?? (($s['pay_mode'] ?? '') === 'no_charge' ? 'no_charge' : '')) !== 'no_charge'));
$freeCount = $c['free_visits'] ?? (count($c['services'] ?? []) - count($paidLines));
$partHead = 'style="font-size:14px; color:#fff; background:' . $P . '; margin:20px 0 0 0; padding:6px 10px; border-radius:4px 4px 0 0;"';
$subHead  = 'style="font-size:12px; color:' . $P . '; margin:10px 0 4px 0;"';
$subRow   = 'style="font-size:11px; padding:5px 6px; text-align:right; background:#faf5fa; border-bottom:1px solid ' . $LINE . '; font-family:Consolas,Menlo,monospace; white-space:nowrap;"';
$subLbl   = 'style="font-size:11px; padding:5px 6px; text-align:right; background:#faf5fa; border-bottom:1px solid ' . $LINE . ';"';
$partRow  = 'style="font-size:12px; font-weight:bold; padding:6px 6px; text-align:right; border-top:2px solid ' . $P . '; font-family:Consolas,Menlo,monospace; white-space:nowrap;"';
$partLbl  = 'style="font-size:12px; font-weight:bold; padding:6px 6px; text-align:right; border-top:2px solid ' . $P . ';"';
// the pax matrix (schema 31): every party size costed on its own, above the three sections; a
// version frozen before the matrix existed has none, and the page reads as it always did
$mx = $c['pax_matrix'] ?? null;
?>

<?php if (!empty($mx['available']) && !empty($mx['levels'])): $mxProfit = $mgmt && empty($mx['profit_hidden']); ?>
<h3 <?= $partHead ?> data-part="matrix">Pax matrix <span style="font-weight:normal; font-size:11px;">— every party size costed on its own from the masters, never a total divided</span></h3>
<table cellpadding="0" cellspacing="0" data-pax-matrix>
    <tr><th <?= $th . $w(7) ?>>Pax</th><th <?= $th . $w($mxProfit ? 16 : 18) ?>>Rooms</th><th <?= $th . $w($mxProfit ? 14 : 16) ?>>Vehicle</th><?php if ($costOn): ?><th <?= $thr . $w(9) ?>>Hotels</th><th <?= $thr . $w(9) ?>>Paid</th><th <?= $thr . $w(9) ?>>Transport</th><th <?= $thr . $w(10) ?>>Total cost</th><th <?= $thr . $w(8) ?>>Cost pp</th><?php endif; ?><th <?= $thr . $w(9) ?>>Price pp</th><?php if ($mxProfit): ?><th <?= $thr . $w(9) ?>>Profit</th><?php endif; ?></tr>
    <?php foreach ($mx['levels'] as $L): $own = !empty($L['own']); $rowBg = $own ? ' background:#faf5fa;' : ''; ?>
    <tr data-pax="<?= (int) $L['pax'] ?>"<?= $own ? ' data-own="1"' : '' ?><?= !$L['complete'] ? ' data-open="1"' : '' ?>>
        <td style="font-size:11px; border-bottom:1px solid #f0e9f0; padding:5px 6px;<?= $rowBg ?>"><b><?= (int) $L['pax'] ?></b><?= $own ? '<br><span style="color:' . $P . '; font-size:9.5px; font-weight:bold;">this inquiry</span>' : '' ?></td>
        <td style="font-size:11px; border-bottom:1px solid #f0e9f0; padding:5px 6px;<?= $rowBg ?>"><?= esc($L['rooms_text']) ?></td>
        <td style="font-size:11px; border-bottom:1px solid #f0e9f0; padding:5px 6px;<?= $rowBg ?>"><?= esc($L['vehicle_text']) ?><?= !empty($L['vehicle_changed']) ? ' <span style="color:' . $AMB . '; font-size:9.5px; font-weight:bold;" data-vehicle-changed="1">new size</span>' : '' ?></td>
        <?php if ($costOn): ?>
        <?php foreach (['hotels', 'paid', 'transport'] as $pk): ?>
        <td style="font-size:11px; border-bottom:1px solid #f0e9f0; padding:5px 6px; text-align:right; font-family:Consolas,Menlo,monospace; white-space:nowrap;<?= $rowBg ?>"><?= $L['parts'][$pk] !== null ? $n2($L['parts'][$pk]) : '<span style="color:' . $RED . '">—</span>' ?></td>
        <?php endforeach; ?>
        <td style="font-size:11px; border-bottom:1px solid #f0e9f0; padding:5px 6px; text-align:right; font-family:Consolas,Menlo,monospace; white-space:nowrap; font-weight:bold;<?= $rowBg ?>" data-mx-cost><?= $L['cost_usd'] !== null ? $n2($L['cost_usd']) : '<span style="color:' . $RED . '">—</span>' ?></td>
        <td style="font-size:11px; border-bottom:1px solid #f0e9f0; padding:5px 6px; text-align:right; font-family:Consolas,Menlo,monospace; white-space:nowrap;<?= $rowBg ?>"><?= $n2($L['cost_pp_usd']) ?></td>
        <?php endif; ?>
        <td style="font-size:11px; border-bottom:1px solid #f0e9f0; padding:5px 6px; text-align:right; font-family:Consolas,Menlo,monospace; white-space:nowrap; color:<?= $P ?>; font-weight:bold;<?= $rowBg ?>" data-mx-price><?= $L['sell_pp_usd'] !== null ? $n2($L['sell_pp_usd']) : '—' ?></td>
        <?php if ($mxProfit): ?><td style="font-size:11px; border-bottom:1px solid #f0e9f0; padding:5px 6px; text-align:right; font-family:Consolas,Menlo,monospace; white-space:nowrap; color:<?= $GRN ?>;<?= $rowBg ?>" data-mx-profit><?= $n2($L['profit_usd']) ?></td><?php endif; ?>
    </tr>
    <?php if (!$L['complete'] && !empty($L['gaps'])): ?>
    <tr><td <?= $td ?>></td><td colspan="<?= ($costOn ? 8 : 3) + ($mxProfit ? 1 : 0) ?>" style="font-size:10.5px; color:<?= $RED ?>; border-bottom:1px solid #f0e9f0; padding:3px 6px 6px 6px;"><?= esc(implode(' ', $L['gaps'])) ?> <span style="color:<?= $MUTED ?>">The agent's table says "on request" for <?= (int) $L['pax'] ?> pax.</span></td></tr>
    <?php endif; ?>
    <?php endforeach; ?>
</table>
<?php $chk = $mx['check'] ?? null; ?>
<?php if ($chk !== null && isset($chk['pax'])): ?>
<p data-mx-check="<?= !empty($chk['equal']) ? 'equal' : 'differs' ?>" style="font-size:11px; margin:6px 0 0 0; color:<?= !empty($chk['equal']) ? $GRN : $RED ?>;"><?= !empty($chk['equal'])
    ? '<b>Checked:</b> at ' . (int) $chk['pax'] . ' pax the matrix comes to USD ' . $n2($chk['matrix_usd']) . ', the same as the detailed costing below, part by part.'
    : '<b>The matrix and the costing disagree:</b> at ' . (int) $chk['pax'] . ' pax the matrix gives USD ' . $n2($chk['matrix_usd']) . ', the detailed costing USD ' . $n2($chk['costing_usd']) . '. The quotation waits until they agree.' ?></p>
<?php endif; ?>
<?php if (!empty($mx['common'])): ?>
<p style="font-size:10.5px; color:<?= $AMB ?>; margin:6px 0 0 0;"><b>Every size waits on:</b> <?= esc(implode(' ', $mx['common'])) ?></p>
<?php endif; ?>
<?php foreach (($mx['notes'] ?? []) as $note): ?>
<p style="font-size:10.5px; color:<?= $MUTED ?>; margin:3px 0 0 0;">&#8226; <?= esc($note) ?></p>
<?php endforeach; ?>
<p style="font-size:10px; color:<?= $MUTED ?>; margin:4px 0 0 0;"><?= esc($mx['basis_note'] ?? '') ?></p>
<?php endif; ?>

<?php $cfr = $c['child_review'] ?? null; if (is_array($cfr) && !empty($cfr['items'])): // tf-child-r1: the Child Fare Review as it stood when this version was costed ?>
<h3 <?= $h3 ?>>Child Fare Review <span style="font-weight:normal; font-size:11px;">— <?= (int) $cfr['adults'] ?> adult(s) and <?= count($cfr['children']) ?> child(ren): <?= esc(implode('; ', array_map(static fn ($k) => 'child ' . (int) $k['n'] . ' ' . ($k['age'] !== null ? $k['age'] . ' yrs' : 'age not given') . ($k['bed'] ? ', ' . str_replace('_', ' ', $k['bed']) : ''), $cfr['children']))) ?> · a child with no charge is still a passenger</span></h3>
<table cellpadding="0" cellspacing="0">
    <tr><th <?= $th . $w(8) ?>>Day</th><th <?= $th . $w(30) ?>>Item</th><th <?= $th . $w(14) ?>>Decision</th><th <?= $th . $w(36) ?>>Child calculation</th><th <?= $thr . $w(12) ?>>Child USD</th></tr>
    <?php foreach ($cfr['items'] as $ci): ?>
    <tr><td <?= $td ?>><?= esc((string) $ci['days']) ?></td><td <?= $td ?>><?= esc($ci['title']) ?></td>
        <td <?= $td ?>><?= $ci['status'] === 'confirmed' ? ($ci['decision'] === 'applicable' ? 'Applicable' : 'Not applicable') : '<span style="color:' . $RED . '; font-weight:bold;">' . esc($ci['status'] === 'review_again' ? 'Review again' : 'Needs checking') . '</span>' ?><?= !empty($ci['decided_by']) && $ci['status'] === 'confirmed' ? '<br><span style="color:' . $MUTED . '">' . esc($ci['decided_by']) . '</span>' : '' ?></td>
        <td <?= $td ?>><?= esc((string) ($ci['child_calc'] ?? '')) ?></td>
        <td <?= $tdr ?>><?= $ci['child_total'] !== null ? $n2($ci['child_total']) : '—' ?></td></tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h3 <?= $partHead ?> data-part="hotels">1 · Hotel cost <span style="font-weight:normal; font-size:11px;">— published rates × room mix, a subtotal for each hotel</span></h3>
<table cellpadding="0" cellspacing="0" data-part-table="hotels">
    <tr><th <?= $th . $w(10) ?>>Night</th><th <?= $th . $w(24) ?>>Hotel · room type · meal</th><th <?= $th . $w(18) ?>>Plan</th><th <?= $th . $w(8) ?>>Rooms</th><th <?= $th . $w(20) ?>>Lines</th><th <?= $thr . $w(11) ?>>Amount</th><th <?= $thr . $w(9) ?>>USD</th></tr>
    <?php foreach ($c['hotels'] as $h): ?>
    <tr>
        <td <?= $td ?>><?= (int) $h['day_no'] ?><br><span style="color:<?= $MUTED ?>"><?= $fmtD($h['date']) ?></span></td>
        <td <?= $td ?>><b><?= esc($h['hotel'] ?: 'no hotel named') ?></b><?= $h['area'] ? ' <span style="color:' . $MUTED . '">' . esc($h['area']) . '</span>' : '' ?><br><?= esc($h['room_type'] ?: '—') ?> · <?= esc($h['meal_plan'] ?: '—') ?> <?= $missing($h) ?></td>
        <td <?= $td ?>><?= esc($h['plan'] ?: '—') ?><?= $h['currency'] ? '<br><span style="color:' . $MUTED . '">' . esc($h['currency']) . '</span>' : '' ?></td>
        <td <?= $td ?>><?= esc($mixShort($h['mix'] ?? [])) ?></td>
        <td <?= $td ?>><?php foreach ($h['lines'] as $l): ?><?= (int) $l['qty'] ?> × <?= esc($l['label']) ?> @ <?= $n2($l['rate']) ?> = <?= $n2($l['amount']) ?><br><?php endforeach; ?>
            <?php foreach ($h['supplements'] as $s): ?><span style="color:<?= $AMB ?>"><?= esc($s['name']) ?>: <?= (int) $s['qty'] ?> × <?= $n2($s['rate']) ?> per <?= esc($s['per']) ?> = <?= $n2($s['amount']) ?></span><br><?php endforeach; ?></td>
        <td <?= $tdr ?>><?= $h['amount'] !== null ? esc($h['currency']) . ' ' . $n2($h['amount']) : '—' ?></td>
        <td <?= $tdr ?>><?= $n2($h['amount_usd']) ?></td>
    </tr>
    <?php if (isset($stayEnd[(int) $h['day_no']])): $st = $stayEnd[(int) $h['day_no']]; ?>
    <tr class="hotel-subtotal"><td colspan="6" <?= $subLbl ?>>Subtotal · <b><?= esc($st['hotel'] ?: 'no hotel named') ?></b> · <?= esc($nightsWord($st['nights'])) ?><?= !$st['complete'] ? ' <span style="color:' . $RED . '">— not every night could be costed</span>' : '' ?></td><td <?= $subRow ?>><b><?= $n2($st['amount_usd']) ?></b></td></tr>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php if (!$c['hotels']): ?><tr><td colspan="7" <?= $td ?>><span style="color:<?= $MUTED ?>">No nights on this inquiry.</span></td></tr><?php endif; ?>
    <?php foreach (($c['transfer_stays'] ?? []) as $ts): /* tf-transfer-r2: a hotel airport transfer by a hotel they do not stay at — payable to that hotel */ ?>
    <tr><td <?= $td ?>><?= $ts['transfer'] === 'arrival' ? 'arrival' : 'departure' ?></td><td colspan="4" <?= $td ?>><b><?= esc($ts['hotel']) ?></b><br><span style="color:<?= $AMB ?>"><?= esc($ts['label']) ?></span></td><td <?= $tdr ?>>USD <?= $n2($ts['amount_usd']) ?></td><td <?= $tdr ?>><?= $n2($ts['amount_usd']) ?></td></tr>
    <?php endforeach; ?>
    <?php if ($costOn): ?><tr><td colspan="6" <?= $partLbl ?>>Hotel cost</td><td <?= $partRow ?> data-part-total="hotels"><?= $n2($parts['hotels']['usd']) ?></td></tr><?php endif; ?>
</table>

<h3 <?= $partHead ?> data-part="paid">2 · Paid activities &amp; services <span style="font-weight:normal; font-size:11px;">— entrances, tickets, activities and excursions at their master rates</span></h3>
<div <?= $subHead ?>>Entrances &amp; tickets — the priced list × the party, USD</div>
<table cellpadding="0" cellspacing="0" data-part-table="paid">
    <tr><th <?= $th . $w(7) ?>>Day</th><th <?= $th . $w(38) ?>>Entrance / ticket / service</th><th <?= $th . $w(23) ?>>Who settles it</th><th <?= $thr . $w(8) ?>>Qty</th><th <?= $thr . $w(12) ?>>Unit USD</th><th <?= $thr . $w(12) ?>>USD</th></tr>
    <?php foreach ($paidLines as $s): ?>
    <tr><td <?= $td ?>><?= (int) $s['day_no'] ?: 'tour' ?></td><td <?= $td ?>><?= esc($s['title']) ?> <span style="color:<?= $MUTED ?>"><?= esc($s['kind']) ?></span> <?= $missing($s) ?></td>
        <td <?= $td ?>><?= esc(str_replace('_', ' ', $s['pay_mode'])) ?><?= !$s['costed'] && $s['why'] ? '<br><span style="color:' . $MUTED . '">' . esc($s['why']) . '</span>' : '' ?></td>
        <td <?= $tdr ?>><?= $s['qty'] !== null ? esc((string) $s['qty']) : 'pp' ?></td><td <?= $tdr ?>><?= $n2($s['unit_usd']) ?></td>
        <td <?= $tdr ?>><?= $s['costed'] ? $n2($s['amount_usd']) : '<span style="color:' . $MUTED . '">' . ($s['amount_usd'] !== null ? '(' . $n2($s['amount_usd']) . ')' : '—') . '</span>' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$paidLines): ?><tr><td colspan="6" <?= $td ?>><span style="color:<?= $MUTED ?>">No paid entrance or service.</span></td></tr><?php endif; ?>
    <?php if ($costOn): ?><tr><td colspan="5" <?= $subLbl ?>>Subtotal · entrances &amp; tickets <span style="color:<?= $MUTED ?>">(the Entrance &amp; Activities total)</span></td><td <?= $subRow ?>><b><?= $n2($c['sections']['services']) ?></b></td></tr><?php endif; ?>
</table>
<div <?= $subHead ?>>Activities &amp; excursions — the master's rate × quantity</div>
<table cellpadding="0" cellspacing="0">
    <tr><th <?= $th . $w(7) ?>>Day</th><th <?= $th . $w(38) ?>>Activity / excursion</th><th <?= $th . $w(12) ?>>Charged</th><th <?= $thr . $w(7) ?>>Qty</th><th <?= $thr . $w(12) ?>>Rate</th><th <?= $thr . $w(13) ?>>Amount</th><th <?= $thr . $w(11) ?>>USD</th></tr>
    <?php foreach ($c['excursions'] as $x): ?>
    <tr><td <?= $td ?>><?= (int) $x['day_no'] ?></td><td <?= $td ?>><?= esc($x['name']) ?> <span style="color:<?= $MUTED ?>"><?= esc($x['code']) ?></span><?= !empty($x['includes_transport']) ? ' <span style="color:' . $GRN . '">transport included — local km waived</span>' : '' ?> <?= $missing($x) ?></td>
        <td <?= $td ?>><?= esc(str_replace('_', ' ', $x['unit'])) ?></td><td <?= $tdr ?>><?= esc((string) $x['qty']) ?></td><td <?= $tdr ?>><?= esc($x['currency']) ?> <?= $n2($x['rate']) ?></td>
        <td <?= $tdr ?>><?= esc($x['currency']) ?> <?= $n2($x['amount']) ?></td><td <?= $tdr ?>><?= $n2($x['amount_usd']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$c['excursions']): ?><tr><td colspan="7" <?= $td ?>><span style="color:<?= $MUTED ?>">No activities or excursions.</span></td></tr><?php endif; ?>
    <?php if ($costOn): ?><tr><td colspan="6" <?= $subLbl ?>>Subtotal · activities &amp; excursions</td><td <?= $subRow ?>><b><?= $n2($c['sections']['excursions']) ?></b></td></tr><?php endif; ?>
    <?php if ($costOn): ?><tr><td colspan="6" <?= $partLbl ?>>Paid activities &amp; services</td><td <?= $partRow ?> data-part-total="paid"><?= $n2($parts['paid']['usd']) ?></td></tr><?php endif; ?>
</table>
<?php if ($freeCount > 0): ?><p class="free-note" style="font-size:10.5px; color:<?= $MUTED ?>; margin:4px 0 0 0;"><?= (int) $freeCount ?> free visit<?= $freeCount === 1 ? '' : 's' ?> on the programme — in the itinerary only, never costed.</p><?php endif; ?>

<h3 <?= $partHead ?> data-part="transport">3 · Transport cost <span style="font-weight:normal; font-size:11px;">— the vehicle on its mileage, the driver and guide</span></h3>
<div <?= $subHead ?>>Vehicle &amp; mileage — kilometres × rate per km</div>
<table cellpadding="0" cellspacing="0" data-part-table="transport">
    <tr><th <?= $th . $w(24) ?>>Vehicle</th><th <?= $thr . $w(6) ?>>Legs</th><th <?= $thr . $w(9) ?>>Driven km</th><th <?= $thr . $w(9) ?>>Local km</th><th <?= $thr . $w(9) ?>>Waived km</th><th <?= $thr . $w(10) ?>>Charged km</th><th <?= $thr . $w(11) ?>>Rate/km</th><th <?= $thr . $w(13) ?>>Amount</th><th <?= $thr . $w(9) ?>>USD</th></tr>
    <?php foreach ($c['transport'] as $t): ?>
    <tr><td <?= $td ?>><b><?= esc($t['label']) ?></b> <?= esc($t['type'] ?: '') ?><?= (int) $t['qty'] > 1 ? ' × ' . (int) $t['qty'] : '' ?> <?= $missing($t) ?></td>
        <td <?= $tdr ?>><?= (int) $t['legs'] ?></td><td <?= $tdr ?>><?= $n1($t['km']) ?></td><td <?= $tdr ?>><?= $n1($t['extra_km']) ?></td>
        <td <?= $tdr ?>><?= (float) $t['waived_km'] > 0 ? $n1($t['waived_km']) : '—' ?></td><td <?= $tdr ?>><b><?= $n1($t['charged_km']) ?></b></td>
        <td <?= $tdr ?>><?= $t['km_rate'] !== null ? esc($t['currency']) . ' ' . $n2($t['km_rate']) : '<span style="color:' . $RED . '">none</span>' ?></td>
        <td <?= $tdr ?>><?= $t['amount'] !== null ? esc($t['currency']) . ' ' . $n2($t['amount']) : '—' ?></td><td <?= $tdr ?>><?= $n2($t['amount_usd']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$c['transport']): ?><tr><td colspan="9" <?= $td ?>><span style="color:<?= $MUTED ?>">No vehicle costed yet.</span></td></tr><?php endif; ?>
    <?php if ($costOn): ?><tr><td colspan="8" <?= $subLbl ?>>Subtotal · vehicle &amp; mileage</td><td <?= $subRow ?>><b><?= $n2($c['sections']['transport']) ?></b></td></tr><?php endif; ?>
</table>
<div <?= $subHead ?>>Driver &amp; guide — bata × days, and their rooms</div>
<table cellpadding="0" cellspacing="0">
    <tr><th <?= $th . $w(28) ?>>Role</th><th <?= $th . $w(24) ?>>Person</th><th <?= $thr . $w(8) ?>>Days</th><th <?= $thr . $w(14) ?>>Bata/day</th><th <?= $thr . $w(16) ?>>Amount</th><th <?= $thr . $w(10) ?>>USD</th></tr>
    <?php foreach ($c['crew'] as $cr): ?>
    <tr><td <?= $td ?>><?= esc($cr['role']) ?> <?= $missing($cr) ?><?php if (($cr['days_by'] ?? null) !== null): ?><br><span style="color:<?= $MUTED ?>; font-size:10px;"><?= ($cr['days_by'] ?? '') === 'human' ? 'days set by hand' : 'days from the programme' . (!empty($cr['days_basis']) ? ': ' . esc($cr['days_basis']) : '') ?></span><?php endif; ?></td><td <?= $td ?>><?= esc($cr['person'] ?: '—') ?></td><td <?= $tdr ?>><?= (int) $cr['days'] ?></td>
        <td <?= $tdr ?>><?= $cr['bata'] !== null ? esc($cr['currency']) . ' ' . $n2($cr['bata']) : '—' ?></td><td <?= $tdr ?>><?= $cr['amount'] !== null ? esc($cr['currency']) . ' ' . $n2($cr['amount']) : '—' ?></td><td <?= $tdr ?>><?= $n2($cr['amount_usd']) ?></td></tr>
    <?php endforeach; ?>
    <?php foreach ($c['crew_rooms'] as $r): ?>
    <tr><td <?= $td ?>><?= esc(ucfirst($r['kind'])) ?> accommodation <?= $missing($r) ?></td><td <?= $td ?>>—</td><td <?= $tdr ?>><?= (int) $r['nights'] ?> n</td>
        <td <?= $tdr ?>><?= $r['rate'] !== null ? esc($r['currency']) . ' ' . $n2($r['rate']) : '—' ?></td><td <?= $tdr ?>><?= $r['amount'] !== null ? esc($r['currency']) . ' ' . $n2($r['amount']) : '—' ?></td><td <?= $tdr ?>><?= $n2($r['amount_usd']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$c['crew'] && !$c['crew_rooms']): ?><tr><td colspan="6" <?= $td ?>><span style="color:<?= $MUTED ?>">No driver or guide on this inquiry.</span></td></tr><?php endif; ?>
    <?php if ($costOn): ?><tr><td colspan="5" <?= $subLbl ?>>Subtotal · driver &amp; guide <span style="color:<?= $MUTED ?>">bata <?= $n2($c['sections']['crew']) ?> + rooms <?= $n2($c['sections']['crew_rooms']) ?></span></td><td <?= $subRow ?>><b><?= $n2($c['sections']['crew'] + $c['sections']['crew_rooms']) ?></b></td></tr><?php endif; ?>
    <?php if ($costOn): ?><tr><td colspan="5" <?= $partLbl ?>>Transport cost</td><td <?= $partRow ?> data-part-total="transport"><?= $n2($parts['transport']['usd']) ?></td></tr><?php endif; ?>
</table>

<h3 <?= $h3 ?>><?= $costOn ? 'Total tour cost' : 'The client\'s price' ?></h3>
<table cellpadding="5" cellspacing="0" style="border:1px solid <?= $LINE ?>;" data-totals>
    <?php $i = 0; foreach ($parts as $k => $pt): $i++; ?>
    <?php if ($costOn): ?><tr><td width="70%" style="font-size:11.5px; border-bottom:1px solid #f0e9f0;"><?= $i ?> · <?= esc($pt['label']) ?></td><td width="30%" align="right" style="font-size:11.5px; border-bottom:1px solid #f0e9f0; font-family:Consolas,Menlo,monospace;" data-total-part="<?= esc($k) ?>">USD <?= $n2($pt['usd']) ?></td></tr><?php endif; ?>
    <?php endforeach; ?>
    <?php if ($costOn): ?>
    <tr style="background:#faf5fa;"><td style="font-size:12.5px; font-weight:bold;">Total tour cost <span style="font-weight:normal; color:<?= $MUTED ?>; font-size:11px;">(1 + 2 + 3)</span></td><td align="right" style="font-size:13px; font-weight:bold; font-family:Consolas,Menlo,monospace;" data-cost-total>USD <?= $n2($c['cost_usd']) ?></td></tr>
    <?php if (abs(round(array_sum(array_column($parts, 'usd')), 2) - round((float) $c['cost_usd'], 2)) >= 0.01): ?>
    <tr><td colspan="2" style="font-size:11px; color:<?= $RED ?>;">The three parts add up to USD <?= $n2(array_sum(array_column($parts, 'usd'))) ?>, not the tour cost. Check the costing again before quoting.</td></tr>
    <?php endif; ?>
    <tr><td style="font-size:11px; color:<?= $MUTED ?>;">in rupees at <?= $n2($c['usd_rate']) ?> · cost per person</td><td align="right" style="font-size:11px; color:<?= $MUTED ?>; font-family:Consolas,Menlo,monospace;">LKR <?= $n2($c['cost_lkr']) ?> · USD <?= $n2($c['per_person_cost_usd']) ?> pp</td></tr>
    <?php else: ?>
    <tr style="background:#faf5fa;"><td colspan="2" style="font-size:11.5px; color:<?= $AMB ?>;">The tour cost total, the markup and the profit belong to Management, under Pricing, and are not shown on this desk. The supplier figure on each line above stays, for the work itself.</td></tr>
    <?php endif; ?>
    <tr><td style="font-size:12.5px; font-weight:bold; border-top:1px solid <?= $LINE ?>;">Client's price<?= $c['markup']['sell_usd'] === null ? ' <span style="color:' . $AMB . '; font-weight:normal;">— pricing not set by Management</span>' : '' ?></td>
        <td align="right" style="font-size:13px; font-weight:bold; border-top:1px solid <?= $LINE ?>; font-family:Consolas,Menlo,monospace; color:<?= $P ?>;"><?= $c['markup']['sell_usd'] !== null ? 'USD ' . $n2($c['markup']['sell_usd']) . ' · ' . $n2($c['markup']['per_person_usd']) . ' pp' : '—' ?></td></tr>
</table>

<?php if ($mgmt): ?>
<table cellpadding="5" cellspacing="0" style="margin-top:10px; border:1px solid #d9d3dd; background:#ffffff;">
    <tr><td style="font-size:11px; color:<?= $AMB ?>;"><b>MANAGEMENT ONLY — markup &amp; profit.</b> Not shown to the desk, never on the client quotation.</td></tr>
    <tr><td style="font-size:11.5px;">Markup <b><?= $c['markup']['pct'] !== null ? $n2($c['markup']['pct']) . '%' : 'not set' ?></b> (<?= esc($c['markup']['source']) ?><?= $c['markup']['set_by'] ? ', set by ' . esc($c['markup']['set_by']) . ' ' . esc(substr((string) $c['markup']['set_at'], 0, 16)) : '' ?>)
        &nbsp;·&nbsp; Profit <b style="color:<?= $GRN ?>;"><?= $c['markup']['profit_usd'] !== null ? 'USD ' . $n2($c['markup']['profit_usd']) : '—' ?></b></td></tr>
</table>
<?php endif; ?>

<p style="font-size:10px; color:<?= $MUTED ?>; margin-top:18px; border-top:1px solid <?= $LINE ?>; padding-top:6px;">Every hotel figure is the published contract rate copied beside the night; every entrance, ticket, activity and excursion its master rate; every kilometre the driving history or a figure typed with permission; every bata the crew master. Free visits are on the itinerary only and never costed. Nothing was typed into this costing.</p>

<?php if (!$pdf): ?>
</div>
<?php if (!empty($auto_print)): ?><script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script><?php endif; ?>
<script>
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
