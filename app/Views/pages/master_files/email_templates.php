<?= view('layouts/head') ?>
<style>
    /* ---------- Email Templates (the Tour Desk master skin) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:980px; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .btn-primary { background:var(--tf-navy); border-color:var(--tf-navy); }
    .tf .btn-primary:hover { background:var(--tf-navy-2); border-color:var(--tf-navy-2); }
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .btn-light:hover { background:var(--tf-bg); }
    .tf .grp { margin-bottom:14px; }
    .tf .grp-h { font-size:12px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--tf-navy); padding:0 2px 6px; }
    .tf .grp-s { font-size:12px; color:var(--tf-muted); padding:0 2px 8px; }
    .tf .tpl { border:1px solid var(--tf-line); border-radius:8px; padding:12px 14px; margin-bottom:8px; background:#fff; }
    .tf .tpl.off { opacity:.6; background:var(--tf-bg); }
    .tf .tpl .nm { font-weight:600; font-size:13.5px; }
    .tf .tpl .sj { font-size:12.5px; color:var(--tf-ink); margin-top:4px; }
    .tf .tpl .ms { font-size:12.5px; color:var(--tf-muted); white-space:pre-wrap; margin-top:6px; border-top:1px dashed var(--tf-line); padding-top:8px; line-height:1.6; }
    .tf .tpl .acts { display:flex; gap:6px; flex-wrap:wrap; margin-top:10px; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .words { display:flex; flex-wrap:wrap; gap:5px; }
    .tf .word { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:11.5px; background:var(--tf-blue-soft); color:var(--tf-navy); border-radius:5px; padding:2px 7px; cursor:pointer; border:0; }
    .tf .word:hover { background:var(--tf-navy); color:#fff; }
    .tf .note { padding:10px 12px; border-radius:8px; background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); font-size:12.5px; }
    .tf .ok { padding:10px 12px; border-radius:8px; background:var(--tf-green-soft); color:var(--tf-green); font-size:12.5px; }
    .tf .bad { padding:10px 12px; border-radius:8px; background:var(--tf-red-soft); color:var(--tf-red); font-size:12.5px; }
    .tf textarea.form-control { font-size:13px; line-height:1.6; }
</style>
<?= view('layouts/tf_master_skin') ?>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf tf-skin" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>

                <div class="tf-title">
                    <div>
                        <h4>Email Templates</h4>
                        <div class="sub">The saved wording of every email the system sends: the quotation and itinerary to the agent, the final tour confirmation, the hotel reservation request and voucher, the restaurant reservation request and voucher, the driver, guide and transport request, and the activity and other supplier request. Words in braces are filled from the tour when an email is prepared &mdash; <span class="word">{agent}</span>, <span class="word">{tour_no}</span>, <span class="word">{dates}</span>, <span class="word">{hotels}</span>. A word the tour cannot fill is left empty and named on the send window, never invented. Staff may edit the message of one email on the send window itself; that never changes what is saved here, and an email already sent is never changed by an edit here.</div>
                    </div>
                    <?php if (!empty($can_edit)): ?><button class="btn btn-primary btn-sm" id="b_add"><i class="bi bi-plus-lg"></i> New template</button><?php endif; ?>
                </div>

                <?php if (empty($can_edit)): ?>
                <div class="note mb-3">You can read every template here. Changing the saved wording needs the <b>Email Templates</b> privilege &mdash; ask Admin to grant it under Settings &rarr; Group Privileges.</div>
                <?php endif; ?>

                <div id="msg" class="mb-3"></div>
                <div id="list"></div>

                <div class="tf-card mt-3">
                    <div class="tf-card-h"><h6>The words a template may carry</h6></div>
                    <div class="tf-card-b"><div class="words" id="words"></div>
                        <div class="help mt-2">Press a word while writing a template and it is put where the cursor is.</div></div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade tf tf-skin" id="m_edit" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="e_title">Template</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div id="e_msg" class="mb-2"></div>
        <div class="row g-2">
            <div class="col-md-7"><label class="help">Name &mdash; what staff see in the list</label><input class="form-control form-control-sm" id="e_name" maxlength="120"></div>
            <div class="col-md-5"><label class="help">Which email it is for</label><select class="form-select form-select-sm" id="e_cat"></select></div>
            <div class="col-md-12"><label class="help">Subject</label><input class="form-control form-control-sm" id="e_subj" maxlength="255"></div>
            <div class="col-md-12"><label class="help">Message</label><textarea class="form-control" id="e_msgtxt" rows="14"></textarea></div>
            <div class="col-md-12"><label class="help">Words you can put in the subject or the message</label><div class="words" id="e_words"></div></div>
            <div class="col-md-12"><label class="help"><input type="checkbox" id="e_def"> Start every email of this group with this one</label></div>
        </div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary btn-sm" id="e_save">Save the template</button><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    const BASE = '<?= rtrim(base_url('master_files/email_templates'), '/') ?>';
    const CAN  = <?= !empty($can_edit) ? 'true' : 'false' ?>;
    const $ = i => document.getElementById(i);
    const esc = s => String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    let D = { rows: [], categories: {}, words: {} };

    const say = (t, kind) => { $('msg').innerHTML = t ? '<div class="' + (kind || 'ok') + '">' + esc(t) + '</div>' : ''; if (t) { window.scrollTo({ top: 0, behavior: 'smooth' }); } };
    const esay = (t, kind) => { $('e_msg').innerHTML = t ? '<div class="' + (kind || 'bad') + '">' + esc(t) + '</div>' : ''; };

    async function api(path, body) {
        const o = body ? { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) }
                       : { headers: { 'X-Requested-With': 'XMLHttpRequest' } };
        try { const r = await fetch(BASE + path, o); return await r.json(); } catch (e) { return null; }
    }

    async function load() {
        const j = await api('/data');
        if (!j || j.status !== 200) { say((j && j.message) || 'The templates could not be read.', 'bad'); return; }
        D = j.data || D;
        render();
    }

    function render() {
        $('words').innerHTML = Object.keys(D.words || {}).map(w => '<span class="word" title="' + esc(D.words[w]) + '">{' + esc(w) + '}</span>').join('');
        const rows = D.rows || [];
        if (!rows.length) {
            $('list').innerHTML = '<div class="tf-card"><div class="tf-card-b"><div class="note">No template is saved yet.'
                + (CAN ? ' Press <b>Write the standard wording</b> to put the standard set in, then change any of it.' : '') + '</div>'
                + (CAN ? '<button class="btn btn-primary btn-sm mt-2" id="b_seed">Write the standard wording</button>' : '') + '</div></div>';
            if ($('b_seed')) { $('b_seed').addEventListener('click', seed); }
            return;
        }
        const cats = D.categories || {};
        const by = {};
        rows.forEach(r => { (by[r.category] = by[r.category] || []).push(r); });
        const order = Object.keys(cats).filter(c => by[c]).concat(Object.keys(by).filter(c => !cats[c]));
        const missing = Object.keys(cats).filter(c => !by[c]);
        $('list').innerHTML = (missing.length && CAN
                ? '<div class="note mb-3">No wording saved yet for: <b>' + missing.map(c => esc(cats[c])).join(', ') + '</b>. '
                  + '<button class="btn btn-light btn-sm ms-2" id="b_seed">Write the standard wording for these</button></div>'
                : '')
            + order.map(c => '<div class="grp"><div class="grp-h">' + esc(cats[c] || c) + '</div>'
                + by[c].map(t => tpl(t)).join('') + '</div>').join('');
        if ($('b_seed')) { $('b_seed').addEventListener('click', seed); }
        document.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => open_(Number(b.dataset.edit))));
        document.querySelectorAll('[data-arch]').forEach(b => b.addEventListener('click', () => act(Number(b.dataset.arch), 'archive')));
        document.querySelectorAll('[data-rest]').forEach(b => b.addEventListener('click', () => act(Number(b.dataset.rest), 'restore')));
    }

    function tpl(t) {
        const off = t.archived_at !== null && t.archived_at !== undefined && t.archived_at !== '';
        return '<div class="tpl' + (off ? ' off' : '') + '">'
            + '<div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">'
            + '<div><span class="nm">' + esc(t.name) + '</span> '
            + (Number(t.is_default) === 1 ? '<span class="pill pill-green">starts this group</span> ' : '')
            + (off ? '<span class="pill pill-grey">off the send windows</span>' : '')
            + '<div class="sj"><b>Subject:</b> ' + esc(t.subject) + '</div></div></div>'
            + '<div class="ms">' + esc(t.message) + '</div>'
            + (CAN ? '<div class="acts">'
                + (off ? '<button class="btn btn-light btn-sm" data-rest="' + t.id + '">Put it back</button>'
                       : '<button class="btn btn-light btn-sm" data-edit="' + t.id + '">Edit</button>'
                         + '<button class="btn btn-light btn-sm" data-arch="' + t.id + '">Take it off the send windows</button>')
                + '</div>' : '')
            + '</div>';
    }

    async function seed() {
        const j = await api('/seed', {});
        if (!j || j.status !== 200) { say((j && j.message) || 'The standard wording could not be written.', 'bad'); return; }
        D = j.data || D; render(); say(j.message);
    }

    async function act(id, what) {
        const j = await api('/' + id + '/' + what, {});
        if (!j || j.status !== 200) { say((j && j.message) || 'Nothing was changed.', 'bad'); return; }
        D = j.data || D; render(); say(j.message);
    }

    let EDIT = null;
    function open_(id) {
        const t = (D.rows || []).find(x => Number(x.id) === Number(id)) || { id: 0, name: '', category: 'general', subject: '', message: '', is_default: 0 };
        EDIT = t;
        $('e_title').textContent = id ? 'Edit "' + t.name + '"' : 'New template';
        $('e_name').value = t.name || '';
        $('e_subj').value = t.subject || '';
        $('e_msgtxt').value = t.message || '';
        $('e_def').checked = Number(t.is_default) === 1;
        $('e_cat').innerHTML = Object.keys(D.categories || {}).map(c => '<option value="' + esc(c) + '"' + (c === t.category ? ' selected' : '') + '>' + esc(D.categories[c]) + '</option>').join('');
        $('e_words').innerHTML = Object.keys(D.words || {}).map(w => '<button type="button" class="word" data-w="' + esc(w) + '" title="' + esc(D.words[w]) + '">{' + esc(w) + '}</button>').join('');
        document.querySelectorAll('#e_words [data-w]').forEach(b => b.addEventListener('click', () => put('{' + b.dataset.w + '}')));
        esay('');
        new bootstrap.Modal($('m_edit')).show();
    }

    function put(word) {
        const el = document.activeElement && document.activeElement.id === 'e_subj' ? $('e_subj') : $('e_msgtxt');
        const a = el.selectionStart === null ? el.value.length : el.selectionStart, b = el.selectionEnd === null ? a : el.selectionEnd;
        el.value = el.value.slice(0, a) + word + el.value.slice(b);
        el.focus(); el.selectionStart = el.selectionEnd = a + word.length;
    }

    if ($('b_add')) { $('b_add').addEventListener('click', () => open_(0)); }
    if ($('e_save')) { $('e_save').addEventListener('click', async () => {
        const body = { id: EDIT ? Number(EDIT.id) : 0, name: $('e_name').value.trim(), category: $('e_cat').value,
                       subject: $('e_subj').value.trim(), message: $('e_msgtxt').value, is_default: $('e_def').checked ? 1 : 0 };
        if (!body.name) { esay('Give the template a name, so staff know which one to pick.'); return; }
        if (!body.subject) { esay('The template needs a subject.'); return; }
        if (!body.message.trim()) { esay('The template needs a message.'); return; }
        const j = await api('/save', body);
        if (!j || j.status !== 200) { esay((j && j.message) || 'The template was not saved.'); return; }
        D = j.data || D; render(); say(j.message);
        bootstrap.Modal.getInstance($('m_edit')).hide();
    }); }

    load();
})();
</script>
</body>
</html>
