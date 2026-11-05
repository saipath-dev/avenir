<?= view('layouts/head') ?>
<?php
/** tf-paydesk-r1 — Supplier Bank Details (master_files/supplier_bank): every supplier's bank-payment information and its verification. */
$e = static fn ($s): string => esc((string) ($s ?? ''));
$mask = static fn ($no) => \App\Libraries\TourPayments\BankAccounts::mask($no, 4);
$show = !empty($can_edit) || !empty($can_verify);
?>
<style>
    .tpdm { --p:#842484; --soft:#f3e7f3; --ink:#2c3e50; --muted:#6c757d; --line:#e4e8ee; color:var(--ink); }
    .tpdm .btn-p { background:var(--p); border-color:var(--p); color:#fff; } .tpdm .btn-p:hover { background:#8f2a8d; color:#fff; }
    .tpdm .card { border:1px solid var(--line); border-radius:14px; }
    .tpdm .pill { display:inline-block; border-radius:999px; padding:2px 9px; font-size:11.5px; font-weight:700; }
    .tpdm .pill.verified { background:#e4f4ea; color:#1f8a4c; } .tpdm .pill.unverified { background:#fdf3e1; color:#b7791f; } .tpdm .pill.changed { background:#fbe9e7; color:#b23a32; } .tpdm .pill.inactive { background:#eef0f3; color:#6c757d; }
    .tpdm .mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
    .tpdm td, .tpdm th { font-size:13px; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content"><div class="container-fluid tpdm">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div><h4 class="mb-0" style="color:var(--p); font-weight:700;">Supplier Bank Details</h4><div class="small" style="color:var(--muted);">Hotels, restaurants and other suppliers: beneficiary, bank, account, currency, SWIFT, invoice and remittance e-mails, and who verified them. A changed or unverified account shows a strong warning on every payment and needs authorised confirmation before approval.</div></div>
            <form class="d-flex gap-2" method="get"><input class="form-control form-control-sm" name="q" value="<?= $e($q) ?>" placeholder="Search supplier"><button class="btn btn-sm btn-p">Search</button></form>
        </div>
        <?php if (empty($tpd_ready)): ?><div class="alert alert-warning">The payment workflow's tables are not set up yet (System Administrator: Tour Payment Dashboard → Set up).</div><?php endif; ?>
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Supplier</th><th>Beneficiary</th><th>Bank · branch</th><th>Account no.</th><th>Cur. / SWIFT</th><th>Invoice e-mail</th><th>Remittance e-mail</th><th>Status</th><th>Verified</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): $acc = $r['b_account'] ?: $r['sup_bank_acc']; ?>
                <tr>
                    <td><b><?= $e($r['Name']) ?></b><br><small class="mono" style="color:var(--muted);"><?= $e($r['Sup_Acc_No']) ?></small><?= (int) $r['bulk_payment'] === 1 ? ' <span class="pill inactive">bulk</span>' : '' ?></td>
                    <td><?= $e($r['beneficiary_name'] ?: $r['acc_holder']) ?></td>
                    <td><?= $e($r['b_bank'] ?: $r['bank_name']) ?><?= ($r['b_branch'] ?: $r['branch_name']) ? '<br><small style="color:var(--muted);">' . $e($r['b_branch'] ?: $r['branch_name']) . '</small>' : '' ?></td>
                    <td class="mono"><?= $acc ? $e($show ? $acc : $mask($acc)) : '<span style="color:var(--muted);">—</span>' ?><?php if ($r['b_account'] && $r['sup_bank_acc'] && preg_replace('/\s+/', '', $r['b_account']) !== preg_replace('/\s+/', '', $r['sup_bank_acc'])): ?><br><small class="text-danger">master: <?= $e($show ? $r['sup_bank_acc'] : $mask($r['sup_bank_acc'])) ?></small><?php endif; ?></td>
                    <td><?= $e($r['currency'] ?: 'LKR') ?><?= $r['swift'] ? '<br><small>' . $e($r['swift']) . '</small>' : '' ?></td>
                    <td class="small"><?= $e($r['invoice_email']) ?></td>
                    <td class="small"><?= $e($r['remittance_email'] ?: $r['email']) ?><?= !$r['remittance_email'] && $r['email'] ? '<br><small style="color:var(--muted);">(supplier master)</small>' : '' ?></td>
                    <td><span class="pill <?= $e($r['state']) ?>"><?= $e($r['state'] === 'changed' ? 'CHANGED' : ucfirst($r['state'])) ?></span></td>
                    <td class="small"><?= $r['verified_by'] ? $e($r['verified_by']) . '<br>' . $e(substr((string) $r['verified_at'], 0, 16)) : '—' ?></td>
                    <td class="text-nowrap">
                        <?php if (!empty($can_edit)): ?><button class="btn btn-sm btn-outline-secondary" onclick='editSupplier(<?= json_encode(['sup_acc' => $r['Sup_Acc_No'], 'name' => $r['Name'], 'beneficiary_name' => $r['beneficiary_name'] ?: $r['acc_holder'], 'bank_name' => $r['b_bank'] ?: $r['bank_name'], 'branch' => $r['b_branch'] ?: $r['branch_name'], 'account_no' => $acc, 'currency' => $r['currency'] ?: 'LKR', 'swift' => $r['swift'], 'invoice_email' => $r['invoice_email'], 'remittance_email' => $r['remittance_email'] ?: $r['email'], 'note' => $r['note'], 'state' => $r['state']], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button><?php endif; ?>
                        <?php if (!empty($can_verify) && $r['state'] !== 'verified' && $acc): ?><button class="btn btn-sm btn-p" onclick="verifySupplier('<?= $e($r['Sup_Acc_No']) ?>', '<?= $e(addslashes($r['Name'])) ?>')">Verify</button><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="10" class="text-center py-4" style="color:var(--muted);">No supplier matches.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div></div>
        <div class="small mt-2" style="color:var(--muted);">The supplier master's own bank columns are not changed here. Verification fingerprints the details as they stand; if the master's account is edited afterwards the record shows CHANGED until it is verified again. Every save and verification is in the audit record.</div>
    </div></div></div>
</div>

<div class="modal fade tpdm" id="supModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold" id="sup_title">Supplier bank details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><form id="supForm" onsubmit="return false;"><input type="hidden" name="sup_acc" id="s_acc">
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label small fw-semibold">Beneficiary name</label><input class="form-control" name="beneficiary_name" id="s_ben"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Bank name</label><input class="form-control" name="bank_name" id="s_bank"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Branch</label><input class="form-control" name="branch" id="s_branch"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Account number</label><input class="form-control mono" name="account_no" id="s_no" autocomplete="off"></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Currency</label><select class="form-select" name="currency" id="s_cur"><option>LKR</option><option>USD</option><option>EUR</option><option>GBP</option></select></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">SWIFT / BIC</label><input class="form-control" name="swift" id="s_swift"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Supplier invoice e-mail</label><input class="form-control" name="invoice_email" id="s_inv"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Accounts / remittance e-mail</label><input class="form-control" name="remittance_email" id="s_rem"></div>
            <div class="col-md-6 d-flex align-items-end"><label class="form-check"><input class="form-check-input" type="checkbox" name="inactive" value="1" id="s_inactive"> <span>Mark inactive (do not pay to this account)</span></label></div>
            <div class="col-12"><label class="form-label small fw-semibold">Note</label><input class="form-control" name="note" id="s_note" maxlength="300"></div>
        </div>
        <div class="small mt-2" style="color:var(--muted);">Changing the account, bank, branch, beneficiary, SWIFT or currency removes the verification; a holder of the verification privilege verifies again.</div>
    </form></div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-p" onclick="saveSupplier()"><i class="ri-save-line me-1"></i>Save</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
const CSRF = ['<?= csrf_token() ?>', '<?= csrf_hash() ?>'];
function editSupplier(s) {
    document.getElementById('sup_title').textContent = s.name;
    const set = (id, v) => { document.getElementById(id).value = v === undefined || v === null ? '' : v; };
    set('s_acc', s.sup_acc); set('s_ben', s.beneficiary_name); set('s_bank', s.bank_name); set('s_branch', s.branch); set('s_no', s.account_no); set('s_cur', s.currency || 'LKR'); set('s_swift', s.swift); set('s_inv', s.invoice_email); set('s_rem', s.remittance_email); set('s_note', s.note);
    document.getElementById('s_inactive').checked = s.state === 'inactive';
    new bootstrap.Modal(document.getElementById('supModal')).show();
}
async function saveSupplier() {
    const fd = new FormData(document.getElementById('supForm')); fd.append(CSRF[0], CSRF[1]);
    const j = await fetch('<?= base_url('master_files/supplier_bank/save') ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).catch(() => ({ status: 0, message: 'Network error' }));
    if (j.status === 200) Swal.fire({ icon: 'success', title: j.message, timer: 1600, showConfirmButton: false }).then(() => location.reload()); else Swal.fire('Not saved', j.message || 'Could not save.', 'error');
}
async function verifySupplier(acc, name) {
    const r = await Swal.fire({ title: 'Verify bank details', html: `<div class="text-start small">${name}: confirm that the beneficiary, bank, branch and account number were checked with the supplier (by a signed letterhead, a call back to a known number, or an earlier successful payment).</div>`, input: 'text', inputPlaceholder: 'How were they checked?', showCancelButton: true, confirmButtonText: 'Verified', inputValidator: v => !v ? 'Say how they were checked' : null });
    if (!r.isConfirmed) return;
    const fd = new FormData(); fd.append('sup_acc', acc); fd.append('note', r.value); fd.append(CSRF[0], CSRF[1]);
    const j = await fetch('<?= base_url('master_files/supplier_bank/verify') ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).catch(() => ({ status: 0, message: 'Network error' }));
    if (j.status === 200) Swal.fire({ icon: 'success', title: j.message, timer: 1600, showConfirmButton: false }).then(() => location.reload()); else Swal.fire('Not verified', j.message || 'Could not verify.', 'error');
}
</script>
</body>
