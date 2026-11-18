<?= view('layouts/head') ?>
<style>
    /* ---------- Photo Master (tf-photomaster-r1) — Avenir purple #8b1d40 headers, green #78b848 actions ---------- */
    .pm { --p:#8b1d40; --pd:#60122b; --g:#78b848; --gd:#5f9a34; --ln:#e3dbe6; --sf:#f8f2f4; color:#1A1A1A; font-size:13px; }
    .pm h4 { margin:0; font-size:22px; font-weight:800; color:#8b1d40; }
    .pm .sub { font-size:12.5px; max-width:760px; }
    .pm .top { display:flex; align-items:flex-start; gap:14px; flex-wrap:wrap; margin-bottom:14px; } .pm .top .sp { flex:1; }
    .pm .btn-g { display:inline-flex; align-items:center; gap:7px; background:#78b848; border:1px solid #78b848; color:#fff; font-weight:700; border-radius:6px; padding:7px 14px; font-size:13px; cursor:pointer; }
    .pm .btn-g:hover { background:#5f9a34; } .pm .btn-l { display:inline-flex; align-items:center; gap:7px; background:#fff; border:1px solid var(--ln); color:#1A1A1A; font-weight:600; border-radius:6px; padding:6px 12px; font-size:12.5px; cursor:pointer; }
    .pm .btn-l:hover { border-color:#8b1d40; color:#8b1d40; } .pm .btn-r { color:#a3123a; } .pm button[disabled] { opacity:.45; cursor:not-allowed; }
    .pm .search { display:flex; gap:8px; align-items:center; background:#fff; border:1px solid var(--ln); border-radius:8px; padding:6px 12px; min-width:320px; }
    .pm .search input { border:0; outline:0; flex:1; font-size:13px; background:transparent; }
    .pm .wrap { display:grid; grid-template-columns:230px 1fr; gap:16px; align-items:start; }
    .pm .side { background:#fff; border:1px solid var(--ln); border-radius:10px; overflow:hidden; position:sticky; top:80px; }
    .pm .side .h { background:#8b1d40; color:#fff; font-weight:700; padding:9px 14px; }
    .pm .cat { display:flex; align-items:center; gap:10px; padding:9px 14px; border-top:1px solid var(--ln); cursor:pointer; font-weight:600; }
    .pm .cat i { color:#8b1d40; font-size:16px; } .pm .cat .n { margin-left:auto; background:var(--sf); border-radius:10px; padding:0 8px; font-size:11.5px; }
    .pm .cat.on { background:var(--sf); color:#8b1d40; box-shadow:inset 3px 0 0 #8b1d40; }
    .pm .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:14px; }
    .pm .col { background:#fff; border:1px solid var(--ln); border-radius:12px; overflow:hidden; cursor:pointer; transition:box-shadow .15s; }
    .pm .col:hover { box-shadow:0 6px 18px rgba(139,29,64,.15); border-color:#8b1d40; }
    .pm .col .ph { height:130px; background:#f3ecee center/cover; position:relative; display:flex; align-items:center; justify-content:center; color:#8b1d40; }
    .pm .col .ph .k { position:absolute; left:8px; top:8px; background:#fff; color:#8b1d40; border-radius:10px; padding:1px 8px; font-size:11px; font-weight:700; }
    .pm .col .ph .cnt { position:absolute; right:8px; bottom:8px; background:rgba(0,0,0,.6); color:#fff; border-radius:10px; padding:1px 8px; font-size:11px; }
    .pm .col .b { padding:9px 12px; } .pm .col .b b { display:block; font-size:14px; color:#60122b; } .pm .col .b small { font-size:11.5px; }
    .pm .col.off { opacity:.55; }
    .pm .panel { background:#fff; border:1px solid var(--ln); border-radius:12px; overflow:hidden; }
    .pm .panel > .h { background:#8b1d40; color:#fff; padding:12px 18px; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .pm .panel > .h h5 { margin:0; font-size:18px; font-weight:800; color:#fff; } .pm .panel > .h .sp { flex:1; } .pm .panel > .h .chip { background:rgba(255,255,255,.18); border-radius:12px; padding:1px 10px; font-size:12px; }
    .pm .panel > .h .btn-l { background:rgba(255,255,255,.12); color:#fff; border-color:rgba(255,255,255,.4); }
    .pm .meta { display:flex; gap:22px; flex-wrap:wrap; padding:10px 18px; background:var(--sf); border-bottom:1px solid var(--ln); font-size:12.5px; } .pm .meta b { color:#60122b; }
    .pm .drop { margin:14px 18px; border:2px dashed #cca8b3; border-radius:12px; padding:18px; text-align:center; background:#fdfafb; }
    .pm .drop.on { border-color:#78b848; background:#f3faec; } .pm .drop i { font-size:28px; color:#8b1d40; }
    .pm .drop .row2 { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-top:12px; text-align:left; }
    .pm label { display:block; font-size:11.5px; font-weight:600; margin:0 0 2px; }
    .pm input.f, .pm select.f, .pm textarea.f { width:100%; border:1px solid var(--ln); border-radius:6px; padding:5px 9px; font-size:13px; background:#fff; color:#1A1A1A; }
    .pm .photos { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:12px; padding:0 18px 18px; }
    .pm .pc { border:1px solid var(--ln); border-radius:10px; overflow:hidden; background:#fff; }
    .pm .pc .im { height:130px; background:#eee center/cover; cursor:zoom-in; position:relative; }
    .pm .pc .im .star { position:absolute; left:8px; top:8px; background:#78b848; color:#fff; border-radius:10px; padding:1px 8px; font-size:11px; font-weight:700; }
    .pm .pc .t { padding:7px 10px; font-size:12px; } .pm .pc .t b { display:block; font-size:12.5px; } .pm .pc .t small { display:block; font-size:11px; }
    .pm .pc .a { display:flex; gap:4px; padding:0 8px 8px; flex-wrap:wrap; } .pm .pc .a button { font-size:11.5px; padding:3px 8px; }
    .pm .pc.off { opacity:.5; }
    .pm .empty { padding:30px; text-align:center; }
    .pm .warn { background:#fdeccb; color:#6b3a00; border-radius:8px; padding:8px 12px; margin-bottom:12px; font-size:12.5px; }
    .pm-ov { position:fixed; inset:0; background:rgba(20,10,25,.6); z-index:2000; display:none; align-items:flex-start; justify-content:center; padding:40px 16px; overflow:auto; }
    .pm-ov.on { display:flex; } .pm-md { background:#fff; border-radius:12px; width:min(980px,100%); overflow:hidden; color:#1A1A1A; font-size:13px; }
    .pm-md .h { background:#8b1d40; color:#fff; padding:11px 16px; display:flex; align-items:center; } .pm-md .h b { flex:1; font-size:15px; } .pm-md .h button { background:none; border:0; color:#fff; font-size:22px; cursor:pointer; }
    .pm-md .bd { padding:14px 16px; } .pm-md .two { display:grid; grid-template-columns:1.4fr 1fr; gap:16px; } .pm-md img { width:100%; border-radius:8px; display:block; }
    .pm-md .fl { margin-bottom:8px; } .pm-md .ft { padding:10px 16px; border-top:1px solid #eee; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap; }
    .pm-md .lbl { display:block; font-size:11.5px; font-weight:600; margin-bottom:2px; } .pm-md .f { width:100%; border:1px solid #e3dbe6; border-radius:6px; padding:5px 9px; font-size:13px; }
    .pm-md .chips span { display:inline-block; border:1px solid #e3dbe6; border-radius:14px; padding:2px 10px; margin:0 4px 4px 0; font-size:12px; cursor:pointer; } .pm-md .chips span.on { background:#8b1d40; color:#fff; border-color:#8b1d40; }
    .pm-md .btn-g { background:#78b848; border:1px solid #78b848; color:#fff; font-weight:700; border-radius:6px; padding:7px 14px; cursor:pointer; } .pm-md .btn-l { background:#fff; border:1px solid #e3dbe6; border-radius:6px; padding:6px 12px; cursor:pointer; }
    .pm-toast { position:fixed; right:20px; bottom:20px; z-index:2100; background:#60122b; color:#fff; padding:10px 16px; border-radius:8px; display:none; max-width:420px; } .pm-toast.bad { background:#a3123a; }
    @media (max-width:900px) { .pm .wrap { grid-template-columns:1fr; } .pm .side { position:static; } .pm .drop .row2, .pm-md .two { grid-template-columns:1fr; } }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf pm" id="tf">
            <div class="container-fluid">
                <?= view('layouts/tourdesk_bar') ?>
                <div class="top">
                    <div><h4><i class="bi bi-images"></i> Photo Master</h4>
                        <div class="sub">Photographs for destinations, sightseeing, hotels, activities, airports and the homepage. Itineraries, quotations, quotation PDFs and the homepage take their pictures from here: the preferred photograph of a collection first, never the same picture twice in one itinerary.</div></div>
                    <span class="sp"></span>
                    <div class="search"><i class="bi bi-search"></i><input id="pm_q" placeholder="Search destination, hotel, activity, caption, photographer…"></div>
                    <button class="btn-g" id="pm_new"><i class="bi bi-plus-lg"></i> New collection</button>
                </div>
                <?php if (empty($installed)): ?><div class="warn">The Tour Desk tables are not at the current version. Open Inquiries and press Install first.</div><?php endif; ?>
                <div id="pm_warn"></div>
                <div class="wrap"><div class="side" id="pm_side"><div class="h">Categories</div></div><div id="pm_main"><div class="empty">Loading…</div></div></div>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>
<div class="pm-ov" id="pm_ov"><div class="pm-md" id="pm_md"></div></div>
<div class="pm-toast" id="pm_toast"></div>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'master_files/tour_photos') ?>';
    const ICON = { all: 'bi-grid', destination: 'bi-geo-alt', attraction: 'bi-bank', hotel: 'bi-building', activity: 'bi-bicycle', airport: 'bi-airplane', homepage: 'bi-house', general: 'bi-folder' };
    const S = { d: null, cat: 'all', q: '', open: null, removed: false };
    const $ = id => document.getElementById(id);
    const esc = v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const toast = (m, bad) => { const t = $('pm_toast'); t.textContent = m; t.className = 'pm-toast' + (bad ? ' bad' : ''); t.style.display = 'block'; clearTimeout(toast.h); toast.h = setTimeout(() => { t.style.display = 'none'; }, 5000); };
    const post = async (url, body) => { const isForm = body instanceof FormData; const r = await fetch(BASE + url, { method: 'POST', headers: isForm ? { 'X-Requested-With': 'XMLHttpRequest' } : { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' }, body: isForm ? body : JSON.stringify(body || {}) }); try { return await r.json(); } catch (e) { return { status: r.status, message: 'The server did not answer (' + r.status + ').' }; } };
    async function load() { const j = await (await fetch(BASE + '/data?all=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).json(); if (j.status !== 200) { $('pm_main').innerHTML = `<div class="empty">${esc(j.message || 'Photo Master could not be read.')}</div>`; return; } S.d = j.data; draw(); }
    const hotelName = id => { const h = (S.d.hotels || []).find(x => Number(x.id) === Number(id)); return h ? h.name : ''; };
    const photosOf = code => S.d.photos.filter(p => p.tag_codes.includes(code) && (S.removed || p.active));
    const coverOf = code => S.d.photos.filter(p => p.active && p.tag_codes.includes(code))[0] || null;
    const KN = k => (S.d.kind_names || {})[k] || k;
    function match(t) {
        if (!S.q) return true;
        const q = S.q.toLowerCase();
        const hay = [t.name, t.code, t.keywords, t.location, KN(t.kind), hotelName(t.hotel_id)].join(' ').toLowerCase();
        if (hay.includes(q)) return true;
        return photosOf(t.code).some(p => [p.caption, p.credit, p.licence, p.original_name].join(' ').toLowerCase().includes(q));
    }
    function draw() {
        const d = S.d, tags = d.tags;
        const w = [];
        if (!d.resize) w.push('This server has no image library, so photos are kept at the size they are uploaded.');
        if (!d.can_edit) w.push('You can look at Photo Master; uploading, editing and deleting need the Photo Master rights.');
        $('pm_warn').innerHTML = w.map(x => `<div class="warn">${x}</div>`).join('');
        $('pm_new').disabled = !d.can_edit;
        const cats = ['all'].concat(d.kinds);
        $('pm_side').innerHTML = '<div class="h">Categories</div>' + cats.map(k => `<div class="cat${S.cat === k ? ' on' : ''}" data-cat="${k}"><i class="bi ${ICON[k] || 'bi-folder'}"></i>${k === 'all' ? 'All collections' : esc(KN(k))}<span class="n">${k === 'all' ? tags.length : tags.filter(t => t.kind === k).length}</span></div>`).join('')
            + `<div class="cat" style="font-weight:500;font-size:12px"><label style="display:flex;gap:6px;align-items:center;margin:0;cursor:pointer"><input type="checkbox" id="pm_rm" ${S.removed ? 'checked' : ''}> Show deleted photographs</label></div>`;
        $('pm_side').querySelectorAll('[data-cat]').forEach(c => c.addEventListener('click', () => { S.cat = c.dataset.cat; S.open = null; draw(); }));
        $('pm_rm').addEventListener('change', e => { S.removed = e.target.checked; draw(); });
        if (S.open) return drawPanel(tags.find(t => t.code === S.open));
        const list = tags.filter(t => (S.cat === 'all' || t.kind === S.cat) && match(t));
        $('pm_main').innerHTML = list.length ? `<div class="grid">${list.map(t => { const c = coverOf(t.code), n = photosOf(t.code).filter(p => p.active).length;
            return `<div class="col${Number(t.active) ? '' : ' off'}" data-open="${esc(t.code)}"><div class="ph" ${c ? `style="background-image:url('${esc(c.thumb_url)}')"` : ''}><span class="k">${esc(KN(t.kind))}</span>${c ? '' : '<span><i class="bi bi-image" style="font-size:26px"></i><br>No photograph yet</span>'}<span class="cnt">${n} photo${n === 1 ? '' : 's'}</span></div>
                <div class="b"><b>${esc(t.name)}</b><small>${esc(t.kind === 'hotel' ? hotelName(t.hotel_id) : (t.kind === 'homepage' ? ((d.positions || {})[t.position] || 'No position chosen') : (t.location || '')))}${Number(t.active) ? '' : ' · switched off'}</small></div></div>`; }).join('')}</div>`
            : `<div class="empty">No collection matches. ${d.can_edit ? 'Press <b>New collection</b> to create one.' : ''}</div>`;
        $('pm_main').querySelectorAll('[data-open]').forEach(c => c.addEventListener('click', () => { S.open = c.dataset.open; draw(); window.scrollTo(0, 0); }));
    }
    function drawPanel(t) {
        if (!t) { S.open = null; return draw(); }
        const d = S.d, ph = photosOf(t.code);
        $('pm_main').innerHTML = `<div class="panel"><div class="h"><button class="btn-l" id="pm_back"><i class="bi bi-arrow-left"></i> All collections</button><h5>${esc(t.name)}</h5><span class="chip">${esc(KN(t.kind))}</span><span class="sp"></span>
                <button class="btn-l" id="pm_edit" ${d.can_edit ? '' : 'disabled'}><i class="bi bi-pencil"></i> Edit collection</button><button class="btn-l" id="pm_del" ${d.can_delete ? '' : 'disabled'}><i class="bi bi-trash"></i> Delete collection</button></div>
            <div class="meta"><span>Code <b>${esc(t.code)}</b></span>${t.location ? `<span>Location <b>${esc(t.location)}</b></span>` : ''}${t.kind === 'hotel' ? `<span>Hotel Master <b>${esc(hotelName(t.hotel_id) || 'not linked')}</b></span>` : ''}${t.kind === 'homepage' ? `<span>Homepage position <b>${esc((d.positions || {})[t.position] || 'none')}</b></span>` : ''}
                ${['homepage', 'hotel'].includes(t.kind) ? '' : `<span>Matched in itineraries by <b>${esc((t.keyword_list || []).join(', ') || 'no words — never matched')}</b></span>`}${Number(t.active) ? '' : '<span><b>Switched off</b></span>'}</div>
            ${d.can_edit ? `<div class="drop" id="pm_drop"><i class="bi bi-cloud-arrow-up"></i><div><b>Drop photographs here</b> or <button class="btn-g" id="pm_pick" type="button"><i class="bi bi-upload"></i> Choose from your computer</button> <input type="file" id="pm_files" accept="image/*" multiple hidden></div>
                <div style="font-size:11.5px;margin-top:4px">JPEG or PNG, one or many at a time. Each is resized to 1600 px wide and a small preview is made. The details below are saved on every photograph of this upload.</div>
                <div class="row2"><div><label>Caption</label><input class="f" id="up_caption" placeholder="e.g. ${esc(t.name)}"></div><div><label>Photographer / credit</label><input class="f" id="up_credit" placeholder="e.g. Photo: A. Perera"></div>
                <div><label>Licence</label><input class="f" id="up_licence" list="pm_lic" placeholder="e.g. CC BY 4.0 / Own photo"></div><div><label>Source page</label><input class="f" id="up_source" placeholder="https://…"></div></div>
                <div style="text-align:left;margin-top:6px"><label style="display:inline-flex;gap:6px;align-items:center;font-weight:500"><input type="checkbox" id="up_pref"> Make preferred</label></div></div>` : ''}
            <div class="photos">${ph.length ? ph.map(p => `<div class="pc${p.active ? '' : ' off'}"><div class="im" data-view="${p.id}" style="background-image:url('${esc(p.thumb_url)}')">${p.preferred ? '<span class="star">★ Preferred</span>' : ''}</div>
                <div class="t"><b>${esc(p.caption || p.original_name || 'No caption')}</b><small>${esc(p.credit_line || 'No credit recorded')}</small><small>${p.width}×${p.height} · ${Math.round((p.bytes || 0) / 1024)} KB${p.on_days ? ' · on ' + p.on_days + ' itinerary day' + (p.on_days === 1 ? '' : 's') : ''}</small></div>
                <div class="a"><button class="btn-l" data-view="${p.id}"><i class="bi bi-arrows-fullscreen"></i> View / edit</button>${p.active ? `${!p.preferred && d.can_edit ? `<button class="btn-l" data-pref="${p.id}">★ Preferred</button>` : ''}${d.can_delete ? `<button class="btn-l btn-r" data-rm="${p.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}` : (d.can_edit ? `<button class="btn-l" data-back="${p.id}">Restore</button>` : '')}</div></div>`).join('') : '<div class="empty">No photograph in this collection yet.</div>'}</div></div>
            <datalist id="pm_lic"><option>Own photo — Avenir Holidays</option><option>Used with permission</option><option>CC0</option><option>Public domain</option><option>CC BY 4.0</option><option>CC BY-SA 4.0</option><option>CC BY 2.0</option><option>CC BY-SA 3.0</option><option>Unsplash Licence</option><option>Pexels Licence</option></datalist>`;
        $('pm_back').addEventListener('click', () => { S.open = null; draw(); });
        $('pm_edit').addEventListener('click', () => collectionForm(t));
        $('pm_del').addEventListener('click', async () => { if (!confirm('Delete the collection "' + t.name + '"? If photographs are in it, it is switched off instead and the photographs keep their other collections.')) return; const j = await post('/tag/delete/' + encodeURIComponent(t.code)); toast(j.message || 'Done', j.status !== 200); if (j.status === 200) { S.open = null; load(); } });
        const drop = $('pm_drop');
        if (drop) {
            $('pm_pick').addEventListener('click', () => $('pm_files').click());
            $('pm_files').addEventListener('change', e => upload(t, e.target.files));
            ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('on'); }));
            ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('on'); }));
            drop.addEventListener('drop', e => upload(t, e.dataTransfer.files));
        }
        $('pm_main').querySelectorAll('[data-view]').forEach(b => b.addEventListener('click', () => photoForm(S.d.photos.find(p => p.id === Number(b.dataset.view)))));
        $('pm_main').querySelectorAll('[data-pref]').forEach(b => b.addEventListener('click', async () => { const j = await post('/photo/' + b.dataset.pref + '/save', { preferred: 1 }); toast(j.status === 200 ? 'Marked preferred.' : j.message, j.status !== 200); load(); }));
        $('pm_main').querySelectorAll('[data-rm]').forEach(b => b.addEventListener('click', async () => { if (!confirm('Delete this photograph? It leaves every itinerary it is on (those days choose another) and can be restored from "Show deleted photographs".')) return; const j = await post('/photo/' + b.dataset.rm + '/remove'); toast(j.message, j.status !== 200); load(); }));
        $('pm_main').querySelectorAll('[data-back]').forEach(b => b.addEventListener('click', async () => { const j = await post('/photo/' + b.dataset.back + '/restore'); toast(j.message, j.status !== 200); load(); }));
    }
    async function upload(t, files) {
        if (!files || !files.length) return;
        const fd = new FormData();
        Array.from(files).forEach(f => fd.append('files[]', f, f.name));
        fd.append('tags', t.code); fd.append('caption', $('up_caption').value); fd.append('credit', $('up_credit').value); fd.append('licence', $('up_licence').value); fd.append('source_url', $('up_source').value); fd.append('preferred', $('up_pref').checked ? '1' : '');
        toast('Uploading ' + files.length + ' photograph' + (files.length === 1 ? '' : 's') + '…');
        const j = await post('/upload', fd);
        toast(j.message || 'Done', j.status !== 200);
        load();
    }
    function modal(title, body, foot) { $('pm_md').innerHTML = `<div class="h"><b>${title}</b><button type="button" id="pm_x">×</button></div><div class="bd">${body}</div><div class="ft">${foot}</div>`; $('pm_ov').classList.add('on'); $('pm_x').addEventListener('click', close); }
    function close() { $('pm_ov').classList.remove('on'); }
    $('pm_ov').addEventListener('click', e => { if (e.target.id === 'pm_ov') close(); });
    function photoForm(p) {
        if (!p) return;
        const d = S.d, ed = d.can_edit, dis = ed ? '' : 'disabled';
        const f = (k, l, v, ph) => `<div class="fl"><span class="lbl">${l}</span><input class="f" id="pf_${k}" value="${esc(v || '')}" placeholder="${esc(ph || '')}" ${dis}></div>`;
        modal(esc(p.caption || 'Photograph'), `<div class="two"><div><img src="${esc(p.url)}" alt=""><div style="font-size:11.5px;margin-top:6px">${p.width}×${p.height} px · ${Math.round((p.bytes || 0) / 1024)} KB · uploaded ${esc(String(p.uploaded_at || '').slice(0, 16))} by ${esc(p.uploaded_by || '')}${p.original_name ? ' · ' + esc(p.original_name) : ''}</div></div>
            <div>${f('caption', 'Caption', p.caption)}${f('credit', 'Photographer / credit', p.credit, 'Photo: …')}${f('licence', 'Licence', p.licence, 'CC BY 4.0, own photo …')}${f('licence_url', 'Licence link', p.licence_url, 'https://creativecommons.org/…')}${f('source_url', 'Source page', p.source_url, 'https://…')}
            <div class="fl"><label style="display:flex;gap:6px;align-items:center"><input type="checkbox" id="pf_pref" ${p.preferred ? 'checked' : ''} ${dis}> Preferred photograph</label></div>
            <div class="fl"><span class="lbl">Collections it belongs to</span><div class="chips" id="pf_tags">${d.tags.map(t => `<span data-t="${esc(t.code)}" class="${p.tag_codes.includes(t.code) ? 'on' : ''}">${esc(t.name)}</span>`).join('')}</div></div>
            ${p.source_url ? `<div class="fl"><a href="${esc(p.source_url)}" target="_blank" rel="noopener" style="color:#8b1d40;font-weight:600">Open the source page</a></div>` : ''}</div></div>`,
            `${ed ? `<input type="file" id="pf_file" accept="image/*" hidden><button class="btn-l" id="pf_rep"><i class="bi bi-arrow-repeat"></i> Replace picture</button>` : ''}${d.can_delete && p.active ? `<button class="btn-l" id="pf_rm" style="color:#a3123a"><i class="bi bi-trash"></i> Delete</button>` : ''}<button class="btn-l" id="pf_close">Close</button>${ed ? '<button class="btn-g" id="pf_save">Save</button>' : ''}`);
        $('pf_close').addEventListener('click', close);
        if (ed) {
            $('pf_tags').querySelectorAll('[data-t]').forEach(s => s.addEventListener('click', () => s.classList.toggle('on')));
            $('pf_save').addEventListener('click', async () => {
                const tags = Array.from($('pf_tags').querySelectorAll('.on')).map(s => s.dataset.t);
                const j = await post('/photo/' + p.id + '/save', { caption: $('pf_caption').value, credit: $('pf_credit').value, licence: $('pf_licence').value, licence_url: $('pf_licence_url').value, source_url: $('pf_source_url').value, preferred: $('pf_pref').checked ? 1 : 0, tags: tags.join(',') });
                toast(j.status === 200 ? 'Saved.' : j.message, j.status !== 200); if (j.status === 200) { close(); load(); }
            });
            $('pf_rep').addEventListener('click', () => $('pf_file').click());
            $('pf_file').addEventListener('change', async e => { const fl = e.target.files[0]; if (!fl) return; const fd = new FormData(); fd.append('file', fl, fl.name); const j = await post('/photo/' + p.id + '/replace', fd); toast(j.message, j.status !== 200); if (j.status === 200) { close(); load(); } });
        }
        if ($('pf_rm')) $('pf_rm').addEventListener('click', async () => { if (!confirm('Delete this photograph? It can be restored from "Show deleted photographs".')) return; const j = await post('/photo/' + p.id + '/remove'); toast(j.message, j.status !== 200); if (j.status === 200) { close(); load(); } });
    }
    function collectionForm(t) {
        const d = S.d, isNew = !t; t = t || { code: '', name: '', kind: S.cat !== 'all' ? S.cat : 'destination', location: '', keywords: '', hotel_id: '', position: '', active: 1 };
        modal(isNew ? 'New photo collection' : 'Edit collection — ' + esc(t.name), `<div class="two"><div>
            <div class="fl"><span class="lbl">Name</span><input class="f" id="cf_name" value="${esc(t.name)}" placeholder="e.g. Negombo, Heritance Kandalama, Homepage banner"></div>
            <div class="fl"><span class="lbl">Category</span><select class="f" id="cf_kind">${d.kinds.map(k => `<option value="${k}" ${t.kind === k ? 'selected' : ''}>${esc(KN(k))}</option>`).join('')}</select></div>
            <div class="fl" id="cf_hotel_w"><span class="lbl">Hotel (from the Hotel Master)</span><select class="f" id="cf_hotel"><option value="">Choose the hotel…</option>${(d.hotels || []).map(h => `<option value="${h.id}" ${Number(t.hotel_id) === Number(h.id) ? 'selected' : ''}>${esc(h.name)}${h.address ? ' — ' + esc(h.address) : ''}</option>`).join('')}</select></div>
            <div class="fl" id="cf_pos_w"><span class="lbl">Homepage position</span><select class="f" id="cf_pos"><option value="">Not shown on the homepage</option>${Object.entries(d.positions || {}).map(([k, v]) => `<option value="${k}" ${t.position === k ? 'selected' : ''}>${esc(v)}</option>`).join('')}</select></div>
            <div class="fl"><span class="lbl">Location</span><input class="f" id="cf_loc" value="${esc(t.location || '')}" placeholder="e.g. Western Province, near Katunayake"></div></div>
            <div><div class="fl" id="cf_kw_w"><span class="lbl">Words that find it in an itinerary (comma separated)</span><textarea class="f" id="cf_kw" rows="5" placeholder="e.g. negombo, negombo beach, negombo lagoon">${esc(t.keywords || '')}</textarea><div style="font-size:11.5px;margin-top:3px">When a day's programme mentions one of these words, the collection's preferred photograph is shown for that day.</div></div>
            ${isNew ? '' : `<div class="fl"><label style="display:flex;gap:6px;align-items:center"><input type="checkbox" id="cf_act" ${Number(t.active) ? 'checked' : ''}> Collection in use</label></div>`}</div></div>`,
            `<button class="btn-l" id="cf_cancel">Cancel</button><button class="btn-g" id="cf_save">${isNew ? 'Create collection' : 'Save'}</button>`);
        const sync = () => { const k = $('cf_kind').value; $('cf_hotel_w').style.display = k === 'hotel' ? '' : 'none'; $('cf_pos_w').style.display = k === 'homepage' ? '' : 'none'; $('cf_kw_w').style.display = ['hotel', 'homepage'].includes(k) ? 'none' : ''; };
        $('cf_kind').addEventListener('change', sync); sync();
        $('cf_hotel').addEventListener('change', () => { if (!$('cf_name').value) $('cf_name').value = $('cf_hotel').selectedOptions[0].text.split(' — ')[0]; });
        $('cf_cancel').addEventListener('click', close);
        $('cf_save').addEventListener('click', async () => {
            const name = $('cf_name').value.trim(); if (!name) { toast('Give the collection a name.', true); return; }
            const kind = $('cf_kind').value;
            const code = t.code || (kind === 'hotel' ? 'HOTEL_' + $('cf_hotel').value : (kind === 'homepage' ? 'HOME_' : '') + name.toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_|_$/g, '')).slice(0, 40);
            const body = { code, name, kind, location: $('cf_loc').value, keywords: ['hotel', 'homepage'].includes(kind) ? '' : $('cf_kw').value, hotel_id: $('cf_hotel').value, position: $('cf_pos').value };
            if (!isNew) body.active = $('cf_act').checked ? 1 : 0;
            if (isNew && d.tags.some(x => x.code === code)) { toast('A collection with the code ' + code + ' already exists.', true); return; }
            const j = await post('/tag', body); toast(j.status === 200 ? (isNew ? 'Collection created.' : 'Saved.') : j.message, j.status !== 200);
            if (j.status === 200) { close(); S.open = code; load(); }
        });
    }
    $('pm_new').addEventListener('click', () => collectionForm(null));
    $('pm_q').addEventListener('input', e => { S.q = e.target.value.trim(); S.open = null; draw(); });
    load();
})();
</script>
</body>
</html>
