<?= view('layouts/head') ?>
<?php /** tf-bankrec-r1/r2 — daily exchange rates and transfers between Avenir accounts (accounts/daily_bank_rec/fx). */ ?>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content"><div class="container-fluid brc">
        <?= view('pages/accounts/bankrec/_common', ['brc' => $brc]) ?>
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <div>
                <div class="small2"><a href="<?= base_url('accounts/daily_bank_rec') ?>">Daily Bank Reconciliation</a> /</div>
                <h4 class="mb-0" style="color:var(--p); font-weight:700;">Rates &amp; transfers</h4>
                <div class="small muted" style="max-width:900px;">The daily rate (LKR per one USD or JPY) values each foreign-currency statement line in LKR, beside its original amount. A rate is taken only for its own day: the rate entered here for that day, or (USD only) the system's daily USD rate of that day — never another day's rate. Changing a rate is refused if someone changed it after you opened it, and daily records already reviewed for the days it affects are flagged for re-review. A transfer pairs money leaving one Avenir account with money arriving in another (for example USD → LKR) and shows the rate actually obtained against the day's rate. Each side is still matched to the ledger in its own account.</div>
            </div>
            <div class="d-flex gap-2 align-items-end">
                <div><label class="form-label small2 mb-0 muted">From</label><input type="date" class="form-control form-control-sm" id="fFrom"></div>
                <div><label class="form-label small2 mb-0 muted">To</label><input type="date" class="form-control form-control-sm" id="fTo"></div>
                <button class="btn btn-sm btn-op" id="btnShow" onclick="load()">Show</button>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-xl-5"><div class="card"><div class="card-h"><span>Daily rates used by the reconciliation</span><?php if (!empty($brc['prepare'])): ?><button class="btn btn-sm btn-p" onclick="editRate()">Enter a rate</button><?php endif; ?></div>
                <div class="scroll"><table class="t" id="rateTable"><thead><tr><th>Date</th><th>Currency</th><th class="num">LKR per unit</th><th>Source</th><th>Entered</th><th></th></tr></thead><tbody></tbody></table></div>
                <div class="p-2 small2 muted" id="sysNote"></div></div></div>
            <div class="col-xl-7"><div class="card mb-3"><div class="card-h"><span>Transfers between Avenir accounts</span></div>
                <div class="scroll"><table class="t" id="trTable"><thead><tr><th>Date</th><th>From</th><th>To</th><th class="num">Rate obtained</th><th class="num">Day's rate</th><th class="num">Difference (LKR)</th><th>Ledger</th><th></th></tr></thead><tbody></tbody></table></div></div>
                <div class="card" id="sugCard" style="display:none;"><div class="card-h"><span>Possible transfers (statement lines that pair up) — nothing is recorded until you accept</span></div><div class="scroll"><table class="t" id="sugTable"><thead><tr><th>From</th><th>To</th><th class="num">Implied rate</th><th></th></tr></thead><tbody></tbody></table></div></div>
            </div>
        </div>
    </div></div></div>
</div>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
const B = window.BRC, $ = id => document.getElementById(id);
let SUG = [];
async function load() {
    const j = await B.get('api/rates', { from: $('fFrom').value, to: $('fTo').value });
    if (j.status !== 200) return B.done(j);
    $('rateTable').querySelector('tbody').innerHTML = (j.rates || []).map(r => '<tr><td class="mono small2">' + B.esc(r.rate_date) + '</td><td>' + B.esc(r.currency) + '</td><td class="num">' + B.esc(Number(r.rate).toFixed(4)) + '</td><td class="small2">' + B.esc(r.source) + (r.note ? '<div class="muted">' + B.esc(r.note) + '</div>' : '') + '</td><td class="small2">' + B.esc(r.updated_by || r.created_by) + '<div class="muted">' + B.esc((r.updated_at || r.created_at || '').slice(0, 16)) + '</div></td><td>' + (B.access.prepare ? '<button class="btn btn-sm btn-light py-0" onclick=\'editRate(' + JSON.stringify(r) + ')\'>Change</button>' : '') + '</td></tr>').join('') || '<tr><td colspan="6" class="muted p-2">No rate entered for this period.</td></tr>';
    $('sysNote').innerHTML = (j.system_usd || []).length ? 'Where no USD rate is entered here, the system\'s daily USD rate is used: ' + j.system_usd.slice(0, 8).map(r => B.esc(r.rate_date) + ' ' + B.esc(r.rate)).join(', ') + (j.system_usd.length > 8 ? '…' : '') : 'No system daily USD rate in this period.';
    $('trTable').querySelector('tbody').innerHTML = (j.transfers || []).map(t => '<tr><td class="mono small2">' + B.esc(t.transfer_date) + '</td><td class="small2"><b>' + B.esc(t.from_label) + '</b><div>' + B.money(t.from_amount, t.from_currency) + '</div><div class="muted">' + B.esc(t.from_desc) + '</div></td><td class="small2"><b>' + B.esc(t.to_label) + '</b><div>' + B.money(t.to_amount, t.to_currency) + '</div><div class="muted">' + B.esc(t.to_desc) + '</div></td>'
        + '<td class="num">' + B.esc(Number(t.implied_rate).toFixed(4)) + '</td><td class="num">' + (t.daily_rate ? B.esc(Number(t.daily_rate).toFixed(4)) + '<div class="small2 muted">' + B.esc(t.daily_rate_source) + '</div>' : '<span class="warn small2">no rate</span>') + '</td><td class="num">' + B.money(t.rate_difference_lkr) + '</td>'
        + '<td class="small2">from: ' + B.pill(t.from_state, t.from_state) + '<br>to: ' + B.pill(t.to_state, t.to_state) + '</td><td>' + B.pill(t.status, t.status) + (B.access.prepare && t.status === 'active' ? '<br><button class="btn btn-sm btn-light py-0 mt-1" onclick="undoTr(' + t.id + ')">Undo</button>' : '') + '</td></tr>').join('') || '<tr><td colspan="8" class="muted p-2">No transfer recorded in this period.</td></tr>';
    SUG = j.transfer_suggestions || [];
    $('sugCard').style.display = SUG.length ? '' : 'none';
    $('sugTable').querySelector('tbody').innerHTML = SUG.map((s, i) => '<tr><td class="small2"><b>' + B.esc(s.from.account) + '</b> ' + B.esc(s.from.date) + '<div>' + B.money(s.from.amount, s.from.currency) + ' — ' + B.esc(s.from.description) + '</div></td><td class="small2"><b>' + B.esc(s.to.account) + '</b> ' + B.esc(s.to.date) + '<div>' + B.money(s.to.amount, s.to.currency) + ' — ' + B.esc(s.to.description) + '</div></td><td class="num">' + B.esc(Number(s.implied_rate).toFixed(4)) + '</td><td><button class="btn btn-sm btn-op py-0" onclick="acceptTr(' + i + ')">Record</button></td></tr>').join('');
}
async function editRate(r) {
    r = r || {};
    const v = await B.ask(r.id ? 'Change the rate' : 'Enter a daily rate', [{ name: 'currency', label: 'Currency', type: 'select', value: r.currency || 'USD', options: [['USD', 'USD'], ['JPY', 'JPY']] }, { name: 'rate_date', label: 'Date', type: 'date', value: r.rate_date || B.today(), required: true },
        { name: 'rate', label: 'LKR per one unit', value: r.rate ? Number(r.rate) : '', required: true }, { name: 'source', label: 'Source (e.g. bank buying rate on the statement, Central Bank)', value: r.source || '', required: true }, { name: 'note', label: r.id ? 'Reason for the change' : 'Note (optional)', min: r.id ? 5 : 0 }]);
    if (!v) return; if (r.id) v.version = r.version || 0; B.done(await B.post('rate/save', v), load);
}
async function acceptTr(i) { const s = SUG[i]; const v = await B.ask('Record this transfer', [{ name: 'note', label: 'Note (optional)' }], 'Record'); if (!v) return; B.done(await B.post('transfer/create', { from_line: s.from.line_id, to_line: s.to.line_id, note: v.note }), load); }
async function undoTr(id) { const v = await B.ask('Undo transfer ' + id, [{ name: 'reason', label: 'Reason', min: 5 }], 'Undo'); if (!v) return; B.done(await B.post('transfer/undo', { id, reason: v.reason }), load); }
(function () { const d = new Date(); $('fTo').value = d.toISOString().slice(0, 10); d.setDate(d.getDate() - 60); $('fFrom').value = d.toISOString().slice(0, 10); <?php if (!empty($brc_status['ready'])): ?>load();<?php endif; ?> })();
</script>
</body>
