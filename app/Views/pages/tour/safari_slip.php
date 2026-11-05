<?php
/** tf-safari-r2: the safari payment slip(s) for one safari line — printable, one page a payee. Account numbers: the last four figures only. */
$fmt = static fn ($v) => number_format((float) $v, 2);
$fmtD = static function (?string $d): string { if (!$d) { return ''; } $t = strtotime($d); return $t ? date('d M Y', $t) : $d; };
$co = $company ?? ['name' => 'Avenir Holidays'];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Safari payment slip <?= esc($s['intake']['file_no'] ?? '') ?></title>
<style>
    :root { --o:#842484; --g:#78b848; --ink:#1d2733; --muted:#5f6f80; --line:#e2e6ec; }
    body { font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; color:var(--ink); background:#f4f5f8; margin:0; padding:20px; font-size:13px; }
    .bar { max-width:800px; margin:0 auto 12px; display:flex; gap:8px; justify-content:flex-end; }
    .bar button { background:var(--o); color:#fff; border:0; border-radius:18px; padding:8px 18px; font-size:13px; cursor:pointer; }
    .slip { background:#fff; max-width:800px; margin:0 auto 20px; border:1px solid var(--line); border-radius:10px; overflow:hidden; page-break-after:always; }
    .head { background:var(--o); color:#fff; padding:16px 22px; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:12px; }
    .head h1 { margin:0; font-size:19px; }
    .head .co { font-size:11.5px; opacity:.9; }
    .ref { text-align:right; }
    .ref b { font-size:16px; display:block; letter-spacing:.02em; white-space:nowrap; }
    .body { padding:18px 22px; }
    .grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
    .box { border:1px solid var(--line); border-radius:8px; padding:10px 12px; }
    .box h3 { margin:0 0 6px; font-size:11px; color:var(--o); text-transform:uppercase; letter-spacing:.05em; }
    .row { display:flex; gap:8px; } .row span { color:var(--muted); min-width:110px; }
    table { width:100%; border-collapse:collapse; margin-top:4px; }
    th { text-align:left; font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid var(--o); padding:7px 8px; }
    td { padding:8px; border-bottom:1px solid var(--line); vertical-align:top; }
    td.n, th.n { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
    tr.total td { border-top:2px solid var(--g); border-bottom:0; font-weight:700; font-size:15px; }
    .note { color:var(--muted); font-size:11.5px; margin-top:10px; }
    .sign { display:flex; justify-content:space-between; margin-top:34px; color:var(--muted); font-size:11.5px; }
    .sign div { border-top:1px solid var(--line); padding-top:4px; width:42%; }
    .err { max-width:800px; margin:40px auto; background:#fff; border:1px solid #efc9c5; color:#b23a32; padding:18px 22px; border-radius:10px; }
    @media print { body { background:#fff; padding:0; } .bar { display:none; } .slip { border:0; border-radius:0; margin:0; } }
    @media (max-width:640px) { .grid { grid-template-columns:1fr; } .ref { text-align:left; } }
</style></head>
<body>
<?php if (!empty($error)): ?>
    <div class="err"><b>The payment slip cannot be made yet.</b><br><?= esc($error) ?></div>
<?php else: ?>
    <div class="bar"><button onclick="window.print()">Print / Save as PDF</button></div>
    <?php foreach ($s['slips'] as $sl): $py = $sl['payee']; ?>
    <div class="slip">
        <div class="head">
            <div><h1>Safari payment slip</h1><div class="co"><?= esc($co['name'] ?? 'Avenir Holidays') ?><?= !empty($co['address']) ? ' · ' . esc($co['address']) : '' ?><?= !empty($co['phone']) ? ' · ' . esc($co['phone']) : '' ?><?= !empty($co['email']) ? ' · ' . esc($co['email']) : '' ?></div></div>
            <div class="ref">Reference<b><?= esc($sl['ref']) ?></b><span style="font-size:11px">Revision <?= (int) $sl['revision'] ?> · <?= esc(date('d M Y')) ?></span></div>
        </div>
        <div class="body">
            <div class="grid">
                <div class="box"><h3>Pay to — <?= esc($py['role_label']) ?></h3>
                    <div><b><?= esc($py['name']) ?></b><?= $py['supplier_acc'] ? ' <span style="color:var(--muted)">(' . esc($py['supplier_acc']) . ')</span>' : '' ?></div>
                    <?php if ($py['contact']): ?><div class="row"><span>Contact</span><?= esc($py['contact']) ?></div><?php endif; ?>
                    <?php if ($py['phone'] || $py['whatsapp']): ?><div class="row"><span>Phone / WhatsApp</span><?= esc(trim(($py['phone'] ?? '') . ($py['whatsapp'] ? ' / ' . $py['whatsapp'] : ''), ' /')) ?></div><?php endif; ?>
                    <?php if ($py['email']): ?><div class="row"><span>E-mail</span><?= esc($py['email']) ?></div><?php endif; ?>
                    <?php if ($py['bank'] || $py['account_masked']): ?><div class="row"><span>Bank</span><?= esc(trim(($py['bank'] ?? '') . ($py['branch'] ? ', ' . $py['branch'] : ''), ', ')) ?><?= $py['account_masked'] ? ' · account ' . esc($py['account_masked']) : '' ?></div><?php endif; ?>
                    <?php if ($py['account_holder']): ?><div class="row"><span>Account holder</span><?= esc($py['account_holder']) ?></div><?php endif; ?>
                    <?php if ($py['payment_terms']): ?><div class="row"><span>Terms</span><?= esc($py['payment_terms']) ?></div><?php endif; ?>
                </div>
                <div class="box"><h3>For</h3>
                    <div class="row"><span>Tour number</span><b><?= esc($s['intake']['tour_no'] ?: 'not yet confirmed') ?></b></div>
                    <div class="row"><span>File number</span><b><?= esc($s['intake']['file_no']) ?></b></div>
                    <?php if ($s['intake']['guest']): ?><div class="row"><span>Guest</span><?= esc($s['intake']['guest']) ?></div><?php endif; ?>
                    <?php if ($s['intake']['agent']): ?><div class="row"><span>Agent</span><?= esc($s['intake']['agent']) ?></div><?php endif; ?>
                    <div class="row"><span>Safari</span><?= esc($s['place']['name']) ?></div>
                    <div class="row"><span>Date</span><?= esc($fmtD($s['safari_date'])) ?></div>
                    <div class="row"><span>Party</span><?= (int) $s['intake']['adults'] ?> adult(s), <?= (int) $s['intake']['children'] ?> child(ren) · <?= (int) $s['jeeps'] ?> jeep(s)</div>
                </div>
            </div>
            <table>
                <thead><tr><th>Breakdown</th><?= $sl['together'] ? '<th>Ledger</th>' : '' ?><th class="n">Qty</th><th class="n">Rate</th><th class="n">Amount</th></tr></thead>
                <tbody>
                <?php foreach ($sl['lines'] as $l): ?>
                    <tr><td><?= esc($l['what']) ?></td><?= $sl['together'] ? '<td style="color:var(--muted);white-space:nowrap">' . esc($l['ledger'] ?? '') . '</td>' : '' ?><td class="n"><?= (int) $l['qty'] ?></td><td class="n"><?= esc($l['cur']) ?> <?= $fmt($l['rate']) ?></td><td class="n"><?= esc($l['cur']) ?> <?= $fmt($l['amount']) ?></td></tr>
                <?php endforeach; ?>
                <tr class="total"><td colspan="<?= $sl['together'] ? 4 : 3 ?>">Total to pay<?= $sl['together'] ? ' — jeep and entrance together' : '' ?></td><td class="n"><?= esc($sl['currency']) ?> <?= $fmt($sl['total']) ?></td></tr>
                </tbody>
            </table>
            <div class="note">Please quote reference <b><?= esc($sl['ref']) ?></b><?= $s['intake']['tour_no'] ? ' and tour number <b>' . esc($s['intake']['tour_no']) . '</b>' : ' and file number <b>' . esc($s['intake']['file_no']) . '</b>' ?> on your receipt. Children: <?= esc($s['place']['age_rule']) ?><?= $s['place']['seats'] ? ' A jeep carries up to ' . (int) $s['place']['seats'] . ' passengers.' : '' ?></div>
            <div class="sign"><div>Prepared by</div><div>Approved by</div></div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</body></html>
