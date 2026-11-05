<?= view('layouts/head') ?>
<?php
/**
 * tf-paydesk-r3 — Tour Payment Desk settings (tour/tour_payment_dashboard/settings): the ledger mapping of the Realised
 * Exchange Gain and Realised Exchange Loss accounts, chosen from the chart of accounts (nothing is invented here — Accounts
 * creates or approves the accounts first), and the state of the desk's tables, privileges and the voucher-number guard.
 * tf-paydesk-r4 — and the Bank Charges account: the bank's charges on a payment are posted to it (Dr) against the paying
 * account (Cr), per tour line, so the paying account's ledger equals what the bank debited.
 */
$P = $tpd_perms ?? [];
$m = $fx_mapping;
$cm = $charge_mapping ?? ['complete' => false, 'code' => '', 'account' => null, 'warning' => 'no Bank Charges account mapped'];
$st = $schema;
$e = static fn ($s): string => esc((string) ($s ?? ''));
?>
<style>
    .tps { --p:#842484; --p2:#8f2a8d; --soft:#f3e7f3; --ink:#2c3e50; --muted:#6c757d; --line:#e4e8ee; color:var(--ink); }
    .tps .btn-p { background:var(--p); border-color:var(--p); color:#fff; } .tps .btn-p:hover { background:var(--p2); color:#fff; }
    .tps .card { border:1px solid var(--line); border-radius:14px; } .tps .card h6 { font-weight:700; color:var(--p); }
    .tps .muted { color:var(--muted); } .tps .mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
    .tps .pill { display:inline-block; border-radius:999px; padding:2px 9px; font-size:11.5px; font-weight:700; }
    .tps .pill.green { background:#e4f4ea; color:#1f8a4c; } .tps .pill.red { background:#fde8e8; color:#b42318; } .tps .pill.amber { background:#fff3d6; color:#9a6400; }
    .tps .acc-list { max-height:240px; overflow:auto; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content"><div class="container-fluid tps">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div><h4 class="mb-0" style="color:var(--p); font-weight:700;"><i class="ri-settings-3-line me-1"></i>Tour Payment Desk — Settings</h4>
                <div class="small muted">Ledger mapping for exchange-rate differences and bank charges, and the state of the desk's tables and privileges.</div></div>
            <div class="d-flex gap-2"><a class="btn btn-sm btn-outline-secondary" href="<?= base_url('tour/tour_payment_dashboard/payables') ?>"><i class="ri-stack-line me-1"></i>Supplier Payables</a><a class="btn btn-sm btn-outline-secondary" href="<?= base_url('tour/tour_payment_dashboard') ?>"><i class="ri-dashboard-line me-1"></i>Dashboard</a></div>
        </div>
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card"><div class="card-body">
                    <h6><i class="ri-exchange-funds-line me-1"></i>Ledger mapping — exchange gain / loss and bank charges</h6>
                    <?php if ($m['complete']): ?>
                        <div class="alert alert-success py-2 small mb-2"><i class="ri-checkbox-circle-line me-1"></i><b>Exchange differences:</b> mapped. At every final Paid of a foreign-currency payment the difference between the final LKR and the provisional (approval) LKR is posted per tour line: a rise in the rate to the <b>loss</b> account, a fall to the <b>gain</b> account, each pair balanced against the supplier's ledger account.</div>
                    <?php else: ?>
                        <div class="alert alert-danger py-2 small mb-2"><i class="ri-error-warning-line me-1"></i><b>Configuration warning.</b> <?= $e($m['warning']) ?></div>
                    <?php endif; ?>
                    <?php if ($cm['complete']): ?>
                        <div class="alert alert-success py-2 small mb-3"><i class="ri-checkbox-circle-line me-1"></i><b>Bank charges:</b> mapped to <b><?= $e($cm['account']['code'] . ' ' . $cm['account']['name']) ?></b>. The charges the bank takes on a payment are posted once at Paid — Dr Bank Charges / Cr the Avenir paying account — one balanced pair per tour line (allocated pro rata to each tour's final LKR, to the cent), so the paying account's ledger equals the amount the bank debited (payment + charges).</div>
                    <?php else: ?>
                        <div class="alert alert-danger py-2 small mb-3"><i class="ri-error-warning-line me-1"></i><b>Configuration warning.</b> <?= $e($cm['warning']) ?></div>
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Realised Exchange Gain — ledger account</label>
                            <div class="input-group"><span class="input-group-text">L_Code</span><input type="text" class="form-control mono" id="gain_code" value="<?= $e($m['gain_code']) ?>" placeholder="choose below"></div>
                            <div class="form-text" id="gain_name"><?= $m['gain'] ? $e($m['gain']['name']) : ($m['gain_code'] !== '' ? '<span class="text-danger">not an active ledger account</span>' : 'not mapped') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Realised Exchange Loss — ledger account</label>
                            <div class="input-group"><span class="input-group-text">L_Code</span><input type="text" class="form-control mono" id="loss_code" value="<?= $e($m['loss_code']) ?>" placeholder="choose below"></div>
                            <div class="form-text" id="loss_name"><?= $m['loss'] ? $e($m['loss']['name']) : ($m['loss_code'] !== '' ? '<span class="text-danger">not an active ledger account</span>' : 'not mapped') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Bank Charges — ledger account (expense)</label>
                            <div class="input-group"><span class="input-group-text">L_Code</span><input type="text" class="form-control mono" id="charges_code" value="<?= $e($cm['code']) ?>" placeholder="choose below"></div>
                            <div class="form-text" id="charges_name"><?= $cm['account'] ? $e($cm['account']['name']) : ($cm['code'] !== '' ? '<span class="text-danger">not an active ledger account</span>' : 'not mapped — Paid with bank charges is blocked until it is') ?></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Find an account in the chart of accounts</label>
                            <div class="d-flex gap-2"><input type="text" class="form-control" id="acc_q" placeholder="type part of the account name or code (e.g. exchange, bank charges)"><button class="btn btn-outline-secondary" onclick="findAccounts()"><i class="ri-search-line"></i></button></div>
                            <div class="list-group acc-list mt-2" id="acc_list"></div>
                            <div class="form-text">Only existing, active ledger accounts can be mapped, and the three must be different accounts. If Accounts has not created them yet, they must be created in the chart of accounts first — nothing is invented here.</div>
                        </div>
                        <div class="col-12"><label class="form-label fw-semibold small">Reason / note for the audit record (optional)</label><input type="text" class="form-control" id="map_reason" maxlength="300"></div>
                        <div class="col-12"><button class="btn btn-p" onclick="saveMapping()"><i class="ri-save-line me-1"></i>Save mapping</button></div>
                    </div>
                </div></div>
            </div>
            <div class="col-lg-5">
                <div class="card"><div class="card-body">
                    <h6><i class="ri-database-2-line me-1"></i>The desk's tables and privileges</h6>
                    <div class="small">Schema version <b><?= (int) $st['version'] ?></b> of <?= (int) $st['target'] ?> · <?= $st['ready'] ? '<span class="pill green">ready</span>' : '<span class="pill amber">setup needed</span>' ?></div>
                    <div class="small mt-2"><b>Tables:</b> <?= implode(' · ', array_map(static fn ($t, $ok) => $e($t) . ($ok ? '' : ' <span class="text-danger">(missing)</span>'), array_keys($st['tables']), $st['tables'])) ?></div>
                    <div class="small mt-2"><b>Privileges:</b> <?= implode(' · ', array_map(static fn ($u, $ok) => $e($u) . ($ok ? '' : ' <span class="text-danger">(missing)</span>'), array_keys($st['menus']), $st['menus'])) ?></div>
                    <?php $vn = $st['voucher_number'] ?? []; ?>
                    <div class="small mt-3"><b>Official Payment Voucher No.</b> = the voucher's own number (voucher.Vnum), one per voucher, assigned when the batch issues it.<br>
                        Unique index: <?= !empty($vn['unique_index']) ? '<span class="pill green">in place — a duplicate number is refused</span>' : '<span class="pill amber">not in place' . ((int) ($vn['duplicates'] ?? 0) > 0 ? ' — ' . (int) $vn['duplicates'] . ' duplicate(s) exist' : '') . '</span>' ?> ·
                        Update guard: <?= !empty($vn['guard_trigger']) ? '<span class="pill green">in place — an issued number cannot be changed</span>' : '<span class="pill amber">not in place</span>' ?></div>
                    <?php if (!empty($P['admin'])): ?><div class="mt-3"><button class="btn btn-sm btn-outline-secondary" onclick="runSetup()"><i class="ri-tools-line me-1"></i>Run setup (create what is missing)</button></div><?php endif; ?>
                </div></div>
            </div>
        </div>
    </div></div></div>
</div>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
const TPD_URL = '<?= base_url('tour/tour_payment_dashboard/') ?>';
const esc = s => String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function postJson(path, body) {
    const fd = Object.entries(body || {}).reduce((f, [k, v]) => { if (v !== undefined && v !== null) f.append(k, v); return f; }, new FormData());
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    try { const r = await fetch(TPD_URL + path, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }); return await r.json(); } catch (e) { return { status: 0, message: 'Network error.' }; }
}
async function findAccounts() {
    const q = document.getElementById('acc_q').value.trim(); if (!q) return;
    const j = await fetch(`${TPD_URL}setup?q=${encodeURIComponent(q)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).catch(() => ({}));
    const rows = (j && j.accounts) || [];
    document.getElementById('acc_list').innerHTML = rows.length ? rows.map(a => `<div class="list-group-item d-flex justify-content-between align-items-center py-1"><span><span class="mono">${esc(a.L_Code)}</span> · ${esc(a.PName)}</span><span><button class="btn btn-sm btn-outline-success" onclick="pick('gain', '${esc(a.L_Code)}', '${esc(a.PName).replace(/'/g, '&#39;')}')">use as gain</button> <button class="btn btn-sm btn-outline-danger" onclick="pick('loss', '${esc(a.L_Code)}', '${esc(a.PName).replace(/'/g, '&#39;')}')">use as loss</button> <button class="btn btn-sm btn-outline-secondary" onclick="pick('charges', '${esc(a.L_Code)}', '${esc(a.PName).replace(/'/g, '&#39;')}')">use as bank charges</button></span></div>`).join('') : '<div class="list-group-item muted">No active ledger account matches. Accounts must create it in the chart of accounts first.</div>';
}
function pick(which, code, name) { document.getElementById(which + '_code').value = code; document.getElementById(which + '_name').textContent = name.replace(/&#39;/g, "'"); }
async function saveMapping() {
    const j = await postJson('setup', { fx_gain_ledger_code: document.getElementById('gain_code').value.trim(), fx_loss_ledger_code: document.getElementById('loss_code').value.trim(), bank_charge_ledger_code: document.getElementById('charges_code').value.trim(), reason: document.getElementById('map_reason').value });
    Swal.fire({ icon: j.status === 200 && j.complete ? 'success' : (j.status === 200 ? 'warning' : 'error'), title: j.status === 200 ? 'Saved' : 'Not saved', text: j.message || '' }).then(() => { if (j.status === 200) location.reload(); });
}
async function runSetup() {
    const j = await postJson('setup', {});
    if (j.status === 200) { await Swal.fire({ icon: 'success', title: 'Set up', html: '<div class="text-start small">' + (j.notes || []).map(esc).join('<br>') + '</div>', width: 720 }); location.reload(); } else Swal.fire({ icon: 'error', title: 'Not done', text: j.message || '' });
}
document.getElementById('acc_q').addEventListener('keydown', e => { if (e.key === 'Enter') findAccounts(); });
</script>
</body>
