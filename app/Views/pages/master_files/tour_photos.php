<?= view('layouts/head') ?>
<style>
    /* ---------- Tour Photos (same tokens as the Tour Inquiry panel) ---------- */
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
    .tf .tbl td { padding:8px 10px; border-bottom:1px solid var(--tf-line); vertical-align:middle; }
    .tf .tbl tr:last-child td { border-bottom:0; }
    .tf .tbl tr.off td { opacity:.5; }
    .tf .tbl tbody tr:hover { background:var(--tf-bg); }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .rowdel { color:#b6bfca; padding:0 6px; line-height:1.1; font-size:15px; }
    .tf .rowdel:hover { color:var(--tf-red); background:var(--tf-red-soft); }
    .tf .chip { display:inline-block; font-size:11.5px; border-radius:20px; padding:3px 10px; border:1px solid var(--tf-line); background:#fff; cursor:pointer; margin:0 5px 5px 0; user-select:none; }
    .tf .chip.on { background:var(--tf-navy); color:#fff; border-color:var(--tf-navy); }
    .tf .chip small { opacity:.7; }
    .tf .drop { border:2px dashed var(--tf-line); border-radius:10px; padding:16px; text-align:center; color:var(--tf-muted); font-size:12.5px; background:var(--tf-bg); cursor:pointer; }
    .tf .drop.over { border-color:var(--tf-teal); background:var(--tf-teal-soft); }
    .tf .picked { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
    .tf .picked img { width:64px; height:48px; object-fit:cover; border-radius:5px; border:1px solid var(--tf-line); }
    .tf .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:12px; }
    .tf .ph { border:1px solid var(--tf-line); border-radius:10px; overflow:hidden; background:#fff; position:relative; display:flex; flex-direction:column; }
    .tf .ph.off { opacity:.55; }
    .tf .ph img { width:100%; height:128px; object-fit:cover; display:block; background:var(--tf-bg); cursor:zoom-in; }
    .tf .ph .star { position:absolute; top:6px; left:8px; font-size:18px; color:#c9d1db; cursor:pointer; text-shadow:0 0 4px #fff; line-height:1; }
    .tf .ph .star.on { color:#f0b429; }
    .tf .ph .body { padding:8px 10px; font-size:12px; flex:1; }
    .tf .ph .body .cap { font-weight:600; font-size:12.5px; margin-bottom:2px; }
    .tf .ph .tags { margin-top:4px; }
    .tf .ph .tags .pill { margin:0 3px 3px 0; }
    .tf .ph .acts { display:flex; gap:4px; padding:6px 8px 8px; border-top:1px solid var(--tf-line); flex-wrap:wrap; }
    .tf .ph .acts .btn { padding:2px 7px; font-size:11.5px; }
    .tf .status { font-size:12px; color:var(--tf-muted); text-align:right; }
    .tf #b_upload { font-size:15px; padding:10px 22px; white-space:nowrap; }
    .tf #b_upload i { margin-right:4px; }
    .tf .tf-card.lit { box-shadow:0 0 0 3px var(--tf-teal-soft), 0 0 0 5px var(--tf-teal); transition:box-shadow .3s ease; }
    .tf .progress-note { font-size:12px; color:var(--tf-ink); }
    .tf .status b.ok { color:var(--tf-green); }
    .tf .status b.bad { color:var(--tf-red); }
    #mv_img { max-width:100%; max-height:60vh; display:block; margin:0 auto; border-radius:8px; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>

                <div class="tf-title">
                    <div>
                        <h4>Tour Photos <span class="help">· Admin</span></h4>
                        <div class="sub">The one place the pictures for client quotations are kept: three to five good photos for each destination, attraction or activity — Sigiriya, Dambulla, Kandy, Tea Country, Safari, Galle, Colombo, the beaches — each tagged to the places it shows, the best marked preferred (★). When a quotation is prepared, every day of the itinerary gets a picture from here that fits the day's programme — preferred first, the least-used next, never the same picture twice on one itinerary — and staff can change any day's picture on the inquiry. Upload, preview, replace, take out and add at any time; a new destination is a new tag. Nothing here is a cost or a rate.</div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-2">
                        <?php if (!empty($installed)): ?><button class="btn btn-primary btn-lg" id="b_upload" type="button" title="Choose photos from your computer or phone and add them to the library"><i class="bi bi-cloud-upload"></i> Upload Photos</button><?php endif; ?>
                        <div class="status" id="st"></div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Inquiry tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="tf-card" id="u_card">
                            <div class="tf-card-h"><h6>Upload photos</h6><span class="help" id="u_limit">JPEG, PNG or WebP · up to 12 MB each</span></div>
                            <div class="tf-card-b">
                                <label class="form-label help">Tag them to the places they show <span class="text-muted">— one or more</span></label>
                                <div id="u_tags"></div>
                                <div class="drop" id="u_drop">Drop photos here, or <u>choose files</u><input type="file" id="u_files" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden></div>
                                <div class="picked" id="u_picked"></div>
                                <div class="row g-2 mt-1">
                                    <div class="col-12"><label class="form-label help">Caption <span class="text-muted">— shown under the picture on the quotation</span></label><input class="form-control form-control-sm" id="u_caption" placeholder="Sigiriya Rock Fortress at sunrise" maxlength="190"></div>
                                    <div class="col-12"><label class="form-label help">Credit <span class="text-muted">— photographer or source, for the record; not printed</span></label><input class="form-control form-control-sm" id="u_credit" placeholder="optional" maxlength="120"></div>
                                    <div class="col-12"><div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="u_pref">
                                        <label class="form-check-label" for="u_pref" style="font-size:12.5px">Preferred — offered before the others for these places</label></div></div>
                                </div>
                                <div class="d-flex gap-2 mt-3 align-items-center">
                                    <button class="btn btn-primary btn-sm" id="u_go"><i class="bi bi-cloud-upload"></i> Upload Photos</button>
                                    <span class="help" id="u_note"></span>
                                </div>
                                <div class="help mt-2">Each picture is resized for the quotation, turned the right way up if the phone stored it on its side, and given a thumbnail; the original stays on the sender's computer. Several files chosen together go up one after another and share the tags, caption and preferred mark — the caption can be changed on each afterwards.</div>
                            </div>
                        </div>

                        <div class="tf-card">
                            <div class="tf-card-h"><h6 id="t_title">Places &amp; attractions</h6><button class="btn btn-light btn-sm" id="t_new" hidden>New</button></div>
                            <div class="tf-card-b">
                                <div class="row g-2">
                                    <div class="col-5"><label class="form-label help">Code</label><input class="form-control form-control-sm mono" id="t_code" placeholder="YALA" maxlength="30"></div>
                                    <div class="col-7"><label class="form-label help">Name</label><input class="form-control form-control-sm" id="t_name" placeholder="Yala" maxlength="120"></div>
                                    <div class="col-5"><label class="form-label help">Kind</label>
                                        <select class="form-select form-select-sm" id="t_kind"><option value="destination">Destination</option><option value="attraction">Attraction</option><option value="activity">Activity</option><option value="general">General</option></select></div>
                                    <div class="col-7"><label class="form-label help">Order</label><input type="number" class="form-control form-control-sm" id="t_sort" placeholder="10, 20, 30…"></div>
                                    <div class="col-12"><label class="form-label help">Words a day's programme uses for it <span class="text-muted">— comma separated</span></label>
                                        <textarea class="form-control form-control-sm" id="t_words" rows="2" placeholder="yala, yala national park, yala safari, tissamaharama, tissa"></textarea></div>
                                    <div class="col-12"><div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="t_active" checked>
                                        <label class="form-check-label" for="t_active" style="font-size:12.5px">Active — matched and offered</label></div></div>
                                </div>
                                <div class="d-flex gap-2 mt-3"><button class="btn btn-primary btn-sm" id="t_save"><i class="bi bi-check2"></i> Save place</button></div>
                                <div class="help mt-2">A day is matched on these words in its area, hotel line, visits and excursions — whole words only, so "galle" does not catch "gallery". The area of the day counts double. A new destination is a new place here; the code is the key and stays.</div>
                            </div>
                            <div style="overflow-x:auto;border-top:1px solid var(--tf-line)">
                                <table class="tbl"><thead><tr><th>Place</th><th>Kind</th><th>Photos</th><th></th></tr></thead>
                                <tbody id="t_list"><tr><td colspan="4" class="empty">Loading…</td></tr></tbody></table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="tf-card">
                            <div class="tf-card-h"><h6>Library</h6>
                                <div class="d-flex gap-2 align-items-center flex-wrap">
                                    <span class="help" id="l_count"></span>
                                    <input class="form-control form-control-sm" id="l_q" placeholder="find by caption" style="width:170px">
                                    <div class="form-check form-switch m-0" title="Pictures taken out of the library, kept for the record">
                                        <input class="form-check-input" type="checkbox" role="switch" id="l_removed"><label class="form-check-label help" for="l_removed">Show removed</label></div>
                                </div></div>
                            <div class="tf-card-b" style="padding-bottom:6px" id="l_chips"></div>
                            <div class="tf-card-b" style="padding-top:0"><div class="grid" id="l_grid"><div class="empty">Loading…</div></div></div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- one picture: preview, caption, tags, preferred, replace, remove -->
<div class="modal fade tf" id="m_view" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mv_title">Photo</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <img id="mv_img" src="" alt="">
        <div class="help mt-2" id="mv_meta"></div>
        <div class="row g-2 mt-1">
            <div class="col-md-8"><label class="form-label help">Caption</label><input class="form-control form-control-sm" id="mv_caption" maxlength="190"></div>
            <div class="col-md-4"><label class="form-label help">Order within its places</label><input type="number" class="form-control form-control-sm" id="mv_sort"></div>
            <div class="col-md-8"><label class="form-label help">Credit</label><input class="form-control form-control-sm" id="mv_credit" maxlength="120"></div>
            <div class="col-md-4"><label class="form-label help">&nbsp;</label><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="mv_pref"><label class="form-check-label" for="mv_pref" style="font-size:12.5px">Preferred ★</label></div></div>
            <div class="col-12"><label class="form-label help">Places it shows</label><div id="mv_tags"></div></div>
        </div>
        <input type="hidden" id="mv_id">
    </div>
    <div class="modal-footer">
        <label class="btn btn-light btn-sm me-auto" title="A better picture under this same entry: every quotation carrying it shows the new one"><i class="bi bi-arrow-repeat"></i> Replace picture<input type="file" id="mv_replace" accept="image/jpeg,image/png,image/webp,image/gif" hidden></label>
        <button class="btn btn-light btn-sm rowdel" id="mv_remove" title="Out of the library">Remove</button>
        <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary btn-sm" id="mv_save">Save</button>
    </div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'master_files/tour_photos') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2400 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const send = (path, form) => api(path, { method: 'POST', body: form });   // multipart: the browser sets the boundary
    const $ = id => document.getElementById(id);
    const show = sel => { const el = document.querySelector(sel); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : new bootstrap.Modal(el)).show(); };
    const hide = sel => { const i = bootstrap.Modal.getInstance(document.querySelector(sel)); if (i) i.hide(); };
    let D = { tags: [], photos: [], kinds: [] };
    let FILTER = '';          // the tag the library grid is narrowed to
    let FILES = [];           // the files picked for upload
    const KIND_WORD = { destination: 'Destination', attraction: 'Attraction', activity: 'Activity', general: 'General' };

    // ---- the chips: one per active place, clickable ----
    function chips(container, selected, withCounts, onToggle) {
        const el = $(container);
        el.innerHTML = D.tags.filter(t => Number(t.active) === 1).map(t => `<span class="chip ${selected.includes(t.code) ? 'on' : ''}" data-code="${esc(t.code)}">${esc(t.name)}${withCounts ? ` <small>${t.photos}</small>` : ''}</span>`).join('')
            || '<span class="help">No places yet — add one below.</span>';
        el.querySelectorAll('[data-code]').forEach(c => c.addEventListener('click', () => onToggle(c.dataset.code, c)));
    }
    const chosen = container => [...document.querySelectorAll('#' + container + ' .chip.on')].map(c => c.dataset.code);

    // ---- upload ----
    function renderPicked() {
        $('u_picked').innerHTML = FILES.map((f, i) => `<span title="${esc(f.name)} · ${Math.round(f.size / 1024)} KB"><img src="${URL.createObjectURL(f)}" alt="" data-unpick="${i}"></span>`).join('');
        $('u_picked').querySelectorAll('[data-unpick]').forEach(img => img.addEventListener('click', () => { FILES.splice(Number(img.dataset.unpick), 1); renderPicked(); }));
        $('u_note').textContent = FILES.length ? FILES.length + ' file' + (FILES.length === 1 ? '' : 's') + ' ready' : '';
    }
    const addFiles = list => { for (const f of list) { if (f && /^image\//.test(f.type)) FILES.push(f); } renderPicked(); goToCard(); };
    // the card brought into view and lit for a moment, so nobody has to hunt for where the photos went
    function goToCard() {
        const c = $('u_card'); if (!c) return;
        c.scrollIntoView({ behavior: 'smooth', block: 'start' });
        c.classList.add('lit'); setTimeout(() => c.classList.remove('lit'), 1800);
    }
    $('b_upload')?.addEventListener('click', () => { goToCard(); $('u_files').click(); });
    $('u_drop').addEventListener('click', () => $('u_files').click());
    $('u_files').addEventListener('change', () => { addFiles($('u_files').files); $('u_files').value = ''; });
    ['dragenter', 'dragover'].forEach(ev => $('u_drop').addEventListener(ev, e => { e.preventDefault(); $('u_drop').classList.add('over'); }));
    ['dragleave', 'drop'].forEach(ev => $('u_drop').addEventListener(ev, e => { e.preventDefault(); $('u_drop').classList.remove('over'); }));
    $('u_drop').addEventListener('drop', e => addFiles(e.dataTransfer.files));
    // One file per request: a phone photo is a few megabytes and the server takes them one at a
    // time, however many were chosen; each one's answer is shown, and one that fails stays in the
    // list to try again while the others go in.
    $('u_go').addEventListener('click', async () => {
        const tags = chosen('u_tags');
        if (!tags.length) { toast('error', 'Tag the photos to at least one place first.'); goToCard(); return; }
        if (!FILES.length) { toast('error', 'Choose one or more photos.'); $('u_files').click(); return; }
        const queue = FILES.slice(), added = [], failed = [], keep = [];
        $('u_go').disabled = true;
        try {
            for (let i = 0; i < queue.length; i++) {
                const f = queue[i];
                $('u_note').textContent = 'Uploading ' + (i + 1) + ' of ' + queue.length + '… ' + f.name;
                const fd = new FormData();
                fd.append('files[]', f, f.name);
                fd.append('tags', tags.join(',')); fd.append('caption', $('u_caption').value); fd.append('credit', $('u_credit').value); fd.append('preferred', $('u_pref').checked ? '1' : '0');
                try {
                    const j = await send('/upload', fd);
                    if (j.status === 200) { added.push(f.name); } else { failed.push(f.name + ': ' + (j.message || 'refused')); keep.push(f); }
                } catch (e) {
                    failed.push(f.name + ': the upload did not go through (' + e.message + ')'); keep.push(f);
                }
            }
            FILES = keep; renderPicked();
            if (added.length) { $('u_caption').value = ''; $('u_credit').value = ''; $('u_pref').checked = false; FILTER = tags[0]; }
            const msg = (added.length ? added.length + ' photo' + (added.length === 1 ? '' : 's') + ' added.' : 'Nothing was added.') + (failed.length ? ' ' + failed.join(' ') : '');
            toast(added.length && !failed.length ? 'success' : (added.length ? 'warning' : 'error'), msg);
            await load();
        } finally { $('u_go').disabled = false; $('u_note').textContent = FILES.length ? FILES.length + ' file' + (FILES.length === 1 ? '' : 's') + ' still to send' : ''; }
    });

    // ---- places (tags) ----
    function clearTagForm() {
        ['t_code', 't_name', 't_words', 't_sort'].forEach(k => $(k).value = '');
        $('t_kind').value = 'destination'; $('t_active').checked = true; $('t_code').readOnly = false;
        $('t_title').textContent = 'Places & attractions'; $('t_new').hidden = true;
    }
    function fillTagForm(code) {
        const t = D.tags.find(x => x.code === code); if (!t) return;
        $('t_code').value = t.code; $('t_code').readOnly = true; $('t_name').value = t.name; $('t_kind').value = t.kind; $('t_sort').value = t.sort_order ?? '';
        $('t_words').value = t.keywords || ''; $('t_active').checked = Number(t.active) === 1;
        $('t_title').textContent = 'Change ' + t.name; $('t_new').hidden = false;
        $('t_code').scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
    function renderTags() {
        $('t_list').innerHTML = D.tags.length ? D.tags.map(t => `<tr class="${Number(t.active) === 1 ? '' : 'off'}">
            <td><b>${esc(t.name)}</b> <span class="mono help">${esc(t.code)}</span>${Number(t.active) === 1 ? '' : ' <span class="pill pill-grey">off</span>'}<div class="help" title="${esc(t.keywords || '')}">${esc((t.keyword_list || []).slice(0, 4).join(', '))}${(t.keyword_list || []).length > 4 ? ' …' : ''}</div></td>
            <td>${esc(KIND_WORD[t.kind] || t.kind)}</td>
            <td>${t.photos ? `<span class="pill ${t.photos >= 3 ? 'pill-green' : 'pill-amber'}" title="${t.photos >= 3 ? 'a good spread' : 'three to five is the aim'}">${t.photos}</span>` : '<span class="pill pill-red" title="A day here gets no picture until one is uploaded">none</span>'}</td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-tedit="${esc(t.code)}">Edit</button> <button class="btn btn-light btn-sm rowdel" data-tdel="${esc(t.code)}" title="${t.photos ? 'Switch off' : 'Remove'}">&times;</button></td>
        </tr>`).join('') : '<tr><td colspan="4" class="empty">No places yet.</td></tr>';
        $('t_list').querySelectorAll('[data-tedit]').forEach(b => b.addEventListener('click', () => fillTagForm(b.dataset.tedit)));
        $('t_list').querySelectorAll('[data-tdel]').forEach(b => b.addEventListener('click', async () => {
            const t = D.tags.find(x => x.code === b.dataset.tdel);
            const ok = await Swal.fire({ title: t.photos ? 'Switch off ' + t.name + '?' : 'Are you sure you want to delete this record?', text: t.photos ? t.photos + ' photo(s) carry it; they keep it and their other tags. It will no longer be matched or offered.' : t.name + ' has no photos, so it is removed from the list.', showCancelButton: true, confirmButtonText: t.photos ? 'Switch off' : 'Delete' });
            if (!ok.isConfirmed) return;
            const j = await post('/tag/delete/' + encodeURIComponent(b.dataset.tdel), {});
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); clearTagForm(); load();
        }));
    }
    $('t_save').addEventListener('click', async () => {
        const j = await post('/tag', { code: $('t_code').value, name: $('t_name').value, kind: $('t_kind').value, keywords: $('t_words').value, sort_order: $('t_sort').value, active: $('t_active').checked ? 1 : 0 });
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); clearTagForm(); load();
    });
    $('t_new').addEventListener('click', clearTagForm);
    // a code from the name, when none is typed
    $('t_name').addEventListener('input', () => { if (!$('t_code').readOnly && !$('t_code').dataset.typed) $('t_code').value = $('t_name').value.trim().toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 30); });
    $('t_code').addEventListener('input', () => { $('t_code').dataset.typed = $('t_code').value ? '1' : ''; });

    // ---- the library grid ----
    function renderGrid() {
        const q = $('l_q').value.trim().toLowerCase();
        let list = D.photos.filter(p => $('l_removed').checked || Number(p.active) === 1);
        if (FILTER) list = list.filter(p => (p.tag_codes || []).includes(FILTER));
        if (q) list = list.filter(p => (p.caption || '').toLowerCase().includes(q) || (p.tags || []).some(t => (t.name || '').toLowerCase().includes(q)));
        const active = D.photos.filter(p => Number(p.active) === 1).length;
        $('l_count').textContent = active + ' photo' + (active === 1 ? '' : 's') + (D.photos.length > active ? ' · ' + (D.photos.length - active) + ' removed' : '') + (D.in_use ? ' · on ' + D.in_use + ' itinerar' + (D.in_use === 1 ? 'y' : 'ies') : '');
        $('l_grid').innerHTML = list.length ? list.map(p => `<div class="ph ${Number(p.active) === 1 ? '' : 'off'}" data-id="${p.id}">
            <span class="star ${Number(p.preferred) === 1 ? 'on' : ''}" data-star="${p.id}" title="${Number(p.preferred) === 1 ? 'Preferred — offered first' : 'Mark as preferred'}">&#9733;</span>
            <img src="${esc(p.thumb_url || p.url)}" alt="" data-view="${p.id}" title="Open">
            <div class="body">
                <div class="cap">${esc(p.caption || '—')}</div>
                <div class="help">${p.width && p.height ? p.width + '×' + p.height + ' · ' : ''}${p.bytes ? Math.round(p.bytes / 1024) + ' KB · ' : ''}${p.on_days ? 'on ' + p.on_days + ' day' + (p.on_days === 1 ? '' : 's') + ' · ' : ''}used ${p.uses}×${p.exists ? '' : ' · <span style="color:var(--tf-red)">file missing</span>'}</div>
                <div class="tags">${(p.tags || []).map(t => `<span class="pill pill-blue">${esc(t.name)}</span>`).join('')}</div>
                ${Number(p.active) === 1 ? '' : `<div class="help" style="color:var(--tf-red)">removed ${esc(String(p.removed_at || '').slice(0, 10))} by ${esc(p.removed_by || '')}</div>`}
            </div>
            <div class="acts">
                <button class="btn btn-light" data-view="${p.id}">Open</button>
                ${Number(p.active) === 1 ? `<button class="btn btn-light rowdel" data-remove="${p.id}" title="Out of the library">Remove</button>` : `<button class="btn btn-light" data-restore="${p.id}">Put back</button>`}
            </div></div>`).join('') : `<div class="empty" style="grid-column:1/-1">${FILTER ? 'No photos carry this place yet.' : 'The library is empty.'} <button class="btn btn-primary btn-sm ms-2" type="button" data-upload-here><i class="bi bi-cloud-upload"></i> Upload Photos</button></div>`;
        $('l_grid').querySelectorAll('[data-upload-here]').forEach(b => b.addEventListener('click', () => $('b_upload').click()));
        $('l_grid').querySelectorAll('[data-view]').forEach(el => el.addEventListener('click', () => openView(Number(el.dataset.view))));
        $('l_grid').querySelectorAll('[data-star]').forEach(el => el.addEventListener('click', async () => {
            const p = D.photos.find(x => x.id === Number(el.dataset.star));
            const j = await post('/photo/' + p.id + '/save', { preferred: Number(p.preferred) === 1 ? 0 : 1 });
            if (j.status !== 200) { toast('error', j.message); return; }
            load();
        }));
        $('l_grid').querySelectorAll('[data-remove]').forEach(el => el.addEventListener('click', () => removePhoto(Number(el.dataset.remove))));
        $('l_grid').querySelectorAll('[data-restore]').forEach(el => el.addEventListener('click', async () => {
            const j = await post('/photo/' + el.dataset.restore + '/restore', {});
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
    }
    async function removePhoto(id) {
        const p = D.photos.find(x => x.id === id);
        const ok = await Swal.fire({ title: 'Are you sure you want to delete this record?', text: 'The picture leaves the library' + (p && p.on_days ? ' and the ' + p.on_days + ' day(s) it is on choose another picture' : '') + '. The file is kept and the picture can be put back from "Show removed".', showCancelButton: true, confirmButtonText: 'Remove' });
        if (!ok.isConfirmed) return;
        const j = await post('/photo/' + id + '/remove', {});
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_view'); toast('success', j.message); load();
    }
    $('l_q').addEventListener('input', renderGrid);
    $('l_removed').addEventListener('change', load);

    // ---- one picture ----
    function openView(id) {
        const p = D.photos.find(x => x.id === id); if (!p) return;
        $('mv_id').value = p.id; $('mv_title').textContent = p.caption || ('Photo ' + p.id);
        $('mv_img').src = p.url; $('mv_caption').value = p.caption || ''; $('mv_credit').value = p.credit || ''; $('mv_sort').value = p.sort_order ?? ''; $('mv_pref').checked = Number(p.preferred) === 1;
        $('mv_meta').innerHTML = `${esc(p.original_name || '')} · ${p.width}×${p.height} · ${Math.round((p.bytes || 0) / 1024)} KB · uploaded ${esc(String(p.uploaded_at || '').slice(0, 16))} by ${esc(p.uploaded_by || '')}${p.replaced_at ? ' · replaced ' + esc(String(p.replaced_at).slice(0, 16)) + ' by ' + esc(p.replaced_by || '') : ''} · used ${p.uses}× on quotations${p.on_days ? ' · on ' + p.on_days + ' day(s) now' : ''}`;
        chips('mv_tags', p.tag_codes || [], false, (code, el) => el.classList.toggle('on'));
        $('mv_remove').hidden = Number(p.active) !== 1;
        show('#m_view');
        if (window.TfView) TfView.setParams({ photo: id }, { push: !TfView.param('photo') });   // tf-refresh-r1
    }
    $('mv_save').addEventListener('click', async () => {
        const tags = chosen('mv_tags');
        if (!tags.length) { toast('error', 'A photo keeps at least one place.'); return; }
        const j = await post('/photo/' + $('mv_id').value + '/save', { caption: $('mv_caption').value, credit: $('mv_credit').value, sort_order: $('mv_sort').value, preferred: $('mv_pref').checked ? 1 : 0, tags });
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_view'); toast('success', j.message); load();
    });
    $('mv_remove').addEventListener('click', () => removePhoto(Number($('mv_id').value)));
    $('mv_replace').addEventListener('change', async () => {
        const f = $('mv_replace').files[0]; if (!f) return;
        const fd = new FormData(); fd.append('file', f, f.name);
        const j = await send('/photo/' + $('mv_id').value + '/replace', fd);
        $('mv_replace').value = '';
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_view'); toast('success', j.message); load();
    });

    async function load() {
        const j = await api('/data' + ($('l_removed').checked ? '?all=1' : ''));
        if (j.status !== 200) { toast('error', j.message); return; }
        D = j.data;
        $('st').innerHTML = `Files under <span class="mono">public/${esc(D.folder)}</span> — <b class="${D.folder_ok ? 'ok' : 'bad'}">${D.folder_ok ? 'writable' : 'not writable'}</b> · resizing <b class="${D.resize ? 'ok' : 'bad'}">${D.resize ? 'on' : 'off (no image library on the server: files are kept as uploaded)'}</b>`;
        // the server's own ceiling on one upload (php.ini) when it is under the library's 12 MB
        const lim = D.upload_limit_mb != null ? Math.min(12, Number(D.upload_limit_mb)) : 12;
        $('u_limit').textContent = 'JPEG, PNG or WebP · up to ' + (Math.round(lim * 10) / 10) + ' MB each' + (lim < 12 ? ' (the server\'s limit)' : '');
        if (FILTER && !D.tags.some(t => t.code === FILTER)) FILTER = '';
        chips('u_tags', chosen('u_tags'), false, (code, el) => el.classList.toggle('on'));
        filterChips();
        renderTags();
        renderGrid();
    }
    function filterChips() {
        chips('l_chips', FILTER ? [FILTER] : [], true, code => { FILTER = FILTER === code ? '' : code; filterChips(); renderGrid(); });
    }
    // tf-refresh-r1: the record open in the window is in the address (?photo=N) — a refresh opens it again, read fresh; Back closes it
    document.getElementById('m_view').addEventListener('hidden.bs.modal', () => { if (window.TfView && TfView.param('photo')) TfView.setParams({ photo: null }, { push: true }); });
    window.addEventListener('popstate', () => {
        const v = Number(new URLSearchParams(location.search).get('photo') || 0);
        const m = document.getElementById('m_view');
        if (v) { openView(v); } else if (m.classList.contains('show')) { bootstrap.Modal.getInstance(m)?.hide(); }
    });
    Promise.resolve(load()).then(() => { const v = Number(new URLSearchParams(location.search).get('photo') || 0); if (v) openView(v); });
})();
</script>
</body>
</html>
