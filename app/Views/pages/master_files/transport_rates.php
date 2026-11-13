<?= view('layouts/head') ?>
<style>
    /* ---------- Transport Rates (same tokens as the Tour Inquiry panel) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:820px; }
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
    .tf .tbl tr.off td { opacity:.5; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .rate { font-size:15px; font-weight:600; }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .stale { border-left:3px solid var(--tf-amber); background:var(--tf-amber-soft); border-radius:8px; padding:10px 12px; font-size:12.5px; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>
                <?= view('layouts/tgs_bar') ?>

                <div class="tf-title">
                    <div>
                        <h4>Transport Rates <span class="help">· Transport &amp; Guide Services</span></h4>
                        <div class="sub">What a kilometre costs, by vehicle size. Rates are typed here and changed here, by a person, when management decides — nothing updates them on its own. A rate is never overwritten: setting a new one keeps the old in the history, and a quotation already sent keeps the rate it went out with.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Inquiry tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div id="stale" class="mb-3" hidden></div>

                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6 id="f_title">Set a rate</h6><span class="help">Kilometre-based only</span></div>
                            <div class="tf-card-b">
                                <div class="mb-2">
                                    <label class="form-label help">Vehicle size</label>
                                    <select class="form-select form-select-sm" id="f_class"></select>
                                    <div class="help mt-1" id="f_hint"></div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <label class="form-label help">Rate per kilometre</label>
                                        <input type="number" min="0" step="0.01" class="form-control form-control-sm" id="f_rate" placeholder="e.g. 95.00">
                                    </div>
                                    <div class="col-5">
                                        <label class="form-label help">Currency</label>
                                        <select class="form-select form-select-sm" id="f_cur"><option value="LKR">LKR</option><option value="USD">USD</option></select>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label help">Effective from</label>
                                    <input type="date" class="form-control form-control-sm" id="f_from">
                                    <div class="help mt-1">Today unless you say otherwise. A future date is allowed: next season's rate can be set now and takes over on that day by itself.</div>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label help">Reason for the change</label>
                                    <input class="form-control form-control-sm" id="f_reason" placeholder="e.g. fuel increase, October revision">
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-primary btn-sm" id="f_save">Save this rate</button>
                                    <button class="btn btn-light btn-sm" id="f_clear">Clear</button>
                                </div>
                                <div class="help mt-2">Saving starts a new rate from the date above. The rate it replaces is closed the day before and kept, not deleted. To put a typing mistake right without that, use Correct on the row.</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>Current rates</h6><span class="help">One rate per size. The vehicle master's figure, where it has one, is shown as a hint and never copied in.</span></div>
                            <div class="tf-card-b" style="overflow-x:auto"><table class="tbl" id="board"><thead><tr>
                                <th>Vehicle size</th><th>Seats</th><th title="The ladder: a party up to this many travels in this size — for new vehicle choices and the pax matrix">Carries up to</th><th>Rate per kilometre</th><th>Effective from</th><th>Status</th><th></th>
                            </tr></thead><tbody></tbody></table></div>
                        </div>
                        <div class="tf-card" style="margin-top:16px" id="fleet_card">
                            <div class="tf-card-h"><h6>The fleet — as the Vehicle master holds it</h6><span class="help">Read-only. The registration number is kept on <?= !empty($can_vehicle_master) ? '<a href="' . base_url($vehicle_master_url ?? 'master_files/vehicle') . '" target="_blank">Master Files → Vehicle</a>' : 'Master Files → Vehicle' ?> (the "Vehicle Number" field, as always); from there it flows to the Transport card, the Tour Board, the Driver's Pack and the Complete Record once the vehicle is allocated. A card allocated before the number was entered offers "take the registration from the master".</span></div>
                            <div class="tf-card-b" style="overflow-x:auto"><table class="tbl" id="fleet"><thead><tr><th>Vehicle</th><th>Seats</th><th>Owner (ledger)</th><th>Registration number</th></tr></thead><tbody><tr><td colspan="4" class="help">Loading…</td></tr></tbody></table>
                            <div class="help mt-2">A vehicle without a number here is set on a card under its type alone and marked "no registration on the master" everywhere it shows. Vehicles themselves are added and retired under Master Files → Vehicle, as always.</div></div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<!-- history -->
<div class="modal fade tf" id="m_hist" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="h_title">Rate history</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="overflow-x:auto">
        <table class="tbl"><thead><tr><th>Rate per kilometre</th><th>From</th><th>To</th><th>Set by</th><th>Reason</th><th>Used on</th><th>Status</th><th></th></tr></thead><tbody id="h_rows"></tbody></table>
        <div class="help mt-2">A rate is never edited once set. Correct puts a typing mistake right in place and is logged as a correction — the old figure, who fixed it and when — so it never reads as a price change. Inquiry lines that already copied a figure keep it.</div>
    </div>
</div></div></div>

<!-- correct -->
<div class="modal fade tf" id="m_fix" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Correct a typing mistake</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help" id="x_what"></div>
        <label class="form-label help mt-2">Corrected rate per kilometre</label>
        <input type="number" min="0" step="0.01" class="form-control form-control-sm" id="x_rate">
        <label class="form-label help mt-2">Reason</label>
        <input class="form-control form-control-sm" id="x_reason" placeholder="e.g. typed 950 for 95">
        <input type="hidden" id="x_id">
        <div class="help mt-2">Use this only for a figure that was never meant. A rate that has genuinely changed is a new rate from a date — set it on the left.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="x_ok">Correct it</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($rates_url ?? 'master_files/transport_rates') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const show = sel => { const el = document.querySelector(sel); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : new bootstrap.Modal(el)).show(); };
    const hide = sel => { const i = bootstrap.Modal.getInstance(document.querySelector(sel)); if (i) i.hide(); };
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2600 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With':'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const money = (v, cur) => v == null ? '—' : (cur ? esc(cur) + ' ' : '') + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtDate = d => { if (!d) return ''; const [y, m, dd] = String(d).split('-'); return `${Number(dd)} ${['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][Number(m) - 1]} ${y}`; };

    let BOARD = [];
    const ROOTB = '<?= rtrim(base_url(), '/') ?>';
    let CREW_URL = ROOTB + '/master_files/crew_people', CREW_MASTER = false;   // Transport & Guide Services: the people whose vehicle is in a class

    function render() {
        const sel = document.getElementById('f_class');
        const keep = sel.value;
        sel.innerHTML = BOARD.map(b => `<option value="${esc(b.code)}">${esc(b.name)} · ${b.seats ?? '?'} seats · up to ${b.max_pax}</option>`).join('');
        if (keep) sel.value = keep;
        hint();

        document.querySelector('#board tbody').innerHTML = BOARD.map(b => {
            const c = b.current;
            const st = !b.active ? '<span class="pill pill-grey">retired</span>'
                : (c ? '<span class="pill pill-green">current</span>' : '<span class="pill pill-red">no rate</span>');
            const up = b.upcoming ? `<div class="help">then ${money(b.upcoming.rate_km, b.upcoming.currency)} from ${fmtDate(b.upcoming.valid_from)}</div>` : '';
            const hintTxt = (!c && b.vehicle_master) ? `<div class="help">vehicle master says ${b.vehicle_master.rates.map(r => Number(r).toLocaleString()).join(' / ')} for ${esc(b.vehicle_master.types.join(', '))} — labelled per hour there, so confirm before typing it in</div>` : '';
            return `<tr class="${b.active ? '' : 'off'}">
                <td><b>${esc(b.name)}</b><div class="help mono">${esc(b.code)}</div></td>
                <td class="mono">${b.seats ?? '—'}</td>
                <td><span class="mono" data-maxpax-of="${esc(b.code)}">${b.max_pax}</span> <button class="btn btn-link btn-sm p-0 align-baseline" data-maxpax="${esc(b.code)}" title="Change how many passengers this size carries on the ladder">change</button></td>
                <td>${c ? `<span class="rate mono">${money(c.rate_km, c.currency)}</span>` : '<span class="pill pill-red">not set</span>'}${up}${hintTxt}
                    ${c && c.corrected_at ? '<div class="help">corrected from ' + money(c.corrected_from) + '</div>' : ''}</td>
                <td>${c ? fmtDate(c.valid_from) + (c.reason ? `<div class="help">${esc(c.reason)}</div>` : '') : '—'}</td>
                <td>${st}${c && b.used_on ? `<div class="help">used on ${b.used_on} inquiry line${b.used_on === 1 ? '' : 's'}</div>` : ''}${b.crew ? `<div class="help">${CREW_MASTER ? `<a href="${CREW_URL}?class=${encodeURIComponent(b.code)}" title="The people on Drivers &amp; Guides whose vehicle is in this class — this is their general rate">${b.crew} on Drivers &amp; Guides</a>` : b.crew + ' on Drivers &amp; Guides'}${!c && b.active ? ' <span class="pill pill-amber" title="They have no general rate until this class has one">no general rate for them</span>' : ''}</div>` : ''}</td>
                <td style="white-space:nowrap">
                    <button class="btn btn-light btn-sm" data-set="${esc(b.code)}">${c ? 'Change rate' : 'Set rate'}</button>
                    <button class="btn btn-light btn-sm" data-hist="${esc(b.code)}" ${b.history_n ? '' : 'disabled'}>History${b.history_n ? ' (' + b.history_n + ')' : ''}</button>
                    <button class="btn btn-light btn-sm" data-act="${esc(b.code)}" data-on="${b.active ? 0 : 1}" title="${b.active ? 'Stop offering this size' : 'Offer this size again'}">${b.active ? 'Retire' : 'Reinstate'}</button>
                </td></tr>`;
        }).join('') || '<tr><td colspan="7" class="empty">No vehicle sizes are set up.</td></tr>';

        document.querySelectorAll('[data-set]').forEach(b => b.addEventListener('click', () => { sel.value = b.dataset.set; hint(); document.getElementById('f_rate').focus(); window.scrollTo({ top: 0, behavior: 'smooth' }); }));
        document.querySelectorAll('[data-hist]').forEach(b => b.addEventListener('click', () => openHistory(b.dataset.hist)));
        document.querySelectorAll('[data-maxpax]').forEach(btn => btn.addEventListener('click', async () => {
            const b = BOARD.find(x => x.code === btn.dataset.maxpax);
            if (!b) return;
            const r = await Swal.fire({
                title: b.name + ' — carries up to',
                html: 'A party of up to this many travels in a ' + esc(b.name) + (b.seats ? ' (it seats ' + b.seats + '; the spare seats are the legroom, the luggage and the guide)' : '') + '. New vehicle choices and the pax matrix follow the new figure; vehicles already on inquiries keep what they have. The change is logged.',
                input: 'number', inputValue: b.max_pax, inputAttributes: { min: 1, max: b.seats || 99, step: 1 },
                showCancelButton: true, confirmButtonText: 'Save', cancelButtonText: 'Cancel'
            });
            if (!r.isConfirmed) return;
            const j = await post('/class/' + encodeURIComponent(b.code) + '/max_pax', { max_pax: r.value });
            if (j.status !== 200) { toast('error', j.message); return; }
            BOARD = j.data; render(); toast('success', j.message);
        }));
        document.querySelectorAll('[data-act]').forEach(b => b.addEventListener('click', async () => {
            const on = b.dataset.on === '1';
            if (!on) {
                const ok = await Swal.fire({ text: 'Retire this size? It stops being offered on new inquiries. Rates and past quotations are untouched, and Reinstate brings it back.', icon: 'question', showCancelButton: true, confirmButtonText: 'Retire', cancelButtonText: 'Cancel' });
                if (!ok.isConfirmed) return;
            }
            const j = await post('/class/' + encodeURIComponent(b.dataset.act) + '/active', { active: on ? 1 : 0 });
            if (j.status !== 200) { toast('error', j.message); return; }
            BOARD = j.data; render(); toast('success', j.message);
        }));
    }

    function hint() {
        const b = BOARD.find(x => x.code === document.getElementById('f_class').value);
        const h = document.getElementById('f_hint');
        if (!b) { h.textContent = ''; return; }
        h.innerHTML = b.current
            ? `Now ${money(b.current.rate_km, b.current.currency)} since ${fmtDate(b.current.valid_from)}. Saving a new figure starts a new period; the current one is kept.`
            : (b.vehicle_master ? `No rate yet. The vehicle master holds ${b.vehicle_master.rates.join(' / ')} for ${esc(b.vehicle_master.types.join(', '))}, labelled per hour there — confirm it is per kilometre before using it.` : 'No rate yet for this size.');
    }

    async function load() {
        const j = await api('/board');
        CREW_URL = ROOTB + '/' + (j.data.crew_url || 'master_files/crew_people'); CREW_MASTER = !!j.data.crew_master;
        if (j.status !== 200) { toast('error', j.message); return; }
        BOARD = j.data.board;
        render();
        const st = document.getElementById('stale');
        if ((j.data.stale || []).length) {
            st.hidden = false;
            st.innerHTML = `<div class="stale"><b>${j.data.stale.length} open inquir${j.data.stale.length === 1 ? 'y is' : 'ies are'} still priced at an earlier rate</b> — ${j.data.stale.map(s => esc(s.inquiry_no) + ' (' + esc(s.class_code) + ' at ' + money(s.km_rate) + ', master now ' + money(s.current) + ')').join('; ')}. Nothing is re-priced by itself: open the inquiry and press Re-price if the new rate should apply.</div>`;
        } else {
            st.hidden = true;
        }
    }

    // tf-masters-r3: the fleet as the Vehicle master holds it — read-only; the number is entered on Master Files → Vehicle
    async function loadFleet() {
        const j = await api('/fleet');
        const tb = document.querySelector('#fleet tbody');
        const rows = j.data || [];
        tb.innerHTML = rows.length ? rows.map(v => `<tr data-vehicle="${v.id}">
            <td><b>${esc(v.type)}</b>${v.flagged ? ' <span class="pill pill-amber" title="Flagged on the vehicle master">flagged</span>' : ''}</td>
            <td class="mono">${v.seats || '—'}</td>
            <td class="mono">${esc(v.owner || '—')}</td>
            <td class="mono" data-reg="${v.id}">${v.reg ? `<b>${esc(v.reg)}</b>` : '<span class="pill pill-amber" title="No registration number on the Vehicle master yet — enter it under Master Files → Vehicle">not on the master yet</span>'}</td>
        </tr>`).join('') : '<tr><td colspan="4" class="help">No vehicles on the master.</td></tr>';
    }
    loadFleet();

    document.getElementById('f_class').addEventListener('change', hint);
    document.getElementById('f_clear').addEventListener('click', () => { document.getElementById('f_rate').value = ''; document.getElementById('f_reason').value = ''; document.getElementById('f_from').value = ''; });
    document.getElementById('f_save').addEventListener('click', async () => {
        const j = await post('/rate', {
            class_code: document.getElementById('f_class').value, rate_km: document.getElementById('f_rate').value,
            currency: document.getElementById('f_cur').value, valid_from: document.getElementById('f_from').value || null,
            reason: document.getElementById('f_reason').value
        });
        if (j.status !== 200) { toast('error', j.message); return; }
        BOARD = j.data; render(); toast('success', j.message);
        document.getElementById('f_rate').value = ''; document.getElementById('f_reason').value = '';
        load();
    });

    async function openHistory(code) {
        const b = BOARD.find(x => x.code === code);
        document.getElementById('h_title').textContent = (b ? b.name : code) + ' — rate history';
        const j = await api('/history/' + encodeURIComponent(code));
        if (j.status !== 200) { toast('error', j.message); return; }
        document.getElementById('h_rows').innerHTML = (j.data || []).map(r => `<tr>
            <td class="mono"><b>${money(r.rate_km, r.currency)}</b>${r.corrected_at ? `<div class="help">corrected from ${money(r.corrected_from)} by ${esc(r.corrected_by || '')}</div>` : ''}</td>
            <td>${fmtDate(r.valid_from)}</td><td>${r.valid_to ? fmtDate(r.valid_to) : '<span class="help">open</span>'}</td>
            <td>${esc(r.set_by || '—')}<div class="help">${esc((r.set_at || '').slice(0, 16))}</div></td>
            <td>${esc(r.reason || '—')}</td>
            <td class="mono">${r.used_on} line${r.used_on === 1 ? '' : 's'}</td>
            <td><span class="pill ${r.state === 'current' ? 'pill-green' : (r.state === 'upcoming' ? 'pill-blue' : 'pill-grey')}">${esc(r.state)}</span></td>
            <td><button class="btn btn-light btn-sm" data-fix="${r.id}" data-rate="${r.rate_km}">Correct</button></td>
        </tr>`).join('') || '<tr><td colspan="8" class="empty">No rate has ever been set for this size.</td></tr>';
        document.querySelectorAll('[data-fix]').forEach(x => x.addEventListener('click', () => {
            document.getElementById('x_id').value = x.dataset.fix;
            document.getElementById('x_rate').value = x.dataset.rate;
            document.getElementById('x_reason').value = '';
            document.getElementById('x_what').textContent = 'Currently ' + money(x.dataset.rate) + ' per km for ' + (b ? b.name : code) + '.';
            hide('#m_hist'); show('#m_fix');
        }));
        show('#m_hist');
    }
    document.getElementById('x_ok').addEventListener('click', async () => {
        const j = await post('/rate/' + document.getElementById('x_id').value + '/correct', { rate_km: document.getElementById('x_rate').value, reason: document.getElementById('x_reason').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_fix'); BOARD = j.data; render(); toast('success', j.message);
    });

    load();
})();
</script>
</body>
</html>
