<?= view('layouts/head') ?>
<style>
    /* ---------- Tour Discounts (Pilot) — tf-discount-r2, the approved mock-up ---------- */
    .td { --td-plum:#842484; --td-plum-d:#6b1d6b; --td-plum-soft:#f7edf7; --td-green:#78b848; --td-green-d:#67a23c;
          --td-ink:#241f28; --td-muted:#6b6472; --td-line:#e4dfe8; --td-bg:#faf8fb; --td-card:#fff;
          --td-amber-soft:#ffffff; --td-amber:#1a1a1a; --td-red:#b23a32; --td-red-soft:#fbe9e7;
          color:var(--td-ink); font-size:14px; }
    .td h4, .td h5, .td h6 { color:var(--td-plum); margin:0; font-weight:700; }
    .td .td-head { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:14px; }
    .td .td-head h4 { font-size:26px; letter-spacing:-.01em; }
    .td .td-mode { background:var(--td-amber-soft); border:1px solid #d9d3dd; color:var(--td-amber); border-radius:999px; padding:6px 14px; font-size:12.5px; font-weight:600; }
    .td .card2 { background:var(--td-card); border:1px solid var(--td-line); border-radius:14px; box-shadow:0 1px 3px rgba(36,31,40,.06); overflow:hidden; margin-bottom:16px; }
    .td .card2 > .hd { background:var(--td-plum); color:#fff; padding:13px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .td .card2 > .hd h5 { color:#fff; font-size:15.5px; }
    .td .card2 > .hd .tag { background:rgba(255,255,255,.18); border-radius:999px; padding:4px 12px; font-size:12px; font-weight:600; }
    .td .card2 > .bd { padding:18px; }
    .td .btn-go { background:var(--td-green); border:0; color:#15300a; font-weight:700; border-radius:9px; padding:11px 22px; font-size:14px; }
    .td .btn-go:hover:not(:disabled) { background:var(--td-green-d); color:#0f2407; }
    .td .btn-go:disabled { opacity:.5; cursor:not-allowed; }
    /* white lettering on the green, as approved for this one button */
    .td .btn-go.wt, .td .btn-go.wt:hover { color:#fff; }
    .td .btn-plum { background:var(--td-plum); border:0; color:#fff; font-weight:700; border-radius:9px; padding:11px 22px; font-size:14px; }
    .td .btn-plum:hover:not(:disabled) { background:var(--td-plum-d); }
    .td .btn-ghost { background:#fff; border:1.5px solid var(--td-plum); color:var(--td-plum); font-weight:700; border-radius:9px; padding:10px 20px; font-size:14px; }
    .td .btn-ghost:hover { background:var(--td-plum-soft); }
    .td .btn-quiet { background:#fff; border:1px solid var(--td-line); color:var(--td-ink); font-weight:600; border-radius:9px; padding:8px 16px; font-size:13px; }
    .td .btn-quiet:hover { background:var(--td-bg); }
    .td label.lb { font-size:12.5px; font-weight:600; color:var(--td-muted); display:block; margin-bottom:5px; }
    .td .form-control, .td .form-select { border:1px solid var(--td-line); border-radius:9px; padding:10px 12px; font-size:14px; color:var(--td-ink); }
    .td .form-control:focus, .td .form-select:focus { border-color:var(--td-plum); box-shadow:0 0 0 3px rgba(132,36,132,.14); }
    .td .ask { font-size:17px; font-weight:700; color:var(--td-ink); margin:4px 0 14px; }
    .td .inv-strip { display:flex; flex-wrap:wrap; gap:0; background:var(--td-bg); border:1px solid var(--td-line); border-radius:12px; overflow:hidden; }
    .td .inv-strip > div { padding:13px 18px; flex:1 1 170px; border-right:1px solid var(--td-line); }
    .td .inv-strip > div:last-child { border-right:0; }
    .td .inv-strip .k { font-size:11.5px; color:var(--td-muted); font-weight:600; }
    .td .inv-strip .v { font-size:18px; font-weight:700; letter-spacing:-.01em; }
    .td .inv-strip .v2 { font-size:12.5px; color:var(--td-muted); }
    .td .sums { border:1px solid var(--td-line); border-radius:12px; overflow:hidden; }
    .td .sums .r { display:flex; justify-content:space-between; gap:16px; padding:12px 16px; border-bottom:1px solid var(--td-line); }
    .td .sums .r:last-child { border-bottom:0; }
    .td .sums .r.total { background:#eef7e6; font-weight:700; font-size:16px; }
    .td .sums .r.cut .amt { color:var(--td-red); }
    .td .sums .amt { font-variant-numeric:tabular-nums; font-weight:600; text-align:right; }
    .td .sums .amt .u { display:block; font-size:12px; font-weight:500; color:var(--td-muted); }
    .td .done { text-align:center; padding:12px 6px 4px; }
    .td .done .tick { width:64px; height:64px; border-radius:50%; background:var(--td-green); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:34px; line-height:1; }
    .td .done h5 { font-size:23px; color:#2c7a1f; margin-top:16px; line-height:1.35; }
    .td .done h5 .ty { font-size:20px; font-weight:600; }
    .td .done .sub { color:var(--td-muted); margin-top:5px; }
    .td .testnote { background:var(--td-amber-soft); border:1px solid #d9d3dd; color:var(--td-amber); border-radius:10px; padding:11px 15px; font-size:13px; font-weight:600; margin:18px 0; display:flex; gap:9px; align-items:center; }
    .td .testnote.ok { background:#e4f4ea; color:#1f8a4c; }
    .td .testnote .ic { font-size:15px; line-height:1; }
    .td .panel { border:1px solid var(--td-line); border-radius:12px; overflow:hidden; }
    .td .panel-h { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;
                   padding:14px 18px; background:var(--td-bg); border-bottom:1px solid var(--td-line); }
    .td .panel-h .nm { font-size:19px; font-weight:700; letter-spacing:-.01em; }
    .td .panel-h .sb { font-size:13px; color:var(--td-muted); }
    .td .panel-h .who { flex:1 1 200px; }
    .td .panel-h .orig { text-align:right; padding-right:14px; border-right:1px solid var(--td-line); }
    .td .panel-h .orig .k { font-size:11px; color:var(--td-muted); font-weight:600; text-transform:uppercase; letter-spacing:.03em; }
    .td .panel-h .orig .v { font-size:16px; font-weight:700; }
    .td .panel-h .orig .v2 { font-size:12px; color:var(--td-muted); }
    @media (max-width:640px) { .td .panel-h .orig { text-align:left; border-right:0; padding-right:0; } }
    .td .panel-b { padding:18px; }
    .td .err { background:var(--td-red-soft); color:var(--td-red); border-radius:10px; padding:12px 15px; font-size:13.5px; margin-bottom:14px; }
    .td .why { background:var(--td-red-soft); color:var(--td-red); border-radius:10px; padding:11px 14px; font-size:12.5px; margin-top:8px; }
    .td .muted { color:var(--td-muted); font-size:12.5px; }
    .td .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .td .pill { display:inline-block; font-size:11px; font-weight:700; border-radius:999px; padding:3px 11px; }
    .td .pill-amber { background:var(--td-amber-soft); border:1px solid #d9d3dd; color:var(--td-amber); }
    .td .pill-green { background:#e4f4ea; color:#1f8a4c; }
    .td .pill-grey  { background:#eceaef; color:var(--td-muted); }
    .td .pill-plum  { background:var(--td-plum-soft); color:var(--td-plum); }
    .td .hist { width:100%; border-collapse:collapse; font-size:13px; }
    .td .hist th { text-align:left; font-size:11px; letter-spacing:.04em; text-transform:uppercase; color:var(--td-muted); padding:9px 12px; border-bottom:1px solid var(--td-line); }
    .td .hist td { padding:11px 12px; border-bottom:1px solid var(--td-line); vertical-align:top; }
    .td .hist tr:last-child td { border-bottom:0; }
    .td details > summary { cursor:pointer; list-style:none; padding:14px 18px; display:flex; justify-content:space-between; align-items:center; font-weight:700; color:var(--td-plum); }
    .td details > summary::-webkit-details-marker { display:none; }
    .td .clk { margin-right:8px; }
    .td details[open] > summary { border-bottom:1px solid var(--td-line); }
    .td .give { display:flex; gap:14px; align-items:flex-end; flex-wrap:wrap; background:var(--td-plum-soft); border:1px solid #ecdcec; border-radius:12px; padding:14px 16px; margin-top:14px; }
    .td .give input { width:140px; }
    .td .det { border:1px solid var(--td-line); border-radius:12px; padding:16px; margin-top:18px; background:var(--td-bg); text-align:left; }
    .td .det h6 { font-size:14px; }
    .td .det .hist td:first-child { color:var(--td-muted); font-weight:600; }
    .td .acts { display:flex; gap:12px; flex-wrap:wrap; justify-content:flex-start; margin-top:18px; }
    .td .acts button { min-width:210px; justify-content:center; }
    @media (max-width:575px) { .td .inv-strip > div { border-right:0; border-bottom:1px solid var(--td-line); } }
    /* tf-discount-r2: the shared floating Back-to-top button covered the status pill and the figures on a
       narrow window. This style block loads with this view only, so the rule applies to this page and to
       no other screen. Below 1200px it is hidden; from 1200px there is clear margin beside the panel and
       it stays where it is, with room reserved underneath so it can never sit over the action buttons. */
    @media (max-width:1199.98px) { #back-to-top { display:none !important; } }
    @media (min-width:1200px)   { .td { padding-bottom:96px; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content td" id="td">
            <div class="container-fluid" style="max-width:1080px">

                <?= view('layouts/tourdesk_bar') ?>

                <div class="td-head">
                    <h4>Tour Discounts</h4>
                    <?php if (empty($posting_enabled)): ?>
                        <span class="td-mode">&#9432;&nbsp; Test mode &middot; Ledger posting is off</span>
                    <?php endif; ?>
                </div>

                <div id="err" class="err" style="display:none"></div>

                <!-- open one invoice -->
                <div class="card2" id="c_open">
                    <div class="hd"><h5>Open an invoice</h5></div>
                    <div class="bd">
                        <div class="row g-2 align-items-end">
                            <div class="col-sm-7 col-md-6">
                                <label class="lb" for="q">Invoice number</label>
                                <input type="text" id="q" class="form-control" placeholder="for example AH-1828-09-26" autocomplete="off">
                            </div>
                            <div class="col-sm-5 col-md-6">
                                <button class="btn-plum" id="q_go" type="button">Open Invoice</button>
                            </div>
                        </div>
                        <div id="q_hint" class="muted mt-2">Type the invoice number and press Open Invoice.</div>
                        <div id="q_pick" class="mt-3"></div>
                    </div>
                </div>

                <!-- the invoice and whatever stage it is at -->
                <div id="stage"></div>

                <!-- history -->
                <div class="card2" id="c_hist" style="display:none">
                    <details id="hist_wrap">
                        <summary><span><span class="clk">&#128339;</span> Discount history</span><span class="muted" id="hist_count"></span></summary>
                        <div class="bd" id="hist_body"></div>
                    </details>
                </div>

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
    const BASE = '<?= base_url($page_url ?? 'tour/pricing/discount') ?>';
    const POSTING = <?= !empty($posting_enabled) ? 'true' : 'false' ?>;
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const lkr = v => v == null ? '—' : 'LKR ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const usd = v => v == null ? '' : 'USD ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const api = async (path, opts) => {
        const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {}));
        return r.json();
    };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });

    let INV = null, ELIG = null, LIST = [], PREVIEW = null, FLASH = null, BUSY = false;
    const FORM = { method: 'percent', input: '', reason: '' };          // kept so a failure never loses what was typed

    const err = (t, tone) => { const b = $('err'); if (!t) { b.style.display = 'none'; b.textContent = ''; return; }
                       b.className = tone === 'info' ? 'testnote' : 'err';
                       b.textContent = t; b.style.display = ''; window.scrollTo({ top: 0, behavior: 'smooth' }); };
    const saveForm = () => { if (!$('f_method')) { return; }
                             FORM.method = $('f_method').value; FORM.input = $('f_input').value; FORM.reason = $('f_reason').value; };

    // ---------------------------------------------------------------- open one invoice
    async function open_() {
        const q = $('q').value.trim();
        err(''); $('q_pick').innerHTML = '';
        if (q === '') { $('q_hint').textContent = 'Type the invoice number and press Open Invoice.'; return; }
        let j;
        try { j = await api('/invoices?q=' + encodeURIComponent(q)); }
        catch (e) { err('The invoice search did not answer. Try again.'); return; }
        if (j.status !== 200) { err(j.message || 'The invoice search was refused.'); return; }
        const rows = j.data || [];
        if (!rows.length) { $('q_hint').textContent = 'No matching invoice'; clearStage(); return; }
        const exact = rows.filter(r => String(r.invoice_no).toLowerCase() === q.toLowerCase());
        if (exact.length === 1) { $('q_hint').textContent = ''; load(exact[0].id); return; }
        if (rows.length === 1) { $('q_hint').textContent = ''; load(rows[0].id); return; }
        // more than one invoice carries that number: the person says which
        $('q_hint').textContent = rows.length + ' invoices carry that number. Choose one.';
        $('q_pick').innerHTML = rows.map(r => `<button type="button" class="btn-quiet me-2 mb-2" data-pick="${r.id}">
            <b class="mono">${esc(r.invoice_no)}</b> · ${esc(r.consignee || '')} · ${lkr(r.credit_balance)}${Number(r.cancel) === 1 ? ' · cancelled' : ''}</button>`).join('');
        document.querySelectorAll('[data-pick]').forEach(b => b.addEventListener('click', () => { $('q_pick').innerHTML = ''; load(b.dataset.pick); }));
    }

    function clearStage() { INV = null; LIST = []; PREVIEW = null; FLASH = null; $('stage').innerHTML = ''; $('c_hist').style.display = 'none'; }

    async function load(id, keepFlash) {
        if (!keepFlash) { FLASH = null; }
        err('');
        let j;
        try { j = await api('/invoice/' + id); }
        catch (e) { err('That invoice did not load. Try again.'); return; }
        if (!j || j.status !== 200) { clearStage(); err((j && j.message) || 'That invoice could not be opened.'); return; }
        INV = j.data.invoice; ELIG = j.data.eligibility; LIST = j.data.discounts || []; PREVIEW = null;
        render();
    }

    // ---------------------------------------------------------------- the stage
    const strip = () => `<div class="inv-strip">
        <div><div class="k">Invoice</div><div class="v mono">${esc(INV.invoice_no)}</div></div>
        <div><div class="k">Customer</div><div class="v" style="font-size:15px">${esc(INV.consignee || '—')}</div></div>
        <div><div class="k">Original invoice</div><div class="v">${lkr(INV.issued_local)}</div>
             <div class="v2">${INV.issued_usd != null ? usd(INV.issued_usd) : ''}</div></div></div>`;

    const sums = (d, dUsd, rev, revUsd, pctLabel) => `<div class="sums">
        <div class="r"><span>Original invoice</span><span class="amt">${lkr(INV.issued_local)}<span class="u">${INV.issued_usd != null ? usd(INV.issued_usd) : ''}</span></span></div>
        <div class="r cut"><span>Discount${pctLabel ? ' (' + esc(pctLabel) + ')' : ''}</span><span class="amt">− ${lkr(d)}<span class="u">${dUsd != null ? usd(dUsd) : ''}</span></span></div>
        <div class="r total"><span>Revised invoice total</span><span class="amt">${lkr(rev)}<span class="u">${revUsd != null ? usd(revUsd) : ''}</span></span></div></div>`;

    function render() {
        const live = LIST.find(d => d.state === 'requested' || d.state === 'approved') || null;
        $('c_hist').style.display = LIST.length ? '' : 'none';
        $('hist_count').textContent = LIST.length ? LIST.length + (LIST.length === 1 ? ' entry' : ' entries') : '';
        renderHistory();

        if (live && live.state === 'approved') { stageApproved(live); }
        else if (live) { stageAwaiting(live); }
        else { stageForm(); }

        $('stage').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // --- the form -------------------------------------------------------------------
    function stageForm() {
        const mOk = ELIG.percent.allowed, pOk = ELIG.points.allowed;
        if (!mOk && !pOk) {
            $('stage').innerHTML = `<div class="card2"><div class="hd"><h5>Invoice ${esc(INV.invoice_no)}</h5></div>
                <div class="bd">${strip()}<div class="why mt-3">${esc(ELIG.percent.why || 'No discount may be recorded on this invoice.')}</div></div></div>`;
            return;
        }
        if (!mOk && FORM.method === 'percent') { FORM.method = 'points'; }
        if (!pOk && FORM.method === 'points') { FORM.method = 'percent'; }

        $('stage').innerHTML = `<div class="card2">
            <div class="hd"><h5>Invoice ${esc(INV.invoice_no)}</h5><span class="tag">Step 1 of 2 · record</span></div>
            <div class="bd">
                ${strip()}
                <div class="ask mt-3">What discount would you like to give?</div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="lb" for="f_method">Method</label>
                        <select id="f_method" class="form-select">
                            <option value="percent"${FORM.method === 'percent' ? ' selected' : ''}${mOk ? '' : ' disabled'}>Percentage of invoice total${mOk ? '' : ' — not available'}</option>
                            <option value="points"${FORM.method === 'points' ? ' selected' : ''}${pOk ? '' : ' disabled'}>Points off the markup${pOk ? '' : ' — not available'}</option>
                        </select>
                        ${mOk ? '' : `<div class="why">Percentage of invoice total — ${esc(ELIG.percent.why)}</div>`}
                        ${pOk ? '' : `<div class="why">Points off the markup — ${esc(ELIG.points.why)}</div>`}
                    </div>
                    <div class="col-md-3">
                        <label class="lb" for="f_input"><span id="f_lbl">${FORM.method === 'percent' ? 'Discount (%)' : 'Points off the markup'}</span></label>
                        <input type="number" id="f_input" class="form-control" step="0.01" min="0" value="${esc(FORM.input)}" placeholder="5">
                    </div>
                    <div class="col-md-4">
                        <label class="lb" for="f_reason">Reason <span style="color:var(--td-red)">*</span></label>
                        <input type="text" id="f_reason" class="form-control" maxlength="500" value="${esc(FORM.reason)}" placeholder="why this discount is given">
                    </div>
                </div>
                <div id="pv" class="mt-3"></div>
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <button class="btn-ghost" id="b_pv" type="button">Preview</button>
                    <button class="btn-go" id="b_submit" type="button" disabled>Submit for Approval</button>
                </div>
                <div class="muted mt-2">Submitting records the discount. It changes nothing until a different authorised account approves it.</div>
            </div></div>`;

        $('f_method').addEventListener('change', () => { saveForm();
            $('f_lbl').textContent = FORM.method === 'percent' ? 'Discount (%)' : 'Points off the markup';
            PREVIEW = null; $('pv').innerHTML = ''; $('b_submit').disabled = true; });
        ['f_input', 'f_reason'].forEach(id => $(id).addEventListener('input', () => { saveForm();
            if (id === 'f_input') { PREVIEW = null; $('pv').innerHTML = ''; $('b_submit').disabled = true; } }));
        $('b_pv').addEventListener('click', preview);
        $('b_submit').addEventListener('click', submit);
        $('f_input').focus();
    }

    async function preview() {
        saveForm(); err('');
        if (BUSY) { return; }
        BUSY = true; $('b_pv').disabled = true;
        let j;
        try { j = await post('/invoice/' + INV.id + '/preview', { method: FORM.method, input: FORM.input }); }
        catch (e) { j = null; }
        BUSY = false; $('b_pv').disabled = false;
        if (!j) { err('The preview did not answer. Your figures are still here — press Preview again.'); return; }
        if (j.status !== 200) { $('pv').innerHTML = `<div class="err mb-0">${esc(j.message)}</div>`; $('b_submit').disabled = true; PREVIEW = null; return; }
        PREVIEW = j.data;
        const p = PREVIEW, pct = p.external_pct != null ? Number(p.external_pct).toFixed(2) + '%' : null;
        $('pv').innerHTML = `${sums(p.discount_local, p.discount_usd, p.revised_local, p.revised_usd, pct)}
            <div class="muted mt-2">${p.reconciles ? 'Original = discount + revised, exactly.' : 'These figures do not reconcile — do not submit this.'}
            ${p.below_cost ? ' <span class="pill pill-amber">below cost</span>' : ''}</div>
            <details class="mt-2"><summary style="padding:8px 0"><span class="muted">The accounting entry this would write</span></summary>
            <div class="muted" style="padding:6px 0 0">${p.proposed_entries.map(e =>
                `${esc(e.L_Code)} &nbsp; Dr ${Number(e.Dr).toFixed(2)} &nbsp; Cr ${Number(e.Cr).toFixed(2)}${e.dollar_cr ? ' &nbsp; USD ' + e.dollar_cr : ''}`).join('<br>')}
            <br>${POSTING ? 'Posting is on.' : 'Test mode — nothing will be posted to the ledger.'}</div></details>`;
        $('b_submit').disabled = !(p.reconciles && p.usd_reconciles);
    }

    async function submit() {
        saveForm(); err('');
        if (BUSY || !PREVIEW) { return; }
        if (FORM.reason.trim().length < 5) { err('Give a short reason before submitting.'); $('f_reason').focus(); return; }
        BUSY = true; $('b_submit').disabled = true; $('b_submit').textContent = 'Submitting…';
        const key = 'ui-' + INV.id + '-' + FORM.method + '-' + FORM.input + '-' + Date.now();
        let j;
        try { j = await post('/invoice/' + INV.id + '/request', { method: FORM.method, input: FORM.input, reason: FORM.reason, idem_key: key }); }
        catch (e) { j = null; }
        BUSY = false;
        if (!j || j.status !== 200) {
            if ($('b_submit')) { $('b_submit').disabled = false; $('b_submit').textContent = 'Submit for Approval'; }
            err((j && j.message) || 'The discount was not submitted. Your figures are still here — try again.');
            return;
        }
        FLASH = { kind: 'submitted' };            // the server confirmed it; the confirmation is shown, not promised
        FORM.input = ''; FORM.reason = '';
        load(INV.id, true);
    }

    // --- waiting for approval -------------------------------------------------------
    function stageAwaiting(d) {
        const pct = d.method === 'percent' ? Number(d.input_value).toFixed(2) + '%' : Number(d.input_value).toFixed(2) + ' points';
        const submitted = FLASH && FLASH.kind === 'submitted';
        $('stage').innerHTML = `<div class="card2">
            <div class="hd"><h5>Review and approve</h5><span class="tag">&#9201; Awaiting approval</span></div>
            <div class="bd">
                ${submitted ? `<div class="done"><div class="tick">&#10003;</div>
                    <h5>Discount submitted for approval. Thank you.</h5>
                    <div class="sub">It is recorded as discount #${d.id}. Nothing changes on the invoice until a different authorised account approves it.</div></div>` : ''}
                <div class="${submitted ? 'mt-3' : ''}">${strip()}</div>
                <div class="give">
                    <div>
                        <label class="lb" for="g_input">Discount given${d.method === 'percent' ? ' (%)' : ' (points off the markup)'}</label>
                        <input type="number" id="g_input" class="form-control" step="0.01" min="0" value="${Number(d.input_value).toFixed(2)}">
                    </div>
                    <div><button class="btn-ghost" id="b_edit" type="button">Update discount</button></div>
                    <div class="muted" style="flex:1 1 240px">Change the figure and press Update discount. The waiting request is cancelled and a new one recorded in its place; both steps stay in the audit trail.</div>
                </div>
                <div class="mt-3">${sums(d.discount_local, d.discount_usd, d.revised_local, d.revised_usd, pct)}</div>
                <div class="muted mt-2">Reason: ${esc(d.reason || '—')} · recorded by ${esc(d.requested_by || '')} on ${esc((d.requested_at || '').slice(0, 16))}</div>
                ${POSTING ? '' : '<div class="testnote">Test mode — no ledger entry will be posted on approval.</div>'}
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <button class="btn-go" id="b_ap" type="button">Approve discount</button>
                    <button class="btn-quiet" id="b_ca" type="button">Cancel this discount</button>
                    <button class="btn-quiet" id="b_other" type="button">Open another invoice</button>
                </div>
                <div class="muted mt-2">Approval by a different authorised account. The account that recorded it cannot approve it.</div>
            </div></div>`;
        $('b_ap').addEventListener('click', () => act(d.id, '/approve', 'Approving…', false, 'A note with this approval (optional)'));
        $('b_edit').addEventListener('click', () => changeFigure(d));
        $('g_input').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); changeFigure(d); } });
        $('b_ca').addEventListener('click', () => act(d.id, '/cancel', 'Cancelling…', false, 'Why is it being cancelled? (optional)'));
        $('b_other').addEventListener('click', another);
    }

    /**
     * Change the figure on a discount that is still waiting. An invoice carries one live adjustment at a
     * time, so the one waiting is cancelled and a new one recorded in its place — two audited steps,
     * nothing silent, and the approval rule is untouched.
     */
    async function changeFigure(d) {
        if (BUSY) { return; }
        err('');
        const fresh = String(($('g_input') || {}).value || '').trim();
        if (fresh === '' || !(Number(fresh) > 0)) { err('Give the discount as a number greater than zero.'); $('g_input').focus(); return; }
        if (Number(fresh) === Number(d.input_value)) { err('That is the figure already waiting for approval.', 'info'); return; }

        const r = await Swal.fire({
            title: 'Change the discount to ' + fresh + (d.method === 'percent' ? '%?' : ' points?'),
            html: '<span style="font-size:13.5px;color:#6b6472">Discount #' + d.id + ' is cancelled and a new request recorded in its place. Both steps are kept in the audit trail.</span>',
            input: 'text', inputLabel: 'Reason for the new figure', inputValue: d.reason || '',
            showCancelButton: true, confirmButtonText: 'Update discount', confirmButtonColor: '#78b848',
        });
        if (!r.isConfirmed) { return; }
        const reason = String(r.value || '').trim();
        if (reason.length < 5) { err('Give a short reason for the new figure.'); return; }

        BUSY = true;
        document.querySelectorAll('#stage button, #stage input').forEach(b => { b.disabled = true; });
        $('b_edit').textContent = 'Updating…';

        let j;
        try { j = await post('/' + d.id + '/cancel', { note: 'Replaced — the figure was changed to ' + fresh }); }
        catch (e) { j = null; }
        if (!j || j.status !== 200) {
            BUSY = false;
            const why = (j && j.message) || 'The waiting discount could not be cancelled, so nothing was changed.';
            await load(INV.id, false);
            err(why);
            return;
        }

        const key = 'ui-' + INV.id + '-' + d.method + '-' + fresh + '-' + Date.now();
        let k;
        try { k = await post('/invoice/' + INV.id + '/request', { method: d.method, input: fresh, reason, idem_key: key }); }
        catch (e) { k = null; }
        BUSY = false;
        if (!k || k.status !== 200) {
            // the old one is already cancelled: say so plainly and leave the form ready with the figure in it
            FORM.method = d.method; FORM.input = fresh; FORM.reason = reason;
            await load(INV.id, false);
            err(((k && k.message) || 'The new figure was not recorded.') +
                ' Discount #' + d.id + ' is already cancelled, so nothing is waiting for approval. The figure is in the form below — press Preview, then Submit for Approval.');
            return;
        }
        FLASH = { kind: 'submitted' };
        await load(INV.id, true);
    }

    // --- approved -------------------------------------------------------------------
    function stageApproved(d) {
        const pct = d.method === 'percent' ? Number(d.input_value).toFixed(2) + '%' : Number(d.input_value).toFixed(2) + ' points';
        // the wording states only what has actually happened: in this system posting is off, so it is
        // recorded, never "applied"
        const headline = d.post_id
            ? 'Discount successfully applied.<br><span class="ty">Thank you.</span>'
            : 'Discount approved and recorded.<br><span class="ty">Thank you.</span>';
        $('stage').innerHTML = `<div class="card2">
            <div class="hd"><h5>Approval confirmation</h5></div>
            <div class="bd">
                <div class="done"><div class="tick">&#10003;</div><h5>${headline}</h5></div>
                ${d.post_id ? `<div class="testnote ok"><span class="ic">&#9432;</span> Posted to the ledger as entry ${d.post_id}.</div>`
                            : '<div class="testnote"><span class="ic">&#9432;</span> Test mode — no ledger entry posted.</div>'}
                <div class="panel">
                    <div class="panel-h">
                        <div class="who"><div class="nm mono">${esc(INV.invoice_no)}</div><div class="sb">${esc(INV.consignee || '')}</div></div>
                        <div class="orig">
                            <div class="k">Original invoice</div>
                            <div class="v">${lkr(INV.issued_local)}</div>
                            <div class="v2">${INV.issued_usd != null ? usd(INV.issued_usd) : ''}</div>
                        </div>
                        <span class="pill pill-green">${d.post_id ? 'APPROVED · POSTED' : 'APPROVED · TEST MODE'}</span>
                    </div>
                    <div class="panel-b">
                        ${sums(d.discount_local, d.discount_usd, d.revised_local, d.revised_usd, pct)}
                        <div class="muted mt-2">Recorded by ${esc(d.requested_by || '')} · approved by ${esc(d.approved_by || '')} on ${esc((d.approved_at || '').slice(0, 16))}</div>
                        <div class="acts">
                            <button class="btn-go wt" id="b_det" type="button">View discount details</button>
                            <button class="btn-ghost" id="b_other" type="button">Open another invoice</button>
                        </div>
                        <div id="details"></div>
                    </div>
                </div>
            </div></div>`;
        $('b_det').addEventListener('click', () => toggleDetails(d));
        $('b_other').addEventListener('click', another);
    }

    /**
     * The approved transaction in full. Reversal lives here and nowhere else: the person reads what was
     * approved before starting one. The reversal itself is unchanged — same server call, same reason
     * requirement, same permissions, same audit.
     */
    function toggleDetails(d) {
        const box = $('details');
        if (box.innerHTML !== '') { box.innerHTML = ''; $('b_det').textContent = 'View discount details'; return; }
        $('b_det').textContent = 'Hide discount details';
        const pct = d.method === 'percent' ? Number(d.input_value).toFixed(2) + '% of the invoice total'
                                           : Number(d.input_value).toFixed(2) + ' points off the markup';
        box.innerHTML = `<div class="det">
            <h6 class="mb-2">Discount #${d.id} — the approved transaction</h6>
            <table class="hist"><tbody>
                <tr><td style="width:190px">Invoice</td><td><b class="mono">${esc(INV.invoice_no)}</b> · ${esc(INV.consignee || '')}</td></tr>
                <tr><td>Method</td><td>${pct}</td></tr>
                <tr><td>Original invoice</td><td class="mono">${lkr(d.issued_local ?? INV.issued_local)}${(d.issued_usd ?? INV.issued_usd) != null ? ' · ' + usd(d.issued_usd ?? INV.issued_usd) : ''}</td></tr>
                <tr><td>Discount</td><td class="mono" style="color:var(--td-red)">${lkr(d.discount_local)}${d.discount_usd != null ? ' · ' + usd(d.discount_usd) : ''}</td></tr>
                <tr><td>Revised invoice total</td><td class="mono"><b>${lkr(d.revised_local)}</b>${d.revised_usd != null ? ' · ' + usd(d.revised_usd) : ''}</td></tr>
                <tr><td>Reason</td><td>${esc(d.reason || '—')}</td></tr>
                <tr><td>Recorded by</td><td>${esc(d.requested_by || '')} on ${esc((d.requested_at || '').slice(0, 16))}</td></tr>
                <tr><td>Approved by</td><td>${esc(d.approved_by || '')} on ${esc((d.approved_at || '').slice(0, 16))}</td></tr>
                <tr><td>Accounting entry</td><td>${d.post_id ? 'posted as ledger ID ' + d.post_id : '<span class="pill pill-amber">none — test mode, no ledger entry posted</span>'}</td></tr>
            </tbody></table>
            <div class="d-flex gap-2 mt-3 flex-wrap align-items-center">
                <button class="btn-quiet" id="b_rv" type="button">Reverse this discount</button>
                <span class="muted">A reversal needs a reason, is recorded in the audit trail, and can be done once.</span>
            </div>
            <div class="muted mt-2">The full history of every discount on this invoice is in Discount history below.</div>
        </div>`;
        $('b_rv').addEventListener('click', () => act(d.id, '/reverse', 'Reversing…', true, 'Why is it being reversed?'));
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function another() { clearStage(); $('q').value = ''; $('q_hint').textContent = 'Type the invoice number and press Open Invoice.'; $('q').focus(); window.scrollTo({ top: 0, behavior: 'smooth' }); }

    // one action at a time; the button is disabled while it is in flight, so a second click does nothing
    async function act(id, path, busyText, noteRequired, prompt) {
        if (BUSY) { return; }
        err('');
        const r = await Swal.fire({ input: 'text', inputLabel: prompt, inputPlaceholder: noteRequired ? 'required' : 'optional',
                                    showCancelButton: true, confirmButtonText: 'Go ahead', confirmButtonColor: '#78b848' });
        if (!r.isConfirmed) { return; }
        const note = String(r.value || '').trim();
        if (noteRequired && note === '') { err('A reason is required.'); return; }
        BUSY = true;
        document.querySelectorAll('#stage button').forEach(b => { b.disabled = true; });
        const btn = $('b_ap') || $('b_rv');
        if (btn) { btn.textContent = busyText; }
        let j;
        try { j = await post('/' + id + path, { note }); }
        catch (e) { j = null; }
        BUSY = false;
        if (!j || j.status !== 200) {
            const why = (j && j.message) || 'That did not go through. Nothing was changed.';
            await load(INV.id, false);      // redraw first: load() clears the banner, so the reason is set after it
            err(why);
            return;
        }
        FLASH = null;
        load(INV.id, true);
    }

    // --- history --------------------------------------------------------------------
    function renderHistory() {
        if (!LIST.length) { $('hist_body').innerHTML = ''; return; }
        const pill = s => s === 'approved' ? 'pill-green' : s === 'requested' ? 'pill-amber' : s === 'reversed' ? 'pill-plum' : 'pill-grey';
        $('hist_body').innerHTML = `<table class="hist">
            <thead><tr><th>#</th><th>Method</th><th>Discount</th><th>Revised</th><th>State</th><th>Recorded / approved</th><th></th></tr></thead>
            <tbody>${LIST.map(d => `<tr>
                <td class="mono">#${d.id}</td>
                <td>${d.method === 'percent' ? Number(d.input_value).toFixed(2) + '% of the total' : Number(d.input_value).toFixed(2) + ' points off the markup'}
                    <div class="muted">${esc(d.reason || '')}</div></td>
                <td class="mono">${lkr(d.discount_local)}<div class="muted">${d.discount_usd != null ? usd(d.discount_usd) : ''}</div></td>
                <td class="mono">${lkr(d.revised_local)}</td>
                <td><span class="pill ${pill(d.state)}">${esc(d.state)}</span>${d.post_id ? `<div class="muted">ledger ${d.post_id}</div>` : ''}</td>
                <td>${esc(d.requested_by || '')}<div class="muted">${esc((d.requested_at || '').slice(0, 16))}${d.approved_by ? '<br>approved: ' + esc(d.approved_by) : ''}${d.cancelled_at ? '<br>cancelled' : ''}${d.reversed_at ? '<br>reversed' : ''}</div></td>
                <td><button class="btn-quiet" data-log="${d.id}" type="button">Audit trail</button></td></tr>
                <tr id="log_${d.id}" style="display:none"><td colspan="7" style="background:var(--td-bg)"><div class="muted">loading…</div></td></tr>`).join('')}
            </tbody></table>`;
        document.querySelectorAll('[data-log]').forEach(b => b.addEventListener('click', async () => {
            const row = $('log_' + b.dataset.log);
            if (row.style.display !== 'none') { row.style.display = 'none'; return; }
            row.style.display = '';
            const j = await api('/' + b.dataset.log + '/history');
            row.firstElementChild.innerHTML = (j.status === 200 && (j.data || []).length)
                ? `<table class="hist"><tbody>${j.data.map(l => `<tr><td class="mono" style="width:140px">${esc((l.at || '').slice(0, 16))}</td>
                    <td style="width:150px"><b>${esc(l.action)}</b></td><td style="width:170px">${esc(l.by_name || l.by_user || '')}</td>
                    <td class="muted">${esc(l.note || '')}</td></tr>`).join('')}</tbody></table>`
                : '<div class="muted">Nothing recorded.</div>';
        }));
    }

    // ---------------------------------------------------------------- start
    $('q_go').addEventListener('click', open_);
    $('q').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); open_(); } });
    const wanted = new URLSearchParams(location.search).get('invoice');
    if (wanted) { $('q').value = wanted; open_(); } else { $('q').focus(); }
})();
</script>
</body>
</html>
