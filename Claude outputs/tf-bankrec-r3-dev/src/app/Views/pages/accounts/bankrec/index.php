<?= view('layouts/head') ?>
<?php /** tf-bankrec-r1/r2 — Daily Bank Reconciliation: the accounts (accounts/daily_bank_rec). */ ?>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content"><div class="container-fluid brc">
        <?= view('pages/accounts/bankrec/_common', ['brc' => $brc]) ?>
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-1" style="color:var(--p); font-weight:700;">Daily Bank Reconciliation</h4>
                <div class="small muted" style="max-width:900px;">Each Avenir bank account is compared, day by day, with its ledger account. Bank and book balances are kept separate. Nothing here is posted to the ledger: differences are listed and explained, and any correction is proposed for an authorised reviewer and posted by hand by Accounts.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-op btn-sm" href="<?= base_url('accounts/daily_bank_rec/fx') ?>"><i class="ri-exchange-dollar-line me-1"></i>Rates &amp; transfers</a>
                <?php if (!empty($brc['admin'])): ?><a class="btn btn-light btn-sm" href="<?= base_url('master_files/bank_accounts') ?>"><i class="ri-bank-line me-1"></i>Avenir Bank Accounts master</a><?php endif; ?>
            </div>
        </div>

        <?php if (empty($brc_status['ready'])): ?>
        <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><b>Not set up yet.</b> The reconciliation's tables and privileges are not in place on this database<?= empty($brc_status['tpd_ready']) ? ' (the payment workflow must be set up first)' : '' ?>.</div>
            <?php if (!empty($brc['admin']) && !empty($brc_status['tpd_ready'])): ?><button class="btn btn-p btn-sm" onclick="setup()">Set up</button><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card mb-3"><div class="card-body py-2">
            <div class="row g-2">
                <div class="col-md-4 col-lg-2 step"><b>1</b><div>Set up the account in the master; <strong>propose</strong> its ledger mapping and first day; another user <strong>confirms</strong> it.</div></div>
                <div class="col-md-4 col-lg-2 step"><b>2</b><div>Enter the <strong>daily rate</strong> for USD / JPY accounts (LKR per unit).</div></div>
                <div class="col-md-4 col-lg-2 step"><b>3</b><div><strong>Upload or type</strong> the statement, check it, keep it as a draft, <strong>accept</strong> it. Never posted.</div></div>
                <div class="col-md-4 col-lg-2 step"><b>4</b><div><strong>Match</strong> lines with ledger rows; classify the rest: timing, duplicate-looking, excluded, bank charge.</div></div>
                <div class="col-md-4 col-lg-2 step"><b>5</b><div><strong>Propose</strong> corrections and opening items for review. Never posted automatically.</div></div>
                <div class="col-md-4 col-lg-2 step"><b>6</b><div><strong>Prepare</strong> the daily record; another user <strong>reviews</strong> it. A later change flags it for <strong>re-review</strong>.</div></div>
            </div>
        </div></div>

        <div class="card mb-3">
            <div class="card-h"><span>Avenir bank accounts</span><span class="small2 muted">Account numbers are shown masked here.</span></div>
            <div class="table-responsive"><table class="t" id="acctTable"><thead><tr>
                <th>Account</th><th>Currency</th><th>Ledger account</th><th>Mapping</th><th>First day</th><th>Statements to</th><th>Status (latest day)</th><th class="num">Bank − book (LKR)</th><th></th>
            </tr></thead><tbody><tr><td colspan="9" class="muted p-3">Loading…</td></tr></tbody></table></div>
        </div>

        <div class="card mb-3" id="reviewCard" style="display:none;">
            <div class="card-h"><span>Waiting for review</span><span class="small2 muted">Mappings, opening items, corrections and daily records proposed by others.</span></div>
            <div class="card-body p-0"><div id="reviewList" class="p-2"></div></div>
        </div>
    </div></div></div>
</div>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
const B = window.BRC;
async function setup() {
    const j = await B.post('setup', {});
    B.done(j, () => location.reload());
}
function mapCell(a) {
    const c = a.map_confirmed, p = a.map_pending;
    let h = B.pill(a.map_state, { none: 'Not proposed', proposed: 'Proposed', confirmed: 'Confirmed', changed: 'Changed in master — confirm again' }[a.map_state]);
    if (c && a.map_state === 'confirmed') h += '<div class="small2 muted">by ' + B.esc(c.decided_by) + ', ' + B.esc((c.decided_at || '').slice(0, 16)) + '</div>';
    if (p) h += '<div class="small2 muted">proposed by ' + B.esc(p.created_by) + ' · first day ' + B.esc(p.rec_start) + '</div>';
    return h;
}
async function load() {
    const j = await B.get('api/accounts');
    const tb = document.querySelector('#acctTable tbody');
    if (j.status !== 200) { tb.innerHTML = '<tr><td colspan="9" class="p-3 muted">' + B.esc(j.message) + '</td></tr>'; return; }
    if (!j.accounts.length) { tb.innerHTML = '<tr><td colspan="9" class="p-3 muted">No Avenir bank account yet. They are set up in the Avenir Bank Accounts master (account, currency, ledger account).</td></tr>'; return; }
    const A = B.access;
    tb.innerHTML = j.accounts.map(a => {
        const d = a.day;
        const first = a.map_confirmed ? a.map_confirmed.rec_start : (a.map_pending ? a.map_pending.rec_start : '');
        let act = '';
        if (a.map_state === 'confirmed' || a.map_state === 'changed') act += '<a class="btn btn-sm btn-p me-1 mb-1" href="' + B.base + '/account/' + a.id + '">Open</a>';
        if (A.prepare && a.supported_currency && a.ledger_code && a.map_state !== 'confirmed') act += '<button class="btn btn-sm btn-op me-1 mb-1" onclick="propose(' + a.id + ')">Propose mapping</button>';
        if (A.prepare && a.map_state === 'confirmed') act += '<button class="btn btn-sm btn-light me-1 mb-1" onclick="propose(' + a.id + ')" title="Propose a new first day or re-confirm">Re-propose</button>';
        if (A.review && a.map_pending && a.map_pending.created_by !== A.user) act += '<button class="btn btn-sm btn-p me-1 mb-1" onclick="decide(' + a.map_pending.id + ', \'confirm\')">Confirm</button><button class="btn btn-sm btn-light mb-1" onclick="decide(' + a.map_pending.id + ', \'reject\')">Reject</button>';
        return '<tr><td><b>' + B.esc(a.label) + '</b><div class="small2 muted">' + B.esc(a.bank_name) + (a.branch ? ', ' + B.esc(a.branch) : '') + ' · <span class="mono">' + B.esc(a.account_no_masked) + '</span>' + (a.active ? '' : ' · inactive') + '</div></td>'
            + '<td>' + B.esc(a.currency) + (a.supported_currency ? '' : '<div class="small2 err">not supported</div>') + '</td>'
            + '<td class="mono small2">' + B.esc(a.ledger_code || '—') + '<div class="muted">' + B.esc(a.ledger_name || '') + '</div></td>'
            + '<td>' + mapCell(a) + '</td><td class="mono small2">' + B.esc(first || '—') + '</td><td class="mono small2">' + B.esc(a.last_statement_to || '—') + (a.drafts ? '<div class="small2 warn">' + a.drafts + ' draft(s)</div>' : '') + '</td>'
            + '<td>' + (d ? B.pill(d.status_code, d.status_label) + '<div class="small2 muted">' + B.esc(d.date) + '</div>' + (d.pending && d.pending.not_corrected ? '<div class="small2 warn"><b>Ledger not corrected:</b> ' + B.esc(d.pending.summary) + '</div>' : '') + (a.rereview && a.rereview.length ? '<div>' + B.pill('reviewed_outdated', 'Re-review needed: ' + a.rereview.map(x => x.date).join(', ')) + '</div>' : '') + (d.legacy_flags ? '<div>' + B.pill('reviewed_outdated', 'Old-screen flags in the period: ' + d.legacy_flags) + '</div>' : '') + (d.reasons || []).slice(0, 2).map(r => '<div class="small2 muted">· ' + B.esc(r) + '</div>').join('') : '<span class="muted small2">—</span>') + '</td>'
            + '<td class="num">' + (d ? B.money(d.difference_lkr) : '<span class="muted">—</span>') + '</td><td>' + act + '</td></tr>';
    }).join('');
    if (A.review) loadReview(j.accounts);
}
async function loadReview(accounts) {
    const items = [];
    for (const a of accounts) if (a.map_pending && a.map_pending.created_by !== B.access.user) items.push('<div class="p-2 border-bottom">' + B.pill('proposed', 'Mapping') + ' <b>' + B.esc(a.label) + '</b> → ' + B.esc(a.map_pending.ledger_code) + ', first day ' + B.esc(a.map_pending.rec_start) + ' <span class="muted small2">(by ' + B.esc(a.map_pending.created_by) + ')</span></div>');
    const c = await B.get('api/corrections', { status: 'proposed' });
    for (const x of (c.corrections || [])) if (x.created_by !== B.access.user) items.push('<div class="p-2 border-bottom">' + B.pill('proposed', 'Correction') + ' <b>' + B.esc(x.account_label) + '</b>: ' + B.esc(x.description) + ' — ' + B.money(x.amount, 'LKR') + ' <span class="muted small2">(by ' + B.esc(x.created_by) + ')</span> <a href="' + B.base + '/account/' + x.bank_account_id + '#corrections">Open</a></div>');
    for (const a of accounts) {
        if (a.map_state !== 'confirmed') continue;
        const d = await B.get('api/days', { account: a.id });
        for (const r of (d.days || [])) {
            if (r.review_status === 'prepared' && r.prepared_by !== B.access.user && (r.live || {}).state === 'prepared') items.push('<div class="p-2 border-bottom">' + B.pill('prepared', 'Daily record') + ' <b>' + B.esc(a.label) + '</b> ' + B.esc(r.rec_date) + ': ' + B.esc(r.status_label) + ' <span class="muted small2">(by ' + B.esc(r.prepared_by) + ')</span> <a href="' + B.base + '/account/' + a.id + '#days">Open</a></div>');
            if ((r.live || {}).state === 'reviewed_outdated') items.push('<div class="p-2 border-bottom">' + B.pill('reviewed_outdated', 'Re-review needed') + ' <b>' + B.esc(a.label) + '</b> ' + B.esc(r.rec_date) + ': record #' + r.id + ' changed since review — ' + B.esc((r.live.changed || []).join(', ')) + ' <a href="' + B.base + '/account/' + a.id + '#days">Open</a></div>');
        }
    }
    document.getElementById('reviewCard').style.display = '';
    document.getElementById('reviewList').innerHTML = items.length ? items.join('') : '<div class="p-2 muted small">Nothing is waiting for your review.</div>';
}
async function propose(id) {
    const v = await B.ask('Propose the ledger mapping', [
        { name: 'rec_start', label: 'First day to reconcile', type: 'date', required: true, help: 'The bank opening balance of this day is compared with the book balance at the end of the day before.' },
        { name: 'note', label: 'Note (optional)', type: 'text' }], 'Propose');
    if (!v) return;
    B.done(await B.post('mapping/propose', { account: id, rec_start: v.rec_start, note: v.note }), load);
}
async function decide(mapId, decision) {
    const v = await B.ask(decision === 'confirm' ? 'Confirm this mapping' : 'Reject this mapping', [{ name: 'note', label: decision === 'confirm' ? 'Note (optional)' : 'Reason', type: 'text', min: decision === 'confirm' ? 0 : 5 }], decision === 'confirm' ? 'Confirm' : 'Reject');
    if (!v) return;
    B.done(await B.post('mapping/decide', { map_id: mapId, decision, note: v.note }), load);
}
<?php if (!empty($brc_status['ready'])): ?>load();<?php endif; ?>
</script>
</body>
