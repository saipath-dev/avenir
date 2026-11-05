<?= view('layouts/head') ?>
<?php /** tf-bankrec-r1 — one account's reconciliation workbench (accounts/daily_bank_rec/account/{id}). */ ?>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content"><div class="container-fluid brc">
        <?= view('pages/accounts/bankrec/_common', ['brc' => $brc]) ?>
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
            <div>
                <div class="small2"><a href="<?= base_url('accounts/daily_bank_rec') ?>">Daily Bank Reconciliation</a> /</div>
                <h4 class="mb-0" style="color:var(--p); font-weight:700;"><?= esc($acct['label']) ?></h4>
                <div class="small muted"><?= esc($acct['bank_name']) ?><?= $acct['branch'] ? ', ' . esc($acct['branch']) : '' ?> · <span class="mono"><?= esc($acct['account_no_masked']) ?></span> · <?= esc($acct['currency']) ?> · ledger <span class="mono"><?= esc($acct['ledger_code']) ?></span> <?= esc($acct['ledger_name'] ?? '') ?></div>
            </div>
            <div class="d-flex align-items-end gap-2 flex-wrap">
                <div><label class="form-label small2 mb-0 muted">As at (end of day)</label><input type="date" class="form-control form-control-sm" id="asAt"></div>
                <button class="btn btn-sm btn-op" id="btnRefresh" onclick="refreshAll()"><i class="ri-refresh-line"></i> Refresh</button>
                <a class="btn btn-sm btn-light" href="<?= base_url('accounts/daily_bank_rec/fx') ?>">Rates &amp; transfers</a>
            </div>
        </div>
        <?php if ($acct['map_state'] !== 'confirmed'): ?><div class="alert alert-warning py-2">The ledger mapping of this account is not confirmed (<?= esc($acct['map_state']) ?>). Statements can be viewed, but reconciliation needs a confirmed mapping.</div><?php endif; ?>

        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#summary">Summary</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#statements">Statements</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#matching">Matching</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#charges">Bank charges</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#corrections">Corrections &amp; opening</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#days">Daily records</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#audit">Audit trail</a></li>
        </ul>
        <div class="tab-content">
            <!-- ======================= SUMMARY -->
            <div class="tab-pane fade show active" id="summary">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2"><span id="statusPill"></span><span class="small muted" id="statusDate"></span></div>
                <ul class="small mb-3" id="reasons"></ul>
                <div class="row g-3">
                    <div class="col-lg-4"><div class="card h-100"><div class="card-h"><span><i class="ri-bank-line"></i> Bank (accepted statements)</span><span class="small2 muted" id="bankCur"></span></div><div class="card-body"><div class="kv" id="bankKv"></div></div></div></div>
                    <div class="col-lg-4"><div class="card h-100"><div class="card-h"><span><i class="ri-book-2-line"></i> Book (ledger, read only)</span><span class="small2 muted">LKR</span></div><div class="card-body"><div class="kv" id="bookKv"></div></div></div></div>
                    <div class="col-lg-4"><div class="card h-100"><div class="card-h"><span>Items</span></div><div class="card-body"><div class="kv" id="countKv"></div></div></div></div>
                </div>
                <div class="card mt-3"><div class="card-h"><span>Reconciliation of bank and book (LKR)</span><span class="small2 muted">positive: the bank is higher than the book</span></div><div class="card-body"><div class="kv" id="recKv" style="max-width:720px;"></div></div></div>
                <div class="card mt-3"><div class="card-h"><span>Daily record</span></div><div class="card-body">
                    <div class="small mb-2" id="lastRecord"></div>
                    <?php if (!empty($brc['prepare'])): ?><button class="btn btn-p btn-sm" onclick="prepareDay()"><i class="ri-file-list-3-line me-1"></i>Prepare the daily record for this date</button><?php endif; ?>
                    <div class="small2 muted mt-2">The record keeps the reconciliation as it stands, with who prepared it and when. Another user with the review privilege reviews it.</div>
                </div></div>
            </div>

            <!-- ======================= STATEMENTS -->
            <div class="tab-pane fade" id="statements">
                <div class="card mb-3"><div class="card-h"><span>Statements of this account</span>
                    <?php if (!empty($brc['prepare'])): ?><div class="d-flex gap-2"><button class="btn btn-sm btn-p" id="btnUpload" onclick="showImport('upload')"><i class="ri-upload-2-line me-1"></i>Upload CSV</button><button class="btn btn-sm btn-op" id="btnManual" onclick="showImport('manual')"><i class="ri-edit-line me-1"></i>Type lines</button></div><?php endif; ?>
                </div><div class="table-responsive"><table class="t" id="stmtTable"><thead><tr><th>#</th><th>Period</th><th class="num">Opening</th><th class="num">Closing</th><th class="num">Lines</th><th>Source</th><th>Checks</th><th>Status</th><th>Entered / accepted</th><th></th></tr></thead><tbody></tbody></table></div></div>

                <div class="card mb-3" id="importCard" style="display:none;"><div class="card-h"><span id="importTitle">Import a statement</span><button class="btn btn-sm btn-light" onclick="document.getElementById('importCard').style.display='none'">Close</button></div><div class="card-body">
                    <form id="importForm" onsubmit="return false;">
                        <input type="hidden" name="source" id="impSource" value="upload">
                        <div class="row g-2">
                            <div class="col-md-4" id="fileBox"><label class="form-label small fw-semibold">Statement file (CSV)</label><input class="form-control form-control-sm" type="file" name="file" id="impFile" accept=".csv,.txt"><div class="small2 muted">From Excel: File → Save As → CSV. The file is read, not stored.</div></div>
                            <div class="col-md-2"><label class="form-label small fw-semibold">Opening balance</label><input class="form-control form-control-sm" name="opening_balance" placeholder="e.g. 2,749,797.37 CR"><div class="small2 muted">as printed; CR in credit, DR overdrawn</div></div>
                            <div class="col-md-2"><label class="form-label small fw-semibold">Closing balance</label><input class="form-control form-control-sm" name="closing_balance" placeholder="optional, checked"></div>
                            <div class="col-md-2"><label class="form-label small fw-semibold">Period from</label><input class="form-control form-control-sm" type="date" name="period_from"></div>
                            <div class="col-md-2"><label class="form-label small fw-semibold">Period to</label><input class="form-control form-control-sm" type="date" name="period_to"></div>
                            <div class="col-md-3 uploadOnly"><label class="form-label small fw-semibold">Date format</label><select class="form-select form-select-sm" name="date_format"><option value="">Recognise automatically (day before month)</option><option value="d/m/Y">dd/mm/yyyy</option><option value="d-m-Y">dd-mm-yyyy</option><option value="Y-m-d">yyyy-mm-dd</option><option value="d-M-Y">dd-Mon-yyyy</option><option value="d.m.Y">dd.mm.yyyy</option></select></div>
                            <div class="col-md-3 uploadOnly"><label class="form-label small fw-semibold">One amount column?</label><select class="form-select form-select-sm" name="amount_sign"><option value="in_positive">positive = money in</option><option value="out_positive">positive = money out</option></select></div>
                            <div class="col-md-6"><label class="form-label small fw-semibold">Note</label><input class="form-control form-control-sm" name="note" maxlength="300"></div>
                        </div>
                        <div id="mapBox" class="mt-2 uploadOnly"></div>
                        <div id="manualBox" class="mt-2" style="display:none;">
                            <table class="t" id="manualTable"><thead><tr><th style="width:140px">Date</th><th>Description</th><th style="width:140px">Reference</th><th style="width:130px" class="num">Money in</th><th style="width:130px" class="num">Money out</th><th style="width:150px" class="num">Balance (optional)</th></tr></thead><tbody></tbody></table>
                            <button class="btn btn-sm btn-light mt-1" onclick="addManualRow()">+ line</button>
                        </div>
                        <div class="d-flex gap-2 mt-3"><button class="btn btn-sm btn-op" id="btnCheck" onclick="previewStatement()"><i class="ri-search-eye-line me-1"></i>Check</button><button class="btn btn-sm btn-p" id="btnKeep" onclick="saveStatement()"><i class="ri-save-line me-1"></i>Keep as draft</button><span class="small2 muted align-self-center">Checking keeps nothing. Keeping it stores the lines as a draft; nothing is posted to the ledger.</span></div>
                    </form>
                    <div id="previewBox" class="mt-3"></div>
                </div></div>

                <div class="card mb-3" id="stmtView" style="display:none;"><div class="card-h"><span id="stmtViewTitle"></span><div class="d-flex gap-2" id="stmtViewActions"></div></div><div class="card-body p-0"><div id="stmtChecks" class="p-2"></div><div class="table-responsive scroll"><table class="t" id="stmtLines"><thead><tr><th>#</th><th>Date</th><th>Description</th><th>Reference</th><th class="num">In</th><th class="num">Out</th><th class="num">Balance</th><th class="num">LKR</th><th>Category</th><th>State</th><th></th></tr></thead><tbody></tbody></table></div></div></div>
            </div>

            <!-- ======================= MATCHING -->
            <div class="tab-pane fade" id="matching">
                <div class="d-flex flex-wrap gap-2 align-items-end mb-2">
                    <div><label class="form-label small2 mb-0 muted">From</label><input type="date" class="form-control form-control-sm" id="mFrom"></div>
                    <div><label class="form-label small2 mb-0 muted">To</label><input type="date" class="form-control form-control-sm" id="mTo"></div>
                    <label class="form-check small mb-1"><input class="form-check-input" type="checkbox" id="mBefore"> <span>also ledger rows up to 30 days before the first day</span></label>
                    <button class="btn btn-sm btn-op" onclick="loadOpen()">Show</button>
                    <?php if (!empty($brc['prepare'])): ?><button class="btn btn-sm btn-p" id="btnSuggest" onclick="suggest()"><i class="ri-magic-line me-1"></i>Suggest matches</button><?php endif; ?>
                </div>
                <div id="suggestBox"></div>
                <div class="row g-3">
                    <div class="col-xl-6"><div class="card"><div class="card-h"><span>Statement lines not yet matched</span><span class="small2 muted" id="bankOpenCount"></span></div><div class="scroll"><table class="t" id="openBank"><thead><tr><th></th><th>Date</th><th>Description</th><th class="num">Amount</th><th class="num">LKR</th><th>Category</th></tr></thead><tbody></tbody></table></div></div></div>
                    <div class="col-xl-6"><div class="card"><div class="card-h"><span>Ledger rows not yet matched</span><span class="small2 muted" id="bookOpenCount"></span></div><div class="scroll"><table class="t" id="openBook"><thead><tr><th></th><th>Date</th><th>Description / payee</th><th>PV</th><th class="num">Dr − Cr</th></tr></thead><tbody></tbody></table></div></div></div>
                </div>
                <?php if (!empty($brc['prepare'])): ?>
                <div class="bar mt-2"><span class="small">Selected statement: <b id="selBank">0.00</b> LKR</span><span class="small">Selected ledger: <b id="selBook">0.00</b> LKR</span><span class="small">Difference: <b id="selDiff">0.00</b></span>
                    <button class="btn btn-sm btn-p" onclick="matchSelected()">Match selected</button><button class="btn btn-sm btn-op" onclick="classifySelected()">Classify one item…</button><button class="btn btn-sm btn-light" onclick="clearSel()">Clear selection</button></div>
                <?php endif; ?>
                <div class="row g-3 mt-1">
                    <div class="col-xl-7"><div class="card"><div class="card-h"><span>Matched</span><span class="small2 muted" id="matchedCount"></span></div><div class="scroll"><table class="t" id="matchedTable"><thead><tr><th>#</th><th>Statement</th><th>Ledger</th><th class="num">Difference</th><th>By</th><th></th></tr></thead><tbody></tbody></table></div></div></div>
                    <div class="col-xl-5"><div class="card"><div class="card-h"><span>Classified (not matched)</span></div><div class="scroll"><table class="t" id="marksTable"><thead><tr><th>Item</th><th>As</th><th>Note</th><th></th></tr></thead><tbody></tbody></table></div></div></div>
                </div>
            </div>

            <!-- ======================= CHARGES -->
            <div class="tab-pane fade" id="charges">
                <div class="card"><div class="card-h"><span>Bank charges on the statements (first day to the as-at date)</span><?php if (!empty($brc['prepare'])): ?><button class="btn btn-sm btn-p" onclick="proposeCharges()">Propose a correction for the selected charges</button><?php endif; ?></div>
                    <div class="p-2 small" id="chargeTotals"></div>
                    <div class="table-responsive scroll"><table class="t" id="chargeTable"><thead><tr><th></th><th>Date</th><th>Type</th><th>Description</th><th class="num">Amount</th><th class="num">LKR</th><th>In the ledger?</th></tr></thead><tbody></tbody></table></div>
                </div>
            </div>

            <!-- ======================= CORRECTIONS & OPENING -->
            <div class="tab-pane fade" id="corrections">
                <div class="note mb-2">Corrections are <b>proposals only</b>. The system never posts them. A reviewer (not the proposer) approves or rejects; Accounts then posts an approved one by hand in the accounting module if they agree, and records the ledger entry here.</div>
                <div class="card mb-3"><div class="card-h"><span>Proposed corrections</span><?php if (!empty($brc['prepare'])): ?><button class="btn btn-sm btn-op" onclick="proposeCorrection()">Propose a correction</button><?php endif; ?></div><div class="table-responsive"><table class="t" id="corrTable"><thead><tr><th>#</th><th>Kind / description</th><th>Proposed entry</th><th class="num">Amount</th><th>Status</th><th>Proposed</th><th>Reviewed</th><th></th></tr></thead><tbody></tbody></table></div></div>
                <div class="card mb-3"><div class="card-h"><span>Opening items (explain bank − book on the first day)</span><?php if (!empty($brc['prepare'])): ?><button class="btn btn-sm btn-op" onclick="proposeOpening()">Add an opening item</button><?php endif; ?></div><div class="table-responsive"><table class="t" id="openTable"><thead><tr><th>#</th><th>Description</th><th class="num">Amount (LKR)</th><th>Evidence</th><th>Status</th><th>Proposed</th><th>Reviewed</th><th></th></tr></thead><tbody></tbody></table></div></div>
            </div>

            <!-- ======================= DAILY RECORDS -->
            <div class="tab-pane fade" id="days">
                <div class="card"><div class="card-h"><span>Daily records</span></div><div class="table-responsive"><table class="t" id="daysTable"><thead><tr><th>#</th><th>Date</th><th>Status</th><th class="num">Bank</th><th class="num">Book (LKR)</th><th class="num">Bank − book</th><th class="num">Unexplained</th><th>Prepared</th><th>Review</th><th></th></tr></thead><tbody></tbody></table></div></div>
            </div>

            <!-- ======================= AUDIT -->
            <div class="tab-pane fade" id="audit">
                <div class="card"><div class="card-h"><span>Audit trail (this account's reconciliation)</span><button class="btn btn-sm btn-light" onclick="loadAudit()">Refresh</button></div><div class="table-responsive scroll" style="max-height:640px"><table class="t" id="auditTable"><thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>Reason / note</th><th>Before</th><th>After</th></tr></thead><tbody></tbody></table></div></div>
            </div>
        </div>
    </div></div></div>
</div>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
const B = window.BRC;
const ACCT = <?= json_encode(['id' => $acct['id'], 'currency' => $acct['currency'], 'label' => $acct['label'], 'map' => $acct['map'], 'ledger_code' => $acct['ledger_code']]) ?>;
const START = ACCT.map ? ACCT.map.rec_start : null;
let DAY = null, OPEN = { bank: [], book: [] }, SEL = { bank: new Set(), book: new Set() }, LEDGERS = [], CHARGE_LEDGER = '';
const $ = id => document.getElementById(id);
const kv = rows => rows.map(r => r === '-' ? '<div class="k" style="grid-column:1/-1;border-top:1px solid var(--line)"></div>' : '<div class="k' + (r[2] ? ' total' : '') + (String(r[0]).startsWith('  ') ? ' sub' : '') + '">' + String(r[0]).trim() + '</div><div class="v' + (r[2] ? ' total' : '') + '">' + r[1] + '</div>').join('');

async function loadDay() {
    const j = await B.get('api/day', { account: ACCT.id, date: $('asAt').value });
    if (j.status !== 200) { $('reasons').innerHTML = '<li class="err">' + B.esc(j.message) + '</li>'; return; }
    DAY = j.day; const d = DAY, cur = ACCT.currency;
    $('statusPill').innerHTML = B.pill(d.status_code, d.status_label);
    $('statusDate').textContent = 'end of ' + d.date + (d.rec_start ? ' · reconciled from ' + d.rec_start : '');
    $('reasons').innerHTML = (d.reasons || []).map(r => '<li>' + B.esc(r) + '</li>').join('');
    $('bankCur').textContent = cur;
    if (!d.bank) { $('bankKv').innerHTML = '<div class="muted">—</div>'; $('bookKv').innerHTML = ''; $('recKv').innerHTML = ''; $('countKv').innerHTML = ''; return; }
    const b = d.bank, k = d.book, r = d.reconciliation, c = d.counts;
    $('bankKv').innerHTML = kv([['Opening on ' + B.esc(d.rec_start), B.money(b.opening, cur)]].concat(cur !== 'LKR' ? [['  rate ' + B.esc(b.opening_rate || '—'), B.money(b.opening_lkr, 'LKR')]] : [])
        .concat([['Closing ' + B.esc(d.date), B.money(b.closing, cur), 1]]).concat(cur !== 'LKR' ? [['  rate ' + B.esc(b.closing_rate || '—') + ' <span class="small2">(' + B.esc(b.closing_rate_source || 'missing') + ')</span>', B.money(b.closing_lkr, 'LKR')]] : []));
    $('bookKv').innerHTML = kv([['Balance before ' + B.esc(d.rec_start), B.money(k.opening)], ['Balance end of ' + B.esc(d.date), B.money(k.closing), 1], ['Last entry on this account', '<span class="small2">' + B.esc(k.account_last_entered || '—') + '</span>'], ['Last entry in the ledger', '<span class="small2">' + B.esc(k.ledger_last_entered || '—') + '</span>']]);
    $('countKv').innerHTML = kv([['Statement lines / ledger rows', c.bank_lines + ' / ' + c.book_rows], ['Matched', c.matched], ['Statement lines not in the ledger', c.bank_unmatched], ['Ledger rows not on the statement', c.book_unmatched],
        ['Timing', c.timing], ['Duplicate-looking', c.duplicate], ['Excluded', c.excluded], ['Bank charges not matched', c.charges], ['Matched to an item after this day', c.matched_later], ['Still to explain', '<b>' + c.unexplained_items + '</b>', 1]]);
    const op = r.opening_remaining !== null && Number(r.opening_remaining) !== 0;
    $('recKv').innerHTML = kv([['Bank closing (LKR)', B.money(b.closing_lkr)], ['Book closing', B.money(k.closing)], ['Difference: bank − book', B.money(r.difference_lkr), 1], '-',
        ['Opening difference on ' + B.esc(d.rec_start) + ' (bank − book)', B.money(r.opening_difference_lkr)],
        ['  cleared since by earlier ledger items now matched', B.money(r.opening_explained_by_earlier_book_items)],
        ['  explained by accepted opening items', B.money(-Number(r.opening_items_accepted))],
        ['  not yet explained' + (op ? ' ' + B.pill('insufficient', 'Unreconciled — insufficient data') : ''), B.money(r.opening_remaining)],
        ['Accepted opening items', B.money(r.opening_items_accepted)], ['Statement items not in the ledger', B.money(r.bank_items_not_in_book)], ['Ledger items not on the statement', B.money(r.book_items_not_on_statement)],
        ['Differences inside matches (rate, rounding)', B.money(r.match_differences)]].concat(cur !== 'LKR' ? [['Revaluation of the ' + cur + ' balance at the day\'s rate', B.money(r.revaluation)]] : [])
        .concat([['Unexplained remainder', B.money(r.unexplained_lkr), 1]]));
    const lr = d.last_record;
    $('lastRecord').innerHTML = lr ? 'Last record for this date: #' + lr.id + ' ' + B.pill(lr.status_code, lr.status_label) + ' prepared by ' + B.esc(lr.prepared_by) + ' at ' + B.esc(lr.prepared_at) + ' · review: ' + B.pill(lr.review_status, lr.review_status) + (lr.reviewed_by ? ' by ' + B.esc(lr.reviewed_by) : '') : '<span class="muted">No record prepared for this date yet.</span>';
    renderCharges();
}
async function prepareDay() {
    const v = await B.ask('Prepare the daily record for ' + $('asAt').value, [{ name: 'note', label: 'Note (optional)', type: 'text' }], 'Prepare');
    if (!v) return;
    B.done(await B.post('day/prepare', { account: ACCT.id, date: $('asAt').value, note: v.note }), () => { loadDay(); loadDays(); });
}

// ------------------------------------------------ statements
async function loadStatements() {
    const j = await B.get('api/statements', { account: ACCT.id });
    const rows = j.statements || [];
    if (!$('asAt').value) { const acc = rows.filter(s => s.status === 'accepted').map(s => s.period_to).sort(); $('asAt').value = acc.length ? acc[acc.length - 1] : B.today(); }
    $('stmtTable').querySelector('tbody').innerHTML = rows.length ? rows.map(s => '<tr><td>' + s.id + '</td><td class="mono small2">' + B.esc(s.period_from) + ' → ' + B.esc(s.period_to) + '</td><td class="num">' + B.money(s.opening_balance) + '</td><td class="num">' + B.money(s.closing_balance) + '</td><td class="num">' + s.line_count + '</td>'
        + '<td class="small2">' + B.esc(s.source) + (s.file_name ? '<div class="muted">' + B.esc(s.file_name) + '</div>' : '') + '</td><td class="small2">' + (s.n_errors ? '<span class="err">' + s.n_errors + ' error(s)</span> ' : '') + (s.n_warnings ? '<span class="warn">' + s.n_warnings + ' warning(s)</span>' : '') + (!s.n_errors && !s.n_warnings ? '<span class="muted">clean</span>' : '') + '</td>'
        + '<td>' + B.pill(s.status, s.status) + '</td><td class="small2">' + B.esc(s.created_by) + ' ' + B.esc((s.created_at || '').slice(0, 16)) + (s.accepted_by ? '<div>accepted: ' + B.esc(s.accepted_by) + ' ' + B.esc((s.accepted_at || '').slice(0, 16)) + '</div>' : '') + (s.void_by ? '<div>void: ' + B.esc(s.void_by) + ' — ' + B.esc(s.void_reason) + '</div>' : '') + '</td>'
        + '<td><button class="btn btn-sm btn-light" onclick="viewStatement(' + s.id + ')">View</button></td></tr>').join('') : '<tr><td colspan="10" class="muted p-3">No statement yet.</td></tr>';
}
function showImport(mode) {
    $('importCard').style.display = ''; $('impSource').value = mode; $('previewBox').innerHTML = ''; $('mapBox').innerHTML = '';
    $('importTitle').textContent = mode === 'upload' ? 'Upload a statement (CSV)' : 'Type the statement lines';
    document.querySelectorAll('.uploadOnly').forEach(e => e.style.display = mode === 'upload' ? '' : 'none');
    $('fileBox').style.display = mode === 'upload' ? '' : 'none'; $('manualBox').style.display = mode === 'manual' ? '' : 'none';
    if (mode === 'manual' && !$('manualTable').querySelector('tbody').children.length) for (let i = 0; i < 5; i++) addManualRow();
}
function addManualRow() {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td><input type="date" class="form-control form-control-sm" data-k="date"></td><td><input class="form-control form-control-sm" data-k="description"></td><td><input class="form-control form-control-sm" data-k="reference"></td><td><input class="form-control form-control-sm text-end" data-k="money_in"></td><td><input class="form-control form-control-sm text-end" data-k="money_out"></td><td><input class="form-control form-control-sm text-end" data-k="balance"></td>';
    $('manualTable').querySelector('tbody').appendChild(tr);
}
let LAST_MAP = null;
function importData() {
    const fd = new FormData($('importForm'));
    fd.append('account', ACCT.id); fd.append('currency', ACCT.currency);
    if ($('impSource').value === 'manual') {
        fd.delete('file');
        const lines = [...$('manualTable').querySelectorAll('tbody tr')].map(tr => Object.fromEntries([...tr.querySelectorAll('input')].map(i => [i.dataset.k, i.value])));
        fd.append('lines', JSON.stringify(lines));
    } else {
        const sel = {}; document.querySelectorAll('#mapBox select[data-col]').forEach(s => { if (s.value !== '') sel[s.dataset.col] = s.value; });
        if (Object.keys(sel).length) fd.append('map', JSON.stringify(sel));
        const hr = document.getElementById('hdrRow'); if (hr) fd.append('header_row', hr.value);
    }
    return fd;
}
function renderChecks(c) {
    const s = c.summary || {};
    return '<div class="row g-2 small"><div class="col-md-6"><div class="kv">' + [['Period', B.esc(s.period_from) + ' → ' + B.esc(s.period_to)], ['Opening', B.money(s.opening_balance, s.currency)], ['Money in', B.money(s.total_in)], ['Money out', B.money(s.total_out)], ['Closing', B.money(s.closing_balance, s.currency)], ['Lines', s.line_count]].map(x => '<div class="k">' + x[0] + '</div><div class="v">' + x[1] + '</div>').join('') + '</div>'
        + '<div class="small2 muted mt-1">Categories: ' + Object.entries(s.categories || {}).map(([k, v]) => B.esc(k) + ' ' + v).join(', ') + '</div></div>'
        + '<div class="col-md-6">' + (c.errors && c.errors.length ? '<div class="err fw-semibold">Must be resolved before acceptance:</div><ul class="err mb-1">' + c.errors.map(e => '<li>' + B.esc(e) + '</li>').join('') + '</ul>' : '<div style="color:#1f7a43" class="fw-semibold">No check errors.</div>')
        + (c.warnings && c.warnings.length ? '<div class="warn fw-semibold">To note:</div><ul class="warn mb-0">' + c.warnings.map(e => '<li>' + B.esc(e) + '</li>').join('') + '</ul>' : '') + '</div></div>';
}
async function previewStatement() {
    const j = await B.post('statement/preview', importData());
    if (j.status !== 200) return B.done(j);
    if ($('impSource').value === 'upload') {
        const hd = j.headers || [], m = j.map || {};
        const cols = [['date', 'Date'], ['value_date', 'Value date'], ['description', 'Description'], ['reference', 'Reference'], ['money_in', 'Money in'], ['money_out', 'Money out'], ['amount', 'One amount column'], ['balance', 'Balance']];
        const opts = sel => '<option value="">—</option>' + hd.map((h, i) => '<option value="' + i + '"' + (String(sel) === String(i) ? ' selected' : '') + '>' + B.esc(h || ('column ' + (i + 1))) + '</option>').join('');
        $('mapBox').innerHTML = '<div class="small fw-semibold mb-1">Columns recognised (change them if needed, then Check again)</div><div class="row g-2">' + cols.map(([k, l]) => '<div class="col-6 col-md-3 col-xl"><label class="small2 muted">' + l + '</label><select class="form-select form-select-sm" data-col="' + k + '">' + opts(m[k]) + '</select></div>').join('')
            + '<div class="col-6 col-md-3 col-xl"><label class="small2 muted">Header row (0 = first)</label><input id="hdrRow" class="form-control form-control-sm" value="' + (j.header_row ?? '') + '"></div></div>';
    }
    const lines = j.lines || [];
    $('previewBox').innerHTML = renderChecks(j.checks) + '<div class="table-responsive scroll mt-2"><table class="t"><thead><tr><th>#</th><th>Date</th><th>Description</th><th>Ref</th><th class="num">In</th><th class="num">Out</th><th class="num">Balance</th><th>Category</th><th>Problems</th></tr></thead><tbody>'
        + lines.map(l => '<tr><td>' + l.line_no + '</td><td class="mono small2">' + B.esc(l.txn_date || '?') + '</td><td>' + B.esc(l.description) + '</td><td class="small2">' + B.esc(l.reference || '') + '</td><td class="num">' + (Number(l.money_in) ? B.money(l.money_in) : '') + '</td><td class="num">' + (Number(l.money_out) ? B.money(l.money_out) : '') + '</td><td class="num">' + (l.balance !== null ? B.money(l.balance) : '') + '</td><td>' + B.pill(l.category === 'charge' ? 'charge' : 'none', l.category + (l.charge_type ? ': ' + l.charge_type : '')) + '</td><td class="err small2">' + (l.errors || []).map(B.esc).join('; ') + '</td></tr>').join('')
        + '</tbody></table></div><div class="small2 muted">' + B.esc(j.message) + '</div>';
}
async function saveStatement() {
    const j = await B.post('statement/save', importData());
    B.done(j, () => { $('importCard').style.display = 'none'; loadStatements(); if (j.id) viewStatement(j.id); });
}
async function viewStatement(id) {
    const j = await B.get('api/statement', { id });
    if (j.status !== 200) return B.done(j);
    const s = j.statement, A = B.access;
    $('stmtView').style.display = '';
    $('stmtViewTitle').innerHTML = 'Statement ' + s.id + ' · ' + B.esc(s.period_from) + ' → ' + B.esc(s.period_to) + ' ' + B.pill(s.status, s.status);
    let act = '';
    if (A.prepare && s.status === 'draft') act += '<button class="btn btn-sm btn-p" onclick="acceptStatement(' + s.id + ')">Accept</button>';
    if ((A.prepare && s.status === 'draft') || (A.review && s.status === 'accepted')) act += '<button class="btn btn-sm btn-light" onclick="voidStatement(' + s.id + ')">Void</button>';
    $('stmtViewActions').innerHTML = act;
    $('stmtChecks').innerHTML = j.checks ? renderChecks(j.checks) : '';
    $('stmtLines').querySelector('tbody').innerHTML = j.lines.map(l => '<tr><td>' + l.line_no + (Number(l.edited) ? ' <span class="pill s-proposed" title="changed after import; the original is kept">edited</span>' : '') + '</td><td class="mono small2">' + B.esc(l.txn_date) + '</td><td>' + B.esc(l.description) + '</td><td class="small2">' + B.esc(l.reference || '') + '</td>'
        + '<td class="num">' + (Number(l.money_in) ? B.money(l.money_in) : '') + '</td><td class="num">' + (Number(l.money_out) ? B.money(l.money_out) : '') + '</td><td class="num">' + (l.balance !== null ? B.money(l.balance) : '') + '</td><td class="num">' + B.money(l.lkr_amount) + '</td>'
        + '<td class="small2">' + B.esc(l.category) + (l.charge_type ? ': ' + B.esc(l.charge_type) : '') + '</td><td>' + B.pill(l.state, l.state) + '</td>'
        + '<td class="text-nowrap">' + (A.prepare && s.status === 'draft' ? '<button class="btn btn-sm btn-light py-0" onclick=\'editLine(' + JSON.stringify(l).replace(/'/g, '&#39;') + ')\'>Edit</button> ' : '') + (A.prepare && s.status !== 'void' ? '<button class="btn btn-sm btn-light py-0" onclick="recat(' + l.id + ', \'' + l.category + '\')">Category</button>' : '') + '</td></tr>').join('');
    $('stmtView').scrollIntoView({ behavior: 'smooth' });
}
async function acceptStatement(id) {
    const r = await Swal.fire({ title: 'Accept statement ' + id + '?', text: 'Its lines are locked and can then be matched. Nothing is posted to the ledger.', showCancelButton: true, confirmButtonText: 'Accept', confirmButtonColor: '#842484' });
    if (!r.isConfirmed) return;
    const j = await B.post('statement/accept', { id }); B.done(j, () => { loadStatements(); viewStatement(id); loadDay(); });
}
async function voidStatement(id) {
    const v = await B.ask('Void statement ' + id, [{ name: 'reason', label: 'Reason', min: 5 }], 'Void');
    if (!v) return; B.done(await B.post('statement/void', { id, reason: v.reason }), () => { loadStatements(); viewStatement(id); loadDay(); });
}
async function editLine(l) {
    const v = await B.ask('Change line ' + l.line_no + ' (the original is kept)', [{ name: 'txn_date', label: 'Date', type: 'date', value: l.txn_date }, { name: 'description', label: 'Description', value: l.description }, { name: 'reference', label: 'Reference', value: l.reference || '' },
        { name: 'money_in', label: 'Money in', value: l.money_in }, { name: 'money_out', label: 'Money out', value: l.money_out }, { name: 'balance', label: 'Balance (as printed)', value: l.balance ?? '' }, { name: 'reason', label: 'Reason for the change', min: 5 }]);
    if (!v) return; B.done(await B.post('line/edit', Object.assign({ id: l.id }, v)), () => viewStatement(l.statement_id));
}
async function recat(id, cur) {
    const v = await B.ask('Change the category', [{ name: 'category', label: 'Category', type: 'select', value: cur, options: [['receipt', 'Receipt'], ['payment', 'Payment'], ['charge', 'Bank charge'], ['transfer', 'Transfer between Avenir accounts'], ['interest', 'Interest'], ['other', 'Other']] },
        { name: 'charge_type', label: 'Charge type (for bank charges)', value: '' }, { name: 'reason', label: 'Reason', min: 5 }]);
    if (!v) return; const j = await B.post('line/category', Object.assign({ id }, v)); B.done(j, () => { loadStatements(); loadDay(); const t = $('stmtViewTitle').textContent.match(/Statement (\d+)/); if (t) viewStatement(t[1]); });
}

// ------------------------------------------------ matching
async function loadOpen() {
    const j = await B.get('api/open', { account: ACCT.id, from: $('mFrom').value, to: $('mTo').value, before: $('mBefore').checked ? 30 : 0 });
    if (j.status !== 200) return;
    OPEN = j.open; SEL = { bank: new Set(), book: new Set() };
    const to = $('mTo').value;
    $('openBank').querySelector('tbody').innerHTML = OPEN.bank.map(l => '<tr data-id="' + l.id + '"><td><input type="checkbox" onchange="sel(\'bank\',\'' + l.id + '\',this)"></td><td class="mono small2">' + B.esc(l.txn_date) + '</td><td>' + B.esc(l.description) + (l.reference ? '<div class="small2 muted">' + B.esc(l.reference) + '</div>' : '') + '</td><td class="num">' + B.money(l.amount) + '</td><td class="num">' + B.money(l.lkr_amount) + '</td><td class="small2">' + B.esc(l.category) + '</td></tr>').join('') || '<tr><td colspan="6" class="muted p-2">None.</td></tr>';
    const book = OPEN.book.filter(b => b.date <= to);
    $('openBook').querySelector('tbody').innerHTML = book.map(b => '<tr data-key="' + b.key + '"><td><input type="checkbox" onchange="sel(\'book\',\'' + b.key + '\',this)"></td><td class="mono small2">' + B.esc(b.date) + (START && b.date < START ? ' <span class="pill s-timing">before first day</span>' : '') + '</td><td>' + B.esc(b.description) + (b.payee ? '<div class="small2 muted">' + B.esc(b.payee) + '</div>' : '') + '<div class="small2 muted mono">ledger ' + B.esc(b.key) + '</div></td><td class="small2">' + B.esc(b.pv && b.pv !== '0' ? b.pv : '') + '</td><td class="num">' + B.money(b.amount) + '</td></tr>').join('') || '<tr><td colspan="5" class="muted p-2">None.</td></tr>';
    $('bankOpenCount').textContent = OPEN.bank.length + ' open'; $('bookOpenCount').textContent = book.length + ' open';
    const ms = (j.matches || []);
    $('matchedCount').textContent = ms.length + ' active';
    $('matchedTable').querySelector('tbody').innerHTML = ms.slice().reverse().map(m => '<tr><td>' + m.id + '</td><td class="small2">' + m.items.filter(i => i.side === 'bank').map(i => B.esc(i.item_date) + ' ' + B.money(i.amount) + ' <span class="muted">#' + i.line_id + '</span>').join('<br>') + '</td><td class="small2">' + m.items.filter(i => i.side === 'book').map(i => B.esc(i.item_date) + ' ' + B.money(i.amount) + ' <span class="muted mono">' + B.esc(i.book_key) + '</span>').join('<br>') + '</td><td class="num">' + (Number(m.difference_lkr) ? B.money(m.difference_lkr) + '<div class="small2 muted">' + B.esc(m.diff_reason) + '</div>' : '—') + '</td><td class="small2">' + B.esc(m.created_by) + '<div class="muted">' + B.esc(m.method) + '</div></td><td>' + (B.access.prepare ? '<button class="btn btn-sm btn-light py-0" onclick="undoMatch(' + m.id + ')">Undo</button>' : '') + '</td></tr>').join('') || '<tr><td colspan="6" class="muted p-2">None.</td></tr>';
    const mk = (j.marks || []);
    $('marksTable').querySelector('tbody').innerHTML = mk.map(k => '<tr><td class="small2">' + (k.side === 'bank' ? 'statement line #' + k.line_id : 'ledger ' + B.esc(k.book_key)) + '<div>' + B.esc(k.item_date) + ' ' + B.money(k.amount) + '</div></td><td>' + B.pill(k.mark, k.mark === 'duplicate' ? 'duplicate-looking' : k.mark) + (k.expected_date ? '<div class="small2 muted">expected ' + B.esc(k.expected_date) + '</div>' : '') + '</td><td class="small2">' + B.esc(k.note) + '<div class="muted">' + B.esc(k.created_by) + '</div></td><td>' + (B.access.prepare ? '<button class="btn btn-sm btn-light py-0" onclick="clearMark(' + k.id + ')">Clear</button>' : '') + '</td></tr>').join('') || '<tr><td colspan="4" class="muted p-2">None.</td></tr>';
    updSel();
}
function sel(side, id, cb) { cb.checked ? SEL[side].add(id) : SEL[side].delete(id); cb.closest('tr').classList.toggle('sel', cb.checked); updSel(); }
function clearSel() { SEL = { bank: new Set(), book: new Set() }; document.querySelectorAll('#openBank input, #openBook input').forEach(c => { c.checked = false; c.closest('tr').classList.remove('sel'); }); updSel(); }
function updSel() {
    if (!$('selBank')) return;
    const bs = OPEN.bank.filter(l => SEL.bank.has(String(l.id))).reduce((a, l) => a + Number(l.lkr_amount || 0), 0);
    const ks = OPEN.book.filter(b => SEL.book.has(b.key)).reduce((a, b) => a + Number(b.amount), 0);
    $('selBank').innerHTML = B.money(bs.toFixed(2)); $('selBook').innerHTML = B.money(ks.toFixed(2)); $('selDiff').innerHTML = B.money((bs - ks).toFixed(2));
}
async function matchSelected() {
    const line_ids = [...SEL.bank], book_keys = [...SEL.book];
    if (!line_ids.length || !book_keys.length) return Swal.fire('Choose items', 'Tick at least one statement line and one ledger row.', 'info');
    const bs = OPEN.bank.filter(l => SEL.bank.has(String(l.id))).reduce((a, l) => a + Number(l.lkr_amount || 0), 0), ks = OPEN.book.filter(b => SEL.book.has(b.key)).reduce((a, b) => a + Number(b.amount), 0);
    let extra = {};
    if (Math.abs(bs - ks) > 0.004) {
        const v = await B.ask('The two sides differ by ' + (bs - ks).toFixed(2) + ' LKR', [{ name: 'diff_reason', label: 'Why', type: 'select', options: [['exchange rate', 'Exchange rate (USD / JPY accounts)'], ['rounding', 'Rounding (up to 1.00)'], ['other', 'Other (explain)']] }, { name: 'note', label: 'Note' }], 'Match');
        if (!v) return; extra = v;
    }
    B.done(await B.post('match/create', Object.assign({ account: ACCT.id, line_ids, book_keys, method: 'manual' }, extra)), () => { loadOpen(); loadDay(); });
}
async function classifySelected() {
    const items = [...SEL.bank].map(id => ['bank', id]).concat([...SEL.book].map(k => ['book', k]));
    if (items.length !== 1) return Swal.fire('Choose one item', 'Tick exactly one statement line or one ledger row to classify.', 'info');
    const [side, ref] = items[0];
    const v = await B.ask('Classify this ' + (side === 'bank' ? 'statement line' : 'ledger row'), [
        { name: 'mark', label: 'As', type: 'select', options: [['timing', 'Timing — expected to clear later'], ['duplicate', 'Duplicate-looking — to investigate'], ['excluded', 'Excluded — with a reason']] },
        { name: 'expected_date', label: 'Expected to clear on (timing)', type: 'date' }, { name: 'related', label: 'Looks like (duplicate-looking): line # or ledger row', type: 'text' }, { name: 'note', label: 'Note', min: 5 }], 'Classify');
    if (!v) return;
    B.done(await B.post('mark/create', Object.assign({ account: ACCT.id, side, ref }, v)), () => { loadOpen(); loadDay(); });
}
async function undoMatch(id) { const v = await B.ask('Undo match ' + id, [{ name: 'reason', label: 'Reason', min: 5 }], 'Undo'); if (!v) return; B.done(await B.post('match/undo', { id, reason: v.reason }), () => { loadOpen(); loadDay(); }); }
async function clearMark(id) { const v = await B.ask('Clear this classification', [{ name: 'reason', label: 'Reason', min: 5 }], 'Clear'); if (!v) return; B.done(await B.post('mark/clear', { id, reason: v.reason }), () => { loadOpen(); loadDay(); }); }
let SUGG = [];
async function suggest() {
    const j = await B.get('api/suggest', { account: ACCT.id, from: $('mFrom').value, to: $('mTo').value });
    SUGG = j.suggestions || [];
    const dups = j.duplicates || [];
    $('suggestBox').innerHTML = '<div class="card mb-3"><div class="card-h"><span>Suggested matches (' + SUGG.length + ') — nothing is matched until you accept</span>' + (SUGG.some(s => s.confidence === 'high') ? '<button class="btn btn-sm btn-p" onclick="acceptAll()">Accept all high-confidence</button>' : '') + '</div><div class="scroll"><table class="t"><thead><tr><th>Statement line</th><th>Ledger row(s)</th><th>Confidence</th><th>Why</th><th></th></tr></thead><tbody>'
        + SUGG.map((s, i) => '<tr><td class="small2">' + B.esc(s.line.date) + ' ' + B.money(s.line.amount) + '<div>' + B.esc(s.line.description) + '</div></td><td class="small2">' + s.book.map(b => B.esc(b.date) + ' ' + B.money(b.amount) + ' <span class="mono muted">' + B.esc(b.key) + '</span> ' + B.esc(b.payee || b.description)).join('<br>') + '</td><td>' + B.pill(s.confidence === 'high' ? 'confirmed' : (s.confidence === 'medium' ? 'explained' : 'proposed'), s.confidence) + (s.alternatives ? '<div class="small2 muted">' + s.alternatives + ' other candidate(s)</div>' : '') + '</td><td class="small2">' + s.why.map(B.esc).join('; ') + '</td><td><button class="btn btn-sm btn-op py-0" id="sg' + i + '" onclick="acceptSugg(' + i + ')">Accept</button></td></tr>').join('')
        + '</tbody></table></div></div>' + (dups.length ? '<div class="card mb-3"><div class="card-h"><span>Duplicate-looking items (' + dups.length + ') — flags only; classify them if they need investigating</span></div><div class="p-2 small">' + dups.map(d => '<div class="mb-1">' + B.pill('duplicate', d.side === 'book' ? 'ledger' : 'statement') + ' ' + d.items.map(x => B.esc(x.date) + ' ' + B.money(x.amount) + ' ' + B.esc(x.description || '') + ' <span class="mono muted">' + B.esc(x.key || ('#' + x.line_id)) + '</span>').join(' · ') + '</div>').join('') + '</div></div>' : '');
}
async function acceptSugg(i, quiet) {
    const s = SUGG[i];
    const j = await B.post('match/create', { account: ACCT.id, line_ids: [s.line.id], book_keys: s.book.map(b => b.key), method: 'suggested', diff_reason: ACCT.currency !== 'LKR' ? 'exchange rate' : '' });
    if (!quiet) B.done(j, () => { loadOpen(); loadDay(); });
    if (j.status === 200 && $('sg' + i)) { $('sg' + i).disabled = true; $('sg' + i).textContent = 'Matched'; }
    return j;
}
async function acceptAll() {
    let ok = 0, bad = 0;
    for (let i = 0; i < SUGG.length; i++) if (SUGG[i].confidence === 'high') { const j = await acceptSugg(i, true); j.status === 200 ? ok++ : bad++; }
    Swal.fire({ icon: bad ? 'warning' : 'success', title: ok + ' matched' + (bad ? ', ' + bad + ' not' : ''), timer: 2000, showConfirmButton: false });
    loadOpen(); loadDay();
}

// ------------------------------------------------ charges
function renderCharges() {
    if (!DAY || !DAY.items) return;
    const ch = DAY.items.charges || [], byType = {};
    let open = 0;
    ch.forEach(l => { const t = l.charge_type || 'Bank charge'; byType[t] = byType[t] || { n: 0, lkr: 0 }; byType[t].n++; byType[t].lkr += Number(l.amount_lkr || 0); if (!l.correction) open += Number(l.amount_lkr || 0); });
    $('chargeTotals').innerHTML = ch.length ? 'Bank charges not matched to the ledger: <b>' + ch.length + '</b> line(s) · ' + Object.entries(byType).map(([k, v]) => v.n + ' × ' + B.esc(k) + ' ' + B.money(v.lkr.toFixed(2))).join(' · ') + ' · without a proposed correction: <b>' + B.money(open.toFixed(2), 'LKR') + '</b>' : '<span class="muted">No unmatched bank charges up to this date.</span>';
    $('chargeTable').querySelector('tbody').innerHTML = ch.map(l => '<tr><td>' + (!l.correction && B.access.prepare ? '<input type="checkbox" class="chg" value="' + l.line_id + '" checked>' : '') + '</td><td class="mono small2">' + B.esc(l.date) + '</td><td class="small2">' + B.esc(l.charge_type || 'Bank charge') + '</td><td>' + B.esc(l.description) + '</td><td class="num">' + B.money(l.amount) + '</td><td class="num">' + B.money(l.amount_lkr) + '</td><td>' + (l.correction ? B.pill(l.correction.status, 'correction #' + l.correction.id + ' ' + l.correction.status) : B.pill('insufficient', 'not in the ledger')) + '</td></tr>').join('') || '<tr><td colspan="7" class="muted p-2">None.</td></tr>';
}
async function proposeCharges() {
    const ids = [...document.querySelectorAll('.chg:checked')].map(c => c.value);
    if (!ids.length) return Swal.fire('Choose charges', 'Tick the bank-charge lines to include.', 'info');
    const v = await B.ask('Propose a correction for ' + ids.length + ' charge line(s)', [{ name: 'charge_ledger', label: 'Bank-charges ledger account (debit)', type: 'select', value: CHARGE_LEDGER, options: [['', '— the payment workflow\'s mapping —']].concat(LEDGERS.map(l => [l.L_Code, l.PName + ' (' + l.L_Code + ')'])) }], 'Propose');
    if (!v) return;
    B.done(await B.post('correction/charges', { account: ACCT.id, line_ids: ids, charge_ledger: v.charge_ledger }), () => { loadDay(); loadCorrections(); });
}

// ------------------------------------------------ corrections & opening
async function loadCorrections() {
    const j = await B.get('api/corrections', { account: ACCT.id });
    LEDGERS = j.ledgers || []; CHARGE_LEDGER = j.charge_ledger || '';
    const A = B.access;
    $('corrTable').querySelector('tbody').innerHTML = (j.corrections || []).map(c => '<tr><td>' + c.id + '</td><td><b class="small">' + B.esc(c.kind_label) + '</b><div class="small2">' + B.esc(c.description) + '</div></td><td class="small2">' + c.entry.map(e => '<div>' + (Number(e.dr) ? 'Dr ' : '&nbsp;&nbsp;&nbsp;Cr ') + B.esc(e.ledger_name) + ' <span class="mono muted">' + B.esc(e.ledger_code) + '</span> ' + B.money(Number(e.dr) || e.cr) + '</div>').join('') + '<div class="muted">date ' + B.esc(c.entry_date) + '</div></td>'
        + '<td class="num">' + B.money(c.amount) + '</td><td>' + B.pill(c.status, c.status) + (c.posted_ref ? '<div class="small2">posted by hand: entry ' + B.esc(c.posted_ref) + '</div>' : '') + '</td><td class="small2">' + B.esc(c.created_by) + '<div class="muted">' + B.esc((c.created_at || '').slice(0, 16)) + '</div></td><td class="small2">' + B.esc(c.reviewed_by || '') + '<div class="muted">' + B.esc((c.reviewed_at || '').slice(0, 16)) + '</div>' + B.esc(c.review_note || '') + '</td>'
        + '<td class="text-nowrap">' + (A.review && c.status === 'proposed' && c.created_by !== A.user ? '<button class="btn btn-sm btn-p py-0 me-1" onclick="decideCorr(' + c.id + ',\'approve\')">Approve</button><button class="btn btn-sm btn-light py-0" onclick="decideCorr(' + c.id + ',\'reject\')">Reject</button>' : '')
        + (A.prepare && c.status === 'proposed' && c.created_by === A.user ? '<button class="btn btn-sm btn-light py-0" onclick="withdrawCorr(' + c.id + ')">Withdraw</button>' : '') + (A.prepare && c.status === 'approved' && !c.posted_ref ? '<button class="btn btn-sm btn-light py-0" onclick="notePosted(' + c.id + ')">Record posting</button>' : '') + '</td></tr>').join('') || '<tr><td colspan="8" class="muted p-2">None.</td></tr>';
    $('openTable').querySelector('tbody').innerHTML = (j.opening || []).map(o => '<tr><td>' + o.id + '</td><td>' + B.esc(o.description) + '</td><td class="num">' + B.money(o.amount) + '</td><td class="small2">' + B.esc(o.evidence || '') + '</td><td>' + B.pill(o.status, o.status) + '</td><td class="small2">' + B.esc(o.created_by) + '</td><td class="small2">' + B.esc(o.reviewed_by || '') + ' ' + B.esc(o.review_note || '') + '</td><td class="text-nowrap">'
        + (A.review && o.status === 'proposed' && o.created_by !== A.user ? '<button class="btn btn-sm btn-p py-0 me-1" onclick="decideOpen(' + o.id + ',\'accept\')">Accept</button><button class="btn btn-sm btn-light py-0" onclick="decideOpen(' + o.id + ',\'reject\')">Reject</button>' : '') + (A.prepare && o.status === 'proposed' && o.created_by === A.user ? '<button class="btn btn-sm btn-light py-0" onclick="decideOpen(' + o.id + ',\'withdraw\')">Withdraw</button>' : '') + '</td></tr>').join('') || '<tr><td colspan="8" class="muted p-2">None.</td></tr>';
}
async function proposeCorrection() {
    const opts = LEDGERS.map(l => [l.L_Code, l.PName + ' (' + l.L_Code + ')']);
    const v = await B.ask('Propose a correction (a proposal only — never posted by the system)', [
        { name: 'kind', label: 'Kind', type: 'select', options: [['unbooked_receipt', 'Receipt on the statement, not in the ledger'], ['unbooked_payment', 'Payment on the statement, not in the ledger'], ['amount_difference', 'Amount differs'], ['duplicate_looking', 'Duplicate-looking ledger entry'], ['fx_difference', 'Exchange-rate difference'], ['bank_charges', 'Bank charges'], ['other', 'Other']] },
        { name: 'description', label: 'Description', min: 5 }, { name: 'entry_date', label: 'Proposed entry date', type: 'date', value: $('asAt').value },
        { name: 'dr_code', label: 'Debit ledger account', type: 'select', options: [['', '—']].concat(opts), value: '' }, { name: 'cr_code', label: 'Credit ledger account', type: 'select', options: [['', '—']].concat(opts) },
        { name: 'amount', label: 'Amount (LKR)', required: true }, { name: 'line_ids', label: 'Statement line numbers it relates to (optional, comma-separated)' }, { name: 'book_keys', label: 'Ledger rows it relates to (optional, e.g. 28741.1)' }], 'Propose');
    if (!v) return;
    const lines = [{ ledger_code: v.dr_code, dr: v.amount, cr: '' }, { ledger_code: v.cr_code, dr: '', cr: v.amount }];
    B.done(await B.post('correction/propose', { account: ACCT.id, kind: v.kind, description: v.description, entry_date: v.entry_date, lines, line_ids: v.line_ids, book_keys: v.book_keys }), () => { loadCorrections(); loadDay(); });
}
async function decideCorr(id, decision) { const v = await B.ask((decision === 'approve' ? 'Approve' : 'Reject') + ' correction ' + id, [{ name: 'note', label: decision === 'approve' ? 'Note (optional)' : 'Reason', min: decision === 'approve' ? 0 : 5 }], decision === 'approve' ? 'Approve' : 'Reject'); if (!v) return; B.done(await B.post('correction/decide', { id, decision, note: v.note }), () => { loadCorrections(); loadDay(); }); }
async function withdrawCorr(id) { const v = await B.ask('Withdraw correction ' + id, [{ name: 'reason', label: 'Reason', min: 5 }], 'Withdraw'); if (!v) return; B.done(await B.post('correction/withdraw', { id, reason: v.reason }), () => { loadCorrections(); loadDay(); }); }
async function notePosted(id) { const v = await B.ask('Record the ledger entry posted by hand for correction ' + id, [{ name: 'ledger_id', label: 'Ledger entry ID', required: true }], 'Record'); if (!v) return; B.done(await B.post('correction/posted', { id, ledger_id: v.ledger_id }), loadCorrections); }
async function proposeOpening() {
    const v = await B.ask('Add an opening item', [{ name: 'description', label: 'What explains part of the difference on the first day', min: 5 }, { name: 'amount', label: 'Amount (LKR): + if it makes the bank higher than the book', required: true }, { name: 'evidence', label: 'Evidence (document, statement page…)' }], 'Propose');
    if (!v) return; B.done(await B.post('opening/propose', Object.assign({ account: ACCT.id }, v)), () => { loadCorrections(); loadDay(); });
}
async function decideOpen(id, decision) { const v = await B.ask({ accept: 'Accept', reject: 'Reject', withdraw: 'Withdraw' }[decision] + ' opening item ' + id, [{ name: 'note', label: decision === 'accept' ? 'Note (optional)' : 'Reason', min: decision === 'accept' ? 0 : 5 }], 'OK'); if (!v) return; B.done(await B.post('opening/decide', { id, decision, note: v.note }), () => { loadCorrections(); loadDay(); }); }

// ------------------------------------------------ days & audit
async function loadDays() {
    const j = await B.get('api/days', { account: ACCT.id }); const A = B.access;
    $('daysTable').querySelector('tbody').innerHTML = (j.days || []).map(d => '<tr><td>' + d.id + '</td><td class="mono small2">' + B.esc(d.rec_date) + '</td><td>' + B.pill(d.status_code, d.status_label) + '</td><td class="num">' + B.money(d.bank_closing, d.currency) + '</td><td class="num">' + B.money(d.book_closing) + '</td><td class="num">' + B.money(d.difference_lkr) + '</td><td class="num">' + B.money(d.unexplained_lkr) + '</td><td class="small2">' + B.esc(d.prepared_by) + '<div class="muted">' + B.esc((d.prepared_at || '').slice(0, 16)) + '</div>' + B.esc(d.prepare_note || '') + '</td><td>' + B.pill(d.review_status, d.review_status) + '<div class="small2">' + B.esc(d.reviewed_by || '') + ' ' + B.esc(d.review_note || '') + '</div></td><td class="text-nowrap">'
        + (A.review && d.review_status === 'prepared' && d.prepared_by !== A.user ? '<button class="btn btn-sm btn-p py-0 me-1" onclick="reviewDay(' + d.id + ',\'reviewed\')">Reviewed</button><button class="btn btn-sm btn-light py-0" onclick="reviewDay(' + d.id + ',\'returned\')">Return</button>' : '') + '</td></tr>').join('') || '<tr><td colspan="10" class="muted p-2">No daily record yet.</td></tr>';
}
async function reviewDay(id, decision) { const v = await B.ask(decision === 'reviewed' ? 'Mark daily record ' + id + ' as reviewed' : 'Return daily record ' + id, [{ name: 'note', label: decision === 'reviewed' ? 'Note (optional)' : 'Reason', min: decision === 'reviewed' ? 0 : 5 }], 'OK'); if (!v) return; B.done(await B.post('day/review', { id, decision, note: v.note }), () => { loadDays(); loadDay(); }); }
const short = s => { if (!s) return ''; const t = String(s); return t.length > 160 ? '<details><summary class="small2">' + B.esc(t.slice(0, 120)) + '…</summary><pre class="small2 mb-0" style="white-space:pre-wrap">' + B.esc(t) + '</pre></details>' : '<span class="small2">' + B.esc(t) + '</span>'; };
async function loadAudit() {
    const j = await B.get('api/audit', { account: ACCT.id });
    $('auditTable').querySelector('tbody').innerHTML = (j.audit || []).map(a => '<tr><td class="mono small2 text-nowrap">' + B.esc(a.at) + '</td><td class="small2">' + B.esc(a.user) + '</td><td class="small2">' + B.esc(a.action) + '</td><td class="small2">' + B.esc(a.ref_type) + ' ' + B.esc(a.ref_id) + '</td><td class="small2">' + B.esc(a.reason || '') + '</td><td>' + short(a.prev_value) + '</td><td>' + short(a.new_value) + '</td></tr>').join('') || '<tr><td colspan="7" class="muted p-2">Nothing yet.</td></tr>';
}
async function refreshAll() { await loadDay(); if (START) { $('mFrom').value = $('mFrom').value || START; $('mTo').value = $('asAt').value; } loadOpen(); loadCorrections(); loadDays(); loadAudit(); }
(async () => {
    await loadStatements();
    if (START) { $('mFrom').value = START; $('mTo').value = $('asAt').value; }
    await refreshAll();
    const h = location.hash; if (h) { const t = document.querySelector('a[href="' + h + '"]'); if (t) new bootstrap.Tab(t).show(); }
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(a => a.addEventListener('shown.bs.tab', e => history.replaceState(null, '', e.target.getAttribute('href'))));
})();
</script>
</body>
