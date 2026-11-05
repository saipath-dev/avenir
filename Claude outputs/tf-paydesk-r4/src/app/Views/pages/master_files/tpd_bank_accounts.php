<?= view('layouts/head') ?>
<?php
/** tf-paydesk-r1 — the Avenir Bank Account Master (master_files/bank_accounts). */
$e = static fn ($s): string => esc((string) ($s ?? ''));
$mask = static fn ($no) => \App\Libraries\TourPayments\BankAccounts::mask($no, 4);
?>
<style>
    .tpdm { --p:#842484; --soft:#f3e7f3; --ink:#2c3e50; --muted:#6c757d; --line:#e4e8ee; color:var(--ink); }
    .tpdm .btn-p { background:var(--p); border-color:var(--p); color:#fff; } .tpdm .btn-p:hover { background:#8f2a8d; color:#fff; }
    .tpdm .card { border:1px solid var(--line); border-radius:14px; }
    .tpdm .pill { display:inline-block; border-radius:999px; padding:2px 9px; font-size:11.5px; font-weight:700; }
    .tpdm .pill.on { background:#e4f4ea; color:#1f8a4c; } .tpdm .pill.off { background:#eef0f3; color:#6c757d; } .tpdm .pill.def { background:var(--soft); color:var(--p); }
    .tpdm .mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content"><div class="container-fluid tpdm">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div><h4 class="mb-0" style="color:var(--p); font-weight:700;">Avenir Bank Accounts</h4><div class="small" style="color:var(--muted);">The company's own accounts the accountant pays from. Each is tied to its ledger account, so the voucher, the accounting record and the final payment record name the same account. Supplier documents show it as its external-display setting allows.</div></div>
            <?php if (!empty($can_edit)): ?><button class="btn btn-p" onclick="editAccount(null)"><i class="ri-add-line me-1"></i>Add account</button><?php endif; ?>
        </div>
        <?php if (empty($tpd_ready)): ?><div class="alert alert-warning">The payment workflow's tables are not set up yet (System Administrator: Tour Payment Dashboard → Set up).</div><?php endif; ?>
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Internal label</th><th>Entity</th><th>Bank · branch</th><th>Account name</th><th>Account no.</th><th>Currency</th><th>SWIFT</th><th>Type</th><th>Ledger</th><th>On supplier documents</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (!$accounts): ?><tr><td colspan="12" class="text-center py-4" style="color:var(--muted);">No company bank account yet. Until one is added, the ledger's bank accounts are offered on approval and initiation.</td></tr><?php endif; ?>
                <?php foreach ($accounts as $a): ?>
                <tr>
                    <td><b><?= $e($a['label']) ?></b><?= (int) $a['is_default'] === 1 ? ' <span class="pill def">default</span>' : '' ?></td>
                    <td><?= $e($a['entity']) ?></td><td><?= $e($a['bank_name']) ?><?= $a['branch'] ? '<br><small style="color:var(--muted);">' . $e($a['branch']) . '</small>' : '' ?></td>
                    <td><?= $e($a['account_name']) ?></td><td class="mono"><?= $e(!empty($can_edit) ? $a['account_no'] : $mask($a['account_no'])) ?></td><td><?= $e($a['currency']) ?></td><td><?= $e($a['swift']) ?></td><td><?= $e($a['account_type']) ?></td>
                    <td class="mono small"><?= $e($a['ledger_code']) ?></td>
                    <td><?= $e($a['external_display'] === 'full' ? 'full number' : ($a['external_display'] === 'hidden' ? 'hidden' : 'masked, last ' . (int) $a['display_digits'] . ' digits')) ?></td>
                    <td><span class="pill <?= (int) $a['active'] === 1 ? 'on' : 'off' ?>"><?= (int) $a['active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                    <td><?php if (!empty($can_edit)): ?><button class="btn btn-sm btn-outline-secondary" onclick='editAccount(<?= json_encode(array_intersect_key($a, array_flip(['id', 'entity', 'bank_name', 'branch', 'account_name', 'account_no', 'currency', 'swift', 'account_type', 'label', 'ledger_code', 'is_default', 'active', 'external_display', 'display_digits', 'note'])), JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div></div></div>
        <div class="small mt-2" style="color:var(--muted);">Account numbers are visible here to holders of this master only; approvers and the dashboard see them masked. Every save is kept in the audit record.</div>
    </div></div></div>
</div>

<div class="modal fade tpdm" id="acctModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold" id="acct_title">Bank account</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><form id="acctForm" onsubmit="return false;"><input type="hidden" name="id" id="f_id">
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label small fw-semibold">Company / legal entity</label><input class="form-control" name="entity" id="f_entity" value="Avenir Holidays (Pvt) Ltd"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Internal label <span class="text-danger">*</span></label><input class="form-control" name="label" id="f_label" placeholder="e.g. Com Bank LKR operating account"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Bank name <span class="text-danger">*</span></label><input class="form-control" name="bank_name" id="f_bank_name"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Branch</label><input class="form-control" name="branch" id="f_branch"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Account name <span class="text-danger">*</span></label><input class="form-control" name="account_name" id="f_account_name"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Account number <span class="text-danger">*</span></label><input class="form-control mono" name="account_no" id="f_account_no" autocomplete="off"></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Currency</label><select class="form-select" name="currency" id="f_currency"><option>LKR</option><option>USD</option><option>EUR</option><option>GBP</option><option>JPY</option></select></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">SWIFT / BIC</label><input class="form-control" name="swift" id="f_swift"></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Account type</label><input class="form-control" name="account_type" id="f_account_type" placeholder="Current / Savings"></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Ledger account <span class="text-danger">*</span></label><select class="form-select" name="ledger_code" id="f_ledger"><option value="">— choose —</option><?php foreach ($ledgers as $l): ?><option value="<?= $e($l['L_Code']) ?>"><?= $e($l['PName']) ?> (<?= $e($l['L_Code']) ?>)</option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label small fw-semibold">On supplier documents</label><select class="form-select" name="external_display" id="f_external"><option value="masked">Masked (last digits only)</option><option value="full">Full account number</option><option value="hidden">Hidden</option></select></div>
            <div class="col-md-2"><label class="form-label small fw-semibold">Digits shown</label><input type="number" min="2" max="8" class="form-control" name="display_digits" id="f_digits" value="4"></div>
            <div class="col-md-3 d-flex align-items-end"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_default" value="1" id="f_default"> <span>Default account</span></label></div>
            <div class="col-md-3 d-flex align-items-end"><label class="form-check"><input class="form-check-input" type="checkbox" name="active" value="1" id="f_active" checked> <span>Active</span></label></div>
            <div class="col-12"><label class="form-label small fw-semibold">Note</label><input class="form-control" name="note" id="f_note" maxlength="300"></div>
        </div></form></div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-p" onclick="saveAccount()"><i class="ri-save-line me-1"></i>Save</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
function editAccount(a) {
    a = a || {};
    document.getElementById('acct_title').textContent = a.id ? 'Edit bank account' : 'Add bank account';
    const set = (id, v) => { document.getElementById(id).value = v === undefined || v === null ? '' : v; };
    set('f_id', a.id || ''); set('f_entity', a.entity || 'Avenir Holidays (Pvt) Ltd'); set('f_label', a.label); set('f_bank_name', a.bank_name); set('f_branch', a.branch); set('f_account_name', a.account_name); set('f_account_no', a.account_no);
    set('f_currency', a.currency || 'LKR'); set('f_swift', a.swift); set('f_account_type', a.account_type); set('f_ledger', a.ledger_code || ''); set('f_external', a.external_display || 'masked'); set('f_digits', a.display_digits || 4); set('f_note', a.note);
    document.getElementById('f_default').checked = String(a.is_default) === '1'; document.getElementById('f_active').checked = a.active === undefined ? true : String(a.active) === '1';
    new bootstrap.Modal(document.getElementById('acctModal')).show();
}
async function saveAccount() {
    const fd = new FormData(document.getElementById('acctForm'));
    if (!document.getElementById('f_active').checked) fd.set('active', '0');
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    const j = await fetch('<?= base_url('master_files/bank_accounts/save') ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).catch(() => ({ status: 0, message: 'Network error' }));
    if (j.status === 200) { Swal.fire({ icon: 'success', title: j.message, timer: 1500, showConfirmButton: false }).then(() => location.reload()); } else { Swal.fire('Not saved', j.message || 'Could not save.', 'error'); }
}
</script>
</body>
