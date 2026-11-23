<?= view('layouts/head') ?>
<?php
/**
 * tf-bankreg-r1 (22 Oct 2026) — Supplier Bank Account Registry (master_files/supplier_accounts).
 * One list of every payee (hotels, drivers, guides, restaurants, entrance / activity suppliers, others) with their registered
 * bank accounts: supplier id and display name · exact account holder · bank · branch · account number · currency · default ·
 * active · verification. Accounts link to the supplier master by its id only. Numbers are masked unless the sign-in may see them.
 */
$e = static fn ($s): string => esc((string) ($s ?? ''));
$mask = static fn ($no) => \App\Libraries\TourPayments\BankAccounts::mask($no, 4);
$showNo = static fn ($no) => $no ? ($full_numbers ? $e($no) : $e($mask($no))) : '<span class="mut">—</span>';
$counts = ['all' => count($rows), 'missing' => 0, 'incomplete' => 0, 'unverified' => 0];
?>
<style>
    .sar { --p:#842484; --pd:#842484; --soft:rgba(132,36,132,.08); --line:rgba(132,36,132,.22); --ink:#26262c; --mut:#6b6f7b; color:var(--ink); font-family:"Inter","Segoe UI",system-ui,sans-serif; }
    .sar h4 { color:var(--p); font-weight:700; }
    .sar .btn-p { background:var(--p); border-color:var(--p); color:#fff; } .sar .btn-p:hover { background:var(--pd); border-color:var(--pd); color:#fff; }
    .sar .btn-o { border:1px solid var(--line); color:var(--pd); background:#fff; } .sar .btn-o:hover { background:var(--soft); }
    .sar .card { border:1px solid var(--line); border-radius:14px; }
    .sar .tabs { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 12px; }
    .sar .tab { border:1px solid var(--line); background:#fff; color:var(--pd); border-radius:999px; padding:6px 14px; font-weight:600; font-size:.86rem; text-decoration:none; }
    .sar .tab.on { background:var(--p); border-color:var(--p); color:#fff; }
    .sar .tab .n { background:rgba(0,0,0,.06); border-radius:12px; padding:0 7px; font-size:.76rem; margin-left:4px; } .sar .tab.on .n { background:rgba(255,255,255,.22); }
    .sar table { font-size:13px; } .sar th { background:var(--p) !important; color:#fff !important; white-space:nowrap; }
    .sar tr.sup td { background:var(--soft); font-weight:700; border-top:2px solid var(--line); }
    .sar .mono { font-family:ui-monospace,Menlo,Consolas,monospace; }
    .sar .mut { color:var(--mut); font-weight:400; }
    .sar .pill { display:inline-block; border-radius:999px; padding:2px 9px; font-size:11px; font-weight:700; white-space:nowrap; }
    .sar .pill.verified { background:#e4f4ea; color:#1f8a4c; } .sar .pill.unverified { background:#ffffff; border:1px solid #d9d3dd; color:#1a1a1a; } .sar .pill.changed { background:#fbe9e7; color:#b23a32; }
    .sar .pill.inactive { background:#eef0f3; color:#6c757d; } .sar .pill.default { background:var(--soft); color:var(--p); } .sar .pill.incomplete { background:#fde8e8; color:#9b1c1c; } .sar .pill.missing { background:#fde8e8; color:#9b1c1c; }
    .sar .act .btn { padding:2px 8px; font-size:12px; }
    .sar .hist td { font-size:12px; }
    #sarModal .form-label { font-size:.82rem; font-weight:600; margin-bottom:2px; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content"><div class="container-fluid sar">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <div><h4 class="mb-0"><i class="bi bi-bank2 me-2"></i>Supplier Bank Account Registry</h4>
                <div class="small mut">Every payee's registered bank accounts, linked to the supplier master by its id. The supplier's display name and the exact account holder's name are kept separately. Payments copy the selected account when approved; a later change here flags those payments for renewed review and never alters them.</div></div>
            <form class="d-flex gap-2" method="get"><input type="hidden" name="f" value="<?= $e($f) ?>"><input class="form-control form-control-sm" style="min-width:260px" name="q" value="<?= $e($q) ?>" placeholder="Supplier name or id, vehicle number, account holder, account number"><button class="btn btn-sm btn-p">Search</button><?php if ($q !== ''): ?><a class="btn btn-sm btn-o" href="?f=<?= $e($f) ?>">Clear</a><?php endif; ?></form>
        </div>
        <?php foreach ($notes as $n): ?><div class="alert alert-info py-2 small"><?= $e($n) ?></div><?php endforeach; ?>
        <?php if (!$installed): ?><div class="alert alert-warning">The registry is not set up yet. The System Administrator opens this page once to create it.</div><?php else: ?>
        <?php
        foreach ($rows as $s) {
            if (!$s['accounts']) { $counts['missing']++; }
            elseif (!array_filter($s['accounts'], static fn ($a) => $a['complete'] && (int) $a['active'] === 1)) { $counts['incomplete']++; }
            if (array_filter($s['accounts'], static fn ($a) => in_array($a['state'], ['unverified', 'changed'], true))) { $counts['unverified']++; }
        }
        ?>
        <div class="tabs">
            <?php foreach (['' => 'All suppliers', 'registered' => 'With accounts', 'missing' => 'No account', 'incomplete' => 'Incomplete', 'unverified' => 'Not verified'] as $k => $lbl): ?>
                <a class="tab <?= $f === $k ? 'on' : '' ?>" href="?f=<?= $k ?>&q=<?= urlencode($q) ?>"><?= $lbl ?><?php if ($k === '' || $f === '' || $f === $k): ?><span class="n"><?= $k === '' ? $counts['all'] : ($f === $k ? count($rows) : ($counts[$k] ?? '')) ?></span><?php endif; ?></a>
            <?php endforeach; ?>
            <?php if ($can_edit): ?><button type="button" class="btn btn-sm btn-p ms-auto" onclick="sarEdit(null, '', '')"><i class="bi bi-plus-lg me-1"></i>Add account</button><?php endif; ?>
        </div>
        <div class="card mb-3"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Supplier</th><th>Supplier ID</th><th>Account holder (exact bank name)</th><th>Bank</th><th>Branch</th><th>Account number</th><th>Currency</th><th>Status</th><th>Verified</th><th>Changed</th><th class="act"></th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="11" class="text-center mut py-4">No supplier matches.</td></tr><?php endif; ?>
                <?php foreach ($rows as $s): ?>
                    <tr class="sup"><td colspan="2"><?= $e($s['name']) ?> <span class="mut">· <?= $e($s['category']) ?></span><?= $s['vehicles'] !== '' ? ' <span class="pill default" title="Registered vehicle number"><i class="bi bi-truck me-1"></i>' . $e($s['vehicles']) . '</span>' : '' ?></td>
                        <td colspan="8" class="mut fw-normal small"><?= $s['accounts'] ? count($s['accounts']) . ' account' . (count($s['accounts']) === 1 ? '' : 's') : '<span class="pill missing">No registered account</span> — payments to this supplier cannot be initiated until one is registered' ?></td>
                        <td class="act text-end"><?php if ($can_edit): ?><button type="button" class="btn btn-o" onclick="sarEdit(null, <?= $e(json_encode($s['sup_acc'])) ?>, <?= $e(json_encode($s['name'])) ?>)"><i class="bi bi-plus"></i> Account</button><?php endif; ?></td></tr>
                    <?php foreach ($s['accounts'] as $a): ?>
                    <tr>
                        <td class="small mut"><?= $a['label'] ? $e($a['label']) : '' ?></td>
                        <td class="mono small"><?= $e($s['sup_acc']) ?></td>
                        <td><b><?= $e($a['holder_name'] ?: '—') ?></b><?php if (strcasecmp(trim((string) $a['holder_name']), trim((string) $s['name'])) !== 0 && $a['holder_name']): ?><br><span class="mut small">differs from the supplier's name</span><?php endif; ?></td>
                        <td><?= $e($a['bank_name'] ?: '—') ?><?= $a['swift'] ? '<br><span class="mut small">' . $e($a['swift']) . '</span>' : '' ?></td>
                        <td><?= $e($a['branch'] ?: '—') ?></td>
                        <td class="mono"><?= $showNo($a['account_no']) ?></td>
                        <td><?= $e($a['currency']) ?></td>
                        <td><span class="pill <?= $e($a['state']) ?>"><?= $a['state'] === 'changed' ? 'CHANGED' : ucfirst($a['state']) ?></span>
                            <?php if ((int) $a['is_default'] === 1): ?> <span class="pill default">Default</span><?php endif; ?>
                            <?php if (!$a['complete']): ?> <span class="pill incomplete" title="<?= $e(implode(', ', $a['missing'])) ?> missing">Incomplete</span><?php endif; ?></td>
                        <td class="small"><?= $a['verified_by'] ? $e($a['verified_by']) . '<br><span class="mut">' . $e(substr((string) $a['verified_at'], 0, 16)) . '</span>' : '<span class="mut">—</span>' ?></td>
                        <td class="small"><?= $e($a['updated_by']) ?><br><span class="mut"><?= $e(substr((string) $a['updated_at'], 0, 16)) ?></span></td>
                        <td class="act text-end text-nowrap">
                            <?php if ($can_edit): ?>
                                <button type="button" class="btn btn-o" onclick='sarEdit(<?= json_encode(array_intersect_key($a, array_flip(['id', 'sup_acc', 'holder_name', 'bank_name', 'branch', 'account_no', 'currency', 'swift', 'label', 'note', 'is_default'])) + ['account_no' => $full_numbers ? $a['account_no'] : ''], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= $e(json_encode($s['sup_acc'])) ?>, <?= $e(json_encode($s['name'])) ?>)' title="Edit"><i class="bi bi-pencil"></i></button>
                                <?php if ((int) $a['active'] === 1 && (int) $a['is_default'] !== 1): ?><button type="button" class="btn btn-o" onclick="sarPost('set_default', {id: <?= (int) $a['id'] ?>})" title="Make default">Default</button><?php endif; ?>
                                <?php if ((int) $a['active'] === 1): ?><button type="button" class="btn btn-o" onclick="sarActive(<?= (int) $a['id'] ?>, 0)" title="Deactivate"><i class="bi bi-pause-circle"></i></button><?php else: ?><button type="button" class="btn btn-o" onclick="sarActive(<?= (int) $a['id'] ?>, 1)" title="Activate"><i class="bi bi-play-circle"></i></button><?php endif; ?>
                            <?php endif; ?>
                            <?php if ($can_verify && $a['complete'] && (int) $a['active'] === 1 && $a['state'] !== 'verified'): ?><?php if (strcasecmp(trim((string) (($a['updated_by'] ?? '') ?: ($a['created_by'] ?? ''))), trim(\App\Libraries\TourPayments\Perms::user())) === 0): ?><span class="help" title="tf-stage5: the person who entered or last changed the details cannot verify them; a Finance & Approvals Management Approver verifies against the bank's document" style="white-space:nowrap;font-size:11px">entered by you</span><?php else: ?><button type="button" class="btn btn-p" onclick="sarVerify(<?= (int) $a['id'] ?>)"><i class="bi bi-patch-check me-1"></i>Verify</button><?php endif; ?><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div></div></div>

        <div class="card hist"><div class="card-body">
            <h6 class="mb-2" style="color:var(--pd);">Change log<?= $q !== '' ? ' — ' . $e($q) : ' — latest 60 registry changes' ?> <span class="mut small">(who changed what, and when; account numbers shown by their last four digits)</span></h6>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>When</th><th>Who</th><th>Action</th><th>Account</th><th>Before</th><th>After</th><th>Reason</th></tr></thead>
                <tbody>
                <?php if (!$history): ?><tr><td colspan="7" class="mut">No changes recorded yet.</td></tr><?php endif; ?>
                <?php foreach ($history as $h): ?>
                    <tr><td class="text-nowrap"><?= $e(substr((string) $h['at'], 0, 19)) ?></td><td><?= $e($h['user']) ?></td><td><?= $e(str_replace('_', ' ', (string) $h['action'])) ?></td><td class="mono"><?= $e($h['ref_type']) ?> <?= $e($h['ref_id']) ?></td>
                        <td class="mut" style="max-width:260px;word-break:break-all;"><?= $e($h['prev_value']) ?></td><td style="max-width:260px;word-break:break-all;"><?= $e($h['new_value']) ?></td><td><?= $e($h['reason']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div></div>
        <?php endif; ?>
    </div></div></div>
</div>

<div class="modal fade" id="sarModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header" style="background:#842484;color:#fff;"><h5 class="modal-title" id="sarTitle">Bank account</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form id="sarForm" onsubmit="return sarSave(event)">
    <div class="modal-body">
        <input type="hidden" name="id" id="sar_id">
        <div class="mb-2"><label class="form-label">Supplier (from the supplier master; the link is the id)</label>
            <div class="input-group input-group-sm"><input class="form-control mono" name="sup_acc" id="sar_sup" placeholder="Supplier id, e.g. 6-9-11-10626" required><span class="input-group-text" id="sar_supname">—</span></div>
            <div class="form-text">Type the supplier id as in the supplier master; the name shows for confirmation. Names are never matched.</div></div>
        <div class="mb-2"><label class="form-label">Account holder's name (exactly as on the bank account)</label><input class="form-control form-control-sm" name="holder_name" id="sar_holder" maxlength="200" required></div>
        <div class="row g-2 mb-2"><div class="col-7"><label class="form-label">Bank</label><input class="form-control form-control-sm" name="bank_name" id="sar_bank" maxlength="160" required></div><div class="col-5"><label class="form-label">Branch</label><input class="form-control form-control-sm" name="branch" id="sar_branch" maxlength="160"></div></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Account number</label><input class="form-control form-control-sm mono" name="account_no" id="sar_no" maxlength="60" required></div><div class="col-3"><label class="form-label">Currency</label><input class="form-control form-control-sm" name="currency" id="sar_cur" value="LKR" maxlength="3" pattern="[A-Za-z]{3}" required></div><div class="col-3"><label class="form-label">SWIFT</label><input class="form-control form-control-sm" name="swift" id="sar_swift" maxlength="20"></div></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Label (optional)</label><input class="form-control form-control-sm" name="label" id="sar_label" maxlength="60" placeholder="e.g. USD account, Colombo branch"></div><div class="col-6"><label class="form-label">Note</label><input class="form-control form-control-sm" name="note" id="sar_note" maxlength="300"></div></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="make_default" value="1" id="sar_default"><label class="form-check-label small" for="sar_default">Make this the supplier's default account</label></div>
        <div class="small mut mt-2">Saving a change to a verified account's holder, bank, branch, number, currency or SWIFT removes its verification; payments that selected it are flagged for renewed review.</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-sm btn-o" data-bs-dismiss="modal">Cancel</button><button class="btn btn-sm btn-p" type="submit">Save</button></div>
    </form>
</div></div></div>

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
const SAR_BASE = <?= json_encode(base_url('master_files/supplier_accounts/'), JSON_UNESCAPED_SLASHES) ?>;
const SAR_CSRF = { '<?= csrf_token() ?>': '<?= csrf_hash() ?>' };
function sarPost(action, data, cb) {
    $.post(SAR_BASE + action, Object.assign({}, data, SAR_CSRF), function (j) {
        if (window.Swal) { Swal.fire({ icon: j.status === 200 ? 'success' : 'error', title: j.status === 200 ? 'Saved' : 'Not saved', text: j.message }).then(function () { if (j.status === 200) { cb ? cb(j) : location.reload(); } }); }
        else { alert(j.message); if (j.status === 200) { cb ? cb(j) : location.reload(); } }
    }, 'json').fail(function (x) { var m = (x.responseJSON && x.responseJSON.message) || 'The request failed.'; window.Swal ? Swal.fire({ icon: 'error', title: 'Not saved', text: m }) : alert(m); });
}
function sarEdit(a, sup, name) {
    a = a || {};
    $('#sarTitle').text(a.id ? 'Edit bank account' : 'Add bank account');
    $('#sar_id').val(a.id || ''); $('#sar_sup').val(a.sup_acc || sup || '').prop('readonly', !!(a.id || sup)); $('#sar_supname').text(name || '—');
    $('#sar_holder').val(a.holder_name || ''); $('#sar_bank').val(a.bank_name || ''); $('#sar_branch').val(a.branch || ''); $('#sar_no').val(a.account_no || '');
    $('#sar_cur').val(a.currency || 'LKR'); $('#sar_swift').val(a.swift || ''); $('#sar_label').val(a.label || ''); $('#sar_note').val(a.note || ''); $('#sar_default').prop('checked', false);
    <?php if (!$full_numbers): ?>if (a.id) { $('#sar_no').attr('placeholder', 'enter the number again to change it').prop('required', false); }<?php endif; ?>
    new bootstrap.Modal(document.getElementById('sarModal')).show();
}
$('#sar_sup').on('change', function () {
    const v = $(this).val().trim(); if (!v) { return; }
    $.getJSON('<?= base_url('master_files/supplier_bank/get') ?>', { acc: v }, function (j) { $('#sar_supname').text(j.data && j.data.supplier_name && j.data.supplier_name !== v ? j.data.supplier_name : 'not found'); }).fail(function () { $('#sar_supname').text('?'); });
});
function sarSave(e) {
    e.preventDefault();
    const d = {}; new FormData(document.getElementById('sarForm')).forEach(function (v, k) { d[k] = v; });
    sarPost('save', d);
    return false;
}
function sarActive(id, on) {
    const reason = on ? '' : window.prompt('Reason for deactivating this account (required):');
    if (!on && (reason === null || !reason.trim())) { return; }
    sarPost('set_active', { id: id, active: on, reason: reason || '' });
}
function sarVerify(id) {
    const note = window.prompt('Verify this account: note how it was confirmed (bank letter, supplier\'s written confirmation, …):');
    if (note === null) { return; }
    sarPost('verify', { id: id, note: note });
}
</script>
</body>
</html>
