<?= view('layouts/head') ?>
<style>
    /* ---------- Tour Desk Manage (same tokens as the other Tour Desk panels) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#b7791f; --tf-amber-soft:#fdf3e1;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); margin-bottom:14px; }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:820px; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .btn-primary { background:var(--tf-navy); border-color:var(--tf-navy); }
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .btn-light:hover { background:var(--tf-bg); }
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl td { padding:9px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr:last-child td { border-bottom:0; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .pill-amber { background:var(--tf-amber-soft); color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .chips { display:flex; gap:6px; flex-wrap:wrap; }
    .tf .chip { border:1px solid var(--tf-line); background:#fff; border-radius:9px; padding:5px 11px; font-size:12px; font-weight:500; cursor:pointer; color:var(--tf-ink); }
    .tf .chip .n { font-family:ui-monospace, Menlo, monospace; font-size:11px; color:var(--tf-muted); margin-left:4px; }
    .tf .chip.on { background:var(--tf-blue-soft); border-color:transparent; color:var(--tf-navy); font-weight:600; }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:14px 0; text-align:center; }
    .tf .rules { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px; }
    .tf .rule { border:1px solid var(--tf-line); border-radius:8px; padding:10px 12px; font-size:12.5px; }
    .tf .rule b { display:block; margin-bottom:2px; }
    .tf .rule i { color:#842484; margin-right:6px; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>
                <?= view('layouts/tourdesk_menu') ?>

                <div class="tf-title">
                    <div>
                        <h4>Admin <span class="pill pill-amber align-middle">Admin only</span></h4>
                        <div class="sub">The one place for the controls only an admin holds: every tour with cancel, delete and reopen; what has been deleted or archived, with its way back or its removal for good; everything anyone changed, across every tour; and the settings. The same privilege is behind every ⋯ menu on the desks.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The Tour Desk tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Inquiries</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <div class="tf-card"><div class="tf-card-b rules">
                    <div class="rule"><i class="bi bi-person-badge"></i><b>Handling agent</b>Whoever starts an inquiry owns it, and the tour and its cards carry the same name. Only Manage reassigns it; every reassignment is on the history. Everyone sees it and filters by it on every screen.</div>
                    <div class="rule"><i class="bi bi-clock-history"></i><b>History</b>Every edit on an inquiry is written field by field — who, when, from what to what. The desks' own trail (requested, confirmed, voucher…) is kept as it was; a tour's history reads both.</div>
                    <div class="rule"><i class="bi bi-archive"></i><b>Delete is archive</b>Deleting a tour cancels it, then archives it with its inquiry: off every desk, list and count, kept whole, restorable from Deleted / archived. A card the hotel holds cannot be archived until it is put back. Nothing is ever deleted by itself.</div>
                    <div class="rule"><i class="bi bi-shield-check"></i><b>Removal, with checks</b>Only a deleted tour, or an archived inquiry with no tour, no issued quotation and no legacy link, can be removed for good — the number has to be typed, and one line on the Activity log says what went and who removed it.</div>
                </div></div>

                <div class="tf-card">
                    <div class="tf-card-h">
                        <div class="chips" id="tabs">
                            <span class="chip on" data-tab="tours">Tours</span>
                            <span class="chip" data-tab="archived">Deleted / archived</span>
                            <span class="chip" data-tab="activity">Activity log</span>
                            <span class="chip" data-tab="edit_requests">Edit Requests</span>
                            <span class="chip" data-tab="settings">Settings</span>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <select class="form-select form-select-sm" id="f_handler" style="width:auto;max-width:190px" title="Handling agent — whose tour it is">
                                <option value="">Any handling agent</option>
                                <?php foreach ((array) ($handlers ?? []) as $h): ?>
                                <option value="<?= esc($h['id'] !== null ? (string) $h['id'] : (string) $h['name']) ?>"><?= esc($h['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select form-select-sm" id="f_by" style="width:auto;max-width:190px;display:none" title="Who made the change">
                                <option value="">Changed by anyone</option>
                                <?php foreach ((array) ($handlers ?? []) as $h): if (!empty($h['username'])): ?>
                                <option value="<?= esc((string) $h['username']) ?>"><?= esc($h['name']) ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                            <input class="form-control form-control-sm" id="f_q" placeholder="Inquiry, group, agent…" style="width:200px">
                            <input type="date" class="form-control form-control-sm" id="f_from" style="width:auto" title="From">
                            <input type="date" class="form-control form-control-sm" id="f_to" style="width:auto" title="To">
                            <button class="btn btn-light btn-sm" id="f_clear" type="button" title="Clear every filter"><i class="bi bi-x-lg"></i></button>
                            <a class="btn btn-light btn-sm" id="f_export" href="#" style="display:none" title="The log with these filters, as a spreadsheet file"><i class="bi bi-download"></i> Export</a>
                        </div>
                    </div>
                    <div id="body"><div class="empty">Loading…</div></div>
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
    const BASE = '<?= base_url($page_url ?? 'tour/manage') ?>';
    const INTAKE = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const HOTEL = '<?= base_url($hotel_url ?? 'tour/hotel_bookings') ?>';
    const TRANSPORT = '<?= base_url($transport_url ?? 'tour/transport_ops') ?>';
    const CREW_MASTER = '<?= base_url('master_files/crew_bata') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    if (!INSTALLED) return;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2800 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With':'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    // The signed-in manager's password, checked on the server before an action that cannot be taken back (cancel,
    // delete). Returns the password to send with the action, or null when the person stepped back or the check
    // failed — nothing is changed on a failure, and the wrong attempt is on the Activity log.
    async function askPassword(title, text, purpose) {
        const pw = await Swal.fire({ title: title + ' — your password', html: `<div style="text-align:left;font-size:13.5px">${esc(text)}</div>`, input: 'password', inputPlaceholder: 'Your password', inputAttributes: { autocomplete: 'current-password' }, inputValidator: v => (v ? undefined : 'Enter your password.'), showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#b23a32' });
        if (!pw.isConfirmed) return null;
        const a = await post('/reauth', { password: pw.value || '', purpose: purpose || title });
        if (a.status !== 200 || !a.ok) { await Swal.fire({ icon: 'error', title: 'Not done', text: a.message || 'Password not recognised. Nothing was changed.' }); return null; }
        return pw.value || '';
    }
    const ME_NAME = <?= json_encode((string) ($me_name ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const fmt = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); };
    const when = s => s ? String(s).slice(0, 16) : '';
    // ?tab=settings opens that tab straight away (the inquiry screen links to the wording settings)
    let TAB = ['tours', 'activity', 'settings', 'archived', 'edit_requests'].includes(new URLSearchParams(location.search).get('tab') || '') ? new URLSearchParams(location.search).get('tab') : 'tours';
    const BOARD = '<?= base_url($board_url ?? 'tour/board') ?>';

    const qs = () => new URLSearchParams({ tab: TAB, handler: document.getElementById('f_handler').value || '', by: document.getElementById('f_by').value || '', q: document.getElementById('f_q').value || '', from: document.getElementById('f_from').value || '', to: document.getElementById('f_to').value || '' }).toString();

    async function load() {
        document.querySelectorAll('[data-tab]').forEach(c => c.classList.toggle('on', c.dataset.tab === TAB));
        document.getElementById('f_by').style.display = TAB === 'activity' ? '' : 'none';
        const ex = document.getElementById('f_export');
        ex.style.display = TAB === 'activity' ? '' : 'none';
        ex.href = BASE + '/activity.csv?' + qs();
        const j = await api('/data?' + qs());
        const box = document.getElementById('body');
        if (j.status !== 200) { box.innerHTML = `<div class="empty">${esc(j.message)}</div>`; return; }
        if (TAB === 'edit_requests') { renderEditRequests(box, j.data); return; }
        window.__et = j.data.email_templates || {};
        box.innerHTML = TAB === 'activity' ? renderActivity(j.data.rows || []) : (TAB === 'settings' ? renderSettings(j.data.numbering || {}, j.data.company || {}, j.data.sightseeing || {}, j.data.itinerary || {}, j.data.email_templates || {}, j.data.mailbox || {}, j.data.inbox || {}, j.data.secrets || {}) : (TAB === 'tours' ? renderTours(j.data.tours || []) : renderArchived(j.data)));
        wireTourActions(box);
        box.querySelectorAll('[data-restore]').forEach(b => b.addEventListener('click', async () => {
            let body = {};
            if (b.dataset.why) {
                const r = await Swal.fire({ title: b.dataset.why, input: 'text', inputPlaceholder: 'Why (required, stays on the trail)', showCancelButton: true, confirmButtonText: 'Go on' });
                if (!r.isConfirmed) return;
                body = { why: r.value || '' };
            }
            const j2 = await post(b.dataset.restore, body);
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); load();
        }));
        document.getElementById('n_save')?.addEventListener('click', async () => {
            const j2 = await post('/settings', { tour_prefix: document.getElementById('n_prefix').value, tour_next: document.getElementById('n_next').value, tour_pad: document.getElementById('n_pad').value });
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); load();
        });
        document.getElementById('ss_save')?.addEventListener('click', async () => {
            const j = await post('/sightseeing', { no_charge_words: document.getElementById('ss_words').value, aliases: document.getElementById('ss_aliases').value });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message);
        });
        document.getElementById('iw_save')?.addEventListener('click', async () => {
            const body = {}; box.querySelectorAll('[data-itin]').forEach(i => body[i.dataset.itin] = i.value);
            const j2 = await post('/itinerary', body);
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); load();
        });
        // the email templates (schema 34): new, edit, archive, restore — all on the Activity log
        const etRows = () => (window.__et || {}).rows || [];
        function etShow(t) {
            const f = document.getElementById('et_form'); if (!f) return;
            f.style.display = '';
            document.getElementById('et_form_title').textContent = t ? 'Edit template' : 'New template';
            document.getElementById('et_id').value = t ? t.id : '';
            document.getElementById('et_name').value = t ? t.name : '';
            document.getElementById('et_category').value = t ? t.category : 'general';
            document.getElementById('et_default').checked = t ? Number(t.is_default) === 1 : false;
            document.getElementById('et_subject').value = t ? t.subject : '';
            document.getElementById('et_message').value = t ? t.message : '';
            f.scrollIntoView({ block: 'nearest' }); document.getElementById('et_name').focus();
        }
        document.getElementById('et_new')?.addEventListener('click', () => etShow(null));
        document.getElementById('et_cancel')?.addEventListener('click', () => { document.getElementById('et_form').style.display = 'none'; });
        box.querySelectorAll('[data-et-edit]').forEach(b => b.addEventListener('click', () => { const t = etRows().find(x => Number(x.id) === Number(b.dataset.etEdit)); if (t) etShow(t); }));
        document.getElementById('et_save')?.addEventListener('click', async () => {
            const j2 = await post('/email_template', { id: document.getElementById('et_id').value || 0, name: document.getElementById('et_name').value, category: document.getElementById('et_category').value, subject: document.getElementById('et_subject').value, message: document.getElementById('et_message').value, is_default: document.getElementById('et_default').checked ? 1 : 0 });
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); load();
        });
        box.querySelectorAll('[data-et-archive]').forEach(b => b.addEventListener('click', async () => {
            const ok = await Swal.fire({ title: 'Archive "' + b.dataset.name + '"?', text: 'No send window offers it afterwards. It is kept, and can be restored from the archived list.', showCancelButton: true, confirmButtonText: 'Archive' });
            if (!ok.isConfirmed) return;
            const j2 = await post('/email_template/' + b.dataset.etArchive + '/archive', {});
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); load();
        }));
        box.querySelectorAll('[data-et-restore]').forEach(b => b.addEventListener('click', async () => {
            const j2 = await post('/email_template/' + b.dataset.etRestore + '/restore', {});
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); load();
        }));
        document.getElementById('iw_reset')?.addEventListener('click', async () => {
            const ok = await Swal.fire({ title: 'Back to the standard sentences?', text: 'Every sentence goes back to the standard wording. Quotations previewed from now on read that way.', showCancelButton: true, confirmButtonText: 'Standard sentences' });
            if (!ok.isConfirmed) return;
            const body = {}; box.querySelectorAll('[data-itin]').forEach(i => body[i.dataset.itin] = '');
            const j2 = await post('/itinerary', body);
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); load();
        });
        if (document.getElementById('bk_list')) loadBackups();
        document.getElementById('bk_take')?.addEventListener('click', async e => {
            const btn = e.currentTarget;
            if (btn.disabled) return;
            btn.disabled = true;
            let j2;
            try { j2 = await post('/backup', {}); } finally { btn.disabled = false; }
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); loadBackups();
        });
        document.getElementById('ib_save')?.addEventListener('click', async () => {
            const btn = document.getElementById('ib_save'); if (btn.disabled) return; btn.disabled = true;
            let j2;
            try { j2 = await post('/inbox', { host: document.getElementById('ib_host').value, port: document.getElementById('ib_port').value, secure: document.getElementById('ib_secure').value, folder: document.getElementById('ib_folder').value, email: document.getElementById('ib_email').value, password: document.getElementById('ib_pass').value, enabled: document.getElementById('ib_enabled').checked ? 1 : 0, auto_quote: document.getElementById('ib_auto').checked ? 1 : 0, new_token: document.getElementById('ib_token')?.checked ? 1 : 0, web_poll: document.getElementById('ib_web')?.checked ? 1 : 0 }); } finally { btn.disabled = false; }
            document.getElementById('ib_pass').value = '';
            if (j2.status !== 200) { toast('error', j2.message); return; }
            if (j2.data && j2.data.new_token) { await Swal.fire({ title: 'Scheduled-task token — shown once', html: '<div class="help">' + esc(j2.message) + ' Put it in the task\'s X-TourFlow-Token header. It is kept only as a hash.</div><input class="form-control form-control-sm mono mt-2" value="' + esc(j2.data.new_token) + '" readonly onclick="this.select()">', showConfirmButton: true, confirmButtonText: 'I have copied it' }); } else toast('success', j2.message);
            load();
        });
        document.getElementById('sc_key')?.addEventListener('click', async () => {
            const r = await Swal.fire({ icon: 'question', title: 'Generate this environment\'s encryption key?', text: 'It is written to .env on this server only and never shown. Do this separately on UAT and on production; never copy a key between servers.', showCancelButton: true, confirmButtonText: 'Generate' });
            if (!r.isConfirmed) return;
            const j2 = await post('/secrets', { action: 'install_key' }); toast(j2.status === 200 ? 'success' : 'error', j2.message); load();
        });
        document.getElementById('sc_migrate')?.addEventListener('click', async () => {
            const r = await Swal.fire({ icon: 'question', title: 'Encrypt the staff mailbox passwords now?', text: 'Each plain-text password is encrypted in place with this install\'s key. Nothing is shown or logged; sending keeps working.', showCancelButton: true, confirmButtonText: 'Encrypt' });
            if (!r.isConfirmed) return;
            const j2 = await post('/secrets', { action: 'migrate_users' }); toast(j2.status === 200 ? 'success' : 'error', j2.message); load();
        });
        document.getElementById('mb_save')?.addEventListener('click', async () => {
            const btn = document.getElementById('mb_save'); if (btn.disabled) return; btn.disabled = true;
            let j2;
            try { j2 = await post('/mailbox', { email: document.getElementById('mb_email').value, name: document.getElementById('mb_name').value, password: document.getElementById('mb_pass').value, signature: document.getElementById('mb_sig').value }); } finally { btn.disabled = false; }
            document.getElementById('mb_pass').value = '';
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); load();
        });
        document.getElementById('mb_clear')?.addEventListener('click', async () => {
            const r = await Swal.fire({ icon: 'warning', title: 'Remove the system mailbox?', text: 'E-mails from Communications & History will go from each person\'s own mailbox instead.', showCancelButton: true, confirmButtonText: 'Remove' });
            if (!r.isConfirmed) return;
            const j2 = await post('/mailbox', { clear: 1 });
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); load();
        });
        document.getElementById('co_save')?.addEventListener('click', async () => {
            const j2 = await post('/company', { name: document.getElementById('co_name').value, address: document.getElementById('co_address').value, phone: document.getElementById('co_phone').value, email: document.getElementById('co_email').value, always_cc: document.getElementById('co_always_cc').value });
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); load();
        });
        box.querySelectorAll('[data-delete]').forEach(b => b.addEventListener('click', async () => {
            const no = b.dataset.no;
            const r = await Swal.fire({ title: 'Remove ' + no + ' for good?', text: 'Type the inquiry number to confirm. This cannot be undone; the history is kept.', input: 'text', showCancelButton: true, confirmButtonText: 'Remove', confirmButtonColor: '#b23a32' });
            if (!r.isConfirmed) return;
            const j2 = await post(b.dataset.delete, { confirm: r.value || '' });
            if (j2.status !== 200) { toast('error', j2.message); return; }
            toast('success', j2.message); load();
        }));
    }

    // ---- Edit Requests (schema 33): who holds the authority, and what waits for an amendment ----
    const ER_ST = { open: ['pill-amber', 'being checked'], waiting: ['pill-blue', 'waiting for amendment'], applied: ['pill-green', 'applied'], cancelled: ['pill-grey', 'withdrawn'] };
    const cutw = (s, n) => { s = String(s || '').replace(/\s+/g, ' ').trim(); return s.length > n ? s.slice(0, n - 1) + '…' : s; };
    function renderEditRequests(box, d) {
        if (!d.installed) { box.innerHTML = '<div class="empty">Edit Requests need the schema 33 upgrade: the administrator opens Tour Inquiry once and installs it.</div>'; return; }
        const people = d.people || [], waiting = d.waiting || [], recent = d.recent || [];
        const on = people.filter(p => p.granted).length;
        box.innerHTML = `
            <div class="tf-card-b">
              <h6 style="margin:0 0 4px">Edit Request Authority <span class="help">· ${on} of ${people.length} switched on</span></h6>
              <div class="help mb-2">Who may make and confirm an Edit Request on an inquiry. Off for everyone until it is switched on here, one person at a time — the System Administrator included; being able to open an inquiry does not give it. On a confirmed tour a request still only waits for Management to open the tour with Amend, whoever makes it. Every switch is written to the Activity log.</div>
              <div style="overflow-x:auto"><table class="tbl" id="er_people"><thead><tr><th>Person</th><th>Signs in as</th><th>Group</th><th>Edit Request Authority</th><th>Last set</th><th></th></tr></thead><tbody>
                ${people.map(p => `<tr data-er-user="${p.user_id}"><td><b>${esc(p.name)}</b>${p.no_desk ? ' <span class="pill pill-amber" title="This account no longer holds a Tour Desk screen">no desk access</span>' : ''}</td><td class="mono">${esc(p.username || '')}</td><td>${esc(p.group || '—')}</td>
                    <td>${p.granted ? '<span class="pill pill-green">ON</span>' : '<span class="pill pill-grey">OFF</span>'}</td>
                    <td class="help">${p.set_at ? esc(when(p.set_at)) + ' by ' + esc(p.set_by || '') + (p.note ? ' — ' + esc(p.note) : '') : 'never set'}</td>
                    <td><button class="btn btn-sm ${p.granted ? 'btn-light' : 'btn-primary'}" data-er-switch="${p.user_id}" data-on="${p.granted ? 0 : 1}" data-name="${esc(p.name)}">${p.granted ? 'Turn off' : 'Turn on'}</button></td></tr>`).join('') || '<tr><td colspan="6" class="empty">No Tour Desk accounts.</td></tr>'}
              </tbody></table></div>

              <h6 style="margin:18px 0 4px">Waiting for amendment <span class="help">· ${waiting.length}</span></h6>
              <div class="help mb-2">Edit Requests saved on confirmed tours. They change nothing until the tour is opened with <b>Amend the quotation</b> on the <a href="${BOARD}">Tour Board</a>; then each is checked and confirmed on its inquiry like any other.</div>
              <div style="overflow-x:auto"><table class="tbl" id="er_waiting"><thead><tr><th>Inquiry</th><th>Request</th><th>Why</th><th>Asked</th></tr></thead><tbody>
                ${waiting.map(w => `<tr><td><a href="${INTAKE}?id=${w.intake_id}" style="white-space:nowrap"><b>${esc(w.inquiry_no)}</b></a><div class="help">${esc(w.tour_name || '')}</div></td>
                    <td><b>#${w.seq}</b> “${esc(cutw(w.pasted_text, 80))}” → “${esc(cutw(w.change_to, 80))}”</td><td>${esc(w.reason || '')}</td><td class="help">${esc(when(w.requested_at))} by ${esc(w.requested_by || '')}</td></tr>`).join('') || '<tr><td colspan="4" class="empty">Nothing waiting.</td></tr>'}
              </tbody></table></div>

              <h6 style="margin:18px 0 4px">Recent Edit Requests</h6>
              <div style="overflow-x:auto"><table class="tbl" id="er_recent"><thead><tr><th>Inquiry</th><th>Request</th><th>Status</th><th>Asked</th><th>Confirmed / withdrawn</th></tr></thead><tbody>
                ${recent.map(r => { const [c, w] = ER_ST[r.status] || ['pill-grey', r.status]; return `<tr><td style="white-space:nowrap"><a href="${INTAKE}?id=${r.intake_id}"><b>${esc(r.inquiry_no)}</b></a></td>
                    <td><b>#${r.seq}</b> “${esc(cutw(r.pasted_text, 70))}” → “${esc(cutw(r.change_to, 70))}”${r.reason ? `<div class="help">Why: ${esc(r.reason)}</div>` : ''}</td>
                    <td><span class="pill ${c}">${w}</span></td><td class="help">${esc(when(r.requested_at))} by ${esc(r.requested_by || '')}</td>
                    <td class="help">${r.confirmed_at ? esc(when(r.confirmed_at)) + ' by ' + esc(r.confirmed_by || '') : (r.cancelled_at ? esc(when(r.cancelled_at)) + ' by ' + esc(r.cancelled_by || '') : '')}</td></tr>`; }).join('') || '<tr><td colspan="5" class="empty">No Edit Request yet.</td></tr>'}
              </tbody></table></div>
            </div>`;
        box.querySelectorAll('[data-er-switch]').forEach(b => b.addEventListener('click', async () => {
            const turnOn = b.dataset.on === '1';
            const r = await Swal.fire({ title: (turnOn ? 'Turn Edit Request Authority ON for ' : 'Turn Edit Request Authority OFF for ') + b.dataset.name + '?',
                text: turnOn ? 'They will be able to make and confirm Edit Requests on the inquiries they can open. On a confirmed tour a request still waits for Amend.' : 'They will still see the requests, but not make or confirm one.',
                input: 'text', inputPlaceholder: 'A note for the log (optional)', showCancelButton: true, confirmButtonText: turnOn ? 'Turn on' : 'Turn off' });
            if (!r.isConfirmed) return;
            const j = await post('/authority/' + b.dataset.erSwitch, { on: turnOn, note: r.value || '' });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message);
            renderEditRequests(box, j.data);
        }));
    }

    function renderArchived(d) {
        const inq = d.inquiries || [], hc = d.hotel_cards || [], tc = d.transport_cards || [];
        const inqRows = inq.map(it => `<tr>
            <td class="mono"><a href="${INTAKE}?id=${it.id}">${esc(it.inquiry_no)}</a><div class="help">${esc(it.status)}</div></td>
            <td><b>${esc(it.agent_name || '—')}</b><div class="help">${esc(it.tour_name || '')}</div></td>
            <td>${esc(it.handler_name || '—')}</td>
            <td class="mono">${it.arrival_date ? fmt(it.arrival_date) : '—'}</td>
            <td>${when(it.archived_at)} · ${esc(it.archived_by || '')}${it.archive_note ? `<div class="help">${esc(it.archive_note)}</div>` : ''}</td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-restore="/intake/${it.id}/restore">Restore</button> <button class="btn btn-light btn-sm" style="color:var(--tf-red)" data-delete="/intake/${it.id}/delete" data-no="${esc(it.inquiry_no)}">Remove…</button></td></tr>`).join('');
        const hcRows = hc.map(c => `<tr>
            <td class="mono"><a href="${HOTEL}">${esc(c.tour_no)}</a><div class="help">${esc(c.tour_name || '')}</div></td>
            <td><b>${esc(c.hotel_name || 'no hotel named')}</b><div class="help">${esc(c.area || '')} · ${esc(c.status)}</div></td>
            <td>${esc(c.handler_name || '—')}</td>
            <td class="mono">${fmt(c.check_in)} → ${fmt(c.check_out)}</td>
            <td>${when(c.archived_at)} · ${esc(c.archived_by || '')}${c.archive_note ? `<div class="help">${esc(c.archive_note)}</div>` : ''}</td>
            <td><button class="btn btn-light btn-sm" data-restore="/hotel_card/${c.id}/restore">Restore</button></td></tr>`).join('');
        const tcRows = tc.map(c => `<tr>
            <td class="mono"><a href="${TRANSPORT}">${esc(c.tour_no)}</a><div class="help">${esc(c.tour_name || '')}</div></td>
            <td><b>${esc(c.vehicle_type)}</b><div class="help">${esc(c.vehicle_reg || 'no vehicle')} · ${esc(c.status)}</div></td>
            <td>${esc(c.handler_name || '—')}</td>
            <td class="mono">${fmt(c.arrival_date)} – ${fmt(c.departure_date)}</td>
            <td>${when(c.archived_at)} · ${esc(c.archived_by || '')}${c.archive_note ? `<div class="help">${esc(c.archive_note)}</div>` : ''}</td>
            <td><button class="btn btn-light btn-sm" data-restore="/transport_card/${c.id}/restore">Restore</button></td></tr>`).join('');
        const sc = d.service_cards || [], hotels = d.hotels || [], exc = d.excursions || [], sizes = d.vehicle_classes || [], tours = d.tours || [], deleted = d.deleted_tours || [];
        const delRows = deleted.map(t => `<tr><td class="mono"><b>${esc(t.tour_no)}</b><div class="help">${esc(t.tour_name || '')} · ${esc(t.agent_name || '')}</div></td><td>${esc(t.handler_name || '—')}</td><td class="mono">${t.arrival_date ? fmt(t.arrival_date) : '—'}</td><td>${when(t.archived_at)} · ${esc(t.archived_by || '')}${t.archive_note ? `<div class="help">${esc(t.archive_note)}</div>` : ''}<div class="help">${t.hotel_cards} hotel card(s) · ${t.transport_cards} transport card(s)</div></td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-restore="/tour/${t.id}/restore">Restore</button> <button class="btn btn-light btn-sm" style="color:var(--tf-red)" data-remove-tour="${t.id}" data-no="${esc(t.tour_no)}">Remove for good…</button></td></tr>`).join('');
        const scRows = sc.map(c => `<tr>
            <td class="mono"><a href="${HOTEL}">${esc(c.tour_no)}</a><div class="help">${esc(c.tour_name || '')}</div></td>
            <td><b>${esc(c.title)}</b><div class="help">${esc(c.kind)} · ${esc(c.status)}</div></td>
            <td>${esc(c.handler_name || '—')}</td>
            <td class="mono">${c.day_no ? 'Day ' + c.day_no : '—'}${c.on_date ? ' · ' + fmt(c.on_date) : ''}</td>
            <td>${when(c.archived_at)} · ${esc(c.archived_by || '')}${c.archive_note ? `<div class="help">${esc(c.archive_note)}</div>` : ''}</td>
            <td><button class="btn btn-light btn-sm" data-restore="/service_card/${c.id}/restore">Restore</button></td></tr>`).join('');
        const hotelRows = hotels.map(h => `<tr><td><b>${esc(h.name)}</b>${h.acc ? `<div class="help mono">${esc(h.acc)}</div>` : ''}</td><td class="help">inactive · ${when(h.updated_at)}</td><td><button class="btn btn-light btn-sm" data-restore="/hotel/${h.id}/restore">Restore</button></td></tr>`).join('');
        const excRows = exc.map(x => `<tr><td><b>${esc(x.name)}</b><div class="help mono">${esc(x.code)} · ${esc(x.currency)} ${esc(x.rate)}</div></td><td class="help">switched off · ${when(x.updated_at)} ${esc(x.updated_by || '')}</td><td><button class="btn btn-light btn-sm" data-restore="/excursion/${encodeURIComponent(x.code)}/restore">Switch on</button></td></tr>`).join('');
        const sizeRows = sizes.map(v => `<tr><td><b>${esc(v.name)}</b><div class="help mono">${esc(v.code)} · up to ${esc(v.max_pax)} pax</div></td><td class="help">retired</td><td><button class="btn btn-light btn-sm" data-restore="/vehicle_class/${encodeURIComponent(v.code)}/restore">Reinstate</button></td></tr>`).join('');
        const tourRows = tours.map(t => `<tr><td class="mono"><b>${esc(t.tour_no)}</b><div class="help">${esc(t.tour_name || '')} · ${esc(t.agent_name || '')}</div></td><td>${esc(t.handler_name || '—')}</td><td class="mono">${t.arrival_date ? fmt(t.arrival_date) : '—'}</td><td>${when(t.cancelled_at)} · ${esc(t.cancelled_by || '')}${t.cancel_why ? `<div class="help">${esc(t.cancel_why)}</div>` : ''}</td><td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-restore="/tour/${t.id}/reopen" data-why="Reopen tour ${esc(t.tour_no)}?">Reopen</button> <button class="btn btn-light btn-sm" style="color:var(--tf-red)" data-delete-tour="${t.id}" data-no="${esc(t.tour_no)}">Delete…</button></td></tr>`).join('');
        return `
            <div class="tf-card-b help">Everything that was put away, in one place, each with its way back. Nothing here was deleted by itself; a record that could be removed for good (an archived inquiry with nothing attached) says so on its row.</div>
            <div class="tf-card-b"><h6 style="font-size:13px">Inquiries <span class="help">${inq.length}</span></h6></div>
            <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Inquiry</th><th>Customer · group</th><th>Handling agent</th><th>Arrival</th><th>Archived</th><th></th></tr></thead>
            <tbody>${inqRows || '<tr><td colspan="6" class="empty">No archived inquiries.</td></tr>'}</tbody></table></div>
            <div class="tf-card-b"><h6 style="font-size:13px">Hotel cards <span class="help">${hc.length}</span></h6></div>
            <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Tour</th><th>Stay</th><th>Handling agent</th><th>Nights</th><th>Archived</th><th></th></tr></thead>
            <tbody>${hcRows || '<tr><td colspan="6" class="empty">No archived hotel cards.</td></tr>'}</tbody></table></div>
            <div class="tf-card-b"><h6 style="font-size:13px">Transport cards <span class="help">${tc.length}</span></h6></div>
            <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Tour</th><th>Vehicle</th><th>Handling agent</th><th>Dates</th><th>Archived</th><th></th></tr></thead>
            <tbody>${tcRows || '<tr><td colspan="6" class="empty">No archived transport cards.</td></tr>'}</tbody></table></div>
            <div class="tf-card-b"><h6 style="font-size:13px">Activity &amp; ticket lines <span class="help">${sc.length}</span></h6></div>
            <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Tour</th><th>Line</th><th>Handling agent</th><th>When</th><th>Archived</th><th></th></tr></thead>
            <tbody>${scRows || '<tr><td colspan="6" class="empty">No archived activity or ticket lines.</td></tr>'}</tbody></table></div>
            <div class="tf-card-b"><h6 style="font-size:13px">Deleted tours <span class="help">${deleted.length}</span></h6><div class="help">Cancelled and archived with their inquiry — off every desk and count, kept whole. Restore brings the tour and its inquiry back (still cancelled); Remove for good takes the tour, its cards, letters and trail lines away and leaves one line on the Activity log.</div></div>
            <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Tour</th><th>Handling agent</th><th>Arrival</th><th>Deleted</th><th></th></tr></thead>
            <tbody>${delRows || '<tr><td colspan="5" class="empty">No deleted tours.</td></tr>'}</tbody></table></div>
            <div class="tf-card-b"><h6 style="font-size:13px">Cancelled tours <span class="help">${tours.length}</span></h6></div>
            <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Tour</th><th>Handling agent</th><th>Arrival</th><th>Cancelled</th><th></th></tr></thead>
            <tbody>${tourRows || '<tr><td colspan="5" class="empty">No cancelled tours.</td></tr>'}</tbody></table></div>
            <div class="tf-card-b"><h6 style="font-size:13px">Hotels set inactive <span class="help">${hotels.length}</span></h6></div>
            <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Hotel</th><th>Status</th><th></th></tr></thead>
            <tbody>${hotelRows || '<tr><td colspan="3" class="empty">No inactive hotels.</td></tr>'}</tbody></table></div>
            <div class="tf-card-b"><h6 style="font-size:13px">Excursion rates switched off <span class="help">${exc.length}</span></h6></div>
            <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Excursion</th><th>Status</th><th></th></tr></thead>
            <tbody>${excRows || '<tr><td colspan="3" class="empty">None switched off.</td></tr>'}</tbody></table></div>
            <div class="tf-card-b"><h6 style="font-size:13px">Vehicle sizes retired <span class="help">${sizes.length}</span></h6></div>
            <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Size</th><th>Status</th><th></th></tr></thead>
            <tbody>${sizeRows || '<tr><td colspan="3" class="empty">None retired.</td></tr>'}</tbody></table></div>`;
    }

    // ---- the Tours tab: every tour on the desks, with cancel, delete and reopen ----
    const STAGE = { confirmed: ['Confirmed', 'pill-blue'], operating: ['Operating', 'pill-teal'], completed: ['Completed', 'pill-green'], closed: ['Closed', 'pill-grey'], cancelled: ['Cancelled', 'pill-grey'] };
    function renderTours(rows) {
        const work = (t, k) => { if (t.stage === 'cancelled') return '<span class="help">—</span>'; const w = t.strip && t.strip[k]; return w === 'complete' ? '<span class="pill pill-green">complete</span>' : (w === 'pending' ? '<span class="pill pill-amber">pending</span>' : '<span class="help">—</span>'); };
        const trs = rows.map(t => { const [sl, sc] = STAGE[t.stage] || [t.stage, 'pill-grey']; const live = t.stage !== 'cancelled'; return `<tr>
            <td class="mono"><b>${esc(t.tour_no)}</b><div class="help">${esc(t.tour_name || '')} · ${esc(t.agent_name || '')}</div></td>
            <td>${esc(t.handler_name || '—')}</td>
            <td class="mono">${t.arrival_date ? fmt(t.arrival_date) : '—'}${t.departure_date ? `<div class="help">to ${fmt(t.departure_date)}</div>` : ''}</td>
            <td><span class="pill ${sc}">${esc(sl)}</span>${Number(t.amending) ? ' <span class="pill pill-amber">amending</span>' : ''}${t.problems ? ` <span class="pill pill-red">${t.problems} problem${t.problems === 1 ? '' : 's'}</span>` : ''}<div class="help">${live ? 'confirmed ' + when(t.confirmed_at) + ' · ' + esc(t.confirmed_by || '') : 'cancelled ' + when(t.cancelled_at) + ' · ' + esc(t.cancelled_by || '') + (t.cancel_why ? ' — ' + esc(t.cancel_why) : '')}</div></td>
            <td>${work(t, 'hotel_work')}</td><td>${work(t, 'transport_work')}</td>
            <td style="white-space:nowrap"><a class="btn btn-light btn-sm" href="${HOTEL}?tour=${t.id}" title="Open on Hotel Bookings"><i class="bi bi-door-open"></i></a> <a class="btn btn-light btn-sm" href="${TRANSPORT}?tour=${t.id}" title="Open on Transport"><i class="bi bi-truck"></i></a> <a class="btn btn-light btn-sm" href="${INTAKE}?id=${t.intake_id}" title="Open the inquiry"><i class="bi bi-file-text"></i></a>
                ${live ? `<button class="btn btn-light btn-sm" data-cancel-tour="${t.id}" data-no="${esc(t.tour_no)}">Cancel…</button>` : `<button class="btn btn-light btn-sm" data-reopen-tour="${t.id}" data-no="${esc(t.tour_no)}">Reopen…</button>`}
                <button class="btn btn-light btn-sm" style="color:var(--tf-red)" data-delete-tour="${t.id}" data-no="${esc(t.tour_no)}">Delete…</button></td></tr>`; }).join('');
        return `<div class="tf-card-b help">Every tour the desks hold, live and cancelled. <b>Cancel</b> marks every card and takes the tour off both counts; <b>Reopen</b> undoes a cancellation; <b>Delete</b> cancels it and archives it with its inquiry — off every list, kept whole, restorable under Deleted / archived, where it can also be removed for good. Each asks for your password and a reason; every action is on the Activity log with your name.</div>
            <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Tour</th><th>Handling agent</th><th>Dates</th><th>Stage</th><th>Hotel work</th><th>Transport work</th><th></th></tr></thead>
            <tbody>${trs || '<tr><td colspan="7" class="empty">No tour on the desks.</td></tr>'}</tbody></table></div>`;
    }
    function wireTourActions(box) {
        box.querySelectorAll('[data-cancel-tour]').forEach(b => b.addEventListener('click', async () => {
            // Management only, in three steps: the password, the reason, the final warning — then every card is marked
            const pw = await askPassword('Cancel tour ' + b.dataset.no, 'Cancelling marks every hotel and transport card cancelled and takes the tour off both desks\' counts. Enter your own sign-in password to go on; it is checked and not kept.', 'Cancel tour ' + b.dataset.no);
            if (pw === null) return;
            const why = await Swal.fire({ title: 'Why is ' + b.dataset.no + ' cancelled?', input: 'text', inputPlaceholder: 'The reason (required, stays on the trail and on every card)', inputValidator: v => (v && v.trim() ? undefined : 'The reason is required.'), showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#b23a32' });
            if (!why.isConfirmed) return;
            const fin = await Swal.fire({ icon: 'warning', title: 'Cancel tour ' + b.dataset.no + '?', html: `<div style="text-align:left;font-size:13.5px"><div style="background:#fbe9e6;border:1px solid #f0b8b0;border-radius:8px;padding:8px 12px;margin-bottom:10px;color:#8a2a22"><b>Last warning.</b> Every hotel, activity and transport card is marked <b>Cancelled</b> for the desks with your reason, the tour leaves the live board and counts for nothing on either desk. Work already done stays on the trail; it stays on the lists under Cancelled and can be reopened, at the cards' first step.</div>Reason: <b>${esc(why.value.trim())}</b><br>Cancelled by <b>${esc(ME_NAME)}</b>, now; the name and time go on the tour and the Activity log.</div>`, showCancelButton: true, cancelButtonText: 'Keep the tour', confirmButtonText: 'Cancel the tour', confirmButtonColor: '#b23a32', focusCancel: true });
            if (!fin.isConfirmed) return;
            const j = await post('/tour/' + b.dataset.cancelTour + '/cancel', { why: why.value.trim(), password: pw });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
        box.querySelectorAll('[data-reopen-tour]').forEach(b => b.addEventListener('click', async () => {
            // Reopen undoes a cancellation: Management only, in the same three steps as Cancel — the password, the reason, the last warning
            const pw = await askPassword('Reopen tour ' + b.dataset.no, 'Reopening undoes the cancellation: the tour returns to the live board and to both desks at the cards\' first step. Enter your own sign-in password to go on; it is checked and not kept.', 'Reopen tour ' + b.dataset.no);
            if (pw === null) return;
            const why = await Swal.fire({ title: 'Why is ' + b.dataset.no + ' reopened?', input: 'text', inputPlaceholder: 'The reason (required, stays on the trail)', inputValidator: v => (v && v.trim() ? undefined : 'The reason is required.'), showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#1f7a4d' });
            if (!why.isConfirmed) return;
            const fin = await Swal.fire({ icon: 'warning', title: 'Reopen tour ' + b.dataset.no + '?', html: `<div style="text-align:left;font-size:13.5px"><div style="background:#fbe9e6;border:1px solid #f0b8b0;border-radius:8px;padding:8px 12px;margin-bottom:10px;color:#8a2a22"><b>Last warning.</b> The cancellation is undone. The tour is back on the live board and on Hotel Bookings and Transport, every card at its first step; the cancellation and this reopen stay on the trail.</div>Reason: <b>${esc(why.value.trim())}</b><br>Reopened by <b>${esc(ME_NAME)}</b>, now; the name, the time and the reason go on the Activity log.</div>`, showCancelButton: true, cancelButtonText: 'Leave it cancelled', confirmButtonText: 'Reopen the tour', confirmButtonColor: '#1f7a4d', focusCancel: true });
            if (!fin.isConfirmed) return;
            const j = await post('/tour/' + b.dataset.reopenTour + '/reopen', { why: why.value.trim(), password: pw });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
        box.querySelectorAll('[data-delete-tour]').forEach(b => b.addEventListener('click', async () => {
            const pw = await askPassword('Delete tour ' + b.dataset.no, 'Deleting cancels the tour and archives it with its inquiry. Enter your own sign-in password to go on; it is checked and not kept.', 'Delete tour ' + b.dataset.no);
            if (pw === null) return;
            const r = await Swal.fire({ title: 'Delete tour ' + b.dataset.no + '?', html: '<div style="text-align:left;font-size:13.5px">Are you sure you want to delete this record? The tour is cancelled and archived with its inquiry: off Inquiries, Hotel Bookings, Transport, the Board and every red count. Nothing is removed — it is kept whole under Deleted / archived and can be restored from there.</div>', input: 'text', inputPlaceholder: 'Why (required, stays on the trail)', inputValidator: v => (v && v.trim() ? undefined : 'The reason is required.'), showCancelButton: true, confirmButtonText: 'Delete the tour', confirmButtonColor: '#b23a32' });
            if (!r.isConfirmed) return;
            const j = await post('/tour/' + b.dataset.deleteTour + '/delete', { why: r.value || '', password: pw });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
        box.querySelectorAll('[data-remove-tour]').forEach(b => b.addEventListener('click', async () => {
            const info = await api('/tour/' + b.dataset.removeTour + '/removal');
            if (info.status !== 200) { toast('error', info.message); return; }
            const d = info.data;
            const inqOk = d.inquiry && !(d.inquiry.blocks || []).length;
            const r = await Swal.fire({ title: 'Remove ' + b.dataset.no + ' for good?', html: `<div style="text-align:left;font-size:13.5px">This cannot be undone. It takes away <b>${d.hotel_cards}</b> hotel card(s), <b>${d.transport_cards}</b> transport card(s), <b>${d.activity_lines}</b> activity line(s), <b>${d.letters}</b> letter(s) and <b>${d.trail_lines}</b> trail line(s). One line on the Activity log records it.${d.inquiry ? `<br><br><label style="display:flex;gap:6px;align-items:flex-start"><input type="checkbox" id="rm_inq" ${inqOk ? 'checked' : 'disabled'}><span>Also remove inquiry <b>${esc(d.inquiry.inquiry_no)}</b> for good${inqOk ? '' : ' — not possible: ' + esc((d.inquiry.blocks || []).join('; '))}</span></label>` : ''}<br>Type the tour number to confirm.</div>`, input: 'text', inputPlaceholder: b.dataset.no, showCancelButton: true, confirmButtonText: 'Remove for good', confirmButtonColor: '#b23a32', preConfirm: () => ({ confirm: Swal.getInput().value, with_inquiry: document.getElementById('rm_inq') && document.getElementById('rm_inq').checked ? 1 : 0 }) });
            if (!r.isConfirmed) return;
            const body = r.value && typeof r.value === 'object' ? r.value : { confirm: r.value || '', with_inquiry: 0 };
            const j = await post('/tour/' + b.dataset.removeTour + '/remove', body);
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); load();
        }));
    }

    function renderSettings(n, co, ss, iw, et, mb, ib, sc) {
        mb = mb || {}; ib = ib || {}; sc = sc || {};
        return `<div class="tf-card-b">
            <h6 style="font-size:13px">Tour numbers</h6>
            <div class="help mb-2">A tour takes its number when it is confirmed: the prefix, the year, and a count that starts again each year, padded to the digits below. Tours already confirmed keep their number. Every change here is on the Activity log.</div>
            <div class="row g-2" style="max-width:640px">
                <div class="col-md-3"><label class="form-label help">Prefix</label><input class="form-control form-control-sm" id="n_prefix" value="${esc(n.tour_prefix || '')}" maxlength="8"></div>
                <div class="col-md-3"><label class="form-label help">Year of the count</label><input class="form-control form-control-sm" value="${esc(n.tour_year || '—')}" disabled title="Set by the first tour confirmed each year"></div>
                <div class="col-md-3"><label class="form-label help">Next count</label><input type="number" min="1" class="form-control form-control-sm" id="n_next" value="${esc(n.tour_next || '1')}"></div>
                <div class="col-md-3"><label class="form-label help">Digits</label><input type="number" min="1" max="8" class="form-control form-control-sm" id="n_pad" value="${esc(n.tour_pad || '5')}"></div>
            </div>
            <div class="mt-2 help">The next tour confirmed will be <b class="mono">${esc(n.example || '')}</b>${n.last_tour_no ? ` · the last one was <span class="mono">${esc(n.last_tour_no)}</span>` : ''}. A number already on a tour is never given twice, whatever the count says.</div>
            <div class="mt-2"><button class="btn btn-primary btn-sm" id="n_save">Save numbering</button></div>
        </div>
        <div class="tf-card-b" style="border-top:1px solid var(--tf-line)">
            <h6 style="font-size:13px">The company's lines on hotel booking requests and vouchers</h6>
            <div class="help mb-2">What heads every letter the Hotel Desk sends a hotel, and signs it off. The person sending adds their own name, mobile and mailbox from their account. Every change here is on the Activity log.</div>
            <div class="row g-2" style="max-width:760px">
                <div class="col-md-6"><label class="form-label help">Company name</label><input class="form-control form-control-sm" id="co_name" value="${esc(co.name || '')}" maxlength="120"></div>
                <div class="col-md-6"><label class="form-label help">Telephone</label><input class="form-control form-control-sm" id="co_phone" value="${esc(co.phone || '')}" maxlength="120"></div>
                <div class="col-12"><label class="form-label help">Address</label><input class="form-control form-control-sm" id="co_address" value="${esc(co.address || '')}" maxlength="255"></div>
                <div class="col-12"><label class="form-label help">Email shown on the letter</label><input class="form-control form-control-sm" id="co_email" value="${esc(co.email || '')}" maxlength="255" placeholder="the office mailbox hotels should reply to"></div>
                <div class="col-12"><label class="form-label help">Copy every e-mail to (CC)</label><input class="form-control form-control-sm" id="co_always_cc" value="${esc(co.always_cc || '')}" maxlength="500" placeholder="e.g. iqbaal@avenirholidays.com, dsm@avenirholidays.com">
                    <div class="help">Every e-mail the system sends — hotel booking requests and vouchers, restaurant vouchers, quotations, invoices — is copied to these addresses. Leave empty to copy nobody.</div></div>
            </div>
            <div class="mt-2"><button class="btn btn-primary btn-sm" id="co_save">Save company lines</button></div>
        </div>
        <div class="tf-card-b" style="border-top:1px solid var(--tf-line)" id="mb_card">
            <h6 style="font-size:13px">System mailbox and standard signature — e-mails sent from Communications &amp; History</h6>
            <div class="help mb-2">An e-mail composed on an inquiry's or tour's Communications &amp; History page goes from this company mailbox, signed with the sender's name and the standard signature; the sender's own address goes on Reply-To so the answer reaches them. With no mailbox here, it goes from the person's own mailbox (My Profile → Mailbox). The password is checked with the mail server before it is saved and is never shown again. ${mb.sysadmin ? '' : '<b>Only the System Administrator changes this.</b>'}</div>
            <div class="row g-2" style="max-width:760px">
                <div class="col-md-6"><label class="form-label help">Mailbox address</label><input class="form-control form-control-sm" id="mb_email" value="${esc(mb.email || '')}" maxlength="120" placeholder="e.g. traveljp@avenirholidays.com" ${mb.sysadmin ? '' : 'disabled'}></div>
                <div class="col-md-6"><label class="form-label help">Sender name shown</label><input class="form-control form-control-sm" id="mb_name" value="${esc(mb.name || 'Avenir Holidays')}" maxlength="80" ${mb.sysadmin ? '' : 'disabled'}></div>
                <div class="col-md-6"><label class="form-label help">Mailbox password ${mb.password_state === 'sealed' ? '<span class="text-success">— saved, accepted by the server, encrypted at rest</span>' : mb.password_state === 'plain' ? '<span class="text-warning">— saved before encryption; enter it again to encrypt it</span>' : mb.password_state === 'unreadable' ? '<span class="text-danger">— saved under another server\'s key; enter it again</span>' : '<span class="text-danger">— not saved yet</span>'}</label><input type="password" class="form-control form-control-sm" id="mb_pass" autocomplete="new-password" placeholder="${mb.has_password ? 'leave blank to keep the saved one' : 'type it to check and save'}" ${mb.sysadmin ? '' : 'disabled'}></div>
                <div class="col-md-6"><label class="form-label help">Mail server</label><div class="form-control form-control-sm bg-light">${esc(mb.server || 'decided by the address (company domain → company server)')}</div></div>
                <div class="col-12"><label class="form-label help">Standard signature — {me} the sender's name, {mobile}, {my_email}, {company}, {address}, {phone}, {email}, {web} are filled in; a line whose words are all empty is left out</label><textarea class="form-control form-control-sm mono" id="mb_sig" rows="5" ${mb.sysadmin ? '' : 'disabled'}>${esc(mb.signature || '')}</textarea>
                    <div class="help">As it would read for you now:</div><pre class="mono help" style="white-space:pre-wrap;background:#f7f5fa;padding:8px 10px;border-radius:8px" id="mb_preview">${esc(mb.signature_preview || '')}</pre></div>
            </div>
            ${mb.sysadmin ? `<div class="mt-2 d-flex gap-2"><button class="btn btn-primary btn-sm" id="mb_save">Save system mailbox and signature</button>${mb.email ? '<button class="btn btn-light btn-sm" id="mb_clear">Remove the system mailbox</button>' : ''}</div>` : ''}
        </div>
        <div class="tf-card-b" style="border-top:1px solid var(--tf-line)" id="ib_card">
            <h6 style="font-size:13px">Incoming mailbox — replies collected automatically into Communications &amp; History</h6>
            <div class="help mb-2">The TourFlow mailbox is read by IMAP (read-only: nothing is flagged, moved or deleted), every message once, seen or unseen. A reply is filed on its record only by a certain reference — the Message-ID of an e-mail sent from here, the <span class="mono">[AVH:INQ 1038]</span> tag, or the <span class="mono">AVH-REF</span> conversation key — and anything else waits in the <a href="${esc(BASE.replace(/tour\/manage.*$/, '') + 'tour/intake/comms/inbox')}">Incoming e-mails queue</a>. Checked when a Communications page opens (at most every 5 minutes) and, on UAT / production, by a scheduled task every few minutes so mail is collected when nobody is signed in. ${ib.imap_ext ? '' : '<b class="text-danger">The PHP imap extension is not loaded on this server.</b>'} ${ib.sysadmin ? '' : '<b>Only the System Administrator changes this.</b>'}</div>
            <div class="row g-2" style="max-width:900px">
                <div class="col-md-4"><label class="form-label help">IMAP server</label><input class="form-control form-control-sm" id="ib_host" value="${esc(ib.host || '')}" maxlength="120" placeholder="e.g. imap.lolipop.jp" ${ib.sysadmin ? '' : 'disabled'}></div>
                <div class="col-md-2"><label class="form-label help">Port</label><input class="form-control form-control-sm" id="ib_port" value="${esc(ib.port || 993)}" ${ib.sysadmin ? '' : 'disabled'}></div>
                <div class="col-md-2"><label class="form-label help">Security</label><select class="form-select form-select-sm" id="ib_secure" ${ib.sysadmin ? '' : 'disabled'}><option value="ssl" ${ib.secure === 'ssl' || !ib.secure ? 'selected' : ''}>SSL (993)</option><option value="tls" ${ib.secure === 'tls' ? 'selected' : ''}>STARTTLS (143)</option><option value="none" ${ib.secure === 'none' ? 'selected' : ''}>none</option></select></div>
                <div class="col-md-4"><label class="form-label help">Folder</label><input class="form-control form-control-sm" id="ib_folder" value="${esc(ib.folder || 'INBOX')}" ${ib.sysadmin ? '' : 'disabled'}></div>
                <div class="col-md-6"><label class="form-label help">Mailbox address</label><input class="form-control form-control-sm" id="ib_email" value="${esc(ib.email || '')}" maxlength="120" placeholder="e.g. tourflow-test@avenirholidays.com" ${ib.sysadmin ? '' : 'disabled'}></div>
                <div class="col-md-6"><label class="form-label help">Mailbox password ${ib.password_state === 'sealed' ? '<span class="text-success">— saved, accepted by the server, encrypted at rest</span>' : ib.password_state === 'unreadable' ? '<span class="text-danger">— saved under another server\'s key; enter it again</span>' : '<span class="text-danger">— not saved yet</span>'}</label><input type="password" class="form-control form-control-sm" id="ib_pass" autocomplete="new-password" placeholder="${ib.password_state === 'sealed' ? 'leave blank to keep the saved one' : 'type it to check and save'}" ${ib.sysadmin ? '' : 'disabled'}></div>
                <div class="col-md-6"><label class="help"><input type="checkbox" id="ib_enabled" ${ib.enabled !== false ? 'checked' : ''} ${ib.sysadmin ? '' : 'disabled'}> collect mail from this mailbox</label><br><label class="help"><input type="checkbox" id="ib_auto" ${ib.auto_quote ? 'checked' : ''} ${ib.sysadmin ? '' : 'disabled'}> read an unmatched e-mail that looks like a quotation request into a new inquiry and a <b>draft</b> quotation for staff review (never issued or sent by itself)</label></div>
                <div class="col-md-6"><div class="help">Last check: <b>${esc(ib.last_check || 'never')}</b>${ib.last_result ? ' — ' + esc(ib.last_result) : ''}<br>Last UID read: ${esc(ib.last_uid || 0)}</div></div>
                <div class="col-12"><div class="help"><b>Scheduled task (UAT / production):</b> run <span class="mono">${esc(ib.spark || 'php spark tourflow:inbox')}</span> every 5 minutes from the server's own scheduler (Windows Task Scheduler / cron). The web entry <span class="mono">POST ${esc(ib.poll_url || '')}</span> with the token in the <span class="mono">X-TourFlow-Token</span> header is <b>${ib.web_poll ? 'on' : 'off'}</b> (off by default; only the token's hash is kept, the token is shown once) ${ib.has_token ? '· token set' : '· no token yet'}. ${ib.sysadmin ? '<label class="help"><input type="checkbox" id="ib_web" ' + (ib.web_poll ? 'checked' : '') + '> web entry on</label> <label class="help"><input type="checkbox" id="ib_token"> make a new token (shown once)</label>' : ''}</div></div>
            </div>
            ${ib.sysadmin ? `<div class="mt-2 d-flex gap-2"><button class="btn btn-primary btn-sm" id="ib_save">Save incoming mailbox</button></div>` : ''}
        </div>
        <div class="tf-card-b" style="border-top:1px solid var(--tf-line)" id="sc_card">
            <h6 style="font-size:13px">Passwords at rest — this install's encryption key</h6>
            <div class="help mb-2">Mailbox passwords (the system mailbox, the incoming mailbox and every staff member's own mailbox) are encrypted with a key that exists only in this install's .env file. Each environment — development, UAT, production — generates its own; a key is never copied between them, shown or logged, and the encrypted passwords are left out of the TourFlow backup copies. After a deployment the passwords are entered again once.</div>
            <div class="help">Key for this install: ${sc.key_ready ? '<b class="text-success">set</b>' : '<b class="text-danger">not set — passwords cannot be saved until it is generated</b>'} · staff mailbox passwords: <b>${esc((sc.users || {}).sealed || 0)}</b> encrypted, <b class="${(sc.users || {}).plain ? 'text-warning' : ''}">${esc((sc.users || {}).plain || 0)}</b> still plain text, ${esc((sc.users || {}).unreadable || 0)} saved under another key, ${esc((sc.users || {}).none || 0)} without one.</div>
            ${sc.sysadmin ? `<div class="mt-2 d-flex gap-2">${sc.key_ready ? '' : '<button class="btn btn-primary btn-sm" id="sc_key">Generate this environment\'s encryption key</button>'}${sc.key_ready && (sc.users || {}).plain ? '<button class="btn btn-primary btn-sm" id="sc_migrate">Encrypt the ' + esc((sc.users || {}).plain) + ' plain-text staff mailbox password(s) now</button>' : ''}</div>` : ''}
        </div>
        <div class="tf-card-b" style="border-top:1px solid var(--tf-line)">
            <h6 style="font-size:13px">Sightseeing with nothing to pay, and the priced list's other names</h6>
            ${ss.master ? `<div class="help mb-2">Entrances and safaris are decided by the <b>Place to Visit / Entrance Fee Master</b> alone${ss.master_url ? ' (<a href="' + esc(BASE.replace(/tour\/manage.*$/, '') + ss.master_url) + '" target="_blank">open it</a>)' : ''}: a place there with a valid rate is charged, one marked Free is free, and an entrance or safari that is not there is held — never free; their other names are kept on each place there. Ordinary sightseeing is outside it, with no entrance charge. The words and aliases below serve only the lines outside the master — meals, services, activities and other stops — exactly as before. Every change is on the Activity log.</div>`
                : `<div class="help mb-2">A day service is priced from the entrance fee master when the list holds a positive amount for it; a stop whose words are below (S/S, market, shopping …) and that the list does not price is <b>no charge</b> — on the programme, nothing costed, no warning; anything else is left unsure and offered No charge, the closest entry or the master, never a forced price. The aliases map what an agent writes to an entry in the list, one per line: <span class="mono">temple of the tooth =&gt; KDY ENT</span>. Every change is on the Activity log.</div>`}
            <div class="row g-2" style="max-width:900px">
                <div class="col-md-6"><label class="form-label help">No-charge words (comma separated)</label><textarea class="form-control form-control-sm" id="ss_words" rows="4">${esc(ss.no_charge_words || '')}</textarea></div>
                <div class="col-md-6"><label class="form-label help">Aliases — phrase =&gt; CODE, one per line</label><textarea class="form-control form-control-sm mono" id="ss_aliases" rows="4">${esc(ss.aliases || '')}</textarea></div>
            </div>
            <div class="mt-2"><button class="btn btn-primary btn-sm" id="ss_save">Save sightseeing settings</button></div>
        </div>
        <div class="tf-card-b" style="border-top:1px solid var(--tf-line)">
            <h6 style="font-size:13px">How the itinerary is written</h6>
            <div class="help mb-2">Every itinerary — the client quotation and the driver's copy — is written to the professional standard from these sentences: arrival → meet and assist → hotel; breakfast → leave for the destination → visits en route → sightseeing at the destination → hotel; transfer → departure flight. The night's area, hotel, visits and excursions (in the order the desk put them) and the flights are filled in — <span class="mono">Upon arrival at Bandaranaike International Airport on SriLankan Airlines flight UL 504, you will be met and assisted by our local representative. Thereafter, transfer to your hotel in Negombo.</span> A word in braces is filled in; each sentence says which it may carry. Separate alternatives with <span class="mono">&nbsp;|&nbsp;</span> and the writer takes them in turn from one day to the next, so the wording varies naturally. Nothing is invented: an unknown flight is left out, an airline not on the list is printed as the agent wrote it, a day with no programme is a day at leisure. Leave a sentence blank to go back to the standard one. A person can write their own wording on any night of an inquiry; a quotation version already issued keeps the wording it went out with. Every change is on the Activity log.</div>
            <div class="row g-2" style="max-width:1000px">
                ${Object.keys(iw.defaults || {}).map(k => (iw.multiline || []).includes(k)
                    ? `<div class="col-12"><label class="form-label help">${esc((iw.labels || {})[k] || k)}</label><textarea class="form-control form-control-sm mono" data-itin="${k}" rows="5" maxlength="4000" placeholder="${esc((iw.defaults || {})[k] || '')}">${esc((iw.values || {})[k] ?? '')}</textarea></div>`
                    : `<div class="${['itin_airport','itin_representative','itin_morning','itin_morning_no_bf','itin_flight_in','itin_flight_in_raw','itin_flight_out','itin_flight_out_raw'].includes(k) ? 'col-md-6' : 'col-12'}"><label class="form-label help">${esc((iw.labels || {})[k] || k)}</label><input class="form-control form-control-sm" data-itin="${k}" value="${esc((iw.values || {})[k] ?? '')}" placeholder="${esc((iw.defaults || {})[k] || '')}" maxlength="500"></div>`).join('')}
            </div>
            <div class="mt-2"><button class="btn btn-primary btn-sm" id="iw_save">Save itinerary wording</button> <button class="btn btn-light btn-sm" id="iw_reset" title="Every sentence back to the standard one">Standard sentences</button></div>
        </div>
        <div class="tf-card-b" style="border-top:1px solid var(--tf-line)" id="et_card">
            <h6 style="font-size:13px">Email templates</h6>
            <div class="help mb-2">The ready-made emails every send window offers — the quotation email, the hotel letters, and the invoice, reminder, confirmation and general emails as their windows come. Choosing one fills the subject and the message; staff edit before sending. A word in braces is filled in from the inquiry or the tour: ${Object.keys(et.words || {}).map(w => `<span class="mono" title="${esc((et.words || {})[w])}">{${esc(w)}}</span>`).join(', ')}. The <b>default</b> of a category is the one its window starts with. A template is archived, never deleted, so a send that used it keeps its trail. Every change is on the Activity log.</div>
            ${et.available === false ? '<div class="help" style="color:var(--tf-red)">The templates table is not installed yet: open Tour Inquiry as the administrator and press Install now.</div>' : ''}
            <div style="overflow-x:auto"><table class="tbl" id="et_table"><thead><tr><th>Template</th><th>Category</th><th>Subject</th><th></th></tr></thead><tbody>
                ${(et.rows || []).filter(t => !t.archived_at).map(t => `<tr data-et="${t.id}"><td><b>${esc(t.name)}</b>${Number(t.is_default) ? ' <span class="pill pill-green">default</span>' : ''}<div class="help">${esc((t.message || '').slice(0, 90))}${(t.message || '').length > 90 ? '…' : ''}</div></td><td>${esc((et.categories || {})[t.category] || t.category)}</td><td class="mono" style="font-size:12px">${esc(t.subject)}</td>
                    <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-et-edit="${t.id}">Edit</button> <button class="btn btn-light btn-sm" data-et-archive="${t.id}" data-name="${esc(t.name)}">Archive</button></td></tr>`).join('') || '<tr><td colspan="4" class="empty">No template yet.</td></tr>'}
            </tbody></table></div>
            ${(et.rows || []).some(t => t.archived_at) ? `<details class="mt-2"><summary class="help">Archived templates (${(et.rows || []).filter(t => t.archived_at).length})</summary><div style="overflow-x:auto"><table class="tbl"><tbody>
                ${(et.rows || []).filter(t => t.archived_at).map(t => `<tr><td><b>${esc(t.name)}</b><div class="help">archived ${when(t.archived_at)} · ${esc(t.archived_by || '')}</div></td><td>${esc((et.categories || {})[t.category] || t.category)}</td><td class="mono" style="font-size:12px">${esc(t.subject)}</td><td><button class="btn btn-light btn-sm" data-et-restore="${t.id}">Restore</button></td></tr>`).join('')}
            </tbody></table></div></details>` : ''}
            <div class="mt-2"><button class="btn btn-primary btn-sm" id="et_new"><i class="bi bi-plus-lg"></i> New template</button></div>
            <div id="et_form" class="mt-3" style="display:none; max-width:1000px; border-top:1px solid var(--tf-line); padding-top:10px">
                <h6 style="font-size:13px" id="et_form_title">New template</h6>
                <input type="hidden" id="et_id" value="">
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label help">Template name</label><input class="form-control form-control-sm" id="et_name" maxlength="120" placeholder="e.g. Quotation – Standard"></div>
                    <div class="col-md-4"><label class="form-label help">Category</label><select class="form-select form-select-sm" id="et_category">${Object.keys(et.categories || {}).map(k => `<option value="${k}">${esc((et.categories || {})[k])}</option>`).join('')}</select></div>
                    <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" id="et_default"><label class="form-check-label help" for="et_default">Default of its category</label></div></div>
                    <div class="col-12"><label class="form-label help">Default subject</label><input class="form-control form-control-sm" id="et_subject" maxlength="255"></div>
                    <div class="col-12"><label class="form-label help">Default email message</label><textarea class="form-control form-control-sm" id="et_message" rows="9" maxlength="8000"></textarea></div>
                </div>
                <div class="mt-2"><button class="btn btn-primary btn-sm" id="et_save">Save template</button> <button class="btn btn-light btn-sm" id="et_cancel">Cancel</button></div>
            </div>
        </div>
        <div class="tf-card-b" style="border-top:1px solid var(--tf-line)" id="bk_card">
            <h6 style="font-size:13px">Copies of the TourFlow tables</h6>
            <div class="help mb-2">Before any upgrade the installer writes a copy of every TourFlow table, with the TourFlow menu entries and who holds them, to <span class="mono">writable/tf_backups</span> on the server, a folder closed to the web, and does not upgrade if it cannot. A copy can be taken here too, before a big change. Run in phpMyAdmin, a copy puts the TourFlow tables back as they were at that moment; everything done in TourFlow after it would be lost, so it is the last resort. Undoing a release does not need it: each release's rollback kit puts the previous program files back and changes no data.</div>
            <div id="bk_list" class="help">Reading…</div>
            <div class="mt-2"><button class="btn btn-light btn-sm" id="bk_take"><i class="bi bi-hdd"></i> Take a copy now</button></div>
        </div>
        <div class="tf-card-b" style="border-top:1px solid var(--tf-line)">
            <h6 style="font-size:13px">Driver and guide bata</h6>
            <div class="help">The driver and guide types (category and language) and the bata per day are maintained under <a href="${CREW_MASTER}">Master Files → Crew Bata</a> — its own privilege, granted to the System Administrator by the installer and to any other group under Settings → Group Privileges. The quotation screen shows the bata and the amount and cannot change them.</div>
        </div>`;
    }

    async function loadBackups() {
        const el = document.getElementById('bk_list');
        if (!el) return;
        const j = await api('/backups');
        const rows = (j.data && j.data.backups) || [];
        const size = b => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
        el.innerHTML = j.status !== 200 ? esc(j.message || 'The copies could not be read.')
            : (rows.length ? `<table class="tbl" style="max-width:900px"><thead><tr><th>Copy</th><th>Taken</th><th>By</th><th>Rows</th><th>Size</th></tr></thead><tbody>${rows.map(b => `<tr data-backup="${esc(b.file)}"><td class="mono">${esc(b.file)}</td><td>${esc(b.at || '')}</td><td>${esc(b.by || '')}</td><td class="mono">${b.rows ?? '—'}</td><td class="mono">${size(Number(b.bytes || 0))}</td></tr>`).join('')}</tbody></table>`
               : 'No copy has been taken yet.');
    }

    function renderActivity(rows) {
        return `<div class="tf-card-b">${window.tdHistory(rows.map(r => Object.assign({ lane: ({ inquiry: 'quotation', pricing: 'quotation', quote: 'quotation', hotel_job: 'hotel', transport_job: 'transport', service_job: 'service', hotel: 'hotel', hotel_rate: 'hotel', master: 'tour', customer: 'tour', log: 'tour' })[r.entity] || 'tour' }, r)), { empty: 'Nothing recorded for these filters.', link: INTAKE })}</div>`;
    }

    document.querySelectorAll('[data-tab]').forEach(c => c.addEventListener('click', () => { TAB = c.dataset.tab; load(); }));
    document.getElementById('f_q').addEventListener('input', () => { clearTimeout(window.__mq); window.__mq = setTimeout(load, 250); });
    ['f_handler', 'f_by', 'f_from', 'f_to'].forEach(id => document.getElementById(id).addEventListener('change', load));
    document.getElementById('f_clear').addEventListener('click', () => { ['f_handler', 'f_by', 'f_q', 'f_from', 'f_to'].forEach(id => document.getElementById(id).value = ''); load(); });
    load();
})();
</script>
</body>
</html>
