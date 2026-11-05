<?php
/**
 * A payment voucher for one driver or guide, for the days actually worked (schema 24).
 *
 * Printed from the Transport Desk; the payment side pays against it. Everything on it is
 * read from the voucher row as it was issued (TourService::crewVoucher) — the person and
 * their ledger account, the tour, the vehicle, the first and last day, the days, the bata
 * per day and where it came from, the amount — so a later change on the card never moves
 * a voucher already printed. A cancelled voucher prints marked CANCELLED.
 *
 *   $voucher   the voucher row joined to the tour and the card
 *   $crew      the crew row it was issued to
 *   $relation  "Replaced X, whose last day was …" / "Replaced by Y from …", or null
 *   $supplier  the ledger record (Name, Address, TP, Mobile), or null
 *   $company   the company's lines from settings
 *   $logo      the URL of the company logo, or null
 *   $user      who is printing
 */
$v    = $voucher;
$co   = $company;
$e    = static fn ($x): string => esc((string) ($x ?? ''));
$fmt  = static function (?string $ymd): string {
    if (!$ymd) {
        return '—';
    }
    $ts = strtotime($ymd);
    return $ts ? date('D, d M Y', $ts) : $ymd;
};
$money = static fn ($n): string => number_format((float) $n, 2);
$cancelled = (string) ($v['state'] ?? '') === 'cancelled';
$days  = (int) $v['days'];
$who   = trim((string) $v['person_name']);
$contactBits = array_values(array_filter([$co['address'] ?? null, !empty($co['phone']) ? 'Tel ' . $co['phone'] : null, !empty($co['email']) ? 'Email ' . $co['email'] : null]));
$py = $payee ?? null;   // tf-crew-master-r1: the Driver & Guide Master's record behind the line, when there is one
$payeeBits   = array_values(array_filter([
    $py ? trim('Legal name ' . $py['name'] . (!empty($py['nickname']) ? ' · known as "' . $py['nickname'] . '"' : '') . (!empty($py['category']) ? ' · ' . $py['category'] . (empty($py['category_confirmed']) ? ' (category to confirm)' : '') : '')) : null,
    $py && !empty($py['bank_visible']) ? (!empty($py['bank_account_no']) ? implode(' · ', array_filter(['Bank account name ' . ($py['bank_account_name'] ?: '(not on file)'), trim(($py['bank_name'] ?? '') . ' ' . ($py['bank_branch'] ?? '')), 'A/C ' . $py['bank_account_no']])) . (!empty($py['bank_verified']) ? ' · verified' . (!empty($py['bank_verified_at']) ? ' ' . $py['bank_verified_at'] : '') : ' · NOT VERIFIED') : 'No bank account on the Driver & Guide Master') : null,
    $py && !empty($py['bank_visible']) && !empty($py['payment_remarks']) ? 'Payment remarks: ' . $py['payment_remarks'] : null,
    !empty($v['supplier_acc']) ? 'Ledger account ' . $v['supplier_acc'] : 'Not on the supplier ledger — named by hand',
    $supplier['Address'] ?? null,
    !empty($supplier['TP']) ? 'Tel ' . $supplier['TP'] : null,
    !empty($supplier['Mobile']) ? 'Mobile ' . $supplier['Mobile'] : null,
]));
$rows = [
    ['Voucher number', $v['voucher_no']],
    ['Tour', trim(($v['tour_no'] ?? '') . ($v['tour_name'] ? ' · ' . $v['tour_name'] : '') . ($v['agent_name'] ? ' · via ' . $v['agent_name'] : ''))],
    ['Tour dates', $fmt($v['arrival_date']) . ' – ' . $fmt($v['departure_date']) . ($v['pax_total'] !== null ? ' · ' . (int) $v['pax_total'] . ' pax' : '')],
    ['Engaged as', trim($v['role_name'] . ' (' . $v['kind'] . ')' . ($v['vehicle_reg'] ? ' · vehicle ' . $v['vehicle_reg'] : ($v['kind'] === 'driver' && !empty($v['vehicle_type']) ? ' · ' . $v['vehicle_type'] : '')))],
    ['Days worked', $fmt($v['date_from']) . ' – ' . $fmt($v['date_to']) . ' · ' . $days . ' day' . ($days === 1 ? '' : 's')],
    [($v['kind'] === 'guide' ? 'Guide fee' : 'Bata') . ' per day', $v['currency'] . ' ' . $money($v['rate']) . ' (' . ($v['rate_source'] === 'person'
        ? match ($crew['person_bata_source'] ?? null) { 'standard' => 'the standard ' . ($v['kind'] === 'guide' ? 'guide fee' : 'bata') . ' copied when engaged', 'typed' => 'the figure typed for this tour', 'agreed' => 'own agreed ' . ($v['kind'] === 'guide' ? 'guide fee' : 'bata'), default => 'own agreed bata on the ledger' }
        : 'the master\'s bata for the role') . ')'],
    ['Amount', $v['currency'] . ' ' . $money($v['amount']) . '  =  ' . $days . ' × ' . $money($v['rate'])],
];
// tf-crew-master-r1: the figures copied from the Driver & Guide Master the day the person was engaged, for the record
$cl = $crew ?? [];
if (!empty($cl['person_bata_from']) || (isset($cl['person_overnight']) && $cl['person_overnight'] !== null && $cl['person_overnight'] !== '') || (isset($cl['person_km_rate']) && $cl['person_km_rate'] !== null && $cl['person_km_rate'] !== '')) {
    $bits = [];
    if (!empty($cl['person_bata_from'])) {
        $bits[] = (($cl['person_bata_source'] ?? '') === 'standard' ? 'the standard ' : 'agreed ') . ($v['kind'] === 'guide' ? 'guide fee' : 'bata') . ' in force from ' . $cl['person_bata_from'];
    }
    if (isset($cl['person_overnight']) && $cl['person_overnight'] !== null && $cl['person_overnight'] !== '') {
        $bits[] = 'overnight allowance LKR ' . $money($cl['person_overnight']) . ' per night' . (($cl['person_overnight_source'] ?? '') === 'standard' ? ' (standard' : ' (agreed') . (!empty($cl['person_overnight_from']) ? ' from ' . $cl['person_overnight_from'] : '') . ') — not in this amount; nothing is added for nights away until the overnight rule is confirmed';
    }
    if (isset($cl['person_km_rate']) && $cl['person_km_rate'] !== null && $cl['person_km_rate'] !== '') {
        $bits[] = 'rate per km LKR ' . $money($cl['person_km_rate']) . ' (' . (($cl['person_rate_source'] ?? '') === 'special' ? 'special rate' : (($cl['person_rate_source'] ?? '') === 'general' ? 'general rate of the vehicle class' : 'the driver\'s rate')) . ', on the settlement)';
    }
    $rows[] = ['Copied from the master', implode(' · ', $bits) . ' — the day the person was engaged; later changes on the master do not move this tour'];
}
if ($relation) {
    $rows[] = ['Replacement', $relation];
}
if (!empty($v['note'])) {
    $rows[] = ['Note', $v['note']];
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($v['voucher_no']) ?> — payment voucher<?= $cancelled ? ' (cancelled)' : '' ?></title>
<style>
    :root { --ink:#1d2733; --muted:#5f6f80; --line:#cfd6de; --purple:#842484; --red:#b23a32; }
    * { box-sizing:border-box; }
    body { margin:0; background:#eef0f3; color:var(--ink); font:13px/1.45 "Segoe UI", Arial, Helvetica, sans-serif; }
    .sheet { position:relative; background:#fff; max-width:820px; margin:18px auto; padding:34px 40px 30px; box-shadow:0 2px 12px rgba(0,0,0,.08); }
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
    table.kv td.k { width:30%; font-weight:600; background:#f6f7fa; }
    table.kv tr.amt td { font-size:15px; font-weight:700; }
    p { margin:0 0 10px; }
    .sigs { display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; margin-top:38px; }
    .sigs div { border-top:1px solid var(--ink); padding-top:6px; font-size:12px; color:var(--muted); }
    .foot { margin-top:26px; padding-top:8px; border-top:1px solid var(--line); color:var(--muted); font-size:11px; display:flex; justify-content:space-between; gap:12px; }
    .stamp { position:absolute; top:36%; left:50%; transform:translate(-50%,-50%) rotate(-22deg); font-size:64px; font-weight:800; letter-spacing:.2em; color:var(--red); opacity:.18; border:6px solid var(--red); padding:6px 22px; border-radius:12px; pointer-events:none; }
    @media print { body { background:#fff; } .sheet { box-shadow:none; margin:0; max-width:none; padding:10mm 12mm; } .bar { display:none; } }
</style>
</head>
<body>
<div class="bar">
    <span class="help">The payment voucher for <?= $e($who) ?>. Print, or save as PDF from the print dialog.<?= $cancelled ? ' This voucher is cancelled.' : '' ?></span>
    <button type="button" onclick="window.print()">Print</button>
</div>
<div class="sheet">
    <?php if ($cancelled): ?><div class="stamp">CANCELLED</div><?php endif; ?>
    <header>
        <div class="co"><b><?= $e($co['name'] ?: 'Avenir Holidays') ?></b><?php foreach ($contactBits as $b): ?><span><?= $e($b) ?></span><?php endforeach; ?></div>
        <?php if (!empty($logo)): ?><img src="<?= $e($logo) ?>" alt=""><?php endif; ?>
    </header>
    <h1>Payment voucher — <?= $e($v['kind'] === 'guide' ? 'guide' : 'driver') ?> bata</h1>
    <div class="ref">Voucher <b><?= $e($v['voucher_no']) ?></b> · tour <b><?= $e($v['tour_no']) ?></b> · issued <?= $e(date('d M Y H:i', strtotime((string) $v['issued_at']))) ?><?= !empty($v['issued_by']) ? ' by ' . $e($v['issued_by']) : '' ?><?= $cancelled ? ' · <b style="color:var(--red)">cancelled ' . $e(date('d M Y H:i', strtotime((string) $v['cancelled_at']))) . '</b>' . (!empty($v['cancel_note']) ? ' — ' . $e($v['cancel_note']) : '') : '' ?></div>
    <div class="to"><b>Pay to: <?= $e($who) ?></b><?php foreach ($payeeBits as $b): ?><span><?= $e($b) ?></span><?php endforeach; ?></div>
    <table class="kv">
        <?php foreach ($rows as [$k, $val]): ?><tr class="<?= $k === 'Amount' ? 'amt' : '' ?>"><td class="k"><?= $e($k) ?></td><td><?= $e($val) ?></td></tr><?php endforeach; ?>
    </table>
    <p>Bata for the days worked on the tour above, at the rate shown. Advances already paid against this tour are set off by the payment side; this voucher does not itself record them.</p>
    <div class="sigs"><div>Prepared by<?= !empty($v['issued_by']) ? ' · ' . $e($v['issued_by']) : '' ?></div><div>Checked by</div><div>Approved by</div><div>Received by · <?= $e($who) ?></div></div>
    <div class="foot"><span><?= $e($co['name']) ?><?= $contactBits ? ' · ' . $e(implode(' · ', $contactBits)) : '' ?></span><span>Printed <?= $e(date('d M Y H:i')) ?><?= !empty($user) ? ' by ' . $e($user) : '' ?></span></div>
</div>
</body>
</html>
