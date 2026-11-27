<?= view('layouts/head') ?>
<style>
    /* ---------- Safari Rate Master (tf-safarirate-r1) — Avenir purple headers, green actions ---------- */
    .sr { --p:#842484; --p-dark:#3f1a44; --p-soft:#f6eaf5; --g:#78b848; --g-2:#5f9a36; --g-soft:#eef7e6; --amber:#8a5a12; --amber-soft:#fff3df;
          --ink:#1d2733; --muted:#5f6f80; --line:#e4dde6; --bg:#f7f5f8; color:var(--ink); }
    .sr .sr-top { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:10px; margin-bottom:12px; }
    .sr .crumb { color:var(--muted); font-size:13px; }
    .sr h3.title { color:var(--p-dark); font-weight:800; margin:2px 0 0; font-size:30px; letter-spacing:-.01em; }
    .sr .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .sr .tabs button { border:1px solid var(--line); background:#fff; color:var(--p-dark); font-weight:600; border-radius:10px; padding:8px 26px; }
    .sr .tabs button.on { background:var(--p-dark); border-color:var(--p-dark); color:#fff; }
    .sr .parkline { color:var(--muted); font-size:15px; }
    .sr hr.sep { border-color:var(--line); opacity:1; margin:10px 0 16px; }
    .sr .card2 { background:#fff; border:1px solid var(--line); border-radius:14px; margin-bottom:16px; overflow:hidden; }
    .sr .card2 .h { background:var(--p); color:#fff; padding:10px 18px; display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; }
    .sr .card2 .h b { font-size:15.5px; font-weight:700; }
    .sr .card2 .h span { font-size:12px; opacity:.9; }
    .sr .card2 .b { padding:14px 18px; }
    .sr label.l { display:block; font-size:12px; color:var(--muted); margin-bottom:3px; }
    .sr .form-control, .sr .form-select { border-color:#d7cbd9; }
    .sr .form-control:focus, .sr .form-select:focus { border-color:var(--p); box-shadow:0 0 0 3px rgba(132,36,132,.12); }
    .sr table.rates { width:100%; border-collapse:separate; border-spacing:0 6px; }
    .sr table.rates th { background:var(--p-soft); color:var(--p-dark); font-size:13px; font-weight:600; padding:8px 10px; }
    .sr table.rates th:first-child { border-radius:8px 0 0 8px; } .sr table.rates th:last-child { border-radius:0 8px 8px 0; }
    .sr table.rates td { padding:2px 10px; font-size:14px; }
    .sr table.rates input { max-width:150px; }
    .sr .note { font-size:12.5px; color:var(--muted); }
    .sr .badge-ev { font-size:12px; font-weight:600; padding:3px 10px; border-radius:20px; }
    .sr .ev-awaiting { background:var(--amber-soft); color:var(--amber); }
    .sr .ev-confirmed { background:var(--g-soft); color:var(--g-2); }
    .sr .quote-card { background:var(--p-dark); color:#fff; border-radius:14px; padding:18px 22px; margin-bottom:16px; }
    .sr .quote-card small { letter-spacing:.06em; font-weight:700; opacity:.8; font-size:11px; }
    .sr .quote-card .nm { font-size:24px; font-weight:700; margin-top:4px; }
    .sr .quote-card .pr { font-size:32px; font-weight:800; }
    .sr .quote-card .pr span { font-size:15px; font-weight:500; margin-left:12px; }
    .sr .quote-card .ln { margin-top:8px; font-size:14.5px; opacity:.95; }
    .sr .prev-row { display:flex; justify-content:space-between; padding:5px 0; font-size:14px; }
    .sr .prev-row.tot { font-weight:700; }
    .sr .margin { border-radius:10px; padding:9px 14px; display:flex; justify-content:space-between; font-weight:700; margin-top:8px; }
    .sr .margin.ok { background:var(--g-soft); color:var(--g-2); }
    .sr .margin.short { background:var(--amber-soft); color:var(--amber); }
    .sr .btn-go { background:var(--g); border-color:var(--g); color:#10230a; font-weight:700; }
    .sr .btn-go:hover { background:var(--g-2); border-color:var(--g-2); color:#fff; }
    .sr .btn-ghost { background:#fff; border:1px solid var(--line); color:var(--p-dark); }
    .sr table.checks { width:100%; font-size:14.5px; }
    .sr table.checks th { background:var(--p-soft); color:var(--p-dark); padding:10px 12px; font-weight:700; }
    .sr table.checks td { padding:10px 12px; border-bottom:1px solid var(--line); }
    .sr table.checks .r { text-align:right; font-variant-numeric:tabular-nums; }
    .sr .short-v { color:var(--amber); font-weight:700; }
    .sr .legacy { background:#fffaf0; border:1px solid #f0dfbf; border-radius:10px; padding:10px 12px; font-size:12.5px; }
    .sr .hist { font-size:12px; color:var(--muted); max-height:180px; overflow:auto; }
    .sr .gaps { background:#fbe9e7; color:#8a2a22; border-radius:10px; padding:9px 12px; font-size:13px; margin-bottom:12px; }
    @media (max-width: 760px) { .sr h3.title { font-size:24px; } .sr table.rates input { max-width:110px; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content sr" id="sr">
            <div class="container-fluid">
                <?= view('layouts/tourdesk_bar', ['tourdesk_active' => $page_url]) ?>

                <div class="sr-top">
                    <div>
                        <div class="crumb">Tour Desk &nbsp;/&nbsp; Masters &nbsp;·&nbsp; <a href="<?= base_url($place_url) ?>" style="color:var(--p)">Places &amp; Entrance Fees</a></div>
                        <h3 class="title">Safari Rate Master</h3>
                    </div>
                </div>

                <?php if (empty($ready)): ?>
                <div class="card2"><div class="b">The Safari Rate Master could not set up its table. Ask the administrator to check the database user can create tables.</div></div>
                <?php else: ?>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="tabs" id="tabs"></div>
                    <div class="parkline" id="parkline"></div>
                </div>
                <hr class="sep">
                <div id="body"><div class="note">Loading…</div></div>
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
    const BASE = '<?= base_url($page_url) ?>';
    if (!document.getElementById('body')) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const usd = v => v == null ? '—' : 'USD ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const usd3 = v => v == null ? '—' : 'USD ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 3 });
    const val = v => v == null ? '' : String(Number(v)).replace(/\.0+$/, '');
    const $ = id => document.getElementById(id);
    const toast = (icon, text) => (window.Swal ? Swal.fire({ icon, text, timer: icon === 'success' ? 2600 : undefined, showConfirmButton: icon !== 'success' }) : alert(text));
    let D = null, TAB = (new URLSearchParams(location.search).get('park') || ''), PREV = 2;

    async function load() {
        const r = await fetch(BASE + '/data', { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(x => x.json()).catch(() => null);
        if (!r || r.status !== 200) { $('body').innerHTML = '<div class="gaps">' + esc(r ? r.message : 'Could not load the Safari Rate Master.') + '</div>'; return; }
        D = r.data;
        if (!TAB || (TAB !== 'checks' && !D.parks.find(p => p.slug === TAB))) TAB = D.parks.length ? D.parks[0].slug : 'checks';
        render();
    }
    function tabs() {
        const b = D.parks.map(p => `<button class="${TAB === p.slug ? 'on' : ''}" data-t="${esc(p.slug)}">${esc(p.label.replace(/ Jeep Safari$/, ''))}</button>`).join('')
            + `<button class="${TAB === 'checks' ? 'on' : ''}" data-t="checks">Cost checks</button>`;
        $('tabs').innerHTML = b;
        $('tabs').querySelectorAll('button').forEach(x => x.addEventListener('click', () => { TAB = x.dataset.t; history.replaceState(null, '', '?park=' + TAB); render(); }));
        const p = D.parks.find(p => p.slug === TAB);
        $('parkline').textContent = p ? p.park + '  /  ' + p.category : '';
    }
    function render() { tabs(); if (TAB === 'checks') { checksView(); } else { parkView(D.parks.find(p => p.slug === TAB)); } }

    // cost of a party of n foreign adults, as the server works it out (SafariRates::cost) — for the live preview
    function costOf(p, n, season) {
        const pre = (season || p.season) === 'regular' ? 'rp_' : 'ts_';
        const a = Number(p[pre + 'adult'] || 0), fx = Number(p.fx_lkr || 0), seats = Math.max(1, Number(p.seats || 6));
        const jeeps = Math.ceil(n / seats);
        const sub = n * a + jeeps * Number(p.service_usd || 0) + (fx > 0 ? jeeps * Number(p.vehicle_lkr || 0) / fx : 0);
        const gate = sub * (1 + Number(p.vat_pct || 0) / 100);
        const hire = fx > 0 ? jeeps * Number(p.hire_lkr || 0) / fx : 0;
        return { jeeps, gate, hire, total: gate + hire };
    }
    function readForm(p) {
        const o = Object.assign({}, p);
        document.querySelectorAll('#body [data-f]').forEach(i => { o[i.dataset.f] = i.value === '' ? null : (i.type === 'number' ? Number(i.value) : i.value); });
        return o;
    }
    function inp(f, v, extra) { return `<input class="form-control form-control-sm" data-f="${f}" type="number" step="0.01" min="0" value="${esc(val(v))}" ${extra || ''}>`; }

    function parkView(p) {
        if (!p) { $('body').innerHTML = '<div class="note">No safari park records.</div>'; return; }
        const rows = Object.keys(D.cats).map(c => `<tr><td>${esc(D.cats[c])}</td><td>${inp('ts_' + c, p['ts_' + c])}</td><td>${inp('rp_' + c, p['rp_' + c])}</td></tr>`).join('');
        const ev = D.evidence[p.evidence] || p.evidence;
        const hist = (p.history || []).map(h => `<div>${esc(h.at)} · ${esc(h.by_user || '')} — ${esc(h.summary)}</div>`).join('') || '<div>No changes yet.</div>';
        $('body').innerHTML = `
        ${p.gaps.length ? `<div class="gaps">Quotations hold this safari until ${esc(p.gaps.join(', '))} is entered.</div>` : ''}
        ${!p.place ? `<div class="gaps">This record is not tied to a safari park in Places &amp; Entrance Fees, so no inquiry is costed from it yet.</div>` : ''}
        <div class="row g-3">
          <div class="col-xl-7">
            <div class="card2"><div class="h"><b>Park entrance rates</b><span>USD per visitor · Gazette 2292/11</span></div><div class="b">
              <table class="rates"><thead><tr><th>Visitor category</th><th>Tourist season</th><th>Regular period</th></tr></thead><tbody>${rows}</tbody></table>
              <div class="row g-2 mt-1">
                <div class="col-sm-4"><label class="l">Free park admission under age</label>${inp('free_under_age', p.free_under_age, 'step="1"')}</div>
                <div class="col-sm-4"><label class="l">Child rate up to age</label>${inp('child_to_age', p.child_to_age, 'step="1"')}</div>
              </div>
              <div class="note mt-2">Under ${esc(val(p.free_under_age))}: free park admission. Count each child when assigning jeep seats. Admission is charged per guest.</div>
            </div></div>
            <div class="card2"><div class="h"><b>Gate charges for a foreign group</b><span>once per jeep</span></div><div class="b"><div class="row g-2">
              <div class="col-sm-4"><label class="l">Service / jeep (USD)</label>${inp('service_usd', p.service_usd)}</div>
              <div class="col-sm-4"><label class="l">Vehicle entry / jeep (LKR)</label>${inp('vehicle_lkr', p.vehicle_lkr)}</div>
              <div class="col-sm-4"><label class="l">VAT on gate subtotal (%)</label>${inp('vat_pct', p.vat_pct)}</div>
            </div><div class="note mt-2">Gate subtotal = guest tickets + service + vehicle entry for each jeep; VAT is added to that subtotal. Jeep hire comes after the gate VAT.</div></div></div>
            <div class="card2"><div class="h"><b>Jeep supplier</b><span class="badge-ev ev-${esc(p.evidence)}">${esc(ev)}</span></div><div class="b"><div class="row g-2">
              <div class="col-sm-5"><label class="l">Operator</label><input class="form-control form-control-sm" data-f="operator" value="${esc(p.operator || '')}"></div>
              <div class="col-sm-4"><label class="l">Hire / jeep (LKR)</label>${inp('hire_lkr', p.hire_lkr)}</div>
              <div class="col-sm-3"><label class="l">Guests / jeep</label>${inp('seats', p.seats, 'step="1" min="1" max="12"')}</div>
              <div class="col-sm-6"><label class="l">Rate source or invoice reference</label><input class="form-control form-control-sm" data-f="source" maxlength="255" value="${esc(p.source || '')}"></div>
              <div class="col-sm-3"><label class="l">Valid from</label><input class="form-control form-control-sm" data-f="valid_from" type="date" value="${esc(p.valid_from || '')}"></div>
              <div class="col-sm-3"><label class="l">Valid to</label><input class="form-control form-control-sm" data-f="valid_to" type="date" value="${esc(p.valid_to || '')}"></div>
              <div class="col-sm-6"><label class="l">Verification</label><select class="form-select form-select-sm" data-f="evidence">${Object.keys(D.evidence).map(k => `<option value="${k}" ${p.evidence === k ? 'selected' : ''}>${esc(D.evidence[k])}</option>`).join('')}</select></div>
            </div>
            <div class="mt-2" style="font-weight:700;color:var(--p-dark)">Supplier costs use one hire charge per jeep.</div>
            <div class="note">Enter contract dates from the supplier agreement. The safari company assigns the driver when it receives the order.</div>
            ${p.legacy_note ? `<div class="legacy mt-2"><b>Reconciliation:</b> ${esc(p.legacy_note)}</div>` : ''}
            </div></div>
          </div>
          <div class="col-xl-5">
            <div class="card2"><div class="h"><b>Quotation settings</b><span>costing only</span></div><div class="b"><div class="row g-2">
              <div class="col-5"><label class="l">LKR per USD</label>${inp('fx_lkr', p.fx_lkr)}</div>
              <div class="col-7"><label class="l">Season used for quotations</label><select class="form-select form-select-sm" data-f="season">${Object.keys(D.seasons).map(k => `<option value="${k}" ${p.season === k ? 'selected' : ''}>${esc(D.seasons[k])}</option>`).join('')}</select></div>
            </div>
            <div class="mt-2" style="font-weight:700;color:var(--p-dark)">No fixed selling price.</div>
            <div class="note">The safari's exact cost for the party goes into the tour cost, and the quotation's markup is added to the total, as for hotels and transport. Fixed quotation FX applies until Avenir revises it. Actual payments use the Daily USD Rate${D.daily_usd ? ' (today ' + esc(Number(D.daily_usd.rate).toFixed(2)) + ', ' + esc(D.daily_usd.rate_date) + ')' : ''}. The Wildlife Department declares the tourist-season dates.</div>
            </div></div>
            <div class="quote-card"><small>CUSTOMER QUOTATION &nbsp;/&nbsp; WHAT THE AGENT SEES</small><div class="nm">${esc(p.label)}</div>
              <div class="ln">Park entrance, taxes and private jeep included.</div>
              <div class="ln" style="opacity:.8">Shown as one line on the day; its price is inside the tour price, which the agent sees per person by party size.</div></div>
            <div class="card2"><div class="h"><b>Internal cost preview</b><span>never shown to the agent</span></div><div class="b">
              <div class="d-flex align-items-center gap-2 mb-2 note"><span id="pv_head"></span><label for="pv_n">Guests:</label><input class="form-control form-control-sm" id="pv_n" type="number" min="1" max="60" step="1" value="${PREV}" style="width:80px"><span>foreign adult(s)</span></div>
              <div id="pv"></div>
              <div class="d-flex gap-2 mt-3 flex-wrap">
                <button class="btn btn-go" id="b_save"><i class="bi bi-check2-circle"></i> Save ${esc(p.label.replace(/ Jeep Safari$/, ''))}</button>
                <button class="btn btn-go" id="b_checks" style="background:#fff;color:var(--g-2)">View cost checks</button>
              </div>
            </div></div>
            <div class="card2"><div class="h"><b>Changes</b><span>${p.updated_by ? 'last by ' + esc(p.updated_by) + ', ' + esc(p.updated_at) : ''}</span></div><div class="b hist">${hist}</div></div>
          </div>
        </div>`;
        const preview = () => {
            const f = readForm(p), n = Math.max(1, Number($('pv_n').value) || 1), c = costOf(f, n);
            $('pv_head').textContent = (D.seasons[f.season] || f.season) + ' · ' + c.jeeps + ' jeep' + (c.jeeps === 1 ? '' : 's') + ' ·';
            $('pv').innerHTML = `<div class="prev-row"><span>Park entrance and charges incl. VAT</span><span>${usd(c.gate)}</span></div>
                <div class="prev-row"><span>Jeep hire (${c.jeeps} × LKR ${Number(f.hire_lkr || 0).toLocaleString('en-US')} ÷ ${val(f.fx_lkr)})</span><span>${usd3(c.hire)}</span></div>
                <div class="prev-row tot"><span>Total internal cost</span><span>${usd(c.total)}</span></div>
                <div class="prev-row tot"><span>Our cost per guest</span><span>${usd(c.total / n)}</span></div>
                ${D.markup_pct != null ? `<div class="margin ok"><span>On a quotation at the default markup (${val(D.markup_pct)}%)</span><span>${usd(c.total / n * (1 + D.markup_pct / 100))} per guest</span></div>` : ''}`;
        };
        $('body').querySelectorAll('[data-f]').forEach(i => i.addEventListener('input', preview));
        $('pv_n').addEventListener('input', () => { PREV = Math.max(1, Number($('pv_n').value) || 1); preview(); });
        preview();
        $('b_checks').addEventListener('click', () => { TAB = 'checks'; history.replaceState(null, '', '?park=checks'); render(); });
        $('b_save').addEventListener('click', async () => {
            const body = { id: p.id };
            document.querySelectorAll('#body [data-f]').forEach(i => { body[i.dataset.f] = i.value; });
            $('b_save').disabled = true;
            const r = await fetch(BASE + '/save', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) }).then(x => x.json()).catch(() => ({ message: 'Not saved: no answer from the server.' }));
            $('b_save').disabled = false;
            if (r.status === 200) { toast('success', r.message); await load(); } else { toast('error', r.message || 'Not saved.'); }
        });
    }

    function checksView() {
        $('parkline').textContent = '';
        const parks = D.parks;
        const sizes = parks.length ? parks[0].checks.map(c => c.guests) : [];
        const mk = D.markup_pct;
        const head = `<tr><th>Guests</th><th>Jeeps</th><th class="r">Gate / person</th>${parks.map(p => `<th class="r">${esc(p.label.replace(/ Jeep Safari$/, ''))} cost / person</th>`).join('')}${mk != null ? parks.map(p => `<th class="r">${esc(p.label.replace(/ Jeep Safari$/, ''))} with ${val(mk)}% markup</th>`).join('') : ''}</tr>`;
        const body = sizes.map((n, i) => {
            const c0 = parks[0].checks[i];
            return `<tr><td><b>${n}</b></td><td>${c0.jeeps}</td><td class="r">${usd(c0.gate_pp_usd)}</td>`
                + parks.map(p => { const c = p.checks[i]; return `<td class="r">${usd(c.cost_pp_usd)}</td>`; }).join('')
                + (mk != null ? parks.map(p => { const c = p.checks[i]; return `<td class="r">${usd(c.cost_pp_usd * (1 + mk / 100))}</td>`; }).join('') : '') + '</tr>';
        }).join('');
        const p0 = parks[0] || {};
        $('body').innerHTML = `
          <div class="card2"><div class="h"><b>Cost checks by party size</b><span>Tourist season · Foreign adults · LKR ${esc(val(p0.fx_lkr))} per USD · ${esc(val(p0.seats))} guest seats per jeep</span></div><div class="b" style="overflow-x:auto">
            <table class="checks"><thead>${head}</thead><tbody>${body}</tbody></table>
            <div class="note mt-2">Seven guests require a second jeep. The service charge, vehicle entry and hire apply once for each jeep used. The cost per person falls as more guests share a jeep. On a quotation the safari's cost joins the tour cost and the inquiry's markup is added to the total${mk != null ? ' (default ' + val(mk) + '%, shown for reference)' : ''}.</div>
          </div></div>
          <div class="row g-3">
            <div class="col-lg-6"><div class="card2"><div class="h"><b>Rate entry and verification</b></div><div class="b note">
              <div style="font-weight:700;color:var(--p-dark)">Official fee schedule</div><div class="mb-2">Gazette 2292/11 (8 Aug 2022), Category I parks. The Wildlife Department declares the tourist-season dates.</div>
              <div style="font-weight:700;color:var(--p-dark)">Supplier evidence</div><div class="mb-2">${parks.map(p => esc(p.label) + ': ' + esc(p.operator || '') + ' LKR ' + Number(p.hire_lkr || 0).toLocaleString('en-US') + ' a jeep — ' + esc(D.evidence[p.evidence] || p.evidence) + (p.source ? ' (' + esc(p.source) + ')' : '')).join('<br>')}</div>
              <div style="font-weight:700;color:var(--p-dark)">If the supplier bundles tickets</div><div>Use the confirmed inclusive amount once for what it covers.</div>
            </div></div></div>
            <div class="col-lg-6"><div class="card2"><div class="h"><b>Quotation behaviour</b></div><div class="b note">
              <div style="font-weight:700;color:var(--p-dark)">Internal calculation</div><div class="mb-2">Total guest tickets + per-jeep gate charges, then VAT. Jeep hire after gate VAT. Rounded after the total.</div>
              <div style="font-weight:700;color:var(--p-dark)">Customer output</div><div class="mb-2">One all-inclusive safari line per park on the quotation screen and in the PDF, with no amount on it. Its exact cost for the party is inside the tour price, which the agent sees per person by party size. Children are costed by their park tickets as the Child Fare Review decides.</div>
              <div style="font-weight:700;color:var(--p-dark)">Existing tour transport</div><div>The safari jeep applies only to the safari excursion. Any transfer or other travel that day stays as planned.</div>
            </div></div></div>
          </div>`;
    }
    load();
})();
</script>
</body>
</html>
