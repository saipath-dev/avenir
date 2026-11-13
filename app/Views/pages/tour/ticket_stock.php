<?= view('layouts/head') ?>
<style>
    /* ---------- Ticket Stock (same tokens as the Tour Inquiry panel) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card + .tf-card { margin-top:16px; }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:860px; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .btn-primary { background:var(--tf-navy); border-color:var(--tf-navy); }
    .tf .btn-primary:hover { background:var(--tf-navy-2); border-color:var(--tf-navy-2); }
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .btn-light:hover { background:var(--tf-bg); }
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl td { padding:9px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr:last-child td { border-bottom:0; }
    .tf .tbl tr.off td { opacity:.55; }
    .tf .tbl tr.sel td { background:var(--tf-blue-soft); }
    .tf .tbl tbody tr:hover { background:var(--tf-bg); }
    .tf .tbl tr[data-pick] { cursor:pointer; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; margin:1px 2px 1px 0; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .pill-teal { background:var(--tf-teal-soft); color:var(--tf-teal); }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .counts { display:flex; gap:14px; flex-wrap:wrap; }
    .tf .counts .c { min-width:88px; padding:8px 12px; border:1px solid var(--tf-line); border-radius:8px; background:#fff; }
    .tf .counts .c b { display:block; font-size:20px; line-height:1.1; }
    .tf .counts .c span { font-size:11px; color:var(--tf-muted); text-transform:uppercase; letter-spacing:.04em; }
    .tf .filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .tf .filters .form-select, .tf .filters .form-control { width:auto; }
    .tf .nums { max-height:420px; overflow:auto; }
    .tf .picker { max-height:220px; overflow:auto; border:1px solid var(--tf-line); border-radius:8px; }
    .tf .picker .it { padding:6px 10px; border-bottom:1px solid var(--tf-line); cursor:pointer; font-size:12.5px; display:flex; justify-content:space-between; gap:8px; }
    .tf .picker .it:hover { background:var(--tf-bg); }
    .tf .picker .it.on { opacity:.5; cursor:default; }
    .tf .picker .it.sel { background:var(--tf-blue-soft); }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar', ['tourdesk_desk_only' => 'reservation']) /* tf-resdesk-r1: the Hotel Reservation Desk strip */ ?>

                <div class="tf-title">
                    <div>
                        <h4>Ticket Stock <span class="help">· entrance tickets under control</span></h4>
                        <div class="sub">The entrance tickets the company buys in advance and holds — Sigiriya, Polonnaruwa and the others — one numbered ticket per row, each <b>available</b>, <b>allocated</b> to one tour, <b>used</b>, or <b>void</b>. Put an attraction under control here, receive each batch by its numbers, and the Activities desk allocates tickets to a tour by its party size: one number is never on two tours, and the remaining stock is always what is counted here. An attraction paid at the counter on the day is marked <b>pay at gate</b> instead, and its lines take that status — never “nothing to arrange”. An attraction nobody has put under control is handled on the desk exactly as before.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                    <div class="not-installed">Ticket stock control is not installed on this site yet. Open <b>Inquiries</b> as the administrator and press <b>Install now</b> (schema 37); nothing else changes.<?= !empty($error) ? ' <span class="mono">' . esc($error) . '</span>' : '' ?></div>
                <?php else: ?>
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>Attractions under control</h6><span class="help" id="a_count"></span></div>
                            <div class="tf-card-b" style="padding:0">
                                <table class="tbl"><thead><tr><th>Attraction</th><th>How</th><th>Available</th><th>Allocated</th><th>Used</th></tr></thead>
                                <tbody id="a_rows"><tr><td colspan="5" class="empty" style="padding:12px">Loading…</td></tr></tbody></table>
                            </div>
                        </div>
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>Put an attraction under control</h6><span class="help">from the Entrance Fee master</span></div>
                            <div class="tf-card-b">
                                <input class="form-control form-control-sm mb-2" id="p_q" placeholder="Find the attraction on the Entrance Fee master…">
                                <div class="picker" id="p_list"></div>
                                <div class="row g-2 mt-1">
                                    <div class="col-7"><label class="form-label mb-1">How the entrance is handled</label>
                                        <select class="form-select form-select-sm" id="p_control"><option value="stock">Tickets from stock (numbered, held in advance)</option><option value="pay_at_gate">Pay at gate (paid at the counter on the day)</option></select></div>
                                    <div class="col-5"><label class="form-label mb-1">Low-stock mark</label><input class="form-control form-control-sm" id="p_low" type="number" min="0" placeholder="e.g. 10"></div>
                                    <div class="col-12"><input class="form-control form-control-sm" id="p_note" placeholder="Note (optional)"></div>
                                </div>
                                <div class="mt-2 d-flex gap-2 align-items-center"><button class="btn btn-primary btn-sm" id="p_add" disabled>Put under control</button><span class="help" id="p_sel">Pick an attraction above.</span></div>
                                <div class="help mt-2">Nothing is assumed: the entrance master's "direct payment" flag is shown as a hint only — you decide. The name and code are copied from the master as it stands.</div>
                            </div>
                        </div>
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>Tours holding tickets</h6><span class="help" id="h_count"></span></div>
                            <div class="tf-card-b" style="padding:0">
                                <table class="tbl"><thead><tr><th>Tour</th><th>Tickets</th><th></th></tr></thead>
                                <tbody id="h_rows"><tr><td colspan="3" class="empty" style="padding:12px">Loading…</td></tr></tbody></table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="tf-card" id="d_card" hidden>
                            <div class="tf-card-h"><h6 id="d_title">—</h6><div class="d-flex gap-2 align-items-center"><span id="d_pills"></span><button class="btn btn-light btn-sm" id="d_edit">Settings…</button></div></div>
                            <div class="tf-card-b">
                                <div class="counts mb-3" id="d_counts"></div>
                                <div id="d_settings" hidden class="mb-3" style="border:1px solid var(--tf-line);border-radius:8px;padding:10px">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-4"><label class="form-label mb-1">How the entrance is handled</label><select class="form-select form-select-sm" id="s_control"><option value="stock">Tickets from stock</option><option value="pay_at_gate">Pay at gate</option></select></div>
                                        <div class="col-md-2"><label class="form-label mb-1">Low-stock mark</label><input class="form-control form-control-sm" id="s_low" type="number" min="0"></div>
                                        <div class="col-md-2"><label class="form-label mb-1">Cost each</label><input class="form-control form-control-sm" id="s_cost" type="number" step="0.01" min="0"></div>
                                        <div class="col-md-2"><label class="form-label mb-1">Currency</label><select class="form-select form-select-sm" id="s_cur"><option value="">—</option><option>LKR</option><option>USD</option></select></div>
                                        <div class="col-md-2"><label class="form-label mb-1">On the list</label><select class="form-select form-select-sm" id="s_active"><option value="1">Yes</option><option value="0">No (kept)</option></select></div>
                                        <div class="col-12"><input class="form-control form-control-sm" id="s_note" placeholder="Note"></div>
                                        <div class="col-12 d-flex gap-2"><button class="btn btn-primary btn-sm" id="s_save">Save settings</button><button class="btn btn-light btn-sm" id="s_cancel">Cancel</button></div>
                                    </div>
                                </div>
                                <div id="d_receive" class="mb-3" style="border:1px solid var(--tf-line);border-radius:8px;padding:10px">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><b style="font-size:13px"><?= !empty($purchase) ? 'Receive a purchase — a new batch, or a top-up' : 'Receive tickets' ?></b><span class="help">a batch by its printed numbers — a range, or a list<?= !empty($purchase) ? '; earlier batches are never changed' : '' ?></span></div>
                                    <?php if (!empty($purchase)): ?>
                                    <div class="row g-2 align-items-end mt-1">
                                        <div class="col-md-3"><label class="form-label mb-1">Purchased / paid on</label><input class="form-control form-control-sm" id="r_paid_on" type="date"></div>
                                        <div class="col-md-3"><label class="form-label mb-1">Amount paid (whole batch)</label><input class="form-control form-control-sm mono" id="r_amount" type="number" step="0.01" min="0" placeholder="e.g. 70000"></div>
                                        <div class="col-md-2"><label class="form-label mb-1">Currency</label><select class="form-select form-select-sm" id="r_cur"><option value="">—</option><option>LKR</option><option>USD</option></select></div>
                                        <div class="col-md-2"><label class="form-label mb-1">Quantity purchased</label><input class="form-control form-control-sm mono" id="r_qty" type="number" step="1" min="1" placeholder="e.g. 10"></div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="row g-2 align-items-end mt-1">
                                        <div class="col-md-2"><label class="form-label mb-1">Prefix</label><input class="form-control form-control-sm" id="r_prefix" placeholder="e.g. SG-"></div>
                                        <div class="col-md-2"><label class="form-label mb-1">First number</label><input class="form-control form-control-sm mono" id="r_from" placeholder="100001"></div>
                                        <div class="col-md-2"><label class="form-label mb-1">Last number</label><input class="form-control form-control-sm mono" id="r_to" placeholder="100050"></div>
                                        <div class="col-md-2"><label class="form-label mb-1">Received on</label><input class="form-control form-control-sm" id="r_on" type="date"></div>
                                        <div class="col-md-2"><label class="form-label mb-1">Cost each</label><input class="form-control form-control-sm" id="r_cost" type="number" step="0.01" min="0" placeholder="optional"></div>
                                        <div class="col-md-2"><label class="form-label mb-1">Receipt / ref</label><input class="form-control form-control-sm" id="r_ref" placeholder="optional"></div>
                                        <div class="col-md-8"><input class="form-control form-control-sm" id="r_numbers" placeholder="…or a list of numbers, separated by commas or lines (used instead of the range)"></div>
                                        <div class="col-md-4 d-flex gap-2 align-items-center"><button class="btn btn-primary btn-sm" id="r_go">Receive</button><span class="help" id="r_preview"></span></div>
                                    </div>
                                    <div class="help mt-1">Zero padding is kept as typed (000101 to 000150 gives 000101 … 000150). A number already on file for this attraction refuses the whole batch, so nothing is counted twice.<?= !empty($purchase) ? ' The quantity purchased must agree with the numbers, or nothing is received. A later purchase is received the same way, as its own batch: the earlier ones, what was paid for them and every ticket\'s history stay as they are.' : '' ?></div>
                                </div>
                                <div class="filters mb-2">
                                    <select class="form-select form-select-sm" id="t_status"><option value="">All tickets</option><option value="available">Available</option><option value="allocated">Allocated</option><option value="used">Used</option><option value="void">Void</option></select>
                                    <input class="form-control form-control-sm" id="t_q" placeholder="Number or tour no." style="width:200px">
                                    <span class="help" id="t_count"></span>
                                </div>
                                <div class="nums"><table class="tbl"><thead><tr><th>Number</th><th>Status</th><th>Tour · line</th><th>When · who</th><th></th></tr></thead><tbody id="t_rows"></tbody></table></div>
                                <div class="mt-3"><b style="font-size:13px"><?= !empty($purchase) ? 'Purchases — every batch received, newest first' : 'Batches received' ?></b><table class="tbl mt-1"><thead><tr><?= !empty($purchase) ? '<th>Bought / paid</th><th>Amount paid</th>' : '' ?><th>Received</th><th>Numbers</th><th>Count</th><th>Available</th><th>Allocated</th><th>Used</th><th>Void</th><th>Ref · note</th></tr></thead><tbody id="b_rows"></tbody></table></div>
                            </div>
                        </div>
                        <div class="tf-card" id="d_none"><div class="tf-card-b empty">Pick an attraction on the left to see its tickets, receive a batch or change how it is handled.</div></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'tour/ticket_stock') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    // tf-tickets-r2 (schema 38): each batch is a purchase — date bought / paid, amount paid, quantity
    const PURCHASE = <?= !empty($purchase) ? 'true' : 'false' ?>;
    const money = (cur, v) => (cur && cur !== '—' ? cur + ' ' : '') + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2600 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const $ = id => document.getElementById(id);
    const when = s => s ? String(s).slice(0, 16) : '';
    const CONTROL = { stock: ['from stock', 'pill-blue'], pay_at_gate: ['pay at gate', 'pill-amber'] };
    const TSTATUS = { available: ['available', 'pill-green'], allocated: ['allocated', 'pill-blue'], used: ['used', 'pill-grey'], void: ['void', 'pill-red'] };
    const pill = (map, k) => { const [l, c] = map[k] || [k, 'pill-grey']; return `<span class="pill ${c}">${esc(l)}</span>`; };
    let ATT = [], FEES = [], HOLD = [], CUR = null, PICK = null, DETAIL = null;

    function renderAtt() {
        $('a_count').textContent = ATT.length ? ATT.length + ' under control' : '';
        $('a_rows').innerHTML = ATT.length ? ATT.map(a => `<tr data-pick="${a.id}" class="${a.active ? '' : 'off'} ${CUR && CUR.id === a.id ? 'sel' : ''}">
            <td><b>${esc(a.name)}</b><div class="help">${esc(a.fee_code || '')}${a.active ? '' : ' · off the list'}</div></td>
            <td>${pill(CONTROL, a.control)}${a.low ? '<span class="pill pill-red">low</span>' : ''}${a.empty ? '<span class="pill pill-red">none left</span>' : ''}</td>
            <td class="mono">${a.control === 'stock' ? a.n_available : '—'}</td><td class="mono">${a.control === 'stock' ? a.n_allocated : '—'}</td><td class="mono">${a.control === 'stock' ? a.n_used : '—'}</td></tr>`).join('')
            : '<tr><td colspan="5" class="empty" style="padding:12px">No attraction is under control yet. Put the first one under control below — Sigiriya, say — and receive its tickets.</td></tr>';
        $('a_rows').querySelectorAll('[data-pick]').forEach(tr => tr.addEventListener('click', () => open(Number(tr.dataset.pick))));
    }
    function renderPicker() {
        const q = $('p_q').value.trim().toLowerCase();
        const list = FEES.filter(f => !q || f.name.toLowerCase().includes(q) || (f.code || '').toLowerCase().includes(q)).slice(0, 80);
        // schema 39: the places of the Place to Visit / Entrance Fee Master (keyed "p<id>"), with the master's rate today
        $('p_list').innerHTML = list.map(f => `<div class="it ${f.attraction_id ? 'on' : ''} ${PICK === String(f.fee_id) ? 'sel' : ''}" data-fee="${esc(String(f.fee_id))}"><span><b>${esc(f.name)}</b> <span class="help">${esc(f.code || '')}${f.rate_text ? ' · ' + esc(f.rate_text) : (f.fee_usd != null ? ' · USD ' + Number(f.fee_usd).toFixed(2) : '')}${f.direct_payment ? ' · master says direct payment' : ''}</span></span><span class="help">${f.attraction_id ? 'under control' : ''}</span></div>`).join('') || `<div class="empty" style="padding:10px">Nothing on the ${FEES.some(f => f.place_id) ? 'Place to Visit / Entrance Fee Master' : 'Entrance Fee master'} matches.</div>`;
        $('p_list').querySelectorAll('.it:not(.on)').forEach(el => el.addEventListener('click', () => {
            PICK = String(el.dataset.fee); const f = FEES.find(x => String(x.fee_id) === PICK);
            $('p_sel').textContent = f ? f.name + (f.direct_payment ? ' — the master flags direct payment; choose pay at gate if that is right' : '') : '';
            $('p_add').disabled = !f; if (f && f.direct_payment) $('p_control').value = 'pay_at_gate';
            renderPicker();
        }));
    }
    function renderHold() {
        $('h_count').textContent = HOLD.length ? HOLD.length + ' tour(s)' : '';
        $('h_rows').innerHTML = HOLD.length ? HOLD.map(t => `<tr><td><b>${esc(t.tour_no || ('tour ' + t.tour_id))}</b><div class="help">${esc(t.tour_name || '')} · ${esc((t.arrival_date || '').slice(0, 10))}${t.over ? ' · <span class="pill pill-amber">tour over</span>' : ''}</div></td>
            <td>${t.lines.map(l => `<div><b>${esc(l.attraction)}</b> <span class="mono">${esc(l.numbers)}</span> <span class="help">(${l.count})</span></div>`).join('')}</td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-used="${t.tour_id}" title="Every allocated ticket of this tour was used at the gates">Mark used</button></td></tr>`).join('')
            : '<tr><td colspan="3" class="empty" style="padding:12px">No tour holds allocated tickets.</td></tr>';
        $('h_rows').querySelectorAll('[data-used]').forEach(b => b.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'Mark this tour\'s tickets used?', text: 'Every ticket allocated to the tour is marked used — the tour has run and the tickets were handed in at the gates. Nothing is deleted.', showCancelButton: true, confirmButtonText: 'Mark used' });
            if (!r.isConfirmed) return;
            const j = await post('/tour/' + b.dataset.used + '/used', {});
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load(true);
        }));
    }
    async function open(id) {
        const j = await api('/attraction/' + id + '?status=' + encodeURIComponent($('t_status').value) + '&q=' + encodeURIComponent($('t_q').value));
        if (j.status !== 200) { toast('error', j.message); return; }
        DETAIL = j.data; CUR = DETAIL.attraction; renderAtt(); renderDetail();
    }
    function renderDetail() {
        const a = CUR, d = DETAIL;
        $('d_none').hidden = true; $('d_card').hidden = false;
        $('d_title').textContent = a.name + (a.fee_code ? ' · ' + a.fee_code : '');
        $('d_pills').innerHTML = pill(CONTROL, a.control) + (a.active ? '' : '<span class="pill pill-grey">off the list</span>') + (a.low ? '<span class="pill pill-red">low stock</span>' : '') + (a.empty ? '<span class="pill pill-red">none left</span>' : '');
        const paidLine = PURCHASE && a.n_batches ? ` · ${a.n_batches} batch${a.n_batches === 1 ? '' : 'es'}${Object.keys(a.paid || {}).length ? ' · paid ' + Object.entries(a.paid).map(([c, v]) => money(c, v)).join(' + ') : ''}${a.n_unpaid ? ` · <span class="pill pill-amber">${a.n_unpaid} batch${a.n_unpaid === 1 ? '' : 'es'} without an amount paid</span>` : ''}` : '';
        $('d_counts').innerHTML = a.control === 'stock'
            ? `${PURCHASE ? `<div class="w-100 help" id="d_balance" style="font-size:13px;color:var(--tf-ink)">Purchased <b>${a.n_received}</b> · allocated / used <b>${a.n_allocated + a.n_used}</b> · void <b>${a.n_void}</b> · available <b>${a.n_available}</b>${paidLine}</div><div class="c"><b>${a.n_received}</b><span>received</span></div>` : ''}<div class="c"><b>${a.n_available}</b><span>available</span></div><div class="c"><b>${a.n_allocated}</b><span>allocated</span></div><div class="c"><b>${a.n_used}</b><span>used</span></div><div class="c"><b>${a.n_void}</b><span>void</span></div><div class="c"><b class="mono" style="font-size:15px">${a.next_no != null ? esc(String(a.next_no)) : '—'}</b><span>next number</span></div>${a.low_mark != null ? `<div class="c"><b>${a.low_mark}</b><span>low mark</span></div>` : ''}`
            : `<div class="help">Paid at the gate on the day. The Activities desk marks each line for ${esc(a.name)} "pay at gate" with the amount the quotation carried; there is no stock to count.${a.note ? '<br>' + esc(a.note) : ''}</div>`;
        $('d_receive').hidden = a.control !== 'stock' || !a.active;
        // the currency of a new purchase starts as the attraction's own setting; the person can change it before Receive
        if (PURCHASE && $('r_cur').dataset.att !== String(a.id)) { $('r_cur').value = a.currency || ''; $('r_cur').dataset.att = String(a.id); }
        $('d_settings').hidden = true;
        $('t_count').textContent = d.tickets.length ? d.tickets.length + ' shown' : '';
        $('t_rows').innerHTML = d.tickets.length ? d.tickets.map(t => `<tr><td class="mono"><b>${esc(t.ticket_no)}</b></td><td>${pill(TSTATUS, t.status)}${t.status === 'void' && t.void_note ? `<div class="help">${esc(t.void_note)}</div>` : ''}</td>
            <td>${t.tour_no ? `<b>${esc(t.tour_no)}</b><div class="help">${esc(t.line_title || '')}${t.line_date ? ' · ' + esc(t.line_date) : ''}</div>` : ''}</td>
            <td class="help">${t.status === 'allocated' ? when(t.allocated_at) + ' · ' + esc(t.allocated_by || '') : (t.status === 'used' ? when(t.used_at) + ' · ' + esc(t.used_by || '') : (t.status === 'void' ? when(t.void_at) + ' · ' + esc(t.void_by || '') : ''))}</td>
            <td style="white-space:nowrap">${t.status === 'available' ? `<button class="btn btn-light btn-sm" data-void="${t.id}">Void…</button>` : (t.status === 'void' ? `<button class="btn btn-light btn-sm" data-unvoid="${t.id}">Back to stock</button>` : '')}</td></tr>`).join('')
            : `<tr><td colspan="5" class="empty">${a.control === 'stock' ? 'No tickets on file for this filter.' : ''}</td></tr>`;
        $('t_rows').querySelectorAll('[data-void]').forEach(b => b.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'Void this ticket?', text: 'Are you sure? The number is kept on file as void, with the reason; it is never deleted and never allocated.', input: 'text', inputPlaceholder: 'Why (lost, damaged, returned to the authority …) — required', showCancelButton: true, confirmButtonText: 'Void' });
            if (!r.isConfirmed) return;
            const j = await post('/ticket/' + b.dataset.void + '/void', { note: r.value || '' });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load(true);
        }));
        $('t_rows').querySelectorAll('[data-unvoid]').forEach(b => b.addEventListener('click', async () => {
            const j = await post('/ticket/' + b.dataset.unvoid + '/unvoid', {});
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load(true);
        }));
        $('b_rows').innerHTML = d.batches.length ? d.batches.map(b => `<tr>${PURCHASE ? `<td>${b.paid_on ? esc(b.paid_on) : '<span class="pill pill-amber">not recorded</span>'}</td><td class="mono">${b.amount_paid != null ? esc(money(b.currency || '', b.amount_paid)) : '<span class="pill pill-amber">not recorded</span>'}</td>` : ''}<td>${esc(b.received_on)}<div class="help">${esc(b.created_by || '')}</div></td><td class="mono">${esc(b.from_no)}${b.to_no !== b.from_no ? ' – ' + esc(b.to_no) : ''}</td><td class="mono">${b.count}</td><td class="mono">${b.n_available || 0}</td><td class="mono">${b.n_allocated || 0}</td><td class="mono">${b.n_used || 0}</td><td class="mono">${b.n_void || 0}</td><td class="help">${esc(b.ref || '')}${b.note ? (b.ref ? ' · ' : '') + esc(b.note) : ''}${b.unit_cost != null ? ' · ' + esc(b.currency || '') + ' ' + Number(b.unit_cost).toFixed(2) + ' each' : ''}</td></tr>`).join('') : `<tr><td colspan="${PURCHASE ? 10 : 8}" class="empty">No batch received yet.</td></tr>`;
    }
    function preview() {
        const list = $('r_numbers').value.trim();
        if (list) { const n = list.split(/[\s,;]+/).filter(Boolean).length; $('r_preview').textContent = n + ' number(s) listed' + qtyNote(n); return; }
        const f = $('r_from').value.trim(), t = $('r_to').value.trim() || f;
        if (!/^\d+$/.test(f) || !/^\d+$/.test(t) || Number(t) < Number(f)) { $('r_preview').textContent = ''; return; }
        const p = $('r_prefix').value.trim().toUpperCase(), w = f.length;
        $('r_preview').textContent = (Number(t) - Number(f) + 1) + ' tickets: ' + p + f + ' … ' + p + String(t).padStart(w, '0') + qtyNote(Number(t) - Number(f) + 1);
    }
    // the quantity purchased against the numbers typed — the server refuses a batch where they disagree
    function qtyNote(n) {
        if (!PURCHASE || !$('r_qty').value) return '';
        return Number($('r_qty').value) === n ? ' — agrees with the quantity purchased' : ` — the quantity purchased is ${Number($('r_qty').value)}: check`;
    }
    async function load(keep) {
        const j = await api('/data');
        if (j.status !== 200) { toast('error', j.message); return; }
        ATT = j.data.attractions || []; FEES = j.data.fees || []; HOLD = j.data.holding || [];
        renderAtt(); renderPicker(); renderHold();
        if (keep && CUR) open(CUR.id);
    }
    $('p_q').addEventListener('input', renderPicker);
    $('p_add').addEventListener('click', async () => {
        if (!PICK) return;
        const j = await post('/attraction', Object.assign(PICK.startsWith('p') ? { place_id: Number(PICK.slice(1)) } : { fee_id: Number(PICK) }, { control: $('p_control').value, low_mark: $('p_low').value, note: $('p_note').value }));
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); PICK = null; $('p_sel').textContent = 'Pick an attraction above.'; $('p_add').disabled = true; $('p_low').value = ''; $('p_note').value = '';
        await load(); open(j.data.id);
    });
    $('d_edit').addEventListener('click', () => {
        const a = CUR; $('d_settings').hidden = !$('d_settings').hidden;
        $('s_control').value = a.control; $('s_low').value = a.low_mark ?? ''; $('s_cost').value = a.unit_cost ?? ''; $('s_cur').value = a.currency || ''; $('s_active').value = a.active ? '1' : '0'; $('s_note').value = a.note || '';
    });
    $('s_cancel').addEventListener('click', () => { $('d_settings').hidden = true; });
    $('s_save').addEventListener('click', async () => {
        const j = await post('/attraction/' + CUR.id + '/update', { control: $('s_control').value, low_mark: $('s_low').value, unit_cost: $('s_cost').value, currency: $('s_cur').value, active: $('s_active').value === '1', note: $('s_note').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); load(true);
    });
    ['r_prefix', 'r_from', 'r_to', 'r_numbers'].concat(PURCHASE ? ['r_qty'] : []).forEach(id => $(id).addEventListener('input', preview));
    $('r_go').addEventListener('click', async () => {
        const body = { prefix: $('r_prefix').value, from_no: $('r_from').value, to_no: $('r_to').value, numbers: $('r_numbers').value, received_on: $('r_on').value, unit_cost: $('r_cost').value, ref: $('r_ref').value };
        if (PURCHASE) Object.assign(body, { quantity: $('r_qty').value, paid_on: $('r_paid_on').value, amount_paid: $('r_amount').value, currency: $('r_cur').value });
        const j = await post('/attraction/' + CUR.id + '/receive', body);
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); ['r_from', 'r_to', 'r_numbers', 'r_ref'].concat(PURCHASE ? ['r_qty', 'r_paid_on', 'r_amount'] : []).forEach(id => $(id).value = ''); preview(); load(true);
    });
    $('t_status').addEventListener('change', () => CUR && open(CUR.id));
    $('t_q').addEventListener('input', () => CUR && open(CUR.id));
    load();
})();
</script>
</body>
</html>
