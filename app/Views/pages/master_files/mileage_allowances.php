<?= view('layouts/head') ?>
<style>
    /* ---------- Mileage Allowances (same tokens as the Tour Inquiry panel) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#b7791f; --tf-amber-soft:#fdf3e1;
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
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl td { padding:9px 10px; border-bottom:1px solid var(--tf-line); vertical-align:middle; }
    .tf .tbl tr:last-child td { border-bottom:0; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .kmin { width:110px; display:inline-block; }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .rowdel { color:var(--tf-red); }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar', ['tourdesk_desk_only' => service('request')->getGet('desk') === 'transport' ? 'transport' : null]) /* tf-transdesk-r1: opened from the Transport Desk (?desk=transport) the strip shows that desk's functions; otherwise unchanged */ ?>
                <?= view('layouts/tgs_bar') ?>

                <div class="tf-title">
                    <div>
                        <h4>Mileage &amp; Allowances <span class="help">· Transport &amp; Guide Services</span></h4>
                        <div class="sub">Kilometres that are charged without being driven between two places: a day the party stays at the hotel, the driver's run to and from the garage, and a day spent in and around one city. Every figure is typed here and changed here, by a person. The price of a kilometre is a separate matter — that is Transport Rates. These are the <b>mileage</b> allowances of the schedule, charged at the vehicle's rate per kilometre; the crew's daily figures — the standard bata, guide fee and each person's agreed figure, what is <b>paid</b> — are on <a href="<?= base_url('master_files/crew_people') ?>">Drivers &amp; Guides</a>, and the quotation's costing bata under <a href="<?= base_url('master_files/crew_bata') ?>">Crew Roles &amp; Quotation Costing</a>.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Inquiry tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div class="tf-card mb-3">
                    <div class="tf-card-h"><h6>Quotation mileage</h6><span class="help">Which of a leg's two figures the quotation is costed on</span></div>
                    <div class="tf-card-b">
                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div class="form-check"><input class="form-check-input" type="radio" name="q_source" id="q_google" value="google"><label class="form-check-label" for="q_google"><b>Google's road distance</b> <span class="help">— asked once per pair of places and kept. It has a figure for a sector nobody has driven yet, and it tends to sit above the driven record, which is the buffer wanted in the price.</span></label></div>
                                <div class="form-check mt-1"><input class="form-check-input" type="radio" name="q_source" id="q_actual" value="actual"><label class="form-check-label" for="q_actual"><b>The driven record</b> <span class="help">— the middle of what the company itself recorded between the two places on past tours. Google's figure is still asked for and shown beside it.</span></label></div>
                                <div class="help mt-2">Both figures are kept on every driven leg whichever is chosen, and a distance typed by hand stands over both. A change applies to inquiries planned from now on; an inquiry already planned keeps its figures until it is planned again or Ask Google is pressed on it.</div>
                                <button class="btn btn-primary btn-sm mt-2" id="q_save">Save</button>
                            </div>
                            <div class="col-lg-5">
                                <div id="q_key" class="help"></div>
                                <div class="row g-2 mt-1 align-items-end">
                                    <div class="col-5"><label class="form-label help">Try a lookup — from</label><input class="form-control form-control-sm" id="q_from" placeholder="Colombo"></div>
                                    <div class="col-5"><label class="form-label help">to</label><input class="form-control form-control-sm" id="q_to" placeholder="Kandy"></div>
                                    <div class="col-2"><button class="btn btn-light btn-sm w-100" id="q_test" title="Ask Google for this one pair, to see the key works">Ask</button></div>
                                </div>
                                <div id="q_answer" class="help mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>Fixed allowances</h6><span class="help">Per day, or once at each end of the tour</span></div>
                            <div class="tf-card-b" style="overflow-x:auto">
                                <table class="tbl" id="fixed"><thead><tr><th>Allowance</th><th>Kilometres</th><th>Applies</th><th></th></tr></thead><tbody></tbody></table>
                                <div class="help mt-2">A change applies to inquiries worked out from now on. Legs already on an inquiry keep their figure until that inquiry's mileage is re-read, and a leg somebody typed by hand is never touched.</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>City tour allowances</h6><span class="help">A fixed mileage for a day out in one town</span></div>
                            <div class="tf-card-b" style="overflow-x:auto">
                                <table class="tbl" id="cities"><thead><tr><th>City / town</th><th>Kilometres</th><th></th></tr></thead><tbody></tbody></table>
                                <div class="row g-2 mt-2 align-items-end">
                                    <div class="col-6"><label class="form-label help">Add a city</label><input class="form-control form-control-sm" id="c_city" placeholder="e.g. Ella"></div>
                                    <div class="col-3"><label class="form-label help">Kilometres</label><input type="number" min="0" step="0.1" class="form-control form-control-sm" id="c_km"></div>
                                    <div class="col-3"><button class="btn btn-primary btn-sm w-100" id="c_add">Add</button></div>
                                </div>
                                <div class="help mt-2" id="c_note">This is the same list as Master Files → Extra Kilometers; a change here shows there and the other way round, so there is one list of cities and not two. A day out in a town not on the list is flagged on the inquiry rather than given a figure.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($allowances_url ?? 'master_files/mileage_allowances') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2600 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With':'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const APPLIES = { stay_day: 'each stay day', both_ends: 'start and end of the tour', tour_start: 'start of the tour', tour_end: 'end of the tour' };

    function renderFixed(rows) {
        document.querySelector('#fixed tbody').innerHTML = rows.map(a => `<tr>
            <td><b>${esc(a.name)}</b>${a.note ? `<div class="help">${esc(a.note)}</div>` : ''}${a.updated_by ? `<div class="help">last set by ${esc(a.updated_by)} · ${esc((a.updated_at || '').slice(0, 16))}</div>` : ''}</td>
            <td><input type="number" min="0" step="0.1" class="form-control form-control-sm kmin mono" data-km="${esc(a.code)}" value="${a.km}"></td>
            <td><span class="pill pill-blue">${esc(APPLIES[a.applies] || a.applies)}</span>
                <div class="form-check form-switch mt-1"><input class="form-check-input" type="checkbox" data-on="${esc(a.code)}" id="on_${esc(a.code)}" ${a.active ? 'checked' : ''}><label class="form-check-label help" for="on_${esc(a.code)}">${a.active ? 'in use' : 'switched off'}</label></div></td>
            <td><button class="btn btn-light btn-sm" data-save="${esc(a.code)}">Save</button></td>
        </tr>`).join('') || '<tr><td colspan="4" class="empty">No allowances are set up.</td></tr>';

        document.querySelectorAll('[data-save]').forEach(b => b.addEventListener('click', async () => {
            const code = b.dataset.save;
            const j = await post('/allowance/' + encodeURIComponent(code), {
                km: document.querySelector(`[data-km="${code}"]`).value,
                active: document.querySelector(`[data-on="${code}"]`).checked ? 1 : 0
            });
            if (j.status !== 200) { toast('error', j.message); return; }
            renderFixed(j.data); toast('success', j.message);
        }));
    }

    function renderCities(rows) {
        document.querySelector('#cities tbody').innerHTML = rows.map(c => `<tr>
            <td><input class="form-control form-control-sm" data-cname="${c.id}" value="${esc(c.city)}"></td>
            <td><input type="number" min="0" step="0.1" class="form-control form-control-sm kmin mono" data-ckm="${c.id}" value="${c.km}"></td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-csave="${c.id}">Save</button>
                <button class="btn btn-light btn-sm rowdel" data-cdel="${c.id}" title="Remove">&times;</button></td>
        </tr>`).join('') || '<tr><td colspan="3" class="empty">No cities yet. Add the first one below.</td></tr>';

        document.querySelectorAll('[data-csave]').forEach(b => b.addEventListener('click', async () => {
            const id = b.dataset.csave;
            const j = await post('/city/' + id, { city: document.querySelector(`[data-cname="${id}"]`).value, km: document.querySelector(`[data-ckm="${id}"]`).value });
            if (j.status !== 200) { toast('error', j.message); return; }
            renderCities(j.data); toast('success', j.message);
        }));
        document.querySelectorAll('[data-cdel]').forEach(b => b.addEventListener('click', async () => {
            const ok = await Swal.fire({ text: 'Are you sure you want to delete this record? A day out in this city is then flagged on the inquiry instead of given a figure.', icon: 'question', showCancelButton: true, confirmButtonText: 'Remove the city', cancelButtonText: 'Cancel' });
            if (!ok.isConfirmed) return;
            const j = await post('/city/' + b.dataset.cdel, { delete: 1 });
            if (j.status !== 200) { toast('error', j.message); return; }
            renderCities(j.data); toast('success', j.message);
        }));
    }

    // which figure the quotation is costed on, and whether Google can be asked at all
    function renderQuote(q) {
        if (!q) return;
        const el = document.getElementById(q.source === 'actual' ? 'q_actual' : 'q_google');
        if (el) el.checked = true;
        document.getElementById('q_key').innerHTML = q.google_key
            ? `<span class="pill pill-green">Google key set</span> ${q.cached} pair${Number(q.cached) === 1 ? '' : 's'} of places asked and kept so far.${q.table ? '' : ' <span class="pill pill-red">the answers table is missing — re-run Install on Tour Inquiry</span>'}`
            : `<span class="pill pill-grey">Google off</span> ${esc(q.key_problem || 'No Google Maps key is set.')} Until then every leg is quoted from the driven record and says so.`;
    }
    document.getElementById('q_save').addEventListener('click', async () => {
        const src = document.querySelector('input[name="q_source"]:checked')?.value;
        const j = await post('/source', { source: src });
        if (j.status !== 200) { toast('error', j.message); return; }
        renderQuote(j.data); toast('success', j.message);
    });
    document.getElementById('q_test').addEventListener('click', async () => {
        const out = document.getElementById('q_answer');
        out.textContent = 'Asking Google…';
        const j = await post('/google_test', { from: document.getElementById('q_from').value, to: document.getElementById('q_to').value });
        out.innerHTML = j.status === 200
            ? `<span class="pill pill-green">${Number(j.data.km).toLocaleString()} km</span> ${esc(j.message)}`
            : `<span class="pill pill-red">no figure</span> ${esc(j.message)}`;
        if (j.data && j.data.cached != null) { const k = document.getElementById('q_key'); if (k && j.status === 200) { const d = await api('/data'); if (d.status === 200) renderQuote(d.data.quote); } }
    });

    document.getElementById('c_add').addEventListener('click', async () => {
        const j = await post('/city', { city: document.getElementById('c_city').value, km: document.getElementById('c_km').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        renderCities(j.data); toast('success', j.message);
        document.getElementById('c_city').value = ''; document.getElementById('c_km').value = '';
    });

    (async () => {
        const j = await api('/data');
        if (j.status !== 200) { toast('error', j.message); return; }
        renderFixed(j.data.fixed);
        renderCities(j.data.cities);
        renderQuote(j.data.quote);
        if (!j.data.has_city_master) {
            document.getElementById('c_note').textContent = 'The extra_kilometers master is not on this install, so city allowances cannot be kept here.';
        }
    })();
})();
</script>
</body>
</html>
